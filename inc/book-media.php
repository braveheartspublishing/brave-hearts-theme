<?php
/**
 * Brave Hearts — per-book "Look Inside" media registry (2026-08-01).
 *
 * This file is the GATE, not the gallery. Every rule about which media may
 * appear on the storefront is enforced here, once, mechanically:
 *
 *   1. An item whose asset does not resolve is DROPPED from the gallery. A
 *      title left with no items renders no section at all — never an empty
 *      frame, never a placeholder, never a "coming soon".
 *   2. Assets are addressed by ATTACHMENT SLUG, never by a hardcoded
 *      attachment ID. Staging and production have genuinely different IDs for
 *      the same file, so an ID baked in here would resolve to the wrong image
 *      on the other environment.
 *   3. Alt text lives HERE, next to the asset, so it travels with the code and
 *      reads identically on every environment rather than depending on what
 *      somebody typed into the media library.
 *
 * The gallery is an ORDERED LIST of items. Each item is either a video or an
 * image, and both kinds share one stage in the UI, so the order below is the
 * order the customer clicks through.
 */

defined('ABSPATH') || exit;

/**
 * The approved media registry, keyed by the same book keys as
 * bhp_book_registry() in inc/book-formats.php.
 *
 * Item shapes:
 *   ['type' => 'video', 'mp4' => slug, 'webm' => slug, 'poster' => slug, 'label' => string]
 *   ['type' => 'image', 'slug' => slug, 'alt' => string]
 *
 * A video needs a poster AND at least one playable source, or it is dropped:
 * a bare <video> paints a black rectangle on first render, which is a defect
 * rather than a graceful degradation.
 *
 * Filter `bhp_book_media` to add or reorder a title's items without editing
 * this file.
 */
