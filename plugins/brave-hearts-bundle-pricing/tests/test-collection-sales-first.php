<?php
/**
 * Brave Hearts — THE SALES SECTION IS FIRST ON /complete-collection/ (1.8.28).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-collection-sales-first.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, with screenshots (⛔ RELAYED through the
 * Chief of Staff; NOT witnessed first-hand by the agent that wrote this
 * file), verbatim:
 *
 *   "we need the actual sales section above the fold - these need to
 *    switch"
 *
 * The sales unit — book gallery + "BEST VALUE" purchase card with the
 * format pills, the price rows, the Shipping row, the Activity Book row
 * and the primary CTA — now renders ABOVE the narrative hero ("Three Big
 * Adventures. One Complete Collection." and the travel copy) inside the
 * same `.bhp-landing-hero__inner`.
 *
 * ⛔ IT WAS A MOVE, NOT A COPY, AND THE INSTRUCTION WAS "NOTHING
 *    DELETED". That is the half of this change a source diff proves
 *    poorly and a rendered document proves well, so §2 below asserts
 *    ZERO LOSS against the actually-rendered page: every price, every
 *    FREE row, both format panels, both CTAs and the narrative copy must
 *    all still be present, and present exactly ONCE where they were once
 *    before.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It fetches the REAL rendered `/complete-collection/` document over HTTP
 * from this environment and asserts DOCUMENT ORDER and PRESENCE. It
 * therefore CANNOT prove:
 *
 *   · that the sales unit is visually above the fold at any given
 *     viewport — that is a painted-box measurement, and it is recorded
 *     from a real browser in the release evidence, never inferred here;
 *   · that the format toggle, the CTA or the exit-intent modal BEHAVE
 *     correctly — those are JS behaviours, verified in a real browser;
 *   · anything about production, when run on staging.
 *
 * Claiming any of those from this file would be a fabricated
 * verification, which the standing rules put in the same class as a
 * fabricated review.
 *
 * ⛔ READ-ONLY. No post, option, product, price, coupon, stock level,
 *    shipping, tax, payment or checkout setting is read or written. No
 *    cart is built. No order is created.
 *
 * Exits non-zero on any failure.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_csf_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Locate the landing page by the shortcode it carries, not by a
 * hardcoded slug or ID — the slug is content, and a content edit must not
 * silently turn this suite into a no-op that reports success.
 */
function bhp_csf_find_landing_page() {
	$q = new WP_Query(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			's'                      => 'bhp_complete_series_landing',
			'posts_per_page'         => 5,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	foreach ( $q->posts as $p ) {
		if ( has_shortcode( $p->post_content, 'bhp_complete_series_landing' ) ) {
			return $p;
		}
	}
	return null;
}

echo "\n=== 0. The page exists and renders ===\n";

$page = bhp_csf_find_landing_page();
bhp_csf_assert( $page instanceof WP_Post, '0: a published page carrying [bhp_complete_series_landing] exists', $failures );

if ( ! $page instanceof WP_Post ) {
	echo "\nCannot continue without the landing page.\n";
	exit( 1 );
}

$url = get_permalink( $page->ID );
echo "    URL under test: {$url}\n";

$res  = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
$html = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res );

bhp_csf_assert( 200 === $code, "0: the page returns HTTP 200 (got {$code})", $failures );
bhp_csf_assert( strlen( $html ) > 20000, '0: the rendered document is non-trivial', $failures );

if ( '' === $html ) {
	echo "\nCannot continue without a rendered document.\n";
	exit( 1 );
}

echo "\n=== 1. THE MOVE: the sales unit precedes the narrative ===\n";

$main_at  = strpos( $html, 'bhp-landing-hero__main' );
$intro_at = strpos( $html, 'bhp-landing-hero__intro' );
$card_at  = strpos( $html, 'bhp-landing-card' );
$h1_at    = strpos( $html, 'bhp-landing-hero__title' );

bhp_csf_assert( false !== $main_at, '1: the sales block (.bhp-landing-hero__main) renders', $failures );
bhp_csf_assert( false !== $intro_at, '1: the narrative block (.bhp-landing-hero__intro) renders', $failures );
bhp_csf_assert( false !== $card_at, '1: the purchase card (.bhp-landing-card) renders', $failures );
bhp_csf_assert( false !== $h1_at, '1: the narrative H1 renders', $failures );

