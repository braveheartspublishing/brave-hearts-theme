<?php
/**
 * Format-selector default test suite (1.19.166) — CYCLE143-CX-2 / CYCLE143-CX-24.
 *
 * No PHPUnit exists in this theme (see docs/RUNBOOK.md) — verification has
 * always been wp-cli-driven. This exercises the real functions and the real
 * rendered markup against a real WordPress bootstrap.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-book-formats.php --user=1
 *
 * Exits non-zero on any failure so it can gate a deploy.
 *
 * ⛔ THIS SUITE WRITES NOTHING. It creates no post, product, option, order or
 *    term, and it mutates no WooCommerce record. It sets $_GET and the global
 *    $wp_query for the duration of a single assertion and restores both, which
 *    is process-local to this WP-CLI invocation and never touches the database.
 *
 * WHAT IT CANNOT PROVE, stated so nobody reads more into a green run: it does
 * not open a browser, so it says nothing about click behaviour, the rendered
 * appearance of the pressed card, viewport behaviour or console errors. Those
 * belong to a browser QA pass.
 */
defined('ABSPATH') || exit;

$failures = [];

if (!function_exists('bhp_test_assert')) {
    function bhp_test_assert(&$failures, $label, $condition) {
        if ($condition) {
            WP_CLI::log("PASS: $label");
        } else {
            WP_CLI::warning("FAIL: $label");
            $failures[] = $label;
        }
    }
}

/**
 * Runs a callback with the main query pointed at $product_id and $_GET set to
 * $get, then restores both. Nothing persists past the callback.
 */
if (!function_exists('bhp_test_with_product_context')) {
    function bhp_test_with_product_context($product_id, array $get, callable $fn) {
        global $wp_query, $wp_the_query;

        $prev_get       = $_GET;
        $prev_query     = $wp_query;
        $prev_the_query = $wp_the_query;

        $_GET = $get;

        $q = new WP_Query([
            'p'         => (int) $product_id,
            'post_type' => 'product',
        ]);
        $q->is_single     = true;
        $q->is_singular   = true;
        $q->is_home       = false;
        $wp_query         = $q;
        $wp_the_query     = $q;

        try {
            return $fn();
        } finally {
            $_GET         = $prev_get;
            $wp_query     = $prev_query;
            $wp_the_query = $prev_the_query;
            wp_reset_postdata();
        }
    }
}

// ---------------------------------------------------------------------
// 0. The functions under test exist at all
// ---------------------------------------------------------------------
bhp_test_assert($failures, 'bhp_book_incoming_format() is defined', function_exists('bhp_book_incoming_format'));
bhp_test_assert($failures, 'bhp_book_viewed_format() is defined', function_exists('bhp_book_viewed_format'));
bhp_test_assert($failures, 'bhp_book_default_format() is defined', function_exists('bhp_book_default_format'));
bhp_test_assert($failures, 'bhp_book_format_order() is defined', function_exists('bhp_book_format_order'));

if ($failures) {
    WP_CLI::error('Core functions missing — the rest of the suite cannot run meaningfully.');
}

$registry = bhp_book_registry();
bhp_test_assert($failures, 'registry holds exactly three titles', count($registry) === 3);

// ---------------------------------------------------------------------
// 1. THE DEFECT ITSELF — a canonical (paperback) product page with NO query
//    string must open on PAPERBACK, on every title.
//    Before 1.19.166 every one of these returned 'hardcover'.
// ---------------------------------------------------------------------
foreach ($registry as $key => $book) {
    $pb = (int) $book['pb_product'];
    $got = bhp_test_with_product_context($pb, [], 'bhp_book_incoming_format');
    bhp_test_assert(
        $failures,
        "CX-2: paperback product {$pb} ({$key}) with no query opens on PAPERBACK, got '{$got}'",
        'paperback' === $got
    );
}

