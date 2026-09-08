<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ONE BOOK, OR ALL THREE — the product-page comparison table.
 * Theme 1.19.389, 2026-09-06, `CYCLE179-LD-BUILD-389`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * SPECIFICATION: `WORKING-DRAFTS\marketing-growth\CYCLE179-MKT-BUNDLE-TABLE-EXIT.md`
 * §1 (`marketing-growth`, 2026-09-06). That document is a copy specification.
 * This file is the build, and it departs from the spec in exactly one place,
 * named below.
 *
 * ⭐⭐ THE ONE DEPARTURE: **NOT ONE PRICE IS TYPED.** `marketing-growth`'s §1.5 HTML carries
 *     `$11.99`, `$31.99`, `$35.97`, `$3.98`, `$1.99`, `$17.99` and `$48.99` as
 *     literals. Every one of them is read live here:
 *
 *       · single paperback and hardcover — `bhp_book_purchase_data()`, which is
 *         `wc_get_product()->get_price()` on the very products this page sells
 *       · the collection price, the bought-separately total and the saving —
 *         summed from `bhp_bundle_catalog()` live prices minus the approved
 *         `bhp_bundle_rules()` discount, exactly the arithmetic
 *         `bhp_book_collection_data()` already performs
 *       · the single-book shipping figure — `bhp_bundle_single_shipping()`
 *       · "Free" on the collection row — `bhp_book_collection_ships_free()`,
 *         which asks all three routes to a complete collection whether the
 *         figure is currently zero
 *
 *     ⛔ `evidence-verification` §5, the derived-claim trap: "save $3.98" is a
 *        NEW claim built from two sourced facts, and it is recomputed at every
 *        render rather than inherited from the draft. If Andrew moves a price
 *        tomorrow, this table moves with it in the same request. There is no
 *        stale number to catch, because there is no stored number.
 *
 * ⭐ WHERE IT RENDERS: `woocommerce_single_product_summary` priority **17** —
 *    after the format cards (15) and the Stripe express element (16), so the
 *    whole purchase block is above it. ⛔ IT WAS 14 UNTIL 1.19.391; Andrew
 *    moved it on 2026-09-07. The full reasoning, and the superseded 14, are
 *    preserved at the `add_action()` call at the foot of this file.
 *
 * ⛔ CANONICAL BOOK PRODUCT PAGES ONLY. `bhp_book_lookup_product()` must
 *    return a canonical entry, or this renders nothing. The colouring line,
 *    the activity book and any future product keep their own page untouched.
 *
 * ⛔ NO TRACKING CLAIM APPEARS ANYWHERE IN THIS FILE. Andrew: there is no
 *    tracking. `CYCLE179-MKT-BUNDLE-TABLE-EXIT.md` §3 row 3 records that the
 *    claim is still live on production elsewhere and that the fix is in the
 *    repo, in Commerce & CX's lane. **This table adds no new instance of it.**
 *
 * ⛔ NO OUTCOME CLAIM, NO REVIEW, NO RATING, NO AWARD, NO SCARCITY, NO
 *    URGENCY, NO SUBSCRIBER COUNT. VOICE §9.1: no "we", "us" or "our" — the
 *    strings are second person and product facts. No em dash anywhere.
 *
 * ⚠ THE VOCABULARY CARD ROW IS **SOURCE-VERIFIED, NOT CHECKOUT-OBSERVED**, and
 *   `marketing-growth` marked it optional for that reason (§1.2). The brief includes
 *   it. It is rendered **only while the plugin says the grant is actually
 *   live** (`bhp_bundle_vocab_cards_live()`), and that function itself returns
 *   false unless the activity-book grant is live, because the cards ride on it.
 *   ⛔ So the row cannot outlive the offer: if the grant is switched off, the
 *   row stops rendering in the same request, with no copy edit. What it still
 *   does NOT do is prove delivery at a real single-book checkout. That gap is
 *   named in the build report rather than smoothed over.
 *
 * ⭐ THE SECOND BLOCK ("The same either way") IS NOT DECORATION. `marketing-growth`'s §1.4
 *    reasoning, kept because it is the reason the shape is what it is: a table
 *    of differences alone reads as a list of things a single-book buyer is
 *    being punished for missing. Four of these rows are identical either way,
 *    and saying so is both true and the more persuasive shape.
 */

defined('ABSPATH') || exit;

/**
 * Every figure the table prints, read live. Returns null when any of it cannot
 * be read, and the caller then renders nothing at all.
 *
 * ⛔ IT NEVER FALLS BACK TO A NUMBER. A comparison table with a guessed price
 *    on a purchase page is worse than no comparison table.
 *
 * @param string $key Book registry key for the product being viewed.
 * @return array<string,mixed>|null
 */
