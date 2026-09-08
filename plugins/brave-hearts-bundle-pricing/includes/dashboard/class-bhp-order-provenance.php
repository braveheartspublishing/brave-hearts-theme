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
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐⭐ 1.8.86 — THE ONE ORIGIN THAT IS NEITHER A SALE NOR A TEST.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * A testimonial reward order: a real family, a real address, a real book
	 * that Bookvault really prints and really ships, bought with a 100 percent
	 * coupon so the store takes no revenue for the goods.
	 *
	 * ⛔ EVERY OTHER NON-`LIVE_CUSTOMER` ORIGIN ABOVE MEANS SOME FLAVOUR OF
	 *    "TEST" — `production_admin_test`, `production_payment_test`,
	 *    `staging_origin_order`, `pre_launch_test_order`, `legacy_test_order`.
	 *    Reusing one of them to keep a reward out of revenue would file a
	 *    genuine shipment to a genuine reader as a fake order, in the executive
	 *    numbers, permanently. ⭐ THAT IS THE ENTIRE REASON THIS CONSTANT
	 *    EXISTS RATHER THAN A ONE-LINE REUSE: the exclusion was always easy;
	 *    the exclusion WITHOUT THE LIE is what needed a new name.
	 *
	 * ⭐ WHAT IT BUYS, AND IT IS EXACT: `reporting_status_for_origin()` maps it
	 *    to `STATUS_AUDIT_ONLY`, so `is_executive_eligible()` answers false,
	 *    and that single function is already the gate on gross sales, net
	 *    revenue, orders, units, AOV, offer mix, format mix, estimated profit
	 *    (see this class's own closing docblock) AND on the `purchase` and
	 *    `bundle_type_purchased` dataLayer events in `bundle-analytics.php`
	 *    lines 346 and 444. ⛔ NO CONSUMER NEEDED CHANGING. If a consumer had
	 *    needed changing, that would have been evidence the gate was not
	 *    single, and worth reporting rather than working around.
	 *
	 * ⭐ IT IS STILL COUNTED IN FULFILLMENT. `audit_only` excludes an order
	 *    from executive commerce KPIs; it does not hide it. A reward order is
	 *    a real print job with a real unit cost, and it must stay visible to
	 *    anything reasoning about what the store actually shipped.
	 */
	const ORIGIN_INCENTIVE_FULFILLMENT    = 'incentive_fulfillment_order';

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
	 * The durable order stamp that marks a reward order.
	 *
	 * ⛔ DELIBERATELY NOT `OVERRIDE_META_KEY`, AND THIS IS THE ONE DESIGN
	 *    DECISION IN 1.8.86 WORTH ARGUING WITH.
	 *
	 * The obvious implementation is to write `OVERRIDE_META_KEY =
	 * ORIGIN_INCENTIVE_FULFILLMENT` and stop — the override already wins over
	 * everything, so it would work on the first try. ⛔ IT WOULD ALSO SPEND
	 * THE MANUAL CHANNEL. `OVERRIDE_META_KEY` is documented directly above as
	 * the human correction door: the thing a person sets, by hand, when the
	 * automatic classification got an order wrong. Once an automatic stamper
	 * writes to it, "this order carries an override" stops meaning "a human
	 * disagreed with the classifier" — and the next person who needs to
	 * correct a reward order's classification has nothing left to correct it
	 * WITH, because the classifier is already sitting in that slot.
	 *
	 * ⭐ So the reward stamp gets its own key, `classify()` reads it as
	 *    evidence like any other evidence, and `OVERRIDE_META_KEY` keeps
	 *    outranking it — meaning a human can still override a reward
	 *    classification, which is the property that would have been lost.
	 *
	 * Value is `yes`. Written once, never cleared. See `stamp_order()`.
	 */
	const INCENTIVE_ORDER_META_KEY = '_bhp_order_incentive_fulfillment';

	/**
	 * Coupon meta marking a coupon as a testimonial reward.
	 *
	 * ⚠️ THIS RAIL CANNOT SURVIVE THE COUPON. These coupons are single-use and
	 *    are deleted after redemption — the seal 1300 test deletes its own and
	 *    Andrew's production procedure will too. Once the post is gone,
	 *    `new WC_Coupon( $code )` returns id 0 and this meta cannot be read at
	 *    all. It is a convenience for the live window, never the load-bearing
	 *    rail. The order stamp and the code prefix are.
	 */
	const INCENTIVE_COUPON_META_KEY = '_bhp_incentive_fulfillment';

	/**
	 * Coupon-code prefixes that identify a testimonial reward, lower-cased.
	 *
	 * ⚠️⚠️ TWO PREFIXES ARE LISTED AND THAT IS A REPORTED DISCREPANCY, NOT A
	 *      DESIGN. The 397 brief names `READER-`. The V-9 module already
	 *      shipped in theme 1.19.396 keys on `bhp-thanks-`, and a real coupon
	 *      with that prefix was created, redeemed and deleted on staging under
	 *      seal 1300 — so `bhp-thanks-` is the only prefix with a live
	 *      redemption behind it, and `READER-` is the only one a brief names.
	 *
	 * ⛔ BOTH ARE RECOGNISED HERE RATHER THAN ONE BEING PICKED, because
	 *    picking is Andrew's call and guessing it wrong is silent: a reward
	 *    coupon whose prefix is not on this list produces a reward order that
	 *    counts as revenue, and nothing anywhere reports that it happened.
	 *    Recognising both cannot produce that failure in either direction.
	 *
	 * ⭐ The cost of the extra prefix is bounded and worth naming: any future
	 *    coupon beginning `READER-` or `bhp-thanks-` — including one created
	 *    for an unrelated purpose — is treated as a reward and kept out of
	 *    revenue. Both are namespaced enough that this is unlikely, and the
	 *    failure direction is "a real sale is under-counted", which is visible
	 *    in the dashboard, rather than "a reward inflates revenue", which is
	 *    not.
	 */
	const INCENTIVE_COUPON_PREFIXES = array( 'reader-', 'bhp-thanks-' );

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

		/*
		 * ⭐⭐ THE REWARD-ORDER TEST, AND ITS POSITION IS DELIBERATE.
		 *
		 * BELOW the manual override, so a human can still correct a reward
		 * order (see INCENTIVE_ORDER_META_KEY's docblock for why that mattered
		 * enough to cost a second meta key).
		 *
		 * ⛔ BELOW THE `failed` BRANCH TOO, AND THAT IS THE ARGUABLE ONE.
		 *    A reward order that fails payment is reported as a payment
		 *    failure, not as a reward. Both readings are defensible: it is not
		 *    a lost SALE, so counting it as a customer payment failure
		 *    overstates the failure rate; but a reward order still charges
		 *    real shipping ($2.99 on this store, measured under seal 1300 —
		 *    a 100 percent coupon does NOT net $0 here), so a family really
		 *    can really fail to pay, and that is a real checkout failure worth
		 *    seeing. ⭐ The tie is broken by blast radius: leaving the `failed`
		 *    branch untouched changes no existing number, and the reverse
		 *    would quietly move orders out of a metric nobody asked to change.
		 */
		if ( self::is_incentive_fulfillment( $order ) && 'failed' !== $order->get_status() ) {
			return array(
				'order_id'         => $id,
				'origin'           => self::ORIGIN_INCENTIVE_FULFILLMENT,
				'reporting_status' => self::STATUS_AUDIT_ONLY,
				'reason'           => 'Testimonial reward fulfillment -- a real order shipped to a real reader under a 100 percent reward coupon. Excluded from revenue, AOV and purchase events because no revenue was taken for the goods, NOT because it is a test order',
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

	/**
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐⭐ THE SINGLE SOURCE OF TRUTH FOR "IS THIS A REWARD ORDER?"
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * The theme's V-9 post-purchase suppression
	 * (`inc/postpurchase-suppression.php`, theme 1.19.396) asks THIS function
	 * rather than re-deriving the answer from its own rails. ⛔ TWO
	 * INDEPENDENT DETECTORS FOR ONE CONCEPT IS THE DEFECT, not the redundancy
	 * it looks like: the day someone adds a prefix to one of them, an order
	 * stops being asked for a review while still counting as revenue, or the
	 * reverse. Either half of that is silent.
	 *
	 * ⭐ V-9 KEEPS ITS OWN RAILS AS A FALLBACK FOR EXACTLY ONE CASE — this
	 *    plugin being deactivated — because a theme that stops suppressing
	 *    review asks when a commerce plugin is switched off would email a real
	 *    reward recipient. See that file's rail 0.
	 *
	 * THREE RAILS, in the order they are cheapest and most durable:
	 *
	 *   1 · THE ORDER STAMP. Written once at checkout, never cleared. Survives
	 *       the coupon's deletion, a coupon rename, and a change to the prefix
	 *       list. ⭐ This is the rail that still works in a year.
	 *   2 · THE CODE PREFIX. A string test on codes the order stores forever.
	 *       Survives coupon deletion; it is what stamps rail 1 in the first
	 *       place, and what classifies orders placed BEFORE 1.8.86 shipped.
	 *   3 · THE COUPON META. Reads the coupon post. ⚠️ Dies with the coupon.
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public static function is_incentive_fulfillment( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		// Rail 1 — the durable stamp.
		if ( 'yes' === $order->get_meta( self::INCENTIVE_ORDER_META_KEY ) ) {
			return true;
		}

		$codes = self::order_coupon_codes( $order );

		// Rail 2 — the code prefix. No database read.
		foreach ( $codes as $code ) {
			if ( self::code_is_incentive( $code ) ) {
				return true;
			}
		}

		// Rail 3 — coupon meta, while the coupon still exists.
		if ( class_exists( 'WC_Coupon' ) ) {
			foreach ( $codes as $code ) {
				$coupon = new WC_Coupon( $code );
				if ( $coupon->get_id() && 'yes' === $coupon->get_meta( self::INCENTIVE_COUPON_META_KEY ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Does this coupon code carry a reward prefix?
	 *
	 * ⛔ COMPARED LOWER-CASED. WooCommerce stores coupon codes lower-cased via
	 *    `wc_format_coupon_code()`, so a brief that writes `READER-` and a
	 *    database that holds `reader-` are the same coupon. A case-sensitive
	 *    test here would match the brief, match nothing in production, and
	 *    fail silently — which is the failure mode this whole class exists to
	 *    stop.
	 *
	 * @param string $code
	 * @return bool
	 */
	public static function code_is_incentive( $code ) {
		$code = strtolower( trim( (string) $code ) );
		if ( '' === $code ) {
			return false;
		}
		foreach ( self::incentive_coupon_prefixes() as $prefix ) {
			if ( '' !== $prefix && 0 === strpos( $code, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The reward-coupon prefixes, lower-cased and filterable.
	 *
	 * @return string[]
	 */
	public static function incentive_coupon_prefixes() {
		$prefixes = array_map( 'strtolower', (array) self::INCENTIVE_COUPON_PREFIXES );

		/**
		 * Adjust the reward-coupon prefix list.
		 *
		 * @param string[] $prefixes Lower-cased prefixes.
		 */
		return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'bhp_incentive_coupon_prefixes', $prefixes ) ) ) );
	}

	/**
	 * Coupon codes on an order, across the two WooCommerce APIs.
	 *
	 * ⛔ `get_coupon_codes()` DOES NOT EXIST ON EVERY ORDER OBJECT THIS STORE
	 *    HANDS AROUND. It landed in WooCommerce 3.7 on `WC_Abstract_Order`,
	 *    and a refund object is a `WC_Order` subclass that does not carry it.
	 *    The `get_items('coupon')` fallback is the pre-3.7 form and works on
	 *    both, so the method test is cheaper than finding out in production.
	 *
	 * @param WC_Order $order
	 * @return string[] Lower-cased codes.
	 */
	private static function order_coupon_codes( $order ) {
		$codes = array();

		if ( method_exists( $order, 'get_coupon_codes' ) ) {
			$codes = (array) $order->get_coupon_codes();
		} elseif ( method_exists( $order, 'get_items' ) ) {
			foreach ( (array) $order->get_items( 'coupon' ) as $item ) {
				if ( is_object( $item ) && method_exists( $item, 'get_code' ) ) {
					$codes[] = $item->get_code();
				}
			}
		}

		return array_values( array_filter( array_map( 'strtolower', array_map( 'strval', $codes ) ) ) );
	}

	/**
	 * Write the durable reward stamp onto an order, once.
	 *
	 * ⭐ IDEMPOTENT AND WRITE-ONLY. It never clears the stamp, because the
	 *    coupon that justified it will be deleted and the evidence would go
	 *    with it. An order that was a reward stays a reward.
	 *
	 * ⛔ IT WRITES ONE ORDER META KEY AND NOTHING ELSE. No coupon is created,
	 *    read for modification, or changed; no product, price, stock,
	 *    shipping, tax, payment or checkout setting is touched.
	 *
	 * @param WC_Order|int $order_or_id
	 * @return bool TRUE if this call wrote the stamp.
	 */
	public static function stamp_order( $order_or_id ) {
		$order = $order_or_id instanceof WC_Order ? $order_or_id : ( function_exists( 'wc_get_order' ) ? wc_get_order( $order_or_id ) : false );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		if ( 'yes' === $order->get_meta( self::INCENTIVE_ORDER_META_KEY ) ) {
			return false; // Already stamped.
		}
		if ( ! self::is_incentive_fulfillment( $order ) ) {
			return false;
		}

		$order->update_meta_data( self::INCENTIVE_ORDER_META_KEY, 'yes' );
		$order->save();
		return true;
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
			self::ORIGIN_INCENTIVE_FULFILLMENT     => self::STATUS_AUDIT_ONLY,
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
			self::ORIGIN_INCENTIVE_FULFILLMENT,
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
			self::ORIGIN_INCENTIVE_FULFILLMENT     => __( 'Testimonial reward fulfillment (real order, no revenue -- not a test)', 'bhp-bundle-pricing' ),
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