// ---------------------------------------------------------------------
// 2. The explicit parameter still wins — in BOTH directions, on a paperback
//    page. This is the path the legacy hardcover 301 depends on.
// ---------------------------------------------------------------------
foreach ($registry as $key => $book) {
    $pb = (int) $book['pb_product'];

    foreach (['paperback', 'hardcover', 'kindle', 'collection'] as $fmt) {
        $got = bhp_test_with_product_context($pb, ['bhp_format' => $fmt], 'bhp_book_incoming_format');
        bhp_test_assert(
            $failures,
            "?bhp_format={$fmt} still wins on product {$pb} ({$key}), got '{$got}'",
            $fmt === $got
        );
    }

    // A value outside the whitelist is still not trusted, and now falls to the
    // URL's own format rather than to the site-wide default.
    $got = bhp_test_with_product_context($pb, ['bhp_format' => 'audiobook'], 'bhp_book_incoming_format');
    bhp_test_assert(
        $failures,
        "an unwhitelisted ?bhp_format is rejected on product {$pb}, got '{$got}'",
        'paperback' === $got
    );

    $got = bhp_test_with_product_context($pb, ['bhp_format' => ''], 'bhp_book_incoming_format');
    bhp_test_assert(
        $failures,
        "an empty ?bhp_format is rejected on product {$pb}, got '{$got}'",
        'paperback' === $got
    );
}

// ---------------------------------------------------------------------
// 3. A HARDCOVER product page resolves to hardcover.
//    Those URLs 301 before rendering, so this proves the resolver's own rule
//    rather than the redirect; it is the belt to the redirect's braces.
// ---------------------------------------------------------------------
foreach ($registry as $key => $book) {
    $hc = (int) $book['hc_product'];
    $got = bhp_test_with_product_context($hc, [], 'bhp_book_viewed_format');
    bhp_test_assert(
        $failures,
        "hardcover product {$hc} ({$key}) is seen as HARDCOVER, got '{$got}'",
        'hardcover' === $got
    );
}

// ---------------------------------------------------------------------
// 4. NOTHING ELSE MOVED. The site-wide default and the default card order are
//    the contract every funnel/landing/collection surface reads, and this
//    change must not have touched them.
// ---------------------------------------------------------------------
$default = bhp_book_default_format();
bhp_test_assert($failures, "site-wide default is still a physical format ('{$default}')",
    in_array($default, ['paperback', 'hardcover'], true));

if (function_exists('bhp_bundle_default_format')) {
    bhp_test_assert($failures, 'site-wide default still delegates to the bundle plugin',
        $default === bhp_bundle_default_format());
}

$order = bhp_book_format_order();
bhp_test_assert($failures, 'bhp_book_format_order() still lists exactly the two physical formats, once each',
    count($order) === 2 && in_array('paperback', $order, true) && in_array('hardcover', $order, true));
bhp_test_assert($failures, 'bhp_book_format_order() still opens with the site-wide default',
    isset($order[0]) && $order[0] === $default);

// Off a product page entirely (no book context) the site-wide default governs,
// which is what the funnel and collection surfaces rely on.
$prev_get = $_GET;
$_GET = [];
bhp_test_assert($failures, 'with no product context the site-wide default still governs',
    bhp_book_incoming_format() === $default);
$_GET = $prev_get;

