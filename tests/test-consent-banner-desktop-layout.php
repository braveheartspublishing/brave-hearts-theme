<?php
/**
 * Consent-banner desktop layout suite (theme 1.19.186, `CYCLE144-GIM-21`).
 *
 * Run on staging (never production, except as a read-only post-deploy
 * verification) via:
 *   wp eval-file tests/test-consent-banner-desktop-layout.php --user=1
 *
 * ---------------------------------------------------------------------------
 * THE DEFECT THIS SUITE EXISTS TO PREVENT — observed live on production AND
 * staging 2026-08-05 in headless Chrome with `window.innerWidth` asserted at
 * 1280, WPConsent Free 1.1.7, theme 1.19.180 / 1.19.182:
 *
 *     Accept All            x 616  -> 983    on screen
 *     Reject Nonessential   x 989  -> 1357   clipped at the 1265px banner edge
 *     Manage Preferences    x 1363 -> 1730   entirely off screen
 *
 * A desktop visitor could accept and could not decline. Mobile was never
 * affected (measured 3 x 118px at 390px).
 *
 * THE MECHANISM, asserted below rather than remembered:
 *   - the plugin ships `.wpconsent-banner-button { width: 100% }`, correct for
 *     ITS column footer;
 *   - `inc/consent-banner-compact.php` turns that footer into a ROW;
 *   - in a row that percentage means "each button as wide as the whole footer",
 *     and it is resolved AFTER flex-shrink;
 *   - `flex: 1 1 0` (the sub-782px rule) sets flex-basis 0 and therefore
 *     overrides `width`, which is why mobile survived;
 *   - `flex: 0 0 auto` alone (the old >=782px rule) restores flex-basis: auto
 *     and hands sizing straight back to the plugin's `width: 100%`.
 *
 * THE INVARIANT: anywhere this file sets `flex: 0 0 auto` on a banner button,
 * it must also neutralise the inherited percentage with `width: auto`. Section
 * 2 asserts exactly that, because that pairing is the whole fix.
 *
 * Section 4 is the guard that matters most for safety: this file is a
 * POSITION-AND-SIZE file. It must never acquire consent semantics.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_cbdl_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

if (!function_exists('bhp_consent_banner_compact_css')) {
    WP_CLI::error('bhp_consent_banner_compact_css() is not loaded — inc/consent-banner-compact.php did not run.');
}

$css = bhp_consent_banner_compact_css();

/** Returns the body of the first `@media (min-width: 782px)` block, or ''. */
function bhp_cbdl_desktop_block($css) {
    $start = strpos($css, '@media (min-width: 782px)');
    if (false === $start) {
        return '';
    }
    $open = strpos($css, '{', $start);
    if (false === $open) {
        return '';
    }
    $depth = 0;
    $len   = strlen($css);
    for ($i = $open; $i < $len; $i++) {
        if ('{' === $css[$i]) {
            $depth++;
        } elseif ('}' === $css[$i]) {
            $depth--;
            if (0 === $depth) {
                return substr($css, $open + 1, $i - $open - 1);
            }
        }
    }
    return '';
}

/** Returns everything OUTSIDE any @media block — the mobile-first base. */
function bhp_cbdl_base_block($css) {
    $out   = '';
    $depth = 0;
    $len   = strlen($css);
    $in_media = false;
    for ($i = 0; $i < $len; $i++) {
        if (!$in_media && '@' === $css[$i] && 0 === substr_compare($css, '@media', $i, 6)) {
            $in_media = true;
            $depth    = 0;
            continue;
        }
        if ($in_media) {
            if ('{' === $css[$i]) {
                $depth++;
            } elseif ('}' === $css[$i]) {
                $depth--;
                if (0 === $depth) {
                    $in_media = false;
                }
            }
            continue;
        }
        $out .= $css[$i];
    }
    return $out;
}

$desktop = bhp_cbdl_desktop_block($css);
$base    = bhp_cbdl_base_block($css);

