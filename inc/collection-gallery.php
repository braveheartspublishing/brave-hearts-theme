<?php
/**
 * Brave Hearts — Complete Collection gallery on the funnel pages.
 *
 * WHAT THIS IS
 * ------------
 * Six pages pitch the Complete Collection in words and show no product:
 * the homepage, the parent Adventure Kit landing page, the three audience
 * landing pages (gift buyers, organizations, educators) and /books/. The
 * approved Collection media set already exists and already resolves on both
 * environments. This file puts a small, page-appropriate subset of it into
 * each of those six pitches.
 *
 * It adds NO new component, NO new markup language and NO second gallery
 * engine. It reuses `template-parts/commerce/look-inside.php` and
 * `bhp_book_media('complete_collection')` exactly as the product pages and
 * the Complete Collection page already do.
 *
 * Specification: Business OS `WORKING-DRAFTS\commerce-cx\
 * OVERNIGHT-2026-08-03-INTEGRATION-SPEC.md` (Pippin / `commerce-cx`,
 * 2026-08-02). Placements, subsets, headings and "do not disturb" lists are
 * that document's, not this file's.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 2026-08-09 (`CYCLE148-LD-16`) — THE WORD "SUBSET" NO LONGER APPLIES.
 *     The two paragraphs above are PRESERVED VERBATIM and are now HISTORY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE RULING. Andrew Signore, 2026-08-09, verbatim (⚠ RELAYED through
 *    `chief-of-staff`; NOT witnessed first-hand by this session):
 *
 *        "do a pass on all pages that have a carosel of the ecollection they
 *         need to be all exactly the same as the collection page- that has
 *         all the photos"
 *
 * ⛔ THIS FILE NO LONGER CHOOSES WHICH SLIDES A PAGE SEES. Every entry in the
 *    placement map now takes `bhp_collection_carousel_slugs()`, which reads
 *    the `complete_collection` registry entry in `inc/book-media.php`. That
 *    registry entry is the single source of truth for all seven surfaces —
 *    /complete-collection/ (which renders it directly, unchanged) plus the six
 *    pages in the map below.
 *
 * ⭐ WHAT THIS FILE STILL DECIDES, and it is worth being exact about, because
 *    "make them all the same" could be over-read into deleting the file:
 *      - WHETHER a page carries the carousel at all (the map's keys);
 *      - WHERE in that page it renders (the template's own call site);
 *      - the section HEADING, which is that section's own introduction and
 *        genuinely differs per audience;
 *      - whether the heading is visible (`heading_hidden`, homepage only);
 *      - whether the cream/forest/gold chrome applies (`collection`).
 *    None of those is media selection. The MEDIA is now uniform by
 *    construction.
 *
 * ⛔ POINT 1 BELOW ("THE SUBSET IS SLICED IN THE CALLER") STILL BINDS AND IS
 *    NOT RELAXED. The registry is still never altered by this file, and the
 *    selection still happens on already-resolved items. What changed is the
 *    CONTENT of the selection, not the mechanism — which is why the fail-closed
 *    behaviour, the slug-not-position rule and the one-instance-per-page rule
 *    all continue to hold with no change.
 *
 * ---------------------------------------------------------------------------
 * FOUR THINGS THIS FILE EXISTS TO GET RIGHT. Each was a predicted failure.
 * ---------------------------------------------------------------------------
 *
 * 1. THE SUBSET IS SLICED IN THE CALLER, NEVER THROUGH `bhp_book_media`.
 *    The `bhp_book_media` filter is applied inside `bhp_book_media_registry()`
 *    on EVERY call on EVERY page. A filter that trimmed the Collection set to
 *    three slides for the gift page would also trim `/complete-collection/`'s
 *    own nine-slide hero gallery — a silent regression on the page that
 *    converts best. So: resolve the full approved set, then select from the
 *    ALREADY-RESOLVED items here. The registry is never altered.
 *
 * 2. SELECTION IS BY SLUG, NEVER BY POSITION. Positions shift the moment
 *    anyone adds a slide to the registry; slugs do not. An image is matched on
 *    its resolved attachment id; a video on its resolved poster id, because a
 *    resolved video item carries no `id` of its own.
 *
 * 3. THE ENQUEUE PREDICATE AND THE RENDER PREDICATE ARE THE SAME PREDICATE.
 *    Both call `bhp_cx_collection_gallery_config()`. If a page is not in the
 *    map, neither the assets nor the markup appear. They cannot drift apart,
 *    because there is only one of them.
 *
 * 4. EXACTLY ONE GALLERY INSTANCE PER PAGE. `look-inside.php` derives its DOM
 *    id from the media key, so two renders of `complete_collection` on one
 *    page would emit two elements with the same id, two `aria-labelledby`
 *    targets pointing at it and two lightboxes — all of which would still look
 *    correct on screen while being wrong in the accessibility tree and in
 *    analytics attribution. `bhp_cx_render_collection_gallery()` therefore
 *    renders once per request and is a no-op afterwards.
 *
 * FAIL-CLOSED, exactly like the component it reuses: if the media does not
 * resolve on this environment, or the named subset resolves to nothing, this
 * file renders nothing, enqueues nothing, and the page is byte-identical to
 * what it was before. No empty frame, no placeholder, no broken image.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

/**
 * The placement map — the single source of truth for this feature.
 *
 * Keyed by the TEMPLATE FILE WordPress actually chose for the request (see
 * `bhp_cx_current_template_basename()`), so it cannot be fooled by a page
 * being reached through a slug rather than an assigned Page Template, or
 * vice versa.
 *
 * `items` are attachment SLUGS from the `complete_collection` registry entry.
 * For a video, name its POSTER slug — that is what a resolved video item
 * carries and it is unique per video.
 *
 * `collection` applies the cream/forest/gold chrome. It is on for the three
 * audience pages and the parent landing page, whose own page systems use that
 * palette, and off for the homepage and /books/, which have no cream system
 * to inherit.
 */
