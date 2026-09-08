<?php
/**
 * Bookvault dispatch tracker — end-to-end integration tests.
 *
 * Drives the real `run()` path against a throwaway order with the Bookvault
 * API mocked at `pre_http_request` and outbound mail short-circuited at
 * `pre_wp_mail`, so nothing leaves the server and no real order is touched.
 *
 * What this proves that the unit test cannot: dry mode really writes
 * nothing, live mode really transitions and really sends exactly one email,
 * and a second run cannot send a second one.
 *
 * ⛔ STAGING ONLY. It creates and then permanently deletes an order.
 *
 * Run:
 *   wp eval-file tests/test-bookvault-tracker-integration.php --user=1
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( false === strpos( home_url(), 'staging' ) ) {
	WP_CLI::error( 'REFUSING TO RUN: this test creates and deletes an order and is staging-only. Host: ' . home_url() );
}

/*
 * ⚠ $GLOBALS and not `global`. `wp eval-file` includes this file inside a
 *   method, so its top-level scope is NOT the global scope.
 */
$GLOBALS['bhp_it_pass'] = 0;
$GLOBALS['bhp_it_fail'] = array();

/**
 * Assert helper.
 *
 * @param string $label     Label.
 * @param bool   $condition Result.
 * @param string $got       Observed.
 * @return void
 */
function bhp_it_assert( $label, $condition, $got = '' ) {
	if ( $condition ) {
		$GLOBALS['bhp_it_pass']++;
		WP_CLI::log( 'PASS  ' . $label );
		return;
	}

	$GLOBALS['bhp_it_fail'][] = $label . ( '' !== $got ? ' [got: ' . $got . ']' : '' );
	WP_CLI::warning( 'FAIL  ' . $label . ( '' !== $got ? ' [got: ' . $got . ']' : '' ) );
}

/* -------------------------------------------------------------------------
 * HARNESS
 * ---------------------------------------------------------------------- */

$GLOBALS['bhp_it_mock']  = null;
$GLOBALS['bhp_it_calls'] = 0;
$GLOBALS['bhp_it_mail']  = array();

