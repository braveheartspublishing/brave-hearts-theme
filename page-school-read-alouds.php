<?php
/**
 * Template Name: School Read-Alouds
 * Description: The TEACHER read-aloud page. What a visit looks like, the
 * founder's own account of one, the scheduler, the proof, and one tail ask.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * SCHOOL READ-ALOUDS — 1.19.333 (2026-08-30, `CYCLE170-LD-BUNDLE`).
 * STAGING ONLY. Slug `school-read-alouds`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ 1.19.333 — THE TWO-PAGE ARCHITECTURE. READ THIS BEFORE EDITING ANYTHING.
 * ---------------------------------------------------------------------------
 * Andrew Signore, carrier item **524**, relayed in the `CYCLE170-LD-BUNDLE`
 * brief. The site has TWO read-aloud pages with TWO audiences and TWO jobs:
 *
 *   `/author-visits/`       PARENT. Upcoming visit cards, the "Order signed
 *                           books for this visit" buttons, How It Works. It is
 *                           reached from PRINTED QR CODES TAPED TO CLASSROOM
 *                           DOORS. ⛔ THIS BUILD DOES NOT TOUCH IT AND IT NEVER
 *                           REDIRECTS ANYWHERE.
 *   `/school-read-alouds/`  TEACHER. This file. A teacher decides whether to
 *                           invite him and picks a day. Nothing here sells a
 *                           book to a parent.
 *
 * ⛔⛔ THE PARENT ZONE IS REMOVED FROM THIS FILE, NOT HIDDEN, NOT GATED, NOT
 *     COMMENTED OUT. Upcoming Visits, the order buttons and How It Works are
 *     GONE from this template. They are not deleted from the codebase: they
 *     still render on `/author-visits/` from `page-author-visits.php`, which is
 *     unedited, and `bhp_author_visits_rows()` still drives them. ⛔ IF A LATER
 *     PASS "RESTORES" THEM HERE IT REVERSES ITEM 524 AND PUTS A PARENT
 *     TRANSACTION IN FRONT OF A TEACHER AGAIN.
 *
 * ⚠ WHAT THAT COSTS, STATED RATHER THAN BURIED: at 1.19.332 the `/author-visits/`
 *   301 was armed, so this page WAS the ordering path for a parent scanning a
 *   classroom-door QR code. Item 524 un-arms that redirect in the same act, so
 *   the QR path keeps its own page and nothing that is already printed breaks.
 *   ⛔ The two halves of item 524 must ship TOGETHER. Removing the parent zone
 *   from this page while `bhp_school_readalouds_merged_slugs()` still contains
 *   `author-visits` would send every QR scan to a page with no order button.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE SECTION ORDER IS FOUNDER-RULED VIA THE BRIEF. Do not reorder on taste.
 * ---------------------------------------------------------------------------
 *   a. Hero, CTA above the fold, + the three chips     ← chips NEW at 1.19.333
 *   b. WHAT A VISIT LOOKS LIKE                         ← NEW, item 523
 *   c. About the read-aloud, the four passages         ← item 512, unchanged
 *   d. THE SCHEDULER (calendar + form)                 ← MOVED UP from below
 *   e. The photo CAROUSEL                              ← MOVED DOWN, below the form
 *   f. PAST read-alouds                                ← MOVED DOWN, below the form
 *   g. Educator email capture                          ← the ONE tail ask
 *   h. Pricing slot                                    ← structural, gated OFF
 *
 * ⭐ WHY d MOVED ABOVE e AND f — item 519's conversion restructure. The proof
 *    (photographs, past visits) used to sit BETWEEN the visitor and the only
 *    action on the page. A teacher who is already convinced had to scroll past
 *    six photographs to find the form. Proof now sits BELOW the ask, where it
 *    answers the hesitation of somebody who did not act rather than delaying
 *    somebody who would have.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ ONE TAIL ASK. THE PARENT FUNNEL IS OFF THIS PAGE ENTIRELY.
 * ---------------------------------------------------------------------------
 * The "Find My Best Next Step" quiz band, the sitewide footer capture block and
 * every parent-funnel capture overlay are SUPPRESSED on this template. ⛔ THAT
 * IS DONE IN `inc/school-read-alouds.php`, THROUGH EACH GATE'S OWN FILTER, NOT
 * HERE and not by editing `functions.php`'s exclusion arrays. Read the block
 * comment there before changing it: it records that this reverses a stale line
 * in `.claude/rules/funnels.md`, and why the doctrine wins.
 *
 * ⭐ THE ONE ASK THAT REMAINS is the educator toolkit in section g, which is the
 *    TEACHER funnel and is this page's audience.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ NOT ONE WORD OF FOUNDER-VOICE PROSE IS WRITTEN BY THIS FILE.
 * ---------------------------------------------------------------------------
 * The four About passages still land through
 * `add_filter('bhp_readaloud_funnel_copy_slots', …)` and the placeholder
 * mechanism is untouched. The item-523 visit section and the item-522 chips are
 * read from `inc/readaloud-approved-copy.php`, which holds the approved strings
 * and records their provenance. This template prints them and adds nothing.
 *
 * ⭐ THE STRINGS ON THIS PAGE THAT ARE **NOT** APPROVED-COPY CONSTANTS, and
 *    where each came from:
 *
 *    · The hero lead is reused VERBATIM from the approved `/author-visits/`
 *      October booking copy (1.19.319), by way of 1.19.325. Not rewritten.
 *    · *"Book a FREE read-aloud"* is Andrew's own CTA wording, carrier item 481.
 *    · *"There is no charge."* is the plain statement of item 481.
 *    · The past-visit rows, their dates and note text are REGISTRY DATA, and the
 *      carousel alt text and captions travel with the photographs.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE COPY RAILS, RESTATED BECAUSE THIS IS THE FILE SOMEONE WILL EDIT
 * ---------------------------------------------------------------------------
 *   · Andrew's I-voice. NO "we", "us" or "our" in any string this file writes.
 *     ⚠ The APPROVED passages contain "We read all the way through chapter
 *       nine" — that is the founder's own approved sentence about a room he was
 *       in, not this file's voice, and it is not edited.
 *   · NO price, fee, rate or figure. None exists (item 481).
 *   · NO review, rating, testimonial, reaction, result, statistic or award.
 *   · NO child named. NO school invented.
 *   · Reading age 6–9, NEVER 5–9. American spelling (§24).
 *   · Approved captions and alt text are reused byte-for-byte.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

$bhp_cta         = bhp_school_readalouds_cta();
$bhp_source_page = bhp_school_readalouds_url();

/*
 * ⛔ EVERY DATA CALL BELOW IS `function_exists()` GUARDED. This template must
 *    render on a site where one of the older includes is missing or older
 *    rather than fataling: a missing section is a gap, a fatal is a white page.
 *
 * ⛔ `bhp_author_visits_rows()` IS NO LONGER CALLED. That is the UPCOMING list,
 *    it belongs to the parent page, and item 524 took it off this one. The PAST
 *    list is a different helper and it stays: a past read-aloud carries no
 *    order button, no `?bhp_visit=` link and no transaction of any kind, so it
 *    is trust evidence for a teacher rather than a parent funnel.
 */
