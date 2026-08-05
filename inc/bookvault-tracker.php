<?php
/**
 * Bookvault dispatch tracker.
 *
 * Polls the Bookvault v3 API for orders that WooCommerce still has in
 * `processing`, and — only on an unambiguous dispatch signal — records the
 * evidence on the order and transitions it to `completed`, which is what
 * fires WooCommerce's E2 "Your books have shipped" email.
 *
 * ---------------------------------------------------------------------
 * THE ONE RULE THIS FILE EXISTS TO ENFORCE
 * ---------------------------------------------------------------------
 * E2's approved copy says "Your books have shipped." That sentence is not
 * derivable from the WordPress database. It is true only under the
 * operating rule recorded in `inc/transactional-emails.php`:
 *
 *   "an order is marked Completed in wp-admin only AFTER Bookvault has
 *    been seen to show it dispatched."
 *
 * This file is the mechanical enforcement of that rule. Therefore:
 *
 *   ⛔ AMBIGUITY NEVER PRODUCES A TRANSITION.
 *      Any transport error, any non-200, any unparseable body, any missing
 *      field, any unrecognised status value, any state that contradicts
 *      itself — all of them log, skip, and retry on the next run.
 *      A missed dispatch costs a few hours. A false "shipped" costs trust.
 *
 * ---------------------------------------------------------------------
 * TWO FIELDS ARE BOTH CALLED "Status". THEY ARE NOT THE SAME FIELD.
 * ---------------------------------------------------------------------
 * Verified against the live v3 OpenAPI document (`/v3/swagger/docs/v3`,
 * read 2026-08-03):
 *
 *   Order.Status          enum Draft|Active|PreSale|BatchPayment
 *                         -> this is the order TYPE. It is NOT fulfilment.
 *                            Every live order here reads "Active" forever.
 *   Order.Progress.Status enum Created|Acknowledged|SentToPrint|Batched|
 *                              Printed|Dispatched|Invoiced
 *                         -> this is fulfilment state.
 *
 * Reading the first one as fulfilment would mark every order shipped the
 * moment it was created. The tracker reads `Progress` and never `Status`.
 *
 * ---------------------------------------------------------------------
 * WHAT THIS FILE DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------
 * ⛔ It never writes `Notifications.NotifyCustomer`, and never sends any
 *    request to Bookvault other than `GET /v3/Order`. Per the v3 schema
 *    that flag emails "the email registered to the account" — Andrew — not
 *    the buyer, so switching it on would look like customer notification
 *    and would not be.
 * ⛔ It never changes E2's copy. Deck §3.2 Variant B (a tracking-number
 *    email) is marked "DO NOT BUILD NOW" and carries its own Andrew gate.
 *    This file records the four Variant B fields on the order so that
 *    build becomes trivial later, and changes nothing the customer reads.
 * ⛔ It never touches a product, price, coupon, stock, shipping, tax or
 *    payment record.
 * ⛔ It never logs, echoes or stores the API key.
 *
 * ---------------------------------------------------------------------
 * SWITCHES
 * ---------------------------------------------------------------------
 *   filter `bhp_tracker_enabled`   bool, default TRUE. Master kill switch.
 *   option `bhp_bookvault_tracker_dry_run`
 *                                  '1' = DRY (default), '0' = LIVE.
 *   filter `bhp_tracker_dry_run`   bool, wins over the option.
 *   option `bhp_bookvault_api_key` the credential. Constant
 *                                  BHP_BOOKVAULT_API_KEY wins over it.
 *
 * DRY mode reads the API and writes the log, and writes NOTHING to any
 * order: no meta, no note, no status change, no email. That is the mode
 * this ships in.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bookvault dispatch tracker.
 */
class BHP_Bookvault_Tracker {

	const CRON_HOOK      = 'bhp_bookvault_tracker_poll';
	const CRON_SCHEDULE  = 'bhp_bookvault_three_hours';
	const OPTION_LOG     = 'bhp_bookvault_tracker_log';
	const OPTION_LAST    = 'bhp_bookvault_tracker_last_run';
	const OPTION_DRY     = 'bhp_bookvault_tracker_dry_run';
	const OPTION_KEY     = 'bhp_bookvault_api_key';
	const API_ENDPOINT   = 'https://api.bookvault.app/v3/Order';
	const LOG_MAX        = 300;

