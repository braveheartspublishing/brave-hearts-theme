<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.350 — THE CATALOG SURFACES. `CYCLE179-LD-350-BUILD`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHY THIS FILE EXISTS, IN THE FOUNDER'S OWN SCOPE (seal 691, verbatim):
 *    *"its not just liberty its the shop catalog across the entire website"*.
 *
 *    Until 1.19.349 the catalog card was three things at once: markup emitted
 *    by hooks in `functions.php`, `inc/book-formats.php` and
 *    `inc/colouring-line.php`; a purchase control gated on `is_shop()`; and
 *    geometry CSS scoped to `body.woocommerce-shop`. The three did not agree
 *    about where a card was allowed to exist, so `/shop/` got a real card and
 *    the 18 taxonomy archives plus product search got a degraded one — a
 *    1110px tile with one price and a navigation link wearing a button.
 *    MEASURED, not inferred: `CATALOG-SURFACES-INVENTORY.md` §3, 2026-09-02.
 *
 * ⛔⛔ THIS FILE SUPERSEDES A COMMENT THAT SAYS THE OPPOSITE, DELIBERATELY AND
 *     IN THE SAME CHANGE. `inc/book-formats.php` (1.19.286) records:
 *
 *       "every rule in `style.css` is deliberately scoped to
 *        `body.woocommerce-shop`. ⭐ THAT SCOPE IS LOAD-BEARING … the fix is
 *        to match the PHP to it, never to widen the CSS to match the PHP."
 *
 *     ⭐ THAT WAS CORRECT WHEN IT WAS WRITTEN AND IT IS NOT CORRECT NOW, and
 *        the reason is a fact about the DOM rather than a change of taste. The
 *        comment's real subject was never `/shop/` versus the archives — it was
 *        `ul.products li.product` ON A SINGLE PRODUCT PAGE, where WooCommerce
 *        renders the related and upsell rows through the very same markup. The
 *        1.19.286 author had one predicate available (`is_shop()`) and it
 *        excluded both the archives and the PDP rows in one stroke.
 *
 *     ⭐ SO THE SCOPE IS KEPT AND ITS BOUNDARY IS MOVED TO WHERE IT ALWAYS
 *        MEANT TO BE. `body.bhp-catalog-grid` is emitted on exactly the pages
 *        `bhp_catalog_grid_context()` returns true for, and that predicate
 *        RETURNS FALSE ON `is_product()` FIRST, before anything else. The PDP
 *        related and upsell rows therefore keep 1.19.286's behaviour byte for
 *        byte: no `bhp-shop-atc`, no grid geometry, the "CHOOSE YOUR FORMAT"
 *        navigation link exactly as before. ⛔ The thing the old comment was
 *        protecting is still protected; only the pages it was protecting it
 *        FROM have changed.
 *
 *     ⚠️ Recorded as `CYCLE179-CX-007` by `commerce-cx` before this build, with
 *        the instruction that the comment "must be updated in the same commit
 *        or the next reader will treat the change as a regression". The
 *        superseding note is written into `inc/book-formats.php` itself as well
 *        as here, because a reader arrives at the comment, not at this file.
 *
 * ⛔ WHAT THIS FILE DOES NOT DO, AND MUST NEVER:
 *    - It reads no WooCommerce setting and writes none. No price, coupon,
 *      stock, shipping, tax, payment or product record is touched anywhere in
 *      it. Ordering is a QUERY filter; `menu_order` is never written.
 *    - It creates no review, rating, testimonial, statistic or claim. The proof
 *      blocks it moves are RELOCATED with their wording untouched (§9.1a: never
 *      rewrite a word inside a quoted third-party statement).
 *    - It renders no "we"/"us"/"our" in any customer-facing string (§9.1 — he
 *      is the sole operator), no em dash (608a), and no outcome claim.
 *
 * @package Brave_Hearts
 * @since   1.19.350
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE PREDICATE — "is this loop a customer-facing catalog grid?"
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⭐⭐ THE ONE PREDICATE. Everything else in the catalog work reads it and
 *     nothing else re-derives it.
 *
 * ⛔ `is_product()` IS TESTED FIRST AND SHORT-CIRCUITS, AND THAT ORDER IS
 *    LOAD-BEARING. A single product page can be `is_woocommerce()` and carries
 *    `ul.products li.product` in its related and upsell rows. Those rows are
 *    NOT a catalog grid, nobody has ruled on them, and 1.19.286 found in a real
 *    browser that giving them the shop control put two live add-to-cart buttons
 *    on a page that was supposed to have one. They stay out.
 *
 * ⛔ THE FOUR EXCLUSIONS, STATED IN CODE RATHER THAN LEFT TO INFERENCE
 *    (`CATALOG-SURFACES-INVENTORY.md` §6b):
 *      · WooCommerce BLOCKS grids (the empty-cart cross-sell). They are React
 *        and the Store API; they never call a PHP loop hook, so this predicate
 *        cannot reach them and must not pretend to.
 *      · The BLOG book rail (`template-parts/guides/book-rail.php`). A single
 *        in-content ask inside prose. Making it a catalog card would break the
 *        1.19.345 ask architecture it was built for.
 *      · The HOMEPAGE editorial sections (`front-page.php`). Compositions, not
 *        a grid.
 *      · SINGLE-OFFER LANDINGS (`/complete-collection/`, `/book-bundles/`,
 *        `/read-aloud/`, `/author-visits/`). `/complete-collection/` is already
 *        the only catalog-adjacent surface whose buy control clears the fold at
 *        both viewports; it is left alone.
 *    None of the four renders through `ul.products` in the main query, so each
 *    is excluded by construction rather than by a name check. That is the
 *    stronger form: a new page cannot accidentally join the set by being named
 *    something.
 *
 * @since 1.19.350
 * @return bool
 */
