<?php
/**
 * Brave Hearts Publishing — Phase 1E: post-publish analytics feedback loop.
 *
 * Evaluates already-published content against whatever analytics data
 * has been imported via BHP_Analytics_Adapter, for standard review
 * windows. Never redirects, deletes, or edits anything -- output is a
 * recommendation for a human to act on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Feedback_Loop {

	const WINDOWS = array( 7, 28, 90 );

	public static function evaluate_url( $url, $window_days = 28 ) {
		if ( ! class_exists( 'BHP_Analytics_Adapter' ) ) {
			return array( 'url' => $url, 'window_days' => $window_days, 'data_available' => false, 'metrics' => array(), 'recommendation' => self::no_data_recommendation() );
		}

		$rows = BHP_Analytics_Adapter::get_rows_for_url( $url );
		$cutoff = gmdate( 'Y-m-d', time() - ( $window_days * DAY_IN_SECONDS ) );
		$rows_in_window = array_values( array_filter( $rows, static function ( $row ) use ( $cutoff ) {
			return $row['date'] >= $cutoff;
		} ) );

		if ( empty( $rows_in_window ) ) {
			return array( 'url' => $url, 'window_days' => $window_days, 'data_available' => false, 'metrics' => array(), 'recommendation' => self::no_data_recommendation() );
		}

		$metrics = self::aggregate_metrics( $rows_in_window );
		$is_fixture = ! empty( array_filter( $rows_in_window, static function ( $r ) {
			return ! empty( $r['_is_fixture'] );
		} ) );

		return array(
			'url'            => $url,
			'window_days'    => $window_days,
			'data_available' => true,
			'data_is_fixture' => $is_fixture,
			'metrics'        => $metrics,
			'recommendation' => self::recommend( $metrics ),
		);
	}

	private static function aggregate_metrics( array $rows ) {
		$sum = static function ( $field ) use ( $rows ) {
			return array_sum( array_map( static function ( $r ) use ( $field ) {
				return $r[ $field ] ?? 0;
			}, $rows ) );
		};

		return array(
			'impressions'        => $sum( 'impressions' ),
			'clicks'             => $sum( 'clicks' ),
			'ctr'                => $sum( 'impressions' ) > 0 ? round( $sum( 'clicks' ) / $sum( 'impressions' ), 4 ) : null,
			'average_position'   => self::average( $rows, 'position' ),
			'sessions'           => $sum( 'sessions' ),
			'engagement_rate'    => self::average( $rows, 'engagement_rate' ),
			'cta_clicks'         => $sum( 'cta_clicks' ),
			'lead_form_starts'   => $sum( 'lead_form_starts' ),
			'lead_signups'       => $sum( 'lead_signups' ),
			'product_views'      => $sum( 'product_views' ),
			'add_to_carts'       => $sum( 'add_to_carts' ),
			'checkout_starts'    => $sum( 'checkout_starts' ),
			'purchases'          => $sum( 'purchases' ),
			'revenue'            => $sum( 'revenue' ),
			'pinterest_impressions' => $sum( 'impressions' ), // shared field name; source-scoped by caller if needed
			'pinterest_saves'    => $sum( 'saves' ),
			'pinterest_outbound_clicks' => $sum( 'outbound_clicks' ),
		);
	}

	private static function average( array $rows, $field ) {
		$values = array_filter( array_map( static function ( $r ) use ( $field ) {
			return isset( $r[ $field ] ) ? (float) $r[ $field ] : null;
		}, $rows ), static function ( $v ) {
			return null !== $v;
		} );
		return empty( $values ) ? null : round( array_sum( $values ) / count( $values ), 4 );
	}

	private static function no_data_recommendation() {
		return array( 'type' => 'monitor_longer', 'reason' => 'No analytics data imported yet for this URL in the requested window -- import data before drawing conclusions.' );
	}

	/**
	 * Deterministic, rule-based recommendation -- same "no black box"
	 * principle as the opportunity engine.
	 */
	private static function recommend( array $metrics ) {
		if ( null !== $metrics['ctr'] && $metrics['ctr'] < 0.015 && $metrics['impressions'] > 1000 ) {
			return array( 'type' => 'improve_title', 'reason' => 'High impressions but very low CTR -- title/snippet likely underperforming its ranking position.' );
		}
		if ( $metrics['sessions'] > 0 && 0 === $metrics['cta_clicks'] ) {
			return array( 'type' => 'change_cta', 'reason' => 'Sessions are occurring but zero CTA clicks recorded -- current CTA may be missing, mismatched, or below the fold.' );
		}
		if ( $metrics['sessions'] > 50 && 0 === $metrics['lead_signups'] && $metrics['lead_form_starts'] > 0 ) {
			return array( 'type' => 'add_lead_offer', 'reason' => 'Visitors start the lead form but never complete it -- reconsider the offer or form friction.' );
		}
		if ( $metrics['pinterest_saves'] > 0 && 0 === $metrics['pinterest_outbound_clicks'] ) {
			return array( 'type' => 'create_more_pinterest_variants', 'reason' => 'Pin is being saved but not clicked through -- try a different headline angle.' );
		}
		if ( null !== $metrics['average_position'] && $metrics['average_position'] > 20 && $metrics['impressions'] < 100 ) {
			return array( 'type' => 'deprioritize_topic', 'reason' => 'Low ranking and low impressions after the review window -- limited demand signal so far.' );
		}
		return array( 'type' => 'keep_unchanged', 'reason' => 'No specific issue detected against current thresholds.' );
	}

	/** Evaluates all three standard windows in one call for a report. */
	public static function evaluate_all_windows( $url ) {
		$result = array();
		foreach ( self::WINDOWS as $days ) {
			$result[ $days . '_day' ] = self::evaluate_url( $url, $days );
		}
		return $result;
	}
}