function bhp_cx_collection_gallery_map() {

    /*
     * ---------------------------------------------------------------------
     * FD-40 — THE UNIFORM FUNNEL SET. One list, referenced by every funnel
     * page, so the pages cannot drift apart by being edited one at a time.
     * ---------------------------------------------------------------------
     *
     * Andrew Signore, 2026-08-03, recorded verbatim at Business OS
     * `FOUNDER-DECISIONS-2026-08-01.md` FD-40 (relayed, not witnessed here):
     * "the flip through of the everest book should be added along side the
     * mariana trench flip through on all the funnel pages… each funnel
     * [having] a different set… should stay consistent unless there was a
     * reason… Otherwise the format looks great."
     *
     * The standing rule FD-40 establishes is broader than the media change:
     * FUNNEL MEDIA STAYS CONSISTENT ACROSS AUDIENCES UNLESS THERE IS A STATED
     * REASON TO DIFFER. Per-audience variation is now the exception that
     * requires a written justification, not the default. If you are about to
     * give one page its own list, write the reason next to it — as the
     * educator entry below does — or do not do it.
     *
     * The educator-spread 4th slide was offered to Andrew as an opt-in and
     * he did not take it. DEFAULT = UNIFORM. Do not add a 4th slide to this
     * list without his words.
     */
    $uniform = [
        'collection-look-01-three-books-v2',   // composite: all three books
        'mariana-look-01-poster',              // video: The Mariana Trench flip-through
        'everest-look-01-poster-v2',           // video: Mount Everest flip-through
    ];

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 2026-08-09 (`CYCLE148-LD-16`) — `$uniform` ABOVE IS SUPERSEDED.
     *     It is PRESERVED, UNUSED, and deliberately NOT deleted: FD-40's
     *     standing rule ("funnel media stays consistent across audiences
     *     unless there is a stated reason to differ") is not repealed by the
     *     2026-08-09 ruling — it is satisfied more strictly than it asked for,
     *     and a future reader tracing FD-40 needs to find the list it created.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⭐ THE SET IS NOW WHATEVER THE COLLECTION PAGE SHOWS. One call, one
     *    list, every entry below. `bhp_collection_carousel_slugs()` derives it
     *    from the `complete_collection` registry entry in `inc/book-media.php`,
     *    so the order the founder specified on 2026-08-09 — three-book image,
     *    Mariana video, Everest video, Amazon video, then the dark-ground
     *    stills — lives in exactly one array, and this file cannot disagree
     *    with the Collection page even by accident.
     *
     * ⛔ FAIL-CLOSED IS UNCHANGED, and this matters more now than it did with
     *    three-slide lists. `bhp_cx_collection_media_subset()` drops any slug
     *    that does not resolve on the current environment, so an environment
     *    missing the new Amazon video renders the other nine slides rather
     *    than an error — which is exactly what production will do between the
     *    theme deploy and the media import if they are ever run out of order.
     *    ⚠ The production packet therefore imports media FIRST. That ordering
     *      is a deploy-sequence requirement, not a code requirement.
     *
     * ⛔ IF THE FUNCTION IS MISSING, this file degrades to `$uniform` rather
     *    than to nothing. That branch is unreachable in a correctly-deployed
     *    theme (both files are loaded by `functions.php` in the same request);
     *    it exists so a partial deploy shows the previous, approved gallery
     *    instead of an empty section.
     */
    $universal = function_exists('bhp_collection_carousel_slugs')
        ? bhp_collection_carousel_slugs()
        : $uniform;
    if (!$universal) {
        $universal = $uniform;
    }

    $map = [

        /*
         * ---- / (homepage) — between the claim and the ask.
         *
         * F18 (2026-08-03). Andrew, walk-2, verbatim: "Lets add a few more
         * pictures to the complete collection carosel on the home page."
         *
         * ⚠ THIS IS A DELIBERATE DEPARTURE FROM `$uniform`, AND FD-40 REQUIRES
         *   THE REASON TO BE WRITTEN HERE RATHER THAN LEFT TO BE INFERRED:
         *   FD-40's uniform-set rule governs the FUNNEL pages, and its "do not
         *   add a fourth slide without his words" caution is about those pages.
         *   These are his words, and they name the homepage specifically. The
         *   five funnel entries below still share `$uniform` and are unchanged.
         *
         * Order follows the same rule as the Collection page (F22): the
         * three-book composite first, then both flip-through videos, then
         * stills. Every slug is already an approved attachment in the
         * `complete_collection` registry — nothing new was generated, uploaded
         * or retouched, and a slug that does not resolve on an environment is
         * simply skipped by the selector.
         *
         * The added stills cost ZERO bytes on load: `look-inside.php` mounts
         * one slide at a time and the videos ship with empty `src` until
         * played. Measured after the F7 `sizes` fix, not assumed.
         */
        'front-page.php' => [
            /*
             * ⭐ B2 (2026-08-03). Andrew, walk-3, verbatim: "take out the old
             *    amazon photos with the wood table behind it".
             *
             * TWO SLUGS REMOVED FROM THIS PAGE ONLY, and each was DOWNLOADED
             * AND VIEWED at full size before removal rather than removed on
             * the strength of its filename:
             *   - `amazon-look-04-front-cover`  (attachment 652) -- the
             *     paperback lying on a light oak table, warm daylight, visible
             *     wood grain and table seams.
             *   - `amazon-look-02-brave-learning` (attachment 650) -- the
             *     Brave Learning spread held open on the same oak table, with
             *     a hand in frame.
             *
             * The six that remain were all viewed too and all share the same
             * dark studio ground: the three-book composite, both flip-through
             * posters, both remaining front covers, and the Everest "How Tall"
             * diagram. The carousel now reads as one shoot instead of two.
             *
             * ⛔ THE REGISTRY IS NOT TOUCHED AND NO FILE IS ALTERED OR DELETED.
             *    This is a caller-side selection on ONE page, exactly like the
             *    educator exception below. Both slugs still resolve, still sit
             *    in the `complete_collection` registry, and still render on
             *    /complete-collection/ and on the Amazon product page.
             *
             * ⚠️ FLAGGED, NOT ABSORBED: `amazon-look-02-brave-learning` is
             *    also the second slide on `page-audience-educators.php` below,
             *    so the same wood-table photograph is still live there.
             *    Andrew's instruction named the homepage carousel, so the
             *    educator page is left alone and the observation is in the
             *    build report for his call. Registered `CYCLE142-DEV-21`.
             *
             * ⭐ THAT FLAG IS NOW CLOSED — the paragraph above is preserved
             *    verbatim so the sequence is visible, but its last two
             *    sentences are SUPERSEDED. Andrew answered on 2026-08-03
             *    ("2. yes remove") and the slug was removed from
             *    `page-audience-educators.php` too. See the PART 2B block on
             *    that entry below. The slug remains in the registry and still
             *    renders on /complete-collection/ and the Amazon PDP.
             *
             * F18's original note (Andrew, walk-2: "Lets add a few more
             * pictures to the complete collection carosel on the home page")
             * still governs why this list departs from `$uniform`; walk-3
             * narrows WHICH pictures, not whether there are extra ones.
             */
            /*
             * ⭐ 2026-08-09 — F18's and B2's six-slug homepage list is
             *    SUPERSEDED by the parity ruling and replaced by `$universal`.
             *    Both notes above are preserved because they record why the
             *    wood-table Amazon photographs left this carousel, and that
             *    reasoning is still live: those two slugs are not in
             *    `$universal` either, because the Amazon slides moved to the
             *    studio-navy renders on 2026-08-09. The homepage gains the
             *    Mariana Brave Learning spread, the two navy Amazon slides and
             *    the new Amazon video; it loses nothing it had.
             */
            'items'      => $universal,
            'heading'    => __('All three books', 'brave-hearts'),
            /*
             * ⭐ 1.19.182 (2026-08-05) — CYCLE144-LD-111. HOMEPAGE ONLY: the
             *    heading is kept for assistive technology and hidden from the
             *    screen.
             *
             * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED
             * through the Chief of Staff — NOT witnessed first-hand by the
             * agent that wrote this): remove the small "All three books"
             * label above the gallery as a redundancy.
             *
             * ⭐ IT IS A REDUNDANCY BECAUSE OF WHERE THIS GALLERY SITS. On the
             *    homepage it renders INSIDE the Best Value box, four lines
             *    under a description that already reads "All three
             *    adventures: the Mariana Trench, Mount Everest and the
             *    Amazon." Both are visible in the same eyeful. On every other
             *    page in this map the heading is the section's own
             *    introduction and carries real information, which is why this
             *    flag is set on exactly one entry.
             *
             * ⛔ THE STRING IS NOT DELETED AND THE ELEMENT IS NOT REMOVED.
             *    See the note in `template-parts/commerce/look-inside.php`:
             *    the gallery region is `aria-labelledby` this heading, so
             *    deleting it would strip the region's accessible name. It is
             *    still in the DOM, still the aria target, still announced.
             *
             * ⚠ /books/ IS DELIBERATELY LEFT ALONE and still shows the
             *   visible heading — see the `page-books.php` entry below. That
             *   is a divergence, it is flagged for Andrew rather than
             *   absorbed, and it is a one-line change if he wants it matched.
             */
            'heading_hidden' => true,
            'collection' => false,
        ],

        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.213 (2026-08-09/10, `CYCLE150-LD-01`) — THE FOUR FUNNEL
         *     ENTRIES BELOW MOVE INTO THE HERO, DIRECTLY UNDER THE PRIMARY CTA.
         *     Stated ONCE, here, because it is one decision applying to four
         *     entries and repeating it four times is how these files drift.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐ THE SPEC. Andrew Signore, relayed verbatim by `chief-of-staff`
         *    in the build brief (⛔ NOT witnessed first-hand by this agent) —
         *    the definitive structure for every funnel page, in order:
         *
         *      1. Heading   2. Paragraph   3. Primary CTA (each funnel's own)
         *      4. THE UNIVERSAL COLLECTION CAROUSEL, DIRECTLY UNDER THE CTA,
         *         "mostly visible" on landing
         *      5. The checkmark selling angles
         *      6. The Best Value buy section, raised to follow directly —
         *         "So they can buy easier on the page."
         *
         *    …plus: the secondary "Explore the Collection" CTA is REMOVED
         *    ("it will distract from the main CTA"), and the static three-book
         *    hero image is REMOVED because slide 1 of this very carousel IS
         *    that three-book image. His words: "This needs to be on every
         *    funnel now."
         *
         * ⛔ NOTHING ABOUT THE MEDIA CHANGES. Every funnel entry still takes
         *    `$universal` — the same ten slides as /complete-collection/, from
         *    the same `bhp_collection_carousel_slugs()` call. The 2026-08-09
         *    parity ruling is untouched. What moved is WHERE the one instance
         *    renders, which this file has always owned (see the head note:
         *    "WHERE in that page it renders (the template's own call site)").
         *
         * ⛔ STILL EXACTLY ONE INSTANCE PER PAGE. `bhp_cx_render_collection_
         *    gallery()` is a render-once no-op, and the four templates each
         *    moved their single call site rather than adding a second one —
         *    which is the defect head-note point 4 exists to prevent (two
         *    elements sharing a DOM id, two `aria-labelledby` targets, two
         *    lightboxes, all of it invisible on screen and wrong underneath).
         *
         * ⭐ TWO PLACEMENT KEYS ARE ADDED, and they are placement DATA exactly
         *    like `heading` and `collection` — the template still passes no
         *    arguments, so the enqueue gate and the render call cannot drift:
         *
         *    `eager_first` — ⛔ THIS ONE IS LOAD-BEARING AND IS NOT COSMETIC.
         *      `bhp_cx_render_collection_gallery()` hardcoded `false` with the
         *      comment "Below the fold on every one of these six pages". ⚠ THAT
         *      SENTENCE IS NOW FALSE for these four. Leaving it false would
         *      lazy-load the LCP element of every funnel page and cost exactly
         *      the metric the placement is meant to win. `true` here, and
         *      deliberately still ABSENT (false) on `front-page.php` and
         *      `page-books.php`, where the gallery genuinely is below the fold
         *      and eager-loading it would steal priority from their real LCP.
         *
         *    `heading_level` — in the hero the nearest preceding heading is the
         *      page `<h1>`, not an `<h2>`, so the old blanket `h3` skipped a
         *      level. `h2` here; the two below-the-fold entries keep `h3`,
         *      whose insertion points do still sit under an `<h2>`.
         *
         * ⭐ `heading_hidden` IS SET ON ALL FOUR, and the homepage's own note
         *    (above) is the precedent, not a new idea. The founder's ordered
         *    list runs CTA → carousel with nothing between them, and a visible
         *    section title in that gap both contradicts the order and spends
         *    ~40px of the fold budget the same brief requires. ⛔ THE STRING IS
         *    NOT DELETED AND THE ELEMENT IS NOT REMOVED — the gallery region is
         *    `aria-labelledby` this heading, so removing it would strip the
         *    region's accessible name. It stays in the DOM, stays the aria
         *    target, stays announced. Each page keeps its OWN heading text, so
         *    the audience-specific accessible names survive intact.
         *
         * ⚠ FLAGGED, NOT ABSORBED: the section headings below ("One
         *   flip-through", "What arrives", "What your program receives",
         *   "Inside the books") were written to be READ on screen inside a
         *   framed section. They are now screen-reader-only. Making any of them
         *   visible again is a one-line change per entry, and it is Andrew's
         *   call, not this file's.
         */

        /*
         * ---- /reluctant-reader-adventure-kit/
         * The section's own <h2> is "You can tell in one flip-through." and
         * the page contained no flip-through. Both flip-throughs are now in
         * the uniform set, so the claim is over-satisfied rather than unmet.
         *
         * ⚠ 1.19.213: that <h2> is on the SEE INSIDE section further down the
         *   page, which is where this gallery used to render. The gallery has
         *   moved to the hero; the section and its heading are untouched and
         *   still stand on their own copy.
         */
        'page-reluctant-reader-adventure-kit.php' => [
            'items'         => $universal,
            'heading'       => __('One flip-through', 'brave-hearts'),
            'heading_hidden' => true,
            'heading_level' => 'h2',
            'eager_first'   => true,
            'collection'    => true,
        ],

        /*
         * ---- /gift-buyers-guide/
         * The composite answers "what arrives in the box"; the two
         * flip-throughs answer "is it a real, well-made book" — which is the
         * giver's actual question about an object they hand over unseen.
         */
        'page-audience-gift-buyers.php' => [
            'items'         => $universal,
            'heading'       => __('What arrives', 'brave-hearts'),
            'heading_hidden' => true,
            'heading_level' => 'h2',
            'eager_first'   => true,
            'collection'    => true,
        ],

        /*
         * ---- /organizations-community-reading-kit/
         * Composite = what a bulk set looks like. The flip-throughs = they are
         * real printed books, which is what makes a programme purchase
         * defensible to whoever signs off.
         */
        'page-audience-organizations.php' => [
            'items'         => $universal,
            'heading'       => __('What your program receives', 'brave-hearts'),
            'heading_hidden' => true,
            'heading_level' => 'h2',
            'eager_first'   => true,
            'collection'    => true,
        ],

        /*
         * ---- /educators-adventure-learning-toolkit/
         * ⛔ THE ONE DELIBERATE EXCEPTION TO FD-40's UNIFORM SET, AND THE
         *    REASON IS WRITTEN HERE BECAUSE FD-40 REQUIRES ONE.
         *
         * INTERIORS ONLY — no covers, no composite. A teacher does not need to
         * be sold the object; they need to see the page a student will be
         * looking at, and they are the one audience that will READ the printed
         * words inside the photograph rather than glance at it.
         *
         * `mariana-look-03-depth-diagram-brave-learning` was the third slide
         * here until 2026-08-03 and has been DROPPED FROM THIS PAGE ONLY.
         * `commerce-cx` downloaded and viewed the full-size file and found four
         * visible text artefacts on its LEFT page — "Mount Ererest", "Hodal
         * Zone" (should be Hadal), "Very few creatures lire here", "The despert
         * spet of all" — from the Higgsfield regeneration pass. Its right page
         * (the Brave Learning companion questions) is clean; the left page is
         * not. Recorded as `CYCLE141-CX-40`; the same artefact strings are
         * cited, unattributed to a file, at `docs/ROADMAP.md`.
         *
         * ⛔ THE REGISTRY IS NOT TOUCHED AND THE ASSET IS NOT ALTERED. The
         *    Mariana set is protected until the queued authentic reshoot runs
         *    (`docs/ROADMAP.md`, and `docs/CURRENT_TASK.md`: "must not be
         *    removed, replaced, regenerated, retouched or reordered"). This is
         *    a caller-side presentation choice on one page — the slide is
         *    still in the registry and still renders on /complete-collection/
         *    and on the Mariana product page, exactly as Andrew approved.
         *
         * Ruled by `chief-of-staff` 2026-08-03 as a reversible presentation
         * choice inside FD-40's stated-reason exception. ⚠️ ANDREW MAY VETO:
         * restoring the uniform set here is a one-line change — replace this
         * `items` list with `$uniform`.
         *
         * ⭐ PART 2B (2026-08-03) — `CYCLE142-DEV-21` CLOSED, ON ANDREW'S WORD.
         *
         * The B2 note on `front-page.php` above flagged that
         * `amazon-look-02-brave-learning` — the Brave Learning spread held open
         * on the light oak table, hand in frame — was removed from the homepage
         * carousel but was STILL LIVE as the second slide here, so the same
         * wood-table photograph survived Andrew's "take out the old amazon
         * photos with the wood table behind it" on this page. That was
         * deliberately left for his call and registered `CYCLE142-DEV-21`.
         *
         * Andrew's call, 2026-08-03: "2. yes remove" — relayed through
         * `chief-of-staff`, ⛔ NOT witnessed by this agent. The slug is removed
         * FROM THIS PAGE ONLY.
         *
         * ⛔ AGAIN: THE REGISTRY IS NOT TOUCHED, NO FILE IS ALTERED OR DELETED,
         *    NO ATTACHMENT IS UNLINKED. `amazon-look-02-brave-learning`
         *    (attachment 650) still resolves, still sits in the
         *    `complete_collection` registry, and still renders on
         *    /complete-collection/ and on the Amazon product page.
         *
         * ⚠️ CONSEQUENCE, STATED RATHER THAN LEFT TO BE DISCOVERED: this page
         *    now has exactly ONE slide. `look-inside.php` already guards its
         *    prev/next arrows and its dot strip behind `$total > 1`, so a
         *    single-slide gallery renders as a plain captioned still with no
         *    dead controls — measured on staging, not inferred. If the queued
         *    `everest-look-08-brave-learning` photograph is ever taken
         *    (`CYCLE141-CX-41`), it slots in here and the carousel controls
         *    return by themselves.
         *
         * The remaining slide was viewed at full size and found clean, every
         * word legible.
         */
        /*
         * ⭐⭐ 2026-08-09 (`CYCLE148-LD-14`) — THE EDUCATOR EXCEPTION IS
         *     RESOLVED BY THE PARITY RULING. This is the only entry where the
         *     ruling costs something, so it is stated plainly rather than
         *     absorbed into a one-line diff.
         *
         * ⛔ WHAT IS LOST. The interiors-only rationale above ("a teacher does
         *    not need to be sold the object; they need to see the page a
         *    student will be looking at") was a good argument and it is now
         *    overruled by an explicit founder instruction that names every
         *    page with a collection carousel. This page now shows the same ten
         *    slides as every other surface, covers and composite included.
         *
         * ⭐ WHAT COMES BACK, AND WHY THE 2026-08-03 REMOVAL REASON IS GONE.
         *    B2 and `CYCLE142-DEV-21` removed `amazon-look-02-brave-learning`
         *    from this page because it was a WOOD-TABLE photograph and Andrew
         *    had said "take out the old amazon photos with the wood table
         *    behind it". That instruction is fully honoured by `$universal`:
         *    the wood-table slug is not in it. What returns here is
         *    `amazon-look-02-brave-learning-navy-v2` — the STUDIO-NAVY render
         *    of the same spread, which is a different asset on the approved
         *    dark ground. ⭐ The removal reason is therefore not overridden;
         *    it no longer applies to anything in the list.
         *
         * ⚠ ONE ARTEFACT RETURNS TO THIS PAGE AND IT IS FLAGGED, NOT HIDDEN.
         *   `mariana-look-03-depth-diagram-brave-learning` was dropped from
         *   THIS page on 2026-08-03 (`CYCLE141-CX-40`) for four visible text
         *   artefacts on its left page — "Mount Ererest", "Hodal Zone", "Very
         *   few creatures lire here", "The despert spet of all". ⭐ RE-VERIFIED
         *   BY VIEWING THE FULL-SIZE FILE ON 2026-08-09, not taken on trust:
         *   all four are still present. It has been live on
         *   /complete-collection/ and the Mariana product page throughout, so
         *   this is a change of exposure, not a new defect — but educators are
         *   the one audience that reads the printed words in the photograph,
         *   which is why it was pulled from here in the first place. The
         *   queued authentic Mariana reshoot (`docs/ROADMAP.md`) is the fix.
         *   Escalated to Andrew through Gandalf; restoring the exception is a
         *   one-line change back to a literal list.
         *
         * ⭐ The single-slide consequence noted above is also gone: this page
         *    now has ten slides, so its arrows, counter and thumb rail return.
         */
        'page-audience-educators.php' => [
            'items'         => $universal,
            'heading'       => __('Inside the books', 'brave-hearts'),
            'heading_hidden' => true,
            'heading_level' => 'h2',
            'eager_first'   => true,
            'collection'    => true,
        ],

        // ---- /books/ — inside the Collection banner.
        'page-books.php' => [
            'items'      => $universal,
            'heading'    => __('All three books', 'brave-hearts'),
            'collection' => false,
        ],
    ];

    /*
     * `/teachers/` is DELIBERATELY ABSENT. It is a teacher resource hub, and
     * its final CTA already links into the parent funnel's landing page — a
     * journey-routing question that is Andrew's, not a code defect. Adding a
     * commerce gallery there would deepen that crossover rather than fix it.
     * Recorded so a future session does not "complete the set" without the
     * decision. (Spec §3.7 / `CYCLE141-CX-8`.)
     */

    return apply_filters('bhp_cx_collection_gallery_map', $map);
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.235 (2026-08-17, `CYCLE162-LD-SHOP-CAROUSEL-V2`) — THE SHOP-ARCHIVE
 *     COMPLETE COLLECTION BANNER TAKES THE REAL CAROUSEL.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE RULING. Andrew Signore, 2026-08-17, verbatim (⚠ RELAYED through
 *    `chief-of-staff` in the build brief; NOT witnessed first-hand here), on
 *    1.19.234's static three-cover fan:
 *
 *        "No that is not very good- just use the collection page carousel
 *         image gallery that we already created - should be easy to swap in"
 *
 * ⛔ WHY THIS LIVES HERE AND NOT IN THE PLACEMENT MAP ABOVE. The map is keyed
 *    by the RESOLVED TEMPLATE BASENAME, and the shop archive resolves to
 *    WooCommerce's own `archive-product.php` — which is ALSO the template for
 *    every `product_cat` and `product_tag` archive. A map entry would have
 *    leaked the carousel onto every category archive, which is precisely the
 *    containment the banner's `is_shop()` guard exists to hold and which
 *    `tests/test-shop-collection-carousel.php` §2 asserts against rendered
 *    documents. So the shop gets its own predicate, gated on `is_shop()`
 *    itself rather than on a template filename that cannot distinguish them.
 *
 * ⭐ HEAD-NOTE POINT 3 STILL BINDS AND IS THE WHOLE DESIGN OF THIS FUNCTION:
 *    the enqueue gate (`bhp_book_enqueue_media_assets()` in
 *    `inc/book-formats.php`) and the render call
 *    (`bhp_woocommerce_shop_complete_collection_banner()` in `functions.php`)
 *    both call THIS function and nothing else. There is exactly one of it, so
 *    the assets and the markup cannot drift apart — a carousel whose CSS/JS
 *    did not load would render as a broken vertical list of every slide, which
 *    is the specific failure the brief named.
 *
 * ⭐ HEAD-NOTE POINT 4 (exactly one gallery instance per page) HOLDS BY
 *    CONSTRUCTION, not by luck: `bhp_cx_render_collection_gallery()`'s map has
 *    no shop entry, and `bhp_book_render_collection_hero_gallery()` fires on
 *    the bundle plugin's `bhp_bundle_landing_hero_media` action, which the shop
 *    archive never does. The shop banner is therefore the only consumer of the
 *    `complete_collection` DOM id on that page.
 *
 * ⛔ NO NEW MEDIA, AND NO SECOND SOURCE OF TRUTH. This returns
 *    `bhp_book_media('complete_collection')` unmodified — the identical call
 *    `bhp_book_render_collection_hero_gallery()` makes for /complete-collection/.
 *    Not a subset, not a re-ordering, not a filter. "The collection page
 *    carousel" means the collection page's carousel.
 *
 * FAIL-CLOSED, like every other consumer of this component: unresolved media
 * returns null, the banner renders its 1.19.233 copy-only markup, and nothing
 * is enqueued. No empty frame, no placeholder.
 *
 * @return array|null The `bhp_book_media()` shape, or null.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔⛔ `bhp_cx_shop_banner_gallery_media()` WAS DEFINED HERE AND WAS REMOVED
 *      AT 1.19.284 UNDER CARRIER ITEM 207.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE WHOLE HEAD-NOTE ABOVE IT — everything from "1.19.235 … THE SHOP-ARCHIVE
 *    COMPLETE COLLECTION BANNER TAKES THE REAL CAROUSEL" down to this line — IS
 *    NOW HISTORY. It is preserved verbatim and deliberately not deleted,
 *    because it records a founder ruling of 2026-08-17 that PUT the carousel on
 *    /shop/, and because its explanation of why the placement could not live in
 *    `bhp_cx_collection_gallery_map()` still binds: ⛔ THE SHOP ARCHIVE SHARES
 *    `archive-product.php` WITH EVERY `product_cat` AND `product_tag` ARCHIVE,
 *    so a map entry would leak a component across all of them. That trap is
 *    unchanged and a future placement must still avoid it.
 *
 * ⭐ WHY IT WENT: Andrew Signore, carrier item 207, 2026-08-21 (⚠️ RELAYED
 *    through `chief-of-staff`, ⛔ NOT witnessed first-hand by the agent that
 *    made this change) ruled the collection carousel OFF the shop page. ⛔ OFF
 *    THAT PAGE ONLY. The function had exactly two callers and both were that
 *    one feature — the banner in `functions.php` and the `is_shop()` enqueue
 *    branch in `inc/book-formats.php` — so with the banner gone it resolved
 *    media nobody rendered.
 *
 * ⛔⛔ NOTHING ELSE IN THIS FILE MOVED, AND THAT IS THE CONTAINMENT ITEM 207
 *    REQUIRES. `bhp_cx_collection_gallery_map()`, `bhp_cx_collection_gallery_
 *    config()`, `bhp_cx_collection_media_subset()` and
 *    `bhp_cx_render_collection_gallery()` are untouched, so /complete-collection/,
 *    the homepage, /books/ and the four funnel surfaces render exactly the
 *    carousel they rendered at 1.19.283. `tests/test-shop-collection-carousel.php`
 *    asserts BOTH halves of that: absent on /shop/, still present on
 *    /complete-collection/.
 *
 * ⭐ TO RESTORE: the function, the banner and the enqueue branch are all in the
 *    1.19.283 tree at commit `da7f0ed`.
 */

/**
 * The template file WordPress actually resolved for this request.
 *
 * Captured from `template_include`, which runs BEFORE `wp_head` and therefore
 * before `wp_enqueue_scripts` — so the enqueue gate and the template's own
 * render call are both asking about the same, already-decided file. Deriving
 * it any other way (`is_page_template()`, slug matching) would answer a
 * different question and could disagree with itself.
 */
function bhp_cx_current_template_basename() {
    static $basename = null;

    if (null !== $basename) {
        return $basename;
    }

    $basename = isset($GLOBALS['bhp_cx_template_file']) && is_string($GLOBALS['bhp_cx_template_file'])
        ? basename($GLOBALS['bhp_cx_template_file'])
        : '';

    return $basename;
}

add_filter('template_include', function ($template) {
    $GLOBALS['bhp_cx_template_file'] = $template;
    return $template;
}, 999);

/**
 * The ONE predicate. Returns the placement config for this request, or null.
 *
 * Null means: not one of the six pages, or the Collection media does not
 * resolve on this environment, or the named subset resolved to nothing. Every
 * one of those is a "render nothing, enqueue nothing" answer.
 */
function bhp_cx_collection_gallery_config() {
    static $resolved = null;
    static $done     = false;

    if ($done) {
        return $resolved;
    }
    $done = true;

    if (is_admin()) {
        return $resolved; // null
    }

    $map      = bhp_cx_collection_gallery_map();
    $basename = bhp_cx_current_template_basename();

    if ('' === $basename || !isset($map[$basename])) {
        return $resolved; // null
    }

    $config = $map[$basename];
    $media  = bhp_cx_collection_media_subset($config['items']);

    if (empty($media['has_any'])) {
        return $resolved; // null — fail closed, exactly like the registry does
    }

    $config['media'] = $media;
    $resolved        = $config;

    return $resolved;
}

/**
 * A page-scoped subset of the approved Complete Collection gallery.
 *
 * Takes attachment slugs and selects from the ALREADY-RESOLVED item list, so
 * an item whose attachment does not exist on this environment is simply
 * absent and the subset shrinks — it never renders an empty frame. That
 * mirrors the registry's own fail-closed rule rather than inventing a second
 * one.
 *
 * Order follows the order of `$slugs`, not the registry's, because the
 * narrative order differs per page (the parent page leads with the video; the
 * gift page leads with the composite).
 *
 * `count` and `has_any` are RECOMPUTED. The template reads both, and a stale
 * count would print "1 / 9" on a three-slide rail.
 *
 * @param string[] $slugs Attachment slugs. For a video, its POSTER slug.
 * @return array   The same shape `bhp_book_media()` returns.
 */
function bhp_cx_collection_media_subset(array $slugs) {
    $full = bhp_book_media('complete_collection');

    $empty = [
        'key'     => 'complete_collection',
        'items'   => [],
        'count'   => 0,
        'has_any' => false,
    ];

    if (empty($full['has_any']) || empty($full['items'])) {
        return $empty;
    }

    /*
     * Index the resolved items by attachment id. An image is keyed by its own
     * id; a video by its poster id, because a resolved video item has no `id`
     * and its poster is unique to it.
     */
    $by_attachment = [];
    foreach ($full['items'] as $item) {
        if ('video' === $item['type'] && !empty($item['poster_id'])) {
            $by_attachment[(int) $item['poster_id']] = $item;
        } elseif (!empty($item['id'])) {
            $by_attachment[(int) $item['id']] = $item;
        }
    }

    $items = [];
    foreach ($slugs as $slug) {
        $id = bhp_book_media_attachment_id($slug);
        if ($id && isset($by_attachment[$id])) {
            $items[] = $by_attachment[$id];
        }
    }

    if (!$items) {
        return $empty;
    }

    return [
        'key'     => 'complete_collection',
        'items'   => $items,
        'count'   => count($items),
        'has_any' => true,
    ];
}

/**
 * Render the gallery for the current page. Called from inside the template,
 * at the exact insertion point the specification names.
 *
 * Takes no arguments on purpose: the placement is data, in the map above, so
 * a template can never quietly pass a different subset or a different mode
 * than the one the enqueue gate reasoned about.
 *
 * Renders at most once per request (see head note, point 4).
 */
function bhp_cx_render_collection_gallery() {
    static $rendered = false;

    if ($rendered) {
        return;
    }

    $config = bhp_cx_collection_gallery_config();
    if (!$config) {
        return; // Fail closed: the page is exactly as it was.
    }

    $bhp_tpl = locate_template('template-parts/commerce/look-inside.php');
    if ('' === $bhp_tpl) {
        return;
    }

    $rendered = true;

    /*
     * Below the fold on every one of these six pages, so the first item must
     * NOT be eager/high-priority — that flag exists for the hero placements,
     * where item 0 genuinely is the LCP element. Passing it here would fetch a
     * large below-fold image at high priority and cost the homepage its LCP.
     *
     * ⭐ 1.19.213 — THE PARAGRAPH ABOVE IS PRESERVED AND IS NOW TRUE OF ONLY
     *    TWO OF THE SIX. It is deliberately not rewritten: its REASONING is
     *    still exactly right, and it is the reason `eager_first` is per-entry
     *    data rather than a blanket `true`. On the four funnel pages the
     *    gallery now renders in the hero directly under the primary CTA, so
     *    item 0 IS the LCP element there and lazy-loading it would be the
     *    mirror-image of the mistake this note warns about. `front-page.php`
     *    and `page-books.php` declare no `eager_first`, so they keep `false`
     *    and their own LCP keeps its priority.
     */
    $media       = $config['media'];
    $heading     = $config['heading'];
    /*
     * 1.19.182 — placement data, exactly like `heading` and `collection`.
     * Absent on every entry but the homepage's, so `!empty()` gives every
     * other page the pre-1.19.182 behaviour with no per-entry edit.
     */
    $heading_hidden = !empty($config['heading_hidden']);
    $collection  = !empty($config['collection']);
    $compact     = true;
    $hero        = false;
    /*
     * 1.19.213 — both of these are placement data now. Absent on the two
     * below-the-fold entries, so they keep the pre-1.19.213 behaviour with no
     * per-entry edit — the same shape `heading_hidden` already uses.
     *
     * `$hero` stays FALSE even for the four hero placements: it is not "is
     * this in a hero", it selects `look-inside.php`'s PRODUCT-hero markup mode,
     * which drops the heading element entirely and would take the gallery
     * region's accessible name with it. The visual hero treatment is CSS.
     */
    $eager_first = !empty($config['eager_first']);
    $level       = isset($config['heading_level']) && 'h2' === $config['heading_level']
        ? 'h2'     // hero placements: the nearest preceding heading is the <h1>
        : 'h3';    // below-the-fold placements already sit under an <h2>
    $intro       = '';

    include $bhp_tpl;
}
