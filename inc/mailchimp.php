<?php
/**
 * Brave Hearts Publishing — Mailchimp production integration.
 *
 * Routes the theme's custom acquisition forms through the connected MC4WP
 * API without replacing theme markup or exposing Mailchimp errors.
 */

defined('ABSPATH') || exit;

/**
 * Resolve the one Mailchimp audience selected on the existing MC4WP form.
 *
 * Use bhp_mailchimp_form_id when the Adventure Club form is not the first
 * published MC4WP form, or bhp_mailchimp_list_id to provide the audience ID.
 */
function bhp_get_mailchimp_list_id() {
    static $resolved_list_id = null;

    if ($resolved_list_id !== null) {
        return $resolved_list_id;
    }

    $resolved_list_id = '';
    $filtered_list_id = apply_filters('bhp_mailchimp_list_id', '');
    if ($filtered_list_id) {
        $resolved_list_id = preg_replace('/[^a-zA-Z0-9]/', '', (string) $filtered_list_id);
        return $resolved_list_id;
    }

    if (!function_exists('mc4wp_get_form')) {
        return '';
    }

    $form_id = absint(apply_filters('bhp_mailchimp_form_id', 0));

    try {
        $form  = mc4wp_get_form($form_id);
        $lists = $form->get_lists();
    } catch (Throwable $exception) {
        return '';
    }

    if (count($lists) !== 1) {
        return '';
    }

    $resolved_list_id = preg_replace('/[^a-zA-Z0-9]/', '', (string) reset($lists));
    return $resolved_list_id;
}

/**
 * Confirm that MC4WP is connected closely enough to accept theme submissions.
 */
function bhp_mailchimp_signup_is_ready() {
    if (!function_exists('mc4wp_get_api_v3') || !bhp_get_mailchimp_list_id()) {
        return false;
    }

    if (function_exists('mc4wp_get_api_key') && !mc4wp_get_api_key()) {
        return false;
    }

    return true;
}

/**
 * Return the production form action while keeping the existing filter contract.
 */
function bhp_get_signup_form_action($requested_action, $audience_type, $context) {
    $default_action = bhp_mailchimp_signup_is_ready() ? admin_url('admin-post.php') : '';
    $action = apply_filters(
        'bhp_signup_form_action',
        $default_action,
        bhp_normalize_audience_type($audience_type),
        sanitize_key($context),
        $requested_action
    );

    return bhp_get_valid_form_action($action);
}

/**
 * Map theme segmentation fields to Mailchimp audience merge tags.
 */
function bhp_get_mailchimp_merge_field_map() {
    return apply_filters('bhp_mailchimp_merge_field_map', [
        'audience_type' => 'AUDIENCE',
        'lead_magnet'   => 'LEADMAG',
        'source_page'   => 'SOURCE',
    ]);
}

/**
 * Initial production tag plus a future-safe extension point.
 */
function bhp_get_mailchimp_signup_tags($context, $audience_type, $lead_magnet, $source_page) {
    $tags = apply_filters(
        'bhp_mailchimp_signup_tags',
        ['Adventure Club'],
        sanitize_key($context),
        bhp_normalize_audience_type($audience_type),
        sanitize_key($lead_magnet),
        $source_page
    );

    $normalized = [];
    foreach ((array) $tags as $tag) {
        $tag = substr(sanitize_text_field((string) $tag), 0, 100);
        if ($tag !== '') {
            $normalized[$tag] = $tag;
        }
    }

    return array_values($normalized);
}

/**
 * Whitelisted post-signup redirect destinations. Forms never supply a URL —
 * only a short key — so there is no attacker-controlled input anywhere in
 * the redirect decision. Each entry is a published page's path; the actual
 * permalink is always resolved server-side through get_permalink().
 */
function bhp_get_signup_success_redirect_pages() {
    return apply_filters('bhp_signup_success_redirect_pages', [
        'mariana_guide_thank_you' => 'mariana-guide-thank-you',
    ]);
}

/**
 * Resolve a whitelisted redirect key (plus the already-normalized audience
 * type) to a same-site URL, or '' if the key is empty/unknown/unpublished.
 * wp_validate_redirect() is applied as a second, independent check even
 * though every candidate URL already comes from get_permalink().
 */
