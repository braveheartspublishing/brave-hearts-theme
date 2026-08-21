<?php
/**
 * Brave Hearts Bundle Pricing — THE OFFER ENGINE.
 * Plugin 1.8.62 / theme 1.19.277. Workstream `CYCLE165-LD-SHOP-MATRIX-FINISH`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE RULING THIS FILE SERVES — `FD-579`, READ FIRST-HAND AT SOURCE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-20 ~17:1x−0600, carrier item 155. ⭐ VERIFIED IN THE
 * CANON BY THE AGENT THAT WROTE THIS FILE — `FOUNDER-DECISIONS-2026-08-01.md`
 * PART 66 §66.4, read at source, NOT relayed:
 *
 *   "WE do the same thing we offer for the collection - on the backend its all
 *    put together for bookvault"
 *
 * ⛔⛔ AND THE LIMB THAT DEFINES THIS FILE'S SHAPE: **NO BUNDLE PRODUCT
 *    RECORDS.** Not one. `FD-579` states the mechanism in his own words, and
 *    he stated it before anyone argued it to him: fulfilment routes PER-ISBN,
 *    and a bundle SKU has no ISBN. A "bundle" here is therefore what a
 *    "collection" has always been in this plugin — a CART THAT QUALIFIES, plus
 *    a page that helps a shopper assemble it. Every book in it is a real,
 *    individually-mapped WooCommerce line item that routes to a printer on its
 *    own record.
 *
 * ⭐ SO THIS FILE INVENTS NO COMMERCE MECHANISM. It is the collection
 *    mechanism, generalised to a second axis:
 *
 *      bhp_bundle_catalog()   the six chapter editions        (unchanged)
 *      bhp_colouring_catalog() the colouring line, SKU-keyed  (1.8.61)
 *      bhp_offer_catalog()    ⭐ NEW — named offers ACROSS those two lines
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE PRICES — `FD-581`, AND EVERY ONE OF THEM IS HIS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   "I accept all those price recommendations"  — carrier item 157, ~17:3x−0600
 *
 * ⭐ Read first-hand at `FOUNDER-DECISIONS-2026-08-01.md` §66.6, whose
 *    twelve-row matrix is the authority for every literal in this file:
 *
 *      PB + colouring           $22.99   `FD-581`
 *      HC + colouring upsell    $28.99   `FD-581`
 *      Colouring collection (3) $34.99   `FD-581`   ⛔ GATED — see below
 *      6-book PB collection     $63.99   `FD-580`   ⛔ GATED
 *      6-book HC collection     $79.99   `FD-581`   ⛔ GATED
 *
 * ⛔ AN OFFER PRICE IS A FOUNDER LITERAL AND IS WRITTEN AS ONE. A COMPONENT
 *    PRICE NEVER IS. Every saving, every "was" figure and every component
 *    subtotal in this file is READ LIVE from WooCommerce at render and cart
 *    time. That split is the whole of `R2.2` and of `evidence-verification` §5
 *    (a claim built from two sourced facts is a NEW claim): the offer price is
 *    sourced from him, the saving is DERIVED and must be recomputed, never
 *    inherited from a draft.
 *
 * ⛔ AND IT FAILS SAFE ON DRIFT. `bhp_offer_discount_amount()` computes
 *    `component_total − offer_price`. If a component price ever moves, the
 *    DISCOUNT moves with it and the customer still pays exactly the founder's
 *    offer price. A fixed-dollar discount would silently start producing a
 *    different total; this cannot. Where the arithmetic would produce a
 *    discount of zero or less, the offer simply does not fire.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE GATE — WHY THREE OF HIS FIVE OFFERS ARE IN THIS FILE AND OFF
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ONE COLOURING BOOK EXISTS. He ruled prices for a three-book colouring
 *    collection and for two six-book collections; those offers cannot be
 *    honoured, because the books they name have not been written.
 *
 * ⛔ THE RULE FROM THE SPEC, AND IT IS ABSOLUTE (`SHOP-MATRIX-SPEC-2026-08-20.md`
 *    §3.2): **THE SHOP NEVER RENDERS A ROW FOR A PRODUCT THAT CANNOT BE BOUGHT
 *    TODAY.** No "coming soon" card, no greyed tile. A row that cannot be
 *    purchased is REMOVED, not softened.
 *
 * ⭐⭐ SO THE GATE IS STRUCTURAL, NOT A HARDCODED "DO NOT RENDER".
 *    `bhp_offer_is_purchasable()` asks WooCommerce whether every component of
 *    an offer resolves to a live, purchasable product. Today the three gated
 *    offers name colouring SKUs that resolve to nothing, so they are absent
 *    from every surface BY CONSTRUCTION. ⭐ The day Everest's colouring book
 *    gets a product record, its rows appear with NO CODE CHANGE — and not one
 *    hour before. A boolean a human has to remember to flip is a boolean that
 *    gets flipped early.
 *
 * ⛔⛔ THE GATED OFFERS CARRY A SECOND, INDEPENDENT GUARD: `cart_rule` is
 *    `unimplemented`, and `bhp_offer_apply_fees()` refuses to price them. A
 *    SET offer overlaps the existing chapter-tier ladder (a six-book cart is
 *    also a complete chapter collection), and resolving that overlap is real
 *    commercial arithmetic that must not be written speculatively against
 *    products that do not exist. ⭐ SPEC-STUBBED, EXPLICITLY, RATHER THAN
 *    HALF-BUILT: two gates, and neither depends on the other.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE JUDGEMENT WENT TO ANDREW AND HE RULED. STACKING IS ON.
 *       Plugin 1.8.65 · `CYCLE165-LD-FLOW-ADJUSTMENTS`
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ CARRIER ITEM 189, READ FIRST-HAND AT SOURCE by the agent that made this
 *    change — `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-AUTHORIZATION.md`
 *    line 819, read on the G: mount, NOT relayed. Andrew Signore, ~06:0x−0600
 *    2026-08-21:
 *
 *      "So no cap and stack is the way to go?"
 *
 *    asked as adoption of the recommendation after Frodo (`finance-analytics`)
 *    returned the math his item-187 condition required ("Stack and see what
 *    the math says").
 *
 * ⭐ SO, FROM 1.8.65: A PAIR OFFER STACKS WITH THE CHAPTER-TIER LADDER, AND
 *    THE PAIR OFFER CARRIES NO QUANTITY CAP.
 *
 *    Frodo's Row A, the cart the ruling was decided on, and the one the suite
 *    now asserts: 3 chapter paperbacks + 1 Mariana colouring book.
 *
 *        components   3 × $11.99 + $12.99          = $48.96
 *        tier fee     Bundle Savings (Paperback)   = −$3.98
 *        offer fee    Bundle Savings (Paperback)   = −$1.99
 *        ─────────────────────────────────────────────────────
 *        charged                                     $42.99
 *
 * ⛔ NO QUANTITY CAP IS ADDED, AND NONE WAS EVER PRESENT.
 *    `bhp_offer_claim_instances()` has always claimed as many complete pairs
 *    as the cart's pool holds and `bhp_offer_apply_fees()` has always
 *    multiplied the saving by that count. His "no cap" limb therefore
 *    required NO code change — it is asserted in the suite rather than
 *    implemented, and this line exists so nobody "adds" a cap later believing
 *    one was removed.
 *
 * ⭐ IT REMAINS ONE FILTER LINE TO REVERSE — `bhp_offer_tier_precedence`,
 *    now returning TRUE to restore suppression. The suite asserts the
 *    reversal in that direction.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * ⛔ THE SUPERSEDED JUDGEMENT, PRESERVED VERBATIM SO IT IS NOT RE-DERIVED.
 *    It was the conservative reading, it was correct to flag rather than
 *    decide, and it was flagged, sent up, and OVERTURNED BY THE OWNER. That
 *    is the mechanism working, not a defect:
 *
 *      "⛔ A PAIR OFFER IS SUPPRESSED WHEN ITS FORMAT ALREADY EARNS A
 *          CHAPTER-TIER DISCOUNT. Cart: 3 chapter paperbacks + 1 colouring
 *          book. The tier ladder already discounts those three paperbacks by
 *          −$3.98 (they are the $31.99 Complete Collection). Firing the pair
 *          offer on top would discount the Mariana paperback TWICE, in two
 *          engines, for the same cart.
 *
 *       ⭐ WHY SUPPRESSION AND NOT STACKING, IN ONE LINE: `FD-581` prices a
 *          TWO-ITEM CART at $22.99. It does not say what a four-item cart
 *          costs, and no advertised claim on this site is broken by declining
 *          to invent one. The shopper still receives the full collection
 *          discount AND `FD-583` free shipping. ⚠️ IT IS A JUDGEMENT, IT IS
 *          REPORTED AS ONE TO GANDALF AND ANDREW, and it is the conservative
 *          direction (Standing Rules §1: the stricter reading applies until
 *          Andrew decides otherwise)."
 *
 * ⚠️ WHAT HIS RULING ACTUALLY COSTS, STATED PLAINLY RATHER THAN BURIED: the
 *    Mariana paperback in a four-item cart IS now discounted by two engines
 *    at once. That is the deliberate outcome, not an oversight. The figure is
 *    Frodo's, not this file's: $1.85 per affected cart, identical in all four
 *    shapes he modelled, no negative cart on any known cost.
 *    ⛔ THIS FILE STILL COMPUTES NO CONTRIBUTION FIGURE AND MAKES NO
 *       PROFITABILITY CLAIM. `STACKING-MATH-FOR-ANDREW.pdf` is the record.
 *

 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT TOUCH, ON ANY ENVIRONMENT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * No product record, SKU, price field, stock status, variation, coupon, tax,
 * payment setting, shipping ZONE or shipping METHOD. No Bookvault or Ingram
 * mapping. The chapter tier tables in `bundle-data.php` are read and never
 * rewritten. `bhp_bundle_addon_skus()` is not touched — the 1.8.61 note on why
 * adding a printed book to the weightless add-on allowlist is a trap stands.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ⭐ THE OFFER CATALOGUE — every offer Andrew has ruled, gated or not.
 *
 * ⛔ AN OFFER IS NOT A PRODUCT. There is no `product_id`, no SKU and no ISBN
 *    on any row below, and there must never be one. Each row is a NAME for a
 *    set of real catalogue items plus the price he ruled for buying them
 *    together (`FD-579`).
 *
 * Row shape:
 *   label        Internal identification. ⛔ NOT customer-facing copy — every
 *                customer-visible string is Andrew's and is drafted for him.
 *   kind         'pair' (one chapter book + its colouring book) or 'set'.
 *   format       'paperback'|'hardcover' — the chapter format the offer buys.
 *   chapter      Adventure slugs taken from `bhp_bundle_catalog()[$format]`.
 *   colouring    Adventure slugs taken from `bhp_colouring_catalog()`.
 *   price        ⭐ HIS literal, with the FD that ruled it named on the line.
 *   cart_rule    'pair' — priced by `bhp_offer_apply_fees()`.
 *                'unimplemented' — ⛔ SPEC-STUB. Never priced. See the header.
 *   upsell_of    Optional: the key this offer upgrades, for the format swap.
 *
 * @return array<string,array> Offer key => definition.
 */
