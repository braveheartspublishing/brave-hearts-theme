<?php
/**
 * THE OFFER ENGINE — `FD-579` / `FD-581`. Plugin 1.8.62.
 * Workstream `CYCLE165-LD-SHOP-MATRIX-FINISH`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-offer-engine.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY THIS SUITE EXISTS AT ALL: THE OFFER ENGINE MOVES MONEY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It adds a negative fee to a real customer's cart. Three of its failure modes
 * are silent and expensive, and every one of them is asserted below:
 *
 *   · DOUBLE-CLAIMING one physical book into two offers (§4);
 *   · PRICING A GATED OFFER whose books do not exist (§3);
 *   · GETTING THE STACKED TOTAL WRONG once Andrew ruled that the pair offer
 *     and the chapter tier BOTH fire (§5, §5b).
 *
 * ⛔ THE THIRD BULLET USED TO READ: "STACKING an offer on top of a chapter-tier
 *    discount that already discounts the same book (§5)" — i.e. stacking was
 *    the failure mode. ⭐ CARRIER ITEM 189 MADE STACKING THE REQUIREMENT. The
 *    superseded line is preserved here rather than deleted so that a reader
 *    who finds it quoted elsewhere can see that it moved, and when.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, STATED PLAINLY
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It does not load a page, build a real WooCommerce cart, contact the Store
 * API or take a payment. §4 and §5 drive `bhp_offer_apply_fees()` with a STUB
 * cart that records `add_fee()` calls — closer than a pure function, and still
 * not a checkout. ⭐ The Blocks cart totals are a DIFFERENT claim and are
 * verified in a real browser, not here.
 *
 * ⛔ NO PRODUCT, PRICE, OPTION, TIER, ZONE, SETTING, CART OR ORDER IS WRITTEN
 *    BY THIS FILE ON ANY ENVIRONMENT.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();
$passes   = 0;

function bhp_oe_assert( $condition, $label, array &$failures, &$passes ) {
	if ( $condition ) {
		echo "  PASS  {$label}\n";
		++$passes;
	} else {
		echo "  FAIL  {$label}\n";
		$failures[] = $label;
	}
}

/** Minimal stand-in for WC_Cart that RECORDS the fees the engine adds. */
class BHP_Offer_Stub_Cart {
	public $items;
	public $coupons;
	public $fees = array();
	/** Fee ids already taken, keyed exactly as WooCommerce keys them. */
	public $fee_ids = array();
	/** Labels WooCommerce would have SILENTLY DROPPED. Asserted, not ignored. */
	public $refused = array();
	public function __construct( array $items, array $coupons = array() ) {
		$this->items   = $items;
		$this->coupons = $coupons;
	}
	public function get_cart() {
		return $this->items; }
	public function get_applied_coupons() {
		return $this->coupons; }
	/**
	 * ⛔⛔ 1.8.65 — THIS NOW REPRODUCES WOOCOMMERCE'S DEDUPLICATION, AND THAT
	 *     CORRECTION IS THE MOST IMPORTANT LINE IN THIS FILE.
	 *
	 * ⭐ WHAT THE OLD VERSION DID: appended every call to an array. So the stub
	 *    happily recorded TWO fees where a real cart keeps ONE — and §5b passed
	 *    while the live Blocks cart charged $46.97 instead of $42.99.
	 *
	 * ⭐ WHAT WOOCOMMERCE ACTUALLY DOES (`WC_Cart_Fees::add_fee()`, read on
	 *    staging, not from memory): it derives the fee id from the NAME and
	 *    refuses a duplicate with `new WP_Error( 'fee_exists', … )`, which
	 *    `WC_Cart::add_fee()` then discards. ⛔ NO NOTICE, NO LOG. The fee
	 *    simply does not exist and the customer pays more.
	 *
	 * ⛔ A STUB THAT IS MORE PERMISSIVE THAN THE REAL THING IS WORSE THAN NO
	 *    STUB, because it converts a live defect into a green suite. Proved
	 *    against a real staging cart before this was written.
	 */
	public function add_fee( $label, $amount, $taxable = false ) {
		$id = function_exists( 'sanitize_title' ) ? sanitize_title( $label ) : strtolower( $label );
		if ( isset( $this->fee_ids[ $id ] ) ) {
			$this->refused[] = $label; // Exactly what WooCommerce does: nothing.
			return;
		}
		$this->fee_ids[ $id ] = true;
		$this->fees[]         = array(
			'label'   => $label,
			'amount'  => (float) $amount,
			'taxable' => $taxable,
		);
	}
	/** Total of every fee added — the number a customer's total moves by. */
	public function fee_total() {
		$t = 0.0;
		foreach ( $this->fees as $f ) {
			$t += $f['amount'];
		}
		return round( $t, 2 );
	}
}

