<?php
/**
 * Region-aware Consent Mode v2 suite (theme 1.19.302,
 * `CYCLE167-LD-CONSENT-GEO`).
 *
 * Run on staging (never production, except as a read-only post-deploy
 * verification) via:
 *   wp eval-file tests/test-cycle167-consent-geo.php --user=1
 *
 * WHAT THIS SUITE CAN AND CANNOT PROVE -- read this before quoting a pass.
 *
 *   ✅ CAN, and does, in PHP: that the server emits exactly the two default
 *      payloads Google's region pattern requires, with the right signals in
 *      each, the right region list, no region key on the catch-all, correct
 *      DOM ordering, and byte-identical output across every cookie state.
 *
 *   ✅ CAN, and does, by static analysis of the emitted bridge JS: that the
 *      lowering paths exist -- a rejection maps every signal to 'denied',
 *      a stored choice outranks GPC, and GPC lowers but never raises.
 *
 *   ⛔ CANNOT, from PHP: actually place the request in a region. `region` is
 *      resolved by Google FROM THE VISITOR'S IP, in the browser, at
 *      tag-fire time. There is no server-side geo input to stub, because
 *      the server performs no geo lookup -- that is the design, and it is
 *      what keeps the page cacheable (`CYCLE143-GIM-51`).
 *
 *      ⭐ THE HONEST CONSEQUENCE: region BEHAVIOUR is verified in a real
 *      browser against the live staging page by replaying the emitted
 *      payloads through Google's own precedence rule, and that evidence is
 *      recorded in the QA log -- not here. A PHP suite asserting "the EEA
 *      visitor stays dark" would be asserting something it cannot observe,
 *      and that is a fabricated check. This suite asserts the CONTRACT; the
 *      browser observes the BEHAVIOUR.
 *
 * Authority: Andrew Signore, carrier item 310, 2026-08-27 -- "Omg - yeah
 * lets just go with US Law - what are we doing". RELAYED via the record,
 * not witnessed by this suite's author.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_geo_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

// --- fixture capture / restore ------------------------------------------
$original_host    = $_SERVER['HTTP_HOST'] ?? '';
$original_cookie  = $_COOKIE[BHP_Consent::COOKIE_NAME] ?? null;
$original_gtm_id  = get_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, false);
$original_gate    = get_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, false);
$original_user_id = get_current_user_id();

$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com'; // the path the cache actually serves
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-TESTID1');
update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);
wp_set_current_user(0);

function bhp_geo_head($cookie_value = null) {
    if (null === $cookie_value) {
        unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
    } else {
        $_COOKIE[BHP_Consent::COOKIE_NAME] = $cookie_value;
    }
    ob_start();
    BHP_GTM_Loader::render_head_snippet();
    return ob_get_clean();
}

$head = bhp_geo_head(null);

// ------------------------------------------------------------------
// 1. THE REGION LIST
// ------------------------------------------------------------------
$regions = BHP_Consent::EEA_UK_REGIONS;

bhp_geo_assert($failures, 'EEA_UK_REGIONS contains 31 entries (27 EU + IS/LI/NO + GB)', 31 === count($regions));
bhp_geo_assert($failures, 'EEA_UK_REGIONS has no duplicate entries', count($regions) === count(array_unique($regions)));
foreach ($regions as $code) {
    if (1 !== preg_match('/^[A-Z]{2}$/', $code)) {
        bhp_geo_assert($failures, sprintf('Region code "%s" is a valid ISO 3166-1 alpha-2 code', $code), false);
    }
}

// A representative spread rather than a re-listing of the constant: a test
// that just repeats the array under test proves nothing.
foreach (['DE', 'FR', 'IE', 'ES', 'IT', 'PL', 'NL', 'SE', 'NO', 'IS', 'LI', 'GB'] as $must) {
    bhp_geo_assert($failures, sprintf('%s is inside the strict (EEA+UK) region list', $must), in_array($must, $regions, true));
}
// The founder's ruling is that these are measured by default. If one of
// them ever appears in the strict list, the ruling has been reversed by
// accident and this is where that shows up.
foreach (['US', 'CA', 'AU', 'NZ', 'JP', 'MX', 'IN'] as $must_not) {
    bhp_geo_assert($failures, sprintf('%s is NOT in the strict list -- it is measured by default, per item 310', $must_not), !in_array($must_not, $regions, true));
}

// ------------------------------------------------------------------
// 2. THE TWO PAYLOADS, AS ACTUALLY EMITTED
// ------------------------------------------------------------------
preg_match_all('/gtag\(\'consent\',\'default\',(\{[^}]*\})\)/', $head, $m);
bhp_geo_assert($failures, 'Exactly two consent default calls are emitted', 2 === count($m[1]));

if (2 === count($m[1])) {
    $eea  = json_decode($m[1][0], true);
    $rest = json_decode($m[1][1], true);

    bhp_geo_assert($failures, 'Both emitted default payloads are valid JSON', is_array($eea) && is_array($rest));

    // -- the strict one ------------------------------------------------
    bhp_geo_assert($failures, 'The region-scoped payload is emitted FIRST, matching Google\'s documented example', isset($eea['region']));
    bhp_geo_assert($failures, 'The emitted region list matches EEA_UK_REGIONS exactly', isset($eea['region']) && $eea['region'] === array_values($regions));
    foreach (BHP_Consent::SIGNALS as $signal) {
        bhp_geo_assert($failures, sprintf('EEA payload: %s is denied -- the European posture is byte-for-byte what it was at 1.19.301', $signal), isset($eea[$signal]) && 'denied' === $eea[$signal]);
    }
    bhp_geo_assert($failures, 'EEA payload carries wait_for_update:500', isset($eea['wait_for_update']) && 500 === $eea['wait_for_update']);

    // -- the measured one ----------------------------------------------
    bhp_geo_assert($failures, 'The catch-all payload carries NO region key, so it applies everywhere the EEA list does not', !isset($rest['region']));
    bhp_geo_assert($failures, 'Catch-all payload: analytics_storage is GRANTED (this is the whole release)', isset($rest['analytics_storage']) && 'granted' === $rest['analytics_storage']);

    /* ⭐⭐ 1.19.312 (`CYCLE167-LD-CONSENT-PIXEL-EXT`) — THE THREE ASSERTIONS
     * REPLACED HERE ARE THE ONES THE FOUNDER'S RULING REVERSES, AND THEY ARE
     * QUOTED RATHER THAN DELETED so that a reader diffing this suite can see
     * a deliberate reversal instead of guessing at a weakened test:
     *
     *   > foreach (['ad_storage', 'ad_user_data', 'ad_personalization'] as $ad_signal) {
     *   >     bhp_geo_assert($failures, sprintf('Catch-all payload: %s is DENIED
     *   >         -- ad signals were not broadened anywhere by this release',
     *   >         $ad_signal), isset($rest[$ad_signal]) && 'denied' === $rest[$ad_signal]);
     *   > }
     *   > bhp_geo_assert($failures, 'NO payload grants any ad_* signal in any
     *   >     region -- asserted across both, so a future edit to either is caught', ...);
     *
     * Authority: Andrew Signore, carrier `^349. FOUNDER` item 4 — "I guess we
     * extend it", after `chief-of-staff` clarified the extension reaches the
     * ad/marketing pixel. ⛔ RELAYED, not witnessed by this suite's author.
     *
     * ⭐ THE REPLACEMENT IS NOT WEAKER, AND THAT IS THE POINT: the old
     * cross-payload invariant is inverted rather than dropped. It now asserts
     * that the EEA payload grants NOTHING while the catch-all grants
     * EVERYTHING — so a future edit that leaks an ad signal into the European
     * default still fails here, which is the failure that actually matters. */
    foreach (['ad_storage', 'ad_user_data', 'ad_personalization'] as $ad_signal) {
        bhp_geo_assert($failures, sprintf('Catch-all payload: %s is GRANTED -- the US-law posture extended to advertising (item 4)', $ad_signal), isset($rest[$ad_signal]) && 'granted' === $rest[$ad_signal]);
    }
    bhp_geo_assert($failures, 'Catch-all payload carries wait_for_update:500', isset($rest['wait_for_update']) && 500 === $rest['wait_for_update']);

    // -- the invariant that spans both ----------------------------------
    bhp_geo_assert(
        $failures,
        'The EEA payload grants NO signal while the catch-all grants EVERY signal -- asserted across both, so an ad signal leaking into the European default is caught here',
        'denied' === $eea['analytics_storage'] && 'granted' === $rest['analytics_storage']
            && 'denied' === $eea['ad_storage'] && 'granted' === $rest['ad_storage']
            && 'denied' === $eea['ad_user_data'] && 'granted' === $rest['ad_user_data']
            && 'denied' === $eea['ad_personalization'] && 'granted' === $rest['ad_personalization']
    );
    bhp_geo_assert(
        $failures,
        'The two payloads are not accidentally identical -- if a future edit ever makes the EEA branch equal the catch-all, the European posture has been silently destroyed and this catches it',
        $eea !== $rest
    );
}

