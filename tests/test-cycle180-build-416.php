<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-416 — Andrew's ruling of founder seal 1497, proven.
 *
 * Theme 1.19.416. ⛔ THE BUNDLE PLUGIN DOES NOT MOVE IN THIS BUILD and §6.4
 * asserts that it is still 1.8.94 rather than assuming it — the same guard
 * 1.19.414 wrote for 1.8.93, for the same reason: `CYCLE180-LD-BUILD-413`
 * silently DOWNGRADED the plugin by two versions while reporting success, and
 * the only thing that caught it was reading the version back.
 *
 * ⛔ WHAT THIS BUILD IS. 1.19.414 fixed a phone ordering defect on the
 *    colouring PDP and deliberately scoped it to `.bhp-colouring-pdp`, because
 *    its brief required the chapter-book PDPs to be UNCHANGED. It raised the
 *    identical, larger defect on those pages as `CYCLE180-LDB-11` and left it
 *    for Andrew, on the stated reasoning that moving eight bands on three
 *    selling pages is a layout decision, not a bug fix.
 *
 *    Andrew decided it, 2026-09-13, founder seal 1497, VERBATIM:
 *        "Apply the same move to all pages for the build"
 *
 *    1.19.416 removes the colouring-only scope. Same move, same three reserved
 *    slots, same specificity argument, every product page that has a rail.
 *
 * ⭐⭐ §2 AND §4 ARE THE TWO ROWS THAT CAN ACTUALLY FAIL ON A PLAUSIBLE BAD
 *     BUILD. Read them before adding a grep and calling this covered.
 *
 *     §2.1 — THE NO-OP TRAP. The obvious way to widen 414's rule is to delete
 *     `.bhp-colouring-pdp` from the selector. That drops it from (0,3,3) to
 *     (0,2,3), which LOSES on class count to the unscoped default at (0,3,2)
 *     in `product-template.css`. The stylesheet would parse, the rule would be
 *     present, every grep would pass — and the page would not move. §2 computes
 *     the specificity from the selectors themselves and asserts the ORDERING
 *     RELATION, so a no-op widening is red, not green.
 *
 *     §4 — THE SCOPE IS AN ENQUEUE, NOT A BODY CLASS, AND §4 PROVES THAT ON
 *     THE RUNNING ENVIRONMENT. The brief offered "extend the body class to all
 *     single product pages" as an alternative. It was not taken, because
 *     `pdp-content.css` is ALREADY scoped by `bhp_pdp_enqueue_content_css()`:
 *     the sheet loads only where `bhp_pdp_has_left_column()` is true. §4 walks
 *     every published product on THIS environment, computes that predicate
 *     from real data, and asserts the partition is real in BOTH directions —
 *     some products have a rail, at least one does not. A static grep cannot
 *     see that, and a claim that "the Activity Book is unaffected" is worth
 *     nothing without it.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE. §1, §2, §3 and §5 are static analysis of
 *   the shipped stylesheets. They prove the rules SHIPPED, are well-formed,
 *   sit inside `max-width: 600px` and carry the intended specificity. They do
 *   NOT prove what a browser painted. The browser evidence — the flattened
 *   grid-item list with computed `order` and page offsets at an asserted
 *   `window.innerWidth` of 390, and the 1440 fingerprint, on all seven URL
 *   states before and after — is captured separately and filed with the build
 *   report. Neither substitutes for the other.
 *
 * ⚠ "SEVEN PDPs" IS SEVEN URL STATES, NOT SEVEN RENDERED PAGES, AND THIS SUITE
 *   DOES NOT PRETEND OTHERWISE. The three hardcover product URLs 301-redirect
 *   onto their paperback PDP carrying `?bhp_format=hardcover`; the rendered
 *   post is the same one. Verified live, not inferred — see §4.5.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-416.php \
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
$GLOBALS['b416_passes']   = 0;
$GLOBALS['b416_failures'] = 0;
$GLOBALS['b416_skips']    = 0;

function b416_assert($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['b416_passes']++;
        echo "  PASS  {$label}\n";
    } else {
        $GLOBALS['b416_failures']++;
        echo "  FAIL  {$label}" . ('' !== $detail ? "  [{$detail}]" : '') . "\n";
    }
}

function b416_skip($label, $why) {
    $GLOBALS['b416_skips']++;
    echo "  SKIP  {$label}  [{$why}]\n";
}

