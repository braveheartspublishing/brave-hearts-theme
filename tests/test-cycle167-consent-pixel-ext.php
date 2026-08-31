<?php
/**
 * The US-law consent posture, extended to advertising and to the Meta pixel
 * (theme 1.19.312, `CYCLE167-LD-CONSENT-PIXEL-EXT`).
 *
 * Run on staging (never production, except as read-only post-deploy
 * verification) via:
 *   wp eval-file tests/test-cycle167-consent-pixel-ext.php --user=1
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT THIS SUITE CAN AND CANNOT PROVE — read this before quoting a pass.
 * ─────────────────────────────────────────────────────────────────────────
 *
 *   ✅ CAN, in PHP: that the server's two Consent Mode default payloads carry
 *      the extended posture — EEA+UK all-denied, everywhere else all-granted —
 *      and that the extension did not reintroduce per-visitor server-side
 *      variation in front of the page cache (`CYCLE143-GIM-51`).
 *
 *   ✅ CAN, by static analysis of the JS ACTUALLY PRINTED INTO THE PAGE: that
 *      the pixel runtime is consent-STATE-driven — that the precedence order
 *      is choice → GPC → region, that every ambiguous branch resolves to
 *      denied, that the opt-out path exists, and that the Lead map and the
 *      one-Lead latch were not touched.
 *
 *   ⛔ CANNOT, from PHP: observe a pixel initialise, an event transmit, or an
 *      opt-out take effect. Those are browser facts. They are proven two
 *      other ways and BOTH are recorded rather than claimed here:
 *        · tests/js/meta-pixel-consent-harness.js executes the emitted runtime
 *          under a stubbed browser across a fixture table, in both directions.
 *        · a real browser session against live staging, recorded in the QA log
 *          with the timestamp and the instrument.
 *      A PHP assertion that "the US visitor's pixel fires" would be asserting
 *      something this file cannot see, and that is a fabricated check.
 *
 * ⚠ AUTHORITY: Andrew Signore, carrier `^349. FOUNDER` item 4, 2026-08-27 —
 * "I guess we extend it", given after the Chief of Staff clarified that the
 * extension reaches the ad/marketing pixel and not only measurement.
 * ⛔ RELAYED THROUGH THE CHIEF OF STAFF, NOT WITNESSED by this suite's author.
 * It extends carrier item 310 ("lets just go with US Law"), itself relayed.
 * This is not a legal opinion.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_cpx_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

// --- fixture capture / restore ------------------------------------------
$original_host    = $_SERVER['HTTP_HOST'] ?? '';
$original_cookies = $_COOKIE;
$original_gtm_id  = get_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, false);
$original_gate    = get_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, false);
$original_user_id = get_current_user_id();

$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-TESTID1');
update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);
wp_set_current_user(0);

// ======================================================================
// A. THE CONSENT MODE DEFAULTS — the ad signals, in both regions
// ======================================================================
echo "\n--- A. Consent Mode v2 defaults, extended to the ad signals ---\n";

$eea  = BHP_Consent::eea_default_signals();
$rest = BHP_Consent::measured_default_signals();

foreach (BHP_Consent::SIGNALS as $signal) {
    bhp_cpx_assert($failures, sprintf('A1 EEA+UK default: %s is DENIED — the European posture is byte-for-byte unchanged by this release', $signal), isset($eea[$signal]) && 'denied' === $eea[$signal]);
}
bhp_cpx_assert($failures, 'A2 the EEA+UK payload still carries the region list', isset($eea['region']) && $eea['region'] === array_values(BHP_Consent::EEA_UK_REGIONS));

foreach (BHP_Consent::SIGNALS as $signal) {
    bhp_cpx_assert($failures, sprintf('A3 catch-all default: %s is GRANTED — item 4, the extension to advertising', $signal), isset($rest[$signal]) && 'granted' === $rest[$signal]);
}
bhp_cpx_assert($failures, 'A4 the catch-all carries NO region key, so it applies wherever the EEA list does not', !isset($rest['region']));
bhp_cpx_assert($failures, 'A5 both payloads still carry wait_for_update:500', 500 === ($eea['wait_for_update'] ?? null) && 500 === ($rest['wait_for_update'] ?? null));

/* The one assertion that catches the failure that would actually matter:
 * an ad signal leaking into the European default. Asserted as a spanning
 * invariant so an edit to EITHER function trips it. */
