<?php
/**
 * Template Name: Positivity News
 * Description: The own-site signup page for the monthly Positivity News email.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POSITIVITY NEWS — 1.19.333 (2026-08-30, `CYCLE170-LD-BUNDLE`).
 * STAGING ONLY. Slug `positivity-news`. Carrier items 520 / 521.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ NOT ONE WORD OF THE VISIBLE COPY IS WRITTEN BY THIS FILE. Every string a
 *     visitor reads comes from `bhp_positivity_news_copy()`, which is carrier
 *     item 489 verbatim via the copy deck, with a second independent witness in
 *     the Mailchimp read-back. Read that function's docblock before editing any
 *     of it. The only strings this template supplies are the image `alt` and one
 *     consent line, both marked below.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THERE IS NO LEAD MAGNET ON THIS PAGE AND THERE MUST NEVER BE ONE.
 * ---------------------------------------------------------------------------
 * The offer is the newsletter. `lead_magnet` is passed as the EMPTY STRING to
 * `signup-form.php` — deliberately, and it is the single most load-bearing
 * argument in this file. A magnet key here would promise a download the copy
 * never mentions and would route the subscriber into a funnel they did not ask
 * for. See `inc/positivity-news.php` for the full reasoning and for the tagging
 * consequence.
 *
 * ---------------------------------------------------------------------------
 * ⭐ IT REUSES THE SHIPPED PIPE. NOTHING IS FORKED.
 * ---------------------------------------------------------------------------
 * `template-parts/acquisition/signup-form.php` → `bhp_mailchimp_signup` is the
 * same path every other capture on this site takes: same nonce, same honeypot,
 * same attribution field, same feedback mechanism, same transport. This page
 * supplies a different CONTEXT and gets different TAGS from a filter, which is
 * exactly what `.claude/rules/funnels.md` prescribes: *"extend the config
 * schema, don't fork the engine."*
 *
 * ---------------------------------------------------------------------------
 * ⭐ THE CHROME IS THE SITE'S OWN, NOT A BARE LANDING PAGE.
 * ---------------------------------------------------------------------------
 * `get_header()` / `get_footer()` are used, matching every other funnel page in
 * this theme (`page-reluctant-reader-adventure-kit.php`,
 * `page-audience-educators.php`). ⛔ THAT IS NOT A STYLE CHOICE: the header and
 * footer carry the consent banner, the analytics loader and the legal links, and
 * a signup form served without a consent surface is a compliance problem, not a
 * cleaner design.
 *
 * ⭐ THE NAVY BAND IS THE NEWSLETTER'S OWN MASTHEAD, sitting inside that chrome.
 *    It is the same lockup, ground and palette as the live email (campaign
 *    8118924), so somebody who clicked from the email recognises where they
 *    landed. The palette provenance is recorded in `style.css`'s section E.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

$bhp_pn_copy    = bhp_positivity_news_copy();
$bhp_pn_form_id = 'bhp-positivity-signup';

/*
 * ⭐ THE SUCCESS STATE IS READ THE SAME WAY `bhp_get_signup_feedback()` READS
 *    IT — the two query parameters the pipe redirects back with — so this page
 *    cannot disagree with the pipe about whether a signup happened.
 *
 * ⛔ BOTH PARAMETERS ARE CHECKED, NOT JUST `bhp_signup`. Without the form-id
 *    match, ANY page on this site reached with `?bhp_signup=success` after some
 *    other form's redirect would render a thank-you for a subscription that did
 *    not happen here.
 *
 * ⛔ NOTHING IS TRUSTED FROM THESE VALUES BEYOND "SHOW THE PANEL". They select
 *    between two blocks of static approved copy. No email, no name and no tag
 *    is read from the URL, and nothing is written anywhere.
 */
