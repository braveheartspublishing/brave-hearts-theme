<?php
/**
 * Brave Hearts Bundle Pricing — SHELF STOCK FOR SCHOOL VISITS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS EXISTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-24, verbatim (⛔ RELAYED through the Chief of
 * Staff in the `CYCLE166-LD-VISIT-STOCK-GATE` dispatch, carrier item 235;
 * NOT witnessed first-hand by the agent that wrote this file):
 *
 *   "I think once we hit 1 chatper book left - we close off the option and
 *    say they have sold out"
 *
 * ⭐ THIS IS A PHYSICAL-INVENTORY FACT, NOT A WOOCOMMERCE ONE, AND THE
 *    DISTINCTION IS THE WHOLE DESIGN.
 *
 *    A school-visit order is hand-delivered. Andrew carries chapter
 *    paperbacks off his own shelf to a read aloud, signs them by hand and
 *    puts them in a child's hands. That shelf is finite and it does not
 *    refill itself: his restock slipped to Sept 7-11, and the visits are
 *    Aug 28 (`adams-2026-08-28`) and Sept 3 (`dallas-harris-2026-09-03`).
 *
 *    An ORDINARY shipped order does not touch that shelf at all. It routes
 *    to Bookvault print-on-demand, which has no inventory to run out of.
 *    ⛔ SO THIS FILE MUST BE COMPLETELY INVISIBLE TO AN ORDINARY SHOPPER,
 *    and every function below returns at the first gate for a visitor with
 *    no live visit flag.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS IS NOT WOOCOMMERCE INVENTORY. `_stock_status` IS NEVER TOUCHED.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The obvious-looking implementation — set the paperback to `outofstock` —
 * would be WRONG and it would be expensive:
 *
 *   · It would stop EVERY shopper on the site from buying a book that is
 *     print-on-demand and genuinely available to them. The shelf is empty;
 *     the BOOK is not.
 *   · `.claude/rules/woocommerce.md` is explicit: "Out-of-stock is NOT an
 *     inventory-control mechanism for print-on-demand titles", and changing
 *     `_stock_status` on any of the six core products requires a fresh
 *     current-turn decision from Andrew.
 *
 * ⭐ SO THE GATE IS SESSION-SCOPED PRESENTATION PLUS A SERVER-SIDE REFUSAL,
 *    exactly like the hardcover and colouring gates next door in
 *    `school-visit-paperback-only.php`. It changes what a FLAGGED session
 *    may add. It changes no product record, no price, no stock status, no
 *    coupon, no shipping tier and no WooCommerce setting, on any
 *    environment.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.76 (2026-08-28, `CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`) —
 *     "SOLD OUT" AND "MAY NOT BUY" STOP BEING THE SAME SENTENCE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-28, verbatim (⛔ RELAYED, carrier item 363):
 *
 *   "I think we allow backorders and we will get the new books in latest by
 *    Sept 10th. If not we will figure something out, Like dropping off the
 *    books a few days later"
 *
 * ⭐⭐ THE RULING SPLITS ONE FACT INTO TWO, AND THIS FILE IS SPLIT TO MATCH:
 *
 *      `bhp_visit_shelf_title_is_exhausted()`  the SHELF. Physical, honest,
 *                                              unchanged arithmetic. Governs
 *                                              the counter.
 *      `bhp_visit_shelf_title_is_closed()`     the PURCHASE GATE. Same name,
 *                                              same signature, same callers —
 *                                              now relaxed when backorders
 *                                              are allowed. Governs every
 *                                              surface and all five refusal
 *                                              seams.
 *
 * ⛔ NOT ONE CALLER IN THIS PLUGIN WAS EDITED. That is the point of keeping the
 *    old name on the gate rather than on the fact: `school-visit-paperback-
 *    only.php`, `offer-engine.php`, `bundle-shortcode.php` and
 *    `bundle-shop-series.php` all keep asking the identical question and
 *    receive the founder's new answer, atomically, with no possibility of a
 *    surface and a seam disagreeing.
 *
 * ⛔ THE HONESTY RULES ARE UNCHANGED. No count is ever displayed higher than
 *    the real one; the 2..10 counter window is untouched; the counter's guard
 *    was moved onto the PURE shelf fact precisely so a relaxed purchase gate
 *    could never make a suppressed number reappear. The allowance, its default,
 *    its one-line reversal and the wording that was REFUSED all live in
 *    `school-visit-backorder.php`.
 *
 * ⚠️ THE 1.8.75 STOCK-PRIVACY FILE'S CLOSING PARAGRAPH IS NOW ANSWERED. It
 *    registered exactly this residual and said resolving it needed his word.
 *    ⛔ Its own claim that a backorder "would be a WooCommerce product-
 *    configuration change" turned out to be WRONG, and that is recorded rather
 *    than quietly corrected: the sold-out state was never WooCommerce
 *    inventory, so relaxing it touches no product record at all.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * THE ARITHMETIC
 * ═══════════════════════════════════════════════════════════════════════
 *
 *     remaining = baseline - committed
 *     exhausted = remaining <= BHP_VISIT_SHELF_BUFFER   (buffer = 1)
 *     closed    = exhausted AND NOT backorders_allowed  (1.8.76)
 *
 * ⭐ `<= 1`, NOT `<= 0`, AND THAT IS THE RULING ITSELF. The last copy is
 *    deliberately kept back as buffer: "once we hit 1 chapter book left we
 *    close off the option". A damaged copy, a miscount, or a parent who
 *    turns up at the visit having ordered late all land on that one copy.
 *    Selling it would be the difference between a child getting a book and
 *    a child not getting a book, in front of a room.
 *
 * ⭐ BASELINE IS AN OPTION, NOT A CONSTANT, so a restock is one WP-CLI line
 *    and never a code change or a deploy. See `THE RESTOCK COMMAND` below.
 *
 * ⭐ COMMITTED IS COMPUTED LIVE FROM REAL ORDERS, never stored and never
 *    decremented. A stored counter drifts the first time an order is
 *    refunded, cancelled or edited in wp-admin, and nothing tells it. Every
 *    new visit order therefore moves the gate by itself, with no hook, no
 *    cron and no bookkeeping that can fall out of step.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE RESTOCK COMMAND — the one line Andrew runs when books arrive
 * ═══════════════════════════════════════════════════════════════════════
 *
 * From the WordPress document root, over SSH:
 *
 *   wp option patch update bhp_visit_shelf_stock counts --format=json \
 *     --user=1 <<< '{"mariana":21,"everest":17,"amazon":21}'
 *
 * and stamp the date the count was taken:
 *
 *   wp option patch update bhp_visit_shelf_stock as_of 2026-09-11 --user=1
 *
 * To seed the option the first time, or to replace it wholesale:
 *
 *   wp option update bhp_visit_shelf_stock --format=json --user=1 <<< \
 *     '{"as_of":"2026-09-11","counts":{"mariana":21,"everest":17,"amazon":21}}'
 *
 * To read what is currently set, and what the gate makes of it:
 *
 *   wp option get bhp_visit_shelf_stock --format=json --user=1
 *
 * ⚠️ THE COUNT IS A GROSS PHYSICAL COUNT OF WHAT IS ON THE SHELF, INCLUDING
 *    COPIES ALREADY SPOKEN FOR BY OPEN ORDERS. Do not subtract open orders
 *    by hand before entering it — this file does that, from the orders
 *    themselves. Counting net and letting the code subtract again would
 *    close every title roughly twice as early as intended.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ✅ IT FAILS OPEN, EVERYWHERE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * No option, an empty option, a malformed option, a missing catalog, an
 * order query that throws, a title absent from the baseline: every one of
 * those results in NOTHING being closed and NOBODY being blocked.
 *
 * ⭐ THAT IS WHY THIS IS SAFE TO DEPLOY BEFORE THE OPTION EXISTS. On an
 *    environment where `bhp_visit_shelf_stock` has never been set, this
 *    file is behaviourally inert: `bhp_visit_shelf_closed_titles()` returns
 *    an empty array and every seam and every surface behaves exactly as it
 *    did in 1.8.70. The cost of failing closed is a parent who cannot buy a
 *    book that is sitting on the shelf.
 *
 * ⛔ VOICE: standing rule §9.1. The customer-facing strings are I/me, never
 *    "we", carry no em dash, promise no restock date, and use AMERICAN
 *    spelling per the founder's standing rule of 2026-08-24.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The option holding the shelf baseline.
 *
 * Shape:
 *   array(
 *     'as_of'  => '2026-08-24',                       // Y-m-d, display only.
 *     'counts' => array( 'mariana' => 21, ... ),      // title slug => int.
 *   )
 */
