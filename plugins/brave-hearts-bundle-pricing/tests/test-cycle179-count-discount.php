<?php
/**
 * Brave Hearts Bundle Pricing — THE MULTI-BUY DISCOUNT KEYS ON A COUNT OF
 * BOOKS, NOT A COUNT OF TITLES. Plugin 1.8.87, `CYCLE179-LD-PLUGIN-1.8.87`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cycle179-count-discount.php --user=1 --url=<site>
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * FOUNDER RULING, SEAL 1359, 2026-09-08, verbatim as relayed to the build
 * session by `chief-of-staff`. ⚠️ RELAYED, NOT WITNESSED FIRST-HAND by the
 * agent that wrote this file:
 *
 *   "If they buy any two books they should get the discount - doesnt matter."
 *
 * ⛔ THE HALF OF A RULING LIKE THIS THAT IS EASIEST TO BREAK IS THE HALF THAT
 *    DID NOT MOVE. Widening the discount is one line; widening it TOO FAR is
 *    the same line. So this suite asserts, as hard as the new behaviour:
 *      · not one figure in `bhp_bundle_rules()` changed (section 1);
 *      · a Complete-Collection AUDIENCE COUPON still needs three distinct
 *        adventures and is NOT earned by three copies of one title (§6);
 *      · `is_complete_collection`, `distinct_adventures` and `has_any_book`
 *        are still TITLE questions with title answers (§5);
 *      · a coloring book is still a physical book for SHIPPING and still
 *        outside the chapter DISCOUNT tier (§7).
 *
 * Exits non-zero on any failure. Pure functions plus a stub cart only — no
 * WooCommerce session, no order, no product record is touched, nothing is
 * written anywhere.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cd_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Same minimal stand-in the sibling suites use. Redeclared under its own name
 * rather than shared, because each test file is eval'd on its own and must run
 * standalone.
 */
class BHP_CountDiscount_Stub_Cart {
	private $items;
	private $coupons;
	public $fees_added = array();

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
	public function add_fee( $label, $amount, $taxable = false ) {
		$this->fees_added[] = array( 'label' => $label, 'amount' => $amount, 'taxable' => $taxable );
	}
}

function bhp_cd_item( $product_id, $variation_id, $price, $quantity = 1 ) {
	$product = new class( $price ) {
		private $price;
		public function __construct( $price ) {
			$this->price = $price;
		}
		public function get_price() {
			return $this->price;
		}
		public function is_on_sale() {
			return false;
		}
	};
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $quantity,
		'data'         => $product,
	);
}

function bhp_cd_cart( array $items, array $coupons = array() ) {
	return new BHP_CountDiscount_Stub_Cart( $items, $coupons );
}
function bhp_cd_eval( array $items ) {
	return bhp_bundle_evaluate_cart( bhp_cd_cart( $items ) );
}
function bhp_cd_ship( array $items ) {
	return bhp_bundle_shipping_amount( bhp_cd_eval( $items ) );
}
/**
 * The real fee path, not a re-implementation of it: this runs
 * `bhp_bundle_apply_discount_fees()` against a stub cart and returns the
 * negative fees it actually added. If the discount is not applied here, it is
 * not applied at checkout either.
 *
 * @return array label => amount (negative dollars).
 */
function bhp_cd_fees( array $items, array $coupons = array() ) {
	$cart = bhp_cd_cart( $items, $coupons );
	bhp_bundle_apply_discount_fees( $cart );
	$out = array();
	foreach ( $cart->fees_added as $fee ) {
		$out[ $fee['label'] ] = round( (float) $fee['amount'], 2 );
	}
	return $out;
}
/** Total of every Bundle Savings fee on a cart, as a positive dollar figure. */
function bhp_cd_saving( array $items ) {
	$total = 0.0;
	foreach ( bhp_cd_fees( $items ) as $label => $amount ) {
		if ( 0 === strpos( $label, 'Bundle Savings' ) ) {
			$total += -1 * $amount;
		}
	}
	return round( $total, 2 );
}
/*
 * A named callback, not a closure: `remove_filter()` cannot remove an
 * anonymous function, so a closure would leak a forced policy into every
 * assertion after the block that set it. The sibling suites make the same
 * point; it is repeated here because it is repeatedly load-bearing.
 */
function bhp_cd_force_conservative() {
	return 'conservative';
}

