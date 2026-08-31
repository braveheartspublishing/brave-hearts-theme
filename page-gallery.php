<?php
/**
 * Template Name: Read-Aloud Funnel
 * Description: The read-aloud booking funnel. The photo gallery is one section
 * inside it, not the point of it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * READ-ALOUD FUNNEL — 1.19.325 (2026-08-29, `CYCLE169-LD-READALOUD-FUNNEL`).
 * STAGING ONLY. Staging page 6087, slug `gallery`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHY THIS FILE WAS REWRITTEN. Andrew reviewed the 1.19.319 build of this
 *    page — headline *"Out in the World"*, three photographs, nothing else —
 *    and REJECTED IT AS FINAL. Carrier item 480, relayed in the build brief:
 *    **a photo wall, not a funnel.** He was right. The page asked a teacher for
 *    nothing, offered her nothing, and had no route by which anybody could book
 *    the thing the photographs were of.
 *
 * ⭐ CARRIER ITEM 481: READ-ALOUDS ARE CURRENTLY FREE. That is the offer, and
 *    it is why the primary CTA says FREE in his own capitals.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE SECTION ORDER IS FOUNDER-RULED. Do not reorder it on taste.
 * ---------------------------------------------------------------------------
 *   1. Above-the-fold hero with the booking CTA
 *   2. Founder intro                       ← PENDING COPY, placeholder slots
 *   3. The photo gallery                   ← approved, reused UNCHANGED
 *   4. Teachers and librarians + 2nd CTA   ← PENDING COPY, placeholder slot
 *   5. Educator email capture              ← the TEACHER funnel, not the parent one
 *   6. Pricing slot                        ← structural, gated OFF, no price text
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ NOT ONE WORD OF FOUNDER-VOICE PROSE WAS WRITTEN BY THE LANE THAT BUILT
 *     THIS FILE, AND THAT IS THE MOST IMPORTANT LINE IN THIS HEADER.
 * ---------------------------------------------------------------------------
 * A concurrent marketing lane is drafting the real passages; they land after
 * Andrew's morning read-back. Until then every narrative passage renders as a
 * loud `[PENDING READ-BACK — do not publish]` block that no one could mistake
 * for finished copy. §27: the founder is interviewed, never invented.
 *
 * ⭐ THE THREE SENTENCES THAT ARE NOT PLACEHOLDERS, AND EXACTLY WHERE EACH CAME
 *    FROM — stated here so nobody has to guess which strings are load-bearing:
 *
 *    · The hero lead is **reused VERBATIM** from the already-approved October
 *      booking copy shipped on `/author-visits/` at 1.19.319. Reusing approved
 *      words is safer than writing new ones, and it keeps the two surfaces from
 *      drifting into two different descriptions of one offer.
 *    · *"Book a FREE read-aloud"* is Andrew's own CTA wording, item 481, by way
 *      of the build brief.
 *    · *"There is no charge."* is the plain statement of item 481 and carries no
 *      promise beyond it. ⚠ The brief's wording is *"currently free"*; the word
 *      "currently" is NOT on the page. Flagged for his ruling rather than
 *      decided here — see the deploy plan.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE COPY RAILS, RESTATED BECAUSE THIS IS THE FILE SOMEONE WILL EDIT
 * ---------------------------------------------------------------------------
 *   · Andrew's I-voice. NO "we", "us" or "our" in any visible string (§9.1).
 *   · NO price, fee, rate or figure. None exists (item 481).
 *   · NO review, rating, testimonial, reaction, result, statistic or award.
 *   · NO child named. NO librarian named. NO school invented.
 *   · Reading age 6–9, NEVER 5–9. American spelling (§24).
 *   · Approved gallery captions and alt text are reused byte-for-byte.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

$bhp_cta         = function_exists( 'bhp_readaloud_funnel_cta' ) ? bhp_readaloud_funnel_cta() : array( 'href' => '', 'label' => '', 'email' => '' );
$bhp_source_page = get_permalink( get_queried_object_id() ) ?: home_url( '/' );

/*
 * The educator lead magnet, resolved BEFORE anything is printed. The educator
 * landing page gates its whole capture panel on this same flag, and this page
 * does the same rather than advertising a PDF that may not be there.
 */
$bhp_toolkit = function_exists( 'bhp_get_teacher_toolkit_download' )
	? bhp_get_teacher_toolkit_download()
	: array( 'url' => '', 'ready' => false );

$bhp_gallery_sections = function_exists( 'bhp_gallery_sections' ) ? bhp_gallery_sections() : array();
$bhp_gallery_total    = 0;
foreach ( $bhp_gallery_sections as $bhp_sec ) {
	$bhp_gallery_total += count( $bhp_sec['photos'] );
}
?>

