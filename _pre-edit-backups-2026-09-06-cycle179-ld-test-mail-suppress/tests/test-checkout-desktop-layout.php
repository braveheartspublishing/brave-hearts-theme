<?php
/**
 * Brave Hearts — DESKTOP CHECKOUT LAYOUT (theme 1.19.185).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-checkout-desktop-layout.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the Chief of Staff
 * and NOT witnessed by the agent that wrote this file):
 *
 *   "The order summary we fixed a while back is now active at the top -
 *    needs to be removed."
 *
 * The cause is measured in assets/css/checkout-experience.css: WooCommerce
 * Blocks picks its cart/checkout layout from the CONTAINER width, not the
 * viewport, and the checkout page's container could never exceed ~664px, so
 * `is-large` — the only state that produces the two-column desktop layout —
 * was unreachable at every viewport.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * This is a PHP/CLI suite. It has no layout engine, so it CANNOT measure a
 * rendered container, cannot evaluate a media query, and cannot observe which
 * `is-*` class Blocks applies. Claiming otherwise would be a fabricated
 * verification, which the standing rules put in the same class as a fabricated
 * review.
 *
 * What it DOES prove, and this is the part that regresses silently:
 *
 *   §1  The stylesheet that carries the fix is still enqueued on /cart/ and
 *       /checkout/, and ONLY there.
 *   §2  Both halves of the fix are present in the SHIPPED stylesheet, with the
 *       exact selectors and the exact 1024px breakpoint. A future
 *       "tidy-up" that drops one half is caught here.
 *   §3  The original F3 `is-mobile` rule still exists. The new rule EXTENDS it;
 *       it does not replace it, and deleting the old one would return the
 *       duplicate summary to phones.
 *   §4  ⭐ THE SELECTOR IS ENUMERATED, NOT `:not(.is-large)`. This is the one
 *       assertion whose failure is dangerous rather than cosmetic: a `:not()`
 *       form would match when Blocks emits no layout class at all and would
 *       DELETE THE DESKTOP ORDER SUMMARY outright. Asymmetric failure modes
 *       deserve an explicit test.
 *   §5  The checkout page still renders through page.php's `.page-content
 *       .page-<slug>` wrapper the width rule targets. If the checkout page
 *       ever gets its own template, rule (1) silently stops applying and the
 *       defect returns with no other symptom.
 *   §6  ⛔ NO PAYMENT PATH WAS TOUCHED. The stylesheet must not contain a
 *       payment-gateway selector, and the theme must not filter payment
 *       gateways. Asserted because this release was raised alongside a
 *       payment-methods report it deliberately does NOT address.
 *
 * The geometry itself is verified in a real browser and recorded in the
 * session's QA evidence, not here.
 *
 * ⛔ NO ORDER IS CREATED. NO CART IS BUILT. No product record, price, coupon,
 *    stock level, shipping, tax or payment setting is read or written by any
 *    part of this file, on any environment.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cdl_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$theme_dir = get_template_directory();
$css_path  = $theme_dir . '/assets/css/checkout-experience.css';
$php_path  = $theme_dir . '/inc/checkout-experience.php';

echo "\n=== §1 · the stylesheet is enqueued on the purchase path, and only there ===\n";

bhp_cdl_assert( file_exists( $css_path ), '§1a assets/css/checkout-experience.css exists', $failures );
bhp_cdl_assert( file_exists( $php_path ), '§1b inc/checkout-experience.php exists', $failures );

$php = file_exists( $php_path ) ? file_get_contents( $php_path ) : '';
$css = file_exists( $css_path ) ? file_get_contents( $css_path ) : '';

bhp_cdl_assert(
	strpos( $php, "assets/css/checkout-experience.css" ) !== false,
	'§1c the stylesheet is registered by inc/checkout-experience.php',
	$failures
);
bhp_cdl_assert(
	strpos( $php, 'is_cart()' ) !== false && strpos( $php, 'is_checkout()' ) !== false,
	'§1d the enqueue is gated to is_cart() || is_checkout()',
	$failures
);
bhp_cdl_assert(
	has_action( 'wp_enqueue_scripts', 'bhp_enqueue_checkout_experience' ) !== false,
	'§1e bhp_enqueue_checkout_experience is hooked to wp_enqueue_scripts',
	$failures
);

echo "\n=== §2 · both halves of the 1.19.185 fix are in the shipped stylesheet ===\n";

/*
 * Half one — the container width. Asserted as three separate facts because
 * each can be broken on its own: the breakpoint, the checkout selector and
 * the cart selector.
 */
