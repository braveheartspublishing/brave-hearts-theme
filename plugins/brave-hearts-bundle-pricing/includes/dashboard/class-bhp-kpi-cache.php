<?php
/**
 * Transient-backed cache for dashboard KPI calculations.
 *
 * Recomputing every historical order on every admin page load doesn't
 * scale and isn't necessary -- KPIs only need to reflect reality within
 * a few minutes of a new/changed order, not on every request. Cache is
 * invalidated automatically on any order status change (new order,
 * payment confirmed, refund, etc.) via WooCommerce's own status hooks,
 * and can always be bypassed with a manual refresh from the dashboard UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_KPI_Cache {

	const TTL_SECONDS = 300; // 5 minutes -- short enough to feel "live" without recalculating on every load
	const OPTION_INVALIDATION_TOKEN = 'bhp_dashboard_cache_token';

	public static function get( $key, callable $compute ) {
		return self::get_with_meta( $key, $compute )['data'];
	}

	/**
	 * Same as get(), but also reports when the returned data was computed
	 * and whether this call served a cached value or triggered a fresh
	 * calculation -- powers the dashboard's "Data as of ..." freshness
	 * display (see class-bhp-dashboard-page.php). Kept as a separate method
	 * rather than changing get()'s return shape so every existing caller
	 * (and every existing test) that only wants the KPI array itself is
	 * unaffected.
	 *
	 * @return array { @type mixed $data, @type bool $from_cache, @type int $computed_at Unix timestamp }
	 */
	public static function get_with_meta( $key, callable $compute ) {
		$token = get_option( self::OPTION_INVALIDATION_TOKEN, '0' );
		$transient_key = 'bhp_dash_' . md5( $key . '_' . $token );
		$meta_transient_key = $transient_key . '_meta';

		$cached = get_transient( $transient_key );
		if ( false !== $cached ) {
			$meta = get_transient( $meta_transient_key );
			return array(
				'data'        => $cached,
				'from_cache'  => true,
				'computed_at' => ( is_array( $meta ) && isset( $meta['computed_at'] ) ) ? $meta['computed_at'] : null,
			);
		}

		$value = call_user_func( $compute );
		$computed_at = time();
		set_transient( $transient_key, $value, self::TTL_SECONDS );
		set_transient( $meta_transient_key, array( 'computed_at' => $computed_at ), self::TTL_SECONDS );
		return array(
			'data'        => $value,
			'from_cache'  => false,
			'computed_at' => $computed_at,
		);
	}

	/**
	 * Bumping the token invalidates every cached KPI at once without
	 * needing to enumerate transient keys (WordPress has no native
	 * "delete all transients matching prefix" call). Idempotent and cheap
	 * (one autoload-false option write) -- safe to call from many hooks,
	 * including ones that can fire several times per request.
	 */
	public static function invalidate_all() {
		$current = (int) get_option( self::OPTION_INVALIDATION_TOKEN, 0 );
		update_option( self::OPTION_INVALIDATION_TOKEN, (string) ( $current + 1 ), false );
	}

	/**
	 * Every WooCommerce event that can change a KPI the dashboard shows.
	 * Grouped by the operational change in the Phase 6 spec so a missing
	 * case is easy to spot on review:
	 *
	 * - New order / payment completion / status transition -> changes
	 *   order_count, gross_revenue, offer mix, units, Bookvault-eligible
	 *   count.
	 * - Order-item save / quantity change (admin edit screen) -> changes
	 *   units, offer classification, revenue.
	 * - New order note -> Bookvault routing notes are plain order notes;
	 *   this is the ONLY reliable hook for "Bookvault status changed",
	 *   since Bookvault has no dedicated action of its own (see
	 *   class-bhp-bookvault-status.php).
	 * - Refund created -> changes refunds_total, net_revenue, and (for a
	 *   full refund) which status bucket the order falls into.
	 *
	 * Deliberately narrow: only WooCommerce's own order/refund/note hooks
	 * are used here, never a blanket `save_post` or `updated_option` --
	 * those would invalidate on every unrelated admin action on the site,
	 * defeating the purpose of caching at all.
	 */
	public static function register_invalidation_hooks() {
		$hooks = array(
			'woocommerce_new_order',              // new order created
			'woocommerce_order_status_changed',   // any status transition, including payment completion and refund
			'woocommerce_payment_complete',        // belt-and-suspenders: some gateways fire this without a status-changed event in between
			'woocommerce_update_order',           // general order object save (covers most metadata updates)
			'woocommerce_saved_order_items',      // admin "Edit order" item/quantity save
			'woocommerce_order_note_added',       // includes Bookvault's routing-status notes
			'woocommerce_order_refunded',         // partial or full refund created
		);
		foreach ( $hooks as $hook ) {
			add_action( $hook, array( __CLASS__, 'invalidate_all' ) );
		}
	}
}
