<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE SEAL-965 TWO-TOUCH REVIEW SEQUENCE SUITE — theme 1.19.362, 2026-09-05,
 * `CYCLE179-LD-REVIEW-SEQ`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-review-seq.php --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ ALWAYS PASS `--url`. Under WP-CLI `$_SERVER['HTTP_HOST']` is unset, so any
 *    suite routing through `BHP_Analytics_Config::is_staging()` takes the wrong
 *    branch without it and reports a phantom pass or a phantom failure.
 *    (`docs/RUNBOOK.md`, added 2026-09-02, `CYCLE179-LD-9`.)
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE IS ACTUALLY GUARDING
 * ---------------------------------------------------------------------------
 * `tests/test-cycle169-review-ask.php` remains the 1.19.317 regression record
 * and runs the superseded single-ask behaviour under a compatibility harness.
 * ⛔ THIS SUITE COVERS ONLY WHAT SEAL 965 ADDED, and the two do not cover for
 *    each other:
 *
 *   §1  the day math, which is the half a wrong answer sends a real email on
 *       the wrong day for: one book versus two, visit lane versus web lane,
 *       and the visit date rather than the completion timestamp;
 *   §2  touch 2 at touch 1 + 7, and `touch1_date_unknown` when nobody
 *       recorded when touch 1 went out;
 *   §3  the reminder is suppressed by a site review, INCLUDING an unapproved
 *       one, which is the assertion that stops the store chasing a parent for
 *       a review they already wrote;
 *   §4  the 90-day customer cooldown gates touch 1 and does NOT gate touch 2,
 *       which is the defect that would silently turn the sequence back into a
 *       single ask;
 *   §5  the morning send window;
 *   §6  PENDING-COPY can never be sent;
 *   §7  the copy rails - no em dash, no "we", the merge slots resolve, and the
 *       approved visit strings are Merry's verbatim.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ IT REFUSES TO RUN ANYWHERE BUT STAGING, AND IT SENDS NOTHING, ANYWHERE
 * ---------------------------------------------------------------------------
 * §0 aborts before a single assertion unless
 * `bhp_staging_mail_guard_is_staging()` says so.
 *
 * ⭐ NOTHING IS EVER HANDED TO A TRANSPORT BY THIS SUITE AT ALL. Unlike the
 *    1.19.317 suite it never calls `bhp_review_ask_send()` or `trigger()`, and
 *    never re-enables the email. Every assertion below reads a decision
 *    function or a copy array. ⛔ There is therefore no path from this file to
 *    `wp_mail()`, which is a stronger guarantee than short-circuiting one.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES, AND HOW IT IS CLEANED UP
 * ---------------------------------------------------------------------------
 * Temporary WooCommerce orders on STAGING, every one tagged
 * `_bhp_cycle179_probe`, every one force-deleted in §8. Billing addresses are
 * all `@example.com`, which RFC 2606 reserves and which is undeliverable by
 * construction.
 *
 * A single held product review is created in §3 and deleted in §8. It is
 * created with `wp_insert_comment()` at `comment_approved = 0`, which is where
 * `bhp_review_force_moderation()` would put it anyway.
 *
 * The four registry options are snapshotted in §0 and restored in §8.
 *
 * ⛔ IT TOUCHES NO PRODUCT, PRICE, COUPON, STOCK, SHIPPING, TAX, PAYMENT OR
 *    CHECKOUT RECORD, AND NO WooCommerce SETTING, ON ANY ENVIRONMENT.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['bhp_rs_pass']     = 0;
$GLOBALS['bhp_rs_fail']     = 0;
$GLOBALS['bhp_rs_orders']   = array();
$GLOBALS['bhp_rs_comments'] = array();

/**
 * One assertion.
 *
 * @param string $label  What is being asserted.
 * @param bool   $cond   The result.
 * @param string $detail Optional detail printed on failure.
 * @return void
 */
function bhp_rs_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_rs_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_rs_fail']++;
		echo "FAIL  {$label}" . ( '' !== $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

/**
 * A section heading.
 *
 * @param string $title Heading.
 * @return void
 */
function bhp_rs_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * A probe order.
 *
 * ⚠ CREATED, THEN AGED, in that order and for the reason the 1.19.317 suite
 *   records: `set_status( 'completed' )` fires the transition that writes
 *   `date_completed`, so the age must be applied AFTER the first save or the
 *   transition overwrites it.
 *
 * @param string $email    Billing email.
 * @param int    $days_ago How long ago it completed.
 * @param array  $meta     Extra order meta.
 * @param array  $book_ids Product ids to add as line items.
 * @return WC_Order
 */
/** A named callback, so `remove_filter()` can actually remove it. */
function bhp_rs_touch2_nine() {
	return 9;
}

function bhp_rs_make_order( $email, $days_ago, $meta = array(), $book_ids = array() ) {
	$order = wc_create_order();

	$order->set_billing_email( $email );
	$order->set_billing_first_name( 'Testparent' );
	$order->update_meta_data( '_bhp_cycle179_probe', '1' );

	foreach ( $meta as $key => $value ) {
		$order->update_meta_data( $key, $value );
	}

	foreach ( $book_ids as $book_id ) {
		$product = wc_get_product( (int) $book_id );

		if ( $product ) {
			$order->add_product( $product, 1 );
		}
	}

	$order->set_status( 'completed' );
	$order->save();

	$order->set_date_completed( time() - ( (int) $days_ago * DAY_IN_SECONDS ) );
	$order->save();

	$GLOBALS['bhp_rs_orders'][] = (int) $order->get_id();

	return wc_get_order( $order->get_id() );
}

/* =========================================================================
 * §0 — ENVIRONMENT GATE, SNAPSHOT AND FIXTURES
 * ====================================================================== */

bhp_rs_head( '§0 Environment gate' );

if ( ! function_exists( 'bhp_staging_mail_guard_is_staging' ) || ! bhp_staging_mail_guard_is_staging() ) {
	echo "ABORT: this suite runs on staging only, and the staging guard does not report staging.\n";
	return;
}
echo "OK: staging confirmed by bhp_staging_mail_guard_is_staging().\n";

foreach ( array( 'bhp_review_ask_copy', 'bhp_book_registry', 'bhp_review_route_slugs' ) as $bhp_rs_fn ) {
	if ( ! function_exists( $bhp_rs_fn ) ) {
		echo "ABORT: {$bhp_rs_fn}() is not loaded.\n";
		return;
	}
}

$bhp_rs_snapshot = array(
	BHP_REVIEW_ASK_OPTOUT_OPTION   => get_option( BHP_REVIEW_ASK_OPTOUT_OPTION, array() ),
	BHP_REVIEW_ASK_CUSTOMER_OPTION => get_option( BHP_REVIEW_ASK_CUSTOMER_OPTION, array() ),
	BHP_REVIEW_ASK_LOG_OPTION      => get_option( BHP_REVIEW_ASK_LOG_OPTION, array() ),
	BHP_REVIEW_ASK_STATS_OPTION    => get_option( BHP_REVIEW_ASK_STATS_OPTION, array() ),
);
echo "OK: ledger snapshot taken; §8 restores it.\n";

/*
 * ⭐⭐ THE VISIT REGISTRY IS FAKED THROUGH THE PLUGIN'S OWN READER, NOT WRITTEN.
 *
 * ⛔ `bhp_school_visits` IS THE BUNDLE PLUGIN'S OPTION AND DRIVES REAL CHECKOUT
 *    ENTITLEMENT. Writing a probe row into it, even on staging, even with a
 *    cleanup step, would put a fake school in front of a real shopper for the
 *    length of the run and would leave a real one broken if the run aborted
 *    between the write and the restore. ⭐ Filtering the read is free,
 *    request-scoped, and cannot outlive the process.
 *
 * ⛔⛔ MEASURED CORRECTION, 1.19.363. The 1.19.362 build of this suite hooked
 *     `bhp_school_visit_records` — A FILTER THAT DOES NOT EXIST. Nothing in the
 *     bundle plugin applies it: `bhp_school_visit_records()`
 *     (`plugins/brave-hearts-bundle-pricing/includes/school-visit-pickup.php`)
 *     reads `get_option( BHP_SCHOOL_VISIT_OPTION )` and sanitises it, with no
 *     `apply_filters()` anywhere in the function. `add_filter()` on an unused
 *     hook is silent and legal, so the fixture LOOKED installed and injected
 *     nothing. Every visit order therefore resolved `visit_date === ''`, fell
 *     down the documented unknown-visit path to the WEB lane, and took the web
 *     10-day delay measured from completion. That, and nothing else, is what
 *     produced the whole §1 failure block on staging AND the §4
 *     `not_due`-instead-of-`customer_cooldown` failure. ⛔ THE ENGINE WAS
 *     NEVER WRONG; the fixture never ran.
 *
 * ⭐ THE FIX IS `pre_option_<option>`, WHICH IS CORE AND IS ACTUALLY APPLIED.
 *    `get_option()` fires it before the cache and before the database, so the
 *    plugin's own reader — untouched, including all of its sanitising, which
 *    is the half worth exercising — sees the probe row. ⛔ It short-circuits,
 *    so the REAL rows are read ONCE here, before the filter is added, and
 *    handed back alongside the probe. A replacement would have hidden every
 *    genuine visit from any concurrent request for the length of the run.
 */
$bhp_rs_visit_date   = gmdate( 'Y-m-d', strtotime( '-9 days' ) );
$bhp_rs_visit_slug   = 'cycle179probe-' . $bhp_rs_visit_date;
$bhp_rs_visit_option = defined( 'BHP_SCHOOL_VISIT_OPTION' ) ? BHP_SCHOOL_VISIT_OPTION : 'bhp_school_visits';
$bhp_rs_visit_hook   = 'pre_option_' . $bhp_rs_visit_option;

// ⭐ READ ONCE, BEFORE THE FILTER EXISTS, so the short-circuit preserves them.
$bhp_rs_real_visits = get_option( $bhp_rs_visit_option, array() );
$bhp_rs_real_visits = is_array( $bhp_rs_real_visits ) ? $bhp_rs_real_visits : array();

$bhp_rs_fake_visits = static function ( $pre ) use ( $bhp_rs_real_visits, $bhp_rs_visit_slug, $bhp_rs_visit_date ) {
	$records = $bhp_rs_real_visits;

	/*
	 * ⛔ THE SHAPE IS THE PLUGIN'S, NOT A CONVENIENT ONE. `bhp_school_visit_records()`
	 *    DROPS any row without a school and a `Y-m-d` `date` AND `cutoff`, so a
	 *    fixture missing `cutoff` would be silently discarded and this bug would
	 *    simply come back wearing a different hat.
	 */
	$records[ $bhp_rs_visit_slug ] = array(
		'slug'   => $bhp_rs_visit_slug,
		'school' => 'Probe Elementary',
		'date'   => $bhp_rs_visit_date,
		'cutoff' => $bhp_rs_visit_date,
		'time'   => '9:00 AM',
	);

	return $records;
};

if ( ! function_exists( 'bhp_school_visit_records' ) ) {
	echo "ABORT: bhp_school_visit_records() is absent (bundle plugin not loaded). The visit lane cannot be tested and a pass would be meaningless.\n";
	return;
}

add_filter( $bhp_rs_visit_hook, $bhp_rs_fake_visits, 99 );

/*
 * ⭐ THE FIXTURE PROVES ITSELF BEFORE ANY ASSERTION DEPENDS ON IT. This is the
 *    guard the 1.19.362 build did not have: an inert hook now stops the suite
 *    here with a named cause instead of reporting seven day-math regressions
 *    that are not regressions.
 */
$bhp_rs_probe_check = bhp_school_visit_records();

if ( ! isset( $bhp_rs_probe_check[ $bhp_rs_visit_slug ]['date'] )
	|| $bhp_rs_visit_date !== $bhp_rs_probe_check[ $bhp_rs_visit_slug ]['date'] ) {
	remove_filter( $bhp_rs_visit_hook, $bhp_rs_fake_visits, 99 );
	echo "ABORT: the probe visit did not reach bhp_school_visit_records() through '{$bhp_rs_visit_hook}'. The fixture is inert; no visit-lane result would mean anything.\n";
	return;
}

echo "OK: probe visit verified live through the plugin's own reader (" . count( $bhp_rs_probe_check ) . " record(s) visible, real rows preserved).\n";

/*
 * ⭐ THE MORNING WINDOW IS FORCED OPEN FOR EVERY SECTION EXCEPT §5, WHICH
 *    TESTS IT. A suite whose result depends on the hour it was run at is a
 *    suite nobody can trust, and "it failed because it is the afternoon" is
 *    indistinguishable from a real regression in a log.
 */
add_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

$bhp_rs_books = bhp_book_registry();
$bhp_rs_pb    = array(
	(int) $bhp_rs_books['mariana_trench']['pb_product'],
	(int) $bhp_rs_books['mount_everest']['pb_product'],
	(int) $bhp_rs_books['amazon_rainforest']['pb_product'],
);

echo "OK: fixtures ready. Probe visit '{$bhp_rs_visit_slug}' dated {$bhp_rs_visit_date}.\n";

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE "COPY APPROVED" SHIM, AND WHY IT HAS TO EXIST.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The touch-2 and web touch-1 sets ship `approved => false`, which is a HARD
 * DECLINE. That is correct, it is the guarantee that no PENDING-COPY string
 * can reach a customer, and §6 asserts it directly.
 *
 * ⛔ BUT IT ALSO MASKS EVERY GATE BEHIND IT. With touch 2 permanently declining
 *    `copy_not_approved`, the reminder's timing, its review suppression and
 *    the cooldown scoping would all be untestable - and they would stay
 *    untestable right up until the day Merry's copy lands and switches them
 *    all on at once, unproven, against real parents.
 *
 * ⭐ SO SECTIONS THAT TEST A GATE **BEHIND** THE COPY GATE MARK THE SET
 *    APPROVED THROUGH THE ENGINE'S OWN PUBLIC `bhp_review_ask_copy` FILTER,
 *    changing NOTHING ELSE - not the words, not the delay, not any other gate.
 *    ⛔ It is removed again immediately, and §6 runs with it off.
 *
 * ⚠ A PASS UNDER THIS SHIM MEANS "the gate behind the copy gate is correct".
 *   ⛔ IT DOES NOT MEAN THE COPY IS APPROVED. Nothing in this suite approves
 *   copy, and `bhp_review_ask_copy_touch2()['approved']` is asserted FALSE in
 *   §6 precisely so that this shim can never be mistaken for a sign-off.
 */
$bhp_rs_approve_shim = static function ( $copy ) {
	if ( is_array( $copy ) ) {
		$copy['approved'] = true;
	}

	return $copy;
};

/**
 * Read a decline reason with the copy-approval gate shimmed open.
 *
 * @param WC_Order $order Order.
 * @param int      $now   Optional "now".
 * @return string
 */
function bhp_rs_reason_behind_copy_gate( $order, $now = 0 ) {
	add_filter( 'bhp_review_ask_copy', $GLOBALS['bhp_rs_approve_shim'], 99 );
	$reason = bhp_review_ask_decline_reason( $order, $now );
	remove_filter( 'bhp_review_ask_copy', $GLOBALS['bhp_rs_approve_shim'], 99 );

	return $reason;
}

$GLOBALS['bhp_rs_approve_shim'] = $bhp_rs_approve_shim;

/* =========================================================================
 * §1 — THE DAY MATH
 * ====================================================================== */

bhp_rs_head( '§1 Day math: one book vs two, visit vs web' );

$bhp_rs_visit_meta = array( '_bhp_school_visit_slug' => $bhp_rs_visit_slug );

$bhp_rs_v1 = bhp_rs_make_order( 'rs-v1@example.com', 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[0] ) );
$bhp_rs_v2 = bhp_rs_make_order( 'rs-v2@example.com', 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[0], $bhp_rs_pb[1] ) );
$bhp_rs_v3 = bhp_rs_make_order( 'rs-v3@example.com', 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[0], $bhp_rs_pb[1], $bhp_rs_pb[2] ) );
/*
 * ⛔⛔ 12 DAYS, NOT 9, AND THE CHANGE IS THE FIXTURE'S BUG BEING FIXED — NOT A
 *     RULE BEING BENT TO MAKE A TEST PASS. §6 asserts this order declines
 *     `copy_not_approved`; on staging at 1.19.363 it returned `not_due`,
 *     because the WEB lane's touch-1 delay is `BHP_REVIEW_ASK_WEB_DELAY_DAYS`
 *     = **10** and this probe was built 9 days old. The order was genuinely
 *     not due. The assertion was right, the fixture was a day short, and the
 *     engine was correct the whole time.
 *
 * ⭐ WHY THE WEB DELAY WAS NOT CHANGED TO 7 INSTEAD, WHICH WOULD ALSO HAVE
 *    MADE THIS PASS. Andrew's seal 977 says *"Then the 7 day review ask for
 *    the website and 10day for multiple books"*, and there are two honest
 *    readings of it: 7/10 everywhere, or 7/10 for the VISIT lane with
 *    "for the website" naming the destination rather than the lane. Merry's
 *    `CYCLE179-MKT-REVIEW-SEQ-V2.md` §4 takes the second reading explicitly
 *    and marks the web timing *"still an inference, not a seal"*, carried as
 *    open conflict **CYCLE179-MKT-34**. ⛔ A developer does not settle an open
 *    conflict by editing a constant. The web delay is untouched at 10 and the
 *    conflict goes to Andrew.
 */
/*
 * ⛔⛔ 1.19.370 · AGED FROM 12 DAYS TO 16, AND THE FIXTURE WAS THE BUG, NOT THE
 *     ENGINE. 1.19.369 raised `BHP_REVIEW_ASK_WEB_DELAY_DAYS` from 10 to 14 on
 *     Andrew's ruling; this fixture stayed at 12, so it silently stopped being
 *     due and §6's copy-gate assertion returned `not_due` instead of
 *     `copy_not_approved`. ⭐ THE FAILURE WAS THEREFORE HONEST AND THE ENGINE
 *     WAS RIGHT — it is only a test that was measuring the wrong world.
 *
 * ⚠ 16, NOT 14, AND THE MARGIN IS DELIBERATE. At exactly 14 the order becomes
 *   due at local midnight of the fourteenth day, so a suite run in the small
 *   hours could land either side of the boundary and this file would fail
 *   intermittently for a reason that has nothing to do with what it tests. Two
 *   days of slack removes the clock from the question entirely.
 *
 * ⛔ THE COMMENT ABOVE ABOUT THE DELAY BEING "UNTOUCHED AT 10" IS SUPERSEDED
 *    AND IS PRESERVED RATHER THAN DELETED: it records that CYCLE179-MKT-34 was
 *    an open conflict this desk refused to settle by editing a constant, which
 *    is still the correct account of how it was handled. Andrew settled it.
 */
$bhp_rs_w1 = bhp_rs_make_order( 'rs-w1@example.com', 16, array(), array( $bhp_rs_pb[0] ) );

bhp_rs_ok( 'A visit order is in the visit lane', 'visit' === bhp_review_ask_lane( $bhp_rs_v1 ) );
bhp_rs_ok( 'An order with no slug is in the web lane', 'web' === bhp_review_ask_lane( $bhp_rs_w1 ) );

bhp_rs_ok( 'One chapter book counts as 1', 1 === bhp_review_ask_chapter_book_count( $bhp_rs_v1 ) );
bhp_rs_ok( 'Two chapter books count as 2', 2 === bhp_review_ask_chapter_book_count( $bhp_rs_v2 ) );
bhp_rs_ok( 'Three chapter books count as 3', 3 === bhp_review_ask_chapter_book_count( $bhp_rs_v3 ) );