function bhp_resolve_success_redirect($key, $audience_type) {
    $key = sanitize_key((string) $key);
    if (!$key) {
        return '';
    }

    $pages = bhp_get_signup_success_redirect_pages();
    if (!isset($pages[$key])) {
        return '';
    }

    $page = get_page_by_path(sanitize_title($pages[$key]));
    if (!$page || $page->post_status !== 'publish') {
        return '';
    }

    $url = get_permalink($page);

    if ($key === 'mariana_guide_thank_you') {
        $guide_param = ($audience_type === 'parents_families') ? 'parent' : 'teacher';
        $url = add_query_arg('guide', $guide_param, $url);
    }

    return wp_validate_redirect($url, '');
}

/**
 * Read friendly feedback for one rendered form after a POST/redirect.
 */
function bhp_get_signup_feedback($form_id) {
    $status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
    $target = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';

    if (!$status || !$target || $target !== sanitize_html_class($form_id)) {
        return [];
    }

    $messages = [
        'success' => [
            'type'    => 'success',
            'role'    => 'status',
            'message' => __('You’re in! Welcome to the Adventure Club.', 'brave-hearts'),
        ],
        'invalid' => [
            'type'    => 'error',
            'role'    => 'alert',
            'message' => __('Please enter a valid email address.', 'brave-hearts'),
        ],
        'missing_name' => [
            'type'    => 'error',
            'role'    => 'alert',
            'message' => __('Please enter your first name.', 'brave-hearts'),
        ],
        'unavailable' => [
            'type'    => 'error',
            'role'    => 'alert',
            'message' => __('Signup is temporarily unavailable. Please try again later.', 'brave-hearts'),
        ],
        'error' => [
            'type'    => 'error',
            'role'    => 'alert',
            'message' => __('We couldn’t complete your signup right now. Please try again in a moment.', 'brave-hearts'),
        ],
    ];

    return $messages[$status] ?? [];
}

/**
 * Re-populate a form's own fields after a validation error redirected back
 * to it. Gated by the same form_id match as bhp_get_signup_feedback() so a
 * value never appears on any form other than the one that submitted it.
 */
function bhp_get_signup_preserved_values($form_id) {
    $target = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
    if (!$target || $target !== sanitize_html_class($form_id)) {
        return ['email' => '', 'name' => ''];
    }

    return [
        'email' => isset($_GET['bhp_email']) ? sanitize_email(wp_unslash($_GET['bhp_email'])) : '',
        'name'  => isset($_GET['bhp_name']) ? sanitize_text_field(wp_unslash($_GET['bhp_name'])) : '',
    ];
}

/**
 * Redirect back to the submitting form without exposing provider details.
 *
 * $success_redirect_key is optional and only takes effect on a successful
 * signup, letting a form send visitors to a dedicated thank-you page
 * instead of back to itself. It is always a whitelisted key resolved by
 * bhp_resolve_success_redirect() — never a URL taken from the request.
 * Forms that never set it keep the exact existing behavior.
 */
function bhp_mailchimp_signup_redirect($status, $source_page, $form_id, $success_redirect = '', $preserve = []) {
    if ($status === 'success' && $success_redirect) {
        wp_safe_redirect($success_redirect, 303);
        exit;
    }

    $fallback = wp_get_referer() ?: home_url('/');
    $return_url = wp_validate_redirect(esc_url_raw($source_page), $fallback);
    $return_url = preg_replace('/#.*$/', '', $return_url);
    $return_url = remove_query_arg(['bhp_signup', 'bhp_form', 'bhp_email', 'bhp_name'], $return_url);
    $form_id = sanitize_html_class($form_id);

    $query_args = [
        'bhp_signup' => sanitize_key($status),
        'bhp_form'   => $form_id,
    ];

    if ($status !== 'success') {
        if (!empty($preserve['email'])) {
            $query_args['bhp_email'] = rawurlencode(sanitize_email($preserve['email']));
        }
        if (!empty($preserve['name'])) {
            $query_args['bhp_name'] = rawurlencode(sanitize_text_field($preserve['name']));
        }
    }

    $return_url = add_query_arg($query_args, $return_url);

    if ($form_id) {
        $return_url .= '#' . rawurlencode($form_id . '-status');
    }

    wp_safe_redirect($return_url, 303);
    exit;
}

/**
 * Process all Brave Hearts acquisition forms through the connected MC4WP API.
 */
