/**
 * Brave Hearts Publishing — FORM-MOMENT ATTRIBUTION, ASSEMBLED CLIENT-SIDE.
 * 1.19.342, `CYCLE172-LD-FUNNEL-FIX`, audit gap G-A.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHY THIS FILE EXISTS AT ALL. READ THIS BEFORE MOVING ANY OF IT BACK
 *     INTO PHP.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Until 1.19.341 the hidden `bhp_attr_now` field was rendered WITH ITS VALUE by
 * PHP, from `$_GET`, on pages that sit behind SiteGround's full-page cache.
 *
 * ⛔ OBSERVED LIVE ON PRODUCTION 2026-08-31, reproduced three times: an
 *    anonymous GET of the parent landing page with NO QUERY STRING AT ALL
 *    returned three signup forms each carrying a real visitor's
 *    `fbclid=PAcGRvZgJ…` and `utm_campaign=leads-free-chapter-2026-08`.
 *    Response headers: `X-Proxy-Cache: HIT`, `Vary: Accept-Encoding` — the
 *    cache varies on encoding and NOTHING ELSE. SiteGround additionally
 *    STRIPS `utm_*` and `fbclid` from the cache key, so every campaign
 *    variant and the clean URL all share ONE cache entry. Whichever render
 *    landed there is served to everyone.
 *
 * ⛔ THE CONSEQUENCE WAS NOT A MISSING NUMBER, IT WAS A FALSE ONE. Organic,
 *    direct and email signups were being stamped `facebook / paid` in
 *    Mailchimp's TRAFFIC merge field. A cost-per-lead computed from that is
 *    wrong in the direction that flatters paid media.
 *
 * ⭐ THE PRIOR CODE'S GUARD WAS *"emit the field only when the URL actually
 *    carried something, so a clean page's cached HTML carries nothing to
 *    leak."* The reasoning is sound and the premise is false: the cache key
 *    cannot see the "only when", so the campaign render is what gets stored
 *    under the clean key. ⛔ THERE IS NO NARROWER SERVER-SIDE CONDITION THAT
 *    FIXES THIS. Any per-visitor value in cacheable HTML is wrong.
 *
 * ⛔⛔ THIS IS THE SECOND TIME THIS COMPANY HAS HIT THIS EXACT FAILURE CLASS.
 *     `CYCLE143-GIM-51` (2026-08-05, theme 1.19.178) removed a per-visitor
 *     consent gate from the analytics renderer for the same reason and wrote
 *     the rule down in `inc/class-bhp-analytics-config.php`: *a per-visitor
 *     server-side gate in front of a full-page cache is defeated in both
 *     directions.* 1.19.323 reintroduced per-visitor server-rendered output on
 *     a cached page and the rule caught it again 26 days later.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE DOES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The field is now rendered UNCONDITIONALLY and ALWAYS EMPTY, so every visitor
 * receives byte-identical HTML and the page is honestly cacheable. This script
 * fills it from `window.location.search` — THE VISITOR'S OWN URL, READ IN THE
 * VISITOR'S OWN BROWSER — which is a value that cannot be cached because it
 * never passes through the cache.
 *
 * It fills twice, and both matter:
 *   1. On DOM ready, so a form serialised by anything other than a native
 *      submit still carries the value.
 *   2. On `submit`, captured on the document, so a form injected into the page
 *      later (the modal, the popup engine) is covered without this file
 *      knowing those components exist.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DELIBERATELY DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ NO COOKIE, NO localStorage, NO sessionStorage, NO NETWORK REQUEST. It
 *    moves a value from the address bar into a form field on the same page and
 *    stops. That is why it carries NO CONSENT GATE and needs none — it is not
 *    storage and not tracking, and it is exactly the posture the PHP version
 *    had. ⭐ IT IS NOT `assets/js/bhp-attribution.js`. THAT file writes the two
 *    first-party attribution COOKIES and IS consent-gated, deliberately and
 *    permanently. Do not merge these two files: one stores, one does not, and
 *    they answer to different rules.
 *
 * ⛔ NO PII, ENFORCED BY WHITELIST. Only the campaign and click-ID parameters
 *    below are ever copied. An email address in a query string is not on the
 *    list and cannot reach the field. ⭐ THE SERVER RE-FILTERS EVERYTHING THAT
 *    ARRIVES through `bhp_extract_attribution_params()` — this whitelist is a
 *    convenience and a second wall, never the only one. A visitor editing this
 *    field by hand can produce nothing they could not already produce by typing
 *    a UTM into their own address bar.
 *
 * ⛔ NO `landing_page`, NO URL, NO PATH, NO REFERRER. Campaign identifiers only.
 *
 * ⛔ IT NEVER OVERWRITES A NON-EMPTY VALUE it did not write itself, so a caller
 *    that deliberately set one keeps it.
 */