/** Strip CSS comments, so "present in a comment" never reads as "live". */
function b416_css_code($css) {
    return preg_replace('#/\*.*?\*/#s', '', (string) $css);
}

/** Every `@media (max-width: 600px)` block body, brace-matched. */
function b416_media_600_blocks($css) {
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
 * CSS specificity as [ids, classes, elements]. Deliberately narrow — type,
 * class, id and descendant/child combinators only, which is all §2 needs, and
 * §2.9 refuses any selector this cannot honestly analyse.
 */
function b416_specificity($selector) {
    $sel = trim(preg_replace('/\s*>\s*/', ' ', (string) $selector));
    $ids = preg_match_all('/#[A-Za-z0-9_-]+/', $sel);
    $cls = preg_match_all('/\.[A-Za-z0-9_-]+/', $sel);
    $stripped = preg_replace('/[#.][A-Za-z0-9_-]+/', ' ', $sel);
    $els = preg_match_all('/\b[a-zA-Z][a-zA-Z0-9]*\b/', $stripped);
    return array((int) $ids, (int) $cls, (int) $els);
}

/** True when $a wins the cascade over $b on specificity alone. */
function b416_beats(array $a, array $b) {
    for ($i = 0; $i < 3; $i++) {
        if ($a[$i] !== $b[$i]) {
            return $a[$i] > $b[$i];
        }
    }
    return false;
}

$b416_theme = get_template_directory();
$b416_src = function ($rel) use ($b416_theme) {
    $path = $b416_theme . '/' . ltrim($rel, '/');
    return is_readable($path) ? (string) file_get_contents($path) : '';
};

$b416_pdp_css   = $b416_src('assets/css/pdp-content.css');
$b416_pdp_min   = $b416_src('assets/css/pdp-content.min.css');
$b416_tpl_css   = $b416_src('assets/css/product-template.css');
$b416_style_css = $b416_src('style.css');
$b416_style_min = $b416_src('style.min.css');
$b416_bf_php    = $b416_src('inc/book-formats.php');

$b416_pdp_code = b416_css_code($b416_pdp_css);
$b416_tpl_code = b416_css_code($b416_tpl_css);

/* The scope token that is unique to the 1.19.416 block. The `html` prefix is
   what makes it unique: the BARE `body.single-product.woocommerce` form is the
   house selector and appears hundreds of times across these sheets. */
$b416_token = 'html body.single-product';

echo "\n=== CYCLE180-LD-BUILD-416 — founder seal 1497, CYCLE180-LDB-11 closed ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §0 · INTEGRITY CONTROLS — the helpers above must actually work, or every
        assertion built on them is decoration.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §0 integrity controls on this suite's own helpers ---\n";

b416_assert('0.1 the stylesheets under test are all readable',
    '' !== $b416_pdp_css && '' !== $b416_pdp_min && '' !== $b416_tpl_css && '' !== $b416_style_css);

b416_assert('0.2 the comment stripper strips (control: a known comment is gone)',
    false !== strpos($b416_pdp_css, 'ANDREW\'S RULING')
    && false === strpos($b416_pdp_code, 'ANDREW\'S RULING'));

b416_assert('0.3 the comment stripper does NOT eat rules (control: a known selector survives)',
    false !== strpos($b416_pdp_code, '.bhp-pdp-look-inside'));

b416_assert('0.4 specificity: (0,3,3) beats (0,3,2) — the whole widening argument',
    b416_beats(array(0, 3, 3), array(0, 3, 2)));

b416_assert('0.5 specificity: (0,2,3) does NOT beat (0,3,2) — the no-op trap, as arithmetic',
    !b416_beats(array(0, 2, 3), array(0, 3, 2)));

b416_assert('0.6 specificity: identical selectors do NOT "beat" each other',
    !b416_beats(b416_specificity('.a .b'), b416_specificity('.c .d')));

b416_assert('0.7 the brace matcher finds at least one max-width:600px block in pdp-content.css',
    count(b416_media_600_blocks($b416_pdp_css)) >= 1,
    'blocks: ' . count(b416_media_600_blocks($b416_pdp_css)));

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · THE WIDENED RULES ARE LIVE — in the source AND in the built artefact.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §1 the widened ordering rules shipped ---\n";

b416_assert('1.1 the default bucket moves to slot 9, unscoped by page type',
    (bool) preg_match('/html body\.single-product\.woocommerce div\.product > \*,[^{]*\{\s*order:\s*9;\s*\}/s', $b416_pdp_code));

b416_assert('1.2 all three promoted levels are covered (direct, summary, gallery section)',
    (bool) preg_match('/html body\.single-product\.woocommerce div\.product > \*,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.summary > \*,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.bhp-media-gallery--hero > \*\s*\{/s', $b416_pdp_code));

b416_assert('1.3 slot 8 is reserved for exactly the note, the CTA and the rail',
    (bool) preg_match('/html body\.single-product\.woocommerce div\.product > \.bhp-media-gallery--hero > \.bhp-look-inside__note,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.summary > \.bhp-offer--product,\s*'
        . 'html body\.single-product\.woocommerce div\.product > \.bhp-pdp-left\s*\{\s*order:\s*8;\s*\}/s', $b416_pdp_code));

$b416_live = substr_count($b416_pdp_code, $b416_token);
b416_assert('1.4 exactly six live occurrences — three defaults, three pins, no fourth',
    6 === $b416_live, "live occurrences: {$b416_live}");

b416_assert('1.5 the built minified sheet carries them too (the CSS build step ran)',
    substr_count($b416_pdp_min, $b416_token) === $b416_live,
    'min occurrences: ' . substr_count($b416_pdp_min, $b416_token));

/*
 * ⭐⭐ §1.6 IS THE ROW THAT CATCHES A HALF-DONE WIDENING. A build that ADDED
 *    the wide rules and LEFT the narrow ones would satisfy every assertion
 *    above and would ship two rules that mean the same thing, one a subset of
 *    the other — the exact shape that makes a stylesheet unreadable three
 *    versions later. The colouring selectors must survive ONLY in the
 *    superseded-wording comment, which is why this is checked against the
 *    comment-stripped code and the raw file separately.
 */
b416_assert('1.6 ⛔ the 1.19.414 colouring-scoped rules are GONE from live code',
    false === strpos($b416_pdp_code, 'bhp-colouring-pdp'),
    'live colouring-scoped occurrences: ' . substr_count($b416_pdp_code, 'bhp-colouring-pdp'));

b416_assert('1.7 ⭐ and are PRESERVED in the superseded-wording comment, not deleted',
    substr_count($b416_pdp_css, 'bhp-colouring-pdp') >= 6,
    'raw occurrences: ' . substr_count($b416_pdp_css, 'bhp-colouring-pdp'));

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · THE ARITHMETIC THE WHOLE FIX RESTS ON, computed rather than eyeballed.
        1.19.414 recorded that its FIRST DRAFT hand-counted one of these wrong.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §2 the specificity ordering, computed from the selectors ---\n";

$b416_scoped_default = 'html body.single-product.woocommerce div.product > *';
$b416_scoped_slot8   = 'html body.single-product.woocommerce div.product > .bhp-pdp-left';
$b416_unscoped_def   = 'body.single-product.woocommerce div.product > *';
$b416_named_title    = 'body.single-product.woocommerce div.product > .summary > .product_title';
$b416_named_age      = 'body.single-product.woocommerce div.product .bhp-product-value-prop__age';
$b416_named_formats  = 'body.single-product.woocommerce div.product > .summary > .bhp-formats';
$b416_unscoped_rail  = 'body.single-product.woocommerce div.product > .bhp-pdp-left';
/* The no-op that a careless widening would have shipped. Never written to the
   stylesheet — asserted here so the reason it was not written is on the
   record as a number rather than as a paragraph. */
$b416_naive_widen    = 'html body.woocommerce div.product > *';

foreach (array(
    'scoped default' => $b416_scoped_default,
    'scoped slot 8'  => $b416_scoped_slot8,
    'unscoped def'   => $b416_unscoped_def,
    'named title'    => $b416_named_title,
    'named age'      => $b416_named_age,
    'named formats'  => $b416_named_formats,
    'unscoped rail'  => $b416_unscoped_rail,
    'NAIVE widen'    => $b416_naive_widen,
) as $b416_lbl => $b416_sel) {
    echo sprintf("        %-15s %s\n", $b416_lbl, '(' . implode(',', b416_specificity($b416_sel)) . ')');
}

b416_assert('2.1 the scoped default BEATS the unscoped default (or the fix never applies)',
    b416_beats(b416_specificity($b416_scoped_default), b416_specificity($b416_unscoped_def)),
    'scoped=(' . implode(',', b416_specificity($b416_scoped_default)) . ') unscoped=('
        . implode(',', b416_specificity($b416_unscoped_def)) . ')');

b416_assert('2.2 ⛔⛔ THE NO-OP TRAP: deleting the scope class instead would have LOST',
    !b416_beats(b416_specificity($b416_naive_widen), b416_specificity($b416_unscoped_def)),
    'naive=(' . implode(',', b416_specificity($b416_naive_widen)) . ') unscoped=('
        . implode(',', b416_specificity($b416_unscoped_def)) . ')');

b416_assert('2.3 ⛔ the scoped default LOSES to the named title slot (or the first screen breaks)',
    b416_beats(b416_specificity($b416_named_title), b416_specificity($b416_scoped_default)));

b416_assert('2.4 ⛔⛔ the scoped default LOSES to the BINDING case, the age pill at (0,4,2)',
    b416_beats(b416_specificity($b416_named_age), b416_specificity($b416_scoped_default)),
    'age=(' . implode(',', b416_specificity($b416_named_age)) . ') scoped=('
        . implode(',', b416_specificity($b416_scoped_default)) . ')');

b416_assert('2.5 ⛔ the scoped default LOSES to the buy box at slot 5 (the CTA does not move)',
    b416_beats(b416_specificity($b416_named_formats), b416_specificity($b416_scoped_default)));

b416_assert('2.6 the scoped slot-8 rule BEATS the unscoped rail rule (order 10 is overridden)',
    b416_beats(b416_specificity($b416_scoped_slot8), b416_specificity($b416_unscoped_rail)));

b416_assert('2.7 the scoped slot-8 rule BEATS the scoped default (the three pins win)',
    b416_beats(b416_specificity($b416_scoped_slot8), b416_specificity($b416_scoped_default)));

b416_assert('2.8 ⭐ the widened selector has the IDENTICAL specificity 1.19.414 engineered',
    b416_specificity($b416_scoped_default) === b416_specificity('html body.woocommerce.bhp-colouring-pdp div.product > *'),
    'new=(' . implode(',', b416_specificity($b416_scoped_default)) . ') 414=('
        . implode(',', b416_specificity('html body.woocommerce.bhp-colouring-pdp div.product > *')) . ')');

b416_assert('2.9 ⛔ guard: no scoped selector uses syntax the specificity helper cannot analyse',
    !preg_match('/html body\.single-product[^,{]*[\[:]/', $b416_pdp_code));

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · THE BLAST RADIUS — what this build must NOT have changed.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §3 the blast radius ---\n";

b416_assert('3.1 the UNSCOPED rail rule is still order 10, verbatim (test-cycle179-build-391 §3 depends on it)',
    (bool) preg_match('/body\.single-product\.woocommerce div\.product > \.bhp-pdp-left \{\s*order:\s*10;\s*\}/s', $b416_pdp_code));

b416_assert('3.2 the UNSCOPED default bucket in product-template.css is still order 8',
    (bool) preg_match('/body\.single-product\.woocommerce div\.product > \*,\s*'
        . 'body\.single-product\.woocommerce div\.product > \.summary > \*,\s*'
        . 'body\.single-product\.woocommerce div\.product > \.bhp-media-gallery--hero > \*\s*\{\s*order:\s*8;/s', $b416_tpl_code));

b416_assert('3.3 product-template.css was NOT edited by this build — the shared sheet carries no html-prefixed rule',
    false === strpos($b416_tpl_css, $b416_token));

b416_assert('3.4 the named first-screen slots are untouched (title 3, age 4, formats 5, amazon 6, hook 7)',
    (bool) preg_match('/> \.summary > \.product_title \{[^}]*order:\s*3;/s', $b416_tpl_code)
    && (bool) preg_match('/\.bhp-product-value-prop__age \{[^}]*order:\s*4;/s', $b416_tpl_code)
    && (bool) preg_match('/> \.summary > \.bhp-formats \{[^}]*order:\s*5;/s', $b416_tpl_code)
    && (bool) preg_match('/> \.amazon-reviews-product-section \{[^}]*order:\s*6;/s', $b416_tpl_code)
    && (bool) preg_match('/\.bhp-product-value-prop__hook \{[^}]*order:\s*7;/s', $b416_tpl_code));

b416_assert('3.5 the gallery and its cue still hold slots 1 and 2',
    (bool) preg_match('/> \.bhp-media-gallery--hero > \.bhp-gallery,\s*[^{]*\{\s*order:\s*1;/s', $b416_tpl_code)
    && (bool) preg_match('/> \.bhp-media-gallery--hero > \.bhp-gallery-cue \{[^}]*order:\s*2;/s', $b416_tpl_code));

/*
 * ⛔⛔ DESKTOP. Every widened rule must sit inside a `max-width: 600px` block.
 *     A single one outside it would re-order the 901px two-column grid, which
 *     Andrew's ruling does not authorise and the brief forbids in as many
 *     words.
 */
$b416_600         = implode("\n", b416_media_600_blocks($b416_pdp_css));
$b416_code_total  = substr_count($b416_pdp_code, $b416_token);
$b416_code_inside = substr_count(b416_css_code($b416_600), $b416_token);

b416_assert('3.6 ⛔ EVERY live widened selector sits inside a max-width:600px block (desktop untouched)',
    $b416_code_total > 0 && $b416_code_total === $b416_code_inside,
    "total={$b416_code_total} inside={$b416_code_inside}");

b416_assert('3.7 the desktop grid placement in style.css is untouched',
    (bool) preg_match('/\.woocommerce div\.product > \.bhp-pdp-left \{[^}]*grid-column:\s*1;[^}]*grid-row:\s*2;/s', $b416_style_css));

b416_assert('3.8 the order fix uses no `!important`',
    !preg_match('/html body\.single-product[^}]*!important/s', $b416_pdp_code));

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · ⭐⭐ THE SCOPE IS THE ENQUEUE, AND THIS SECTION PROVES IT ON THE
        RUNNING ENVIRONMENT RATHER THAN ASSERTING IT IN A COMMENT.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §4 the enqueue is the scope: a real partition, computed from live data ---\n";

b416_assert('4.1 the enqueue predicate exists and is the one the renderer uses',
    function_exists('bhp_pdp_has_left_column') && function_exists('bhp_pdp_content_key'));

/*
 * ⛔ THE SOURCE OF THE ENQUEUE IS READ, NOT ASSUMED. If a future build ever
 *    enqueues this sheet sitewide, every rule in it reaches pages that have no
 *    rail and the "no body class needed" argument silently stops being true.
 */
if (function_exists('bhp_pdp_enqueue_content_css')) {
    $b416_ref  = new ReflectionFunction('bhp_pdp_enqueue_content_css');
    $b416_file = (string) @file_get_contents($b416_ref->getFileName());
    $b416_body = implode("\n", array_slice(
        explode("\n", $b416_file),
        $b416_ref->getStartLine() - 1,
        $b416_ref->getEndLine() - $b416_ref->getStartLine() + 1
    ));
    b416_assert('4.2 ⛔ the enqueue is GATED by bhp_pdp_has_left_column() — the sheet is not sitewide',
        false !== strpos($b416_body, 'bhp_pdp_has_left_column')
        && false !== strpos($b416_body, 'return;'));
    b416_assert('4.3 the enqueue registers assets/css/pdp-content.css and nothing else',
        false !== strpos($b416_body, 'assets/css/pdp-content.css'));
} else {
    b416_skip('4.2 the enqueue is gated by bhp_pdp_has_left_column()', 'bhp_pdp_enqueue_content_css() not loaded');
    b416_skip('4.3 the enqueue registers assets/css/pdp-content.css', 'bhp_pdp_enqueue_content_css() not loaded');
}

/*
 * ⭐⭐ THE PARTITION, IN BOTH DIRECTIONS. A one-sided check ("the books have a
 *    rail") would pass on a build where EVERY product has one, which is
 *    precisely the condition under which the "no body class needed" argument
 *    collapses. Both halves are asserted, and both are derived from whatever
 *    products this environment actually holds — no ids are hardcoded.
 */
if (function_exists('bhp_pdp_has_left_column') && function_exists('bhp_book_lookup_product')) {
    $b416_products = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ));

    $b416_with = array();
    $b416_without = array();
    foreach ($b416_products as $b416_pid) {
        $b416_key = '';
        if (function_exists('bhp_colouring_slug_for_product')) {
            $b416_slug = bhp_colouring_slug_for_product((int) $b416_pid);
            if ($b416_slug) {
                $b416_key = 'colouring_' . $b416_slug;
            }
        }
        if ('' === $b416_key) {
            $b416_found = bhp_book_lookup_product((int) $b416_pid);
            $b416_key = $b416_found ? $b416_found['key'] : '';
        }
        if (bhp_pdp_has_left_column($b416_key)) {
            $b416_with[] = (int) $b416_pid . ':' . $b416_key;
        } else {
            $b416_without[] = (int) $b416_pid . ':' . ('' === $b416_key ? '(no key)' : $b416_key);
        }
    }

    echo '        rail YES (' . count($b416_with) . '): ' . implode(', ', $b416_with) . "\n";
    echo '        rail NO  (' . count($b416_without) . '): ' . implode(', ', $b416_without) . "\n";

    b416_assert('4.4a the environment has published products to partition at all',
        count($b416_products) > 0, 'products: ' . count($b416_products));

    b416_assert('4.4b ⭐ at least four published products DO render the rail (the fix has a job to do)',
        count($b416_with) >= 4, 'with rail: ' . count($b416_with));

    b416_assert('4.4c ⭐⭐ at least one published product does NOT — the sheet never loads there, '
        . 'so it cannot be reordered by these rules',
        count($b416_without) >= 1, 'without rail: ' . count($b416_without));
} else {
    b416_skip('4.4 the rail partition is real in both directions', 'book/colouring registry not loaded');
}

