<?php
/**
 * Brave Hearts Bundle Pricing — THE SCHOOL-VISIT BACKORDER ALLOWANCE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS EXISTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-28, verbatim (⛔ RELAYED to this desk through the
 * Chief of Staff's `CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS` dispatch and
 * through carrier item 363 of `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-
 * AUTHORIZATION.md`; NOT witnessed first-hand by the agent that wrote this):
 *
 *   "I think we allow backorders and we will get the new books in latest by
 *    Sept 10th. If not we will figure something out, Like dropping off the
 *    books a few days later"
 *
 * ⭐⭐ THIS IS THE ANSWER TO A RISK THIS PLUGIN REGISTERED AGAINST ITSELF.
 *    `school-visit-stock-privacy.php` (1.8.75) closes with a paragraph headed
 *    "THE RESIDUAL, NAMED RATHER THAN HIDDEN", which says: hiding the Amity
 *    quantity does not help if the shelf actually reaches the buffer, because
 *    the title then CLOSES for every visit-flagged session and an Amity parent
 *    cannot buy it at all. That file recorded the residual and refused to
 *    resolve it, because resolving it needed his word. ⭐ ITEM 363 IS HIS WORD.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS FILE IS **NOT**, AND THE DISTINCTION IS THE WHOLE DESIGN
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT IS NOT A WOOCOMMERCE BACKORDER SETTING. `_backorders`, `_stock`,
 *    `_stock_status` and `_manage_stock` are NEVER read-modified or written by
 *    this file, on any environment. ⭐ VERIFIED READ-ONLY ON PRODUCTION
 *    2026-08-28 by the 1.8.75 lane and recorded in that file: all nine
 *    product/variation records carry `_manage_stock = no`, an empty `_stock`,
 *    `_stock_status = instock` and `_backorders = no`. ⛔ CHANGING ANY OF THEM
 *    IS A WOOCOMMERCE PRODUCT-CONFIGURATION MUTATION AND IS ANDREW'S GATE.
 *    `school-visit-stock-privacy.php` said so in as many words: "Allowing a
 *    visit-flagged backorder would be a WooCommerce product-configuration
 *    change and requires his explicit word." ⭐ IT TURNS OUT NOT TO NEED ONE.
 *
 * ⭐⭐ BECAUSE THE THING BEING RELAXED WAS NEVER WOOCOMMERCE INVENTORY IN THE
 *    FIRST PLACE. The sold-out state is this plugin's OWN session-scoped gate,
 *    computed as `baseline - committed <= buffer` from an option and from real
 *    orders. Relaxing it is relaxing a rule this plugin invented. ⭐ SO THE
 *    "backorder" here is a BACKORDER IN THE ORDINARY-ENGLISH SENSE — an order
 *    accepted for stock not yet on the shelf — and no WooCommerce concept, and
 *    no product record, is involved at any point.
 *
 * ⛔ AND IT IS INVISIBLE TO EVERY ORDINARY SHOPPER. Every gate it touches is
 *    already behind `bhp_school_visit_paperback_only()`, which is false for
 *    every unflagged session on every environment. An ordinary shopper's page
 *    is byte-identical to 1.8.75.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE DEFAULT IS **ON**, AND THAT IS A CHOICE WITH A REASON
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Most switches in this plugin default to the inert setting. This one does not,
 * and the reasoning is the same governance reasoning 1.8.75 used when it
 * shipped the Amity slug in code rather than waiting for a data row:
 *
 *   ⛔ A default of OFF would deploy this build INERT. The ruling would look
 *      like it was in force while a real parent, at a real visit, still met a
 *      "Sold out for the school visit" button. A ruling that is only in force
 *      after somebody remembers to run a WP-CLI line is not in force.
 *
 * ⭐ IT IS ONE LINE TO REVERSE, WITH NO DEPLOY, IN EITHER DIRECTION:
 *
 *      wp option update bhp_visit_shelf_backorders no  --user=1   # off
 *      wp option delete bhp_visit_shelf_backorders     --user=1   # back to on
 *
 *   or, in a theme or mu-plugin:
 *
 *      add_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false' );
 *
 * ⚠️ AND IT IS BEHAVIOURALLY INERT TODAY ANYWAY, WHICH IS WHY DEFAULTING IT ON
 *    IS SAFE RATHER THAN MERELY DEFENSIBLE. `bhp_visit_shelf_stock` is UNSET on
 *    production, so `bhp_visit_shelf_title_is_exhausted()` is false for every
 *    title, so this allowance has nothing to relax. It becomes live the moment
 *    a baseline is seeded and a title actually runs down — which is exactly
 *    when he wants it.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE HONESTY RULES ARE UNCHANGED AND ARE TIGHTENED, NOT LOOSENED
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ✅ NO SURFACE MAY EVER DISPLAY A COUNT HIGHER THAN THE REAL ONE. This file
 *    contains no arithmetic and no number. It cannot raise a count because it
 *    never touches one.
 * ✅ THE COUNTER STILL GOES SILENT AT THE BUFFER. The 2..10 display window is
 *    untouched, and `bhp_visit_shelf_title_counter()` now guards on the PURE
 *    SHELF FACT (`_is_exhausted()`), NOT on the relaxed purchase gate — so a
 *    title Andrew closes by hand at remaining = 6 still prints no number, and
 *    an exhausted title can never print "Only 1 left".
 * ✅ WHAT REPLACES THE COUNTER IS A STATEMENT ABOUT ORDERING, NOT ABOUT STOCK.
 *    See the words below and the reasoning attached to them.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE WORDING DECISION, AND IT DEPARTS FROM THE DISPATCH ON PURPOSE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The dispatch suggested a line in the shape of "More copies arriving before
 * the visit". ⛔ THAT SENTENCE IS FALSE FOR AT LEAST ONE LIVE VISIT AND CANNOT
 * SHIP AS WRITTEN. The restock lands 2026-09-07 to 09-11. Dallas Harris is
 * 2026-09-03 and Liberty is 2026-09-04 — BOTH BEFORE THE RESTOCK. Telling a
 * Dallas Harris parent that more copies arrive before their visit would be a
 * fabricated availability claim, which is the one direction this plugin is
 * forbidden to move in, and it would be fabricated to the parents least able
 * to absorb it.
 *
 * ⛔ IT ALSO BREAKS A STANDING CONSTRAINT THIS SUBSYSTEM ALREADY CARRIES.
 *    `school-visit-shelf-stock.php`, on its own customer-facing strings: "NO
 *    RESTOCK DATE AND NO RESTOCK PROMISE. His restock has already slipped once
 *    (Sept 7-11), and a date in a storefront string becomes a promise to a
 *    parent that nobody can keep." A restock PROMISE is the same object as a
 *    restock DATE with the number filed off.
 *
 * ⭐ SO THE SHIPPED LINE PROMISES ONLY WHAT THE FOUNDER HIMSELF ACCEPTED AS
 *   THE WORST CASE, IN HIS OWN WORDS: "we will figure something out, Like
 *   dropping off the books a few days later." It is the same commitment, said
 *   to the parent instead of about them.
 *
 * ⛔ §9.1 VOICE: I/me, never "we"/"us"/"our" standing for the company.
 * ⛔ NO EM DASH. ⛔ AMERICAN SPELLING. ⛔ NO DATE. ⛔ NO NUMBER.
 * ⛔ NO URGENCY DEVICE: no "hurry", no "last chance", no exclamation mark.
 * ⛔⛔ FLAGGED FOR ANDREW'S EYE — DRAFT, NOT SELF-APPROVED. Both strings below
 *     are new customer-facing copy written by an agent and are marked NEEDS
 *     ANDREW in the build report, with the one-line overrule beside each.
 *
 * @package brave-hearts-bundle-pricing
 * @since 1.8.76
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The option that can switch the allowance off without a deploy.
 *
 * ⛔ ABSENT MEANS ON. See the header: an absent option is the founder's ruling
 *    in force, not a feature waiting to be enabled.
 */
