<?php
/**
 * Template Name: Author Visits
 * Description: Public list of upcoming school read-aloud visits, with a per-visit order link.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * AUTHOR VISITS — 1.19.233 (2026-08-17, `CYCLE162-LD-VISITS-PAGE`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, RELAYED through the Chief of Staff and NOT witnessed by this
 * agent: *"we list the schools dates and times of the read alouds"*.
 *
 * ⭐ 1.19.239 (2026-08-18, `CYCLE164-LD-ORDER-WINDOW`) CHANGED EXACTLY ONE
 *    BRANCH OF THIS FILE: the CLOSED state of a visit row. The button is no
 *    longer removed — it stays, greyed and unclickable, and the row keeps its
 *    school, date, time and stated "Order by" line, so every read-aloud remains
 *    on the page as a trust record. ⛔ THE OPEN BRANCH IS BYTE-IDENTICAL TO
 *    1.19.238. Before the close, this page renders exactly what it rendered
 *    yesterday, and the test suite asserts that rather than assuming it.
 *
 * ⛔ THIS FILE IS COPY AND MARKUP ONLY. Every decision — which visits appear,
 *    which carry a button, what each button's URL is, how "today" is worked out
 *    — lives in `inc/author-visits.php`. Read that file first. The split exists
 *    so the logic is testable without rendering a page.
 *
 * ⛔ NO STRUCTURED DATA IS EMITTED HERE, and none may be added without a fresh
 *    decision. `Event` schema for a school read-aloud would be a public claim
 *    about a private classroom's timetable, and `.claude/rules/schema.md`
 *    requires any schema change to be verified in the rendered
 *    `rank-math-schema` block rather than assumed from the source.
 *
 * ⛔ NO PRICE, NO STOCK, NO PRODUCT AND NO COUPON IS NAMED. Prices drift; a
 *    page destined for printed QR codes must not carry one.
 *
 * ⭐ WHY THE DESIGN IS PLAIN. This page will be linked from print. It must
 *    render correctly on a phone, in one column, with no script running: there
 *    is no JavaScript on it at all, no image, no carousel and no popup of its
 *    own. Every class it uses is an existing theme token plus one scoped
 *    `author-visits*` block in `style.css`.
 *
 * ⚠ THE COPY BELOW IS A FIRST DRAFT PENDING ANDREW'S VOICE PASS. It was written
 *   against the constraints in the build brief: founder voice (I/me only, never
 *   "we"), no em dashes, no invented claim, no reading age stated. Nothing on
 *   this page describes a parent, teacher or child reaction, and nothing
 *   promises an outcome.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

$bhp_visit_rows = function_exists( 'bhp_author_visits_rows' ) ? bhp_author_visits_rows() : array();
$bhp_kit_url    = home_url( '/reluctant-reader-adventure-kit/' );

/*
 * ⭐ 1.19.319 (2026-08-29, `CYCLE169-LD-READALOUD-TRUST-GALLERY`) — THE TRUST
 *    COLUMN, THE GALLERY AND THE OCTOBER BOOKING CTA.
 *
 * Andrew Signore, verbatim, carrier item 432 (first-hand to the Chief of Staff,
 * commissioning this agent by name): *"putting a column for past read alouds on
 * the read-aloud site- I want more trust on that and lets put a picture gallery
 * of the read alouds on that page too."* And carrier item 412: *"I can take read
 * alouds in boise starting in october not I cant do them this season"*, restated
 * at item 429 as *"Yes, October is open for business"*.
 *
 * ⛔ EVERY GUARD IS `function_exists()`. This template must render on a site
 *    where `inc/author-visits.php` is present but older — the page degrades to
 *    exactly what it rendered at 1.19.239 rather than fataling.
 */
$bhp_visit_past   = function_exists( 'bhp_author_visits_past_rows' ) ? bhp_author_visits_past_rows() : array();
$bhp_visit_photos = function_exists( 'bhp_author_visits_gallery_photos' ) ? bhp_author_visits_gallery_photos( $bhp_visit_past ) : array();
?>

