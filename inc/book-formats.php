<?php
/**
 * Brave Hearts — unified book purchase experience (Phase 2, 2026-07-30).
 *
 * ONE canonical customer-facing page per title, with an Amazon-style format
 * selector, built as a PRESENTATION layer only. No WooCommerce product is
 * merged, deleted, renamed or re-priced: the paperback product of each title
 * is designated canonical, the hardcover product keeps existing untouched
 * (SKU, stock, Bookvault mapping, reviews, order history all intact) and its
 * customer-facing URL simply redirects to the canonical page with hardcover
 * pre-selected.
 *
 * Every price rendered here is read live from WooCommerce at request time.
 * There is deliberately no price anywhere in this file, in the templates, or
 * in the JavaScript.
 */

defined('ABSPATH') || exit;

// ============================================================
// 2D (2026-08-03) — HARDCOVER-FIRST, ONE SOURCE OF TRUTH
// ============================================================
/*
 * Andrew, walk-4, verbatim (RELAYED through the Chief of Staff, NOT witnessed
 * by this agent): "all the funnel pages and collection pages should default to
 * the hardcovers not paperback".
 *
 * The Complete Collection landing page has defaulted to hardcover since
 * 2026-07-30 via the bundle plugin's own bhp_bundle_default_format(). Every
 * OTHER surface - the five audience/funnel landing pages, /book-bundles/ and
 * the canonical product page's format selector - still opened on paperback,
 * each with its own hardcoded literal. Six hardcoded defaults in six files is
 * exactly how the collection page and the sticky bar drifted apart before.
 *
 * These two functions are therefore the ONLY place the theme decides. They
 * delegate to the bundle plugin so the plugin stays the single owner of the
 * decision, and fall back to 'hardcover' only if the plugin is deactivated.
 *
 * WHAT THIS CHANGES: which control is pre-selected and which panel paints
 * first. WHAT IT DOES NOT CHANGE: any price, discount, shipping rule, tax,
 * product record, stock status, SKU or Bookvault mapping. Every figure on
 * every affected surface is still read at render time from WooCommerce or
 * from the bundle plugin's approved tables, so the numbers follow the default
 * rather than being restated next to it.
 */

/** The format every surface presents first when the visitor has not chosen. */
function bhp_book_default_format() {
    $default = function_exists('bhp_bundle_default_format') ? bhp_bundle_default_format() : 'hardcover';
    return in_array($default, ['paperback', 'hardcover'], true) ? $default : 'hardcover';
}

/**
 * The two physical formats, default first. Used by every toggle so the
 * presentation order and the pre-selected control can never disagree.
 *
 * @return string[]
 */
