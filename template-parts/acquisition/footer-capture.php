<?php
/**
 * Sitewide footer capture block — free Reluctant Reader Adventure Kit,
 * with a live-aligned segment selector. Wave 1, 2026-08-04, theme 1.19.168.
 *
 * ⭐ THIS SHIPS ENABLED. It is not a popup: it is static server-rendered
 *    markup in the footer, it opens nothing, it interrupts nothing, and it
 *    writes no client-side storage of any kind. Andrew's 2026-07-19 popup
 *    retirement does not touch it.
 *
 * ── WHY THE SEGMENT SELECTOR DOES NOT BREACH FUNNEL ISOLATION ──────────
 * `CYCLE143-MKT-136` asked exactly this before the build, and the
 * answer is structural rather than careful:
 *
 *   1. ⛔ THIS BLOCK TOUCHES NO POPUP STORAGE NAMESPACE. It writes no
 *      localStorage and no sessionStorage. `bhp_parent_popup_*` and
 *      `bhp_mariana_popup_*` are neither read nor written here, so nothing
 *      a visitor does in this form can change either popup's suppression
 *      state, in either direction.
 *   2. ⛔ IT HAS ITS OWN ANALYTICS NAMESPACE. `context` is
 *      `footer_capture`, which is what the shared form emits as
 *      `data-bhp-form-placement`. It is not `parent_popup` and not
 *      `teacher_popup`, so no funnel's event prefix is reused.
 *   3. ⭐ ONE OFFER, MANY AUDIENCE TAGS. Every segment receives the SAME
 *      deliverable — the free kit that this block's own copy promises. The
 *      selector changes the AUDIENCE TAG only. That is what keeps the two
 *      funnels from crossing: there is no second lead magnet here to route
 *      into, so a teacher selecting "My class, library or homeschool" is
 *      tagged for teacher segmentation but is NOT injected into the
 *      teacher lead-magnet funnel, and does not leave the parent one.
 *   4. ⛔ THE BROWSER SENDS A SHORT KEY, NEVER A TAG. Resolution happens
 *      server-side in `bhp_get_capture_segment_routes()`.
 *
 * ── THE GIFT SEGMENT ──────────────────────────────────────────────────
 * The Chief of Staff's ruling, relayed 2026-08-04: gift is CAPTURE AND TAG ONLY. No
 * gift journey and no gift kit is promised anywhere in this block, because
 * neither exists yet (`CYCLE143-MKT-131`). The gift selection therefore
 * routes to the same free kit as every other selection, and simply carries
 * an `Audience: Gift Buyer` tag so the lane can be built in Wave 2.
 *
 * COPY: the approved set in DRAFT-2026-08-04-WAVE1-CAPTURE-COPY.md §2.1
 * (heading, O1 one-liner, recommended button) and §2.2 SET A labels, which
 * are the four LIVE segments verbatim from `/find-your-adventure/`. No
 * number, count, urgency, scarcity, rating or review claim appears here.
 */
defined('ABSPATH') || exit;

$panel_id   = 'footer-capture';
$heading_id = $panel_id . '-title';
?>
<section id="<?php echo esc_attr($panel_id); ?>" class="footer-capture" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="footer-capture__inner">
    <div class="footer-capture__content">
      <?php
      /*
       * ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — THE OFFER
       *     NAME. Founder's pick, carrier item 290.
       *
       * ⛔ THE HEADING WAS "Start with one free chapter." AND IT WAS APPROVED
       *    COPY — DRAFT-2026-08-04-WAVE1-CAPTURE-COPY.md §2.1, cited in
       *    this file's own header and referenced by `tests/test-wave1-capture.php`
       *    as "the approved footer heading". It is superseded by the founder's
       *    own later pick, which is the only thing that can supersede it, and it
       *    is superseded OPENLY: the guard test is updated in the same release
       *    with the authority named, never quietly relaxed.
       *
       * ⚠ THIS BLOCK IS SITEWIDE AND SEGMENTED, WHICH MAKES IT THE ONE CAREFUL
       *   CASE IN THE SWEEP. Its selector lets a visitor say they are buying for
       *   a class, library or homeschool. ⛔ THE SELECTOR AND ITS FOUR LABELS ARE
       *   BYTE-UNCHANGED, and so is the `context` (`footer_capture`) and every
       *   route in `bhp_get_capture_segment_routes()` — a new context string
       *   would mint a NEW tag in the live Mailchimp audience and split this
       *   surface's segment in two, which is Andrew's decision and not an
       *   engineering one. ⭐ AND THE ISOLATION ARGUMENT IN THIS FILE'S HEADER
       *   STILL HOLDS UNCHANGED: point 3 is "ONE OFFER, MANY AUDIENCE TAGS" —
       *   every segment already received the SAME deliverable, so renaming that
       *   one deliverable in copy changes no routing, injects nobody into the
       *   teacher lead-magnet funnel, and crosses no funnel boundary.
       *
       * ⛔ NO OUTCOME CLAIM, no number, count, urgency, scarcity, rating or
       *    review claim. VOICE §9.1: I/me/my, no em dash, ages 6 to 9.
       */
      ?>
      <h2 id="<?php echo esc_attr($heading_id); ?>" class="footer-capture__title"><?php esc_html_e('FREE Chapter for Reluctant Readers', 'brave-hearts'); ?></h2>
      <p class="footer-capture__text"><?php esc_html_e("I'll send you the chapter now, just add your email. It arrives inside my free Reluctant Reader Adventure Kit, along with a printable activity and tips for reading it with a 6 to 9 year old.", 'brave-hearts'); ?></p>
    </div>
    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $panel_id . '-form',
        'context'              => 'footer_capture',
        // The block's own default audience is the offer's audience. A
        // visitor who changes the selector overrides it server-side.
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'success_redirect_key' => 'adventure_kit_thank_you',
        'segment_options'      => bhp_get_capture_segment_labels(),
        'segment_label'        => __('I\'m looking for books for...', 'brave-hearts'),
        // ⭐ 1.19.297 — was "Send me the free kit". ⛔ FREE comes OFF the button
        //    (it belongs in the headline, where it is the offer rather than a
        //    competitor to the action) and the deliverable named on the control
        //    now matches the deliverable named in the headline.
        'submit_label'         => __('Send me the chapter', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'acquisition-form--footer-capture',
        'aria_labelledby'      => $heading_id,
    ]); ?>
  </div>
</section>
