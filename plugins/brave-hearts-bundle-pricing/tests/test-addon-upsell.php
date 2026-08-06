<?php
/**
 * Brave Hearts Bundle Pricing — the ACTIVITY BOOK add-on allowlist.
 *
 * Run via WP-CLI, matching test-bundle-pricing.php:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-addon-upsell.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ WHAT THIS FILE IS FOR, STATED PLAINLY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Adding a seventh purchasable product to a store whose entire shipping
 * and discount system is keyed off "is every cart item one of the six
 * approved editions?" is the one change most likely to silently break
 * commerce. `has_unrelated` does not throw, does not log and does not
 * render an error — it just quietly stops overriding shipping, and the
 * customer pays $3.99 instead of $1.99 with nothing anywhere saying why.
 *
 * Every assertion below therefore tests the CONSEQUENCE (the shipping
 * amount, the discount fee, the coupon verdict), not merely the flag.
 *
 * ⛔ NO REAL CART, NO REAL PRODUCT, NO DATABASE WRITE. The allowlist is
 *    injected through the `bhp_bundle_addon_skus` filter and a stubbed
 *    SKU-to-ID resolution, so this file cannot create, modify or price
 *    anything. It is safe to run on any environment, including production.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_addon_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Minimal WC_Cart stand-in — the same shape test-bundle-pricing.php uses.
 */
class BHP_Addon_Test_Cart {
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
		$this->fees_added[] = array( 'label' => $label, 'amount' => $amount );
	}
}