// Mock every request to the Bookvault API. Anything else is left alone.
add_filter(
	'pre_http_request',
	function ( $preempt, $args, $url ) {
		if ( false === strpos( $url, 'api.bookvault.app' ) ) {
			return $preempt;
		}

		$GLOBALS['bhp_it_calls']++;

		$mock = $GLOBALS['bhp_it_mock'];

		if ( null === $mock ) {
			return new WP_Error( 'bhp_it_no_mock', 'No mock configured for this call.' );
		}

		if ( $mock instanceof WP_Error ) {
			return $mock;
		}

		return array(
			'headers'  => array(),
			'body'     => is_string( $mock['body'] ) ? $mock['body'] : wp_json_encode( $mock['body'] ),
			'response' => array( 'code' => (int) $mock['code'], 'message' => '' ),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	10,
	3
);

// Short-circuit ALL outbound mail. Nothing reaches a real inbox.
add_filter(
	'pre_wp_mail',
	function ( $null, $atts ) {
		$GLOBALS['bhp_it_mail'][] = isset( $atts['subject'] ) ? $atts['subject'] : '(no subject)';
		return true;
	},
	10,
	2
);

/**
 * Reset the mail capture.
 *
 * @return void
 */
function bhp_it_reset_mail() {
	$GLOBALS['bhp_it_mail'] = array();
}

/**
 * Set the mock response.
 *
 * @param int|WP_Error $code HTTP code, or a WP_Error for a transport failure.
 * @param mixed        $body Body.
 * @return void
 */
function bhp_it_mock( $code, $body = '' ) {
	$GLOBALS['bhp_it_mock'] = ( $code instanceof WP_Error ) ? $code : array( 'code' => $code, 'body' => $body );
}

/**
 * A dispatched v3 payload.
 *
 * @return array
 */
function bhp_it_dispatched_payload() {
	return array(
		'PodRef'   => 9999999,
		'DocRef'   => 'WC-2724-TEST',
		'Status'   => 'Active',
		'Progress' => array(
			'Status'       => 'Dispatched',
			'IsDispatched' => true,
			'Dispatched'   => gmdate( 'Y-m-d\TH:i:s\Z', time() - 3600 ),
		),
		'Tracking' => array(
			'Tracked'        => true,
			'TrackingNumber' => 'TESTTRACK123',
			'CombinedURL'    => 'https://example.com/track/TESTTRACK123',
			'ServName'       => 'Test Carrier',
			'ServDetail'     => 'Tracked',
		),
	);
}

/**
 * A not-yet-dispatched v3 payload, matching the real current state of the
 * two live production orders.
 *
 * @return array
 */
function bhp_it_pending_payload() {
	return array(
		'PodRef'   => 9999999,
		'DocRef'   => 'WC-2724-TEST',
		'Status'   => 'Active',
		'Progress' => array(
			'Status'       => 'SentToPrint',
			'IsDispatched' => false,
			'Dispatched'   => null,
		),
	);
}

/* -------------------------------------------------------------------------
 * FIXTURE ORDER
 * ---------------------------------------------------------------------- */

$order = wc_create_order();
$order->set_billing_first_name( 'Tracker' );
$order->set_billing_last_name( 'Fixture' );
// RFC 2606 reserved domain. Mail is short-circuited above regardless.
$order->set_billing_email( 'tracker-fixture@example.com' );
$order->update_meta_data( 'BVRef', '9999999' );
$order->update_meta_data( '_bhp_tracker_test_fixture', '1' );
$order->set_status( 'processing' );
$order->save();

$order_id = $order->get_id();
$GLOBALS['bhp_it_order_id'] = $order_id;

WP_CLI::log( 'Fixture order created: #' . $order_id );
WP_CLI::log( str_repeat( '-', 62 ) );

/**
 * Reload the fixture from the store rather than trusting the in-memory copy.
 *
 * @return WC_Order|false
 */
function bhp_it_reload() {
	return wc_get_order( $GLOBALS['bhp_it_order_id'] );
}

/**
 * Run the tracker against the fixture only.
 *
 * @param bool $dry Dry-run.
 * @return array
 */
function bhp_it_run( $dry ) {
	bhp_it_reset_mail();

	return BHP_Bookvault_Tracker::run(
		array(
			'dry_run'  => $dry,
			'order_id' => $GLOBALS['bhp_it_order_id'],
			'trigger'  => 'integration-test',
		)
	);
}

// Remember whatever credential state this environment was in, so the test
// restores it exactly. Value is never read, printed or compared.
$bhp_it_had_key = ( false !== get_option( BHP_Bookvault_Tracker::OPTION_KEY, false ) );
$GLOBALS['bhp_it_saved_key'] = $bhp_it_had_key ? get_option( BHP_Bookvault_Tracker::OPTION_KEY ) : null;

try {

	/* ---------------------------------------------------------------------
	 * 0. NO CREDENTIAL. The tracker must refuse to do anything at all,
	 *    including making a request, even on a dispatched payload.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 0. No credential configured ===' );

	delete_option( BHP_Bookvault_Tracker::OPTION_KEY );

	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$calls_before = $GLOBALS['bhp_it_calls'];
	$s = bhp_it_run( false );

	bhp_it_assert( 'No credential: run halts', 'no_credential' === $s['halted'], $s['halted'] );
	bhp_it_assert( 'No credential: no API call attempted', $calls_before === $GLOBALS['bhp_it_calls'] );
	bhp_it_assert( 'No credential: order untouched', 'processing' === bhp_it_reload()->get_status() );
	bhp_it_assert( 'No credential: no email', 0 === count( $GLOBALS['bhp_it_mail'] ) );

	// From here on the tracker needs to believe it is configured. The HTTP
	// layer is mocked above, so this value is never sent anywhere.
	update_option( BHP_Bookvault_Tracker::OPTION_KEY, 'bv_integration_test_placeholder' );

	/* ---------------------------------------------------------------------
	 * 1. Not yet dispatched. This is the real current state of both live
	 *    production orders, and it must do nothing at all.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 1. SentToPrint, not dispatched ===' );

	bhp_it_mock( 200, bhp_it_pending_payload() );
	$s = bhp_it_run( true );

	bhp_it_assert( 'Pending order is examined', 1 === $s['examined'] );
	bhp_it_assert( 'Pending order is not dispatched', 0 === $s['dispatched'] );
	bhp_it_assert( 'Pending order is skipped', 1 === $s['skipped'] );
	bhp_it_assert( 'Pending order status is unchanged', 'processing' === bhp_it_reload()->get_status(), bhp_it_reload()->get_status() );
	bhp_it_assert( 'Pending order sends no mail', 0 === count( $GLOBALS['bhp_it_mail'] ), implode( ' | ', $GLOBALS['bhp_it_mail'] ) );

	/* ---------------------------------------------------------------------
	 * 2. DRY MODE on a genuinely dispatched order: it must recognise the
	 *    dispatch and still write absolutely nothing.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 2. Dispatched, DRY mode ===' );

	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$s = bhp_it_run( true );

	$o = bhp_it_reload();

	bhp_it_assert( 'DRY: mode reported as dry', 'dry' === $s['mode'], $s['mode'] );
	bhp_it_assert( 'DRY: order status still processing', 'processing' === $o->get_status(), $o->get_status() );
	bhp_it_assert( 'DRY: no dispatch-recorded meta written', '' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_RECORDED ) );
	bhp_it_assert( 'DRY: no tracking meta written', '' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_TRACKING ) );
	bhp_it_assert( 'DRY: no mail sent', 0 === count( $GLOBALS['bhp_it_mail'] ), implode( ' | ', $GLOBALS['bhp_it_mail'] ) );

	$notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
	$note_hits = 0;
	foreach ( $notes as $n ) {
		if ( false !== strpos( $n->content, 'Bookvault dispatch confirmed' ) ) {
			$note_hits++;
		}
	}
	bhp_it_assert( 'DRY: no dispatch note added', 0 === $note_hits, (string) $note_hits );

	$log = BHP_Bookvault_Tracker::get_log( 5 );
	$logged_dry = false;
	foreach ( $log as $entry ) {
		if ( 'would_complete' === $entry['reason'] && $order_id === (int) $entry['order_id'] ) {
			$logged_dry = true;
		}
	}
	bhp_it_assert( 'DRY: the log records what it WOULD have done', $logged_dry );

	/* ---------------------------------------------------------------------
	 * 3. LIVE MODE: transition, evidence, exactly one email.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 3. Dispatched, LIVE mode ===' );

	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$s = bhp_it_run( false );

	$o = bhp_it_reload();

	bhp_it_assert( 'LIVE: one dispatch reported', 1 === $s['dispatched'], (string) $s['dispatched'] );
	bhp_it_assert( 'LIVE: order is now completed', 'completed' === $o->get_status(), $o->get_status() );
	bhp_it_assert( 'LIVE: dispatch-recorded meta written', '' !== (string) $o->get_meta( BHP_Bookvault_Tracker::META_RECORDED ) );
	bhp_it_assert( 'LIVE: dispatch timestamp saved', '' !== (string) $o->get_meta( BHP_Bookvault_Tracker::META_AT ) );
	bhp_it_assert( 'LIVE: carrier saved', 'Test Carrier' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_CARRIER ), (string) $o->get_meta( BHP_Bookvault_Tracker::META_CARRIER ) );
	bhp_it_assert( 'LIVE: tracking number saved', 'TESTTRACK123' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_TRACKING ) );
	bhp_it_assert( 'LIVE: tracking URL saved', 'https://example.com/track/TESTTRACK123' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_URL ) );
	bhp_it_assert( 'LIVE: Variant B four-field flag saved as yes', 'yes' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_COMPLETE ) );
	bhp_it_assert( 'LIVE: Progress.Status saved as Dispatched', 'Dispatched' === (string) $o->get_meta( BHP_Bookvault_Tracker::META_PROGRESS ) );

	$notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
	$evidence_note = '';
	foreach ( $notes as $n ) {
		if ( false !== strpos( $n->content, 'Bookvault dispatch confirmed' ) ) {
			$evidence_note = $n->content;
		}
	}
	bhp_it_assert( 'LIVE: evidence note written', '' !== $evidence_note );
	bhp_it_assert( 'LIVE: evidence note carries the tracking link', false !== strpos( $evidence_note, 'https://example.com/track/TESTTRACK123' ) );
	bhp_it_assert( 'LIVE: evidence note carries the Bookvault reference', false !== strpos( $evidence_note, 'BV9999999' ) );

	$mail_count = count( $GLOBALS['bhp_it_mail'] );
	bhp_it_assert( 'LIVE: exactly one email sent', 1 === $mail_count, implode( ' | ', $GLOBALS['bhp_it_mail'] ) );
	bhp_it_assert(
		'LIVE: that email is the E2 shipping confirmation',
		1 === $mail_count && false !== strpos( $GLOBALS['bhp_it_mail'][0], 'shipped' ),
		implode( ' | ', $GLOBALS['bhp_it_mail'] )
	);

	/* ---------------------------------------------------------------------
	 * 4. IDEMPOTENCY. Run again with the same dispatched payload.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 4. Idempotency ===' );

	$calls_before = $GLOBALS['bhp_it_calls'];

	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$s = bhp_it_run( false );

	bhp_it_assert( 'Second run dispatches nothing', 0 === $s['dispatched'], (string) $s['dispatched'] );
	bhp_it_assert( 'Second run sends no email', 0 === count( $GLOBALS['bhp_it_mail'] ), implode( ' | ', $GLOBALS['bhp_it_mail'] ) );
	bhp_it_assert( 'Second run makes no API call at all (guard runs first)', $calls_before === $GLOBALS['bhp_it_calls'], (string) ( $GLOBALS['bhp_it_calls'] - $calls_before ) );
	bhp_it_assert( 'Order is still completed exactly once', 'completed' === bhp_it_reload()->get_status() );

	// Force the harder case: an order pushed back to processing by a human
	// while the dispatch record still exists. The recorded-meta guard must
	// still stop it.
	$o = bhp_it_reload();
	$o->set_status( 'processing' );
	$o->save();

	$calls_before = $GLOBALS['bhp_it_calls'];
	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$s = bhp_it_run( false );

	bhp_it_assert( 'Re-opened order with a dispatch record is not re-completed', 0 === $s['dispatched'] );
	bhp_it_assert( 'Re-opened order sends no second email', 0 === count( $GLOBALS['bhp_it_mail'] ), implode( ' | ', $GLOBALS['bhp_it_mail'] ) );
	bhp_it_assert( 'Re-opened order triggers no API call', $calls_before === $GLOBALS['bhp_it_calls'] );

	/* ---------------------------------------------------------------------
	 * 5. FAILURE MODES against a clean order.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 5. Failure modes ===' );

	$o = bhp_it_reload();
	$o->delete_meta_data( BHP_Bookvault_Tracker::META_RECORDED );
	$o->set_status( 'processing' );
	$o->save();

	$failure_cases = array(
		'API 500'            => array( 500, 'Internal Server Error' ),
		'API 401'            => array( 401, 'Invalid Token' ),
		'API 404'            => array( 404, 'Order not found' ),
		'API 200 + HTML'     => array( 200, '<html>oops</html>' ),
		'API 200 + no body'  => array( 200, '' ),
	);

	foreach ( $failure_cases as $label => $mock ) {
		bhp_it_mock( $mock[0], $mock[1] );
		$s = bhp_it_run( false );

		$o = bhp_it_reload();

		bhp_it_assert( $label . ': no dispatch', 0 === $s['dispatched'] );
		bhp_it_assert( $label . ': counted as an error', 1 === $s['errors'], (string) $s['errors'] );
		bhp_it_assert( $label . ': order still processing', 'processing' === $o->get_status(), $o->get_status() );
		bhp_it_assert( $label . ': no email', 0 === count( $GLOBALS['bhp_it_mail'] ), implode( ' | ', $GLOBALS['bhp_it_mail'] ) );
	}

	bhp_it_mock( new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );
	$s = bhp_it_run( false );
	bhp_it_assert( 'Transport failure: no dispatch', 0 === $s['dispatched'] );
	bhp_it_assert( 'Transport failure: order still processing', 'processing' === bhp_it_reload()->get_status() );
	bhp_it_assert( 'Transport failure: no email', 0 === count( $GLOBALS['bhp_it_mail'] ) );

	/* ---------------------------------------------------------------------
	 * 6. Ambiguous dispatch: IsDispatched true, no timestamp.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 6. Ambiguous dispatch ===' );

	$payload = bhp_it_dispatched_payload();
	$payload['Progress']['Dispatched'] = null;

	bhp_it_mock( 200, $payload );
	$s = bhp_it_run( false );

	bhp_it_assert( 'IsDispatched=true with no timestamp: no dispatch', 0 === $s['dispatched'] );
	bhp_it_assert( 'IsDispatched=true with no timestamp: order still processing', 'processing' === bhp_it_reload()->get_status() );
	bhp_it_assert( 'IsDispatched=true with no timestamp: NO EMAIL CLAIMING SHIPMENT', 0 === count( $GLOBALS['bhp_it_mail'] ), implode( ' | ', $GLOBALS['bhp_it_mail'] ) );

	/* ---------------------------------------------------------------------
	 * 7. Kill switch.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 7. Kill switch ===' );

	add_filter( 'bhp_tracker_enabled', '__return_false' );
	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$calls_before = $GLOBALS['bhp_it_calls'];
	$s = bhp_it_run( false );
	remove_filter( 'bhp_tracker_enabled', '__return_false' );

	bhp_it_assert( 'Kill switch halts the run', 'disabled_by_filter' === $s['halted'], $s['halted'] );
	bhp_it_assert( 'Kill switch prevents any API call', $calls_before === $GLOBALS['bhp_it_calls'] );
	bhp_it_assert( 'Kill switch leaves the order alone', 'processing' === bhp_it_reload()->get_status() );
	bhp_it_assert( 'Kill switch sends no email', 0 === count( $GLOBALS['bhp_it_mail'] ) );

	/* ---------------------------------------------------------------------
	 * 8. An order with no BVRef is never polled.
	 * ------------------------------------------------------------------ */

	WP_CLI::log( '=== 8. No Bookvault reference ===' );

	$o = bhp_it_reload();
	$o->delete_meta_data( 'BVRef' );
	$o->save();

	$calls_before = $GLOBALS['bhp_it_calls'];
	bhp_it_mock( 200, bhp_it_dispatched_payload() );
	$s = bhp_it_run( false );

	bhp_it_assert( 'Order without BVRef: no dispatch', 0 === $s['dispatched'] );
	bhp_it_assert( 'Order without BVRef: no API call', $calls_before === $GLOBALS['bhp_it_calls'] );
	bhp_it_assert( 'Order without BVRef: no email', 0 === count( $GLOBALS['bhp_it_mail'] ) );

} finally {

	/* ---------------------------------------------------------------------
	 * CLEANUP — the fixture order and the placeholder credential are both
	 * removed, pass or fail.
	 * ------------------------------------------------------------------ */

	if ( null === $GLOBALS['bhp_it_saved_key'] ) {
		delete_option( BHP_Bookvault_Tracker::OPTION_KEY );
	} else {
		update_option( BHP_Bookvault_Tracker::OPTION_KEY, $GLOBALS['bhp_it_saved_key'] );
	}

	bhp_it_assert(
		'CLEANUP: the placeholder credential is gone',
		'bv_integration_test_placeholder' !== (string) get_option( BHP_Bookvault_Tracker::OPTION_KEY, '' )
	);

	$o = wc_get_order( $order_id );

	if ( $o ) {
		$o->delete( true );
	}

	$still_there = wc_get_order( $order_id );
	bhp_it_assert( 'CLEANUP: fixture order permanently deleted', ! $still_there || 'trash' === $still_there->get_status() );

	WP_CLI::log( 'Fixture order #' . $order_id . ' deleted.' );
}

$bhp_it_pass = (int) $GLOBALS['bhp_it_pass'];
$bhp_it_fail = (array) $GLOBALS['bhp_it_fail'];
$total       = $bhp_it_pass + count( $bhp_it_fail );

WP_CLI::log( '' );
WP_CLI::log( str_repeat( '=', 62 ) );

if ( empty( $bhp_it_fail ) ) {
	WP_CLI::success( sprintf( 'INTEGRATION: %d/%d assertions passed. Mocked API calls: %d. Emails that left the server: 0.', $bhp_it_pass, $total, $GLOBALS['bhp_it_calls'] ) );
} else {
	foreach ( $bhp_it_fail as $f ) {
		WP_CLI::warning( 'FAILED: ' . $f );
	}
	WP_CLI::error( sprintf( 'INTEGRATION: %d of %d assertions FAILED.', count( $bhp_it_fail ), $total ) );
}