// ---------------------------------------------------------------------
// 5. THE RENDERED MARKUP — the part a customer actually sees.
//    Rendered through the real template with the real WooCommerce data.
// ---------------------------------------------------------------------
foreach ($registry as $key => $book) {
    $pb = (int) $book['pb_product'];

    $html = bhp_test_with_product_context($pb, [], function () {
        ob_start();
        bhp_book_render_format_selector();
        return ob_get_clean();
    });

    bhp_test_assert($failures, "selector renders on product {$pb} ({$key})", strpos($html, 'bhp-formats__grid') !== false);

    bhp_test_assert(
        $failures,
        "markup {$pb}: data-bhp-format-initial is paperback",
        strpos($html, 'data-bhp-format-initial="paperback"') !== false
    );

    // The PAPERBACK card carries the pressed state...
    bhp_test_assert(
        $failures,
        "markup {$pb}: the PAPERBACK card is the selected one",
        (bool) preg_match('/class="bhp-format-card is-selected" data-bhp-format="paperback" aria-pressed="true"/', $html)
    );

    // ...and the HARDCOVER card does not.
    bhp_test_assert(
        $failures,
        "markup {$pb}: the HARDCOVER card is NOT selected",
        (bool) preg_match('/class="bhp-format-card" data-bhp-format="hardcover" aria-pressed="false"/', $html)
    );

    // Exactly one card is pressed — a selector with two pressed cards is a
    // different defect that would otherwise pass both assertions above.
    bhp_test_assert(
        $failures,
        "markup {$pb}: exactly one card is aria-pressed=\"true\"",
        substr_count($html, 'aria-pressed="true"') === 1
    );

    // Order follows the selection, so the pressed card is not sitting second.
    $pos_pb = strpos($html, 'data-bhp-format="paperback"');
    $pos_hc = strpos($html, 'data-bhp-format="hardcover"');
    bhp_test_assert(
        $failures,
        "markup {$pb}: the PAPERBACK card is rendered FIRST",
        false !== $pos_pb && false !== $pos_hc && $pos_pb < $pos_hc
    );

    // The CTA — the element the customer clicks — is server-rendered, names
    // paperback, and points at the paperback add-to-cart URL.
    bhp_test_assert(
        $failures,
        "markup {$pb}: the CTA reads ADD PAPERBACK TO CART",
        strpos($html, '>ADD PAPERBACK TO CART</a>') !== false
    );
    bhp_test_assert(
        $failures,
        "markup {$pb}: the CTA does NOT read ADD HARDCOVER TO CART",
        strpos($html, 'ADD HARDCOVER TO CART</a>') === false
    );
    bhp_test_assert(
        $failures,
        "markup {$pb}: the CTA href is no longer the dead '#' placeholder",
        (bool) preg_match('/data-bhp-format-cta\s+href="(?!#")[^"]+"/', $html)
    );

    // And it points at THIS title's paperback, not some other product.
    $data = bhp_book_purchase_data($key);
    $expected_add = $data['paperback']['add_url'];
    bhp_test_assert(
        $failures,
        "markup {$pb}: the CTA href is this title's paperback add-to-cart URL",
        $expected_add && strpos($html, 'href="' . esc_url($expected_add) . '"') !== false
    );

    // The add-to-cart target must name the paperback product/variation, never
    // the hardcover product ID.
    $hc_id = (int) $book['hc_product'];
    bhp_test_assert(
        $failures,
        "markup {$pb}: the CTA target does not carry hardcover product {$hc_id}",
        !preg_match('/data-bhp-format-cta\s+href="[^"]*add-to-cart=' . $hc_id . '\b/', $html)
    );

    // Spec line and shipping note follow the same selection.
    bhp_test_assert(
        $failures,
        "markup {$pb}: the spec line is the PAPERBACK spec",
        strpos($html, 'data-bhp-format-spec>Paperback.') !== false
    );
    bhp_test_assert(
        $failures,
        "markup {$pb}: the shipping note is server-rendered, not empty",
        !preg_match('/data-bhp-format-note><\/p>/', $html)
    );

    // The JSON payload still carries all four formats with their own labels —
    // hoisting it must not have dropped or renamed anything.
    if (preg_match('/data-bhp-format-data>\s*(\{.*?\})\s*<\/script>/s', $html, $m)) {
        $payload = json_decode($m[1], true);
        bhp_test_assert($failures, "payload {$pb}: decodes as JSON", is_array($payload));
        foreach (['paperback', 'hardcover', 'kindle', 'collection'] as $fmt) {
            bhp_test_assert($failures, "payload {$pb}: carries '{$fmt}'", isset($payload[$fmt]));
        }
        bhp_test_assert($failures, "payload {$pb}: hardcover label intact",
            isset($payload['hardcover']['ctaLabel']) && $payload['hardcover']['ctaLabel'] === 'ADD HARDCOVER TO CART');
        bhp_test_assert($failures, "payload {$pb}: paperback label intact",
            isset($payload['paperback']['ctaLabel']) && $payload['paperback']['ctaLabel'] === 'ADD PAPERBACK TO CART');
        // The server-rendered CTA and the payload the script re-applies must
        // agree — that is the whole reason the array was hoisted.
        bhp_test_assert($failures, "payload {$pb}: server CTA label === payload paperback label",
            isset($payload['paperback']['ctaLabel']) && strpos($html, '>' . $payload['paperback']['ctaLabel'] . '</a>') !== false);
        bhp_test_assert($failures, "payload {$pb}: server CTA href === payload paperback addUrl",
            isset($payload['paperback']['addUrl']) && strpos($html, 'href="' . esc_url($payload['paperback']['addUrl']) . '"') !== false);
    } else {
        bhp_test_assert($failures, "payload {$pb}: the JSON block is present", false);
    }
}