if ( ! defined( 'BHP_VISIT_BACKORDER_OPTION' ) ) {
	define( 'BHP_VISIT_BACKORDER_OPTION', 'bhp_visit_shelf_backorders' );
}

/* =========================================================================
 * THE PREDICATE
 * ====================================================================== */

/**
 * ⭐⭐ MAY A VISIT-FLAGGED SESSION ORDER PAST THE SHELF COUNT?
 *
 * ⛔ IT DOES NOT ASK WHETHER THE SESSION IS FLAGGED, for exactly the reason
 *    `bhp_visit_shelf_title_is_exhausted()` and
 *    `bhp_school_visit_record_hides_stock()` do not: it answers a question
 *    about POLICY, which is true or false regardless of who is looking, so a
 *    report or a test can ask it without faking a session. Its callers are
 *    already visit-scoped.
 *
 * ⛔ IT IS PER-TITLE-AWARE BUT NOT PER-TITLE BY DEFAULT. The slug is passed to
 *    the filter so Andrew can allow backorders on two titles and refuse them on
 *    a third (a book he genuinely cannot reprint in time) without a code change.
 *    Nothing in the default path varies by slug.
 *
 * ⭐ FAILS TO **ON**, and the direction is chosen rather than inherited. A
 *    throwing option read must not silently reimpose a sold-out gate the
 *    founder lifted; the cost of the other direction is a parent refused a book
 *    Andrew was willing to deliver a few days late.
 *
 * @param string $slug Title slug, or '' when asking the general question.
 * @return bool
 */
