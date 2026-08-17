<?php
/**
 * Brave Hearts — THE ACTIVITY BOOK IS FREE WITH A COLLECTION (1.8.27).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-addon-free-collection.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the Chief of
 * Staff; NOT witnessed first-hand by the agent that wrote this file):
 *
 *   "I want to change the upsell- make the activity book free and I want
 *    it clear that you get Free Shipping and a Free Activity book with
 *    Collection purchase- on all collection pages and boxes"
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It is a PHP/CLI suite over PURE functions and STUB carts. It therefore
 * CANNOT prove:
 *
 *   · that the auto-include actually fires on a real Blocks cart (that is
 *     a Store API round trip; it is verified in a real browser and in the
 *     WP-CLI live-cart section of the release record, not here);
 *   · that WooCommerce grants a download permission for a $0.00 line (that
 *     is verified against a REAL ORDER on staging, in the release record);
 *   · that the add-on thank-you email is delivered.
 *
 * Claiming any of those from this file would be a fabricated verification,
 * which the standing rules put in the same class as a fabricated review.
 * What it DOES prove is the part that regresses silently: the predicate,
 * the price rule, the removal policy, the copy constraints, and — most
 * important — that none of the guards this feature sits next to moved.
 *
 * ⛔ NO ORDER IS CREATED. NO REAL CART IS BUILT OR MUTATED. No product
 *    record, price, coupon, stock level, shipping, tax or payment setting
 *    is read or written by any part of this file, on any environment.
 *
 * Exits non-zero on any failure.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_afc_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_afc_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

/**
 * The smallest product stub the code under test actually touches:
 * set_price/get_price for the price rule, is_on_sale for the coupon
 * qualifier. Nothing here reaches WooCommerce.
 */
if ( ! class_exists( 'BHP_AFC_Product' ) ) {
	class BHP_AFC_Product {
		private $price;
		public function __construct( $price ) {
			$this->price = (float) $price;
		}
		public function set_price( $price ) {
			$this->price = (float) $price;
		}
		public function get_price() {
			return $this->price;
		}
		public function is_on_sale() {
			return false;
		}
	}
}

if ( ! class_exists( 'BHP_AFC_Cart' ) ) {
	class BHP_AFC_Cart {
		private $items;
		public function __construct( array $items ) {
			$this->items = $items;
		}
		public function get_cart() {
			return $this->items;
		}
	}
}

/**
 * One cart line. `$grant` marks it as a copy this plugin gave away.
 */
function bhp_afc_item( $product_id, $variation_id = 0, $price = 11.99, $grant = false, $qty = 1 ) {
	$item = array(
		'key'          => 'afc_' . $product_id . '_' . $variation_id . ( $grant ? '_g' : '' ),
		'product_id'   => (int) $product_id,
		'variation_id' => (int) $variation_id,
		'quantity'     => (int) $qty,
		'data'         => new BHP_AFC_Product( $price ),
	);
	if ( $grant ) {
		$item[ BHP_BUNDLE_ADDON_GRANT_KEY ] = 'yes';
	}
	return $item;
}

echo "\n=== §0 · the module is loaded and its contract is intact ===\n";

foreach ( array(
	'bhp_bundle_addon_free_enabled',
	'bhp_bundle_addon_free_with_collection',
	'bhp_bundle_cart_earns_free_addon',
	'bhp_bundle_addon_cart_lines',
	'bhp_bundle_addon_apply_free_price',
	'bhp_bundle_addon_sync_free_grant',
	'bhp_bundle_addon_note_customer_removal',
	'bhp_bundle_addon_free_item_data',
	'bhp_bundle_addon_free_copy',
	'bhp_bundle_addon_free_display',
	'bhp_bundle_addon_free_offer_label',
	'bhp_bundle_addon_free_declined',
	'bhp_bundle_addon_withdrawing',
) as $fn ) {
	bhp_afc_assert( function_exists( $fn ), "§0 {$fn}() is loaded", $failures );
}

bhp_afc_assert( defined( 'BHP_BUNDLE_ADDON_GRANT_KEY' ), '§0 the grant key constant is defined', $failures );
bhp_afc_assert( defined( 'BHP_BUNDLE_ADDON_DECLINED_KEY' ), '§0 the declined-session-key constant is defined', $failures );