// Catalog IDs, from bhp_bundle_catalog(). Named so each cart reads as a
// sentence rather than as a list of integers.
$PB_MARIANA = array( 333, 334 );
$PB_EVEREST = array( 15, 0 );
$PB_AMAZON  = array( 18, 0 );
$HC_MARIANA = array( 14, 0 );
$HC_EVEREST = array( 17, 0 );
$HC_AMAZON  = array( 20, 0 );

$PB = 11.99;
$HC = 17.99;

echo "\n=== 1. THE APPROVED TABLE DID NOT MOVE ===\n";
/*
 * ⛔ FIRST, AND DELIBERATELY. Seal 1359 changes WHICH CARTS REACH a row. If a
 *    row itself moved, that would be a different decision wearing this one's
 *    name, and every assertion below would be measuring the wrong thing.
 */
$pb_rules = bhp_bundle_rules( 'paperback' );
$hc_rules = bhp_bundle_rules( 'hardcover' );
bhp_cd_assert( 1.99 === (float) $pb_rules[2]['discount'], '1. paperback tier-2 discount is still $1.99', $failures );
bhp_cd_assert( 2.99 === (float) $pb_rules[2]['shipping'], '1. paperback tier-2 shipping is still $2.99', $failures );
bhp_cd_assert( 3.98 === (float) $pb_rules[3]['discount'], '1. paperback tier-3 discount is still $3.98', $failures );
bhp_cd_assert( 0.00 === (float) $pb_rules[3]['shipping'], '1. paperback tier-3 shipping is still $0.00', $failures );
bhp_cd_assert( 2.99 === (float) $hc_rules[2]['discount'], '1. hardcover tier-2 discount is still $2.99', $failures );
bhp_cd_assert( 3.99 === (float) $hc_rules[2]['shipping'], '1. hardcover tier-2 shipping is still $3.99', $failures );
bhp_cd_assert( 4.98 === (float) $hc_rules[3]['discount'], '1. hardcover tier-3 discount is still $4.98', $failures );
bhp_cd_assert( 0.00 === (float) $hc_rules[3]['shipping'], '1. hardcover tier-3 shipping is still $0.00', $failures );
bhp_cd_assert( 1.99 === (float) bhp_bundle_single_shipping( 'paperback' ), '1. single paperback shipping is still $1.99', $failures );
bhp_cd_assert( 2.99 === (float) bhp_bundle_single_shipping( 'hardcover' ), '1. single hardcover shipping is still $2.99', $failures );
bhp_cd_assert( 11.99 === (float) bhp_bundle_expected_price( 'paperback' ), '1. expected paperback price is still $11.99', $failures );
bhp_cd_assert( 17.99 === (float) bhp_bundle_expected_price( 'hardcover' ), '1. expected hardcover price is still $17.99', $failures );

echo "\n=== 2. THE TIER FUNCTION ITSELF ===\n";
bhp_cd_assert( 0 === bhp_bundle_qualifying_tier_by_count( 0 ), '2. 0 books -> tier 0', $failures );
bhp_cd_assert( 0 === bhp_bundle_qualifying_tier_by_count( 1 ), '2. 1 book  -> tier 0', $failures );
bhp_cd_assert( 2 === bhp_bundle_qualifying_tier_by_count( 2 ), '2. 2 books -> tier 2', $failures );
bhp_cd_assert( 3 === bhp_bundle_qualifying_tier_by_count( 3 ), '2. 3 books -> tier 3', $failures );
bhp_cd_assert( 3 === bhp_bundle_qualifying_tier_by_count( 4 ), '2. 4 books -> tier 3 (no tier above 3 exists, none is invented)', $failures );

echo "\n=== 3. PAPERBACK DUPLICATES: THE RULING ITSELF ===\n";
$two_dupe_pb   = array( bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 2 ) );
$three_dupe_pb = array( bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 3 ) );
$eval2         = bhp_cd_eval( $two_dupe_pb );
$eval3         = bhp_cd_eval( $three_dupe_pb );

