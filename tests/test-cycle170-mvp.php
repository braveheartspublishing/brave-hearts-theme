<?php
/**
 * CYCLE170-LD-MVP — the conversion trim, the calendar floor, the apostrophe.
 * Theme 1.19.334 (2026-08-30). STAGING ONLY. Version pin amended to 1.19.335 at CYCLE170-LD-WEEKPICKER.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Three things in this pass can fail
 *    silently, and "silently" is the operative word in all three.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  · **The trim can fail LOUD or it can fail SILENT, and the two failures look
 *    nothing alike.** Item 530 removes three approved passages from the teacher
 *    page. Done by marking them `pending`, the page ships three
 *    `[PENDING READ-BACK — do not publish]` blocks onto the founder's return
 *    gate. Done by unsetting them GLOBALLY, `/gallery/` — which renders the same
 *    three slots at `page-gallery.php` lines 162-164 — loses its About section
 *    with no error, no placeholder and nothing to notice. ⭐ BOTH FAILURE MODES
 *    ARE ASSERTED AGAINST, in §1 and §4, and the second is the one a human
 *    reviewer would not have caught.
 *
 *  · **The calendar floor is an HONESTY fix, so the assertion is a CONSISTENCY
 *    assertion.** The page says "October onward" in two places. Before 1.19.334
 *    it offered September days anyway. §2 and §4 assert that the copy and the
 *    calendar now agree — not merely that the floor code runs.
 *
 *  · **The apostrophe can pass green while being wrong.** A test that only
 *    checks the curly form is present passes even if the straight form is ALSO
 *    present somewhere. §3 asserts BOTH directions.
 *
 * ⛔ RUN: `wp eval-file tests/test-cycle170-mvp.php` on STAGING.
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
function bhp_mvp_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_mvp_pass'] ) ) {
		$GLOBALS['bhp_mvp_pass'] = 0;
		$GLOBALS['bhp_mvp_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_mvp_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_mvp_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * GET a path on this site.
 *
 * @param string $path Path.
 * @return array{code:int,body:string}
 */
function bhp_mvp_fetch( $path ) {
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

echo "\n=== CYCLE170-LD-MVP · pin moved to theme 1.19.335 at CYCLE170-LD-WEEKPICKER ===\n";

echo "\n=== 0 · VERSION ===\n";

/*
 * ⭐ THE PIN MOVES WITH THE RELEASE, and it moves for the same reason the
 *    `bundle` suite's pin moved at 1.19.334: this suite now covers 1.19.335
 *    behaviour, because two of its assertions were amended for carrier item 534
 *    (the headline in §3, the September month draw in §4). A pin left at
 *    1.19.334 would go red on a correct build and would say nothing useful.
 */
$bhp_mvp_style = (string) file_get_contents( get_template_directory() . '/style.css' );
/*
 * ⛔ PIN MOVED AGAIN TO 1.19.336 AT `CYCLE170-LD-CHAIN`, for the same reason the
 *    two notes above give. ⛔ SUPERSEDED, quoted rather than deleted — the
 *    1.19.335 pin was the WEEKPICKER lane's and that attribution stands:
 *
 *      1 === preg_match( '/^Version:\s*1\.19\.335\s*$/m', $bhp_mvp_style ),
 *      'style.css declares Version: 1.19.335'
 */
/*
 * ⭐ MOVED AGAIN TO 1.19.337 BY `CYCLE170-LD-MICRO` (2026-08-30). SUPERSEDED
 *    ASSERTION, PRESERVED VERBATIM — the 1.19.336 pin was the CHAIN lane's and
 *    that attribution stands:
 *
 *      1 === preg_match( '/^Version:\s*1\.19\.336\s*$/m', $bhp_mvp_style ),
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
 *      bhp_mvp_assert(
 *      	1 === preg_match( '/^Version:\s*1\.19\.337\s*$/m', $bhp_mvp_style ),
 *      	'style.css declares Version: 1.19.337'
 *      );
 */
bhp_mvp_assert(
	1 === preg_match( '/^Version:\s*1\.19\.339\s*$/m', $bhp_mvp_style ),
	'style.css declares Version: 1.19.339'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE CONVERSION TRIM — CARRIER ITEM 530
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 1 · THE TRIM (item 530) ===\n";

bhp_mvp_assert(
	function_exists( 'bhp_readaloud_trimmed_slots' ),
	'bhp_readaloud_trimmed_slots() exists'
);

$bhp_mvp_trim = bhp_readaloud_trimmed_slots();
bhp_mvp_assert(
	array( 'founder-1', 'founder-2', 'founder-3' ) === $bhp_mvp_trim,
	'the trim list is exactly passages 1, 2 and 3'
);
bhp_mvp_assert(
	! in_array( 'founder-4', $bhp_mvp_trim, true ),
	'⛔ passage 4 is NOT in the trim list'
);

/*
 * ⛔⛔ THE PASSAGES ARE NOT DELETED. Item 530 says disabled, not gone. If a
 *     later pass "tidies" the constant, restoring the trim would mean finding
 *     and re-approving founder copy rather than deleting three array entries.
 */
$bhp_mvp_passages = bhp_readaloud_approved_passages();
foreach ( array( 'founder-1', 'founder-2', 'founder-3', 'founder-4' ) as $bhp_mvp_id ) {
	bhp_mvp_assert(
		isset( $bhp_mvp_passages[ $bhp_mvp_id ] ) && '' !== trim( $bhp_mvp_passages[ $bhp_mvp_id ] ),
		"⭐ the approved text of {$bhp_mvp_id} still EXISTS in the filter file (disabled, not deleted)"
	);
}
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_passages['founder-1'], 'Adams Elementary in Boise' ),
	'passage 1 is still character-intact in the source'
);
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_passages['founder-4'], 'summited Island Peak, just over 20,000 feet' ),
	'passage 4 is still character-intact in the source'
);

