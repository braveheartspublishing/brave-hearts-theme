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
 * ⭐⭐ THE SITE-WIDE FIRST-SEEN FORMAT. 1.8.57 (2026-08-18) — IT IS PAPERBACK.
 *
 * Andrew Signore, 2026-08-18, verbatim (⛔ RELAYED through the Chief of Staff
 * in the `CYCLE164-LD-PAPERBACK-DEFAULT` brief; NOT witnessed first-hand by
 * the agent that made this change):
 *
 *   "yes, lets make it the paperbacks"
 *
 * ⭐ ONE LINE MOVES EVERY SURFACE, WHICH IS THE WHOLE POINT OF THIS FUNCTION
 *    EXISTING. Read by the homepage Best Value band, `/books/`, the four
 *    audience/funnel landing pages, the product-page collection cross-sell,
 *    the shop grid's collection card, the header collection CTA,
 *    `/book-bundles/` and `/shop-the-series/`. Before 2026-07-30 those were
 *    six hardcoded literals in six files; the reason they were consolidated
 *    here is precisely so a founder ruling like this one is a ONE-LINE change
 *    that cannot half-apply.
 *
 * ⚠ THIS IS A REAL COMMERCIAL MOVEMENT AND IT IS REPORTED AS ONE, NOT AS A
 *   NEUTRAL DISPLAY TWEAK: the first price a shopper meets on the homepage
 *   band and the funnel price cards goes from the $17.99/$48.99 hardcover
 *   figures to the $11.99/$31.99 paperback ones. HARDCOVER REMAINS ONE TAP
 *   AWAY on every one of those surfaces, fully in stock and fully
 *   purchasable. It is the founder's own instruction, applied as given.
 *
 * ⛔ NO PRICE, DISCOUNT, SHIPPING TIER, TAX, COUPON, STOCK STATUS, PRODUCT
 *    RECORD, VARIATION, SKU, BOOKVAULT MAPPING OR WOOCOMMERCE SETTING IS
 *    CHANGED BY THIS FUNCTION, on any environment. Every figure on every
 *    affected surface is still read at render time from WooCommerce or from
 *    the approved tables below, so the numbers FOLLOW the default rather than
 *    being restated beside it. Only which control starts selected moves.
 *
 * ⛔ SUPERSEDED, PRESERVED VERBATIM SO THE MOVEMENT STAYS VISIBLE RATHER THAN
 *    BEING RE-DERIVED. This function returned `'hardcover'` from 2026-07-30 to
 *    2026-08-18 under Andrew's decision of 2026-07-30, and its docblock read:
 *
 *      "The format the Complete Collection landing page presents first when
 *       the visitor has not chosen one (2026-07-30, Andrew's explicit
 *       decision).
 *
 *       Hardcover: $48.99 collection (3 x $17.99 = $53.97, less the existing
 *       $4.98 bundle discount), $4.99 flat shipping. Both figures are the
 *       pre-existing commerce model -- nothing about pricing, discounts,
 *       shipping rules, taxes, product IDs or Bookvault behavior is changed
 *       by defaulting to it. Only the initially-selected control changes."
 *
 *    ⚠ Note what the old docblock's FIRST LINE says and what is no longer
 *      true of it: the Complete Collection LANDING PAGE has had its own
 *      separate default since 2026-08-14 (`bhp_bundle_landing_default_format()`
 *      in bundle-landing-page.php, paperback), so that page is already
 *      paperback-first and is UNAFFECTED by this change. Confirmed by reading
 *      that function, not assumed.
 *
 * Single source of truth: the format selector, the pricing panel and the final
 * CTA panel all read this, so the default can never drift apart between them.
 *
 * @return string 'paperback'|'hardcover'
 */
