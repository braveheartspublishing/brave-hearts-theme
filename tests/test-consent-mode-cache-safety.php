<?php
/**
 * Consent Mode / page-cache safety suite (theme 1.19.178, `CYCLE143-GIM-51`).
 *
 * Run on staging (never production, except as a read-only post-deploy
 * verification) via:
 *   wp eval-file tests/test-consent-mode-cache-safety.php --user=1
 *
 * The defect this suite exists to prevent, observed live on production
 * 2026-08-04: the GTM snippet used to be gated per visitor on the
 * `bhp_consent_state` cookie, while SiteGround's page cache varies only on
 * Accept-Encoding. A cache entry primed by a consenting visitor was served
 * byte-identically to a cookie-less visitor, complete with
 * `analytics_storage:"granted"`; and a cookie-less entry was served to
 * consenting visitors, who were then never measured.
 *
 * THE INVARIANT: what the server prints must be byte-identical for every
 * consent-cookie state. Anything that varies per visitor belongs in the
 * browser, not in the HTML. Section 1 asserts that property directly by
 * capturing real printed output, not by reading the gating booleans.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_cms_test_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

/** Captures BHP_GTM_Loader head output for a simulated cookie state. */
function bhp_cms_render_head($cookie_value) {
    if (null === $cookie_value) {
        unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
    } else {
        $_COOKIE[BHP_Consent::COOKIE_NAME] = $cookie_value;
    }
    ob_start();
    BHP_GTM_Loader::render_head_snippet();
    return ob_get_clean();
}

function bhp_cms_render_noscript($cookie_value) {
    if (null === $cookie_value) {
        unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
    } else {
        $_COOKIE[BHP_Consent::COOKIE_NAME] = $cookie_value;
    }
    ob_start();
    BHP_GTM_Loader::render_noscript_snippet();
    return ob_get_clean();
}

// --- fixture capture / restore -----------------------------------------
// A real environment already has a real container ID configured. Two
// earlier suites wiped one by deleting the option unconditionally
// (2026-07-06) -- capture and restore exactly, whatever the fixtures do.
$original_host      = $_SERVER['HTTP_HOST'] ?? '';
$original_cookie    = $_COOKIE[BHP_Consent::COOKIE_NAME] ?? null;
$original_gtm_id    = get_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, false);
$original_gate      = get_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, false);
$original_user_id   = get_current_user_id();

$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com'; // production path: the one the cache actually serves
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-TESTID1');
update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);
wp_set_current_user(0); // a real logged-out visitor, not the wp-cli admin

// The four states a real request can arrive in.
$states = [
    'no cookie at all (first-time visitor)' => null,
    'accepted everything'                   => wp_json_encode([
        'analytics_storage'  => 'granted',
        'ad_storage'         => 'granted',
        'ad_user_data'       => 'granted',
        'ad_personalization' => 'granted',
    ]),
    'explicitly rejected'                   => wp_json_encode([
        'analytics_storage'  => 'denied',
        'ad_storage'         => 'denied',
        'ad_user_data'       => 'denied',
        'ad_personalization' => 'denied',
    ]),
    'malformed cookie'                      => 'not-json{{{',
];

// ---------------------------------------------------------------------
// 1. THE CACHE-SAFETY PROPERTY: byte-identical emission in every state
// ---------------------------------------------------------------------
$baseline_head     = bhp_cms_render_head(null);
$baseline_noscript = bhp_cms_render_noscript(null);

bhp_cms_test_assert($failures, 'Baseline: the head snippet actually renders something for a cookie-less visitor (the whole point -- GTM is no longer consent-gated server-side)', '' !== $baseline_head);

foreach ($states as $label => $cookie) {
    $head = bhp_cms_render_head($cookie);
    bhp_cms_test_assert(
        $failures,
        sprintf('Head snippet is BYTE-IDENTICAL to the cookie-less render for state: %s (md5 %s)', $label, substr(md5($head), 0, 8)),
        $head === $baseline_head
    );
    $noscript = bhp_cms_render_noscript($cookie);
    bhp_cms_test_assert(
        $failures,
        sprintf('Noscript snippet is BYTE-IDENTICAL to the cookie-less render for state: %s', $label),
        $noscript === $baseline_noscript
    );
    bhp_cms_test_assert(
        $failures,
        sprintf('should_render_analytics() returns the same value regardless of consent cookie for state: %s', $label),
        true === BHP_Analytics_Config::should_render_analytics()
    );
}

