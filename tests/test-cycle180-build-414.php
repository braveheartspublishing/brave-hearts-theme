<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-414 — Andrew's two notes of founder seal 1486, proven.
 *
 * Theme 1.19.414. ⛔ The bundle plugin is NOT touched by this build and stays
 * at 1.8.93; §3.4 asserts that rather than assuming it.
 *
 * ⛔ WHAT THIS SUITE COVERS, one section per note:
 *      §1  note 1 — the pair landing value heading rendered FOREST ON FOREST.
 *      §2  note 2 — at 390 the colouring PDP's bundle CTA and look-inside rail
 *                   were ~4,000px apart.
 *      §3  version, stamp and the untouched-plugin guard.
 *      §4  the standing guards: no literal palette, no hardcoded product id,
 *          no rehearsal scaffolding, no `!important`.
 *
 * ⭐⭐ THE TWO ASSERTIONS THAT ACTUALLY EARN THEIR KEEP, AND WHY THE OBVIOUS
 *     ONES DO NOT — read this before adding a `strpos()` and calling it covered.
 *
 *     BOTH DEFECTS WERE CASCADE ARITHMETIC, NOT MISSING CODE. In both cases the
 *     correct declaration was ALREADY IN THE STYLESHEET and was LOSING:
 *
 *       note 1  `.bhp-landing-value__heading { color: #fff }`      (0,1,0)
 *               lost to `.bhp-landing h2 { color: var(--bl-forest) }` (0,1,1)
 *       note 2  `.bhp-pdp-left { order: 10 }` was not "too high" — it was the
 *               only named slot BELOW the default bucket every unnamed block
 *               falls into, so 10 and 9 mean exactly the same thing.
 *
 *     ⛔ A TEST THAT ONLY GREPS FOR THE NEW RULE WOULD HAVE PASSED ON THE
 *        BROKEN BUILD TOO, because the rule it greps for was already there.
 *        So this suite computes the two things that actually decide the
 *        outcome:
 *          §1.4  the WCAG contrast ratio, from the resolved colour values;
 *          §2.7  CSS specificity, from the selectors themselves, and asserts
 *                the ORDERING RELATION the fix depends on.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE, stated so nobody over-claims: this is a
 *   static analysis of the shipped stylesheets plus live registry reads. It
 *   does not prove what a browser painted. The browser evidence — element
 *   order with top offsets and computed colours at asserted innerWidths of 390
 *   and 1440 — is captured separately and filed with the build report.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-414.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no variation,
 *    no term, no option, no post, no cart, no order, no file.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ COUNTERS LIVE IN $GLOBALS, NOT IN `global $x`. `wp eval-file` includes
 *     this file INSIDE A FUNCTION, so every top-level variable here is a LOCAL
 *     and a helper declaring `global` would increment a DIFFERENT variable from
 *     the one the summary and `exit()` read — printing "0 failed" however many
 *     assertions failed. Observed on this project at 1.19.412, not theorised.
 */
$GLOBALS['b414_failures'] = 0;
$GLOBALS['b414_passes']   = 0;
$GLOBALS['b414_skips']    = 0;

function b414_assert($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['b414_passes']++;
        echo "  PASS  {$label}\n";
    } else {
        $GLOBALS['b414_failures']++;
        echo "  FAIL  {$label}" . ('' !== $detail ? "  [{$detail}]" : '') . "\n";
    }
}

function b414_skip($label, $why) {
    $GLOBALS['b414_skips']++;
    echo "  SKIP  {$label}  [{$why}]\n";
}

/**
 * CSS specificity of a single (comma-free) selector, as [ids, classes, elements].
 *
 * ⛔ DELIBERATELY NARROW. It handles exactly what this codebase's PDP selectors
 *    use — type, class, id, child and descendant combinators — and nothing
 *    else. It is NOT a general CSS parser and must not be reached for as one.
 *    Attribute selectors, pseudo-classes and `:is()/:where()/:not()` are not
 *    supported; if a future selector needs them, this returns a WRONG number
 *    silently, so §2.7 also asserts the guard below.
 */
function b414_specificity($selector) {
    $sel = trim(preg_replace('/\s*>\s*/', ' ', (string) $selector));
    $ids = 0;
    $classes = 0;
    $elements = 0;
    foreach (preg_split('/\s+/', $sel) as $part) {
        if ('' === $part) {
            continue;
        }
        $ids     += substr_count($part, '#');
        $classes += substr_count($part, '.');
        // A leading type selector: the text before the first '.', '#' or the
        // whole part when there is none. '*' contributes nothing.
        $head = preg_split('/[.#]/', $part)[0];
        if ('' !== $head && '*' !== $head) {
            $elements++;
        }
    }
    return array($ids, $classes, $elements);
}

/** True when specificity $a beats specificity $b outright (no source-order tie). */
function b414_beats(array $a, array $b) {
    for ($i = 0; $i < 3; $i++) {
        if ($a[$i] !== $b[$i]) {
            return $a[$i] > $b[$i];
        }
    }
    return false; // identical: decided by source order, which is NOT "beats".
}