function bhp_book_format_order() {
    $default = bhp_book_default_format();
    return 'paperback' === $default ? ['paperback', 'hardcover'] : ['hardcover', 'paperback'];
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.240 (2026-08-18) — CYCLE164-LD-PAPERBACK-DEFAULT.
 *     THE FORMATS A VISITOR MAY ACTUALLY BUY, AS OPPOSED TO THE ONES THAT
 *     EXIST.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-18, verbatim (⛔ RELAYED through the Chief of Staff;
 * NOT witnessed first-hand by the agent that wrote this):
 *
 *   "yes, lets make it the paperbacks - also for the orders on the pre-signed
 *    books for the read alouds- based on my inventory I can only do paperbacks"
 *
 * ⭐ WHY THIS IS A SECOND FUNCTION RATHER THAN AN EDIT TO
 *    bhp_book_format_order(). The two questions are genuinely different and
 *    conflating them would be wrong in both directions:
 *
 *      bhp_book_format_order()      "which format LEADS" — a presentation
 *                                   decision, site-wide, both formats always
 *                                   present, ordered by the site default.
 *      bhp_book_available_formats() "which formats may be OFFERED AT ALL on
 *                                   THIS request" — an inventory fact, and on
 *                                   a school-visit session it is paperback
 *                                   alone, because Andrew cannot carry a
 *                                   hardcover to a read aloud and sign it.
 *
 *    Overloading the order function would also have broken its own contract
 *    ("exactly the two physical formats, once each"), which
 *    tests/test-book-formats.php asserts and which is doing its job.
 *
 * ⛔ THE RESTRICTION IS THE PLUGIN'S, NOT THE THEME'S, AND THAT IS DELIBERATE.
 *    `bhp_bundle_available_format_order()` lives in the bundle plugin next to
 *    the SERVER-SIDE ENFORCEMENT that refuses the add-to-cart and the
 *    checkout (`includes/school-visit-paperback-only.php`). One predicate
 *    decides both what is hidden and what is refused, so the two can never
 *    disagree — hiding a control the server would still accept, or refusing a
 *    control the page still shows, are both worse than either alone.
 *
 * ✅ FAILS OPEN. With the plugin deactivated, or on any request with no visit
 *    flag, this returns exactly what bhp_book_format_order() returns and this
 *    file behaves as it did in 1.19.239.
 *
 * @return string[] One or both physical formats, presentation order.
 */
function bhp_book_available_formats() {
    if (function_exists('bhp_bundle_available_format_order')) {
        $formats = bhp_bundle_available_format_order();
        if (is_array($formats) && !empty($formats)) {
            return array_values($formats);
        }
    }
    return bhp_book_format_order();
}

/**
 * True when the hardcover format may be offered on this request at all.
 *
 * @return bool
 */
function bhp_book_hardcover_is_offerable() {
    if (function_exists('bhp_bundle_hardcover_is_offerable')) {
        return (bool) bhp_bundle_hardcover_is_offerable();
    }
    return true; // FAIL OPEN: plugin absent -> nothing is restricted.
}

/**
 * The short §9.1 note a surface prints in place of a hidden hardcover control.
 *
 * Empty string for every ordinary shopper, so a template may echo it
 * unconditionally and print nothing.
 *
 * @return string
 */
function bhp_book_paperback_only_note() {
    if (function_exists('bhp_school_visit_paperback_only_note')) {
        return (string) bhp_school_visit_paperback_only_note();
    }
    return '';
}

// ============================================================
// CYCLE143-LD-171 (2026-08-04) — SHIPPING NOTES THAT SURVIVE A $0.00 TIER
// ============================================================
/*
 * Bundle plugin 1.8.23 took the three-book tier to $0.00 on Andrew's Option B
 * ruling ("Option B approved, CPA table adjusted", 2026-08-04). Every theme
 * surface that renders a shipping figure had exactly one branch — the dollar
 * one — so five customer-facing strings started printing "Shipping from $0.00"
 * and "+ $0.00 flat shipping" live on staging. That is not wrong so much as
 * illiterate: it spends the word "free" on a number nobody reads as free.
 *
 * These four functions are the ONLY place the theme decides how a shipping
 * figure becomes a sentence. Five surfaces call them (three in the PDP format
 * cards, four landing-page price cards — the same landing string, four times),
 * which is why they live here rather than being branched five times in five
 * templates and drifting four ways, the way the six hardcoded format defaults
 * did before 2D.
 *
 * ⛔ THE THRESHOLD IS THE PLUGIN'S, NOT OURS. bhp_bundle_shipping_is_free()
 *    is exported by the plugin next to its own label renderer precisely so the
 *    two can never disagree about where "free" starts. The function_exists
 *    guard exists so the theme degrades to a correct dollar sentence against an
 *    older plugin rather than fatalling; the mirrored `< 0.005` is that
 *    fallback and nothing else, and in practice it is unreachable on the four
 *    landing pages, whose whole price card is already gated behind
 *    bhp_bundle_rules() being callable.
 *
 * ⚠️ WHY bhp_bundle_shipping_display() IS NOT USED FOR THESE SENTENCES, since
 *    the plugin exports it and the question will be asked: it renders a
 *    standalone phrase ("FREE shipping" / "$3.99 flat shipping"). Two of the
 *    three sentences here are not built that way — "Shipping from $1.99 in the
 *    contiguous US." would become "Shipping from FREE shipping in the
 *    contiguous US." Splicing a phrase into a sentence would also break the
 *    string for translation and would let a plugin-side copy change silently
 *    rewrite theme copy. The PREDICATE is shared; the wording is the theme's.
 *
 * ⛔ NOT CHANGED HERE: any price, discount, tier, tax, product record, stock
 *    status or WooCommerce setting. These functions read a number they are
 *    handed and return a string. They compute no shipping.
 */

/**
 * True when a shipping figure should be spoken of as free.
 *
 * @param float|int|string $amount Shipping figure, in dollars.
 * @return bool
 */
function bhp_book_shipping_is_free($amount) {
    if (function_exists('bhp_bundle_shipping_is_free')) {
        return (bool) bhp_bundle_shipping_is_free($amount);
    }
    return (float) $amount < 0.005; // Fallback only — plugin deactivated.
}

/**
 * The PDP format card's per-book shipping sentence.
 *
 * @param float|int|string $amount Shipping figure, in dollars.
 * @return string Translated, unescaped.
 */
function bhp_book_ship_note_single($amount) {
    if (bhp_book_shipping_is_free($amount)) {
        return __('Ships from our print partner. FREE shipping in the contiguous US.', 'brave-hearts');
    }

    /* translators: %s is a dollar amount, e.g. 1.99 */
    return sprintf(
        __('Ships from our print partner. Shipping from $%s in the contiguous US.', 'brave-hearts'),
        number_format((float) $amount, 2)
    );
}

/**
 * The PDP format card's Complete Collection shipping sentence.
 *
 * @param float|int|string $amount Shipping figure, in dollars.
 * @return string Translated, unescaped.
 */
function bhp_book_ship_note_collection($amount) {
    if (bhp_book_shipping_is_free($amount)) {
        return __('All three adventures, bundled at a lower price than buying separately. FREE shipping in the contiguous US.', 'brave-hearts');
    }

    /* translators: %s is a dollar amount, e.g. 3.99 */
    return sprintf(
        __('All three adventures, bundled at a lower price than buying separately. Shipping from $%s in the contiguous US.', 'brave-hearts'),
        number_format((float) $amount, 2)
    );
}

/**
 * The audience/landing price card's shipping line. One string, four pages.
 *
 * ⚠️ "printed & shipped in the USA" is carried through verbatim from the
 *    pre-existing copy. It is an OPEN finding (`CYCLE143-LD-177`) on all four
 *    pages and is deliberately NOT resolved here — this pass fixes the
 *    shipping figure and nothing else. Removing it is a copy decision.
 *
 * @param float|int|string $amount Shipping figure, in dollars.
 * @return string Translated, unescaped.
 */
function bhp_book_landing_ship_note($amount, $exclude_free = false) {
    if (bhp_book_shipping_is_free($amount)) {
        /*
         * ⭐ 1.19.210 (2026-08-09, CYCLE148-LD-03) — `$exclude_free`.
         *
         * Andrew Signore, 2026-08-06, relayed (⛔ NOT witnessed first-hand):
         * "FREE-items emphasis on ALL funnel + collection pages: bold, each
         * free item its own bullet line, NEVER COMBINED SENTENCES."
         *
         * This function's free branch IS a combined sentence — it welds
         * "FREE shipping" to the reading age and the print origin — so a
         * caller that has already printed FREE Shipping as its own bold
         * bullet must not print it again inside a run-on line. Passing TRUE
         * returns the remainder of the sentence with the free claim taken
         * out, so the fact is stated exactly once, in the emphasised place.
         *
         * ⛔ DEFAULT FALSE, so every existing caller behaves exactly as it
         *    did and this parameter changes nothing for them.
         */
        return $exclude_free
            ? __('Ages 6–9 · printed & shipped in the USA', 'brave-hearts')
            : __('+ FREE shipping · ages 6–9 · printed & shipped in the USA', 'brave-hearts');
    }

    /* translators: %s is a dollar amount, e.g. 3.99 */
    return sprintf(
        __('+ $%s flat shipping · ages 6–9 · printed & shipped in the USA', 'brave-hearts'),
        number_format((float) $amount, 2)
    );
}

// ============================================================
// CYCLE144-LD-42 (2026-08-05) — IS THE COLLECTION ACTUALLY FREE TO SHIP?
// ============================================================
/*
 * The four helpers above turn a shipping figure the CALLER already has into a
 * sentence. This one answers a different question, and it is the question the
 * Complete Collection band needs before it may say the word "free" at all:
 * **is the complete-collection shipping rule currently $0.00, right now, on
 * this environment?**
 *
 * ⛔ THE WHOLE POINT IS THAT THE BAND NEVER HARDCODES THE PROMISE. A band that
 *    prints "ships free" unconditionally is a promise the checkout can
 *    contradict the moment the tier table moves, and the tier table has moved
 *    twice in four days (1.8.23 took the three-book tier to $0.00; the
 *    theme's own 1.19.170 exists because five surfaces were still printing
 *    "$0.00" as a dollar figure). This function is the reason the sentence can
 *    be added without adding a claim that can go stale: if the rule stops
 *    being free, the sentence stops rendering, in the same deploy, with no
 *    copy edit.
 *
 * ⛔ IT COMPUTES NOTHING AND CHANGES NOTHING. It reads the PLUGIN's own
 *    numbers — `bhp_bundle_rules()` and `bhp_bundle_shipping_amount()`, the
 *    same two the customer is actually charged from — and asks the plugin's
 *    own predicate whether they count as free. There is no theme-side
 *    threshold and no second copy of the rule.
 *
 * ⭐ IT CHECKS ALL THREE ROUTES TO A COMPLETE COLLECTION, because the band's
 *    sentence covers all three and one of them is not in the tier table:
 *      1. three distinct paperbacks -> bhp_bundle_rules('paperback')[3]
 *      2. three distinct hardcovers -> bhp_bundle_rules('hardcover')[3]
 *      3. three distinct adventures ACROSS formats -> the `is_complete_
 *         collection` branch at the top of bhp_bundle_shipping_amount(),
 *         which is checked BEFORE the mixed-format table and is the only
 *         thing stopping a mixed collection from being charged $4.99.
 *    Route 3 is probed with a synthesised evaluation array. That is a pure,
 *    read-only call into a pure function: no cart, no session, no order, no
 *    product and no option is read or written.
 *
 * ⛔ FALSE IS THE SAFE ANSWER and it is what a missing plugin returns. A band
 *    that says nothing about shipping is exactly what shipped before this
 *    change; a band that says "free" when the plugin is not loaded is a lie.
 *
 * @return bool
 */
function bhp_book_collection_ships_free() {
    if (!function_exists('bhp_bundle_rules')) {
        return false;
    }

    // Routes 1 and 2 — the per-format three-title tiers.
    foreach (['paperback', 'hardcover'] as $format) {
        $rules = bhp_bundle_rules($format);
        if (!isset($rules[3]['shipping']) || !bhp_book_shipping_is_free($rules[3]['shipping'])) {
            return false;
        }
    }

    // Route 3 — the mixed-format collection, priced by the is_complete_
    // collection branch rather than by the tier table.
    if (function_exists('bhp_bundle_shipping_amount')) {
        $mixed = bhp_bundle_shipping_amount([
            'is_complete_collection' => true,
            'is_mixed_format'        => true,
            'total_quantity'         => 3,
            'has_paperback'          => true,
            'has_hardcover'          => true,
            'paperback_tier'         => 2,
            'hardcover_tier'         => 0,
            'distinct_adventures'    => 3,
            'has_unrelated'          => false,
        ]);
        if (null === $mixed || !bhp_book_shipping_is_free($mixed)) {
            return false;
        }
    }

    return true;
}

// ============================================================
// CYCLE144-LD-222 (2026-08-05) — IS THE ACTIVITY BOOK ACTUALLY FREE?
// ============================================================
/*
 * The exact sibling of bhp_book_collection_ships_free() above, for the
 * second half of Andrew's 2026-08-05 ruling (relayed): "I want it clear that
 * you get Free Shipping and a Free Activity book with Collection purchase-
 * on all collection pages and boxes".
 *
 * ⛔ THE THEME NEVER DECIDES WHAT "FREE" MEANS, and this is the third time
 *    that rule is applied rather than the first. It asks the PLUGIN — which
 *    owns the offer, the cart-line price and the product resolution — and
 *    prints nothing if the plugin says no. `bhp_bundle_addon_free_with_
 *    collection()` is false when the offer is switched off AND when the
 *    `BHP-ACTIVITY-BOOK-01` SKU does not resolve to a real, purchasable,
 *    in-stock product on this environment. So on any environment without the
 *    product, every sentence below disappears on the next page load, with no
 *    copy edit and no deploy.
 *
 * ⛔ FALSE IS THE SAFE ANSWER, and it is what a missing or older plugin
 *    returns: a page that says nothing about the activity book is exactly
 *    what shipped before this change. A page that promises a free book the
 *    cart will charge $5.00 for is a lie on the page that takes the money.
 *
 * ⛔ IT READS NOTHING AND WRITES NOTHING. No cart, no session, no order, no
 *    option. `bhp_bundle_addon_product()` is a request-scoped static lookup
 *    by SKU.
 *
 * @return bool
 */
function bhp_book_collection_includes_free_addon() {
    if (!function_exists('bhp_bundle_addon_free_with_collection')) {
        return false;
    }
    return (bool) bhp_bundle_addon_free_with_collection();
}

/**
 * The one-sentence version, for a card that already has a shipping line.
 *
 * ⛔ NO EM DASH. No price, no "$5 value", no struck-out comparison — those
 *    would each be a claim about a number, and this sentence makes none.
 * ⛔ NO reading age, no outcome claim, no rating, no reaction, no
 *    statistic. Nothing on the never-invent list appears here.
 *
 * Returns an EMPTY STRING when the offer is not live, so every caller can
 * concatenate unconditionally and still print nothing.
 *
 * @return string Translated, unescaped. '' when the offer is not live.
 */
function bhp_book_free_addon_note() {
    if (!bhp_book_collection_includes_free_addon()) {
        return '';
    }
    return __('Includes The Adventure Activity Book FREE.', 'brave-hearts');
}

/**
 * The badge-sized version, for a price card's pill row.
 *
 * @return string Translated, unescaped. '' when the offer is not live.
 */
function bhp_book_free_addon_badge() {
    if (!bhp_book_collection_includes_free_addon()) {
        return '';
    }
    return __('FREE Activity Book', 'brave-hearts');
}

// ============================================================
// CYCLE148-LD-01 (2026-08-09) — THE FREE ITEMS, AS BULLET LINES
// ============================================================
/*
 * Andrew Signore, 2026-08-06, relayed (⛔ NOT witnessed first-hand by this
 * agent; carrier on disk `FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-
 * AUTHORIZATION.md`, Sunday-batch-2 addendum, item 3):
 *
 *   "FREE-items emphasis on ALL funnel + collection pages: bold, each free
 *    item its own bullet line, never combined sentences."
 *
 * ⛔ THE RULE IS STRUCTURAL, NOT DECORATIVE, and that is why this returns a
 *    LIST rather than a sentence. The failure it forbids is the combined
 *    sentence ("Free shipping and a free activity book with your
 *    collection") — so a helper that returns one string would make the
 *    forbidden shape the easy one. A caller that gets an array can only
 *    render bullets.
 *
 * ⛔ "FREE" IS UPPERCASE IN THE STRING, NEVER VIA `text-transform`. A CSS
 *    transform leaves the accessible name, the plain-text fallback and any
 *    copy audit reading "Free", which is exactly the emphasis the ruling
 *    asks for and would not get. The BOLD is the caller's markup; the CAPS
 *    are the string's.
 *
 * ⛔ EVERY LINE IS GATED ON ITS OWN LIVE PREDICATE, separately. Free
 *    shipping is asked of `bhp_book_collection_ships_free()`; the activity
 *    book is asked of the plugin. Neither is inferred from the other, so a
 *    surface can never promise a free item that is not actually free on
 *    this environment, and an environment missing one still prints the
 *    other.
 *
 * ⭐ CYCLE163-LD-PICKUP-NATIVE (1.19.236) - THE SCHOOL-VISIT SWAP, and it is
 *    the reason the first line now comes from `bhp_book_free_shipping_line()`
 *    rather than a literal.
 *
 *    Andrew Signore, RELAYED through the Chief of Staff and NOT witnessed by
 *    this agent, after walking the visit-parent path on staging: "for that page
 *    we need to eliminate all the 'Free Shipping' ... This will definitely
 *    confuse the parents purchasing. They will think its getting shipped."
 *
 *    A parent who arrived from a school's pre-visit link is not being shipped
 *    anything, so the word must not appear on the page they buy from. ⛔ THE
 *    CLAIM IS UNCHANGED IN SUBSTANCE - delivery still costs nothing - and it is
 *    still gated on exactly the same live predicate. Only the FRAMING moves,
 *    and only for a session carrying a live visit flag.
 *
 * ⛔ THE BULLET ORDER IS UNCHANGED AND IS ASSERTED ELSEWHERE ON THIS SOURCE:
 *    shipping, then activity book, then vocabulary cards. Two suites match a
 *    bounded window across this function body
 *    (`test-collection-band-freeship.php`, `test-wave1-capture.php`), which is
 *    why the swap's explanation lives up here in the docblock and the body
 *    carries one short line. Adding a long comment inside the body would break
 *    a passing suite on a `.{0,900}` window, which is a real thing that
 *    happened while writing this change and is recorded rather than re-derived.
 *
 * @param string $scope 'collection' for a complete-collection context (both
 *                      lines can apply) or 'any_book' for a context where
 *                      only the always-on lines apply.
 * @return string[] Zero or more bullet lines. Translated, unescaped.
 */
function bhp_book_free_bullet_lines($scope = 'collection') {
    $lines = [];

    if ('collection' === $scope && function_exists('bhp_book_collection_ships_free') && bhp_book_collection_ships_free()) {
        // 1.19.236: school-visit swap. See the docblock. Same gate, same claim.
        $lines[] = bhp_book_free_shipping_line();
    }

    if (bhp_book_collection_includes_free_addon()) {
        /*
         * The plugin owns this sentence, including the "$5.00 savings"
         * phrase Andrew asked for, and builds the figure from WooCommerce's
         * own price rather than a literal. The theme prints what it is
         * given; it does not compose a savings claim of its own.
         */
        $lines[] = function_exists('bhp_bundle_addon_free_offer_line')
            ? bhp_bundle_addon_free_offer_line()
            : bhp_book_free_addon_badge();
    }

    /*
     * CYCLE151-LD-01 (2026-08-09/10) - THE THIRD LINE. Andrew's
     * vocabulary-cards ruling, RELAYED through the Chief of Staff and ⛔ NOT
     * witnessed first-hand by this agent: "FREE Vocabulary Card Activity"
     * joins the FREE bullet lists on ALL collection and funnel pages,
     * wherever the current two FREE bullets render.
     *
     * ⛔ ORDER IS FIXED AND IS THE BRIEF'S: Shipping, then Activity Book,
     *    then Vocabulary Cards. It is achieved by APPENDING here rather than
     *    by sorting, because there is nothing to sort by that is not a copy
     *    string.
     *
     * ⛔ NO DOLLAR ANCHOR ON THIS LINE, DELIBERATELY, and it is the brief's
     *    own word. The activity book's line carries "a $5.00 savings"
     *    because WooCommerce holds a real $5.00 record behind it. The cards
     *    have no price record, so a savings figure here would be invented.
     *
     * ⛔ ITS OWN LIVE PREDICATE, like both lines above it. The plugin owns
     *    the answer and the theme prints what it is given; a theme that
     *    never asks would promise a file the environment cannot deliver.
     */
    if (function_exists('bhp_bundle_vocab_cards_live')
        && bhp_bundle_vocab_cards_live()
        && function_exists('bhp_bundle_vocab_free_offer_line')) {
        $lines[] = bhp_bundle_vocab_free_offer_line();
    }

    return array_values(array_filter($lines, static function ($line) {
        return is_string($line) && '' !== trim($line);
    }));
}

/**
 * The first FREE bullet, framed for whoever is actually reading it.
 *
 * ⭐ 1.19.236, `CYCLE163-LD-PICKUP-NATIVE`. Ordinary visitor: the locked
 *    sentence, byte-identical to every release before this one. School-visit
 *    parent (a session carrying a live `?bhp_visit=` flag): the hand-delivery
 *    sentence, because nothing about their order is being posted.
 *
 * ⛔ BOTH THE PREDICATE AND THE REPLACEMENT LIVE IN THE PLUGIN
 *    (`school-visit-pickup.php`) and are called behind `function_exists`. The
 *    dependency in this codebase runs theme -> plugin, never the reverse, and
 *    this helper has to keep working under a plugin that does not define them.
 *    With the plugin absent, or with no visit flag, the answer is the locked
 *    sentence and nothing else changes.
 *
 * ⛔ IT DECIDES FRAMING ONLY. Whether the line appears at all is still the
 *    caller's `bhp_book_collection_ships_free()` gate, untouched.
 *
 * @return string Translated, unescaped.
 */
function bhp_book_free_shipping_line() {
    if (function_exists('bhp_school_visit_use_delivery_framing')
        && function_exists('bhp_school_visit_delivery_bullet')
        && bhp_school_visit_use_delivery_framing()) {
        return bhp_school_visit_delivery_bullet();
    }

    return __('FREE Shipping on the complete collection', 'brave-hearts');
}

/**
 * The bullet list as markup, with the emphasis the ruling requires.
 *
 * Returns '' when there is nothing free to say, so every caller can echo it
 * unconditionally and still print nothing.
 *
 * @param string $scope See bhp_book_free_bullet_lines().
 * @param string $class Extra class on the <ul>.
 * @return string Escaped, ready to echo.
 */
function bhp_book_free_bullets_markup($scope = 'collection', $class = '') {
    $lines = bhp_book_free_bullet_lines($scope);
    if (empty($lines)) {
        return '';
    }

    $classes = trim('bhp-free-bullets ' . $class);
    $out     = '<ul class="' . esc_attr($classes) . '">';
    foreach ($lines as $line) {
        $out .= '<li class="bhp-free-bullets__item"><strong>' . esc_html($line) . '</strong></li>';
    }
    $out .= '</ul>';

    return $out;
}

/**
 * The verified product map. IDs/SKUs confirmed live on staging 2026-07-30.
 * `pb_variation` is only set where the paperback is a variable product —
 * Mariana is, and its single variation must be what reaches the cart.
 */
function bhp_book_registry() {
    return apply_filters('bhp_book_registry', [
        'mariana_trench' => [
            'title'        => __('Adventures of Charlotte and Henry: The Mariana Trench', 'brave-hearts'),
            'descriptor'   => __('The deepest place on Earth', 'brave-hearts'),
            'pb_product'   => 333,
            'pb_variation' => 334,
            'pb_attributes' => ['attribute_paperback' => 'Perfect Bound'],
            'hc_product'   => 14,
            'amazon_key'   => 'mariana_trench',
        ],
        'mount_everest' => [
            'title'        => __('Adventures of Charlotte and Henry: Mount Everest', 'brave-hearts'),
            'descriptor'   => __('The highest mountain on Earth', 'brave-hearts'),
            'pb_product'   => 15,
            'pb_variation' => 0,
            'pb_attributes' => [],
            'hc_product'   => 17,
            'amazon_key'   => 'mount_everest',
        ],
        'amazon_rainforest' => [
            'title'        => __('Adventures of Charlotte and Henry: The Amazon', 'brave-hearts'),
            'descriptor'   => __('The world’s greatest rainforest', 'brave-hearts'),
            'pb_product'   => 18,
            'pb_variation' => 0,
            'pb_attributes' => [],
            'hc_product'   => 20,
            'amazon_key'   => 'amazon_rainforest',
        ],
    ]);
}

/** The canonical (customer-facing) product ID for a title is its paperback. */
function bhp_book_canonical_id($key) {
    $reg = bhp_book_registry();
    return isset($reg[$key]) ? (int) $reg[$key]['pb_product'] : 0;
}

/** Reverse lookup: which title does this product belong to, and as what format? */
function bhp_book_lookup_product($product_id) {
    $product_id = (int) $product_id;
    foreach (bhp_book_registry() as $key => $book) {
        if ((int) $book['pb_product'] === $product_id) {
            return ['key' => $key, 'format' => 'paperback', 'canonical' => true];
        }
        if ((int) $book['hc_product'] === $product_id) {
            return ['key' => $key, 'format' => 'hardcover', 'canonical' => false];
        }
    }
    return null;
}

/**
 * Live purchase data for one title. Prices come from WC_Product every time;
 * an unavailable product yields an empty price rather than a guess.
 */
function bhp_book_purchase_data($key) {
    $reg = bhp_book_registry();
    if (!isset($reg[$key]) || !function_exists('wc_get_product')) {
        return null;
    }
    $book = $reg[$key];

    $pb_priced = $book['pb_variation'] ? wc_get_product($book['pb_variation']) : wc_get_product($book['pb_product']);
    $pb_parent = wc_get_product($book['pb_product']);
    $hc = wc_get_product($book['hc_product']);

    $add_pb = $book['pb_variation']
        ? add_query_arg(array_merge(
            ['add-to-cart' => $book['pb_product'], 'variation_id' => $book['pb_variation']],
            $book['pb_attributes']
        ), get_permalink($book['pb_product']))
        : add_query_arg(['add-to-cart' => $book['pb_product']], get_permalink($book['pb_product']));

    return [
        'key'          => $key,
        'title'        => $book['title'],
        'descriptor'   => $book['descriptor'],
        'canonical_url' => get_permalink($book['pb_product']),
        'paperback' => [
            'product_id'   => (int) $book['pb_product'],
            'variation_id' => (int) $book['pb_variation'],
            'sku'          => $pb_parent ? $pb_parent->get_sku() : '',
            'price_html'   => $pb_priced ? $pb_priced->get_price_html() : '',
            'price'        => $pb_priced ? $pb_priced->get_price() : '',
            'in_stock'     => $pb_priced ? $pb_priced->is_in_stock() : false,
            'add_url'      => $add_pb,
        ],
        'hardcover' => [
            'product_id'   => (int) $book['hc_product'],
            'variation_id' => 0,
            'sku'          => $hc ? $hc->get_sku() : '',
            'price_html'   => $hc ? $hc->get_price_html() : '',
            'price'        => $hc ? $hc->get_price() : '',
            'in_stock'     => $hc ? $hc->is_in_stock() : false,
            'add_url'      => add_query_arg(['add-to-cart' => $book['hc_product']], get_permalink($book['hc_product'])),
        ],
        // Kindle deliberately carries NO price. Amazon controls the live
        // price and the verified affiliate link is the only source of truth.
        'kindle' => [
            'url' => function_exists('bhp_get_amazon_affiliate_url')
                ? bhp_get_amazon_affiliate_url($book['amazon_key'])
                : '',
        ],
        'collection' => bhp_book_collection_data(),
    ];
}

/**
 * Complete Collection figures, read from the bundle plugin so the theme
 * never recreates bundle maths. Defaults to the plugin's own default format.
 *
 * ⭐ 1.19.240 (2026-08-18): the plugin's default is now PAPERBACK (Andrew's
 *    2026-08-18 ruling), so this returns the $31.99 paperback collection where
 *    it returned the $48.99 hardcover set from 2026-07-30 to 2026-08-18. The
 *    superseded docblock line said "(hardcover), matching the dedicated
 *    Complete Collection page" — note that the Collection PAGE has had its own
 *    paperback default since 2026-08-14, so the two now agree for the first
 *    time since that override was added.
 *
 * ⭐ AND ON A SCHOOL-VISIT SESSION IT IS PAPERBACK REGARDLESS. This function
 *    feeds the product-page collection cross-sell AND the shop grid's
 *    collection card, which are two of the three surfaces where a flagged
 *    parent could otherwise be offered a hardcover set that cannot be
 *    hand-delivered. Restricting it here rather than in the two templates is
 *    what stops them drifting apart.
 *
 * ⛔ NO PRICE IS COMPUTED OR STORED HERE. Every figure is still read live from
 *    WooCommerce and the plugin's approved discount table.
 */
function bhp_book_collection_data() {
    $page = get_page_by_path('complete-collection');
    $url = $page && 'publish' === $page->post_status ? get_permalink($page) : home_url('/complete-collection/');

    $format = function_exists('bhp_bundle_default_format') ? bhp_bundle_default_format() : 'hardcover';
    if ('hardcover' === $format && function_exists('bhp_book_hardcover_is_offerable') && !bhp_book_hardcover_is_offerable()) {
        $format = 'paperback';
    }
    $price_html = '';

    if (function_exists('bhp_bundle_rules')) {
        $rules = bhp_bundle_rules($format);
        $final = is_array($rules) ? end($rules) : null;
        // The bundle price is the sum of its books minus the plugin's own
        // discount — computed by the plugin's data, never re-derived here.
        if ($final && isset($final['discount']) && function_exists('bhp_bundle_catalog')) {
            $catalog = bhp_bundle_catalog();
            $books = isset($catalog[$format]) ? $catalog[$format] : [];
            $subtotal = 0.0;
            foreach ($books as $entry) {
                // Price the variation where one exists (Mariana paperback),
                // exactly as the bundle plugin's own cart logic does.
                $pid = !empty($entry['variation_id']) ? (int) $entry['variation_id'] : (int) $entry['product_id'];
                $p = $pid ? wc_get_product($pid) : null;
                if ($p) {
                    $subtotal += (float) $p->get_price();
                }
            }
            if ($subtotal > 0) {
                $price_html = wc_price($subtotal - (float) $final['discount']);
            }
        }
    }

    return [
        'url'        => $url,
        'format'     => $format,
        'price_html' => $price_html,
    ];
}

/** Lowest live price across a title's physical formats, for the Shop card. */
function bhp_book_lowest_price_html($key) {
    $data = bhp_book_purchase_data($key);
    if (!$data) {
        return '';
    }
    $candidates = array_filter([
        $data['paperback']['price'] !== '' ? (float) $data['paperback']['price'] : null,
        $data['hardcover']['price'] !== '' ? (float) $data['hardcover']['price'] : null,
    ], static function ($v) { return $v !== null; });

    return $candidates ? wc_price(min($candidates)) : '';
}

// ============================================================
// OLD URL ROUTING — hardcover product URLs fold into the canonical page
// ============================================================
/**
 * A hardcover product URL 301s to its title's canonical page with the
 * hardcover format pre-selected. `bhp_format` is a non-PII indicator and
 * every incoming query parameter (UTMs included) is carried across
 * untouched, so ad and analytics attribution survives the hop.
 *
 * The paperback (canonical) URL is NOT redirected, so the most-linked
 * existing URLs keep resolving with zero redirect hops and no loop is
 * possible: the redirect only ever fires on a hardcover product and always
 * targets a different (paperback) product.
 */
function bhp_book_redirect_legacy_format_urls() {
    if (is_admin() || !function_exists('is_product') || !is_product()) {
        return;
    }

    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found || $found['canonical']) {
        return; // Canonical paperback pages render normally.
    }

    $target = get_permalink(bhp_book_canonical_id($found['key']));
    if (!$target) {
        return;
    }

    $params = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only passthrough of public query args.
    unset($params['bhp_format']);
    $params['bhp_format'] = $found['format'];

    wp_safe_redirect(add_query_arg(array_map('sanitize_text_field', wp_unslash($params)), $target), 301);
    exit;
}
add_action('template_redirect', 'bhp_book_redirect_legacy_format_urls', 1);

