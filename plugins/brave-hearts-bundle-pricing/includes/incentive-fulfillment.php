<?php
/**
 * THE TESTIMONIAL-REWARD ORDER STAMPER.
 * Bundle plugin 1.8.86. Workstream `CYCLE179-LD-BUILD-397`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ WHAT THIS CLOSES, AND WHY IT NEEDED A PLUGIN CHANGE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A testimonial reward is a real book, printed by Bookvault, shipped to a real
 * family, paid for with a 100 percent coupon. ⛔ MEASURED UNDER SEAL 1300 ON
 * STAGING 2026-09-07, NOT ASSUMED: such an order does NOT net $0 on this store
 * — it nets **$2.99**, the whole gap being the shipping line, because a
 * coupon's "allow free shipping" flag enables a `free_shipping` METHOD and no
 * such method exists in any zone.
 *
 * ⛔ SO A REWARD ORDER CARRIES REAL MONEY AND REAL UNITS INTO THE EXECUTIVE
 *    NUMBERS. Left alone it inflates order count, drags AOV down toward $2.99,
 *    and fires a `purchase` dataLayer event that reaches GA4 as a sale.
 *
 * ⭐ `commerce-cx` identified the exclusion in the 396 build and DELIBERATELY
 *    DID NOT WORK AROUND IT. The available mechanism was
 *    `_bhp_order_provenance_override`, and every `ORIGIN_*` constant it would
 *    accept meant some flavour of "test order". Stamping one onto a genuine
 *    shipment to a genuine reader would have solved the arithmetic by filing a
 *    real family's book as a fake order, permanently, in the record Andrew
 *    reads. ⭐ THAT REFUSAL IS WHY THIS FILE EXISTS. The fix was one constant
 *    away the whole time; the constant had to be an honest one.
 *
 * `BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT` is that constant. See
 * its docblock for what `STATUS_AUDIT_ONLY` buys and why no consumer needed
 * changing.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY THE STAMP IS WRITTEN AT CHECKOUT RATHER THAN COMPUTED ON READ
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `BHP_Order_Provenance::is_incentive_fulfillment()` can answer from the
 * coupon code alone, with no database read, so classification would work with
 * no stamp at all. The stamp is written anyway, for two reasons that are about
 * time rather than correctness:
 *
 *   1 · ⭐ THE PREFIX LIST IS NOT FROZEN. It currently holds two entries and
 *       one of them is a live discrepancy awaiting Andrew's decision (see that
 *       constant's docblock). An order classified from a list that later
 *       changes is an order whose REPORTED HISTORY CHANGES UNDER IT. The stamp
 *       freezes the answer at the moment the evidence was unambiguous.
 *   2 · ⭐ IT IS THE CHEAP RAIL. A dashboard pass over a year of orders reads
 *       one meta value instead of walking every order's coupon items.
 *
 * ⛔ THE STAMP IS NEVER CLEARED. An order that was a reward stays a reward,
 *    including after the single-use coupon that justified it is deleted —
 *    which is the normal end state, not an edge case.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE THREE HOOKS, AND WHY IT IS THREE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Identical to the set the theme's V-9 module already uses, deliberately, so
 * the two stamps are written at the same three moments and neither can be
 * present without the other:
 *
 *   `woocommerce_store_api_checkout_order_processed`  the Blocks checkout,
 *                                                     which is how this store
 *                                                     actually takes orders
 *   `woocommerce_checkout_order_processed`            the legacy shortcode
 *                                                     checkout, still reachable
 *   `woocommerce_order_status_completed`              the backstop, for an
 *                                                     order created any other
 *                                                     way — admin, import, CLI
 *
 * ⛔ NO OPTION, PRODUCT, VARIATION, PRICE, COUPON, STOCK, SHIPPING, TAX,
 *    PAYMENT OR CHECKOUT SETTING IS READ FOR MODIFICATION OR WRITTEN BY THIS
 *    FILE, ON ANY ENVIRONMENT. It writes exactly one order meta key.
 *
 * ⚠️ IT DEGRADES TO A NO-OP WHEN THE DASHBOARD MODULE IS ABSENT.
 *    `BHP_Order_Provenance` lives in `includes/dashboard/`, which is
 *    deliberately omissible from a storefront-only release (see
 *    `bhp_bundle_pricing_load_dashboard_module()`), so every entry point here
 *    is guarded by `class_exists()`. A release without the dashboard has
 *    nothing to classify FOR, so not stamping costs nothing — and the theme's
 *    V-9 suppression keeps working regardless, on its own rails.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stamp a reward order, if it is one.
 *
 * @param int|WC_Order $order_or_id
 * @return void
 */
function bhp_incentive_fulfillment_stamp( $order_or_id ) {
	if ( ! class_exists( 'BHP_Order_Provenance' ) ) {
		return;
	}
	BHP_Order_Provenance::stamp_order( $order_or_id );
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_incentive_fulfillment_stamp', 5 );
add_action( 'woocommerce_checkout_order_processed', 'bhp_incentive_fulfillment_stamp', 5 );
add_action( 'woocommerce_order_status_completed', 'bhp_incentive_fulfillment_stamp', 5 );

/**
 * Is this order a testimonial reward fulfillment?
 *
 * ⭐ THE ONE PUBLIC ENTRY POINT OTHER CODE SHOULD CALL, and the reason it is a
 *    plain function rather than a static method: the THEME calls it, the theme
 *    must not depend on this plugin being active, and a `function_exists()`
 *    test at the call site reads better than a `class_exists()` plus a method
 *    check. See `inc/postpurchase-suppression.php` rail 0.
 *
 * @param WC_Order $order
 * @return bool FALSE when the classifier is not loaded — never a guess.
 */
function bhp_is_incentive_fulfillment_order( $order ) {
	if ( ! class_exists( 'BHP_Order_Provenance' ) ) {
		return false;
	}
	return BHP_Order_Provenance::is_incentive_fulfillment( $order );
}
