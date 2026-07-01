<?php
/** Newsletter section powered by the provider-neutral acquisition form. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'form_action' => '', 'email_name' => 'email',
    'email_label' => '', 'email_placeholder' => '', 'submit_label' => '', 'privacy_text' => '', 'hidden_fields' => [],
    'audience_type' => 'parents_families', 'lead_magnet' => 'explorer_passport', 'source_page' => '', 'class' => '', 'benefits' => [],
]);
if (!$args['title']) { return; }
$section_id = $args['id'] ?: wp_unique_id('adventure-club-');
$heading_id = $section_id . '-title';
$form_action = apply_filters('bhp_newsletter_form_action', $args['form_action']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="newsletter-signup section section--dark <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container container--content newsletter-signup__inner">
    <?php if ($args['eyebrow']): ?><p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title']); ?></h2>
    <?php if ($args['text']): ?><div class="text-lead newsletter-signup__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <?php if ($args['benefits']): ?>
      <div class="newsletter-signup__benefits">
        <?php foreach ($args['benefits'] as $benefit): ?>
          <div>
            <h3><?php echo esc_html($benefit['title'] ?? ''); ?></h3>
            <?php if (!empty($benefit['text'])): ?><p><?php echo esc_html($benefit['text']); ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                => $section_id . '-form',
        'action'            => $form_action,
        'context'           => 'adventure_club',
        'audience_type'     => $args['audience_type'],
        'lead_magnet'       => $args['lead_magnet'],
        'source_page'       => $args['source_page'],
        'email_name'        => $args['email_name'],
        'email_label'       => $args['email_label'],
        'email_placeholder' => $args['email_placeholder'],
        'submit_label'      => $args['submit_label'],
        'privacy_text'      => $args['privacy_text'],
        'hidden_fields'     => $args['hidden_fields'],
        'aria_labelledby'   => $heading_id,
        'class'             => 'newsletter-signup__form',
        'field_class'       => 'newsletter-signup__field',
        'privacy_class'     => 'newsletter-signup__privacy',
    ]); ?>
  </div>
</section>