	/** Order meta written on a confirmed dispatch (LIVE mode only). */
	const META_RECORDED  = '_bhp_bv_dispatch_recorded';
	const META_AT        = '_bhp_bv_dispatched_at';
	const META_CARRIER   = '_bhp_bv_carrier';
	const META_TRACKING  = '_bhp_bv_tracking_number';
	const META_URL       = '_bhp_bv_tracking_url';
	const META_PROGRESS  = '_bhp_bv_progress_status';
	const META_COMPLETE  = '_bhp_bv_tracking_complete';

	/**
	 * The `Progress.Status` values the v3 schema defines. Anything outside
	 * this set is an unknown the tracker refuses to interpret.
	 *
	 * @var string[]
	 */
	const KNOWN_STATUSES = array(
		'Created',
		'Acknowledged',
		'SentToPrint',
		'Batched',
		'Printed',
		'Dispatched',
		'Invoiced',
	);

	/**
	 * The only `Progress.Status` values that may coexist with
	 * `IsDispatched = true`.
	 *
	 * `Invoiced` is included deliberately: it comes AFTER `Dispatched` in
	 * the enum, so an order can legitimately be dispatched and already
	 * invoiced by the time a poll sees it. Requiring `Dispatched` exactly
	 * would silently miss every order that moved two steps between polls.
	 *
	 * @var string[]
	 */
	const POST_DISPATCH_STATUSES = array( 'Dispatched', 'Invoiced' );

	/* =====================================================================
	 * PURE STATE MACHINE
	 *
	 * `evaluate()` calls no WordPress function on purpose, so the whole
	 * decision surface can be unit-tested without a WordPress bootstrap.
	 * See tests/bookvault-tracker-state-machine-test.php.
	 * ================================================================== */

	/**
	 * Decide what a single API response means.
	 *
	 * @param int|null    $http_code    HTTP status, or null for a transport failure.
	 * @param string|null $raw_body     Raw response body, or null.
	 * @param string|null $transport_error Transport error message, if any.
	 * @return array{action:string,reason:string,detail:string,evidence:array}
	 */
	public static function evaluate( $http_code, $raw_body, $transport_error = null ) {
		if ( null !== $transport_error && '' !== $transport_error ) {
			return self::outcome( 'error', 'transport_error', (string) $transport_error );
		}

		$http_code = (int) $http_code;

		if ( 200 !== $http_code ) {
			// 404 is a real answer, not a failure: Bookvault does not know
			// this reference. It still must never advance an order.
			$reason = ( 404 === $http_code ) ? 'order_not_found' : 'http_error';
			return self::outcome( 'error', $reason, 'HTTP ' . $http_code );
		}

		if ( ! is_string( $raw_body ) || '' === trim( $raw_body ) ) {
			return self::outcome( 'error', 'empty_body', 'HTTP 200 with an empty body' );
		}

		$data = json_decode( $raw_body, true );

		if ( ! is_array( $data ) ) {
			return self::outcome( 'error', 'unparseable_body', 'HTTP 200 body is not JSON' );
		}

		if ( ! isset( $data['Progress'] ) || ! is_array( $data['Progress'] ) ) {
			return self::outcome( 'skip', 'no_progress_object', 'Response carries no Progress object' );
		}

		$progress = $data['Progress'];
		$status   = isset( $progress['Status'] ) && is_string( $progress['Status'] ) ? trim( $progress['Status'] ) : '';

		if ( '' === $status ) {
			return self::outcome( 'skip', 'no_progress_status', 'Progress.Status is absent or empty' );
		}

		if ( ! in_array( $status, self::KNOWN_STATUSES, true ) ) {
			// A value the schema does not define. Bookvault may have added
			// one. Refuse to guess what it means.
			return self::outcome( 'skip', 'unknown_progress_status', 'Progress.Status = ' . $status );
		}

		$is_dispatched = isset( $progress['IsDispatched'] ) ? $progress['IsDispatched'] : null;

		// Strict true only. The string "false", 0, "0" and null are all
		// NOT dispatched, and a loose comparison would read "false" as true.
		if ( true !== $is_dispatched ) {
			return self::outcome(
				'skip',
				'not_dispatched',
				'Progress.Status = ' . $status . ', IsDispatched = ' . self::describe( $is_dispatched ),
				array( 'progress_status' => $status )
			);
		}

		$dispatched_at = isset( $progress['Dispatched'] ) && is_string( $progress['Dispatched'] ) ? trim( $progress['Dispatched'] ) : '';

		if ( '' === $dispatched_at || ! self::is_real_timestamp( $dispatched_at ) ) {
			// IsDispatched says yes but there is no real moment attached to
			// it. That is exactly the ambiguity that must never become an
			// email claiming the books moved.
			return self::outcome(
				'skip',
				'dispatched_without_timestamp',
				'IsDispatched = true but Progress.Dispatched = ' . self::describe( $dispatched_at ),
				array( 'progress_status' => $status )
			);
		}

		if ( ! in_array( $status, self::POST_DISPATCH_STATUSES, true ) ) {
			// IsDispatched = true while the status is still pre-dispatch.
			// The record contradicts itself; do not resolve it here.
			return self::outcome(
				'skip',
				'contradictory_state',
				'IsDispatched = true but Progress.Status = ' . $status,
				array( 'progress_status' => $status )
			);
		}

		$tracking = isset( $data['Tracking'] ) && is_array( $data['Tracking'] ) ? $data['Tracking'] : array();

		$evidence = array(
			'progress_status' => $status,
			'dispatched_at'   => $dispatched_at,
			'carrier'         => self::str( $tracking, 'ServName' ),
			'service_detail'  => self::str( $tracking, 'ServDetail' ),
			'tracking_number' => self::str( $tracking, 'TrackingNumber' ),
			'tracking_url'    => self::str( $tracking, 'CombinedURL' ),
			'tracked'         => isset( $tracking['Tracked'] ) && true === $tracking['Tracked'],
			'pod_ref'         => isset( $data['PodRef'] ) ? (string) $data['PodRef'] : '',
			'doc_ref'         => self::str( $data, 'DocRef' ),
		);

		// Deck §3.2 Variant B's gate, evaluated but NOT acted on here: all
		// four of timestamp + carrier + tracking number + tracking URL.
		// Recorded so a future Variant B build can trust one boolean instead
		// of re-deriving the rule.
		$evidence['variant_b_complete'] = (
			'' !== $evidence['dispatched_at']
			&& '' !== $evidence['carrier']
			&& '' !== $evidence['tracking_number']
			&& '' !== $evidence['tracking_url']
		);

		return self::outcome( 'dispatch', 'dispatched', 'Dispatched ' . $dispatched_at, $evidence );
	}

