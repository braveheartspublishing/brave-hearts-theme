<?php
/**
 * postpurchase-suppression.php — theme 1.19.396, 2026-09-07.
 * `CYCLE179-CX-BUILD-396` · commerce-cx, under chief-of-staff.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * V-9 — A REDEEMED 100 PERCENT REWARD COUPON MUST NOT PULL THE BUYER INTO THE
 *       POST-PURCHASE REVIEW SEQUENCE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Raised by `marketing-growth`, seal 1304, out of the video-testimonial
 * work. RELAYED to this desk through the Chief of Staff, NOT witnessed
 * first-hand (Standing Rules §9.2).
 *
 * ⭐ THE REASON, WHICH IS NOT TIDINESS. The reward coupon exists so a parent who
 *    already gave a video testimonial can be sent a free coloring book. Letting
 *    that order fall into the review-ask sequence asks that same parent, a few
 *    days later, to write a review of a book that was given to them in exchange
 *    for a testimonial. ⛔ That is the FTC problem, not a UX problem: it turns a
 *    disclosed incentive into an undisclosed one and puts an incentivized review
 *    on a store page where the incentive is invisible. The ads-knowledge doctrine lane
 *    (`CYCLE179-ADS-TESTIMONIAL-DOCTRINE`, 16 CFR 465) carries the reasoning.
 *
 * ⛔⛔ WHAT THIS FILE DOES NOT DO, STATED FIRST SO IT IS NOT ASSUMED.
 *
 *   - It creates, edits, reads-to-change or deletes NO coupon, product,
 *     variation, price, stock, shipping, tax, payment or checkout record, on
 *     any environment. It reads coupon CODES off an order that already exists.
 *   - It writes NO `ivole_*` option and schedules or cancels NO
 *     `ivole_send_reminder` event. The CusRev integration below is that
 *     plugin's OWN published extension point, `cr_skip_reminder_generic`,
 *     which is consulted BEFORE a reminder is ever scheduled. Read
 *     `includes/emails/class-cr-sender.php` line ~138: the plugin's own comment
 *     calls it *"a generic filter to skip scheduling a review reminder"*.
 *     ⭐ That distinction is the whole reason this is allowed: the standing
 *     prohibition in `inc/review-ask-email.php` is against touching CusRev's
 *     SETTINGS and its ALREADY-SCHEDULED EVENTS. Declining to create one
 *     through the plugin's own door is neither.
 *   - It sends nothing, and suppresses rather than sends, so it cannot
 *     misfire in the sending direction.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * HOW AN ORDER IS RECOGNISED — TWO RAILS, AND WHY IT MUST BE TWO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * RAIL 1 · CODE PREFIX. Any coupon whose code begins `BHP-THANKS-`
 *          (case-insensitive; WooCommerce lowercases codes on save anyway).
 *
 * RAIL 2 · COUPON META FLAG. `_bhp_suppress_postpurchase` = `yes` on the
 *          coupon post, for a coupon created without the prefix.
 *
 * ⭐⭐ WHY BOTH, AND WHY THE PREFIX IS THE LOAD-BEARING ONE. These reward
 *    coupons are SINGLE USE and are expected to be DELETED after redemption —
 *    the seal 1300 staging test deletes its own coupon as its last step, and
 *    Andrew's production procedure will do the same. Once the coupon post is
 *    gone, RAIL 2 CANNOT BE EVALUATED AT ALL: `new WC_Coupon( $code )` returns
 *    an object with id 0 and no meta. The order, however, keeps the coupon
 *    CODE forever in its coupon line items. So:
 *
 *      - the prefix survives the coupon's deletion; the meta flag does not;
 *      - the meta flag is the convenience for a coupon someone names
 *        differently, and it is only reliable while the coupon still exists.
 *
 * ⛔ A DESIGN THAT USED THE META FLAG ALONE WOULD PASS EVERY TEST WRITTEN ON
 *    THE DAY THE COUPON WAS CREATED AND THEN SILENTLY STOP WORKING once the
 *    coupon was cleaned up — which is exactly when the review-ask cron next
 *    runs. That failure mode is the reason the prefix exists.
 *
 * RAIL 3 · THE STAMP. `_bhp_postpurchase_suppressed` = `yes` is written onto
 *          the ORDER the first time the order is seen carrying such a coupon.
 *          It is checked first and short-circuits both other rails.
 *
 * ⭐ THE STAMP IS THE DURABLE RECORD. It makes the decision auditable after the
 *    fact ("why did order 561 never get an ask?" is answerable from the order
 *    itself), and it makes the suppression survive even a change to the prefix
 *    constant. It is written on order creation and on completion, and also
 *    lazily by the predicate itself, so an order that predates this file still
 *    gets stamped the first time anything asks about it.
 *
 * ⚠ THE STAMP IS ONLY EVER WRITTEN, NEVER CLEARED. Removing a coupon from an
 *   order after the fact does not un-suppress it. That is deliberate and it is
 *   the safe direction: the failure this file exists to prevent is an email
 *   that should not have gone out, and an unsent email is recoverable by hand
 *   while a sent one is not.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHO CONSULTS IT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   1. `bhp_review_ask_decline_reason()` — the store's own two-touch sequence.
 *      Declines with `suppressed_coupon`, which appears in the run summary and
 *      the KPI report like every other decline reason.
 *   2. `cr_skip_reminder_generic` — CusRev, before it schedules anything.
 *   3. The future day-30 testimonial ask — NOT BUILT, and this file does not
 *      pretend it is. `bhp_postpurchase_is_suppressed()` is the single rail it
 *      must call, and `bhp_postpurchase_suppressed` is the filter that lets it
 *      be extended without another copy of this logic.
 *
 * ⛔ WHAT IS OUT OF REACH FROM THE THEME, AND IS THEREFORE NOT CLAIMED.
 *    Seal 1299 condition (3) also asks that $0 reward orders be excluded from
 *    revenue, AOV and the plugin's `purchase` event. The bundle plugin already
 *    owns that rail: `BHP_Order_Provenance::classify()` honours an order-meta
 *    override, `_bhp_order_provenance_override`. ⚠ BUT the override is
 *    validated against the plugin's existing `ORIGIN_*` constants and NONE of
 *    them means "incentive fulfillment". Stamping one of the test origins onto
 *    a genuine reward shipment would file a real fulfillment as a fake order.
 *    ➡ NOT DONE HERE. It needs a new `ORIGIN_INCENTIVE_FULFILLMENT` constant in
 *      the PLUGIN, which is outside this workstream's declared scope. It is
 *      escalated in the build report as a decision, not silently worked around.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The reserved code prefix. Any coupon code beginning with this suppresses the
 * post-purchase sequence for the order that redeems it.
 *
 * ⚠ Changing this string does NOT un-suppress orders already stamped, by
 *   design — see RAIL 3 above.
 */