bhp_cpx_assert(
    $failures,
    'A6 ⭐ no signal is granted anywhere in the EEA+UK payload, while every signal is granted in the catch-all — a leak in either direction fails here',
    'granted' !== ($eea['analytics_storage'] ?? null) && 'granted' !== ($eea['ad_storage'] ?? null)
        && 'granted' !== ($eea['ad_user_data'] ?? null) && 'granted' !== ($eea['ad_personalization'] ?? null)
        && 'denied' !== ($rest['ad_storage'] ?? null) && 'denied' !== ($rest['ad_user_data'] ?? null)
        && 'denied' !== ($rest['ad_personalization'] ?? null)
);

/* default_signals() is the strict base and must stay strict — it is what the
 * EEA branch is built from, and a "helpful" edit there would silently grant
 * the EEA. */
$strict = BHP_Consent::default_signals();
bhp_cpx_assert($failures, 'A7 default_signals() is still all-denied — the strict base the EEA branch is built from was not touched', 'denied' === $strict['analytics_storage'] && 'denied' === $strict['ad_storage'] && 'denied' === $strict['ad_user_data'] && 'denied' === $strict['ad_personalization']);

// ======================================================================
// B. CACHE SAFETY SURVIVES THE EXTENSION (the CYCLE143-GIM-51 invariant)
// ======================================================================
echo "\n--- B. cache safety ---\n";

function bhp_cpx_head($cookies) {
    $_COOKIE = $cookies;
    ob_start();
    BHP_GTM_Loader::render_head_snippet();
    BHP_Meta_Pixel::render_head();
    return ob_get_clean();
}

$states = [
    'no cookie at all'         => [],
    'marketing accepted'       => ['wpconsent_preferences' => '{"essential":true,"statistics":true,"marketing":true}', 'bhp_consent_state' => '{"analytics_storage":"granted","ad_storage":"granted","ad_user_data":"granted","ad_personalization":"granted"}'],
    'marketing rejected'       => ['wpconsent_preferences' => '{"essential":true,"statistics":false,"marketing":false}', 'bhp_consent_state' => '{"analytics_storage":"denied","ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied"}'],
    'malformed consent cookie' => ['wpconsent_preferences' => 'not-json{{{', 'bhp_consent_state' => ']]]'],
];
$baseline = bhp_cpx_head([]);
foreach ($states as $label => $cookies) {
    bhp_cpx_assert($failures, sprintf('B1 emission is byte-identical for cookie state "%s" — the extension did NOT reintroduce per-visitor server variation', $label), bhp_cpx_head($cookies) === $baseline);
}
$_COOKIE = $original_cookies;

/* The server must still perform no geo work of any kind. The region decision
 * is the browser's, in both subsystems, which is the whole reason this is
 * cacheable. Asserted against the files that would have to change for it to
 * stop being true. */
foreach (['inc/class-bhp-consent.php', 'inc/class-bhp-meta-pixel.php'] as $rel) {
    $src = (string) @file_get_contents(get_template_directory() . '/' . $rel);
    bhp_cpx_assert($failures, sprintf('B2 %s reads no geo header and calls no geo lookup server-side', $rel), 0 === preg_match('/CF-IPCountry|GEOIP|HTTP_X_COUNTRY|geoip_country|MaxMind/i', $src));
}

// ======================================================================
// C. THE PIXEL RUNTIME IS CONSENT-STATE-DRIVEN
//
// Static assertions against the JS ACTUALLY EMITTED, not the source file.
// Behaviour is the harness's and the browser's job — see the header.
// ======================================================================
echo "\n--- C. the pixel runtime ---\n";

$runtime = BHP_Meta_Pixel::runtime_js();

bhp_cpx_assert($failures, 'C1 ⭐ the runtime resolves an EFFECTIVE consent state rather than reading the banner cookie alone', false !== strpos($runtime, 'function effectiveMarketingConsent( prefs )'));
/* ⚠ C2 WAS WHITESPACE-EXACT ON ITS FIRST STAGING RUN AND FAILED AGAINST
 * ENTIRELY CORRECT CODE — it matched "\n\tapplyPreferences();", i.e. exactly
 * one tab of indentation. The emitted nowdoc's indentation is not this suite's
 * business, and asserting on it makes a re-indent look like a regression. The
 * claim is "it is called with NO ARGUMENT, at statement level"; that is what
 * is asserted now. Recorded rather than quietly widened.
 *
 * ⭐⭐ AND THE SECOND HALF FAILED FOR A DIFFERENT, MORE INTERESTING REASON —
 * recorded because it will bite the next person who writes a "the old code is
 * gone" assertion against this codebase. The absence check searched for
 * `applyPreferences( storedPreferences() )`, and FOUND IT: the runtime's own
 * new comment QUOTES the superseded call, exactly as this house style requires
 * of every reversal. The test was correct about the string and wrong about the
 * claim. ⛔ THE LESSON: in a codebase that deliberately preserves superseded
 * code inside comments, an absence assertion MUST be anchored to something a
 * quotation cannot carry — here the trailing semicolon of a real statement. A
 * bare substring search for removed code will match its own epitaph. */