function bhp_compare_table_data($key) {
    if (!function_exists('bhp_book_purchase_data') || !function_exists('bhp_bundle_rules') || !function_exists('bhp_bundle_catalog')) {
        return null;
    }

    $book = bhp_book_purchase_data($key);
    if (!$book) {
        return null;
    }

    $single_pb = isset($book['paperback']['price']) ? (float) $book['paperback']['price'] : 0.0;
    $single_hc = isset($book['hardcover']['price']) ? (float) $book['hardcover']['price'] : 0.0;
    if ($single_pb <= 0) {
        return null;
    }

    $collection = bhp_compare_collection_figures('paperback');
    if (!$collection) {
        return null;
    }

    $collection_hc = bhp_compare_collection_figures('hardcover');

    $ship_single = function_exists('bhp_bundle_single_shipping')
        ? (float) bhp_bundle_single_shipping('paperback')
        : null;

    $collection_ships_free = function_exists('bhp_book_collection_ships_free')
        ? (bool) bhp_book_collection_ships_free()
        : false;

    return [
        /*
         * ⭐ 1.19.391 (`CYCLE179-CX-BUILD-391`) — THE KEY TRAVELS WITH THE
         *    FIGURES. The Concept B "Look inside" cue is an in-page anchor to
         *    `#bhp-look-inside-<key>` and its thumbnails come from
         *    `bhp_book_media($key)`, so the template needs the key.
         * ⛔ IT IS PASSED, NOT RE-DERIVED. The template must never call
         *    `get_queried_object_id()` for itself: this partial is also
         *    included by `tests/test-cycle179-build-389.php` §3.13 with a
         *    hand-built `$bhp_compare` and no queried object, and a second
         *    lookup there would resolve to the wrong post or to nothing.
         */
        'key'                   => (string) $key,
        'single_paperback'      => $single_pb,
        'single_hardcover'      => $single_hc,
        'collection_price'      => $collection['price'],
        'collection_separately' => $collection['separately'],
        'collection_saving'     => $collection['saving'],
        'collection_hardcover'  => $collection_hc ? $collection_hc['price'] : 0.0,
        'collection_url'        => function_exists('bhp_book_collection_data') ? bhp_book_collection_data('paperback')['url'] : home_url('/complete-collection/'),
        'ship_single'           => $ship_single,
        'collection_ships_free' => $collection_ships_free,
        'activity_book_live'    => function_exists('bhp_bundle_addon_free_with_collection') ? (bool) bhp_bundle_addon_free_with_collection() : false,
        'vocab_cards_live'      => function_exists('bhp_bundle_vocab_cards_live') ? (bool) bhp_bundle_vocab_cards_live() : false,
    ];
}

/**
 * Collection price, bought-separately total and saving, for one format.
 *
 * ⭐ THIS IS `bhp_book_collection_data()`'s ARITHMETIC, DELIBERATELY REPEATED
 *    RATHER THAN REFACTORED. That function returns only a formatted
 *    `price_html`; this table needs the three raw figures. Rewriting the
 *    shipped function to return more would put a purchase surface at risk for
 *    a table, so the read is duplicated and the duplication is stated. Both
 *    read the same two sources: `bhp_bundle_catalog()` for live prices and
 *    `bhp_bundle_rules()` for the approved discount.
 *
 * @param string $format 'paperback' or 'hardcover'.
 * @return array{price:float,separately:float,saving:float}|null
 */
function bhp_compare_collection_figures($format) {
    if (!function_exists('bhp_bundle_rules') || !function_exists('bhp_bundle_catalog') || !function_exists('wc_get_product')) {
        return null;
    }

    $rules = bhp_bundle_rules($format);
    $final = is_array($rules) ? end($rules) : null;
    if (!$final || !isset($final['discount'])) {
        return null;
    }

    $catalog = bhp_bundle_catalog();
    $books   = isset($catalog[$format]) ? $catalog[$format] : [];
    if (count($books) < 3) {
        return null;
    }

    $separately = 0.0;
    foreach ($books as $entry) {
        // Price the variation where one exists (Mariana paperback), exactly as
        // the bundle plugin's own cart logic does.
        $pid = !empty($entry['variation_id']) ? (int) $entry['variation_id'] : (int) $entry['product_id'];
        $p   = $pid ? wc_get_product($pid) : null;
        if (!$p) {
            return null;
        }
        $separately += (float) $p->get_price();
    }

    if ($separately <= 0) {
        return null;
    }

    $saving = (float) $final['discount'];
    return [
        'price'      => $separately - $saving,
        'separately' => $separately,
        'saving'     => $saving,
    ];
}

/**
 * Render the table on a canonical book product page.
 */
function bhp_compare_table_render() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    if (!function_exists('bhp_book_lookup_product')) {
        return;
    }

    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found || empty($found['canonical'])) {
        return;
    }

    $bhp_compare = bhp_compare_table_data($found['key']);
    if (!$bhp_compare) {
        return;
    }

    $bhp_tpl = locate_template('template-parts/commerce/compare-table.php');
    if ('' === $bhp_tpl) {
        return;
    }
    include $bhp_tpl;
}
/*
 * ⭐⭐ 1.19.391 — THE TABLE MOVED FROM PRIORITY 14 TO 17, ON ANDREW SIGNORE'S
 *     WORD, 2026-09-07. His instruction: the format cards, the price, the
 *     add-to-cart button and the Stripe express element stay ABOVE THE FOLD
 *     and ABOVE THE TABLE; the table goes below the purchase block.
 *
 * ⛔ SUPERSEDED, PRESERVED AT THE LINE RATHER THAN DELETED, because the
 *    reasoning for 14 was correct for the question it was answering and a
 *    later reader who finds only "17" will not know 14 was ever considered:
 *
 *      ~~WHERE IT RENDERS: `woocommerce_single_product_summary` priority 14 —
 *        after the value prop (6), before the format cards (15).~~
 *
 * ⭐ THE HOOK NEIGHBOURHOOD, RE-READ THIS BUILD RATHER THAN REMEMBERED:
 *       6  bhp_woocommerce_product_value_prop
 *      15  bhp_book_render_format_selector   ← cards + price + add to cart
 *      16  bhp_express_bridge_render         ← Stripe express element
 *      17  bhp_compare_table_render          ← THIS, now
 *      32  bhp_woocommerce_product_trust_row
 *    17 is the first free slot after the express element, so the table lands
 *    directly under the purchase block with nothing inserted between them.
 *
 * ⛔ `tests/test-cycle179-build-389.php` §3.2 and §3.38 asserted the OLD
 *    order. Both are superseded in place in that file, at the assertions
 *    themselves, with the founder's instruction recorded there.
 */
add_action('woocommerce_single_product_summary', 'bhp_compare_table_render', 17);