bhp_afc_assert(
	has_action( 'woocommerce_before_calculate_totals', 'bhp_bundle_addon_apply_free_price' ) !== false,
	'§0 the price rule is hooked to woocommerce_before_calculate_totals',
	$failures
);
foreach ( array(
	'woocommerce_cart_loaded_from_session',
	'woocommerce_add_to_cart',
	'woocommerce_cart_item_removed',
	'woocommerce_after_cart_item_quantity_update',
) as $hook ) {
	bhp_afc_assert(
		has_action( $hook, 'bhp_bundle_addon_sync_free_grant' ) !== false,
		"§0 the grant sync is hooked to {$hook}",
		$failures
	);
}
bhp_afc_assert(
	has_action( 'woocommerce_remove_cart_item', 'bhp_bundle_addon_note_customer_removal' ) !== false,
	'§0 the decline latch is hooked to woocommerce_remove_cart_item (BEFORE the line is gone, so its data is still readable)',
	$failures
);
bhp_afc_assert(
	has_filter( 'woocommerce_get_item_data', 'bhp_bundle_addon_free_item_data' ) !== false,
	'§0 the "FREE with your collection" line is added through woocommerce_get_item_data (the one filter both classic and Store API honour)',
	$failures
);

echo "\n=== §1 · THE PREDICATE IS THE EXISTING ONE. No second definition of 'collection'. ===\n";

/*
 * ⭐ THE ASSERTION THAT MATTERS MOST IN THIS SECTION is the pair at the
 *    end: three BOOKS that are only two ADVENTURES must NOT earn the free
 *    copy, for exactly the reason they do not earn free shipping. If this
 *    ever passes, the store is giving a free book for buying the same
 *    story twice, and the free-addon rule has drifted away from the
 *    shipping rule it is supposed to share.
 */
$afc_three_pb   = new BHP_AFC_Cart( array( bhp_afc_item( 334, 0, 11.99 ), bhp_afc_item( 15, 0, 11.99 ), bhp_afc_item( 18, 0, 11.99 ) ) );
$afc_three_hc   = new BHP_AFC_Cart( array( bhp_afc_item( 14, 0, 17.99 ), bhp_afc_item( 17, 0, 17.99 ), bhp_afc_item( 20, 0, 17.99 ) ) );
$afc_mixed_coll = new BHP_AFC_Cart( array( bhp_afc_item( 15, 0, 11.99 ), bhp_afc_item( 18, 0, 11.99 ), bhp_afc_item( 14, 0, 17.99 ) ) );
$afc_two_pb     = new BHP_AFC_Cart( array( bhp_afc_item( 334, 0, 11.99 ), bhp_afc_item( 15, 0, 11.99 ) ) );
$afc_one_pb     = new BHP_AFC_Cart( array( bhp_afc_item( 334, 0, 11.99 ) ) );
$afc_dupe       = new BHP_AFC_Cart( array( bhp_afc_item( 15, 0, 11.99, false, 2 ), bhp_afc_item( 14, 0, 17.99 ) ) );
$afc_empty      = new BHP_AFC_Cart( array() );

bhp_afc_assert( true === bhp_bundle_cart_earns_free_addon( $afc_three_pb ), '§1 3 distinct paperbacks EARN the free copy', $failures );
bhp_afc_assert( true === bhp_bundle_cart_earns_free_addon( $afc_three_hc ), '§1 3 distinct hardcovers EARN the free copy', $failures );
bhp_afc_assert( true === bhp_bundle_cart_earns_free_addon( $afc_mixed_coll ), '§1 3 distinct adventures ACROSS formats EARN it (same rule as free shipping)', $failures );
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.36 — THESE ASSERTIONS ARE INVERTED, AND THAT IS THE POINT.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Until 1.8.35 the four assertions below required 1 adventure, 2
 * adventures and the duplicate-title trap to NOT earn the free copy. They
 * were correct, they were guarding a real policy, and they FAILED THE
 * MOMENT THE POLICY CHANGED -- which is exactly what a guard is for.
 *
 * Andrew Signore, 2026-08-06, verbatim (⛔ RELAYED, not witnessed
 * first-hand): "make the activity book free with any book purchase - say
 * its a $5.00 savings". The offer now attaches to ANY cart holding at
 * least one of the six approved editions.
 *
 * ⛔ THE SUPERSEDED EXPECTATIONS ARE PRESERVED HERE RATHER THAN DELETED,
 *    so a future reader sees a policy change rather than a loosened test:
 *
 *      false === ...( $afc_two_pb )   // 1.8.27-1.8.35: 2 adventures do NOT
 *      false === ...( $afc_one_pb )   // 1.8.27-1.8.35: 1 adventure does NOT
 *      false === ...( $afc_dupe )     // 1.8.27-1.8.35: the 2-adventure trap does NOT
 *
 * ⛔ ONE EXPECTATION IS DELIBERATELY UNCHANGED AND IS NOW THE LOAD-BEARING
 *    ONE: an EMPTY cart still does not earn it. So does a cart holding
 *    only the add-on -- asserted separately below -- because the Activity
 *    Book is not in the six-edition catalogue the count walks. That is the
 *    never-sold-alone guard, restated in data, and widening the offer must
 *    not widen it.
 */
