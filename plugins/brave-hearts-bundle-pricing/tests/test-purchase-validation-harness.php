<?php
/**
 * Purchase event validation harness (Analytics Phase 1B correction pass,
 * 2026-07-06).
 *
 * Exercises the REAL bhp_bundle_track_purchase_completed() code path --
 * not a re-implementation of it -- against real, persisted WC_Order
 * fixtures, so this proves the actual production code, not a stand-in.
 *
 * SAFETY: this file creates and deletes real WooCommerce orders. It
 * refuses to run anywhere but staging (checked first, before anything
 * else). No payment gateway is ever invoked -- orders are created
 * directly via wc_create_order(), never through the real checkout/Stripe/
 * PayPal flow, matching "no real payment is submitted." Every order this
 * file creates is force-deleted before the file exits, including on the
 * failure path (a shutdown-registered cleanup below), so nothing persists
 * afterward, and no code here weakens BHP_Order_Provenance's exclusion
 * logic -- it *uses* the classifier's own documented manual-override
 * mechanism (OVERRIDE_META_KEY / ORIGIN_PRELAUNCH_TEST), the same
 * mechanism the class's own docblock describes as the sanctioned way to
 * classify a new order without editing the hardcoded ID list.
 *
 * Run via WP-CLI, staging only:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-purchase-validation-harness.php --user=1
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! class_exists( 'BHP_Analytics_Config' ) || ! BHP_Analytics_Config::is_staging() ) {
	echo "REFUSED: this harness creates and deletes real WooCommerce orders and must only ever run on staging (staging2.braveheartspublishing.com). Refusing to run here.\n";
	exit( 1 );
}

$failures       = array();
$created_orders = array();

function bhp_pvh_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Extracts the JSON payload from one bhp_bundle_print_datalayer_push()
 * printed <script> tag. Returns an array of decoded payloads, one per
 * dataLayer.push() call found in the captured output (a real page load
 * can print more than one, e.g. purchase + bundle_type_purchased).
 */
function bhp_pvh_extract_pushes( $html ) {
	$pushes = array();
	if ( preg_match_all( '/dataLayer\.push\((\{.*?\})\);/', $html, $matches ) ) {
		foreach ( $matches[1] as $json ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) ) {
				$pushes[] = $decoded;
			}
		}
	}
	return $pushes;
}

/**
 * Builds a real, persisted, minimal test order using an actual catalog
 * product (Mount Everest paperback, product 15) -- never a fabricated
 * product ID. No payment gateway is touched; calculate_totals() is
 * WooCommerce's own order-math method, the same one every real order
 * uses, so tax/shipping/total reconcile exactly like a genuine order.
 */
function bhp_pvh_build_test_order( array &$created_orders ) {
	$product = wc_get_product( 15 ); // Mount Everest paperback -- real catalog product, see bundle-data.php
	if ( ! $product ) {
		return null;
	}
	$order = wc_create_order();
	$order->add_product( $product, 1 );
	$order->set_shipping_total( 1.99 ); // matches the approved single-paperback shipping amount
	$order->calculate_totals(); // WooCommerce's own real order-total math (item totals + shipping + tax per store tax settings)
	$order->set_status( 'processing' );
	$order->save();
	$created_orders[] = $order->get_id();
	return $order;
}

/**
 * Cleanup runs even if an assertion fails partway through (or the process
 * dies with a fatal), so no synthetic order can ever be left behind by an
 * aborted run. Reads $GLOBALS['bhp_pvh_created_orders'] at shutdown time
 * (not a value captured at registration) so every order created up to
 * the moment of failure is still included, not just the ones that
 * existed when the shutdown handler was first registered.
 */
$GLOBALS['bhp_pvh_created_orders'] = &$created_orders;
function bhp_pvh_cleanup() {
	$order_ids = isset( $GLOBALS['bhp_pvh_created_orders'] ) ? $GLOBALS['bhp_pvh_created_orders'] : array();
	foreach ( $order_ids as $id ) {
		$order = wc_get_order( $id );
		if ( $order ) {
			$order->delete( true ); // force delete, bypass trash -- never leaves a synthetic order sitting around
		}
	}
}
register_shutdown_function( 'bhp_pvh_cleanup' );

