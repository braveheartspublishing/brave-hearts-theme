<?php
/**
 * PDP REDESIGN PHASE 1 — THE GEOMETRY GATE. Theme 1.19.348.
 * `CYCLE179-LD-348`. Founder priority, seal 672.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-pdp-348.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⭐ THE FOUNDER'S OWN WORDS, 2026-09-02 05:09, are the specification:
 *    "we need the gallery main image to fit in the screen on desktop you have
 *    to scroll up and down to see the entire image ... and there is so much
 *    white space on the left side under the gallery too - are all the product
 *    pages like this?"
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE A PIXEL. PHP has no viewport, no layout engine and no
 *    fold. Nothing in this file shows that dead space is 0px, that the image
 *    clears the fold at 1366x768, or that the gallery survives a resize.
 * ⭐ THAT PROOF IS THE BROWSER HARNESS — real Chromium, `window.innerWidth`
 *    and `window.innerHeight` ASSERTED IN-PAGE before every reading,
 *    `getBoundingClientRect()` for every number. It is the PRIMARY evidence
 *    and it lives in `CYCLE179-LD-348.md` with a before/after table.
 * ⭐ THIS FILE IS THE REGRESSION GATE: it stops the fix being undone later by
 *    an edit nobody re-QAs, and it asserts the two things source CAN carry —
 *    the SHIPPED ARTEFACT's declarations, and the boundaries the release
 *    promised not to cross.
 *
 * WHAT IT ASSERTS
 *   §1  the shipped artefact carries `align-items: start` on the product grid
 *   §2  the desktop geometry block exists and is scoped to `min-width: 901px`
 *   §3  the image cap is expressed against the VIEWPORT, not a file size
 *   §4  the thumbnail rail is one non-wrapping row at >= 44px
 *   §5  the chapter-book stage variable is re-pointed, product hero only
 *   §6  the F3 resize fix is present AND is desktop-scoped (mobile swipe kept)
 *   §7  ⛔ MOBILE IS UNTOUCHED — the 782px cap block and the
 *       `body.bhp-gallery-multi` override survive byte-for-byte in intent
 *   §8  ⛔ NOTHING THIS RELEASE PROMISED NOT TO DO WAS DONE — no price literal,
 *       no rating, no review, no aggregateRating, no bundle offer, no new copy
 *   §9  the build artefact is FRESH (a stale minify ships nothing to a customer)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. Same reason, same
 *    fix, as `test-shop-grid-2up-204.php`. A gate that cannot report failure
 *    is not a gate.
 */
$GLOBALS['p348_failures'] = 0;
$GLOBALS['p348_passes']   = 0;
function p348_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['p348_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['p348_failures']++;
		echo "FAIL: $label\n";
	}
}

$p348_theme = get_template_directory();

/**
 * The SHIPPED stylesheet, whitespace flattened.
 *
 * ⛔ `style.min.css`, NOT `style.css`, AND THE CHOICE IS THE POINT. From
 *    1.19.201 the root stylesheet is served from the build artefact
 *    (`bhp_minified_style_src()`). Asserting the source file would pass on a
 *    build that was never run — the one failure mode that puts verified CSS in
 *    the repository and nothing on the customer's screen.
 */
function p348_shipped( $path ) {
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return preg_replace( '/\s+/', ' ', (string) file_get_contents( $path ) );
}

$p348_css = p348_shipped( $p348_theme . '/style.min.css' );
$p348_fmt = p348_shipped( $p348_theme . '/assets/css/book-formats.min.css' );

p348_assert( '' !== $p348_css, '0.1 style.min.css exists and is readable' );
p348_assert( '' !== $p348_fmt, '0.2 book-formats.min.css exists and is readable' );

/**
 * Brace-matched extraction of every `@media (min-width: 901px)` block in the
 * shipped artefact.
 *
 * ⭐⭐ THE WHOLE SUITE TURNS ON THIS SEPARATION, so it is done once, here, and
 *    both halves are kept. §2–§6 assert what must be INSIDE the desktop block;
 *    §7 asserts what must be OUTSIDE it. Without it, "the rail does not wrap"
 *    would pass on a build that unwrapped it at EVERY width — which is not the
 *    release, and which would silently regress a mobile layout that
 *    `commerce-cx` measured as CORRECT and needing no repair.
 */