<section class="section section--dark author-visits-hero" aria-labelledby="author-visits-title">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e( 'Read-alouds in Idaho classrooms', 'brave-hearts' ); ?></p>
    <h1 id="author-visits-title" class="text-hero"><?php esc_html_e( 'Where I Am Reading Next', 'brave-hearts' ); ?></h1>
    <p class="text-lead">
      <?php esc_html_e( 'I visit Idaho classrooms to read aloud. If your child’s school is on this list, you can order books before the visit. I sign each book to your child by name and hand it to them at the visit.', 'brave-hearts' ); ?>
    </p>
  </div>
</section>

<section class="section author-visits-list-section" aria-labelledby="author-visits-list-title">
  <div class="container container--content">

  <?php
  /*
   * ⭐ THE TWO COLUMNS. Andrew asked for a "column for past read alouds", and
   *    this is that word taken literally: at 64rem and wider, upcoming sits
   *    beside past. BELOW 64rem THEY STACK, upcoming first, and the page is
   *    single-column exactly as it has been since 1.19.233.
   *
   * ⛔ THAT ORDER IS NOT COSMETIC. This page is reached from PRINTED QR CODES
   *    taped to classroom doors, so the phone reader is the primary reader, and
   *    the thing they scanned the code to find is the NEXT visit. History must
   *    never push the ordering button below the fold on a phone. The grid is
   *    declared so the DOM order and the visual order agree at every width —
   *    no `order`, no `row-reverse`, nothing a screen reader would read in a
   *    different sequence than a sighted reader sees.
   */
  ?>
  <div class="author-visits-columns<?php echo empty( $bhp_visit_past ) ? '' : ' author-visits-columns--split'; ?>">

    <div class="author-visits-col author-visits-col--upcoming">
    <header class="component-heading">
      <h2 id="author-visits-list-title" class="text-section-title"><?php esc_html_e( 'Upcoming Visits', 'brave-hearts' ); ?></h2>
    </header>

    <?php if ( empty( $bhp_visit_rows ) ) : ?>

      <?php /* EMPTY STATE. Short, honest, and it promises nothing about when the next date will exist. */ ?>
      <div class="author-visits-empty">
        <p><?php esc_html_e( 'No visits are on the calendar right now.', 'brave-hearts' ); ?></p>
        <p><?php esc_html_e( 'While you wait, the Reluctant Reader Adventure Kit is free to download.', 'brave-hearts' ); ?></p>
        <p class="author-visits-empty__cta">
          <a class="btn btn-primary" href="<?php echo esc_url( $bhp_kit_url ); ?>"><?php esc_html_e( 'Get the free Adventure Kit', 'brave-hearts' ); ?></a>
        </p>
      </div>

    <?php else : ?>

      <ul class="author-visits-list">
        <?php foreach ( $bhp_visit_rows as $bhp_row ) : ?>
          <li class="author-visits-card<?php echo empty( $bhp_row['open'] ) ? ' author-visits-card--closed' : ''; ?>">
            <h3 class="author-visits-card__school"><?php echo esc_html( $bhp_row['school'] ); ?></h3>

            <?php /* Date always. Time ONLY if the registry row carries one — a row with no time renders date-only, never an empty separator. */ ?>
            <p class="author-visits-card__when">
              <span class="author-visits-card__date"><?php echo esc_html( $bhp_row['date_display'] ); ?></span>
              <?php if ( '' !== $bhp_row['time'] ) : ?>
                <span class="author-visits-card__sep" aria-hidden="true">&middot;</span>
                <span class="author-visits-card__time"><?php echo esc_html( $bhp_row['time'] ); ?></span>
              <?php endif; ?>
            </p>

            <?php if ( ! empty( $bhp_row['open'] ) && '' !== $bhp_row['url'] ) : ?>

              <p class="author-visits-card__note">
                <?php
                printf(
                  /* translators: %s: the last date an order may be placed, e.g. "Monday, August 25" */
                  esc_html__( 'Order by %s and I will have your child’s book signed and in my bag on the day.', 'brave-hearts' ),
                  /* ⭐ 1.19.350-FIX: `deadline`, not `cutoff` — the ONE date the shop band
                     also prints. It equals `cutoff` on every conventionally entered row and
                     can never be later than it, so the grace window stays unadvertised.
                     ⛔ SUPERSEDED, PRESERVED: this printed `$bhp_row['cutoff']` directly. */
                  esc_html( bhp_author_visits_format_date( $bhp_row['deadline'] ) )
                );
                ?>
              </p>
              <p class="author-visits-card__cta">
                <a class="btn btn-primary" href="<?php echo esc_url( $bhp_row['url'] ); ?>"><?php esc_html_e( 'Order signed books for this visit', 'brave-hearts' ); ?></a>
              </p>

            <?php elseif ( ! empty( $bhp_row['after'] ) && '' !== $bhp_row['url'] ) : ?>

              <?php
              /*
               * ⭐⭐⭐ AFTER-VISIT — 1.19.357 (`CYCLE179-LD-357`). THE READ-ALOUD HAS
               *     HAPPENED AND THE FUNNEL IS OPEN AGAIN, FOR SHIPPING ONLY.
               *
               * Andrew Signore, RELAYED through the `chief-of-staff` brief and NOT
               * witnessed first-hand by this agent (Standing Rules 9.2 rule 2), seal 868:
               * *"we need to reopen the link to schools after but only for shipping
               * instead of hand delivery ... it should open back up for parents to move
               * through that funnel."*
               *
               * ⛔ THE "Order by" LINE IS GONE FROM THIS BRANCH ON PURPOSE. That deadline
               *    is the HAND-DELIVERY deadline; it has passed, and repeating it beside a
               *    live ordering link would tell a parent the thing they are looking at is
               *    already over. ⛔ AND NO NEW DEADLINE REPLACES IT. There is no urgency
               *    here that is real, and the after-visit window is an internal setting
               *    rather than a promise made to anybody, so it is not advertised.
               *
               * ⛔ THE BUTTON SAYS "shipped", NEVER "signed". The open-state button above
               *    says *"Order signed books for this visit"* because Andrew signs those
               *    books in person on the day. These are printed and posted and nobody
               *    signs them. The label is read from `bhp_visit_band_after_link_label()`
               *    so that the shop band and this card cannot come to say different
               *    things, which is the same discipline `bhp_visit_deadline_display()`
               *    imposed on the two deadline sentences at 1.19.350-FIX.
               *
               * ⛔ NOTHING HERE CLAIMS THE VISIT WENT WELL. "Read-aloud done" is a
               *    statement of fact about the calendar. No reaction, no count, no
               *    outcome, and nothing a parent, a teacher or a child is said to have
               *    said. Standing Rules 3.
               */
              ?>
              <p class="author-visits-card__note author-visits-card__note--after">
                <?php esc_html_e( 'Read-aloud done. Books can still be ordered and shipped to your home.', 'brave-hearts' ); ?>
              </p>
              <p class="author-visits-card__cta">
                <a class="btn btn-primary" href="<?php echo esc_url( $bhp_row['url'] ); ?>">
                  <?php
                  echo esc_html(
                    function_exists( 'bhp_visit_band_after_link_label' )
                      ? bhp_visit_band_after_link_label()
                      : __( 'Order books shipped to your home', 'brave-hearts' )
                  );
                  ?>
                </a>
              </p>

            <?php else : ?>

              <?php
              /*
               * ⭐ CLOSED — 1.19.239 (`CYCLE164-LD-ORDER-WINDOW`). THE ROW KEEPS ITS FULL
               *    SHAPE AND THE BUTTON STAYS, GREYED. Andrew Signore, RELAYED through the
               *    Chief of Staff and NOT witnessed by this agent: *"then just make the
               *    button unclickable - keep it up to keep a trust record of all the read
               *    alouds I will be doing"* and *"the button goes off and gets greyed out
               *    the morning 1 day before the read aloud"*.
               *
               * ⛔ SUPERSEDED MARKUP, PRESERVED SO THE MOVEMENT IS VISIBLE:
               *      <p class="author-visits-card__note author-visits-card__note--closed">
               *        <?php esc_html_e( 'Ordering for this visit has closed.', ... ); ?>
               *      </p>
               *    The button used to be REMOVED and replaced by that sentence alone.
               *
               * ⛔ IT IS A <span>, NOT AN <a>. Not an `<a>` without an href, not an `<a>`
               *    with `onclick="return false"`, not a `<button disabled>` (which would be
               *    a form control in a page that has no form). A `<span>` is not focusable,
               *    carries no href a browser could follow, cannot be middle-clicked into a
               *    new tab and cannot be copied as a link address. `bhp_author_visits_rows()`
               *    also returns an EMPTY url for a closed row, so there is nothing to leak
               *    even if this markup were changed carelessly.
               *
               * ⛔ THE "Order by" LINE STAYS AND STILL PRINTS THE **STATED** DEADLINE
               *    (`cutoff`, three days before the visit) — the date parents were emailed.
               *    ⛔ NOTHING HERE MENTIONS THAT ORDERING ACTUALLY RAN A DAY LONGER. The
               *    grace window is deliberate and is never advertised.
               *
               * ♿ `role="link"` + `aria-disabled="true"` is the accessible-disabled-control
               *    pattern: assistive technology announces it and marks it unavailable,
               *    while the absence of `tabindex` keeps it out of the keyboard tab order.
               *    The full sentence is kept for screen readers in `.screen-reader-text`,
               *    because "Ordering closed" alone is terse out of visual context.
               */
              ?>
              <p class="author-visits-card__note author-visits-card__note--closed">
                <?php
                printf(
                  /* translators: %s: the deadline that was published for this visit, e.g. "Monday, August 25" */
                  esc_html__( 'Order by %s.', 'brave-hearts' ),
                  /* ⭐ 1.19.350-FIX: `deadline`, not `cutoff`. Identical on every
                     `visit - 3` row — the sentence above about the STATED deadline still
                     holds, and still cannot mention the grace window, because
                     `bhp_visit_deadline_display()` never returns a date later than
                     `cutoff`. ⛔ SUPERSEDED, PRESERVED: this printed `$bhp_row['cutoff']`. */
                  esc_html( bhp_author_visits_format_date( $bhp_row['deadline'] ) )
                );
                ?>
              </p>
              <p class="author-visits-card__cta">
                <span class="btn btn-primary author-visits-card__btn--closed" role="link" aria-disabled="true"><?php esc_html_e( 'Ordering closed', 'brave-hearts' ); ?></span>
                <span class="screen-reader-text"><?php esc_html_e( 'Ordering for this visit has closed.', 'brave-hearts' ); ?></span>
              </p>

            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

    <?php endif; ?>
    </div><?php /* .author-visits-col--upcoming */ ?>

    <?php if ( ! empty( $bhp_visit_past ) ) : ?>
      <?php
      /*
       * ⛔ THE TRUST COLUMN. It exists because Andrew asked for "more trust on
       *    that", and trust here means one thing only: a verifiable record that
       *    these visits actually happened. So EVERY WORD IN IT IS EITHER
       *    REGISTRY DATA OR FOUNDER-ATTESTED NOTE TEXT read from an option.
       *
       * ⛔ NOTHING IN THIS BLOCK CLAIMS A REACTION. No parent said, no teacher
       *    said, no child said, no "loved", no count that Andrew did not give.
       *    Standing Rules §3's never-invent list is not a style preference here:
       *    a trust section is precisely where a fabricated reaction would do the
       *    most damage and be the least likely to be challenged.
       *
       * ⛔ THE SCHOOL NAME COMES FROM THE REGISTRY AND THE LIBRARIAN IS NEVER
       *    NAMED. A school is an institution and is already public on this page
       *    for the upcoming list; a librarian is a private individual.
       *
       * ⭐ NO BUTTON, NO ORDERING AFFORDANCE, NO `?bhp_visit=` LINK. A past visit
       *    can never be ordered for, so the row carries no control that could
       *    imply otherwise. The only link is to the recap, and only if one exists.
       *
       * ⚠️⚠️ 1.19.357 (`CYCLE179-LD-357`) — THE PARAGRAPH IMMEDIATELY ABOVE IS
       *     PRESERVED VERBATIM AND IS NO LONGER TRUE FOR A BOUNDED WINDOW. It is
       *     corrected here rather than deleted, because a reader arriving from the
       *     1.19.319 release notes needs to know the rule moved and which way.
       *
       * ⭐ WHAT CHANGED, AND ONLY THIS: for the
       *    `bhp_school_visit_after_days()` days that follow a read-aloud, the row
       *    carries ONE link, to the same `?bhp_visit=` shop URL, labelled for
       *    shipping. Andrew Signore, RELAYED, seal 868: *"it should open back up
       *    for parents to move through that funnel."* The full reasoning for why
       *    it is this column and not only the upcoming one is on
       *    `bhp_author_visits_build_past_rows()` and is not restated here.
       *
       * ⛔ WHAT DID NOT CHANGE: once the window passes, `$bhp_past['url']` is ''
       *    and this row is again exactly the trust record it has been since
       *    1.19.319.
       *
       * ⚠️⚠️ 1.19.358 (2026-09-03, `CYCLE179-LD-358`) — THE TWO SENTENCES ABOVE
       *     ARE PRESERVED VERBATIM AND NO LONGER DESCRIBE THE DEFAULT. ⛔ THERE
       *     IS NO WINDOW TO PASS: Andrew ruled the after-visit state stays open
       *     INDEFINITELY (seal 870, RELAYED, NOT witnessed first-hand by this
       *     agent). Under the shipped default a past row keeps its shipping link
       *     for good, and `bhp_school_visit_after_days()` no longer names a
       *     number of days. Both sentences remain exactly right for a BOUNDED
       *     window, which the `bhp_school_visit_after_days` filter can still set.
       *     ⭐ NOT ONE LINE OF MARKUP OR COPY IN THIS COLUMN MOVED FOR 1.19.358.
       *
       * ⛔ EVERY OTHER WORD IN THIS COLUMN IS STILL EITHER REGISTRY
       *    DATA OR FOUNDER-ATTESTED NOTE TEXT, and the never-invent rule above
       *    binds this branch exactly as it binds the rest.
       */
      ?>
      <div class="author-visits-col author-visits-col--past">
        <header class="component-heading">
          <h2 id="author-visits-past-title" class="text-section-title"><?php esc_html_e( 'Past Read-Alouds', 'brave-hearts' ); ?></h2>
        </header>

        <ul class="author-visits-past" aria-labelledby="author-visits-past-title">
          <?php foreach ( $bhp_visit_past as $bhp_past ) : ?>
            <li class="author-visits-past__item">
              <h3 class="author-visits-past__school"><?php echo esc_html( $bhp_past['school'] ); ?></h3>
              <p class="author-visits-past__when"><?php echo esc_html( $bhp_past['date_display'] ); ?></p>

              <?php if ( '' !== $bhp_past['note'] ) : ?>
                <p class="author-visits-past__note"><?php echo esc_html( $bhp_past['note'] ); ?></p>
              <?php endif; ?>

              <?php if ( '' !== $bhp_past['recap_url'] ) : ?>
                <p class="author-visits-past__recap">
                  <a href="<?php echo esc_url( $bhp_past['recap_url'] ); ?>">
                    <?php esc_html_e( 'Read what happened', 'brave-hearts' ); ?>
                    <span class="screen-reader-text"><?php echo esc_html( $bhp_past['school'] ); ?></span>
                  </a>
                </p>
              <?php endif; ?>

              <?php
              /*
               * ⭐ 1.19.357 — THE SHIPPING LINK, AND ONLY WHILE THE WINDOW IS OPEN.
               *
               * ⚠️ 1.19.358: THE HEADING ABOVE IS PRESERVED AND ITS SECOND CLAUSE IS
               *    NOW VACUOUS BY DEFAULT. The window has no end (seal 870, RELAYED),
               *    so "while the window is open" means "always, from the visit date".
               *    The two guards below are UNCHANGED and still both required.
               *
               * ⛔ IT IS GUARDED ON THE URL ITSELF, not on the `after` flag, so a row
               *    that somehow carried the flag without a URL renders nothing rather
               *    than an empty `href`. `bhp_author_visits_build_past_rows()` already
               *    returns '' for every row outside the window, so this is the second of
               *    two independent guards on the same fact.
               * ⛔ THE SCREEN-READER SPAN NAMES THE SCHOOL, because a page carrying
               *    several of these would otherwise announce several identical links.
               */
              ?>
              <?php if ( ! empty( $bhp_past['url'] ) ) : ?>
                <p class="author-visits-past__order">
                  <a class="btn btn-secondary" href="<?php echo esc_url( $bhp_past['url'] ); ?>">
                    <?php
                    echo esc_html(
                      function_exists( 'bhp_visit_band_after_link_label' )
                        ? bhp_visit_band_after_link_label()
                        : __( 'Order books shipped to your home', 'brave-hearts' )
                    );
                    ?>
                    <span class="screen-reader-text"><?php echo esc_html( $bhp_past['school'] ); ?></span>
                  </a>
                </p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

  </div><?php /* .author-visits-columns */ ?>

  <?php
  /*
   * ⛔ "HOW IT WORKS" MOVED OUT OF THE COLUMNS, AND ITS CONDITION IS UNCHANGED.
   *    It still renders if and only if there is at least one UPCOMING visit,
   *    exactly as it did at 1.19.239 — it describes how to order, and there is
   *    nothing to order when the upcoming list is empty. It sits below both
   *    columns rather than inside the left one so it reads as instructions for
   *    the page, not as a footnote to the upcoming list.
   *
   * ⚠️⚠️ 1.19.358 (2026-09-03, `CYCLE179-LD-358`) — THE CONDITION ABOVE IS
   *    SUPERSEDED AND IS PRESERVED VERBATIM SO THE MOVEMENT IS VISIBLE. The
   *    superseded line was:
   *
   *        <?php if ( ! empty( $bhp_visit_rows ) ) : ?>
   *
   * ⛔ WHY IT MOVED. `CYCLE179-LD-23`, raised by this lane at 1.19.357 and
   *    OBSERVED on the live page rather than inferred: these three steps and a
   *    "Read-aloud done" card could sit on ONE SCREEN. Step 2 tells a parent to
   *    choose free author hand-delivery at checkout and step 3 says the books
   *    are handed to their child at the school. Once the read-aloud has
   *    happened that option is gone and the order ships, so the steps would be
   *    instructions for something the site refuses.
   *
   * ⭐ THE NEW CONDITION IS "AT LEAST ONE VISIT IS CURRENTLY OPEN", which is
   *    strictly narrower than the old one and never wider. Every page that
   *    rendered the block before and still has an open visit renders it
   *    byte-identically.
   *
   * ⛔ NOT ONE WORD OF THE COPY BELOW IS TOUCHED. This is a display gate over
   *    approved strings. Copy is Andrew's gate.
   */
  ?>
  <?php if ( bhp_author_visits_has_open_row( $bhp_visit_rows ) ) : ?>
      <div class="author-visits-how">
        <h2 class="author-visits-how__title"><?php esc_html_e( 'How It Works', 'brave-hearts' ); ?></h2>
        <ol class="author-visits-how__steps">
          <li><?php esc_html_e( 'Find your child’s school above and open the shop from that button.', 'brave-hearts' ); ?></li>
          <li><?php esc_html_e( 'At checkout, choose the free author hand-delivery option and tell me your child’s first name.', 'brave-hearts' ); ?></li>
          <li><?php esc_html_e( 'I sign the books and hand them to your child at the school on the day of the visit.', 'brave-hearts' ); ?></li>
        </ol>
      </div>
  <?php endif; ?>

  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * THE PICTURE GALLERY. Item 432: *"lets put a picture gallery of the read
 * alouds on that page too"*.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE PERMISSION POSITION, WRITTEN DOWN BECAUSE IT IS THE RISKIEST THING ON
 *    THIS PAGE. Every photograph rendered here is one Andrew has ALREADY
 *    PUBLISHED HIMSELF on the recap post, by his own hands (carrier item 424).
 *    Their clearance rests on his first-hand statement at item 368: *"all kids
 *    already had permission slips signed to have photos taken which I can
 *    use"*, and on item 393, where he asked for read-aloud pictures *"with all
 *    the kids"* in customer-facing material. Nothing here is a new disclosure.
 *
 * ⛔ NO CHILD IS NAMED, IN THE MARKUP OR IN ANY ALT STRING. The librarian is
 *    not named either. Alt text describes what is in the frame and stops.
 *
 * ⛔ THE ALT TEXT IS NOT WRITTEN HERE. It travels with each photograph in the
 *    notes option and is the same text already published on the recap post, so
 *    the two surfaces cannot drift into describing the same photograph
 *    differently. A photo with no alt text is DROPPED by
 *    `bhp_author_visits_notes()` rather than rendered with an empty attribute.
 *
 * ⭐ `loading="lazy"` + `decoding="async"` + explicit `width`/`height`. The
 *    dimensions are not decoration: without them the browser cannot reserve the
 *    box before the bytes arrive and the October CTA below jumps down the page
 *    as each photograph loads. This page is mostly read on a phone on a school
 *    parking lot connection.
 */
