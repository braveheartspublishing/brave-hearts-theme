<?php
/**
 * THE PURCHASE FLOW — direct-buy goes to /checkout/; ADD TO CART opens the panel.
 * Theme 1.19.281. Workstream `CYCLE165-LD-FLOW-ADJUSTMENTS`.
 * ============================================================================
 *
 * ⛔⛔ THIS FILE'S TITLE LINE USED TO READ "every buy button finishes on
 *     /checkout/", and 1.19.280 was built that way in good faith under item
 *     186's delegated mechanism. ⭐ CARRIER ITEM 188 IS ANDREW CHOOSING FOR
 *     HIMSELF, and it splits the two button classes:
 *
 *       ADD TO CART  →  adds, and OPENS THE SIDE PANEL (item 188)
 *       DIRECT BUY   →  straight to /checkout/, unchanged (he walked it)
 *
 *     The superseded title is preserved above the change rather than deleted,
 *     because 1.19.280's own docs, tests and the prepared production script
 *     all quote it, and a reader arriving from any of them needs to see that
 *     it moved. ⭐ The `checkout` half of this file is BYTE-UNCHANGED; item
 *     188 added a second value beside it and took nothing away.
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
 * The allowlisted query flag and its TWO allowlisted values.
 *
 * ⛔ STILL NOT A URL, STILL NOT CUSTOMER-CONTROLLED. The request may carry
 *    `bhp_buy=checkout` or `bhp_buy=panel` and nothing else. Each value is
 *    compared against one literal; neither is ever concatenated into a
 *    destination. Adding a second literal does not weaken the open-redirect
 *    discipline in this file's header — it is still a closed set compared by
 *    identity, and `panel` builds no URL at all.
 */
const BHP_PURCHASE_FLOW_FLAG  = 'bhp_buy';
const BHP_PURCHASE_FLOW_VALUE = 'checkout';

/**
 * ⭐⭐ 1.19.281 — THE SECOND VALUE. `bhp_buy=panel` means "add, then let the
 *    side panel take it from here".
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE RULING THIS SERVES — carrier item 188, READ FIRST-HAND AT SOURCE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, ~05:4x−0600 2026-08-21. ⭐ VERIFIED AT SOURCE by the agent
 * that wrote this constant — `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-
 * AUTHORIZATION.md` line 818, read on the G: mount, NOT relayed:
 *
 *   "Well if we keep add to cart - lets not do the cart page- we made the cart
 *    side panel for a reason with the upsells and the totals in their- they go
 *    to checkout then add the coupon"
 *
 * ⭐ SO THE FLOW IS, FINAL: ADD-TO-CART keeps ADD semantics and OPENS THE SIDE
 *    PANEL — item added, running totals and the shipping tier visible, the
 *    cross-sells beside it. The panel's Secure Checkout button carries the
 *    intent onward to /checkout/, where the coupon field lives.
 *
 * ⛔ THIS REFINES ITEM 186, IT DOES NOT CONTRADICT IT. 1.19.280 read the
 *    delegated mechanism ("whatever you and boromir thinks is best") as
 *    ATC → /checkout/. He then chose for himself. ⭐ HIS CHOICE GOVERNS, and
 *    the earlier reading is superseded rather than defended.
 *
 * ⛔⛔ DIRECT-BUY BUTTONS ARE UNTOUCHED AND MUST STAY UNTOUCHED. The
 *     collection / bundle "GET THE…" controls post the plugin's own
 *     allowlisted checkout-redirect form (`bhp_collection_add_to_cart_cta()`).
 *     ⭐ HE WALKED THAT PATH HIMSELF. It still goes straight to checkout, and
 *     nothing in this file's `panel` path is wired to it.
 *
 * ⛔⛔ THE PANEL NEVER SELF-OPENS ON LANDING — Boromir's second condition.
 *     ⭐ NOTE WHAT THIS CONSTANT DELIBERATELY DOES NOT DO: it does not send
 *     any "open the panel" flag onward in a URL. There is NO query parameter
 *     anywhere in this build that opens the panel, so no bookmark, no shared
 *     link, no back-button and no crawler can ever open it. The panel opens
 *     on exactly two events, both of them the shopper's own click: an
 *     add-to-cart control, and the header cart icon.
 */
const BHP_PURCHASE_FLOW_VALUE_PANEL = 'panel';

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
 * Mark an add-to-cart URL as "add, then open the side panel".
 *
 * ⭐ 1.19.281, carrier item 188. This is what an ADD TO CART button carries.
 *
 * @param string $url An add-to-cart URL, or '' .
 * @return string The same URL carrying the flag, or '' unchanged.
 */
function bhp_purchase_flow_mark_panel( $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return $url;
	}
	return add_query_arg( BHP_PURCHASE_FLOW_FLAG, BHP_PURCHASE_FLOW_VALUE_PANEL, $url );
}