$bhp_past = function_exists( 'bhp_author_visits_past_rows' ) ? bhp_author_visits_past_rows() : array();

/*
 * The carousel's photographs: ONE flat, newest-first list.
 * `bhp_gallery_sections()` is still the source of the HEADING, so it cannot
 * drift from `/gallery/`'s. ⛔ NOTHING IS DELETED FROM `inc/gallery-page.php`.
 */
$bhp_carousel_photos = function_exists( 'bhp_readaloud_carousel_photos' ) ? bhp_readaloud_carousel_photos() : array();
$bhp_gallery_total   = count( $bhp_carousel_photos );

/*
 * The educator lead magnet, resolved BEFORE anything is printed. The educator
 * landing page gates its whole capture panel on this same flag and this page
 * does the same, rather than advertising a PDF that may not be there.
 */
$bhp_toolkit = function_exists( 'bhp_get_teacher_toolkit_download' )
	? bhp_get_teacher_toolkit_download()
	: array( 'url' => '', 'ready' => false );

/* The approved item-522 / item-523 strings. Guarded like everything else. */
$bhp_chips        = function_exists( 'bhp_readaloud_hero_chips' ) ? bhp_readaloud_hero_chips() : array();
$bhp_visit_points = function_exists( 'bhp_readaloud_visit_shape_points' ) ? bhp_readaloud_visit_shape_points() : array();
$bhp_visit_close  = function_exists( 'bhp_readaloud_visit_shape_closing' ) ? bhp_readaloud_visit_shape_closing() : '';
?>

