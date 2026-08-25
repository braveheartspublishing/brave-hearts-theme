<?php
/**
 * Brave Hearts Bundle Pricing — A VISIT-FLAGGED SESSION IS CHAPTER PAPERBACKS
 * ONLY.
 *
 * ⚠️ THE FILENAME AND THE FUNCTION NAMES STILL SAY "paperback_only" AND
 *    "hardcover", AND THAT IS A DELIBERATE CHOICE, NOT AN OVERSIGHT. 1.8.69
 *    widened the rule from "no hardcovers" to "chapter paperbacks only" (see
 *    the 1.8.69 block further down). The names were NOT renamed with it,
 *    because `woocommerce_add_to_cart_validation` and the four other seams
 *    are registered BY CALLBACK NAME and are asserted by name in
 *    `tests/test-visit-paperback-only.php` and `test-visit-flag-expiry.php`.
 *    A rename would have been a wide, purely cosmetic diff across three
 *    suites on a queue-jumped founder-found fix. ⭐ READ
 *    `bhp_school_visit_is_refused_item()` FOR WHAT IS ACTUALLY REFUSED —
 *    never infer it from a function's name in this file.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS EXISTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-18, verbatim (⛔ RELAYED through the Chief of
 * Staff in the `CYCLE164-LD-PAPERBACK-DEFAULT` brief; NOT witnessed
 * first-hand by the agent that wrote this file):
 *
 *   "yes, lets make it the paperbacks - also for the orders on the
 *    pre-signed books for the read alouds- based on my inventory I can
 *    only do paperbacks"
 *
 * ⭐ THIS IS AN INVENTORY FACT, NOT A PRESENTATION PREFERENCE. Andrew
 *    carries paperbacks to a school visit and signs them by hand. He cannot
 *    supply a hardcover for a signed pre-order. A flagged parent who buys a
 *    HARDCOVER has bought something that cannot be hand-delivered, and the
 *    failure surfaces at the read aloud, in front of a child.
 *
 * ⭐ THE GAP WAS LIVE. `commerce-cx` walked the flagged path on PRODUCTION on
 *    2026-08-18 and observed the product page rendering a selectable
 *    `HARDCOVER $17.99` card to a visit-flagged session.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ UI HIDING IS NOT THE FIX. THIS FILE IS THE FIX.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The templates hide the hardcover controls, and they should — but hiding a
 * control does not stop:
 *
 *   · a parent who put a hardcover in the cart in an ORDINARY session and
 *     only THEN clicked the school link (the cart survives; the flag is set
 *     afterwards);
 *   · a stale `?add-to-cart=14` link, a bookmark, a shared URL, a browser
 *     back button, or an autocomplete;
 *   · any Store API client, which renders no UI at all.
 *
 * So the rule is enforced on the SERVER, at four seams. This store runs full
 * WooCommerce Blocks: the cart and checkout a customer actually uses are
 * React over the Store API, and a classic-only guard would enforce nothing
 * where it matters.
 *
 *   1. `woocommerce_add_to_cart_validation` — the classic add, which is what
 *      the PDP CTA's `?add-to-cart=` URL and `bhp_bundle_add_titles_to_cart()`
 *      both run through.
 *   2. `woocommerce_store_api_validate_add_to_cart` — the Store API add, which
 *      is what `bundle-drawer.js` uses when it intercepts a bundle form.
 *      `CartController::validateAddToCart()` fires it and a thrown
 *      `RouteException` becomes a 400 with the message
 *      (`src/StoreApi/Utilities/CartController.php:381`, read on the running
 *      store, WooCommerce 10.9.1).
 *   3. `woocommerce_store_api_cart_errors` — ⭐ THE ONE THAT CLOSES THE STALE
 *      CART. `CartController::validate_cart()` fires it and throws a 409
 *      `InvalidCartException` if any error was added (same file, line 496),
 *      and the Checkout route calls `validate_cart()` BEFORE it will process
 *      an order. This is a hard server-side stop on the real checkout, not a
 *      UI hint.
 *   4. `woocommerce_check_cart_items` — the classic cart and checkout pages,
 *      so the sentence is visible if a shortcode cart is ever used.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE CONTROL PATH IS UNTOUCHED, AND THAT MATTERS MORE THAN THIS FEATURE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Every function below returns at the first gate for a visitor with no visit
 * flag. An ordinary shopper's add-to-cart, cart, checkout and hardcover
 * purchase are byte-identical to 1.8.56. A regression there hits everyone;
 * this bug hits the parents of three schools.
 *
 * ✅ IT FAILS OPEN, EVERYWHERE. No WooCommerce, no predicate, an exception
 *    from the resolver, an empty hardcover id set, a filter that returns
 *    nonsense: every one of those results in NOBODY being blocked. The cost
 *    of failing closed is a paying customer who cannot buy a book.
 *
 * ⛔ WHAT THIS FILE DOES NOT DO
 *   - It does not empty, alter, re-price or silently swap anybody's cart. It
 *     adds an error and stops. Nothing is removed on a customer's behalf,
 *     because a cart that changes itself is worse than one that explains why
 *     it cannot proceed.
 *   - It changes NO price, discount, shipping tier, coupon, tax, stock status,
 *     product record or payment setting, and writes no option or order.
 *     HARDCOVER REMAINS IN STOCK AND FULLY PURCHASABLE for every ordinary
 *     shopper on every environment.
 *   - It does not touch the ORDER once placed. An order that already exists is
 *     history and is not re-litigated here.
 *
 * ⛔ VOICE: standing rule §9.1. The customer-facing sentence is I/me, never
 *    "we", and carries no em dash.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True when THIS request must be restricted to paperback.
 *
 * ⭐ ONE PREDICATE, DELEGATING TO THE EXISTING ONE. It is deliberately a thin
 *    wrapper over `bhp_school_visit_use_delivery_framing()` rather than a
 *    second resolver: the checkout framing, the pickup method, the payment
 *    gate and this restriction must never disagree about whether a visitor is
 *    on a visit. A second source of truth is how the collection page and the
 *    sticky bar drifted apart before.
 *
 * ⭐ FILTERABLE so Andrew can lift the restriction for a visit he can supply
 *    hardcovers for, without a code change. Returning false anywhere restores
 *    1.8.56 behaviour exactly.
 *
 * @return bool
 */
