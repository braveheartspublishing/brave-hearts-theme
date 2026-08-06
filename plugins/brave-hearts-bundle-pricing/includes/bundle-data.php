<?php
/**
 * Brave Hearts Bundle Pricing — product catalog + eligibility math.
 *
 * Pure data and detection helpers. Nothing in this file touches
 * WordPress/WooCommerce hooks, cart totals, or output — that keeps the
 * eligibility rules (Phase 4 of the approved pricing decision) readable
 * and checkable on their own, separate from how they get applied.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The six approved catalog editions. Keys are internal "title slugs" —
 * never shown to the customer, only used to detect which cart items belong
 * to which format/title combination.
 *
 * variation_id is 0 for simple products. Mariana Trench paperback is the
 * one variable product in the catalog (product 333, variation 334).
 */
function bhp_bundle_catalog() {
	return array(
		'paperback' => array(
			'mariana' => array(
				'product_id'   => 333,
				'variation_id' => 334,
				'isbn'         => '9798234014016',
				'label'        => 'Adventures of Charlotte and Henry: The Mariana Trench',
			),
			'everest' => array(
				'product_id'   => 15,
				'variation_id' => 0,
				'isbn'         => '9798234055873',
				'label'        => 'Adventures of Charlotte and Henry: Mount Everest',
			),
			'amazon' => array(
				'product_id'   => 18,
				'variation_id' => 0,
				'isbn'         => '9798996810802',
				'label'        => 'Adventures of Charlotte and Henry: The Amazon',
			),
		),
		'hardcover' => array(
			'mariana' => array(
				'product_id'   => 14,
				'variation_id' => 0,
				'isbn'         => '9798996810819',
				'label'        => 'Adventures of Charlotte and Henry: The Mariana Trench',
			),
			'everest' => array(
				'product_id'   => 17,
				'variation_id' => 0,
				'isbn'         => '9798996810826',
				'label'        => 'Adventures of Charlotte and Henry: Mount Everest',
			),
			'amazon' => array(
				'product_id'   => 20,
				'variation_id' => 0,
				'isbn'         => '9798996810833',
				'label'        => 'Adventures of Charlotte and Henry: The Amazon',
			),
		),
	);
}

/**
 * Approved permanent individual price per format. Used only as a sanity
 * check before trusting the fixed-dollar bundle discount table below —
 * WooCommerce's own product price is always the real source of truth for
 * what the customer is charged per line item.
 */
function bhp_bundle_expected_price( $format ) {
	return 'paperback' === $format ? 11.99 : 17.99;
}

/**
 * The format the Complete Collection landing page presents first when the
 * visitor has not chosen one (2026-07-30, Andrew's explicit decision).
 *
 * Hardcover: $48.99 collection (3 x $17.99 = $53.97, less the existing $4.98
 * bundle discount), $4.99 flat shipping. Both figures are the pre-existing
 * commerce model -- nothing about pricing, discounts, shipping rules, taxes,
 * product IDs or Bookvault behavior is changed by defaulting to it. Only the
 * initially-selected control changes.
 *
 * Single source of truth: the format selector, the pricing panel and the final
 * CTA panel all read this, so the default can never drift apart between them.
 *
 * @return string 'paperback'|'hardcover'
 */
function bhp_bundle_default_format() {
	return 'hardcover';
}