function bhp_offer_catalog() {
	/**
	 * The named cross-line offers.
	 *
	 * @param array<string,array> $offers Offer key => definition.
	 */
	return apply_filters(
		'bhp_offer_catalog',
		array(

			/* ─────────────────────────────────────────────────────────────
			 * ⭐ LIVE TODAY. Both components exist and are purchasable.
			 * ───────────────────────────────────────────────────────────── */

			'mariana_pb_colouring' => array(
				'label'     => 'Mariana Trench paperback + Mariana Trench coloring book',
				'kind'      => 'pair',
				'format'    => 'paperback',
				'chapter'   => array( 'mariana' ),
				'colouring' => array( 'mariana' ),
				'price'     => 22.99, // ⭐ FD-581, §66.6 row "PB + colouring bundle".
				'cart_rule' => 'pair',
			),

			'mariana_hc_colouring' => array(
				'label'      => 'Mariana Trench hardcover + Mariana Trench coloring book',
				'kind'       => 'pair',
				'format'     => 'hardcover',
				'chapter'    => array( 'mariana' ),
				'colouring'  => array( 'mariana' ),
				'price'      => 28.99, // ⭐ FD-581, §66.6 row "HC + colouring upsell".
				'cart_rule'  => 'pair',
				/*
				 * ⭐ HIS OWN LIMB: "1 PB and 1 Coloring book x3 (with the upsell
				 *    of HC)". The upsell is a FORMAT SWAP on an offer already
				 *    chosen — the same move the product format rail already
				 *    makes and the shopper already understands. ⛔ NOT a
				 *    pre-payment interstitial and NOT a post-purchase one-click
				 *    (spec §6.4: three mechanics, three QA surfaces, and
				 *    `FD-558` says simple).
				 * ⛔ PAPERBACK STAYS THE DEFAULT — `FD-439`, and
				 *    `bhp_bundle_default_format()` already returns 'paperback',
				 *    so this introduces no second default.
				 */
				'upsell_of'  => 'mariana_pb_colouring',
			),

			/* ─────────────────────────────────────────────────────────────
			 * ⛔⛔ GATED. RULED BY HIM, NOT BUILDABLE TODAY.
			 *
			 * ⭐ Recorded here rather than omitted so that his ruling is not
			 *    LOST between the canon and the code, and so the day the books
			 *    exist nobody has to re-derive a price from a register. ⛔ Both
			 *    gates hold independently: the components do not resolve, AND
			 *    `cart_rule` refuses to price them.
			 * ───────────────────────────────────────────────────────────── */

			'colouring_collection' => array(
				'label'     => 'The three coloring books',
				'kind'      => 'set',
				'format'    => null, // ⛔ No chapter component. Colouring only.
				'chapter'   => array(),
				'colouring' => array( 'mariana', 'everest', 'amazon' ),
				'price'     => 34.99, // ⭐ FD-581, §66.6 row "Colouring collection (3)".
				'cart_rule' => 'unimplemented',
			),

			'six_book_pb' => array(
				'label'     => 'All three adventures in paperback plus all three coloring books',
				'kind'      => 'set',
				'format'    => 'paperback',
				'chapter'   => array( 'mariana', 'everest', 'amazon' ),
				'colouring' => array( 'mariana', 'everest', 'amazon' ),
				'price'     => 63.99, // ⭐ FD-580, his own figure, ~17:2x−0600.
				'cart_rule' => 'unimplemented',
			),

			'six_book_hc' => array(
				'label'     => 'All three adventures in hardcover plus all three coloring books',
				'kind'      => 'set',
				'format'    => 'hardcover',
				'chapter'   => array( 'mariana', 'everest', 'amazon' ),
				'colouring' => array( 'mariana', 'everest', 'amazon' ),
				'price'     => 79.99, // ⭐ FD-581, §66.6 row "6-book HC collection".
				'cart_rule' => 'unimplemented',
			),
		)
	);
}