bhp_cd_assert( 2 === (int) $eval2['paperback_count'], '3. 2x Mariana PB -> paperback_count 2', $failures );
bhp_cd_assert( 2 === (int) $eval2['paperback_tier'], '3. 2x Mariana PB -> paperback_tier 2 (was 0 before 1.8.87)', $failures );
bhp_cd_assert( 1.99 === bhp_cd_saving( $two_dupe_pb ), '3. ⭐ 2x Mariana PB -> the real fee path applies -$1.99', $failures );
bhp_cd_assert( 2.99 === (float) bhp_cd_ship( $two_dupe_pb ), '3. ⭐ 2x Mariana PB -> shipping $2.99, the two-book tier (was $1.99 before 1.8.87)', $failures );

bhp_cd_assert( 3 === (int) $eval3['paperback_count'], '3. 3x Mariana PB -> paperback_count 3', $failures );
bhp_cd_assert( 3 === (int) $eval3['paperback_tier'], '3. 3x Mariana PB -> paperback_tier 3', $failures );
bhp_cd_assert( 3.98 === bhp_cd_saving( $three_dupe_pb ), '3. ⭐ 3x Mariana PB -> the real fee path applies -$3.98, the Collection price', $failures );
bhp_cd_assert( 0.00 === (float) bhp_cd_ship( $three_dupe_pb ), '3. ⭐ 3x Mariana PB -> shipping $0.00', $failures );
/*
 * ⭐ THE COLLECTION PRICE, STATED AS THE NUMBER A CUSTOMER SEES rather than as
 *    a discount. 3 x $11.99 = $35.97, less $3.98, is $31.99 — the same figure
 *    `/complete-collection/` advertises for three distinct paperbacks.
 */
bhp_cd_assert( 31.99 === round( ( 3 * $PB ) - bhp_cd_saving( $three_dupe_pb ), 2 ), '3. ⭐ 3x Mariana PB -> set price $31.99, identical to the three-title Collection', $failures );

// Two copies of one title split across two line items must count the same as
// one line item of quantity 2. A cart can hold either shape.
$two_lines_pb = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 1 ),
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 1 ),
);
bhp_cd_assert( 1.99 === bhp_cd_saving( $two_lines_pb ), '3. two SEPARATE lines of the same title also earn -$1.99 (quantity is summed, not per-line)', $failures );

// Four copies stays at tier 3.
$four_dupe_pb = array( bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 4 ) );
bhp_cd_assert( 3.98 === bhp_cd_saving( $four_dupe_pb ), '3. 4x Mariana PB -> still -$3.98, no invented tier 4', $failures );

// The pre-ruling shape must not regress.
$two_distinct_pb = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB ),
	bhp_cd_item( $PB_EVEREST[0], $PB_EVEREST[1], $PB ),
);
$three_distinct_pb = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB ),
	bhp_cd_item( $PB_EVEREST[0], $PB_EVEREST[1], $PB ),
	bhp_cd_item( $PB_AMAZON[0], $PB_AMAZON[1], $PB ),
);
bhp_cd_assert( 1.99 === bhp_cd_saving( $two_distinct_pb ), '3. REGRESSION: 2 distinct paperbacks still -$1.99', $failures );
bhp_cd_assert( 2.99 === (float) bhp_cd_ship( $two_distinct_pb ), '3. REGRESSION: 2 distinct paperbacks still ship $2.99', $failures );
bhp_cd_assert( 3.98 === bhp_cd_saving( $three_distinct_pb ), '3. REGRESSION: 3 distinct paperbacks still -$3.98', $failures );
bhp_cd_assert( 0.00 === (float) bhp_cd_ship( $three_distinct_pb ), '3. REGRESSION: 3 distinct paperbacks still ship free', $failures );
// One book is still one book.
$one_pb = array( bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 1 ) );
bhp_cd_assert( 0.00 === bhp_cd_saving( $one_pb ), '3. REGRESSION: 1 paperback earns no bundle discount', $failures );
bhp_cd_assert( 1.99 === (float) bhp_cd_ship( $one_pb ), '3. REGRESSION: 1 paperback still ships $1.99', $failures );

