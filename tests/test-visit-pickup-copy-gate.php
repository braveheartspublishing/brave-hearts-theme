<?php
/**
 * CYCLE164-LD-PICKUP-COPY-GATE — the visit copy gate and the §9.1 voice rule.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-visit-pickup-copy-gate.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔ THE CONTROL PATH. An ordinary paying customer — someone who has never
 *      touched a school link — must still get the "Printed Just for You" notice
 *      on checkout, unchanged. A regression there is worse than the bug this
 *      build fixes, because it hits everyone rather than three schools. §2.
 *
 *   2. ⛔ THE FLAGGED PATH. A school-visit parent must NOT be told their book is
 *      "printed especially for you after your order is placed" or that
 *      "Production and delivery times can vary". Andrew rejected exactly that
 *      framing on 2026-08-17: "They will think its getting shipped."
 *      (CYCLE164-CX-01, found live on production by `commerce-cx`.) §3.
 *
 *   3. THE SUPPRESSION IS A SUPPRESSION. The filter must return the checkout
 *      block content IDENTICALLY — not emptied, not rebuilt, not reordered. §3.
 *
 *   4. ⛔ THE VOICE RULE. Standing rule §9.1, adopted by Andrew Signore on
 *      2026-08-18: no "we"/"us"/"our" standing for the company in customer-facing
 *      words. §4 asserts it on the two strings this build changed, and ONLY
 *      those two — the rest of the corpus is inventoried for Andrew, not
 *      rewritten, so this suite must not be extended to police strings he has
 *      not ruled on.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO coupon, NO shipping, tax or
 *    payment setting, on any environment. §6 re-reads both WooCommerce local-
 *    pickup options before and after every flagged code path and asserts they
 *    came back byte-identical.
 * ⛔ It places NO order and delivers NO webhook.
 * ⚠ It DOES set one key in the WooCommerce session for the duration of §3, and
 *    clears it again in §5. That is a session value, not a stored setting, and
 *    it is the only way to exercise the real predicate rather than a mock.
 * ⚠ §3 SKIPS, with its reason printed, when the visit registry holds no live
 *    non-expired visit. A vacuous pass on the load-bearing assertion would be
 *    worse than a skip.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skips    = array();

function bhp_cg_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_cg_skip( $label, $reason, array &$skips ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$skips[] = $label;
}

/**
 * Customer-facing "we/us/our" detector.
 *
 * Word-boundary matched and case-insensitive. "US" as the country abbreviation
 * is NOT what this is looking for, so "Contiguous US Shipping" would trip it —
 * which is why this helper is only ever pointed at the two specific strings
 * this build changed, and never swept across arbitrary copy.
 */
function bhp_cg_has_company_we( $text ) {
	return (bool) preg_match( '/\b(we|us|our|ours|we\'re|we\'ve)\b/i', (string) $text );
}

echo "\n=== CYCLE164 visit copy gate — theme " . wp_get_theme()->get( 'Version' ) . " ===\n\n";

/* =====================================================================
 * §1 — PRECONDITIONS
 * ===================================================================== */
echo "--- §1 preconditions ---\n";

bhp_cg_assert(
	function_exists( 'bhp_printed_for_you_is_visit_session' ),
	'§1 the theme exposes bhp_printed_for_you_is_visit_session()',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_checkout_printed_for_you_after_actions' ),
	'§1 the checkout render_block filter function is present',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_school_visit_use_delivery_framing' ),
	'§1 the bundle plugin exposes the ONE shared visit predicate (no second source of truth)',
	$failures
);

$pickup_settings_before  = get_option( 'woocommerce_pickup_location_settings', 'ABSENT' );
$pickup_locations_before = get_option( 'pickup_location_pickup_locations', 'ABSENT' );

/* Put the request on the checkout page so is_checkout() is genuinely true. */
$checkout_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
bhp_cg_assert( $checkout_id > 0, '§1 a checkout page id resolves', $failures );
if ( $checkout_id > 0 ) {
	// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts -- test harness only; wp_reset_query() in §5.
	query_posts( array( 'page_id' => $checkout_id ) );
}
bhp_cg_assert(
	function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page(),
	'§1 the harness has put this request on the real checkout surface',
	$failures
);