<div class="readaloud-funnel school-readalouds">

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * a · THE HERO. The CTA must be visible WITHOUT SCROLLING at 1440 and at 375.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THAT IS A MEASURED REQUIREMENT, NOT A DESIGN INTENTION. The site header is
 *    sticky and roughly 93px tall, so the hero's whole budget is the viewport
 *    minus that. This section reuses the `.readaloud-funnel__hero` geometry
 *    shipped at 1.19.325, which declares its OWN smaller vertical padding
 *    instead of the sitewide `.section` padding for exactly this reason. If
 *    someone later restores the sitewide padding, the CTA drops below the fold
 *    on a phone and a founder ruling is quietly broken.
 *
 * ⛔ THE BUTTON SITS ABOVE THE SUPPORTING LINE, not below it. One fewer line of
 *    text between the visitor and the only action on the screen.
 *
 * ⭐ 1.19.333 — THE THREE CHIPS. Carrier item 522, relayed in the brief. They
 *    are printed AFTER the CTA in DOM order, so they cannot push it down, and
 *    they are a `<ul>` rather than one string with middots so a screen reader
 *    announces three facts rather than one run-on sentence.
 *
 * ⛔ THE MIDDOTS BETWEEN THEM ARE CSS `::before` CONTENT AND ARE
 *    `aria-hidden`-equivalent by construction (generated content is not read as
 *    text by the engines that matter here, and the list already conveys the
 *    separation). No punctuation is written into the approved strings.
 *
 * ⚠ EVERY CHIP RESTATES A FACT ALREADY ON THIS PAGE. See
 *   `bhp_readaloud_hero_chips()` for the per-chip provenance. No count, no
 *   rating, no reaction and no result appears in any of them.
 *
 * ---------------------------------------------------------------------------
 * ⭐ 1.19.329 — THE SMALL HERO PHOTOGRAPH (carrier item 492), UNCHANGED.
 * ---------------------------------------------------------------------------
 * ⛔ THE DOM ORDER IS COPY FIRST, PHOTOGRAPH SECOND, AND THAT IS THE WHOLE
 *    ABOVE-THE-FOLD SAFETY MECHANISM. On a phone there is no second column, so
 *    the figure falls BELOW the CTA in normal flow and cannot push it down by a
 *    single pixel. On desktop CSS grid lifts the same element into column two,
 *    row one. Reorder these two children and the founder-ruled requirement that
 *    the CTA is visible without scrolling at 375 breaks silently.
 */
