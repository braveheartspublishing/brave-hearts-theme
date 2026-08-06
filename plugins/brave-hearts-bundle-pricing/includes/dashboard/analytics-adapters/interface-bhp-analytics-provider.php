<?php
/**
 * Provider-neutral analytics adapter contract (Phase 9 architecture spec).
 *
 * NOT wired into the live dashboard yet -- this file is dormant scaffold
 * code, not required from dashboard-bootstrap.php. It exists so a future
 * session can implement a real Meta/GA4/Pinterest/email adapter against a
 * stable, already-reviewed contract instead of designing one under
 * pressure later. See docs/meta-ads-dashboard-integration.md for the full
 * architecture this supports.
 *
 * Design rule: the dashboard UI must never talk to a provider's raw API
 * response shape directly. Every provider (Meta today, GA4/Pinterest/
 * email later) normalizes its own data into the single schema documented
 * on get_daily_metrics() below, so a new provider never requires a
 * dashboard UI change.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface BHP_Analytics_Provider_Interface {

	/**
	 * Machine-readable provider identifier, e.g. 'meta_ads', 'ga4',
	 * 'pinterest', 'email'. Used as a label/source tag in the UI and in
	 * the normalized metric records below -- never used to branch
	 * dashboard rendering logic on a specific provider's identity.
	 */
	public function get_provider_key();

	/**
	 * Human-readable label for the admin UI, e.g. "Meta Ads".
	 */
	public function get_display_name();

	/**
	 * Whether this provider has valid, non-expired configuration (API
	 * credentials present, reachable, etc.) -- checked WITHOUT making a
	 * live API call. A provider that returns false here must still allow
	 * the rest of the dashboard to render normally; it never fatals or
	 * blocks the page.
	 *
	 * @return bool
	 */
	public function is_configured();

	/**
	 * Human-readable reason is_configured() returned false, e.g.
	 * "No access token configured" or "Token expired 2026-06-01" --
	 * NEVER includes the token/secret value itself, even partially.
	 * Returns null when is_configured() is true.
	 *
	 * @return string|null
	 */
	public function get_configuration_issue();

	/**
	 * Reads already-fetched, already-normalized daily aggregate records
	 * for the given local-date range from this plugin's OWN cache/storage
	 * -- never a live synchronous call to the provider's API. Fetching
	 * fresh data from the provider is a separate, explicitly scheduled
	 * background operation (see docs/meta-ads-dashboard-integration.md,
	 * section E) -- a dashboard page load must never block on an external
	 * HTTP call.
	 *
	 * @param DateTime $start Local midnight, inclusive.
	 * @param DateTime $end   Exclusive upper bound (see BHP_Dashboard_Page::parse_custom_range()).
	 * @return array[] List of normalized metric records, one per
	 *     provider-reported date/breakdown row. Each record is a plain
	 *     associative array with these keys (any provider that cannot
	 *     supply a given metric returns null for it, never a fabricated
	 *     0 or omission that would be misread as "zero spend"):
	 *
	 *     - 'provider'            string   e.g. 'meta_ads' (matches get_provider_key())
	 *     - 'date'                string   'Y-m-d', the provider's own reporting date (its ad-account timezone, not necessarily the site's)
	 *     - 'campaign_id'         string|null
	 *     - 'campaign_name'       string|null
	 *     - 'ad_set_id'           string|null
	 *     - 'ad_set_name'         string|null
	 *     - 'ad_id'               string|null
	 *     - 'ad_name'             string|null
	 *     - 'amount_spent'        float|null   in the account's own currency
	 *     - 'impressions'         int|null
	 *     - 'reach'               int|null
	 *     - 'frequency'           float|null
	 *     - 'link_clicks'         int|null
	 *     - 'landing_page_views'  int|null
	 *     - 'click_through_rate'  float|null   percentage, not a fraction
	 *     - 'cost_per_click'      float|null
	 *     - 'cost_per_landing_page_view' float|null
	 *     - 'add_to_cart'         int|null     provider-attributed, NOT WooCommerce's own count
	 *     - 'initiated_checkout'  int|null     provider-attributed
	 *     - 'purchases'           int|null     provider-ATTRIBUTED purchase count -- never to be added to a WooCommerce order count (see reconciliation model)
	 *     - 'purchase_value'      float|null   provider-attributed conversion value
	 *     - 'attribution_window'  string|null  e.g. '7d_click_1d_view', exactly as configured on the ad account
	 *     - 'currency'            string|null  ISO 4217 code
	 *     - 'last_synced_at'      string|null  ISO 8601, when THIS record was last refreshed from the provider
	 *     - 'sync_status'         string       'ok' | 'stale' | 'failed' | 'never_synced'
	 */
	public function get_daily_metrics( $start, $end );

	/**
	 * When this provider's data was last successfully synchronized, and
	 * whether the most recent sync attempt succeeded -- surfaced in the
	 * UI so a stale or broken feed is obvious rather than silently shown
	 * as if it were current.
	 *
	 * @return array { @type string|null $last_success (ISO 8601), @type string $status 'ok'|'stale'|'failed'|'never_synced', @type string|null $failure_reason (redacted, never a raw API error containing a token) }
	 */
	public function get_sync_status();
}