$BLOCK   = array( 'blockName' => 'woocommerce/checkout' );
$CONTENT = '<div class="wp-block-woocommerce-checkout">CHECKOUT-BLOCK-CONTENT</div>';

/* =====================================================================
 * §2 — THE CONTROL PATH IS UNCHANGED
 * ===================================================================== */
echo "\n--- §2 control path (no visit session) ---\n";

bhp_cg_assert(
	false === bhp_printed_for_you_is_visit_session(),
	'§2 with no visit session the predicate is FALSE',
	$failures
);

$control_out = bhp_checkout_printed_for_you_after_actions( $CONTENT, $BLOCK );

bhp_cg_assert(
	$control_out !== $CONTENT,
	'§2 ⛔ the notice is STILL APPENDED for an ordinary shopper (the control path did not regress)',
	$failures
);
bhp_cg_assert(
	false !== strpos( $control_out, 'bhp-printed-for-you' ),
	'§2 the appended markup is the real notice component',
	$failures
);
bhp_cg_assert(
	false !== strpos( $control_out, 'bhp-checkout-after-actions' ),
	'§2 it is still wrapped in the below-Place-Order wrapper (B5 placement intact)',
	$failures
);
bhp_cg_assert(
	0 === strpos( $control_out, $CONTENT ),
	'§2 WooCommerce\'s own block content is emitted first and untouched',
	$failures
);
bhp_cg_assert(
	false !== strpos( $control_out, 'printed especially for you' ),
	'§2 the print-on-demand expectation-setting copy still reaches the ordinary shopper',
	$failures
);

/* A block that is not the checkout block is returned by identity, always. */
bhp_cg_assert(
	$CONTENT === bhp_checkout_printed_for_you_after_actions( $CONTENT, array( 'blockName' => 'core/paragraph' ) ),
	'§2 any other block is returned by identity',
	$failures
);

/* =====================================================================
 * §3 — THE FLAGGED PATH SUPPRESSES THE NOTICE
 * ===================================================================== */
echo "\n--- §3 flagged path (live school-visit session) ---\n";

$live_slug = '';
if ( function_exists( 'bhp_school_visit_records' ) && function_exists( 'bhp_school_visit_resolve' ) ) {
	foreach ( array_keys( bhp_school_visit_records() ) as $slug ) {
		if ( bhp_school_visit_resolve( $slug ) ) {
			$live_slug = $slug;
			break;
		}
	}
}

if ( '' === $live_slug ) {
	bhp_cg_skip(
		'§3 the whole flagged-path section',
		'no live, non-expired visit exists in the registry on this environment, so a pass here would be vacuous',
		$skips
	);
} elseif ( ! function_exists( 'WC' ) ) {
	bhp_cg_skip( '§3 the whole flagged-path section', 'WooCommerce is not loaded', $skips );
} else {
	if ( ! WC()->session && is_callable( array( WC(), 'initialize_session' ) ) ) {
		WC()->initialize_session();
	}

	if ( ! WC()->session ) {
		bhp_cg_skip( '§3 the whole flagged-path section', 'no WooCommerce session could be initialised in CLI', $skips );
	} else {
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $live_slug );

		bhp_cg_assert(
			true === bhp_printed_for_you_is_visit_session(),
			"§3 with a live visit flag ({$live_slug}) the predicate is TRUE",
			$failures
		);

		$flagged_out = bhp_checkout_printed_for_you_after_actions( $CONTENT, $BLOCK );

		bhp_cg_assert(
			$flagged_out === $CONTENT,
			'§3 ⭐⭐ the checkout block is returned BYTE-IDENTICAL — a pure suppression, nothing emptied or rebuilt',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $flagged_out, 'bhp-printed-for-you' ),
			'§3 ⛔ the print-on-demand notice is ABSENT from a visit-flagged checkout',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $flagged_out, 'printed especially for you' ),
			'§3 ⛔ "printed especially for you after your order is placed" does not reach a visit parent',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $flagged_out, 'Production and delivery times can vary' ),
			'§3 ⛔ "Production and delivery times can vary, so please order early" does not reach a visit parent',
			$failures
		);

		/* The component itself is not broken — only suppressed on this surface. */
		bhp_cg_assert(
			false !== strpos( bhp_render_printed_for_you_notice( 'product' ), 'bhp-printed-for-you' ),
			'§3 the component still renders when called directly (suppressed on checkout, not disabled globally)',
			$failures
		);
	}
}

