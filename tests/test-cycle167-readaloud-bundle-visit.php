<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE VISIT-FLAGGED BUNDLE SUITE — theme 1.19.295, 2026-08-26,
 * `CYCLE167-LD-READALOUD-BUNDLE-FIX`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-readaloud-bundle-visit.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHY THIS SUITE EXISTS — THE FOUNDER FOUND WHAT QA DID NOT.
 * ---------------------------------------------------------------------------
 * Carrier item 278, relayed through the Chief of Staff: *"there should be the
 * bundle of the MT book and MT coloring book? we fixed that already and its
 * gone"*. It was gone because his browser carried the Adams school-visit flag,
 * and so does every Adams parent who opened the pre-visit email. The
 * `FD-642` colouring gate refused the offer, `bhp_offer_render_module()`
 * returned '', and `bhp_read_aloud_combo()` returned [] — which deleted the
 * ENTIRE combo block from `/read-aloud/`.
 *
 * ⛔⛔ THE PREVIOUS SUITE COULD NOT HAVE CAUGHT THIS, AND THAT IS THE POINT.
 *     `test-read-aloud-landing.php` asserts the combo renders — on the
 *     UNFLAGGED session a WP-CLI run has by default. It had no flagged case at
 *     all, so a defect that only exists for flagged parents was invisible to
 *     it. ⭐ §9 below codifies the founder's gap as a PERMANENT test: on a
 *     visit-flagged session the pair must still be PRESENT on the page.
 *
 * ---------------------------------------------------------------------------
 * ⭐ HOW A FLAGGED SESSION IS SIMULATED, AND WHY IT IS THE REAL CHAIN.
 * ---------------------------------------------------------------------------
 * `WC()->session` IS a live `WC_Session_Handler` under `wp eval-file` (probed
 * first-hand on staging before this suite was written, not assumed). So the
 * flag is set exactly the way `bhp_school_visit_capture_intent()` sets it:
 *   WC()->session->set( 'bhp_school_visit', <slug> )
 *   WC()->session->set( 'bhp_school_visit_set_at', time() )
 *
 * ⛔ NOTHING IS MOCKED, STUBBED OR FILTERED AROUND. The suite drives the real
 *    `bhp_school_visit_paperback_only()` → `bhp_offer_is_offerable()` →
 *    `bhp_offer_render_module()` chain. A pass therefore means the shipped
 *    gate behaved, not that a test double did.
 *
 * ⛔ THE SLUG IS READ FROM THE LIVE REGISTRY, never typed. A hardcoded
 *    `adams-2026-08-28` becomes a false PASS the day that visit closes: the
 *    flag would resolve to nothing, `paperback_only()` would return false, and
 *    every "flagged" assertion below would be silently testing the UNFLAGGED
 *    path while still printing PASS. §0 refuses to run rather than allow that.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT THIS SUITE DOES NOT PROVE — stated so no one over-reads a PASS.
 * ---------------------------------------------------------------------------
 * It is PHP and source level, not a browser. It cannot observe layout, colour,
 * a tap target, console cleanliness or a real cart round trip. Those are
 * browser-QA claims and are recorded separately, with viewport evidence, in
 * the handoff. ⛔ It also does not prove the ANCHOR SURVIVES WooCommerce's
 * notice escaping — that is `wc_kses_notice()`'s behaviour at render time and
 * is verified in a browser, not here.
 *
 * ⛔ IT WRITES NOTHING PERSISTENT. It sets and clears a WooCommerce SESSION
 *    value and nothing else: no option, no product, no price, no order, no
 *    stock, no setting. §10 restores the session to its entry state and
 *    asserts it did.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔ COUNTERS IN $GLOBALS — the repaired defect from the sibling suite, and it
 *    must not be re-introduced. `wp eval-file` runs this file inside a FUNCTION
 *    scope, so a file-top `$pass = 0;` is a LOCAL and `global $pass;` inside the
 *    helper binds a DIFFERENT, unset global: the helper increments one variable
 *    and the summary reads another, making the summary structurally incapable
 *    of reporting a failure. A suite that cannot fail is a fabricated
 *    verification. Keep the counter and the summary on the SAME storage.
 */
$GLOBALS['bhp_c167_pass'] = 0;
$GLOBALS['bhp_c167_fail'] = 0;

function bhp_c167_ok($label, $cond, $detail = '') {
    if ($cond) {
        $GLOBALS['bhp_c167_pass']++;
        echo "PASS  {$label}\n";
    } else {
        $GLOBALS['bhp_c167_fail']++;
        echo "FAIL  {$label}" . ($detail ? "  -- {$detail}" : '') . "\n";
    }
}

function bhp_c167_head($title) {
    echo "\n=== {$title} ===\n";
}

/**
 * Markup → comparable plain text.
 *
 * ⛔ TAGS STRIPPED **AND** ENTITIES DECODED. Stripping alone is not enough:
 *    `wp_kses_post()` rewrites `&#36;` as `&#036;`, so two renders of the same
 *    price compare unequal while being identical to a reader. Comparing
 *    decoded text asserts the FIGURE rather than the escaping layer.
 */
