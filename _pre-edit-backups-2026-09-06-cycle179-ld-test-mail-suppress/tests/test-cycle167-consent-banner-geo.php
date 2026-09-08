<?php
/**
 * Banner-visibility + footer-link CONTRACT suite (theme 1.19.309,
 * `CYCLE167-LD-CONSENT-BANNER-GEO`).
 *
 * Run on staging (never production, except as a read-only post-deploy
 * verification) via:
 *   wp eval-file tests/test-cycle167-consent-banner-geo.php --user=1
 *
 * ⭐⭐ WHAT THIS SUITE CAN AND CANNOT PROVE -- read this before quoting a pass.
 *
 *   ✅ CAN, in PHP: that the gate script is emitted on every page, that its
 *      bytes do not vary by cookie state (the `CYCLE143-GIM-51` invariant),
 *      that its region list has not drifted from BHP_Consent::EEA_UK_REGIONS,
 *      that the suppression uses the plugin's OWN settings gate rather than a
 *      CSS hack that would also kill the preferences modal, that the footer
 *      "Privacy Choices" link renders on every page type, and that this
 *      release changed NOTHING about the measurement defaults.
 *
 *   ⛔ CANNOT, from PHP: decide whether a given visitor sees the bar. The
 *      decision is made in the BROWSER from the visitor's own timezone. There
 *      is no server-side geo input to stub, because the server performs no geo
 *      lookup -- that is the design, and it is what keeps the page cacheable.
 *
 *      ⭐ THE HONEST SPLIT, and it is deliberate:
 *        · the DECISION FUNCTION is unit-tested, both directions, by
 *          `node tests/js/consent-region-harness.js <rendered-page.html>`,
 *          which executes the real emitted JS against fixture timezones;
 *        · the SUPPRESSION WIRING and the footer link's behaviour are
 *          observed in a real browser and recorded in the QA log.
 *      A PHP assertion claiming "the US visitor sees no bar" would be
 *      asserting something it cannot observe, and that is a fabricated check.
 *
 * Authority: founder report, carrier item 332, 2026-08-27 -- "The consent bar
 * is still firing on new browsers." RELAYED via the record, not witnessed by
 * this suite's author.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_bgeo_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

// --- fixture capture / restore ------------------------------------------
$original_cookie  = $_COOKIE[BHP_Consent::COOKIE_NAME] ?? null;
$original_wpc     = $_COOKIE['wpconsent_preferences'] ?? null;
$original_user_id = get_current_user_id();

function bhp_bgeo_gate($cookie_value = null, $wpc_value = null) {
    if (null === $cookie_value) { unset($_COOKIE[BHP_Consent::COOKIE_NAME]); }
    else { $_COOKIE[BHP_Consent::COOKIE_NAME] = $cookie_value; }
    if (null === $wpc_value) { unset($_COOKIE['wpconsent_preferences']); }
    else { $_COOKIE['wpconsent_preferences'] = $wpc_value; }
    ob_start();
    bhp_consent_region_gate_script();
    return ob_get_clean();
}

$gate = bhp_bgeo_gate(null, null);

// ------------------------------------------------------------------
// 1. THE GATE IS EMITTED AT ALL
// ------------------------------------------------------------------
bhp_bgeo_assert($failures, 'WPConsent is active, so the gate has something to gate', function_exists('wpconsent'));
bhp_bgeo_assert($failures, 'The region gate script is emitted', false !== strpos($gate, '<script id="bhp-consent-region-gate">'));
bhp_bgeo_assert($failures, 'It is registered on wp_head ahead of the CLS guard (2) and the compact styler (3)', 1 === has_action('wp_head', 'bhp_consent_region_gate_script'));
bhp_bgeo_assert($failures, 'The CLS guard is still registered at 2 -- this release did not displace it', 2 === has_action('wp_head', 'bhp_consent_banner_cls_guard'));
bhp_bgeo_assert($failures, 'The compact styler is still registered at 3 -- this release did not displace it', 3 === has_action('wp_head', 'bhp_consent_banner_compact_script'));

// ------------------------------------------------------------------
// 2. ⛔ THE CACHE-SAFETY INVARIANT -- `CYCLE143-GIM-51`, STILL CLOSED.
//    The gate must be byte-identical for every visitor, because SiteGround's
//    page cache varies only on Accept-Encoding. Verified first-hand on both
//    environments this release: `Vary: Accept-Encoding`, `X-Cache-Enabled:
//    True`, and a production `X-Proxy-Cache: HIT`.
// ------------------------------------------------------------------
$states = [
    'no cookies at all'          => [null, null],
    'bhp_consent_state granted'  => ['{"analytics_storage":"granted","ad_storage":"granted","ad_user_data":"granted","ad_personalization":"granted"}', null],
    'bhp_consent_state denied'   => ['{"analytics_storage":"denied","ad_storage":"denied","ad_user_data":"denied","ad_personalization":"denied"}', null],
    'wpconsent accepted'         => [null, '{"essential":true,"statistics":true,"marketing":true}'],
    'wpconsent rejected'         => [null, '{"essential":true,"statistics":false,"marketing":false}'],
    'malformed cookie'           => ['not-json-at-all', 'also-not-json'],
];
$identical = true;
foreach ($states as $label => $pair) {
    if (bhp_bgeo_gate($pair[0], $pair[1]) !== $gate) { $identical = false; }
}
bhp_bgeo_assert($failures, 'The gate is BYTE-IDENTICAL across all six cookie states -- the page stays cacheable', $identical);
bhp_bgeo_assert($failures, 'The gate reads no PHP superglobal -- no $_COOKIE, $_SERVER or $_GET value is interpolated into it', 0 === preg_match('/\$_(COOKIE|SERVER|GET|POST)/', $gate));

// ------------------------------------------------------------------
// 3. ⛔ THE FAIL-SAFE DIRECTION IS PRESENT IN WHAT IS ACTUALLY SHIPPED.
//    Behaviour is proven by the node harness; this asserts the branches
//    survived into the emitted bytes.
// ------------------------------------------------------------------
bhp_bgeo_assert($failures, 'An unusable timezone returns true (shows)', false !== strpos($gate, "if ( typeof tz !== 'string' || !tz ) { return true; }"));
bhp_bgeo_assert($failures, 'UTC / GMT / Etc-* are treated as ambiguous and show', false !== strpos($gate, "tz === 'UTC' || tz === 'GMT' || tz.indexOf( 'Etc/' ) === 0"));
bhp_bgeo_assert($failures, 'A thrown exception in the decision falls back to SHOW', false !== strpos($gate, 'show = true; /* ANY failure -> SHOW'));
bhp_bgeo_assert($failures, 'Europe/ and Atlantic/ are both whole-prefix shows (Azores, Madeira, Canary, Reykjavik)', false !== strpos($gate, "SHOW_PREFIXES = [ 'Europe/', 'Atlantic/' ]"));
bhp_bgeo_assert($failures, 'Cyprus is enumerated -- it reports Asia/Nicosia, not Europe/*', false !== strpos($gate, "'Asia/Nicosia'"));
bhp_bgeo_assert($failures, 'The decision function is exposed for the node harness rather than grepped from source', false !== strpos($gate, 'shouldShowBanner: shouldShowBanner'));

// ------------------------------------------------------------------
// 4. ⭐ THE TWO REGION LISTS MUST NOT DRIFT APART.
//    PHP owns EEA_UK_REGIONS for the Consent Mode defaults; the JS owns
//    SHOW_REGIONS for the banner. They describe the same set, in two
//    languages, in two files. A silent divergence is the obvious future bug.
// ------------------------------------------------------------------
preg_match('/SHOW_REGIONS = \[(.*?)\];/s', $gate, $m);
$js_regions = [];
if (!empty($m[1])) {
    preg_match_all("/'([A-Z]{2})'/", $m[1], $rm);
    $js_regions = $rm[1];
}
$php_regions = BHP_Consent::EEA_UK_REGIONS;
sort($js_regions);
$sorted_php = $php_regions;
sort($sorted_php);
bhp_bgeo_assert($failures, 'The JS banner region list was parsed out of the emitted script', count($js_regions) > 0);
bhp_bgeo_assert($failures, sprintf('The JS banner region list matches BHP_Consent::EEA_UK_REGIONS exactly (%d vs %d)', count($js_regions), count($php_regions)), $js_regions === $sorted_php);

// ------------------------------------------------------------------
// 5. ⛔ THE SUPPRESSION MECHANISM. It must use the plugin's OWN settings
//    gate. A CSS `display:none` on #wpconsent-container would ALSO hide the
//    preferences modal, which lives in the same shadow root -- verified live
//    in a real browser this release -- and would silently break the footer
//    link this release adds.
// ------------------------------------------------------------------
bhp_bgeo_assert($failures, "Suppression flips the plugin's own `enable_consent_banner` settings value", false !== strpos($gate, 'settings.enable_consent_banner = false'));
bhp_bgeo_assert($failures, "It registers through the plugin's public registerSettingsHook()", false !== strpos($gate, 'registerSettingsHook( disableBanner )'));
bhp_bgeo_assert($failures, 'The gate never hides #wpconsent-container -- that would kill the preferences modal too', false === strpos($gate, '#wpconsent-container'));
bhp_bgeo_assert($failures, 'The gate writes no cookie -- a suppressed banner must never record a choice on the visitor\'s behalf', false === strpos($gate, 'document.cookie'));
bhp_bgeo_assert($failures, 'The gate emits no gtag consent call -- measurement is not this file\'s business', false === strpos($gate, "'consent'"));
bhp_bgeo_assert($failures, 'The EEA path returns before any suppression code', false !== strpos($gate, 'if ( show ) { return; }'));

// ------------------------------------------------------------------
// 6. ⭐ THE FOOTER "Privacy Choices" LINK, ON EVERY PAGE TYPE.
//    Rendered, not grepped from footer.php.
// ------------------------------------------------------------------
$contexts = [];
$front_id = (int) get_option('page_on_front');
if ($front_id) { $contexts['front page'] = $front_id; }
foreach ([
    'a standard page'   => ['post_type' => 'page'],
    'a blog post'       => ['post_type' => 'post'],
] as $label => $args) {
    $ids = get_posts(array_merge($args, ['post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids']));
    if (!empty($ids)) { $contexts[$label] = (int) $ids[0]; }
}
foreach (['teachers', 'reluctant-reader-adventure-kit', 'complete-collection'] as $slug) {
    $p = get_page_by_path($slug);
    if ($p) { $contexts["/$slug/"] = (int) $p->ID; }
}

bhp_bgeo_assert($failures, sprintf('At least four page contexts were found to render (%d)', count($contexts)), count($contexts) >= 4);

foreach ($contexts as $label => $post_id) {
    global $post, $wp_query;
    $saved_post = $post;
    $post = get_post($post_id);
    setup_postdata($post);
    $wp_query->queried_object    = $post;
    $wp_query->queried_object_id = $post_id;

    ob_start();
    $tpl = locate_template('footer.php');
    if ($tpl) { include $tpl; }
    $html = ob_get_clean();

    $post = $saved_post;
    wp_reset_postdata();

    bhp_bgeo_assert($failures, "Privacy Choices link renders on $label", false !== strpos($html, 'Privacy Choices'));
    bhp_bgeo_assert($failures, "...carrying the plugin's own trigger class on $label", false !== strpos($html, 'wpconsent-open-preferences'));
    bhp_bgeo_assert($failures, "...styled as a sibling policy link on $label", false !== strpos($html, 'footer-bottom__link wpconsent-open-preferences'));
    bhp_bgeo_assert($failures, "...with a real href fallback if JS never runs on $label", (bool) preg_match('/<a class="footer-bottom__link wpconsent-open-preferences" href="https?:\/\/[^"]+"/', $html));
    bhp_bgeo_assert($failures, "The existing policy links survive alongside it on $label", false !== strpos($html, 'Privacy Policy') && false !== strpos($html, 'Terms'));
}

// ------------------------------------------------------------------
// 7. ⛔ MEASUREMENT IS UNTOUCHED BY THIS RELEASE. Item 310's posture stands.
// ------------------------------------------------------------------
$eea      = BHP_Consent::eea_default_signals();
$measured = BHP_Consent::measured_default_signals();
bhp_bgeo_assert($failures, 'EEA default still denies analytics_storage', 'denied' === $eea['analytics_storage']);
bhp_bgeo_assert($failures, 'EEA default still carries the region scope', isset($eea['region']) && is_array($eea['region']));
bhp_bgeo_assert($failures, 'The catch-all default still GRANTS analytics_storage (item 310)', 'granted' === $measured['analytics_storage']);
bhp_bgeo_assert($failures, 'The catch-all default still carries NO region key', !isset($measured['region']));
/* ⭐ 1.19.312 (`CYCLE167-LD-CONSENT-PIXEL-EXT`). The superseded assertion,
 * quoted rather than deleted so a reader diffing this suite sees a deliberate
 * reversal and not a weakened test:
 *
 *   > bhp_bgeo_assert($failures, "$ad is still DENIED by default in every
 *   >     region", 'denied' === $eea[$ad] && 'denied' === $measured[$ad]);
 *
 * Andrew Signore extended the US-law posture from measurement to the
 * ad/marketing signals — carrier `^349. FOUNDER` item 4, "I guess we extend
 * it", RELAYED through the Chief of Staff and not witnessed by this suite's
 * author. ⛔ THE EEA HALF OF THE OLD ASSERTION IS KEPT INTACT below and is
 * the half that matters here: THIS release (1.19.309) is about which visitors
 * see a bar, and it must still not have moved the European posture. */
