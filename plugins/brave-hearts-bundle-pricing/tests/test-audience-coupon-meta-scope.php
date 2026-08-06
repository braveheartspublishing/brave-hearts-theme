<?php
/**
 * Brave Hearts Bundle Pricing — audience-coupon scope source test (1.8.29).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-audience-coupon-meta-scope.php --user=1
 *
 * WHAT THIS PROTECTS
 * ------------------
 * Audience-coupon scope used to be granted two ways: a literal list of
 * coupon codes compiled into bundle-cart.php, and a per-coupon meta flag
 * added in 1.8.8. This plugin is published in a public repository, so the
 * literal list published working discount codes; 1.8.29 emptied it and
 * left the meta flag as the only route.
 *
 * That change is only safe if the meta flag genuinely governs. This suite
 * asserts it continuously, rather than resting on the one-off check made
 * at the time:
 *
 *   1. the compiled literal list carries no code in this build;
 *   2. every applicable coupon on this environment resolves its audience
 *      scope from its own record, and the resolver agrees with the flag
 *      exactly -- no coupon is audience-scoped for any other reason;
 *   3. a code that is not a coupon at all is never audience-scoped;
 *   4. the stacking, validation, native-discount-zeroing and savings-fee
 *      decision points all resolve through the same one resolver, so they
 *      cannot disagree about what an audience coupon is.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 * It creates, edits and deletes NOTHING. No coupon, product, order,
 * option or setting is written on any environment. It reads the coupons
 * that already exist and asserts relationships between them. An
 * environment with no published coupons at all still passes -- and says
 * which assertions it could not exercise, rather than reporting a green
 * run it did not earn.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_ac_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_ac_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

// ==================== 1. The compiled list carries no code ====================

bhp_ac_assert( defined( 'BHP_AUDIENCE_COUPON_CODES' ), 'BHP_AUDIENCE_COUPON_CODES is still defined (call sites cannot fatal)', $failures );
bhp_ac_assert( is_array( BHP_AUDIENCE_COUPON_CODES ), 'BHP_AUDIENCE_COUPON_CODES is an array', $failures );
bhp_ac_assert( defined( 'BHP_AUDIENCE_COUPON_META_KEY' ) && '_bhp_audience_coupon' === BHP_AUDIENCE_COUPON_META_KEY, 'The per-coupon meta key is unchanged', $failures );

// The constant is empty in this repository. A private environment may
// legitimately pin codes outside source control, so this assertion reads
// the SHIPPED FILE rather than the runtime constant -- what matters is
// that no code is committed, not that no environment ever defines one.
$cart_file = dirname( __DIR__ ) . '/includes/bundle-cart.php';
$cart_src  = file_exists( $cart_file ) ? file_get_contents( $cart_file ) : '';
bhp_ac_assert( '' !== $cart_src, 'bundle-cart.php is readable for source inspection', $failures );

$defines_empty = (bool) preg_match(
	"/define\(\s*'BHP_AUDIENCE_COUPON_CODES'\s*,\s*array\(\s*\)\s*\)/",
	$cart_src
);
bhp_ac_assert( $defines_empty, 'The shipped bundle-cart.php defines BHP_AUDIENCE_COUPON_CODES as an EMPTY array -- no coupon code is committed to this public repository', $failures );

// Belt and braces: no string literal at all inside that define call.
if ( preg_match( "/define\(\s*'BHP_AUDIENCE_COUPON_CODES'\s*,(.*?)\);/s", $cart_src, $m ) ) {
	bhp_ac_assert( 0 === preg_match( "/'[^']+'/", $m[1] ), 'The BHP_AUDIENCE_COUPON_CODES define contains no string literal of any kind', $failures );
} else {
	bhp_ac_assert( false, 'The BHP_AUDIENCE_COUPON_CODES define could not be located for inspection', $failures );
}

// ==================== 2. The resolver and the flag agree, coupon by coupon ====================

$coupons = get_posts( array(
	'post_type'      => 'shop_coupon',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );

if ( empty( $coupons ) ) {
	bhp_ac_skip( 'No published coupon exists on this environment -- the per-coupon equivalence assertions could not be exercised', $skipped );
} else {
	$agree        = true;
	$flagged      = 0;
	$unflagged    = 0;
	$disagreement = array();

	foreach ( $coupons as $coupon_id ) {
		$coupon    = new WC_Coupon( $coupon_id );
		$code      = $coupon->get_code();
		$flag      = ( 'yes' === get_post_meta( $coupon_id, BHP_AUDIENCE_COUPON_META_KEY, true ) );
		$by_code   = bhp_is_audience_coupon_code( $code );
		$by_object = bhp_is_audience_coupon( $coupon );

		if ( $flag ) {
			$flagged++;
		} else {
			$unflagged++;
		}

		// The flag is the ONLY thing that may grant scope, and both
		// resolvers must reach the same verdict from it.
		if ( $by_code !== $flag || $by_object !== $flag ) {
			$agree = false;
			$disagreement[] = $coupon_id; // an ID, never the code
		}
	}

	bhp_ac_assert(
		$agree,
		'Every published coupon resolves audience scope from its own meta flag, by both the code path and the object path' .
			( $agree ? '' : ' (disagreeing coupon IDs: ' . implode( ', ', $disagreement ) . ')' ),
		$failures
	);

	if ( 0 === $flagged ) {
		bhp_ac_skip( 'No published coupon carries the audience flag on this environment -- the positive case could not be exercised', $skipped );
	} else {
		bhp_ac_assert( true, "The positive case is exercised: {$flagged} published coupon(s) carry the audience flag and resolve as audience coupons", $failures );
	}
	if ( 0 === $unflagged ) {
		bhp_ac_skip( 'Every published coupon carries the audience flag on this environment -- the negative case could not be exercised', $skipped );
	} else {
		bhp_ac_assert( true, "The negative case is exercised: {$unflagged} published coupon(s) do not carry the flag and resolve as ordinary coupons", $failures );
	}
}

// ==================== 3. A code that is not a coupon is never scoped ====================

$nonexistent = 'bhp-not-a-real-coupon-' . wp_generate_password( 12, false, false );
bhp_ac_assert( false === bhp_is_audience_coupon_code( $nonexistent ), 'A code that matches no coupon record is never audience-scoped', $failures );
bhp_ac_assert( false === bhp_is_audience_coupon_code( '' ), 'An empty code is never audience-scoped', $failures );
bhp_ac_assert( false === bhp_is_audience_coupon_code( '   ' ), 'A whitespace-only code is never audience-scoped', $failures );
bhp_ac_assert( false === bhp_is_audience_coupon( null ), 'A non-coupon value is never audience-scoped', $failures );
bhp_ac_assert( false === bhp_is_audience_coupon( new stdClass() ), 'A non-WC_Coupon object is never audience-scoped', $failures );

// An unsaved coupon object carrying no flag must not be scoped either --
// this is the path a coupon takes before it exists in the database.
$unsaved = new WC_Coupon();
$unsaved->set_code( $nonexistent );
bhp_ac_assert( false === bhp_is_audience_coupon( $unsaved ), 'An unsaved coupon object with no flag is never audience-scoped', $failures );

// ==================== 4. One resolver behind every decision point ====================

$expected_functions = array(
	'bhp_is_audience_coupon_code',
	'bhp_is_audience_coupon',
	'bhp_validate_audience_coupon_scope',
	'bhp_audience_coupon_zero_native_discount',
	'bhp_audience_coupon_apply_savings_fee',
	'bhp_bundle_apply_discount_fees',
);
foreach ( $expected_functions as $fn ) {
	bhp_ac_assert( function_exists( $fn ), "{$fn}() exists -- the audience-coupon decision points are all still wired", $failures );
}

// Each decision point must route through a resolver, never re-implement
// the check. Asserted against the source so a future edit that inlines a
// literal comparison fails here rather than in production.
$decision_points = array(
	'bhp_validate_audience_coupon_scope',
	'bhp_audience_coupon_zero_native_discount',
	'bhp_audience_coupon_apply_savings_fee',
	'bhp_bundle_apply_discount_fees',
);
$all_routed = true;
foreach ( $decision_points as $fn ) {
	if ( ! preg_match( '/function\s+' . preg_quote( $fn, '/' ) . '\s*\([^)]*\)\s*\{(.*?)\n\}/s', $cart_src, $body ) ) {
		$all_routed = false;
		continue;
	}
	if ( ! preg_match( '/bhp_is_audience_coupon(_code)?\s*\(/', $body[1] ) ) {
		$all_routed = false;
	}
}
bhp_ac_assert( $all_routed, 'Validation, native-discount zeroing, the savings fee and the Bundle Savings stacking guard all resolve audience scope through the shared resolver', $failures );

// The savings-fee label is built from the applied code at runtime, so no
// code needs to be known ahead of time for the fee to render correctly.
bhp_ac_assert(
	(bool) preg_match( "/add_fee\(\s*sprintf\(\s*'%s Savings'/", $cart_src ),
	'The savings-fee line label is derived from the applied coupon code at runtime, not from a compiled list',
	$failures
);

// ==================== Result ====================

echo "\n";
if ( ! empty( $skipped ) ) {
	echo count( $skipped ) . " assertion group(s) SKIPPED for lack of data on this environment:\n";
	foreach ( $skipped as $s ) { echo " - {$s}\n"; }
	echo "\n";
}
echo empty( $failures ) ? "ALL AUDIENCE-COUPON SCOPE TESTS PASSED\n" : count( $failures ) . " TEST(S) FAILED:\n";
if ( ! empty( $failures ) ) {
	foreach ( $failures as $f ) { echo " - {$f}\n"; }
	exit( 1 );
}
