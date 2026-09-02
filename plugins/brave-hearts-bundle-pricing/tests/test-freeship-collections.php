<?php
/**
 * Brave Hearts Bundle Pricing — FREE COLLECTION SHIPPING (1.8.23).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-freeship-collections.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore's ruling of 2026-08-04, relayed to the build session and
 * recorded at `Business OS\WORKING-DRAFTS\chief-of-staff\
 * OVERNIGHT-EXECUTION-REGISTER-2026-08-04.md` MESSAGE 39: **"Option B
 * approved, CPA table adjusted."** Option B is defined in
 * `WORKING-DRAFTS\finance-analytics\DRAFT-2026-08-04-FREESHIP-ECONOMICS.md`
 * §4 as *absorb the shipping in full at UNCHANGED collection pricing*.
 * ⚠ RELAYED, not witnessed by the agent that wrote this file.
 *
 * ⛔ THE HALF OF THE RULING THAT IS EASIEST TO BREAK IS THE HALF THAT SAYS
 *    "UNCHANGED". A change that made collections free AND moved a price
 *    would look identical in a smoke test and would be a different decision.
 *    §1 below therefore asserts the unchanged half as hard as the new half.
 *
 * Exits non-zero on any failure. Pure functions plus a stub cart only — no
 * WooCommerce session, no order, no product record is touched.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_fs_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Same minimal stand-in the sibling suites use. Redeclared under its own
 * name rather than shared, because each test file is eval'd on its own and
 * must run standalone.
 */