/**
 * Resolve one offer's components to live WooCommerce products.
 *
 * ⛔ RESOLUTION IS BY THE EXISTING CATALOGUES ONLY. Chapter components come
 *    from `bhp_bundle_catalog()`, colouring components from
 *    `bhp_colouring_product_ids()` (SKU-keyed, 1.8.61). This function reaches
 *    for no product on its own, so an offer can never name something outside
 *    the two approved catalogues.
 *
 * ⭐ IT FAILS CLOSED. Any component that does not resolve returns null for the
 *    WHOLE offer, so a partially-resolvable offer is indistinguishable from an
 *    absent one everywhere downstream. That is what makes the gate structural.
 *
 * @param string $key Offer key.
 * @return array|null List of component arrays, or null if the offer cannot be
 *                    assembled from live products right now.
 */
function bhp_offer_components( $key ) {
	$catalog = bhp_offer_catalog();
	if ( ! isset( $catalog[ $key ] ) ) {
		return null;
	}
	$offer = $catalog[ $key ];

	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	$components   = array();
	$book_catalog = function_exists( 'bhp_bundle_catalog' ) ? bhp_bundle_catalog() : array();
	$colouring    = function_exists( 'bhp_colouring_product_ids' ) ? bhp_colouring_product_ids() : array();
	$format       = $offer['format'];

	foreach ( (array) $offer['chapter'] as $slug ) {
		if ( ! $format || ! isset( $book_catalog[ $format ][ $slug ] ) ) {
			return null;
		}
		$info    = $book_catalog[ $format ][ $slug ];
		$buy_id  = $info['variation_id'] ? (int) $info['variation_id'] : (int) $info['product_id'];
		$product = wc_get_product( $buy_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			return null;
		}
		$components[] = array(
			'line'         => 'chapter',
			'adventure'    => $slug,
			'format'       => $format,
			'product_id'   => (int) $info['product_id'],
			'variation_id' => (int) $info['variation_id'],
			'buy_id'       => $buy_id,
			// ⛔ LIVE. Never a literal, never inherited from a draft.
			'price'        => (float) $product->get_price(),
		);
	}

	foreach ( (array) $offer['colouring'] as $slug ) {
		if ( empty( $colouring[ $slug ] ) ) {
			return null; // ⭐ The gate. This is where Everest and Amazon stop.
		}
		$buy_id  = (int) $colouring[ $slug ];
		$product = wc_get_product( $buy_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			return null;
		}
		$components[] = array(
			'line'         => 'colouring',
			'adventure'    => $slug,
			'format'       => 'colouring',
			'product_id'   => $buy_id,
			'variation_id' => 0,
			'buy_id'       => $buy_id,
			'price'        => (float) $product->get_price(),
		);
	}

	return empty( $components ) ? null : $components;
}

