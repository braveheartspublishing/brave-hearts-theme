<?php
/**
 * GA4 behavioral-analytics adapter contract (Analytics Phase 1B).
 *
 * Deliberately separate from BHP_Analytics_Provider_Interface
 * (interface-bhp-analytics-provider.php), which is shaped for AD-SPEND
 * reconciliation (Meta/Pinterest ad accounts: campaign_id, amount_spent,
 * impressions, CPC). GA4's own Data API reports sessions/users/traffic-
 * source/landing-page/device dimensions, not ad spend -- forcing both
 * into one interface would mean half of every record is always null for
 * whichever shape doesn't apply. Two small, honest contracts are clearer
 * than one contract with two purposes.
 *
 * NOT wired into the live dashboard yet -- dormant scaffold code, same
 * status as the ad-spend contract it sits beside. A future session
 * implements a real GA4 Data API client against this interface once
 * OAuth/service-account credentials exist (see docs/analytics-architecture.md).
 *
 * Design rule, same as the ad-spend contract: the dashboard UI never
 * touches GA4's raw API response shape directly, and WooCommerce always
 * remains authoritative for actual orders/revenue -- GA4 data here is
 * behavioral/attributed only, never summed into WooCommerce's own
 * revenue figures (Phase 13 requirement, see
 * BHP_GA4_Null_Provider's docblock for the reconciliation rule this
 * protects).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface BHP_GA4_Provider_Interface {

	public function is_configured();

	/**
	 * @return string|null e.g. "No GA4 property/credentials configured"; null when is_configured() is true
	 */
	public function get_configuration_issue();

	/**
	 * Already-fetched, already-cached daily aggregate records -- never a
	 * live synchronous GA4 Data API call during a dashboard page load
	 * (Phase 13: "No external API call during every dashboard page load").
	 *
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return array[] one record per date, each a plain associative array:
	 *     - 'date'                   string 'Y-m-d'
	 *     - 'sessions'               int|null
	 *     - 'users'                  int|null
	 *     - 'new_users'              int|null
	 *     - 'returning_users'        int|null
	 *     - 'ecommerce_purchases'    int|null   GA4-attributed purchase count -- NEVER added to WooCommerce's own order count
	 *     - 'purchase_revenue'       float|null GA4-attributed revenue -- NEVER added to WooCommerce's own revenue
	 *     - 'currency'               string|null
	 */
	public function get_daily_metrics( $start, $end );

	/**
	 * Breakdown rows for a given local-date range, one per dimension
	 * value -- e.g. traffic source/medium, campaign, landing page, or
	 * device category, selected by $dimension.
	 *
	 * @param string   $dimension 'source_medium'|'campaign'|'landing_page'|'device_category'
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return array[] each row: array( 'dimension_value' => string, 'sessions' => int|null, 'conversions' => int|null, 'revenue' => float|null )
	 */
	public function get_breakdown( $dimension, $start, $end );

	/**
	 * @return array { @type string|null $last_success, @type string $status 'ok'|'stale'|'failed'|'never_synced', @type string|null $failure_reason }
	 */
	public function get_sync_status();
}
