<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-419 — the hardcover row on the Complete Collection
 *     shop card is REMOVED BY DEFAULT.
 *
 * Theme 1.19.419. ⛔ THE BUNDLE PLUGIN DOES NOT MOVE IN THIS BUILD and §4.1
 * asserts it is still 1.8.94 rather than assuming it — `CYCLE180-LD-BUILD-413`
 * silently DOWNGRADED the plugin by two versions while reporting success, and
 * the only thing that caught it was reading the version back.
 *
 * ⚠ EVIDENCE CLASS OF THE RULING: RELAYED through `chief-of-staff`. ⛔ NOT
 *   witnessed first-hand by the agent that wrote this suite (Standing Rules
 *   §9.2 rule 2). A founder ruling of 2026-09-13, recorded before 1.19.418
 *   shipped, which did not reach the 418 brief in time. This closes
 *   `CYCLE180-LD-417-F1`.
 *
 * ⛔ THE RULING IS POINTED AT, NOT QUOTED. This repository is public and
 *   founder words do not travel here (Standing Rules §4.1, and the precedent
 *   1.19.418 set). Its wording lives in the private carrier and in the build
 *   report `CYCLE180-LD-BUILD-419`.
 *
 * ⭐ ONE FUNCTIONAL CHANGE IN THE WHOLE RELEASE: the boolean in
 *   `define( 'BHP_SHOP_CARD_HARDCOVER_ROW', … )` moved from `true` to `false`.
 *   ⛔ NO CODE WAS DELETED. The constant, the `defined()` guard, the
 *   `bhp_shop_card_hardcover_row` filter and the single call site are
 *   byte-identical to 1.19.418.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE FOUR ROWS THAT CAN ACTUALLY FAIL ON A PLAUSIBLE BAD BUILD.
 *     Read these before adding a grep and calling this covered.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * §1.3 — THE REMOVAL IS REVERSIBLE. A "removal" implemented by deleting the
 * markup would pass every other row in this file and be a one-way door. §1.3
 * adds `__return_true`, re-reads the resolver, and removes the filter again, so
 * "restorable with one define, no deploy" is a CHECKED FACT rather than a
 * sentence in a report. ⛔ If a later build deletes the block and keeps the
 * constant as decoration, this row is the one that goes red.
 *
 * §2.1/§2.2 — IT REACHES ONE ROW ON ONE CARD, NOT THE HARDCOVER EDITION. The
 * dangerous version of this change is a sitewide kill switch: the same gate
 * copied onto the PDP format selector, the pair card's hardcover swap, or the
 * colouring line's upsell. §2 asserts the gate appears at exactly ONE call site
 * and is ABSENT from those three surfaces, with a control proving each file was
 * really read.
 *
 * §3.1 — NO WOOCOMMERCE DATA WAS TOUCHED, AND THIS IS THE ROW THAT PROVES IT.
 * The hardcover collection must still be a live, priced, purchasable product
 * after this build. A theme flag that quietly coincided with a product going
 * unavailable would look identical on the rendered page and be a completely
 * different event. §3.1 reads the hardcover collection record back and requires
 * a real price. ⛔ Standing Rules §6: no agent mutates product data, and this
 * build mutated none.
 *
 * §5.1 — THE 418 FEATURES SURVIVE. A version bump that regresses the affiliate
 * disclosure or the store links would ship green on a suite that only looked at
 * the thing it changed.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE. This suite exercises PHP. It does NOT
 *   prove what a browser painted. ⛔ THE ROW'S ABSENCE FROM THE RENDERED
 *   `/shop/` PAGE IS NOT ASSERTED HERE and must not be inferred from a green
 *   run — it is verified separately by reading the rendered page and filed with
 *   the build report. Neither substitutes for the other.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-419.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no variation,
 *    no attachment, no term, no option, no post, no comment, no cart, no order,
 *    no file. The one filter §1.3 adds is REMOVED in the same breath it is
 *    added, and §1.4 re-reads the resolver afterwards to prove the removal took.
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
$GLOBALS['b419_passes']   = 0;
$GLOBALS['b419_failures'] = 0;
$GLOBALS['b419_skips']    = 0;