// ---------------------------------------------------------------------
// 6. ?bhp_format=hardcover on a canonical page — the legacy-301 landing.
//    The hardcover card must be pressed, first, and the CTA must name it.
// ---------------------------------------------------------------------
foreach ($registry as $key => $book) {
    $pb = (int) $book['pb_product'];

    $html = bhp_test_with_product_context($pb, ['bhp_format' => 'hardcover'], function () {
        ob_start();
        bhp_book_render_format_selector();
        return ob_get_clean();
    });

    bhp_test_assert(
        $failures,
        "markup {$pb} (?bhp_format=hardcover): initial is hardcover",
        strpos($html, 'data-bhp-format-initial="hardcover"') !== false
    );
    bhp_test_assert(
        $failures,
        "markup {$pb} (?bhp_format=hardcover): the HARDCOVER card is selected",
        (bool) preg_match('/class="bhp-format-card is-selected" data-bhp-format="hardcover" aria-pressed="true"/', $html)
    );
    bhp_test_assert(
        $failures,
        "markup {$pb} (?bhp_format=hardcover): exactly one card is pressed",
        substr_count($html, 'aria-pressed="true"') === 1
    );
    $pos_pb = strpos($html, 'data-bhp-format="paperback"');
    $pos_hc = strpos($html, 'data-bhp-format="hardcover"');
    bhp_test_assert(
        $failures,
        "markup {$pb} (?bhp_format=hardcover): the HARDCOVER card is rendered FIRST",
        false !== $pos_pb && false !== $pos_hc && $pos_hc < $pos_pb
    );
    bhp_test_assert(
        $failures,
        "markup {$pb} (?bhp_format=hardcover): the CTA reads ADD HARDCOVER TO CART",
        strpos($html, '>ADD HARDCOVER TO CART</a>') !== false
    );
}

// ---------------------------------------------------------------------
// 7. The COLLECTION card is deliberately unchanged — it is not URL-bound, so
//    it still follows the site-wide default in BOTH its price and its shipping
//    line (2D's fix for the two disagreeing). Asserted so a later pass cannot
//    quietly drag it along with the product-page change.
// ---------------------------------------------------------------------
$collection_data = function_exists('bhp_book_collection_data') ? bhp_book_collection_data() : null;
if (is_array($collection_data)) {
    bhp_test_assert($failures, 'collection card data still resolves', isset($collection_data['price_html']));
}

$html = bhp_test_with_product_context((int) $registry['mariana_trench']['pb_product'], [], function () {
    ob_start();
    bhp_book_render_format_selector();
    return ob_get_clean();
});
bhp_test_assert(
    $failures,
    'the COMPLETE COLLECTION card is still rendered and still last of the four',
    strpos($html, 'bhp-format-card--collection') !== false
    && strpos($html, 'bhp-format-card--collection') > strpos($html, 'data-bhp-format="kindle"')
);
bhp_test_assert(
    $failures,
    'the COLLECTION card is not pressed on a paperback URL',
    (bool) preg_match('/data-bhp-format="collection" aria-pressed="false"/', $html)
);

// ---------------------------------------------------------------------
// 8. CYCLE143-LD-171 (2026-08-04) - FREE SHIPPING RENDERS AS THE WORD "FREE".
//
//    Bundle plugin 1.8.23 took the three-book tier to $0.00 on Andrew's
//    Option B ruling. Before this pass the theme had one branch and printed
//    "Shipping from $0.00" / "+ $0.00 flat shipping" on five surfaces, live.
//
//    These assertions are two-directional on purpose: FREE must render at
//    zero, and the DOLLAR sentence must still render above it. A one-sided
//    test would pass just as happily against code that had hardcoded "FREE"
//    everywhere and broken every non-zero tier.
//
//    THESE FUNCTIONS COMPUTE NO SHIPPING. They are handed a number and return
//    a string, so nothing here touches a WooCommerce record.
// ---------------------------------------------------------------------
bhp_test_assert(
    $failures,
    'the free-shipping helpers exist',
    function_exists('bhp_book_shipping_is_free')
    && function_exists('bhp_book_ship_note_single')
    && function_exists('bhp_book_ship_note_collection')
    && function_exists('bhp_book_landing_ship_note')
);

