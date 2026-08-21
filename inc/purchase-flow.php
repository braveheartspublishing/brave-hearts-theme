<?php
/**
 * THE PURCHASE FLOW — every buy button finishes on /checkout/.
 * Theme 1.19.280. Workstream `CYCLE165-LD-PURCHASE-FLOW-ROUND`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE RULING THIS FILE SERVES — carrier item 186, READ FIRST-HAND
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, ~05:1x−0600 2026-08-21. ⭐ VERIFIED AT SOURCE by the agent
 * that wrote this file — `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-
 * AUTHORIZATION.md` line 816, read on the G: mount, NOT relayed:
 *
 *   "when you click on add to cart it goes straight to the footer of the cart
 *    - it should go straight to check out honestly. We want it easy to buy
 *    books. Not extra steps. or at least have the cart pop up not the cart
 *    page show. I honestly dont like having this cart page in the middle of a
 *    purchase. It should just go to check out or to the cart side panel then
 *    to check out (whatever you and boromir thinks is best)"
 *
 * ⭐ THE MECHANISM WAS DELEGATED — "whatever you and boromir thinks is best" —
 *    and Gandalf's call (Boromir validating in parallel) is: BUY BUTTONS GO
 *    STRAIGHT TO CHECKOUT, using the mechanism the collection card already
 *    uses; a MINI-CART SIDE PANEL serves the multi-item shopper; the CART PAGE
 *    leaves the purchase flow while its URL keeps working.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS FILE INVENTS NO COMMERCE MECHANISM AND CHANGES NO SETTING
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ NO WOOCOMMERCE OPTION IS TOUCHED ON ANY ENVIRONMENT. In particular
 *    `woocommerce_cart_redirect_after_add` — the option that produces today's
 *    "straight to the footer of the cart" — is READ BY NOBODY HERE AND
 *    WRITTEN BY NOBODY HERE. Changing a WooCommerce setting is an Andrew gate
 *    (`BHP-AGENT-STANDING-RULES.md` §6) and this build did not cross it. The
 *    redirect is steered per-request by a filter instead, which is reversible
 *    by deactivating the theme and leaves the store's own configuration
 *    exactly as Andrew left it.
 *
 * ⛔ NO PRICE, DISCOUNT, SHIPPING COST, TAX, STOCK, COUPON, PRODUCT RECORD,
 *    SKU, VARIATION OR BOOKVAULT MAPPING IS READ OR WRITTEN. This file only
 *    changes WHERE THE CUSTOMER LANDS after a successful add.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE OPEN-REDIRECT DISCIPLINE, COPIED DELIBERATELY FROM THE PLUGIN
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `inc/collection-cta.php` records the rule for the plugin's own flag and it
 * applies here word for word:
 *
 *   "THE REDIRECT VALUE IS NOT A URL AND IS NOT CUSTOMER-CONTROLLED. The
 *    plugin compares the posted value against ONE literal and then builds the
 *    destination from WooCommerce's own `wc_get_checkout_url()`. Nothing from
 *    the request ever reaches `wp_safe_redirect()`."
 *
 * ⛔ SO: the request may carry `bhp_buy=checkout` and NOTHING ELSE. The value
 *    is compared against one literal; the destination is built from
 *    `wc_get_checkout_url()`; no byte of the request is ever concatenated into
 *    it. On a page that takes payment an open redirect is a real
 *    vulnerability, so this is the design, not a nicety. ⛔ DO NOT
 *    "generalise" this to accept a destination.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ IT FAILS TO TODAY'S BEHAVIOUR, NEVER TO A DEAD END
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * - No flag on the request → the filter returns `$url` untouched, so every
 *   add-to-cart that is not one of our buy buttons behaves byte-identically
 *   to 1.19.279.
 * - An error notice present (out of stock, a variation that will not resolve)
 *   → the filter stands down and WooCommerce keeps the customer where the
 *   error can be read. ⛔ Sending a failed add to /checkout/ would show an
 *   empty cart and read as a broken store.
 * - `wc_get_checkout_url()` unavailable (plugin off) → `$url` untouched.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE SCHOOL-VISIT FLAGGED PATH SURVIVES THIS, BY CONSTRUCTION
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `?bhp_visit=<slug>` is not carried in the URL between pages — it is adopted
 * once into the WOOCOMMERCE SESSION (`WC()->session->set(
 * BHP_SCHOOL_VISIT_SESSION_KEY, … )` in the plugin's `school-visit-pickup.php`)
 * and read from there on every subsequent request. A redirect to
 * `wc_get_checkout_url()` is an ordinary same-session request, so the flag,
 * the hand-delivery framing and the pickup selection are all still in force at
 * checkout. ⭐ ASSERTED IN QA AT AN ASSERTED VIEWPORT, not inferred from this
 * paragraph — `FD-505`/`FD-506` keeps the flagged path in the bar.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The one allowlisted query flag. One literal, compared, never interpolated.
 */