/*
 * ⭐ THE ITEM-530 CITATION IS IN THE FILE. The brief requires the disabled
 *    passages to carry it, so that a reader who finds three unused strings knows
 *    they were removed by a ruling rather than orphaned by an accident.
 */
$bhp_mvp_copy_src = (string) file_get_contents( get_template_directory() . '/inc/readaloud-approved-copy.php' );
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_copy_src, '530' ),
	'the item-530 citation is recorded in inc/readaloud-approved-copy.php'
);

/*
 * ⛔⛔ THE SCOPING TEST. Called OUTSIDE a page context the filter must return
 *     ALL FOUR passages — that is what protects `/gallery/`. A global unset
 *     would show up here as three missing slots.
 */
$bhp_mvp_slots = bhp_readaloud_funnel_copy_slots();
foreach ( array( 'founder-1', 'founder-2', 'founder-3' ) as $bhp_mvp_id ) {
	bhp_mvp_assert(
		isset( $bhp_mvp_slots[ $bhp_mvp_id ] ),
		"⛔ off the teacher page, {$bhp_mvp_id} is STILL in the slot map (this is what keeps /gallery/ whole)"
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE CALENDAR FLOOR — bundle finding #1, founder items 412 / 429
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 2 · THE CALENDAR FLOOR ===\n";

bhp_mvp_assert(
	function_exists( 'bhp_readaloud_scheduler_floor_date' ),
	'bhp_readaloud_scheduler_floor_date() exists'
);
bhp_mvp_assert(
	'2026-10-01' === bhp_readaloud_scheduler_floor_date(),
	'the floor is 2026-10-01, got ' . bhp_readaloud_scheduler_floor_date()
);

/*
 * ⭐ PURE BOUNDARY ASSERTIONS, with a FIXED "today". These are the cases that
 *    cannot be observed by loading the page on any one day.
 */
$bhp_mvp_aug = bhp_readaloud_scheduler_build_dates( '2026-08-30', 7, 90, '2026-10-01' );
bhp_mvp_assert( ! empty( $bhp_mvp_aug ), 'with the floor applied, dates are still offered' );
bhp_mvp_assert(
	'2026-10-01' === $bhp_mvp_aug[0]['ymd'],
	'⭐ the FIRST offerable day is 2026-10-01, got ' . ( $bhp_mvp_aug ? $bhp_mvp_aug[0]['ymd'] : 'none' )
);

$bhp_mvp_sept = 0;
foreach ( $bhp_mvp_aug as $bhp_mvp_row ) {
	if ( '2026-09' === substr( $bhp_mvp_row['ymd'], 0, 7 ) ) {
		++$bhp_mvp_sept;
	}
}
bhp_mvp_assert( 0 === $bhp_mvp_sept, "⛔ ZERO September days are offered, got {$bhp_mvp_sept}" );

/*
 * ⛔ THE HORIZON IS STILL MEASURED FROM TODAY AND IS NOT EXTENDED BY THE FLOOR.
 *    A floor that pushed the far end out would have quietly turned a 90-day
 *    horizon into a 120-day one.
 */
$bhp_mvp_last = $bhp_mvp_aug[ count( $bhp_mvp_aug ) - 1 ]['ymd'];
bhp_mvp_assert(
	$bhp_mvp_last <= '2026-11-28',
	"the far end of the horizon is unmoved (<= 2026-11-28), got {$bhp_mvp_last}"
);

/*
 * ⭐⭐ THE FLOOR EXPIRES BY ITSELF. This is the assertion that proves nobody has
 *     to remember to remove it. With a "today" after the floor, the 7-day lead
 *     rules again and the floor changes nothing at all.
 */
$bhp_mvp_after_floored = bhp_readaloud_scheduler_build_dates( '2026-11-10', 7, 90, '2026-10-01' );
$bhp_mvp_after_plain   = bhp_readaloud_scheduler_build_dates( '2026-11-10', 7, 90, '' );
bhp_mvp_assert(
	$bhp_mvp_after_floored === $bhp_mvp_after_plain,
	'⭐ once the lead edge passes the floor, the floored and unfloored lists are IDENTICAL (it expires by itself)'
);
bhp_mvp_assert(
	! empty( $bhp_mvp_after_floored ) && '2026-11-17' === $bhp_mvp_after_floored[0]['ymd'],
	'after the floor date, the 7-day lead rules again, got ' . ( $bhp_mvp_after_floored ? $bhp_mvp_after_floored[0]['ymd'] : 'none' )
);

/*
 * ⛔ THE PURE FUNCTION IS UNCHANGED WITHOUT A FLOOR ARGUMENT. Every pre-existing
 *    boundary assertion in the 1.19.331 and 1.19.326 suites calls it with three
 *    arguments; if the default were ON, those suites would be measuring
 *    something other than what they were written to measure.
 */
$bhp_mvp_nofloor = bhp_readaloud_scheduler_build_dates( '2026-08-30', 7, 90 );
bhp_mvp_assert(
	! empty( $bhp_mvp_nofloor ) && '2026-09' === substr( $bhp_mvp_nofloor[0]['ymd'], 0, 7 ),
	'⛔ the 3-argument (pure) call is UNCHANGED — no default floor, so older suites still measure lead/horizon'
);

/* A malformed floor must be ignored rather than treated as a floor of zero. */
bhp_mvp_assert(
	bhp_readaloud_scheduler_build_dates( '2026-08-30', 7, 90, 'garbage' ) === $bhp_mvp_nofloor,
	'a malformed floor is ignored, not applied as an empty string comparison'
);

/*
 * ⭐ THE GRID AGREES WITH THE OFFER LIST. September squares must exist and be
 *    DEAD — the brief's "dead spans like weekends" — not absent.
 */
$bhp_mvp_months = bhp_readaloud_scheduler_build_months( '2026-08-30', 7, 90, '2026-10-01' );
$bhp_mvp_keys   = array();
foreach ( $bhp_mvp_months as $bhp_mvp_m ) {
	$bhp_mvp_keys[ $bhp_mvp_m['key'] ] = (int) $bhp_mvp_m['offered'];
}
bhp_mvp_assert(
	isset( $bhp_mvp_keys['2026-09'] ),
	'⭐ September is still DRAWN as a month (dead spans, not a missing month)'
);
bhp_mvp_assert(
	isset( $bhp_mvp_keys['2026-09'] ) && 0 === $bhp_mvp_keys['2026-09'],
	'⛔ September offers ZERO selectable squares, got ' . ( isset( $bhp_mvp_keys['2026-09'] ) ? $bhp_mvp_keys['2026-09'] : 'n/a' )
);
bhp_mvp_assert(
	isset( $bhp_mvp_keys['2026-08'] ) && 0 === $bhp_mvp_keys['2026-08'],
	'August (the current month) is still drawn first and offers zero'
);
bhp_mvp_assert(
	isset( $bhp_mvp_keys['2026-10'] ) && $bhp_mvp_keys['2026-10'] > 0,
	'⭐ October offers selectable squares, got ' . ( isset( $bhp_mvp_keys['2026-10'] ) ? $bhp_mvp_keys['2026-10'] : 'n/a' )
);

/*
 * ⛔⛔ THE SERVER GATE. The grid not drawing a September radio is presentation.
 *     This is the assertion that a POSTed September date is REFUSED, which is
 *     the only one that matters against a crafted request.
 */
bhp_mvp_assert(
	! bhp_readaloud_scheduler_date_is_offered( '2026-09-15' ),
	'⛔ the SERVER refuses a posted September date (not merely absent from the grid)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE APOSTROPHE
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 3 · THE APOSTROPHE ===\n";

/*
 * ⛔⛔ AMENDED AT 1.19.335 (`CYCLE170-LD-WEEKPICKER`, carrier item 534).
 *     THE NEEDLE MOVED BY ONE WORD. THE ASSERTION'S PURPOSE DID NOT, AND
 *     NOTHING WAS DELETED.
 *
 *     WAS, at 1.19.334:
 *       $bhp_mvp_curly    = 'Pick a day. I’ll come read to your class. Free.';
 *       $bhp_mvp_straight = "Pick a day. I'll come read to your class. Free.";
 *
 *     Item 534 makes the scheduler a WEEK picker, so the headline reads "Pick a
 *     week" — the same honesty rule the 1.19.334 calendar floor served, applied
 *     to the copy instead of the control: a headline that said "Pick a day"
 *     beside a week picker would state a mechanism the page does not have.
 *
 * ⭐ THIS SECTION'S REAL SUBJECT IS THE APOSTROPHE, AND THAT IS UNCHANGED. All
 *    three assertions still test exactly what they tested: the typographic form
 *    is present, the straight form is absent, and the wording either side of it
 *    is untouched. A fourth assertion is added below to prove the OLD headline
 *    is gone rather than merely joined by the new one — the same both-directions
 *    discipline this section already applies to the apostrophe itself.
 */
$bhp_mvp_sra_src  = (string) file_get_contents( get_template_directory() . '/inc/school-read-alouds.php' );
$bhp_mvp_curly    = 'Pick a week. I’ll come read to your class. Free.';
$bhp_mvp_straight = "Pick a week. I'll come read to your class. Free.";

bhp_mvp_assert(
	false !== strpos( $bhp_mvp_sra_src, $bhp_mvp_curly ),
	'source: the headline carries the typographic apostrophe (U+2019)'
);
bhp_mvp_assert(
	false === strpos( $bhp_mvp_sra_src, $bhp_mvp_straight ),
	'⛔ source: the straight-apostrophe form is GONE, not merely joined by the curly one'
);
/* One character moved at 1.19.334, one word at 1.19.335. Nothing else may have. */
bhp_mvp_assert(
	1 === preg_match( '/Pick a week\. I.ll come read to your class\. Free\./u', $bhp_mvp_sra_src ),
	'the wording either side of the apostrophe is untouched'
);
bhp_mvp_assert(
	0 === preg_match( '/Pick a day\. I.ll come read to your class\. Free\./u', $bhp_mvp_sra_src ),
	'⛔ 1.19.335: the OLD "Pick a day" headline is GONE, not merely joined by the new one'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · THE RENDERED PAGE — what a browser actually receives
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 4 · THE RENDERED TEACHER PAGE ===\n";

$bhp_mvp_res = bhp_mvp_fetch( '/school-read-alouds/' );
bhp_mvp_assert( 200 === $bhp_mvp_res['code'], '/school-read-alouds/ returns 200, got ' . $bhp_mvp_res['code'] );
$bhp_mvp_html = $bhp_mvp_res['body'];

/* ── THE TRIM, AS RENDERED ─────────────────────────────────────────────── */

foreach ( array( 'founder-1', 'founder-2', 'founder-3' ) as $bhp_mvp_id ) {
	bhp_mvp_assert(
		false === strpos( $bhp_mvp_html, esc_html( $bhp_mvp_passages[ $bhp_mvp_id ] ) ),
		"⛔ rendered: {$bhp_mvp_id} is ABSENT from the page"
	);
}
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_html, esc_html( $bhp_mvp_passages['founder-4'] ) ),
	'⭐ rendered: passage 4 IS present, character-exact'
);

/*
 * ⛔⛔ ZERO PLACEHOLDERS. This is the assertion that separates "trimmed" from
 *     "marked pending", and a `[PENDING READ-BACK — do not publish]` block on
 *     the founder's return gate is the failure it exists to catch.
 */
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, 'PENDING READ-BACK' ),
	'⛔ rendered: ZERO "[PENDING READ-BACK — do not publish]" blocks'
);
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, 'bhp-copy-placeholder' ),
	'⛔ rendered: ZERO placeholder containers of any kind'
);
/* The About section itself survives the trim and is not left headless. */
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_html, 'About the read-aloud' ),
	'rendered: the About section heading is still on the page'
);