echo "\n=== 4. HARDCOVER DUPLICATES AND MIXED CARTS ===\n";
$two_dupe_hc   = array( bhp_cd_item( $HC_MARIANA[0], $HC_MARIANA[1], $HC, 2 ) );
$three_dupe_hc = array( bhp_cd_item( $HC_MARIANA[0], $HC_MARIANA[1], $HC, 3 ) );
bhp_cd_assert( 2.99 === bhp_cd_saving( $two_dupe_hc ), '4. ⭐ 2x Mariana HC -> -$2.99', $failures );
bhp_cd_assert( 3.99 === (float) bhp_cd_ship( $two_dupe_hc ), '4. ⭐ 2x Mariana HC -> shipping $3.99, the two-book hardcover tier', $failures );
bhp_cd_assert( 4.98 === bhp_cd_saving( $three_dupe_hc ), '4. ⭐ 3x Mariana HC -> -$4.98, the Collection price', $failures );
bhp_cd_assert( 0.00 === (float) bhp_cd_ship( $three_dupe_hc ), '4. ⭐ 3x Mariana HC -> shipping $0.00', $failures );
bhp_cd_assert( 48.99 === round( ( 3 * $HC ) - bhp_cd_saving( $three_dupe_hc ), 2 ), '4. ⭐ 3x Mariana HC -> set price $48.99, identical to the three-title Collection', $failures );

/*
 * ⛔ MIXED CARTS: THE PARTIAL 2-BOOK SUPPRESSION IS UNCHANGED, AND IT IS NOW A
 *    COUNT SUPPRESSION. The "any 2 paperbacks" offer was never advertised as
 *    compatible with a hardcover; seal 1359 says nothing about that guard, so
 *    it stands exactly as written and simply reads the new tier.
 */
$mixed_two_dupe_pb_plus_hc = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 2 ),
	bhp_cd_item( $HC_EVEREST[0], $HC_EVEREST[1], $HC, 1 ),
);
bhp_cd_assert( 0.00 === bhp_cd_saving( $mixed_two_dupe_pb_plus_hc ), '4. 2x Mariana PB + 1 HC -> partial 2-book discount still suppressed once mixed', $failures );
$mixed_three_dupe_pb_plus_hc = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 3 ),
	bhp_cd_item( $HC_EVEREST[0], $HC_EVEREST[1], $HC, 1 ),
);
bhp_cd_assert( 3.98 === bhp_cd_saving( $mixed_three_dupe_pb_plus_hc ), '4. ⭐ 3x Mariana PB + 1 HC -> the COMPLETE-set rule applies once earned, mixed or not: -$3.98', $failures );
// Both formats at three copies each earns both complete-set discounts.
$both_three_dupe = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 3 ),
	bhp_cd_item( $HC_MARIANA[0], $HC_MARIANA[1], $HC, 3 ),
);
bhp_cd_assert( 8.96 === bhp_cd_saving( $both_three_dupe ), '4. 3x PB + 3x HC (all one title) -> BOTH complete-set discounts, -$3.98 + -$4.98', $failures );

// Mixed shipping is a count and always was.
$mixed_one_each = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 1 ),
	bhp_cd_item( $HC_EVEREST[0], $HC_EVEREST[1], $HC, 1 ),
);
bhp_cd_assert( 3.99 === (float) bhp_cd_ship( $mixed_one_each ), '4. REGRESSION: mixed cart of exactly 2 books still ships $3.99', $failures );
bhp_cd_assert( 0.00 === bhp_cd_saving( $mixed_one_each ), '4. REGRESSION: mixed cart of 1 + 1 earns no discount (no format reaches 2)', $failures );

/*
 * ⭐ THE CART THE STRUCK COMMENT NAMED. `bundle-data.php` used to argue that
 *    Mariana PB + Mariana HC + Everest PB "keeps the $4.99 mixed rate". It has
 *    not since 1.8.62 (`FD-583`, any-three). Asserted so the correction in the
 *    struck block is a measured statement rather than a claim.
 */
$struck_comment_cart = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 1 ),
	bhp_cd_item( $HC_MARIANA[0], $HC_MARIANA[1], $HC, 1 ),
	bhp_cd_item( $PB_EVEREST[0], $PB_EVEREST[1], $PB, 1 ),
);
bhp_cd_assert( 0.00 === (float) bhp_cd_ship( $struck_comment_cart ), '4. ⭐ the struck comment\'s own example (3 books, 2 adventures) ships FREE under any-three, NOT $4.99', $failures );

echo "\n=== 5. THE TITLE QUESTIONS ARE STILL ANSWERED BY TITLES ===\n";
/*
 * ⛔ THIS SECTION IS THE GUARD ON OVER-APPLICATION. Seal 1359 moved the money.
 *    Everything below is a SET question and must be unmoved.
 */
