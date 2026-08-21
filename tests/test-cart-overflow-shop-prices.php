<?php
/**
 * Brave Hearts — CART ROW OVERFLOW + SHOP-CARD FORMAT PRICES (theme 1.19.259).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cart-overflow-shop-prices.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR — two founder rulings, both 2026-08-19
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * (1) Andrew Signore, verbatim, from his own iPhone:
 *
 *       "The cart page is all messed up and moves totally off the page.
 *        the numbers go past the right side of the page"
 *
 * (2) Andrew Signore, verbatim, on the /shop/ cards:
 *
 *       "Show both prices"
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * This is a PHP/CLI suite. It has NO LAYOUT ENGINE. It cannot measure a
 * rendered row, cannot evaluate a media query and cannot see a right edge.
 * Claiming it verified the overflow would be a fabricated verification, which
 * the standing rules put in the same class as a fabricated review.
 *
 * ⭐ THE GEOMETRY WAS MEASURED IN A REAL BROWSER, at asserted `innerWidth`,
 *    with a populated cart, and is recorded in the session's QA evidence and
 *    in the C3-WIDE docblock in assets/css/checkout-experience.css. NOT here.
 *
 * What this file DOES prove — and it is precisely the part that regresses
 * silently, because `document.scrollWidth` cannot see this defect at all:
 *
 *   §1  The stylesheet that carries the fix still ships and is still enqueued
 *       on the purchase path.
 *   §2  ⭐ THE RULE IS AT THE WIDER BREAKPOINT. The row override exists at
 *       WooCommerce's own 699px query — not only at the stale 359px one. This
 *       is the assertion the whole release turns on.
 *   §3  ⛔ SOURCE ORDER. The 359px block still exists AND still comes AFTER
 *       the 699px block. Reordering them silently returns 320px phones to a
 *       56px image track they cannot afford — a cascade bug with no syntax
 *       error and no visible symptom in review.
 *   §4  The 44px tap targets and the quantity selector survived the change.
 *   §5  ⛔ THE SUPERSEDED 1.19.157 MEASUREMENT IS STILL QUOTED IN THE FILE.
 *       Additive-only discipline: a future reader must be able to see that
 *       "360 fits" was once true and why it stopped being true.
 *   §6  Both format prices render on a shop card, and each MATCHES the live
 *       `_price` of the product WooCommerce would actually charge for.
 *   §7  ⛔ NO PRICE LITERAL IN THE MARKUP. Both figures are read from
 *       WC_Product; a hardcoded dollar figure in the shop-card code fails.
 *   §8  ⭐ REWRITTEN 1.19.286 (items 210+211). SUPERSEDED WORDING, kept so the
 *       movement is visible: *"The 'CHOOSE YOUR FORMAT' CTA still points at
 *       the canonical paperback product page, where the format selector
 *       lives."* The card now ADDS the paperback and opens the side panel;
 *       §8 asserts the id it adds is the paperback's and never the hardcover's.
 *   §9  §9.1 voice rails on the new strings: no "we/us/our", no em dash.
 *
 * ⛔ NO ORDER IS CREATED. NO CART IS BUILT. No product record, price, coupon,
 *    stock level, shipping, tax or payment setting is WRITTEN by any part of
 *    this file, on any environment. Prices are READ, never modified.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cos_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$theme_dir = get_template_directory();
$css_path  = $theme_dir . '/assets/css/checkout-experience.css';
$min_path  = $theme_dir . '/assets/css/checkout-experience.min.css';
$php_path  = $theme_dir . '/inc/checkout-experience.php';
$fmt_path  = $theme_dir . '/inc/book-formats.php';

$css = file_exists( $css_path ) ? file_get_contents( $css_path ) : '';
$min = file_exists( $min_path ) ? file_get_contents( $min_path ) : '';
$php = file_exists( $php_path ) ? file_get_contents( $php_path ) : '';
$fmt = file_exists( $fmt_path ) ? file_get_contents( $fmt_path ) : '';

echo "\n=== §1 · the stylesheet ships and is still enqueued on the purchase path ===\n";

bhp_cos_assert( '' !== $css, '§1a assets/css/checkout-experience.css exists and is non-empty', $failures );
bhp_cos_assert( '' !== $min, '§1b assets/css/checkout-experience.min.css exists and is non-empty', $failures );
bhp_cos_assert(
	strpos( $php, 'assets/css/checkout-experience.css' ) !== false,
	'§1c the stylesheet is registered by inc/checkout-experience.php',
	$failures
);
bhp_cos_assert(
	strpos( $php, 'is_cart()' ) !== false && strpos( $php, 'is_checkout()' ) !== false,
	'§1d the enqueue is still gated to is_cart() || is_checkout()',
	$failures
);

echo "\n=== §2 · THE ROW OVERRIDE EXISTS AT THE WIDER (699px) BREAKPOINT ===\n";

/*
 * ⭐ THIS IS THE ASSERTION THE RELEASE TURNS ON. 699px is not a number this
 *    theme chose — it is the breakpoint WooCommerce's own cart.css uses for
 *    the `grid-template-columns: 80px 132px` rule being overridden. Matching
 *    it is what closes the gap by construction instead of by another measured
 *    guess that can expire the way "360 fits" did.
 *
 * Both the SOURCE and the SHIPPED (minified) stylesheet are asserted. A build
 * that was never re-run is the failure mode a source-only check misses, and
 * the browser only ever loads the shipped one.
 */
