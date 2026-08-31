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
    $ready = true;

    if (!function_exists('mc4wp_get_api_v3') || !bhp_get_mailchimp_list_id()) {
        $ready = false;
    } elseif (function_exists('mc4wp_get_api_key') && !mc4wp_get_api_key()) {
        $ready = false;
    }

    /*
     * ⭐ 1.19.296 — FIX-2 (interim) from `CYCLE167-LD-CAPTURE-PIPE-DIAGNOSIS` §7.
     *
     * ⛔ THE DEFAULT IS BYTE-EQUIVALENT TO THE PREVIOUS BEHAVIOUR. The three
     *    conditions above are unchanged and still decide `$ready`; nothing
     *    subscribes to this filter on production.
     *
     * ⭐ WHY IT EXISTS: staging has no Mailchimp API key, so this returned
     *    false there, so `bhp_get_signup_form_action()` returned '', so EVERY
     *    signup form on staging rendered `action=""` and posted nowhere. The
     *    email pipe could not be exercised end to end in ANY environment,
     *    which is the diagnosis's actual P0 — it is why a suspected ten-day
     *    outage went unseen for ten days.
     *
     * ⛔ AND YOU CANNOT SIMPLY ADD THE KEY TO STAGING: staging's audience ID is
     *    byte-identical to production's, so a working key on staging would
     *    write test subscribers into the founder's live list. The lever is
     *    therefore a STUB, never a credential — see inc/mailchimp-staging-stub.php,
     *    which is inert on production by construction.
     */
    return (bool) apply_filters('bhp_mailchimp_signup_is_ready', $ready);
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
 *
 * ⭐ 1.19.211 (2026-08-09, `CYCLE148-FIN-002`) — `traffic_source` -> `TRAFFIC`.
 *    Measurement-critical: without it, a signup driven by a paid/UTM-tagged
 *    probe and a signup from the organic sitewide popup arrive in Mailchimp
 *    looking IDENTICAL, because every existing merge field describes the FORM
 *    (`AUDIENCE`, `LEADMAG`) or the PAGE (`SOURCE`) and none of them describes
 *    where the visitor came from before they got there.
 *
 * ⛔ ADDITIVE ONLY. The three existing rows are byte-unchanged and their merge
 *    tags are untouched — a renamed merge tag would orphan every existing
 *    contact's data, which is not a reversible mistake.
 *
 * ⛔ MERGE-TAG LENGTH IS A REAL CONSTRAINT, not a style choice. The sanitiser
 *    in `bhp_process_signup()` truncates every tag to 10 characters, and
 *    Mailchimp itself caps a merge tag at 10. `TRAFFIC` is 7, so it survives
 *    both intact. A longer name would be silently cut and would then never
 *    match the field in the audience.
 */
function bhp_get_mailchimp_merge_field_map() {
    return apply_filters('bhp_mailchimp_merge_field_map', [
        'audience_type' => 'AUDIENCE',
        'lead_magnet'   => 'LEADMAG',
        'source_page'   => 'SOURCE',
        'traffic_source' => 'TRAFFIC',
    ]);
}

/**
 * The visitor's traffic source, as one short string, for the `TRAFFIC` merge
 * field above. `CYCLE148-FIN-002`.
 *
 * ⭐ IT READS THE COOKIES THAT ALREADY EXIST. `bhp_attr_last` / `bhp_attr_first`
 *    are written by `assets/js/bhp-attribution.js` and read back by
 *    `BHP_UTM_Attribution`, which already sanitises, caps and whitelists every
 *    field. This function adds NO new capture, NO new cookie, NO new request
 *    parsing and no second copy of the attribution rules — it formats what the
 *    checkout path has been recording on orders since Phase 1B.
 *
 * ⛔ LAST TOUCH WINS, FIRST TOUCH IS THE FALLBACK. Last touch is the campaign
 *    that produced THIS visit and therefore this signup; first touch answers a
 *    different question and is only used when the last-touch cookie carries no
 *    campaign signal at all (a direct visit deliberately leaves it untouched).
 *
 * ⛔ THREE OUTCOMES, AND THE DIFFERENCE BETWEEN TWO OF THEM MATTERS:
 *      - a campaign signal exists  -> "source / medium / campaign"
 *      - a cookie exists with no campaign signal -> "direct"
 *      - NO cookie exists at all   -> '' and the field is not sent
 *    The third case is NOT "direct". No attribution cookie is written until the
 *    visitor grants analytics consent (asserted by `tests/js/
 *    consent-bridge-harness.js`), so a consent-declining visitor has no cookie
 *    — and recording them as "direct" would be inventing a fact about where
 *    they came from. Unknown stays unknown, and an empty value is dropped by
 *    the loop in `bhp_process_signup()` exactly like an empty lead magnet.
 *
 * ⛔ NO PII, BY CONSTRUCTION. Only the campaign/click identifiers are read.
 *    `landing_page` is deliberately NOT included even though the cookie carries
 *    it: it is a URL that can pick up arbitrary query parameters, and this
 *    value is going to a third-party marketing platform.
 *
 * @return string '' when nothing is known.
 */
