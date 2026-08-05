<?php
/**
 * Brave Hearts Publishing — Phase 1E: Author Fingerprint package import/validation.
 *
 * Consumes the JSON handoff file produced by brave-hearts-seo-engine's
 * `bhp-seo package export-approved` command (see that repo's
 * packets/approved_package_export.py). This class never parses a DOCX
 * manuscript, never stores raw manuscript text, and never talks to the
 * seo-engine's SQLite database directly -- the JSON file is the entire
 * contract between the two repositories.
 *
 * Checksum note: the exported file carries a sha256 `checksum_sha256`
 * computed in Python over a canonicalized JSON body. This class
 * deliberately does NOT attempt to bit-for-bit recompute that hash in
 * PHP -- JSON canonicalization (unicode escaping, forward-slash
 * escaping, recursive key ordering) differs enough between Python's
 * `json.dumps` and PHP's `json_encode` that a naive re-implementation
 * would be fragile and could silently "pass" a corrupted file or
 * "fail" a genuine one. Instead: (1) the field's presence and 64-hex-
 * char shape is validated structurally, (2) a PHP-side sha256 of the
 * raw imported file's bytes is stored locally at import time and used
 * to detect the LOCAL file changing after import (a real, honestly
 * achievable guarantee), and (3) schema_version + required-field
 * completeness are the actual gates. This tradeoff is documented, not
 * silently glossed over.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Author_Fingerprint_Package {

	const SUPPORTED_SCHEMA_VERSIONS = array( 1 );

	const REQUIRED_TOP_LEVEL_FIELDS = array(
		'schema_version', 'package_uuid', 'content_brief_id', 'generated_at',
		'checksum_sha256', 'provenance', 'brief', 'research_packet',
		'corpus_manifest', 'brand_voice_profile', 'author_connection',
		'author_fingerprint_check', 'book_corpus_grounding', 'global_prohibited_uses',
	);

	const MANDATORY_CORPUS_KEYS = array( 'brand_identity', 'founder_life_story', 'volume_1_manuscript', 'volume_2_manuscript' );

	// Anecdote-reuse rotation controls (Workstream: "Anecdote retrieval and
	// rotation"). Deliberately simple, auditable heuristics -- no semantic
	// embedding model exists in this stack, so "similarity" is a normalized
	// exact/near-exact text match, not true semantic similarity.
	const MAX_REUSE_COUNT             = 3;
	const MIN_ARTICLES_BEFORE_REUSE   = 2; // at least this many other anecdote_usage rows since the last use of this exact anecdote, tracked locally.

	/**
	 * Structural schema validation -- required fields present, schema
	 * version supported, checksum shaped correctly, corpus manifest
	 * carries all four mandatory keys, no raw manuscript text smuggled
	 * in (a defensive length/keyset check, not a content-scan).
	 *
	 * @return array List of human-readable issues; empty = structurally valid.
	 */
	public static function validate_schema( array $package ) {
		$issues = array();

		foreach ( self::REQUIRED_TOP_LEVEL_FIELDS as $field ) {
			if ( ! array_key_exists( $field, $package ) ) {
				$issues[] = "Missing required field: {$field}";
			}
		}
		if ( ! empty( $issues ) ) {
			return $issues; // no point checking sub-structure of a field that isn't there.
		}

		if ( ! in_array( (int) $package['schema_version'], self::SUPPORTED_SCHEMA_VERSIONS, true ) ) {
			$issues[] = "Unsupported schema_version: {$package['schema_version']} (supported: " . implode( ', ', self::SUPPORTED_SCHEMA_VERSIONS ) . ')';
		}
		if ( ! preg_match( '/^[a-f0-9]{64}$/i', (string) $package['checksum_sha256'] ) ) {
			$issues[] = 'checksum_sha256 is not a well-formed 64-character hex sha256 digest.';
		}
		if ( empty( $package['package_uuid'] ) ) {
			$issues[] = 'package_uuid is empty.';
		}

		$manifest_keys = wp_list_pluck( (array) $package['corpus_manifest'], 'mandatory_key' );
		foreach ( self::MANDATORY_CORPUS_KEYS as $key ) {
			if ( ! in_array( $key, $manifest_keys, true ) ) {
				$issues[] = "corpus_manifest is missing the mandatory source: {$key}";
			}
		}
		foreach ( (array) $package['corpus_manifest'] as $entry ) {
			if ( isset( $entry['text'] ) || isset( $entry['content'] ) || isset( $entry['file_content'] ) ) {
				$issues[] = 'corpus_manifest entry carries a raw text/content field -- manuscript text must never be embedded here.';
			}
		}

		if ( ! is_array( $package['brand_voice_profile'] ) || empty( $package['brand_voice_profile']['source_id'] ) ) {
			$issues[] = 'brand_voice_profile is missing or has no source_id.';
		}

		return $issues;
	}

	/**
	 * Imports a JSON handoff file into local, content-engine-style storage
	 * (mirrors the existing content-engine/blogs/<slug>/*.json convention
	 * -- never a new, parallel storage mechanism). Idempotent: importing
	 * the same package_uuid twice updates the same local record rather
	 * than creating a duplicate.
	 *
	 * @return array|WP_Error Structured result, or WP_Error with a
	 *                         'schema_issues' error-data array on failure.
	 */
	public static function import_from_file( $file_path, $dry_run = false ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'bhp_afp_file_not_found', "File not found: {$file_path}" );
		}
		$raw = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local file, not a remote request.
		$package = json_decode( $raw, true );
		if ( null === $package || ! is_array( $package ) ) {
			return new WP_Error( 'bhp_afp_invalid_json', 'File does not contain valid JSON.' );
		}

		$issues = self::validate_schema( $package );
		if ( ! empty( $issues ) ) {
			return new WP_Error( 'bhp_afp_schema_invalid', 'Package failed schema validation: ' . implode( '; ', $issues ), $issues );
		}

		$local_file_hash = hash( 'sha256', $raw );

		if ( $dry_run ) {
			return array(
				'dry_run'         => true,
				'package_uuid'    => $package['package_uuid'],
				'content_brief_id' => $package['content_brief_id'],
				'schema_version'  => $package['schema_version'],
				'local_file_hash' => $local_file_hash,
				'issues'          => array(),
			);
		}

		$dir = get_template_directory() . '/content-engine/author-packages';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$local_path = $dir . '/' . sanitize_file_name( $package['package_uuid'] ) . '.json';
		file_put_contents( $local_path, $raw ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local build artifact, matches existing content-engine/ convention.

		$manifest_path = $dir . '/index.json';
		$index = file_exists( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$index[ $package['package_uuid'] ] = array(
			'content_brief_id' => $package['content_brief_id'],
			'imported_at'      => current_time( 'mysql', true ),
			'local_file_hash'  => $local_file_hash,
			'local_path'       => $package['package_uuid'] . '.json',
		);
		file_put_contents( $manifest_path, wp_json_encode( $index, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return array(
			'dry_run'          => false,
			'package_uuid'     => $package['package_uuid'],
			'content_brief_id' => $package['content_brief_id'],
			'schema_version'   => $package['schema_version'],
			'local_file_hash'  => $local_file_hash,
			'local_path'       => $local_path,
			'issues'           => array(),
		);
	}

	/** Reads back a previously imported package by its UUID. */
	public static function get( $package_uuid ) {
		$dir = get_template_directory() . '/content-engine/author-packages';
		$path = $dir . '/' . sanitize_file_name( $package_uuid ) . '.json';
		if ( ! file_exists( $path ) ) {
			return null;
		}
		return json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Full validation used by the draft-creation gate: schema validity
	 * PLUS the editorial content of the package (corpus loaded, Author
	 * Connection complete, Fingerprint Check passed, anecdote reuse
	 * within threshold, brand voice profile present).
	 *
	 * @return array List of field-labeled issues; empty = eligible.
	 */
	public static function validate_for_draft_gate( array $package ) {
		$issues = self::validate_schema( $package );
		if ( ! empty( $issues ) ) {
			return array_map( static function ( $i ) { return array( 'field' => 'author_package.schema', 'reason' => $i ); }, $issues );
		}

		$field_issues = array();
		$add = static function ( $field, $reason ) use ( &$field_issues ) {
			$field_issues[] = array( 'field' => $field, 'reason' => $reason );
		};

		foreach ( self::MANDATORY_CORPUS_KEYS as $key ) {
			$entry = self::manifest_entry( $package, $key );
			if ( null === $entry ) {
				$add( "author_package.corpus.{$key}", 'Not present in corpus_manifest.' );
			} elseif ( empty( $entry['checksum_sha256'] ) ) {
				$add( "author_package.corpus.{$key}", 'No checksum recorded for this source at export time.' );
			}
		}

		if ( empty( $package['author_connection'] ) ) {
			$add( 'author_package.author_connection', 'No Author Connection anecdote selected for this article.' );
		} else {
			$ac = $package['author_connection'];
			if ( empty( $ac['full_text'] ) ) {
				$add( 'author_package.author_connection.full_text', 'Author Connection has no text.' );
			}
			if ( empty( $ac['source_passage'] ) ) {
				$add( 'author_package.author_connection.source', 'Author Connection has no recorded source passage.' );
			}
			if ( ( $ac['reuse_count'] ?? 0 ) > self::MAX_REUSE_COUNT ) {
				$add( 'author_package.author_connection.reuse_count', "Anecdote '{$ac['anecdote_key']}' has been used {$ac['reuse_count']} times, exceeding the max of " . self::MAX_REUSE_COUNT . '.' );
			}
		}

		$fingerprint = $package['author_fingerprint_check'] ?? array();
		if ( empty( $fingerprint['passed'] ) ) {
			$add( 'author_package.fingerprint_check', 'Author Fingerprint Check did not pass (' . wp_json_encode( array(
				'prohibited_matches' => $fingerprint['prohibited_matches'] ?? array(),
				'overused_anecdotes' => $fingerprint['overused_anecdotes'] ?? array(),
			) ) . ').' );
		}

		if ( empty( $package['brand_voice_profile']['source_id'] ) ) {
			$add( 'author_package.brand_voice', 'Brand voice profile not loaded.' );
		}

		return $field_issues;
	}

	private static function manifest_entry( array $package, $mandatory_key ) {
		foreach ( (array) ( $package['corpus_manifest'] ?? array() ) as $entry ) {
			if ( ( $entry['mandatory_key'] ?? null ) === $mandatory_key ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Runs the brand-voice heuristic check against a body of generated
	 * text -- mirrors brave-hearts-seo-engine's
	 * authorship/brand_voice.py::check_brand_voice() pattern (kept as a
	 * parallel PHP implementation since WordPress cannot invoke Python
	 * directly; the pattern list is deliberately kept in sync by hand,
	 * documented in docs/phase1e-content-intelligence-architecture.md).
	 */
	public static function check_brand_voice( $text ) {
		$patterns = array(
			array( '/^\s*\d+\s+(reasons|ways|tips|things)\b/i', 'listicle-style title/opening' ),
			array( '/\breading is (so )?important\b/i', 'generic "reading is important" messaging' ),
			array( '/\bguarantee(d)?\b/i', 'guarantee language' ),
			array( '/\bproven to\b/i', 'unsupported-outcome / hype claim' ),
			array( '/\b(unlock|unleash|supercharge|game-chang(er|ing))\b/i', 'marketing hype vocabulary' ),
			array( '/!{2,}/', 'performative multi-exclamation-point enthusiasm' ),
			array( '/\bin today\'s (fast-paced|digital) world\b/i', 'corporate/press-release throat-clearing' ),
		);
		$matches = array();
		foreach ( $patterns as $p ) {
			if ( preg_match( $p[0], $text ) ) {
				$matches[] = $p[1];
			}
		}
		return array( 'forbidden_matches' => $matches, 'passed' => empty( $matches ) );
	}

	/** Detects an obvious, low-effort re-insertion of first-person author text without genuine first-person voice signals. */
	public static function has_first_person_author_voice( $text ) {
		return (bool) preg_match( '/\bI\s+(wrote|remember|learned|grew up|worked|traveled|chose|decided|visited|climbed|dove|dived)\b/i', $text );
	}
}