/* ── PAGE ORDER ────────────────────────────────────────────────────────── */

/*
 * ⭐ ORDER IS ASSERTED BY POSITION, NOT BY EYE. The brief fixes the sequence:
 *    hero+chips -> what a visit looks like -> passage 4 -> calendar/form ->
 *    carousel -> Past Read-Alouds -> educator capture.
 */
$bhp_mvp_pos = array(
	'visit'    => strpos( $bhp_mvp_html, 'What a visit looks like' ),
	'passage4' => strpos( $bhp_mvp_html, esc_html( $bhp_mvp_passages['founder-4'] ) ),
	'sched'    => strpos( $bhp_mvp_html, 'id="readaloud-scheduler"' ),
	'past'     => strpos( $bhp_mvp_html, 'Past Read-Alouds' ),
	'capture'  => strpos( $bhp_mvp_html, 'Free classroom resources by email' ),
);
foreach ( $bhp_mvp_pos as $bhp_mvp_k => $bhp_mvp_v ) {
	bhp_mvp_assert( false !== $bhp_mvp_v, "rendered: landmark present: {$bhp_mvp_k}" );
}
bhp_mvp_assert(
	$bhp_mvp_pos['visit'] < $bhp_mvp_pos['passage4'],
	'order: "What a visit looks like" precedes passage 4'
);
bhp_mvp_assert(
	$bhp_mvp_pos['passage4'] < $bhp_mvp_pos['sched'],
	'order: passage 4 precedes the calendar/form'
);
bhp_mvp_assert(
	$bhp_mvp_pos['sched'] < $bhp_mvp_pos['past'],
	'order: the calendar/form precedes Past Read-Alouds'
);
bhp_mvp_assert(
	$bhp_mvp_pos['past'] < $bhp_mvp_pos['capture'],
	'order: Past Read-Alouds precedes the educator capture'
);

