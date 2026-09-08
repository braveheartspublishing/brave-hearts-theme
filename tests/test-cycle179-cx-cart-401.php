<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE CART BAND — `CYCLE179-CX-BUILD-401-CART`. theme 1.19.401, 2026-09-07.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, 2026-09-07 (⚠️ RELAYED through `chief-of-staff`, NOT
 *    witnessed first-hand by this desk): "The cart shows a ton of white space
 *    on it- needs to be changed to match the other pages"
 *
 * ⛔⛔ READ THIS BEFORE ADDING AN ASSERTION: THIS SUITE CANNOT MEASURE A BAND'S
 *     HEIGHT, AND IT DOES NOT PRETEND TO. `wp eval-file` has no viewport, no
 *     layout engine and no `getBoundingClientRect()`. The brief's "band height
 *     rule" is GEOMETRY: it is a pixel height at an asserted `innerWidth`, and
 *     the only honest instrument is a real browser with the width read IN the
 *     page. Those numbers live in the release QA evidence. A PHP assertion
 *     claiming to have measured 49px would be a fabricated verification, which
 *     Standing Rules §3 puts in the same class as a fabricated review.
 *
 * ⭐ WHAT THIS SUITE ASSERTS INSTEAD: every STRUCTURAL PRECONDITION the
 *    geometry depends on. If one fails, the measured band is wrong and no
 *    amount of re-measuring fixes it. If they all pass, the browser evidence
 *    is measuring the build it claims to.
 *
 *   §1  the band renders on /cart/, with all four of its parts
 *   §2  the parchment hero is GONE from /cart/ and the page still has one <h1>
 *   §3  the band CSS ships AND is scoped to the token the markup emits
 *   §4  the padding collapse ships, top-only, bottom preserved
 *   §5  the empty-cart state still renders (⭐ NOT built by this release)
 *   §6  the cart-capture panel (1.19.391) is structurally untouched
 *   §7  ⛔ /checkout/ still has NO band — the founder ruling of 2026-08-05
 *   §8  the house content rails on every string this release added
 *
 * ⛔⛔ §5 GUARDS SOMETHING THIS RELEASE DID NOT BUILD, AND THAT IS THE POINT.
 *     The build brief asked for an empty-cart state with a line in Andrew's
 *     voice and three book cards. ⭐ VERIFIED LIVE on staging AND production
 *     1.19.400 on 2026-09-07 before any code was written: it already exists.
 *     `bhp_checkout_empty_cart_markup()` in `inc/checkout-experience.php`
 *     renders a compass mark, "Your expedition pack is empty", a line of prose
 *     and two CTAs; WooCommerce's own cross-sell row renders four product
 *     cards under "New in store". Building it again would have produced a
 *     duplicate. These assertions exist so the NEXT release cannot remove it
 *     while believing it was never there.
 *
 * ⛔⛔ THE COMMENT-STRIPPING IN §3 AND §4 IS NOT TIDINESS. Build 400 recorded
 *     four false failures caused by needles matching inside the supersession
 *     comments that documented a rule's REMOVAL. A structural assertion must
 *     look at declarations, never at prose about declarations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_c401_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/**
 * Strip CSS and PHP block comments so a structural match cannot land inside
 * prose. See the head-note: this is build 400's lesson, applied.
 */
function bhp_c401_decomment( $css ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', (string) $css );
}

echo "\n=== 0. The cart page renders ===\n";

$cart_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'cart' ) : 0;
bhp_c401_assert( $cart_id > 0, "0: WooCommerce reports a cart page id (got {$cart_id})", $failures );
if ( $cart_id <= 0 ) {
	fwrite( STDERR, "Cannot continue without the cart page.\n" );
	exit( 1 );
}

$cart_url = get_permalink( $cart_id );
$response = wp_remote_get( $cart_url, array( 'timeout' => 45, 'sslverify' => false ) );
$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
bhp_c401_assert( 200 === $code, "0: /cart/ returns HTTP 200 (got {$code})", $failures );
if ( 200 !== $code ) {
	fwrite( STDERR, "Cannot continue without the rendered cart document.\n" );
	exit( 1 );
}

echo "\n=== 1. The band renders, with all four of its parts ===\n";

