<?php
/**
 * /author-visits/ — test suite. Theme 1.19.233, `CYCLE162-LD-VISITS-PAGE`.
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-author-visits-page.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, in descending order of what it costs if it breaks
 * ---------------------------------------------------------------------------
 *   1. ⛔ A BUTTON MUST NEVER APPEAR FOR A VISIT THE SITE WOULD REFUSE. The page
 *      decides "ordering is open" and `bhp_school_visit_resolve()` decides
 *      "this session may have hand-delivery". If those two ever disagree, a
 *      parent clicks an order button, gets an ordinary checkout with postage,
 *      and believes Andrew is bringing the book. Asserted by running BOTH
 *      against the SAME seeded registry on the SAME day.
 *   2. ⛔ A PAST VISIT MUST DISAPPEAR, and an ordering-closed-but-not-yet-visited
 *      visit must NOT disappear. Two different dates, two different questions.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ REWRITTEN AT THEME 1.19.239 / PLUGIN 1.8.56 (`CYCLE164-LD-ORDER-WINDOW`,
 *    finished by `CYCLE164-LD-ORDER-WINDOW-FINISH`). THE GATE MOVED.
 * ---------------------------------------------------------------------------
 * ⛔ THE STATED `cutoff` FIELD NO LONGER GATES ANYTHING. It is display only:
 *    it is what the page prints as "Order by ..." and what parents were emailed,
 *    three days before the visit. The button is now decided by the ONLINE CLOSE,
 *    00:00 site time on `visit - 1`, so ordering stays open through the whole of
 *    `visit - 2`. `bhp_school_visit_is_open_on()` in the bundle plugin is the one
 *    place that question is answered, and this page routes through it.
 *
 * ⛔ THREE ASSERTIONS IN THIS FILE DESCRIBED THE OLD GATE AND WOULD HAVE
 *    FAILED AGAINST 1.19.239. They are rewritten below, and each superseded
 *    wording is preserved verbatim in a comment beside its replacement so the
 *    movement is visible and is not re-derived. ⛔ NOT ONE ASSERTION WAS
 *    LOOSENED OR DELETED TO MAKE THE SUITE GREEN -- the count went UP.
 *
 * ⛔ THE GRACE WINDOW BETWEEN THE STATED DEADLINE AND THE ONLINE CLOSE IS
 *    DELIBERATE AND IS NEVER ADVERTISED. §5 asserts that silence directly.
 *   3. Missing-time tolerance: a registry row with no `time` must still render.
 *      The three visits already seeded on both environments predate the field.
 *   4. Link and UTM correctness, since these URLs are destined for print.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It renders no page and starts no HTTP request. `bhp_author_visits_build_rows()`
 *    is pure precisely so that every date boundary is an assertion instead of
 *    something you could only observe by waiting for a Tuesday.
 * ⛔ It writes NO product, price, coupon, shipping setting, zone or order. It
 *    writes exactly one thing and restores it: the `bhp_school_visits` option,
 *    snapshotted before the first write and restored on EVERY exit path.
 * ⛔ Cleanup is EXPLICIT, never `register_shutdown_function()`. Under
 *    `wp eval-file` a shutdown callback does not run when the script calls
 *    `exit()`, which is exactly what a failing suite does — reproduced and
 *    recorded in `plugins/brave-hearts-bundle-pricing/tests/test-school-visit-pickup.php`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$GLOBALS['bhp_avp_failures'] = array();
$GLOBALS['bhp_avp_skips']    = array();

function bhp_avp_assert( $condition, $label ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$GLOBALS['bhp_avp_failures'][] = $label;
	}
}

function bhp_avp_skip( $label, $reason ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$GLOBALS['bhp_avp_skips'][] = $label;
}

/*
 * ⛔ `$GLOBALS[...]` RATHER THAN FILE-SCOPE VARIABLES, AND IT IS NOT STYLE.
 *    `wp eval-file` includes this file from inside a method, so file-scope
 *    variables are method locals and are invisible to the functions above.
 */
$GLOBALS['bhp_avp_option_snapshot'] = null;
$GLOBALS['bhp_avp_option_seeded']   = false;

function bhp_avp_cleanup() {
	if ( ! $GLOBALS['bhp_avp_option_seeded'] ) {
		return;
	}
	$GLOBALS['bhp_avp_option_seeded'] = false;

	$option = defined( 'BHP_SCHOOL_VISIT_OPTION' ) ? BHP_SCHOOL_VISIT_OPTION : 'bhp_school_visits';
	if ( null === $GLOBALS['bhp_avp_option_snapshot'] || false === $GLOBALS['bhp_avp_option_snapshot'] ) {
		delete_option( $option );
		echo "CLEANUP: the visit registry did not exist before this run and has been deleted again.\n";
	} else {
		update_option( $option, $GLOBALS['bhp_avp_option_snapshot'] );
		echo "CLEANUP: the visit registry has been restored to its pre-run value.\n";
	}
}

