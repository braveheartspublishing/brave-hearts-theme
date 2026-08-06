<?php
/**
 * Refund accounting for the dashboard.
 *
 * Explicit decisions (see docs/kpi-definitions.md for the full rationale):
 *
 * - "Gross sales" = the original order total at the time it was placed,
 *   for every order that reached a revenue-relevant status (processing,
 *   completed, or refunded) with date_created in the selected period.
 *   WooCommerce never rewrites `_order_total` when a refund is issued
 *   (refunds are separate `shop_order_refund` records), so
 *   $order->get_total() is already a true pre-refund figure -- gross
 *   sales requires no refund subtraction of its own.
 *
 * - "Refunds" is a separate KPI, attributed to the period in which the
 *   REFUND was created, not the period the original order was created
 *   in. This is a deliberate choice: a dashboard viewer looking at
 *   "this month" wants to see refund activity that happened this month,
 *   even against a sale from three months ago. The alternative
 *   (retroactively editing a past period's totals every time a refund
 *   happens) would make historical figures change after the fact, which
 *   is worse for a period-snapshot dashboard like this one.
 *
 * - "Net revenue" = gross sales (this period's orders) minus refunds
 *   (this period's refund events). Because these two figures can be
 *   drawn from different underlying orders when a refund crosses a
 *   period boundary, net revenue is NOT simply "sum of (order total -
 *   that order's own refunds)" -- it is "sales that started this period"
 *   minus "refund events that happened this period". This matches how
 *   WooCommerce's own Analytics reports and most cash-basis dashboards
 *   present period totals, and is documented explicitly so the number
 *   is never mistaken for a per-order reconciliation.
 *
 * - Paid-order COUNT includes orders later fully refunded. Whether an
 *   order was paid and whether it was later refunded are different
 *   facts; conflating them would undercount genuinely completed
 *   checkouts. The refund KPIs separately show the financial impact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Refund_Metrics {

	/**
	 * Extracts a plain, testable record from a real WC_Order_Refund
	 * object. Kept separate from the query/aggregation logic so refund
	 * math can be unit-tested against plain arrays without a database.
	 *
	 * @param WC_Order_Refund $refund
	 * @return array|null {
	 *     @type int    $refund_id
	 *     @type int    $parent_order_id
	 *     @type float  $total             positive magnitude of the refund (items + shipping + tax combined)
	 *     @type float  $shipping_refunded positive magnitude of the shipping portion, if any
	 *     @type float  $tax_refunded      positive magnitude of the tax portion, if any
	 *     @type int    $units_refunded    total |quantity| across refunded line items
	 *     @type string|null $date_created ISO 8601
	 * }
	 */
	public static function extract_refund_record( $refund ) {
		if ( ! $refund instanceof WC_Order_Refund ) {
			return null;
		}

		$units = 0;
		foreach ( $refund->get_items( 'line_item' ) as $item ) {
			$units += abs( (int) $item->get_quantity() );
		}

		return array(
			'refund_id'         => $refund->get_id(),
			'parent_order_id'   => $refund->get_parent_id(),
			'total'             => abs( (float) $refund->get_total() ),
			'shipping_refunded' => abs( (float) $refund->get_shipping_total() ),
			'tax_refunded'      => abs( (float) $refund->get_total_tax() ),
			'units_refunded'    => $units,
			'date_created'      => $refund->get_date_created() ? $refund->get_date_created()->date( 'c' ) : null,
		);
	}

	/**
	 * Pure aggregation over already-extracted refund records (see above).
	 * Never touches WordPress/WooCommerce APIs directly, so every
	 * combination in the test suite (item refund, shipping refund, tax
	 * refund, multiple refunds on one order, etc.) runs against plain
	 * arrays.
	 *
	 * @param array $records List of extract_refund_record() results.
	 * @return array {
	 *     @type float $refunds_total      sum of all refund totals
	 *     @type float $shipping_refunded  sum of shipping-only portions
	 *     @type float $tax_refunded       sum of tax-only portions
	 *     @type int   $refunded_units     sum of refunded line-item units
	 *     @type int   $refund_count       number of refund records
	 *     @type int[] $affected_order_ids unique parent order IDs, no PII
	 * }
	 */
	public static function summarize_refunds( array $records ) {
		$summary = array(
			'refunds_total'      => 0.0,
			'shipping_refunded'  => 0.0,
			'tax_refunded'       => 0.0,
			'refunded_units'     => 0,
			'refund_count'       => count( $records ),
			'affected_order_ids' => array(),
		);

		foreach ( $records as $r ) {
			$summary['refunds_total']     += (float) ( $r['total'] ?? 0 );
			$summary['shipping_refunded'] += (float) ( $r['shipping_refunded'] ?? 0 );
			$summary['tax_refunded']      += (float) ( $r['tax_refunded'] ?? 0 );
			$summary['refunded_units']    += (int) ( $r['units_refunded'] ?? 0 );
			if ( isset( $r['parent_order_id'] ) && ! in_array( (int) $r['parent_order_id'], $summary['affected_order_ids'], true ) ) {
				$summary['affected_order_ids'][] = (int) $r['parent_order_id'];
			}
		}

		$summary['refunds_total']     = round( $summary['refunds_total'], 2 );
		$summary['shipping_refunded'] = round( $summary['shipping_refunded'], 2 );
		$summary['tax_refunded']      = round( $summary['tax_refunded'], 2 );

		return $summary;
	}

	/**
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return WC_Order_Refund[]
	 */
	public static function get_refunds_in_period( $start, $end ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}
		$results = wc_get_orders( array(
			'type'         => 'shop_order_refund',
			'date_created' => $start->format( 'Y-m-d H:i:s' ) . '...' . $end->format( 'Y-m-d H:i:s' ),
			'limit'        => -1,
			'orderby'      => 'date',
			'order'        => 'DESC',
		) );
		return array_values( array_filter( $results, function ( $item ) {
			return $item instanceof WC_Order_Refund;
		} ) );
	}

	/**
	 * Full refund KPI block for one period, ready to merge into
	 * BHP_Order_Metrics::compute_kpis()'s return value.
	 */
	public static function compute_refund_kpis( $start, $end ) {
		$refunds = self::get_refunds_in_period( $start, $end );
		$records = array_values( array_filter( array_map( array( __CLASS__, 'extract_refund_record' ), $refunds ) ) );
		return self::summarize_refunds( $records );
	}

	/**
	 * Whether a given order is refunded at all, and to what degree, based
	 * on WooCommerce's own authoritative running total -- used to label
	 * individual rows in the recent-orders table.
	 *
	 * @param WC_Order $order
	 * @return array { @type string $state 'none'|'partial'|'full', @type float $amount }
	 */
	public static function get_order_refund_state( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return self::refund_state_from_totals( 0.0, 0.0 );
		}
		return self::refund_state_from_totals( (float) $order->get_total(), (float) $order->get_total_refunded() );
	}

	/**
	 * Pure form of get_order_refund_state(), operating on plain numbers
	 * so the partial/full boundary logic (including the floating-point
	 * epsilon) can be unit-tested without a real WC_Order object.
	 *
	 * @param float $total     the order's original total
	 * @param float $refunded  the order's running total_refunded (either sign)
	 * @return array { @type string $state 'none'|'partial'|'full', @type float $amount }
	 */
	public static function refund_state_from_totals( $total, $refunded ) {
		$refunded = abs( (float) $refunded );
		if ( $refunded <= 0.0 ) {
			return array( 'state' => 'none', 'amount' => 0.0 );
		}
		$total = (float) $total;
		// A tiny epsilon avoids floating-point rounding turning an
		// exact full refund into a false "partial".
		$is_full = $total > 0 && ( $total - $refunded ) <= 0.005;
		return array(
			'state'  => $is_full ? 'full' : 'partial',
			'amount' => round( $refunded, 2 ),
		);
	}
}