/** WCAG 2.x relative luminance of an #rrggbb string. */
function b414_luminance($hex) {
    $hex = ltrim((string) $hex, '#');
    if (3 === strlen($hex)) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $chan = array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    );
    $lin = array();
    foreach ($chan as $c) {
        $s = $c / 255;
        $lin[] = ($s <= 0.03928) ? ($s / 12.92) : pow((($s + 0.055) / 1.055), 2.4);
    }
    return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
}

/** WCAG contrast ratio between two #rrggbb strings. */
function b414_contrast($fg, $bg) {
    $l1 = b414_luminance($fg);
    $l2 = b414_luminance($bg);
    $hi = max($l1, $l2);
    $lo = min($l1, $l2);
    return ($hi + 0.05) / ($lo + 0.05);
}

/**
 * Every `@media (max-width: 600px) { ... }` body in $css, brace-matched.
 *
 * ⛔ BRACE MATCHING, NOT A REGEX, and the reason is this codebase specifically:
 *    the phone blocks here contain nested rules and essay-length comments, and
 *    a lazy `\{(.*?)\}` would close the media query at the first inner brace
 *    and then cheerfully report that a rule sitting OUTSIDE the breakpoint was
 *    inside it. That is the exact failure this section exists to catch.
 */
function b414_media_600_blocks($css) {
    $blocks = array();
    $offset = 0;
    while (false !== ($at = strpos($css, '@media (max-width: 600px)', $offset))) {
        $open = strpos($css, '{', $at);
        if (false === $open) {
            break;
        }
        $depth = 0;
        $len   = strlen($css);
        for ($i = $open; $i < $len; $i++) {
            if ('{' === $css[$i]) {
                $depth++;
            } elseif ('}' === $css[$i]) {
                $depth--;
                if (0 === $depth) {
                    $blocks[] = substr($css, $open + 1, $i - $open - 1);
                    $offset   = $i + 1;
                    continue 2;
                }
            }
        }
        break;
    }
    return $blocks;
}

/**
 * Strip CSS block comments, so "is this rule LIVE?" is the question asked.
 *
 * ⛔ NOT OPTIONAL ON THIS PROJECT. The house style preserves every superseded
 *    rule verbatim in a comment directly above its replacement, so a raw
 *    `strpos()` finds the old code inside the very comment that documents its
 *    removal. §0.2 and §0.3 are the integrity controls that prove this
 *    function strips comments and only comments.
 */
function b414_css_code($css) {
    return preg_replace('#/\*.*?\*/#s', '', (string) $css);
}

$b414_theme = get_template_directory();
$b414_src   = function ($rel) use ($b414_theme) {
    $path = $b414_theme . '/' . ltrim($rel, '/');
    return is_readable($path) ? (string) file_get_contents($path) : '';
};

$b414_pair_css      = $b414_src('assets/css/bundle-pair-landing.css');
$b414_pair_min      = $b414_src('assets/css/bundle-pair-landing.min.css');
$b414_pdp_css       = $b414_src('assets/css/pdp-content.css');
$b414_pdp_min       = $b414_src('assets/css/pdp-content.min.css');
$b414_tpl_css       = $b414_src('assets/css/product-template.css');
$b414_style_css     = $b414_src('style.css');
$b414_style_min     = $b414_src('style.min.css');
$b414_functions     = $b414_src('functions.php');

$b414_pair_code = b414_css_code($b414_pair_css);
$b414_pdp_code  = b414_css_code($b414_pdp_css);
$b414_tpl_code  = b414_css_code($b414_tpl_css);

echo "\n=== CYCLE180-LD-BUILD-414 — founder seal 1486, notes 1 and 2 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §0 · INTEGRITY CONTROLS — the helpers above must actually work, or every
        assertion built on them is decoration.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §0 integrity controls on this suite's own helpers ---\n";

b414_assert('0.1 the four stylesheets under test are all readable',
    '' !== $b414_pair_css && '' !== $b414_pdp_css && '' !== $b414_tpl_css && '' !== $b414_style_css);

b414_assert('0.2 the comment stripper strips (control: a known comment is gone)',
    false !== strpos($b414_pair_css, 'SPECIFICITY LOSS')
    && false === strpos($b414_pair_code, 'SPECIFICITY LOSS'));

b414_assert('0.3 the comment stripper does NOT eat rules (control: a known selector survives)',
    false !== strpos($b414_pair_code, '.bhp-pair-landing__title'));

b414_assert('0.4 specificity: (0,2,0) beats (0,1,1) — the note-1 arithmetic',
    b414_beats(b414_specificity('.bhp-pair-landing .bhp-landing-value__heading'),
               b414_specificity('.bhp-landing h2')));

b414_assert('0.5 specificity: identical selectors do NOT "beat" each other',
    !b414_beats(b414_specificity('.a .b'), b414_specificity('.c .d')));

b414_assert('0.6 contrast helper: white on black is 21:1',
    abs(b414_contrast('#ffffff', '#000000') - 21.0) < 0.01,
    (string) b414_contrast('#ffffff', '#000000'));