function bhp_get_signup_traffic_source($form_moment = null) {
    if (!class_exists('BHP_UTM_Attribution')) {
        return '';
    }

    $attribution = BHP_UTM_Attribution::current_visitor_attribution();
    $last  = isset($attribution['last_touch']) && is_array($attribution['last_touch']) ? $attribution['last_touch'] : [];
    $first = isset($attribution['first_touch']) && is_array($attribution['first_touch']) ? $attribution['first_touch'] : [];

    /*
     * ⭐⭐ 1.19.323 (`CYCLE169-LD-R3-IMGCAP-ATTRIBUTION`) — THE FORM-MOMENT
     *    SOURCE. Andrew: *"Lets do that right now please."*
     *
     * ⛔ NULL MEANS "COLLECT IT YOURSELF"; AN ARRAY MEANS "USE EXACTLY THIS".
     *    The default is null so that EVERY existing caller of
     *    `bhp_process_signup()` — popup, inline forms, the capture band, the
     *    footer/end-of-post block, the quiz's JSON endpoint, and any surface
     *    added later — inherits the new behaviour without a line of its own.
     *    That is the "fix it at the pipe level" requirement expressed in the
     *    signature rather than in a comment. Passing `[]` opts out explicitly,
     *    which is what the tests use to isolate the cookie path.
     */
    if (null === $form_moment) {
        $form_moment = bhp_get_form_moment_attribution();
    }
    $form_moment = is_array($form_moment) ? $form_moment : [];

    if (!$last && !$first && !$form_moment) {
        return '';
    }

    /*
     * ⛔⛔ PRECEDENCE, AND THE ONE ORDERING DECISION IN THIS CHANGE:
     *
     *   1. cookie LAST touch, if it carries a campaign signal
     *   2. cookie FIRST touch, if it carries a campaign signal
     *   3. ⭐ the FORM MOMENT — a click ID or UTM on the page the visitor was
     *      actually standing on when they typed their address
     *   4. "direct" — a cookie exists and no signal anywhere
     *   5. '' — nothing is known, and the merge field is not sent at all
     *
     * ⭐ THE COOKIE STILL WINS, AND IT SHOULD: it carries cross-page history,
     *    so it can say "this visitor arrived from Facebook three pages ago and
     *    signed up here", which a single URL cannot.
     *
     * ⚠️ BUT IT WINS ONLY WHEN IT ACTUALLY SAYS SOMETHING. A consenting
     *    visitor who arrived directly LAST WEEK has a cookie with no campaign
     *    signal; if they come back today on a live `?fbclid=…` click and sign
     *    up, ranking that empty cookie above the click would file a paid
     *    conversion as "direct" — a WRONG fact, not merely a missing one, and
     *    §3's never-invent rule reaches invented attribution the same way it
     *    reaches invented reviews. So the form moment is ranked ABOVE "direct"
     *    and BELOW any real cookie signal. ⛔ RECORDED AS THIS DESK'S CALL, not
     *    the founder's, and flagged in the round-3 report for Gandalf.
     *
     * ⛔ CASE 5 IS STILL NOT "direct", and that distinction is PRESERVED
     *    EXACTLY as 1.19.211 built it. No attribution cookie is written until
     *    the visitor grants analytics consent, so a consent-declining visitor
     *    on a clean URL has neither cookie nor click ID — and calling them
     *    "direct" would invent a fact about where they came from. Unknown
     *    stays unknown and the field is dropped.
     */
    $value = bhp_describe_traffic_source($last);
    if ('' === $value) {
        $value = bhp_describe_traffic_source($first);
    }
    if ('' === $value) {
        $value = bhp_describe_traffic_source($form_moment);
    }
    if ('' === $value && ($last || $first)) {
        // A cookie exists and carries no campaign signal. That IS direct.
        $value = 'direct';
    }
    if ('' === $value) {
        return '';
    }

    return substr(sanitize_text_field($value), 0, 100);
}

/**
 * Format one attribution snapshot as the short `TRAFFIC` string.
 *
 * ⭐ EXTRACTED VERBATIM at 1.19.323 from the closure that lived inside
 *    `bhp_get_signup_traffic_source()`. Not one rule of it changed. It is a
 *    named function now for exactly one reason: the cookie path and the new
 *    form-moment path MUST produce identically-shaped values, and the only way
 *    to guarantee that is for there to be one formatter rather than two that
 *    agree today. A second copy is how "facebook / cpc" and "Facebook/CPC"
 *    end up in the same audience.
 *
 * @param array $touch Whitelisted campaign/click fields. Never PII.
 * @return string '' when the snapshot carries no campaign signal at all.
 */