?>
<section class="section section--dark readaloud-funnel__hero" aria-labelledby="school-readalouds-hero-title">
  <div class="container container--content school-readalouds__hero-grid">

    <div class="school-readalouds__hero-copy">
      <p class="component-heading__eyebrow"><?php esc_html_e( 'Read-alouds', 'brave-hearts' ); ?></p>

      <h1 id="school-readalouds-hero-title" class="text-hero readaloud-funnel__hero-title">
        <?php esc_html_e( 'Book a free read-aloud', 'brave-hearts' ); ?>
      </h1>

      <p class="text-lead readaloud-funnel__hero-lead">
        <?php
        /*
         * VERBATIM from the approved `/author-visits/` October booking copy
         * (theme 1.19.319). Not rewritten, not paraphrased, not "improved".
         */
        esc_html_e( 'My calendar is open for Boise-area classroom read-alouds from October onward.', 'brave-hearts' );
        ?>
      </p>

      <p class="readaloud-funnel__hero-cta">
        <a class="btn btn-primary readaloud-funnel__btn"
           href="<?php echo esc_url( $bhp_cta['href'] ); ?>"
           data-readaloud-cta="hero">
          <?php echo esc_html( $bhp_cta['label'] ); ?>
        </a>
      </p>

      <?php if ( ! empty( $bhp_chips ) ) : ?>
        <ul class="school-readalouds__chips">
          <?php foreach ( $bhp_chips as $bhp_chip ) : ?>
            <li class="school-readalouds__chip"><?php echo esc_html( $bhp_chip ); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php
      /*
       * ═══════════════════════════════════════════════════════════════════════
       * ⛔⛔ THE HERO NOTE IS REMOVED AT 1.19.339 (`CYCLE170-LD-FINAL2`, carrier
       *     item 562, THE `chief-of-staff` IMPLEMENTATION RULING). THE SUPERSEDED BLOCK IS
       *     QUOTED VERBATIM RATHER THAN DELETED.
       * ═══════════════════════════════════════════════════════════════════════
       *
       *     <p class="readaloud-funnel__hero-note">
       *       <?php
       *       / *
       *        * ⚠ 1.19.325's string, KEPT UNCHANGED ON INSTRUCTION. The brief's
       *        *   wording is "currently free"; this says it flat. Andrew's open
       *        *   call, not this lane's to settle.
       *        * /
       *       esc_html_e( 'There is no charge.', 'brave-hearts' );
       *       ?>
       *     </p>
       *
       * ⭐ HIS OPEN CALL IS NOW SETTLED, AND HE SETTLED IT. The line stood since
       *    1.19.325 explicitly flagged as unresolved; item 562 resolves it by
       *    removing it.
       *
       * ⛔⛔ THE OFFER DID NOT CHANGE AND NOTHING NOW CHARGES FOR A READ-ALOUD.
       *     The fact this line carried is still stated TWICE in this hero, in the
       *     founder's own item-481 words: the `<h1>` reads "Book a free
       *     read-aloud" and the CTA button reads "Book a FREE read-aloud". It is
       *     stated a third time further down the page, outside the hero region
       *     this ruling scopes, by the fifth visit point ("I leave a signed copy
       *     for your classroom library, free.", item 541, untouched).
       *
       * ⭐ THE RULING IS A COUNT, AND THE COUNT IS ASSERTED MECHANICALLY.
       *    `tests/test-cycle170-final2.php` §2 parses the rendered
       *    `.readaloud-funnel__hero` section out of the live page and asserts
       *    EXACTLY TWO case-insensitive occurrences of "free" inside it — so a
       *    later pass that reintroduces a third saying fails a test rather than
       *    passing a careful reading.
       *
       * ⛔ NO OTHER HERO STRING MOVED. The eyebrow, the `<h1>`, the lead, the CTA
       *    label and the photograph's alt text are byte-identical to 1.19.338.
       */
      ?>
    </div>

    <?php
    /*
     * ⛔ THEME ASSET, NOT A REGISTRY ROW AND NOT A MEDIA-LIBRARY ATTACHMENT.
     *    The carousel further down this page is driven by the
     *    `bhp_school_visit_notes` option; this photograph deliberately is not,
     *    because the hero must render identically on any environment and an
     *    attachment ID that exists only on production renders as a broken image
     *    on staging. It reuses `bhp_author_visits_photo_url()` so there is ONE
     *    place in the codebase that knows where read-aloud photographs live.
     *
     * ⛔ `width`/`height` ARE THE REAL FILE DIMENSIONS (640x640) and they are
     *    load-bearing: they reserve the box so the CTA cannot be shifted by a
     *    late-arriving image.
     *
     * ⛔ THE ALT TEXT IS FACTUAL AND NAMES NOBODY BUT ANDREW. No child is named,
     *    no staff member is named, and it claims NOTHING about what the visit
     *    did to anyone (§3 never-invent). It says what is in the frame and stops.
     */
    $bhp_hero_photo = function_exists( 'bhp_author_visits_photo_url' )
      ? bhp_author_visits_photo_url( 'adams-elementary-read-aloud-hero.jpg' )
      : '';
    if ( '' !== $bhp_hero_photo ) :
    ?>
    <figure class="school-readalouds__hero-photo">
      <img class="school-readalouds__hero-photo-img"
           src="<?php echo esc_url( $bhp_hero_photo ); ?>"
           width="640" height="640"
           loading="eager" decoding="async"
           alt="<?php esc_attr_e( 'Andrew Signore reading to a classroom at Adams Elementary', 'brave-hearts' ); ?>">
    </figure>
    <?php endif; ?>

  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * b · WHAT A VISIT LOOKS LIKE — 1.19.333. NEW. Carrier item 523, APPROVED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ EVERY CHARACTER IN THIS SECTION'S FOUR POINTS AND ITS CLOSING COUPLET IS
 *     FOUNDER-APPROVED AND IS READ FROM `inc/readaloud-approved-copy.php`. This
 *     template does not write, join, shorten or reorder a single word of it.
 *     ⚠ Its provenance is SINGLE-SOURCE (the build brief citing item 523) and
 *     that is stated in full at the constant, not glossed over here.
 *
 * ⭐ IT SITS DIRECTLY UNDER THE HERO BECAUSE THAT IS THE TEACHER'S FIRST
 *    QUESTION. Everything else on this page — who he is, when he came, what it
 *    looked like — answers a question she only asks after she knows what the
 *    hour actually contains.
 *
 * ⛔ AN `<ol>`, NOT AN `<ul>`, AND NOT FOUR `<p>`s. The brief calls them "four
 *    numbered points"; the numbers are structure, so they are the list's own
 *    markers rather than typed into the strings. A screen reader announces
 *    "list of 4 items" and the copy stays clean of "1." characters.
 *
 * ⛔ NO CLAIM IS MADE ABOUT WHAT THE VISIT ACHIEVES for any child. The closing
 *    couplet states the founder's GOAL in his own approved words ("My goal is
 *    to empower kids...") and stops there. §3's never-invent list forbids the
 *    developmental or classroom-outcome claim this section would otherwise be
 *    the natural home for.
 */