b414_assert('0.7 contrast helper: a colour on itself is 1:1 (the defect signature)',
    abs(b414_contrast('#173f2f', '#173f2f') - 1.0) < 0.0001);

b414_assert('0.8 the brace matcher finds at least one max-width:600px block in pdp-content.css',
    count(b414_media_600_blocks($b414_pdp_css)) >= 1,
    'blocks: ' . count(b414_media_600_blocks($b414_pdp_css)));

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · NOTE 1 — the value heading was forest type on a forest band.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §1 note 1: the pair landing value heading is readable ---\n";

b414_assert('1.1 the scoped heading rule is LIVE in bundle-pair-landing.css (not only in a comment)',
    (bool) preg_match('/\.bhp-pair-landing\s+\.bhp-landing-value__heading\s*\{[^}]*color\s*:\s*var\(--bl-ivory\)/s', $b414_pair_code));

b414_assert('1.2 the built minified sheet carries it too (the CSS build step ran)',
    (bool) preg_match('/\.bhp-pair-landing\s+\.bhp-landing-value__heading\s*\{[^}]*color\s*:\s*var\(--bl-ivory\)/s', $b414_pair_min));

b414_assert('1.3 the new rule declares COLOUR ONLY — no layout, no content, no size',
    (bool) preg_match('/\.bhp-pair-landing\s+\.bhp-landing-value__heading\s*\{\s*color\s*:\s*var\(--bl-ivory\)\s*;\s*\}/s', $b414_pair_code));

/*
 * ⭐⭐ THE ASSERTION THAT WOULD HAVE CAUGHT THE DEFECT. Resolved values, not
 *     token names: `--bl-ivory` is declared #FFFDF8 by the PLUGIN's
 *     bundle-landing.css, and `--bl-forest` -> `--color-forest` -> #173f2f in
 *     style.css. Both are read out of the shipped files below rather than
 *     retyped here, so a palette change breaks this test instead of silently
 *     re-breaking the page.
 */
/*
 * ⛔⛔ THE PLUGIN SHEET IS NOT WHERE THE REPOSITORY PUTS IT, AND THIS COST A
 *     RED RUN. In the repo the bundle plugin sits UNDER the theme directory at
 *     `plugins/brave-hearts-bundle-pricing/`; on a server it is installed at
 *     `wp-content/plugins/`, and the theme ZIP excludes `plugins/` entirely
 *     (entry gate: "plugins/ in theme zip MUST 0"). The first run of this
 *     suite on staging therefore read an EMPTY string and reported §1.4a,
 *     §1.7 and §1.8 as failing while the shipped code was correct.
 *
 * ⭐ RESOLVED IN INSTALL ORDER, and the repo path is the LAST resort so a
 *    developer running this locally still gets a real answer:
 *      1. WP_PLUGIN_DIR      — the installed location, what staging/production use
 *      2. WPMU_PLUGIN_DIR    — completeness; the bundle plugin is not an mu-plugin today
 *      3. the theme-relative repo path
 *    §1.4a below fails loudly if none of them resolve, rather than skipping —
 *    a contrast test that quietly does not run is worse than one that is red.
 */
$b414_plugin_css = '';
$b414_plugin_css_path = '';
$b414_candidates = array();
if (defined('WP_PLUGIN_DIR')) {
    $b414_candidates[] = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/assets/bundle-landing.css';
}
if (defined('WPMU_PLUGIN_DIR')) {
    $b414_candidates[] = WPMU_PLUGIN_DIR . '/brave-hearts-bundle-pricing/assets/bundle-landing.css';
}
$b414_candidates[] = $b414_theme . '/plugins/brave-hearts-bundle-pricing/assets/bundle-landing.css';
foreach ($b414_candidates as $b414_cand) {
    if (is_readable($b414_cand)) {
        $b414_plugin_css_path = $b414_cand;
        $b414_plugin_css      = (string) file_get_contents($b414_cand);
        break;
    }
}
b414_assert('1.4-pre the plugin stylesheet resolves somewhere readable',
    '' !== $b414_plugin_css,
    'tried: ' . implode(' | ', $b414_candidates));
echo "        (plugin sheet read from: " . ('' !== $b414_plugin_css_path ? $b414_plugin_css_path : 'NOWHERE') . ")\n";

$b414_ivory  = preg_match('/--bl-ivory:\s*(#[0-9A-Fa-f]{3,6})/', $b414_plugin_css, $m1) ? $m1[1] : '';
$b414_forest = preg_match('/--color-forest:\s*(#[0-9A-Fa-f]{3,6})/', $b414_style_css, $m2) ? $m2[1] : '';

b414_assert('1.4a both palette values resolve out of the shipped files',
    '' !== $b414_ivory && '' !== $b414_forest, "ivory={$b414_ivory} forest={$b414_forest}");