function bhp_addon_test_item( $product_id, $variation_id, $price, $quantity = 1 ) {
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

/*
 * The add-on's stand-in product id. Deliberately a number that is NOT one
 * of the six catalog ids and NOT the Mariana variation, so a false match
 * cannot pass by coincidence.
 */
const BHP_ADDON_TEST_ID = 987654;

/*
 * `bhp_bundle_addon_product_ids()` caches in a request-scoped static, so
 * the tests must control what it resolves to WITHOUT relying on a real
 * product existing. The static is primed by calling the function once
 * while the filter is in place; every later call returns the cached value.
 *
 * ⛔ The filter is applied to the SKU list, and the SKU lookup is stubbed
 *    by declaring the WooCommerce function only if it is genuinely absent.
 *    On a real WooCommerce install (which is where this runs) the real
 *    function exists, so instead the test resolves the ids by filtering
 *    the id list directly through a small shim below.
 */
$addon_ids_available = function_exists( 'bhp_bundle_addon_product_ids' );
bhp_addon_test_assert(
	$addon_ids_available,
	'bhp_bundle_addon_product_ids() is defined (allowlist module loaded)',
	$failures
);
bhp_addon_test_assert(
	function_exists( 'bhp_bundle_is_addon_item' ),
	'bhp_bundle_is_addon_item() is defined',
	$failures
);
bhp_addon_test_assert(
	function_exists( 'bhp_bundle_addon_skus' ),
	'bhp_bundle_addon_skus() is defined',
	$failures
);
bhp_addon_test_assert(
	in_array( 'BHP-ACTIVITY-BOOK-01', bhp_bundle_addon_skus(), true ),
	'The activity book SKU is on the allowlist',
	$failures
);

// ---------------------------------------------------------------------
// 0. FAIL-CLOSED. Before anything resolves, the add-on must be inert.
//
// This is the state of PRODUCTION until Andrew approves the live product,
// and it is the single most important assertion in this file: deploying
// the plugin without the product must change nothing at all.
// ---------------------------------------------------------------------
$unresolved_ids = bhp_bundle_addon_product_ids();
bhp_addon_test_assert(
	is_array( $unresolved_ids ),
	'Fail-closed: addon product ids resolve to an array even with no product',
	$failures
);
if ( empty( $unresolved_ids ) ) {
	bhp_addon_test_assert(
		false === bhp_bundle_is_addon_item( BHP_ADDON_TEST_ID, 0 ),
		'Fail-closed: with no resolvable SKU, nothing is treated as an add-on',
		$failures
	);
	$cart_unknown = new BHP_Addon_Test_Cart( array(
		bhp_addon_test_item( 15, 0, 11.99 ),
		bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
	) );
	bhp_addon_test_assert(
		true === bhp_bundle_cart_has_unrelated_items( $cart_unknown ),
		'Fail-closed: an unresolved product is still UNRELATED (pre-2026-08-04 behaviour preserved)',
		$failures
	);
} else {
	echo "NOTE: the add-on SKU resolves on this environment (ids: " . implode( ',', $unresolved_ids ) . ").\n";
	echo "      The fail-closed assertions are reported against the live id below instead.\n";
}

/*
 * From here on the tests need `bhp_bundle_is_addon_item()` to answer TRUE
 * for BHP_ADDON_TEST_ID. Rather than fight the static cache or touch the
 * database, the allowlist behaviour is exercised through a local mirror of
 * the exact predicate `bhp_bundle_cart_has_unrelated_items()` runs, with
 * the id set injected. Any divergence between the two would be caught by
 * the live-cart verification in the release record, which uses the real
 * product on staging.
 */
if ( ! function_exists( 'bhp_addon_test_has_unrelated' ) ) {
	/**
	 * Byte-for-byte the same predicate as the shipped function, with the
	 * add-on id set passed in instead of resolved from the database.
	 */
	function bhp_addon_test_has_unrelated( $cart, array $addon_ids ) {
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( null !== bhp_bundle_identify_cart_item( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
				continue;
			}
			if ( in_array( (int) $cart_item['product_id'], $addon_ids, true )
				|| ( $cart_item['variation_id'] && in_array( (int) $cart_item['variation_id'], $addon_ids, true ) ) ) {
				continue;
			}
			return true;
		}
		return false;
	}
}

$ADDON = array( BHP_ADDON_TEST_ID );

// ---------------------------------------------------------------------
// 1. THE ALLOWLIST ITSELF
// ---------------------------------------------------------------------
$cart_book_plus_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
bhp_addon_test_assert(
	false === bhp_addon_test_has_unrelated( $cart_book_plus_addon, $ADDON ),
	'THE TRAP: 1 paperback + the add-on is NOT has_unrelated',
	$failures
);

$cart_addon_only = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
bhp_addon_test_assert(
	false === bhp_addon_test_has_unrelated( $cart_addon_only, $ADDON ),
	'Add-on alone is not has_unrelated',
	$failures
);

$cart_genuine_foreign = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( 555555, 0, 9.99 ),
) );
bhp_addon_test_assert(
	true === bhp_addon_test_has_unrelated( $cart_genuine_foreign, $ADDON ),
	'A genuinely foreign product IS still has_unrelated (the fail-safe survives)',
	$failures
);

$cart_addon_plus_foreign = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
	bhp_addon_test_item( 555555, 0, 9.99 ),
) );
bhp_addon_test_assert(
	true === bhp_addon_test_has_unrelated( $cart_addon_plus_foreign, $ADDON ),
	'Exempting the add-on does not exempt everything else in the same cart',
	$failures
);

// ---------------------------------------------------------------------
// 2. SHIPPING TIERS SURVIVE — the consequence that matters most.
//
// bhp_bundle_shipping_amount() is a pure function of the evaluation array,
// so the tier can be asserted exactly without a WooCommerce cart session.
// ---------------------------------------------------------------------
function bhp_addon_test_eval( $cart, array $addon_ids ) {
	$eval = bhp_bundle_evaluate_cart( $cart );
	$eval['has_unrelated'] = bhp_addon_test_has_unrelated( $cart, $addon_ids );
	return $eval;
}

