<?php
/**
 * THE HEADER CART CONTROL — the opener for the mini-cart side panel.
 * Theme 1.19.280. Workstream `CYCLE165-LD-PURCHASE-FLOW-ROUND`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE RULING — carrier item 186, READ FIRST-HAND AT SOURCE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, ~05:1x−0600 2026-08-21, verified by the agent that wrote
 * this file at `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-AUTHORIZATION.md`
 * line 816 on the G: mount — NOT relayed:
 *
 *   "or at least have the cart pop up not the cart page show. I honestly dont
 *    like having this cart page in the middle of a purchase. It should just go
 *    to check out or to the cart side panel then to check out (whatever you
 *    and [ads-knowledge] thinks is best)"
 *
 * ⚠ THE SQUARE BRACKETS ABOVE ARE AN EDITORIAL SUBSTITUTION, not his word.
 *   He used that agent's internal call name; internal call names may not
 *   appear in this repository, which is public (Standing Rules §14.5).
 *   Nothing else in the quotation is altered.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS FILE BUILDS NO PANEL. THE PANEL ALREADY EXISTS AND ALREADY WORKS.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FINDING THAT SHAPED THIS BUILD, recorded so nobody re-derives it: the
 *    "mini-cart side panel" is ALREADY SHIPPED. `#bhp-cart-drawer`
 *    (`plugins/brave-hearts-bundle-pricing/includes/bundle-drawer.php` +
 *    `assets/bundle-drawer.js`) is a Store-API-driven slide-out with:
 *
 *      · line items with cover, title and format
 *      · quantity steppers (`data-qty-up` / `data-qty-down`)
 *      · per-line remove (`data-remove`)
 *      · a cross-sell region ("Add This Adventure")
 *      · the Activity Book order bump
 *      · a live price breakdown recomputed from the Store API every render
 *      · the contiguous-US ship note
 *      · ⭐ a "Secure Checkout" button
 *
 *    ⛔ SO NOTHING ABOUT THE PANEL IS REBUILT, RESTYLED OR FORKED HERE.
 *       Building a second one would have been the drift class this codebase
 *       has already paid for twice.
 *
 * ⭐ WHAT WAS ACTUALLY MISSING was the thing he named: A HEADER CART ICON.
 *    Until 1.19.280 the ONLY way to open the panel was the floating action
 *    button, which is `is-visible` only once the cart is non-empty. This file
 *    adds one control, in the header, that opens THAT SAME PANEL through THAT
 *    SAME `openDrawer()` — via the generic `data-bhp-cart-open` hook added to
 *    the drawer script in plugin 1.8.64.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ CACHE SAFETY — WHY NO COUNT IS SERVER-RENDERED HERE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS SITE IS BEHIND SITEGROUND'S PAGE CACHE. A server-rendered cart count
 *    in the SITEWIDE header would be cached with the page and then served to
 *    every other visitor — one shopper's "3" shown to someone with an empty
 *    cart. The floating button can afford a server-rendered count because it
 *    only ever renders where it renders; a header that appears on every
 *    cacheable page cannot.
 *
 * ⭐ SO THE COUNT SHIPS AS ZERO AND HIDDEN, and `updateExtraOpeners()` in
 *    bundle-drawer.js hydrates it from the customer's real Store-API cart on
 *    the same priming fetch the drawer already performs. Worst case before
 *    hydration is a cart button with no number on it — never a wrong number.
 *
 * ⛔ `data-bhp-cart-persist` KEEPS IT IN PLACE AT ZERO. A control that appears
 *    and vanishes as the cart changes reflows the nav; the header is the one
 *    place on this site where that is least acceptable.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It renders no price. It reads no product, SKU, coupon, discount, shipping
 * rate, tax or total. It changes no WooCommerce setting. It adds no second
 * primary CTA — the control is a quiet utility icon, deliberately not a filled
 * button, so `21-PROTECTED-ELEMENTS-MANIFEST.md` §5.3's "one primary per page"
 * property is untouched on every page including `/complete-collection/`.
 * It links to nothing: it is a `<button>`, so with JavaScript unavailable it
 * is inert rather than a dead link to a page we have taken out of the flow.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Can a header cart control do anything useful on this request?
 *
 * ⛔ ALL THREE GATES MATTER:
 *    - WooCommerce absent  → there is no cart to open.
 *    - The drawer markup absent → the opener would be inert. The plugin
 *      suppresses `#bhp-cart-drawer`'s floating opener on checkout for the
 *      same reason, and this matches that rule rather than inventing another.
 *    - `is_checkout()` → the customer is already paying. A cart re-entry
 *      control there is noise at the worst possible moment.
 *
 * @return bool
 */
function bhp_mini_cart_available() {
	if ( ! function_exists( 'WC' ) || ! function_exists( 'bhp_bundle_drawer_markup' ) ) {
		return false;
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return false;
	}
	if ( is_admin() ) {
		return false;
	}
	return true;
}

/**
 * The header cart control.
 *
 * @return string HTML, or '' when the panel cannot be opened on this request.
 */
function bhp_mini_cart_header_control() {
	if ( ! bhp_mini_cart_available() ) {
		return '';
	}

	ob_start();
	?>
	<button type="button"
	        class="bhp-cart-toggle"
	        data-bhp-cart-open
	        data-bhp-cart-source="header"
	        data-bhp-cart-persist
	        data-bhp-cart-empty="true"
	        aria-label="<?php esc_attr_e( 'Open cart', 'brave-hearts' ); ?>">
		<svg class="bhp-cart-toggle__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path fill="currentColor" d="M7 4h-2l-1 2v1h2l3.6 7.59-1.35 2.44c-.16.28-.25.61-.25.97 0 1.1.9 2 2 2h12v-2h-11.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1h-14.98zm12 15c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
		<?php
		/*
		 * ⛔ SHIPS AS "0" AND HIDDEN — see the cache note in this file's header.
		 *    `[data-bhp-cart-count]` is the hook bundle-drawer.js writes into;
		 *    `[data-bhp-cart-empty]` is what the stylesheet hides on.
		 */
		?>
		<span class="bhp-cart-toggle__count" data-bhp-cart-count aria-hidden="true">0</span>
	</button>
	<?php
	return ob_get_clean();
}