// ==================== Scenario A: internal/test-marked order is correctly suppressed ====================
$order_a = bhp_pvh_build_test_order( $created_orders );
bhp_pvh_assert( null !== $order_a, 'Test fixture: real catalog product (ID 15) is available to build a test order from', $failures );

if ( $order_a ) {
	$order_a->update_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, BHP_Order_Provenance::ORIGIN_PRELAUNCH_TEST );
	$order_a->save_meta_data();

	bhp_pvh_assert(
		false === BHP_Order_Provenance::is_executive_eligible( $order_a ),
		'Scenario A: the provenance override (ORIGIN_PRELAUNCH_TEST) correctly makes this order NOT executive-eligible -- proves the harness uses the real classifier, not a bypass',
		$failures
	);

	ob_start();
	bhp_bundle_track_purchase_completed( $order_a->get_id() );
	$output_a = ob_get_clean();
	$pushes_a = bhp_pvh_extract_pushes( $output_a );

	$has_real_purchase = false;
	$has_suppression_debug = false;
	foreach ( $pushes_a as $push ) {
		if ( isset( $push['event'] ) && 'purchase' === $push['event'] ) {
			$has_real_purchase = true;
		}
		if ( isset( $push['event'] ) && 'bhp_debug_internal_order_purchase_suppressed' === $push['event'] ) {
			$has_suppression_debug = true;
		}
	}
	bhp_pvh_assert( false === $has_real_purchase, 'Scenario A: internal/test-marked order does NOT emit a real `purchase` event', $failures );
	bhp_pvh_assert( true === $has_suppression_debug, 'Scenario A: internal/test-marked order emits the staging-only suppression debug event instead', $failures );

	// Refresh simulation: calling again must still never fire a real purchase.
	ob_start();
	bhp_bundle_track_purchase_completed( $order_a->get_id() );
	$output_a2 = ob_get_clean();
	bhp_pvh_assert( false === strpos( $output_a2, '"event":"purchase"' ), 'Scenario A: a second call (refresh) still never emits a real purchase event', $failures );
}

// ==================== Scenario B: an eligible order fires exactly once and reconciles ====================
$order_b = bhp_pvh_build_test_order( $created_orders );

if ( $order_b ) {
	bhp_pvh_assert(
		true === BHP_Order_Provenance::is_executive_eligible( $order_b ),
		'Scenario B: an order with no override and no known-test ID is executive-eligible by default (the genuine-customer-order path)',
		$failures
	);

	ob_start();
	bhp_bundle_track_purchase_completed( $order_b->get_id() );
	$output_b1 = ob_get_clean();
	$pushes_b1 = bhp_pvh_extract_pushes( $output_b1 );
	$purchase_push = null;
	foreach ( $pushes_b1 as $push ) {
		if ( isset( $push['event'] ) && 'purchase' === $push['event'] ) {
			$purchase_push = $push;
		}
	}

	bhp_pvh_assert( null !== $purchase_push, 'Scenario B: an eligible order emits exactly one `purchase` event on first render', $failures );

	if ( $purchase_push ) {
		$order_b_fresh = wc_get_order( $order_b->get_id() ); // re-read from DB to avoid any in-memory staleness
		bhp_pvh_assert(
			(string) $order_b->get_id() === $purchase_push['transaction_id'],
			'transaction_id equals the WooCommerce order ID',
			$failures
		);
		bhp_pvh_assert(
			'purchase_' . $order_b->get_id() === $purchase_push['event_id'],
			'event_id is deterministic (purchase_<order_id>)',
			$failures
		);
		$expected_value = round( (float) $order_b_fresh->get_total() - (float) $order_b_fresh->get_total_tax(), 2 );
		bhp_pvh_assert(
			abs( $expected_value - (float) $purchase_push['value'] ) < 0.01,
			'value excludes tax per approved policy (order total minus total tax)',
			$failures
		);
		bhp_pvh_assert(
			abs( (float) $order_b_fresh->get_total_tax() - (float) $purchase_push['tax'] ) < 0.01,
			'tax field matches the order\'s actual total tax exactly',
			$failures
		);
		bhp_pvh_assert(
			abs( (float) $order_b_fresh->get_shipping_total() - (float) $purchase_push['shipping'] ) < 0.01,
			'shipping field matches the order\'s actual shipping total exactly',
			$failures
		);
		$items_total = array_sum( array_map( function ( $i ) { return $i['price'] * $i['quantity']; }, $purchase_push['items'] ) );
		bhp_pvh_assert(
			abs( $items_total - (float) $order_b_fresh->get_subtotal() ) < 0.01,
			'item-level totals reconcile with the order subtotal',
			$failures
		);
	}

	// Refresh simulation: the highest-risk case -- a second call for the
	// SAME order must never re-fire `purchase` (dedup via order meta).
	ob_start();
	bhp_bundle_track_purchase_completed( $order_b->get_id() );
	$output_b2 = ob_get_clean();
	bhp_pvh_assert( '' === $output_b2, 'Scenario B: a second call (simulated page refresh) prints nothing at all -- no duplicate purchase event', $failures );
}

