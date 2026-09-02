<?php
/**
 * Brave Hearts Bundle Pricing — eligibility/discount math test suite.
 *
 * Run via WP-CLI, matching the pattern already used for the theme's own
 * component tests:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-bundle-pricing.php --user=1
 *
 * Exits non-zero on any failure. Only exercises the pure functions in
 * includes/bundle-data.php plus a minimal stub cart — no real WooCommerce
 * cart session or checkout is touched by this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/*
 * ⭐ 1.8.62 — A NAMED CALLBACK, NOT A CLOSURE. `remove_filter()` cannot remove
 *    an anonymous function, so a closure would leak the forced policy into
 *    every assertion after the block that set it, silently turning later rows
 *    into tests of a policy the store does not run.
 */
function bhp_bp_force_conservative() {
	return 'conservative';
}

/**
 * Minimal stand-in for WC_Cart, exposing only what bundle-data.php and
 * bundle-cart.php actually call: get_cart() and get_applied_coupons().
 */
class BHP_Test_Stub_Cart {
	private $items;
	private $coupons;

	public function __construct( array $items, array $coupons = array() ) {
		$this->items   = $items;
		$this->coupons = $coupons;
	}

	public function get_cart() {
		return $this->items;
	}

	public function get_applied_coupons() {
		return $this->coupons;
	}

	public $fees_added = array();
	public function add_fee( $label, $amount, $taxable = false ) {
		$this->fees_added[] = array( 'label' => $label, 'amount' => $amount, 'taxable' => $taxable );
	}
}

function bhp_test_stub_item( $product_id, $variation_id, $price, $quantity = 1 ) {
	$data = new stdClass();
	$data->_price = $price;
	// get_price() is called on $cart_item['data'] — use an anonymous class
	// so ->get_price() works exactly like a real WC_Product would.
	$product = new class( $price ) {
		private $price;
		public function __construct( $price ) {
			$this->price = $price;
		}
		public function get_price() {
			return $this->price;
		}
	};

	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $quantity,
		'data'         => $product,
	);
}

// ---------------------------------------------------------------------
// 1. Catalog identification
// ---------------------------------------------------------------------
$catalog = bhp_bundle_catalog();
bhp_test_assert( 3 === count( $catalog['paperback'] ), 'Catalog has 3 paperback titles', $failures );
bhp_test_assert( 3 === count( $catalog['hardcover'] ), 'Catalog has 3 hardcover titles', $failures );

bhp_test_assert(
	array( 'paperback', 'mariana' ) === bhp_bundle_identify_cart_item( 333, 334 ),
	'Identifies Mariana paperback by variation ID 334',
	$failures
);
bhp_test_assert(
	array( 'paperback', 'everest' ) === bhp_bundle_identify_cart_item( 15, 0 ),
	'Identifies Everest paperback by product ID 15',
	$failures
);
bhp_test_assert(
	array( 'paperback', 'amazon' ) === bhp_bundle_identify_cart_item( 18, 0 ),
	'Identifies Amazon paperback by product ID 18',
	$failures
);
bhp_test_assert(
	array( 'hardcover', 'mariana' ) === bhp_bundle_identify_cart_item( 14, 0 ),
	'Identifies Mariana hardcover by product ID 14',
	$failures
);
bhp_test_assert(
	array( 'hardcover', 'everest' ) === bhp_bundle_identify_cart_item( 17, 0 ),
	'Identifies Everest hardcover by product ID 17',
	$failures
);
bhp_test_assert(
	array( 'hardcover', 'amazon' ) === bhp_bundle_identify_cart_item( 20, 0 ),
	'Identifies Amazon hardcover by product ID 20',
	$failures
);
bhp_test_assert(
	null === bhp_bundle_identify_cart_item( 999999, 0 ),
	'Unknown product ID returns null (not a false match)',
	$failures
);
bhp_test_assert(
	null === bhp_bundle_identify_cart_item( 333, 0 ),
	'Mariana paperback parent ID alone (no variation) does not false-match',
	$failures
);

// ---------------------------------------------------------------------
// 2. Tier math
// ---------------------------------------------------------------------
bhp_test_assert( 0 === bhp_bundle_qualifying_tier( array() ), 'Zero distinct titles -> tier 0', $failures );
bhp_test_assert( 0 === bhp_bundle_qualifying_tier( array( 'mariana' ) ), 'One distinct title -> tier 0 (not a bundle)', $failures );
bhp_test_assert( 2 === bhp_bundle_qualifying_tier( array( 'mariana', 'everest' ) ), 'Two distinct titles -> tier 2', $failures );
bhp_test_assert( 3 === bhp_bundle_qualifying_tier( array( 'mariana', 'everest', 'amazon' ) ), 'Three distinct titles -> tier 3', $failures );