	/**
	 * Build an outcome array.
	 *
	 * @param string $action   dispatch|skip|error.
	 * @param string $reason   Machine-readable reason.
	 * @param string $detail   Human-readable detail.
	 * @param array  $evidence Evidence payload.
	 * @return array
	 */
	private static function outcome( $action, $reason, $detail, $evidence = array() ) {
		return array(
			'action'   => $action,
			'reason'   => $reason,
			'detail'   => $detail,
			'evidence' => $evidence,
		);
	}

	/**
	 * Is this a timestamp that names a real moment?
	 *
	 * Rejects the empty string, the .NET zero date, the Unix epoch and
	 * anything `strtotime()` cannot read. Bookvault returns ISO 8601.
	 *
	 * @param string $value Candidate timestamp.
	 * @return bool
	 */
	public static function is_real_timestamp( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return false;
		}

		$value = trim( $value );

		// The two "no date" sentinels an ASP.NET API emits.
		if ( 0 === strpos( $value, '0001-01-01' ) || 0 === strpos( $value, '1970-01-01' ) ) {
			return false;
		}

		$ts = strtotime( $value );

		if ( false === $ts || $ts <= 0 ) {
			return false;
		}

		// A dispatch date in the far future is a data fault, not a dispatch.
		if ( $ts > time() + DAY_IN_SECONDS ) {
			return false;
		}