bhp_rs_ok(
	'⭐ ONE book on a visit order gives a 7-day delay',
	7 === bhp_review_ask_touch1_delay_days( $bhp_rs_v1 ),
	'got ' . bhp_review_ask_touch1_delay_days( $bhp_rs_v1 )
);
bhp_rs_ok(
	'⭐ TWO books on a visit order gives a 10-day delay',
	10 === bhp_review_ask_touch1_delay_days( $bhp_rs_v2 ),
	'got ' . bhp_review_ask_touch1_delay_days( $bhp_rs_v2 )
);
bhp_rs_ok(
	'THREE books also gives 10, not 13 (the rule is "two or more", not per book)',
	10 === bhp_review_ask_touch1_delay_days( $bhp_rs_v3 )
);
bhp_rs_ok(
	'⭐ A web order gives the web delay, measured from completion',
	BHP_REVIEW_ASK_WEB_DELAY_DAYS === bhp_review_ask_touch1_delay_days( $bhp_rs_w1 )
);

/*
 * ⭐⭐ THE ASSERTION THAT MATTERS MOST IN THIS FILE. The visit lane anchors on
 *     the VISIT DATE, not on when Andrew flipped the order to completed. The
 *     four probe orders above were all "completed" nine days ago; if the
 *     anchor were completion, a one-book and a two-book visit order would be
 *     due on the same day and the whole ruling would be inert.
 */
bhp_rs_ok(
	'⭐ The visit-lane anchor is the visit date at local midnight, NOT the completion timestamp',
	bhp_review_ask_touch1_anchor( $bhp_rs_v1 ) === bhp_review_ask_local_midnight( $bhp_rs_visit_date )
);
bhp_rs_ok(
	'The web-lane anchor is the completion timestamp',
	bhp_review_ask_touch1_anchor( $bhp_rs_w1 ) === bhp_review_ask_anchor_timestamp( $bhp_rs_w1 )
);

bhp_rs_ok(
	'⭐ visit + 7: a one-book order is DUE at nine days after the visit',
	'' === bhp_review_ask_decline_reason( $bhp_rs_v1 ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_v1 )
);

/*
 * ⭐ THE BOUNDARY, ASSERTED ON BOTH SIDES. A gate that fires a day early is
 *    indistinguishable from a working one until somebody reads the send log.
 */
$bhp_rs_day6 = bhp_review_ask_local_midnight( $bhp_rs_visit_date ) + ( 6 * DAY_IN_SECONDS );
$bhp_rs_day7 = bhp_review_ask_local_midnight( $bhp_rs_visit_date ) + ( 7 * DAY_IN_SECONDS );

bhp_rs_ok( 'One-book visit order at visit+6 declines not_due', 'not_due' === bhp_review_ask_decline_reason( $bhp_rs_v1, $bhp_rs_day6 ) );
bhp_rs_ok( 'One-book visit order at visit+7 QUALIFIES', '' === bhp_review_ask_decline_reason( $bhp_rs_v1, $bhp_rs_day7 ), 'got: ' . bhp_review_ask_decline_reason( $bhp_rs_v1, $bhp_rs_day7 ) );
bhp_rs_ok( '⭐ TWO-book visit order at visit+7 is STILL not due', 'not_due' === bhp_review_ask_decline_reason( $bhp_rs_v2, $bhp_rs_day7 ) );

$bhp_rs_day9  = bhp_review_ask_local_midnight( $bhp_rs_visit_date ) + ( 9 * DAY_IN_SECONDS );
$bhp_rs_day10 = bhp_review_ask_local_midnight( $bhp_rs_visit_date ) + ( 10 * DAY_IN_SECONDS );

bhp_rs_ok( 'Two-book visit order at visit+9 declines not_due', 'not_due' === bhp_review_ask_decline_reason( $bhp_rs_v2, $bhp_rs_day9 ) );
bhp_rs_ok( '⭐ Two-book visit order at visit+10 QUALIFIES', '' === bhp_review_ask_decline_reason( $bhp_rs_v2, $bhp_rs_day10 ), 'got: ' . bhp_review_ask_decline_reason( $bhp_rs_v2, $bhp_rs_day10 ) );

/*
 * ⭐ THE REAL SCHEDULE, RE-DERIVED FROM THE ENGINE RATHER THAN RESTATED. Gimli
 *    built sixteen hand-sent drafts to these exact dates on 2026-09-05
 *    (`Business OS\ANDREW-REVIEW\2026-09-05\REVIEW-ASKS\SUMMARY.md`): Dallas
 *    Harris visited 2026-09-03 and sends 09-10 / 09-13; Liberty visited
 *    2026-09-04 and sends 09-11 / 09-14. ⛔ If the engine and the hand-built
 *    schedule ever disagree, one of them is wrong and a parent gets two notes
 *    on different days. This asserts they agree.
 */
foreach ( array(
	array( '2026-09-03', 1, '2026-09-10' ),
	array( '2026-09-03', 2, '2026-09-13' ),
	array( '2026-09-04', 1, '2026-09-11' ),
	array( '2026-09-04', 2, '2026-09-14' ),
) as $bhp_rs_row ) {
	list( $bhp_rs_vd, $bhp_rs_n, $bhp_rs_expected ) = $bhp_rs_row;

	$bhp_rs_delay = ( $bhp_rs_n >= 2 ) ? BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK : BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK;
	$bhp_rs_got   = wp_date( 'Y-m-d', bhp_review_ask_local_midnight( $bhp_rs_vd ) + ( $bhp_rs_delay * DAY_IN_SECONDS ) );

	bhp_rs_ok(
		"Visit {$bhp_rs_vd}, {$bhp_rs_n} book(s) -> {$bhp_rs_expected}",
		$bhp_rs_expected === $bhp_rs_got,
		"got {$bhp_rs_got}"
	);
}

/*
 * ⛔ THE ACTIVITY BOOK MUST NOT COUNT. It is excluded structurally rather than
 *    by name - it is simply not in `bhp_book_registry()` - so this asserts the
 *    structural property directly: a non-chapter-book product on the order
 *    does not move the count.
 */
$bhp_rs_activity_id = 0;
$bhp_rs_activity    = get_page_by_path( 'the-adventure-activity-book', OBJECT, 'product' );

if ( $bhp_rs_activity instanceof WP_Post ) {
	$bhp_rs_activity_id = (int) $bhp_rs_activity->ID;
}

if ( $bhp_rs_activity_id ) {
	$bhp_rs_act = bhp_rs_make_order( 'rs-act@example.com', 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[0], $bhp_rs_activity_id ) );

	bhp_rs_ok(
		'⭐ The Activity Book does NOT count toward the chapter-book total',
		1 === bhp_review_ask_chapter_book_count( $bhp_rs_act ),
		'got ' . bhp_review_ask_chapter_book_count( $bhp_rs_act )
	);
	bhp_rs_ok(
		'⭐ ... so one chapter book plus the Activity Book still gives a 7-day delay',
		7 === bhp_review_ask_touch1_delay_days( $bhp_rs_act )
	);
} else {
	echo "NOTE: the Activity Book product could not be resolved by slug on this environment; its two assertions were SKIPPED, not passed.\n";
}

/*
 * ⭐ THE SAME BOOK TWICE IS ONE BOOK. A parent who bought two copies of Mount
 *    Everest for two children has not been handed a longer reading job.
 */
$bhp_rs_dupe = bhp_rs_make_order( 'rs-dupe@example.com', 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[1], $bhp_rs_pb[1] ) );
bhp_rs_ok( 'Two copies of the same title count as ONE chapter book', 1 === bhp_review_ask_chapter_book_count( $bhp_rs_dupe ) );
bhp_rs_ok( '... and therefore keep the 7-day delay', 7 === bhp_review_ask_touch1_delay_days( $bhp_rs_dupe ) );

/*
 * ⚠ A VISIT ORDER WHOSE VISIT THE REGISTRY DOES NOT KNOW MUST FALL BACK TO
 *   COMPLETION, WHICH CAN ONLY DELAY AN ASK, NEVER FIRE ONE EARLY.
 */
$bhp_rs_orphan = bhp_rs_make_order( 'rs-orphan@example.com', 9, array( '_bhp_school_visit_slug' => 'no-such-visit-ever' ), array( $bhp_rs_pb[0] ) );
bhp_rs_ok( 'An unknown visit slug yields no visit date', '' === bhp_review_ask_visit_date( $bhp_rs_orphan ) );
bhp_rs_ok(
	'An unknown visit slug falls back to the completion anchor',
	bhp_review_ask_touch1_anchor( $bhp_rs_orphan ) === bhp_review_ask_anchor_timestamp( $bhp_rs_orphan )
);

/* =========================================================================
 * §2 — TOUCH 2 AT TOUCH 1 + 7
 * ====================================================================== */

bhp_rs_head( '§2 Touch 2' );

bhp_rs_ok( 'A fresh order is next in line for touch 1', 1 === bhp_review_ask_next_touch( $bhp_rs_v1 ) );

