<?php
/**
 * Brave Hearts Publishing — single-use, short-TTL conversion tokens.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.292 (2026-08-26, `CYCLE166-CX-CAPTURE-REPAIR`) — A CONVERSION
 *    EVENT MAY ONLY FIRE ON A REAL SIGNUP REDIRECT. A BARE PAGE LOAD OF A
 *    THANK-YOU PAGE NOW FIRES NOTHING.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT THIS CLOSES IS DATA INTEGRITY, NOT SECURITY. Nothing here
 *    protects money or PII; it protects the ONLY numbers the funnel is
 *    steered by. Until now `adventure_kit_signup` fired for ANY GET of
 *    /adventure-kit-thank-you/, from anyone, forever. The page's own
 *    docblock asserted the trust boundary — *"only reachable via the
 *    whitelisted redirect key"* — but that describes how the SITE links to
 *    it, and a URL is not a capability. Nothing stopped a crawler, a
 *    preview fetcher or a person with the link from manufacturing a
 *    conversion.
 *
 * ⭐ IT WAS NOT HYPOTHETICAL — MEASURED ON PRODUCTION, 2026-08-26, from the
 *    raw SiteGround access logs (`~/www/braveheartspublishing.com/logs/`,
 *    gzipped, read read-only over SSH):
 *
 *      2026-08-17 .. 2026-08-26 — thank-you page GETs: 34
 *      2026-08-17 .. 2026-08-26 — real signups in the same window: 0
 *
 *    The user agents behind those 34 loads: `curl/8.12.1` (6),
 *    `HeadlessChrome` (7), `Applebot` (4), `AhrefsBot` (3), `Amazonbot` (2),
 *    `Googlebot` (1), an internal `WordPress/7.0.4` loopback (1), and a
 *    handful of ordinary UAs. ⭐ 2026-08-20 ALONE PRODUCED 20 THANK-YOU
 *    PAGE LOADS AND ZERO SIGNUPS. Every one of those loads pushed
 *    `adventure_kit_signup` into `dataLayer`. That is the whole of the
 *    "success > submit" anomaly, and it is why the funnel's conversion
 *    count cannot currently be trusted in either direction.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * HOW IT WORKS — and why each choice is the conservative one
 * ─────────────────────────────────────────────────────────────────────────
 *
 * `bhp_mint_conversion_token()` is called at exactly ONE place — inside
 * `bhp_resolve_success_redirect()` in inc/mailchimp.php, which is the single
 * choke point through which every successful signup URL is built, for both
 * the classic 303 form POST and the quiz/modal JSON endpoint. Minting there
 * rather than at each call site means a future signup surface inherits the
 * gate by construction and cannot forget it.
 *
 * The token is 32 hex characters from `wp_generate_password()` /
 * `random_bytes()` via WordPress, stored as a TRANSIENT keyed by its own
 * hash. The value stored is a tiny array describing WHICH conversion it
 * was — never an email, never a name, never an IP. ⛔ NO PII TOUCHES THIS
 * SUBSYSTEM, which also keeps it clear of Andrew's parked failure-path
 * email-storage decision.
 *
 * ⭐ SINGLE-USE: `bhp_consume_conversion_token()` DELETES the transient
 *    before it returns success. A refresh, a back-navigation, a second tab
 *    or a shared link therefore fires nothing. This REPLACES the old
 *    client-side `sessionStorage` dedup latch, which could only ever dedup
 *    within one tab and did nothing at all about a load that never had a
 *    conversion behind it.
 *
 * ⭐ SHORT TTL: five minutes. A redirect consumes its token within
 *    milliseconds; the only thing a longer window buys is a larger replay
 *    surface. Filterable via `bhp_conversion_token_ttl` for QA, floored at
 *    30s and capped at 30min so a filter cannot accidentally make the token
 *    permanent.
 *
 * ⚠️ TRANSIENTS CAN BE EVICTED. If an object cache drops the transient
 *    between the redirect and the page load, the conversion is LOST rather
 *    than double-counted. That direction is deliberate: this pass exists
 *    because the metric was inflated, and an undercount that is visible is
 *    better than an overcount that is invisible. Recorded honestly rather
 *    than engineered around.
 *
 * ⛔ THIS FILE CHANGES NO EVENT NAME AND NO PAYLOAD FIELD. `dataLayer`
 *    receives exactly the same `adventure_kit_signup` object it always did,
 *    with the same keys, so no GTM tag, GA4 config or Meta mapping needs to
 *    change and no historical series is renamed. ONLY WHETHER IT FIRES
 *    CHANGED. GTM is the `connected-operator` lane and is deliberately untouched.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Query argument that carries the token on a success redirect.
 *
 * Deliberately opaque and deliberately NOT named anything resembling
 * "email", "lead" or "success" — it is a nonce-like capability, and a
 * descriptive name would invite someone to guess it means something.
 */
const BHP_CONVERSION_TOKEN_ARG = 'bhp_ct';

