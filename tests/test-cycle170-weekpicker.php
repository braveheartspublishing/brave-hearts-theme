<?php
/**
 * CYCLE170-LD-WEEKPICKER — the week picker replaces the day grid.
 * Theme 1.19.335 (2026-08-30). STAGING ONLY.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Four things in this pass can go wrong
 *    QUIETLY, and quiet is the operative word in all four.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  · **A week picker can widen what is askable without anybody noticing.** The
 *    whole safety argument of this release is that a week is a GROUPING of days
 *    `build_dates()` already offered — so it cannot admit a day 1.19.334
 *    refused. §2 asserts that directly: every day inside every offered week is
 *    a day the untouched day list also contains, and no offered week contains
 *    a September day, a weekend, or a day inside the lead window.
 *
 *  · **The server gate can be left behind when the control moves.** The day
 *    grid's gate was `date_is_offered()`. If the form now posts `visit_week`
 *    and the handler still validated a date, every posted week would be
 *    refused, or worse, waved through. §3 asserts the new gate exists, refuses
 *    the four shapes that matter (September, beyond the horizon, empty,
 *    injection-shaped), and ACCEPTS a week the form actually rendered.
 *
 *  · **The honest line can be paraphrased into near-correctness.** Item 534 is
 *    a founder statement about his own working life. A build that "tidied" a
 *    comma would be putting words in his mouth. §4 asserts it character by
 *    character, including both typographic apostrophes and the absence of the
 *    straight form.
 *
 *  · **The old field name can survive as a silent fallback.** A handler that
 *    still honoured `visit_date` would let a cached 1.19.334 page post a single
 *    DAY into a week-level flow, and he would be asked for a day he never
 *    agreed to be asked for. §5 asserts the rendered form no longer emits it
 *    and the handler source no longer reads it.
 *
 * ⛔ RUN: `wp eval-file tests/test-cycle170-weekpicker.php` on STAGING.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assert.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Message.
 */
