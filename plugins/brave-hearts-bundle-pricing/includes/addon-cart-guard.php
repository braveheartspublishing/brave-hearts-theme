<?php
/**
 * Brave Hearts Bundle Pricing — THE ADD-ON-ONLY CHECKOUT GUARD.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS EXISTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-04, Message 32, verbatim (RELAYED through the
 * Chief of Staff; ⛔ NOT witnessed first-hand by the agent that wrote this
 * file):
 *
 *   "People cannot just buy the $5 activity book- its and upsell with
 *    1,2,3 books or more."
 *
 * The checkbox UI already cannot be reached with an empty cart: it renders
 * on the cart page, the checkout page and the cart drawer, all of which
 * show nothing to offer when there is nothing in the cart
 * (`CYCLE143-CX-20`, observed in browser QA on 2026-08-04).
 *
 * ⭐ THIS GUARD COVERS THE PATH THAT UI CANNOT: tick the add-on with books
 *    in the cart, then remove the books. The cart is then legitimately
 *    add-on-only, every surface is happy, and checkout would have gone
 *    through. It is also the path a direct `add-to-cart=` URL or a Store
 *    API client reaches, neither of which renders any UI at all.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHERE IT IS ENFORCED, AND WHY IT IS NOT ONE HOOK
 * ═══════════════════════════════════════════════════════════════════════
 *
 * This store runs full WooCommerce Blocks. The cart and checkout the
 * customer actually uses are React, driven by the Store API, and a classic
 * `woocommerce_checkout_process` guard alone would enforce NOTHING on the
 * real checkout. Three hooks, deliberately:
 *
 *   1. `woocommerce_store_api_cart_errors` — THE ONE THAT MATTERS.
 *      `CartController::validate_cart()` fires it and throws a 409
 *      `InvalidCartException` if any error was added
 *      (`src/StoreApi/Utilities/CartController.php:496`, read on the
 *      running store, WooCommerce 10.9.1). The Checkout route calls
 *      `validate_cart()` before it will process an order, so this is a
 *      hard server-side stop, not a UI hint.
 *   2. `woocommerce_check_cart_items` — the classic cart and checkout
 *      pages, so the message is visible where a shortcode cart is used.
 *   3. `woocommerce_checkout_process` — the classic checkout POST.
 *
 * ⚠ ONE MESSAGE PER REQUEST. In the classic flow both (2) and (3) fire
 *   inside the same `WC_Checkout::process_checkout()` call
 *   (`includes/class-wc-checkout.php:343`, then the process action), which
 *   would print the same sentence twice. `bhp_bundle_addon_guard_notice()`
 *   holds a request-scoped static so the customer sees it once.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - It does not block ADDING the add-on to the cart. Blocking the add
 *     would break the ordinary "tick it, then change your mind about a
 *     book" flow and would fire on a cart the customer is still building.
 *     The gate is at checkout, where the rule actually applies.
 *   - It does not empty, alter or reorder anybody's cart. It adds an error
 *     and stops. Nothing is silently removed on a customer's behalf.
 *   - It changes no price, discount, shipping tier, coupon, tax or payment
 *     setting, and writes no option, product or order record.
 *   - It contains no customer-facing string. The message lives in
 *     `addon-thankyou-copy.php` with the email copy, so both land in one
 *     file swap.
 *
 * ✅ IT FAILS CLOSED IN THE SAFE DIRECTION. With no resolvable
 *    `BHP-ACTIVITY-BOOK-01` SKU — the state of production until Andrew
 *    approves the live product — `bhp_bundle_addon_product_ids()` is empty,
 *    nothing is ever recognised as the add-on, and this guard can never
 *    fire. It cannot block a books-only checkout under any condition,
 *    because a cart with no add-on item in it returns false at the first
 *    non-add-on line.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-thankyou-copy.php';

/**
 * Is this cart nothing but allowlisted add-ons?
 *
 * True only when the cart is non-empty, contains at least one add-on, and
 * contains NOTHING else. An empty cart is false: WooCommerce has its own
 * empty-cart error and a second, differently-worded one would be noise.
 *
 * @param WC_Cart|object|null $cart Cart, or any object exposing get_cart().
 * @return bool
 */