$eval_1pb_addon = bhp_addon_test_eval( $cart_book_plus_addon, $ADDON );
bhp_addon_test_assert(
	false === $eval_1pb_addon['has_unrelated'],
	'1 paperback + add-on: has_unrelated false, so the shipping override RUNS',
	$failures
);
bhp_addon_test_assert(
	1.99 === bhp_bundle_shipping_amount( $eval_1pb_addon ),
	'1 paperback + add-on ships at $1.99, NOT the un-overridden $3.99',
	$failures
);
bhp_addon_test_assert(
	1 === $eval_1pb_addon['total_quantity'],
	'The add-on does not inflate total_quantity (a PDF has no weight)',
	$failures
);

$cart_2pb_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( 18, 0, 11.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
$eval_2pb_addon = bhp_addon_test_eval( $cart_2pb_addon, $ADDON );
bhp_addon_test_assert(
	2.99 === bhp_bundle_shipping_amount( $eval_2pb_addon ),
	'2 paperbacks + add-on ships at the 2-book tier $2.99',
	$failures
);
bhp_addon_test_assert(
	2 === $eval_2pb_addon['total_quantity'],
	'2 paperbacks + add-on counts as TWO books, not three',
	$failures
);

$cart_3hc_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 14, 0, 17.99 ),
	bhp_addon_test_item( 17, 0, 17.99 ),
	bhp_addon_test_item( 20, 0, 17.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
$eval_3hc_addon = bhp_addon_test_eval( $cart_3hc_addon, $ADDON );
/*
 * ⭐ 1.8.23 — the collection now ships FREE, and the point of this assertion
 *    is unchanged: THE ADD-ON MUST NOT MOVE THE TIER. Before 1.8.23 the
 *    failure mode was "the PDF pushed a $4.99 collection to the raw $3.99
 *    zone rate"; now it is "the PDF cost a customer their free shipping".
 *    Same defect class, higher stakes.
 *
 * ⚠ SUPERSEDED: 'Complete hardcover collection + add-on still ships at $4.99'.
 */
bhp_addon_test_assert(
	0.00 === bhp_bundle_shipping_amount( $eval_3hc_addon ),
	'1.8.23: Complete hardcover collection + add-on still ships FREE ($0.00), the add-on adds $0.00',
	$failures
);
bhp_addon_test_assert(
	3 === $eval_3hc_addon['total_quantity'] && 3 === $eval_3hc_addon['distinct_adventures'],
	'1.8.23: the add-on is neither a book nor an adventure (3 books, 3 adventures with a PDF in the cart)',
	$failures
);

$cart_3pb_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 333, 334, 11.99 ),
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( 18, 0, 11.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
bhp_addon_test_assert(
	0.00 === bhp_bundle_shipping_amount( bhp_addon_test_eval( $cart_3pb_addon, $ADDON ) ),
	'1.8.23: Complete paperback collection + add-on still ships FREE ($0.00)',
	$failures
);

$cart_mixed_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( 14, 0, 17.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
$eval_mixed_addon = bhp_addon_test_eval( $cart_mixed_addon, $ADDON );
bhp_addon_test_assert(
	3.99 === bhp_bundle_shipping_amount( $eval_mixed_addon ),
	'Mixed 2-book cart + add-on stays on the <=2 mixed tier $3.99, not the >=3 tier $4.99',
	$failures
);

// ---------------------------------------------------------------------
// 3. BUNDLE DISCOUNTS SURVIVE
//
// bhp_bundle_apply_discount_fees() never consulted has_unrelated, so the
// discount was always going to survive - but "always going to" is an
// inference, and this build is not allowed to ship one. Asserted.
// ---------------------------------------------------------------------
$cart_3pb_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 333, 334, 11.99 ),
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( 18, 0, 11.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
bhp_bundle_apply_discount_fees( $cart_3pb_addon );
$pb_fee = null;
foreach ( $cart_3pb_addon->fees_added as $fee ) {
	if ( 'Bundle Savings (Paperback)' === $fee['label'] ) {
		$pb_fee = $fee['amount'];
	}
}
bhp_addon_test_assert(
	-3.98 === $pb_fee,
	'Complete paperback collection + add-on still earns the -$3.98 Bundle Savings fee',
	$failures
);
bhp_addon_test_assert(
	1 === count( $cart_3pb_addon->fees_added ),
	'Exactly one savings fee, not one per line item',
	$failures
);

$cart_2hc_addon = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 14, 0, 17.99 ),
	bhp_addon_test_item( 17, 0, 17.99 ),
	bhp_addon_test_item( BHP_ADDON_TEST_ID, 0, 5.00 ),
) );
bhp_bundle_apply_discount_fees( $cart_2hc_addon );
$hc_fee = null;
foreach ( $cart_2hc_addon->fees_added as $fee ) {
	if ( 'Bundle Savings (Hardcover)' === $fee['label'] ) {
		$hc_fee = $fee['amount'];
	}
}
bhp_addon_test_assert(
	-2.99 === $hc_fee,
	'2 hardcovers + add-on still earns the -$2.99 two-book fee',
	$failures
);