		return true;
	}

	/**
	 * Safe string read from an array.
	 *
	 * @param array  $arr Source array.
	 * @param string $key Key.
	 * @return string
	 */
	private static function str( $arr, $key ) {
		return ( isset( $arr[ $key ] ) && is_string( $arr[ $key ] ) ) ? trim( $arr[ $key ] ) : '';
	}

	/**
	 * Describe a value for a log line without lying about its type.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function describe( $value ) {
		if ( null === $value ) {
			return 'null';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_scalar( $value ) ) {
			return '"' . (string) $value . '"';
		}
		return '[' . gettype( $value ) . ']';
	}

	/* =====================================================================
	 * CONFIGURATION
	 * ================================================================== */

	/**
	 * Master kill switch.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) apply_filters( 'bhp_tracker_enabled', true );
	}

	/**
	 * Is the tracker in dry-run (read-only) mode?
	 *
	 * Defaults to TRUE. A tracker that has never been switched on must not
	 * change an order the first time it runs.
	 *
	 * @return bool
	 */
	public static function is_dry_run() {
		$option = get_option( self::OPTION_DRY, '1' );
		$dry    = ( '0' !== (string) $option );

		return (bool) apply_filters( 'bhp_tracker_dry_run', $dry );
	}

	/**
	 * The API credential.
	 *
	 * ⛔ NEVER log, echo, note or store the return value of this method.
	 *    Callers test it for emptiness and pass it straight to the request
	 *    header. `credential_state()` is what may be shown to a human.
	 *
	 * @return string
	 */
	private static function api_key() {
		if ( defined( 'BHP_BOOKVAULT_API_KEY' ) && '' !== (string) BHP_BOOKVAULT_API_KEY ) {
			return (string) BHP_BOOKVAULT_API_KEY;
		}

		return (string) get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Report the credential by shape only, never by value.
	 *
	 * @return array{present:bool,length:int,source:string,well_formed:bool}
	 */
	public static function credential_state() {
		$from_constant = defined( 'BHP_BOOKVAULT_API_KEY' ) && '' !== (string) BHP_BOOKVAULT_API_KEY;
		$key           = self::api_key();

		return array(
			'present'     => ( '' !== $key ),
			'length'      => strlen( $key ),
			'source'      => $from_constant ? 'constant' : ( '' !== $key ? 'option' : 'none' ),
			// Bookvault's documented key prefix. A key without it will not
			// authenticate, and saying so early beats 401-ing every run.
			'well_formed' => ( 0 === strpos( $key, 'bv_' ) ),
		);
	}

	/**
	 * The order statuses the tracker considers candidates.
	 *
	 * @return string[]
	 */
	public static function candidate_statuses() {
		return (array) apply_filters( 'bhp_tracker_candidate_statuses', array( 'processing' ) );
	}

	/* =====================================================================
	 * SCHEDULING
	 * ================================================================== */

	/**
	 * Register the custom three-hour schedule.
	 *
	 * Inside the 2-4 hour band the brief specifies. Two live orders is a
	 * trivial load on Bookvault; three hours is courteous and still gives
	 * eight checks a day.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public static function add_schedule( $schedules ) {
		$schedules[ self::CRON_SCHEDULE ] = array(
			'interval' => 3 * HOUR_IN_SECONDS,
			'display'  => __( 'Every 3 hours (Bookvault tracker)', 'brave-hearts' ),
		);

		return $schedules;
	}

	/**
	 * Ensure the cron event exists while the tracker is enabled, and does
	 * not exist while it is not.
	 *
	 * @return void
	 */
	public static function maybe_schedule() {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( ! self::is_enabled() ) {
			if ( $scheduled ) {
				wp_unschedule_event( $scheduled, self::CRON_HOOK );
			}
			return;
		}

		if ( ! $scheduled ) {
			wp_schedule_event( time() + ( 5 * MINUTE_IN_SECONDS ), self::CRON_SCHEDULE, self::CRON_HOOK );
		}
	}

	/**
	 * Remove the cron event. Bound to `switch_theme` so the tracker leaves
	 * nothing running behind a theme rollback.
	 *
	 * @return void
	 */
	public static function unschedule() {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}

		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/* =====================================================================
	 * THE RUN
	 * ================================================================== */

	/**
	 * Poll Bookvault and act on the results.
	 *
	 * @param array $args {
	 *     @type bool     $dry_run  Override the mode for this run.
	 *     @type int      $order_id Restrict the run to one order.
	 *     @type int      $limit    Max orders in this run.
	 *     @type callable $logger   Optional line logger, for WP-CLI.
	 * }
	 * @return array Summary.
	 */
	public static function run( $args = array() ) {
		$defaults = array(
			'dry_run'  => null,
			'order_id' => 0,
			'limit'    => (int) apply_filters( 'bhp_tracker_batch_limit', 25 ),
			'logger'   => null,
			'trigger'  => 'cron',
		);

		$args   = array_merge( $defaults, (array) $args );
		$logger = is_callable( $args['logger'] ) ? $args['logger'] : null;
		$say    = static function ( $line ) use ( $logger ) {
			if ( $logger ) {
				call_user_func( $logger, $line );
			}
		};

		$summary = array(
			'started_at' => gmdate( 'c' ),
			'trigger'    => $args['trigger'],
			'mode'       => 'dry',
			'examined'   => 0,
			'dispatched' => 0,
			'skipped'    => 0,
			'errors'     => 0,
			'halted'     => '',
			'orders'     => array(),
		);

		if ( ! self::is_enabled() ) {
			$summary['halted'] = 'disabled_by_filter';
			$say( 'Tracker disabled by the bhp_tracker_enabled filter. Nothing done.' );
			self::record_run( $summary );
			return $summary;
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			$summary['halted'] = 'woocommerce_missing';
			$say( 'WooCommerce is not loaded. Nothing done.' );
			self::record_run( $summary );
			return $summary;
		}

		$dry = ( null === $args['dry_run'] ) ? self::is_dry_run() : (bool) $args['dry_run'];
		$summary['mode'] = $dry ? 'dry' : 'live';

		$cred = self::credential_state();

		if ( ! $cred['present'] ) {
			$summary['halted'] = 'no_credential';
			$say( 'No Bookvault API credential configured. Nothing polled, nothing changed.' );
			self::log( 'halt', 0, 'no_credential', 'Set option ' . self::OPTION_KEY . ' or define BHP_BOOKVAULT_API_KEY.' );
			self::record_run( $summary );
			return $summary;
		}

		if ( ! $cred['well_formed'] ) {
			// Not fatal — Bookvault could change the prefix — but worth
			// saying once per run rather than reading eight 401s a day.
			$say( 'Warning: the stored credential does not start with the documented "bv_" prefix.' );
		}

		$orders = self::candidate_orders( $args['order_id'], $args['limit'] );

		$say( sprintf( 'Mode: %s. Candidate orders: %d.', strtoupper( $summary['mode'] ), count( $orders ) ) );

		foreach ( $orders as $order ) {
			$summary['examined']++;

			$order_id = $order->get_id();
			$pod_ref  = trim( (string) $order->get_meta( 'BVRef' ) );

			$result = self::process_order( $order, $pod_ref, $dry, $say );

			$summary['orders'][] = array(
				'order_id' => $order_id,
				'pod_ref'  => $pod_ref,
				'action'   => $result['action'],
				'reason'   => $result['reason'],
			);

			if ( 'dispatch' === $result['action'] ) {
				$summary['dispatched']++;
			} elseif ( 'error' === $result['action'] ) {
				$summary['errors']++;
			} else {
				$summary['skipped']++;
			}

			// API courtesy. Two orders today, but the loop must stay polite
			// if the store ever has two hundred.
			usleep( 250000 );
		}

		$summary['finished_at'] = gmdate( 'c' );

		$say(
			sprintf(
				'Done. examined=%d dispatched=%d skipped=%d errors=%d',
				$summary['examined'],
				$summary['dispatched'],
				$summary['skipped'],
				$summary['errors']
			)
		);

		self::record_run( $summary );

		return $summary;
	}

	/**
	 * Handle one order end to end.
	 *
	 * @param WC_Order $order   Order.
	 * @param string   $pod_ref Bookvault PodRef from the `BVRef` meta.
	 * @param bool     $dry     Dry-run.
	 * @param callable $say     Line logger.
	 * @return array
	 */
	private static function process_order( $order, $pod_ref, $dry, $say ) {
		$order_id = $order->get_id();

		if ( '' === $pod_ref || ! ctype_digit( $pod_ref ) ) {
			$out = self::outcome( 'skip', 'no_bvref', 'BVRef meta is absent or not numeric' );
			self::log( $out['action'], $order_id, $out['reason'], $out['detail'] );
			$say( sprintf( '  #%d: SKIP (%s)', $order_id, $out['reason'] ) );
			return $out;
		}

		// Idempotency guard 1: this order has already been recorded as
		// dispatched by a previous run. Never act twice.
		if ( '' !== (string) $order->get_meta( self::META_RECORDED ) ) {
			$out = self::outcome( 'skip', 'already_recorded', 'Dispatch already recorded on this order' );
			self::log( $out['action'], $order_id, $out['reason'], $out['detail'] );
			$say( sprintf( '  #%d: SKIP (%s)', $order_id, $out['reason'] ) );
			return $out;
		}

		// Idempotency guard 2: the order is no longer a candidate. Somebody
		// (or something) moved it since the query ran.
		if ( ! in_array( $order->get_status(), self::candidate_statuses(), true ) ) {
			$out = self::outcome( 'skip', 'status_changed', 'Order status is now ' . $order->get_status() );
			self::log( $out['action'], $order_id, $out['reason'], $out['detail'] );
			$say( sprintf( '  #%d: SKIP (%s)', $order_id, $out['reason'] ) );
			return $out;
		}

		$response = self::fetch( $pod_ref );
		$outcome  = self::evaluate( $response['code'], $response['body'], $response['error'] );

		self::log( $outcome['action'], $order_id, $outcome['reason'], $outcome['detail'], $pod_ref );

		$say(
			sprintf(
				'  #%d (BV%s): %s - %s',
				$order_id,
				$pod_ref,
				strtoupper( $outcome['action'] ),
				$outcome['detail']
			)
		);

		if ( 'dispatch' !== $outcome['action'] ) {
			return $outcome;
		}

		if ( $dry ) {
			$say( sprintf( '  #%d: DRY RUN - would complete this order. Nothing written.', $order_id ) );
			self::log( 'dry', $order_id, 'would_complete', $outcome['detail'], $pod_ref );
			return self::outcome( 'skip', 'dry_run_would_complete', $outcome['detail'], $outcome['evidence'] );
		}

		self::apply_dispatch( $order, $outcome['evidence'] );
		$say( sprintf( '  #%d: COMPLETED. E2 has been triggered.', $order_id ) );

		return $outcome;
	}

	/**
	 * Write the evidence and transition the order.
	 *
	 * Order of operations matters: meta and the evidence note are written
	 * BEFORE the status change, so that anything hooked to the transition
	 * — including the E2 email — can already see the dispatch record.
	 *
	 * @param WC_Order $order    Order.
	 * @param array    $evidence Evidence from `evaluate()`.
	 * @return void
	 */
	private static function apply_dispatch( $order, $evidence ) {
		$order->update_meta_data( self::META_RECORDED, gmdate( 'c' ) );
		$order->update_meta_data( self::META_AT, $evidence['dispatched_at'] );
		$order->update_meta_data( self::META_PROGRESS, $evidence['progress_status'] );
		$order->update_meta_data( self::META_CARRIER, $evidence['carrier'] );
		$order->update_meta_data( self::META_TRACKING, $evidence['tracking_number'] );
		$order->update_meta_data( self::META_URL, $evidence['tracking_url'] );
		$order->update_meta_data( self::META_COMPLETE, $evidence['variant_b_complete'] ? 'yes' : 'no' );
		$order->save();

		$order->add_order_note( self::build_note( $evidence ), 0, false );

		/**
		 * Fires immediately before the tracker completes an order.
		 *
		 * @param WC_Order $order    Order.
		 * @param array    $evidence Dispatch evidence.
		 */
		do_action( 'bhp_tracker_before_complete', $order, $evidence );

		// This is the line that sends E2.
		$order->update_status(
			'completed',
			__( 'Bookvault dispatch confirmed by the tracker.', 'brave-hearts' ),
			false
		);
	}

	/**
	 * The private order note. This is the audit trail a human reads.
	 *
	 * @param array $evidence Evidence.
	 * @return string
	 */
	public static function build_note( $evidence ) {
		$lines = array();

		$lines[] = 'Bookvault dispatch confirmed (automated tracker).';
		$lines[] = 'Bookvault reference: BV' . ( '' !== $evidence['pod_ref'] ? $evidence['pod_ref'] : 'unknown' );
		$lines[] = 'Progress.Status: ' . $evidence['progress_status'] . ' (IsDispatched = true)';
		$lines[] = 'Dispatched: ' . $evidence['dispatched_at'];

		$lines[] = 'Carrier: ' . ( '' !== $evidence['carrier'] ? $evidence['carrier'] : 'not supplied' );
		$lines[] = 'Tracking number: ' . ( '' !== $evidence['tracking_number'] ? $evidence['tracking_number'] : 'not supplied' );
		$lines[] = 'Tracking link: ' . ( '' !== $evidence['tracking_url'] ? $evidence['tracking_url'] : 'not supplied' );

		$lines[] = $evidence['variant_b_complete']
			? 'All four dispatch fields present.'
			: 'Not all four dispatch fields present. Customer email carries no tracking detail.';

		$lines[] = 'Order moved to Completed, which sends the shipping confirmation email.';

		return implode( "\n", $lines );
	}

	/* =====================================================================
	 * IO
	 * ================================================================== */

	/**
	 * Candidate orders.
	 *
	 * HPOS-safe: `wc_get_orders()` reads whichever store is active. A
	 * `wp_posts` query would return zero on this store.
	 *
	 * @param int $order_id Restrict to one order, or 0.
	 * @param int $limit    Max results.
	 * @return WC_Order[]
	 */
	private static function candidate_orders( $order_id = 0, $limit = 25 ) {
		if ( $order_id > 0 ) {
			$order = wc_get_order( $order_id );
			return $order ? array( $order ) : array();
		}

		$orders = wc_get_orders(
			array(
				'status'  => self::candidate_statuses(),
				'limit'   => max( 1, (int) $limit ),
				'orderby' => 'date',
				'order'   => 'ASC',
				'type'    => 'shop_order',
			)
		);

		$out = array();

		foreach ( (array) $orders as $order ) {
			if ( $order instanceof WC_Order && '' !== trim( (string) $order->get_meta( 'BVRef' ) ) ) {
				$out[] = $order;
			}
		}

		return $out;
	}

	/**
	 * GET /v3/Order?PodRef=<ref>.
	 *
	 * `PodRef` and not `DocRef`: the Bookvault plugin stores Bookvault's own
	 * numeric reference in the `BVRef` order meta at creation time, which is
	 * an exact key. `DocRef` is our own string and depends on a store-id
	 * pattern that would break silently if the store were ever re-linked.
	 *
	 * @param string $pod_ref Numeric Bookvault reference.
	 * @return array{code:int|null,body:string|null,error:string|null}
	 */
	private static function fetch( $pod_ref ) {
		$url = add_query_arg( array( 'PodRef' => $pod_ref ), self::API_ENDPOINT );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					// Documented scheme: `Authorization: basic bv_<key>`.
					// Not HTTP Basic; the key is the whole credential.
					'Authorization' => 'basic ' . self::api_key(),
					'Accept'        => 'application/json',
				),
				'user-agent' => 'BraveHeartsDispatchTracker/1.0 (+' . home_url( '/' ) . ')',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'code'  => null,
				'body'  => null,
				'error' => $response->get_error_message(),
			);
		}

		return array(
			'code'  => (int) wp_remote_retrieve_response_code( $response ),
			'body'  => (string) wp_remote_retrieve_body( $response ),
			'error' => null,
		);
	}

	/* =====================================================================
	 * LOGGING
	 * ================================================================== */

	/**
	 * Append one line to the ring log.
	 *
	 * @param string $action   Action.
	 * @param int    $order_id Order id.
	 * @param string $reason   Reason.
	 * @param string $detail   Detail.
	 * @param string $pod_ref  Bookvault reference.
	 * @return void
	 */
	public static function log( $action, $order_id, $reason, $detail = '', $pod_ref = '' ) {
		$log = get_option( self::OPTION_LOG, array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		array_unshift(
			$log,
			array(
				'at'       => gmdate( 'c' ),
				'action'   => (string) $action,
				'order_id' => (int) $order_id,
				'pod_ref'  => (string) $pod_ref,
				'reason'   => (string) $reason,
				'detail'   => (string) $detail,
			)
		);

		if ( count( $log ) > self::LOG_MAX ) {
			$log = array_slice( $log, 0, self::LOG_MAX );
		}

		update_option( self::OPTION_LOG, $log, false );
	}

	/**
	 * Store the last-run summary.
	 *
	 * @param array $summary Summary.
	 * @return void
	 */
	private static function record_run( $summary ) {
		update_option( self::OPTION_LAST, $summary, false );
	}

	/**
	 * Read the log.
	 *
	 * @param int $limit Max entries.
	 * @return array
	 */
	public static function get_log( $limit = 50 ) {
		$log = get_option( self::OPTION_LOG, array() );

		if ( ! is_array( $log ) ) {
			return array();
		}

		return array_slice( $log, 0, max( 1, (int) $limit ) );
	}

	/**
	 * Read the last-run summary.
	 *
	 * @return array
	 */
	public static function get_last_run() {
		$last = get_option( self::OPTION_LAST, array() );

		return is_array( $last ) ? $last : array();
	}

	/* =====================================================================
	 * BOOTSTRAP
	 * ================================================================== */

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- 3 hours, well above the 15-minute guidance.
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
		add_action( 'switch_theme', array( __CLASS__, 'unschedule' ) );
	}
}

