<?php
/**
 * ⭐⭐ THE COLOURING IDENTITY SPLIT — BOTH PRODUCT SHAPES, ON A REAL DATABASE.
 *
 * Theme 1.19.412 / plugin 1.8.92, `CYCLE180-LD-BUILD-412-RESOLVER`.
 *
 * ⛔ WHAT THIS SUITE EXISTS TO CATCH, stated plainly because the defect is
 *    invisible: until 1.8.92 the whole colouring line resolved through ONE id
 *    from `wc_get_product_id_by_sku()`. That is correct while the colouring
 *    book is a SIMPLE product (618 production / 4065 staging) and silently
 *    wrong the moment it becomes a VARIABLE product with one "Perfect Bound"
 *    variation carrying the SKU — the lookup then returns the VARIATION, and
 *    permalinks, archive queries, thumbnails and `get_queried_object_id()`
 *    comparisons all quietly stop working. Nothing throws. The PDP, the shop
 *    card and the read-aloud tile just stop being there.
 *
 * ⭐⭐ HOW THE VARIABLE SHAPE IS TESTED WITHOUT CREATING A PRODUCT — read this
 *     before "improving" it, because the restraint is the design.
 *
 *     This store ALREADY CONTAINS a product of exactly the shape under test:
 *     the Mariana Trench paperback, parent 333 with ONE variation 334 that
 *     carries the SKU (`bhp_book_registry()`'s `pb_product` / `pb_variation`).
 *     So the variable shape is exercised by pointing the colouring resolver at
 *     a REAL variable product via the documented `bhp_colouring_product_ids`
 *     filter, rather than by creating a throwaway one.
 *
 * ⛔ WHY THAT MATTERS AND IS NOT MERELY CONVENIENT: creating a WooCommerce
 *    product — even a scratch one on staging, even one deleted afterwards —
 *    is a product-record mutation, which is ANDREW'S GATE under the
 *    `lead-developer` charter and the Standing Rules. This suite therefore
 *    proves the resolver against real variable-product records while crossing
 *    no approval line at all. It also cannot leave debris behind: a filter
 *    added in one request dies with that request.
 *
 * ⚠ WHAT THIS SUITE THEREFORE DOES *NOT* PROVE, stated so no reader
 *   over-claims from a green run: it proves IDENTITY RESOLUTION and PAYLOAD
 *   SHAPE on both product shapes. It does not prove that a browser can
 *   complete an add-to-cart against a variable COLOURING product, because no
 *   such record exists on any environment. The nearest live evidence for that
 *   half is that the Mariana paperback PDP — the same shape, the same
 *   `add-to-cart` + `variation_id` + attribute URL construction — sells today.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-colouring-identity-split.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no
 *    variation, no term, no option, no cart, no order.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ COUNTERS LIVE IN $GLOBALS, NOT IN `global $x`, AND THIS IS NOT STYLE.
 *     `wp eval-file` includes this file INSIDE A FUNCTION, so every top-level
 *     variable here is a LOCAL. A helper declaring `global $cis_failures`
 *     therefore increments a DIFFERENT variable from the one the summary and
 *     `exit()` read — the suite prints "0 failed" and exits 0 no matter how
 *     many assertions failed.
 * ⚠ OBSERVED, NOT THEORISED: the first run of this suite on staging
 *   2026-09-12 printed "0 passed, 0 failed, 0 skipped" directly beneath 46
 *   visible PASS lines. A suite that cannot fail is not a suite.
 */
$GLOBALS['cis_failures'] = 0;
$GLOBALS['cis_passes']   = 0;
$GLOBALS['cis_skips']    = 0;

function cis_assert($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['cis_passes']++;
        echo "  PASS  {$label}\n";
    } else {
        $GLOBALS['cis_failures']++;
        echo "  FAIL  {$label}" . ($detail !== '' ? "  [{$detail}]" : '') . "\n";
    }
}