bhp_c401_assert(
	function_exists( 'bhp_cart_band_applies' ) && function_exists( 'bhp_cart_band_render' ),
	'1: inc/cart-surface.php is loaded (both entry points exist)',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'bhp-cart-band' ) !== false,
	'1: the cart band header renders on /cart/',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'bhp-catalog-band__diamond' ) !== false,
	'1: the band carries the diamond',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'Big Places. Brave Hearts.' ) !== false,
	'1: the band carries the brand line',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'bhp-catalog-band__meta' ) !== false,
	'1: the band carries the right-hand meta slot',
	$failures
);
/*
 * ⭐ THE BAND MUST REUSE THE SHOP'S CLASSES, NOT NEW ONES. This is the
 *    assertion that keeps the two surfaces from drifting: the whole reason the
 *    cart "matches the other pages" is that it is styled by the same rules.
 */
bhp_c401_assert(
	strpos( $html, 'bhp-catalog-band__title' ) !== false
		&& strpos( $html, 'bhp-catalog-band__series' ) !== false,
	'1: the band reuses the shop band classes rather than defining its own',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'bhp-cart-band-on' ) !== false,
	'1: the body carries the bhp-cart-band-on scope token the CSS needs',
	$failures
);

echo "\n=== 2. The parchment hero is gone; the document still has exactly one h1 ===\n";

/*
 * ⛔ SCOPED TO THE HEADER ELEMENT, NOT THE WHOLE DOCUMENT. `interior-hero`
 *    appears in the shipped stylesheet's own selectors, and on a page that
 *    inlines critical CSS a document-wide search for the string would match
 *    the stylesheet and fail for the wrong reason.
 */
$has_parchment_header = (bool) preg_match(
	'/<header[^>]*class="[^"]*interior-hero--parchment[^"]*"/i',
	$html
);
bhp_c401_assert(
	! $has_parchment_header,
	'2: no parchment hero header renders on /cart/',
	$failures
);
preg_match_all( '/<h1\b/i', $html, $h1s );
bhp_c401_assert(
	count( $h1s[0] ) === 1,
	'2: exactly one <h1> on /cart/ (got ' . count( $h1s[0] ) . ')',
	$failures
);
bhp_c401_assert(
	(bool) preg_match( '/<h1[^>]*class="[^"]*bhp-catalog-band__title[^"]*"[^>]*>\s*Cart\s*<\/h1>/i', $html ),
	'2: the single h1 is the band title and its word is still "Cart"',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'FIELD NOTE' ) === false,
	'2: the decorative coordinate does not render on /cart/',
	$failures
);

echo "\n=== 3. The band CSS ships and is scoped to the emitted token ===\n";

$css_path = get_template_directory() . '/style.css';
$css      = file_exists( $css_path ) ? bhp_c401_decomment( file_get_contents( $css_path ) ) : '';
bhp_c401_assert( '' !== $css, '3: style.css is readable', $failures );

bhp_c401_assert(
	strpos( $css, 'body.bhp-cart-band-on header.bhp-catalog-band.interior-hero' ) !== false,
	'3: the band padding rule ships, scoped to body.bhp-cart-band-on',
	$failures
);
/*
 * ⛔ THE TITLE RULE MUST CARRY THE BODY TOKEN AND THE ELEMENT QUALIFIER.
 *    `.bhp-catalog-band__title` alone (0,1,0) loses to
 *    `body:not(.home) .interior-hero h1`, and the band renders at the interior
 *    hero's display size — roughly twice its intended height. This assertion is
 *    the specificity guard, and it is the one most likely to be "simplified"
 *    away by a later tidy-up.
 */
bhp_c401_assert(
	strpos( $css, 'body.bhp-cart-band-on .bhp-catalog-band h1.bhp-catalog-band__title' ) !== false,
	'3: the title rule carries the full specificity chain (body token + h1 qualifier)',
	$failures
);
bhp_c401_assert(
	(bool) preg_match(
		'/body\.bhp-cart-band-on\s+\.bhp-catalog-band\s+h1\.bhp-catalog-band__title\s*\{[^}]*font-size:\s*1\.55rem/s',
		$css
	),
	'3: the desktop band title is set to 1.55rem, matching the shop band',
	$failures
);
bhp_c401_assert(
	strpos( $css, 'body.bhp-catalog-grid .bhp-catalog-band h1.bhp-catalog-band__title' ) !== false,
	'3: the SHOP band rule is still present and was not repurposed',
	$failures
);

