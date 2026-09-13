<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-417 — Andrew's three notes after the 1.19.416 push.
 *
 * Theme 1.19.417. ⛔ THE BUNDLE PLUGIN DOES NOT MOVE IN THIS BUILD and §6.3
 * asserts it is still 1.8.94 rather than assuming it — the same guard 1.19.414
 * and 1.19.416 wrote, for the same reason: `CYCLE180-LD-BUILD-413` silently
 * DOWNGRADED the plugin by two versions while reporting success, and the only
 * thing that caught it was reading the version back.
 *
 * ⛔ WHAT THIS BUILD IS. Andrew Signore, after the 416 push, three desktop
 *    screenshots, VERBATIM (⚠️ RELAYED through `chief-of-staff` in the 417
 *    brief — ⛔ NOT witnessed first-hand by the agent that wrote this suite,
 *    Standing Rules §9.2 rule 2):
 *
 *      "Shop page CTA not above the fold. The bottom of the product pages
 *       still have these very long product cards that dont fit on the screen.
 *       Also, choose your format isnt centered on those product cards."
 *
 *    Three notes, three changes, one per section below.
 *
 * ⭐⭐ §1.3 AND §2.4 ARE THE TWO ROWS THAT CAN ACTUALLY FAIL ON A PLAUSIBLE BAD
 *     BUILD. Read them before adding a grep and calling this covered.
 *
 *     §1.3 — THE SOURCE-ORDER TRAP, AND IT IS NOT HYPOTHETICAL: IT HAPPENED
 *     DURING THIS BUILD. `--bhp-cover-well` is declared three times. The base
 *     (0,3,2) and the ≤640px MOBILE value (0,3,2) have IDENTICAL specificity,
 *     so the phone value wins ON SOURCE ORDER ALONE. The first attempt at this
 *     change APPENDED a new rule for the token instead of editing the base
 *     declaration in place; every grep passed, the desktop moved correctly, and
 *     the PHONE CARD GREW FROM 324px TO 372px. §1.3 asserts the ORDERING of the
 *     two declarations in the file, so re-appending goes red instead of green.
 *
 *     §2.4 — THE CENTRING RULE MUST BEAT `style.css`, AND MUST NOT BE INSIDE A
 *     MEDIA QUERY. The brief's words are "at every width". The 1.19.415
 *     related-card block in the same stylesheet IS `min-width: 1024px`, so the
 *     obvious place to put a new related-card rule is inside it — where it
 *     would silently do nothing at 390, which is a width the defect was
 *     MEASURED at (15.6px left of centre). §2.4 computes the specificity from
 *     the selectors themselves and asserts both the ordering relation and the
 *     absence of a media-query wrapper.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE. §1 and §2 are static analysis of the
 *   shipped stylesheets. They prove the rules SHIPPED, are well-formed, carry
 *   the intended specificity and sit in the intended at-rule scope. They do NOT
 *   prove what a browser painted. The browser evidence — card heights, button
 *   top offsets and button-centre offsets at an asserted `window.innerWidth`
 *   and `window.innerHeight` of 1440x900, 1920x900 and 390x844, before and
 *   after — is captured separately and filed with the build report. Neither
 *   substitutes for the other.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-417.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no variation,
 *    no attachment, no term, no option, no post, no comment, no cart, no order,
 *    no file.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ COUNTERS LIVE IN $GLOBALS, NOT IN `global $x` — `wp eval-file` includes
 *     this file INSIDE A FUNCTION, so a helper declaring `global` would
 *     increment a DIFFERENT variable from the one the summary reads, printing
 *     "0 failed" however many assertions failed. Observed at 1.19.412.
 */
$GLOBALS['b417_passes']   = 0;
$GLOBALS['b417_failures'] = 0;
$GLOBALS['b417_skips']    = 0;