/**
 * Can a customer buy this offer, right now, on this environment?
 *
 * ⭐⭐ THIS IS THE FUNCTION EVERY CUSTOMER-FACING SURFACE MUST ASK BEFORE
 *     RENDERING AN OFFER ROW, CARD, PRICE OR BUTTON. Spec §3.2 / `R1.4`.
 *
 * @param string $key Offer key.
 * @return bool
 */
function bhp_offer_is_purchasable( $key ) {
	return null !== bhp_offer_components( $key );
}

/**
 * Every offer that is purchasable today, in catalogue order.
 *
 * @return string[] Offer keys.
 */
function bhp_offer_purchasable_keys() {
	$keys = array();
	foreach ( array_keys( bhp_offer_catalog() ) as $key ) {
		if ( bhp_offer_is_purchasable( $key ) ) {
			$keys[] = $key;
		}
	}
	return $keys;
}

/**
 * The offer's own price — ⭐ Andrew's literal, unmodified.
 *
 * @param string $key Offer key.
 * @return float|null
 */
function bhp_offer_price( $key ) {
	$catalog = bhp_offer_catalog();
	return isset( $catalog[ $key ]['price'] ) ? (float) $catalog[ $key ]['price'] : null;
}

/**
 * What the components cost bought separately, ⭐ READ LIVE.
 *
 * @param string $key Offer key.
 * @return float|null
 */