bhp_afc_assert( true === bhp_bundle_cart_earns_free_addon( $afc_two_pb ), '§1 2 adventures EARN it (1.8.36: any book purchase)', $failures );
bhp_afc_assert( true === bhp_bundle_cart_earns_free_addon( $afc_one_pb ), '§1 ⭐ ONE book EARNS it - the whole point of 1.8.36', $failures );
bhp_afc_assert( false === bhp_bundle_cart_earns_free_addon( $afc_empty ), '§1 ⛔ an empty cart STILL does not', $failures );
bhp_afc_assert(
	true === bhp_bundle_cart_earns_free_addon( $afc_dupe ),
	'§1 3 BOOKS / 2 ADVENTURES now EARNS it too - it is a book purchase, which is the only question 1.8.36 asks',
	$failures
);

/*
 * And it is literally the same flag, not a parallel computation.
 */
foreach ( array(
	'3 paperbacks' => $afc_three_pb,
	'3 hardcovers' => $afc_three_hc,
	'mixed set'    => $afc_mixed_coll,
	'2 adventures' => $afc_two_pb,
	'2 of one'     => $afc_dupe,
) as $name => $cart ) {
	$eval = bhp_bundle_evaluate_cart( $cart );
	bhp_afc_assert(
		bhp_bundle_cart_earns_free_addon( $cart ) === (bool) $eval['has_any_book'],
		"§1 ⭐ '{$name}': the free-addon predicate IS bhp_bundle_evaluate_cart()['has_any_book'], not a copy of it",
		$failures
	);
}

echo "\n=== §2 · the shipping tiers and the collection discount are UNMOVED ===\n";

/*
 * The feature must be invisible to every number the customer is charged
 * apart from the add-on line itself. These are the same figures the
 * shipping suite asserts; they are re-asserted here because THIS release
 * is the one that could move them.
 */
bhp_afc_assert( 0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_three_pb ) ), '§2 3 paperbacks still ship FREE', $failures );
bhp_afc_assert( 0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_three_hc ) ), '§2 3 hardcovers still ship FREE', $failures );
bhp_afc_assert( 0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_mixed_coll ) ), '§2 a mixed collection still ships FREE', $failures );
bhp_afc_assert( 2.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_two_pb ) ), '§2 2 paperbacks still ship $2.99', $failures );
bhp_afc_assert( 1.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_one_pb ) ), '§2 1 paperback still ships $1.99', $failures );
bhp_afc_assert( 4.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_dupe ) ), '§2 3 books / 2 adventures still ship $4.99', $failures );

bhp_afc_assert( 3.98 === (float) bhp_bundle_rules( 'paperback' )[3]['discount'], '§2 the paperback collection discount is still $3.98', $failures );
bhp_afc_assert( 4.98 === (float) bhp_bundle_rules( 'hardcover' )[3]['discount'], '§2 the hardcover collection discount is still $4.98', $failures );

echo "\n=== §3 · THE AUDIENCE COUPON STACKS ON THE COLLECTION PRICE, NOT ON THE \$0 ITEM ===\n";

