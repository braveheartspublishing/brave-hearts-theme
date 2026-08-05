<?php
/**
 * Brave Hearts Publishing — Phase 1D: conversion-readiness scoring.
 *
 * Distinct from content-engine/config/scoring-rubric.yaml, which scores
 * Pinterest PIN CREATIVE. This scores a PAGE's overall organic-funnel
 * conversion readiness (audience clarity, CTA relevance, analytics
 * coverage, etc.) -- a different rubric for a different artifact.
 *
 * Every criterion is tagged with a check_type so a caller never mistakes
 * an automated proxy for a real manual review:
 * - 'automated': computed directly and reliably from data this class has.
 * - 'inferred':  computed from a heuristic/proxy that is a reasonable
 *                signal but not a certain measurement.
 * - 'manual':    cannot be honestly automated at all (subjective human
 *                judgment, or requires a real browser/device/screenshot
 *                per docs/testing rules) -- always returned as null
 *                needing a human-supplied override.
 *
 * This class NEVER edits content based on its own score -- it only
 * reports. Bulk auto-editing based on a score was explicitly out of
 * scope for Phase 1D.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Conversion_Scoring {

	const STATE_PASS   = 'pass';
	const STATE_REVISE = 'revise';
	const STATE_FAIL   = 'fail';

	/**
	 * The 14 criteria named in the Phase 1D workstream. 'weight' values
	 * are arbitrary-but-documented, summing to 1.0, so different pages
	 * can be compared on a single overall score -- but the per-criterion
	 * breakdown is always the primary output, not the single number.
	 */
	public static function criteria() {
		return apply_filters( 'bhp_conversion_scoring_criteria', array(
			'audience_clarity'          => array( 'weight' => 0.10, 'check_type' => 'automated', 'description' => 'Content has an explicit or registry-derived audience classification, not the flat default.' ),
			'funnel_stage_clarity'      => array( 'weight' => 0.08, 'check_type' => 'automated', 'description' => 'Content has an explicit or registry-derived funnel stage, not the flat default.' ),
			'search_intent_match'       => array( 'weight' => 0.08, 'check_type' => 'manual', 'description' => 'The content actually answers what a reader searching this topic wants to know.' ),
			'cta_relevance_benefit_clarity' => array( 'weight' => 0.10, 'check_type' => 'automated', 'description' => 'BHP_CTA_Engine can resolve a real, non-empty CTA for this content\'s classification.' ),
			'lead_offer_fit'            => array( 'weight' => 0.07, 'check_type' => 'inferred', 'description' => 'The classified lead_offer/featured_book is consistent with the CTA actually selected.' ),
			'product_relevance'         => array( 'weight' => 0.07, 'check_type' => 'inferred', 'description' => 'For product/collection pages, real price/format data is present in the rendered output.' ),
			'internal_link_quality'     => array( 'weight' => 0.07, 'check_type' => 'automated', 'description' => 'At least one internal link exists in the rendered content (readers have somewhere to go next).' ),
			'mobile_usability'          => array( 'weight' => 0.07, 'check_type' => 'manual', 'description' => 'Requires real-device or real-viewport-resize testing per docs/testing.md -- never inferred from markup alone.' ),
			'accessibility'             => array( 'weight' => 0.07, 'check_type' => 'inferred', 'description' => 'Proxy only: every <img> in the rendered content has a non-empty alt attribute. Full a11y (focus order, contrast, ARIA) requires manual review.' ),
			'analytics_coverage'        => array( 'weight' => 0.08, 'check_type' => 'automated', 'description' => 'At least one data-bhp-event or data-bhp-impression-event attribute is present in the rendered output.' ),
			'attribution_readiness'     => array( 'weight' => 0.05, 'check_type' => 'automated', 'description' => 'BHP_UTM_Attribution is loaded and active sitewide (does not verify any specific visit\'s attribution).' ),
			'claim_accuracy'            => array( 'weight' => 0.10, 'check_type' => 'inferred', 'description' => 'Proxy only: rendered text contains none of the fixed forbidden-claim words (award, guarantee, clinically, proven, limited time, etc). A real accuracy review is still manual.' ),
			'visual_readiness'          => array( 'weight' => 0.04, 'check_type' => 'manual', 'description' => 'Requires an actual screenshot/visual review -- never inferred from markup.' ),
			'performance_risk'          => array( 'weight' => 0.02, 'check_type' => 'inferred', 'description' => 'Proxy only: counts <img> tags without loading="lazy" as a rough signal, not a real performance audit (e.g. Lighthouse).' ),
		) );
	}

	private static function forbidden_claim_words() {
		return array( 'award', 'guarantee', 'clinically', 'proven to', 'limited time', 'best-selling', 'as seen on' );
	}

	/**
	 * Scores one page. $html is the already-rendered content/output for
	 * this page (caller's responsibility to fetch it -- this class makes
	 * no HTTP requests and runs no per-request expensive recalculation
	 * of its own). $classification is the return value of
	 * BHP_Content_Classification::get_classification() when available
	 * (null for non-post pages like the Complete Collection page, which
	 * has no post-based classification).
	 *
	 * @param string     $page_type one of blog|resource|product|collection|landing (documentation only, not scored)
	 * @param string     $html      rendered HTML/text content for this page
	 * @param array|null $classification classification array, or null if not applicable
	 * @param array      $manual_overrides optional pre-supplied scores for 'manual' criteria, e.g. ['mobile_usability' => 8, 'visual_readiness' => 7, 'search_intent_match' => 9]
	 * @return array{page_type:string, criteria:array, overall_score:float|null, overall_state:string}
	 */
	public static function score_page( $page_type, $html, $classification = null, array $manual_overrides = array() ) {
		$results = array();

		$results['audience_clarity'] = self::state_from_bool(
			$classification && 'flat_default' !== ( $classification['source'] ?? 'flat_default' )
		);
		$results['funnel_stage_clarity'] = self::state_from_bool(
			$classification && 'flat_default' !== ( $classification['source'] ?? 'flat_default' )
		);

		$cta_resolves = false;
		if ( $classification && class_exists( 'BHP_CTA_Engine' ) ) {
			$cta_resolves = null !== BHP_CTA_Engine::select_cta( $classification );
		}
		$results['cta_relevance_benefit_clarity'] = self::state_from_bool( $cta_resolves );

		$results['lead_offer_fit'] = self::state_from_bool(
			$classification && ! empty( $classification['featured_book'] ) || ( $classification['primary_goal'] ?? '' ) === 'related_content_engagement'
		);

		$has_price = (bool) preg_match( '/\$\d+(\.\d{2})?/', (string) $html );
		$results['product_relevance'] = in_array( $page_type, array( 'product', 'collection' ), true )
			? self::state_from_bool( $has_price )
			: array( 'state' => null, 'note' => 'Not applicable to this page_type.' );

		$internal_link_count = preg_match_all( '/href="(?:https?:\/\/(?:braveheartspublishing\.com|staging2\.braveheartspublishing\.com))?\/[^"]*"/i', (string) $html );
		$results['internal_link_quality'] = self::state_from_bool( $internal_link_count >= 1 );

		$results['mobile_usability'] = self::manual_result( $manual_overrides['mobile_usability'] ?? null );

		$img_count = preg_match_all( '/<img\b[^>]*>/i', (string) $html, $img_matches );
		$imgs_missing_alt = 0;
		if ( $img_count ) {
			foreach ( $img_matches[0] as $img_tag ) {
				if ( ! preg_match( '/alt="[^"]*"/i', $img_tag ) || preg_match( '/alt=""/i', $img_tag ) ) {
					$imgs_missing_alt++;
				}
			}
		}
		$results['accessibility'] = $img_count > 0
			? self::state_from_bool( 0 === $imgs_missing_alt, "{$imgs_missing_alt}/{$img_count} images missing alt text (proxy check only)" )
			: array( 'state' => self::STATE_PASS, 'note' => 'No images to check (proxy only -- does not cover focus order/contrast/ARIA).' );

		$has_analytics_attr = (bool) preg_match( '/data-bhp-(?:event|impression-event)="/i', (string) $html );
		$results['analytics_coverage'] = self::state_from_bool( $has_analytics_attr );

		$results['attribution_readiness'] = self::state_from_bool( class_exists( 'BHP_UTM_Attribution' ) );

		$claim_hit = null;
		foreach ( self::forbidden_claim_words() as $word ) {
			if ( false !== stripos( (string) $html, $word ) ) {
				$claim_hit = $word;
				break;
			}
		}
		$results['claim_accuracy'] = $claim_hit
			? array( 'state' => self::STATE_FAIL, 'note' => "Forbidden claim word found: '{$claim_hit}' (proxy only -- a full accuracy review is still manual)" )
			: array( 'state' => self::STATE_PASS, 'note' => 'No forbidden claim words found (proxy only -- does not verify factual accuracy of remaining claims)' );

		$results['visual_readiness'] = self::manual_result( $manual_overrides['visual_readiness'] ?? null );
		$results['search_intent_match'] = self::manual_result( $manual_overrides['search_intent_match'] ?? null );

		$lazy_missing = 0;
		if ( $img_count ) {
			foreach ( $img_matches[0] as $img_tag ) {
				if ( false === stripos( $img_tag, 'loading="lazy"' ) ) {
					$lazy_missing++;
				}
			}
		}
		$results['performance_risk'] = $img_count > 2
			? self::state_from_bool( $lazy_missing <= 1, "{$lazy_missing}/{$img_count} images without loading=\"lazy\" (rough proxy only, not a real performance audit)" )
			: array( 'state' => self::STATE_PASS, 'note' => 'Few enough images that lazy-loading is not a meaningful concern (proxy only).' );

		// Attach check_type/weight from the canonical criteria definition
		// and compute the overall score (skips null/manual-unscored criteria).
		$criteria_def = self::criteria();
		$weighted_sum = 0.0;
		$weight_used  = 0.0;
		foreach ( $results as $key => &$result ) {
			$result['check_type'] = $criteria_def[ $key ]['check_type'] ?? 'unknown';
			$result['weight']     = round( $criteria_def[ $key ]['weight'] ?? 0, 2 );
			$result['description'] = $criteria_def[ $key ]['description'] ?? '';
			if ( isset( $result['score'] ) && is_numeric( $result['score'] ) ) {
				$weighted_sum += $result['score'] * $result['weight'];
				$weight_used  += $result['weight'];
			} elseif ( in_array( $result['state'], array( self::STATE_PASS, self::STATE_FAIL, self::STATE_REVISE ), true ) ) {
				$numeric = self::STATE_PASS === $result['state'] ? 10 : ( self::STATE_REVISE === $result['state'] ? 5 : 0 );
				$weighted_sum += $numeric * $result['weight'];
				$weight_used  += $result['weight'];
			}
		}
		unset( $result );

		$overall_score = $weight_used > 0 ? round( $weighted_sum / $weight_used, 2 ) : null;
		$overall_state = null;
		if ( null !== $overall_score ) {
			$overall_state = $overall_score >= 7 ? self::STATE_PASS : ( $overall_score >= 4 ? self::STATE_REVISE : self::STATE_FAIL );
		}

		return array(
			'page_type'      => $page_type,
			'criteria'       => $results,
			'overall_score'  => $overall_score,
			'overall_state'  => $overall_state,
			'scored_weight_coverage' => round( $weight_used, 2 ), // how much of the 1.0 total weight had a real score (rest are unresolved 'manual' criteria)
		);
	}

	private static function state_from_bool( $bool, $note = '' ) {
		return array( 'state' => $bool ? self::STATE_PASS : self::STATE_FAIL, 'note' => $note );
	}

	private static function manual_result( $override_score ) {
		if ( null === $override_score ) {
			return array( 'state' => null, 'score' => null, 'note' => 'No manual review supplied yet -- this criterion cannot be honestly automated.' );
		}
		$score = max( 0, min( 10, (float) $override_score ) );
		return array(
			'state' => $score >= 7 ? self::STATE_PASS : ( $score >= 4 ? self::STATE_REVISE : self::STATE_FAIL ),
			'score' => $score,
			'note'  => 'Manually supplied score.',
		);
	}
}
