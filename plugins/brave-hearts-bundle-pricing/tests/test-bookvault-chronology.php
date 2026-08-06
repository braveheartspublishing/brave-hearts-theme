<?php
/**
 * Brave Hearts Dashboard — Bookvault status chronology test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-bookvault-chronology.php --user=1
 *
 * Exercises BHP_Bookvault_Status::get_status_from_events() and
 * classify_note_content() directly with plain arrays -- no real WC_Order
 * or database notes are read/written. Covers every chronology permutation
 * from the Phase 5 KPI reconciliation spec, using the exact note wording
 * observed on real orders (#351, #355) as fixtures.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_bv_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// Real note wording observed during the production Bookvault audit,
// order #355 (clean success) and #351 (historical failure case).
$SUCCESS_ACTIVE = 'Order saved with status Active as BV2796848';
$SUCCESS_DRAFT  = 'Order saved with status Draft as BV2796764';
$FAILURE_NOTE   = 'Failed to read line_items: Notice - The Bookvault plugin scans all incoming orders to identify those specifically intended for Bookvault to fulfill...';
$UNRELATED_NOTE = 'Stripe charge complete (Charge ID: py_abc123)';

function bhp_bv_event( $content, $timestamp ) {
	$event = BHP_Bookvault_Status::classify_note_content( $content );
	if ( null === $event ) {
		return null;
	}
	$event['timestamp'] = $timestamp;
	$event['time'] = gmdate( 'c', $timestamp );
	return $event;
}

function bhp_bv_events_from_notes( array $notes_with_times ) {
	$events = array();
	foreach ( $notes_with_times as $pair ) {
		list( $content, $ts ) = $pair;
		$event = bhp_bv_event( $content, $ts );
		if ( null !== $event ) {
			$events[] = $event;
		}
	}
	return $events;
}

$T0 = 1720000000; // arbitrary fixed base timestamp for deterministic fixtures

// 1. No Bookvault note at all.
$r = BHP_Bookvault_Status::get_status_from_events( array(), $T0 );
bhp_bv_test_assert( 'unknown' === $r['status'], '1. No Bookvault note -> unknown', $failures );
bhp_bv_test_assert( false === $r['had_prior_success'], '1. No note -> had_prior_success is false', $failures );

// 2. Failure only.
$events = bhp_bv_events_from_notes( array( array( $FAILURE_NOTE, $T0 + 60 ) ) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'failed' === $r['status'], '2. Failure only -> failed', $failures );
bhp_bv_test_assert( 1 === $r['failure_count'], '2. Failure only -> failure_count 1', $failures );
bhp_bv_test_assert( false === $r['had_prior_success'], '2. Failure only -> had_prior_success is false', $failures );

// 3. Draft only.
$events = bhp_bv_events_from_notes( array( array( $SUCCESS_DRAFT, $T0 + 60 ) ) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '3. Draft only -> routed', $failures );
bhp_bv_test_assert( true === $r['bookvault_state_is_draft'], '3. Draft only -> bookvault_state_is_draft true', $failures );

// 4. Active success only.
$events = bhp_bv_events_from_notes( array( array( $SUCCESS_ACTIVE, $T0 + 60 ) ) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '4. Active success only -> routed', $failures );
bhp_bv_test_assert( false === $r['bookvault_state_is_draft'], '4. Active success only -> not draft', $failures );
bhp_bv_test_assert( 60 === $r['seconds_to_route'], '4. Active success only -> seconds_to_route is 60', $failures );

// 5. Failure followed by Draft.
$events = bhp_bv_events_from_notes( array(
	array( $FAILURE_NOTE, $T0 + 60 ),
	array( $SUCCESS_DRAFT, $T0 + 120 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '5. Failure then Draft -> routed (retry succeeded)', $failures );
bhp_bv_test_assert( 1 === $r['failure_count'], '5. Failure then Draft -> failure_count still recorded as 1', $failures );

// 6. Failure followed by Active.
$events = bhp_bv_events_from_notes( array(
	array( $FAILURE_NOTE, $T0 + 60 ),
	array( $SUCCESS_ACTIVE, $T0 + 600 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '6. Failure then Active -> routed', $failures );
bhp_bv_test_assert( 'BV2796848' === $r['bookvault_ref'], '6. Failure then Active -> ref reflects the success', $failures );

// 7. Draft followed by Active -- the chronology bug this rewrite fixes:
// a version that returns on the first success match would incorrectly
// freeze this at "Draft" forever.
$events = bhp_bv_events_from_notes( array(
	array( $SUCCESS_DRAFT, $T0 + 60 ),
	array( $SUCCESS_ACTIVE, $T0 + 3600 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '7. Draft then Active -> routed', $failures );
bhp_bv_test_assert( false === $r['bookvault_state_is_draft'], '7. Draft then Active -> latest state is Active, not draft', $failures );
bhp_bv_test_assert( 'Active' === $r['bookvault_state'], '7. Draft then Active -> bookvault_state is the LATEST (Active), not the first (Draft)', $failures );
bhp_bv_test_assert( 60 === $r['seconds_to_route'], '7. Draft then Active -> seconds_to_route still measures the FIRST success (initial routing latency)', $failures );

// 8. Multiple failures followed by success.
$events = bhp_bv_events_from_notes( array(
	array( $FAILURE_NOTE, $T0 + 60 ),
	array( $FAILURE_NOTE, $T0 + 180 ),
	array( $FAILURE_NOTE, $T0 + 600 ),
	array( $SUCCESS_ACTIVE, $T0 + 900 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '8. Multiple failures then success -> routed', $failures );
bhp_bv_test_assert( 3 === $r['failure_count'], '8. Multiple failures then success -> failure_count is 3', $failures );

// 9. Success followed by a later failure -- the other chronology bug this
// rewrite fixes: a version that returns on the first success match would
// incorrectly keep reporting "routed" even after a later failure.
$events = bhp_bv_events_from_notes( array(
	array( $SUCCESS_ACTIVE, $T0 + 60 ),
	array( $FAILURE_NOTE, $T0 + 7200 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'failed' === $r['status'], '9. Success then later failure -> failed (latest event wins)', $failures );
bhp_bv_test_assert( true === $r['had_prior_success'], '9. Success then later failure -> had_prior_success is true (distinguishes from never-routed)', $failures );
bhp_bv_test_assert( 60 === $r['seconds_to_route'], '9. Success then later failure -> seconds_to_route still reflects the original successful routing', $failures );

// 10. Multiple Bookvault references (e.g. a resend created a new BV id) --
// the latest reference should win, not the first.
$events = bhp_bv_events_from_notes( array(
	array( $SUCCESS_DRAFT, $T0 + 60 ),
	array( $SUCCESS_ACTIVE, $T0 + 3600 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'BV2796848' === $r['bookvault_ref'], '10. Multiple references -> latest reference wins', $failures );

// 11. Manual resolution note where recognizable -- a manual resend that
// produces the same Bookvault-generated success note is recognized like
// any other success; free-text admin notes that don't match either
// pattern are correctly ignored rather than guessed at.
$events = bhp_bv_events_from_notes( array(
	array( $FAILURE_NOTE, $T0 + 60 ),
	array( 'Andrew manually resent this order from the Bookvault portal.', $T0 + 3000 ), // free text, not a Bookvault-generated note
	array( $SUCCESS_ACTIVE, $T0 + 3100 ),
) );
$r = BHP_Bookvault_Status::get_status_from_events( $events, $T0 );
bhp_bv_test_assert( 'routed' === $r['status'], '11. Manual resend note (unrecognized text) does not block the later real success note from being read', $failures );

// 12. Paid order still awaiting Bookvault routing (no notes yet) -- pure
// get_status_from_events() reports 'unknown', matching "no notes yet";
// BHP_Order_Metrics::is_routing_overdue() is what decides whether this is
// still within the normal window or overdue, deliberately kept separate.
$r = BHP_Bookvault_Status::get_status_from_events( array(), $T0 );
bhp_bv_test_assert( 'unknown' === $r['status'], '12. Paid order with no notes yet -> unknown (business-level "overdue?" decision lives in BHP_Order_Metrics, not here)', $failures );

// 13. Order not expected to route at all -- covered by
// order_is_bookvault_eligible(), not by get_status_from_events() (which
// only ever runs for eligible orders in the real dashboard flow).
bhp_bv_test_assert(
	function_exists( 'BHP_Bookvault_Status::order_is_bookvault_eligible' ) || method_exists( 'BHP_Bookvault_Status', 'order_is_bookvault_eligible' ),
	'13. order_is_bookvault_eligible() exists to gate non-catalog orders out before chronology is ever computed',
	$failures
);

// --- Unrelated note text should never be classified as an event ---
bhp_bv_test_assert(
	null === BHP_Bookvault_Status::classify_note_content( $UNRELATED_NOTE ),
	'Unrelated Stripe note is not classified as a Bookvault event',
	$failures
);

echo empty( $failures ) ? "\nALL BOOKVAULT CHRONOLOGY TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