function bhp_book_media_registry() {
    return apply_filters('bhp_book_media', [

        /*
         * MOUNT EVEREST — full set supplied and approved by Andrew 2026-08-01.
         *
         * PROVENANCE NOTE, recorded so it is not re-derived from filenames by
         * a future session: this set originates from an AI-assisted production
         * pipeline (Higgsfield job IDs on every source file; the icefall
         * spread additionally carries IPTC/XMP `trainedAlgorithmicMedia` /
         * "Made with Google AI", and that XMP was deliberately PRESERVED when
         * the web derivative was encoded rather than stripped).
         *
         * Two items carry visible text artefacts that a print run would not
         * produce, flagged to Andrew before installation and approved by him
         * for staging:
         *   - everest-look-04-icefall-spread: running head reads
         *     "ADVENTURES OF CHARLOTTE AND IJENRS".
         *   - everest-look-07-back-cover: "ond it opens", "winding wountain
         *     trails", "breathioking landscopes", "the world is foll of big
         *     challenges", "Perfect fcr first chapter book readers".
         * Swapping either is a one-line change to its slug below.
         */
        'mount_everest' => [
            'items' => [
                /*
                 * VIDEO — position 2, immediately after the cover
                 * (Andrew, 2026-08-02).
                 *
                 * `poster` is the 16:9 stage image shown before playback;
                 * `thumb` is a square crop of the same frame, cut tight to the
                 * book so the rail tile stays recognisable at ~76px instead of
                 * showing a mostly-empty background. Both come from the same
                 * 0.6s frame, chosen because it is the sharpest in the clip:
                 * whole cover visible, evenly lit, no hand occlusion, no motion
                 * blur.
                 *
                 * WITHDRAWN FROM THE GALLERY, NOT DELETED (Andrew, 2026-08-02):
                 * the wood-table capture `everest-flipthrough-authentic*`
                 * (staging attachments 637/638/639) remains uploaded and intact
                 * for rollback and history. To restore it, add an item here
                 * with those three slugs — no re-upload needed.
                 */
                [
                    'type'   => 'video',
                    'mp4'    => 'everest-look-01-flip-through',
                    'webm'   => 'everest-look-01-flip-through-vp9',
                    'poster' => 'everest-look-01-poster-v2',
                    'thumb'  => 'everest-look-01-thumb',
                    'label'  => __('Mount Everest, cover to cover', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'everest-look-02-chapter-spread',
                    'alt'  => __('Interior spread showing a pencil illustration of a yak, a "Mountain Fact" note, and short lines of chapter text.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'everest-look-03-how-tall-diagram',
                    'alt'  => __('Interior page with a labelled "How Tall Is Mount Everest?" diagram marking the altitude zones from Base Zone to Summit.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'everest-look-04-icefall-spread',
                    'alt'  => __('Interior spread from the Icefall chapter with a pencil illustration of climbers crossing ladders over a crevasse.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'everest-look-05-front-cover',
                    'alt'  => __('Front cover of Mount Everest: Charlotte pointing up a snowy peak with Henry the dog beside her.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'everest-look-06-front-cover-angled',
                    'alt'  => __('The Mount Everest hardcover shown at an angle.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'everest-look-07-back-cover',
                    'alt'  => __('Back cover of Mount Everest with the story summary and the themes Resilience, Teamwork and Courage.', 'brave-hearts'),
                ],
            ],
        ],

        /*
         * THE MARIANA TRENCH — video approved 2026-08-02. No interior page
         * stills yet.
         *
         * Video source: `Mariana Trench\hf_20260802_043537_….mp4`. Reviewed
         * frame by frame: the real printed paperback on the same navy ground as
         * the approved Everest clip — real hands, correct cover, real title
         * page, Contents, "Before You Begin — Meet the Real Explorers" with the
         * Cousteau and Sylvia Earle entries, Chapter 1 "The Book", Chapter 2
         * "Old Looney". All text correctly spelled. 16:9, 12.05s.
         *
         * NOT USED, and why — so it is not "discovered" and added later by
         * mistake: `Mariana Trench\Interior Photos\` holds five files
         * (C1 Full Page, C2/C5/C12 Half Scene, C8 The Whale2). These are the
         * ILLUSTRATION ARTWORK PLATES — line art on plain white, no page
         * layout, no body text. They are not photographs of the printed book
         * and would misrepresent what a spread actually looks like, which is
         * the one thing this gallery exists to show honestly.
         *
         * TO ADD INTERIOR STILLS LATER: upload per the checklist, then
         * uncomment the block below. No code change is needed.
         */
        'mariana_trench' => [
            'items' => [
                [
                    'type'   => 'video',
                    'mp4'    => 'mariana-look-01-flip-through',
                    'webm'   => 'mariana-look-01-flip-through-vp9',
                    'poster' => 'mariana-look-01-poster',
                    'thumb'  => 'mariana-look-01-thumb',
                    'label'  => __('The Mariana Trench, cover to cover', 'brave-hearts'),
                ],

                /*
                 * STILLS — supplied and approved by Andrew 2026-08-02, from
                 * `Mariana Trench\archive\`. Six files were supplied; five are
                 * used. Order is story proof -> educational proof -> back
                 * matter -> covers.
                 *
                 * The requested "Meet the Real Explorers" slot has NO matching
                 * photograph in the supplied set, so the depth-diagram spread
                 * carries the educational slot instead. That page does appear
                 * in the flip-through video at item 2.
                 *
                 * EXCLUDED: `hf_20260802_032018_afebff5b…` — a second
                 * straight-on front-cover shot, same edition, same artwork,
                 * same framing as `…032002_b842d11a` below, differing only in
                 * scale and background tone. Dropped as a near-duplicate, per
                 * the duplication rule. It is NOT deleted; it remains in the
                 * source folder and can be added as a seventh still by
                 * uploading it as `mariana-look-07-front-cover-alt`.
                 */
                [
                    'type' => 'image',
                    'slug' => 'mariana-look-02-whale-chapter-spread',
                    'alt'  => __('Interior spread from the chapter The Whale, with a pencil illustration of a humpback whale and an Ocean Fact note above short lines of text.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'mariana-look-03-depth-diagram-brave-learning',
                    'alt'  => __('Interior spread showing a labelled How Deep Is the Mariana Trench diagram beside the Brave Learning STEM and SEL companion questions.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'mariana-look-04-glossary-thank-you',
                    'alt'  => __('Interior spread showing the author thank-you page beside the glossary, Big Words for Brave Readers.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'mariana-look-05-front-cover',
                    'alt'  => __('Front cover of The Mariana Trench: Charlotte in a diving helmet pointing through a coral reef with Henry the dog beside her.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'mariana-look-06-back-cover',
                    'alt'  => __('Back cover of The Mariana Trench with the story summary and the themes Courage, Kindness and Curiosity.', 'brave-hearts'),
                ],
            ],
        ],

        /*
         * THE AMAZON — four interior/cover stills, video withdrawn.
         *
         * The strongest source set of the three titles: the stills are
         * Andrew's own iPhone photographs of the real printed book (camera
         * originals 3023x4031 and 4283x5711; web derivatives on the server
         * 1050x1400), not AI-processed. See the STILLS block inside the item
         * list below for the 2026-08-02 revert and the reasons for it.
         *
         * Video source: `Amazon\Iphone photos\IMG_3761.MOV` — 4K portrait, 62s,
         * trimmed to the first 25s (cover, title page, the Book 1/2/3 series
         * list, Contents, "Before You Begin", Teddy Roosevelt's Expedition Map,
         * Chapter 1 "The Green Book"). Audio dropped: the source carries an
         * unusual `apac` track and a flip-through needs none.
         *
         * ORIENTATION NOTE: this clip is PORTRAIT while Everest's and
         * Mariana's are landscape, because no navy-ground version of The Amazon
         * has been shot. The stage contains it correctly either way; a matching
         * landscape re-shoot is on the checklist as optional.
         */
        'amazon_rainforest' => [
            'items' => [
                /*
                 * VIDEO WITHDRAWN 2026-08-02 on Andrew's instruction. The clip
                 * (staging attachments 646-649, cut from `IMG_3761.MOV`) is
                 * NOT deleted — it stays uploaded as a rollback asset. The
                 * Amazon page is stills-only until a navy-ground video matching
                 * the Everest and Mariana treatment exists.
                 *
                 * To restore: uncomment the block below. No re-upload needed.
                 *
                 * [ 'type' => 'video',
                 *   'mp4'    => 'amazon-look-01-flip-through',
                 *   'webm'   => 'amazon-look-01-flip-through-vp9',
                 *   'poster' => 'amazon-look-01-poster',
                 *   'thumb'  => 'amazon-look-01-thumb',
                 *   'label'  => __('The Amazon, cover to cover', 'brave-hearts') ],
                 */
                /*
                 * ═══════════════════════════════════════════════════════════
                 * ⭐⭐ 2026-08-09 (`CYCLE148-LD-11`) — THE STUDIO-NAVY SET IS
                 *     BACK, ON A FOUNDER DECISION, AND THE 2026-08-02 REVERT
                 *     BELOW IS THEREFORE SUPERSEDED — NOT DELETED.
                 * ═══════════════════════════════════════════════════════════
                 *
                 * ⭐ THE DECISION. Andrew Signore, 2026-08-09, verbatim:
                 *
                 *        "Ship them- I cant even tell they have spelling
                 *         issues"
                 *
                 *    ⚠ RELAYED, NOT WITNESSED FIRST-HAND. It reached this
                 *      session through `chief-of-staff` (Gandalf) in the build
                 *      brief, and it is described here as a relay rather than
                 *      dressed up as a direct observation.
                 *
                 * ⛔ AND A COMMENT IS STILL NOT A DECISION RECORD — that is the
                 *    exact lesson of the 2026-08-02 revert preserved below, and
                 *    re-applying the set would be worthless if it repeated the
                 *    defect. This block therefore POINTS AT the durable record
                 *    rather than being it: the founder-decision entry handed to
                 *    `business-ops-knowledge` for `docs/DECISIONS.md` in the
                 *    same sitting, plus `docs/ROADMAP.md`'s existing
                 *    Higgsfield-artefact queue entry. ⚠ IF THAT RECORD IS NOT
                 *    PRESENT WHEN YOU READ THIS, TREAT THIS SWAP AS UNRECORDED
                 *    AND ESCALATE — do not re-derive approval from these words.
                 *
                 * ⭐ WHAT THE FOUR SLUGS ARE. Re-encoded 2026-08-09 from the
                 *    ORIGINAL Higgsfield PNG masters (`Higgsfield\Amazon\
                 *    archive\hf_20260802_0*.png`, 896x1200, 1.17-1.63 MB each),
                 *    NOT from the already-uploaded 2026-08-02 JPEGs — so this
                 *    set is one generation of compression better than the
                 *    `-navy` attachments it replaces. Verified pixel-identical
                 *    in content to those attachments before encoding (mean
                 *    absolute channel difference 1.18 and 1.39 of 255 against
                 *    659-662, i.e. JPEG noise and nothing else), so this is a
                 *    RE-APPLICATION of a known set, not a new one nobody has
                 *    seen. Native size preserved at 896x1200 — no upscaling,
                 *    no retouching, no regeneration, no "fixing" of anything
                 *    inside the frame. Each JPEG carries its Higgsfield job ID
                 *    in the file comment so the AI-pipeline provenance travels
                 *    with the asset, matching the Everest precedent above.
                 *    Staging attachments 1728-1731.
                 *
                 * ⛔⛔ ONE DEFECT IS SHIPPING WITH THEM AND IT IS *NOT* A
                 *     SPELLING ISSUE — READ THIS BEFORE ANY PRODUCTION DEPLOY.
                 *
                 *     `amazon-look-05-back-cover-navy-v2` renders the printed
                 *     ISBN as **879-8-8886-1080-2** with barcode digits
                 *     **9 796986 518002**. The real book's ISBN is
                 *     **979-8-9968-1080-2**, barcode **9 798996 810802** —
                 *     OBSERVED 2026-08-09 by reading both barcodes at 2x from
                 *     the render and from `amazon-look-05-back-cover` (the
                 *     authentic photograph, staging attachment 653), not
                 *     inferred from the 2026-08-02 note.
                 *
                 *     An ISBN is a product identifier, not prose. The founder
                 *     decision quoted above is scoped, in its own words, to
                 *     "spelling issues", so this session did NOT read it as
                 *     covering a wrong product identifier, and did NOT resolve
                 *     the question either way. It is STAGED and ESCALATED:
                 *     `CYCLE148-LD-12`, routed to Andrew through Gandalf.
                 *     ⛔ Do not deploy this slug to production until Andrew has
                 *     answered it. The other three carry text artefacts only.
                 *
                 * ✅ RETRACTED BEFORE IT LEFT THIS SESSION, recorded so nobody
                 *    re-raises it: the front-cover render's byline reads "Big
                 *    Places. Kind Hearts.", which looks like a corruption of the
                 *    company line "Big Places. Brave Hearts." It is NOT. The
                 *    real printed Amazon cover says "Big Places. Kind Hearts."
                 *    — verified against `amazon-look-04-front-cover`, the
                 *    authentic photograph. The render is right and the
                 *    suspicion was wrong.
                 *
                 * ⭐ ROLLBACK IS ONE EDIT AND NOTHING IS DELETED. The authentic
                 *    iPhone set (attachments 650-653) and the 2026-08-02 navy
                 *    set (659-662) both remain uploaded and intact. To revert,
                 *    drop the `-navy-v2` suffix from the four slugs below and
                 *    the previous, live-verified gallery returns with no
                 *    re-upload.
                 *
                 * ⛔ THE ORDER IS UNCHANGED, deliberately: Brave Learning ->
                 *    Chapter 7 -> front cover -> back cover, the same sequence
                 *    the authentic set ran in, so only the pixels moved.
                 *
                 * ───────────────────────────────────────────────────────────
                 * ⛔ EVERYTHING BELOW THIS LINE IS THE 2026-08-02 RECORD. It is
                 *    SUPERSEDED by the decision above and PRESERVED VERBATIM so
                 *    the movement stays visible and is not re-derived. Its
                 *    reasoning about ISBN honesty is still live — see
                 *    `CYCLE148-LD-12` above.
                 * ───────────────────────────────────────────────────────────
                 *
                 * STILLS — THE AUTHENTIC iPHONE SET. Restored 2026-08-02 on
                 * Andrew's explicit instruction, reverting the `-navy` swap
                 * made earlier the same day.
                 *
                 * WHY THE REVERT HAPPENED — recorded in full so it is not
                 * re-litigated, and so nobody re-applies the navy set on the
                 * strength of a comment:
                 *
                 *   1. The navy set was introduced with NO recorded owner
                 *      decision. `docs/DECISIONS.md` contains zero matches for
                 *      "navy"; no document under `docs/RELEASES/` mentions it.
                 *      The ONLY evidence of approval was the code comment that
                 *      previously stood here ("on Andrew's decision",
                 *      "accepted by Andrew"). Andrew has ruled that a code
                 *      comment alone is not sufficient evidence of an owner
                 *      decision. A comment is not a decision record.
                 *   2. It contradicted the continuity checkpoint at
                 *      `docs/CURRENT_TASK.md`, which states that The Amazon's
                 *      stills are "genuine full-resolution iPhone photographs"
                 *      carrying "no Higgsfield artefacts".
                 *   3. The navy set carried visible text artefacts a print run
                 *      would not produce — "Along the woy", "heartfult
                 *      adventure", "With a bind heart" — and rendered the
                 *      printed ISBN as 879-8-8886-1080-2. Publishing a
                 *      photograph of a book that misspells its own back cover
                 *      is not an honest representation of the product, which is
                 *      the one thing this gallery exists to provide.
                 *
                 * THE FOUR SLUGS BELOW (staging attachments 650-653) are
                 * Andrew's own photographs of the real printed paperback —
                 * IMG_3762/3763/3764/3766, wood table, natural light, visible
                 * hands. Verified on staging 2026-08-02: all four resolve, all
                 * four render, spelling correct throughout, and the back cover
                 * shows the CORRECT ISBN 979-8-9968-1080-2 / barcode
                 * 9798996810802. PRESERVE THEM EXACTLY AS THEY ARE — do not
                 * regenerate, retouch, upscale, recolour or "fix" anything
                 * inside them.
                 *
                 * SUPERSEDED, RETAINED AS DOCUMENTED ROLLBACK, NOT DELETED
                 * (staging attachments 659-662):
                 * `amazon-look-02-brave-learning-navy`,
                 * `-03-chapter-jaguar-navy`, `-04-front-cover-navy`,
                 * `-05-back-cover-navy`. They remain uploaded. Re-applying them
                 * is a matter of re-adding the `-navy` suffix to the four slugs
                 * below — and requires a RECORDED owner decision first, not a
                 * comment.
                 *
                 * Resolution note: the uploaded web derivatives of the
                 * authentic set are 1050x1400 (the 4283x5711 figure quoted in
                 * the previous comment describes the camera originals, not what
                 * is on the server). The navy derivatives were 896x1200, so the
                 * authentic set is also the higher-resolution one on staging.
                 */
                [
                    'type' => 'image',
                    'slug' => 'amazon-look-02-brave-learning-navy-v2',
                    'alt'  => __('Interior spread showing the Brave Learning companion questions beside a labelled Connected Amazon ecology diagram.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'amazon-look-03-chapter-jaguar-navy-v2',
                    'alt'  => __('Interior spread opening Chapter 7, The Jaguar, with a pencil illustration of a jaguar meeting Charlotte and Henry in the forest.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'amazon-look-04-front-cover-navy-v2',
                    'alt'  => __('Front cover of The Amazon: Charlotte pointing into the rainforest with Henry the dog beside her and a jaguar watching from the undergrowth.', 'brave-hearts'),
                ],
                [
                    'type' => 'image',
                    'slug' => 'amazon-look-05-back-cover-navy-v2',
                    'alt'  => __('Back cover of The Amazon with the story summary and the themes Kindness, Conservation and Connection to the natural world.', 'brave-hearts'),
                ],

                // ---- AWAITING ASSETS · uncomment once uploaded ----
                // [ 'type' => 'image', 'slug' => 'amazon-look-06-expedition-map',
                //   'alt'  => __('Interior page showing Teddy Roosevelt\'s Expedition Map.', 'brave-hearts') ],
            ],
        ],

        /*
         * THE COMPLETE COLLECTION — hero gallery, 2026-08-02.
         *
         * Composed almost entirely from slugs already approved on the three
         * individual pages, so nothing is duplicated: replace a title's asset
         * there and it updates here too.
         *
         * `group` drives the rail's visual grouping and each thumb's accessible
         * name. It is presentation only — NOT a second navigation system, and
         * nothing is filtered by it.
         *
         * Slide 1, `collection-look-01-three-books-v2`, is a composite built
         * from the three genuine cover files using resize, rotation, drop
         * shadow and background compositing ONLY. No cover was regenerated,
         * redrawn, recoloured or extended; no spine, page or prop was invented;
         * no text was synthesised.
         *
         * REBUILT 2026-08-02 (v2). Andrew rejected v1
         * (`collection-look-01-three-books`, staging attachment 663): its
         * Mariana panel was built from a cover variant that does NOT carry the
         * "Big Places. Brave Hearts. / Andrew Signore" byline, while Everest
         * and Amazon both showed theirs. v2 replaces ONLY the Mariana panel's
         * pixels, warped from the genuine cover file
         * `book1_mariana_trench_ebook_cover.jpg` (1303x1999, byline present),
         * into the exact quadrilateral the old panel occupied — fitted by least
         * squares to the old panel's own silhouette, residual ~0.3px. Every
         * other pixel, including the background gradient, both drop shadows and
         * the Everest and Amazon panels, is carried through unchanged, so the
         * approved composition and background treatment are preserved and no
         * spacing moved. Build script and evidence:
         * `Business OS\WORKING-DRAFTS\lead-developer\` (private, not in this
         * repo).
         *
         * v1 is NOT deleted. It stays uploaded as staging attachment 663 so the
         * rollback is one slug edit.
         *
         * Each video's `thumb` is deliberately an INTERIOR spread, not a cover
         * shot: on this page the title's cover already sits in the tile beside
         * it, so a cover thumbnail would read as a duplicate.
         *
         * NOT INCLUDED — The Amazon flip-through. Andrew removed it from the
         * Amazon product page in the same instruction that specified this
         * gallery, because no navy-ground version exists; re-introducing it
         * here would place a wood-table clip directly beside two navy ones.
         * Slot reserved below.
         *
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 2026-08-09 — TWO THINGS ABOUT THIS ENTRY CHANGED, AND THE
         *     PARAGRAPH IMMEDIATELY ABOVE IS THE FIRST OF THEM. It is
         *     PRESERVED VERBATIM rather than rewritten, because its reasoning
         *     ("no navy-ground version exists") is exactly the condition that
         *     has now been met, and a future reader needs to see the condition
         *     to understand why the video is here.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⛔ 1. THE AMAZON FLIP-THROUGH IS NOW INCLUDED — see slide 4 and its
         *       note. A navy-ground, landscape, real-camera clip now exists;
         *       the reserved slot below is therefore obsolete and is left in
         *       place only as the record of what was reserved.
         *
         * ⭐⭐ 2. THIS ENTRY IS NOW THE SINGLE SOURCE OF TRUTH FOR EVERY
         *        COLLECTION CAROUSEL ON THE SITE. It is no longer "the
         *        Collection page's gallery, which other pages sample from".
         *
         *     THE RULING. Andrew Signore, 2026-08-09, verbatim (⚠ RELAYED
         *     through `chief-of-staff`, not witnessed here):
         *
         *         "do a pass on all pages that have a carosel of the
         *          ecollection they need to be all exactly the same as the
         *          collection page- that has all the photos"
         *
         *     ⛔ WHAT THAT MEANS MECHANICALLY: `inc/collection-gallery.php` no
         *        longer keeps per-page slug lists. Every surface that renders
         *        a Collection carousel — /complete-collection/, the homepage,
         *        /books/, the parent Adventure Kit page and the three audience
         *        landing pages — reads THIS list, in THIS order, through
         *        `bhp_collection_carousel_slugs()` below. Editing this array
         *        is the only edit needed to change all seven surfaces, and it
         *        is no longer possible for them to drift apart by being edited
         *        one at a time, because there is nothing else to edit.
         *
         *     ⛔ SUPERSEDED BY THAT RULING, named rather than silently
         *        deleted, all in `inc/collection-gallery.php`:
         *          - FD-40's three-slide `$uniform` funnel set (2026-08-03);
         *          - F18's six-slide homepage list (2026-08-03);
         *          - B2's homepage-only removal of two wood-table Amazon
         *            slugs (2026-08-03) — MOOT, not reversed: both slugs left
         *            this gallery entirely on 2026-08-09 when the Amazon
         *            slides moved to the studio-navy renders, so the wood
         *            table is not on any collection carousel by any route;
         *          - the educator page's single-slide interiors-only exception
         *            (`CYCLE142-DEV-21`, `CYCLE141-CX-40`), which is the one
         *            genuine loss of intent — see the note on that entry.
         */
        'complete_collection' => [
            'items' => [
                [
                    'type'  => 'image',
                    'slug'  => 'collection-look-01-three-books-v2',
                    'group' => __('Complete Collection', 'brave-hearts'),
                    'alt'   => __('All three Adventures of Charlotte and Henry books together: The Mariana Trench, Mount Everest and The Amazon.', 'brave-hearts'),
                ],

                /*
                 * F22 (2026-08-03) — SLIDE ORDER. Andrew, walk-2, verbatim:
                 * "i want them to see the videos first after the three book
                 * image". Order is now: the three-book composite, then BOTH
                 * flip-through videos, then the stills.
                 *
                 * The two videos take the group label "Flip Through" rather
                 * than their title's name. That is not cosmetic: the rail
                 * starts a new visual cluster whenever `group` changes, so
                 * leaving them as "Mariana"/"Everest" would have printed
                 * "Mariana" twice and "Everest" twice in one rail. Each
                 * video's own accessible name still names its title in full
                 * ("Play video: The Mariana Trench, cover to cover"), so
                 * nothing is lost for assistive technology.
                 *
                 * Videos still cost ZERO bytes until played — their <source>
                 * elements ship with no `src` and are mounted by JS on first
                 * activation. Moving them earlier in the order does not move
                 * a single byte earlier in the page load.
                 */
                [
                    'type'   => 'video',
                    'mp4'    => 'mariana-look-01-flip-through',
                    'webm'   => 'mariana-look-01-flip-through-vp9',
                    'poster' => 'mariana-look-01-poster',
                    'thumb'  => 'mariana-look-04-glossary-thank-you',
                    'group'  => __('Flip Through', 'brave-hearts'),
                    'label'  => __('The Mariana Trench, cover to cover', 'brave-hearts'),
                ],
                [
                    'type'   => 'video',
                    'mp4'    => 'everest-look-01-flip-through',
                    'webm'   => 'everest-look-01-flip-through-vp9',
                    'poster' => 'everest-look-01-poster-v2',
                    'thumb'  => 'everest-look-02-chapter-spread',
                    'group'  => __('Flip Through', 'brave-hearts'),
                    'label'  => __('Mount Everest, cover to cover', 'brave-hearts'),
                ],

                /*
                 * ⭐⭐ 2026-08-09 (`CYCLE148-LD-15`) — THE THIRD FLIP-THROUGH.
                 *
                 * ⭐ THE INSTRUCTION. Andrew Signore, 2026-08-09, verbatim:
                 *
                 *        "Also need to add 'amazon-fast-flip-edited.mov' to
                 *         all the carosels as well- the order should be -3
                 *         book image- MT video, Everest video, Amazon video-
                 *         then all the pictures with the dark blue gray
                 *         background"
                 *
                 *    ⚠ RELAYED through `chief-of-staff` (Gandalf) in the build
                 *      brief. NOT witnessed first-hand by this session. The
                 *      durable record is the founder-decision entry handed to
                 *      `business-ops-knowledge` in the same sitting — a code
                 *      comment is not a decision record (see the 2026-08-02
                 *      revert preserved in The Amazon's block above, which
                 *      exists precisely because someone once treated one as
                 *      though it were).
                 *
                 * ⭐ THIS SLIDE IS THE ONLY ORDER-BEARING CHANGE in this entry.
                 *    Slides 1-3 and 5-10 are byte-identical to 1.19.211; the
                 *    Amazon video is inserted at position 4 and nothing else
                 *    moved. That satisfies the instruction's stated order —
                 *    three-book image, MT video, Everest video, Amazon video,
                 *    then the dark-ground stills — without re-sequencing an
                 *    approved set on inference.
                 *
                 * ⭐ WHAT THE ASSET IS, AND WHY IT CLEARS THE 2026-08-02 BAR.
                 *    Source: `02 - Marketing Materials\PRODUCT-FOOTAGE-2026-08\
                 *    Amazon-fast-flip-edited.mp4` (1920x1080, 24 fps, 10.10 s,
                 *    43.4 Mb/s, 54.6 MB). It is a REAL CAMERA CAPTURE of the
                 *    real printed paperback — real hands, real pages, no
                 *    Higgsfield job ID, no generated frame, nothing to
                 *    misspell. Reviewed frame by frame at 2 fps before
                 *    encoding: cover -> title page -> chapter openings ->
                 *    interior illustrations -> back to cover.
                 *
                 * ⛔ THIS IS THE CLIP THE 2026-08-02 WITHDRAWAL WAS WAITING
                 *    FOR. That note (in The Amazon's block above) withdrew the
                 *    wood-table clip because "The Amazon page is stills-only
                 *    until a navy-ground video matching the Everest and Mariana
                 *    treatment exists." This one is shot on the same dark navy
                 *    studio ground and is LANDSCAPE 16:9 like the other two,
                 *    where the withdrawn clip was portrait. ⚠ THE AMAZON
                 *    PRODUCT PAGE IS DELIBERATELY NOT CHANGED BY THIS PASS —
                 *    the brief scopes this work to COLLECTION carousels, and
                 *    restoring a video to a single-book PDP is a different
                 *    decision. It is flagged to Andrew, not taken. Restoring
                 *    it there is a four-line uncomment in that block using the
                 *    `-v2` slugs below.
                 *
                 * ⭐ THE WEB DERIVATIVES MATCH THE EXISTING TWO, MEASURED
                 *    RATHER THAN ASSUMED (ffmpeg 7.1, two-pass, 2026-08-09):
                 *
                 *      mp4  H.264 High / yuv420p / 1280x720 / 24 fps /
                 *           1384 kb/s video + AAC-LC 44.1 kHz stereo,
                 *           +faststart, 1.83 MB
                 *      webm VP9 Profile 0 / yuv420p / 1280x720 / 24 fps /
                 *           ~1330 kb/s video + Opus 48 kHz stereo, 1.80 MB
                 *
                 *    Against Everest (1271 kb/s mp4 / 1305 kb/s webm) and
                 *    Mariana (1406 / 1486). Every parameter that matters —
                 *    codec, profile, pixel format, frame size, frame rate,
                 *    bitrate band, audio codec and channel layout — sits inside
                 *    the band the two approved clips already define, so this
                 *    adds no new decode path and no new page-weight class.
                 *
                 * ⭐ THE POSTER IS THE CLIP'S OWN FIRST FRAME (t = 0.000 s),
                 *    1280x720, chosen the same way Everest's was: whole cover
                 *    visible, evenly lit, no motion blur, title and byline
                 *    fully legible. Taking frame 0 also means the poster and
                 *    the first decoded frame are the same picture, so playback
                 *    starts with no visible jump.
                 *
                 * ⭐ THE THUMB IS AN INTERIOR SPREAD, not a cover, exactly like
                 *    the two videos above it — on this page the title's cover
                 *    already occupies its own tile further along the rail, so a
                 *    cover thumbnail would read as a duplicate.
                 *
                 * ⛔ COSTS ZERO BYTES UNTIL PLAYED. Like every other video
                 *    here, its <source> elements ship with no `src` and its
                 *    poster is not emitted until the slide is first activated
                 *    (`look-inside.php`, LAZY MOUNTING). It is slide 4, so it
                 *    is never the initially-mounted slide on any surface.
                 *
                 * Staging attachments 1766 (mp4), 1767 (webm), 1768 (poster).
                 */
                [
                    'type'   => 'video',
                    'mp4'    => 'amazon-look-01-flip-through-v2',
                    'webm'   => 'amazon-look-01-flip-through-v2-vp9',
                    'poster' => 'amazon-look-01-poster-v2',
                    'thumb'  => 'amazon-look-03-chapter-jaguar-navy-v2',
                    'group'  => __('Flip Through', 'brave-hearts'),
                    'label'  => __('The Amazon, cover to cover', 'brave-hearts'),
                ],

                [
                    'type'  => 'image',
                    'slug'  => 'mariana-look-05-front-cover',
                    'group' => __('Mariana', 'brave-hearts'),
                    'alt'   => __('Front cover of The Mariana Trench: Charlotte in a diving helmet pointing through a coral reef with Henry the dog beside her.', 'brave-hearts'),
                ],
                [
                    'type'  => 'image',
                    'slug'  => 'mariana-look-03-depth-diagram-brave-learning',
                    'group' => __('Mariana', 'brave-hearts'),
                    'alt'   => __('Interior spread showing a labelled How Deep Is the Mariana Trench diagram beside the Brave Learning STEM and SEL companion questions.', 'brave-hearts'),
                ],

                [
                    'type'  => 'image',
                    'slug'  => 'everest-look-05-front-cover',
                    'group' => __('Everest', 'brave-hearts'),
                    'alt'   => __('Front cover of Mount Everest: Charlotte pointing up a snowy peak with Henry the dog beside her.', 'brave-hearts'),
                ],


                [
                    'type'  => 'image',
                    'slug'  => 'everest-look-03-how-tall-diagram',
                    'group' => __('Everest', 'brave-hearts'),
                    'alt'   => __('Interior page with a labelled How Tall Is Mount Everest diagram marking the altitude zones from Base Zone to Summit.', 'brave-hearts'),
                ],

                /*
                 * ⭐ 2026-08-09 (`CYCLE148-LD-11`) — the two Amazon slides
                 *    follow the product page onto the studio-navy set. They are
                 *    the SAME slugs the Amazon block above uses, which is the
                 *    whole point of composing this gallery from approved slugs:
                 *    the swap happened once, and every surface that renders the
                 *    Amazon gallery — product page, shop card, this collection
                 *    hero and the funnel pages — moved with it. No second
                 *    decision, no second list to keep in sync.
                 *
                 * ⛔ The back cover is NOT one of them and never was: this
                 *    gallery only ever carried the front cover and the Brave
                 *    Learning spread. The `CYCLE148-LD-12` ISBN escalation
                 *    therefore does not touch this page at all.
                 */
                [
                    'type'  => 'image',
                    'slug'  => 'amazon-look-04-front-cover-navy-v2',
                    'group' => __('Amazon', 'brave-hearts'),
                    'alt'   => __('Front cover of The Amazon: Charlotte pointing into the rainforest with Henry the dog beside her and a jaguar watching from the undergrowth.', 'brave-hearts'),
                ],
                // Reserved — see the note above.
                // [ 'type' => 'video', 'mp4' => 'amazon-look-01-flip-through',
                //   'webm' => 'amazon-look-01-flip-through-vp9',
                //   'poster' => 'amazon-look-01-poster',
                //   'thumb'  => 'amazon-look-03-chapter-jaguar',
                //   'group'  => __('Amazon', 'brave-hearts'),
                //   'label'  => __('The Amazon, cover to cover', 'brave-hearts') ],
                [
                    'type'  => 'image',
                    'slug'  => 'amazon-look-02-brave-learning-navy-v2',
                    'group' => __('Amazon', 'brave-hearts'),
                    'alt'   => __('Interior spread showing the Brave Learning companion questions beside a labelled Connected Amazon ecology diagram.', 'brave-hearts'),
                ],
            ],
        ],
    ]);
}

/**
 * ⭐ THE COLLECTION CAROUSEL, AS AN ORDERED LIST OF ATTACHMENT SLUGS.
 *
 * `CYCLE148-LD-16` (2026-08-09). One function, one list, seven surfaces.
 *
 * WHAT IT IS FOR. Andrew's 2026-08-09 parity ruling requires every Collection
 * carousel on the site to be "exactly the same as the collection page". This
 * returns the Collection page's own set, in the Collection page's own order,
 * derived FROM the registry above rather than restated anywhere — so the
 * parity is structural. There is no second list that could be forgotten.
 *
 * ⛔ WHY SLUGS AND NOT RESOLVED ITEMS. The consumer,
 * `bhp_cx_collection_media_subset()`, selects by attachment id from an
 * already-resolved set, and that machinery is what makes the funnel galleries
 * fail closed on an environment where an asset is missing. Handing it slugs
 * keeps that behaviour exactly as it is instead of routing around it. It also
 * means this function performs NO database work: it reads the static registry
 * array and nothing else.
 *
 * ⛔ WHY A VIDEO IS NAMED BY ITS POSTER. A resolved video item carries no `id`
 * of its own — the subset selector keys videos by `poster_id`, because a
 * poster is unique per video. Returning the poster slug is what makes a video
 * addressable at all. This is the same convention the per-page lists used
 * before they were removed, so nothing about it is new.
 *
 * ⛔ IT DOES NOT FILTER, TRIM OR REORDER. Whatever the registry says, in
 * whatever order, is what comes out. A surface that wants fewer slides is a
 * decision for Andrew, not a slice taken here — that is precisely the pattern
 * the parity ruling ended.
 *
 * @return string[] Ordered attachment slugs; empty if the entry is missing.
 */
function bhp_collection_carousel_slugs() {
    static $slugs = null;

    if (null !== $slugs) {
        return $slugs;
    }

    $registry = bhp_book_media_registry();
    $items    = isset($registry['complete_collection']['items']) && is_array($registry['complete_collection']['items'])
        ? $registry['complete_collection']['items']
        : [];

    $slugs = [];

    foreach ($items as $item) {
        $type = isset($item['type']) ? $item['type'] : '';

        if ('video' === $type) {
            if (!empty($item['poster'])) {
                $slugs[] = $item['poster'];
            }
            continue;
        }

        if ('image' === $type && !empty($item['slug'])) {
            $slugs[] = $item['slug'];
        }
    }

    return $slugs;
}

/**
 * Resolve an attachment slug to an attachment ID on the CURRENT environment.
 * Returns 0 when it does not resolve, which every caller treats as
 * "not approved". Memoised: one page can ask for the same slug repeatedly.
 */
function bhp_book_media_attachment_id($slug) {
    static $cache = [];

    $slug = is_string($slug) ? trim($slug) : '';
    if ('' === $slug) {
        return 0;
    }
    if (isset($cache[$slug])) {
        return $cache[$slug];
    }

    $post = get_page_by_path($slug, OBJECT, 'attachment');
    $cache[$slug] = ($post && 'attachment' === $post->post_type) ? (int) $post->ID : 0;

    return $cache[$slug];
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.255 (2026-08-19) — CYCLE165-LD-HERO-CTA-FALLBACK.
 *    THE HOMEPAGE'S "FIRST PAGES" RESOLUTION, IN ONE PLACE, SO THE BUTTON
 *    AND THE SECTION CAN NEVER DISAGREE ABOUT WHETHER THE SECTION EXISTS.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS EXISTS BECAUSE A DEAD ANCHOR SHIPPED TO PRODUCTION AND THE FOUNDER
 *    FOUND IT, NOT QA. Andrew Signore, 2026-08-19, item 82: "The main CTA on
 *    the home page doesnt even click to to first free pages. Bad link." /
 *    "The pages 1,2,3 arent even on the homepage!"
 *
 * ⭐ THE MECHANISM OF THE DEFECT, RECORDED SO IT IS NOT RE-DERIVED. From
 *    1.19.243 the hero button hard-coded `#home-open-the-book`, while
 *    `template-parts/components/home-open-the-book.php` emitted that id only
 *    if at least one of `mariana-trench-page-1-full` / `-page-1` / `-page-2` /
 *    `-page-3` resolved to an attachment ON THIS ENVIRONMENT. Staging2 has
 *    them; production has none of them, because deploy #4 moved THEME FILES
 *    and media is not theme files. So production rendered a button whose
 *    target its own gate had already suppressed.
 *
 *    VERIFIED LIVE, not inferred: headless-Chrome DOM read of
 *    https://braveheartspublishing.com/ on 2026-08-19 — exactly one
 *    `href="#home-open-the-book"`, and ZERO `id="home-open-the-book"`.
 *
 * ⭐ THE FIX IS STRUCTURAL, NOT A NEW STRING. The gate and the button now
 *    read the SAME predicate, so the only way to reintroduce the defect is to
 *    stop calling this function — which the test suite asserts against. A
 *    second copy of the slug list in `front-page.php` would have been a
 *    faster fix and would have drifted the first time anyone edited one.
 */

/**
 * The homepage "open the book" spread specification, BEFORE resolution.
 *
 * Kept as its own function purely so a test harness can empty it with
 * `add_filter( 'bhp_home_open_the_book_spreads', '__return_empty_array' )`
 * and exercise the no-media branch WITHOUT deleting an attachment from any
 * environment. Simulating a missing image by removing real media is how a
 * QA step becomes a data-loss incident.
 *
 * @return array<int,array{slug:string,caption:string,aspect:string}>
 */
function bhp_home_open_the_book_spread_spec() {
    $spreads = [
        ['slug' => 'mariana-trench-page-1-full', 'caption' => __('Page 1', 'brave-hearts'), 'aspect' => 'tall'],
        ['slug' => 'mariana-trench-page-2',      'caption' => __('Page 2', 'brave-hearts'), 'aspect' => 'square'],
        ['slug' => 'mariana-trench-page-3',      'caption' => __('Page 3', 'brave-hearts'), 'aspect' => 'square'],
    ];

    /**
     * Filters the homepage first-pages spread specification.
     *
     * @param array $spreads Slug / caption / aspect triples, in render order.
     */
    return (array) apply_filters('bhp_home_open_the_book_spreads', $spreads);
}

/**
 * Per-slot fallbacks for the spread specification.
 *
 * ⭐ THE PAGE-1 FALLBACK, AND WHY IT IS A FALLBACK RATHER THAN A SECOND ROW
 *    (moved here verbatim from the component in 1.19.255; the reasoning is
 *    unchanged and is preserved rather than re-summarised).
 *
 *    `mariana-trench-page-1-full` exists on STAGING. It does not exist on
 *    production, and it will not until Andrew approves that media moving. On
 *    an environment where the full-page attachment is absent, slot 1 falls
 *    back to the SQUARE page 1 so the section still opens with page 1 — a
 *    cropped page 1 is worse than the full page, but a MISSING page 1 would
 *    start the sequence at page 2, which is a worse failure than the one
 *    Andrew reported. The fallback is per-slot and silent by design: it fails
 *    to the previous behaviour, never to an empty frame.
 *
 * @return array<string,array{slug:string,aspect:string}>
 */
function bhp_home_open_the_book_fallbacks() {
    return [
        'mariana-trench-page-1-full' => ['slug' => 'mariana-trench-page-1', 'aspect' => 'square'],
    ];
}

/**
 * The resolved homepage first-pages spreads for THIS environment.
 *
 * ⛔ DELIBERATELY NOT MEMOISED. `bhp_book_media_attachment_id()` already
 *    caches every slug lookup, so the repeat cost is three array reads — and
 *    memoising here would make the test filter above unobservable after the
 *    first call, which is precisely the branch that most needs testing.
 *
 * @return array<int,array{slug:string,caption:string,aspect:string,id:int}>
 *         Empty array means: this environment cannot render the section.
 */
function bhp_home_open_the_book_spreads() {
    $fallbacks = bhp_home_open_the_book_fallbacks();
    $resolved  = [];

    foreach (bhp_home_open_the_book_spread_spec() as $spread) {
        if (!is_array($spread) || empty($spread['slug'])) {
            continue;
        }

        $id = (int) bhp_book_media_attachment_id($spread['slug']);

        if ($id <= 0 && isset($fallbacks[$spread['slug']])) {
            $fallback = $fallbacks[$spread['slug']];
            $id       = (int) bhp_book_media_attachment_id($fallback['slug']);
            if ($id > 0) {
                // The aspect travels WITH the attachment. A 1:1 crop rendered
                // in a 3:4 frame would letterbox or crop it, which is the
                // defect the per-slot aspect exists to remove.
                $spread['aspect'] = $fallback['aspect'];
            }
        }

        if ($id > 0) {
            $spread['id'] = $id;
            $resolved[]   = $spread;
        }
    }

    return $resolved;
}

/**
 * Will the homepage actually emit `id="home-open-the-book"` on this request?
 *
 * This is THE gate. The component returns early when it is false; the hero
 * button refuses to point at the section when it is false. One predicate,
 * two readers.
 *
 * @return bool
 */
function bhp_home_open_the_book_resolved() {
    return (bool) bhp_home_open_the_book_spreads();
}

/**
 * The best REAL "read the first pages" anchor available on the homepage now.
 *
 * ⭐ THREE CANDIDATES, IN DESCENDING ORDER OF HONESTY, AND EVERY ONE OF THEM
 *    IS CHECKED AGAINST THE THING THAT ACTUALLY RENDERS IT.
 *
 *    1. `#home-open-the-book` — the dedicated first-pages section. Checked
 *       with the same predicate its own gate uses.
 *    2. `#bhp-look-inside-complete_collection` — the Look Inside gallery
 *       inside the collection band. Checked with `bhp_cx_collection_gallery_config()`,
 *       the ONE predicate `inc/collection-gallery.php` documents as its own
 *       render gate, so this cannot claim a gallery the page will not build.
 *       The id is `look-inside.php`'s own `'bhp-look-inside-' . sanitize_html_class($media['key'])`
 *       with key `complete_collection`. VERIFIED LIVE on production 2026-08-19
 *       (headless-Chrome DOM read): the section is present with
 *       `data-bhp-gallery-count="10"`, and it sits INSIDE `#home-sales-paths`.
 *    3. `#home-sales-paths` — the collection band's own section id, which
 *       `front-page.php` renders unconditionally (no gate, no arguments but
 *       `cta`, so the component's `section_id` default applies). This is the
 *       floor: it is not a first-pages surface, but it is the nearest real
 *       section to the promise and it CANNOT be absent.
 *
 * ⛔ `/complete-collection/` IS NOT IN THIS CHAIN, AND THAT IS DELIBERATE.
 *    Andrew rejected exactly that destination for exactly this button in
 *    1.19.242: "When you hit read the first pages it goes direct to the
 *    collection page... this is all incorrect." Falling back to it would
 *    reintroduce a defect he has already reported once.
 *
 * ⚠️ SCOPE: this answers for the HOMEPAGE. Every candidate is a homepage
 *    section, and the only caller is `front-page.php`.
 *
 * @return string A fragment, always beginning with `#`.
 */
function bhp_home_first_pages_anchor() {
    if (bhp_home_open_the_book_resolved()) {
        return '#home-open-the-book';
    }

    if (function_exists('bhp_cx_collection_gallery_config')) {
        $config = bhp_cx_collection_gallery_config();
        if (is_array($config) && !empty($config['media']['has_any'])) {
            return '#bhp-look-inside-complete_collection';
        }
    }

    return '#home-sales-paths';
}

/**
 * ⭐ 1.19.241 — whole-second duration of a video attachment, or 0 if unknown.
 *
 * WordPress writes `length` into `_wp_attachment_metadata` at upload time from
 * the real container, so this is the file's own length rather than anybody's
 * recollection of it.
 *
 * ⛔ 0 IS A REAL ANSWER AND MUST STAY ONE. An attachment whose metadata was
 *    never generated (a direct DB insert, a failed regeneration, a
 *    non-video mime) has no honest duration to report, and every caller is
 *    expected to omit the duration rather than substitute a plausible number.
 *    Returning a guess here would put a fabricated figure in customer-facing
 *    copy, which `BHP-AGENT-STANDING-RULES.md` §3 forbids outright.
 *
 * @param int $attachment_id
 * @return int Whole seconds, or 0 when not knowable.
 */
function bhp_book_media_duration($attachment_id) {
    $attachment_id = (int) $attachment_id;
    if ($attachment_id <= 0) {
        return 0;
    }

    $meta = wp_get_attachment_metadata($attachment_id);
    if (!is_array($meta) || !isset($meta['length'])) {
        return 0;
    }

    $length = (int) round((float) $meta['length']);

    return $length > 0 ? $length : 0;
}

/**
 * Approved, fully-resolved gallery items for one title.
 *
 * Returns:
 *   items    array  resolved items, unresolvable ones dropped
 *   count    int
 *   has_any  bool   the single render gate every consumer checks
 */
function bhp_book_media($key) {
    $registry = bhp_book_media_registry();
    $entry    = isset($registry[$key]) ? $registry[$key] : [];
    $raw      = isset($entry['items']) && is_array($entry['items']) ? $entry['items'] : [];

    $items = [];

    foreach ($raw as $item) {
        $type = isset($item['type']) ? $item['type'] : '';

        if ('video' === $type) {
            $mp4    = bhp_book_media_attachment_id(isset($item['mp4']) ? $item['mp4'] : '');
            $webm   = bhp_book_media_attachment_id(isset($item['webm']) ? $item['webm'] : '');
            $poster = bhp_book_media_attachment_id(isset($item['poster']) ? $item['poster'] : '');

            if (!$poster || (!$mp4 && !$webm)) {
                continue; // No poster or no source -> not showable.
            }

            $mp4_url  = $mp4 ? wp_get_attachment_url($mp4) : '';
            $webm_url = $webm ? wp_get_attachment_url($webm) : '';
            if ('' === $mp4_url && '' === $webm_url) {
                continue;
            }

            /*
             * The rail tile. Optional, and it falls back to the poster — a
             * 16:9 poster centre-cropped to a square tile can leave the subject
             * small, so a purpose-cut square is preferred where one exists.
             */
            $thumb = bhp_book_media_attachment_id(isset($item['thumb']) ? $item['thumb'] : '');
            $thumb_url = $thumb ? wp_get_attachment_image_url($thumb, 'thumbnail') : '';

            $items[] = [
                'type'      => 'video',
                'group'     => isset($item['group']) ? $item['group'] : '',
                'mp4'       => $mp4_url,
                'webm'      => $webm_url,
                'poster'    => wp_get_attachment_url($poster),
                'poster_id' => $poster,
                'thumb_id'  => $thumb,
                'thumb'     => $thumb_url ? $thumb_url : wp_get_attachment_url($poster),
                /*
                 * ⭐ 1.19.241 (2026-08-18, `CYCLE164-LD-STOREFRONT-BATCH`) —
                 *    THE CLIP'S OWN LENGTH, READ FROM THE ASSET.
                 *
                 * The PDP cue says "(12 sec)" out loud, so the number has to
                 * come from the file rather than from a comment or a brief.
                 * `_wp_attachment_metadata['length']` is whole seconds, written
                 * by WordPress at upload from the real container.
                 *
                 * ⛔ WHY NOT A LITERAL. Both approved clips happen to be 12s
                 *    today (VERIFIED on staging 2026-08-18 by WP-CLI:
                 *    mariana-look-01-flip-through id 642 length 12;
                 *    everest-look-01-flip-through id 628 length 12). A literal
                 *    would keep saying 12 after someone swaps in a 30-second
                 *    re-shoot — which is a claim about the product, not a
                 *    caption, and the never-invent rule reaches it.
                 *
                 * 0 means "not known", and the cue then omits the duration
                 * entirely rather than guessing or printing "(0 sec)".
                 */
                'duration'  => bhp_book_media_duration($mp4 ? $mp4 : $webm),
                'label'     => isset($item['label']) && $item['label']
                    ? $item['label']
                    : __('Flip-through of the printed book', 'brave-hearts'),
            ];
            continue;
        }

        if ('image' === $type) {
            $id = bhp_book_media_attachment_id(isset($item['slug']) ? $item['slug'] : '');
            if (!$id) {
                continue;
            }
            $items[] = [
                'type'  => 'image',
                'group' => isset($item['group']) ? $item['group'] : '',
                'id'    => $id,
                'alt'   => isset($item['alt']) ? $item['alt'] : '',
            ];
        }
    }

    /*
     * How many items make a gallery worth showing. One is enough now that the
     * stage handles a single item gracefully (arrows and thumbs both hide),
     * but it stays filterable so the threshold is a decision rather than a
     * magic number buried in a template.
     */
    $min = (int) apply_filters('bhp_look_inside_min_items', 1, $key);

    return [
        'key'     => $key,
        'items'   => $items,
        'count'   => count($items),
        'has_any' => count($items) >= max(1, $min),
    ];
}

/**
 * Does this title have anything worth linking to? Used by the shop card's
 * "Look inside" affordance so it never links to a section that will not be
 * there when the visitor arrives.
 */
function bhp_book_has_look_inside($key) {
    $media = bhp_book_media($key);
    return $media['has_any'];
}

/**
 * F7 / CYCLE142-LD-01 — keep WordPress's `auto` sizes off the gallery stage.
 *
 * WordPress 7.0 prepends `auto, ` to the `sizes` attribute of any image it
 * renders with `loading="lazy"` (wp-includes/media.php:1156-1167). That is the
 * right default for images that stay in the layout. It is actively harmful for
 * this gallery, whose inactive slides carry the `hidden` attribute and
 * therefore have no layout box at all: `auto` cannot resolve against a zero
 * box, the browser falls through to the next clause, and it downloads the
 * largest srcset candidate — the full-size original — for a slide the visitor
 * has already left. Measured waste on a 9-slide walk at 390px:
 * 2,191 KB -> 1,040 KB. See the long note in
 * `template-parts/commerce/look-inside.php`.
 *
 * Scoped by CLASS, deliberately. The global `wp_img_tag_add_auto_sizes` filter
 * would turn auto-sizes off for every image on every page, including content
 * images where it is doing its job correctly. This touches only elements this
 * theme emitted with `bhp-gallery__img`, and only when a real `sizes` value is
 * already present — if the template ever stopped supplying one, this is a
 * no-op rather than a silent downgrade.
 *
 * Runs on `wp_get_attachment_image_attributes`, which fires AFTER the prefix is
 * added, so it is version-tolerant: it removes the prefix if WordPress added
 * one and does nothing if a future release stops adding it.
 */
/**
 * F7 / CYCLE142-LD-01, THE FIX THAT ACTUALLY WORKS — turn WordPress's `auto`
 * sizes off for the whole request on pages that carry a gallery.
 *
 * WHY IT HAS TO BE REQUEST-WIDE RATHER THAN PER-IMAGE. WordPress adds the
 * `auto, ` prefix in TWO places: inside `wp_get_attachment_image()`
 * (wp-includes/media.php:1166), and again in `wp_filter_content_tags()` ->
 * `wp_img_tag_add_auto_sizes()` (media.php:1960), which post-processes the
 * finished HTML of anything that passes through `the_content`. The Complete
 * Collection page renders through a SHORTCODE inside `the_content`, so the
 * second pass re-added the prefix to markup the first pass had already been
 * told not to add it to. Two earlier attempts that only addressed the first
 * path were verified live and measured NO change: the 9-slide walk still cost
 * 1,851 KB, identical to production.
 *
 * WHY IT IS SAFE. `auto` sizes is a progressive enhancement introduced in
 * WordPress 6.7; every image on these pages already carries an accurate,
 * explicit `sizes` written by this theme or by WordPress itself, so switching
 * it off returns them to 6.6 behaviour rather than leaving them unsized. On
 * an element with NO layout box — which is every inactive gallery slide,
 * because they carry the `hidden` attribute — `auto` cannot resolve at all,
 * falls through to the next clause, and makes the browser fetch the largest
 * srcset candidate for a picture nobody is looking at.
 *
 * WHY IT IS SCOPED. Blog posts and pages without a gallery keep auto-sizes,
 * where it does its intended job. The list below is exactly the set of
 * templates that render `look-inside.php`.
 */
function bhp_disable_auto_sizes_on_gallery_pages() {
    if (is_admin()) {
        return;
    }
    $carries_gallery = is_front_page()
        || (function_exists('is_product') && is_product())
        || is_page('complete-collection')
        || is_page_template([
            'page-complete-collection.php',
            'page-books.php',
            'page-reluctant-reader-adventure-kit.php',
            'page-audience-educators.php',
            'page-audience-gift-buyers.php',
            'page-audience-organizations.php',
            'page-audience-retailers.php',
        ]);
    if ($carries_gallery) {
        add_filter('wp_img_tag_add_auto_sizes', '__return_false');
    }
}
add_action('template_redirect', 'bhp_disable_auto_sizes_on_gallery_pages', 5);

function bhp_gallery_strip_auto_sizes($attr) {
    if (empty($attr['sizes']) || empty($attr['class'])) {
        return $attr;
    }
    if (false === strpos((string) $attr['class'], 'bhp-gallery__img')) {
        return $attr;
    }
    $sizes = (string) $attr['sizes'];
    if (0 === stripos($sizes, 'auto,')) {
        $attr['sizes'] = trim(substr($sizes, 5));
    } elseif (0 === stripos($sizes, 'auto ')) {
        $attr['sizes'] = trim(substr($sizes, 5));
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'bhp_gallery_strip_auto_sizes', 20, 1);