function bhp_avp_finish() {
	bhp_avp_cleanup();
	$failures = $GLOBALS['bhp_avp_failures'];
	$skips    = $GLOBALS['bhp_avp_skips'];
	echo "\n--------------------------------------------------\n";
	if ( empty( $failures ) ) {
		echo 'RESULT: ALL ASSERTIONS PASSED (' . count( $skips ) . " skipped)\n";
		exit( 0 );
	}
	echo 'RESULT: ' . count( $failures ) . " FAILING ASSERTION(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}

/* =========================================================================
 * 0. EVERYTHING IS LOADED
 * ====================================================================== */

bhp_avp_assert( function_exists( 'bhp_author_visits_build_rows' ), 'inc/author-visits.php is loaded by the theme (bhp_author_visits_build_rows exists)' );
bhp_avp_assert( function_exists( 'bhp_author_visits_shop_url' ), 'The shop-URL builder is defined' );
bhp_avp_assert( function_exists( 'bhp_author_visits_rows' ), 'The live row reader is defined' );
bhp_avp_assert( function_exists( 'bhp_author_visits_today' ), 'The site-timezone "today" helper is defined' );

if ( ! function_exists( 'bhp_author_visits_build_rows' ) ) {
	fwrite( STDERR, "FATAL: the feature file is not loaded; nothing else can be tested.\n" );
	bhp_avp_finish();
}

/* =========================================================================
 * 1. THE PURE ROW BUILDER — every date boundary, stated as an assertion
 * ====================================================================== */

$today  = '2026-08-17';
$fixture = array(
	// Wide open: visit and cutoff both in the future. CARRIES A TIME.
	'avptest-open'      => array( 'slug' => 'avptest-open', 'school' => 'Open Test School', 'date' => '2026-08-28', 'cutoff' => '2026-08-25', 'time' => '8:50 AM' ),
	// Open, and NO TIME AT ALL. This is the shape of every row seeded before
	// 1.8.51, so it is the tolerance case that actually happens.
	'avptest-notime'    => array( 'slug' => 'avptest-notime', 'school' => 'No Time Test School', 'date' => '2026-09-04', 'cutoff' => '2026-09-01' ),
	/*
	 * ⛔ SUPERSEDED FIXTURE COMMENTS, PRESERVED SO THE MOVEMENT IS VISIBLE:
	 *      // Cutoff is TODAY. Inclusive, so ordering is still open.
	 *      // Cutoff passed YESTERDAY, visit still to come. Listed, no button.
	 *   ⭐ 1.19.239: the STATED cutoff is TODAY and the visit is three weeks
	 *   away, so ordering is wide open and the stated deadline proves it gates
	 *   nothing. `avptest-closed`'s DATE moved from 2026-08-20 to 2026-08-18 --
	 *   under the new gate a visit on the 20th would still be OPEN on the 17th
	 *   (`visit - 2` is the 18th), so the row had to become a real close: the
	 *   visit is TOMORROW, therefore today is `visit - 1` and the button greyed
	 *   at 00:00 this morning.
	 */
	'avptest-lastday'   => array( 'slug' => 'avptest-lastday', 'school' => 'Last Day Test School', 'date' => '2026-09-10', 'cutoff' => '2026-08-17', 'time' => '10:10 AM' ),
	'avptest-closed'    => array( 'slug' => 'avptest-closed', 'school' => 'Closed Test School', 'date' => '2026-08-18', 'cutoff' => '2026-08-16', 'time' => '9:00 AM' ),
	// ⭐ 1.19.239 THE GRACE CASE, and the only reason this release exists. The
	// STATED deadline passed YESTERDAY, but the visit is the day after tomorrow,
	// so today is `visit - 2` and the button is STILL LIVE. Nothing on the page
	// says so.
	'avptest-grace'     => array( 'slug' => 'avptest-grace', 'school' => 'Grace Test School', 'date' => '2026-08-19', 'cutoff' => '2026-08-16', 'time' => '1:15 PM' ),
	// The visit is TODAY. Still listed.
	'avptest-todayvis'  => array( 'slug' => 'avptest-todayvis', 'school' => 'Today Test School', 'date' => '2026-08-17', 'cutoff' => '2026-08-14' ),
	// The visit was YESTERDAY. Gone.
	'avptest-past'      => array( 'slug' => 'avptest-past', 'school' => 'Past Test School', 'date' => '2026-08-16', 'cutoff' => '2026-08-13' ),
	// Malformed: no school.
	'avptest-noschool'  => array( 'slug' => 'avptest-noschool', 'school' => '', 'date' => '2026-09-20', 'cutoff' => '2026-09-17' ),
	// Malformed: no cutoff.
	'avptest-nocutoff'  => array( 'slug' => 'avptest-nocutoff', 'school' => 'No Cutoff Test School', 'date' => '2026-09-20' ),
);

$rows = bhp_author_visits_build_rows( $fixture, $today );

$by_slug = array();
foreach ( $rows as $row ) {
	$by_slug[ $row['slug'] ] = $row;
}

bhp_avp_assert( isset( $by_slug['avptest-open'] ), 'An upcoming visit inside its cutoff is LISTED' );
bhp_avp_assert( ! isset( $by_slug['avptest-past'] ), 'A visit whose DATE has passed is HIDDEN' );
bhp_avp_assert( isset( $by_slug['avptest-todayvis'] ), 'A visit happening TODAY is still listed (the date comparison is inclusive)' );
bhp_avp_assert( isset( $by_slug['avptest-closed'] ), 'A visit whose CUTOFF has passed but whose DATE has not is STILL LISTED -- cutoff and date answer different questions' );
bhp_avp_assert( ! isset( $by_slug['avptest-noschool'] ), 'A row with an empty school name is dropped' );
bhp_avp_assert( ! isset( $by_slug['avptest-nocutoff'] ), 'A row with no cutoff is dropped' );

/* --- the closed state --------------------------------------------------- */

/*
 * ⛔ SUPERSEDED ASSERTION LABELS, PRESERVED VERBATIM SO THE MOVEMENT IS
 *    VISIBLE AND IS NOT RE-DERIVED. Until 1.19.238 these three read:
 *      'A past-cutoff visit is marked open=false'
 *      'A past-cutoff visit carries NO URL AT ALL -- a template cannot render a
 *       link that does not exist'
 *      'A visit happening today whose cutoff has passed is listed but closed to
 *       ordering'
 *    All three named the STATED cutoff as the thing that closed the row. From
 *    1.19.239 the online close does that, and the fixtures moved with them.
 */
bhp_avp_assert( isset( $by_slug['avptest-closed'] ) && false === $by_slug['avptest-closed']['open'], '⛔ THE ONLINE CLOSE BITES: a visit happening TOMORROW is marked open=false -- the button greyed at 00:00 this morning' );
bhp_avp_assert( isset( $by_slug['avptest-closed'] ) && '' === $by_slug['avptest-closed']['url'], 'A closed visit carries NO URL AT ALL -- a template cannot render a link that does not exist, greyed or otherwise' );
bhp_avp_assert( isset( $by_slug['avptest-closed'] ) && '2026-08-16' === $by_slug['avptest-closed']['cutoff'], '⛔ A CLOSED ROW STILL CARRIES ITS STATED DEADLINE -- the "Order by ..." line survives the close, because the row stays as a trust record' );
bhp_avp_assert( isset( $by_slug['avptest-todayvis'] ) && false === $by_slug['avptest-todayvis']['open'], 'A visit happening TODAY is listed but closed to ordering' );

/* --- ⭐ THE GRACE WINDOW, WHICH IS NEVER ADVERTISED -------------------- */

bhp_avp_assert( isset( $by_slug['avptest-grace'] ), 'A visit whose STATED deadline has passed but whose online close has not is LISTED' );
bhp_avp_assert( isset( $by_slug['avptest-grace'] ) && true === $by_slug['avptest-grace']['open'], '⭐ THE GRACE IS REAL: the stated deadline passed YESTERDAY, the visit is in two days, and the button is STILL LIVE' );
bhp_avp_assert( isset( $by_slug['avptest-grace'] ) && '' !== $by_slug['avptest-grace']['url'], 'The grace row carries a real order URL, not a dead one' );
bhp_avp_assert( isset( $by_slug['avptest-grace'] ) && '2026-08-16' === $by_slug['avptest-grace']['cutoff'], '⛔ AND IT STILL PRINTS THE DEADLINE THAT HAS ALREADY PASSED -- the page never tells the parent the real window is a day longer' );

/* --- ⭐⭐ THE BOUNDARY, WALKED DAY BY DAY ACROSS ONE VISIT -----------
 *
 * ⛔ SUPERSEDED ASSERTION, PRESERVED VERBATIM. Until 1.19.238 the boundary
 *    was walked across the STATED cutoff and read:
 *
 *      $rows_tomorrow = bhp_author_visits_build_rows( $fixture, '2026-08-18' );
 *      ... 'ONE DAY AFTER the cutoff, the same row is closed -- the boundary
 *           moves with the date, not with a deploy'
 *
 *    That is no longer true and MUST no longer be true: one day after the
 *    stated cutoff is exactly the grace day Andrew asked for. The boundary is
 *    walked below against the VISIT date instead, which is what now decides it.
 *    `avptest-open` is the visit on 2026-08-28, so `visit - 2` is the 26th.
 * ---------------------------------------------------------------------- */

bhp_avp_assert( isset( $by_slug['avptest-lastday'] ) && true === $by_slug['avptest-lastday']['open'], '⛔ THE STATED CUTOFF NO LONGER GATES: a visit three weeks out is open even ON its stated deadline day, and would be open the day after it too' );
bhp_avp_assert( isset( $by_slug['avptest-lastday'] ) && '' !== $by_slug['avptest-lastday']['url'], 'That row still carries an order URL' );

$bhp_avp_walk = array();
foreach ( array( '2026-08-25', '2026-08-26', '2026-08-27', '2026-08-28', '2026-08-29' ) as $bhp_avp_day ) {
	$bhp_avp_walk[ $bhp_avp_day ] = array( 'listed' => false, 'open' => null, 'url' => null );
	foreach ( bhp_author_visits_build_rows( $fixture, $bhp_avp_day ) as $bhp_avp_r ) {
		if ( 'avptest-open' === $bhp_avp_r['slug'] ) {
			$bhp_avp_walk[ $bhp_avp_day ] = array( 'listed' => true, 'open' => $bhp_avp_r['open'], 'url' => $bhp_avp_r['url'] );
		}
	}
}

bhp_avp_assert( true === $bhp_avp_walk['2026-08-25']['open'], 'BOUNDARY visit-3 (the STATED deadline): open' );
bhp_avp_assert( true === $bhp_avp_walk['2026-08-26']['open'], '⭐ BOUNDARY visit-2: OPEN ALL DAY -- this is the grace day, one day past the stated deadline' );
bhp_avp_assert( '' !== $bhp_avp_walk['2026-08-26']['url'], 'BOUNDARY visit-2: and it still carries a live URL' );
bhp_avp_assert( false === $bhp_avp_walk['2026-08-27']['open'], '⛔ BOUNDARY visit-1: CLOSED from 00:00 that morning -- this is the sentence Andrew said' );
bhp_avp_assert( '' === $bhp_avp_walk['2026-08-27']['url'], '⛔ BOUNDARY visit-1: and the URL is GONE, not merely hidden' );
bhp_avp_assert( true === $bhp_avp_walk['2026-08-27']['listed'], '⭐ BOUNDARY visit-1: THE ROW IS STILL LISTED -- it keeps its place as a trust record of the read-aloud' );
bhp_avp_assert( false === $bhp_avp_walk['2026-08-28']['open'], 'BOUNDARY visit day: closed' );
bhp_avp_assert( true === $bhp_avp_walk['2026-08-28']['listed'], 'BOUNDARY visit day: still listed, so a parent at the door sees the visit is happening' );
bhp_avp_assert( false === $bhp_avp_walk['2026-08-29']['listed'], 'BOUNDARY visit+1: the row is GONE -- a past visit disappears' );

$rows_tomorrow = bhp_author_visits_build_rows( $fixture, '2026-08-18' );
$open_tomorrow = array();
foreach ( $rows_tomorrow as $row ) {
	$open_tomorrow[ $row['slug'] ] = $row['open'];
}
bhp_avp_assert( isset( $open_tomorrow['avptest-lastday'] ) && true === $open_tomorrow['avptest-lastday'], 'ONE DAY AFTER the stated cutoff, a far-off visit is STILL OPEN -- the boundary moved to the visit date' );
bhp_avp_assert( isset( $open_tomorrow['avptest-grace'] ) && false === $open_tomorrow['avptest-grace'], 'THE GRACE DAY IS ONE DAY LONG: the row that was open today is closed tomorrow, because tomorrow is its visit-1' );
bhp_avp_assert( ! isset( $open_tomorrow['avptest-todayvis'] ), 'A visit that happened yesterday has disappeared by the following day' );

/* --- the page and the plugin answer with ONE function -------------------- */

if ( function_exists( 'bhp_school_visit_is_open_on' ) ) {
	$bhp_avp_agree = true;
	foreach ( array( '2026-08-25', '2026-08-26', '2026-08-27', '2026-08-28' ) as $bhp_avp_day ) {
		if ( $bhp_avp_walk[ $bhp_avp_day ]['listed'] && bhp_school_visit_is_open_on( '2026-08-28', $bhp_avp_day ) !== $bhp_avp_walk[ $bhp_avp_day ]['open'] ) {
			$bhp_avp_agree = false;
			echo "  MISMATCH on {$bhp_avp_day}\n";
		}
	}
	bhp_avp_assert( $bhp_avp_agree, '⛔ THE ROW BUILDER ROUTES THROUGH THE PLUGIN: on every day of the boundary, the page row and bhp_school_visit_is_open_on() give the same answer -- the window is computed in ONE place' );
} else {
	bhp_avp_skip( 'Row builder routes through the plugin', 'the bundle plugin is not active on this install' );
}

/* --- missing-time tolerance --------------------------------------------- */

bhp_avp_assert( isset( $by_slug['avptest-notime'] ), 'A row with NO time is a COMPLETE row and is listed' );
bhp_avp_assert( isset( $by_slug['avptest-notime'] ) && '' === $by_slug['avptest-notime']['time'], 'A row with no time reports an EMPTY time, so the template renders the date alone' );
bhp_avp_assert( isset( $by_slug['avptest-open'] ) && '8:50 AM' === $by_slug['avptest-open']['time'], 'A time round-trips to the row exactly as seeded' );
bhp_avp_assert( isset( $by_slug['avptest-notime'] ) && '' !== $by_slug['avptest-notime']['url'], 'A row with no time still gets its order button -- the time is decoration, never a gate' );

/* --- ordering ------------------------------------------------------------ */

$order = array();
foreach ( $rows as $row ) {
	$order[] = $row['date'];
}
$sorted = $order;
sort( $sorted );
bhp_avp_assert( $order === $sorted, 'Rows are ordered SOONEST FIRST' );

/* --- display date -------------------------------------------------------- */

bhp_avp_assert( false !== strpos( bhp_author_visits_format_date( '2026-08-28' ), 'August' ), 'A date is rendered for a human ("August"), not as 2026-08-28' );
bhp_avp_assert( '' === bhp_author_visits_format_date( '' ), 'An empty date formats to an empty string rather than to today' );
bhp_avp_assert( 'not-a-date' === bhp_author_visits_format_date( 'not-a-date' ), 'An unparseable date falls back to the raw value rather than to a blank' );

/* =========================================================================
 * 2. THE LINK AND ITS UTMs -- these go onto printed QR codes
 * ====================================================================== */

$url = bhp_author_visits_shop_url( 'avptest-open' );

bhp_avp_assert( '' !== $url, 'A slug produces a URL' );
bhp_avp_assert( 0 === strpos( $url, 'http' ), 'The URL is absolute' );
bhp_avp_assert( false !== strpos( $url, '/shop/' ), 'The URL points at the shop page' );
bhp_avp_assert( false !== strpos( $url, 'bhp_visit=avptest-open' ), 'The URL carries the visit param -- this is the only part that changes behaviour' );
bhp_avp_assert( false !== strpos( $url, 'utm_campaign=visit-avptest-open' ), 'The URL carries utm_campaign=visit-<slug>' );
bhp_avp_assert( false !== strpos( $url, 'utm_source=author-visits' ), 'The URL carries a utm_source (a campaign with no source is (not set) in GA4)' );
bhp_avp_assert( false !== strpos( $url, 'utm_medium=onsite' ), 'The URL carries a utm_medium' );
bhp_avp_assert( '' === bhp_author_visits_shop_url( '' ), 'An empty slug produces no URL' );
bhp_avp_assert( false === strpos( bhp_author_visits_shop_url( '../../etc/passwd' ), '..' ), 'A path-traversal-shaped slug is sanitised out of the URL' );

$row_url = isset( $by_slug['avptest-open']['url'] ) ? $by_slug['avptest-open']['url'] : '';
bhp_avp_assert( $row_url === $url, 'The row builder uses the same URL builder -- there is one place a visit link is constructed' );

/* =========================================================================
 * 2b. ⭐⭐ THE BOUNDARY AGAINST THE **REAL** VISITS -- READ-ONLY.
 *
 * ⛔ THIS BLOCK WRITES NOTHING. It reads the live registry and runs the pure
 *    row builder against it on three chosen days per visit. §3 below is the
 *    block that seeds and restores; this one runs BEFORE it, so the rows it
 *    sees are the operator's own.
 *
 * ⭐ WHY BOTH: §1 proves the RULE with fixtures. This proves the rule is true
 *    of the visits Andrew is actually driving to -- rows that are hand-entered,
 *    printed on QR codes and taped to classroom doors. No real slug, school or
 *    date is typed into this file; every label is built at run time, which §4
 *    then enforces structurally.
 * ====================================================================== */

if ( ! function_exists( 'bhp_school_visit_records' ) ) {
	bhp_avp_skip( 'Real-visit boundary', 'the bundle plugin is not active on this install, so there is no registry to read' );
} else {
	$bhp_avp_real = bhp_school_visit_records();
	foreach ( $bhp_avp_real as $bhp_avp_slug => $bhp_avp_row ) {
		if ( 0 === strpos( (string) $bhp_avp_slug, 'avptest' ) || 0 === strpos( (string) $bhp_avp_slug, 'bhpsvptest-' ) ) {
			continue; // a fixture left behind by a killed run is not a real visit
		}
		$bhp_avp_visit = $bhp_avp_row['date'];
		$bhp_avp_last  = function_exists( 'bhp_school_visit_last_order_date' ) ? bhp_school_visit_last_order_date( $bhp_avp_visit ) : '';
		$bhp_avp_close = function_exists( 'bhp_school_visit_online_close_date' ) ? bhp_school_visit_online_close_date( $bhp_avp_visit ) : '';
		if ( '' === $bhp_avp_last || '' === $bhp_avp_close ) {
			bhp_avp_skip( "Real-visit boundary [{$bhp_avp_slug}]", 'the window helpers are unavailable or this row has an unusable date' );
			continue;
		}
		$bhp_avp_tag = "[{$bhp_avp_slug} visit={$bhp_avp_visit} stated={$bhp_avp_row['cutoff']} last={$bhp_avp_last} close={$bhp_avp_close}]";
		$bhp_avp_at  = array();
		foreach ( array( $bhp_avp_last, $bhp_avp_close, $bhp_avp_visit ) as $bhp_avp_day ) {
			$bhp_avp_at[ $bhp_avp_day ] = array( 'listed' => false, 'open' => null, 'url' => null, 'cutoff' => null );
			foreach ( bhp_author_visits_build_rows( $bhp_avp_real, $bhp_avp_day ) as $bhp_avp_r ) {
				if ( $bhp_avp_slug === $bhp_avp_r['slug'] ) {
					$bhp_avp_at[ $bhp_avp_day ] = array( 'listed' => true, 'open' => $bhp_avp_r['open'], 'url' => $bhp_avp_r['url'], 'cutoff' => $bhp_avp_r['cutoff'] );
				}
			}
		}
		bhp_avp_assert( true === $bhp_avp_at[ $bhp_avp_last ]['open'], "{$bhp_avp_tag} REAL VISIT: the button is LIVE on visit-2" );
		bhp_avp_assert( '' !== (string) $bhp_avp_at[ $bhp_avp_last ]['url'], "{$bhp_avp_tag} REAL VISIT: with a real order URL on visit-2" );
		bhp_avp_assert( false === $bhp_avp_at[ $bhp_avp_close ]['open'], "⛔ {$bhp_avp_tag} REAL VISIT: the button is GREY on visit-1" );
		bhp_avp_assert( '' === (string) $bhp_avp_at[ $bhp_avp_close ]['url'], "⛔ {$bhp_avp_tag} REAL VISIT: and carries no URL on visit-1" );
		bhp_avp_assert( true === $bhp_avp_at[ $bhp_avp_close ]['listed'], "⭐ {$bhp_avp_tag} REAL VISIT: the row STAYS on the page on visit-1" );
		bhp_avp_assert( false === $bhp_avp_at[ $bhp_avp_visit ]['open'], "⛔ {$bhp_avp_tag} REAL VISIT: closed on the day of the visit" );
		bhp_avp_assert( true === $bhp_avp_at[ $bhp_avp_visit ]['listed'], "{$bhp_avp_tag} REAL VISIT: and still listed on the day of the visit" );
		bhp_avp_assert( $bhp_avp_row['cutoff'] === $bhp_avp_at[ $bhp_avp_close ]['cutoff'], "⛔ {$bhp_avp_tag} REAL VISIT: the STATED deadline is still what the closed row prints -- unchanged, and never replaced by the real close date" );
	}
}

/* =========================================================================
 * 3. ⛔ THE PAGE AND THE CHECKOUT MUST AGREE ON THE SAME DAY
 *
 * The one assertion that protects a real parent. Seeded against the LIVE
 * registry option and the LIVE resolver, then restored.
 * ====================================================================== */

if ( ! function_exists( 'bhp_school_visit_records' ) || ! function_exists( 'bhp_school_visit_resolve' ) || ! defined( 'BHP_SCHOOL_VISIT_OPTION' ) ) {
	bhp_avp_skip( 'Page/checkout agreement', 'the bundle plugin is not active on this install, so there is no resolver to compare against' );
} else {
	$GLOBALS['bhp_avp_option_snapshot'] = get_option( BHP_SCHOOL_VISIT_OPTION, false );
	$GLOBALS['bhp_avp_option_seeded']   = true;

	$live_today = bhp_author_visits_today();
	$tomorrow   = wp_date( 'Y-m-d', strtotime( '+1 day' ) );
	$yesterday  = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
	$next_month = wp_date( 'Y-m-d', strtotime( '+30 days' ) );
	$day_after  = wp_date( 'Y-m-d', strtotime( '+2 days' ) );

	/*
	 * ⛔ MERGE, NEVER REPLACE -- CHANGED AT 1.19.239. The superseded call
	 *    passed a bare array() and REPLACED the operator's registry for the
	 *    duration of the run, so a process killed between here and cleanup
	 *    lost the real visits. That is exactly the 2026-08-17 incident recorded
	 *    in the bundle plugin's own suite. The snapshot/restore below is
	 *    unchanged and still runs on every exit path; this makes the window in
	 *    which it matters survivable instead of fatal. A fixture slug cannot
	 *    collide with a real one -- no operator names a school
	 *    `avptestlive-open`.
	 *
	 * ⭐ THE FIXTURES ALSO MOVED. They used to differ only in `cutoff`, with
	 *    every DATE a month out, so after 1.8.56 all three would have been open
	 *    and the agreement assertion would have passed without ever testing a
	 *    closed row. They now straddle the real boundary.
	 */
	$bhp_avp_seed_base = is_array( $GLOBALS['bhp_avp_option_snapshot'] ) ? $GLOBALS['bhp_avp_option_snapshot'] : array();
	update_option(
		BHP_SCHOOL_VISIT_OPTION,
		$bhp_avp_seed_base + array(
			// Visit a month out: open on both sides.
			'avptestlive-open'    => array( 'school' => 'Agreement Open School', 'date' => $next_month, 'cutoff' => $tomorrow, 'time' => '8:50 AM' ),
			// ⭐ The stated deadline is TODAY, the visit is a month out: the
			//    stated deadline gates nothing, so this must be OPEN on both sides.
			'avptestlive-lastday' => array( 'school' => 'Agreement Lastday School', 'date' => $next_month, 'cutoff' => $live_today ),
			// ⛔ The visit is TOMORROW, so today is visit-1: CLOSED on both
			//    sides. This is the row that makes the agreement assertion mean
			//    something.
			'avptestlive-closed'  => array( 'school' => 'Agreement Closed School', 'date' => $tomorrow, 'cutoff' => $yesterday, 'time' => '9:00 AM' ),
			// ⭐ The stated deadline passed YESTERDAY and the visit is in two
			//    days: the grace day, OPEN on both sides.
			'avptestlive-grace'   => array( 'school' => 'Agreement Grace School', 'date' => $day_after, 'cutoff' => $yesterday ),
		)
	);

	$live_records = bhp_school_visit_records();
	$live_rows    = bhp_author_visits_build_rows( $live_records, $live_today );
	$live_by_slug = array();
	foreach ( $live_rows as $row ) {
		$live_by_slug[ $row['slug'] ] = $row;
	}

	$agree = true;
	foreach ( array( 'avptestlive-open', 'avptestlive-lastday', 'avptestlive-closed', 'avptestlive-grace' ) as $slug ) {
		$page_says     = ! empty( $live_by_slug[ $slug ]['open'] );
		$checkout_says = ( null !== bhp_school_visit_resolve( $slug ) );
		if ( $page_says !== $checkout_says ) {
			$agree = false;
			echo "  MISMATCH on {$slug}: page open={$page_says}, resolver open={$checkout_says}\n";
		}
	}
	bhp_avp_assert( $agree, '⛔ THE BUTTON AND THE ENTITLEMENT AGREE: for every seeded visit, "the page shows an order button" and "the site would grant hand-delivery" give the same answer on the same day' );
	bhp_avp_assert( isset( $live_by_slug['avptestlive-closed'] ) && false === $live_by_slug['avptestlive-closed']['open'], '⛔ AND THE AGREEMENT IS NOT VACUOUS: at least one seeded row is genuinely CLOSED on both sides (visit tomorrow = visit-1)' );
	bhp_avp_assert( isset( $live_by_slug['avptestlive-grace'] ) && true === $live_by_slug['avptestlive-grace']['open'], '⭐ AND ONE IS GENUINELY IN THE GRACE WINDOW on both sides (stated deadline yesterday, visit in two days)' );

	bhp_avp_assert( isset( $live_records['avptestlive-open'] ) && '8:50 AM' === $live_records['avptestlive-open']['time'], 'THE REGISTRY carries the new time field through the plugin sanitiser' );
	bhp_avp_assert( isset( $live_records['avptestlive-lastday'] ) && '' === $live_records['avptestlive-lastday']['time'], 'A registry row with NO time survives sanitisation and reports an empty time -- rows seeded before 1.8.51 are not dropped' );
	bhp_avp_assert( isset( $live_records['avptestlive-lastday'] ) && 'Agreement Lastday School' === $live_records['avptestlive-lastday']['school'], 'A row with no time keeps every other field intact' );

	if ( function_exists( 'bhp_school_visit_sanitize_time' ) ) {
		bhp_avp_assert( '' === bhp_school_visit_sanitize_time( array( 'x' ) ), 'The time sanitiser refuses a non-scalar' );
		bhp_avp_assert( false === strpos( bhp_school_visit_sanitize_time( '<script>alert(1)</script>8:50 AM' ), '<' ), 'The time sanitiser strips tags -- the value is echoed to a public page' );
		bhp_avp_assert( 'right after lunch' === bhp_school_visit_sanitize_time( "  right   after\nlunch " ), 'The time sanitiser collapses whitespace and accepts a plain-English time, because "8:50 AM" is not the only legitimate value' );
		bhp_avp_assert( strlen( bhp_school_visit_sanitize_time( str_repeat( 'x', 500 ) ) ) <= BHP_SCHOOL_VISIT_TIME_MAXLEN, 'The time sanitiser caps length -- a time, not a paragraph' );
	} else {
		bhp_avp_skip( 'Time sanitiser', 'bhp_school_visit_sanitize_time() is not defined on this install' );
	}

	// The live reader must survive being called for real.
	$live_reader_rows = bhp_author_visits_rows();
	bhp_avp_assert( is_array( $live_reader_rows ), 'bhp_author_visits_rows() returns an array against the real option' );

	bhp_avp_cleanup();
}

/* =========================================================================
 * 4. STRUCTURAL — no hardcoded visit data, and the template is a template
 * ====================================================================== */

$inc_path  = get_template_directory() . '/inc/author-visits.php';
$tpl_path  = get_template_directory() . '/page-author-visits.php';
$inc_src   = file_exists( $inc_path ) ? file_get_contents( $inc_path ) : '';
$tpl_src   = file_exists( $tpl_path ) ? file_get_contents( $tpl_path ) : '';

bhp_avp_assert( '' !== $inc_src, 'inc/author-visits.php is readable for the structural audit' );
bhp_avp_assert( '' !== $tpl_src, 'page-author-visits.php is readable for the structural audit' );

if ( '' !== $tpl_src ) {
	bhp_avp_assert( 1 === preg_match( '/^\s*\*\s*Template Name:\s*Author Visits\s*$/m', $tpl_src ), 'The template declares "Template Name: Author Visits" so it can be assigned to a page' );
	bhp_avp_assert( false === strpos( $tpl_src, 'application/ld+json' ), 'NO structured data is emitted by the template' );
	bhp_avp_assert( false === strpos( $tpl_src, 'rank_math' ), 'The template registers no Rank Math schema filter' );

	/* ⭐ 1.19.239 -- THE GREYED CONTROL IS DEAD IN FACT, NOT ONLY IN LOOK. */
	bhp_avp_assert( 1 === preg_match( '/<span[^>]*author-visits-card__btn--closed[^>]*>/', $tpl_src ), '⛔ THE CLOSED BUTTON IS A <span>, and the markup says so' );
	bhp_avp_assert( ! preg_match( '/<a[^>]*author-visits-card__btn--closed/', $tpl_src ), '⛔ IT IS NOT AN <a>: there is no anchor carrying the closed-button class, so nothing can be middle-clicked, copied as a link address or followed' );
	bhp_avp_assert( ! preg_match( '/author-visits-card__btn--closed[^>]*href/', $tpl_src ), '⛔ IT CARRIES NO href' );
	bhp_avp_assert( ! preg_match( '/author-visits-card__btn--closed[^>]*tabindex/', $tpl_src ), '⛔ IT CARRIES NO tabindex: a <span> without one is not focusable, so it is out of the keyboard tab order' );
	bhp_avp_assert( 1 === preg_match( '/author-visits-card__btn--closed[^>]*aria-disabled="true"/', $tpl_src ), '♿ IT IS ANNOUNCED AS UNAVAILABLE: aria-disabled="true" on the control assistive technology will read' );
	bhp_avp_assert( 1 === preg_match( '/author-visits-card__btn--closed[^>]*role="link"/', $tpl_src ), '♿ AND IT IS ANNOUNCED AS A LINK-SHAPED THING, which is what it looks like' );
	/* ⚠ SCOPED TO MARKUP, NOT TO THE WHOLE FILE, AND DELIBERATELY: the
	   template's own comment legitimately contains the string `onclick` while
	   explaining what the control is NOT. A whole-file check would fail forever
	   or force the comment to be dishonestly reworded. */
	bhp_avp_assert( ! preg_match( '/<[a-z][^>]*\sonclick=/i', $tpl_src ), '⛔ NO onclick ATTRIBUTE ON ANY ELEMENT: the control is not disarmed by JavaScript, which would leave it live for anyone with JS off' );
}

$both_src = $inc_src . "\n" . $tpl_src;
if ( '' !== $both_src ) {
	bhp_avp_assert( ! preg_match( '/adams-20\d\d|dallas-harris|liberty-20\d\d/i', $both_src ), 'NO real visit slug appears anywhere in the page source' );
	bhp_avp_assert( ! preg_match( '/[\'"]school[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/', $both_src ), 'NO literal visit row exists in the page source -- the registry is data, never code' );
	bhp_avp_assert( ! preg_match( '/\b\d{1,2}:\d{2}\s*(AM|PM)\b/i', $both_src ), 'NO literal clock time is hardcoded -- every time comes from the registry' );
	bhp_avp_assert( ! preg_match( '/\bElementary\b/', $both_src ), 'NO school name is hardcoded' );
}

/* =========================================================================
 * 5. THE CUSTOMER-FACING COPY — the standing content constraints
 *
 * ⛔ ASSERTED AGAINST THE TRANSLATABLE STRINGS ONLY, never the whole file.
 *    The file's own comments legitimately contain em dashes and the word "we"
 *    (they quote Andrew's instruction), and a whole-file check would either
 *    fail forever or force the comments to be dishonestly reworded.
 * ====================================================================== */

$copy = array();
if ( '' !== $tpl_src && preg_match_all( '/(?:esc_html_e|esc_html__|__)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $tpl_src, $m ) ) {
	$copy = $m[1];
}
$copy_blob = implode( ' ', $copy );

bhp_avp_assert( count( $copy ) >= 10, 'The template copy was extracted for auditing (' . count( $copy ) . ' translatable strings found)' );
bhp_avp_assert( false === strpos( $copy_blob, '—' ), 'NO em dash in any customer-facing string' );
bhp_avp_assert( false === strpos( $copy_blob, '–' ), 'NO en dash in any customer-facing string' );
bhp_avp_assert( ! preg_match( '/\b(we|us|our|ours|we’re|we\'re)\b/i', $copy_blob ), 'FOUNDER VOICE: no "we", "us" or "our" anywhere in the copy -- Andrew speaks as I/me' );
bhp_avp_assert( ! preg_match( '/\b5\s*[-–—]\s*9\b/', $copy_blob ), 'The reading age is never stated as 5-9' );
bhp_avp_assert( ! preg_match( '/\b(best|favou?rite|loved|beloved|proven|award|bestsell|#1|thousands of|parents say|teachers say)\b/i', $copy_blob ), 'NO superlative, award, ranking, sales figure or reader-reaction claim appears in the copy' );
bhp_avp_assert( ! preg_match( '/\b(reading level|lexile|grade level)\b/i', $copy_blob ), 'NO reading-level claim appears in the copy' );
bhp_avp_assert( ! preg_match( '/\$\s?\d/', $copy_blob ), 'NO price appears in the copy -- prices drift and this page is destined for print' );
bhp_avp_assert( false !== strpos( $copy_blob, 'Order signed books for this visit' ), 'The order button carries the briefed label' );
bhp_avp_assert( false !== strpos( $copy_blob, 'Ordering for this visit has closed.' ), 'The closed state says so in words (kept for screen readers beside the greyed control)' );
bhp_avp_assert( false !== strpos( $copy_blob, 'Ordering closed' ), '1.19.239: the GREYED BUTTON carries a label of its own' );
bhp_avp_assert( false !== strpos( $copy_blob, 'Order by %s.' ), '⛔ 1.19.239: THE CLOSED ROW STILL PRINTS THE STATED DEADLINE -- "Order by ...", the date parents were emailed' );
bhp_avp_assert( ! preg_match( '/\b(grace|extra day|one more day|sneak|late order|really closes|actually closes|day before the visit|secretly)\b/i', $copy_blob ), '⛔⛔ THE GRACE WINDOW IS NEVER ADVERTISED: no customer-facing string on this page hints that ordering runs past the stated deadline. A page that advertises a secret extension has no deadline at all' );

/* =========================================================================
 * 6. THE STYLESHEET CARRIES THE PAGE'S CLASSES
 * ====================================================================== */

$css_path = get_template_directory() . '/style.css';
$min_path = get_template_directory() . '/style.min.css';
$css_src  = file_exists( $css_path ) ? file_get_contents( $css_path ) : '';
$min_src  = file_exists( $min_path ) ? file_get_contents( $min_path ) : '';

bhp_avp_assert( false !== strpos( $css_src, '.author-visits-card' ), 'style.css carries the page\'s scoped rules' );
bhp_avp_assert( '' === $min_src || false !== strpos( $min_src, '.author-visits-card' ), 'style.min.css -- the artefact actually served -- was REBUILT after the CSS change' );
bhp_avp_assert( false !== strpos( $css_src, '.author-visits-card__btn--closed' ), '1.19.239: style.css carries the greyed-button rule' );
bhp_avp_assert( '' === $min_src || false !== strpos( $min_src, '.author-visits-card__btn--closed' ), '⛔ 1.19.239: AND style.min.css CARRIES IT TOO -- the minified file is the one the browser loads, and a stale one would ship a gold button that does nothing' );
bhp_avp_assert( false !== strpos( $css_src, 'not-allowed' ), '1.19.239: the greyed button is cursor: not-allowed, so it feels dead under the pointer' );

bhp_avp_finish();
