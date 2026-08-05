<?php
/**
 * Bookvault dispatch tracker — state-machine unit tests.
 *
 * Covers `BHP_Bookvault_Tracker::evaluate()`, which is deliberately free of
 * WordPress calls so that every branch can be driven from a fixture instead
 * of from a live API.
 *
 * The property under test is one-directional and it is the whole point of
 * the tracker: NOTHING except an unambiguous dispatch may return the
 * `dispatch` action. Every error, every absent field, every unrecognised
 * value and every self-contradicting record must return `skip` or `error`.
 *
 * Run (staging, never production):
 *   wp eval-file tests/test-bookvault-tracker.php --user=1
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/*
 * ⚠ $GLOBALS and not `global`, deliberately. `wp eval-file` includes this
 *   file inside a method, so its top-level scope is NOT the global scope and
 *   a `global $x` inside a helper would bind to a different, empty variable.
 *   Every counter here is addressed through $GLOBALS so the two scopes are
 *   provably the same one.
 */
$GLOBALS['bhp_tracker_pass'] = 0;
$GLOBALS['bhp_tracker_fail'] = array();

/**
 * Assert helper.
 *
 * @param string $label     Test label.
 * @param bool   $condition Result.
 * @param string $got       What was actually observed.
 * @return void
 */
function bhp_tracker_assert( $label, $condition, $got = '' ) {
	if ( $condition ) {
		$GLOBALS['bhp_tracker_pass']++;
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( 'PASS  ' . $label );
		}
		return;
	}

	$GLOBALS['bhp_tracker_fail'][] = $label . ( '' !== $got ? ' [got: ' . $got . ']' : '' );

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::warning( 'FAIL  ' . $label . ( '' !== $got ? ' [got: ' . $got . ']' : '' ) );
	}
}

/**
 * Run one fixture through evaluate() and assert on action + reason.
 *
 * @param string     $label    Test label.
 * @param int|null   $code     HTTP code.
 * @param array|null $payload  Decoded body to re-encode, or a raw string.
 * @param string     $action   Expected action.
 * @param string     $reason   Expected reason.
 * @param string     $error    Transport error.
 * @return array The outcome, for further assertions.
 */
function bhp_tracker_case( $label, $code, $payload, $action, $reason, $error = null ) {
	$body = is_array( $payload ) ? wp_json_encode( $payload ) : $payload;

	$out = BHP_Bookvault_Tracker::evaluate( $code, $body, $error );

	bhp_tracker_assert(
		$label,
		$action === $out['action'] && $reason === $out['reason'],
		$out['action'] . '/' . $out['reason'] . ' - ' . $out['detail']
	);

	return $out;
}

/**
 * Build a v3-shaped Order payload.
 *
 * Mirrors the real schema, including the `Status` / `Progress.Status` trap:
 * the top-level `Status` is always the order TYPE and is always "Active" on
 * this store, which is exactly why it must never be read as fulfilment.
 *
 * @param array $progress Progress overrides.
 * @param array $tracking Tracking overrides.
 * @return array
 */
function bhp_tracker_payload( $progress = array(), $tracking = null ) {
	$order = array(
		'PodRef'      => 2845712,
		'DocRef'      => 'WC-2724-417',
		'Status'      => 'Active', // ⛔ order TYPE, never fulfilment.
		'OrderMethod' => 'WooCommerce',
		'Progress'    => array_merge(
			array(
				'Status'         => 'SentToPrint',
				'Created'        => '2026-08-01T08:19:00Z',
				'IsAcknowledged' => true,
				'IsPrintSent'    => true,
				'IsDispatched'   => false,
				'Dispatched'     => null,
			),
			$progress
		),
	);

	if ( null !== $tracking ) {
		$order['Tracking'] = $tracking;
	}

	return $order;
}

/**
 * A fully-populated tracking block, i.e. the Variant B happy path.
 *
 * @return array
 */
function bhp_tracker_full_tracking() {
	return array(
		'Tracked'        => true,
		'TrackingNumber' => '9400100000000000000000',
		'BaseURL'        => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=',
		'CombinedURL'    => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=9400100000000000000000',
		'ServName'       => 'USPS Ground Advantage',
		'ServDetail'     => 'Tracked, no signature',
	);
}

