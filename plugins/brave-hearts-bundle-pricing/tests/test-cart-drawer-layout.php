<?php
/**
 * Brave Hearts Bundle Pricing — cart-drawer LAYOUT regression suite (1.8.26).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cart-drawer-layout.php --user=1
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * Andrew Signore, 2026-08-05, current-turn defect report with a screenshot,
 * on /complete-collection/ at desktop AND phone widths, verbatim:
 *     "you cant really see whats in your cart... you can barely see whats
 *      going on"
 *
 * Measured on staging (plugin 1.8.25, theme 1.19.181) in headless Chrome
 * over CDP, real Store API cart of 3 paperbacks + the $5 activity book:
 *
 *   viewport      panel   header   BODY     footer   items fully visible
 *   1440 x 900    900     100.19   304.72   495.09   1 of 4
 *    390 x 844    844      70.80   278.11   495.09   1 of 4
 *
 * Root cause: the panel is a flex column and the FOOTER was `flex: 0 0 auto`
 * (unshrinkable) while the BODY was `flex: 1 1 auto` with an automatic
 * minimum size of 0 (because `overflow-y: auto`). Every pixel the footer
 * gained came out of the cart contents. The footer gained 78.30px in 1.8.20
 * (activity-book order bump) and its summary grew to 194.59px in 1.8.23/24.
 *
 * WHAT THIS SUITE IS AND IS NOT
 * -----------------------------
 * ⛔ This is a STRUCTURAL suite. It cannot measure a rendered pixel — PHP has
 *    no layout engine. The pixel measurements above and after the fix were
 *    taken in a real browser and are recorded in the release notes, NOT
 *    asserted here. What this suite does is make the layout CONTRACT that
 *    produces those pixels impossible to delete silently: the floor, the
 *    shrinkable footer, the pinned action bar, and the DOM order that
 *    decides what clips first on a short viewport.
 *
 * It touches no cart, no product, no order and no WooCommerce setting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_drawer_layout_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$plugin_dir = defined( 'BHP_BUNDLE_PRICING_DIR' )
	? BHP_BUNDLE_PRICING_DIR
	: dirname( __DIR__ ) . '/';

$css_path = $plugin_dir . 'assets/bundle-drawer.css';
$php_path = $plugin_dir . 'includes/bundle-drawer.php';

bhp_drawer_layout_assert( file_exists( $css_path ), 'assets/bundle-drawer.css exists', $failures );
bhp_drawer_layout_assert( file_exists( $php_path ), 'includes/bundle-drawer.php exists', $failures );

if ( ! file_exists( $css_path ) || ! file_exists( $php_path ) ) {
	echo "\nABORTED: drawer source files not found.\n";
	exit( 1 );
}

$css = file_get_contents( $css_path );
$php = file_get_contents( $php_path );

/**
 * Extracts the declaration block of a single top-level rule whose selector
 * matches exactly. Returns '' when the rule is absent, which every caller
 * treats as a failure rather than a skip.
 */
function bhp_drawer_rule( $css, $selector ) {
	$quoted = preg_quote( $selector, '/' );
	if ( ! preg_match( '/(^|\})\s*' . $quoted . '\s*\{([^}]*)\}/m', $css, $m ) ) {
		return '';
	}
	return $m[2];
}

// ==================== 1. THE FLOOR — the fix itself ====================

$body_rule = bhp_drawer_rule( $css, '.bhp-cart-drawer__body' );

bhp_drawer_layout_assert(
	'' !== $body_rule,
	'1a. `.bhp-cart-drawer__body` still has its own top-level rule',
	$failures
);

// The floor. Without this the body shrinks to zero and the defect returns.
bhp_drawer_layout_assert(
	(bool) preg_match( '/min-height\s*:\s*min\(\s*330px\s*,\s*45vh\s*\)/', $body_rule ),
	'1b. THE FLOOR: the items region declares `min-height: min(330px, 45vh)` — 20px body padding + a 163.52px first row + a 139.38px second row = 322.90px, so two full item rows can never be squeezed out',
	$failures
);