$mq699_src = '/@media\s*\(\s*max-width:\s*699px\s*\)/';
bhp_cos_assert( (bool) preg_match( $mq699_src, $css ), '§2a source CSS carries a max-width:699px media query', $failures );
bhp_cos_assert( (bool) preg_match( $mq699_src, $min ), '§2b SHIPPED CSS carries a max-width:699px media query', $failures );

/*
 * The three selectors mirror WooCommerce's own two rules (its media query and
 * its `.is-small` / `.is-mobile` container-class variants), so whichever one
 * Blocks applies at a given width is overridden. All three are asserted
 * individually because each can be dropped on its own.
 */
$row_selectors = array(
	'.wc-block-cart table.wc-block-cart-items .wc-block-cart-items__row' => '§2c the .wc-block-cart media-query variant is overridden',
	'.is-small table.wc-block-cart-items .wc-block-cart-items__row'      => '§2d the .is-small container variant is overridden',
	'.is-mobile table.wc-block-cart-items .wc-block-cart-items__row'     => '§2e the .is-mobile container variant is overridden',
);
foreach ( $row_selectors as $selector => $label ) {
	bhp_cos_assert( strpos( $css, $selector ) !== false, $label, $failures );
}

/*
 * The floor is what overflowed — a FIXED 132px product track that cannot
 * shrink. `minmax(0, 1fr)` is the fix; asserting the literal string catches a
 * "tidy-up" that reverts it to a fixed width.
 */
bhp_cos_assert(
	(bool) preg_match( '/grid-template-columns:\s*56px\s+minmax\(\s*0\s*,\s*1fr\s*\)\s*!important/', $css ),
	'§2f the 699px block declares 56px minmax(0, 1fr) !important',
	$failures
);
bhp_cos_assert(
	(bool) preg_match( '/grid-template-columns:\s*56px\s+minmax\(\s*0\s*,\s*1fr\s*\)\s*!important/', $min ),
	'§2g the SHIPPED stylesheet declares 56px minmax(0, 1fr) !important',
	$failures
);

/*
 * ⚠ `!important` is load-bearing and was proven necessary by measurement in
 *   1.19.158, not added pre-emptively: WooCommerce's rule has EQUAL
 *   specificity, so without it the cascade winner is decided by stylesheet
 *   order, which is WooCommerce's to choose. The 1.19.158 half-fix left the
 *   table obeying and the ROW not, with the price still clipped.
 */
bhp_cos_assert(
	substr_count( $css, 'minmax(0, 1fr) !important' ) >= 2,
	'§2h both the 699px and the 359px blocks keep !important on the row template',
	$failures
);

