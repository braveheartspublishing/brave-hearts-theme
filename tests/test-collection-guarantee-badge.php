<?php
/**
 * Brave Hearts — THE 30-DAY GUARANTEE BADGE (theme 1.19.228 / bundle 1.8.46).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-collection-guarantee-badge.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE161-LD-TYP-AND-GUARANTEE`, build 2
 * ═══════════════════════════════════════════════════════════════════════
 *
 * A small trust block below the Complete Collection's buy button, quoting
 * the founder-approved 30-Day Guarantee in short form and linking to the
 * Refund and Returns Policy page.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE ONE THING THIS SUITE EXISTS FOR ABOVE ALL OTHERS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * A refund promise is a claim a customer can hold the company to. This
 * suite asserts that the badge's link RESOLVES to a real, published policy
 * page (HTTP 200, not a 404 and not an empty href) and that the badge does
 * not overclaim relative to what the policy says — specifically that it
 * keeps the "of delivery" clock anchor the policy sets.
 *
 * ⚠⚠ WHAT IT DELIBERATELY DOES *NOT* ASSERT, AND WHY. It does NOT require
 *    the policy page's body to contain the guarantee text. On 2026-08-14
 *    the guarantee section is LIVE ON PRODUCTION (page 10, post_modified
 *    2026-08-14 12:55:42) and ABSENT FROM STAGING (page 10, post_modified
 *    2026-08-03, still carrying the superseded "Returns and Buyer's
 *    Remorse … we generally do not accept returns" section). Asserting it
 *    would make this suite fail on staging for a CONTENT-PARITY reason
 *    rather than a code reason, and a suite that fails for a reason it
 *    cannot fix teaches everyone to ignore it. The gap is instead REPORTED
 *    by section 4 below as an explicit, visible, non-failing check.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads a
 *    rendered document. It proves presence, absence, cardinality and
 *    document order. It CANNOT prove geometry — that the badge sits below
 *    the button on screen, or that the fold measurements are unchanged.
 *    Those need a real browser with `window.innerWidth` asserted and live
 *    in the CYCLE161 QA evidence instead. They are not claimed here.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_gb_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/** Document order helper. Returns true when $a occurs before $b. */
function bhp_gb_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
}

echo "\n=== 0. The plugin exposes the guarantee functions ===\n";

foreach ( array(
	'bhp_bundle_render_landing_guarantee',
	'bhp_bundle_guarantee_policy_url',
) as $gb_fn ) {
	bhp_gb_assert( function_exists( $gb_fn ), "0: {$gb_fn}() exists", $failures );
}
if ( ! function_exists( 'bhp_bundle_guarantee_policy_url' ) ) {
	fwrite( STDERR, "Cannot continue: the bundle plugin is not the 1.8.46 build.\n" );
	exit( 1 );
}

echo "\n=== 1. The policy link resolves to a real, published page ===\n";

$gb_url = bhp_bundle_guarantee_policy_url();
bhp_gb_assert( '' !== trim( (string) $gb_url ), '1: the policy URL is not empty', $failures );

$gb_resp = wp_remote_get( $gb_url, array( 'timeout' => 30, 'sslverify' => false ) );
$gb_code = is_wp_error( $gb_resp ) ? 0 : (int) wp_remote_retrieve_response_code( $gb_resp );
bhp_gb_assert( 200 === $gb_code, "1: the policy page returns HTTP 200 (got {$gb_code}) — {$gb_url}", $failures );

/*
 * ⛔ IT MUST BE RESOLVED, NOT GUESSED. The function's first choice is
 *    WooCommerce's own `woocommerce_refund_returns_page_id`. If that option
 *    is set, the URL it produces has to be that page's permalink — a
 *    hardcoded fallback silently winning is exactly the failure mode the
 *    three-step lookup exists to avoid.
 */
$gb_page_id = (int) get_option( 'woocommerce_refund_returns_page_id', 0 );
if ( $gb_page_id > 0 && 'publish' === get_post_status( $gb_page_id ) ) {
	bhp_gb_assert(
		$gb_url === get_permalink( $gb_page_id ),
		"1: the URL is WooCommerce's configured refund page (id {$gb_page_id}), not the hardcoded fallback",
		$failures
	);
} else {
	echo "SKIP: 1: woocommerce_refund_returns_page_id is unset on this environment\n";
}