bhp_cd_assert( 1 === (int) $eval3['distinct_adventures'], '5. 3x Mariana PB -> distinct_adventures is 1, not 3', $failures );
bhp_cd_assert( empty( $eval3['is_complete_collection'] ), '5. ⭐ 3x Mariana PB is NOT a complete collection', $failures );
bhp_cd_assert( ! empty( $eval3['has_any_book'] ), '5. 3x Mariana PB does hold a book (has_any_book true)', $failures );
bhp_cd_assert( 0 === (int) $eval3['paperback_titles_tier'], '5. ⭐ 3x Mariana PB -> paperback_titles_tier is 0 (one distinct title), while paperback_tier is 3. TWO ANSWERS, TWO QUESTIONS.', $failures );
bhp_cd_assert( array( 'mariana' ) === bhp_bundle_distinct_titles_in_cart( bhp_cd_cart( $three_dupe_pb ) )['paperback'], '5. bhp_bundle_distinct_titles_in_cart() still returns ONE title for 3 copies', $failures );
$eval_3_distinct = bhp_cd_eval( $three_distinct_pb );
bhp_cd_assert( 3 === (int) $eval_3_distinct['distinct_adventures'], '5. REGRESSION: 3 distinct paperbacks -> distinct_adventures 3', $failures );
bhp_cd_assert( ! empty( $eval_3_distinct['is_complete_collection'] ), '5. REGRESSION: 3 distinct paperbacks IS a complete collection', $failures );
bhp_cd_assert( 3 === (int) $eval_3_distinct['paperback_titles_tier'], '5. REGRESSION: 3 distinct paperbacks -> paperback_titles_tier 3', $failures );

echo "\n=== 6. THE AUDIENCE COUPON IS HELD AT THREE DISTINCT ADVENTURES ===\n";
/*
 * ⛔⛔ THE MOST IMPORTANT NEGATIVE ASSERTION IN THIS FILE. An audience coupon is
 *    a Collection-only instrument with its own approval history. Seal 1359 is
 *    about the multi-buy discount and says nothing about coupon scope, so
 *    widening it by side effect would be this desk deciding a commercial
 *    question that belongs to Andrew. If `bhp_audience_coupon_cart_qualifies()`
 *    is ever repointed at `paperback_tier`, this row fails loudly.
 */
bhp_cd_assert( false === bhp_audience_coupon_cart_qualifies( bhp_cd_cart( $three_dupe_pb ) ), '6. ⛔ 3x Mariana PB does NOT qualify for a Complete-Collection audience coupon', $failures );
bhp_cd_assert( false === bhp_audience_coupon_cart_qualifies( bhp_cd_cart( $three_dupe_hc ) ), '6. ⛔ 3x Mariana HC does NOT qualify for a Complete-Collection audience coupon', $failures );
bhp_cd_assert( true === bhp_audience_coupon_cart_qualifies( bhp_cd_cart( $three_distinct_pb ) ), '6. REGRESSION: 3 DISTINCT paperbacks still qualify', $failures );
bhp_cd_assert( 'paperback' === bhp_audience_coupon_qualifying_format( bhp_cd_cart( $three_distinct_pb ) ), '6. REGRESSION: the qualifying format is still reported correctly', $failures );
bhp_cd_assert( null === bhp_audience_coupon_qualifying_format( bhp_cd_cart( $three_dupe_pb ) ), '6. ⛔ a duplicate cart reports NO qualifying format', $failures );

echo "\n=== 7. THE COLORING BOOK ===\n";
/*
 * ⭐ THE COLORING LINE IS SKU-RESOLVED AND MAY RESOLVE TO NOTHING on this
 *    environment. Rather than skip the section silently, an injected id is
 *    used — the same seam `bhp_colouring_product_ids()`'s own docblock says
 *    exists so the tier machine can be tested where no product record does.
 *
 * ⛔ 9000001 IS A FIXTURE, NOT A PRODUCT. Nothing is created, read or written;
 *    the filter is added and removed inside this block only.
 */
function bhp_cd_colouring_ids() {
	return array( 'mariana' => 9000001 );
}
add_filter( 'bhp_colouring_product_ids', 'bhp_cd_colouring_ids' );