$dispatched_at = gmdate( 'Y-m-d\TH:i:s\Z', time() - HOUR_IN_SECONDS );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== A. Every Progress.Status value, not yet dispatched ===' );
}

/* -------------------------------------------------------------------------
 * A. The full enum, IsDispatched false. NONE may dispatch.
 * ---------------------------------------------------------------------- */

foreach ( BHP_Bookvault_Tracker::KNOWN_STATUSES as $status ) {
	bhp_tracker_case(
		'Progress.Status=' . $status . ' + IsDispatched=false -> skip/not_dispatched',
		200,
		bhp_tracker_payload( array( 'Status' => $status, 'IsDispatched' => false ) ),
		'skip',
		'not_dispatched'
	);
}

// The same enum with IsDispatched entirely absent.
foreach ( BHP_Bookvault_Tracker::KNOWN_STATUSES as $status ) {
	$payload = bhp_tracker_payload( array( 'Status' => $status ) );
	unset( $payload['Progress']['IsDispatched'] );

	bhp_tracker_case(
		'Progress.Status=' . $status . ' + IsDispatched absent -> skip/not_dispatched',
		200,
		$payload,
		'skip',
		'not_dispatched'
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== B. The only two states that may dispatch ===' );
}

/* -------------------------------------------------------------------------
 * B. The dispatch happy paths.
 * ---------------------------------------------------------------------- */

$out = bhp_tracker_case(
	'Dispatched + IsDispatched=true + real timestamp + full tracking -> dispatch',
	200,
	bhp_tracker_payload(
		array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ),
		bhp_tracker_full_tracking()
	),
	'dispatch',
	'dispatched'
);

bhp_tracker_assert( '  ... evidence carries the dispatch timestamp', $dispatched_at === $out['evidence']['dispatched_at'], $out['evidence']['dispatched_at'] );
bhp_tracker_assert( '  ... evidence carries the carrier', 'USPS Ground Advantage' === $out['evidence']['carrier'], $out['evidence']['carrier'] );
bhp_tracker_assert( '  ... evidence carries the tracking number', '9400100000000000000000' === $out['evidence']['tracking_number'], $out['evidence']['tracking_number'] );
bhp_tracker_assert( '  ... evidence carries the tracking URL', 0 === strpos( $out['evidence']['tracking_url'], 'https://tools.usps.com/' ), $out['evidence']['tracking_url'] );
bhp_tracker_assert( '  ... Variant B gate reports COMPLETE when all four fields exist', true === $out['evidence']['variant_b_complete'] );

// `Invoiced` comes after `Dispatched` in the enum. An order that moved two
// steps between polls is still dispatched and must not be missed.
bhp_tracker_case(
	'Invoiced + IsDispatched=true + real timestamp -> dispatch (post-dispatch status)',
	200,
	bhp_tracker_payload(
		array( 'Status' => 'Invoiced', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ),
		bhp_tracker_full_tracking()
	),
	'dispatch',
	'dispatched'
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== C. Dispatch signals that are NOT trustworthy ===' );
}

/* -------------------------------------------------------------------------
 * C. Every way a dispatch claim can be untrustworthy.
 * ---------------------------------------------------------------------- */

bhp_tracker_case(
	'IsDispatched=true but Dispatched is null -> skip/dispatched_without_timestamp',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => null ) ),
	'skip',
	'dispatched_without_timestamp'
);

bhp_tracker_case(
	'IsDispatched=true but Dispatched is an empty string -> skip/dispatched_without_timestamp',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => '' ) ),
	'skip',
	'dispatched_without_timestamp'
);

bhp_tracker_case(
	'IsDispatched=true but Dispatched is the .NET zero date -> skip/dispatched_without_timestamp',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => '0001-01-01T00:00:00' ) ),
	'skip',
	'dispatched_without_timestamp'
);

bhp_tracker_case(
	'IsDispatched=true but Dispatched is the Unix epoch -> skip/dispatched_without_timestamp',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => '1970-01-01T00:00:00Z' ) ),
	'skip',
	'dispatched_without_timestamp'
);

bhp_tracker_case(
	'IsDispatched=true but Dispatched is unparseable -> skip/dispatched_without_timestamp',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => 'sometime last week' ) ),
	'skip',
	'dispatched_without_timestamp'
);