$has_media = (bool) preg_match( '/@media\s*\(\s*min-width:\s*1024px\s*\)/', $css );
bhp_cdl_assert( $has_media, '§2a the 1024px min-width breakpoint is present', $failures );

bhp_cdl_assert(
	strpos( $css, '.page-content.page-checkout' ) !== false,
	'§2b the width rule targets .page-content.page-checkout',
	$failures
);
bhp_cdl_assert(
	strpos( $css, '.page-content.page-cart' ) !== false,
	'§2c the width rule targets .page-content.page-cart',
	$failures
);
bhp_cdl_assert(
	(bool) preg_match( '/max-width:\s*1240px/', $css ),
	'§2d the desktop container cap is 1240px (yields a >700px Blocks container)',
	$failures
);

/*
 * Half two — the duplicate-summary suppression, extended to the two middle
 * stacked states. All four selectors are asserted individually.
 */
$extended = array(
	'.wc-block-checkout.is-small > .wc-block-checkout__sidebar'          => '§2e checkout is-small sidebar suppressed',
	'.wc-block-checkout.is-medium > .wc-block-checkout__sidebar'         => '§2f checkout is-medium sidebar suppressed',
	'.wc-block-cart.is-small > .wc-block-cart__sidebar.is-duplicate'     => '§2g cart is-small duplicate sidebar suppressed',
	'.wc-block-cart.is-medium > .wc-block-cart__sidebar.is-duplicate'    => '§2h cart is-medium duplicate sidebar suppressed',
);
foreach ( $extended as $selector => $label ) {
	bhp_cdl_assert( strpos( $css, $selector ) !== false, $label, $failures );
}

echo "\n=== §3 · the original F3 mobile rule survives — this EXTENDS it ===\n";

bhp_cdl_assert(
	strpos( $css, '.wc-block-checkout.is-mobile > .wc-block-checkout__sidebar' ) !== false,
	'§3a the original is-mobile checkout rule is still present',
	$failures
);
bhp_cdl_assert(
	strpos( $css, '.wc-block-cart.is-mobile > .wc-block-cart__sidebar.is-duplicate' ) !== false,
	'§3b the original is-mobile cart rule is still present',
	$failures
);

echo "\n=== §4 · the suppression selector is ENUMERATED, never :not(.is-large) ===\n";

/*
 * ⭐ THE ONE ASSERTION WHOSE FAILURE IS DANGEROUS RATHER THAN COSMETIC.
 * `:not(.is-large)` matches an element carrying NO layout class, which is
 * exactly what the DOM looks like before Blocks' ResizeObserver has run and
 * would be the permanent state if a future release renamed the classes. The
 * enumerated form fails safe (the duplicate comes back); the :not() form fails
 * catastrophically (the desktop order summary disappears).
 *
 * ⛔ SCANNED WITH COMMENTS STRIPPED, AND THAT IS NOT A DETAIL. The first
 *    version of this section scanned the raw file and FAILED on a correct
 *    build, because the 1.19.185 comment block explains, in prose, why
 *    `:not(.is-large)` was rejected — and `stripos()` cannot tell an
 *    explanation from a selector. It is the same defect
 *    tests/test-collection-purchase-path.php already recorded once: reading
 *    source to judge output. Observed on staging 1.19.185, 2026-08-05.
 */
$css_rules = preg_replace( '#/\*.*?\*/#s', '', $css );

bhp_cdl_assert(
	stripos( $css_rules, ':not(.is-large)' ) === false,
	'§4a no :not(.is-large) selector in any RULE (comments excluded)',
	$failures
);
bhp_cdl_assert(
	stripos( $css_rules, ':not(.is-mobile)' ) === false,
	'§4b no :not(.is-mobile) selector in any RULE (comments excluded)',
	$failures
);
bhp_cdl_assert(
	stripos( $css_rules, '.is-large > .wc-block-checkout__sidebar' ) === false,
	'§4c the desktop (is-large) sidebar is never itself suppressed',
	$failures
);
bhp_cdl_assert(
	stripos( $css, ':not(.is-large)' ) !== false,
	'§4d the rejected :not() form is still EXPLAINED in a comment (the reasoning survives)',
	$failures
);