/*
 * ⭐ THE ARITHMETIC IS ASSERTED AGAINST THE FOUNDER-VERIFIED TOTAL
 *    of 2026-08-05 ($28.79 paperback), because the free add-on is
 *    exactly the kind of change that could move it without anybody
 *    noticing: a $0.00 line in the cart is still a LINE, and a coupon that
 *    discounted line items proportionally would now have one more of them.
 *
 *    It does not, and this section is why we can say so rather than hope:
 *    the savings expression is a pure function of bhp_bundle_rules() and
 *    bhp_bundle_expected_price(). It never reads a cart line, so an extra
 *    $0.00 line cannot enter the calculation.
 *
 *    paperback  3 x $11.99 = $35.97 - $3.98 = $31.99 ; 10% = $3.20
 *               $31.99 - $3.20 = $28.79   <- founder-verified total
 *    hardcover  3 x $17.99 = $53.97 - $4.98 = $48.99 ; 10% = $4.90
 *               $48.99 - $4.90 = $44.09   <- founder-verified total
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ RETARGETED 1.8.48 (2026-08-17, `CYCLE162-LD-TYP-V2-QA`) AFTER THIS
 *    SECTION KILLED THE WHOLE SUITE WITH AN UNCAUGHT `Error`.
 * ═══════════════════════════════════════════════════════════════════════
 *
 *    Superseded call, verbatim, preserved so the movement is visible:
 *        $afc_pb_savings = bhp_audience_coupon_savings_amount( $afc_three_pb );
 *        $afc_hc_savings = bhp_audience_coupon_savings_amount( $afc_three_hc );
 *
 *    1.8.47 gave `bhp_audience_coupon_savings_amount()` a second job — read
 *    the cart's APPLIED COUPON and take the percentage off the live record
 *    instead of a `0.10` literal. That is the right change, and it means the
 *    function is no longer pure: it calls `$cart->get_applied_coupons()`, a
 *    method this file's deliberately minimal cart double does not have.
 *    OBSERVED on staging 2026-08-17: *"Call to undefined method
 *    BHP_AFC_Cart::get_applied_coupons()"*, aborting every assertion below.
 *
 *    THE FOUNDER-VERIFIED TOTALS ARE UNCHANGED AND STILL THE POINT. What
 *    moved is which function is asked for them: the PURE expression
 *    `bhp_audience_coupon_savings_for_format()`, which is what
 *    `bhp_audience_coupon_savings_amount()` itself calls once it has read the
 *    percentage, and what the thank-you page's quoted price comes out of.
 *
 * ⭐ THE `10.0` HERE IS THE RATE THE $28.79 / $44.09 FIGURES WERE VERIFIED
 *    AT, not an assumption about the live coupon. Asserting a founder-
 *    verified total against a rate that can be edited in wp-admin would make
 *    this suite fail for a legitimate reason and hide a real regression. The
 *    live rate is asserted where it belongs, against the live record, in
 *    `tests/test-typ-auto-coupon.php` and `tests/test-kit-thankyou-upsell.php`.
 *
 * ⛔ AND THE NEW CONTRACT IS ASSERTED RATHER THAN ASSUMED: a cart object that
 *    cannot report its coupons must return 0.0, not fatal. That is the
 *    1.8.48 guard, and this is the regression test for it.
 */
$afc_pb_collection_price = ( 3 * bhp_bundle_expected_price( 'paperback' ) ) - bhp_bundle_rules( 'paperback' )[3]['discount'];
$afc_hc_collection_price = ( 3 * bhp_bundle_expected_price( 'hardcover' ) ) - bhp_bundle_rules( 'hardcover' )[3]['discount'];

bhp_afc_assert( 31.99 === round( $afc_pb_collection_price, 2 ), '§3 the paperback collection price is $31.99', $failures );
bhp_afc_assert( 48.99 === round( $afc_hc_collection_price, 2 ), '§3 the hardcover collection price is $48.99', $failures );

$afc_pb_savings = bhp_audience_coupon_savings_for_format( 'paperback', 10.0 );
$afc_hc_savings = bhp_audience_coupon_savings_for_format( 'hardcover', 10.0 );

bhp_afc_assert(
	0.0 === bhp_audience_coupon_savings_amount( $afc_three_pb ),
	'§3 ⛔ a cart object that cannot report applied coupons returns 0.0, never a fatal (1.8.48 guard)',
	$failures
);

bhp_afc_assert( 3.20 === round( $afc_pb_savings, 2 ), '§3 the 10% audience-coupon saving on a paperback collection is $3.20', $failures );
bhp_afc_assert( 4.90 === round( $afc_hc_savings, 2 ), '§3 the 10% audience-coupon saving on a hardcover collection is $4.90', $failures );
bhp_afc_assert(
	28.79 === round( $afc_pb_collection_price - $afc_pb_savings, 2 ),
	'§3 ⭐ a 10% audience coupon + paperback collection = $28.79, UNCHANGED by the free add-on (founder-verified totals, 2026-08-05)',
	$failures
);
bhp_afc_assert(
	44.09 === round( $afc_hc_collection_price - $afc_hc_savings, 2 ),
	'§3 ⭐ a 10% audience coupon + hardcover collection = $44.09, UNCHANGED by the free add-on (founder-verified totals, 2026-08-05)',
	$failures
);

