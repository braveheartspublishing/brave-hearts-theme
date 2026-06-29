<?php
/**
 * Provider-neutral acquisition form.
 *
 * Required segmentation fields: audience_type, lead_magnet, and source_page.
 * Forms remain disabled until bhp_signup_form_action returns a real endpoint.
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
$form_id       = $args['id'] ?: wp_unique_id('bhp-signup-');
$email_id      = $form_id . '-email';
$note_id       = $form_id . '-note';
$source_page   = $args['source_page'];

if (!$source_page) {
    $object_id   = get_queried_object_id();
    $source_page = $object_id ? get_permalink($object_id) : home_url('/');
}

$provider_action = bhp_get_signup_form_action($args['action'], $audience_type, $context);
$form_ready      = (bool) $provider_action;
$form_action     = $form_ready ? $provider_action : bhp_get_signup_placeholder_action($context);
$form_classes    = trim('acquisition-form ' . $args['class']);
$field_classes   = trim('acquisition-form__field ' . $args['field_class']);
$privacy_classes = trim('acquisition-form__privacy ' . $args['privacy_class']);
$provider_note   = $args['provider_note'] ?: __('Email provider connection pending. This form is not active yet.', 'brave-hearts');
$reserved_fields = ['audience_type', 'lead_magnet', 'source_page'];
?>
<?php if (!$form_ready): ?>
  <!-- Provider integration placeholder: configure the bhp_signup_form_action filter before enabling this form. -->
<?php endif; ?>
<form
  id="<?php echo esc_attr($form_id); ?>"
  class="<?php echo esc_attr($form_classes); ?>"
  action="<?php echo esc_url($form_action); ?>"
  method="post"
  <?php if ($args['aria_labelledby']): ?>aria-labelledby="<?php echo esc_attr($args['aria_labelledby']); ?>"<?php endif; ?>
  aria-describedby="<?php echo esc_attr($note_id); ?>"
>
  <input type="hidden" name="audience_type" value="<?php echo esc_attr($audience_type); ?>">
  <input type="hidden" name="lead_magnet" value="<?php echo esc_attr(sanitize_key($args['lead_magnet'])); ?>">
  <input type="hidden" name="source_page" value="<?php echo esc_url($source_page); ?>">

  <?php foreach ($args['hidden_fields'] as $name => $value): ?>
    <?php if (!in_array($name, $reserved_fields, true)): ?>
      <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="<?php echo esc_attr($field_classes); ?>">
    <label for="<?php echo esc_attr($email_id); ?>"><?php echo esc_html($args['email_label'] ?: __('Email address', 'brave-hearts')); ?></label>
    <input
      id="<?php echo esc_attr($email_id); ?>"
      name="<?php echo esc_attr($args['email_name']); ?>"
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
    <?php if ($args['privacy_text']): ?><p class="<?php echo esc_attr($privacy_classes); ?>"><?php echo esc_html($args['privacy_text']); ?></p><?php endif; ?>
    <?php if (!$form_ready): ?><p class="acquisition-form__provider-note"><?php echo esc_html($provider_note); ?></p><?php endif; ?>
  </div>
</form>