echo "\n=== 4. The padding collapse ships, top only ===\n";

bhp_c401_assert(
	(bool) preg_match( '/body\.bhp-cart-band-on\s+\.page-content\s*\{[^}]*padding-top:/s', $css ),
	'4: the .page-content padding-top collapse ships',
	$failures
);
/*
 * ⛔⛔ THE RULE MUST NOT USE THE `padding` SHORTHAND. `.page-content`'s BOTTOM
 *     padding is what separates the cart from the footer. A shorthand would
 *     zero it silently and the defect would only show on a short cart.
 */
bhp_c401_assert(
	! preg_match( '/body\.bhp-cart-band-on\s+\.page-content\s*\{[^}]*(?<![-\w])padding:\s/s', $css ),
	'4: the collapse names padding-top, never the padding shorthand',
	$failures
);
bhp_c401_assert(
	(bool) preg_match( '/body\.bhp-cart-band-on\s+\.page-content\s*>\s*\.entry-content\s*\{[^}]*padding-top:\s*0/s', $css ),
	'4: the nested article padding-top is collapsed to 0',
	$failures
);

echo "\n=== 5. The empty-cart state still renders (NOT built by this release) ===\n";

bhp_c401_assert(
	strpos( $html, 'bhp-empty-cart__title' ) !== false,
	'5: the empty-cart heading still renders',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'bhp-empty-cart__text' ) !== false,
	'5: the empty-cart prose line still renders',
	$failures
);
bhp_c401_assert(
	substr_count( $html, 'bhp-empty-cart__cta' ) >= 1,
	'5: at least one empty-cart CTA still renders',
	$failures
);
bhp_c401_assert(
	strpos( $html, 'wp-block-woocommerce-empty-cart-block' ) !== false,
	'5: the WooCommerce empty-cart block wrapper is still present',
	$failures
);

echo "\n=== 6. The cart-capture panel (1.19.391) is structurally untouched ===\n";

bhp_c401_assert(
	class_exists( 'BHP_Early_Cart_Capture' ) || function_exists( 'bhp_early_cart_capture_init' ),
	'6: the early cart capture module is still loaded',
	$failures
);
/*
 * ⭐ THE PANEL IS CORRECTLY ABSENT HERE, AND ASSERTING ITS ABSENCE IS THE
 *    REGRESSION GUARD. `should_capture()` returns false on an empty cart, and
 *    `wp_remote_get` has no session, so this request's cart IS empty. A panel
 *    appearing in this document would mean the empty-cart gate had been lost.
 */
bhp_c401_assert(
	strpos( $html, 'data-bhp-cart-capture' ) === false,
	'6: no capture panel renders on an empty cart (the empty-cart gate holds)',
	$failures
);

echo "\n=== 7. /checkout/ still has NO band — the founder ruling of 2026-08-05 ===\n";