// ---------------------------------------------------------------------
// 3. Approved dollar amounts, verbatim from the pricing decision
// ---------------------------------------------------------------------
$pb_rules = bhp_bundle_rules( 'paperback' );
$hc_rules = bhp_bundle_rules( 'hardcover' );

bhp_test_assert( 1.99 === $pb_rules[2]['discount'], 'Paperback 2-book discount is $1.99', $failures );
bhp_test_assert( 2.99 === $pb_rules[2]['shipping'], 'Paperback 2-book shipping is $2.99', $failures );
bhp_test_assert( 3.98 === $pb_rules[3]['discount'], 'Paperback 3-book discount is $3.98', $failures );

/*
 * ⭐ 1.8.23 — Option B (Andrew Signore, 2026-08-04, relayed): the complete
 *    collection ships FREE at UNCHANGED pricing.
 *
 * ⚠ SUPERSEDED ASSERTIONS, stated rather than silently deleted, so a reader
 *   of this file sees what moved:
 *       'Paperback 3-book shipping is $3.99'  -> now $0.00
 *       'Hardcover 3-book shipping is $4.99'  -> now $0.00
 *   ⛔ NOTHING ELSE IN THIS BLOCK CHANGED. The discounts, the tier-2 rows and
 *      the single-book rates immediately below are byte-identical, and the
 *      "3 x $11.99 equals $31.99 + $3.98" cross-checks further down still
 *      pass unaltered — which is the proof that the PRICE did not move.
 */
bhp_test_assert( 0.00 === $pb_rules[3]['shipping'], '1.8.23: Paperback 3-book shipping is $0.00 (was $3.99)', $failures );

bhp_test_assert( 2.99 === $hc_rules[2]['discount'], 'Hardcover 2-book discount is $2.99', $failures );
bhp_test_assert( 3.99 === $hc_rules[2]['shipping'], 'Hardcover 2-book shipping is $3.99', $failures );
bhp_test_assert( 4.98 === $hc_rules[3]['discount'], 'Hardcover 3-book discount is $4.98', $failures );
bhp_test_assert( 0.00 === $hc_rules[3]['shipping'], '1.8.23: Hardcover 3-book shipping is $0.00 (was $4.99)', $failures );

bhp_test_assert( 11.99 === bhp_bundle_expected_price( 'paperback' ), 'Expected paperback price is $11.99', $failures );
bhp_test_assert( 17.99 === bhp_bundle_expected_price( 'hardcover' ), 'Expected hardcover price is $17.99', $failures );
bhp_test_assert( 1.99 === bhp_bundle_single_shipping( 'paperback' ), 'Single paperback shipping is $1.99', $failures );
bhp_test_assert( 2.99 === bhp_bundle_single_shipping( 'hardcover' ), 'Single hardcover shipping is $2.99', $failures );

// Cross-check: normal totals implied by the approved prices match the
// spec's stated "normal product total" figures exactly.
bhp_test_assert(
	abs( ( 2 * 11.99 ) - ( 21.99 + 1.99 ) ) < 0.001,
	'2 paperbacks: 2x$11.99 equals $21.99 + $1.99 discount',
	$failures
);
bhp_test_assert(
	abs( ( 3 * 11.99 ) - ( 31.99 + 3.98 ) ) < 0.001,
	'3 paperbacks: 3x$11.99 equals $31.99 + $3.98 discount',
	$failures
);
bhp_test_assert(
	abs( ( 2 * 17.99 ) - ( 32.99 + 2.99 ) ) < 0.001,
	'2 hardcovers: 2x$17.99 equals $32.99 + $2.99 discount',
	$failures
);
bhp_test_assert(
	abs( ( 3 * 17.99 ) - ( 48.99 + 4.98 ) ) < 0.001,
	'3 hardcovers: 3x$17.99 equals $48.99 + $4.98 discount',
	$failures
);

// ---------------------------------------------------------------------
// 4. End-to-end cart evaluation via the stub cart
// ---------------------------------------------------------------------
$cart_one_pb = new BHP_Test_Stub_Cart( array( bhp_test_stub_item( 15, 0, 11.99 ) ) );
$eval        = bhp_bundle_evaluate_cart( $cart_one_pb );
bhp_test_assert( 0 === $eval['paperback_tier'], 'One paperback alone does not qualify for a bundle tier', $failures );
bhp_test_assert( ! $eval['is_mixed_format'], 'One paperback alone is not a mixed-format cart', $failures );