if ( ! defined( 'BHP_POSTPURCHASE_SUPPRESS_PREFIX' ) ) {
	define( 'BHP_POSTPURCHASE_SUPPRESS_PREFIX', 'bhp-thanks-' );
}

/** Coupon-post meta flag, for a coupon that does not carry the prefix. */
if ( ! defined( 'BHP_POSTPURCHASE_SUPPRESS_COUPON_META' ) ) {
	define( 'BHP_POSTPURCHASE_SUPPRESS_COUPON_META', '_bhp_suppress_postpurchase' );
}

/** Durable order stamp. Written once, never cleared. */
if ( ! defined( 'BHP_POSTPURCHASE_SUPPRESS_ORDER_META' ) ) {
	define( 'BHP_POSTPURCHASE_SUPPRESS_ORDER_META', '_bhp_postpurchase_suppressed' );
}

/**
 * Does this coupon CODE suppress the post-purchase sequence?
 *
 * Rail 1 (prefix) is answered from the string alone and needs no database read,
 * which is what makes it work after the coupon has been deleted. Rail 2 is only
 * consulted when rail 1 says no AND the coupon still exists.
 *
 * @param string $code Coupon code as stored on the order line item.
 * @return bool
 */
function bhp_postpurchase_code_suppresses( $code ) {
	$code = strtolower( trim( (string) $code ) );

	if ( '' === $code ) {
		return false;
	}

	// RAIL 1 — prefix. Survives the coupon's deletion.
	if ( 0 === strpos( $code, strtolower( BHP_POSTPURCHASE_SUPPRESS_PREFIX ) ) ) {
		return true;
	}

	// RAIL 2 — coupon meta. Only answerable while the coupon post exists.
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return false;
	}

	$coupon = new WC_Coupon( $code );

	if ( ! $coupon->get_id() ) {
		// ⭐ Deleted or never existed. NOT an error and NOT a suppression:
		//    rail 1 already had its say, and inventing a "yes" here would
		//    suppress every order whose coupon has been tidied up.
		return false;
	}

	return 'yes' === $coupon->get_meta( BHP_POSTPURCHASE_SUPPRESS_COUPON_META, true );
}

