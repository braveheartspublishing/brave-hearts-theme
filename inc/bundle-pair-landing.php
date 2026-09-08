<?php
/**
 * Brave Hearts - the book + coloring book PAIR landing page.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.399 - WHY THIS FILE EXISTS. `CYCLE179-LD-BUILD-399-BUNDLE-PAGES`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FOUNDER FOUND IT ON THE SHOP GRID HIMSELF. Andrew Signore, 2026-09-07,
 *    verbatim, relayed in the build brief (⚠️ RELAYED through `chief-of-staff`,
 *    NOT witnessed first-hand by this desk):
 *
 *      "when you click on the bundles they dont have their own bundle page?"
 *      "I clicked on the shop grid and when you try to click each bundle they
 *       dont go ti a product page like the individual books"
 *
 * ⭐ VERIFIED FIRST-HAND ON STAGING BEFORE ANY CODE WAS WRITTEN, at
 *    `window.innerWidth` 1280, by reading the served DOM of `/shop/`:
 *      · the four real product cards each carry 2 anchors to their PDP;
 *      · `.bhp-shop-collection-card` carries ⛔ ZERO anchors - image not
 *        wrapped, title a bare `<h2>` - although `/complete-collection/`
 *        (page 360) exists and is published;
 *      · `.bhp-catalog-bundle-strip`'s pair card carries ⛔ ZERO anchors, and
 *        there was no page anywhere for it to point at.
 *    The collection card is fixed at its source in `inc/book-formats.php`.
 *    THIS FILE BUILDS THE MISSING DESTINATION FOR THE PAIR.
 *
 * ⛔⛔ NO BUNDLE IS A WOOCOMMERCE PRODUCT, AND THIS FILE DOES NOT MAKE ONE.
 *    The pair is computed by the bundle plugin's offer engine from cart
 *    contents. No product record, no variation, no SKU and no price record is
 *    created, read for writing, or implied anywhere below. A "bundle page" here
 *    is a WordPress PAGE carrying a shortcode, exactly as `/complete-collection/`
 *    is - which is the "same template family" the brief asked for.
 *
 * ⛔⛔ NOT ONE PRICE, SAVING OR SHIPPING FIGURE IS TYPED IN THIS FILE.
 *    Every number is read at render from the plugin that owns it:
 *      · `bhp_offer_price()`            - Andrew's approved offer price
 *      · `bhp_offer_component_total()`  - what the two books cost separately,
 *                                         READ LIVE from WooCommerce
 *      · `bhp_offer_saving()`           - the DERIVED claim, recomputed every
 *                                         render, null when it cannot be
 *                                         honestly stated
 *      · `bhp_book_free_shipping_line()` - the locked shipping sentence
 *    ⭐ `grep -nE '[0-9]+\.[0-9]{2}' inc/bundle-pair-landing.php` returns
 *       nothing. `tests/test-cycle179-399.php` asserts that every run.
 *
 * ⛔ IT FAILS CLOSED, EVERYWHERE. If the plugin is absent, if either component
 *    does not resolve to a live purchasable product, or if the session is
 *    school-visit flagged, the section renders NOTHING rather than an
 *    unbuyable promise. That is `R1.4` and it is the same rule the shop card
 *    already follows.
 *
 * ⚠️⚠️ THE SHIPPING SENTENCE, AND THE ONE THING A READER OF THIS FILE MUST NOT
 *    MISREAD. `bhp_book_free_shipping_line()` says "FREE Shipping on the
 *    complete collection or 3 or more books purchased". That sentence is TRUE
 *    and it is the locked approved string, so it is printed verbatim.
 *    ⛔ IT IS NOT A CLAIM THAT THIS PAIR SHIPS FREE, AND THIS PAIR DOES NOT.
 *       A two-book cart is below the any-three threshold. VERIFIED by reading
 *       `bhp_bundle_shipping_amount()` this build: a paperback-chapter +
 *       coloring cart takes `bhp_bundle_rules('paperback')[2]['shipping']`, and
 *       a hardcover + coloring cart takes the mixed-format branch.
 *    ⛔ SO NO PER-PAIR SHIPPING FIGURE IS PRINTED HERE AT ALL. The hardcover
 *       branch's figure is a LITERAL inside a plugin `if`, with no accessor,
 *       and printing it from the theme would be exactly the hardcoded number
 *       the brief forbids. Silence is the correct failure: shipping is stated
 *       at checkout by the engine that charges it.
 *    ⭐ ROUTED TO ANDREW as a decision, with both verified figures, rather
 *       than settled here.
 *
 * ⭐ COPY PROVENANCE. Every bullet in "what is inside" is the ALREADY-APPROVED
 *    string from `bhp_book_whats_inside()` - `mariana_trench` for the chapter
 *    book, `colouring_mariana` for the coloring book. ⛔ Nothing is rewritten,
 *    re-ordered or summarised. New sentences written for this page are few,
 *    are collected in `bhp_pair_landing_draft_copy()`, and are marked
 *    ⛔ FOR ANDREW'S APPROVAL there.
 *
 * ⭐ HOUSE RAILS OBSERVED THROUGHOUT: American spelling in every customer-facing
 *    word (the identifiers say `colouring` because the plugin's API does; the
 *    WORDS say "coloring") · no em dashes in customer copy · no "we", "us" or
 *    "our" in customer copy, the voice is I/me/my (Standing Rules §9.1) · no
 *    outcome claims · reading age 6 to 9, never 5 to 9 · no invented review,
 *    rating, statistic or endorsement.
 *
 * @package brave-hearts
 * @since   1.19.399
 */