function bhp_visit_shelf_backorder_allowed( $slug = '' ) {
	$slug    = sanitize_key( (string) $slug );
	$allowed = true;

	try {
		$raw = get_option( BHP_VISIT_BACKORDER_OPTION, null );

		/*
		 * ⛔ ONLY AN EXPLICIT, RECOGNISABLE "no" TURNS IT OFF. A null, an empty
		 *    string, a stray array or a value nobody meant is treated as
		 *    "unset", i.e. ON — because the ruling is the default and a
		 *    malformed row must not quietly revoke it.
		 */
		if ( is_scalar( $raw ) ) {
			$raw = strtolower( trim( (string) $raw ) );
			if ( in_array( $raw, array( 'no', 'off', 'false', '0' ), true ) ) {
				$allowed = false;
			}
		}
	} catch ( Throwable $e ) {
		$allowed = true; // FAIL ON.
	}

	/**
	 * Whether visit-flagged sessions may order past the shelf count.
	 *
	 * @since 1.8.76
	 * @param bool   $allowed True when backorders are permitted.
	 * @param string $slug    Title slug, or '' for the general question.
	 */
	return (bool) apply_filters( 'bhp_visit_shelf_backorder_allowed', $allowed, $slug );
}

/**
 * ⭐ IS THIS TITLE ON BACKORDER FOR THE SCHOOL VISIT, FOR **THIS** REQUEST?
 *
 * True only when ALL of: the session is visit-flagged, the shelf really is
 * exhausted, and backorders are allowed. ⛔ FALSE FOR EVERY ORDINARY SHOPPER,
 * on every environment, always — and false on any environment with no shelf
 * baseline set, because nothing is exhausted there.
 *
 * ⭐ THIS IS THE ONE PREDICATE A SURFACE MAY ASK, and it is the reason the
 *    backorder line and the sold-out label can never print together: they are
 *    the two halves of the same `if`, taken from the same two functions.
 *
 * @param string $slug Title slug.
 * @return bool
 */
function bhp_visit_shelf_title_is_backordered_for_request( $slug ) {
	if ( ! function_exists( 'bhp_school_visit_paperback_only' )
		|| ! function_exists( 'bhp_visit_shelf_title_is_exhausted' ) ) {
		return false; // FAIL SILENT: no visit machinery -> no new words anywhere.
	}

	try {
		if ( ! bhp_school_visit_paperback_only() ) {
			return false; // ⭐⭐ ZERO CHANGE for every ordinary shopper.
		}
		if ( ! bhp_visit_shelf_title_is_exhausted( $slug ) ) {
			return false; // The shelf is fine. Nothing to say.
		}
		return bhp_visit_shelf_backorder_allowed( $slug );
	} catch ( Throwable $e ) {
		return false; // FAIL SILENT.
	}
}