?>
<?php if ( ! empty( $bhp_visit_points ) ) : ?>
<section class="section section--muted readaloud-visit" aria-labelledby="school-readalouds-visit-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="school-readalouds-visit-title" class="text-section-title"><?php esc_html_e( 'What a visit looks like', 'brave-hearts' ); ?></h2>
    </header>

    <ol class="readaloud-visit__points">
      <?php foreach ( $bhp_visit_points as $bhp_point ) : ?>
        <li class="readaloud-visit__point"><?php echo esc_html( $bhp_point ); ?></li>
      <?php endforeach; ?>
    </ol>

    <?php if ( '' !== $bhp_visit_close ) : ?>
      <p class="readaloud-visit__closing"><?php echo esc_html( $bhp_visit_close ); ?></p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * c · ⭐⭐ THE PROOF PAIR — ABOUT **BESIDE** THE CAROUSEL. 1.19.337.
 *     CARRIER ITEM 552. `CYCLE170-LD-MICRO`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐⭐ THE FOUNDER'S WORDS, verbatim, 2026-08-30, carrier item 552:
 *
 *      "I think we should bring the carousel up and put it with the 'About'
 *       make the carousel a little smaller so it fits. Then bring up the pick
 *       a week as close as possible to it"
 *
 *    ⛔ RELAYED through `chief-of-staff`; read first-hand at the carrier file
 *      before this edit. NOT witnessed by this desk.
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT ACTUALLY MOVED, AND IT IS ONLY POSITION AND WIDTH
 * ---------------------------------------------------------------------------
 * SUPERSEDED ORDER at 1.19.336, preserved so the movement is visible and is
 * not re-derived:
 *
 *     a hero -> b visit points -> c ABOUT -> d SCHEDULER -> e CAROUSEL
 *     -> f past -> g educator capture -> h pricing
 *
 * ORDER FROM 1.19.337:
 *
 *     a hero -> b visit points -> c ABOUT + CAROUSEL (one section, two
 *     columns at desktop) -> d SCHEDULER -> f past -> g educator capture
 *     -> h pricing
 *
 * ⛔⛔ NOT ONE APPROVED STRING CHANGED. The About slot still renders through
 *     `bhp_readaloud_funnel_render_slot()`; the carousel still reads
 *     `bhp_readaloud_carousel_photos()`, still takes its heading from
 *     `bhp_gallery_sections()`, and still shows each photograph with the alt
 *     Andrew published. ⭐ A copy diff of this release against 1.19.336 on this
 *     page is EMPTY, and the suite asserts it.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE PAIRING IS A GRID, AND THE DOM ORDER IS ABOUT-THEN-CAROUSEL AT EVERY
 *    WIDTH
 * ---------------------------------------------------------------------------
 * At desktop CSS places them side by side; on a phone there is no second
 * column and they stack in this same order, tightly. ⛔ NO `order`, no absolute
 * positioning and no transform is used, so keyboard order matches visible order
 * — the rule `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md` set for this
 * theme and the same one `page-positivity-news.php` follows this release.
 *
 * ⭐ "MAKE THE CAROUSEL A LITTLE SMALLER SO IT FITS" is done by giving the pair
 *    a `container--content` rather than the carousel's old `container--wide`,
 *    plus a scoped rail height. ⛔ The carousel COMPONENT is not forked and
 *    `template-parts/media/photo-carousel.php` is byte-untouched; only the box
 *    it sits in changed.
 *
 * ---------------------------------------------------------------------------
 * ⚠️ ONE HONEST CONSEQUENCE, STATED RATHER THAN DISCOVERED LATER
 * ---------------------------------------------------------------------------
 * The About section used to be a full-measure column of prose. Passage 4 is one
 * paragraph (items 530 / 512 trimmed the other three off THIS page), so it sits
 * comfortably in half the measure. ⛔ IF A FUTURE RULING RESTORES PASSAGES 1-3
 * BY EMPTYING `bhp_readaloud_trimmed_slots()`, THIS COLUMN BECOMES FOUR
 * PARAGRAPHS BESIDE A PHOTO RAIL and the pairing should be re-judged by eye.
 * That is one line in `inc/readaloud-approved-copy.php` and this comment is the
 * flag on it.
 *
 * ⛔ THE PLACEHOLDER MECHANISM IS UNCHANGED. A slot still renders the loud
 *    `[PENDING READ-BACK]` block if it is ever marked pending; moving a section
 *    does not soften a gate.
 */
$bhp_pair_has_photos = ( $bhp_gallery_total > 0 );
$bhp_gallery_sections = function_exists( 'bhp_gallery_sections' ) ? bhp_gallery_sections() : array();
$bhp_gallery_title    = isset( $bhp_gallery_sections['read-alouds']['title'] )
  ? (string) $bhp_gallery_sections['read-alouds']['title']
  : __( 'School read-alouds', 'brave-hearts' );
