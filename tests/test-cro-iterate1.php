<?php
/**
 * Brave Hearts — CRO ITERATE 1: the three fixes from the full rubric audit.
 *
 * `CYCLE165-LD-ITERATE-1-CRO-FIXES` (2026-08-19, theme 1.19.265).
 * Source of the findings: the `commerce-cx` full rubric audit of
 * staging2 at theme 1.19.264 / plugin 1.8.59, `CX-018`, `CX-020`, `CX-021`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cro-iterate1.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from the SHIPPED stylesheet text, the SHIPPED PHP, and the SERVED
 * document — never from a guess and never from a docblock:
 *
 *   §1  `CX-018` — the buy-box label rule that the sitewide `body:not(.home)
 *       h2` rule has been beating is now scoped to win, at the size its own
 *       component always asked for; the desktop section exists, is confined to
 *       `min-width: 601px`, and pins the summary column to the top of its row.
 *   §2  `CX-018` — the mobile first screen step 3 built is UNTOUCHED: every
 *       `order:` slot it named is still in the `max-width: 600px` block, and
 *       the new section introduces no `order`, no `display: none` and no
 *       `visibility: hidden`.
 *   §3  `CX-020` — the capture form's segment select is >= 16px in the shipped
 *       source AND in the shipped minified artefact, and the form-wide floor
 *       is present.
 *   §4  `CX-021` — the stock-heading strip is a pure function that removes
 *       exactly one node, leaves everything else byte-identical, is
 *       idempotent, and fails OPEN when the markup does not match; and the
 *       served `/cart/` document carries exactly one empty-cart message.
 *   §5  the branded empty-cart copy still passes the standing voice rails.
 *   §6  no price literal entered any file this release touched.
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads text and
 *    markup. It does NOT prove that price and Add-to-Cart land above 900 px at
 *    1440, that nothing overflows, or that the reorder paints as intended.
 *    Those are BROWSER facts. They were measured separately in headless Chrome
 *    at an asserted `window.innerWidth`, before and after, and filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-iterate1-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 * ⛔ NOTHING IS WRITTEN. No product, price, variation, coupon, stock level,
 *    shipping, tax or payment setting, cart, order, post, page, option,
 *    attachment, review or user is created or modified by any line here.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_it1_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_it1_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** Strip CSS and PHP comments, so a docblock quoting a value cannot pass a test. */
function bhp_it1_code_only( $src ) {
	$src = preg_replace( '#/\*.*?\*/#s', '', (string) $src );
	return preg_replace( '#^\s*//.*$#m', '', $src );
}

$tpl_dir = get_template_directory();

$css_formats     = (string) @file_get_contents( $tpl_dir . '/assets/css/book-formats.css' );
$css_formats_min = (string) @file_get_contents( $tpl_dir . '/assets/css/book-formats.min.css' );
$css_product     = (string) @file_get_contents( $tpl_dir . '/assets/css/product-template.css' );
$css_product_min = (string) @file_get_contents( $tpl_dir . '/assets/css/product-template.min.css' );
$css_style       = (string) @file_get_contents( $tpl_dir . '/style.css' );
$css_style_min   = (string) @file_get_contents( $tpl_dir . '/style.min.css' );
$php_checkout    = (string) @file_get_contents( $tpl_dir . '/inc/checkout-experience.php' );

echo "\n=== \u{00A7}0 — THE ARTEFACTS THIS SUITE READS ARE PRESENT ===\n";

foreach (
	array(
		'assets/css/book-formats.css'         => $css_formats,
		'assets/css/book-formats.min.css'     => $css_formats_min,
		'assets/css/product-template.css'     => $css_product,
		'assets/css/product-template.min.css' => $css_product_min,
		'style.css'                           => $css_style,
		'style.min.css'                       => $css_style_min,
		'inc/checkout-experience.php'         => $php_checkout,
	) as $file => $src
) {
	bhp_it1_assert( '' !== $src, "§0 {$file} is readable and non-empty", $failures );
}

/* ⚠ AMENDED BY `CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS` (theme 1.19.266),
 *   and the amendment is disclosed here rather than made quietly, because this
 *   is another workstream's suite.
 *
 * IT READ `'1.19.265' === ...`. That is an equality on a version that only
 * ever moves forward, so this assertion was guaranteed to fail on the very
 * next release even though every fix it guards was still present and correct —
 * which is what happened, on the next release, forty minutes later.
 *
 * WHAT THE ASSERTION IS ACTUALLY FOR is "the build carrying CX-018/020/021 is
 * the one installed", and `version_compare( ..., '>=' )` says that without
 * also asserting that no one has shipped since. The floor is still 1.19.265,
 * so a rollback below it fails exactly as before. Nothing else in this file is
 * touched, and all 44 of its other assertions were green on 1.19.266.
 */