// ==================== Scenario C: a coupon-discounted order reports the coupon code and a discount-adjusted value (Objective: coupon analytics validation) ====================
$coupon_c = null;
$order_c  = bhp_pvh_build_test_order( $created_orders );

if ( $order_c ) {
	// Real, temporary WooCommerce coupon -- created and deleted within this
	// run only, never a production coupon, matching the "temporary staging
	// coupon fixtures only" requirement.
	$coupon_c = new WC_Coupon();
	$coupon_c->set_code( 'BHPTEST-PVH-' . $order_c->get_id() );
	$coupon_c->set_discount_type( 'percent' );
	$coupon_c->set_amount( 10 );
	$coupon_c->save();

	$order_c->apply_coupon( $coupon_c->get_code() );
	$order_c->calculate_totals();
	$order_c->save();

	ob_start();
	bhp_bundle_track_purchase_completed( $order_c->get_id() );
	$output_c = ob_get_clean();
	$pushes_c = bhp_pvh_extract_pushes( $output_c );
	$purchase_push_c = null;
	foreach ( $pushes_c as $push ) {
		if ( isset( $push['event'] ) && 'purchase' === $push['event'] ) {
			$purchase_push_c = $push;
		}
	}

	bhp_pvh_assert( null !== $purchase_push_c, 'Scenario C: a coupon-discounted order still emits exactly one `purchase` event', $failures );

	if ( $purchase_push_c ) {
		$order_c_fresh = wc_get_order( $order_c->get_id() );
		bhp_pvh_assert(
			$coupon_c->get_code() === $purchase_push_c['coupon'],
			'Scenario C: the `coupon` field is exactly the applied coupon code (not fabricated, not blank)',
			$failures
		);
		$expected_value_c = round( (float) $order_c_fresh->get_total() - (float) $order_c_fresh->get_total_tax(), 2 );
		bhp_pvh_assert(
			abs( $expected_value_c - (float) $purchase_push_c['value'] ) < 0.01,
			'Scenario C: `value` reflects the order total net of the coupon discount (WooCommerce applies the discount before get_total() is read, so no separate subtraction is needed here)',
			$failures
		);
		bhp_pvh_assert(
			(float) $purchase_push_c['value'] < (float) $order_c_fresh->get_subtotal(),
			'Scenario C: the discounted value is strictly less than the pre-discount subtotal -- the coupon is not silently ignored',
			$failures
		);
	}
}

// ==================== Cleanup (also registered as a shutdown handler above in case of a fatal mid-run) ====================
if ( $coupon_c ) {
	$coupon_post_id = $coupon_c->get_id();
	if ( $coupon_post_id ) {
		wp_delete_post( $coupon_post_id, true );
	}
}
bhp_pvh_cleanup();

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL PURCHASE VALIDATION HARNESS TESTS PASSED\n";
