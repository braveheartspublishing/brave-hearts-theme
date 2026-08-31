<?php
/**
 * Brave Hearts Bundle Pricing — 1.8.77, `CYCLE172-LD-COUPON-DEFECT`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cycle172-coupon-key.php --user=1
 *
 * WHAT THIS PROTECTS — AND WHY IT IS WORTH A SUITE OF ITS OWN
 * -----------------------------------------------------------
 * `CYCLE172-CX-FUNNEL-E2E` **E-1**: on production, on a three-paperback cart,
 * two of the three audience coupons rendered *"Your 10% discount code … is
 * applied"* and then charged **$35.97** — $3.98 MORE than applying no coupon,
 * and $7.18 more than the third audience coupon on the byte-identical cart.
 *
 * The cause was NOT the coupon records (confirmed byte-identical) and NOT the
 * code strings (nothing in this plugin branches on one). It was the ARRAY KEY:
 *
 *   WC_Cart::remove_coupon()      unsets a key and does NOT reindex
 *   WC_Cart::apply_coupon()       appends with `[]`, i.e. max-key + 1
 *   WC_Cart::set_applied_coupons() stores the array as-is
 *   WC_Cart_Session               persists and restores it as-is
 *
 * So an `individual_use` swap inside ONE request leaves `array( 1 => 'code' )`,
 * and three call sites in bundle-cart.php read `$applied[0]` — a key that no
 * longer exists. Both money-bearing fees went silent; the appearance-bearing
 * discount-zeroing kept working, because it reads the coupon OBJECT.
 *
 * The suite asserts the INVARIANT that makes the key irrelevant, not one
 * reproduction of one sequence. §1 is pure and runs anywhere. §2 exercises the
 * real cart when the environment has a published audience coupon and the three
 * paperbacks; where it cannot, it SKIPS and says so rather than reporting a
 * green run it did not earn.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 * It writes NO coupon, product, order, option or setting on any environment.
 * §2 mutates only a transient CLI cart and empties it again at the end.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_ck_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_ck_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

echo "===== 1. The normaliser exists and is total =====\n";

bhp_ck_assert(
	function_exists( 'bhp_cart_applied_coupons' ),
	'bhp_cart_applied_coupons() exists — the single reader every call site goes through',
	$failures
);

/**
 * A cart double whose applied-coupon array is deliberately gapped, exactly the
 * way WooCommerce leaves it after an individual_use swap. It implements
 * get_applied_coupons() and get_cart() and nothing else — the same lightweight
 * shape tests/test-addon-free-collection.php already relies on.
 */
class BHP_CK_Gapped_Cart {
	public $coupons = array();
	public $items   = array();
	public function __construct( $coupons = array(), $items = array() ) {
		$this->coupons = $coupons;
		$this->items   = $items;
	}
	public function get_applied_coupons() {
		return $this->coupons;
	}
	public function get_cart() {
		return $this->items;
	}
}

$gapped = new BHP_CK_Gapped_Cart( array( 1 => 'somecode' ) );
$dense  = bhp_cart_applied_coupons( $gapped );

bhp_ck_assert(
	array_key_exists( 0, $dense ),
	'A gapped array( 1 => code ) is reindexed so key 0 exists — the exact expression E-1 read as null',
	$failures
);
bhp_ck_assert(
	isset( $dense[0] ) && 'somecode' === $dense[0],
	'…and key 0 holds the coupon that is actually applied, not a different one',
	$failures
);
bhp_ck_assert(
	1 === count( $dense ),
	'Reindexing does not change the COUNT — the `1 === count()` guards at every call site still mean what they meant',
	$failures
);

$multi = bhp_cart_applied_coupons( new BHP_CK_Gapped_Cart( array( 3 => 'a', 7 => 'b' ) ) );
bhp_ck_assert(
	array( 'a', 'b' ) === $multi,
	'Order is preserved across reindexing (a two-coupon cart still reads first-applied-first)',
	$failures
);

bhp_ck_assert(
	array() === bhp_cart_applied_coupons( new BHP_CK_Gapped_Cart( array() ) ),
	'An empty cart still returns an empty array — the fix cannot manufacture a coupon',
	$failures
);
bhp_ck_assert(
	array() === bhp_cart_applied_coupons( null ) && array() === bhp_cart_applied_coupons( new stdClass() ),
	'A non-cart, and a cart object that cannot report coupons, both return array() rather than fatal',
	$failures
);