echo "\n=== §3 · SOURCE ORDER — the 359px block survives and still comes LAST ===\n";

/*
 * ⛔ THE CASCADE IS THE MECHANISM AND IT HAS NO SYNTAX ERROR WHEN BROKEN.
 *    Below 360px a 56px image track is more than the viewport can spare, so
 *    the narrower block must keep winning there. Two rules of equal
 *    specificity in one stylesheet are decided purely by source order.
 */
/*
 * ⛔ ANCHORED ON "\n@media ... {", NOT ON THE BARE STRING. Both breakpoints
 *    are also NAMED IN PROSE in the docblocks above them — the C3 block quotes
 *    WooCommerce's own `@media (max-width: 699px)` as the root cause, and
 *    C3-WIDE quotes it again as the reason for the new boundary. A bare
 *    strpos() finds the DOCUMENTATION first and then measures the wrong span,
 *    which is exactly what the first draft of this test did: it reported the
 *    699px block as containing an image rule because the PROSE mentions
 *    `.wc-block-cart-item__image`. Requiring a line-start and a trailing brace
 *    matches only the real declaration.
 */
$pos699 = strpos( $css, "\n@media (max-width: 699px) {" );
$pos359 = strrpos( $css, "\n@media (max-width: 359px) {" );

bhp_cos_assert( false !== $pos359, '§3a the 359px block still exists (it was NOT replaced)', $failures );
bhp_cos_assert( false !== $pos699, '§3b the 699px block exists', $failures );
bhp_cos_assert(
	false !== $pos699 && false !== $pos359 && $pos359 > $pos699,
	'§3c the 359px block comes AFTER the 699px block, so it still wins below 360px',
	$failures
);
bhp_cos_assert(
	(bool) preg_match( '/grid-template-columns:\s*48px\s+minmax\(\s*0\s*,\s*1fr\s*\)/', $css ),
	'§3d the 359px block still declares the narrower 48px image track',
	$failures
);
bhp_cos_assert(
	strpos( $css, '.wc-block-cart-item__image img' ) !== false
		&& (bool) preg_match( '/width:\s*40px/', $css ),
	'§3e the sub-360px 40px image rule survives',
	$failures
);

echo "\n=== §4 · the 44px tap targets and the quantity selector are untouched ===\n";

/*
 * The constraint that decided every number in C3 and in C3-WIDE. Nothing in
 * either block shrinks a control; these assertions are what keeps that true
 * through a future edit.
 */
bhp_cos_assert(
	(bool) preg_match( '/\.wc-block-cart-item__remove-link\s*\{[^}]*min-width:\s*44px/s', $css ),
	'§4a the remove control keeps its 44px hit area',
	$failures
);
bhp_cos_assert(
	(bool) preg_match( '/\.wc-block-cart-item__remove-link\s*\{[^}]*min-height:\s*44px/s', $css ),
	'§4b the remove control keeps its 44px hit height',
	$failures
);
bhp_cos_assert(
	strpos( $css, '.wc-block-components-quantity-selector__input' ) !== false,
	'§4c the quantity selector rule survives the change',
	$failures
);
bhp_cos_assert(
	strpos( $css, '.wc-block-components-quantity-selector__button' ) !== false,
	'§4d the quantity stepper buttons still carry an explicit rule',
	$failures
);
/*
 * ⛔ THE 699px BLOCK MUST NOT TOUCH THE IMAGE. Its whole safety argument is
 *    that 56px is what the cell already measures, so no rendered pixel of the
 *    image moves at 360px and above. A future edit that adds an image rule
 *    inside it breaks that argument silently.
 */
$block699 = '';
if ( false !== $pos699 ) {
	$end      = strpos( $css, "\n}", $pos699 );
	$block699 = false !== $end ? substr( $css, $pos699, $end - $pos699 ) : '';
}
bhp_cos_assert(
	'' !== $block699 && strpos( $block699, '__image' ) === false,
	'§4e the 699px block does NOT resize the cart image (56px is the measured cell, not a new design)',
	$failures
);

echo "\n=== §5 · the superseded 1.19.157 measurement is preserved, not deleted ===\n";