if ('' !== $b414_ivory && '' !== $b414_forest) {
    $b414_ratio = b414_contrast($b414_ivory, $b414_forest);
    b414_assert(
        '1.4b ⭐ the heading colour on the value band clears 4.5:1',
        $b414_ratio >= 4.5,
        sprintf('%s on %s = %.2f:1', $b414_ivory, $b414_forest, $b414_ratio)
    );
    echo sprintf("        (measured from the shipped palette: %s on %s = %.2f:1)\n",
        $b414_ivory, $b414_forest, $b414_ratio);

    b414_assert(
        '1.4c ⛔ the BROKEN pairing is still proven broken (forest on forest = 1.00:1)',
        abs(b414_contrast($b414_forest, $b414_forest) - 1.0) < 0.0001
    );
} else {
    b414_skip('1.4b the heading colour clears 4.5:1', 'palette values unresolved');
    b414_skip('1.4c the broken pairing is still proven broken', 'palette values unresolved');
}

b414_assert('1.5 the fix is SPECIFICITY, not `!important`',
    false === stripos($b414_pair_code, '!important'));

b414_assert('1.6 ⛔ the new rule beats the plugin selector that caused the defect',
    b414_beats(b414_specificity('.bhp-pair-landing .bhp-landing-value__heading'),
               b414_specificity('.bhp-landing h2')));

/*
 * ⛔ THE PLUGIN IS NOT EDITED BY THIS BUILD. Both sides of the original
 *    cascade must still be present, verbatim, in the plugin sheet — if a later
 *    pass "helpfully" fixes the plugin as well, this suite says so instead of
 *    letting two fixes silently fight.
 */
b414_assert('1.7 the plugin sheet still carries its generic heading colour (untouched)',
    false !== strpos($b414_plugin_css, '.bhp-landing h1, .bhp-landing h2, .bhp-landing h3'));

b414_assert('1.8 the plugin sheet still carries its own #fff declaration (untouched)',
    (bool) preg_match('/\.bhp-landing-value__heading\s*\{[^}]*color:\s*#fff/', $b414_plugin_css));

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · NOTE 2 — the CTA and the rail, 4,000px apart at 390.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §2 note 2: the bundle CTA and the look-inside rail are adjacent at 390 ---\n";

b414_assert('2.1 the colouring body class is emitted by a LIVE gate in functions.php',
    (bool) preg_match("/bhp_colouring_slug_for_product\(get_queried_object_id\(\)\)\s*\)\s*\{\s*\\\$classes\[\]\s*=\s*'bhp-colouring-pdp';/s",
        preg_replace('#/\*.*?\*/#s', '', $b414_functions)));

b414_assert('2.2 the gate is registry-driven — the predicate exists',
    function_exists('bhp_colouring_slug_for_product'));

if (function_exists('bhp_colouring_slug_for_product') && function_exists('bhp_colouring_product_ids')) {
    $b414_col_ids = (array) bhp_colouring_product_ids();
    b414_assert('2.3a the colouring registry resolves at least one product id',
        !empty($b414_col_ids), 'ids: ' . wp_json_encode($b414_col_ids));

    $b414_all_col = true;
    foreach ($b414_col_ids as $b414_id) {
        if (null === bhp_colouring_slug_for_product((int) $b414_id)) {
            $b414_all_col = false;
        }
    }
    b414_assert('2.3b every registered colouring product resolves to a slug (the class WILL be emitted)',
        $b414_all_col);

    /*
     * ⛔ THE OTHER HALF, AND IT IS THE HALF THE BRIEF CARES ABOUT: the chapter
     *    books must NOT get the class, or the fix reaches pages the brief
     *    requires to be unchanged. Ids are read from the live store by slug
     *    rather than hardcoded, so the assertion survives a migration.
     */
    $b414_non_col = array();
    foreach (array(
        'adventures-of-charlotte-and-henry-the-mariana-trench-paperback',
        'adventures-of-charlotte-and-henry-mount-everest-paperback',
        'adventures-of-charlotte-and-henry-the-amazon-paperback',
        'the-adventure-activity-book',
    ) as $b414_slug) {
        $b414_page = get_page_by_path($b414_slug, OBJECT, 'product');
        if ($b414_page instanceof WP_Post) {
            $b414_non_col[$b414_slug] = bhp_colouring_slug_for_product((int) $b414_page->ID);
        }
    }
    if (!empty($b414_non_col)) {
        $b414_leaked = array_filter($b414_non_col, function ($v) { return null !== $v; });
        b414_assert('2.4 ⛔ NO chapter book and NO activity book resolves to a colouring slug',
            empty($b414_leaked),
            'leaked: ' . wp_json_encode($b414_leaked) . ' checked: ' . wp_json_encode(array_keys($b414_non_col)));
    } else {
        b414_skip('2.4 no chapter book resolves to a colouring slug', 'no non-colouring products found by slug on this environment');
    }
} else {
    b414_skip('2.3a the colouring registry resolves at least one product id', 'colouring line not loaded');
    b414_skip('2.3b every registered colouring product resolves to a slug', 'colouring line not loaded');
    b414_skip('2.4 no chapter book resolves to a colouring slug', 'colouring line not loaded');
}

