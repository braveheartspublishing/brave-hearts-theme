<?php
/** Lead magnet call-to-action selected from the central registry. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'lead_magnet' => 'explorer_passport', 'title' => '', 'text' => '', 'action' => '',
    'audience_type' => '', 'source_page' => '', 'submit_label' => '', 'class' => '',
]);
$magnets = bhp_get_lead_magnets();
$magnet_key = sanitize_key($args['lead_magnet']);
if (!isset($magnets[$magnet_key])) { return; }
$magnet = $magnets[$magnet_key];
$panel_id = $args['id'] ?: wp_unique_id('lead-magnet-');
$heading_id = $panel_id . '-title';
$audience_type = $args['audience_type'] ?: $magnet['audience_type'];
?>
<aside id="<?php echo esc_attr($panel_id); ?>" class="acquisition-panel lead-magnet-cta <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="acquisition-panel__content">
    <p class="component-heading__eyebrow"><?php esc_html_e('Free printable resource', 'brave-hearts'); ?></p>
    <h3 id="<?php echo esc_attr($heading_id); ?>"><?php echo esc_html($args['title'] ?: $magnet['title']); ?></h3>
    <div class="acquisition-panel__text"><?php echo wp_kses_post($args['text'] ?: $magnet['description']); ?></div>
  </div>
  <?php get_template_part('template-parts/acquisition/signup-form', null, [
      'id'            => $panel_id . '-form',
      'action'        => $args['action'],
      'context'       => 'lead_magnet',
      'audience_type' => $audience_type,
      'lead_magnet'   => $magnet_key,
      'source_page'   => $args['source_page'],
      'submit_label'  => $args['submit_label'] ?: __('Get the Free Resource', 'brave-hearts'),
      'privacy_text'  => __('The resource will be delivered after provider integration is configured.', 'brave-hearts'),
      'aria_labelledby' => $heading_id,
  ]); ?>
</aside>
