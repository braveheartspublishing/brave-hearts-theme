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
	 * True only when the visitor's own recorded choice grants
	 * analytics_storage. Fail-closed in every other case: no cookie, an
	 * unparseable cookie, an unrecognised value, or no consent surface at
	 * all all resolve to false.
	 *
	 * Prefers window.bhpConsentBridge (printed in <head> ahead of the GTM
	 * container, and the single source of truth for consent) and falls back
	 * to reading the cookies directly, so this file still behaves correctly
	 * on a page where the container is not emitted at all.
	 */
	function analyticsConsentGranted() {
		if (window.bhpConsentBridge && typeof window.bhpConsentBridge.storedChoice === 'function') {
			var stored = window.bhpConsentBridge.storedChoice();
			return !! stored && 'granted' === stored.analytics_storage;
		}

		var own = readCookie('bhp_consent_state');
		if (own) {
			return 'granted' === own.analytics_storage;
		}

		var cmp = readCookie('wpconsent_preferences');
		if (cmp) {
			return true === cmp.statistics;
		}

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
