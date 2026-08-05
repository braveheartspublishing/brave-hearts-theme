<?php
/**
 * Sitewide parent-focused lead popup for the Reluctant Reader Adventure
 * Kit. Uses the same generic engine as the teacher popup
 * (assets/js/mariana-popup.js) with its own isolated storage prefix
 * (bhp_parent_popup_*) and analytics event prefix (parent_popup_*), so
 * dismissing or signing up through one popup never affects the other's
 * suppression state.
 *
 * Does not render at all while the parent PDF is unconfigured — see
 * bhp_should_show_parent_popup().
 */
defined('ABSPATH') || exit;

$source_page = get_permalink(get_queried_object_id()) ?: home_url('/');
$form_id = 'parent-popup-signup-form';

$submitted_form = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');

$popup_config = wp_json_encode([
    'eventPrefix'   => 'parent_popup',
    'source'        => 'parent_popup',
    'storagePrefix' => 'bhp_parent_popup',
    'thankYouPath'  => 'adventure-kit-thank-you',
    'trigger'       => [
        'mode'    => 'gated',
        'desktop' => ['minDelay' => 8000, 'fallbackDelay' => 15000, 'scrollPct' => 40],
        'mobile'  => ['minDelay' => 10000, 'fallbackDelay' => 18000, 'scrollPct' => 50],
    ],
]);
?>
<div
  id="parent-popup"
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
    aria-labelledby="parent-popup-title"
    aria-describedby="parent-popup-desc"
    tabindex="-1"
  >
    <button type="button" class="mariana-popup__close" data-bhp-popup-close aria-label="<?php esc_attr_e('Close', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <p class="component-heading__eyebrow"><?php esc_html_e('Free reading adventure', 'brave-hearts'); ?></p>
    <h2 id="parent-popup-title"><?php esc_html_e('Does Your Child Say Reading Is Boring?', 'brave-hearts'); ?></h2>
    <p id="parent-popup-desc" class="mariana-popup__text">
      <?php /* A6 (2026-08-03): duration claim removed. Was "Try a free
               20-minute adventure designed for curious kids ages 6–9 - …". */ ?>
      <?php esc_html_e('Try a free adventure designed for curious kids ages 6–9 - with a sample chapter, explorer activity, and simple ways to make reading feel fun again.', 'brave-hearts'); ?>
    </p>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        'context'              => 'parent_popup',
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => true,
        'submit_label'         => __('Send Me the Free Adventure Kit', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        'aria_labelledby'      => 'parent-popup-title',
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php esc_html_e('Free printable PDF. No purchase required.', 'brave-hearts'); ?></p>
    <button type="button" class="mariana-popup__dismiss" data-bhp-popup-dismiss><?php esc_html_e('No thanks', 'brave-hearts'); ?></button>
  </div>
</div>
