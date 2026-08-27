<?php
/**
 * Teacher-guide lead popup, now restricted to the Teachers page only (see
 * bhp_should_show_teacher_popup()) rather than sitewide.
 *
 * Reuses the existing signup-form.php handler, Mailchimp integration,
 * tag/merge-field mapping, and thank-you redirect key — this file only
 * supplies markup and content. Display eligibility for the page itself is
 * decided server-side; the timing, scroll-depth trigger, and frequency
 * capping are decided client-side in mariana-popup.js (the shared, generic
 * popup engine), driven entirely by the data-popup-config JSON below.
 *
 * Storage keys intentionally keep the original bhp_mariana_popup_* prefix
 * (rather than a newer bhp_teacher_popup_* name) so any dismissal or
 * permanent-suppression state already saved in a returning visitor's
 * browser from before this page-restriction change remains valid — only
 * the analytics event names change to teacher_popup_*, since those carry
 * no suppression behavior of their own.
 */
defined('ABSPATH') || exit;

$source_page = get_permalink(get_queried_object_id()) ?: home_url('/');
$form_id = 'mariana-popup-signup-form';
$popup_config = wp_json_encode([
    'eventPrefix'   => 'teacher_popup',
    'source'        => 'teacher_popup',
    'storagePrefix' => 'bhp_mariana_popup',
    'thankYouPath'  => 'mariana-guide-thank-you',
    /*
     * ═══════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.300 (2026-08-27, `CYCLE167-LD-POPUP-TIME-ONLY`) — TIME ONLY,
     *     AT FIFTEEN SECONDS. THE SCROLL REQUIREMENT IS REMOVED.
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⭐ Andrew Signore, 2026-08-27, carrier item 306, VERBATIM:
     *      "We also dont have the awareness or market share - I think we keep
     *       our pop ups time only."
     *    ⚠ RELAYED through the Chief of Staff, who states he read it
     *      first-hand; NOT witnessed by this desk.
     *
     * ⭐ "OUR POP UPS" IS PLURAL, AND THAT IS WHY THIS FILE MOVES TOO. This is
     *    the teacher funnel, not the parent funnel, and it KEEPS every one of
     *    its own keys — but the trigger PHILOSOPHY is a house-wide manner, and
     *    1.19.296 (below) set this surface's floor to the parent funnel's
     *    number for exactly that reason: one answer to "how long before this
     *    site interrupts anyone". That reason still holds; the answer moved.
     *
     * ⭐ MECHANICALLY: mode `gated` → mode `simple`, and both `scrollPct`
     *    thresholds are removed rather than lowered. The engine registers its
     *    scroll listener only when a threshold is present, so no scroll
     *    listener exists on `/teachers/` at all now.
     * ⭐ 15000 ms is UNCHANGED — only the key name differs, because `simple`
     *    mode reads `delay`.
     *
     * ⛔ FUNNEL ISOLATION UNTOUCHED. `teacher_popup`, `bhp_mariana_popup` and
     *    `mariana-guide-thank-you` above are byte-for-byte what they were. This
     *    pass changes WHEN this popup asks, never WHOSE funnel it belongs to.
     *
     * ---------------------------------------------------------------------
     * ⛔ SUPERSEDED BY ITEM 306 — PRESERVED, NOT DELETED:
     *
     *  > ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — mode
     *  >    `simple` (a bare 5-second timer) -> mode `gated` (engagement AND
     *  >    time), which is the treatment the parent funnel already runs.
     *  >
     *  > ⭐ WHY IT HAD TO MOVE AT THE SAME TIME AS THE SUPPRESSION LIFTED.
     *  >    This config had been dormant since 2026-07-19, so it was the only
     *  >    surface on the site that never received Andrew's 2026-08-19
     *  >    ruling: *"Agree on the first paint day google recs - wait for
     *  >    engagement and time."* Un-suppressing it unchanged would have
     *  >    shipped a 5-second interrupt onto a 36-screen page — the exact
     *  >    pattern that ruling retired, and the one Google's
     *  >    mobile-interstitial guidance penalises on a search landing.
     *  >    `/teachers/` IS a search landing.
     *  >
     *  > ⭐ scrollPct 20 / 12 were the parent funnel's MEASURED values.
     *  >
     *  > ⛔ NO fallbackDelay, for the same reason the parent popup had none.
     *
     * ⚠ THE GOOGLE POINT SURVIVES THE SUPERSESSION AND IS NOT SILENTLY
     *   DROPPED. `/teachers/` is still a search landing, and a 15-second
     *   time-only interstitial is still an interstitial. What changed is that
     *   1.19.296 argued 5s was too soon and reached for scroll as the
     *   remedy; item 306 keeps the 15s remedy and drops the scroll half. The
     *   floor — the part Google's guidance actually turns on — is unchanged
     *   at fifteen seconds, three times the interval that block objected to.
     *   ⛔ FLAGGED to the Chief of Staff, NOT resolved here: whether a
     *   time-only interstitial on a search-landing page is acceptable SEO risk
     *   is a decision, and it is Andrew's, not this desk's.
     * ---------------------------------------------------------------------
     */
    'trigger'       => [
        'mode'    => 'simple',
        'desktop' => ['delay' => 15000],
        'mobile'  => ['delay' => 15000],
    ],
]);

// If the popup form itself was just submitted and failed validation, the
// existing PRG redirect sends the visitor back to this same page with
// ?bhp_form=<form_id>&bhp_signup=<status>. Force the popup open immediately
// in that case so the inline error (rendered by signup-form.php itself via
// bhp_get_signup_feedback()) is actually visible instead of hidden behind
// the normal timer/scroll trigger.
$submitted_form = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');
?>
<div
  id="mariana-popup"
  class="mariana-popup"
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
    aria-labelledby="mariana-popup-title"
    aria-describedby="mariana-popup-desc"
    tabindex="-1"
  >
    <button type="button" class="mariana-popup__close" data-bhp-popup-close aria-label="<?php esc_attr_e('Close', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <p class="component-heading__eyebrow"><?php esc_html_e('Free classroom resource', 'brave-hearts'); ?></p>
    <h2 id="mariana-popup-title"><?php esc_html_e('Bring the Mariana Trench into Your Classroom', 'brave-hearts'); ?></h2>
    <p id="mariana-popup-desc" class="mariana-popup__text">
      <?php esc_html_e('Get a free, printable 20-minute classroom guide for Grades 1–3 with discussion questions, vocabulary, an activity, and connections to Common Core ELA and NGSS concepts.', 'brave-hearts'); ?>
    </p>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        'context'              => 'mariana_popup',
        'audience_type'        => 'teachers',
        'lead_magnet'          => 'mariana_trench_classroom_guide',
        'source_page'          => $source_page,
        'success_redirect_key' => 'mariana_guide_thank_you',
        'require_name'         => true,
        'submit_label'         => __('Email Me the Free Guide', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        'aria_labelledby'      => 'mariana-popup-title',
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php esc_html_e('Free printable PDF. No purchase required.', 'brave-hearts'); ?></p>
    <button type="button" class="mariana-popup__dismiss" data-bhp-popup-dismiss><?php esc_html_e('No thanks', 'brave-hearts'); ?></button>
  </div>
</div>