// ---------------------------------------------------------------------
// 1. The stylesheet is structurally sound and still has both breakpoints
// ---------------------------------------------------------------------
bhp_cbdl_assert($failures, '1.1 CSS braces balance', substr_count($css, '{') === substr_count($css, '}'));
bhp_cbdl_assert($failures, '1.2 the >=782px desktop block exists and is non-empty', '' !== trim($desktop));
bhp_cbdl_assert($failures, '1.3 a mobile-first base block exists outside any @media', '' !== trim($base));

// ---------------------------------------------------------------------
// 2. THE FIX — the flex-basis / percentage pairing
// ---------------------------------------------------------------------
$btn_rule_desktop = '';
if (preg_match('/\.wpconsent-banner-footer\s+\.wpconsent-banner-button\s*\{([^}]*)\}/', $desktop, $m)) {
    $btn_rule_desktop = $m[1];
}
bhp_cbdl_assert($failures, '2.1 the desktop block styles .wpconsent-banner-footer .wpconsent-banner-button', '' !== $btn_rule_desktop);
bhp_cbdl_assert(
    $failures,
    '2.2 the desktop button rule neutralises the plugin percentage with width:auto !important',
    (bool) preg_match('/width\s*:\s*auto\s*!important/', $btn_rule_desktop)
);
bhp_cbdl_assert(
    $failures,
    '2.3 THE INVARIANT — no `flex: 0 0 auto` on a banner button anywhere in this stylesheet without `width: auto` in the same rule',
    (function () use ($css) {
        if (!preg_match_all('/([^{}]*\.wpconsent-banner-button[^{}]*)\{([^}]*)\}/', $css, $all, PREG_SET_ORDER)) {
            return false;
        }
        foreach ($all as $rule) {
            $body = $rule[2];
            if (preg_match('/flex\s*:\s*0\s+0\s+auto/', $body) && !preg_match('/width\s*:\s*auto/', $body)) {
                return false;
            }
        }
        return true;
    })()
);
bhp_cbdl_assert(
    $failures,
    '2.4 the footer cannot shrink below its buttons on desktop (flex: 0 0 auto + width: auto)',
    (bool) preg_match('/\.wpconsent-banner-footer\s*\{[^}]*flex\s*:\s*0\s+0\s+auto[^}]*width\s*:\s*auto[^}]*\}/s', $desktop)
);
bhp_cbdl_assert(
    $failures,
    '2.5 the message body is the element allowed to absorb a narrow desktop viewport',
    (bool) preg_match('/\.wpconsent-banner-body\s*\{[^}]*min-width\s*:\s*0[^}]*\}/s', $desktop)
);
bhp_cbdl_assert(
    $failures,
    '2.6 desktop labels stay on one line inside the fixed-height button box',
    (bool) preg_match('/white-space\s*:\s*nowrap/', $btn_rule_desktop)
);

// ---------------------------------------------------------------------
// 3. Mobile — the path that was never broken must stay unbroken
// ---------------------------------------------------------------------
$btn_rule_base = '';
if (preg_match('/\.wpconsent-banner-footer\s+\.wpconsent-banner-button\s*\{([^}]*)\}/', $base, $m)) {
    $btn_rule_base = $m[1];
}
bhp_cbdl_assert($failures, '3.1 the base block still styles the banner buttons', '' !== $btn_rule_base);
bhp_cbdl_assert(
    $failures,
    '3.2 the base rule keeps `flex: 1 1 0` — the zero flex-basis is what overrides the plugin percentage below 782px',
    (bool) preg_match('/flex\s*:\s*1\s+1\s+0/', $btn_rule_base)
);
bhp_cbdl_assert(
    $failures,
    '3.3 the footer is still forced to a single row — the coupling that makes section 2 necessary',
    (bool) preg_match('/\.wpconsent-banner-footer\s*\{[^}]*flex-direction\s*:\s*row\s*!important[^}]*\}/s', $base)
);
bhp_cbdl_assert(
    $failures,
    '3.4 the banner is still bottom-anchored, so it never covers the site header again',
    (bool) preg_match('/\.wpconsent-banner\s*\{[^}]*bottom\s*:\s*0\s*!important[^}]*\}/s', $base)
);

