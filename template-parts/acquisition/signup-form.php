<?php
/**
 * Brave Hearts acquisition form backed by the connected MC4WP audience.
 *
 * Required segmentation fields: audience_type, lead_magnet, and source_page.
 * Forms remain disabled if MC4WP or its selected audience is unavailable.
 *
 * Set 'require_name' => true to make the first-name field required for this
 * specific form only — other forms rendered from this same template are
 * unaffected unless they also opt in.
 *
 * Wave 1 (2026-08-04, theme 1.19.168) adds two OPTIONAL, BACKWARD-COMPATIBLE
 * arguments. Every existing caller passes neither and renders exactly as it
 * did in 1.19.167:
 *
 *   'show_name'       — render the first-name field WITHOUT making it
 *                       required. `require_name` still implies it, so the
 *                       parent popup and every other existing caller is
 *                       unaffected.
 *   'segment_options' — [key => label] map rendering ONE <select
 *                       name="bhp_segment">. ⛔ The browser only ever sends
 *                       the short KEY. It never sends an audience type, a
 *                       lead-magnet key, a tag string or a URL. The key is
 *                       resolved server-side against a fixed whitelist
 *                       (`bhp_get_capture_segment_routes()`), exactly like
 *                       the quiz's route map, so nothing about the audience
 *                       or the tags applied is attacker-controlled.
 *
 * 1.19.205 (2026-08-07) adds ONE further optional, backward-compatible
 * argument. Every existing caller passes nothing and renders exactly as it
 * did in 1.19.204 — the default is the empty string, so the emitted class
 * list is byte-identical unless a caller opts in:
 *
 *   'submit_class'    — extra class(es) on the submit button. It exists so a
 *                       placement can wear one of the site's SHIPPED CTA
 *                       treatments (`btn-cta-primary`) instead of a
 *                       component re-declaring those colours for itself,
 *                       which is how the site ended up with six button
 *                       specs before wave F1 collapsed them. `btn` and
 *                       `btn-primary` are deliberately left in place: F1's
 *                       geometry rules key off `.btn`, and the colour list
 *                       that carries `btn-cta-primary` is declared LATER in
 *                       `style.css` than `.btn-primary`'s, so with equal
 *                       specificity and both `!important` the CTA treatment
 *                       wins on source order. Verified in the browser, not
 *                       inferred: computed background rgb(23, 63, 47).
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'id'                   => '',
    'action'               => '',
    'context'              => 'adventure_club',
    'audience_type'        => 'general_readers',
    'lead_magnet'          => '',
    'source_page'          => '',
    'success_redirect_key' => '',
    'require_name'         => false,
    'show_name'            => false,
    'segment_options'      => [],
    'segment_label'        => '',
    'name_name'            => 'first_name',
    'name_label'           => '',
    'name_placeholder'     => '',
    'email_name'           => 'email',
    'email_label'          => '',
    'email_placeholder'    => '',
    'submit_label'         => '',
    'privacy_text'         => '',
    'provider_note'        => '',
    'hidden_fields'        => [],
    'aria_labelledby'      => '',
    'class'                => '',
    'field_class'          => '',
    'privacy_class'        => '',
    'submit_class'         => '',
]);

$context       = sanitize_key($args['context']);
$audience_type = bhp_normalize_audience_type($args['audience_type']);
$form_id       = sanitize_html_class($args['id'] ?: wp_unique_id('bhp-signup-'));
$name_id       = $form_id . '-name';
$email_id      = $form_id . '-email';
$note_id       = $form_id . '-note';
$status_id     = $form_id . '-status';
$source_page   = $args['source_page'];
$require_name  = (bool) $args['require_name'];
$show_name     = $require_name || (bool) $args['show_name'];
$segment_options = is_array($args['segment_options']) ? $args['segment_options'] : [];
$segment_id    = $form_id . '-segment';
$name_name     = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $args['name_name']) ?: 'first_name';
$email_name    = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $args['email_name']) ?: 'email';

if (!$source_page) {
    $object_id   = get_queried_object_id();
    $source_page = $object_id ? get_permalink($object_id) : home_url('/');
}

$form_action     = bhp_get_signup_form_action($args['action'], $audience_type, $context);
$form_ready      = (bool) $form_action;

/**
 * 1.19.191 (2026-08-05), CYCLE144-LD-18x. `.acquisition-form`'s default grid is
 * `minmax(0, 1fr) auto` — ONE field beside ONE submit button. It was never a
 * layout for a form with a SECOND field: the browser auto-places field 2 into
 * the submit's `auto` column, so the email input ends up orphaned in a
 * max-content-sized column with the CTA stranded on the row below it. Observed
 * on PRODUCTION markup at 1920 and 1440 in the footer capture block (email
 * input 238px, floating right of the segment select) and on the Mariana
 * teacher-guide page (name 308px / email 238px).
 *
 * The class is emitted SERVER-SIDE, from the same booleans that decide whether
 * the extra fields render at all, so the stacking can never disagree with the
 * field count. The CSS also carries a `:has()` net for markup this template
 * does not produce; this class is what makes the fix deterministic in engines
 * without `:has()`.
 */