if ( ! defined( 'BHP_VISIT_SHELF_OPTION' ) ) {
	define( 'BHP_VISIT_SHELF_OPTION', 'bhp_visit_shelf_stock' );
}

/**
 * ⭐ THE BUFFER. A title closes when remaining drops to THIS OR BELOW.
 *
 * 1, per the founder ruling. Defined rather than inlined so the number
 * appears exactly once and the tests can assert it by name.
 */
if ( ! defined( 'BHP_VISIT_SHELF_BUFFER' ) ) {
	define( 'BHP_VISIT_SHELF_BUFFER', 1 );
}

/**
 * ⭐ 1.8.72 — THE COUNTER CEILING. A live count is shown when remaining is AT
 *    OR BELOW this number and still ABOVE the buffer.
 *
 * 10, per the founder's addition of 2026-08-24 (⛔ RELAYED through the Chief of
 * Staff in the `CYCLE166-LD-VISIT-STOCK-COUNTER` dispatch, carrier items
 * 235/240; NOT witnessed first-hand by the agent that wrote this):
 *
 *   show a per-title live stock counter WHEN remaining <= 10 and > 1
 *
 * ⛔ THE WINDOW IS BOUNDED AT BOTH ENDS AND BOTH ENDS MATTER.
 *
 *      remaining >  10  -> NOTHING. Not a count, not a hint, not a class.
 *      remaining <=  1  -> the EXISTING sold-out state, completely unchanged.
 *      2 .. 10          -> the count, as a plain fact.
 *
 * ⛔ THE LOWER BOUND IS `BHP_VISIT_SHELF_BUFFER`, NOT A SECOND LITERAL `1`.
 *    If Andrew ever moves the buffer, the counter window has to move with it
 *    in the same breath. Writing `> 1` here would let a title be
 *    simultaneously "sold out" and "only 1 left", which is the single worst
 *    thing this feature could print to a parent.
 */
if ( ! defined( 'BHP_VISIT_SHELF_COUNTER_MAX' ) ) {
	define( 'BHP_VISIT_SHELF_COUNTER_MAX', 10 );
}

/**
 * The order statuses whose quantities count as COMMITTED shelf stock.
 *
 * ⭐ WHY THESE TWO, AND WHY `completed` IS DELIBERATELY ABSENT.
 *
 *    The baseline is a count of books PHYSICALLY ON THE SHELF. A book on an
 *    order that is `processing` is still on that shelf: it is paid for, and
 *    it is waiting to be carried to a school. It is committed.
 *
 *    A book on a `completed` order has already been handed to a child. It
 *    has LEFT the shelf, so the next physical recount will not find it, and
 *    counting it again here would subtract it twice.
 *
 *    `cancelled`, `refunded` and `failed` never consumed a copy at all.
 *    `pending` is an unpaid checkout that was abandoned far more often than
 *    it was finished, and holding shelf stock against one would close a
 *    title on the strength of somebody who never bought anything.
 *
 * ⚠️ THIS MAKES ONE OPERATIONAL ASSUMPTION AND IT IS STATED RATHER THAN
 *    BURIED: an order is marked `completed` WHEN THE BOOK CHANGES HANDS,
 *    not before. Marking a stack of orders complete the night before a
 *    visit would release their copies back into "remaining" while the books
 *    are still on the shelf and still owed to somebody. Flagged for Andrew
 *    rather than defended.
 *
 * @return string[] Status slugs, unprefixed.
 */
function bhp_visit_shelf_committed_statuses() {
	/**
	 * Which order statuses hold shelf stock.
	 *
	 * @param string[] $statuses Unprefixed WooCommerce status slugs.
	 */
	return (array) apply_filters(
		'bhp_visit_shelf_committed_statuses',
		array( 'processing', 'on-hold' )
	);
}

/**
 * The chapter-paperback title slugs this gate knows about, FROM THE CATALOG.
 *
 * ⛔ NOT A HARDCODED LIST, and not a hardcoded set of product ids. The brief
 *    that produced this file named the titles in prose; `bhp_bundle_catalog()`
 *    is the single owner of which WooCommerce record is which edition, and a
 *    second list here would be wrong the first time a product id moves.
 *
 * ⭐ VERIFIED READ-ONLY OVER SSH, 2026-08-24, BOTH ENVIRONMENTS: the six core
 *    product ids are IDENTICAL on production and staging (333/334 Mariana PB,
 *    15 Everest PB, 18 Amazon PB) but their SKUs are NOT (production carries
 *    ISBNs, staging carries `BHP-*` codes). ⛔ THAT IS WHY THIS KEYS ON THE
 *    CATALOG'S TITLE SLUG AND NOT ON SKU. The colouring gate next door keys on
 *    SKU and is right to, because ITS product id differs across environments
 *    (618 production, 4065 staging) while its SKU does not. Two catalogs, two
 *    stable keys, and picking the wrong one produces a suite that passes
 *    vacuously on the environment it is QA'd on.
 *
 * @return string[] Title slugs, e.g. array( 'mariana', 'everest', 'amazon' ).
 */
function bhp_visit_shelf_title_slugs() {
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		return array(); // FAIL OPEN: no catalog -> no title is ever closed.
	}

	try {
		$catalog = bhp_bundle_catalog();
	} catch ( Throwable $e ) {
		return array(); // FAIL OPEN.
	}

	if ( ! isset( $catalog['paperback'] ) || ! is_array( $catalog['paperback'] ) ) {
		return array();
	}

	return array_keys( $catalog['paperback'] );
}

/**
 * The shelf baseline, sanitised.
 *
 * Fails closed on every malformed row rather than guessing, in the same shape
 * `bhp_school_visit_records()` does: a count that is not a non-negative
 * integer, or a slug that is not in the catalog, is DROPPED rather than
 * defaulted. A dropped title is simply never closed, which is the safe
 * direction.
 *
 * @return array{as_of:string,counts:array<string,int>}
 */
function bhp_visit_shelf_baseline() {
	$empty = array(
		'as_of'  => '',
		'counts' => array(),
	);

	$raw = get_option( BHP_VISIT_SHELF_OPTION, array() );
	if ( ! is_array( $raw ) || empty( $raw['counts'] ) || ! is_array( $raw['counts'] ) ) {
		return $empty; // FAIL OPEN: option missing or shapeless -> nothing closes.
	}

	$known  = bhp_visit_shelf_title_slugs();
	$counts = array();

	foreach ( $raw['counts'] as $slug => $count ) {
		$slug = sanitize_key( (string) $slug );

		if ( '' === $slug || ! in_array( $slug, $known, true ) ) {
			continue; // Not a title this catalog knows. Drop it.
		}
		if ( ! is_numeric( $count ) ) {
			continue; // Not a number. Drop it rather than call it zero.
		}

		$count = (int) $count;
		if ( $count < 0 ) {
			continue; // Negative shelf stock is a typo, not a fact.
		}

		$counts[ $slug ] = $count;
	}

	$as_of = isset( $raw['as_of'] ) ? trim( (string) $raw['as_of'] ) : '';
	if ( ! function_exists( 'bhp_school_visit_is_ymd' ) || ! bhp_school_visit_is_ymd( $as_of ) ) {
		$as_of = ''; // Display-only. A bad date never gates anything.
	}

	return array(
		'as_of'  => $as_of,
		'counts' => $counts,
	);
}