/* ═══════════════════════════════════════════════════════════════════════════
   ⛔⛔ AMENDED 2026-09-13 by `CYCLE180-LD-BUILD-416` — §2.5 THROUGH §2.9 NOW
      TEST THE ALL-PRODUCT-PAGE SCOPE, NOT THE COLOURING-ONLY SCOPE.
   ---------------------------------------------------------------------------
   ⭐ WHY, AND IT IS NOT "A RED LIGHT MADE GREEN". 1.19.414 deliberately
      scoped its ordering fix to `.bhp-colouring-pdp` because its own brief
      required the chapter-book PDPs to be UNCHANGED, and it raised the
      chapter-book gap as `CYCLE180-LDB-11` for Andrew rather than absorbing
      it. Andrew decided it on 2026-09-13, founder seal 1497, verbatim:
      "Apply the same move to all pages for the build." 1.19.416 is that
      decision applied, so the selector this suite pins MOVED BY INSTRUCTION.
      The suite is amended to follow it, exactly as `CYCLE180-LD-BUILD-415`
      amended §3's version equalities to floors and for the same reason.

   ⭐⭐ THE PROTECTIVE VALUE IS UNCHANGED, WHICH IS THE TEST OF A LEGITIMATE
      AMENDMENT. Every regression §2.5-§2.9 exists to catch is still caught:
      the default bucket must still move to 9; slot 8 must still be reserved
      for EXACTLY the note, the CTA and the rail (§2.6b still counts, and
      still fails on a fourth); the scoped default must still BEAT the
      unscoped default and still LOSE to the age pill at (0,4,2); the rules
      must still all sit inside `max-width: 600px`; `!important` is still
      forbidden. Only the scope TOKEN changed, from
      `html body.woocommerce.bhp-colouring-pdp` to
      `html body.single-product.woocommerce` — one class for one class, so
      every computed specificity in §2.7 is numerically identical to 414's.

   ⚠ §2.8c CHANGED MEANING SLIGHTLY AND THIS IS THE ONE TO READ TWICE. It
      asserted that `product-template.css` contains no scope class. With the
      token now `single-product`, that file is full of the bare form, so the
      needle is the `html`-PREFIXED form `html body.single-product`, which is
      unique to the 1.19.416 block in `pdp-content.css`. The assertion still
      says what it always said: THIS FIX DID NOT LEAK INTO THE SHARED SHEET.

   ⛔ §2.1-§2.4 ARE NOT AMENDED AND STILL PASS. The `bhp-colouring-pdp` body
      class is still emitted by `bhp_body_classes()` and is still registry
      driven; 1.19.416 simply no longer DEPENDS on it for ordering.

   ⛔ SUPERSEDED NEEDLES, preserved rather than deleted:
        html body\.woocommerce\.bhp-colouring-pdp div\.product      (§2.5a/b, §2.6a)
        'bhp-colouring-pdp div.product > ...'                       (§2.6b counters)
        'html body.woocommerce.bhp-colouring-pdp div.product > *'   (§2.7 default)
        'html body.woocommerce.bhp-colouring-pdp div.product > .bhp-pdp-left'
                                                                   (§2.7 slot 8)
        substr_count(..., 'bhp-colouring-pdp')                      (§2.9a/b/d)
        strpos($b414_tpl_css, 'bhp-colouring-pdp')                  (§2.8c)
   ═══════════════════════════════════════════════════════════════════════════ */

b414_assert('2.5a the scoped default bucket moves to slot 9',
    (bool) preg_match('/html body\.single-product\.woocommerce div\.product > \*,[^{]*\{\s*order:\s*9;\s*\}/s', $b414_pdp_code));

b414_assert('2.5b all three promoted levels are covered (direct, summary, gallery section)',
    (bool) preg_match('/html body\.single-product\.woocommerce div\.product > \*,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.summary > \*,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.bhp-media-gallery--hero > \*\s*\{/s', $b414_pdp_code));

b414_assert('2.6a slot 8 is reserved for exactly the note, the CTA and the rail',
    (bool) preg_match('/html body\.single-product\.woocommerce div\.product > \.bhp-media-gallery--hero > \.bhp-look-inside__note,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.summary > \.bhp-offer--product,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.bhp-pdp-left\s*\{\s*order:\s*8;\s*\}/s', $b414_pdp_code));

b414_assert('2.6b no fourth selector crept into the reserved slot',
    3 === substr_count($b414_pdp_code, 'html body.single-product.woocommerce div.product > .bhp-media-gallery--hero > .bhp-look-inside__note')
        + substr_count($b414_pdp_code, 'html body.single-product.woocommerce div.product > .summary > .bhp-offer--product')
        + substr_count($b414_pdp_code, 'html body.single-product.woocommerce div.product > .bhp-pdp-left'),
    'selector occurrences: ' . (substr_count($b414_pdp_code, 'html body.single-product.woocommerce div.product > .bhp-media-gallery--hero > .bhp-look-inside__note')
        + substr_count($b414_pdp_code, 'html body.single-product.woocommerce div.product > .summary > .bhp-offer--product')
        + substr_count($b414_pdp_code, 'html body.single-product.woocommerce div.product > .bhp-pdp-left')));

