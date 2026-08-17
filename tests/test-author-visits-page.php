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
 *   2. ⛔ A PAST VISIT MUST DISAPPEAR, and a cutoff-passed-but-not-yet-visited
 *      visit must NOT disappear. Two different dates, two different questions.
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
	// Cutoff is TODAY. Inclusive, so ordering is still open.
	'avptest-lastday'   => array( 'slug' => 'avptest-lastday', 'school' => 'Last Day Test School', 'date' => '2026-09-10', 'cutoff' => '2026-08-17', 'time' => '10:10 AM' ),
	// Cutoff passed YESTERDAY, visit still to come. Listed, no button.
	'avptest-closed'    => array( 'slug' => 'avptest-closed', 'school' => 'Closed Test School', 'date' => '2026-08-20', 'cutoff' => '2026-08-16', 'time' => '9:00 AM' ),
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

bhp_avp_assert( isset( $by_slug['avptest-closed'] ) && false === $by_slug['avptest-closed']['open'], 'A past-cutoff visit is marked open=false' );
bhp_avp_assert( isset( $by_slug['avptest-closed'] ) && '' === $by_slug['avptest-closed']['url'], 'A past-cutoff visit carries NO URL AT ALL -- a template cannot render a link that does not exist' );
bhp_avp_assert( isset( $by_slug['avptest-todayvis'] ) && false === $by_slug['avptest-todayvis']['open'], 'A visit happening today whose cutoff has passed is listed but closed to ordering' );

/* --- the inclusive cutoff ----------------------------------------------- */

bhp_avp_assert( isset( $by_slug['avptest-lastday'] ) && true === $by_slug['avptest-lastday']['open'], 'THE CUTOFF IS INCLUSIVE: on the cutoff date itself, ordering is still open' );
bhp_avp_assert( isset( $by_slug['avptest-lastday'] ) && '' !== $by_slug['avptest-lastday']['url'], 'The last-day row still carries an order URL' );

$rows_tomorrow = bhp_author_visits_build_rows( $fixture, '2026-08-18' );
$open_tomorrow = array();
foreach ( $rows_tomorrow as $row ) {
	$open_tomorrow[ $row['slug'] ] = $row['open'];
}
bhp_avp_assert( isset( $open_tomorrow['avptest-lastday'] ) && false === $open_tomorrow['avptest-lastday'], 'ONE DAY AFTER the cutoff, the same row is closed -- the boundary moves with the date, not with a deploy' );
bhp_avp_assert( ! isset( $open_tomorrow['avptest-todayvis'] ), 'A visit that happened yesterday has disappeared by the following day' );

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

	update_option(
		BHP_SCHOOL_VISIT_OPTION,
		array(
			'avptestlive-open'    => array( 'school' => 'Agreement Open School', 'date' => $next_month, 'cutoff' => $tomorrow, 'time' => '8:50 AM' ),
			'avptestlive-lastday' => array( 'school' => 'Agreement Lastday School', 'date' => $next_month, 'cutoff' => $live_today ),
			'avptestlive-closed'  => array( 'school' => 'Agreement Closed School', 'date' => $next_month, 'cutoff' => $yesterday, 'time' => '9:00 AM' ),
		)
	);

	$live_records = bhp_school_visit_records();
	$live_rows    = bhp_author_visits_build_rows( $live_records, $live_today );
	$live_by_slug = array();
	foreach ( $live_rows as $row ) {
		$live_by_slug[ $row['slug'] ] = $row;
	}

	$agree = true;
	foreach ( array( 'avptestlive-open', 'avptestlive-lastday', 'avptestlive-closed' ) as $slug ) {
		$page_says     = ! empty( $live_by_slug[ $slug ]['open'] );
		$checkout_says = ( null !== bhp_school_visit_resolve( $slug ) );
		if ( $page_says !== $checkout_says ) {
			$agree = false;
			echo "  MISMATCH on {$slug}: page open={$page_says}, resolver open={$checkout_says}\n";
		}
	}
	bhp_avp_assert( $agree, '⛔ THE BUTTON AND THE ENTITLEMENT AGREE: for every seeded visit, "the page shows an order button" and "the site would grant hand-delivery" give the same answer on the same day' );

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
bhp_avp_assert( false !== strpos( $copy_blob, 'Ordering for this visit has closed.' ), 'The closed state says so in words' );

/* =========================================================================
 * 6. THE STYLESHEET CARRIES THE PAGE'S CLASSES
 * ====================================================================== */

$css_path = get_template_directory() . '/style.css';
$min_path = get_template_directory() . '/style.min.css';
$css_src  = file_exists( $css_path ) ? file_get_contents( $css_path ) : '';
$min_src  = file_exists( $min_path ) ? file_get_contents( $min_path ) : '';

bhp_avp_assert( false !== strpos( $css_src, '.author-visits-card' ), 'style.css carries the page\'s scoped rules' );
bhp_avp_assert( '' === $min_src || false !== strpos( $min_src, '.author-visits-card' ), 'style.min.css -- the artefact actually served -- was REBUILT after the CSS change' );

bhp_avp_finish();
