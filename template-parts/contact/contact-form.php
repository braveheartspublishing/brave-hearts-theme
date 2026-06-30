<?php
/** Accessible provider-neutral contact and outreach form. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'action' => '', 'source_page' => '', 'inquiry_type' => '', 'class' => '',
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
?>
<form id="<?php echo esc_attr($form_id); ?>" class="contact-form <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" <?php if ($form_ready): ?>action="<?php echo esc_url($form_action); ?>"<?php endif; ?> method="post" aria-describedby="<?php echo esc_attr($note_id); ?>">
  <input type="hidden" name="source_page" value="<?php echo esc_url($source_page); ?>">

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-name"><?php esc_html_e('Name', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-name" name="name" type="text" autocomplete="name" <?php disabled(!$form_ready); ?> required>
  </div>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-email"><?php esc_html_e('Email', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-email" name="email" type="email" autocomplete="email" <?php disabled(!$form_ready); ?> required>
  </div>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-organization"><?php esc_html_e('Organization / School', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-organization" name="organization" type="text" autocomplete="organization" <?php disabled(!$form_ready); ?>>
  </div>

  <div class="contact-form__field">
    <label for="<?php echo esc_attr($form_id); ?>-role"><?php esc_html_e('Role', 'brave-hearts'); ?></label>
    <select id="<?php echo esc_attr($form_id); ?>-role" name="role" <?php disabled(!$form_ready); ?>>
      <?php foreach ($roles as $value => $label): ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="contact-form__field contact-form__field--full">
    <label for="<?php echo esc_attr($form_id); ?>-inquiry"><?php esc_html_e('Inquiry Type', 'brave-hearts'); ?></label>
    <select id="<?php echo esc_attr($form_id); ?>-inquiry" name="inquiry_type" <?php disabled(!$form_ready); ?> required>
      <?php foreach ($inquiry_types as $value => $label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($selected_inquiry, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="contact-form__field contact-form__field--full">
    <label for="<?php echo esc_attr($form_id); ?>-message"><?php esc_html_e('Message', 'brave-hearts'); ?></label>
    <textarea id="<?php echo esc_attr($form_id); ?>-message" name="message" rows="7" <?php disabled(!$form_ready); ?> required></textarea>
  </div>

  <div class="contact-form__actions contact-form__field--full">
    <button class="btn btn-primary" type="submit" <?php disabled(!$form_ready); ?> aria-disabled="<?php echo $form_ready ? 'false' : 'true'; ?>"><?php esc_html_e('Send Message', 'brave-hearts'); ?></button>
  </div>

  <div id="<?php echo esc_attr($note_id); ?>" class="contact-form__note contact-form__field--full">
    <?php if ($form_ready): ?>
      <p><?php esc_html_e('Please do not include sensitive or private student information.', 'brave-hearts'); ?></p>
    <?php else: ?>
      <p><?php esc_html_e('The online contact form is temporarily unavailable. Please use the direct email shown on this page.', 'brave-hearts'); ?></p>
    <?php endif; ?>
  </div>
</form>