function bhp_offer_component_total( $key ) {
	$components = bhp_offer_components( $key );
	if ( null === $components ) {
		return null;
	}
	$total = 0.0;
	foreach ( $components as $component ) {
		$total += (float) $component['price'];
	}
	return $total;
}

/**
 * ⭐⭐ THE SAVING — A DERIVED CLAIM, RECOMPUTED AT EVERY RENDER.
 *
 * ⛔ `evidence-verification` §5: a claim built from two sourced facts is a NEW
 *    claim. "Save $1.99" is not Andrew's ruling and is not a constant. It is
 *    the difference between what WooCommerce charges for the components TODAY
 *    and the price he set, and it must be printed from this function or not
 *    printed at all.
 *
 * @param string $key Offer key.
 * @return float|null Positive saving, or null when the offer cannot be priced.
 */
function bhp_offer_saving( $key ) {
	$total = bhp_offer_component_total( $key );
	$price = bhp_offer_price( $key );
	if ( null === $total || null === $price ) {
		return null;
	}
	$saving = $total - $price;
	return $saving > 0 ? $saving : null;
}

/**
 * The cart fee this offer contributes, per matched instance.
 *
 * @param string $key Offer key.
 * @return float|null
 */
function bhp_offer_discount_amount( $key ) {
	return bhp_offer_saving( $key );
}

/**
 * Does the chapter-tier ladder already discount this offer's format in this
 * cart? See the header's flagged judgement.
 *
 * @param string $key  Offer key.
 * @param array  $eval `bhp_bundle_evaluate_cart()` output.
 * @return bool TRUE when the pair offer must stand down.
 */
function bhp_offer_tier_takes_precedence( $key, array $eval ) {
	$catalog = bhp_offer_catalog();
	$format  = isset( $catalog[ $key ]['format'] ) ? $catalog[ $key ]['format'] : null;

	/*
	 * ⭐ COMPUTED, THEN DELIBERATELY NOT ACTED ON. `$tier_already_discounts`
	 *    is the exact condition that used to suppress the offer, kept as a
	 *    named value so that a reader of this function — and anyone who
	 *    re-suppresses on Andrew's word — can see WHICH carts the ruling
	 *    changed, rather than having to re-derive it from a deleted line.
	 */
	$tier_already_discounts = (bool) (
		$format
		&& isset( $eval[ $format . '_tier' ] )
		&& (int) $eval[ $format . '_tier' ] >= 2
	);

	/*
	 * ⭐ 1.8.65 — STACKING IS ON. The default is FALSE for every cart.
	 */
	$suppressed = false;

	/**
	 * Whether the chapter-tier ladder outranks this offer for this cart.
	 *
	 * ⭐ The one-line reversal named in this file's header, now pointing the
	 *    other way. Returning TRUE restores suppression, which remains a
	 *    commercial decision and Andrew's to change back.
	 *
	 * ⭐ `$tier_already_discounts` is passed as a fourth argument so a filter
	 *    can restore the old behaviour without re-reading `$eval`.
	 *
	 * @param bool   $suppressed             TRUE to suppress the offer.
	 * @param string $key                    Offer key.
	 * @param array  $eval                   Cart evaluation.
	 * @param bool   $tier_already_discounts TRUE when the chapter-tier ladder
	 *                                       already discounts this format.
	 */
	return (bool) apply_filters( 'bhp_offer_tier_precedence', $suppressed, $key, $eval, $tier_already_discounts );
}

