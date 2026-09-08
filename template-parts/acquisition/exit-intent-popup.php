<?php
/**
 * Exit-intent capture modal — PARENT FUNNEL, free Reluctant Reader
 * Adventure Kit. Wave 1, 2026-08-04, theme 1.19.168.
 *
 * ⛔ SHIPPED DISABLED. `bhp_should_show_exit_intent_popup()` defaults to
 *    FALSE. See the block above that function in functions.php: Andrew
 *    Signore retired every lead-magnet popup sitewide on 2026-07-19 ("the
 *    quiz modal becomes the ONLY popup on the site"), and this file does
 *    not reverse an owner ruling. It is built, wired, tested and one
 *    filter away from live.
 *
 * ⭐⭐ THE FIVE LINES DIRECTLY ABOVE ARE STALE AND HAVE BEEN SINCE 2026-08-04.
 *     ⛔ DO NOT ACT ON THEM. They are preserved struck rather than deleted so a
 *     reader who meets the same words quoted elsewhere knows they were
 *     corrected, and when.
 *
 *     **THIS POPUP IS LIVE.** Corrected in 1.19.389 (2026-09-06,
 *     `CYCLE179-LD-BUILD-389`) on `marketing-growth`'s finding
 *     (`WORKING-DRAFTS\marketing-growth\CYCLE179-MKT-BUNDLE-TABLE-EXIT.md`
 *     §2.1, §3 row 4), re-verified against the code this build:
 *     `bhp_should_show_exit_intent_popup()` in `functions.php` returns
 *     `apply_filters('bhp_show_exit_intent_popup', true)` and has defaulted
 *     **true** since 2026-08-04 on Andrew's "Turn it on."
 *
 *     The only suppressions are `/teachers/`, the read-aloud landing, the
 *     school read-alouds and the positivity news pages.
 *
 *     ⭐ CODE BEATS COMMENT. A header that says a live customer-facing surface
 *        is disabled is worse than no header: it invites the next author to
 *        edit copy nobody is reading, which is exactly what a parent then
 *        reads.
 *
 * FUNNEL RULES, all of them from `.claude/rules/funnels.md`, applied here
 * rather than reinvented:
 *   - Storage prefix `bhp_parent_popup`, event prefix `parent_popup`,
 *     lead magnet key `reluctant_reader_adventure_kit`, thank-you path
 *     `adventure-kit-thank-you`. IDENTICAL to the timed parent popup,
 *     deliberately: this is the SAME funnel and the SAME offer, so a
 *     visitor who dismissed or joined through one must not be asked again
 *     through the other. Minting a new prefix would have created a second
 *     parent funnel, which is exactly what the isolation rule forbids.
 *   - ⛔ NOTHING here reads or writes `bhp_mariana_popup_*`. The teacher
 *     funnel is untouched in both directions.
 *   - ⛔ Never on `/teachers/` — enforced server-side in
 *     `bhp_should_show_exit_intent_popup()`, not by CSS or JS.
 *
 * TIMING: `minDelay` 20000 on BOTH devices. Andrew Signore, 2026-08-04:
 * "20 seconds please". The quiz modal's dwell floor and this one are the
 * same number on purpose — a visitor cannot be interrupted by anything
 * automatic inside the first 20 seconds of a page.
 *
 * STACKING: `sessionGuard` blocks this modal outright if the quiz modal or
 * any other engine popup has already been shown in this session.
 *
 * COPY: every string below is from the approved set in
 * `WORKING-DRAFTS\marketing-growth\DRAFT-2026-08-04-WAVE1-CAPTURE-COPY.md`
 * §1.2 (H1/S1 recommended). `submit_label` and `privacy_text` are reused
 * VERBATIM from the live kit page so the modal and the landing page say
 * the same thing in the same words. No number, no count, no urgency, no
 * scarcity, no review claim appears anywhere in this file.
 */
defined('ABSPATH') || exit;

$source_page = get_permalink(get_queried_object_id()) ?: home_url('/');
$form_id = 'exit-intent-signup-form';

$submitted_form = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');