$cart_two_distinct_pb = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
	)
);
$eval = bhp_bundle_evaluate_cart( $cart_two_distinct_pb );
bhp_test_assert( 2 === $eval['paperback_tier'], 'Everest + Amazon paperback qualifies for tier 2', $failures );

$cart_two_same_pb = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 15, 0, 11.99 ),
	)
);
$eval = bhp_bundle_evaluate_cart( $cart_two_same_pb );
bhp_test_assert( 0 === $eval['paperback_tier'], 'Two copies of the same paperback do NOT qualify as "any 2"', $failures );

$cart_all_three_hc = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 14, 0, 17.99 ),
		bhp_test_stub_item( 17, 0, 17.99 ),
		bhp_test_stub_item( 20, 0, 17.99 ),
	)
);
$eval = bhp_bundle_evaluate_cart( $cart_all_three_hc );
bhp_test_assert( 3 === $eval['hardcover_tier'], 'All three hardcovers qualify for tier 3', $failures );

// ---------------------------------------------------------------------
// 4b. Shipping amount resolution (Phase 6 table, verbatim)
// ---------------------------------------------------------------------
$cart_one_pb_ship = new BHP_Test_Stub_Cart( array( bhp_test_stub_item( 15, 0, 11.99 ) ) );
bhp_test_assert(
	1.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_one_pb_ship ) ),
	'Shipping: one paperback alone -> $1.99',
	$failures
);

$cart_one_hc_ship = new BHP_Test_Stub_Cart( array( bhp_test_stub_item( 14, 0, 17.99 ) ) );
bhp_test_assert(
	2.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_one_hc_ship ) ),
	'Shipping: one hardcover alone -> $2.99',
	$failures
);

bhp_test_assert(
	2.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_two_distinct_pb ) ),
	'Shipping: any 2 paperbacks -> $2.99',
	$failures
);

$cart_all_three_pb_ship = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 333, 334, 11.99 ),
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
	)
);
bhp_test_assert(
	0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_all_three_pb_ship ) ),
	'1.8.23 Shipping: all 3 paperbacks -> $0.00 FREE (was $3.99)',
	$failures
);

$cart_two_distinct_hc_ship = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 14, 0, 17.99 ),
		bhp_test_stub_item( 17, 0, 17.99 ),
	)
);
bhp_test_assert(
	3.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_two_distinct_hc_ship ) ),
	'Shipping: any 2 hardcovers -> $3.99',
	$failures
);

bhp_test_assert(
	0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_all_three_hc ) ),
	'1.8.23 Shipping: all 3 hardcovers -> $0.00 FREE (was $4.99)',
	$failures
);

// Staging Refinement Phase 1, item 5: mixed-format carts now get a flat
// approved shipping tier by total book count ($3.99 for 2, $4.99 for 3+)
// instead of falling back to WooCommerce's existing rate untouched.
$cart_mixed_ship = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 14, 0, 17.99 ),
	)
);
bhp_test_assert(
	3.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_mixed_ship ) ),
	'Shipping: mixed cart with 2 total books -> $3.99',
	$failures
);

$cart_mixed_three_ship = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
		bhp_test_stub_item( 14, 0, 17.99 ),
	)
);
/*
 * ⭐ 1.8.23 — THE EDGE CASE THIS BUILD DECIDED, AND ITS COUNTERFACTUAL TWIN.
 *
 * This cart is Everest PB + Amazon PB + Mariana HC: three books, three
 * DISTINCT ADVENTURES, two formats. The `chief-of-staff` direction for this build was
 * that mixed 3-distinct-book carts ship free as part of the same bundle
 * family, and under Option B that is what the customer has: the complete
 * collection, in one shipment.
 *
 * ⚠ SUPERSEDED: 'Shipping: mixed cart with 3 total books -> $4.99'. It is
 *   NOT superseded generally — see the very next assertion, which is the
 *   same book count and still $4.99.
 */
bhp_test_assert(
	0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_mixed_three_ship ) ),
	'1.8.23 Shipping: mixed cart, 3 DISTINCT adventures (2 PB + 1 HC) -> $0.00 FREE',
	$failures
);

