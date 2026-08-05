<?php
/**
 * Brave Hearts Publishing — Analytics Phase 1B: Google Consent Mode scaffold.
 *
 * COMPLIANCE ASSUMPTION FLAGGED FOR ANDREW'S REVIEW (see
 * docs/analytics-architecture.md, "Consent" section): this site currently
 * has no cookie/consent banner and no consent-management platform
 * (confirmed by direct audit, 2026-07-06 and again in this phase). Absent
 * a real consent UI, this class defaults every Consent Mode signal to
 * 'denied' -- the conservative posture -- and only reads an 'granted'
 * state if a first-party cookie (`bhp_consent_state`) is later set by an
 * actual consent banner Andrew approves. No consent banner is built in
 * this phase; that is explicitly out of scope. This file only makes sure
 * that WHEN a consent banner exists, GTM/GA4 already knows how to respect
 * it, and that no analytics fires as "granted" before one exists.
 *
 * ⚠ 2026-08-05 UPDATE (theme 1.19.178) -- the paragraph above describes
 * the state on 2026-07-06 and is kept for history. WPConsent Free is now
 * live and IS the consent banner. More importantly, this class no longer
 * varies its rendered output per visitor: render_default_snippet() prints
 * a CONSTANT all-denied payload, and the visitor's real choice is applied
 * client-side by BHP_WPConsent_Bridge via gtag('consent','update',...).
 * That is the standard Google Consent Mode pattern, and it is what makes
 * the page cacheable -- see default_signals() for the full reasoning and
 * the live evidence behind it (`CYCLE143-GIM-51`).
 *
 * This is not a legal opinion. Whether Consent Mode / a cookie banner is
 * legally required for this site's traffic mix is Andrew's decision to
 * make with appropriate counsel, not something inferred here.
 *
 * FUTURE CMP INTEGRATION POINT (not selected or purchased in this phase --
 * see docs/analytics-architecture.md for the full list of considerations):
 * whatever consent-management platform Andrew eventually chooses (e.g. a
 * WordPress plugin like Complianz/CookieYes, or a standalone CMP), the
 * ONLY integration work needed here is for that CMP to set the
 * `bhp_consent_state` first-party cookie (a small JSON object of the four
 * signal keys in SIGNALS below, each 'granted'|'denied') when the visitor
 * makes a choice. This class already reads that cookie correctly and
 * already renders the gtag('consent', 'update', ...) call GTM/GA4 expect
 * on a change (see render_default_snippet() -- a real CMP would also call
 * gtag('consent','update',...) directly itself per Google's own
 * documented pattern, in addition to or instead of relying on this
 * cookie). No changes to BHP_GTM_Loader, BHP_Analytics_Config, or any
 * ecommerce event code would be required to adopt a CMP later.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Consent {

	const COOKIE_NAME = 'bhp_consent_state';

	/**
	 * The four Consent Mode v2 signals this phase supports, each
	 * independently 'granted' or 'denied'. Defaults are all 'denied' --
	 * the safe posture when no consent banner exists yet. Advertising
	 * consent is never inferred from analytics consent (Phase 12
	 * requirement) -- they are stored and read as fully separate keys.
	 */
	const SIGNALS = array( 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization' );

	/**
	 * Reads the current consent state from the first-party cookie a future
	 * consent banner would set. Cookie value is a small JSON object of the
	 * four signals above, e.g. {"analytics_storage":"granted",...}. Any
	 * missing/malformed/absent cookie resolves every signal to 'denied' --
	 * fail-safe, never fail-open.
	 *
	 * @return array<string,string> one of 'granted'|'denied' per signal
	 */
	public static function current_state() {
		$state = array();
		foreach ( self::SIGNALS as $signal ) {
			$state[ $signal ] = 'denied';
		}

		$has_real_choice = ! empty( $_COOKIE[ self::COOKIE_NAME ] );

		/**
		 * Staging-only validation exception: when Andrew has explicitly
		 * turned on the staging tracking override (the same option that
		 * gates BHP_Analytics_Config::is_tracking_enabled()), analytics
		 * consent defaults to 'granted' so a staging QA session can
		 * observe events reach GA4 before any real consent banner exists.
		 * This only ever fills in the DEFAULT prior to a visitor making a
		 * real choice -- see below, an actual recorded choice (e.g. a
		 * visitor clicking "Reject" in a real consent banner) always wins,
		 * since the point of this QA flag is to unblock validation before
		 * a choice exists, not to override one that already does (fixed
		 * 2026-07-13 after WPConsent integration QA caught GTM loading
		 * despite an explicit Reject -- see DECISIONS.md).
		 */
		if ( ! $has_real_choice && BHP_Analytics_Config::is_staging() && get_option( BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, false ) ) {
			$state['analytics_storage'] = 'granted';
		}

		if ( ! $has_real_choice ) {
			return $state;
		}

		$decoded = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );
		if ( ! is_array( $decoded ) ) {
			return $state;
		}

		foreach ( self::SIGNALS as $signal ) {
			$state[ $signal ] = ( isset( $decoded[ $signal ] ) && 'granted' === $decoded[ $signal ] ) ? 'granted' : 'denied';
		}
		return $state;
	}

	/**
	 * The Consent Mode default payload. CONSTANT BY DESIGN, 2026-08-05
	 * (`CYCLE143-GIM-51`): every signal is 'denied' for every visitor, on
	 * every request, in every environment. It does NOT read the consent
	 * cookie and must never be made to.
	 *
	 * Why constant: SiteGround's page cache stores rendered HTML and varies
	 * only on Accept-Encoding. Any per-visitor variation in this snippet is
	 * therefore served to the wrong visitors from cache -- observed live on
	 * production 2026-08-04, in both directions (a cache entry primed by a
	 * consenting visitor served `analytics_storage:"granted"` to a
	 * cookie-less visitor; the canonical homepage's no-GTM entry starved
	 * consenting visitors of measurement entirely). A constant
	 * defaults-denied payload is byte-identical for everyone, so the cache
	 * cannot mis-serve it, and the real per-visitor state is applied
	 * client-side by BHP_WPConsent_Bridge's `consent`/`update` call.
	 *
	 * `wait_for_update: 500` gives that client-side update 500ms to arrive
	 * before GTM proceeds under the denied defaults.
	 *
	 * @return array<string,int|string>
	 */
	public static function default_signals() {
		$defaults = array();
		foreach ( self::SIGNALS as $signal ) {
			$defaults[ $signal ] = 'denied';
		}
		$defaults['wait_for_update'] = 500;
		return $defaults;
	}

	/**
	 * Renders the gtag() Consent Mode default call. Must print BEFORE the
	 * GTM/gtag loader script (Phase 4/12 ordering requirement) so GTM
	 * initializes already aware of the current consent state rather than
	 * assuming default "granted" behavior for a brief window.
	 *
	 * Output is identical for every visitor -- see default_signals().
	 */
	public static function render_default_snippet() {
		printf(
			"<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',%s);</script>\n",
			wp_json_encode( self::default_signals() )
		);
	}

	/**
	 * True only when analytics_storage is granted for THIS request's
	 * cookie state.
	 *
	 * ⚠ 2026-08-05: this is no longer a rendering gate. It is diagnostic
	 * only (the staging debug panel, consent_gate_reason()). It must NOT be
	 * reintroduced into any code path that decides what HTML to print --
	 * doing so re-creates `CYCLE143-GIM-51`, because the page cache does
	 * not vary on the consent cookie. Collection gating lives entirely in
	 * Consent Mode now.
	 */
	public static function analytics_allowed() {
		$state = self::current_state();
		return 'granted' === $state['analytics_storage'];
	}
}
