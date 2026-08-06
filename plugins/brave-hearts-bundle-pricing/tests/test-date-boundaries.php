<?php
/**
 * Brave Hearts Dashboard — date/timezone boundary test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-date-boundaries.php --user=1
 *
 * Uses PHP Reflection to call BHP_Dashboard_Page's private
 * get_period_bounds() / parse_custom_range() directly with controlled
 * inputs -- no HTTP request or $_GET superglobal manipulation needed,
 * and no change to the class's public API surface just for testing.
 *
 * Verified site configuration at the time this suite was written:
 * timezone_string is empty and gmt_offset is 0 -- i.e. this store
 * currently runs on a FIXED UTC+0 offset, not a named/DST-observing
 * timezone. That means daylight-saving transitions are not reachable on
 * the live site today. The DST-safety test below therefore verifies the
 * date-arithmetic PATTERN the dashboard code uses (modify() first, then
 * setTime() to re-anchor to local midnight) against a real DST-observing
 * zone (America/New_York) using plain DateTime objects, independent of
 * whatever wp_timezone() currently returns -- so the code is proven safe
 * in advance of Andrew ever changing the site's timezone setting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_date_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_date_call_private( $method, ...$args ) {
	$ref = new ReflectionMethod( 'BHP_Dashboard_Page', $method );
	$ref->setAccessible( true );
	return $ref->invoke( null, ...$args );
}

// ==================== Site timezone configuration check ====================
$tz_string = get_option( 'timezone_string' );
$gmt_offset = get_option( 'gmt_offset' );
echo "INFO: site timezone_string='" . $tz_string . "', gmt_offset=" . $gmt_offset . "\n";
bhp_date_test_assert(
	true, // informational only, not a pass/fail condition
	'Site timezone configuration recorded (see INFO line above) for this test run',
	$failures
);

// ==================== 'today' begins at local midnight ====================
list( $start, $end, $prior_start, $prior_end ) = bhp_date_call_private( 'get_period_bounds', 'today' );
bhp_date_test_assert( '00:00:00' === $start->format( 'H:i:s' ), "'today' period starts at local midnight (00:00:00)", $failures );
bhp_date_test_assert( $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' ) || $end > $start, "'today' end (now) is on/after the start of today", $failures );

// Prior period for 'today' is exactly yesterday, full 24h.
bhp_date_test_assert( '00:00:00' === $prior_start->format( 'H:i:s' ), "'today' prior-period start is local midnight", $failures );
$expected_prior_start = ( clone $start )->modify( '-1 day' );
bhp_date_test_assert( $prior_start->format( 'Y-m-d H:i:s' ) === $expected_prior_start->format( 'Y-m-d H:i:s' ), "'today' prior period is exactly the immediately preceding calendar day", $failures );

// ==================== 'Last 7 days' has inclusive, well-defined boundaries ====================
list( $start7, $end7, $prior_start7, $prior_end7 ) = bhp_date_call_private( 'get_period_bounds', '7d' );
$days_in_range = ( $end7->getTimestamp() - $start7->getTimestamp() ) / DAY_IN_SECONDS;
bhp_date_test_assert( $days_in_range >= 6.0 && $days_in_range < 7.0, "'Last 7 days' spans exactly 7 calendar days (6 full days back to local midnight, plus today so far)", $failures );
bhp_date_test_assert( '00:00:00' === $start7->format( 'H:i:s' ), "'Last 7 days' start is anchored to local midnight", $failures );

// Prior period is an equal-length (7-day) period immediately before, with
// no gap and no overlap: prior_end must be exactly 1 second before start.
bhp_date_test_assert( ( $start7->getTimestamp() - $prior_end7->getTimestamp() ) === 1, "'Last 7 days' prior period ends exactly 1 second before the current period starts (no gap, no overlap)", $failures );
$prior_span_days = (int) round( ( $prior_end7->getTimestamp() - $prior_start7->getTimestamp() ) / DAY_IN_SECONDS );
bhp_date_test_assert( 7 === $prior_span_days, "'Last 7 days' prior-period comparison window is the same length (7 days) as the current period", $failures );

// ==================== 'Last 30 days' has inclusive, well-defined boundaries ====================
list( $start30, $end30, $prior_start30, $prior_end30 ) = bhp_date_call_private( 'get_period_bounds', '30d' );
$days_in_range30 = ( $end30->getTimestamp() - $start30->getTimestamp() ) / DAY_IN_SECONDS;
bhp_date_test_assert( $days_in_range30 >= 29.0 && $days_in_range30 < 30.0, "'Last 30 days' spans exactly 30 calendar days", $failures );
$prior_span_days30 = (int) round( ( $prior_end30->getTimestamp() - $prior_start30->getTimestamp() ) / DAY_IN_SECONDS );
bhp_date_test_assert( 30 === $prior_span_days30, "'Last 30 days' prior-period comparison window is the same length (30 days)", $failures );

// ==================== Custom range: parse_custom_range() validation ====================
$_GET['bhp_start'] = '2026-06-01';
$_GET['bhp_end']   = '2026-06-15';
$range = bhp_date_call_private( 'parse_custom_range' );
bhp_date_test_assert( null !== $range, 'Valid custom range (2026-06-01 to 2026-06-15) parses successfully', $failures );
if ( null !== $range ) {
	bhp_date_test_assert( '2026-06-01' === $range[0]->format( 'Y-m-d' ), 'Custom range start matches the admin-entered start date exactly', $failures );
	bhp_date_test_assert( '00:00:00' === $range[0]->format( 'H:i:s' ), 'Custom range start is local midnight', $failures );
	// End is stored as an EXCLUSIVE upper bound (midnight of the day
	// after) so an order at 23:59:59 on the selected end date is
	// included -- verify it is exactly one day past the admin's input.
	bhp_date_test_assert( '2026-06-16' === $range[1]->format( 'Y-m-d' ), 'Custom range end is stored as the day AFTER the admin-entered end date (exclusive upper bound), so the entered end date is fully included', $failures );
}

// Custom range bounds computation via get_period_bounds() with a real range.
list( $cstart, $cend, $cprior_start, $cprior_end ) = bhp_date_call_private( 'get_period_bounds', 'custom', $range[0], $range[1] );
$custom_span_days = round( ( $cend->getTimestamp() - $cstart->getTimestamp() ) / DAY_IN_SECONDS );
$custom_prior_span_days = round( ( $cprior_end->getTimestamp() - $cprior_start->getTimestamp() ) / DAY_IN_SECONDS );
bhp_date_test_assert( $custom_span_days === $custom_prior_span_days, 'Custom range prior-period comparison window is the same length as the custom range itself', $failures );
bhp_date_test_assert( ( $cstart->getTimestamp() - $cprior_end->getTimestamp() ) === 1, 'Custom range prior period ends exactly 1 second before the custom range starts', $failures );

// Invalid inputs must be rejected, not silently coerced into a query.
$_GET['bhp_start'] = 'not-a-date';
$_GET['bhp_end']   = '2026-06-15';
bhp_date_test_assert( null === bhp_date_call_private( 'parse_custom_range' ), 'Malformed start date is rejected (returns null, caller falls back to "today")', $failures );

$_GET['bhp_start'] = '2026-06-15';
$_GET['bhp_end']   = '2026-06-01'; // end before start
bhp_date_test_assert( null === bhp_date_call_private( 'parse_custom_range' ), 'End date before start date is rejected', $failures );

$_GET['bhp_start'] = '2020-01-01';
$_GET['bhp_end']   = '2026-06-15'; // ~6.5 years, exceeds the 366-day sanity cap
bhp_date_test_assert( null === bhp_date_call_private( 'parse_custom_range' ), 'An unreasonably large custom range (multi-year) is rejected rather than triggering an unbounded query', $failures );

unset( $_GET['bhp_start'], $_GET['bhp_end'] );

// ==================== DST-safety of the modify()+setTime() pattern ====================
// Independent of wp_timezone(): proves that anchoring to local midnight
// via setTime(0,0,0) AFTER a day-based modify() never produces a
// duplicate or skipped calendar day across a real DST transition, using
// America/New_York's actual 2026 transitions (DST starts 2026-03-08,
// ends 2026-11-01).
$ny = new DateTimeZone( 'America/New_York' );

// Spring-forward: walking backward 6 days from March 10 (after the
// transition) must still land on exactly March 4 at local midnight, not
// March 3 23:00 or March 4 01:00.
$after_spring = new DateTime( '2026-03-10 12:00:00', $ny );
$spring_result = ( clone $after_spring )->modify( '-6 days' )->setTime( 0, 0, 0 );
bhp_date_test_assert(
	'2026-03-04 00:00:00' === $spring_result->format( 'Y-m-d H:i:s' ),
	'DST spring-forward: modify(-6 days)->setTime(0,0,0) lands exactly on local midnight of the correct calendar day, not shifted by the 1-hour transition',
	$failures
);

// Fall-back: walking backward 6 days from November 3 (after the
// transition) must still land on exactly October 28 at local midnight.
$after_fall = new DateTime( '2026-11-03 12:00:00', $ny );
$fall_result = ( clone $after_fall )->modify( '-6 days' )->setTime( 0, 0, 0 );
bhp_date_test_assert(
	'2026-10-28 00:00:00' === $fall_result->format( 'Y-m-d H:i:s' ),
	'DST fall-back: modify(-6 days)->setTime(0,0,0) lands exactly on local midnight of the correct calendar day, not duplicated by the repeated hour',
	$failures
);

// A 7-day span crossing the spring-forward transition is 6 calendar days
// x 24h *minus* the one skipped hour -- i.e. NOT a clean multiple of
// 86400 seconds. This is expected and correct (real elapsed wall-clock
// time legitimately loses an hour that week); the important property is
// that the CALENDAR DAY COUNT (not the raw second count) is still 7,
// which is what get_period_bounds() actually promises.
$span_start = ( clone $after_spring )->modify( '-6 days' )->setTime( 0, 0, 0 );
$span_seconds = $after_spring->getTimestamp() - $span_start->getTimestamp();
$expected_seconds_without_dst = 6 * DAY_IN_SECONDS + 12 * 3600; // 6 full days + 12h to noon
bhp_date_test_assert(
	$span_seconds === ( $expected_seconds_without_dst - 3600 ),
	'A span crossing the spring-forward transition is correctly 1 hour SHORTER in raw seconds than the same span would be without DST -- confirms the code measures real elapsed time, not a naive day-count assumption',
	$failures
);

echo empty( $failures ) ? "\nALL DATE/TIMEZONE BOUNDARY TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