class BHP_FreeShip_Stub_Cart {
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

function bhp_fs_item( $product_id, $variation_id, $price, $quantity = 1 ) {
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

function bhp_fs_cart( array $items, array $coupons = array() ) {
	return new BHP_FreeShip_Stub_Cart( $items, $coupons );
}
function bhp_fs_ship( array $items ) {
	return bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( bhp_fs_cart( $items ) ) );
}
/*
 * ⭐ 1.8.62 — A NAMED CALLBACK, NOT A CLOSURE, AND THAT IS DELIBERATE:
 *    `remove_filter()` cannot remove an anonymous function, so a closure here
 *    would leak the forced policy into every assertion after the block that
 *    set it — silently turning later rows into tests of a policy the store
 *    does not run. Naming it is what makes the scoping real.
 */
function bhp_fs_force_conservative() {
	return 'conservative';
}

// Catalog IDs, from bhp_bundle_catalog(). Named so each cart below reads as
// a sentence rather than as a list of integers.
$PB_MARIANA = array( 333, 334 );
$PB_EVEREST = array( 15, 0 );
$PB_AMAZON  = array( 18, 0 );
$HC_MARIANA = array( 14, 0 );
$HC_EVEREST = array( 17, 0 );
$HC_AMAZON  = array( 20, 0 );

$pb = function ( $ids, $qty = 1 ) {
	return bhp_fs_item( $ids[0], $ids[1], 11.99, $qty );
};
$hc = function ( $ids, $qty = 1 ) {
	return bhp_fs_item( $ids[0], $ids[1], 17.99, $qty );
};

// =====================================================================
// 1. THE UNCHANGED HALF OF THE RULING
// =====================================================================
$pb_rules = bhp_bundle_rules( 'paperback' );
$hc_rules = bhp_bundle_rules( 'hardcover' );

bhp_fs_assert( 3.98 === $pb_rules[3]['discount'], '1. PB collection discount UNCHANGED at -$3.98', $failures );
bhp_fs_assert( 4.98 === $hc_rules[3]['discount'], '1. HC collection discount UNCHANGED at -$4.98', $failures );
bhp_fs_assert( 'Save $3.98' === $pb_rules[3]['save'], '1. PB "Save $3.98" badge UNCHANGED (it describes the discount, not shipping)', $failures );
bhp_fs_assert( 'Save $4.98' === $hc_rules[3]['save'], '1. HC "Save $4.98" badge UNCHANGED', $failures );
bhp_fs_assert(
	abs( ( ( 3 * bhp_bundle_expected_price( 'paperback' ) ) - $pb_rules[3]['discount'] ) - 31.99 ) < 0.001,
	'1. PB collection PRICE still resolves to $31.99',
	$failures
);
bhp_fs_assert(
	abs( ( ( 3 * bhp_bundle_expected_price( 'hardcover' ) ) - $hc_rules[3]['discount'] ) - 48.99 ) < 0.001,
	'1. HC collection PRICE still resolves to $48.99',
	$failures
);
bhp_fs_assert( 11.99 === bhp_bundle_expected_price( 'paperback' ), '1. PB list price UNCHANGED at $11.99', $failures );
bhp_fs_assert( 17.99 === bhp_bundle_expected_price( 'hardcover' ), '1. HC list price UNCHANGED at $17.99', $failures );

// =====================================================================
// 2. THE FULL SHIPPING MATRIX — every tier, in one place
// =====================================================================
bhp_fs_assert( 1.99 === bhp_fs_ship( array( $pb( $PB_EVEREST ) ) ), '2. 1 paperback -> $1.99 (unchanged)', $failures );
bhp_fs_assert( 2.99 === bhp_fs_ship( array( $hc( $HC_MARIANA ) ) ), '2. 1 hardcover -> $2.99 (unchanged)', $failures );
bhp_fs_assert(
	2.99 === bhp_fs_ship( array( $pb( $PB_EVEREST ), $pb( $PB_AMAZON ) ) ),
	'2. 2 distinct paperbacks -> $2.99 (unchanged)',
	$failures
);
bhp_fs_assert(
	3.99 === bhp_fs_ship( array( $hc( $HC_MARIANA ), $hc( $HC_EVEREST ) ) ),
	'2. 2 distinct hardcovers -> $3.99 (unchanged)',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ) ) ),
	'2. COMPLETE PAPERBACK COLLECTION -> $0.00 FREE',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $hc( $HC_MARIANA ), $hc( $HC_EVEREST ), $hc( $HC_AMAZON ) ) ),
	'2. COMPLETE HARDCOVER COLLECTION -> $0.00 FREE',
	$failures
);
bhp_fs_assert(
	3.99 === bhp_fs_ship( array( $pb( $PB_EVEREST ), $hc( $HC_MARIANA ) ) ),
	'2. Mixed, 2 books -> $3.99 (unchanged)',
	$failures
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.62 — THIS ASSERTION IS NOW POLICY-SCOPED, BY FOUNDER RULING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ IT IS NOT DELETED, NOT WEAKENED AND NOT "FIXED TO PASS". The rule it
 *    tests — three copies of one title is ONE adventure and never a collection
 *    — is STILL TRUE, and it is still asserted below, verbatim, under the
 *    policy it was written for.
 *
 * ⭐ WHAT MOVED IS THE DEFAULT. `FD-583` (PART 66 §66.8, read at source):
 *    *"any 3 books"*, DUPLICATES INCLUDED. So three copies of one paperback
 *    now SHIP FREE while remaining two titles short of a collection for every
 *    DISCOUNT purpose. ⭐ Both halves are asserted, because that split is
 *    precisely the thing a future reader would otherwise get wrong.
 */
add_filter( 'bhp_bundle_colouring_policy', 'bhp_fs_force_conservative' );
bhp_fs_assert(
	1.99 === bhp_fs_ship( array( $pb( $PB_EVEREST, 3 ) ) ),
	'2. [conservative] 3 copies of ONE paperback -> $1.99 single rate, NOT free (never a collection)',
	$failures
);
remove_filter( 'bhp_bundle_colouring_policy', 'bhp_fs_force_conservative' );

bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_EVEREST, 3 ) ) ),
	'2. [any-three, FD-583] 3 copies of ONE paperback -> $0.00 FREE (duplicates count)',
	$failures
);
bhp_fs_assert(
	1 === count( bhp_bundle_distinct_adventures_in_cart( bhp_fs_cart( array( $pb( $PB_EVEREST, 3 ) ) ) ) ),
	'2. ...and it is STILL one adventure, so it is STILL not a collection for discount purposes',
	$failures
);