( function () {
	'use strict';

	/*
	 * ⛔ MUST STAY IN SYNC WITH `bhp_get_attribution_capture_params()` IN
	 *    `inc/mailchimp.php`, WHICH IS THE CANONICAL LIST. The server filters
	 *    against its own copy, so a drift here can only ever DROP a parameter,
	 *    never admit one the server would refuse.
	 */
	var TRACKED_PARAMS = [
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_content',
		'utm_term',
		'gclid',
		'fbclid',
		'ttclid',
		'msclkid'
	];

	/* Matches the server-side per-value cap in `bhp_extract_attribution_params()`. */
	var MAX_VALUE_LENGTH = 200;

	/**
	 * The campaign fragment for THIS page load, or '' when the visitor's URL
	 * carries nothing worth carrying.
	 *
	 * Built with URLSearchParams so encoding matches what the server expects,
	 * and rebuilt from the whitelist rather than passed through, so nothing
	 * outside the list can survive.
	 */
	function currentFragment() {
		if ( typeof window.URLSearchParams !== 'function' ) {
			return ''; // Ancient browser: degrade to no capture, never to a guess.
		}

		var params = new window.URLSearchParams( window.location.search );
		var out = new window.URLSearchParams();
		var found = false;

		TRACKED_PARAMS.forEach( function ( key ) {
			var value = params.get( key );
			if ( value ) {
				out.append( key, String( value ).slice( 0, MAX_VALUE_LENGTH ) );
				found = true;
			}
		} );

		return found ? out.toString() : '';
	}

	/*
	 * Computed once. The address bar can change under a SPA-style history push,
	 * but this site is server-rendered and a real navigation reloads the script,
	 * so recomputing per submit would buy nothing and would let a `history`
	 * rewrite by any other script change what gets attributed.
	 */
	var FRAGMENT = currentFragment();

	/* Marks a field as "this script owns this value", so we never clobber a
	   value some other caller set deliberately. */
	var OWNED_FLAG = 'bhpAttrNowFilled';

	function fill( root ) {
		if ( '' === FRAGMENT || ! root || typeof root.querySelectorAll !== 'function' ) {
			return;
		}
		var fields = root.querySelectorAll( 'input[data-bhp-attr-now]' );
		for ( var i = 0; i < fields.length; i++ ) {
			var field = fields[ i ];
			if ( '' !== field.value && ! field.dataset[ OWNED_FLAG ] ) {
				continue; // Somebody else's value. Leave it alone.
			}
			field.value = FRAGMENT;
			field.dataset[ OWNED_FLAG ] = '1';
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			fill( document );
		} );
	} else {
		fill( document );
	}

	/*
	 * ⭐ CAPTURE PHASE, ON THE DOCUMENT. A form that did not exist at DOM-ready
	 *    — the lead-magnet modal, anything the popup engine renders — is still
	 *    filled the instant it is submitted, and this file needs to know nothing
	 *    about those components. Capture rather than bubble so the value is in
	 *    place before any other submit handler reads the form.
	 */
	document.addEventListener(
		'submit',
		function ( event ) {
			if ( event.target && 'FORM' === event.target.tagName ) {
				fill( event.target );
			}
		},
		true
	);
} )();