/* ── THE CALENDAR, AS RENDERED ─────────────────────────────────────────── */

/*
 * ⛔ A SELECTABLE DAY IS A RADIO INPUT. A dead one is a `<span>` and carries no
 *    `value=`. So counting `value="2026-09-` counts exactly the September days a
 *    browser could submit.
 */
$bhp_mvp_sep_inputs = substr_count( $bhp_mvp_html, 'value="2026-09-' );
$bhp_mvp_oct_inputs = substr_count( $bhp_mvp_html, 'value="2026-10-' );
bhp_mvp_assert(
	0 === $bhp_mvp_sep_inputs,
	"⛔ rendered: ZERO selectable September days, got {$bhp_mvp_sep_inputs}"
);
bhp_mvp_assert(
	$bhp_mvp_oct_inputs > 0,
	"⭐ rendered: October days ARE selectable, got {$bhp_mvp_oct_inputs}"
);
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, 'id="bhp-day-2026-09-' ),
	'rendered: no September day carries a form-control id'
);

/*
 * ⛔⛔ AMENDED AT 1.19.335 (`CYCLE170-LD-WEEKPICKER`, carrier item 534).
 *
 *     WAS: `'⭐ rendered: September is still DRAWN as a month (dead spans, not a
 *     hole)'`, asserting `false !== strpos( $html, 'September 2026' )`.
 *
 * ⭐ THAT ASSERTION BELONGED TO A MONTH GRID AND ONLY TO A MONTH GRID. A
 *    calendar must draw September or its weekday columns go ragged, so "drawn
 *    dead, not omitted" was the correct requirement at 1.19.334. Item 534
 *    replaces the grid with a LIST of week cards, and a list has no such
 *    obligation: an unofferable week is simply not a card, which is a stronger
 *    guarantee than a dead square (there is no control in the DOM at all).
 *
 * ⛔ SO THE REQUIREMENT INVERTS, AND THAT IS THE HONEST OUTCOME RATHER THAN A
 *    LOOSENING: at 1.19.335 the page must NOT print "September 2026" anywhere,
 *    because the hero lead and the item-522 chip both say "October onward" and
 *    there is no longer any structural reason to draw a month a teacher cannot
 *    ask for. The three September assertions above are UNCHANGED and still
 *    carry this section's real subject: no September day is selectable.
 */
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, 'September 2026' ),
	'⭐ 1.19.335 rendered: "September 2026" does not appear at all - the grid that had to draw it is gone'
);

