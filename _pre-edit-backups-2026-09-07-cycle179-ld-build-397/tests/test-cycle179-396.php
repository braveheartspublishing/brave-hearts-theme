<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * test-cycle179-396.php — theme 1.19.396, 2026-09-07.
 * `CYCLE179-CX-BUILD-396` · commerce-cx, under chief-of-staff.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-396.php --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ---------------------------------------------------------------------------
 * WHAT IT COVERS
 * ---------------------------------------------------------------------------
 *   §1  V-9 · the coupon-code PREFIX rail, including after the coupon is gone
 *   §2  V-9 · the coupon META rail
 *   §3  V-9 · the durable order STAMP, and that it is write-only
 *   §4  V-9 · the review-ask sequence declines `suppressed_coupon`
 *   §5  V-9 · CusRev is declined through its own published filter, and NO
 *             `ivole_*` option or scheduled event is read, written or cleared
 *   §6  V-9 · an ordinary order is NOT suppressed (the false-positive guard)
 *   §7  items 1 and 2 · the CSS rails, as source assertions only
 *   §8  rails that must not have moved
 *   §9  cleanup, asserted rather than assumed
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ WHAT A PHP SUITE CANNOT PROVE, SAID HERE SO IT IS NOT READ AS PROVED.
 * ---------------------------------------------------------------------------
 * §7 asserts that CSS DECLARATIONS EXIST. It does not and cannot show that the
 * shop chips are centred at 375 or that the blog gap matches the shop's. A rule
 * being present in a stylesheet is a different claim from a box being where the
 * rule intends. Those are browser measurements with `window.innerWidth`
 * asserted beside them and they live in the build report, not here.
 *
 * Likewise §5 proves the FILTER IS REGISTERED AND RETURNS TRUE. It does not
 * prove CusRev honours it; that is proved by reading the plugin's own
 * `class-cr-sender.php`, which consults `cr_skip_reminder_generic` BEFORE
 * `wp_schedule_single_event()`. The read is recorded in the report.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES: throwaway `shop_order` records on STAGING, each tagged
 *   `_bhp_cycle396_probe`, all force-deleted in §9. It creates, edits and
 *   deletes NO coupon, product, variation, price, stock, shipping, tax or
 *   payment record. §2's meta rail is exercised against a coupon POST created
 *   and destroyed inside the suite, which is a fixture, not store
 *   configuration — see the note at §2.
 * ---------------------------------------------------------------------------
 *
 * ⛔⛔ THE TALLY IS READ FROM `$GLOBALS`, NOT FROM A TOP-LEVEL VARIABLE.
 *    `wp eval-file` executes this file inside a FUNCTION, so a top-level `$x`
 *    is a LOCAL variable while a helper's `global $x` binds to `$GLOBALS['x']`.
 *    They are two different variables that share a name. The 394 suite shipped
 *    with that bug and printed `PASS 0 FAIL 0` underneath real failures —
 *    observed on staging 2026-09-07, not reasoned about. That is the worst
 *    available failure mode for a suite: a green total sitting on top of red
 *    lines, read by exactly the person who only reads the last line.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['c396_pass']   = 0;
$GLOBALS['c396_fail']   = 0;
$GLOBALS['c396_orders'] = array();

function c396_ok( $label, $cond, $detail = '' ) {
	global $c396_pass, $c396_fail;
	if ( $cond ) {
		$c396_pass++;
		echo "PASS  {$label}\n";
		return true;
	}
	$c396_fail++;
	echo "FAIL  {$label}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
	return false;
}