echo "\n=== §5 · the checkout page still renders through page.php's wrapper ===\n";

/*
 * The width rule targets `.page-content.page-<slug>`, which page.php builds
 * from the post slug. Two things must both hold, and both are read from the
 * live database rather than assumed.
 */
$checkout_id   = (int) wc_get_page_id( 'checkout' );
$cart_id       = (int) wc_get_page_id( 'cart' );
$checkout_slug = $checkout_id > 0 ? get_post_field( 'post_name', $checkout_id ) : '';
$cart_slug     = $cart_id > 0 ? get_post_field( 'post_name', $cart_id ) : '';

echo "     checkout page id={$checkout_id} slug='{$checkout_slug}'\n";
echo "     cart     page id={$cart_id} slug='{$cart_slug}'\n";

bhp_cdl_assert( $checkout_id > 0, '§5a a WooCommerce checkout page is configured', $failures );
bhp_cdl_assert(
	'checkout' === $checkout_slug,
	"§5b the checkout slug is 'checkout', so .page-checkout is the rendered class (got '{$checkout_slug}')",
	$failures
);
bhp_cdl_assert(
	'cart' === $cart_slug,
	"§5c the cart slug is 'cart', so .page-cart is the rendered class (got '{$cart_slug}')",
	$failures
);

/*
 * If the checkout page ever acquires a dedicated template, page.php's wrapper
 * is gone and rule (1) stops applying with no other visible symptom.
 */
$checkout_template = $checkout_id > 0 ? get_page_template_slug( $checkout_id ) : '';
bhp_cdl_assert(
	'' === $checkout_template || 'default' === $checkout_template,
	"§5d the checkout page still uses the default template page.php (got '{$checkout_template}')",
	$failures
);
bhp_cdl_assert(
	! file_exists( $theme_dir . '/page-checkout.php' ),
	'§5e no page-checkout.php exists that would bypass page.php\'s .page-content wrapper',
	$failures
);
bhp_cdl_assert(
	strpos( file_get_contents( $theme_dir . '/page.php' ), 'page-content page-' ) !== false,
	'§5f page.php still emits the "page-content page-<slug>" wrapper',
	$failures
);

/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ §5g/§5h ADDED 1.19.194 (2026-08-05, CYCLE144-LD-210). NOTHING ABOVE
 *    WAS CHANGED OR RELAXED — this section is ADDITIVE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05 (⛔ RELAYED, not witnessed first-hand):
 * "Remove the whole section on the checkout page "Brave Hearts Field Journal
 * Checkout" ... bring everything up".
 *
 * ⭐ WHY THAT RULING LANDS IN *THIS* SUITE. The obvious way to remove a
 *    heading from one page is to give that page its own template. §5e above
 *    already forbids `page-checkout.php` — and it forbids it for a reason
 *    this release makes live rather than hypothetical: the 1240px desktop
 *    container rule targets `.page-content.page-checkout`, which only
 *    page.php emits. §5d and §5e were written as a tripwire for a future
 *    edit, and this is that edit. They passed, and these two assertions
 *    record HOW the removal was done so the next reader does not have to
 *    re-derive that a template was deliberately not created.
 */
$cdl_page_src = file_get_contents( $theme_dir . '/page.php' );

bhp_cdl_assert(
	(bool) preg_match( '/is_checkout\(\)\s*&&\s*!\s*is_order_received_page\(\)/', $cdl_page_src ),
	'§5g the checkout hero is suppressed by a CONDITION inside page.php, not by a separate template',
	$failures
);
bhp_cdl_assert(
	strpos( $cdl_page_src, 'interior-hero interior-hero--parchment' ) !== false
	&& strpos( $cdl_page_src, 'Brave Hearts Field Journal' ) !== false,
	'§5h ⛔ and the hero itself is still present for every OTHER page - a suppression, never a deletion',
	$failures
);

echo "\n=== §6 · this release touched NO payment path ===\n";