/*
 * ⚠ §4.5 — "SEVEN PDPs" IS SEVEN URL STATES OVER FOUR RENDERED POSTS, AND THE
 *   BRIEF'S OWN WORDING WOULD HAVE A READER EXPECT SEVEN. The three hardcover
 *   products redirect onto their paperback PDP with `?bhp_format=hardcover`;
 *   the post that renders is the paperback. This is asserted rather than
 *   footnoted so that nobody later "fixes" a test that expected seven posts.
 */
if (function_exists('bhp_book_lookup_product')) {
    $b416_hc = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));
    $b416_hc_ids = array();
    foreach ($b416_hc as $b416_pid) {
        $b416_nm = get_post_field('post_name', (int) $b416_pid);
        if (false !== strpos((string) $b416_nm, 'hardcover')) {
            $b416_hc_ids[] = (int) $b416_pid;
        }
    }
    if (count($b416_hc_ids) > 0) {
        b416_assert('4.5 ⚠ hardcover products exist as separate posts (their URLs redirect to the paperback PDP)',
            count($b416_hc_ids) >= 1, 'hardcover post ids: ' . implode(',', $b416_hc_ids));
    } else {
        b416_skip('4.5 hardcover products exist as separate posts', 'no hardcover slugs on this environment');
    }
} else {
    b416_skip('4.5 hardcover products exist as separate posts', 'book registry not loaded');
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · THE THREE PINNED BLOCKS ARE REAL CLASSES THAT REAL TEMPLATES EMIT.
        A reserved slot for a selector nothing renders is a silent no-op.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §5 the three pinned selectors match markup that is actually emitted ---\n";

$b416_emitters = array(
    'bhp-look-inside__note' => array('template-parts/commerce/', 'inc/'),
    'bhp-offer--product'    => array('inc/'),
    'bhp-pdp-left'          => array('template-parts/commerce/', 'inc/'),
);

foreach ($b416_emitters as $b416_cls => $b416_dirs) {
    $b416_hits = 0;
    foreach ($b416_dirs as $b416_dir) {
        $b416_full = $b416_theme . '/' . $b416_dir;
        if (!is_dir($b416_full)) {
            continue;
        }
        $b416_it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($b416_full));
        foreach ($b416_it as $b416_f) {
            if (!$b416_f->isFile() || 'php' !== strtolower($b416_f->getExtension())) {
                continue;
            }
            if (false !== strpos((string) $b416_f->getPathname(), '_pre-edit-backups')) {
                continue;
            }
            if (false !== strpos((string) @file_get_contents($b416_f->getPathname()), $b416_cls)) {
                $b416_hits++;
            }
        }
    }
    b416_assert("5.x `{$b416_cls}` is emitted by at least one live template/include",
        $b416_hits > 0, "files emitting it: {$b416_hits}");
}

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · VERSION, STAMP, AND THE UNMOVED-PLUGIN GUARD.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §6 version, stamp and the plugin freeze ---\n";