/**
 * C1 (2026-08-03) — THE TWO FORMATS, DEFAULT FIRST.
 *
 * The 2D flag, verbatim: "Collection page format pills: paperback still listed
 * left/first while page defaults hardcover — one function call for consistency".
 *
 * ⭐ THE DEFECT IT FIXES IS A REAL ONE, not a tidiness point. Since 2026-07-30
 *    the Collection page has opened on HARDCOVER (`bhp_bundle_default_format()`),
 *    but its format pills were rendered from a literal
 *    `array( 'paperback', 'hardcover' )`, so the SELECTED pill sat on the right
 *    and an unselected pill sat on the left, first in reading order and first in
 *    tab order. The page therefore told a visitor "paperback" with its layout
 *    and "hardcover" with its state, on the highest-value commerce page on the
 *    site.
 *
 * ⛔ THIS CHANGES ORDER ONLY. It is derived from `bhp_bundle_default_format()`,
 *    which is unchanged, so there is exactly one source of truth for "which
 *    format leads" and the pills, the panels and the final CTA can no longer
 *    disagree with each other or with the selection. No price, discount,
 *    shipping tier, product id, catalog entry or default SELECTION is touched
 *    by this function — the same two formats come back, in a different order.
 *
 * @return string[] Both formats, the default one first.
 */
function bhp_bundle_format_order() {
	$default = bhp_bundle_default_format();
	$other   = 'hardcover' === $default ? 'paperback' : 'hardcover';
	return array( $default, $other );
}

/**
 * Approved single-item (non-bundle) shipping amount per format.
 */
function bhp_bundle_single_shipping( $format ) {
	return 'paperback' === $format ? 1.99 : 2.99;
}

/**
 * Fixed-dollar bundle rules, keyed by qualifying tier (2 or 3 distinct
 * titles). Every number here is the literal approved amount — never a
 * computed percentage, never derived from anything other than this table.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.23 (2026-08-04) — COMPLETE COLLECTIONS SHIP FREE. "OPTION B."
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE OWNER RULING, as relayed to this session: **"Option B approved,
 *    CPA table adjusted."** — Andrew Signore, 2026-08-04, witnessed by the
 *    main session, recorded at `Business OS\WORKING-DRAFTS\chief-of-staff\
 *    OVERNIGHT-EXECUTION-REGISTER-2026-08-04.md` MESSAGE 39.
 *    ⚠ RELAYED, not witnessed here.
 *
 * ⛔ EXACTLY ONE NUMBER MOVED PER FORMAT, and it is the tier-3 SHIPPING
 *    figure: paperback $3.99 → **$0.00**, hardcover $4.99 → **$0.00**.
 *
 * ✅ WHAT DID NOT MOVE, deliberately and verifiably:
 *      · the tier-3 DISCOUNTS — still −$3.98 / −$4.98;
 *      · the collection prices they produce — still $31.99 / $48.99;
 *      · every tier-2 figure — −$1.99/$2.99 and −$2.99/$3.99;
 *      · both single-book rates — $1.99 / $2.99
 *        (`bhp_bundle_single_shipping()`, untouched);
 *      · the "Save $X" badge strings, which describe the DISCOUNT and
 *        would become false if they were repointed at shipping.
 *    Structure B in the economics sheet is defined as "absorb in full at
 *    unchanged pricing", so a price change here would be a different
 *    decision wearing this one's name.
 *
 * ⛔ THE WOOCOMMERCE ZONE IS NOT TOUCHED, on any environment. The single
 *    `flat_rate` instance is still configured at $3.99;
 *    `bhp_bundle_override_shipping_cost()` rewrites the COST of the rate
 *    already shown, exactly as it has since the tiered table shipped.
 *    A $0.00 override is the same mechanism with a smaller number.
 *
 * ⚠ RENDERING: $0.00 is a price, "FREE" is the message. Every surface that
 *   prints this figure must go through `bhp_bundle_shipping_display()`
 *   below rather than `number_format()`-ing it directly.
 */
function bhp_bundle_rules( $format ) {
	if ( 'paperback' === $format ) {
		return array(
			2 => array(
				'discount' => 1.99,
				'shipping' => 2.99,
				'heading'  => 'Choose Any 2 Paperbacks',
				'save'     => 'Save $1.99',
			),
			3 => array(
				'discount' => 3.98,
				'shipping' => 0.00, // 1.8.23: was 3.99. Option B, Andrew 2026-08-04.
				'heading'  => 'Complete Paperback Collection',
				'save'     => 'Save $3.98',
			),
		);
	}
	return array(
		2 => array(
			'discount' => 2.99,
			'shipping' => 3.99,
			'heading'  => 'Choose Any 2 Hardcovers',
			'save'     => 'Save $2.99',
		),
		3 => array(
			'discount' => 4.98,
			'shipping' => 0.00, // 1.8.23: was 4.99. Option B, Andrew 2026-08-04.
			'heading'  => 'Complete Hardcover Collection',
			'save'     => 'Save $4.98',
		),
	);
}