/**
 * Which format should a canonical page open on?
 *
 * 2D (2026-08-03): the site-wide default, which is HARDCOVER, unless the URL
 * explicitly asks for something else. The rule is deliberately one sentence:
 *
 *   ?bhp_format=<paperback|hardcover|kindle|collection>  wins, always.
 *   Anything else - no parameter, an empty one, or a value outside the
 *   whitelist - opens on bhp_book_default_format().
 *
 * WHAT WAS CHECKED RATHER THAN ASSUMED before changing the fallback: the only
 * link anywhere in this theme or the bundle plugin that carries a bhp_format
 * parameter is the legacy HARDCOVER product 301 built by
 * bhp_book_redirect_legacy_format_urls() above, plus the same URL re-emitted
 * as the hardcover Offer `url` in the Product schema further down this file.
 * Both say hardcover, so both are unaffected. NO
 * link currently emits ?bhp_format=paperback, which is why the fallback - not
 * a link rewrite - is where this change has to be made. ?bhp_format=paperback
 * is the documented, whitelisted way for a paperback-specific entry point to
 * keep opening on paperback, and it works today.
 *
 * The parameter is still never trusted beyond the whitelist, and it is still
 * a non-PII selection indicator that carries no commerce authority: it
 * chooses which card is pre-pressed, nothing else.
 */