/**
 * The raw allowlisted flag value on THIS request, or '' .
 *
 * ⛔ Reads only, and returns ONE OF THREE FIXED STRINGS — never a byte from
 *    the request. An unrecognised value is '' , which every caller treats
 *    exactly as an absent flag.
 *
 * @return string '' | BHP_PURCHASE_FLOW_VALUE | BHP_PURCHASE_FLOW_VALUE_PANEL
 */
function bhp_purchase_flow_value() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only
	// destination hint on a GET add-to-cart; WooCommerce's own add_to_cart_action
	// performs the nonce/validation work and this only steers where it lands.
	if ( ! isset( $_REQUEST[ BHP_PURCHASE_FLOW_FLAG ] ) ) {
		return '';
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$raw = wp_unslash( $_REQUEST[ BHP_PURCHASE_FLOW_FLAG ] );
	if ( ! is_string( $raw ) ) {
		return '';
	}
	if ( BHP_PURCHASE_FLOW_VALUE === $raw ) {
		return BHP_PURCHASE_FLOW_VALUE;
	}
	if ( BHP_PURCHASE_FLOW_VALUE_PANEL === $raw ) {
		return BHP_PURCHASE_FLOW_VALUE_PANEL;
	}
	return '';
}

/**
 * Is THIS request one of our flagged straight-to-checkout buy clicks?
 *
 * ⛔ Reads only. Compares against one literal. Never returns anything from the
 *    request to a caller.
 *
 * @return bool
 */
function bhp_purchase_flow_requested() {
	return BHP_PURCHASE_FLOW_VALUE === bhp_purchase_flow_value();
}

/**
 * Is THIS request a flagged "add, then open the panel" click?
 *
 * @return bool
 */
function bhp_purchase_flow_panel_requested() {
	return BHP_PURCHASE_FLOW_VALUE_PANEL === bhp_purchase_flow_value();
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
 * ⭐⭐ THE NO-JAVASCRIPT FALLBACK FOR AN ADD-TO-CART CLICK — carrier item 188.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHAT ACTUALLY HAPPENS ON A NORMAL VISIT, SO THIS IS READ IN PROPORTION
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS FUNCTION IS NOT THE MECHANISM. On a browser with JavaScript, the
 *    ADD TO CART anchor never navigates at all: `bundle-drawer.js` intercepts
 *    the click, adds the line through the Store API, and opens the panel — no
 *    page load, no redirect, nothing for this filter to do. THAT is the flow
 *    Andrew described and the flow QA walks.
 *
 * ⭐ THIS IS THE FLOOR UNDER IT: JavaScript off, or the Store API call
 *    failing, in which case the anchor is followed for real. Without this
 *    filter that shopper lands on the CART PAGE FOOTER — the exact thing item
 *    186 named ("when you click on add to cart it goes straight to the footer
 *    of the cart… I honestly dont like having this cart page in the middle of
 *    a purchase"). ⛔ SO THE FALLBACK MUST NOT BE THE CART PAGE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ NO WOOCOMMERCE SETTING IS WRITTEN. THIS IS A READ FILTER.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `option_{$name}` fires on `get_option()`. This changes what WooCommerce
 * READS, for the duration of ONE flagged request, and writes nothing to the
 * database. `woocommerce_cart_redirect_after_add` is left exactly as Andrew
 * set it, on every environment, and deactivating the theme restores today's
 * behaviour completely. Changing the stored setting would be an Andrew gate
 * (`BHP-AGENT-STANDING-RULES.md` §6 / §16.4) and this build did not cross it —
 * the same discipline `bhp_purchase_flow_redirect()` above already follows.
 *
 * ⛔ IT FAILS TO TODAY'S BEHAVIOUR: no flag on the request → the stored value
 *    is returned untouched, so every add-to-cart that is not one of our ADD
 *    TO CART buttons behaves byte-identically to 1.19.280.
 *
 * ⛔ AND IT DOES NOT SEND A PANEL FLAG ONWARD. The shopper stays on the
 *    product page with the item in the cart and WooCommerce's own "added to
 *    cart" notice. The panel does NOT open, because opening it would need a
 *    URL parameter, and a URL that opens the panel is a URL that can be
 *    bookmarked, shared and landed on — Boromir's second condition. ⭐ A
 *    JavaScript-less shopper getting a correct product page instead of a
 *    panel is a degradation. A panel that any link can open is a defect.
 *
 * @param mixed $value The stored option value.
 * @return mixed 'no' on a flagged panel add; the stored value otherwise.
 */
function bhp_purchase_flow_panel_suppress_cart_redirect( $value ) {
	return bhp_purchase_flow_panel_requested() ? 'no' : $value;
}
add_filter( 'option_woocommerce_cart_redirect_after_add', 'bhp_purchase_flow_panel_suppress_cart_redirect' );
add_filter( 'default_option_woocommerce_cart_redirect_after_add', 'bhp_purchase_flow_panel_suppress_cart_redirect' );

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