bhp_tracker_case(
	'IsDispatched=true but Dispatched is a year in the future -> skip/dispatched_without_timestamp',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => gmdate( 'Y-m-d\TH:i:s\Z', time() + YEAR_IN_SECONDS ) ) ),
	'skip',
	'dispatched_without_timestamp'
);

// Strict `true` only. A loose test would read the STRING "false" as true.
bhp_tracker_case(
	'IsDispatched is the string "true" -> skip/not_dispatched (strict comparison)',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => 'true', 'Dispatched' => $dispatched_at ) ),
	'skip',
	'not_dispatched'
);

bhp_tracker_case(
	'IsDispatched is the string "false" -> skip/not_dispatched (never read as truthy)',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => 'false', 'Dispatched' => $dispatched_at ) ),
	'skip',
	'not_dispatched'
);

bhp_tracker_case(
	'IsDispatched is integer 1 -> skip/not_dispatched (strict comparison)',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => 1, 'Dispatched' => $dispatched_at ) ),
	'skip',
	'not_dispatched'
);

bhp_tracker_case(
	'IsDispatched=true while Progress.Status is still SentToPrint -> skip/contradictory_state',
	200,
	bhp_tracker_payload( array( 'Status' => 'SentToPrint', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ) ),
	'skip',
	'contradictory_state'
);

bhp_tracker_case(
	'IsDispatched=true while Progress.Status is Printed -> skip/contradictory_state',
	200,
	bhp_tracker_payload( array( 'Status' => 'Printed', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ) ),
	'skip',
	'contradictory_state'
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== D. Malformed and unknown responses ===' );
}

/* -------------------------------------------------------------------------
 * D. Shape failures.
 * ---------------------------------------------------------------------- */

bhp_tracker_case(
	'Progress.Status is a value the v3 schema does not define -> skip/unknown_progress_status',
	200,
	bhp_tracker_payload( array( 'Status' => 'AwaitingReprint', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ) ),
	'skip',
	'unknown_progress_status'
);

$payload = bhp_tracker_payload();
unset( $payload['Progress'] );
bhp_tracker_case( 'Response carries no Progress object -> skip/no_progress_object', 200, $payload, 'skip', 'no_progress_object' );

$payload = bhp_tracker_payload();
$payload['Progress'] = 'Dispatched';
bhp_tracker_case( 'Progress is a string rather than an object -> skip/no_progress_object', 200, $payload, 'skip', 'no_progress_object' );

$payload = bhp_tracker_payload();
unset( $payload['Progress']['Status'] );
bhp_tracker_case( 'Progress present but Status absent -> skip/no_progress_status', 200, $payload, 'skip', 'no_progress_status' );

$payload = bhp_tracker_payload( array( 'Status' => '' ) );
bhp_tracker_case( 'Progress.Status is an empty string -> skip/no_progress_status', 200, $payload, 'skip', 'no_progress_status' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== E. THE TWO-STATUS TRAP ===' );
}

/* -------------------------------------------------------------------------
 * E. The trap that would break everything if read wrong.
 * ---------------------------------------------------------------------- */

// Top-level `Status` is the order TYPE. On this store it reads "Active" on
// every order from creation onward. If the tracker ever read it as
// fulfilment, every order would be marked shipped the minute it was placed.
$payload = bhp_tracker_payload( array( 'Status' => 'Created', 'IsDispatched' => false ) );
$payload['Status'] = 'Active';
bhp_tracker_case(
	'Order.Status="Active" with Progress.Status="Created" -> skip (order TYPE is never read as fulfilment)',
	200,
	$payload,
	'skip',
	'not_dispatched'
);

$payload = bhp_tracker_payload( array( 'Status' => 'SentToPrint', 'IsDispatched' => false ) );
$payload['Status'] = 'BatchPayment';
bhp_tracker_case(
	'Order.Status="BatchPayment" with Progress.Status="SentToPrint" -> skip',
	200,
	$payload,
	'skip',
	'not_dispatched'
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== F. Transport and HTTP failures ===' );
}

/* -------------------------------------------------------------------------
 * F. Failures. None may dispatch.
 * ---------------------------------------------------------------------- */