echo "\n===== 2. No call site indexes the raw WooCommerce array any more =====\n";

$cart_src = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-cart.php' );

/*
 * ⛔ THIS IS A SOURCE ASSERTION AND IT IS LABELLED AS ONE. It cannot prove
 *    runtime behaviour; §3 does that. What it CAN do is stop the defect being
 *    reintroduced by a future edit that adds a fourth positional reader — which
 *    is precisely how the three existing readers drifted apart in the first
 *    place. A grep is the only tool that sees a call site nobody executed.
 */
/*
 * ⚠ EXACTLY ONE, NOT ZERO — and the first draft of this assertion said zero and
 *   FAILED on the fix that had just been proven correct in §4. The normaliser
 *   itself must call the method; it is the one place allowed to. Recorded rather
 *   than quietly rewritten, because "the test was wrong" is a claim that has to
 *   be earned: §4 observed the real cart producing identical totals at key 0 and
 *   key 1 in the same run this assertion failed.
 */
bhp_ck_assert(
	1 === substr_count( $cart_src, '$cart->get_applied_coupons()' ),
	'bundle-cart.php calls get_applied_coupons() exactly once — inside the normaliser and nowhere else',
	$failures
);
bhp_ck_assert(
	false !== strpos( $cart_src, 'return array_values( (array) $cart->get_applied_coupons() );' ),
	'…and that one call is the normaliser\'s own array_values() return',
	$failures
);
bhp_ck_assert(
	3 <= substr_count( $cart_src, 'bhp_cart_applied_coupons(' ),
	'All three former positional readers now go through the normaliser',
	$failures
);

echo "\n===== 3. F1 — no _wp_http_referer field is emitted into cacheable HTML =====\n";

bhp_ck_assert(
	function_exists( 'bhp_bundle_nonce_input' ),
	'bhp_bundle_nonce_input() exists',
	$failures
);
if ( function_exists( 'bhp_bundle_nonce_input' ) ) {
	ob_start();
	bhp_bundle_nonce_input();
	$html = (string) ob_get_clean();
	bhp_ck_assert(
		false === strpos( $html, '_wp_http_referer' ),
		'F1: bhp_bundle_nonce_input() emits NO _wp_http_referer field',
		$failures
	);
	bhp_ck_assert(
		false !== strpos( $html, 'name="bhp_bundle_nonce"' ),
		'…and still emits the nonce itself, so the form still verifies',
		$failures
	);
	bhp_ck_assert(
		false === strpos( $html, 'id=' ),
		'…and still carries no DOM id (the F14 fix from 1.8.5x is not regressed)',
		$failures
	);
}

$series_src = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-shop-series.php' );
bhp_ck_assert(
	false !== strpos( $series_src, "wp_nonce_field( 'bhp_bundle_add', 'bhp_bundle_nonce', false )" ),
	'F1: the shop-series add form passes $referer = false',
	$failures
);

echo "\n===== 4. The real cart: same coupon, key 0 vs key 1, identical totals =====\n";

$audience_code = '';
if ( function_exists( 'wc_get_coupons' ) || class_exists( 'WC_Coupon' ) ) {
	$coupon_posts = get_posts( array(
		'post_type'   => 'shop_coupon',
		'post_status' => 'publish',
		'numberposts' => 50,
		'fields'      => 'ids',
	) );
	foreach ( $coupon_posts as $cid ) {
		$c = new WC_Coupon( $cid );
		if ( bhp_is_audience_coupon( $c ) && 'percent' === $c->get_discount_type() && (float) $c->get_amount() > 0 ) {
			$audience_code = $c->get_code();
			break;
		}
	}
}