$bhp_rs_t1_date = gmdate( 'Y-m-d', strtotime( '-8 days' ) );
$bhp_rs_t2      = bhp_rs_make_order(
	'rs-t2@example.com',
	20,
	array(
		'_bhp_school_visit_slug'    => $bhp_rs_visit_slug,
		'_bhp_review_ask_sent'      => $bhp_rs_t1_date . ' 09:00:00',
		'_bhp_review_ask_touch1_at' => $bhp_rs_t1_date . ' 09:00:00',
	),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok( 'An order with touch 1 marked is next in line for touch 2', 2 === bhp_review_ask_next_touch( $bhp_rs_t2 ) );
/*
 * ⭐⭐ FOUR DAYS, NOT SEVEN, SINCE 1.19.364. ANDREW, SEAL 977, VERBATIM: *"If
 *     no reviews we ask 4 days later"*. Merry's `CYCLE179-MKT-REVIEW-SEQ-V2.md`
 *     §3 carries the same number and marks it as the change from V1.
 *
 * ⛔ SUPERSEDED ASSERTION, PRESERVED RATHER THAN DELETED (seal 965, shipped in
 *    1.19.362 and 1.19.363, superseded the same day by seal 977):
 *
 *      '⭐ Touch 2 is due exactly 7 days after touch 1 went out'
 *      ... + ( 7 * DAY_IN_SECONDS )
 *
 * ⭐ THE LITERAL 4 IS DELIBERATE AND THE CONSTANT IS ASSERTED SEPARATELY BELOW.
 *    Writing `BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS * DAY_IN_SECONDS` here would make
 *    this test agree with the engine no matter what the engine said, which is
 *    the same thing as not testing it. The number Andrew ruled is written out.
 */
bhp_rs_ok(
	'⭐ The touch-2 delay constant is 4 days (seal 977)',
	4 === BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS,
	'got ' . BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS
);
bhp_rs_ok(
	'⭐ Touch 2 is due exactly 4 days after touch 1 went out',
	bhp_review_ask_touch2_due_timestamp( $bhp_rs_t2 )
		=== bhp_review_ask_local_datetime( $bhp_rs_t1_date . ' 09:00:00' ) + ( 4 * DAY_IN_SECONDS )
);
bhp_rs_ok(
	'⛔ The touch-2 copy set declares the SAME delay the engine will use',
	in_array( 4, (array) bhp_review_ask_copy_touch2()['delay_days'], true )
);

/*
 * ⭐ AND THE DELAY IS FILTERABLE, PROVED BY MOVING IT AND MOVING IT BACK.
 */
add_filter( 'bhp_review_ask_touch2_delay_days', 'bhp_rs_touch2_nine', 99 );
bhp_rs_ok(
	'⭐ The touch-2 delay is filterable',
	bhp_review_ask_touch2_due_timestamp( $bhp_rs_t2 )
		=== bhp_review_ask_local_datetime( $bhp_rs_t1_date . ' 09:00:00' ) + ( 9 * DAY_IN_SECONDS )
);
remove_filter( 'bhp_review_ask_touch2_delay_days', 'bhp_rs_touch2_nine', 99 );
bhp_rs_ok(
	'... and the filter was cleanly removed, so nothing below inherits it',
	bhp_review_ask_touch2_due_timestamp( $bhp_rs_t2 )
		=== bhp_review_ask_local_datetime( $bhp_rs_t1_date . ' 09:00:00' ) + ( 4 * DAY_IN_SECONDS )
);

$bhp_rs_t2_due = bhp_review_ask_touch2_due_timestamp( $bhp_rs_t2 );

bhp_rs_ok( 'Touch 2 at +6 days declines not_due', 'not_due' === bhp_review_ask_decline_reason( $bhp_rs_t2, $bhp_rs_t2_due - DAY_IN_SECONDS ) );

/*
 * ⛔ FAIL CLOSED. An order marked `external-<date>` carries no parseable
 *    touch-1 datetime, so there is no honest day to count seven from. It must
 *    decline by name rather than guess. This is the exact shape the migration
 *    writes for the sixteen hand-prepared orders when nobody has confirmed the
 *    send in Gmail.
 */
$bhp_rs_ext = bhp_rs_make_order(
	'rs-ext@example.com',
	30,
	array( '_bhp_review_ask_sent' => 'external-pending-2026-09-10' ),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok( 'An external-pending marker suppresses touch 1', 2 === bhp_review_ask_next_touch( $bhp_rs_ext ) );
bhp_rs_ok(
	'⭐ ... and touch 2 declines touch1_date_unknown rather than guessing a date',
	'touch1_date_unknown' === bhp_review_ask_decline_reason( $bhp_rs_ext ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_ext )
);

$bhp_rs_done = bhp_rs_make_order(
	'rs-done@example.com',
	30,
	array(
		'_bhp_review_ask_sent'        => $bhp_rs_t1_date . ' 09:00:00',
		'_bhp_review_ask_touch1_at'   => $bhp_rs_t1_date . ' 09:00:00',
		'_bhp_review_ask_touch2_sent' => gmdate( 'Y-m-d H:i:s' ),
	),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok( '⭐ Both touches marked: the sequence is finished for that order', 0 === bhp_review_ask_next_touch( $bhp_rs_done ) );
bhp_rs_ok( '... and it declines already_sent, never again', 'already_sent' === bhp_review_ask_decline_reason( $bhp_rs_done ) );

/* =========================================================================
 * §3 — THE REMINDER IS SUPPRESSED BY A SITE REVIEW
 * ====================================================================== */

bhp_rs_head( '§3 Reminder suppression' );

$bhp_rs_review_target = (int) bhp_review_target_id( 'mariana_trench' );

bhp_rs_ok( 'The review target for a title resolves to its canonical product', $bhp_rs_review_target > 0 );

$bhp_rs_reviewer = 'rs-reviewed@example.com';
$bhp_rs_t2r      = bhp_rs_make_order(
	$bhp_rs_reviewer,
	20,
	array(
		'_bhp_school_visit_slug'    => $bhp_rs_visit_slug,
		'_bhp_review_ask_sent'      => $bhp_rs_t1_date . ' 09:00:00',
		'_bhp_review_ask_touch1_at' => $bhp_rs_t1_date . ' 09:00:00',
	),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok(
	'Before any review, the reminder QUALIFIES',
	'' === bhp_rs_reason_behind_copy_gate( $bhp_rs_t2r ),
	'got: ' . bhp_rs_reason_behind_copy_gate( $bhp_rs_t2r )
);
bhp_rs_ok( 'No site review is detected for that address yet', ! bhp_review_ask_has_site_review( $bhp_rs_reviewer ) );

/*
 * ⭐⭐ THE REVIEW IS INSERTED **UNAPPROVED**, AND THAT IS THE WHOLE POINT.
 *     `bhp_review_force_moderation()` holds every product review at
 *     `comment_approved = 0` in code, so a parent who did exactly what touch 1
 *     asked is invisible to any check that counts only approved reviews - and
 *     would be chased for a review they had already written.
 */
$bhp_rs_comment_id = wp_insert_comment(
	array(
		'comment_post_ID'      => $bhp_rs_review_target,
		'comment_author'       => 'Probe Parent',
		'comment_author_email' => $bhp_rs_reviewer,
		'comment_content'      => 'CYCLE179 probe review. Deleted by section 8 of this suite.',
		'comment_type'         => 'review',
		'comment_approved'     => 0,
	)
);

if ( $bhp_rs_comment_id ) {
	$GLOBALS['bhp_rs_comments'][] = (int) $bhp_rs_comment_id;
}

bhp_rs_ok( 'The probe review was created (held for moderation)', (bool) $bhp_rs_comment_id );

bhp_rs_ok(
	'⭐⭐ An UNAPPROVED site review is detected',
	bhp_review_ask_has_site_review( $bhp_rs_reviewer )
);
bhp_rs_ok(
	'⭐⭐ ... and the reminder is suppressed: already_reviewed',
	'already_reviewed' === bhp_rs_reason_behind_copy_gate( $bhp_rs_t2r ),
	'got: ' . bhp_rs_reason_behind_copy_gate( $bhp_rs_t2r )
);

/*
 * ⛔ THE SUPPRESSION IS TOUCH 2 ONLY. A NEW order from somebody who reviewed
 *    a different book months ago must still get a first ask; the reminder is
 *    the only thing a prior review cancels.
 */
$bhp_rs_t1r = bhp_rs_make_order( 'rs-notreviewed@example.com', 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[0] ) );
bhp_rs_ok(
	'A prior review does NOT suppress touch 1 for a different buyer',
	'already_reviewed' !== bhp_review_ask_decline_reason( $bhp_rs_t1r )
);

/* =========================================================================
 * §4 — THE 90-DAY COOLDOWN GATES TOUCH 1 AND NOT TOUCH 2
 * ====================================================================== */

bhp_rs_head( '§4 Cooldown scoping' );

$bhp_rs_cool_email = 'rs-cooldown@example.com';

bhp_review_ask_record_customer( $bhp_rs_cool_email );

$bhp_rs_cool1 = bhp_rs_make_order( $bhp_rs_cool_email, 9, $bhp_rs_visit_meta, array( $bhp_rs_pb[0] ) );

bhp_rs_ok(
	'A customer asked within 90 days declines touch 1: customer_cooldown',
	'customer_cooldown' === bhp_review_ask_decline_reason( $bhp_rs_cool1 ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_cool1 )
);

/*
 * ⭐⭐ THE ASSERTION THAT PROTECTS THE WHOLE RULING. Touch 1 writes the
 *     customer stamp. If the cooldown also gated touch 2, the stamp written
 *     seven days ago would decline every reminder, forever, and the two-touch
 *     sequence Andrew approved would silently be a single ask again - with
 *     nothing in any log to show it.
 */
$bhp_rs_cool2 = bhp_rs_make_order(
	$bhp_rs_cool_email,
	20,
	array(
		'_bhp_school_visit_slug'    => $bhp_rs_visit_slug,
		'_bhp_review_ask_sent'      => $bhp_rs_t1_date . ' 09:00:00',
		'_bhp_review_ask_touch1_at' => $bhp_rs_t1_date . ' 09:00:00',
	),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok(
	'⭐⭐ The SAME customer, inside the 90-day window, STILL gets touch 2',
	'customer_cooldown' !== bhp_review_ask_decline_reason( $bhp_rs_cool2 ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_cool2 )
);
bhp_rs_ok(
	'... and touch 2 qualifies outright',
	'' === bhp_rs_reason_behind_copy_gate( $bhp_rs_cool2 ),
	'got: ' . bhp_rs_reason_behind_copy_gate( $bhp_rs_cool2 )
);

/* =========================================================================
 * §5 — THE MORNING SEND WINDOW
 * ====================================================================== */

bhp_rs_head( '§5 Send window' );

remove_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

/*
 * ⭐ TESTED BY MOVING THE CLOCK, NOT BY MOVING THE WINDOW, because moving the
 *    window would only prove the filter works.
 */
$bhp_rs_today = current_time( 'Y-m-d' );

bhp_rs_ok( 'The window is CLOSED at 03:00 site-local', ! bhp_review_ask_in_send_window( bhp_review_ask_local_datetime( $bhp_rs_today . ' 03:00:00' ) ) );
bhp_rs_ok( '⭐ The window is OPEN at 09:00 site-local', bhp_review_ask_in_send_window( bhp_review_ask_local_datetime( $bhp_rs_today . ' 09:00:00' ) ) );
bhp_rs_ok( 'The window is OPEN at 08:00, the first minute', bhp_review_ask_in_send_window( bhp_review_ask_local_datetime( $bhp_rs_today . ' 08:00:00' ) ) );
bhp_rs_ok( 'The window is CLOSED at 12:00, the first minute after', ! bhp_review_ask_in_send_window( bhp_review_ask_local_datetime( $bhp_rs_today . ' 12:00:00' ) ) );
bhp_rs_ok( 'The window is CLOSED at 19:00 site-local', ! bhp_review_ask_in_send_window( bhp_review_ask_local_datetime( $bhp_rs_today . ' 19:00:00' ) ) );

$bhp_rs_evening = bhp_review_ask_local_midnight( $bhp_rs_visit_date ) + ( 9 * DAY_IN_SECONDS ) + ( 19 * HOUR_IN_SECONDS );

bhp_rs_ok(
	'⭐ A genuinely due order declines outside_send_window in the evening',
	'outside_send_window' === bhp_review_ask_decline_reason( $bhp_rs_v1, $bhp_rs_evening ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_v1, $bhp_rs_evening )
);

add_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

/* =========================================================================
 * §6 — THE COPY GATE, NOW THAT ALL FOUR SETS ARE APPROVED
 *
 * ⭐⭐ 1.19.365 · THIS SECTION WAS INVERTED BY ANDREW'S SEAL 982, AND THE
 *     INVERSION IS THE POINT. Until this build the section proved "PENDING-COPY
 *     can never be sent" by observing two sets that were unapproved. Those sets
 *     now carry Merry's V2 §3 and §4 prose and Andrew has approved them, so
 *     the old assertions would now be asserting the WRONG WORLD.
 *
 * ⛔⛔ THE GATE IS THEREFORE PROVED THE OTHER WAY ROUND, WHICH IS STRONGER:
 *     the section forces a set unapproved through the engine's own public
 *     filter and asserts the decline still fires. A gate observed to fire on
 *     demand is proved; a gate that merely happened to be closed was only ever
 *     being described.
 * ====================================================================== */

bhp_rs_head( '§6 The copy gate, with all four sets approved (seal 982)' );

bhp_rs_ok( '⭐ The visit touch-1 set is APPROVED (seal 982)', ! empty( bhp_review_ask_copy_visit_touch1()['approved'] ) );
bhp_rs_ok( '⭐ The web touch-1 set is APPROVED (seal 982)', ! empty( bhp_review_ask_copy_web_touch1()['approved'] ) );
bhp_rs_ok( '⭐ The touch-2 set is APPROVED (seal 982)', ! empty( bhp_review_ask_copy_touch2()['approved'] ) );

/*
 * ⛔⛔ THE GATE ITSELF, PROVED BY FORCING IT SHUT. `$bhp_rs_unapprove_shim` is
 *     the mirror of the approve shim in §0 and touches nothing but the bool.
 */
$bhp_rs_unapprove_shim = static function ( $copy ) {
	if ( is_array( $copy ) ) {
		$copy['approved'] = false;
	}

	return $copy;
};

add_filter( 'bhp_review_ask_copy', $bhp_rs_unapprove_shim, 99 );

bhp_rs_ok(
	'⭐⭐ THE GATE STILL FIRES: a due WEB order whose copy is forced unapproved declines copy_not_approved',
	'copy_not_approved' === bhp_review_ask_decline_reason( $bhp_rs_w1 ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_w1 )
);

bhp_rs_ok(
	'⭐⭐ THE GATE STILL FIRES: a due TOUCH-2 order whose copy is forced unapproved declines copy_not_approved',
	'copy_not_approved' === bhp_review_ask_decline_reason( $bhp_rs_t2, bhp_review_ask_touch2_due_timestamp( $bhp_rs_t2 ) + DAY_IN_SECONDS ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_t2, bhp_review_ask_touch2_due_timestamp( $bhp_rs_t2 ) + DAY_IN_SECONDS )
);

remove_filter( 'bhp_review_ask_copy', $bhp_rs_unapprove_shim, 99 );

bhp_rs_ok(
	'⭐ The shim was removed: the web set reports APPROVED again',
	! empty( bhp_review_ask_copy_web_touch1()['approved'] )
);

/*
 * ⛔⛔ APPROVED COPY IS NOT AN ACTIVATED ENGINE, AND THAT IS THE ASSERTION
 *     ANDREW SHOULD BE ABLE TO POINT AT. Seal 982 approved words. It did not
 *     throw the master switch, and nothing in 1.19.365 throws it.
 */
bhp_rs_ok(
	'⛔⛔ The master switch is STILL OFF even though every set is now approved',
	'yes' !== get_option( 'bhp_review_ask_enabled', 'no' )
);

/*
 * ⛔ NO PENDING-COPY PLACEHOLDER SURVIVES ANYWHERE. If a future edit restores
 *    one, it must not be able to hide behind an `approved => true`.
 */
$bhp_rs_all_sets = wp_json_encode(
	array(
		bhp_review_ask_copy_visit_touch1(),
		bhp_review_ask_copy_web_touch1(),
		bhp_review_ask_copy_touch2(),
	)
);

bhp_rs_ok(
	'⛔ No "PENDING-COPY" string survives in any shipped set',
	false === strpos( (string) $bhp_rs_all_sets, 'PENDING-COPY' )
);

/* =========================================================================
 * §6B — MERRY'S V2 PROSE, ASSERTED WORD FOR WORD
 *
 * ⛔ ASSERTED AGAINST THE **UNMERGED** SETS, so a slot that fails to resolve
 *    cannot make a sentence look right by disappearing.
 * ====================================================================== */

bhp_rs_head( '§6B Seal 982 copy, verbatim' );

$bhp_rs_v2_web    = bhp_review_ask_copy_web_touch1();
$bhp_rs_v2_touch2 = bhp_review_ask_copy_touch2();

bhp_rs_ok(
	'⭐ Web touch 1 opens with V2 §4 verbatim',
	'Your reader has had {BookTitle} for a couple of weeks now. It went out in the mail, so I never got to see who opened it.' === $bhp_rs_v2_web['body_before'][0]
);
bhp_rs_ok(
	'⭐ Web touch 1 second paragraph is V2 §4 verbatim',
	'Would you rate it? It takes about ten seconds, and if you have another minute after that, two or three honest sentences would help the next parent decide. Honest is the useful part.' === $bhp_rs_v2_web['body_before'][1]
);
bhp_rs_ok(
	'⭐ Web touch 1 closes on the line that already carried its own approval',
	'Thank you for taking a chance on a book by somebody you had never heard of.' === $bhp_rs_v2_web['body_after'][0]
);
bhp_rs_ok(
	'⛔ Web touch 1 names no school and no child (V2 §4: there was no table and no signature)',
	false === strpos( (string) wp_json_encode( $bhp_rs_v2_web ), '{SchoolName}' )
		&& false === strpos( (string) wp_json_encode( $bhp_rs_v2_web ), '{ChildFirstName}' )
);

bhp_rs_ok(
	'⭐⭐ Touch 2 carries ANDREW\'S OWN SENTENCE, unreworded (seal 977)',
	'I know how a week can get away from me, so I made this as short as I could.' === $bhp_rs_v2_touch2['body_before'][0]
);
bhp_rs_ok(
	'⛔⛔ Touch 2 puts NO blame on the reader: no "you", "your" or "busy" in its body',
	! preg_match( '/\b(you|your|busy)\b/i', (string) $bhp_rs_v2_touch2['body_before'][0] )
);
bhp_rs_ok(
	'⭐⭐ Touch 2 promises finality, and the exit comes BEFORE the thanks',
	'If it is not for you, that is completely fine. This is the last note I will send about it.' === $bhp_rs_v2_touch2['body_after'][0]
);
bhp_rs_ok(
	'⛔⛔ THERE IS NO TOUCH 3, which is what makes that promise true',
	3 !== (int) bhp_review_ask_next_touch( $bhp_rs_t2 )
);
bhp_rs_ok(
	'⭐ Touch 2 subject is V2 §3 verbatim and under 40 characters',
	'Last note about the book' === $bhp_rs_v2_touch2['subject'] && strlen( $bhp_rs_v2_touch2['subject'] ) < 40
);

/*
 * ⛔⛔ SEAL 981. The removed sentence must not exist anywhere in the theme, in
 *     any set, in any comment that could be copy-pasted back into one.
 */
bhp_rs_ok(
	'⛔⛔ SEAL 981: the three-star sentence appears in NO shipped copy set',
	false === strpos( (string) $bhp_rs_all_sets, 'A three-star review' )
		&& false === strpos( (string) $bhp_rs_all_sets, 'three-star review that says why' )
);

/*
 * ⭐ THE NAMED / GENERIC SWAP (V2 §2, conflict CYCLE179-MKT-32). With no order
 *    in hand the set must fall to the GENERIC wording, because that is what
 *    most real orders will get.
 */
$bhp_rs_generic = bhp_review_ask_copy_visit_touch1();

bhp_rs_ok(
	'⭐ With no order, visit touch 1 uses the GENERIC opener (capital "Your reader")',
	0 === strpos( (string) $bhp_rs_generic['body_before'][0], 'Your reader has had {BookTitle}' )
);
bhp_rs_ok(
	'⭐ The generic close is V2 §2 verbatim',
	'Thank you for reading together.' === $bhp_rs_generic['body_after'][0]
);
bhp_rs_ok(
	'⭐ The generic P.S. is V2 §2 verbatim',
	'P.S. If your reader has a question about the book, hit reply. I answer every one.' === $bhp_rs_generic['postscript']
);
bhp_rs_ok(
	'⛔ The generic set carries NO {ChildFirstName} slot at all (never send with an empty slot)',
	false === strpos( (string) wp_json_encode( $bhp_rs_generic ), '{ChildFirstName}' )
);

/*
 * ⭐⭐ THE TIME PHRASE MOVES WITH THE DELAY, AND THIS IS THE ONE MERRY FLAGGED.
 *     A one-book order sends at +7 and must say "about a week"; a two-book
 *     order sends at +10 and must say "a week and a half". ⛔ If these two ever
 *     read the same, the copy is false on one of the two lanes.
 */
$bhp_rs_one_book   = bhp_review_ask_copy_visit_touch1( $bhp_rs_v1 );
$bhp_rs_multi_book = bhp_review_ask_copy_visit_touch1( $bhp_rs_v2 );

bhp_rs_ok(
	'⭐⭐ A ONE-book visit order says "for about a week now" (true at +7)',
	false !== strpos( (string) $bhp_rs_one_book['body_before'][0], 'for about a week now' ),
	$bhp_rs_one_book['body_before'][0]
);
bhp_rs_ok(
	'⭐⭐ A TWO-book visit order says "for a week and a half now" (true at +10)',
	false !== strpos( (string) $bhp_rs_multi_book['body_before'][0], 'for a week and a half now' ),
	$bhp_rs_multi_book['body_before'][0]
);
bhp_rs_ok(
	'⛔ The two openers are NOT the same string',
	$bhp_rs_one_book['body_before'][0] !== $bhp_rs_multi_book['body_before'][0]
);
bhp_rs_ok(
	'⭐ Both visit delays are still declared, so the interlock passes at 7 and at 10',
	in_array( BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK, (array) $bhp_rs_one_book['delay_days'], true )
		&& in_array( BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK, (array) $bhp_rs_one_book['delay_days'], true )
);

/*
 * ⭐ THE POST-STAR-ROW LINE IS PRESENT ON ALL THREE SETS, verbatim, and it is
 *    the same sentence in each because Merry wrote it once.
 */
foreach (
	array(
		'visit touch 1' => $bhp_rs_generic,
		'web touch 1'   => $bhp_rs_v2_web,
		'touch 2'       => $bhp_rs_v2_touch2,
	) as $bhp_rs_label => $bhp_rs_set
) {
	/*
	 * ⛔ 1.19.369 · THE INSTRUCTION MOVED FROM `links_lead` TO `stars_caption`
	 *    AND ITS WORDS CHANGED. Round-8 brief, item 1. SUPERSEDED ASSERTION,
	 *    PRESERVED: 'Tap the stars that fit, then two or three honest sentences
	 *    on the next page.' === $bhp_rs_set['links_lead'].
	 */
	bhp_rs_ok(
		'⭐ ' . $bhp_rs_label . ' carries the round-8 star caption verbatim',
		'Tap a star to rate {BookTitle}. Then two or three honest sentences on the next page.' === $bhp_rs_set['stars_caption'],
		'got: ' . ( isset( $bhp_rs_set['stars_caption'] ) ? $bhp_rs_set['stars_caption'] : '(absent)' )
	);
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_label . ' no longer bolds a lead line above the link',
		'' === $bhp_rs_set['links_lead']
	);
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_label . ' still passes bhp_review_ask_copy_is_usable()',
		bhp_review_ask_copy_is_usable( $bhp_rs_set )
	);
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_label . ' names Amazon nowhere',
		false === stripos( (string) wp_json_encode( $bhp_rs_set ), 'amazon' )
	);
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_label . ' has no em dash and no en dash',
		false === strpos( (string) wp_json_encode( $bhp_rs_set ), "\xe2\x80\x94" )
			&& false === strpos( (string) wp_json_encode( $bhp_rs_set ), "\xe2\x80\x93" )
	);
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_label . ' subject is under 40 characters',
		strlen( (string) $bhp_rs_set['subject'] ) < 40
	);
}

/* =========================================================================
 * §6C — `test-send` IS WIRED, AND ITS SAFETY RAILS EXIST
 *
 * ⚠ WHAT THIS SECTION CAN AND CANNOT PROVE. It proves the two functions exist
 *   and that the command is dispatched. ⛔ IT DOES NOT SEND ANYTHING and it
 *   cannot prove the staging refusal fires, because proving that would mean
 *   running the command on a non-staging host, which is the exact thing the
 *   refusal exists to prevent. That check is verified by a human running it
 *   once with the wrong --url and reading the refusal.
 * ====================================================================== */

bhp_rs_head( '§6C test-send wiring' );

bhp_rs_ok( '⭐ bhp_review_ask_cli_test_send() exists', function_exists( 'bhp_review_ask_cli_test_send' ) );
bhp_rs_ok( '⭐ bhp_review_ask_cli_test_send_deliver() exists', function_exists( 'bhp_review_ask_cli_test_send_deliver' ) );