// =====================================================================
// 3. THE MIXED-FORMAT EDGE CASE, decided by this build
// =====================================================================
/*
 * ⭐ THE DECISION, RECORDED WHERE IT IS TESTED: a cart holding all three
 *    ADVENTURES ships free regardless of which format each one is in.
 *    The `chief-of-staff` direction for this build: mixed 3-distinct-book carts ship
 *    free as part of the same bundle family. The customer has the complete
 *    collection and it goes in one shipment.
 *
 * ⛔ IT IS A UNION OF TITLES, NOT A BOOK COUNT, and §4 below is the proof.
 */
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $hc( $HC_AMAZON ) ) ),
	'3. Mixed complete collection (2 PB + 1 HC, three distinct adventures) -> $0.00 FREE',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $hc( $HC_MARIANA ), $hc( $HC_EVEREST ), $pb( $PB_AMAZON ) ) ),
	'3. Mixed complete collection the other way round (2 HC + 1 PB) -> $0.00 FREE',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ), $hc( $HC_MARIANA ) ) ),
	'3. Complete PB set plus a spare hardcover -> $0.00 FREE (was $4.99 on the mixed count table)',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship(
		array(
			$pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ),
			$hc( $HC_MARIANA ), $hc( $HC_EVEREST ), $hc( $HC_AMAZON ),
		)
	),
	'3. Both complete sets in one cart -> $0.00 FREE',
	$failures
);

// =====================================================================
// 4. COUNTERFACTUAL PROOF — shipping was not simply zeroed
// =====================================================================
/*
 * ⭐ EACH PAIR BELOW HOLDS THE BOOK COUNT FIXED AND CHANGES ONLY WHETHER THE
 *    CART CONTAINS THREE DISTINCT ADVENTURES. If 1.8.23 had zeroed shipping
 *    globally, or keyed it off "three or more books", or off "mixed cart",
 *    every second assertion here would fail. They pass, so the rule really
 *    is the collection and nothing else.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.62 — THE WHOLE COUNTERFACTUAL BLOCK NOW RUNS UNDER `conservative`,
 *     AND THAT IS THE HONEST PLACE FOR IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE PARAGRAPH ABOVE IS PRESERVED VERBATIM AND STILL DESCRIBES EXACTLY
 *    WHAT THESE THREE ROWS PROVE: that 1.8.23 keyed free shipping on DISTINCT
 *    ADVENTURES and not on a book count. ⭐ That property of the collection
 *    rule is unchanged and is still worth proving.
 *
 * ⭐ WHAT CHANGED IS WHICH RULE THE STORE RUNS BY DEFAULT. `FD-583` keys free
 *    shipping on a BOOK COUNT of 3+, so under the shipped default every one of
 *    these carts now ships free. ⛔ Forcing `conservative` here is NOT hiding
 *    that — the new §4b immediately below asserts the shipped behaviour on the
 *    SAME three carts, so both rules are proved on identical inputs and the
 *    difference between them is visible in one screen.
 */
add_filter( 'bhp_bundle_colouring_policy', 'bhp_fs_force_conservative' );
bhp_fs_assert(
	4.99 === bhp_fs_ship( array( $pb( $PB_EVEREST, 2 ), $hc( $HC_MARIANA ) ) ),
	'4. [conservative] COUNTERFACTUAL: 3 books, 2 adventures (2x Everest PB + Mariana HC) -> $4.99, NOT free',
	$failures
);
bhp_fs_assert(
	4.99 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $hc( $HC_MARIANA ), $pb( $PB_EVEREST ) ) ),
	'4. [conservative] COUNTERFACTUAL: 3 books, 2 adventures across formats (Mariana twice) -> $4.99, NOT free',
	$failures
);
bhp_fs_assert(
	2.99 === bhp_fs_ship( array( $pb( $PB_EVEREST, 5 ), $pb( $PB_AMAZON, 5 ) ) ),
	'4. [conservative] COUNTERFACTUAL: 10 books but only 2 adventures -> $2.99 tier-2 rate, NOT free',
	$failures
);
remove_filter( 'bhp_bundle_colouring_policy', 'bhp_fs_force_conservative' );

/*
 * §4b · ⭐⭐ THE SHIPPED RULE, ON THE SAME THREE CARTS. `FD-583`.
 *
 * ⛔ THE POINT OF REUSING THE IDENTICAL CARTS is that the delta between the
 *    two policies is then a fact on the screen rather than a claim in a
 *    comment. Every one of these is >= 3 PHYSICAL BOOKS, so every one is free.
 */
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_EVEREST, 2 ), $hc( $HC_MARIANA ) ) ),
	'4b. [any-three, FD-583] 3 books, 2 adventures -> $0.00 FREE',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $hc( $HC_MARIANA ), $pb( $PB_EVEREST ) ) ),
	'4b. [any-three, FD-583] 3 books across formats, 2 adventures -> $0.00 FREE',
	$failures
);
bhp_fs_assert(
	0.00 === bhp_fs_ship( array( $pb( $PB_EVEREST, 5 ), $pb( $PB_AMAZON, 5 ) ) ),
	'4b. [any-three, FD-583] 10 books, 2 adventures -> $0.00 FREE',
	$failures
);
/*
 * ⛔ AND THE FLOOR IS STILL A FLOOR. Two books is not three, under either
 *    policy — so `FD-583` did not simply zero shipping, which is the same
 *    counterfactual discipline the block above applies to 1.8.23.
 */