function bhp_catalog_grid_context() {
    if (is_admin() || !function_exists('is_shop')) {
        return false;
    }

    // ⛔ FIRST, ALWAYS. See the block above.
    if (function_exists('is_product') && is_product()) {
        return false;
    }

    $is = is_shop() || (function_exists('is_product_taxonomy') && is_product_taxonomy());

    if (!$is && is_search()) {
        /*
         * WooCommerce product search is `/?s=…&post_type=product`. It is
         * `is_search()`, it is NOT `is_post_type_archive('product')`, and it
         * renders through the same `content-product.php`. Both the scalar and
         * the array shape of `post_type` are accepted because WordPress will
         * hand back either depending on how the query was built.
         */
        $pt = get_query_var('post_type');
        $is = ('product' === $pt) || (is_array($pt) && in_array('product', $pt, true));
    }

    /**
     * Whether the current request renders a customer-facing catalog grid.
     *
     * ⭐ A TEST SEAM, not a configuration point. A WP-CLI suite has no real
     *    query to make `is_shop()` true for, and a suite that could only ever
     *    reach the false branch would assert the wrong card.
     *
     * @since 1.19.350
     * @param bool $is
     */
    return (bool) apply_filters('bhp_catalog_grid_context', $is);
}

/**
 * The body class every catalog grid carries, and the ONE token the card
 * geometry CSS is scoped to from 1.19.350.
 *
 * ⭐ ONE TOKEN, NOT A REWRITE OF 53 SELECTORS INTO A CARD CLASS. The selectors
 *    keep their exact shape and their exact specificity; only the page-level
 *    gate moves from `body.woocommerce-shop` to `body.bhp-catalog-grid`. That
 *    makes the change mechanically checkable — `grep -c` on the two tokens —
 *    instead of 53 hand-edited rules any one of which could have drifted.
 *
 * @since 1.19.350
 * @param string[] $classes
 * @return string[]
 */