bhp_cpx_assert($failures, 'C2 ⭐ on load it asks for the effective state — the superseded `applyPreferences( storedPreferences() );` STATEMENT, which made an EEA-only banner produce a permanently dead US pixel, is GONE (its quotation in the comment above it does not count)', 1 === preg_match('/^\s*applyPreferences\(\);\s*$/m', $runtime) && false === strpos($runtime, 'applyPreferences( storedPreferences() );'));
bhp_cpx_assert($failures, 'C3 the runtime reads the region gate global published by bhp_consent_region_gate_script()', false !== strpos($runtime, 'window.bhpConsentRegion'));
bhp_cpx_assert($failures, 'C4 the runtime reads Global Privacy Control, the same signal the Consent Mode bridge reads', false !== strpos($runtime, 'navigator.globalPrivacyControl === true'));
bhp_cpx_assert($failures, 'C5 the runtime reads the bridge mirror cookie as the fallback recorded choice', false !== strpos($runtime, "readCookie( 'bhp_consent_state' )") && false !== strpos($runtime, "signals.ad_storage === 'granted'"));

/* ⛔ THE FAIL-SAFE DIRECTION, and it is inverted relative to the banner gate:
 * the banner SHOWS on ambiguity, this runtime DENIES on ambiguity. Asserted
 * mechanically rather than trusted to the comment above it. */
bhp_cpx_assert($failures, 'C6 ⛔ the region default is granted ONLY on an affirmative, strictly-false showBanner — anything else (absent global, non-boolean, throw) denies', false !== strpos($runtime, 'r.showBanner === false'));
bhp_cpx_assert($failures, 'C7 ⛔ regionAllowsDefault() returns false from its catch — a thrown exception denies, it never grants', 1 === preg_match('/function regionAllowsDefault\(\)[\s\S]{0,400}?catch \( e \) \{\s*return false;/', $runtime));
bhp_cpx_assert($failures, 'C8 ⛔ gpcActive() returns false from its catch — an unreadable GPC signal does not become an opt-out AND does not become a grant; the region branch still decides', 1 === preg_match('/function gpcActive\(\)[\s\S]{0,400}?catch \( e \) \{\s*return false;/', $runtime));
bhp_cpx_assert($failures, 'C9 ⛔ mirroredMarketing() returns null on a malformed cookie, so a corrupt mirror falls through to GPC/region rather than granting', 1 === preg_match('/function mirroredMarketing\(\)[\s\S]{0,600}?catch \( e \) \{ return null; \}/', $runtime));

/* PRECEDENCE. Order matters and is asserted by position, because a reordering
 * is exactly the kind of edit that reads harmlessly and changes everything:
 * if GPC were consulted before the recorded choice, an accepting visitor with
 * GPC on would be silently overridden. */
$pos_choice   = strpos($runtime, 'var stored = storedPreferences();');
$pos_mirror   = strpos($runtime, 'var mirrored = mirroredMarketing();');
$pos_gpc      = strpos($runtime, 'if ( gpcActive() ) { return false; }');
$pos_region   = strpos($runtime, 'return regionAllowsDefault();');
bhp_cpx_assert(
    $failures,
    'C10 ⭐ precedence is choice → mirror → GPC → region, in that order — identical to BHP_WPConsent_Bridge, so the pixel and Consent Mode can never disagree about one visitor',
    false !== $pos_choice && false !== $pos_mirror && false !== $pos_gpc && false !== $pos_region
        && $pos_choice < $pos_mirror && $pos_mirror < $pos_gpc && $pos_gpc < $pos_region
);
bhp_cpx_assert($failures, 'C11 an explicit live choice short-circuits ahead of everything else, so a banner click outranks region and GPC in both directions', 1 === preg_match('/function effectiveMarketingConsent\( prefs \) \{\s*if \( typeof prefs !== \x27undefined\x27 && prefs !== null \) \{\s*return marketingAccepted\( prefs \);/', $runtime));

