<?php
/**
 * Brave Hearts Bundle Pricing — testimonial-reward order origin test suite.
 * Plugin 1.8.86. Workstream `CYCLE179-LD-BUILD-397`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-incentive-fulfillment.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ NO DATABASE WRITE OCCURS. Every fixture is an UNSAVED `WC_Order` with a
 *    forced id via `set_id()` — the same pattern
 *    `test-order-provenance.php` and `test-bookvault-fulfillment-eligibility.php`
 *    already use in this directory, and the reason it is reused rather than
 *    improved on: a suite that writes orders needs a cleanup step, and a
 *    cleanup step is a thing that can fail silently and leave probe orders in
 *    Andrew's numbers.
 *
 * ⛔ IT CREATES NO COUPON. The 397 brief forbids it. Coupon codes are attached
 *    as in-memory `WC_Order_Item_Coupon` line items with no coupon post behind
 *    them — which is also the state a real reward order lives in for almost
 *    all of its life, since these coupons are single-use and are deleted after
 *    redemption.
 *
 * ⚠️ THEREFORE RAIL 3 (the coupon META rail) IS NOT EXERCISED HERE. That is a
 *    stated gap, not an oversight. Rails 1 (the order stamp) and 2 (the code
 *    prefix) are the ones that survive the coupon and are the ones tested.
 *
 * ⛔ `stamp_order()` IS NOT CALLED BY THIS SUITE, because it calls `save()`.
 *    Its effect — an order carrying `INCENTIVE_ORDER_META_KEY = yes` — is
 *    exercised by setting the meta in memory instead. The live stamping path
 *    is covered on staging by the theme suite `tests/test-cycle179-397.php`
 *    §3.11, which does write and does clean up.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$GLOBALS['bhp_inc_pass'] = 0;
$GLOBALS['bhp_inc_fail'] = 0;

function bhp_inc_assert( $condition, $label, $detail = '' ) {
	global $bhp_inc_pass, $bhp_inc_fail;
	if ( $condition ) {
		$bhp_inc_pass++;
		echo "PASS: {$label}\n";
		return;
	}
	$bhp_inc_fail++;
	echo "FAIL: {$label}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
}

/**
 * An UNSAVED order carrying coupon line items. Never saved, never read back.
 *
 * @param int      $id     Sentinel id, deliberately outside every known list.
 * @param string[] $codes  Coupon codes to attach as line items.
 * @param string   $status Order status.
 * @return WC_Order
 */
function bhp_inc_make_order( $id, $codes = array(), $status = 'processing' ) {
	$order = new WC_Order();
	$order->set_id( $id ); // never saved -- no database write occurs
	$order->set_status( $status );
	$order->set_total( 2.99 );

	foreach ( (array) $codes as $code ) {
		$item = new WC_Order_Item_Coupon();
		$item->set_code( $code );
		$item->set_discount( 12.99 );
		$order->add_item( $item );
	}

	return $order;
}

if ( ! class_exists( 'BHP_Order_Provenance' ) ) {
	echo "FAIL: BHP_Order_Provenance is not loaded (dashboard module absent?)\n";
	echo "PASS 0  FAIL 1\n";
	return;
}

// ==================== the constant itself ====================

bhp_inc_assert(
	defined( 'BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT' )
		&& 'incentive_fulfillment_order' === BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT,
	'1.1 ORIGIN_INCENTIVE_FULFILLMENT exists with its documented value'
);

/*
 * ⭐⭐ 1.2 IS THE ASSERTION WORTH MORE THAN THE EXCLUSION IT GUARDS.
 *
 * The exclusion was always one line away: stamp `staging_origin_order` and the
 * arithmetic comes out right. ⛔ It comes out right by recording a real book,
 * shipped to a real family, as a fake order — permanently, in the record Andrew
 * reads. This asserts the origin string cannot be quietly swapped back to one
 * that says "test", which is the shortcut a future reader who only checks the
 * numbers would take.
 */
bhp_inc_assert(
	false === strpos( BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT, 'test' ),
	'1.2 the origin does not describe a genuine fulfillment as a test'
);

