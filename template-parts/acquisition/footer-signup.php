<?php
/** Compact signup suitable for the global footer. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'title' => '', 'text' => '', 'action' => '', 'audience_type' => 'general_readers',
    'lead_magnet' => '', 'source_page' => '', 'submit_label' => '', 'class' => '',
]);
$panel_id = $args['id'] ?: wp_unique_id('footer-signup-');
$heading_id = $panel_id . '-title';
?>
<section id="<?php echo esc_attr($panel_id); ?>" class="footer-signup <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="footer-signup__content">
    <h3 id="<?php echo esc_attr($heading_id); ?>"><?php echo esc_html($args['title'] ?: __('Be first to hear about new books', 'brave-hearts')); ?></h3>
    <div class="footer-signup__text"><?php echo wp_kses_post($args['text'] ?: __('Join the Adventure Club for book news and free printable adventures.', 'brave-hearts')); ?></div>
  </div>
  <?php get_template_part('template-parts/acquisition/signup-form', null, [
      'id'            => $panel_id . '-form',
      'action'        => $args['action'],
      'context'       => 'footer',
      'audience_type' => $args['audience_type'],
      'lead_magnet'   => $args['lead_magnet'],
      'source_page'   => $args['source_page'],
      'submit_label'  => $args['submit_label'] ?: __('Join the Adventure Club', 'brave-hearts'),
      'class'         => 'acquisition-form--footer',
      'aria_labelledby' => $heading_id,
  ]); ?>
</section>