bhp_fs_assert(
	3.99 === bhp_fs_ship( array( $pb( $PB_EVEREST ), $hc( $HC_MARIANA ) ) ),
	'4b. [any-three] COUNTERFACTUAL: 2 books -> $3.99, NOT free (the 3-book floor is real)',
	$failures
);
bhp_fs_assert(
	null === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( bhp_fs_cart( array() ) ) ),
	'4. COUNTERFACTUAL: empty cart returns null, not a free rate',
	$failures
);

// =====================================================================
// 5. THE EVALUATION FLAGS the rest of the plugin reads
// =====================================================================
$eval_two = bhp_bundle_evaluate_cart( bhp_fs_cart( array( $pb( $PB_EVEREST ), $pb( $PB_AMAZON ) ) ) );
bhp_fs_assert( 2 === $eval_two['distinct_adventures'], '5. 2 distinct paperbacks -> distinct_adventures = 2', $failures );
bhp_fs_assert( false === $eval_two['is_complete_collection'], '5. 2 distinct paperbacks -> is_complete_collection false', $failures );

$eval_mixed_two = bhp_bundle_evaluate_cart( bhp_fs_cart( array( $pb( $PB_EVEREST ), $hc( $HC_AMAZON ) ) ) );
bhp_fs_assert( 2 === $eval_mixed_two['distinct_adventures'], '5. 1 PB + 1 HC of different titles -> distinct_adventures = 2', $failures );

$eval_dupe = bhp_bundle_evaluate_cart( bhp_fs_cart( array( $pb( $PB_MARIANA ), $hc( $HC_MARIANA ) ) ) );
bhp_fs_assert( 1 === $eval_dupe['distinct_adventures'], '5. Same title in both formats -> distinct_adventures = 1, not 2', $failures );

$eval_full = bhp_bundle_evaluate_cart( bhp_fs_cart( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $hc( $HC_AMAZON ) ) ) );
bhp_fs_assert( true === $eval_full['is_complete_collection'], '5. Mixed three adventures -> is_complete_collection true', $failures );
bhp_fs_assert( true === $eval_full['is_mixed_format'], '5. ...and is_mixed_format is still true (the flags are independent)', $failures );

// =====================================================================
// 6. THE DISCOUNT SIDE IS UNTOUCHED BY THE SHIPPING CHANGE
// =====================================================================
/*
 * ⛔ THE MOST PLAUSIBLE WAY THIS BUILD COULD HAVE GONE WRONG: making the
 *    mixed complete-collection cart free ALSO handing it a bundle discount
 *    it never earned. Free shipping and the Bundle Savings fee are separate
 *    rules and must stay separate.
 */
$cart_mixed_free = bhp_fs_cart( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $hc( $HC_AMAZON ) ) );
bhp_bundle_apply_discount_fees( $cart_mixed_free );
bhp_fs_assert(
	empty( $cart_mixed_free->fees_added ),
	'6. Mixed 3-adventure cart ships free but earns NO bundle discount (no format has a complete set)',
	$failures
);

$cart_pb_set = bhp_fs_cart( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ) ) );
bhp_bundle_apply_discount_fees( $cart_pb_set );
bhp_fs_assert(
	1 === count( $cart_pb_set->fees_added ) && abs( $cart_pb_set->fees_added[0]['amount'] - ( -3.98 ) ) < 0.001,
	'6. Complete PB set still earns exactly one -$3.98 Bundle Savings fee',
	$failures
);

$cart_hc_set = bhp_fs_cart( array( $hc( $HC_MARIANA ), $hc( $HC_EVEREST ), $hc( $HC_AMAZON ) ) );
bhp_bundle_apply_discount_fees( $cart_hc_set );
bhp_fs_assert(
	1 === count( $cart_hc_set->fees_added ) && abs( $cart_hc_set->fees_added[0]['amount'] - ( -4.98 ) ) < 0.001,
	'6. Complete HC set still earns exactly one -$4.98 Bundle Savings fee',
	$failures
);

