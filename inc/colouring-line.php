<?php
/**
 * THE COLOURING LINE ON THE STOREFRONT — product page, shop card, offer.
 * Theme 1.19.277 / plugin 1.8.62. Workstream `CYCLE165-LD-SHOP-MATRIX-FINISH`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHAT THIS FILE IS, IN ONE LINE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The PRESENTATION half of the colouring line. The plugin owns the cart maths
 * (`offer-engine.php`, `bundle-data.php`); this file owns what a parent sees.
 * ⛔ It computes no price, no discount and no shipping figure of its own —
 *    every number on every surface below is read from the plugin or from
 *    WooCommerce at render time.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE RAIL CONTRACT — `FD-549`, AND IT IS ABSOLUTE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ A CARD'S IMAGE AND ITS PRICE DESCRIBE THE SAME OBJECT.
 *
 *   · A single-title card shows THAT title's cover and THAT title's price.
 *   · A bundle card shows a BUNDLE COMPOSITE and the BUNDLE price.
 *   · A collection card shows the COLLECTION composite and its price.
 *
 * ⛔⛔ AND THE DEGRADE RULE, WHICH IS THE HALF THAT ACTUALLY GETS VIOLATED
 *    (spec `R2.3`): if a bundle composite does not resolve, the card renders
 *    **WITH NO IMAGE**. ⛔ It must NEVER fall back to one component's cover.
 *    A chapter-book cover beside a bundle price states that THAT BOOK costs
 *    the bundle price — a false claim assembled from two true facts, and the
 *    exact defect `FD-549` was written after.
 *
 * ⚠️ NO BUNDLE COMPOSITE EXISTS TODAY. It is the `design-creative`
 *    launch deliverable in the spec's §8 imagery plan. ⭐ SO EVERY OFFER
 *    SURFACE IN THIS FILE RENDERS IMAGELESS, BY DESIGN, UNTIL ONE IS
 *    REGISTERED THROUGH `bhp_offer_composite_attachment_id`. That is the
 *    contract holding, not a gap in the build.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ EVERY CUSTOMER-FACING STRING BELOW IS A **DRAFT FOR ANDREW**
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ They are collected in ONE function, `bhp_colouring_draft_copy()`, so he
 *    can read the whole surface in one place and change it in one place.
 *    ⛔ NONE of them is approved copy. They are flagged in the build report.
 *
 * ⭐ THE STANDING COPY RAIL IS OBEYED IN ALL OF THEM: §9.1 voice (I/me/my, ⛔
 *    never a company "we") · ⛔ no em dash · ⭐ ages **6-9**, never 5-9 · ⛔ no
 *    outcome claim (what the book IS, never what it will do to a child) · ⛔ no
 *    review, rating, testimonial, statistic or comparison.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE COUNTS ON THE PAGE ARE THE COUNTS IN THE PRINTED BOOK
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Spec `R3.8`: **recompute at build, never inherit.** `FD-532`'s own note
 *    said "40 designs / 80 pages" and that was true then and is stale now.
 *
 * ⭐⭐ RECOMPUTED THIS BUILD, FIRST-HAND, FROM THE SHIPPED FILE ON DISK:
 *    `01 — Books & Manuscripts\MT Coloring Book\MARIANA-COLORING-BOOK-FINAL-
 *    57designs-118pp-8.5x11-300dpi-LOSSLESS.pdf` parsed with PyMuPDF —
 *    ⭐ **118 pages**, ⭐ **8.5 x 11.0 in**, both read out of the file itself
 *    rather than off its filename.
 *
 * ⭐ **57** is the design count printed on ANDREW'S OWN COVER ("57 COLORING
 *    ADVENTURES INSIDE!", read this build from his `MT Coloring Book Cover
 *    (17.52 x 11.25 in).pdf`) and recorded at `22-COLOURING-BOOK-PRODUCTION-
 *    CANON.md`. ⛔ It is not counted programmatically and is not asserted as
 *    if it were: it is sourced to the printed artefact, which is the object
 *    the claim is about.
 *
 * ⛔ THE WORDING IS HIS OWN COVER'S WORDING — "coloring adventures", not a
 *    phrase invented here. The safest source for a claim about a book is the
 *    book.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE NEVER DOES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * No product record, price, SKU, stock status, variation, coupon, tax,
 * shipping zone or shipping method is written or read-then-rewritten by any
 * function here. No `aggregateRating` and no `review` schema is emitted for
 * the colouring line — ⛔ there are no reviews, and absent is the only honest
 * state. No row is rendered for an offer that cannot be bought today.
 */

defined('ABSPATH') || exit;

/**
 * ⭐ THE THEME-SIDE COLOURING REGISTRY — presentation facts only.
 *
 * ⛔ IT HOLDS NO PRODUCT ID AND NO SKU. Identity comes from the plugin's
 *    SKU-keyed `bhp_colouring_product_ids()`, which is the single source of
 *    truth for "is this a colouring product" on both environments. A second
 *    id map here is exactly the drift 1.8.61 wrote its registry to prevent.
 *
 * ⛔ AND IT HOLDS NO TITLE. `FD-557` is verbatim and is the PRODUCT RECORD's
 *    own title; restating it here would create a second copy that can drift
 *    from the record a customer actually buys.
 *
 * @return array<string,array> Adventure slug => presentation facts.
 */
function bhp_colouring_registry() {
    return apply_filters('bhp_colouring_registry', [
        'mariana' => [
            // Shop-card descriptor. ⛔ <= 40 chars, states a FACT (spec R2.8).
            'descriptor' => __('Color the deep ocean', 'brave-hearts'),
            // ⭐ Recomputed from the shipped PDF this build. See the header.
            'designs'    => 57,
            'pages'      => 118,
            'trim'       => __('8.5 x 11', 'brave-hearts'),
        ],
    ]);
}

/**
 * Adventure slug for a colouring product id, or null.
 *
 * ⛔ ID-BASED, NEVER TITLE-SUBSTRING. `CYCLE165-OPS-019` was the
 *    title-substring absorption bug that put a colouring cover beside a
 *    chapter-book price. This delegates to the plugin's ID test and adds no
 *    second rule that could disagree with it.
 *
 * @param int $product_id
 * @return string|null
 */
function bhp_colouring_slug_for_product($product_id) {
    if (!function_exists('bhp_colouring_product_ids')) {
        return null;
    }
    foreach (bhp_colouring_product_ids() as $slug => $id) {
        if ((int) $id === (int) $product_id) {
            return $slug;
        }
    }
    return null;
}

/** True when the current request is a colouring-line product page. */
function bhp_colouring_is_product_page() {
    if (!function_exists('is_product') || !is_product()) {
        return false;
    }
    return null !== bhp_colouring_slug_for_product(get_queried_object_id());
}

/**
 * ⛔ EVERY CUSTOMER-FACING STRING IN THE COLOURING LINE, IN ONE PLACE.
 *    ⭐ DRAFTS FOR ANDREW. Not approved copy. See the file header.
 *
 * @param string $key
 * @param array  $tokens sprintf arguments.
 * @return string
 */