/**
 * Is this order suppressed from every post-purchase ask?
 *
 * Order of checks is deliberate: the stamp first (cheap, durable, survives
 * coupon deletion), then the order's own coupon codes.
 *
 * @param WC_Order|int $order Order or order id.
 * @return bool
 */
function bhp_postpurchase_is_suppressed( $order ) {
	if ( ! $order instanceof WC_Order ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order ) : false;
	}

	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	/*
	 * ⛔ THE REFUND TRAP, the same one `inc/review-ask-email.php` documents.
	 *    A WC_Order_Refund is an order object. It has no coupons and must not
	 *    be stamped.
	 */
	if ( method_exists( $order, 'get_type' ) && 'shop_order' !== $order->get_type() ) {
		return false;
	}

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐⭐ RAIL 0 — 1.19.397. THE PLUGIN'S ORDER ORIGIN IS THE SOURCE OF
	 *     TRUTH, AND THIS MODULE NOW ASKS IT RATHER THAN DECIDING AGAIN.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐ WHAT WAS ACTUALLY WRONG BEFORE, AND IT WAS NOT A BUG. Rails 1–3 below
	 *    are correct and were proved on a real redemption under seal 1300.
	 *    ⛔ THE DEFECT WAS THAT THEY WERE THE SECOND ANSWER TO A QUESTION THE
	 *    BUNDLE PLUGIN NOW ALSO ANSWERS. `BHP_Order_Provenance` decides whether
	 *    a reward order counts as revenue; this file decides whether it gets a
	 *    review ask. Those are two consequences of ONE fact, and two detectors
	 *    for one fact drift — silently, and in the worst direction:
	 *
	 *      · a prefix added HERE but not THERE  → a reward order is spared the
	 *        review ask and still counts as a sale in Andrew's numbers;
	 *      · a prefix added THERE but not HERE  → a reward order is excluded
	 *        from revenue and still emails the family asking them to review
	 *        the book they were given for writing a testimonial.
	 *
	 *    ⭐ Neither of those fails a test, appears in a log, or looks wrong to
	 *       anyone reading either file. That is what makes it worth a rail.
	 *
	 * ⛔ RAILS 1–3 ARE KEPT, NOT REPLACED, AND FOR EXACTLY ONE REASON: the
	 *    plugin can be deactivated. A theme whose review-ask suppression stops
	 *    working when a commerce plugin is switched off would email a real
	 *    reward recipient, and "the plugin was off" is not a defence anyone
	 *    gets to make to that family. ⭐ THE PRECEDENCE IS WHAT MATTERS: when
	 *    the plugin IS present its answer wins, so the two can never disagree
	 *    in the case that actually occurs.
	 *
	 * ⚠️ IT ONLY EVER ADDS SUPPRESSION. A `false` from the plugin means "not a
	 *    reward order", never "do not suppress" — rails 1–3 still run below and
	 *    can still say yes. The `bhp-thanks-` prefix predates this plugin
	 *    constant and must keep working on its own.
	 */
	if ( function_exists( 'bhp_is_incentive_fulfillment_order' ) && bhp_is_incentive_fulfillment_order( $order ) ) {
		bhp_postpurchase_stamp_order( $order );
		return (bool) apply_filters( 'bhp_postpurchase_suppressed', true, $order );
	}

	// RAIL 3 — the stamp. Short-circuits everything.
	if ( 'yes' === $order->get_meta( BHP_POSTPURCHASE_SUPPRESS_ORDER_META, true ) ) {
		return (bool) apply_filters( 'bhp_postpurchase_suppressed', true, $order );
	}

	$suppressed = false;

	foreach ( bhp_postpurchase_order_coupon_codes( $order ) as $code ) {
		if ( bhp_postpurchase_code_suppresses( $code ) ) {
			$suppressed = true;
			break;
		}
	}

	if ( $suppressed ) {
		bhp_postpurchase_stamp_order( $order );
	}

	/**
	 * Final say, so a future post-purchase surface (the day-30 testimonial ask)
	 * can extend the rule without duplicating any of it.
	 *
	 * @param bool     $suppressed Whether the order is suppressed.
	 * @param WC_Order $order      The order.
	 */
	return (bool) apply_filters( 'bhp_postpurchase_suppressed', $suppressed, $order );
}