$popup_config = wp_json_encode([
    'eventPrefix'   => 'parent_popup',
    'source'        => 'parent_popup_exit',
    'storagePrefix' => 'bhp_parent_popup',
    'thankYouPath'  => 'adventure-kit-thank-you',
    // One capture modal per session, whichever got there first.
    'sessionGuard'  => ['bhp_quiz_auto_shown', 'bhp_popup_shown_session'],
    'trigger'       => [
        'mode'    => 'exit',
        // 20s dwell floor on both devices. Desktop uses leave-intent only.
        // `interactionCooldownMs` (1.19.173): nothing may open within this
        // many ms of the visitor activating a link, button or form control.
        // Andrew Signore, 2026-08-05: the modal opened while he was clicking
        // "Get the Complete Collection". A CTA click is the opposite of exit
        // intent, and the engine now refuses to confuse the two.
        'desktop' => ['minDelay' => 20000, 'interactionCooldownMs' => 1500],
        // Mobile has no pointer to leave the viewport, so a fast upward
        // flick AFTER 45% depth stands in for it. Conservative on purpose,
        // and MORE conservative since 1.19.173: the travel threshold is
        // raised 300 -> 400 px, and travel now only counts while a real
        // finger gesture is in progress (`touchWindowMs`). A page that
        // scrolls ITSELF — an anchor jump from a CTA tap, a scroll-into-
        // view, a drawer locking the body — accumulates nothing.
        //
        // ⚠ THE DWELL-FLOOR LINE MUST STAY ON ONE LINE, WITH SINGLE SPACES
        //   AROUND ITS ARROW, IN BOTH DEVICE ARRAYS — AND THAT EXACT STRING
        //   MUST APPEAR EXACTLY TWICE IN THIS FILE, ONCE PER DEVICE.
        //   `tests/test-wave1-capture.php` counts its occurrences to guard
        //   Andrew's "20 seconds please" against a silent edit. Aligning the
        //   arrows breaks that guard while changing no behaviour; so does
        //   quoting the line in a comment. Both were done and both were
        //   caught by running that suite, 2026-08-05.
        'mobile'  => [
            'minDelay' => 20000,
            'scrollPct' => 45,
            'upThresholdPx' => 400,
            'upWindowMs' => 600,
            'interactionCooldownMs' => 1500,
            'touchWindowMs' => 700,
        ],
    ],
]);
?>
<div
  id="exit-intent-popup"
  class="mariana-popup mariana-popup--exit"
  data-bhp-popup
  data-page-type="<?php echo esc_attr(bhp_get_page_type_for_analytics()); ?>"
  data-force-open="<?php echo $force_open ? '1' : '0'; ?>"
  data-popup-config="<?php echo esc_attr($popup_config); ?>"
  hidden