function bhp_colouring_draft_copy($key, array $tokens = []) {
    $copy = apply_filters('bhp_colouring_draft_copy', [
        // Product page — the one-format rail. ⛔ NOT a question: there is no choice.
        'rail_heading'    => __('Paperback · 8.5 × 11', 'brave-hearts'),
        'rail_card_name'  => __('PAPERBACK', 'brave-hearts'),
        /*
         * ═════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.349 — THE COLOURING SHIPPING LINE. `CYCLE179-LD-349`,
         *     the fix for `WHATS-INSIDE-COPY.md` §0 finding 5, seal 687.
         * ═════════════════════════════════════════════════════════════════
         *
         * ⛔⛔ THE DEFECT WAS LIVE AND IT UNDERSTATED WHAT THE CART CHARGES.
         *     This rail set `rail_spec` but NO `rail_note`, so the shipping
         *     sentence fell through to the chapter-book default,
         *     `bhp_book_ship_note_single(bhp_bundle_single_shipping('paperback'))`
         *     = "$1.99". A cart holding exactly one colouring book charges
         *     $2.99 (`bhp_colouring_single_shipping()`, plugin 1.8.66,
         *     founder carrier item 195, 2026-08-21). ⭐ MEASURED, NOT
         *     INFERRED: the live staging PDP at 1.19.348 rendered
         *     "Shipping starts at $1.99 in the contiguous US" on this page,
         *     read out of the DOM at an asserted 1440x900 on 2026-09-02.
         *
         * ⛔ THE FIX IS A THEME STRING. NOT A WOOCOMMERCE SETTING, not a
         *    zone, not a method, not a tier number. Nothing about what the
         *    cart charges changes; the sentence stops disagreeing with it.
         *
         * ⛔ THE FIGURE IS NEVER TYPED. `%s` is filled at render time from
         *    the plugin's own approved table, so this sentence cannot drift
         *    away from the charge the way the inherited one did.
         *
         * ⭐ THE WORDING IS THE APPROVED ONE, seal 687, proven by
         *    `CYCLE179-CX-SHIP-CART-TEST`. ⛔ "I", never "we" (§9.1 — and
         *    `21-PROTECTED-ELEMENTS-MANIFEST.md` §3.3 protects the string
         *    "Ships from my print partner" at min 1 on a product page, which
         *    this keeps). ⛔ No em dash. ⛔ "contiguous US" retained.
         */
        /* translators: %s is a dollar amount, e.g. 2.99 */
        'rail_note'       => __('Ships from my print partner. Shipping is $%s for this book on its own in the contiguous US. 3 or more books ship FREE.', 'brave-hearts'),
        /*
         * ═════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.350 — THE TRIM COMES OUT OF THIS LINE. ONE THING ONCE.
         *     `CYCLE179-LD-350-BUILD`, the second 349 cosmetic.
         * ═════════════════════════════════════════════════════════════════
         *
         * ⛔ THE SUPERSEDED STRING, PRESERVED SO THE MOVEMENT IS VISIBLE:
         *
         *      '%1$d coloring adventures · %2$d pages · %3$s · ages 6-9'
         *
         * ⛔⛔ THE DEFECT WAS LIVE AND IT WAS MEASURED, NOT INFERRED. On the
         *     colouring product page at an asserted 1440x900 on staging2,
         *     2026-09-02, the trim printed TWICE, 23px apart, in two
         *     different glyphs:
         *
         *       y503  .bhp-formats__spec     "… · 118 pages · 8.5 x 11 · ages 6-9"
         *       y526  .bhp-formats__heading  "Paperback · 8.5 × 11"
         *
         *     Same fact, twice, with an ASCII `x` in one and a multiplication
         *     sign in the other, which reads as two different measurements to
         *     anyone who notices and as a duplication bug to anyone who does.
         *
         * ⭐ THE HEADING KEEPS IT AND THIS LINE LOSES IT, not the other way
         *    round, because the heading is the FORMAT CARD'S OWN NAME and the
         *    trim is part of naming the format: "Paperback · 8.5 × 11" is what
         *    the object IS. This line lists what is inside it. ⛔ Nothing is
         *    lost from the page: the trim still renders, 23px lower, once.
         *
         * ⛔ `%3$s` IS RETAINED IN THE ARGUMENT LIST AND SIMPLY NOT PRINTED —
         *    see `bhp_colouring_purchase_data()`, which still passes designs,
         *    pages and trim. Removing the token here rather than the argument
         *    there means the registry's `trim` field stays the single source
         *    for the heading and nothing else had to move.
         *
         * ⭐ His own cover's wording. Ages 6-9. ⛔ No outcome claim, no "we",
         *    no em dash.
         */
        /* translators: %1$d: number of designs. %2$d: number of pages. %3$s: trim size, retained for callers and deliberately not printed since 1.19.350. */
        'spec_line'       => __('%1$d coloring adventures · %2$d pages · ages 6-9', 'brave-hearts'),
        /*
         * The offer. ⛔ "I", never "we". ⛔ THREE DIFFERENT SENTENCES, and that
         * is deliberate: the card title NAMES the offer, the descriptor says
         * what is in it, and the module heading is suppressed on the card so
         * the same words never appear twice in one tile.
         */
        /*
         * ⛔ THE CARD TITLE DESCRIBES, IT DOES NOT NAME. "The Mariana Trench
         *    Pair" was drafted first and dropped: coining a product NAME for
         *    something that is not a product would be inventing a brand term
         *    on Andrew's behalf, and `FD-579` is explicit that no bundle
         *    product record exists. This says what is in the cart and stops.
         */
        'offer_card_title' => __('The Mariana Trench: book + coloring book', 'brave-hearts'),
        'offer_heading'    => __('The book and its coloring book', 'brave-hearts'),
        'offer_cta'        => __('ADD BOTH FOR %s', 'brave-hearts'),
        'offer_saving'     => __('Save %s', 'brave-hearts'),
        'offer_upsell'     => __('Prefer the hardcover? %s', 'brave-hearts'),
        'offer_descriptor' => __('The chapter book and its coloring book', 'brave-hearts'),

        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.281 — THE TWO STRINGS THE CART SIDE PANEL NEEDS.
         *     ⚠️ NEW DRAFTS. THEY GO ON THE LIST THAT WAITS FOR ANDREW.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐ WHY THEY EXIST: the panel's cross-sell rail could offer "add the
         *    next chapter book" and could not offer the coloring book, which
         *    is one of the two things Andrew named by hand in item 186 ("add
         *    the coloring book, add the next chapter book etc."). A rail
         *    cannot offer a thing it has no words for.
         *
         * ⛔ TWO STRINGS, NOT A PARAGRAPH, and both built to the existing
         *    rails: no "we" (§9.1 — he is the sole operator), no em dash, no
         *    outcome claim, and NO SAVINGS FIGURE INSIDE THE COPY. The
         *    "- Save $1.99" clause is appended by the drawer from
         *    `bhp_offer_saving()`, recomputed live, exactly as the adventure
         *    cross-sell does it. A number typed into a string here would be
         *    the derived-claim trap.
         *
         * ⛔ `panel_label` TAKES THE ADVENTURE NAME AS A TOKEN rather than
         *    naming Mariana, so the day Everest's coloring book gets a record
         *    the line is already right and nobody writes a second string.
         */
        'panel_label'      => __('%s coloring book', 'brave-hearts'),
        'panel_cta'        => __('Add The Coloring Book', 'brave-hearts'),

        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.287 — THE SAME OFFER, READ FROM THE OTHER END.
         *     CARRIER ITEM 214. ⚠️ NEW DRAFTS. THEY GO ON ANDREW'S LIST.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐ WHY THEY EXIST: the panel could offer the coloring book to a cart
         *    holding the chapter book, and could offer NOTHING AT ALL to a
         *    cart holding only the coloring book. That cart is one $11.99 book
         *    away from the same `FD-581` $22.99 pair, and the rail had no
         *    words for the ask. ⛔ This is the SAME offer and the SAME saving
         *    read in the other direction, not a new offer and not a new price.
         *
         * ⛔ NO SAVINGS FIGURE IS TYPED INTO EITHER STRING, for the same
         *    reason `panel_label` / `panel_cta` carry none: the "- Save $X"
         *    clause is appended by the drawer from `bhp_offer_saving()`,
         *    recomputed live. A number here would be the derived-claim trap.
         *
         * ⛔ BOTH OBEY THE STANDING RAIL: no "we" (§9.1 — he is the sole
         *    operator), no em dash, no outcome claim, no review, no statistic.
         *
         * ⭐ `panel_chapter_label` TAKES BOTH THE BOOK NAME AND THE FORMAT AS
         *    TOKENS. The format is the half that matters here: this rail can
         *    offer a paperback or a hardcover, and an unlabelled title on a
         *    cart that is deciding between $11.99 and $17.99 is the `FD-549`
         *    ambiguity again. ⛔ Neither word is coined — "Paperback" and
         *    "Hardcover" are the site's own format labels.
         *
         * ⭐ `panel_chapter_cta` MIRRORS `panel_cta` ABOVE and deliberately
         *    does NOT restate the title the label directly above it already
         *    carries (`R2.6`, one thing once). ⛔ "Add This Adventure" was
         *    rejected: it is the ADVENTURE cross-sell's approved button, it
         *    would collide with that offer's meaning, and the thing being
         *    offered here completes a pair rather than a collection.
         */
        /* translators: %1$s: book title. %2$s: format, e.g. Paperback. */
        'panel_chapter_label' => __('%1$s (%2$s)', 'brave-hearts'),
        'panel_chapter_cta'   => __('Add The Chapter Book', 'brave-hearts'),

        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.284 — THE TWO STRINGS THE PRODUCT-STYLE BUNDLE CARD NEEDS.
         *     ⚠️ NEW DRAFTS. THEY GO ON THE LIST THAT WAITS FOR ANDREW.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐ WHY THEY EXIST: carrier item 206 makes the bundle a PRODUCT-STYLE
         *    CARD — "image · title · price · CTA". That splits one string into
         *    two jobs the old `offer_cta` did at once. "ADD BOTH FOR $22.99"
         *    was BOTH the price and the button; on a card the price is its own
         *    line, so the button must stop restating it (`R2.6`, one price
         *    once) and the price line needs a label of its own.
         *
         * ⛔ `offer_cta` IS NOT DELETED AND NOT REWORDED. It is still the
         *    product-page control's exact label, byte for byte. Only the shop
         *    card takes the new pair. A string he has seen keeps its wording.
         *
         * ⛔ BOTH OBEY THE STANDING RAIL: no "we" (§9.1 — he is the sole
         *    operator), no em dash, no outcome claim, no review or statistic,
         *    ⛔ AND NO FIGURE TYPED INTO EITHER OF THEM. The number comes from
         *    `bhp_offer_price()` at render, every render.
         *
         * ⭐ `offer_card_price_label` SAYS WHAT THE $22.99 BUYS, which is the
         *    job the neighbouring cards' "PAPERBACK" / "HARDCOVER" labels do.
         *    An unlabelled figure on a bundle tile is the `FD-549` ambiguity
         *    the whole rail contract exists to close.
         */
        'offer_card_price_label' => __('BOOK + COLORING BOOK', 'brave-hearts'),
        /*
         * ⭐ 1.19.350 — the CARD's hardcover swap label. See the long note at
         *    the swap's own render site. ⛔ It NAMES the two objects in the
         *    cart, exactly as `offer_card_price_label` above does, and it never
         *    says "pair". ⛔ No figure is typed: `%s` is filled from
         *    `bhp_offer_price()` at render. ⛔ No "we", no em dash, no claim.
         */
        /* translators: %s is a dollar amount, e.g. $28.99. */
        'offer_card_upsell'      => __('HARDCOVER + COLORING BOOK %s', 'brave-hearts'),
        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.286 — CARRIER ITEM 211. THE CARD CTA BECOMES THE ONE WORD.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⛔ THE SUPERSEDED STRING, QUOTED SO THE MOVEMENT IS VISIBLE AND IS
         *    NOT RE-DERIVED: this key read `'GET BOTH'` in 1.19.284–1.19.285.
         *    ⭐ THE FOUNDER PHOTOGRAPHED IT OVERFLOWING ITS CARD on his own
         *    desktop — an observed defect on his own screen, which Standing
         *    Rules §9.2 rule 7 ranks above every instrument QA owns.
         *
         * ⭐ IT NOW DEFERS TO `bhp_shop_card_atc_label()` — the ONE label, in
         *    the theme's one place — rather than holding a sixth literal that
         *    happens to match today. ⛔ A uniform grid maintained as six
         *    strings is uniform until the next release touches five of them.
         *
         * ⛔ `offer_cta` ABOVE IS NOT TOUCHED. The PRODUCT-PAGE control still
         *    reads "ADD BOTH FOR $22.99", byte for byte. A string he has seen
         *    keeps its wording; only the SHOP CARD moved.
         */
        'offer_card_cta'         => function_exists('bhp_shop_card_atc_label')
            ? bhp_shop_card_atc_label()
            : __('ADD TO CART', 'brave-hearts'),

        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.346 — THE VALUE-PROP LINE UNDER THE H1.
         *     `CYCLE178-LD-345-PDP-LINE`, from the `commerce-cx` colouring-line
         *     launch review (see internal release notes) F-list. ⚠️ NEW DRAFT.
         *     IT GOES ON THE LIST
         *     THAT WAITS FOR ANDREW, exactly like every other key in here.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⛔ THE DEFECT: `bhp_woocommerce_product_value_prop()` in
         *    `functions.php` renders ONE hardcoded sentence for EVERY product
         *    page — "Adventure chapter books for ages 6-9 that combine real
         *    places, science, history, courage, and kindness." On the colouring
         *    PDP that is a FALSE DESCRIPTION OF THE OBJECT BEING SOLD: the book
         *    has no chapters, no history and no science. It is the same class
         *    of defect as `CYCLE165-OPS-019` (a colouring cover beside a
         *    chapter-book price) read on the copy side instead of the price
         *    side — two true facts about DIFFERENT objects, assembled into one
         *    false claim about this one.
         *
         * ⛔ THE SPEC LINE ABOVE ALREADY CARRIES THE COUNTS. This line must NOT
         *    restate the designs, pages or trim (`R2.6`, one thing once) — it
         *    says what the object IS and stops, which is the job the chapter
         *    books' own hook does for them.
         *
         * ⛔ IT OBEYS THE STANDING RAIL, and every clause was checked rather
         *    than assumed: no "we" (§9.1 — he is the sole operator) · ⛔ NO EM
         *    DASH, and "ages 6 to 9" is spelled with the word "to" rather than
         *    the en dash the chapter-book hook uses, because the brief
         *    specified this wording · ⭐ ages 6-9, never 5-9 · ⛔ no outcome
         *    claim (what the book IS, never what it will do to a child) · ⛔ no
         *    review, rating, statistic or comparison.
         *
         * ⭐ "coloring adventure" IS HIS OWN COVER'S NOUN ("57 COLORING
         *    ADVENTURES INSIDE!"), singular here because the line describes the
         *    book, not the count inside it. Nothing is coined.
         */
        'value_prop_hook'        => __('A coloring adventure for ages 6 to 9', 'brave-hearts'),
    ], $key);

    if (!isset($copy[$key])) {
        return '';
    }
    return $tokens ? vsprintf($copy[$key], $tokens) : $copy[$key];
}

/* ═══════════════════════════════════════════════════════════════════════════
 * THE PRODUCT PAGE — a one-card format rail
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE MECHANISM ALREADY EXISTS AND IS PROVEN IN PRODUCTION CODE, which is
 *    why nothing new is invented for it: `bhp_book_available_formats()`
 *    returning a single format already renders a one-card `bhp-formats__grid`
 *    on the school-visit path (1.19.240). ⛔ This reuses the same template and
 *    the same grid, so every element `21-PROTECTED-ELEMENTS-MANIFEST.md` §3.3
 *    protects is emitted on this page too — `bhp-formats__selected-price`,
 *    `bhp-formats__grid`, `bhp-formats__cta`.
 *
 * ⛔ NO SECOND TEMPLATE. A second template is a second place for a protected
 *    element to go missing (spec §7), and `FD-558` says simple.
 */

/**
 * Purchase data for a colouring product, in the shape `format-cards.php`
 * already consumes.
 *
 * ⛔ KINDLE URL IS EMPTY AND THAT IS LOAD-BEARING: the template already gates
 *    its Kindle card on a non-empty url, so an empty string removes the card
 *    rather than hiding it. A control that exists in the DOM is reachable by
 *    keyboard and by a screen reader.
 *
 * ⛔ `show_collection` IS FALSE: the Complete Collection is three CHAPTER
 *    books. Offering it as a "format" of a colouring book would state that the
 *    two are the same object, which is the rail contract violated on the
 *    template that invented the rail.
 *
 * @param int $product_id
 * @return array|null
 */
function bhp_colouring_purchase_data($product_id) {
    $slug = bhp_colouring_slug_for_product($product_id);
    if (!$slug || !function_exists('wc_get_product')) {
        return null;
    }
    $product = wc_get_product($product_id);
    if (!$product) {
        return null;
    }

    $reg = bhp_colouring_registry();

    /*
     * ⭐⭐ THE COLOURING BOOK SITS IN THE `paperback` SLOT, AND THAT IS THE
     *     WHOLE TRICK. It is not a workaround — it is what the book IS: a
     *     single-format, perfect-bound paperback. Filling the slot the template
     *     already understands means the ENTIRE proven path runs unchanged —
     *     the server-rendered price, the server-rendered CTA, the pressed
     *     card, the `bhp-formats__grid`, `bhp-formats__selected-price` and
     *     `bhp-formats__cta` that `21-PROTECTED-ELEMENTS-MANIFEST.md` §3.3
     *     protects, and the §3.7 ordering assertion.
     *
     * ⛔ A `colouring` FORMAT KEY WOULD HAVE MEANT TEACHING EVERY ONE OF THOSE
     *    ABOUT A FIFTH FORMAT — five places for a protected element to go
     *    missing, to render one card that already renders.
     *
     * ⛔ `hardcover` IS EMPTY AND `rail_hardcover` IS FALSE: there is no
     *    hardcover colouring book. The template's own `$bhp_hc_offerable`
     *    branch then drops the card AND the payload entry together, which is
     *    the 1.19.240 behaviour that already ships on the school-visit path.
     */
    return [
        'key'           => 'colouring_' . $slug,
        'title'         => $product->get_name(), // ⛔ FD-557 lives on the record.
        'descriptor'    => $reg[$slug]['descriptor'],
        'canonical_url' => get_permalink($product_id),
        'paperback'     => [
            'product_id'   => (int) $product_id,
            'variation_id' => 0,
            'sku'          => $product->get_sku(),
            // ⛔ LIVE, every request. Never a literal.
            'price_html'   => $product->get_price_html(),
            'price'        => $product->get_price(),
            'in_stock'     => $product->is_in_stock(),
            /*
             * ⭐ 1.19.281 — CARRIER ITEM 188. The colouring book's ADD TO CART
             *    is an ADD TO CART like any other, so it carries the same
             *    `bhp_buy=panel` mark and opens the same side panel.
             *
             * ⛔ IT WAS UNMARKED IN 1.19.277–1.19.280 — it never carried
             *    `bhp_buy=checkout`, so this is not a change of destination,
             *    it is the FIRST time this button is steered at all. Before
             *    this line it fell through to the store's stored
             *    `woocommerce_cart_redirect_after_add`, i.e. to the cart page
             *    footer Andrew objected to in item 186.
             */
            'add_url'      => function_exists('bhp_purchase_flow_mark_panel')
                ? bhp_purchase_flow_mark_panel(add_query_arg(['add-to-cart' => $product_id], get_permalink($product_id)))
                : add_query_arg(['add-to-cart' => $product_id], get_permalink($product_id)),
        ],
        'hardcover'     => [
            'product_id'   => 0,
            'variation_id' => 0,
            'sku'          => '',
            'price_html'   => '',
            'price'        => '',
            'in_stock'     => false,
            'add_url'      => '',
        ],
        // ⛔ Empty url: the template already gates the Kindle card on a
        //    non-empty url, so the card is NOT EMITTED rather than hidden.
        'kindle'          => ['url' => ''],
        'collection'      => ['url' => '', 'price_html' => '', 'format' => 'paperback'],

        /*
         * ⭐ THE THREE FLAGS THE TEMPLATE READS FOR THIS RAIL.
         *
         * ⛔ `rail_collection` IS FALSE BECAUSE THE COMPLETE COLLECTION IS
         *    THREE CHAPTER BOOKS. Offering it as a "format" of a colouring
         *    book would state that the two are the same object — the rail
         *    contract violated on the template that invented the rail.
         */
        'rail_single'     => true,
        'rail_hardcover'  => false,
        'rail_collection' => false,
        // ⛔ NOT the chapter-book spec line. "12 short chapters" is false here.
        'rail_spec'       => bhp_colouring_draft_copy(
            'spec_line',
            [$reg[$slug]['designs'], $reg[$slug]['pages'], $reg[$slug]['trim']]
        ),
        'rail_heading'    => bhp_colouring_draft_copy('rail_heading'),
        /*
         * ⭐ 1.19.349 — THE SHIPPING SENTENCE FOR THIS RAIL. See the long note
         *    on the `rail_note` string in `bhp_colouring_draft_copy()`.
         *
         * ⛔ THE NUMBER IS READ, NEVER TYPED, and it is read from the ONE
         *    function that owns it. `function_exists()` is the gate, not
         *    decoration: `bhp_colouring_single_shipping()` lives in the bundle
         *    PLUGIN and this is the THEME. With the plugin deactivated this
         *    falls back to `2.99`, which is the same figure the plugin returns
         *    today (verified by reading `bundle-data.php` this build), so a
         *    plugin-less site shows a correct sentence rather than a blank one
         *    or a fatal.
         */
        'rail_note'       => bhp_colouring_draft_copy('rail_note', [
            number_format(
                function_exists('bhp_colouring_single_shipping')
                    ? (float) bhp_colouring_single_shipping()
                    : 2.99,
                2
            ),
        ]),
    ];
}

/**
 * Render the one-card rail on a colouring product page.
 *
 * Priority 15 — ⭐ THE SAME SLOT the chapter books' rail uses, so price,
 * format element and ADD TO CART all precede the long-form body exactly as
 * `test-product-template.php` §3.7 asserts for every other product page.
 */
function bhp_colouring_render_format_rail() {
    if (!bhp_colouring_is_product_page()) {
        return;
    }
    $data = bhp_colouring_purchase_data(get_queried_object_id());
    if (!$data) {
        return;
    }
    $initial = 'paperback';
    $bhp_tpl = locate_template('template-parts/commerce/format-cards.php');
    if ('' === $bhp_tpl) {
        return; // Template missing: render nothing rather than emit a warning.
    }
    include $bhp_tpl;
}
add_action('woocommerce_single_product_summary', 'bhp_colouring_render_format_rail', 15);

/**
 * Suppress WooCommerce's own price + add-to-cart on a colouring page, because
 * the rail above provides both.
 *
 * ⛔ EXACTLY THE TREATMENT `bhp_book_remove_default_add_to_cart()` ALREADY
 *    APPLIES to the three chapter pages. Two purchase controls on one page is
 *    the defect 1.19.274 fixed on a different template.
 */
function bhp_colouring_remove_default_add_to_cart() {
    if (!bhp_colouring_is_product_page()) {
        return;
    }
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
}
add_action('wp', 'bhp_colouring_remove_default_add_to_cart');

/*
 * ⛔ THERE IS NO SEPARATE "WHAT IS INSIDE" BLOCK, AND THAT IS DELIBERATE.
 *
 * ⭐ The design count, page count, trim and reading age travel in the rail's
 *    own `formatSpec` slot (`$data['rail_spec']`), which is the element that
 *    already sits directly under the price on every other product page and
 *    already swaps with the selector. Printing the same four facts a second
 *    time lower down would state one thing twice, in two places that can
 *    drift, on the page where `R3.8` asks for exactly one count.
 */

/* ═══════════════════════════════════════════════════════════════════════════
 * THE SHOP CARD
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * A colouring card: ⭐ ONE priced line, ⛔ no "Hardcover" element, ⛔ no
 * "CHOOSE YOUR FORMAT" label. Spec `R2.5`.
 *
 * ⛔ AND THE DUPLICATE-PRICE FIX, `R2.6`. A chapter card prints WooCommerce's
 *    own `span.price` AND a "Paperback $11.99" line below it. On a ONE-FORMAT
 *    card those become the same number twice with nothing to tell them apart.
 *    `bhp_colouring_hide_loop_price()` removes WooCommerce's line and this
 *    prints the labelled one, so each distinct price appears exactly once.
 */
function bhp_colouring_shop_card_meta() {
    $slug = bhp_colouring_slug_for_product(get_the_ID());
    if (!$slug) {
        return;
    }
    $reg = bhp_colouring_registry();
    if (!isset($reg[$slug])) {
        return;
    }
    $product = wc_get_product(get_the_ID());
    if (!$product) {
        return;
    }
    ?>
    <?php
    /*
     * ⭐ 1.19.283 — THE AGE LINE, CARRIER ITEM 204, ON THE COLOURING CARD TOO.
     *
     * ⛔ AND IT IS THE SAME HELPER, NOT A SECOND COPY OF THE STRING. Two
     *    literals is how a grid ends up saying "Ages 6-9" on three cards and
     *    something else on the fourth.
     * ⭐ IT IS ALSO TRUE OF THIS BOOK BY ITS OWN COVER: `spec_line` above
     *    already reads "… · ages 6-9", drafted from his own cover wording.
     * ⛔ NO RAIL VIOLATION (`FD-549`): an age band is not a chapter-book word,
     *    and this card still shows THIS product's cover beside THIS product's
     *    single price. Nothing about what the object IS has changed.
     * ⭐ `function_exists()` because `book-formats.php` is required BEFORE this
     *    file (functions.php ~3822 vs ~3880) — so the guard should never fire,
     *    and if the load order is ever reordered the card degrades by dropping
     *    one line rather than fatalling on the shop archive.
     */
    ?>
    <?php if (function_exists('bhp_shop_card_age_range')): ?>
      <p class="bhp-shop-ages"><?php echo esc_html(bhp_shop_card_age_range(get_the_ID())); ?></p>
    <?php endif; ?>
    <p class="bhp-shop-descriptor" data-bhp-card-kind="single"><?php echo esc_html($reg[$slug]['descriptor']); ?></p>
    <?php
    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.350 — THE COLOURING CHIP CARRIES THE TRIM, NOT A SECOND PRICE.
     *     `CYCLE179-LD-350-BUILD`.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ THE SUPERSEDED CHIP, PRESERVED SO THE MOVEMENT IS VISIBLE:
     *      label = `rail_card_name` ("PAPERBACK"), amount = wc_price(get_price()).
     *
     * ⭐ WHY IT CHANGES. Until 1.19.349 this card was the ONE card in the grid
     *    with no `.price` element: `bhp_colouring_hide_loop_price()` removed
     *    WooCommerce's own line so that the labelled chip could carry the only
     *    figure, which was `R2.6`'s correct answer WHEN THE CHIP WAS THE ONLY
     *    PLACE A PRICE COULD GO. From 1.19.350 every card in the catalog grid
     *    has the same shape — one big price, then format chips — and a card
     *    that opts out of the big price is a card shaped unlike its neighbours.
     *
     * ⭐ SO THE PRICE MOVES UP INTO THE SLOT EVERY OTHER CARD USES, AND THE
     *    CHIP CARRIES THE OTHER FACT A BUYER OF A COLOURING BOOK WANTS: the
     *    trim. That is what the founder-approved concept of record draws
     *    (the design lane's shop concept set, seal 686:
     *    "PAPERBACK / 8.5 x 11").
     *
     * ⛔ `R2.6` IS NOT WEAKENED, IT IS SATISFIED THE OTHER WAY. The figure
     *    still appears EXACTLY ONCE on the card — in `.price` instead of in
     *    the chip. See `bhp_colouring_hide_loop_price()` below, which now
     *    stands down on a catalog grid for precisely this reason.
     *
     * ⛔ THE TRIM IS READ FROM THE REGISTRY, never typed. It is the same
     *    `trim` field the product page's format heading reads, so the card and
     *    the page cannot state two different sizes.
     */
    $bhp_col_trim = isset($reg[$slug]['trim']) ? (string) $reg[$slug]['trim'] : '';
    ?>
    <span class="bhp-shop-from-price bhp-shop-format-prices">
      <span class="bhp-shop-format-price bhp-shop-format-price--selected">
        <span class="bhp-shop-format-price__label"><?php echo esc_html(bhp_colouring_draft_copy('rail_card_name')); ?></span>
        <?php if ('' !== $bhp_col_trim && function_exists('bhp_catalog_grid_context') && bhp_catalog_grid_context()): ?>
          <span class="bhp-shop-format-price__trim"><?php echo esc_html($bhp_col_trim); ?></span>
        <?php else: ?>
          <span class="bhp-shop-format-price__amount"><?php echo wp_kses_post(wc_price((float) $product->get_price())); ?></span>
        <?php endif; ?>
      </span>
    </span>
    <?php
}
add_action('woocommerce_after_shop_loop_item_title', 'bhp_colouring_shop_card_meta', 12);

/**
 * Remove WooCommerce's own loop price on a colouring card only. `R2.6`.
 */
function bhp_colouring_hide_loop_price() {
    if (is_admin() || !function_exists('is_shop')) {
        return;
    }
    if (null === bhp_colouring_slug_for_product(get_the_ID())) {
        return;
    }
    /*
     * ⭐⭐ 1.19.350 — IT STANDS DOWN ON A CATALOG GRID, AND `R2.6` STILL HOLDS.
     *
     * ⛔ THE RULE HAS NOT CHANGED: each distinct price appears exactly once on
     *    a card. What changed is WHERE the single figure lives. From 1.19.350
     *    the colouring chip carries the TRIM ("8.5 x 11") instead of the price,
     *    so removing WooCommerce's own `.price` here would leave the card with
     *    NO figure at all, which is a worse defect than the duplicate this
     *    removal was written to prevent.
     *
     * ⭐ EVERYWHERE ELSE THIS IS 1.19.349, BYTE FOR BYTE. Off a catalog grid
     *    the chip still carries the price, so the removal still has a duplicate
     *    to prevent and still performs it.
     */
    if (function_exists('bhp_catalog_grid_context') && bhp_catalog_grid_context()) {
        return;
    }
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
}
add_action('woocommerce_before_shop_loop_item_title', 'bhp_colouring_hide_loop_price', 1);

/**
 * Restore it for the next card in the loop, so removing it on one card cannot
 * silently strip the price from every card after it.
 */
function bhp_colouring_restore_loop_price() {
    if (!has_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price')) {
        add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    }
}
add_action('woocommerce_after_shop_loop_item', 'bhp_colouring_restore_loop_price', 99);

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.286 — THE COLOURING CARD JOINS THE ONE CONTROL SET. ITEMS 210+211.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SUPERSEDED CONTROL, NAMED SO THE MOVEMENT IS VISIBLE: until this
 *    release this card carried WOOCOMMERCE'S OWN loop button — sentence-case
 *    "Add to cart", core's own classes, and no panel hook at all, because
 *    `bhp_book_shop_add_to_cart_link()` returns `$html` untouched for a product
 *    that is not one of the three chapter books. ⭐ It was the one card in the
 *    grid whose button neither matched its neighbours nor opened the panel.
 *
 * ⛔ IT IS THE SAME MECHANISM AS THE CHAPTER CARDS, NOT A SECOND ONE:
 *    `data-bhp-cart-add` + `data-product-id`, intercepted by
 *    `bundle-drawer.js`, added over the Store API, panel opened (item 188).
 *    The href is the JavaScript-less floor and is already marked
 *    `bhp_buy=panel` in `bhp_colouring_purchase_data()` — ⭐ built in ONE place
 *    so the card and the product page cannot disagree about the destination.
 *
 * ⛔ `variation_id` IS 0 AND THAT IS THE RECORD, not a shortcut: the colouring
 *    book is a simple product. `addItem()` resolves `variationId ? variationId
 *    : productId`, so 0 is correct and a fabricated id would be wrong.
 *
 * ⛔ IT DEGRADES TO WOOCOMMERCE'S OWN CONTROL, NEVER TO A LYING LABEL — the
 *    identical rule the chapter cards follow. No live price or out of stock →
 *    `$html` untouched.
 *
 * ⭐ PRIORITY 11: after `bhp_book_shop_add_to_cart_link()` at 10, which has
 *    already declined this product. Two filters, one per catalogue, neither
 *    able to claim the other's cards.
 */
function bhp_colouring_shop_add_to_cart_link($html, $product) {
    if (!$product || !function_exists('bhp_shop_card_atc_label')) {
        return $html;
    }
    /*
     * ⛔ THE SHOP ARCHIVE ONLY — the brief's own boundary, and the same gate the
     *    chapter cards take. Off the archive this card kept WooCommerce's own
     *    control in 1.19.285 and keeps it now: `$html` untouched.
     */
    if (!function_exists('bhp_shop_card_context') || !bhp_shop_card_context()) {
        return $html;
    }
    $data = bhp_colouring_purchase_data($product->get_id());
    if (!$data) {
        return $html;
    }

    $pb = $data['paperback'];
    if (!$pb['product_id'] || '' === $pb['price'] || !$pb['in_stock'] || '' === $pb['add_url']) {
        return $html;
    }

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.295 — LINK MODE ON A VISIT-FLAGGED SESSION. `CYCLE167-LD-002`.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔⛔ THE DEFECT THIS CLOSES, STATED PLAINLY BECAUSE IT WAS BACKWARDS.
     *     On a visit-flagged session the server REFUSES this product
     *     (`FD-642`), and until 1.19.295 this card still rendered a fully
     *     live-looking ADD TO CART for it. The parent pressed a real button
     *     and got a refusal notice. ⭐ Meanwhile the BUNDLE card, which a
     *     parent might legitimately want shipped, was hidden entirely. The
     *     suppression was applied to the wrong one of the two.
     *
     * ⭐ A CONTROL THE SERVER WILL REFUSE MUST NOT LOOK LIVE. That is the
     *    whole rule, and it is the same `R1.4` the offer module already obeys.
     *
     * ⛔ NO `data-bhp-cart-add` AND NO `data-product-id` IN THIS BRANCH. Those
     *    two attributes are exactly what `bundle-drawer.js` binds to; emitting
     *    them would keep the Store API add alive behind a relabelled button,
     *    which is a worse lie than the one being fixed. This is a plain anchor
     *    to a destination that genuinely sells the book.
     *
     * ⛔ BOTH PREDICATES, AND THE PAIR IS LOAD-BEARING.
     *    `bhp_school_visit_is_refused_item()` is a PRODUCT-CLASSIFICATION
     *    predicate with NO SESSION IN IT (its own file's header warns that the
     *    names lie) — it is true of the colouring book for everyone, always.
     *    `bhp_school_visit_paperback_only()` is the SESSION test. Taking the
     *    classifier alone would relabel this card for every ordinary shopper
     *    on the site.
     *
     * ✅ FAILS OPEN TO 1.19.294: plugin off, either predicate missing, or a
     *    resolver that throws → the ordinary ADD TO CART, byte for byte.
     * ⛔ CONTROL PATH: false for every ordinary shopper. Nothing below this
     *    comment runs for anyone who has not opened a school-visit link.
     */
    $bhp_visit_blocked = false;
    if (function_exists('bhp_school_visit_paperback_only')
        && function_exists('bhp_school_visit_is_refused_item')) {
        try {
            $bhp_visit_blocked = bhp_school_visit_paperback_only()
                && bhp_school_visit_is_refused_item((int) $pb['product_id'], 0);
        } catch (Throwable $e) {
            $bhp_visit_blocked = false; // FAIL OPEN.
        }
    }

    if ($bhp_visit_blocked) {
        $bhp_ship_url = bhp_colouring_ship_home_url((int) $pb['product_id']);
        if ('' === $bhp_ship_url) {
            return $html; // No honest destination → leave core's control alone.
        }

        /*
         * ⛔ THE LABEL IS DELIBERATELY NOT `bhp_shop_card_atc_label()`. The
         *    uniform grid label exists so every ADD TO CART matches; this is
         *    NOT an add to cart, and borrowing that label would recreate the
         *    exact "looks live, is not" defect. ⛔ No first person (a button is
         *    read in the parent's voice, and Andrew is the "I" elsewhere on the
         *    storefront). ⛔ American spelling. ⛔ No em dash, no outcome claim.
         */
        /*
         * ⛔⛔ THE `button` CLASS IS DELIBERATELY OMITTED, AND THIS IS A
         *     CORRECTNESS FIX RATHER THAN A STYLING PREFERENCE.
         *     `.woocommerce ul.products li.product .button` sets
         *     `background: … !important; color: white !important` in TWO
         *     places (style.css:1132 and :4416). Carrying `button` here meant
         *     the ship-home control could only be made to look different by
         *     WINNING AN `!important` FIGHT — and a control whose honesty
         *     depends on out-specifying someone else's `!important` is one
         *     stylesheet edit away from silently looking live again.
         *
         * ⭐ DROPPING `button` REMOVES THE FIGHT INSTEAD OF WINNING IT. The
         *    element keeps `bhp-shop-atc`, which carries ALL the grid geometry
         *    (48px floor, wrapping label, card insets) and sets NO colour, so
         *    the `--shiphome` rule governs the treatment outright with no
         *    `!important` anywhere. Same "safe by construction" discipline as
         *    the missing cart form.
         *
         * ⛔ NOTHING BINDS TO `.button` HERE: this is a plain link, and
         *    `bundle-drawer.js` binds `data-bhp-cart-add`, which this control
         *    deliberately does not have.
         */
        /*
         * ⭐⭐ 1.19.350 — THIS CARD NOW CARRIES THE NOTE TOO. `R9a`, seal 698.
         *
         * ⛔ THE DEFECT, VERIFIED LIVE ON PRODUCTION 2026-09-02
         *    (`VISIT-SHOP-AUDIT.md` §E2): the BUNDLE card rendered an
         *    explanatory line under its ship-home control and THIS card
         *    rendered none. A parent tapping the coloring book because their
         *    child wants it lost hand delivery for the chapter books too, in
         *    one tap, with nothing on the page saying so.
         *
         * ⛔ A FILTER ON `woocommerce_loop_add_to_cart_link` CAN ONLY RETURN
         *    THE CONTROL, so the note cannot be appended here. The flag below
         *    is read by `bhp_colouring_shop_shiphome_note()` on
         *    `woocommerce_after_shop_loop_item`, which is the next hook inside
         *    the same `<li>`. ⭐ It is set at the LAST possible moment, after
         *    every degrade path above has already returned, so a card that does
         *    not render the control can never render the note.
         */
        $GLOBALS['bhp_colouring_shiphome_card'] = (int) get_the_ID();

        return sprintf(
            '<a href="%1$s" class="%2$s bhp-shop-atc--shiphome" data-bhp-shiphome>%3$s</a>',
            esc_url($bhp_ship_url),
            esc_attr(defined('BHP_SHOP_ATC_CLASS') ? BHP_SHOP_ATC_CLASS : 'bhp-shop-atc'),
            esc_html__('Ship to your home', 'brave-hearts')
        );
    }

    return sprintf(
        '<a href="%1$s" class="button %2$s" data-bhp-cart-add data-product-id="%3$d" data-variation-id="0">%4$s</a>',
        esc_url($pb['add_url']),
        esc_attr(defined('BHP_SHOP_ATC_CLASS') ? BHP_SHOP_ATC_CLASS : 'bhp-shop-atc'),
        (int) $pb['product_id'],
        esc_html(bhp_shop_card_atc_label())
    );
}
add_filter('woocommerce_loop_add_to_cart_link', 'bhp_colouring_shop_add_to_cart_link', 11, 2);

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.295 — THE ONE SHIP-TO-HOME URL BUILDER FOR THE COLOURING RAIL.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ ONE BUILDER, NOT THREE. The shop card, the refusal sentence and any
 *    future surface all need the same "this destination, with the visit flag
 *    cleared" URL. Three copies of an `add_query_arg()` is how two of them end
 *    up carrying a different token.
 *
 * ⛔ THE PARAM AND TOKEN ARE THE PLUGIN'S CONSTANTS
 *    (`school-visit-pickup.php:251,331`), never literals. The fallbacks exist
 *    only so the theme cannot fatal with the plugin deactivated.
 *
 * ⭐ CLEARING IS SAFE TO DO BY LINK: `?bhp_visit=clear` is handled first in
 *    `bhp_school_visit_capture_intent()` and is a plain session clear. It
 *    creates no order, mutates no product and changes no setting. It costs the
 *    visitor their hand-delivery entitlement in THIS BROWSER, which is why
 *    every surface that renders one of these links must also say so.
 *
 * @param int $product_id Destination product. 0 → the shop archive.
 * @return string Absolute URL, or '' when nothing resolves.
 */
function bhp_colouring_ship_home_url($product_id = 0) {
    $base = '';

    if ($product_id > 0) {
        $base = (string) (get_permalink((int) $product_id) ?: '');
    }
    if ('' === $base && function_exists('wc_get_page_permalink')) {
        $base = (string) (wc_get_page_permalink('shop') ?: '');
    }
    if ('' === $base) {
        return '';
    }

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐⭐ 1.19.350 — THE LINK NO LONGER CLEARS THE SESSION. `R9c` / `E2`,
     *      founder seal 698: *"the gate's 'Ship to your home' link must NOT
     *      clear the visit session for the whole browser without a
     *      confirmation step"*.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ THE SUPERSEDED RETURN, PRESERVED SO THE MOVEMENT IS VISIBLE:
     *
     *      return add_query_arg([$param => $token], $base);
     *
     *    where `$param` / `$token` were `bhp_visit` / `clear`. Following it
     *    called `bhp_school_visit_clear_session()` on `template_redirect`,
     *    BEFORE a single pixel rendered. VERIFIED LIVE on production
     *    2026-09-02: the parent lost hand delivery for the whole browser, the
     *    counters vanished, the pickup arrangement vanished, shipping
     *    reappeared, and nothing told them any of it had happened. Recovery
     *    meant re-scanning the flyer QR code, which most parents would not know
     *    to do.
     *
     * ⭐ THE LINK NOW ASKS. It carries `?bhp_shiphome=<product id>`, which
     *    clears NOTHING. The destination page renders a confirmation panel
     *    (`bhp_colouring_shiphome_confirm_notice()`), and only the parent's own
     *    click on that panel's confirm control carries `?bhp_visit=clear`.
     *
     * ⛔ THE CLEAR TOKEN ITSELF IS UNCHANGED AND STILL WORKS. `?bhp_visit=clear`
     *    is still handled first in `bhp_school_visit_capture_intent()`, still a
     *    plain session clear, still safe for anyone to hit, and is still what
     *    QA and Andrew's own phone use. Only the AUTOMATIC route to it is gone.
     *
     * ⭐ ONE BUILDER, SO ALL THREE SURFACES GAIN THE STEP AT ONCE — the single
     *    coloring card, the bundle card's ship-home module, and the refusal
     *    sentence. That is exactly why this file insisted on one builder.
     *
     * ⛔ IT WRITES NOTHING. No session, no cookie, no cart, no order, no
     *    product, no setting. It builds a URL.
     */
    $param = defined('BHP_COLOURING_SHIPHOME_PARAM') ? (string) BHP_COLOURING_SHIPHOME_PARAM : 'bhp_shiphome';

    return add_query_arg([$param => ($product_id > 0 ? (int) $product_id : 'shop')], $base);
}

/**
 * The query parameter that ASKS for ship-to-home. ⛔ It grants nothing and
 * clears nothing; it only makes the confirmation panel render.
 *
 * @since 1.19.350
 */
if (!defined('BHP_COLOURING_SHIPHOME_PARAM')) {
    define('BHP_COLOURING_SHIPHOME_PARAM', 'bhp_shiphome');
}

/**
 * ⭐⭐ THE CONFIRMATION STEP. `R9c` / `E2`, founder seal 698.
 *
 * ⛔ IT RENDERS ONLY WHEN THERE IS SOMETHING TO LOSE. No `?bhp_shiphome`, or a
 *    session with no live visit, means no panel: an ordinary shopper who
 *    somehow lands on this URL sees the ordinary page and nothing else.
 *
 * ⛔ NO JAVASCRIPT. Two links, both plain GETs, both idempotent. A parent on a
 *    school corridor with one bar of signal gets a working choice.
 *
 * ⭐ THE COPY SAYS WHAT IS LOST, NOT WHAT IS SWITCHED (`R9b`). The superseded
 *    note read *"Switches this browser out of school-visit pickup, so both
 *    books arrive by mail"* — accurate, and it buries the cost in the word
 *    "switches". ⛔ No "we" (§9.1). ⛔ No em dash. ⛔ No outcome claim, no
 *    figure, no urgency that is not the real deadline.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_colouring_shiphome_confirm_notice() {
    $param = BHP_COLOURING_SHIPHOME_PARAM;

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag; it grants nothing and changes nothing.
    if (empty($_GET[$param])) {
        return;
    }
    if (!function_exists('bhp_school_visit_active')) {
        return;
    }

    $record = bhp_school_visit_active();
    if (!$record) {
        return; // ⛔ Nothing to give up. No panel, no alarm.
    }

    $school = isset($record['school']) ? (string) $record['school'] : '';

    $here    = remove_query_arg($param);
    $vparam  = defined('BHP_SCHOOL_VISIT_PARAM') ? (string) BHP_SCHOOL_VISIT_PARAM : 'bhp_visit';
    $vtoken  = defined('BHP_SCHOOL_VISIT_CLEAR_TOKEN') ? (string) BHP_SCHOOL_VISIT_CLEAR_TOKEN : 'clear';
    $confirm = add_query_arg([$vparam => $vtoken], $here);
    ?>
    <section class="bhp-shiphome-confirm" role="region" aria-label="<?php esc_attr_e('Confirm how this order is delivered', 'brave-hearts'); ?>">
      <div class="bhp-shiphome-confirm__inner">
        <p class="bhp-shiphome-confirm__lead">
          <?php
          if ('' !== $school) {
              printf(
                  /* translators: %s: the school's name. */
                  esc_html__('This one is not on the signed shelf for %s.', 'brave-hearts'),
                  esc_html($school)
              );
          } else {
              esc_html_e('This one is not on the signed shelf for the school visit.', 'brave-hearts');
          }
          ?>
        </p>
        <p class="bhp-shiphome-confirm__body">
          <?php esc_html_e('Ordering it posts your whole order and ends school visit pickup in this browser. The signed, hand delivered copies would be mailed instead, and shipping would apply.', 'brave-hearts'); ?>
        </p>
        <p class="bhp-shiphome-confirm__actions">
          <a class="bhp-shiphome-confirm__keep" href="<?php echo esc_url($here); ?>"><?php esc_html_e('Keep school visit pickup', 'brave-hearts'); ?></a>
          <a class="bhp-shiphome-confirm__go" href="<?php echo esc_url($confirm); ?>" data-bhp-shiphome-confirm><?php esc_html_e('Mail my whole order instead', 'brave-hearts'); ?></a>
        </p>
      </div>
    </section>
    <?php
}
add_action('woocommerce_before_main_content', 'bhp_colouring_shiphome_confirm_notice', 6);