// ---------------------------------------------------------------------
// 4. SAFETY — this file styles. It must never acquire consent semantics.
// ---------------------------------------------------------------------
$file   = get_template_directory() . '/inc/consent-banner-compact.php';
$source = file_exists($file) ? file_get_contents($file) : '';
bhp_cbdl_assert($failures, '4.1 the source file is readable', '' !== $source);

$forbidden = [
    '4.2 no button is hidden by this stylesheet'            => '/\.wpconsent-(accept|cancel|preferences)-all[^{}]*\{[^}]*display\s*:\s*none/s',
    '4.3 no consent control is programmatically clicked'    => '/\.click\s*\(/',
    '4.4 no handler is bound to a consent control'          => '/addEventListener\s*\(\s*[\'"]click/',
    '4.5 no consent cookie is written'                      => '/document\.cookie\s*=/',
    '4.6 no consent state is written to storage'            => '/(localStorage|sessionStorage)\s*\.\s*setItem/',
    '4.7 the shadow tree is never rewritten wholesale'      => '/innerHTML\s*=/',
    '4.8 no node is removed from the shadow tree'           => '/\.remove(Child)?\s*\(/',
    '4.9 the plugin JS API is never called from this file'  => '/window\s*\.\s*wpconsent\s*\./',
];
foreach ($forbidden as $label => $pattern) {
    bhp_cbdl_assert($failures, $label, !preg_match($pattern, $source));
}

bhp_cbdl_assert(
    $failures,
    '4.10 the only mutation this file performs on the shadow root is appending its own <style>',
    1 === preg_match_all('/sr\.appendChild\s*\(/', $source)
        && (bool) preg_match('/id\s*=\s*[\'"]bhp-consent-compact-style[\'"]/', $source)
);
bhp_cbdl_assert(
    $failures,
    '4.11 the body-class mirror that lifts the sticky bars is intact',
    (bool) preg_match('/classList\.toggle\(\s*[\'"]bhp-consent-banner-visible[\'"]/', $source)
        && (bool) preg_match('/wpconsent-banner-visible/', $source)
);
bhp_cbdl_assert(
    $failures,
    '4.12 the file still renders nothing at all when WPConsent is absent',
    (bool) preg_match('/function_exists\(\s*[\'"]wpconsent[\'"]\s*\)/', $source)
        && (bool) preg_match('/if\s*\(\s*!\s*bhp_consent_banner_compact_active\(\)\s*\)\s*\{\s*return;/s', $source)
);

// ---------------------------------------------------------------------
// 5. The emitted footer markup actually carries the stylesheet
// ---------------------------------------------------------------------
if (bhp_consent_banner_compact_active()) {
    ob_start();
    bhp_consent_banner_compact_script();
    $emitted = ob_get_clean();
    bhp_cbdl_assert($failures, '5.1 the footer script is emitted when WPConsent is active', false !== strpos($emitted, 'bhp-consent-compact'));
    bhp_cbdl_assert($failures, '5.2 the emitted script carries the desktop width:auto declaration', false !== strpos($emitted, 'width: auto !important'));
    bhp_cbdl_assert($failures, '5.3 the emitted CSS is JSON-encoded, not raw-interpolated', false !== strpos($emitted, 'var CSS = "'));
} else {
    WP_CLI::warning('SKIP: section 5 — WPConsent is not active in this environment, so no footer script is emitted.');
    WP_CLI::warning('SKIP is reported as a skip, not as a pass.');
}

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d consent-banner desktop-layout test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All consent-banner desktop-layout tests passed.');
}
