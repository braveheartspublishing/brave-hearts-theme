<?php
/**
 * Brave Hearts Bundle Pricing — N1, the CHECKOUT-PAGE cross-sell module.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS IS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew, production walk, 2026-08-03, verbatim: "On the checkout page- we
 * should have the same style 'Add This Adventure- Save $x' as the cart drawer
 * but on the checkout page - we need to be able to upsell on the checkout
 * page - and once they get to 3 books they get the full collection discount."
 * (Relayed through the Chief of Staff; NOT witnessed first-hand by this agent.)
 *
 * The cart drawer has carried that module since B4. The checkout page — the
 * one page every buyer must pass through, and the last place an extra title
 * can still be added without a second decision to shop — had nothing.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE SECOND HALF OF ANDREW'S SENTENCE WAS ALREADY TRUE, AND IT WAS
 *    VERIFIED LIVE BEFORE ANY OF THIS WAS WRITTEN RATHER THAN ASSUMED.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * "once they get to 3 books they get the full collection discount" is not a
 * thing this build had to implement — `bhp_bundle_apply_discount_fees()` has
 * always applied it by cart composition, and the Blocks checkout renders the
 * resulting fee. Measured on staging 1.19.157, real Blocks checkout, real
 * browser, both states read out of the rendered totals rows:
 *
 *     2 hardcovers  Subtotal $35.98 · Bundle Savings (Hardcover) -$2.99
 *                   · Contiguous US Shipping $3.99 · Total $36.98
 *     3 hardcovers  Subtotal $53.97 · Bundle Savings (Hardcover) -$4.98
 *                   · Contiguous US Shipping $4.99 · Total $53.98
 *
 * So the reward moment already exists and already renders. What this module
 * adds is the SURFACE that gets a customer to it — and the QA requirement is
 * therefore to prove that the row appears, visibly, in place, at the moment
 * the third book lands from this module's own button.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO — read before extending it
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - It computes NO price, discount, saving, shipping amount or total.
 *     Every number the module shows comes from `bhp_bundle_rules()` by way of
 *     the drawer's own exported `crossSellSavings()`; every number in the
 *     TOTALS comes from WooCommerce. There is no second copy of the maths.
 *   - It changes NO WooCommerce setting, product, variation, price, coupon,
 *     stock, shipping, tax or payment record, on any environment. It calls no
 *     `update_option()` and writes nothing.
 *   - It registers no block, ships no build step and adds no React
 *     dependency. It is one small stylesheet and one small script, loaded on
 *     the checkout page only.
 *   - It never touches the submit path, the payment methods or the order.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The module's copy, in ONE place.
 *
 * ⭐ THE TWO CUSTOMER-FACING STRINGS ARE THE DRAWER'S, VERBATIM, AND THAT IS
 *    THE REQUIREMENT RATHER THAN A CONVENIENCE. Andrew asked for "the same
 *    style 'Add This Adventure- Save $x' as the cart drawer". The label and
 *    its savings clause are assembled by the shared script exactly as the
 *    drawer assembles them — "Add This Adventure", then " - Save $X.XX"
 *    appended only when a real, non-zero saving exists.
 *
 *    ⛔ A HYPHEN, NEVER AN EM DASH. The sitewide em-dash purge (commit
 *       3ef65be) applies to new copy too, and the drawer's B4 note says so.
 *
 * ⛔ THE HEADING IS THE ONLY NEW STRING, AND IT MAKES NO CLAIM. "Complete the
 *    collection" states what the button does. It promises no outcome, quotes
 *    no statistic, and names no discount the totals will not show.
 */
function bhp_bundle_checkout_upsell_copy() {
	return array(
		'heading'     => __( 'Complete the collection', 'brave-hearts' ),
		'cta'         => __( 'Add This Adventure', 'brave-hearts' ),
		/* translators: %s: formatted money amount, e.g. $1.99 */
		'ctaSavings'  => __( ' - Save %s', 'brave-hearts' ),
		'adding'      => __( 'Adding…', 'brave-hearts' ),
		'failed'      => __( 'That book could not be added. Please try again.', 'brave-hearts' ),
		/* translators: %s: book title */
		'addAria'     => __( 'Add %s to your order', 'brave-hearts' ),
	);
}

/**
 * Enqueue on the CHECKOUT PAGE ONLY, and never on the order-received screen.
 *
 * ⛔ `is_order_received_page()` is excluded deliberately and it is not
 *    defensive padding: `is_checkout()` returns TRUE on the thank-you page,
 *    because order-received is a checkout endpoint. Without that exclusion
 *    this module would offer "Add This Adventure - Save $2.99" to somebody who
 *    has already paid, and the button would silently modify a cart that no
 *    longer has anything to do with their completed order. That is the single
 *    worst failure this file could have, so it is guarded at the enqueue.
 *
 * The script declares `bhp-cart-drawer` as a dependency for two reasons, both
 * load-bearing: it guarantees `window.bhpDrawerData` (catalog + bundleRules)
 * exists before this runs, and it guarantees `window.bhpBundleCrossSell` — the
 * shared maths — has been defined.
 */
function bhp_bundle_checkout_upsell_assets() {
	if ( is_admin() || ! function_exists( 'is_checkout' ) ) {
		return;
	}
	if ( ! is_checkout() || ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
		return;
	}

	wp_enqueue_style(
		'bhp-checkout-upsell',
		BHP_BUNDLE_PRICING_URL . 'assets/checkout-upsell.css',
		array( 'bhp-cart-drawer' ),
		BHP_BUNDLE_PRICING_VERSION
	);
	wp_enqueue_script(
		'bhp-checkout-upsell',
		BHP_BUNDLE_PRICING_URL . 'assets/checkout-upsell.js',
		array( 'bhp-cart-drawer' ),
		BHP_BUNDLE_PRICING_VERSION,
		true
	);
	wp_localize_script( 'bhp-checkout-upsell', 'bhpCheckoutUpsell', bhp_bundle_checkout_upsell_copy() );
}
add_action( 'wp_enqueue_scripts', 'bhp_bundle_checkout_upsell_assets', 20 );