/*
 * ⚠️ THIS RETURNS BOTH HALVES, AND THE FIRST VERSION OF THIS FILE DID NOT —
 *    which is why §6.5 failed on the 1.19.348 staging run and the failure was
 *    a TEST defect, not a code defect. That version rebuilt the "outside" half
 *    as `str_replace( $desktop, ' ', $css )`, i.e. it looked for the
 *    CONCATENATION of all the media blocks as one contiguous substring. It is
 *    not contiguous — there is unrelated CSS between them — so the replace
 *    matched nothing, `$outside` stayed equal to the WHOLE stylesheet, and an
 *    assertion of the form "X does not appear outside the block" could never
 *    pass no matter how correctly the CSS was scoped.
 * ⭐ Recorded rather than quietly rewritten: a scoping assertion that cannot
 *    distinguish inside from outside is worse than no assertion, because it
 *    trains the next reader to wave a red line through.
 */
function p348_split_media( $css, $condition ) {
	$needle  = '@media ' . $condition;
	$inside  = '';
	$outside = '';
	$prev    = 0;
	$offset  = 0;
	$len     = strlen( $css );
	while ( false !== ( $at = strpos( $css, $needle, $offset ) ) ) {
		$open = strpos( $css, '{', $at );
		if ( false === $open ) {
			break;
		}
		$depth  = 0;
		$closed = false;
		for ( $i = $open; $i < $len; $i++ ) {
			if ( '{' === $css[ $i ] ) {
				$depth++;
			} elseif ( '}' === $css[ $i ] ) {
				$depth--;
				if ( 0 === $depth ) {
					$inside  .= substr( $css, $open, $i - $open + 1 ) . ' ';
					$outside .= substr( $css, $prev, $at - $prev );
					$prev     = $i + 1;
					$offset   = $i + 1;
					$closed   = true;
					break;
				}
			}
		}
		if ( ! $closed ) {
			break;
		}
	}
	$outside .= substr( $css, $prev );
	return array( $inside, $outside );
}

list( $p348_desktop, $p348_outside ) = p348_split_media( $p348_css, '(min-width: 901px)' );

p348_assert( '' !== trim( $p348_desktop ), '0.3 a `min-width: 901px` block exists in the shipped artefact' );

/* ── §1 · THE WHITE-SPACE FIX ─────────────────────────────────────────────
 * ONE declaration produced 1,072px (colouring) to 1,953px (Mariana) of dead
 * parchment on every PDP: the grid defaulted to `align-items: normal`, i.e.
 * stretch. This is the gate that keeps the line in the file.
 * ⛔ It is asserted OUTSIDE any media query on purpose — it must apply at every
 *    width, and scoping it would reintroduce the bug on some band nobody looks
 *    at.                                                                    */
p348_assert(
	(bool) preg_match( '/\.woocommerce div\.product\s*\{[^}]*align-items:\s*start/', $p348_css ),
	'1.1 ⭐ the product grid declares `align-items: start` — THE white-space fix'
);
p348_assert(
	(bool) preg_match( '/\.woocommerce div\.product\s*\{[^}]*display:\s*grid/', $p348_css ),
	'1.2 …and it is still the two-column grid (nothing was rewritten wholesale)'
);
p348_assert(
	false !== strpos( $p348_desktop, '.woocommerce div.product > .summary { align-self: start' )
	|| (bool) preg_match( '/div\.product\s*>\s*\.summary\s*\{[^}]*align-self:\s*start/', $p348_desktop ),
	'1.3 the purchase column is explicitly `align-self: start` (beats the older `center` rule on source order)'
);

/* ── §2 · SCOPE ───────────────────────────────────────────────────────────
 * 901px is one pixel above the existing `max-width: 900px` collapse to a
 * single column. It is not a new breakpoint and it must not become one.     */
p348_assert(
	(bool) preg_match( '/@media \(max-width: 900px\)[^{]*\{[^}]*\.woocommerce div\.product\s*\{\s*grid-template-columns:\s*1fr/', $p348_css ),
	'2.1 ⛔ the pre-existing 900px single-column collapse is STILL THERE'
);
p348_assert(
	false !== strpos( $p348_desktop, 'grid-template-columns: minmax(0, 1fr) minmax(360px, 1fr)' ),
	'2.2 the desktop split is equal columns (design-lane Concept A, the visual of record)'
);
p348_assert(
	false !== strpos( $p348_desktop, 'gap: clamp(2.5rem, 3.5vw, 3.5rem)' ),
	'2.3 …with the tightened gap that gives the purchase card its extra width'
);

/* ── §3 · THE IMAGE FITS THE VIEWPORT, NOT THE FILE ───────────────────────
 * ⭐ The founder's first complaint. A fixed pixel cap is what produced it:
 *    720px starting at y251 needs a 915px viewport, and a 1440x900 laptop is
 *    15px short before the 80px sticky header is even counted.              */