$COLOURING = 9000001;
$dupe_pb_plus_colouring = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 2 ),
	bhp_cd_item( $COLOURING, 0, 12.99, 1 ),
);
$eval_dpc = bhp_cd_eval( $dupe_pb_plus_colouring );
bhp_cd_assert( 2 === (int) $eval_dpc['paperback_count'], '7. ⭐ 2x Mariana PB + 1 coloring -> paperback_count is 2, the coloring book is NOT in the chapter tier', $failures );
bhp_cd_assert( 3 === (int) $eval_dpc['physical_book_count'], '7. ⭐ ... and physical_book_count is 3, the coloring book IS a physical book for postage', $failures );
bhp_cd_assert( 1.99 === bhp_cd_saving( $dupe_pb_plus_colouring ), '7. ⭐ 2x Mariana PB + 1 coloring -> the -$1.99 two-book chapter discount still applies', $failures );
bhp_cd_assert( 0.00 === (float) bhp_cd_ship( $dupe_pb_plus_colouring ), '7. ⭐ ... and it ships FREE, because three physical books is the any-three threshold', $failures );

// One chapter paperback + one coloring book: no chapter discount, $2.99.
$one_pb_plus_colouring = array(
	bhp_cd_item( $PB_MARIANA[0], $PB_MARIANA[1], $PB, 1 ),
	bhp_cd_item( $COLOURING, 0, 12.99, 1 ),
);
bhp_cd_assert( 0.00 === bhp_cd_saving( $one_pb_plus_colouring ), '7. REGRESSION: 1 chapter PB + 1 coloring earns NO chapter bundle discount (the pair offer is offer-engine\'s, not this table\'s)', $failures );
bhp_cd_assert( 2.99 === (float) bhp_cd_ship( $one_pb_plus_colouring ), '7. REGRESSION: 1 chapter PB + 1 coloring still ships $2.99', $failures );

// Two copies of the coloring book alone: still no chapter discount.
$two_dupe_colouring = array( bhp_cd_item( $COLOURING, 0, 12.99, 2 ) );
bhp_cd_assert( 0.00 === bhp_cd_saving( $two_dupe_colouring ), '7. ⛔ 2x coloring book alone earns NO chapter bundle discount - the coloring line has its own offer engine', $failures );
bhp_cd_assert( 2.99 === (float) bhp_cd_ship( $two_dupe_colouring ), '7. REGRESSION: 2x coloring book still ships $2.99', $failures );

// Under the conservative policy the any-three branch is off, so the duplicate
// cart is priced on the coloring ladder instead. Asserted so the interaction
// between the two rules is measured rather than assumed.
add_filter( 'bhp_bundle_colouring_policy', 'bhp_cd_force_conservative' );
bhp_cd_assert( 4.99 === (float) bhp_cd_ship( $dupe_pb_plus_colouring ), '7. [conservative] 2x Mariana PB + 1 coloring -> $4.99, the 3-or-more coloring row', $failures );
bhp_cd_assert( 1.99 === bhp_cd_saving( $dupe_pb_plus_colouring ), '7. [conservative] ... and the -$1.99 chapter discount is unaffected by the shipping policy', $failures );
remove_filter( 'bhp_bundle_colouring_policy', 'bhp_cd_force_conservative' );

remove_filter( 'bhp_colouring_product_ids', 'bhp_cd_colouring_ids' );