// ------------------------------------------------------------------
// 3. CACHE SAFETY SURVIVES THE CHANGE (the CYCLE143-GIM-51 invariant)
// ------------------------------------------------------------------
$cookie_states = [
    'no cookie'  => null,
    'accepted'   => wp_json_encode(['analytics_storage' => 'granted', 'ad_storage' => 'granted', 'ad_user_data' => 'granted', 'ad_personalization' => 'granted']),
    'rejected'   => wp_json_encode(['analytics_storage' => 'denied', 'ad_storage' => 'denied', 'ad_user_data' => 'denied', 'ad_personalization' => 'denied']),
    'malformed'  => 'not-json{{{',
];
$baseline = bhp_geo_head(null);
foreach ($cookie_states as $label => $cookie) {
    bhp_geo_assert($failures, sprintf('Emission is byte-identical for cookie state "%s" -- region scoping did NOT reintroduce per-visitor server variation', $label), bhp_geo_head($cookie) === $baseline);
}
bhp_geo_assert($failures, 'The server performs no geo lookup: no geo header is read anywhere in the consent class', 0 === preg_match('/CF-IPCountry|GEOIP|HTTP_X_COUNTRY|geoip_country/i', (string) @file_get_contents(get_template_directory() . '/inc/class-bhp-consent.php')));

