<?php
/** Adventure Club signup for parents, families, or general readers. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'title' => '', 'text' => '', 'action' => '', 'audience_type' => 'parents_families',
    'lead_magnet' => 'explorer_passport', 'source_page' => '', 'submit_label' => '', 'class' => '',
]);
$section_id = $args['id'] ?: wp_unique_id('adventure-club-signup-');
$heading_id = $section_id . '-title';
?>
<section id="<?php echo esc_attr($section_id); ?>" class="acquisition-panel adventure-club-signup <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="acquisition-panel__content">
    <p class="component-heading__eyebrow"><?php esc_html_e('The Adventure Club', 'brave-hearts'); ?></p>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title'] ?: __('Join the Adventure Club', 'brave-hearts')); ?></h2>
    <div class="acquisition-panel__text"><?php echo wp_kses_post($args['text'] ?: __('Get free printable adventures and be first to hear about new books.', 'brave-hearts')); ?></div>
  </div>
  <?php get_template_part('template-parts/acquisition/signup-form', null, [
      'id'            => $section_id . '-form',
      'action'        => $args['action'],
      'context'       => 'adventure_club',
      'audience_type' => $args['audience_type'],
      'lead_magnet'   => $args['lead_magnet'],
      'source_page'   => $args['source_page'],
      'submit_label'  => $args['submit_label'] ?: __('Join the Adventure Club', 'brave-hearts'),
      'privacy_text'  => __('Useful adventures only. Unsubscribe anytime.', 'brave-hearts'),
      'aria_labelledby' => $heading_id,
  ]); ?>
</section>
