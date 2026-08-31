<?php
/**
 * CONTRACT suite for theme 1.19.343 / bundle plugin 1.8.78
 * (`CYCLE173-LD-CONSENT-CHECKOUT`).
 *
 * Run on staging (never production, except as a read-only post-deploy
 * verification) via:
 *   wp eval-file tests/test-cycle173-consent-checkout.php --user=1
 *
 * ⭐⭐ WHAT THIS SUITE CAN AND CANNOT PROVE — read this before quoting a pass.
 *
 *   ✅ CAN, in PHP: that the shipped JS files contain the branches this
 *      release exists to add, in the required precedence order; that the
 *      begin_checkout latch and the empty-cart guard survived into the
 *      shipped bytes; that the drawer's racing click event no longer
 *      carries the name `begin_checkout`; that the attribution gate reads
 *      the SHIPPED region object rather than a second copy of the EEA
 *      list; that this release changed NOTHING about the Consent Mode
 *      defaults, the region gate, or the banner posture.
 *
 *   ⛔ CANNOT, from PHP: observe an event reach the dataLayer, or observe a
 *      cookie be written. Both happen in the browser, on a real checkout
 *      with a real cart. Those are recorded in the staging QA log from a
 *      real browser session, not asserted here. A PHP assertion claiming
 *      "begin_checkout fired" would be asserting something it cannot
 *      observe, and that is a fabricated check.
 *
 * ⛔ THIS SUITE DELIBERATELY ASSERTS THAT THE BANNER POSTURE DID NOT MOVE.
 *    The briefed item was "make the consent banner display"; the diagnosis
 *    found the non-display is Andrew's own ruling (theme 1.19.309,
 *    `CYCLE167-LD-CONSENT-BANNER-GEO`, carrier items 310 and 332) and the
 *    item was stopped rather than implemented. Section 4 is the guard that
 *    proves this release did not quietly reverse it.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_c173_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

$theme_dir  = get_stylesheet_directory();
$plugin_dir = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing';

$attr_js     = @file_get_contents($theme_dir . '/assets/js/bhp-attribution.js');
$checkout_js = @file_get_contents($plugin_dir . '/assets/bhp-checkout-events.js');
$drawer_js   = @file_get_contents($plugin_dir . '/assets/bundle-drawer.js');

// ------------------------------------------------------------------
// 0. THE FILES THIS SUITE READS ACTUALLY EXIST.
//    An empty read makes every strpos() below return false, which would
//    look like a wall of real failures. Fail here instead, clearly.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'assets/js/bhp-attribution.js is readable', is_string($attr_js) && strlen($attr_js) > 1000);
bhp_c173_assert($failures, 'plugin assets/bhp-checkout-events.js is readable', is_string($checkout_js) && strlen($checkout_js) > 1000);
bhp_c173_assert($failures, 'plugin assets/bundle-drawer.js is readable', is_string($drawer_js) && strlen($drawer_js) > 1000);

if (!is_string($attr_js) || !is_string($checkout_js) || !is_string($drawer_js)) {
    WP_CLI::error('Cannot continue: one or more shipped JS files could not be read.');
}

// ------------------------------------------------------------------
// 1. VERSIONS — the deployed artefact is the one this suite describes.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'Theme version is 1.19.343', '1.19.343' === wp_get_theme()->get('Version'));
bhp_c173_assert($failures, 'Bundle plugin version constant is 1.8.78', defined('BHP_BUNDLE_PRICING_VERSION') && '1.8.78' === BHP_BUNDLE_PRICING_VERSION);

// ------------------------------------------------------------------
// 2. begin_checkout — EXACTLY ONCE, ON THE CHECKOUT PAGE, NON-EMPTY CART.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'begin_checkout is emitted from the checkout-page script', false !== strpos($checkout_js, "pushEvent('begin_checkout'"));
bhp_c173_assert($failures, 'It carries source: checkout_page, distinguishing it from the old drawer emission', false !== strpos($checkout_js, "source: 'checkout_page'"));
bhp_c173_assert($failures, 'A module-scoped latch exists', false !== strpos($checkout_js, 'var beginCheckoutFired = false;'));
bhp_c173_assert($failures, 'The latch is checked before emitting', false !== strpos($checkout_js, 'if (beginCheckoutFired) {'));
bhp_c173_assert($failures, 'The latch is set before the push, not after -- a throw inside pushEvent cannot produce a second event', (bool) preg_match('/beginCheckoutFired = true;\s*pushEvent\(\'begin_checkout\'/', $checkout_js));
bhp_c173_assert($failures, 'An empty cart is guarded', false !== strpos($checkout_js, '!cart.items || !cart.items.length'));
bhp_c173_assert($failures, 'An empty cart does NOT consume the latch -- the items guard returns BEFORE the latch is set', (bool) preg_match('/!cart\.items \|\| !cart\.items\.length\) \{\s*return;\s*\}\s*beginCheckoutFired = true;/', $checkout_js));
bhp_c173_assert($failures, 'It is driven by an unconditional cart read on load, not only by observing Blocks traffic', false !== strpos($checkout_js, 'function fireBeginCheckoutFromCart()'));
bhp_c173_assert($failures, 'That read runs on DOMContentLoaded and on an already-parsed document', 2 === substr_count($checkout_js, 'fireBeginCheckoutFromCart();'));
bhp_c173_assert($failures, 'It uses originalFetch via currentCart(), not the observed window.fetch', false !== strpos($checkout_js, 'currentCart().then(maybeFireBeginCheckout)'));
bhp_c173_assert($failures, 'A failed cart read is swallowed -- observability never breaks checkout', (bool) preg_match('/currentCart\(\)\.then\(maybeFireBeginCheckout\)\.catch\(/', $checkout_js));
bhp_c173_assert($failures, 'The fetch observer is the second, redundant route to the same latched function', false !== strpos($checkout_js, 'maybeFireBeginCheckout(cart);'));
bhp_c173_assert($failures, 'value is items-only (cartItemsValue), the same basis as every other GA4 event here', (bool) preg_match('/pushEvent\(\'begin_checkout\',[^;]*value: cartItemsValue\(cart\)/s', $checkout_js));
bhp_c173_assert($failures, 'items use the shared catalog-aware builder, not a second item schema', (bool) preg_match('/pushEvent\(\'begin_checkout\',[^;]*items: ga4ItemsFromCart\(cart\)/s', $checkout_js));

// ------------------------------------------------------------------
// 3. ⛔ NO DOUBLE-COUNT. The drawer's racing click must no longer be named
//    begin_checkout, or every side-cart customer is counted twice the
//    moment the reliable event starts arriving.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'bundle-drawer.js emits NO begin_checkout event of any kind', false === strpos($drawer_js, "pushEvent('begin_checkout'"));
bhp_c173_assert($failures, 'The side-cart click keeps its own distinct name instead of being deleted', false !== strpos($drawer_js, "pushEvent('side_cart_checkout_click'"));
bhp_c173_assert($failures, 'The renamed event still carries source: side_cart, so the two routes stay distinguishable', (bool) preg_match('/pushEvent\(\'side_cart_checkout_click\',[^;]*source: \'side_cart\'/s', $drawer_js));
/*
 * ⚠ THIS ASSERTION COUNTS CODE, NOT COMMENTS — and it was wrong on the
 *   first staging run, which is how the defect was found rather than
 *   reasoned about. The 1.8.78 docblock quotes the OLD emission verbatim
 *   ("pushEvent('begin_checkout', {...});") to show what was moved, so a
 *   naive substr_count over the raw file returned 2 and reported a
 *   double-count that does not exist. Stripping comments first is the
 *   correction: the claim being made is about emissions the browser
 *   executes, so the instrument must look at exactly that.
 */
