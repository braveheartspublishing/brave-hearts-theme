<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-413 — the four folded items, proven rather than asserted.
 *
 * Theme 1.19.413 / bundle plugin 1.8.93.
 *
 * ⛔ WHAT THIS SUITE COVERS, one section per briefed item:
 *      §1  `CYCLE180-LDR-1` — the value-prop suite read the SKU off the PARENT.
 *      §2  `CYCLE180-LDR-2` — the offer cart door was STOCK-BLIND.
 *      §3  founder seal `1477`  — the pair landing rendered an EMPTY shell.
 *      §4  `CYCLE180-MKT-GSC-TRIAGE` R1 — `/author-visits/` was an ORPHAN.
 *      §5  the staging rehearsal mu-plugin must not exist in the theme tree.
 *
 * ⭐⭐ HOW THE OUT-OF-STOCK STATE IS TESTED WITHOUT TOUCHING A PRODUCT RECORD —
 *     read this before "improving" it, because the restraint IS the design.
 *
 *     Every stock branch below is driven by WooCommerce's own
 *     `woocommerce_product_is_in_stock` filter, added for the duration of one
 *     assertion and removed immediately afterwards.
 *
 * ⛔ WHY THAT MATTERS AND IS NOT MERELY CONVENIENT: changing `_stock_status`
 *    on any product — even on staging, even set back afterwards — is a
 *    WooCommerce product mutation, which is ANDREW'S GATE under the
 *    `lead-developer` charter and Standing Rules §6. It is also forbidden by
 *    `.claude/rules/woocommerce.md`, which requires a fresh current-turn
 *    decision from Andrew before any core product's stock status moves. This
 *    suite therefore exercises both stock states while crossing no approval
 *    line, and it cannot leave debris: a filter added in one request dies with
 *    that request.
 *
 * ⚠ WHAT A GREEN RUN THEREFORE DOES *NOT* PROVE, stated so nobody over-claims:
 *   it proves the CODE PATHS and the RENDERED MARKUP under both stock states.
 *   It does not prove how the out-of-stock page LOOKS in a browser at a given
 *   viewport, because that would need the real record out of stock. That gap
 *   is recorded explicitly in the build report rather than papered over.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-413.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no variation,
 *    no term, no option, no post, no cart, no order.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ COUNTERS LIVE IN $GLOBALS, NOT IN `global $x`, AND THIS IS NOT STYLE.
 *     `wp eval-file` includes this file INSIDE A FUNCTION, so every top-level
 *     variable here is a LOCAL. A helper declaring `global $b413_failures`
 *     would increment a DIFFERENT variable from the one the summary and
 *     `exit()` read, and the suite would print "0 failed" and exit 0 however
 *     many assertions failed. ⚠ OBSERVED on this project at 1.19.412, not
 *     theorised. A suite that cannot fail is not a suite.
 */
$GLOBALS['b413_failures'] = 0;
$GLOBALS['b413_passes']   = 0;
$GLOBALS['b413_skips']    = 0;

function b413_assert($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['b413_passes']++;
        echo "  PASS  {$label}\n";
    } else {
        $GLOBALS['b413_failures']++;
        echo "  FAIL  {$label}" . ('' !== $detail ? "  [{$detail}]" : '') . "\n";
    }
}

function b413_skip($label, $why) {
    $GLOBALS['b413_skips']++;
    echo "  SKIP  {$label}  [{$why}]\n";
}

/**
 * Run $fn with the given product ids forced OUT OF STOCK, then restore.
 *
 * ⛔ The filter is removed in a `finally`, so a throwing assertion cannot leave
 *    the store pretending a book is unavailable for the rest of the request.
 */
function b413_with_out_of_stock(array $ids, callable $fn) {
    $ids    = array_map('intval', $ids);
    $filter = function ($in_stock, $product) use ($ids) {
        return (is_object($product) && in_array((int) $product->get_id(), $ids, true)) ? false : $in_stock;
    };
    add_filter('woocommerce_product_is_in_stock', $filter, 999, 2);
    try {
        return $fn();
    } finally {
        remove_filter('woocommerce_product_is_in_stock', $filter, 999);
    }
}

