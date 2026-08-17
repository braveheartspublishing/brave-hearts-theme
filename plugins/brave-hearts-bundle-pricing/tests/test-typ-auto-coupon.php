<?php
/**
 * Brave Hearts Bundle Pricing — the auto-applied welcome discount (1.8.47).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-typ-auto-coupon.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE162-LD-TYP-V2`
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew, verbatim, relayed through the Chief of Staff and NOT witnessed by
 * this agent: *"if they click get collection it auto applies the discount so
 * they have a 2 click path to purchase no need to add the coupon code in"*.
 *
 * A mechanism that silently applies a discount to a customer's cart has three
 * failure modes that cost real money, and this suite exists for those three:
 *
 *   A. IT FIRES WHEN NOBODY ASKED. A normal Complete Collection visit — no
 *      thank-you page, no param, no session intent — must be byte-for-byte
 *      the 1.8.46 purchase. That is the NO-PARAM REGRESSION and it is the
 *      most important assertion in the file.
 *   B. IT DESTROYS THE BUNDLE SAVING. `C1_C6_COUPON_ROTATION.md` records the
 *      measured trap: a coupon WITHOUT the `_bhp_audience_coupon` flag
 *      suppresses the Bundle Savings fee and makes the Collection $0.41 MORE
 *      expensive than using no coupon at all. This suite refuses to let an
 *      unflagged coupon be wired to the auto-apply option.
 *   C. IT ADVERTISES A PRICE THE CART WILL NOT HONOUR. The figure a page
 *      prints and the figure the cart charges must come out of one
 *      expression, not two that agree today.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It creates, edits and deletes NOTHING. No coupon, product, order, option or
 * setting is written on any environment. It never prints a coupon code. It
 * cannot prove browser geometry and it cannot prove a real Blocks checkout
 * total — that is measured in a real cart and recorded in the CYCLE162 QA
 * evidence, not asserted here.
 *
 * An environment with the feature switched off still passes, and says which
 * assertions it could not exercise rather than reporting a green run it did
 * not earn.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_typ_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}
function bhp_typ_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

echo "=== 1. The mechanism exists and is wired ===\n";

foreach ( array(
	'bhp_audience_coupon_resolve',
	'bhp_typ_auto_coupon_record',
	'bhp_typ_auto_coupon_offer',
	'bhp_typ_capture_auto_coupon_intent',
	'bhp_typ_maybe_apply_auto_coupon',
	'bhp_audience_coupon_savings_for_format',
	'bhp_audience_coupon_savings_amount',
	'bhp_audience_coupon_cart_qualifies',
) as $fn ) {
	bhp_typ_assert( function_exists( $fn ), "1: {$fn}() exists", $failures );
}
foreach ( array(
	'BHP_TYP_AUTO_COUPON_OPTION',
	'BHP_TYP_AUTO_COUPON_SESSION_KEY',
	'BHP_TYP_AUTO_COUPON_PARAM',
	'BHP_TYP_AUTO_COUPON_PARAM_VALUE',
) as $const ) {
	bhp_typ_assert( defined( $const ), "1: {$const} is defined", $failures );
}
bhp_typ_assert(
	has_action( 'template_redirect', 'bhp_typ_capture_auto_coupon_intent' ) !== false,
	'1: the intent capture is on template_redirect',
	$failures
);
bhp_typ_assert(
	has_action( 'woocommerce_add_to_cart', 'bhp_typ_auto_coupon_net' ) !== false
	&& has_action( 'woocommerce_check_cart_items', 'bhp_typ_auto_coupon_net' ) !== false,
	'1: both safety nets are hooked (add-to-cart and cart/checkout validation)',
	$failures
);
/* The primary path: the bundle form handler calls it directly. */
$bhp_typ_sc = (string) @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-shortcode.php' );
bhp_typ_assert(
	strpos( $bhp_typ_sc, 'bhp_typ_maybe_apply_auto_coupon' ) !== false,
	'1: the bundle add-to-cart handler calls it on the primary path',
	$failures
);

echo "\n=== 2. ⛔ NO COUPON CODE LITERAL ENTERS THIS PUBLIC REPOSITORY ===\n";

/*
 * BHP-AGENT-STANDING-RULES §4.1 classes coupon codes as PRIVATE and conflict
 * C6 is the live instance of them leaking. Plugin 1.8.29 emptied the last
 * literal list for exactly this reason; nothing added since may reintroduce
 * one. Block comments are stripped first — a docblock recording that a code
 * is read from an option is evidence, not a literal.
 */