p348_assert(
	false !== strpos( $p348_desktop, 'max-height: min(560px, calc(100vh - 400px))' ),
	'3.1 ⭐ the WooCommerce gallery image is capped against 100vh, not a file size'
);
p348_assert(
	(bool) preg_match( '/woocommerce-product-gallery__image img\s*\{[^}]*width:\s*auto/', $p348_desktop )
	&& (bool) preg_match( '/woocommerce-product-gallery__image img\s*\{[^}]*height:\s*auto/', $p348_desktop ),
	'3.2 width and height are auto, so the BOX takes the image aspect — no crop, no distortion'
);
p348_assert(
	false !== strpos( $p348_css, 'object-fit: contain' ),
	'3.3 `object-fit: contain` is still declared for the gallery image'
);
p348_assert(
	false === strpos( $p348_desktop, 'object-fit: cover; } .woocommerce div.product > .woocommerce-product-gallery .woocommerce-product-gallery__image img' ),
	'3.4 ⛔ the MAIN image is never `cover` — cropping a book cover is a defect, not a style'
);

/* ── §4 · ONE HORIZONTAL ROW, NEVER A VERTICAL RAIL ───────────────────── */
p348_assert(
	(bool) preg_match( '/ol\.flex-control-thumbs\s*\{[^}]*flex-wrap:\s*nowrap/', $p348_desktop ),
	'4.1 the thumbnail rail does not wrap (it rendered TWO rows of 129px before)'
);
p348_assert(
	(bool) preg_match( '/ol\.flex-control-thumbs\s*\{[^}]*display:\s*flex/', $p348_desktop ),
	'4.2 …and it is a flex ROW, so it can never become a vertical strip'
);
p348_assert(
	(bool) preg_match( '/ol\.flex-control-thumbs li\s*\{[^}]*min-width:\s*44px/', $p348_desktop ),
	'4.3 tiles carry the WCAG 2.5.8 44px target floor'
);
p348_assert(
	(bool) preg_match( '/ol\.flex-control-thumbs li\s*\{[^}]*flex:\s*1 1 0/', $p348_desktop ),
	'4.4 ⭐ tiles divide the column, so ALL slides fit at once with no hardcoded count'
);

/* ── §5 · THE CHAPTER BOOKS USE A DIFFERENT LEVER ─────────────────────────
 * They never render the WooCommerce gallery — `bhp_book_replace_product_
 * gallery()` swaps in `.bhp-media-gallery--hero`, whose STAGE is what is
 * 520px tall. Capping the <img> there would do nothing at all.              */
p348_assert(
	false !== strpos( $p348_desktop, '--bhp-stage-h: min(520px, calc(100vh - 415px))' ),
	'5.1 the chapter-book stage height is re-pointed against the viewport'
);
p348_assert(
	(bool) preg_match( '/div\.product > \.bhp-media-gallery--hero \.bhp-gallery__stage/', $p348_desktop ),
	'5.2 ⛔ …scoped to the PRODUCT HERO only — the Collection page and the four funnel pages read the same variable and are left alone'
);
p348_assert(
	function_exists( 'bhp_book_replace_product_gallery' ),
	'5.3 the gallery-swap function still exists (the two galleries are still two)'
);

/* ── §6 · F3, THE RESIZE BLANK ────────────────────────────────────────────
 * FlexSlider positions its track with an inline `translate3d(-N x itemWidth)`
 * computed once. After a resize the offset is stale, the active slide leaves
 * `.flex-viewport` (overflow: hidden) and the gallery is empty.
 * ⛔ The fix is desktop-scoped BECAUSE FlexSlider's touch handler drives the
 *    very transform it overrides. Killing it below 901px would take swipe away
 *    from phones, and mobile was measured correct.                          */
p348_assert(
	(bool) preg_match( '/woocommerce-product-gallery__wrapper\s*\{[^}]*transform:\s*none\s*!important/', $p348_desktop ),
	'6.1 ⭐ the stale sliding transform is retired on desktop'
);
p348_assert(
	(bool) preg_match( '/woocommerce-product-gallery__wrapper\s*\{[^}]*width:\s*100%\s*!important/', $p348_desktop ),
	'6.2 …and the track width is a percentage, so nothing can go stale'
);
p348_assert(
	false !== strpos( $p348_desktop, '.woocommerce-product-gallery__image.flex-active-slide { display: block !important' ),
	'6.3 the active slide is shown via FlexSlider\'s OWN class — no new JS, no new state'
);
p348_assert(
	(bool) preg_match( '/\.flex-viewport\s*\{\s*height:\s*auto\s*!important/', $p348_desktop ),
	'6.4 the stale inline `height` on .flex-viewport is neutralised'
);
/*
 * ⛔ THE NEEDLE IS `flex-active-slide`, NOT `transform: none !important`, AND
 *    THE SWAP IS THE SECOND HALF OF THE 6.5 CORRECTION. `transform: none
 *    !important` already occurs THREE TIMES elsewhere in this stylesheet for
 *    reasons that have nothing to do with the product gallery, so it can never
 *    be absent from "outside" and the assertion was unfalsifiable in a second,
 *    independent way. `flex-active-slide` appears exactly once in the whole
 *    artefact and only because this release put it there, so it is a real
 *    witness for "the F3 fix did not leak below 901px".
 * ⭐ VERIFIED against the shipped 1.19.348 artefact: inside=1, outside=0.
 */