/*
 * ⭐ CYCLE143-CX-2 / CYCLE143-CX-24 (2026-08-04) — THE URL NOW OUTRANKS THE
 *    SITE-WIDE DEFAULT ON A PRODUCT PAGE, AND ONLY ON A PRODUCT PAGE.
 *
 * THE DEFECT, OBSERVED (not inferred) 2026-08-04 by GET on BOTH environments,
 * before this change, on the Mariana canonical URL with no query string:
 *
 *   /product/...-the-mariana-trench-paperback/
 *     -> data-bhp-format-initial="hardcover"
 *     -> the HARDCOVER card rendered first AND is-selected
 *     -> the CTA the customer clicks says "ADD HARDCOVER TO CART" ($17.99)
 *
 * So every blog CTA, every ad link and every organic result pointing at a
 * PAPERBACK URL landed the visitor on a pre-selected $17.99 hardcover. A URL
 * that says `-paperback` and a card that says HARDCOVER is not a default; it
 * is a contradiction, and the customer resolves it as a mistake.
 *
 * ⚠ WHY THIS IS NOT A REVERSAL OF THE 2D HARDCOVER-FIRST RULING. Andrew's
 *   walk-4 instruction, verbatim and relayed, is: "all the FUNNEL pages and
 *   COLLECTION pages should default to the hardcovers not paperback". Those
 *   surfaces have no format in their URL, so the site-wide default is the only
 *   answer they can have, and they still get it — bhp_book_default_format() is
 *   unchanged, bhp_book_format_order() is unchanged, and the five audience/
 *   funnel landing pages, /book-bundles/ and the Complete Collection page are
 *   BYTE-UNTOUCHED by this change. The 2D pass additionally applied the rule
 *   to the canonical PRODUCT page, which Andrew's sentence does not name; that
 *   extension is what this narrows, on exactly one surface, and it is flagged
 *   to him rather than presented as pre-approved (`CYCLE143-LD-51`).
 *
 * THE RULE, still one sentence longer than it was:
 *   ?bhp_format=<paperback|hardcover|kindle|collection>   wins, always.
 *   Otherwise, if the visitor is standing on a product that IS a known format
 *   of a known title, open on THAT format — the page they actually asked for.
 *   Otherwise (no parameter, no book context) open on bhp_book_default_format().
 *
 * Note the hardcover path is unaffected in both directions: a hardcover
 * product URL still 301s to the canonical page carrying ?bhp_format=hardcover,
 * which is an explicit parameter and therefore still wins at step one. The
 * hardcover Offer `url` in the Product schema is the same URL and likewise
 * unaffected. No link anywhere in the theme or the bundle plugin was rewritten
 * by this change; the resolution order is the only thing that moved.
 */