function bhp_handle_mailchimp_signup() {
    $post = wp_unslash($_POST);

    $form_id = isset($post['bhp_form_id']) ? sanitize_html_class($post['bhp_form_id']) : 'bhp-signup';
    $source_page = isset($post['source_page']) ? esc_url_raw($post['source_page']) : home_url('/');
    $source_page = wp_validate_redirect($source_page, home_url('/'));
    $success_redirect_key = isset($post['bhp_success_redirect_key']) ? sanitize_key($post['bhp_success_redirect_key']) : '';
    $require_name = !empty($post['bhp_require_name']);
    $nonce = isset($post['bhp_signup_nonce']) ? sanitize_text_field($post['bhp_signup_nonce']) : '';

    $email_field = isset($post['bhp_email_field'])
        ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $post['bhp_email_field'])
        : 'email';
    $raw_email = isset($post[$email_field]) ? (string) $post[$email_field] : '';
    $name_field = isset($post['bhp_name_field'])
        ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $post['bhp_name_field'])
        : 'first_name';
    $raw_name = isset($post[$name_field]) ? sanitize_text_field($post[$name_field]) : '';
    $preserve = ['email' => sanitize_email($raw_email), 'name' => $raw_name];

    if (!$nonce || !wp_verify_nonce($nonce, 'bhp_mailchimp_signup_' . $form_id)) {
        bhp_mailchimp_signup_redirect('error', $source_page, $form_id, '', $preserve);
    }

    if (!empty($post['bhp_website'])) {
        bhp_mailchimp_signup_redirect('error', $source_page, $form_id, '', $preserve);
    }

    $email = sanitize_email($raw_email);
    if (!$email || !is_email($email)) {
        bhp_mailchimp_signup_redirect('invalid', $source_page, $form_id, '', $preserve);
    }

    if ($require_name && trim($raw_name) === '') {
        bhp_mailchimp_signup_redirect('missing_name', $source_page, $form_id, '', $preserve);
    }

    if (!bhp_mailchimp_signup_is_ready()) {
        bhp_mailchimp_signup_redirect('unavailable', $source_page, $form_id, '', $preserve);
    }

    $context = isset($post['bhp_context']) ? sanitize_key($post['bhp_context']) : 'adventure_club';
    $audience_type = bhp_normalize_audience_type(
        isset($post['audience_type']) ? sanitize_key($post['audience_type']) : 'general_readers'
    );
    $lead_magnet = isset($post['lead_magnet']) ? sanitize_key($post['lead_magnet']) : '';
    $success_redirect = bhp_resolve_success_redirect($success_redirect_key, $audience_type);

    $field_values = [
        'audience_type' => substr($audience_type, 0, 100),
        'lead_magnet'   => substr($lead_magnet, 0, 100),
        'source_page'   => substr($source_page, 0, 255),
    ];
    $merge_fields = [];

    foreach (bhp_get_mailchimp_merge_field_map() as $field => $merge_tag) {
        $field = sanitize_key($field);
        $merge_tag = substr(preg_replace('/[^A-Z0-9_]/', '', strtoupper((string) $merge_tag)), 0, 10);
        if ($merge_tag && isset($field_values[$field]) && $field_values[$field] !== '') {
            $merge_fields[$merge_tag] = $field_values[$field];
        }
    }

    if ($raw_name !== '') {
        $merge_fields['FNAME'] = substr($raw_name, 0, 100);
    }

    $subscriber_data = [
        'email_address' => $email,
        'status'        => 'subscribed',
        'merge_fields'  => $merge_fields,
    ];

    try {
        $api = mc4wp_get_api_v3();
        $subscriber = $api->add_list_member(
            bhp_get_mailchimp_list_id(),
            $subscriber_data,
            true
        );

        $tags = bhp_get_mailchimp_signup_tags($context, $audience_type, $lead_magnet, $source_page);
        if ($tags) {
            $api->update_list_member_tags(
                bhp_get_mailchimp_list_id(),
                $email,
                [
                    'tags' => array_map(static function ($tag) {
                        return [
                            'name'   => $tag,
                            'status' => 'active',
                        ];
                    }, $tags),
                ]
            );
        }

        do_action(
            'bhp_mailchimp_signup_success',
            $subscriber,
            $email,
            $context,
            $audience_type,
            $lead_magnet,
            $source_page,
            $tags
        );
    } catch (Throwable $exception) {
        do_action(
            'bhp_mailchimp_signup_failed',
            $exception,
            $context,
            $audience_type,
            $lead_magnet,
            $source_page
        );
        bhp_mailchimp_signup_redirect('error', $source_page, $form_id, '', $preserve);
    }

    bhp_mailchimp_signup_redirect('success', $source_page, $form_id, $success_redirect);
}
add_action('admin_post_nopriv_bhp_mailchimp_signup', 'bhp_handle_mailchimp_signup');
add_action('admin_post_bhp_mailchimp_signup', 'bhp_handle_mailchimp_signup');