/**
 * The ship-home note on the SINGLE coloring card. `R9a` + `R9b`, seal 698.
 *
 * ⛔ PARITY, NOT A NEW IDEA: the bundle card has rendered a note under this
 *    control since 1.19.295 and this card rendered none. One class, one
 *    sentence, one place it is emitted from.
 *
 * ⭐ THE SENTENCE IS THE `R9b` REWRITE and it is the same wording the
 *    confirmation panel uses, so a parent reads the same fact twice in the same
 *    words rather than two paraphrases that could drift.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_colouring_shop_shiphome_note() {
    if (empty($GLOBALS['bhp_colouring_shiphome_card'])) {
        return;
    }
    if ((int) $GLOBALS['bhp_colouring_shiphome_card'] !== (int) get_the_ID()) {
        return;
    }
    unset($GLOBALS['bhp_colouring_shiphome_card']);
    ?>
    <p class="bhp-offer__shiphome-note"><?php esc_html_e('Not on the signed shelf. Ordering it posts your whole order and ends school visit pickup in this browser.', 'brave-hearts'); ?></p>
    <?php
}
/*
 * ⛔⛔ PRIORITY 15, AND 5 WAS WRONG IN A WAY ONLY THE RENDERED PAGE SHOWS.
 *
 * The flag this reads is set INSIDE `bhp_colouring_shop_add_to_cart_link()`,
 * which is a filter on `woocommerce_loop_add_to_cart_link` — and that filter
 * runs inside `woocommerce_template_loop_add_to_cart()`, which is hooked to
 * THIS action at priority 10. At priority 5 the button has not been built yet,
 * so the flag is unset and the note silently does not render.
 *
 * ⭐ OBSERVED, NOT REASONED: the flagged `/shop/` page at an asserted 1440x900
 *    on staging rendered "SHIP TO YOUR HOME" on the coloring card with NO note
 *    under it — which is exactly the `E2` defect this fix exists to remove,
 *    reproduced by the fix itself.
 *
 * ⭐ 15 ALSO PUTS THE SENTENCE WHERE IT BELONGS: under the control it explains,
 *    matching the bundle card, whose note has sat under its control since
 *    1.19.295.
 */