function bhp_wp_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_wp_pass'] ) ) {
		$GLOBALS['bhp_wp_pass'] = 0;
		$GLOBALS['bhp_wp_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_wp_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_wp_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * GET a path on this site.
 *
 * @param string $path Path.
 * @return array{code:int,body:string}
 */
function bhp_wp_fetch( $path ) {
	$r = wp_remote_get(
		home_url( $path ),
		array(
			'timeout'   => 45,
			'sslverify' => false,
		)
	);
	return array(
		'code' => (int) wp_remote_retrieve_response_code( $r ),
		'body' => (string) wp_remote_retrieve_body( $r ),
	);
}

echo "\n=== CYCLE170-LD-WEEKPICKER · theme 1.19.335 ===\n";

echo "\n=== 0 · VERSION AND SHAPE ===\n";

$bhp_wp_style = (string) file_get_contents( get_template_directory() . '/style.css' );
/*
 * ⛔ PIN MOVED TO 1.19.336 AT `CYCLE170-LD-CHAIN`. The suite travels with the
 *    release it is shipped in, so a suite run against an older theme says so
 *    loudly instead of passing for the wrong reason.
 *
 * ⛔ SUPERSEDED, quoted rather than deleted — this suite SHIPPED at 1.19.335 and
 *    that history is not rewritten:
 *
 *      1 === preg_match( '/^Version:\s*1\.19\.335\s*$/m', $bhp_wp_style ),
 *      'style.css declares Version: 1.19.335'
 */
/*
 * ⭐ MOVED AGAIN TO 1.19.337 BY `CYCLE170-LD-MICRO` (2026-08-30). SUPERSEDED
 *    ASSERTION, PRESERVED VERBATIM — the 1.19.336 pin was the CHAIN lane's and
 *    that history is not rewritten:
 *
 *      1 === preg_match( '/^Version:\s*1\.19\.336\s*$/m', $bhp_wp_style ),
 *      'style.css declares Version: 1.19.336'
 */
/*
 * ⭐ PIN MOVED TO 1.19.339 BY `CYCLE170-LD-FINAL2` (2026-08-30). This lane
 *    bumped the theme, so this lane owns the pin it broke — the same discipline
 *    `CYCLE170-LD-CHAIN` §6a set. The four stale pins belonging to OTHER lanes
 *    (ship-prep, triple, school-readaloud, cycle169-funnel) are STILL deliberately
 *    left alone; moving them would silently adopt somebody else's stale suite.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM rather than corrected in place:
 *
 *      bhp_wp_assert(
 *      	1 === preg_match( '/^Version:\s*1\.19\.337\s*$/m', $bhp_wp_style ),
 *      	'style.css declares Version: 1.19.337'
 *      );
 */
bhp_wp_assert(
	1 === preg_match( '/^Version:\s*1\.19\.339\s*$/m', $bhp_wp_style ),
	'style.css declares Version: 1.19.339'
);

foreach ( array(
	'bhp_readaloud_scheduler_week_count',
	'bhp_readaloud_scheduler_week_horizon_days',
	'bhp_readaloud_scheduler_week_start',
	'bhp_readaloud_scheduler_build_weeks',
	'bhp_readaloud_scheduler_weeks',
	'bhp_readaloud_scheduler_week_is_offered',
	'bhp_readaloud_scheduler_week_by_value',
	'bhp_readaloud_scheduler_honest_line',
	'bhp_readaloud_scheduler_weekdays',
) as $bhp_wp_fn ) {
	bhp_wp_assert( function_exists( $bhp_wp_fn ), "{$bhp_wp_fn}() exists" );
}

/*
 * ⛔⛔ THE DAY LAYER IS NOT DELETED, AND THIS IS THE FIRST THING THE SUITE
 *     CHECKS AFTER THE VERSION. Every safety property of this release is
 *     inherited from `build_dates()`. If a later pass "tidies" the day layer
 *     away because the page no longer draws a day grid, the week list loses its
 *     floor, its lead and its weekend rule all at once and nothing else in the
 *     theme would notice.
 */
foreach ( array(
	'bhp_readaloud_scheduler_build_dates',
	'bhp_readaloud_scheduler_dates',
	'bhp_readaloud_scheduler_date_is_offered',
	'bhp_readaloud_scheduler_floor_date',
	'bhp_readaloud_scheduler_build_months',
) as $bhp_wp_kept ) {
	bhp_wp_assert( function_exists( $bhp_wp_kept ), "⛔ the day layer is KEPT: {$bhp_wp_kept}()" );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE WEEK MODEL — PURE ARITHMETIC, FIXED "TODAY"
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ EVERY ASSERTION BELOW USES A FIXED DATE, so it measures the same thing on
 *    every day this suite is ever run. A boundary test against "now" passes for
 *    the wrong reason on most days of the year.
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 1 · THE WEEK MODEL (item 534) ===\n";

$bhp_wp_today = '2026-08-30';
$bhp_wp_weeks = bhp_readaloud_scheduler_build_weeks( $bhp_wp_today, 7, 112, '2026-10-01', 12 );

bhp_wp_assert( 12 === count( $bhp_wp_weeks ), '⭐ exactly 12 week cards at the shipped defaults (the brief asked for 10-12)' );

/*
 * ⛔⛔ THE TWO KEYS, AND THE DISTINCTION THAT WAS CORRECTED DURING THIS BUILD.
 *     `start` is the Monday and is NEVER rendered into an input. `value` is the
 *     week's first OFFERABLE day and is what the radio carries and what the gate
 *     checks. Posting the Monday would have put `value="2026-09-28"` on a page
 *     that says "October onward" everywhere, and would have broken
 *     `test-cycle170-mvp.php`'s standing assertion that ZERO `value="2026-09-`
 *     strings reach the rendered page. Both keys are asserted here so a later
 *     pass cannot quietly swap which one the form posts.
 */
bhp_wp_assert(
	'2026-09-28' === $bhp_wp_weeks[0]['start'],
	'the first card GROUPING key is the Monday 2026-09-28 (internal, never posted)'
);
bhp_wp_assert(
	'2026-10-01' === $bhp_wp_weeks[0]['value'],
	'⛔⛔ the first card POSTED value is 2026-10-01, its first offerable day - NOT the September Monday'
);
$bhp_wp_sept_vals_pure = 0;
foreach ( $bhp_wp_weeks as $bhp_wp_w ) {
	if ( 0 === strpos( $bhp_wp_w['value'], '2026-09' ) ) {
		++$bhp_wp_sept_vals_pure;
	}
}
bhp_wp_assert( 0 === $bhp_wp_sept_vals_pure, '⛔⛔ NOT ONE card posts a September value' );
bhp_wp_assert(
	'2026-10-05' === $bhp_wp_weeks[1]['value'] && $bhp_wp_weeks[1]['value'] === $bhp_wp_weeks[1]['start'],
	'for a FULL week the posted value and the Monday are the same string'
);
bhp_wp_assert(
	'Week of October 1' === $bhp_wp_weeks[0]['label'],
	'⭐⭐ the first card READS "Week of October 1" - its first OFFERABLE day, never "September 28"'
);
bhp_wp_assert(
	'Week of October 5' === $bhp_wp_weeks[1]['label'],
	'⭐ the second card reads "Week of October 5" - the brief\'s own example, exactly'
);
bhp_wp_assert(
	'2026-10-01' === $bhp_wp_weeks[0]['first'] && '2026-10-02' === $bhp_wp_weeks[0]['last'],
	'the first card is the PARTIAL week the floor opens: 2026-10-01 to 2026-10-02'
);
bhp_wp_assert(
	2 === $bhp_wp_weeks[0]['count'],
	'the partial first week reports 2 school days, not 5'
);

/*
 * ⛔⛔ NO CARD MAY PRINT THE WORD SEPTEMBER, IN ITS LABEL OR IN ITS RANGE.
 *     The hero lead and the item-522 chip both say "October onward". The
 *     1.19.334 floor made the day grid agree with them; this asserts the WEEK
 *     cards agree too. A card reading "Week of September 28" would re-open the
 *     exact honesty defect the floor closed.
 */
$bhp_wp_sept = 0;
foreach ( $bhp_wp_weeks as $bhp_wp_w ) {
	if ( false !== strpos( $bhp_wp_w['label'], 'September' ) ) {
		++$bhp_wp_sept;
	}
	if ( false !== strpos( $bhp_wp_w['range'], 'September' ) ) {
		++$bhp_wp_sept;
	}
}
bhp_wp_assert( 0 === $bhp_wp_sept, '⛔⛔ NO card label or range contains the word "September"' );

$bhp_wp_five = 0;
foreach ( array_slice( $bhp_wp_weeks, 1 ) as $bhp_wp_w ) {
	if ( 5 === $bhp_wp_w['count'] ) {
		++$bhp_wp_five;
	}
}
bhp_wp_assert( 11 === $bhp_wp_five, 'every card after the partial first one holds 5 school days' );

bhp_wp_assert(
	'2026-12-14' === $bhp_wp_weeks[11]['start'],
	'the twelfth card is the week of 2026-12-14 (the cap binds, not the 90-day day-horizon)'
);

/* Malformed and degenerate inputs. */
bhp_wp_assert( array() === bhp_readaloud_scheduler_build_weeks( 'not-a-date', 7, 112 ), 'a malformed "today" yields no weeks, not a warning' );
bhp_wp_assert( array() === bhp_readaloud_scheduler_build_weeks( $bhp_wp_today, 120, 30 ), 'lead beyond horizon yields no weeks (the honest empty state)' );
bhp_wp_assert( '' === bhp_readaloud_scheduler_week_start( 'nonsense' ), 'week_start() refuses a malformed date' );
bhp_wp_assert( '2026-10-05' === bhp_readaloud_scheduler_week_start( '2026-10-05' ), 'week_start() of a Monday is that Monday' );
bhp_wp_assert( '2026-10-05' === bhp_readaloud_scheduler_week_start( '2026-10-09' ), 'week_start() of a Friday is that week\'s Monday' );
bhp_wp_assert( '2026-10-05' === bhp_readaloud_scheduler_week_start( '2026-10-11' ), '⭐ week_start() of a SUNDAY is the Monday BEFORE it (a school week, not a US calendar week)' );

/* The cap. */
bhp_wp_assert( 3 === count( bhp_readaloud_scheduler_build_weeks( $bhp_wp_today, 7, 112, '2026-10-01', 3 ) ), 'the cap truncates the list' );
bhp_wp_assert( count( bhp_readaloud_scheduler_build_weeks( $bhp_wp_today, 7, 112, '2026-10-01', 0 ) ) >= 12, 'cap 0 means no cap' );

/*
 * ⭐⭐ THE FLOOR STILL EXPIRES BY ITSELF, AND THE WEEK LIST INHERITS THAT.
 *     This is the 1.19.334 property, re-asserted at the week layer: with a
 *     "today" past the floor, the floored and unfloored week lists are
 *     IDENTICAL. Nobody has to remember to remove anything.
 */
bhp_wp_assert(
	bhp_readaloud_scheduler_build_weeks( '2026-11-10', 7, 112, '2026-10-01', 12 )
		=== bhp_readaloud_scheduler_build_weeks( '2026-11-10', 7, 112, '', 12 ),
	'⭐⭐ the calendar floor SELF-EXPIRES: at today=2026-11-10 floored and unfloored week lists are identical'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · A WEEK GRANTS NO PERMISSION A DAY DID NOT ALREADY HAVE
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 2 · NO WIDENING (the safety property) ===\n";

$bhp_wp_days = bhp_readaloud_scheduler_build_dates( $bhp_wp_today, 7, 112, '2026-10-01' );
$bhp_wp_set  = array();
foreach ( $bhp_wp_days as $bhp_wp_row ) {
	$bhp_wp_set[ $bhp_wp_row['ymd'] ] = true;
}

$bhp_wp_stray   = 0;
$bhp_wp_weekend = 0;
$bhp_wp_before  = 0;
$bhp_wp_total   = 0;
foreach ( $bhp_wp_weeks as $bhp_wp_w ) {
	foreach ( $bhp_wp_w['days'] as $bhp_wp_d ) {
		++$bhp_wp_total;
		if ( ! isset( $bhp_wp_set[ $bhp_wp_d ] ) ) {
			++$bhp_wp_stray;
		}
		if ( ! bhp_readaloud_scheduler_is_school_day( $bhp_wp_d ) ) {
			++$bhp_wp_weekend;
		}
		if ( $bhp_wp_d < '2026-10-01' ) {
			++$bhp_wp_before;
		}
	}
}

bhp_wp_assert( $bhp_wp_total > 50, "the twelve cards carry {$bhp_wp_total} school days between them" );
bhp_wp_assert( 0 === $bhp_wp_stray, '⛔⛔ EVERY day inside EVERY offered week is a day the untouched day list also offers' );
bhp_wp_assert( 0 === $bhp_wp_weekend, '⛔ no offered week contains a weekend day' );
bhp_wp_assert( 0 === $bhp_wp_before, '⛔ no offered week contains a day before the 2026-10-01 floor' );

/*
 * ⛔ AND THE CONVERSE: the week list must not LOSE a day the day list offers
 *    inside the same span. A grouping that silently dropped Fridays would be
 *    just as wrong as one that added Saturdays, and it would be invisible on
 *    the page because a card shows a range rather than a list.
 */
$bhp_wp_last_day = $bhp_wp_weeks[11]['last'];
$bhp_wp_expected = 0;
foreach ( $bhp_wp_days as $bhp_wp_row ) {
	if ( $bhp_wp_row['ymd'] <= $bhp_wp_last_day ) {
		++$bhp_wp_expected;
	}
}
bhp_wp_assert( $bhp_wp_expected === $bhp_wp_total, '⛔ the week list DROPS no offerable day inside its own span either' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE SERVER GATE MOVED WITH THE CONTROL
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 3 · THE WEEK GATE ===\n";

bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( '' ), '⛔⛔ the LIVE gate rejects an empty week' );
bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( '2026-09-07' ), '⛔⛔ the LIVE gate rejects a SEPTEMBER Monday' );
bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( '2026-01-05' ), '⛔⛔ the LIVE gate rejects a Monday in the past' );
bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( '2099-01-04' ), '⛔⛔ the LIVE gate rejects a Monday past the horizon' );
bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( "2026-10-05' OR 1=1" ), '⛔⛔ the LIVE gate rejects an injection-shaped string' );
bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( 'not-a-date' ), '⛔⛔ the LIVE gate rejects a malformed value' );