$strip_comments = static function ($js) {
    $js = preg_replace('!/\*.*?\*/!s', '', $js);
    return preg_replace('!^\s*//.*$!m', '', $js);
};
$checkout_code = $strip_comments($checkout_js);
$drawer_code   = $strip_comments($drawer_js);
bhp_c173_assert($failures, 'The comment stripper actually removed the docblocks it is relied on to remove', strlen($checkout_code) < strlen($checkout_js) && strlen($drawer_code) < strlen($drawer_js));
bhp_c173_assert($failures, 'Exactly one begin_checkout emission exists in EXECUTABLE code across both shipped client scripts', 1 === (substr_count($checkout_code, "pushEvent('begin_checkout'") + substr_count($drawer_code, "pushEvent('begin_checkout'")));
bhp_c173_assert($failures, 'And it is in the checkout-page script, not the drawer', 1 === substr_count($checkout_code, "pushEvent('begin_checkout'") && 0 === substr_count($drawer_code, "pushEvent('begin_checkout'"));

// ------------------------------------------------------------------
// 4. ⛔ THE BANNER POSTURE DID NOT MOVE. This release stopped that item;
//    these assertions prove it did not reverse Andrew's ruling by accident.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'The region gate is still registered at wp_head priority 1', 1 === has_action('wp_head', 'bhp_consent_region_gate_script'));
bhp_c173_assert($failures, 'The EEA+UK Consent Mode default is still all-four-denied', BHP_Consent::default_signals() === array_diff_key(BHP_Consent::eea_default_signals(), ['region' => 1]));
bhp_c173_assert($failures, 'The EEA+UK region list is still exactly 31 entries', 31 === count(BHP_Consent::EEA_UK_REGIONS));
$measured = BHP_Consent::measured_default_signals();
bhp_c173_assert($failures, 'The catch-all default still grants all four signals -- unchanged by this release', 'granted' === $measured['analytics_storage'] && 'granted' === $measured['ad_storage'] && 'granted' === $measured['ad_user_data'] && 'granted' === $measured['ad_personalization']);
$gate_out = '';
ob_start();
bhp_consent_region_gate_script();
$gate_out = ob_get_clean();
bhp_c173_assert($failures, 'The gate still suppresses the bar via the plugin OWN settings flag, not a CSS hack', false !== strpos($gate_out, 'settings.enable_consent_banner = false'));
bhp_c173_assert($failures, 'The gate still fails SAFE toward showing the banner on any error', false !== strpos($gate_out, 'show = true; /* ANY failure -> SHOW'));
bhp_c173_assert($failures, 'The server-side WPConsent setting is untouched -- enable_consent_banner is still 1 in the database', (function () {
    $s = get_option('wpconsent_settings');
    return is_array($s) && !empty($s['enable_consent_banner']);
})());

