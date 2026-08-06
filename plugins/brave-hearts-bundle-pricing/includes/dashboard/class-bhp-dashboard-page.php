<?php
/**
 * Brave Hearts executive dashboard -- read-only admin page.
 *
 * Location: WooCommerce -> Brave Hearts Dashboard. Gated on
 * manage_woocommerce so shop managers (not just full administrators) can
 * use it, matching the capability WooCommerce's own order screens use.
 * No public-facing output, no write actions beyond an explicit,
 * nonce-protected "refresh cache" button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Dashboard_Page {

	const CAPABILITY = 'manage_woocommerce';
	const SLUG       = 'bhp-dashboard';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		BHP_KPI_Cache::register_invalidation_hooks();
	}

	public static function register_menu() {
		add_submenu_page(
			'woocommerce',
			'Brave Hearts Dashboard',
			'Brave Hearts Dashboard',
			self::CAPABILITY,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * All boundaries are computed from current_datetime() -- a WordPress
	 * core function that returns "now" already converted to the site's
	 * configured timezone (Settings -> General), never server or UTC time.
	 * Every period below is deliberately INCLUSIVE at both ends and
	 * anchored to local midnight, so "Last 7 days" always means exactly
	 * seven local calendar days (today + the six before it), not a
	 * rolling 168-hour window that could straddle a different local day
	 * depending on what time the page happens to load.
	 *
	 * Known, documented limitation: for 'today', the CURRENT period is
	 * partial (midnight through right now) while the prior-period
	 * comparison is a full 24 hours (all of yesterday). Comparing a
	 * partial day to a full day will make "today" look artificially low
	 * early in the day -- this is called out explicitly in
	 * docs/kpi-definitions.md rather than silently accepted.
	 *
	 * @param string        $period        'today' | '7d' | '30d' | 'custom'
	 * @param DateTime|null $custom_start  local midnight of the custom range's first day (period === 'custom' only)
	 * @param DateTime|null $custom_end    local midnight of the day AFTER the custom range's last day, i.e. an exclusive upper bound (period === 'custom' only)
	 */
	private static function get_period_bounds( $period, $custom_start = null, $custom_end = null ) {
		$now = current_datetime();
		switch ( $period ) {
			case '7d':
				$start = ( clone $now )->modify( '-6 days' )->setTime( 0, 0, 0 );
				$prior_start = ( clone $start )->modify( '-7 days' );
				$prior_end   = ( clone $start )->modify( '-1 second' );
				$end = $now;
				break;
			case '30d':
				$start = ( clone $now )->modify( '-29 days' )->setTime( 0, 0, 0 );
				$prior_start = ( clone $start )->modify( '-30 days' );
				$prior_end   = ( clone $start )->modify( '-1 second' );
				$end = $now;
				break;
			case 'custom':
				// Both bounds are validated, local-midnight DateTime
				// objects by the time they reach here (see render()) --
				// this function only computes the matching prior period.
				$start = $custom_start;
				$end   = $custom_end;
				$span_seconds = $end->getTimestamp() - $start->getTimestamp();
				$prior_end    = ( clone $start )->modify( '-1 second' );
				$prior_start  = ( clone $prior_end )->modify( '-' . $span_seconds . ' seconds' )->modify( '+1 second' );
				break;
			case 'today':
			default:
				$start = ( clone $now )->setTime( 0, 0, 0 );
				$prior_start = ( clone $start )->modify( '-1 day' );
				$prior_end   = ( clone $start )->modify( '-1 second' );
				$end = $now;
				break;
		}
		return array( $start, $end, $prior_start, $prior_end );
	}

	/**
	 * Parses and validates the bhp_start/bhp_end GET parameters for the
	 * custom range option. Returns null on any invalid input (missing,
	 * malformed, end before start, or a range judged unreasonably large)
	 * so the caller can fall back to 'today' rather than ever running a
	 * query against a garbage or attacker-supplied range.
	 *
	 * @return array{0: DateTime, 1: DateTime}|null [start (local midnight), end (exclusive, local midnight of the day after)]
	 */
	private static function parse_custom_range() {
		$raw_start = isset( $_GET['bhp_start'] ) ? sanitize_text_field( wp_unslash( $_GET['bhp_start'] ) ) : '';
		$raw_end   = isset( $_GET['bhp_end'] ) ? sanitize_text_field( wp_unslash( $_GET['bhp_end'] ) ) : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_end ) ) {
			return null;
		}

		try {
			$tz = wp_timezone();
			$start = new DateTime( $raw_start, $tz );
			$start->setTime( 0, 0, 0 );
			// End is inclusive as the admin types it (e.g. "through July 5")
			// but is stored/used as an EXCLUSIVE upper bound one day later,
			// so an order placed at 23:59:59 on the selected end date is
			// still included -- matching how 'today'/'7d'/'30d' already work.
			$end = new DateTime( $raw_end, $tz );
			$end->setTime( 0, 0, 0 );
			$end->modify( '+1 day' );
		} catch ( Exception $e ) {
			return null;
		}

		if ( $end <= $start ) {
			return null;
		}

		// A generous but finite sanity cap -- prevents an accidental or
		// malicious multi-decade range from triggering an enormous
		// wc_get_orders() query.
		$max_days = 366;
		if ( ( $end->getTimestamp() - $start->getTimestamp() ) > ( $max_days * DAY_IN_SECONDS ) ) {
			return null;
		}

		return array( $start, $end );
	}

	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'bhp-bundle-pricing' ) );
		}

		if ( isset( $_POST['bhp_refresh_cache'] ) && check_admin_referer( 'bhp_dashboard_refresh', 'bhp_dashboard_nonce' ) ) {
			BHP_KPI_Cache::invalidate_all();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Dashboard cache refreshed.', 'bhp-bundle-pricing' ) . '</p></div>';
		}

		$period = isset( $_GET['bhp_period'] ) ? sanitize_key( wp_unslash( $_GET['bhp_period'] ) ) : 'today';
		if ( ! in_array( $period, array( 'today', '7d', '30d', 'custom' ), true ) ) {
			$period = 'today';
		}

		$custom_range = null;
		if ( 'custom' === $period ) {
			$custom_range = self::parse_custom_range();
			if ( null === $custom_range ) {
				// Invalid/missing custom dates -- fall back to 'today'
				// rather than running an unbounded or malformed query.
				$period = 'today';
			}
		}

		if ( 'custom' === $period ) {
			list( $start, $end, $prior_start, $prior_end ) = self::get_period_bounds( $period, $custom_range[0], $custom_range[1] );
			$cache_key = 'kpis_custom_' . $start->format( 'Ymd' ) . '_' . $end->format( 'Ymd' );
			$prior_cache_key = 'kpis_prior_custom_' . $prior_start->format( 'Ymd' ) . '_' . $prior_end->format( 'Ymd' );
		} else {
			list( $start, $end, $prior_start, $prior_end ) = self::get_period_bounds( $period );
			$cache_key = 'kpis_' . $period . '_' . $start->format( 'Ymd' );
			$prior_cache_key = 'kpis_prior_' . $period . '_' . $prior_start->format( 'Ymd' );
		}

		$kpi_result = BHP_KPI_Cache::get_with_meta( $cache_key, function () use ( $start, $end ) {
			return BHP_Order_Metrics::compute_kpis( $start, $end );
		} );
		$kpi             = $kpi_result['data'];
		$data_as_of      = $kpi_result['computed_at'];
		$data_from_cache = $kpi_result['from_cache'];

		$prior_kpi = BHP_KPI_Cache::get( $prior_cache_key, function () use ( $prior_start, $prior_end ) {
			return BHP_Order_Metrics::compute_kpis( $prior_start, $prior_end );
		} );

		// Display strings mirror exactly what the admin typed: the
		// internal $end boundary is exclusive (midnight of the day
		// AFTER the selected end date), so it's shifted back one day
		// here purely for the date-picker's own value attribute.
		$display_start = 'custom' === $period ? $start->format( 'Y-m-d' ) : '';
		$display_end   = 'custom' === $period ? ( clone $end )->modify( '-1 day' )->format( 'Y-m-d' ) : '';

		self::render_page( $period, $kpi, $prior_kpi, $start, $end, $display_start, $display_end, $data_as_of, $data_from_cache );
	}

	private static function fmt_money( $amount ) {
		return '$' . number_format( (float) $amount, 2 );
	}

	/**
	 * @param mixed $current
	 * @param mixed $prior
	 * @param bool  $prior_had_orders  Whether the PRIOR PERIOD had any
	 *              orders at all -- distinct from whether this specific
	 *              metric's prior value happens to be zero. Passing the
	 *              order-count-based flag (rather than checking $prior
	 *              directly) is what lets "0% bundle rate, but 5 real
	 *              prior orders" render as "New activity" instead of the
	 *              misleading "No prior-period data".
	 */
	private static function trend_html( $current, $prior, $prior_had_orders, $lower_is_better = false ) {
		if ( ! $prior_had_orders ) {
			return '<span class="bhp-dash-trend bhp-dash-trend--neutral">' . esc_html__( 'No orders in prior period', 'bhp-bundle-pricing' ) . '</span>';
		}
		if ( null === $current ) {
			return '<span class="bhp-dash-trend bhp-dash-trend--neutral">' . esc_html__( 'No data', 'bhp-bundle-pricing' ) . '</span>';
		}
		if ( null === $prior || 0 == $prior ) {
			if ( 0 == $current ) {
				return '<span class="bhp-dash-trend bhp-dash-trend--neutral">' . esc_html__( 'No change', 'bhp-bundle-pricing' ) . '</span>';
			}
			// The prior period had real orders, but THIS metric was
			// legitimately zero then (e.g. 0% bundle rate) -- a percentage
			// change from zero is mathematically undefined, not "infinite%".
			// Say what actually happened instead of a misleading number.
			$good  = ! $lower_is_better;
			$class = $good ? 'bhp-dash-trend--good' : 'bhp-dash-trend--bad';
			return '<span class="bhp-dash-trend ' . esc_attr( $class ) . '">' . esc_html__( 'New activity', 'bhp-bundle-pricing' ) . '</span>';
		}
		$diff = $current - $prior;
		$pct  = round( ( $diff / abs( $prior ) ) * 100, 1 );
		$is_up = $diff > 0;
		$good = $lower_is_better ? ! $is_up : $is_up;
		$class = 0 == $diff ? 'bhp-dash-trend--neutral' : ( $good ? 'bhp-dash-trend--good' : 'bhp-dash-trend--bad' );
		$arrow = 0 == $diff ? '→' : ( $is_up ? '↑' : '↓' );
		$pct_text = esc_html( ( $pct > 0 ? '+' : '' ) . $pct . '%' );
		return '<span class="bhp-dash-trend ' . esc_attr( $class ) . '">' . esc_html( $arrow ) . ' ' . $pct_text . '</span>';
	}

	private static function kpi_card( $label, $value_html, $current, $prior, $prior_had_orders, $lower_is_better = false, $warning = false ) {
		$warning_class = $warning ? ' bhp-dash-card--warning' : '';
		echo '<div class="bhp-dash-card' . esc_attr( $warning_class ) . '">';
		echo '<div class="bhp-dash-card__label">' . esc_html( $label ) . '</div>';
		echo '<div class="bhp-dash-card__value">' . $value_html . '</div>'; // phpcs:ignore -- pre-escaped by caller
		echo '<div class="bhp-dash-card__trend">' . self::trend_html( $current, $prior, $prior_had_orders, $lower_is_better ) . '</div>';
		echo '</div>';
	}

	/**
	 * Human-readable labels for BHP_Order_Metrics' bookvault_excluded_reasons
	 * keys -- shared between the warnings panel's historical summary and the
	 * fulfillment-summary breakdown table so the two never drift apart.
	 */
	private static function bookvault_excluded_reason_labels() {
		return array(
			'excluded_by_bookvault'  => __( 'Declined by Bookvault (not flagged for fulfillment)', 'bhp-bundle-pricing' ),
			'refunded'               => __( 'Fully refunded (fulfillment no longer expected)', 'bhp-bundle-pricing' ),
			'cancelled'              => __( 'Cancelled (fulfillment no longer expected)', 'bhp-bundle-pricing' ),
			'legacy_pre_integration' => __( 'Placed before Bookvault integration was active', 'bhp-bundle-pricing' ),
		);
	}

	private static function render_page( $period, $kpi, $prior_kpi, $start, $end, $display_start = '', $display_end = '', $data_as_of = null, $data_from_cache = false ) {
		$labels = BHP_Offer_Classifier::labels();
		$has_prior_orders = $prior_kpi['order_count'] > 0;
		?>
		<div class="wrap bhp-dashboard">
			<h1><?php esc_html_e( 'Brave Hearts Dashboard', 'bhp-bundle-pricing' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Read-only operational and revenue snapshot. All figures are computed directly from live WooCommerce order data.', 'bhp-bundle-pricing' ); ?></p>
			<p class="bhp-dash-dataset-notice"><strong><?php esc_html_e( 'Executive KPIs include verified live production customer orders only.', 'bhp-bundle-pricing' ); ?></strong> <?php esc_html_e( 'Orders identified as internal admin/payment tests, staging artifacts, or of unconfirmed origin are excluded from revenue, order-count, unit, offer-mix, and profit figures below -- see the "Order dataset & audit" section for the full breakdown and every excluded order.', 'bhp-bundle-pricing' ); ?></p>

			<form method="get" class="bhp-dash-period-form" id="bhp-dash-period-form">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
				<label for="bhp_period"><?php esc_html_e( 'Period:', 'bhp-bundle-pricing' ); ?></label>
				<select name="bhp_period" id="bhp_period" onchange="document.getElementById('bhp-dash-custom-range').style.display = (this.value === 'custom') ? 'inline-block' : 'none'; if (this.value !== 'custom') { this.form.submit(); }">
					<option value="today" <?php selected( $period, 'today' ); ?>><?php esc_html_e( 'Today', 'bhp-bundle-pricing' ); ?></option>
					<option value="7d" <?php selected( $period, '7d' ); ?>><?php esc_html_e( 'Last 7 days', 'bhp-bundle-pricing' ); ?></option>
					<option value="30d" <?php selected( $period, '30d' ); ?>><?php esc_html_e( 'Last 30 days', 'bhp-bundle-pricing' ); ?></option>
					<option value="custom" <?php selected( $period, 'custom' ); ?>><?php esc_html_e( 'Custom range', 'bhp-bundle-pricing' ); ?></option>
				</select>
				<span id="bhp-dash-custom-range" style="<?php echo 'custom' === $period ? '' : 'display:none;'; ?>">
					<label for="bhp_start"><?php esc_html_e( 'From', 'bhp-bundle-pricing' ); ?></label>
					<input type="date" name="bhp_start" id="bhp_start" value="<?php echo esc_attr( $display_start ); ?>" />
					<label for="bhp_end"><?php esc_html_e( 'Through', 'bhp-bundle-pricing' ); ?></label>
					<input type="date" name="bhp_end" id="bhp_end" value="<?php echo esc_attr( $display_end ); ?>" />
					<button type="submit" class="button"><?php esc_html_e( 'Apply', 'bhp-bundle-pricing' ); ?></button>
				</span>
			</form>
			<?php if ( 'custom' === $period ) : ?>
				<p class="description">
					<?php
					printf(
						/* translators: 1: start date, 2: end date */
						esc_html__( 'Showing %1$s through %2$s (inclusive, site local time).', 'bhp-bundle-pricing' ),
						esc_html( $display_start ),
						esc_html( $display_end )
					);
					?>
				</p>
			<?php endif; ?>

			<p class="bhp-dash-data-as-of description">
				<?php
				if ( $data_as_of ) {
					printf(
						/* translators: 1: formatted date/time in the site's own timezone, 2: "cached" or "freshly calculated" */
						esc_html__( 'Data as of %1$s (%2$s). This is a periodic snapshot, not a live/real-time feed.', 'bhp-bundle-pricing' ),
						esc_html( wp_date( 'Y-m-d H:i:s T', $data_as_of ) ),
						$data_from_cache ? esc_html__( 'cached', 'bhp-bundle-pricing' ) : esc_html__( 'freshly calculated', 'bhp-bundle-pricing' )
					);
				} else {
					esc_html_e( 'Data freshness unavailable for this view.', 'bhp-bundle-pricing' );
				}
				?>
			</p>

			<form method="post" class="bhp-dash-refresh-form">
				<?php wp_nonce_field( 'bhp_dashboard_refresh', 'bhp_dashboard_nonce' ); ?>
				<button type="submit" name="bhp_refresh_cache" value="1" class="button"><?php esc_html_e( 'Refresh now', 'bhp-bundle-pricing' ); ?></button>
				<span class="description"><?php esc_html_e( 'Figures auto-refresh every 5 minutes; use this to force an immediate recalculation.', 'bhp-bundle-pricing' ); ?></span>
			</form>

			<?php if ( 0 === $kpi['order_count'] ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'No orders in this period.', 'bhp-bundle-pricing' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Key metrics', 'bhp-bundle-pricing' ); ?></h2>
			<div class="bhp-dash-cards">
				<?php
				self::kpi_card(
					__( 'Gross sales', 'bhp-bundle-pricing' ),
					self::fmt_money( $kpi['gross_revenue'] ) . ' <span class="bhp-dash-card__note">(before refunds)</span>',
					$kpi['gross_revenue'], $prior_kpi['gross_revenue'], $has_prior_orders
				);
				self::kpi_card(
					__( 'Refunds', 'bhp-bundle-pricing' ),
					self::fmt_money( $kpi['refunds_total'] ),
					$kpi['refunds_total'], $prior_kpi['refunds_total'], $has_prior_orders, true,
					$kpi['refunds_total'] > 0
				);
				self::kpi_card(
					__( 'Net revenue', 'bhp-bundle-pricing' ),
					self::fmt_money( $kpi['net_revenue'] ) . ' <span class="bhp-dash-card__note">(gross sales &minus; refunds)</span>',
					$kpi['net_revenue'], $prior_kpi['net_revenue'], $has_prior_orders
				);
				self::kpi_card( __( 'Orders', 'bhp-bundle-pricing' ), (string) $kpi['order_count'], $kpi['order_count'], $prior_kpi['order_count'], $has_prior_orders );
				self::kpi_card(
					__( 'Average order value', 'bhp-bundle-pricing' ),
					null === $kpi['average_order_value'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : self::fmt_money( $kpi['average_order_value'] ),
					$kpi['average_order_value'], $prior_kpi['average_order_value'], $has_prior_orders
				);
				self::kpi_card( __( 'Units sold', 'bhp-bundle-pricing' ), (string) $kpi['units_sold'], $kpi['units_sold'], $prior_kpi['units_sold'], $has_prior_orders );
				self::kpi_card(
					__( 'Units per order', 'bhp-bundle-pricing' ),
					null === $kpi['units_per_order'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : (string) $kpi['units_per_order'],
					$kpi['units_per_order'], $prior_kpi['units_per_order'], $has_prior_orders
				);
				self::kpi_card(
					__( 'Bundle purchase rate', 'bhp-bundle-pricing' ),
					null === $kpi['bundle_purchase_rate'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : $kpi['bundle_purchase_rate'] . '%',
					$kpi['bundle_purchase_rate'], $prior_kpi['bundle_purchase_rate'], $has_prior_orders
				);
				self::kpi_card(
					__( 'Complete Collection rate', 'bhp-bundle-pricing' ),
					null === $kpi['complete_collection_rate'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : $kpi['complete_collection_rate'] . '%',
					$kpi['complete_collection_rate'], $prior_kpi['complete_collection_rate'], $has_prior_orders
				);
				self::kpi_card(
					__( 'Estimated gross profit', 'bhp-bundle-pricing' ),
					self::fmt_money( $kpi['estimated_profit_total'] ) . ' <span class="bhp-dash-card__note">(est., before refund impact)</span>',
					$kpi['estimated_profit_total'], $prior_kpi['estimated_profit_total'], $has_prior_orders
				);
				self::kpi_card(
					__( 'Estimated profit after refunds', 'bhp-bundle-pricing' ),
					self::fmt_money( $kpi['estimated_profit_after_refunds'] ) . ' <span class="bhp-dash-card__note">(est.)</span>',
					$kpi['estimated_profit_after_refunds'], $prior_kpi['estimated_profit_after_refunds'], $has_prior_orders
				);
				self::kpi_card(
					__( 'Payment failures', 'bhp-bundle-pricing' ),
					(string) $kpi['payment_failure_count'],
					$kpi['payment_failure_count'], $prior_kpi['payment_failure_count'], $has_prior_orders, true,
					$kpi['payment_failure_count'] > 0
				);
				self::kpi_card(
					__( 'Bookvault routing success rate', 'bhp-bundle-pricing' ),
					null === $kpi['bookvault_routing_success_rate'] ? esc_html__( 'No orders currently expected to route', 'bhp-bundle-pricing' ) : $kpi['bookvault_routing_success_rate'] . '%',
					$kpi['bookvault_routing_success_rate'], $prior_kpi['bookvault_routing_success_rate'], $has_prior_orders, false,
					null !== $kpi['bookvault_routing_success_rate'] && $kpi['bookvault_routing_success_rate'] < 100
				);
				self::kpi_card(
					__( 'Orders needing attention', 'bhp-bundle-pricing' ),
					(string) count( $kpi['manual_attention_orders'] ),
					count( $kpi['manual_attention_orders'] ), count( $prior_kpi['manual_attention_orders'] ), $has_prior_orders, true,
					count( $kpi['manual_attention_orders'] ) > 0
				);
				?>
			</div>

			<h2><?php esc_html_e( 'Order dataset & audit', 'bhp-bundle-pricing' ); ?></h2>
			<?php self::render_dataset_audit( $kpi ); ?>

			<h2><?php esc_html_e( 'Operational warnings', 'bhp-bundle-pricing' ); ?></h2>
			<?php self::render_warnings( $kpi ); ?>

			<h2><?php esc_html_e( 'Offer mix', 'bhp-bundle-pricing' ); ?></h2>
			<table class="widefat striped bhp-dash-table">
				<thead><tr><th><?php esc_html_e( 'Offer type', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Orders', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Share', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Estimated profit', 'bhp-bundle-pricing' ); ?></th></tr></thead>
				<tbody>
				<?php if ( 0 === $kpi['order_count'] ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No orders in this period.', 'bhp-bundle-pricing' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $labels as $type => $label ) :
						$count = $kpi['offer_counts'][ $type ];
						if ( 0 === $count ) { continue; }
						$share = round( ( $count / $kpi['order_count'] ) * 100, 1 );
						$profit = $kpi['per_offer_profit'][ $type ] ?? 0;
						$row_class = ( BHP_Offer_Classifier::LEGACY_PRECATALOG === $type ) ? ' class="bhp-dash-row--subdued"' : '';
						?>
						<tr<?php echo $row_class; // phpcs:ignore -- static, not user input ?>>
							<td><?php echo esc_html( $label ); ?></td>
							<td><?php echo esc_html( $count ); ?></td>
							<td><div class="bhp-dash-bar"><div class="bhp-dash-bar__fill" style="width: <?php echo esc_attr( $share ); ?>%"></div></div> <?php echo esc_html( $share ); ?>%</td>
							<td><?php echo esc_html( self::fmt_money( $profit ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<?php if ( $kpi['offer_counts'][ BHP_Offer_Classifier::LEGACY_PRECATALOG ] > 0 ) : ?>
				<p class="description"><?php esc_html_e( '"Legacy / pre-catalog" orders used a product ID from before the current catalog (see docs/kpi-definitions.md) -- they are excluded from bundle rate, Complete Collection rate, and format-mix calculations, the same as they were before this label existed.', 'bhp-bundle-pricing' ); ?></p>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Format mix (units)', 'bhp-bundle-pricing' ); ?></h2>
			<?php
			$total_units = $kpi['units_paperback'] + $kpi['units_hardcover'];
			$pb_share = $total_units > 0 ? round( ( $kpi['units_paperback'] / $total_units ) * 100, 1 ) : 0;
			$hc_share = $total_units > 0 ? round( ( $kpi['units_hardcover'] / $total_units ) * 100, 1 ) : 0;
			?>
			<table class="widefat striped bhp-dash-table">
				<thead><tr><th><?php esc_html_e( 'Format', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Units', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Share', 'bhp-bundle-pricing' ); ?></th></tr></thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Paperback', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['units_paperback'] ); ?></td><td><div class="bhp-dash-bar"><div class="bhp-dash-bar__fill" style="width: <?php echo esc_attr( $pb_share ); ?>%"></div></div> <?php echo esc_html( $pb_share ); ?>%</td></tr>
					<tr><td><?php esc_html_e( 'Hardcover', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['units_hardcover'] ); ?></td><td><div class="bhp-dash-bar"><div class="bhp-dash-bar__fill" style="width: <?php echo esc_attr( $hc_share ); ?>%"></div></div> <?php echo esc_html( $hc_share ); ?>%</td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Economics summary (this period)', 'bhp-bundle-pricing' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Computed from this period\'s actual, executive-eligible orders only -- see "Order dataset & audit" above. Reserve figures are a conservative estimate, not a report of real refunds (those are in "Refunds" above).', 'bhp-bundle-pricing' ); ?></p>
			<?php self::render_economics_summary( $kpi ); ?>

			<h2><?php esc_html_e( 'Product & offer economics', 'bhp-bundle-pricing' ); ?></h2>
			<p class="description"><?php esc_html_e( 'A prospective, per-offer cost model -- not tied to any specific real order. Used to set the acquisition-cost (CPA) targets below. See docs/economics-model.md for the full formula and every input\'s source.', 'bhp-bundle-pricing' ); ?></p>
			<?php self::render_offer_economics_table(); ?>

			<h2><?php esc_html_e( 'Acquisition cost (CPA) targets', 'bhp-bundle-pricing' ); ?></h2>
			<?php self::render_cpa_table(); ?>

			<h2><?php esc_html_e( 'Cost & reserve assumptions', 'bhp-bundle-pricing' ); ?></h2>
			<?php self::render_assumptions_panel(); ?>

			<h2><?php esc_html_e( 'Bookvault fulfillment summary', 'bhp-bundle-pricing' ); ?></h2>
			<p class="description">
				<?php
				printf(
					/* translators: 1: orders currently expected to fulfill via Bookvault, 2: total catalog-eligible orders, 3: orders excluded from that count */
					esc_html__( '%1$d of %2$d catalog-eligible orders this period are currently expected to fulfill via Bookvault (automatic routing only). %3$d are excluded from that automatic-routing denominator as historical, refunded, cancelled, or declined by Bookvault itself -- see the breakdown below.', 'bhp-bundle-pricing' ),
					$kpi['bookvault_expected_count'],
					$kpi['bookvault_eligible_count'],
					$kpi['bookvault_excluded_count']
				);
				?>
			</p>
			<table class="widefat striped bhp-dash-table">
				<thead><tr><th><?php esc_html_e( 'Bookvault records (this period)', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Orders', 'bhp-bundle-pricing' ); ?></th></tr></thead>
				<tbody>
					<tr><td><strong><?php esc_html_e( 'Total Bookvault records tied to Brave Hearts orders', 'bhp-bundle-pricing' ); ?></strong></td><td><strong><?php echo esc_html( $kpi['bookvault_total_records_count'] ); ?></strong></td></tr>
					<tr><td><?php esc_html_e( 'Automatically created from WooCommerce (routing success)', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['bookvault_created_count'] ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Manually created by Andrew after routing failure/test', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['bookvault_manual_fulfillment_count'] ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Automatic-routing success rate (eligible orders only)', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['bookvault_routing_success_rate'] !== null ? $kpi['bookvault_routing_success_rate'] . '%' : '—' ); ?></td></tr>
				</tbody>
			</table>
			<?php if ( $kpi['bookvault_manual_fulfillment_count'] > 0 ) : ?>
				<p class="bhp-dash-historical-note"><?php esc_html_e( 'A manually-fulfilled order is a genuine, real Bookvault record -- it is simply excluded from the automatic-routing success-rate calculation above (which measures only the WooCommerce->Bookvault integration itself), not from being counted as fulfilled. See the audit table below for which order(s).', 'bhp-bundle-pricing' ); ?></p>
			<?php endif; ?>
			<table class="widefat striped bhp-dash-table">
				<thead><tr><th><?php esc_html_e( 'Status', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Orders', 'bhp-bundle-pricing' ); ?></th></tr></thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Active in Bookvault', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['bookvault_active_count'] ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Draft in Bookvault (created, not yet Active)', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['bookvault_draft_count'] ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Awaiting routing (within normal window)', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( max( 0, $kpi['bookvault_expected_count'] - $kpi['bookvault_created_count'] - $kpi['bookvault_action_required_count'] ) ); ?></td></tr>
					<tr class="bhp-dash-row--critical"><td><?php esc_html_e( 'Action required now', 'bhp-bundle-pricing' ); ?></td><td><?php echo esc_html( $kpi['bookvault_action_required_count'] ); ?></td></tr>
				</tbody>
			</table>
			<?php if ( $kpi['bookvault_excluded_count'] > 0 ) : ?>
				<table class="widefat striped bhp-dash-table bhp-dash-table--subdued">
					<thead><tr><th><?php esc_html_e( 'Excluded reason (no action needed)', 'bhp-bundle-pricing' ); ?></th><th><?php esc_html_e( 'Orders', 'bhp-bundle-pricing' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( self::bookvault_excluded_reason_labels() as $reason => $reason_label ) :
							$count = $kpi['bookvault_excluded_reasons'][ $reason ] ?? 0;
							if ( 0 === $count ) { continue; }
							?>
							<tr><td><?php echo esc_html( $reason_label ); ?></td><td><?php echo esc_html( $count ); ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'In-production / shipped / tracking status is not available from any current data source (no Bookvault tracking webhook or API integration exists yet) -- check the Bookvault portal directly for shipment status.', 'bhp-bundle-pricing' ); ?></p>

			<h2><?php esc_html_e( 'Recent orders', 'bhp-bundle-pricing' ); ?></h2>
			<?php self::render_recent_orders_table( $start, $end ); ?>

			<h2><?php esc_html_e( 'Organic content-to-lead conversion (Phase 1D)', 'bhp-bundle-pricing' ); ?></h2>
			<?php self::render_conversion_funnel_panel(); ?>

			<h2><?php esc_html_e( 'Future analytics (not yet connected)', 'bhp-bundle-pricing' ); ?></h2>
			<div class="bhp-dash-future-panel">
				<p><strong><?php esc_html_e( 'Analytics connection required.', 'bhp-bundle-pricing' ); ?></strong> <?php esc_html_e( 'Sessions, conversion rate, funnel drop-off, and traffic-source revenue will appear here once GA4/GTM is installed. See the GA4/GTM implementation plan for what is needed.', 'bhp-bundle-pricing' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Phase 1D: content-classification coverage + lead-signup summary,
	 * integrated into this SAME existing dashboard rather than a second
	 * admin page. Both data sources are already-existing, cheap,
	 * capability-gated reads (BHP_Content_Classification::coverage_stats()
	 * and BHP_Lead_Event_Log::get_summary()) -- coverage_stats() is
	 * cached via the same BHP_KPI_Cache mechanism the rest of this
	 * dashboard already uses, so it is never recalculated on every
	 * page load. No PII anywhere in this panel.
	 */
	private static function render_conversion_funnel_panel() {
		if ( ! class_exists( 'BHP_Content_Classification' ) || ! class_exists( 'BHP_Lead_Event_Log' ) ) {
			echo '<p class="description">' . esc_html__( 'Content classification / lead-event modules are not available (theme not active or not loaded).', 'bhp-bundle-pricing' ) . '</p>';
			return;
		}

		$coverage = BHP_KPI_Cache::get( 'phase1d_classification_coverage', function () {
			return BHP_Content_Classification::coverage_stats();
		} );

		echo '<h3>' . esc_html__( 'Content classification coverage', 'bhp-bundle-pricing' ) . '</h3>';
		echo '<div class="bhp-dash-cards bhp-dash-cards--compact">';
		self::mini_stat_card( __( 'Total eligible content', 'bhp-bundle-pricing' ), (string) $coverage['total'] );
		self::mini_stat_card( __( 'Explicitly classified', 'bhp-bundle-pricing' ), (string) $coverage['explicit'] );
		self::mini_stat_card( __( 'Registry-derived (smart default)', 'bhp-bundle-pricing' ), (string) $coverage['registry_derived'] );
		self::mini_stat_card( __( 'Unclassified (flat default)', 'bhp-bundle-pricing' ), (string) $coverage['unclassified'], $coverage['unclassified'] > 0 );
		self::mini_stat_card( __( 'Missing lead-offer/featured-book', 'bhp-bundle-pricing' ), (string) $coverage['missing_lead_offer_or_book'], $coverage['missing_lead_offer_or_book'] > 0 );
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Unclassified content still gets a safe, graceful-fallback CTA (see BHP_CTA_Engine) -- this is a coverage/prioritization signal, not an error state.', 'bhp-bundle-pricing' ) . '</p>';

		$lead_summary = BHP_Lead_Event_Log::get_summary( 30 );

		echo '<h3>' . esc_html__( 'Lead signups, last 30 days', 'bhp-bundle-pricing' ) . '</h3>';
		echo '<div class="bhp-dash-cards bhp-dash-cards--compact">';
		self::mini_stat_card( __( 'Real successful signups', 'bhp-bundle-pricing' ), (string) $lead_summary['real']['success'] );
		self::mini_stat_card( __( 'Real failed signups', 'bhp-bundle-pricing' ), (string) $lead_summary['real']['failed'], $lead_summary['real']['failed'] > 0 );
		self::mini_stat_card( __( 'Test/staging signups (excluded above)', 'bhp-bundle-pricing' ), (string) ( $lead_summary['test']['success'] + $lead_summary['test']['failed'] ) );
		echo '</div>';

		if ( ! empty( $lead_summary['by_placement'] ) ) {
			echo '<table class="widefat striped bhp-dash-table"><thead><tr><th>' . esc_html__( 'Placement', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Real signups', 'bhp-bundle-pricing' ) . '</th></tr></thead><tbody>';
			foreach ( $lead_summary['by_placement'] as $placement => $count ) {
				echo '<tr><td>' . esc_html( $placement ) . '</td><td>' . esc_html( $count ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<p class="description">' . sprintf(
			/* translators: %s: link to the Lead Signups admin page */
			esc_html__( 'Full per-signup detail (including test-provenance classification) is at %s -- this panel is a summary only, not a duplicate of that page.', 'bhp-bundle-pricing' ),
			'<a href="' . esc_url( admin_url( 'tools.php?page=bhp-lead-signups' ) ) . '">' . esc_html__( 'Tools -> Lead Signups', 'bhp-bundle-pricing' ) . '</a>'
		) . '</p>';
		echo '<p class="description">' . sprintf(
			/* translators: %s: link to the conversion-readiness scoring sample-run doc */
			esc_html__( 'Conversion-readiness scores (audience clarity, CTA relevance, analytics coverage, and more) are not yet live in this dashboard -- run %s manually via WP-CLI. See docs/phase1d-conversion-scoring-sample-run.md for the last sample run.', 'bhp-bundle-pricing' ),
			'<code>wp eval-file content-engine/scripts/run-conversion-scoring-sample.php</code>'
		) . '</p>';
	}

	/**
	 * Dataset transparency section (Phase 3/5 dataset-origin correction,
	 * 2026-07-06): a compact audit summary plus a full per-order
	 * historical/audit table, so an excluded order can always be reviewed
	 * without it ever influencing executive performance figures. No
	 * customer PII anywhere -- order ID, date, status, total, and origin
	 * classification only.
	 */
	private static function render_dataset_audit( $kpi ) {
		$origin_labels = class_exists( 'BHP_Order_Provenance' ) ? BHP_Order_Provenance::origin_labels() : array();
		$status_labels = class_exists( 'BHP_Order_Provenance' ) ? BHP_Order_Provenance::reporting_status_labels() : array();

		echo '<div class="bhp-dash-cards bhp-dash-cards--compact">';
		self::mini_stat_card( __( 'Live production orders included', 'bhp-bundle-pricing' ), (string) $kpi['order_count'] );
		self::mini_stat_card( __( 'Test/staging/admin orders excluded', 'bhp-bundle-pricing' ), $kpi['excluded_test_order_count'] . ' (' . self::fmt_money( $kpi['excluded_test_order_value'] ) . ')' );
		self::mini_stat_card( __( 'Unknown-origin orders (excluded pending confirmation)', 'bhp-bundle-pricing' ), $kpi['excluded_unknown_order_count'] . ' (' . self::fmt_money( $kpi['excluded_unknown_order_value'] ) . ')', $kpi['excluded_unknown_order_count'] > 0 );
		self::mini_stat_card( __( 'Genuine customer refunds', 'bhp-bundle-pricing' ), $kpi['refund_count'] . ' (' . self::fmt_money( $kpi['refunds_total'] ) . ')' );
		self::mini_stat_card( __( 'Test-order refunds (excluded from customer refund total)', 'bhp-bundle-pricing' ), $kpi['test_refund_count'] . ' (' . self::fmt_money( $kpi['test_refunds_total'] ) . ')' );
		self::mini_stat_card( __( 'Genuine customer payment failures', 'bhp-bundle-pricing' ), (string) $kpi['payment_failure_count'] );
		self::mini_stat_card( __( 'Admin/test payment failures (excluded)', 'bhp-bundle-pricing' ), (string) $kpi['test_payment_failure_count'] );
		echo '</div>';

		if ( $kpi['excluded_unknown_order_count'] > 0 ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'One or more orders have circumstantial but not definitive test-origin evidence and are excluded from executive KPIs by default. Andrew\'s confirmation is needed to classify them permanently -- see the table below and docs/order-provenance-audit.md.', 'bhp-bundle-pricing' ) . '</p></div>';
		}

		if ( empty( $kpi['audit_orders'] ) ) {
			echo '<p class="description">' . esc_html__( 'No orders (of any origin) in this period.', 'bhp-bundle-pricing' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped bhp-dash-table bhp-dash-table--subdued">';
		echo '<thead><tr>';
		foreach ( array( 'Order', 'Date', 'Status', 'Total', 'Origin classification', 'Reporting status', 'Reason' ) as $col ) {
			echo '<th>' . esc_html__( $col, 'bhp-bundle-pricing' ) . '</th>'; // phpcs:ignore -- static column labels
		}
		echo '</tr></thead><tbody>';
		foreach ( $kpi['audit_orders'] as $row ) {
			$origin_label = $origin_labels[ $row['origin'] ] ?? $row['origin'];
			$status_label = $status_labels[ $row['reporting_status'] ] ?? $row['reporting_status'];
			$row_class = BHP_Order_Provenance::STATUS_INCLUDE === $row['reporting_status'] ? '' : ' class="bhp-dash-row--subdued"';
			echo '<tr' . $row_class . '>'; // phpcs:ignore -- static class list
			echo '<td>#' . esc_html( $row['order_id'] ) . '</td>';
			echo '<td>' . esc_html( $row['date'] ) . '</td>';
			echo '<td>' . esc_html( wc_get_order_status_name( $row['status'] ) ) . '</td>';
			echo '<td>' . esc_html( self::fmt_money( $row['total'] ) ) . '</td>';
			echo '<td>' . esc_html( $origin_label ) . '</td>';
			echo '<td>' . esc_html( $status_label ) . '</td>';
			echo '<td>' . esc_html( $row['reason'] ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'This table includes every order in the selected period regardless of executive-KPI inclusion, so an excluded order can always be reviewed here without affecting business performance figures above. See docs/order-provenance-audit.md for the full evidence behind each classification.', 'bhp-bundle-pricing' ) . '</p>';
	}

	private static function mini_stat_card( $label, $value, $warning = false ) {
		$warning_class = $warning ? ' bhp-dash-card--warning' : '';
		echo '<div class="bhp-dash-card' . esc_attr( $warning_class ) . '">';
		echo '<div class="bhp-dash-card__label">' . esc_html( $label ) . '</div>';
		echo '<div class="bhp-dash-card__value bhp-dash-card__value--compact">' . esc_html( $value ) . '</div>';
		echo '</div>';
	}

	/**
	 * Human-readable label for the title keys in an offer_table() row,
	 * e.g. ['everest','amazon'] -> "Mount Everest + The Amazon".
	 */
	private static function title_keys_label( array $title_keys ) {
		$catalog_labels = array(
			'mariana' => __( 'The Mariana Trench', 'bhp-bundle-pricing' ),
			'everest' => __( 'Mount Everest', 'bhp-bundle-pricing' ),
			'amazon'  => __( 'The Amazon', 'bhp-bundle-pricing' ),
		);
		$parts = array_map( function ( $key ) use ( $catalog_labels ) {
			return $catalog_labels[ $key ] ?? $key;
		}, $title_keys );
		return implode( ' + ', $parts );
	}

	private static function offer_type_display_label( $offer_type ) {
		$labels = array(
			BHP_Offer_Economics::SINGLE_PAPERBACK       => __( 'Single paperback', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::SINGLE_HARDCOVER       => __( 'Single hardcover', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE   => __( 'Two-paperback bundle', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE   => __( 'Two-hardcover bundle', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::COMPLETE_PAPERBACK_SET => __( 'Complete Paperback Collection', 'bhp-bundle-pricing' ),
			BHP_Offer_Economics::COMPLETE_HARDCOVER_SET => __( 'Complete Hardcover Collection', 'bhp-bundle-pricing' ),
		);
		return $labels[ $offer_type ] ?? $offer_type;
	}

	/**
	 * Executive economics summary (Phase 1A): a small, focused card row --
	 * deliberately separate from "Key metrics" above so the existing,
	 * already-dense executive overview isn't cluttered. Built entirely
	 * from this period's actual executive-eligible orders (already
	 * provenance-filtered) -- never from the prospective offer-economics
	 * table below, which models hypothetical future orders instead.
	 */
	private static function render_economics_summary( $kpi ) {
		echo '<div class="bhp-dash-cards bhp-dash-cards--compact">';
		self::mini_stat_card(
			__( 'Contribution before acquisition', 'bhp-bundle-pricing' ),
			null === $kpi['estimated_profit_after_refunds'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : self::fmt_money( $kpi['estimated_profit_after_refunds'] ) . ' (est.)'
		);
		self::mini_stat_card(
			__( 'Contribution after reserves', 'bhp-bundle-pricing' ),
			null === $kpi['contribution_after_reserves'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : self::fmt_money( $kpi['contribution_after_reserves'] ) . ' (est.)'
		);
		self::mini_stat_card(
			__( 'Contribution margin', 'bhp-bundle-pricing' ),
			null === $kpi['contribution_margin_pct'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : $kpi['contribution_margin_pct'] . '%'
		);
		self::mini_stat_card(
			__( 'Profit per order', 'bhp-bundle-pricing' ),
			null === $kpi['profit_per_order'] ? esc_html__( 'No orders', 'bhp-bundle-pricing' ) : self::fmt_money( $kpi['profit_per_order'] )
		);
		self::mini_stat_card(
			__( 'Profit per unit', 'bhp-bundle-pricing' ),
			null === $kpi['profit_per_unit'] ? esc_html__( 'No units', 'bhp-bundle-pricing' ) : self::fmt_money( $kpi['profit_per_unit'] )
		);
		self::mini_stat_card(
			__( 'Data-quality status', 'bhp-bundle-pricing' ),
			'complete' === $kpi['data_quality_status'] ? esc_html__( 'Complete', 'bhp-bundle-pricing' ) : sprintf(
				/* translators: %d: number of orders with unknown cost */
				esc_html__( 'Partial -- %d order(s) unknown cost', 'bhp-bundle-pricing' ),
				$kpi['orders_with_unknown_cost_count']
			),
			'complete' !== $kpi['data_quality_status']
		);
		echo '</div>';
		if ( $kpi['orders_with_unknown_cost_count'] > 0 ) {
			echo '<p class="bhp-dash-historical-note">' . sprintf(
				/* translators: %d: number of orders */
				esc_html( _n(
					'%d order this period contains a product with no cost mapping -- its revenue is counted above, but its profit contribution is excluded from these figures (shown as unknown, not zero) until the mapping is added.',
					'%d orders this period contain a product with no cost mapping -- their revenue is counted above, but their profit contribution is excluded from these figures (shown as unknown, not zero) until the mapping is added.',
					$kpi['orders_with_unknown_cost_count'],
					'bhp-bundle-pricing'
				) ),
				$kpi['orders_with_unknown_cost_count']
			) . '</p>';
		}
	}

	/**
	 * Product & offer economics table (Phase 1A): one row per real,
	 * purchasable offer, including every distinct 2-book combination
	 * separately. Every dollar figure here is prospective/modeled, not
	 * tied to a specific real order -- see the class docblock on
	 * BHP_Offer_Economics for why this is intentionally kept separate
	 * from the real-period "Economics summary" above. Joins in each
	 * offer's CPA-by-type figures (preferred/safer-max CPA, strategic use)
	 * so the full picture is in one table per Phase 1A's dashboard spec.
	 */
	private static function render_offer_economics_table() {
		if ( ! class_exists( 'BHP_Offer_Economics' ) ) {
			echo '<p class="description">' . esc_html__( 'Offer economics module not available.', 'bhp-bundle-pricing' ) . '</p>';
			return;
		}
		$rows = BHP_Offer_Economics::offer_table();
		$cpa_by_type = array();
		if ( class_exists( 'BHP_CPA_Model' ) ) {
			foreach ( BHP_CPA_Model::build_table() as $cpa_row ) {
				$cpa_by_type[ $cpa_row['offer_type'] ] = $cpa_row;
			}
		}

		echo '<table class="widefat striped bhp-dash-table">';
		echo '<thead><tr>';
		foreach ( array( 'Offer', 'Titles', 'Price', 'Shipping collected', 'Discount', 'Print cost', 'Bookvault postage', 'Stripe fee', 'Reserve amount', 'Contribution before ads', 'Margin', 'CPA (target / ceiling)', 'Strategic use', 'Basis' ) as $col ) {
			echo '<th>' . esc_html__( $col, 'bhp-bundle-pricing' ) . '</th>'; // phpcs:ignore -- static column labels
		}
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$cpa = $cpa_by_type[ $row['offer_type'] ] ?? null;

			echo '<tr>';
			echo '<td>' . esc_html( self::offer_type_display_label( $row['offer_type'] ) ) . '</td>';
			echo '<td>' . esc_html( self::title_keys_label( $row['title_keys'] ) ) . '</td>';
			echo '<td>' . esc_html( self::fmt_money( $row['price'] ) ) . '</td>';
			echo '<td>' . esc_html( self::fmt_money( $row['shipping_collected'] ) ) . '</td>';
			echo '<td>' . esc_html( $row['discount'] > 0 ? '-' . self::fmt_money( $row['discount'] ) : '—' ) . '</td>';
			echo '<td>' . esc_html( self::fmt_money( $row['print_cost']['amount'] ) ) . ' <span class="bhp-dash-card__note">(est.)</span></td>';
			echo '<td>' . esc_html( self::fmt_money( $row['postage']['amount'] ) ) . ' <span class="bhp-dash-card__note">(est.)</span></td>';
			echo '<td>' . esc_html( self::fmt_money( $row['stripe_fee'] ) ) . ' <span class="bhp-dash-card__note">(est.)</span></td>';
			echo '<td>' . esc_html( self::fmt_money( $row['reserves']['total'] ) ) . ' <span class="bhp-dash-card__note">(est.)</span></td>';
			echo '<td><strong>' . esc_html( 'unknown' === $row['basis'] ? __( 'Unknown', 'bhp-bundle-pricing' ) : self::fmt_money( $row['contribution_before_acquisition'] ) ) . '</strong></td>';
			echo '<td>' . esc_html( ( 'unknown' === $row['basis'] || null === $row['contribution_margin_pct'] ) ? '—' : $row['contribution_margin_pct'] . '%' ) . '</td>';
			echo '<td>' . self::cpa_cell_html( $cpa ) . '</td>';
			echo '<td>' . esc_html( $cpa['strategic_statement'] ?? '—' ) . '</td>';
			echo '<td><span class="bhp-dash-basis bhp-dash-basis--' . esc_attr( $row['basis'] ) . '">' . esc_html( 'unknown' === $row['basis'] ? __( 'Unknown', 'bhp-bundle-pricing' ) : __( 'Estimated', 'bhp-bundle-pricing' ) ) . '</span></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Price/shipping/discount are the actual, live storefront values. Print cost, Bookvault postage, Stripe fee, and reserves are estimated -- see the Assumptions panel below for each figure\'s source and date. Only the two Complete Collections have an Andrew-approved target CPA; the other offers show a break-even figure and a labeled model-estimate ceiling only -- see the CPA table below for the full breakdown.', 'bhp-bundle-pricing' ) . '</p>';
	}

	/**
	 * Compact CPA cell for the product economics table -- approved offers
	 * show a bold dollar target; non-approved offers explicitly say so
	 * rather than showing a number that could be mistaken for policy.
	 *
	 * 1.8.30: an environment whose acquisition-policy option is unseeded
	 * has no target and no ceiling to show. It says so. It must never fall
	 * through to fmt_money( null ), which would render a confident $0.00.
	 */
	private static function cpa_cell_html( $cpa ) {
		if ( ! $cpa || 'unknown' === $cpa['basis'] ) {
			return '—';
		}
		if ( 'unavailable' === $cpa['ceiling_basis'] ) {
			return '<span class="bhp-dash-basis bhp-dash-basis--na">' . esc_html__( 'Acquisition policy not configured', 'bhp-bundle-pricing' ) . '</span>';
		}
		if ( $cpa['cold_acquisition_approved'] ) {
			return '<strong>' . esc_html( self::fmt_money( $cpa['target_cpa'] ) . ' or less' ) . '</strong> <span class="bhp-dash-card__note">(approved; ceiling ' . esc_html( self::fmt_money( $cpa['safer_ceiling_low'] ) . '–' . self::fmt_money( $cpa['safer_ceiling_high'] ) ) . ')</span>';
		}
		return '<span class="bhp-dash-basis bhp-dash-basis--na">' . esc_html__( 'No approved cold-acquisition target', 'bhp-bundle-pricing' ) . '</span> <span class="bhp-dash-card__note">(model estimate ceiling: ' . esc_html( self::fmt_money( $cpa['safer_ceiling_low'] ) . '–' . self::fmt_money( $cpa['safer_ceiling_high'] ) ) . ')</span>';
	}

	/**
	 * CPA (allowable acquisition cost) table (Phase 1A): one row per offer
	 * TYPE, using the conservative (lowest) contribution among that type's
	 * real combinations -- see BHP_CPA_Model's class docblock. Only the
	 * two Complete Collections carry an Andrew-approved preferred target;
	 * the other four show contribution/break-even (always real) and a
	 * clearly-labeled MODEL ESTIMATE ceiling only, never a definitive
	 * target, per approved company policy (2026-07-06).
	 */
	private static function render_cpa_table() {
		if ( ! class_exists( 'BHP_CPA_Model' ) ) {
			echo '<p class="description">' . esc_html__( 'CPA model not available.', 'bhp-bundle-pricing' ) . '</p>';
			return;
		}
		$rows = BHP_CPA_Model::build_table();

		echo '<table class="widefat striped bhp-dash-table">';
		echo '<thead><tr>';
		foreach ( array( 'Offer', 'Contribution before ads', 'Theoretical break-even CPA', 'Preferred target CPA (approved)', 'Operating ceiling', 'Hard stop', 'Cold acquisition approved?', 'Strategic use' ) as $col ) {
			echo '<th>' . esc_html__( $col, 'bhp-bundle-pricing' ) . '</th>'; // phpcs:ignore -- static column labels
		}
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$is_unknown = 'unknown' === $row['basis'];
			echo '<tr>';
			echo '<td>' . esc_html( $row['offer_label'] ) . '</td>';
			echo '<td>' . esc_html( $is_unknown ? __( 'Unknown', 'bhp-bundle-pricing' ) : self::fmt_money( $row['contribution_before_acquisition'] ) ) . '</td>';
			echo '<td>' . esc_html( $is_unknown ? '—' : self::fmt_money( $row['theoretical_breakeven_cpa'] ) ) . '</td>';
			if ( $is_unknown ) {
				echo '<td>—</td>';
			} elseif ( null === $row['target_cpa'] && 'unavailable' === $row['ceiling_basis'] ) {
				// 1.8.30: policy not seeded on this environment. Never
				// fmt_money( null ) -- a rendered $0.00 target would read as
				// a decision rather than as a missing one.
				echo '<td><span class="bhp-dash-basis bhp-dash-basis--na">' . esc_html__( 'Not configured', 'bhp-bundle-pricing' ) . '</span></td>';
			} elseif ( $row['cold_acquisition_approved'] ) {
				echo '<td><strong>' . esc_html( self::fmt_money( $row['target_cpa'] ) . ' or less' ) . '</strong></td>';
			} else {
				echo '<td><span class="bhp-dash-basis bhp-dash-basis--na">' . esc_html__( 'No approved cold-acquisition target', 'bhp-bundle-pricing' ) . '</span></td>';
			}
			if ( $is_unknown ) {
				echo '<td>—</td>';
			} elseif ( 'unavailable' === $row['ceiling_basis'] ) {
				echo '<td><span class="bhp-dash-basis bhp-dash-basis--na">' . esc_html__( 'Not configured', 'bhp-bundle-pricing' ) . '</span></td>';
			} else {
				$ceiling_note = 'approved' === $row['ceiling_basis'] ? esc_html__( 'approved', 'bhp-bundle-pricing' ) : esc_html__( 'model estimate', 'bhp-bundle-pricing' );
				/*
				 * ⭐ 1.8.23 — an approved ceiling that has drifted above live
				 *    break-even is named on the row, not left to be spotted
				 *    by comparing two columns. Free collection shipping put
				 *    both Complete Collections into that state
				 *    (`CYCLE143-FIN-11`). This warns; it never edits an
				 *    approved figure, which is Andrew's alone.
				 */
				if ( ! empty( $row['ceiling_exceeds_breakeven'] ) ) {
					$ceiling_note .= ' &middot; ' . esc_html__( 'ABOVE break-even, needs re-approval', 'bhp-bundle-pricing' );
				}
				echo '<td>' . esc_html( self::fmt_money( $row['safer_ceiling_low'] ) . '–' . self::fmt_money( $row['safer_ceiling_high'] ) ) . ' <span class="bhp-dash-card__note">(' . $ceiling_note . ')</span></td>'; // phpcs:ignore -- $ceiling_note is one of two static, already-escaped strings
			}
			echo '<td>' . esc_html( $is_unknown ? '—' : self::fmt_money( $row['hard_stop_cpa'] ) ) . '</td>';
			echo '<td>' . ( $row['cold_acquisition_approved'] ? '<span class="bhp-dash-basis bhp-dash-basis--actual">' . esc_html__( 'Yes', 'bhp-bundle-pricing' ) . '</span>' : '<span class="bhp-dash-basis bhp-dash-basis--na">' . esc_html__( 'No', 'bhp-bundle-pricing' ) . '</span>' ) . '</td>';
			echo '<td>' . esc_html( $row['strategic_statement'] ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'No paid acquisition channel is connected yet (Phase 1A is the cost model only) -- these are planning figures, not a report of actual ad performance. Only the two Complete Collections are approved for cold paid acquisition; the "model estimate" ceilings on the other four offers are informational only, extrapolated from the same ratio as the approved Collections, and are NOT approved company policy. Do not treat a positive break-even as an endorsement to spend -- see the strategic-use column.', 'bhp-bundle-pricing' ) . '</p>';
	}

	/**
	 * Assumptions panel (Phase 1A): every cost/reserve input behind the
	 * tables above, with its basis, source, and effective date, so an
	 * estimate is never mistaken for a confirmed figure.
	 */
	private static function render_assumptions_panel() {
		if ( ! class_exists( 'BHP_Cost_Config' ) ) {
			return;
		}
		$rows = array();

		$titles = BHP_Cost_Config::title_print_cost();
		foreach ( $titles as $format => $by_title ) {
			foreach ( $by_title as $title_key => $cfg ) {
				$rows[] = array( ucfirst( $format ) . ' print cost — ' . self::title_keys_label( array( $title_key ) ), self::fmt_money( $cfg['amount'] ) . '/unit', $cfg['basis'], $cfg['source'], $cfg['effective_date'] );
			}
		}

		$pb_single_postage = BHP_Cost_Config::bookvault_postage_for_offer( 'paperback', 1 );
		$bundle_postage    = BHP_Cost_Config::bookvault_postage_for_offer( 'hardcover', 1 );
		$rows[] = array( 'Bookvault postage — single paperback', self::fmt_money( $pb_single_postage['amount'] ) . '/order', $pb_single_postage['basis'], $pb_single_postage['source'], $pb_single_postage['effective_date'] );
		$rows[] = array( 'Bookvault postage — single hardcover / any bundle', self::fmt_money( $bundle_postage['amount'] ) . '/order', $bundle_postage['basis'], $bundle_postage['source'], $bundle_postage['effective_date'] );

		$stripe = BHP_Cost_Config::stripe_fee_formula();
		$rows[] = array( 'Stripe processing fee', ( $stripe['percentage'] * 100 ) . '% + ' . self::fmt_money( $stripe['fixed'] ), $stripe['basis'], $stripe['source'], $stripe['effective_date'] );

		$refund = BHP_Cost_Config::refund_reserve_percentage();
		$rows[] = array( 'Refund reserve', ( $refund['percentage'] * 100 ) . '% of price + shipping', $refund['basis'], $refund['source'], $refund['effective_date'] );

		$replacement = BHP_Cost_Config::replacement_reserve_percentage();
		$rows[] = array( 'Replacement/damage reserve', ( $replacement['percentage'] * 100 ) . '% of price + shipping', $replacement['basis'], $replacement['source'], $replacement['effective_date'] );

		$chargeback = BHP_Cost_Config::chargeback_reserve_percentage();
		$rows[] = array( 'Chargeback reserve (optional, not yet applied)', ( $chargeback['percentage'] * 100 ) . '% of price + shipping', $chargeback['basis'], $chargeback['source'], $chargeback['effective_date'] );

		$sample = BHP_Cost_Config::sample_allocation_per_unit();
		$rows[] = array( 'Creator sample allocation', self::fmt_money( $sample['amount'] ) . '/unit', $sample['basis'], $sample['source'], $sample['effective_date'] );

		echo '<table class="widefat striped bhp-dash-table bhp-dash-table--subdued">';
		echo '<thead><tr><th>' . esc_html__( 'Input', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Current value', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Basis', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Source', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Effective date', 'bhp-bundle-pricing' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			list( $label, $value, $basis, $source, $date ) = $r;
			$basis_class = 'not_applicable' === $basis ? 'bhp-dash-basis--na' : 'bhp-dash-basis--estimated';
			$basis_display = 'not_applicable' === $basis ? __( 'N/A (no program yet)', 'bhp-bundle-pricing' ) : ucfirst( $basis );
			echo '<tr>';
			echo '<td>' . esc_html( $label ) . '</td>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '<td><span class="bhp-dash-basis ' . esc_attr( $basis_class ) . '">' . esc_html( $basis_display ) . '</span></td>';
			echo '<td>' . esc_html( $source ) . '</td>';
			echo '<td>' . esc_html( $date ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Every value above is estimated, not a confirmed invoice/statement figure, unless otherwise noted. See docs/economics-model.md and docs/cost-sources.md for what would upgrade an estimate to "actual."', 'bhp-bundle-pricing' ) . '</p>';
	}

	private static function render_warnings( $kpi ) {
		$warnings = array();

		if ( $kpi['bookvault_action_required_count'] > 0 ) {
			$warnings[] = array(
				'severity' => 'critical',
				'message'  => sprintf(
					/* translators: %d: number of orders */
					_n(
						'%d paid order needs Bookvault attention now.',
						'%d paid orders need Bookvault attention now.',
						$kpi['bookvault_action_required_count'],
						'bhp-bundle-pricing'
					),
					$kpi['bookvault_action_required_count']
				),
				'next_step' => __( 'Open each flagged order in WooCommerce, confirm the Bookvault routing note, and resend from the Bookvault portal if needed.', 'bhp-bundle-pricing' ),
			);
		}

		if ( $kpi['payment_failure_count'] > 0 ) {
			$warnings[] = array(
				'severity' => 'warning',
				'message'  => sprintf(
					/* translators: %d: number of failed payments */
					_n( '%d payment failure recorded.', '%d payment failures recorded.', $kpi['payment_failure_count'], 'bhp-bundle-pricing' ),
					$kpi['payment_failure_count']
				),
				'next_step' => __( 'Review the failed orders in WooCommerce -> Orders for patterns (declined cards, 3DS failures, etc.).', 'bhp-bundle-pricing' ),
			);
		}

		if ( empty( $warnings ) ) {
			echo '<p class="bhp-dash-no-warnings">' . esc_html__( 'No warnings for this period.', 'bhp-bundle-pricing' ) . '</p>';
		} else {
			echo '<table class="widefat striped bhp-dash-table bhp-dash-warnings">';
			echo '<thead><tr><th>' . esc_html__( 'Severity', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Issue', 'bhp-bundle-pricing' ) . '</th><th>' . esc_html__( 'Recommended next step', 'bhp-bundle-pricing' ) . '</th></tr></thead><tbody>';
			foreach ( $warnings as $w ) {
				echo '<tr class="bhp-dash-warning-row bhp-dash-warning-row--' . esc_attr( $w['severity'] ) . '">';
				echo '<td>' . esc_html( ucfirst( $w['severity'] ) ) . '</td>';
				echo '<td>' . esc_html( $w['message'] ) . '</td>';
				echo '<td>' . esc_html( $w['next_step'] ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		// Historical/excluded orders are intentionally never mixed into the
		// alert table above -- showing them as "warnings" would create
		// alert fatigue for things that need no action (a refunded order,
		// an order Bookvault itself declined, an order from before the
		// integration existed). This subdued, separate note answers "why
		// isn't that order counted" without implying it needs attention.
		if ( $kpi['bookvault_excluded_count'] > 0 ) {
			$parts = array();
			foreach ( self::bookvault_excluded_reason_labels() as $reason => $reason_label ) {
				$count = $kpi['bookvault_excluded_reasons'][ $reason ] ?? 0;
				if ( $count > 0 ) {
					$parts[] = $count . ' ' . lcfirst( $reason_label );
				}
			}
			echo '<p class="bhp-dash-historical-note">' . sprintf(
				/* translators: 1: count of excluded orders, 2: comma-separated reason breakdown */
				esc_html(
					_n(
						'%1$d catalog-eligible order this period is historical/excluded from Bookvault routing metrics (%2$s) and needs no action.',
						'%1$d catalog-eligible orders this period are historical/excluded from Bookvault routing metrics (%2$s) and need no action.',
						$kpi['bookvault_excluded_count'],
						'bhp-bundle-pricing'
					)
				),
				$kpi['bookvault_excluded_count'],
				esc_html( implode( '; ', $parts ) )
			) . '</p>';
		}
	}

	private static function render_recent_orders_table( $start, $end ) {
		$orders = BHP_Order_Metrics::get_valid_paid_orders( $start, $end );

		if ( empty( $orders ) ) {
			echo '<p>' . esc_html__( 'No orders in this period.', 'bhp-bundle-pricing' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped bhp-dash-table">';
		echo '<thead><tr>';
		foreach ( array( 'Order', 'Date', 'Offer type', 'Units', 'Order total', 'Refund', 'Payment status', 'Bookvault status', 'Bookvault ref', 'Time to route', 'Attention' ) as $col ) {
			echo '<th>' . esc_html__( $col, 'bhp-bundle-pricing' ) . '</th>'; // phpcs:ignore -- static column labels
		}
		echo '</tr></thead><tbody>';

		$labels = BHP_Offer_Classifier::labels();

		foreach ( $orders as $order ) {
			$classification = BHP_Offer_Classifier::classify_order( $order );
			$units = $classification['units_paperback'] + $classification['units_hardcover'];
			$is_eligible = BHP_Bookvault_Status::order_is_bookvault_eligible( $order );

			$bv_status_label = 'Not applicable';
			$bv_ref = '—';
			$time_to_route = '—';
			$attention = 'OK';
			$attention_class = 'ok';

			if ( $is_eligible ) {
				$fulfillment = BHP_Order_Metrics::bookvault_fulfillment_status( $order );
				$bv          = $fulfillment['bv'];

				if ( 'routed' === $bv['status'] ) {
					$bv_status_label = $bv['bookvault_state_is_draft']
						? 'Routed (Draft — not yet Active)'
						: 'Routed (' . $bv['bookvault_state'] . ')';
					$bv_ref = $bv['bookvault_ref'] ? $bv['bookvault_ref'] : '—';
					$time_to_route = null !== $bv['seconds_to_route'] ? round( $bv['seconds_to_route'] ) . 's' : '—';
					$attention = 'OK';
					$attention_class = 'ok';
				} elseif ( ! $fulfillment['expected'] ) {
					// Not currently expected to fulfill -- historical or
					// intentionally excluded, never an active alert. The
					// reason is shown so this reads as "explained", not
					// "silently ignored".
					switch ( $fulfillment['reason'] ) {
						case 'excluded_by_bookvault':
							$bv_status_label = 'Excluded — not selected for Bookvault';
							break;
						case 'refunded':
							$bv_status_label = 'Refunded — fulfillment not expected';
							break;
						case 'cancelled':
							$bv_status_label = 'Cancelled — fulfillment not expected';
							break;
						case 'legacy_pre_integration':
							$bv_status_label = 'Legacy / pre-integration — no attempt possible';
							break;
						default:
							$bv_status_label = 'Excluded';
					}
					$attention = 'Excluded';
					$attention_class = 'excluded';
				} elseif ( 'failed' === $bv['status'] ) {
					// Chronology matters here: a routing that succeeded once
					// and then failed again is a different problem (and a
					// different next step for Andrew) than one that never
					// routed at all -- see docs/kpi-definitions.md.
					$bv_status_label = $bv['had_prior_success']
						? 'Failed (previously routed — check for a duplicate or reversed order)'
						: 'Failed';
					$attention = 'Needs review';
					$attention_class = 'critical';
				} else {
					// Same overdue threshold the aggregate "Orders needing
					// attention" KPI/warnings use
					// (BHP_Order_Metrics::is_routing_overdue()) -- an eligible,
					// currently-expected order with no note yet is only
					// flagged once it's actually overdue, and this row must
					// never disagree with that aggregate count.
					if ( BHP_Order_Metrics::is_routing_overdue( $order, $bv ) ) {
						$bv_status_label = 'Pending (overdue)';
						$attention       = 'Needs review';
						$attention_class = 'critical';
					} else {
						$bv_status_label = 'Pending';
						$attention       = 'Monitoring';
						$attention_class = 'monitoring';
					}
				}
			}

			$order_edit_link = get_edit_post_link( $order->get_id() );
			$refund_state = BHP_Refund_Metrics::get_order_refund_state( $order );
			$refund_label = '—';
			if ( 'full' === $refund_state['state'] ) {
				$refund_label = 'Full (' . self::fmt_money( $refund_state['amount'] ) . ')';
			} elseif ( 'partial' === $refund_state['state'] ) {
				$refund_label = 'Partial (' . self::fmt_money( $refund_state['amount'] ) . ')';
			}

			echo '<tr>';
			echo '<td>' . ( $order_edit_link ? '<a href="' . esc_url( $order_edit_link ) . '">#' . esc_html( $order->get_order_number() ) . '</a>' : '#' . esc_html( $order->get_order_number() ) ) . '</td>';
			echo '<td>' . esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '—' ) . '</td>';
			echo '<td>' . esc_html( $labels[ $classification['offer_type'] ] ) . '</td>';
			echo '<td>' . esc_html( $units ) . '</td>';
			echo '<td>' . esc_html( self::fmt_money( $order->get_total() ) ) . '</td>';
			echo '<td>' . esc_html( $refund_label ) . '</td>';
			echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
			echo '<td>' . esc_html( $bv_status_label ) . '</td>';
			echo '<td>' . esc_html( $bv_ref ) . '</td>';
			echo '<td>' . esc_html( $time_to_route ) . '</td>';
			echo '<td><span class="bhp-dash-attention bhp-dash-attention--' . esc_attr( $attention_class ) . '">' . esc_html( $attention ) . '</span></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}
