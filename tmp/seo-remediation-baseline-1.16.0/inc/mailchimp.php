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
 * Redirect back to the submitting form without exposing provider details.
 */
function bhp_mailchimp_signup_redirect($status, $source_page, $form_id) {
    $fallback = wp_get_referer() ?: home_url('/');
    $return_url = wp_validate_redirect(esc_url_raw($source_page), $fallback);
    $return_url = preg_replace('/#.*$/', '', $return_url);
    $return_url = remove_query_arg(['bhp_signup', 'bhp_form'], $return_url);
    $form_id = sanitize_html_class($form_id);

    $return_url = add_query_arg([
        'bhp_signup' => sanitize_key($status),
        'bhp_form'   => $form_id,
    ], $return_url);

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
    $nonce = isset($post['bhp_signup_nonce']) ? sanitize_text_field($post['bhp_signup_nonce']) : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'bhp_mailchimp_signup_' . $form_id)) {
        bhp_mailchimp_signup_redirect('error', $source_page, $form_id);
    }

    if (!empty($post['bhp_website'])) {
        bhp_mailchimp_signup_redirect('error', $source_page, $form_id);
    }

    $email_field = isset($post['bhp_email_field'])
        ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $post['bhp_email_field'])
        : 'email';
    $email = isset($post[$email_field]) ? sanitize_email($post[$email_field]) : '';

    if (!$email || !is_email($email)) {
        bhp_mailchimp_signup_redirect('invalid', $source_page, $form_id);
    }

    if (!bhp_mailchimp_signup_is_ready()) {
        bhp_mailchimp_signup_redirect('unavailable', $source_page, $form_id);
    }

    $context = isset($post['bhp_context']) ? sanitize_key($post['bhp_context']) : 'adventure_club';
    $audience_type = bhp_normalize_audience_type(
        isset($post['audience_type']) ? sanitize_key($post['audience_type']) : 'general_readers'
    );
    $lead_magnet = isset($post['lead_magnet']) ? sanitize_key($post['lead_magnet']) : '';

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
        bhp_mailchimp_signup_redirect('error', $source_page, $form_id);
    }

    bhp_mailchimp_signup_redirect('success', $source_page, $form_id);
}
add_action('admin_post_nopriv_bhp_mailchimp_signup', 'bhp_handle_mailchimp_signup');
add_action('admin_post_bhp_mailchimp_signup', 'bhp_handle_mailchimp_signup');