<div class="readaloud-funnel">

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE HERO. The CTA must be visible WITHOUT SCROLLING at 1440 and at 375.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THAT IS A MEASURED REQUIREMENT, NOT A DESIGN INTENTION. The site header is
 *    sticky and roughly 93px tall, so the hero's whole budget is the viewport
 *    minus that. This section therefore does NOT use the sitewide `.section`
 *    80px vertical padding — it declares its own, smaller, in the stylesheet.
 *    If someone later restores the sitewide padding here, the CTA drops below
 *    the fold on a phone and the founder ruling is quietly broken.
 *
 * ⛔ THE BUTTON SITS ABOVE THE SUPPORTING LINE, not below it. One fewer line of
 *    text between the visitor and the only action on the screen.
 */
?>
<section class="section section--dark readaloud-funnel__hero" aria-labelledby="readaloud-funnel-hero-title">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e( 'Read-alouds', 'brave-hearts' ); ?></p>

    <h1 id="readaloud-funnel-hero-title" class="text-hero readaloud-funnel__hero-title">
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

    <p class="readaloud-funnel__hero-note">
      <?php esc_html_e( 'There is no charge.', 'brave-hearts' ); ?>
    </p>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE FOUNDER INTRO. Three slots, all PENDING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ These render as placeholders on purpose and they look wrong on purpose.
 *    The heading below is a structural label, not founder prose.
 */
?>
<section class="section readaloud-funnel__founder" aria-labelledby="readaloud-funnel-founder-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="readaloud-funnel-founder-title" class="text-section-title"><?php esc_html_e( 'About the read-aloud', 'brave-hearts' ); ?></h2>
    </header>
    <?php
    bhp_readaloud_funnel_render_slot( 'founder-1' );
    bhp_readaloud_funnel_render_slot( 'founder-2' );
    bhp_readaloud_funnel_render_slot( 'founder-3' );
    ?>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE PHOTO GALLERY — REUSED EXACTLY. Approved assets, untouched.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE MARKUP, CLASSES, ALT TEXT AND CAPTIONS BELOW ARE THE 1.19.319 GALLERY
 *    BYTE-FOR-BYTE, minus the standalone page's own <h1>, which the funnel hero
 *    now owns. The photographs are founder-cleared and their alt text is reused
 *    verbatim from the attachments he published himself, so the two surfaces
 *    cannot drift. An empty category is HIDDEN, never rendered as a
 *    "coming soon" — an empty heading advertises an absence.
 *
 * ⛔ THE MARKETS CATEGORY IS STILL EMPTY, AND STILL DELIBERATELY SO. No farmers
 *    market photograph exists (finding F2 of the 1.19.319 candidate, folder
 *    opened and empty). Nothing has been invented or substituted to fill it.
 */