// flex-basis MUST be 0, not auto. With `auto` the body contributes its full
// content height to the flex shrink calculation and the footer gets squeezed
// on tall viewports too, which would clip the Estimated total.
bhp_drawer_layout_assert(
	(bool) preg_match( '/flex\s*:\s*1\s+1\s+0%?\s*;/', $body_rule ),
	'1c. The items region is `flex: 1 1 0%` — a zero basis, so the footer shrinks by exactly the amount the floor demands and not one pixel more',
	$failures
);

bhp_drawer_layout_assert(
	(bool) preg_match( '/overflow-y\s*:\s*auto/', $body_rule ),
	'1d. The items region still scrolls internally past the floor',
	$failures
);

// The cross-sell renders ABOVE the items (F4, Andrew-ruled) and consumes
// 152.53px + 16px of separation at the top of the same scroll region. With
// only the 330px floor a one-book cart at 390x844 measured 341.09px of body
// against 352.05px of need and showed ZERO full rows. Found by measuring the
// first pass on staging, not by review.
$cross_floor_rule = bhp_drawer_rule( $css, '.bhp-cart-drawer__body:has(.bhp-cart-drawer__crosssell:not(:empty))' );
bhp_drawer_layout_assert(
	'' !== $cross_floor_rule
		&& preg_match( '/min-height\s*:\s*min\(\s*360px\s*,\s*48vh\s*\)/', $cross_floor_rule ),
	'1e. THE CROSS-SELL FLOOR: when the cross-sell box is rendered the items region floor rises to `min(360px, 48vh)`, so the first line item is still fully visible beneath it',
	$failures
);

// ==================== 2. THE FOOTER MUST BE ABLE TO SHRINK ====================

$footer_rule = bhp_drawer_rule( $css, '.bhp-cart-drawer__footer' );

bhp_drawer_layout_assert(
	'' !== $footer_rule,
	'2a. `.bhp-cart-drawer__footer` still has its own top-level rule',
	$failures
);

// THE REGRESSION GUARD. `flex: 0 0 auto` on this element is the 1.8.25 bug.
bhp_drawer_layout_assert(
	! preg_match( '/flex\s*:\s*0\s+0\s+/', $footer_rule ),
	'2b. REGRESSION GUARD: the footer is NOT `flex: 0 0 auto` — that exact value is what made the footer unshrinkable and starved the cart contents in 1.8.25',
	$failures
);

bhp_drawer_layout_assert(
	(bool) preg_match( '/flex\s*:\s*0\s+1\s+auto/', $footer_rule )
		&& (bool) preg_match( '/min-height\s*:\s*0/', $footer_rule ),
	'2c. The footer is `flex: 0 1 auto` with `min-height: 0`, so it can yield space to the items region',
	$failures
);

bhp_drawer_layout_assert(
	(bool) preg_match( '/flex-direction\s*:\s*column/', $footer_rule )
		&& (bool) preg_match( '/display\s*:\s*flex/', $footer_rule ),
	'2d. The footer is a flex column, which is what lets one region scroll while the other stays pinned',
	$failures
);

// ==================== 3. THE TWO FOOTER REGIONS ====================

$scroll_rule  = bhp_drawer_rule( $css, '.bhp-cart-drawer__footer-scroll' );
$actions_rule = bhp_drawer_rule( $css, '.bhp-cart-drawer__footer-actions' );

bhp_drawer_layout_assert(
	'' !== $scroll_rule
		&& preg_match( '/overflow-y\s*:\s*auto/', $scroll_rule )
		&& preg_match( '/min-height\s*:\s*0/', $scroll_rule ),
	'3a. `.bhp-cart-drawer__footer-scroll` scrolls its own overflow (`overflow-y: auto`, `min-height: 0`)',
	$failures
);

// Secure Checkout must never require the customer to find a second scrollbar.
bhp_drawer_layout_assert(
	'' !== $actions_rule
		&& preg_match( '/flex\s*:\s*0\s+0\s+auto/', $actions_rule )
		&& ! preg_match( '/overflow/', $actions_rule ),
	'3b. `.bhp-cart-drawer__footer-actions` is `flex: 0 0 auto` and never scrolls — Secure Checkout stays reachable at every viewport height',
	$failures
);