// Audience-coupon eligibility is a SINGLE-FORMAT test and must not have been
// widened by the free-shipping change.
bhp_fs_assert(
	true === bhp_audience_coupon_cart_qualifies( bhp_fs_cart( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ) ) ) ),
	'6. Audience coupon still qualifies on a single-format complete collection',
	$failures
);
bhp_fs_assert(
	false === bhp_audience_coupon_cart_qualifies( bhp_fs_cart( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $hc( $HC_AMAZON ) ) ) ),
	'6. Audience coupon still REFUSED on a mixed cart, even though that cart now ships free',
	$failures
);

// =====================================================================
// 7. THE ADD-ON ALLOWLIST MUST NOT AFFECT QUALIFICATION
// =====================================================================
/*
 * The activity book is exempt from the has_unrelated fail-safe. A $5 digital
 * file has no weight, must not move a shipping tier, and must not be able to
 * take free shipping away from a customer who has earned it.
 *
 * ⚠ HONEST LIMIT OF THIS SECTION: the exemption resolves a SKU to a live
 *   product id. Where that SKU does not exist (production today), the
 *   allowlist is empty and the add-on assertions below are skipped rather
 *   than faked — a skipped check is reported as skipped.
 */
$addon_ids = bhp_bundle_addon_product_ids();
if ( empty( $addon_ids ) ) {
	echo "SKIP: 7. add-on interaction — SKU BHP-ACTIVITY-BOOK-01 resolves to no product on this environment\n";
} else {
	$addon_item = bhp_fs_item( $addon_ids[0], 0, 5.00 );

	bhp_fs_assert(
		0.00 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ), $addon_item ) ),
		'7. Complete PB collection + activity book -> still $0.00 FREE',
		$failures
	);
	bhp_fs_assert(
		0.00 === bhp_fs_ship( array( $hc( $HC_MARIANA ), $hc( $HC_EVEREST ), $hc( $HC_AMAZON ), $addon_item ) ),
		'7. Complete HC collection + activity book -> still $0.00 FREE',
		$failures
	);
	bhp_fs_assert(
		0.00 === bhp_fs_ship( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $hc( $HC_AMAZON ), $addon_item ) ),
		'7. Mixed complete collection + activity book -> still $0.00 FREE',
		$failures
	);

	// The add-on adds $0 of shipping at every non-free tier too.
	bhp_fs_assert(
		1.99 === bhp_fs_ship( array( $pb( $PB_EVEREST ), $addon_item ) ),
		'7. 1 paperback + activity book -> $1.99, the add-on adds $0.00',
		$failures
	);
	bhp_fs_assert(
		2.99 === bhp_fs_ship( array( $pb( $PB_EVEREST ), $pb( $PB_AMAZON ), $addon_item ) ),
		'7. 2 paperbacks + activity book -> $2.99, the add-on adds $0.00 and does not complete a collection',
		$failures
	);

	$eval_addon = bhp_bundle_evaluate_cart( bhp_fs_cart( array( $pb( $PB_MARIANA ), $pb( $PB_EVEREST ), $pb( $PB_AMAZON ), $addon_item ) ) );
	bhp_fs_assert(
		3 === $eval_addon['distinct_adventures'] && false === $eval_addon['has_unrelated'],
		'7. Add-on counts as neither an adventure nor an unrelated item',
		$failures
	);
}

// =====================================================================
// 8. AN UNKNOWN PRODUCT STILL DISABLES THE OVERRIDE ENTIRELY
// =====================================================================
/*
 * ⛔ The fail-safe is the reason free shipping cannot leak. With something
 *    the bundle system does not recognise in the cart,
 *    bhp_bundle_override_shipping_cost() returns the rates untouched and the
 *    customer pays the zone's own rate. That must remain true even when the
 *    rest of the cart is a perfect collection.
 */
$eval_unknown = bhp_bundle_evaluate_cart(
	bhp_fs_cart(
		array(
			$pb( $PB_MARIANA ),
			$pb( $PB_EVEREST ),
			$pb( $PB_AMAZON ),
			bhp_fs_item( 999999, 0, 25.00 ),
		)
	)
);
bhp_fs_assert( true === $eval_unknown['has_unrelated'], '8. Unknown product still flags has_unrelated on a complete collection', $failures );
bhp_fs_assert(
	true === $eval_unknown['is_complete_collection'],
	'8. ...and the collection flag is still honestly true (the CALLER is what refuses to act on it)',
	$failures
);