function bhp_oe_item( $product_id, $variation_id = 0, $qty = 1 ) {
	/*
	 * ⭐ 1.8.65 — `data` NOW CARRIES THE REAL `WC_Product`, where one resolves.
	 *    It was `null`, which was sufficient while only `bhp_offer_apply_fees()`
	 *    ran over this stub: that function never reads it.
	 *
	 * ⛔ §5b RUNS `bhp_bundle_apply_discount_fees()` OVER THE SAME STUB, and
	 *    that path reaches `bhp_bundle_prices_match_expected()`, which calls
	 *    `$cart_item['data']->get_price()`. With `null` there it fatals; with a
	 *    real product it does the price-drift check a real cart does. So this
	 *    makes the stub MORE like a cart, never less.
	 *
	 * ⛔ `false` for a deliberately unrecognised id (§6's 999999). That is
	 *    correct and is never dereferenced: `bhp_bundle_identify_cart_item()`
	 *    does not match it, so the price loop skips the line entirely.
	 */
	$buy_id = $variation_id ? $variation_id : $product_id;
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $qty,
		'data'         => function_exists( 'wc_get_product' ) ? wc_get_product( $buy_id ) : null,
	);
}

/** Run the engine over a stub cart and return it, fees recorded. */
function bhp_oe_run( array $items, array $coupons = array() ) {
	$cart = new BHP_Offer_Stub_Cart( $items, $coupons );
	bhp_offer_apply_fees( $cart );
	return $cart;
}

echo "\n══════════ THE OFFER ENGINE — plugin 1.8.62 ══════════\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE CATALOGUE IS A CATALOGUE OF OFFERS, NEVER OF PRODUCTS
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§1] FD-579: an offer is not a product\n";
$catalog = bhp_offer_catalog();

$no_product_identity = true;
foreach ( $catalog as $offer ) {
	// ⛔ THE `FD-579` INVARIANT. A bundle SKU has no ISBN, and fulfilment
	//    routes per-ISBN. If any of these keys ever appears here, someone has
	//    started building a bundle PRODUCT and the ruling has been crossed.
	foreach ( array( 'product_id', 'variation_id', 'sku', 'isbn' ) as $forbidden ) {
		if ( array_key_exists( $forbidden, $offer ) ) {
			$no_product_identity = false;
		}
	}
}
bhp_oe_assert( $no_product_identity, 'no offer row carries a product id, variation id, SKU or ISBN', $failures, $passes );

/*
 * ⭐ THE PRICES ARE ANDREW'S. Each literal below is quoted from
 *    `FOUNDER-DECISIONS-2026-08-01.md` PART 66 §66.6's twelve-row matrix. This
 *    assertion is what stops a price being "tidied" by anyone but him.
 */