$b413_theme = get_template_directory();
$b413_src   = function ($rel) use ($b413_theme) {
    $path = $b413_theme . '/' . ltrim($rel, '/');
    return is_readable($path) ? (string) file_get_contents($path) : '';
};

/**
 * ⛔⛔ STRIP COMMENTS BEFORE ASSERTING THAT A LINE OF CODE IS GONE.
 *
 * ⚠ OBSERVED, not anticipated — the first run of this suite on staging
 *   2026-09-12 reported §1.2, §1.5 and §2.1 as FAILING when all three fixes
 *   were correctly in place. The house style on this project PRESERVES THE
 *   SUPERSEDED LINE VERBATIM IN A COMMENT directly above every replacement, so
 *   a naive `strpos()` over the raw file finds the old code in the very comment
 *   that documents its removal.
 *
 * ⛔ THE NAIVE TEST IS WORSE THAN USELESS: it is a test that can only be made
 *    green by DELETING the historical record, which is the opposite of what
 *    this codebase requires. `token_get_all()` gives the executable text
 *    exactly, so the assertion asks the right question — "is this still
 *    RUNNING?" — rather than "does this string appear anywhere?".
 *
 * ⭐ The integrity control below proves the stripper actually strips; without
 *    it, a stripper that returned '' would make every "is gone" assertion pass.
 */
$b413_code = function ($src) {
    if ('' === $src || !function_exists('token_get_all')) {
        return $src;
    }
    $out = '';
    foreach (token_get_all($src) as $t) {
        if (is_array($t)) {
            if (T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0]) {
                continue;
            }
            $out .= $t[1];
        } else {
            $out .= $t;
        }
    }
    return $out;
};