/**
 * How a shipping amount is WRITTEN to a customer.
 *
 * ⭐ ONE FUNCTION, SO "FREE" CANNOT BE SPELLED TWO WAYS. Before 1.8.23 every
 *    surface did its own `'$' . number_format( $rule['shipping'], 2 )`, which
 *    was harmless while every tier was positive and becomes "$0.00 flat" —
 *    technically true, commercially useless — the moment one is zero.
 *
 * ⛔ THE ZERO TEST IS A TOLERANCE, NOT `=== 0.0`. These figures pass through
 *    float arithmetic in the dashboard's economics model before they reach a
 *    label; a strict comparison would silently start printing "$0.00" again
 *    after some future rounding. Half a cent is far below any real rate.
 *
 * ⭐ TWO CONTEXTS, BECAUSE THE WORD "SHIPPING" IS ALREADY ON SCREEN IN ONE
 *    OF THEM. A definition-list row already carries a `Shipping` label, so
 *    its value is `FREE` / `$4.99 flat`; a running sentence has no such
 *    label, so it reads `FREE shipping` / `$4.99 flat shipping`. Both are
 *    produced here rather than by a caller appending a word, which is how
 *    "Shipping: FREE shipping" gets shipped.
 *
 * @param float  $amount  The shipping figure.
 * @param string $context 'row' for a labelled value, anything else for a
 *                        standalone phrase.
 * @return string Customer-facing text, already plain (callers escape).
 */
function bhp_bundle_shipping_display( $amount, $context = 'inline' ) {
	$amount = (float) $amount;
	$is_row = ( 'row' === $context );

	if ( $amount < 0.005 ) {
		$text = $is_row ? 'FREE' : 'FREE shipping';
	} else {
		$text = '$' . number_format( $amount, 2 ) . ( $is_row ? ' flat' : ' flat shipping' );
	}

	/**
	 * Swap the rendered shipping wording without touching a template.
	 *
	 * @param string $text    The rendered wording.
	 * @param float  $amount  The shipping figure it describes.
	 * @param string $context Caller hint.
	 */
	return apply_filters( 'bhp_bundle_shipping_display', $text, $amount, $context );
}

/**
 * True when this shipping figure is a free one. Kept next to the label so
 * the two can never disagree about where the threshold sits.
 */