/**
 * Transient key prefix. The token itself is never used as the key —
 * the key is a salted hash of it, so a database or object-cache dump does
 * not hand anyone a working token.
 */
const BHP_CONVERSION_TOKEN_PREFIX = 'bhp_ct_';

/**
 * Token lifetime in seconds. Five minutes, filterable, hard-bounded.
 */
function bhp_conversion_token_ttl() {
	$ttl = (int) apply_filters( 'bhp_conversion_token_ttl', 5 * MINUTE_IN_SECONDS );

	// A filter must not be able to make the token effectively permanent,
	// which would reopen exactly the replay hole this file closes.
	return max( 30, min( $ttl, 30 * MINUTE_IN_SECONDS ) );
}

/**
 * Storage key for a token. Salted hash, never the raw token.
 */
function bhp_conversion_token_key( $token ) {
	$token = (string) $token;
	if ( $token === '' ) {
		return '';
	}

	return BHP_CONVERSION_TOKEN_PREFIX . substr( hash_hmac( 'sha256', $token, wp_salt( 'nonce' ) ), 0, 32 );
}

/**
 * Mint a single-use conversion token for one real, completed signup.
 *
 * $context is a small, non-PII description of the conversion that the
 * thank-you page may use to enrich its event. Anything resembling personal
 * data is stripped here rather than trusted not to be passed — a defensive
 * choice, because this function is reachable from every signup surface.
 *
 * @param array $context ['lead_magnet' => string, 'audience' => string, 'signup_method' => string]
 * @return string The token, or '' if one could not be minted.
 */
function bhp_mint_conversion_token( array $context = array() ) {
	$token = wp_generate_password( 32, false, false );
	$key   = bhp_conversion_token_key( $token );

	if ( $key === '' ) {
		return '';
	}

	// ⛔ WHITELIST, NOT BLACKLIST. Only these three keys are ever stored, so
	//    an email or a name passed in by a future caller cannot leak into
	//    the transient store by accident.
	$payload = array(
		'lead_magnet'   => isset( $context['lead_magnet'] ) ? sanitize_key( (string) $context['lead_magnet'] ) : '',
		'audience'      => isset( $context['audience'] ) ? sanitize_key( (string) $context['audience'] ) : '',
		'signup_method' => isset( $context['signup_method'] ) ? sanitize_key( (string) $context['signup_method'] ) : 'form',
		'minted'        => time(),
	);

	if ( ! set_transient( $key, $payload, bhp_conversion_token_ttl() ) ) {
		return '';
	}

	return $token;
}

/**
 * Append a freshly minted token to a success-redirect URL.
 *
 * Returns the URL unchanged if a token could not be minted, so a storage
 * failure degrades to "no conversion event" and NEVER to "no redirect".
 * ⛔ THE VISITOR ALWAYS REACHES THEIR THANK-YOU PAGE AND THEIR KIT. The
 *    analytics gate must never be able to break the customer's journey.
 */
function bhp_add_conversion_token( $url, array $context = array() ) {
	$url = (string) $url;
	if ( $url === '' ) {
		return $url;
	}

	$token = bhp_mint_conversion_token( $context );
	if ( $token === '' ) {
		return $url;
	}

	return add_query_arg( BHP_CONVERSION_TOKEN_ARG, $token, $url );
}

/**
 * Consume the token on the current request, if there is a valid one.
 *
 * ⭐ SINGLE-USE IS ENFORCED HERE, BY DELETION BEFORE RETURN. Called at most
 *    once per request; the result is memoised so that two callers on one
 *    page load (say, the page template and a future Meta bridge) both see
 *    the same answer rather than the second one seeing a spent token.
 *
 * @return array|false The stored context on success, false otherwise.
 */
function bhp_consume_conversion_token() {
	static $resolved = null;

	if ( $resolved !== null ) {
		return $resolved;
	}

	$resolved = false;

	if ( empty( $_GET[ BHP_CONVERSION_TOKEN_ARG ] ) ) {
		return $resolved;
	}

	$token = sanitize_text_field( wp_unslash( $_GET[ BHP_CONVERSION_TOKEN_ARG ] ) );

	// Shape check before any storage read: the mint format is exactly 32
	// alphanumerics, so anything else is not worth a database round trip.
	if ( ! preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
		return $resolved;
	}

	$key = bhp_conversion_token_key( $token );
	if ( $key === '' ) {
		return $resolved;
	}

	$payload = get_transient( $key );
	if ( ! is_array( $payload ) ) {
		return $resolved;
	}

	// ⭐ BURN IT. Everything after this point is a one-way door: a refresh,
	//    a back-navigation or a forwarded link finds nothing.
	delete_transient( $key );

	$resolved = $payload;

	return $resolved;
}

/**
 * Is the CURRENT request a genuine, freshly-converted arrival?
 *
 * This is the single predicate every thank-you page asks. It is deliberately
 * a thin wrapper so that call sites read as intent rather than mechanism.
 */
function bhp_is_verified_conversion() {
	return false !== bhp_consume_conversion_token();
}