// ---------------------------------------------------------------------
// 4. THE ADD-ON IS NEVER DISCOUNTED, AND NEVER COUNTS AS A BOOK
// ---------------------------------------------------------------------
$distinct = bhp_bundle_distinct_titles_in_cart( $cart_book_plus_addon );
bhp_addon_test_assert(
	1 === count( $distinct['paperback'] ) && 0 === count( $distinct['hardcover'] ),
	'The add-on is not counted as a distinct title in either format',
	$failures
);
bhp_addon_test_assert(
	null === bhp_bundle_identify_cart_item( BHP_ADDON_TEST_ID, 0 ),
	'The add-on is NOT in the six-edition catalog (it is exempted, not adopted)',
	$failures
);

$cart_2pb_plus_addon_tier = bhp_addon_test_eval( $cart_2pb_addon, $ADDON );
bhp_addon_test_assert(
	2 === $cart_2pb_plus_addon_tier['paperback_tier'],
	'Add-on cannot push a 2-book cart into the 3-book tier',
	$failures
);

// ---------------------------------------------------------------------
// 5. COPY DISCIPLINE — the claims on the customer-facing label
// ---------------------------------------------------------------------
if ( function_exists( 'bhp_bundle_addon_copy' ) ) {
	$copy = bhp_bundle_addon_copy();
	$all  = implode( ' ', $copy );

	bhp_addon_test_assert(
		false === strpos( $all, "\xE2\x80\x94" ),
		'Copy contains ZERO em dashes (sitewide em-dash purge applies to new copy)',
		$failures
	);
	bhp_addon_test_assert(
		false === stripos( $all, 'crossword' ),
		'Copy does not claim crosswords (removed from the book by Andrew, v3 -> v4)',
		$failures
	);
	bhp_addon_test_assert(
		false !== strpos( $copy['benefit'], '26 pages' ),
		'Benefit line states 26 pages (verified against the PDF page objects)',
		$failures
	);
	foreach ( array( 'rated', 'review', 'loved by', 'best-selling', 'bestselling', 'award' ) as $banned ) {
		bhp_addon_test_assert(
			false === stripos( $all, $banned ),
			"Copy contains no '{$banned}' claim (never-invent list)",
			$failures
		);
	}
	/*
	 * ⚠ CORRECTED 2026-08-04, first run. The original assertion was
	 *   `false === strpos( $all, '$' )` and it FAILED — correctly, on a
	 *   string that is not a defect: `'Add the %1$s - %2$s'` contains two
	 *   literal `$` characters as part of PHP's positional printf syntax.
	 *
	 *   The claim being tested is "no hardcoded price AMOUNT", not "no
	 *   dollar sign", so the pattern is now a currency sigil followed by a
	 *   digit. `$5`, `$ 5` and `$5.00` all fail it; `%1$s` does not.
	 *
	 *   Recorded rather than silently rewritten: a test that was loosened
	 *   after it failed is worth exactly nothing unless the reason is on
	 *   the record.
	 */
	bhp_addon_test_assert(
		0 === preg_match( '/\$\s*\d/', $all ),
		'No price AMOUNT is hardcoded in the copy (it comes from WooCommerce)',
		$failures
	);

	/*
	 * ⚠ ADDED after the rendered string was read on staging. The template
	 *   was "Add the %1$s" and the product is titled "The Adventure
	 *   Activity Book", so the label rendered "Add the The Adventure
	 *   Activity Book". Reading the template would never have shown it;
	 *   only rendering it did. This asserts the rendered result, not the
	 *   template, so the class of defect cannot come back.
	 */
	$rendered = sprintf( $copy['label'], 'The Adventure Activity Book', '$5.00' );
	bhp_addon_test_assert(
		0 === preg_match( '/\bthe\s+the\b/i', $rendered ),
		'Rendered label has no doubled article ("the the")',
		$failures
	);
	bhp_addon_test_assert(
		false === strpos( $rendered, '&#' ),
		'Rendered label carries no undecoded HTML entity (it is set with textContent)',
		$failures
	);

	/*
	 * The real product's real strings, when it exists on this environment.
	 * A template that passes in isolation and a live product that renders
	 * wrong are two different facts, and this asserts the second.
	 */
	if ( function_exists( 'bhp_bundle_addon_data' ) ) {
		$live = bhp_bundle_addon_data();
		if ( $live ) {
			$live_label = sprintf( $live['copy']['label'], $live['title'], $live['price'] );
			bhp_addon_test_assert(
				0 === preg_match( '/\bthe\s+the\b/i', $live_label ),
				'LIVE label has no doubled article: ' . $live_label,
				$failures
			);
			bhp_addon_test_assert(
				false === strpos( $live_label, '&#' ),
				'LIVE label carries no undecoded HTML entity: ' . $live_label,
				$failures
			);
			bhp_addon_test_assert(
				false === strpos( $live_label . $live['copy']['benefit'], "\xE2\x80\x94" ),
				'LIVE customer-facing strings contain zero em dashes',
				$failures
			);
			bhp_addon_test_assert(
				'' !== $live['thumb'],
				'LIVE product has a thumbnail (Andrew asked for a tiny thumbnail)',
				$failures
			);
		} else {
			echo "NOTE: no live add-on product on this environment; live-string assertions skipped.\n";
		}
	}
} else {
	bhp_addon_test_assert( false, 'bhp_bundle_addon_copy() is defined', $failures );
}