const BHP_PURCHASE_FLOW_FLAG  = 'bhp_buy';
const BHP_PURCHASE_FLOW_VALUE = 'checkout';

/**
 * Mark an add-to-cart URL as "finish on /checkout/".
 *
 * @param string $url An add-to-cart URL, or '' .
 * @return string The same URL carrying the flag, or '' unchanged.
 */
function bhp_purchase_flow_mark( $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return $url;
	}
	return add_query_arg( BHP_PURCHASE_FLOW_FLAG, BHP_PURCHASE_FLOW_VALUE, $url );
}

/**
 * Is THIS request one of our flagged buy clicks?
 *
 * ⛔ Reads only. Compares against one literal. Never returns anything from the
 *    request to a caller.
 *
 * @return bool
 */
function bhp_purchase_flow_requested() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only
	// destination hint on a GET add-to-cart; WooCommerce's own add_to_cart_action
	// performs the nonce/validation work and this only steers where it lands.
	if ( ! isset( $_REQUEST[ BHP_PURCHASE_FLOW_FLAG ] ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw = wp_unslash( $_REQUEST[ BHP_PURCHASE_FLOW_FLAG ] );
	return is_string( $raw ) && BHP_PURCHASE_FLOW_VALUE === $raw;
}

/**
 * Send a successful, flagged add straight to /checkout/.
 *
 * @param string $url WooCommerce's chosen destination.
 * @return string
 */
function bhp_purchase_flow_redirect( $url ) {
	if ( ! bhp_purchase_flow_requested() ) {
		return $url;
	}
	/*
	 * ⛔ A FAILED ADD IS NEVER SENT TO CHECKOUT. `wc_add_notice`-class errors
	 *    (out of stock, unresolvable variation, a cart guard refusing the
	 *    item) must stay on a page where the customer can read them; checkout
	 *    would show an empty cart and read as a broken store.
	 */
	if ( function_exists( 'wc_notice_count' ) && wc_notice_count( 'error' ) > 0 ) {
		return $url;
	}
	if ( ! function_exists( 'wc_get_checkout_url' ) ) {
		return $url;
	}
	/* ⛔ Built from WooCommerce's own helper. Nothing from the request. */
	return wc_get_checkout_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'bhp_purchase_flow_redirect', 20 );

/**
 * Should a cart-page LINK be offered anywhere in the purchase flow?
 *
 * ⭐ HIS LIMB: "I honestly dont like having this cart page in the middle of a
 *    purchase." The cart PAGE is not deleted, not redirected and not
 *    unpublished — ⛔ nothing 404s and a direct hit, a bookmark, an old email
 *    link and WooCommerce's own empty-cart redirect from /checkout/ all keep
 *    working exactly as before. What changes is that NO CONTROL WE RENDER
 *    POINTS AT IT. The side panel is the cart surface now.
 *
 * @return bool Always false today; a filter so the decision has one home.
 */
function bhp_purchase_flow_cart_page_in_flow() {
	/**
	 * @param bool $in_flow FALSE = no rendered control routes to /cart/.
	 */
	return (bool) apply_filters( 'bhp_purchase_flow_cart_page_in_flow', false );
}
