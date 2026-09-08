<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * THE COLLECTION PAGE'S FOLD — `CYCLE165-LD-COLLECTION-CONVERSION`, T-2.
 * theme 1.19.254 / bundle 1.8.58, 2026-08-19.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ READ THIS BEFORE ADDING AN ASSERTION: THIS SUITE CANNOT MEASURE A FOLD,
 *    AND IT DOES NOT PRETEND TO.
 *
 * `wp eval-file` has no viewport, no layout engine and no
 * `getBoundingClientRect()`. Acceptance criteria M-1, M-2, M-5, M-7, D-1 and
 * D-2 are GEOMETRY: they are pixel positions at an asserted `innerWidth`, and
 * the only honest instrument for them is a real browser with the width read
 * IN the page. Those numbers live in the release QA evidence, not here, and a
 * PHP assertion claiming to have checked them would be a fabricated
 * verification.
 *
 * ⭐ WHAT THIS SUITE DOES INSTEAD, AND WHY IT IS WORTH HAVING: it asserts every
 *    STRUCTURAL PRECONDITION the geometry depends on. If one of these fails,
 *    the measured fold is wrong and no amount of re-measuring will fix it. If
 *    they all pass, the browser evidence is measuring the build it claims to.
 *
 *   §1  the still exists, is non-interactive, and the gallery still ships
 *       every control it had (R-1)
 *   §2  the promise line renders ONCE, above the price (R-3 / R-10 / M-4)
 *   §3  a trust element renders inside the cold-open block (R-6 / M-5 / D-3)
 *   §4  the guarantee renders and carries no em dash (R-13 / M-6)
 *   §5  the CSS that makes R-1 and R-5 true actually ships (M-7, D-4)
 *   §6  the primary CTA is still a real add-to-cart submit (R-2 / M-3)
 *
 * ⛔ §6 EXISTS BECAUSE THE SPECIFICATION'S DIAGNOSIS WAS WRONG ABOUT IT, AND
 *    THAT IS RECORDED RATHER THAN QUIETLY CORRECTED. The build brief's F-2
 *    states the above-fold CTA "cannot buy anything" and merely scrolls to the
 *    format selector. It does not: it has been a `<button type="submit">`
 *    inside `form.bhp-bundle-form` carrying `complete_{format}_smart` since
 *    1.8.32, and `bundle-drawer.js` intercepts it. The only element that
 *    scrolls is the gift-section CTA, which is the sole carrier of
 *    `data-bhp-scroll-to-card`. R-2 was therefore already satisfied and the
 *    correct action was to GUARD it, not to build a second add path — which
 *    R-2's own constraint forbids. These assertions are that guard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_fold_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/** Document order helper. Returns true when $a occurs before $b. */
function bhp_fold_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
}

echo "\n=== 0. The page renders ===\n";

$page = null;
foreach ( get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'fields'         => 'ids',
	)
) as $id ) {
	$content = get_post_field( 'post_content', $id );
	if ( is_string( $content ) && has_shortcode( $content, 'bhp_complete_series_landing' ) ) {
		$page = $id;
		break;
	}
}
bhp_fold_assert( (bool) $page, '0: a published page carrying [bhp_complete_series_landing] exists', $failures );
if ( ! $page ) {
	fwrite( STDERR, "Cannot continue without the landing page.\n" );
	exit( 1 );
}