echo "\n=== 8. THE PROGRESS COPY, AT EACH COUNT ===\n";
/*
 * ⛔⛔⛔ RESHAPED 2026-09-08, PLUGIN 1.8.88, SEAL 1411. THE SURFACE THESE FIVE
 *      ROWS READ WAS REMOVED, AND THE ROWS ARE REPOINTED RATHER THAN DELETED.
 *
 * ⛔ `bhp_bundle_print_progress_messages()` and its two `add_action` calls are
 *    gone from `bundle-cart.php`. They hooked `woocommerce_before_cart_table`
 *    and `woocommerce_checkout_before_order_review` — CLASSIC SHORTCODE hooks
 *    — and this store's cart and checkout are WooCommerce BLOCKS. VERIFIED
 *    LIVE on staging2 in a real browser AT 1.8.87, while the function was
 *    still present and still hooked: `.bhp-bundle-message` count 0 on both
 *    pages. ⛔ IT RENDERED TO NOBODY, SO IT WAS NEVER PROOF OF WHAT A
 *    CUSTOMER READ.
 *
 * ⛔ THE SUPERSEDED NEEDLES, PRESERVED RATHER THAN QUIETLY SWAPPED:
 *
 *      ~~$cart_src = file_get_contents( ... 'includes/bundle-cart.php' );
 *        "'add'      => 'Add another paperback and save $1.99.'"
 *        "'saved'    => 'You saved $1.99 with your 2-book paperback set.'"
 *        "'complete' => 'Add the final adventure to complete the series and
 *                        save $3.98 total.'"
 *        "if ( 1 === $books ) { $sentence[] = $copy[ $format ]['add'];"
 *        "if ( 2 === $titles && $books < 3 ) {"~~
 *
 * ⭐⭐ WHAT THOSE ROWS WERE REALLY FOR SURVIVES INTACT. Their subject was seal
 *    1359's split — MONEY lines key on a book COUNT, SERIES lines key on
 *    distinct TITLES — and the drawer makes exactly the same split, in the
 *    surface a customer actually reads. The `$drawer_src` and `$drawer_php`
 *    rows below already asserted it there and are BYTE-UNCHANGED by 1.8.88.
 *    ⛔ NOT ONE ASSERTION ABOUT THE 1.8.87 RULING WAS DROPPED TO MAKE THIS
 *    REMOVAL PASS; the rows that vanished tested a dead surface's copy of a
 *    rule that is still tested on the live one.
 *
 * ⭐ ONE STRING WAS UNIQUE TO THE REMOVED PATH AND IS NAMED, NOT GLOSSED:
 *    'Add the final adventure to complete the series and save $3.98 total.'
 *    The drawer carries the same approved claim in its own long-standing
 *    variant, with "collection" for "series". ⛔ NOTHING IS REWRITTEN TO
 *    CLOSE THAT ONE-WORD GAP — it is reported to Andrew. The existing
 *    `$drawer_php` row below already pins the drawer variant, so a later pass
 *    cannot harmonise it silently.
 */
/*
 * PLUGIN 1.8.88 - STRIP COMMENTS BEFORE ASSERTING THAT SOMETHING IS ABSENT.
 *
 * ⛔ THIS HELPER EXISTS BECAUSE THE NAIVE ASSERTION WAS WRONG AND THE ZIP
 *    CHECK CAUGHT IT BEFORE IT SHIPPED. `bundle-cart.php` now carries a dated
 *    STRIKE that quotes the removed function signature, its two `add_action`
 *    lines and its copy table VERBATIM - that is the house additive-only
 *    discipline working as intended. A plain `strpos()` for those tokens
 *    therefore matches the STRIKE and reports the dead code as still present.
 *
 * ⭐ SO ABSENCE IS ASSERTED AGAINST CODE ONLY, VIA `token_get_all()`, WHILE
 *    PRESENCE OF THE STRIKE IS ASSERTED AGAINST THE FULL SOURCE. The two
 *    questions are different and are asked of different inputs:
 *      "is the function still declared or hooked?"  -> code only
 *      "is the removal recorded at the line?"       -> full source
 *
 * ⛔ NEVER REPLACE THIS WITH A REGEX ON `^function`. A `^`-anchored needle
 *    passes today only because the strike happens to indent its quotation; a
 *    later reflow of that comment would silently turn this suite green on a
 *    file that still declares the function.
 */
function bhp_cd_code_only( $src ) {
	$out = '';
	foreach ( token_get_all( (string) $src ) as $tok ) {
		if ( is_array( $tok ) ) {
			if ( T_COMMENT === $tok[0] || T_DOC_COMMENT === $tok[0] ) {
				continue;
			}
			$out .= $tok[1];
		} else {
			$out .= $tok;
		}
	}
	return $out;
}

$cart_src = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-cart.php' );
$cart_code = bhp_cd_code_only( $cart_src );
bhp_cd_assert( false === strpos( $cart_code, 'bhp_bundle_print_progress_messages' ), '8. ⛔ the dead classic-hook surface is REMOVED from bundle-cart.php (1.8.88, seal 1411)', $failures );
bhp_cd_assert( false !== strpos( $cart_src, 'REMOVED AT PLUGIN 1.8.88' ), '8. ...and the removal is struck and dated AT the line, not performed silently', $failures );
bhp_cd_assert( false === strpos( $cart_code, 'complete the series and save' ), '8. the "series" variant is no longer carried by bundle-cart.php, and is reported rather than substituted', $failures );