/**
 * The format of the product currently being viewed, if it is one of the six
 * registry editions. Empty string anywhere else — a funnel page, the shop, the
 * collection page, a blog post or an unrelated product all return '' and fall
 * through to the site-wide default.
 *
 * @return string 'paperback' | 'hardcover' | ''
 */
function bhp_book_viewed_format() {
    if (!function_exists('is_product') || !is_product()) {
        return '';
    }
    $found = bhp_book_lookup_product(get_queried_object_id());
    return $found ? $found['format'] : '';
}

function bhp_book_incoming_format() {
    $resolved = bhp_book_incoming_format_unrestricted();

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.240 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT) — THE ONE
     *     RESOLVER IS ALSO WHERE THE SCHOOL-VISIT RESTRICTION LANDS.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⭐ THIS IS A DEFECT FOUND IN BROWSER QA, NOT A PRECAUTION. With the
     *    format CARDS already restricted, a flagged walk of
     *    /product/…-the-mariana-trench-HARDCOVER/ (which 301s here carrying
     *    `?bhp_format=hardcover`) still rendered a VISIBLE, SUBMITTABLE button
     *    reading "Add the Complete Hardcover Collection", posting
     *    `complete_hardcover_smart`. OBSERVED at 1440 and 390 on staging
     *    2026-08-18, `window.innerWidth` asserted. It comes from the
     *    product-page collection upsell in inc/audit-remediation.php, which
     *    reads THIS function and not the format cards.
     *
     * ⛔ FIXING IT IN THE UPSELL WOULD HAVE BEEN THE WRONG FIX. That file's own
     *    comment says why this function exists: "bhp_book_incoming_format() is
     *    the theme's ONE resolver for this question ... Reusing it is what
     *    stops this card and the selector directly above it from ever
     *    disagreeing again." Patching the consumer would have re-created the
     *    disagreement it was written to end, and left every OTHER consumer
     *    (the Offer schema's primary offer, bhp_book_hardcover_leads) still
     *    answering "hardcover" on a session that cannot buy one.
     *
     * ⭐ THE PARAMETER STILL WINS FOR EVERY ORDINARY SHOPPER. Nothing about the
     *    resolution ORDER changes; a format the visitor may not buy is simply
     *    not a valid answer to "which format is this visitor looking at".
     *    `kindle` and `collection` are untouched: Amazon fulfils the first and
     *    the second resolves to a paperback set through
     *    `bhp_book_collection_data()`.
     *
     * ⛔ bhp_book_incoming_format_unrestricted() is kept and exported so a
     *    caller that genuinely needs the raw URL intent (a canonical/redirect
     *    decision, say) can still get it without this restriction. Nothing
     *    calls it that way today; it exists so a future need does not
     *    reintroduce a second resolver.
     */
    if ('hardcover' === $resolved
        && function_exists('bhp_book_hardcover_is_offerable')
        && !bhp_book_hardcover_is_offerable()) {
        return 'paperback';
    }

    return $resolved;
}

/**
 * The raw incoming format, BEFORE the school-visit paperback-only restriction.
 *
 * The pre-1.19.240 body of bhp_book_incoming_format(), unchanged.
 *
 * @return string 'paperback'|'hardcover'|'kindle'|'collection'
 */