/* ── THE CONSISTENCY ASSERTION THE BRIEF ASKED FOR ─────────────────────── */

/*
 * ⭐⭐ THIS IS THE POINT OF THE FLOOR. The page states a rule in two places; the
 *     calendar must now obey it. Before 1.19.334 these three assertions could
 *     not all have passed together.
 */
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_html, 'from October onward' ),
	'the hero lead still says "from October onward"'
);
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_html, 'October onward' ),
	'the item-522 chip still says "October onward"'
);
$bhp_mvp_live = bhp_readaloud_scheduler_dates();
bhp_mvp_assert(
	! empty( $bhp_mvp_live ) && '2026-10-01' <= $bhp_mvp_live[0]['ymd'],
	'⭐⭐ CONSISTENCY: the live first offerable day is on or after 2026-10-01, so "October onward" is LITERALLY TRUE'
);

/* ── THE APOSTROPHE, AS RENDERED ───────────────────────────────────────── */

bhp_mvp_assert(
	false !== strpos( $bhp_mvp_html, esc_html( $bhp_mvp_curly ) ),
	'rendered: the scheduler headline carries the typographic apostrophe'
);
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, esc_html( $bhp_mvp_straight ) ),
	'⛔ rendered: the straight-apostrophe form does not appear'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · NOTHING ELSE MOVED
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 5 · NON-REGRESSION ===\n";

