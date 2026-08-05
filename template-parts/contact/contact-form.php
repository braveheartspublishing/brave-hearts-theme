<?php
/** Accessible provider-neutral contact and outreach form. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'action' => '', 'source_page' => '', 'inquiry_type' => '', 'fallback_email' => '', 'class' => '',
]);
$form_id = $args['id'] ?: wp_unique_id('bhp-contact-form-');
$note_id = $form_id . '-note';
$source_page = $args['source_page'] ?: (get_queried_object_id() ? get_permalink(get_queried_object_id()) : home_url('/'));
$roles = apply_filters('bhp_contact_roles', [
    '' => __('Select a role', 'brave-hearts'),
    'parent-family' => __('Parent / Family', 'brave-hearts'),
    'teacher' => __('Teacher', 'brave-hearts'),
    'librarian' => __('Librarian', 'brave-hearts'),
    'school-administrator' => __('School Administrator', 'brave-hearts'),
    'homeschool-educator' => __('Homeschool Educator', 'brave-hearts'),
    'media-podcast' => __('Media / Podcast', 'brave-hearts'),
    'bookseller' => __('Bookseller', 'brave-hearts'),
    'other' => __('Other', 'brave-hearts'),
]);
$inquiry_types = apply_filters('bhp_contact_inquiry_types', [
    '' => __('Select an inquiry type', 'brave-hearts'),
    'general' => __('General Question', 'brave-hearts'),
    'read-aloud' => __('Read-Aloud Request', 'brave-hearts'),
    'school-library' => __('School / Library Inquiry', 'brave-hearts'),
    'media' => __('Media / Podcast Inquiry', 'brave-hearts'),
    'bulk-orders' => __('Bulk Orders / Classroom Packs', 'brave-hearts'),
    'partnership' => __('Partnership', 'brave-hearts'),
    'other' => __('Other', 'brave-hearts'),
]);
$query_inquiry = isset($_GET['inquiry']) ? sanitize_key(wp_unslash($_GET['inquiry'])) : '';
$selected_inquiry = sanitize_key($args['inquiry_type'] ?: $query_inquiry);
if (!isset($inquiry_types[$selected_inquiry])) {
    $selected_inquiry = '';
}
$provider_action = bhp_get_contact_form_action($args['action']);
$form_ready = (bool) $provider_action;
$form_action = $provider_action;
$fallback_email = sanitize_email($args['fallback_email']);
$is_native = function_exists('bhp_contact_is_native') && bhp_contact_is_native($form_action);

// Finding #27: on-page success/error feedback after a native submission
// (?bhp_contact=success|invalid|error). No PII is echoed back.
$contact_status = isset($_GET['bhp_contact']) ? sanitize_key(wp_unslash($_GET['bhp_contact'])) : '';
$status_messages = [
    'success' => ['type' => 'success', 'role' => 'status', 'text' => __('Thank you - your message has been sent. We’ll reply by email as soon as we can.', 'brave-hearts')],
    'invalid' => ['type' => 'error', 'role' => 'alert', 'text' => __('Please add your name, a valid email, an inquiry type, and a message, then send again.', 'brave-hearts')],
    'error'   => ['type' => 'error', 'role' => 'alert', 'text' => __('Sorry - something went wrong sending your message. Please try again, or email us directly.', 'brave-hearts')],
];
$status = $status_messages[$contact_status] ?? null;
?>
<?php if (!$form_ready): ?>
  <div class="contact-form contact-form--fallback <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
    <p><?php esc_html_e('Tell us about your question, classroom, library, event, or partnership by email. Please do not include sensitive or private student information.', 'brave-hearts'); ?></p>
    <?php if ($fallback_email): ?>
      <p><a class="btn btn-primary" href="mailto:<?php echo esc_attr($fallback_email); ?>"><?php esc_html_e('Email Brave Hearts Publishing', 'brave-hearts'); ?></a></p>
    <?php endif; ?>
  </div>
  <?php return; ?>
<?php endif; ?>
<?php if ($status): ?>
  <p class="contact-form__status is-<?php echo esc_attr($status['type']); ?>" role="<?php echo esc_attr($status['role']); ?>" aria-live="polite"><?php echo esc_html($status['text']); ?></p>
  <?php if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()): ?>
    <script>
    (function(){
      var key='bhp_contact_evt_<?php echo esc_js($contact_status); ?>';
      try{ if(sessionStorage.getItem(key)) return; sessionStorage.setItem(key,'1'); }catch(e){}
      window.dataLayer=window.dataLayer||[];
      window.dataLayer.push({event:<?php echo wp_json_encode('success' === $contact_status ? 'contact_submit' : 'contact_error'); ?>, form:'contact', status:<?php echo wp_json_encode($contact_status); ?>});
    })();
    </script>
  <?php endif; ?>
<?php endif; ?>
<form id="<?php echo esc_attr($form_id); ?>" class="contact-form <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" action="<?php echo esc_url($form_action); ?>" method="post" aria-describedby="<?php echo esc_attr($note_id); ?>">
  <input type="hidden" name="source_page" value="<?php echo esc_url($source_page); ?>">
  <?php if ($is_native): ?>
    <input type="hidden" name="action" value="<?php echo esc_attr(BHP_CONTACT_ACTION); ?>">
    <?php wp_nonce_field(BHP_CONTACT_ACTION, 'bhp_contact_nonce', false); ?>
    <div class="bhp-form-honeypot" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
      <label for="<?php echo esc_attr($form_id); ?>-hp"><?php esc_html_e('Leave this field blank', 'brave-hearts'); ?></label>
      <input id="<?php echo esc_attr($form_id); ?>-hp" name="bhp_contact_hp" type="text" tabindex="-1" autocomplete="off">
    </div>
  <?php endif; ?>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-name"><?php esc_html_e('Name', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-name" name="name" type="text" autocomplete="name" required>
  </div>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-email"><?php esc_html_e('Email', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-email" name="email" type="email" autocomplete="email" required>
  </div>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-organization"><?php esc_html_e('Organization / School', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-organization" name="organization" type="text" autocomplete="organization">
  </div>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-role"><?php esc_html_e('Role', 'brave-hearts'); ?></label>
    <select id="<?php echo esc_attr($form_id); ?>-role" name="role">
      <?php foreach ($roles as $value => $label): ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="contact-form__field contact-form__field--full">
    <label for="<?php echo esc_attr($form_id); ?>-inquiry"><?php esc_html_e('Inquiry Type', 'brave-hearts'); ?></label>
    <select id="<?php echo esc_attr($form_id); ?>-inquiry" name="inquiry_type" required>
      <?php foreach ($inquiry_types as $value => $label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($selected_inquiry, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="contact-form__field contact-form__field--full">
    <label for="<?php echo esc_attr($form_id); ?>-message"><?php esc_html_e('Message', 'brave-hearts'); ?></label>
    <textarea id="<?php echo esc_attr($form_id); ?>-message" name="message" rows="7" required></textarea>
  </div>

  <div class="contact-form__actions contact-form__field--full">
    <button class="btn btn-primary" type="submit"><?php esc_html_e('Send Message', 'brave-hearts'); ?></button>
  </div>

  <div id="<?php echo esc_attr($note_id); ?>" class="contact-form__note contact-form__field--full">
    <p><?php esc_html_e('Please do not include sensitive or private student information.', 'brave-hearts'); ?></p>
  </div>
</form>
