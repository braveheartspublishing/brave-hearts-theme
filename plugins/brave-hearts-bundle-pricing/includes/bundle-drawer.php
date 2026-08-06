<?php
/**
 * Brave Hearts Bundle Pricing — slide-out cart drawer.
 *
 * The drawer is rendered and driven entirely by JavaScript against the
 * WooCommerce Store API (the same API this site's own Blocks-based cart
 * and checkout already use), so line items, prices, and the Bundle
 * Savings fee always match what checkout will actually charge — this file
 * never invents a second copy of cart totals.
 *
 * Bundle-progress messaging and the cross-sell suggestion are computed
 * client-side (assets/bundle-drawer.js) directly from the Store API's own
 * cart response, rather than a second custom REST call — a separate
 * WordPress REST route does not automatically share the customer's
 * WooCommerce session the way the Store API's own routes do, which made a
 * second server round-trip fragile. The exact copy text and the six-title
 * catalog are still defined once, here, and localized to JS so there is
 * only one place either ever needs to be edited.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', 'bhp_bundle_drawer_markup' );
function bhp_bundle_drawer_markup() {
	if ( is_admin() || ! function_exists( 'WC' ) ) {
		return;
	}
	?>
	<div id="bhp-cart-drawer" class="bhp-cart-drawer" aria-hidden="true">
		<div class="bhp-cart-drawer__overlay" data-bhp-drawer-close></div>
		<div class="bhp-cart-drawer__panel" role="dialog" aria-modal="true" aria-label="Shopping cart">
			<div class="bhp-cart-drawer__header">
				<h2>Your Cart</h2>
				<button type="button" class="bhp-cart-drawer__close" data-bhp-drawer-close aria-label="Close cart">&times;</button>
			</div>
			<div class="bhp-cart-drawer__body">
				<?php
				/*
				 * F4 (2026-08-03). Andrew, phone walk, verbatim: "put it on top
				 * of the inventory so they see it first."
				 *
				 * The "Add This Adventure" cross-sell used to render BELOW the
				 * line items and below the progress message, which on a phone
				 * put the highest-margin element in the drawer below the fold
				 * of a three-item cart. It is now the first thing in the body.
				 *
				 * ONLY THE DOM ORDER CHANGED. bundle-drawer.js selects all three
				 * regions by class name and appends into them, so no script,
				 * no cross-sell logic, no analytics event and no pricing rule is
				 * touched by this move -- and if the cross-sell has nothing to
				 * offer, the container renders empty exactly as before.
				 */
				?>
				<div class="bhp-cart-drawer__crosssell"></div>
				<div class="bhp-cart-drawer__items"></div>
				<div class="bhp-cart-drawer__message" aria-live="polite"></div>
			</div>
			<div class="bhp-cart-drawer__footer">
				<?php
				/*
				 * ⭐ 1.8.26 / `CYCLE144-LD-120` — THE FOOTER IS NOW TWO REGIONS,
				 *    AND THAT IS THE ONLY CHANGE MADE TO THIS FILE.
				 *
				 *    Andrew Signore, 2026-08-05, current-turn, with a screenshot:
				 *    "you cant really see whats in your cart... you can barely
				 *    see whats going on". Measured on staging before the fix: at
				 *    390 x 844 this footer was 495.09px of an 844px panel — 59%
				 *    — and it could not shrink, so the line-items region was
				 *    278.11px and showed ONE of four items. The reasoning in the
				 *    comment below is preserved verbatim and is still correct
				 *    about WHERE the add-on belongs; what it did not anticipate
				 *    is that "sticky footer" plus a growing footer means the
				 *    cart contents pay for every pixel the footer gains.
				 *
				 *    `__footer-scroll` may scroll when the viewport is short;
				 *    `__footer-actions` never does. The add-on is deliberately
				 *    the FIRST child of the scrolling region and the price
				 *    breakdown the second, so a short viewport clips the two
				 *    trailing hints and never the order bump or the total.
				 *
				 * ⛔ NOTHING ELSE MOVED. Same six nodes, same DOM order, same
				 *    classes, same copy, same `aria-live`, same checkout URL.
				 *    Every selector in `bundle-drawer.js` and `addon-upsell.js`
				 *    is a DESCENDANT query rooted at the drawer
				 *    (`qs('.bhp-cart-drawer__summary', drawer)` and friends), and
				 *    `assets/addon-upsell.css` and the theme's `style.css` reach
				 *    these nodes by class only — verified by grep across both
				 *    the plugin and the theme, no child combinator anywhere —
				 *    so one extra wrapper level is inert to all of them.
				 */
				?>
				<div class="bhp-cart-drawer__footer-scroll">
				<?php
				/*
				 * The ACTIVITY BOOK checkbox order bump — surface 3 of 3
				 * (Andrew Signore, 2026-08-04, relayed: cart page, all
				 * checkout pages AND the cart drawer, permanent and
				 * universal, regardless of cart contents).
				 *
				 * ⭐ IN THE FOOTER, DIRECTLY ABOVE THE SUMMARY, ON PURPOSE.
				 *    The drawer footer is sticky, so the checkbox is
				 *    visible without scrolling on a phone even with a
				 *    three-item cart — which is what "permanent" has to
				 *    mean on the surface where the cross-sell above it was
				 *    already moved to the top for exactly that reason (F4).
				 *    It also sits next to the money, which is where an
				 *    order bump belongs and where its price can be read
				 *    against the total.
				 *
				 * ⛔ THE CONTAINER IS EMPTY HERE AND STAYS THAT WAY IF THE
				 *    PRODUCT DOES NOT EXIST. `addon-upsell.js` is only
				 *    enqueued when the SKU resolves to a real purchasable
				 *    product, so on an environment without it (production,
				 *    until Andrew approves the live product) this renders
				 *    as one empty div with no height and no styling.
				 *
				 * ⛔ bundle-drawer.js NEVER WIPES THIS NODE. It wipes
				 *    `__items`, `__message` and `__crosssell` on every
				 *    render; this one is populated once and then only
				 *    re-synced, so a customer cannot lose focus or an
				 *    in-flight click to a cart refresh.
				 */
				?>
				<div class="bhp-cart-drawer__addon"></div>
				<div class="bhp-cart-drawer__summary" aria-live="polite"></div>
				<p class="bhp-cart-drawer__coupon-hint">Have a coupon? You can add it at checkout.</p>
				<?php
				/*
				 * A5 / D3 (2026-08-03) — the contiguous-US disclosure, stated
				 * BEFORE the customer commits to a checkout they may not be
				 * able to complete. Andrew-approved wording (relayed through
				 * `chief-of-staff`, not witnessed here); source deck
				 * `WORKING-DRAFTS\commerce-cx\
				 * DRAFT-2026-08-03-HC-COPY-AND-SHIPPING-MESSAGES.md` §3.1 A1.
				 *
				 * SERVER-RENDERED AND STATIC. Zero JS, zero Store API calls,
				 * no condition to evaluate. The AK/HI message deliberately
				 * does NOT go here: the drawer has no address, so the
				 * condition cannot be tested, and a permanent AK/HI warning
				 * would alarm every customer it does not apply to.
				 */
				?>
				<p class="bhp-cart-drawer__ship-note">We currently ship within the contiguous United States.</p>
				</div>
				<div class="bhp-cart-drawer__footer-actions">
					<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button bhp-cart-drawer__checkout">Secure Checkout</a>
					<button type="button" class="bhp-cart-drawer__continue" data-bhp-drawer-close>Continue Shopping</button>
				</div>
			</div>
		</div>
	</div>
	<?php
	// Hidden on checkout (is_checkout() also covers the order-received
	// endpoint) -- a cart re-entry button has no purpose once the
	// customer is already paying or has already paid.
	if ( ! is_checkout() ) {
		bhp_bundle_floating_cart_markup();
	}
}