function bhp_book_incoming_format_unrestricted() {
    $raw = isset($_GET['bhp_format']) ? sanitize_key(wp_unslash($_GET['bhp_format'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (in_array($raw, ['paperback', 'hardcover', 'kindle', 'collection'], true)) {
        return $raw;
    }

    $viewed = bhp_book_viewed_format();
    if ('' !== $viewed) {
        return $viewed;
    }

    return bhp_book_default_format();
}

// ============================================================
// SHOP ARCHIVE — one card per title, hardcovers hidden from the grid
// ============================================================
/**
 * Hides the three hardcover products from the shop/catalog loop so each
 * title appears exactly once. The products remain published, purchasable,
 * indexable by direct URL, and fully intact — this only affects which
 * cards the archive lists.
 */
function bhp_book_hide_hardcovers_from_shop($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if (!function_exists('is_shop') || !(is_shop() || is_product_taxonomy())) {
        return;
    }

    $hidden = [];
    foreach (bhp_book_registry() as $book) {
        $hidden[] = (int) $book['hc_product'];
    }

    $existing = (array) $query->get('post__not_in');
    $query->set('post__not_in', array_merge($existing, $hidden));
}
add_action('pre_get_posts', 'bhp_book_hide_hardcovers_from_shop');

/**
 * Related products: the OTHER titles, once each, canonical only.
 *
 * `bhp_book_hide_hardcovers_from_shop()` above only runs on `pre_get_posts`
 * for the shop/taxonomy archives. Related products are built by
 * `wc_get_related_products()`, which never passes through that query — so the
 * consolidation stopped at the shop and the hardcover records leaked back in
 * here. Measured on the Mariana page before this fix: four cards —
 * 14 (Mariana HARDCOVER, i.e. the very book being viewed), 15, 18 and
 * 20 (The Amazon HARDCOVER, a duplicate of 18).
 *
 * Two things were wrong with that, not one:
 *   1. Formats are chosen ON the title's page. Offering them as separate
 *      related cards re-introduces exactly the choice the consolidation
 *      removed.
 *   2. A hardcover URL 301s to its canonical page, so the Mariana hardcover
 *      card sent a visitor on the Mariana page straight back to the Mariana
 *      page. A related link that returns you to where you already are reads
 *      as broken.
 *
 * So: return the canonical (paperback) product of each OTHER title, in
 * registry order. On Mariana that is exactly Everest and The Amazon; the
 * visitor picks a title, then picks a format on that title's page.
 *
 * Non-book products are left entirely to WooCommerce.
 */
function bhp_book_related_products($related, $product_id, $args) {
    $found = bhp_book_lookup_product($product_id);
    if (!$found) {
        return $related;
    }

    $siblings = [];
    foreach (bhp_book_registry() as $key => $book) {
        if ($key === $found['key']) {
            continue; // Never relate a title to itself in another binding.
        }

        $sibling_id = (int) $book['pb_product'];
        $sibling    = $sibling_id ? wc_get_product($sibling_id) : null;

        /*
         * PUBLISHED / VISIBLE GUARD.
         *
         * The registry is a static map, so without this a title that had been
         * unpublished, trashed, deleted or hidden from the catalog would still
         * be advertised here — a dead card, or a link to a 404.
         *
         * Both checks are deliberate, and the status check is NOT redundant:
         * WC_Product::is_visible() returns true for a non-published product
         * when the current user can edit it, so a logged-in admin would see a
         * draft sibling that no customer could see. Checking status explicitly
         * keeps the related list identical for admins and customers, which is
         * what makes it reviewable.
         *
         * is_visible() additionally honours catalog visibility ('hidden' /
         * 'search'-only) and the store's hide-out-of-stock setting, so this
         * follows WooCommerce's own rules rather than second-guessing them.
         *
         * If nothing qualifies, an empty array is returned and WooCommerce
         * omits the related section entirely — the correct degradation.
         */
        if (!$sibling || 'publish' !== $sibling->get_status() || !$sibling->is_visible()) {
            continue;
        }

        $siblings[] = $sibling_id;
    }

    return $siblings;
}
/*
 * PRIORITY 5 IS LOAD-BEARING — do not raise it.
 *
 * The bundle plugin's GA4 observer (`bhp_bundle_track_related_view_item_list`,
 * bundle-analytics.php) hooks this SAME filter at priority 10 and emits the
 * `view_item_list` payload plus the `bhpTrackedLists` click registry from
 * whatever list it is handed. It passes the list through unchanged — it is an
 * observer, not a modifier — so it is only accurate if every modifier has
 * already run.
 *
 * Measured with this filter at priority 20: the page rendered 2 cards (15, 18)
 * while GA4 reported 4 impressions (14, 15, 18, 20) and registered click
 * targets for two hardcover permalinks that were not in the DOM. Moving to 5
 * makes the observer see the final list, so rendered cards and analytics agree.
 * No analytics event name or payload shape is changed by this — only the item
 * set, which is the point.
 */
add_filter('woocommerce_related_products', 'bhp_book_related_products', 5, 3);

// ============================================================
// CANONICAL METADATA
// ============================================================
/**
 * Both product URLs point their canonical at the unified page, so the
 * hardcover record can never compete with it in search.
 */
function bhp_book_canonical_url($canonical) {
    if (!function_exists('is_product') || !is_product()) {
        return $canonical;
    }
    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found) {
        return $canonical;
    }
    $target = get_permalink(bhp_book_canonical_id($found['key']));
    return $target ?: $canonical;
}
add_filter('rank_math/frontend/canonical', 'bhp_book_canonical_url');

/**
 * On the canonical page the customer-facing heading is the title without its
 * "(Paperback)" suffix — a display-layer change only. The product's stored
 * post_title, and therefore admin, cart line items, order history and
 * exports, are all untouched.
 */
function bhp_book_display_title($title, $id = null) {
    if (is_admin() || !$id || !function_exists('is_product')) {
        return $title;
    }

    // Applies on the canonical product page, in the shop/catalog grid, AND in
    // the related/upsell rails on a product page, so a title reads the same
    // everywhere a customer browses. Everywhere else — admin, cart line items,
    // orders, emails, exports — is untouched. (WooCommerce builds cart and
    // order line items from WC_Product::get_name(), which does not run this
    // filter, so a cart line still says "(Paperback)" / "(Hardcover)" — which
    // is exactly right: format matters once something is IN the cart.)
    $on_canonical_page = is_product() && (int) $id === (int) get_queried_object_id();
    $in_catalog = (function_exists('is_shop') && (is_shop() || is_product_taxonomy()));

    /*
     * Related and upsell cards sit ON a product page but are not the queried
     * product, so neither condition above caught them and they kept rendering
     * "… Mount Everest (Paperback)". A related card advertises a TITLE, not an
     * edition — the format is chosen after arriving on that title's page — so
     * the suffix is noise there, and worse, it implies the visitor is being
     * offered one specific binding.
     */
    $in_product_rail = is_product() && (int) $id !== (int) get_queried_object_id();

    if (!$on_canonical_page && !$in_catalog && !$in_product_rail) {
        return $title;
    }

    $found = bhp_book_lookup_product($id);
    if (!$found || !$found['canonical']) {
        return $title;
    }
    return trim(preg_replace('/\s*\((Paperback|Hardcover)\)\s*$/i', '', $title));
}
add_filter('the_title', 'bhp_book_display_title', 10, 2);

// ============================================================
// RENDERING
// ============================================================
/** Assets load only where the selector actually renders. */
function bhp_book_enqueue_assets() {
    if (!function_exists('is_product') || !(is_product() || is_shop())) {
        return;
    }
    $ver = wp_get_theme()->get('Version');
    wp_enqueue_style('bhp-book-formats', get_stylesheet_directory_uri() . '/assets/css/book-formats.css', [], $ver);
    if (is_product()) {
        wp_enqueue_script('bhp-book-formats', get_stylesheet_directory_uri() . '/assets/js/book-formats.js', [], $ver, true);
    }
}
add_action('wp_enqueue_scripts', 'bhp_book_enqueue_assets');

/**
 * "Look Inside" assets. Loaded on product pages, the shop archive (for the
 * card affordance) and the Complete Collection page — and NOT loaded at all
 * on a product page whose title has no approved media, so a title with an
 * empty registry costs the visitor nothing.
 */
function bhp_book_enqueue_media_assets() {
    if (is_admin()) {
        return;
    }

    /*
     * Product pages and the Complete Collection page. The shop-card affordance
     * remains PARKED, so the shop archive is deliberately absent here —
     * restore that branch at the same time as its hook, not before.
     *
     * ⭐ 1.19.235 — THE SENTENCE ABOVE IS PRESERVED AND IS NOW TRUE OF ONLY THE
     *    SHOP-CARD AFFORDANCE. The shop archive DOES load these assets now, but
     *    for a different feature: the Complete Collection banner's carousel
     *    (`bhp_woocommerce_shop_complete_collection_banner()`). The parked
     *    `bhp_book_shop_look_inside_link()` hook is still parked and this
     *    changes nothing about it — do not read the new branch below as
     *    permission to un-park it.
     */
    if (function_exists('is_product') && is_product()) {
        $found = bhp_book_lookup_product(get_queried_object_id());
        if (!$found || !$found['canonical'] || !bhp_book_has_look_inside($found['key'])) {
            return;
        }
    } elseif (function_exists('bhp_cx_shop_banner_gallery_media') && bhp_cx_shop_banner_gallery_media()) {
        /*
         * ⭐ 1.19.235 / `CYCLE162-LD-SHOP-CAROUSEL-V2` — the shop archive's
         *    Complete Collection banner.
         *
         * ⛔ THE SAME PREDICATE THE RENDER CALLS, exactly as the funnel branch
         *    below does and for exactly the same reason (see the head note in
         *    `inc/collection-gallery.php`, point 3). If the Collection media
         *    does not resolve on this environment the predicate returns null,
         *    the banner renders copy-only, and nothing is enqueued — the
         *    assets and the markup cannot appear without each other.
         *
         * ⚠ THIS BRANCH MUST STAY ABOVE the collection-page branch only in the
         *   sense that order does not matter here: `is_shop()` and
         *   `bhp_book_is_collection_page()` are mutually exclusive. It is
         *   placed here so the two banner branches read together.
         *
         * Intentionally empty body: the predicate in the condition IS the whole
         * check and falling through to the enqueue below is correct.
         */
    } elseif (bhp_book_is_collection_page()) {
        if (!bhp_book_media('complete_collection')['has_any']) {
            return;
        }
    } elseif (function_exists('bhp_cx_collection_gallery_config') && bhp_cx_collection_gallery_config()) {
        /*
         * The six funnel pages that carry a Collection subset (homepage,
         * parent kit, gift buyers, organizations, educators, /books/).
         *
         * This branch asks the SAME question the render call asks — it calls
         * the same function — so the assets and the markup can never appear
         * without each other. `bhp_cx_collection_gallery_config()` already
         * returns null when the media does not resolve, so the fail-closed
         * check the two branches above perform inline is performed inside it
         * here rather than repeated. See `inc/collection-gallery.php`.
         *
         * The block is intentionally empty: the predicate in the condition is
         * the whole check, and falling through to the enqueue below is the
         * correct behaviour.
         */
    } else {
        return;
    }

    $ver = wp_get_theme()->get('Version');
    wp_enqueue_style('bhp-book-media', get_stylesheet_directory_uri() . '/assets/css/book-media.css', [], $ver);
    wp_enqueue_script('bhp-book-media', get_stylesheet_directory_uri() . '/assets/js/book-media.js', [], $ver, true);
}
add_action('wp_enqueue_scripts', 'bhp_book_enqueue_media_assets');

// ============================================================
// LOOK INSIDE — the shared media experience
// ============================================================
/**
 * HERO PLACEMENT — the gallery IS the main product image.
 *
 * On a canonical book page that has approved media, WooCommerce's native
 * product gallery is removed and this gallery takes its place, so every
 * approved photo and the flip-through video live in the main image at the top
 * of the page rather than in a separate section further down.
 *
 * The swap is conditional: a title with no approved media keeps WooCommerce's
 * native gallery exactly as before. Nothing is removed that is not replaced.
 */
function bhp_book_replace_product_gallery() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found || !$found['canonical'] || !bhp_book_has_look_inside($found['key'])) {
        return; // Native gallery stays.
    }

    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    add_action('woocommerce_before_single_product_summary', 'bhp_book_render_hero_gallery', 20);
}
add_action('wp', 'bhp_book_replace_product_gallery');