/**
 * ⭐⭐ COMMITTED COPIES PER TITLE, COMPUTED LIVE FROM REAL ORDERS.
 *
 * ⛔ IT COUNTS LINE-ITEM QUANTITIES, NOT ORDERS, AND THAT IS LOAD-BEARING.
 *    A parent who buys the three-book set produces ONE order carrying THREE
 *    separate chapter line items, one per title. ⭐ VERIFIED READ-ONLY
 *    AGAINST PRODUCTION, 2026-08-24: 13 visit-flagged orders exist and five
 *    of them (#614, #622, #625, #626, #629) carry all three titles as three
 *    distinct line items. There is NO single "complete collection" product
 *    on either environment, so no line item ever hides more than one title.
 *    Counting orders instead of quantities would have undercounted the shelf
 *    by 10 copies on the day this was written.
 *
 * ⛔ IT COUNTS ONLY VISIT-FLAGGED ORDERS. `_bhp_school_pickup === 'yes'` is
 *    set by `bhp_school_visit_stamp_order()` and is the same flag that makes
 *    an order skip the Bookvault webhook. An ordinary shipped order prints
 *    on demand and consumes NO shelf stock, so including one would close a
 *    title on the strength of a book Andrew never has to carry.
 *
 * ⛔ IT IS NOT CACHED ACROSS REQUESTS, ONLY WITHIN ONE. A transient would
 *    mean a parent could add the copy that the previous parent just bought,
 *    for as long as the transient lived. The per-request static exists only
 *    so the several surfaces on one page render from one query.
 *
 * @return array<string,int> Title slug => committed quantity. Empty on any failure.
 */
function bhp_visit_shelf_committed() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$cache = array();

	if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'bhp_bundle_catalog' ) ) {
		return $cache; // FAIL OPEN.
	}

	// Reverse map: every id that means a chapter paperback -> its title slug.
	$by_id = array();
	try {
		$catalog = bhp_bundle_catalog();
	} catch ( Throwable $e ) {
		return $cache; // FAIL OPEN.
	}
	if ( ! isset( $catalog['paperback'] ) || ! is_array( $catalog['paperback'] ) ) {
		return $cache;
	}
	foreach ( $catalog['paperback'] as $slug => $edition ) {
		if ( ! empty( $edition['product_id'] ) ) {
			$by_id[ (int) $edition['product_id'] ] = $slug;
		}
		if ( ! empty( $edition['variation_id'] ) ) {
			$by_id[ (int) $edition['variation_id'] ] = $slug;
		}
	}
	if ( empty( $by_id ) ) {
		return $cache; // FAIL OPEN: nothing recognisable.
	}

	try {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => bhp_visit_shelf_committed_statuses(),
				'return'     => 'objects',
				'meta_query' => array(
					array(
						'key'   => defined( 'BHP_SCHOOL_PICKUP_META_FLAG' ) ? BHP_SCHOOL_PICKUP_META_FLAG : '_bhp_school_pickup',
						'value' => 'yes',
					),
				),
			)
		);
	} catch ( Throwable $e ) {
		return $cache; // FAIL OPEN: a failing query must never close a title.
	}

	if ( empty( $orders ) || ! is_array( $orders ) ) {
		return $cache;
	}

	foreach ( $orders as $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
			continue;
		}
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
				continue;
			}

			$variation_id = method_exists( $item, 'get_variation_id' ) ? (int) $item->get_variation_id() : 0;
			$product_id   = (int) $item->get_product_id();

			// Prefer the variation: Mariana paperback is the one variable product.
			$slug = null;
			if ( $variation_id && isset( $by_id[ $variation_id ] ) ) {
				$slug = $by_id[ $variation_id ];
			} elseif ( isset( $by_id[ $product_id ] ) ) {
				$slug = $by_id[ $product_id ];
			}

			if ( null === $slug ) {
				continue; // Activity book, colouring book, hardcover, anything else.
			}

			$qty = method_exists( $item, 'get_quantity' ) ? (int) $item->get_quantity() : 1;
			if ( $qty < 1 ) {
				$qty = 1;
			}

			$cache[ $slug ] = isset( $cache[ $slug ] ) ? $cache[ $slug ] + $qty : $qty;
		}
	}

	return $cache;
}

/**
 * Remaining shelf copies per title.
 *
 * ⛔ ONLY TITLES WITH A BASELINE APPEAR. A title Andrew has not counted is
 *    absent from the result rather than present with a guessed number, and an
 *    absent title is never closed.
 *
 * @return array<string,int> Title slug => remaining (may be negative if oversold).
 */
function bhp_visit_shelf_remaining() {
	$baseline  = bhp_visit_shelf_baseline();
	$committed = bhp_visit_shelf_committed();
	$out       = array();

	foreach ( $baseline['counts'] as $slug => $count ) {
		$out[ $slug ] = $count - ( isset( $committed[ $slug ] ) ? (int) $committed[ $slug ] : 0 );
	}

	return $out;
}

/**
 * ⭐ IS THIS TITLE'S SHELF EXHAUSTED? THE PURE PHYSICAL FACT, AND NOTHING ELSE.
 *
 * ⛔ THIS FUNCTION DOES NOT ASK WHETHER THE SESSION IS FLAGGED. It answers a
 *    question about the SHELF, which is true or false regardless of who is
 *    looking. The visit-mode gate is applied by the callers, so that a report,
 *    a test or a future admin screen can ask "what is actually low" without
 *    having to fake a session.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.76 (`CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`) — THIS FUNCTION
 *     WAS CALLED `bhp_visit_shelf_title_is_closed()` UNTIL THIS RELEASE, AND
 *     THE RENAME IS THE WHOLE POINT OF THE CHANGE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ ITS BODY IS BYTE-FOR-BYTE WHAT IT WAS, INCLUDING ITS FILTER NAME. Not one
 *    line of the arithmetic moved. What moved is the QUESTION IT IS ANSWERING.
 *
 * ⭐⭐ WHY THE SPLIT EXISTS. Item 363 allows visit-flagged parents to order
 *    past the shelf count. That makes "the shelf is empty" and "the parent may
 *    not buy" two DIFFERENT facts for the first time — they were the same fact
 *    from 1.8.71 to 1.8.75, which is why one function served both. Keeping one
 *    function would have forced a choice between two wrong outcomes:
 *      · relax it, and the COUNTER's "closed outranks counted" guard relaxes
 *        with it, so a title Andrew closed by hand at remaining = 6 starts
 *        printing "Only 6 left" again;
 *      · leave it, and the five refusal seams keep refusing an order the
 *        founder said to accept.
 *
 * ⭐ SO: THIS function is the SHELF (used by the counter's guard, by reports
 *   and by the backorder module). `bhp_visit_shelf_title_is_closed()` below is
 *   the PURCHASE GATE (used by every surface and every refusal seam). The two
 *   are identical whenever backorders are off, which is why 1.8.75 behaviour is
 *   recoverable with one filter.
 *
 * ⛔ THE FILTER IS STILL NAMED `bhp_visit_shelf_title_is_closed`, DELIBERATELY
 *    AND DESPITE THE RENAME. Andrew's documented one-liner for closing or
 *    reopening a title by hand uses that name, it may already be sitting in a
 *    mu-plugin, and silently invalidating a documented operator command to buy
 *    tidier naming is a bad trade. ⭐ A hand-close through it still suppresses
 *    the counter and still marks the title exhausted; whether that ALSO blocks
 *    the purchase is now the backorder allowance's business, one layer up.
 *
 * @since 1.8.76 Renamed from `bhp_visit_shelf_title_is_closed()`.
 * @param string $slug Title slug.
 * @return bool True when remaining <= the buffer.
 */