/**
 * Persistent floating button that reopens the existing drawer. The
 * initial visibility/count/label are read directly from the real
 * WooCommerce session cart (get_cart_contents_count() sums quantities,
 * matching the "total units, not line count" requirement) so a
 * returning customer sees the correct state on first paint, before
 * bundle-drawer.js's own priming fetch even completes. JS takes over
 * keeping it in sync after that -- this server-rendered value is never
 * read again once the page's own JS has run.
 */
function bhp_bundle_floating_cart_markup() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$label = $count > 0 ? ( 'Open cart, ' . $count . ' item' . ( 1 === $count ? '' : 's' ) ) : 'Open cart';
	?>
	<button type="button" id="bhp-floating-cart" class="bhp-floating-cart<?php echo $count > 0 ? ' is-visible' : ''; ?>" aria-label="<?php echo esc_attr( $label ); ?>">
		<svg class="bhp-floating-cart__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path fill="currentColor" d="M7 4h-2l-1 2v1h2l3.6 7.59-1.35 2.44c-.16.28-.25.61-.25.97 0 1.1.9 2 2 2h12v-2h-11.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1h-14.98zm12 15c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
		<span class="bhp-floating-cart__badge" aria-hidden="true"><?php echo (int) $count; ?></span>
	</button>
	<?php
}

