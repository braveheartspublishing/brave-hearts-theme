/**
 * Brave Hearts Publishing — Analytics Phase 1B: first-party UTM/attribution
 * capture.
 *
 * Runs on every page load. Writes two first-party cookies:
 *   - bhp_attr_first (90-day expiry) — set ONCE per visitor, never
 *     overwritten again for the life of the cookie (Phase 9: "Do not
 *     overwrite first-touch values").
 *   - bhp_attr_last (30-day expiry) — updated only when the CURRENT page
 *     load carries a valid new source/campaign signal. A later direct
 *     visit (no UTM/click-id params, referrer same-site or empty) leaves
 *     the existing last-touch value untouched (Phase 9: "Direct visits
 *     must not erase a valid prior campaign").
 *
 * No PII is ever read or stored here — only campaign/click identifiers
 * and the landing path, matching docs/utm-attribution.md.
 *
 * ⚠ 2026-08-05 (theme 1.19.178, `CYCLE143-GIM-51` / `CYCLE144-LD-64`).
 * CONSENT GATE MOVED FROM PHP TO HERE, and this file is the reason it had
 * to move rather than simply disappear.
 *
 * Until 1.19.177 the PHP enqueue was wrapped in
 * BHP_Analytics_Config::should_render_analytics(), which included a
 * per-visitor consent check — so this script only reached a visitor who
 * had already granted analytics consent. 1.19.178 removed that per-visitor
 * check because it made the rendered HTML vary by cookie and SiteGround's
 * page cache, which varies only on Accept-Encoding, then served it to the
 * wrong visitors in both directions.
 *
 * Removing it without compensating here would have meant these two
 * first-party cookies being written BEFORE the visitor consents. So the
 * script tag is now unconditional (identical HTML for everyone, which is
 * what makes the page cacheable) and the WRITE is gated in the browser,
 * against the visitor's own stored choice — fail-closed: no recognised
 * grant, no cookie. A visitor who accepts on this very page is captured
 * on the acceptance event, so attribution is not lost.
 *
 * ⭐⭐ 2026-08-31 UPDATE (theme 1.19.343, `CYCLE173-LD-CONSENT-CHECKOUT`) —
 * THE WORDS "against the visitor's own stored choice" IMMEDIATELY ABOVE ARE
 * SUPERSEDED. They are kept, not corrected in place, so the movement stays
 * legible. Between 1.19.302 and 1.19.342 that condition became UNSATISFIABLE
 * for the site's actual traffic: the banner is deliberately suppressed
 * outside the EEA+UK, so a US visitor has no way to record a choice, so
 * these two cookies were never written for anyone. The gate now reads the
 * site's own region-scoped consent posture — the same one GA4 and the Meta
 * pixel already run on — with the stored choice still winning outright in
 * both directions. Full reasoning, the live evidence and the precedence
 * order are at analyticsConsentGranted() below. ⛔ Its production deploy is
 * a separately-approved Andrew decision.
 */
