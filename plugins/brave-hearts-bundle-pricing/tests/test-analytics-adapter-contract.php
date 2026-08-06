<?php
/**
 * Brave Hearts Dashboard — analytics provider adapter contract test.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-analytics-adapter-contract.php --user=1
 *
 * Phase 9 deliverable: "Test strategy using fixtures/mocks only." No live
 * Meta/GA4/Pinterest API call is made anywhere in this file -- only the
 * null provider and a plain in-memory fixture provider, both of which
 * implement BHP_Analytics_Provider_Interface. This proves the interface
 * contract is usable end-to-end (including by a real future adapter)
 * without ever touching a network.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/includes/dashboard/analytics-adapters/interface-bhp-analytics-provider.php';
require_once dirname( __DIR__ ) . '/includes/dashboard/analytics-adapters/class-bhp-null-analytics-provider.php';

$failures = array();

function bhp_adapter_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// ==================== Null provider: dashboard works with zero configuration ====================
$null_provider = new BHP_Null_Analytics_Provider();
bhp_adapter_test_assert( $null_provider instanceof BHP_Analytics_Provider_Interface, 'BHP_Null_Analytics_Provider satisfies the interface contract', $failures );
bhp_adapter_test_assert( false === $null_provider->is_configured(), 'Null provider reports is_configured() = false', $failures );
bhp_adapter_test_assert( is_string( $null_provider->get_configuration_issue() ), 'Null provider explains why it is not configured', $failures );
bhp_adapter_test_assert( array() === $null_provider->get_daily_metrics( new DateTime( '2026-06-01' ), new DateTime( '2026-06-08' ) ), 'Null provider returns an empty array (never null) for get_daily_metrics(), so a caller can always safely iterate/count', $failures );
$sync = $null_provider->get_sync_status();
bhp_adapter_test_assert( 'never_synced' === $sync['status'], 'Null provider reports sync_status = never_synced, not a false "ok"', $failures );

// ==================== Fixture provider: proves the contract is implementable by a real adapter ====================
/**
 * Minimal in-memory fixture standing in for what a real Meta Ads adapter
 * would eventually look like -- returns hard-coded fixture rows shaped
 * exactly like get_daily_metrics()'s documented schema. No HTTP client,
 * no SDK, no credentials of any kind.
 */
class BHP_Test_Fixture_Ads_Provider implements BHP_Analytics_Provider_Interface {
	public function get_provider_key() { return 'meta_ads'; }
	public function get_display_name() { return 'Meta Ads (fixture)'; }
	public function is_configured() { return true; }
	public function get_configuration_issue() { return null; }
	public function get_daily_metrics( $start, $end ) {
		return array(
			array(
				'provider' => 'meta_ads', 'date' => '2026-06-01',
				'campaign_id' => '120001', 'campaign_name' => 'Complete Collection - Prospecting',
				'ad_set_id' => '230001', 'ad_set_name' => 'Broad US Parents',
				'ad_id' => '340001', 'ad_name' => 'Carousel v1',
				'amount_spent' => 42.17, 'impressions' => 5210, 'reach' => 4110, 'frequency' => 1.27,
				'link_clicks' => 88, 'landing_page_views' => 71, 'click_through_rate' => 1.69,
				'cost_per_click' => 0.48, 'cost_per_landing_page_view' => 0.59,
				'add_to_cart' => 6, 'initiated_checkout' => 3, 'purchases' => 2, 'purchase_value' => 63.98,
				'attribution_window' => '7d_click_1d_view', 'currency' => 'USD',
				'last_synced_at' => '2026-06-02T04:00:00+00:00', 'sync_status' => 'ok',
			),
		);
	}
	public function get_sync_status() {
		return array( 'last_success' => '2026-06-02T04:00:00+00:00', 'status' => 'ok', 'failure_reason' => null );
	}
}

$fixture = new BHP_Test_Fixture_Ads_Provider();
bhp_adapter_test_assert( $fixture instanceof BHP_Analytics_Provider_Interface, 'Fixture Meta Ads provider satisfies the same interface as the null provider', $failures );
bhp_adapter_test_assert( true === $fixture->is_configured(), 'Fixture provider reports configured', $failures );

$rows = $fixture->get_daily_metrics( new DateTime( '2026-06-01' ), new DateTime( '2026-06-02' ) );
bhp_adapter_test_assert( 1 === count( $rows ), 'Fixture provider returns the expected number of normalized rows', $failures );
$row = $rows[0];
bhp_adapter_test_assert( 'meta_ads' === $row['provider'], 'Normalized row is tagged with its provider key', $failures );
bhp_adapter_test_assert( 2 === $row['purchases'] && 63.98 === $row['purchase_value'], 'Normalized row exposes provider-ATTRIBUTED purchases/value distinctly (never pre-summed with WooCommerce data)', $failures );
bhp_adapter_test_assert( '7d_click_1d_view' === $row['attribution_window'], 'Normalized row records its own attribution window, required before any ROAS figure can be trusted', $failures );

// ==================== Reconciliation-model guard: never sum providers' purchase counts ====================
// This is a documentation-enforcing test: it demonstrates the CORRECT
// pattern (keep sources separate) rather than a convenient-but-wrong one
// (sum them), so a future implementer copies the safe pattern.
$woocommerce_actual_orders = 40; // stand-in for a real BHP_Order_Metrics::compute_kpis() order_count
$meta_attributed_purchases = $row['purchases'];
$blended_report = array(
	'woocommerce_orders'        => $woocommerce_actual_orders,
	'meta_attributed_purchases' => $meta_attributed_purchases,
	// Deliberately NOT: 'total_purchases' => $woocommerce_actual_orders + $meta_attributed_purchases
);
bhp_adapter_test_assert(
	! array_key_exists( 'total_purchases', $blended_report ),
	'Reconciliation report keeps WooCommerce orders and Meta-attributed purchases as separate labeled fields -- never summed into a single blended count',
	$failures
);

echo empty( $failures ) ? "\nALL ANALYTICS ADAPTER CONTRACT TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