$bhp_wp_live = bhp_readaloud_scheduler_weeks();
if ( $bhp_wp_live ) {
	bhp_wp_assert( true === bhp_readaloud_scheduler_week_is_offered( $bhp_wp_live[0]['value'] ), '⭐ the LIVE gate ACCEPTS a week the form actually rendered' );
	bhp_wp_assert( null !== bhp_readaloud_scheduler_week_by_value( $bhp_wp_live[0]['value'] ), 'week_by_value() finds a rendered week' );
	bhp_wp_assert( null === bhp_readaloud_scheduler_week_by_value( '2026-09-07' ), 'week_by_value() returns null for a week that was never offered' );
	/*
	 * ⛔ A MID-WEEK DAY IS NOT A WEEK. This is the shape a stale 1.19.334 form or
	 *    a hand-built POST would most plausibly send, and it must be refused.
	 */
	bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( $bhp_wp_live[1]['value'] . 'x' ), '⛔ the gate rejects a near-miss of a real week value' );
	if ( isset( $bhp_wp_live[1]['days'][2] ) ) {
		bhp_wp_assert( false === bhp_readaloud_scheduler_week_is_offered( $bhp_wp_live[1]['days'][2] ), '⛔⛔ the gate rejects a MID-WEEK DAY posted where a week belongs' );
	}
} else {
	bhp_wp_assert( false, 'the live week list is empty - every gate assertion below was SKIPPED' );
}