/*
 * ⭐⭐ §2.7 — THE ARITHMETIC THE WHOLE FIX RESTS ON, computed rather than
 *     asserted by eye. The scoped default must beat the unscoped default AND
 *     must LOSE to every named slot rule. If either half flips, slots 1-7
 *     collapse into the default bucket and the first screen rearranges itself.
 */
echo "\n--- §2.7 the specificity ordering, computed from the selectors ---\n";

$b414_scoped_default = 'html body.single-product.woocommerce div.product > *';
$b414_scoped_slot8   = 'html body.single-product.woocommerce div.product > .bhp-pdp-left';
$b414_unscoped_def   = 'body.single-product.woocommerce div.product > *';
$b414_named_title    = 'body.single-product.woocommerce div.product > .summary > .product_title';
$b414_unscoped_rail  = 'body.single-product.woocommerce div.product > .bhp-pdp-left';
/*
 * ⭐⭐ THE BINDING CASE. The age pill is the LEAST specific of the named
 *    first-screen slots — a descendant selector with no `.summary` in it, so
 *    (0,4,2) against the title's (0,5,2). If the scoped default ever beats
 *    ANY named slot it will beat this one first, which is why it is asserted
 *    separately rather than folded into the title check.
 */
$b414_named_age      = 'body.single-product.woocommerce div.product .bhp-product-value-prop__age';

foreach (array(
    'scoped default' => $b414_scoped_default,
    'scoped slot 8'  => $b414_scoped_slot8,
    'unscoped def'   => $b414_unscoped_def,
    'named title'    => $b414_named_title,
    'named age'      => $b414_named_age,
    'unscoped rail'  => $b414_unscoped_rail,
) as $b414_lbl => $b414_sel) {
    echo sprintf("        %-15s %s\n", $b414_lbl, '(' . implode(',', b414_specificity($b414_sel)) . ')');
}

b414_assert('2.7a the scoped default BEATS the unscoped default (or the fix never applies)',
    b414_beats(b414_specificity($b414_scoped_default), b414_specificity($b414_unscoped_def)));

b414_assert('2.7b ⛔ the scoped default LOSES to the named title slot (or the first screen breaks)',
    b414_beats(b414_specificity($b414_named_title), b414_specificity($b414_scoped_default)));

b414_assert('2.7b2 ⛔⛔ the scoped default LOSES to the BINDING case, the age pill at (0,4,2)',
    b414_beats(b414_specificity($b414_named_age), b414_specificity($b414_scoped_default)),
    'age=(' . implode(',', b414_specificity($b414_named_age)) . ') scoped=('
        . implode(',', b414_specificity($b414_scoped_default)) . ')');

b414_assert('2.7c the scoped slot-8 rule BEATS the unscoped rail rule (order 10 is overridden)',
    b414_beats(b414_specificity($b414_scoped_slot8), b414_specificity($b414_unscoped_rail)));

b414_assert('2.7d the scoped slot-8 rule BEATS the scoped default (the three pins win)',
    b414_beats(b414_specificity($b414_scoped_slot8), b414_specificity($b414_scoped_default)));

/*
 * ⛔ The specificity helper is narrow on purpose. If a selector ever grows a
 *    pseudo-class or an attribute test, the numbers above become fiction, so
 *    the guard refuses the combination outright rather than reporting a green
 *    run on an unanalysable selector.
 */
b414_assert('2.7e ⛔ guard: no scoped selector uses syntax the specificity helper cannot analyse',
    !preg_match('/html body\.single-product[^,{]*[\[:]/', $b414_pdp_code));

echo "\n--- §2.8 the blast radius: what this build must NOT have changed ---\n";

b414_assert('2.8a the UNSCOPED rail rule is still order 10, verbatim (test-cycle179-build-391 §3 depends on it)',
    (bool) preg_match('/body\.single-product\.woocommerce div\.product > \.bhp-pdp-left \{\s*order:\s*10;\s*\}/s', $b414_pdp_code));

b414_assert('2.8b the UNSCOPED default bucket in product-template.css is still order 8',
    (bool) preg_match('/body\.single-product\.woocommerce div\.product > \*,\s*'
        . 'body\.single-product\.woocommerce div\.product > \.summary > \*,\s*'
        . 'body\.single-product\.woocommerce div\.product > \.bhp-media-gallery--hero > \*\s*\{\s*order:\s*8;/s', $b414_tpl_code));

b414_assert('2.8c product-template.css was NOT edited by this build — no scope class appears in it',
    false === strpos($b414_tpl_css, 'html body.single-product'));

b414_assert('2.8d the named first-screen slots are untouched (title 3, age 4, formats 5, hook 7)',
    (bool) preg_match('/> \.summary > \.product_title \{[^}]*order:\s*3;/s', $b414_tpl_code)
    && (bool) preg_match('/\.bhp-product-value-prop__age \{[^}]*order:\s*4;/s', $b414_tpl_code)
    && (bool) preg_match('/> \.summary > \.bhp-formats \{[^}]*order:\s*5;/s', $b414_tpl_code)
    && (bool) preg_match('/\.bhp-product-value-prop__hook \{[^}]*order:\s*7;/s', $b414_tpl_code));

