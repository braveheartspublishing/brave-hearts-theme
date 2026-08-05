<?php
/**
 * Brave Hearts Publishing — Phase 1E: content opportunity scoring engine.
 *
 * Combines the read-only content inventory (BHP_Content_Inventory) with
 * whatever analytics rows are available (BHP_Analytics_Adapter) to
 * produce prioritized, explainable recommendations. Every score exposes
 * its inputs, weights, confidence, and missing data explicitly -- this
 * class must never produce a number that looks more certain than the
 * data behind it (see design principle 6: "do not hide uncertainty
 * behind false precision").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Opportunity_Engine {

	const RECOMMENDATION_TYPES = array(
		'create_new_article',
		'refresh_existing_article',
		'improve_title_meta',
		'strengthen_internal_links',
		'add_contextual_cta',
		'add_lead_offer',
		'add_product_block',
		'create_teacher_variation',
		'create_parent_variation',
		'create_pinterest_campaign',
		'consolidate_overlapping_articles',
		'monitor',
		'deprioritize',
	);

	/**
	 * Weights sum to 1.0 across the factors that CAN be automatically
	 * computed. Any factor with no available data is excluded from the
	 * weighted sum (not scored as zero) and its absence is recorded in
	 * `missing_signals` + reflected in a reduced `confidence`.
	 */
	const WEIGHTS = array(
		'demand'               => 0.20, // GSC impressions
		'ctr_gap'              => 0.15, // actual CTR vs. expected CTR for its position band
		'trend'                => 0.10, // click trend across available dates
		'conversion_potential' => 0.15, // CTA clicks + lead signups relative to sessions
		'business_relevance'   => 0.15, // featured_book + lead_offer + primary_cta present
		'audience_fit'         => 0.05,
		'funnel_coverage'      => 0.05,
		'internal_link_value'  => 0.05, // orphan penalty
		'cannibalization_risk' => 0.05, // penalty, inverted
		'content_freshness'    => 0.05, // penalty for stale content
	);

	/**
	 * Rough expected-CTR-by-position bands, used only to compute a CTR
	 * *gap* signal (actual vs. expected), never presented as a precise
	 * industry benchmark. This is a coarse heuristic, documented as such.
	 */
	const EXPECTED_CTR_BY_POSITION = array(
		1  => 0.28, 2 => 0.15, 3 => 0.11, 4 => 0.08, 5 => 0.06,
		10 => 0.03, 15 => 0.02, 20 => 0.013,
	);

	private static function expected_ctr_for_position( $position ) {
		$closest = 20;
		foreach ( array_keys( self::EXPECTED_CTR_BY_POSITION ) as $band ) {
			if ( $position <= $band ) {
				$closest = $band;
				break;
			}
		}
		return self::EXPECTED_CTR_BY_POSITION[ $closest ];
	}

	/**
	 * Scores one inventory item. $gsc_rows / $ga4_rows are the rows
	 * already filtered to this item's URL (see
	 * BHP_Analytics_Adapter::get_rows_for_url()) -- this method never
	 * queries analytics itself, keeping it independently testable.
	 */
	public static function score_item( array $item, array $gsc_rows, array $ga4_rows ) {
		$signals         = array();
		$missing_signals = array();

		// --- demand + ctr_gap + trend (from GSC) ---
		if ( ! empty( $gsc_rows ) ) {
			$impressions = array_sum( wp_list_pluck( $gsc_rows, 'impressions' ) );
			$clicks      = array_sum( wp_list_pluck( $gsc_rows, 'clicks' ) );
			$avg_position = array_sum( wp_list_pluck( $gsc_rows, 'position' ) ) / count( $gsc_rows );
			$actual_ctr  = $impressions > 0 ? $clicks / $impressions : 0;
			$expected_ctr = self::expected_ctr_for_position( $avg_position );

			$signals['demand'] = array(
				'value'      => $impressions,
				'normalized' => min( 1.0, $impressions / 5000 ),
				'detail'     => "{$impressions} impressions across " . count( $gsc_rows ) . ' row(s)',
			);
			$signals['ctr_gap'] = array(
				'value'      => $expected_ctr - $actual_ctr,
				'normalized' => max( 0, min( 1.0, ( $expected_ctr - $actual_ctr ) / max( 0.001, $expected_ctr ) ) ),
				'detail'     => sprintf( 'Actual CTR %.2f%% vs. expected ~%.2f%% for position %.1f', $actual_ctr * 100, $expected_ctr * 100, $avg_position ),
			);
			$dates_sorted = $gsc_rows;
			usort( $dates_sorted, static function ( $a, $b ) {
				return strcmp( $a['date'], $b['date'] );
			} );
			$first_clicks = $dates_sorted[0]['clicks'];
			$last_clicks  = $dates_sorted[ count( $dates_sorted ) - 1 ]['clicks'];
			$signals['trend'] = array(
				'value'      => $last_clicks - $first_clicks,
				'normalized' => $last_clicks > $first_clicks ? 0.75 : ( $last_clicks < $first_clicks ? 0.25 : 0.5 ),
				'detail'     => $last_clicks > $first_clicks ? 'Clicks growing' : ( $last_clicks < $first_clicks ? 'Clicks declining' : 'Clicks flat' ),
			);
		} else {
			$missing_signals[] = 'demand';
			$missing_signals[] = 'ctr_gap';
			$missing_signals[] = 'trend';
		}

		// --- conversion_potential (from GA4) ---
		if ( ! empty( $ga4_rows ) ) {
			$sessions      = array_sum( wp_list_pluck( $ga4_rows, 'sessions' ) );
			$cta_clicks    = array_sum( array_map( static function ( $r ) {
				return $r['cta_clicks'] ?? 0;
			}, $ga4_rows ) );
			$lead_signups  = array_sum( array_map( static function ( $r ) {
				return $r['lead_signups'] ?? 0;
			}, $ga4_rows ) );
			$conv_rate = $sessions > 0 ? ( $cta_clicks + $lead_signups ) / $sessions : 0;
			$signals['conversion_potential'] = array(
				'value'      => $conv_rate,
				'normalized' => min( 1.0, $conv_rate * 5 ),
				'detail'     => sprintf( '%d CTA clicks + %d signups across %d sessions', $cta_clicks, $lead_signups, $sessions ),
			);
		} else {
			$missing_signals[] = 'conversion_potential';
		}

		// --- signals derivable purely from the inventory item itself ---
		$business_score = ( ! empty( $item['featured_book'] ) ? 1 : 0 )
			+ ( ! empty( $item['lead_offer'] ) ? 1 : 0 )
			+ ( ! empty( $item['primary_cta'] ) ? 1 : 0 );
		$signals['business_relevance'] = array(
			'value'      => $business_score,
			'normalized' => $business_score / 3,
			'detail'     => "{$business_score}/3 of featured_book, lead_offer, primary_cta present",
		);

		$signals['audience_fit'] = array(
			'value'      => ! empty( $item['audience'] ) ? 1 : 0,
			'normalized' => ! empty( $item['audience'] ) ? 1.0 : 0.0,
			'detail'     => ! empty( $item['audience'] ) ? "Classified as: {$item['audience']}" : 'No audience classification',
		);
		$signals['funnel_coverage'] = array(
			'value'      => ! empty( $item['funnel_stage'] ) ? 1 : 0,
			'normalized' => ! empty( $item['funnel_stage'] ) ? 1.0 : 0.0,
			'detail'     => ! empty( $item['funnel_stage'] ) ? "Funnel stage: {$item['funnel_stage']}" : 'No funnel-stage classification',
		);

		$is_orphan = empty( $item['internal_link_targets'] );
		$signals['internal_link_value'] = array(
			'value'      => $is_orphan ? 0 : 1,
			'normalized' => $is_orphan ? 0.0 : 1.0,
			'detail'     => $is_orphan ? 'No outgoing internal links found (possible orphan)' : count( $item['internal_link_targets'] ) . ' internal link(s) found',
		);

		// cannibalization_risk is populated by the caller (needs full-site
		// context) via $item['_cannibalization_risk']; default to none.
		$cannibal_risk = isset( $item['_cannibalization_risk'] ) ? (float) $item['_cannibalization_risk'] : 0.0;
		$signals['cannibalization_risk'] = array(
			'value'      => $cannibal_risk,
			'normalized' => 1.0 - min( 1.0, $cannibal_risk ),
			'detail'     => $cannibal_risk > 0 ? 'Shares a primary keyword with another published item' : 'No overlapping primary keyword detected',
		);

		if ( ! empty( $item['last_modified_date'] ) ) {
			$days_old = ( time() - strtotime( $item['last_modified_date'] . ' UTC' ) ) / DAY_IN_SECONDS;
			$signals['content_freshness'] = array(
				'value'      => $days_old,
				'normalized' => max( 0, 1.0 - min( 1.0, $days_old / 540 ) ), // 18 months = fully stale
				'detail'     => sprintf( 'Last modified %.0f days ago', $days_old ),
			);
		} else {
			$missing_signals[] = 'content_freshness';
		}

		// --- weighted sum over available signals only ---
		$available_weight = 0.0;
		$weighted_sum      = 0.0;
		foreach ( self::WEIGHTS as $factor => $weight ) {
			if ( isset( $signals[ $factor ] ) ) {
				$weighted_sum      += $weight * $signals[ $factor ]['normalized'];
				$available_weight += $weight;
			}
		}
		$score = $available_weight > 0 ? round( ( $weighted_sum / $available_weight ) * 10, 2 ) : null;

		$confidence = $available_weight >= 0.85 ? 'high' : ( $available_weight >= 0.5 ? 'medium' : 'low' );

		return array(
			'content_id'       => $item['content_id'] ?? null,
			'url'              => $item['url'] ?? null,
			'score'            => $score,
			'confidence'       => $confidence,
			'available_weight' => round( $available_weight, 2 ),
			'signals'          => $signals,
			'missing_signals'  => $missing_signals,
			'recommendation'   => self::recommend( $item, $signals, $missing_signals ),
		);
	}

	/**
	 * Deterministic, rule-based recommendation (not a black box) so the
	 * "recommendation reason" required by the spec is always a plain
	 * sentence derived from the exact rule that fired.
	 */
	private static function recommend( array $item, array $signals, array $missing_signals ) {
		if ( isset( $signals['demand'] ) && isset( $signals['ctr_gap'] ) && $signals['demand']['normalized'] > 0.4 && $signals['ctr_gap']['normalized'] > 0.5 ) {
			return array( 'type' => 'improve_title_meta', 'reason' => 'High impressions with a large CTR gap versus its ranking position -- the title/meta is likely underselling the click.' );
		}
		if ( $signals['internal_link_value']['normalized'] < 1.0 ) {
			return array( 'type' => 'strengthen_internal_links', 'reason' => 'No other page currently links to this content (orphan risk).' );
		}
		if ( $signals['cannibalization_risk']['normalized'] < 1.0 ) {
			return array( 'type' => 'consolidate_overlapping_articles', 'reason' => 'This page shares a primary keyword with at least one other published page.' );
		}
		if ( isset( $signals['content_freshness'] ) && $signals['content_freshness']['normalized'] < 0.3 ) {
			return array( 'type' => 'refresh_existing_article', 'reason' => 'Content has not been modified in a long time relative to typical freshness expectations.' );
		}
		if ( empty( $item['primary_cta'] ) ) {
			return array( 'type' => 'add_contextual_cta', 'reason' => 'This page has no primary CTA classified.' );
		}
		if ( empty( $item['lead_offer'] ) && 'awareness' === ( $item['funnel_stage'] ?? '' ) ) {
			return array( 'type' => 'add_lead_offer', 'reason' => 'Awareness-stage content with no lead offer attached.' );
		}
		if ( count( $missing_signals ) >= 4 ) {
			return array( 'type' => 'monitor', 'reason' => 'Insufficient analytics data to make a confident recommendation yet.' );
		}
		return array( 'type' => 'monitor', 'reason' => 'No specific issue detected against current thresholds; continue monitoring.' );
	}

	/**
	 * Scores an entire inventory in one pass, computing cannibalization
	 * risk from the inventory's own `possible_keyword_cannibalization`
	 * gap list before scoring individual items, then sorts by score
	 * descending (unscored items last, never silently dropped).
	 */
	public static function score_inventory( array $inventory, $sources = null ) {
		$items = $inventory['items'];
		$cannibal_urls = array();
		foreach ( $inventory['gaps']['possible_keyword_cannibalization'] as $group ) {
			foreach ( $group['urls'] as $url ) {
				$cannibal_urls[ $url ] = true;
			}
		}

		$results = array();
		foreach ( $items as $item ) {
			if ( isset( $cannibal_urls[ $item['url'] ] ) ) {
				$item['_cannibalization_risk'] = 1.0;
			}
			$gsc_rows = class_exists( 'BHP_Analytics_Adapter' ) ? BHP_Analytics_Adapter::get_rows_for_url( $item['url'], array( 'gsc' ) ) : array();
			$ga4_rows = class_exists( 'BHP_Analytics_Adapter' ) ? BHP_Analytics_Adapter::get_rows_for_url( $item['url'], array( 'ga4' ) ) : array();
			$results[] = self::score_item( $item, $gsc_rows, $ga4_rows );
		}

		usort( $results, static function ( $a, $b ) {
			if ( null === $a['score'] ) {
				return 1;
			}
			if ( null === $b['score'] ) {
				return -1;
			}
			return $b['score'] <=> $a['score'];
		} );

		return $results;
	}
}