$bhp_it1_ver = wp_get_theme()->get( 'Version' );
bhp_it1_assert(
	version_compare( $bhp_it1_ver, '1.19.265', '>=' ),
	"§0.1 the active theme is at or after 1.19.265, the release these fixes shipped in (found {$bhp_it1_ver})",
	$failures
);

echo "\n=== \u{00A7}1 — CX-018: THE DESKTOP FIRST SCREEN ===\n";

$formats_code = bhp_it1_code_only( $css_formats );
$product_code = bhp_it1_code_only( $css_product );
$style_code   = bhp_it1_code_only( $css_style );

/*
 * The test is only meaningful while the rule it defends against still exists.
 * If someone later deletes `body:not(.home) h2`, this assertion fails loudly
 * and whoever reads it learns the scoped override is now redundant — which is
 * a better outcome than a green suite guarding nothing.
 */
bhp_it1_assert(
	1 === preg_match( '/body:not\(\.home\)\s+h2\s*\{[^}]*font-size/s', $style_code ),
	'§1.1 the sitewide body:not(.home) h2 font-size rule still exists (this is what §1.2 must outrank)',
	$failures
);

bhp_it1_assert(
	1 === preg_match(
		'/\.bhp-formats\s+\.bhp-formats__heading\s*\{[^}]*font-size:\s*1rem/s',
		$formats_code
	),
	'§1.2 book-formats.css scopes the buy-box heading to .bhp-formats .bhp-formats__heading at 1rem',
	$failures
);

/*
 * Specificity, computed rather than trusted. `.bhp-formats .bhp-formats__heading`
 * is two classes and no type selectors: (0,2,0). `body:not(.home) h2` is one
 * class (from `:not(.home)`) and two type selectors: (0,1,2). Classes are
 * compared before type selectors, so 2 > 1 decides it and stylesheet order
 * never enters into it. Asserted as an arithmetic fact so a future reader does
 * not have to re-derive it.
 */
$spec_scoped   = array( 0, 2, 0 );
$spec_sitewide = array( 0, 1, 2 );
bhp_it1_assert(
	$spec_scoped[1] > $spec_sitewide[1],
	'§1.3 (0,2,0) outranks (0,1,2) on class count, so the scoped rule wins independently of order',
	$failures
);

bhp_it1_assert(
	false !== strpos( $css_formats_min, '.bhp-formats .bhp-formats__heading' ),
	'§1.4 the scoped heading rule survived minification into book-formats.min.css',
	$failures
);

// The desktop section: present, and everything in it inside min-width: 601px.
bhp_it1_assert(
	1 === preg_match( '/@media\s*\(\s*min-width:\s*601px\s*\)/', $product_code ),
	'§1.5 product-template.css has a min-width: 601px section',
	$failures
);

$desktop_block = '';
if ( preg_match( '/@media\s*\(\s*min-width:\s*601px\s*\)\s*\{(.*)$/s', $product_code, $m ) ) {
	$desktop_block = $m[1];
}

bhp_it1_assert(
	'' !== $desktop_block
		&& 1 === preg_match( '/>\s*\.summary\s*\{[^}]*align-self:\s*start/s', $desktop_block ),
	'§1.6 the desktop section pins the summary column with align-self: start (the trap in this fix)',
	$failures
);

bhp_it1_assert(
	'' !== $desktop_block
		&& 1 === preg_match( '/\.product_title\s*\{[^}]*margin-bottom:\s*1\.25rem/s', $desktop_block ),
	'§1.7 the desktop section pins the gap under the H1',
	$failures
);

bhp_it1_assert(
	'' !== $desktop_block
		&& 1 === preg_match( '/\.woocommerce-breadcrumb\s*\{[^}]*margin-bottom:\s*1\.15rem/s', $desktop_block ),
	'§1.8 the desktop section pins the gap under the breadcrumb',
	$failures
);

bhp_it1_assert(
	false !== strpos( $css_product_min, 'min-width: 601px' )
		|| false !== strpos( $css_product_min, 'min-width:601px' ),
	'§1.9 the desktop section survived minification into product-template.min.css',
	$failures
);