echo "\n=== 2. The badge renders on the collection page, below the buy button ===\n";

$gb_page = get_page_by_path( 'complete-collection' );
if ( ! $gb_page ) {
	fwrite( STDERR, "Cannot continue without the landing page.\n" );
	exit( 1 );
}
$gb_pres  = wp_remote_get( get_permalink( $gb_page ), array( 'timeout' => 30, 'sslverify' => false ) );
$gb_pcode = is_wp_error( $gb_pres ) ? 0 : (int) wp_remote_retrieve_response_code( $gb_pres );
$html     = is_wp_error( $gb_pres ) ? '' : (string) wp_remote_retrieve_body( $gb_pres );
bhp_gb_assert( 200 === $gb_pcode, "2: the collection page returns HTTP 200 (got {$gb_pcode})", $failures );
if ( 200 !== $gb_pcode ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

/*
 * Two format panels are in the DOM at once (one `hidden`), exactly as the
 * price block is, so the badge renders twice and exactly twice. A third
 * copy would mean it had leaked outside the panel.
 */
bhp_gb_assert(
	substr_count( $html, 'class="bhp-landing-guarantee"' ) === 2,
	'2: exactly two guarantee blocks render — one per format panel, one visible',
	$failures
);
bhp_gb_assert(
	bhp_gb_before( $html, 'data-bhp-landing-main-cta', 'bhp-landing-guarantee' ),
	'2: the guarantee renders AFTER the primary CTA in document order (below the button)',
	$failures
);
/*
 * ⛔ THE FOLD REGRESSION GUARD. Everything CYCLE160 measured above the fold
 *    — the cold-open price block and the buy button — must still precede
 *    the guarantee. If a future edit moves the badge above either of them
 *    it moves the button down, and this is the cheapest place to catch it.
 */
bhp_gb_assert(
	bhp_gb_before( $html, 'bhp-landing-coldopen__price', 'bhp-landing-guarantee' ),
	'2: the guarantee is still BELOW the cold-open price block (fold guard)',
	$failures
);

echo "\n=== 3. The claim, and the words that make it true ===\n";

bhp_gb_assert(
	strpos( $html, '<span class="bhp-landing-guarantee__label">30-Day Guarantee</span>' ) !== false,
	'3: the badge is labelled "30-Day Guarantee"',
	$failures
);
/*
 * ⛔ THE CLOCK ANCHOR IS THE ASSERTION THAT MATTERS. The policy grants the
 *    refund window "within 30 days OF DELIVERY". A badge that says only
 *    "within 30 days" starts the clock at an unstated point and can be read
 *    as from the order date — a wider promise than the policy makes. The
 *    build brief's short form omitted it; it was restored, and this
 *    assertion is what stops it being dropped again.
 */
bhp_gb_assert(
	strpos( $html, 'within 30 days of delivery' ) !== false,
	'3: the refund window is anchored to DELIVERY, matching the policy',
	$failures
);
bhp_gb_assert(
	strpos( $html, 'refund you in full' ) !== false,
	'3: the badge states a full refund',
	$failures
);
bhp_gb_assert(
	strpos( $html, 'Keep the books.' ) !== false,
	'3: the badge states the books need not be returned',
	$failures
);
/*
 * ⛔ NO NUMBER OTHER THAN THE WINDOW. The badge must not restate a price, a
 *    saving or a percentage — the price block 60px above owns those, and a
 *    second statement of them is the duplication 1.8.39 and 1.8.41 spent
 *    two releases removing from this card.
 */
if ( preg_match( '/<p class="bhp-landing-guarantee">(.*?)<\/p>/su', $html, $gb_m ) ) {
	bhp_gb_assert(
		strpos( $gb_m[1], '$' ) === false && strpos( $gb_m[1], '%' ) === false,
		'3: the badge carries no price and no percentage',
		$failures
	);
	bhp_gb_assert(
		strpos( $gb_m[1], 'bhp-landing-guarantee__link' ) !== false
		&& strpos( $gb_m[1], 'href="' ) !== false,
		'3: the badge carries the policy link',
		$failures
	);
} else {
	bhp_gb_assert( false, '3: the badge markup could not be isolated', $failures );
}

/*
 * ⛔ NO NEW COLOUR. The brief required existing tokens only. Assert against
 *    the stylesheet that actually ships rather than trusting the diff.
 */
$gb_css_path = dirname( __DIR__, 3 ) . '/plugins/brave-hearts-bundle-pricing/assets/bundle-landing.css';
if ( ! is_readable( $gb_css_path ) ) {
	$gb_css_path = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/assets/bundle-landing.css';
}
$gb_css = is_readable( $gb_css_path ) ? (string) file_get_contents( $gb_css_path ) : '';
bhp_gb_assert( '' !== $gb_css, '3: bundle-landing.css is readable', $failures );
if ( '' !== $gb_css && preg_match_all( '/\.bhp-landing-guarantee[^{]*\{([^}]*)\}/s', $gb_css, $gb_rules ) ) {
	$gb_block = implode( "\n", $gb_rules[1] );
	bhp_gb_assert(
		preg_match( '/#[0-9a-fA-F]{3,8}\b/', $gb_block ) !== 1
		&& stripos( $gb_block, 'rgb(' ) === false
		&& stripos( $gb_block, 'hsl(' ) === false,
		'3: the guarantee CSS introduces no raw colour value — tokens only',
		$failures
	);
} else {
	bhp_gb_assert( false, '3: the guarantee CSS rules could not be isolated', $failures );
}

echo "\n=== 4. Policy-page parity — REPORTED, never failed (see the header) ===\n";

$gb_policy_html = is_wp_error( $gb_resp ) ? '' : (string) wp_remote_retrieve_body( $gb_resp );
$gb_has_section = stripos( $gb_policy_html, '30-Day Guarantee' ) !== false;
$gb_has_keep    = stripos( $gb_policy_html, 'keep the books' ) !== false;
if ( $gb_has_section && $gb_has_keep ) {
	echo "PASS: 4: the linked policy page carries the guarantee section AND the keep-the-books sentence\n";
} else {
	echo "⚠ REPORT (not a failure): the linked policy page at {$gb_url} is MISSING "
		. ( $gb_has_section ? '' : 'the "30-Day Guarantee" heading' )
		. ( ( ! $gb_has_section && ! $gb_has_keep ) ? ' and ' : '' )
		. ( $gb_has_keep ? '' : 'the "keep the books" sentence' )
		. ". The badge is linking to copy that does not yet state what the badge promises."
		. " This is a CONTENT-PARITY gap on this environment, not a code defect — page copy is the owner's."
		. " On production 2026-08-14 the section is present. ESCALATE; do not silently edit the page.\n";
}

echo "\n=== 5. Nothing that must not move, moved ===\n";

/* The schema layer is not this build's business and must be untouched. */
$gb_schema = '';
if ( preg_match( '/<script[^>]*class="rank-math-schema"[^>]*>(.*?)<\/script>/su', $html, $gb_sm ) ) {
	$gb_schema = $gb_sm[1];
}
bhp_gb_assert(
	strpos( $gb_schema, 'aggregateRating' ) === false && strpos( $gb_schema, '"review"' ) === false,
	'5: no aggregateRating and no review schema (never-invent rule)',
	$failures
);
bhp_gb_assert(
	strpos( $gb_schema, 'guarantee' ) === false && strpos( $gb_schema, 'MerchantReturnPolicy' ) === false,
	'5: the badge emitted NO new structured data',
	$failures
);
/* The buy button and the price block still render, once each per format. */
bhp_gb_assert(
	substr_count( $html, 'data-bhp-landing-main-cta' ) === 2,
	'5: both primary CTA buttons still render (unchanged)',
	$failures
);
bhp_gb_assert(
	substr_count( $html, 'class="bhp-landing-coldopen__price"' ) === 2,
	'5: both cold-open price blocks still render (unchanged)',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