add_action( 'wp_enqueue_scripts', 'bhp_bundle_drawer_assets' );
function bhp_bundle_drawer_assets() {
	if ( is_admin() || ! function_exists( 'WC' ) ) {
		return;
	}
	wp_enqueue_style( 'bhp-cart-drawer', BHP_BUNDLE_PRICING_URL . 'assets/bundle-drawer.css', array(), BHP_BUNDLE_PRICING_VERSION );
	wp_enqueue_script( 'bhp-cart-drawer', BHP_BUNDLE_PRICING_URL . 'assets/bundle-drawer.js', array( 'jquery' ), BHP_BUNDLE_PRICING_VERSION, true );
	// Analytics Phase 1B: select_item tracking, matches clicks against the
	// window.bhpTrackedLists registry printed by bundle-analytics.php.
	wp_enqueue_script( 'bhp-list-tracking', BHP_BUNDLE_PRICING_URL . 'assets/bhp-list-tracking.js', array(), BHP_BUNDLE_PRICING_VERSION, true );

	// Analytics Phase 1B correction pass: add_shipping_info/add_payment_info,
	// checkout-page only -- depends on window.bhpDrawerData.catalog (from
	// the localize_script call below, already enqueued on every page).
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
		wp_enqueue_script( 'bhp-checkout-events', BHP_BUNDLE_PRICING_URL . 'assets/bhp-checkout-events.js', array( 'bhp-cart-drawer' ), BHP_BUNDLE_PRICING_VERSION, true );
	}
	wp_localize_script(
		'bhp-cart-drawer',
		'bhpDrawerData',
		array(
			'cartUrl'     => esc_url( wc_get_cart_url() ),
			'checkoutUrl' => esc_url( wc_get_checkout_url() ),
			'catalog'     => bhp_bundle_catalog(),
			/*
			 * B4 (2026-08-03). Andrew, walk-3, verbatim: "Add this adventure -
			 * save X amount of money".
			 *
			 * ⛔ NOTHING IS HARDCODED HERE. `bhp_bundle_rules()` is the SAME
			 *    function `bhp_bundle_apply_discount_fees()` reads to create
			 *    the real cart fee, so the number on the button and the number
			 *    on the invoice cannot drift: change the table and both move
			 *    together. The drawer computes the DELTA (what adding this one
			 *    title actually gains) rather than quoting a tier total, which
			 *    would overstate the saving for a customer who already has two.
			 */
			'bundleRules' => array(
				'paperback' => bhp_bundle_rules( 'paperback' ),
				'hardcover' => bhp_bundle_rules( 'hardcover' ),
			),
			'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
			/*
			 * R4 (2026-08-03): the cross-sell box's gold eyebrow.
			 *
			 * ⛔ ONE STRING, ONE SOURCE. It is read from the checkout module's
			 *    own copy function rather than restated here, so the drawer and
			 *    the checkout panel cannot say two different things. Andrew
			 *    approved that module for production; matching it is the point.
			 *
			 * `function_exists` is not defensive padding: `checkout-upsell.php`
			 * is required AFTER this file in the bootstrap. It is loaded long
			 * before `wp_enqueue_scripts` fires, so the guard never trips in
			 * practice — but if the module is ever removed, the drawer must
			 * degrade to its pre-R4 markup rather than fatal.
			 */
			'crossSellHeading' => function_exists( 'bhp_bundle_checkout_upsell_copy' )
				? bhp_bundle_checkout_upsell_copy()['heading']
				: '',
			// Copy verbatim from the approved UX addendum. Keyed the same
			// way bundle-drawer.js counts distinct titles (1, 2, 3) so the
			// script only has to look up a string, never compose one.
			'progressCopy' => array(
				'paperback' => array(
					1 => 'Add another paperback and save $1.99.',
					2 => 'Add the final adventure to complete the collection and save $3.98 total.',
					3 => 'Best Value - Complete Paperback Collection',
				),
				'hardcover' => array(
					1 => 'Add another hardcover and save $2.99.',
					2 => 'Complete the hardcover collection and save $4.98 total.',
					3 => 'Best Value - Complete Hardcover Collection',
				),
			),
			'savedCopy' => array(
				'paperback' => 'You saved $1.99 with your 2-book paperback set.',
				'hardcover' => 'You saved $2.99 with your 2-book hardcover set.',
			),
			/*
			 * ⭐ 1.8.23 — the free-shipping nudge, localized from the SAME
			 *    function the cart and checkout pages read
			 *    (`bhp_bundle_freeship_copy()`), so the drawer and the cart
			 *    cannot end up saying two different things about one rule.
			 *    A filter that changes the wording changes all three
			 *    surfaces at once.
			 *
			 * ⛔ The drawer applies the same suppression the PHP side does:
			 *    bundle-drawer.js only renders these when the cart contains
			 *    nothing outside the six editions and the allowlisted
			 *    add-on, because an unrelated product stops the shipping
			 *    override from running at all.
			 */
			'freeShipCopy' => bhp_bundle_freeship_copy(),
			/*
			 * ⭐ 1.8.23 — how a ZERO shipping figure is written in the
			 *    drawer's own summary. Read from
			 *    `bhp_bundle_shipping_display()` rather than hardcoded in
			 *    JS, so "FREE shipping" has exactly one author.
			 */
			'freeShipLabel' => bhp_bundle_shipping_display( 0.00, 'row' ),
			/*
			 * ⭐ 1.8.23 — the allowlisted add-on product IDs, so the drawer
			 *    can reproduce `bhp_bundle_cart_has_unrelated_items()`
			 *    EXACTLY rather than approximately.
			 *
			 * ⛔ WITHOUT THIS THE DRAWER WOULD GET THE ADD-ON BACKWARDS. Its
			 *    only test for "is this item ours" is the six-title catalog,
			 *    so a $5 activity book would read as an unrelated product and
			 *    would suppress a free-shipping message that the server is
			 *    correctly still applying. The add-on must never affect
			 *    qualification, on any surface.
			 *
			 * ✅ FAILS CLOSED AND EMPTY. `bhp_bundle_addon_product_ids()`
			 *    returns an empty array wherever the SKU resolves to nothing
			 *    (production, today), and an empty list simply means every
			 *    non-catalog item counts as unrelated — the pre-1.8.23
			 *    behaviour.
			 */
			'addonProductIds' => array_map( 'intval', bhp_bundle_addon_product_ids() ),
		)
	);
}