function bhp_visit_shelf_title_is_exhausted( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return false;
	}

	$remaining = bhp_visit_shelf_remaining();

	/*
	 * ⛔ NO BASELINE FOR THIS TITLE -> NOT EXHAUSTED. That is the fail-open
	 *    default, and it is what makes this whole file inert on an
	 *    environment where `bhp_visit_shelf_stock` has never been set.
	 */
	$has_baseline   = isset( $remaining[ $slug ] );
	$remaining_here = $has_baseline ? (int) $remaining[ $slug ] : null;
	$closed         = $has_baseline && $remaining_here <= (int) BHP_VISIT_SHELF_BUFFER;

	/**
	 * Whether one title's shelf is exhausted for school visits.
	 *
	 * ⭐ FILTERABLE so Andrew can reopen a title he has found more copies of,
	 *    or close one by hand, without a code change and without a deploy.
	 *
	 * ⛔ THE FILTER RUNS EVEN WHEN THERE IS NO BASELINE, deliberately. That is
	 *    what lets a title be closed by hand on an environment with no counts
	 *    seeded at all, and it is what lets the test suite exercise the closed
	 *    state WITHOUT writing the option on a live environment. Returning the
	 *    default unchanged keeps the fail-open behaviour exactly as documented.
	 *
	 * ⛔ NAME KEPT AT `bhp_visit_shelf_title_is_closed` ACROSS THE 1.8.76
	 *    RENAME. See this function's header for why.
	 *
	 * @param bool     $closed    True when the title's shelf is exhausted.
	 * @param string   $slug      Title slug.
	 * @param int|null $remaining Copies remaining, or null when uncounted.
	 */
	return (bool) apply_filters(
		'bhp_visit_shelf_title_is_closed',
		$closed,
		$slug,
		$remaining_here
	);
}

/**
 * ⭐⭐ IS THIS TITLE CLOSED TO PURCHASE FOR SCHOOL VISITS?
 *
 * ⭐ THE ONE PREDICATE EVERY SURFACE AND EVERY REFUSAL SEAM ASKS, directly or
 *   through `bhp_visit_shelf_closed_titles()` / `_closed_map_for_request()` /
 *   `bhp_visit_shelf_is_closed_item()`. Its NAME AND SIGNATURE ARE UNCHANGED
 *   from 1.8.71, which is why not one caller in this plugin had to be edited:
 *   all five refusal seams, `offer-engine.php`, `bundle-shortcode.php` and
 *   `bundle-shop-series.php` keep asking the same question and get the
 *   founder's new answer.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE RELAXATION IS APPLIED **HERE AND ONLY HERE**, AND THAT IS THE
 *     PROPERTY THAT STOPS A SURFACE FROM EVER DISAGREEING WITH A SEAM.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * This file's oldest invariant, from `bhp_visit_shelf_closed_titles()`: "the
 * server would refuse a title the page had just offered ... Asking the
 * predicate about every known title is the only way the two can never drift."
 * ⭐ One relaxation point keeps that invariant intact. Relaxing the surfaces
 * and the seams separately is how a page grows an Add to Cart button that the
 * checkout then rejects, in front of a parent.
 *
 * ⛔ IT FAILS TO THE 1.8.75 BEHAVIOUR. With the backorder module absent — an
 *    older plugin build, a partial deploy, a file that failed to load — this
 *    returns exactly what it returned in 1.8.75: exhausted means closed. A
 *    missing module can therefore only ever be MORE restrictive, never less,
 *    which is the safe direction for a gate.
 *
 * @since 1.8.76 Now consults the backorder allowance; arithmetic unchanged.
 * @param string $slug Title slug.
 * @return bool True when the title may NOT be added on a visit-flagged session.
 */
function bhp_visit_shelf_title_is_closed( $slug ) {
	$exhausted = bhp_visit_shelf_title_is_exhausted( $slug );

	if ( ! $exhausted ) {
		return false; // Nothing to relax.
	}

	$blocked = true;
	if ( function_exists( 'bhp_visit_shelf_backorder_allowed' ) ) {
		try {
			if ( bhp_visit_shelf_backorder_allowed( $slug ) ) {
				$blocked = false; // ⭐ ITEM 363: the order is accepted anyway.
			}
		} catch ( Throwable $e ) {
			$blocked = true; // FAIL TO 1.8.75.
		}
	}

	/**
	 * Whether one title is closed to PURCHASE for school visits.
	 *
	 * ⛔ A SEPARATE FILTER FROM `bhp_visit_shelf_title_is_closed`, on purpose.
	 *    That one is the SHELF FACT and still governs the counter; this one is
	 *    the PURCHASE GATE. Confusing them is the whole reason 1.8.76 split
	 *    them, and giving them one name would rebuild the confusion.
	 *
	 * @since 1.8.76
	 * @param bool   $blocked   True when the title may not be added.
	 * @param string $slug      Title slug.
	 * @param bool   $exhausted True when the physical shelf is at or below the buffer.
	 */
	return (bool) apply_filters(
		'bhp_visit_shelf_title_is_blocked',
		$blocked,
		$slug,
		$exhausted
	);
}

/**
 * Every closed title slug.
 *
 * @return string[]
 */
function bhp_visit_shelf_closed_titles() {
	/*
	 * ⛔ IT ITERATES THE CATALOG, NOT THE BASELINE. Iterating the baseline
	 *    would silently skip any title closed by the
	 *    `bhp_visit_shelf_title_is_closed` filter rather than by a count, and
	 *    the surfaces would then disagree with the seams: the server would
	 *    refuse a title the page had just offered. Asking the predicate about
	 *    every known title is the only way the two can never drift.
	 */
	$out = array();
	foreach ( bhp_visit_shelf_title_slugs() as $slug ) {
		if ( bhp_visit_shelf_title_is_closed( $slug ) ) {
			$out[] = $slug;
		}
	}
	return $out;
}

/**
 * ⭐ THE VISIT-MODE PREDICATE THE SURFACES AND SEAMS ACTUALLY ASK.
 *
 * This is the one that couples the shelf to the session. It is false for
 * every ordinary shopper on every environment, always, which is the property
 * that makes this feature safe.
 *
 * @param string $slug Title slug.
 * @return bool
 */
function bhp_visit_shelf_title_is_closed_for_request( $slug ) {
	if ( ! function_exists( 'bhp_school_visit_paperback_only' ) ) {
		return false; // FAIL OPEN: no visit gate loaded -> nothing is withheld.
	}

	try {
		if ( ! bhp_school_visit_paperback_only() ) {
			return false; // ⭐ ZERO CHANGE for every ordinary shopper.
		}
	} catch ( Throwable $e ) {
		return false; // FAIL OPEN.
	}

	return bhp_visit_shelf_title_is_closed( $slug );
}

/**
 * ⭐ THE ONE CALL EVERY PURCHASE SURFACE MAKES.
 *
 * Returns the closed title slugs for THIS request, as a lookup map so a
 * template can test membership without a function call per row.
 *
 * ⛔ EMPTY FOR EVERY ORDINARY SHOPPER, on every environment, always. A
 *    template can therefore compute it once at the top and branch on it
 *    without any visit-mode check of its own, which is what stops a surface
 *    from becoming a second source of truth about who is restricted.
 *
 * @return array<string,true> Closed title slug => true. Empty when nothing closes.
 */
function bhp_visit_shelf_closed_map_for_request() {
	if ( ! function_exists( 'bhp_school_visit_paperback_only' ) ) {
		return array(); // FAIL OPEN.
	}

	try {
		if ( ! bhp_school_visit_paperback_only() ) {
			return array(); // ⭐ ZERO CHANGE for every ordinary shopper.
		}
	} catch ( Throwable $e ) {
		return array(); // FAIL OPEN.
	}

	$map = array();
	foreach ( bhp_visit_shelf_closed_titles() as $slug ) {
		$map[ $slug ] = true;
	}
	return $map;
}

/* =========================================================================
 * ⭐⭐ 1.8.72 — THE LIVE COUNTER (CYCLE166-LD-VISIT-STOCK-COUNTER)
 *
 * ⛔ IT IS THE SAME NUMBER, ASKED THE SAME WAY, EVERY TIME. There is no
 *    counter store, no counter option, no counter meta and no counter cache.
 *    `bhp_visit_shelf_remaining()` is the one arithmetic, and it is
 *    `baseline - committed` computed from real orders on every render. A
 *    displayed number that disagreed with the gate that refuses the add would
 *    be worse than no number at all: the page would offer six copies and the
 *    server would refuse the next one.
 *
 * ⛔ CLOSED OUTRANKS COUNTED, ALWAYS. Every entry point below asks
 *    `bhp_visit_shelf_title_is_closed()` FIRST and returns null if it is true.
 *    That predicate is filterable, so a title Andrew closes by hand at
 *    remaining = 6 must go silent here rather than print "Only 6 left" beside
 *    a sold-out badge.
 *
 * ⛔ NOTHING BELOW EVER RETURNS A NUMBER FOR AN UNFLAGGED SESSION. The two
 *    `_for_request` functions gate on `bhp_school_visit_paperback_only()`
 *    exactly as the closed map does, and the surfaces call ONLY those two.
 * ====================================================================== */