$cart_mixed_qty_ship = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99, 2 ), // 2x same paperback
		bhp_test_stub_item( 14, 0, 17.99, 1 ),
	)
);
/*
 * ⛔ THE COUNTERFACTUAL. Same THREE books as the assertion above, same two
 *    formats, and it is still $4.99 — because 2x Everest paperback +
 *    Mariana hardcover is three books but only TWO adventures.
 *
 * ⭐ THIS PAIR IS THE PROOF THAT 1.8.23 DID NOT SIMPLY ZERO SHIPPING. If the
 *    implementation had keyed free shipping off a book count, or off "mixed
 *    with 3 or more", this assertion would now fail. It passes unchanged,
 *    which is what demonstrates the rule is "you hold the collection", never
 *    "you spent enough" — and it keeps faith with the Phase 4 rule that two
 *    copies of one title never qualify for anything.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.62 — POLICY-SCOPED, BY FOUNDER RULING. NOT WEAKENED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE PARAGRAPH ABOVE IS PRESERVED VERBATIM AND STILL PROVES WHAT IT SAYS
 *    IT PROVES — under the `conservative` policy it was written for, which is
 *    the 1.8.23 rule and is still reachable through the filter.
 *
 * ⭐ `FD-583` (PART 66 §66.8, read at source) moved the SHIPPED default to
 *    "any 3 books, duplicates included", which keys free shipping on exactly
 *    the book count this row was written to disprove. ⛔ So the row is scoped
 *    rather than deleted, and the shipped behaviour on the IDENTICAL cart is
 *    asserted immediately after it — the difference between the two rules is
 *    then a fact on the screen, not a claim in a comment.
 */
add_filter( 'bhp_bundle_colouring_policy', 'bhp_bp_force_conservative' );
bhp_test_assert(
	4.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_mixed_qty_ship ) ),
	'1.8.23 [conservative] COUNTERFACTUAL: mixed cart, 3 books but only 2 adventures (2x Everest PB + Mariana HC) -> still $4.99, NOT free',
	$failures
);
remove_filter( 'bhp_bundle_colouring_policy', 'bhp_bp_force_conservative' );

bhp_test_assert(
	0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_mixed_qty_ship ) ),
	'FD-583 [any-three, SHIPPED]: the same cart, 3 physical books -> $0.00 FREE',
	$failures
);

$cart_mixed = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
		bhp_test_stub_item( 14, 0, 17.99 ),
	)
);
$eval = bhp_bundle_evaluate_cart( $cart_mixed );
bhp_test_assert( 2 === $eval['paperback_tier'], 'Mixed cart: paperback tier still evaluates independently', $failures );
bhp_test_assert( 0 === $eval['hardcover_tier'], 'Mixed cart: single hardcover does not qualify on its own', $failures );
bhp_test_assert( $eval['is_mixed_format'], 'Paperback + hardcover together is flagged mixed-format', $failures );

// Bug-fix regression: a mixed cart where ONE format alone reaches tier 2+
// (here, 2 distinct paperbacks + 1 hardcover) must NOT receive a bundle
// discount fee at all -- previously bhp_bundle_apply_discount_fees() only
// checked each format's own tier and would have wrongly applied the
// paperback discount despite the cart being mixed.
bhp_bundle_apply_discount_fees( $cart_mixed );
bhp_test_assert(
	empty( $cart_mixed->fees_added ),
	'Bug fix: mixed cart (2 distinct paperbacks + 1 hardcover) gets NO bundle discount fee at all',
	$failures
);

// Sanity check the fee mechanism itself on a genuinely non-mixed qualifying
// cart, so the assertion above is verified against a stub that is known to
// actually add a fee when one should apply.
bhp_bundle_apply_discount_fees( $cart_two_distinct_pb );
bhp_test_assert(
	1 === count( $cart_two_distinct_pb->fees_added ) && abs( $cart_two_distinct_pb->fees_added[0]['amount'] - ( -1.99 ) ) < 0.001,
	'Sanity check: non-mixed 2-paperback cart DOES get a -$1.99 Bundle Savings fee',
	$failures
);

$cart_price_drift = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 12.99 ), // Stale price, not the approved $11.99.
		bhp_test_stub_item( 18, 0, 11.99 ),
	)
);
bhp_test_assert(
	false === bhp_bundle_prices_match_expected( $cart_price_drift, 'paperback' ),
	'Price-drift guard detects a line item priced away from the approved $11.99',
	$failures
);

$cart_with_coupon = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
	),
	array( 'SOME-COUPON' )
);
bhp_test_assert(
	! empty( $cart_with_coupon->get_applied_coupons() ),
	'Stub cart with a coupon reports it applied (sanity check for the coupon-stacking guard)',
	$failures
);