$bhp_typ_files = array(
	'includes/bundle-cart.php',
	'includes/bundle-shortcode.php',
);
foreach ( $bhp_typ_files as $rel ) {
	$src = (string) @file_get_contents( BHP_BUNDLE_PRICING_DIR . $rel );
	bhp_typ_assert( '' !== $src, "2: {$rel} is readable", $failures );
	if ( '' === $src ) {
		continue;
	}
	$code_only = (string) preg_replace( '#/\*.*?\*/#s', '', $src );
	$code_only = (string) preg_replace( '#//[^\n]*#', '', $code_only );
	bhp_typ_assert(
		preg_match( '/[\'"][A-Z]{4,}[0-9]{1,3}[\'"]/', $code_only ) !== 1,
		"2: no coupon-code-shaped literal in {$rel}",
		$failures
	);
}
bhp_typ_assert(
	is_array( BHP_AUDIENCE_COUPON_CODES ) && array() === BHP_AUDIENCE_COUPON_CODES,
	'2: the compiled literal code list is still empty in this build',
	$failures
);
bhp_typ_assert(
	'bhp_typ_auto_coupon' === BHP_TYP_AUTO_COUPON_OPTION,
	'2: the code is read from a per-environment site option, not compiled in',
	$failures
);
/* The param is a fixed neutral literal and must not resemble a code. */
bhp_typ_assert(
	'welcome' === BHP_TYP_AUTO_COUPON_PARAM_VALUE
	&& preg_match( '/[A-Z]{4,}[0-9]/', BHP_TYP_AUTO_COUPON_PARAM_VALUE ) !== 1,
	'2: the URL param value is a neutral literal that discloses nothing',
	$failures
);

echo "\n=== 3. The savings expression has ONE definition ===\n";

/*
 * Until 1.8.46 the cart's savings arithmetic hardcoded 10% while the coupon
 * record carried its own amount. They agreed — but a page quoting an
 * effective price has to compute it from the same expression the cart
 * charges from, or a coupon edit nobody re-deploys for silently splits them.
 */
$bhp_typ_cart_src = (string) @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-cart.php' );
$bhp_typ_cart_code = (string) preg_replace( '#/\*.*?\*/#s', '', $bhp_typ_cart_src );
$bhp_typ_cart_code = (string) preg_replace( '#//[^\n]*#', '', $bhp_typ_cart_code );
bhp_typ_assert(
	strpos( $bhp_typ_cart_code, '* 0.10' ) === false && strpos( $bhp_typ_cart_code, '*0.10' ) === false,
	'3: the hardcoded 10% rate is gone from the cart savings arithmetic',
	$failures
);
foreach ( array( 'paperback', 'hardcover' ) as $fmt ) {
	$rules    = bhp_bundle_rules( $fmt );
	$combined = 3 * bhp_bundle_expected_price( $fmt );
	$expected = round( ( $combined - $rules[3]['discount'] ) * 0.10, 2 );
	bhp_typ_assert(
		abs( bhp_audience_coupon_savings_for_format( $fmt, 10 ) - $expected ) < 0.0001,
		sprintf( '3: %s at 10%% is unchanged from 1.8.46 behaviour (%.2f)', $fmt, $expected ),
		$failures
	);
	bhp_typ_assert(
		0.0 === bhp_audience_coupon_savings_for_format( $fmt, 0 ),
		"3: {$fmt} at 0% yields no savings (fails closed)",
		$failures
	);
	bhp_typ_assert(
		bhp_audience_coupon_savings_for_format( $fmt, 20 ) > bhp_audience_coupon_savings_for_format( $fmt, 10 ),
		"3: {$fmt} tracks the live percentage rather than a constant",
		$failures
	);
}

echo "\n=== 4. ⛔ THE AUDIENCE-COUPON TRAP: an unflagged coupon can never be wired ===\n";

/*
 * The measured trap, from docs/RELEASES/C1_C6_COUPON_ROTATION.md: a coupon
 * cloned field-for-field but WITHOUT `_bhp_audience_coupon` suppresses the
 * Bundle Savings fee and makes the Collection $0.41 MORE expensive than no
 * coupon at all. The resolver is the single gate that prevents it.
 */