/*
 * ⛔⛔ DESKTOP. Every scoped rule must sit inside a `max-width: 600px` block.
 *     A single one outside it would re-order the 901px two-column grid, which
 *     the brief forbids in as many words.
 */
$b414_600 = implode("\n", b414_media_600_blocks($b414_pdp_css));
$b414_scoped_total  = substr_count($b414_pdp_css, 'html body.single-product');
$b414_scoped_inside = substr_count($b414_600, 'html body.single-product');
$b414_scoped_code_total  = substr_count($b414_pdp_code, 'html body.single-product');
$b414_scoped_code_inside = substr_count(b414_css_code($b414_600), 'html body.single-product');

b414_assert('2.9a ⛔ EVERY live scoped selector sits inside a max-width:600px block (desktop untouched)',
    $b414_scoped_code_total > 0 && $b414_scoped_code_total === $b414_scoped_code_inside,
    "live occurrences total={$b414_scoped_code_total} inside={$b414_scoped_code_inside}");

b414_assert('2.9b the scoped block is not empty (control: the count is non-zero)',
    $b414_scoped_code_total >= 6, "live occurrences: {$b414_scoped_code_total}");

b414_assert('2.9c the desktop grid placement in style.css is untouched',
    (bool) preg_match('/\.woocommerce div\.product > \.bhp-pdp-left \{[^}]*grid-column:\s*1;[^}]*grid-row:\s*2;/s', $b414_style_css));

b414_assert('2.9d the built minified pdp sheet carries the scoped rules',
    substr_count($b414_pdp_min, 'html body.single-product') === $b414_scoped_code_total,
    'min occurrences: ' . substr_count($b414_pdp_min, 'html body.single-product'));

b414_assert('2.9e the order fix uses no `!important` either',
    !preg_match('/html body\.single-product[^}]*!important/s', $b414_pdp_code));

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · VERSION, STAMP AND THE UNTOUCHED-PLUGIN GUARD
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §3 version, stamp, plugin guard ---\n";

/*
 * ⛔ SUPERSEDED 2026-09-12 BY `CYCLE180-LD-BUILD-415` — THE THREE VERSION ROWS
 *    IN THIS SECTION WERE EQUALITIES AND ALL THREE FIRED ON THE NEXT BUILD.
 *    They are preserved verbatim, struck, rather than deleted:
 *
 *      ~~b414_assert('3.1 style.css declares Version: 1.19.414',
 *          (bool) preg_match('/^Version:\s*1\.19\.414\s*$/m', $b414_style_css));~~
 *      ~~b414_assert('3.3 the shipped theme reports 1.19.414 to WordPress',
 *          '1.19.414' === (string) wp_get_theme(get_template())->get('Version'), ...);~~
 *      ~~b414_assert('3.4 ⛔ the bundle plugin is UNCHANGED at 1.8.93 — this
 *          build claimed no plugin version',
 *          defined('BHP_BUNDLE_PRICING_VERSION') && '1.8.93' === ..., ...);~~
 *
 * ⭐ WHY THEY WERE WRONG TO BE EQUALITIES, AND IT IS NOT THAT THEY WERE TOO
 *    STRICT. A suite named for a build is a record of WHAT THAT BUILD DID, and
 *    it keeps earning its place only while every row still describes an
 *    invariant. "The theme is exactly 1.19.414" stopped being an invariant the
 *    moment 1.19.415 was authorised — it is a statement about a MOMENT. The
 *    rows below assert the thing that is actually durable: this build's work is
 *    PRESENT AND NOT ROLLED BACK.
 *
 * ⛔ THE REGRESSION THESE ROWS EXIST TO CATCH IS STILL CAUGHT. A deploy that
 *    silently shipped an older tree — the `CYCLE180-LD-BUILD-413` temp-index
 *    accident, which downgraded the plugin by two versions and reported
 *    "Plugin updated successfully" while doing it — lands BELOW the floor and
 *    still fails here. What no longer fails is a legitimate forward release.
 *
 * ⚠ §3.2, THE `source-md5` / CRLF TRAP, IS DELIBERATELY LEFT EXACTLY AS IT IS.
 *    It is not a version pin: it asserts that the shipped `style.min.css` was
 *    built from the shipped `style.css`, which is an invariant of EVERY build
 *    and must never become a floor. It passed on 1.19.415 unchanged.
 */
b414_assert('3.1 style.css declares Version: 1.19.414 or later (floor since 1.19.415)',
    (bool) preg_match('/^Version:\s*(1\.19\.\d+)\s*$/m', $b414_style_css, $b414_vm)
        && version_compare($b414_vm[1], '1.19.414', '>='),
    (bool) preg_match('/^Version:\s*([^\r\n]+)$/m', $b414_style_css, $b414_vs) ? trim($b414_vs[1]) : 'unparsed');

