<?php
/**
 * Brave Hearts Publishing — Phase 1E: content production queue.
 *
 * A persistent, reviewable queue of content recommendations, following
 * the same private-CPT + postmeta pattern as BHP_Lead_Event_Log rather
 * than a new database table. Nothing in this class ever auto-approves
 * an item or auto-publishes content -- every status transition past
 * 'needs_review' requires an explicit call with an approving user.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Production_Queue {

	const POST_TYPE = 'bhp_content_queue';

	const META_RECOMMENDATION_TYPE = '_bhp_cq_recommendation_type';
	const META_TARGET_URL          = '_bhp_cq_target_url';
	const META_PROPOSED_SLUG       = '_bhp_cq_proposed_slug';
	const META_AUDIENCE            = '_bhp_cq_audience';
	const META_FUNNEL_STAGE        = '_bhp_cq_funnel_stage';
	const META_CONTENT_INTENT      = '_bhp_cq_content_intent';
	const META_PRIMARY_KEYWORD     = '_bhp_cq_primary_keyword';
	const META_SECONDARY_KEYWORDS  = '_bhp_cq_secondary_keywords'; // JSON array
	const META_SOURCE_EVIDENCE     = '_bhp_cq_source_evidence';   // JSON (opportunity score payload)
	const META_OPPORTUNITY_SCORE   = '_bhp_cq_opportunity_score';
	const META_CONFIDENCE          = '_bhp_cq_confidence';
	const META_FEATURED_BOOK       = '_bhp_cq_featured_book';
	const META_LEAD_OFFER          = '_bhp_cq_lead_offer';
	const META_CTA_GOAL            = '_bhp_cq_cta_goal';
	const META_PINTEREST_POTENTIAL = '_bhp_cq_pinterest_potential';
	const META_STATUS              = '_bhp_cq_status';
	const META_OWNER               = '_bhp_cq_owner';
	const META_APPROVAL_STATUS     = '_bhp_cq_approval_status'; // 'pending' | 'approved' | 'declined'
	const META_APPROVED_BY         = '_bhp_cq_approved_by';
	const META_APPROVED_AT         = '_bhp_cq_approved_at';

	const STATUSES = array(
		'discovered', 'needs_review', 'approved', 'brief_ready', 'drafting', 'qa',
		'revision_required', 'ready_for_wp_draft', 'wp_draft_created',
		'approved_for_publishing', 'published', 'monitoring',
		'refresh_recommended', 'declined',
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Content Production Queue', 'brave-hearts' ),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title' ),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Adds one item, always starting at 'discovered' regardless of what
	 * status the caller passes -- a fresh recommendation is never
	 * pre-approved. Returns the new queue post ID, or 0 on failure.
	 */
	public static function add_item( array $data ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '[%s] %s', $data['recommendation_type'] ?? 'unknown', $data['proposed_topic'] ?? ( $data['target_url'] ?? 'untitled' ) ),
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, self::META_RECOMMENDATION_TYPE, sanitize_key( $data['recommendation_type'] ?? '' ) );
		update_post_meta( $post_id, self::META_TARGET_URL, esc_url_raw( $data['target_url'] ?? '' ) );
		update_post_meta( $post_id, self::META_PROPOSED_SLUG, sanitize_title( $data['proposed_slug'] ?? '' ) );
		update_post_meta( $post_id, self::META_AUDIENCE, sanitize_key( $data['audience'] ?? '' ) );
		update_post_meta( $post_id, self::META_FUNNEL_STAGE, sanitize_key( $data['funnel_stage'] ?? '' ) );
		update_post_meta( $post_id, self::META_CONTENT_INTENT, sanitize_key( $data['content_intent'] ?? '' ) );
		update_post_meta( $post_id, self::META_PRIMARY_KEYWORD, sanitize_text_field( $data['primary_keyword'] ?? '' ) );
		update_post_meta( $post_id, self::META_SECONDARY_KEYWORDS, wp_json_encode( array_map( 'sanitize_text_field', (array) ( $data['secondary_keywords'] ?? array() ) ) ) );
		update_post_meta( $post_id, self::META_SOURCE_EVIDENCE, wp_json_encode( $data['source_evidence'] ?? array() ) );
		update_post_meta( $post_id, self::META_OPPORTUNITY_SCORE, isset( $data['opportunity_score'] ) ? (float) $data['opportunity_score'] : null );
		update_post_meta( $post_id, self::META_CONFIDENCE, sanitize_key( $data['confidence'] ?? 'low' ) );
		update_post_meta( $post_id, self::META_FEATURED_BOOK, sanitize_key( $data['featured_book'] ?? '' ) );
		update_post_meta( $post_id, self::META_LEAD_OFFER, sanitize_key( $data['lead_offer'] ?? '' ) );
		update_post_meta( $post_id, self::META_CTA_GOAL, sanitize_key( $data['cta_goal'] ?? '' ) );
		update_post_meta( $post_id, self::META_PINTEREST_POTENTIAL, sanitize_key( $data['pinterest_potential'] ?? 'unassessed' ) );
		update_post_meta( $post_id, self::META_STATUS, 'discovered' );
		update_post_meta( $post_id, self::META_OWNER, sanitize_text_field( $data['owner'] ?? 'system' ) );
		update_post_meta( $post_id, self::META_APPROVAL_STATUS, 'pending' );

		return $post_id;
	}

	/**
	 * Moves an item to a new status. Approval-gated transitions (anything
	 * from 'approved' onward) REQUIRE an explicit $approved_by user --
	 * this method refuses the transition otherwise rather than silently
	 * defaulting to some system actor.
	 */
	public static function transition( $queue_id, $new_status, $approved_by = null ) {
		if ( ! in_array( $new_status, self::STATUSES, true ) ) {
			return new WP_Error( 'bhp_cq_invalid_status', 'Unknown status: ' . $new_status );
		}
		$post = get_post( $queue_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'bhp_cq_not_found', 'Queue item not found.' );
		}

		$gated_statuses = array( 'approved', 'ready_for_wp_draft', 'wp_draft_created', 'approved_for_publishing', 'published' );
		if ( in_array( $new_status, $gated_statuses, true ) ) {
			if ( empty( $approved_by ) ) {
				return new WP_Error( 'bhp_cq_approval_required', "Status '{$new_status}' requires an explicit approving user; none was given." );
			}
			update_post_meta( $queue_id, self::META_APPROVAL_STATUS, 'approved' );
			update_post_meta( $queue_id, self::META_APPROVED_BY, sanitize_text_field( $approved_by ) );
			update_post_meta( $queue_id, self::META_APPROVED_AT, current_time( 'mysql', true ) );
		}
		if ( 'declined' === $new_status ) {
			update_post_meta( $queue_id, self::META_APPROVAL_STATUS, 'declined' );
		}

		update_post_meta( $queue_id, self::META_STATUS, $new_status );
		wp_update_post( array( 'ID' => $queue_id ) ); // bumps post_modified

		return true;
	}

	public static function get_item( $queue_id ) {
		$post = get_post( $queue_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}
		return self::describe( $post );
	}

	private static function describe( WP_Post $post ) {
		return array(
			'queue_id'            => $post->ID,
			'recommendation_type' => get_post_meta( $post->ID, self::META_RECOMMENDATION_TYPE, true ),
			'target_url'          => get_post_meta( $post->ID, self::META_TARGET_URL, true ),
			'proposed_slug'       => get_post_meta( $post->ID, self::META_PROPOSED_SLUG, true ),
			'audience'            => get_post_meta( $post->ID, self::META_AUDIENCE, true ),
			'funnel_stage'        => get_post_meta( $post->ID, self::META_FUNNEL_STAGE, true ),
			'content_intent'      => get_post_meta( $post->ID, self::META_CONTENT_INTENT, true ),
			'primary_keyword'     => get_post_meta( $post->ID, self::META_PRIMARY_KEYWORD, true ),
			'secondary_keywords'  => (array) json_decode( get_post_meta( $post->ID, self::META_SECONDARY_KEYWORDS, true ), true ),
			'source_evidence'     => json_decode( get_post_meta( $post->ID, self::META_SOURCE_EVIDENCE, true ), true ),
			'opportunity_score'   => get_post_meta( $post->ID, self::META_OPPORTUNITY_SCORE, true ),
			'confidence'          => get_post_meta( $post->ID, self::META_CONFIDENCE, true ),
			'featured_book'       => get_post_meta( $post->ID, self::META_FEATURED_BOOK, true ),
			'lead_offer'          => get_post_meta( $post->ID, self::META_LEAD_OFFER, true ),
			'cta_goal'            => get_post_meta( $post->ID, self::META_CTA_GOAL, true ),
			'pinterest_potential' => get_post_meta( $post->ID, self::META_PINTEREST_POTENTIAL, true ),
			'status'              => get_post_meta( $post->ID, self::META_STATUS, true ),
			'owner'               => get_post_meta( $post->ID, self::META_OWNER, true ),
			'approval_status'     => get_post_meta( $post->ID, self::META_APPROVAL_STATUS, true ),
			'approved_by'         => get_post_meta( $post->ID, self::META_APPROVED_BY, true ),
			'created_date'        => $post->post_date_gmt,
			'updated_date'        => $post->post_modified_gmt,
		);
	}

	/**
	 * Paginated, filtered listing -- never an unbounded query. $filters
	 * supports 'audience', 'funnel_stage', 'content_intent', 'status'.
	 */
	public static function list_items( array $filters = array(), $page = 1, $per_page = 20 ) {
		$meta_query = array( 'relation' => 'AND' );
		$map = array(
			'audience'       => self::META_AUDIENCE,
			'funnel_stage'   => self::META_FUNNEL_STAGE,
			'content_intent' => self::META_CONTENT_INTENT,
			'status'         => self::META_STATUS,
		);
		foreach ( $map as $filter_key => $meta_key ) {
			if ( ! empty( $filters[ $filter_key ] ) ) {
				$meta_query[] = array( 'key' => $meta_key, 'value' => sanitize_key( $filters[ $filter_key ] ) );
			}
		}

		$per_page = max( 1, min( 100, (int) $per_page ) ); // hard ceiling: never an unbounded admin query
		$query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => $per_page,
			'paged'          => max( 1, (int) $page ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => count( $meta_query ) > 1 ? $meta_query : array(),
		) );

		return array(
			'items'       => array_map( array( __CLASS__, 'describe' ), $query->posts ),
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => max( 1, (int) $page ),
		);
	}

	public static function export_json( array $filters = array() ) {
		$all = array();
		$page = 1;
		do {
			$result = self::list_items( $filters, $page, 100 );
			$all    = array_merge( $all, $result['items'] );
			++$page;
		} while ( $page <= $result['total_pages'] );
		return wp_json_encode( $all, JSON_PRETTY_PRINT );
	}

	public static function register_admin_page() {
		add_management_page(
			__( 'Content Production Queue', 'brave-hearts' ),
			__( 'Content Queue', 'brave-hearts' ),
			'edit_others_posts',
			'bhp-content-queue',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$page   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification -- read-only pagination
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$result = self::list_items( $status_filter ? array( 'status' => $status_filter ) : array(), $page, 20 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Content Production Queue', 'brave-hearts' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Every recommendation must be explicitly approved before any draft is created and explicitly approved again before publishing. Nothing here publishes automatically.', 'brave-hearts' ); ?></p>
			<form method="get">
				<input type="hidden" name="page" value="bhp-content-queue" />
				<select name="status">
					<option value=""><?php esc_html_e( 'All statuses', 'brave-hearts' ); ?></option>
					<?php foreach ( self::STATUSES as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status_filter, $status ); ?>><?php echo esc_html( $status ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button"><?php esc_html_e( 'Filter', 'brave-hearts' ); ?></button>
			</form>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Target/Topic', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Audience', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Score', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Confidence', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Status', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Approval', 'brave-hearts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['items'] as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['recommendation_type'] ); ?></td>
							<td><?php echo esc_html( $item['target_url'] ?: $item['proposed_slug'] ); ?></td>
							<td><?php echo esc_html( $item['audience'] ); ?></td>
							<td><?php echo esc_html( null === $item['opportunity_score'] || '' === $item['opportunity_score'] ? '—' : $item['opportunity_score'] ); ?></td>
							<td><?php echo esc_html( $item['confidence'] ); ?></td>
							<td><?php echo esc_html( $item['status'] ); ?></td>
							<td><?php echo esc_html( $item['approval_status'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $result['items'] ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No items in the queue yet.', 'brave-hearts' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			<p><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d — %3$d total item(s).', 'brave-hearts' ), $result['page'], max( 1, $result['total_pages'] ), $result['total'] ) ); ?></p>
		</div>
		<?php
	}
}

BHP_Content_Production_Queue::init();
