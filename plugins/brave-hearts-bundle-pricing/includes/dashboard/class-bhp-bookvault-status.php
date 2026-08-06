<?php
/**
 * Reads Bookvault routing status from WooCommerce order notes, with the
 * order's own `BVRef` postmeta as a stronger, structured cross-check.
 *
 * Bookvault does not expose routing status via a dedicated database table
 * (confirmed by direct inspection -- no `%bookvault%`/`%bvlt%` table
 * exists) or via any WordPress option. It DOES write one structured
 * postmeta key on an order it actually creates: `BVRef` (the Bookvault
 * order reference, e.g. "2796848") -- confirmed present only on orders
 * that genuinely exist in the Bookvault portal, and absent on every order
 * Bookvault never created. That makes `BVRef` the single most reliable
 * local signal for "does a real Bookvault order exist for this WooCommerce
 * order" -- stronger than parsing note text, which can only describe what
 * happened at one point in time, not Bookvault's current state. See
 * has_bookvault_record() and the source-of-truth hierarchy in
 * docs/bookvault-chronology.md.
 *
 * Beyond BVRef, the only trace of *what* happened is the order note the
 * Bookvault plugin itself writes. Three patterns have been observed
 * directly on real orders (2026-07-06 direct reconciliation against the
 * Bookvault portal, which listed exactly 3 orders total -- see
 * docs/bookvault-chronology.md for the full order-by-order evidence):
 *
 *   Success:  "Order saved with status Active as BV2796848"
 *             "Order saved with status Draft as BV2796764"
 *   Excluded: "Failed to read line_items: Notice - The Bookvault plugin
 *              scans all incoming orders to identify those specifically
 *              intended for Bookvault to fulfill. Based on your current
 *              configuration, this order does not indicate Bookvault as
 *              the selected fulfillment service. As a result, it will not
 *              be processed by Bookvault."
 *
 * The "Excluded" pattern is easy to mistake for a technical failure
 * because the Bookvault plugin's own log message is oddly prefixed
 * "Failed to read line_items:" -- but the message text is Bookvault
 * intentionally declining to process the order (it wasn't flagged for
 * Bookvault fulfillment), not an error. Order #351 hit this exact path
 * and does NOT exist in Bookvault's own order list, confirming "excluded"
 * is the correct read, not "failed". A prior version of this class
 * classified BOTH patterns as a generic failure, which is what produced
 * the incorrect "4 orders needing attention" dashboard count -- see
 * docs/bookvault-chronology.md for the full incident writeup.
 *
 * A genuine technical failure (Bookvault attempted to process the order
 * and hit a real error unrelated to the "not selected for Bookvault"
 * notice) would still match FAILURE_PATTERN without matching
 * EXCLUDED_PATTERN, and is classified 'failure' -- a real action item,
 * unlike 'excluded'.
 *
 * If Bookvault's own note wording ever changes, these patterns will stop
 * matching and every order will report 'unknown' rather than a false
 * positive -- see get_status() below.
 *
 * Chronology: classification reflects the LATEST relevant note, not the
 * first. Walking every note in chronological order and letting the last
 * event decide `status` distinguishes "never routed" from "routed, then
 * something went wrong again" -- two very different situations -- via
 * `had_prior_success` / `failure_count`, which preserve full history.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Bookvault_Status {

	const SUCCESS_PATTERN  = '/Order saved with status (\w+) as (BV\d+)/i';
	const EXCLUDED_PATTERN = '/does not indicate Bookvault as the selected fulfillment service|will not be processed by Bookvault/i';
	const FAILURE_PATTERN  = '/Failed to read line_items/i';

	/**
	 * The earliest point real Bookvault activity is confirmed in this
	 * store's order notes/BVRef data (site-local time). Orders paid before
	 * this cutoff with no Bookvault note and no BVRef are "legacy /
	 * pre-integration" -- Bookvault was never actually wired up to attempt
	 * them, so silence is expected, not a stuck/overdue order.
	 *
	 * Evidence (2026-07-06 direct reconciliation): the last order paid
	 * with zero Bookvault-related notes was #336 (2026-07-02 21:24:41);
	 * the first order paid with a real Bookvault note (even though it was
	 * an "excluded" note, not success) was #351 (2026-07-05 06:30:07).
	 * This constant is set to the midpoint day boundary between those two
	 * confirmed data points -- it is a conservative approximation, not an
	 * exact go-live timestamp. If Andrew has an exact deployment/activation
	 * time for the Bookvault integration, update this constant to that
	 * value instead.
	 */
	const INTEGRATION_LIVE_SINCE = '2026-07-04 00:00:00';

	/**
	 * @param WC_Order $order
	 * @return array see get_status_from_events() for the full shape.
	 */
	public static function get_status( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return self::empty_status();
		}

		$notes  = wc_get_order_notes( array( 'order_id' => $order->get_id(), 'order_by' => 'date_created', 'order' => 'ASC' ) );
		$events = array();
		foreach ( $notes as $note ) {
			$event = self::classify_note_content( $note->content );
			if ( null !== $event ) {
				$event['time']      = $note->date_created ? $note->date_created->date( 'c' ) : null;
				$event['timestamp'] = $note->date_created ? $note->date_created->getTimestamp() : null;
				$events[]           = $event;
			}
		}

		$paid_time      = $order->get_date_paid();
		$paid_timestamp = $paid_time ? $paid_time->getTimestamp() : null;

		$result = self::get_status_from_events( $events, $paid_timestamp );

		// BVRef postmeta outranks note-text inference (see class docblock
		// and docs/bookvault-chronology.md's source-of-truth hierarchy): if
		// Bookvault actually created this order, that fact is authoritative
		// even if the latest note somehow suggests otherwise (e.g. a later,
		// unrelated note pattern match, or a missing/garbled success note).
		$bv_ref = self::get_bvref_meta( $order );
		if ( '' !== $bv_ref && 'routed' !== $result['status'] ) {
			$result['status']         = 'routed';
			$result['bookvault_ref']  = $bv_ref;
			// The state word (Active/Draft) isn't recoverable from BVRef
			// alone -- only the note text carries it. Flag this honestly
			// rather than guessing.
			if ( null === $result['bookvault_state'] ) {
				$result['bookvault_state'] = 'Unknown';
			}
			$result['bookvault_state_is_draft'] = ( 'Draft' === $result['bookvault_state'] );
		}

		return $result;
	}

	/**
	 * Reads the Bookvault-assigned reference directly from order postmeta
	 * (`BVRef`) -- present only on orders Bookvault actually created.
	 * Confirmed via direct inspection: present on #355 (BVRef=2796848) and
	 * #353 (BVRef=2796764), absent on #351/#336/#322/#317 -- all four of
	 * which do NOT exist in Bookvault's own order list.
	 *
	 * @param WC_Order $order
	 * @return string Empty string when no Bookvault record exists.
	 */
	public static function get_bvref_meta( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return '';
		}
		$value = $order->get_meta( 'BVRef' );
		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Whether a real Bookvault order exists for this WooCommerce order,
	 * per the strongest available local signal (BVRef postmeta).
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public static function has_bookvault_record( $order ) {
		return '' !== self::get_bvref_meta( $order );
	}

	/**
	 * Whether this order was paid before Bookvault integration is confirmed
	 * to have been live -- see INTEGRATION_LIVE_SINCE.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public static function is_legacy_pre_integration( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		$paid = $order->get_date_paid();
		if ( ! $paid ) {
			return false;
		}
		return $paid->getTimestamp() < strtotime( self::INTEGRATION_LIVE_SINCE );
	}

	/**
	 * Parses one order note's text into a structured event, or null if the
	 * note is unrelated to Bookvault routing. Kept separate from get_status()
	 * so a single note string can be unit-tested in isolation.
	 *
	 * EXCLUDED_PATTERN is checked before FAILURE_PATTERN because the
	 * "excluded" note text also happens to contain "Failed to read
	 * line_items:" as a prefix -- checking failure first would misclassify
	 * every intentional exclusion as a technical failure (the exact bug
	 * this class previously had).
	 */
	public static function classify_note_content( $content ) {
		if ( preg_match( self::SUCCESS_PATTERN, $content, $matches ) ) {
			return array(
				'type'  => 'success',
				'state' => $matches[1],
				'ref'   => $matches[2],
			);
		}
		if ( preg_match( self::EXCLUDED_PATTERN, $content ) ) {
			return array( 'type' => 'excluded' );
		}
		if ( preg_match( self::FAILURE_PATTERN, $content ) ) {
			return array( 'type' => 'failure' );
		}
		return null;
	}

	/**
	 * Pure, unit-testable chronology engine. Takes a plain list of already-
	 * classified events (each with 'type' => 'success'|'excluded'|'failure',
	 * in chronological order) plus the order's paid timestamp, and returns
	 * the full status verdict. No WordPress/WooCommerce objects required.
	 *
	 * @param array    $events         Chronological list of ['type'=>..., 'state'=>..., 'ref'=>..., 'timestamp'=>..., 'time'=>...]
	 * @param int|null $paid_timestamp
	 * @return array {
	 *     @type string      $status             'routed' | 'excluded' | 'failed' | 'unknown'
	 *     @type string|null $bookvault_ref      latest success event's reference
	 *     @type string|null $bookvault_state    latest success event's state word (Active/Draft)
	 *     @type bool        $bookvault_state_is_draft  true when the latest success is still 'Draft', not yet 'Active'
	 *     @type int         $failure_count      total failure notes seen, across all time
	 *     @type bool        $had_prior_success  a success event exists earlier than the current (possibly failed) latest state
	 *     @type string|null $first_note_time
	 *     @type string|null $last_note_time
	 *     @type int|null    $seconds_to_route   time between payment and the FIRST success note (routing latency), independent of any later failure
	 * }
	 */
	public static function get_status_from_events( array $events, $paid_timestamp = null ) {
		$result = self::empty_status();

		if ( empty( $events ) ) {
			return $result;
		}

		$first_success = null;
		$had_success   = false;

		foreach ( $events as $event ) {
			$result['last_note_time'] = $event['time'] ?? $result['last_note_time'];
			if ( null === $result['first_note_time'] ) {
				$result['first_note_time'] = $event['time'] ?? null;
			}

			if ( 'success' === $event['type'] ) {
				if ( null === $first_success ) {
					$first_success = $event;
				}
				$had_success = true;
				// Latest event so far is a success: this is authoritative
				// until a later event overrides it below.
				$result['status']          = 'routed';
				$result['bookvault_state'] = $event['state'];
				$result['bookvault_ref']   = $event['ref'];
			} elseif ( 'excluded' === $event['type'] ) {
				// Bookvault itself declined to process this order -- an
				// intentional non-inclusion, not a technical problem. Never
				// counted as a failure and never flagged for action.
				$result['status'] = 'excluded';
			} elseif ( 'failure' === $event['type'] ) {
				$result['failure_count']++;
				// A failure always becomes the current latest state,
				// even if an earlier success already set 'routed' --
				// chronology means the LAST event wins, not the first.
				$result['status'] = 'failed';
			}
		}

		$result['had_prior_success']        = $had_success;
		$result['bookvault_state_is_draft'] = 'routed' === $result['status'] && 'Draft' === $result['bookvault_state'];

		if ( $first_success && null !== $paid_timestamp && null !== $first_success['timestamp'] ) {
			$result['seconds_to_route'] = $first_success['timestamp'] - $paid_timestamp;
		}

		return $result;
	}

	private static function empty_status() {
		return array(
			'status'                   => 'unknown',
			'bookvault_ref'            => null,
			'bookvault_state'          => null,
			'bookvault_state_is_draft' => false,
			'failure_count'            => 0,
			'had_prior_success'        => false,
			'first_note_time'          => null,
			'last_note_time'           => null,
			'seconds_to_route'         => null,
		);
	}

	/**
	 * Whether this order is even expected to route to Bookvault at all
	 * (i.e. contains at least one of the six approved catalog editions).
	 * An order containing only non-catalog items should never be counted
	 * against the routing success rate.
	 *
	 * This checks PRODUCT eligibility only -- whether the order is also
	 * currently EXPECTED to fulfill (not refunded/cancelled/excluded/
	 * legacy) is a separate question, see BHP_Order_Metrics::
	 * bookvault_fulfillment_expected().
	 */
	public static function order_is_bookvault_eligible( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		$catalog_ids = BHP_Offer_Classifier::all_catalog_product_ids();
		foreach ( $order->get_items() as $item ) {
			$id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			if ( in_array( (int) $id, $catalog_ids, true ) ) {
				return true;
			}
		}
		return false;
	}
}
