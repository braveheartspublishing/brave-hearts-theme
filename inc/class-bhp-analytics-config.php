<?php
/**
 * Brave Hearts Publishing — Analytics Phase 1B: central configuration.
 *
 * Single source of truth for environment detection, the GTM container ID,
 * and consent defaults, so no other file ever branches on a hostname or
 * hardcodes a container ID directly. Every other analytics file in this
 * phase (GTM loader, consent, UTM attribution, ecommerce events) reads
 * through this class instead of re-deriving these facts.
 *
 * No ID is hardcoded here or anywhere else: `bhp_gtm_container_id` is a
 * WordPress option, empty by default, set once real credentials exist
 * (see docs/analytics-architecture.md). Until then every consumer of this
 * class degrades gracefully to "analytics not configured" rather than
 * printing a placeholder script.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Analytics_Config {

	const OPTION_GTM_CONTAINER_ID = 'bhp_gtm_container_id';
	const OPTION_GA4_MEASUREMENT_ID = 'bhp_ga4_measurement_id';
	const OPTION_STAGING_TRACKING_OVERRIDE = 'bhp_staging_analytics_override';

	/**
	 * Production consent-decision readiness gate (Phase 1B correction
	 * pass, 2026-07-06). Separate from, and stricter than, the per-request
	 * Consent Mode signals in BHP_Consent: this is a one-time BUSINESS
	 * decision gate, not a per-visitor consent state. Even once a real GTM
	 * container ID is configured, GTM must not go operational in
	 * production until Andrew has explicitly approved a consent-handling
	 * decision (which could be "we built/adopted a real consent banner"
	 * OR "we've reviewed this and a banner isn't required for our current
	 * posture" -- either way, a deliberate decision, never a default).
	 * Defaults to false/not-approved. Staging is unaffected by this gate
	 * (the existing OPTION_STAGING_TRACKING_OVERRIDE already governs
	 * staging's own bounded QA access) -- this only ever blocks production.
	 */
	const OPTION_CONSENT_DECISION_APPROVED = 'bhp_consent_decision_approved';

	public static function consent_decision_approved() {
		return (bool) get_option( self::OPTION_CONSENT_DECISION_APPROVED, false );
	}

	/**
	 * Staging is identified by hostname, not a constant that could be left
	 * stale after a migration -- matches how BHP_Order_Provenance already
	 * treats staging2.braveheartspublishing.com as the canonical staging
	 * origin. Anything not that exact host is treated as production for
	 * analytics-destination purposes, including local/dev environments an
	 * engineer might run this on -- see is_tracking_enabled() below, which
	 * additionally requires an explicit opt-in before staging ever sends a
	 * real event.
	 */
	const STAGING_HOST = 'staging2.braveheartspublishing.com';

	public static function is_staging() {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		return self::STAGING_HOST === $host;
	}

	public static function environment_label() {
		return self::is_staging() ? 'staging' : 'production';
	}

	/**
	 * GTM container ID, or empty string if not configured. Never a
	 * placeholder value -- an empty return means "print nothing" to every
	 * caller (BHP_GTM_Loader), not "use a default."
	 */
	public static function gtm_container_id() {
		$id = trim( (string) get_option( self::OPTION_GTM_CONTAINER_ID, '' ) );
		if ( '' === $id ) {
			return '';
		}
		// Defensive shape check only -- never invents an ID, only refuses
		// to print something that clearly isn't a GTM container ID (e.g. a
		// stray value pasted into the wrong option by mistake).
		if ( ! preg_match( '/^GTM-[A-Z0-9]+$/', $id ) ) {
			return '';
		}
		return $id;
	}

	public static function ga4_measurement_id() {
		$id = trim( (string) get_option( self::OPTION_GA4_MEASUREMENT_ID, '' ) );
		if ( '' === $id || ! preg_match( '/^G-[A-Z0-9]+$/', $id ) ) {
			return '';
		}
		return $id;
	}

	/**
	 * Staging must never silently contaminate production reporting
	 * (Phase 3 requirement). Default: tracking is DISABLED on staging
	 * unless an explicit override option is turned on -- e.g. for a single
	 * controlled validation session. Production is always eligible
	 * (subject to is_admin_traffic() and consent gating elsewhere).
	 */
	public static function is_tracking_enabled() {
		if ( ! self::is_staging() ) {
			return true;
		}
		return (bool) get_option( self::OPTION_STAGING_TRACKING_OVERRIDE, false );
	}

	/**
	 * Excludes WordPress admin, wp-login.php, and logged-in
	 * administrator/shop-manager traffic from storefront tracking -- Phase
	 * 3/4 requirement. Editors previewing a draft page while logged in are
	 * still real internal traffic, not a customer, so they are excluded
	 * too; this errs toward under-counting internal activity rather than
	 * ever inflating genuine customer numbers.
	 */
	public static function is_excluded_internal_request() {
		if ( is_admin() ) {
			return true;
		}
		global $pagenow;
		if ( 'wp-login.php' === $pagenow ) {
			return true;
		}
		if ( is_user_logged_in() && ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * The single gate every rendering call site (GTM loader, debug panel)
	 * must check before printing anything analytics-related to the page.
	 * On production specifically, the consent-decision gate above is an
	 * ADDITIONAL, non-negotiable requirement -- never bypassed just
	 * because a container ID exists (Phase 1B correction: "GTM/GA4 must
	 * not become operational in production until a consent decision is
	 * approved").
	 *
	 * ⛔ REMOVED 2026-08-05 (theme 1.19.178, `CYCLE143-GIM-51`): the
	 * per-visitor `BHP_Consent::analytics_allowed()` check that used to be
	 * the fourth gate here. Its intent was right and its implementation was
	 * correct per-request, but it made the RENDERED HTML vary by consent
	 * cookie -- and SiteGround's page cache varies only on Accept-Encoding.
	 * Observed live on production 2026-08-04: a cache entry primed by a
	 * consenting visitor was served, byte-identical, to a cookie-less
	 * visitor complete with `analytics_storage:"granted"`; and the
	 * canonical homepage's cookie-less entry was served to consenting
	 * visitors, so they were never measured. A per-visitor server-side gate
	 * in front of a full-page cache is defeated in both directions.
	 *
	 * Every remaining check below is either site-wide (options) or
	 * cache-exempt (logged-in/admin traffic, which SiteGround does not
	 * serve from the page cache), so what this function now returns is
	 * constant for all cacheable traffic on a given environment. Collection
	 * gating moved entirely into Consent Mode: the defaults are denied for
	 * everyone (BHP_Consent::default_signals()) and only the visitor's own
	 * acceptance raises them, client-side (BHP_WPConsent_Bridge).
	 *
	 * ⛔ Do not re-add a per-visitor condition to this function.
	 */
	public static function should_render_analytics() {
		if ( self::is_excluded_internal_request() ) {
			return false;
		}
		if ( ! self::is_tracking_enabled() ) {
			return false;
		}
		if ( ! self::is_staging() && ! self::consent_decision_approved() ) {
			return false;
		}
		return true;
	}

	/**
	 * Human-readable reason analytics is currently blocked, for an admin
	 * to see WITHOUT needing to read source code -- never silent. Returns
	 * null when analytics would actually render for a real visitor right
	 * now (i.e., nothing is blocking it). Order of checks matches
	 * should_render_analytics() above so the reported reason is always
	 * the actual first blocker, not a misleading secondary one.
	 */
	public static function consent_gate_reason() {
		if ( '' === self::gtm_container_id() ) {
			return 'No GTM container ID is configured yet (bhp_gtm_container_id is empty) -- nothing would fire regardless of consent.';
		}
		if ( ! self::is_staging() && ! self::consent_decision_approved() ) {
			return 'Production consent decision has not been approved yet. Set the bhp_consent_decision_approved option once Andrew has explicitly approved a consent-handling decision (a real consent banner, or a documented reason one is not required) -- see docs/analytics-architecture.md. GTM will not go operational in production until then, regardless of the container ID being configured.';
		}
		if ( self::is_staging() && ! self::is_tracking_enabled() ) {
			return 'Staging tracking is disabled by default. Turn on bhp_staging_analytics_override for a bounded validation session, then turn it off again afterward.';
		}
		if ( ! BHP_Consent::analytics_allowed() ) {
			// Informational only since 2026-08-05: this no longer blocks
			// rendering. GTM loads for everyone and Consent Mode decides
			// what may be collected -- so the honest report is "loaded,
			// currently denied", not "blocked".
			return 'GTM is loading, but analytics consent (analytics_storage) is currently DENIED for this visitor/session, so Consent Mode is suppressing analytics storage. This is the fail-closed default until the visitor accepts analytics cookies -- not an error, and not a block on the container itself.';
		}
		return null;
	}

	/**
	 * Debug/validation mode (Phase 14) is staging-only and admin-only --
	 * never shown to a real customer, and never on production regardless
	 * of login state, since it renders raw event payloads into the page.
	 */
	public static function debug_mode_available() {
		return self::is_staging() && is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/**
	 * Admin-visible notice (Phase 1B correction: "A WordPress
	 * administrator must be able to see why analytics is blocked") --
	 * only shown once a real GTM container ID exists, so it never nags
	 * before Andrew has even started configuring anything. Shown on every
	 * wp-admin screen an administrator visits, not hidden in a settings
	 * page nobody would think to check.
	 */
	public static function maybe_render_admin_notice() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( '' === self::gtm_container_id() ) {
			return; // nothing configured yet -- nothing to warn about
		}
		if ( self::is_staging() ) {
			return; // this notice is about the PRODUCTION consent gate specifically
		}
		if ( self::consent_decision_approved() ) {
			return; // gate is approved -- no notice needed
		}
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Brave Hearts Analytics:', 'bhp-bundle-pricing' ),
			esc_html( self::consent_gate_reason() )
		);
	}
}
add_action( 'admin_notices', array( 'BHP_Analytics_Config', 'maybe_render_admin_notice' ) );
