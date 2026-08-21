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
 * ⚠️ NO BUNDLE COMPOSITE EXISTS TODAY. It is Legolas's (`design-creative`)
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
        // ⭐ His own cover's wording. Ages 6-9. No outcome claim.
        'spec_line'       => __('%1$d coloring adventures · %2$d pages · %3$s · ages 6-9', 'brave-hearts'),
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
        'offer_card_cta'         => __('GET BOTH', 'brave-hearts'),
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
    <span class="bhp-shop-from-price bhp-shop-format-prices">
      <span class="bhp-shop-format-price">
        <span class="bhp-shop-format-price__label"><?php echo esc_html(bhp_colouring_draft_copy('rail_card_name')); ?></span>
        <span class="bhp-shop-format-price__amount"><?php echo wp_kses_post(wc_price((float) $product->get_price())); ?></span>
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
 * ⭐ THE SLUGS ARE THE FILENAMES LEGOLAS SHIPPED, prefixed. The masters are
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
    if (!function_exists('bhp_offer_is_purchasable') || !bhp_offer_is_purchasable($key)) {
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
        if (!empty($offer['upsell_of']) && $offer['upsell_of'] === $key && bhp_offer_is_purchasable($candidate)) {
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

      <form class="bhp-offer__form" method="post">
        <?php bhp_bundle_nonce_input(); ?>
        <input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr('offer_' . $key); ?>" />
        <?php bhp_bundle_checkout_redirect_input(); ?>
        <button type="submit" class="button bhp-offer__cta">
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

      <?php if (null !== $saving) : ?>
        <?php
        /*
         * ⛔ A DERIVED CLAIM, RECOMPUTED EVERY RENDER. `evidence-verification`
         *    §5: the saving is the difference between what the components cost
         *    in WooCommerce TODAY and Andrew's offer price. It is never
         *    inherited from a draft and never stored.
         */
        ?>
        <p class="bhp-offer__saving"><?php echo esc_html(bhp_colouring_draft_copy('offer_saving', [wp_strip_all_tags(wc_price($saving))])); ?></p>
      <?php endif; ?>

      <?php if ($upsell_key) : $upsell_price = bhp_offer_price($upsell_key); ?>
        <?php
        /*
         * ⭐ HIS LIMB: "with the upsell of HC". A FORMAT SWAP under an offer
         *    already chosen (spec §6.4) — ⛔ not an interstitial, ⛔ not a
         *    post-purchase one-click.
         */
        ?>
        <form class="bhp-offer__form bhp-offer__form--upsell" method="post">
          <?php bhp_bundle_nonce_input(); ?>
          <input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr('offer_' . $upsell_key); ?>" />
          <?php bhp_bundle_checkout_redirect_input(); ?>
          <button type="submit" class="bhp-offer__upsell">
            <?php echo esc_html(bhp_colouring_draft_copy('offer_upsell', [wp_strip_all_tags(wc_price($upsell_price))])); ?>
          </button>
        </form>
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
function bhp_offer_shop_cards($loop_end) {
    if (is_admin() || !function_exists('is_shop') || !is_shop() || !function_exists('bhp_offer_catalog')) {
        return $loop_end;
    }

    $out = '';
    foreach (bhp_offer_catalog() as $key => $offer) {
        if (!empty($offer['upsell_of'])) {
            continue; // The upsell is a swap inside its parent, never its own card.
        }
        if (!bhp_offer_is_purchasable($key)) {
            continue;
        }
        $module = bhp_offer_render_module($key, 'bhp-offer--card', false, true);
        if ('' === $module) {
            continue;
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

    return $out . $loop_end;
}
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
