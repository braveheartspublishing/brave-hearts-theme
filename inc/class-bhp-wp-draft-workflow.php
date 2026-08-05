<?php
/**
 * Brave Hearts Publishing — Phase 1E: controlled WordPress draft workflow.
 *
 * Follows the same wp_insert_post() pattern already used by
 * BHP_Lead_Event_Log::write_event() for the actual insert, but this
 * class creates real, public 'post'-type content -- so unlike the lead
 * log it is status-gated ('draft' only, never anything else) and
 * requires the queue item to already be in an approval-gated status.
 * It never overwrites an existing post and never changes post_status
 * away from 'draft' -- publishing remains a fully separate, explicit
 * action outside this class's authority.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_WP_Draft_Workflow {

	const META_QUEUE_ID       = '_bhp_draft_queue_id';
	const META_PROVENANCE     = '_bhp_draft_provenance'; // 'phase1e_generated'
	const META_SEO_TITLE      = 'rank_math_title';
	const META_SEO_DESCRIPTION = 'rank_math_description';
	const META_SEO_FOCUS_KW   = 'rank_math_focus_keyword';
	const META_AUDIENCE       = '_bhp_content_audience';
	const META_FUNNEL_STAGE   = '_bhp_content_funnel_stage';
	const META_INTENT         = '_bhp_content_intent';
	const META_LEAD_OFFER     = '_bhp_content_lead_offer';
	const META_PRIMARY_GOAL   = '_bhp_content_primary_goal';

	const BLOCKING_QA_STATUSES = array( 'fail', 'revise', 'editorial_review_required' );

	// Reused from BHP_Content_Classification's own constants where a
	// field already exists there -- never a second, parallel meta key
	// for the same fact.
	const META_SECONDARY_GOAL = '_bhp_content_secondary_goal';
	const META_FEATURED_BOOK  = '_bhp_content_featured_book';
	// New, draft-package-specific fields (Workstreams 4/6/9/10/11).
	const META_CAMPAIGN_ID          = '_bhp_draft_campaign_id';
	const META_BRIEF_ID             = '_bhp_draft_brief_id';
	const META_OPPORTUNITY_ID       = '_bhp_draft_opportunity_id';
	const META_QA_STATUS            = '_bhp_draft_qa_status';
	const META_ORIGINALITY_STATUS   = '_bhp_draft_originality_status';
	const META_CONTENT_VERSION      = '_bhp_draft_content_version';
	const META_TAXONOMY_ASSIGNMENT  = '_bhp_draft_taxonomy_assignment';
	const META_IMAGE_METADATA       = '_bhp_draft_image_metadata';
	const META_INTERNAL_LINKS       = '_bhp_draft_internal_links';
	const META_PINTEREST_LINKAGE    = '_bhp_draft_pinterest_linkage';
	const META_ANALYTICS_METADATA   = '_bhp_draft_analytics_metadata';
	const META_EDITORIAL_GOVERNANCE = '_bhp_draft_editorial_governance';
	const META_AUTHOR_PACKAGE       = '_bhp_draft_author_fingerprint_package';
	const META_AUTHOR_PACKAGE_UUID  = '_bhp_draft_author_package_uuid';

	/**
	 * @param int   $queue_id  Must be a BHP_Content_Production_Queue item
	 *                         already transitioned to 'ready_for_wp_draft'.
	 * @param array $draft     Output of BHP_Blog_Draft_Generator::assemble_article_draft() --
	 *                         NEVER the placeholder scaffold from ::generate().
	 * @param array $seo       Output of BHP_SEO_Metadata_Package::generate().
	 * @param array $brief     Output of BHP_Content_Brief_Generator::generate().
	 * @param string $approved_by Required -- who approved this draft creation.
	 * @param array $qa_result Required -- BHP_Content_QA_Gate::evaluate() output for
	 *                         this exact draft. Creation is refused unless its
	 *                         overall_status has cleared every blocking gate.
	 * @return int|WP_Error New post ID, or WP_Error on refusal/failure.
	 */
	public static function create_draft( $queue_id, array $draft, array $seo, array $brief, $approved_by, array $qa_result = array() ) {
		if ( empty( $approved_by ) ) {
			return new WP_Error( 'bhp_wpdw_approval_required', 'Draft creation requires an explicit approving user.' );
		}
		if ( ! class_exists( 'BHP_Content_Production_Queue' ) ) {
			return new WP_Error( 'bhp_wpdw_missing_dependency', 'Production queue class not loaded.' );
		}
		$queue_item = BHP_Content_Production_Queue::get_item( $queue_id );
		if ( ! $queue_item ) {
			return new WP_Error( 'bhp_wpdw_queue_not_found', 'Queue item not found.' );
		}
		if ( 'approved' !== $queue_item['approval_status'] ) {
			return new WP_Error( 'bhp_wpdw_not_approved', 'Queue item has not been explicitly approved.' );
		}

		// --- Editorial-readiness gates -----------------------------------
		// A post 460 style scaffold-as-draft can never reach wp_insert_post
		// again: every one of these is checked against the actual content,
		// not just trusted from the caller's say-so.
		if ( ! empty( $draft['placeholders'] ) ) {
			return new WP_Error( 'bhp_wpdw_placeholders_remaining', 'Refusing to create a WordPress draft: unresolved placeholder sections remain (' . implode( ', ', $draft['placeholders'] ) . '). Use BHP_Blog_Draft_Generator::assemble_article_draft() with real prose instead.' );
		}
		if ( class_exists( 'BHP_Blog_Draft_Generator' ) && BHP_Blog_Draft_Generator::contains_editorial_instructions( $draft['content_html'] ?? '' ) ) {
			return new WP_Error( 'bhp_wpdw_editorial_instructions_present', 'Refusing to create a WordPress draft: the body still contains internal editorial instruction markers (e.g. "[PLACEHOLDER:").' );
		}
		if ( class_exists( 'BHP_Blog_Draft_Generator' ) ) {
			$markup_errors = BHP_Blog_Draft_Generator::validate_markup( $draft['content_html'] ?? '' );
			if ( ! empty( $markup_errors ) ) {
				return new WP_Error( 'bhp_wpdw_invalid_markup', 'Refusing to create a WordPress draft: invalid Gutenberg block markup detected -- ' . implode( '; ', $markup_errors ) );
			}
		}
		if ( empty( $qa_result ) || empty( $qa_result['overall_status'] ) ) {
			return new WP_Error( 'bhp_wpdw_qa_not_evaluated', 'Refusing to create a WordPress draft: no QA gate result was supplied. Run BHP_Content_QA_Gate::evaluate() on this exact draft first.' );
		}
		if ( in_array( $qa_result['overall_status'], self::BLOCKING_QA_STATUSES, true ) ) {
			return new WP_Error( 'bhp_wpdw_qa_not_cleared', "Refusing to create a WordPress draft: QA gate overall_status is '{$qa_result['overall_status']}'." );
		}
		// ------------------------------------------------------------------

		$slug = self::unique_slug( $seo['proposed_slug'] );

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'draft', // NEVER anything else in this class.
				'post_title'   => wp_strip_all_tags( $brief['working_title'] ),
				'post_name'    => $slug,
				'post_content' => $draft['content_html'],
				'post_excerpt' => $seo['excerpt'],
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return is_wp_error( $post_id ) ? $post_id : new WP_Error( 'bhp_wpdw_insert_failed', 'wp_insert_post() failed.' );
		}

		update_post_meta( $post_id, self::META_QUEUE_ID, (int) $queue_id );
		update_post_meta( $post_id, self::META_PROVENANCE, 'phase1e_generated' );
		update_post_meta( $post_id, self::META_SEO_TITLE, sanitize_text_field( $seo['seo_title'] ) );
		update_post_meta( $post_id, self::META_SEO_DESCRIPTION, sanitize_text_field( $seo['meta_description'] ) );
		update_post_meta( $post_id, self::META_SEO_FOCUS_KW, sanitize_text_field( $seo['primary_keyword'] ) );
		update_post_meta( $post_id, self::META_AUDIENCE, sanitize_key( $brief['target_audience'] ) );
		update_post_meta( $post_id, self::META_FUNNEL_STAGE, sanitize_key( $brief['funnel_stage'] ) );
		update_post_meta( $post_id, self::META_INTENT, sanitize_key( $brief['content_intent'] ) );
		update_post_meta( $post_id, self::META_LEAD_OFFER, sanitize_key( $brief['lead_offer'] ) );
		update_post_meta( $post_id, self::META_PRIMARY_GOAL, sanitize_key( $brief['primary_cta']['id'] ?? '' ) );
		update_post_meta( $post_id, '_bhp_draft_approved_by', sanitize_text_field( $approved_by ) );
		update_post_meta( $post_id, '_bhp_draft_created_at', current_time( 'mysql', true ) );

		BHP_Content_Production_Queue::transition( $queue_id, 'wp_draft_created', $approved_by );

		return $post_id;
	}

	/**
	 * Full-publishing-package draft creation (Phase 1E metadata/taxonomy/
	 * SEO/social/analytics/editorial-governance expansion). This is the
	 * ONLY method that should be used going forward -- create_draft()
	 * above remains for the narrower body-only contract already covered
	 * by tests/test-content-intelligence-engine.php, but every NEW draft
	 * should carry the full package this method requires and persists.
	 *
	 * @param int    $queue_id       BHP_Content_Production_Queue item ID, already 'approved'.
	 * @param array  $article_draft  BHP_Blog_Draft_Generator::assemble_article_draft() output.
	 * @param array  $package        BHP_Draft_Package_Builder::build() output for this exact draft.
	 * @param string $approved_by    Required -- who approved this draft creation.
	 * @return int|WP_Error New post ID, or a WP_Error whose get_error_data()
	 *                       is the field-by-field blocking report from
	 *                       BHP_Draft_Package_Builder::validate_complete()
	 *                       when the package is incomplete.
	 */
	public static function create_full_package_draft( $queue_id, array $article_draft, array $package, $approved_by ) {
		if ( empty( $approved_by ) ) {
			return new WP_Error( 'bhp_wpdw_approval_required', 'Draft creation requires an explicit approving user.' );
		}
		if ( ! class_exists( 'BHP_Content_Production_Queue' ) ) {
			return new WP_Error( 'bhp_wpdw_missing_dependency', 'Production queue class not loaded.' );
		}
		$queue_item = BHP_Content_Production_Queue::get_item( $queue_id );
		if ( ! $queue_item ) {
			return new WP_Error( 'bhp_wpdw_queue_not_found', 'Queue item not found.' );
		}
		if ( 'approved' !== $queue_item['approval_status'] ) {
			return new WP_Error( 'bhp_wpdw_not_approved', 'Queue item has not been explicitly approved.' );
		}

		$qa_result = $package['qa'] ?? array();
		$issues    = class_exists( 'BHP_Draft_Package_Builder' )
			? BHP_Draft_Package_Builder::validate_complete( $package, $article_draft, $qa_result )
			: array( array( 'field' => 'package', 'reason' => 'BHP_Draft_Package_Builder not loaded.' ) );
		if ( ! empty( $issues ) ) {
			return new WP_Error(
				'bhp_wpdw_package_incomplete',
				'Refusing to create a WordPress draft: ' . count( $issues ) . ' required field(s) are missing or invalid. See get_error_data() for the field-by-field report.',
				$issues
			);
		}

		$core = $package['core'];
		$seo  = $package['seo'];
		$slug = self::unique_slug( $core['proposed_slug'] );

		$post_id = wp_insert_post(
			array(
				'post_type'    => $core['post_type'],
				'post_status'  => 'draft', // NEVER anything else in this class.
				'post_title'   => $core['title'],
				'post_name'    => $slug,
				'post_content' => $article_draft['content_html'],
				'post_excerpt' => $core['excerpt'],
				'post_author'  => (int) $core['author_id'],
				'post_parent'  => (int) $core['parent_page_id'],
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return is_wp_error( $post_id ) ? $post_id : new WP_Error( 'bhp_wpdw_insert_failed', 'wp_insert_post() failed.' );
		}

		if ( 'standard' !== $core['post_format'] ) {
			set_post_format( $post_id, $core['post_format'] );
		}

		// Taxonomy: only ever the exact existing term IDs the assignment
		// engine matched -- never a term name string (which would let
		// WordPress silently create a new term on the spot).
		$taxonomy = $package['taxonomy'];
		if ( ! empty( $taxonomy['primary_category_id'] ) ) {
			$category_ids = array_unique( array_merge( array( (int) $taxonomy['primary_category_id'] ), array_map( 'intval', $taxonomy['secondary_category_ids'] ?? array() ) ) );
			wp_set_post_terms( $post_id, $category_ids, 'category', false );
			update_post_meta( $post_id, 'rank_math_primary_category', (string) $taxonomy['primary_category_id'] );
		}
		if ( ! empty( $taxonomy['tag_ids'] ) ) {
			wp_set_post_terms( $post_id, array_map( 'intval', $taxonomy['tag_ids'] ), 'post_tag', false );
		}

		// Core provenance + approval.
		update_post_meta( $post_id, self::META_QUEUE_ID, (int) $queue_id );
		update_post_meta( $post_id, self::META_PROVENANCE, 'phase1e_generated' );
		update_post_meta( $post_id, '_bhp_draft_approved_by', sanitize_text_field( $approved_by ) );
		update_post_meta( $post_id, '_bhp_draft_created_at', current_time( 'mysql', true ) );
		update_post_meta( $post_id, self::META_CONTENT_VERSION, (int) $core['content_version'] );

		// SEO (Workstream 5) -- real Rank Math postmeta keys only.
		foreach ( BHP_SEO_Metadata_Package::to_rank_math_postmeta( $seo, $taxonomy['primary_category_id'] ?? 0 ) as $meta_key => $value ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
		}

		// Classification (Workstream 6) -- reuses BHP_Content_Classification's
		// own meta keys for every field that already exists there.
		$classification = $package['classification'];
		update_post_meta( $post_id, BHP_Content_Classification::META_AUDIENCE, sanitize_key( $classification['audience'] ) );
		update_post_meta( $post_id, BHP_Content_Classification::META_FUNNEL_STAGE, sanitize_key( $classification['funnel_stage'] ) );
		update_post_meta( $post_id, BHP_Content_Classification::META_INTENT, sanitize_key( $classification['content_intent'] ) );
		update_post_meta( $post_id, BHP_Content_Classification::META_LEAD_OFFER, sanitize_key( $classification['lead_offer'] ) );
		update_post_meta( $post_id, BHP_Content_Classification::META_FEATURED_BOOK, sanitize_key( $classification['featured_book'] ) );
		update_post_meta( $post_id, self::META_PRIMARY_GOAL, sanitize_key( $classification['primary_cta'] ) );
		update_post_meta( $post_id, self::META_SECONDARY_GOAL, sanitize_key( $classification['secondary_cta'] ) );
		update_post_meta( $post_id, self::META_CAMPAIGN_ID, sanitize_key( $classification['campaign_id'] ) );
		update_post_meta( $post_id, self::META_BRIEF_ID, sanitize_text_field( $classification['brief_id'] ) );
		update_post_meta( $post_id, self::META_OPPORTUNITY_ID, sanitize_text_field( (string) $classification['opportunity_id'] ) );
		update_post_meta( $post_id, self::META_ORIGINALITY_STATUS, sanitize_key( $classification['originality_status'] ) );
		update_post_meta( $post_id, self::META_QA_STATUS, sanitize_key( $classification['qa_status'] ) );

		// Structured sub-packages -- stored as JSON blobs, matching the
		// existing convention (e.g. BHP_Content_Production_Queue's
		// _bhp_cq_source_evidence) for compound, non-scalar data.
		update_post_meta( $post_id, self::META_TAXONOMY_ASSIGNMENT, wp_json_encode( $taxonomy ) );
		update_post_meta( $post_id, self::META_IMAGE_METADATA, wp_json_encode( $package['images'] ) );
		update_post_meta( $post_id, self::META_INTERNAL_LINKS, wp_json_encode( $package['internal_links'] ) );
		update_post_meta( $post_id, self::META_PINTEREST_LINKAGE, wp_json_encode( $package['pinterest'] ) );
		update_post_meta( $post_id, self::META_ANALYTICS_METADATA, wp_json_encode( $package['analytics'] ) );
		update_post_meta( $post_id, self::META_EDITORIAL_GOVERNANCE, wp_json_encode( $package['editorial'] ) );
		update_post_meta( $post_id, self::META_AUTHOR_PACKAGE, wp_json_encode( $package['author_package'] ?? array() ) );
		update_post_meta( $post_id, self::META_AUTHOR_PACKAGE_UUID, sanitize_text_field( $package['author_package']['package_uuid'] ?? '' ) );

		BHP_Content_Production_Queue::transition( $queue_id, 'wp_draft_created', $approved_by );

		return $post_id;
	}

	/**
	 * Reads back every field the admin panel and CLI inspector need,
	 * decoding the JSON sub-packages. Read-only -- never mutates.
	 */
	public static function get_full_package( $post_id ) {
		return array(
			'provenance'      => self::get_draft_provenance( $post_id ),
			'core'            => array(
				'title'   => get_the_title( $post_id ),
				'slug'    => get_post_field( 'post_name', $post_id ),
				'status'  => get_post_field( 'post_status', $post_id ),
				'author'  => (int) get_post_field( 'post_author', $post_id ),
				'excerpt' => get_post_field( 'post_excerpt', $post_id ),
			),
			'categories'      => wp_get_post_terms( $post_id, 'category', array( 'fields' => 'all' ) ),
			'tags'            => wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'all' ) ),
			'primary_category_id' => (int) get_post_meta( $post_id, 'rank_math_primary_category', true ),
			'seo'             => array(
				'title'             => get_post_meta( $post_id, 'rank_math_title', true ),
				'description'       => get_post_meta( $post_id, 'rank_math_description', true ),
				'focus_keyword'     => get_post_meta( $post_id, 'rank_math_focus_keyword', true ),
				'canonical_url'     => get_post_meta( $post_id, 'rank_math_canonical_url', true ),
				'breadcrumb_title'  => get_post_meta( $post_id, 'rank_math_breadcrumb_title', true ),
				'facebook_title'    => get_post_meta( $post_id, 'rank_math_facebook_title', true ),
				'twitter_title'     => get_post_meta( $post_id, 'rank_math_twitter_title', true ),
			),
			'classification'  => array(
				'audience'       => get_post_meta( $post_id, BHP_Content_Classification::META_AUDIENCE, true ),
				'funnel_stage'   => get_post_meta( $post_id, BHP_Content_Classification::META_FUNNEL_STAGE, true ),
				'content_intent' => get_post_meta( $post_id, BHP_Content_Classification::META_INTENT, true ),
				'lead_offer'     => get_post_meta( $post_id, BHP_Content_Classification::META_LEAD_OFFER, true ),
				'featured_book'  => get_post_meta( $post_id, BHP_Content_Classification::META_FEATURED_BOOK, true ),
				'primary_cta'    => get_post_meta( $post_id, self::META_PRIMARY_GOAL, true ),
				'secondary_cta'  => get_post_meta( $post_id, self::META_SECONDARY_GOAL, true ),
				'campaign_id'    => get_post_meta( $post_id, self::META_CAMPAIGN_ID, true ),
			),
			'brief_id'        => get_post_meta( $post_id, self::META_BRIEF_ID, true ),
			'opportunity_id'  => get_post_meta( $post_id, self::META_OPPORTUNITY_ID, true ),
			'originality_status' => get_post_meta( $post_id, self::META_ORIGINALITY_STATUS, true ),
			'qa_status'       => get_post_meta( $post_id, self::META_QA_STATUS, true ),
			'content_version' => (int) get_post_meta( $post_id, self::META_CONTENT_VERSION, true ),
			'taxonomy_assignment' => json_decode( get_post_meta( $post_id, self::META_TAXONOMY_ASSIGNMENT, true ), true ),
			'images'          => json_decode( get_post_meta( $post_id, self::META_IMAGE_METADATA, true ), true ),
			'internal_links'  => json_decode( get_post_meta( $post_id, self::META_INTERNAL_LINKS, true ), true ),
			'pinterest'       => json_decode( get_post_meta( $post_id, self::META_PINTEREST_LINKAGE, true ), true ),
			'analytics'       => json_decode( get_post_meta( $post_id, self::META_ANALYTICS_METADATA, true ), true ),
			'editorial'       => json_decode( get_post_meta( $post_id, self::META_EDITORIAL_GOVERNANCE, true ), true ),
			'author_package'  => json_decode( get_post_meta( $post_id, self::META_AUTHOR_PACKAGE, true ), true ),
			'author_package_uuid' => get_post_meta( $post_id, self::META_AUTHOR_PACKAGE_UUID, true ),
		);
	}

	/**
	 * Guarantees no accidental collision with an existing published slug
	 * (or a prior synthetic draft) -- appends -2, -3, ... rather than
	 * ever silently overwriting.
	 */
	private static function unique_slug( $base_slug ) {
		$slug  = sanitize_title( $base_slug );
		$try   = $slug;
		$index = 2;
		while ( get_page_by_path( $try, OBJECT, array( 'post', 'page' ) ) ) {
			$try = $slug . '-' . $index;
			++$index;
		}
		return $try;
	}

	/**
	 * Rollback support: deletes a Phase-1E-generated draft ONLY if it is
	 * still in 'draft' status and carries our provenance marker -- this
	 * can never delete a real published post or a post this system did
	 * not create, even if called with an arbitrary ID.
	 */
	public static function delete_synthetic_draft( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'bhp_wpdw_not_found', 'Post not found.' );
		}
		if ( 'draft' !== $post->post_status ) {
			return new WP_Error( 'bhp_wpdw_not_draft', 'Refusing to delete a post that is not in draft status.' );
		}
		if ( 'phase1e_generated' !== get_post_meta( $post_id, self::META_PROVENANCE, true ) ) {
			return new WP_Error( 'bhp_wpdw_not_synthetic', 'Refusing to delete a post without the Phase 1E provenance marker.' );
		}
		$queue_id = (int) get_post_meta( $post_id, self::META_QUEUE_ID, true );
		$result   = wp_delete_post( $post_id, true );
		if ( $result && $queue_id && class_exists( 'BHP_Content_Production_Queue' ) ) {
			// The draft no longer exists -- leaving the queue item at
			// 'wp_draft_created' would falsely tell future automation a
			// draft already exists for this topic. Roll it back to
			// 'ready_for_wp_draft' so it can be regenerated.
			BHP_Content_Production_Queue::transition( $queue_id, 'ready_for_wp_draft', 'system:rollback_after_draft_deletion' );
		}
		return $result;
	}

	public static function get_draft_provenance( $post_id ) {
		return array(
			'is_phase1e_generated' => 'phase1e_generated' === get_post_meta( $post_id, self::META_PROVENANCE, true ),
			'queue_id'             => (int) get_post_meta( $post_id, self::META_QUEUE_ID, true ),
			'approved_by'          => get_post_meta( $post_id, '_bhp_draft_approved_by', true ),
			'created_at'           => get_post_meta( $post_id, '_bhp_draft_created_at', true ),
		);
	}
}
