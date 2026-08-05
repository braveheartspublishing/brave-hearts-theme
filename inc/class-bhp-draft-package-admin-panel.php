<?php
/**
 * Brave Hearts Publishing — Phase 1E: read-only draft package meta box.
 *
 * Shows the full Phase 1E metadata package (taxonomy/SEO/classification/
 * CTA/lead offer/Pinterest/analytics/images/internal links/editorial
 * review/QA/provenance) on the post edit screen for any post carrying
 * the Phase 1E provenance marker. Entirely read-only -- no form, no
 * save handler, nothing here can change a post's data. Never renders on
 * the public front end (add_meta_box only ever runs in wp-admin).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Draft_Package_Admin_Panel {

	const CAPABILITY = 'edit_others_posts'; // same capability gate as the rest of the Phase 1E admin surface.

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		add_meta_box(
			'bhp_draft_package_panel',
			__( 'Phase 1E Content Package (read-only)', 'brave-hearts' ),
			array( __CLASS__, 'render' ),
			'post',
			'normal',
			'high'
		);
	}

	public static function render( WP_Post $post ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		if ( 'phase1e_generated' !== get_post_meta( $post->ID, '_bhp_draft_provenance', true ) ) {
			echo '<p class="description">' . esc_html__( 'Not a Phase 1E-generated post -- no package to display.', 'brave-hearts' ) . '</p>';
			return;
		}
		if ( ! class_exists( 'BHP_WP_Draft_Workflow' ) ) {
			return;
		}

		$pkg = BHP_WP_Draft_Workflow::get_full_package( $post->ID );
		?>
		<div class="bhp-draft-package-panel">
			<p class="description"><?php esc_html_e( 'Read-only. This panel never publishes, changes taxonomy, or edits metadata -- it only displays what the Phase 1E content engine recorded when this draft was created.', 'brave-hearts' ); ?></p>

			<h3><?php esc_html_e( 'Provenance', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Queue item', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['provenance']['queue_id'] ); ?> <a href="<?php echo esc_url( admin_url( 'tools.php?page=bhp-content-queue' ) ); ?>"><?php esc_html_e( '(view queue)', 'brave-hearts' ); ?></a></td></tr>
					<tr><th><?php esc_html_e( 'Approved by', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['provenance']['approved_by'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Created at', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['provenance']['created_at'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Brief ID', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['brief_id'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Content version', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['content_version'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'QA status', 'brave-hearts' ); ?></th><td><?php echo esc_html( self::state_badge( $pkg['qa_status'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Originality status', 'brave-hearts' ); ?></th><td><?php echo esc_html( self::state_badge( $pkg['originality_status'] ) ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Taxonomy', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Categories', 'brave-hearts' ); ?></th><td><?php echo esc_html( implode( ', ', wp_list_pluck( $pkg['categories'], 'name' ) ) ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Primary category', 'brave-hearts' ); ?></th><td><?php echo esc_html( self::term_name( $pkg['primary_category_id'], 'category' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Tags', 'brave-hearts' ); ?></th><td><?php echo esc_html( implode( ', ', wp_list_pluck( $pkg['tags'], 'name' ) ) ?: '—' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'SEO', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'SEO title', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['seo']['title'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Meta description', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['seo']['description'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Focus keyword', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['seo']['focus_keyword'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Canonical URL', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['seo']['canonical_url'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Breadcrumb title', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['seo']['breadcrumb_title'] ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Classification & CTA', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Audience', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['audience'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Funnel stage', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['funnel_stage'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Content intent', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['content_intent'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Featured book', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['featured_book'] ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Lead offer', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['lead_offer'] ?: 'none' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Primary CTA', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['primary_cta'] ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Campaign ID', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['classification']['campaign_id'] ?: '—' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Images', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Featured image status', 'brave-hearts' ); ?></th><td><?php echo esc_html( self::state_badge( $pkg['images']['featured_image']['status'] ?? '' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Inline images required', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['images']['inline_image_requirement'] ?? '—' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Internal links', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Body link validation', 'brave-hearts' ); ?></th><td><?php echo esc_html( self::state_badge( $pkg['internal_links']['inserted_link_validation']['state'] ?? '' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'CTA destination', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['internal_links']['cta_destination'] ?? '—' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Pinterest', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Linked variants', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['pinterest']['variant_count'] ?? 0 ); ?> / 4</td></tr>
					<tr><th><?php esc_html_e( 'Publishing status', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['pinterest']['publishing_status'] ?? '—' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Analytics', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Analytics content ID', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['analytics']['analytics_content_id'] ?? '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Data source', 'brave-hearts' ); ?></th><td><?php echo esc_html( ( $pkg['analytics']['is_fixture_derived'] ?? false ) ? 'FIXTURE' : 'live' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Editorial review', 'brave-hearts' ); ?></h3>
			<table class="widefat striped">
				<tbody>
					<tr><th><?php esc_html_e( 'Factual review', 'brave-hearts' ); ?></th><td><?php echo esc_html( ( $pkg['editorial']['factual_review_complete'] ?? false ) ? 'complete (' . $pkg['editorial']['factual_reviewer'] . ')' : 'pending' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Audience-fit review', 'brave-hearts' ); ?></th><td><?php echo esc_html( ( $pkg['editorial']['audience_fit_review_complete'] ?? false ) ? 'complete (' . $pkg['editorial']['audience_fit_reviewer'] . ')' : 'pending' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Author Fingerprint (brand/founder grounding)', 'brave-hearts' ); ?></h3>
			<?php $ap = $pkg['author_package'] ?? array(); ?>
			<?php if ( empty( $ap ) ) : ?>
				<p class="description"><?php esc_html_e( 'No Author Fingerprint package linked to this draft.', 'brave-hearts' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<tbody>
						<tr><th><?php esc_html_e( 'Package UUID', 'brave-hearts' ); ?></th><td><?php echo esc_html( $pkg['author_package_uuid'] ?? '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Brand corpus status', 'brave-hearts' ); ?></th>
							<td><?php
							$corpus_lines = array();
							foreach ( (array) ( $ap['corpus_manifest'] ?? array() ) as $entry ) {
								$corpus_lines[] = sprintf( '%s: %s (%s)', esc_html( $entry['mandatory_key'] ), esc_html( $entry['source_id'] ), esc_html( $entry['canonical_status'] ) );
							}
							echo wp_kses_post( implode( '<br>', $corpus_lines ) ?: '—' );
							?></td>
						</tr>
						<tr><th><?php esc_html_e( 'Founder/brand voice source', 'brave-hearts' ); ?></th><td><?php echo esc_html( ( $ap['brand_voice_profile']['source_id'] ?? '—' ) . ' -- ' . ( $ap['brand_voice_profile']['source_title'] ?? '' ) ); ?></td></tr>
						<?php if ( ! empty( $ap['author_connection'] ) ) : $ac = $ap['author_connection']; ?>
							<tr><th><?php esc_html_e( 'Anecdote used', 'brave-hearts' ); ?></th><td><?php echo esc_html( $ac['anecdote_key'] ); ?></td></tr>
							<tr><th><?php esc_html_e( 'Relevance / topic tags', 'brave-hearts' ); ?></th><td><?php echo esc_html( implode( ', ', (array) ( $ac['topic_categories'] ?? array() ) ) ); ?></td></tr>
							<tr><th><?php esc_html_e( 'Prior-use count', 'brave-hearts' ); ?></th><td><?php echo esc_html( $ac['reuse_count'] ?? 0 ); ?></td></tr>
							<tr><th><?php esc_html_e( 'Verification state', 'brave-hearts' ); ?></th><td><?php echo esc_html( self::state_badge( $ac['verification_state'] ?? '' ) ); ?></td></tr>
						<?php else : ?>
							<tr><th><?php esc_html_e( 'Anecdote used', 'brave-hearts' ); ?></th><td><?php esc_html_e( 'none selected', 'brave-hearts' ); ?></td></tr>
						<?php endif; ?>
						<tr><th><?php esc_html_e( 'Author Fingerprint Check', 'brave-hearts' ); ?></th><td><?php echo esc_html( ( $ap['author_fingerprint_check']['passed'] ?? false ) ? 'PASS' : 'FAIL' ); ?></td></tr>
						<?php if ( ! empty( $ap['author_fingerprint_check']['prohibited_matches'] ) ) : ?>
							<tr><th><?php esc_html_e( 'Unsupported-claim warnings', 'brave-hearts' ); ?></th><td><?php echo esc_html( implode( '; ', $ap['author_fingerprint_check']['prohibited_matches'] ) ); ?></td></tr>
						<?php endif; ?>
						<?php if ( ! empty( $ap['author_fingerprint_check']['overused_anecdotes'] ) ) : ?>
							<tr><th><?php esc_html_e( 'Overused anecdotes', 'brave-hearts' ); ?></th><td><?php echo esc_html( implode( ', ', $ap['author_fingerprint_check']['overused_anecdotes'] ) ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( 'No private manuscript text is ever stored or displayed here -- only source IDs, canonical status, and the approved, reusable Author Connection anecdote.', 'brave-hearts' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function term_name( $term_id, $taxonomy ) {
		if ( ! $term_id ) {
			return '—';
		}
		$term = get_term( $term_id, $taxonomy );
		return $term instanceof WP_Term ? $term->name : '—';
	}

	private static function state_badge( $state ) {
		$labels = array(
			'pass' => 'PASS', 'complete' => 'COMPLETE', 'fail' => 'FAIL',
			'revise' => 'REVISE', 'requires_human_review' => 'PENDING REVIEW',
			'pending_generation' => 'PENDING', 'pending_upload' => 'PENDING UPLOAD',
			'not_required' => 'N/A',
		);
		return $labels[ $state ] ?? ( $state ?: '—' );
	}
}

BHP_Draft_Package_Admin_Panel::init();