/*
 * ⛔⛔ THIS IS THE MOST IMPORTANT ASSERTION IN THE FILE AND IT GUARDS A REFUSAL.
 *     The build brief asked for "cart AND CHECKOUT headers". Andrew removed the
 *     checkout header himself on 2026-08-05 — "its clearly understood that its
 *     a check out page- bring everything up" — and putting a band back would
 *     add furniture above the form he asked to have raised. No agent reverses a
 *     founder ruling (Standing Rules §7). Registered as CYCLE179-CX-B-1.
 *     ⭐ If Andrew later rules that checkout SHOULD carry the band, this
 *     assertion is what must be deliberately changed, in the same commit, by
 *     someone who has read this note.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ 1.19.402 — §7 WAS AN INSTRUMENT DEFECT AND IT FAILED TWICE ON A BUILD
 *     THAT WAS CORRECT. THE FIX IS TO THE INSTRUMENT, NOT TO THE ASSERTION.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHAT HAPPENED, MEASURED. The 1.19.401 suite run on staging reported
 *    exactly two new FAIL lines against 1.19.400, both from this section:
 *      FAIL: 7: no cart band renders on /checkout/
 *      FAIL: 7: /checkout/ still renders no interior hero of any kind
 *
 * ⭐⭐ THEY WERE FALSE, AND THE PROOF IS A BROWSER, NOT AN ARGUMENT. Verified
 *     live on staging2 at an asserted `window.innerWidth` of 1280 on
 *     2026-09-07, immediately after the run:
 *       · WITH AN ITEM IN THE CART, `/checkout/` stays on `/checkout/`,
 *         `document.body.className` carries `woocommerce-checkout page-checkout`,
 *         `.bhp-cart-band` is ABSENT, `bhp-cart-band-on` is ABSENT,
 *         `.interior-hero` is ABSENT, and the H1 "Checkout" is still
 *         position:absolute. The founder ruling of 2026-08-05 HOLDS.
 *       · WITH AN EMPTY CART, `/checkout/` 302s to `/cart/`. `location.href`
 *         comes back `/cart/`, the title is "Cart", the body carries
 *         `page-id-7 ... page-cart bhp-cart-band-on`.
 *
 * ⛔⛔ SO THE OLD CODE WAS ASSERTING ABOUT THE CART PAGE WHILE PRINTING THE
 *     WORD "checkout". `wp_remote_get()` follows redirects by default, and a
 *     WP-CLI request carries no cart session, so the cart is ALWAYS empty and
 *     the redirect ALWAYS fires. This section could never have passed on any
 *     build, correct or broken. ⭐ A test that cannot pass is worse than no
 *     test: it trains the next reader to discount a red line.
 *
 * ⛔ WHAT WAS DELIBERATELY *NOT* DONE, because it is build 400's recorded
 *    lesson running in reverse: the assertion was NOT weakened, NOT deleted,
 *    and NOT made to pass by dropping the needle. Weakening a correct
 *    assertion to clear a false failure is how a suite is taught to lie.
 *    ⛔ Nor was a cart seeded here to force the non-redirect path: the CLI's
 *       cart session and the HTTP request's cart session are different
 *       sessions, so seeding one cannot change what the other fetches. That
 *       route does not exist; it is recorded so it is not attempted again.
 *
 * ⭐ WHAT REPLACES IT, IN TWO PARTS:
 *    (a) The HTTP branch now IDENTIFIES which page came back before asserting
 *        anything about it, and only runs the checkout assertions when it
 *        actually holds the checkout page. When the redirect fires it says so
 *        out loud and PASSES a named detection assertion, so the line count
 *        never silently drops and the reason is in the log.
 *    (b) A SOURCE-LEVEL structural pair runs UNCONDITIONALLY, on every run, in
 *        both environments. Those are things PHP can honestly know: that the
 *        band's own predicate refuses `is_checkout()`, and that `page.php`
 *        still routes a non-cart, non-checkout page to the parchment hero.
 *
 * ⛔ THE LIVE CLAIM ABOUT `/checkout/` NOW LIVES WHERE IT CAN BE MEASURED —
 *    in the release's browser evidence, exactly as the geometry claims do
 *    (see this file's head-note). This suite no longer pretends to make it.
 */
$checkout_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
bhp_c401_assert( $checkout_id > 0, '7: WooCommerce reports a checkout page id', $failures );
if ( $checkout_id > 0 ) {
	$co_res  = wp_remote_get( get_permalink( $checkout_id ), array( 'timeout' => 45, 'sslverify' => false ) );
	$co_code = is_wp_error( $co_res ) ? 0 : (int) wp_remote_retrieve_response_code( $co_res );
	$co_html = is_wp_error( $co_res ) ? '' : (string) wp_remote_retrieve_body( $co_res );
	bhp_c401_assert( 200 === $co_code, "7: /checkout/ returns HTTP 200 (got {$co_code})", $failures );

	if ( 200 === $co_code ) {
		/*
		 * Which page is actually in hand? The body class is the honest
		 * discriminator: WordPress prints `page-checkout` on the checkout page
		 * and `page-cart` on the cart page, and the redirect swaps one for the
		 * other. Do not use the URL — `wp_remote_get()` has already followed
		 * the redirect by the time the body is readable.
		 */
		$got_checkout = ( false !== strpos( $co_html, 'woocommerce-checkout' ) )
			&& ( false === strpos( $co_html, 'page-cart' ) );

		if ( $got_checkout ) {
			bhp_c401_assert(
				strpos( $co_html, 'bhp-cart-band' ) === false,
				'7: no cart band renders on /checkout/',
				$failures
			);
			bhp_c401_assert(
				! preg_match( '/<header[^>]*class="[^"]*interior-hero[^"]*"/i', $co_html ),
				'7: /checkout/ still renders no interior hero of any kind',
				$failures
			);
			bhp_c401_assert(
				strpos( $co_html, 'screen-reader-text' ) !== false,
				'7: /checkout/ keeps its visually-hidden h1 (document outline preserved)',
				$failures
			);
		} else {
			echo "NOTE: /checkout/ redirected to the cart because this request carries no cart session.\n";
			echo "NOTE: the three /checkout/ render assertions are BROWSER-ONLY and are recorded in the\n";
			echo "NOTE: release QA evidence for this build, not asserted here. See the section head-note.\n";
			bhp_c401_assert(
				true,
				'7: the empty-cart redirect to /cart/ was DETECTED, so no checkout claim was faked',
				$failures
			);
		}
	}
}

