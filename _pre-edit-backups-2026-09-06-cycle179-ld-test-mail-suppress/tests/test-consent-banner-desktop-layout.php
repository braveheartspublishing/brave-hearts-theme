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
// 6. The 1.19.249 COMPACT BAR at <= 600px ("slim the banner").
//
//    Added because the phone is now the viewport with the tight constraint,
//    and every assertion above was written when only the desktop was.
//
//    THE MEASURED FACTS these guard, from the deployed 1.19.248 build in
//    headless Chrome at an asserted 390 x 664 with cookies cleared:
//        banner  390 x 112, top 552 -> bottom 664
//        hero CTA centre (195, 624.1)
//        document.elementFromPoint(195, 624.1) -> DIV#wpconsent-container
//    The banner was intercepting the tap on the homepage's primary CTA.
//
//    THE ARITHMETIC, asserted rather than remembered: the banner is
//    bottom-anchored, so its bottom edge IS the viewport bottom (664). For the
//    CTA centre at 624.1 to be clickable the banner must be SHORTER than
//    664 - 624.1 = 39.9px. That is a CEILING. A "compact" 48px bar still
//    covers it. 6.3 below is the assertion that stops a future edit from
//    rounding 36 up to a friendlier-looking number and silently re-breaking
//    the CTA.
//
//    PHP cannot lay out a page, so no pixel claim is made here; the pixel
//    result is measured in a real browser and recorded in the release notes.
//    What PHP can prove is that the rules producing it are present, correctly
//    scoped, and shipped in the stylesheet the browser downloads.
// ---------------------------------------------------------------------

/** Returns the body of the first `@media (max-width: 600px)` block, or ''. */
function bhp_cbdl_mobile_block($css) {
    $start = strpos($css, '@media (max-width: 600px)');
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

$mobile = bhp_cbdl_mobile_block($css);

bhp_cbdl_assert($failures, '6.1 the <= 600px compact-bar block exists and is non-empty', '' !== trim($mobile));

$btn_rule_mobile = '';
if (preg_match('/\.wpconsent-banner-footer\s+\.wpconsent-banner-button\s*\{([^}]*)\}/', $mobile, $m)) {
    $btn_rule_mobile = $m[1];
}
bhp_cbdl_assert($failures, '6.2 the compact block styles .wpconsent-banner-footer .wpconsent-banner-button', '' !== $btn_rule_mobile);

/* THE ONE THAT MATTERS. 39.9px is the ceiling; anything at or above it puts
   the banner back over the CTA centre. Both `height` and `max-height` are
   checked, because setting one and leaving the other at the base block's
   112px would produce a bar that is only accidentally short. */
$mobile_banner_rule = '';
if (preg_match('/\.wpconsent-banner\s*\{([^}]*)\}/', $mobile, $m)) {
    $mobile_banner_rule = $m[1];
}
$mobile_h  = preg_match('/[^-]height\s*:\s*([0-9.]+)px/', $mobile_banner_rule, $mh) ? (float) $mh[1] : -1.0;
$mobile_mh = preg_match('/max-height\s*:\s*([0-9.]+)px/', $mobile_banner_rule, $mm) ? (float) $mm[1] : -1.0;

bhp_cbdl_assert(
    $failures,
    sprintf('6.3 the compact bar is shorter than the 39.9px ceiling (height %.1fpx, max-height %.1fpx)', $mobile_h, $mobile_mh),
    $mobile_h > 0 && $mobile_h < 39.9 && $mobile_mh > 0 && $mobile_mh < 39.9
);

/* The 1.19.186 pairing, re-asserted at this breakpoint specifically. Section
   2.3 already scans every button rule in the file, but naming it here means a
   failure points straight at the compact bar instead of at "somewhere". */
bhp_cbdl_assert(
    $failures,
    '6.4 the compact-bar buttons pair flex: 0 0 auto with width: auto (the 1.19.186 fix)',
    (bool) preg_match('/flex\s*:\s*0\s+0\s+auto/', $btn_rule_mobile)
        && (bool) preg_match('/width\s*:\s*auto\s*!important/', $btn_rule_mobile)
);

/* The footer's own width: 100% comes from the PLUGIN's max-width: 767px rule.
   Left alone it eats the entire row and collapses the message to zero width --
   observed, not theorised. */
bhp_cbdl_assert(
    $failures,
    '6.5 the compact-bar footer neutralises the plugin\'s mobile width: 100%',
    (bool) preg_match('/\.wpconsent-banner-footer\s*\{[^}]*width\s*:\s*auto\s*!important[^}]*\}/s', $mobile)
);

/* Buttons must fill the bar. A short button inside a short bar is the one way
   to lose hit area without losing height, and 36px is already below the 40px
   target purely because of the geometry above. */
bhp_cbdl_assert(
    $failures,
    '6.6 the compact-bar buttons fill the bar height (min-height present, >= 32px)',
    preg_match('/min-height\s*:\s*([0-9.]+)px/', $btn_rule_mobile, $bh) && (float) $bh[1] >= 32.0
);

/* ALL THREE CONSENT CONTROLS SURVIVE. This is the safety assertion of the
   section: a "compact" bar is exactly the kind of change that quietly drops
   Reject or Manage, which would be a compliance failure and not a cosmetic
   one. Nothing in the compact block may hide a consent control. */
bhp_cbdl_assert(
    $failures,
    '6.7 the compact block hides no consent button and no footer',
    !preg_match('/\.wpconsent-(banner-footer|accept-all|cancel-all|preferences-all|banner-button)[^{}]*\{[^}]*display\s*:\s*none/s', $mobile)
);
bhp_cbdl_assert(
    $failures,
    '6.8 the compact block hides no banner body or message (the prose is clamped, never removed)',
    !preg_match('/\.wpconsent-banner-(body|message)[^{}]*\{[^}]*display\s*:\s*none/s', $mobile)
);

/* Scope. A compact-bar rule leaking onto the desktop would undo 1.19.186. */
bhp_cbdl_assert(
    $failures,
    '6.9 the compact block declares no min-width media query of its own',
    0 === preg_match('/@media[^{]*min-width/', $mobile)
);

/* And the desktop block must still be there, unchanged in kind. */
bhp_cbdl_assert(
    $failures,
    '6.10 the >= 782px desktop block still caps its own height (desktop untouched)',
    (bool) preg_match('/\.wpconsent-banner\s*\{[^}]*max-height\s*:\s*92px[^}]*\}/s', $desktop)
);

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d consent-banner desktop-layout test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All consent-banner desktop-layout tests passed.');
}