$drawer_src = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'assets/bundle-drawer.js' );
bhp_cd_assert( false !== strpos( $drawer_src, 'var books = counts[format];' ), '8. the drawer reads a book count per format', $failures );
bhp_cd_assert( false !== strpos( $drawer_src, 'if (1 === books && progressCopy[format] && progressCopy[format][1])' ), '8. ⭐ the drawer\'s "Add another paperback and save $1.99." keys on the BOOK COUNT', $failures );
bhp_cd_assert( false !== strpos( $drawer_src, 'if (3 === titles && progressCopy[format] && progressCopy[format][3])' ), '8. ⛔ the drawer\'s "Best Value - Complete Collection" stays a TITLE test', $failures );
bhp_cd_assert( false !== strpos( $drawer_src, 'counts.paperback >= 3 ? 3 : (counts.paperback >= 2 ? 2 : 0)' ), '8. the drawer\'s per-line savings tier is counted from books, matching the fee', $failures );

$drawer_php = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-drawer.php' );
bhp_cd_assert( false !== strpos( $drawer_php, "1 => 'Add another paperback and save \$1.99.'" ), '8. REGRESSION: the localized drawer copy is unedited', $failures );
bhp_cd_assert( false !== strpos( $drawer_php, "2 => 'Add the final adventure to complete the collection and save \$3.98 total.'" ), '8. REGRESSION: the localized drawer copy is unedited', $failures );
bhp_cd_assert( false !== strpos( $drawer_php, "3 => 'Best Value - Complete Paperback Collection'" ), '8. REGRESSION: the localized drawer copy is unedited', $failures );

echo "\n=== 9. THE STRUCK COMMENTS ARE STRUCK, AT THE LINE ===\n";
/*
 * ⛔ THE METHOD FINDING THIS COMPANY HAS ALREADY PAID FOR: a correction placed
 *    far from the rule it corrects is not a correction. This section asserts
 *    the old rule is no longer stated as live doctrine anywhere in the file,
 *    and that the ruling that replaced it is quoted at the same place.
 */
$data_src = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-data.php' );
bhp_cd_assert( false !== strpos( $data_src, 'STRUCK 2026-09-08, PLUGIN 1.8.87' ), '9. bundle-data.php carries the dated strike marker', $failures );
bhp_cd_assert( 2 === substr_count( $data_src, 'STRUCK 2026-09-08, PLUGIN 1.8.87' ), '9. ⭐ BOTH flagged comment blocks are struck, not just one', $failures );
bhp_cd_assert( 3 <= substr_count( $data_src, 'SEAL 1359' ) + substr_count( $data_src, 'seal 1359' ), '9. seal 1359 is cited beside the struck text', $failures );
/*
 * ⛔ THE QUOTE WRAPS ACROSS TWO COMMENT LINES in bundle-data.php, so it is
 *    asserted in the halves it is actually written in rather than as one
 *    contiguous string. ⭐ "doesnt" IS HIS SPELLING AND IT IS NOT CORRECTED
 *    ANYWHERE. A quote tidied into "doesn't" is no longer a quote, and the
 *    second row is what stops a future pass tidying it.
 */
bhp_cd_assert( false !== strpos( $data_src, 'If they buy any two books they should get the discount - doesnt' ), '9. the founder quote is present, first half', $failures );
bhp_cd_assert( false === strpos( $data_src, "doesn't matter" ), '9. his spelling "doesnt" is nowhere silently corrected', $failures );
// The old sentences survive only inside strike markers.
bhp_cd_assert( false !== strpos( $data_src, '~~"the Phase 4 rule is explicit that two copies of the same title never' ), '9. the old 2-book sentence is preserved STRUCK, not deleted', $failures );
bhp_cd_assert( false !== strpos( $data_src, '~~"⛔ IT IS A UNION OF TITLES, NEVER A COUNT OF BOOKS' ), '9. the old union-of-titles argument is preserved STRUCK, not deleted', $failures );
// And nowhere as live doctrine.
bhp_cd_assert( false === strpos( $data_src, ' * ⛔ IT IS A UNION OF TITLES, NEVER A COUNT OF BOOKS, and that is the whole' ), '9. ⛔ the union-of-titles paragraph no longer stands as live doctrine', $failures );

echo "\n";
if ( empty( $failures ) ) {
	echo "ALL PASS (" . 0 . " failures)\n";
	exit( 0 );
}
echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