// ==================== 4. THE MARKUP ====================

bhp_drawer_layout_assert(
	(bool) preg_match( '/<div class="bhp-cart-drawer__footer-scroll">/', $php ),
	'4a. The scrollable footer region is rendered',
	$failures
);

bhp_drawer_layout_assert(
	(bool) preg_match( '/<div class="bhp-cart-drawer__footer-actions">/', $php ),
	'4b. The pinned action region is rendered',
	$failures
);

// Positions, so nesting and order are asserted rather than assumed.
$pos = array(
	'footer'   => strpos( $php, 'class="bhp-cart-drawer__footer"' ),
	'scroll'   => strpos( $php, 'class="bhp-cart-drawer__footer-scroll"' ),
	'addon'    => strpos( $php, 'class="bhp-cart-drawer__addon"' ),
	'summary'  => strpos( $php, 'class="bhp-cart-drawer__summary"' ),
	'coupon'   => strpos( $php, 'class="bhp-cart-drawer__coupon-hint"' ),
	'ship'     => strpos( $php, 'class="bhp-cart-drawer__ship-note"' ),
	'actions'  => strpos( $php, 'class="bhp-cart-drawer__footer-actions"' ),
	'checkout' => strpos( $php, 'bhp-cart-drawer__checkout' ),
	'continue' => strpos( $php, 'class="bhp-cart-drawer__continue"' ),
);

bhp_drawer_layout_assert(
	false !== $pos['footer'] && $pos['footer'] < $pos['scroll'] && $pos['scroll'] < $pos['addon'],
	'4c. The add-on order bump is INSIDE the scrollable region',
	$failures
);

// Andrew Signore, 2026-08-04 (relayed): the activity-book checkbox is
// "permanent and universal, regardless of cart contents". Being first in the
// scroll region is what keeps that true on a short viewport — clipping starts
// at the bottom, so the add-on is the last thing that could ever scroll away.
bhp_drawer_layout_assert(
	$pos['addon'] < $pos['summary'] && $pos['summary'] < $pos['coupon'] && $pos['coupon'] < $pos['ship'],
	'4d. Scroll-region order is add-on -> summary -> coupon hint -> shipping note, so a short viewport clips the two hints and never the order bump or the Estimated total',
	$failures
);

bhp_drawer_layout_assert(
	$pos['ship'] < $pos['actions'] && $pos['actions'] < $pos['checkout'] && $pos['checkout'] < $pos['continue'],
	'4e. Secure Checkout and Continue Shopping are both inside the pinned action region, in that order',
	$failures
);

// ==================== 5. NOTHING ELSE CHANGED ====================
// A layout fix that silently edits approved copy is not a layout fix.

/*
 * ⭐⭐ THE CONTIGUOUS-US DISCLOSURE CHANGED ON 2026-08-18, AND IT CHANGED
 *     BECAUSE ANDREW SIGNORE SAID SO, NOT BECAUSE AN AGENT TIDIED IT.
 *
 *     His words, verbatim, relayed through the Chief of Staff and NOT witnessed
 *     by the session that made this edit: "change to I currently ship".
 *
 *     The assertion below therefore moved from
 *         "We currently ship within the contiguous United States."   (locked 2026-08-03, A5 / D3)
 *     to
 *         "I currently ship within the contiguous United States."    (approved 2026-08-18)
 *
 *     ⛔ THE OLD STRING IS RECORDED HERE RATHER THAN DELETED, so a future
 *        reader can see that the lock moved on the founder's instruction and
 *        does not re-derive the change as a regression. Standing Rules §9 says
 *        approved copy is locked and §9.1 is the voice rule; a locked string
 *        moves only with his word, and this one has it.
 *
 *     ⛔ IT IS NOW BYTE-IDENTICAL TO `inc/checkout-experience.php`
 *        `bhp_checkout_shipping_scope_notice()`, which
 *        `tests/test-visit-pickup-copy-gate.php` already asserts. Two surfaces,
 *        one sentence, one voice.
 */