// ------------------------------------------------------------------
// 4. ORDERING -- defaults, then the sync, then the container
// ------------------------------------------------------------------
$first_default = strpos($head, "gtag('consent','default'");
$last_default  = strrpos($head, "gtag('consent','default'");
$pos_sync      = strpos($head, 'wpconsent_consent_saved');
$pos_loader    = strpos($head, 'googletagmanager.com/gtm.js');

bhp_geo_assert($failures, 'Both defaults precede the client-side sync', false !== $last_default && false !== $pos_sync && $last_default < $pos_sync);
bhp_geo_assert($failures, 'The client-side sync precedes the container loader', $pos_sync < $pos_loader);
bhp_geo_assert($failures, 'Both defaults precede the container loader', $first_default < $pos_loader && $last_default < $pos_loader);

// ------------------------------------------------------------------
// 5. THE OPT-OUT PATH -- the half that makes a notice a real notice
//
// Static assertions against the JS ACTUALLY PRINTED INTO THE PAGE, not
// against the source file on disk. Behavioural proof is the browser's job
// and is recorded in the QA log; these assert the mechanism is present.
// ------------------------------------------------------------------
bhp_geo_assert($failures, 'The emitted bridge maps a rejected statistics category to analytics_storage denied (the lowering path)', false !== strpos($head, "analytics_storage: analytics ? 'granted' : 'denied'"));
bhp_geo_assert($failures, 'The emitted bridge still sends a Consent Mode UPDATE on a live banner choice', false !== strpos($head, "addEventListener( 'wpconsent_consent_saved'"));
bhp_geo_assert($failures, 'The emitted bridge reads GPC (navigator.globalPrivacyControl)', false !== strpos($head, 'globalPrivacyControl'));
bhp_geo_assert($failures, 'GPC is evaluated ONLY when there is no stored choice -- an explicit choice outranks the browser signal', false !== strpos($head, '} else if ( gpcActive() ) {'));
bhp_geo_assert($failures, 'The GPC branch sends an all-denied update, so it can only ever LOWER a signal', false !== strpos($head, 'updateGtag( normaliseSignals( null ) )'));
bhp_geo_assert($failures, 'The GPC branch does NOT write the mirror cookie (a browser setting is not a recorded choice)', false === strpos($head, 'writeBhpCookie( normaliseSignals( null ) )'));
bhp_geo_assert($failures, 'The emitted bridge still normalises unknown stored values to denied, never to granted', false !== strpos($head, "( raw && raw[ key ] === 'granted' ) ? 'granted' : 'denied'"));