// ⭐ REWRITTEN 2026-08-27 (1.19.302, `CYCLE167-LD-CONSENT-GEO`).
//
// The two assertions that stood here until this release said, in effect,
// "the server never grants anything to anyone." Andrew Signore superseded
// that posture by his own word -- carrier item 310, 2026-08-27, "yeah lets
// just go with US Law" -- so the assertions are rewritten to the posture he
// ruled, rather than left to fail or quietly deleted.
//
// ⛔ WHAT IS **NOT** RELAXED, and this is the important half: the property
// this suite exists for is BYTE-IDENTICAL EMISSION ACROSS EVERY COOKIE
// STATE, and it is asserted unchanged in the loop above and again below.
// The 2026-08-04 defect was per-visitor SERVER variation in front of a
// page cache. Region scoping is resolved by Google in the BROWSER from the
// visitor's IP, so the server still emits one constant byte-string. The
// old assertions were a proxy for cache-safety; these are the real thing.
//
// Matched as the JSON pair a consent payload actually emits, not as the
// bare word: the bridge's client-side mapping legitimately contains the
// token 'granted' in `analytics ? 'granted' : 'denied'`, which is
// JavaScript the browser evaluates against the visitor's own cookie -- not
// a server assertion about anyone's consent. A bare-substring check fails
// on that and was the first version of this assertion (fixed 2026-08-05).

/** Extracts every gtag('consent','default',{...}) payload, in emitted order. */
function bhp_cms_default_payloads($head) {
    preg_match_all('/gtag\(\'consent\',\'default\',(\{[^}]*\})\)/', $head, $m);
    return array_map(static function ($json) {
        return json_decode($json, true);
    }, $m[1]);
}

foreach ($states as $label => $cookie) {
    $head     = bhp_cms_render_head($cookie);
    $payloads = bhp_cms_default_payloads($head);

    bhp_cms_test_assert(
        $failures,
        sprintf('Exactly TWO consent default payloads are emitted (EEA-scoped + catch-all) for state: %s', $label),
        2 === count($payloads)
    );

    if (2 !== count($payloads)) {
        continue;
    }
    list($eea, $rest) = $payloads;

    // --- payload 1: EEA+UK, the unchanged strict posture -----------------
    bhp_cms_test_assert(
        $failures,
        sprintf('EEA payload is region-scoped and the region list is non-empty for state: %s', $label),
        isset($eea['region']) && is_array($eea['region']) && count($eea['region']) > 0
    );
    bhp_cms_test_assert(
        $failures,
        sprintf('EEA payload grants NOTHING -- every signal denied, exactly as before 1.19.302, for state: %s', $label),
        !in_array('granted', array_intersect_key($eea, array_flip(BHP_Consent::SIGNALS)), true)
    );

    // --- payload 2: everywhere else, the posture item 310 ruled ----------
    bhp_cms_test_assert(
        $failures,
        sprintf('Catch-all payload carries NO region key (it must apply everywhere the EEA list does not) for state: %s', $label),
        !isset($rest['region'])
    );
    bhp_cms_test_assert(
        $failures,
        sprintf('Catch-all payload GRANTS analytics_storage -- the measurement item 310 asked for, for state: %s', $label),
        isset($rest['analytics_storage']) && 'granted' === $rest['analytics_storage']
    );
    foreach (['ad_storage', 'ad_user_data', 'ad_personalization'] as $ad_signal) {
        bhp_cms_test_assert(
            $failures,
            sprintf('Catch-all payload leaves %s DENIED -- ad signals were NOT broadened by this release, for state: %s', $ad_signal, $label),
            isset($rest[$ad_signal]) && 'denied' === $rest[$ad_signal]
        );
    }
}

// ---------------------------------------------------------------------
// 2. DEFAULTS ARE DENIED, ALL FOUR, WITH wait_for_update
// ---------------------------------------------------------------------
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
ob_start();
BHP_Consent::render_default_snippet();
$defaults = ob_get_clean();

bhp_cms_test_assert($failures, 'Defaults snippet emits a gtag consent default call', false !== strpos($defaults, "gtag('consent','default'"));

