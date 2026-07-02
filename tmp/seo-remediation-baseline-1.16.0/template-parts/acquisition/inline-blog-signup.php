<?php
/** Compact inline signup for blog posts. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'title' => '', 'text' => '', 'action' => '', 'audience_type' => 'general_readers',
    'lead_magnet' => 'reading_guides', 'source_page' => '', 'submit_label' => '', 'class' => '',
]);
$panel_id = $args['id'] ?: wp_unique_id('inline-blog-signup-');
$heading_id = $panel_id . '-title';
?>
<aside id="<?php echo esc_attr($panel_id); ?>" class="acquisition-panel acquisition-panel--compact inline-blog-signup <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="acquisition-panel__content">
    <h3 id="<?php echo esc_attr($heading_id); ?>"><?php echo esc_html($args['title'] ?: __('Get free printable adventures', 'brave-hearts')); ?></h3>
    <div class="acquisition-panel__text"><?php echo wp_kses_post($args['text'] ?: __('Keep curious readers exploring after the last page.', 'brave-hearts')); ?></div>
  </div>
  <?php get_template_part('template-parts/acquisition/signup-form', null, [
      'id'            => $panel_id . '-form',
      'action'        => $args['action'],
      'context'       => 'inline_blog',
      'audience_type' => $args['audience_type'],
      'lead_magnet'   => $args['lead_magnet'],
      'source_page'   => $args['source_page'],
      'submit_label'  => $args['submit_label'] ?: __('Send Me the Adventure', 'brave-hearts'),
      'aria_labelledby' => $heading_id,
  ]); ?>
</aside>