/**
 * ⭐ THE LIVE REMAINING COUNT FOR ONE TITLE, IF IT IS IN THE DISPLAY WINDOW.
 *
 * ⛔ THIS FUNCTION DOES NOT ASK WHETHER THE SESSION IS FLAGGED, for the same
 *    reason `bhp_visit_shelf_title_is_closed()` does not: it answers a question
 *    about the SHELF. A report or a test can ask "what is actually low" without
 *    faking a session. ⛔ SURFACES MUST NOT CALL THIS ONE.
 *
 * @param string $slug Title slug.
 * @return int|null Copies remaining when 1 < remaining <= the ceiling; null otherwise.
 */
function bhp_visit_shelf_title_counter( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}

	$remaining = bhp_visit_shelf_remaining();

	/*
	 * ⛔ NO BASELINE -> NO COUNTER. This is what keeps 1.8.72 as inert as 1.8.71
	 *    on an environment where `bhp_visit_shelf_stock` has never been set:
	 *    `bhp_visit_shelf_remaining()` is empty, so every title returns null and
	 *    not one byte of counter markup is emitted anywhere on the site.
	 */
	$has_baseline = isset( $remaining[ $slug ] );
	$live         = $has_baseline ? (int) $remaining[ $slug ] : null;

	$ceiling = (int) apply_filters( 'bhp_visit_shelf_counter_max', (int) BHP_VISIT_SHELF_COUNTER_MAX, $slug );

	/*
	 * ⛔⛔ 1.8.76 — THIS GUARD ASKS `_is_exhausted()`, NOT `_is_closed()`, AND
	 *     THE ONE-WORD DIFFERENCE IS A HONESTY GUARANTEE, NOT A TIDY-UP.
	 *
	 *     `_is_closed()` now RELAXES when backorders are allowed. If this guard
	 *     still asked it, then a title Andrew had closed BY HAND at remaining=6
	 *     through the `bhp_visit_shelf_title_is_closed` filter would become
	 *     "not closed" the moment backorders were on, and this function would
	 *     start printing "Only 6 left for the school visit" for a title he had
	 *     deliberately taken off the shelf. ⛔ THE COUNTER MUST FOLLOW THE
	 *     PHYSICAL FACT, ALWAYS. It reports what is on the shelf; it does not
	 *     report what somebody is allowed to order.
	 *
	 * ⭐ THE ORIGINAL RULE IS UNCHANGED IN SPIRIT AND IN EFFECT: CLOSED
	 *   OUTRANKS COUNTED. The window is still `> BHP_VISIT_SHELF_BUFFER`, so
	 *   the smallest number this can print is still 2 and an exhausted title
	 *   still prints nothing at all.
	 */
	$out = null;
	if ( $has_baseline
		&& ! bhp_visit_shelf_title_is_exhausted( $slug )   // ⛔ EXHAUSTED OUTRANKS COUNTED.
		&& $live > (int) BHP_VISIT_SHELF_BUFFER
		&& $live <= $ceiling ) {
		$out = $live;
	}

	/**
	 * The counter value for one title, or null for "print nothing".
	 *
	 * ⭐ THE FILTER RUNS EVEN WHEN THE ANSWER IS null, deliberately, and for the
	 *    same reason `bhp_visit_shelf_title_is_closed` does: it is the ONLY way
	 *    the suite can exercise a low shelf WITHOUT writing `bhp_visit_shelf_stock`
	 *    on a live environment.
	 *
	 * @param int|null $out      The count to display, or null.
	 * @param string   $slug     Title slug.
	 * @param int|null $live     Live remaining, or null when uncounted.
	 * @param int      $ceiling  The display ceiling in force.
	 */
	$out = apply_filters( 'bhp_visit_shelf_title_counter', $out, $slug, $live, $ceiling );

	if ( null === $out ) {
		return null;
	}

	// A filter that returns something absurd must not reach a customer.
	$out = (int) $out;
	return $out > 0 ? $out : null;
}

/**
 * ⭐ THE VISIT-MODE COUNTER. null for every ordinary shopper, always.
 *
 * ⭐⭐ 1.8.75 (`CYCLE168-LD-AMITY-STOCK-SUPPRESSION`) ADDS THE SECOND GATE.
 *     See `bhp_visit_shelf_counter_map_for_request()` below for why it is here
 *     and not inside `bhp_visit_shelf_title_counter()`.
 *
 * @param string $slug Title slug.
 * @return int|null
 */
function bhp_visit_shelf_counter_for_request( $slug ) {
	if ( ! function_exists( 'bhp_school_visit_paperback_only' ) ) {
		return null; // FAIL SILENT: no visit gate loaded -> no stock is ever hinted at.
	}

	try {
		if ( ! bhp_school_visit_paperback_only() ) {
			return null; // ⭐⭐ ZERO STOCK MARKUP for every ordinary shopper.
		}
		// ⭐ 1.8.75 — this visit does not show quantities. Amity, by founder ruling.
		if ( function_exists( 'bhp_school_visit_hide_stock_for_request' )
			&& bhp_school_visit_hide_stock_for_request() ) {
			return null;
		}
	} catch ( Throwable $e ) {
		return null; // FAIL SILENT.
	}

	return bhp_visit_shelf_title_counter( $slug );
}

/**
 * ⭐ THE ONE CALL EVERY SURFACE MAKES.
 *
 * ⛔ EMPTY FOR EVERY ORDINARY SHOPPER, on every environment, always — and
 *    empty on any environment with no shelf baseline. A template can compute it
 *    once at the top and branch on it with no visit check of its own, which is
 *    what stops a surface from becoming a second source of truth about stock.
 *
 * ⭐⭐ 1.8.75 (`CYCLE168-LD-AMITY-STOCK-SUPPRESSION`) — AND EMPTY FOR A VISIT
 *     WHOSE PARENTS MUST NOT SEE A QUANTITY. Andrew Signore, 2026-08-28, on
 *     Amity (⛔ RELAYED, carrier item 359): *"I just want Amity not to see the
 *     current stock since we will have 75 more books coming Sept 7-11."*
 *
 * ⛔ THE GATE IS HERE AND IN `bhp_visit_shelf_counter_for_request()`, NOT
 *    INSIDE `bhp_visit_shelf_title_counter()`, AND THE PLACEMENT IS THE WHOLE
 *    DESIGN. That function's own header says it "does not ask whether the
 *    session is flagged" because it answers a question about the SHELF, so a
 *    report or a suite can ask what is actually low without faking a session.
 *    Putting a session check inside it would break that contract and would
 *    make the arithmetic itself lie to Andrew's own tooling. ⭐ These two
 *    `_for_request` functions are, by this file's own rule, THE ONLY TWO A
 *    SURFACE MAY CALL — so gating both gates every surface, including
 *    `bhp_visit_shelf_constraining_title_for_request()`, which reads this map.
 *
 * ⛔ IT SUPPRESSES A NUMBER AND NOTHING ELSE. The sold-out state, the buffer,
 *    the closed map and all five server refusal seams are untouched: an Amity
 *    session sees the ordinary purchasable page, and sees "Sold out for the
 *    school visit" if and only if that title really is closed.
 *
 * @return array<string,int> Title slug => copies remaining. Empty when nothing shows.
 */
function bhp_visit_shelf_counter_map_for_request() {
	if ( ! function_exists( 'bhp_school_visit_paperback_only' ) ) {
		return array();
	}

	try {
		if ( ! bhp_school_visit_paperback_only() ) {
			return array(); // ⭐⭐ ZERO STOCK MARKUP for every ordinary shopper.
		}
		// ⭐ 1.8.75 — this visit does not show quantities. Amity, by founder ruling.
		if ( function_exists( 'bhp_school_visit_hide_stock_for_request' )
			&& bhp_school_visit_hide_stock_for_request() ) {
			return array();
		}
	} catch ( Throwable $e ) {
		return array();
	}

	$map = array();
	foreach ( bhp_visit_shelf_title_slugs() as $slug ) {
		$n = bhp_visit_shelf_title_counter( $slug );
		if ( null !== $n ) {
			$map[ $slug ] = (int) $n;
		}
	}
	return $map;
}

