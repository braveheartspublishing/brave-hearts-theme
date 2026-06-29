<?php
/** Gentle final homepage call to action. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'primary_link' => [], 'secondary_link' => [], 'class' => '',
]);
if (!$args['title']) { return; }
$section_id = $args['id'] ?: wp_unique_id('final-cta-');
$heading_id = $section_id . '-title';
$primary = wp_parse_args($args['primary_link'], ['url' => '', 'label' => '']);
$secondary = wp_parse_args($args['secondary_link'], ['url' => '', 'label' => '']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="final-cta section <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container container--content final-cta__inner">
    <?php if ($args['eyebrow']): ?><p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title']); ?></h2>
    <?php if ($args['text']): ?><div class="text-lead final-cta__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <div class="final-cta__actions cluster">
      <?php if ($primary['url'] && $primary['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($primary['url']); ?>"><?php echo esc_html($primary['label']); ?></a><?php endif; ?>
      <?php if ($secondary['url'] && $secondary['label']): ?><a class="btn btn-ghost" href="<?php echo esc_url($secondary['url']); ?>"><?php echo esc_html($secondary['label']); ?></a><?php endif; ?>
    </div>
  </div>
</section>