/* THE OPT-OUT PATH — the half that makes this an opt-out regime rather than
 * a taking. */
bhp_cpx_assert($failures, 'C12 ⭐ revoke() issues a live fbq consent revoke', false !== strpos($runtime, "window.fbq( 'consent', 'revoke' );"));
bhp_cpx_assert($failures, 'C13 ⭐ the async grant callback re-checks `granted` first, so an opt-out taken while fbevents.js is still in flight is not undone by a stale closure', 1 === preg_match('/loadSdk\( function \(\) \{[\s\S]{0,2000}?if \( !granted \) \{ return; \}\s*window\.fbq\( \x27consent\x27, \x27grant\x27 \);/', $runtime));
bhp_cpx_assert($failures, 'C14 the runtime still listens to WPConsent save AND update events, so a change made in the preferences modal applies without a reload', false !== strpos($runtime, 'wpconsent_consent_saved') && false !== strpos($runtime, 'wpconsent_consent_updated'));
bhp_cpx_assert($failures, 'C15 a save event with no detail resolves to denied, not granted', false !== strpos($runtime, 'applyPreferences( e.detail || {} )'));

/* Invariant 3 and the funnel-isolation rule, re-asserted because this release
 * touched the runtime. */
bhp_cpx_assert($failures, 'C16 ⛔ the runtime still uses NO localStorage', false === strpos($runtime, 'localStorage'));
bhp_cpx_assert($failures, 'C17 ⛔ the runtime still uses NO sessionStorage', false === strpos($runtime, 'sessionStorage'));
bhp_cpx_assert($failures, 'C18 ⛔ the runtime records no choice on the visitor\'s behalf — it writes no consent cookie, so a default is never mistaken for a decision', false === strpos($runtime, "document.cookie = 'bhp_consent_state") && false === strpos($runtime, "document.cookie = 'wpconsent"));

/* The runtime is still static text: no PHP value interpolated, so the bytes
 * cannot vary by visitor. */
bhp_cpx_assert($failures, 'C19 ⛔ runtime_js() is still a nowdoc — no PHP value can be interpolated into the emitted JS', false === strpos($runtime, BHP_Meta_Pixel::PIXEL_ID));

// ======================================================================
// D. WHAT THIS RELEASE DELIBERATELY DID NOT TOUCH
// ======================================================================
echo "\n--- D. unchanged surfaces ---\n";

$map = BHP_Meta_Pixel::runtime_config()['map'];
bhp_cpx_assert($failures, 'D1 ⭐ adventure_kit_signup still maps to Lead — the founder\'s leads signal path, unchanged by this release', isset($map['adventure_kit_signup']) && 'Lead' === $map['adventure_kit_signup'][0]);
foreach (['parent_popup_success', 'teacher_popup_success', 'lead_signup_success'] as $ev) {
    bhp_cpx_assert($failures, sprintf('D2 %s still maps to Lead', $ev), isset($map[$ev]) && 'Lead' === $map[$ev][0]);
}
bhp_cpx_assert($failures, 'D3 ⭐ the one-Lead-per-page-load latch is still in place and still drops the second mapped Lead', false !== strpos($runtime, 'var leadFired = false;') && false !== strpos($runtime, 'if ( leadFired ) { return; }'));
bhp_cpx_assert($failures, 'D4 every Lead still carries an eventID for Conversions-API dedup', false !== strpos($runtime, 'eventId = newEventId();') && false !== strpos($runtime, 'payload.event_id = eventId;'));
bhp_cpx_assert($failures, 'D5 the base code still revokes BEFORE init — layer 1 is untouched, so a denied visitor is silent exactly as before', strpos(BHP_Meta_Pixel::base_code_html(), "fbq('consent','revoke')") < strpos(BHP_Meta_Pixel::base_code_html(), "fbq('init'"));
bhp_cpx_assert($failures, 'D6 the no-JS beacon is still NOT rendered by default', '' === BHP_Meta_Pixel::noscript_tag());
bhp_cpx_assert($failures, 'D7 the pixel ID was not changed by this release', '2050405642533821' === BHP_Meta_Pixel::PIXEL_ID);

/* Layer 2 on staging: the SDK URL must still be blank unless the bounded
 * override is set, so this suite running on staging cannot itself send a byte
 * to Meta. If this ever fails on staging, someone has left the override on. */