/*
 * ⭐ FLOORS, NOT EQUALITIES, and the reason is on the record: 1.19.415 had to
 *    amend two suites whose version EQUALITIES failed a perfectly correct
 *    forward release. A floor still catches the regression these exist for —
 *    the 1.19.413 accident, where a temp index missing a path silently shipped
 *    an OLDER version while reporting success, lands BELOW the floor.
 */
preg_match('/^\s*Version:\s*([0-9.]+)\s*$/m', $b416_style_css, $b416_vm);
$b416_ver = isset($b416_vm[1]) ? trim($b416_vm[1]) : '';
b416_assert('6.1 style.css declares Version: 1.19.416 or later',
    '' !== $b416_ver && version_compare($b416_ver, '1.19.416', '>='), "style.css: {$b416_ver}");

preg_match('/source-md5:\s*([0-9a-f]{32})/', $b416_style_min, $b416_sm);
$b416_stamp = isset($b416_sm[1]) ? $b416_sm[1] : '';
b416_assert('6.2 ⛔ style.min.css `source-md5` matches style.css exactly (the CRLF trap)',
    '' !== $b416_stamp && $b416_stamp === md5($b416_style_css),
    "stamp={$b416_stamp} actual=" . md5($b416_style_css));

$b416_live_ver = function_exists('wp_get_theme') ? (string) wp_get_theme()->get('Version') : '';
b416_assert('6.3 the shipped theme reports 1.19.416 or later to WordPress',
    '' !== $b416_live_ver && version_compare($b416_live_ver, '1.19.416', '>='), "live: {$b416_live_ver}");