add_action('woocommerce_after_shop_loop_item', 'bhp_colouring_shop_shiphome_note', 15);

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.295 — THE REFUSAL SENTENCE STOPS NAMING A BLOCKED ROUTE.
 *     `CYCLE167-LD-003`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THE DEFECT: the shipped sentence ends *"you can order it separately
 *     from the shop."* ⭐ THE SAME SESSION FLAG THAT PRINTED THE REFUSAL ALSO
 *     BLOCKS IT IN THE SHOP. The remedy the sentence names does not work for
 *     the person reading it. Verified live on staging in the diagnosis pass
 *     (S6, S7, S9), not inferred.
 *
 * ⛔ THIS IS A THEME-SIDE FILTER, AND THAT IS THE WHOLE REASON NO PLUGIN BUMP
 *    IS NEEDED. `bhp_school_visit_paperback_only_message()` publishes
 *    `bhp_school_visit_paperback_only_message` as a public filter. All five
 *    seams print through that one function, so one filter reaches all five and
 *    `brave-hearts-bundle-pricing` stays at 1.8.74, byte-untouched.
 *
 * ⛔ WHY IT LIVES IN THIS FILE rather than a new one: a new include needs a
 *    `require` line in `functions.php`, which is shared ground carrying two
 *    other workstreams' uncommitted work this cycle. The colouring book is
 *    also the refusal this sentence most often prints for.
 *
 * ⛔ THE EDIT IS MINIMAL AND ADDS NO CLAIM. Sentences one and two are
 *    BYTE-IDENTICAL to the plugin's, deliberately:
 *      · "chapter paperbacks only" is asserted by the plugin's own suite
 *        (`tests/test-visit-colouring-gate.php` §6). Rewording it would break
 *        a passing test to fix a different sentence.
 *      · it is a string Andrew has seen and approved. Only the FALSE clause
 *        moves.
 *    Sentence three drops "from the shop" and names the route that works.
 *
 * ⛔ RAILS: §9.1 voice is Andrew's own I/me (his sentence, his mouth) ·
 *    ⛔ no em dash · ⛔ no outcome claim · ⛔ no apology padding ·
 *    ⛔ "coloring", American, matching the book's own cover ·
 *    ⛔ no school name, date or slug, so it stays true for every visit.
 *
 * ⚠️ THE ANCHOR SURVIVES THE NOTICE LAYER. All five consumers route into
 *    `wc_add_notice( …, 'error' )`, which WooCommerce prints through
 *    `wc_kses_notice()` — an allowlist that includes `<a href>`. ⭐ VERIFIED
 *    RENDERED ON STAGING, not read from source, in this pass's QA.
 *
 * ✅ FAILS SAFE: no resolvable URL → the plugin's own sentence is returned
 *    untouched. A refusal notice always prints something true.
 *
 * @param string $message The plugin's customer-facing sentence.
 * @return string
 */