echo "\n=== §4 · the never-sold-alone guard is untouched ===\n";

bhp_afc_assert(
	function_exists( 'bhp_bundle_cart_is_addon_only' ),
	'§4 the add-on-only guard still exists',
	$failures
);
bhp_afc_assert(
	has_action( 'woocommerce_store_api_cart_errors', 'bhp_bundle_addon_guard_store_api' ) !== false,
	'§4 ⭐ and it is still on the Store API hook - the ONE that actually stops a Blocks checkout',
	$failures
);
bhp_afc_assert(
	false === bhp_bundle_cart_is_addon_only( $afc_three_pb ),
	'§4 a books cart is not "add-on only" (the guard cannot fire on a real order)',
	$failures
);
bhp_afc_assert(
	false === bhp_bundle_cart_is_addon_only( $afc_empty ),
	'§4 an empty cart is not "add-on only" either (WooCommerce has its own empty-cart error)',
	$failures
);

echo "\n=== §5 · THE COPY. Every constraint the \$5 copy was written under. ===\n";

$afc_copy = bhp_bundle_addon_free_copy();

foreach ( array( 'line_key', 'line_value', 'label', 'price', 'item_note', 'aria', 'offer_short' ) as $key ) {
	bhp_afc_assert( ! empty( $afc_copy[ $key ] ), "§5 the free copy carries '{$key}'", $failures );
}

/*
 * ⭐⭐ 1.8.36 — THE DOLLAR-FIGURE BAN IS REPLACED, NOT DROPPED.
 *
 * The superseded assertion, preserved so this reads as a policy change:
 *
 *     false === strpos( $string, '$' )   // 1.8.27-1.8.35
 *
 * It was right while the copy said only "FREE". Andrew's 2026-08-06 ruling
 * is explicitly that it must now "say its a $5.00 savings", so a blanket
 * ban on the dollar sign would forbid the thing the owner asked for.
 *
 * ⛔ WHAT REPLACES IT IS STRICTER WHERE IT MATTERS: any dollar figure that
 *    appears must be THE PRODUCT'S OWN PRICE, read from WooCommerce at
 *    render time. A hardcoded "$5.00" in a string would pass the old test
 *    (no, it would fail it) and would pass a naive new one -- this asserts
 *    the figure MATCHES `bhp_bundle_addon_free_savings()`, which is
 *    derived from the live product record. Reprice the product and the
 *    copy follows; hardcode it and this fails.
 */
$afc_savings_amt = bhp_bundle_addon_free_savings();
foreach ( $afc_copy as $key => $string ) {
	bhp_afc_assert( false === strpos( $string, '—' ), "§5 '{$key}': no em dash", $failures );
	if ( false !== strpos( $string, '$' ) ) {
		bhp_afc_assert(
			'' !== $afc_savings_amt && false !== strpos( $string, $afc_savings_amt ),
			"§5 '{$key}': any dollar figure is WooCommerce's OWN price for the add-on, never a literal",
			$failures
		);
	}
}

/*
 * ⛔ AND THE FIGURE ITSELF IS NOT WRITTEN DOWN ANYWHERE IN THE MODULE. This
 *    is the assertion that actually stops a future copy edit pinning "$5.00"
 *    into a string, which would then keep saying $5.00 after a reprice.
 */
$afc_module_src = @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/addon-free-with-collection.php' );
bhp_afc_assert(
	is_string( $afc_module_src ) && '' !== $afc_module_src,
	'§5 the free-offer module is readable for the hardcoded-price scan',
	$failures
);
if ( is_string( $afc_module_src ) && '' !== $afc_module_src ) {
	/*
	 * Comments are stripped first, deliberately: this file's own header
	 * QUOTES Andrew's ruling, which contains the characters "$5.00". A scan
	 * that did not strip comments would fail on the sentence that authorises
	 * the feature, which is the least useful possible false positive.
	 */
	$afc_code_only = (string) preg_replace( '#/\*.*?\*/#s', '', $afc_module_src );
	$afc_code_only = (string) preg_replace( '#//[^\n]*#', '', $afc_code_only );
	bhp_afc_assert(
		0 === preg_match( '/\'[^\']*\$[0-9]/', $afc_code_only ),
		'§5 ⭐ NO hardcoded dollar amount in any string literal in the module - the figure comes from the product record',
		$failures
	);
}