function bhp_catalog_body_class($classes) {
    if (bhp_catalog_grid_context()) {
        $classes[] = 'bhp-catalog-grid';
    }
    return $classes;
}
add_filter('body_class', 'bhp_catalog_body_class');

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE WOOCOMMERCE CHROME — removed from catalog grids
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⛔ THE RESULT COUNT IS REMOVED BECAUSE IT IS FALSE, not because it is ugly.
 *    `/shop/` renders "Showing all 4 results" above SIX cards, and product
 *    search renders "Showing all 2 results" above FOUR, because the bundle and
 *    collection cards are injected outside the WooCommerce query and the count
 *    only ever knew about the query. VERIFIED LIVE on both environments,
 *    2026-09-02 (`CYCLE179-CX-002`, `F-03`). A customer-facing sentence that
 *    states a wrong number is a defect whichever way it is fixed; removing the
 *    sentence is the fix that does not require reconciling an injection
 *    mechanism with a core query.
 *
 * ⛔ THE SORT SELECT GOES WITH IT because a five-item catalog with no
 *    pagination has nothing to sort, and because the two share a row: removing
 *    one leaves the row and buys back nothing.
 *
 * ⭐ THE BREADCRUMB STAYS. It is 24px, it is orientation rather than furniture,
 *    and the founder's complaint was about product, not about navigation.
 *
 * ⛔ REMOVED, NOT HIDDEN. `display:none` leaves the element in the DOM and,
 *    with it, the false sentence in the accessibility tree and in the page
 *    text a search engine reads.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_catalog_remove_loop_chrome() {
    if (!bhp_catalog_grid_context()) {
        return;
    }
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
}
add_action('wp', 'bhp_catalog_remove_loop_chrome');

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · READING ORDER — in the QUERY, never in `menu_order`
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The canonical grid order, as product IDs, read from the registry rather than
 * typed.
 *
 * ⭐ THE REGISTRY IS ALREADY IN READING ORDER — Mariana, Everest, Amazon — and
 *    has been since it was written. Reading it here rather than restating the
 *    three IDs is what stops the grid and the product pages from ever
 *    disagreeing about which book is Book 1.
 *
 * ⛔ THE COLOURING BOOK IS APPENDED LAST IN THE QUERY. Its VISUAL position is
 *    fifth, after the Complete Collection, and that last swap is done in CSS —
 *    see the note on `.bhp-shop-collection-item { order: … }` in `style.css`.
 *    The reason is mechanical: the Collection card is not a post, it is markup
 *    injected on `woocommerce_product_loop_end`, so a query can never place it
 *    fourth. ⚠️ RECORDED HONESTLY: DOM order is therefore
 *    book · book · book · colouring · collection while VISUAL order is
 *    book · book · book · collection · colouring. Two adjacent, fully-titled,
 *    fully-priced cards are transposed for a keyboard or screen-reader user.
 *    The alternative — manually rendering the colouring card outside the loop —
 *    was rejected because it would run `inc/colouring-line.php`'s six card
 *    hooks outside the loop they read `get_the_ID()` from.
 *
 * @since 1.19.350
 * @return int[]
 */
function bhp_catalog_reading_order_ids() {
    $ids = [];

    if (function_exists('bhp_book_registry')) {
        foreach (bhp_book_registry() as $book) {
            if (!empty($book['pb_product'])) {
                $ids[] = (int) $book['pb_product'];
            }
        }
    }

    if (function_exists('bhp_colouring_product_ids')) {
        foreach (bhp_colouring_product_ids() as $id) {
            $ids[] = (int) $id;
        }
    }

    /**
     * The catalog grid's reading order, as product IDs.
     *
     * @since 1.19.350
     * @param int[] $ids
     */
    return array_values(array_unique(array_filter((array) apply_filters('bhp_catalog_reading_order_ids', $ids))));
}

/**
 * ⛔⛔ THE ORDER IS SET ON THE QUERY AND NOWHERE ELSE. `menu_order` on the
 *     product records is a WOOCOMMERCE PRODUCT MUTATION and is Andrew's gate on
 *     staging as well as production. `SHOP-RECON-AND-PROPOSAL.md` §11 names it
 *     explicitly and recommends this route. Nothing here writes a product.
 *
 * ⭐ `post__in` + `orderby=post__in` is the only ordering that expresses "this
 *    exact sequence" rather than "sorted by some field that happens to give
 *    this sequence today". A field-based order is a sequence that drifts the
 *    first time somebody edits a title or a date.
 *
 * ⛔ IT RUNS ON `/shop/` ONLY, NOT ON THE TAXONOMY ARCHIVES. A category archive
 *    is a SUBSET, and forcing a five-item sequence onto a two-item term would
 *    either drop products or resurrect ones the term does not contain. The
 *    archives keep WooCommerce's own ordering and gain the card, which is the
 *    part that was actually broken there.
 *
 * ⛔ IT IS ADDITIVE TO `bhp_book_hide_hardcovers_from_shop()`, which runs on
 *    the same hook and sets `post__not_in`. `post__in` and `post__not_in`
 *    coexist in WP_Query; the hardcovers are absent from this list anyway, so
 *    the two filters cannot fight.
 *
 * @since 1.19.350
 * @param WP_Query $query
 * @return void
 */