?>
<section class="section readaloud-funnel__founder school-readalouds__proof" aria-labelledby="school-readalouds-founder-title">
  <div class="container container--content school-readalouds__proof-grid<?php echo $bhp_pair_has_photos ? '' : ' school-readalouds__proof-grid--solo'; ?>">

    <div class="school-readalouds__proof-about">
      <header class="component-heading">
        <h2 id="school-readalouds-founder-title" class="text-section-title"><?php esc_html_e( 'About the read-aloud', 'brave-hearts' ); ?></h2>
      </header>
      <?php
      if ( function_exists( 'bhp_readaloud_funnel_render_slot' ) ) {
        bhp_readaloud_funnel_render_slot( 'founder-1' );
        bhp_readaloud_funnel_render_slot( 'founder-2' );
        bhp_readaloud_funnel_render_slot( 'founder-3' );
        /*
         * ⭐ 1.19.332 — THE FOURTH PASSAGE. Andrew approved FOUR passages at
         *    carrier item 512 and this section rendered only three slots, so the
         *    fourth had nowhere to print. `educators-1` was NOT reused for it:
         *    that slot is the teacher and librarian lead paragraph, it is
         *    machine-written copy still awaiting the strike pass, and the merge
         *    dropped its render call anyway. See inc/readaloud-approved-copy.php.
         *
         * ⛔ ALL FOUR CALLS ARE KEPT, UNCHANGED, IN THIS ORDER. Items 530 / 512
         *    trim the first three off THIS page from `readaloud-approved-copy.php`
         *    rather than from here, and that indirection is what makes the trim
         *    reversible from one file. Deleting the three dead calls while moving
         *    the section would have quietly taken that property away.
         */
        bhp_readaloud_funnel_render_slot( 'founder-4' );
      }
      ?>
    </div>

    <?php if ( $bhp_pair_has_photos ) : ?>
      <?php
      /*
       * ⛔ THE HEADING IS STILL THE REGISTRY'S OWN "School read-alouds", read
       *    from `bhp_gallery_sections()` so it cannot drift from `/gallery/`'s,
       *    and it is an `<h2>` exactly as it was — the pairing changed where it
       *    renders, not what it is in the document outline.
       *
       * ⛔ `locate_template()` + `include`, NOT `get_template_part()`, and that
       *    is the THEME'S OWN pattern rather than a preference. WordPress does
       *    not extract `get_template_part()`'s `$args` into local variables — it
       *    passes them through as a single `$args` array — and every other
       *    template part in this theme is included the way
       *    `bhp_book_render_hero_gallery()` includes `look-inside.php`: local
       *    variables set, `locate_template()`, `include`, and RENDER NOTHING if
       *    the file is missing so a partial deploy degrades to a gap rather than
       *    a fatal.
       *
       * ⛔ `$pc_id` IS UNCHANGED (`school-readalouds-carousel`). It is the join
       *    key `assets/js/photo-carousel.js` and three suites use; renaming it
       *    while moving the markup would have broken the arrows silently.
       */
      $bhp_sec_id = 'school-readalouds-gallery-read-alouds';
      ?>
      <div class="school-readalouds__proof-photos author-visits-gallery-section readaloud-funnel__gallery" aria-labelledby="<?php echo esc_attr( $bhp_sec_id ); ?>" role="group">
        <header class="component-heading">
          <h2 id="<?php echo esc_attr( $bhp_sec_id ); ?>" class="text-section-title"><?php echo esc_html( $bhp_gallery_title ); ?></h2>
        </header>
        <?php
        $photos   = $bhp_carousel_photos;
        $pc_label = $bhp_gallery_title;
        $pc_id    = 'school-readalouds-carousel';
        $bhp_pc_tpl = locate_template( 'template-parts/media/photo-carousel.php' );
        if ( '' !== $bhp_pc_tpl ) {
          include $bhp_pc_tpl;
        }
        ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * d · THE SCHEDULER. PROMOTED AT 1.19.333 — it is now the page's centre.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT ASKS, IT DOES NOT BOOK. See `inc/readaloud-scheduler.php` for the whole
 *    position and for why the mail is CAPTURED rather than sent on staging. The
 *    "This sends me a request. It does not book the day." line under the button
 *    is not decoration and is not removable.
 *
 * ⭐ THE ONLY CHANGE IS ITS POSITION AND ITS HEADING. Every control, every
 *    value, every `required`, the honeypot, the nonce and the server-side
 *    re-derivation of the offered dates are byte-identical to 1.19.332. ⛔ THIS
 *    IS A LAYOUT AND COPY CHANGE, NOT A VALIDATION CHANGE: no value can reach
 *    the handler that could not reach it before.
 */
if ( function_exists( 'bhp_school_readalouds_render_scheduler' ) ) {
	bhp_school_readalouds_render_scheduler();
}
?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * e · ⛔ THE STANDALONE CAROUSEL SECTION IS GONE FROM HERE. ITEM 552, 1.19.337.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐⭐ IT IS MOVED, NOT REMOVED. Every photograph, the registry heading, the
 *     alt text, the order, the captions, the arrows, the dots, the swipe rail
 *     and the `school-readalouds-carousel` id all still render — one section
 *     UP, beside the About passage, under founder item 552's own words:
 *     *"bring the carousel up and put it with the 'About'"*. See section c.
 *
 * ⛔ THIS COMMENT REPLACES THE MARKUP RATHER THAN THE MARKUP BEING DELETED
 *    SILENTLY. `CYCLE170-LD-CHAIN`'s process note in this repo is explicit that
 *    a removal a later reader cannot see is how a "restore" happens; a reader
 *    arriving here from item 519 (which put the carousel BELOW the form) needs
 *    to find out where it went rather than conclude it was dropped.
 *
 * ⛔ THE ITEM 519 REASONING IS SUPERSEDED, NOT WRONG. It placed the carousel
 *    below the form so nothing sat between a teacher and the scheduler. Item
 *    552 keeps that property by a different route: the pair is PROOF beside
 *    PITCH, and the form now follows both immediately with the minimum gap the
 *    stylesheet can give it. Nothing was reintroduced between the two.
 *
 * ⭐ THE CONSENT QUESTION ON THESE PHOTOGRAPHS REMAINS CLOSED — `CYCLE141-CX-48`
 *    was closed by Andrew's own attestation at carrier item 510.
 * ⛔ THE MARKETS CATEGORY IS UNTOUCHED AND `inc/gallery-page.php` IS UNEDITED.
 */