/**
 * ⭐ THE CONSTRAINING TITLE OF A FORMAT: the open title with the FEWEST copies.
 *
 * Used only by the three-book box, and only when the box counter is switched on
 * (it is off by default — see `bhp_visit_shelf_counter_on_complete_box()`).
 *
 * @param string $format Catalog format key, e.g. 'paperback'.
 * @return string|null Title slug, or null when no title in this format is low.
 */
function bhp_visit_shelf_constraining_title_for_request( $format ) {
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		return null;
	}

	try {
		$catalog = bhp_bundle_catalog();
	} catch ( Throwable $e ) {
		return null;
	}

	if ( ! isset( $catalog[ $format ] ) || ! is_array( $catalog[ $format ] ) ) {
		return null;
	}

	$counters = array_intersect_key( bhp_visit_shelf_counter_map_for_request(), $catalog[ $format ] );
	if ( empty( $counters ) ) {
		return null;
	}

	asort( $counters, SORT_NUMERIC );
	$slugs = array_keys( $counters );
	return $slugs[0];
}

/**
 * ⭐⭐ THE ONE-LINE FLIP: does the THREE-BOOK BOX carry a counter?
 *
 * DEFAULT false, AND THAT IS A RECOMMENDATION MADE EXPLICIT RATHER THAN A
 * DEFAULT CHOSEN BY ACCIDENT. The reasoning, so it is not re-derived:
 *
 * ⛔ A NUMBER ON A THREE-TITLE CARD IS AMBIGUOUS ABOUT WHAT IT COUNTS. The
 *    complete-collection card has no per-title rows and one button. "Only 3
 *    left for the school visit" printed on it reads as THREE SETS, when what it
 *    means is three copies of ONE of the three books. Naming the title to fix
 *    the ambiguity ("Only 3 Mount Everest left") turns a set card into a
 *    per-title inventory notice, which is the job the per-title lists on the
 *    SAME PAGE already do: `/shop-the-series/` renders the single-title list
 *    directly above it, and `/book-bundles/` renders the "choose any two" list
 *    directly above it. The constraining title's count is visible either way.
 *
 * ⛔ AND THE SET IS THE THING MOST LIKELY TO BE WRONG. If Everest is at 3 and
 *    the parent buys three separate single Everest copies, the box was never
 *    limited to 3 in the first place — it is limited to 3 SETS only if the
 *    other two titles are also at 3 or more. Printing a per-title number on a
 *    set card invites a parent to do arithmetic that the number does not
 *    support.
 *
 * ⭐ TO FLIP IT, one line in a theme or mu-plugin, no deploy:
 *      add_filter( 'bhp_visit_shelf_counter_on_complete_box', '__return_true' );
 *    The true branch is fully implemented and covered by the suite, so the flip
 *    is a switch and not a build.
 *
 * @return bool
 */
function bhp_visit_shelf_counter_on_complete_box() {
	/**
	 * Whether the complete-collection card shows the constraining title's count.
	 *
	 * @param bool $show Default false.
	 */
	return (bool) apply_filters( 'bhp_visit_shelf_counter_on_complete_box', false );
}

/**
 * How many titles of a format a visit-flagged shopper may still choose from.
 *
 * Used by the "choose any two" card, which has nothing to offer once fewer
 * than two titles remain open.
 *
 * @param string $format Catalog format key, e.g. 'paperback'.
 * @return int
 */
function bhp_visit_shelf_open_title_count( $format ) {
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		return PHP_INT_MAX; // FAIL OPEN: never suppress a card.
	}

	try {
		$catalog = bhp_bundle_catalog();
	} catch ( Throwable $e ) {
		return PHP_INT_MAX; // FAIL OPEN.
	}

	if ( ! isset( $catalog[ $format ] ) || ! is_array( $catalog[ $format ] ) ) {
		return PHP_INT_MAX;
	}

	$closed = bhp_visit_shelf_closed_map_for_request();
	$open   = 0;
	foreach ( array_keys( $catalog[ $format ] ) as $slug ) {
		if ( ! isset( $closed[ $slug ] ) ) {
			++$open;
		}
	}
	return $open;
}

/**
 * Which chapter-paperback title is this product/variation pair, if any?
 *
 * ⛔ PAPERBACK ONLY, ON PURPOSE. A hardcover is already refused outright on a
 *    flagged session by `bhp_school_visit_is_hardcover()`, and it is not on
 *    the shelf Andrew carries, so it has no shelf count and must not acquire
 *    one here.
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return string|null Title slug, or null.
 */
function bhp_visit_shelf_identify_title( $product_id, $variation_id = 0 ) {
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		return null; // FAIL OPEN.
	}

	try {
		$catalog = bhp_bundle_catalog();
	} catch ( Throwable $e ) {
		return null; // FAIL OPEN.
	}

	if ( ! isset( $catalog['paperback'] ) || ! is_array( $catalog['paperback'] ) ) {
		return null;
	}

	$product_id   = (int) $product_id;
	$variation_id = (int) $variation_id;

	foreach ( $catalog['paperback'] as $slug => $edition ) {
		$pid = ! empty( $edition['product_id'] ) ? (int) $edition['product_id'] : 0;
		$vid = ! empty( $edition['variation_id'] ) ? (int) $edition['variation_id'] : 0;

		if ( $vid && $variation_id && $vid === $variation_id ) {
			return $slug;
		}
		if ( $pid && $product_id && $pid === $product_id ) {
			return $slug;
		}
		if ( $vid && $product_id && $vid === $product_id ) {
			return $slug; // A variation id arriving in the product slot.
		}
	}

	return null;
}

/**
 * ⭐ THE PRODUCT-LEVEL PREDICATE THE REFUSAL SEAMS ASK.
 *
 * @param int $product_id   Product id.
 * @param int $variation_id Variation id, or 0.
 * @return bool True when this item is a chapter paperback whose shelf is closed.
 */
function bhp_visit_shelf_is_closed_item( $product_id, $variation_id = 0 ) {
	$slug = bhp_visit_shelf_identify_title( $product_id, $variation_id );
	if ( null === $slug ) {
		return false;
	}
	return bhp_visit_shelf_title_is_closed( $slug );
}

/* =========================================================================
 * THE CUSTOMER-FACING WORDS.
 *
 * ⛔⛔ FLAGGED FOR ANDREW'S EYE — DRAFT, NOT SELF-APPROVED. Both strings
 *     below are new customer-facing copy written by an agent and are marked
 *     NEEDS ANDREW in the build report. They ship behind an inert gate (no
 *     baseline option is set on production), so nothing prints them to a
 *     real parent until he both approves the words and seeds the option.
 *
 * ⛔ §9.1 VOICE RULE: no "we", "us" or "our" standing for the company. I/me.
 * ⛔ NO EM DASH. Sitewide standing constraint.
 * ⛔ NO RESTOCK DATE AND NO RESTOCK PROMISE. His restock has already slipped
 *    once (Sept 7-11), and a date in a storefront string becomes a promise to
 *    a parent that nobody can keep.
 * ⛔ AMERICAN SPELLING, per the founder's standing rule of 2026-08-24.
 * ⛔ NO OUTCOME CLAIM, no urgency device, no apology-shaped padding.
 * ====================================================================== */

/**
 * The short label printed where a purchase control used to be.
 *
 * @return string
 */
function bhp_visit_shelf_sold_out_label() {
	/**
	 * The short sold-out label for a closed title in visit mode.
	 *
	 * @param string $label Customer-facing label.
	 */
	return (string) apply_filters(
		'bhp_visit_shelf_sold_out_label',
		__( 'Sold out for the school visit', 'brave-hearts' )
	);
}

/**
 * The full sentence: printed by the server refusal, and beside a closed title.
 *
 * @return string
 */
function bhp_visit_shelf_sold_out_message() {
	/**
	 * The sentence shown when a closed title meets a visit-flagged session.
	 *
	 * @param string $message Customer-facing sentence.
	 */
	return (string) apply_filters(
		'bhp_visit_shelf_sold_out_message',
		__( 'This title is sold out for the school visit. I sign and hand deliver these from the copies I have with me, and the rest are already spoken for. The other adventures are still available for the visit, and you can order this one from the shop any time to be shipped to your home.', 'brave-hearts' )
	);
}