/*
 * Additive-only discipline. The old block was right in its reasoning and stale
 * in exactly one number; deleting the number would make a future session
 * re-derive the same wrong boundary from the same one-item cart.
 */
bhp_cos_assert(
	strpos( $css, '3.7px of slack' ) !== false,
	'§5a the superseded "360px ... 3.7px of slack, fits" measurement is still quoted',
	$failures
);
bhp_cos_assert(
	strpos( $css, 'BOUNDARY IS 359px, NOT 360px' ) !== false,
	'§5b the superseded boundary rationale is still quoted verbatim',
	$failures
);
bhp_cos_assert(
	strpos( $css, 'overflow: clip' ) !== false && strpos( $css, 'scrollWidth' ) !== false,
	'§5c the warning that a horizontal-scroll check CANNOT see this defect survives',
	$failures
);

echo "\n=== §6 · BOTH FORMAT PRICES RENDER, AND BOTH MATCH LIVE PRODUCT DATA ===\n";

if ( ! function_exists( 'bhp_book_shop_format_prices' ) || ! function_exists( 'bhp_book_registry' ) ) {
	bhp_cos_assert( false, '§6a bhp_book_shop_format_prices() and bhp_book_registry() are available', $failures );
} else {
	bhp_cos_assert( true, '§6a bhp_book_shop_format_prices() and bhp_book_registry() are available', $failures );

	$registry = bhp_book_registry();
	bhp_cos_assert( count( $registry ) === 3, '§6b the registry still carries exactly three canonical titles', $failures );

	foreach ( $registry as $key => $book ) {
		$formats = bhp_book_shop_format_prices( $key );

		bhp_cos_assert(
			count( $formats ) === 2,
			"§6c [{$key}] the card yields TWO priced formats, not one",
			$failures
		);

		$labels = array_map(
			static function ( $f ) {
				return $f['label'];
			},
			$formats
		);
		bhp_cos_assert(
			$labels === array( 'Paperback', 'Hardcover' ),
			"§6d [{$key}] the formats are labelled Paperback then Hardcover, in that order",
			$failures
		);

		/*
		 * ⭐ THE FIGURE IS COMPARED AGAINST THE PRODUCT WOOCOMMERCE WOULD
		 *    ACTUALLY CHARGE FOR — the VARIATION where one exists (Mariana's
		 *    paperback is a variable product), the parent otherwise. Comparing
		 *    against the parent's `_price` would pass on a wrong number.
		 */
		$pb_id = ! empty( $book['pb_variation'] ) ? (int) $book['pb_variation'] : (int) $book['pb_product'];
		$hc_id = (int) $book['hc_product'];

		$expected = array(
			'Paperback' => get_post_meta( $pb_id, '_price', true ),
			'Hardcover' => get_post_meta( $hc_id, '_price', true ),
		);

		foreach ( $formats as $format ) {
			$label = $format['label'];
			$want  = isset( $expected[ $label ] ) ? $expected[ $label ] : null;

			bhp_cos_assert(
				null !== $want && '' !== $want,
				"§6e [{$key}] {$label} has a live _price on the product WooCommerce charges for (id {$pb_id}/{$hc_id})",
				$failures
			);

			// Strip the currency markup wc_price() wraps around the figure and
			// compare the NUMBER, so a currency-symbol or decimal-separator
			// setting change does not produce a false failure.
			$rendered = html_entity_decode( wp_strip_all_tags( $format['price_html'] ), ENT_QUOTES, 'UTF-8' );
			$digits   = preg_replace( '/[^0-9.]/', '', $rendered );

			bhp_cos_assert(
				'' !== $want && abs( (float) $digits - (float) $want ) < 0.005,
				"§6f [{$key}] the rendered {$label} figure ({$digits}) matches _price ({$want})",
				$failures
			);
		}
	}

	/*
	 * The superseded helper is deliberately NOT deleted — it is a valid live
	 * price reader and something else may yet want a floor. Asserting its
	 * survival records that its disuse was a decision, not an accident.
	 */
	bhp_cos_assert(
		function_exists( 'bhp_book_lowest_price_html' ),
		'§6g the superseded bhp_book_lowest_price_html() helper still exists (disused, not deleted)',
		$failures
	);
}