// ⭐ REWRITTEN 2026-08-27 (item 310). The loop that stood here asserted
// `"<signal>":"denied"` against the WHOLE snippet, which after 1.19.302
// would have kept passing on the EEA payload alone while saying nothing
// about the catch-all -- a test that passes for the wrong reason is worse
// than one that fails. Each payload is now asserted separately, by object.
foreach (BHP_Consent::SIGNALS as $signal) {
    bhp_cms_test_assert($failures, sprintf('EEA default for %s is denied (posture unchanged from 1.19.301)', $signal), 'denied' === BHP_Consent::eea_default_signals()[$signal]);
}
bhp_cms_test_assert($failures, 'Catch-all default grants analytics_storage', 'granted' === BHP_Consent::measured_default_signals()['analytics_storage']);
foreach (['ad_storage', 'ad_user_data', 'ad_personalization'] as $ad_signal) {
    bhp_cms_test_assert($failures, sprintf('Catch-all default leaves %s denied', $ad_signal), 'denied' === BHP_Consent::measured_default_signals()[$ad_signal]);
}
bhp_cms_test_assert($failures, 'BOTH Consent Mode defaults carry wait_for_update:500 so the client-side update has a window to arrive', 2 === substr_count($defaults, '"wait_for_update":500'));
bhp_cms_test_assert($failures, 'default_signals() still covers exactly the four Consent Mode v2 signals plus wait_for_update, nothing else', 5 === count(BHP_Consent::default_signals()));
bhp_cms_test_assert($failures, 'eea_default_signals() is default_signals() plus exactly one key: region', 6 === count(BHP_Consent::eea_default_signals()) && isset(BHP_Consent::eea_default_signals()['region']));
bhp_cms_test_assert($failures, 'measured_default_signals() covers exactly the four signals plus wait_for_update, nothing else', 5 === count(BHP_Consent::measured_default_signals()));

// Even with a granting cookie present, the DEFAULTS are unmoved.
$_COOKIE[BHP_Consent::COOKIE_NAME] = $states['accepted everything'];
ob_start();
BHP_Consent::render_default_snippet();
$defaults_with_consent = ob_get_clean();
bhp_cms_test_assert($failures, 'Defaults snippet ignores a granting consent cookie entirely (it is a constant, not a read of state)', $defaults_with_consent === $defaults);
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);

// ---------------------------------------------------------------------
// 3. DOM ORDER: defaults -> client sync -> container loader
// ---------------------------------------------------------------------
$head = bhp_cms_render_head(null);
$pos_default  = strpos($head, "gtag('consent','default'");
$pos_sync     = strpos($head, 'wpconsent_consent_saved');
$pos_loader   = strpos($head, 'googletagmanager.com/gtm.js');

bhp_cms_test_assert($failures, 'The consent DEFAULTS snippet precedes the GTM loader script in DOM order', false !== $pos_default && false !== $pos_loader && $pos_default < $pos_loader);
bhp_cms_test_assert($failures, 'The client-side consent SYNC precedes the GTM loader script too (a returning visitor is corrected before the container initialises)', false !== $pos_sync && $pos_sync < $pos_loader);
bhp_cms_test_assert($failures, 'The defaults precede the sync (denied first, then the visitor\'s own choice raises it)', $pos_default < $pos_sync);
bhp_cms_test_assert($failures, 'Exactly one gtm.js loader is emitted', 1 === substr_count($head, 'googletagmanager.com/gtm.js'));
// ⭐ REWRITTEN 2026-08-27 (item 310): one -> two. The region-scoped EEA
// default and the unscoped catch-all. A THIRD would mean something else
// started emitting consent commands, which is the failure this counts for.
bhp_cms_test_assert($failures, 'Exactly TWO consent default calls are emitted -- the EEA-scoped one and the catch-all, and nothing else', 2 === substr_count($head, "gtag('consent','default'"));
bhp_cms_test_assert($failures, 'BOTH consent defaults precede the GTM loader in DOM order', strrpos($head, "gtag('consent','default'") < $pos_loader);

// ---------------------------------------------------------------------
// 4. THE STORED-COOKIE UPDATE PATH (the first-visit gap CYCLE143-GIM-51
//    proved broken). Asserted against the JS that is actually printed
//    into the page, not against the source file on disk.
// ---------------------------------------------------------------------
bhp_cms_test_assert($failures, 'The emitted bridge reads the CMP cookie (wpconsent_preferences) on load', false !== strpos($head, 'wpconsent_preferences'));
bhp_cms_test_assert($failures, 'The emitted bridge falls back to its own mirror cookie (bhp_consent_state) on load', false !== strpos($head, 'bhp_consent_state'));
bhp_cms_test_assert($failures, 'The emitted bridge sends a Consent Mode UPDATE (this is the only thing that can ever grant)', false !== strpos($head, "'consent', 'update'"));
bhp_cms_test_assert($failures, 'The emitted bridge still listens for a live banner acceptance (wpconsent_consent_saved)', false !== strpos($head, "addEventListener( 'wpconsent_consent_saved'"));
bhp_cms_test_assert($failures, 'The emitted bridge calls storedChoice() on load, so a returning acceptor is corrected without a fresh banner interaction', false !== strpos($head, 'var stored = storedChoice();'));
bhp_cms_test_assert($failures, 'The emitted bridge normalises unknown stored values to denied, never to granted', false !== strpos($head, "( raw && raw[ key ] === 'granted' ) ? 'granted' : 'denied'"));
bhp_cms_test_assert($failures, 'The emitted bridge self-guards against double initialisation', false !== strpos($head, 'if ( window.bhpConsentBridge ) { return; }'));