$bhp_pn_status  = isset( $_GET['bhp_signup'] ) ? sanitize_key( wp_unslash( $_GET['bhp_signup'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only branch; nothing is written and no value is trusted beyond selecting a block of static copy.
$bhp_pn_form    = isset( $_GET['bhp_form'] ) ? sanitize_html_class( wp_unslash( $_GET['bhp_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- as above.
$bhp_pn_success = ( 'success' === $bhp_pn_status && $bhp_pn_form === $bhp_pn_form_id );
?>

<div class="bhp-positivity">

  <?php
  /*
   * ⭐ THE NAVY LOGO BAND.
   *
   * ⛔ THE REVERSED LOCKUP, because the ground is navy. `header.php` and
   *    `footer.php` both already make this exact choice for this exact reason
   *    (see the comment block in `footer.php`): the plated navy lockup cannot
   *    read on a dark ground, the transparent reversed mark can.
   *
   * ⛔ `width`/`height` ARE THE REAL FILE DIMENSIONS and are load-bearing: they
   *    reserve the box so a late-arriving image cannot push the headline and the
   *    form down the screen after paint.
   *
   * ⛔ THE ALT TEXT IS THE SITE NAME AND NOTHING ELSE — one of the two strings
   *    this template supplies. It is the same value `footer.php` uses for the
   *    same asset, so a screen reader hears one consistent name for the mark.
   */
  ?>
  <div class="bhp-positivity__band">
    <img class="bhp-positivity__logo"
         src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/brand/brave-hearts-horizontal-reversed-rose.png' ); ?>"
         alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
         width="338" height="100"
         loading="eager" decoding="async">
  </div>

  <div class="bhp-positivity__card">

    <h1 class="bhp-positivity__title"><?php echo esc_html( $bhp_pn_copy['headline'] ); ?></h1>
    <p class="bhp-positivity__subhead"><?php echo esc_html( $bhp_pn_copy['subhead'] ); ?></p>
    <hr class="bhp-positivity__rule">

    <?php if ( $bhp_pn_success ) : ?>

      <?php
      /*
       * ═══════════════════════════════════════════════════════════════════
       * ⛔⛔ THE THANK-YOU STATE. THIS IS WHERE "NO LEAD MAGNET" IS VISIBLE.
       * ═══════════════════════════════════════════════════════════════════
       *
       * It says what they will get and it stops. ⛔ NO DOWNLOAD LINK, NO
       * "check your inbox for your PDF", NO second offer, NO cross-sell and
       * NO other funnel. Every other thank-you surface on this site delivers
       * a resource; this one delivers a promise about an email, because that
       * is the entire offer the page made.
       *
       * ⛔ THE FORM IS NOT RENDERED IN THIS STATE. Leaving it below the
       *    confirmation invites a second submission and a duplicate contact.
       */
      ?>
      <div class="bhp-positivity__thanks" role="status" aria-live="polite">
        <p class="bhp-positivity__thanks-title"><?php esc_html_e( 'You are subscribed.', 'brave-hearts' ); ?></p>
        <p class="bhp-positivity__thanks-text"><?php echo esc_html( $bhp_pn_copy['thanks'] ); ?></p>
      </div>

    <?php else : ?>

      <?php
      /*
       * ═══════════════════════════════════════════════════════════════════
       * ⭐⭐ 1.19.337 (2026-08-30, `CYCLE170-LD-MICRO`) — THE FORM IS NOW
       *     ABOVE THE BODY COPY. CARRIER ITEM 546.
       * ═══════════════════════════════════════════════════════════════════
       *
       * ⭐⭐ THE FOUNDER'S WORDS, verbatim, 2026-08-30, carrier item 546:
       *      "also the email address area should be above the fold"
       *    ⛔ RELAYED through `chief-of-staff`; read first-hand at the carrier
       *      before this edit. NOT witnessed by this desk.
       *
       * ⛔⛔ NOT ONE CHARACTER OF THE APPROVED COPY CHANGED. The two body
       *     paragraphs are the same two strings from
       *     `bhp_positivity_news_copy()`, in the same order, rendered by the
       *     same loop. ⭐ ONLY THE ORDER OF TWO BLOCKS MOVED, and the block
       *     that moved is a FORM, not prose.
       *
       * ---------------------------------------------------------------------
       * ⭐ IT IS THE HOUSE PRECEDENT, NOT A NEW IDEA
       * ---------------------------------------------------------------------
       * `page-school-read-alouds.php`'s hero already states the same rule for
       * the same reason: *"THE BUTTON SITS ABOVE THE SUPPORTING LINE, not
       * below it. One fewer line of text between the visitor and the only
       * action on the screen."* This page has one action; it now sits directly
       * under the headline, the subhead and the rule, which together already
       * say what is being offered.
       *
       * ---------------------------------------------------------------------
       * ⛔⛔ WHY REORDERING THE DOM RATHER THAN CSS `order:`
       * ---------------------------------------------------------------------
       * A CSS `order` would put the visual sequence and the KEYBOARD/screen
       * reader sequence out of step. `RELEASES/HOMEPAGE_HERO_MOBILE_ORDER_1_19_120.md`
       * records this theme making exactly that call once already: *"no `order`,
       * absolute positioning or transforms are used, so keyboard order matches
       * the visible order."* Moving the markup keeps the two identical.
       *
       * ⚠️ THE MEASUREMENT THAT MOTIVATED IT, so a later reader does not
       *   "restore" the old order as a tidy-up. Staging 1.19.336, real browser,
       *   `innerWidth` asserted 375 / `innerHeight` 812, 2026-08-30: the
       *   Subscribe button's bottom edge sat at **758px**. That is above 812 by
       *   54px on a viewport with NO browser chrome — and a real iPhone's
       *   Safari toolbars take roughly 100-150px, which puts it under the fold
       *   on the device the founder was holding. The compression rules in
       *   style.css and this reorder are the two halves of the fix; the
       *   re-measured figures are in the release record.
       */
      ?>
      <div class="bhp-positivity__form">
        <?php
        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.340 (2026-08-31, `CYCLE170-LD-NAMEFIELD`) — AN OPTIONAL
         *     FIRST-NAME FIELD. FOUNDER ORDER, relayed through
         *     `chief-of-staff`; NOT witnessed by this desk.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⛔⛔ SUPERSEDED COMMENT, preserved verbatim so the movement is
         *     visible and a later reader does not "restore" email-only as a
         *     tidy-up:
         *
         *       "⛔ ONE FIELD. `require_name` AND `show_name` ARE BOTH LEFT AT
         *        THEIR DEFAULT FALSE, so no first-name input renders. The copy
         *        deck specifies email-only, and Gimli's Mailchimp read-back
         *        confirmed the hosted page carries a single `EMAIL` field.
         *        Both surfaces ask for the same one thing."
         *
         * ⚠️ THAT LAST SENTENCE IS NOW FALSE OF THE TWO SURFACES, and it is
         *   recorded rather than quietly dropped: this page now asks for a
         *   name, the Mailchimp-hosted page 42351 still does not. ⛔ NOTHING IN
         *   THIS BUILD TOUCHES 42351 — it is Gimli's surface and Andrew's
         *   publish click. The divergence is a finding for the handover, not a
         *   defect this file may fix.
         *
         * ---------------------------------------------------------------------
         * ⛔⛔ `require_name` STAYS FALSE. OPTIONAL MEANS OPTIONAL.
         * ---------------------------------------------------------------------
         * `show_name` renders the input; `require_name` is what would add
         * `required` / `aria-required` to it AND emit the `bhp_require_name`
         * hidden field that makes the pipe REJECT a blank name with
         * `missing_name`. Passing it here would turn a founder's "optional"
         * into a wall between a subscriber and a newsletter. An empty name
         * still subscribes, which is asserted end to end against the staging
         * stub rather than reasoned about.
         *
         * ⭐ THE POSTED NAME TAKES THE SHIPPED PATH, BYTE FOR BYTE. The input
         *    is `name="first_name"` (the template's `name_name` default);
         *    `bhp_handle_mailchimp_signup()` reads `$post['first_name']`
         *    through the SAME `sanitize_text_field()` it has always used
         *    (`bhp_name_field` is absent, and the handler's default for it is
         *    already `'first_name'`), hands it to `bhp_process_signup()` as
         *    `name`, and that function sets `FNAME` to `substr($name, 0, 100)`
         *    only when the string is non-empty. ⛔ NOT ONE LINE OF
         *    `inc/mailchimp.php` OR OF `signup-form.php` IS EDITED BY THIS
         *    BUILD. This is the same route `/read-aloud/`'s capture already
         *    takes (`page-read-aloud.php`, `'show_name' => true`).
         *
         * ⚠️ IT DOES CHANGE THE FORM'S LAYOUT CLASS, and that is intended, not
         *   incidental: `$is_multi_field` becomes true, so the template emits
         *   `acquisition-form--stacked`. That class exists precisely because
         *   `.acquisition-form`'s one-field grid strands a second field beside
         *   the submit button (see the 1.19.191 note in `signup-form.php`).
         *   Measured at both viewports after the change, not assumed.
         *
         * ⛔ NO COPY DECK STRING IS TOUCHED. The label below is a FIELD LABEL,
         *    not body copy: it is the same class of string as the
         *    `email_label` and `privacy_text` already supplied here. Carrier
         *    item 489's five approved strings are byte-untouched.
         *
         * ⛔ `lead_magnet` IS `''`. Read the header block. This is the argument
         *    that makes the page honest.
         *
         * ⛔ `audience_type` IS THE DEFAULT `general_readers` AND IS NOT
         *    `parents_families` OR `educators`. A newsletter subscriber has
         *    declared no audience, and guessing one here would put them in a
         *    segment they never chose. The tag callback returns exactly two
         *    tags and no `Audience:` tag at all, which is the same statement
         *    made in the place Mailchimp actually reads.
         *
         * ⛔ NO `success_redirect_key`. There is no thank-you PAGE, by design:
         *    the success state is this page, above. Passing a key would send a
         *    newsletter subscriber to a lead-magnet delivery page.
         */
        get_template_part(
          'template-parts/acquisition/signup-form',
          null,
          array(
            'id'            => $bhp_pn_form_id,
            'context'       => bhp_positivity_news_context(),
            'audience_type' => 'general_readers',
            'lead_magnet'   => '',
            'source_page'   => bhp_positivity_news_url(),
            /* ⭐ 1.19.340 — the optional first-name field. `show_name` renders
               it; `require_name` is deliberately absent (its default is false)
               so a blank name still subscribes. */
            'show_name'     => true,
            'require_name'  => false,
            'name_label'    => __( 'First name (optional)', 'brave-hearts' ),
            'email_label'   => __( 'Email address', 'brave-hearts' ),
            'submit_label'  => $bhp_pn_copy['submit'],
            /*
             * ⭐ THE ONE OTHER STRING THIS TEMPLATE SUPPLIES. It is not
             *    marketing copy: it is the consent and expectation line, it
             *    promises nothing the approved body does not already promise
             *    ("Once a month", "Only positive things"), and it adds the
             *    unsubscribe statement that every other capture on this site
             *    carries. ⛔ NO RESOURCE, PDF OR DOWNLOAD IS NAMED IN IT.
             */
            'privacy_text'  => __( 'One email a month. Unsubscribe anytime.', 'brave-hearts' ),
          )
        );
        ?>
      </div>

      <div class="bhp-positivity__body">
        <?php foreach ( $bhp_pn_copy['body'] as $bhp_pn_para ) : ?>
          <p><?php echo esc_html( $bhp_pn_para ); ?></p>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <?php
    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.337 — THE GRADIENT PHOTOGRAPH. CARRIER ITEM 545.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⭐⭐ Founder, verbatim, 2026-08-30, item 545: *"Excellent if we can do a
     *    gradient picture below it of the read aloud with the kiddos that
     *    would make it pop"*. ⛔ RELAYED, read first-hand at the carrier.
     *
     * ⛔ THE IMAGE IS UNTOUCHED. The "gradient treatment" is entirely CSS — a
     *    `::after` overlay that fades the photograph into `--color-ivory`, the
     *    card's own ground, so it reads as part of the page rather than as a
     *    rectangle dropped on it. See `.bhp-positivity__photo` in style.css and
     *    `bhp_positivity_news_photo()` for the asset's provenance and for why
     *    its `alt` is carried in code.
     *
     * ⛔ IT RENDERS IN BOTH STATES, FORM AND THANK-YOU, deliberately: it is
     *    the page's warmth, not part of the ask, and a subscriber who has just
     *    said yes should not watch the photograph disappear as their reward.
     *    ⭐ It is OUTSIDE the `if ( $bhp_pn_success )` branch for that reason.
     *
     * ⛔ IT SITS BELOW THE FORM AND CANNOT PUSH IT DOWN. Item 546 rules that
     *    the email field is above the fold; a photograph placed above the form
     *    would break that ruling with the other one. DOM order is the whole
     *    mechanism — do not move this block up.
     *
     * ⛔ `loading="lazy"` BECAUSE IT IS BELOW THE FOLD BY CONSTRUCTION. The
     *    band logo above stays `eager`; this one must never compete with the
     *    form for bandwidth on a phone.
     *
     * ⛔ NO CAPTION, NO COUNT, NO REACTION, NO OUTCOME. The photograph is
     *    presented as a photograph. Nothing on this page claims what a visit
     *    did to any child, and this block adds no such claim (§3 never-invent).
     */
    $bhp_pn_photo = function_exists( 'bhp_positivity_news_photo' ) ? bhp_positivity_news_photo() : array( 'url' => '', 'alt' => '' );
    if ( ! empty( $bhp_pn_photo['url'] ) && '' !== (string) $bhp_pn_photo['alt'] ) :
    ?>
      <figure class="bhp-positivity__photo">
        <img class="bhp-positivity__photo-img"
             src="<?php echo esc_url( $bhp_pn_photo['url'] ); ?>"
             width="<?php echo esc_attr( (string) $bhp_pn_photo['w'] ); 