$is_multi_field  = $show_name || (bool) $segment_options;
$form_classes    = trim('acquisition-form ' . ($is_multi_field ? 'acquisition-form--stacked ' : '') . $args['class']);
$field_classes   = trim('acquisition-form__field ' . $args['field_class']);
$privacy_classes = trim('acquisition-form__privacy ' . $args['privacy_class']);
$submit_classes  = trim('btn btn-primary acquisition-form__submit ' . $args['submit_class']);
$provider_note   = $args['provider_note'] ?: __('Signup is temporarily unavailable while the email connection is restored.', 'brave-hearts');
$feedback        = bhp_get_signup_feedback($form_id);
$preserved       = bhp_get_signup_preserved_values($form_id);
$describedby     = trim($note_id . ($feedback ? ' ' . $status_id : ''));
$reserved_fields = [
    'action',
    'audience_type',
    'lead_magnet',
    'source_page',
    'bhp_context',
    'bhp_email_field',
    'bhp_name_field',
    'bhp_require_name',
    'bhp_form_id',
    'bhp_signup_nonce',
    'bhp_success_redirect_key',
    // 1.19.323 — reserved so a caller's own hidden_fields cannot shadow the
    // form-moment attribution field with a value the pipe would then trust.
    'bhp_attr_now',
    'bhp_website',
    'bhp_segment',
    $email_name,
    $name_name,
];
$success_redirect_key = sanitize_key($args['success_redirect_key']);
?>
<form
  id="<?php echo esc_attr($form_id); ?>"
  class="<?php echo esc_attr($form_classes); ?>"
  action="<?php echo esc_url($form_action); ?>"
  method="post"
  <?php if ($args['aria_labelledby']): ?>aria-labelledby="<?php echo esc_attr($args['aria_labelledby']); ?>"<?php endif; ?>
  aria-describedby="<?php echo esc_attr($describedby); ?>"
  data-bhp-impression-event="lead_form_view"
  data-bhp-lead-offer="<?php echo esc_attr(sanitize_key($args['lead_magnet'])); ?>"
  data-bhp-form-audience="<?php echo esc_attr($audience_type); ?>"
  data-bhp-form-placement="<?php echo esc_attr($context); ?>"