/*
 * The load-bearing assertion. Everything else in this file guards against
 * losing something; THIS is the one that encodes Andrew's instruction.
 */
bhp_csf_assert(
	false !== $main_at && false !== $intro_at && $main_at < $intro_at,
	'1: ⭐ the SALES block precedes the NARRATIVE block in the document (they switched)',
	$failures
);
bhp_csf_assert(
	false !== $card_at && false !== $h1_at && $card_at < $h1_at,
	'1: ⭐ the purchase card precedes the H1 headline',
	$failures
);
bhp_csf_assert(
	strpos( $html, 'bhp-landing-hero--sales-first' ) !== false,
	'1: the section carries the sales-first modifier the CSS margins key on',
	$failures
);

echo "\n=== 2. ZERO LOSS: nothing was deleted by the move ===\n";

/*
 * Read the figures from the SAME single source of truth the page renders
 * from, so this suite cannot drift into asserting a stale hardcoded
 * price. If Andrew changes a price in bundle-data.php, this suite follows
 * it instead of failing.
 */
foreach ( array( 'paperback', 'hardcover' ) as $format ) {
	$rule     = bhp_bundle_rules( $format )[3];
	$combined = 3 * bhp_bundle_expected_price( $format );
	$bundle   = $combined - $rule['discount'];

	bhp_csf_assert(
		strpos( $html, '$' . number_format( $bundle, 2 ) ) !== false,
		"2: the {$format} collection price \$" . number_format( $bundle, 2 ) . ' is still on the page',
		$failures
	);
	bhp_csf_assert(
		strpos( $html, '$' . number_format( $combined, 2 ) ) !== false,
		"2: the {$format} struck-through combined price \$" . number_format( $combined, 2 ) . ' is still on the page',
		$failures
	);
	bhp_csf_assert(
		strpos( $html, $rule['save'] ) !== false,
		"2: the {$format} savings badge (\"{$rule['save']}\") is still on the page",
		$failures
	);
	bhp_csf_assert(
		strpos( $html, 'data-bhp-format-btn="' . $format . '"' ) !== false,
		"2: the {$format} format pill is still on the page",
		$failures
	);
	bhp_csf_assert(
		substr_count( $html, 'data-bhp-format-panel="' . $format . '"' ) >= 2,
		"2: both {$format} panels (pricing card + final CTA) are still on the page",
		$failures
	);
	bhp_csf_assert(
		strpos( $html, 'complete_' . $format . '_smart' ) !== false,
		"2: the {$format} smart add-to-cart action is still on the page",
		$failures
	);
}

// The two FREE rows Andrew named explicitly.
bhp_csf_assert( strpos( $html, '<dt>Shipping</dt>' ) !== false, '2: the Shipping price row is still on the page', $failures );
if ( function_exists( 'bhp_bundle_addon_free_with_collection' ) && bhp_bundle_addon_free_with_collection() ) {
	bhp_csf_assert( strpos( $html, '<dt>Activity Book</dt>' ) !== false, '2: the Activity Book price row is still on the page', $failures );
	bhp_csf_assert(
		substr_count( $html, '<dt>Activity Book</dt>' ) === substr_count( $html, '<dt>Shipping</dt>' ),
		'2: the Activity Book row appears exactly as often as the Shipping row it pairs with',
		$failures
	);
} else {
	echo "SKIP: the free-activity-book offer is not deliverable on this environment; its row is correctly absent\n";
}

// The 2-click CTA path: primary button + the checkout-redirect input that
// makes it one tap to /checkout/.
bhp_csf_assert( strpos( $html, 'data-bhp-landing-main-cta' ) !== false, '2: the primary in-card CTA is still on the page', $failures );
bhp_csf_assert( strpos( $html, 'data-bhp-landing-lower-cta' ) !== false, '2: the final-section CTA is still on the page', $failures );
bhp_csf_assert( strpos( $html, 'data-bhp-landing-sticky-cta' ) !== false, '2: the sticky-bar CTA is still on the page', $failures );
/*
 * ⚠ CORRECTED after this suite's first run on staging. The first version
 * asserted the string `bhp_bundle_checkout_redirect`, which is the name of
 * the PHP FUNCTION, not of the field it emits. It FAILED on a correct
 * build. `bhp_bundle_checkout_redirect_input()` (bundle-shortcode.php)
 * emits `<input type="hidden" name="bhp_bundle_redirect" value="checkout">`.
 * Recorded rather than quietly patched: an assertion that guesses a field
 * name is a false failure today and would be a false PASS the day the
 * function is renamed.
 *
 * Asserted by COUNT as well as presence: every `form.bhp-bundle-form` on
 * this page must carry the redirect input, or some CTA silently becomes a
 * 3-click path. Four forms render (two pricing panels, two final-CTA
 * panels) plus the sticky bar = five.
 */