/**
 * Every coupon code on an order, across WooCommerce versions.
 *
 * ⚠ `WC_Order::get_coupon_codes()` exists from WooCommerce 3.7. The older
 *   `get_used_coupons()` is kept as a fallback rather than assumed absent,
 *   because this runs on whatever the site is actually running, not on what a
 *   document says it runs.
 *
 * @param WC_Order $order Order.
 * @return string[]
 */
function bhp_postpurchase_order_coupon_codes( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return array();
	}

	if ( method_exists( $order, 'get_coupon_codes' ) ) {
		return (array) $order->get_coupon_codes();
	}

	if ( method_exists( $order, 'get_used_coupons' ) ) {
		return (array) $order->get_used_coupons();
	}

	return array();
}

/**
 * Write the durable stamp, once.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function bhp_postpurchase_stamp_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( 'yes' === $order->get_meta( BHP_POSTPURCHASE_SUPPRESS_ORDER_META, true ) ) {
		return;
	}

	$order->update_meta_data( BHP_POSTPURCHASE_SUPPRESS_ORDER_META, 'yes' );
	$order->save();

	if ( method_exists( $order, 'add_order_note' ) ) {
		$order->add_order_note(
			__( 'Post-purchase review sequence suppressed: this order redeemed a reward coupon. No review-ask email and no CusRev reminder will be scheduled for it.', 'brave-hearts' )
		);
	}
}

/**
 * Stamp at the moment the order is created, so the decision is on the record
 * before any scheduler has had a chance to look at it.
 *
 * ⭐ BOTH checkout paths are covered. The store runs full WooCommerce Blocks,
 *    so `woocommerce_store_api_checkout_order_processed` is the one that
 *    actually fires for a customer; the classic hook is registered too because
 *    an admin-created or programmatically created order takes the other path.
 *    This mirrors what `includes/school-visit-pickup.php` already does.
 *
 * @param mixed $order_or_id Order object (Store API) or order id (classic).
 * @return void
 */
function bhp_postpurchase_stamp_on_create( $order_or_id ) {
	$order = $order_or_id instanceof WC_Order
		? $order_or_id
		: ( function_exists( 'wc_get_order' ) ? wc_get_order( $order_or_id ) : false );

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	// The predicate stamps as a side effect when it finds a suppressing coupon.
	bhp_postpurchase_is_suppressed( $order );
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_postpurchase_stamp_on_create', 5 );
add_action( 'woocommerce_checkout_order_processed', 'bhp_postpurchase_stamp_on_create', 5 );
add_action( 'woocommerce_order_status_completed', 'bhp_postpurchase_stamp_on_create', 5 );

/**
 * CusRev — decline the reminder before one is scheduled.
 *
 * ⛔ This is the plugin's own published filter and it runs BEFORE
 *    `wp_schedule_single_event()`. Nothing here reads or writes an `ivole_*`
 *    option, and nothing here unschedules an existing event. The standing
 *    prohibition in `inc/review-ask-email.php` is intact.
 *
 * ⚠ `$order_id` is what CusRev passes to this filter, not an order object.
 *
 * @param bool $skip     Whether CusRev should skip.
 * @param int  $order_id Order id.
 * @return bool
 */
function bhp_postpurchase_cusrev_skip( $skip, $order_id ) {
	if ( $skip ) {
		return $skip;
	}

	return bhp_postpurchase_is_suppressed( $order_id );
}
add_filter( 'cr_skip_reminder_generic', 'bhp_postpurchase_cusrev_skip', 10, 2 );