?>
<?php if ( ! empty( $bhp_visit_photos ) ) : ?>
<section class="section author-visits-gallery-section" aria-labelledby="author-visits-gallery-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="author-visits-gallery-title" class="text-section-title"><?php esc_html_e( 'From the Read-Alouds', 'brave-hearts' ); ?></h2>
    </header>

    <ul class="author-visits-gallery">
      <?php foreach ( $bhp_visit_photos as $bhp_photo ) : ?>
        <?php
        $bhp_photo_url = function_exists( 'bhp_author_visits_photo_url' ) ? bhp_author_visits_photo_url( $bhp_photo['file'] ) : '';
        if ( '' === $bhp_photo_url ) {
          continue;
        }
        ?>
        <li class="author-visits-gallery__item">
          <figure class="author-visits-gallery__figure">
            <img
              class="author-visits-gallery__img"
              src="<?php echo esc_url( $bhp_photo_url ); ?>"
              alt="<?php echo esc_attr( $bhp_photo['alt'] ); ?>"
              <?php if ( $bhp_photo['w'] && $bhp_photo['h'] ) : ?>
              width="<?php echo esc_attr( (string) $bhp_photo['w'] ); ?>"
              height="<?php echo esc_attr( (string) $bhp_photo['h'] ); ?>"
              <?php endif; ?>
              loading="lazy"
              decoding="async"
            />
            <?php if ( '' !== $bhp_photo['school'] || '' !== $bhp_photo['date_display'] ) : ?>
              <figcaption class="author-visits-gallery__caption">
                <?php
                /* Registry data only. The caption states which visit the photograph is from and nothing else. */
                echo esc_html( trim( $bhp_photo['school'] . ( '' !== $bhp_photo['date_display'] ? ', ' . $bhp_photo['date_display'] : '' ) ) );
                ?>
              </figcaption>
            <?php endif; ?>
          </figure>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * THE OCTOBER BOOKING CTA. Carrier items 412 and 429.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THIS CLOSES THE NO-BOOKING-SURFACE GAP. Until now the visits page listed
 *    where Andrew is going and offered a teacher or librarian reading it NO WAY
 *    TO ASK HIM TO COME. Item 412 amended item 309's calendar-full position:
 *    *"I can take read alouds in boise starting in october"*.
 *
 * ⛔ IT IS A `mailto:`, NOT A NEW FORM, AND THAT IS A DELIBERATE SCOPE LINE.
 *    Building a request-form system — fields, validation, storage, spam
 *    handling, notification — is a separate build with its own approval
 *    surface. The email address is already public on this site and is the route
 *    Andrew gave parents himself in the visit-completed email (item 377:
 *    *"Feel free to email me any time at Andrew@braveheartspublishing.com"*).
 *    A proper request form is FLAGGED FOR PHASE 2, not quietly half-built here.
 *
 * ⛔ THE SUBJECT LINE IS PRE-FILLED AND THE BODY IS NOT. A prefilled body puts
 *    words in a stranger's mouth and gets deleted before it is read; a subject
 *    line just files the mail.
 *
 * ⛔ NO PROMISE IS MADE. It does not say he will say yes, does not name a fee,
 *    does not state availability beyond the month he actually gave, and does
 *    not claim any past school was pleased.
 */
