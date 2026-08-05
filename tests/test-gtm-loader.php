<?php
/**
 * GTM loader test suite (Analytics Phase 1B).
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-gtm-loader.php --user=1
 *
 * Exercises BHP_GTM_Loader's rendering gate by capturing its actual
 * printed output via output buffering -- proves what is/isn't printed,
 * not just that the gating functions return the expected booleans.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_gtm_test_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

$original_host = $_SERVER['HTTP_HOST'] ?? '';
$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com'; // production, so is_tracking_enabled() is true regardless of the staging override option

// This suite exercises OPTION_GTM_CONTAINER_ID with throwaway fixture
// values and deletes it at the end -- but a real environment may already
// have a real container ID configured. Deleting it unconditionally wiped
// a real staging GTM ID twice in one session (2026-07-06, both here and
// in test-analytics-phase1b.php before that file was fixed the same way).
// Capture whatever is already there now and restore it exactly at the
// end, regardless of what the test fixtures do in between.
$bhp_gtm_test_original_gtm_id = get_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, false);

// ---------------------------------------------------------------------
// 1. No GTM ID configured -> prints nothing at all
// ---------------------------------------------------------------------
delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
ob_start();
BHP_GTM_Loader::render_head_snippet();
$head_output = ob_get_clean();
bhp_gtm_test_assert($failures, 'With no GTM container ID configured, the head snippet prints nothing', '' === $head_output);

ob_start();
BHP_GTM_Loader::render_noscript_snippet();
$body_output = ob_get_clean();
bhp_gtm_test_assert($failures, 'With no GTM container ID configured, the noscript snippet prints nothing', '' === $body_output);

// ---------------------------------------------------------------------
// 2. A configured GTM ID prints exactly once, with consent defaults first
// ---------------------------------------------------------------------
// This suite runs via `wp eval-file ... --user=1`, which authenticates
// as the site's real administrator account for the whole process --
// exactly the traffic BHP_Analytics_Config::is_excluded_internal_request()
// is designed to exclude from tracking. Simulating a logged-out visitor
// here (restored right after) is required to test the "real customer"
// render path at all; the admin-exclusion path itself is verified
// separately in section 3 below using this same real wp-cli user.
$original_user_id = get_current_user_id();
wp_set_current_user(0);
update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, 'GTM-TESTID1');
// Phase 1B correction pass (2026-07-06) added the business
// consent-decision-approved gate, which must ALSO hold before GTM prints
// in production. It is a site-wide switch and is unchanged.
//
// ⚠ CHANGED 2026-08-05 (theme 1.19.178, `CYCLE143-GIM-51`): a per-visitor
// analytics_storage grant used to be simulated here too, because the
// loader was gated on it. It no longer is -- the page cache does not vary
// on the consent cookie, so a per-visitor server-side gate was defeated in
// both directions on live production. GTM now prints for every cacheable
// visitor and Consent Mode does the gating. NO CONSENT COOKIE IS SET
// BELOW, deliberately: that is the state a first-time visitor arrives in,
// and the container must still print.
update_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED, true);
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
ob_start();
BHP_GTM_Loader::render_head_snippet();
$head_output = ob_get_clean();
bhp_gtm_test_assert($failures, 'A configured GTM ID produces exactly one googletagmanager.com/gtm.js reference', 1 === substr_count($head_output, 'googletagmanager.com/gtm.js'));
bhp_gtm_test_assert($failures, 'The configured container ID appears in the printed snippet', false !== strpos($head_output, 'GTM-TESTID1'));
$consent_pos = strpos($head_output, "gtag('consent'");
$gtm_pos = strpos($head_output, 'googletagmanager.com/gtm.js');
bhp_gtm_test_assert($failures, 'Consent Mode defaults print BEFORE the GTM loader script (correct initialization order)', false !== $consent_pos && $consent_pos < $gtm_pos);
bhp_gtm_test_assert($failures, 'The container prints for a visitor with NO consent cookie at all (1.19.178: cache-safe unconditional emission)', false !== strpos($head_output, 'GTM-TESTID1'));
bhp_gtm_test_assert($failures, 'The server-rendered defaults deny analytics_storage for that same cookie-less visitor', false !== strpos($head_output, '"analytics_storage":"denied"'));

// Byte-equality across consent states is the property that makes the page
// cacheable. Asserted in full in tests/test-consent-mode-cache-safety.php;
// this is the cheap regression tripwire in the loader's own suite.
$_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode(['analytics_storage' => 'granted']);
ob_start();
BHP_GTM_Loader::render_head_snippet();
$head_output_consented = ob_get_clean();
unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
bhp_gtm_test_assert($failures, 'The head snippet is byte-identical with and without a granting consent cookie (the SiteGround page cache cannot mis-serve it)', $head_output_consented === $head_output);

ob_start();
BHP_GTM_Loader::render_noscript_snippet();
$body_output = ob_get_clean();
bhp_gtm_test_assert($failures, 'The noscript body snippet contains exactly one iframe', 1 === substr_count($body_output, '<iframe'));
bhp_gtm_test_assert($failures, 'The noscript snippet references the same container ID', false !== strpos($body_output, 'GTM-TESTID1'));

wp_set_current_user($original_user_id); // restore the real wp-cli admin user for section 3, which specifically tests admin-exclusion

// ---------------------------------------------------------------------
// 3. Never loads in wp-admin
// ---------------------------------------------------------------------
$_COOKIE[BHP_Consent::COOKIE_NAME] = wp_json_encode(['analytics_storage' => 'granted']);
// Admin-exclusion must hold even with the consent gate approved and
// consent granted -- proves is_excluded_internal_request()
// is checked first, ahead of the other gates, and is never bypassed by
// an otherwise-fully-approved configuration.
global $current_screen;
set_current_screen('dashboard'); // makes is_admin()-adjacent checks reflect an admin context for this test process
// is_admin() itself is driven by whether ABSPATH's request came through
// wp-admin, which cannot be toggled mid-process here -- instead this
// exercises the explicit exclusion path directly, matching how
// BHP_Analytics_Config::is_excluded_internal_request() is actually
// consulted by should_render_analytics().
bhp_gtm_test_assert($failures, 'is_admin() is true while running inside the admin context this test just set', is_admin());
ob_start();
BHP_GTM_Loader::render_head_snippet();
$admin_output = ob_get_clean();
bhp_gtm_test_assert($failures, 'GTM prints nothing while is_admin() is true, even with a valid container ID configured', '' === $admin_output);

unset($_COOKIE[BHP_Consent::COOKIE_NAME]);
delete_option(BHP_Analytics_Config::OPTION_CONSENT_DECISION_APPROVED);

// Restore whatever real GTM configuration existed before this suite ran
// -- see the capture near the top of this file for why this matters.
if (false !== $bhp_gtm_test_original_gtm_id) {
    update_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID, $bhp_gtm_test_original_gtm_id);
} else {
    delete_option(BHP_Analytics_Config::OPTION_GTM_CONTAINER_ID);
}
$_SERVER['HTTP_HOST'] = $original_host;

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d GTM loader test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All GTM loader tests passed.');
}