$locked_copy = array(
	'Have a coupon? You can add it at checkout.'          => 'coupon hint',
	'I currently ship within the contiguous United States.' => 'contiguous-US disclosure (A5 / D3 wording, voice corrected 2026-08-18 on Andrew Signore\'s approval: "change to I currently ship")',
	'Secure Checkout'                                     => 'primary CTA label',
	'Continue Shopping'                                   => 'secondary CTA label',
	'Your Cart'                                           => 'drawer heading',
);

foreach ( $locked_copy as $string => $what ) {
	bhp_drawer_layout_assert(
		false !== strpos( $php, $string ),
		"5a. Locked copy intact — {$what}",
		$failures
	);
}

/*
 * ⛔ AND THE SUPERSEDED WORDING IS GONE FROM THE RENDERED SENTENCE, not merely
 *    joined by the new one. Asserting only the presence of the "I" version
 *    would pass on a file that still contained BOTH, which is precisely how a
 *    half-applied copy change survives a green suite.
 *
 * ⚠ The check is scoped to the `ship-note` PARAGRAPH rather than the whole
 *   file, because the comment block directly above that paragraph quotes the
 *   superseded sentence on purpose, so the movement is visible to the next
 *   reader instead of being re-derived.
 */
$ship_note = '';
if ( preg_match( '/<p class="bhp-cart-drawer__ship-note">(.*?)<\/p>/s', $php, $m ) ) {
	$ship_note = $m[1];
}
bhp_drawer_layout_assert(
	'I currently ship within the contiguous United States.' === trim( $ship_note ),
	'5b. ⭐ The contiguous-US paragraph renders Andrew\'s 2026-08-18 wording exactly ("change to I currently ship")',
	$failures
);
bhp_drawer_layout_assert(
	false === strpos( $ship_note, 'We currently ship' ),
	'5c. ⛔ The superseded "We currently ship" is GONE from the rendered paragraph, not merely accompanied by the new one',
	$failures
);
bhp_drawer_layout_assert(
	false === stripos( $ship_note, ' we ' ) && 0 !== stripos( trim( $ship_note ), 'we ' ),
	'5d. ⛔ No company "we" survives anywhere in the disclosure a customer reads (Standing Rules §9.1, Andrew Signore 2026-08-18)',
	$failures
);

// The three body regions are untouched by this change, and F4's Andrew-ruled
// DOM order (cross-sell ABOVE the line items: "put it on top of the inventory
// so they see it first") must survive it.
$cross = strpos( $php, 'class="bhp-cart-drawer__crosssell"' );
$items = strpos( $php, 'class="bhp-cart-drawer__items"' );
$msg   = strpos( $php, 'class="bhp-cart-drawer__message"' );
bhp_drawer_layout_assert(
	false !== $cross && $cross < $items && $items < $msg,
	'5b. F4 preserved: the cross-sell still renders above the line items, which still render above the progress message',
	$failures
);

bhp_drawer_layout_assert(
	(bool) preg_match( '/class="bhp-cart-drawer__summary" aria-live="polite"/', $php )
		&& (bool) preg_match( '/class="bhp-cart-drawer__message" aria-live="polite"/', $php ),
	'5c. Both `aria-live="polite"` regions survived the re-wrap',
	$failures
);

// The add-on container must stay a plain empty div: `addon-upsell.js` fills
// it only when the SKU resolves to a real purchasable product, and on an
// environment without that product it must render as nothing at all.
bhp_drawer_layout_assert(
	(bool) preg_match( '/<div class="bhp-cart-drawer__addon"><\/div>/', $php ),
	'5d. The add-on container is still rendered empty and populated only by addon-upsell.js',
	$failures
);

// ==================== 6. VERSION ====================

bhp_drawer_layout_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.26', '>=' ),
	'6a. Plugin version is at least 1.8.26 (currently ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ') — the stylesheet is cache-busted by this constant',
	$failures
);

echo empty( $failures ) ? "\nALL CART DRAWER LAYOUT TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