function bhp_catalog_reading_order($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if (!function_exists('is_shop') || !is_shop()) {
        return;
    }

    $ids = bhp_catalog_reading_order_ids();
    if (count($ids) < 2) {
        return; // Nothing to order. Leave WooCommerce's own ordering alone.
    }

    $query->set('post__in', $ids);
    $query->set('orderby', 'post__in');
    $query->set('order', 'ASC');
}
add_action('pre_get_posts', 'bhp_catalog_reading_order', 20);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · THE CARD EYEBROW — "Book 1 of 3", not "Brave Hearts Expedition" ×5
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The eyebrow for one card.
 *
 * ⛔ NO NEW CLAIM IS COINED. "Book 1 of 3" is a POSITION IN A SERIES, read from
 *    the registry's own order. It states a fact about the catalog that the
 *    catalog already contains, and it is the string the founder-approved
 *    concept of record draws (the design lane's shop concept set, seal 686).
 *    ⛔ No outcome
 *    claim, no reading level, no superlative, no "we", no em dash.
 *
 * ⭐ THE SUPERSEDED STRING IS PRESERVED AS THE FALLBACK, not deleted: any card
 *    this function cannot place in the series still reads "Brave Hearts
 *    Expedition", exactly as every card did in 1.19.349.
 *
 * @since 1.19.350
 * @param int $product_id
 * @return string
 */
function bhp_catalog_card_eyebrow($product_id) {
    $product_id = (int) $product_id;

    if (function_exists('bhp_book_registry')) {
        $n     = 0;
        $total = 0;
        $hit   = 0;
        foreach (bhp_book_registry() as $book) {
            $total++;
            if ((int) $book['pb_product'] === $product_id || (int) $book['hc_product'] === $product_id) {
                $hit = $total;
            }
        }
        unset($n);
        if ($hit > 0 && $total > 0) {
            /* translators: 1: this book's position in the series, 2: how many books are in the series. */
            return sprintf(__('Book %1$d of %2$d', 'brave-hearts'), $hit, $total);
        }
    }

    if (function_exists('bhp_colouring_slug_for_product') && null !== bhp_colouring_slug_for_product($product_id)) {
        return __('Companion coloring book', 'brave-hearts');
    }

    return __('Brave Hearts Expedition', 'brave-hearts');
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 3b · F-01 — A PRODUCT ARCHIVE THAT SELLS NOTHING MUST NOT BE A LANDING PAGE
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Every product ID this theme hides from the catalog loop.
 *
 * ⭐ READ FROM THE SAME REGISTRY `bhp_book_hide_hardcovers_from_shop()` READS,
 *    so the redirect below and the filter that causes it cannot disagree about
 *    which products are hidden. ⛔ No ID is typed here.
 *
 * @since 1.19.350
 * @return int[]
 */
function bhp_catalog_hidden_product_ids() {
    $hidden = [];
    if (function_exists('bhp_book_registry')) {
        foreach (bhp_book_registry() as $book) {
            if (!empty($book['hc_product'])) {
                $hidden[] = (int) $book['hc_product'];
            }
        }
    }
    return $hidden;
}

/**
 * ⭐⭐⭐ F-01. `/product-category/hardcover-books/` renders
 *      "No products were found matching your selection." VERIFIED LIVE on
 *      staging2 at four asserted viewports, 2026-09-02, by this desk: zero
 *      cards, zero buy controls, at 1920, 1440, 1366 and 375.
 *
 * ⛔ THE CAUSE IS NOT A MISSING PRODUCT. Both hardcovers ARE in that term and
 *    both ARE published and purchasable. `bhp_book_hide_hardcovers_from_shop()`
 *    (`inc/book-formats.php`) suppresses them from `is_shop() ||
 *    is_product_taxonomy()` so each title appears once on the grid, and it
 *    empties this one term as a side effect.
 *
 * ⭐⭐ THE CHIEF OF STAFF'S DECISION, AND THE REASON IT IS A REDIRECT RATHER
 *    THAN A FIX:
 *     301 to `/shop/` until the founder rules on hardcover availability (`C12`).
 *     ⛔ THE HARDCOVERS ARE NOT UN-HIDDEN. Un-hiding them would put six cards
 *     on the grid where the whole one-card-per-title consolidation exists to
 *     put four, and it would pre-empt a founder decision. ⛔ No product record,
 *     category, visibility or stock value is read or written here.
 *
 * ⛔ THE TEST IS MECHANICAL, NOT A SLUG LITERAL. A term qualifies only when it
 *    HAS published products AND EVERY ONE of them is in the hidden set. So this
 *    fires on `hardcover-books` today and on any future term that the same
 *    filter empties, and it can never fire on a term that is empty for some
 *    other reason. ⭐ `uncategorized` (`F-02`) is empty for a different reason
 *    and is deliberately NOT redirected — it is noindexed below instead,
 *    because whether it should be a public archive at all is Andrew's (`A3`).
 *
 * @since 1.19.350
 * @return void
 */
function bhp_catalog_redirect_emptied_taxonomy() {
    if (is_admin() || wp_doing_ajax() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (!function_exists('is_product_taxonomy') || !is_product_taxonomy()) {
        return;
    }

    $term = get_queried_object();
    if (!$term instanceof WP_Term) {
        return;
    }

    $hidden = bhp_catalog_hidden_product_ids();
    if (empty($hidden)) {
        return;
    }

    /*
     * ⛔ A DELIBERATE SECOND QUERY, AND IT IS NOT THE MAIN ONE. The main query
     *    has already been filtered by `pre_get_posts`, so asking it "were you
     *    emptied?" cannot distinguish "emptied by the filter" from "empty".
     *    This asks the term what it CONTAINS. `fields => ids`, capped, no meta,
     *    no term cache update: the cheapest shape that answers the question.
     */
    $in_term = get_posts([
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'fields'                 => 'ids',
        'posts_per_page'         => 50,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'suppress_filters'       => false,
        'tax_query'              => [[
            'taxonomy' => $term->taxonomy,
            'field'    => 'term_id',
            'terms'    => (int) $term->term_id,
        ]],
    ]);

    if (empty($in_term)) {
        return; // ⛔ Genuinely empty. Not this function's case. See the noindex below.
    }

    if (array_diff($in_term, $hidden)) {
        return; // ⛔ At least one product survives the filter. The archive works.
    }

    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
    if (!$shop) {
        return; // ⛔ No destination resolved. Render the page rather than 302 to nowhere.
    }

    wp_safe_redirect($shop, 301);
    exit;
}
add_action('template_redirect', 'bhp_catalog_redirect_emptied_taxonomy', 2);

/**
 * A product archive that renders no card is noindexed. `F-02`, and the second
 * half of the `chief-of-staff` F-01 decision.
 *
 * ⛔ NOINDEX, NOT A REDIRECT, AND NOT A DELETION. Whether `uncategorized` should
 *    be a public archive at all is a WooCommerce product-category question and
 *    therefore Andrew's (`A3` in `CATALOG-SURFACES-INVENTORY.md` §9). This stops
 *    a zero-product page collecting crawl budget without deciding anything.
 *
 * ⭐ IT READS THE MAIN QUERY'S OWN RESULT, so it is true of whatever actually
 *    rendered rather than of what a second query thinks should have.
 *
 * @since 1.19.350
 * @param array $robots
 * @return array
 */
function bhp_catalog_noindex_empty_archive($robots) {
    if (is_admin() || !function_exists('is_product_taxonomy') || !is_product_taxonomy()) {
        return $robots;
    }
    global $wp_query;
    if (!$wp_query instanceof WP_Query || (int) $wp_query->post_count > 0) {
        return $robots;
    }

    $robots['noindex']  = true;
    $robots['nofollow'] = false; // ⭐ Crawl onward from it; just do not index it.
    unset($robots['index']);

    return $robots;
}
add_filter('wp_robots', 'bhp_catalog_noindex_empty_archive', 20);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4a · THE CARD TITLE DROPS THE SERIES PREFIX THE BAND ALREADY CARRIES
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⭐⭐ THE CARD SAYS "The Mariana Trench", NOT "Adventures of Charlotte and
 *     Henry: The Mariana Trench", AND ONLY ON A CATALOG GRID.
 *
 * ⛔ THIS IS THE SAME SEAM, FOR THE SAME REASON, AS A TRANSFORM THIS THEME
 *    ALREADY SHIPS. `bhp_book_display_title()` (`inc/book-formats.php`) has
 *    stripped "(Paperback)" / "(Hardcover)" from catalog and rail titles since
 *    before this release, on the stated grounds that *"a related card
 *    advertises a TITLE, not an edition"* and that the suffix is noise there.
 *    The series prefix is the same kind of noise in the same place: the band
 *    directly above the grid reads "Adventures of Charlotte and Henry", so
 *    every card was repeating the page's own masthead before saying what it is.
 *
 * ⭐ IT IS WHAT THE FOUNDER-APPROVED CONCEPT OF RECORD DRAWS. The design
 *    lane's shop concept set (seal 686) renders the five card titles as
 *    "The Mariana Trench",
 *    "Mount Everest", "The Amazon", "The Complete Collection" and "The Mariana
 *    Trench Ocean Coloring Book".
 *
 * ⛔⛔ AND IT IS LOAD-BEARING FOR THE FOLD, WHICH IS WHY IT IS IN THIS RELEASE
 *     RATHER THAN PROPOSED FOR A LATER ONE. MEASURED at an asserted 375x812:
 *     the coloring card's full name runs to FOUR lines and drove its whole row,
 *     and the chapter titles ran to two. That is roughly 40px a card, which is
 *     the difference between two rows of cards above the 812 fold and one.
 *
 * ⛔ NOTHING IS RENAMED AND NOTHING IS LOST. It is a DISPLAY transform on
 *    `the_title`, scoped to a catalog grid inside the loop. The product record
 *    is untouched. The full name still renders on the product page, in the
 *    cart, in every order, in every email, in every export and in the schema
 *    (Rank Math builds its Product entity from the record, not from
 *    `the_title`). ⛔ No product, price, stock or setting is read or written.
 *
 * ⛔ THE SPLIT IS MECHANICAL, NOT A LIST OF LITERALS: everything up to and
 *    including the FIRST ": " comes off, and only when what remains is a real
 *    title. A product whose name has no colon is returned untouched, and so is
 *    one whose remainder would be too short to be a name. ⭐ So a title added
 *    later needs no code change and cannot be silently mangled.
 *
 * ⚠️ RECORDED FOR RATIFICATION as decision X5 in the 350 build report. It is
 *    display copy, it is reversible by deleting one `add_filter`, and the full
 *    name is one click away on every card.
 *
 * @since 1.19.350
 * @param string   $title
 * @param int|null $id
 * @return string
 */
function bhp_catalog_card_title($title, $id = null) {
    if (is_admin() || !$id || !in_the_loop() || !bhp_catalog_grid_context()) {
        return $title;
    }

    $known = false;
    if (function_exists('bhp_book_lookup_product')) {
        $found = bhp_book_lookup_product((int) $id);
        $known = ($found && !empty($found['canonical']));
    }
    if (!$known && function_exists('bhp_colouring_slug_for_product')) {
        $known = (null !== bhp_colouring_slug_for_product((int) $id));
    }
    if (!$known) {
        return $title; // ⛔ Only products this theme actually knows about.
    }

    $pos = strpos($title, ': ');
    if (false === $pos) {
        return $title;
    }

    $short = trim(substr($title, $pos + 2));
    if (strlen($short) < 4) {
        return $title; // ⛔ Degrade to the full name rather than to a stub.
    }

    return $short;
}
/* ⭐ Priority 11, after `bhp_book_display_title()` at 10, so the format suffix
   has already come off and this works on the name rather than on the edition. */
add_filter('the_title', 'bhp_catalog_card_title', 11, 2);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4b · THE "FROM" LEAD ON THE ONE BIG PRICE
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⭐ ONE PRICE PER CARD, AND IT SAYS WHICH KIND OF PRICE IT IS.
 *
 * The card's big figure is the PAPERBACK price, because the paperback is what
 * the card's ADD TO CART buys (`FD-439`). A card that also offers a hardcover
 * therefore shows a floor, not a fixed price, and "From" is the word that says
 * so. ⛔ It is emitted ONLY where a second offerable format actually exists: a
 * one-format card (the coloring book, or any card on a school-visit-flagged
 * session where the paperback-only gate is doing its job) shows a bare figure,
 * because "From $12.99" on a single-format product would be misleading.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔⛔ THE SUPERSEDED IMPLEMENTATION, AND IT IS RECORDED BECAUSE IT SHIPPED
 *      TO STAGING AND FATALLED THE PAGE. DO NOT REINTRODUCE IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The first version of this was a filter on `woocommerce_get_price_html`:
 *
 *     add_filter('woocommerce_get_price_html', 'bhp_catalog_from_price_html', 20, 2);
 *     …
 *     $formats = bhp_book_shop_format_prices($found['key']);
 *     if (count($formats) < 2) { return $html; }
 *     return '<span class="bhp-shop-price-lead">From</span> ' . $html;
 *
 * ⛔⛔ IT IS INFINITELY RECURSIVE, AND THE CYCLE IS FOUR CALLS LONG:
 *
 *     woocommerce_get_price_html
 *       → bhp_catalog_from_price_html()
 *         → bhp_book_shop_format_prices()
 *           → bhp_book_purchase_data()      (`inc/book-formats.php`)
 *             → $product->get_price_html()  (two call sites, paperback + hardcover)
 *               → woocommerce_get_price_html …
 *
 * ⭐ OBSERVED, NOT REASONED. Deployed to staging2 as 1.19.350 at 14:34 UTC on
 *    2026-09-02; `/shop/` returned "There has been a critical error on this
 *    website" in a real browser at an asserted 1440x900, and
 *    `public_html/php_errorlog` recorded, repeatedly:
 *
 *      PHP Fatal error: Allowed memory size of 805306368 bytes exhausted
 *      (tried to allocate 20480 bytes) in wp-content/plugins/woocommerce/
 *      includes/abstracts/abstract-wc-data.php on line 886
 *
 *    ⛔ `php -l` passed on all five changed files beforehand. A lint cannot see
 *    a hook cycle, which is exactly why the browser is the instrument.
 *
 * ⭐ THE FIX IS TO STOP FILTERING A VALUE THIS CODE ALSO READS. The lead is now
 *    its own element, emitted on `woocommerce_after_shop_loop_item_title` at
 *    priority 9 — one priority ahead of `woocommerce_template_loop_price` at 10
 *    — so it lands immediately above the price with no filter in the loop and
 *    no way to re-enter. ⛔ A reentrancy static was considered and REJECTED: a
 *    guard makes a recursive design survivable, it does not make it correct,
 *    and the next reader would inherit a landmine with a fence around it.
 *
 * ⛔ NO NEW CLAIM. "From" is a price qualifier, not a superlative, not an
 *    outcome, not a figure. ⛔ No "we". ⛔ No em dash.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_catalog_price_lead() {
    if (!bhp_catalog_grid_context() || !function_exists('bhp_book_lookup_product')) {
        return;
    }

    $found = bhp_book_lookup_product(get_the_ID());
    if (!$found || empty($found['canonical'])) {
        return;
    }

    $formats = function_exists('bhp_book_shop_format_prices') ? bhp_book_shop_format_prices($found['key']) : [];
    if (count($formats) < 2) {
        return; // ⛔ One format, one figure, no floor to state.
    }

    echo '<p class="bhp-shop-price-lead">' . esc_html__('From', 'brave-hearts') . '</p>';
}
add_action('woocommerce_after_shop_loop_item_title', 'bhp_catalog_price_lead', 9);

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE PROOF BLOCKS — RELOCATED below the grid, never reworded, never deleted
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⛔⛔ RELOCATE, NEVER DELETE, AND NEVER REWORD. These are REAL Amazon customer
 *     reviews and a REAL Kirkus line. Standing Rules §3 (never invent) and
 *     §9.1a (never rewrite a pronoun inside a quoted third-party statement)
 *     both bind here. One of the live quotes reads "We read a few chapters each
 *     night" — that "we" is a CUSTOMER'S word and "fixing" it to match the
 *     founder's voice rule would FABRICATE A CUSTOMER STATEMENT.
 *
 * ⛔ WHAT WAS ACTUALLY WRONG WAS THE POSITION, AND IT WAS MEASURED. The compact
 *    review showcase is 166px and the Kirkus badge 28px, and both sat BETWEEN
 *    the price and the buy button on every card, on `/shop/` and on all 18
 *    taxonomy archives (`SHOP-RECON-AND-PROPOSAL.md` §2d;
 *    `CATALOG-SURFACES-INVENTORY.md` §3.4, both live-measured 2026-09-02). They
 *    pushed the buy control 194px further down on EVERY card at EVERY viewport,
 *    and the proof was uneven — card 1 had both, card 2 a review only, card 3
 *    neither, while all three were still forced to 916px, so The Amazon carried
 *    194px of blank space purely to keep the row aligned.
 *
 * ⭐ SO THE TWO HOOKS COME OFF THE CARD AND ONE BLOCK GOES BELOW THE GRID. The
 *    same components, the same registry, the same words, rendered once instead
 *    of three times.
 *
 * ⚠️ THE ON-PAGE PROOF WORDING IS NOT TOUCHED BY THIS BUILD. `ACT-OPS-333` has
 *    not cleared, and the founder default (seal 704) is "relocated BELOW the
 *    grid, unchanged in wording". The verified count (31 ratings / 28 written
 *    reviews, all five stars, seal 717) is therefore NOT written onto any page
 *    here. If a proof line is ever rewritten, the approved wording is
 *    "31 ratings on Amazon across the three books, all five stars, as of
 *    2 September 2026", dated and linked, and it never goes on the coloring
 *    page. Recorded so the next reader does not have to find it again.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_catalog_unhook_card_proof() {
    if (!bhp_catalog_grid_context()) {
        return;
    }
    remove_action('woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_kirkus_badge', 15);
    remove_action('woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_amazon_review_badge', 20);
}
add_action('wp', 'bhp_catalog_unhook_card_proof');

/**
 * The one trust strip, below the grid.
 *
 * ⛔ IT RENDERS NOTHING WHEN THERE IS NOTHING REAL TO RENDER. Both component
 *    calls return '' for a book with no approved review (The Amazon has none as
 *    of this writing, confirmed rather than assumed), and an empty strip is not
 *    emitted at all. ⛔ There is no placeholder, no "reviews coming soon", and
 *    no `aggregateRating`.
 *
 * ⛔ NOT ON A VISIT-FLAGGED SESSION. A parent arriving from a flyer QR code has
 *    a deadline and a shelf count; the page must not spend its remaining height
 *    on marketing furniture. `bhp_school_visit_active()` is the same test the
 *    counters use, so the two cannot disagree.
 *
 * @since 1.19.350
 * @return void
 */
function bhp_catalog_trust_strip() {
    if (!bhp_catalog_grid_context()) {
        return;
    }
    if (function_exists('bhp_school_visit_active') && bhp_school_visit_active()) {
        return;
    }
    if (!function_exists('bhp_render_amazon_review_showcase')) {
        return;
    }

    $blocks = '';

    if (function_exists('bhp_render_kirkus_credibility')) {
        $blocks .= bhp_render_kirkus_credibility('compact', ['source' => 'catalog_trust_strip', 'show_link' => true]);
    }

    foreach (['mariana_trench', 'mount_everest', 'amazon_rainforest'] as $bhp_cts_key) {
        $blocks .= bhp_render_amazon_review_showcase($bhp_cts_key, 'compact', [
            'source'               => 'catalog_trust_strip',
            'show_link'            => false,
            'max_reviews'          => 1,
            'max_excerpt_words'    => 18,
            'show_verified_badge'  => false,
        ]);
    }

    if ('' === trim($blocks)) {
        return; // ⛔ Nothing real to show. Render nothing at all.
    }
    ?>
    <section class="bhp-catalog-trust-strip" aria-label="<?php esc_attr_e('Reviews and awards', 'brave-hearts'); ?>">
      <div class="bhp-catalog-trust-strip__inner">
        <?php echo $blocks; // phpcs:ignore WordPress.Security.EscapeOutput -- already escaped by the components. ?>
      </div>
    </section>
    <?php
}
add_action('woocommerce_after_shop_loop', 'bhp_catalog_trust_strip', 30);