function bhp_colouring_visit_refusal_message($message) {
    $url = bhp_colouring_ship_home_url(0);
    if ('' === $url) {
        return $message;
    }

    return sprintf(
        /* translators: %s is a link to the shop with school-visit pickup switched off. */
        __('I can only bring the chapter paperbacks to the school visit, so signed copies are chapter paperbacks only. Please choose a chapter paperback. If you would like a hardcover or a coloring book as well, you can %s. That switches this browser out of visit pickup, so those books arrive by mail instead of being handed over at the visit.', 'brave-hearts'),
        sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url($url),
            esc_html__('order it shipped to your home', 'brave-hearts')
        )
    );
}
add_filter('bhp_school_visit_paperback_only_message', 'bhp_colouring_visit_refusal_message', 10, 1);

/* ═══════════════════════════════════════════════════════════════════════════
 * THE OFFER SURFACE — `FD-579`'s "same thing we offer for the collection"
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⭐⭐ 1.19.284 — THE COMPOSITE SLUG REGISTRY (carrier item 206).
 *
 * ⭐ SLUGS, NOT IDS, AND THAT IS THE WHOLE POINT. An attachment id is
 *    environment-local: staging's 4570 is not production's 4570, and a
 *    hardcoded id is how a build renders the right picture on staging and a
 *    random one — or nothing — on the live storefront. Every other approved
 *    image in this theme resolves the same way
 *    (`bhp_book_media_attachment_id()`), so this reuses that resolver rather
 *    than inventing a second one.
 *
 * ⭐ THE SLUGS ARE THE FILENAMES `design-creative` SHIPPED, prefixed. The masters are
 *    the transparent 2000x2000 PNGs at `Business OS\WORKING-DRAFTS\
 *    design-creative\bundle-composites\out\`; the builder's
 *    `README-PROVENANCE.md` records that every pixel came from a real cover
 *    file, that nothing was generated, retouched or recoloured, and why the
 *    masters are SQUARE (this store's `woocommerce_thumbnail` is a hard-cropped
 *    square, so a landscape master would have its outer books sliced off).
 *
 * ⛔ THE COLLECTION ENTRY IS NOT AN OFFER KEY. `bhp_offer_catalog()` has no
 *    three-paperback row and must not gain one — the Complete Collection is
 *    priced by the bundle plugin's own tier table, not by the offer engine.
 *    `collection` is a presentation key for the shop card only, which is why
 *    it lives in this map and nowhere near the catalogue.
 *
 * @return array<string,string> Composite key => attachment slug.
 */