(function () {
	'use strict';

	var FIRST_TOUCH_COOKIE = 'bhp_attr_first';
	var LAST_TOUCH_COOKIE = 'bhp_attr_last';
	var FIRST_TOUCH_DAYS = 90;
	var LAST_TOUCH_DAYS = 30;
	var MAX_FIELD_LENGTH = 200;

	var TRACKED_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'ttclid', 'msclkid'];

	function readCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
		if (!match) {
			return null;
		}
		try {
			return JSON.parse(decodeURIComponent(match[1]));
		} catch (e) {
			return null;
		}
	}

	function writeCookie(name, valueObj, days) {
		var expires = new Date();
		expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
		var value = encodeURIComponent(JSON.stringify(valueObj));
		var secure = 'https:' === window.location.protocol ? '; Secure' : '';
		document.cookie = name + '=' + value + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax' + secure;
	}

	function sanitize(value) {
		if (!value) {
			return '';
		}
		return String(value).slice(0, MAX_FIELD_LENGTH);
	}

	/**
	 * A "valid new touch" requires at least one UTM param OR one click ID
	 * param present in the current URL — a bare visit to the homepage with
	 * no query string, or a same-site internal navigation, never counts as
	 * a new touch (this is what keeps direct visits from clobbering an
	 * existing last-touch value).
	 */
	function parseCurrentTouch() {
		var params = new URLSearchParams(window.location.search);
		var touch = {};
		var hasSignal = false;

		TRACKED_PARAMS.forEach(function (key) {
			var value = params.get(key);
			if (value) {
				touch[key] = sanitize(value);
				hasSignal = true;
			}
		});

		if (!hasSignal) {
			return null;
		}

		touch.landing_page = sanitize(window.location.pathname);
		touch.timestamp = new Date().toISOString();
		return touch;
	}

	function capture() {
		var current = parseCurrentTouch();

		if (!readCookie(FIRST_TOUCH_COOKIE)) {
			// No first-touch recorded yet for this visitor at all. If this
			// page load itself carries a campaign signal, that becomes
			// first-touch; otherwise record a plain "direct/organic" first
			// touch so later reporting always has a first-touch baseline
			// rather than silently having none.
			var firstTouch = current || {
				utm_source: 'direct',
				utm_medium: 'none',
				landing_page: sanitize(window.location.pathname),
				timestamp: new Date().toISOString()
			};
			writeCookie(FIRST_TOUCH_COOKIE, firstTouch, FIRST_TOUCH_DAYS);
		}

		if (current) {
			// Only overwrite last-touch when THIS load carries a real
			// campaign/click signal — a direct visit never reaches this
			// branch, so the previous last-touch value is left alone.
			writeCookie(LAST_TOUCH_COOKIE, current, LAST_TOUCH_DAYS);
		}
	}

	/**
	 * Returns the visitor's OWN recorded choice for analytics_storage, or
	 * null when they have never made one. Never guesses.
	 *
	 * Prefers window.bhpConsentBridge (printed in <head> ahead of the GTM
	 * container, and the single source of truth for consent) and falls back
	 * to reading the cookies directly, so this file still behaves correctly
	 * on a page where the container is not emitted at all.
	 *
	 * @return {boolean|null} true = granted, false = refused, null = no choice
	 */
	function storedAnalyticsChoice() {
		if (window.bhpConsentBridge && typeof window.bhpConsentBridge.storedChoice === 'function') {
			var stored = window.bhpConsentBridge.storedChoice();
			return stored ? ('granted' === stored.analytics_storage) : null;
		}

		var own = readCookie('bhp_consent_state');
		if (own) {
			return 'granted' === own.analytics_storage;
		}

		var cmp = readCookie('wpconsent_preferences');
		if (cmp) {
			return true === cmp.statistics;
		}

		return null;
	}

	/**
	 * Global Privacy Control. Mirrors the branch BHP_WPConsent_Bridge
	 * already runs — the same signal must not mean two different things in
	 * two files that gate the same visitor.
	 */
	function gpcActive() {
		try {
			return window.navigator && true === window.navigator.globalPrivacyControl;
		} catch (e) {
			return false;
		}
	}

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.19.343 (2026-08-31, `CYCLE173-LD-CONSENT-CHECKOUT`) — THIS GATE
	 *     WAS LEFT BEHIND BY 1.19.302/1.19.312 AND IS THE REASON NO VISITOR
	 *     ON THIS SITE HAS FIRST-PARTY ATTRIBUTION.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔⛔ READ THIS BEFORE CHANGING ANYTHING BELOW. THE PRODUCTION DEPLOY OF
	 *     THIS PARTICULAR CHANGE IS AN EXPLICIT ANDREW DECISION and is
	 *     carried as its own line item in the 1.19.343 DEPLOY-PLAN. It is on
	 *     staging so it can be seen working; it is not approved for
	 *     production by the existence of this code.
	 *
	 * ⚠ THE OBSERVED FACT. Production, real browser, 2026-08-31, theme
	 *   1.19.342, a US visitor (`America/Denver`, `en-US`):
	 *
	 *     window.bhpConsentRegion.showBanner .... false   (banner suppressed)
	 *     window.wpconsent.enable_consent_banner  false
	 *     gtag consent default (catch-all) ...... all four GRANTED
	 *     cookies present ....................... _ga, _ga_7M42X19Z2T,
	 *                                             _gcl_au, _fbp, sbjs_*
	 *     cookies ABSENT ........................ bhp_attr_first,
	 *                                             bhp_attr_last,
	 *                                             wpconsent_preferences,
	 *                                             bhp_consent_state
	 *
	 *   GA4 measures that visitor. The Meta pixel measures that visitor.
	 *   WooCommerce's own sourcebuster attribution measures that visitor.
	 *   THIS FILE DOES NOT — and it is the only one of the four that asks
	 *   for a STORED CHOICE rather than for the site's consent STATE.
	 *
	 * ⛔ AND A US VISITOR CANNOT MAKE A STORED CHOICE. The banner is
	 *   deliberately suppressed outside the EEA+UK (theme 1.19.309,
	 *   `CYCLE167-LD-CONSENT-BANNER-GEO`, on Andrew's ruling). So the
	 *   pre-1.19.343 condition was not strict — it was UNSATISFIABLE. That
	 *   is the `finance-analytics` gap G-E, measured: `_bhp_lead_first_touch` and
	 *   `_bhp_lead_last_touch` empty on ALL 12 most recent lead events;
	 *   order-side first-touch 6/22, last-touch 4/22.
	 *
	 * ⭐ THE CORRECTION IS AN ALIGNMENT, NOT A NEW POSTURE. This file now
	 *   reads the SAME region decision and the SAME precedence the rest of
	 *   the consent system already runs, from the SAME shipped object
	 *   (`window.bhpConsentRegion`, published at wp_head priority 1 by
	 *   `bhp_consent_region_gate_script()`). No second region list, no
	 *   second heuristic, no copy of the EEA table — a divergence between
	 *   two lists is the obvious future bug and there is now nothing to
	 *   diverge.
	 *
	 *   PRECEDENCE, and it is deliberately identical to the bridge's:
	 *     1. An explicit stored choice ALWAYS wins, in both directions. An
	 *        EEA visitor who accepted is captured; ANY visitor who refused
	 *        is not, banner or no banner. ⛔ This is the limb that makes the
	 *        opt-out real, and it must never be reordered below 2 or 3.
	 *     2. No choice + GPC on -> NO capture. Lowers the granted default,
	 *        can never raise anything.
	 *     3. No choice, no GPC, banner correctly suppressed for this region
	 *        -> capture, because `analytics_storage` is GRANTED by default
	 *        for exactly that visitor (BHP_Consent::measured_default_signals).
	 *     4. Anything else -> NO capture. That covers every EEA and every
	 *        ambiguous visitor (the region gate fails SAFE toward showing
	 *        the banner, so ambiguity lands here), and it also covers the
	 *        region object being absent, malformed or throwing.
	 *
	 * ⛔ THE FAIL-SAFE DIRECTION IS UNCHANGED AND IS ASSERTED BY THE SUITE.
	 *   Every uncertain path still returns false. If a future edit makes any
	 *   branch here capture on uncertainty, it is wrong.
	 *
	 * ⛔ NOT WIDENED: no new cookie, no new field, no PII. The two cookies
	 *   and their contents are byte-for-byte what they were — campaign and
	 *   click identifiers plus the landing path. Only the CONDITION under
	 *   which they are written moved, and it moved onto the site's existing
	 *   approved posture rather than off it.
	 */
	function analyticsConsentGranted() {
		var choice = storedAnalyticsChoice();

		// 1. The visitor's own recorded choice, both directions.
		if (null !== choice) {
			return choice;
		}

		// 2. No choice, but the browser signals opt-out.
		if (gpcActive()) {
			return false;
		}

		// 3. No choice, no GPC: defer entirely to the region gate that
		//    already decided this visitor's Consent Mode default.
		try {
			var region = window.bhpConsentRegion;
			if (region && false === region.showBanner) {
				return true;
			}
		} catch (e) {
			return false; // 4. ANY failure -> no capture.
		}

		// 4. Banner shown (EEA / ambiguous), or no region gate at all.
		return false;
	}

	function captureIfConsented() {
		if (analyticsConsentGranted()) {
			capture();
		}
	}

	captureIfConsented();

	// A visitor who accepts on this very page load: capture at the moment
	// of acceptance, so the campaign that brought them here is not lost to
	// the few seconds they spent reading the banner. Nothing is written
	// before that click.
	window.addEventListener('wpconsent_consent_saved', function () {
		// The bridge writes bhp_consent_state synchronously in its own
		// handler; both listeners are registered on window, and the
		// bridge's is registered first (it is printed in <head>, this file
		// is enqueued in the footer), so the cookie is already updated.
		captureIfConsented();
	});
})();