/*
 * ⛔ NOTHING ON THE NEVER-INVENT LIST. Asserted rather than asserted-by-
 *    eye, because this is customer-facing copy on the page that takes the
 *    money and a future copy swap goes through the same filter.
 */
$afc_banned = array( 'best', 'loved', 'rated', 'review', 'award', 'proven', 'hurry', 'today only', 'limited time', 'ends ', 'value of', 'normally' );
foreach ( $afc_copy as $key => $string ) {
	foreach ( $afc_banned as $needle ) {
		bhp_afc_assert(
			false === stripos( $string, $needle ),
			"§5 '{$key}': no '{$needle}' (never-invent list / no false urgency)",
			$failures
		);
	}
}

bhp_afc_assert( 'FREE' === bhp_bundle_addon_free_display(), '§5 the price-box row reads exactly "FREE"', $failures );
bhp_afc_assert( 'FREE Activity Book' === bhp_bundle_addon_free_offer_label(), '§5 the short offer label reads "FREE Activity Book"', $failures );

echo "\n=== §6 · the price rule, on stub carts ===\n";

$afc_ids = bhp_bundle_addon_product_ids();

if ( empty( $afc_ids ) ) {
	/*
	 * ⭐ THIS IS NOT A HOLE, IT IS THE FAIL-CLOSED STATE, and it is asserted
	 *    as such rather than skipped silently. With no resolvable SKU the
	 *    feature must be inert, which is exactly what production looked like
	 *    before the product existed.
	 */
	bhp_afc_assert(
		false === bhp_bundle_addon_free_with_collection(),
		'§6 FAIL-CLOSED: with no resolvable SKU the offer is not live and no surface may claim it',
		$failures
	);
	bhp_afc_skip( '§6 the price/grant assertions need a resolvable BHP-ACTIVITY-BOOK-01 on this environment', $skipped );
} else {
	$afc_addon_id = (int) $afc_ids[0];
	echo "     add-on resolves to product id {$afc_addon_id} on this environment\n";

	bhp_afc_assert(
		true === bhp_bundle_addon_free_with_collection(),
		'§6 the offer is live on this environment (enabled + a real purchasable in-stock product)',
		$failures
	);

	// A qualifying cart: three paperbacks plus a granted add-on line.
	$afc_qualifying = new BHP_AFC_Cart( array(
		bhp_afc_item( 334, 0, 11.99 ),
		bhp_afc_item( 15, 0, 11.99 ),
		bhp_afc_item( 18, 0, 11.99 ),
		bhp_afc_item( $afc_addon_id, 0, 5.00, true ),
	) );
	// A NON-qualifying cart: one paperback plus a bought add-on line.
	$afc_paid = new BHP_AFC_Cart( array(
		bhp_afc_item( 334, 0, 11.99 ),
		bhp_afc_item( $afc_addon_id, 0, 5.00, false ),
	) );

	bhp_bundle_addon_apply_free_price( $afc_qualifying );
	bhp_bundle_addon_apply_free_price( $afc_paid );

	$afc_q_items = $afc_qualifying->get_cart();
	$afc_p_items = $afc_paid->get_cart();
	$afc_q_addon = end( $afc_q_items );
	$afc_p_addon = end( $afc_p_items );

	bhp_afc_assert(
		0.0 === (float) $afc_q_addon['data']->get_price(),
		'§6 ⭐ on a COLLECTION cart the add-on line is priced $0.00',
		$failures
	);
	/*
	 * ⭐⭐ 1.8.36 — INVERTED. The superseded expectation, preserved:
	 *
	 *     5.0 === (float) $afc_p_addon['data']->get_price();
	 *     // "on a NON-collection cart the add-on line is STILL $5.00 -
	 *     //  the paid checkbox is exactly as it was"
	 *
	 * That WAS the policy, and it is the exact half of it Andrew reversed:
	 * the $5.00 checkbox never sold a copy (his observation, recorded as an
	 * observation and not as a measured statistic) and is retired. A cart
	 * with one book gets the Activity Book at $0.00 like any other.
	 */
	bhp_afc_assert(
		0.0 === (float) $afc_p_addon['data']->get_price(),
		'§6 ⭐ on a ONE-BOOK cart the add-on line is ALSO $0.00 - the $5.00 checkbox is retired',
		$failures
	);

	// The books must not be touched by the price rule, in either cart.
	$afc_book_prices_ok = true;
	foreach ( $afc_q_items as $item ) {
		if ( (int) $item['product_id'] === $afc_addon_id ) {
			continue;
		}
		if ( abs( (float) $item['data']->get_price() - 11.99 ) > 0.001 ) {
			$afc_book_prices_ok = false;
		}
	}
	bhp_afc_assert( $afc_book_prices_ok, '§6 ⛔ no BOOK line is repriced by the free-addon rule', $failures );

	/*
	 * ⭐ THE SECOND-ANSWER CASE, asserted because the first answer was
	 *    wrong. A customer who ticked the $5 checkbox at two books and THEN
	 *    completed the collection must not still be charged $5 for the thing
	 *    the same page now calls free. The rule is about the CART, not about
	 *    how the line got there, so an UNFLAGGED add-on line in a qualifying
	 *    cart is free too.
	 */
	$afc_bought_then_completed = new BHP_AFC_Cart( array(
		bhp_afc_item( 334, 0, 11.99 ),
		bhp_afc_item( 15, 0, 11.99 ),
		bhp_afc_item( 18, 0, 11.99 ),
		bhp_afc_item( $afc_addon_id, 0, 5.00, false ),
	) );
	bhp_bundle_addon_apply_free_price( $afc_bought_then_completed );
	$afc_btc_items = $afc_bought_then_completed->get_cart();
	$afc_btc_addon = end( $afc_btc_items );
	bhp_afc_assert(
		0.0 === (float) $afc_btc_addon['data']->get_price(),
		'§6 ⭐ a copy the customer ticked BEFORE completing the collection becomes free too - nobody pays $5 for the advertised-free item',
		$failures
	);

	// The add-on is still allowlisted, so it still cannot break shipping.
	bhp_afc_assert(
		false === bhp_bundle_cart_has_unrelated_items( $afc_qualifying ),
		'§6 the granted line is still an ALLOWLISTED add-on, so has_unrelated stays false and the tier table still runs',
		$failures
	);
	bhp_afc_assert(
		0.00 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_qualifying ) ),
		'§6 ⭐ a collection + the free add-on still ships FREE',
		$failures
	);
	bhp_afc_assert(
		1.99 === bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( $afc_paid ) ),
		'§6 1 paperback + a PAID add-on still ships $1.99 (the digital file moves no book-count tier)',
		$failures
	);

	echo "\n=== §7 · the item-data note follows the same predicate ===\n";

	$afc_note_q = bhp_bundle_addon_free_item_data( array(), $afc_q_addon );
	$afc_note_book = bhp_bundle_addon_free_item_data( array(), $afc_q_items[ array_keys( $afc_q_items )[0] ] );

	/*
	 * ⚠ THIS SECTION READS WC()->cart, WHICH IS THE CLI'S OWN EMPTY CART.
	 *   `bhp_bundle_addon_free_item_data()` deliberately asks the LIVE cart
	 *   rather than the passed item's cart, because that is the only cart
	 *   WooCommerce gives the filter any way to reach. Under WP-CLI that
	 *   cart is empty, so the honest expectation here is NO note - and
	 *   asserting the absence still proves the gate is doing its job.
	 */
	bhp_afc_assert(
		is_array( $afc_note_q ),
		'§7 the item-data filter returns an array',
		$failures
	);
	bhp_afc_assert(
		empty( $afc_note_book ),
		'§7 ⭐ a BOOK line never gets the "FREE with your collection" note',
		$failures
	);
	if ( function_exists( 'WC' ) && WC()->cart && bhp_bundle_cart_earns_free_addon( WC()->cart ) ) {
		bhp_afc_assert(
			! empty( $afc_note_q ),
			'§7 the add-on line gets the note while the LIVE cart qualifies',
			$failures
		);
	} else {
		bhp_afc_assert(
			empty( $afc_note_q ),
			'§7 ⭐ with a non-qualifying LIVE cart the note is withheld even for the add-on line - the claim tracks the cart, never the product',
			$failures
		);
		bhp_afc_skip( '§7 the positive note case needs a qualifying live cart (browser QA covers it)', $skipped );
	}
}