$labels = BHP_Order_Provenance::origin_labels();
bhp_inc_assert(
	isset( $labels[ BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT ] ),
	'1.3 the origin has a human label'
);

// ==================== prefix matching ====================

bhp_inc_assert( BHP_Order_Provenance::code_is_incentive( 'READER-4K2X' ), '2.1 READER- matches (the brief\'s spelling)' );
bhp_inc_assert( BHP_Order_Provenance::code_is_incentive( 'reader-4k2x' ), '2.2 reader- matches (WooCommerce\'s storage form)' );
bhp_inc_assert( BHP_Order_Provenance::code_is_incentive( 'bhp-thanks-abc' ), '2.3 bhp-thanks- matches (V-9\'s shipped prefix)' );
bhp_inc_assert( ! BHP_Order_Provenance::code_is_incentive( 'parent10' ), '2.4 an ordinary coupon does not match' );
bhp_inc_assert( ! BHP_Order_Provenance::code_is_incentive( 'explore10' ), '2.5 the other live coupon does not match' );
bhp_inc_assert( ! BHP_Order_Provenance::code_is_incentive( 'welcome-reader-10' ), '2.6 a code CONTAINING the word does not match — it is a prefix test, not a substring test' );
bhp_inc_assert( ! BHP_Order_Provenance::code_is_incentive( '' ), '2.7 an empty code does not match' );
bhp_inc_assert( ! BHP_Order_Provenance::code_is_incentive( '   ' ), '2.8 whitespace does not match' );

$prefixes = BHP_Order_Provenance::incentive_coupon_prefixes();
bhp_inc_assert(
	in_array( 'reader-', $prefixes, true ) && in_array( 'bhp-thanks-', $prefixes, true ),
	'2.9 both prefixes are live — the discrepancy is recognised, not resolved by guessing'
);
bhp_inc_assert(
	$prefixes === array_map( 'strtolower', $prefixes ),
	'2.10 every prefix is lower-cased, so it can match what WooCommerce actually stores'
);

// ==================== detection: rail 2, the code prefix ====================

$reward = bhp_inc_make_order( 999999901, array( 'reader-fixture' ) );
bhp_inc_assert( BHP_Order_Provenance::is_incentive_fulfillment( $reward ), '3.1 a READER- order is detected from its code alone' );

$reward_legacy = bhp_inc_make_order( 999999902, array( 'bhp-thanks-fixture' ) );
bhp_inc_assert( BHP_Order_Provenance::is_incentive_fulfillment( $reward_legacy ), '3.2 a bhp-thanks- order is detected from its code alone' );

$ordinary = bhp_inc_make_order( 999999903, array( 'parent10' ) );
bhp_inc_assert( ! BHP_Order_Provenance::is_incentive_fulfillment( $ordinary ), '3.3 an ordinary coupon order is not detected' );

$bare = bhp_inc_make_order( 999999904 );
bhp_inc_assert( ! BHP_Order_Provenance::is_incentive_fulfillment( $bare ), '3.4 an order with no coupon is not detected' );

bhp_inc_assert( ! BHP_Order_Provenance::is_incentive_fulfillment( null ), '3.5 a non-order is not detected' );
bhp_inc_assert( ! BHP_Order_Provenance::is_incentive_fulfillment( new stdClass() ), '3.6 an arbitrary object is not detected' );

// ==================== detection: rail 1, the durable stamp ====================

/*
 * ⭐⭐ 4.1 IS THE DURABILITY PROPERTY, AND THE FIXTURE IS BUILT TO BE HOSTILE:
 *    the stamp is present and there is NO coupon line item at all. That is
 *    harsher than the real end state (where the code survives on the order and
 *    only the coupon post is deleted), so passing it means the reward stays
 *    classified however thoroughly the coupon is tidied away.
 */
$stamped = bhp_inc_make_order( 999999905 );
$stamped->update_meta_data( BHP_Order_Provenance::INCENTIVE_ORDER_META_KEY, 'yes' );
bhp_inc_assert(
	BHP_Order_Provenance::is_incentive_fulfillment( $stamped ),
	'4.1 the stamp alone classifies an order with no coupon trace left on it'
);