function bhp_describe_traffic_source(array $touch) {
    $source   = isset($touch['utm_source']) ? trim((string) $touch['utm_source']) : '';
    $medium   = isset($touch['utm_medium']) ? trim((string) $touch['utm_medium']) : '';
    $campaign = isset($touch['utm_campaign']) ? trim((string) $touch['utm_campaign']) : '';

    /*
     * A click ID with no utm_source is still a paid click, and it is the
     * shape an ad platform's auto-tagging produces. Naming the platform is
     * more useful than printing the opaque ID, which is per-click and would
     * make every contact's value unique and un-groupable.
     */
    if ('' === $source) {
        foreach (bhp_get_attribution_click_id_platforms() as $param => $platform) {
            if (!empty($touch[$param])) {
                $source = $platform;
                if ('' === $medium) {
                    $medium = 'cpc';
                }
                break;
            }
        }
    }

    if ('' === $source && '' === $medium && '' === $campaign) {
        return '';
    }

    $parts = [
        '' !== $source ? $source : 'unknown',
        '' !== $medium ? $medium : 'unknown',
    ];
    if ('' !== $campaign) {
        $parts[] = $campaign;
    }

    return implode(' / ', $parts);
}

/**
 * The four click-ID parameters and the platform each one names.
 *
 * One list, filterable, used by BOTH the formatter above and the request
 * parser below — so a fifth ad platform is one row, not three edits.
 */
function bhp_get_attribution_click_id_platforms() {
    return apply_filters('bhp_attribution_click_id_platforms', [
        'gclid'   => 'google',
        'fbclid'  => 'facebook',
        'ttclid'  => 'tiktok',
        'msclkid' => 'microsoft',
    ]);
}

/**
 * Every query parameter the form-moment capture will look at.
 *
 * ⛔⛔ `landing_page` AND `timestamp` ARE DELIBERATELY ABSENT, and their absence
 *     is the privacy posture, not an oversight. The cookie carries
 *     `landing_page`; `bhp_get_signup_traffic_source()` has excluded it from
 *     the Mailchimp payload since 1.19.211 because it is a URL that can pick
 *     up arbitrary query parameters and this value goes to a third-party
 *     marketing platform. ⭐ THE ROUND-3 BRIEF RESTATES THAT EXCLUSION AS A
 *     REQUIREMENT — *"no landing_page forwarding (keep the existing privacy
 *     exclusion)"* — so the new path never even reads it.
 *
 * ⛔ NO PII IS REACHABLE THROUGH THIS LIST BY CONSTRUCTION. Every entry is a
 *    campaign label or an opaque ad-click identifier.
 */
function bhp_get_attribution_capture_params() {
    return apply_filters('bhp_attribution_capture_params', array_merge(
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'],
        array_keys(bhp_get_attribution_click_id_platforms())
    ));
}

/**
 * Pull the whitelisted attribution parameters out of one query string or URL.
 *
 * @param string $query A raw query string, or a full URL.
 * @return array Sanitized, capped, whitelisted. [] when nothing matched.
 */
function bhp_extract_attribution_params($query) {
    $query = (string) $query;
    if ('' === $query) {
        return [];
    }

    if (false !== strpos($query, '://') || 0 === strpos($query, '/')) {
        $query = (string) wp_parse_url($query, PHP_URL_QUERY);
    }
    $query = ltrim($query, '?');
    if ('' === $query) {
        return [];
    }

    $parsed = [];
    wp_parse_str($query, $parsed);
    if (!is_array($parsed)) {
        return [];
    }

    $clean = [];
    foreach (bhp_get_attribution_capture_params() as $key) {
        if (!isset($parsed[$key]) || !is_scalar($parsed[$key])) {
            continue;
        }
        /*
         * Same defence-in-depth shape `BHP_UTM_Attribution::read_cookie()`
         * applies to the cookie: 200-character cap then sanitize_text_field().
         * A form-moment value and a cookie value are therefore directly
         * comparable and neither can carry markup or an oversized string.
         */
        $value = sanitize_text_field(substr((string) $parsed[$key], 0, 200));
        if ('' !== $value) {
            $clean[$key] = $value;
        }
    }

    return $clean;
}