function bhp_bundle_shipping_is_free( $amount ) {
	return (float) $amount < 0.005;
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.23 — THE TWO FREE-SHIPPING LINES. **BUILT TO BE SWAPPED.**
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⚠ COPY STATUS: DRAFTED FROM ANDREW'S STATED INTENT, **PENDING MERRY
 *   (`marketing-growth`) REVIEW.** His phrasing intent, relayed: *"free
 *   shipping with the purchase of this book."* These are this build's
 *   rendering of that intent, not his verbatim words, and they are
 *   deliberately reachable from a one-line filter so review can change them
 *   without a code change of substance:
 *
 *       add_filter( 'bhp_bundle_freeship_copy', function ( $copy ) {
 *           $copy['nudge'] = 'New approved wording.';
 *           return $copy;
 *       } );
 *
 * ⛔ WHAT THE WORDING IS CONSTRAINED BY, and every constraint is met:
 *      · TRUTHFUL AT THE MOMENT IT RENDERS. `nudge` only ever prints on a
 *        cart holding exactly TWO distinct adventures, where adding a third
 *        genuinely takes shipping to $0.00. `earned` only prints once the
 *        collection is actually complete and the override is actually
 *        running (no unrelated item present).
 *      · NO EM-DASH.
 *      · NO FALSE URGENCY. No countdown, no "today only", no "hurry", no
 *        scarcity. Nothing here expires, and the copy does not pretend it
 *        does.
 *      · NO NUMBER IN THE STRING. The nudge deliberately does not quote a
 *        dollar figure, so it cannot drift out of step with the tier table
 *        the way a hardcoded "$2.99" would.
 *
 * ⭐ FORMAT-NEUTRAL ON PURPOSE. Free shipping is earned on three distinct
 *    ADVENTURES in any format combination, so a paperback-specific and a
 *    hardcover-specific variant would be two strings describing one rule,
 *    and a mixed cart would match neither.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.24 (2026-08-05) — `cta_clause` ADDED. CYCLE144-LD-14.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (relayed through the Chief of Staff;
 * NOT witnessed first-hand by this agent):
 *
 *   "On the checkout when you have two books - It still says 'Add this
 *    adventure save $1.99' supposed to say the Free Shipping info- Same
 *    issue for hardcovers."
 *
 * ⭐ WHAT HE WAS LOOKING AT, IDENTIFIED BY ARITHMETIC RATHER THAN GUESSED.
 *    The string is the CROSS-SELL BUTTON's savings clause (B4), which the
 *    cart drawer and the checkout panel both assemble from the shared
 *    `crossSellSavings()`. On a 2-distinct-title cart it computes the DELTA
 *    between the 3-book and 2-book discounts from `bhp_bundle_rules()`:
 *
 *        paperback   $3.98 − $1.99 = $1.99
 *        hardcover   $4.98 − $2.99 = $1.99
 *
 *    Both formats produce exactly $1.99, which is why Andrew saw the same
 *    wrong-feeling number twice and wrote "Same issue for hardcovers".
 *
 * ⭐ WHY IT NEEDED CHANGING AND NOT JUST RE-ORDERING. Since 1.8.23 the third
 *    adventure also takes shipping to $0.00, so at that exact moment the
 *    button was advertising the SMALLER half of the offer: $1.99 of discount,
 *    while silently omitting $2.99 (paperback) or $3.99 (hardcover) of
 *    shipping. `cta_clause` replaces the savings clause on that one
 *    transition. Everywhere else the savings clause is untouched.
 *
 * ⛔ THE CLAUSE QUOTES NO FIGURE, for the same reason `nudge` does not: the
 *    shipping saved differs by format and by cart, so any single number
 *    would be wrong somewhere. "Ships Free" is true in every case that can
 *    reach it, because the only case that can reach it is the one where
 *    `bhp_bundle_shipping_amount()` returns 0.00.
 *
 * ⛔ THE $1.99 DISCOUNT IS NOT HIDDEN OR DENIED. It is still applied, still
 *    computed by the same code, and still rendered on the invoice as the
 *    Bundle Savings fee line; the drawer's own "Add the final adventure to
 *    complete the collection and save $3.98 total." message is untouched.
 *    This changes which of two true facts LEADS on one button.
 *
 * @return array{nudge:string,earned:string,cta_clause:string}
 */
function bhp_bundle_freeship_copy() {
	$copy = array(
		'nudge'  => 'Add the final adventure and your order ships free.',
		'earned' => 'Your complete collection ships free.',
		/*
		 * Appended to "Add This Adventure" exactly where " - Save $X.XX"
		 * would otherwise go, so the button's shape, hyphen convention (B4:
		 * a HYPHEN, never an em dash) and geometry are unchanged.
		 */
		'cta_clause' => ' - Ships Free',
	);

	/**
	 * Swap the free-shipping cart copy.
	 *
	 * @param array $copy 'nudge' (two adventures in cart), 'earned'
	 *                    (collection complete) and 'cta_clause' (the
	 *                    cross-sell button's clause when the offered title
	 *                    is the one that earns free shipping).
	 */
	return apply_filters( 'bhp_bundle_freeship_copy', $copy );
}

/**
 * Identify which catalog title (if any) a cart line item represents.
 *
 * @param int $product_id
 * @param int $variation_id
 * @return array{0:string,1:string}|null [format, title_key], or null if the
 *   item is not one of the six approved editions.
 */
function bhp_bundle_identify_cart_item( $product_id, $variation_id ) {
	$catalog     = bhp_bundle_catalog();
	$cart_key_id = $variation_id ? (int) $variation_id : (int) $product_id;

	foreach ( $catalog as $format => $titles ) {
		foreach ( $titles as $title_key => $info ) {
			$catalog_key_id = $info['variation_id'] ? (int) $info['variation_id'] : (int) $info['product_id'];
			if ( $catalog_key_id === $cart_key_id ) {
				return array( $format, $title_key );
			}
		}
	}
	return null;
}

/**
 * Scan the cart and report, per format, which distinct approved titles are
 * present. Extra quantity of a title already present does not add a second
 * "distinct title" — the Phase 4 rule is explicit that two copies of the
 * same title never qualify as a 2-book bundle.
 *
 * @param WC_Cart|null $cart
 * @return array{paperback: string[], hardcover: string[]}
 */
function bhp_bundle_distinct_titles_in_cart( $cart ) {
	$present = array(
		'paperback' => array(),
		'hardcover' => array(),
	);

	if ( ! $cart ) {
		return $present;
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		$match = bhp_bundle_identify_cart_item( $cart_item['product_id'], $cart_item['variation_id'] );
		if ( null === $match ) {
			continue;
		}
		list( $format, $title_key ) = $match;
		if ( ! in_array( $title_key, $present[ $format ], true ) ) {
			$present[ $format ][] = $title_key;
		}
	}

	return $present;
}

/**
 * The distinct ADVENTURES in the cart, counted across both formats at once.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.23 — this is what "a complete collection" means for FREE SHIPPING,
 *    and it is deliberately a different question from the tier tables.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `bhp_bundle_distinct_titles_in_cart()` answers "which titles of THIS
 * format are present", because a DISCOUNT is a per-format offer
 * ("Complete Paperback Collection", −$3.98). Free shipping was ruled on the
 * COLLECTION, and a customer holding Mariana in paperback, Everest in
 * paperback and Amazon in hardcover has the collection — three adventures,
 * one shipment. Gandalf's direction for this build, verbatim: *"mixed
 * 3-distinct-book carts ALSO ship free (same bundle family)"*.
 *
 * ⛔ IT IS A UNION OF TITLES, NEVER A COUNT OF BOOKS, and that is the whole
 *    coherence argument. `bundle-data.php` has said since Phase 4 that
 *    "two copies of the same title never qualify" — so Mariana paperback +
 *    Mariana hardcover + Everest paperback is THREE BOOKS but TWO
 *    ADVENTURES, is not a collection, and keeps the $4.99 mixed rate. If
 *    this counted books instead, the store would be giving free shipping
 *    for buying the same story twice, which contradicts the rule the entire
 *    tier table is built on.
 *
 * ⭐ It subsumes the pure sets rather than competing with them: three
 *    distinct paperbacks are also three distinct adventures, so the
 *    `bhp_bundle_rules()[3]['shipping'] = 0.00` path and this path agree by
 *    construction and cannot drift into two different answers.
 *
 * @param WC_Cart|null $cart
 * @return string[] Distinct title keys ('mariana','everest','amazon').
 */
function bhp_bundle_distinct_adventures_in_cart( $cart ) {
	$distinct   = bhp_bundle_distinct_titles_in_cart( $cart );
	$adventures = array();
	foreach ( array( 'paperback', 'hardcover' ) as $format ) {
		foreach ( $distinct[ $format ] as $title_key ) {
			if ( ! in_array( $title_key, $adventures, true ) ) {
				$adventures[] = $title_key;
			}
		}
	}
	return $adventures;
}

/**
 * Total quantity of approved-catalog line items in the cart, across both
 * formats. Used only for the mixed-format shipping tiers (Staging
 * Refinement Phase 1, item 5) — a mixed cart never qualifies for a
 * distinct-title bundle discount, so shipping there is priced by raw book
 * count instead of by tier.
 */
function bhp_bundle_total_quantity_in_cart( $cart ) {
	$total = 0;
	if ( ! $cart ) {
		return $total;
	}
	foreach ( $cart->get_cart() as $cart_item ) {
		if ( null === bhp_bundle_identify_cart_item( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
			continue;
		}
		$total += (int) $cart_item['quantity'];
	}
	return $total;
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE ADD-ON ALLOWLIST — the single most load-bearing part of the
 *    activity-book order bump, and the reason it does not break commerce.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `bhp_bundle_cart_has_unrelated_items()` below is a HARD FAIL-SAFE: the
 * instant it sees anything outside the six approved editions it makes
 * `has_unrelated` true, and two things then silently stop happening.
 *
 *   1. `bhp_bundle_override_shipping_cost()` returns the rates untouched,
 *      so the customer falls back to the zone's raw $3.99 flat rate
 *      instead of the approved $1.99/$2.99/$3.99/$4.99 tier.
 *   2. `bhp_audience_coupon_cart_qualifies()` returns false, so an
 *      audience coupon is refused on a cart that is a genuine Complete
 *      Collection.
 *
 * That fail-safe is exactly right for a genuinely unknown product. It is
 * exactly WRONG for a deliberate, zero-shipping digital add-on that the
 * store itself offers on the cart and checkout: a $5 PDF must not be able
 * to raise a one-paperback customer's shipping from $1.99 to $3.99, and
 * must not be able to invalidate a coupon.
 *
 * ⛔ THE EXEMPTION IS AN ALLOWLIST, NEVER A CATEGORY TEST. It is not
 *    "any virtual product" or "anything downloadable" — a future plugin
 *    or a mis-configured product could satisfy either of those by
 *    accident and would then silently re-open the same defect from the
 *    other direction. Only a product whose SKU is on the explicit list
 *    below is exempt.
 *
 * ⭐ SKU, NOT A HARDCODED ID, and that is deliberate. Product IDs differ
 *    between staging and production (they already do for every other
 *    record in this store). A hardcoded staging ID would either do
 *    nothing on production or, far worse, match a DIFFERENT product
 *    there. The SKU is authored once and travels.
 *
 * ✅ FAILS CLOSED. If the SKU resolves to nothing — which is exactly the
 *    state of production until Andrew approves the live product — every
 *    function here returns an empty list and `has_unrelated` behaves
 *    byte-for-byte as it did before this file was touched.
 */
function bhp_bundle_addon_skus() {
	/**
	 * The digital add-ons allowed to coexist with bundle pricing.
	 *
	 * @param string[] $skus Product SKUs exempt from the has_unrelated fail-safe.
	 */
	return apply_filters( 'bhp_bundle_addon_skus', array( 'BHP-ACTIVITY-BOOK-01' ) );
}

/**
 * Resolve the allowlisted SKUs to live product IDs.
 *
 * Cached in a request-scoped static because `has_unrelated` is evaluated
 * several times per cart calculation (shipping override, coupon
 * qualification, fee calculation) and each miss would otherwise be a
 * fresh meta lookup.
 *
 * @return int[] Product IDs, possibly empty.
 */
function bhp_bundle_addon_product_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids = array();
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return $ids;
	}
	foreach ( bhp_bundle_addon_skus() as $sku ) {
		$id = (int) wc_get_product_id_by_sku( $sku );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/**
 * True if this cart line is an allowlisted digital add-on.
 *
 * Matches on the parent product id AND on the variation id, so a future
 * variable add-on cannot slip past by being added as a variation.
 */
function bhp_bundle_is_addon_item( $product_id, $variation_id = 0 ) {
	$ids = bhp_bundle_addon_product_ids();
	if ( empty( $ids ) ) {
		return false;
	}
	return in_array( (int) $product_id, $ids, true )
		|| ( $variation_id && in_array( (int) $variation_id, $ids, true ) );
}

/**
 * True if the cart contains anything that is NOT one of the six approved
 * editions and NOT an allowlisted digital add-on. The shipping override
 * refuses to run in this case — see bhp_bundle_override_shipping_cost() —
 * rather than guess a shipping amount for a cart the bundle system
 * doesn't fully understand.
 *
 * ⭐ The add-on is skipped, never counted. It is not added to
 *    `total_quantity` either (see bhp_bundle_total_quantity_in_cart(),
 *    which counts only identified catalog items), so a cart of two
 *    paperbacks plus the activity book is still a TWO-BOOK cart for every
 *    shipping and discount decision. A digital file has no weight and
 *    must not move a book-count tier.
 */
function bhp_bundle_cart_has_unrelated_items( $cart ) {
	if ( ! $cart ) {
		return false;
	}
	foreach ( $cart->get_cart() as $cart_item ) {
		if ( null !== bhp_bundle_identify_cart_item( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
			continue;
		}
		if ( bhp_bundle_is_addon_item( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
			continue;
		}
		return true;
	}
	return false;
}

/**
 * The single best-qualifying tier for one format: 3 if all three distinct
 * titles are present, 2 if exactly two are, otherwise 0. Deliberately
 * cannot return more than one tier at once — that is what guarantees the
 * two-book and three-book discounts can never both apply to the same
 * format in the same cart (Phase 9).
 */
function bhp_bundle_qualifying_tier( array $distinct_titles ) {
	$count = count( $distinct_titles );
	if ( $count >= 3 ) {
		return 3;
	}
	if ( 2 === $count ) {
		return 2;
	}
	return 0;
}

/**
 * Full bundle evaluation for the current cart: which tier (if any) applies
 * per format, whether each format is present at all, whether an unrelated
 * product is mixed in, and whether the cart mixes paperback + hardcover in
 * a way the approved shipping table does not cover.
 *
 * @param WC_Cart|null $cart
 * @return array
 */
function bhp_bundle_evaluate_cart( $cart ) {
	$distinct = bhp_bundle_distinct_titles_in_cart( $cart );

	$result = array(
		'paperback_tier'  => bhp_bundle_qualifying_tier( $distinct['paperback'] ),
		'hardcover_tier'  => bhp_bundle_qualifying_tier( $distinct['hardcover'] ),
		'has_paperback'   => count( $distinct['paperback'] ) > 0,
		'has_hardcover'   => count( $distinct['hardcover'] ) > 0,
		'has_unrelated'   => bhp_bundle_cart_has_unrelated_items( $cart ),
		'total_quantity'  => bhp_bundle_total_quantity_in_cart( $cart ),
		// 1.8.23: distinct ADVENTURES across both formats -- the free-shipping
		// test. See bhp_bundle_distinct_adventures_in_cart() for why this is
		// a union of titles and not a book count.
		'distinct_adventures' => count( bhp_bundle_distinct_adventures_in_cart( $cart ) ),
	);
	// 1.8.23: the cart holds the whole series in some combination of formats.
	// Stated once, here, so no surface has to re-derive it.
	$result['is_complete_collection'] = $result['distinct_adventures'] >= 3;
	// Mixed-format cart: paperback AND hardcover both present. The approved
	// shipping table (Phase 6) only defines paperback-only or
	// hardcover-only tiers, so this combination is intentionally left
	// unresolved here — see bhp_bundle_shipping_amount() for the fallback.
	$result['is_mixed_format'] = $result['has_paperback'] && $result['has_hardcover'];

	return $result;
}
