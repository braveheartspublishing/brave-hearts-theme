<?php
/**
 * Brave Hearts Publishing — Analytics Phase 1B: UTM/attribution persistence.
 *
 * Server-side half of attribution: reads the first-party cookies written
 * by assets/js/bhp-attribution.js and attaches a snapshot to the
 * WooCommerce order at checkout, so attribution survives long after the
 * cookie itself expires. See docs/utm-attribution.md for cookie names,
 * expiry windows, and the first-touch/last-touch rules.
 *
 * This class never reads $_GET UTM parameters directly -- capture is the
 * JS file's job (it runs on every page load, before this PHP-side code
 * ever needs to act). This class only reads back what the browser already
 * decided to persist, at the one moment (order creation) that value needs
 * to become permanent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_UTM_Attribution {

	const COOKIE_FIRST_TOUCH = 'bhp_attr_first';
	const COOKIE_LAST_TOUCH  = 'bhp_attr_last';

	const FIELDS = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'ttclid', 'msclkid', 'landing_page', 'timestamp' );

	const ORDER_META_FIRST_TOUCH = '_bhp_attribution_first_touch';
	const ORDER_META_LAST_TOUCH  = '_bhp_attribution_last_touch';

	public static function init() {
		// Fires once per real checkout completion, same trust boundary as
		// the purchase-event dedup hook -- reads cookies while the request
		// still has them, before any redirect to the thank-you page.
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'attach_to_order' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'attach_to_order' ), 10, 1 );
	}

	private static function read_cookie( $name ) {
		if ( empty( $_COOKIE[ $name ] ) ) {
			return null;
		}
		$decoded = json_decode( wp_unslash( $_COOKIE[ $name ] ), true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}
		$clean = array();
		foreach ( self::FIELDS as $field ) {
			if ( isset( $decoded[ $field ] ) ) {
				// Defense in depth even though the JS side already limits
				// length/charset -- an order meta field must never carry
				// an oversized or malformed value regardless of client
				// trust, and must never contain PII (these fields are
				// campaign/click identifiers only, never name/email/etc.).
				$clean[ $field ] = sanitize_text_field( wp_unslash( substr( (string) $decoded[ $field ], 0, 200 ) ) );
			}
		}
		return empty( $clean ) ? null : $clean;
	}

	/**
	 * Attaches first-touch and last-touch attribution snapshots to the
	 * order as private (underscore-prefixed) meta -- never displayed to
	 * the customer, never included in order emails, matching how every
	 * other internal/diagnostic meta field on this store is stored (see
	 * BVRef, _bhp_purchase_event_fired). Only ever WRITES new meta on a
	 * brand-new order; never edits an existing order's financial data or
	 * any field outside this attribution namespace.
	 *
	 * @param int|WC_Order $order_or_id
	 */
	public static function attach_to_order( $order_or_id ) {
		$order = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order ) {
			return;
		}
		// Idempotent: a retried/duplicate hook firing for the same order
		// (e.g. both checkout hooks above firing for the same Store-API
		// order) must never overwrite an already-recorded first-touch.
		if ( $order->get_meta( self::ORDER_META_FIRST_TOUCH, true ) ) {
			return;
		}

		$first = self::read_cookie( self::COOKIE_FIRST_TOUCH );
		$last  = self::read_cookie( self::COOKIE_LAST_TOUCH );

		if ( $first ) {
			$order->update_meta_data( self::ORDER_META_FIRST_TOUCH, wp_json_encode( $first ) );
		}
		if ( $last ) {
			$order->update_meta_data( self::ORDER_META_LAST_TOUCH, wp_json_encode( $last ) );
		}
		if ( $first || $last ) {
			$order->save_meta_data();
		}
	}

	/**
	 * Reads the current visitor's first/last-touch cookies directly --
	 * used by anything that needs attribution at the moment of an action
	 * (e.g. Phase 1C lead-event logging) without waiting for an order to
	 * exist. Same sanitized/capped shape as attach_to_order() uses, so a
	 * lead-event record and an order's attribution snapshot are always
	 * directly comparable.
	 *
	 * @return array{first_touch: array|null, last_touch: array|null}
	 */
	public static function current_visitor_attribution() {
		return array(
			'first_touch' => self::read_cookie( self::COOKIE_FIRST_TOUCH ),
			'last_touch'  => self::read_cookie( self::COOKIE_LAST_TOUCH ),
		);
	}

	/**
	 * Reads back a stored attribution snapshot for reporting (dashboard
	 * adapter, Phase 13). Returns null if never recorded -- never a
	 * fabricated empty-but-present record.
	 *
	 * @return array|null
	 */
	public static function get_order_attribution( $order ) {
		if ( ! $order ) {
			return null;
		}
		$first = $order->get_meta( self::ORDER_META_FIRST_TOUCH, true );
		$last  = $order->get_meta( self::ORDER_META_LAST_TOUCH, true );
		if ( ! $first && ! $last ) {
			return null;
		}
		return array(
			'first_touch' => $first ? json_decode( $first, true ) : null,
			'last_touch'  => $last ? json_decode( $last, true ) : null,
		);
	}
}

BHP_UTM_Attribution::init();