/* =========================================================================
 * ⭐⭐ 1.8.72 — THE COUNTER'S WORDS.
 *
 * ⛔⛔ FLAGGED FOR ANDREW'S EYE — DRAFT, NOT SELF-APPROVED, marked NEEDS ANDREW
 *     in the build report. The DEFAULT below is the founder's own phrasing as
 *     carried in the dispatch ("e.g. 'Only 6 left for the school visit'"), so
 *     the shipped string is his sentence and not an agent's invention. Two
 *     alternates are named in the report and each is a one-line filter away.
 *
 * ⛔ IT IS A FACT, NOT A DEVICE. The founder's constraint, carried verbatim:
 *    "never styled as urgency theater beyond the plain fact." So:
 *      · no "hurry", "act fast", "going fast", "last chance", "don't miss";
 *      · no exclamation mark;
 *      · no countdown, no timer, no "X people are viewing";
 *      · no claim about how quickly the rest will go;
 *      · and the CSS class is quiet type, not a red alarm badge.
 *
 * ⛔ NO SINGULAR FORM IS DEFINED, ON PURPOSE. The display window is
 *    `remaining > BHP_VISIT_SHELF_BUFFER`, and the buffer is 1, so the smallest
 *    number this can ever print is 2. A singular string would be unreachable
 *    code that a future reader would mistake for a supported state. If the
 *    buffer ever drops to 0, "Only 1 left for the school visit" is still
 *    grammatical, which is why this is safe rather than merely convenient.
 *
 * ⛔ §9.1 VOICE: no "we"/"us"/"our". ⛔ NO EM DASH. ⛔ AMERICAN SPELLING.
 * ⛔ NO RESTOCK DATE AND NO RESTOCK PROMISE, for the same reason as the
 *    sold-out strings: his restock has already slipped once.
 * ====================================================================== */

/**
 * The per-title counter sentence.
 *
 * @param int $count Live copies remaining. Always >= 2 in practice.
 * @return string
 */
function bhp_visit_shelf_counter_label( $count ) {
	$count = (int) $count;

	$label = sprintf(
		/* translators: %d: copies of this title left on the shelf for the school visit. */
		__( 'Only %d left for the school visit', 'brave-hearts' ),
		$count
	);

	/**
	 * The per-title counter sentence.
	 *
	 * ⭐ ANDREW'S ONE-LINE ALTERNATES, should he prefer either:
	 *    plainest      add_filter( 'bhp_visit_shelf_counter_label',
	 *                    fn( $l, $n ) => sprintf( '%d left for the school visit', $n ), 10, 2 );
	 *    I-voice       add_filter( 'bhp_visit_shelf_counter_label',
	 *                    fn( $l, $n ) => sprintf( 'I have %d left for the school visit', $n ), 10, 2 );
	 *
	 * @param string $label The sentence.
	 * @param int    $count Live copies remaining.
	 */
	return (string) apply_filters( 'bhp_visit_shelf_counter_label', $label, $count );
}

/**
 * The counter sentence WITH the title named.
 *
 * Used only by the three-book box when that counter is switched on, where the
 * bare sentence would not say which of the three books it counts.
 *
 * @param string $slug  Title slug.
 * @param int    $count Live copies remaining.
 * @return string
 */
function bhp_visit_shelf_counter_label_named( $slug, $count ) {
	$count = (int) $count;
	$slug  = sanitize_key( (string) $slug );
	$title = $slug;

	if ( function_exists( 'bhp_bundle_catalog' ) ) {
		try {
			$catalog = bhp_bundle_catalog();
			if ( isset( $catalog['paperback'][ $slug ]['label'] ) ) {
				$title = (string) $catalog['paperback'][ $slug ]['label'];
			}
		} catch ( Throwable $e ) {
			$title = $slug; // FAIL SOFT: the slug is ugly but it is not wrong.
		}
	}

	/*
	 * ⚠️ THE SERIES PREFIX COMES OFF, AND THAT IS AN OBSERVED FIX, NOT A
	 *    PREFERENCE. The catalog label is the full retail name
	 *    ("Adventures of Charlotte and Henry: The Mariana Trench"), which is
	 *    correct in a product row and unreadable in a sentence: the first draft
	 *    of this string rendered, on staging, as
	 *      "Only 4 Adventures of Charlotte and Henry: The Mariana Trench left
	 *       for the school visit"
	 *    ⛔ The catalog is NOT edited to fix this. That label is shared with the
	 *    purchase rows, the cart and the order, and shortening it there to suit
	 *    one sentence would change three surfaces to fix one.
	 */
	if ( false !== strpos( $title, ': ' ) ) {
		$parts = explode( ': ', $title );
		$title = trim( (string) array_pop( $parts ) );
	}

	$label = sprintf(
		/* translators: 1: number of copies left, 2: book title. */
		__( 'Only %1$d copies of %2$s left for the school visit', 'brave-hearts' ),
		$count,
		$title
	);

	/**
	 * The counter sentence with the title named.
	 *
	 * @param string $label The sentence.
	 * @param string $slug  Title slug.
	 * @param int    $count Live copies remaining.
	 * @param string $title The catalog label used.
	 */
	return (string) apply_filters( 'bhp_visit_shelf_counter_label_named', $label, $slug, $count, $title );
}

/**
 * ⭐ THE ONE OWNER OF THE COUNTER MARKUP.
 *
 * ⛔ THE CLASS NAME EXISTS IN EXACTLY ONE PLACE IN PHP, and the suite asserts
 *    that. Three surfaces echoing their own `<span class="...">` is how a
 *    fourth surface eventually grows a copy that forgets the visit gate.
 *
 * ⛔ IT PRINTS NOTHING, AND EMITS NO ELEMENT AT ALL, unless
 *    `bhp_visit_shelf_counter_for_request()` returns a number. Not an empty
 *    span, not a hidden one: an ordinary shopper's HTML must contain no trace.
 *
 * @param string $slug Title slug.
 * @return void
 */
function bhp_visit_shelf_render_counter( $slug ) {
	$n = bhp_visit_shelf_counter_for_request( $slug );

	if ( null !== $n ) {
		printf(
			'<span class="bhp-bundle-stock-counter">%s</span>',
			esc_html( bhp_visit_shelf_counter_label( (int) $n ) )
		);
		return;
	}

	/*
	 * ⭐⭐ 1.8.76 — THE BACKORDER FALL-THROUGH, AND IT IS PUT HERE ON PURPOSE.
	 *
	 * All three purchase surfaces already share the identical shape:
	 *      if ( $is_closed )  -> sold-out label
	 *      elseif ( ... )     -> bhp_visit_shelf_render_counter( $slug )
	 * With backorders on, `$is_closed` is false for an exhausted title, so
	 * every one of those surfaces lands in THIS function. Wiring the new line
	 * here rather than editing `bundle-shop-series.php`, `bundle-shortcode.php`
	 * (twice) and the WooCommerce loop gives all four ONE implementation and
	 * makes it impossible for a fifth surface to grow a copy that forgets the
	 * visit gate. Exactly the reason this function owns the counter markup.
	 *
	 * ⛔ IT PRINTS NOTHING IN THE ORDINARY CASE. `_is_backordered_for_request()`
	 *    is false for every unflagged session, false with no shelf baseline,
	 *    false when the shelf is fine, and false when backorders are switched
	 *    off — so an ordinary shopper's HTML is byte-identical to 1.8.75.
	 *
	 * ⛔ IT CAN NEVER PRINT ALONGSIDE THE COUNTER: the counter branch above
	 *    returns. It can never print alongside the sold-out label either, since
	 *    that branch is `$is_closed`, which is exactly the negation of the
	 *    backorder allowance for an exhausted title.
	 */
	if ( function_exists( 'bhp_visit_shelf_render_backorder_line' ) ) {
		bhp_visit_shelf_render_backorder_line( $slug );
	}
}