>
  <?php if ($form_ready): ?>
    <input type="hidden" name="action" value="bhp_mailchimp_signup">
    <input type="hidden" name="bhp_context" value="<?php echo esc_attr($context); ?>">
    <input type="hidden" name="bhp_email_field" value="<?php echo esc_attr($email_name); ?>">
    <?php if ($require_name): ?>
      <input type="hidden" name="bhp_name_field" value="<?php echo esc_attr($name_name); ?>">
      <input type="hidden" name="bhp_require_name" value="1">
    <?php endif; ?>
    <input type="hidden" name="bhp_form_id" value="<?php echo esc_attr($form_id); ?>">
    <?php if ($success_redirect_key): ?>
      <input type="hidden" name="bhp_success_redirect_key" value="<?php echo esc_attr($success_redirect_key); ?>">
    <?php endif; ?>
    <?php wp_nonce_field('bhp_mailchimp_signup_' . $form_id, 'bhp_signup_nonce', false); ?>
  <?php endif; ?>

  <input type="hidden" name="audience_type" value="<?php echo esc_attr($audience_type); ?>">
  <input type="hidden" name="lead_magnet" value="<?php echo esc_attr(sanitize_key($args['lead_magnet'])); ?>">
  <input type="hidden" name="source_page" value="<?php echo esc_url($source_page); ?>">

  <?php
  /*
   * ⭐⭐ 1.19.323 (`CYCLE169-LD-R3-IMGCAP-ATTRIBUTION`) — THE FORM-MOMENT
   *    ATTRIBUTION FIELD. Andrew: *"Lets do that right now please."*
   *
   * ⛔⛔ IT IS EMITTED ONLY WHEN THE CURRENT PAGE URL ACTUALLY CARRIES A CLICK
   *     ID OR A UTM. On a clean URL `bhp_get_signup_attribution_field_value()`
   *     returns '' and NO INPUT IS WRITTEN — so the rendered markup of every
   *     form on this site is BYTE-IDENTICAL to 1.19.322 for every ordinary
   *     visitor, and every existing assertion about this template's output
   *     still describes what it emits.
   *
   * ⛔ IT CARRIES NO URL AND NO `landing_page`. The value is a query-string
   *    fragment rebuilt from a fixed whitelist of campaign and click-ID
   *    parameters and nothing else — see `bhp_get_attribution_capture_params()`
   *    in `inc/mailchimp.php`, where the privacy exclusion is stated and
   *    enforced. No PII can reach this field.
   *
   * ⛔ IT IS THE THIRD-CHOICE READING, NOT THE FIRST. The pipe prefers the
   *    referer, which is read fresh at submission and therefore immune to page
   *    caching. This field is the fallback for a browser that strips it.
   *
   * ⛔ NO COOKIE IS WRITTEN AND NO CONSENT POSTURE CHANGES. This is one hidden
   *    input on one form, and it lives only as long as the request.
   */
  $bhp_attr_now = function_exists('bhp_get_signup_attribution_field_value')
      ? bhp_get_signup_attribution_field_value()
      : '';
  ?>
  <?php if ($form_ready && '' !== $bhp_attr_now): ?>
    <input type="hidden" name="bhp_attr_now" value="<?php echo esc_attr($bhp_attr_now); ?>">
  <?php endif; ?>

  <?php foreach ($args['hidden_fields'] as $name => $value): ?>
    <?php if (!in_array($name, $reserved_fields, true)): ?>
      <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="bhp-form-honeypot" aria-hidden="true">
    <label for="<?php echo esc_attr($form_id); ?>-website"><?php esc_html_e('Leave this field blank', 'brave-hearts'); ?></label>
    <input id="<?php echo esc_attr($form_id); ?>-website" name="bhp_website" type="text" tabindex="-1" autocomplete="off" <?php disabled(!$form_ready); ?>>
  </div>

  <?php if ($segment_options): ?>
    <div class="<?php echo esc_attr($field_classes); ?> acquisition-form__field--segment">
      <label for="<?php echo esc_attr($segment_id); ?>"><?php echo esc_html($args['segment_label'] ?: __('I\'m looking for books for...', 'brave-hearts')); ?></label>
      <select id="<?php echo esc_attr($segment_id); ?>" name="bhp_segment" <?php disabled(!$form_ready); ?>>
        <?php foreach ($segment_options as $segment_key => $segment_label): ?>
          <option value="<?php echo esc_attr(sanitize_key($segment_key)); ?>"><?php echo esc_html($segment_label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <?php if ($show_name): ?>
    <div class="<?php echo esc_attr($field_classes); ?>">
      <label for="<?php echo esc_attr($name_id); ?>"><?php echo esc_html($args['name_label'] ?: __('First name', 'brave-hearts')); ?></label>
      <input
        id="<?php echo esc_attr($name_id); ?>"
        name="<?php echo esc_attr($name_name); ?>"
        type="text"
        autocomplete="given-name"
        placeholder="<?php echo esc_attr($args['name_placeholder']); ?>"
        value="<?php echo esc_attr($preserved['name']); ?>"
        <?php disabled(!$form_ready); ?>
        <?php if ($require_name): ?>required aria-required="true"<?php endif; ?>
      >
    </div>
  <?php endif; ?>

  <div class="<?php echo esc_attr($field_classes); ?>">
    <label for="<?php echo esc_attr($email_id); ?>"><?php echo esc_html($args['email_label'] ?: __('Email address', 'brave-hearts')); ?></label>
    <input
      id="<?php echo esc_attr($email_id); ?>"
      name="<?php echo esc_attr($email_name); ?>"
      type="email"
      autocomplete="email"
      placeholder="<?php echo esc_attr($args['email_placeholder']); ?>"
      value="<?php echo esc_attr($preserved['email']); ?>"
      <?php disabled(!$form_ready); ?>
      required
      aria-required="true"
      data-bhp-focus-event="lead_form_start"
      data-bhp-lead-offer="<?php echo esc_attr(sanitize_key($args['lead_magnet'])); ?>"
      data-bhp-form-audience="<?php echo esc_attr($audience_type); ?>"
      data-bhp-form-placement="<?php echo esc_attr($context); ?>"
    >
  </div>

  <button class="<?php echo esc_attr($submit_classes); ?>" type="submit" <?php disabled(!$form_ready); ?> aria-disabled="<?php echo $form_ready ? 'false' : 'true'; ?>">
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
      <?php
      // Phase 1C: signup_error / lead_signup_success analytics. This
      // block is the ONE place every acquisition form's POST/redirect
      // feedback renders, so hooking analytics here covers every
      // placement sitewide without touching each individual template.
      // Forms with a dedicated thank-you page (Adventure Kit, Mariana
      // guide) already fire their own named success event there and
      // are explicitly excluded below to avoid a double-count; this
      // only fires lead_signup_success for forms that show their
      // success message inline (footer, adventure_club, etc.) and
      // never contains the submitted email/name.
      $bhp_status_raw = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
      $bhp_has_dedicated_thank_you = '' !== $success_redirect_key;
      if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics() && $bhp_status_raw) {
          $bhp_event_name = ('success' === $bhp_status_raw)
              ? ($bhp_has_dedicated_thank_you ? '' : 'lead_signup_success')
              : 'signup_error';
          if ($bhp_event_name) {
              $bhp_form_event_payload = wp_json_encode([
                  'event'         => $bhp_event_name,
                  'lead_offer'    => sanitize_key($args['lead_magnet']),
                  'audience'      => $audience_type,
                  'placement'     => $context,
                  'signup_method' => 'form',
                  'error_reason'  => 'success' === $bhp_status_raw ? '' : $bhp_status_raw,
              ]);
              ?>
              <script>
              (function () {
                  var dedupKey = 'bhp_signup_event_fired_<?php echo esc_js($form_id); ?>_<?php echo esc_js($bhp_status_raw); ?>';
                  try {
                      if (sessionStorage.getItem(dedupKey)) {
                          return; // page refresh with the same status query string -- not a new event
                      }
                      sessionStorage.setItem(dedupKey, '1');
                  } catch (e) {
                      // fail safe: still fire once for this load
                  }
                  window.dataLayer = window.dataLayer || [];
                  window.dataLayer.push(<?php echo $bhp_form_event_payload; ?>);
              })();
              </script>
              <?php
          }
      }
      ?>
    <?php endif; ?>
    <?php if ($args['privacy_text']): ?><p class="<?php echo esc_attr($privacy_classes); ?>"><?php echo esc_html($args['privacy_text']); ?></p><?php endif; ?>
    <?php if (!$form_ready): ?><p class="acquisition-form__provider-note" role="status"><?php echo esc_html($provider_note); ?></p><?php endif; ?>
  </div>
</form>