?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * f · PAST READ-ALOUDS — the trust column, now a full-width section.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ IT STANDS ALONE AT 1.19.333. It used to be the right-hand half of an
 *    `.author-visits-columns--split` pair whose left half was Upcoming Visits.
 *    Item 524 took the upcoming list off this page, so a two-column wrapper
 *    with one child would have rendered a half-width column beside empty space.
 *    ⛔ The `--split` modifier is therefore NOT emitted here; the class still
 *    exists and still governs `/author-visits/`, which is unedited.
 *
 * ⛔ EVERY WORD IN IT IS EITHER REGISTRY DATA OR FOUNDER-ATTESTED NOTE TEXT
 *    read from an option. NOTHING IN THIS BLOCK CLAIMS A REACTION. No parent
 *    said, no teacher said, no child said, no "loved", no count Andrew did not
 *    give. §3's never-invent list is not a style preference here: a trust
 *    section is precisely where a fabricated reaction would do the most damage
 *    and be the least likely to be challenged.
 *
 * ⛔ THE SCHOOL NAME COMES FROM THE REGISTRY AND NO INDIVIDUAL IS NAMED HERE. A
 *    school is an institution and is already public on this page.
 *
 * ⭐ NO BUTTON, NO ORDERING AFFORDANCE, NO `?bhp_visit=` LINK. A past visit can
 *    never be ordered for, so the row carries no control implying otherwise.
 *    ⛔ THAT IS ALSO WHY THIS SECTION IS NOT PART OF THE PARENT ZONE ITEM 524
 *    REMOVED: it contains no transaction at all.
 */
?>
<?php if ( ! empty( $bhp_past ) ) : ?>
<section class="section school-readalouds__past" aria-labelledby="school-readalouds-past-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="school-readalouds-past-title" class="text-section-title"><?php esc_html_e( 'Past Read-Alouds', 'brave-hearts' ); ?></h2>
    </header>

    <ul class="author-visits-past" aria-labelledby="school-readalouds-past-title">
      <?php foreach ( $bhp_past as $bhp_p ) : ?>
        <li class="author-visits-past__item">
          <h3 class="author-visits-past__school"><?php echo esc_html( $bhp_p['school'] ); ?></h3>
          <p class="author-visits-past__when"><?php echo esc_html( $bhp_p['date_display'] ); ?></p>

          <?php if ( '' !== $bhp_p['note'] ) : ?>
            <p class="author-visits-past__note"><?php echo esc_html( $bhp_p['note'] ); ?></p>
          <?php endif; ?>

          <?php if ( '' !== $bhp_p['recap_url'] ) : ?>
            <p class="author-visits-past__recap">
              <a href="<?php echo esc_url( $bhp_p['recap_url'] ); ?>">
                <?php esc_html_e( 'Read what happened', 'brave-hearts' ); ?>
                <span class="screen-reader-text"><?php echo esc_html( $bhp_p['school'] ); ?></span>
              </a>
            </p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * g · THE EDUCATOR EMAIL CAPTURE — THE TEACHER FUNNEL, AND THE ONLY TAIL ASK.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ FUNNEL ISOLATION IS THE RULE THIS SECTION RESPECTS, NOT ONE IT TESTS.
 *     `.claude/rules/funnels.md` and `docs/ENGINEERING/FUNNEL_CONSTITUTION.md`
 *     keep the parent and teacher funnels apart. This page feeds the TEACHER
 *     side, so it passes the educator landing page's EXACT pair —
 *     `lead_magnet` `teacher_adventure_toolkit` and `audience_type`
 *     `educators` — through the SAME `lead-magnet-cta` → `signup-form.php` →
 *     `bhp_mailchimp_signup` pipe, never a fork of it.
 *
 * ⭐ 1.19.333 — IT IS NOW THE ONLY ASK BELOW THE SCHEDULER. The quiz band and
 *    the sitewide footer capture that used to follow it are suppressed for this
 *    template in `inc/school-read-alouds.php`. A teacher who reaches the foot
 *    of this page meets ONE offer, and it is hers.
 *
 * ⛔ TAGGING IS UNCHANGED FROM 1.19.325, ON INSTRUCTION. The known consequence
 *    is unchanged with it: the third tag reads *"Source: Educator Landing Page"*
 *    for a signup that happened here. Per-page attribution is NOT lost —
 *    `source_page` carries THIS page's permalink into the `SOURCE` merge field,
 *    a different field from the tags. A dedicated source tag is one branch in
 *    `bhp_mailchimp_signup_tags` and is Andrew's call because it changes
 *    Mailchimp segmentation. Reported, not patched.
 *
 * ⛔ NOTHING PARENT-FUNNEL IS TOUCHED, REFERENCED OR RENDERED BY THIS TEMPLATE.
 */