/**
 * How many complete instances of a PAIR offer this cart holds, given a pool of
 * still-unclaimed quantities.
 *
 * ⛔ THE POOL IS WHY THIS TAKES A REFERENCE. A cart holding one colouring book,
 *    one paperback and one hardcover satisfies BOTH Mariana pair offers on
 *    paper — but there is only ONE colouring book, and it can only be in one
 *    pair. Consuming from a shared pool is what stops the same physical book
 *    being sold into two discounts.
 *
 * @param string $key   Offer key.
 * @param array  $pool  buy_id => remaining quantity. Passed by reference.
 * @return int Number of complete instances claimed.
 */
function bhp_offer_claim_instances( $key, array &$pool ) {
	$components = bhp_offer_components( $key );
	if ( null === $components ) {
		return 0;
	}

	$instances = PHP_INT_MAX;
	$needed    = array();
	foreach ( $components as $component ) {
		$id             = (int) $component['buy_id'];
		$needed[ $id ]  = isset( $needed[ $id ] ) ? $needed[ $id ] + 1 : 1;
	}
	foreach ( $needed as $id => $per_instance ) {
		$available = isset( $pool[ $id ] ) ? (int) $pool[ $id ] : 0;
		$instances = min( $instances, intdiv( $available, $per_instance ) );
	}
	if ( PHP_INT_MAX === $instances || $instances < 1 ) {
		return 0;
	}

	foreach ( $needed as $id => $per_instance ) {
		$pool[ $id ] -= $instances * $per_instance;
	}
	return $instances;
}

/**
 * ⭐⭐⭐ THE CART SIDE PANEL'S COLOURING OFFERS — plugin 1.8.65.
 * ============================================================================
 *
 * ⭐ WHY THIS EXISTS: the panel's cross-sell rail could offer "add the next
 *    chapter book" and could NOT offer the colouring book. Andrew named both
 *    by hand in carrier item 186 — "add the coloring book, add the next
 *    chapter book etc." — and only one of them was wired.
 *
 * ⛔ IT INVENTS NO OFFER AND NO SAVING. Every row below is an offer already in
 *    `bhp_offer_catalog()`, already gated by `bhp_offer_is_purchasable()`, and
 *    its `saving` is `bhp_offer_saving()` — the SAME derived figure the offer
 *    engine turns into the real cart fee. ⭐ The number on the button and the
 *    number on the invoice come from one function and cannot drift.
 *
 * ⛔ IT IS EMPTY UNTIL A COLOURING PRODUCT RECORD EXISTS, structurally, by way
 *    of `bhp_offer_is_purchasable()`. On production today that is the state
 *    (`FD-598`), so the panel renders no colouring offer there and this
 *    function returns `array()`.
 *
 * ⛔ NO CUSTOMER COPY IS WRITTEN HERE. The two strings come from the theme's
 *    `bhp_colouring_draft_copy()`, where every colouring string Andrew has to
 *    approve already lives in one place. If the theme is not providing them,
 *    the label falls back to the PRODUCT'S OWN NAME — a record, never an
 *    invention — rather than this file coining a phrase.
 *
 * @return array<int,array> Rows the drawer script can match against a cart.
 */
