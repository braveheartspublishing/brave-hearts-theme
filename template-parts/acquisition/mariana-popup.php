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
    'trigger'       => [
        'mode'    => 'simple',
        'desktop' => ['delay' => 5000, 'scrollPct' => 30],
        'mobile'  => ['delay' => 5000, 'scrollPct' => 30],
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