$ruled = array(
	'mariana_pb_colouring' => 22.99, // FD-581
	'mariana_hc_colouring' => 28.99, // FD-581
	'colouring_collection' => 34.99, // FD-581
	'six_book_pb'          => 63.99, // FD-580
	'six_book_hc'          => 79.99, // FD-581
);
foreach ( $ruled as $key => $price ) {
	bhp_oe_assert(
		isset( $catalog[ $key ] ) && abs( bhp_offer_price( $key ) - $price ) < 0.001,
		sprintf( '%s is $%.2f, exactly as ruled', $key, $price ),
		$failures,
		$passes
	);
}
bhp_oe_assert( 5 === count( $catalog ), 'the catalogue holds exactly his five offers, no invented sixth', $failures, $passes );

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE SAVING IS DERIVED, NEVER STORED
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§2] The saving is recomputed from live prices\n";
foreach ( array( 'mariana_pb_colouring', 'mariana_hc_colouring' ) as $key ) {
	if ( ! bhp_offer_is_purchasable( $key ) ) {
		echo "  SKIP  {$key} is not purchasable on this environment (no colouring product record)\n";
		continue;
	}
	$total  = bhp_offer_component_total( $key );
	$saving = bhp_offer_saving( $key );
	bhp_oe_assert(
		abs( ( $total - bhp_offer_price( $key ) ) - $saving ) < 0.001,
		sprintf( '%s: saving == component_total - offer_price (%.2f - %.2f = %.2f)', $key, $total, bhp_offer_price( $key ), $saving ),
		$failures,
		$passes
	);
	bhp_oe_assert( $saving > 0, sprintf( '%s: the offer is actually cheaper than buying separately', $key ), $failures, $passes );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ⛔⛔ THE GATE — TWO INDEPENDENT LOCKS ON THE THREE UNBUILDABLE OFFERS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ONE COLOURING BOOK EXISTS. He priced a three-book colouring collection and
 *    two six-book collections; those books have not been written. ⛔ The shop
 *    never renders a row for a product that cannot be bought today.
 */
echo "\n[§3] The gated offers are gated, twice over\n";
foreach ( array( 'colouring_collection', 'six_book_pb', 'six_book_hc' ) as $key ) {
	bhp_oe_assert( ! bhp_offer_is_purchasable( $key ), sprintf( 'LOCK 1 — %s: components do not resolve', $key ), $failures, $passes );
	bhp_oe_assert( null === bhp_offer_components( $key ), sprintf( 'LOCK 1 — %s: components() is null, so it fails closed everywhere downstream', $key ), $failures, $passes );
	bhp_oe_assert( 'unimplemented' === $catalog[ $key ]['cart_rule'], sprintf( 'LOCK 2 — %s: cart_rule refuses to price it', $key ), $failures, $passes );
}
bhp_oe_assert(
	! in_array( 'colouring_collection', bhp_offer_purchasable_keys(), true )
	&& ! in_array( 'six_book_pb', bhp_offer_purchasable_keys(), true )
	&& ! in_array( 'six_book_hc', bhp_offer_purchasable_keys(), true ),
	'no gated offer appears in the purchasable list any surface renders from',
	$failures,
	$passes
);
bhp_oe_assert(
	array() === array_diff( array_keys( $catalog ), array( 'mariana_pb_colouring', 'mariana_hc_colouring', 'colouring_collection', 'six_book_pb', 'six_book_hc' ) ),
	'Everest and Amazon colouring titles are NOT invented anywhere in the catalogue',
	$failures,
	$passes
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ⭐⭐ THE CART. ONE PHYSICAL BOOK CANNOT BE SOLD INTO TWO OFFERS.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§4] Cart pricing, and the pool that stops double-claiming\n";

$colouring_ids = bhp_colouring_product_ids();
if ( empty( $colouring_ids['mariana'] ) ) {
	echo "  SKIP  §4 and §5 need a resolved colouring product; none on this environment\n";
} else {
	$COLOUR   = (int) $colouring_ids['mariana'];
	$catalogue = bhp_bundle_catalog();
	$PB_M     = $catalogue['paperback']['mariana'];
	$HC_M     = $catalogue['hardcover']['mariana'];
	$PB_E     = $catalogue['paperback']['everest'];
	$PB_A     = $catalogue['paperback']['amazon'];

	$pb_m = bhp_oe_item( $PB_M['product_id'], $PB_M['variation_id'], 1 );
	$hc_m = bhp_oe_item( $HC_M['product_id'], $HC_M['variation_id'], 1 );
	$col  = bhp_oe_item( $COLOUR, 0, 1 );

	$expected_pair = round( bhp_offer_saving( 'mariana_pb_colouring' ), 2 );

	// The offer, exactly as advertised: one paperback, one colouring book.
	$cart = bhp_oe_run( array( $pb_m, $col ) );
	bhp_oe_assert(
		abs( $cart->fee_total() + $expected_pair ) < 0.011,
		sprintf( 'PB + colouring -> one fee of -$%.2f, so the cart totals $22.99', $expected_pair ),
		$failures,
		$passes
	);
	bhp_oe_assert( 1 === count( $cart->fees ), 'PB + colouring -> exactly ONE fee, never two', $failures, $passes );

	/*
	 * ⛔⛔ THE DOUBLE-CLAIM CASE, AND IT IS THE REASON THE POOL EXISTS.
	 *    One paperback, one hardcover, ONE colouring book. Both Mariana pair
	 *    offers match on paper. There is only one colouring book, and it can
	 *    only be in one pair. Two fees here would give away $1.99 of a book
	 *    that was never bought twice.
	 */
	$cart = bhp_oe_run( array( $pb_m, $hc_m, $col ) );
	bhp_oe_assert(
		1 === count( $cart->fees ),
		'PB + HC + ONE colouring book -> exactly ONE pair fires (the pool is not double-claimed)',
		$failures,
		$passes
	);

	// Two colouring books and two paperbacks is genuinely two pairs.
	$cart = bhp_oe_run( array( bhp_oe_item( $PB_M['product_id'], $PB_M['variation_id'], 2 ), bhp_oe_item( $COLOUR, 0, 2 ) ) );
	bhp_oe_assert(
		abs( $cart->fee_total() + ( 2 * $expected_pair ) ) < 0.021,
		'2 x PB + 2 x colouring -> the pair fires TWICE, because two pairs were genuinely bought',
		$failures,
		$passes
	);

	// A colouring book on its own earns no offer.
	$cart = bhp_oe_run( array( $col ) );
	bhp_oe_assert( array() === $cart->fees, 'a colouring book alone -> no offer fee', $failures, $passes );

	// A chapter book on its own earns no offer.
	$cart = bhp_oe_run( array( $pb_m ) );
	bhp_oe_assert( array() === $cart->fees, 'a paperback alone -> no offer fee', $failures, $passes );

	/*
	 * ⛔ AN OFFER IS PER-ADVENTURE. An Everest paperback beside a MARIANA
	 *    colouring book is not the Mariana bundle, however many books it is.
	 */
	$cart = bhp_oe_run( array( bhp_oe_item( $PB_E['product_id'], $PB_E['variation_id'], 1 ), $col ) );
	bhp_oe_assert( array() === $cart->fees, 'Everest PB + Mariana colouring -> no offer (an offer is per-adventure)', $failures, $passes );

	/* ═══════════════════════════════════════════════════════════════════════
	 * §5 · ⭐⭐⭐ CARRIER ITEM 189 — STACKING IS ON, AND THERE IS NO CAP
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐ Andrew Signore, ~06:0x−0600 2026-08-21, read first-hand at source by
	 *    the agent that wrote this section (`FOUNDER-VERBATIM-2026-08-05-
	 *    PRODUCTION-DEPLOY-AUTHORIZATION.md` line 819, G: mount, NOT relayed):
	 *
	 *      "So no cap and stack is the way to go?"
	 *
	 * ⛔ THIS SECTION IS THE INVERSE OF WHAT IT ASSERTED IN 1.8.62–1.8.64.
	 *    It used to prove the pair offer STOOD DOWN on a collection cart. The
	 *    superseded assertion read:
	 *
	 *      '3 chapter paperbacks + colouring -> the OFFER engine adds nothing,
	 *       so the Mariana paperback is not discounted twice'
	 *
	 *    ⭐ It was correct for the judgement it tested. He overturned the
	 *       judgement, so the assertion inverts with it.
	 * ═══════════════════════════════════════════════════════════════════════ */
	echo "\n[§5] Item 189: the pair offer STACKS with the chapter-tier ladder\n";

	$three_pb = array(
		bhp_oe_item( $PB_M['product_id'], $PB_M['variation_id'], 1 ),
		bhp_oe_item( $PB_E['product_id'], $PB_E['variation_id'], 1 ),
		bhp_oe_item( $PB_A['product_id'], $PB_A['variation_id'], 1 ),
	);
	$cart = bhp_oe_run( array_merge( $three_pb, array( $col ) ) );
	bhp_oe_assert(
		1 === count( $cart->fees ),
		'3 chapter paperbacks + colouring -> the OFFER engine DOES fire (item 189: stacking is on)',
		$failures,
		$passes
	);
	bhp_oe_assert(
		1 === count( $cart->fees ) && abs( $cart->fee_total() - ( -1.99 ) ) < 0.001,
		sprintf( '...and the offer fee is exactly -$1.99, the live derived saving (got %.2f)', $cart->fee_total() ),
		$failures,
		$passes
	);

	/*
	 * ⭐ AND IT IS STILL ONE FILTER LINE TO REVERSE — now in the OTHER
	 *    direction. Asserted rather than claimed in a comment, because
	 *    "reversible" is exactly the kind of promise that quietly stops being
	 *    true, and because re-suppressing is the move Andrew makes if the
	 *    contribution read ever turns.
	 */
	add_filter( 'bhp_offer_tier_precedence', 'bhp_oe_force_suppress' );
	$cart = bhp_oe_run( array_merge( $three_pb, array( $col ) ) );
	remove_filter( 'bhp_offer_tier_precedence', 'bhp_oe_force_suppress' );
	bhp_oe_assert(
		array() === $cart->fees,
		'...and bhp_offer_tier_precedence restores suppression in one line, so the judgement stays Andrew\'s to change back',
		$failures,
		$passes
	);

	/*
	 * ⛔ NO QUANTITY CAP — his second limb. Two complete pairs in one cart
	 *    must earn the saving TWICE. ⭐ This required no code change and is
	 *    asserted here precisely so nobody "adds" a cap later believing one
	 *    was removed.
	 */
	$two_pairs = array(
		bhp_oe_item( $PB_M['product_id'], $PB_M['variation_id'], 2 ),
		bhp_oe_item( $COLOUR, 0, 2 ),
	);
	$cart = bhp_oe_run( $two_pairs );
	bhp_oe_assert(
		1 === count( $cart->fees ) && abs( $cart->fee_total() - ( -3.98 ) ) < 0.001,
		sprintf( 'NO CAP: 2 Mariana paperbacks + 2 colouring books -> the saving is claimed TWICE, -$3.98 (got %.2f)', $cart->fee_total() ),
		$failures,
		$passes
	);
	$cart = bhp_oe_run(
		array(
			bhp_oe_item( $PB_M['product_id'], $PB_M['variation_id'], 3 ),
			bhp_oe_item( $COLOUR, 0, 3 ),
		)
	);
	bhp_oe_assert(
		abs( $cart->fee_total() - ( -5.97 ) ) < 0.001,
		sprintf( 'NO CAP: three complete pairs -> -$5.97, still uncapped (got %.2f)', $cart->fee_total() ),
		$failures,
		$passes
	);

	/* ═══════════════════════════════════════════════════════════════════════
	 * §5b · ⭐⭐⭐ THE `finance-analytics` ROW A, END TO END — BOTH ENGINES OVER ONE CART
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐⭐ THIS IS THE ONLY ASSERTION IN THE SUITE THAT RUNS *BOTH* FEE
	 *     ENGINES over the same cart, and it exists because item 189's whole
	 *     point is what happens when they BOTH fire. §5 above proves the
	 *     offer engine fires; it cannot prove the customer's total, because
	 *     `bhp_offer_apply_fees()` never sees the tier fee.
	 *
	 *     `bhp_bundle_apply_discount_fees()` (priority 20) is therefore run
	 *     FIRST, exactly as WooCommerce runs it, then the offer engine at 21.
	 *
	 * ⛔ WHAT THIS STILL CANNOT PROVE, STATED PLAINLY: it is a stub cart. It
	 *    does not load /cart/, does not call the Store API, does not compute
	 *    tax or shipping, and does not take a payment. ⭐ THE $42.99 A REAL
	 *    SHOPPER IS CHARGED IS A DIFFERENT CLAIM AND IS VERIFIED IN A REAL
	 *    BLOCKS CART, in a browser, in the QA evidence for this build.
	 * ═══════════════════════════════════════════════════════════════════════ */
	echo "\n[§5b] finance-analytics Row A: the four-item cart carries BOTH discounts\n";

	$row_a = array_merge( $three_pb, array( $col ) );

	$components = 0.0;
	foreach ( $row_a as $line ) {
		$p           = wc_get_product( $line['variation_id'] ? $line['variation_id'] : $line['product_id'] );
		$components += $p ? (float) $p->get_price() * (int) $line['quantity'] : 0.0;
	}

	$cart = new BHP_Offer_Stub_Cart( $row_a );
	bhp_bundle_apply_discount_fees( $cart ); // priority 20 — the chapter tier.
	bhp_offer_apply_fees( $cart );           // priority 21 — the pair offer.

	bhp_oe_assert(
		2 === count( $cart->fees ),
		sprintf( 'the 4-item cart carries TWO fees, not one (got %d)', count( $cart->fees ) ),
		$failures,
		$passes
	);
	bhp_oe_assert(
		abs( $components - 48.96 ) < 0.001,
		sprintf( 'components read LIVE from WooCommerce total $48.96 (got %.2f)', $components ),
		$failures,
		$passes
	);
	bhp_oe_assert(
		abs( $cart->fee_total() - ( -5.97 ) ) < 0.001,
		sprintf( 'the two discounts total -$5.97 (-3.98 tier + -1.99 pair) (got %.2f)', $cart->fee_total() ),
		$failures,
		$passes
	);
	bhp_oe_assert(
		abs( ( $components + $cart->fee_total() ) - 42.99 ) < 0.001,
		sprintf( 'the cart charges $42.99 before tax and shipping — finance-analytics Row A (got %.2f)', $components + $cart->fee_total() ),
		$failures,
		$passes
	);
	/*
	 * ⛔ AND THE $0.00 FREE-SHIPPING TIER IS NOT LOST BY STACKING. Three
	 *    distinct adventures still complete the collection with a colouring
	 *    book in the cart (1.8.61 made a colouring book RELATED, not
	 *    unrelated), so `FD-583` still holds on this exact cart.
	 */
	$eval_a = bhp_bundle_evaluate_cart( $cart );
	bhp_oe_assert(
		! empty( $eval_a['is_complete_collection'] ) && empty( $eval_a['has_unrelated'] )
		&& 0.0 === (float) bhp_bundle_shipping_amount( $eval_a ),
		'...and it still ships free — stacking did not cost the shopper FD-583',
		$failures,
		$passes
	);

	/* ═══════════════════════════════════════════════════════════════════════
	 * §5c · ⛔⛔ THE FEE-LABEL COLLISION — THE DEFECT THAT ALMOST SHIPPED
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐ A WooCommerce fee's identity IS its name. A second fee with the same
	 *    name is discarded with a `WP_Error` that `WC_Cart::add_fee()` throws
	 *    away — no notice, no log, and the customer pays more.
	 *
	 * ⛔ UNTIL 1.8.65 THE OFFER FEE AND THE CHAPTER-TIER FEE SHARED A LABEL.
	 *    That was harmless only while suppression guaranteed they could never
	 *    fire together. ⭐ ITEM 189 REMOVED THAT GUARANTEE, and on the first
	 *    real Blocks cart the offer fee vanished: one fee, −$3.98, $46.97
	 *    charged instead of $42.99 — a flip that flipped and changed nothing.
	 *
	 * ⛔ THE SUITE DID NOT CATCH IT because the stub appended fees instead of
	 *    deduplicating them. `BHP_Offer_Stub_Cart::add_fee()` now reproduces
	 *    WooCommerce exactly, which is what makes the §5b assertions above
	 *    mean anything at all.
	 * ═══════════════════════════════════════════════════════════════════════ */
	echo "\n[§5c] Fee labels are unique across BOTH engines\n";

	bhp_oe_assert(
		empty( $cart->refused ),
		sprintf(
			'⛔ NOTHING WAS SILENTLY DROPPED on finance-analytics Row A (%d refused: %s)',
			count( $cart->refused ),
			$cart->refused ? implode( ', ', $cart->refused ) : 'none'
		),
		$failures,
		$passes
	);
	$labels = bhp_offer_all_fee_labels();
	$ids    = array_map( 'sanitize_title', $labels );
	bhp_oe_assert(
		count( $ids ) === count( array_unique( $ids ) ),
		sprintf(
			'⛔ every fee label this plugin can put on one cart has a DISTINCT WooCommerce id (%d labels: %s)',
			count( $labels ),
			implode( ' | ', $labels )
		),
		$failures,
		$passes
	);
	bhp_oe_assert(
		sanitize_title( bhp_offer_fee_label( 'mariana_pb_colouring' ) ) !== sanitize_title( 'Bundle Savings (Paperback)' ),
		'⛔ …and the pair-offer label specifically does NOT collide with the chapter-tier label it used to be identical to',
		$failures,
		$passes
	);
	/*
	 * ⭐ THE NEGATIVE CONTROL FOR THE STUB ITSELF: it must actually refuse a
	 *    duplicate, or §5c above proves nothing.
	 */
	$dup = new BHP_Offer_Stub_Cart( array() );
	$dup->add_fee( 'Bundle Savings (Paperback)', -1.00, false );
	$dup->add_fee( 'Bundle Savings (Paperback)', -2.00, false );
	bhp_oe_assert(
		1 === count( $dup->fees ) && 1 === count( $dup->refused ),
		'⛔ the STUB reproduces WooCommerce\'s silent drop — a permissive stub turns a live defect into a green suite',
		$failures,
		$passes
	);

	/* ═══════════════════════════════════════════════════════════════════════
	 * §6 · IT FAILS SAFE
	 * ═══════════════════════════════════════════════════════════════════════ */
	echo "\n[§6] Fail-safe paths\n";

	// ⛔ An unrecognised product means the engine does not understand the cart.
	$cart = bhp_oe_run( array( $pb_m, $col, bhp_oe_item( 999999, 0, 1 ) ) );
	bhp_oe_assert( array() === $cart->fees, 'an unrecognised product in the cart -> NO offer fee (has_unrelated fails safe)', $failures, $passes );

	// ⛔ A coupon suppresses the offer fee.
	$cart = bhp_oe_run( array( $pb_m, $col ), array( 'SOMECOUPON' ) );
	bhp_oe_assert( array() === $cart->fees, 'a coupon on the cart -> NO offer fee (no silent stacking)', $failures, $passes );

	// ⛔ The fee is non-taxable, for the reason the chapter-tier fee is.
	$cart = bhp_oe_run( array( $pb_m, $col ) );
	bhp_oe_assert( false === $cart->fees[0]['taxable'], 'the offer fee is non-taxable, so WooCommerce reduces tax on its own rather than double-adjusting', $failures, $passes );

	// ⛔ The fee is NEGATIVE. A positive one would charge the customer more.
	bhp_oe_assert( $cart->fees[0]['amount'] < 0, 'the offer fee is negative, always', $failures, $passes );

	// Empty cart.
	$cart = bhp_oe_run( array() );
	bhp_oe_assert( array() === $cart->fees, 'an empty cart -> no fee', $failures, $passes );
}

/**
 * ⭐ Restores the pre-item-189 suppression, for the reversibility assertion in
 *    §5. Renamed from `bhp_oe_force_stack()` (which returned FALSE) when the
 *    default flipped — the helper now has to push in the OTHER direction, and
 *    a helper called "force_stack" that forced suppression would be a trap.
 */
function bhp_oe_force_suppress() {
	return true; }

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · ⭐ THE NEGATIVE CONTROL — this harness fails when it should
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ A suite that has never failed has not been shown to be capable of
 *    failing. The two rows below are DELIBERATELY FALSE.
 */
echo "\n[§7] Negative control (the two FAILs below are EXPECTED)\n";
$control_failures = array();
$control_passes   = 0;
bhp_oe_assert( 22.99 === bhp_offer_price( 'mariana_pb_colouring' ) + 1, 'DELIBERATE MISMATCH — this row is SUPPOSED to fail', $control_failures, $control_passes );
bhp_oe_assert( false, 'DELIBERATE FALSE — this row is SUPPOSED to fail', $control_failures, $control_passes );
bhp_oe_assert(
	2 === count( $control_failures ) && 0 === $control_passes,
	'⭐ both deliberate failures were CAUGHT — the harness genuinely fails when it should',
	$failures,
	$passes
);

echo "\n=== RESULT ===\n";
printf( "  %d passed, %d failed\n", $passes, count( $failures ) );
if ( $failures ) {
	echo "FAILURES:\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
} else {
	echo "  ALL PASS\n";
}