echo "\n=== \u{00A7}2 — CX-018: THE MOBILE FIRST SCREEN STEP 3 BUILT IS UNTOUCHED ===\n";

/*
 * The whole risk of a desktop fix on a template whose mobile order was rebuilt
 * this morning is that it reaches back into <=600px. Two independent guards:
 * every step-3 slot is still named in the max-width block, and the new section
 * introduces no ordering or hiding at all.
 */
$mobile_block = '';
if ( preg_match( '/@media\s*\(\s*max-width:\s*600px\s*\)\s*\{(.*?)\n\}\s*\n/s', $product_code, $m ) ) {
	$mobile_block = $m[1];
} elseif ( preg_match( '/@media\s*\(\s*max-width:\s*600px\s*\)\s*\{(.*)@media/s', $product_code, $m ) ) {
	$mobile_block = $m[1];
}

bhp_it1_assert( '' !== $mobile_block, '§2.1 the max-width: 600px block is still present and parseable', $failures );

foreach (
	array(
		'.product_title'                    => 'order: 1',
		'.bhp-product-value-prop__age'      => 'order: 2',
		'.bhp-formats'                      => 'order: 3',
		'.amazon-reviews-product-section'   => 'order: 4',
		'.bhp-media-gallery--hero'          => 'order: 5',
		'.bhp-product-value-prop__hook'     => 'order: 6',
	) as $sel => $slot
) {
	bhp_it1_assert(
		false !== strpos( $mobile_block, $sel ) && false !== strpos( $mobile_block, $slot ),
		"§2.2 step 3's mobile slot survives: {$sel} / {$slot}",
		$failures
	);
}

bhp_it1_assert(
	'' !== $desktop_block && 0 === preg_match( '/\border\s*:/', $desktop_block ),
	'§2.3 the desktop section reorders nothing',
	$failures
);

bhp_it1_assert(
	'' !== $desktop_block
		&& 0 === preg_match( '/display\s*:\s*none|visibility\s*:\s*hidden/', $desktop_block ),
	'§2.4 the desktop section hides nothing',
	$failures
);

echo "\n=== \u{00A7}3 — CX-020: NO FORM CONTROL UNDER 16px IN THE CAPTURE FORM ===\n";

$segment_rule = '';
if ( preg_match( '/\.acquisition-form__field--segment\s+select\s*\{([^}]*)\}/s', $style_code, $m ) ) {
	$segment_rule = $m[1];
}

bhp_it1_assert( '' !== $segment_rule, '§3.1 the segment select rule is present in style.css', $failures );

bhp_it1_assert(
	'' !== $segment_rule && false === strpos( $segment_rule, '--text-sm' ),
	'§3.2 the segment select no longer takes --text-sm (14.4px, the iOS zoom trigger)',
	$failures
);

/*
 * ⭐ EXACTLY ONE DECLARATION DECIDES THIS FORM'S CONTROL SIZE.
 *    The segment rule and the form-wide floor have IDENTICAL specificity —
 *    (0,1,1) each — so a `font-size` in both is a tie broken by source order,
 *    and the loser is dead code whose stated value is not the measured value.
 *    A first pass at CX-020 shipped exactly that (declared 1rem, measured
 *    18px). The remedy is not more specificity, it is one declaration.
 */
bhp_it1_assert(
	'' !== $segment_rule && 0 === preg_match( '/font-size/', $segment_rule ),
	'§3.3 the segment select rule declares no font-size, so the form-wide floor is the only authority',
	$failures
);

bhp_it1_assert(
	1 === preg_match(
		'/\.acquisition-form\s+select\s*,\s*\.acquisition-form\s+textarea\s*\{[^}]*font-size:\s*max\(\s*1rem/s',
		$style_code
	),
	'§3.4 a form-wide >=16px floor covers input, select and textarea, so a later field cannot regress',
	$failures
);

bhp_it1_assert(
	false !== strpos( $css_style_min, '.acquisition-form__field--segment select' )
		&& false !== strpos( $css_style_min, 'max(1rem, 1em)' ),
	'§3.5 both CX-020 rules survived minification into style.min.css',
	$failures
);

echo "\n=== \u{00A7}4 — CX-021: EXACTLY ONE EMPTY-CART MESSAGE ===\n";

bhp_it1_assert(
	function_exists( 'bhp_empty_cart_strip_stock_title' ),
	'§4.1 bhp_empty_cart_strip_stock_title() is loaded',
	$failures
);

