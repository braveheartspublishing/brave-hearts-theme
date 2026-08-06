<?php
/**
 * Null-object implementation of BHP_GA4_Provider_Interface (Analytics
 * Phase 1B). Lets the dashboard render a "GA4: Not connected" panel today
 * without any conditional branching once a real provider exists later --
 * the real implementation is a drop-in replacement behind the same
 * interface.
 *
 * Reconciliation rule this class exists to protect (Phase 13): WooCommerce
 * remains authoritative for actual orders and revenue everywhere in this
 * dashboard. GA4 is authoritative only for behavioral analytics and
 * attributed journeys. A future real GA4 adapter's `purchase_revenue` and
 * `ecommerce_purchases` fields must always be displayed ALONGSIDE
 * WooCommerce's own figures with the discrepancy shown explicitly --
 * never summed together into one number. This null implementation
 * returns empty arrays specifically so there is nothing to accidentally
 * sum in the first place.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/interface-bhp-ga4-provider.php';

class BHP_GA4_Null_Provider implements BHP_GA4_Provider_Interface {

	public function is_configured() {
		return '' !== BHP_Analytics_Config::ga4_measurement_id();
		// Note: even when a Measurement ID is configured, this null
		// provider still returns empty data below -- is_configured()
		// only reflects whether a real adapter COULD be built, not that
		// one has been (that requires GA4 Data API credentials, a
		// separate, later step -- see docs/analytics-architecture.md).
	}

	public function get_configuration_issue() {
		if ( $this->is_configured() ) {
			return 'GA4 Measurement ID is set, but no GA4 Data API credentials are configured yet -- reporting data is not yet available.';
		}
		return 'No GA4 property configured yet. See docs/analytics-architecture.md for the manual setup steps required.';
	}

	public function get_daily_metrics( $start, $end ) {
		return array();
	}

	public function get_breakdown( $dimension, $start, $end ) {
		return array();
	}

	public function get_sync_status() {
		return array(
			'last_success'   => null,
			'status'         => 'never_synced',
			'failure_reason' => null,
		);
	}
}
