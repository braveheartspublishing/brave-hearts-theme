<?php
/**
 * Phase 1C — BHP_Lead_Event_Log test suite.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-lead-event-log.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * Exercises the real hook path (bhp_mailchimp_signup_success /
 * bhp_mailchimp_signup_failed) with synthetic +bhptest addresses, never
 * a real Mailchimp API call (this test never calls
 * bhp_handle_mailchimp_signup() itself, only fires the two actions it
 * already fires on success/failure -- exactly what a real signup would
 * trigger, without needing a live MC4WP connection).
 *
 * Cleanup: a direct call at the end of this script (bhp_lel_cleanup())
 * is the PRIMARY cleanup path -- register_shutdown_function() is kept
 * only as a secondary safety net for a genuinely aborted run, but it has
 * now been observed twice (once with an inline closure, once with a
 * named function referencing $GLOBALS) to NOT reliably fire inside
 * `wp eval-file`'s execution context. Never rely on it alone; always
 * also call the cleanup function directly once the script's real work
 * is done.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$GLOBALS['bhp_lel_created_post_ids'] = array();

function bhp_lel_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_lel_cleanup() {
	foreach ( $GLOBALS['bhp_lel_created_post_ids'] as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}
register_shutdown_function( 'bhp_lel_cleanup' );

if ( ! class_exists( 'BHP_Lead_Event_Log' ) ) {
	bhp_lel_test_assert( false, 'BHP_Lead_Event_Log class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Provenance classification ====================
bhp_lel_test_assert(
	BHP_Lead_Event_Log::PROVENANCE_TEST === BHP_Lead_Event_Log::classify_provenance( 'jane+bhptest@example.com' ),
	'An email using the +bhptest marker is classified as test provenance',
	$failures
);
bhp_lel_test_assert(
	BHP_Lead_Event_Log::PROVENANCE_TEST === BHP_Lead_Event_Log::classify_provenance( 'someone@bhptest.invalid' ),
	'An email on the @bhptest.invalid reserved domain is classified as test provenance',
	$failures
);
bhp_lel_test_assert(
	BHP_Lead_Event_Log::PROVENANCE_TEST === BHP_Lead_Event_Log::classify_provenance( 'real.parent@gmail.com' ),
	'On staging, even an ordinary-looking address is classified as test provenance (fail-safe: staging is never counted as real)',
	$failures
);

// ==================== Success path (real hook, synthetic address) ====================
$test_email = 'lead-log-test+bhptest@example.com';
$before_ids = wp_list_pluck( BHP_Lead_Event_Log::get_recent( 5 ), 'ID' );

do_action(
	'bhp_mailchimp_signup_success',
	(object) array( 'id' => 'synthetic' ),
	$test_email,
	'inline_blog',
	'general_readers',
	'reading_guides',
	home_url( '/blog/some-post/' ),
	array( 'Adventure Club' )
);

$after = BHP_Lead_Event_Log::get_recent( 5 );
$new_post = null;
foreach ( $after as $post ) {
	if ( ! in_array( $post->ID, $before_ids, true ) ) {
		$new_post = $post;
		break;
	}
}
$GLOBALS['bhp_lel_created_post_ids'][] = $new_post ? $new_post->ID : 0;

bhp_lel_test_assert( null !== $new_post, 'A success event creates exactly one new lead-event post', $failures );
if ( $new_post ) {
	bhp_lel_test_assert( 'success' === get_post_meta( $new_post->ID, BHP_Lead_Event_Log::META_RESULT, true ), 'Success event is recorded with result=success', $failures );
	bhp_lel_test_assert( $test_email === get_post_meta( $new_post->ID, BHP_Lead_Event_Log::META_EMAIL, true ), 'Success event records the email exactly as submitted', $failures );
	bhp_lel_test_assert( 'inline_blog' === get_post_meta( $new_post->ID, BHP_Lead_Event_Log::META_CONTEXT, true ), 'Success event records the context/placement', $failures );
	bhp_lel_test_assert( 'reading_guides' === get_post_meta( $new_post->ID, BHP_Lead_Event_Log::META_LEAD_MAGNET, true ), 'Success event records the lead magnet', $failures );
	bhp_lel_test_assert( BHP_Lead_Event_Log::PROVENANCE_TEST === get_post_meta( $new_post->ID, BHP_Lead_Event_Log::META_PROVENANCE, true ), 'A +bhptest address on staging is stored with test provenance', $failures );
	bhp_lel_test_assert( 'private' === get_post_status( $new_post->ID ), 'Lead event posts are stored with private status, never publicly queryable', $failures );
}

// ==================== Failure path (real hook, synthetic exception) ====================
$before_ids_2 = wp_list_pluck( BHP_Lead_Event_Log::get_recent( 5 ), 'ID' );
$synthetic_exception = new Exception( 'Simulated MC4WP API timeout for test purposes' );

do_action(
	'bhp_mailchimp_signup_failed',
	$synthetic_exception,
	'footer',
	'teachers',
	'',
	home_url( '/' )
);

$after_2 = BHP_Lead_Event_Log::get_recent( 5 );
$new_post_2 = null;
foreach ( $after_2 as $post ) {
	if ( ! in_array( $post->ID, $before_ids_2, true ) ) {
		$new_post_2 = $post;
		break;
	}
}
$GLOBALS['bhp_lel_created_post_ids'][] = $new_post_2 ? $new_post_2->ID : 0;

bhp_lel_test_assert( null !== $new_post_2, 'A failure event creates exactly one new lead-event post', $failures );
if ( $new_post_2 ) {
	bhp_lel_test_assert( 'failed' === get_post_meta( $new_post_2->ID, BHP_Lead_Event_Log::META_RESULT, true ), 'Failure event is recorded with result=failed', $failures );
	bhp_lel_test_assert( '' === get_post_meta( $new_post_2->ID, BHP_Lead_Event_Log::META_EMAIL, true ), 'Failure event never records an email (the failure hook never receives one)', $failures );
	$reason = get_post_meta( $new_post_2->ID, BHP_Lead_Event_Log::META_FAILURE_REASON, true );
	bhp_lel_test_assert(
		false !== strpos( $reason, 'Exception' ) && false === strpos( $reason, 'Simulated MC4WP API timeout' ),
		'Failure reason stores only a generic exception-class label, never the raw exception message (which could contain provider account details)',
		$failures
	);
}

// ==================== Attribution attachment ====================
$_COOKIE[ BHP_UTM_Attribution::COOKIE_FIRST_TOUCH ] = wp_json_encode( array(
	'utm_source' => 'google',
	'utm_medium' => 'cpc',
	'landing_page' => '/shop/',
	'timestamp' => '2026-06-01T00:00:00.000Z',
) );
$before_ids_3 = wp_list_pluck( BHP_Lead_Event_Log::get_recent( 5 ), 'ID' );
do_action(
	'bhp_mailchimp_signup_success',
	(object) array( 'id' => 'synthetic' ),
	'attribution-test+bhptest@example.com',
	'adventure_club',
	'general_readers',
	'explorer_passport',
	home_url( '/' ),
	array()
);
$after_3 = BHP_Lead_Event_Log::get_recent( 5 );
$new_post_3 = null;
foreach ( $after_3 as $post ) {
	if ( ! in_array( $post->ID, $before_ids_3, true ) ) {
		$new_post_3 = $post;
		break;
	}
}
$GLOBALS['bhp_lel_created_post_ids'][] = $new_post_3 ? $new_post_3->ID : 0;
unset( $_COOKIE[ BHP_UTM_Attribution::COOKIE_FIRST_TOUCH ] );

if ( $new_post_3 ) {
	$stored_first_touch = get_post_meta( $new_post_3->ID, BHP_Lead_Event_Log::META_FIRST_TOUCH, true );
	$decoded = json_decode( $stored_first_touch, true );
	bhp_lel_test_assert( is_array( $decoded ) && 'google' === ( $decoded['utm_source'] ?? null ), 'A lead event captures first-touch attribution from the visitor cookie at signup time, matching the order-attachment pattern', $failures );
} else {
	bhp_lel_test_assert( false, 'Attribution-attachment test lead event must have been created', $failures );
}

// ==================== Summary aggregation never blends test into real ====================
$summary = BHP_Lead_Event_Log::get_summary( 1 );
bhp_lel_test_assert( is_array( $summary ) && isset( $summary['real'], $summary['test'] ), 'get_summary() returns separate real/test buckets', $failures );
bhp_lel_test_assert( $summary['test']['success'] >= 2, 'The synthetic +bhptest signups created by this test are counted under test, not real', $failures );

// ==================== Cleanup (primary path -- do not rely on shutdown) ====================
bhp_lel_cleanup();

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL LEAD EVENT LOG TESTS PASSED\n";