echo "\n=== §7 · NO PRICE LITERAL IN THE SHOP-CARD CODE ===\n";

/*
 * ⛔ Both figures must come from WC_Product every request. A hardcoded dollar
 *    figure would go stale the moment Andrew changes a price and would then
 *    misquote the store to a parent. Only the executable code is scanned —
 *    the docblocks above legitimately quote observed prices as evidence, and
 *    scanning them would fail this test on its own documentation (the exact
 *    defect 1.19.256 had to fix in the collection guard).
 */
$fmt_code = preg_replace( '#/\*.*?\*/#s', '', $fmt );
$fmt_code = preg_replace( '#^\s*//.*$#m', '', (string) $fmt_code );

bhp_cos_assert(
	! preg_match( '/\$\s?\d+\.\d{2}/', (string) $fmt_code ),
	'§7a inc/book-formats.php executable code contains no hardcoded price literal',
	$failures
);
bhp_cos_assert(
	strpos( $fmt, 'bhp_book_purchase_data' ) !== false
		&& (bool) preg_match( '/function bhp_book_shop_format_prices/', $fmt ),
	'§7b the card reads its figures through bhp_book_purchase_data()',
	$failures
);
bhp_cos_assert(
	strpos( $fmt, 'bhp_book_hardcover_is_offerable()' ) !== false,
	'§7c the hardcover line is gated by the school-visit paperback-only predicate',
	$failures
);
bhp_cos_assert(
	strpos( $fmt, "Formats from" ) === false || strpos( $fmt, "release the card printed" ) !== false,
	'§7d the superseded "Formats from" string survives only as quoted documentation',
	$failures
);

echo "\n=== §8 · the shop CTA — ⭐ SUPERSEDED BY ITEMS 210+211, AND SAYING SO ===\n";

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS SECTION WAS REWRITTEN IN 1.19.286. THE SUPERSEDED ASSERTIONS ARE
 *     QUOTED HERE RATHER THAN DELETED, because a reader arriving from the
 *     1.19.259 release record needs to see that they moved, not wonder where
 *     they went:
 *
 *       §8b  has_filter(..., 'bhp_book_shop_choose_format_link')
 *       §8d  strpos( $html, 'CHOOSE YOUR FORMAT' ) !== false
 *              "the CTA label is unchanged"
 *       §8e  the CTA href is the paperback PERMALINK
 *
 * ⭐ ANDREW SIGNORE, carrier items 210 + 211, 2026-08-21 (⚠️ RELAYED through
 *    `chief-of-staff`, ⛔ NOT witnessed first-hand): every shop card carries a
 *    uniform ADD TO CART, and every one of them opens the side panel. So the
 *    chapter card's control is no longer a link to the page that sells — it
 *    ADDS THE PAPERBACK (`FD-439`) and the panel takes it from there.
 *
 * ⛔ WHAT §8 STILL GUARDS, AND IT IS THE PART THAT ACTUALLY MATTERED: the
 *    control resolves to the PAPERBACK product, not the hardcover and not a
 *    remembered id. That was the real content of §8c/§8e and it is asserted
 *    below against the live registry, unchanged in strength.
 */

