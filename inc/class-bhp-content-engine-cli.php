<?php
/**
 * Brave Hearts Publishing — Phase 1E: WP-CLI automation commands.
 *
 * Three safety levels, matching docs/phase1e-content-intelligence-architecture.md:
 *   Level 1 (automatic)              — import, score, report, UTM/dupe checks
 *   Level 2 (automatic with review)  — recommendations, briefs, metadata, Pinterest variants
 *   Level 3 (explicit approval only) — draft creation, metadata changes to
 *                                       existing posts, link insertion, publishing
 *
 * Every Level 3 command REQUIRES --approved-by=<name>; every command
 * supports --dry-run where a real write would otherwise occur. Nothing
 * here can publish a post, publish GTM, or write to production --
 * those actions simply do not exist as commands in this class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class BHP_Content_Engine_CLI {

	/**
	 * Imports an analytics CSV or JSON file. Level 1 (automatic).
	 *
	 * ## OPTIONS
	 * <source>
	 * : gsc|ga4|pinterest|woocommerce
	 *
	 * <file>
	 * : Path to a .csv or .json file.
	 *
	 * [--label=<label>]
	 * [--fixture]
	 *
	 * ## EXAMPLES
	 *     wp bhp-content import gsc /path/export.csv --label="June GSC export"
	 */
	public function import( $args, $assoc_args ) {
		list( $source, $file ) = $args;
		if ( ! file_exists( $file ) ) {
			WP_CLI::error( "File not found: {$file}" );
		}
		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- CLI reads a local operator-supplied file, not a request input.
		$is_json  = 'json' === strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

		$result = $is_json
			? BHP_Analytics_Adapter::import_json( $source, $contents, array( 'label' => $assoc_args['label'] ?? '', 'is_fixture' => isset( $assoc_args['fixture'] ) ) )
			: BHP_Analytics_Adapter::import_csv( $source, $contents, array( 'label' => $assoc_args['label'] ?? '', 'is_fixture' => isset( $assoc_args['fixture'] ) ) );

		WP_CLI::log( sprintf( 'Imported %d row(s), rejected %d row(s).', $result['imported'], $result['rejected'] ) );
		foreach ( $result['errors'] as $error ) {
			WP_CLI::warning( is_array( $error ) ? "Row {$error['row']}: {$error['reason']}" : $error );
		}
		if ( $result['import_id'] ) {
			WP_CLI::success( "Import batch post ID: {$result['import_id']}" );
		}
	}

	/**
	 * Imports the safe built-in fixtures for a source. Level 1.
	 *
	 * ## OPTIONS
	 * <source>
	 * : gsc|ga4|pinterest|woocommerce
	 */
	public function import_fixtures( $args, $assoc_args ) {
		list( $source ) = $args;
		$rows   = BHP_Analytics_Adapter::fixture_rows( $source );
		$result = BHP_Analytics_Adapter::import_rows( $source, $rows, array( 'label' => 'Built-in fixture', 'is_fixture' => true ) );
		WP_CLI::success( sprintf( 'Imported %d fixture row(s) for %s (import ID %d).', $result['imported'], $source, $result['import_id'] ) );
	}

	/**
	 * Builds and prints/exports the content inventory. Level 1.
	 *
	 * [--export]
	 * [--limit=<n>]
	 */
	public function inventory( $args, $assoc_args ) {
		$limit = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 300;
		$inventory = BHP_Content_Inventory::build( $limit );
		WP_CLI::log( sprintf( 'Inventoried %d item(s).', $inventory['content_count'] ) );
		WP_CLI::log( sprintf( 'Orphan pages: %d | Missing metadata: %d | Missing classification: %d | Possible cannibalization groups: %d',
			count( $inventory['gaps']['orphan_pages'] ), count( $inventory['gaps']['missing_metadata'] ),
			count( $inventory['gaps']['missing_classification'] ), count( $inventory['gaps']['possible_keyword_cannibalization'] ) ) );
		if ( isset( $assoc_args['export'] ) ) {
			$path = BHP_Content_Inventory::export_json( $limit );
			WP_CLI::success( "Exported to {$path}" );
		}
	}

	/**
	 * Runs the opportunity engine over the current inventory and prints
	 * the top N recommendations. Level 1 (scoring/report) — does NOT
	 * add anything to the queue by itself; use `add-to-queue` for that.
	 *
	 * [--top=<n>]
	 */
	public function opportunities( $args, $assoc_args ) {
		$inventory = BHP_Content_Inventory::build();
		$scored    = BHP_Content_Opportunity_Engine::score_inventory( $inventory );
		$top       = array_slice( $scored, 0, isset( $assoc_args['top'] ) ? (int) $assoc_args['top'] : 10 );

		foreach ( $top as $result ) {
			WP_CLI::log( sprintf(
				'[%s] score=%s conf=%s -> %s (%s)',
				$result['url'],
				null === $result['score'] ? 'n/a' : $result['score'],
				$result['confidence'],
				$result['recommendation']['type'],
				$result['recommendation']['reason']
			) );
		}
	}

	/**
	 * Lists production-queue items. Level 1 (read-only).
	 *
	 * [--status=<status>]
	 * [--audience=<audience>]
	 * [--page=<n>]
	 */
	public function queue_list( $args, $assoc_args ) {
		$filters = array_intersect_key( $assoc_args, array_flip( array( 'status', 'audience', 'funnel_stage', 'content_intent' ) ) );
		$result  = BHP_Content_Production_Queue::list_items( $filters, isset( $assoc_args['page'] ) ? (int) $assoc_args['page'] : 1, 20 );
		foreach ( $result['items'] as $item ) {
			WP_CLI::log( sprintf( '#%d [%s] %s — status=%s approval=%s', $item['queue_id'], $item['recommendation_type'], $item['target_url'] ?: $item['proposed_slug'], $item['status'], $item['approval_status'] ) );
		}
		WP_CLI::log( sprintf( 'Page %d of %d (%d total)', $result['page'], max( 1, $result['total_pages'] ), $result['total'] ) );
	}

	/**
	 * Moves a queue item to a new status. Approval-gated statuses
	 * REQUIRE --approved-by. Level 2/3 depending on target status.
	 *
	 * <queue_id>
	 * <status>
	 * [--approved-by=<name>]
	 */
	public function queue_transition( $args, $assoc_args ) {
		list( $queue_id, $status ) = $args;
		$result = BHP_Content_Production_Queue::transition( (int) $queue_id, $status, $assoc_args['approved-by'] ?? null );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( "Queue item #{$queue_id} -> {$status}" );
	}

	/**
	 * Generates a content brief for an approved (or any) queue item.
	 * Level 2 (automatic with review — writes a brief file, does not
	 * touch WordPress content).
	 *
	 * <queue_id>
	 */
	public function generate_brief( $args ) {
		list( $queue_id ) = $args;
		$brief = BHP_Content_Brief_Generator::generate( (int) $queue_id );
		if ( is_wp_error( $brief ) ) {
			WP_CLI::error( $brief->get_error_message() );
		}
		WP_CLI::success( "Brief written for blog_slug={$brief['blog_slug']}" );
	}

	/**
	 * Generates a placeholder SCAFFOLD (never real prose) + SEO package +
	 * Pinterest variants for a brief already on disk, and runs the QA
	 * gate against the scaffold so its (expected) failures are visible
	 * early. Level 2 -- writes local JSON artifacts only, never touches
	 * WordPress content. The scaffold is never eligible for
	 * create-wp-draft; see assemble-article-draft.
	 *
	 * <blog_slug>
	 * [--dry-run]
	 */
	public function generate_draft_package( $args, $assoc_args ) {
		list( $slug ) = $args;
		$brief_path = get_template_directory() . "/content-engine/blogs/{$slug}/content-brief.json";
		if ( ! file_exists( $brief_path ) ) {
			WP_CLI::error( "No content-brief.json found for slug '{$slug}'. Run generate-brief first." );
		}
		$brief = json_decode( file_get_contents( $brief_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$inventory_items = BHP_Content_Inventory::build()['items'];
		$seo             = BHP_SEO_Metadata_Package::generate( $brief, $inventory_items );
		$draft           = BHP_Blog_Draft_Generator::generate( $brief );
		$pinterest       = BHP_Pinterest_Variant_Generator::generate( $brief, $seo );
		$qa              = BHP_Content_QA_Gate::evaluate( $brief, $draft, $seo, $pinterest );

		if ( isset( $assoc_args['dry-run'] ) ) {
			WP_CLI::log( wp_json_encode( array( 'seo' => $seo, 'qa' => $qa ), JSON_PRETTY_PRINT ) );
			return;
		}

		BHP_Blog_Draft_Generator::write_draft_file( $draft );
		BHP_Pinterest_Variant_Generator::export_json( $brief, $seo );
		WP_CLI::success( "Scaffold package generated for {$slug} (draft-scaffold.json -- placeholders only, NOT eligible for create-wp-draft). QA overall status: {$qa['overall_status']}" );
		foreach ( $qa['checks'] as $name => $check ) {
			WP_CLI::log( sprintf( '  %-32s %s (%s)', $name, $check['state'], $check['check_type'] ) );
		}
	}

	/**
	 * Assembles the real ARTICLE DRAFT from actual prose supplied in a
	 * local JSON file (never fabricated by this system), runs SEO +
	 * Pinterest + QA, and writes article-draft.json. This is the only
	 * artifact create-wp-draft will accept. Level 2 -- local only.
	 *
	 * <blog_slug>
	 * <prose_json_path>
	 * : Path to a JSON file matching BHP_Blog_Draft_Generator::assemble_article_draft()'s $prose shape.
	 * [--dry-run]
	 */
	public function assemble_article_draft( $args, $assoc_args ) {
		list( $slug, $prose_path ) = $args;
		$brief_path = get_template_directory() . "/content-engine/blogs/{$slug}/content-brief.json";
		if ( ! file_exists( $brief_path ) ) {
			WP_CLI::error( "No content-brief.json found for slug '{$slug}'. Run generate-brief first." );
		}
		if ( ! file_exists( $prose_path ) ) {
			WP_CLI::error( "Prose file not found: {$prose_path}" );
		}
		$brief = json_decode( file_get_contents( $brief_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$prose = json_decode( file_get_contents( $prose_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$draft = BHP_Blog_Draft_Generator::assemble_article_draft( $brief, (array) $prose );
		if ( is_wp_error( $draft ) ) {
			WP_CLI::error( $draft->get_error_message() );
		}

		$markup_errors = BHP_Blog_Draft_Generator::validate_markup( $draft['content_html'] );
		if ( ! empty( $markup_errors ) ) {
			WP_CLI::error( 'Assembled draft has invalid Gutenberg markup: ' . implode( '; ', $markup_errors ) );
		}

		$inventory_items = BHP_Content_Inventory::build()['items'];
		$seo             = BHP_SEO_Metadata_Package::generate( $brief, $inventory_items );
		$pinterest       = BHP_Pinterest_Variant_Generator::generate( $brief, $seo );
		$qa              = BHP_Content_QA_Gate::evaluate( $brief, $draft, $seo, $pinterest );

		if ( isset( $assoc_args['dry-run'] ) ) {
			WP_CLI::log( wp_json_encode( array( 'draft' => $draft, 'qa' => $qa ), JSON_PRETTY_PRINT ) );
			return;
		}

		BHP_Blog_Draft_Generator::write_article_draft_file( $draft );
		WP_CLI::success( "Article draft assembled for {$slug} (article-draft.json). QA overall status: {$qa['overall_status']}" );
		foreach ( $qa['checks'] as $name => $check ) {
			WP_CLI::log( sprintf( '  %-32s %s (%s)', $name, $check['state'], $check['check_type'] ) );
		}
	}

	/**
	 * Creates ONE WordPress staging draft from an ASSEMBLED article draft
	 * (article-draft.json) -- never from the placeholder scaffold.
	 * Level 3 -- REQUIRES --approved-by. Blocked unless the FULL
	 * publishing package (taxonomy, SEO, classification, images,
	 * internal links, Pinterest, analytics, editorial governance, QA)
	 * clears BHP_Draft_Package_Builder::validate_complete() -- every
	 * missing/invalid field is reported by name, and NONE of them are
	 * silently defaulted. Always post_status=draft.
	 *
	 * <queue_id>
	 * <blog_slug>
	 * --approved-by=<name>
	 * --factual-review-confirmed-by=<name>
	 * : Required. A named human confirming every factual claim in the
	 *   brief's factual_claims_requiring_verification has actually been
	 *   checked. Never inferred by this command.
	 * --audience-fit-confirmed-by=<name>
	 * : Required. A named human confirming tone/reading-level fit.
	 * --author-package-uuid=<uuid>
	 * : Required. UUID of an Author Fingerprint package already imported via
	 *   `wp bhp-content import-approved-package`. Every article requires
	 *   canonical brand/founder grounding and an Author Connection -- there
	 *   is no way to bypass this.
	 * [--originality-confirmations=<comma,separated,keys>]
	 */
	public function create_wp_draft( $args, $assoc_args ) {
		list( $queue_id, $slug ) = $args;
		if ( empty( $assoc_args['approved-by'] ) ) {
			WP_CLI::error( 'This action requires --approved-by=<name>.' );
		}
		if ( empty( $assoc_args['factual-review-confirmed-by'] ) ) {
			WP_CLI::error( 'This action requires --factual-review-confirmed-by=<name> -- factual claims must be human-checked before any WordPress draft is created.' );
		}
		if ( empty( $assoc_args['audience-fit-confirmed-by'] ) ) {
			WP_CLI::error( 'This action requires --audience-fit-confirmed-by=<name>.' );
		}
		if ( empty( $assoc_args['author-package-uuid'] ) ) {
			WP_CLI::error( 'This action requires --author-package-uuid=<uuid> -- run `wp bhp-content import-approved-package` first. Every article requires canonical brand/founder grounding.' );
		}
		$author_package = BHP_Author_Fingerprint_Package::get( $assoc_args['author-package-uuid'] );
		if ( null === $author_package ) {
			WP_CLI::error( "No imported Author Fingerprint package found for UUID '{$assoc_args['author-package-uuid']}'. Run import-approved-package first." );
		}

		$brief_path = get_template_directory() . "/content-engine/blogs/{$slug}/content-brief.json";
		$draft_path = get_template_directory() . "/content-engine/blogs/{$slug}/article-draft.json";
		if ( ! file_exists( $brief_path ) || ! file_exists( $draft_path ) ) {
			WP_CLI::error( 'Missing content-brief.json or article-draft.json -- run generate-brief and assemble-article-draft (with real prose) first. The placeholder scaffold (draft-scaffold.json) is never eligible here.' );
		}
		$brief = json_decode( file_get_contents( $brief_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$draft = json_decode( file_get_contents( $draft_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$seo   = BHP_SEO_Metadata_Package::generate( $brief, BHP_Content_Inventory::build()['items'] );
		$pinterest = BHP_Pinterest_Variant_Generator::generate( $brief, $seo );

		$originality_confirmations = ! empty( $assoc_args['originality-confirmations'] )
			? array_map( 'trim', explode( ',', $assoc_args['originality-confirmations'] ) )
			: array();
		$qa = BHP_Content_QA_Gate::evaluate( $brief, $draft, $seo, $pinterest, $originality_confirmations, array(
			'factual_accuracy' => $assoc_args['factual-review-confirmed-by'],
			'audience_fit'     => $assoc_args['audience-fit-confirmed-by'],
		) );

		BHP_Content_Production_Queue::transition( (int) $queue_id, 'approved', $assoc_args['approved-by'] );
		BHP_Content_Production_Queue::transition( (int) $queue_id, 'ready_for_wp_draft', $assoc_args['approved-by'] );

		$queue_item = BHP_Content_Production_Queue::get_item( (int) $queue_id );
		$package    = BHP_Draft_Package_Builder::build( $queue_item, $brief, $draft, $seo, $pinterest, $qa, array(), array(), $author_package );

		$post_id = BHP_WP_Draft_Workflow::create_full_package_draft( (int) $queue_id, $draft, $package, $assoc_args['approved-by'] );
		if ( is_wp_error( $post_id ) ) {
			foreach ( (array) $post_id->get_error_data() as $issue ) {
				WP_CLI::log( "  BLOCKED: {$issue['field']} -- {$issue['reason']}" );
			}
			WP_CLI::error( $post_id->get_error_message() ); // halts after the field-by-field report above has printed.
		}
		WP_CLI::success( "Draft post created (ID {$post_id}, status=draft, never published)." );
	}

	/**
	 * Rolls back a synthetic Phase-1E draft. Refuses anything not
	 * draft-status and not carrying the Phase 1E provenance marker.
	 *
	 * <post_id>
	 */
	public function delete_draft( $args ) {
		list( $post_id ) = $args;
		$result = BHP_WP_Draft_Workflow::delete_synthetic_draft( (int) $post_id );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( "Deleted synthetic draft #{$post_id}." );
	}

	/**
	 * Imports a JSON handoff file from brave-hearts-seo-engine's
	 * `bhp-seo package export-approved`. Read-only against WordPress
	 * (writes only a local content-engine/author-packages/ JSON copy) --
	 * never parses a manuscript, never talks to the seo-engine's database.
	 *
	 * <file>
	 * [--dry-run]
	 */
	public function import_approved_package( $args, $assoc_args ) {
		list( $file ) = $args;
		$result = BHP_Author_Fingerprint_Package::import_from_file( $file, isset( $assoc_args['dry-run'] ) );
		if ( is_wp_error( $result ) ) {
			foreach ( (array) $result->get_error_data() as $issue ) {
				WP_CLI::log( "  ISSUE: {$issue}" );
			}
			WP_CLI::error( $result->get_error_message() );
		}
		if ( $result['dry_run'] ) {
			WP_CLI::success( "Package {$result['package_uuid']} (brief {$result['content_brief_id']}) is schema-valid. Dry run -- nothing written." );
			return;
		}
		WP_CLI::success( "Imported package {$result['package_uuid']} -> {$result['local_path']}" );
	}

	/**
	 * Validates a previously-imported Author Fingerprint package against
	 * the full draft-gate criteria (schema + corpus loaded + Author
	 * Connection + Fingerprint Check + brand voice + reuse threshold).
	 * Read-only, reports only -- never creates or modifies anything.
	 *
	 * <package_uuid>
	 */
	public function validate_package( $args ) {
		list( $package_uuid ) = $args;
		$package = BHP_Author_Fingerprint_Package::get( $package_uuid );
		if ( null === $package ) {
			WP_CLI::error( "No imported package found for UUID '{$package_uuid}'. Run import-approved-package first." );
		}
		$issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $package );
		if ( empty( $issues ) ) {
			WP_CLI::success( "Package {$package_uuid} passes all Author Fingerprint gate checks." );
			return;
		}
		foreach ( $issues as $issue ) {
			WP_CLI::log( "  BLOCKED: {$issue['field']} -- {$issue['reason']}" );
		}
		WP_CLI::error( count( $issues ) . ' issue(s) found -- see above.' );
	}

	/**
	 * Inspects a Phase 1E-generated draft's full metadata package.
	 * Read-only -- Level 1.
	 *
	 * <post_id>
	 * [--format=table|json]
	 */
	public function inspect_draft( $args, $assoc_args ) {
		list( $post_id ) = $args;
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( "Post {$post_id} not found." );
		}

		$provenance = BHP_WP_Draft_Workflow::get_draft_provenance( $post_id );
		if ( ! $provenance['is_phase1e_generated'] ) {
			WP_CLI::error( "Post {$post_id} does not carry the Phase 1E provenance marker -- nothing to inspect." );
		}

		$pkg = BHP_WP_Draft_Workflow::get_full_package( $post_id );

		$missing_fields = array();
		foreach ( array(
			'core.title'          => empty( $pkg['core']['title'] ),
			'taxonomy.primary_category' => empty( $pkg['primary_category_id'] ),
			'taxonomy.tags'        => empty( $pkg['tags'] ),
			'seo.title'            => empty( $pkg['seo']['title'] ),
			'seo.description'      => empty( $pkg['seo']['description'] ),
			'classification.audience' => empty( $pkg['classification']['audience'] ),
			'classification.funnel_stage' => empty( $pkg['classification']['funnel_stage'] ),
			'pinterest.variants'   => empty( $pkg['pinterest']['variant_count'] ),
			'analytics.content_id' => empty( $pkg['analytics']['analytics_content_id'] ),
			'editorial.factual_review' => empty( $pkg['editorial']['factual_review_complete'] ),
			'author_package'       => empty( $pkg['author_package'] ),
		) as $field => $is_missing ) {
			if ( $is_missing ) {
				$missing_fields[] = $field;
			}
		}

		$eligibility = array(
			'is_draft_status'        => 'draft' === $pkg['core']['status'],
			'qa_status'              => $pkg['qa_status'],
			'missing_fields'         => $missing_fields,
			'would_currently_pass_full_gate' => empty( $missing_fields ) && ! in_array( $pkg['qa_status'], BHP_WP_Draft_Workflow::BLOCKING_QA_STATUSES, true ),
		);

		if ( 'json' === ( $assoc_args['format'] ?? 'table' ) ) {
			WP_CLI::log( wp_json_encode( array_merge( $pkg, array( 'eligibility' => $eligibility ) ), JSON_PRETTY_PRINT ) );
			return;
		}

		WP_CLI::log( "=== Post {$post_id}: {$pkg['core']['title']} ===" );
		WP_CLI::log( "Status: {$pkg['core']['status']}   Slug: {$pkg['core']['slug']}   Author: {$pkg['core']['author']}" );
		WP_CLI::log( 'Categories: ' . implode( ', ', wp_list_pluck( $pkg['categories'], 'name' ) ) . ' (primary: ' . ( get_term( $pkg['primary_category_id'] ) ? get_term( $pkg['primary_category_id'] )->name : '—' ) . ')' );
		WP_CLI::log( 'Tags: ' . implode( ', ', wp_list_pluck( $pkg['tags'], 'name' ) ) );
		WP_CLI::log( "SEO title: {$pkg['seo']['title']}" );
		WP_CLI::log( "Meta description: {$pkg['seo']['description']}" );
		WP_CLI::log( "Classification: audience={$pkg['classification']['audience']} funnel_stage={$pkg['classification']['funnel_stage']} intent={$pkg['classification']['content_intent']}" );
		WP_CLI::log( "CTA: primary={$pkg['classification']['primary_cta']} lead_offer={$pkg['classification']['lead_offer']}" );
		WP_CLI::log( 'Pinterest variants linked: ' . ( $pkg['pinterest']['variant_count'] ?? 0 ) . '/4' );
		WP_CLI::log( "Analytics content ID: {$pkg['analytics']['analytics_content_id']}" );
		WP_CLI::log( 'Image status: featured=' . ( $pkg['images']['featured_image']['status'] ?? 'unknown' ) );
		WP_CLI::log( "QA status: {$pkg['qa_status']}   Originality: {$pkg['originality_status']}" );
		WP_CLI::log( 'Factual review complete: ' . ( ( $pkg['editorial']['factual_review_complete'] ?? false ) ? 'yes (' . $pkg['editorial']['factual_reviewer'] . ')' : 'no' ) );
		WP_CLI::log( 'Audience-fit review complete: ' . ( ( $pkg['editorial']['audience_fit_review_complete'] ?? false ) ? 'yes (' . $pkg['editorial']['audience_fit_reviewer'] . ')' : 'no' ) );
		WP_CLI::log( "Provenance: approved_by={$provenance['approved_by']} created_at={$provenance['created_at']}" );
		WP_CLI::log( '--- Author Fingerprint package ---' );
		if ( empty( $pkg['author_package'] ) ) {
			WP_CLI::log( 'No Author Fingerprint package linked.' );
		} else {
			$ap = $pkg['author_package'];
			WP_CLI::log( 'Package UUID: ' . $pkg['author_package_uuid'] );
			WP_CLI::log( 'Brand voice source: ' . ( $ap['brand_voice_profile']['source_id'] ?? '—' ) . ' (' . ( $ap['brand_voice_profile']['source_title'] ?? '—' ) . ')' );
			foreach ( (array) ( $ap['corpus_manifest'] ?? array() ) as $entry ) {
				WP_CLI::log( "  corpus[{$entry['mandatory_key']}]: {$entry['source_id']} ({$entry['canonical_status']})" );
			}
			if ( ! empty( $ap['author_connection'] ) ) {
				$ac = $ap['author_connection'];
				WP_CLI::log( "Anecdote used: {$ac['anecdote_key']} (reuse_count={$ac['reuse_count']}, verification={$ac['verification_state']})" );
			} else {
				WP_CLI::log( 'Anecdote used: none selected' );
			}
			WP_CLI::log( 'Author Fingerprint Check passed: ' . ( ( $ap['author_fingerprint_check']['passed'] ?? false ) ? 'YES' : 'NO' ) );
			if ( ! empty( $ap['author_fingerprint_check']['prohibited_matches'] ) ) {
				WP_CLI::log( '  unsupported-claim warnings: ' . implode( '; ', $ap['author_fingerprint_check']['prohibited_matches'] ) );
			}
		}
		WP_CLI::log( '--- Draft eligibility ---' );
		WP_CLI::log( 'Missing fields: ' . ( empty( $missing_fields ) ? 'none' : implode( ', ', $missing_fields ) ) );
		WP_CLI::log( 'Would currently pass the full package gate: ' . ( $eligibility['would_currently_pass_full_gate'] ? 'YES' : 'NO' ) );
	}

	/**
	 * Evaluates published-content feedback for a URL across the
	 * standard review windows. Level 1 (report-only).
	 *
	 * <url>
	 */
	public function feedback( $args ) {
		list( $url ) = $args;
		$result = BHP_Content_Feedback_Loop::evaluate_all_windows( $url );
		foreach ( $result as $window => $data ) {
			if ( ! $data['data_available'] ) {
				WP_CLI::log( "{$window}: no data imported yet." );
				continue;
			}
			WP_CLI::log( sprintf( '%s%s: %s -- %s', $window, $data['data_is_fixture'] ? ' [FIXTURE DATA]' : '', $data['recommendation']['type'], $data['recommendation']['reason'] ) );
		}
	}

	/** Internal-link recommendation report across the whole inventory. Level 1/2. */
	public function link_report( $args, $assoc_args ) {
		$inventory = BHP_Content_Inventory::build();
		$report    = BHP_Internal_Link_Engine::build_report( $inventory['items'] );
		WP_CLI::log( sprintf( 'Orphan pages: %d | Overlinked targets: %d | Pages with recommendations: %d',
			count( $report['orphan_pages'] ), count( $report['overlinked_targets'] ), count( $report['link_recommendations'] ) ) );
		if ( isset( $assoc_args['export'] ) ) {
			$dir = get_template_directory() . '/content-engine/reports';
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			file_put_contents( $dir . '/internal-link-report.json', wp_json_encode( $report, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			WP_CLI::success( "Exported to {$dir}/internal-link-report.json" );
		}
	}
}

WP_CLI::add_command( 'bhp-content import', array( 'BHP_Content_Engine_CLI', 'import' ) );
WP_CLI::add_command( 'bhp-content import-fixtures', array( 'BHP_Content_Engine_CLI', 'import_fixtures' ) );
WP_CLI::add_command( 'bhp-content inventory', array( 'BHP_Content_Engine_CLI', 'inventory' ) );
WP_CLI::add_command( 'bhp-content opportunities', array( 'BHP_Content_Engine_CLI', 'opportunities' ) );
WP_CLI::add_command( 'bhp-content queue-list', array( 'BHP_Content_Engine_CLI', 'queue_list' ) );
WP_CLI::add_command( 'bhp-content queue-transition', array( 'BHP_Content_Engine_CLI', 'queue_transition' ) );
WP_CLI::add_command( 'bhp-content generate-brief', array( 'BHP_Content_Engine_CLI', 'generate_brief' ) );
WP_CLI::add_command( 'bhp-content generate-draft-package', array( 'BHP_Content_Engine_CLI', 'generate_draft_package' ) );
WP_CLI::add_command( 'bhp-content assemble-article-draft', array( 'BHP_Content_Engine_CLI', 'assemble_article_draft' ) );
WP_CLI::add_command( 'bhp-content create-wp-draft', array( 'BHP_Content_Engine_CLI', 'create_wp_draft' ) );
WP_CLI::add_command( 'bhp-content delete-draft', array( 'BHP_Content_Engine_CLI', 'delete_draft' ) );
WP_CLI::add_command( 'bhp-content import-approved-package', array( 'BHP_Content_Engine_CLI', 'import_approved_package' ) );
WP_CLI::add_command( 'bhp-content validate-package', array( 'BHP_Content_Engine_CLI', 'validate_package' ) );
WP_CLI::add_command( 'bhp-content inspect-draft', array( 'BHP_Content_Engine_CLI', 'inspect_draft' ) );
WP_CLI::add_command( 'bhp-content feedback', array( 'BHP_Content_Engine_CLI', 'feedback' ) );
WP_CLI::add_command( 'bhp-content link-report', array( 'BHP_Content_Engine_CLI', 'link_report' ) );
