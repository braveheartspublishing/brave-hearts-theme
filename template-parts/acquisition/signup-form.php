<?php
/**
 * Brave Hearts acquisition form backed by the connected MC4WP audience.
 *
 * Required segmentation fields: audience_type, lead_magnet, and source_page.
 * Forms remain disabled if MC4WP or its selected audience is unavailable.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'id'                => '',
    'action'            => '',
    'context'           => 'adventure_club',
    'audience_type'     => 'general_readers',
    'lead_magnet'       => '',
    'source_page'       => '',
    'email_name'        => 'email',
    'email_label'       => '',
    'email_placeholder' => '',
    'submit_label'      => '',
    'privacy_text'      => '',
    'provider_note'     => '',
    'hidden_fields'     => [],
    'aria_labelledby'   => '',
    'class'             => '',
    'field_class'       => '',
    'privacy_class'     => '',
]);

$context       = sanitize_key($args['context']);
$audience_type = bhp_normalize_audience_type($args['audience_type']);
$form_id       = sanitize_html_class($args['id'] ?: wp_unique_id('bhp-signup-'));
$email_id      = $form_id . '-email';
$note_id       = $form_id . '-note';
$status_id     = $form_id . '-status';
$source_page   = $args['source_page'];
$email_name    = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $args['email_name']) ?: 'email';

if (!$source_page) {
    $object_id   = get_queried_object_id();
    $source_page = $object_id ? get_permalink($object_id) : home_url('/');
}

$form_action     = bhp_get_signup_form_action($args['action'], $audience_type, $context);
$form_ready      = (bool) $form_action;
$form_classes    = trim('acquisition-form ' . $args['class']);
$field_classes   = trim('acquisition-form__field ' . $args['field_class']);
$privacy_classes = trim('acquisition-form__privacy ' . $args['privacy_class']);
$provider_note   = $args['provider_note'] ?: __('Signup is temporarily unavailable while the email connection is restored.', 'brave-hearts');
$feedback        = bhp_get_signup_feedback($form_id);
$describedby     = trim($note_id . ($feedback ? ' ' . $status_id : ''));
$reserved_fields = [
    'action',
    'audience_type',
    'lead_magnet',
    'source_page',
    'bhp_context',
    'bhp_email_field',
    'bhp_form_id',
    'bhp_signup_nonce',
    'bhp_website',
    $email_name,
];
?>
<form
  id="<?php echo esc_attr($form_id); ?>"
  class="<?php echo esc_attr($form_classes); ?>"
  action="<?php echo esc_url($form_action); ?>"
  method="post"
  <?php if ($args['aria_labelledby']): ?>aria-labelledby="<?php echo esc_attr($args['aria_labelledby']); ?>"<?php endif; ?>
  aria-describedby="<?php echo esc_attr($describedby); ?>"
>
  <?php if ($form_ready): ?>
    <input type="hidden" name="action" value="bhp_mailchimp_signup">
    <input type="hidden" name="bhp_context" value="<?php echo esc_attr($context); ?>">
    <input type="hidden" name="bhp_email_field" value="<?php echo esc_attr($email_name); ?>">
    <input type="hidden" name="bhp_form_id" value="<?php echo esc_attr($form_id); ?>">
    <?php wp_nonce_field('bhp_mailchimp_signup_' . $form_id, 'bhp_signup_nonce', false); ?>
  <?php endif; ?>

  <input type="hidden" name="audience_type" value="<?php echo esc_attr($audience_type); ?>">
  <input type="hidden" name="lead_magnet" value="<?php echo esc_attr(sanitize_key($args['lead_magnet'])); ?>">
  <input type="hidden" name="source_page" value="<?php echo esc_url($source_page); ?>">

  <?php foreach ($args['hidden_fields'] as $name => $value): ?>
    <?php if (!in_array($name, $reserved_fields, true)): ?>
      <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="bhp-form-honeypot" aria-hidden="true">
    <label for="<?php echo esc_attr($form_id); ?>-website"><?php esc_html_e('Leave this field blank', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-website" name="bhp_website" type="text" tabindex="-1" autocomplete="off" <?php disabled(!$form_ready); ?>>
  </div>

  <div class="<?php echo esc_attr($field_classes); ?>">
    <label for="<?php echo esc_attr($email_id); ?>"><?php echo esc_html($args['email_label'] ?: __('Email address', 'brave-hearts')); ?></label>
    <input
      id="<?php echo esc_attr($email_id); ?>"
      name="<?php echo esc_attr($email_name); ?>"
      type="email"
      autocomplete="email"
      placeholder="<?php echo esc_attr($args['email_placeholder']); ?>"
      <?php disabled(!$form_ready); ?>
      required
    >
  </div>

  <button class="btn btn-primary acquisition-form__submit" type="submit" <?php disabled(!$form_ready); ?> aria-disabled="<?php echo $form_ready ? 'false' : 'true'; ?>">
    <?php echo esc_html($args['submit_label'] ?: __('Join the Adventure Club', 'brave-hearts')); ?>
  </button>

  <div id="<?php echo esc_attr($note_id); ?>" class="acquisition-form__notes">
    <?php if ($feedback): ?>
      <p
        id="<?php echo esc_attr($status_id); ?>"
        class="acquisition-form__status is-<?php echo esc_attr($feedback['type']); ?>"
        role="<?php echo esc_attr($feedback['role']); ?>"
        aria-live="polite"
      ><?php echo esc_html($feedback['message']); ?></p>
    <?php endif; ?>
    <?php if ($args['privacy_text']): ?><p class="<?php echo esc_attr($privacy_classes); ?>"><?php echo esc_html($args['privacy_text']); ?></p><?php endif; ?>
    <?php if (!$form_ready): ?><p class="acquisition-form__provider-note" role="status"><?php echo esc_html($provider_note); ?></p><?php endif; ?>
  </div>
</form>