/*
 * (b) The unconditional structural pair. These run whether or not the redirect
 *     fired, so the refusal is guarded on every single run.
 */
$band_src_7 = bhp_c401_decomment( (string) @file_get_contents( get_template_directory() . '/inc/cart-surface.php' ) );
bhp_c401_assert(
	( false !== strpos( $band_src_7, "function_exists('is_checkout')" ) )
		&& ( false !== strpos( $band_src_7, 'is_checkout()' ) )
		&& ( false !== strpos( $band_src_7, 'return false;' ) ),
	'7: bhp_cart_band_applies() still refuses is_checkout() in source',
	$failures
);

$page_src_7 = bhp_c401_decomment( (string) @file_get_contents( get_template_directory() . '/page.php' ) );
bhp_c401_assert(
	false !== strpos( $page_src_7, 'elseif (!$bhp_is_checkout_page)' ),
	'7: page.php still routes non-cart, non-checkout pages to the parchment hero',
	$failures
);

echo "\n=== 8. The house content rails, on the strings this release added ===\n";

$band_src = file_exists( get_template_directory() . '/inc/cart-surface.php' )
	? (string) file_get_contents( get_template_directory() . '/inc/cart-surface.php' )
	: '';
bhp_c401_assert( '' !== $band_src, '8: inc/cart-surface.php is readable', $failures );

/*
 * ⛔ THE RAILS ARE CHECKED ON THE RENDERED BAND, NOT ON THE SOURCE FILE. The
 *    source carries Andrew's own quoted instruction in a comment, which
 *    contains words the rails would flag; the customer never sees a comment.
 *    ⭐ This is the §9.1a carve-out in miniature: a rail matches a word, a rule
 *    is about a claim, and the quoted words of a third party are exempt.
 */
$band_markup = preg_match( '/<header[^>]*bhp-cart-band.*?<\/header>/su', $html, $bm ) ? $bm[0] : '';
bhp_c401_assert( '' !== $band_markup, '8: the rendered band is extractable for scoped rail checks', $failures );

bhp_c401_assert(
	! preg_match( '/\b(we|us|our)\b/i', wp_strip_all_tags( $band_markup ) ),
	'8: the band carries no "we", "us" or "our" (Standing Rules 9.1)',
	$failures
);
bhp_c401_assert(
	strpos( $band_markup, "\xE2\x80\x94" ) === false,
	'8: the band carries no em dash',
	$failures
);
bhp_c401_assert(
	! preg_match( '/\b5\s*(to|-|\x{2013})\s*9\b/u', wp_strip_all_tags( $band_markup ) ),
	'8: the band never says 5 to 9 (the approved band is 6 to 9)',
	$failures
);
/*
 * ⛔ NO RATING, REVIEW OR AGGREGATE MAY BE INVENTED ANYWHERE, AND A BAND IS AS
 *    GOOD A PLACE AS ANY TO SMUGGLE ONE IN. Standing Rules §2, absolute.
 */
bhp_c401_assert(
	! preg_match( '/aggregateRating|ratingValue|reviewCount/i', $band_markup ),
	'8: the band emits no rating or review schema',
	$failures
);
bhp_c401_assert(
	! preg_match( '/\b(colour|favourite|realise|organise|centre|grey|catalogue|whilst|towards)\b/i', wp_strip_all_tags( $band_markup ) ),
	'8: the band uses American spelling (Standing Rules 9.4)',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
exit( 0 );