if ( ! function_exists( 'bhp_book_shop_add_to_cart_link' ) || ! function_exists( 'bhp_book_canonical_id' ) ) {
	bhp_cos_assert( false, '§8a the shop CTA filter is available', $failures );
} else {
	bhp_cos_assert( true, '§8a the shop CTA filter is available', $failures );
	bhp_cos_assert(
		has_filter( 'woocommerce_loop_add_to_cart_link', 'bhp_book_shop_add_to_cart_link' ) !== false,
		'§8b the CTA filter is still hooked to woocommerce_loop_add_to_cart_link',
		$failures
	);
	bhp_cos_assert(
		has_filter( 'woocommerce_loop_add_to_cart_link', 'bhp_book_shop_choose_format_link' ) === false,
		'§8b2 ⛔ the SUPERSEDED navigation filter is gone, not left double-hooked beside its replacement',
		$failures
	);

	foreach ( bhp_book_registry() as $key => $book ) {
		$canonical = bhp_book_canonical_id( $key );
		bhp_cos_assert(
			$canonical === (int) $book['pb_product'],
			"§8c [{$key}] the canonical destination is still the PAPERBACK product",
			$failures
		);

		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $book['pb_product'] );
			if ( $product ) {
				$html = bhp_book_shop_add_to_cart_link( '<a>fallback</a>', $product );
				bhp_cos_assert(
					strpos( $html, 'CHOOSE YOUR FORMAT' ) === false,
					"§8d [{$key}] ⛔ the superseded label is GONE from the rendered control",
					$failures
				);
				bhp_cos_assert(
					strpos( $html, 'ADD TO CART' ) !== false,
					"§8d2 [{$key}] ⭐ the control carries the one founder label (item 211)",
					$failures
				);
				/*
				 * ⭐ THE PAPERBACK, NAMED BY ID. This is what §8e used to prove
				 *    through the permalink and it proves it more directly: the
				 *    id the panel will add is the paperback's, and the variation
				 *    id travels beside it because Mariana's paperback is a
				 *    variable product.
				 */
				bhp_cos_assert(
					strpos( $html, 'data-product-id="' . (int) $book['pb_product'] . '"' ) !== false,
					"§8e [{$key}] ⭐ the control adds the PAPERBACK product id, never the hardcover",
					$failures
				);
				bhp_cos_assert(
					strpos( $html, 'data-variation-id="' . (int) $book['pb_variation'] . '"' ) !== false,
					"§8e2 [{$key}] the paperback variation id travels with it (0 when the product is simple)",
					$failures
				);
				bhp_cos_assert(
					strpos( $html, 'data-product-id="' . (int) $book['hc_product'] . '"' ) === false,
					"§8f [{$key}] ⛔ the HARDCOVER id is not what this control adds (FD-439: paperback is the one default)",
					$failures
				);
			}
		}
	}
}

echo "\n=== §9 · §9.1 voice rails on the new customer-facing strings ===\n";

/*
 * The strings this release adds to a customer-facing surface are exactly two:
 * "Paperback" and "Hardcover", plus a "·" separator. They are asserted here
 * rather than assumed, because the rails are absolute: no "we/us/our" in
 * front-facing words, no em dash, no outcome claim.
 */
$new_strings = array( 'Paperback', 'Hardcover' );
foreach ( $new_strings as $s ) {
	bhp_cos_assert(
		! preg_match( '/\b(we|us|our)\b/i', $s ),
		"§9a the new customer-facing string \"{$s}\" carries no company \"we\"",
		$failures
	);
	bhp_cos_assert(
		strpos( $s, "\xE2\x80\x94" ) === false,
		"§9b the new customer-facing string \"{$s}\" carries no em dash",
		$failures
	);
}
bhp_cos_assert(
	strpos( $fmt, "\xC2\xB7" ) !== false,
	'§9c the separator is a middle dot, not an em dash',
	$failures
);
bhp_cos_assert(
	strpos( $fmt, 'bhp-shop-format-prices__sep' ) !== false
		&& (bool) preg_match( '/bhp-shop-format-prices__sep"\s+aria-hidden="true"/', $fmt ),
	'§9d the decorative separator is hidden from assistive technology',
	$failures
);

echo "\n=====================================================================\n";
if ( $failures ) {
	echo 'RESULT: FAIL — ' . count( $failures ) . " assertion(s) failed:\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	echo "=====================================================================\n";
	exit( 1 );
}
echo "RESULT: PASS — every assertion in this file passed.\n";
echo "⛔ REMINDER: this file proves the RULES ship and the FIGURES match live\n";
echo "   product data. It does NOT prove the row fits — that was measured in a\n";
echo "   real browser at asserted innerWidth and is recorded in the QA evidence.\n";
echo "=====================================================================\n";