echo "\n=== §8 · REMOVED MEANS REMOVED - asserted from the source, honestly labelled ===\n";

/*
 * ⚠ THESE ARE SOURCE ASSERTIONS, NOT BEHAVIOURAL ONES, AND THEY SAY SO.
 *   The nag-loop guard only has meaning across HTTP requests with a real
 *   WooCommerce session, which a CLI process does not have. What can be
 *   proved here is that the code still contains the three structures the
 *   policy is made of - and each of them is a thing a well-meaning tidy-up
 *   would remove.
 */
$afc_src = @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/addon-free-with-collection.php' );
$afc_code = '';
if ( is_string( $afc_src ) ) {
	foreach ( token_get_all( $afc_src ) as $token ) {
		if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$afc_code .= is_array( $token ) ? $token[1] : $token;
	}
}

bhp_afc_assert( '' !== $afc_code, '§8 the module source is readable', $failures );
bhp_afc_assert(
	1 === preg_match( '/bhp_bundle_addon_free_declined\(\)/', $afc_code )
	&& 1 === preg_match( '/!\s*bhp_bundle_addon_free_declined\(\)/', $afc_code ),
	'§8 ⭐ the auto-include is gated on NOT having been declined - the nag loop is structurally impossible',
	$failures
);
bhp_afc_assert(
	1 === preg_match( '/bhp_bundle_addon_withdrawing\(\s*true\s*\)/', $afc_code )
	&& 1 === preg_match( '/bhp_bundle_addon_withdrawing\(\s*false\s*\)/', $afc_code ),
	'§8 ⭐ our own withdrawal is bracketed by the withdrawing flag, so an ordinary book removal cannot latch the decline',
	$failures
);
bhp_afc_assert(
	1 === preg_match( '/static\s+\$running\s*=\s*false;/', $afc_code ),
	'§8 the re-entrancy guard survives (WC_Cart hooks calculate_totals onto woocommerce_add_to_cart, which add_to_cart fires)',
	$failures
);
bhp_afc_assert(
	0 === preg_match( '/update_post_meta|update_option|->save\(\)/', $afc_code ),
	'§8 ⛔ THE PRODUCT-RECORD ASSERTION: the module writes NO post meta, NO option and saves NO object. The $0.00 is cart-line pricing only.',
	$failures
);
bhp_afc_assert(
	1 === preg_match( '/->set_price\(\s*0\s*\)/', $afc_code ),
	'§8 and the zero is applied with set_price() on the cart item\'s cloned product, the WooCommerce cart-scoped mechanism',
	$failures
);

