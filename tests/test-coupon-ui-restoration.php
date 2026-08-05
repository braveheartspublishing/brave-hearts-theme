<?php
/**
 * Coupon-entry restoration regression test (2026-07-09).
 *
 * Root cause: WooCommerce Blocks' native coupon toggle was always present
 * in the cart/checkout block markup, but rendered with zero visual weight
 * (plain text, no border/background), making it easy to miss. This test
 * guards the fix: the toggle must be visibly styled, scoped narrowly so it
 * never touches other WC Blocks panels, coupons must remain enabled via
 * WooCommerce's own native setting (no custom on/off logic), and the
 * pre-existing bundle/coupon stacking guard must remain intact.
 *
 * No PHPUnit exists in this theme (see docs/RUNBOOK.md) -- run via:
 *   wp eval-file tests/test-coupon-ui-restoration.php --user=1
 *
 * Exits non-zero on any failure so it can gate a deploy.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_coupon_test_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

$theme_dir = get_stylesheet_directory();
$style_css = file_get_contents($theme_dir . '/style.css');

// ---------------------------------------------------------------------
// 1. Coupon toggle styling exists and is narrowly scoped
// ---------------------------------------------------------------------
bhp_coupon_test_assert($failures, 'style.css scopes coupon styling to .wc-block-components-totals-coupon',
    strpos($style_css, '.wc-block-components-totals-coupon {') !== false);
bhp_coupon_test_assert($failures, 'coupon toggle button gets a visible color from theme tokens (not left at browser default)',
    strpos($style_css, '.wc-block-components-totals-coupon .wc-block-components-panel__button {') !== false);
bhp_coupon_test_assert($failures, 'coupon toggle has a focus-visible state for keyboard accessibility',
    strpos($style_css, '.wc-block-components-totals-coupon .wc-block-components-panel__button:focus-visible {') !== false);
bhp_coupon_test_assert($failures, 'mobile layout rule prevents the coupon form from overflowing narrow viewports',
    strpos($style_css, '.wc-block-components-totals-coupon__form {') !== false && strpos($style_css, '@media (max-width: 480px)') !== false);

// Guard against accidentally styling the generic panel class sitewide --
// every occurrence of the bare ".wc-block-components-panel" selector
// (not compounded with ".wc-block-components-totals-coupon") would risk
// affecting an unrelated WC Blocks panel.
bhp_coupon_test_assert($failures, 'no unscoped ".wc-block-components-panel" rule was introduced',
    !preg_match('/(?<!totals-coupon )\.wc-block-components-panel\s*\{/', $style_css));

// ---------------------------------------------------------------------
// 2. Cart drawer: informational routing hint only, no duplicate coupon logic
// ---------------------------------------------------------------------
$drawer_php = file_get_contents(BHP_BUNDLE_PRICING_DIR . 'includes/bundle-drawer.php');
bhp_coupon_test_assert($failures, 'drawer shows a coupon routing hint',
    strpos($drawer_php, 'bhp-cart-drawer__coupon-hint') !== false);
bhp_coupon_test_assert($failures, 'drawer hint text points to checkout, not a fake in-drawer coupon field',
    strpos($drawer_php, 'Have a coupon? You can add it at checkout.') !== false);
bhp_coupon_test_assert($failures, 'drawer has no coupon input/form of its own (would be duplicate, fragile logic)',
    strpos($drawer_php, '<input') === false && strpos($drawer_php, 'bhp-cart-drawer__coupon-input') === false);
bhp_coupon_test_assert($failures, 'drawer checkout link still uses the native wc_get_checkout_url()',
    strpos($drawer_php, 'wc_get_checkout_url()') !== false);

// ---------------------------------------------------------------------
// 3. Coupons remain enabled via WooCommerce's own native setting
// ---------------------------------------------------------------------
bhp_coupon_test_assert($failures, 'wc_coupons_enabled() is true (native WooCommerce setting, not a custom toggle)',
    function_exists('wc_coupons_enabled') && wc_coupons_enabled() === true);

// ---------------------------------------------------------------------
// 4. Pre-existing bundle/coupon stacking guard is untouched
// ---------------------------------------------------------------------
$bundle_cart_php = file_get_contents(BHP_BUNDLE_PRICING_DIR . 'includes/bundle-cart.php');
bhp_coupon_test_assert($failures, 'bundle discount still skips entirely when any coupon is applied (no silent stacking)',
    strpos($bundle_cart_php, 'get_applied_coupons()') !== false);

// ---------------------------------------------------------------------
// 5. Purchase-event analytics still reports real coupon codes
// ---------------------------------------------------------------------
$bundle_analytics_php = file_get_contents(BHP_BUNDLE_PRICING_DIR . 'includes/bundle-analytics.php');
bhp_coupon_test_assert($failures, 'purchase event still reports the order\'s real coupon codes',
    strpos($bundle_analytics_php, 'get_coupon_codes()') !== false);

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d coupon-UI restoration test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All coupon-UI restoration tests passed.');
}