if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::is_staging()) {
    bhp_cpx_assert($failures, 'D8 ⭐ on staging the SDK URL is still empty unless bhp_meta_pixel_staging_mode is explicitly `live` — staging still reaches Meta with zero bytes by default', '' === BHP_Meta_Pixel::runtime_config()['sdk'] && false === BHP_Meta_Pixel::loads_sdk());
}

// ======================================================================
// E. THE OPT-OUT IS REACHABLE — the mechanism the posture depends on
// ======================================================================
echo "\n--- E. the opt-out is reachable ---\n";

$wpc = get_option('wpconsent_settings', []);
bhp_cpx_assert($failures, 'E1 WPConsent Free is still active — the preferences UI the "Privacy Choices" link opens is its modal', function_exists('wpconsent'));
bhp_cpx_assert($failures, 'E2 the banner template is still enabled server-side, so the preferences modal still renders for the footer link to open', is_array($wpc) && !empty($wpc['enable_consent_banner']));
bhp_cpx_assert($failures, 'E3 WPConsent\'s own Consent Mode DEFAULT emitter is still OFF — only BHP_Consent declares defaults', is_array($wpc) && empty($wpc['google_consent_mode']));

/* ⚠⚠ THE GUARD ON THE RISK THIS RELEASE ENLARGES. `default_allow` makes
 * WPConsent emit an ALL-GRANTED update for a visitor who has chosen nothing,
 * in EVERY region including the EEA. Before 1.19.312 that would have defeated
 * the ad_*-denied default. It is now WORSE, not better: the ad signals are the
 * granted default outside the EEA, so the only thing `default_allow` can still
 * break is the EUROPEAN posture — and it would break it silently, from
 * wp-admin, with no code change anywhere. */
bhp_cpx_assert($failures, 'E4 ⛔ WPConsent "default_allow" is OFF — if it were ON, a cookieless EEA first-load would receive an all-granted update and the European posture would be destroyed from wp-admin with no code change', is_array($wpc) && empty($wpc['default_allow']));

/* The footer link is the reachable route to the opt-out for a US visitor who
 * never sees a banner. If it is not on the page, the posture has no opt-out
 * and the whole basis of the ruling fails. */
$footer_marker_found = false;
foreach ([get_template_directory() . '/footer.php', get_template_directory() . '/inc/consent-banner-compact.php'] as $cand) {
    $src = (string) @file_get_contents($cand);
    if (false !== stripos($src, 'wpconsent-open-preferences') || false !== stripos($src, 'Privacy Choices')) {
        $footer_marker_found = true;
    }
}
bhp_cpx_assert($failures, 'E5 ⭐ a "Privacy Choices" / open-preferences control exists in theme source — the ONLY route to the opt-out for a non-EEA visitor, who no longer sees a banner (1.19.309)', $footer_marker_found);

// ======================================================================
// F. ORDERING — the region gate must publish before the pixel reads it
// ======================================================================
echo "\n--- F. ordering ---\n";

$prio_gate  = has_action('wp_head', 'bhp_consent_region_gate_script');
$prio_pixel = has_action('wp_head', ['BHP_Meta_Pixel', 'render_head']);
bhp_cpx_assert($failures, 'F1 the region gate is hooked to wp_head', false !== $prio_gate && 0 !== $prio_gate);
bhp_cpx_assert($failures, 'F2 the pixel head render is hooked to wp_head', false !== $prio_pixel && 0 !== $prio_pixel);
bhp_cpx_assert(
    $failures,
    'F3 ⭐ the region gate runs at a STRICTLY EARLIER wp_head priority than the pixel — window.bhpConsentRegion is therefore always defined by the time the runtime reads it, and this is a contract, not luck',
    is_int($prio_gate) && is_int($prio_pixel) && $prio_gate < $prio_pixel
);

// ======================================================================
// Restore
// ======================================================================
wp_set_current_user($original_user_id);
if (false !== $original_gtm_id) {
    update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, $original_gtm_id);
} else {
    delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
}
if (false !== $original_gate) {
    update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, $original_gate);
} else {
    delete_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED);
}
$_COOKIE = $original_cookies;
$_SERVER['HTTP_HOST'] = $original_host;

if ($failures) {
    WP_CLI::error(sprintf('%d consent/pixel extension test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All consent/pixel extension tests passed.');
}
