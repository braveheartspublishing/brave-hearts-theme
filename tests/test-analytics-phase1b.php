<?php
/**
 * Analytics Phase 1B — theme-level test suite.
 *
 * Covers BHP_Analytics_Config, BHP_Consent, and BHP_UTM_Attribution.
 * Same convention as test-kirkus-component.php: wp-cli-driven, no
 * PHPUnit, exercises real classes against the real WordPress bootstrap.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-analytics-phase1b.php --user=1
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_a11b_test_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

// This suite exercises OPTION_GTM_CONTAINER_ID/OPTION_GA4_MEASUREMENT_ID
// with throwaway fixture values, then deletes them at the end -- but a
// real environment (staging or production) may already have real IDs
// configured. Deleting those unconditionally would silently wipe live
// configuration every time this suite runs (this happened once, 2026-07-06:
// running this file wiped a real staging GTM container ID and GA4
// Measurement ID that had just been configured). Capture whatever is
// already there now and restore it exactly at the end, regardless of
// what the test fixtures do in between.
$bhp_a11b_original_gtm_id = get_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, false);
$bhp_a11b_original_ga4_id = get_option(BHP_Analytics_Config::OPTION_GA4_MEASUREMENT_ID, false);

// ---------------------------------------------------------------------
// 1. BHP_Analytics_Config: GTM ID validation never trusts a malformed value
// ---------------------------------------------------------------------
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-ABC1234');
bhp_a11b_test_assert($failures, 'A well-formed GTM-XXXXXXX ID is accepted', 'GTM-ABC1234' === BHP_Analytics_Config::gtm_container_id());

update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'not-a-real-id');
bhp_a11b_test_assert($failures, 'A malformed GTM ID is rejected (never printed as-is)', '' === BHP_Analytics_Config::gtm_container_id());

update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, '');
bhp_a11b_test_assert($failures, 'An empty GTM ID option returns empty string, never a placeholder', '' === BHP_Analytics_Config::gtm_container_id());

delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
bhp_a11b_test_assert($failures, 'A never-set GTM ID option returns empty string, not a fatal error', '' === BHP_Analytics_Config::gtm_container_id());

// ---------------------------------------------------------------------
// 2. BHP_Analytics_Config: GA4 measurement ID validation
// ---------------------------------------------------------------------
update_option(BHP_Analytics_Config::OPTION_GA4_MEASUREMENT_ID, 'G-ABCDE12345');
bhp_a11b_test_assert($failures, 'A well-formed G-XXXXXXXXXX ID is accepted', 'G-ABCDE12345' === BHP_Analytics_Config::ga4_measurement_id());
update_option(BHP_Analytics_Config::OPTION_GA4_MEASUREMENT_ID, 'UA-12345-1');
bhp_a11b_test_assert($failures, 'An old Universal Analytics ID (UA-...) is rejected, never mistaken for GA4', '' === BHP_Analytics_Config::ga4_measurement_id());
delete_option(BHP_Analytics_Config::OPTION_GA4_MEASUREMENT_ID);

// ---------------------------------------------------------------------
// 3. BHP_Analytics_Config: staging detection is hostname-based, not a
//    guessable constant
// ---------------------------------------------------------------------
$original_host = $_SERVER['HTTP_HOST'] ?? '';
$_SERVER['HTTP_HOST'] = 'staging2.braveheartspublishing.com';
bhp_a11b_test_assert($failures, 'staging2.braveheartspublishing.com is detected as staging', true === BHP_Analytics_Config::is_staging());
$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';
bhp_a11b_test_assert($failures, 'braveheartspublishing.com is NOT detected as staging', false === BHP_Analytics_Config::is_staging());
$_SERVER['HTTP_HOST'] = 'some-random-host.example.com';
bhp_a11b_test_assert($failures, 'An unrelated hostname is treated as production, not staging (fail-safe default)', false === BHP_Analytics_Config::is_staging());
$_SERVER['HTTP_HOST'] = $original_host;

// ---------------------------------------------------------------------
// 4. BHP_Analytics_Config: staging never sends real tracking unless
//    explicitly overridden (Phase 3 "staging must never contaminate
//    production" requirement)
// ---------------------------------------------------------------------
// ⚠ 2026-08-05: this section used to DELETE the staging override
// unconditionally and never put it back, so running the suite silently
// turned off a live staging QA session's tracking -- the same defect class
// this file's own header warns about for the container ID, found when a
// verified-rendering staging page stopped emitting GTM immediately after a
// test run (`CYCLE144-LD-62`). Capture and restore, like every other
// fixture here.
$bhp_a11b_original_staging_override = get_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, false);
$_SERVER['HTTP_HOST'] = 'staging2.braveheartspublishing.com';
delete_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE);
bhp_a11b_test_assert($failures, 'Staging tracking is DISABLED by default with no override set', false === BHP_Analytics_Config::is_tracking_enabled());
update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, true);
bhp_a11b_test_assert($failures, 'Staging tracking is enabled once the explicit override option is turned on', true === BHP_Analytics_Config::is_tracking_enabled());
if (false !== $bhp_a11b_original_staging_override) {
    update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, $bhp_a11b_original_staging_override);
} else {
    delete_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE);
}
$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';
bhp_a11b_test_assert($failures, 'Production tracking is enabled by default regardless of the staging-only override option', true === BHP_Analytics_Config::is_tracking_enabled());
$_SERVER['HTTP_HOST'] = $original_host;

// ---------------------------------------------------------------------
// 5. BHP_Consent: default-denied unless a real consent cookie exists
// ---------------------------------------------------------------------
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com'; // production, no staging override applies
$state = BHP_Consent::current_state();
bhp_a11b_test_assert($failures, 'With no consent cookie on production, analytics_storage defaults to denied', 'denied' === $state['analytics_storage']);
bhp_a11b_test_assert($failures, 'With no consent cookie on production, ad_storage defaults to denied', 'denied' === $state['ad_storage']);
bhp_a11b_test_assert($failures, 'With no consent cookie on production, ad_personalization defaults to denied', 'denied' === $state['ad_personalization']);

// Note: real $_COOKIE values arrive already URL-decoded (PHP decodes the
// raw Cookie header automatically when populating the superglobal, same
// as $_GET) -- so a test simulating this must assign the plain JSON
// string directly, never a URL-encoded one, or it doesn't match what the
// class actually receives in production.
$_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode(['analytics_storage' => 'granted', 'ad_storage' => 'granted']);
$state = BHP_Consent::current_state();
bhp_a11b_test_assert($failures, 'A real consent cookie granting analytics_storage is honored', 'granted' === $state['analytics_storage']);
bhp_a11b_test_assert($failures, 'Granting analytics consent does NOT imply ad_user_data consent (Phase 12: never infer advertising consent from analytics consent)', 'denied' === $state['ad_user_data']);
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);

$_SERVER['HTTP_HOST'] = 'staging2.braveheartspublishing.com';
update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, true);
$state = BHP_Consent::current_state();
bhp_a11b_test_assert($failures, 'Staging validation override grants analytics_storage for internal QA purposes only', 'granted' === $state['analytics_storage']);
bhp_a11b_test_assert($failures, 'Staging validation override does NOT grant ad_storage (no advertising consent needed to validate GA4 events)', 'denied' === $state['ad_storage']);
// Restore, do not delete -- see the note in section 4 (`CYCLE144-LD-62`).
if (false !== $bhp_a11b_original_staging_override) {
    update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, $bhp_a11b_original_staging_override);
} else {
    delete_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE);
}
$_SERVER['HTTP_HOST'] = $original_host;

// ---------------------------------------------------------------------
// 6. BHP_UTM_Attribution: cookie parsing sanitizes and caps field length,
//    never stores an oversized or malformed value on an order
// ---------------------------------------------------------------------
$reflection = new ReflectionClass('BHP_UTM_Attribution');
$read_cookie = $reflection->getMethod('read_cookie');
$read_cookie->setAccessible(true);

$_COOKIE['bhp_attr_test'] = wp_json_encode([
    'utm_source'   => str_repeat('a', 500), // oversized -- must be capped
    'utm_medium'   => 'cpc',
    'landing_page' => '/shop/',
    'timestamp'    => '2026-07-06T00:00:00.000Z',
]);
$result = $read_cookie->invoke(null, 'bhp_attr_test');
bhp_a11b_test_assert($failures, 'An oversized cookie field value is capped at 200 characters, never stored raw', is_array($result) && strlen($result['utm_source']) <= 200);
bhp_a11b_test_assert($failures, 'A normal field value passes through correctly', 'cpc' === $result['utm_medium']);
unset($_COOKIE['bhp_attr_test']);

$read_cookie_missing = $reflection->getMethod('read_cookie');
$read_cookie_missing->setAccessible(true);
bhp_a11b_test_assert($failures, 'A missing cookie returns null, never a fatal error or empty-but-present array', null === $read_cookie_missing->invoke(null, 'bhp_attr_nonexistent'));

$_COOKIE['bhp_attr_malformed'] = 'not-valid-json{{{';
bhp_a11b_test_assert($failures, 'A malformed (non-JSON) cookie value returns null rather than throwing', null === $read_cookie->invoke(null, 'bhp_attr_malformed'));
unset($_COOKIE['bhp_attr_malformed']);

// ---------------------------------------------------------------------
// 7. Production consent-decision readiness gate (Phase 1B correction
//    pass, 2026-07-06): a real GTM container ID alone must NEVER be
//    sufficient to activate analytics in production -- the separate
//    bhp_consent_decision_approved gate must also be explicitly on.
// ---------------------------------------------------------------------
$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com'; // production
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-GATETEST');
delete_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED);
$original_user_id_gate_test = get_current_user_id();
wp_set_current_user(0); // ensure we're testing as a real visitor, not the wp-cli admin user (see test-gtm-loader.php's note on this)

bhp_a11b_test_assert($failures, 'consent_decision_approved() defaults to false when the option has never been set', false === BHP_Analytics_Config::consent_decision_approved());
bhp_a11b_test_assert(
    $failures,
    'A real GTM container ID configured on production, with the consent gate NOT approved, still blocks analytics entirely',
    false === BHP_Analytics_Config::should_render_analytics()
);
bhp_a11b_test_assert($failures, 'consent_gate_reason() explains the block is the unapproved production consent gate, not a vague/empty reason', false !== strpos((string) BHP_Analytics_Config::consent_gate_reason(), 'consent decision has not been approved'));

update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);
bhp_a11b_test_assert($failures, 'Once the gate is explicitly approved (and consent_storage is granted), analytics is no longer blocked by the gate itself', true === BHP_Analytics_Config::consent_decision_approved());

// Consent Mode's analytics_storage signal is a SEPARATE state from the
// business gate -- approving the gate must never silently grant consent.
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
bhp_a11b_test_assert($failures, 'Approving the consent gate does NOT silently grant analytics_storage consent -- it stays denied with no real consent cookie', 'denied' === BHP_Consent::current_state()['analytics_storage']);

// ⚠ CHANGED 2026-08-05 (theme 1.19.178, `CYCLE143-GIM-51`). The assertion
// that stood here read: "With the gate approved but consent still denied,
// should_render_analytics() is still false (both checks must independently
// pass)". That was correct as a per-request statement and WRONG as a
// privacy control, because SiteGround's page cache varies only on
// Accept-Encoding: the consent-gated HTML was demonstrably served to the
// wrong visitors in both directions on live production (2026-08-04).
// Emission is now unconditional and cacheable; collection is gated by
// Consent Mode, whose server-rendered default is denied for everyone.
bhp_a11b_test_assert($failures, 'With the gate approved, should_render_analytics() is TRUE even with consent denied -- emission is now cache-safe and unconditional, and gating lives in Consent Mode', true === BHP_Analytics_Config::should_render_analytics());
bhp_a11b_test_assert($failures, 'should_render_analytics() returns the SAME value with a granting cookie as without one (no per-visitor variation may re-enter the render path)', BHP_Analytics_Config::should_render_analytics() === (function () {
    $_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode(['analytics_storage' => 'granted']);
    $result = BHP_Analytics_Config::should_render_analytics();
    unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
    return $result;
})());
bhp_a11b_test_assert($failures, 'The Consent Mode DEFAULT payload still denies analytics_storage regardless of the gate being approved', 'denied' === BHP_Consent::default_signals()['analytics_storage']);

delete_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED);
delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
$_SERVER['HTTP_HOST'] = $original_host;
wp_set_current_user($original_user_id_gate_test);

// ---------------------------------------------------------------------
// 8. BHP_UTM_Attribution: additional edge cases (Phase 1C hardening,
//    2026-07-06) -- non-whitelisted fields, partial cookies, HTML/script
//    content, and the FIELDS/TRACKED_PARAMS consistency guard. The
//    client-side capture behavior itself (direct visits never overwrite
//    last-touch, 90/30-day expiry windows) lives in
//    assets/js/bhp-attribution.js and is verified by static assertions
//    below plus a live browser check -- there is no JS test runner in
//    this repo, and this suite's own convention is exercising real PHP
//    classes, not re-implementing a JS harness for one file.
// ---------------------------------------------------------------------
$read_cookie_2 = $reflection->getMethod('read_cookie');
$read_cookie_2->setAccessible(true);

$_COOKIE['bhp_attr_edge1'] = wp_json_encode([
    'utm_source'     => 'google',
    'malicious_field' => '<script>alert(1)</script>', // not in BHP_UTM_Attribution::FIELDS
]);
$result_edge1 = $read_cookie_2->invoke(null, 'bhp_attr_edge1');
bhp_a11b_test_assert($failures, 'A field not in the FIELDS whitelist is silently dropped, never stored', is_array($result_edge1) && !array_key_exists('malicious_field', $result_edge1));
bhp_a11b_test_assert($failures, 'A whitelisted field alongside a non-whitelisted one still passes through correctly', 'google' === ($result_edge1['utm_source'] ?? null));
unset($_COOKIE['bhp_attr_edge1']);

$_COOKIE['bhp_attr_edge2'] = wp_json_encode([
    'utm_source' => '<script>alert(1)</script>',
]);
$result_edge2 = $read_cookie_2->invoke(null, 'bhp_attr_edge2');
bhp_a11b_test_assert($failures, 'HTML/script content in a whitelisted field is stripped by sanitize_text_field, never stored with tags intact', is_array($result_edge2) && false === strpos((string) ($result_edge2['utm_source'] ?? ''), '<script>'));
unset($_COOKIE['bhp_attr_edge2']);

$_COOKIE['bhp_attr_edge3'] = wp_json_encode([
    'utm_source' => 'newsletter',
    // utm_medium, utm_campaign, etc. deliberately absent
]);
$result_edge3 = $read_cookie_2->invoke(null, 'bhp_attr_edge3');
bhp_a11b_test_assert($failures, 'A cookie with only some tracked fields present returns just those fields, never fabricating the missing ones', is_array($result_edge3) && 'newsletter' === $result_edge3['utm_source'] && !array_key_exists('utm_medium', $result_edge3));
unset($_COOKIE['bhp_attr_edge3']);

$_COOKIE['bhp_attr_edge4'] = wp_json_encode([]);
$result_edge4 = $read_cookie_2->invoke(null, 'bhp_attr_edge4');
bhp_a11b_test_assert($failures, 'A well-formed but entirely empty JSON object returns null, never an empty-but-present array', null === $result_edge4);
unset($_COOKIE['bhp_attr_edge4']);

// FIELDS/TRACKED_PARAMS consistency guard: the client-side capture list
// (assets/js/bhp-attribution.js) and the server-side read whitelist
// (BHP_UTM_Attribution::FIELDS) must agree on every UTM/click-id field,
// or capture and read silently diverge -- catches that class of drift
// automatically instead of relying on someone remembering to update both.
$bhp_attr_js_path = get_template_directory() . '/assets/js/bhp-attribution.js';
$bhp_attr_js_source = file_exists($bhp_attr_js_path) ? file_get_contents($bhp_attr_js_path) : '';
preg_match('/TRACKED_PARAMS\s*=\s*\[([^\]]*)\]/', $bhp_attr_js_source, $tracked_params_match);
preg_match_all('/[\'"]([a-z_]+)[\'"]/', $tracked_params_match[1] ?? '', $tracked_params_list);
$js_tracked_params = $tracked_params_list[1] ?? [];
$php_fields_minus_extras = array_diff(BHP_UTM_Attribution::FIELDS, ['landing_page', 'timestamp']);
sort($js_tracked_params);
$php_fields_sorted = $php_fields_minus_extras;
sort($php_fields_sorted);
bhp_a11b_test_assert(
    $failures,
    'assets/js/bhp-attribution.js TRACKED_PARAMS and BHP_UTM_Attribution::FIELDS (minus landing_page/timestamp, which only the PHP side adds) list the exact same UTM/click-id fields',
    !empty($js_tracked_params) && $js_tracked_params === array_values($php_fields_sorted)
);

preg_match('/FIRST_TOUCH_DAYS\s*=\s*(\d+)/', $bhp_attr_js_source, $first_days_match);
preg_match('/LAST_TOUCH_DAYS\s*=\s*(\d+)/', $bhp_attr_js_source, $last_days_match);
bhp_a11b_test_assert($failures, 'First-touch cookie expiry is documented and implemented as 90 days', '90' === ($first_days_match[1] ?? null));
bhp_a11b_test_assert($failures, 'Last-touch cookie expiry is documented and implemented as 30 days', '30' === ($last_days_match[1] ?? null));

// Restore whatever real GTM/GA4 configuration existed before this suite
// ran -- see the capture at the top of this file for why this matters.
if (false !== $bhp_a11b_original_gtm_id) {
    update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, $bhp_a11b_original_gtm_id);
} else {
    delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
}
if (false !== $bhp_a11b_original_ga4_id) {
    update_option(BHP_Analytics_Config::OPTION_GA4_MEASUREMENT_ID, $bhp_a11b_original_ga4_id);
} else {
    delete_option(BHP_Analytics_Config::OPTION_GA4_MEASUREMENT_ID);
}

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d Analytics Phase 1B test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All Analytics Phase 1B (config/consent/UTM) tests passed.');
}