/**
 * ⭐⭐ 1.19.323 — THE FORM MOMENT. The attribution visible on the page the
 *    visitor was standing on when they submitted, read from the LIVE REQUEST
 *    rather than from any stored cookie.
 *
 * ⛔⛔ WHY THIS EXISTS: the `TRAFFIC` merge field reaches Mailchimp only from
 *     consent-gated cookies. `assets/js/bhp-attribution.js` fails CLOSED — no
 *     analytics consent, no cookie, no attribution — so a visitor who declines
 *     or ignores the banner and then signs up arrives with the field blank.
 *     That is the correct privacy behaviour and it is NOT being changed here.
 *     ⭐ This adds a second, consent-independent reading of a click identifier
 *     the ad platform itself put in the URL the visitor is already on.
 *
 * ⛔⛔ WHAT THIS DOES **NOT** DO, stated so it cannot be over-read:
 *     · ⛔ IT WRITES NO COOKIE. Not one, not a session value, not a transient.
 *     · ⛔ IT CHANGES NO CONSENT POSTURE. `bhp-attribution.js` is untouched,
 *          still fail-closed, and the consent banner is not consulted or
 *          altered. Nothing here is persisted beyond this single request.
 *     · ⛔ IT FORWARDS NO `landing_page` AND NO URL. Only the whitelist above.
 *     · ⛔ IT DOES NOT DEMOTE THE COOKIE. See the precedence note on
 *          `bhp_get_signup_traffic_source()`.
 *
 * ⚠️ THREE CANDIDATE READINGS, IN ORDER, AND EACH ONE COVERS A REAL HOLE THE
 *    ONE BEFORE IT LEAVES OPEN. All three are client-supplied, all three go
 *    through the same whitelist and sanitiser, and none can produce a value a
 *    visitor could not already produce by typing a UTM into their own address
 *    bar — so there is no new trust surface, only new coverage:
 *
 *      1. `$_GET` ON THIS REQUEST. Correct whenever the submitting request's
 *         own URL carries the parameters.
 *      2. ⭐ THE REFERER'S QUERY STRING. This is the one that does the work for
 *         a classic form POST: the browser posts to `admin-post.php`, so
 *         `$_GET` is empty, but the referer is the actual page the visitor was
 *         reading — `?fbclid=…` and all. Same-origin navigations send the FULL
 *         URL under the default referrer policy. ⛔ HOST-CHECKED, so an
 *         off-site referer cannot inject anything.
 *      3. THE POSTED HIDDEN FIELD, rendered server-side by
 *         `template-parts/acquisition/signup-form.php`. It is the fallback for
 *         a browser or extension that strips the referer entirely.
 *
 * ⚠️ AND THE HONEST LIMITATION OF READING #3, STATED RATHER THAN HIDDEN: a
 *    hidden field is rendered when the PAGE is rendered, so a full-page cache
 *    could in principle serve one visitor's parameters to the next. That is
 *    why it is ranked LAST — the referer is read fresh on every submission and
 *    wins whenever it exists. It is also why the field is emitted ONLY when
 *    the page URL actually carried something: on a clean URL no field is
 *    written at all, so the cached HTML of a clean page carries nothing to
 *    leak. ⭐ Verified on staging that a page requested with a click ID does
 *    not serve another request's value — evidence in the round-3 record.
 *
 * @return array Whitelisted, sanitized. [] when the live request knows nothing.
 */