// ---------------------------------------------------------------------
// 5. Overnight Conversion Sprint, Priority 7: mixed-cart complete-set
// rules. A complete 3-book set ALWAYS earns its discount even when the
// opposite format is also present -- only the partial 2-book discount is
// suppressed by mixing. This supersedes the old "any mixing kills every
// discount" rule tested above (that rule's own test, "2 distinct
// paperbacks + 1 hardcover gets NO fee", still holds correctly under the
// new rule too, since paperback tier there is 2, not 3).
// ---------------------------------------------------------------------

// 5a. Three paperbacks + one hardcover: complete paperback discount
// applies; the lone hardcover doesn't qualify for anything on its own.
$cart_complete_pb_plus_hc = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 333, 334, 11.99 ),
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
		bhp_test_stub_item( 14, 0, 17.99 ),
	)
);
bhp_bundle_apply_discount_fees( $cart_complete_pb_plus_hc );
bhp_test_assert(
	1 === count( $cart_complete_pb_plus_hc->fees_added )
		&& abs( $cart_complete_pb_plus_hc->fees_added[0]['amount'] - ( -3.98 ) ) < 0.001,
	'Priority 7: 3 paperbacks + 1 hardcover -> complete paperback discount (-$3.98) still applies despite the extra hardcover',
	$failures
);

// 5b. Three hardcovers + one paperback: complete hardcover discount
// applies; the lone paperback doesn't qualify for anything on its own.
$cart_complete_hc_plus_pb = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 14, 0, 17.99 ),
		bhp_test_stub_item( 17, 0, 17.99 ),
		bhp_test_stub_item( 20, 0, 17.99 ),
		bhp_test_stub_item( 15, 0, 11.99 ),
	)
);
bhp_bundle_apply_discount_fees( $cart_complete_hc_plus_pb );
bhp_test_assert(
	1 === count( $cart_complete_hc_plus_pb->fees_added )
		&& abs( $cart_complete_hc_plus_pb->fees_added[0]['amount'] - ( -4.98 ) ) < 0.001,
	'Priority 7: 3 hardcovers + 1 paperback -> complete hardcover discount (-$4.98) still applies despite the extra paperback',
	$failures
);

// 5c. Both complete sets in the same cart: both discounts apply
// simultaneously, total $8.96, never a blanket suppression.
$cart_both_complete = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 333, 334, 11.99 ),
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 18, 0, 11.99 ),
		bhp_test_stub_item( 14, 0, 17.99 ),
		bhp_test_stub_item( 17, 0, 17.99 ),
		bhp_test_stub_item( 20, 0, 17.99 ),
	)
);
bhp_bundle_apply_discount_fees( $cart_both_complete );
$both_complete_total = 0;
foreach ( $cart_both_complete->fees_added as $fee ) {
	$both_complete_total += $fee['amount'];
}
bhp_test_assert(
	2 === count( $cart_both_complete->fees_added ) && abs( $both_complete_total - ( -8.96 ) ) < 0.001,
	'Priority 7: 3 paperbacks + 3 hardcovers -> both complete-set discounts apply, total -$8.96',
	$failures
);

// 5d. Two hardcovers + one paperback: partial hardcover discount is
// suppressed (mirrors the already-tested "2 paperbacks + 1 hardcover"
// case, checked here for the opposite format).
$cart_two_hc_one_pb = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 14, 0, 17.99 ),
		bhp_test_stub_item( 17, 0, 17.99 ),
		bhp_test_stub_item( 15, 0, 11.99 ),
	)
);
bhp_bundle_apply_discount_fees( $cart_two_hc_one_pb );
bhp_test_assert(
	empty( $cart_two_hc_one_pb->fees_added ),
	'Priority 7: 2 hardcovers + 1 paperback -> NO partial hardcover discount (mixed, tier 2 only)',
	$failures
);

// 5e. One paperback + one hardcover: no discount at all (both tiers 0).
$cart_one_each = new BHP_Test_Stub_Cart(
	array(
		bhp_test_stub_item( 15, 0, 11.99 ),
		bhp_test_stub_item( 14, 0, 17.99 ),
	)
);
bhp_bundle_apply_discount_fees( $cart_one_each );
bhp_test_assert(
	empty( $cart_one_each->fees_added ),
	'Priority 7: 1 paperback + 1 hardcover -> no discount at all',
	$failures
);

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL BUNDLE PRICING TESTS PASSED\n";
	exit( 0 );
}

echo count( $failures ) . " TEST(S) FAILED:\n";
foreach ( $failures as $label ) {
	echo " - {$label}\n";
}
exit( 1 );
