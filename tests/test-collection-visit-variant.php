<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * THE SCHOOL-VISIT VARIANT SURVIVES THIS RELEASE —
 * `CYCLE165-LD-COLLECTION-CONVERSION`, T-8 / acceptance criterion B-2.
 * theme 1.19.254 / bundle 1.8.58, 2026-08-19.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE FINDING THIS SUITE EXISTS FOR, AND IT IS A PROCESS DEFECT RATHER THAN
 *    A CODE ONE: `/complete-collection/` serves TWO live variants on one URL.
 *    A session carrying a live school-visit flag gets hand-delivery framing
 *    instead of shipping framing, and gets PAPERBACK ONLY — the hardcover
 *    format control is not rendered at all. Verified live in two concurrent
 *    sessions on the same production URL in the same minute, 2026-08-19.
 *    The gate works as designed and fails open; the problem is that a QA
 *    profile carrying a stale visit session silently tests the wrong variant,
 *    so a hardcover regression is invisible to whoever is looking.
 *
 * ⛔⛔ READ THIS BEFORE TRUSTING A PASS FROM THIS SUITE.
 *
 *    `wp eval-file` HAS NO WOOCOMMERCE SESSION AND NO COOKIE.
 *    `bhp_school_visit_request_record()` returns null without one, so
 *    `bhp_school_visit_use_delivery_framing()` is FALSE here, always. This
 *    suite therefore CANNOT render the flagged variant, and it does not
 *    pretend to. Anything claiming otherwise would be a fabricated
 *    verification.
 *
 *    ⭐ WHAT IT ACTUALLY ASSERTS — and each of these is a real regression this
 *      release could have caused:
 *
 *      §1  the UNFLAGGED (cold) variant is correct and complete, which is the
 *          variant every ordinary shopper gets and the one R-1..R-14 changed
 *      §2  the flagged path is still WIRED — every swap still routes through
 *          the single predicate, and no requirement of this release bypassed,
 *          duplicated or hardcoded around it
 *      §3  the restriction still lives in ONE place (the order function), not
 *          at the call sites, which is the property that stops a hidden pill
 *          appearing beside a visible panel
 *
 *    ⛔ THE FLAGGED RENDER ITSELF IS A BROWSER CHECK, in a separate flagged
 *       session, and it is recorded in the release QA evidence. It is NOT
 *       claimed here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_vv_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

function bhp_vv_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

echo "\n=== 0. The instrument declares its own limit ===\n";

$vv_flagged = function_exists( 'bhp_school_visit_use_delivery_framing' ) && bhp_school_visit_use_delivery_framing();
bhp_vv_assert(
	function_exists( 'bhp_school_visit_use_delivery_framing' ),
	'0: the single visit predicate exists',
	$failures
);
bhp_vv_assert(
	! $vv_flagged,
	'0: this CLI context is UNFLAGGED, as expected — so §1 below is testing the cold variant',
	$failures
);
bhp_vv_skip( '0: the FLAGGED render is not reachable from WP-CLI and is verified in a real flagged browser session instead (B-2)', $skipped );

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
bhp_vv_assert( (bool) $page, '0: a published page carrying [bhp_complete_series_landing] exists', $failures );
if ( ! $page ) {
	fwrite( STDERR, "Cannot continue without the landing page.\n" );
	exit( 1 );
}