echo "\n=== §9 · the product record itself is UNCHANGED (live read, not a source read) ===\n";

/*
 * ⭐ THE ONE ASSERTION IN THIS FILE THAT TOUCHES THE DATABASE, and it is a
 *    READ. Andrew's gate is that product 538 (production) / the staging
 *    equivalent stays a $5.00 product record; this release delivers a $0.00
 *    CART LINE. If this ever fails, the implementation crossed a gate.
 */
if ( empty( $afc_ids ) ) {
	bhp_afc_skip( '§9 no add-on product on this environment to read', $skipped );
} else {
	$afc_product = wc_get_product( (int) $afc_ids[0] );
	if ( ! $afc_product ) {
		bhp_afc_assert( false, '§9 the add-on product id resolves to a product', $failures );
	} else {
		bhp_afc_assert(
			abs( (float) $afc_product->get_price() - 5.00 ) < 0.001,
			'§9 ⭐ the ACTIVITY BOOK PRODUCT RECORD is still priced $5.00 - the free-ness lives in the cart, never in the catalogue (got $' . $afc_product->get_price() . ')',
			$failures
		);
		bhp_afc_assert( $afc_product->is_downloadable(), '§9 it is still downloadable (a $0.00 line still has to deliver a file)', $failures );
		bhp_afc_assert( $afc_product->is_purchasable(), '§9 it is still purchasable', $failures );
		bhp_afc_assert( $afc_product->is_in_stock(), '§9 it is still in stock', $failures );
		bhp_afc_assert( 'BHP-ACTIVITY-BOOK-01' === $afc_product->get_sku(), '§9 the SKU is unchanged', $failures );
	}
}

echo "\n";
if ( $skipped ) {
	echo count( $skipped ) . " SKIPPED (stated, not hidden):\n";
	foreach ( $skipped as $s ) {
		echo "  - {$s}\n";
	}
	echo "\n";
}
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED (9 sections)\n";
	exit( 0 );
}

echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