bhp_tracker_case( 'HTTP 500 -> error/http_error', 500, 'Internal Server Error', 'error', 'http_error' );
bhp_tracker_case( 'HTTP 502 -> error/http_error', 502, '', 'error', 'http_error' );
bhp_tracker_case( 'HTTP 401 Invalid Token -> error/http_error', 401, 'Invalid Token', 'error', 'http_error' );
bhp_tracker_case( 'HTTP 403 -> error/http_error', 403, '', 'error', 'http_error' );
bhp_tracker_case( 'HTTP 400 -> error/http_error', 400, 'Either a PodRef or DocRef is required', 'error', 'http_error' );
bhp_tracker_case( 'HTTP 404 -> error/order_not_found', 404, 'Order not found', 'error', 'order_not_found' );
bhp_tracker_case( 'HTTP 429 rate limited -> error/http_error', 429, '', 'error', 'http_error' );

bhp_tracker_case( 'HTTP 200 with an empty body -> error/empty_body', 200, '', 'error', 'empty_body' );
bhp_tracker_case( 'HTTP 200 with whitespace only -> error/empty_body', 200, "   \n ", 'error', 'empty_body' );
bhp_tracker_case( 'HTTP 200 with an HTML error page -> error/unparseable_body', 200, '<html><body>Gateway</body></html>', 'error', 'unparseable_body' );
bhp_tracker_case( 'HTTP 200 with a bare JSON string -> error/unparseable_body', 200, '"Dispatched"', 'error', 'unparseable_body' );
bhp_tracker_case( 'HTTP 200 with JSON null -> error/unparseable_body', 200, 'null', 'error', 'unparseable_body' );

bhp_tracker_case(
	'cURL transport failure -> error/transport_error',
	null,
	null,
	'error',
	'transport_error',
	'cURL error 28: Operation timed out'
);

// A transport error must win even when a stale body is somehow present.
bhp_tracker_case(
	'Transport error alongside a dispatched-looking body -> error/transport_error (never dispatch)',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ), bhp_tracker_full_tracking() ),
	'error',
	'transport_error',
	'cURL error 6: Could not resolve host'
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== G. The Variant B four-field gate ===' );
}

/* -------------------------------------------------------------------------
 * G. Deck §3.2 Variant B: all four fields, or the email carries no tracking.
 * ---------------------------------------------------------------------- */

$partial_cases = array(
	'ServName'       => 'carrier',
	'TrackingNumber' => 'tracking number',
	'CombinedURL'    => 'tracking URL',
);

foreach ( $partial_cases as $missing_key => $label ) {
	$tracking = bhp_tracker_full_tracking();
	$tracking[ $missing_key ] = '';

	$out = bhp_tracker_case(
		'Dispatch with the ' . $label . ' missing -> still dispatch (the books really did move)',
		200,
		bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ), $tracking ),
		'dispatch',
		'dispatched'
	);

	bhp_tracker_assert(
		'  ... but the Variant B gate reports INCOMPLETE without the ' . $label,
		false === $out['evidence']['variant_b_complete']
	);
}

$out = bhp_tracker_case(
	'Dispatch with no Tracking object at all -> still dispatch',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ) ),
	'dispatch',
	'dispatched'
);
bhp_tracker_assert( '  ... Variant B gate INCOMPLETE with no Tracking object', false === $out['evidence']['variant_b_complete'] );
bhp_tracker_assert( '  ... carrier reads as an empty string, never as a guess', '' === $out['evidence']['carrier'] );

$note = BHP_Bookvault_Tracker::build_note( $out['evidence'] );
bhp_tracker_assert( '  ... the order note says "not supplied" rather than inventing a carrier', false !== strpos( $note, 'Carrier: not supplied' ), $note );
bhp_tracker_assert( '  ... the order note states the four-field gate result', false !== strpos( $note, 'Not all four dispatch fields present' ) );

$out = bhp_tracker_case(
	'Dispatch with full tracking -> note carries the real link',
	200,
	bhp_tracker_payload( array( 'Status' => 'Dispatched', 'IsDispatched' => true, 'Dispatched' => $dispatched_at ), bhp_tracker_full_tracking() ),
	'dispatch',
	'dispatched'
);
$note = BHP_Bookvault_Tracker::build_note( $out['evidence'] );
bhp_tracker_assert( '  ... the order note carries the tracking link', false !== strpos( $note, 'https://tools.usps.com/' ), $note );
bhp_tracker_assert( '  ... the order note names the Bookvault reference', false !== strpos( $note, 'BV2845712' ) );
bhp_tracker_assert( '  ... the order note explains that Completed sends the email', false !== strpos( $note, 'sends the shipping confirmation email' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '=== H. Configuration and credential handling ===' );
}

