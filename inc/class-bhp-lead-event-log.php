<?php
/**
 * Brave Hearts Publishing — Phase 1C: local lead-event log.
 *
 * The existing Mailchimp signup handler (inc/mailchimp.php) already
 * validates, sends, tags, and redirects every acquisition form on this
 * site -- this class does not touch any of that. It only OBSERVES the
 * two extension points that handler already fires
 * (`bhp_mailchimp_signup_success` / `bhp_mailchimp_signup_failed`) and
 * writes a private, admin-only record of what happened, because today
 * nothing on this site persists a queryable local history of signups —
 * reporting is entirely delegated to Mailchimp's own UI (confirmed via
 * repository audit, 2026-07-06). This class exists to close that one
 * specific gap, not to become a second email system or a CRM.
 *
 * Storage: a private (non-public, no front-end templates, not shown in
 * search) custom post type, `bhp_lead_event`. A CPT was chosen over a
 * new database table because it needs zero migrations, gets WordPress's
 * own admin list-table tooling for free, and matches how this codebase
 * already prefers post/postmeta storage over bespoke tables (see
 * BHP_Order_Provenance, which annotates WC_Order posts rather than
 * maintaining a parallel ledger).
 *
 * PII posture: the email address IS stored (the success/failure hooks
 * already receive it), exactly as it already lives in Mailchimp and in
 * every WooCommerce order — visible only in wp-admin behind
 * `manage_options`, never echoed into any public page, never included
 * in any analytics/dataLayer payload, never logged to PHP error logs.
 * This is an internal operational record, not a second marketing list.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Lead_Event_Log {

	const POST_TYPE = 'bhp_lead_event';

	const META_EMAIL          = '_bhp_lead_email';
	const META_RESULT         = '_bhp_lead_result'; // 'success' | 'failed'
	const META_CONTEXT        = '_bhp_lead_context';
	const META_AUDIENCE       = '_bhp_lead_audience';
	const META_LEAD_MAGNET    = '_bhp_lead_magnet';
	const META_SOURCE_PAGE    = '_bhp_lead_source_page';
	const META_TAGS           = '_bhp_lead_tags';
	const META_FAILURE_REASON = '_bhp_lead_failure_reason';
	const META_FIRST_TOUCH    = '_bhp_lead_first_touch';
	const META_LAST_TOUCH     = '_bhp_lead_last_touch';
	const META_PROVENANCE     = '_bhp_lead_provenance'; // 'real' | 'test'
	const META_HOST           = '_bhp_lead_host';

	/**
	 * ⭐⭐ 1.19.342 (`CYCLE172-LD-FUNNEL-FIX`, audit gap G-E) — THE FORM-MOMENT
	 *    ATTRIBUTION THIS SIGNUP RESOLVED TO, STORED LOCALLY.
	 *
	 * ⛔ THE ASYMMETRY THIS CLOSES, OBSERVED ON PRODUCTION 2026-08-31. The
	 *    `TRAFFIC` merge field sent to Mailchimp resolves through
	 *    `bhp_get_signup_traffic_source()`, which has a form-moment fallback
	 *    that does NOT require consent. The two fields above
	 *    (`META_FIRST_TOUCH` / `META_LAST_TOUCH`) read the consent-gated
	 *    COOKIES and are therefore blank for any visitor who declined or
	 *    never answered the banner. Result: **all 12 of the most recent lead
	 *    events had both touch fields empty while Mailchimp held a source for
	 *    the same signups.** The third party knew more about our own funnel
	 *    than our own log did, and when the two disagreed the local log was
	 *    the one that was wrong.
	 *
	 * ⭐ SO THE LOCAL RECORD NOW STORES WHAT WAS ACTUALLY SENT. This is not a
	 *    third attribution instrument — it is the SAME value the Mailchimp
	 *    pipe resolved, written down on this side of the wire so the two can
	 *    be reconciled without a Mailchimp login.
	 *
	 * ⛔ IT IS NOT A CONSENT BYPASS AND CHANGES NO CONSENT POSTURE. It writes
	 *    no cookie and reads none. It records a value the visitor supplied in
	 *    their own request (their URL or their same-origin referer) and that
	 *    this site was already transmitting to a third party. Recording
	 *    locally what we already send outward is strictly less exposure, not
	 *    more. The consent-gated cookie capture is untouched.
	 *
	 * ⛔ NO PII. The value passes `bhp_extract_attribution_params()`, a fixed
	 *    campaign/click-ID whitelist. Same shape, same cap, same rules as the
	 *    two touch fields, so all three are directly comparable.
	 */
	const META_FORM_MOMENT    = '_bhp_lead_form_moment';

	const PROVENANCE_REAL = 'real';
	const PROVENANCE_TEST = 'test';

	/**
	 * Recognized synthetic-address conventions for QA, so a real signup
	 * is never miscounted as test data and vice versa. Both conventions
	 * work with real mail providers (Gmail-style plus-addressing, or a
	 * reserved invalid TLD that never resolves), so a tester can pick
	 * whichever fits the manual test at hand -- see docs/lead-capture-testing.md.
	 */
	const TEST_EMAIL_MARKER = '+bhptest';
	const TEST_EMAIL_DOMAIN = '@bhptest.invalid';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'bhp_mailchimp_signup_success', array( __CLASS__, 'record_success' ), 10, 7 );
		add_action( 'bhp_mailchimp_signup_failed', array( __CLASS__, 'record_failure' ), 10, 5 );
		// ⭐ 1.19.296 FIX-1 — rejections that never reached the provider at all.
		add_action( 'bhp_mailchimp_signup_rejected', array( __CLASS__, 'record_rejection' ), 10, 5 );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
	}

	/**
	 * Private CPT: no public archive/single templates, not shown in
	 * search, not exposed via REST -- an internal record only, reachable
	 * exclusively through the dedicated admin page below.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'           => __( 'Lead Signup Events', 'brave-hearts' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'capability_type' => 'post',
				'supports'        => array( 'title' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Classifies an email address as real or test/synthetic. Staging is
	 * always test provenance regardless of the address used, matching
	 * the fail-safe posture already established by
	 * BHP_Analytics_Config::is_staging() elsewhere in this codebase.
	 */
	public static function classify_provenance( $email ) {
		if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_staging() ) {
			return self::PROVENANCE_TEST;
		}
		$email = strtolower( (string) $email );
		if ( false !== strpos( $email, self::TEST_EMAIL_MARKER ) ) {
			return self::PROVENANCE_TEST;
		}
		if ( false !== strpos( $email, self::TEST_EMAIL_DOMAIN ) ) {
			return self::PROVENANCE_TEST;
		}
		return self::PROVENANCE_REAL;
	}

	/**
	 * Shared write path for both success and failure -- a single place
	 * that decides what gets stored, so the two hook callbacks below stay
	 * thin and can never drift out of sync with each other.
	 */
	private static function write_event( $result, $email, $context, $audience_type, $lead_magnet, $source_page, $tags = array(), $failure_reason = '' ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s lead event — %s', $result, current_time( 'mysql' ) ),
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0; // Never let logging failure break the real signup flow.
		}

		$attribution = class_exists( 'BHP_UTM_Attribution' ) ? BHP_UTM_Attribution::current_visitor_attribution() : array( 'first_touch' => null, 'last_touch' => null );

		/*
		 * 2026-07-30: quiz-sourced events are logged WITHOUT the email
		 * address, per the approved privacy decision for the inline quiz
		 * capture — those records keep only non-PII operational data
		 * (result, context, audience, lead magnet, tags, provenance,
		 * timestamp). Provenance is still classified from the address
		 * in-memory first, so test-vs-real reporting keeps working without
		 * the address ever being written to the database.
		 *
		 * Every other context (landing pages, popups, Adventure Club) is
		 * unchanged and still stores the address it always has.
		 */
		$is_quiz_event = ( 'audience_quiz' === sanitize_key( (string) $context ) );
		update_post_meta( $post_id, self::META_EMAIL, $is_quiz_event ? '' : sanitize_email( (string) $email ) );
		update_post_meta( $post_id, self::META_RESULT, $result );
		update_post_meta( $post_id, self::META_CONTEXT, sanitize_key( (string) $context ) );
		update_post_meta( $post_id, self::META_AUDIENCE, sanitize_key( (string) $audience_type ) );
		update_post_meta( $post_id, self::META_LEAD_MAGNET, sanitize_key( (string) $lead_magnet ) );
		update_post_meta( $post_id, self::META_SOURCE_PAGE, esc_url_raw( (string) $source_page ) );
		update_post_meta( $post_id, self::META_TAGS, wp_json_encode( array_map( 'sanitize_text_field', (array) $tags ) ) );
		update_post_meta( $post_id, self::META_FAILURE_REASON, sanitize_text_field( (string) $failure_reason ) );
		update_post_meta( $post_id, self::META_FIRST_TOUCH, $attribution['first_touch'] ? wp_json_encode( $attribution['first_touch'] ) : '' );
		update_post_meta( $post_id, self::META_LAST_TOUCH, $attribution['last_touch'] ? wp_json_encode( $attribution['last_touch'] ) : '' );

		/*
		 * G-E. Written on the SAME request the signup happened on, so it sees
		 * exactly what the Mailchimp pipe saw. `function_exists()` rather than
		 * an assumption: this log is loaded independently of `inc/mailchimp.php`
		 * and must degrade to an empty string, never fatal, if that file is not
		 * present. An empty string here means "the request carried no campaign
		 * signal", which is a real and common answer — it is NOT a failure and
		 * must not be read as one.
		 */
		$form_moment = function_exists( 'bhp_get_form_moment_attribution' )
			? (array) bhp_get_form_moment_attribution()
			: array();
		update_post_meta( $post_id, self::META_FORM_MOMENT, $form_moment ? wp_json_encode( $form_moment ) : '' );
		update_post_meta( $post_id, self::META_PROVENANCE, self::classify_provenance( $email ) );
		update_post_meta( $post_id, self::META_HOST, isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' );

		return $post_id;
	}

	/**
	 * @param mixed  $subscriber     MC4WP subscriber response (not stored --
	 *                               a provider-shaped object, not this
	 *                               log's concern).
	 * @param string $email
	 * @param string $context
	 * @param string $audience_type
	 * @param string $lead_magnet
	 * @param string $source_page
	 * @param array  $tags
	 */
	public static function record_success( $subscriber, $email, $context, $audience_type, $lead_magnet, $source_page, $tags ) {
		self::write_event( 'success', $email, $context, $audience_type, $lead_magnet, $source_page, $tags );
	}

	/**
	 * @param Throwable $exception     Never stored verbatim -- only a short,
	 *                                 generic class-name reason, since the
	 *                                 real exception message could contain
	 *                                 provider-side account/API details.
	 */
	public static function record_failure( $exception, $context, $audience_type, $lead_magnet, $source_page ) {
		/*
		 * ⚠ CORRECTED 2026-08-27 (theme 1.19.296, `CYCLE167-LD-CAPTURE-FIX-BUILD`).
		 *   The comment that stood here said the generic statuses
		 *   (invalid/missing_name/unavailable) "never reach here at all, so this
		 *   log only ever sees actual provider/API failures". That was TRUE when
		 *   it was written and it is the exact sentence that described the
		 *   blind spot: those rejections reached NOTHING, not merely "not here".
		 *   They are now recorded by record_rejection() below.
		 *   ⭐ THIS METHOD IS UNCHANGED IN BEHAVIOUR. It still handles only
		 *   provider/API exceptions, and `bhp_mailchimp_signup_failed` still
		 *   means only that. The correction is to the comment's implication,
		 *   not to the code.
		 */
		$reason = is_object( $exception ) && method_exists( $exception, 'getMessage' )
			? ( 'Provider error: ' . get_class( $exception ) )
			: 'Unknown provider error';
		self::write_event( 'failed', '', $context, $audience_type, $lead_magnet, $source_page, array(), $reason );
	}

	/**
	 * ⭐ 1.19.296 — FIX-1. Record a signup REJECTED before the provider was
	 *    ever called: a malformed address, a missing required name, or — the
	 *    one that matters — the Mailchimp integration not being ready at all.
	 *
	 * ⛔ NO EMAIL ADDRESS IS ACCEPTED BY THIS METHOD OR WRITTEN BY IT. The
	 *    caller does not pass one. Andrew's parked decision on failure-path
	 *    email storage is NOT pre-empted; the reason and the context are what
	 *    close the blind spot, and they are non-PII.
	 *
	 * ⛔ PROVENANCE IS 'real' ON THIS PATH BY CONSTRUCTION, because
	 *    classify_provenance() is given an empty string. That is honest rather
	 *    than convenient: without the address there is nothing to classify, and
	 *    guessing 'test' would hide real rejections from the summary.
	 *
	 * @param string $code          Generic classifier: invalid|missing_name|unavailable.
	 * @param string $context
	 * @param string $audience_type
	 * @param string $lead_magnet
	 * @param string $source_page
	 */
	public static function record_rejection( $code, $context, $audience_type, $lead_magnet, $source_page ) {
		$code = sanitize_key( (string) $code );
		$allowed = array( 'invalid', 'missing_name', 'unavailable' );
		if ( ! in_array( $code, $allowed, true ) ) {
			$code = 'unknown';
		}
		self::write_event( 'failed', '', $context, $audience_type, $lead_magnet, $source_page, array(), $code );
	}

	/**
	 * Returns recent lead events for the admin summary page, newest first.
	 *
	 * @return WP_Post[]
	 */
	public static function get_recent( $limit = 50 ) {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
	}

	/**
	 * Aggregate counts for the admin summary, split by real vs. test
	 * provenance so a QA session never inflates the figures Andrew reads
	 * as genuine signups (same principle as BHP_Order_Provenance keeping
	 * executive KPIs separate from internal test orders).
	 */
	public static function get_summary( $days = 30 ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => -1,
				'date_query'     => array( array( 'after' => $days . ' days ago' ) ),
			)
		);

		$summary = array(
			'real' => array( 'success' => 0, 'failed' => 0 ),
			'test'  => array( 'success' => 0, 'failed' => 0 ),
			'by_placement' => array(),
			'by_audience'  => array(),
			'by_lead_magnet' => array(),
		);

		foreach ( $posts as $post ) {
			$provenance = get_post_meta( $post->ID, self::META_PROVENANCE, true ) ?: self::PROVENANCE_REAL;
			$result     = get_post_meta( $post->ID, self::META_RESULT, true ) ?: 'failed';
			$context    = get_post_meta( $post->ID, self::META_CONTEXT, true ) ?: 'unknown';
			$audience   = get_post_meta( $post->ID, self::META_AUDIENCE, true ) ?: 'unknown';
			$magnet     = get_post_meta( $post->ID, self::META_LEAD_MAGNET, true ) ?: 'unknown';

			if ( isset( $summary[ $provenance ][ $result ] ) ) {
				++$summary[ $provenance ][ $result ];
			}
			if ( self::PROVENANCE_REAL === $provenance && 'success' === $result ) {
				$summary['by_placement'][ $context ]    = ( $summary['by_placement'][ $context ] ?? 0 ) + 1;
				$summary['by_audience'][ $audience ]     = ( $summary['by_audience'][ $audience ] ?? 0 ) + 1;
				$summary['by_lead_magnet'][ $magnet ]    = ( $summary['by_lead_magnet'][ $magnet ] ?? 0 ) + 1;
			}
		}

		return $summary;
	}

	public static function register_admin_page() {
		add_management_page(
			__( 'Lead Signups', 'brave-hearts' ),
			__( 'Lead Signups', 'brave-hearts' ),
			'manage_options',
			'bhp-lead-signups',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$summary = self::get_summary( 30 );
		$recent  = self::get_recent( 50 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Lead Signups (last 30 days)', 'brave-hearts' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'This is an internal operational log, not the marketing list itself -- the real subscriber record lives in Mailchimp. Test/synthetic signups (staging, or addresses using the +bhptest / @bhptest.invalid convention) are counted separately and never included in the real totals below.', 'brave-hearts' ); ?>
			</p>

			<h2><?php esc_html_e( 'Real signups', 'brave-hearts' ); ?></h2>
			<table class="widefat striped" style="max-width:500px">
				<tbody>
					<tr><th><?php esc_html_e( 'Successful', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['real']['success'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Failed', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['real']['failed'] ); ?></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Test / synthetic signups', 'brave-hearts' ); ?></h2>
			<table class="widefat striped" style="max-width:500px">
				<tbody>
					<tr><th><?php esc_html_e( 'Successful', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['test']['success'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Failed', 'brave-hearts' ); ?></th><td><?php echo esc_html( $summary['test']['failed'] ); ?></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Real successful signups by placement / audience / lead magnet', 'brave-hearts' ); ?></h2>
			<table class="widefat striped" style="max-width:700px">
				<thead><tr><th><?php esc_html_e( 'Placement (context)', 'brave-hearts' ); ?></th><th><?php esc_html_e( 'Count', 'brave-hearts' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $summary['by_placement'] as $key => $count ) : ?>
						<tr><td><?php echo esc_html( $key ); ?></td><td><?php echo esc_html( $count ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Recent events (last 50)', 'brave-hearts' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Result', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Provenance', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Email', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Context', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Audience', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Lead magnet', 'brave-hearts' ); ?></th>
						<th><?php esc_html_e( 'Failure reason', 'brave-hearts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent as $post ) : ?>
						<tr>
							<td><?php echo esc_html( get_the_date( 'Y-m-d H:i', $post ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_RESULT, true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_PROVENANCE, true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_EMAIL, true ) ?: '—' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_CONTEXT, true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_AUDIENCE, true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_LEAD_MAGNET, true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, self::META_FAILURE_REASON, true ) ?: '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

BHP_Lead_Event_Log::init();