// -- the predicate, at and around the plugin's own threshold --
bhp_test_assert($failures, 'predicate: 0.00 is free',      bhp_book_shipping_is_free(0.00) === true);
bhp_test_assert($failures, 'predicate: 0.004 is free',     bhp_book_shipping_is_free(0.004) === true);
bhp_test_assert($failures, 'predicate: 0.005 is NOT free', bhp_book_shipping_is_free(0.005) === false);
bhp_test_assert($failures, 'predicate: 1.99 is NOT free',  bhp_book_shipping_is_free(1.99) === false);
bhp_test_assert($failures, 'predicate: 4.99 is NOT free',  bhp_book_shipping_is_free(4.99) === false);
bhp_test_assert(
    $failures,
    'predicate delegates to the PLUGIN, so the threshold can never disagree',
    !function_exists('bhp_bundle_shipping_is_free')
    || (
        bhp_book_shipping_is_free(0.00) === (bool) bhp_bundle_shipping_is_free(0.00)
        && bhp_book_shipping_is_free(3.99) === (bool) bhp_bundle_shipping_is_free(3.99)
    )
);

// -- the three sentences, FREE branch --
$free_single     = bhp_book_ship_note_single(0.00);
$free_collection = bhp_book_ship_note_collection(0.00);
$free_landing    = bhp_book_landing_ship_note(0.00);

bhp_test_assert(
    $failures,
    'FREE (PDP single): exact approved wording',
    $free_single === 'Ships from my print partner. Shipping is free in the contiguous US.'
);
/*
 * ⭐ 1.19.262 (CYCLE165-LD-DIRECTION1-STEP3-PRODUCT). The three assertions in
 *    this block moved from "our print partner" to "my print partner", and the
 *    dollar branch from "Shipping from $x" to "Shipping starts at $x", under
 *    standing rule §9.1 (no "we"/"us"/"our" in customer-facing copy, adopted
 *    2026-08-18 on Andrew Signore's own words). SUPERSEDED strings, retained
 *    here so a future reader sees the movement rather than re-deriving it:
 *      'Ships from our print partner. FREE shipping in the contiguous US.'
 *      'Ships from our print partner. Shipping from $1.99 in the contiguous US.'
 *      'Ships from our print partner. Shipping from $2.99 in the contiguous US.'
 *    The COLLECTION and LANDING sentences are untouched: neither contains a
 *    first-person-plural pronoun, and re-wording approved copy that does not
 *    breach the rule would be a rewrite, not a fix.
 */
bhp_test_assert(
    $failures,
    'VOICE §9.1 (PDP single, free branch): no "our"/"we"/"us"',
    !preg_match('/(our|we|us)/i', $free_single)
);
bhp_test_assert(
    $failures,
    'FREE (PDP collection): exact approved wording',
    $free_collection === 'All three adventures, bundled at a lower price than buying separately. FREE shipping in the contiguous US.'
);
bhp_test_assert(
    $failures,
    'FREE (landing card): exact approved wording',
    $free_landing === '+ FREE shipping · ages 6–9 · printed & shipped in the USA'
);
foreach (['PDP single' => $free_single, 'PDP collection' => $free_collection, 'landing card' => $free_landing] as $where => $text) {
    bhp_test_assert($failures, "FREE ({$where}): no dollar figure survives", strpos($text, '$') === false);
    bhp_test_assert($failures, "FREE ({$where}): no em-dash", strpos($text, '—') === false);
}

// -- the three sentences, DOLLAR branch, which must still work --
bhp_test_assert(
    $failures,
    'DOLLAR (PDP single, 1.99): exact approved wording, cost included',
    bhp_book_ship_note_single(1.99) === 'Ships from my print partner. Shipping starts at $1.99 in the contiguous US.'
);
bhp_test_assert(
    $failures,
    'DOLLAR (PDP single, 2.99): exact approved wording, cost included',
    bhp_book_ship_note_single(2.99) === 'Ships from my print partner. Shipping starts at $2.99 in the contiguous US.'
);
bhp_test_assert(
    $failures,
    'VOICE §9.1 (PDP single, dollar branch): no "our"/"we"/"us"',
    !preg_match('/(our|we|us)/i', bhp_book_ship_note_single(1.99))
);
bhp_test_assert(
    $failures,
    'DOLLAR (PDP single): no em-dash',
    strpos(bhp_book_ship_note_single(1.99), '—') === false
);
bhp_test_assert(
    $failures,
    'DOLLAR (PDP collection, 4.99): unchanged wording',
    bhp_book_ship_note_collection(4.99) === 'All three adventures, bundled at a lower price than buying separately. Shipping from $4.99 in the contiguous US.'
);
bhp_test_assert(
    $failures,
    'DOLLAR (landing card, 3.99): unchanged wording',
    bhp_book_landing_ship_note(3.99) === '+ $3.99 flat shipping · ages 6–9 · printed & shipped in the USA'
);