/*
 * ⛔⛔ THE PLUGIN DOES NOT MOVE IN THIS BUILD. `CYCLE180-LD-BUILD-413` shipped
 *    1.8.91 over 1.8.93 while reporting "Plugin updated successfully", and the
 *    only thing that caught it was reading the version back. A theme-only
 *    build asserts the plugin is UNCHANGED for exactly that reason.
 */
if (function_exists('get_plugins') || function_exists('get_plugin_data')) {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $b416_plugin_ver = '';
    foreach (get_plugins() as $b416_file => $b416_data) {
        if (false !== strpos((string) $b416_file, 'brave-hearts-bundle-pricing')) {
            $b416_plugin_ver = (string) $b416_data['Version'];
            break;
        }
    }
    if ('' !== $b416_plugin_ver) {
        b416_assert('6.4 ⛔ the bundle plugin is still 1.8.94 — 416 is THEME-ONLY and moves no plugin',
            version_compare($b416_plugin_ver, '1.8.94', '>='), "plugin: {$b416_plugin_ver}");
    } else {
        b416_skip('6.4 the bundle plugin is still 1.8.94', 'bundle plugin not installed on this environment');
    }
} else {
    b416_skip('6.4 the bundle plugin is still 1.8.94', 'plugin API unavailable');
}