function bhp_offer_composite_slugs() {
    /**
     * Composite key => attachment slug.
     *
     * @param array<string,string> $slugs
     */
    return apply_filters('bhp_offer_composite_slugs', [
        'mariana_pb_colouring' => 'bhp-bundle-composite-mariana-pb-colouring',
        'collection'           => 'bhp-bundle-composite-collection-three-paperbacks',
    ]);
}

/**
 * The composite image for an offer, if one has been produced.
 *
 * ⭐⭐ 1.19.284 — IT NOW RESOLVES. Until this release it returned 0 on purpose,
 *    because no composite existed; the two named in `bhp_offer_composite_slugs()`
 *    were registered as attachments for carrier item 206.
 *
 * ⛔ IT STILL FAILS CLOSED, AND THAT IS UNCHANGED AND NON-NEGOTIABLE. An
 *    unresolved slug returns 0, the card renders WITH NO IMAGE, and it never
 *    falls back to a component's cover — `FD-549` `R2.3`. A chapter-book cover
 *    beside a bundle price states that THAT BOOK costs the bundle price.
 *
 * ⛔ THE HARDCOVER UPSELL HAS NO ENTRY AND MUST NOT GET ONE. It is a format
 *    swap inside the paperback offer, not its own card, so it never asks for
 *    an image. If it ever did, the paperback composite would be the wrong
 *    picture for it.
 *
 * @param string $key Offer key, or a presentation key from the slug registry.
 * @return int Attachment id, or 0.
 */
function bhp_offer_composite_attachment_id($key) {
    $slugs      = bhp_offer_composite_slugs();
    $attachment = 0;

    if (isset($slugs[$key]) && function_exists('bhp_book_media_attachment_id')) {
        $attachment = (int) bhp_book_media_attachment_id($slugs[$key]);
    }

    /**
     * Attachment id of the composite image for this offer.
     *
     * ⛔ IT MUST BE A COMPOSITE OF EVERY COMPONENT. A single component's cover
     *    here would violate `FD-549` at the one place the rule exists to
     *    protect.
     *
     * @param int    $attachment_id 0 when no composite resolves.
     * @param string $key           Offer key.
     */
    return (int) apply_filters('bhp_offer_composite_attachment_id', $attachment, $key);
}

/**
 * The composite, as a shop-card image, or '' when it does not resolve.
 *
 * ⭐ IT IS THE SAME `woocommerce_thumbnail` SIZE EVERY OTHER CARD IN THE GRID
 *    USES, so the two bundle cards sit in the 2-up track at the same geometry
 *    as a chapter-book card rather than at a size of their own.
 *
 * ⛔ EMPTY STRING, NOT A PLACEHOLDER. `R2.3` degrade-never-mix: no image is a
 *    valid card; a wrong image is not.
 *
 * @param string $key Composite key.
 * @return string HTML, or ''.
 */
function bhp_offer_composite_card_image($key) {
    $attachment = bhp_offer_composite_attachment_id($key);
    if (!$attachment) {
        return '';
    }
    return wp_get_attachment_image($attachment, 'woocommerce_thumbnail', false, [
        'class' => 'bhp-offer__composite',
    ]);
}

/**
 * Render one offer as a purchase control.
 *
 * ⛔ NO NEW COMMERCE MECHANISM. Same `form.bhp-bundle-form`, same plugin nonce
 *    (`bhp_bundle_add`), same allowlisted "finish on /checkout/" flag every
 *    collection CTA has posted since P2-5. Only the action value is new, and
 *    the plugin validates it against `bhp_offer_catalog()` before it reaches
 *    any product lookup.
 *
 * ⛔ NO JAVASCRIPT IS REQUIRED. The paperback offer and the hardcover upsell
 *    are two real forms, each fully server-rendered. `FD-558` (simple), and a
 *    purchase control that depends on a script is a purchase control that can
 *    be blocked — the defect 1.19.274's own commit message records.
 *
 * ⛔ PAPERBACK IS THE DEFAULT AND NO SECOND DEFAULT IS INTRODUCED (`FD-439`).
 *
 * @param string $key          Offer key (the paperback offer).
 * @param string $class        Extra wrapper classes.
 * @param bool   $show_heading FALSE inside a shop card, which already carries
 *                             its own `<h2>` and descriptor. ⛔ Printing the
 *                             heading there too would say the same thing twice
 *                             in one tile — the duplication the first staging
 *                             DOM read of this card found.
 * @param bool   $card         ⭐ 1.19.284 / carrier item 206 — SHOP-CARD MODE.
 *                             TRUE makes this render as the purchase half of a
 *                             PRODUCT-STYLE card: ⛔ no media (the caller hoists
 *                             the composite above the `<h2>`, where every other
 *                             card in the grid carries its picture), ⭐ a
 *                             labelled price line in the grid's own
 *                             `.bhp-shop-format-prices` markup, and a CTA that
 *                             does NOT restate the figure.
 *                             ⛔ THE PRODUCT-PAGE MODE IS BYTE-FOR-BYTE WHAT IT
 *                                WAS. One template, two modes — a second
 *                                template would be a second place for a
 *                                protected element to go missing (spec §7).
 * @return string HTML, or '' when the offer cannot be bought right now.
 */