$bhp_visit_email   = 'andrew@braveheartspublishing.com';
$bhp_visit_subject = rawurlencode( 'Read-aloud request' );
?>
<section class="section section--muted author-visits-booking" aria-labelledby="author-visits-booking-title">
  <div class="container container--content">
    <div class="author-visits-booking__inner">
      <h2 id="author-visits-booking-title" class="author-visits-booking__title"><?php esc_html_e( 'Book a read-aloud for your school', 'brave-hearts' ); ?></h2>
      <p class="author-visits-booking__lead">
        <?php esc_html_e( 'My calendar is open for Boise-area classroom read-alouds from October onward. If you are a teacher or a librarian and you would like me to come and read, email me and tell me your school and which grades you have in mind.', 'brave-hearts' ); ?>
      </p>
      <p class="author-visits-booking__cta">
        <a class="btn btn-primary" href="<?php echo esc_url( 'mailto:' . $bhp_visit_email . '?subject=' . $bhp_visit_subject ); ?>">
          <?php esc_html_e( 'Email me about a read-aloud', 'brave-hearts' ); ?>
        </a>
      </p>
      <p class="author-visits-booking__address">
        <?php echo esc_html( $bhp_visit_email ); ?>
      </p>
    </div>
  </div>
</section>

<?php get_footer(); ?>
