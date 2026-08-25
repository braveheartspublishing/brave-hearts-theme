<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * READ-ALOUD TAKE-HOME LANDING — the decisions half of `/read-aloud/`.
 * Theme 1.19.290 (2026-08-24, `CYCLE166-CX-READALOUD-LANDING`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * WHAT THIS PAGE IS. Andrew does elementary read-aloud visits. At the end of
 * each one every child takes home a PRINTED COLORING PAGE from the Mariana
 * Trench coloring book, with a dynamic QR at the foot of the sheet. That QR
 * opens this page. So the scanner is not a cold visitor: it is a PARENT, at
 * home, that evening, holding the sheet their child just coloured, with a
 * child beside them who met Charlotte and Henry a few hours ago.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE PAGE IS SCHOOL-AGNOSTIC AND MUST STAY THAT WAY. THIS IS THE ONE
 *     RULE THAT DECIDES THE WHOLE DESIGN.
 * ---------------------------------------------------------------------------
 * ⛔ NO school name. NO date. NO grade. NO teacher. NO visit slug. NO city.
 *    Not in this file, not in `page-read-aloud.php`, not in the CSS, not in a
 *    query argument this page reads.
 * ⭐ WHY: ONE page serves EVERY visit, forever. The QR on the printed sheet is
 *    dynamic, but the paper is not — a sheet handed out in August is still in
 *    a kitchen drawer in March. A page that named the August school would be
 *    WRONG the moment it was scanned by anyone else, and STALE the moment the
 *    visit passed. School-agnostic is not a simplification here; it is the
 *    only shape that does not decay.
 * ⛔ IT IS THEREFORE NOT `/author-visits/` AND MUST NOT DRIFT INTO IT.
 *    `/author-visits/` is the SCHOOL-SPECIFIC surface: it lists schools, dates,
 *    times and per-visit order buttons, and its buttons carry `?bhp_visit=`
 *    which is what grants hand-delivery entitlement. ⛔ THIS PAGE SETS NO VISIT
 *    FLAG, CARRIES NO `bhp_visit` ARGUMENT AND GRANTS NO ENTITLEMENT. A parent
 *    who scans the QR gets the series and the free kit, not a pickup slot.
 *    (⚠ RECORDED, NOT RESOLVED: the take-home flyer's v3/v4 QR was built
 *    against `/author-visits/` — see the design-creative flyer lock. Whether
 *    the printed sheet's QR is repointed at `/read-aloud/` is Andrew's, and
 *    this file neither assumes it nor depends on it.)
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ THE FOUNDER AMENDMENT: BOTH COVERS, NOT ONE.
 * ---------------------------------------------------------------------------
 * Andrew Signore, this turn, RELAYED through the Chief of Staff and NOT
 * witnessed by this agent, verbatim: *"Show both the MT book and the MT
 * coloring book not just the coloring book"*.
 *
 * ⭐ SO THE CONTINUITY SECTION IS A PAIR, AND THE PAIR IS THE POINT. The
 *    coloring page in the child's hand came from ONE of these books; the story
 *    they heard came from the OTHER. Showing only the coloring book would
 *    answer "where did this page come from" and silently drop "what was the
 *    story" — which is the half that sells a chapter book.
 *
 * ⛔ EACH TILE RESOLVES ITS OWN COVER FROM ITS OWN PRODUCT AND NEVER BORROWS
 *    THE OTHER'S. This is `FD-549` applied at the one place it would be
 *    easiest to break: a chapter-book cover under the words "your coloring
 *    page came from this book" is a false statement assembled from two true
 *    facts. `bhp_read_aloud_continuity_pair()` below therefore returns each
 *    tile INDEPENDENTLY and FAILS CLOSED — an unresolved tile renders with no
 *    image rather than with the wrong one, and never falls back to its sibling.
 *
 * ⛔ NO IMAGE IS GENERATED, RETOUCHED OR SYNTHESISED. Both covers are the real
 *    published cover art already attached to the real WooCommerce product
 *    records, read through the product's own featured image.
 *
 * ⭐ IDS ARE NEVER HARDCODED. An attachment id is environment-local: staging's
 *    13 is not production's 13. Resolution is:
 *      · chapter book  → `bhp_get_series_adventures()['mariana_trench']`,
 *        the theme's existing series resolver, which since 1.19.276 EXCLUDES
 *        the colouring line by product id before its title matcher runs — so
 *        it cannot hand back the colouring cover here.
 *      · colouring book → `bhp_colouring_product_ids()['mariana']`, the bundle
 *        plugin's SKU-keyed registry (the SKU is the ISBN), then that
 *        product's own featured image.
 *    ⭐ VERIFIED LIVE ON STAGING 2026-08-24 by `wp eval`, not inferred:
 *       `bhp_colouring_product_ids()` returned `{"mariana":4065}` and the
 *       series resolver returned image_id 13 with the MT paperback permalink.
 *       Production carries the same colouring SKU (`9798996810840`, read
 *       read-only the same day), so both resolve there too.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ THE SECOND FOUNDER AMENDMENT (1.19.291): THE COMBO, AND IT GOES FIRST.
 * ---------------------------------------------------------------------------
 * Andrew Signore walked this page on staging, PASSED it ("very good") and
 * asked for one thing. RELAYED through the Chief of Staff and NOT witnessed by
 * this agent, verbatim: *"I think we need to add the combo of both of them as
 * well - like if they didnt buy MT they could buy both in one swoop - It
 * should be the first option as well"*.
 *
 * ⭐ WHY IT BELONGS ON THIS PAGE SPECIFICALLY, not just on /shop/. The two
 *    tiles above already establish that the child met ONE book and coloured a
 *    page from the OTHER. A parent who follows that reasoning arrives at
 *    "so I need both" — and until 1.19.291 the page answered that with two
 *    separate product links and left the parent to assemble the order
 *    themselves. The combo is the answer to a question the page itself asks.
 *
 * ⛔⛔ AND IT IS THE **EXISTING** OFFER, RESOLVED THROUGH THE EXISTING ENGINE.
 *     NO SECOND PRICING PATH IS BUILT HERE AND NONE MAY BE.
 *       · The offer is `mariana_pb_colouring` in `bhp_offer_catalog()`.
 *       · ⛔ It has NO product record and must never be given one — `FD-579`.
 *         An offer is a NAME for a set of real catalogue items plus the price
 *         Andrew ruled for buying them together (`FD-581`, $22.99).
 *       · ⛔ NO product id and NO price literal appears in this feature's
 *         files. Ids are environment-local; the price is `bhp_offer_price()`'s
 *         and is re-read live on every render, so it cannot go stale here.
 *       · ⭐ The money is a cart FEE created server-side by
 *         `bhp_offer_apply_fees()` from what is ACTUALLY in the cart. Adding
 *         the components IS applying the offer — the browser is never trusted
 *         with a figure and this page publishes none.
 *
 * ⭐ THE CONTROL IS `bhp_offer_render_module($key, …, $card = TRUE)` — the
 *    SHOP-GRID CARD MODE, byte-identical to the card `/shop/` renders, and
 *    that choice is load-bearing rather than convenient:
 *      · CARD mode emits `data-bhp-offer-panel`, the explicit per-form opt-in
 *        `interceptOfferForms()` (plugin 1.8.67) reads — so ADD TO CART opens
 *        the CART SIDE PANEL, which is what every other add-to-cart on this
 *        site does and what the brief requires.
 *      · PRODUCT mode would post `bhp_bundle_redirect=checkout` and throw the
 *        parent straight onto /checkout/. ⛔ On a page whose whole job is
 *        "here is the series, here is the free kit", a control that
 *        teleports to checkout is a different page.
 *      · ⭐ VERIFIED, not assumed: `bhp_bundle_drawer_assets()` enqueues
 *        `bundle-drawer.js` and localises `bhpDrawerData.offerAdds` on EVERY
 *        front-end page (its only guards are `is_admin()` and WC missing), and
 *        `interceptOfferForms()` binds a delegated document listener. So the
 *        panel path already reaches this template and NO new asset, endpoint
 *        or script is enqueued by this feature. The no-JavaScript floor is the
 *        module's own `bhp_offer_panel` field, which the plugin's POST handler
 *        resolves to a real product page.
 *
 * ⛔ R1.4 — NOTHING IS ADVERTISED THAT CANNOT BE BOUGHT, AND THE ORDER OF
 *    OPERATIONS IS THE ENFORCEMENT. `bhp_read_aloud_combo()` below renders the
 *    module FIRST and returns an empty array when it comes back ''. The
 *    template prints no heading, no eyebrow, no picture and no "or pick just
 *    one" line unless a real, buyable control exists. ⭐ That also makes the
 *    school-visit gate work for free: on a `bhp_visit`-flagged session
 *    `bhp_offer_is_offerable()` refuses the colouring half, the module returns
 *    '', and the combo simply is not on the page — while the two tiles above
 *    still are.
 *
 * ⛔ THE COMPOSITE, AND `FD-549` `R2.3` AT THE PLACE IT MATTERS MOST. The
 *    picture beside $22.99 is `bhp_offer_composite_attachment_id()`'s — a real
 *    photograph of BOTH books, produced by design-creative, registered under
 *    the slug `bhp-bundle-composite-mariana-pb-colouring`. ⛔ It NEVER falls
 *    back to one component's cover: a chapter-book cover beside $22.99 states
 *    that that book costs $22.99. When the slug does not resolve on an
 *    environment the combo renders WITH NO PICTURE. Degrade, never mix.
 *    ⭐ VERIFIED LIVE ON STAGING 2026-08-24 by `wp eval`: attachment id 4570,
 *       slug as above, alt "The Mariana Trench chapter book beside the Mariana
 *       Trench coloring book." — already American, already accurate.
 *
 * ⛔ THE WORDS ARE THE ENGINE'S, NOT A SECOND COPY OF THEM. Title, descriptor,
 *    price label, CTA and saving all come from `bhp_colouring_draft_copy()` —
 *    the same table `/shop/` reads. ⭐ Re-typing "The Mariana Trench: book +
 *    coloring book" here would be a second place for Andrew's copy to drift
 *    out of agreement with itself. Only the ONE line that is specific to this
 *    page — the read-aloud framing of why both books belong together — is
 *    authored in the template.
 *
 * ---------------------------------------------------------------------------
 * ⛔ AMERICAN SPELLING IN CUSTOMER-FACING TEXT. FOUNDER STANDING RULE.
 * ---------------------------------------------------------------------------
 * Andrew, 2026-08-24, relayed: **"coloring", never "colouring"**, in anything
 * a customer reads.
 *
 * ⭐ AUDITED, NOT ASSUMED. Every customer-visible string this feature emits was
 *    re-read this pass, plus every string it pulls from elsewhere:
 *      · this template's own copy — already American, zero changes needed;
 *      · `bhp_colouring_draft_copy()`'s offer strings — "The Mariana Trench:
 *        book + coloring book", "BOOK + COLORING BOOK", "The chapter book and
 *        its coloring book". American. VERIFIED LIVE by `wp eval`, not read
 *        from source;
 *      · the three image alt texts (chapter cover, colouring cover, composite)
 *        — read live off the real attachments. American.
 *    ⭐ The suite now ASSERTS this rather than trusting the audit: §12 fails on
 *       any British form in a customer-facing string.
 *
 * ⛔ WHAT DELIBERATELY KEEPS ITS BRITISH SPELLING, AND WHY THAT IS CORRECT:
 *    `bhp_colouring_product_ids()`, `bhp_colouring_draft_copy()`, the
 *    `'colouring'` tile key, the `--colouring` CSS modifier and the attachment
 *    slug `bhp-bundle-composite-mariana-pb-colouring`. ⛔ NONE of these is text
 *    a customer reads. They are a PHP API owned by another workstream, a CSS
 *    hook, and a media record that `bhp_offer_composite_slugs()` looks up by
 *    exact string. Renaming any of them would break a live contract to fix a
 *    spelling nobody sees — and the media-record rename is a WordPress write
 *    this desk has no authority to make. The rule is about customer-facing
 *    copy; this is the line between the two.
 *
 * ---------------------------------------------------------------------------
 * ⛔ ONE CAPTURE, AND IT IS THE EXISTING PARENT FUNNEL. NO NEW MACHINERY.
 * ---------------------------------------------------------------------------
 * `.claude/rules/funnels.md` governs and is obeyed literally:
 *   ⛔ NO third funnel is minted. No new storage prefix, no new event prefix,
 *      no new lead magnet, no new thank-you page, no new endpoint, no AJAX,
 *      no second Mailchimp path, and `assets/js/mariana-popup.js` is NOT
 *      forked — this page renders no `[data-bhp-popup]` element at all.
 *   ⭐ THE PAGE CALLS `template-parts/acquisition/signup-form.php` DIRECTLY,
 *      the same shared handler the parent landing page, the popup, the
 *      exit-intent modal and the footer capture all submit through. Same
 *      lead magnet (`reluctant_reader_adventure_kit`), same audience
 *      (`parents_families`), same thank-you (`adventure_kit_thank_you`,
 *      already whitelisted in `bhp_signup_success_redirect_pages`).
 *   ⭐ ONLY THE CONFIG IS EXTENDED — a distinct `context`, exactly as
 *      `school_visit`, `footer_capture`, `parent_popup_exit` and
 *      `parent_popup_ab` each did before it. That is the "extend the config,
 *      don't fork the engine" instruction discharged.
 *
 * ⛔ TEACHER FUNNEL: UNTOUCHED IN BOTH DIRECTIONS. This file reads and writes
 *    nothing under `bhp_mariana_popup*`, emits no `teacher_popup*` event and
 *    applies no educator tag. A parent who signs up here has no effect on the
 *    teacher funnel's storage or analytics state, and vice versa.
 *
 * ---------------------------------------------------------------------------
 * ⛔ COPY RAILS THIS FILE AND ITS TEMPLATE ARE HELD TO
 * ---------------------------------------------------------------------------
 *   · Andrew's I-voice. ⛔ NO "we", "us" or "our" in any customer-facing
 *     string. He is one author, not a marketing department, and a parent who
 *     just watched him read to their child would hear the switch.
 *   · ⛔ NO fabricated review, rating, testimonial, parent/teacher reaction,
 *     classroom result, statistic, award or endorsement. There are none to
 *     quote, so there are none on the page — ABSENT, not softened.
 *   · ⛔ NO PRICE **LITERAL** ANYWHERE IN COPY.
 *     ⚠️ REFINED AT 1.19.291, and the refinement is a narrowing, not a
 *        loosening. This rail previously read *"NO price anywhere in copy …
 *        Every commercial line is a LINK."* The reason behind it was always
 *        STALENESS: a figure typed into a template is a second copy of a
 *        number that lives on a product record, and it goes wrong silently the
 *        day the record changes.
 *     ⭐ The combo's figure is not that. It is `bhp_offer_price()`'s, resolved
 *        from the offer engine on every single render, and it is rendered by
 *        the engine's own module — not written into this feature's copy. It
 *        CANNOT go stale, which is the exact hazard the rail exists to stop.
 *     ⛔ SO THE RULE STANDS IN THE FORM THAT CARRIES ITS REASON: no price
 *        LITERAL in any string in these files, and the suite asserts it. The
 *        superseded absolute is preserved above so it is not re-derived and
 *        the movement is visible.
 *     ⭐ AND THE FOUNDER RULED THE TRADE HIMSELF: he asked for a buyable combo
 *        on this page. A purchase control with the price hidden is a worse
 *        page than one that shows it.
 *   · ⛔ Reading age is **6–9**. NEVER 5–9.
 *   · ⛔ NO design count, page count or chapter count is restated here. Those
 *     numbers are owned by the product pages and the colouring rail; a second
 *     copy is a second thing to keep true.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ---------------------------------------------------------------------------
 *   It writes NOTHING. No option, no meta, no session, no cookie, no order, no
 *   product, no price, no coupon, no stock, no shipping method. A view of
 *   `/read-aloud/` is a read.
 *
 *   ⚠️ SUPERSEDED AT 1.19.291, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
 *      RE-DERIVED. This paragraph previously ended: *"It adds no commerce
 *      mechanism — every buying control on the page is an ordinary link to a
 *      product page or to `/shop/`."* The first clause is STILL TRUE and is
 *      the one that matters; the second is now FALSE, because the combo is a
 *      real add-to-cart control.
 *
 *   ⭐ THE ACCURATE STATEMENT: this feature adds **no commerce MECHANISM**. It
 *      introduces no cart handler, no pricing path, no fee, no nonce, no POST
 *      endpoint and no product of its own. The one add-to-cart control on the
 *      page is `bhp_offer_render_module()`'s existing form, posting the
 *      existing `bhp_bundle_add` nonce to the existing plugin handler, priced
 *      by the existing `bhp_offer_apply_fees()`. ⛔ A VIEW of this page still
 *      writes nothing; a customer PRESSING that button writes a cart, exactly
 *      as pressing it on `/shop/` does.
 *
 * @package BraveHearts
 */

defined('ABSPATH') || exit;

/**
 * The template file this whole feature is scoped to.
 *
 * ⭐ ONE LITERAL, ONE PLACE. Three separate hooks below key off the template
 *    name; three separate string literals is how one of them silently stops
 *    matching after a rename.
 */
function bhp_read_aloud_template() {
    return 'page-read-aloud.php';
}

/**
 * TRUE on the read-aloud landing page, and nowhere else.
 *
 * ⛔ TEMPLATE-BASED, NOT SLUG-BASED, DELIBERATELY. A slug test would also
 *    match `/read-alouds/` (page id 108 on staging, a DIFFERENT and older
 *    page that already exists and is not this) and would break the moment
 *    Andrew renamed the page in the WordPress editor.
 */
function bhp_is_read_aloud_landing() {
    return is_page_template(bhp_read_aloud_template());
}

/**
 * The two continuity tiles — chapter book first, colouring book second.
 *
 * ⛔ FAILS CLOSED, PER TILE, AND NEVER SUBSTITUTES. A tile with no resolvable
 *    cover comes back with `image_id => 0` and the template renders its words
 *    without a picture. It does NOT borrow the sibling's cover: see the
 *    `FD-549` note at the top of this file.
 *
 * ⛔ NO TILE IS DROPPED WHEN ITS COVER IS MISSING. The founder amendment is
 *    "show BOTH" — the pair is the message, so both tiles render either way
 *    and only the artwork is conditional.
 *
 * @return array<int,array{key:string,image_id:int,alt:string,url:string}>
 */
function bhp_read_aloud_continuity_pair() {
    $pair = [];

    /*
     * ── Tile 1: THE CHAPTER BOOK — the book the story was read from. ─────────
     * Resolved through the theme's own series resolver, which already excludes
     * the colouring line by product id (1.19.276) before its title matcher can
     * confuse "…: The Mariana Trench Ocean Coloring Book" for the novel.
     */
    $chapter = ['key' => 'chapter', 'image_id' => 0, 'alt' => '', 'url' => ''];
    if (function_exists('bhp_get_series_adventures')) {
        $adventures = bhp_get_series_adventures();
        $mariana    = isset($adventures['mariana_trench']) ? $adventures['mariana_trench'] : [];
        $chapter['image_id'] = (int) ($mariana['image_id'] ?? 0);
        $chapter['alt']      = (string) ($mariana['image_alt'] ?? '');
        $chapter['url']      = (string) ($mariana['primary_url'] ?? '');
    }
    $pair[] = $chapter;

    /*
     * ── Tile 2: THE COLOURING BOOK — where the take-home sheet came from. ────
     * ⛔ EVERY CALL INTO PLUGIN TERRITORY IS `function_exists()`-GUARDED. With
     *    the bundle plugin deactivated this tile resolves to nothing and the
     *    page still renders, rather than fataling on a parent's phone.
     */
    $colouring = ['key' => 'colouring', 'image_id' => 0, 'alt' => '', 'url' => ''];
    if (function_exists('bhp_colouring_product_ids')) {
        $ids           = bhp_colouring_product_ids();
        $colouring_id  = (int) ($ids['mariana'] ?? 0);
        if ($colouring_id > 0 && 'publish' === get_post_status($colouring_id)) {
            $thumb = (int) get_post_thumbnail_id($colouring_id);
            $colouring['image_id'] = $thumb;
            $colouring['alt']      = $thumb ? (string) get_post_meta($thumb, '_wp_attachment_image_alt', true) : '';
            $colouring['url']      = (string) (get_permalink($colouring_id) ?: '');
        }
    }
    $pair[] = $colouring;

    /**
     * The read-aloud continuity pair.
     *
     * ⛔ A FILTER MAY NOT MAKE THE TWO TILES SHARE AN IMAGE. That is the one
     *    thing this whole structure exists to prevent.
     *
     * @param array $pair Two tiles, chapter book then colouring book.
     */
    return apply_filters('bhp_read_aloud_continuity_pair', $pair);
}

/**
 * The offer key for the combo shown on this page.
 *
 * ⛔ ONE LITERAL, ONE PLACE, AND IT IS AN OFFER KEY — NOT A PRODUCT ID, NOT A
 *    SKU, NOT A PRICE. `bhp_offer_catalog()` owns everything else about it.
 *
 * ⭐ FILTERABLE ON PURPOSE. The day the Everest or Amazon colouring books
 *    exist, their pair offers become purchasable on their own and a future
 *    read-aloud page for those titles needs a different key — not a fork of
 *    this file. ⛔ The filter can only change WHICH offer; it cannot invent
 *    one, because `bhp_offer_components()` still has to resolve it against the
 *    two approved catalogues.
 */
function bhp_read_aloud_combo_key() {
    /**
     * The pair offer advertised on the read-aloud landing page.
     *
     * @param string $key An offer key from `bhp_offer_catalog()`.
     */
    return (string) apply_filters('bhp_read_aloud_combo_key', 'mariana_pb_colouring');
}

/**
 * The combo block — the pair offer, rendered through the EXISTING shop card.
 *
 * ⛔⛔ THE MODULE IS RENDERED **BEFORE** ANYTHING ELSE IS DECIDED, AND THE
 *     ORDER IS THE WHOLE SAFETY PROPERTY. `bhp_offer_render_module()` is the
 *     single gate: it returns '' when the offer is not offerable on this
 *     environment, in this session, right now. By asking it first and
 *     returning an EMPTY ARRAY on '', this function makes it structurally
 *     impossible for the template to print a heading, a picture, a framing
 *     line or an "or pick just one" divider for a combo that cannot be bought
 *     (`R1.4`). ⛔ Do not reorder this so the copy is assembled first — that
 *     is exactly how a page ends up advertising a control it did not render.
 *
 * ⛔ CARD MODE (`$card = true`), SO THE SIDE PANEL OPENS. See the header note.
 *    ⛔ `$show_heading = false`, because this template supplies the `<h3>` —
 *       printing the module's own heading too would say the same thing twice
 *       in one card, the duplication the shop card's first staging read found.
 *
 * ⛔ EVERY CALL INTO THE OFFER LAYER IS `function_exists()`-GUARDED. With the
 *    bundle plugin deactivated, or the colouring rail absent, this returns []
 *    and the page renders complete and correct without it — rather than
 *    fataling on a parent's phone at nine in the evening.
 *
 * @return array{}|array{key:string,html:string,art:string,title:string,descriptor:string}
 *         Empty when there is nothing buyable to show.
 */
function bhp_read_aloud_combo() {
    if (!function_exists('bhp_offer_render_module')) {
        return [];
    }

    $key = bhp_read_aloud_combo_key();

    /*
     * ⭐ THE GATE. `bhp-offer--card` is the shop card's own class so the
     *    control keeps its shipped internal geometry; `read-aloud-combo__offer`
     *    is this page's hook and carries every visual override, scoped.
     */
    $html = (string) bhp_offer_render_module($key, 'bhp-offer--card read-aloud-combo__offer', false, true);
    if ('' === $html) {
        return []; // ⛔ Not buyable → not advertised. No copy, no picture, nothing.
    }

    /*
     * ⛔ R2.3 — DEGRADE, NEVER MIX. '' when no composite resolves on this
     *    environment. It never borrows a component's cover.
     */
    $art = function_exists('bhp_offer_composite_card_image')
        ? (string) bhp_offer_composite_card_image($key)
        : '';

    /*
     * ⛔ ANDREW'S WORDS, FROM THE ONE TABLE THAT HOLDS THEM. Not re-typed here.
     */
    $title      = function_exists('bhp_colouring_draft_copy') ? (string) bhp_colouring_draft_copy('offer_card_title') : '';
    $descriptor = function_exists('bhp_colouring_draft_copy') ? (string) bhp_colouring_draft_copy('offer_descriptor') : '';

    return [
        'key'        => $key,
        'html'       => $html,
        'art'        => $art,
        'title'      => $title,
        'descriptor' => $descriptor,
    ];
}

/**
 * This page's own scoped stylesheet — the same one-template pattern as
 * `bhp_enqueue_parent_landing_assets()`, so nothing here can leak sitewide.
 *
 * ⛔ NO JAVASCRIPT IS ENQUEUED BY THIS FEATURE, AND THAT IS A DESIGN DECISION,
 *    NOT AN OMISSION. The audience is a parent on a phone on home wi-fi who has
 *    just scanned a QR code. Everything on the page — the covers, the links,
 *    the signup form — is server-rendered and works with scripting off. A
 *    capture that depends on a script is a capture that can silently not
 *    happen.
 *
 * ⚠️ 1.19.291 — STATED PRECISELY, BECAUSE THE COMBO MAKES THE SLOPPY VERSION
 *    OF THIS SENTENCE FALSE. This feature still enqueues no script of its own,
 *    and the count of handles it registers is unchanged at one stylesheet.
 *    What it now DOES is render a control that the plugin's ALREADY-SITEWIDE
 *    `bundle-drawer.js` enhances into a side-panel open. ⭐ That is an
 *    enhancement, not a dependency: with scripting off the same form does a
 *    real POST and the plugin's handler lands the parent on the offer's own
 *    product page with the items in the cart. ⛔ The combo therefore adds no
 *    request, no handle and no failure mode to this page's critical path.
 *
 * ⛔ NO GOOGLE FONTS REQUEST. The sitewide `bhp-google-fonts` handle already
 *    carries every family this page uses (F1, 2026-08-03).
 */
function bhp_enqueue_read_aloud_assets() {
    if (!bhp_is_read_aloud_landing()) {
        return;
    }
    wp_enqueue_style(
        'bhp-read-aloud',
        get_template_directory_uri() . '/assets/css/read-aloud.css',
        ['bhp-style'],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_read_aloud_assets');

/**
 * No automatic popup on this page.
 *
 * ⭐ IT IS ALREADY THE DEDICATED SIGNUP DESTINATION, so an exit-intent modal
 *    offering the SAME Adventure Kit that is already open on the page in front
 *    of the visitor is a second ask for something they have not yet declined
 *    — the exact stacking `bhp_should_show_any_popup()` excludes the parent
 *    landing page and the four audience pages for.
 *
 * ⭐ DONE THROUGH THE SHIPPED `bhp_show_exit_intent_popup` FILTER RATHER THAN
 *    BY EDITING THAT FUNCTION'S TEMPLATE LIST. Same effect, and it keeps this
 *    feature's whole footprint inside its own two files plus one require line
 *    — which matters this cycle, because `functions.php` is shared ground and
 *    two other workstreams have uncommitted work in this tree.
 *
 * ⛔ IT SUPPRESSES ONLY THIS PAGE. Every other surface's exit-intent behaviour
 *    is byte-unchanged: the callback returns `$show` untouched off-template.
 *
 * @param bool $show Whether the exit-intent modal may render.
 * @return bool
 */
function bhp_read_aloud_suppress_exit_intent($show) {
    return bhp_is_read_aloud_landing() ? false : $show;
}
add_filter('bhp_show_exit_intent_popup', 'bhp_read_aloud_suppress_exit_intent');

/**
 * Mailchimp tags for a signup captured on the read-aloud landing page.
 *
 * ⭐ A SEPARATE `add_filter` CALL, NOT AN EDIT TO AN EXISTING CALLBACK — the
 *    same reasoning already recorded on the 2026-07-15 and 2026-08-04
 *    callbacks in `functions.php`: the proven Parent / Mariana / audience tag
 *    logic stays byte-untouched, so a mistake here cannot cost an existing
 *    funnel its tags.
 *
 * ⛔⛔ PRIORITY 20, AND THE PRIORITY IS LOAD-BEARING RATHER THAN COSMETIC.
 *     The callback at `functions.php:2460` branches on
 *     `$lead_magnet === 'reluctant_reader_adventure_kit'` — which IS this
 *     page's lead magnet — and returns a set ending in
 *     'Source: Parent Landing Page'. At the same priority this callback would
 *     be racing that one on registration order, and registration order here
 *     depends on where a `require_once` line happens to sit in a 300 KB file.
 *     Priority 20 makes the outcome a stated rule instead of an accident.
 *     ⭐ Asserted by CALLING the real filter in
 *        `tests/test-read-aloud-landing.php`, not by reading it.
 *
 * ⛔ NO TAG NAMES A RESOURCE THAT DOES NOT EXIST. This signup delivers the
 *    Reluctant Reader Adventure Kit and nothing else, so that is the resource
 *    tag — identical to every other placement of the same magnet. Only the
 *    SOURCE differs, which is the entire purpose of tagging this page apart:
 *    it lets Andrew see which signups came from a school visit without
 *    creating a second list, a second magnet or a second journey.
 *
 * ⛔ AUDIENCE IS PARENT/GRANDPARENT. ⛔ NOT educator, NOT teacher, NOT
 *    librarian — a read-aloud happens in a school, but the person scanning
 *    this QR is the parent at home. Tagging them as an educator would put them
 *    in the teacher funnel's segment and is exactly the cross-contamination
 *    `.claude/rules/funnels.md` forbids.
 *
 * @param array  $tags          Tags resolved so far.
 * @param string $context       Signup context.
 * @param string $audience_type Normalised audience.
 * @param string $lead_magnet   Lead-magnet key.
 * @param string $source_page   Source page URL.
 * @return array
 */
function bhp_read_aloud_mailchimp_tags($tags, $context, $audience_type = '', $lead_magnet = '', $source_page = '') {
    if ('read_aloud_landing' !== $context) {
        return $tags;
    }

    return ['Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Read-Aloud Visit'];
}
add_filter('bhp_mailchimp_signup_tags', 'bhp_read_aloud_mailchimp_tags', 20, 5);
