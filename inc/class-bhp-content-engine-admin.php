<?php
/**
 * Brave Hearts Publishing — Phase 1E: Content Intelligence admin view.
 *
 * A new Tools submenu page, following the exact same pattern as the
 * existing "Lead Signups" page (BHP_Lead_Event_Log) rather than adding
 * a second executive/KPI dashboard -- the commerce KPI dashboard in
 * plugins/brave-hearts-bundle-pricing/includes/dashboard/ is untouched
 * and remains the one place for revenue/order metrics. This page is
 * read-only except one nonce-protected "refresh summary" action, and
 * every summary is cached for a short period to avoid recomputing the
 * inventory/opportunity scan on every page load.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Engine_Admin {

	const CACHE_KEY = 'bhp_content_engine_admin_summary';
	const CACHE_TTL = 900; // 15 minutes -- summary, not real-time.

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
	}

	public static function register_admin_page() {
		add_management_page(
			__( 'Content Intelligence', 'brave-hearts' ),
			__( 'Content Intelligence', 'brave-hearts' ),
			'edit_others_posts',
			'bhp-content-intelligence',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	private static function get_summary( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$inventory = class_exists( 'BHP_Content_Inventory' ) ? BHP_Content_Inventory::build( 150 ) : array( 'content_count' => 0, 'gaps' => array() );
		$scored    = class_exists( 'BHP_Content_Opportunity_Engine' ) ? array_slice( BHP_Content_Opportunity_Engine::score_inventory( $inventory ), 0, 10 ) : array();
		$queue     = class_exists( 'BHP_Content_Production_Queue' ) ? BHP_Content_Production_Queue::list_items( array(), 1, 10 ) : array( 'items' => array(), 'total' => 0 );
		$imports   = class_exists( 'BHP_Analytics_Adapter' ) ? BHP_Analytics_Adapter::list_imports( 10 ) : array();
		$link_report = class_exists( 'BHP_Internal_Link_Engine' ) ? BHP_Internal_Link_Engine::build_report( $inventory['items'] ?? array() ) : array( 'orphan_pages' => array(), 'overlinked_targets' => array() );

		$summary = array(
			'generated_at'   => current_time( 'mysql' ),
			'content_count'  => $inventory['content_count'] ?? 0,
			'gaps'           => $inventory['gaps'] ?? array(),
			'top_opportunities' => $scored,
			'queue_recent'   => $queue,
			'recent_imports' => $imports,
			'orphan_count'   => count( $link_report['orphan_pages'] ?? array() ),
			'overlinked_count' => count( $link_report['overlinked_targets'] ?? array() ),
		);

		set_transient( self::CACHE_KEY, $summary, self::CACHE_TTL );
		return $summary;
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$force_refresh = false;
		if ( isset( $_POST['bhp_ci_refresh'] ) && check_admin_referer( 'bhp_ci_refresh_action', 'bhp_ci_refresh_nonce' ) ) {
			$force_refresh = true;
		}
		$summary = self::get_summary( $force_refresh );
		$is_staging = class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_staging();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Content Intelligence (Phase 1E)', 'brave-hearts' ); ?></h1>

			<?php if ( $is_staging ) : ?>
				<div class="notice notice-warning"><p><strong><?php esc_html_e( 'Staging environment.', 'brave-hearts' ); ?></strong> <?php esc_html_e( 'Any analytics imports shown here may include fixture/test data. Fixture rows are always labeled explicitly below.', 'brave-hearts' ); ?></p></div>
			<?php endif; ?>

			<p class="description"><?php esc_html_e( 'Read-only summary. Nothing on this page publishes content, changes existing metadata, or sends anything externally. Use WP-CLI (wp bhp-content ...) to run the full pipeline.', 'brave-hearts' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'bhp_ci_refresh_action', 'bhp_ci_refresh_nonce' ); ?>
				<button type="submit" name="bhp_ci_refresh" value="1" class="button"><?php esc_html_e( 'Refresh summary', 'brave-hearts' ); ?></button>
				<span class="description"> <?php echo esc_html( sprintf( __( 'Last generated: %s', 'brave-hearts' ), $summary['generated_at'] ) ); ?></span>
			</form>

			<h2><?php esc_html_e( 'Content inventory', 'brave-hearts' ); ?></h2>
			<table class="widefat striped" style="max-width:600px">
				<tbody>
					<tr><th><?php esc_html_e( 'Content items scanned', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['content_count'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Orphan pages', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['orphan_count'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Overlinked targets', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['overlinked_count'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Missing metadata', 'brave-hearts' ); ?></th><td><?php echo esc_html( count( $summary['gaps']['missing_metadata'] ?? array() ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Missing classification', 'brave-hearts' ); ?></th><td><?php echo esc_html( count( $summary['gaps']['missing_classification'] ?? array() ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Possible cannibalization groups', 'brave-hearts' ); ?></th><td><?php echo esc_html( count( $summary['gaps']['possible_keyword_cannibalization'] ?? array() ) ); ?></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Top content opportunities', 'brave-hearts' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'URL', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Score', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Confidence', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Recommendation', 'brave-hearts' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $summary['top_opportunities'] ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No opportunities scored yet -- import analytics data first (wp bhp-content import-fixtures gsc, or a real export).', 'brave-hearts' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $summary['top_opportunities'] as $opp ) : ?>
						<tr>
							<td><?php echo esc_html( $opp['url'] ); ?></td>
							<td><?php echo esc_html( null === $opp['score'] ? '—' : $opp['score'] ); ?></td>
							<td><?php echo esc_html( $opp['confidence'] ); ?></td>
							<td><?php echo esc_html( $opp['recommendation']['type'] . ': ' . $opp['recommendation']['reason'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Production queue (most recent 10)', 'brave-hearts' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Type', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Target/topic', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Status', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Approval', 'brave-hearts' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $summary['queue_recent']['items'] ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'Queue is empty.', 'brave-hearts' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $summary['queue_recent']['items'] as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['recommendation_type'] ); ?></td>
							<td><?php echo esc_html( $item['target_url'] ?: $item['proposed_slug'] ); ?></td>
							<td><?php echo esc_html( $item['status'] ); ?></td>
							<td><?php echo esc_html( $item['approval_status'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'tools.php?page=bhp-content-queue' ) ); ?>"><?php esc_html_e( 'View full production queue', 'brave-hearts' ); ?></a></p>

			<h2><?php esc_html_e( 'Recent analytics imports', 'brave-hearts' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Label', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Source', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Rows', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Date range', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Fixture?', 'brave-hearts' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $summary['recent_imports'] ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No analytics data imported yet.', 'brave-hearts' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $summary['recent_imports'] as $import ) : ?>
						<tr>
							<td><?php echo esc_html( $import['label'] ); ?></td>
							<td><?php echo esc_html( $import['source'] ); ?></td>
							<td><?php echo esc_html( $import['row_count'] ); ?></td>
							<td><?php echo esc_html( $import['date_start'] . ' – ' . $import['date_end'] ); ?></td>
							<td><?php echo $import['is_fixture'] ? '<strong>' . esc_html__( 'FIXTURE', 'brave-hearts' ) . '</strong>' : esc_html__( 'Live import', 'brave-hearts' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

BHP_Content_Engine_Admin::init();