/* =====================================================================
 * §4 — THE VOICE RULE, ON THE TWO STRINGS THIS BUILD CHANGED
 * ===================================================================== */
echo "\n--- §4 standing rule §9.1, the voice rule ---\n";

$pfy = function_exists( 'bhp_get_printed_for_you_data' ) ? bhp_get_printed_for_you_data() : array();
$pfy_paragraphs = isset( $pfy['paragraphs'] ) ? (array) $pfy['paragraphs'] : array();

bhp_cg_assert(
	! empty( $pfy_paragraphs ),
	'§4 the printed-for-you copy is readable from its single source of truth',
	$failures
);

$waste_line = '';
foreach ( $pfy_paragraphs as $para ) {
	if ( false !== strpos( $para, 'reduce waste' ) ) {
		$waste_line = $para;
	}
}
bhp_cg_assert( '' !== $waste_line, '§4 the "reduce waste" sentence is present', $failures );
bhp_cg_assert(
	'' !== $waste_line && false !== strpos( $waste_line, 'This helps me reduce waste' ),
	'§4 CYCLE164-CX-02 it now reads "This helps me reduce waste", not "us"',
	$failures
);
bhp_cg_assert(
	'' !== $waste_line && ! bhp_cg_has_company_we( $waste_line ),
	'§4 CYCLE164-CX-02 no we/us/our survives in that sentence',
	$failures
);

$scope = function_exists( 'bhp_checkout_shipping_scope_notice' ) ? bhp_checkout_shipping_scope_notice() : '';
bhp_cg_assert(
	'I currently ship within the contiguous United States.' === $scope,
	'§4 CYCLE164-CX-03 the control-checkout shipping-scope line reads "I currently ship...", not "We"',
	$failures
);
bhp_cg_assert(
	! bhp_cg_has_company_we( $scope ),
	'§4 CYCLE164-CX-03 no we/us/our survives in the shipping-scope line',
	$failures
);

/* =====================================================================
 * §5 — RESTORE
 * ===================================================================== */
echo "\n--- §5 restore ---\n";

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}
bhp_cg_assert(
	false === bhp_printed_for_you_is_visit_session(),
	'§5 the visit flag is cleared and the predicate is FALSE again',
	$failures
);
$restored = bhp_checkout_printed_for_you_after_actions( $CONTENT, $BLOCK );
bhp_cg_assert(
	$restored !== $CONTENT && false !== strpos( $restored, 'bhp-printed-for-you' ),
	'§5 ⛔ after the flag is cleared the ordinary shopper gets the notice again',
	$failures
);

wp_reset_query();

/* =====================================================================
 * §6 — NOTHING WAS WRITTEN
 * ===================================================================== */
echo "\n--- §6 no WooCommerce setting was mutated ---\n";

bhp_cg_assert(
	$pickup_settings_before === get_option( 'woocommerce_pickup_location_settings', 'ABSENT' ),
	'§6 ⛔ woocommerce_pickup_location_settings came back BYTE-IDENTICAL',
	$failures
);
bhp_cg_assert(
	$pickup_locations_before === get_option( 'pickup_location_pickup_locations', 'ABSENT' ),
	'§6 ⛔ pickup_location_pickup_locations came back BYTE-IDENTICAL',
	$failures
);

/* =====================================================================
 * RESULT
 * ===================================================================== */
echo "\n===================================\n";
if ( $skips ) {
	echo 'SKIPPED (' . count( $skips ) . "): " . implode( ' | ', $skips ) . "\n";
}
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "ALL ASSERTIONS PASSED.\n";
exit( 0 );