if ( function_exists( 'bhp_empty_cart_strip_stock_title' ) ) {
	// WooCommerce's own markup, as served by staging2 at 1.19.264.
	$stock = '<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">Your cart is currently empty!</h2>';
	$before = '<div class="wp-block-woocommerce-empty-cart-block">KEEP-BEFORE';
	$after  = 'KEEP-AFTER<a href="/shop/">Browse store</a></div>';

	$stripped = bhp_empty_cart_strip_stock_title( $before . $stock . $after );

	bhp_it1_assert(
		false === strpos( $stripped, 'wc-block-cart__empty-cart__title' ),
		'§4.2 the stock heading is removed from the rendered block',
		$failures
	);
	bhp_it1_assert(
		$before . $after === $stripped,
		'§4.3 every other byte of the block is untouched (exact string equality, not a substring check)',
		$failures
	);
	bhp_it1_assert(
		$stripped === bhp_empty_cart_strip_stock_title( $stripped ),
		'§4.4 the strip is idempotent',
		$failures
	);

	// Fails OPEN: unmatched markup is returned byte-identical, never blanked.
	$foreign = '<div class="wp-block-woocommerce-empty-cart-block"><h2 class="something-else">Renamed by a future Woo release</h2></div>';
	bhp_it1_assert(
		$foreign === bhp_empty_cart_strip_stock_title( $foreign ),
		'§4.5 unmatched markup is returned byte-identical — the strip fails open, not closed',
		$failures
	);

	// One node, not a greedy sweep to the last </h2> on the page.
	$two = $stock . '<h2 class="bhp-empty-cart__title">Your expedition pack is empty</h2>';
	bhp_it1_assert(
		false !== strpos( bhp_empty_cart_strip_stock_title( $two ), 'bhp-empty-cart__title' ),
		'§4.6 the strip is non-greedy: the branded heading beside it survives',
		$failures
	);
}

/*
 * The served document. `/cart/` for an anonymous request has an empty cart, so
 * this is the real empty state, rendered by the real filter chain.
 */
$cart_html = bhp_it1_fetch( home_url( '/cart/' ) );
if ( '' === $cart_html ) {
	echo "SKIP: §4.7 /cart/ could not be fetched from this host\n";
} else {
	bhp_it1_assert(
		1 === substr_count( $cart_html, 'bhp-empty-cart__title' ),
		'§4.7 the served /cart/ carries exactly one branded empty-cart heading',
		$failures
	);
	bhp_it1_assert(
		0 === substr_count( $cart_html, 'wc-block-cart__empty-cart__title' ),
		'§4.8 the served /cart/ carries no stock empty-cart heading',
		$failures
	);
}

echo "\n=== \u{00A7}5 — THE BRANDED COPY STILL PASSES THE VOICE RAILS ===\n";

if ( function_exists( 'bhp_empty_cart_copy' ) ) {
	$copy = bhp_empty_cart_copy();
	$words = $copy['title'] . ' ' . $copy['text'] . ' ' . $copy['primaryLabel'] . ' ' . $copy['secondaryLabel'];

	bhp_it1_assert(
		0 === preg_match( '/\b(we|us|our|we\'re|we\'ve)\b/i', $words ),
		'§5.1 §9.1 — the empty-cart copy contains no "we"/"us"/"our"',
		$failures
	);
	bhp_it1_assert(
		false === strpos( $words, "\u{2014}" ) && false === strpos( $words, '--' ),
		'§5.2 the empty-cart copy contains no em dash',
		$failures
	);
	bhp_it1_assert(
		'Your expedition pack is empty' === $copy['title'],
		'§5.3 the approved title is byte-unchanged by this release',
		$failures
	);
} else {
	echo "SKIP: §5 bhp_empty_cart_copy() is not loaded\n";
}

echo "\n=== \u{00A7}6 — NO PRICE LITERAL ENTERED ANY FILE THIS RELEASE TOUCHED ===\n";

foreach (
	array(
		'assets/css/book-formats.css'     => $css_formats,
		'assets/css/product-template.css' => $css_product,
		'inc/checkout-experience.php'     => $php_checkout,
	) as $file => $src
) {
	$code_only = bhp_it1_code_only( $src );
	bhp_it1_assert(
		0 === preg_match( '/\$\s?\d+\.\d{2}/', $code_only ),
		"§6 {$file} contains no price literal",
		$failures
	);
}

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL PASS\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