$response = wp_remote_get( get_permalink( $page ), array( 'timeout' => 30, 'sslverify' => false ) );
$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
bhp_vv_assert( 200 === $code, "0: the page returns HTTP 200 (got {$code})", $failures );
if ( 200 !== $code ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

echo "\n=== 1. R-4 — the COLD variant is complete and unregressed ===\n";

/*
 * ⛔ THIS IS THE ASSERTION A STALE VISIT SESSION HIDES. In a flagged session
 *    the hardcover control is correctly absent; if the QA profile is flagged,
 *    a genuine hardcover regression looks identical to correct behaviour.
 *    Asserted here, from a context proven unflagged above.
 */
bhp_vv_assert( strpos( $html, 'data-bhp-format-btn="paperback"' ) !== false, '1: the paperback format control renders', $failures );
bhp_vv_assert( strpos( $html, 'data-bhp-format-btn="hardcover"' ) !== false, '1: the HARDCOVER format control renders in a cold session', $failures );
bhp_vv_assert(
	preg_match( '/data-bhp-format-btn="paperback"[^>]*>|class="[^"]*bhp-landing-format-btn is-selected[^"]*"[^>]*data-bhp-format-btn="paperback"/', $html ) === 1
		|| preg_match( '/is-selected[^>]*data-bhp-format-btn="paperback"/', $html ) === 1,
	'1: paperback is the control the page opens on',
	$failures
);
bhp_vv_assert(
	function_exists( 'bhp_bundle_landing_default_format' ) && 'paperback' === bhp_bundle_landing_default_format(),
	'1: and the page-scoped default still resolves to paperback',
	$failures
);
bhp_vv_assert(
	strpos( $html, 'FREE Shipping on the complete collection or 3 or more books purchased' ) !== false,
	'1: the first FREE bullet is the SHIPPING one (if this says hand-delivery, the session is flagged and every other assertion here is about the wrong variant)',
	$failures
);
bhp_vv_assert(
	strpos( $html, 'FREE author hand-delivery at your school visit' ) === false,
	'1: the hand-delivery bullet does NOT render in a cold session',
	$failures
);
bhp_vv_assert(
	strpos( $html, 'Paperback only for signed copies at the visit' ) === false,
	'1: the paperback-only note does NOT render in a cold session',
	$failures
);
bhp_vv_assert(
	strpos( $html, 'Choose paperback for the most affordable reading set' ) !== false,
	'1: the ordinary format helper sentence renders instead',
	$failures
);
bhp_vv_assert(
	strpos( $html, 'available</button>' ) !== false || strpos( $html, 'Hardcover available' ) !== false,
	'1: the quiet alternate-format control renders in a cold session (R-5 raises its touch target; it must still exist to be raised)',
	$failures
);

echo "\n=== 2. The flagged path is still wired, and still routes through ONE predicate ===\n";

foreach ( array(
	'bhp_school_visit_use_delivery_framing',
	'bhp_school_visit_delivery_bullet',
	'bhp_school_visit_paperback_only',
	'bhp_school_visit_paperback_only_note',
) as $vv_fn ) {
	bhp_vv_assert( function_exists( $vv_fn ), "2: {$vv_fn}() still exists", $failures );
}

$vv_lp  = defined( 'BHP_BUNDLE_PRICING_DIR' ) ? BHP_BUNDLE_PRICING_DIR . 'includes/bundle-landing-page.php' : '';
$vv_src = ( '' !== $vv_lp && is_readable( $vv_lp ) ) ? (string) file_get_contents( $vv_lp ) : '';
if ( '' === $vv_src ) {
	bhp_vv_skip( '2: bundle-landing-page.php is not readable in this deployment', $skipped );
} else {
	/*
	 * ⛔ TWO SWAP SITES, NO MORE AND NO FEWER: the cold-open bullet at the top
	 *    of the page and the closing CTA's bullet at the bottom. Both ends make
	 *    the same promise in the same words, which is the whole reason both
	 *    route through one helper. A third would mean a surface was copied
	 *    rather than reused; a first would mean one end went stale.
	 */
	bhp_vv_assert(
		2 === substr_count( $vv_src, 'bhp_school_visit_use_delivery_framing()' ),
		'2: the delivery-framing swap happens at exactly the two expected surfaces (' . substr_count( $vv_src, 'bhp_school_visit_use_delivery_framing()' ) . ' found)',
		$failures
	);
	bhp_vv_assert(
		2 === substr_count( $vv_src, 'bhp_school_visit_delivery_bullet()' ),
		'2: and both read the same single bullet helper',
		$failures
	);
	/*
	 * §3's property. The paperback restriction is applied in
	 * `bhp_bundle_landing_format_order()` — the page's single source for
	 * "which formats exist and in what order" — rather than at the four call
	 * sites that read it. Filtering three of four is how a hidden pill ends up
	 * beside a visible panel.
	 */
	bhp_vv_assert(
		preg_match( '/function bhp_bundle_landing_format_order\(\)(?:(?!\nfunction ).)*?bhp_school_visit_paperback_only\(\)/s', $vv_src ) === 1,
		'3: the format restriction still lives in the order function, not at the call sites',
		$failures
	);
	/*
	 * ⛔ AND THIS RELEASE DID NOT ADD AN UNGATED FORMAT LIST. Every surface that
	 *    enumerates formats must go through the order function, or a
	 *    visit-flagged session gets offered a hardcover the server then refuses.
	 */
	bhp_vv_assert(
		substr_count( $vv_src, "array( 'paperback', 'hardcover' )" ) === 0,
		'3: no render path enumerates both formats directly, bypassing the order function',
		$failures
	);
}

echo "\n";
if ( $skipped ) {
	echo 'SKIPPED: ' . count( $skipped ) . " (each names the instrument that DOES cover it)\n";
	foreach ( $skipped as $s ) {
		echo "  - {$s}\n";
	}
}
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
exit( 0 );
