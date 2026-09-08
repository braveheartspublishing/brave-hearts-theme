<?php
/**
 * BHP_Consent staging-override precedence test (WPConsent integration QA,
 * 2026-07-13).
 *
 * Regression coverage for the defect found during WPConsent Free staging
 * QA: the staging analytics-validation override must only ever fill in a
 * DEFAULT before a visitor has made a real choice. It must never outrank
 * an explicit real consent cookie in either direction (reject OR accept).
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-consent-precedence.php --user=1
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_consent_precedence_test_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

$original_host = $_SERVER['HTTP_HOST'] ?? '';
$original_override = get_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, false);
$original_cookie = $_COOKIE[BHP_Consent::COOKIE_NAME] ?? null;

$_SERVER['HTTP_HOST'] = 'staging2.braveheartspublishing.com';

// ---------------------------------------------------------------------
// 1. No real choice yet, override ON -> override fills the default
//    (this is the override's actual intended purpose; must still work)
// ---------------------------------------------------------------------
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, true);
$state = BHP_Consent::current_state();
bhp_consent_precedence_test_assert($failures, 'No real choice + override ON: analytics_storage defaults to granted (override still works before any real choice)', 'granted' === $state['analytics_storage']);

// ---------------------------------------------------------------------
// 2. Explicit Reject cookie, override ON -> Reject wins (the actual bug:
//    previously the override unconditionally forced 'granted' here)
// ---------------------------------------------------------------------
$_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode([
    'analytics_storage'  => 'denied',
    'ad_storage'         => 'denied',
    'ad_user_data'       => 'denied',
    'ad_personalization' => 'denied',
]);
$state = BHP_Consent::current_state();
bhp_consent_precedence_test_assert($failures, 'Explicit Reject cookie + override ON: analytics_storage stays denied (real choice outranks the QA override)', 'denied' === $state['analytics_storage']);
bhp_consent_precedence_test_assert($failures, 'Explicit Reject cookie + override ON: ad_storage stays denied', 'denied' === $state['ad_storage']);

// ---------------------------------------------------------------------
// 3. Explicit Accept cookie, override ON -> Accept still honored (not a
//    regression target, but confirms the fix didn't break the grant path)
// ---------------------------------------------------------------------
$_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode([
    'analytics_storage'  => 'granted',
    'ad_storage'         => 'granted',
    'ad_user_data'       => 'granted',
    'ad_personalization' => 'granted',
]);
$state = BHP_Consent::current_state();
bhp_consent_precedence_test_assert($failures, 'Explicit Accept cookie + override ON: analytics_storage stays granted', 'granted' === $state['analytics_storage']);
bhp_consent_precedence_test_assert($failures, 'Explicit Accept cookie + override ON: ad_storage stays granted', 'granted' === $state['ad_storage']);

// ---------------------------------------------------------------------
// 4. Explicit Reject cookie, override OFF -> Reject still respected
//    (baseline, no override involved at all)
// ---------------------------------------------------------------------
delete_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE);
$_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode(['analytics_storage' => 'denied']);
$state = BHP_Consent::current_state();
bhp_consent_precedence_test_assert($failures, 'Explicit Reject cookie + override OFF: analytics_storage stays denied', 'denied' === $state['analytics_storage']);

// ---------------------------------------------------------------------
// 5. No cookie, override OFF -> default denied (fail-closed baseline)
// ---------------------------------------------------------------------
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
$state = BHP_Consent::current_state();
bhp_consent_precedence_test_assert($failures, 'No real choice + override OFF: analytics_storage defaults to denied (fail-closed)', 'denied' === $state['analytics_storage']);

// ---------------------------------------------------------------------
// Restore original state
// ---------------------------------------------------------------------
if (false !== $original_override) {
    update_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, $original_override);
} else {
    delete_option(BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE);
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
    WP_CLI::error(sprintf('%d consent precedence test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All consent precedence tests passed.');
}