function bhp_c167_plain($html) {
    return html_entity_decode(wp_strip_all_tags((string) $html), ENT_QUOTES, 'UTF-8');
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§0 PRECONDITIONS');

$bhp_c167_required = [
    'bhp_read_aloud_combo',
    'bhp_read_aloud_combo_key',
    'bhp_read_aloud_ship_home_url',
    'bhp_read_aloud_offer_blocked_by_visit',
    'bhp_colouring_ship_home_url',
    'bhp_colouring_visit_refusal_message',
    'bhp_colouring_shop_add_to_cart_link',
    'bhp_offer_is_purchasable',
    'bhp_offer_is_offerable',
    'bhp_offer_render_module',
    'bhp_school_visit_paperback_only',
    'bhp_school_visit_paperback_only_message',
    'bhp_colouring_product_ids',
];
foreach ($bhp_c167_required as $bhp_c167_fn) {
    bhp_c167_ok("§0 {$bhp_c167_fn}() is loaded", function_exists($bhp_c167_fn));
}

if (!function_exists('WC') || !WC()->session) {
    echo "FAIL  §0 WC()->session unavailable — the flagged half of this suite cannot run.\n";
    echo "\nPASS: {$GLOBALS['bhp_c167_pass']}   FAIL: " . ($GLOBALS['bhp_c167_fail'] + 1) . "\nSUITE FAIL\n";
    return;
}

/*
 * ⛔ THE SLUG COMES FROM THE LIVE REGISTRY. An OPEN visit is required: a closed
 *    one sets a flag that resolves to nothing, which would silently turn every
 *    "flagged" assertion below into a second copy of the unflagged path.
 */
/*
 * ⚠️ THE SLUG IS THE ARRAY **KEY**, NOT A `slug` FIELD. Recorded because the
 *    first run of this suite got it wrong and the guard below is what caught
 *    it: a row reads
 *      "adams-2026-08-28" => [ school, date, cutoff, time ]
 *    with no `slug` member at all. Reading `$row['slug']` yields '' for every
 *    row, no slug resolves, and the suite REFUSES rather than silently running
 *    the flagged half against an unflagged session. ⭐ That refusal is the
 *    guard working, and it is why the guard is worth its lines.
 */
$bhp_c167_slug = '';
$bhp_c167_visits = (array) get_option('bhp_school_visits', []);
foreach ($bhp_c167_visits as $bhp_c167_row_key => $bhp_c167_row) {
    $bhp_c167_try = (string) $bhp_c167_row_key;
    if ('' === $bhp_c167_try) {
        continue;
    }
    WC()->session->set('bhp_school_visit', $bhp_c167_try);
    WC()->session->set('bhp_school_visit_set_at', time());
    if (bhp_school_visit_paperback_only()) {
        $bhp_c167_slug = $bhp_c167_try;
        break;
    }
}
WC()->session->set('bhp_school_visit', null);
WC()->session->set('bhp_school_visit_set_at', null);

bhp_c167_ok(
    '§0 an OPEN visit slug resolves, so the flagged half tests the real gate',
    '' !== $bhp_c167_slug,
    'no registry slug made bhp_school_visit_paperback_only() true'
);

if ('' === $bhp_c167_slug) {
    echo "\n⛔ REFUSING TO CONTINUE. Every visit in the registry is closed, so a\n";
    echo "   flagged session cannot be simulated and the flagged assertions would\n";
    echo "   pass while testing the UNFLAGGED path. That is a false green.\n";
    echo "\nPASS: {$GLOBALS['bhp_c167_pass']}   FAIL: {$GLOBALS['bhp_c167_fail']}\nSUITE FAIL\n";
    return;
}
echo "      (flagged half will use registry slug: {$bhp_c167_slug})\n";

$bhp_c167_key = bhp_read_aloud_combo_key();

function bhp_c167_flag_on($slug) {
    WC()->session->set('bhp_school_visit', $slug);
    WC()->session->set('bhp_school_visit_set_at', time());
}
function bhp_c167_flag_off() {
    WC()->session->set('bhp_school_visit', null);
    WC()->session->set('bhp_school_visit_set_at', null);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE CONTROL PATH — an ordinary shopper is byte-for-byte unaffected.
 *
 * ⛔ THIS SECTION IS THE ONE THAT MATTERS MOST COMMERCIALLY. Every other
 *    section describes a minority session. This one is everybody.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§1 UNFLAGGED — the ordinary shopper, unchanged');
bhp_c167_flag_off();

bhp_c167_ok('§1.1 paperback_only() is FALSE with no visit flag', false === bhp_school_visit_paperback_only());
bhp_c167_ok('§1.2 the pair is purchasable', true === bhp_offer_is_purchasable($bhp_c167_key));
bhp_c167_ok('§1.3 the pair is offerable', true === bhp_offer_is_offerable($bhp_c167_key));
bhp_c167_ok(
    '§1.4 blocked_by_visit() is FALSE — nothing about link mode reaches an ordinary shopper',
    false === bhp_read_aloud_offer_blocked_by_visit($bhp_c167_key)
);

$bhp_c167_cart = bhp_read_aloud_combo();
bhp_c167_ok('§1.5 the combo is NOT empty', !empty($bhp_c167_cart));
bhp_c167_ok('§1.6 mode is "cart"', isset($bhp_c167_cart['mode']) && 'cart' === $bhp_c167_cart['mode']);
bhp_c167_ok(
    '§1.7 ⭐ THE REAL ADD-TO-CART FORM IS PRESENT',
    isset($bhp_c167_cart['html']) && false !== stripos($bhp_c167_cart['html'], '<form'),
    'cart mode must still emit the engine form'
);
/*
 * ⚠️ THE FIELD IS `bhp_bundle_nonce`. `bhp_bundle_add` is only the nonce
 *    ACTION string handed to `wp_create_nonce()` and it NEVER appears in the
 *    emitted HTML (`bundle-landing-page.php:277-283`). This assertion
 *    originally looked for the action and failed against a perfectly correct
 *    form. Recorded rather than quietly corrected, because the same mistake in
 *    §3.4 would have made a SAFETY check pass vacuously.
 */
bhp_c167_ok(
    '§1.8 ⭐ it carries the existing bhp_bundle_nonce field — no second pricing path',
    isset($bhp_c167_cart['html']) && false !== stripos($bhp_c167_cart['html'], 'bhp_bundle_nonce')
);
bhp_c167_ok(
    '§1.9 it opts into the cart side panel (data-bhp-offer-panel)',
    isset($bhp_c167_cart['html']) && false !== stripos($bhp_c167_cart['html'], 'data-bhp-offer-panel')
);
bhp_c167_ok(
    '§1.10 cart mode carries NO ship-home control',
    isset($bhp_c167_cart['url']) && '' === $bhp_c167_cart['url'],
    'the clear-flag link must not appear for someone who has no flag to clear'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · FLAGGED — LINK MODE. `CYCLE167-LD-001`, the founder's report.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§2 FLAGGED — link mode on /read-aloud/');
bhp_c167_flag_on($bhp_c167_slug);

bhp_c167_ok('§2.1 paperback_only() is TRUE', true === bhp_school_visit_paperback_only());
bhp_c167_ok('§2.2 the pair is STILL purchasable (catalogue truth)', true === bhp_offer_is_purchasable($bhp_c167_key));
bhp_c167_ok('§2.3 the pair is NOT offerable (session truth)', false === bhp_offer_is_offerable($bhp_c167_key));
bhp_c167_ok(
    '§2.4 the engine module returns "" — the FD-642 gate still fires',
    '' === (string) bhp_offer_render_module($bhp_c167_key, 'x', false, true),
    'the gate itself must be untouched by this build'
);
bhp_c167_ok(
    '§2.5 blocked_by_visit() correctly separates the two ""-causes',
    true === bhp_read_aloud_offer_blocked_by_visit($bhp_c167_key)
);

$bhp_c167_link = bhp_read_aloud_combo();
bhp_c167_ok(
    '§2.6 ⭐⭐ THE COMBO IS NOT EMPTY — the founder-reported defect is fixed',
    !empty($bhp_c167_link),
    'this is the exact assertion that returned [] on 1.19.294'
);
bhp_c167_ok('§2.7 mode is "link"', isset($bhp_c167_link['mode']) && 'link' === $bhp_c167_link['mode']);
bhp_c167_ok('§2.8 the engine TITLE is present', !empty($bhp_c167_link['title']));
bhp_c167_ok('§2.9 the engine PRICE is present', !empty($bhp_c167_link['price_html']));
bhp_c167_ok('§2.10 the price LABEL is present', !empty($bhp_c167_link['price_label']));
bhp_c167_ok('§2.11 the composite ART is present', !empty($bhp_c167_link['art']));
bhp_c167_ok('§2.12 a CTA label is present', !empty($bhp_c167_link['cta']));
bhp_c167_ok('§2.13 the trade-off NOTE is present', !empty($bhp_c167_link['note']));

/*
 * ⛔ THE PRICE IS THE ENGINE'S, PROVEN BY COMPARISON RATHER THAN BY READING
 *    THE CODE THAT PRODUCES IT. If link mode ever grew its own figure this
 *    assertion is what catches it.
 */
$bhp_c167_price = bhp_offer_price($bhp_c167_key);
bhp_c167_ok(
    '§2.14 ⭐ the rendered figure IS bhp_offer_price() formatted, not a literal',
    null !== $bhp_c167_price
        && false !== strpos(
            bhp_c167_plain($bhp_c167_link["price_html"]),
            bhp_c167_plain(wc_price($bhp_c167_price))
        )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ⛔⛔ FD-642 IS PRESERVED BY CONSTRUCTION. THE SAFETY SECTION.
 *
 * A colouring product must never be able to enter a hand-delivery cart. Link
 * mode's guarantee is STRUCTURAL: there is no control to press.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§3 FD-642 — no cart control exists in link mode');

bhp_c167_ok(
    '§3.1 ⛔ link mode emits NO engine HTML at all',
    isset($bhp_c167_link['html']) && '' === $bhp_c167_link['html']
);

/*
 * ⛔ ASSERTED ON THE SHIPPED TEMPLATE SOURCE, because the array alone cannot
 *    prove the TEMPLATE does not add a form of its own. This reads the real
 *    file and isolates the link branch.
 */
$bhp_c167_tpl = (string) @file_get_contents(get_template_directory() . '/page-read-aloud.php');
bhp_c167_ok('§3.2 page-read-aloud.php is readable', '' !== $bhp_c167_tpl);

$bhp_c167_branch = '';
$bhp_c167_a = strpos($bhp_c167_tpl, "'link' === (\$combo['mode']");
$bhp_c167_b = strpos($bhp_c167_tpl, '<?php else : ?>', (int) $bhp_c167_a);
if (false !== $bhp_c167_a && false !== $bhp_c167_b) {
    $bhp_c167_branch = substr($bhp_c167_tpl, $bhp_c167_a, $bhp_c167_b - $bhp_c167_a);
}
bhp_c167_ok('§3.3 the link branch was located in the template', '' !== $bhp_c167_branch);

/*
 * ⛔ `bhp_bundle_nonce` IS THE FIELD THAT ACTUALLY APPEARS IN THE MARKUP.
 *    Searching for `bhp_bundle_add` here would have been a VACUOUS pass: that
 *    string is the nonce action and is never emitted, so the assertion would
 *    have held no matter what this branch contained. A safety check that
 *    cannot fail is not a safety check.
 */
foreach ([
    '<form'                 => 'a form element',
    'bhp_bundle_nonce'      => 'the bundle add nonce field',
    'data-bhp-cart-add'     => 'the Store API add hook',
    'data-bhp-offer-panel'  => 'the side-panel add opt-in',
    'add-to-cart'           => 'a classic add-to-cart argument',
] as $bhp_c167_needle => $bhp_c167_what) {
    bhp_c167_ok(
        "§3.4 ⛔ the link branch contains NO {$bhp_c167_what}",
        '' !== $bhp_c167_branch && false === stripos($bhp_c167_branch, $bhp_c167_needle),
        "found '{$bhp_c167_needle}' — link mode must not be able to add to a cart"
    );
}

/*
 * ⛔⛔ AND THE GATE ITSELF IS UNTOUCHED, PROVEN AT THE SEAM RATHER THAN
 *     ASSUMED FROM "we did not edit the plugin". If this build had taken the
 *     unsafe three-line shortcut (filtering `bhp_school_visit_paperback_only`
 *     false on the page), THIS is the assertion that would fail.
 */
bhp_c167_ok(
    '§3.5 ⛔⛔ the colouring book is STILL a refused item on a flagged session',
    function_exists('bhp_school_visit_is_refused_item')
        && true === bhp_school_visit_is_refused_item((int) (bhp_colouring_product_ids()['mariana'] ?? 0), 0)
);
bhp_c167_ok(
    '§3.6 ⛔⛔ paperback_only() was NOT filtered false anywhere by this build',
    true === bhp_school_visit_paperback_only(),
    'the unsafe shortcut the diagnosis forbade would show up exactly here'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE SHIP-HOME URL — the route, and it must actually clear the flag.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§4 the ?bhp_visit=clear route');

$bhp_c167_param = defined('BHP_SCHOOL_VISIT_PARAM') ? BHP_SCHOOL_VISIT_PARAM : 'bhp_visit';
$bhp_c167_token = defined('BHP_SCHOOL_VISIT_CLEAR_TOKEN') ? BHP_SCHOOL_VISIT_CLEAR_TOKEN : 'clear';

bhp_c167_ok('§4.1 the plugin constants are defined (not theme literals)',
    defined('BHP_SCHOOL_VISIT_PARAM') && defined('BHP_SCHOOL_VISIT_CLEAR_TOKEN'));
bhp_c167_ok(
    '§4.2 the combo URL carries the clear token',
    !empty($bhp_c167_link['url'])
        && false !== strpos($bhp_c167_link['url'], $bhp_c167_param . '=' . $bhp_c167_token)
);
bhp_c167_ok(
    '§4.3 the combo URL is on THIS site',
    !empty($bhp_c167_link['url']) && 0 === strpos($bhp_c167_link['url'], home_url())
);
bhp_c167_ok(
    '§4.4 ⛔ the URL carries NO visit slug, school, date or grade',
    !empty($bhp_c167_link['url'])
        && false === stripos($bhp_c167_link['url'], $bhp_c167_slug),
    'the page is school-agnostic and the link must not leak the visit'
);

$bhp_c167_shop_url = bhp_colouring_ship_home_url(0);
bhp_c167_ok('§4.5 the shop ship-home URL resolves', '' !== $bhp_c167_shop_url);
bhp_c167_ok(
    '§4.6 it carries the clear token',
    false !== strpos((string) $bhp_c167_shop_url, $bhp_c167_param . '=' . $bhp_c167_token)
);

$bhp_c167_cid = (int) (bhp_colouring_product_ids()['mariana'] ?? 0);
$bhp_c167_prod_url = bhp_colouring_ship_home_url($bhp_c167_cid);
bhp_c167_ok(
    '§4.7 a product ship-home URL resolves to that product with the token',
    $bhp_c167_cid > 0
        && false !== strpos((string) $bhp_c167_prod_url, $bhp_c167_param . '=' . $bhp_c167_token)
        && 0 === strpos((string) $bhp_c167_prod_url, (string) get_permalink($bhp_c167_cid))
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · `CYCLE167-LD-002` — the /shop/ standalone coloring card.
 *
 * ⭐ `bhp_shop_card_context` is filterable precisely so a CLI suite can reach
 *    the archive branch `is_shop()` can never make true.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§5 CYCLE167-LD-002 — the /shop/ coloring card');

add_filter('bhp_shop_card_context', '__return_true', 99);
$bhp_c167_product = $bhp_c167_cid > 0 ? wc_get_product($bhp_c167_cid) : null;
bhp_c167_ok('§5.0 the coloring product resolves', (bool) $bhp_c167_product);

if ($bhp_c167_product) {
    /* FLAGGED — the control must not look live. */
    bhp_c167_flag_on($bhp_c167_slug);
    $bhp_c167_card_f = bhp_colouring_shop_add_to_cart_link('<a>CORE</a>', $bhp_c167_product);

    bhp_c167_ok(
        '§5.1 ⭐⭐ FLAGGED: the card carries NO data-bhp-cart-add',
        false === stripos($bhp_c167_card_f, 'data-bhp-cart-add'),
        'a control the server refuses must not be wired to the Store API'
    );
    bhp_c167_ok(
        '§5.2 ⛔ FLAGGED: no data-product-id either',
        false === stripos($bhp_c167_card_f, 'data-product-id')
    );
    bhp_c167_ok(
        '§5.3 FLAGGED: it is the ship-home control',
        false !== stripos($bhp_c167_card_f, 'bhp-shop-atc--shiphome')
            && false !== stripos($bhp_c167_card_f, 'data-bhp-shiphome')
    );
    bhp_c167_ok(
        '§5.4 FLAGGED: its href carries the clear token',
        false !== strpos($bhp_c167_card_f, $bhp_c167_param . '=' . $bhp_c167_token)
    );
    bhp_c167_ok(
        '§5.5 ⛔ FLAGGED: the label is NOT the uniform ADD TO CART label',
        false === stripos(wp_strip_all_tags($bhp_c167_card_f), (string) bhp_shop_card_atc_label()),
        'borrowing the add label would recreate the looks-live defect'
    );

    /* UNFLAGGED — byte-for-byte the shipped control. */
    bhp_c167_flag_off();
    $bhp_c167_card_u = bhp_colouring_shop_add_to_cart_link('<a>CORE</a>', $bhp_c167_product);

    bhp_c167_ok(
        '§5.6 ⭐ UNFLAGGED: the real ADD TO CART is back',
        false !== stripos($bhp_c167_card_u, 'data-bhp-cart-add')
            && false !== stripos($bhp_c167_card_u, 'data-product-id="' . $bhp_c167_cid . '"')
    );
    bhp_c167_ok(
        '§5.7 UNFLAGGED: no ship-home control anywhere',
        false === stripos($bhp_c167_card_u, 'shiphome')
    );
    bhp_c167_ok(
        '§5.8 UNFLAGGED: it carries the uniform grid label',
        false !== stripos(wp_strip_all_tags($bhp_c167_card_u), (string) bhp_shop_card_atc_label())
    );
}
remove_filter('bhp_shop_card_context', '__return_true', 99);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5b · `CYCLE167-LD-001` ON `/shop/` — the bundle CARD, not just the page.
 *
 * The founder's "its gone" is true of `/shop/` as well (diagnosis S6): the
 * grid's `continue` deleted the whole bundle tile on a flagged session.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§5b CYCLE167-LD-001 — the /shop/ bundle card');

bhp_c167_ok('§5b.0 the shop ship-home module exists', function_exists('bhp_offer_shop_shiphome_module'));

bhp_c167_flag_off();
bhp_c167_ok(
    '§5b.1 ⛔ CONTROL PATH: it renders NOTHING for an ordinary shopper',
    '' === bhp_offer_shop_shiphome_module($bhp_c167_key),
    'the real cart card must be the only thing an unflagged shopper ever sees'
);

bhp_c167_flag_on($bhp_c167_slug);
$bhp_c167_shopmod = bhp_offer_shop_shiphome_module($bhp_c167_key);

bhp_c167_ok('§5b.2 ⭐ FLAGGED: the bundle card renders instead of vanishing', '' !== $bhp_c167_shopmod);
/*
 * ⚠️ ENTITIES ARE DECODED ON BOTH SIDES BEFORE COMPARING, and the reason is
 *    recorded because the first run failed here on a CORRECT card:
 *    `wp_kses_post()` normalises `&#36;` to the zero-padded `&#036;`, so the
 *    module and a bare `wc_price()` render the SAME price in two different
 *    entity forms. Comparing raw strings tested the escaping layer, not the
 *    figure. Decoding both makes the assertion about the NUMBER, which is what
 *    it was always meant to be about.
 */
bhp_c167_ok(
    '§5b.3 it shows the engine price',
    '' !== $bhp_c167_shopmod
        && false !== strpos(
            bhp_c167_plain($bhp_c167_shopmod),
            bhp_c167_plain(wc_price(bhp_offer_price($bhp_c167_key)))
        )
);
bhp_c167_ok(
    '§5b.4 it carries the ?bhp_visit=clear route',
    false !== strpos($bhp_c167_shopmod, $bhp_c167_param . '=' . $bhp_c167_token)
);
bhp_c167_ok(
    '§5b.5 ⭐ it carries the honest trade-off note',
    false !== stripos($bhp_c167_shopmod, 'bhp-offer__shiphome-note')
        && false !== stripos($bhp_c167_shopmod, 'visit pickup')
);
foreach ([
    '<form'                => 'a form element',
    'bhp_bundle_nonce'     => 'the bundle add nonce field',
    'data-bhp-cart-add'    => 'the Store API add hook',
    'data-bhp-offer-panel' => 'the side-panel add opt-in',
    'add-to-cart'          => 'a classic add-to-cart argument',
] as $bhp_c167_n => $bhp_c167_w) {
    bhp_c167_ok(
        "§5b.6 ⛔⛔ FD-642: the shop ship-home card contains NO {$bhp_c167_w}",
        false === stripos($bhp_c167_shopmod, $bhp_c167_n)
    );
}
/*
 * ⚠️ ONLY THE AUTHORED STRINGS GO TO THE COPY RAILS, NOT THE WHOLE MODULE.
 *    The module legitimately contains the engine's rendered figure ($22.99
 *    today), so feeding it to §7.4's no-price-literal rail would fail a
 *    CORRECT card. ⭐ The rail's reason is "no figure TYPED INTO COPY", and a
 *    `wc_price(bhp_offer_price())` render is the opposite of that: it is the
 *    live number, re-read every request. §5b.3 above already proves the figure
 *    IS the engine's, which is the assertion that actually protects the rail.
 */
$bhp_c167_copy_shopmod = '';
if (preg_match('~<p class="bhp-offer__shiphome-note">(.*?)</p>~s', $bhp_c167_shopmod, $bhp_c167_nm)) {
    $bhp_c167_copy_shopmod = wp_strip_all_tags($bhp_c167_nm[1]);
}
if (preg_match('~bhp-shop-atc--shiphome"[^>]*>(.*?)</a>~s', $bhp_c167_shopmod, $bhp_c167_lm)) {
    $bhp_c167_copy_shopmod .= ' ' . wp_strip_all_tags($bhp_c167_lm[1]);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5c · THE SHIP-HOME CONTROL MUST NOT LOOK LIKE A LIVE ADD TO CART.
 *
 * ⚠️ THIS SECTION EXISTS BECAUSE THE FIRST STAGING READ CAUGHT THE OPPOSITE.
 *    The control rendered as a SOLID FOREST FILL, pixel-identical to the live
 *    ADD TO CART beside it, because the grid's shared rule
 *    `.woocommerce ul.products li.product .button` sets
 *    `background: … !important; color: white !important` — and `!important`
 *    beats any specificity. The outline treatment was being written and then
 *    silently discarded by the cascade.
 *
 * ⛔ A SOURCE ASSERTION, AND ITS LIMIT IS STATED. PHP cannot read a computed
 *    style, so this proves the OVERRIDE IS PRESENT IN THE SHIPPED CSS, not
 *    that it won at render time. The render-time proof is a computed-style
 *    read in a browser and is recorded in the handoff with its viewport.
 *    ⭐ Together they close the gap: this catches a future edit that drops the
 *    override, which is the realistic regression.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§5c the ship-home control is visually secondary');

$bhp_c167_css = (string) @file_get_contents(get_template_directory() . '/style.css');
bhp_c167_ok('§5c.1 style.css is readable', '' !== $bhp_c167_css);
bhp_c167_ok(
    '§5c.2 ⭐ the shiphome outline override is present',
    false !== strpos($bhp_c167_css, '.bhp-shop-atc--shiphome')
);
/*
 * ⛔⛔ THE STRUCTURAL ASSERTION, AND IT REPLACED A WEAKER ONE.
 *     The first version of this build gave the control the `button` class and
 *     fought `.woocommerce ul.products li.product .button`'s two
 *     `!important` fills with an `!important` of its own. ⭐ That is exactly
 *     the kind of guarantee that decays: one later, more specific `!important`
 *     anywhere in a 12,000-line stylesheet and the control silently looks like
 *     a live ADD TO CART again, with no test failing.
 *
 * ⭐ THE CONTROL NOW OMITS `button` ENTIRELY, so those rules cannot match it
 *    and no `!important` is needed on either side. THIS assertion is what
 *    keeps that true: if someone re-adds the class, the colour fight silently
 *    returns and this fails by name.
 */
bhp_c167_ok(
    '§5c.3 ⛔⛔ the control does NOT carry the `button` class',
    isset($bhp_c167_card_f)
        && 0 === preg_match('~class="[^"]*\bbutton\b[^"]*"~', $bhp_c167_card_f),
    'carrying `button` re-enters an !important fight the control must not depend on'
);
bhp_c167_ok(
    '§5c.3b ⛔ neither does the bundle card\'s ship-home control',
    isset($bhp_c167_shopmod)
        && 0 === preg_match('~<a[^>]*class="[^"]*\bbutton\b[^"]*"~', $bhp_c167_shopmod)
);
bhp_c167_ok(
    '§5c.3c ⭐ and it therefore needs no !important to be visually secondary',
    (bool) preg_match(
        '~\.bhp-shop-atc--shiphome\s*\{[^}]*background:\s*transparent\s*;~s',
        $bhp_c167_css
    )
);
bhp_c167_ok(
    '§5c.4 the minified stylesheet was rebuilt from this source',
    false !== strpos(
        (string) @file_get_contents(get_template_directory() . '/style.min.css'),
        'bhp-shop-atc--shiphome'
    ),
    'style.min.css is what the browser loads; a stale build ships nothing'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · `CYCLE167-LD-003` — the refusal sentence stops naming a blocked route.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§6 CYCLE167-LD-003 — the refusal sentence');

$bhp_c167_msg = bhp_school_visit_paperback_only_message();

bhp_c167_ok(
    '§6.1 ⛔⛔ it NO LONGER promises "from the shop"',
    false === stripos($bhp_c167_msg, 'from the shop'),
    'the same flag blocks the shop, so that remedy did not work for the reader'
);
bhp_c167_ok(
    '§6.2 ⭐ it names the route that DOES work (?bhp_visit=clear)',
    false !== strpos($bhp_c167_msg, $bhp_c167_param . '=' . $bhp_c167_token)
);
bhp_c167_ok(
    '§6.3 ⭐ it still says "chapter paperbacks only" — the plugin suite asserts this',
    false !== stripos($bhp_c167_msg, 'chapter paperbacks only'),
    'rewording this would break tests/test-visit-colouring-gate.php §6'
);
bhp_c167_ok(
    '§6.4 it still tells the parent what to do next',
    false !== stripos($bhp_c167_msg, 'choose a chapter paperback')
);
bhp_c167_ok(
    '§6.5 it discloses that the route switches off visit pickup',
    false !== stripos($bhp_c167_msg, 'visit pickup')
);
bhp_c167_ok(
    '§6.6 ⛔ FAILS SAFE: the filter returns a non-empty sentence',
    '' !== trim(wp_strip_all_tags($bhp_c167_msg))
);
bhp_c167_ok(
    '§6.7 the link is a real anchor',
    false !== stripos($bhp_c167_msg, '<a href=')
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · COPY RAILS — every customer-facing string this build adds.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§7 copy rails on the new strings');

$bhp_c167_copy = [
    'combo CTA'       => (string) ($bhp_c167_link['cta'] ?? ''),
    'combo note'      => (string) ($bhp_c167_link['note'] ?? ''),
    'refusal message' => wp_strip_all_tags($bhp_c167_msg),
];
if ($bhp_c167_product) {
    add_filter('bhp_shop_card_context', '__return_true', 99);
    bhp_c167_flag_on($bhp_c167_slug);
    $bhp_c167_copy['shop card label'] = wp_strip_all_tags(
        bhp_colouring_shop_add_to_cart_link('<a>CORE</a>', $bhp_c167_product)
    );
    bhp_c167_flag_off();
    remove_filter('bhp_shop_card_context', '__return_true', 99);
}
if (isset($bhp_c167_copy_shopmod)) {
    $bhp_c167_copy['shop bundle ship-home card'] = $bhp_c167_copy_shopmod;
}

foreach ($bhp_c167_copy as $bhp_c167_where => $bhp_c167_str) {
    /* ⛔ §9.1 VOICE — no "we/us/our" standing for the company. */
    bhp_c167_ok(
        "§7.1 [{$bhp_c167_where}] no \"we\", \"us\" or \"our\"",
        0 === preg_match('/\b(we|us|our|we\'re|we\'ve|we\'ll)\b/i', $bhp_c167_str),
        $bhp_c167_str
    );
    /* ⛔ NO EM DASH — sitewide standing constraint on his copy. */
    bhp_c167_ok(
        "§7.2 [{$bhp_c167_where}] no em dash",
        false === strpos($bhp_c167_str, "\xE2\x80\x94"),
        $bhp_c167_str
    );
    /* ⛔ AMERICAN SPELLING in anything a customer reads. */
    bhp_c167_ok(
        "§7.3 [{$bhp_c167_where}] American spelling (no \"colour\")",
        0 === preg_match('/colour/i', $bhp_c167_str),
        $bhp_c167_str
    );
    /* ⛔ NO PRICE LITERAL — the figure is the engine's, never typed into copy. */
    bhp_c167_ok(
        "§7.4 [{$bhp_c167_where}] no price literal",
        0 === preg_match('/\$\s?\d/', $bhp_c167_str),
        $bhp_c167_str
    );
    /* ⛔ NO OUTCOME CLAIM / no fabricated-evidence vocabulary. */
    bhp_c167_ok(
        "§7.5 [{$bhp_c167_where}] no review, rating or outcome vocabulary",
        0 === preg_match('/\b(review|rating|stars?|bestsell|award|proven|guarantee[ds]?|loved by|parents say)\b/i', $bhp_c167_str),
        $bhp_c167_str
    );
    /* ⛔ SCHOOL-AGNOSTIC — no school, date, grade or slug, ever. */
    bhp_c167_ok(
        "§7.6 [{$bhp_c167_where}] school-agnostic (no slug, school name or grade)",
        false === stripos($bhp_c167_str, $bhp_c167_slug)
            && 0 === preg_match('/\b(elementary|adams|liberty|harris|grade\s*\d)\b/i', $bhp_c167_str),
        $bhp_c167_str
    );
    /* ⛔ READING AGE is 6-9, never 5-9 — asserted wherever an age could appear. */
    bhp_c167_ok(
        "§7.7 [{$bhp_c167_where}] no 5-9 age range",
        0 === preg_match('/\b5\s*[-–to]+\s*9\b/i', $bhp_c167_str),
        $bhp_c167_str
    );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · `!purchasable` STILL MEANS SILENCE. The other ""-cause is unchanged.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§8 an unpurchasable offer is still not advertised');

function bhp_c167_bogus_key() {
    return 'bhp_c167_offer_that_does_not_exist';
}
add_filter('bhp_read_aloud_combo_key', 'bhp_c167_bogus_key', 99);

bhp_c167_flag_off();
bhp_c167_ok(
    '§8.1 UNFLAGGED + !purchasable → [] (silence, exactly as 1.19.294)',
    [] === bhp_read_aloud_combo()
);
bhp_c167_flag_on($bhp_c167_slug);
bhp_c167_ok(
    '§8.2 ⛔ FLAGGED + !purchasable → [] — link mode does NOT rescue a dead offer',
    [] === bhp_read_aloud_combo(),
    'R1.4: a link to something that cannot be bought is still advertising it'
);
bhp_c167_ok(
    '§8.3 blocked_by_visit() is FALSE for an unpurchasable key',
    false === bhp_read_aloud_offer_blocked_by_visit(bhp_c167_bogus_key())
);

remove_filter('bhp_read_aloud_combo_key', 'bhp_c167_bogus_key', 99);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · ⭐⭐⭐ THE FOUNDER'S GAP, CODIFIED PERMANENTLY.
 *
 * Carrier item 278. This is the assertion whose ABSENCE let the defect ship,
 * written so that any future change which re-hides the pair from a flagged
 * parent fails here by name rather than being found on his phone again.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§9 ⭐ THE FOUNDER-FOUND GAP — permanent regression guard');

bhp_c167_flag_on($bhp_c167_slug);
$bhp_c167_guard = bhp_read_aloud_combo();

bhp_c167_ok(
    '§9.1 ⭐⭐⭐ ON A VISIT-FLAGGED SESSION THE PAIR IS PRESENT ON /read-aloud/',
    !empty($bhp_c167_guard),
    'carrier item 278: "there should be the bundle ... and its gone". If this '
    . 'FAILS, a flagged parent sees no bundle at all and the founder-reported '
    . 'defect has regressed.'
);
bhp_c167_ok(
    '§9.2 ⭐ and it is presented WITHOUT a purchase control (FD-642 intact)',
    !empty($bhp_c167_guard)
        && 'link' === ($bhp_c167_guard['mode'] ?? '')
        && '' === ($bhp_c167_guard['html'] ?? 'x'),
    'both halves must hold at once: visible AND not buyable in this session'
);
bhp_c167_ok(
    '§9.3 ⭐ and the parent is given a working route and told what it costs',
    !empty($bhp_c167_guard['url']) && !empty($bhp_c167_guard['note'])
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §10 · CLEANUP — leave the session exactly as it was found.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c167_head('§10 cleanup');

bhp_c167_flag_off();
bhp_c167_ok(
    '§10.1 the visit flag is cleared',
    false === bhp_school_visit_paperback_only()
);
bhp_c167_ok(
    '§10.2 the pair is offerable again — the session is genuinely back to normal',
    true === bhp_offer_is_offerable($bhp_c167_key)
);
bhp_c167_ok(
    '§10.3 ⛔ nothing persistent was written: the visit REGISTRY is untouched',
    $bhp_c167_visits === (array) get_option('bhp_school_visits', [])
);

echo "\nPASS: {$GLOBALS['bhp_c167_pass']}   FAIL: {$GLOBALS['bhp_c167_fail']}\n";
echo ($GLOBALS['bhp_c167_fail'] > 0 ? "SUITE FAIL\n" : "SUITE PASS\n");
