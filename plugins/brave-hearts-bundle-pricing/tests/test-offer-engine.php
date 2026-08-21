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
 *   · STACKING an offer on top of a chapter-tier discount that already
 *     discounts the same book (§5);
 *   · PRICING A GATED OFFER whose books do not exist (§3).
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
	public function __construct( array $items, array $coupons = array() ) {
		$this->items   = $items;
		$this->coupons = $coupons;
	}
	public function get_cart() {
		return $this->items; }
	public function get_applied_coupons() {
		return $this->coupons; }
	public function add_fee( $label, $amount, $taxable = false ) {
		$this->fees[] = array(
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
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $qty,
		'data'         => null,
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
	 * §5 · ⚠️ THE FLAGGED JUDGEMENT — TIER PRECEDENCE
	 * ═══════════════════════════════════════════════════════════════════════ */
	echo "\n[§5] The chapter-tier ladder outranks a pair offer on the same format\n";

	$three_pb = array(
		bhp_oe_item( $PB_M['product_id'], $PB_M['variation_id'], 1 ),
		bhp_oe_item( $PB_E['product_id'], $PB_E['variation_id'], 1 ),
		bhp_oe_item( $PB_A['product_id'], $PB_A['variation_id'], 1 ),
	);
	$cart = bhp_oe_run( array_merge( $three_pb, array( $col ) ) );
	bhp_oe_assert(
		array() === $cart->fees,
		'3 chapter paperbacks + colouring -> the OFFER engine adds nothing, so the Mariana paperback is not discounted twice',
		$failures,
		$passes
	);

	/*
	 * ⭐ AND IT IS ONE FILTER LINE TO REVERSE, which is asserted rather than
	 *    merely claimed in a comment — because "reversible" is exactly the
	 *    kind of promise that quietly stops being true.
	 */
	add_filter( 'bhp_offer_tier_precedence', 'bhp_oe_force_stack' );
	$cart = bhp_oe_run( array_merge( $three_pb, array( $col ) ) );
	remove_filter( 'bhp_offer_tier_precedence', 'bhp_oe_force_stack' );
	bhp_oe_assert(
		1 === count( $cart->fees ),
		'...and bhp_offer_tier_precedence reverses it in one line, so the judgement is Andrew\'s to change',
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

function bhp_oe_force_stack() {
	return false; }

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