/*
 * Raised alongside a production-only "no payment methods available" report
 * that this release deliberately does NOT address. These assertions exist so a
 * later reader cannot mistake this release for a payment fix, and so a future
 * edit to this stylesheet cannot quietly become one.
 */
$payment_selectors = array(
	'wc-block-checkout__payment-method',
	'wc-block-components-radio-control-accordion',
	'radio-control-wc-payment-method-options',
	'wcstripe',
	'wc-stripe',
	'ppcp',
);
foreach ( $payment_selectors as $needle ) {
	bhp_cdl_assert(
		stripos( $css, $needle ) === false,
		"§6a the stylesheet contains no payment selector '{$needle}'",
		$failures
	);
}

/*
 * ⛔ CORRECTED AFTER THIS SUITE PRODUCED TWO FALSE FAILURES ON A CORRECT
 *    BUILD, staging 1.19.185, 2026-08-05. The first version asserted
 *    `! has_filter( 'woocommerce_available_payment_gateways' )`. That is a
 *    GLOBAL question, not a theme question: WooCommerce core and every
 *    payment plugin legitimately register on those hooks, so the assertion
 *    reported a defect that did not exist and said nothing about the theme.
 *    Recorded rather than silently rewritten — an assertion that can only
 *    ever fail is worse than no assertion, because it trains a reader to
 *    ignore a red line. `CYCLE144-LD-124`.
 *
 *    The theme-scoped question is asked instead: does any PHP file the theme
 *    ships attach to a gateway hook? That is answerable from the theme's own
 *    source and is the claim this release actually makes.
 */
$theme_php   = array();
$theme_iter  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $theme_dir, FilesystemIterator::SKIP_DOTS ) );
foreach ( $theme_iter as $file ) {
	if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
		$rel = str_replace( $theme_dir . DIRECTORY_SEPARATOR, '', $file->getPathname() );
		if ( 0 === strpos( $rel, 'tests' ) || 0 === strpos( $rel, 'docs' ) ) {
			continue; // this file names the hooks in prose; so may a release doc
		}
		$theme_php[ $rel ] = file_get_contents( $file->getPathname() );
	}
}
echo '     scanned ' . count( $theme_php ) . " shipped theme PHP files (tests/ and docs/ excluded)\n";

foreach ( array( 'woocommerce_available_payment_gateways', 'woocommerce_payment_gateways' ) as $hook ) {
	$hits = array();
	foreach ( $theme_php as $rel => $src ) {
		if ( preg_match( '/(add_filter|add_action)\s*\(\s*[\'"]' . preg_quote( $hook, '/' ) . '[\'"]/', $src ) ) {
			$hits[] = $rel;
		}
	}
	bhp_cdl_assert(
		empty( $hits ),
		"§6b no shipped theme PHP file registers '{$hook}'" . ( $hits ? ' (found in: ' . implode( ', ', $hits ) . ')' : '' ),
		$failures
	);
}

/*
 * INFORMATIONAL, NOT AN ASSERTION. Who IS attached to the availability hook is
 * exactly what a future payment investigation wants, and printing it costs
 * nothing. It is deliberately not asserted: the correct set is environment
 * dependent and this suite has no basis to declare one.
 */
global $wp_filter;
echo "     woocommerce_available_payment_gateways callbacks (informational):\n";
if ( empty( $wp_filter['woocommerce_available_payment_gateways'] ) ) {
	echo "       (none)\n";
} else {
	foreach ( $wp_filter['woocommerce_available_payment_gateways']->callbacks as $prio => $cbs ) {
		foreach ( $cbs as $cb ) {
			$fn = $cb['function'];
			if ( is_array( $fn ) ) {
				$name = ( is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0] ) . '::' . $fn[1];
			} elseif ( $fn instanceof Closure ) {
				$ref  = new ReflectionFunction( $fn );
				$name = 'Closure @ ' . str_replace( ABSPATH, '', $ref->getFileName() ) . ':' . $ref->getStartLine();
			} else {
				$name = (string) $fn;
			}
			echo "       [{$prio}] {$name}\n";
		}
	}
}

echo "\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED (" . count( $extended ) . " extended selectors, 6 sections)\n";
	exit( 0 );
}

echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