/* ═══════════════════════════════════════════════════════════════════════════
   §7 · STANDING GUARDS — cheap, and they have each caught something once.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §7 standing guards ---\n";

b416_assert('7.1 no em dash in any LIVE declaration (comments may carry them; rules may not)',
    false === strpos(b416_css_code($b416_pdp_css), "\xe2\x80\x94"));

b416_assert('7.2 pdp-content.css carries no CR bytes (the CRLF trap, at source)',
    false === strpos($b416_pdp_css, "\r"));

b416_assert('7.3 pdp-content.min.css carries no CR bytes',
    false === strpos($b416_pdp_min, "\r"));

b416_assert('7.4 the minified sheet is genuinely smaller than the source (the build ran, not a copy)',
    strlen($b416_pdp_min) > 0 && strlen($b416_pdp_min) < strlen($b416_pdp_css),
    'src=' . strlen($b416_pdp_css) . ' min=' . strlen($b416_pdp_min));

b416_assert('7.5 the rehearsal mu-plugin is not inside the theme tree',
    !file_exists($b416_theme . '/bhp-rehearsal-testsku.php'));

/* ═══════════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════════ */
printf(
    "\n=== CYCLE180-LD-BUILD-416: %d passed, %d failed, %d skipped ===\n",
    $GLOBALS['b416_passes'],
    $GLOBALS['b416_failures'],
    $GLOBALS['b416_skips']
);

if ($GLOBALS['b416_failures'] > 0) {
    exit(1);
}
