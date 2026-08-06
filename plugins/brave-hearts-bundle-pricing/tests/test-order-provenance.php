<?php
/**
 * Brave Hearts Dashboard — order-provenance (dataset-origin) test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-order-provenance.php --user=1
 *
 * Exercises BHP_Order_Provenance::classify()/is_executive_eligible()
 * against UNSAVED WC_Order stub objects with a forced ID (via set_id(),
 * never save()) -- the same safe, no-database-write pattern used by
 * test-bookvault-fulfillment-eligibility.php. No real order is read or
 * written by this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_prov_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_prov_make_order( $id, $status = 'processing', $total = 17.99 ) {
	$order = new WC_Order();
	$order->set_id( $id ); // never saved -- no database write occurs
	$order->set_status( $status );
	$order->set_total( $total );
	return $order;
}

// ==================== Live production customer order (default case) ====================
$order = bhp_prov_make_order( 999999801 ); // definite, unused sentinel ID -- not in any known list
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER === $c['origin'], 'Unknown, non-listed order ID -> classified as live production customer order', $failures );
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_INCLUDE === $c['reporting_status'], 'Live production customer order -> included in executive KPIs', $failures );
bhp_prov_test_assert( true === BHP_Order_Provenance::is_executive_eligible( $order ), 'is_executive_eligible() true for a genuine live order', $failures );

// ==================== Confirmed internal test order (e.g. #351/#353/#355) ====================
$order = bhp_prov_make_order( 351 );
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_PAYMENT_TEST === $c['origin'], 'Order #351 (documented Bookvault verification test) -> classified as production payment test', $failures );
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_AUDIT_ONLY === $c['reporting_status'], 'Confirmed test order -> audit-only, not executive-eligible', $failures );
bhp_prov_test_assert( false === BHP_Order_Provenance::is_executive_eligible( $order ), 'is_executive_eligible() false for a confirmed test order', $failures );

// ==================== Confirmed test order, unrefunded stub (e.g. #351) ====================
// Note: the ORIGIN_PAYMENT_TEST vs. ORIGIN_REFUNDED_TEST distinction
// inside classify() depends on WC_Order::get_total_refunded(), which
// WooCommerce computes from real WC_Order_Refund child records -- there
// is no public setter to fake this on an unsaved stub (confirmed:
// method_exists('WC_Order','set_total_refunded') is false). That branch
// is verified separately against the real, already-refunded order #317
// during staging/production validation (see docs/order-provenance-audit.md)
// rather than here, so this suite stays deterministic and
// database-independent like the rest of the test files in this plugin.
$order = bhp_prov_make_order( 317 );
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert(
	in_array( $c['origin'], array( BHP_Order_Provenance::ORIGIN_PAYMENT_TEST, BHP_Order_Provenance::ORIGIN_REFUNDED_TEST ), true ),
	'Order #317 (documented test order) classifies as a test-order variant regardless of refund-state branch',
	$failures
);
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_AUDIT_ONLY === $c['reporting_status'], 'Order #317 -> audit-only either way', $failures );

// ==================== Confirmed internal fulfillment test with manual Bookvault record (#336) ====================
// Andrew confirmed (2026-07-06) #336 was his own internal Mariana Trench
// paperback fulfillment test: a real live-mode payment that did not route
// to Bookvault automatically, which he then fulfilled manually in the
// Bookvault portal (ref BV2793822 / manual reference 43908-#00001).
// Previously (before this confirmation) this order sat in
// NEEDS_CONFIRMATION_ORDER_IDS and classified as ORIGIN_UNKNOWN.
$order = bhp_prov_make_order( 336 );
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_INTERNAL_FULFILLMENT_TEST === $c['origin'], 'Order #336 (confirmed by Andrew) -> classified as a production live-mode internal fulfillment test, not unknown', $failures );
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_AUDIT_ONLY === $c['reporting_status'], 'Confirmed internal fulfillment test -> audit-only, not executive-eligible', $failures );
bhp_prov_test_assert( false === BHP_Order_Provenance::is_executive_eligible( $order ), 'is_executive_eligible() false for #336 -- real payment/fulfillment, but still not an external customer sale', $failures );
bhp_prov_test_assert( false !== strpos( $c['reason'], 'BV2793822' ), 'Order #336 classification reason cites its manual Bookvault reference', $failures );

bhp_prov_test_assert(
	array() === BHP_Order_Provenance::NEEDS_CONFIRMATION_ORDER_IDS,
	'NEEDS_CONFIRMATION_ORDER_IDS is empty now that #336 has been confirmed and moved to KNOWN_TEST_ORDER_IDS',
	$failures
);

// ==================== Manual Bookvault fulfillment lookup ====================
$manual = BHP_Order_Provenance::manual_bookvault_fulfillment( 336 );
bhp_prov_test_assert( is_array( $manual ) && 'BV2793822' === $manual['bookvault_ref'], 'manual_bookvault_fulfillment(336) returns the confirmed Bookvault reference', $failures );
bhp_prov_test_assert( '43908-#00001' === $manual['manual_reference'], 'manual_bookvault_fulfillment(336) returns the confirmed manual reference number', $failures );
bhp_prov_test_assert( null === BHP_Order_Provenance::manual_bookvault_fulfillment( 351 ), 'manual_bookvault_fulfillment() returns null for an order with no manual Bookvault record (e.g. #351, which Bookvault declined and no one manually fulfilled)', $failures );

// ==================== Failed payments: genuine vs. test-cluster ====================
$order = bhp_prov_make_order( 999999802, 'failed' ); // not in the known-test list
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_FAILED_PAYMENT === $c['origin'], 'Failed order outside any test cluster -> classified as failed payment', $failures );
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_FAILURE_ONLY === $c['reporting_status'], 'Genuine failed payment -> failure-only reporting status', $failures );
bhp_prov_test_assert( false === strpos( $c['reason'], 'test cluster' ), 'Genuine failed payment reason text does not claim test-cluster membership', $failures );

$order = bhp_prov_make_order( 319, 'failed' ); // documented member of the test cluster
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_FAILED_PAYMENT === $c['origin'], 'Failed order #319 (test cluster) -> still classified as failed payment (status-based)', $failures );
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_FAILURE_ONLY === $c['reporting_status'], 'Test-cluster failed order -> failure-only, never counted as executive revenue', $failures );
bhp_prov_test_assert( false !== strpos( $c['reason'], 'test cluster' ), 'Test-cluster failed order reason text explicitly says so', $failures );

// ==================== get_classified_payment_failures() distinguishes genuine vs test failures by ID membership ====================
bhp_prov_test_assert(
	in_array( 319, BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS, true ),
	'Order #319 is a documented member of KNOWN_TEST_ORDER_IDS (used directly by get_classified_payment_failures(), not by parsing reason text)',
	$failures
);

// ==================== Manual override takes precedence over everything ====================
$order = bhp_prov_make_order( 999999803 ); // would otherwise classify as live customer
$order->add_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, BHP_Order_Provenance::ORIGIN_STAGING );
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_STAGING === $c['origin'], 'Manual provenance override meta takes precedence over the default live-customer classification', $failures );
bhp_prov_test_assert( BHP_Order_Provenance::STATUS_AUDIT_ONLY === $c['reporting_status'], 'Manually overridden staging-origin order -> audit-only', $failures );

$order2 = bhp_prov_make_order( 351 ); // would otherwise classify as a confirmed test
$order2->add_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER );
$c2 = BHP_Order_Provenance::classify( $order2 );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER === $c2['origin'], 'Manual override can also correct a listed test order back to live-customer if ever needed', $failures );
bhp_prov_test_assert( true === BHP_Order_Provenance::is_executive_eligible( $order2 ), 'Overridden order becomes executive-eligible', $failures );

// ==================== Invalid override value is ignored (fails safe) ====================
$order = bhp_prov_make_order( 999999804 );
$order->add_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, 'not_a_real_origin_constant' );
$c = BHP_Order_Provenance::classify( $order );
bhp_prov_test_assert( BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER === $c['origin'], 'An invalid override value is ignored, falling back to normal classification rather than accepting garbage input', $failures );

// ==================== Every KNOWN_TEST_ORDER_IDS entry is excluded from executive KPIs ====================
foreach ( BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS as $test_id ) {
	$o = bhp_prov_make_order( $test_id );
	bhp_prov_test_assert(
		false === BHP_Order_Provenance::is_executive_eligible( $o ),
		"Known test order #{$test_id} is excluded from executive KPIs",
		$failures
	);
}

echo empty( $failures ) ? "\nALL ORDER-PROVENANCE TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