$bhp_csf_forms    = substr_count( $html, 'class="bhp-bundle-form' );
$bhp_csf_redirect = substr_count( $html, 'name="bhp_bundle_redirect" value="checkout"' );
bhp_csf_assert( $bhp_csf_redirect > 0, '2: the checkout-redirect input (the 2-click path) survives', $failures );
bhp_csf_assert(
	$bhp_csf_forms > 0 && $bhp_csf_redirect === $bhp_csf_forms,
	"2: EVERY bundle form carries the redirect input ({$bhp_csf_redirect} inputs / {$bhp_csf_forms} forms)",
	$failures
);

// The narrative copy itself — moved, never rewritten. Locked prose.
bhp_csf_assert( strpos( $html, 'Three Big Adventures.' ) !== false, '2: the narrative headline text is unchanged and still present', $failures );
bhp_csf_assert( strpos( $html, 'deepest ocean trench' ) !== false, '2: the travel copy is unchanged and still present', $failures );
bhp_csf_assert( strpos( $html, 'The Adventures of Charlotte' ) !== false, '2: the eyebrow is still present', $failures );

// Exactly one H1, and it is still the narrative headline.
bhp_csf_assert( substr_count( $html, 'bhp-landing-hero__title' ) === 1, '2: the H1 was moved, not duplicated (exactly one)', $failures );
bhp_csf_assert( substr_count( $html, 'bhp-landing-hero__main' ) === 1, '2: the sales block was moved, not duplicated (exactly one)', $failures );
bhp_csf_assert( substr_count( $html, 'bhp-landing-hero__intro' ) === 1, '2: the narrative block was moved, not duplicated (exactly one)', $failures );
bhp_csf_assert( substr_count( $html, 'data-bhp-pricing-card' ) === 1, '2: the purchase card was moved, not duplicated (exactly one)', $failures );

echo "\n=== 3. In-page anchors resolve ===\n";

/*
 * Before 1.8.28 the gift section's `href="#bhp-landing-pricing-card"` had
 * NO target element anywhere in the document (verified on production
 * 2026-08-05: zero occurrences of the id). It worked only because
 * bundle-landing.js intercepted the click. A dangling fragment is exactly
 * the thing that breaks when a page is re-ordered, so it is asserted here
 * rather than assumed.
 */
if ( preg_match_all( '/href="#([A-Za-z0-9_\-]+)"/', $html, $m ) ) {
	$targets = array_values( array_unique( $m[1] ) );
	foreach ( $targets as $frag ) {
		if ( strpos( $html, 'id="' . $frag . '"' ) !== false ) {
			echo "PASS: 3: in-page anchor #{$frag} resolves to an element with that id\n";
			continue;
		}
		/*
		 * Only fragments this page itself emits are this suite's business.
		 * A theme/plugin header or footer fragment is out of scope and is
		 * reported rather than failed, so the suite does not become a
		 * sitewide anchor auditor by accident.
		 */
		if ( 'bhp-landing-pricing-card' === $frag ) {
			bhp_csf_assert( false, "3: in-page anchor #{$frag} resolves to an element with that id", $failures );
		} else {
			echo "NOTE: 3: #{$frag} has no target in this document (not emitted by this page; out of scope)\n";
		}
	}
}
bhp_csf_assert(
	substr_count( $html, 'id="bhp-landing-pricing-card"' ) === 1,
	'3: the pricing-card anchor target exists exactly once',
	$failures
);
bhp_csf_assert(
	strpos( $html, 'data-bhp-scroll-to-card' ) !== false,
	'3: the JS scroll-to-card hook is unchanged and still present',
	$failures
);

echo "\n=== 4. Version ===\n";
bhp_csf_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.28', '>=' ),
	'4: plugin version is 1.8.28 or later (found ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ')',
	$failures
);
bhp_csf_assert(
	function_exists( 'bhp_bundle_render_landing_hero_sales' ) && function_exists( 'bhp_bundle_render_landing_hero_intro' ),
	'4: both hero helper functions are defined',
	$failures
);

echo "\n============================================================\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