if ( function_exists( 'add_action' ) ) {
	BHP_Bookvault_Tracker::init();
}

/* =========================================================================
 * WP-CLI
 * ====================================================================== */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Bookvault dispatch tracker commands.
	 */
	class BHP_Bookvault_Tracker_CLI {

		/**
		 * Poll Bookvault and report what the tracker would do, or does.
		 *
		 * ## OPTIONS
		 *
		 * [--dry]
		 * : Force dry-run. Reads the API and writes nothing to any order.
		 *
		 * [--live]
		 * : Force live mode: complete dispatched orders and send the email.
		 *
		 * [--order=<id>]
		 * : Restrict the run to a single WooCommerce order id.
		 *
		 * [--limit=<n>]
		 * : Max orders to examine. Default 25.
		 *
		 * ## EXAMPLES
		 *
		 *     wp bhp-tracker run --dry
		 *     wp bhp-tracker run --dry --order=417
		 *     wp bhp-tracker run --live
		 *
		 * @param array $args       Positional args.
		 * @param array $assoc_args Flags.
		 * @return void
		 */
		public function run( $args, $assoc_args ) {
			$dry = null;

			if ( isset( $assoc_args['dry'] ) ) {
				$dry = true;
			}

			if ( isset( $assoc_args['live'] ) ) {
				if ( true === $dry ) {
					WP_CLI::error( 'Pass --dry or --live, not both.' );
				}
				$dry = false;
			}

			$summary = BHP_Bookvault_Tracker::run(
				array(
					'dry_run'  => $dry,
					'order_id' => isset( $assoc_args['order'] ) ? (int) $assoc_args['order'] : 0,
					'limit'    => isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 25,
					'trigger'  => 'wp-cli',
					'logger'   => array( 'WP_CLI', 'log' ),
				)
			);

			if ( '' !== $summary['halted'] ) {
				WP_CLI::warning( 'Halted: ' . $summary['halted'] );
				return;
			}

			WP_CLI::success(
				sprintf(
					'%s run complete. examined=%d dispatched=%d skipped=%d errors=%d',
					strtoupper( $summary['mode'] ),
					$summary['examined'],
					$summary['dispatched'],
					$summary['skipped'],
					$summary['errors']
				)
			);
		}

		/**
		 * Show the tracker's configuration and last run.
		 *
		 * @return void
		 */
		public function status() {
			$cred = BHP_Bookvault_Tracker::credential_state();
			$next = wp_next_scheduled( BHP_Bookvault_Tracker::CRON_HOOK );

			WP_CLI::log( 'Enabled        : ' . ( BHP_Bookvault_Tracker::is_enabled() ? 'yes' : 'no (bhp_tracker_enabled filter)' ) );
			WP_CLI::log( 'Mode           : ' . ( BHP_Bookvault_Tracker::is_dry_run() ? 'DRY (writes nothing)' : 'LIVE (completes orders, sends email)' ) );
			WP_CLI::log( 'Credential     : present=' . ( $cred['present'] ? 'yes' : 'no' ) . ' length=' . $cred['length'] . ' source=' . $cred['source'] . ' bv_prefix=' . ( $cred['well_formed'] ? 'yes' : 'no' ) );
			WP_CLI::log( 'Next scheduled : ' . ( $next ? gmdate( 'c', $next ) : 'not scheduled' ) );

			$last = BHP_Bookvault_Tracker::get_last_run();

			if ( empty( $last ) ) {
				WP_CLI::log( 'Last run       : never' );
				return;
			}

			WP_CLI::log(
				sprintf(
					'Last run       : %s via %s, mode=%s examined=%d dispatched=%d skipped=%d errors=%d%s',
					isset( $last['started_at'] ) ? $last['started_at'] : '?',
					isset( $last['trigger'] ) ? $last['trigger'] : '?',
					isset( $last['mode'] ) ? $last['mode'] : '?',
					isset( $last['examined'] ) ? $last['examined'] : 0,
					isset( $last['dispatched'] ) ? $last['dispatched'] : 0,
					isset( $last['skipped'] ) ? $last['skipped'] : 0,
					isset( $last['errors'] ) ? $last['errors'] : 0,
					( ! empty( $last['halted'] ) ? ' HALTED=' . $last['halted'] : '' )
				)
			);
		}

		/**
		 * Print the tracker log, newest first.
		 *
		 * ## OPTIONS
		 *
		 * [--limit=<n>]
		 * : How many entries. Default 30.
		 *
		 * @param array $args       Positional args.
		 * @param array $assoc_args Flags.
		 * @return void
		 */
		public function log( $args, $assoc_args ) {
			$limit   = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 30;
			$entries = BHP_Bookvault_Tracker::get_log( $limit );

			if ( empty( $entries ) ) {
				WP_CLI::log( 'Log is empty.' );
				return;
			}

			foreach ( $entries as $e ) {
				WP_CLI::log(
					sprintf(
						'%s  %-8s  order=%-6s bv=%-9s %-28s %s',
						$e['at'],
						$e['action'],
						$e['order_id'] ? $e['order_id'] : '-',
						'' !== $e['pod_ref'] ? $e['pod_ref'] : '-',
						$e['reason'],
						$e['detail']
					)
				);
			}
		}
	}

	WP_CLI::add_command( 'bhp-tracker run', array( 'BHP_Bookvault_Tracker_CLI', 'run' ) );
	WP_CLI::add_command( 'bhp-tracker status', array( 'BHP_Bookvault_Tracker_CLI', 'status' ) );
	WP_CLI::add_command( 'bhp-tracker log', array( 'BHP_Bookvault_Tracker_CLI', 'log' ) );
}
