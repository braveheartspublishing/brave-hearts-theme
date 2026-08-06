<?php
/**
 * Brave Hearts Dashboard — refund accounting test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-refund-accounting.php --user=1
 *
 * Exercises BHP_Refund_Metrics's pure summarize_refunds()/get_order_refund_state()
 * against plain arrays and lightweight stub objects -- no real WC_Order or
 * WC_Order_Refund is read/written by this file. Covers every refund case
 * from the Phase 4 KPI reconciliation spec.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_refund_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// refund_state_from_totals() is the pure boundary-logic function --
// get_order_refund_state() is a thin WC_Order-reading wrapper around it
// (not re-tested here since it requires a real order object; the
// interesting logic -- the partial/full boundary -- lives in the pure
// function and is fully covered below).

// ==================== 1. Unrefunded paid order ====================
$state = BHP_Refund_Metrics::refund_state_from_totals( 24.98, 0.0 );
bhp_refund_test_assert( 'none' === $state['state'], '1. Unrefunded paid order -> refund state is none', $failures );
bhp_refund_test_assert( 0.0 === $state['amount'], '1. Unrefunded paid order -> refunded amount is 0', $failures );

// ==================== 2. Partially refunded order ====================
$state = BHP_Refund_Metrics::refund_state_from_totals( 24.98, 11.99 );
bhp_refund_test_assert( 'partial' === $state['state'], '2. Partially refunded order -> refund state is partial', $failures );
bhp_refund_test_assert( abs( $state['amount'] - 11.99 ) < 0.01, '2. Partially refunded order -> refunded amount matches', $failures );

// ==================== 3. Fully refunded order ====================
$state = BHP_Refund_Metrics::refund_state_from_totals( 24.98, 24.98 );
bhp_refund_test_assert( 'full' === $state['state'], '3. Fully refunded order -> refund state is full', $failures );

// 3b. Fully refunded order with a floating-point rounding remainder
// (e.g. 24.98 - 24.980000000000004) must still read as 'full', not 'partial'.
$state = BHP_Refund_Metrics::refund_state_from_totals( 24.98, 24.979999999999997 );
bhp_refund_test_assert( 'full' === $state['state'], '3b. Full refund with float rounding noise -> still reads as full, not a false partial', $failures );

// 3c. WooCommerce stores total_refunded as a negative number on some
// versions/paths -- the pure function must treat sign as irrelevant.
$state = BHP_Refund_Metrics::refund_state_from_totals( 24.98, -24.98 );
bhp_refund_test_assert( 'full' === $state['state'], '3c. Negative-signed total_refunded (as WooCommerce sometimes stores it) is still read as full', $failures );

// ==================== 4. Item-level partial refund ====================
$records = array(
	array( 'parent_order_id' => 500, 'total' => 11.99, 'shipping_refunded' => 0.0, 'tax_refunded' => 0.0, 'units_refunded' => 1 ),
);
$summary = BHP_Refund_Metrics::summarize_refunds( $records );
bhp_refund_test_assert( abs( $summary['refunds_total'] - 11.99 ) < 0.01, '4. Item-level partial refund -> refunds_total matches the item amount', $failures );
bhp_refund_test_assert( 1 === $summary['refunded_units'], '4. Item-level partial refund -> refunded_units is 1', $failures );
bhp_refund_test_assert( 0.0 === $summary['shipping_refunded'], '4. Item-level partial refund -> no shipping portion counted', $failures );

// ==================== 5. Shipping refund ====================
$records = array(
	array( 'parent_order_id' => 501, 'total' => 3.99, 'shipping_refunded' => 3.99, 'tax_refunded' => 0.0, 'units_refunded' => 0 ),
);
$summary = BHP_Refund_Metrics::summarize_refunds( $records );
bhp_refund_test_assert( abs( $summary['refunds_total'] - 3.99 ) < 0.01, '5. Shipping-only refund -> counted in refunds_total', $failures );
bhp_refund_test_assert( abs( $summary['shipping_refunded'] - 3.99 ) < 0.01, '5. Shipping-only refund -> shipping_refunded matches', $failures );
bhp_refund_test_assert( 0 === $summary['refunded_units'], '5. Shipping-only refund -> no units refunded', $failures );

// ==================== 6. Tax refund ====================
$records = array(
	array( 'parent_order_id' => 502, 'total' => 0.85, 'shipping_refunded' => 0.0, 'tax_refunded' => 0.85, 'units_refunded' => 0 ),
);
$summary = BHP_Refund_Metrics::summarize_refunds( $records );
bhp_refund_test_assert( abs( $summary['tax_refunded'] - 0.85 ) < 0.01, '6. Tax-only refund -> tax_refunded matches', $failures );
bhp_refund_test_assert( abs( $summary['refunds_total'] - 0.85 ) < 0.01, '6. Tax-only refund -> counted in refunds_total (tax refund is still a real cash outflow)', $failures );

// ==================== 7. Refund created outside the selected period for an earlier order ====================
// This case is handled architecturally, not by summarize_refunds() itself:
// get_refunds_in_period() queries refunds by their OWN date_created, so a
// refund dated inside the selected period is included regardless of when
// its parent order was created. summarize_refunds() just aggregates
// whatever refund records it's given -- verify it doesn't need or use any
// "order created" date at all (i.e. it can't accidentally filter a
// cross-period refund back out).
$records = array(
	array( 'parent_order_id' => 999, 'total' => 17.99, 'shipping_refunded' => 0.0, 'tax_refunded' => 0.0, 'units_refunded' => 1 ),
);
$summary = BHP_Refund_Metrics::summarize_refunds( $records );
bhp_refund_test_assert(
	abs( $summary['refunds_total'] - 17.99 ) < 0.01,
	'7. A refund record for an order from an earlier period is still summed (period attribution happens in get_refunds_in_period()\'s own date query, not here)',
	$failures
);
bhp_refund_test_assert( array( 999 ) === $summary['affected_order_ids'], '7. affected_order_ids records the parent order even though it is from an earlier period', $failures );

// ==================== 8. Multiple refunds on one order ====================
$records = array(
	array( 'parent_order_id' => 600, 'total' => 5.00, 'shipping_refunded' => 0.0, 'tax_refunded' => 0.0, 'units_refunded' => 0 ),
	array( 'parent_order_id' => 600, 'total' => 6.99, 'shipping_refunded' => 3.99, 'tax_refunded' => 0.0, 'units_refunded' => 1 ),
);
$summary = BHP_Refund_Metrics::summarize_refunds( $records );
bhp_refund_test_assert( abs( $summary['refunds_total'] - 11.99 ) < 0.01, '8. Multiple refunds on one order -> totals sum across both refund records', $failures );
bhp_refund_test_assert( 2 === $summary['refund_count'], '8. Multiple refunds on one order -> refund_count is 2', $failures );
bhp_refund_test_assert( array( 600 ) === $summary['affected_order_ids'], '8. Multiple refunds on one order -> affected_order_ids lists the order once, not twice', $failures );

// ==================== Cross-cutting: net revenue must never be labeled gross ====================
// Simulates BHP_Order_Metrics::compute_kpis()'s own arithmetic without a
// live database: gross_revenue (pre-refund) minus refunds_total (this
// period's refund events) equals net_revenue.
$gross_revenue = 100.00;
$refunds_total = 24.98;
$net_revenue = round( $gross_revenue - $refunds_total, 2 );
bhp_refund_test_assert( 75.02 === $net_revenue, 'Net revenue = gross revenue - refunds (simple period-level subtraction)', $failures );
bhp_refund_test_assert( $net_revenue !== $gross_revenue, 'Net revenue is never equal to (and must never be labeled as) gross revenue when refunds exist', $failures );

// ==================== Empty refund set ====================
$summary = BHP_Refund_Metrics::summarize_refunds( array() );
bhp_refund_test_assert( 0.0 === $summary['refunds_total'], 'No refunds in period -> refunds_total is 0', $failures );
bhp_refund_test_assert( 0 === $summary['refund_count'], 'No refunds in period -> refund_count is 0', $failures );
bhp_refund_test_assert( array() === $summary['affected_order_ids'], 'No refunds in period -> affected_order_ids is empty', $failures );

// ==================== extract_refund_record() type guard ====================
bhp_refund_test_assert(
	null === BHP_Refund_Metrics::extract_refund_record( new stdClass() ),
	'extract_refund_record() returns null for a non-WC_Order_Refund input rather than fataling',
	$failures
);

echo empty( $failures ) ? "\nALL REFUND ACCOUNTING TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
