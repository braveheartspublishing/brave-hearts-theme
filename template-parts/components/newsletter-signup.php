<?php
/** Newsletter signup section with a provider-neutral email form. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'form_action' => '', 'email_name' => 'email',
    'email_label' => '', 'email_placeholder' => '', 'submit_label' => '', 'privacy_text' => '', 'hidden_fields' => [], 'class' => '',
]);
if (!$args['title']) { return; }
$section_id = $args['id'] ?: wp_unique_id('adventure-club-');
$heading_id = $section_id . '-title';
$email_id = $section_id . '-email';
$form_action = apply_filters('bhp_newsletter_form_action', $args['form_action']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="newsletter-signup section section--dark <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container container--content newsletter-signup__inner">
    <?php if ($args['eyebrow']): ?><p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title']); ?></h2>
    <?php if ($args['text']): ?><div class="text-lead newsletter-signup__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <form class="newsletter-signup__form" action="<?php echo esc_url($form_action); ?>" method="post">
      <?php foreach ($args['hidden_fields'] as $name => $value): ?>
        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
      <?php endforeach; ?>
      <div class="newsletter-signup__field">
        <label for="<?php echo esc_attr($email_id); ?>"><?php echo esc_html($args['email_label'] ?: __('Email address', 'brave-hearts')); ?></label>
        <input id="<?php echo esc_attr($email_id); ?>" name="<?php echo esc_attr($args['email_name']); ?>" type="email" autocomplete="email" placeholder="<?php echo esc_attr($args['email_placeholder']); ?>" required>
      </div>
      <button class="btn btn-primary" type="submit"><?php echo esc_html($args['submit_label'] ?: __('Join the Adventure Club', 'brave-hearts')); ?></button>
    </form>
    <?php if ($args['privacy_text']): ?><p class="newsletter-signup__privacy"><?php echo esc_html($args['privacy_text']); ?></p><?php endif; ?>
  </div>
</section>