/* =========================================================================
 * ⭐⭐ 1.8.73 — THE WOOCOMMERCE PRODUCT-LOOP SURFACE
 *     (`CYCLE166-LD-AUTHOR-VISITS-SHELF-UI`)
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHY THIS EXISTS, AND WHY IT IS NOT ON `page-author-visits.php`
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The dispatch that produced this section asked for the 1.8.72 presentation
 * to be wired into `/author-visits/` (theme `page-author-visits.php`), on the
 * stated premise that it is "the page school parents actually order from".
 *
 * ⛔ THAT PREMISE WAS DISPROVEN BY LIVE READ-ONLY VERIFICATION OF PRODUCTION
 *    ON 2026-08-24, and the finding is recorded here rather than in a report
 *    alone, because the next reader will otherwise re-derive it:
 *
 *      · `/author-visits/` renders NO product, NO price, NO per-title row and
 *        NO add-to-cart control. It is a DIRECTORY of visits. Its own file
 *        header forbids stock: "NO PRICE, NO STOCK, NO PRODUCT AND NO COUPON
 *        IS NAMED."  There is nothing on it for a per-title counter to attach
 *        to without inventing a product list the page does not have.
 *      · Its buttons route OUT to `/shop/?bhp_visit=<slug>` — verified live:
 *        `bhp_author_visits_rows()` returns exactly that URL for all three
 *        open visits. `/shop/` is where the add-to-cart controls are.
 *      · A parent arriving from the printed flyer QR reaches `/author-visits/`
 *        with NO `bhp_visit` argument, therefore NO session flag, therefore
 *        `bhp_visit_shelf_counter_map_for_request()` is EMPTY for them by
 *        design. A counter placed on that page would render nothing for the
 *        very audience it was meant to serve.
 *
 * ⭐ THE ACTUAL DEFECT, VERIFIED LIVE AND STATED PLAINLY: the 1.8.72
 *    presentation was wired into `[bhp_shop_the_series]` (bundle-shop-series)
 *    and `[bhp_bundle_offers]` (bundle-shortcode). ⛔ NEITHER SHORTCODE
 *    APPEARS ON ANY PRODUCTION PAGE, IN ANY POST STATUS. Both host pages
 *    (`/shop-the-series/` 359, `/book-bundles/` 356) exist ONLY ON STAGING.
 *    So on production the counter has never been reachable by anybody — not
 *    because of `/author-visits/`, but because its only two host pages were
 *    never created there. That is a staging-to-production CONTENT divergence,
 *    not a code defect, and resolving it is Andrew's (create the pages, or
 *    accept this surface instead). ⚠️ RECORDED, NOT RESOLVED.
 *
 * ⭐ SO THE COUNTER IS PUT WHERE THE PRODUCTION PARENT ACTUALLY IS: the
 *    WooCommerce product loop, which is what `/shop/` renders and what the
 *    visit buttons actually open.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ IT ADDS NO ARITHMETIC AND NO NEW WORDS. NOT ONE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Every number comes from `bhp_visit_shelf_counter_for_request()` and every
 * string from `bhp_visit_shelf_counter_label()` / `_sold_out_label()` /
 * `_sold_out_message()` — the same functions the 1.8.71/1.8.72 surfaces call.
 * There is no second copy of the ceiling, the buffer, the subtraction or the
 * copy anywhere in this section, which is the property that stops this
 * surface from ever disagreeing with the seams that refuse the add.
 *
 * ⛔ INERT FOR EVERY ORDINARY SHOPPER, ON EVERY ENVIRONMENT. Both entry
 *    points below go through the `_for_request` gates, which return
 *    null/empty unless `bhp_school_visit_paperback_only()` is true. An
 *    unflagged shopper's archive HTML contains no counter element, no
 *    sold-out element and no added class — not a hidden one, not an empty
 *    one. The suite asserts the byte-cleanliness rather than assuming it.
 * ====================================================================== */

/**
 * Resolve the loop's current product to a chapter-paperback title slug.
 *
 * ⛔ RETURNS null FOR EVERYTHING THAT IS NOT A CHAPTER PAPERBACK — the
 *    coloring book, the hardcovers, the activity book, any future product.
 *    `bhp_visit_shelf_identify_title()` is the single owner of that mapping
 *    and is asked rather than re-implemented.
 *
 * @param WC_Product|null $product The loop product.
 * @return string|null Title slug, or null.
 */
function bhp_visit_shelf_loop_title_slug( $product = null ) {
	if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
		return null;
	}
	if ( ! function_exists( 'bhp_visit_shelf_identify_title' ) ) {
		return null;
	}

	try {
		return bhp_visit_shelf_identify_title( (int) $product->get_id(), 0 );
	} catch ( Throwable $e ) {
		return null; // FAIL OPEN: an unrecognised card is an ordinary card.
	}
}

/**
 * ⭐ THE COUNTER / SOLD-OUT LINE UNDER A PRODUCT CARD'S TITLE.
 *
 * ⛔ CLOSED OUTRANKS COUNTED, exactly as it does on the other two surfaces:
 *    the sold-out branch is tested FIRST and returns, so a title can never
 *    print "Only 2 left" beside a sold-out badge.
 *
 * @return void
 */
function bhp_visit_shelf_loop_stock_line() {
	global $product;

	$slug = bhp_visit_shelf_loop_title_slug( $product );
	if ( null === $slug ) {
		return;
	}

	// ⭐ Both gates are visit-mode scoped, so an ordinary shopper exits here.
	$closed = function_exists( 'bhp_visit_shelf_closed_map_for_request' )
		? bhp_visit_shelf_closed_map_for_request()
		: array();

	if ( isset( $closed[ $slug ] ) ) {
		printf(
			'<span class="bhp-bundle-sold-out-label">%s</span>',
			esc_html( bhp_visit_shelf_sold_out_label() )
		);
		return;
	}

	if ( function_exists( 'bhp_visit_shelf_render_counter' ) ) {
		bhp_visit_shelf_render_counter( $slug ); // Prints nothing outside 2..10.
	}
}
add_action( 'woocommerce_after_shop_loop_item_title', 'bhp_visit_shelf_loop_stock_line', 13 );

/**
 * ⭐ REPLACE THE ADD-TO-CART CONTROL OF A CLOSED TITLE ON A PRODUCT LOOP.
 *
 * ⛔ IT IS A <span>, NOT A DISABLED <a>, and that mirrors the decision already
 *    taken and documented in `page-author-visits.php` for the closed visit
 *    row: a `<span>` is not focusable, carries no href a browser could
 *    follow, cannot be middle-clicked into a new tab and cannot be copied as
 *    a link address.
 *
 * ♿ `role="link"` + `aria-disabled="true"` is the accessible-disabled-control
 *    pattern; the absence of `tabindex` keeps it out of the tab order. The
 *    full sentence rides along in `.screen-reader-text` because the short
 *    label alone is terse out of visual context.
 *
 * ⛔ THIS IS PRESENTATION ONLY AND IS NOT THE ENFORCEMENT. The five
 *    server-side refusal seams in `school-visit-paperback-only.php` and this
 *    file's `bhp_visit_shelf_is_closed_item()` remain the control. Removing
 *    a button never stops a crafted `?add-to-cart=` and was never asked to.
 *
 * @param string          $html    The add-to-cart markup WooCommerce built.
 * @param WC_Product|null $product The loop product.
 * @return string
 */
function bhp_visit_shelf_loop_add_to_cart_link( $html, $product = null ) {
	$slug = bhp_visit_shelf_loop_title_slug( $product );
	if ( null === $slug ) {
		return $html;
	}

	$closed = function_exists( 'bhp_visit_shelf_closed_map_for_request' )
		? bhp_visit_shelf_closed_map_for_request()
		: array();

	if ( ! isset( $closed[ $slug ] ) ) {
		return $html; // ⭐ BYTE-IDENTICAL for every ordinary shopper.
	}

	return sprintf(
		'<span class="button bhp-bundle-sold-out-button" role="link" aria-disabled="true">%1$s</span><span class="screen-reader-text">%2$s</span>',
		esc_html( bhp_visit_shelf_sold_out_label() ),
		esc_html( bhp_visit_shelf_sold_out_message() )
	);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'bhp_visit_shelf_loop_add_to_cart_link', 20, 2 );