function bhp_bundle_cart_is_addon_only( $cart ) {
	if ( ! $cart || ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return false;
	}
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return false;
	}

	$items = $cart->get_cart();
	if ( empty( $items ) ) {
		return false;
	}

	$has_addon = false;

	foreach ( $items as $item ) {
		$product_id   = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		$variation_id = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;

		if ( bhp_bundle_is_addon_item( $product_id, $variation_id ) ) {
			$has_addon = true;
			continue;
		}

		// Anything that is not the add-on makes this a normal cart.
		return false;
	}

	return $has_addon;
}

/**
 * The guard message, with the real product title when one resolves.
 *
 * @return string
 */
function bhp_bundle_addon_only_message() {
	$copy = bhp_bundle_addon_thankyou_copy();

	/*
	 * ⭐ INTERPOLATE ONLY IF THE APPROVED STRING ASKS FOR IT.
	 *
	 * ⚠ ADDED IN THE COPY SWAP. The approved guard string NAMES the product
	 *   in prose ("The Adventure Activity Book is a companion download...")
	 *   and carries no `%s`. Calling `sprintf()` on it unconditionally is
	 *   harmless in PHP - a surplus argument is ignored - but it reads as
	 *   though a substitution is happening when none is, and the next
	 *   person to touch this would have to prove that to themselves. The
	 *   `%s` branch is kept intact so a future copy that DOES interpolate
	 *   still works with no code change.
	 */
	if ( false === strpos( $copy['cart_guard']['addon_only'], '%s' ) ) {
		return $copy['cart_guard']['addon_only'];
	}

	$product = function_exists( 'bhp_bundle_addon_product' ) ? bhp_bundle_addon_product() : null;

	if ( $product && $product->get_name() ) {
		return sprintf( $copy['cart_guard']['addon_only'], $product->get_name() );
	}

	return $copy['cart_guard']['addon_only_generic'];
}

/**
 * Add the guard notice once per request.
 *
 * @return bool True if a notice was added by this call.
 */
function bhp_bundle_addon_guard_notice() {
	static $added = false;

	if ( $added ) {
		return false;
	}
	if ( ! function_exists( 'wc_add_notice' ) ) {
		return false;
	}

	$added = true;
	wc_add_notice( bhp_bundle_addon_only_message(), 'error' );

	return true;
}

/**
 * Store API: block checkout on an add-on-only cart.
 *
 * `validate_cart()` throws a 409 as soon as this `WP_Error` has anything in
 * it, so this is the enforcement point for the Blocks checkout the store
 * actually uses.
 *
 * @param WP_Error $errors Errors collected by the Store API.
 * @param WC_Cart  $cart   Cart.
 * @return void
 */
function bhp_bundle_addon_guard_store_api( $errors, $cart ) {
	if ( ! $errors instanceof WP_Error ) {
		return;
	}
	if ( ! bhp_bundle_cart_is_addon_only( $cart ) ) {
		return;
	}

	$errors->add( 'bhp_bundle_addon_only_cart', bhp_bundle_addon_only_message() );
}
add_action( 'woocommerce_store_api_cart_errors', 'bhp_bundle_addon_guard_store_api', 10, 2 );

/**
 * Classic cart and checkout pages: show the message.
 *
 * ⚠ In the Store API path `validate_cart()` throws on the error above
 *   before it ever reaches `woocommerce_check_cart_items`, so this does not
 *   double up there. In the classic path the request-scoped static in
 *   `bhp_bundle_addon_guard_notice()` is what prevents a double.
 *
 * @return void
 */
function bhp_bundle_addon_guard_check_cart_items() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	if ( ! bhp_bundle_cart_is_addon_only( WC()->cart ) ) {
		return;
	}

	bhp_bundle_addon_guard_notice();
}
add_action( 'woocommerce_check_cart_items', 'bhp_bundle_addon_guard_check_cart_items' );

/**
 * Classic checkout POST: refuse to place the order.
 *
 * @return void
 */
function bhp_bundle_addon_guard_checkout_process() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	if ( ! bhp_bundle_cart_is_addon_only( WC()->cart ) ) {
		return;
	}

	bhp_bundle_addon_guard_notice();
}
add_action( 'woocommerce_checkout_process', 'bhp_bundle_addon_guard_checkout_process' );