function bhp_school_visit_paperback_only() {
	if ( ! function_exists( 'bhp_school_visit_use_delivery_framing' ) ) {
		return false; // FAIL OPEN: no predicate -> nobody is restricted.
	}

	try {
		$flagged = (bool) bhp_school_visit_use_delivery_framing();
	} catch ( Throwable $e ) {
		return false; // FAIL OPEN: a resolver that throws must never cost a sale.
	}

	if ( ! $flagged ) {
		return false; // ⭐ ZERO CHANGE for every ordinary shopper.
	}

	/**
	 * Whether a visit-flagged session is restricted to paperback.
	 *
	 * @param bool  $restricted True to restrict. Only ever reached when the
	 *                          session actually carries a live visit flag.
	 * @param array|null $record The live visit record, or null.
	 */
	return (bool) apply_filters(
		'bhp_school_visit_paperback_only',
		true,
		function_exists( 'bhp_school_visit_request_record' ) ? bhp_school_visit_request_record() : null
	);
}

/**
 * The hardcover product ids, READ FROM THE CATALOG rather than restated.
 *
 * ⛔ NOT HARDCODED. `bhp_bundle_catalog()` is the single owner of which
 *    WooCommerce record is which edition, and a second list of three ids here
 *    would be wrong the first time a product id changes.
 *
 * @return int[] Product ids. Empty array if the catalog is unavailable.
 */
function bhp_school_visit_hardcover_product_ids() {
	static $ids = null;

	if ( null !== $ids ) {
		return $ids;
	}
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		$ids = array(); // FAIL OPEN: no catalog -> nothing is ever recognised.
		return $ids;
	}

	$catalog = bhp_bundle_catalog();
	$out     = array();

	if ( isset( $catalog['hardcover'] ) && is_array( $catalog['hardcover'] ) ) {
		foreach ( $catalog['hardcover'] as $edition ) {
			if ( ! empty( $edition['product_id'] ) ) {
				$out[] = (int) $edition['product_id'];
			}
			if ( ! empty( $edition['variation_id'] ) ) {
				$out[] = (int) $edition['variation_id'];
			}
		}
	}

	$ids = array_values( array_unique( $out ) );
	return $ids;
}

/**
 * Is this product/variation pair one of the three hardcover editions?
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return bool
 */
function bhp_school_visit_is_hardcover( $product_id, $variation_id = 0 ) {
	$ids = bhp_school_visit_hardcover_product_ids();
	if ( empty( $ids ) ) {
		return false;
	}

	$product_id   = (int) $product_id;
	$variation_id = (int) $variation_id;

	if ( $product_id && in_array( $product_id, $ids, true ) ) {
		return true;
	}
	if ( $variation_id && in_array( $variation_id, $ids, true ) ) {
		return true;
	}

	return false;
}