?>
<?php if ( $bhp_gallery_total > 0 ) : ?>
  <?php foreach ( $bhp_gallery_sections as $bhp_key => $bhp_sec ) : ?>
    <?php
    if ( empty( $bhp_sec['photos'] ) ) {
      continue;
    }
    $bhp_sec_id = 'gallery-section-' . sanitize_html_class( (string) $bhp_key );
    ?>
    <section class="section section--muted author-visits-gallery-section readaloud-funnel__gallery" aria-labelledby="<?php echo esc_attr( $bhp_sec_id ); ?>">
      <div class="container container--content">
        <header class="component-heading">
          <h2 id="<?php echo esc_attr( $bhp_sec_id ); ?>" class="text-section-title"><?php echo esc_html( $bhp_sec['title'] ); ?></h2>
        </header>

        <ul class="author-visits-gallery">
          <?php foreach ( $bhp_sec['photos'] as $bhp_photo ) : ?>
            <?php
            $bhp_photo_url = function_exists( 'bhp_author_visits_photo_url' ) ? bhp_author_visits_photo_url( $bhp_photo['file'] ) : '';
            if ( '' === $bhp_photo_url || '' === $bhp_photo['alt'] ) {
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
                    <?php echo esc_html( trim( $bhp_photo['school'] . ( '' !== $bhp_photo['date_display'] ? ', ' . $bhp_photo['date_display'] : '' ) ) ); ?>
                  </figcaption>
                <?php endif; ?>
              </figure>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 4 · TEACHERS AND LIBRARIANS, plus the SECOND booking CTA.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ The second CTA is the same route and the same label as the hero one. Two
 *    different labels for one action reads as two different offers, and a
 *    second `mailto:` subject would split his inbox for no reason.
 */
?>
<section class="section readaloud-funnel__educators" aria-labelledby="readaloud-funnel-educators-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="readaloud-funnel-educators-title" class="text-section-title"><?php esc_html_e( 'For teachers and librarians', 'brave-hearts' ); ?></h2>
    </header>

    <?php bhp_readaloud_funnel_render_slot( 'educators-1' ); ?>

    <p class="readaloud-funnel__cta">
      <a class="btn btn-primary readaloud-funnel__btn"
         href="<?php echo esc_url( $bhp_cta['href'] ); ?>"
         data-readaloud-cta="educators">
        <?php echo esc_html( $bhp_cta['label'] ); ?>
      </a>
    </p>

    <p class="readaloud-funnel__address">
      <?php echo esc_html( $bhp_cta['email'] ); ?>
    </p>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE EDUCATOR EMAIL CAPTURE — THE TEACHER FUNNEL, AND ONLY THE TEACHER
 *     FUNNEL.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ FUNNEL ISOLATION IS THE RULE THIS SECTION EXISTS TO RESPECT, NOT TO TEST.
 *     `.claude/rules/funnels.md` and `docs/ENGINEERING/FUNNEL_CONSTITUTION.md`
 *     keep the parent and teacher funnels apart. This page feeds the TEACHER
 *     side, so it passes the educator landing page's EXACT pair —
 *     `lead_magnet` `teacher_adventure_toolkit` and `audience_type`
 *     `educators` — through the SAME `lead-magnet-cta` → `signup-form.php` →
 *     `bhp_mailchimp_signup` pipe, never a fork of it.
 *
 * ⭐ WHAT THAT PAIR RESOLVES TO, READ OUT OF `functions.php` RATHER THAN
 *    ASSUMED: the tags `Adventure Learning Toolkit`, `Audience: Educator`,
 *    `Source: Educator Landing Page`. **Identical to the educator landing
 *    page's, by construction, because it is the same key.**
 *
 * ⚠ ONE HONEST CONSEQUENCE, RECORDED RATHER THAN QUIETLY ENGINEERED AROUND:
 *   the third tag will read *"Source: Educator Landing Page"* for a signup that
 *   actually happened here. The brief said to match the educator tagging
 *   EXACTLY, so it is matched exactly and the inaccuracy is reported, not
 *   patched. **Per-page attribution is NOT lost:** `source_page` below carries
 *   this page's own permalink into the `SOURCE` merge field, which is a
 *   different field from the tags. A dedicated source tag is a one-branch
 *   addition to `bhp_mailchimp_signup_tags` and is Andrew's call, because it
 *   changes Mailchimp segmentation.
 *
 * ⛔ NOTHING PARENT-FUNNEL IS TOUCHED, REFERENCED OR RENDERED ON THIS PAGE. No
 *    `reluctant_reader_adventure_kit`, no `bhp_parent_popup` storage key, no
 *    `parent_popup` event prefix. A test asserts their absence.
 */
?>
<section id="free" class="section section--muted readaloud-funnel__capture" aria-labelledby="readaloud-funnel-capture-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="readaloud-funnel-capture-title" class="text-section-title"><?php esc_html_e( 'Free classroom resources by email', 'brave-hearts' ); ?></h2>
    </header>

    <?php if ( ! empty( $bhp_toolkit['ready'] ) ) : ?>
      <?php
      get_template_part(
        'template-parts/acquisition/lead-magnet-cta',
        null,
        array(
          'id'            => 'readaloud-funnel-educator-signup',
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
 * 6 · THE PRICING SLOT — STRUCTURAL, GATED OFF, AND EMPTY OF ANY FIGURE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THERE IS NO PRICE ON THIS PAGE AND NO PRICE EXISTS TO PUT ON IT. Item 481
 *     says read-alouds are currently free. This section is a place for a future
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
  id="readaloud-funnel-pricing"
  class="readaloud-funnel__pricing"
  data-readaloud-pricing="<?php echo $bhp_show_pricing ? 'on' : 'off'; ?>"
  aria-labelledby="readaloud-funnel-pricing-title"
  <?php echo $bhp_show_pricing ? '' : 'hidden'; ?>
>
  <div class="container container--content">
    <h2 id="readaloud-funnel-pricing-title" class="text-section-title"><?php esc_html_e( 'Booking details', 'brave-hearts' ); ?></h2>
    <?php if ( $bhp_show_pricing ) : ?>
      <div class="bhp-copy-placeholder" data-copy-slot="pricing-1" role="note">
        <p class="bhp-copy-placeholder__flag"><?php echo esc_html( '[PENDING READ-BACK — do not publish]' ); ?></p>
        <p class="bhp-copy-placeholder__label"><?php esc_html_e( 'BOOKING DETAILS — AWAITING A FOUNDER RULING', 'brave-hearts' ); ?></p>
        <p class="bhp-copy-placeholder__spec"><?php esc_html_e( 'No terms exist for this slot. Read-alouds are free today.', 'brave-hearts' ); ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

</div><?php /* .readaloud-funnel */ ?>

<?php get_footer(); ?>
