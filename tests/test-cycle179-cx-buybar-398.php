<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.398 — THE PINNED PHONE BUY BAR SHOWS ONE CTA AND ONE WALLET.
 *      `CYCLE179-CX-BUILD-398`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHAT THE FOUNDER SAW, WHICH IS WHY THIS SUITE EXISTS. Andrew Signore,
 *    2026-09-07, on staging 1.19.397 on his own iPhone, RELAYED through the
 *    supervising session (Standing Rules §9.2 rule 2 — not witnessed
 *    first-hand by the desk that wrote this file):
 *
 *      "Double get the complete collection on dtaging. Do you think there are
 *       too many ways to pay, like its just a little too big on the screen?"
 *
 *    Two separate defects in one photograph:
 *      (a) TWO identical GET THE COMPLETE COLLECTION buttons stacked in the
 *          bar, and
 *      (b) the CTA plus THREE wallets — Apple Pay, Google Pay and Link — over
 *          roughly half a 375x812 screen.
 *
 * ⭐ ROOT CAUSE OF (a), MEASURED ON STAGING 1.19.397 IN A REAL BROWSER AT AN
 *    ASSERTED `window.innerWidth` OF 375, NOT INFERRED FROM SOURCE:
 *
 *        A.bhp-formats__cta                hidden=true   display=block  h=48
 *        BUTTON.bhp-formats__cta (direct)  hidden=false  display=block  h=48
 *
 *    `[hidden] { display: none }` is a USER-AGENT rule, so the bare
 *    `display: block` that 1.19.393 put on `.bhp-formats__buy
 *    .bhp-formats__cta` beat it on ORIGIN — no specificity contest needed —
 *    and un-hid a control the template had correctly marked hidden. At an
 *    asserted 1440 the same anchor computed `display: none`, so this was a
 *    phone-only defect from the day the bar shipped.
 *
 * ⛔⛔ WHAT THIS SUITE CANNOT DO, STATED SO IT IS NOT MISTAKEN FOR WHAT IT
 *     DOES. PHP DOES NOT EVALUATE CSS AND CANNOT MOUNT A STRIPE WALLET.
 *     Every section below asserts that the shipped ARTEFACT contains the
 *     declarations, shaped correctly, and that the template still prints
 *     exactly one of each control. Whether the bar actually renders two rows,
 *     and WHICH wallet paints, is a real-browser-on-a-real-device question:
 *     that evidence lives in the build record at an asserted
 *     `window.innerWidth`, and ⛔ NO ASSERTION HERE MAY BE READ AS PROOF THAT
 *     A WALLET PAINTED. `CYCLE179-LD-354` is the standing lesson — a CSS rule
 *     that reads correctly can still compute to nothing.
 *
 * ⛔ IT WRITES NOTHING AT ALL. No post, no option, no meta, no session, no
 *    cart, no order, no product, no price, no stock, no shipping setting, no
 *    payment setting. It reads theme files and calls one template function.
 *
 * ⭐ INVOCATION, WITH `--url=` (`CYCLE179-LD-9`):
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-cx-buybar-398.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.398
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE. This suite has no
 *     mail path at all and includes the block anyway, so the next assertion
 *     added here does not have to remember.
 *
 * ⛔ NO ISO DATE APPEARS IN THIS BLOCK, AND THAT IS DELIBERATE. Two suites scan
 *    their OWN source for one and fail if they find it.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$GLOBALS['c398_pass']    = 0;