foreach (['ad_storage', 'ad_user_data', 'ad_personalization'] as $ad) {
    bhp_bgeo_assert($failures, "$ad is still DENIED by default in the EEA+UK — the banner-visibility release did not touch the European posture, and neither did the 1.19.312 extension", 'denied' === $eea[$ad]);
    bhp_bgeo_assert($failures, "$ad is GRANTED by default outside the EEA+UK (item 4, 1.19.312)", 'granted' === $measured[$ad]);
}

// ------------------------------------------------------------------
// 8. ⛔ NO WPCONSENT SETTING WAS CHANGED ON THE SERVER BY THIS RELEASE.
// ------------------------------------------------------------------
$wpconsent_settings = get_option('wpconsent_settings');
bhp_bgeo_assert($failures, 'wpconsent_settings.enable_consent_banner is STILL 1 in the database -- the suppression is client-side only, and the banner template must keep rendering because the preferences modal lives inside it', is_array($wpconsent_settings) && !empty($wpconsent_settings['enable_consent_banner']));
bhp_bgeo_assert($failures, 'WPConsent "default_allow" is still OFF -- if it were ON, a cookieless first-load would emit an all-granted update in every region', is_array($wpconsent_settings) && empty($wpconsent_settings['default_allow']));
bhp_bgeo_assert($failures, 'WPConsent google_consent_mode is still OFF -- the theme owns the default emission', is_array($wpconsent_settings) && empty($wpconsent_settings['google_consent_mode']));

// ------------------------------------------------------------------
// Restore
// ------------------------------------------------------------------
wp_set_current_user($original_user_id);
if (null !== $original_cookie) { $_COOKIE[BHP_Consent::COOKIE_NAME] = $original_cookie; }
else { unset($_COOKIE[BHP_Consent::COOKIE_NAME]); }
if (null !== $original_wpc) { $_COOKIE['wpconsent_preferences'] = $original_wpc; }
else { unset($_COOKIE['wpconsent_preferences']); }

if ($failures) {
    WP_CLI::error(sprintf('%d banner-visibility test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All banner-visibility and footer-link tests passed.');
}