/* The handler's source, read directly: the shape of the validation. */
$bhp_wp_sched_src = (string) file_get_contents( get_template_directory() . '/inc/readaloud-scheduler.php' );

bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "\$_POST['visit_week']" ), 'the handler reads visit_week' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "\$_POST['visit_week_backup']" ), 'the handler reads visit_week_backup' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "\$_POST['weekdays']" ), 'the handler reads weekdays[]' );
bhp_wp_assert( false === strpos( $bhp_wp_sched_src, "\$_POST['visit_date']" ), '⛔⛔ the handler NO LONGER reads visit_date - no silent fallback for a stale cached form' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "bhp_readaloud_scheduler_week_by_value( \$week )" ), 'the handler validates the week against the server list' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "\$redirect( 'sameweek' )" ), 'the handler refuses a backup identical to the first choice' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "\$redirect( 'badweek' )" ), 'the handler refuses an unoffered week' );

/* The unchanged mechanics: nonce, honeypot, server-controlled recipient, capture. */
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, 'wp_verify_nonce' ), '⛔ the nonce check is unchanged and still present' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, "\$_POST['bhp_readaloud_hp']" ), '⛔ the honeypot check is unchanged and still present' );
bhp_wp_assert( false !== strpos( $bhp_wp_sched_src, 'bhp_readaloud_request_should_capture()' ), '⛔ the staging mail capture is unchanged and still present' );
bhp_wp_assert(
	false === strpos( $bhp_wp_sched_src, "\$_POST['to']" ) && false !== strpos( $bhp_wp_sched_src, "get_option( 'admin_email' )" ),
	'⛔⛔ the recipient is still SERVER-CONTROLLED and never read from the POST body'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · THE HONEST LINE — CARRIER ITEM 534, CHARACTER BY CHARACTER
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 4 · THE HONEST LINE (item 534, verbatim) ===\n";

$bhp_wp_honest   = bhp_readaloud_scheduler_honest_line();
$bhp_wp_expected_line = 'I’m an ICU nurse, and my hospital schedule posts about a month at a time. So pick the week that works for your class, and I’ll confirm the exact day and time as soon as my schedule comes out.';

bhp_wp_assert( $bhp_wp_expected_line === $bhp_wp_honest, '⛔⛔ the honest line is item 534 VERBATIM, character for character' );
bhp_wp_assert( 2 === substr_count( $bhp_wp_honest, '’' ), 'it carries exactly TWO typographic apostrophes (U+2019)' );
bhp_wp_assert( 0 === substr_count( $bhp_wp_honest, "'" ), '⛔ it carries NO straight apostrophe (U+0027)' );
bhp_wp_assert( false === strpos( $bhp_wp_honest, '—' ), '⛔ no em dash (his standing copy rail)' );
bhp_wp_assert(
	0 === preg_match( '/\b(we|us|our)\b/i', $bhp_wp_honest ),
	'⛔⛔ §9.1 THE VOICE RULE: no "we", "us" or "our" - this is his I-voice'
);
bhp_wp_assert( false !== strpos( $bhp_wp_honest, 'ICU nurse' ), 'it states the reason the picker asks for a week' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE RENDERED PAGE
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 5 · THE RENDERED PAGE ===\n";

$bhp_wp_page = bhp_wp_fetch( '/school-read-alouds/' );
echo "  NOTE  /school-read-alouds/ => HTTP {$bhp_wp_page['code']}, " . strlen( $bhp_wp_page['body'] ) . " bytes\n";

if ( 200 === $bhp_wp_page['code'] && '' !== $bhp_wp_page['body'] ) {
	$bhp_wp_b = $bhp_wp_page['body'];

	/* The headline: one word moved. Both directions asserted, per the 1.19.334
	   lesson that a one-sided string test passes green for the wrong reason. */
	bhp_wp_assert(
		false !== strpos( $bhp_wp_b, esc_html( 'Pick a week. I’ll come read to your class. Free.' ) ),
		'⭐ the headline reads "Pick a week. I’ll come read to your class. Free."'
	);
	bhp_wp_assert(
		false === strpos( $bhp_wp_b, esc_html( 'Pick a day. I’ll come read to your class. Free.' ) ),
		'⛔ the OLD "Pick a day" headline is gone'
	);
	bhp_wp_assert(
		false === strpos( $bhp_wp_b, "Pick a week. I'll come read" ),
		'⛔ the straight apostrophe does not appear in the headline'
	);

	bhp_wp_assert( false !== strpos( $bhp_wp_b, esc_html( $bhp_wp_expected_line ) ), '⛔⛔ the honest line renders on the LIVE page, verbatim' );

	/* The controls. */
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'name="visit_week"' ), '⭐⭐ the LIVE page emits the visit_week radio group' );
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'name="visit_week_backup"' ), '⭐ the LIVE page emits the backup radio group' );
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'name="weekdays[]"' ), '⭐ the LIVE page emits the Mon-Fri preference checkboxes' );
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'name="slots[]"' ), '⛔ the Morning/Afternoon checkboxes are unchanged and still present' );
	bhp_wp_assert( false === strpos( $bhp_wp_b, 'name="visit_date"' ), '⛔⛔ the LIVE page emits NO visit_date control' );
	bhp_wp_assert( false === strpos( $bhp_wp_b, 'data-bhp-cal-month' ), '⛔ the month grid is gone from the LIVE page' );

	/* Card count and labels. */
	preg_match_all( '/name="visit_week"\s+value="(\d{4}-\d{2}-\d{2})"/', $bhp_wp_b, $bhp_wp_m );
	$bhp_wp_rendered = isset( $bhp_wp_m[1] ) ? $bhp_wp_m[1] : array();
	echo '  NOTE  rendered week values: ' . count( $bhp_wp_rendered ) . "\n";
	bhp_wp_assert( count( $bhp_wp_rendered ) >= 10 && count( $bhp_wp_rendered ) <= 12, '⭐ the LIVE page renders 10-12 week cards' );
	bhp_wp_assert(
		count( $bhp_wp_rendered ) === count( array_unique( $bhp_wp_rendered ) ),
		'every rendered week value is distinct'
	);

	/* ⛔ Every rendered value must pass the server's own gate. This is the one
	   assertion that would catch a template and a gate drifting apart. */
	$bhp_wp_ungated = 0;
	foreach ( $bhp_wp_rendered as $bhp_wp_v ) {
		if ( ! bhp_readaloud_scheduler_week_is_offered( $bhp_wp_v ) ) {
			++$bhp_wp_ungated;
		}
	}
	bhp_wp_assert( 0 === $bhp_wp_ungated, '⛔⛔ EVERY week the page renders passes the server gate' );

	/* Backup values must be the same set, so a teacher can back up any week. */
	preg_match_all( '/name="visit_week_backup"\s+value="(\d{4}-\d{2}-\d{2})"/', $bhp_wp_b, $bhp_wp_mb );
	bhp_wp_assert(
		isset( $bhp_wp_mb[1] ) && $bhp_wp_mb[1] === $bhp_wp_rendered,
		'the backup group offers exactly the same weeks as the first-choice group'
	);

	/* No September, anywhere in the picker's own values. */
	$bhp_wp_sept_vals = 0;
	foreach ( $bhp_wp_rendered as $bhp_wp_v ) {
		foreach ( (array) bhp_readaloud_scheduler_week_by_value( $bhp_wp_v )['days'] as $bhp_wp_d ) {
			if ( 0 === strpos( $bhp_wp_d, '2026-09' ) ) {
				++$bhp_wp_sept_vals;
			}
		}
	}
	bhp_wp_assert( 0 === $bhp_wp_sept_vals, '⛔⛔ NO rendered week contains a September 2026 school day' );

	/* Required-ness. */
	bhp_wp_assert(
		(bool) preg_match( '/name="visit_week"\s+value="\d{4}-\d{2}-\d{2}"\s+required/', $bhp_wp_b ),
		'the first-choice radios carry required'
	);
	bhp_wp_assert(
		! preg_match( '/name="visit_week_backup"\s+value="\d{4}-\d{2}-\d{2}"\s+required/', $bhp_wp_b ),
		'⭐ the BACKUP radios carry NO required - the backup is optional'
	);
	bhp_wp_assert(
		! preg_match( '/name="weekdays\[\]"[^>]*required/', $bhp_wp_b ),
		'⭐ the weekday preferences carry NO required - they are optional'
	);
	bhp_wp_assert(
		! preg_match( '/<input[^>]*name="visit_week[^"]*"[^>]*disabled/i', $bhp_wp_b ),
		'⛔⛔ no disabled week input is ever printed'
	);

	/* Unchanged mechanics, on the rendered page. */
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'bhp_readaloud_nonce' ), '⛔ the nonce field still renders' );
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'bhp_readaloud_hp' ), '⛔ the honeypot still renders' );
	bhp_wp_assert( false !== strpos( $bhp_wp_b, 'name="school"' ) && false !== strpos( $bhp_wp_b, 'name="city"' ) && false !== strpos( $bhp_wp_b, 'name="contact"' ) && false !== strpos( $bhp_wp_b, 'name="email"' ) && false !== strpos( $bhp_wp_b, 'name="grades"' ) && false !== strpos( $bhp_wp_b, 'name="notes"' ), '⛔ every pre-existing form field still renders' );

	/* The tentative line, before the click. */
	bhp_wp_assert(
		false !== strpos( $bhp_wp_b, esc_html( 'It does not book the week.' ) ),
		'⛔⛔ the pre-click tentative line says the request does not book the week'
	);

	/* Funnel isolation and placeholders - unchanged expectations. */
	bhp_wp_assert( 0 === substr_count( $bhp_wp_b, 'data-popup-config' ), '⛔ no parent-funnel popup config on the teacher page' );
	bhp_wp_assert( 0 === substr_count( $bhp_wp_b, 'PENDING READ-BACK' ), '⛔ zero copy placeholders' );

	/* No price, no fee, no rate. */
	bhp_wp_assert(
		0 === preg_match( '/\$\s?\d/', substr( $bhp_wp_b, strpos( $bhp_wp_b, 'readaloud-scheduler' ) ?: 0, 20000 ) ),
		'⛔ no price figure appears anywhere in the scheduler section'
	);
} else {
	bhp_wp_assert( false, "the page did not return 200 - every §5 assertion was SKIPPED (code {$bhp_wp_page['code']})" );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · THE EMAIL — PURE, ASSERTED WITHOUT SENDING ANYTHING
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 6 · THE EMAIL ===\n";

$bhp_wp_msg = bhp_readaloud_request_compose(
	array(
		'school'       => 'Test Elementary',
		'city'         => 'boise',
		'contact'      => 'A Teacher',
		'email'        => 'teacher@example.com',
		'grades'       => 'First and second grade',
		'week'         => '2026-10-05',
		'week_label'   => 'Week of October 5',
		'week_range'   => 'Monday, October 5 to Friday, October 9',
		'backup'       => '2026-10-12',
		'backup_label' => 'Week of October 12',
		'backup_range' => 'Monday, October 12 to Friday, October 16',
		'weekdays'     => array( 'tue', 'thu' ),
		'slots'        => array( 'morning', 'afternoon' ),
		'notes'        => 'Two classes if that is possible.',
		'source_page'  => 'https://example.test/school-read-alouds/',
	)
);

bhp_wp_assert( false !== strpos( $bhp_wp_msg['subject'], 'TENTATIVE' ), '⛔⛔ the SUBJECT still carries the word TENTATIVE' );
bhp_wp_assert( false !== strpos( $bhp_wp_msg['subject'], 'Week of October 5' ), 'the subject names the requested WEEK' );
bhp_wp_assert( false !== strpos( $bhp_wp_msg['subject'], 'Test Elementary' ), 'the subject names the school' );

foreach ( array(
	'Week wanted:'    => 'Week of October 5',
	'the range'       => 'Monday, October 5 to Friday, October 9',
	'the raw value'   => '[2026-10-05]',
	'Backup week:'    => 'Week of October 12',
	'the days'        => 'Tuesday, Thursday',
	'the slots'       => 'Morning and Afternoon',
	'either one'      => '(either one works for them)',
	'the school'      => 'Test Elementary',
	'the grades'      => 'First and second grade',
	'the email'       => 'teacher@example.com',
	'the notes'       => 'Two classes if that is possible.',
	'nothing booked'  => 'Nothing is booked until you reply',
) as $bhp_wp_what => $bhp_wp_needle ) {
	bhp_wp_assert( false !== strpos( $bhp_wp_msg['body'], $bhp_wp_needle ), "the email carries {$bhp_wp_what}" );
}

/* The empty-optional path: a request with no backup and no weekday preference
   must still compose a readable message rather than two blank lines. */
$bhp_wp_min = bhp_readaloud_request_compose(
	array(
		'school'     => 'Test Elementary',
		'city'       => 'other',
		'contact'    => 'A Teacher',
		'email'      => 'teacher@example.com',
		'grades'     => 'Third grade',
		'week'       => '2026-10-05',
		'week_label' => 'Week of October 5',
		'week_range' => 'Monday, October 5 to Friday, October 9',
		'slots'      => array( 'morning' ),
	)
);
bhp_wp_assert( false !== strpos( $bhp_wp_min['body'], 'Backup week:   none given' ), '⭐ an absent backup says "none given" rather than leaving a blank line' );
bhp_wp_assert( false !== strpos( $bhp_wp_min['body'], 'Days that work: no preference given' ), '⭐ absent weekday preferences say "no preference given"' );
bhp_wp_assert( false !== strpos( $bhp_wp_min['body'], 'OUTSIDE THE FOUR LISTED CITIES' ), '⛔ the out-of-area flag is unchanged and still fires' );
bhp_wp_assert( false === strpos( $bhp_wp_min['body'], '(either one works for them)' ), 'one slot does not print the both-slots note' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 7 · THE THANK-YOU AND ERROR STATES
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 7 · STATUS MESSAGES ===\n";

$bhp_wp_ok = bhp_readaloud_request_status_message( 'captured' );
bhp_wp_assert( is_array( $bhp_wp_ok ), 'the captured state has a message' );
bhp_wp_assert( false !== strpos( $bhp_wp_ok['title'], 'Nothing is booked yet' ), '⛔⛔ the thank-you title still says nothing is booked yet' );
bhp_wp_assert( false !== strpos( $bhp_wp_ok['text'], 'TENTATIVE' ), '⛔⛔ the thank-you text still carries the word TENTATIVE' );
bhp_wp_assert( false !== strpos( $bhp_wp_ok['text'], 'confirm it by reply' ), '⛔⛔ it still says he confirms BY REPLY' );
bhp_wp_assert( false !== strpos( $bhp_wp_ok['text'], 'exact day and time' ), '⭐ it now says what he confirms: the exact day and time' );
bhp_wp_assert(
	bhp_readaloud_request_status_message( 'success' ) === $bhp_wp_ok,
	'the success and captured states are the same message (a visitor must not be able to tell)'
);

bhp_wp_assert( is_array( bhp_readaloud_request_status_message( 'badweek' ) ), 'the badweek state has a message' );
bhp_wp_assert( is_array( bhp_readaloud_request_status_message( 'sameweek' ) ), 'the sameweek state has a message' );
bhp_wp_assert( is_array( bhp_readaloud_request_status_message( 'noslot' ) ), 'the noslot state is unchanged and still present' );
bhp_wp_assert(
	is_array( bhp_readaloud_request_status_message( 'baddate' ) ),
	'⭐ the legacy baddate state still renders a sentence - a bookmarked 1.19.334 URL must not land on a blank'
);
bhp_wp_assert( null === bhp_readaloud_request_status_message( 'nonsense' ), 'an unknown status renders nothing' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 8 · COPY RAILS ACROSS EVERY NEW STRING
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 8 · COPY RAILS ===\n";

$bhp_wp_strings = array( $bhp_wp_honest );
foreach ( bhp_readaloud_scheduler_weekdays() as $bhp_wp_s ) {
	$bhp_wp_strings[] = $bhp_wp_s;
}
foreach ( array( 'success', 'badweek', 'sameweek', 'noslot', 'baddate', 'invalid', 'error' ) as $bhp_wp_st ) {
	$bhp_wp_m2 = bhp_readaloud_request_status_message( $bhp_wp_st );
	if ( is_array( $bhp_wp_m2 ) ) {
		$bhp_wp_strings[] = $bhp_wp_m2['title'];
		$bhp_wp_strings[] = $bhp_wp_m2['text'];
	}
}
foreach ( bhp_readaloud_scheduler_build_weeks( '2026-08-30', 7, 112, '2026-10-01', 12 ) as $bhp_wp_w ) {
	$bhp_wp_strings[] = $bhp_wp_w['label'];
	$bhp_wp_strings[] = $bhp_wp_w['range'];
}

$bhp_wp_we = 0;
$bhp_wp_em = 0;
foreach ( $bhp_wp_strings as $bhp_wp_s ) {
	if ( preg_match( '/\b(we|us|our)\b/i', $bhp_wp_s ) ) {
		++$bhp_wp_we;
		echo "  NOTE  'we' candidate: {$bhp_wp_s}\n";
	}
	if ( false !== strpos( $bhp_wp_s, '—' ) ) {
		++$bhp_wp_em;
	}
}
bhp_wp_assert( 0 === $bhp_wp_we, '⛔⛔ §9.1: NOT ONE customer-facing string this release adds contains "we", "us" or "our"' );
bhp_wp_assert( 0 === $bhp_wp_em, '⛔ no em dash in any string this release adds' );

/*
 * ⛔ THE NEVER-INVENT SWEEP. No new string may carry a reaction, a result, a
 *    rating, a count of schools, or a promise that he will say yes.
 */
$bhp_wp_banned = 0;
foreach ( $bhp_wp_strings as $bhp_wp_s ) {
	if ( preg_match( '/\b(loved|thrilled|teachers say|classrooms|proven|guarantee|award|rated|reviews?)\b/i', $bhp_wp_s ) ) {
		++$bhp_wp_banned;
		echo "  NOTE  never-invent candidate: {$bhp_wp_s}\n";
	}
}
bhp_wp_assert( 0 === $bhp_wp_banned, '⛔⛔ no new string carries a reaction, a result, a rating or an award' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 9 · THE ASSET
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 9 · THE HELPER SCRIPT ===\n";

$bhp_wp_js_path = get_template_directory() . '/assets/js/readaloud-calendar.js';
bhp_wp_assert( file_exists( $bhp_wp_js_path ), 'assets/js/readaloud-calendar.js still exists at its shipped path' );
$bhp_wp_js = (string) file_get_contents( $bhp_wp_js_path );
bhp_wp_assert( false !== strpos( $bhp_wp_js, 'visit_week' ), 'the helper reads the week radios' );
/*
 * ⛔⛔ AMENDED AT 1.19.336 (`CYCLE170-LD-CHAIN`). THE ASSERTION WAS WRONG, THE
 *     CODE WAS RIGHT, AND IT WENT RED ON ITS FIRST REAL EXECUTION — this suite
 *     had never been run against a PHP interpreter before 2026-08-30.
 *
 * ⛔ SUPERSEDED LINE, quoted rather than deleted:
 *
 *      bhp_wp_assert( false === strpos( $bhp_wp_js, 'toLocaleDateString' ), '⛔ the helper contains NO date formatter of its own' );
 *
 * ⭐ WHY IT FAILED: `assets/js/readaloud-calendar.js` contains the string
 *    `toLocaleDateString` exactly once, INSIDE THE DOC COMMENT THAT EXPLAINS WHY
 *    THE FILE DOES NOT USE ONE. A naive `strpos` over a whole file cannot tell a
 *    call from a comment about a call, so DOCUMENTING the rule broke the
 *    assertion that CHECKS the rule.
 *
 * ⛔ THIS IS A FAILURE CLASS, NOT A ONE-OFF. `bhp_bun_code_only()` in
 *    `test-cycle170-bundle.php` exists because the same thing happened there
 *    with `token_get_all()`; the MVP plan flagged it a third time for the Meta
 *    Pixel's inline docblocks. The fix is always to strip commentary first and
 *    never to delete the explanation to make a test pass.
 *
 * ⭐ THE PROPERTY IS UNCHANGED AND IS NOW ASSERTED MORE PRECISELY: comments are
 *    stripped, and the needle is the CALL (`toLocaleDateString(`) rather than
 *    the bare identifier. It still fails if anyone adds a real formatter.
 */
$bhp_wp_js_code = preg_replace( '#/\*.*?\*/#s', '', $bhp_wp_js );
$bhp_wp_js_code = preg_replace( '#^\s*//.*$#m', '', (string) $bhp_wp_js_code );
bhp_wp_assert(
	false === strpos( (string) $bhp_wp_js_code, 'toLocaleDateString(' ),
	'⛔ the helper contains NO date formatter of its own (comments stripped)'
);
bhp_wp_assert(
	1 === substr_count( $bhp_wp_js, 'toLocaleDateString' ),
	'⭐ the only mention of toLocaleDateString in the file is the comment explaining its absence'
);
bhp_wp_assert( false === strpos( $bhp_wp_js, 'createElement' ), '⛔⛔ the helper creates NO control - it cannot manufacture an offerable week' );
bhp_wp_assert( false === strpos( $bhp_wp_js, '.checked = true' ), '⛔ the helper never CHECKS a radio, it only clears a duplicate backup' );
bhp_wp_assert( function_exists( 'bhp_enqueue_readaloud_calendar_assets' ), 'the enqueue is unchanged and still present' );

/* ═══════════════════════════════════════════════════════════════════════════
 * TOTALS
 * ═══════════════════════════════════════════════════════════════════════════ */

$bhp_wp_p = isset( $GLOBALS['bhp_wp_pass'] ) ? (int) $GLOBALS['bhp_wp_pass'] : 0;
$bhp_wp_f = isset( $GLOBALS['bhp_wp_fail'] ) ? (int) $GLOBALS['bhp_wp_fail'] : 0;

echo "\n=== TOTALS ===\n";
echo "  PASS: {$bhp_wp_p}\n";
echo "  FAIL: {$bhp_wp_f}\n";
echo ( 0 === $bhp_wp_f ? "  RESULT: ALL PASS\n" : "  RESULT: FAILURES PRESENT\n" );