if ( '' === $audience_code ) {
	bhp_ck_skip( 'No PUBLISHED audience coupon on this environment — the live-cart assertions cannot run here', $skipped );
} elseif ( ! function_exists( 'wc_load_cart' ) ) {
	bhp_ck_skip( 'wc_load_cart() unavailable — the live-cart assertions cannot run here', $skipped );
} else {
	wc_load_cart();

	$pb = array();
	foreach ( bhp_bundle_catalog()['paperback'] as $info ) {
		$pb[] = ! empty( $info['variation_id'] ) ? (int) $info['variation_id'] : (int) $info['product_id'];
	}

	$build = function () use ( $pb ) {
		WC()->cart->remove_coupons();
		WC()->cart->empty_cart();
		foreach ( $pb as $id ) {
			WC()->cart->add_to_cart( $id, 1 );
		}
		WC()->cart->calculate_totals();
	};

	$fee_sum = function () {
		$sum = 0.0;
		foreach ( WC()->cart->get_fees() as $fee ) {
			$sum += (float) $fee->amount;
		}
		return round( $sum, 2 );
	};

	// --- key 0: the control, which always worked ---
	$build();
	WC()->cart->set_applied_coupons( array( 0 => $audience_code ) );
	WC()->cart->calculate_totals();
	$fees_at_0  = $fee_sum();
	$count_at_0 = count( WC()->cart->get_fees() );
	$total_at_0 = round( (float) WC()->cart->get_total( 'edit' ), 2 );

	// --- key 1: the exact shape E-1 produced ---
	$build();
	WC()->cart->set_applied_coupons( array( 1 => $audience_code ) );
	WC()->cart->calculate_totals();
	$fees_at_1  = $fee_sum();
	$count_at_1 = count( WC()->cart->get_fees() );
	$total_at_1 = round( (float) WC()->cart->get_total( 'edit' ), 2 );

	echo "  observed: key0 fees={$fees_at_0} ({$count_at_0} lines) total={$total_at_0}\n";
	echo "  observed: key1 fees={$fees_at_1} ({$count_at_1} lines) total={$total_at_1}\n";

	bhp_ck_assert( $fees_at_0 < 0, 'Key 0: the qualifying collection earns negative fees at all (harness sanity)', $failures );
	bhp_ck_assert( 2 === $count_at_0, 'Key 0: BOTH lines present — "[CODE] Savings" and "Bundle Savings"', $failures );
	bhp_ck_assert( 2 === $count_at_1, 'Key 1: BOTH lines present — this is the assertion that fails on 1.8.76', $failures );
	bhp_ck_assert( $fees_at_0 === $fees_at_1, 'Key 0 and key 1 produce the IDENTICAL discount total', $failures );
	bhp_ck_assert( $total_at_0 === $total_at_1, 'Key 0 and key 1 produce the IDENTICAL cart total', $failures );

	// --- the real sequence, not a synthetic key: an individual_use swap ---
	$other = '';
	foreach ( $coupon_posts as $cid ) {
		$c = new WC_Coupon( $cid );
		if ( $c->get_code() !== $audience_code && $c->get_individual_use() ) {
			$other = $c->get_code();
			break;
		}
	}
	if ( '' === $other ) {
		bhp_ck_skip( 'No second published individual_use coupon here — the real swap sequence cannot be exercised', $skipped );
	} else {
		$build();
		WC()->cart->apply_coupon( $other );
		WC()->cart->calculate_totals();
		WC()->cart->apply_coupon( $audience_code ); // same request: individual_use swap
		WC()->cart->calculate_totals();
		$raw   = WC()->cart->get_applied_coupons();
		$swap  = $fee_sum();
		$lines = count( WC()->cart->get_fees() );
		echo '  observed: raw applied_coupons after swap = ' . str_replace( array( "\n", ' ' ), '', var_export( $raw, true ) ) . "\n";
		echo "  observed: swap fees={$swap} ({$lines} lines)\n";
		bhp_ck_assert( 2 === $lines, 'Real individual_use swap in one request: BOTH savings lines still present', $failures );
		bhp_ck_assert( $swap === $fees_at_0, 'Real individual_use swap: discount total identical to the clean-cart control', $failures );
	}

	WC()->cart->remove_coupons();
	WC()->cart->empty_cart();
	echo "  (cart emptied: " . WC()->cart->get_cart_contents_count() . " items, " . count( WC()->cart->get_applied_coupons() ) . " coupons)\n";
}

echo "\n==================================================\n";
printf( "RESULT: %d failed, %d skipped\n", count( $failures ), count( $skipped ) );
foreach ( $failures as $f ) {
	echo "  FAILED: {$f}\n";
}
foreach ( $skipped as $s ) {
	echo "  SKIPPED: {$s}\n";
}
if ( $failures ) {
	echo "SUITE: FAIL\n";
} else {
	echo "SUITE: PASS\n";
}