/**
 * Does this cart hold a hardcover?
 *
 * @param WC_Cart|object|null $cart Cart, or any object exposing get_cart().
 * @return bool
 */
function bhp_school_visit_cart_has_hardcover( $cart ) {
	if ( ! $cart || ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return false;
	}

	$items = $cart->get_cart();
	if ( empty( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		$product_id   = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		$variation_id = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;

		if ( bhp_school_visit_is_hardcover( $product_id, $variation_id ) ) {
			return true;
		}
	}

	return false;
}

/*
 * =========================================================================
 * ⭐⭐ 1.8.69 (`CYCLE165-LD-VISIT-COLOURING-GATE`, carrier item 217) — THE
 *     COLOURING BOOK JOINS THE HARDCOVER ON THE REFUSED LIST.
 * =========================================================================
 *
 * Andrew Signore, 2026-08-21, ⛔ RELAYED through the Chief of Staff in the
 * `CYCLE165-LD-VISIT-COLOURING-GATE` dispatch; NOT witnessed first-hand by
 * the agent that wrote this code. The rule as carried: a visit-flagged cart
 * must REFUSE the colouring book, because a signed school order is CHAPTER
 * PAPERBACKS ONLY.
 *
 * ⭐ SAME INVENTORY LOGIC AS THE HARDCOVER, ONE STEP FURTHER. He carries
 *    chapter paperbacks to a school and signs them by hand. The colouring
 *    book is a paperback, so the ORIGINAL format-shaped predicate let it
 *    straight through: `bhp_school_visit_is_hardcover()` returns false for
 *    it, and every one of the five seams therefore accepted it. A flagged
 *    parent could buy a colouring book that cannot be hand-delivered, and
 *    the failure surfaces at the read aloud, in front of a child. That is
 *    the identical failure mode the hardcover gate exists to prevent.
 *
 * ⛔⛔ NOT A HARDCODED ID, AND THE ID IN THE DISPATCH IS NOT PORTABLE. The
 *     brief names "product 618". ⭐ VERIFIED READ-ONLY OVER SSH THIS BUILD,
 *     2026-08-21: 618 is the PRODUCTION id; the same book is 4065 ON
 *     STAGING. A literal 618 would have gated production and enforced
 *     NOTHING on the environment this build is QA'd on, and the suite would
 *     have passed vacuously. Resolution goes through
 *     `bhp_bundle_identify_colouring_item()` -> `bhp_colouring_product_ids()`
 *     -> `bhp_colouring_catalog()`, which is SKU-keyed on the ISBN
 *     9798996810840. Every future colouring title is therefore refused the
 *     day its record exists, with no edit here.
 *
 * ✅ IT FAILS OPEN exactly like the hardcover half: no registry, no resolver,
 *    an empty id map, a throwing filter — every one of those results in
 *    NOBODY being blocked. On an environment with no colouring record the
 *    map is empty and this file is byte-for-byte inert.
 */

/**
 * Is this product/variation pair a colouring-line book?
 *
 * ⛔ DELEGATES. It does not keep a list, and it does not re-implement the
 *    parent/variation matching that `bhp_bundle_identify_colouring_item()`
 *    already does correctly (that function matches BOTH ids, so a future
 *    variable colouring product cannot slip past by being added as a
 *    variation).
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return bool
 */
function bhp_school_visit_is_colouring( $product_id, $variation_id = 0 ) {
	if ( ! function_exists( 'bhp_bundle_identify_colouring_item' ) ) {
		return false; // FAIL OPEN: no registry -> nothing is ever recognised.
	}

	try {
		return null !== bhp_bundle_identify_colouring_item( (int) $product_id, (int) $variation_id );
	} catch ( Throwable $e ) {
		return false; // FAIL OPEN: a resolver that throws must never cost a sale.
	}
}

/**
 * ⭐⭐ THE ONE PRODUCT-LEVEL PREDICATE THE FIVE SEAMS ASK.
 *
 * ⛔ THIS IS WHAT REPLACED `bhp_school_visit_is_hardcover()` AT THE SEAMS,
 *    AND THE REASON IS WORTH THE LINE: a signed school order is defined by
 *    what Andrew can CARRY AND SIGN, not by a binding. "Hardcover" was only
 *    ever a proxy for that, and the colouring book is the case that showed
 *    the proxy was wrong. A seam that asks "is this a hardcover" will let
 *    the next non-chapter paperback through too.
 *
 * ⛔ `bhp_school_visit_is_hardcover()` IS NOT DELETED AND NOT REWORDED. It
 *    is still the hardcover-only test, it is still public, and the existing
 *    suite still asserts it directly. Only the SEAMS moved.
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return bool True when a visit-flagged session must refuse this item.
 */
function bhp_school_visit_is_refused_item( $product_id, $variation_id = 0 ) {
	return bhp_school_visit_is_hardcover( $product_id, $variation_id )
		|| bhp_school_visit_is_colouring( $product_id, $variation_id )
		|| bhp_school_visit_is_sold_out_title( $product_id, $variation_id );
}

/*
 * =========================================================================
 * ⭐⭐ 1.8.71 (`CYCLE166-LD-VISIT-STOCK-GATE`, carrier item 235) — A CHAPTER
 *     PAPERBACK WHOSE SHELF IS EMPTY JOINS THE REFUSED LIST, FOR VISITS ONLY.
 * =========================================================================
 *
 * Andrew Signore, 2026-08-24, ⛔ RELAYED through the Chief of Staff in the
 * `CYCLE166-LD-VISIT-STOCK-GATE` dispatch; NOT witnessed first-hand by the
 * agent that wrote this code:
 *
 *   "I think once we hit 1 chatper book left - we close off the option and
 *    say they have sold out"
 *
 * ⭐ THE THIRD LIMB IS A DIFFERENT KIND OF FACT FROM THE FIRST TWO, AND THE
 *    DIFFERENCE IS WORTH THE LINE. Hardcover and colouring are refused
 *    because of WHAT THEY ARE: no count changes that, and the refusal is
 *    permanent for as long as the visit programme runs. A chapter paperback
 *    is refused because of HOW MANY ARE LEFT: the same product is refused at
 *    10:02 and available at 09:58 if Andrew restocks in between. It is a
 *    TIME-VARYING refusal driven by live order data, so it is computed in
 *    `school-visit-shelf-stock.php` and only asked here.
 *
 * ⛔ WHICH IS ALSO WHY IT NEEDED THE MESSAGE SPLIT BELOW. The existing
 *    sentence tells a parent to "choose a chapter paperback". Printing that
 *    at somebody who was just refused a chapter paperback would describe the
 *    refusal as the remedy, which is the exact defect 1.8.69 recorded when
 *    the colouring book joined the list.
 *
 * ✅ IT FAILS OPEN. No shelf module, no baseline option, a resolver that
 *    throws: every one results in NOBODY being blocked. On production today
 *    the `bhp_visit_shelf_stock` option is unset, so this limb is inert.
 */

/**
 * Is this product/variation pair a chapter paperback whose shelf is closed?
 *
 * ⛔ DELEGATES. It keeps no count, no product id and no threshold of its own.
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return bool
 */
function bhp_school_visit_is_sold_out_title( $product_id, $variation_id = 0 ) {
	if ( ! function_exists( 'bhp_visit_shelf_is_closed_item' ) ) {
		return false; // FAIL OPEN: no shelf module -> nothing is ever closed.
	}

	try {
		return (bool) bhp_visit_shelf_is_closed_item( (int) $product_id, (int) $variation_id );
	} catch ( Throwable $e ) {
		return false; // FAIL OPEN: a resolver that throws must never cost a sale.
	}
}

/**
 * ⭐ THE RIGHT SENTENCE FOR THE ACTUAL REASON THIS ITEM WAS REFUSED.
 *
 * ⛔ SOLD-OUT IS TESTED FIRST, DELIBERATELY. The two reasons cannot overlap
 *    today (the shelf gate only knows chapter paperbacks; the format gate
 *    only knows hardcovers and colouring books), but if they ever did, the
 *    sold-out sentence is the more specific and more useful one: it names
 *    what actually happened instead of restating a rule the parent already
 *    complied with.
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return string The customer-facing sentence for this refusal.
 */
function bhp_school_visit_refusal_message( $product_id = 0, $variation_id = 0 ) {
	if ( bhp_school_visit_is_sold_out_title( $product_id, $variation_id )
		&& function_exists( 'bhp_visit_shelf_sold_out_message' ) ) {
		return (string) bhp_visit_shelf_sold_out_message();
	}

	return bhp_school_visit_paperback_only_message();
}

/**
 * The right sentence for whatever is wrong with THIS cart.
 *
 * ⛔ IT REPORTS THE FIRST REFUSED ITEM'S OWN REASON rather than a generic
 *    line, because a cart holding a sold-out Everest and nothing else must
 *    not be told that signed copies are chapter paperbacks only.
 *
 * @param WC_Cart|object|null $cart Cart, or any object exposing get_cart().
 * @return string
 */
function bhp_school_visit_cart_refusal_message( $cart ) {
	if ( $cart && is_object( $cart ) && method_exists( $cart, 'get_cart' ) ) {
		foreach ( (array) $cart->get_cart() as $item ) {
			$product_id   = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
			$variation_id = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;

			if ( bhp_school_visit_is_refused_item( $product_id, $variation_id ) ) {
				return bhp_school_visit_refusal_message( $product_id, $variation_id );
			}
		}
	}

	return bhp_school_visit_paperback_only_message();
}

/**
 * Does this cart hold anything a visit-flagged session must refuse?
 *
 * @param WC_Cart|object|null $cart Cart, or any object exposing get_cart().
 * @return bool
 */
function bhp_school_visit_cart_has_refused_item( $cart ) {
	if ( ! $cart || ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return false;
	}

	$items = $cart->get_cart();
	if ( empty( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		$product_id   = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		$variation_id = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;

		if ( bhp_school_visit_is_refused_item( $product_id, $variation_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * ⭐ THE SENTENCE. ONE AUTHOR, FIVE SEAMS.
 *
 * ⛔ §9.1 VOICE RULE, adopted by Andrew Signore 2026-08-18: no "we", "us" or
 *    "our" standing for the company in customer-facing words. This is I/me.
 * ⛔ NO EM DASH. Sitewide standing constraint.
 * ⛔ NO OUTCOME CLAIM and no apology-shaped padding. It says what is true
 *    (chapter paperbacks only, and why) and what to do next (choose one, or
 *    order the other book separately without the school link).
 *
 * Five seams print this rather than five sentences, because five copies of a
 * customer-facing phrase is how two of them end up disagreeing (the same
 * reason `bhp_school_pickup_totals_label()` exists).
 *
 * ═════════════════════════════════════════════════════════════════════════
 * ⚠️ 1.8.69 — THIS STRING MOVED. FLAGGED FOR ANDREW'S EYE, NOT SELF-APPROVED.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SUPERSEDED STRING, PRESERVED VERBATIM so the movement is visible and
 *    is not re-derived. It shipped 1.8.65 to 1.8.68:
 *
 *      "I can only bring paperbacks to the school visit, so signed copies
 *       are paperback only. Please choose the paperback. If you would like a
 *       hardcover as well, you can order it separately from the shop."
 *
 * ⭐ WHY IT COULD NOT STAY. It became FALSE the moment the gate widened. It
 *    tells a parent holding a refused colouring book that the fix is to
 *    "choose the paperback" — and the colouring book IS a paperback. The
 *    sentence would have described the refusal it was printed by as
 *    something the customer had already done.
 *
 * ⛔ THE EDIT IS MINIMAL AND ADDS NO CLAIM. Three moves, no fourth:
 *      1. "paperbacks" -> "chapter paperbacks" (the thing he actually carries)
 *      2. "the paperback" -> "a chapter paperback" (there are three of them)
 *      3. "a hardcover" -> "a hardcover or a coloring book" (the refused set)
 *    No apology, no urgency, no outcome claim, no new fact about the visit.
 *
 * ⛔ "coloring", AMERICAN SPELLING, DELIBERATELY. The CODE spells it
 *    "colouring" throughout and the CUSTOMER-FACING copy spells it
 *    "coloring" — that split already exists and is correct. The product
 *    record's own title reads "Coloring Adventures with Charlotte and Henry",
 *    and every customer-facing string in `bhp_colouring_draft_copy()` reads
 *    "coloring". A British spelling here would be the one place on the
 *    storefront that disagreed with the book's own cover.
 *
 * @return string
 */
function bhp_school_visit_paperback_only_message() {
	/**
	 * The message shown when a refused item meets a visit-flagged session.
	 *
	 * @param string $message Customer-facing sentence.
	 */
	return (string) apply_filters(
		'bhp_school_visit_paperback_only_message',
		__( 'I can only bring the chapter paperbacks to the school visit, so signed copies are chapter paperbacks only. Please choose a chapter paperback. If you would like a hardcover or a coloring book as well, you can order it separately from the shop.', 'brave-hearts' )
	);
}

/*
 * =========================================================================
 * SEAM 1 — THE CLASSIC ADD. `?add-to-cart=14`, and every bundle form that
 *          runs through `bhp_bundle_add_titles_to_cart()`.
 * =========================================================================
 */

/**
 * Refuse a hardcover add on a visit-flagged session.
 *
 * @param bool $passed       Whether the add may proceed.
 * @param int  $product_id   Product id.
 * @param int  $quantity     Quantity (unused; a hardcover is refused at any).
 * @param int  $variation_id Variation id, or 0.
 * @return bool
 */
function bhp_school_visit_block_hardcover_add( $passed, $product_id = 0, $quantity = 1, $variation_id = 0 ) {
	if ( true !== $passed ) {
		return $passed; // Someone else already refused. Do not overwrite their reason.
	}
	if ( ! bhp_school_visit_is_refused_item( $product_id, $variation_id ) ) {
		return $passed; // ⭐ ZERO CHANGE for a chapter paperback, the add-on, or anything else.
	}
	if ( ! bhp_school_visit_paperback_only() ) {
		return $passed; // ⭐ ZERO CHANGE for every ordinary shopper.
	}

	if ( function_exists( 'wc_add_notice' ) ) {
		// 1.8.71: the sentence now matches the REASON, not just the rule.
		wc_add_notice( bhp_school_visit_refusal_message( $product_id, $variation_id ), 'error' );
	}

	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'bhp_school_visit_block_hardcover_add', 20, 4 );

/*
 * =========================================================================
 * SEAM 2 — THE STORE API ADD. `bundle-drawer.js` intercepts every
 *          `.bhp-bundle-form` and adds over the Store API, so this is the
 *          seam the JS purchase path actually uses.
 * =========================================================================
 */

/**
 * Refuse a hardcover Store API add on a visit-flagged session.
 *
 * `CartController::validateAddToCart()` catches nothing here: a
 * `RouteException` propagates and becomes a 400 carrying this message.
 *
 * @param WC_Product|object|null $product The product being added.
 * @return void
 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When refused.
 */
function bhp_school_visit_block_hardcover_store_api_add( $product = null ) {
	if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
		return; // FAIL OPEN.
	}

	$product_id   = (int) $product->get_id();
	$variation_id = 0;
	if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
		$variation_id = $product_id;
		$product_id   = method_exists( $product, 'get_parent_id' ) ? (int) $product->get_parent_id() : 0;
	}

	if ( ! bhp_school_visit_is_refused_item( $product_id, $variation_id ) ) {
		return;
	}
	if ( ! bhp_school_visit_paperback_only() ) {
		return;
	}
	if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
		return; // FAIL OPEN: seams 3 and 4 still stop the checkout.
	}

	throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
		'bhp_school_visit_paperback_only',
		// 1.8.71: reason-aware. The error CODE is deliberately unchanged --
		// `bundle-drawer.js` and the Store API contract key on it, and a new
		// code would be a silent breaking change for one extra sentence.
		bhp_school_visit_refusal_message( $product_id, $variation_id ),
		400
	);
}
add_action( 'woocommerce_store_api_validate_add_to_cart', 'bhp_school_visit_block_hardcover_store_api_add', 20, 1 );

/*
 * =========================================================================
 * ⭐ SEAM 3 — THE STALE CART. THE SEAM THAT MAKES THIS A FIX RATHER THAN A
 *             UI CHANGE.
 *
 * The path that seams 1 and 2 cannot reach: add a hardcover in an ORDINARY
 * session, THEN click the school link. The add already happened, legally,
 * before the flag existed. Only a cart-level gate catches it, and only a
 * STORE API cart-level gate catches it on the checkout this store actually
 * renders.
 * =========================================================================
 */

/**
 * Block the Blocks cart and checkout while a flagged cart holds a hardcover.
 *
 * @param WP_Error|object|null $errors Error bag.
 * @param WC_Cart|object|null  $cart   The cart being validated.
 * @return void
 */
function bhp_school_visit_hardcover_store_api_cart_error( $errors = null, $cart = null ) {
	if ( ! $errors || ! is_object( $errors ) || ! method_exists( $errors, 'add' ) ) {
		return; // FAIL OPEN.
	}
	if ( ! $cart && function_exists( 'WC' ) && WC()->cart ) {
		$cart = WC()->cart;
	}
	if ( ! bhp_school_visit_cart_has_refused_item( $cart ) ) {
		return; // ⭐ ZERO CHANGE for a cart with nothing refused in it.
	}
	if ( ! bhp_school_visit_paperback_only() ) {
		return; // ⭐ ZERO CHANGE for every ordinary shopper, hardcover cart included.
	}

	// 1.8.71: reason-aware, from the first refused item actually in the cart.
	$errors->add( 'bhp_school_visit_paperback_only', bhp_school_visit_cart_refusal_message( $cart ) );
}
add_action( 'woocommerce_store_api_cart_errors', 'bhp_school_visit_hardcover_store_api_cart_error', 20, 2 );

/*
 * =========================================================================
 * SEAM 4 — THE CLASSIC CART AND CHECKOUT PAGES.
 * =========================================================================
 */

/**
 * Print the refusal on the classic cart/checkout pages.
 *
 * ⚠ ONE MESSAGE PER REQUEST. `woocommerce_check_cart_items` fires on the cart
 *   page AND from inside `WC_Checkout::process_checkout()`, which would print
 *   the same sentence twice in one classic checkout POST. A request-scoped
 *   static holds it to one, exactly as `bhp_bundle_addon_guard_notice()` does.
 *
 * @return void
 */
function bhp_school_visit_hardcover_classic_cart_error() {
	static $said = false;

	if ( $said ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	if ( ! bhp_school_visit_cart_has_refused_item( WC()->cart ) ) {
		return;
	}
	if ( ! bhp_school_visit_paperback_only() ) {
		return;
	}

	$said = true;
	if ( function_exists( 'wc_add_notice' ) ) {
		// 1.8.71: reason-aware.
		wc_add_notice( bhp_school_visit_cart_refusal_message( WC()->cart ), 'error' );
	}
}
add_action( 'woocommerce_check_cart_items', 'bhp_school_visit_hardcover_classic_cart_error', 20 );

/*
 * =========================================================================
 * ⭐⭐ SEAM 5 — `WC_Cart::add_to_cart()` ITSELF.
 *
 * ⛔ READ THIS BEFORE TOUCHING ANY OF THE FIVE SEAMS. THIS ONE EXISTS
 *    BECAUSE SEAMS 1 TO 4 LEFT A REAL HOLE, AND THE HOLE WAS FOUND BY
 *    RUNNING THE CODE, NOT BY READING IT.
 *
 * ⭐ THE FACT, VERIFIED ON THE RUNNING STORE (WooCommerce 10.9.1, staging,
 *    2026-08-18) BY GREPPING THE SOURCE AND THEN BY OBSERVING A REAL CART:
 *
 *      `WC_Cart::add_to_cart()` DOES NOT APPLY
 *      `woocommerce_add_to_cart_validation`.
 *
 *    That filter is applied by `WC_Form_Handler` (three call sites),
 *    `WC_AJAX`, `WC_Cart_Session` on restore, and the Store API's
 *    `CartController` — but NOT by the cart method itself.
 *
 * ⛔ WHAT THAT MEANT IN PRACTICE. The plugin's OWN bundle forms —
 *    `/book-bundles/`, `/shop-the-series/`, the homepage Best Value band and
 *    the four funnel pages — all post to `bhp_bundle_handle_add_to_cart()`,
 *    which calls `bhp_bundle_add_titles_to_cart()`, which calls
 *    `WC()->cart->add_to_cart()` DIRECTLY. Seam 1 was therefore never
 *    consulted on that route. MEASURED on a flagged session before this seam
 *    existed: `bhp_bundle_add_titles_to_cart( 'hardcover', [all three] )`
 *    put products 14, 17 and 20 in the cart and printed "Bundle added to your
 *    cart." The hardcover controls are hidden on those surfaces, so a parent
 *    could not click one — but a bookmarked page, a browser back button after
 *    following the school link, or a page cached before the flag existed all
 *    still POST. Checkout would then have refused at seam 3, so no order could
 *    complete; the parent would simply have hit a wall with three hardcovers
 *    in the basket.
 *
 * ⭐ THE MECHANISM. `woocommerce_add_cart_item_data` fires INSIDE
 *    `WC_Cart::add_to_cart()`'s try block, and the method's own
 *    `catch ( Exception $e )` adds the message as an error notice and returns
 *    false (`includes/class-wc-cart.php`, the `add_to_cart` body, read on the
 *    running store). Throwing here therefore produces exactly WooCommerce's
 *    own "could not be added" behaviour with our sentence, rather than a
 *    fatal or a half-added line item.
 *
 * ⛔ IT DOES NOT DOUBLE-FIRE WITH SEAM 1. On the form-handler and AJAX routes
 *    seam 1 returns false BEFORE `add_to_cart()` is ever called, so this seam
 *    is never reached on those paths.
 *
 * ⛔ CONTROL PATH: for a paperback, the add-on, or any visitor without a live
 *    visit flag, this returns `$cart_item_data` untouched at the first gate.
 * =========================================================================
 */

/**
 * Refuse a hardcover from inside `WC_Cart::add_to_cart()`.
 *
 * @param array $cart_item_data Cart item data.
 * @param int   $product_id     Product id.
 * @param int   $variation_id   Variation id, or 0.
 * @return array
 * @throws Exception When a hardcover is added on a restricted session.
 */
function bhp_school_visit_block_hardcover_cart_add( $cart_item_data, $product_id = 0, $variation_id = 0 ) {
	if ( ! bhp_school_visit_is_refused_item( $product_id, $variation_id ) ) {
		return $cart_item_data; // ⭐ ZERO CHANGE for anything not on the refused list.
	}
	if ( ! bhp_school_visit_paperback_only() ) {
		return $cart_item_data; // ⭐ ZERO CHANGE for every ordinary shopper.
	}

	// 1.8.71: reason-aware.
	throw new Exception( esc_html( bhp_school_visit_refusal_message( $product_id, $variation_id ) ) );
}
add_filter( 'woocommerce_add_cart_item_data', 'bhp_school_visit_block_hardcover_cart_add', 20, 3 );

/*
 * =========================================================================
 * THE PRESENTATION HELPERS THE TEMPLATES READ.
 *
 * ⭐ THEY LIVE HERE, BESIDE THE ENFORCEMENT, ON PURPOSE. A template that
 *    decided for itself which formats to show would be a second source of
 *    truth about the same rule, and the two would drift. Every surface asks
 *    these two functions, and every surface therefore hides exactly what the
 *    server refuses.
 * =========================================================================
 */

/**
 * The physical formats this visitor may actually buy, presentation order.
 *
 * @return string[] `array( 'paperback' )` on a restricted session, otherwise
 *                  the site-wide order from `bhp_bundle_format_order()`.
 */
function bhp_bundle_available_format_order() {
	$all = function_exists( 'bhp_bundle_format_order' )
		? bhp_bundle_format_order()
		: array( 'paperback', 'hardcover' );

	if ( ! bhp_school_visit_paperback_only() ) {
		return $all;
	}

	$restricted = array_values( array_intersect( $all, array( 'paperback' ) ) );

	// FAIL OPEN: if intersecting somehow emptied the list, show the normal one.
	return empty( $restricted ) ? $all : $restricted;
}

/**
 * True when the hardcover format may be offered on this request at all.
 *
 * @return bool
 */
function bhp_bundle_hardcover_is_offerable() {
	return ! bhp_school_visit_paperback_only();
}

/**
 * The short §9.1 line a surface prints where a hardcover control used to be.
 *
 * Empty string when the session is not restricted, so a template can echo it
 * unconditionally and print nothing for an ordinary shopper.
 *
 * ⛔ NO "we". NO em dash.
 *
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.69 — THIS STRING DELIBERATELY DID **NOT** MOVE WITH THE MESSAGE.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * The refusal MESSAGE above widened to "chapter paperbacks" because it is
 * printed BY the refusal and now has to be true of a refused colouring book.
 * ⛔ This NOTE is a different job on a different surface: it is the caption
 *    printed beside a CHAPTER BOOK's format selector (the product rail and
 *    the collection page's format pills), in the space a hardcover control
 *    used to occupy. On that surface the only choice is paperback vs
 *    hardcover, "Paperback only" is exactly true, and no colouring book is
 *    on the page to be ambiguous about.
 *
 * ⛔ WIDENING IT WOULD HAVE BEEN A GRATUITOUS EDIT TO A STRING ANDREW HAS
 *    SEEN, on a surface whose meaning did not change. A string he has seen
 *    keeps its wording unless the build made it false. This one is still
 *    true.
 *
 * @return string
 */
function bhp_school_visit_paperback_only_note() {
	if ( ! bhp_school_visit_paperback_only() ) {
		return '';
	}

	/**
	 * The short note shown beside a paperback-only format selector.
	 *
	 * @param string $note Customer-facing sentence.
	 */
	return (string) apply_filters(
		'bhp_school_visit_paperback_only_note',
		__( 'Paperback only for signed copies at the visit.', 'brave-hearts' )
	);
}
