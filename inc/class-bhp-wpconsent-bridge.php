<?php
/**
 * Brave Hearts Publishing — WPConsent Free integration bridge.
 *
 * WPConsent Free (wpconsent-cookies-banner-privacy-suite, WordPress.org)
 * owns the banner UI (Accept All / Reject Nonessential / Manage
 * Preferences) and its own `wpconsent_preferences` cookie. This class is
 * the ONLY thing that turns that choice into a Consent Mode signal.
 *
 * WPConsent's own Google-consent-mode script is deliberately left
 * disabled (google_consent_mode=0 in its settings) so there is exactly
 * one system emitting gtag('consent', ...) calls -- BHP_Consent for the
 * defaults, this class for the updates -- avoiding any contradictory
 * consent commands between two plugins.
 *
 * ⚠ 2026-08-05 REWRITE (theme 1.19.178, `CYCLE143-GIM-51`). Two things
 * changed and both matter:
 *
 * 1. The server no longer gates the GTM snippet on the consent cookie, so
 *    THIS class is now the only mechanism that ever raises a signal from
 *    denied to granted. If it fails, the site under-measures; it can never
 *    over-collect, because the server-rendered default is denied for
 *    everyone.
 *
 * 2. The sync runs in the HEAD, printed inline by BHP_GTM_Loader between
 *    the defaults snippet and the container loader -- not in the footer.
 *    A returning visitor who already accepted must have their update in
 *    the dataLayer before GTM initializes (and comfortably inside the
 *    500ms `wait_for_update` window), which a footer script cannot
 *    guarantee. The printed bytes are identical for every visitor; only
 *    the browser's own execution differs, against a cookie the server
 *    never reads. That is what makes the page cacheable.
 *
 * The old first-visit gap this closes, proven live on production
 * 2026-08-04: the previous bridge only acted when a `wpconsent_preferences`
 * cookie already existed or a fresh save event fired, so a visitor served
 * a mis-cached "granted" page had nothing to correct them. Defaults are
 * now denied for everyone, and this script only ever moves a signal
 * upward from the visitor's own recorded choice.
 *
 * The footer enqueue is retained ONLY for the configuration where the head
 * snippet does not print at all (no container ID / analytics off), so the
 * `bhp_consent_state` mirror cookie still tracks the visitor's choice for
 * the staging debug panel. It never double-emits: the shared script
 * self-guards on window.bhpConsentBridge.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_WPConsent_Bridge {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_bridge_script' ), 20 );
	}

	private static function is_wpconsent_active() {
		return function_exists( 'wpconsent' );
	}

	/**
	 * Footer fallback. Only runs when the head snippet is NOT printing --
	 * BHP_GTM_Loader::render_head_snippet() prints the same JS inline, and
	 * emitting it twice would register the save-event listener twice.
	 */
	public static function enqueue_bridge_script() {
		if ( ! self::is_wpconsent_active() ) {
			return;
		}
		if ( class_exists( 'BHP_GTM_Loader' ) && BHP_GTM_Loader::should_print() ) {
			return; // already printed in <head>, ahead of the container
		}

		wp_register_script( 'bhp-wpconsent-bridge', false, array(), wp_get_theme()->get( 'Version' ), true );
		wp_enqueue_script( 'bhp-wpconsent-bridge' );
		wp_add_inline_script( 'bhp-wpconsent-bridge', self::bridge_js() );
	}

	/**
	 * Prints the bridge inline in <head>, immediately after the Consent
	 * Mode defaults and immediately before the GTM container loader.
	 *
	 * Printed unconditionally by the loader (not conditioned on WPConsent
	 * being active) for two reasons: the output must be identical for every
	 * visitor, and the `bhp_consent_state` fallback path still works if the
	 * CMP plugin is ever swapped.
	 */
	public static function render_consent_sync_snippet() {
		printf( "<script>%s</script>\n", self::bridge_js() ); // phpcs:ignore WordPress.Security.EscapeOutput -- static, self-authored JS with no dynamic input
	}

	/**
	 * Maps WPConsent's two consent categories (statistics, marketing) to
	 * the four Consent Mode v2 signals BHP_Consent::SIGNALS expects.
	 * Statistics -> analytics_storage. Marketing -> the three ad_* signals
	 * (this site does not currently distinguish ad_storage from
	 * ad_user_data/ad_personalization at the banner-category level, so
	 * all three move together with the single "marketing" toggle --
	 * matches WPConsent's own updateWordPressConsent() mapping).
	 *
	 * Everything here is static text. No PHP value is interpolated, so the
	 * emitted bytes cannot vary by visitor, request or cookie.
	 */
	private static function bridge_js() {
		return <<<'JS'
(function () {
	if ( window.bhpConsentBridge ) { return; } // already initialised in <head>

	var SIGNAL_KEYS = [ 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization' ];

	window.dataLayer = window.dataLayer || [];
	if ( typeof window.gtag !== 'function' ) {
		window.gtag = function () { window.dataLayer.push( arguments ); };
	}

	function readCookie( name ) {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ) : null;
	}

	function toSignals( preferences ) {
		var analytics = !! preferences.statistics;
		var marketing = !! preferences.marketing;
		return {
			analytics_storage: analytics ? 'granted' : 'denied',
			ad_storage: marketing ? 'granted' : 'denied',
			ad_user_data: marketing ? 'granted' : 'denied',
			ad_personalization: marketing ? 'granted' : 'denied'
		};
	}

	// Accepts only the exact signal vocabulary. Anything unrecognised in a
	// stored cookie resolves to 'denied' -- never to 'granted' by accident.
	function normaliseSignals( raw ) {
		var out = {};
		for ( var i = 0; i < SIGNAL_KEYS.length; i++ ) {
			var key = SIGNAL_KEYS[ i ];
			out[ key ] = ( raw && raw[ key ] === 'granted' ) ? 'granted' : 'denied';
		}
		return out;
	}

	function writeBhpCookie( signals ) {
		var value = encodeURIComponent( JSON.stringify( signals ) );
		var days = 365;
		var expires = new Date( Date.now() + days * 24 * 60 * 60 * 1000 ).toUTCString();
		document.cookie = 'bhp_consent_state=' + value + '; expires=' + expires + '; path=/; SameSite=Lax';
	}

	function updateGtag( signals ) {
		window.gtag( 'consent', 'update', signals );
	}

	function applySignals( signals ) {
		writeBhpCookie( signals );
		updateGtag( signals );
	}

	function applyPreferences( preferences ) {
		applySignals( toSignals( preferences || {} ) );
	}

	// Returns the visitor's own recorded choice, or null if they have never
	// made one. WPConsent's cookie is authoritative; bhp_consent_state is
	// this bridge's own mirror and is the fallback when the CMP cookie is
	// absent (e.g. a different consent surface wrote it).
	function storedChoice() {
		var wp = readCookie( 'wpconsent_preferences' );
		if ( wp ) {
			try { return toSignals( JSON.parse( wp ) ); } catch ( e ) {}
		}
		var own = readCookie( 'bhp_consent_state' );
		if ( own ) {
			try { return normaliseSignals( JSON.parse( own ) ); } catch ( e ) {}
		}
		return null;
	}

	window.bhpConsentBridge = {
		applyPreferences: applyPreferences,
		applySignals: applySignals,
		storedChoice: storedChoice
	};

	// ON LOAD: the server-rendered defaults are denied for EVERY visitor,
	// so a returning visitor who already accepted is currently denied and
	// must be corrected here -- before the container initialises, inside
	// the wait_for_update window. A first-time visitor has no stored
	// choice, nothing is sent, and the denied defaults stand. That is the
	// path CYCLE143-GIM-51 proved broken and this is the fix.
	var stored = storedChoice();
	if ( stored ) {
		applySignals( stored );
	}

	// ON CHOICE: the visitor's live banner interaction.
	window.addEventListener( 'wpconsent_consent_saved', function ( event ) {
		applyPreferences( event.detail || {} );
	} );
} )();
JS;
	}
}

BHP_WPConsent_Bridge::init();