function cis_skip($label, $why) {
    $GLOBALS['cis_skips']++;
    echo "  SKIP  {$label}  [{$why}]\n";
}

echo "\n=== colouring identity split — 1.19.412 / 1.8.92 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · THE SPLIT EXISTS AT ALL
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §0 the functions are present ---\n";

foreach ([
    'bhp_colouring_identity_for_id',
    'bhp_colouring_identity_map',
    'bhp_colouring_parent_ids',
    'bhp_colouring_buy_ids',
    'bhp_colouring_slug_for_any_id',
    'bhp_colouring_product_ids',
] as $fn) {
    cis_assert("0.1 `{$fn}()` is defined", function_exists($fn));
}
cis_assert(
    '0.2 the theme-side shim `bhp_colouring_ids_for_product()` is defined',
    function_exists('bhp_colouring_ids_for_product')
);

if (!function_exists('bhp_colouring_identity_map')) {
    echo "\nFATAL: the split is not loaded; nothing below can run.\n";
    exit(1);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · SHAPE (a) — THE SIMPLE PRODUCT THAT IS LIVE TODAY
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §1 shape (a): the live SIMPLE colouring product ---\n";

$cis_live = bhp_colouring_identity_map();

if (empty($cis_live['mariana'])) {
    cis_skip(
        '1.x the live colouring product',
        'no colouring SKU resolves on this environment'
    );
} else {
    $cis_id = $cis_live['mariana'];
    $cis_product = wc_get_product((int) $cis_id['parent']);

    echo "        resolved: parent={$cis_id['parent']} buy={$cis_id['buy']} variation={$cis_id['variation']}\n";
    echo "        type:     " . ($cis_product ? $cis_product->get_type() : 'UNLOADABLE') . "\n";

    cis_assert('1.1 the parent id resolves to a loadable product', (bool) $cis_product);

    if ($cis_product && $cis_product->is_type('simple')) {
        cis_assert(
            '1.2 on a SIMPLE product parent === buy',
            (int) $cis_id['parent'] === (int) $cis_id['buy'],
            "parent {$cis_id['parent']} vs buy {$cis_id['buy']}"
        );
        cis_assert(
            '1.3 on a SIMPLE product variation is 0',
            0 === (int) $cis_id['variation']
        );
        /*
         * ⛔ THE BACK-COMPATIBILITY ASSERTION, and it is the one that protects
         *    production. 1.8.92 must be a NO-OP on today's shape. If this ever
         *    fails, the split has changed live behaviour on 618/4065 and must
         *    not ship.
         */
        $cis_legacy = bhp_colouring_product_ids();
        cis_assert(
            '1.4 ⛔ `bhp_colouring_product_ids()` returns exactly what 1.8.91 returned',
            (int) ($cis_legacy['mariana'] ?? 0) === (int) $cis_id['parent'],
            'legacy ' . (int) ($cis_legacy['mariana'] ?? 0) . ' vs parent ' . $cis_id['parent']
        );
    } else {
        cis_skip('1.2-1.4 simple-shape assertions', 'live colouring product is not simple');
    }

    // The PDP payload must build against the live record.
    if (function_exists('bhp_colouring_purchase_data')) {
        $cis_pd = bhp_colouring_purchase_data((int) $cis_id['parent']);
        cis_assert('1.5 purchase data builds from the PARENT id', is_array($cis_pd));
        if (is_array($cis_pd)) {
            cis_assert(
                '1.6 payload product_id is the parent',
                (int) $cis_pd['paperback']['product_id'] === (int) $cis_id['parent']
            );
            cis_assert(
                '1.7 payload variation_id matches the identity map',
                (int) $cis_pd['paperback']['variation_id'] === (int) $cis_id['variation']
            );
            cis_assert(
                '1.8 canonical_url is a real permalink, not empty',
                is_string($cis_pd['canonical_url']) && '' !== $cis_pd['canonical_url'],
                (string) $cis_pd['canonical_url']
            );
        }
        // ⭐ And from the BUY id too — the PDP is reachable by either number.
        $cis_pd_buy = bhp_colouring_purchase_data((int) $cis_id['buy']);
        cis_assert('1.9 purchase data ALSO builds from the BUY id', is_array($cis_pd_buy));
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · SHAPE (b) — A REAL VARIABLE PRODUCT WITH ONE SKU-CARRYING VARIATION
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §2 shape (b): a REAL variable product, injected by filter ---\n";

$cis_reg = function_exists('bhp_book_registry') ? bhp_book_registry() : [];
$cis_var_parent = 0;
$cis_var_child  = 0;

foreach ($cis_reg as $cis_book) {
    if (!empty($cis_book['pb_variation'])) {
        $cis_var_parent = (int) $cis_book['pb_product'];
        $cis_var_child  = (int) $cis_book['pb_variation'];
        break;
    }
}

if (!$cis_var_parent || !$cis_var_child) {
    cis_skip('2.x the variable shape', 'no registry title has a pb_variation on this environment');
} else {
    $cis_vp = wc_get_product($cis_var_parent);
    $cis_vc = wc_get_product($cis_var_child);
    echo "        using real records: parent {$cis_var_parent} (" . ($cis_vp ? $cis_vp->get_type() : '?') . ")"
       . " / variation {$cis_var_child} (" . ($cis_vc ? $cis_vc->get_type() : '?') . ")\n";

    cis_assert('2.0 the stand-in parent really is a variable product', $cis_vp && $cis_vp->is_type('variable'));
    cis_assert('2.0b the stand-in child really is a variation', $cis_vc && $cis_vc->is_type('variation'));

    /*
     * ⭐ THE CORE ASSERTION OF THE WHOLE RELEASE, and it needs no filter:
     *    handed the id a SKU lookup would return on a variable shape (the
     *    VARIATION), the resolver must hand back the PARENT for page work and
     *    the VARIATION for cart work.
     */
    $cis_from_child = bhp_colouring_identity_for_id($cis_var_child);
    cis_assert(
        '2.1 ⭐ a VARIATION id resolves to its PARENT for page work',
        (int) $cis_from_child['parent'] === $cis_var_parent,
        "got {$cis_from_child['parent']}, want {$cis_var_parent}"
    );
    cis_assert(
        '2.2 ⭐ a VARIATION id stays the BUY id for cart work',
        (int) $cis_from_child['buy'] === $cis_var_child,
        "got {$cis_from_child['buy']}, want {$cis_var_child}"
    );
    cis_assert(
        '2.3 ⭐ the variation id is reported as such',
        (int) $cis_from_child['variation'] === $cis_var_child
    );

    // ── And from the PARENT of a single-variation variable product.
    $cis_from_parent = bhp_colouring_identity_for_id($cis_var_parent);
    cis_assert(
        '2.4 a VARIABLE PARENT id keeps itself as parent',
        (int) $cis_from_parent['parent'] === $cis_var_parent
    );
    cis_assert(
        '2.5 …and resolves its single child as the buy id',
        (int) $cis_from_parent['buy'] === $cis_var_child,
        "got {$cis_from_parent['buy']}, want {$cis_var_child}"
    );

    /*
     * ⭐ NOW THE FULL PIPELINE, with the colouring resolver pointed at that
     *    real variable product exactly as a SKU lookup would point it on a
     *    migrated store.
     */
    $cis_inject = function () use ($cis_var_child) {
        return ['mariana' => $cis_var_child];
    };
    add_filter('bhp_colouring_product_ids', $cis_inject, 99);

    // The SKU-lookup cache is static, but the FILTER runs every call — which
    // is precisely why the plugin applies it on every call. Verify that held.
    $cis_map = bhp_colouring_identity_map();

    cis_assert(
        '2.6 the injected variable shape reaches the identity map',
        isset($cis_map['mariana']) && (int) $cis_map['mariana']['parent'] === $cis_var_parent,
        isset($cis_map['mariana']) ? json_encode($cis_map['mariana']) : 'absent'
    );
    cis_assert(
        '2.7 `bhp_colouring_parent_ids()` yields the PARENT',
        (int) (bhp_colouring_parent_ids()['mariana'] ?? 0) === $cis_var_parent
    );
    cis_assert(
        '2.8 `bhp_colouring_buy_ids()` yields the VARIATION',
        (int) (bhp_colouring_buy_ids()['mariana'] ?? 0) === $cis_var_child
    );

    /*
     * ⛔ THE PAGE-SIDE FUNCTIONS THAT WOULD HAVE FAILED SILENTLY. These three
     *    are the read-aloud tile's exact calls, and all three return something
     *    useless for a variation.
     */
    cis_assert(
        '2.9 ⛔ the parent id has a usable permalink (a variation does not)',
        is_string(get_permalink($cis_var_parent)) && '' !== get_permalink($cis_var_parent)
    );
    cis_assert(
        '2.10 ⛔ the parent id is a `product` post (an archive query needs that)',
        'product' === get_post_type($cis_var_parent),
        (string) get_post_type($cis_var_parent)
    );

    // ── Identity must answer YES to BOTH ids, or half the call sites break.
    cis_assert(
        '2.11 ⭐ the PARENT id is recognised as colouring',
        'mariana' === bhp_colouring_slug_for_any_id($cis_var_parent)
    );
    cis_assert(
        '2.12 ⭐ the VARIATION id is recognised as colouring',
        'mariana' === bhp_colouring_slug_for_any_id($cis_var_child)
    );
    cis_assert(
        '2.13 a cart line (parent + variation) is identified',
        'mariana' === bhp_bundle_identify_colouring_item($cis_var_parent, $cis_var_child)
    );
    cis_assert(
        '2.14 an unrelated id is NOT identified as colouring',
        null === bhp_colouring_slug_for_any_id(999999)
    );

    /*
     * ⭐ THE ADD-TO-CART URL — the defect the brief names explicitly. On a
     *    variable shape the URL must carry `add-to-cart=<parent>` AND
     *    `variation_id=<variation>`, or WooCommerce refuses the add.
     */
    if (function_exists('bhp_colouring_purchase_data')) {
        $cis_vpd = bhp_colouring_purchase_data($cis_var_parent);
        cis_assert('2.15 purchase data builds on the variable shape', is_array($cis_vpd));
        if (is_array($cis_vpd)) {
            $cis_add = (string) $cis_vpd['paperback']['add_url'];
            echo "        add_url: {$cis_add}\n";
            cis_assert(
                '2.16 ⭐ add_url carries add-to-cart=<PARENT>',
                false !== strpos($cis_add, 'add-to-cart=' . $cis_var_parent),
                $cis_add
            );
            cis_assert(
                '2.17 ⭐⭐ add_url carries variation_id=<VARIATION> — the hard-coded 0 is gone',
                false !== strpos($cis_add, 'variation_id=' . $cis_var_child),
                $cis_add
            );
            cis_assert(
                '2.18 ⛔ add_url does NOT carry variation_id=0',
                false === strpos($cis_add, 'variation_id=0'),
                $cis_add
            );
            cis_assert(
                '2.19 payload product_id is the PARENT, not the variation',
                (int) $cis_vpd['paperback']['product_id'] === $cis_var_parent
            );
            cis_assert(
                '2.20 payload variation_id is the VARIATION',
                (int) $cis_vpd['paperback']['variation_id'] === $cis_var_child
            );
            /*
             * ⛔ PRICE COMES OFF THE VARIATION. A variable parent's
             *    `get_price_html()` is a RANGE; a rail showing a range for a
             *    single-format book is the defect this half prevents.
             */
            cis_assert(
                '2.21 ⭐ price is read from the VARIATION, not the parent',
                '' !== (string) $cis_vpd['paperback']['price'],
                'price="' . $cis_vpd['paperback']['price'] . '"'
            );
            cis_assert(
                '2.22 SKU is read from the VARIATION',
                (string) $cis_vpd['paperback']['sku'] === (string) $cis_vc->get_sku(),
                'payload="' . $cis_vpd['paperback']['sku'] . '" record="' . $cis_vc->get_sku() . '"'
            );
            cis_assert(
                '2.23 the title is the PARENT post title (a variation appends its attributes)',
                (string) $cis_vpd['title'] === (string) $cis_vp->get_name(),
                'payload="' . $cis_vpd['title'] . '"'
            );
        }
    }

    /*
     * ⭐ THE OFFER ENGINE — pair/combo. Its colouring component carried
     *    `variation_id => 0` hard-coded.
     */
    if (function_exists('bhp_offer_components') && function_exists('bhp_offer_catalog')) {
        $cis_ran_offer = false;
        foreach (bhp_offer_catalog() as $cis_key => $cis_offer) {
            if (empty($cis_offer['colouring'])) {
                continue;
            }
            $cis_comp = bhp_offer_components($cis_key);
            if (!is_array($cis_comp)) {
                continue;
            }
            foreach ($cis_comp as $cis_c) {
                if ('colouring' !== $cis_c['line']) {
                    continue;
                }
                $cis_ran_offer = true;
                cis_assert(
                    "2.24 ⭐ offer `{$cis_key}` colouring component product_id is the PARENT",
                    (int) $cis_c['product_id'] === $cis_var_parent,
                    "got {$cis_c['product_id']}, want {$cis_var_parent}"
                );
                cis_assert(
                    "2.25 ⭐⭐ offer `{$cis_key}` colouring component variation_id is the VARIATION",
                    (int) $cis_c['variation_id'] === $cis_var_child,
                    "got {$cis_c['variation_id']}, want {$cis_var_child}"
                );
                cis_assert(
                    "2.26 offer `{$cis_key}` buy_id is the VARIATION",
                    (int) $cis_c['buy_id'] === $cis_var_child
                );
                break 2;
            }
        }
        if (!$cis_ran_offer) {
            cis_skip('2.24-2.26 offer components', 'no buyable colouring offer on this environment');
        }
    } else {
        // ⛔ Loud, not silent. The first version of this suite named these
        //    functions WRONGLY (`bhp_bundle_offer_*`), so the whole block was
        //    dead code that printed nothing and passed.
        cis_skip('2.24-2.26 offer components', 'bhp_offer_catalog()/bhp_offer_components() not loaded');
    }

    remove_filter('bhp_colouring_product_ids', $cis_inject, 99);

    // ⛔ The injection must not outlive itself.
    $cis_after = bhp_colouring_identity_map();
    cis_assert(
        '2.27 ⛔ removing the filter restores the real map (no leakage)',
        (int) ($cis_after['mariana']['parent'] ?? 0) !== $cis_var_parent
            || empty($cis_live['mariana']),
        'still ' . json_encode($cis_after['mariana'] ?? null)
    );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · DEGRADATION — a resolver that cannot resolve must fail to the simple
 *      shape, never to zero.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §3 degradation ---\n";

$cis_zero = bhp_colouring_identity_for_id(0);
cis_assert('3.1 id 0 yields zeros rather than a bogus identity', 0 === (int) $cis_zero['parent']);

$cis_ghost = bhp_colouring_identity_for_id(999999);
cis_assert(
    '3.2 ⛔ an unloadable id FAILS OPEN to the simple shape, not to zero',
    999999 === (int) $cis_ghost['parent'] && 999999 === (int) $cis_ghost['buy'],
    json_encode($cis_ghost)
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== {$GLOBALS['cis_passes']} passed, {$GLOBALS['cis_failures']} failed, {$GLOBALS['cis_skips']} skipped ===\n";
exit($GLOBALS['cis_failures'] > 0 ? 1 : 0);
