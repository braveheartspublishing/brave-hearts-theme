<?php
/**
 * Aggregates WooCommerce orders into the dashboard's KPI set.
 *
 * "Valid paid orders" = status in (processing, completed, refunded) --
 * the three WooCommerce statuses a Stripe-charged order actually reaches
 * on this store (Stripe moves a captured charge straight to
 * "processing"; WooCommerce itself moves an order to "refunded" once its
 * full total has been refunded). "refunded" is included deliberately: a
 * fully-refunded order was still a genuine paid transaction and must
 * still appear in gross sales / paid-order counts, with the refund's
 * financial impact surfaced separately (see BHP_Refund_Metrics) rather
 * than by silently disappearing from every KPI. "on-hold" would mean an
 * unusual manual gateway this store doesn't use, so it's deliberately
 * excluded rather than assumed paid. Failed/cancelled/trashed orders are
 * excluded by construction (wc_get_orders status filter), not filtered
 * after the fact -- see get_valid_paid_orders().
 *
 * Bookvault fulfillment-eligibility model (2026-07-06 correction): being
 * PAID and containing a catalog product no longer alone means an order
 * counts toward the Bookvault routing denominator. A direct reconciliation
 * against the Bookvault portal (which listed exactly 3 real orders total)
 * found the previous "6 eligible / 4 needing attention" figures were
 * inflated by orders that were never actually expected to fulfill via
 * Bookvault at all -- see bookvault_fulfillment_status() and
 * docs/bookvault-chronology.md for the full evidence and per-order
 * reasoning.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Order_Metrics {

	const VALID_PAID_STATUSES = array( 'processing', 'completed', 'refunded' );

	/**
	 * Bookvault routing is only "failed" once this many minutes have
	 * passed since payment with no success note -- matches the
	 * observed real retry window (order #351's automatic retries ran
	 * at +0m, +2m, and +10m before requiring manual resend). A fresh
	 * order that simply hasn't routed yet within a couple of minutes is
	 * "pending", not "failed".
	 */
	const ROUTING_FAILURE_THRESHOLD_MINUTES = 15;

	/**
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return WC_Order[]
	 */
	public static function get_valid_paid_orders( $start, $end ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}
		$results = wc_get_orders( array(
			'status'       => self::VALID_PAID_STATUSES,
			'date_created' => $start->format( 'Y-m-d H:i:s' ) . '...' . $end->format( 'Y-m-d H:i:s' ),
			'limit'        => -1,
			'orderby'      => 'date',
			'order'        => 'DESC',
		) );

		/**
		 * wc_get_orders() can return refund objects alongside real orders
		 * (a partially-refunded order's own refund record can match a
		 * loose status/date query even though a refund isn't itself a
		 * paid order) -- confirmed live on staging: a refund object
		 * (Automattic\WooCommerce\Admin\Overrides\OrderRefund under HPOS)
		 * doesn't implement get_order_number() and fatals every caller
		 * downstream. Filtering to real WC_Order instances here, once,
		 * protects every caller instead of each one needing its own check.
		 */
		return array_values( array_filter( $results, function ( $item ) {
			return $item instanceof WC_Order && ! ( $item instanceof WC_Order_Refund );
		} ) );
	}

	/**
	 * @deprecated Use get_classified_payment_failures() -- kept only for
	 * any external caller that predates the 2026-07-06 provenance
	 * correction; does not distinguish genuine customer failures from
	 * admin/test ones.
	 */
	public static function get_failed_orders_count( $start, $end ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}
		$orders = wc_get_orders( array(
			'status'       => array( 'failed' ),
			'date_created' => $start->format( 'Y-m-d H:i:s' ) . '...' . $end->format( 'Y-m-d H:i:s' ),
			'limit'        => -1,
			'return'       => 'ids',
		) );
		return count( $orders );
	}

	/**
	 * Failed orders in the period, split into genuine customer failures
	 * vs. ones belonging to a documented internal test cluster (Phase 3
	 * dataset-origin correction, 2026-07-06) -- see
	 * BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS's docblock for why order
	 * #319 (a real, failed, otherwise-invisible order) exists at all: it
	 * was never in any "valid paid" query, but it still inflated the raw
	 * "payment failures" count before this correction.
	 *
	 * @return array { @type int $genuine_count, @type int $test_count, @type int $total_count }
	 */
	public static function get_classified_payment_failures( $start, $end ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array( 'genuine_count' => 0, 'test_count' => 0, 'total_count' => 0 );
		}
		$orders = wc_get_orders( array(
			'status'       => array( 'failed' ),
			'date_created' => $start->format( 'Y-m-d H:i:s' ) . '...' . $end->format( 'Y-m-d H:i:s' ),
			'limit'        => -1,
			'orderby'      => 'date',
			'order'        => 'DESC',
		) );
		$genuine = 0;
		$test    = 0;
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			// Membership in the documented test-order list is the actual
			// signal (see BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS) --
			// checked directly rather than parsing classify()'s
			// human-readable reason string.
			if ( in_array( $order->get_id(), BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS, true ) ) {
				$test++;
			} else {
				$genuine++;
			}
		}
		return array( 'genuine_count' => $genuine, 'test_count' => $test, 'total_count' => $genuine + $test );
	}

	/**
	 * Pure decision function: given an order's already-resolved Bookvault
	 * status, refund state, WooCommerce status, and legacy flag, decides
	 * whether Bookvault fulfillment is currently EXPECTED for this order --
	 * i.e. whether it belongs in the routing-success-rate denominator at
	 * all. Kept pure (plain scalars in, plain array out) so every
	 * combination is unit-testable without constructing a real WC_Order.
	 *
	 * Priority order matters and is deliberate:
	 * 1. A real Bookvault record (bv_status === 'routed') always means
	 *    "expected and fulfilled" regardless of what happened to the order
	 *    afterward (e.g. a later refund) -- the historical routing record
	 *    is preserved, per the Phase 5 requirement, rather than retroactively
	 *    un-counting a real Bookvault order.
	 * 2. Bookvault's own "excluded" note (it declined to process the
	 *    order) is authoritative and final -- not a failure, not
	 *    reconsidered by any other signal.
	 * 3. A fully refunded order (that Bookvault never actually created) is
	 *    no longer expected to fulfill.
	 * 4. A cancelled order (that Bookvault never actually created) is no
	 *    longer expected to fulfill.
	 * 5. An order paid before Bookvault integration is confirmed live has
	 *    no realistic chance of a routing attempt having occurred -- silence
	 *    is expected, not an overdue failure.
	 * 6. Otherwise: expected, "ok".
	 *
	 * @param string $bv_status    'routed' | 'excluded' | 'failed' | 'unknown'
	 * @param string $refund_state 'none' | 'partial' | 'full'
	 * @param string $order_status WooCommerce order status, e.g. 'processing', 'cancelled'
	 * @param bool   $is_legacy_pre_integration
	 * @return array { @type bool $expected, @type string $reason }
	 *     reason: 'ok' | 'excluded_by_bookvault' | 'refunded' | 'cancelled' | 'legacy_pre_integration'
	 */
	public static function determine_bookvault_fulfillment_expectation( $bv_status, $refund_state, $order_status, $is_legacy_pre_integration ) {
		if ( 'routed' === $bv_status ) {
			return array( 'expected' => true, 'reason' => 'ok' );
		}
		if ( 'excluded' === $bv_status ) {
			return array( 'expected' => false, 'reason' => 'excluded_by_bookvault' );
		}
		if ( 'full' === $refund_state ) {
			return array( 'expected' => false, 'reason' => 'refunded' );
		}
		if ( 'cancelled' === $order_status ) {
			return array( 'expected' => false, 'reason' => 'cancelled' );
		}
		if ( $is_legacy_pre_integration ) {
			return array( 'expected' => false, 'reason' => 'legacy_pre_integration' );
		}
		return array( 'expected' => true, 'reason' => 'ok' );
	}

	/**
	 * Thin wrapper: extracts real order state and calls the pure decision
	 * function above.
	 *
	 * @param WC_Order $order
	 * @return array { @type bool $expected, @type string $reason, @type array $bv }
	 */
	public static function bookvault_fulfillment_status( $order ) {
		$bv           = BHP_Bookvault_Status::get_status( $order );
		$refund_state = BHP_Refund_Metrics::get_order_refund_state( $order );
		$decision     = self::determine_bookvault_fulfillment_expectation(
			$bv['status'],
			$refund_state['state'],
			$order instanceof WC_Order ? $order->get_status() : '',
			BHP_Bookvault_Status::is_legacy_pre_integration( $order )
		);
		$decision['bv'] = $bv;
		return $decision;
	}

	/**
	 * Full KPI computation for one period. Every count/sum is derived
	 * directly from real order data -- there is no synthetic or
	 * placeholder value anywhere in this method.
	 *
	 * Refund accounting (see BHP_Refund_Metrics for the full rationale):
	 * 'gross_revenue' here is a pre-refund figure -- WooCommerce never
	 * rewrites an order's own total when it's refunded, so no special
	 * handling is needed to keep it that way. 'refunds_total' is computed
	 * separately, attributed to the period the REFUND happened in (which
	 * may reference an order created in an earlier period). 'net_revenue'
	 * = gross_revenue (this period's orders) - refunds_total (this
	 * period's refund events); see the class docblock on
	 * BHP_Refund_Metrics for why these two figures are allowed to be
	 * drawn from different order sets.
	 */
	public static function compute_kpis( $start, $end ) {
		// The FULL valid-paid-order dataset -- Bookvault fulfillment
		// tracking below deliberately keeps using this unfiltered set
		// (per explicit instruction, its own dataset was already
		// corrected in a prior pass and must not change here). Executive
		// commerce KPIs (gross/net revenue, order count, units, offer/
		// format mix, estimated profit) use ONLY the provenance-eligible
		// subset -- see BHP_Order_Provenance's class docblock for why an
		// order needs an explicit, documented classification rather than
		// being trusted just because its WooCommerce status is "paid."
		$orders = self::get_valid_paid_orders( $start, $end );

		$audit_orders = array();
		$excluded_test_count = 0;
		$excluded_unknown_count = 0;
		$excluded_test_value = 0.0;
		$excluded_unknown_value = 0.0;

		foreach ( $orders as $order ) {
			$c = BHP_Order_Provenance::classify( $order );
			$audit_orders[] = array(
				'order_id'         => $order->get_id(),
				'date'             => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '—',
				'status'           => $order->get_status(),
				'total'            => round( (float) $order->get_total(), 2 ),
				'origin'           => $c['origin'],
				'reporting_status' => $c['reporting_status'],
				'reason'           => $c['reason'],
			);
			if ( BHP_Order_Provenance::STATUS_UNKNOWN === $c['reporting_status'] ) {
				$excluded_unknown_count++;
				$excluded_unknown_value += (float) $order->get_total();
			} elseif ( BHP_Order_Provenance::STATUS_INCLUDE !== $c['reporting_status'] ) {
				$excluded_test_count++;
				$excluded_test_value += (float) $order->get_total();
			}
		}

		$executive_orders = array_values( array_filter( $orders, array( 'BHP_Order_Provenance', 'is_executive_eligible' ) ) );

		// Refunds: split into genuine-customer vs. test-order refunds so a
		// refund like #317's ("Test-mode refund verification," a
		// documented internal test) never subtracts from a "customer
		// refunds" figure that excludes the order it belongs to in the
		// first place -- see BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS.
		$all_refunds = BHP_Refund_Metrics::get_refunds_in_period( $start, $end );
		$customer_refund_records = array();
		$test_refund_records = array();
		foreach ( $all_refunds as $refund ) {
			$record = BHP_Refund_Metrics::extract_refund_record( $refund );
			if ( null === $record ) {
				continue;
			}
			$parent = wc_get_order( $record['parent_order_id'] );
			$is_test = $parent instanceof WC_Order && ! BHP_Order_Provenance::is_executive_eligible( $parent );
			if ( $is_test ) {
				$test_refund_records[] = $record;
			} else {
				$customer_refund_records[] = $record;
			}
		}
		$refund_kpis = BHP_Refund_Metrics::summarize_refunds( $customer_refund_records );
		$test_refund_kpis = BHP_Refund_Metrics::summarize_refunds( $test_refund_records );

		$payment_failures = self::get_classified_payment_failures( $start, $end );

		$kpi = array(
			'order_count'              => count( $executive_orders ),
			'gross_revenue'            => 0.0,
			'refunds_total'            => $refund_kpis['refunds_total'],
			'refunded_units'           => $refund_kpis['refunded_units'],
			'refund_count'             => $refund_kpis['refund_count'],
			'test_refunds_total'       => $test_refund_kpis['refunds_total'],
			'test_refund_count'        => $test_refund_kpis['refund_count'],
			'net_revenue'              => 0.0, // filled in below, after gross_revenue is summed
			'units_sold'               => 0,
			'units_paperback'          => 0,
			'units_hardcover'          => 0,
			'offer_counts'             => array_fill_keys( array_keys( BHP_Offer_Classifier::labels() ), 0 ),
			'estimated_profit_total'   => 0.0,
			'payment_failure_count'      => $payment_failures['genuine_count'],
			'test_payment_failure_count' => $payment_failures['test_count'],

			// Dataset-transparency counters (Phase 3 correction, 2026-07-06)
			'excluded_test_order_count'    => $excluded_test_count,
			'excluded_test_order_value'    => round( $excluded_test_value, 2 ),
			'excluded_unknown_order_count' => $excluded_unknown_count,
			'excluded_unknown_order_value' => round( $excluded_unknown_value, 2 ),
			'audit_orders'                 => $audit_orders, // order id/date/status/total/origin only -- no PII

			// Broad, product-based signal: "contains a catalog edition" --
			// unchanged meaning from before. Not the routing denominator.
			'bookvault_eligible_count' => 0,

			// Narrower, corrected signal: catalog-eligible AND currently
			// expected to fulfill via Bookvault (excludes refunded,
			// cancelled, Bookvault-excluded, and legacy/pre-integration
			// orders) -- THIS is the routing-success-rate denominator.
			// Deliberately still computed over the FULL $orders set, not
			// $executive_orders -- see this method's docblock.
			'bookvault_expected_count'      => 0,
			'bookvault_created_count'       => 0, // has a real Bookvault record (Draft or Active), created AUTOMATICALLY by the WooCommerce->Bookvault integration
			'bookvault_active_count'        => 0,
			'bookvault_draft_count'         => 0,
			'bookvault_action_required_count' => 0, // expected, no record yet, past the overdue window (or a genuine technical failure)
			'bookvault_excluded_count'      => 0, // eligible but not expected, broken down below
			'bookvault_excluded_reasons'    => array(
				'excluded_by_bookvault'   => 0,
				'refunded'                => 0,
				'cancelled'               => 0,
				'legacy_pre_integration'  => 0,
			),

			// Manual-vs-automatic fulfillment distinction (2026-07-06
			// correction, order #336): a Bookvault record can exist for an
			// order even when automatic WooCommerce->Bookvault routing
			// never fired, if Andrew created it directly in the Bookvault
			// portal afterward. This is never derivable from WooCommerce
			// data (see BHP_Order_Provenance::MANUALLY_FULFILLED_BOOKVAULT_ORDERS)
			// and is tracked entirely separately from bookvault_created_count
			// above, which counts ONLY automatic routing successes.
			'bookvault_manual_fulfillment_count' => 0,
			'bookvault_total_records_count'      => 0, // automatic + manual -- "how many real Bookvault records exist for this period's orders", regardless of how they were created

			'manual_attention_orders'  => array(), // order IDs only, no PII -- only genuinely action-required orders
			'per_offer_profit'         => array(),

			// Phase 1A economics additions -- see docs/economics-model.md.
			// An order with any non-catalog/unmapped line item has an
			// UNKNOWN cost, not a zero one: its profit is excluded from
			// estimated_profit_total (which would otherwise silently
			// understate true cost) and tracked separately here instead.
			'orders_with_unknown_cost_count' => 0,
			'unknown_cost_order_ids'         => array(), // order IDs only, no PII
			'costed_order_count'             => 0, // executive orders actually included in estimated_profit_total (order_count minus unknown-cost orders)
		);

		foreach ( $executive_orders as $order ) {
			$total = (float) $order->get_total();
			$kpi['gross_revenue'] += $total;

			$classification = BHP_Offer_Classifier::classify_order( $order );
			$kpi['offer_counts'][ $classification['offer_type'] ]++;
			$kpi['units_paperback'] += $classification['units_paperback'];
			$kpi['units_hardcover'] += $classification['units_hardcover'];
			$kpi['units_sold'] += $classification['units_paperback'] + $classification['units_hardcover'];

			if ( $classification['units_non_catalog'] > 0 ) {
				// At least one line item has no known catalog/cost mapping
				// -- this order's cost is UNKNOWN, not zero. Revenue is
				// still counted above (it's real, actual, and known); the
				// PROFIT figure is excluded from the aggregate rather than
				// computed with a silent $0 cost for the unmapped unit(s).
				$kpi['orders_with_unknown_cost_count']++;
				$kpi['unknown_cost_order_ids'][] = $order->get_id();
				continue;
			}

			$product_revenue = (float) $order->get_subtotal();
			$shipping        = (float) $order->get_shipping_total();
			$discount        = (float) $order->get_discount_total();
			// Exact per-title costing -- e.g. an Everest+Amazon paperback
			// order costs a few cents more to print than a Mariana+Everest
			// one; see BHP_Cost_Config::estimate_order_profit_precise().
			$profit = BHP_Cost_Config::estimate_order_profit_precise(
				$product_revenue,
				$shipping,
				$discount,
				$total,
				$classification['paperback_titles'],
				$classification['hardcover_titles']
			);
			if ( 'unknown' === $profit['cost_basis'] ) {
				// combo_print_cost() found a title with no cost mapping
				// even though classify_items() recognized it as catalog --
				// a genuinely inconsistent state (a catalog title with no
				// cost entry), handled the same conservative way: excluded
				// from the aggregate, not silently zeroed.
				$kpi['orders_with_unknown_cost_count']++;
				$kpi['unknown_cost_order_ids'][] = $order->get_id();
				continue;
			}

			$kpi['costed_order_count']++;
			$kpi['estimated_profit_total'] += $profit['estimated_profit'];

			if ( ! isset( $kpi['per_offer_profit'][ $classification['offer_type'] ] ) ) {
				$kpi['per_offer_profit'][ $classification['offer_type'] ] = 0.0;
			}
			$kpi['per_offer_profit'][ $classification['offer_type'] ] += $profit['estimated_profit'];
		}

		// Bookvault fulfillment tracking: unchanged dataset ($orders, not
		// $executive_orders) and unchanged logic -- see this method's
		// docblock for why.
		foreach ( $orders as $order ) {
			if ( BHP_Bookvault_Status::order_is_bookvault_eligible( $order ) ) {
				$kpi['bookvault_eligible_count']++;

				// Manual-fulfillment check runs independently of the
				// automatic-routing expected/excluded logic below -- a
				// manually-fulfilled order (e.g. #336) is correctly EXCLUDED
				// from the automatic-routing denominator (it predates/was
				// outside successful automatic integration routing, same as
				// any other legacy_pre_integration order), but must still be
				// counted as a real, fulfilled Bookvault record for the
				// "total records" and "manually fulfilled" figures.
				if ( class_exists( 'BHP_Order_Provenance' ) && BHP_Order_Provenance::manual_bookvault_fulfillment( $order->get_id() ) ) {
					$kpi['bookvault_manual_fulfillment_count']++;
				}

				$fulfillment = self::bookvault_fulfillment_status( $order );
				$bv          = $fulfillment['bv'];

				if ( ! $fulfillment['expected'] ) {
					$kpi['bookvault_excluded_count']++;
					$kpi['bookvault_excluded_reasons'][ $fulfillment['reason'] ]++;
					continue;
				}

				$kpi['bookvault_expected_count']++;

				if ( 'routed' === $bv['status'] ) {
					$kpi['bookvault_created_count']++;
					if ( $bv['bookvault_state_is_draft'] ) {
						$kpi['bookvault_draft_count']++;
					} else {
						$kpi['bookvault_active_count']++;
					}
				} elseif ( self::is_routing_overdue( $order, $bv ) ) {
					$kpi['bookvault_action_required_count']++;
					$kpi['manual_attention_orders'][] = $order->get_id();
				}
				// else: expected, not yet created, still within the normal
				// pending window -- no bucket increment, not flagged.
			}
		}

		$kpi['bookvault_total_records_count'] = $kpi['bookvault_created_count'] + $kpi['bookvault_manual_fulfillment_count'];

		$kpi['average_order_value'] = $kpi['order_count'] > 0 ? round( $kpi['gross_revenue'] / $kpi['order_count'], 2 ) : null;
		$kpi['units_per_order']     = $kpi['order_count'] > 0 ? round( $kpi['units_sold'] / $kpi['order_count'], 2 ) : null;
		$kpi['gross_revenue']       = round( $kpi['gross_revenue'], 2 );
		// See the class docblock: refunds_total is drawn from a separate
		// per-refund-date query, so net_revenue can reference a refund on
		// an order created in an earlier period. Never label this 'gross'.
		$kpi['net_revenue']         = round( $kpi['gross_revenue'] - $kpi['refunds_total'], 2 );
		$kpi['estimated_profit_total'] = round( $kpi['estimated_profit_total'], 2 );
		// Refund impact on profit is a simple pass-through reduction, not
		// a full re-derivation: the Stripe fee and print/shipping cost
		// already spent on a refunded order are not modeled as recovered
		// (Stripe generally keeps its processing fee on a refund, and a
		// printed book cannot be unprinted). This is a known, documented
		// simplification -- see docs/kpi-definitions.md.
		$kpi['estimated_profit_after_refunds'] = round( $kpi['estimated_profit_total'] - $kpi['refunds_total'], 2 );

		// Phase 1A: a supplementary, conservative, forward-looking reserve
		// applied on top of (never instead of) the actual-refunds figure
		// above -- a provision against refund/replacement risk on orders
		// in this period that have NOT (yet) refunded. Applied as a % of
		// period gross revenue (matching the same % of price+shipping
		// methodology BHP_Offer_Economics::reserve_amount() uses
		// prospectively), which is deliberately slightly conservative
		// (it also nominally reserves against orders that already
		// refunded) rather than attempting a precise non-refunded-only
		// calculation. Always labeled as an estimate -- see
		// docs/economics-model.md for the full rationale.
		if ( class_exists( 'BHP_Cost_Config' ) && method_exists( 'BHP_Cost_Config', 'refund_reserve_percentage' ) ) {
			$refund_reserve_cfg      = BHP_Cost_Config::refund_reserve_percentage();
			$replacement_reserve_cfg = BHP_Cost_Config::replacement_reserve_percentage();
			$reserve_pct             = $refund_reserve_cfg['percentage'] + $replacement_reserve_cfg['percentage'];
			$kpi['reserve_percentage_applied'] = $reserve_pct;
			$kpi['estimated_reserve_amount']   = round( $kpi['gross_revenue'] * $reserve_pct, 2 );
			$kpi['contribution_after_reserves'] = round( $kpi['estimated_profit_after_refunds'] - $kpi['estimated_reserve_amount'], 2 );
		} else {
			$kpi['reserve_percentage_applied']  = null;
			$kpi['estimated_reserve_amount']    = null;
			$kpi['contribution_after_reserves'] = null;
		}

		$kpi['contribution_margin_pct'] = ( $kpi['gross_revenue'] > 0 && null !== $kpi['contribution_after_reserves'] )
			? round( ( $kpi['contribution_after_reserves'] / $kpi['gross_revenue'] ) * 100, 1 )
			: null;
		$kpi['profit_per_order'] = ( $kpi['order_count'] > 0 && null !== $kpi['contribution_after_reserves'] )
			? round( $kpi['contribution_after_reserves'] / $kpi['order_count'], 2 )
			: null;
		$kpi['profit_per_unit'] = ( $kpi['units_sold'] > 0 && null !== $kpi['contribution_after_reserves'] )
			? round( $kpi['contribution_after_reserves'] / $kpi['units_sold'], 2 )
			: null;

		// Data-quality status: a single, glanceable summary of whether this
		// period's economics figures are complete. 'complete' only when
		// every executive order was successfully costed; 'partial' means
		// real revenue is fully accounted for but some orders' profit
		// contribution is missing (unknown cost), not zeroed.
		$kpi['data_quality_status'] = $kpi['orders_with_unknown_cost_count'] > 0 ? 'partial_unknown_cost' : 'complete';

		$bundle_types = array(
			BHP_Offer_Classifier::TWO_PAPERBACK_BUNDLE,
			BHP_Offer_Classifier::COMPLETE_PAPERBACK_SET,
			BHP_Offer_Classifier::TWO_HARDCOVER_BUNDLE,
			BHP_Offer_Classifier::COMPLETE_HARDCOVER_SET,
			BHP_Offer_Classifier::MIXED_FORMAT,
			BHP_Offer_Classifier::BOTH_COMPLETE,
		);
		$bundle_order_count = 0;
		foreach ( $bundle_types as $type ) {
			$bundle_order_count += $kpi['offer_counts'][ $type ];
		}
		$kpi['bundle_purchase_rate'] = $kpi['order_count'] > 0 ? round( ( $bundle_order_count / $kpi['order_count'] ) * 100, 1 ) : null;

		$complete_collection_count = $kpi['offer_counts'][ BHP_Offer_Classifier::COMPLETE_PAPERBACK_SET ]
			+ $kpi['offer_counts'][ BHP_Offer_Classifier::COMPLETE_HARDCOVER_SET ]
			+ $kpi['offer_counts'][ BHP_Offer_Classifier::BOTH_COMPLETE ];
		$kpi['complete_collection_rate'] = $kpi['order_count'] > 0 ? round( ( $complete_collection_count / $kpi['order_count'] ) * 100, 1 ) : null;

		// Corrected denominator: only orders genuinely expected to fulfill
		// via Bookvault right now, not every catalog-eligible paid order
		// regardless of refund/cancellation/exclusion/legacy status.
		$kpi['bookvault_routing_success_rate'] = $kpi['bookvault_expected_count'] > 0
			? round( ( $kpi['bookvault_created_count'] / $kpi['bookvault_expected_count'] ) * 100, 1 )
			: null;

		return $kpi;
	}

	/**
	 * An eligible order with no success note yet is only "failed" once
	 * the retry window has passed -- otherwise a perfectly healthy order
	 * that routed 90 seconds ago would show as a false failure.
	 */
	public static function is_routing_overdue( $order, $bookvault_status ) {
		if ( 'failed' === $bookvault_status['status'] ) {
			return true;
		}
		$paid = $order->get_date_paid();
		if ( ! $paid ) {
			return false; // not even paid yet by WooCommerce's own record -- not a routing question yet
		}
		$minutes_since_paid = ( time() - $paid->getTimestamp() ) / 60;
		return $minutes_since_paid > self::ROUTING_FAILURE_THRESHOLD_MINUTES;
	}
}
