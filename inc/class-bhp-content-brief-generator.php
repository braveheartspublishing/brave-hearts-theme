<?php
/**
 * Brave Hearts Publishing — Phase 1E: structured content-brief generator.
 *
 * Produces a `content-brief.json` per approved production-queue item,
 * stored alongside the existing content-engine artifacts
 * (content-engine/blogs/<slug>/). This is a distinct artifact from the
 * pre-existing `design-brief.json` (Pinterest creative brief) -- a blog
 * brief describes what to WRITE, a design brief describes what to
 * PIN. Both live under the same blog-slug directory.
 *
 * This generator never fabricates facts, reviews, statistics, or
 * claims. Every field is either derived from data already in this
 * codebase (classification, CTA registry, catalog) or explicitly marked
 * as a placeholder requiring Andrew's input.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Brief_Generator {

	const PROHIBITED_CLAIM_PATTERNS = array(
		'/\bguarantee(d)?\b/i', '/\bclinically\b/i', '/\bproven to\b/i',
		'/\baward[- ]winning\b/i', '/\bbest[- ]selling\b/i', '/\b#1\b/',
		'/\blimited time\b/i', '/\bstudies show\b/i', '/\bexperts agree\b/i',
	);

	/**
	 * @param int $queue_id A BHP_Content_Production_Queue item ID.
	 * @return array|WP_Error The brief array, or WP_Error if the queue
	 *                        item is missing required fields.
	 */
	public static function generate( $queue_id ) {
		if ( ! class_exists( 'BHP_Content_Production_Queue' ) ) {
			return new WP_Error( 'bhp_cbg_missing_dependency', 'Production queue class not loaded.' );
		}
		$queue_item = BHP_Content_Production_Queue::get_item( $queue_id );
		if ( ! $queue_item ) {
			return new WP_Error( 'bhp_cbg_not_found', 'Queue item not found.' );
		}
		if ( empty( $queue_item['primary_keyword'] ) ) {
			return new WP_Error( 'bhp_cbg_missing_keyword', 'Queue item has no primary_keyword; cannot generate a brief.' );
		}

		$slug = $queue_item['proposed_slug'] ?: sanitize_title( $queue_item['primary_keyword'] );

		$cta_context = array(
			'audience'     => $queue_item['audience'],
			'funnel_stage' => $queue_item['funnel_stage'],
			'intent'       => $queue_item['content_intent'],
		);
		$primary_cta = null;
		if ( class_exists( 'BHP_CTA_Engine' ) ) {
			$primary_cta = $queue_item['cta_goal']
				? BHP_CTA_Engine::select_specific( $queue_item['cta_goal'], $cta_context )
				: BHP_CTA_Engine::select_cta( $cta_context );
		}

		$related = class_exists( 'BHP_Internal_Link_Engine' )
			? BHP_Internal_Link_Engine::recommend_for_new_content( $queue_item )
			: array();

		$brief = array(
			'blog_slug'          => $slug,
			'queue_id'           => $queue_id,
			'working_title'      => '[PLACEHOLDER: Andrew/editor to finalize working title around primary keyword "' . $queue_item['primary_keyword'] . '"]',
			'primary_keyword'    => $queue_item['primary_keyword'],
			'secondary_keywords' => $queue_item['secondary_keywords'],
			'search_intent'      => self::infer_search_intent( $queue_item ),
			'target_audience'    => $queue_item['audience'],
			'funnel_stage'       => $queue_item['funnel_stage'],
			'content_intent'     => $queue_item['content_intent'],
			'reader_problem'     => '[PLACEHOLDER: what specific problem is the reader trying to solve when they search this?]',
			'desired_reader_outcome' => '[PLACEHOLDER: what should the reader be able to do/decide after reading?]',
			'unique_angle'       => '[PLACEHOLDER: the specific BHP angle -- must not be a generic rewrite of existing search results]',
			'originality_requirement' => self::originality_requirements(),
			'featured_book'      => $queue_item['featured_book'],
			'lead_offer'         => $queue_item['lead_offer'],
			'primary_cta'        => $primary_cta ? array( 'id' => $primary_cta['id'] ?? $queue_item['cta_goal'], 'headline' => $primary_cta['headline'] ?? '' ) : array( 'id' => $queue_item['cta_goal'], 'headline' => '[No CTA registry match -- manual selection required]' ),
			'secondary_cta'      => '',
			'internal_link_targets' => $related,
			'authoritative_external_source_requirements' => '[PLACEHOLDER: list 1-3 real, checkable external sources for any factual claim -- e.g. NOAA, National Geographic Kids, a library-science source. Do not fabricate a citation.]',
			'factual_claims_requiring_verification' => array(
				'[PLACEHOLDER: list every specific number, date, or scientific claim the draft will make, so it can be checked before publishing]',
			),
			'recommended_outline' => self::recommended_outline( $queue_item ),
			'recommended_length_range' => '900-1400 words (typical for this site\'s existing resource articles; adjust per topic depth)',
			'faq_opportunities'  => array(),
			'image_requirements' => '[PLACEHOLDER: at least one original or licensed image; no stock photography implying a real child/classroom unless actually sourced and rights-cleared]',
			'featured_image_direction' => '[PLACEHOLDER: visual direction consistent with content-engine/config/brand-guidelines.yaml]',
			'pinterest_angles'   => array( 'problem-led', 'outcome-led', 'curiosity-led', 'resource-led' ),
			'prohibited_claims'  => array( 'fabricated reviews', 'fabricated statistics', 'fabricated awards', 'fabricated classroom testimonials', 'unverified "clinically proven" or "guaranteed" language' ),
			'cannibalization_warnings' => $queue_item['source_evidence']['signals']['cannibalization_risk']['detail'] ?? 'Not assessed at brief time -- re-check against current inventory before drafting.',
			'existing_related_content' => $related,
			'metadata_requirements' => array( 'seo_title_max' => 60, 'meta_description_max' => 160, 'requires_focus_keyword' => true ),
			'approval_status'   => 'needs_review',
			'generated_at'       => current_time( 'mysql', true ),
		);

		self::write_brief( $slug, $brief );
		BHP_Content_Production_Queue::transition( $queue_id, 'brief_ready' );

		return $brief;
	}

	private static function infer_search_intent( array $queue_item ) {
		$stage_intent_map = array(
			'awareness'            => 'informational',
			'problem_recognition'  => 'informational',
			'consideration'        => 'informational_commercial',
			'product_discovery'    => 'commercial',
			'conversion'           => 'transactional',
			'retention_engagement' => 'navigational',
		);
		return $stage_intent_map[ $queue_item['funnel_stage'] ] ?? 'informational';
	}

	private static function originality_requirements() {
		// Mirrors Workstream 6's originality checklist -- brief must ask
		// the writer for at least 2 of these, never left implicit.
		return array(
			'options' => array(
				'unique_bhp_perspective', 'charlotte_and_henry_connection', 'original_activity_or_prompt',
				'parent_use_guidance', 'teacher_use_guidance', 'age_specific_explanation',
				'comparison_table', 'reading_development_insight', 'geographic_or_scientific_explanation',
				'factual_source_synthesis', 'book_specific_context', 'downloadable_resource_connection',
			),
			'minimum_required' => 2,
			'note' => 'Draft must satisfy at least 2 of the above -- see BHP_Content_Originality for automated + manual checks against the final draft.',
		);
	}

	private static function recommended_outline( array $queue_item ) {
		return array(
			'H1' => '[Derived from working_title once finalized]',
			'sections' => array(
				'Hook / reader problem (no heading, opening paragraphs)',
				'H2: Core answer to the search query',
				'H2: BHP-specific angle / Charlotte & Henry connection',
				'H2: ' . ( 'teacher' === $queue_item['audience'] ? 'Classroom / lesson use' : 'Parent / family use' ),
				'H2: FAQ (if applicable)',
				'H2: Related reading / continue exploring (internal links + CTA)',
			),
		);
	}

	private static function write_brief( $slug, array $brief ) {
		$dir = get_template_directory() . '/content-engine/blogs/' . $slug;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		file_put_contents( $dir . '/content-brief.json', wp_json_encode( $brief, JSON_PRETTY_PRINT ) ); // phpcs:ignore -- local build artifact.
	}

	/** Scans free text for claim patterns that must never be fabricated. */
	public static function detect_prohibited_claims( $text ) {
		$hits = array();
		foreach ( self::PROHIBITED_CLAIM_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $text, $m ) ) {
				$hits[] = $m[0];
			}
		}
		return $hits;
	}
}