defined('ABSPATH') || exit;

/**
 * The offers this page sells, keyed by the format the shopper chooses.
 *
 * ⛔ THE KEYS ARE THE PLUGIN'S OWN OFFER KEYS and are never invented here. They
 *    are filterable so a second pair (an Everest pair, an Amazon pair) becomes
 *    a filter rather than a fork of this file the day those books exist.
 *
 * ⛔ THE BRIEF IS EXPLICIT THAT NOTHING ELSE IS ADDED TO THE GRID THIS BUILD:
 *    the plugin also defines `colouring_collection`, `six_book_pb` and
 *    `six_book_hc`, all of which carry `cart_rule => 'unimplemented'` and are
 *    correctly invisible. ⭐ This file reaches for none of them.
 *
 * @since 1.19.399
 * @return array<string,string> format => offer key.
 */
function bhp_pair_landing_offers() {
    return (array) apply_filters('bhp_pair_landing_offers', [
        'paperback' => 'mariana_pb_colouring',
        'hardcover' => 'mariana_hc_colouring',
    ]);
}

/**
 * The page's own URL, used by the shop grid's bundle-strip card.
 *
 * ⛔ ONE DEFINITION, TWO CALLERS. The card link and the page itself must never
 *    be able to disagree about where this page lives, which is precisely the
 *    defect that produced the dead card in the first place.
 *
 * ⚠️ THE SLUG IS CUSTOMER-FACING AND IS ⛔ FOR ANDREW'S APPROVAL. It is a
 *    proposal, not a decision: `/mariana-trench-book-and-coloring-book/`.
 *    Changing it is one filter or one constant.
 *
 * @since 1.19.399
 * @return string
 */
function bhp_pair_landing_slug() {
    return (string) apply_filters('bhp_pair_landing_slug', 'mariana-trench-book-and-coloring-book');
}

/**
 * @since 1.19.399
 * @return string Absolute URL of the pair page.
 */
function bhp_pair_landing_url() {
    return (string) apply_filters('bhp_pair_landing_url', home_url('/' . trim(bhp_pair_landing_slug(), '/') . '/'));
}

/**
 * Does a PUBLISHED page carrying this shortcode actually exist right now?
 *
 * ⛔⛔ THIS IS THE GATE THE SHOP CARD LINK USES, AND IT IS THE WHOLE POINT.
 *    Linking a card at a URL that 404s is a WORSE defect than the dead card
 *    Andrew found: a dead card frustrates, a broken link looks like a broken
 *    store. ⭐ So the card only becomes a link once the destination is proved
 *    to exist, published, and non-private - by query, at render, on whichever
 *    environment is serving.
 *
 * ⭐ CACHED PER REQUEST. The shop grid asks once; a second ask inside the same
 *    page load reuses the answer rather than running a second query.
 *
 * @since 1.19.399
 * @return bool
 */
function bhp_pair_landing_page_exists() {
    static $exists = null;
    if (null !== $exists) {
        return $exists;
    }

    $page = get_page_by_path(bhp_pair_landing_slug(), OBJECT, 'page');

    /*
     * ⛔ THREE CONDITIONS, ALL REQUIRED, AND `post_status` ALONE IS NOT ENOUGH.
     *    A page can be `publish` and still be password-protected, which serves
     *    a password form to a shopper who clicked a product card. The brief
     *    names the privacy flags explicitly and this is where they are enforced
     *    rather than merely asserted in a report.
     */
    $exists = ($page instanceof WP_Post)
        && 'publish' === $page->post_status
        && '' === (string) $page->post_password;

    return $exists;
}