// ------------------------------------------------------------------
// 5. THE ATTRIBUTION GATE — precedence, and the fail-safe direction.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'The stored choice is read as a tri-state (granted / refused / never chosen), not a boolean', false !== strpos($attr_js, 'function storedAnalyticsChoice()'));
bhp_c173_assert($failures, 'A refusal is honoured -- an explicit choice returns before any region logic runs', (bool) preg_match('/if \(null !== choice\) \{\s*return choice;\s*\}/', $attr_js));
bhp_c173_assert($failures, 'GPC is honoured when no choice exists', (bool) preg_match('/if \(gpcActive\(\)\) \{\s*return false;\s*\}/', $attr_js));
bhp_c173_assert($failures, 'GPC is checked AFTER the stored choice -- an explicit acceptance still outranks a browser setting, matching BHP_WPConsent_Bridge', strpos($attr_js, 'if (null !== choice)') < strpos($attr_js, 'if (gpcActive())'));
bhp_c173_assert($failures, 'The region decision is READ from the shipped window.bhpConsentRegion, never recomputed', false !== strpos($attr_js, 'window.bhpConsentRegion'));
bhp_c173_assert($failures, 'It captures only on an explicit false -- an absent or truthy showBanner never grants', false !== strpos($attr_js, 'false === region.showBanner'));
bhp_c173_assert($failures, 'A throw anywhere in the region branch resolves to NO capture', (bool) preg_match('/\} catch \(e\) \{\s*return false;/', $attr_js));
bhp_c173_assert($failures, 'The final fallthrough is NO capture -- EEA, ambiguous, and no-gate visitors all land here', (bool) preg_match('/\/\/ 4\. Banner shown \(EEA \/ ambiguous\), or no region gate at all\.\s*return false;\s*\}/', $attr_js));

