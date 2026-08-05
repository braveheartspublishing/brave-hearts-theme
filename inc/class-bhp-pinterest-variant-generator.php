<?php
/**
 * Brave Hearts Publishing — Phase 1E: programmatic Pinterest 4-variant generator.
 *
 * Extends the existing content-engine Pinterest schema (see
 * content-engine/templates/design-brief.template.json and
 * content-engine/scripts/validate-design-brief.php) with a generator
 * that produces the four required creative hypotheses from a content
 * brief, rather than requiring each design-brief.json to be hand-written
 * as the one existing example (mariana-trench-facts-for-kids) was. This
 * does not replace the existing schema or validator -- it targets the
 * exact same shape so the existing validator still applies unchanged.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Pinterest_Variant_Generator {

	const VARIANT_TYPES = array( 'problem-led', 'outcome-led', 'curiosity-led', 'resource-led' );

	const PROHIBITED_PATTERNS = array(
		'/\bguarantee(d)?\b/i', '/\bclinically\b/i', '/\bproven to\b/i', '/\baward[- ]winning\b/i',
		'/\bbest[- ]selling\b/i', '/\blimited time\b/i', '/\b#1\b/', '/\bbest\b/i', '/\bonly\b.*\bever\b/i',
	);

	/**
	 * @param array $brief   Output of BHP_Content_Brief_Generator::generate().
	 * @param array $seo     Output of BHP_SEO_Metadata_Package::generate().
	 * @param int   $version Revision number, mirrors utm_content versioning.
	 */
	public static function generate( array $brief, array $seo, $version = 1 ) {
		$slug        = $brief['blog_slug'];
		$destination = home_url( '/blog/' . $slug . '/' );
		$campaign    = self::infer_campaign( $brief );
		$board       = self::infer_board( $brief );

		$templates = array(
			'problem-led' => array(
				'headline'        => 'Struggling to find ' . $brief['primary_keyword'] . ' your child actually wants to read?',
				'supporting_line' => '[PLACEHOLDER: one line naming the specific frustration this content solves]',
			),
			'outcome-led' => array(
				'headline'        => 'Watch your child beg for "just one more chapter" — ' . ucwords( $brief['primary_keyword'] ),
				'supporting_line' => '[PLACEHOLDER: one line describing the concrete result a parent/teacher gets]',
			),
			'curiosity-led' => array(
				'headline'        => '[PLACEHOLDER: a genuinely surprising, factually-checked hook related to ' . $brief['primary_keyword'] . ']',
				'supporting_line' => '[PLACEHOLDER: the follow-through fact or question]',
			),
			'resource-led' => array(
				'headline'        => 'A real-science guide to ' . ucwords( $brief['primary_keyword'] ) . ' for kids',
				'supporting_line' => '[PLACEHOLDER: what the reader gets by saving this pin -- a list, a printable, a guide]',
			),
		);

		$variants = array();
		foreach ( self::VARIANT_TYPES as $type ) {
			$utm_content = sprintf( 'blog-%s_%s_v%d', $slug, $type, (int) $version );
			$variants[]  = array(
				'variant_type'          => $type,
				'audience'              => $brief['target_audience'],
				'funnel_stage'          => $brief['funnel_stage'],
				'campaign_id'           => $campaign,
				'headline'              => $templates[ $type ]['headline'],
				'supporting_copy'       => $templates[ $type ]['supporting_line'],
				'visual_direction'      => '[PLACEHOLDER: see content-engine/config/brand-guidelines.yaml for voice/color/typography constraints]',
				'image_generation_prompt' => '[PLACEHOLDER: describe the desired image literally; must not depict a real, identifiable child or fabricated classroom scene]',
				'approved_subject'      => $brief['featured_book'] ?: 'series_general',
				'prohibited_visual_claims' => array( 'fake reviews/stars', 'fabricated "as seen in" logos', 'unverifiable before/after reading claims' ),
				'pinterest_title'       => $seo['pinterest_title'],
				'pinterest_description' => $seo['pinterest_description'],
				'alt_text'              => '[PLACEHOLDER: literal description of the pin image]',
				'destination_url'       => $destination,
				'utm_source'            => 'pinterest',
				'utm_medium'            => 'organic_social',
				'utm_campaign'          => $campaign,
				'utm_content'           => $utm_content,
				'board_id'              => $board,
				'cta'                   => $brief['primary_cta']['id'] ?? '',
				'featured_book'         => $brief['featured_book'],
				'lead_offer'            => $brief['lead_offer'],
				'claim_validation_status' => 'pending_review',
				'quality_score'         => null,
			);
		}

		$validation = self::validate( $variants );

		return array(
			'blog_slug' => $slug,
			'variants'  => $variants,
			'validation' => $validation,
		);
	}

	private static function infer_campaign( array $brief ) {
		$book_campaign_map = array(
			'mariana_trench' => 'ocean_explorers',
			'mount_everest'  => 'mountain_explorers',
			'amazon_rainforest' => 'rainforest_explorers',
		);
		return $book_campaign_map[ $brief['featured_book'] ] ?? 'reluctant_reader_transition';
	}

	private static function infer_board( array $brief ) {
		$audience_board_map = array(
			'teacher'   => 'classroom_read_alouds',
			'parent'    => 'reluctant_readers',
			'homeschool' => 'homeschool_science_reading',
		);
		return $audience_board_map[ $brief['target_audience'] ] ?? 'adventure_chapter_books';
	}

	/**
	 * Validates the four-variant structure. Mirrors the checks already
	 * implemented in content-engine/scripts/validate-design-brief.php so
	 * results agree; re-implemented here as a pure PHP method (no CLI
	 * dependency) for use inside the QA gate.
	 */
	public static function validate( array $variants ) {
		$findings = array();

		$types_present = wp_list_pluck( $variants, 'variant_type' );
		foreach ( self::VARIANT_TYPES as $required_type ) {
			if ( ! in_array( $required_type, $types_present, true ) ) {
				$findings[] = "Missing required variant type: {$required_type}";
			}
		}
		if ( count( $types_present ) !== count( array_unique( $types_present ) ) ) {
			$findings[] = 'Duplicate variant type detected -- each of the 4 types must appear exactly once.';
		}

		$headlines = array();
		foreach ( $variants as $variant ) {
			foreach ( array( 'headline', 'supporting_copy', 'pinterest_title', 'pinterest_description', 'alt_text', 'destination_url', 'utm_content' ) as $field ) {
				if ( empty( $variant[ $field ] ) ) {
					$findings[] = "Variant '{$variant['variant_type']}' missing required field: {$field}";
				}
			}
			$headlines[] = strtolower( trim( (string) $variant['headline'] ) );

			$expected_pattern = '/^blog-.+_(problem-led|outcome-led|curiosity-led|resource-led)_v\d+$/';
			if ( ! preg_match( $expected_pattern, $variant['utm_content'] ) ) {
				$findings[] = "Variant '{$variant['variant_type']}' has malformed utm_content: {$variant['utm_content']}";
			}

			foreach ( self::PROHIBITED_PATTERNS as $pattern ) {
				if ( preg_match( $pattern, $variant['headline'] . ' ' . $variant['supporting_copy'] ) ) {
					$findings[] = "Variant '{$variant['variant_type']}' contains a prohibited claim pattern.";
				}
			}
		}
		if ( count( $headlines ) !== count( array_unique( $headlines ) ) ) {
			$findings[] = 'Two or more variants have identical headlines -- variants must be genuinely distinct.';
		}

		return array(
			'findings' => $findings,
			'state'    => empty( $findings ) ? 'pass' : 'revise',
		);
	}

	public static function export_json( array $brief, array $seo, $version = 1 ) {
		$result = self::generate( $brief, $seo, $version );
		$dir    = get_template_directory() . '/content-engine/blogs/' . $brief['blog_slug'];
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		file_put_contents( $dir . '/design-brief-generated.json', wp_json_encode( $result, JSON_PRETTY_PRINT ) ); // phpcs:ignore -- local build artifact.
		return $result;
	}
}
