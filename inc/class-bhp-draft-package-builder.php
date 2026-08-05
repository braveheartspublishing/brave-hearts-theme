<?php
/**
 * Brave Hearts Publishing — Phase 1E: full WordPress draft package builder.
 *
 * Assembles every sub-package (taxonomy, SEO, classification, images,
 * internal links, Pinterest, analytics, editorial governance) that a
 * NEW WordPress draft must carry, and implements the single exhaustive
 * gate (validate_complete()) that BHP_WP_Draft_Workflow::create_full_package_draft()
 * enforces before wp_insert_post() ever runs. This class assembles and
 * validates only -- it never writes to WordPress itself.
 *
 * Every sub-package this class references already owns its own field
 * definitions and validation (BHP_Taxonomy_Assignment_Engine,
 * BHP_SEO_Metadata_Package, BHP_Image_Metadata_Package,
 * BHP_Pinterest_Draft_Linkage, BHP_Analytics_Metadata_Package,
 * BHP_Editorial_Governance) -- this class composes them and adds only
 * the cross-cutting checks (core WP fields, classification completeness,
 * internal-link body validation) that don't belong to any single one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Draft_Package_Builder {

	/**
	 * @param array $queue_item BHP_Content_Production_Queue::get_item() output.
	 * @param array $brief      BHP_Content_Brief_Generator::generate() output.
	 * @param array $article_draft BHP_Blog_Draft_Generator::assemble_article_draft() output.
	 * @param array $seo        BHP_SEO_Metadata_Package::generate() output.
	 * @param array $pinterest  BHP_Pinterest_Variant_Generator::generate() output.
	 * @param array $qa_result  BHP_Content_QA_Gate::evaluate() output for this exact draft.
	 * @param array $image_overrides Optional overrides for BHP_Image_Metadata_Package::build().
	 * @param array $editorial_overrides Optional overrides for BHP_Editorial_Governance::build().
	 * @param array $author_package Imported BHP_Author_Fingerprint_Package (via ::get()) for this
	 *                              article's canonical brand/founder/Author Connection grounding.
	 *                              Empty array means "not supplied" -- validate_complete() always
	 *                              blocks on that, it is never an optional field.
	 */
	public static function build( array $queue_item, array $brief, array $article_draft, array $seo, array $pinterest, array $qa_result, array $image_overrides = array(), array $editorial_overrides = array(), array $author_package = array() ) {
		$taxonomy_inventory = class_exists( 'BHP_Taxonomy_Inventory' ) ? BHP_Taxonomy_Inventory::build() : array();
		$taxonomy           = class_exists( 'BHP_Taxonomy_Assignment_Engine' ) ? BHP_Taxonomy_Assignment_Engine::assign( $brief, $taxonomy_inventory ) : array();
		$images             = class_exists( 'BHP_Image_Metadata_Package' ) ? BHP_Image_Metadata_Package::build( $brief, $image_overrides ) : array();
		$pinterest_linkage  = class_exists( 'BHP_Pinterest_Draft_Linkage' ) ? BHP_Pinterest_Draft_Linkage::build( $pinterest ) : array();
		$analytics          = class_exists( 'BHP_Analytics_Metadata_Package' ) ? BHP_Analytics_Metadata_Package::build( $queue_item, $brief ) : array();
		$editorial          = class_exists( 'BHP_Editorial_Governance' ) ? BHP_Editorial_Governance::build( $brief, $qa_result, $editorial_overrides ) : array();
		$internal_links     = self::build_internal_links( $brief, $article_draft );

		$core = array(
			'title'          => wp_strip_all_tags( $brief['working_title'] ?? '' ),
			'proposed_slug'  => sanitize_title( $seo['proposed_slug'] ?? '' ),
			'excerpt'        => $seo['excerpt'] ?? '',
			'author_id'      => get_current_user_id() ?: 1,
			'post_type'      => 'post',
			'post_format'    => 'standard',
			'parent_page_id' => 0,
			'content_version' => 1,
		);

		$classification = array(
			'audience'          => $brief['target_audience'] ?? '',
			'funnel_stage'      => $brief['funnel_stage'] ?? '',
			'content_intent'    => $brief['content_intent'] ?? '',
			'featured_book'     => $brief['featured_book'] ?? '',
			'lead_offer'        => $brief['lead_offer'] ?? '',
			'primary_cta'       => $brief['primary_cta']['id'] ?? '',
			'secondary_cta'     => $brief['secondary_cta'] ?? '',
			'campaign_id'       => $analytics['campaign_id'] ?? '',
			'queue_id'          => $queue_item['queue_id'] ?? 0,
			'brief_id'          => $brief['blog_slug'] ?? '',
			'opportunity_id'    => $analytics['opportunity_record_id'],
			'originality_status' => self::originality_status( $article_draft ),
			'qa_status'         => $qa_result['overall_status'] ?? '',
		);

		return array(
			'core'           => $core,
			'taxonomy'       => $taxonomy,
			'seo'            => $seo,
			'classification' => $classification,
			'images'         => $images,
			'internal_links' => $internal_links,
			'pinterest'      => $pinterest_linkage,
			'analytics'      => $analytics,
			'editorial'      => $editorial,
			'qa'             => $qa_result,
			'author_package' => $author_package,
		);
	}

	private static function originality_status( array $article_draft ) {
		if ( ! class_exists( 'BHP_Content_Originality' ) ) {
			return 'unknown';
		}
		$findings = BHP_Content_Originality::check_draft( wp_strip_all_tags( $article_draft['content_html'] ?? '' ) );
		$has_fail = ! empty( array_filter( $findings, static function ( $f ) {
			return 'fail' === $f['severity'];
		} ) );
		return $has_fail ? 'fail' : ( empty( $findings ) ? 'pass' : 'revise' );
	}

	private static function build_internal_links( array $brief, array $article_draft ) {
		$broken = class_exists( 'BHP_Internal_Link_Engine' )
			? BHP_Internal_Link_Engine::validate_body_links( $article_draft['content_html'] ?? '' )
			: array();

		return array(
			'inserted_link_validation' => array(
				'broken_links' => $broken,
				'state'        => empty( $broken ) ? 'pass' : 'fail',
			),
			'recommended_inbound'   => array(), // requires re-scanning the whole live inventory for pages that SHOULD link here -- out of scope for a pre-publish draft; tracked as a post-publish feedback-loop task instead.
			'recommended_outbound'  => $brief['internal_link_targets'] ?? array(),
			'hub_link'              => '', // no formal hub-page concept exists yet in BHP_Content_Classification; left explicitly empty rather than fabricated.
			'related_reading_links' => $brief['internal_link_targets'] ?? array(),
			'product_links'         => array(), // product/book link targets are chosen by the human writer once real prose references a specific book/product page.
			'lead_offer_link'       => '',
			'cta_destination'       => $brief['primary_cta']['id'] ?? '',
			'anchor_text_recommendations' => wp_list_pluck( (array) ( $brief['internal_link_targets'] ?? array() ), 'title' ),
			'orphan_risk'           => 'not_assessed_pre_publish', // orphan status is only meaningful once the post is live and other pages can link to it.
		);
	}

	/**
	 * The single exhaustive gate. Returns an array of blocking issues
	 * (empty = eligible for wp_insert_post()); every issue names the
	 * exact field, matching the field-by-field blocking report Workstream
	 * 14 requires. This is intentionally a flat list of independent
	 * checks -- readable and individually testable -- rather than one
	 * dense boolean expression.
	 */
	public static function validate_complete( array $package, array $article_draft, array $qa_result ) {
		$issues = array();
		$add    = static function ( $field, $reason ) use ( &$issues ) {
			$issues[] = array( 'field' => $field, 'reason' => $reason );
		};

		// --- body ---------------------------------------------------------
		if ( class_exists( 'BHP_Blog_Draft_Generator' ) ) {
			if ( ! empty( $article_draft['placeholders'] ) ) {
				$add( 'body.placeholders', 'Unresolved placeholder sections remain: ' . implode( ', ', $article_draft['placeholders'] ) );
			}
			if ( BHP_Blog_Draft_Generator::contains_editorial_instructions( $article_draft['content_html'] ?? '' ) ) {
				$add( 'body.editorial_instructions', 'Body still contains an internal editorial instruction marker (e.g. "[PLACEHOLDER:").' );
			}
			$markup_errors = BHP_Blog_Draft_Generator::validate_markup( $article_draft['content_html'] ?? '' );
			if ( ! empty( $markup_errors ) ) {
				$add( 'body.gutenberg_markup', implode( '; ', $markup_errors ) );
			}
		}

		// --- core fields (Workstream 4) ------------------------------------
		$core = $package['core'] ?? array();
		if ( empty( $core['title'] ) ) {
			$add( 'core.title', 'Title is unresolved.' );
		}
		if ( empty( $core['proposed_slug'] ) ) {
			$add( 'core.slug', 'Slug is empty.' );
		} elseif ( get_page_by_path( $core['proposed_slug'], OBJECT, array( 'post', 'page' ) ) ) {
			$add( 'core.slug', "Slug '{$core['proposed_slug']}' collides with an existing post/page." );
		}
		if ( empty( $core['excerpt'] ) ) {
			$add( 'core.excerpt', 'Excerpt is missing.' );
		}
		if ( empty( $core['author_id'] ) || ! get_userdata( $core['author_id'] ) ) {
			$add( 'core.author', 'No valid author is assigned.' );
		}

		// --- taxonomy (Workstreams 1-3) ------------------------------------
		$taxonomy = $package['taxonomy'] ?? array();
		if ( empty( $taxonomy['primary_category_id'] ) ) {
			$add( 'taxonomy.primary_category', 'No primary category assigned (' . ( $taxonomy['category_assignment_reason'] ?? 'no reason recorded' ) . ').' );
		}
		if ( 'approval_required' === ( $taxonomy['taxonomy_approval_status'] ?? '' ) ) {
			$add( 'taxonomy.approval_status', 'Taxonomy assignment requires human approval before this draft can be created.' );
		}
		if ( empty( $taxonomy['tag_ids'] ) ) {
			$add( 'taxonomy.tags', 'No tags resolved to existing terms.' );
		}

		// --- SEO (Workstream 5) --------------------------------------------
		$seo = $package['seo'] ?? array();
		foreach ( array( 'seo_title' => 'SEO title', 'meta_description' => 'meta description', 'primary_keyword' => 'focus keyword', 'canonical_recommendation' => 'canonical recommendation', 'robots_recommendation' => 'robots recommendation', 'schema_type_recommendation' => 'schema type' ) as $key => $label ) {
			if ( empty( $seo[ $key ] ) ) {
				$add( "seo.{$key}", ucfirst( $label ) . ' is missing.' );
			}
		}
		if ( 'fail' === ( $seo['validation']['state'] ?? '' ) ) {
			$add( 'seo.validation', 'SEO validation state is fail: ' . wp_json_encode( $seo['validation']['findings'] ?? array() ) );
		}

		// --- classification (Workstream 6) ---------------------------------
		$classification = $package['classification'] ?? array();
		foreach ( array( 'audience', 'funnel_stage', 'content_intent' ) as $field ) {
			if ( empty( $classification[ $field ] ) ) {
				$add( "classification.{$field}", ucfirst( str_replace( '_', ' ', $field ) ) . ' is not assigned.' );
			}
		}
		if ( empty( $classification['primary_cta'] ) ) {
			$add( 'classification.primary_cta', 'No primary CTA assigned.' );
		}
		if ( '' === $classification['lead_offer'] ) {
			$add( 'classification.lead_offer', 'lead_offer must be either a real offer ID or explicitly "none" -- it is empty.' );
		}

		// --- images (Workstream 7) ------------------------------------------
		if ( 'revise' === ( $package['images']['validation']['state'] ?? '' ) || 'fail' === ( $package['images']['validation']['state'] ?? '' ) ) {
			$add( 'images', 'Image metadata incomplete: ' . wp_json_encode( $package['images']['validation']['findings'] ?? array() ) );
		}

		// --- internal links (Workstream 8) ----------------------------------
		if ( 'fail' === ( $package['internal_links']['inserted_link_validation']['state'] ?? '' ) ) {
			$add( 'internal_links', 'Broken internal link(s) inserted in body: ' . wp_json_encode( $package['internal_links']['inserted_link_validation']['broken_links'] ) );
		}

		// --- Pinterest (Workstream 9) ----------------------------------------
		if ( 'revise' === ( $package['pinterest']['validation']['state'] ?? '' ) ) {
			$add( 'pinterest', 'Pinterest package incomplete: ' . wp_json_encode( $package['pinterest']['validation']['findings'] ?? array() ) );
		}

		// --- analytics (Workstream 10) ----------------------------------------
		if ( empty( $package['analytics']['analytics_content_id'] ) ) {
			$add( 'analytics.content_id', 'No analytics content ID assigned.' );
		}
		if ( 'fail' === ( $package['analytics']['validation']['state'] ?? '' ) ) {
			$add( 'analytics.pii', 'Possible PII detected in the analytics package: ' . wp_json_encode( $package['analytics']['validation']['findings'] ?? array() ) );
		}

		// --- editorial governance / QA (Workstream 11 & 14) --------------------
		if ( empty( $package['editorial']['factual_review_complete'] ) ) {
			$add( 'editorial.factual_review', 'Factual review is not complete.' );
		}
		if ( empty( $package['editorial']['audience_fit_review_complete'] ) ) {
			$add( 'editorial.audience_fit_review', 'Audience-fit review is not complete.' );
		}
		if ( 'fail' === ( $classification['originality_status'] ?? '' ) ) {
			$add( 'editorial.originality', 'Originality check failed.' );
		}
		if ( empty( $qa_result['overall_status'] ) || in_array( $qa_result['overall_status'], BHP_WP_Draft_Workflow::BLOCKING_QA_STATUSES, true ) ) {
			$add( 'qa.overall_status', "QA gate overall_status is '" . ( $qa_result['overall_status'] ?? 'unset' ) . "', not pass_for_wp_draft/pass_for_publishing_review." );
		}
		if ( 'fail' === ( $qa_result['checks']['unsupported_claim_risk']['state'] ?? '' ) ) {
			$add( 'qa.unsupported_claims', 'Unsupported claim language detected in the draft.' );
		}

		// --- Author Fingerprint / brand-corpus grounding (mandatory, never optional) ---
		$author_package = $package['author_package'] ?? array();
		if ( empty( $author_package ) ) {
			$add( 'author_package.missing', 'No Author Fingerprint package supplied -- every article requires canonical brand/founder grounding and an Author Connection. Run wp bhp-content import-approved-package first.' );
		} elseif ( class_exists( 'BHP_Author_Fingerprint_Package' ) ) {
			foreach ( BHP_Author_Fingerprint_Package::validate_for_draft_gate( $author_package ) as $issue ) {
				$add( $issue['field'], $issue['reason'] );
			}
			$body_text = wp_strip_all_tags( $article_draft['content_html'] ?? '' );
			$voice_check = BHP_Author_Fingerprint_Package::check_brand_voice( $body_text );
			if ( ! $voice_check['passed'] ) {
				$add( 'author_package.brand_voice_check', 'Body fails the brand voice check: ' . implode( '; ', $voice_check['forbidden_matches'] ) );
			}
		}

		return $issues;
	}
}