// ⛔ THE ANTI-DRIFT ASSERTION, and it is the one that matters most here.
// A second copy of the EEA table in this file is the obvious future bug:
// it would silently disagree with BHP_Consent::EEA_UK_REGIONS and with the
// region gate, and nothing would notice.
$eea_literals = 0;
foreach (BHP_Consent::EEA_UK_REGIONS as $code) {
    if (false !== strpos($attr_js, "'" . $code . "'")) {
        $eea_literals++;
    }
}
bhp_c173_assert($failures, 'bhp-attribution.js carries NO copy of the EEA region list -- one region list, one source', 0 === $eea_literals);
bhp_c173_assert($failures, 'It also carries no timezone heuristic of its own', false === strpos($attr_js, 'resolvedOptions'));

// ------------------------------------------------------------------
// 6. NOT WIDENED. The cookies and their contents are unchanged; only the
//    condition under which they are written moved.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'Still exactly two attribution cookies', false !== strpos($attr_js, "FIRST_TOUCH_COOKIE = 'bhp_attr_first'") && false !== strpos($attr_js, "LAST_TOUCH_COOKIE = 'bhp_attr_last'"));
bhp_c173_assert($failures, 'Expiries unchanged at 90 / 30 days', false !== strpos($attr_js, 'FIRST_TOUCH_DAYS = 90') && false !== strpos($attr_js, 'LAST_TOUCH_DAYS = 30'));
bhp_c173_assert($failures, 'The tracked-parameter list is unchanged -- no new field is collected', false !== strpos($attr_js, "TRACKED_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'ttclid', 'msclkid']"));
bhp_c173_assert($failures, 'The write is still gated -- capture() is never called unconditionally', false === strpos($attr_js, "\n\tcapture();"));
bhp_c173_assert($failures, 'The accept-on-this-page listener still exists, so an EEA acceptor is not lost', false !== strpos($attr_js, "window.addEventListener('wpconsent_consent_saved'"));

// ------------------------------------------------------------------
// 7. ENQUEUE SCOPE — the checkout script must still be checkout-only.
// ------------------------------------------------------------------
bhp_c173_assert($failures, 'bhp_bundle_drawer_assets() is still the conditional enqueue site', function_exists('bhp_bundle_drawer_assets'));
$drawer_php = @file_get_contents($plugin_dir . '/includes/bundle-drawer.php');
bhp_c173_assert($failures, 'bhp-checkout-events.js is still enqueued only on checkout, excluding order-received', is_string($drawer_php) && (bool) preg_match('/is_checkout\(\) && ! is_order_received_page\(\)[\s\S]{0,200}bhp-checkout-events/', $drawer_php));

// ------------------------------------------------------------------
// 8. ⛔ THE RIDER WAS REFUSED. returnMethod must NOT be present.
//    The live published policy (production page 10, post_modified
//    2026-08-14, read read-only over SSH 2026-08-31) says verbatim:
//    "Because every book is printed on demand, there is nothing to send
//    back - keep the books or pass them along." Emitting ReturnByMail
//    would publish a structured-data claim the store's own policy
//    contradicts. This assertion is the guard against it being added
//    later without the policy changing first.
// ------------------------------------------------------------------
$functions_php = @file_get_contents($theme_dir . '/functions.php');
bhp_c173_assert($failures, 'No returnMethod is emitted into hasMerchantReturnPolicy', is_string($functions_php) && false === strpos($functions_php, "'returnMethod'"));
bhp_c173_assert($failures, 'The return policy still carries only the four fields the live policy page supports', is_string($functions_php) && (bool) preg_match("/'\@type'\s*=> 'MerchantReturnPolicy',\s*'applicableCountry'\s*=> 'US',\s*'returnPolicyCategory'.*?'merchantReturnDays'\s*=> 30,\s*'returnFees'/s", $functions_php));
bhp_c173_assert($failures, 'Still no fabricated aggregateRating or review schema anywhere in the filter', is_string($functions_php) && false === strpos($functions_php, "'aggregateRating' =>"));

// ------------------------------------------------------------------
WP_CLI::log('');
if (empty($failures)) {
    WP_CLI::success('CYCLE173 consent/checkout suite: ALL ASSERTIONS PASS.');
} else {
    WP_CLI::warning(count($failures) . ' FAILURE(S):');
    foreach ($failures as $f) {
        WP_CLI::log('  - ' . $f);
    }
    WP_CLI::error('CYCLE173 consent/checkout suite FAILED.');
}