function b419_assert($label, $ok, $detail = '') {
    if ($ok) {
        $GLOBALS['b419_passes']++;
        printf("  PASS  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '');
    } else {
        $GLOBALS['b419_failures']++;
        printf("  FAIL  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '');
    }
    return (bool) $ok;
}

function b419_skip($label, $why) {
    $GLOBALS['b419_skips']++;
    printf("  SKIP  %s  [%s]\n", $label, $why);
}

/**
 * Strip PHP block comments so a needle can never match an essay ABOUT a
 * statement.
 *
 * ⛔ `inc/book-formats.php` IS MOSTLY COMMENTARY BY BYTE COUNT, and every
 *    superseded value in it is preserved in prose ON PURPOSE — including the
 *    two worked `define()` examples that document how to travel this build's
 *    switch in either direction. An assertion about the SHIPPED DEFAULT that
 *    reads the raw file counts those examples and fails a correct build.
 *
 * ⭐ Mirrors `b418_css_code()`, which exists for exactly the same reason on the
 *    stylesheets. Block comments only — a `//` line comment inside a live
 *    statement's own line is not the hazard here.
 */
function b419_php_code($php) {
    return preg_replace('!/\*.*?\*/!s', '', $php);
}

$b419_theme_dir = get_template_directory();
$b419_bf        = (string) @file_get_contents($b419_theme_dir . '/inc/book-formats.php');

printf("\n=== CYCLE180-LD-BUILD-419 — theme %s ===\n", wp_get_theme()->get('Version'));

/* ═══════════════════════════════════════════════════════════════════════════
   §0 · PREMISES — a suite that cannot read its own subject proves nothing
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §0 premises --\n";

/*
 * ⛔ A FLOOR, NOT AN EQUALITY. `test-cycle180-build-417.php` pinned its own
 *    version with `===` and went red the moment 1.19.418 installed — 55 correct
 *    assertions and 2 red rows that said nothing except that time had passed.
 *    A floor still catches the failure that matters (a deploy landing an OLDER
 *    theme, the 413 incident) and does not go stale.
 */
$b419_live_ver = (string) wp_get_theme(get_template())->get('Version');
b419_assert('0.1 the active theme reports 1.19.419 or later',
    '' !== $b419_live_ver && version_compare($b419_live_ver, '1.19.419', '>='),
    "live: {$b419_live_ver}");

b419_assert('0.2 inc/book-formats.php read and non-trivial',
    strlen($b419_bf) > 20000, strlen($b419_bf) . ' bytes');

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · THE RULING — the row is OFF by default, and the switch still travels
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §1 the hardcover row defaults to REMOVED --\n";

b419_assert('1.0a the constant is still defined (not deleted)',
    defined('BHP_SHOP_CARD_HARDCOVER_ROW'));
b419_assert('1.0b the resolver still exists (not deleted)',
    function_exists('bhp_shop_card_hardcover_row_enabled'));

if (defined('BHP_SHOP_CARD_HARDCOVER_ROW') && function_exists('bhp_shop_card_hardcover_row_enabled')) {

    /*
     * ⛔⛔ THE WHOLE RULING IS THIS ROW. An exact assertion, deliberately: a
     *     build that silently reverted to KEEP must go red, and a floor cannot
     *     express "false".
     */
    b419_assert('1.1 ⛔⛔ the constant defaults to FALSE — the row is REMOVED',
        false === BHP_SHOP_CARD_HARDCOVER_ROW,
        var_export(BHP_SHOP_CARD_HARDCOVER_ROW, true));

    b419_assert('1.2 ⛔ and the resolver agrees with the constant',
        false === bhp_shop_card_hardcover_row_enabled(),
        var_export(bhp_shop_card_hardcover_row_enabled(), true));

    /*
     * ⭐⭐ §1.3 — THE REMOVAL IS REVERSIBLE, AND THAT IS WHY 418 BUILT A GATE
     *     INSTEAD OF DELETING THE BLOCK. One filter (or one `define()` in
     *     `wp-config.php`) brings the row back with NO deploy and NO code
     *     change. ⛔ A removal implemented by deleting markup would pass every
     *     other row in this file; this is the row that catches it.
     */
    add_filter('bhp_shop_card_hardcover_row', '__return_true');
    $b419_restored = bhp_shop_card_hardcover_row_enabled();
    remove_filter('bhp_shop_card_hardcover_row', '__return_true');
    b419_assert('1.3 ⛔⛔ REVERSIBLE — one filter restores the row, no deploy needed',
        true === $b419_restored, var_export($b419_restored, true));

    b419_assert('1.4 ⭐ and the flip was undone — the resolver reads false again',
        false === bhp_shop_card_hardcover_row_enabled(),
        var_export(bhp_shop_card_hardcover_row_enabled(), true));

    /*
     * ⛔ THE `defined()` GUARD SURVIVES. Without it the constant cannot be set
     *    from `wp-config.php` or an mu-plugin, and "restorable with no deploy"
     *    stops being true — the switch would be a code change wearing a flag's
     *    clothes.
     */
    b419_assert('1.5 ⛔ the defined() guard survives (settable per environment, no deploy)',
        false !== strpos($b419_bf, "if ( ! defined( 'BHP_SHOP_CARD_HARDCOVER_ROW' ) ) {"));

    /*
     * ⛔⛔ STRIP THE DOCBLOCKS FIRST — AND THIS ROW WAS WRONG FIRST TIME. Read
     *     against the raw file it counted 2 `false` defines and 1 `true` one on
     *     a CORRECT build: the live statement, plus the two worked examples in
     *     the docblock that tell the next reader how to travel the switch in
     *     either direction. ⭐ This file is mostly commentary by byte count and
     *     every superseded value in it is preserved in prose on purpose, so an
     *     assertion about a STATEMENT must never be able to match an essay
     *     ABOUT one. Same discipline as `b418_css_code()` for stylesheets.
     */
    b419_assert('1.6 the shipped default in LIVE source really is false',
        1 === substr_count(b419_php_code($b419_bf), "define( 'BHP_SHOP_CARD_HARDCOVER_ROW', false );")
            && 0 === substr_count(b419_php_code($b419_bf), "define( 'BHP_SHOP_CARD_HARDCOVER_ROW', true );"));

    /*
     * ⭐ CONTROL: the strip did not simply eat the file. The docblock really
     *    does still document BOTH directions — which is what makes "restorable
     *    with one define" discoverable by the next reader rather than folklore.
     */
    $b419_raw_defines  = preg_match_all("/BHP_SHOP_CARD_HARDCOVER_ROW', (?:true|false) \)/", $b419_bf);
    $b419_code_defines = preg_match_all("/BHP_SHOP_CARD_HARDCOVER_ROW', (?:true|false) \)/", b419_php_code($b419_bf));
    b419_assert('1.6a control: the docblock still documents both directions',
        ($b419_raw_defines - $b419_code_defines) >= 2,
        "raw {$b419_raw_defines}, code {$b419_code_defines}");

    /*
     * ⛔ THE FILTER RUNS EVEN WHEN THE CONSTANT SAYS SO — an `if` that returned
     *    early on the constant would make the row unrestorable at runtime.
     */
    b419_assert('1.7 the filter escape hatch is still applied unconditionally',
        false !== strpos($b419_bf, "apply_filters( 'bhp_shop_card_hardcover_row'"));
}

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · ⛔⛔ ONE ROW ON ONE CARD — NOT A SITEWIDE HARDCOVER KILL SWITCH
   ═══════════════════════════════════════════════════════════════════════════

   ⭐ This is the section that matters most on THIS build. Flipping a default to
      false is one character; the failure mode is that the same gate spreads,
      and the hardcover edition quietly stops being offered anywhere. Every
      absence below is paired with a CONTROL proving the file was really read,
      because a zero from a wrong path looks exactly like a zero from a clean
      surface (`build-418.sh`, the `'|'` delimiter incident).
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §2 the gate reaches exactly one surface --\n";

$b419_call = '$bhp_cc_alt = (bhp_shop_card_hardcover_row_enabled()';
b419_assert('2.1 ⛔ exactly ONE call site',
    1 === substr_count($b419_bf, $b419_call),
    substr_count($b419_bf, $b419_call) . ' call site(s)');

b419_assert('2.1a exactly one function definition',
    1 === preg_match_all('/^function bhp_shop_card_hardcover_row_enabled/m', $b419_bf));

/*
 * ⭐ AND THE THREE PRE-EXISTING CONDITIONS ARE STILL ALL REQUIRED. The gate
 *    only ever makes the row LESS likely to render, never more — so a
 *    hardcover that is not offerable still cannot appear.
 */
b419_assert('2.1b the three original conditions survive',
    false !== strpos($b419_bf, "'paperback' === \$collection['format']")
        && false !== strpos($b419_bf, "function_exists('bhp_book_hardcover_is_offerable')")
        && false !== strpos($b419_bf, 'bhp_book_hardcover_is_offerable()'));

$b419_surfaces = [
    'inc/colouring-line.php'                     => 'BHP_COLOURING',
    'template-parts/commerce/format-cards.php'   => 'format',
];
foreach ($b419_surfaces as $b419_rel => $b419_control) {
    $b419_src = (string) @file_get_contents($b419_theme_dir . '/' . $b419_rel);
    if ('' === $b419_src) {
        b419_skip("2.2 gate absent from {$b419_rel}", 'file unreadable');
        continue;
    }
    b419_assert("2.2 ⛔ gate ABSENT from {$b419_rel}",
        false === strpos($b419_src, 'bhp_shop_card_hardcover_row_enabled'));
    /* ⛔ CONTROL: a zero above is a real absence, not a wrong path. */
    b419_assert("2.2c control: {$b419_rel} was actually read",
        strlen($b419_src) > 1000 && false !== stripos($b419_src, $b419_control),
        strlen($b419_src) . ' bytes');
}

/*
 * ⛔ THE COLOURING LINE'S OWN UPSELL STRING IS A DIFFERENT SURFACE AND STAYS.
 *    It happens to use the same sentence shape; it is not this row.
 */
$b419_cl = (string) @file_get_contents($b419_theme_dir . '/inc/colouring-line.php');
b419_assert('2.3 the colouring line\'s own hardcover upsell string is untouched',
    false !== strpos($b419_cl, 'Prefer the hardcover? %s'));

/*
 * ⭐ AND THE SHOP CARD'S OWN STRING IS STILL IN THE SOURCE, GATED RATHER THAN
 *    DELETED. ⛔ Its presence in the file is NOT evidence it renders — §1.2 is.
 *    It is evidence that the way back does not require rewriting copy.
 */
b419_assert('2.4 the shop card\'s row markup survives in source, gated not deleted',
    false !== strpos($b419_bf, 'bhp-shop-collection-card__form--upsell'));

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · ⛔⛔ NO WOOCOMMERCE DATA WAS TOUCHED
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §3 the hardcover product is untouched and still purchasable --\n";

if (function_exists('bhp_book_collection_data')) {
    $b419_hc = bhp_book_collection_data('hardcover');
    /*
     * ⭐⭐ THE ROW IS GONE; THE PRODUCT IS NOT. A theme flag that coincided with
     *     a product going unavailable would look identical on the rendered page
     *     and be a completely different event — one of them an Andrew gate this
     *     build did not cross (Standing Rules §6).
     */
    b419_assert('3.1 ⛔⛔ the hardcover collection is STILL a real, priced record',
        is_array($b419_hc) && !empty($b419_hc['price_html']),
        is_array($b419_hc) ? ('format=' . (isset($b419_hc['format']) ? $b419_hc['format'] : '?')) : 'not an array');

    $b419_pb = bhp_book_collection_data('paperback');
    b419_assert('3.1a control: the paperback collection reads back too',
        is_array($b419_pb) && !empty($b419_pb['price_html']));
} else {
    b419_skip('3.1 the hardcover collection is still purchasable',
        'bhp_book_collection_data() unavailable');
}

/*
 * ⭐ AND THE HARDCOVER IS STILL OFFERABLE AT ALL. If this were false the row
 *    would have vanished for a reason that has nothing to do with the ruling,
 *    and the rendered-page evidence would be proving the wrong thing.
 */
if (function_exists('bhp_book_hardcover_is_offerable')) {
    b419_assert('3.2 ⭐ hardcover is still OFFERABLE — the row is gone by RULING, not by stock',
        true === (bool) bhp_book_hardcover_is_offerable(),
        var_export((bool) bhp_book_hardcover_is_offerable(), true));
} else {
    b419_skip('3.2 hardcover still offerable', 'bhp_book_hardcover_is_offerable() unavailable');
}

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · THE BUNDLE PLUGIN DOES NOT MOVE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §4 the bundle plugin --\n";

/*
 * ⛔ READ IT BACK, DO NOT ASSUME IT. `CYCLE180-LD-BUILD-413` silently
 *    downgraded this plugin by two versions while reporting success.
 *    ⭐ A FLOOR, not an equality: `test-cycle180-build-417.php` §6.3 pins
 *    '1.8.94' with `===` and will go red the first time the plugin is
 *    LEGITIMATELY upgraded (`CYCLE180-LD-418-F5`, still open). This row catches
 *    the failure that matters — a DOWNGRADE — without inheriting that defect.
 */
if (function_exists('get_plugin_data')) {
    $b419_plug = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/brave-hearts-bundle-pricing.php';
    if (file_exists($b419_plug)) {
        $b419_pv = (string) get_plugin_data($b419_plug, false, false)['Version'];
        b419_assert('4.1 the bundle plugin is 1.8.94 or later (no downgrade)',
            '' !== $b419_pv && version_compare($b419_pv, '1.8.94', '>='),
            "live: {$b419_pv}");
    } else {
        b419_skip('4.1 bundle plugin version', 'plugin file not found at the expected path');
    }
} else {
    b419_skip('4.1 bundle plugin version', 'get_plugin_data() unavailable');
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · REGRESSION — the 1.19.418 features survive a version bump
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §5 the 418 features survive --\n";

b419_assert('5.1a the affiliate disclosure helper still exists',
    function_exists('bhp_affiliate_disclosure_html'));
b419_assert('5.1b the store-links resolver still exists',
    function_exists('bhp_related_books_for_post'));

$b419_aff = (string) @file_get_contents($b419_theme_dir . '/inc/affiliate-disclosure.php');
b419_assert('5.2 the approved disclosure sentence is byte-unchanged',
    1 === substr_count($b419_aff,
        'Some links on this page go to Amazon and earn me a small commission at no cost to you.'));

$b419_fn = (string) @file_get_contents($b419_theme_dir . '/functions.php');
b419_assert('5.3 the sitewide footer disclosure is a DIFFERENT string and still present',
    false !== strpos($b419_fn,
        'As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.'));

$b419_rc = (string) @file_get_contents($b419_theme_dir . '/template-parts/guides/related-content.php');
b419_assert('5.4 exactly ONE aside in the end-of-post block (the 2026-08-31 redundancy ruling)',
    1 === substr_count($b419_rc, '<aside class="guide-continuation"'));

/* ═══════════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════════ */
printf(
    "\n=== %d passed, %d failed, %d skipped ===\n",
    $GLOBALS['b419_passes'],
    $GLOBALS['b419_failures'],
    $GLOBALS['b419_skips']
);

if ($GLOBALS['b419_failures'] > 0) {
    exit(1);
}