echo "\n=== CYCLE180-LD-BUILD-413 — theme " . wp_get_theme()->get('Version')
    . ' / plugin ' . (defined('BHP_BUNDLE_PRICING_VERSION') ? BHP_BUNDLE_PRICING_VERSION : 'ABSENT') . " ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · VERSIONS — the build is what it says it is.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §0 versions ---\n";
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ CORRECTED 1.19.414 (`CYCLE180-LDB-12`) — A SUITE THAT GOES RED ON THE
 *     NEXT BUILD IS NOT A REGRESSION TEST, IT IS AN ALARM CLOCK.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * SUPERSEDED LINE, preserved verbatim rather than deleted:
 *
 *   b413_assert('0.1 theme reports 1.19.413', '1.19.413' === (string) wp_get_theme()->get('Version'), (string) wp_get_theme()->get('Version'));
 *
 * ⚠ OBSERVED, NOT ANTICIPATED. This suite was green at 1.19.413 and went red
 *   the moment 1.19.414 installed — not because anything it guards broke, but
 *   because it pinned an EQUALITY to the version that happened to be current
 *   when it was written. Every one of its 44 real assertions still passed.
 *
 * ⭐ WHAT THE ASSERTION IS ACTUALLY FOR: proving the build under test is not
 *   OLDER than the one that introduced these fixes — i.e. that the tester is
 *   not looking at a stale deploy. A FLOOR expresses that; an equality
 *   expresses "nothing may ever ship again", which is not a thing anyone
 *   meant to assert.
 *
 * ⛔ THE FAILURE MODE THIS PREVENTS IS THE ONE THAT MATTERS: a red suite that
 *   is "known to be fine" trains the next reader to skim past red lines, and
 *   the real regression then hides inside the noise. `CYCLE180-LD-BUILD-413`
 *   already carries one always-failing row of this exact class
 *   (`test-cycle179-407.php` §6.1) and recorded it as a finding; this is that
 *   finding acted on rather than repeated.
 *
 * ⛔ SUPERSEDED 2026-09-12 BY `CYCLE180-LD-BUILD-415`. The equality fired
 *   exactly as designed, and this note is the "someone must look at this
 *   suite" clause being honoured rather than silenced. THE SUPERSEDED TEXT IS
 *   PRESERVED VERBATIM DIRECTLY BELOW, struck, so the movement stays visible:
 *
 *     ~~⛔ THE PLUGIN ASSERTION BELOW IS DELIBERATELY LEFT AS AN EQUALITY.
 *       1.8.93 is the version that carries the `LDR-2` stock gate, and the 414
 *       brief freezes the plugin there: if the plugin moves, someone must look
 *       at this suite. The two are not inconsistent — the theme is expected to
 *       advance and the plugin is not.~~
 *
 * ⭐ WHAT CHANGED, AND WHY THIS IS A FLOOR NOW. The 414 freeze was a property
 *   of the 414 BRIEF, not of the `LDR-2` gate. The 415 brief (Andrew's notes,
 *   founder seals 1492/1493, item 5) DELIBERATELY moves the plugin to 1.8.94 to
 *   land the `LDB-9` root-cause fix at `bundle-landing.css:277`, which is the
 *   only edit that reaches `/complete-collection/`. So "the plugin has not
 *   moved" is no longer a true invariant and an equality on it now asserts a
 *   frozen moment rather than a behaviour.
 *
 * ⛔ WHAT THIS ROW STILL PROTECTS IS UNCHANGED: that the running plugin is NOT
 *   OLDER than the version carrying the `LDR-2` stock gate. A downgrade — the
 *   exact accident `CYCLE180-LD-BUILD-413` recorded, where a temp index missing
 *   `plugins` silently shipped 1.8.91 over 1.8.93 — still fails this row. Only
 *   a legitimate forward move now passes, which is precisely the distinction
 *   the equality could not draw.
 *
 * ⚠ THIS IS THE SAME CORRECTION THE RUNBOOK ALREADY MADE TO ITS OWN
 *   `.min.css` gate ("MUST be 10" -> "MUST be >= 14"), for the same reason
 *   stated there: a fixed number goes stale and then gets "corrected" by
 *   someone trusting it. The floor keeps the guard and drops the staleness.
 */