// ------------------------------------------------------------------
// 6. THE BANNER IS STILL THERE, AND WPCONSENT WAS NOT DISABLED
// ------------------------------------------------------------------
$wpconsent_settings = get_option('wpconsent_settings', []);
bhp_geo_assert($failures, 'WPConsent Free is still active (the plugin was never removed -- the brief forbids it)', function_exists('wpconsent'));
bhp_geo_assert($failures, 'WPConsent\'s banner is still enabled -- the banner shows to EVERY visitor, US included, as notice + opt-out', is_array($wpconsent_settings) && !empty($wpconsent_settings['enable_consent_banner']));
bhp_geo_assert($failures, 'WPConsent\'s own Google-consent-mode DEFAULT emitter is still OFF, so only BHP_Consent declares consent defaults', is_array($wpconsent_settings) && empty($wpconsent_settings['google_consent_mode']));

// ⚠⚠ THE GUARD ON THE RISK THIS RELEASE CREATES. See the long note in
// class-bhp-wpconsent-bridge.php. WPConsent's `default_allow` makes the
// plugin emit an ALL-GRANTED consent update -- ad signals included -- for a
// visitor who has chosen nothing, on first load, in EVERY region including
// the EEA. That would silently defeat both this release's ad_*-denied
// default AND the strict European posture, and it is a wp-admin settings
// change that no amount of theme code can prevent. So it is asserted here:
// if anyone ever turns it on, this suite fails and says why.
bhp_geo_assert($failures, 'WPConsent "default_allow" is OFF -- if it were ON, a cookieless first-load would emit an all-granted update and override the ad_*-denied default in every region, EEA included', is_array($wpconsent_settings) && empty($wpconsent_settings['default_allow']));
bhp_geo_assert($failures, 'WPConsent script blocking is not disabled -- the other half of the same condition (see default_allow above)', is_array($wpconsent_settings) && !isset($wpconsent_settings['enable_script_blocking']) || !empty($wpconsent_settings['enable_script_blocking']));

// ------------------------------------------------------------------
// 7. THE SITE-WIDE GATES ARE UNTOUCHED BY THIS RELEASE
// ------------------------------------------------------------------
delete_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED);
bhp_geo_assert($failures, 'The production consent-decision gate still blocks everything when unapproved -- geo-awareness did not weaken it', '' === bhp_geo_head(null));
update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);

wp_set_current_user($original_user_id);
bhp_geo_assert($failures, 'Administrator traffic is still excluded entirely, in every region', '' === bhp_geo_head(null));
wp_set_current_user(0);

// ------------------------------------------------------------------
// Restore
// ------------------------------------------------------------------
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
if (null !== $original_cookie) {
    $_COOKIE[BHP_Consent::COOKIE_NAME] = $original_cookie;
} else {
    unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
}
$_SERVER['HTTP_HOST'] = $original_host;

if ($failures) {
    WP_CLI::error(sprintf('%d region-aware consent test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All region-aware consent tests passed.');
}