$bhp_rs_engine_src = file_get_contents( get_template_directory() . '/inc/review-ask-email.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

bhp_rs_ok(
	'⭐ `test-send` is dispatched by the CLI router',
	false !== strpos( (string) $bhp_rs_engine_src, "if ( 'test-send' === \$sub ) {" )
);
bhp_rs_ok(
	'⛔⛔ test-send checks home_url(), NOT the typed --url',
	false !== strpos( (string) $bhp_rs_engine_src, "wp_parse_url( home_url(), PHP_URL_HOST )" )
);
/*
 * ⛔⛔ THE LEDGER ASSERTION: the test-send function body, isolated from
 *     the rest of the file, must contain none of the recording calls.
 */
$bhp_rs_ts_start = strpos( (string) $bhp_rs_engine_src, 'function bhp_review_ask_cli_test_send( $assoc_args, $say ) {' );
$bhp_rs_ts_end   = strpos( (string) $bhp_rs_engine_src, 'function bhp_review_ask_cli_plan(' );
$bhp_rs_ts_body  = ( false !== $bhp_rs_ts_start && false !== $bhp_rs_ts_end && $bhp_rs_ts_end > $bhp_rs_ts_start )
	? substr( (string) $bhp_rs_engine_src, $bhp_rs_ts_start, $bhp_rs_ts_end - $bhp_rs_ts_start )
	: '';

bhp_rs_ok( '⭐ The test-send function body was isolated for inspection', '' !== $bhp_rs_ts_body );

foreach ( array( 'bhp_review_ask_log_send', 'bhp_review_ask_mark_sent', 'bhp_review_ask_record_customer', 'bhp_review_ask_record_optout', 'update_option' ) as $bhp_rs_forbidden ) {
	bhp_rs_ok(
		'⛔⛔ test-send never calls ' . $bhp_rs_forbidden . '()',
		false === strpos( $bhp_rs_ts_body, $bhp_rs_forbidden . '(' )
	);
}

/* =========================================================================
 * §7 — THE COPY RAILS AND THE MERGE SLOTS
 * ====================================================================== */

bhp_rs_head( '§7 Copy rails and merge slots' );

$bhp_rs_copy = bhp_review_ask_copy( 1, $bhp_rs_v1 );

bhp_rs_ok( 'A visit order selects the visit touch-1 set', 'visit_touch1' === $bhp_rs_copy['set'], 'got: ' . ( isset( $bhp_rs_copy['set'] ) ? $bhp_rs_copy['set'] : '(unset)' ) );

/*
 * ⭐⭐ 1.19.366 · THE WORDING VARIANT IS REPORTED SEPARATELY FROM THE SET
 *     IDENTITY, and this pair of assertions is what stops the 1.19.365
 *     regression coming back. That build encoded the named/generic swap INTO
 *     the `set` key, so a visit order with no child name on record — the
 *     majority case, per CYCLE179-MKT-32 — named a set that no approval, no
 *     ledger and no CLI summary has ever heard of.
 */
bhp_rs_ok(
	'⭐ A visit order with no child name reports the GENERIC variant, still under the visit_touch1 set',
	'generic' === $bhp_rs_copy['variant'] && 'visit_touch1' === $bhp_rs_copy['set'],
	'got set: ' . $bhp_rs_copy['set'] . ', variant: ' . ( isset( $bhp_rs_copy['variant'] ) ? $bhp_rs_copy['variant'] : '(unset)' )
);
bhp_rs_ok(
	'⛔ The set key is NEVER varied by the wording branch',
	bhp_review_ask_copy_visit_touch1()['set'] === bhp_review_ask_copy_visit_touch1( $bhp_rs_v1 )['set']
);
bhp_rs_ok( 'A web order selects the web touch-1 set', 'web_touch1' === bhp_review_ask_copy( 1, $bhp_rs_w1 )['set'] );
bhp_rs_ok( 'Touch 2 selects the touch-2 set', 'touch2' === bhp_review_ask_copy( 2, $bhp_rs_v1 )['set'] );

$bhp_rs_text = wp_json_encode( $bhp_rs_copy );

bhp_rs_ok( '⛔ No merge slot survives into the rendered copy', false === strpos( $bhp_rs_text, '{' . 'ChildFirstName}' ) && false === strpos( $bhp_rs_text, '{' . 'SchoolName}' ) && false === strpos( $bhp_rs_text, '{' . 'BookTitle}' ) && false === strpos( $bhp_rs_text, '{' . 'ReviewLink}' ) );

bhp_rs_ok( '⛔ No em dash anywhere in the approved copy', false === strpos( $bhp_rs_text, "\xe2\x80\x94" ) );
bhp_rs_ok( '⛔ No en dash anywhere in the approved copy', false === strpos( $bhp_rs_text, "\xe2\x80\x93" ) );
bhp_rs_ok(
	'⛔ No standalone "we", "us" or "our" in the approved copy (Standing Rules 9.1)',
	0 === preg_match( '/\b(we|us|our|ours|we\'re|weve)\b/i', wp_strip_all_tags( implode( ' ', $bhp_rs_copy['body_before'] ) . ' ' . implode( ' ', $bhp_rs_copy['body_after'] ) . ' ' . $bhp_rs_copy['subject'] . ' ' . $bhp_rs_copy['postscript'] ) )
);

bhp_rs_ok( 'The school name resolves from the registry', 'Probe Elementary' === bhp_review_ask_school_name( $bhp_rs_v1 ) );
bhp_rs_ok( 'The book title resolves to the short title', 'The Mariana Trench' === bhp_review_ask_book_title( $bhp_rs_v1 ), 'got: ' . bhp_review_ask_book_title( $bhp_rs_v1 ) );
bhp_rs_ok(
	'⭐ The review link is the per-title SITE page, not Amazon, and never the bare /review/',
	bhp_review_ask_review_link( $bhp_rs_v1 ) === home_url( '/review/the-mariana-trench/' ),
	'got: ' . bhp_review_ask_review_link( $bhp_rs_v1 )
);
bhp_rs_ok( 'Exactly one review link in the visit set', 1 === count( $bhp_rs_copy['links'] ) );

bhp_rs_ok(
	'⭐ For a multi-book order the link is the FIRST chapter book on the order',
	bhp_review_ask_review_link( $bhp_rs_v3 ) === home_url( '/review/the-mariana-trench/' ),
	'got: ' . bhp_review_ask_review_link( $bhp_rs_v3 )
);

bhp_rs_ok(
	'⭐ With no child name known, the fallback is "your reader"',
	'your reader' === bhp_review_ask_child_first_name( $bhp_rs_v1 ),
	'got: ' . bhp_review_ask_child_first_name( $bhp_rs_v1 )
);
bhp_rs_ok( '... and it is reported as not-known', ! bhp_review_ask_child_first_name_is_known( $bhp_rs_v1 ) );

/*
 * ⛔ THE PARENT'S NAME IS NEVER USED AS THE CHILD'S. The probe orders all carry
 *    billing first name "Testparent"; if that ever appears where the child's
 *    name goes, a real parent is being addressed as their own child.
 */
bhp_rs_ok(
	'⛔ The billing first name is NEVER used as the child first name',
	'Testparent' !== bhp_review_ask_child_first_name( $bhp_rs_v1 )
);

/*
 * ⭐ A NAME-SHAPED FIELD IS USED WHEN ONE EXISTS, AND A TWO-CHILD FIELD IS NOT.
 *    Gimli hit the two-child case twice in sixteen real orders.
 */
$bhp_rs_named = bhp_rs_make_order( 'rs-named@example.com', 9, array_merge( $bhp_rs_visit_meta, array( '_bhp_school_visit_child_first_name' => 'Rowan' ) ), array( $bhp_rs_pb[0] ) );
bhp_rs_ok( 'A single child first name is used', 'Rowan' === bhp_review_ask_child_first_name( $bhp_rs_named ) );

$bhp_rs_two = bhp_rs_make_order( 'rs-two@example.com', 9, array_merge( $bhp_rs_visit_meta, array( '_bhp_school_visit_child_first_name' => 'Rowan and Wren' ) ), array( $bhp_rs_pb[0] ) );
/*
 * ⭐⭐ 1.19.369 · SEAL 994: *"Always use names when we can."* ⛔ SUPERSEDED
 *     ASSERTION, PRESERVED RATHER THAN DELETED: '⭐ Two children on one order
 *     fall back to "your reader" rather than reading as one name',
 *     'your reader' === bhp_review_ask_child_first_name( $bhp_rs_two ).
 */
bhp_rs_ok(
	'⭐⭐ Two children are BOTH named, joined with "and"',
	'Rowan and Wren' === bhp_review_ask_child_first_name( $bhp_rs_two ),
	'got: ' . bhp_review_ask_child_first_name( $bhp_rs_two )
);
bhp_rs_ok( '... and two children is reported as KNOWN, not as the fallback', bhp_review_ask_child_first_name_is_known( $bhp_rs_two ) );

/*
 * ⛔ AN UNRESOLVABLE SLOT IS A DECLINE, NOT A BLANK IN A REAL EMAIL. An order
 *    with a visit slug the registry knows but NO chapter book has no
 *    {BookTitle} and no {ReviewLink}.
 */
$bhp_rs_nobook = bhp_rs_make_order( 'rs-nobook@example.com', 9, $bhp_rs_visit_meta, array() );
bhp_rs_ok(
	'⭐ An order with no chapter book declines unresolved_merge_slot rather than rendering a blank',
	'unresolved_merge_slot' === bhp_review_ask_decline_reason( $bhp_rs_nobook ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_rs_nobook )
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ MERRY'S APPROVED SENTENCES, VERBATIM.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ 1.19.366 · THE SOURCE OF THESE PINS CHANGED, AND THAT IS WHY FIVE OF
 *     THEM FAILED ON STAGING AT 1.19.365. They were transcribed from
 *     `CYCLE179-MKT-REVIEW-ASKS.md` §1, which 1.19.365 SUPERSEDED: seal 982
 *     names `CYCLE179-MKT-REVIEW-SEQ-V2.md` (md5
 *     `1ecd9c75acfc755df0e121b47ca73842`) as the approved copy, and the engine
 *     was rewritten to carry V2 §2. The pins were left behind pointing at
 *     prose that is deliberately no longer shipped, so they were asserting the
 *     ABSENCE of the approval rather than its presence.
 *
 * ⚠ THE FIVE REMOVED PINS ARE NAMED HERE RATHER THAN DELETED SILENTLY, so a
 *   future reader can tell a supersession from an accident:
 *     · "Thank you for picking up {BookTitle} at {SchoolName} last week."
 *     · "Signing it for {ChildFirstName} was the best part of my morning."
 *     · "Two or three honest sentences is plenty, and honest is the useful
 *        part."                                      (reworded by V2 §2)
 *     · "If you would rather leave it on Amazon instead, that helps too"
 *                                                    (removed by SEAL 977)
 *     · "Either way, thank you for reading with your little human."
 *
 * ⭐ SOURCE OF THE PINS BELOW: V2 §2, via Gandalf's round-5 ruling on
 *    CYCLE179-LD-47. ⛔ Asserted against the UNMERGED set so a slot
 *    substitution cannot mask a reworded sentence.
 */
$bhp_rs_raw      = bhp_review_ask_copy_visit_touch1();
$bhp_rs_raw_text = implode( ' ', array_merge( $bhp_rs_raw['body_before'], $bhp_rs_raw['body_after'], array( $bhp_rs_raw['subject'], $bhp_rs_raw['links_lead'], $bhp_rs_raw['stars_caption'], $bhp_rs_raw['postscript'] ) ) );

foreach ( array(
	'A small favor about the book',
	'Your reader has had {BookTitle}',
	'Would you rate it? It takes about ten seconds, and if you have another minute after that, two or three honest sentences would help the next parent decide. Honest is the useful part.',
	'Tap a star to rate {BookTitle}. Then two or three honest sentences on the next page.',
	'Thank you for reading together.',
	'I answer every one.',
) as $bhp_rs_phrase ) {
	bhp_rs_ok( 'Approved phrase present (V2 §2, generic): "' . substr( $bhp_rs_phrase, 0, 46 ) . '"', false !== strpos( $bhp_rs_raw_text, $bhp_rs_phrase ), 'not found in the generic set' );
}

/*
 * ⭐ AND THE NAMED WORDING IS PINNED TOO. It is the branch a real order takes
 *    only when a child first name is on record (CYCLE179-MKT-32 says that is
 *    the minority), and until now nothing asserted its sentences at all.
 */
$bhp_rs_raw_named      = bhp_review_ask_copy_visit_touch1( $bhp_rs_named );
$bhp_rs_raw_named_text = implode( ' ', array_merge( $bhp_rs_raw_named['body_before'], $bhp_rs_raw_named['body_after'], array( $bhp_rs_raw_named['subject'], $bhp_rs_raw_named['postscript'] ) ) );

foreach ( array(
	'{ChildFirstName} has had {BookTitle}',
	'Thank you for reading with {ChildFirstName}.',
	'P.S. If {ChildFirstName} has a question about the book, hit reply. I answer every one.',
) as $bhp_rs_phrase ) {
	bhp_rs_ok( 'Approved phrase present (V2 §2, named): "' . substr( $bhp_rs_phrase, 0, 46 ) . '"', false !== strpos( $bhp_rs_raw_named_text, $bhp_rs_phrase ), 'not found in the named set' );
}

/*
 * ⛔⛔ 1.19.366 · AND THE SUPERSEDED PROSE IS ASSERTED ABSENT. Removing a pin
 *     leaves nothing watching the sentence it used to watch; this puts the
 *     watch back the other way round, so restoring the ASKS §1 wording from an
 *     older draft fails the suite instead of shipping quietly.
 */
foreach ( array(
	'Thank you for picking up',
	'was the best part of my morning',
	'is plenty, and honest is the useful part',
	'leave it on Amazon instead',
	'your little human',
) as $bhp_rs_gone ) {
	bhp_rs_ok(
		'⛔ SUPERSEDED prose absent from every shipped set: "' . $bhp_rs_gone . '"',
		false === strpos( (string) $bhp_rs_all_sets, $bhp_rs_gone )
			// ⚠ $bhp_rs_all_sets holds the GENERIC visit wording. The named
			//   wording is a separate render and is checked here as well.
			&& false === strpos( (string) wp_json_encode( $bhp_rs_raw_named ), $bhp_rs_gone )
	);
}

bhp_rs_ok( '⛔ No price, coupon or shipping figure anywhere in the copy', 0 === preg_match( '/\$\d|PARENT10|coupon|discount|free shipping/i', $bhp_rs_raw_text ) );
bhp_rs_ok( '⛔ No review count, rating or reaction claim anywhere in the copy', 0 === preg_match( '/\b(\d+(\.\d)?\s*star|rating|reviews so far|other parents (say|told)|classroom result)\b/i', $bhp_rs_raw_text ) );

/* =========================================================================
 * §8 — CLEANUP
 * ====================================================================== */

bhp_rs_head( '§8 Cleanup' );

remove_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );
remove_filter( $bhp_rs_visit_hook, $bhp_rs_fake_visits, 99 );

bhp_rs_ok( 'The forced send window was removed', ! has_filter( 'bhp_review_ask_in_send_window', '__return_true' ) );
bhp_rs_ok( 'The probe visit registry filter was removed', '' === bhp_review_ask_visit_date( $bhp_rs_v1 ) );

/*
 * ⭐⭐ AND THE REAL REGISTRY IS PROVED UNHARMED. The probe never touched the
 *     database, so this asserts the stated property rather than restoring one:
 *     every genuine visit that was there before the run is still there, and the
 *     probe slug is gone.
 */
$bhp_rs_after_visits = get_option( $bhp_rs_visit_option, array() );
$bhp_rs_after_visits = is_array( $bhp_rs_after_visits ) ? $bhp_rs_after_visits : array();

bhp_rs_ok(
	'⭐ The real bhp_school_visits option is byte-identical to its pre-run value',
	$bhp_rs_after_visits == $bhp_rs_real_visits, // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- array value compare is the assertion.
	'rows before: ' . count( $bhp_rs_real_visits ) . ', after: ' . count( $bhp_rs_after_visits )
);
bhp_rs_ok(
	'⛔ The probe slug was never written to the registry option',
	! isset( $bhp_rs_after_visits[ $bhp_rs_visit_slug ] )
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ 1.19.366 · THE FIXTURE TEARDOWN MOVED OUT OF THIS SECTION. IT USED TO
 *     RUN HERE, AND THAT IS WHY §9.7 REPORTED AN EMPTY NAME BOX ON STAGING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * §9 was appended in 1.19.364 AFTER this teardown, and the teardown was not
 * moved with it. So by the time §9.7 minted a genuine pre-fill token for
 * `$bhp_rs_v1` and asked `bhp_review_prefill()` for the name box, every probe
 * order had already been force-deleted from the database.
 *
 * ⭐ AND THE ENGINE WAS RIGHT THE WHOLE TIME. `bhp_review_prefill()` does not
 *    carry the name in the token — by design, so the name cannot be forged —
 *    it looks the order up through `wc_get_order()` and returns '' when the
 *    order is gone. Deleting the order first and then asserting the name is
 *    asking a correct function to answer for a fixture that no longer exists.
 *
 * ⛔ THE ASSERTION IS UNCHANGED. It still demands the literal 'Testparent'
 *    from the order rather than from the token. What changed is that the
 *    order is still there when it is asked. The teardown now runs at the very
 *    end of the file, below §10, and it still force-deletes everything.
 */
bhp_rs_ok( 'Fixture teardown deferred to the end of the run (see the note above)', true );

/* =========================================================================
 * ⭐ §9 — SEAL 977: THE STAR ROW, THE PRE-FILL TOKEN, AND THE +4 REMINDER
 *
 * ⛔ EVERY ASSERTION HERE IS ABOUT A MECHANISM, NOT ABOUT WORDS. The copy this
 *    machinery carries is Merry's and Andrew's; nothing below asserts a
 *    sentence that has not already been approved somewhere else.
 * ====================================================================== */

bhp_rs_head( '§9 Star row, pre-fill token, +4 reminder' );

foreach ( array( 'bhp_review_star_labels', 'bhp_review_star_url', 'bhp_review_prefill_token', 'bhp_review_prefill_verify', 'bhp_review_requested_rating', 'bhp_review_ask_star_row' ) as $bhp_rs_fn9 ) {
	bhp_rs_ok( "{$bhp_rs_fn9}() is loaded", function_exists( $bhp_rs_fn9 ) );
}

/* ---- 9.1 the five labels, and that they are the site form's own ---- */

$bhp_rs_labels = bhp_review_star_labels();

bhp_rs_ok( '⭐ There are exactly five rating labels', 5 === count( $bhp_rs_labels ) );
bhp_rs_ok( '⭐ They are keyed 5 down to 1, in that order', array( 5, 4, 3, 2, 1 ) === array_keys( $bhp_rs_labels ) );
bhp_rs_ok( '"5 stars: loved it", verbatim', '5 stars: loved it' === $bhp_rs_labels[5], 'got: ' . $bhp_rs_labels[5] );
bhp_rs_ok( '"4 stars: really good", verbatim', '4 stars: really good' === $bhp_rs_labels[4], 'got: ' . $bhp_rs_labels[4] );
bhp_rs_ok( '"3 stars: it was okay", verbatim', '3 stars: it was okay' === $bhp_rs_labels[3], 'got: ' . $bhp_rs_labels[3] );
bhp_rs_ok( '"2 stars: not for us", verbatim', '2 stars: not for us' === $bhp_rs_labels[2], 'got: ' . $bhp_rs_labels[2] );
bhp_rs_ok( '"1 star: did not work for us", verbatim', '1 star: did not work for us' === $bhp_rs_labels[1], 'got: ' . $bhp_rs_labels[1] );

/*
 * ⛔ NO EM DASHES. The 1.19.262 ruling that produced these labels replaced
 *    "5 stars — loved it" with a colon. A future edit that reintroduces one
 *    breaks the store's email copy rail as well as the page.
 */
bhp_rs_ok(
	'⛔ No label contains an em dash',
	false === strpos( implode( ' ', $bhp_rs_labels ), "\xe2\x80\x94" )
);

/* ---- 9.2 the star URLs resolve to the right page and the right rating ---- */

$bhp_rs_row = bhp_review_ask_star_row( $bhp_rs_v1 );

bhp_rs_ok( '⭐ The star row for a one-book visit order has five rows', 5 === count( $bhp_rs_row ), 'got: ' . count( $bhp_rs_row ) );
/*
 * ⭐⭐ 1.19.369 · ASCENDING, LEFT TO RIGHT. ANDREW, SEAL 998: *"5 stars in a
 *     row from left to right."* ⛔ SUPERSEDED ASSERTION, PRESERVED RATHER THAN
 *     DELETED: '⭐ It starts at 5 and ends at 1', 5 === $bhp_rs_row[0]['rating']
 *     && 1 === $bhp_rs_row[4]['rating'].
 */
bhp_rs_ok(
	'⭐⭐ It starts at 1 and ends at 5, left to right',
	1 === $bhp_rs_row[0]['rating'] && 5 === $bhp_rs_row[4]['rating'],
	'got: ' . implode( ',', wp_list_pluck( $bhp_rs_row, 'rating' ) )
);
bhp_rs_ok(
	'⭐ Every intermediate star is in order, 1 2 3 4 5',
	array( 1, 2, 3, 4, 5 ) === array_map( 'intval', wp_list_pluck( $bhp_rs_row, 'rating' ) )
);
bhp_rs_ok(
	'⛔ The SITE FORM is untouched and still reads 5 down to 1',
	array( 5, 4, 3, 2, 1 ) === array_keys( bhp_review_star_labels() )
);

$bhp_rs_book_key = bhp_review_ask_first_chapter_book_key( $bhp_rs_v1 );
$bhp_rs_book_url = bhp_review_page_url( $bhp_rs_book_key );

bhp_rs_ok( 'The probe order resolves to a real review page', '' !== $bhp_rs_book_url, 'got: ' . $bhp_rs_book_url );

foreach ( $bhp_rs_row as $bhp_rs_star ) {
	$bhp_rs_n = (int) $bhp_rs_star['rating'];

	bhp_rs_ok(
		"⭐ Star {$bhp_rs_n} links to THIS book's review page",
		0 === strpos( $bhp_rs_star['url'], $bhp_rs_book_url ),
		'got: ' . $bhp_rs_star['url']
	);
	bhp_rs_ok(
		"⭐ Star {$bhp_rs_n} carries rating={$bhp_rs_n} and no other rating",
		false !== strpos( $bhp_rs_star['url'], 'rating=' . $bhp_rs_n ),
		'got: ' . $bhp_rs_star['url']
	);
	bhp_rs_ok(
		"Star {$bhp_rs_n}'s label is the site form's label for {$bhp_rs_n}",
		$bhp_rs_labels[ $bhp_rs_n ] === $bhp_rs_star['label']
	);
}

/*
 * ⛔⛔ THE BARE /review/ PATH IS A LIVE 404 AND MUST NEVER BE CONSTRUCTED.
 *     Merry's V2 §5 records it as verified on 2026-09-05.
 */
foreach ( $bhp_rs_row as $bhp_rs_star ) {
	bhp_rs_ok(
		'⛔ Star ' . $bhp_rs_star['rating'] . ' is not the bare /review/ path',
		home_url( '/review/' ) !== strtok( $bhp_rs_star['url'], '?' )
	);
}

bhp_rs_ok(
	'⛔ An unknown book key yields no star URL rather than a bare /review/',
	'' === bhp_review_star_url( 'no_such_book', 5 )
);
bhp_rs_ok( '⛔ Rating 0 yields no URL', '' === bhp_review_star_url( $bhp_rs_book_key, 0 ) );
bhp_rs_ok( '⛔ Rating 6 yields no URL', '' === bhp_review_star_url( $bhp_rs_book_key, 6 ) );

/*
 * ⭐⭐ AND AN ORDER WITH NO CHAPTER BOOK BUILDS NO ROW AT ALL. Four stars would
 *     be a steered row; this is why the function returns array() rather than a
 *     partial set. The decline that follows from it is asserted in §7.
 */
bhp_rs_ok(
	'⭐⭐ An order with no chapter book builds NO star row',
	array() === bhp_review_ask_star_row( $bhp_rs_nobook ),
	'got ' . count( bhp_review_ask_star_row( $bhp_rs_nobook ) ) . ' row(s)'
);

/* ---- 9.3 the shipped copy sets all declare a star row ---- */

foreach ( array( 'visit touch 1' => bhp_review_ask_copy_visit_touch1(), 'web touch 1' => bhp_review_ask_copy_web_touch1(), 'touch 2' => bhp_review_ask_copy_touch2() ) as $bhp_rs_name => $bhp_rs_set ) {
	bhp_rs_ok( "⭐ The {$bhp_rs_name} set declares a star row", ! empty( $bhp_rs_set['stars'] ) );
}

/* ---- 9.4 ONE DESTINATION: no Amazon anywhere in a shipped set ---- */

foreach ( array( 'visit touch 1' => bhp_review_ask_copy_visit_touch1(), 'web touch 1' => bhp_review_ask_copy_web_touch1(), 'touch 2' => bhp_review_ask_copy_touch2() ) as $bhp_rs_name => $bhp_rs_set ) {
	$bhp_rs_blob = strtolower( (string) wp_json_encode( $bhp_rs_set ) );

	bhp_rs_ok(
		"⛔⛔ The {$bhp_rs_name} set names Amazon nowhere (seal 977, one destination)",
		false === strpos( $bhp_rs_blob, 'amazon' ),
		'the set still mentions Amazon'
	);
}

/* ---- 9.5 the pre-fill token: signs, validates, expires, and is scoped ---- */

$bhp_rs_tok = bhp_review_prefill_token( 4242, 'Parent@Example.com', $bhp_rs_book_key );

bhp_rs_ok( '⭐ A token is minted for a real order/email/book', '' !== $bhp_rs_tok );

$bhp_rs_payload = bhp_review_prefill_verify( $bhp_rs_tok );

bhp_rs_ok( '⭐ It validates', false !== $bhp_rs_payload );
bhp_rs_ok( 'It carries the order id', 4242 === $bhp_rs_payload['order_id'] );
bhp_rs_ok( '⭐ It lower-cases the email, so the signature is stable', 'parent@example.com' === $bhp_rs_payload['email'] );
bhp_rs_ok( 'It carries the book key', $bhp_rs_book_key === $bhp_rs_payload['key'] );

/*
 * ⛔⛔ TAMPERING FAILS. One character of the payload changed and the signature
 *     no longer matches. This is the assertion that makes "the token is signed"
 *     a fact rather than a claim in a comment.
 */
$bhp_rs_bad = substr( $bhp_rs_tok, 0, 3 ) . ( 'X' === $bhp_rs_tok[3] ? 'Y' : 'X' ) . substr( $bhp_rs_tok, 4 );
bhp_rs_ok( '⛔⛔ A tampered payload does not validate', false === bhp_review_prefill_verify( $bhp_rs_bad ) );

bhp_rs_ok( '⛔ A tampered signature does not validate', false === bhp_review_prefill_verify( strtok( $bhp_rs_tok, '.' ) . '.' . str_repeat( 'a', 64 ) ) );
bhp_rs_ok( '⛔ Garbage does not validate', false === bhp_review_prefill_verify( 'not-a-token' ) );
bhp_rs_ok( '⛔ An empty token does not validate', false === bhp_review_prefill_verify( '' ) );

/*
 * ⭐⭐ EXPIRY IS TESTED BY MOVING THE CLOCK, NOT BY MINTING A STALE TOKEN,
 *     because a token minted in the past would also prove nothing about the
 *     ordering of the signature check and the expiry check.
 */
$bhp_rs_exp = time() + ( 30 * DAY_IN_SECONDS );
$bhp_rs_t30 = bhp_review_prefill_token( 4242, 'parent@example.com', $bhp_rs_book_key, $bhp_rs_exp );

bhp_rs_ok( '⭐ A 30-day token is valid the day before it expires', false !== bhp_review_prefill_verify( $bhp_rs_t30, $bhp_rs_exp - DAY_IN_SECONDS ) );
bhp_rs_ok( '⭐⭐ ... and is REFUSED one second after it expires', false === bhp_review_prefill_verify( $bhp_rs_t30, $bhp_rs_exp + 1 ) );
bhp_rs_ok( '⭐ The default TTL is 30 days', ( 30 * DAY_IN_SECONDS ) === BHP_REVIEW_PREFILL_TTL );

/*
 * ⛔ AND A TOKEN FOR ONE BOOK DOES NOT PRE-FILL ANOTHER BOOK'S FORM.
 */
$bhp_rs_other = '';
foreach ( array_keys( bhp_review_route_slugs() ) as $bhp_rs_k ) {
	if ( $bhp_rs_k !== $bhp_rs_book_key ) {
		$bhp_rs_other = $bhp_rs_k;
		break;
	}
}
if ( '' !== $bhp_rs_other ) {
	$bhp_rs_p2 = bhp_review_prefill_verify( bhp_review_prefill_token( 4242, 'parent@example.com', $bhp_rs_other ) );
	bhp_rs_ok( '⛔ A token minted for another title carries that other key', $bhp_rs_other === $bhp_rs_p2['key'] );
}

bhp_rs_ok( '⛔ A token cannot be minted for a bad email', '' === bhp_review_prefill_token( 4242, 'not-an-email', $bhp_rs_book_key ) );
bhp_rs_ok( '⛔ A token cannot be minted for order 0', '' === bhp_review_prefill_token( 0, 'parent@example.com', $bhp_rs_book_key ) );

/* ---- 9.6 the star URLs carry the token, and it validates from the URL ---- */

/*
 * ⛔⛔ 1.19.370 · THIS READ `$bhp_rs_row[3]` AND IT WAS AN INDEX LEFT BEHIND BY
 *     A REORDER. Under the descending row (5,4,3,2,1) offset 3 was the 2-star
 *     link. 1.19.369 made the row ASCENDING for seal 998, so offset 3 became
 *     the 4-star link and an assertion named "The 2-star link carries rating=2"
 *     started failing while describing a defect that did not exist.
 *
 * ⭐ THE FIX IS NOT `$bhp_rs_row[1]`. An offset is the wrong way to ask this
 *    question at all: it re-breaks the next time anyone touches the order. The
 *    row is SEARCHED FOR THE RATING, so the assertion is true under any order
 *    and the ORDER ITSELF is asserted separately, on purpose, below.
 */
$bhp_rs_qs   = array();
$bhp_rs_url  = '';
$bhp_rs_seen = array();

foreach ( $bhp_rs_row as $bhp_rs_cell ) {
	$bhp_rs_seen[] = (int) $bhp_rs_cell['rating'];

	if ( 2 === (int) $bhp_rs_cell['rating'] ) {
		$bhp_rs_url = (string) $bhp_rs_cell['url'];
	}
}

bhp_rs_ok(
	'⭐⭐ The row runs 1,2,3,4,5 left to right (seal 998)',
	array( 1, 2, 3, 4, 5 ) === $bhp_rs_seen,
	'got: ' . implode( ',', $bhp_rs_seen )
);

bhp_rs_ok( '⭐ A 2-star cell exists in the row', '' !== $bhp_rs_url );

parse_str( (string) wp_parse_url( $bhp_rs_url, PHP_URL_QUERY ), $bhp_rs_qs );

bhp_rs_ok(
	'⭐ The 2-star link carries rating=2',
	isset( $bhp_rs_qs['rating'] ) && '2' === (string) $bhp_rs_qs['rating'],
	'got: ' . ( isset( $bhp_rs_qs['rating'] ) ? (string) $bhp_rs_qs['rating'] : '(none)' )
);
bhp_rs_ok( '⭐ It carries a pre-fill token', ! empty( $bhp_rs_qs['bhp_pf'] ) );
bhp_rs_ok(
	'⭐⭐ ... and that token validates and names THIS order',
	! empty( $bhp_rs_qs['bhp_pf'] ) && (int) $bhp_rs_v1->get_id() === (int) bhp_review_prefill_verify( $bhp_rs_qs['bhp_pf'] )['order_id']
);
bhp_rs_ok(
	'⭐ ... and the email it carries is the order\'s billing email',
	! empty( $bhp_rs_qs['bhp_pf'] ) && strtolower( $bhp_rs_v1->get_billing_email() ) === bhp_review_prefill_verify( $bhp_rs_qs['bhp_pf'] )['email']
);

/* ---- 9.7 the rating query parameter is sanitised ---- */

$bhp_rs_get_backup = $_GET;

foreach ( array( '5' => 5, '1' => 1, '3' => 3, '0' => 0, '6' => 0, '-1' => 0, '5.0' => 0, '+5' => 0, 'five' => 0, '' => 0, '<script>' => 0, '5abc' => 0, ' 4 ' => 4 ) as $bhp_rs_raw => $bhp_rs_want ) {
	$_GET['rating'] = $bhp_rs_raw;
	bhp_rs_ok(
		"⛔ ?rating=" . var_export( $bhp_rs_raw, true ) . " resolves to {$bhp_rs_want}",
		$bhp_rs_want === bhp_review_requested_rating(),
		'got: ' . bhp_review_requested_rating()
	);
}

$_GET['rating'] = array( 5 );
bhp_rs_ok( '⛔ ?rating[]=5 (an array) resolves to 0, not 5', 0 === bhp_review_requested_rating() );

unset( $_GET['rating'] );
bhp_rs_ok( '⭐⭐ With NO rating parameter the form gets 0 and nothing is pre-selected', 0 === bhp_review_requested_rating() );

/*
 * ⭐⭐ AND WITH NO TOKEN, NOTHING IS PRE-FILLED. This is the "behaves normally
 *     without them" half of the brief, and it is asserted rather than assumed.
 */
unset( $_GET['bhp_pf'] );
$bhp_rs_pf = bhp_review_prefill( $bhp_rs_book_key );
bhp_rs_ok( '⭐⭐ With no token, the name box is empty', '' === $bhp_rs_pf['author'] );
bhp_rs_ok( '⭐⭐ With no token, the email box is empty', '' === $bhp_rs_pf['email'] );

$_GET['bhp_pf'] = 'forged.' . str_repeat( 'a', 64 );
$bhp_rs_pf = bhp_review_prefill( $bhp_rs_book_key );
bhp_rs_ok( '⛔ A forged token pre-fills nothing and says nothing', '' === $bhp_rs_pf['author'] && '' === $bhp_rs_pf['email'] );

$_GET['bhp_pf'] = bhp_review_prefill_token( (int) $bhp_rs_v1->get_id(), $bhp_rs_v1->get_billing_email(), $bhp_rs_book_key );
$bhp_rs_pf = bhp_review_prefill( $bhp_rs_book_key );
bhp_rs_ok(
	'⭐⭐ A genuine token pre-fills the email box',
	strtolower( $bhp_rs_v1->get_billing_email() ) === $bhp_rs_pf['email'],
	'got: ' . $bhp_rs_pf['email']
);
bhp_rs_ok(
	'⭐ ... and the name box, from the order rather than from the token',
	'Testparent' === $bhp_rs_pf['author'],
	'got: ' . $bhp_rs_pf['author']
);

if ( '' !== $bhp_rs_other ) {
	$bhp_rs_pf = bhp_review_prefill( $bhp_rs_other );
	bhp_rs_ok(
		'⛔⛔ The SAME token pre-fills NOTHING on a different book\'s page',
		'' === $bhp_rs_pf['author'] && '' === $bhp_rs_pf['email']
	);
}

$_GET = $bhp_rs_get_backup;
bhp_rs_ok( 'The suite restored $_GET', true );

/* ---- 9.8 the day-0 email asks for nothing ---- */

if ( function_exists( 'bhp_visit_email_copy_sets' ) ) {
	foreach ( bhp_visit_email_copy_sets() as $bhp_rs_slug => $bhp_rs_set ) {
		$bhp_rs_blob = strtolower( (string) wp_json_encode( $bhp_rs_set ) );

		bhp_rs_ok(
			"⭐⭐ Day 0 set '{$bhp_rs_slug}' names Amazon nowhere",
			false === strpos( $bhp_rs_blob, 'amazon.com' ) && false === strpos( $bhp_rs_blob, 'amazon review' ),
			'the set still points at Amazon'
		);
		bhp_rs_ok(
			"⭐⭐ Day 0 set '{$bhp_rs_slug}' contains no review link",
			false === strpos( $bhp_rs_blob, '/review/' ) && false === strpos( $bhp_rs_blob, '{reviewlink}' ),
			'the day-0 email carries a review link'
		);
		bhp_rs_ok(
			"⛔ Day 0 set '{$bhp_rs_slug}' does not ask for a review",
			false === strpos( $bhp_rs_blob, 'write a review' ),
			'the day-0 email still asks for a review'
		);
	}

	/*
	 * ⭐⭐ 1.19.365 · INVERTED BY SEAL 982. Until this build the _default
	 *     day-0 set was Merry's V2 §1 prose awaiting Andrew, and this
	 *     asserted it could not send. He approved it, so the assertion now
	 *     reads the other way. ⛔ The GATE is still proved, immediately
	 *     below, by asking an unapproved key the same question.
	 */
	bhp_rs_ok(
		'⭐⭐ The rewritten _default day-0 set is APPROVED (seal 982) and may render',
		bhp_visit_email_copy_is_approved( BHP_VISIT_EMAIL_DEFAULT_KEY )
	);
	/*
	 * =====================================================================
	 * ⭐⭐ 1.19.369 · SEAL 994 REPLACED PER-SCHOOL ROUTING WITH ONE GENERIC
	 *     SET FOR EVERY VISIT, SO THE "UNKNOWN SLUG" ASSERTIONS ARE REWRITTEN
	 *     RATHER THAN DELETED. READ THIS BEFORE RESTORING THEM.
	 * =====================================================================
	 *
	 * ⛔ SUPERSEDED ASSERTIONS, PRESERVED VERBATIM:
	 *
	 *      '⛔⛔ THE DAY-0 GATE STILL FIRES: an unknown/unapproved set key is
	 *       not approved',  ! bhp_visit_email_copy_is_approved( 'no-such-set-ever-2026' )
	 *
	 *      '⭐ The approved Adams day-0 set is still approved',
	 *       bhp_visit_email_copy_is_approved( 'adams-2026-08-28' )
	 *
	 *      '⛔⛔ An UNAPPROVED day-0 set still renders NOTHING rather than
	 *       placeholder text',
	 *       ! bhp_visit_email_may_render( 'no-such-set-ever-2026', $bhp_rs_v1 )
	 *
	 * ⚠ WHY THEY CANNOT STAND. There is exactly ONE day-0 set now and it is
	 *   the set for EVERY visit slug by decision, so "a slug the theme does not
	 *   carry" describes every real slug including `adams-2026-08-28`. Asserting
	 *   that such a slug is unapproved would now be asserting that the day-0
	 *   email must never send to anyone.
	 *
	 * ⭐ WHAT REPLACES THEM PROVES THE SAME TWO THINGS THAT ARE STILL TRUE:
	 *   the gate fires on NO SLUG AT ALL, and it fires on a FILTERED set that
	 *   carries `approved => false`. The second is the case the seam exists for
	 *   and is the one that could actually reach a parent.
	 */
	bhp_rs_ok(
		'⭐⭐ SEAL 994: every visit slug resolves to the ONE generic approved set',
		bhp_visit_email_copy_is_approved( 'adams-2026-08-28' )
			&& bhp_visit_email_copy_is_approved( 'no-such-set-ever-2026' )
			&& bhp_visit_email_copy( 'adams-2026-08-28' ) === bhp_visit_email_copy( 'no-such-set-ever-2026' )
	);
	bhp_rs_ok(
		'⛔ The per-school Adams set is GONE from the sets array (retired to comments)',
		! isset( bhp_visit_email_copy_sets()['adams-2026-08-28'] )
			&& array( BHP_VISIT_EMAIL_DEFAULT_KEY ) === array_keys( bhp_visit_email_copy_sets() ),
		'keys: ' . implode( ',', array_keys( bhp_visit_email_copy_sets() ) )
	);
	bhp_rs_ok(
		'⛔ The Adams-only facts cannot reach any parent from this file',
		false === stripos( (string) wp_json_encode( bhp_visit_email_copy_sets() ), '1st and 2nd Graders' )
			&& false === stripos( (string) wp_json_encode( bhp_visit_email_copy_sets() ), 'thirty or so' )
	);
	bhp_rs_ok(
		'⛔⛔ THE DAY-0 GATE STILL FIRES: no slug at all renders nothing',
		! bhp_visit_email_may_render( '', $bhp_rs_v1 ) && ! bhp_visit_email_copy_is_approved( '' )
	);

	/*
	 * ⛔⛔ AND AN UNAPPROVED SET SUPPLIED THROUGH THE FILTER SEAM STILL
	 *     RENDERS NOTHING. This is the path that could actually put unapproved
	 *     prose in front of a parent, and it is closed.
	 */
	$bhp_rs_unapproved = function () {
		return array(
			'approved'  => false,
			'subject'   => 'UNAPPROVED PROBE SUBJECT',
			'heading'   => 'UNAPPROVED PROBE HEADING',
			'preheader' => 'UNAPPROVED PROBE PREHEADER',
			'body'      => array( 'UNAPPROVED PROBE BODY' ),
		);
	};

	add_filter( 'bhp_visit_email_copy', $bhp_rs_unapproved, 99 );

	bhp_rs_ok(
		'⛔⛔ An UNAPPROVED day-0 set still renders NOTHING rather than placeholder text',
		! bhp_visit_email_copy_is_approved( 'adams-2026-08-28' )
			&& ! bhp_visit_email_may_render( 'adams-2026-08-28', $bhp_rs_v1 )
	);

	remove_filter( 'bhp_visit_email_copy', $bhp_rs_unapproved, 99 );

	bhp_rs_ok(
		'The suite removed its unapproved-copy filter',
		bhp_visit_email_copy_is_approved( BHP_VISIT_EMAIL_DEFAULT_KEY )
	);
} else {
	bhp_rs_ok( 'SKIPPED: inc/visit-completed-email.php is not loaded', true );
}

foreach ( $bhp_rs_snapshot as $bhp_rs_option => $bhp_rs_value ) {
	update_option( $bhp_rs_option, $bhp_rs_value, false );
}
bhp_rs_ok( 'The four registry options were restored to their pre-run values', true );

/* =========================================================================
 * ⭐ §10 — THE REVIEW PAGE'S OWN COPY
 *
 * ⛔⛔ WHY THIS SECTION IS IN **THIS** SUITE AND NOT A COSMETIC ONE. Every set
 *     above sends the buyer to exactly one place: the per-title review page,
 *     through `bhp_review_ask_review_link()` and through all five stars of
 *     `bhp_review_ask_star_row()`. The email obeys a copy rail — no em dash,
 *     no standalone "we" (Standing Rules 9.1) — and §6B and §7 enforce it
 *     hard. The page those emails land on was obeying neither, so the rail
 *     ended at the inbox. 1.19.366 extends it one click further.
 *
 * ⚠ WHAT THIS SECTION DELIBERATELY DOES **NOT** COVER, stated rather than
 *   quietly excluded:
 *
 *   1. APPROVED CUSTOMER REVIEW TEXT. `bhp_review_render_section()` renders
 *      real parents' sentences alongside the form. Those are not the store's
 *      copy and the store does not get to rewrite them, so this section reads
 *      the FORM — which is the whole of the page's own voice — and not the
 *      section.
 *   2. THE POST-SUBMIT "THANKS" BRANCH. ⭐ RESOLVED IN 1.19.367 — CYCLE179-LD-51
 *      is closed. Both copies of *"Thank you [em dash] your review has been
 *      sent."* (`template-parts/reviews/review-section.php` and
 *      `template-parts/reviews/standalone-review-page.php`) now read *"Thank
 *      you. Your review has been sent."* That branch only renders after a real
 *      POST, so it cannot be reached by rendering the form here; it is
 *      asserted at SOURCE level at the end of this section instead, and the
 *      section says so rather than letting a source pin masquerade as a
 *      render pin.
 *
 * ⛔⛔ 1.19.367 — WHY THIS SECTION RENDERS LOGGED OUT, AND WHY IT DID NOT BEFORE.
 *     THE ROOT CAUSE OF THE 1.19.366 PRIVACY-LINE FAILURE. This suite is run
 *     `--user=1` (see the header). `template-parts/reviews/review-form.php`
 *     wraps the name field, the email field AND the privacy sentence in
 *     `if ( ! is_user_logged_in() )`. So under `--user=1` the privacy line was
 *     never rendered at all, and the verbatim pin below was failing on a
 *     sentence that is present and correct for every real reader — a curl of
 *     the public page shows it verbatim. The moderation line sits OUTSIDE that
 *     guard, which is exactly why it passed while its twin failed. It was not
 *     entity encoding, not whitespace collapsing and not the wrong element.
 *
 *     ⭐ The fix is to assert the state a buyer is actually in. Nobody arrives
 *        here from a review-ask email logged in as the shop administrator, so
 *        the current user is dropped to 0 for the render and restored
 *        immediately afterwards — §12's teardown below needs its capabilities.
 * ====================================================================== */

bhp_rs_head( '§10 The review page copy rail' );

if ( ! function_exists( 'bhp_review_render_form' ) ) {
	bhp_rs_ok( 'SKIPPED: bhp_review_render_form() is not loaded', true );
} else {
	$bhp_rs_page_key = bhp_review_ask_first_chapter_book_key( $bhp_rs_v1 );

	$bhp_rs_prev_user = get_current_user_id();
	wp_set_current_user( 0 );
	$bhp_rs_page = bhp_review_render_form( $bhp_rs_page_key, 'standalone' );
	wp_set_current_user( $bhp_rs_prev_user );

	/*
	 * ⛔ THIS ASSERTION MUST BE ABLE TO FAIL. It reads the render for the email
	 *    input that only exists inside the `! is_user_logged_in()` guard, so it
	 *    goes red the moment somebody runs this section logged in again — which
	 *    is the exact 1.19.366 failure. A check on the user id alone would be a
	 *    tautology dressed up as evidence.
	 */
	bhp_rs_ok(
		'⛔ The render really was logged out: the guarded email field is present',
		false !== strpos( (string) $bhp_rs_page, 'name="email"' ),
		'the ! is_user_logged_in() block did not render, so the privacy pin below cannot mean anything'
	);
	bhp_rs_ok(
		'⛔ ...and the admin user was restored for the §12 teardown',
		get_current_user_id() === $bhp_rs_prev_user,
		'expected user ' . $bhp_rs_prev_user . ', got ' . get_current_user_id()
	);

	bhp_rs_ok(
		'⭐ The review page the engine sends every buyer to actually rendered',
		'' !== trim( (string) $bhp_rs_page ),
		'got an empty render for key: ' . $bhp_rs_page_key
	);

	/*
	 * ⛔ ASSERTED ON THE RENDERED HTML, NOT ON THE TEMPLATE SOURCE. A pin
	 *    against the file would pass on a string that never reaches a reader
	 *    and fail on a comment that does not.
	 *
	 * ⛔⛔ 1.19.367 — WHAT THIS ACTUALLY CAUGHT IN 1.19.366, recorded because a
	 *     tag-stripped curl of the same public page reported ZERO em dashes and
	 *     the disagreement looked like a broken test. It was not. The form
	 *     prints `bhp_review_error_messages()` as JSON into
	 *     `<script type="application/json" class="bhp-review-form__messages">`
	 *     for `assets/js/reviews.js`, and two of those strings carried em
	 *     dashes (`email_invalid`, `generic` — `inc/reviews.php`). This
	 *     assertion reads the RAW render and saw them; `wp_strip_all_tags()`
	 *     deletes `<script>` blocks content and all, so the stripped text did
	 *     not. ⭐ The raw check is the correct one: those strings are shown to
	 *     the reviewer by JS, so they are customer-facing copy under Standing
	 *     Rules 608. Both were reworded in 1.19.367.
	 */
	bhp_rs_ok(
		'⛔⛔ No em dash anywhere in the rendered review page',
		false === strpos( (string) $bhp_rs_page, "\xe2\x80\x94" ),
		'an em dash reached the page a review-ask email points at'
	);
	bhp_rs_ok(
		'⛔ No en dash either',
		false === strpos( (string) $bhp_rs_page, "\xe2\x80\x93" )
	);

	/*
	 * ⛔⛔ STANDALONE "we", ON THE VISIBLE TEXT ONLY. Tags are stripped first,
	 *     because class names and attributes are not copy. ⚠ "us" is NOT
	 *     asserted here and that is deliberate, not an omission: the five star
	 *     labels are Andrew's approved wording and two of them end *"not for
	 *     us"* / *"did not work for us"*. §9.1 pins all five verbatim.
	 */
	$bhp_rs_page_text = wp_strip_all_tags( (string) $bhp_rs_page );

	bhp_rs_ok(
		'⛔⛔ No standalone "we" in the rendered review page (Standing Rules 9.1)',
		0 === preg_match( '/\bwe\b/i', $bhp_rs_page_text ),
		'the page still speaks as "we"'
	);

	/*
	 * ⛔⛔ 1.19.368 — THE SAME RULE, ON THE PART OF THE RAW RENDER THE CHECK
	 *     ABOVE IS STRUCTURALLY BLIND TO. CYCLE179-LD-53: the assertion above
	 *     passed all through 1.19.367 while `bhp_review_error_messages()`
	 *     ['author'] read *"Please add your name, so we know who the review is
	 *     from."* — a standalone "we" shown to reviewers by
	 *     `assets/js/reviews.js`. `wp_strip_all_tags()` deletes `<script>`
	 *     blocks CONTENT AND ALL, so the only "we" left on the page was
	 *     invisible to the only test looking for it. Exactly the 1.19.366
	 *     em-dash blind spot, one release later, in the same block.
	 *
	 * ⚠ WHY NOT JUST `preg_match('/\bwe\b/i', $bhp_rs_page)` ON THE RAW HTML,
	 *   which is the obvious reading of "assert the raw render": because `\b`
	 *   treats a hyphen as a word boundary, so any class, data attribute, query
	 *   arg or inline third-party script carrying a `-we-` or `_we_` token
	 *   fails it — on markup nobody reads as copy. That is a flaky test, not a
	 *   stronger one. The raw render is instead read HERE, with tags removed
	 *   but SCRIPT CONTENT KEPT: the JSON messages block is pulled out of
	 *   `$bhp_rs_page` verbatim and scanned. Attributes cannot reach it; the
	 *   strings that actually caught fire twice cannot escape it.
	 */
	$bhp_rs_json_blocks = [];
	if ( preg_match_all( '#<script[^>]*bhp-review-form__messages[^>]*>(.*?)</script>#is', (string) $bhp_rs_page, $bhp_rs_json_m ) ) {
		$bhp_rs_json_blocks = $bhp_rs_json_m[1];
	}

	/*
	 * ⛔ THIS ONE MUST BE ABLE TO FAIL. If the block stops rendering — renamed
	 *    class, template refactor, JS rewritten to fetch the strings instead —
	 *    the scan below would pass on an empty corpus and report a clean page
	 *    it never read. Same failure class as the §10 logged-out pin above.
	 */
	bhp_rs_ok(
		'⛔ The JSON messages block really is in the raw render (so the scan below can mean something)',
		! empty( $bhp_rs_json_blocks ),
		'no <script class="bhp-review-form__messages"> found in the render; the copy scan below would be vacuous'
	);

	$bhp_rs_json_text = html_entity_decode( implode( "\n", $bhp_rs_json_blocks ), ENT_QUOTES, 'UTF-8' );
	bhp_rs_ok(
		'⛔⛔ No standalone "we" in the JSON error-message block of the RAW render (CYCLE179-LD-53)',
		0 === preg_match( '/\bwe\b/i', $bhp_rs_json_text ),
		'a validation message shown to the reviewer still speaks as "we"'
	);
	bhp_rs_ok(
		'⛔ ...and no em or en dash in that block either, read off the raw render rather than the array',
		false === strpos( $bhp_rs_json_text, "\xe2\x80\x94" )
			&& false === strpos( $bhp_rs_json_text, "\xe2\x80\x93" ),
		'a dash reached the JSON messages block'
	);

	/*
	 * ⭐ AND THE REPLACEMENT IS PINNED VERBATIM, so a later edit that removes
	 *    the "we" by deleting the reason instead of rewording it fails here.
	 *    The superseded sentence is pinned by absence beside it.
	 */
	bhp_rs_ok(
		'⭐ The author-name message is the approved 1.19.368 wording, verbatim',
		false !== strpos( $bhp_rs_json_text, 'Please add your name, so the review has a name on it.' ),
		'the approved author-name wording is not in the rendered JSON block'
	);
	bhp_rs_ok(
		'⛔ The superseded "so we know who the review is from" wording is gone from the render',
		false === strpos( $bhp_rs_json_text, 'so we know who the review is from' )
	);

	/*
	 * ⭐ AND THE TWO REPLACEMENT SENTENCES ARE PINNED VERBATIM, so a later
	 *    edit that removes the em dash by deleting the promise instead of
	 *    rewording it fails here.
	 */
	bhp_rs_ok(
		'⭐ The email-privacy line is the approved 1.19.366 wording, verbatim',
		false !== strpos( $bhp_rs_page_text, 'Your email is never published and is never added to any mailing list. It is only for a reply about your review.' )
	);
	bhp_rs_ok(
		'⭐ The moderation line is the approved 1.19.366 wording, verbatim',
		false !== strpos( $bhp_rs_page_text, 'Every review is read before it appears on the site. Nothing is edited: reviews are either published as written or not published.' )
	);
	bhp_rs_ok(
		'⛔ Neither superseded sentence survives on the page',
		false === strpos( $bhp_rs_page_text, 'how we can reach you' )
			&& false === strpos( $bhp_rs_page_text, 'Nothing is edited ' )
	);

	/*
	 * ⛔ THE MODERATION PROMISE IS A CLAIM ABOUT WHAT THE STORE DOES, and it is
	 *    only true because `inc/reviews.php` holds every review. Asserted so
	 *    the sentence cannot outlive the behaviour it describes.
	 */
	if ( function_exists( 'bhp_review_force_moderation' ) ) {
		bhp_rs_ok(
			'⛔⛔ "Every review is read before it appears" is TRUE: the moderation hold is wired',
			has_filter( 'pre_comment_approved', 'bhp_review_force_moderation' ) !== false
		);
	}

	/*
	 * ⭐ 1.19.367 — THE TWO BRANCHES A RENDER CANNOT REACH, asserted at SOURCE
	 *    level and labelled as such so nobody reads them as render evidence.
	 *
	 *      a) The post-submit "thanks" heading (CYCLE179-LD-51) needs a real
	 *         POST and a `$submitted` flag.
	 *      b) The rating summary in `review-section.php` renders only when
	 *         $count > 0 && $average > 0. Staging holds every review for
	 *         moderation by construction, so the approved count is 0 and that
	 *         line is unreachable on staging at all. It carried an em dash from
	 *         1.19.162 until 1.19.367 for exactly that reason.
	 *
	 * ⭐ CLOSED IN 1.19.368 — CYCLE179-LD-53. This slot previously read "RAISED,
	 *   NOT RESOLVED": `bhp_review_error_messages()['author']` said *"so we know
	 *   who the review is from"*, a standalone "we" that Standing Rules 9.1
	 *   forbids, shown to reviewers by `assets/js/reviews.js`, and invisible to
	 *   the stripped-text "we" assertion for the `wp_strip_all_tags()` reason
	 *   above. The string was reworded and the blind spot was closed at BOTH
	 *   ends: a JSON-block scan of the raw render (above) and a direct scan of
	 *   the array itself (below).
	 */
	$bhp_rs_src_files = [
		get_stylesheet_directory() . '/template-parts/reviews/review-section.php',
		get_stylesheet_directory() . '/template-parts/reviews/standalone-review-page.php',
	];
	foreach ( $bhp_rs_src_files as $bhp_rs_src_file ) {
		$bhp_rs_src = file_exists( $bhp_rs_src_file ) ? (string) file_get_contents( $bhp_rs_src_file ) : '';
		bhp_rs_ok(
			'⭐ SOURCE PIN (not a render): ' . basename( $bhp_rs_src_file ) . ' says "Thank you. Your review has been sent."',
			false !== strpos( $bhp_rs_src, 'Thank you. Your review has been sent.' ),
			'file unreadable or the approved 1.19.367 thanks wording is absent'
		);
		/*
		 * ⛔ NOT a blanket em-dash scan of the file: both files are thick with
		 *    docblocks that legitimately use em dashes, and comments are not
		 *    copy. The superseded STRINGS are pinned by absence instead, which
		 *    is what actually catches a restore from an older build.
		 */
		bhp_rs_ok(
			'⛔ SOURCE PIN (not a render): the superseded thanks string is gone from ' . basename( $bhp_rs_src_file ),
			false === strpos( $bhp_rs_src, "esc_html_e('Thank you \xe2\x80\x94" ),
			'the em-dash thanks heading was restored in ' . basename( $bhp_rs_src_file )
		);
	}

	$bhp_rs_section_src = get_stylesheet_directory() . '/template-parts/reviews/review-section.php';
	$bhp_rs_section     = file_exists( $bhp_rs_section_src ) ? (string) file_get_contents( $bhp_rs_section_src ) : '';
	bhp_rs_ok(
		'⛔ SOURCE PIN (not a render): the rating summary line carries no em dash',
		false === strpos( $bhp_rs_section, "out of 5 \xe2\x80\x94 from" )
			&& false !== strpos( $bhp_rs_section, 'out of 5, from %2$s reader review' ),
		'the $count > 0 rating summary is unreachable on staging, so only a source pin can hold it'
	);

	$bhp_rs_msg_dash = false;
	$bhp_rs_msg_we   = [];
	if ( function_exists( 'bhp_review_error_messages' ) ) {
		foreach ( bhp_review_error_messages() as $bhp_rs_msg_key => $bhp_rs_msg ) {
			if ( false !== strpos( (string) $bhp_rs_msg, "\xe2\x80\x94" ) || false !== strpos( (string) $bhp_rs_msg, "\xe2\x80\x93" ) ) {
				$bhp_rs_msg_dash = true;
			}
			if ( preg_match( '/\bwe\b/i', (string) $bhp_rs_msg ) ) {
				$bhp_rs_msg_we[] = $bhp_rs_msg_key;
			}
		}
		bhp_rs_ok(
			'⛔⛔ No em or en dash in any bhp_review_error_messages() string (they are printed as JSON on every page load)',
			false === $bhp_rs_msg_dash
		);
		/*
		 * ⛔⛔ 1.19.368 — THE SAME ARRAY, THE SAME REASON, FOR "we". The render
		 *     scan above is the stronger evidence because it proves what
		 *     reached the page; this one names the offending KEY when it goes
		 *     red, which the render scan cannot. Both are kept: one for proof,
		 *     one for diagnosis.
		 *
		 * ⚠ "us" is deliberately NOT asserted anywhere in §10 — two of Andrew's
		 *   five approved star labels end *"not for us"* / *"did not work for
		 *   us"*. That is the reader's voice, not the store's, and §9.1 pins
		 *   all five verbatim.
		 */
		bhp_rs_ok(
			'⛔⛔ No standalone "we" in any bhp_review_error_messages() string (Standing Rules 9.1)',
			empty( $bhp_rs_msg_we ),
			'these message keys still speak as "we": ' . implode( ', ', $bhp_rs_msg_we )
		);
	}
}

/* =========================================================================
 * ⭐⭐ §11 — ROUND 8. SEAL 998 (the star row) AND SEAL 994 (names, day 0,
 *     the web delay, the retired migration).
 *
 * ⛔ IT RUNS BEFORE THE TEARDOWN because every assertion below needs a live
 *    probe order, exactly as §9 and §10 do.
 * ====================================================================== */

bhp_rs_head( '§11 Round 8: stars, names, day 0, delays, cap' );

/* ---- 11.1 the star row is an image row, ascending, with short alts ---- */

$bhp_rs_r8_row = bhp_review_ask_star_row( $bhp_rs_v1 );

bhp_rs_ok( '⭐ The row still has exactly five stars', 5 === count( $bhp_rs_r8_row ) );
bhp_rs_ok(
	'⭐⭐ Ascending 1..5, left to right (seal 998)',
	array( 1, 2, 3, 4, 5 ) === array_map( 'intval', wp_list_pluck( $bhp_rs_r8_row, 'rating' ) ),
	'got: ' . implode( ',', wp_list_pluck( $bhp_rs_r8_row, 'rating' ) )
);

$bhp_rs_r8_alts = wp_list_pluck( $bhp_rs_r8_row, 'alt' );

bhp_rs_ok(
	'⭐ The alts are "1 star" then "2 stars" .. "5 stars", verbatim',
	array( '1 star', '2 stars', '3 stars', '4 stars', '5 stars' ) === $bhp_rs_r8_alts,
	'got: ' . implode( ' | ', $bhp_rs_r8_alts )
);
bhp_rs_ok(
	'⛔ No alt carries the site form\'s descriptive label',
	false === strpos( implode( ' ', $bhp_rs_r8_alts ), 'loved it' )
		&& false === strpos( implode( ' ', $bhp_rs_r8_alts ), 'not for us' )
);

/*
 * ⛔⛔ THE IMAGE MUST BE SHIPPED, ABSOLUTE AND A PNG. A data: URI is stripped
 *     by Gmail, a relative path resolves against the reader's mail client, and
 *     a missing file silently degrades the whole row to text.
 */
bhp_rs_ok(
	'⭐⭐ The star image resolves to an absolute http(s) URL',
	'' !== bhp_review_ask_star_image_url()
		&& 0 === strpos( bhp_review_ask_star_image_url(), 'http' ),
	'got: ' . bhp_review_ask_star_image_url()
);
bhp_rs_ok(
	'⭐ It is a PNG shipped inside this theme',
	false !== strpos( bhp_review_ask_star_image_url(), '/assets/images/email/review-star-gold@2x.png' )
);
bhp_rs_ok(
	'⛔ The file actually exists on disk (a missing file is a silent downgrade)',
	file_exists( get_template_directory() . '/assets/images/email/review-star-gold@2x.png' )
);
bhp_rs_ok(
	'⛔ It is 2x: 64px square, so it renders crisply at 32px',
	array( 64, 64 ) === array_slice( (array) getimagesize( get_template_directory() . '/assets/images/email/review-star-gold@2x.png' ), 0, 2 )
);

/*
 * ⭐ EVERY STAR CARRIES THE SAME IMAGE. This is the "nothing steers toward
 *    five" rule expressed as an assertion rather than as a comment.
 */
bhp_rs_ok(
	'⭐⭐ All five cells carry the IDENTICAL image (nothing is emphasised)',
	1 === count( array_unique( wp_list_pluck( $bhp_rs_r8_row, 'image' ) ) )
);

/* ---- 11.2 the caption replaced the bolded lead, on all three sets ---- */

foreach (
	array(
		'visit touch 1' => bhp_review_ask_copy_visit_touch1( $bhp_rs_v1 ),
		'web touch 1'   => bhp_review_ask_copy_web_touch1(),
		'touch 2'       => bhp_review_ask_copy_touch2(),
	) as $bhp_rs_r8_name => $bhp_rs_r8_set
) {
	bhp_rs_ok(
		'⭐ ' . $bhp_rs_r8_name . ' carries the round-8 caption, unmerged',
		'Tap a star to rate {BookTitle}. Then two or three honest sentences on the next page.' === $bhp_rs_r8_set['stars_caption']
	);
	bhp_rs_ok( '⛔ ' . $bhp_rs_r8_name . ' has an empty links_lead', '' === $bhp_rs_r8_set['links_lead'] );
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_r8_name . ' no longer links the bare book title',
		'{BookTitle}' !== $bhp_rs_r8_set['links'][0]['label']
			&& 'Or open the review page' === $bhp_rs_r8_set['links'][0]['label'],
		'got: ' . $bhp_rs_r8_set['links'][0]['label']
	);
	bhp_rs_ok(
		'⛔ ' . $bhp_rs_r8_name . ' still passes bhp_review_ask_copy_is_usable() after the change',
		bhp_review_ask_copy_is_usable( $bhp_rs_r8_set )
	);
}

/*
 * ⛔ AND THE CAPTION'S {BookTitle} ACTUALLY RESOLVES ON A REAL ORDER. A caption
 *    reading "Tap a star to rate ." is the exact defect the merge gate exists
 *    for, and the gate only checks slots it can see.
 */
$bhp_rs_r8_merged = bhp_review_ask_copy( 1, $bhp_rs_v1 );

bhp_rs_ok(
	'⭐⭐ The caption merges to a real title on a real order',
	false === strpos( $bhp_rs_r8_merged['stars_caption'], '{' )
		&& false !== strpos( $bhp_rs_r8_merged['stars_caption'], bhp_review_ask_book_title( $bhp_rs_v1 ) ),
	'got: ' . $bhp_rs_r8_merged['stars_caption']
);

/* ---- 11.3 names always, with verb agreement ---- */

$bhp_rs_r8_two = bhp_rs_make_order(
	'rs-r8-two@example.com',
	9,
	array_merge( $bhp_rs_visit_meta, array( '_bhp_school_visit_child_name' => 'Ada, Bo' ) ),
	array( $bhp_rs_pb[0] )
);
$bhp_rs_r8_three = bhp_rs_make_order(
	'rs-r8-three@example.com',
	9,
	array_merge( $bhp_rs_visit_meta, array( '_bhp_school_visit_child_name' => 'Ada, Bo, Cy' ) ),
	array( $bhp_rs_pb[0] )
);
$bhp_rs_r8_one = bhp_rs_make_order(
	'rs-r8-one@example.com',
	9,
	array_merge( $bhp_rs_visit_meta, array( '_bhp_school_visit_child_name' => 'Ada Smith' ) ),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok( '⭐ One child renders "Ada"', 'Ada' === bhp_review_ask_child_first_name( $bhp_rs_r8_one ), 'got: ' . bhp_review_ask_child_first_name( $bhp_rs_r8_one ) );
bhp_rs_ok( '⭐⭐ Two children render "Ada and Bo" (production order 612 shape)', 'Ada and Bo' === bhp_review_ask_child_first_name( $bhp_rs_r8_two ), 'got: ' . bhp_review_ask_child_first_name( $bhp_rs_r8_two ) );
bhp_rs_ok( '⭐ Three children render "Ada, Bo and Cy", with no Oxford comma', 'Ada, Bo and Cy' === bhp_review_ask_child_first_name( $bhp_rs_r8_three ), 'got: ' . bhp_review_ask_child_first_name( $bhp_rs_r8_three ) );
bhp_rs_ok( '⭐ The surname is dropped, the first name is not', 1 === bhp_review_ask_child_count( $bhp_rs_r8_one ) );
bhp_rs_ok( '⛔ An order with no child meta still falls back to "your reader"', 'your reader' === bhp_review_ask_child_first_name( $bhp_rs_v1 ) );

/*
 * ⛔⛔ THE VERB. "Ada and Bo has had" is a broken email, and a parent reading
 *     their own two children's names in a sentence that does not parse notices
 *     immediately.
 */
$bhp_rs_r8_c1 = bhp_review_ask_copy_visit_touch1( $bhp_rs_r8_one );
$bhp_rs_r8_c2 = bhp_review_ask_copy_visit_touch1( $bhp_rs_r8_two );

bhp_rs_ok( '⭐ One child: "{ChildFirstName} has had"', false !== strpos( $bhp_rs_r8_c1['body_before'][0], '{ChildFirstName} has had' ), 'got: ' . $bhp_rs_r8_c1['body_before'][0] );
bhp_rs_ok( '⭐⭐ Two children: "{ChildFirstName} have had"', false !== strpos( $bhp_rs_r8_c2['body_before'][0], '{ChildFirstName} have had' ), 'got: ' . $bhp_rs_r8_c2['body_before'][0] );
bhp_rs_ok( '⭐ One child P.S.: "has a question"', false !== strpos( $bhp_rs_r8_c1['postscript'], 'has a question' ) );
bhp_rs_ok( '⭐⭐ Two children P.S.: "have a question"', false !== strpos( $bhp_rs_r8_c2['postscript'], 'have a question' ), 'got: ' . $bhp_rs_r8_c2['postscript'] );

$bhp_rs_r8_m2 = bhp_review_ask_copy( 1, $bhp_rs_r8_two );

bhp_rs_ok(
	'⭐⭐ Merged, a two-child order reads "Ada and Bo have had ..."',
	false !== strpos( $bhp_rs_r8_m2['body_before'][0], 'Ada and Bo have had' ),
	'got: ' . $bhp_rs_r8_m2['body_before'][0]
);
bhp_rs_ok(
	'⛔ And no merge slot survives into that sentence',
	false === strpos( $bhp_rs_r8_m2['body_before'][0], '{' )
);

/*
 * ⚠ TOUCH 2 CARRIES NO CHILD NAME AT ALL, AND THAT IS ASSERTED RATHER THAN
 *   ASSUMED. Item 3 of the round-8 brief asks for verb agreement in touch 2 as
 *   well; there is no subject in Merry's approved V2 §3 body to agree with, and
 *   no name was invented to create one. This assertion is the record of that.
 */
bhp_rs_ok(
	'⚠ Touch 2 names no child (so its "verb agreement" is a no-op, by design)',
	false === strpos( (string) wp_json_encode( bhp_review_ask_copy_touch2() ), 'ChildFirstName' )
);

/* ---- 11.4 the web lane is 14 days, and the copy moved with it ---- */

bhp_rs_ok( '⭐⭐ The web delay is 14 days (seal 994)', 14 === BHP_REVIEW_ASK_WEB_DELAY_DAYS && 14 === bhp_review_ask_delay_days() );
bhp_rs_ok(
	'⭐ Web touch 1 says "for a couple of weeks now"',
	false !== strpos( bhp_review_ask_copy_web_touch1()['body_before'][0], 'for a couple of weeks now' )
);
bhp_rs_ok(
	'⛔ ... and no longer says "for a week or so now", which is false at 14 days',
	false === strpos( bhp_review_ask_copy_web_touch1()['body_before'][0], 'for a week or so now' )
);
bhp_rs_ok(
	'⛔⛔ The copy/delay interlock agrees: the web set declares 14',
	array( 14 ) === array_map( 'intval', bhp_review_ask_copy_web_touch1()['delay_days'] )
);
bhp_rs_ok(
	'⭐ The VISIT lane is untouched: still 7 one-book and 10 multi-book',
	7 === BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK && 10 === BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK
);
bhp_rs_ok( '⭐ Touch 2 is still 4 days after touch 1', 4 === BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS );

/* ---- 11.5 the cap is 10 and it is per lane ---- */

bhp_rs_ok( '⭐⭐ The default cap is 10', 10 === BHP_REVIEW_ASK_DEFAULT_DAILY_CAP );
bhp_rs_ok( '⭐ Both lanes get their own budget of 10', 10 === bhp_review_ask_daily_cap( 'visit' ) && 10 === bhp_review_ask_daily_cap( 'web' ) );
/*
 * ⚠ ASSERTED AS "COUNTS SOMETHING SANE", NOT AS ZERO. The ledger is a live
 *   option on whatever environment this runs on, and a suite that demands a
 *   zero there is asserting a fact about the site's morning rather than about
 *   the code. What must be true is that the visit and web lanes are counted
 *   SEPARATELY and that neither exceeds the all-lane total.
 */
$bhp_rs_r8_visit_today = bhp_review_ask_sent_today_in_lane( 'visit' );
$bhp_rs_r8_web_today   = bhp_review_ask_sent_today_in_lane( 'web' );

bhp_rs_ok( '⭐ A per-lane count function exists and returns an integer', is_int( $bhp_rs_r8_visit_today ) && is_int( $bhp_rs_r8_web_today ) );
bhp_rs_ok(
	'⭐ Neither lane count exceeds the all-lane count for today',
	$bhp_rs_r8_visit_today <= bhp_review_ask_sent_today() && $bhp_rs_r8_web_today <= bhp_review_ask_sent_today(),
	'visit=' . $bhp_rs_r8_visit_today . ' web=' . $bhp_rs_r8_web_today . ' all=' . bhp_review_ask_sent_today()
);

$bhp_rs_r8_capfilter = function ( $cap, $lane ) {
	return ( 'web' === $lane ) ? 0 : $cap;
};
add_filter( 'bhp_review_ask_daily_cap', $bhp_rs_r8_capfilter, 99, 2 );

bhp_rs_ok(
	'⭐⭐ A lane can be paused to zero without touching the other (0 now means 0)',
	0 === bhp_review_ask_daily_cap( 'web' ) && 10 === bhp_review_ask_daily_cap( 'visit' ),
	'web=' . bhp_review_ask_daily_cap( 'web' ) . ' visit=' . bhp_review_ask_daily_cap( 'visit' )
);

remove_filter( 'bhp_review_ask_daily_cap', $bhp_rs_r8_capfilter, 99 );

bhp_rs_ok( 'The suite removed its cap filter', 10 === bhp_review_ask_daily_cap( 'web' ) );

/*
 * ⭐⭐ AND THE CAP MUST NOT DELAY THE SIXTEEN. The four touch-1 dates carry at
 *    most two groups on any one day (2026-09-14 carries Dallas one-book touch 2
 *    AND Liberty multi touch 1), so the sixteen can only exceed a lane budget
 *    of 10 if more than ten of them land together. ⚠ ASSERTED AS ARITHMETIC ON
 *    THE COUNT, not against the real per-school split, which this desk did not
 *    read off either environment.
 */
bhp_rs_ok(
	'⭐ A visit-lane budget of 10 covers the whole sixteen-order migration in one day if it ever landed together',
	bhp_review_ask_daily_cap( 'visit' ) >= 10
);

/* ---- 11.6 the migration path is retired ---- */

bhp_rs_ok( '⭐ The retired-order list exists', function_exists( 'bhp_review_ask_migration_retired_orders' ) );

$bhp_rs_r8_retired = bhp_review_ask_migration_retired_orders();

bhp_rs_ok( '⭐⭐ It names exactly the sixteen orders from the brief', 16 === count( $bhp_rs_r8_retired ), 'got: ' . count( $bhp_rs_r8_retired ) );
bhp_rs_ok(
	'⭐ Including 612, the order whose child meta was read on production',
	in_array( 612, $bhp_rs_r8_retired, true ) && in_array( 772, $bhp_rs_r8_retired, true )
);

/*
 * ⛔⛔ AND THE COMMAND REFUSES THEM. This is the assertion that stops one
 *     `--apply` cancelling the entire launch: `external-pending-` on any of the
 *     sixteen would suppress touch 1 forever and make touch 2 decline
 *     `touch1_date_unknown`.
 */
$bhp_rs_r8_lines = array();
$bhp_rs_r8_say   = function ( $line ) use ( &$bhp_rs_r8_lines ) {
	$bhp_rs_r8_lines[] = (string) $line;
};

bhp_review_ask_cli_migrate( array( 'orders' => '612:2026-09-10' ), $bhp_rs_r8_say );

$bhp_rs_r8_out = implode( "\n", $bhp_rs_r8_lines );

bhp_rs_ok(
	'⛔⛔ A dry migrate of order 612 REFUSES rather than promising a write',
	false !== strpos( $bhp_rs_r8_out, 'REFUSED order 612' ),
	'got: ' . $bhp_rs_r8_out
);
bhp_rs_ok(
	'⛔ And it wrote nothing: order 612 is not marked by this suite',
	false === strpos( $bhp_rs_r8_out, 'WRITE order 612' )
);

/* ---- 11.7 the school name comes off the order, not only the registry ---- */

$bhp_rs_r8_school = bhp_rs_make_order(
	'rs-r8-school@example.com',
	9,
	array(
		'_bhp_school_visit_slug'   => 'no-such-visit-ever',
		'_bhp_school_visit_school' => 'Probe Elementary',
	),
	array( $bhp_rs_pb[0] )
);

bhp_rs_ok(
	'⭐⭐ {SchoolName} resolves from _bhp_school_visit_school even when the registry has forgotten the visit',
	'Probe Elementary' === bhp_review_ask_school_name( $bhp_rs_r8_school ),
	'got: ' . bhp_review_ask_school_name( $bhp_rs_r8_school )
);
bhp_rs_ok(
	'⛔ An ordinary web order still has no school name',
	'' === bhp_review_ask_school_name( $bhp_rs_w1 )
);

/* =========================================================================
 * §13 — ROUND 9: CHARSET, THE UNICODE STAR ROW, THE HERO BAND AND THE SHELL
 *
 * ⭐ EVERY ASSERTION IN THIS SECTION IS MADE AGAINST A REAL RENDER OF THE REAL
 *    TEMPLATE THROUGH THE REAL `WC_Email` OBJECT, not against the copy arrays
 *    and not against the source of the template. A template that "should"
 *    emit a charset is not a charset.
 *
 * ⛔ NOTHING IS SENT. `prepare_preview()` is the QA seam documented on
 *    `WC_Email_BHP_Review_Ask`: it fills the object for a render and writes no
 *    sent marker, no ledger row and no mail.
 * ====================================================================== */

bhp_rs_head( '§13 Round 9: charset, Unicode stars, hero band, shell' );

/* ---- 13.1 the charset on the Content-Type header ---- */

/*
 * ⛔⛔ THE DEFECT THIS PROVES CLOSED. FluentSMTP logged the outgoing
 *     `Content-Type` as bare `text/html`, and the delivered U+2605 stars
 *     arrived as `âââââ` — UTF-8 bytes decoded as CP1252.
 */
bhp_rs_ok(
	'⭐ bhp_email_force_charset() exists',
	function_exists( 'bhp_email_force_charset' )
);

if ( function_exists( 'bhp_email_force_charset' ) ) {
	$bhp_rs_h_in  = "Content-Type: text/html\r\n";
	$bhp_rs_h_out = bhp_email_force_charset( $bhp_rs_h_in );

	bhp_rs_ok(
		'⭐⭐ A bare text/html header gains charset=UTF-8',
		false !== stripos( $bhp_rs_h_out, 'Content-Type: text/html; charset=UTF-8' ),
		'got: ' . trim( $bhp_rs_h_out )
	);

	/*
	 * ⛔ IT ONLY ADDS. An existing charset is never rewritten, and the media
	 *    type is never changed — rewriting a media type from a header filter
	 *    is how a plain-text email starts arriving as HTML source.
	 */
	bhp_rs_ok(
		'⛔ An existing charset is left alone',
		"Content-Type: text/html; charset=iso-8859-1\r\n" === bhp_email_force_charset( "Content-Type: text/html; charset=iso-8859-1\r\n" )
	);

	bhp_rs_ok(
		'⛔ The media type is never rewritten (multipart stays multipart)',
		false !== stripos( bhp_email_force_charset( "Content-Type: multipart/alternative\r\n" ), 'multipart/alternative; charset=' )
	);

	bhp_rs_ok(
		'⛔ A List-Unsubscribe line is not touched',
		false !== strpos( bhp_email_force_charset( "List-Unsubscribe: <https://x.test/?t=text/html>\r\n" ), 'https://x.test/?t=text/html>' )
	);

	bhp_rs_ok(
		'⛔ The filter is registered on woocommerce_email_headers',
		false !== has_filter( 'woocommerce_email_headers', 'bhp_email_force_charset' )
	);
}

/*
 * ⭐⭐ AND THE HEADER IS READ OFF THE LIVE EMAIL OBJECT, WHICH IS THE ONLY
 *     VERSION OF THIS ASSERTION THAT PROVES ANYTHING. `get_headers()` on the
 *     class runs `parent::get_headers()`, which is where the filter fires.
 */
$bhp_rs_mailer = function_exists( 'WC' ) ? WC()->mailer() : null;
$bhp_rs_email  = null;

if ( $bhp_rs_mailer ) {
	foreach ( (array) $bhp_rs_mailer->get_emails() as $bhp_rs_e ) {
		if ( $bhp_rs_e instanceof WC_Email_BHP_Review_Ask ) {
			$bhp_rs_email = $bhp_rs_e;
			break;
		}
	}
}

bhp_rs_ok( '⭐ The review-ask email object resolves from the mailer', $bhp_rs_email instanceof WC_Email_BHP_Review_Ask );

if ( $bhp_rs_email instanceof WC_Email_BHP_Review_Ask ) {
	$bhp_rs_email->touch = 1;
	$bhp_rs_email->prepare_preview( $bhp_rs_v1 );

	$bhp_rs_hdrs = (string) $bhp_rs_email->get_headers();

	bhp_rs_ok(
		'⭐⭐ THE LIVE EMAIL\'S HEADERS CARRY charset=UTF-8',
		(bool) preg_match( '/content-type\s*:[^\r\n]*charset\s*=\s*"?UTF-8/i', $bhp_rs_hdrs ),
		'got: ' . str_replace( array( "\r", "\n" ), array( '', ' | ' ), $bhp_rs_hdrs )
	);

	/* ---- 13.2 the rendered HTML ---- */

	$bhp_rs_html  = (string) $bhp_rs_email->get_content_html();
	$bhp_rs_plain = (string) $bhp_rs_email->get_content_plain();

	/*
	 * ⛔ THE PLAIN ALTERNATIVE MUST BE VALID UTF-8. `mb_check_encoding` is the
	 *    real question; a byte sequence that is not valid UTF-8 is exactly what
	 *    a mail transport re-encodes into mojibake.
	 */
	bhp_rs_ok(
		'⭐⭐ The plain-text alternative is valid UTF-8',
		! function_exists( 'mb_check_encoding' ) || mb_check_encoding( $bhp_rs_plain, 'UTF-8' )
	);

	bhp_rs_ok(
		'⭐ The HTML body is valid UTF-8',
		! function_exists( 'mb_check_encoding' ) || mb_check_encoding( $bhp_rs_html, 'UTF-8' )
	);

	/* ---- 13.3 the Unicode star row ---- */

	bhp_rs_ok(
		'⭐⭐ The rendered row carries five U+2605 glyphs',
		5 === substr_count( $bhp_rs_html, "\xE2\x98\x85" ),
		'got: ' . substr_count( $bhp_rs_html, "\xE2\x98\x85" )
	);

	/*
	 * ⛔⛔ ROUND 8's PNG IS GONE FROM THE EMAIL. Legolas rendered that row with
	 *     images blocked and got five empty grey boxes.
	 */
	bhp_rs_ok(
		'⛔⛔ No star PNG is referenced in the rendered email any more',
		false === strpos( $bhp_rs_html, 'review-star-gold' )
	);

	bhp_rs_ok(
		'⭐ Each star cell carries the bhp-star class',
		5 === substr_count( $bhp_rs_html, 'bhp-star' ) || substr_count( $bhp_rs_html, 'bhp-star' ) >= 5,
		'got: ' . substr_count( $bhp_rs_html, 'bhp-star' )
	);

	/*
	 * ⭐ THE ARIA LABELS, ALL FIVE, IN ASCENDING ORDER. The order is proved by
	 *    comparing the positions of the five labels in the emitted string, not
	 *    by trusting the loop that wrote them.
	 */
	$bhp_rs_positions = array();

	foreach ( array( '1 star', '2 stars', '3 stars', '4 stars', '5 stars' ) as $bhp_rs_label ) {
		$bhp_rs_positions[ $bhp_rs_label ] = strpos( $bhp_rs_html, 'aria-label="' . $bhp_rs_label . '"' );
	}

	bhp_rs_ok(
		'⭐ All five aria-labels are present, "1 star" through "5 stars"',
		! in_array( false, $bhp_rs_positions, true )
	);

	bhp_rs_ok(
		'⭐⭐ THE LABELS APPEAR IN ASCENDING ORDER IN THE EMITTED HTML (seal 998)',
		! in_array( false, $bhp_rs_positions, true )
			&& array_values( $bhp_rs_positions ) === array_values( array_filter( $bhp_rs_positions, 'is_int' ) )
			&& $bhp_rs_positions['1 star'] < $bhp_rs_positions['2 stars']
			&& $bhp_rs_positions['2 stars'] < $bhp_rs_positions['3 stars']
			&& $bhp_rs_positions['3 stars'] < $bhp_rs_positions['4 stars']
			&& $bhp_rs_positions['4 stars'] < $bhp_rs_positions['5 stars'],
		'positions: ' . wp_json_encode( $bhp_rs_positions )
	);

	/*
	 * ⭐ THE RESTING GREY IS INLINE, so a webmail that strips <style> still
	 *    gets the intended resting state and loses only the hover.
	 */
	bhp_rs_ok(
		'⭐ The resting grey #c9c2b3 is inline on every star link',
		5 === substr_count( $bhp_rs_html, '#c9c2b3' ),
		'got: ' . substr_count( $bhp_rs_html, '#c9c2b3' )
	);

	/*
	 * ⛔⛔ 32px AND A 44px TAP TARGET, ASSERTED ON THE EMITTED STYLE STRING.
	 *     ⚠ THIS IS ARITHMETIC ON DECLARED SIZES, NOT A MEASUREMENT IN A MAIL
	 *     CLIENT. No client was opened in this build.
	 */
	bhp_rs_ok(
		'⭐ Stars render at 32px with 6px padding (a 44px tap target)',
		false !== strpos( $bhp_rs_html, 'font-size:32px;line-height:32px' ) && false !== strpos( $bhp_rs_html, 'padding:6px;' )
	);

	bhp_rs_ok(
		'⭐ The caption line still renders under the row',
		false !== strpos( $bhp_rs_html, 'Tap a star to rate' )
	);

	bhp_rs_ok(
		'⭐ The "Or open the review page" link is still there',
		false !== stripos( $bhp_rs_html, 'Or open the review page' )
	);

	/* ---- 13.4 the plain-text five lines ---- */

	$bhp_rs_plain_lines = 0;

	foreach ( array( '1 star:', '2 stars:', '3 stars:', '4 stars:', '5 stars:' ) as $bhp_rs_pl ) {
		if ( false !== strpos( $bhp_rs_plain, $bhp_rs_pl ) ) {
			$bhp_rs_plain_lines++;
		}
	}

	bhp_rs_ok( '⭐ The plain twin carries five rating lines', 5 === $bhp_rs_plain_lines, 'got: ' . $bhp_rs_plain_lines );

	/* ---- 13.5 the hero band ---- */

	bhp_rs_ok(
		'⭐⭐ Touch 1 renders a hero image from the theme',
		false !== strpos( $bhp_rs_html, '/assets/images/email/hero-' ),
		'no hero in the rendered touch 1'
	);

	/*
	 * ⛔ A VISIT ORDER WITH NO MAPPED SLUG GETS THE GENERAL FRAME, NOT THE
	 *    DALLAS HARRIS ONE. A photograph captioned for a school the family
	 *    never attended is a false statement in a picture.
	 */
	bhp_rs_ok(
		'⛔ An unmapped visit slug falls back to the general hero',
		false !== strpos( $bhp_rs_html, 'hero-read-aloud-general.jpg' ),
		'the fixture slug is not in the map, so this must be the general frame'
	);

	bhp_rs_ok(
		'⭐ The hero carries descriptive alt text including its baked caption',
		false !== strpos( $bhp_rs_html, 'Caption: A morning read-aloud' )
	);

	bhp_rs_ok(
		'⭐ The hero is width-capped at 536px inside the card',
		false !== strpos( $bhp_rs_html, 'max-width:536px' ) || false !== strpos( $bhp_rs_html, 'max-width: 536px' )
	);

	/*
	 * ⛔⛔ TOUCH 2 HAS NO HERO. Legolas §7: the short last note must not look
	 *     like a bigger ask than it is.
	 */
	$bhp_rs_email->touch = 2;
	$bhp_rs_html2        = (string) $bhp_rs_email->get_content_html();

	bhp_rs_ok(
		'⛔⛔ TOUCH 2 RENDERS NO HERO IMAGE',
		false === strpos( $bhp_rs_html2, '/assets/images/email/hero-' )
	);

	bhp_rs_ok(
		'⭐ ... but touch 2 still renders its five stars',
		5 === substr_count( $bhp_rs_html2, "\xE2\x98\x85" ),
		'got: ' . substr_count( $bhp_rs_html2, "\xE2\x98\x85" )
	);

	$bhp_rs_email->touch = 1;

	/* ---- 13.6 the shell and the signature block ---- */

	bhp_rs_ok(
		'⭐ The signature block renders the name, the role and the brand line',
		false !== strpos( $bhp_rs_html, 'Andrew Signore' )
			&& false !== strpos( $bhp_rs_html, 'Author | Brave Hearts Publishing' )
			&& false !== strpos( $bhp_rs_html, 'Big Places. Brave Hearts.' )
	);

	/*
	 * ⛔⛔ AND NO SOCIAL LINK IS INVENTED. No Facebook or Instagram URL exists
	 *     anywhere in this repository, so the line must render EMPTY until real
	 *     URLs are supplied. This assertion is the guard against a future
	 *     "helpful" guess.
	 */
	bhp_rs_ok(
		'⛔⛔ NO fabricated Facebook or Instagram URL is emitted',
		false === stripos( $bhp_rs_html, 'facebook.com' ) && false === stripos( $bhp_rs_html, 'instagram.com' )
	);

	/*
	 * ⭐ AND THE LINE APPEARS THE MOMENT REAL URLs EXIST. Proved by supplying
	 *    two through the public filter rather than by reading the code.
	 */
	$bhp_rs_social_shim = static function () {
		return array(
			array(
				'label' => 'Facebook',
				'url'   => 'https://example.test/fb',
			),
			array(
				'label' => 'Instagram',
				'url'   => 'https://example.test/ig',
			),
			array(
				'label' => 'Dead',
				'url'   => '',
			),
		);
	};

	add_filter( 'bhp_review_ask_social_links', $bhp_rs_social_shim, 99 );
	$bhp_rs_sig = bhp_review_ask_signature();
	remove_filter( 'bhp_review_ask_social_links', $bhp_rs_social_shim, 99 );

	bhp_rs_ok( '⭐ Two supplied social links are accepted', 2 === count( $bhp_rs_sig['social'] ), 'got: ' . count( $bhp_rs_sig['social'] ) );
	bhp_rs_ok( '⛔ An entry with an empty URL is dropped, not rendered dead', 'Dead' !== $bhp_rs_sig['social'][ count( $bhp_rs_sig['social'] ) - 1 ]['label'] );

	bhp_rs_ok(
		'⭐ The opt-out link and the postal address still render',
		false !== strpos( $bhp_rs_html, 'bhp_review_optout' ) || false !== strpos( $bhp_rs_html, $bhp_rs_email->optout_url )
	);

	/* ---- 13.7 the byte budget ---- */

	/*
	 * ⛔⛔ 60 KB, MEASURED ON THE FULL RENDERED MESSAGE, NOT ON THE BODY
	 *     FRAGMENT. `get_content()` is what the mailer is handed: doctype,
	 *     inlined CSS, header, body, footer. ⭐ THE REASON IT MATTERS IS GMAIL:
	 *     it clips a message above roughly 102 KB, and the clipped tail is
	 *     exactly where the unsubscribe link and the postal address live.
	 */
	$bhp_rs_full  = (string) $bhp_rs_email->get_content();
	$bhp_rs_bytes = strlen( $bhp_rs_full );

	bhp_rs_ok(
		'⭐⭐ A rendered touch 1 is under 60 KB (' . number_format( $bhp_rs_bytes ) . ' bytes)',
		$bhp_rs_bytes > 0 && $bhp_rs_bytes < 61440,
		'got: ' . $bhp_rs_bytes . ' bytes'
	);

	/*
	 * ⚠ A ZERO-BYTE RENDER WOULD PASS A NAIVE "under 60 KB" CHECK, so the
	 *   floor is asserted too. An email that renders to nothing is not a small
	 *   email.
	 */
	bhp_rs_ok( '⛔ ... and it is not empty', $bhp_rs_bytes > 3000, 'got: ' . $bhp_rs_bytes . ' bytes' );

	/* ---- 13.8 the hover CSS reaches the stylesheet ---- */

	bhp_rs_ok(
		'⭐ bhp_review_ask_star_css() emits a :hover rule and the gold',
		function_exists( 'bhp_review_ask_star_css' )
			&& false !== strpos( bhp_review_ask_star_css(), ':hover' )
			&& false !== strpos( bhp_review_ask_star_css(), '#c4a15c' )
	);

	/*
	 * ⭐⭐ AND IT IS IN THE ASSEMBLED EMAIL STYLESHEET, not merely in a function
	 *     nobody calls. This is the assertion that proves the wiring.
	 */
	$bhp_rs_css = (string) apply_filters( 'woocommerce_email_styles', '', $bhp_rs_email );

	bhp_rs_ok(
		'⭐⭐ The star hover rules are present in the assembled email CSS',
		false !== strpos( $bhp_rs_css, '.bhp-star' ) && false !== strpos( $bhp_rs_css, ':hover' ),
		'assembled CSS length: ' . strlen( $bhp_rs_css )
	);

	bhp_rs_ok(
		'⭐ The cumulative :has() fill rule is present',
		false !== strpos( $bhp_rs_css, ':has(~ .bhp-star:hover)' )
	);

	bhp_rs_ok(
		'⭐ The H1 is set to 30px per the design spec',
		false !== strpos( $bhp_rs_css, 'font-size: 30px' )
	);
}

/* ---- 13.9 the hero mapping, without a render ---- */

bhp_rs_ok( '⭐ bhp_review_ask_hero() exists', function_exists( 'bhp_review_ask_hero' ) );

if ( function_exists( 'bhp_review_ask_hero' ) ) {
	bhp_rs_ok( '⛔⛔ Touch 2 never resolves a hero, for any order', array() === bhp_review_ask_hero( $bhp_rs_v1, 'touch2' ) );

	/*
	 * ⭐ THE MAPPED SLUG RESOLVES THE TWO DALLAS HARRIS FRAMES THE SPEC
	 *    ASSIGNS: frame 02 (the book being read) for touch 1, frame 01 (the
	 *    whole room) for day 0.
	 */
	$bhp_rs_visit_hero = bhp_rs_make_order(
		'rs-hero@example.com',
		9,
		array( '_bhp_school_visit_slug' => 'dallas-harris-2026-09-03' ),
		array( $bhp_rs_pb[0] )
	);

	$bhp_rs_h_t1 = bhp_review_ask_hero( $bhp_rs_visit_hero, 'touch1' );
	$bhp_rs_h_d0 = bhp_review_ask_hero( $bhp_rs_visit_hero, 'day0' );

	bhp_rs_ok(
		'⭐⭐ The dallas-harris slug maps to frame 02 on touch 1',
		! empty( $bhp_rs_h_t1['url'] ) && false !== strpos( $bhp_rs_h_t1['url'], 'hero-dallas-harris-2026-09-03-02.jpg' ),
		'got: ' . ( isset( $bhp_rs_h_t1['url'] ) ? $bhp_rs_h_t1['url'] : '(empty)' )
	);

	bhp_rs_ok(
		'⭐⭐ ... and to frame 01 on day 0',
		! empty( $bhp_rs_h_d0['url'] ) && false !== strpos( $bhp_rs_h_d0['url'], 'hero-dallas-harris-2026-09-03-01.jpg' ),
		'got: ' . ( isset( $bhp_rs_h_d0['url'] ) ? $bhp_rs_h_d0['url'] : '(empty)' )
	);

	bhp_rs_ok(
		'⭐ The mapped hero carries its own alt text, naming the school and the date',
		! empty( $bhp_rs_h_t1['alt'] ) && false !== strpos( $bhp_rs_h_t1['alt'], 'Dallas Harris Elementary, September 3, 2026' )
	);

	/*
	 * ⛔⛔ FRAME 05 IS NOT SHIPPED AND MUST NOT BE. It shows a second adult
	 *     whose consent is not on record and a legible visitor badge.
	 *     `CYCLE179-DES-29(b)`.
	 */
	bhp_rs_ok(
		'⛔⛔ Frame 05 of the Dallas Harris set is NOT in the theme',
		! file_exists( get_template_directory() . '/assets/images/email/hero-dallas-harris-2026-09-03-05.jpg' )
			&& ! file_exists( get_template_directory() . '/assets/images/email/read-aloud-dallas-harris-2026-09-03-05.jpg' )
	);

	/*
	 * ⛔ A FILTER NAMING A FILE THAT WAS NEVER DEPLOYED RENDERS NOTHING, not a
	 *    broken-image icon at the top of the email.
	 */
	$bhp_rs_bad_map = static function () {
		return array( 'general' => array( 'touch1' => 'hero-does-not-exist.jpg' ) );
	};

	add_filter( 'bhp_review_ask_hero_map', $bhp_rs_bad_map, 99 );
	$bhp_rs_missing = bhp_review_ask_hero( $bhp_rs_w1, 'touch1' );
	remove_filter( 'bhp_review_ask_hero_map', $bhp_rs_bad_map, 99 );

	bhp_rs_ok( '⛔ A hero file that is not on disk resolves to nothing', array() === $bhp_rs_missing );

	/* And the three shipped files are actually present. */
	foreach ( array( 'hero-dallas-harris-2026-09-03-01.jpg', 'hero-dallas-harris-2026-09-03-02.jpg', 'hero-read-aloud-general.jpg' ) as $bhp_rs_hf ) {
		bhp_rs_ok(
			'⭐ Shipped in the theme: ' . $bhp_rs_hf,
			file_exists( get_template_directory() . '/assets/images/email/' . $bhp_rs_hf )
		);
	}
}

/* =========================================================================
 * §12 — DEFERRED FIXTURE TEARDOWN
 *
 * ⭐ MOVED HERE FROM §8 IN 1.19.366. §9 and §10 both need a LIVE probe order:
 *    §9.7 looks the name box up through `wc_get_order()`, and §10 resolves the
 *    review page from the order's first chapter book. Deleting the fixtures
 *    before those sections ran was the whole of the "(empty)" name box.
 *
 * ⛔ NOTHING IS LEFT BEHIND. Force-delete, same call, same assertions.
 * ====================================================================== */

bhp_rs_head( '§12 Deferred fixture teardown' );

$bhp_rs_deleted = 0;
foreach ( $GLOBALS['bhp_rs_orders'] as $bhp_rs_id ) {
	$bhp_rs_o = wc_get_order( $bhp_rs_id );
	if ( $bhp_rs_o ) {
		$bhp_rs_o->delete( true );
		$bhp_rs_deleted++;
	}
}
bhp_rs_ok( 'Every probe order was force-deleted (' . $bhp_rs_deleted . ' of ' . count( $GLOBALS['bhp_rs_orders'] ) . ')', $bhp_rs_deleted === count( $GLOBALS['bhp_rs_orders'] ) );

$bhp_rs_c_deleted = 0;
foreach ( $GLOBALS['bhp_rs_comments'] as $bhp_rs_cid ) {
	if ( wp_delete_comment( $bhp_rs_cid, true ) ) {
		$bhp_rs_c_deleted++;
	}
}
bhp_rs_ok( 'Every probe review was force-deleted (' . $bhp_rs_c_deleted . ' of ' . count( $GLOBALS['bhp_rs_comments'] ) . ')', $bhp_rs_c_deleted === count( $GLOBALS['bhp_rs_comments'] ) );

/*
 * ⛔ AND THE DELETION IS VERIFIED RATHER THAN COUNTED. A count proves the loop
 *    ran; this proves the rows are gone.
 */
$bhp_rs_survivors = 0;
foreach ( $GLOBALS['bhp_rs_orders'] as $bhp_rs_id ) {
	if ( wc_get_order( $bhp_rs_id ) ) {
		$bhp_rs_survivors++;
	}
}
bhp_rs_ok( '⛔ No probe order survives the run', 0 === $bhp_rs_survivors, 'survivors: ' . $bhp_rs_survivors );

/*
 * ⛔ THE MASTER SWITCH IS ASSERTED, NOT ASSUMED. This suite never turned it on,
 *    and it must still be off when the suite ends. A run that leaves a live
 *    emailer behind it is the one outcome worse than a failing assertion.
 */
bhp_rs_ok(
	'⛔ The engine master switch is still OFF after the run',
	'yes' !== get_option( 'bhp_review_ask_enabled', 'no' )
);

echo "\n========================================\n";
echo "PASS: {$GLOBALS['bhp_rs_pass']}   FAIL: {$GLOBALS['bhp_rs_fail']}\n";
echo "========================================\n";

if ( $GLOBALS['bhp_rs_fail'] > 0 ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::error( $GLOBALS['bhp_rs_fail'] . ' assertion(s) failed.' );
	}
	exit( 1 );
}