function bhp_bundle_default_format() {
	return 'paperback';
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
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.66 — THE SINGLE COLOURING BOOK SHIPS AT $2.99, NOT $1.99.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER RULING, CARRIER ITEM 195, 2026-08-21 — relayed to this agent by
 *    `chief-of-staff` in the build brief. ⚠️ RELAYED, NOT WITNESSED FIRST-HAND
 *    HERE, and it is labelled that way rather than dressed up as a source read.
 *
 * ⭐ IT CLOSES A READING THAT 1.8.61 EXPLICITLY REFUSED TO SETTLE. That build
 *    wrote, in `bhp_bundle_shipping_amount()` branch C: "⚠️⚠️ THREE READINGS
 *    ARE FLAGGED RATHER THAN PRESENTED AS SETTLED... 1. A single colouring book
 *    ships at $1.99, like a single paperback, NOT at the hardcover $2.99.
 *    Basis: it is a paperback binding. ⛔ NONE of these is a founder ruling."
 *    ⭐ FLAGGED READING 1 IS NOW A FOUNDER RULING, AND HE WENT THE OTHER WAY.
 *
 * ⛔⛔ SCOPE — THE SINGLE COLOURING ROW AND NOTHING ELSE. Stated as a list of
 *    what did NOT move, because a shipping edit that quietly widens is the
 *    defect class this file keeps catching:
 *      · a single chapter PAPERBACK is still $1.99 (`bhp_bundle_single_shipping`
 *        above is byte-untouched, and a test asserts it in the same pass)
 *      · a single HARDCOVER is still $2.99
 *      · 1 chapter PB + 1 colouring is still $2.99
 *      · 2 copies of one colouring title is still $2.99
 *      · the COLLECTION tier is still $0.00
 *      · the `any-three` free-shipping rule is untouched — 3+ physical books
 *        still return $0.00 from branch A, before this figure is ever reached
 *      · no bundle DISCOUNT table, no product price, no coupon
 *
 * ⛔ IT IS A PLUGIN FIGURE, NOT A WOOCOMMERCE SETTING. No zone, no method, no
 *    `flat_rate` instance and no store option is touched on any environment by
 *    this constant. The zone base stays $3.99 and the override still only
 *    rewrites a cost the zone already produces.
 *
 * ⭐ WHY A FUNCTION OF ITS OWN RATHER THAN A LITERAL IN branch C: so the
 *    colouring ladder has ONE author, is filterable the way every other figure
 *    in this file is, and so a future reader grepping "2.99" does not have to
 *    guess whether a given occurrence is the hardcover single or this one.
 *
 * @return float The single-colouring-book shipping figure, in dollars.
 */
function bhp_colouring_single_shipping() {
	/**
	 * The shipping figure for a cart holding exactly one colouring-line book.
	 *
	 * ⭐ 1.8.66: $2.99 (founder carrier item 195). Was `bhp_bundle_single_
	 *    shipping('paperback')` = $1.99 from 1.8.61.
	 *
	 * @param float $amount Dollars.
	 */
	return (float) apply_filters( 'bhp_colouring_single_shipping', 2.99 );
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
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.80 — THE "SAVE $X" BADGE IS COMPUTED AT RENDER, FROM LIVE PRICES.
 *      `CYCLE179-LD-355`, brief item 7.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT, AND IT IS A CLAIM DEFECT RATHER THAN A LAYOUT ONE. The badge
 *    strings in `bhp_bundle_rules()` above are literals baked at build time,
 *    and they are printed on four customer-facing surfaces BEFORE ANY CART
 *    EXISTS. The cart, meanwhile, refuses the fixed-dollar discount entirely
 *    when a live line price no longer matches the approved individual price:
 *    `bhp_bundle_prices_match_expected()` in `bundle-cart.php` returns false
 *    and `bhp_bundle_apply_discount_fees()` does `continue`, logging *"Bundle
 *    discount skipped ... a cart item price does not match the expected"*.
 *
 * ⛔ SO ONE PRICE EDIT IN WOOCOMMERCE IS ENOUGH TO MAKE EVERY ONE OF THOSE FOUR
 *    SURFACES PROMISE A SAVING THE CHECKOUT WILL NOT GIVE. No code change and
 *    no deploy is required to trigger it. That is the same shape as the defect
 *    `bhp_bundle_apply_discount_fees()`'s own `has_unrelated` guard was written
 *    to pre-empt: a page promising something the cart then declines.
 *
 * ⭐⭐ THE RULE, IN ONE SENTENCE: print the saving only when the live prices
 *     still satisfy the condition the cart will apply the discount under, and
 *     otherwise print no saving at all.
 *
 * ⛔ IT FAILS CLOSED, AND SILENCE IS THE CORRECT FAILURE. A missing badge costs
 *    a line of persuasion. A badge the checkout contradicts costs trust and is
 *    an FTC-class claim. The same reasoning is already recorded on
 *    `bhp_bundle_landing_price_facts()` for the strikethrough anchor.
 *
 * ⛔ IT INVENTS NO NUMBER AND CHANGES NO APPROVED ONE. The amount it prints is
 *    the table's own `discount` for that tier, which is exactly what
 *    `bhp_bundle_apply_discount_fees()` subtracts. `bhp_bundle_rules()` is NOT
 *    modified: its comment says every number in it is the literal approved
 *    amount, and that stays true. This function decides whether that amount may
 *    be STATED today, not what it is.
 *
 * ⚠️ WHAT THE SAVING IS, STATED PRECISELY, BECAUSE THE WORD IS AMBIGUOUS AND
 *    THE AMBIGUITY HAS ALREADY PRODUCED TWO DIFFERENT FIGURES IN CIRCULATION:
 *
 *      · AGAINST THE SAME BOOKS BOUGHT SEPARATELY IN ONE CART, the saving is
 *        the tier's `discount` exactly. VERIFIED LIVE 2026-09-02 by WP-CLI on
 *        staging: all three paperbacks read 11.99, all three hardcovers 17.99,
 *        so any-2 paperback is 23.98 - 1.99 = 21.99. THAT IS WHAT THIS PRINTS.
 *      · AGAINST TWO SEPARATE SINGLE-BOOK ORDERS the shipping tier also moves,
 *        from 2 x 1.99 to 2.99, a further 0.99. ⛔ THIS FUNCTION DOES NOT ADD
 *        IT, because the badge sits beside a subtotal claim and folding a
 *        shipping delta into a "Save $X" on a product box would be the
 *        derived-claim trap: two true facts assembled into a third statement
 *        nobody approved.
 *
 * @since 1.8.80
 * @param string $format 'paperback'|'hardcover'.
 * @param int    $tier   2 or 3.
 * @return string 'Save $X.XX', or '' when no saving may honestly be stated.
 */
function bhp_bundle_saving_label( $format, $tier ) {
	$rules = bhp_bundle_rules( $format );
	$tier  = (int) $tier;

	if ( ! isset( $rules[ $tier ]['discount'] ) ) {
		return '';
	}

	$discount = (float) $rules[ $tier ]['discount'];
	if ( $discount <= 0 ) {
		return '';
	}

	$catalog = bhp_bundle_catalog();
	if ( ! isset( $catalog[ $format ] ) || ! function_exists( 'wc_get_product' ) ) {
		return ''; // No live prices readable: state nothing.
	}

	$expected = (float) bhp_bundle_expected_price( $format );
	$found    = 0;

	foreach ( $catalog[ $format ] as $info ) {
		$product = wc_get_product( (int) $info['product_id'] );
		if ( ! $product ) {
			return ''; // A title that cannot be read is a title that cannot be promised.
		}

		$price = (float) $product->get_price();

		/*
		 * ⭐ THE SAME TOLERANCE `bhp_bundle_shipping_display()` uses, and for
		 *    the same reason: these figures pass through float arithmetic
		 *    before they are compared, and a strict `===` would start failing
		 *    on a rounding artefact rather than on a real price change.
		 */
		if ( $price <= 0 || abs( $price - $expected ) >= 0.005 ) {
			return '';
		}

		$found++;
	}

	/*
	 * ⛔ A TIER NEEDS ITS OWN NUMBER OF QUALIFYING TITLES TO BE READABLE. Tier 2
	 *    is "any two of these", so two live titles is the floor; tier 3 is the
	 *    complete set and needs all three. Reading fewer than that and printing
	 *    the badge anyway would be asserting a set that is not there.
	 */
	if ( $found < $tier ) {
		return '';
	}

	$label = 'Save $' . number_format( $discount, 2 );

	/**
	 * The saving a bundle box may state today.
	 *
	 * ⛔ A SEAM FOR TESTS AND FOR A FOUNDER RULING, not a configuration point.
	 *    It changes what is PRINTED and nothing else: it opens no gate, changes
	 *    no price, applies no discount and moves no shipping amount.
	 *
	 * @since 1.8.80
	 * @param string $label    'Save $X.XX', or ''.
	 * @param string $format   'paperback'|'hardcover'.
	 * @param int    $tier     2 or 3.
	 * @param float  $discount The approved fixed-dollar discount for that tier.
	 */
	return (string) apply_filters( 'bhp_bundle_saving_label', $label, $format, $tier, $discount );
}

/**
 * The heading a bundle box prints, with its saving appended only when there is
 * one to state.
 *
 * ⭐ ONE FUNCTION, SO A SUPPRESSED BADGE CANNOT LEAVE A DANGLING SEPARATOR.
 *    Every call site printed `heading . ' - ' . save` inline, so returning ''
 *    from the function above would have left four headings ending in " - ".
 *    That is the `bhp_bundle_shipping_display()` lesson applied to a second
 *    string: the surface that owns the separator owns the empty case too.
 *
 * @since 1.8.80
 * @param string $format 'paperback'|'hardcover'.
 * @param int    $tier   2 or 3.
 * @return string Customer-facing text, already plain (callers escape).
 */
function bhp_bundle_box_heading( $format, $tier ) {
	$rules   = bhp_bundle_rules( $format );
	$tier    = (int) $tier;
	$heading = isset( $rules[ $tier ]['heading'] ) ? (string) $rules[ $tier ]['heading'] : '';
	$saving  = bhp_bundle_saving_label( $format, $tier );

	if ( '' === $heading || '' === $saving ) {
		return $heading;
	}

	return $heading . ' - ' . $saving;
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
 * ⚠ COPY STATUS: DRAFTED FROM ANDREW'S STATED INTENT, **PENDING
 *   `marketing-growth` REVIEW.** His phrasing intent, relayed: *"free
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
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.66 — THE FREE-SHIPPING PROGRESS LINE. FOUNDER CARRIER ITEM 196.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE THREE STRINGS ARE FOUNDER-APPROVED — he approved the copy itself, not
 *    merely the idea of a line. ⚠️ RELAYED to this agent by `chief-of-staff` in
 *    the build brief; NOT witnessed first-hand here, and labelled as relayed
 *    rather than presented as a source read.
 *
 * ⭐⭐ IT IS KEYED ON A **COUNT OF PHYSICAL BOOKS**, DUPLICATES INCLUDED — the
 *    same quantity the shipping rule itself reads, and this is the whole
 *    reason the line can be trusted. The counter is
 *    `bhp_bundle_physical_book_count()` (bundle-data.php), which is
 *    `bhp_bundle_total_quantity_in_cart()` + `bhp_bundle_colouring_quantity_
 *    in_cart()`; `bhp_bundle_shipping_amount()` branch A (bundle-cart.php)
 *    reads that exact field as `$eval['physical_book_count']` and returns
 *    $0.00 at `>= 3` under `FD-583` / `bhp_bundle_colouring_policy() ===
 *    'any-three'`. ⛔ ONE COUNTER, TWO READERS. The panel cannot promise a
 *    threshold the cart charges past, because it is not counting separately.
 *
 * ⛔ IT IS **NOT** A COUNT OF DISTINCT ADVENTURES, and that distinction is the
 *    difference between this line and `bhp_bundle_freeship_copy()` above.
 *    That one is a COLLECTION nudge keyed on distinct titles; this one is a
 *    SHIPPING nudge keyed on quantity. Three copies of one title is three
 *    books of postage and zero progress toward a collection, so the two lines
 *    legitimately say different things about the same cart.
 *
 * ⛔ NO URGENCY THEATRICS, by instruction and by the standing constraint on
 *    `bhp_bundle_freeship_copy()` above: no countdown, no "today only", no
 *    "hurry", no scarcity, no exclamation, no colour alarm. Nothing here
 *    expires and the copy does not pretend it does.
 *
 * ⛔ NO DOLLAR FIGURE IN ANY OF THE THREE STRINGS — the same rule the nudge
 *    follows, for the same reason: a hardcoded amount drifts out of step with
 *    the tier table, and the amount saved differs by format and by cart.
 *
 * ⭐ SINGULAR/PLURAL IS BAKED IN RATHER THAN COMPUTED. "1 more book" and
 *    "2 more books" are two separate approved strings, not one string with a
 *    pluralisation branch, so no code decides how his sentence reads.
 *
 * @return array{1:string,2:string,earned:string}
 */
function bhp_bundle_ship_progress_copy() {
	$copy = array(
		1        => 'Add 2 more books and shipping is FREE.',
		2        => 'Add 1 more book and shipping is FREE.',
		'earned' => 'Your order ships FREE.',
	);

	/**
	 * Swap the mini-cart free-shipping progress copy.
	 *
	 * @param array $copy Keyed by the number of physical books ALREADY IN THE
	 *                    CART (1, 2), plus 'earned' for the threshold and
	 *                    above. ⛔ Keyed on what the shopper HAS, not on what
	 *                    is missing — the strings themselves name the
	 *                    remainder, so a reader comparing key to sentence does
	 *                    not have to do the subtraction twice.
	 */
	return apply_filters( 'bhp_bundle_ship_progress_copy', $copy );
}

/**
 * ⭐ 1.8.66 — the book count at which shipping becomes free.
 *
 * ⛔ READ FROM THE RULE, NOT WRITTEN DOWN A SECOND TIME. The threshold lives in
 *    `bhp_bundle_shipping_amount()` branch A as `>= 3`; this exposes it so the
 *    panel copy and the cart cannot disagree about where the line is.
 *
 * @return int
 */
function bhp_bundle_freeship_book_threshold() {
	return (int) apply_filters( 'bhp_bundle_freeship_book_threshold', 3 );
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
 * one shipment. The `chief-of-staff` direction for this build, verbatim: *"mixed
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
		/*
		 * ⭐⭐ 1.8.61 — A COLOURING BOOK IS RELATED. `ACT-OPS-269`.
		 *
		 * ⛔ THIS IS THE LINE THAT CLOSES THE DEFECT, and it is deliberately
		 *    NOT the add-on allowlist above it. A colouring book is skipped
		 *    here because the system UNDERSTANDS it -- it is in
		 *    `bhp_colouring_catalog()`, it is counted by
		 *    `bhp_bundle_physical_book_count()`, and it is priced by
		 *    `bhp_bundle_shipping_amount()`. It is NOT skipped because it is
		 *    weightless, which is what the add-on branch above asserts and
		 *    which would be FALSE of a printed 8.5x11 book.
		 *
		 * ⭐ THE FAIL-SAFE IS UNCHANGED FOR EVERYTHING ELSE. A genuinely
		 *    unknown product still returns true here, and from 1.8.61 that
		 *    now suppresses the DISCOUNT as well as the shipping override --
		 *    see `bhp_bundle_apply_discount_fees()`. Unrelated fails SAFE in
		 *    BOTH directions: no discount AND no free-shipping promise.
		 */
		if ( null !== bhp_bundle_identify_colouring_item( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
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
	/*
	 * ⭐ 1.8.36 — DOES THIS CART CONTAIN AT LEAST ONE OF THE SIX APPROVED
	 *    BOOK EDITIONS? This is the predicate the FREE Activity Book offer
	 *    now runs on, after Andrew Signore's ruling of 2026-08-06 widened it
	 *    from collections to ANY book purchase (⛔ RELAYED, not witnessed
	 *    first-hand; verbatim on disk in the Chief of Staff's founder
	 *    carrier: "make the activity book free with any book purchase - say
	 *    its a $5.00 savings").
	 *
	 * ⛔ IT IS DERIVED FROM `distinct_adventures`, THE SAME QUANTITY
	 *    `is_complete_collection` COMES FROM, and that is the whole point:
	 *    one count, one definition of "a book", three thresholds (>=1 free
	 *    add-on, ==2 free-shipping nudge, >=3 collection). A second count
	 *    here would be a place where "you have a book" and "you have a
	 *    collection" could disagree about the same cart.
	 *
	 * ⛔ AN ADD-ON-ONLY CART IS FALSE BY CONSTRUCTION. The Activity Book is
	 *    not in the six-edition catalogue `bhp_bundle_distinct_titles_in_cart()`
	 *    walks, so a cart holding only the add-on counts ZERO adventures.
	 *    That is the never-sold-alone guard restated in data rather than a
	 *    second rule that could drift from it.
	 */
	$result['has_any_book'] = $result['distinct_adventures'] >= 1;
	// Mixed-format cart: paperback AND hardcover both present. The approved
	// shipping table (Phase 6) only defines paperback-only or
	// hardcover-only tiers, so this combination is intentionally left
	// unresolved here — see bhp_bundle_shipping_amount() for the fallback.
	$result['is_mixed_format'] = $result['has_paperback'] && $result['has_hardcover'];

	/*
	 * ⭐⭐ 1.8.61 — THE COLOURING LINE, ADDED TO THE EVALUATION RATHER THAN
	 *    BOLTED ONTO THE CALLERS. `ACT-OPS-269`.
	 *
	 * ⛔ EVERY KEY BELOW IS ZERO / EMPTY / FALSE UNTIL A COLOURING SKU
	 *    RESOLVES TO A LIVE PRODUCT, which is the state of both environments
	 *    as this ships. `$result` is therefore a SUPERSET of what it was, with
	 *    every pre-existing key computed by byte-identical code. Nothing that
	 *    reads this array today can observe a different value.
	 */
	$result['colouring_quantity']  = bhp_bundle_colouring_quantity_in_cart( $cart );
	$result['distinct_colouring']  = count( bhp_bundle_distinct_colouring_in_cart( $cart ) );
	$result['has_colouring']       = $result['colouring_quantity'] > 0;
	// ⭐ The count postage is actually paid on: catalogue editions + colouring
	//    books, duplicates included, weightless add-ons excluded.
	$result['physical_book_count'] = bhp_bundle_physical_book_count( $cart );

	return $result;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.61 — THE COLOURING LINE. `ACT-OPS-269`, and it ships BEFORE the
 *              first colouring product record on ANY environment.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ WHY THIS EXISTS, AND WHY IT COULD NOT WAIT FOR THE PRODUCT.
 *
 * `bhp_bundle_cart_has_unrelated_items()` above returns TRUE for anything that
 * is neither one of the six approved editions nor an allowlisted add-on.
 * Before this file was extended, a colouring book was "anything". The
 * consequence, on a cart of three chapter paperbacks + one colouring book:
 *
 *   · `bhp_bundle_override_shipping_cost()` bails on `has_unrelated`, so the
 *     customer falls back to the zone's raw flat rate;
 *   · `bhp_bundle_apply_discount_fees()` carried NO `has_unrelated` guard, so
 *     the -$3.98 collection discount STILL APPLIED;
 *   · the collection page's "FREE shipping" promise is produced by
 *     `bhp_bundle_rules()` BEFORE any cart exists, so it STILL RENDERED;
 *   · `bhp_bundle_print_progress_messages()` DOES check `has_unrelated`, so
 *     the cart copy went quiet -- making it LESS discoverable, not more.
 *
 * ⭐ NET: the site promises free shipping, the shopper adds the collection and
 *    a colouring book, and is charged for shipping. A FALSE ADVERTISED CLAIM,
 *    created by adding a product record, with NO code change. Registered
 *    `CYCLE165-OPS-018`; the prerequisite is `ACT-OPS-269`.
 *
 * ⛔⛔ THE OBVIOUS FIX IS A TRAP AND IT WAS NOT TAKEN. Adding the colouring
 *    SKUs to `bhp_bundle_addon_skus()` silences `has_unrelated` in one line --
 *    and thereby declares a PHYSICAL, SHIPPABLE, PRINTED BOOK to be a
 *    weightless digital add-on. `bhp_bundle_total_quantity_in_cart()` SKIPS
 *    add-ons, so a cart of 2 paperbacks + 1 colouring book would be priced as
 *    a TWO-BOOK shipment while THREE physical books ship. That is a real
 *    fulfilment-cost error dressed as a config change. The colouring line gets
 *    its OWN registry instead, and is counted as the physical book it is.
 *
 * ⭐ A SEPARATE REGISTRY, NOT AN EXTENSION OF `bhp_book_registry()`. Every
 *    entry in that registry assumes a paperback AND a hardcover, and
 *    `bhp_book_hide_hardcovers_from_shop()` pushes `hc_product` into
 *    `post__not_in`. A colouring book has ONE binding. ⛔ It is not modelled
 *    as a book with a missing hardcover.
 *
 * ✅ SKU-KEYED, AND IT FAILS CLOSED. Product IDs differ between staging and
 *    production; a hardcoded ID would either do nothing on production or match
 *    a DIFFERENT product there. Until a SKU resolves -- which is the state of
 *    BOTH environments as this ships, verified over SSH this session -- every
 *    function below returns an empty list and the cart maths is BYTE-FOR-BYTE
 *    what it was before this file was touched. That is deliberate: the fix
 *    lands first, the product later.
 */
function bhp_colouring_catalog() {
	/**
	 * The colouring line, keyed by adventure slug.
	 *
	 * ⛔ `product_id` is deliberately ABSENT. Resolution is by SKU only.
	 * ⚠ Fulfilment routes PER-ISBN, which is the whole reason there is no
	 *   bundle SKU anywhere in this plugin.
	 *
	 * @param array $titles Colouring-line titles, keyed by adventure slug.
	 */
	return apply_filters(
		'bhp_colouring_catalog',
		array(
			'mariana' => array(
				/*
				 * ═══════════════════════════════════════════════════════════
				 * ⭐⭐ 1.8.63 (`CYCLE165-LD-COLOURING-ISBN-WIRING`) — THE SKU IS
				 *     THE ISBN, BECAUSE THE SKU IS WHAT FULFILMENT READS.
				 * ═══════════════════════════════════════════════════════════
				 *
				 * ⛔ THE PREVIOUS VALUE WAS `BHP-COLOR-MT-01` AND IT WOULD HAVE
				 *    ROUTED NOWHERE. Read this build, first-hand, from the
				 *    installed third-party plugin on staging:
				 *
				 *      wp-content/plugins/bookvault/Bookvault.php:136-139
				 *        $sku = $product->get_sku();
				 *        if (strlen($sku) == 13) {
				 *          $transaction_lines[] = ["ISBN" => $sku, ...];
				 *        }
				 *
				 *    ⭐ THE ISBN IS NOT A SEPARATE FIELD TO THAT CODE. It IS the
				 *       SKU, gated on being exactly 13 characters long.
				 *       `BHP-COLOR-MT-01` is 15, so the colouring book would
				 *       have contributed NO order line at all — silently, with
				 *       no error anywhere.
				 *
				 * ⭐ AND IT IS WHAT THE SIX LIVE PRODUCTS ACTUALLY DO. Read
				 *    read-only off PRODUCTION this build (`wp post meta get`):
				 *    every one of 14/15/17/18/20 and variation 334 carries its
				 *    ISBN in BOTH `_sku` AND `_global_unique_id`. Matching that
				 *    pattern is the whole task; inventing a third pattern for
				 *    product seven is what would break it.
				 *
				 * ⚠ STAGING DIVERGES FROM PRODUCTION ON THIS AND IT IS RECORDED
				 *   RATHER THAN QUIETLY FIXED — staging's chapter books carry
				 *   internal SKUs (`BHP-MT-HC` and friends) with the ISBN only
				 *   in `_global_unique_id`. That is a STAGING DATA defect, it
				 *   predates this build, and repairing the six chapter records
				 *   is NOT in this workstream's scope.
				 */
				'sku'   => '9798996810840',
				/*
				 * ⭐ THE LEGACY SKU STILL RESOLVES, AND THAT IS NOT A SECOND
				 *    MECHANISM. It is the deploy-ordering guarantee: this code
				 *    ships BEFORE the product record is edited, and an
				 *    environment whose record still reads `BHP-COLOR-MT-01`
				 *    must keep its cart maths working in the interval rather
				 *    than going inert for a shopper mid-order.
				 * ⛔ NOTHING MAY CREATE A NEW PRODUCT ON THE LEGACY SKU. The
				 *    production creation step uses the ISBN, first and only.
				 */
				'sku_aliases' => array( 'BHP-COLOR-MT-01' ),
				/*
				 * ⭐ THE ISBN, STATED SEPARATELY FROM THE SKU ON PURPOSE.
				 *    Today they are the same string. They are DIFFERENT FACTS —
				 *    one is a store identifier, one is a printed-book identity —
				 *    and a reader asking "what ISBN does this route to" must not
				 *    have to infer it from a field named `sku`.
				 * ⭐ SOURCE: `22-COLOURING-BOOK-PRODUCTION-CANON.md`, carried in
				 *    the founder's item-183 dispatch. Not derived, not computed.
				 */
				'isbn'  => '9798996810840',
				/*
				 * ⛔ FD-557, VERBATIM. No agent shortens, re-cases or drops the
				 *    subtitle. This string is for INTERNAL identification only;
				 *    the customer-facing title is the product record's own.
				 */
				'label' => 'Coloring Adventures with Charlotte and Henry: The Mariana Trench Ocean Coloring Book',
			),
			/*
			 * ⛔ Everest and Amazon are NOT listed. They do not exist in any
			 *    form, and their titles are Andrew's per title (spec D-7). The
			 *    series title pattern is INFERRED FROM ONE INSTANCE and is a
			 *    hypothesis, never a ruling. An entry here would be an
			 *    invented product.
			 */
		)
	);
}

/**
 * Resolve the colouring-line SKUs to live product IDs.
 *
 * Request-scoped static for the same reason `bhp_bundle_addon_product_ids()`
 * has one: `has_unrelated` is evaluated several times per cart calculation.
 *
 * @return array<string,int> adventure slug => product id, for SKUs that resolve.
 */
function bhp_colouring_product_ids() {
	static $resolved = null;

	if ( null === $resolved ) {
		$resolved = array();
		if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
			foreach ( bhp_colouring_catalog() as $slug => $info ) {
				if ( empty( $info['sku'] ) ) {
					continue;
				}
				/*
				 * ⭐ 1.8.63 — THE CANONICAL SKU IS TRIED FIRST, ALWAYS, AND THE
				 *    ORDER IS LOAD-BEARING RATHER THAN COSMETIC. On an
				 *    environment mid-migration BOTH could resolve — the ISBN on
				 *    the real record and the legacy string on some leftover —
				 *    and the ISBN is the one fulfilment can actually route.
				 *    First match wins and the loop stops.
				 */
				$candidates = array_merge(
					array( $info['sku'] ),
					isset( $info['sku_aliases'] ) ? (array) $info['sku_aliases'] : array()
				);
				foreach ( $candidates as $candidate ) {
					$candidate = trim( (string) $candidate );
					if ( '' === $candidate ) {
						continue;
					}
					$id = (int) wc_get_product_id_by_sku( $candidate );
					if ( $id > 0 ) {
						$resolved[ $slug ] = $id;
						break;
					}
				}
			}
		}
	}

	/**
	 * The resolved colouring-line product IDs.
	 *
	 * ⭐ THE FILTER IS APPLIED ON EVERY CALL WHILE THE SKU LOOKUP ITSELF IS
	 *    CACHED ONCE. That split is deliberate and it is what makes the tier
	 *    machine TESTABLE: `tests/test-colouring-line-tiers.php` must be able
	 *    to inject a colouring product ID on an environment where no colouring
	 *    product exists, which is every environment today. Caching the FILTER
	 *    result instead would freeze the empty map before any test could reach
	 *    it, and the any-three rule would then be shipped untested -- which is
	 *    the one thing that must not happen to a rule that moves money.
	 *
	 * ⛔ IT IS STILL AN ALLOWLIST, NOT A CATEGORY TEST. Only a product whose
	 *    SKU is in `bhp_colouring_catalog()` resolves here on its own; reaching
	 *    this filter requires code, exactly as `bhp_bundle_addon_skus()` does.
	 *
	 * @param array<string,int> $resolved adventure slug => product id.
	 */
	return apply_filters( 'bhp_colouring_product_ids', $resolved );
}

/**
 * ⭐⭐ 1.8.63 — THE FULFILMENT IDENTITY OF A COLOURING TITLE, AS A 13-DIGIT ISBN.
 *
 * ⛔ WHY THIS FUNCTION EXISTS AT ALL, given the value also sits in `sku`: the
 *    two fields answer different questions and are allowed to diverge in the
 *    future. A caller asking "what does fulfilment route this to" must read a
 *    function named for that question, so that the day a store SKU stops being
 *    an ISBN, every such caller breaks visibly instead of silently shipping the
 *    wrong book.
 *
 * ⛔ IT VALIDATES SHAPE, NOT CHECKSUM, AND THAT IS DELIBERATE. 13 digits is
 *    exactly the test the installed `bookvault` plugin applies
 *    (`Bookvault.php:137`, `strlen($sku) == 13`). Applying a STRICTER test here
 *    than fulfilment applies would let this function report "no ISBN" for a
 *    string fulfilment would happily accept, which is the wrong direction for a
 *    guard: it would hide a live routing problem rather than surface it.
 *
 * @param string $slug Adventure slug.
 * @return string The 13-digit ISBN, or '' when the catalogue has no usable one.
 */
function bhp_colouring_isbn( $slug ) {
	$catalog = bhp_colouring_catalog();
	$slug    = (string) $slug;

	if ( ! isset( $catalog[ $slug ]['isbn'] ) ) {
		return '';
	}

	$isbn = trim( (string) $catalog[ $slug ]['isbn'] );

	// ⛔ FAILS CLOSED. A malformed entry yields '' rather than a bad route.
	return ( 13 === strlen( $isbn ) && ctype_digit( $isbn ) ) ? $isbn : '';
}

/**
 * ⭐ 1.8.63 — the ISBN a colouring PRODUCT ID routes to, or ''.
 *
 * The product-side counterpart of `bhp_colouring_isbn()`, for callers holding
 * an order line rather than a slug.
 *
 * @param int $product_id Product or variation id.
 * @return string
 */
function bhp_colouring_isbn_for_product( $product_id ) {
	foreach ( bhp_colouring_product_ids() as $slug => $id ) {
		if ( (int) $product_id === (int) $id ) {
			return bhp_colouring_isbn( $slug );
		}
	}
	return '';
}

/**
 * ⭐⭐ 1.8.63 — THE ISBN A LIVE PRODUCT RECORD WOULD ACTUALLY ROUTE TO.
 *
 * ⛔ THIS IS THE ONE THAT TELLS THE TRUTH, AND IT IS NOT THE CATALOGUE. Every
 *    function above reads the CATALOGUE — code, which states intent. This reads
 *    the PRODUCT RECORD — data, which states what fulfilment will actually see
 *    on the wire. When they disagree, the record wins and the catalogue is
 *    wrong, and a test that only ever consulted the catalogue could never
 *    discover that.
 *
 * ⭐ IT MIRRORS THE INSTALLED PLUGIN'S OWN RULE EXACTLY, deliberately including
 *    the part that looks like a bug: `Bookvault.php:136-139` reads the SKU and
 *    accepts it only at exactly 13 characters. `_global_unique_id` is read as a
 *    SECOND opinion because the WooCommerce order webhook payload carries it
 *    too (`class-wc-rest-orders-v2-controller.php:232`), and Bookvault's own
 *    receiver is server-side and cannot be read from here.
 *
 * ⚠ SO A DISAGREEMENT BETWEEN THE TWO FIELDS IS REPORTED, NEVER RESOLVED. This
 *   returns them both and lets the caller assert they match, because guessing
 *   which one the remote receiver prefers is precisely the guess that would
 *   ship the wrong book.
 *
 * @param int $product_id Product or variation id.
 * @return array{sku:string,guid:string,routes:bool,agree:bool}
 */
function bhp_colouring_product_isbn_state( $product_id ) {
	$out = array(
		'sku'    => '',
		'guid'   => '',
		'routes' => false,
		'agree'  => false,
	);

	if ( ! function_exists( 'wc_get_product' ) ) {
		return $out;
	}

	$product = wc_get_product( (int) $product_id );
	if ( ! $product ) {
		return $out;
	}

	$out['sku'] = trim( (string) $product->get_sku() );

	if ( method_exists( $product, 'get_global_unique_id' ) ) {
		$out['guid'] = trim( (string) $product->get_global_unique_id() );
	}

	// ⭐ The plugin's own gate, character for character. Not a stricter one.
	$out['routes'] = ( 13 === strlen( $out['sku'] ) );
	$out['agree']  = ( '' !== $out['sku'] && $out['sku'] === $out['guid'] );

	return $out;
}

/**
 * True if this product id belongs to the colouring line. The ID-based test
 * that replaces title-substring matching everywhere it matters.
 */
function bhp_is_colouring_product( $product_id ) {
	$product_id = (int) $product_id;
	return $product_id > 0 && in_array( $product_id, array_map( 'intval', bhp_colouring_product_ids() ), true );
}

/**
 * Which colouring title (if any) a cart line represents.
 *
 * Matches parent product id AND variation id, so a future variable colouring
 * product cannot slip past by being added as a variation.
 *
 * @return string|null Adventure slug, or null.
 */
function bhp_bundle_identify_colouring_item( $product_id, $variation_id = 0 ) {
	foreach ( bhp_colouring_product_ids() as $slug => $id ) {
		if ( (int) $product_id === (int) $id ) {
			return $slug;
		}
		if ( $variation_id && (int) $variation_id === (int) $id ) {
			return $slug;
		}
	}
	return null;
}

/**
 * Total quantity of colouring-line items in the cart. ⭐ DUPLICATES COUNT --
 * three copies of one title is three printed books and three books of postage.
 */
function bhp_bundle_colouring_quantity_in_cart( $cart ) {
	$total = 0;
	if ( ! $cart ) {
		return $total;
	}
	foreach ( $cart->get_cart() as $cart_item ) {
		if ( null === bhp_bundle_identify_colouring_item( $cart_item['product_id'], $cart_item['variation_id'] ) ) {
			continue;
		}
		$total += (int) $cart_item['quantity'];
	}
	return $total;
}

/**
 * Distinct colouring TITLES in the cart. A collection counts titles; shipping
 * counts books. Keeping the two questions separate is what stops "buy the same
 * book three times" from ever being mistaken for owning a collection.
 *
 * @return string[] Distinct adventure slugs.
 */
function bhp_bundle_distinct_colouring_in_cart( $cart ) {
	$present = array();
	if ( ! $cart ) {
		return $present;
	}
	foreach ( $cart->get_cart() as $cart_item ) {
		$slug = bhp_bundle_identify_colouring_item( $cart_item['product_id'], $cart_item['variation_id'] );
		if ( null !== $slug && ! in_array( $slug, $present, true ) ) {
			$present[] = $slug;
		}
	}
	return $present;
}

/**
 * ⭐⭐ EVERY PHYSICAL, SHIPPABLE BOOK IN THE CART -- the count postage is
 *    actually paid on.
 *
 * ⛔ IT IS A QUANTITY, NOT A SET. Duplicates count. Three copies of one title
 *    is three books in one box.
 *
 * ⛔ WEIGHTLESS ADD-ONS ARE EXCLUDED, and that exclusion must never drift:
 *    `bhp_bundle_addon_skus()` names a digital PDF, and a digital file has no
 *    weight and must not move a book-count tier. Both terms below already
 *    skip it -- `bhp_bundle_total_quantity_in_cart()` counts only identified
 *    catalogue editions, and the colouring term counts only resolved
 *    colouring SKUs.
 */
function bhp_bundle_physical_book_count( $cart ) {
	return bhp_bundle_total_quantity_in_cart( $cart ) + bhp_bundle_colouring_quantity_in_cart( $cart );
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️⚠️ THE FREE-SHIPPING POLICY SWITCH — ⛔ AND IT IS AN OPEN FOUNDER DECISION
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ TWO SOURCES DISAGREE ABOUT WHETHER THIS IS RULED. RECORDED, NOT
 *    RESOLVED, per Standing Rules §7. NO AGENT PICKS.
 *
 *   · `00A-WHAT-GOVERNS-TODAY.md` §2A / S1 / C1 / N2, READ LIVE 2026-08-20 by
 *     this agent, that file updated 2026-08-20T09:0x-0600: THE FOUR BUILD
 *     DECISIONS -- architecture, the eight prices, ⭐ THE COLOURING-IN-FREE-
 *     SHIPPING POLICY, and sequence confirm -- all read ⛔⛔ OPEN. `FD-575`(c)
 *     approved the shop-matrix spec as a PLAN, not a BUILD.
 *   · The build brief this agent received relays founder carrier items 158/159
 *     of 2026-08-20 ~17:5x-0600 ruling ⭐ FREE SHIPPING AT ANY 3+ BOOKS,
 *     DUPLICATES INCLUDED. ⚠️ RELAYED THROUGH THE CHIEF OF STAFF, NOT
 *     WITNESSED FIRST-HAND by this agent, and ⛔ NOT FOUND ON DISK IN ANY
 *     CANONICAL FILE by a recursive search run this session.
 *
 * ⭐ SO BOTH BEHAVIOURS ARE BUILT AND BOTH ARE TESTED. The DEFAULT is the
 *    STRICTER one (Standing Rules §1: when instructions conflict, the stricter
 *    restriction applies until Andrew explicitly decides otherwise).
 *
 *   `conservative` ⭐ DEFAULT. Free shipping still requires THREE DISTINCT
 *                  ADVENTURES, exactly as 1.8.23 ruled it. Colouring books are
 *                  recognised, counted and priced -- ⛔ which is what actually
 *                  closes `ACT-OPS-269` -- but they open no new free-shipping
 *                  route. NOTHING A CUSTOMER IS PROMISED CHANGES.
 *
 *   `any-three`    The brief's ruling. Any 3+ PHYSICAL BOOKS ship free,
 *                  duplicates included. ⭐ The $4.99 mixed-3+-but-under-3-
 *                  distinct row DIES: it becomes unreachable, because the
 *                  any-three branch catches every cart that could reach it.
 *
 * ⭐ FLIPPING IT IS ONE LINE, once the ruling is in a canonical file:
 *
 *       add_filter( 'bhp_bundle_colouring_policy', function () {
 *           return 'any-three';
 *       } );
 *
 * ⛔ DO NOT flip the default without the founder ruling on disk. Moving a
 *    customer's shipping cost on a relayed claim that no document carries is
 *    the precise failure class this corpus exists to prevent.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.62 — THE DEFAULT IS NOW `any-three`. THE RULING IS ON DISK.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ EVERYTHING ABOVE THIS BLOCK IS PRESERVED VERBATIM AND IS NOW HISTORICAL.
 *    ⭐ It is deliberately NOT corrected in place. It records that 1.8.61 built
 *    both behaviours and defaulted to the stricter one BECAUSE THE RULING WAS
 *    NOT YET IN A CANONICAL FILE — which was the correct call at the time, and
 *    a future reader needs to see that the conservative default was a reasoned
 *    refusal rather than an oversight.
 *
 * ⭐⭐ THE CONDITION 1.8.61 SET FOR FLIPPING THIS IS NOW MET, AND IT WAS MET BY
 *    THE FOUNDER, NOT BY A BRIEF. Its own words: "DO NOT flip the default
 *    without the founder ruling on disk."
 *
 * ⭐ `FD-583`, `FOUNDER-DECISIONS-2026-08-01.md` PART 66 §66.8 — ⭐⭐ READ AT
 *    SOURCE, FIRST-HAND, BY THE AGENT THAT MADE THIS CHANGE. ⛔ NOT RELAYED,
 *    NOT INHERITED FROM A BRIEF, NOT TAKEN FROM A PRIOR REPORT. Andrew
 *    Signore, 2026-08-20 ~17:4x−0600, carrier item 159, verbatim:
 *
 *      "any 3 books - I think the margins will hold the same especially since
 *       we increased the coloring book price to 12.99"
 *
 * ⭐ HE CONNECTED THE PRICE RULING TO THE SHIPPING RULING HIMSELF. §66.8
 *    records that explicitly: "this is not an inference anyone made for him."
 *
 * ⭐ AND THE AMBIGUITY WAS PUT TO HIM RATHER THAN GUESSED. "3 or more books"
 *    has two readings that build different carts — ANY three including
 *    duplicates, or three DISTINCT titles. He was asked in the same turn
 *    (`FD-582`, §66.7) and answered immediately. ⛔ THE DUPLICATES LIMB IS HIS.
 *
 * ⚠️⚠️ THIS IS A REAL COMMERCIAL MOVEMENT AND IS REPORTED AS ONE, NOT AS A
 *    CONFIG TIDY-UP: a cart of three copies of one title shipped at $4.99 and
 *    from 1.8.62 ships FREE. ⛔ The mixed "≥3 books but <3 distinct" $4.99 row
 *    becomes UNREACHABLE — "the $4.99 row dies" (§66.8) — and it is left in the
 *    table rather than deleted so a reader can still see what it used to say.
 *
 * ⛔ THE 00A CONTRADICTION 1.8.61 RECORDED IS RESOLVED BY THE FOUNDER, NOT BY
 *    THIS AGENT. `00A-WHAT-GOVERNS-TODAY.md` read the policy ⛔ OPEN at
 *    2026-08-20T09:0x−0600; `FD-583` was sealed at ~17:5x−0600 the same day.
 *    ⭐ The document was true when written and is now STALE — the newer
 *    founder ruling governs, and §66.1 records `00A` §5 S1 flipping to RULED
 *    on the strength of it. No agent resolved anything (Standing Rules §7).
 *
 * ⛔ `conservative` IS NOT DELETED. It remains reachable through the filter, so
 *    the 1.8.61 behaviour is one line away and every one of its tests still
 *    runs against it.
 *
 * @return string 'conservative'|'any-three'
 */
function bhp_bundle_colouring_policy() {
	/**
	 * The free-shipping policy for carts containing colouring-line books.
	 *
	 * ⭐ 1.8.62: the default is `any-three` (`FD-583`). Was `conservative`.
	 *
	 * @param string $policy 'any-three' (default) or 'conservative'.
	 */
	$policy = apply_filters( 'bhp_bundle_colouring_policy', 'any-three' );
	return in_array( $policy, array( 'conservative', 'any-three' ), true ) ? $policy : 'conservative';
}