/** Renders the hero gallery in place of the native product gallery. */
function bhp_book_render_hero_gallery() {
    $product_id = get_queried_object_id();
    $found = bhp_book_lookup_product($product_id);
    if (!$found || !$found['canonical']) {
        return;
    }

    $media = bhp_book_media($found['key']);
    if (empty($media['has_any'])) {
        return;
    }

    /*
     * The product's own featured image leads, so the page still opens on the
     * cover a returning visitor recognises, and so the LCP element is the same
     * image it has always been. It is read from the product rather than the
     * registry because WooCommerce, the cart, the schema and the shop card all
     * use that same attachment — duplicating its ID here would let them drift.
     */
    $thumb_id = (int) get_post_thumbnail_id($product_id);
    if ($thumb_id) {
        $thumb_alt = (string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
        array_unshift($media['items'], [
            'type' => 'image',
            'id'   => $thumb_id,
            'alt'  => $thumb_alt !== ''
                ? $thumb_alt
                : sprintf(
                    /* translators: %s: book title. */
                    __('Cover of %s', 'brave-hearts'),
                    wp_strip_all_tags(get_the_title($product_id))
                ),
        ]);
        $media['count'] = count($media['items']);
    }

    $hero    = true;
    $heading = __('Book images and flip-through', 'brave-hearts');
    $intro   = '';
    $level   = 'h2';
    $compact = false;

    $bhp_tpl = locate_template('template-parts/commerce/look-inside.php');
    if ('' === $bhp_tpl) {
        return; // Template missing: render nothing so the caller's fallback stands.
    }
    include $bhp_tpl;
}

/**
 * Shop card affordance: a plain link down to the product page's Look Inside
 * section. Rendered ONLY for a title that actually has approved media, so it
 * can never promise a section that will not be there on arrival.
 */
function bhp_book_shop_look_inside_link() {
    $found = bhp_book_lookup_product(get_the_ID());
    if (!$found || !$found['canonical'] || !bhp_book_has_look_inside($found['key'])) {
        return;
    }

    printf(
        '<a class="bhp-shop-look-inside" href="%s#%s">%s</a>',
        esc_url(get_permalink(get_the_ID())),
        esc_attr('bhp-look-inside-' . sanitize_html_class($found['key'])),
        esc_html__('Look inside', 'brave-hearts')
    );
}
/*
 * PARKED 2026-08-01 — one page at a time. The Everest product-page hero is
 * the reference implementation; this shop-card affordance is replicated only
 * once that page is signed off. The function stays because it is correct and
 * tested; only the hook is off.
 */
// add_action('woocommerce_after_shop_loop_item_title', 'bhp_book_shop_look_inside_link', 13);

/** Is this the page that renders the Complete Collection landing shortcode? */
function bhp_book_is_collection_page() {
    if (!is_page()) {
        return false;
    }
    $content = get_post_field('post_content', get_queried_object_id());
    return is_string($content) && has_shortcode($content, 'bhp_complete_series_landing');
}

/**
 * Complete Collection hero gallery.
 *
 * Renders into the bundle plugin's additive `bhp_bundle_landing_hero_media`
 * slot, taking the place of the old static three-cover block inside the hero's
 * existing left grid column. The purchase panel to its right is untouched:
 * format selector, pricing, savings, shipping and CTA all still come from
 * bhp_bundle_render_landing_pricing_card().
 *
 * Echoing nothing leaves the plugin's original covers in place, so a missing
 * or unresolvable media set degrades to exactly the previous design rather
 * than to an empty hero.
 */
function bhp_book_render_collection_hero_gallery() {
    $media = bhp_book_media('complete_collection');
    if (empty($media['has_any'])) {
        return;
    }

    $hero       = true;
    $collection = true;
    $heading    = __('The Complete Collection - all three books', 'brave-hearts');
    $intro      = '';
    $level      = 'h2';
    $compact    = false;

    $bhp_tpl = locate_template('template-parts/commerce/look-inside.php');
    if ('' === $bhp_tpl) {
        return; // Template missing: render nothing so the caller's fallback stands.
    }
    include $bhp_tpl;
}
add_action('bhp_bundle_landing_hero_media', 'bhp_book_render_collection_hero_gallery');

/** Renders the format selector on the canonical product page. */
function bhp_book_render_format_selector() {
    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found || !$found['canonical']) {
        return;
    }
    $data = bhp_book_purchase_data($found['key']);
    if (!$data) {
        return;
    }
    $initial = bhp_book_incoming_format();
    $bhp_tpl = locate_template('template-parts/commerce/format-cards.php');
    if ('' === $bhp_tpl) {
        return; // Template missing: render nothing rather than emitting a warning.
    }
    include $bhp_tpl;
}
/*
 * Priority 15 places the whole purchase interface directly after the title
 * (5), value prop (6) and rating/review signal (10), and crucially BEFORE
 * WooCommerce's short description (20), meta (40) and the tabs/description,
 * gallery thumbnails, reviews and related content further down. This is
 * what gives the page an Amazon-style purchase-first hierarchy rather than
 * burying the cards under the long copy.
 */
add_action('woocommerce_single_product_summary', 'bhp_book_render_format_selector', 15);

/**
 * The native variations form / add-to-cart button is replaced by the
 * selector above on canonical pages only. Every other product keeps
 * WooCommerce's default behaviour.
 */
function bhp_book_remove_default_add_to_cart() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found || !$found['canonical']) {
        return;
    }
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
}
add_action('wp', 'bhp_book_remove_default_add_to_cart');

// ============================================================
// SHOP CARDS
// ============================================================
/** Short adventure descriptor + "Formats from <lowest live price>". */
function bhp_book_shop_card_meta() {
    $found = bhp_book_lookup_product(get_the_ID());
    if (!$found || !$found['canonical']) {
        return;
    }
    $reg = bhp_book_registry();
    $lowest = bhp_book_lowest_price_html($found['key']);
    ?>
    <p class="bhp-shop-descriptor"><?php echo esc_html($reg[$found['key']]['descriptor']); ?></p>
    <?php if ($lowest): ?>
      <span class="bhp-shop-from-price">
        <?php
        /* translators: %s: lowest live WooCommerce price across formats. */
        printf(esc_html__('Formats from %s', 'brave-hearts'), wp_kses_post($lowest));
        ?>
      </span>
    <?php endif; ?>
    <?php
}
add_action('woocommerce_after_shop_loop_item_title', 'bhp_book_shop_card_meta', 12);

/**
 * Shop cards lead to the canonical page to choose a format, rather than
 * adding a specific edition straight from the grid.
 */
function bhp_book_shop_choose_format_link($html, $product) {
    $found = $product ? bhp_book_lookup_product($product->get_id()) : null;
    if (!$found || !$found['canonical']) {
        return $html;
    }
    return sprintf(
        '<a href="%s" class="button bhp-shop-choose-format">%s</a>',
        esc_url(get_permalink($product->get_id())),
        esc_html__('CHOOSE YOUR FORMAT', 'brave-hearts')
    );
}
add_filter('woocommerce_loop_add_to_cart_link', 'bhp_book_shop_choose_format_link', 10, 2);

/**
 * Adds the Complete Collection as a real fourth card INSIDE the product
 * grid (injected before the closing </ul>), so the Shop reads as three
 * titles plus the collection. Price and destination come from the bundle
 * plugin — nothing is recalculated here.
 */