$b413_theme_version = (string) wp_get_theme()->get('Version');
b413_assert(
    '0.1 theme reports 1.19.413 or later (floor, not equality — see the block above)',
    version_compare($b413_theme_version, '1.19.413', '>='),
    $b413_theme_version
);
b413_assert(
    '0.2 bundle plugin reports 1.8.93 or later (floor since 1.19.415 — see the block above; a DOWNGRADE still fails)',
    defined('BHP_BUNDLE_PRICING_VERSION')
        && version_compare((string) BHP_BUNDLE_PRICING_VERSION, '1.8.93', '>='),
    defined('BHP_BUNDLE_PRICING_VERSION') ? BHP_BUNDLE_PRICING_VERSION : 'undefined'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · `CYCLE180-LDR-1` — the value-prop suite reads the BUY identity.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §1 LDR-1: the SKU is read off the BUY record, not the parent ---\n";

$b413_vp_raw = $b413_src('tests/test-cycle178-pdp-value-prop.php');
$b413_vp     = $b413_code($b413_vp_raw);

b413_assert(
    '1.1 the value-prop suite exists and is readable',
    '' !== $b413_vp_raw
);

/*
 * ⭐ INTEGRITY CONTROL for the comment stripper. Without this, a stripper that
 *    silently returned '' would make every "is gone" assertion below pass.
 */
b413_assert(
    '1.1a ⛔ the comment stripper works: executable text survives, the preserved superseded line does not',
    '' !== $b413_vp
        && false !== strpos($b413_vp, 'bhp_vp_assert')
        && false !== strpos($b413_vp_raw, 'SUPERSEDED')
        && false === strpos($b413_vp, 'SUPERSEDED'),
    'stripper returned ' . strlen($b413_vp) . ' of ' . strlen($b413_vp_raw) . ' bytes'
);

b413_assert(
    '1.2 ⛔ the superseded PARENT read is gone from EXECUTING code (it survives in the comment, by design)',
    false === strpos($b413_vp, "\$sku = wc_get_product( \$pid ) ? wc_get_product( \$pid )->get_sku() : '';"),
    'the 1.19.412 line is still executing'
);
b413_assert(
    '1.3 it now resolves through `bhp_colouring_buy_ids()`',
    false !== strpos($b413_vp, 'bhp_colouring_buy_ids')
);
b413_assert(
    '1.4 ⛔ the expected SKU is read from `bhp_colouring_catalog()`, not hardcoded a second time',
    false !== strpos($b413_vp, 'bhp_colouring_catalog')
);
b413_assert(
    "1.5 ⛔ the bare literal '9798996810840' is no longer an assertion value in executing code",
    false === strpos($b413_vp, "'9798996810840' === \$sku"),
    'a second owner of the catalogue SKU still exists'
);

/*
 * ⭐ THE BEHAVIOURAL HALF. §1.2–1.5 are source reads; on their own they prove
 *    the edit was made, not that it was the RIGHT edit. This proves the defect
 *    is real on whatever shape this environment actually carries.
 */
if (!function_exists('bhp_colouring_parent_ids') || !function_exists('bhp_colouring_buy_ids')) {
    b413_skip('1.6 parent-vs-buy SKU divergence', 'the 1.8.92 identity split is not loaded');
} else {
    $b413_parents = bhp_colouring_parent_ids();
    $b413_buys    = bhp_colouring_buy_ids();

    if (empty($b413_parents)) {
        b413_skip('1.6 parent-vs-buy SKU divergence', 'no colouring product resolves on this environment');
    } else {
        foreach ($b413_parents as $b413_slug => $b413_parent) {
            $b413_buy = (int) ($b413_buys[$b413_slug] ?? 0);
            $b413_pp  = wc_get_product((int) $b413_parent);
            $b413_bp  = $b413_buy ? wc_get_product($b413_buy) : null;

            $b413_parent_sku = $b413_pp ? (string) $b413_pp->get_sku() : '';
            $b413_buy_sku    = $b413_bp ? (string) $b413_bp->get_sku() : '';
            $b413_is_var     = $b413_buy > 0 && $b413_buy !== (int) $b413_parent;

            echo sprintf(
                "        [%s] parent=%d sku=\"%s\"  buy=%d sku=\"%s\"  shape=%s\n",
                $b413_slug, (int) $b413_parent, $b413_parent_sku, $b413_buy, $b413_buy_sku,
                $b413_is_var ? 'VARIABLE' : 'simple'
            );

            if ($b413_is_var) {
                /*
                 * ⛔ THIS IS THE WHOLE OF LDR-1, REPRODUCED. On a variable shape
                 *    the parent carries no SKU, so the superseded line compared
                 *    a canonical ISBN against an empty string and failed.
                 */
                b413_assert(
                    sprintf('1.6 [%s] ⛔ VARIABLE shape: the BUY record carries a SKU the PARENT does not', $b413_slug),
                    '' !== $b413_buy_sku && $b413_buy_sku !== $b413_parent_sku,
                    sprintf('parent "%s" vs buy "%s"', $b413_parent_sku, $b413_buy_sku)
                );
            } else {
                b413_assert(
                    sprintf('1.6 [%s] ✅ SIMPLE shape: parent and buy are one record, so nothing observable changes', $b413_slug),
                    $b413_buy === (int) $b413_parent && $b413_buy_sku === $b413_parent_sku
                );
            }
        }
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · `CYCLE180-LDR-2` — the offer cart door now tests stock.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §2 LDR-2: the add-to-cart door is no longer stock-blind ---\n";

$b413_shortcode_path = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/bundle-shortcode.php';
$b413_shortcode_raw  = is_readable($b413_shortcode_path) ? (string) file_get_contents($b413_shortcode_path) : '';
$b413_shortcode      = $b413_code($b413_shortcode_raw);

if ('' === $b413_shortcode_raw) {
    b413_skip('2.1 cart-door source', 'bundle-shortcode.php is not readable from ' . $b413_shortcode_path);
} else {
    b413_assert(
        '2.1 ⛔ the stock-blind gate is gone from EXECUTING code (it survives in the comment, by design)',
        false === strpos($b413_shortcode, 'if ( ! bhp_offer_is_purchasable( $offer_key ) ) {'),
        'the 1.8.92 gate is still executing'
    );
    b413_assert(
        '2.2 the cart door now asks `bhp_offer_is_offerable()`, the same predicate every render surface asks',
        false !== strpos($b413_shortcode, 'if ( ! bhp_offer_is_offerable( $offer_key ) ) {')
    );
    b413_assert(
        '2.3 ⛔ the customer-facing notice string is UNCHANGED (no new copy in this build)',
        false !== strpos($b413_shortcode, "wc_add_notice( 'That offer is not available right now.', 'error' );")
    );
}

if (!function_exists('bhp_offer_is_purchasable') || !function_exists('bhp_offer_is_offerable') || !function_exists('bhp_offer_catalog')) {
    b413_skip('2.4 predicate divergence under out-of-stock', 'the offer engine is not loaded');
} else {
    /* Pick a real offer that carries a colouring component. */
    $b413_offer_key = '';
    $b413_buy_ids   = function_exists('bhp_colouring_buy_ids') ? bhp_colouring_buy_ids() : array();
    foreach (bhp_offer_catalog() as $b413_k => $b413_row) {
        if (bhp_offer_is_purchasable($b413_k)) {
            $b413_comp = bhp_offer_components($b413_k);
            foreach ((array) $b413_comp as $b413_c) {
                if (in_array((int) ($b413_c['buy_id'] ?? 0), array_map('intval', $b413_buy_ids), true)) {
                    $b413_offer_key = $b413_k;
                    break 2;
                }
            }
        }
    }

    if ('' === $b413_offer_key) {
        b413_skip('2.4 predicate divergence under out-of-stock', 'no purchasable offer with a colouring component on this environment');
    } else {
        echo "        (offer under test: {$b413_offer_key})\n";

        /* ✅ CONTROL FIRST — while everything is in stock the two agree. */
        b413_assert(
            '2.4 ✅ CONTROL: in stock, `is_offerable` agrees with `is_purchasable`, so an ordinary add is unchanged',
            bhp_offer_is_purchasable($b413_offer_key) === bhp_offer_is_offerable($b413_offer_key)
        );

        $b413_forced = array_values(array_map('intval', $b413_buy_ids));
        b413_with_out_of_stock($b413_forced, function () use ($b413_offer_key) {
            /*
             * ⛔ THE DEFECT, REPRODUCED EXACTLY AS THE REHEARSAL OBSERVED IT:
             *      is_in_stock()     false
             *      is_purchasable()  TRUE   <- the old gate opened here
             */
            b413_assert(
                '2.5 ⛔ OUT OF STOCK: `is_purchasable()` is still TRUE — this is why the old gate let a replayed POST through',
                true === bhp_offer_is_purchasable($b413_offer_key)
            );
            b413_assert(
                '2.6 ⛔ OUT OF STOCK: `bhp_offer_is_in_stock()` is FALSE',
                false === bhp_offer_is_in_stock($b413_offer_key)
            );
            b413_assert(
                '2.7 ⭐ OUT OF STOCK: the NEW gate `bhp_offer_is_offerable()` is FALSE — the cart door now closes',
                false === bhp_offer_is_offerable($b413_offer_key)
            );
        });

        /* The filter must be gone again. */
        b413_assert(
            '2.8 ⛔ the forced-stock filter was removed — the store is not left pretending a book is unavailable',
            true === bhp_offer_is_offerable($b413_offer_key)
        );
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · Founder seal `1477` — the pair landing states a reason instead of
 *      rendering an empty shell.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §3 seal 1477: the pair landing out-of-stock notice ---\n";

$b413_pair_src_raw = $b413_src('inc/bundle-pair-landing.php');
$b413_pair_src     = $b413_code($b413_pair_src_raw);

b413_assert('3.1 `bhp_pair_landing_unavailable_by_stock()` is defined', function_exists('bhp_pair_landing_unavailable_by_stock'));
b413_assert('3.2 `bhp_pair_landing_render_unavailable()` is defined', function_exists('bhp_pair_landing_render_unavailable'));
b413_assert(
    '3.3 ⛔ the label is read from `BHP_COLOURING_UNAVAILABLE_CTA`, never re-typed as a literal',
    false !== strpos($b413_pair_src, 'BHP_COLOURING_UNAVAILABLE_CTA')
);
b413_assert(
    '3.4 ⛔ the approved wording has exactly ONE owner — the constant, not this file',
    1 >= substr_count($b413_pair_src, "__('Temporarily unavailable'"),
    substr_count($b413_pair_src, "__('Temporarily unavailable'") . ' literal occurrences (1 = the defined() fallback only)'
);

$b413_label = defined('BHP_COLOURING_UNAVAILABLE_CTA') ? (string) BHP_COLOURING_UNAVAILABLE_CTA : '';
b413_assert('3.5 the constant resolves to a non-empty string', '' !== $b413_label, $b413_label);

if (!function_exists('bhp_pair_landing_render') || !function_exists('bhp_pair_landing_offers')) {
    b413_skip('3.6 rendered behaviour', 'the pair landing is not loaded');
} else {
    $b413_offers   = bhp_pair_landing_offers();
    $b413_pair_key = (string) ($b413_offers['paperback'] ?? '');

    if ('' === $b413_pair_key || !function_exists('bhp_offer_components')) {
        b413_skip('3.6 rendered behaviour', 'no paperback pair offer resolves on this environment');
    } else {
        /* ✅ CONTROL: in stock, the real page renders and the notice does NOT. */
        $b413_live = bhp_pair_landing_render();
        b413_assert(
            '3.6 ✅ CONTROL: in stock the full page renders (hero present)',
            false !== strpos($b413_live, 'bhp-pair-landing__hero') || false !== strpos($b413_live, 'bhp-landing-hero'),
            'rendered ' . strlen($b413_live) . ' bytes'
        );
        b413_assert(
            '3.7 ✅ CONTROL: in stock the unavailable notice is ABSENT',
            false === strpos($b413_live, 'bhp-pair-landing--unavailable')
        );

        $b413_comp     = (array) bhp_offer_components($b413_pair_key);
        $b413_comp_ids = array();
        foreach ($b413_comp as $b413_c) {
            $b413_comp_ids[] = (int) ($b413_c['buy_id'] ?? 0);
        }

        b413_with_out_of_stock($b413_comp_ids, function () use ($b413_label) {
            $out = bhp_pair_landing_render();

            b413_assert(
                '3.8 ⛔ OUT OF STOCK: the shortcode no longer returns an EMPTY string (seal 1477)',
                '' !== trim($out),
                'still empty'
            );
            b413_assert(
                '3.9 ⭐ OUT OF STOCK: it renders the approved string from the constant',
                false !== strpos($out, esc_html($b413_label)),
                'rendered: ' . substr(wp_strip_all_tags($out), 0, 120)
            );
            b413_assert(
                '3.10 it carries the scoped wrapper class so the sheet can style it',
                false !== strpos($out, 'bhp-pair-landing--unavailable')
            );
            /*
             * ⛔ `R1.4` — "nothing is advertised that cannot be bought" — is NOT
             *    weakened. The notice must carry no price, no add control and
             *    no form.
             */
            b413_assert(
                '3.11 ⛔ R1.4 HOLDS: no price is rendered in the unavailable state',
                false === strpos($out, get_woocommerce_currency_symbol()),
                'a currency symbol reached the out-of-stock page'
            );
            b413_assert(
                '3.12 ⛔ R1.4 HOLDS: no <form> and no add-to-cart control is rendered',
                false === stripos($out, '<form') && false === stripos($out, 'bhp_bundle_action')
            );
            b413_assert(
                '3.13 ⛔ no date, no promise about when it returns',
                false === stripos($out, 'back in stock') && false === stripos($out, 'soon') && false === stripos($out, 'restock')
            );
        });

        b413_assert(
            '3.14 ⛔ the forced-stock filter was removed — the page renders normally again',
            false === strpos(bhp_pair_landing_render(), 'bhp-pair-landing--unavailable')
        );
    }
}

/*
 * ⛔ THE OTHER REFUSALS MUST STAY SILENT. Out of stock is the ONLY branch that
 *    speaks; a visitor-gated or unpriceable page still renders nothing. This
 *    asserts the predicate is narrow rather than trusting the comment.
 */
if (function_exists('bhp_pair_landing_unavailable_by_stock')) {
    b413_assert(
        '3.15 ⛔ the predicate is narrow: it is FALSE while the offer is in stock',
        false === bhp_pair_landing_unavailable_by_stock()
    );
    b413_assert(
        '3.16 ⛔ it asks `bhp_offer_is_in_stock`, not `bhp_offer_is_offerable` — the visit gate must NOT print "temporarily unavailable"',
        false !== strpos($b413_pair_src, 'bhp_offer_is_purchasable($key) && !bhp_offer_is_in_stock($key)')
    );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · `CYCLE180-MKT-GSC-TRIAGE` R1 — `/author-visits/` is no longer an orphan.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §4 R1: internal links to /author-visits/ ---\n";

$b413_av = get_page_by_path('author-visits');
$b413_av_title = $b413_av ? (string) $b413_av->post_title : '';

if (!$b413_av) {
    b413_skip('4.1 the link text equals the page title', 'no /author-visits/ page on this environment');
} else {
    echo "        (/author-visits/ is post {$b413_av->ID}, title \"{$b413_av_title}\")\n";
    b413_assert(
        '4.1 ⛔ the link text is the PAGE\'S OWN TITLE, not invented copy',
        'Author Visits' === $b413_av_title,
        $b413_av_title
    );
}

$b413_footer_src   = $b413_src('footer.php');
$b413_teachers_src = $b413_src('page-teachers.php');

b413_assert(
    '4.2 footer.php links to /author-visits/',
    false !== strpos($b413_footer_src, "home_url('/author-visits/')")
);
b413_assert(
    '4.3 footer.php labels it "Author Visits"',
    false !== strpos($b413_footer_src, "esc_html_e('Author Visits', 'brave-hearts')")
);
b413_assert(
    '4.4 page-teachers.php links to /author-visits/',
    false !== strpos($b413_teachers_src, "home_url('/author-visits/')")
);
b413_assert(
    '4.5 page-teachers.php labels it "Author Visits"',
    false !== strpos($b413_teachers_src, "esc_html_e('Author Visits', 'brave-hearts')")
);
b413_assert(
    '4.6 ⛔ NO NEW SENTENCE on /teachers/ — the availability line is byte-unchanged',
    false !== strpos($b413_teachers_src, "esc_html_e('Availability depends on location, timing, audience, and school-year schedule.', 'brave-hearts')")
);
b413_assert(
    '4.7 ⛔ the teachers link reuses the EXISTING button pattern, inventing no class',
    false !== strpos($b413_teachers_src, 'class="btn btn-secondary" href="<?php echo esc_url(home_url(\'/author-visits/\'))')
);

/*
 * ⭐ THE RENDERED HALF. Source reads prove the edit; these prove the LINK
 *    ACTUALLY REACHES A READER, which is the entire point of R1.
 */
$b413_fetch = function ($url) {
    $res = wp_remote_get($url, array('timeout' => 45, 'sslverify' => false));
    return is_wp_error($res) ? '' : (string) wp_remote_retrieve_body($res);
};

$b413_home_doc = $b413_fetch(home_url('/'));
if ('' === $b413_home_doc) {
    b413_skip('4.8 rendered home document carries the footer link', 'loopback fetch of the home page failed');
} else {
    b413_assert(
        '4.8 ⭐ the RENDERED home document links to /author-visits/ (this is what closes the orphan for /blog/ and the home page at once)',
        false !== strpos($b413_home_doc, '/author-visits/')
    );

    /* §4.7 of test-cro-iterate5 guards Andrew's footer prune; keep them in step. */
    if (preg_match('/<footer\b[\s\S]*?<\/footer>/i', $b413_home_doc, $b413_m)) {
        $b413_flinks = preg_match_all('/<a\b[^>]*href=/', $b413_m[0]);
        echo "        (footer link count on the home document: {$b413_flinks})\n";
        b413_assert(
            '4.9 ⛔ the footer still carries at most 15 links (CYCLE180-LDB-5: the pre-413 count was ALREADY 14, measured on production) — Andrew\'s prune ruling is guarded, not abandoned',
            $b413_flinks > 0 && $b413_flinks <= 15,
            (string) $b413_flinks
        );
    } else {
        b413_skip('4.9 footer link ceiling', 'no <footer> element found in the home document');
    }
}

$b413_teachers_doc = $b413_fetch(home_url('/teachers/'));
if ('' === $b413_teachers_doc) {
    b413_skip('4.10 rendered /teachers/ carries the link', 'loopback fetch of /teachers/ failed');
} else {
    b413_assert(
        '4.10 ⭐ the RENDERED /teachers/ document links to /author-visits/ (0 before this build, measured by CYCLE180-MKT-GSC-TRIAGE)',
        false !== strpos($b413_teachers_doc, '/author-visits/')
    );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · The staging rehearsal mu-plugin must NEVER be in the theme tree.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ `wp-content/mu-plugins/bhp-rehearsal-testsku.php` is rehearsal scaffolding
 *    created by `CYCLE180-LD-COLORING-MIGRATION-REHEARSAL`. It injects a TEST
 *    SKU into the colouring resolver so staging can hold the migrated state.
 *    On PRODUCTION it would point the live catalogue at a product that does not
 *    exist. It is a staging-only file and it must not exist inside the theme,
 *    which is what gets packaged.
 * ⭐ The ARTEFACT-level assertion is in the entry gate (see `docs/RUNBOOK.md`);
 *    this is the source-tree half of the same guard.
 */
echo "\n--- §5 the rehearsal mu-plugin is not in the theme tree ---\n";

$b413_found = glob($b413_theme . '/**/bhp-rehearsal-testsku.php') ?: array();
$b413_found = array_merge($b413_found, glob($b413_theme . '/bhp-rehearsal-testsku.php') ?: array());
b413_assert(
    '5.1 ⛔ no `bhp-rehearsal-testsku.php` anywhere in the theme directory',
    empty($b413_found),
    implode(', ', $b413_found)
);

$b413_refs = array();
foreach (array('functions.php', 'inc/colouring-line.php', 'inc/bundle-pair-landing.php', 'inc/catalog-surfaces.php') as $b413_rel) {
    if (false !== strpos($b413_src($b413_rel), 'bhp-rehearsal-testsku')) {
        $b413_refs[] = $b413_rel;
    }
}
b413_assert(
    '5.2 ⛔ no shipped theme file references the rehearsal scaffolding',
    empty($b413_refs),
    implode(', ', $b413_refs)
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== {$GLOBALS['b413_passes']} passed, {$GLOBALS['b413_failures']} failed, {$GLOBALS['b413_skips']} skipped ===\n";
exit($GLOBALS['b413_failures'] > 0 ? 1 : 0);