function bhp_get_form_moment_attribution() {
    $candidates = [];

    if (!empty($_GET)) {
        $candidates[] = bhp_extract_attribution_params(
            http_build_query(wp_unslash($_GET)) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, whitelisted, never trusted for authorisation.
        );
    }

    $referer = wp_get_raw_referer();
    if ($referer) {
        $referer_host = strtolower((string) wp_parse_url($referer, PHP_URL_HOST));
        $home_host    = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ('' === $referer_host || $referer_host === $home_host) {
            $candidates[] = bhp_extract_attribution_params($referer);
        }
    }

    if (isset($_POST['bhp_attr_now'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- callers verify the nonce before reaching the pipe; this value is whitelisted and never authorises anything.
        $candidates[] = bhp_extract_attribution_params(
            (string) wp_unslash($_POST['bhp_attr_now']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
        );
    }

    foreach ($candidates as $candidate) {
        if ($candidate && '' !== bhp_describe_traffic_source($candidate)) {
            return $candidate;
        }
    }

    return [];
}

/**
 * The value of the hidden field `template-parts/acquisition/signup-form.php`
 * renders. '' when the current page URL carries nothing worth carrying, in
 * which case the template emits NO field at all and the rendered markup of
 * every existing form is byte-identical to 1.19.322 on a clean URL.
 *
 * @return string A query-string fragment, or ''.
 */
function bhp_get_signup_attribution_field_value() {
    $params = empty($_GET)
        ? []
        : bhp_extract_attribution_params(http_build_query(wp_unslash($_GET))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if (!$params || '' === bhp_describe_traffic_source($params)) {
        return '';
    }

    return http_build_query($params);
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
 * Shared signup core (2026-07-30). Extracted verbatim from the body of
 * bhp_handle_mailchimp_signup() so the quiz's JSON endpoint and every
 * existing standalone form run the exact same subscriber logic — there is
 * still only ONE place that talks to Mailchimp.
 *
 * It never redirects and never exits. Callers decide how to present the
 * outcome (303 redirect for classic forms, JSON for the quiz).
 *
 * ⚠️ CORRECTED AT 1.19.323 — THIS DOCBLOCK USED TO CLAIM THE FUNCTION IS
 *    "deliberately pure with respect to the request: it never reads a
 *    superglobal". ⛔ THAT WAS ALREADY FALSE BEFORE THIS RELEASE: since
 *    1.19.211 it has called `bhp_get_signup_traffic_source()`, which reads
 *    `$_COOKIE` through `BHP_UTM_Attribution`. 1.19.323 widens that reading to
 *    the request's own query string and referer. ⭐ THE CLAIM IS CORRECTED
 *    RATHER THAN LEFT STANDING — a comment that describes a property the code
 *    does not have is worse than no comment, because the next person builds on
 *    it. What IS still true, and is the part that was load-bearing: this
 *    function performs NO redirect, NO exit and NO output, so both transports
 *    can call it. Request reading is confined to the one attribution helper,
 *    and any caller may pass `form_attribution` to suppress it entirely.
 *
 * $input keys: email, name, require_name, context, audience_type,
 * lead_magnet, source_page, success_redirect_key, form_attribution (optional).
 *
 * Returns ['ok' => bool, 'code' => string, 'redirect' => string]. `code` is
 * one of success|invalid|missing_name|unavailable|error and is always a
 * generic classifier — provider messages are never surfaced to the browser.
 */
function bhp_process_signup(array $input) {
    $fail = static function ($code) {
        return ['ok' => false, 'code' => $code, 'redirect' => ''];
    };

    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — FIX-1 FROM
     *    `CYCLE167-LD-CAPTURE-PIPE-DIAGNOSIS` §7. THE SILENT-FAILURE HOLE.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⛔ THE DEFECT, IN ONE SENTENCE: the three early returns below happen
     *    BEFORE the `try` block, and `bhp_mailchimp_signup_failed` only ever
     *    fires from that block's `catch`. So `invalid`, `missing_name` and
     *    `unavailable` produced NO lead event, NO failure record, NO log line
     *    — nothing, anywhere.
     *
     * ⛔⛔ WHY THAT IS THE WORST OF THE THREE: `unavailable` is what this
     *    function returns when the Mailchimp API key is missing or empty. If
     *    production's key were ever cleared, rotated badly or allowed to
     *    expire, EVERY SIGNUP ON THE SITE WOULD FAIL WITH ZERO TRACE — the
     *    visitor would see a normal-looking form, and no instrument anywhere
     *    would record that the address was lost. That is not a hypothetical:
     *    staging is in exactly that state today (§5a of the diagnosis), which
     *    is how it stayed invisible long enough to be argued about from three
     *    documents at once.
     *
     * ⭐ THE FIX IS A SIBLING ACTION, NOT A WIDENING OF THE EXISTING ONE.
     *    `bhp_mailchimp_signup_rejected` carries the generic classifier code.
     *    `bhp_mailchimp_signup_failed` is left BYTE-UNCHANGED and still means
     *    exactly what it meant: a provider/API exception. Reusing it would
     *    have merged routine validation rejections into the provider-error
     *    stream and made both unreadable.
     *
     * ⛔⛔ NO EMAIL ADDRESS IS PASSED OR STORED ON THIS PATH, AND THAT IS A
     *    CONDITION OF THE SPEC, NOT AN OVERSIGHT. Andrew has a PARKED DECISION
     *    on failure-path email storage (see the note further down this file).
     *    ⭐ A reason-only row closes the blind spot completely — knowing that
     *    a signup was rejected as `unavailable` at 03:14 is the whole alarm.
     *    ⛔ Storing the address would pre-empt his decision, so it is not done,
     *    and the address is not even handed to the action.
     *
     * ⭐ THE EARLY RETURNS RUN BEFORE THE SANITISED LOCALS EXIST, so context /
     *    audience / lead magnet are read straight from `$input` here and
     *    sanitised on the spot with the same functions used below. They are
     *    operational, non-PII fields.
     */
    $reject = static function ($code) use ($input) {
        do_action(
            'bhp_mailchimp_signup_rejected',
            $code,
            sanitize_key((string) ($input['context'] ?? 'adventure_club')),
            bhp_normalize_audience_type(sanitize_key((string) ($input['audience_type'] ?? 'general_readers'))),
            sanitize_key((string) ($input['lead_magnet'] ?? '')),
            (string) ($input['source_page'] ?? home_url('/'))
        );
        return ['ok' => false, 'code' => $code, 'redirect' => ''];
    };

    $email = sanitize_email((string) ($input['email'] ?? ''));
    if (!$email || !is_email($email)) {
        return $reject('invalid');
    }

    $name = sanitize_text_field((string) ($input['name'] ?? ''));
    if (!empty($input['require_name']) && trim($name) === '') {
        return $reject('missing_name');
    }

    if (!bhp_mailchimp_signup_is_ready()) {
        return $reject('unavailable');
    }

    $context       = sanitize_key((string) ($input['context'] ?? 'adventure_club'));
    $audience_type = bhp_normalize_audience_type(sanitize_key((string) ($input['audience_type'] ?? 'general_readers')));
    $lead_magnet   = sanitize_key((string) ($input['lead_magnet'] ?? ''));
    $source_page   = (string) ($input['source_page'] ?? home_url('/'));
    $success_redirect = bhp_resolve_success_redirect((string) ($input['success_redirect_key'] ?? ''), $audience_type);

    $field_values = [
        'audience_type' => substr($audience_type, 0, 100),
        'lead_magnet'   => substr($lead_magnet, 0, 100),
        'source_page'   => substr($source_page, 0, 255),
        // CYCLE148-FIN-002. '' when unknown, and an empty value is skipped
        // below exactly like an empty lead magnet — see the long note on
        // bhp_get_signup_traffic_source().
        //
        // 1.19.323: `form_attribution` is normally ABSENT, and absent means
        // "collect the form moment from the live request". A caller may pass
        // an explicit array to override it, or [] to suppress it — which is
        // how the tests isolate the cookie path from the URL path.
        'traffic_source' => bhp_get_signup_traffic_source(
            array_key_exists('form_attribution', $input) ? $input['form_attribution'] : null
        ),
    ];
    $merge_fields = [];

    foreach (bhp_get_mailchimp_merge_field_map() as $field => $merge_tag) {
        $field = sanitize_key($field);
        $merge_tag = substr(preg_replace('/[^A-Z0-9_]/', '', strtoupper((string) $merge_tag)), 0, 10);
        if ($merge_tag && isset($field_values[$field]) && $field_values[$field] !== '') {
            $merge_fields[$merge_tag] = $field_values[$field];
        }
    }

    if ($name !== '') {
        $merge_fields['FNAME'] = substr($name, 0, 100);
    }

    /*
     * ⭐ CYCLE148-FIN-002 — THE GRACEFUL NO-OP, BUILT RATHER THAN ASSUMED.
     *
     * The `TRAFFIC` merge field has to be created once, by hand, in the
     * Mailchimp audience console. Until somebody does that, this code is
     * sending a merge tag the audience does not have.
     *
     * ⛔ The tempting version of this comment is "Mailchimp ignores unknown
     *    merge tags." That may well be true — but this session did not test it
     *    against the live API, and a signup is the one path on this site where
     *    being wrong costs a real subscriber. So the safety is STRUCTURAL, not
     *    a belief: if the write fails AND the new field was part of it, drop
     *    that one field and try once more. The visitor's signup can therefore
     *    never fail *because* of the new field, whatever the API does with it.
     *
     * ⛔ EXACTLY ONE RETRY, and only when the new field was actually present.
     *    A genuine outage still fails, still reaches the existing
     *    `bhp_mailchimp_signup_failed` action and still returns 'error' — this
     *    must not turn a broken integration into a silent one.
     */
    $optional_merge_tags = ['TRAFFIC'];

    try {
        /*
         * ⭐ 1.19.296 — FIX-2 (interim): THE ONE INJECTION POINT.
         *
         * ⛔ DEFAULT PATH UNCHANGED. No subscriber to this filter means
         *    `mc4wp_get_api_v3()` exactly as before — production is untouched
         *    and takes the identical call it has always taken.
         *
         * ⭐ A subscriber (only inc/mailchimp-staging-stub.php, only on
         *    staging) may return an object exposing `add_list_member()` and
         *    `update_list_member_tags()` that RECORDS the payload instead of
         *    transmitting it. That makes form -> admin-post.php -> handler ->
         *    payload assertable, which is ~90% of the pipe at 0% of the risk
         *    of writing a real subscriber into the founder's live audience.
         */
        $api = apply_filters('bhp_mailchimp_api_transport', null);
        if (!is_object($api)) {
            $api = mc4wp_get_api_v3();
        }
        try {
            $subscriber = $api->add_list_member(
                bhp_get_mailchimp_list_id(),
                [
                    'email_address' => $email,
                    'status'        => 'subscribed',
                    'merge_fields'  => $merge_fields,
                ],
                true
            );
        } catch (Throwable $merge_exception) {
            $reduced = array_diff_key($merge_fields, array_flip($optional_merge_tags));
            if ($reduced === $merge_fields) {
                throw $merge_exception; // Nothing optional was in play; a real failure.
            }
            do_action('bhp_mailchimp_optional_merge_field_dropped', $merge_exception, array_keys(array_diff_key($merge_fields, $reduced)));
            $merge_fields = $reduced;
            $subscriber = $api->add_list_member(
                bhp_get_mailchimp_list_id(),
                [
                    'email_address' => $email,
                    'status'        => 'subscribed',
                    'merge_fields'  => $merge_fields,
                ],
                true
            );
        }

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
        return $fail('error');
    }

    /*
     * ═══════════════════════════════════════════════════════════════════
     * ⭐ 1.19.292 (2026-08-26, `CYCLE166-CX-CAPTURE-REPAIR`) — THE SUCCESS
     *    URL LEAVES HERE CARRYING A SINGLE-USE CONVERSION TOKEN.
     * ═══════════════════════════════════════════════════════════════════
     *
     * ⛔ THE PLACEMENT IS THE POINT, AND IT IS DELIBERATELY *HERE* RATHER
     *    THAN AT LINE ~427 WHERE `$success_redirect` IS RESOLVED. That
     *    resolution happens BEFORE the Mailchimp subscribe and tag write,
     *    and it happens for failures too — minting there would hand a valid
     *    conversion token to a signup that then threw and returned
     *    `fail('error')`. This is the only statement in the function that
     *    is reached exactly when a real subscriber really landed.
     *
     * ⭐ BOTH SIGNUP TRANSPORTS INHERIT IT FROM THIS ONE LINE. The classic
     *    303 form POST passes `$result['redirect']` to
     *    `bhp_mailchimp_signup_redirect()`; the quiz/modal JSON endpoint
     *    returns `$result['redirect']` to the browser. Neither needed a
     *    change, and a future signup surface that calls
     *    `bhp_process_signup()` gets the gate for free rather than having
     *    to remember it.
     *
     * ⛔ NO PII CROSSES. `$email` and `$name` are in scope right here and
     *    are deliberately NOT passed — the token describes WHICH OFFER
     *    converted, never WHO. It also keeps this subsystem entirely clear
     *    of Andrew's parked failure-path email-storage decision.
     *
     * ⚠️ DEGRADES TOWARDS THE CUSTOMER, NEVER AGAINST THEM. If the token
     *    cannot be minted (transient storage unavailable),
     *    `bhp_add_conversion_token()` returns the URL untouched: the visitor
     *    still reaches their thank-you page and still gets their kit, and
     *    all that is lost is one analytics event. An undercount is the
     *    acceptable failure here; a broken redirect is not.
     */
    $success_redirect = bhp_add_conversion_token($success_redirect, [
        'lead_magnet'   => $lead_magnet,
        'audience'      => $audience_type,
        'signup_method' => $context === 'audience_quiz' ? 'quiz' : 'form',
    ]);

    return ['ok' => true, 'code' => 'success', 'redirect' => $success_redirect];
}

/**
 * Process all Brave Hearts acquisition forms through the connected MC4WP API.
 *
 * Thin request wrapper around bhp_process_signup() since 2026-07-30 —
 * request parsing, nonce, honeypot and redirect behaviour are unchanged.
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

    /*
     * Wave 1 (2026-08-04, theme 1.19.168) — SEGMENT SELECTOR.
     *
     * A capture form may carry one `bhp_segment` key (the footer capture
     * block does). The browser sends the KEY ONLY; it is resolved here
     * against the fixed server-side whitelist in
     * bhp_get_capture_segment_routes(), exactly like the quiz's route map.
     * An empty or unknown key changes nothing and the form's own declared
     * audience_type stands.
     *
     * ⛔ The segment can change the AUDIENCE. It cannot change the lead
     *    magnet, the redirect, or which funnel's storage/analytics
     *    namespace anything belongs to — none of those is derived from it.
     */
    $audience_type = isset($post['audience_type']) ? $post['audience_type'] : 'general_readers';
    if (function_exists('bhp_resolve_capture_segment')) {
        $segment_key = bhp_resolve_capture_segment(isset($post['bhp_segment']) ? $post['bhp_segment'] : '');
        if ($segment_key) {
            $routes = bhp_get_capture_segment_routes();
            $audience_type = $routes[$segment_key]['audience_type'];
        }
    }

    $result = bhp_process_signup([
        'email'                => $raw_email,
        'name'                 => $raw_name,
        'require_name'         => $require_name,
        'context'              => isset($post['bhp_context']) ? $post['bhp_context'] : 'adventure_club',
        'audience_type'        => $audience_type,
        'lead_magnet'          => isset($post['lead_magnet']) ? $post['lead_magnet'] : '',
        'source_page'          => $source_page,
        'success_redirect_key' => $success_redirect_key,
    ]);

    if (!$result['ok']) {
        bhp_mailchimp_signup_redirect($result['code'], $source_page, $form_id, '', $preserve);
    }

    bhp_mailchimp_signup_redirect('success', $source_page, $form_id, $result['redirect']);
}
add_action('admin_post_nopriv_bhp_mailchimp_signup', 'bhp_handle_mailchimp_signup');
add_action('admin_post_bhp_mailchimp_signup', 'bhp_handle_mailchimp_signup');

// ============================================================
// QUIZ INLINE SIGNUP — same-origin JSON endpoint (2026-07-30)
// ============================================================
/**
 * Fixed, server-side map from a quiz result key to everything the signup
 * needs. The browser only ever sends the short key — never a tag, never a
 * lead-magnet key, never a URL — so nothing about the audience, the tags
 * applied, or the redirect destination is attacker-controlled.
 *
 * `lead_magnet` values are the lead-magnet REGISTRY keys, so the existing
 * bhp_mailchimp_signup_tags filters in functions.php produce the already
 * live-verified tag sets. No tag strings are duplicated here.
 *
 * The organization "partnership" answer is deliberately absent: it promises
 * no resource, so it has no signup and cannot reach this endpoint.
 */
function bhp_get_quiz_signup_routes() {
    return apply_filters('bhp_quiz_signup_routes', [
        'parent' => [
            'audience_type' => 'parents_families',
            'lead_magnet'   => 'reluctant_reader_adventure_kit',
            'redirect_key'  => 'quiz_parent_kit',
        ],
        'educator' => [
            'audience_type' => 'educators',
            'lead_magnet'   => 'teacher_adventure_toolkit',
            'redirect_key'  => 'quiz_educator_toolkit',
        ],
        'gift' => [
            'audience_type' => 'gift_buyers',
            'lead_magnet'   => 'meaningful_gift_guide',
            'redirect_key'  => 'quiz_gift_guide',
        ],
        'organization' => [
            'audience_type' => 'organizations',
            'lead_magnet'   => 'community_reading_kit',
            'redirect_key'  => 'quiz_community_kit',
        ],
    ]);
}

/**
 * Rate limit the public JSON endpoint. The theme had no rate limiting of
 * any kind before this, and an always-on JSON route is materially easier to
 * abuse than a form POST, so this is new protection rather than something
 * carried over. The IP is hashed with wp_salt() and only ever used as a
 * transient key — the raw address is never stored or logged.
 */
function bhp_quiz_signup_rate_limited() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    if ($ip === '') {
        return false;
    }

    $key = 'bhp_qz_' . substr(hash_hmac('sha256', $ip, wp_salt('nonce')), 0, 24);
    $hits = (int) get_transient($key);
    $max = (int) apply_filters('bhp_quiz_signup_rate_limit', 8);

    if ($hits >= $max) {
        return true;
    }

    set_transient($key, $hits + 1, 10 * MINUTE_IN_SECONDS);
    return false;
}

/**
 * Quiz result signup. Returns JSON only; never redirects, and deliberately
 * never calls bhp_mailchimp_signup_redirect() — that helper puts the email
 * and first name into a query string to repopulate classic forms, which
 * would violate the rule that no personal data appears in a URL. The quiz
 * preserves the visitor's entries in the live DOM instead.
 */
function bhp_handle_quiz_signup_ajax() {
    $post = wp_unslash($_POST);

    $nonce = isset($post['nonce']) ? sanitize_text_field($post['nonce']) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'bhp_quiz_signup')) {
        wp_send_json(['ok' => false, 'code' => 'error'], 403);
    }

    // Honeypot: same field name and semantics as the classic forms.
    if (!empty($post['bhp_website'])) {
        wp_send_json(['ok' => false, 'code' => 'error'], 400);
    }

    if (bhp_quiz_signup_rate_limited()) {
        wp_send_json(['ok' => false, 'code' => 'rate_limited'], 429);
    }

    $route_key = isset($post['route']) ? sanitize_key($post['route']) : '';
    $routes = bhp_get_quiz_signup_routes();
    if (!isset($routes[$route_key])) {
        wp_send_json(['ok' => false, 'code' => 'error'], 400);
    }
    $route = $routes[$route_key];

    $result = bhp_process_signup([
        'email'                => isset($post['email']) ? $post['email'] : '',
        'name'                 => isset($post['first_name']) ? $post['first_name'] : '',
        'require_name'         => false, // First name is deliberately optional.
        'context'              => 'audience_quiz',
        'audience_type'        => $route['audience_type'],
        'lead_magnet'          => $route['lead_magnet'],
        'source_page'          => home_url('/'),
        'success_redirect_key' => $route['redirect_key'],
    ]);

    if (!$result['ok']) {
        wp_send_json(['ok' => false, 'code' => $result['code']], 200);
    }

    // Only redirect once Mailchimp has accepted BOTH the subscriber and the
    // tag write. Delivery itself is asynchronous and is not waited on.
    wp_send_json([
        'ok'       => true,
        'redirect' => $result['redirect'] ?: home_url('/'),
    ], 200);
}
add_action('wp_ajax_nopriv_bhp_quiz_signup', 'bhp_handle_quiz_signup_ajax');
add_action('wp_ajax_bhp_quiz_signup', 'bhp_handle_quiz_signup_ajax');
