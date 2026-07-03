<?php
/**
 * Minimal admin settings page for lead-magnet PDF URLs.
 *
 * Lets Andrew paste or pick (via the Media Library) the PDF for each guide
 * without touching code. Landing pages and download CTAs read these values
 * through bhp_get_lead_magnet_pdf_url() and treat an empty value as "not
 * ready yet" the same way the existing Explorer Passport placeholders do.
 */

defined('ABSPATH') || exit;

const BHP_LEAD_MAGNET_PDF_KEYS = ['mariana_teacher', 'mariana_parent'];

function bhp_get_lead_magnet_pdf_url($key) {
    $urls = get_option('bhp_lead_magnet_pdfs', []);
    return isset($urls[$key]) ? esc_url_raw($urls[$key]) : '';
}

/**
 * Only ever accept an https:// URL on this site's own host, or clear the
 * field. This is an admin-only (manage_options) settings screen, but the
 * value it stores is later echoed into public landing pages, so it's held
 * to the same standard as user-facing input: no other scheme, no other
 * host, and never anything that could carry markup or script content.
 */
function bhp_sanitize_lead_magnet_pdfs($input) {
    $clean = [];
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

    foreach (BHP_LEAD_MAGNET_PDF_KEYS as $key) {
        $value = isset($input[$key]) ? trim((string) $input[$key]) : '';
        if ($value === '') {
            $clean[$key] = '';
            continue;
        }

        $value = esc_url_raw($value, ['https']);
        $host = $value ? wp_parse_url($value, PHP_URL_HOST) : '';

        $clean[$key] = ($value && $host && $site_host && $host === $site_host && wp_http_validate_url($value))
            ? $value
            : '';
    }

    return $clean;
}

add_action('admin_menu', function () {
    add_options_page(
        __('Brave Hearts Lead Magnets', 'brave-hearts'),
        __('Lead Magnets', 'brave-hearts'),
        'manage_options',
        'bhp-lead-magnets',
        'bhp_render_lead_magnet_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('bhp_lead_magnets', 'bhp_lead_magnet_pdfs', [
        'type'              => 'array',
        'sanitize_callback' => 'bhp_sanitize_lead_magnet_pdfs',
        'default'           => [],
    ]);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_bhp-lead-magnets') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script(
        'bhp-lead-magnet-settings',
        get_template_directory_uri() . '/assets/js/lead-magnet-settings-admin.js',
        ['jquery'],
        wp_get_theme()->get('Version'),
        true
    );
});

function bhp_render_lead_magnet_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $urls = get_option('bhp_lead_magnet_pdfs', []);
    $fields = [
        'mariana_teacher' => [
            'label' => __('Mariana Trench Classroom Guide (Teacher PDF)', 'brave-hearts'),
            'help'  => __('Used on the teacher/librarian landing page.', 'brave-hearts'),
        ],
        'mariana_parent' => [
            'label' => __('Mariana Trench Parent Guide (PDF)', 'brave-hearts'),
            'help'  => __('Leave blank until the parent guide is ready. The parent landing page stays in a "coming soon" state until this is set.', 'brave-hearts'),
        ],
    ];
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Brave Hearts Lead Magnets', 'brave-hearts'); ?></h1>
      <p><?php esc_html_e('Set the PDF download link for each lead-magnet guide. Pick a file already in the Media Library, or paste a direct URL.', 'brave-hearts'); ?></p>
      <form method="post" action="options.php">
        <?php settings_fields('bhp_lead_magnets'); ?>
        <table class="form-table" role="presentation">
          <?php foreach ($fields as $key => $field): ?>
            <tr>
              <th scope="row"><label for="bhp-pdf-<?php echo esc_attr($key); ?>"><?php echo esc_html($field['label']); ?></label></th>
              <td>
                <input
                  type="url"
                  id="bhp-pdf-<?php echo esc_attr($key); ?>"
                  name="bhp_lead_magnet_pdfs[<?php echo esc_attr($key); ?>]"
                  value="<?php echo esc_attr($urls[$key] ?? ''); ?>"
                  class="regular-text bhp-media-url-field"
                >
                <button type="button" class="button bhp-media-select"><?php esc_html_e('Select from Media Library', 'brave-hearts'); ?></button>
                <p class="description"><?php echo esc_html($field['help']); ?></p>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}