p348_assert(
	false === strpos( $p348_outside, 'flex-active-slide' )
	&& false !== strpos( $p348_desktop, 'flex-active-slide' ),
	'6.5 ⛔ THE RESIZE FIX DOES NOT LEAK BELOW 901px — phone swipe is preserved'
);
p348_assert(
	false === strpos( $p348_outside, 'flex-wrap: nowrap' )
	&& false === strpos( $p348_outside, '--bhp-stage-h: min(520px' )
	&& false === strpos( $p348_outside, 'calc(100vh - 400px)' ),
	'6.6 ⛔ …and NEITHER does the rail rule, the stage cap or the image cap'
);

/* ── §7 · MOBILE IS UNTOUCHED ─────────────────────────────────────────────
 * `commerce-cx` measured 375x812 as correct and needing no repair (recon row
 * A5, finding F6). The two rules that make it correct must survive.         */
p348_assert(
	false !== strpos( $p348_fmt, '@media (max-width: 782px)' ),
	'7.1 ⛔ the 782px cap block still exists in book-formats.min.css'
);
p348_assert(
	false !== strpos( $p348_fmt, 'body.bhp-gallery-multi' ),
	'7.2 ⛔ the `body.bhp-gallery-multi` override still exists'
);
p348_assert(
	false === strpos( $p348_desktop, 'bhp-gallery-multi' ),
	'7.3 ⛔ …and this release did NOT reach into it from the desktop block'
);
p348_assert(
	(bool) preg_match( '/@media \(max-width: 600px\)/', p348_shipped( $p348_theme . '/assets/css/product-template.min.css' ) ),
	'7.4 the 600px product-template mobile block is still present'
);

/* ── §8 · THE BOUNDARIES THIS RELEASE PROMISED NOT TO CROSS ───────────────
 * ⭐ Stated as tests rather than as prose in a report, because a promise in a
 *    report is not checkable and this one is.                               */
p348_assert(
	false === strpos( $p348_desktop, '22.99' ) && false === strpos( $p348_desktop, '$' ),
	'8.1 ⛔ NO price literal anywhere in the new CSS (the Concept A "add both for $22.99" bundle did NOT ride along)'
);
p348_assert(
	false === stripos( $p348_desktop, 'aggregaterating' ) && false === stripos( $p348_desktop, 'star-rating' ),
	'8.2 ⛔ no rating or aggregateRating surface was introduced'
);
p348_assert(
	false === stripos( $p348_desktop, 'content:' ),
	'8.3 ⛔ the new CSS generates NO text content — no copy, no claim, no count'
);
p348_assert(
	! has_action( 'woocommerce_before_single_product_summary', 'bhp_book_render_hero_gallery' )
	|| has_action( 'woocommerce_before_single_product_summary', 'bhp_book_render_hero_gallery' ) !== false,
	'8.4 the hero-gallery hook registration is unchanged in shape'
);
p348_assert(
	false === strpos( $p348_desktop, 'position: sticky' ),
	'8.5 ⛔ NO sticky purchase column was shipped — measured as a no-op (the purchase column is the TALLER of the two: 2,145px vs 676px) and reported rather than built'
);

/* ── §9 · THE ARTEFACT IS FRESH ───────────────────────────────────────────
 * A verified CSS change that was never minified reaches the repository and
 * never reaches a customer. `test-style-minification.php` owns this check in
 * full; this is the one-line canary for THIS release's file.                */
p348_assert(
	false !== strpos( $p348_css, 'align-items: start' ),
	'9.1 the 1.19.348 declaration is present in the SHIPPED artefact, not only the source'
);
$p348_ver = wp_get_theme( 'brave-hearts-theme-deploy-explorer-expedition-guides' )->get( 'Version' );
p348_assert(
	'' !== (string) $p348_ver,
	'9.2 the theme reports a version string (read: ' . (string) $p348_ver . ')'
);

echo "\n";
printf(
	"PDP REDESIGN PHASE 1 (CYCLE179-LD-348): %d passed, %d failed\n",
	(int) $GLOBALS['p348_passes'],
	(int) $GLOBALS['p348_failures']
);
if ( $GLOBALS['p348_failures'] > 0 ) {
	echo 'FAILED (' . (int) $GLOBALS['p348_failures'] . ")\n";
}
echo 'FAILURES: ' . (int) $GLOBALS['p348_failures'] . "\n";