function bhp_offer_drawer_payload() {
	$rows = array();

	foreach ( bhp_offer_catalog() as $key => $offer ) {
		if ( 'pair' !== $offer['cart_rule'] ) {
			continue; // ⛔ SPEC-STUB offers are never surfaced.
		}
		$components = bhp_offer_components( $key );
		if ( null === $components ) {
			continue; // ⛔ THE GATE. No product record, no row.
		}
		$saving = bhp_offer_saving( $key );
		if ( null === $saving ) {
			continue; // ⛔ No honest saving, no offer.
		}

		$chapter_ids  = array();
		$colouring    = null;
		$adventure    = '';
		foreach ( $components as $component ) {
			if ( 'colouring' === $component['line'] ) {
				$colouring = $component;
				$adventure = $component['adventure'];
			} else {
				$chapter_ids[] = (int) $component['buy_id'];
			}
		}
		if ( null === $colouring || empty( $chapter_ids ) ) {
			continue;
		}

		/*
		 * ⭐ THE ADVENTURE'S DISPLAY NAME, read from the SAME catalogue the
		 *    adventure cross-sell reads its labels from, so the two rows in
		 *    the rail name the same book the same way.
		 */
		$book_catalog = function_exists( 'bhp_bundle_catalog' ) ? bhp_bundle_catalog() : array();
		$format       = $offer['format'];
		$book_label   = isset( $book_catalog[ $format ][ $adventure ]['label'] )
			? $book_catalog[ $format ][ $adventure ]['label']
			: '';

		$product = wc_get_product( (int) $colouring['buy_id'] );
		$label   = $product ? $product->get_name() : '';
		if ( $book_label && function_exists( 'bhp_colouring_draft_copy' ) ) {
			$drafted = bhp_colouring_draft_copy( 'panel_label', array( $book_label ) );
			if ( '' !== $drafted ) {
				$label = $drafted;
			}
		}
		$cta = function_exists( 'bhp_colouring_draft_copy' ) ? bhp_colouring_draft_copy( 'panel_cta' ) : '';

		$rows[] = array(
			'key'          => $key,
			'format'       => $format,
			'title_key'    => 'colouring_' . $adventure,
			// ⭐ EVERY chapter id must be in the cart for this row to apply.
			'chapter_ids'  => $chapter_ids,
			'colouring_id' => (int) $colouring['buy_id'],
			'product_id'   => (int) $colouring['product_id'],
			'variation_id' => (int) $colouring['variation_id'],
			'label'        => $label,
			'cta'          => $cta,
			// ⭐ DERIVED, live, this request. Never stored, never a literal.
			'saving'       => round( (float) $saving, 2 ),
		);
	}

	return $rows;
}

/**
 * ⭐⭐ THE CART SIDE OF THE OFFER ENGINE.
 *
 * Priority 21 — deliberately AFTER `bhp_bundle_apply_discount_fees()` at 20,
 * so the chapter-tier evaluation this function consults has already run on the
 * same cart.
 *
 * ⛔ IT ADDS A FEE. It does not change a product price, and no product record
 *    is touched — exactly as the chapter-tier discount has worked since Phase
 *    4. `taxable = false` for the same reason stated there: the fee reduces
 *    the pre-tax subtotal of items that are already taxable, so WooCommerce
 *    recalculates tax on the reduced total by itself.
 *
 * @param WC_Cart $cart Cart being calculated.
 */