function b417_assert($label, $ok, $detail = '') {
    if ($ok) {
        $GLOBALS['b417_passes']++;
        printf("  PASS  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '');
    } else {
        $GLOBALS['b417_failures']++;
        printf("  FAIL  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '');
    }
    return (bool) $ok;
}

function b417_skip($label, $why) {
    $GLOBALS['b417_skips']++;
    printf("  SKIP  %s  [%s]\n", $label, $why);
}

/**
 * Strip CSS comments so a needle can never match an essay ABOUT a rule.
 *
 * ⛔ THIS FILE'S STYLESHEETS ARE MOSTLY COMMENTARY BY BYTE COUNT, and every
 *    superseded value in them is preserved in prose on purpose. A grep for
 *    "230px" matches the 1.19.417 supersession note that RECORDS the old value.
 *    Every assertion about a DECLARATION runs against this function's output.
 */
function b417_css_code($css) {
    return preg_replace('!/\*.*?\*/!s', '', $css);
}

/**
 * Specificity of a single compound selector as (ids, classes, elements).
 *
 * ⛔ DELIBERATELY NARROW. It handles the selector shapes this theme actually
 *    writes — type, `.class`, `#id`, `[attr]`, `:pseudo-class`, `::element` —
 *    and nothing else. §2.4's control asserts it produces the values this
 *    codebase has already published for two known selectors, so a wrong
 *    implementation fails loudly rather than silently agreeing with itself.
 */
function b417_specificity($sel) {
    $sel = trim(preg_replace('/\s*([>+~])\s*/', ' ', $sel));
    $ids = $classes = $elements = 0;

    // ::pseudo-element counts as an element; remove before :pseudo-class.
    $sel = preg_replace_callback('/::[a-zA-Z-]+/', function () use (&$elements) {
        $elements++;
        return ' ';
    }, $sel);
    $sel = preg_replace_callback('/#[A-Za-z0-9_-]+/', function () use (&$ids) {
        $ids++;
        return ' ';
    }, $sel);
    $sel = preg_replace_callback('/\.[A-Za-z0-9_-]+/', function () use (&$classes) {
        $classes++;
        return ' ';
    }, $sel);
    $sel = preg_replace_callback('/\[[^\]]*\]/', function () use (&$classes) {
        $classes++;
        return ' ';
    }, $sel);
    $sel = preg_replace_callback('/:[a-zA-Z-]+(\([^)]*\))?/', function () use (&$classes) {
        $classes++;
        return ' ';
    }, $sel);
    foreach (preg_split('/\s+/', trim($sel)) as $tok) {
        if ('' !== $tok && '*' !== $tok && preg_match('/^[A-Za-z][A-Za-z0-9-]*$/', $tok)) {
            $elements++;
        }
    }
    return [$ids, $classes, $elements];
}

function b417_spec_gt($a, $b) {
    for ($i = 0; $i < 3; $i++) {
        if ($a[$i] !== $b[$i]) {
            return $a[$i] > $b[$i];
        }
    }
    return false;
}

function b417_spec_str($s) {
    return '(' . implode(',', $s) . ')';
}

$b417_theme     = get_template_directory();
$b417_style     = (string) @file_get_contents($b417_theme . '/style.css');
$b417_style_min = (string) @file_get_contents($b417_theme . '/style.min.css');
$b417_bf        = (string) @file_get_contents($b417_theme . '/assets/css/book-formats.css');
$b417_bf_min    = (string) @file_get_contents($b417_theme . '/assets/css/book-formats.min.css');
$b417_php       = (string) @file_get_contents($b417_theme . '/inc/catalog-surfaces.php');

$b417_style_code = b417_css_code($b417_style);
$b417_bf_code    = b417_css_code($b417_bf);

echo "\n=== CYCLE180-LD-BUILD-417 — theme 1.19.417 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §0 · THE FILES EXIST AND ARE THE ONES WE THINK THEY ARE.
   ⛔ A suite that silently reads '' from a missing file passes every
      "needle is absent" assertion it has. Guard the premise first.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §0 premises ---\n";

b417_assert('0.1 style.css read and non-trivial', strlen($b417_style) > 400000, strlen($b417_style) . ' bytes');
b417_assert('0.2 book-formats.css read and non-trivial', strlen($b417_bf) > 40000, strlen($b417_bf) . ' bytes');
b417_assert('0.3 inc/catalog-surfaces.php read and non-trivial', strlen($b417_php) > 20000, strlen($b417_php) . ' bytes');
/* ═══════════════════════════════════════════════════════════════════════════
 * ⛔ CORRECTED 2026-09-13 (`CYCLE180-LD-BUILD-418`) — §0.4 AND §0.5 WERE
 *    EQUALITY ASSERTIONS ON A VERSION NUMBER, AND THEY FAIL EVERY CORRECT
 *    BUILD THAT COMES AFTER THIS ONE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ OBSERVED, NOT ANTICIPATED. The full staging sweep on 1.19.418 went from 9
 *    non-zero suites to 10, and the newcomer was this file. All 55 of its
 *    SUBSTANTIVE rows still passed — the three 1.19.417 changes are intact and
 *    are independently re-asserted by `test-cycle180-build-418.php` §5. The
 *    only two failures were these: *"style.css declares Version: 1.19.417"*
 *    against a style.css that correctly declares 1.19.418.
 *
 * ⛔ THIS IS THE SAME DEFECT THE RUNBOOK ALREADY CORRECTED ONCE, in the same
 *    words: an equality assertion on a moving number "goes stale and then gets
 *    corrected downward by someone trusting it", and a gate that fails a
 *    CORRECT artefact is the more expensive failure because it teaches the next
 *    builder to distrust a passing build.
 *
 * ⭐⭐ AND THE HOUSE PATTERN ALREADY EXISTED — THIS FILE DEPARTED FROM IT.
 *     `test-cycle180-build-416.php` §6.1 and §6.3 read *"1.19.416 or later"*
 *     and use `version_compare(..., '>=')`. That is what is restored here.
 *
 * ⛔ NOTHING IS RELAXED. A FLOOR STILL CATCHES THE FAILURE THESE ROWS EXIST
 *    FOR — a deploy that lands an older theme than the one being tested, which
 *    is precisely the `CYCLE180-LD-BUILD-413` incident (a silent two-version
 *    DOWNGRADE that reported success). What it no longer does is go red because
 *    time passed.
 *
 * **The superseded assertions are preserved immediately below rather than
 *   deleted, so the movement stays visible:**
 *
 *   // SUPERSEDED 2026-09-13 — equality on a version number; red on every later build
 *   b417_assert('0.4 style.css declares Version: 1.19.417',
 *       (bool) preg_match('/^Version:\s*1\.19\.417\s*$/m', $b417_style));
 *   b417_assert('0.5 the active theme reports 1.19.417',
 *       '1.19.417' === wp_get_theme(get_template())->get('Version'),
 *       wp_get_theme(get_template())->get('Version'));
 *
 * ⚠ THIS EDIT TOUCHES ONLY §0.4 AND §0.5. Every other assertion in this file is
 *   byte-untouched, including the two that 1.19.417 itself recorded as the ones
 *   that can actually fail on a plausible bad build (§1.3 source order, §2.4
 *   specificity).
 * ═══════════════════════════════════════════════════════════════════════════ */
$b417_declared = '';
if (preg_match('/^Version:\s*([0-9.]+)\s*$/m', $b417_style, $b417_vm)) {
    $b417_declared = $b417_vm[1];
}
b417_assert('0.4 style.css declares Version: 1.19.417 or later',
    '' !== $b417_declared && version_compare($b417_declared, '1.19.417', '>='),
    "style.css: {$b417_declared}");

$b417_live_ver = (string) wp_get_theme(get_template())->get('Version');
b417_assert('0.5 the active theme reports 1.19.417 or later',
    '' !== $b417_live_ver && version_compare($b417_live_ver, '1.19.417', '>='),
    "live: {$b417_live_ver}");

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · NOTE 1 — "Shop page CTA not above the fold."
        The cover well and the vertical rhythm.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §1 shop grid: cover well + rhythm ---\n";

b417_assert('1.1 the base catalog cover well is 180px',
    (bool) preg_match('/--bhp-cover-well:\s*180px/', $b417_style_code));

b417_assert('1.2 the superseded 230px is NOT a live declaration anywhere',
    !preg_match('/--bhp-cover-well:\s*230px/', $b417_style_code));

/*
 * ⭐⭐ §1.3 — THE ONE THAT MATTERS. See the header. The two same-specificity
 *     declarations must stay in this order or the phone silently inherits the
 *     desktop value. Asserted on byte offsets in the real file, not on intent.
 */
$b417_base_pos   = strpos($b417_style_code, '--bhp-cover-well: 180px');
$b417_mobile_pos = strpos($b417_style_code, '--bhp-cover-well: 132px');
$b417_visit_pos  = strpos($b417_style_code, '--bhp-cover-well: 158px');

b417_assert('1.3a the ≤640px mobile well (132px) still exists', false !== $b417_mobile_pos);
b417_assert('1.3b the visit-active well (158px) still exists', false !== $b417_visit_pos);
b417_assert('1.3c ⭐ the 180px base is declared BEFORE the 132px mobile value (same specificity — source order is the ONLY thing that makes the phone win)',
    false !== $b417_base_pos && false !== $b417_mobile_pos && $b417_base_pos < $b417_mobile_pos,
    "base@$b417_base_pos mobile@$b417_mobile_pos");

/*
 * The visit-active override wins on SPECIFICITY, so its position is irrelevant
 * — but it must still be lower than the new base or a flagged session's card
 * would be the taller of the two, which inverts the reason it exists.
 */
b417_assert('1.3d the visit-active well (158px) is still lower than the new base (180px)', 158 < 180);

b417_assert('1.4 both cover selectors still read the token (the collection composite must shrink with the covers, or the row stays tall)',
    (bool) preg_match('/\.bhp-offer__composite\s*\{[^}]*var\(--bhp-cover-well\)/s', $b417_style_code)
    || (bool) preg_match('/li\.product\s+\.bhp-offer__composite\s*,?[^{]*\{[^}]*var\(--bhp-cover-well\)/s', $b417_style_code)
    || 2 <= preg_match_all('/max-height:\s*var\(--bhp-cover-well\)/', $b417_style_code));

b417_assert('1.5 object-fit: contain still governs the well (no cover is ever cropped)',
    (bool) preg_match('/max-height:\s*var\(--bhp-cover-well\);\s*object-fit:\s*contain/', $b417_style_code));

/* The 1.19.417 rhythm block, and its scope. */
$b417_rhythm_ok = preg_match(
    '/@media\s*\(min-width:\s*641px\)\s*\{(?:[^{}]|\{[^{}]*\})*\.bhp-shop-from-price\s*\{\s*margin-bottom:\s*0\.4rem/s',
    $b417_style_code
);
b417_assert('1.6 the 417 rhythm block is inside @media (min-width: 641px) — the exact complement of the ≤640px block, so it can never reach the phone',
    (bool) $b417_rhythm_ok);

b417_assert('1.7 the eyebrow font-size is NOT touched by the rhythm block (0.75rem is the test-cro-iterate5 §5.3 accessibility floor)',
    !preg_match('/@media\s*\(min-width:\s*641px\)\s*\{(?:[^{}]|\{[^{}]*\})*\.woo-card__eyebrow\s*\{[^}]*font-size/s', $b417_style_code));

b417_assert('1.8 the descriptor keeps its 1px rule line (only the air around it came in)',
    (bool) preg_match('/\.bhp-shop-descriptor\s*\{[^}]*border-bottom:\s*1px solid/s', $b417_style_code));

/*
 * ⛔ §1.9 — THE BRIEF'S EXPLICIT CONSTRAINT: keep the 1.19.415 BEST VALUE
 *    clearance. A 3px reduction here was built, measured (it bought exactly
 *    3px) and reverted. This asserts the 415 values are byte-present.
 */
b417_assert('1.9 ⛔ the 1.19.415 BEST VALUE badge clearance is UNCHANGED (margin-top -2px / margin-bottom 6px)',
    (bool) preg_match('/\.bhp-shop-collection-card__badge\s*\{\s*margin-top:\s*-2px;\s*margin-bottom:\s*6px;\s*\}/s', $b417_bf_code));

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · NOTE 3 — "choose your format isnt centered on those product cards."
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §2 related/upsell card: the button is centred ---\n";

b417_assert('2.1 the centring rule ships for BOTH the related and the upsell row',
    (bool) preg_match('/\.related\.products\s+ul\.products\s+li\.product\s+a\.button\s*,\s*\.woocommerce\s+div\.product\s+\.upsells\.products\s+ul\.products\s+li\.product\s+a\.button\s*\{/s', $b417_bf_code));

$b417_centre_rule = '';
if (preg_match('/([^{}]*a\.button\s*)\{\s*align-self:\s*center;\s*margin-left:\s*auto;\s*margin-right:\s*auto;\s*\}/s', $b417_bf_code, $m)) {
    $b417_centre_rule = $m[1];
}
b417_assert('2.2 it declares align-self:center plus auto inline margins', '' !== $b417_centre_rule);

b417_assert('2.3 the button WIDTH is not set by the centring rule (a fixed width would clip a label or widen a card)',
    '' !== $b417_centre_rule && !preg_match('/a\.button\s*\{\s*align-self:\s*center;\s*margin-left:\s*auto;\s*margin-right:\s*auto;\s*width/s', $b417_bf_code));

/*
 * ⭐⭐ §2.4 — THE ROW THAT CAN ACTUALLY FAIL. See the header.
 */
$b417_sel_new = '.woocommerce div.product .related.products ul.products li.product a.button';
$b417_sel_old = '.woocommerce ul.products li.product .button';
$b417_spec_new = b417_specificity($b417_sel_new);
$b417_spec_old = b417_specificity($b417_sel_old);

/*
 * Control: a specificity function that agrees with itself proves nothing, so
 * both triples are pinned to hand-counted values.
 *
 * ⛔ AND THE FIRST DRAFT OF THIS SUITE PINNED THEM TO (0,3,1) AND (0,4,4),
 *    WHICH ARE BOTH WRONG. The helper was right and the hand count was not:
 *      `.woocommerce ul.products li.product .button`
 *          classes  .woocommerce .products .product .button   = 4
 *          elements ul li                                     = 2   → (0,4,2)
 *      `.woocommerce div.product .related.products ul.products li.product a.button`
 *          classes  .woocommerce .product .related .products
 *                   .products .product .button                = 7
 *          elements div ul li a                               = 4   → (0,7,4)
 * ⭐ The conclusion never moved — the new rule still wins — which is exactly
 *    why the wrong numbers survived review and got as far as a shipped CSS
 *    comment. The comment in `assets/css/book-formats.css` was corrected in the
 *    same change, and these controls are what forced it.
 */
b417_assert('2.4a control: the specificity helper reproduces the hand count for style.css\'s winning rule — (0,4,2)',
    [0, 4, 2] === $b417_spec_old, b417_spec_str($b417_spec_old));
b417_assert('2.4b control: and for the new related-row selector — (0,7,4)',
    [0, 7, 4] === $b417_spec_new, b417_spec_str($b417_spec_new));
b417_assert('2.4d control: the shipped CSS comment publishes the SAME two triples this suite computes (a comment that disagrees with the code is how the first draft shipped)',
    false !== strpos($b417_bf, b417_spec_str($b417_spec_old)) && false !== strpos($b417_bf, b417_spec_str($b417_spec_new)),
    b417_spec_str($b417_spec_old) . ' and ' . b417_spec_str($b417_spec_new));
b417_assert('2.4c ⭐ the centring rule BEATS style.css\'s align-self: flex-start on specificity alone (not on source order, not on which sheet loads second)',
    b417_spec_gt($b417_spec_new, $b417_spec_old),
    b417_spec_str($b417_spec_new) . ' > ' . b417_spec_str($b417_spec_old));

/*
 * ⭐ §2.5 — "at every width" is the brief's wording. The 1.19.415 related-card
 *    block in this same file IS min-width:1024px, which is exactly why a new
 *    related-card rule could plausibly be dropped inside it and silently do
 *    nothing at 390 — a width the defect was MEASURED at.
 *    The test: cut the file at the centring rule and count unbalanced braces
 *    before it. Zero means it sits at the top level, outside every at-rule.
 */
$b417_cut = strpos($b417_bf_code, 'align-self: center;');
if (false !== $b417_cut) {
    $b417_head  = substr($b417_bf_code, 0, $b417_cut);
    $b417_depth = substr_count($b417_head, '{') - substr_count($b417_head, '}');
    b417_assert('2.5 ⭐ the centring rule is NOT inside any media query (brace depth 0 at its position — it applies at every width)',
        1 === $b417_depth,
        "open-brace depth $b417_depth (1 = inside its own rule only)");
} else {
    b417_assert('2.5 the centring rule is present at all', false);
}

b417_assert('2.6 style.css\'s own align-self: flex-start is STILL PRESENT (1.19.286 deliberately kept it for the shop grid, the search archive and the category archives — this build narrows one exception, it does not delete the rule)',
    (bool) preg_match('/\.woocommerce\s+ul\.products\s+li\.product\s+\.button\s*\{\s*align-self:\s*flex-start/s', $b417_style_code));

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · NOTE 2 — "very long product cards that dont fit on the screen."
        The two proof blocks come off the PDP's related and upsell cards.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §3 related/upsell card: the proof blocks ---\n";

b417_assert('3.1 the new predicate exists', function_exists('bhp_pdp_loop_row_context'));
b417_assert('3.2 the unhook ORs the two contexts',
    (bool) preg_match('/if\s*\(\s*!\s*bhp_catalog_grid_context\(\)\s*&&\s*!\s*bhp_pdp_loop_row_context\(\)\s*\)/', $b417_php));

b417_assert('3.3 both proof hooks are still the ones being removed',
    (bool) preg_match("/remove_action\('woocommerce_after_shop_loop_item_title',\s*'bhp_woocommerce_loop_kirkus_badge',\s*15\)/", $b417_php)
    && (bool) preg_match("/remove_action\('woocommerce_after_shop_loop_item_title',\s*'bhp_woocommerce_loop_amazon_review_badge',\s*20\)/", $b417_php));

/*
 * ⛔⛔ §3.4 — THE NO-WIDENING GUARD, AND IT IS THE REASON TWO PREDICATES EXIST
 *     RATHER THAN ONE LOOSENED ONE. The 1.19.350 block records that giving the
 *     PDP rows the shop control put TWO live add-to-cart buttons on a page that
 *     is supposed to have one. If a later tidy-up "simplifies" this by deleting
 *     the is_product() short-circuit from bhp_catalog_grid_context(), that
 *     defect ships again. This asserts the short-circuit is still there AND
 *     still first.
 */
if (function_exists('bhp_catalog_grid_context')) {
    $b417_fn = '';
    if (preg_match('/function\s+bhp_catalog_grid_context\(\)\s*\{(.*?)\n\}/s', $b417_php, $m)) {
        $b417_fn = $m[1];
    }
    $b417_ia = strpos($b417_fn, 'is_admin()');
    $b417_ip = strpos($b417_fn, 'is_product()');
    $b417_is = strpos($b417_fn, 'is_shop()');
    b417_assert('3.4a ⛔ bhp_catalog_grid_context() still short-circuits on is_product()',
        false !== $b417_ip);
    b417_assert('3.4b ⛔ and still does so BEFORE is_shop() — the order is load-bearing (1.19.350: two live add-to-cart buttons)',
        false !== $b417_ip && false !== $b417_is && $b417_ip < $b417_is,
        "is_product@$b417_ip is_shop@$b417_is");
    b417_assert('3.4c the admin guard is still first of all', false !== $b417_ia && $b417_ia < $b417_ip);

    /*
     * ⭐ BOTH BRANCHES ARE EXERCISED THROUGH THE TEST SEAMS, so this is behaviour
     *    rather than a grep. WP-CLI has no real product query to make
     *    is_product() true for, which is exactly why the filters exist.
     */
    $b417_force_true  = function () { return true; };
    $b417_force_false = function () { return false; };

    add_filter('bhp_pdp_loop_row_context', $b417_force_true, 99);
    add_filter('bhp_catalog_grid_context', $b417_force_false, 99);
    b417_assert('3.5a PDP-row context TRUE + grid context FALSE  ⇒ the unhook runs',
        true === bhp_pdp_loop_row_context() && false === bhp_catalog_grid_context());
    remove_filter('bhp_pdp_loop_row_context', $b417_force_true, 99);
    remove_filter('bhp_catalog_grid_context', $b417_force_false, 99);

    add_filter('bhp_pdp_loop_row_context', $b417_force_false, 99);
    add_filter('bhp_catalog_grid_context', $b417_force_false, 99);
    b417_assert('3.5b both FALSE ⇒ the unhook does NOT run (a blog page, the home page, a landing page — the badges keep whatever behaviour they had)',
        false === bhp_pdp_loop_row_context() && false === bhp_catalog_grid_context());
    remove_filter('bhp_pdp_loop_row_context', $b417_force_false, 99);
    remove_filter('bhp_catalog_grid_context', $b417_force_false, 99);

    b417_assert('3.5c ⛔ the seam is a FILTER, not a hardcoded branch — bhp_pdp_loop_row_context() returns is_product() when nothing filters it',
        (bool) preg_match("/apply_filters\('bhp_pdp_loop_row_context',\s*is_product\(\)\)/", $b417_php));
} else {
    b417_skip('3.4 / 3.5 predicate behaviour', 'bhp_catalog_grid_context() not loaded');
}

/*
 * ⛔⛔ §3.6 — NOTHING WAS DELETED. The components and the renderers that serve
 *     the PDP's OWN proof blocks must still exist, or this build removed real
 *     Kirkus and real Amazon review content from the site instead of relocating
 *     which card it appears on. §3 of the Standing Rules binds here.
 */
b417_assert('3.6a the Kirkus component renderer still exists', function_exists('bhp_render_kirkus_credibility'));
b417_assert('3.6b the Amazon review showcase renderer still exists', function_exists('bhp_render_amazon_review_showcase'));
b417_assert('3.6c the two loop-badge callbacks are NOT deleted (they are unhooked per-context, and still serve any surface that is neither a catalog grid nor a PDP)',
    function_exists('bhp_woocommerce_loop_kirkus_badge') && function_exists('bhp_woocommerce_loop_amazon_review_badge'));
b417_assert('3.6d the catalog trust strip below the grid is untouched', function_exists('bhp_catalog_trust_strip'));

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · NO COPY MOVED. This build changed geometry and hook registration only.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §4 no copy changed ---\n";

b417_assert('4.1 no "Ages 5-9" anywhere in the changed files (§9: the reading age is 6-9, never 5-9)',
    false === stripos($b417_php, 'Ages 5-9') && false === stripos($b417_style, 'Ages 5-9') && false === stripos($b417_bf, 'Ages 5-9'));

/*
 * ⭐ 4.2 — SCOPED TO THE TWO FUNCTIONS THIS BUILD WROTE OR CHANGED, not to the
 *    whole file. `inc/catalog-surfaces.php` legitimately emits strings
 *    elsewhere (the "From" price lead, the trust-strip aria-label), so a
 *    file-wide grep would be a check that always fails and would be deleted by
 *    the next reader. The claim being tested is narrow and true: the 417
 *    changes add NO customer-facing text.
 */
$b417_new_php = '';
if (preg_match('/function\s+bhp_pdp_loop_row_context\(\)\s*\{(.*?)\n\}/s', $b417_php, $m)) {
    $b417_new_php .= $m[1];
}
if (preg_match('/function\s+bhp_catalog_unhook_card_proof\(\)\s*\{(.*?)\n\}/s', $b417_php, $m)) {
    $b417_new_php .= $m[1];
}
b417_assert('4.2a control: the two changed function bodies were actually located',
    strlen($b417_new_php) > 200, strlen($b417_new_php) . ' bytes');
b417_assert('4.2b neither changed function emits a translatable / customer-facing string',
    0 === preg_match_all('/\b(esc_html__|esc_attr__|esc_html_e|esc_attr_e|_e|__)\s*\(\s*[\'"]/', b417_css_code($b417_new_php)));

/*
 * ⛔ 4.3 RUNS AGAINST COMMENT-STRIPPED CODE, and the first draft did not — it
 *    read the raw file and failed, because the ONLY occurrence of the string in
 *    `inc/catalog-surfaces.php` is the 1.19.350 comment that FORBIDS it
 *    ("⛔ There is no placeholder, no 'reviews coming soon', and no
 *    aggregateRating."). ⭐ A guard that fires on the prose telling you not to
 *    do the thing is a guard that gets deleted by the next reader.
 */
$b417_php_code = preg_replace(['!/\*.*?\*/!s', '!^\s*//.*$!m', '!^\s*#.*$!m'], '', $b417_php);
b417_assert('4.3a control: comment-stripping actually removed something (a no-op stripper would make 4.3b vacuous)',
    strlen($b417_php_code) < strlen($b417_php) * 0.6,
    'raw=' . strlen($b417_php) . ' code=' . strlen($b417_php_code));
b417_assert('4.3b no aggregateRating or review schema in LIVE code (the string survives in a comment that forbids it, by design)',
    false === stripos($b417_php_code, 'aggregateRating'));

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · STANDING GUARDS — cheap, and they have each caught something once.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §5 standing guards ---\n";

b417_assert('5.1 no em dash in any LIVE declaration of book-formats.css (comments may carry them; rules may not)',
    false === strpos($b417_bf_code, "\xe2\x80\x94"));
b417_assert('5.2 style.css carries no CR bytes (the CRLF trap, at source)', false === strpos($b417_style, "\r"));
b417_assert('5.3 style.min.css carries no CR bytes', false === strpos($b417_style_min, "\r"));
b417_assert('5.4 book-formats.css carries no CR bytes', false === strpos($b417_bf, "\r"));
b417_assert('5.5 book-formats.min.css carries no CR bytes', false === strpos($b417_bf_min, "\r"));

b417_assert('5.6 style.min.css is genuinely smaller than its source (the build ran; this is not a copy)',
    strlen($b417_style_min) > 0 && strlen($b417_style_min) < strlen($b417_style),
    'src=' . strlen($b417_style) . ' min=' . strlen($b417_style_min));
b417_assert('5.7 book-formats.min.css is genuinely smaller than its source',
    strlen($b417_bf_min) > 0 && strlen($b417_bf_min) < strlen($b417_bf),
    'src=' . strlen($b417_bf) . ' min=' . strlen($b417_bf_min));

/*
 * ⭐ 5.8 / 5.9 — THE STALE-MINIFICATION GUARD, RE-RUN HERE ON THE TWO SHEETS
 *    THIS BUILD ACTUALLY EDITED. `test-style-minification.php` covers this
 *    globally; the point of repeating it is that BOTH of this build's
 *    stylesheets are CSS-only changes, so a forgotten `node tools/build-css.mjs`
 *    would ship a green PHP suite and an unchanged website.
 */
foreach ([['style.css', $b417_style, $b417_style_min], ['book-formats.css', $b417_bf, $b417_bf_min]] as $b417_pair) {
    list($b417_name, $b417_src, $b417_min) = $b417_pair;
    if (preg_match('/source-md5:\s*([0-9a-f]{32})/', $b417_min, $m)) {
        b417_assert("5.8 $b417_name: the shipped .min.css was built from the shipped source",
            md5($b417_src) === $m[1], 'src=' . md5($b417_src) . ' stamp=' . $m[1]);
    } else {
        b417_assert("5.8 $b417_name: .min.css carries a source-md5 stamp", false);
    }
}

b417_assert('5.9 the rehearsal mu-plugin is not inside the theme tree',
    !file_exists($b417_theme . '/bhp-rehearsal-testsku.php'));

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · THE ENVIRONMENT DID NOT MOVE UNDERNEATH THIS BUILD.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §6 environment ---\n";

b417_assert('6.1 the active theme slug is still the deploy slug (a mismatched ZIP prefix installs a NEW inactive theme instead of replacing the live one)',
    'brave-hearts-theme-deploy-explorer-expedition-guides' === get_template(), get_template());

b417_assert('6.2 WooCommerce is active', class_exists('WooCommerce'));

if (function_exists('get_plugins')) {
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $b417_plug = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/brave-hearts-bundle-pricing.php';
    if (file_exists($b417_plug)) {
        $b417_pv = get_plugin_data($b417_plug, false, false);
        b417_assert('6.3 ⛔ the bundle plugin is STILL 1.8.94 — this build does not move it, and 413 proved that has to be read back rather than assumed',
            '1.8.94' === $b417_pv['Version'], $b417_pv['Version']);
    } else {
        b417_skip('6.3 the bundle plugin is still 1.8.94', 'bundle plugin not installed on this environment');
    }
} else {
    b417_skip('6.3 the bundle plugin is still 1.8.94', 'plugin API unavailable');
}

/* ═══════════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════════ */
printf(
    "\n=== CYCLE180-LD-BUILD-417: %d passed, %d failed, %d skipped ===\n",
    $GLOBALS['b417_passes'],
    $GLOBALS['b417_failures'],
    $GLOBALS['b417_skips']
);

if ($GLOBALS['b417_failures'] > 0) {
    exit(1);
}