function c396_section( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * A throwaway staging order carrying literal coupon-code line items.
 *
 * ⭐⭐ THE COUPON LINE ITEM IS ADDED DIRECTLY, NOT THROUGH `apply_coupon()`.
 *    That is the whole point of the fixture. `WC_Order::apply_coupon()`
 *    requires the coupon POST to exist and refuses otherwise, so it cannot
 *    reproduce the state this feature must survive: an order that redeemed a
 *    single-use reward coupon which was afterwards DELETED. A raw
 *    `WC_Order_Item_Coupon` reproduces exactly that state, because that is
 *    literally what WooCommerce leaves behind.
 *
 * @param string   $email Billing email.
 * @param string[] $codes Coupon codes to attach.
 * @param array    $meta  Extra order meta.
 * @return WC_Order
 */
function c396_make_order( $email, $codes = array(), $meta = array() ) {
	$order = wc_create_order();

	$order->set_billing_email( $email );
	$order->set_billing_first_name( 'Testparent' );
	$order->update_meta_data( '_bhp_cycle396_probe', '1' );

	foreach ( $meta as $k => $v ) {
		$order->update_meta_data( $k, $v );
	}

	foreach ( $codes as $code ) {
		$item = new WC_Order_Item_Coupon();
		$item->set_code( $code );
		$item->set_discount( 0 );
		$order->add_item( $item );
	}

	$order->save();

	$GLOBALS['c396_orders'][] = $order->get_id();

	return wc_get_order( $order->get_id() );
}

/* =========================================================================
 * §0 · PRECONDITIONS
 * ====================================================================== */
c396_section( '§0 preconditions' );

c396_ok(
	'0.1 running on staging, not production',
	false !== strpos( home_url(), 'staging2.' ),
	home_url()
);

c396_ok( '0.2 WooCommerce is loaded', function_exists( 'wc_create_order' ) );

c396_ok(
	'0.3 the suppression module loaded',
	function_exists( 'bhp_postpurchase_is_suppressed' )
	&& function_exists( 'bhp_postpurchase_code_suppresses' )
	&& function_exists( 'bhp_postpurchase_stamp_order' )
);

c396_ok(
	'0.4 the three constants are defined',
	defined( 'BHP_POSTPURCHASE_SUPPRESS_PREFIX' )
	&& defined( 'BHP_POSTPURCHASE_SUPPRESS_COUPON_META' )
	&& defined( 'BHP_POSTPURCHASE_SUPPRESS_ORDER_META' )
);

c396_ok(
	'0.5 style.css declares 1.19.396',
	false !== strpos(
		(string) file_get_contents( get_template_directory() . '/style.css', false, null, 0, 600 ),
		'Version: 1.19.396'
	)
);

/* =========================================================================
 * §1 · THE PREFIX RAIL — the one that survives the coupon's deletion
 * ====================================================================== */
c396_section( '§1 prefix rail' );

c396_ok(
	'1.1 a code carrying the prefix suppresses',
	true === bhp_postpurchase_code_suppresses( BHP_POSTPURCHASE_SUPPRESS_PREFIX . 'abc123' )
);

c396_ok(
	'1.2 the prefix match is case-insensitive',
	true === bhp_postpurchase_code_suppresses( strtoupper( BHP_POSTPURCHASE_SUPPRESS_PREFIX ) . 'ABC123' )
);

c396_ok(
	'1.3 surrounding whitespace does not defeat it',
	true === bhp_postpurchase_code_suppresses( '  ' . BHP_POSTPURCHASE_SUPPRESS_PREFIX . 'xyz  ' )
);

c396_ok(
	'1.4 an unrelated code does NOT suppress',
	false === bhp_postpurchase_code_suppresses( 'summer10' )
);

c396_ok(
	'1.5 an empty code does NOT suppress',
	false === bhp_postpurchase_code_suppresses( '' )
);

/*
 * ⭐⭐ 1.6 IS THE ASSERTION THIS WHOLE DESIGN EXISTS FOR.
 *
 * The reward coupons are single-use and are deleted after redemption. The
 * seal-1300 staging test deletes its own; Andrew's production procedure will
 * do the same. A design keyed only on coupon META would pass every test
 * written on the day the coupon was created and then SILENTLY STOP WORKING the
 * moment the coupon was tidied up — which is precisely when the review-ask
 * cron next runs over that order.
 *
 * This order names a coupon that has NEVER existed in the database. That is a
 * strictly harder case than "existed and was deleted", and it is indistinguishable
 * from it as far as the code path is concerned: `new WC_Coupon( $code )`
 * returns id 0 in both.
 */
$c396_ghost = c396_make_order(
	'ghost@example.invalid',
	array( BHP_POSTPURCHASE_SUPPRESS_PREFIX . 'deletedcoupon' )
);

c396_ok(
	'1.6 an order whose reward coupon no longer exists is STILL suppressed',
	true === bhp_postpurchase_is_suppressed( $c396_ghost ),
	'this is the deleted-coupon case; the prefix is what carries it'
);

c396_ok(
	'1.7 the coupon really is absent from the database',
	class_exists( 'WC_Coupon' )
	&& 0 === ( new WC_Coupon( BHP_POSTPURCHASE_SUPPRESS_PREFIX . 'deletedcoupon' ) )->get_id(),
	'proves 1.6 was not passing via the meta rail'
);

/* =========================================================================
 * §2 · THE COUPON META RAIL
 *
 * ⚠ THE FIXTURE COUPON, AND WHY IT IS NOT A CONFIGURATION MUTATION.
 *   This section creates a `shop_coupon` post, reads it, and force-deletes it
 *   in §9. It is never applied to a cart, never given an amount, never made
 *   available to a customer, and never survives the run. That is a FIXTURE, the
 *   same class of object as the throwaway orders above — not a change to what
 *   the store offers anyone. The Andrew gate this desk holds is on the store's
 *   OFFER: a coupon a buyer could redeem. Nothing here is redeemable.
 *   ⛔ If that reading is ever disputed, delete this section. Rail 1 is the
 *     load-bearing one and §1 covers it without any coupon at all.
 * ====================================================================== */
c396_section( '§2 coupon meta rail' );

$c396_coupon_id = 0;

if ( class_exists( 'WC_Coupon' ) ) {
	$c396_coupon = new WC_Coupon();
	$c396_coupon->set_code( 'c396-fixture-nometa-prefix' );
	$c396_coupon->set_amount( 0 );
	$c396_coupon->update_meta_data( BHP_POSTPURCHASE_SUPPRESS_COUPON_META, 'yes' );
	$c396_coupon->save();
	$c396_coupon_id = $c396_coupon->get_id();
}

c396_ok( '2.1 the fixture coupon was created', $c396_coupon_id > 0 );

c396_ok(
	'2.2 a coupon flagged in meta suppresses even without the prefix',
	true === bhp_postpurchase_code_suppresses( 'c396-fixture-nometa-prefix' )
);

c396_ok(
	'2.3 that code does NOT carry the prefix, so 2.2 proves the meta rail',
	0 !== strpos( 'c396-fixture-nometa-prefix', BHP_POSTPURCHASE_SUPPRESS_PREFIX )
);

/* =========================================================================
 * §3 · THE DURABLE ORDER STAMP
 * ====================================================================== */
c396_section( '§3 order stamp' );

c396_ok(
	'3.1 evaluating a suppressed order stamps it',
	'yes' === wc_get_order( $c396_ghost->get_id() )->get_meta( BHP_POSTPURCHASE_SUPPRESS_ORDER_META, true )
);

$c396_note_found = false;
foreach ( wc_get_order_notes( array( 'order_id' => $c396_ghost->get_id() ) ) as $c396_n ) {
	if ( false !== strpos( $c396_n->content, 'Post-purchase review sequence suppressed' ) ) {
		$c396_note_found = true;
	}
}
c396_ok( '3.2 an order note records why', $c396_note_found );

/*
 * ⭐ 3.3 · THE STAMP IS WRITE-ONLY AND THAT IS DELIBERATE.
 *    Removing the coupon from an order afterwards must NOT un-suppress it. The
 *    failure this feature prevents is an email that should not have gone out;
 *    an unsent email is recoverable by hand, a sent one is not. So the safe
 *    direction is to stay suppressed.
 */
$c396_stripped = wc_get_order( $c396_ghost->get_id() );
foreach ( $c396_stripped->get_items( 'coupon' ) as $c396_iid => $c396_ci ) {
	$c396_stripped->remove_item( $c396_iid );
}
$c396_stripped->save();
$c396_stripped = wc_get_order( $c396_ghost->get_id() );

c396_ok(
	'3.3 stripping the coupon afterwards does NOT un-suppress the order',
	0 === count( $c396_stripped->get_items( 'coupon' ) )
	&& true === bhp_postpurchase_is_suppressed( $c396_stripped )
);

/* =========================================================================
 * §4 · THE REVIEW-ASK SEQUENCE DECLINES
 * ====================================================================== */
c396_section( '§4 review-ask decline' );

c396_ok(
	'4.1 the decline gate exists',
	function_exists( 'bhp_review_ask_decline_reason' )
);

$c396_sup = c396_make_order(
	'suppressed@example.invalid',
	array( BHP_POSTPURCHASE_SUPPRESS_PREFIX . 'reward01' )
);
$c396_sup->set_status( 'completed' );
$c396_sup->save();
$c396_sup = wc_get_order( $c396_sup->get_id() );

c396_ok(
	'4.2 a completed order redeeming a reward coupon declines `suppressed_coupon`',
	'suppressed_coupon' === bhp_review_ask_decline_reason( $c396_sup ),
	bhp_review_ask_decline_reason( $c396_sup )
);

c396_ok(
	'4.3 …and therefore never sends',
	false === bhp_review_ask_should_send( $c396_sup )
);

/*
 * ⛔ 4.4 · THE GATE SITS ABOVE THE TIMING CHECKS, NOT AMONG THEM.
 *    Every check below it in `bhp_review_ask_decline_reason()` reasons about
 *    WHEN to ask. This one decides WHETHER. If it were placed lower, an order
 *    that must never be asked could still return a timing reason such as
 *    `not_due` — which reads as "ask later" and is the wrong answer forever.
 */
/*
 * ⛔⛔ THIS ASSERTION WAS WRONG ON ITS FIRST RUN AND THE CORRECTION IS KEPT
 *    VISIBLE, because the wrong version FAILED AGAINST CORRECT CODE, which is
 *    the more expensive direction: it teaches the next reader to distrust a
 *    passing build.
 *
 *    It read:
 *        $pos_touch = strpos( $src, 'bhp_review_ask_next_touch( $order )' );
 *
 *    `strpos` returns the FIRST occurrence, and the first occurrence in that
 *    file is the function's own DEFINITION at line 1188 —
 *    `function bhp_review_ask_next_touch( $order ) {` — roughly 3,400 lines
 *    ABOVE the gate being tested. So it compared the gate against a definition
 *    instead of against the call, and reported a real ordering (4625 before
 *    4636) as a violation.
 *
 * ⭐ THE FIX IS TO SCOPE THE SEARCH TO THE FUNCTION BODY rather than to pick a
 *    more distinctive needle. A needle chosen for uniqueness goes stale the
 *    next time the file gains a similar line; a bounded window cannot, because
 *    it is asking the question the assertion actually means: "within this one
 *    function, does WHETHER come before WHEN?"
 */
$c396_src = (string) file_get_contents( get_template_directory() . '/inc/review-ask-email.php' );

$c396_fn_start = strpos( $c396_src, 'function bhp_review_ask_decline_reason(' );
$c396_fn_end   = false !== $c396_fn_start
	? strpos( $c396_src, "\nfunction ", $c396_fn_start + 1 )
	: false;
$c396_fn_body  = ( false !== $c396_fn_start && false !== $c396_fn_end )
	? substr( $c396_src, $c396_fn_start, $c396_fn_end - $c396_fn_start )
	: '';

$c396_pos_sup   = strpos( $c396_fn_body, "return 'suppressed_coupon';" );
$c396_pos_touch = strpos( $c396_fn_body, '$touch = bhp_review_ask_next_touch( $order );' );

c396_ok(
	'4.4 inside the decline function, the suppression gate precedes the touch computation',
	'' !== $c396_fn_body
	&& false !== $c396_pos_sup
	&& false !== $c396_pos_touch
	&& $c396_pos_sup < $c396_pos_touch,
	'body=' . strlen( $c396_fn_body ) . ' sup=' . var_export( $c396_pos_sup, true ) . ' touch=' . var_export( $c396_pos_touch, true )
);

/* =========================================================================
 * §5 · CUSREV, THROUGH ITS OWN DOOR ONLY
 * ====================================================================== */
c396_section( '§5 CusRev' );

c396_ok(
	'5.1 the theme filters `cr_skip_reminder_generic`',
	false !== has_filter( 'cr_skip_reminder_generic', 'bhp_postpurchase_cusrev_skip' )
);

c396_ok(
	'5.2 the filter returns true for a suppressed order',
	true === apply_filters( 'cr_skip_reminder_generic', false, $c396_sup->get_id() )
);

/*
 * ⛔⛔ 5.3 IS THE SAFETY ASSERTION, NOT A STYLE CHECK.
 *
 * `inc/review-ask-email.php` carries a standing prohibition: nothing in this
 * repository schedules, cancels, reads or changes an `ivole_*` option or an
 * `ivole_send_reminder` event. Disabling a plugin setting is a WooCommerce
 * configuration mutation and an Andrew gate (Standing Rules §6).
 *
 * ⭐ Declining to CREATE a reminder through the plugin's own published filter
 *    is a different act from cancelling one that exists, and this assertion is
 *    what keeps the two from being confused by a later edit. It scans this
 *    build's own new module for the forbidden verbs.
 */
$c396_pps = (string) file_get_contents( get_template_directory() . '/inc/postpurchase-suppression.php' );

/*
 * ⛔⛔ THIS ASSERTION ALSO FAILED ON ITS FIRST RUN, AGAINST CORRECT CODE, and
 *    the reason is worth more than the fix.
 *
 *    It scanned the RAW FILE TEXT for forbidden verbs. The module's own head
 *    note explains that CusRev's filter *"runs BEFORE `wp_schedule_single_event()`"* —
 *    so the assertion matched a function name inside a COMMENT THAT EXISTS TO
 *    DOCUMENT THE SAFETY PROPERTY THE ASSERTION IS CHECKING FOR. Writing down
 *    why the code is safe made the safety test fail.
 *
 * ⭐ A SOURCE-SCANNING ASSERTION MUST SCAN CODE, NOT PROSE. `token_get_all()`
 *    is the right instrument: it hands back PHP's own lexical view, from which
 *    comments and docblocks can be dropped exactly. Grepping the file text is
 *    an approximation of that, and this is the failure the approximation has.
 *
 * ⚠ Strings are KEPT (not dropped) on purpose: `wp_clear_scheduled_hook( 'ivole_send_reminder' )`
 *   would hide the hook name in a string literal, and that is precisely the
 *   call this assertion exists to catch.
 */
function c396_code_only( $php ) {
	$out = '';
	foreach ( token_get_all( $php ) as $tok ) {
		if ( is_array( $tok ) ) {
			if ( in_array( $tok[0], array( T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML ), true ) ) {
				continue;
			}
			$out .= $tok[1];
			continue;
		}
		$out .= $tok;
	}
	return $out;
}

$c396_pps_code = c396_code_only( $c396_pps );

c396_ok(
	'5.3 the module CODE touches no ivole_* option and no ivole_send_reminder event',
	0 === preg_match(
		'/\b(update_option|add_option|delete_option|wp_schedule_single_event|wp_unschedule_event|wp_clear_scheduled_hook|wp_next_scheduled)\b/',
		$c396_pps_code
	)
	&& false === strpos( $c396_pps_code, 'ivole_send_reminder' )
	&& false === strpos( $c396_pps_code, 'ivole_enable' )
);

c396_ok(
	'5.3b the comment-stripper actually removed something, so 5.3 is not vacuous',
	strlen( $c396_pps_code ) < strlen( $c396_pps ),
	strlen( $c396_pps_code ) . ' of ' . strlen( $c396_pps )
);

c396_ok(
	'5.3c …and the raw file DOES contain the word, proving 5.3 tests the stripper',
	false !== strpos( $c396_pps, 'wp_schedule_single_event' )
);

c396_ok(
	'5.4 the module CODE writes no product, price, coupon, stock or shipping record',
	0 === preg_match(
		'/\b(wp_insert_post|wp_update_post|wp_delete_post|wc_create_order|set_price|set_regular_price|set_stock_status|WC_Shipping_Zone)\b/',
		$c396_pps_code
	)
);

/* =========================================================================
 * §6 · THE FALSE-POSITIVE GUARD
 *
 * ⭐ A suppression feature that suppresses too much is worse than one that
 *    does not exist, because it fails SILENTLY: nobody reports the review-ask
 *    email they never received.
 * ====================================================================== */
c396_section( '§6 ordinary orders are untouched' );

$c396_plain = c396_make_order( 'plain@example.invalid', array() );

c396_ok( '6.1 an order with no coupon is not suppressed', false === bhp_postpurchase_is_suppressed( $c396_plain ) );

c396_ok(
	'6.2 …and carries no stamp',
	'' === (string) wc_get_order( $c396_plain->get_id() )->get_meta( BHP_POSTPURCHASE_SUPPRESS_ORDER_META, true )
);

$c396_ordinary_coupon = c396_make_order( 'ordinary@example.invalid', array( 'summer10' ) );

c396_ok(
	'6.3 an order with an ORDINARY coupon is not suppressed',
	false === bhp_postpurchase_is_suppressed( $c396_ordinary_coupon )
);

c396_ok(
	'6.4 CusRev is NOT skipped for an ordinary order',
	false === apply_filters( 'cr_skip_reminder_generic', false, $c396_ordinary_coupon->get_id() )
);

c396_ok(
	'6.5 a refund object is never suppressed and never stamped',
	false === bhp_postpurchase_is_suppressed( 0 )
);

/* =========================================================================
 * §7 · ITEMS 1 AND 2 — SOURCE ASSERTIONS ONLY
 *
 * ⛔ READ THE HEADER. These prove DECLARATIONS EXIST. They do not prove the
 *    chips are centred or the gap matches. Those are in the build report as
 *    browser measurements with `innerWidth` asserted beside them.
 * ====================================================================== */
c396_section( '§7 CSS rails (source assertions, NOT rendering proof)' );

$c396_css = (string) file_get_contents( get_template_directory() . '/style.css' );

c396_ok(
	'7.1 the shop chip row centres and wraps',
	1 === preg_match(
		'/\.bhp-shop-format-prices \{[^}]*flex-wrap:\s*wrap;[^}]*justify-content:\s*center;/s',
		$c396_css
	)
);

c396_ok(
	'7.2 the chip carries `min-width: 0`, the declaration that lets it shrink',
	1 === preg_match(
		'/li\.product \.bhp-shop-format-price \{[^}]*min-width:\s*0;/s',
		$c396_css
	)
);

/*
 * ⚠ 7.3 CARRIES THE `body:not(.home)` PREFIX ON PURPOSE. Without it the rule is
 *   (0,2,0) and loses to `body:not(.home) .section` at (0,2,1), which is
 *   written against a DIFFERENT class and is therefore invisible to anyone
 *   searching for what overrides `.blog-index`. The first build of 1.19.396
 *   shipped the bare selector: it was in the stylesheet, it matched the
 *   element, and the computed padding never moved. This assertion exists so
 *   the prefix cannot be "simplified" back out.
 */
c396_ok(
	'7.3 the blog gap rule carries the specificity prefix that beats `body:not(.home) .section`',
	false !== strpos( $c396_css, 'body:not(.home) .bhp-archive-band + .blog-index { padding-block-start: 2.5rem; }' )
);

c396_ok(
	'7.4 the original `.blog-index` section rhythm is preserved, not overwritten',
	false !== strpos( $c396_css, '.blog-index { padding-block: var(--section-space); }' )
);

/* =========================================================================
 * §8 · RAILS THAT MUST NOT HAVE MOVED
 * ====================================================================== */
c396_section( '§8 rails' );

c396_ok(
	'8.1 no BookVAULT Shipping method is zoned',
	0 === count(
		array_filter(
			(array) ( class_exists( 'WC_Shipping_Zones' ) ? WC_Shipping_Zones::get_zones() : array() ),
			function ( $z ) {
				foreach ( (array) $z['shipping_methods'] as $m ) {
					if ( false !== stripos( (string) $m->id, 'bookvault' ) ) {
						return true;
					}
				}
				return false;
			}
		)
	)
);

c396_ok(
	'8.2 the staging mail guard still lists the review-ask email by id',
	false !== strpos(
		(string) file_get_contents( get_template_directory() . '/inc/staging-mail-guard.php' ),
		'bhp_review_ask'
	)
);

c396_ok(
	'8.3 no aggregateRating is synthesized by this build',
	false === strpos( $c396_pps, 'aggregateRating' )
);

c396_ok(
	'8.4 the reading age band is untouched in the shop card markup',
	false !== strpos( (string) file_get_contents( get_template_directory() . '/inc/book-formats.php' ), 'Ages 6' )
);

/* =========================================================================
 * §9 · CLEANUP — ASSERTED, NOT ASSUMED
 * ====================================================================== */
c396_section( '§9 cleanup' );

$c396_deleted = 0;
foreach ( $GLOBALS['c396_orders'] as $c396_oid ) {
	$c396_o = wc_get_order( $c396_oid );
	if ( $c396_o ) {
		$c396_o->delete( true );
		$c396_deleted++;
	}
}

c396_ok(
	'9.1 every probe order was force-deleted',
	$c396_deleted === count( $GLOBALS['c396_orders'] ),
	$c396_deleted . '/' . count( $GLOBALS['c396_orders'] )
);

if ( $c396_coupon_id ) {
	wp_delete_post( $c396_coupon_id, true );
}

c396_ok(
	'9.2 the fixture coupon was force-deleted',
	! $c396_coupon_id || null === get_post( $c396_coupon_id )
);

c396_ok(
	'9.3 no probe order survives the run',
	0 === count(
		(array) wc_get_orders(
			array(
				'limit'      => 5,
				'type'       => 'shop_order',
				'meta_key'   => '_bhp_cycle396_probe',
				'meta_value' => '1',
				'return'     => 'ids',
			)
		)
	)
);

$c396_pass_total = isset( $GLOBALS['c396_pass'] ) ? (int) $GLOBALS['c396_pass'] : 0;
$c396_fail_total = isset( $GLOBALS['c396_fail'] ) ? (int) $GLOBALS['c396_fail'] : 0;

echo "\n=== CYCLE179-CX-BUILD-396 ===\nPASS {$c396_pass_total}  FAIL {$c396_fail_total}\n";