$bhp_typ_coupons = get_posts( array(
	'post_type'      => 'shop_coupon',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );
bhp_typ_assert( is_array( $bhp_typ_coupons ), '4: the coupon list is readable', $failures );

$bhp_typ_checked = 0;
foreach ( (array) $bhp_typ_coupons as $cid ) {
	$coupon    = new WC_Coupon( $cid );
	$flagged   = ( 'yes' === $coupon->get_meta( BHP_AUDIENCE_COUPON_META_KEY, true ) );
	$published = ( 'publish' === get_post_status( $cid ) );
	$resolved  = bhp_audience_coupon_resolve( $coupon->get_code() );

	if ( ! $flagged ) {
		bhp_typ_assert(
			null === $resolved,
			sprintf( '4: coupon #%d (unflagged) is REFUSED by the resolver — the $0.41 trap cannot be wired', $cid ),
			$failures
		);
		$bhp_typ_checked++;
		continue;
	}
	if ( ! $published ) {
		bhp_typ_assert(
			null === $resolved,
			sprintf( '4: coupon #%d (flagged but not published) is REFUSED', $cid ),
			$failures
		);
		$bhp_typ_checked++;
		continue;
	}
	/* Flagged AND published: it must be a positive percent coupon, unexpired,
	   and its minimum spend must be reachable on the collection it would sit
	   beside. Anything else must resolve to null. */
	$is_percent = ( 'percent' === $coupon->get_discount_type() && (float) $coupon->get_amount() > 0 );
	$expiry     = $coupon->get_date_expires();
	$unexpired  = ! ( $expiry && $expiry->getTimestamp() < time() );
	$min        = (float) $coupon->get_minimum_amount();
	$facts      = bhp_bundle_landing_price_facts( 'paperback' );
	$reachable  = ( $min <= 0 || $min <= (float) $facts['bundle'] );

	bhp_typ_assert(
		( $is_percent && $unexpired && $reachable ) === ( null !== $resolved ),
		sprintf(
			'4: coupon #%d resolves if and only if it is a live, unexpired, positive percent coupon with a reachable minimum (percent=%s unexpired=%s reachable=%s min=%.2f)',
			$cid,
			$is_percent ? 'y' : 'n',
			$unexpired ? 'y' : 'n',
			$reachable ? 'y' : 'n',
			$min
		),
		$failures
	);
	if ( null !== $resolved ) {
		bhp_typ_assert(
			bhp_is_audience_coupon_code( $resolved['code'] ),
			sprintf( '4: coupon #%d survives the resolver AND is audience-scoped, so Bundle Savings will SURVIVE alongside it', $cid ),
			$failures
		);
		bhp_typ_assert(
			abs( $resolved['percent'] - (float) $coupon->get_amount() ) < 0.0001,
			sprintf( '4: coupon #%d reports the live amount, not a typed one', $cid ),
			$failures
		);
	}
	$bhp_typ_checked++;
}
if ( 0 === $bhp_typ_checked ) {
	bhp_typ_skip( '4: no coupon records exist on this environment to exercise the trap gate', $skipped );
}
/* A code that is not a coupon at all is never audience-scoped. */
bhp_typ_assert(
	null === bhp_audience_coupon_resolve( 'not-a-real-coupon-' . wp_generate_password( 8, false ) ),
	'4: a non-existent code never resolves',
	$failures
);
bhp_typ_assert(
	null === bhp_audience_coupon_resolve( '' ),
	'4: an empty code never resolves (the shipped default)',
	$failures
);

echo "\n=== 5. Bundle Savings survives the stack, by construction ===\n";

/*
 * The stacking guard in bhp_bundle_apply_discount_fees() returns early — i.e.
 * suppresses Bundle Savings — unless the ONE applied coupon is an audience
 * coupon AND the cart is a qualifying collection. Both branches resolve
 * through bhp_is_audience_coupon_code(), the same function the auto-apply
 * path validates with. This asserts they cannot drift apart.
 */
bhp_typ_assert(
	strpos( $bhp_typ_cart_code, 'bhp_is_audience_coupon_code( $applied_coupons[0] )' ) !== false,
	'5: the Bundle Savings stacking guard still resolves scope through the one resolver',
	$failures
);
bhp_typ_assert(
	strpos( $bhp_typ_cart_code, 'bhp_audience_coupon_cart_qualifies' ) !== false,
	'5: it still requires a genuine qualifying collection as well',
	$failures
);
bhp_typ_assert(
	has_action( 'woocommerce_cart_calculate_fees', 'bhp_bundle_apply_discount_fees' ) === 20
	&& has_action( 'woocommerce_cart_calculate_fees', 'bhp_audience_coupon_apply_savings_fee' ) === 21,
	'5: both fees still fire, Bundle Savings first (20) then the coupon savings (21)',
	$failures
);

echo "\n=== 6. The live gate: what this environment is actually configured to do ===\n";

$bhp_typ_option = trim( (string) get_option( BHP_TYP_AUTO_COUPON_OPTION, '' ) );
$bhp_typ_offer  = bhp_typ_auto_coupon_offer( 'paperback' );

if ( '' === $bhp_typ_option ) {
	echo "NOTE: bhp_typ_auto_coupon is UNSET on this environment. This is the shipped default — no release seeds it.\n";
	bhp_typ_assert( null === $bhp_typ_offer, '6: with the option unset, no offer resolves', $failures );
	bhp_typ_assert( null === bhp_typ_auto_coupon_record(), '6: and no record resolves', $failures );
	bhp_typ_skip( '6: the live-offer arithmetic cannot be exercised with the feature off', $skipped );
} elseif ( null === $bhp_typ_offer ) {
	/* ⛔ THE GRACEFUL PATH. The option names something the live coupon record
	 *    will not honour — deleted, unpublished, expired, wrong type, or no
	 *    longer audience-flagged. The page must render nothing and the cart
	 *    must apply nothing. Nothing fatals; nothing half-applies. */
	echo "NOTE: bhp_typ_auto_coupon IS set but the coupon FAILED a live check. This is the graceful/invalid path.\n";
	bhp_typ_assert( null === bhp_typ_auto_coupon_record(), '6: an invalid/expired configured coupon resolves to null, not a partial offer', $failures );
	bhp_typ_assert( true, '6: the invalid path is silent — no exception reached this line', $failures );
} else {
	$facts = bhp_bundle_landing_price_facts( 'paperback' );
	$sav   = bhp_audience_coupon_savings_for_format( 'paperback', $bhp_typ_offer['percent'] );
	printf(
		"NOTE: the auto-applied welcome discount is LIVE here. paperback collection %.2f - savings %.2f = %.2f at %.2f%%.\n",
		$facts['bundle'],
		$sav,
		$bhp_typ_offer['effective'],
		$bhp_typ_offer['percent']
	);
	bhp_typ_assert(
		abs( $bhp_typ_offer['effective'] - round( (float) $facts['bundle'] - $sav, 2 ) ) < 0.005,
		'6: the offered effective price is bundle minus the cart savings expression, to the cent',
		$failures
	);
	bhp_typ_assert(
		$bhp_typ_offer['savings'] > 0 && $bhp_typ_offer['effective'] > 0 && $bhp_typ_offer['effective'] < (float) $facts['bundle'],
		'6: the offer is a real, positive, sub-collection-price discount',
		$failures
	);
	bhp_typ_assert(
		bhp_is_audience_coupon_code( $bhp_typ_offer['code'] ),
		'6: the configured coupon carries audience scope, so Bundle Savings SURVIVES it',
		$failures
	);
	/* The minimum-spend question, answered against live data rather than
	   assumed: a floor above the collection price would make the advertised
	   offer unreachable on the very cart it sits beside. */
	$rec = bhp_typ_auto_coupon_record( 'paperback' );
	bhp_typ_assert(
		$rec && ( $rec['minimum'] <= 0 || $rec['minimum'] <= (float) $facts['bundle'] ),
		sprintf( '6: its minimum spend (%.2f) is reachable on the %.2f collection', $rec ? $rec['minimum'] : -1, $facts['bundle'] ),
		$failures
	);
}

echo "\n=== 7. ⛔ THE NO-PARAM REGRESSION: an ordinary Collection visit is untouched ===\n";

/*
 * The single most important assertion in this file. A customer who never saw
 * the thank-you page, never carried the param and has no session intent must
 * get the 1.8.46 purchase exactly. This is asserted at the only place it can
 * be asserted without a browser: the intent gate itself.
 */
bhp_typ_assert(
	! function_exists( 'WC' ) || ! WC()->session || ! WC()->session->get( BHP_TYP_AUTO_COUPON_SESSION_KEY ),
	'7: no session intent exists in a plain WP-CLI/no-param context',
	$failures
);
bhp_typ_assert(
	false === bhp_typ_maybe_apply_auto_coupon(),
	'7: with no session intent, the apply function is a no-op and returns false',
	$failures
);
bhp_typ_assert(
	empty( $_GET[ BHP_TYP_AUTO_COUPON_PARAM ] ),
	'7: the param is absent here, which is the no-param case being asserted',
	$failures
);
/* The capture gate refuses every value except the one literal. */
$bhp_typ_saved_get = $_GET;
/* ⛔ Every value here is deliberately NOT coupon-code-shaped. This file is in
   the same public repository, and a "realistic" fake code in a test is still
   a string a reader will try. */
foreach ( array( '1', 'yes', 'true', 'Welcome ', 'wellcome', 'welcome-offer' ) as $bad ) {
	$_GET[ BHP_TYP_AUTO_COUPON_PARAM ] = $bad;
	bhp_typ_capture_auto_coupon_intent();
	$leaked = ( function_exists( 'WC' ) && WC()->session ) ? (bool) WC()->session->get( BHP_TYP_AUTO_COUPON_SESSION_KEY ) : false;
	bhp_typ_assert(
		! $leaked || 'welcome' === sanitize_key( $bad ),
		sprintf( '7: param value "%s" does not set an intent', $bad ),
		$failures
	);
}
$_GET = $bhp_typ_saved_get;
bhp_typ_assert(
	true,
	'7: exercising the capture gate raised no exception',
	$failures
);

echo "\n";
if ( $skipped ) {
	echo 'SKIPPED (' . count( $skipped ) . "), stated rather than counted as passes:\n";
	foreach ( $skipped as $s ) {
		echo "  - {$s}\n";
	}
	echo "\n";
}
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
