<?php
/**
 * Brave Hearts Publishing — Analytics Phase 1B: GTM installation.
 *
 * The ONE place the GTM container script is ever printed. No other file
 * in this theme or the bundle-pricing plugin should print a
 * googletagmanager.com script tag -- if a second GTM/GA4 install is ever
 * proposed, it belongs here as a config change, not a second copy of this
 * class elsewhere (Phase 4 "no duplicate GTM or GA4 loading" requirement).
 *
 * Prints nothing at all when BHP_Analytics_Config::gtm_container_id() is
 * empty -- there is no placeholder ID and never will be one committed to
 * this repo (see docs/analytics-architecture.md).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_GTM_Loader {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render_head_snippet' ), 2 ); // after bhp_init_datalayer() at priority 1
		add_action( 'wp_body_open', array( __CLASS__, 'render_noscript_snippet' ), 1 );
	}

	/**
	 * Whether the container prints for this request.
	 *
	 * ⚠ 2026-08-05 (`CYCLE143-GIM-51`): deliberately does NOT depend on the
	 * visitor's consent cookie. Identical HTML for every cacheable visitor
	 * is the whole point -- see BHP_Analytics_Config::should_render_analytics()
	 * for the live evidence. Public so other emitters can ask the same
	 * question without duplicating the rule.
	 */
	public static function should_print() {
		return '' !== BHP_Analytics_Config::gtm_container_id() && BHP_Analytics_Config::should_render_analytics();
	}

	/**
	 * Prints, in this exact order:
	 *
	 *   1. the Consent Mode DEFAULTS -- constant, all four signals denied,
	 *      wait_for_update 500 (BHP_Consent::render_default_snippet());
	 *   2. the client-side consent SYNC -- reads the visitor's own stored
	 *      choice from their browser and, only if they actually accepted,
	 *      sends gtag('consent','update',...) before the container loads
	 *      (BHP_WPConsent_Bridge::render_consent_sync_snippet());
	 *   3. the GTM container loader.
	 *
	 * Steps 1 and 2 MUST both precede step 3 (Phase 12 ordering
	 * requirement) so the container initializes already knowing the consent
	 * state instead of assuming "granted" for a brief window.
	 *
	 * The order is produced by direct calls rather than by three separate
	 * wp_head hooks precisely so it cannot be re-ordered by a priority
	 * change somewhere else.
	 *
	 * ⛔ Every byte printed here is identical for every visitor. The only
	 * per-visitor behaviour is executed by the browser in step 2, against
	 * a cookie the server never reads.
	 */
	public static function render_head_snippet() {
		if ( ! self::should_print() ) {
			return;
		}

		BHP_Consent::render_default_snippet();
		BHP_WPConsent_Bridge::render_consent_sync_snippet();

		$container_id = BHP_Analytics_Config::gtm_container_id();
		printf(
			"<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','%s');</script>\n",
			esc_js( $container_id )
		);
	}

	public static function render_noscript_snippet() {
		if ( ! self::should_print() ) {
			return;
		}
		printf(
			'<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n",
			esc_attr( BHP_Analytics_Config::gtm_container_id() )
		);
	}
}

BHP_GTM_Loader::init();