function bhp_offer_render_module($key, $class = '', $show_heading = true, $card = false) {
    /*
     * ⛔ 1.19.288 — THE GATE MOVED FROM `bhp_offer_is_purchasable()` TO
     *    `bhp_offer_is_offerable()`, AND THIS IS THE ONE PLACE THE PAIR CARD
     *    IS ACTUALLY BUILT — the product-page cross-sell and the shop card
     *    both come through here, so gating here closes both at once.
     *
     * ⭐ `R1.4` IS UNCHANGED AND IS NOW STRICTER: nothing is advertised that
     *    cannot be bought, and on a visit-flagged session the pair cannot be
     *    bought (the colouring half is refused server-side by all five seams).
     * ⛔ CONTROL PATH: for every ordinary shopper `bhp_offer_is_offerable()`
     *    returns exactly what `bhp_offer_is_purchasable()` returns.
     * ✅ FAILS OPEN to the old predicate if the plugin is older than 1.8.69.
     */
    if (function_exists('bhp_offer_is_offerable')) {
        if (!bhp_offer_is_offerable($key)) {
            return '';
        }
    } elseif (!function_exists('bhp_offer_is_purchasable') || !bhp_offer_is_purchasable($key)) {
        return ''; // ⛔ R1.4: nothing is advertised that cannot be bought.
    }
    if (!function_exists('bhp_bundle_nonce_input') || !function_exists('bhp_bundle_checkout_redirect_input')) {
        return ''; // Plugin off: render nothing rather than a dead button.
    }

    $price  = bhp_offer_price($key);
    $saving = bhp_offer_saving($key);
    if (null === $price) {
        return '';
    }

    // ⭐ The hardcover swap, only if IT is purchasable too and names this offer.
    $upsell_key = '';
    foreach (bhp_offer_catalog() as $candidate => $offer) {
        // ⛔ 1.19.288 — `is_offerable`, so the HARDCOVER swap cannot appear on a
        //    visit-flagged session even if the parent offer somehow rendered.
        $bhp_upsell_ok = function_exists('bhp_offer_is_offerable')
            ? bhp_offer_is_offerable($candidate)
            : bhp_offer_is_purchasable($candidate);
        if (!empty($offer['upsell_of']) && $offer['upsell_of'] === $key && $bhp_upsell_ok) {
            $upsell_key = $candidate;
            break;
        }
    }

    $composite = $card ? 0 : bhp_offer_composite_attachment_id($key);

    ob_start();
    ?>
    <div class="bhp-offer <?php echo esc_attr($class); ?>" data-bhp-card-kind="bundle" data-bhp-offer="<?php echo esc_attr($key); ?>">
      <?php
      /*
       * ⛔ R2.3 — DEGRADE, NEVER MIX. No composite means NO IMAGE. It never
       *    falls back to one component's cover.
       * ⭐ 1.19.284: in CARD mode `$composite` is forced to 0 here because the
       *    picture has already been rendered by the caller, ABOVE the `<h2>`,
       *    in the slot every other card in the grid uses. Rendering it here too
       *    would put the same composite on one tile twice.
       */
      if ($composite) :
        ?><div class="bhp-offer__media"><?php echo wp_get_attachment_image($composite, 'woocommerce_thumbnail', false, ['class' => 'bhp-offer__composite']); ?></div><?php
      endif;
      ?>
      <?php if ($show_heading) : ?>
        <p class="bhp-offer__heading"><?php echo esc_html(bhp_colouring_draft_copy('offer_heading')); ?></p>
      <?php endif; ?>

      <?php if ($card) : ?>
        <?php
        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.284 — THE PRICE, AS ITS OWN LINE. CARRIER ITEM 206.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐ HIS CARD CONTRACT IS "image · title · price · CTA", so the price
         *    is an ELEMENT, not a phrase inside a button. It is emitted in the
         *    grid's OWN `.bhp-shop-from-price.bhp-shop-format-prices` markup —
         *    the identical classes `bhp_book_shop_card_meta()` and
         *    `bhp_colouring_shop_card_meta()` use — so the bundle card's price
         *    sits at the same size, weight and baseline as the price on the
         *    card beside it. ⛔ A bespoke price element here would be a second
         *    way of printing money on one grid.
         *
         * ⛔ R2.2 STILL HOLDS AND IS THE REASON THIS IS SAFE: the figure is
         *    `bhp_offer_price()`'s, resolved live from the engine every render.
         *    ⛔ No price literal exists anywhere in this file.
         *
         * ⛔ AND R2.6 — ONE PRICE, ONCE. The card CTA below deliberately does
         *    NOT carry the figure, because a labelled price line plus
         *    "ADD BOTH FOR $22.99" is the same number twice on a 172px tile,
         *    which is the exact duplicate-price defect `R2.6` already removed
         *    from the colouring card.
         *
         * ⭐ FD-549 HOLDS BY CONSTRUCTION: this figure is the price of the
         *    OFFER, and the picture above the `<h2>` is the composite of that
         *    same offer's components. Image and price describe one object.
         */
        ?>
        <span class="bhp-shop-from-price bhp-shop-format-prices">
          <span class="bhp-shop-format-price">
            <span class="bhp-shop-format-price__label"><?php echo esc_html(bhp_colouring_draft_copy('offer_card_price_label')); ?></span>
            <span class="bhp-shop-format-price__amount"><?php echo wp_kses_post(wc_price($price)); ?></span>
          </span>
        </span>
      <?php endif; ?>

      <?php
      /*
       * ═══════════════════════════════════════════════════════════════════════
       * ⭐⭐⭐ 1.19.286 — ITEMS 210 + 211 ON THE PAIR CARD. THREE MOVES, NAMED.
       * ═══════════════════════════════════════════════════════════════════════
       *
       * ⭐ ALL THREE ARE GATED ON `$card`, so the PRODUCT-PAGE cross-sell is
       *    BYTE-FOR-BYTE what it was in 1.19.285 — same label, same
       *    `bhp_bundle_redirect=checkout` field, same order, same straight-to-
       *    checkout path the founder walked himself.
       *
       * 1. THE LABEL comes from `offer_card_cta`, which now defers to the one
       *    shop-card label (item 211). ⭐ It is also the overflow fix: "GET
       *    BOTH" was photographed breaking its card on his desktop.
       *
       * 2. THE DESTINATION. ⛔ `bhp_bundle_checkout_redirect_input()` is
       *    OMITTED IN CARD MODE and printed in every other mode. On a shop
       *    card the pair adds and the SIDE PANEL takes it from there (item
       *    210); on the product page it still finishes on /checkout/.
       *
       * ⛔⛔ AND ONE THING THE OMISSION ALONE COULD NOT DO, STATED PLAINLY
       *     RATHER THAN ASSUMED: `offer_*` is NOT one of the three action
       *     families `bundle-drawer.js` implements. Its capability test
       *     (`/^(complete_|single_|any2_)/`, plugin 1.8.62) deliberately
       *     returns BEFORE `preventDefault()`, so an offer form has always
       *     done a real POST and the server has always redirected it. Dropping
       *     the checkout field on its own would therefore have landed this
       *     card on the CART PAGE — the exact surface item 186 objected to.
       *     ⭐ SO `data-bhp-offer-panel` IS AN EXPLICIT OPT-IN, read by
       *     `interceptOfferForms()` in plugin 1.8.67, and it is emitted on the
       *     SHOP CARD ONLY. ⛔ An attribute rather than a class, because the
       *     capability test is the plugin's contract and widening it to claim
       *     every `offer_*` form on the site would silently re-route the
       *     product page too.
       *
       * 3. THE ORDER. In card mode the saving line and the hardcover swap
       *    render ABOVE the CTA so the CTA is the last child and
       *    `margin-top:auto` can pin it to the card floor — the one baseline
       *    all six buttons share. ⛔ DOM ORDER, NOT a CSS `order` property: a
       *    visual reorder that leaves focus order behind is an accessibility
       *    defect on a control a keyboard reaches. ⛔ NOTHING IS REMOVED — the
       *    saving is the same recomputed figure and the swap is the same real
       *    form; they moved, they did not go.
       */
      $bhp_offer_saving_html = '';
      if (null !== $saving) {
          /*
           * ⛔ A DERIVED CLAIM, RECOMPUTED EVERY RENDER. `evidence-verification`
           *    §5: the saving is the difference between what the components cost
           *    in WooCommerce TODAY and Andrew's offer price. It is never
           *    inherited from a draft and never stored.
           */
          $bhp_offer_saving_html = '<p class="bhp-offer__saving">'
              . esc_html(bhp_colouring_draft_copy('offer_saving', [wp_strip_all_tags(wc_price($saving))]))
              . '</p>';
      }

      $bhp_offer_upsell_html = '';
      if ($upsell_key) {
          $upsell_price = bhp_offer_price($upsell_key);
          /*
           * ⭐ HIS LIMB: "with the upsell of HC". A FORMAT SWAP under an offer
           *    already chosen (spec §6.4) — ⛔ not an interstitial, ⛔ not a
           *    post-purchase one-click.
           */
          ob_start();
          ?>
          <?php
          /*
           * ⛔ THE SWAP TAKES THE SAME ROUTE AS THE CTA ABOVE IT, and that is not
           *    tidiness — it is the whole point. Two controls on one card that
           *    finish in two different places (one in the panel, one on
           *    /checkout/) is a card that behaves differently depending on which
           *    button the shopper happens to press. ⭐ So in CARD mode the swap
           *    carries the same `data-bhp-offer-panel` opt-in and the same
           *    no-JavaScript floor field; on the PRODUCT PAGE it still posts the
           *    checkout redirect, exactly as it did.
           */
          ?>
          <form class="bhp-offer__form bhp-offer__form--upsell" method="post"<?php echo $card ? ' data-bhp-offer-panel="' . esc_attr($upsell_key) . '"' : ''; ?>>
            <?php bhp_bundle_nonce_input(); ?>
            <input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr('offer_' . $upsell_key); ?>" />
            <?php if ($card) : ?>
              <input type="hidden" name="bhp_offer_panel" value="1" />
            <?php else : ?>
              <?php bhp_bundle_checkout_redirect_input(); ?>
            <?php endif; ?>
            <button type="submit" class="bhp-offer__upsell">
              <?php
              /*
               * ⭐⭐ 1.19.350 — ON A CARD THE SWAP NAMES WHAT IT BUYS.
               *
               * ⛔ THE BRIEF IS EXPLICIT AND IT IS A FINANCE CORRECTION, NOT A
               *    STYLE NOTE (`finance-analytics`, seal 690): the two offers
               *    must read
               *    "book + coloring book $22.99" and "hardcover + coloring book
               *    $28.99", and MUST NOT read "paperback pair" / "hardcover
               *    pair" — because two PAPERBACKS save $0.99, not $1.99, and a
               *    label saying "pair" invites the reader to price the wrong
               *    pair. ⚠️ The founder-approved concept of record draws
               *    "PAPERBACK PAIR" / "HARDCOVER PAIR" on these two chips; the
               *    brief supersedes the mock on this one point and the brief is
               *    followed. Recorded in the build report, not silently chosen.
               *
               * ⛔ THE PRODUCT-PAGE STRING IS UNTOUCHED. `offer_upsell`
               *    ("Prefer the hardcover? %s") is still what the product page
               *    renders, byte for byte. Only the card takes the new one.
               *
               * ⛔ THE FIGURE IS STILL THE ENGINE'S, never a literal.
               */
              echo esc_html(bhp_colouring_draft_copy(
                  $card ? 'offer_card_upsell' : 'offer_upsell',
                  [wp_strip_all_tags(wc_price($upsell_price))]
              ));
              ?>
            </button>
          </form>
          <?php
          $bhp_offer_upsell_html = ob_get_clean();
      }
      ?>

      <?php if ($card) : ?>
        <?php echo $bhp_offer_saving_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source. ?>
        <?php echo $bhp_offer_upsell_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source. ?>
      <?php endif; ?>

      <form class="bhp-offer__form<?php echo $card ? ' bhp-offer__form--card' : ''; ?>" method="post"<?php echo $card ? ' data-bhp-offer-panel="' . esc_attr($key) . '"' : ''; ?>>
        <?php bhp_bundle_nonce_input(); ?>
        <input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr('offer_' . $key); ?>" />
        <?php if ($card) : ?>
          <?php
          /*
           * ⛔ THE NO-JAVASCRIPT FLOOR FOR THIS CARD, AND IT IS NOT THE CART
           *    PAGE. `bhp_offer_panel` is read by the plugin's own POST handler
           *    and resolves to the OFFER'S OWN PRODUCT PAGE — a real page with
           *    the item in the cart, the panel closed, and WooCommerce's own
           *    "added to cart" notice. ⛔ Still not a URL and still not
           *    customer-controlled: the handler compares it to one literal and
           *    builds the destination itself.
           */
          ?>
          <input type="hidden" name="bhp_offer_panel" value="1" />
        <?php else : ?>
          <?php bhp_bundle_checkout_redirect_input(); ?>
        <?php endif; ?>
        <button type="submit" class="button bhp-offer__cta<?php echo $card && defined('BHP_SHOP_ATC_CLASS') ? ' ' . esc_attr(BHP_SHOP_ATC_CLASS) : ''; ?>">
          <?php
          /* ⛔ R2.2: the figure is the ENGINE's, never a literal in this template. */
          echo esc_html(
              $card
                  ? bhp_colouring_draft_copy('offer_card_cta')
                  : bhp_colouring_draft_copy('offer_cta', [wp_strip_all_tags(wc_price($price))])
          );
          ?>
        </button>
      </form>

      <?php if (!$card) : ?>
        <?php echo $bhp_offer_saving_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source. ?>
        <?php echo $bhp_offer_upsell_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source. ?>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Which offer, if any, belongs on the product page being viewed?
 *
 * ⭐ AN OFFER IS SHOWN ON A PAGE ONLY WHEN THAT PAGE'S PRODUCT IS ONE OF ITS
 *    COMPONENTS. A Mariana offer never appears on an Everest page.
 *
 * @return string Offer key, or ''.
 */
function bhp_offer_for_current_product() {
    if (!function_exists('is_product') || !is_product() || !function_exists('bhp_offer_catalog')) {
        return '';
    }
    $product_id = (int) get_queried_object_id();

    foreach (bhp_offer_catalog() as $key => $offer) {
        // ⛔ The PAPERBACK offer leads; the hardcover one rides inside it.
        if (!empty($offer['upsell_of'])) {
            continue;
        }
        if (!bhp_offer_is_purchasable($key)) {
            continue;
        }
        $components = bhp_offer_components($key);
        foreach ((array) $components as $component) {
            if ((int) $component['product_id'] === $product_id || (int) $component['buy_id'] === $product_id) {
                return $key;
            }
        }
    }
    return '';
}

/**
 * The offer, as a cross-sell below the purchase interface.
 *
 * Priority 35 — after the rail (15) and the spec line (25), before the kit CTA
 * (40). ⭐ Slot 9 of the spec's page order: the shopper has met the price and
 * the ADD TO CART before being offered a second thing to buy.
 */
function bhp_offer_render_product_cross_sell() {
    $key = bhp_offer_for_current_product();
    if ('' === $key) {
        return;
    }
    echo bhp_offer_render_module($key, 'bhp-offer--product'); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source.
}
add_action('woocommerce_single_product_summary', 'bhp_offer_render_product_cross_sell', 35);

/**
 * Offer cards in the shop grid, injected before the closing `</ul>`.
 *
 * ⛔ ONE CARD PER PURCHASABLE OFFER, AND ZERO FOR ANYTHING ELSE (`R1.1`,
 *    `R1.4`). The gate is `bhp_offer_is_purchasable()` — structural, not a
 *    hardcoded list. ⭐ The three gated offers therefore produce no card at
 *    all today, and will produce one the day their books exist, with no code
 *    change and not one hour before.
 */
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.295 — THE SHOP CARD'S SHIP-TO-HOME CONTROL. `CYCLE167-LD-001`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The `/shop/` twin of the `/read-aloud/` link mode: everything the cart card
 * shows EXCEPT the ability to buy, plus one honest line about what following
 * the link costs.
 *
 * ⛔ IT RENDERS ONLY FOR A SESSION THE VISIT GATE REFUSED. `purchasable` is
 *    catalogue truth, `offerable` is that plus the session; only the gap
 *    between them is a session refusal with a remedy. Any other reason for a
 *    missing module returns '' here and the caller leaves the card absent.
 *    ⛔ CONTROL PATH: '' for every ordinary shopper.
 *
 * ⛔ NO FORM, NO NONCE, NO `data-bhp-cart-add`, NO `add-to-cart`. FD-642 is a
 *    structural property of this markup, not a check inside it.
 *
 * ⛔ THE PRICE IS `bhp_offer_price()`'s, in the GRID'S OWN price markup, so it
 *    sits at the same size and baseline as every neighbouring card. No literal
 *    anywhere. ⛔ The label is the engine's `offer_card_price_label`, because
 *    an unlabelled figure beside a two-book composite is the `FD-549`
 *    ambiguity the rail contract exists to close.
 *
 * ⛔ AMERICAN SPELLING · no "we/us/our" · no em dash · no outcome claim ·
 *    no school name, date, grade or slug.
 *
 * @param string $key Offer key.
 * @return string Markup, or '' when this is not a session refusal.
 */
function bhp_offer_shop_shiphome_module($key) {
    if (!function_exists('bhp_offer_is_purchasable') || !function_exists('bhp_offer_is_offerable')) {
        return '';
    }

    try {
        if (!bhp_offer_is_purchasable($key) || bhp_offer_is_offerable($key)) {
            return '';
        }
    } catch (Throwable $e) {
        return ''; // FAIL CLOSED: an unexplained refusal is not advertised.
    }

    $url = bhp_colouring_ship_home_url(0);
    if ('' === $url) {
        return '';
    }

    $price = function_exists('bhp_offer_price') ? bhp_offer_price($key) : null;
    if (null === $price || !function_exists('wc_price')) {
        return '';
    }

    $label = function_exists('bhp_colouring_draft_copy')
        ? (string) bhp_colouring_draft_copy('offer_card_price_label')
        : '';

    return '<div class="bhp-offer bhp-offer--card bhp-offer--shiphome" data-bhp-offer="' . esc_attr($key) . '">'
        . '<span class="bhp-shop-from-price bhp-shop-format-prices">'
        . '<span class="bhp-shop-format-price">'
        . ('' !== $label ? '<span class="bhp-shop-format-price__label">' . esc_html($label) . '</span>' : '')
        . '<span class="bhp-shop-format-price__amount">' . wp_kses_post(wc_price($price)) . '</span>'
        . '</span></span>'
        /* ⛔ NO `button` CLASS — see the note on the standalone card above. */
        . '<a href="' . esc_url($url) . '" class="'
        . esc_attr(defined('BHP_SHOP_ATC_CLASS') ? BHP_SHOP_ATC_CLASS : 'bhp-shop-atc')
        . ' bhp-shop-atc--shiphome" data-bhp-shiphome>'
        . esc_html__('Ship to your home', 'brave-hearts')
        . '</a>'
        /*
         * ⛔ The honest half. The control never renders without it.
         *
         * ⭐⭐ 1.19.350 — `R9b`: IT NOW SAYS WHAT IS LOST, NOT WHAT IS SWITCHED.
         *    ⛔ THE SUPERSEDED SENTENCE, PRESERVED: *"Switches this browser out
         *    of school-visit pickup, so both books arrive by mail."* Accurate,
         *    and it buries the cost in the word "switches" — a parent reads it
         *    as a delivery preference rather than as giving up the signed,
         *    hand-delivered copies. ⭐ The replacement is the SAME WORDING the
         *    single coloring card and the confirmation panel now use, so all
         *    three state one fact in one form.
         */
        . '<p class="bhp-offer__shiphome-note">'
        . esc_html__('Not on the signed shelf. Ordering it posts your whole order and ends school visit pickup in this browser.', 'brave-hearts')
        . '</p>'
        . '</div>';
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.350 — THE BUNDLE CARDS BUILD HERE AND RENDER BELOW THE GRID.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE MARKUP IS UNCHANGED, BYTE FOR BYTE, AND THAT IS THE WHOLE DESIGN OF
 *    THIS REFACTOR. `bhp_offer_shop_cards()` below used to inline this loop and
 *    prepend the result to `woocommerce_product_loop_end`. It now calls this,
 *    and so does the strip renderer. ⛔ Nothing about how a bundle card is
 *    BUILT moved — the price, the recomputed saving, the hardcover swap, the
 *    ship-home degrade and all five `FD-642` refusal seams are the same code
 *    they were in 1.19.349. Only where the `<li>` is printed has changed.
 *
 * ⛔ WHY IT MOVED (brief `CYCLE179-LD-350`, founder concept seal 686): a bundle
 *    is not a title. Two bundle cards inside a five-up product row make the row
 *    seven wide and push the five actual books off the fold, and a reader
 *    cannot tell a set from a book at a glance. The strip states the pair under
 *    the row, where a reader who has just seen the components can price it.
 *
 * @since 1.19.350
 * @return string Zero or more `<li class="product bhp-shop-offer-item">`.
 */
function bhp_offer_shop_card_items() {
    if (is_admin() || !function_exists('bhp_offer_catalog')) {
        return '';
    }

    $out = '';
    foreach (bhp_offer_catalog() as $key => $offer) {
        if (!empty($offer['upsell_of'])) {
            continue; // The upsell is a swap inside its parent, never its own card.
        }
        if (!bhp_offer_is_purchasable($key)) {
            continue;
        }
        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.295 — LINK MODE ON THE SHOP CARD TOO. `CYCLE167-LD-001`.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⛔ THE SAME DEFECT LIVED HERE, and `/shop/` is where the founder's
         *    complaint bites second: on a visit-flagged session the module
         *    returned '' and `continue` DELETED THE WHOLE BUNDLE CARD from the
         *    grid. The parent saw the chapter books and the coloring book and
         *    no way to get both, with nothing to explain the absence.
         *
         * ⛔ TWO ''-CAUSES, TWO ANSWERS, EXACTLY AS ON `/read-aloud/`.
         *    `!purchasable` is already handled above and still `continue`s.
         *    Reaching here with '' therefore means the SESSION was refused,
         *    and that has a remedy the parent can choose.
         *
         * ⛔ FD-642 PRESERVED BY CONSTRUCTION: the ship-home module emits no
         *    form, no nonce and no add control. Nothing on this card can put a
         *    coloring book into a hand-delivery cart.
         */
        $module = bhp_offer_render_module($key, 'bhp-offer--card', false, true);
        if ('' === $module) {
            $module = bhp_offer_shop_shiphome_module($key);
            if ('' === $module) {
                continue; // Nothing honest to show → the card stays absent.
            }
        }
        /*
         * ⭐ THE CARD CARRIES AN `<h2>` IN THE SAME CLASS EVERY OTHER CARD IN
         *    THE GRID USES. ⛔ Without it the bundle is the one tile with no
         *    heading — invisible to a heading-navigating screen-reader user,
         *    and visually the odd one out in a row of titled cards.
         *
         * ⛔ THE TITLE AND THE DESCRIPTOR ARE DIFFERENT SENTENCES. Rendering
         *    the same words twice (the defect the first staging read of this
         *    card found) says nothing twice and reads as a duplication bug.
         *
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.284 — THE COMPOSITE GOES ABOVE THE `<h2>`. CARRIER ITEM 206.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐ THAT POSITION IS THE WHOLE POINT OF "PRODUCT-STYLE". WooCommerce's
         *    own card is image → title → meta → CTA, and the shop CSS sizes the
         *    picture off `li.product img`. Leaving the composite inside
         *    `.bhp-offer` — below the title, where 1.19.283 put it — would have
         *    produced a tile shaped unlike every tile beside it, which is the
         *    "reads as a broken tile" failure the 1.19.283 full-row rule was
         *    working around.
         *
         * ⛔ AND IT DEGRADES, IT DOES NOT SUBSTITUTE (`FD-549` `R2.3`). When the
         *    slug does not resolve on this environment `bhp_offer_composite_
         *    card_image()` returns '' and the card renders TITLE-FIRST with no
         *    picture. ⛔ It never borrows a component's cover. A chapter-book
         *    cover beside $22.99 states that that book costs $22.99.
         */
        $out .= '<li class="product bhp-shop-offer-item" data-bhp-card-kind="bundle">'
            . bhp_offer_composite_card_image($key)
            . '<h2 class="woocommerce-loop-product__title">' . esc_html(bhp_colouring_draft_copy('offer_card_title')) . '</h2>'
            . '<p class="bhp-shop-descriptor">' . esc_html(bhp_colouring_draft_copy('offer_descriptor')) . '</p>'
            . $module
            . '</li>';
    }

    return $out;
}

/**
 * The 1.19.349 injection point, retained for every surface that is NOT a
 * catalog grid.
 *
 * ⛔ ON A CATALOG GRID IT NOW RETURNS `$loop_end` UNTOUCHED and the cards are
 *    rendered by `bhp_offer_catalog_bundle_strip()` on
 *    `woocommerce_after_shop_loop` instead. ⭐ Two renderers can never both
 *    fire, because each tests the same predicate in opposite directions.
 *
 * ⭐ THE `is_shop()` GATE IS DELIBERATELY NOT WIDENED. A bundle strip belongs
 *    under the full catalog, not under a two-product category term where the
 *    pair's components may not both be present.
 */
function bhp_offer_shop_cards($loop_end) {
    if (is_admin() || !function_exists('is_shop') || !is_shop()) {
        return $loop_end;
    }
    if (function_exists('bhp_catalog_grid_context') && bhp_catalog_grid_context()) {
        return $loop_end; // ⭐ 1.19.350: the strip owns these cards now.
    }
    return bhp_offer_shop_card_items() . $loop_end;
}

/**
 * ⭐⭐ THE BUNDLE STRIP — one row, under the grid, above the trust strip.
 *
 * ⛔ IT IS A `<ul class="products">` BECAUSE THE CARDS ARE `<li>`. Reusing the
 *    existing card markup unchanged is what makes this a RELOCATION rather than
 *    a rewrite; the price of that is that the container has to be a list. It
 *    carries its own modifier class so no grid rule reaches it.
 *
 * ⛔ IT RENDERS NOTHING WHEN THERE IS NOTHING BUYABLE. `bhp_offer_shop_card_
 *    items()` already returns '' for an offer that is not purchasable, and an
 *    empty strip is not emitted at all.
 *
 * ⭐ PRIORITY 20, AHEAD OF THE TRUST STRIP AT 30. A reader meets the offer
 *    while the products are still in view, and the proof comes last.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_offer_catalog_bundle_strip() {
    if (!function_exists('bhp_catalog_grid_context') || !bhp_catalog_grid_context()) {
        return;
    }
    if (!function_exists('is_shop') || !is_shop()) {
        return; // ⛔ The full catalog only. See the note on the gate above.
    }

    $items = bhp_offer_shop_card_items();
    if ('' === trim($items)) {
        return;
    }
    ?>
    <section class="bhp-catalog-bundle-strip" aria-label="<?php esc_attr_e('Book and coloring book together', 'brave-hearts'); ?>">
      <ul class="products bhp-catalog-bundle-strip__list">
        <?php echo $items; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source in bhp_offer_shop_card_items(). ?>
      </ul>
    </section>
    <?php
}
add_action('woocommerce_after_shop_loop', 'bhp_offer_catalog_bundle_strip', 20);
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️ PRIORITY 15, AND THE REASON IS COUNTER-INTUITIVE ENOUGH TO WRITE DOWN.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Every filter on `woocommerce_product_loop_end` PREPENDS its markup to
 * `$loop_end`. So the LAST filter to run puts its card FIRST.
 *
 * ⛔ THIS SHIPPED AS PRIORITY 5 AND WAS WRONG, AND IT WAS CAUGHT BY READING
 *    THE SERVED DOM ON STAGING RATHER THAN BY REASONING ABOUT THE HOOK: at 5
 *    the offer ran BEFORE `bhp_book_shop_collection_card()` (default 10),
 *    which then prepended the collection card in front of it — so the grid
 *    rendered the Complete Collection and THEN the bundle, the exact reverse
 *    of the intended order.
 *
 * ⭐ AT 15 the collection card is built first and the offer is prepended in
 *    front of it, so the grid reads: three chapter books, the colouring book,
 *    the bundle, the Complete Collection. ⛔ A collection is only legible once
 *    you know what it collects (spec §3.4), so it stays last.
 */
add_filter('woocommerce_product_loop_end', 'bhp_offer_shop_cards', 15);
