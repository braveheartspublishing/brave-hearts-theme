<?php
/**
 * Classifies WooCommerce orders by ORIGIN (real customer vs. internal
 * test/verification), separately from WooCommerce's own order STATUS.
 *
 * Written 2026-07-06 after discovering every order in the dashboard's
 * "Last 30 Days" view was, on investigation, an internal verification
 * purchase from this project's own Bookvault/catalog testing phases --
 * not a single one showed evidence of a genuine third-party customer.
 * See docs/order-provenance-audit.md for the full per-order evidence.
 *
 * WHY THIS IS AN EXPLICIT ID LIST, NOT A HEURISTIC:
 * There is no reliable automated signal available on this store to
 * detect "this was a test" from order data alone:
 * - No `_stripe_livemode` postmeta exists on any order (this WooCommerce
 *   Stripe gateway version doesn't record it), so live-vs-test-mode
 *   cannot be read from the order itself.
 * - `_created_via` is 'store-api' for every order checked, including
 *   ones definitively known to be internal tests (the real checkout
 *   flow was used deliberately, not the wp-admin "new order" screen) --
 *   so created_via cannot distinguish admin testing from a genuine sale.
 * - IP address and note-text patterns are STRONG circumstantial evidence
 *   (used to build this list) but are not something a fully-automated
 *   rule should re-derive on every dashboard load: a genuine future
 *   customer could share an IP with Andrew's household/network, and a
 *   future genuine Bookvault "Draft" note is not itself suspicious.
 *
 * Given that, the auditable, honest approach is the same one this
 * codebase already uses for legacy product IDs
 * (BHP_Offer_Classifier::KNOWN_LEGACY_PRODUCT_IDS): a short, explicit,
 * documented list, reviewed by a human, not a fragile pattern-match. A
 * manual per-order meta override is also supported so a
 * misclassification can be corrected without a code deploy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Order_Provenance {

	// Reporting statuses
	const STATUS_INCLUDE          = 'include_executive';
	const STATUS_EXCLUDE          = 'exclude_executive';
	const STATUS_AUDIT_ONLY       = 'audit_only';
	const STATUS_FAILURE_ONLY     = 'failure_only';
	const STATUS_UNKNOWN          = 'unknown_needs_confirmation';

	// Origin classifications
	const ORIGIN_LIVE_CUSTOMER            = 'live_production_customer_order';
	const ORIGIN_ADMIN_TEST               = 'production_admin_test';
	const ORIGIN_PAYMENT_TEST             = 'production_payment_test';
	const ORIGIN_INTERNAL_FULFILLMENT_TEST = 'production_internal_fulfillment_test';
	const ORIGIN_IMPORTED_STAGING         = 'imported_staging_order';
	const ORIGIN_STAGING                  = 'staging_origin_order';
	const ORIGIN_PRELAUNCH_TEST           = 'pre_launch_test_order';
	const ORIGIN_LEGACY_REAL              = 'legacy_real_customer_order';
	const ORIGIN_LEGACY_TEST              = 'legacy_test_order';
	const ORIGIN_REFUNDED_TEST            = 'refunded_test_order';
	const ORIGIN_FAILED_PAYMENT           = 'failed_payment';
	const ORIGIN_UNKNOWN                  = 'unknown_origin';

	/**
	 * Manual override meta key. If ever set on a real order (via wp-cli
	 * or a future small admin action -- none exists yet, this is just the
	 * read side), it takes precedence over everything below, so a
	 * misclassification or a genuine future order that happens to
	 * resemble a test can be corrected without a code deploy.
	 * Value must be one of the ORIGIN_* constants above.
	 */
	const OVERRIDE_META_KEY = '_bhp_order_provenance_override';

	/**
	 * Known internal verification orders, confirmed 2026-07-06 by direct
	 * investigation (see docs/order-provenance-audit.md for the full
	 * evidence per order -- IP address, order notes, date/time clustering
	 * against this project's own documented Bookvault testing phases, and
	 * in two cases a literal textual match to prior internal testing
	 * artifacts). None of these showed any evidence of a third-party
	 * customer: every one was placed from one of only two IP addresses,
	 * both consistent with Andrew's own known testing sessions, within
	 * narrow time windows that align exactly with this project's own
	 * session history for Bookvault/catalog verification work.
	 *
	 * - 317: refund reason literally states "Test-mode refund
	 *   verification" -- definitive, in the order's own data.
	 * - 318: same IP/day as #317; contains a failed-then-retried charge
	 *   consistent with deliberate payment-flow testing; legacy product ID.
	 * - 319: same IP/day/cluster as #317/#318; ended FAILED; legacy
	 *   product ID. Already excluded from executive KPIs by the existing
	 *   status filter, but was still counting toward "payment failures."
	 * - 321: same IP/day as #322, 10 minutes apart; legacy product ID;
	 *   timing matches this project's own documented Mariana
	 *   catalog-remediation testing phase.
	 * - 322: same IP/day as #321/#318/#317/#319.
	 * - 336: CONFIRMED by Andrew (2026-07-06) as the first Mariana Trench
	 *   paperback fulfillment test -- a real, live-mode payment he placed
	 *   internally, which did not route to Bookvault automatically and
	 *   which he then entered into Bookvault manually afterward (Bookvault
	 *   reference BV2793822, manual reference 43908-#00001). Real payment
	 *   AND real fulfillment both occurred, but it is still an internal
	 *   operational test, not an external customer sale -- see
	 *   ORIGIN_INTERNAL_FULFILLMENT_TEST and MANUALLY_FULFILLED_BOOKVAULT_ORDERS
	 *   below. Previously listed under NEEDS_CONFIRMATION_ORDER_IDS pending
	 *   this confirmation.
	 * - 351: DEFINITIVELY documented in this project's own prior session
	 *   history as an explicitly Andrew-approved live test order placed
	 *   specifically to verify Bookvault routing (see docs/bookvault-chronology.md).
	 * - 353: its Bookvault "Draft" reference (BV2796764) is the EXACT
	 *   value later hardcoded as example/fixture data in this plugin's
	 *   own test suite -- strong documentary evidence it was a deliberate
	 *   verification order, not a customer purchase.
	 * - 355: same IP/session as #351/#353 (2026-07-05), completing what
	 *   reads as a deliberate 3-order sequence exercising Bookvault's
	 *   three status branches (declined/Draft/Active) in one sitting.
	 */
	const KNOWN_TEST_ORDER_IDS = array( 317, 318, 319, 321, 322, 336, 351, 353, 355 );

	/**
	 * Orders with circumstantial evidence pointing toward "test" but
	 * without a definitive signal, pending Andrew's explicit confirmation.
	 * Deliberately kept separate from KNOWN_TEST_ORDER_IDS rather than
	 * assumed. Per the explicit instruction not to infer real-vs-test from
	 * circumstantial signals alone, anything listed here defaults to
	 * EXCLUDED from executive KPIs (the conservative choice -- an
	 * unconfirmed order should not inflate revenue) but is flagged for
	 * Andrew's explicit confirmation rather than silently bucketed with
	 * the confirmed tests. Empty as of 2026-07-06 -- #336, the only entry
	 * ever listed here, was confirmed by Andrew and moved to
	 * KNOWN_TEST_ORDER_IDS.
	 */
	const NEEDS_CONFIRMATION_ORDER_IDS = array();

	/**
	 * Orders whose Bookvault fulfillment was created MANUALLY by Andrew
	 * (via the Bookvault portal directly) after automatic WooCommerce->
	 * Bookvault routing did not occur -- as opposed to the two orders
	 * (#353, #355) Bookvault's own plugin created automatically. This
	 * distinction cannot be read from WooCommerce order data at all (a
	 * manually-created Bookvault record leaves no BVRef postmeta or order
	 * note on the WooCommerce side, since the automated plugin integration
	 * never touched it) -- it is documented here the same way
	 * KNOWN_TEST_ORDER_IDS is, from Andrew's direct confirmation
	 * (2026-07-06), not derived.
	 *
	 * Used to build the Bookvault fulfillment summary's automatic-vs-
	 * manual distinction: automatic-routing eligible denominator = 2
	 * (#353, #355), automatic-routing successes = 2 (100%), manually
	 * fulfilled = 1 (#336), total Bookvault records tied to Brave Hearts
	 * orders = 3.
	 */
	const MANUALLY_FULFILLED_BOOKVAULT_ORDERS = array(
		336 => array( 'bookvault_ref' => 'BV2793822', 'manual_reference' => '43908-#00001' ),
	);

	/**
	 * Full classification for one order. Never PII: only the order ID,
	 * status, and derived flags are inspected/returned.
	 *
	 * @param WC_Order $order
	 * @return array {
	 *     @type int    $order_id
	 *     @type string $origin           one of the ORIGIN_* constants
	 *     @type string $reporting_status one of the STATUS_* constants
	 *     @type string $reason           human-readable, non-PII justification
	 * }
	 */
	public static function classify( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return array(
				'order_id'         => 0,
				'origin'           => self::ORIGIN_UNKNOWN,
				'reporting_status' => self::STATUS_UNKNOWN,
				'reason'           => 'Not a real WC_Order object',
			);
		}

		$id = $order->get_id();

		// Manual override always wins -- lets a future correction happen
		// via order meta instead of a code deploy.
		$override = $order->get_meta( self::OVERRIDE_META_KEY );
		if ( $override && in_array( $override, self::all_origin_constants(), true ) ) {
			return array(
				'order_id'         => $id,
				'origin'           => $override,
				'reporting_status' => self::reporting_status_for_origin( $override ),
				'reason'           => 'Manually overridden via ' . self::OVERRIDE_META_KEY . ' order meta',
			);
		}

		if ( 'failed' === $order->get_status() ) {
			if ( in_array( $id, self::KNOWN_TEST_ORDER_IDS, true ) ) {
				return array(
					'order_id'         => $id,
					'origin'           => self::ORIGIN_FAILED_PAYMENT,
					'reporting_status' => self::STATUS_FAILURE_ONLY,
					'reason'           => 'Failed order within a confirmed internal test cluster (see docs/order-provenance-audit.md) -- treated as a test/admin payment failure, not a genuine customer payment failure',
				);
			}
			return array(
				'order_id'         => $id,
				'origin'           => self::ORIGIN_FAILED_PAYMENT,
				'reporting_status' => self::STATUS_FAILURE_ONLY,
				'reason'           => 'Failed order, no test-cluster evidence -- treated as a genuine customer payment failure',
			);
		}

		if ( isset( self::MANUALLY_FULFILLED_BOOKVAULT_ORDERS[ $id ] ) ) {
			$manual = self::MANUALLY_FULFILLED_BOOKVAULT_ORDERS[ $id ];
			return array(
				'order_id'         => $id,
				'origin'           => self::ORIGIN_INTERNAL_FULFILLMENT_TEST,
				'reporting_status' => self::STATUS_AUDIT_ONLY,
				'reason'           => 'Confirmed by Andrew as an internal production fulfillment test -- real live-mode payment, automatic Bookvault routing did not occur, manually fulfilled afterward (Bookvault ref ' . $manual['bookvault_ref'] . ' / manual reference ' . $manual['manual_reference'] . ')',
			);
		}

		if ( in_array( $id, self::KNOWN_TEST_ORDER_IDS, true ) ) {
			$refund_state = class_exists( 'BHP_Refund_Metrics' ) ? BHP_Refund_Metrics::get_order_refund_state( $order ) : array( 'state' => 'none' );
			$origin = ( 'full' === $refund_state['state'] ) ? self::ORIGIN_REFUNDED_TEST : self::ORIGIN_PAYMENT_TEST;
			return array(
				'order_id'         => $id,
				'origin'           => $origin,
				'reporting_status' => self::STATUS_AUDIT_ONLY,
				'reason'           => 'Confirmed internal verification order -- see docs/order-provenance-audit.md for this order\'s specific evidence',
			);
		}

		if ( in_array( $id, self::NEEDS_CONFIRMATION_ORDER_IDS, true ) ) {
			return array(
				'order_id'         => $id,
				'origin'           => self::ORIGIN_UNKNOWN,
				'reporting_status' => self::STATUS_UNKNOWN,
				'reason'           => 'Circumstantial evidence (shared IP with confirmed test orders on the same day) but not definitive -- excluded from executive KPIs by default pending Andrew\'s confirmation',
			);
		}

		// No test/unknown signal found -- treat as a genuine executive
		// order, still subject to WooCommerce's own status filter
		// (BHP_Order_Metrics::VALID_PAID_STATUSES) upstream of this call.
		return array(
			'order_id'         => $id,
			'origin'           => self::ORIGIN_LIVE_CUSTOMER,
			'reporting_status' => self::STATUS_INCLUDE,
			'reason'           => 'No test/staging/admin signal found -- treated as a genuine live production customer order',
		);
	}

	private static function reporting_status_for_origin( $origin ) {
		$map = array(
			self::ORIGIN_LIVE_CUSTOMER             => self::STATUS_INCLUDE,
			self::ORIGIN_LEGACY_REAL               => self::STATUS_INCLUDE,
			self::ORIGIN_ADMIN_TEST                => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_PAYMENT_TEST              => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_INTERNAL_FULFILLMENT_TEST => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_IMPORTED_STAGING          => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_STAGING                   => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_PRELAUNCH_TEST            => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_LEGACY_TEST               => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_REFUNDED_TEST             => self::STATUS_AUDIT_ONLY,
			self::ORIGIN_FAILED_PAYMENT            => self::STATUS_FAILURE_ONLY,
			self::ORIGIN_UNKNOWN                   => self::STATUS_UNKNOWN,
		);
		return $map[ $origin ] ?? self::STATUS_UNKNOWN;
	}

	private static function all_origin_constants() {
		return array(
			self::ORIGIN_LIVE_CUSTOMER, self::ORIGIN_ADMIN_TEST, self::ORIGIN_PAYMENT_TEST,
			self::ORIGIN_INTERNAL_FULFILLMENT_TEST,
			self::ORIGIN_IMPORTED_STAGING, self::ORIGIN_STAGING, self::ORIGIN_PRELAUNCH_TEST,
			self::ORIGIN_LEGACY_REAL, self::ORIGIN_LEGACY_TEST, self::ORIGIN_REFUNDED_TEST,
			self::ORIGIN_FAILED_PAYMENT, self::ORIGIN_UNKNOWN,
		);
	}

	public static function origin_labels() {
		return array(
			self::ORIGIN_LIVE_CUSTOMER             => __( 'Live production customer order', 'bhp-bundle-pricing' ),
			self::ORIGIN_ADMIN_TEST                => __( 'Production admin test', 'bhp-bundle-pricing' ),
			self::ORIGIN_PAYMENT_TEST              => __( 'Production payment test', 'bhp-bundle-pricing' ),
			self::ORIGIN_INTERNAL_FULFILLMENT_TEST => __( 'Production live-mode internal fulfillment test', 'bhp-bundle-pricing' ),
			self::ORIGIN_IMPORTED_STAGING          => __( 'Imported staging order', 'bhp-bundle-pricing' ),
			self::ORIGIN_STAGING                   => __( 'Staging-origin order', 'bhp-bundle-pricing' ),
			self::ORIGIN_PRELAUNCH_TEST            => __( 'Pre-launch test order', 'bhp-bundle-pricing' ),
			self::ORIGIN_LEGACY_REAL               => __( 'Legacy real customer order', 'bhp-bundle-pricing' ),
			self::ORIGIN_LEGACY_TEST               => __( 'Legacy test order', 'bhp-bundle-pricing' ),
			self::ORIGIN_REFUNDED_TEST             => __( 'Refunded test order', 'bhp-bundle-pricing' ),
			self::ORIGIN_FAILED_PAYMENT            => __( 'Failed payment', 'bhp-bundle-pricing' ),
			self::ORIGIN_UNKNOWN                   => __( 'Unknown origin', 'bhp-bundle-pricing' ),
		);
	}

	/**
	 * @return array{bookvault_ref: string, manual_reference: string}|null
	 */
	public static function manual_bookvault_fulfillment( $order_id ) {
		return self::MANUALLY_FULFILLED_BOOKVAULT_ORDERS[ (int) $order_id ] ?? null;
	}

	public static function reporting_status_labels() {
		return array(
			self::STATUS_INCLUDE      => __( 'Included in executive commerce KPIs', 'bhp-bundle-pricing' ),
			self::STATUS_EXCLUDE      => __( 'Excluded from executive commerce KPIs', 'bhp-bundle-pricing' ),
			self::STATUS_AUDIT_ONLY   => __( 'Historical/audit reporting only', 'bhp-bundle-pricing' ),
			self::STATUS_FAILURE_ONLY => __( 'Payment-failure reporting only', 'bhp-bundle-pricing' ),
			self::STATUS_UNKNOWN      => __( 'Unknown -- requires Andrew\'s confirmation', 'bhp-bundle-pricing' ),
		);
	}

	/**
	 * True only for STATUS_INCLUDE -- the single gate every executive KPI
	 * (gross sales, net revenue, orders, units, AOV, offer mix, format
	 * mix, estimated profit) must pass an order through before counting
	 * it, per the Phase 3 "single reusable rule" requirement.
	 */
	public static function is_executive_eligible( $order ) {
		$c = self::classify( $order );
		return self::STATUS_INCLUDE === $c['reporting_status'];
	}
}