/*
 * ⛔⛔ /author-visits/ IS REACHED FROM PRINTED QR CODES TAPED TO CLASSROOM
 *     DOORS. Item 524: it must never 301 and must never change. Paper cannot be
 *     recalled.
 */
$bhp_mvp_av = bhp_mvp_fetch( '/author-visits/' );
bhp_mvp_assert( 200 === $bhp_mvp_av['code'], '/author-visits/ still returns 200, got ' . $bhp_mvp_av['code'] );
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_av['body'], 'from October onward' ),
	'/author-visits/ is unchanged by this lane'
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE PARENT FUNNEL STAYS OFF THE TEACHER PAGE — AND THE NEEDLE MATTERS.
 *     THIS ASSERTION WAS WRONG ON ITS FIRST RUN AND IS CORRECTED HERE RATHER
 *     THAN LOOSENED, WITH THE MEASUREMENT THAT SETTLED IT RECORDED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The first run of this suite asserted the bare substring `parent_popup` and
 * went RED with 3 hits. ⛔ THE BUILD WAS FINE; THE ASSERTION WAS NOT.
 *
 * ⭐ WHAT THE THREE HITS ACTUALLY ARE, measured on staging 2026-08-30: all three
 *    sit INSIDE A `<script>` BLOCK (verified by scanning tag boundaries, not by
 *    eye) and all three belong to `window.bhpMetaPixel.config.map` — the
 *    SITEWIDE Meta Pixel EVENT-NAME LOOKUP TABLE, whose keys include
 *    `parent_popup_success` and `teacher_popup_success`. It is a static
 *    dictionary. It renders no markup, sets no storage and fires nothing.
 *
 * ⭐⭐ THE CONTROL THAT PROVES IT IS NOT A LEAK: `/teachers/` — the established
 *     teacher page, the one surface `.claude/rules/funnels.md` has always
 *     excluded — carries the SAME 3 hits. A needle that reds on the reference
 *     teacher page is measuring the pixel dictionary, not funnel state.
 *
 * ⛔ SO THE ASSERTIONS BELOW MEASURE THINGS THAT ACTUALLY CHANGE FUNNEL STATE:
 *    the storage-key prefix, the lead-magnet key, and the popup ROOT ELEMENT.
 *    Counts observed on staging 2026-08-30 at 1.19.334:
 *
 *      surface               bhp_parent_popup   data-popup-config
 *      /  /author-visits/  /books/        1              1   (parent popup live)
 *      /teachers/                         0              1   (TEACHER popup)
 *      /school-read-alouds/               0              0   (no popup at all)
 *
 *    ⭐ THE TEACHER PAGE IS STRICTER THAN `/teachers/`: it renders NO popup root
 *      of any kind. That is item 524's single-tail-ask ruling, measured.
 */
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, 'bhp_parent_popup' ),
	'⛔ rendered: zero parent-funnel STORAGE KEY (bhp_parent_popup)'
);
bhp_mvp_assert(
	false === strpos( $bhp_mvp_html, 'adventure_kit_parent' ),
	'⛔ rendered: zero parent LEAD-MAGNET key (adventure_kit_parent)'
);
bhp_mvp_assert(
	0 === substr_count( $bhp_mvp_html, 'data-popup-config' ),
	'⛔⛔ rendered: ZERO popup roots of ANY funnel, got ' . substr_count( $bhp_mvp_html, 'data-popup-config' )
);
/*
 * ⭐⭐ THE MARKUP, WITH EVERY `<script>` BLOCK REMOVED. This is the assertion
 *     that says what the earlier over-broad needles were REACHING FOR: no
 *     parent-funnel MARKUP — no link, no form, no overlay — reaches a reader.
 *
 * ⛔ THE STRIPPING IS THE POINT, NOT A LOOPHOLE. Measured on staging
 *    2026-08-30, `adventure-kit-thank-you` appears twice on this page and TWICE
 *    ON `/teachers/`, and all four occurrences are JS COMMENTS inside the
 *    sitewide Meta Pixel inline script (verified by tag-boundary scan). A
 *    comment inside a script is not a funnel surface. What WOULD be one is an
 *    `href`, a form action or a popup root — all of which live in markup, which
 *    is exactly what remains after the strip.
 */