// ---------------------------------------------------------------------
// 5. THE SITE-WIDE GATES STILL HOLD (they are cache-safe: options and
//    logged-in traffic, never a per-visitor cookie)
// ---------------------------------------------------------------------
delete_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED);
bhp_cms_test_assert($failures, 'Production consent-decision gate still blocks everything when unapproved -- the site-wide business switch is UNCHANGED by this release', '' === bhp_cms_render_head(null));
bhp_cms_test_assert($failures, 'consent_gate_reason() names the unapproved production gate as the blocker', false !== strpos((string) BHP_Analytics_Config::consent_gate_reason(), 'consent decision has not been approved'));
update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);

delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
bhp_cms_test_assert($failures, 'With no container ID configured, nothing prints at all (no placeholder, no bare consent snippet)', '' === bhp_cms_render_head(null));
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-TESTID1');

$_SERVER['HTTP_HOST'] = 'staging2.braveheartspublishing.com';
$staging_override = get_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, false);
delete_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE);
bhp_cms_test_assert($failures, 'Staging still prints nothing without the explicit staging override (production reporting stays uncontaminated)', '' === bhp_cms_render_head(null));
if (false !== $staging_override) {
    update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, $staging_override);
}
$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';

wp_set_current_user($original_user_id); // the real wp-cli administrator
bhp_cms_test_assert($failures, 'Administrator traffic is still excluded entirely, even with everything configured and approved', '' === bhp_cms_render_head($states['accepted everything']));
wp_set_current_user(0);

// ---------------------------------------------------------------------
// 6. FUNNEL ISOLATION UNTOUCHED (source-level guard, scoped to this
//    release: the analytics/consent files must not carry any funnel
//    prefix, and the two popup templates must still carry their own)
// ---------------------------------------------------------------------
$theme_dir = get_template_directory();
$analytics_files = [
    '/inc/class-bhp-consent.php',
    '/inc/class-bhp-gtm-loader.php',
    '/inc/class-bhp-analytics-config.php',
    '/inc/class-bhp-wpconsent-bridge.php',
];
$funnel_prefixes = ['bhp_parent_popup', 'bhp_mariana_popup', 'parent_popup', 'teacher_popup'];
$leaked = [];
foreach ($analytics_files as $rel) {
    $contents = (string) @file_get_contents($theme_dir . $rel);
    foreach ($funnel_prefixes as $prefix) {
        if (false !== strpos($contents, $prefix)) {
            $leaked[] = $rel . ':' . $prefix;
        }
    }
}
bhp_cms_test_assert($failures, 'No funnel storage/analytics prefix appears in any file this release touched (funnel isolation cannot have been affected)', [] === $leaked);

$parent_popup  = (string) @file_get_contents($theme_dir . '/template-parts/acquisition/parent-popup.php');
$teacher_popup = (string) @file_get_contents($theme_dir . '/template-parts/acquisition/mariana-popup.php');
if ('' === $teacher_popup) {
    $teacher_popup = (string) @file_get_contents($theme_dir . '/template-parts/acquisition/teacher-popup.php');
}
bhp_cms_test_assert($failures, 'Parent funnel still declares its own storage prefix (bhp_parent_popup)', false !== strpos($parent_popup, 'bhp_parent_popup'));
bhp_cms_test_assert($failures, 'Parent funnel still declares its own event prefix (parent_popup)', false !== strpos($parent_popup, 'parent_popup'));
bhp_cms_test_assert($failures, 'Teacher funnel keeps its distinct storage prefix (bhp_mariana_popup), separate from the parent funnel', '' !== $teacher_popup && false !== strpos($teacher_popup, 'bhp_mariana_popup'));

// ---------------------------------------------------------------------
// Restore everything this suite touched
// ---------------------------------------------------------------------
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

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d consent-mode cache-safety test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All consent-mode cache-safety tests passed.');
}
