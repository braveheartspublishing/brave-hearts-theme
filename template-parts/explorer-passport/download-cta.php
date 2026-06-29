<?php
/** Reusable Explorer Passport download call-to-action. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'title' => '', 'text' => '', 'label' => '', 'url' => '', 'note' => '', 'class' => '',
]);
$panel_id = $args['id'] ?: wp_unique_id('passport-download-');
$heading_id = $panel_id . '-title';
$download = bhp_get_explorer_passport_download($args['url']);
$button_classes = 'btn btn-primary passport-download-cta__button' . ($download['ready'] ? '' : ' is-disabled');
?>
<aside id="<?php echo esc_attr($panel_id); ?>" class="passport-download-cta <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="passport-download-cta__content">
    <p class="component-heading__eyebrow"><?php esc_html_e('Explorer Passport', 'brave-hearts'); ?></p>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title'] ?: __('Become an Official Brave Hearts Explorer.', 'brave-hearts')); ?></h2>
    <div class="passport-download-cta__text"><?php echo wp_kses_post($args['text'] ?: __('Your printable Explorer Passport will be available here after the first approved PDF is created.', 'brave-hearts')); ?></div>
  </div>
  <?php if (!$download['ready']): ?>
    <!-- Explorer Passport download placeholder: replace through bhp_explorer_passport_download_url and mark ready only after asset approval. -->
  <?php endif; ?>
  <a
    class="<?php echo esc_attr($button_classes); ?>"
    href="<?php echo esc_url($download['url']); ?>"
    <?php if (!$download['ready']): ?>aria-disabled="true" tabindex="-1"<?php endif; ?>
  ><?php echo esc_html($args['label'] ?: __('Download the Explorer Passport', 'brave-hearts')); ?></a>
  <p class="passport-download-cta__note"><?php echo esc_html($args['note'] ?: ($download['ready'] ? __('Printable PDF download.', 'brave-hearts') : __('PDF creation and delivery are coming in a later phase.', 'brave-hearts'))); ?></p>
</aside>