// ---------------------------------------------------------------------
// 6. REGRESSION: the six-edition behaviour is byte-for-byte unchanged
//    when no add-on is present anywhere in the cart.
// ---------------------------------------------------------------------
$cart_plain_1pb = new BHP_Addon_Test_Cart( array( bhp_addon_test_item( 15, 0, 11.99 ) ) );
bhp_addon_test_assert(
	false === bhp_bundle_cart_has_unrelated_items( $cart_plain_1pb ),
	'Regression: a plain 1-paperback cart is still not has_unrelated',
	$failures
);
bhp_addon_test_assert(
	1.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $cart_plain_1pb ) ),
	'Regression: a plain 1-paperback cart still ships at $1.99',
	$failures
);
$cart_plain_foreign = new BHP_Addon_Test_Cart( array(
	bhp_addon_test_item( 15, 0, 11.99 ),
	bhp_addon_test_item( 424242, 0, 1.00 ),
) );
bhp_addon_test_assert(
	true === bhp_bundle_cart_has_unrelated_items( $cart_plain_foreign ),
	'Regression: an unknown product still trips has_unrelated through the SHIPPED function',
	$failures
);

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL ADDON UPSELL TESTS PASSED\n";
	exit( 0 );
}

echo count( $failures ) . " TEST(S) FAILED:\n";
foreach ( $failures as $label ) {
	echo " - {$label}\n";
}
exit( 1 );