// -- the four landing templates all route through the helper, and none of
//    them still carries its own copy of the dollar-only sprintf --
foreach ([
    'page-audience-educators.php',
    'page-audience-gift-buyers.php',
    'page-audience-organizations.php',
    'page-reluctant-reader-adventure-kit.php',
] as $tpl) {
    $src = @file_get_contents(get_template_directory() . '/' . $tpl);
    bhp_test_assert($failures, "{$tpl}: readable", is_string($src) && $src !== '');
    /*
     * ⭐ 1.19.210 (2026-08-09, CYCLE148-LD-05) — the assertion now matches the
     *    CALL, not one exact argument list.
     *
     * It used to require the literal `bhp_book_landing_ship_note($f['shipping'])`.
     * That is a stricter test than the thing it exists to protect: what must
     * be true is that the template routes through the helper rather than
     * carrying its own sprintf. When the helper gained the `$exclude_free`
     * parameter (so a card that has already printed "FREE Shipping" as its
     * own bold bullet does not repeat it inside a run-on sentence) all four
     * templates started passing a second argument, and four guards failed
     * for a change that did not weaken anything they guard.
     *
     * ⛔ THE GUARD IS NOT LOOSENED IN SUBSTANCE. It still requires the call,
     *    still requires `$f['shipping']` to be the figure passed, and the
     *    companion assertion below still forbids a hand-rolled dollar-only
     *    sprintf. What is removed is the requirement that the call have
     *    EXACTLY one argument, which was never the property under test.
     */
    bhp_test_assert(
        $failures,
        "{$tpl}: renders its ship-note through bhp_book_landing_ship_note()",
        is_string($src) && preg_match(
            '/bhp_book_landing_ship_note\(\s*\$f\[\s*\x27shipping\x27\s*\]\s*[,)]/',
            $src
        ) === 1
    );
    bhp_test_assert(
        $failures,
        "{$tpl}: no un-branched dollar-only ship-note sprintf left behind",
        is_string($src) && strpos($src, '+ $%s flat shipping') === false
    );
}

// -- and the LIVE tier, whatever the plugin currently says it is, renders a
//    sentence with no "$0.00" in it. This is the assertion that actually
//    closes CYCLE143-LD-171 against the deployed plugin. --
if (function_exists('bhp_bundle_rules')) {
    foreach (['paperback', 'hardcover'] as $fmt) {
        $rules = bhp_bundle_rules($fmt);
        $three = isset($rules[3]['shipping']) ? (float) $rules[3]['shipping'] : null;
        if (null === $three) {
            continue;
        }
        $landing    = bhp_book_landing_ship_note($three);
        $collection = bhp_book_ship_note_collection($three);
        bhp_test_assert(
            $failures,
            "LIVE {$fmt} 3-book tier ({$three}): landing card renders no zero-dollar figure",
            strpos($landing, '$0.00') === false
        );
        bhp_test_assert(
            $failures,
            "LIVE {$fmt} 3-book tier ({$three}): collection card renders no zero-dollar figure",
            strpos($collection, '$0.00') === false
        );
        bhp_test_assert(
            $failures,
            "LIVE {$fmt} 3-book tier ({$three}): the two cards agree about free-ness",
            (strpos($landing, 'FREE') !== false) === (strpos($collection, 'FREE') !== false)
        );
    }
}
if (function_exists('bhp_bundle_single_shipping')) {
    foreach (['paperback', 'hardcover'] as $fmt) {
        $one = (float) bhp_bundle_single_shipping($fmt);
        bhp_test_assert(
            $failures,
            "LIVE {$fmt} single-book tier ({$one}): PDP card renders no zero-dollar figure",
            strpos(bhp_book_ship_note_single($one), '$0.00') === false
        );
    }
}

// -- the rendered format selector itself, end to end --
$html = bhp_test_with_product_context((int) $registry['mariana_trench']['pb_product'], [], function () {
    ob_start();
    bhp_book_render_format_selector();
    return ob_get_clean();
});
bhp_test_assert(
    $failures,
    'RENDERED format selector contains no zero-dollar figure anywhere',
    strpos($html, '$0.00') === false && strpos($html, '&#036;0.00') === false
);
bhp_test_assert(
    $failures,
    'RENDERED format selector still carries a shipping sentence',
    strpos($html, 'in the contiguous US.') !== false
);