/* =========================================================================
 * THE CUSTOMER-FACING WORDS.
 *
 * ⛔⛔ DRAFT, NOT SELF-APPROVED. See this file's header for the full wording
 *     rationale, for the sentence that was REFUSED, and for why.
 * ====================================================================== */

/**
 * The short label printed where the counter or the sold-out label would go.
 *
 * ⛔ IT IS NOT "SOLD OUT" AND IT IS NOT "IN STOCK". Both would be untrue. The
 *    title is orderable and the copy is not on the shelf yet, and the label
 *    says exactly that in three words.
 *
 * @return string
 */
function bhp_visit_shelf_backorder_label() {
	/**
	 * The short backorder label for an exhausted title in visit mode.
	 *
	 * ⭐ ANDREW'S ONE-LINE ALTERNATES, should he prefer either:
	 *    plainer   add_filter( 'bhp_visit_shelf_backorder_label',
	 *                fn() => 'Available to order' );
	 *    warmer    add_filter( 'bhp_visit_shelf_backorder_label',
	 *                fn() => 'You can still order this one' );
	 *
	 * @since 1.8.76
	 * @param string $label Customer-facing label.
	 */
	return (string) apply_filters(
		'bhp_visit_shelf_backorder_label',
		__( 'Ordering ahead', 'brave-hearts' )
	);
}

/**
 * The full sentence: printed beside a backordered title and anywhere the
 * sold-out sentence used to be.
 *
 * ⛔ EVERY CLAUSE TRACES TO SOMETHING HE SAID OR SOMETHING ALREADY TRUE:
 *    · "I am waiting on more copies of this one" — item 363, that the new
 *      books are not here yet. No date, because his restock already slipped.
 *    · "you can still order it for the visit" — item 363's ruling itself.
 *    · "I will get it to you within a few days after" — item 363 verbatim,
 *      "Like dropping off the books a few days later", said to the parent.
 *    · "I sign and hand deliver these myself" — already on the site, in
 *      `bhp_visit_shelf_sold_out_message()`, approved and unchanged.
 *
 * ⛔ WHAT IS DELIBERATELY ABSENT: any date, any count, any "before the visit",
 *    any "arriving soon", any apology, and any claim about how many are coming.
 *
 * @return string
 */
function bhp_visit_shelf_backorder_message() {
	/**
	 * The sentence shown when an exhausted title meets a visit-flagged session
	 * and backorders are allowed.
	 *
	 * @since 1.8.76
	 * @param string $message Customer-facing sentence.
	 */
	return (string) apply_filters(
		'bhp_visit_shelf_backorder_message',
		__( 'I am waiting on more copies of this one. You can still order it for the visit, and if it is not in my hands by then I will get it to you within a few days after. I sign and hand deliver these myself either way.', 'brave-hearts' )
	);
}

/**
 * ⭐ THE ONE OWNER OF THE BACKORDER MARKUP.
 *
 * ⛔ THE CLASS NAME EXISTS IN EXACTLY ONE PLACE IN PHP, exactly as
 *    `bhp-bundle-stock-counter` does, and the suite asserts that. Three
 *    surfaces echoing their own span is how a fourth surface eventually grows
 *    a copy that forgets the visit gate.
 *
 * ⛔ IT PRINTS NOTHING, AND EMITS NO ELEMENT AT ALL, unless this request is a
 *    visit-flagged session meeting an exhausted title with backorders on. Not
 *    an empty span, not a hidden one: an ordinary shopper's HTML must contain
 *    no trace.
 *
 * ♿ The short label carries the full sentence in `.screen-reader-text`,
 *    matching the pattern the sold-out control next door already uses: three
 *    words are terse out of visual context.
 *
 * @param string $slug Title slug.
 * @return void
 */
function bhp_visit_shelf_render_backorder_line( $slug ) {
	if ( ! bhp_visit_shelf_title_is_backordered_for_request( $slug ) ) {
		return;
	}
	printf(
		'<span class="bhp-bundle-backorder-label">%1$s</span><span class="screen-reader-text">%2$s</span>',
		esc_html( bhp_visit_shelf_backorder_label() ),
		esc_html( bhp_visit_shelf_backorder_message() )
	);
}