add_action( 'woocommerce_cart_calculate_fees', 'bhp_offer_apply_fees', 21 );
function bhp_offer_apply_fees( $cart ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! is_object( $cart ) ) {
		return;
	}

	/*
	 * ⛔ COUPON-STACKING GUARD, inherited from `bhp_bundle_apply_discount_fees()`
	 *    rather than reinvented with a different rule. Any coupon at all
	 *    suppresses an offer fee. ⭐ STRICTER THAN THE CHAPTER-TIER GUARD ON
	 *    PURPOSE: that one carves out audience coupons on a qualifying
	 *    COLLECTION cart, a policy written against the collection offer and
	 *    tested against it. Extending a carve-out to an offer nobody has
	 *    priced against a coupon would be an inference, and this file does not
	 *    make one.
	 */
	if ( ! empty( $cart->get_applied_coupons() ) ) {
		return;
	}

	$eval = function_exists( 'bhp_bundle_evaluate_cart' ) ? bhp_bundle_evaluate_cart( $cart ) : array();

	/*
	 * ⛔ THE SAME `has_unrelated` FAIL-SAFE 1.8.61 GAVE THE CHAPTER TIERS. If
	 *    the cart holds something neither catalogue recognises, this engine
	 *    does not understand the cart and prices nothing — no discount AND no
	 *    promise, the only combination that cannot lie to a customer.
	 */
	if ( ! empty( $eval['has_unrelated'] ) ) {
		return;
	}

	// The still-unclaimed quantity of every line in the cart, by buy id.
	$pool = array();
	foreach ( $cart->get_cart() as $cart_item ) {
		$id = (int) $cart_item['variation_id'] ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];
		$pool[ $id ] = ( isset( $pool[ $id ] ) ? $pool[ $id ] : 0 ) + (int) $cart_item['quantity'];
	}

	/*
	 * ⭐ BEST OFFER FIRST, THEN ALPHABETICAL. Two reasons, both load-bearing:
	 *    the shopper gets the largest saving the cart can earn, and the
	 *    allocation is DETERMINISTIC — the same cart always produces the same
	 *    total, which is what makes the cart matrix in QA meaningful at all.
	 */
	$candidates = array();
	foreach ( bhp_offer_catalog() as $key => $offer ) {
		if ( 'pair' !== $offer['cart_rule'] ) {
			continue; // ⛔ SPEC-STUB. `unimplemented` is never priced. See header.
		}
		if ( ! bhp_offer_is_purchasable( $key ) ) {
			continue;
		}
		if ( bhp_offer_tier_takes_precedence( $key, $eval ) ) {
			continue;
		}
		$saving = bhp_offer_discount_amount( $key );
		if ( null === $saving ) {
			continue;
		}
		$candidates[ $key ] = $saving;
	}
	if ( empty( $candidates ) ) {
		return;
	}
	arsort( $candidates );

	foreach ( $candidates as $key => $saving ) {
		$instances = bhp_offer_claim_instances( $key, $pool );
		if ( $instances < 1 ) {
			continue;
		}
		$catalog = bhp_offer_catalog();
		$cart->add_fee(
			/*
			 * ⛔ ONE LABEL FOR THE WHOLE LINE, matching "Bundle Savings
			 *    (Paperback)" in shape so the cart reads as one system. The
			 *    offer's internal label is NOT used — it is identification,
			 *    not copy.
			 */
			sprintf( 'Bundle Savings (%s)', ucfirst( $catalog[ $key ]['format'] ) ),
			-1 * $saving * $instances,
			false
		);
	}
}

/**
 * Add every missing component of an offer to the cart.
 *
 * ⭐ "SMART", exactly like `bhp_bundle_add_missing_titles_to_cart()`: a
 *    component already in the cart is not added again, so a repeat click
 *    cannot double-add. The server path is a redirect-after-POST, so F5
 *    re-GETs the destination.
 *
 * ⛔ EACH COMPONENT IS ADDED AS ITS OWN REAL, INDIVIDUALLY-MAPPED LINE ITEM.
 *    ⭐ THIS IS `FD-579` IN CODE: "on the backend its all put together for
 *    bookvault". No bundle product is created, added or substituted.
 *
 * @param string $key Offer key.
 * @return int Number of components added.
 */
function bhp_offer_add_to_cart( $key ) {
	$components = bhp_offer_components( $key );
	if ( null === $components ) {
		wc_add_notice( 'That offer is not available right now.', 'error' );
		return 0;
	}

	$catalog = bhp_offer_catalog();
	$format  = isset( $catalog[ $key ]['format'] ) ? $catalog[ $key ]['format'] : null;

	/*
	 * ⛔ THE SCHOOL-VISIT PAPERBACK-ONLY REFUSAL, applied by name at the
	 *    function that takes the format — belt and braces on top of seam 5,
	 *    for the same reason `bhp_bundle_add_titles_to_cart()` does it: one
	 *    clear sentence to the parent instead of per-component errors.
	 *    ⛔ CONTROL PATH: false for every ordinary shopper.
	 */
	if ( 'hardcover' === $format
		&& function_exists( 'bhp_school_visit_paperback_only' )
		&& bhp_school_visit_paperback_only() ) {
		wc_add_notice( bhp_school_visit_paperback_only_message(), 'error' );
		return 0;
	}

	$in_cart = array();
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$id             = (int) $cart_item['variation_id'] ? (int) $cart_item['variation_id'] : (int) $cart_item['product_id'];
		$in_cart[ $id ] = true;
	}

	$added = 0;
	foreach ( $components as $component ) {
		if ( isset( $in_cart[ (int) $component['buy_id'] ] ) ) {
			continue;
		}
		$ok = $component['variation_id']
			? WC()->cart->add_to_cart( $component['product_id'], 1, $component['variation_id'] )
			: WC()->cart->add_to_cart( $component['product_id'], 1 );
		if ( $ok ) {
			++$added;
			$in_cart[ (int) $component['buy_id'] ] = true;
		}
	}

	if ( 0 === $added && empty( $in_cart ) ) {
		wc_add_notice( 'That offer could not be added to your cart. Please try again.', 'error' );
	}

	return $added;
}