>
  <div class="mariana-popup__overlay" data-bhp-popup-overlay></div>
  <div
    class="mariana-popup__dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="exit-intent-popup-title"
    aria-describedby="exit-intent-popup-desc"
    tabindex="-1"
  >
    <button type="button" class="mariana-popup__close" data-bhp-popup-close aria-label="<?php esc_attr_e('Close and keep browsing', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <?php
    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — LOCKED
     *     PROSE CHANGED, DELIBERATELY, ON THE OWNER'S OWN LATER WORD.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ READ THIS BEFORE ASSUMING A DRIFT. The prior headline, "Before you go,
     *    take the free kit.", was guarded by `tests/test-exit-intent-trigger.php`
     *    with the assertion label *"approved headline is unchanged (locked prose
     *    is never silently rewritten)"*. ⭐ THAT GUARD DID EXACTLY ITS JOB: it
     *    forced this change to be made in the open, with the authority named,
     *    in the same release as the assertion it breaks. It was NOT relaxed and
     *    NOT deleted — it now asserts the NEW approved string, so the property
     *    it protects (this headline never moves without a recorded ruling) is
     *    still protected.
     *
     * ⭐ THE AUTHORITY IS THE OWNER, carrier item 290, 2026-08-27, verbatim:
     *    *"FREE Chapter for Reluctant Readers - I'll send you the chapter now,
     *    just add your email - Something like that"*, together with his
     *    agreement that *"we need to be consistent on the email capture across
     *    the entire website"*. This surface is a PARENT capture surface and was
     *    one of the twelve the teardown found describing the offer differently.
     *    ⚠ RELAYED through the Chief of Staff, who witnessed it; NOT witnessed
     *      by this desk. ⛔ STAGING ONLY. FLAGGED at the top of this build's
     *      report so Andrew meets it at the deploy gate rather than afterwards.
     *      If he says no, the reversal is two strings here and one assertion in
     *      the suite.
     *
     * ⛔ WHAT IS **NOT** CHANGED ON THIS SURFACE, so the boundary is legible:
     *    the `context` (`parent_popup_exit`) — a live Mailchimp tag join key —
     *    the storage prefix, the trigger config, the 20s floor, the optional
     *    name field, the trust caption ("Free printable PDF. No purchase
     *    required.", separately guarded and still true of the artefact), and the
     *    "No thanks, not tonight" dismiss control.
     *
     * ⭐ THE SUPPORT SENTENCE BRIDGES chapter -> Kit (item 290 condition (b)).
     *    Its contents half is artefact-checked, not paraphrased: the 296 lane
     *    read all seven pages of the live `Reluctant-Reader-Adventure-Kit-1.pdf`
     *    from the production document root.
     * ⛔ NO OUTCOME CLAIM. VOICE §9.1: I/me/my, no em dash, ages 6 to 9.
     */
    ?>
    <?php
    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐⭐ 1.19.389 (2026-09-06, `CYCLE179-LD-BUILD-389`) — THE COPY SET IS
     *      `marketing-growth`'s **VARIANT B**, from
     *      `WORKING-DRAFTS\marketing-growth\CYCLE179-MKT-BUNDLE-TABLE-EXIT.md`
     *      §2.2, carried in the `chief-of-staff` desk's brief.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ THE SUPERSEDED SET, PRESERVED VERBATIM SO THE MOVEMENT IS VISIBLE AND
     *    IS NOT RE-DERIVED:
     *      eyebrow  "Before you go"
     *      headline "FREE Chapter for Reluctant Readers"
     *      support  "I'll send you the chapter now, just add your email. It
     *                arrives inside my free Reluctant Reader Adventure Kit,
     *                along with a printable activity and tips for reading it
     *                with a 6 to 9 year old."
     *
     * ⭐ THE EYEBROW MOVED RATHER THAN HIS SENTENCE BEING TRIMMED. Andrew's
     *    line opens "Before you go", which was already the eyebrow; keeping
     *    both would print those words twice on one card. The `marketing-growth` desk's judgement,
     *    kept: his words are the headline and the label is what gives way.
     *
     * ⭐⭐ VARIANT B NAMES **CHAPTER 10**, AND ITS PRECONDITION IS MET. `marketing-growth`
     *     wrote both variants because a working draft recorded the Chapter 10
     *     kit as unshipped. ⛔ THAT DRAFT IS THE STALE PARTY. Verified in the
     *     live system by `lead-developer` on 2026-09-06 over SSH against the
     *     PRODUCTION document root (Standing Rules §9.2 — who, when, with
     *     what):
     *       · `wp option get bhp_lead_magnet_pdfs` →
     *         `adventure_kit_parent` = `.../uploads/2026/07/
     *         Reluctant-Reader-Adventure-Kit-1.pdf`
     *       · that file: 8,944,368 bytes, mtime **2026-09-03 19:26**, md5
     *         **e227eea53ec762df4abdb6a09615a730**, `/Count 11`
     *       · same md5 as the Drive kit of record "Reluctant Reader Adventure
     *         Kit v2.2 (Chapter 10, live 2026-09-03).pdf", page 3 of which
     *         reads "FROM THE MARIANA TRENCH, CHAPTER 10: THE DIVE"
     *     ⭐ the `marketing-growth` desk's own precondition 2 — "the live kit page's Chapter 7
     *        mentions are updated in the same release" — is satisfied by this
     *        same release, in `page-reluctant-reader-adventure-kit.php`.
     *
     * ⭐ "about ten minutes" IS SOURCED, NOT ESTIMATED. The kit's own page 1
     *    reads "A real chapter from the book. About 10 minutes." It is written
     *    in words here, not digits, and it is a description of the artefact
     *    rather than a claim about what reading it will do to a child.
     *    ⚠ FLAGGED IN THE BUILD REPORT rather than settled here: the kit
     *      LANDING PAGE retired duration claims on 2026-08-03 (rule A6), and
     *      whether that retirement was meant to reach this popup is Andrew's
     *      call, not this desk's. It ships as `marketing-growth` wrote it and as the brief
     *      instructed, with the tension named.
     *
     * ⛔ WHAT IS **NOT** CHANGED, checked rather than assumed:
     *    the `context` `parent_popup_exit` (a live Mailchimp tag join key) ·
     *    the storage prefix `bhp_parent_popup` · the event prefix
     *    `parent_popup` · the 20 second dwell floor and its two literal arrow
     *    lines · the submit label · the privacy line · the trust caption · the
     *    dismiss control · the close control · the name label.
     *
     * ⛔ RAILS ON THE NEW STRINGS: no em dash · no "we"/"us"/"our" · first
     *    person is I/my · no rating, review, award, urgency or scarcity · no
     *    outcome claim · no subscriber count · nothing on the never-invent
     *    list · no tracking claim.
     */
    ?>
    <p class="component-heading__eyebrow"><?php esc_html_e('A free chapter tonight', 'brave-hearts'); ?></p>
    <h2 id="exit-intent-popup-title"><?php esc_html_e('Before you go, test Chapter 10 with your child tonight for free', 'brave-hearts'); ?></h2>
    <p id="exit-intent-popup-desc" class="mariana-popup__text">
      <?php esc_html_e('It is a real chapter from The Mariana Trench, about ten minutes of reading, and it arrives with a printable activity and three ways to make it feel like an adventure.', 'brave-hearts'); ?>
    </p>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        // Its own context, so the Mailchimp source tag distinguishes an
        // exit-intent capture from the timed popup and from the landing
        // page without changing either of their tag sets.
        'context'              => 'parent_popup_exit',
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => false,
        'name_label'           => __('First name (optional)', 'brave-hearts'),
        // ⭐ 1.19.297 — was "Send me the free chapter & activity". ⚠ THE OLD
        //    STRING IS ALSO WHY THIS FILE'S HEADER DISCUSSES `white-space:
        //    nowrap` on the submit button: it was long enough to overflow. The
        //    new string is shorter, so that constraint is relaxed rather than
        //    strained. ⛔ The CSS rule is NOT touched by this pass — a shorter
        //    label cannot break a rule written to survive a longer one.
        'submit_label'         => __('Send me the chapter', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        'aria_labelledby'      => 'exit-intent-popup-title',
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php esc_html_e('Free printable PDF. No purchase required.', 'brave-hearts'); ?></p>
    <button type="button" class="mariana-popup__dismiss" data-bhp-popup-dismiss><?php esc_html_e('No thanks, not tonight', 'brave-hearts'); ?></button>
  </div>
</div>