$stamped_no = bhp_inc_make_order( 999999906 );
$stamped_no->update_meta_data( BHP_Order_Provenance::INCENTIVE_ORDER_META_KEY, 'no' );
bhp_inc_assert(
	! BHP_Order_Provenance::is_incentive_fulfillment( $stamped_no ),
	'4.2 a stamp value other than yes does not classify'
);

bhp_inc_assert(
	BHP_Order_Provenance::INCENTIVE_ORDER_META_KEY !== BHP_Order_Provenance::OVERRIDE_META_KEY,
	'4.3 ⭐ the reward stamp does not spend the manual-override channel'
);

// ==================== exclusion ====================

$cls = BHP_Order_Provenance::classify( $reward );
bhp_inc_assert( BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT === $cls['origin'], '5.1 classify() returns the reward origin', $cls['origin'] );
bhp_inc_assert( BHP_Order_Provenance::STATUS_AUDIT_ONLY === $cls['reporting_status'], '5.2 reporting status is audit_only', $cls['reporting_status'] );
bhp_inc_assert( ! BHP_Order_Provenance::is_executive_eligible( $reward ), '5.3 ⭐⭐ excluded from executive KPIs — revenue, AOV, orders, units and the purchase event all gate here' );
bhp_inc_assert( BHP_Order_Provenance::is_executive_eligible( $ordinary ), '5.4 an ordinary order is still included (the exclusion is not indiscriminate)' );
bhp_inc_assert(
	false !== stripos( $cls['reason'], 'NOT because it is a test' ),
	'5.5 the reason states, in the record itself, that this is not a test order'
);

// ==================== precedence ====================

$overridden = bhp_inc_make_order( 999999907, array( 'reader-fixture' ) );
$overridden->update_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER );
bhp_inc_assert(
	BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER === BHP_Order_Provenance::classify( $overridden )['origin'],
	'6.1 ⭐ a human override still outranks the reward classification'
);

/*
 * ⭐ 6.2 documents the DELIBERATE choice recorded in classify(): a reward order
 *    that fails payment is still reported as a payment failure. Both readings
 *    were defensible; the tie was broken by blast radius. It is asserted so the
 *    choice is visible and a later change to it is a decision, not a drift.
 */
$failed_reward = bhp_inc_make_order( 999999908, array( 'reader-fixture' ), 'failed' );
bhp_inc_assert(
	BHP_Order_Provenance::ORIGIN_FAILED_PAYMENT === BHP_Order_Provenance::classify( $failed_reward )['origin'],
	'6.2 a FAILED reward order is still reported as a payment failure (deliberate — see classify())',
	BHP_Order_Provenance::classify( $failed_reward )['origin']
);

// ==================== the shared predicate ====================

bhp_inc_assert( function_exists( 'bhp_is_incentive_fulfillment_order' ), '7.1 the shared predicate the theme calls exists' );
if ( function_exists( 'bhp_is_incentive_fulfillment_order' ) ) {
	bhp_inc_assert( bhp_is_incentive_fulfillment_order( $reward ), '7.2 it agrees with the class on a reward order' );
	bhp_inc_assert( ! bhp_is_incentive_fulfillment_order( $ordinary ), '7.3 it agrees with the class on an ordinary order' );
}

// ==================== the stamper's hooks ====================

foreach (
	array(
		'woocommerce_store_api_checkout_order_processed',
		'woocommerce_checkout_order_processed',
		'woocommerce_order_status_completed',
	) as $hook
) {
	bhp_inc_assert(
		5 === has_action( $hook, 'bhp_incentive_fulfillment_stamp' ),
		"8. the stamper is registered on {$hook} at priority 5"
	);
}

echo "\nPASS {$GLOBALS['bhp_inc_pass']}  FAIL {$GLOBALS['bhp_inc_fail']}\n";
if ( $GLOBALS['bhp_inc_fail'] > 0 ) {
	echo "FAILURES: {$GLOBALS['bhp_inc_fail']}\n";
}