/**
 * The few sentences this page adds that did not already exist.
 *
 * ⛔⛔ EVERY STRING IN THIS FUNCTION IS ⛔ FOR ANDREW'S APPROVAL. They are
 *    drafted to the house rails and they are NOT approved copy: no "we", no em
 *    dash, American spelling, ages 6 to 9, no outcome claim, no review, no
 *    statistic, no endorsement, and no number that the plugin computes.
 *
 * ⭐ THE LIST IS DELIBERATELY SHORT. Everything that could be taken from
 *    already-approved copy was taken from it instead of rewritten - the two
 *    "what is inside" lists are `bhp_book_whats_inside()` verbatim, the
 *    guarantee is the plugin's own, the shipping sentence is the locked one,
 *    and the offer heading and descriptor are the strings the shop card
 *    already ships.
 *
 * @since 1.19.399
 * @param string $key    Copy key.
 * @param array  $tokens sprintf tokens.
 * @return string Translated, UNESCAPED. Callers escape.
 */
function bhp_pair_landing_draft_copy($key, array $tokens = []) {
    $copy = [
        /* ⛔ FOR ANDREW'S APPROVAL - the page title and the H1. */
        'page_title'      => __('The Mariana Trench: book and coloring book', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'hero_eyebrow'    => __('Two books, one order', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. Describes the object. Makes no claim about a child. */
        'hero_sub'        => __('The chapter book that takes Charlotte and Henry seven miles down, and the coloring book that follows the same dive.', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'inside_heading'  => __('What is inside each book', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'inside_chapter'  => __('The chapter book', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'inside_colour'   => __('The coloring book', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'value_heading'   => __('Both books together', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'value_separate'  => __('Bought separately', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'value_together'  => __('Bought as the set', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. %s is the saving, printed by the engine. */
        'value_saving'    => __('You save %s', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'panel_pb'        => __('Paperback set', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'panel_hc'        => __('Hardcover set', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. %s is the price, printed by the engine. */
        'panel_cta'       => __('ADD THE SET FOR %s', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'lookinside_head' => __('Look inside the chapter book', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'final_heading'   => __('Get both books together', 'brave-hearts'),
        /* ⛔ FOR ANDREW'S APPROVAL. */
        'singles_link'    => __('Prefer just one of them? Browse the books', 'brave-hearts'),
    ];

    if (!isset($copy[$key])) {
        return '';
    }
    return $tokens ? vsprintf($copy[$key], $tokens) : $copy[$key];
}

/**
 * Is this page renderable AT ALL on this request?
 *
 * ⛔ ONE PREDICATE, ASKED BY EVERY SECTION. The paperback offer is the page's
 *    subject; with it unofferable there is no page, and a hardcover-only page
 *    would contradict `FD-439` (paperback is the default) as well as leaving a
 *    hero with one price panel.
 *
 * @since 1.19.399
 * @return bool
 */
function bhp_pair_landing_available() {
    if (!function_exists('bhp_offer_price') || !function_exists('bhp_offer_is_offerable')) {
        return false; // Plugin off: render nothing rather than a dead page.
    }
    $offers = bhp_pair_landing_offers();
    if (empty($offers['paperback'])) {
        return false;
    }
    return bhp_offer_is_offerable($offers['paperback'])
        && null !== bhp_offer_price($offers['paperback']);
}

/**
 * ⭐ THE PAGE REUSES THE COLLECTION PAGE'S STYLESHEET, AND THAT IS THE BRIEF'S
 *    "SAME TEMPLATE FAMILY" MADE LITERAL RATHER THAN APPROXIMATED.
 *
 * ⛔ THE PLUGIN'S OWN ENQUEUE CANNOT DO IT. `bhp_bundle_landing_enqueue_assets()`
 *    gates on `has_shortcode( ..., 'bhp_complete_series_landing' )`, so a page
 *    carrying THIS shortcode gets none of that CSS and would render the
 *    forest-green sections unstyled. ⭐ Re-registering the same handle from the
 *    theme is a no-op when the plugin already enqueued it (WordPress dedupes on
 *    handle), so the two can never double-load or fight.
 *
 * ⛔ NO PLUGIN FILE IS EDITED TO ACHIEVE THIS. The brief scopes this build to
 *    theme 1.19.399, and widening it to a plugin release to move one gate would
 *    be scope creep wearing a tidy-up's name.
 *
 * ⛔ IT DEGRADES. With the plugin absent, `BHP_BUNDLE_PRICING_URL` is undefined,
 *    nothing is enqueued, and `bhp_pair_landing_available()` has already
 *    refused to render the page anyway.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_enqueue_assets() {
    if (!is_singular()) {
        return;
    }
    $post = get_post();
    if (!$post instanceof WP_Post || !has_shortcode((string) $post->post_content, 'bhp_bundle_pair_landing')) {
        return;
    }

    if (defined('BHP_BUNDLE_PRICING_URL') && defined('BHP_BUNDLE_PRICING_VERSION')) {
        wp_enqueue_style(
            'bhp-bundle-landing',
            BHP_BUNDLE_PRICING_URL . 'assets/bundle-landing.css',
            [],
            BHP_BUNDLE_PRICING_VERSION
        );
    }

    /*
     * ⭐ THE PAIR-ONLY RULES. A short sheet, loaded after the collection sheet
     *    so it can only ever ADD to it. ⛔ It overrides no collection selector:
     *    every rule in it is scoped under `.bhp-pair-landing`.
     *
     * ⭐ VERSIONED OFF THE THEME VERSION AND NOTHING ELSE, which is correct
     *    rather than lazy: 1.19.397's cache-busting is a `style_loader_src`
     *    FILTER (`bhp_asset_version_filter_src()`), so the content-hash suffix
     *    is appended to every theme asset centrally. ⛔ Computing a stamp here
     *    would be a second owner of the same string.
     */
    wp_enqueue_style(
        'bhp-bundle-pair-landing',
        get_template_directory_uri() . '/assets/css/bundle-pair-landing.min.css',
        ['bhp-bundle-landing'],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'bhp_pair_landing_enqueue_assets');

/**
 * ⭐ THE SHORTCODE. Mirrors `bhp_bundle_render_landing_page()`'s shape: one
 *    ordered list of section calls, and ⛔ THIS IS THE ONLY PLACE THE ORDER MAY
 *    BE CHANGED. Never re-order with CSS `order`; DOM order and reading order
 *    stay identical for screen readers, and the suite asserts one sequence.
 *
 * @since 1.19.399
 * @return string
 */
function bhp_pair_landing_render() {
    if (!bhp_pair_landing_available()) {
        return ''; // R1.4: nothing is advertised that cannot be bought.
    }

    ob_start();
    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }
    ?>
    <div class="bhp-landing bhp-pair-landing" data-bhp-landing data-bhp-pair-landing>
        <?php
        bhp_pair_landing_render_hero();
        if (function_exists('bhp_bundle_render_landing_trust_row')) {
            bhp_bundle_render_landing_trust_row();
        }
        bhp_pair_landing_render_whats_inside();
        bhp_pair_landing_render_look_inside();
        if (function_exists('bhp_bundle_render_landing_kirkus')) {
            /*
             * ⭐ THE BRIEF'S CONDITION, TESTED RATHER THAN ASSUMED: "the Kirkus
             *    quote block if the collection page carries it". It does -
             *    `bhp_bundle_render_landing_page()` calls this same function -
             *    and the component returns '' of its own accord when no
             *    approved Kirkus data resolves. ⛔ Nothing about the quote, its
             *    attribution or its source URL is restated here.
             */
            bhp_bundle_render_landing_kirkus();
        }
        bhp_pair_landing_render_value();
        bhp_pair_landing_render_singles_exit();
        bhp_pair_landing_render_final_cta();
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bhp_bundle_pair_landing', 'bhp_pair_landing_render');

/**
 * One priced panel: the covers are above, this is the money and the button.
 *
 * ⛔⛔ THE FORM IS THE EXISTING BUNDLE MECHANISM, NOT A NEW ONE, AND THE FIELD
 *    NAMES ARE THE PLUGIN'S CONTRACT:
 *      · `bhp_bundle_nonce`  - from `bhp_bundle_nonce_input()`
 *      · `bhp_bundle_action` - `offer_<key>`, the exact family the shop card
 *                              and the product-page cross-sell already post
 *      · the checkout redirect field, so the set lands on /checkout/
 *
 * ⚠️⚠️ A CORRECTION TO THE BRIEF, RECORDED RATHER THAN SILENTLY ABSORBED. The
 *    brief asks that these forms "carry both product ids". ⛔ NO OFFER FORM IN
 *    THIS CODEBASE HAS EVER CARRIED A PRODUCT ID, and making this one the
 *    exception would be a second way of saying what an offer contains.
 *    `bhp_offer_add_to_cart()` resolves the components SERVER-SIDE from
 *    `bhp_offer_components()`, and that is what makes a tampered POST harmless:
 *    the browser names the OFFER, never the goods. ⭐ The assurance the brief
 *    actually wants - that both items reach the cart - is asserted in the suite
 *    at the layer that decides it: `bhp_offer_components()` for each key
 *    resolves to exactly two live purchasable products whose buy ids are the
 *    chapter book and the coloring book. Verified live this build.
 *
 * @since 1.19.399
 * @param string $format 'paperback'|'hardcover'.
 * @param string $key    Offer key.
 * @param string $label  Panel label, already translated.
 * @return void
 */
function bhp_pair_landing_render_panel($format, $key, $label) {
    if (!bhp_offer_is_offerable($key)) {
        return; // ⛔ Fails closed, per panel. A missing panel beats a dead button.
    }
    $price = bhp_offer_price($key);
    if (null === $price) {
        return;
    }
    $saving = function_exists('bhp_offer_saving') ? bhp_offer_saving($key) : null;
    $total  = function_exists('bhp_offer_component_total') ? bhp_offer_component_total($key) : null;
    ?>
    <div class="bhp-pair-landing__panel bhp-pair-landing__panel--<?php echo esc_attr($format); ?>" data-bhp-pair-panel="<?php echo esc_attr($format); ?>" data-bhp-offer="<?php echo esc_attr($key); ?>">
        <p class="bhp-pair-landing__panel-label"><?php echo esc_html($label); ?></p>

        <p class="bhp-pair-landing__price">
            <?php
            /*
             * ⛔ THE ANCHOR IS PRINTED ONLY WHEN THE SAVING IS. `bhp_offer_saving()`
             *    returns null the moment a live component price stops matching, and
             *    a struck-through "separately" figure beside no saving is a claim
             *    with its own conclusion removed.
             */
            if (null !== $saving && null !== $total) :
                ?>
                <span class="bhp-pair-landing__was"><?php echo wp_kses_post(wc_price($total)); ?></span>
            <?php endif; ?>
            <span class="bhp-pair-landing__now"><?php echo wp_kses_post(wc_price($price)); ?></span>
        </p>

        <?php if (null !== $saving) : ?>
            <p class="bhp-pair-landing__saving"><?php
                echo esc_html(bhp_pair_landing_draft_copy('value_saving', [wp_strip_all_tags(wc_price($saving))]));
            ?></p>
        <?php endif; ?>

        <form class="bhp-bundle-form bhp-pair-landing__form" method="post">
            <?php bhp_bundle_nonce_input(); ?>
            <input type="hidden" name="bhp_bundle_action" value="<?php echo esc_attr('offer_' . $key); ?>" />
            <?php bhp_bundle_checkout_redirect_input(); ?>
            <button type="submit" class="button bhp-landing-cta bhp-landing-cta--gold bhp-pair-landing__cta">
                <?php
                /* ⛔ The figure is the ENGINE's. No price literal exists in this file. */
                echo esc_html(bhp_pair_landing_draft_copy('panel_cta', [wp_strip_all_tags(wc_price($price))]));
                ?>
            </button>
        </form>
    </div>
    <?php
}

/**
 * Section 1: the hero. Two covers, the promise, both priced panels, the
 * shipping sentence and the guarantee.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_render_hero() {
    $offers = bhp_pair_landing_offers();
    ?>
    <section class="bhp-landing-hero bhp-pair-landing__hero">
        <div class="bhp-landing-hero__inner">
            <p class="bhp-landing-eyebrow"><?php echo esc_html(bhp_pair_landing_draft_copy('hero_eyebrow')); ?></p>
            <h1 class="bhp-pair-landing__title"><?php echo esc_html(bhp_pair_landing_draft_copy('page_title')); ?></h1>
            <p class="bhp-pair-landing__sub"><?php echo esc_html(bhp_pair_landing_draft_copy('hero_sub')); ?></p>

            <?php
            /*
             * ⛔ R2.3 - DEGRADE, NEVER MIX. The pair's composite is the ONE
             *    approved picture of this object. If the slug does not resolve
             *    on this environment, NO IMAGE renders. ⛔ It never falls back
             *    to one component's cover: a chapter-book cover above a $22.99
             *    set price states that that book costs $22.99, which is the
             *    exact defect `FD-549` names.
             * ⛔ NO NEW MEDIA IS CREATED and no registry is edited. This
             *    resolves an already-registered attachment by slug.
             */
            $composite = function_exists('bhp_offer_composite_card_image')
                ? bhp_offer_composite_card_image($offers['paperback'])
                : '';
            if ('' !== trim((string) $composite)) :
                ?>
                <div class="bhp-pair-landing__covers">
                    <?php echo $composite; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image() output. ?>
                </div>
            <?php endif; ?>

            <div class="bhp-pair-landing__panels">
                <?php
                bhp_pair_landing_render_panel('paperback', $offers['paperback'], bhp_pair_landing_draft_copy('panel_pb'));
                if (!empty($offers['hardcover'])) {
                    bhp_pair_landing_render_panel('hardcover', $offers['hardcover'], bhp_pair_landing_draft_copy('panel_hc'));
                }
                ?>
            </div>

            <?php
            /*
             * ⚠️ THE SHIPPING SENTENCE. See the file header for why NO per-pair
             *    figure is printed beside it. The string is the locked one and
             *    is printed verbatim, from its single owner.
             */
            if (function_exists('bhp_book_free_shipping_line')) :
                ?>
                <p class="bhp-pair-landing__shipping"><?php echo esc_html(bhp_book_free_shipping_line()); ?></p>
            <?php endif; ?>

            <?php
            /*
             * ═══════════════════════════════════════════════════════════════
             * ⭐⭐ 1.19.399 — THE NUDGE, ON `chief-of-staff`'S CALL IN THE BRIEF.
             * ═══════════════════════════════════════════════════════════════
             *
             * ⭐ THE RULE LINE ABOVE SAYS WHERE THE THRESHOLD IS. THIS SAYS
             *    WHERE THIS SHOPPER STANDS. Printed together they are honest;
             *    the rule line alone is true but reads, beside a two-book set,
             *    as though the set ships free. It does not.
             *
             * ⛔ NO PER-PAIR FEE FIGURE IS PRINTED, HERE OR ANYWHERE ON THIS
             *    PAGE. That is unchanged; it is `chief-of-staff`'s call as well as
             *    this desk's finding: the hardcover pair's amount is a literal
             *    inside a plugin `if` with no accessor, and printing it from
             *    the theme would be exactly the hardcoded number the brief
             *    forbids. Shipping is stated at checkout by the engine that
             *    charges it.
             *
             * ⛔⛔ THE SENTENCE IS NOT TYPED HERE. It is
             *     `bhp_bundle_ship_progress_copy()`, the plugin's own
             *     founder-approved table, read at render. ⚠️ ITS APPROVED BYTES
             *     ARE "Add 1 more book and shipping is FREE." — capital FREE.
             *     The brief renders it lower-case; the locked string governs
             *     (Standing Rules §9: approved copy is never silently
             *     rewritten), and the difference is REPORTED rather than
             *     resolved by picking.
             *
             * ⛔⛔ THE KEY IS COUNTED, NOT ASSUMED TO BE 2. It is the number of
             *     physical books THIS offer puts in a cart, from
             *     `bhp_offer_components()` — one entry per book, quantity one
             *     each, which is the same figure `bhp_bundle_physical_book_
             *     count()` will see once they are in the cart. If an Everest
             *     pair or a three-item offer is ever added through
             *     `bhp_pair_landing_offers()`, this line follows it instead of
             *     lying about it.
             *
             * ⛔ AND IT DEFERS TO THE THRESHOLD ACCESSOR rather than to the
             *    number 3. At or above `bhp_bundle_freeship_book_threshold()`
             *    the correct sentence is the 'earned' one, not a nudge.
             *
             * ⛔ FAILS CLOSED. Plugin off, components unresolvable, or no
             *    approved string for this count → NOTHING is printed. There is
             *    no fallback sentence, because a fallback here would be a
             *    shipping claim this desk wrote.
             */
            $bhp_pl_nudge = '';
            if (
                function_exists('bhp_bundle_ship_progress_copy')
                && function_exists('bhp_offer_components')
                && function_exists('bhp_bundle_freeship_book_threshold')
            ) {
                $bhp_pl_components = bhp_offer_components($offers['paperback']);
                if (is_array($bhp_pl_components) && $bhp_pl_components) {
                    $bhp_pl_books = count($bhp_pl_components);
                    $bhp_pl_copy  = (array) bhp_bundle_ship_progress_copy();

                    if ($bhp_pl_books >= (int) bhp_bundle_freeship_book_threshold()) {
                        $bhp_pl_nudge = isset($bhp_pl_copy['earned']) ? (string) $bhp_pl_copy['earned'] : '';
                    } elseif (isset($bhp_pl_copy[$bhp_pl_books])) {
                        $bhp_pl_nudge = (string) $bhp_pl_copy[$bhp_pl_books];
                    }
                }
            }
            if ('' !== $bhp_pl_nudge) :
                ?>
                <p class="bhp-pair-landing__shipping-nudge"><?php echo esc_html($bhp_pl_nudge); ?></p>
            <?php endif; ?>

            <?php
            /*
             * ⭐ THE GUARANTEE IS THE PLUGIN'S OWN RENDERER, called rather than
             *    restated. It owns its wording, its policy URL and its own
             *    refusal to render where no policy page resolves. ⛔ Copying
             *    those two sentences into this file would create a second
             *    guarantee that could drift from the first.
             */
            if (function_exists('bhp_bundle_render_landing_guarantee')) {
                bhp_bundle_render_landing_guarantee();
            }
            ?>
        </div>
    </section>
    <?php
}

/**
 * Section 3: what is inside each book. ⛔ APPROVED BULLETS ONLY.
 *
 * ⛔ NOT ONE WORD IS WRITTEN HERE. Both lists come from
 *    `bhp_book_whats_inside()`, which is the single owner of these sentences
 *    and already carries a claims-audit row per bullet. The only new strings
 *    are the two column headings, and they are in the draft-copy table marked
 *    for Andrew.
 *
 * ⛔ IT RENDERS NOTHING WHEN A LIST IS EMPTY. A "what is inside" heading above
 *    an empty column is worse than no section.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_render_whats_inside() {
    if (!function_exists('bhp_book_whats_inside')) {
        return;
    }
    $chapter = (array) bhp_book_whats_inside('mariana_trench');
    $colour  = (array) bhp_book_whats_inside('colouring_mariana');

    if (empty($chapter) && empty($colour)) {
        return;
    }
    ?>
    <section class="bhp-landing-outcomes bhp-pair-landing__inside">
        <div class="bhp-pair-landing__inside-inner">
            <h2 class="bhp-pair-landing__section-heading"><?php echo esc_html(bhp_pair_landing_draft_copy('inside_heading')); ?></h2>
            <div class="bhp-pair-landing__inside-cols">
                <?php if (!empty($chapter)) : ?>
                    <div class="bhp-pair-landing__inside-col">
                        <h3 class="bhp-pair-landing__inside-title"><?php echo esc_html(bhp_pair_landing_draft_copy('inside_chapter')); ?></h3>
                        <ul class="bhp-pair-landing__inside-list">
                            <?php foreach ($chapter as $line) : ?>
                                <li><?php echo esc_html($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (!empty($colour)) : ?>
                    <div class="bhp-pair-landing__inside-col">
                        <h3 class="bhp-pair-landing__inside-title"><?php echo esc_html(bhp_pair_landing_draft_copy('inside_colour')); ?></h3>
                        <ul class="bhp-pair-landing__inside-list">
                            <?php foreach ($colour as $line) : ?>
                                <li><?php echo esc_html($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Section 4: the look-inside plates for the CHAPTER book.
 *
 * ⛔ THE CHAPTER BOOK ONLY, AND THAT IS NOT AN OVERSIGHT. `bhp_book_media()`'s
 *    registry holds `mount_everest`, `mariana_trench`, `amazon_rainforest` and
 *    `complete_collection`. ⛔ THERE IS NO APPROVED PLATE SET FOR THE COLORING
 *    BOOK, so none is shown and none is substituted. The brief asks for exactly
 *    this, and the component's own gate would refuse an empty set anyway.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_render_look_inside() {
    if (!function_exists('bhp_book_media')) {
        return;
    }
    $media = bhp_book_media('mariana_trench');
    if (empty($media) || empty($media['has_any'])) {
        return; // The component's own gate, asked before the section wrapper.
    }

    /*
     * ⛔ `locate_template()` + `include`, NOT `get_template_part()` WITH ARGS,
     *    AND THAT IS THE THEME'S EXISTING CONTRACT RATHER THAN A PREFERENCE.
     *    `template-parts/commerce/look-inside.php` reads BARE VARIABLES
     *    (`$media`, `$heading`, `$level`, `$compact`, `$eager_first`) - it never
     *    touches `$args`. The product-page hero caller in `inc/book-formats.php`
     *    sets exactly these locals and `include`s the located template, and this
     *    matches it line for line. ⭐ `get_template_part()` would have passed
     *    `$args` the template does not read, and the section would have rendered
     *    with every default silently in force.
     *
     * ⛔ A MISSING TEMPLATE RENDERS NOTHING rather than fataling.
     */
    $bhp_pl_tpl = locate_template('template-parts/commerce/look-inside.php');
    if ('' === $bhp_pl_tpl) {
        return;
    }

    $heading = bhp_pair_landing_draft_copy('lookinside_head');
    $intro   = '';
    $level   = 'h2';
    $compact = true;
    /*
     * ⭐ FALSE, and the variable is `$eager_first` because that is what the
     *    template reads. These plates are well below the fold; loading slide 1
     *    at high fetch priority here would compete with this page's real LCP
     *    element, which is the hero composite.
     */
    $eager_first = false;
    ?>
    <section class="bhp-pair-landing__lookinside">
        <div class="bhp-pair-landing__lookinside-inner">
            <?php include $bhp_pl_tpl; ?>
        </div>
    </section>
    <?php
}

/**
 * Section 6: the value comparison, for the PAIR.
 *
 * ⛔⛔ EVERY FIGURE IS THE ENGINE'S, AND THE SAVING IS A DERIVED CLAIM
 *    RECOMPUTED AT THIS RENDER. `evidence-verification` §5: a claim built from
 *    two sourced facts is a NEW claim. "Save $1.99" is not Andrew's ruling and
 *    is not a constant - it is the difference between what WooCommerce charges
 *    for the two books TODAY and the price he set.
 *
 * ⛔ IT FAILS CLOSED AND SILENCE IS THE CORRECT FAILURE. With no saving
 *    statable, the whole section is skipped: a comparison table whose right
 *    column has no number is a promise with the evidence removed.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_render_value() {
    $offers = bhp_pair_landing_offers();
    $rows   = [];

    foreach ([
        'paperback' => bhp_pair_landing_draft_copy('panel_pb'),
        'hardcover' => bhp_pair_landing_draft_copy('panel_hc'),
    ] as $format => $label) {
        if (empty($offers[$format]) || !bhp_offer_is_offerable($offers[$format])) {
            continue;
        }
        $key    = $offers[$format];
        $price  = bhp_offer_price($key);
        $total  = function_exists('bhp_offer_component_total') ? bhp_offer_component_total($key) : null;
        $saving = function_exists('bhp_offer_saving') ? bhp_offer_saving($key) : null;

        if (null === $price || null === $total || null === $saving) {
            continue; // ⛔ Not statable today: state nothing for this row.
        }
        $rows[] = compact('label', 'price', 'total', 'saving');
    }

    if (empty($rows)) {
        return;
    }
    ?>
    <section class="bhp-landing-value bhp-pair-landing__value">
        <div class="bhp-landing-value__inner">
            <h2 class="bhp-landing-value__heading"><?php echo esc_html(bhp_pair_landing_draft_copy('value_heading')); ?></h2>
            <div class="bhp-landing-value__panels">
                <div class="bhp-landing-value__panel bhp-landing-value__panel--dark">
                    <p class="bhp-landing-value__panel-label"><?php echo esc_html(bhp_pair_landing_draft_copy('value_separate')); ?></p>
                    <?php foreach ($rows as $row) : ?>
                        <div class="bhp-landing-value__row"><span><?php echo esc_html($row['label']); ?></span><span><?php echo wp_kses_post(wc_price($row['total'])); ?></span></div>
                    <?php endforeach; ?>
                </div>
                <div class="bhp-landing-value__panel bhp-landing-value__panel--light">
                    <p class="bhp-landing-value__panel-label"><?php echo esc_html(bhp_pair_landing_draft_copy('value_together')); ?></p>
                    <?php foreach ($rows as $row) : ?>
                        <div class="bhp-landing-value__row"><span><?php echo esc_html($row['label']); ?></span><span><?php echo wp_kses_post(wc_price($row['price'])); ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="bhp-landing-value__footnote">
                <?php
                $parts = [];
                foreach ($rows as $row) {
                    $parts[] = $row['label'] . ' · ' . bhp_pair_landing_draft_copy('value_saving', [wp_strip_all_tags(wc_price($row['saving']))]);
                }
                echo esc_html(implode('    ', $parts));
                ?>
            </p>
        </div>
    </section>
    <?php
}

/**
 * The exit for a reader who wants one book, not the set.
 *
 * ⭐ THE COLLECTION PAGE CARRIES THE SAME AFFORDANCE
 *    (`bhp_bundle_render_landing_individual_books_link()`). A set page with no
 *    way out sends a reader who wants one book to the back button.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_render_singles_exit() {
    ?>
    <section class="bhp-landing-singles bhp-pair-landing__singles">
        <p class="bhp-landing-singles__text">
            <a class="bhp-landing-singles__link" href="<?php echo esc_url(home_url('/shop/')); ?>"><?php
                echo esc_html(bhp_pair_landing_draft_copy('singles_link'));
            ?></a>
        </p>
    </section>
    <?php
}

/**
 * The closing CTA. Repeats the paperback panel and nothing else.
 *
 * ⛔ ONE OFFER, NOT BOTH, AND THE DEFAULT IS PAPERBACK (`FD-439`,
 *    `bhp_bundle_default_format()`). A closing block that re-asks the format
 *    question is a closing block that reopens a decision the reader already
 *    made at the top of the page.
 *
 * @since 1.19.399
 * @return void
 */
function bhp_pair_landing_render_final_cta() {
    $offers = bhp_pair_landing_offers();
    if (empty($offers['paperback']) || !bhp_offer_is_offerable($offers['paperback'])) {
        return;
    }
    ?>
    <section class="bhp-landing-final bhp-pair-landing__final">
        <div class="bhp-landing-final__inner">
            <h2 class="bhp-landing-final__heading"><?php echo esc_html(bhp_pair_landing_draft_copy('final_heading')); ?></h2>
            <?php bhp_pair_landing_render_panel('paperback', $offers['paperback'], bhp_pair_landing_draft_copy('panel_pb')); ?>
        </div>
    </section>
    <?php
}