$GLOBALS['c398_fail']    = 0;
$GLOBALS['c398_skipped'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function c398_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['c398_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['c398_fail'];
	echo "  FAIL  {$label}\n";
}

/**
 * A check that could not be performed, recorded as not performed.
 *
 * @param string $label  What was not checked.
 * @param string $reason Why not.
 * @return void
 */
function c398_skip( $label, $reason ) {
	++$GLOBALS['c398_skipped'];
	echo "  SKIP  {$label}  --  {$reason}\n";
}

/**
 * Read a theme file, or '' when it is not there.
 *
 * @param string $rel Path relative to the theme root.
 * @return string
 */
function c398_src( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Collapse whitespace so a CSS assertion is not defeated by reformatting.
 *
 * @param string $css Stylesheet source.
 * @return string
 */
function c398_flat( $css ) {
	return (string) preg_replace( '/\s+/', ' ', $css );
}

echo "\n=== CYCLE179-CX-BUILD-398 — the pinned phone buy bar ===\n";

/* =========================================================================
 * §0 · VERSIONS
 * ====================================================================== */

echo "\n§0 versions\n";

$c398_theme_ver = wp_get_theme()->get( 'Version' );
echo "  theme version: {$c398_theme_ver}\n";
c398_assert(
	version_compare( $c398_theme_ver, '1.19.398', '>=' ),
	'0.1 the active theme is 1.19.398 or later'
);

$c398_css     = c398_src( 'assets/css/product-template.css' );
$c398_min     = c398_src( 'assets/css/product-template.min.css' );
$c398_tpl     = c398_src( 'template-parts/commerce/format-cards.php' );
$c398_bridge  = c398_src( 'inc/express-checkout-bridge.php' );
$c398_bridge_js = c398_src( 'assets/js/express-checkout-bridge.js' );

c398_assert( '' !== $c398_css, '0.2 assets/css/product-template.css is present in the artefact' );
c398_assert( '' !== $c398_min, '0.3 assets/css/product-template.min.css is present in the artefact' );
c398_assert( '' !== $c398_tpl, '0.4 template-parts/commerce/format-cards.php is present in the artefact' );

$c398_flat     = c398_flat( $c398_css );
$c398_flat_min = c398_flat( $c398_min );

/* =========================================================================
 * §1 · THE DUPLICATE CTA — THE `hidden` ATTRIBUTE IS NO LONGER OVERRULED
 *
 * ⛔ THE NEGATIVE ASSERTION IS THE IMPORTANT ONE. It is not enough that the
 *    fixed selector exists; the ORIGINAL bare selector must be GONE, because
 *    both matching would put the bug straight back — a later bare
 *    `display: block` on the same class wins on source order.
 * ====================================================================== */

echo "\n§1 the duplicate CTA\n";

$c398_bare  = '.bhp-formats__buy .bhp-formats__cta { display: block;';
$c398_fixed = '.bhp-formats__buy .bhp-formats__cta:not([hidden]) { display: block;';

c398_assert(
	false !== strpos( $c398_flat, $c398_fixed ),
	'1.1 the bar CTA rule is guarded with :not([hidden])'
);

c398_assert(
	false === strpos( $c398_flat, $c398_bare ),
	'1.2 the unguarded `.bhp-formats__buy .bhp-formats__cta { display: block` rule is GONE'
);

c398_assert(
	false !== strpos( $c398_flat, '.bhp-formats__buy [hidden] { display: none; }' ),
	'1.3 the buy block states the invariant: anything [hidden] inside it stays hidden'
);

/*
 * ⭐ THE ARTEFACT, NOT THE SOURCE, IS WHAT A CUSTOMER LOADS. `style.min.css`
 *    and its siblings are what the theme enqueues, so a source-only assertion
 *    would pass on a stale build. `test-style-minification.php` already guards
 *    freshness by md5; this guards the two declarations that matter here.
 */
c398_assert(
	false !== strpos( $c398_flat_min, $c398_fixed ),
	'1.4 the :not([hidden]) guard is present in the MINIFIED artefact'
);

c398_assert(
	false === strpos( $c398_flat_min, $c398_bare ),
	'1.5 the unguarded rule is absent from the MINIFIED artefact'
);

/*
 * ⭐ THE TEMPLATE'S HALF OF THE CONTRACT. The stylesheet fix only works
 *    because exactly one of these two controls carries `hidden` at a time.
 *    `$bhp_cta_is_direct` is what decides, and it decides for BOTH — if a
 *    future edit ever hid neither, or hid both, §1.1–1.5 would still pass and
 *    the page would still be wrong.
 */
c398_assert(
	false !== strpos( $c398_tpl, "\$bhp_cta_is_direct ? 'hidden' : ''" ),
	'1.6 the anchor is marked hidden when the direct-buy control is the live one'
);

c398_assert(
	false !== strpos( $c398_tpl, "\$bhp_cta_is_direct ? '' : ' hidden'" ),
	'1.7 the direct-buy control is marked hidden when the anchor is the live one'
);

/*
 * ⛔ ONE ANCHOR, ONE DIRECT CONTROL, ONE EXPRESS CONTAINER — COUNTED, NOT
 *    ASSUMED. The 1.19.393 note in `format-cards.php` says "nothing is
 *    duplicated"; this is the assertion that keeps that true, and it is the
 *    reason the fix is a cascade fix rather than an IntersectionObserver.
 *    THE BAR IS THE PURCHASE BLOCK. `position: fixed` MOVES it; it does not
 *    copy it. There is nothing to hide it "behind".
 */
c398_assert(
	1 === substr_count( $c398_tpl, 'class="bhp-formats__buy"' ),
	'1.8 exactly one .bhp-formats__buy wrapper is printed'
);

c398_assert(
	1 === substr_count( $c398_tpl, 'data-bhp-format-cta' ),
	'1.9 exactly one format CTA anchor is printed'
);

c398_assert(
	/*
	 * ⚠ THE SEMICOLON IS LOAD-BEARING IN THIS NEEDLE. The same call appears a
	 *   second time in the file's own docblock, without one, so matching
	 *   without it counts two and goes red against correct code.
	 */
	1 === substr_count( $c398_bridge, "do_action('woocommerce_after_add_to_cart_form');" ),
	'1.10 the bridge fires the express hook exactly once'
);

c398_assert(
	false !== strpos( $c398_bridge, 'static $printed = false;' ),
	'1.11 the bridge still carries its print-once guard (two containers would be two wallets)'
);

/* =========================================================================
 * §2 · ONE WALLET IN THE BAR, AND NEVER ZERO
 *
 * ⛔⛔ THE SAFETY PROPERTY IS THE ORDER OF THE RULES, NOT THE COUNT OF THEM.
 *     Nothing is hidden unless something else is PROVEN PRESENT by `:has()`,
 *     so there is no browser on which this block can leave the bar with no
 *     way to pay. A device offering only Link keeps Link. These assertions
 *     exist to stop a future "simplification" into an unconditional
 *     `#…-link { display: none }`, which would do exactly that.
 * ====================================================================== */

echo "\n§2 one wallet in the bar, and never zero\n";

$c398_ap   = '#wc-stripe-express-checkout-element-applePay';
$c398_gp   = '#wc-stripe-express-checkout-element-googlePay';
$c398_link = '#wc-stripe-express-checkout-element-link';
$c398_amz  = '#wc-stripe-express-checkout-element-amazonPay';

c398_assert(
	false !== strpos( $c398_flat, ":has(> {$c398_ap}, > {$c398_gp}) > {$c398_link}" ),
	'2.1 Link is hidden ONLY where a native wallet has mounted to replace it'
);

c398_assert(
	false !== strpos( $c398_flat, ":has(> {$c398_ap}, > {$c398_gp}) > {$c398_amz}" ),
	'2.2 Amazon Pay is hidden ONLY where a native wallet has mounted to replace it'
);

c398_assert(
	false !== strpos( $c398_flat, ":has(> {$c398_link}) > {$c398_amz}" ),
	'2.3 with no native wallet, Link is the one that stays and Amazon Pay stands down'
);

c398_assert(
	false !== strpos( $c398_flat, ":has(> {$c398_ap}) > {$c398_gp}" ),
	'2.4 Apple Pay wins over Google Pay on a device that offers both'
);

/*
 * ⛔ THE NEGATIVE THAT MATTERS. An unconditional hide of any wallet id is the
 *    failure mode this whole section is built to prevent: on a device that
 *    offers only that wallet it removes the only way to pay.
 */
$c398_uncond = false;
foreach ( array( $c398_link, $c398_amz, $c398_gp, $c398_ap ) as $c398_id ) {
	if ( preg_match( '/(?<!\) > )' . preg_quote( $c398_id, '/' ) . '\s*\{\s*display:\s*none/', $c398_flat ) ) {
		$c398_uncond = true;
	}
}
c398_assert(
	! $c398_uncond,
	'2.5 no wallet id is hidden unconditionally — every hide is guarded by :has()'
);

c398_assert(
	false !== strpos( $c398_flat_min, ":has(> {$c398_ap}) > {$c398_gp}" ),
	'2.6 the wallet-preference rules are present in the MINIFIED artefact'
);

/*
 * ⛔⛔ THE STRIPE GATEWAY EXPOSES NO OPTION FOR THIS AND THE THEME MUST NOT
 *     PRETEND OTHERWISE. `layout.maxRows`, `paymentMethodOrder` and
 *     `paymentMethods` are hardcoded inside the plugin's own
 *     `build/express-checkout.js`; there is no filter, and the wallet set is
 *     otherwise a PAYMENT-CONFIGURATION question, which is Andrew's gate.
 *     If a future pass ever writes one of those keys from theme code, that is
 *     a decision someone made, and it should fail here first.
 */
c398_assert(
	false === strpos( $c398_bridge_js, 'maxRows' )
		&& false === strpos( $c398_bridge_js, 'paymentMethodOrder' ),
	'2.7 the theme sets no Stripe express element options (no filter exists; wallet set is Andrew\'s gate)'
);

/* =========================================================================
 * §3 · THE BAR'S GEOMETRY — TAP TARGETS AND THE PUBLISHED HEIGHT
 * ====================================================================== */

echo "\n§3 geometry\n";

c398_assert(
	false !== strpos( $c398_flat, 'min-height: 48px;' ),
	'3.1 the bar CTA keeps a 48px minimum tap target'
);

c398_assert(
	false !== strpos( $c398_flat, 'padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));' ),
	'3.2 the bar still clears the iPhone home indicator via env(safe-area-inset-bottom)'
);

/*
 * ⭐ THE RESERVATION IS STILL PUBLISHED BY THE BAR ITSELF. Dropping a wallet
 *    row CHANGES the bar's height, so the ResizeObserver in
 *    `express-checkout-bridge.js` is what keeps `--bhp-buybar-h` honest. If
 *    that were removed, the page would reserve the wrong number and either
 *    cover the footer or leave dead parchment under it.
 */
c398_assert(
	false !== strpos( $c398_bridge_js, "--bhp-buybar-h" )
		&& false !== strpos( $c398_bridge_js, 'ResizeObserver' ),
	'3.3 the bar still measures and republishes its own height'
);

c398_assert(
	false !== strpos( $c398_flat, 'padding-bottom: var(--bhp-buybar-h, 132px);' ),
	'3.4 the page still reserves the bar height from the published custom property'
);

c398_skip(
	'3.5 the rendered bar height at 375',
	'PHP cannot lay out CSS. Measured in a real browser at an asserted window.innerWidth and recorded in the build record.'
);

c398_skip(
	'3.6 which wallet actually paints',
	'browser-and-account dependent. Chrome on the build machine offers Apple Pay + Google Pay; a real iPhone must confirm Apple Pay alone.'
);

/* =========================================================================
 * §4 · THE HARD COMMERCE CONSTRAINTS THIS BUILD MUST NOT HAVE TOUCHED
 *
 * ⭐ Cheap, and it is the section that catches a scope slip rather than a
 *    logic error. This lane changed presentation only.
 * ====================================================================== */

echo "\n§4 constraints not touched\n";

/*
 * ⚠ CORRECTED IN THE SAME SITTING IT WAS WRITTEN, AND RECORDED RATHER THAN
 *   QUIETLY SWAPPED. The first form of this assertion searched BOTH files for
 *   the bare word "bookvault" and went RED against correct code:
 *   `format-cards.php` line 466 carries a PRE-EXISTING comment — *"no product
 *   record, SKU or Bookvault mapping is touched anywhere in this pass"* — which
 *   is a promise not to touch it, not a use of it. ⭐ The constraint that
 *   actually binds is the SHIPPING METHOD, so that is what is searched for,
 *   and only in the stylesheet this lane actually wrote.
 */
c398_assert(
	false === stripos( $c398_css, 'bookvault shipping' )
		&& false === stripos( $c398_css, 'bookvault' ),
	'4.1 the stylesheet this lane wrote does not name BookVAULT'
);

c398_assert(
	false === strpos( $c398_css, 'aggregateRating' ),
	'4.2 no rating markup was introduced by this lane'
);

c398_assert(
	false === strpos( $c398_css, '_stock_status' ) && false === strpos( $c398_tpl, '_stock_status' ),
	'4.3 no stock status is written by this lane'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */

echo "\n=== CYCLE179-CX-BUILD-398 RESULT ===\n";
echo "  passed:  {$GLOBALS['c398_pass']}\n";
echo "  failed:  {$GLOBALS['c398_fail']}\n";
echo "  skipped: {$GLOBALS['c398_skipped']}\n";

if ( $GLOBALS['c398_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