$bhp_mvp_markup = (string) preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $bhp_mvp_html );
bhp_mvp_assert(
	'' !== $bhp_mvp_markup && strlen( $bhp_mvp_markup ) < strlen( $bhp_mvp_html ),
	'the script-stripped markup was produced (strip actually removed something)'
);
foreach ( array( 'adventure-kit-thank-you', 'parent_popup', 'adventure_kit' ) as $bhp_mvp_needle ) {
	bhp_mvp_assert(
		false === strpos( $bhp_mvp_markup, $bhp_mvp_needle ),
		"⛔⛔ MARKUP (scripts stripped): zero parent-funnel trace: {$bhp_mvp_needle}"
	);
}

/*
 * ⚠ PRINTED, NOT ASSERTED, AND ROUTED TO GANDALF AS AN INHERITED FINDING.
 *   The sitewide Meta Pixel inline script ships full PHP-docblock-style
 *   COMMENTARY to every page — it is why two naive needles in this suite went
 *   red on their first run. It costs bytes on every page load and it defeats
 *   string assertions across the whole test corpus. NOT this lane's to change.
 */
echo '  NOTE  INHERITED, SITEWIDE (not this lane): in-script "parent_popup" = '
	. substr_count( $bhp_mvp_html, 'parent_popup' )
	. ', in-script "adventure-kit-thank-you" = '
	. substr_count( $bhp_mvp_html, 'adventure-kit-thank-you' )
	. " — identical counts on /teachers/; all inside <script> comments\n";

/* noindex survives (item 525). */
bhp_mvp_assert(
	false !== strpos( $bhp_mvp_html, 'noindex' ),
	'the teacher page is still noindex (item 525)'
);

echo "\n=== RESULT ===\n";
$bhp_mvp_p = isset( $GLOBALS['bhp_mvp_pass'] ) ? (int) $GLOBALS['bhp_mvp_pass'] : 0;
$bhp_mvp_f = isset( $GLOBALS['bhp_mvp_fail'] ) ? (int) $GLOBALS['bhp_mvp_fail'] : 0;
echo "  PASS {$bhp_mvp_p}   FAIL {$bhp_mvp_f}\n";
echo 0 === $bhp_mvp_f ? "  ALL PASS\n\n" : "  ⛔ FAILURES PRESENT\n\n";