// =====================================================================
// 9. RENDERING — "$0.00" is a price, "FREE" is the message
// =====================================================================
bhp_fs_assert( 'FREE' === bhp_bundle_shipping_display( 0.00, 'row' ), '9. Row context at $0.00 renders "FREE"', $failures );
bhp_fs_assert( 'FREE shipping' === bhp_bundle_shipping_display( 0.00 ), '9. Sentence context at $0.00 renders "FREE shipping"', $failures );
bhp_fs_assert( '$4.99 flat' === bhp_bundle_shipping_display( 4.99, 'row' ), '9. Row context at $4.99 is unchanged', $failures );
bhp_fs_assert( '$1.99 flat shipping' === bhp_bundle_shipping_display( 1.99 ), '9. Sentence context at $1.99 is unchanged', $failures );
bhp_fs_assert( false === strpos( bhp_bundle_shipping_display( 0.00 ), '0.00' ), '9. No customer-facing surface can print "$0.00" through this helper', $failures );
bhp_fs_assert( true === bhp_bundle_shipping_is_free( 0.0 ), '9. Zero is free', $failures );
bhp_fs_assert( false === bhp_bundle_shipping_is_free( 1.99 ), '9. $1.99 is not free', $failures );
bhp_fs_assert( true === bhp_bundle_shipping_is_free( 0.004 ), '9. The zero test is a tolerance, not an identity comparison', $failures );

// The rules table and the label must agree without a second zero test.
bhp_fs_assert(
	'FREE' === bhp_bundle_shipping_display( bhp_bundle_rules( 'paperback' )[3]['shipping'], 'row' ),
	'9. PB collection tier renders FREE straight from the rules table',
	$failures
);
bhp_fs_assert(
	'FREE' === bhp_bundle_shipping_display( bhp_bundle_rules( 'hardcover' )[3]['shipping'], 'row' ),
	'9. HC collection tier renders FREE straight from the rules table',
	$failures
);

// =====================================================================
// 10. THE 2-BOOK NUDGE COPY
// =====================================================================
/*
 * ⚠ COPY STATUS: drafted from Andrew's stated intent ("free shipping with
 *   the purchase of this book"), PENDING `marketing-growth` REVIEW.
 *   These assertions test the CONSTRAINTS the wording must satisfy, not the
 *   exact sentence, so an approved rewording changes one string and does not
 *   break the suite.
 */
$copy = bhp_bundle_freeship_copy();
bhp_fs_assert( ! empty( $copy['nudge'] ) && ! empty( $copy['earned'] ), '10. Both free-shipping strings exist', $failures );
bhp_fs_assert( false === strpos( $copy['nudge'], '—' ), '10. Nudge contains no em-dash', $failures );
bhp_fs_assert( false === strpos( $copy['earned'], '—' ), '10. Earned line contains no em-dash', $failures );
bhp_fs_assert( false === strpos( $copy['nudge'], '$' ), '10. Nudge quotes no dollar figure that could drift from the tier table', $failures );

$urgency = array( 'hurry', 'today only', 'expires', 'limited time', 'act now', 'ends soon', 'last chance' );
$has_urgency = false;
foreach ( $urgency as $word ) {
	if ( false !== stripos( $copy['nudge'], $word ) || false !== stripos( $copy['earned'], $word ) ) {
		$has_urgency = true;
	}
}
bhp_fs_assert( false === $has_urgency, '10. Neither string uses false-urgency language', $failures );

// The filter is the swap mechanism the review depends on.
add_filter(
	'bhp_bundle_freeship_copy',
	function ( $c ) {
		$c['nudge'] = 'REVIEWED WORDING';
		return $c;
	}
);
$swapped = bhp_bundle_freeship_copy();
bhp_fs_assert( 'REVIEWED WORDING' === $swapped['nudge'], '10. Nudge copy is swappable through bhp_bundle_freeship_copy', $failures );

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL FREE-SHIPPING COLLECTION TESTS PASSED\n";
	exit( 0 );
}

echo count( $failures ) . " TEST(S) FAILED:\n";
foreach ( $failures as $label ) {
	echo " - {$label}\n";
}
exit( 1 );