?>
<section id="free" class="section section--muted readaloud-funnel__capture" aria-labelledby="school-readalouds-capture-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="school-readalouds-capture-title" class="text-section-title"><?php esc_html_e( 'Free classroom resources by email', 'brave-hearts' ); ?></h2>
    </header>

    <?php if ( ! empty( $bhp_toolkit['ready'] ) ) : ?>
      <?php
      get_template_part(
        'template-parts/acquisition/lead-magnet-cta',
        null,
        array(
          'id'            => 'school-readalouds-educator-signup',
          'lead_magnet'   => 'teacher_adventure_toolkit',
          'audience_type' => 'educators',
          'title'         => __( 'Send Me the Free Adventure Learning Toolkit', 'brave-hearts' ),
          /* Copy reused VERBATIM from the educator landing page. One offer, one description. */
          'text'          => __( 'Classroom-ready resources connecting the series to geography, science, history, vocabulary, and discussion.', 'brave-hearts' ),
          'submit_label'  => __( 'Get the Free Adventure Learning Toolkit', 'brave-hearts' ),
          'source_page'   => $bhp_source_page,
          'require_name'  => true,
        )
      );
      ?>
      <p class="readaloud-funnel__fine-print">
        <?php esc_html_e( 'Free printable PDF · No purchase required · Occasional classroom resource updates. Unsubscribe anytime.', 'brave-hearts' ); ?>
      </p>
    <?php else : ?>
      <?php /* ⛔ Honest gate. If the PDF is not there, nothing is offered. */ ?>
      <div class="author-visits-empty">
        <p class="component-heading__eyebrow"><?php esc_html_e( 'Coming soon', 'brave-hearts' ); ?></p>
        <p><?php esc_html_e( 'The Adventure Learning Toolkit is still being finished. Check back soon to get your free copy by email.', 'brave-hearts' ); ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * h · THE PRICING SLOT — STRUCTURAL, GATED OFF, EMPTY OF ANY FIGURE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THERE IS NO PRICE ON THIS PAGE AND NONE EXISTS TO PUT ON IT. Carrier item
 *     481: read-alouds are currently free. This section is a place for a future
 *     ruling to land, nothing more: it renders `hidden`, it is `display:none`
 *     in the stylesheet, and it contains NO figure, NO currency symbol and NO
 *     fee word. A suite asserts all of that against the rendered page.
 *
 * ⛔ `bhp_readaloud_funnel_show_pricing()` is FALSE and flipping it is not an
 *    engineering decision. Charging for a read-aloud is a founder ruling, and
 *    the copy for it does not exist.
 */
$bhp_show_pricing = function_exists( 'bhp_readaloud_funnel_show_pricing' ) ? bhp_readaloud_funnel_show_pricing() : false;
?>
<section
  id="school-readalouds-pricing"
  class="readaloud-funnel__pricing"
  data-readaloud-pricing="<?php echo $bhp_show_pricing ? 'on' : 'off'; ?>"
  aria-labelledby="school-readalouds-pricing-title"
  <?php echo $bhp_show_pricing ? '' : 'hidden'; ?>
>
  <div class="container container--content">
    <h2 id="school-readalouds-pricing-title" class="text-section-title"><?php esc_html_e( 'Booking details', 'brave-hearts' ); ?></h2>
    <?php if ( $bhp_show_pricing ) : ?>
      <div class="bhp-copy-placeholder" data-copy-slot="pricing-1" role="note">
        <p class="bhp-copy-placeholder__flag"><?php echo esc_html( '[PENDING READ-BACK — do not publish]' ); ?></p>
        <p class="bhp-copy-placeholder__label"><?php esc_html_e( 'BOOKING DETAILS — AWAITING A FOUNDER RULING', 'brave-hearts' ); ?></p>
        <p class="bhp-copy-placeholder__spec"><?php esc_html_e( 'No terms exist for this slot. Read-alouds are free today.', 'brave-hearts' ); ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

</div><?php /* .readaloud-funnel.school-readalouds */ ?>

<?php get_footer(); ?>