/* -------------------------------------------------------------------------
 * H. Configuration defaults and the credential-redaction contract.
 * ---------------------------------------------------------------------- */

$cred = BHP_Bookvault_Tracker::credential_state();

bhp_tracker_assert(
	'credential_state() returns only present/length/source/well_formed and no value',
	array( 'present', 'length', 'source', 'well_formed' ) === array_keys( $cred ),
	implode( ',', array_keys( $cred ) )
);

$flat = wp_json_encode( $cred );
bhp_tracker_assert( 'credential_state() output contains no "bv_" string', false === strpos( $flat, 'bv_' ), $flat );

bhp_tracker_assert(
	'Dry-run defaults to TRUE when the option has never been set',
	'1' === (string) get_option( BHP_Bookvault_Tracker::OPTION_DRY, '1' )
);

bhp_tracker_assert(
	'The kill switch defaults to enabled',
	true === BHP_Bookvault_Tracker::is_enabled()
);

add_filter( 'bhp_tracker_enabled', '__return_false' );
bhp_tracker_assert( 'bhp_tracker_enabled=false turns the tracker off', false === BHP_Bookvault_Tracker::is_enabled() );
remove_filter( 'bhp_tracker_enabled', '__return_false' );
bhp_tracker_assert( 'Removing the filter turns it back on', true === BHP_Bookvault_Tracker::is_enabled() );

bhp_tracker_assert(
	'Only `processing` is a candidate status by default',
	array( 'processing' ) === BHP_Bookvault_Tracker::candidate_statuses(),
	implode( ',', BHP_Bookvault_Tracker::candidate_statuses() )
);

$schedules = apply_filters( 'cron_schedules', array() );
bhp_tracker_assert(
	'The custom cron schedule exists and is 3 hours (inside the 2-4h band)',
	isset( $schedules[ BHP_Bookvault_Tracker::CRON_SCHEDULE ] ) && 10800 === (int) $schedules[ BHP_Bookvault_Tracker::CRON_SCHEDULE ]['interval'],
	isset( $schedules[ BHP_Bookvault_Tracker::CRON_SCHEDULE ] ) ? (string) $schedules[ BHP_Bookvault_Tracker::CRON_SCHEDULE ]['interval'] : 'absent'
);

bhp_tracker_assert(
	'is_real_timestamp() rejects the empty string',
	false === BHP_Bookvault_Tracker::is_real_timestamp( '' )
);
bhp_tracker_assert(
	'is_real_timestamp() accepts a real ISO 8601 moment',
	true === BHP_Bookvault_Tracker::is_real_timestamp( $dispatched_at )
);
bhp_tracker_assert(
	'is_real_timestamp() accepts a Bookvault-style offset timestamp',
	true === BHP_Bookvault_Tracker::is_real_timestamp( gmdate( 'Y-m-d\TH:i:s', time() - 7200 ) . '+00:00' )
);

/* -------------------------------------------------------------------------
 * RESULT
 * ---------------------------------------------------------------------- */

$bhp_tracker_pass = (int) $GLOBALS['bhp_tracker_pass'];
$bhp_tracker_fail = (array) $GLOBALS['bhp_tracker_fail'];
$total            = $bhp_tracker_pass + count( $bhp_tracker_fail );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::log( '' );
	WP_CLI::log( str_repeat( '=', 62 ) );

	if ( empty( $bhp_tracker_fail ) ) {
		WP_CLI::success( sprintf( 'STATE MACHINE: %d/%d assertions passed.', $bhp_tracker_pass, $total ) );
	} else {
		foreach ( $bhp_tracker_fail as $f ) {
			WP_CLI::warning( 'FAILED: ' . $f );
		}
		WP_CLI::error( sprintf( 'STATE MACHINE: %d of %d assertions FAILED.', count( $bhp_tracker_fail ), $total ) );
	}
}