// ---------------------------------------------------------------------
// 9. ⭐ 1.19.222 (2026-08-13) — THE VISIBLE PAGE AND THE STRUCTURED DATA MUST
//    AGREE. `CYCLE156-LD-01`, "Option B" from the Google product feed audit.
//
//    THE DEFECT THIS PINS, verified live 2026-08-13 on the rendered JSON-LD of
//    the Mariana canonical page BEFORE the change: with `?bhp_format=hardcover`
//    the visible page showed the HARDCOVER card pressed at $17.99 (§6 above
//    already asserted that, and it was already true), while the page's
//    structured data still led with the $11.99 PAPERBACK offer. A Google feed
//    item priced $17.99 landed on a page whose primary offer said $11.99 — a
//    $6.00 contradiction between two surfaces of the same page.
//
//    ⭐ THIS SECTION LIVES HERE, not only in tests/test-product-offer-schema.php,
//      ON PURPOSE. That suite asserts the schema is correct. This one asserts
//      the schema AGREES WITH THE MARKUP — the defect was never that either
//      surface was wrong on its own; it was that they disagreed. Only a test
//      that reads both in the same breath can catch that class again.
//
//    ⛔ ASSERTS NOTHING ABOUT GOOGLE. Whether Merchant Center accepts the
//      product is Google's verdict after a re-sync and an Andrew-submitted
//      review request, and is not knowable from here.
// ---------------------------------------------------------------------
if (!function_exists('bhp_book_add_hardcover_offer')) {
    bhp_test_assert($failures, 'bhp_book_add_hardcover_offer() is defined', false);
} else {
    foreach ($registry as $key => $book) {
        $pb      = (int) $book['pb_product'];
        $hc_prod = wc_get_product((int) $book['hc_product']);
        $pb_prod = wc_get_product((int) ($book['pb_variation'] ? $book['pb_variation'] : $book['pb_product']));
        if (!$hc_prod || !$pb_prod) {
            continue;
        }

        foreach ([
            ''          => ['label' => 'no param',              'lead' => 'paperback'],
            'hardcover' => ['label' => '?bhp_format=hardcover', 'lead' => 'hardcover'],
            'paperback' => ['label' => '?bhp_format=paperback', 'lead' => 'paperback'],
        ] as $param => $case) {
            $get = '' === $param ? [] : ['bhp_format' => $param];

            // Read BOTH surfaces inside ONE context, so they cannot be
            // compared across two different requests and appear to agree.
            $both = bhp_test_with_product_context($pb, $get, function () use ($pb) {
                ob_start();
                bhp_book_render_format_selector();
                $markup = ob_get_clean();

                // The shape Rank Math hands downstream callbacks at 999.
                $seed = ['product' => [
                    '@type'  => 'Product',
                    'offers' => [
                        '@type'         => 'Offer',
                        'price'         => wc_format_decimal(wc_get_product($pb)->get_price(), wc_get_price_decimals()),
                        'priceCurrency' => get_woocommerce_currency(),
                        'url'           => get_permalink($pb),
                    ],
                ]];

                return ['markup' => $markup, 'graph' => bhp_book_add_hardcover_offer($seed)];
            });

            $offers = $both['graph']['product']['offers'];
            $offers = isset($offers['@type']) ? [$offers] : array_values((array) $offers);
            $lead   = $offers[0] ?? [];

            $expected_lead_price = 'hardcover' === $case['lead']
                ? (float) $hc_prod->get_price()
                : (float) $pb_prod->get_price();

            bhp_test_assert(
                $failures,
                "{$key} ({$case['label']}): the markup presses the {$case['lead']} card",
                (bool) preg_match(
                    '/class="bhp-format-card is-selected" data-bhp-format="' . $case['lead'] . '" aria-pressed="true"/',
                    $both['markup']
                )
            );
            bhp_test_assert(
                $failures,
                "{$key} ({$case['label']}): the PRIMARY structured-data offer is the {$case['lead']} price",
                isset($lead['price']) && (float) $lead['price'] === $expected_lead_price
            );
            // Both editions remain purchasable and remain in the graph either way.
            bhp_test_assert(
                $failures,
                "{$key} ({$case['label']}): both editions are still offered — nothing was dropped",
                2 === count($offers)
            );
        }
    }
}

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(count($failures) . ' assertion(s) failed: ' . implode(' | ', $failures));
}
WP_CLI::success('All format-selector assertions passed.');