$b414_stamp = preg_match('/source-md5:\s*([0-9a-f]{32})/', $b414_style_min, $m3) ? $m3[1] : '';
b414_assert('3.2 ⛔ style.min.css `source-md5` matches style.css exactly (the CRLF trap)',
    '' !== $b414_stamp && $b414_stamp === md5($b414_style_css),
    "stamp={$b414_stamp} actual=" . md5($b414_style_css));

b414_assert('3.3 the shipped theme reports 1.19.414 or later to WordPress (floor since 1.19.415)',
    version_compare((string) wp_get_theme(get_template())->get('Version'), '1.19.414', '>='),
    (string) wp_get_theme(get_template())->get('Version'));

if (function_exists('get_plugin_data') || defined('BHP_BUNDLE_PRICING_VERSION')) {
    /*
     * ⛔ THE ROW NAME CHANGED AS WELL AS THE COMPARISON, AND THAT IS THE POINT.
     *    "the bundle plugin is UNCHANGED at 1.8.93" asserted something about
     *    the 414 BRIEF (which touched no plugin code), not about the product.
     *    1.19.415 ships plugin 1.8.94 on purpose. A row whose NAME still said
     *    "UNCHANGED" while its test allowed change would be worse than either.
     */
    b414_assert('3.4 the bundle plugin is 1.8.93 or later — 414 shipped no plugin change; 415 moves it to 1.8.94 by design',
        defined('BHP_BUNDLE_PRICING_VERSION')
            && version_compare((string) BHP_BUNDLE_PRICING_VERSION, '1.8.93', '>='),
        defined('BHP_BUNDLE_PRICING_VERSION') ? (string) BHP_BUNDLE_PRICING_VERSION : 'undefined');
} else {
    b414_skip('3.4 the bundle plugin is 1.8.93 or later', 'plugin constant not available');
}

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · THE STANDING GUARDS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §4 standing guards ---\n";

/*
 * ⛔ NO SECOND PALETTE. bundle-pair-landing.css's own header forbids a colour
 *    literal: every colour must be a `--bl-*` property already declared by the
 *    plugin sheet. This asserts the header's rule rather than trusting it.
 */
b414_assert('4.1 ⛔ bundle-pair-landing.css still contains NO hex colour literal',
    !preg_match('/#[0-9A-Fa-f]{3,8}\b/', $b414_pair_code),
    (string) (preg_match('/#[0-9A-Fa-f]{3,8}\b/', $b414_pair_code, $m4) ? $m4[0] : ''));

/*
 * ⛔ THE GATE MUST FOLLOW THE REGISTRY, NOT AN ID. The check runs against the
 *    EXECUTABLE text only — the block's explanatory comment legitimately cites
 *    the measured ids 19020 and 333, and a naive scan of the raw file would
 *    fail on its own documentation.
 */
$b414_fn_code = '';
if (function_exists('token_get_all')) {
    foreach (token_get_all($b414_functions) as $b414_t) {
        if (is_array($b414_t)) {
            if (T_COMMENT === $b414_t[0] || T_DOC_COMMENT === $b414_t[0]) {
                continue;
            }
            $b414_fn_code .= $b414_t[1];
        } else {
            $b414_fn_code .= $b414_t;
        }
    }
} else {
    $b414_fn_code = $b414_functions;
}

b414_assert('4.2a the comment stripper worked on functions.php (control)',
    '' !== $b414_fn_code && false !== strpos($b414_fn_code, 'bhp-colouring-pdp'));

$b414_gate_line = '';
foreach (explode("\n", $b414_fn_code) as $b414_ln) {
    if (false !== strpos($b414_ln, 'bhp-colouring-pdp') || false !== strpos($b414_ln, 'bhp_colouring_slug_for_product(get_queried_object_id')) {
        $b414_gate_line .= $b414_ln . "\n";
    }
}
b414_assert('4.2b ⛔ the live body-class gate hardcodes NO product id',
    '' !== $b414_gate_line && !preg_match('/\b(19020|19021|946|947|4065|618|333|14|833)\b/', $b414_gate_line),
    trim($b414_gate_line));

b414_assert('4.3 ⛔ no `bhp-rehearsal-testsku.php` anywhere in the theme tree',
    empty(glob($b414_theme . '/bhp-rehearsal-testsku.php'))
    && empty(glob($b414_theme . '/**/bhp-rehearsal-testsku.php')));

b414_assert('4.4 ⛔ no customer-facing string was added — the two edited sheets declare no `content:`',
    !preg_match('/\.bhp-pair-landing\s+\.bhp-landing-value__heading\s*\{[^}]*content\s*:/s', $b414_pair_code)
    && !preg_match('/bhp-colouring-pdp[^}]*content\s*:/s', $b414_pdp_code));

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== {$GLOBALS['b414_passes']} passed, {$GLOBALS['b414_failures']} failed, {$GLOBALS['b414_skips']} skipped ===\n";
exit($GLOBALS['b414_failures'] > 0 ? 1 : 0);