function bhp_book_shop_collection_card($loop_end) {
    // Deliberately no in_the_loop() check: this filter runs AFTER the loop
    // has ended, where in_the_loop() is already false.
    if (is_admin() || !function_exists('is_shop') || !is_shop()) {
        return $loop_end;
    }

    $collection = bhp_book_collection_data();

    ob_start();
    ?>
    <li class="product bhp-shop-collection-item">
      <div class="bhp-shop-collection-card">
        <span class="bhp-shop-collection-card__badge"><?php esc_html_e('BEST VALUE', 'brave-hearts'); ?></span>
        <h2 class="woocommerce-loop-product__title"><?php esc_html_e('The Complete Collection', 'brave-hearts'); ?></h2>
        <p class="bhp-shop-descriptor"><?php esc_html_e('All three adventures together', 'brave-hearts'); ?></p>
        <?php if ($collection['price_html']): ?>
          <span class="bhp-shop-collection-card__price"><?php echo wp_kses_post($collection['price_html']); ?></span>
        <?php endif; ?>
        <?php /* 1.19.197: carries the shared collection-CTA style token so this
                 card's button reads as the same control as the nav-bar CTA. It
                 is a plain link by design — the Shop grid sends the shopper to
                 the Collection page to choose a format, exactly as before; only
                 the treatment changed. */ ?>
        <a href="<?php echo esc_url($collection['url']); ?>" class="button <?php echo esc_attr(defined('BHP_COLLECTION_CTA_CLASS') ? BHP_COLLECTION_CTA_CLASS : 'bhp-collection-cta__btn'); ?>">
          <?php esc_html_e('GET THE COMPLETE COLLECTION', 'brave-hearts'); ?>
        </a>
      </div>
    </li>
    <?php
    return ob_get_clean() . $loop_end;
}
add_filter('woocommerce_product_loop_end', 'bhp_book_shop_collection_card');

// ============================================================
// STRUCTURED DATA — add the hardcover offer to the canonical page
// ============================================================
/**
 * Each canonical page now presents two purchasable physical editions, but
 * Rank Math builds a single Product entity carrying only the paperback
 * offer. Left alone, the hardcover edition would be materially absent from
 * the structured data for the page a shopper actually lands on.
 *
 * What this does: appends a SECOND Offer to the SAME Product entity, with
 * price, currency, availability and SKU all read live from WooCommerce.
 * Nothing is fabricated and no second Product/ProductGroup entity is
 * emitted, so there is no duplicate or conflicting Product schema.
 *
 * What this deliberately does NOT do: emit Google's ProductGroup /
 * hasVariant / variesBy structure. Rank Math owns the Product node and has
 * no ProductGroup support, so producing one would mean either replacing
 * Rank Math's entity wholesale or emitting a competing graph — and it could
 * not be validated in this environment (staging is noindex, and the Rich
 * Results Test cannot reach it). That remains recommended follow-up, to be
 * done against production with a real validator.
 *
 * Priority 999: Rank Math assembles the Product entity progressively across
 * calls to this same filter, so a default-priority callback would see an
 * empty array. See .claude/rules/schema.md.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.222 (2026-08-13) — THE PRIMARY OFFER NOW FOLLOWS `?bhp_format=`.
 *    "OPTION B" from the 2026-08-13 Google feed audit, F5 option (iii).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE DEFECT, VERIFIED LIVE 2026-08-13 (rendered JSON-LD on staging and, per
 *    the audit, on production): a hardcover feed link 301s to
 *    `…-paperback/?bhp_format=hardcover`, and the page it lands on emitted
 *    `offers[0].price = 11.99` while the Google feed for that item said
 *    `17.99`. A $6.00 feed-vs-landing-page contradiction, on all three
 *    hardcovers. Google's product data specification requires the feed price
 *    to match the landing page.
 *
 * ⛔ EXACTLY ONE THING MOVED: the ORDER of the two offers, and only when the
 *    URL explicitly asks for hardcover. Both offers were already emitted, both
 *    already carried their own live price, and both still do. No price, no
 *    availability, no SKU, no URL and no product record is touched by this —
 *    every figure is still read live from WooCommerce on every request.
 *
 * ✅ WHY THE VISIBLE STATE DID NOT HAVE TO CHANGE: it already followed the
 *    parameter. `bhp_book_incoming_format()` has driven the pressed card, the
 *    card ORDER, the CTA label, the CTA href, the spec line and the shipping
 *    note since 1.19.166 (`CYCLE143-CX-2`). The structured data was the one
 *    surface still contradicting the visible page. This aligns it with what
 *    the customer was already being shown; it does not introduce a new
 *    behaviour for the customer to notice.
 *
 * ⭐ CACHE SAFETY — MEASURED, NOT ASSUMED. This is the constraint that would
 *    make the change unsafe if it were wrong, so it was tested rather than
 *    reasoned about (`CYCLE143-GIM-51` is the standing prohibition on
 *    per-visitor server-rendered variance in front of a full-page cache).
 *
 *      · The variance here is a function of the URL, NOT of the visitor. Two
 *        different people requesting the same URL always get the same bytes.
 *        That is categorically different from `CYCLE143-GIM-51`, which was
 *        per-visitor variance on ONE cache key.
 *      · OBSERVED ON PRODUCTION 2026-08-13 (read-only GETs, no writes), on
 *        the Mariana canonical URL, using the `x-proxy-cache` response header
 *        and the served `data-bhp-format-initial` value:
 *            ?bhp_format=hardcover  → MISS, then HIT, body reads "hardcover"
 *            (no parameter)         → HIT,  body reads "paperback"
 *            ?bhp_format=paperback  → MISS, body reads "paperback"
 *        Three URLs, three separate cache entries, each serving its own body,
 *        and the parameterless entry was NOT poisoned by the parameterised
 *        request that immediately preceded it.
 *      ➡ SiteGround's dynamic cache keys on the full request URI including the
 *        query string. A parameterised page therefore cannot poison the
 *        parameterless one, and no `DONOTCACHEPAGE` or cache exclusion is
 *        needed — which is why none was added.
 *
 * ⚠ WHAT THIS STILL DOES NOT FIX, stated so a green QA run is not over-read:
 *    `Product.name` still ends "(Paperback)" and `Product.category` still
 *    reads "Paperback Books" on the parameterised URL, because both come from
 *    the canonical WooCommerce record and changing either is a product-record
 *    decision that belongs to Andrew. The offer that Google price-checks now
 *    matches the feed; the entity's own name does not.
 */
function bhp_book_add_hardcover_offer($data) {
    if (!function_exists('is_product') || !is_product()) {
        return $data;
    }

    $found = bhp_book_lookup_product(get_queried_object_id());
    if (!$found || !$found['canonical']) {
        return $data;
    }

    $reg = bhp_book_registry();
    $hc = wc_get_product($reg[$found['key']]['hc_product']);
    if (!$hc || '' === $hc->get_price()) {
        return $data; // No live price -> no offer, rather than a guess.
    }

    // The one resolver the whole theme already uses for "which format did this
    // request ask for" — the same call that drives the pressed card and the CTA.
    // Re-deriving it here would create a second definition that can drift, and
    // the visible page and the structured data disagreeing is the exact defect
    // this function is fixing.
    $hardcover_leads = 'hardcover' === bhp_book_incoming_format();

    foreach ($data as $key => $node) {
        if (!isset($node['@type']) || false === strpos((string) wp_json_encode($node['@type']), 'Product')) {
            continue;
        }

        $offer = [
            '@type'         => 'Offer',
            'price'         => wc_format_decimal($hc->get_price(), wc_get_price_decimals()),
            'priceCurrency' => get_woocommerce_currency(),
            'availability'  => $hc->is_in_stock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url'           => get_permalink($reg[$found['key']]['pb_product']) . '?bhp_format=hardcover',
            'sku'           => $hc->get_sku(),
            'itemCondition' => 'https://schema.org/NewCondition',
        ];

        $existing = isset($node['offers']) ? $node['offers'] : [];
        if (isset($existing['@type'])) {
            $existing = [$existing]; // Single offer -> list.
        }
        $existing = array_values((array) $existing);

        // Never add the same SKU twice if this filter runs more than once.
        foreach ($existing as $e) {
            if (isset($e['sku']) && $e['sku'] === $offer['sku']) {
                return $data;
            }
        }

        /*
         * A LEADING offer must not be structurally thinner than the one it
         * leads. Rank Math gives its own (paperback) offer `seller` and
         * `priceValidUntil`; a hardcover offer promoted to first position
         * without them would look like a lesser record of the same shop to
         * anything reading the graph in order.
         *
         * ⛔ COPIED, NEVER INVENTED. Both values are taken from the sibling
         *    offer Rank Math already built on this same page — same shop, same
         *    validity window. If Rank Math did not emit one, neither does this;
         *    a fabricated `priceValidUntil` would be a made-up commercial fact.
         */
        if ($hardcover_leads) {
            foreach (['seller', 'priceValidUntil'] as $carry) {
                if (!isset($offer[$carry])) {
                    foreach ($existing as $sibling) {
                        if (is_array($sibling) && isset($sibling[$carry])) {
                            $offer[$carry] = $sibling[$carry];
                            break;
                        }
                    }
                }
            }
            array_unshift($existing, $offer);
        } else {
            $existing[] = $offer;
        }

        $data[$key]['offers'] = $existing;
        break;
    }

    return $data;
}
add_filter('rank_math/json_ld', 'bhp_book_add_hardcover_offer', 999);