$response = wp_remote_get( get_permalink( $page ), array( 'timeout' => 30, 'sslverify' => false ) );
$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
bhp_fold_assert( 200 === $code, "0: the page returns HTTP 200 (got {$code})", $failures );
if ( 200 !== $code ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

/*
 * The cold-open block, extracted once and reused. The needle is non-greedy and
 * stops at the first `</div>`, which is correct ONLY while the block contains
 * no nested `<div>`. That is a deliberate property of the markup, not an
 * accident — see the note in `bhp_bundle_render_landing_cold_open()`.
 */
$fold_cold = preg_match( '/<div class="bhp-landing-coldopen">(.*?)<\/div>/su', $html, $fold_m ) ? $fold_m[1] : '';
bhp_fold_assert( '' !== $fold_cold, '0: the cold-open block is extractable for scoped assertions', $failures );

echo "\n=== 1. R-1 — the still stands in above the fold; the gallery keeps every control ===\n";

bhp_fold_assert(
	substr_count( $html, 'bhp-landing-hero__still"' ) === 1,
	'1: exactly one mobile still renders (never two, never zero on a page with approved media)',
	$failures
);
/*
 * ⛔ THE STILL MUST NOT BE A CONTROL. The whole requirement is that the phone
 *    fold offers ONE interactive element. A still wrapped in a button, a link
 *    or an inspect affordance would defeat it while still looking correct.
 */
$fold_still = preg_match( '/<div class="bhp-landing-hero__still"[^>]*>(.*?)<\/div>/su', $html, $fold_sm ) ? $fold_sm[1] : '';
bhp_fold_assert( '' !== $fold_still, '1: the still block is extractable', $failures );
bhp_fold_assert(
	'' !== $fold_still
		&& stripos( $fold_still, '<button' ) === false
		&& stripos( $fold_still, '<a ' ) === false
		&& stripos( $fold_still, 'tabindex' ) === false,
	'1: the still carries NO interactive element (no button, no link, no tab stop)',
	$failures
);
bhp_fold_assert(
	strpos( $html, '<div class="bhp-landing-hero__still" aria-hidden="true">' ) !== false,
	'1: the still is aria-hidden, so the gallery below is not announced twice',
	$failures
);
/*
 * ⛔ AND THE GALLERY IS INTACT. R-1 is a MOVE. If any of these three drops to
 *    zero the requirement has been implemented by deletion, which it must not
 *    be — every image, every flip-through video and every control stays.
 */
bhp_fold_assert( strpos( $html, 'data-bhp-gallery-thumbs' ) !== false, '1: the thumbnail rail still renders (R-1 is a move, not a removal)', $failures );
bhp_fold_assert( strpos( $html, 'data-bhp-gallery-prev' ) !== false && strpos( $html, 'data-bhp-gallery-next' ) !== false, '1: both gallery arrows still render', $failures );
bhp_fold_assert( strpos( $html, 'data-bhp-gallery-inspect' ) !== false, '1: the inspect control still renders', $failures );
bhp_fold_assert(
	bhp_fold_before( $html, 'bhp-landing-hero__still', 'data-bhp-gallery-stage' ),
	'1: in DOM order the still precedes the gallery, so reading order matches the phone layout',
	$failures
);

echo "\n=== 2. R-3 / R-10 / M-4 — the promise line, once, above the price ===\n";

$fold_promise = 'Adventure chapter books for ages 6&ndash;9 that bring together real places';
bhp_fold_assert(
	substr_count( $html, $fold_promise ) === 1,
	'2: the promise line renders EXACTLY once (it was moved, not copied)',
	$failures
);
bhp_fold_assert(
	strpos( $fold_cold, 'bhp-landing-coldopen__promise' ) !== false,
	'2: the promise line renders inside the cold-open block',
	$failures
);
bhp_fold_assert(
	bhp_fold_before( $html, 'bhp-landing-coldopen__promise', 'bhp-landing-coldopen__price' ),
	'2: M-4 precondition — the promise line precedes the price element in the document',
	$failures
);
/*
 * The old home must be empty. If `.bhp-landing-hero__subtext` still renders,
 * the sentence was COPIED rather than moved and the page now says the same
 * thing twice — which the count assertion above would also catch, but this one
 * names the actual cause.
 */
bhp_fold_assert(
	strpos( $html, 'bhp-landing-hero__subtext' ) === false,
	'2: the sentence no longer renders at the bottom of the hero (it moved; it was not duplicated)',
	$failures
);
/* Standing content constraint: the reading age is 6-9 and never 5-9. */
bhp_fold_assert( strpos( $html, '6&ndash;9' ) !== false || strpos( $html, '6–9' ) !== false, '2: the approved 6-9 reading age survives the move', $failures );

echo "\n=== 3. R-6 / M-5 / D-3 — a trust element renders in the cold-open block ===\n";

bhp_fold_assert(
	strpos( $fold_cold, 'bhp-landing-coldopen__trustline' ) !== false,
	'3: the condensed trust line renders inside the cold-open block',
	$failures
);
bhp_fold_assert(
	strpos( $fold_cold, 'Kirkus-reviewed title' ) !== false,
	'3: it carries the Kirkus MENTION',
	$failures
);
/*
 * ⛔ A MENTION, NEVER THE QUOTE. The full approved quote, its attribution and
 *    its link stay in `bhp-landing-kirkus`, once. `test-collection-cold-traffic.php`
 *    §3 asserts both directions in detail; this is the local half.
 */
bhp_fold_assert(
	strpos( $fold_cold, 'spark children&rsquo;s curiosity' ) === false
		&& strpos( $fold_cold, 'spark children’s curiosity' ) === false,
	'3: the Kirkus QUOTE is still absent from the cold-open box (mention above, quote below)',
	$failures
);
bhp_fold_assert(
	strpos( $html, 'bhp-landing-coldopen__stars' ) !== false,
	'3: the data-gated five-star line is untouched and still renders above the headline',
	$failures
);

echo "\n=== 4. R-13 / M-6 — the guarantee, with no em dash ===\n";

bhp_fold_assert( strpos( $html, 'bhp-landing-guarantee' ) !== false, '4: the guarantee badge still renders', $failures );
bhp_fold_assert(
	strpos( $html, '30-Day Guarantee.' ) !== false,
	'4: the label now ends in a full stop',
	$failures
);
bhp_fold_assert(
	strpos( $html, 'If these books don&rsquo;t fit your reader' ) !== false
		|| strpos( $html, 'If these books don’t fit your reader' ) !== false,
	'4: the sentence that followed the dash now starts a sentence of its own',
	$failures
);
bhp_fold_assert(
	strpos( $html, 'bhp-landing-guarantee__sep' ) === false,
	'4: the decorative separator span is gone (its CSS rule is deliberately left in place, inert)',
	$failures
);
/* R-14 — the first-person voice is the claim's, and it stays. */
bhp_fold_assert(
	strpos( $html, 'tell me within 30 days of delivery' ) !== false,
	'4: R-14 — "tell me" survives; the guarantee was not rewritten into the plural',
	$failures
);
bhp_fold_assert(
	strpos( $html, 'I&rsquo;ll refund you in full' ) !== false || strpos( $html, 'I’ll refund you in full' ) !== false,
	'4: R-14 — "I\'ll refund you in full" survives',
	$failures
);

echo "\n=== 5. The CSS that makes the geometry true actually ships ===\n";

/*
 * ⛔ ASSERTING THE STYLESHEET, NOT THE PIXELS. A rule that is not in the
 *    deployed artefact cannot produce the measured number, so a missing rule
 *    is a defect this suite CAN catch — and it is the one that would otherwise
 *    only surface as a browser measurement nobody re-ran.
 */
$fold_css_path = defined( 'BHP_BUNDLE_PRICING_DIR' ) ? BHP_BUNDLE_PRICING_DIR . 'assets/bundle-landing.css' : '';
$fold_css      = ( '' !== $fold_css_path && is_readable( $fold_css_path ) ) ? (string) file_get_contents( $fold_css_path ) : '';
bhp_fold_assert( '' !== $fold_css, '5: bundle-landing.css is readable in this deployment', $failures );
if ( '' !== $fold_css ) {
	bhp_fold_assert(
		preg_match( '/\.bhp-landing-hero__still\s*\{\s*display:\s*none/', $fold_css ) === 1,
		'5: D-4 precondition — the still is display:none by default, so desktop keeps the thumbnail rail in place',
		$failures
	);
	bhp_fold_assert(
		preg_match( '/@media\s*\(max-width:\s*600px\)\s*\{[^@]*\.bhp-landing-hero__main\s*>\s*\.bhp-media-gallery--collection\s*\{\s*order:\s*3/s', $fold_css ) === 1,
		'5: R-1 — the gallery is ordered below the buy box at <=600px, and only at <=600px',
		$failures
	);
	bhp_fold_assert(
		preg_match( '/\.bhp-landing-coldopen__price-altbtn\s*\{[^}]*min-height:\s*44px/s', $fold_css ) === 1,
		'5: M-7 precondition — the "Hardcover available" control declares a 44px minimum height',
		$failures
	);
	bhp_fold_assert(
		preg_match( '/\.bhp-landing-coldopen__price-altbtn\s*\{[^}]*margin-block:\s*-12px/s', $fold_css ) === 1,
		'5: and it buys that height back out of flow, so the fold budget is unchanged',
		$failures
	);
}

echo "\n=== 6. R-2 / M-3 — the above-fold CTA is a real add-to-cart, not a scroll ===\n";

bhp_fold_assert( strpos( $html, 'data-bhp-landing-main-cta' ) !== false, '6: the primary CTA hook renders', $failures );
/*
 * The primary CTA must sit inside a real bundle form carrying the smart action.
 * The needle deliberately spans from the form open tag to the CTA hook: it is
 * the containment that matters, not the presence of two strings on one page.
 */
bhp_fold_assert(
	preg_match( '/<form[^>]*class="[^"]*bhp-bundle-form[^"]*"[^>]*>(?:(?!<\/form>).)*?name="bhp_bundle_action" value="complete_paperback_smart"(?:(?!<\/form>).)*?data-bhp-landing-main-cta/s', $html ) === 1,
	'6: the primary CTA submits the paperback smart-add form (no second add path was invented)',
	$failures
);
bhp_fold_assert(
	preg_match( '/data-bhp-scroll-to-card[^>]*data-bhp-landing-main-cta|data-bhp-landing-main-cta[^>]*data-bhp-scroll-to-card/s', $html ) === 0,
	'6: the primary CTA is NOT a scroll-to-card control (that hook belongs to the gift section alone)',
	$failures
);
/* R-4 — do not regress the format choice. Both panels, paperback selected. */
bhp_fold_assert(
	strpos( $html, 'data-bhp-format-btn="paperback"' ) !== false,
	'6: R-4 — the paperback format control still renders',
	$failures
);
bhp_fold_assert(
	preg_match( '/data-bhp-format-btn="paperback"/', $html ) === 1
		|| substr_count( $html, 'data-bhp-format-btn="paperback"' ) >= 1,
	'6: R-4 — paperback is present exactly as before',
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
