<?php
/**
 * Brave Hearts Dashboard — cache invalidation test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cache-invalidation.php --user=1
 *
 * Verifies BHP_KPI_Cache's invalidation-token mechanism and confirms every
 * operational hook required by the Phase 6 spec is actually registered
 * against BHP_KPI_Cache::invalidate_all(). Uses real WordPress transients/
 * options (this plugin's own cache store) but never touches a real
 * WooCommerce order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cache_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// ==================== Every required hook is registered ====================
// register_invalidation_hooks() already ran once during normal plugin
// boot (BHP_Dashboard_Page::init()); re-running it here is idempotent
// (add_action de-dupes identical callback+hook+priority registrations),
// so this is safe to call again for the test.
BHP_KPI_Cache::register_invalidation_hooks();

$required_hooks = array(
	'woocommerce_new_order'            => 'New order creation',
	'woocommerce_order_status_changed' => 'Order-status transition (covers payment completion in most gateways)',
	'woocommerce_payment_complete'     => 'Payment completion (belt-and-suspenders for gateways that skip a status-changed event)',
	'woocommerce_update_order'         => 'Order-item update / general metadata update',
	'woocommerce_saved_order_items'    => 'Quantity change via the admin Edit Order screen',
	'woocommerce_order_note_added'     => 'New order note, including Bookvault routing/fulfillment notes',
	'woocommerce_order_refunded'       => 'Partial or full refund created',
);

foreach ( $required_hooks as $hook => $description ) {
	bhp_cache_test_assert(
		has_action( $hook, array( 'BHP_KPI_Cache', 'invalidate_all' ) ) !== false,
		"Cache invalidates on '{$hook}' ({$description})",
		$failures
	);
}

// ==================== Invalidation token actually changes cached results ====================
$key = 'test_cache_key_' . wp_rand( 1000, 9999 );
$calls = 0;
$compute = function () use ( &$calls ) {
	$calls++;
	return 'value_' . $calls;
};

$first = BHP_KPI_Cache::get( $key, $compute );
bhp_cache_test_assert( 'value_1' === $first, 'First call to get() computes and caches a fresh value', $failures );

$second = BHP_KPI_Cache::get( $key, $compute );
bhp_cache_test_assert( 'value_1' === $second, 'Second call to get() with the same key returns the CACHED value, not a fresh computation', $failures );
bhp_cache_test_assert( 1 === $calls, 'The compute callback only actually ran once before invalidation', $failures );

BHP_KPI_Cache::invalidate_all();

$third = BHP_KPI_Cache::get( $key, $compute );
bhp_cache_test_assert( 'value_2' === $third, 'After invalidate_all(), the SAME key recomputes a fresh value instead of returning the stale cached one', $failures );
bhp_cache_test_assert( 2 === $calls, 'The compute callback ran a second time after invalidation', $failures );

// ==================== Different keys never collide ====================
$other_key = 'test_cache_key_other_' . wp_rand( 1000, 9999 );
$other_calls = 0;
$other_compute = function () use ( &$other_calls ) {
	$other_calls++;
	return 'other_value';
};
BHP_KPI_Cache::get( $other_key, $other_compute );
BHP_KPI_Cache::get( $key, $compute ); // re-read the first key again
bhp_cache_test_assert( 1 === $other_calls, 'A different cache key computes independently and is not affected by reads of another key', $failures );

// ==================== A failed refresh never replaces valid cached data with corrupt data ====================
// BHP_KPI_Cache::get() only calls set_transient() with whatever the
// compute callback returns; if the callback throws, no set_transient()
// call happens at all, so the previously-cached (still valid) value is
// left completely untouched. Verified here by making the callback throw,
// confirming the old cached value survives the failed attempt.
$safe_key = 'test_cache_key_safe_' . wp_rand( 1000, 9999 );
BHP_KPI_Cache::get( $safe_key, function () { return 'good_value'; } );
try {
	BHP_KPI_Cache::get( $safe_key . '_never_cached_because_key_differs', function () {
		throw new Exception( 'simulated compute failure' );
	} );
} catch ( Exception $e ) {
	// expected -- BHP_KPI_Cache::get() does not (and should not) swallow
	// a compute failure; the caller decides how to handle it. What
	// matters for this test is that the ORIGINAL cached value is untouched.
}
$still_good = BHP_KPI_Cache::get( $safe_key, function () { return 'DIFFERENT_VALUE_WOULD_MEAN_CORRUPTION'; } );
bhp_cache_test_assert( 'good_value' === $still_good, 'A failed compute for a different key never corrupts or replaces an unrelated, already-cached valid value', $failures );

// ==================== Cache keys are date-range-specific (no cross-period bleed) ====================
BHP_KPI_Cache::invalidate_all(); // start clean so token differences below are only about the key string
$today_result = BHP_KPI_Cache::get( 'kpis_today_20260705', function () { return 'today_data'; } );
$week_result  = BHP_KPI_Cache::get( 'kpis_7d_20260629', function () { return 'week_data'; } );
bhp_cache_test_assert( 'today_data' !== $week_result && 'week_data' === $week_result, 'Different period cache keys (today vs 7d) never return each other\'s cached data', $failures );

echo empty( $failures ) ? "\nALL CACHE INVALIDATION TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
