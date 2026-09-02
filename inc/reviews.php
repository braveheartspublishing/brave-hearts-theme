<?php
/**
 * Brave Hearts — native customer review system (1.19.162, 2026-08-03).
 *
 * WHAT THIS SOLVES. Until this file existed there was no way for a reader to
 * write a review anywhere on the site. Verified live on staging 2026-08-03
 * before a line was written:
 *
 *   - `wp option get woocommerce_enable_reviews` = yes, and all three
 *     canonical products have `comment_status = open` — so WooCommerce was
 *     willing to take reviews the whole time.
 *   - The only surface that would have rendered a review form is the native
 *     WooCommerce "Reviews" product tab, and `bhp_hide_empty_reviews_tab()`
 *     in inc/audit-remediation.php removes that tab whenever the review count
 *     is 0. Review count was 0 on every product. The form therefore existed
 *     nowhere on the site.
 *   - The Mailchimp review-ask emails (E3-A / E3-B) deep-link to
 *     `/product/<slug>-paperback/#reviews`. `grep -c 'id="reviews"'` against
 *     the rendered staging product page returns **0**: that anchor does not
 *     exist, so those links land at the top of the page and do nothing.
 *
 * WHAT IT DOES.
 *
 * 1. ONE REVIEW STORE PER TITLE, NOT PER SKU. Every format of a title maps to
 *    that title's canonical product (its paperback — bhp_book_canonical_id(),
 *    the same designation inc/book-formats.php already uses for the canonical
 *    customer-facing page). Reviews therefore accumulate in one place per
 *    book instead of splitting across six SKUs, and the aggregate that Rank
 *    Math reads is the aggregate on the page the customer actually sees.
 *    ⚠ This is also why NO product record has to be modified: the three
 *    hardcover products are `comment_status = closed` on staging, and they
 *    stay that way. Nothing here mutates a WooCommerce product.
 *
 * 2. A REAL FORM ON THE PRODUCT PAGE, below the Kirkus credibility block,
 *    carrying the `#reviews` and `#write-review` anchors the emails already
 *    point at.
 *
 * 3. A DEDICATED `/review/<slug>/` PAGE per title — the two-click email
 *    route. Cover, title, star picker, one text box, submit. noindex.
 *
 * 4. MODERATION IS ENFORCED IN CODE, not left to a site option. Every product
 *    review is held for approval regardless of what wp-admin → Discussion
 *    says, because the claims-integrity rule that reviews are never invented
 *    is worth more than one round trip. See bhp_review_force_moderation().
 *
 * SUBMISSION IS NATIVE. The form posts to `wp-comments-post.php`, exactly as
 * WooCommerce's own review form does. That is deliberate: it means
 * WC_Comments::update_comment_type() still stamps `comment_type = review`,
 * add_comment_rating() still writes the `rating` meta, the purchase
 * verification still writes `verified`, the rating transients still clear,
 * and WordPress still runs flood control, duplicate detection and moderator
 * notification. No parallel review store is created, and nothing about how a
 * review is stored differs from a review left through stock WooCommerce.
 *
 * ⛔ NO SCHEMA IS EMITTED BY THIS FILE. Rating and review structured data
 *    comes from Rank Math reading WooCommerce's own aggregate, which is zero
 *    until a real review is approved. Nothing here fabricates, seeds,
 *    back-fills or defaults a rating.
 */

defined('ABSPATH') || exit;

/**
 * Bumped whenever the rewrite rules below change, so a deploy re-flushes them
 * once and exactly once instead of on every request.
 */
define('BHP_REVIEW_ROUTE_VERSION', '1');

// ============================================================
// REGISTRY — which titles are reviewable, and where reviews live
// ============================================================

/**
 * The clean, human URL segment for each title's standalone review page.
 * Deliberately short and readable: these are pasted into email buttons.
 */
function bhp_review_route_slugs() {
    return apply_filters('bhp_review_route_slugs', [
        'mariana_trench'    => 'the-mariana-trench',
        'mount_everest'     => 'mount-everest',
        'amazon_rainforest' => 'the-amazon',
    ]);
}

/** adventure key -> `/review/<slug>/` URL. Empty string for an unknown key. */
function bhp_review_page_url($key) {
    $slugs = bhp_review_route_slugs();
    return isset($slugs[$key]) ? home_url('/review/' . $slugs[$key] . '/') : '';
}

/** `<slug>` -> adventure key. Empty string for anything not in the registry. */
function bhp_review_key_from_slug($slug) {
    $slug = sanitize_title($slug);
    foreach (bhp_review_route_slugs() as $key => $registered) {
        if ($registered === $slug) {
            return $key;
        }
    }
    return '';
}

/**
 * The product a review for this title is stored against — the title's
 * canonical (paperback) product, which is also the product whose page the
 * customer is on and whose schema Rank Math renders.
 */
function bhp_review_target_id($key) {
    if (!function_exists('bhp_book_canonical_id')) {
        return 0;
    }
    return (int) apply_filters('bhp_review_target_id', bhp_book_canonical_id($key), $key);
}

/**
 * The short display title — "The Mariana Trench", not "Adventures of
 * Charlotte and Henry: The Mariana Trench". Derived from the registry rather
 * than restated, so a title change in one place cannot drift from another.
 */
function bhp_review_book_title($key) {
    $registry = function_exists('bhp_book_registry') ? bhp_book_registry() : [];
    if (!isset($registry[$key]['title'])) {
        return '';
    }
    $full  = $registry[$key]['title'];
    $parts = explode(':', $full, 2);
    return trim(isset($parts[1]) ? $parts[1] : $parts[0]);
}

/** The full title, for the page `<title>` and the schema-free heading. */
function bhp_review_book_full_title($key) {
    $registry = function_exists('bhp_book_registry') ? bhp_book_registry() : [];
    return isset($registry[$key]['title']) ? $registry[$key]['title'] : '';
}

/** "Write a Review for The Mariana Trench" — one wording, used everywhere. */
function bhp_review_invitation_heading($key) {
    /* translators: %s: short book title, e.g. "The Mariana Trench". */
    return sprintf(__('Write a Review for %s', 'brave-hearts'), bhp_review_book_title($key));
}

// ============================================================
// MODERATION — enforced in code, not trusted to a site option
// ============================================================

/**
 * Hold every product review for approval.
 *
 * `comment_moderation` is 0 on staging (verified 2026-08-03), which means
 * WordPress would auto-approve a second review from an address that already
 * has one approved. For blog comments that is a reasonable default. For
 * reviews it is not: an approved review becomes public social proof AND feeds
 * the aggregateRating in structured data, and the company rule that ratings
 * are never fabricated is only as strong as the weakest path by which one can
 * appear. So this holds every one of them and lets Andrew decide.
 *
 * Runs late (999) so it is the final word, and it deliberately does NOT
 * rescue anything an earlier check condemned: a 'spam' or 'trash' verdict, or
 * a WP_Error, is returned untouched. It only ever moves a comment from
 * "approved" to "held", never the other way.
 */
add_filter('pre_comment_approved', 'bhp_review_force_moderation', 999, 2);
function bhp_review_force_moderation($approved, $commentdata) {
    if (is_wp_error($approved) || 'spam' === $approved || 'trash' === $approved) {
        return $approved;
    }
    $post_id = isset($commentdata['comment_post_ID']) ? (int) $commentdata['comment_post_ID'] : 0;
    if ($post_id && 'product' === get_post_type($post_id)) {
        return 0; // 0 = held for moderation.
    }
    return $approved;
}

/**
 * Reject an obvious bot before WordPress ever sees the comment.
 *
 * A hidden field a human never fills in. Cheap, no third-party service, no
 * cookie, no consent implication, and — unlike a nonce — it survives full-page
 * caching, which matters because SiteGround caches the product page and a
 * stale nonce would reject genuine reviews with no visible cause.
 *
 * ⚠ The honeypot is ALSO checked earlier, in bhp_review_intercept_submission()
 *   below, which is what actually fires on a real submission. This filter is
 *   kept as defence in depth for any path that reaches wp_new_comment() without
 *   going through wp-comments-post.php. Both die BARE and deliberately tell the
 *   submitter nothing: a bot that learns why it was rejected is a bot that
 *   adapts. Every OTHER failure mode is handled gracefully — see below.
 */
add_filter('preprocess_comment', 'bhp_review_reject_honeypot', 1);
function bhp_review_reject_honeypot($commentdata) {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- public unauthenticated form; see the docblock above for why a nonce is deliberately not used.
    if (!isset($_POST['bhp_review_context'])) {
        return $commentdata;
    }
    if (!empty($_POST['bhp_review_hp'])) {
        wp_die(
            esc_html__('Sorry, we could not accept that submission. Please try again.', 'brave-hearts'),
            esc_html__('Review not submitted', 'brave-hearts'),
            ['response' => 403, 'back_link' => true]
        );
    }
    // phpcs:enable
    return $commentdata;
}

// ============================================================
// VALIDATION — nobody ever lands on WordPress's bare error page
// ============================================================

/**
 * ⚠ CYCLE142-CX-071 / CYCLE142-CX-072 — the two defects this section exists to
 *   fix, both found in the `commerce-cx` staging QA of 1.19.164 and both real.
 *
 * `-071`: the form carries `novalidate` and NOTHING replaced it, so every
 *   validation failure was a full-page POST that ended on WordPress's
 *   `wp_die()` screen — no header, no footer, no branding, black text on white,
 *   a `« Back` link. Measured live: `document.body.id` = `error-page`,
 *   `document.title` = "Comment Submission Failure". A parent who typed two
 *   sentences about their child and forgot the email field was thrown off the
 *   website onto a page that looks broken.
 *
 * `-072`: the rating was marked required in the markup and enforced NOWHERE.
 *   A review with no rating was accepted and queued. The root cause is precise
 *   and worth recording, because it is not obvious: WooCommerce's own
 *   `WC_Comments::check_comment_rating()` guards on
 *   `isset($_POST['rating']) && empty($_POST['rating'])`. When no star is
 *   selected the browser posts NO `rating` key at all, so `isset()` is false
 *   and WooCommerce's check is skipped entirely. `novalidate` had already
 *   disabled the client-side `required`. Neither half fired.
 *
 * ⛔ `novalidate` IS KEPT, deliberately. The star picker is five radios sized
 *    1×1 and `opacity:0` — that is what makes `input:checked ~ label` able to
 *    colour the stars, and it is the correct accessible technique. Chrome
 *    refuses to show a native validation bubble for a control it cannot
 *    display, and blocks submission with a console warning and NO visible
 *    message instead. That is why `novalidate` was added, and removing it would
 *    trade a bad error page for a form that silently refuses to submit. So the
 *    validation is ours, at both ends:
 *
 *    - CLIENT: assets/js/reviews.js blocks submit, lists the failures in-page,
 *      marks the fields and moves focus. Progressive enhancement only.
 *    - SERVER: bhp_review_intercept_submission() below is the real gate. It
 *      runs with JavaScript off, with a scripted POST, and with a bot.
 *
 * THE DECISION ON A RATING-LESS SUBMISSION, recorded rather than left implicit:
 * it is REJECTED with a message, not silently accepted and not accepted with a
 * null rating. Accepting it produces the contradictory page state `commerce-cx`
 * derived — `bhp_review_count()` ≥ 1 while `bhp_review_average()` is 0.0, so
 * the invitation renders "1 reader review" directly above a section that says
 * "No reader has reviewed this yet". Two contradictory statements about the
 * same book on one page. Rejecting costs the reviewer one click; accepting
 * costs the page its credibility. The rating is only required when
 * `wc_review_ratings_required()` says so, which is the same switch the form
 * reads when it decides whether to print the asterisk — one source of truth.
 *
 * NOTHING HERE WEAKENS THE MODERATION HOLD OR TOUCHES SCHEMA. A review that
 * passes validation is still held by bhp_review_force_moderation(), and no
 * rating markup is emitted by any of this.
 */

/** How long a failed submission's typed content is kept so it can be restored. */
define('BHP_REVIEW_ERROR_TTL', 15 * MINUTE_IN_SECONDS);

/**
 * Is this request a POST of one of THIS theme's review forms?
 *
 * `bhp_review_context` is a hidden field only our two forms carry, so nothing
 * below can affect an ordinary blog comment, a WooCommerce review left through
 * any other surface, or any other POST on the site.
 */
function bhp_review_is_our_submission() {
    if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])))) {
        return false;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public unauthenticated form; see review-form.php for why a nonce is deliberately not used.
    return isset($_POST['bhp_review_context']);
}

/**
 * Is a star rating required for this target?
 *
 * Reads WooCommerce's own setting rather than hardcoding, and returns false for
 * anything that is not a product, so this can never demand a rating on a blog
 * comment that somehow carried our hidden field.
 */
function bhp_review_rating_required_for($post_id) {
    if (!$post_id || 'product' !== get_post_type($post_id)) {
        return false;
    }
    return function_exists('wc_review_ratings_required') ? (bool) wc_review_ratings_required() : true;
}

/** The customer-facing wording for every failure mode. One place. */
function bhp_review_error_messages() {
    return [
        'rating'        => __('Please choose a star rating.', 'brave-hearts'),
        'comment'       => __('Please write a sentence or two about how the book went for your reader.', 'brave-hearts'),
        'author'        => __('Please add your name, so we know who the review is from.', 'brave-hearts'),
        'email'         => __('Please add your email address. It is never published and never added to a mailing list.', 'brave-hearts'),
        'email_invalid' => __('That email address does not look right — please check it.', 'brave-hearts'),
        'duplicate'     => __('It looks as though that exact review has already been sent. Only one copy is needed.', 'brave-hearts'),
        'flood'         => __('That arrived very soon after your last one. Please wait a moment and send it again.', 'brave-hearts'),
        'generic'       => __('Your review could not be sent. Nothing you typed has been lost — please check the form below and try again.', 'brave-hearts'),
    ];
}

/** Which form field each failure belongs to, for the in-page anchors. */
function bhp_review_error_fields() {
    return [
        'rating'        => 'rating',
        'comment'       => 'comment',
        'author'        => 'author',
        'email'         => 'email',
        'email_invalid' => 'email',
    ];
}

/**
 * Send the reviewer back to their own form with the failures named and every
 * word they typed still in the boxes.
 *
 * The typed content travels in a short-lived transient keyed by a random token
 * in the URL, NOT in the query string: a review is long-form text and would not
 * survive a URL, and putting it there would leak it into logs and referrers.
 * The token is single-use in practice (15 minutes) and unguessable.
 *
 * The unique `bhp_rk` also busts SiteGround's full-page cache for this one
 * response, which is why the error state renders at all on a cached page — the
 * same mechanism the existing `?bhp_review=thanks` state already relies on.
 *
 * Always exits.
 */
function bhp_review_bounce_with_errors($errors, $values, $context) {
    $context = 'standalone' === $context ? 'standalone' : 'product';
    $token   = strtolower(wp_generate_password(32, false, false));

    set_transient('bhp_review_err_' . $token, [
        'errors'  => array_values(array_unique((array) $errors)),
        'values'  => (array) $values,
        'context' => $context,
    ], BHP_REVIEW_ERROR_TTL);

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see bhp_review_reject_honeypot().
    $raw    = isset($_POST['bhp_review_return']) ? esc_url_raw(wp_unslash($_POST['bhp_review_return'])) : '';
    $return = wp_validate_redirect($raw, home_url('/'));
    $return = remove_query_arg(['bhp_review', 'bhp_rk', 'unapproved', 'moderation-hash', 'approved'], $return);
    $return = add_query_arg(['bhp_review' => 'error', 'bhp_rk' => $token], $return);

    wp_safe_redirect($return . '#bhp-review-errors-' . $context, 302);
    exit;
}

/**
 * The failed-submission state for THIS page view, or an empty array.
 *
 * Deliberately NOT deleted on read: a reviewer who reloads the page, or who
 * uses the back button after wandering off, should still find their words.
 * The transient expires on its own.
 */
function bhp_review_submission_state() {
    // Cached PER TOKEN rather than once per request: the form is rendered more
    // than once per page view (section + template part both consult it), and
    // one transient read per distinct token is the right amount of work. Keying
    // the cache also means the test suite can exercise more than one state in a
    // single bootstrap instead of being stuck with whichever it read first.
    static $cache = [];

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only presentation state keyed by an unguessable random token; it carries no authority and grants nothing.
    $mode  = isset($_GET['bhp_review']) ? sanitize_key(wp_unslash($_GET['bhp_review'])) : '';
    $token = isset($_GET['bhp_rk']) ? sanitize_key(wp_unslash($_GET['bhp_rk'])) : '';
    // phpcs:enable

    $cache_key = $mode . '|' . $token;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $state = [];
    if ('error' === $mode && $token) {
        $payload = get_transient('bhp_review_err_' . $token);
        if (is_array($payload) && !empty($payload['errors'])) {
            $state = $payload;
        }
    }

    $cache[$cache_key] = $state;
    return $state;
}

/** The failure codes to show on a form rendered in `$context`, if any. */
function bhp_review_errors_for($context) {
    $state = bhp_review_submission_state();
    if (empty($state['errors'])) {
        return [];
    }
    $for = isset($state['context']) ? $state['context'] : 'product';
    return $for === $context ? $state['errors'] : [];
}

/** What the reviewer typed last time, so the form can be refilled. */
function bhp_review_value_for($context, $field, $default = '') {
    $state = bhp_review_submission_state();
    if (empty($state['errors']) || (isset($state['context']) ? $state['context'] : 'product') !== $context) {
        return $default;
    }
    return isset($state['values'][$field]) ? $state['values'][$field] : $default;
}

/**
 * The gate. Runs on `wp_loaded`, which wp-comments-post.php fires (via
 * wp-load.php) BEFORE it calls wp_handle_comment_submission().
 *
 * Running here rather than on `preprocess_comment` is the whole point: WordPress
 * checks name and email inside wp_handle_comment_submission() and returns a
 * WP_Error BEFORE `preprocess_comment` ever fires, so a filter there could never
 * have caught the most common failure. This sees the raw POST first and owns
 * every outcome.
 */
add_action('wp_loaded', 'bhp_review_intercept_submission', 99);
function bhp_review_intercept_submission() {
    if (!bhp_review_is_our_submission()) {
        return;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- public unauthenticated form; see review-form.php.
    $context = 'standalone' === sanitize_key(wp_unslash($_POST['bhp_review_context'])) ? 'standalone' : 'product';

    // 1. Honeypot first, and it still dies bare — a bot is told nothing. This
    //    runs BEFORE the graceful handler is installed, on purpose.
    if (!empty($_POST['bhp_review_hp'])) {
        wp_die(
            esc_html__('Sorry, we could not accept that submission. Please try again.', 'brave-hearts'),
            esc_html__('Review not submitted', 'brave-hearts'),
            ['response' => 403, 'back_link' => true]
        );
    }

    $post_id = isset($_POST['comment_post_ID']) ? absint($_POST['comment_post_ID']) : 0;
    $rating  = isset($_POST['rating']) ? absint($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim((string) wp_unslash($_POST['comment'])) : '';
    $author  = isset($_POST['author']) ? trim((string) wp_unslash($_POST['author'])) : '';
    $email   = isset($_POST['email']) ? trim((string) wp_unslash($_POST['email'])) : '';
    // phpcs:enable

    $values = [
        'rating'  => ($rating >= 1 && $rating <= 5) ? $rating : 0,
        'comment' => $comment,
        'author'  => $author,
        'email'   => $email,
    ];

    $errors = [];

    // CYCLE142-CX-072 — the rating, enforced for real, on the server.
    if (bhp_review_rating_required_for($post_id) && ($rating < 1 || $rating > 5)) {
        $errors[] = 'rating';
    }
    if ('' === $comment) {
        $errors[] = 'comment';
    }
    // Mirrors WordPress's own require_name_email rule rather than second-guessing it.
    if (!is_user_logged_in() && get_option('require_name_email')) {
        if ('' === $author) {
            $errors[] = 'author';
        }
        if ('' === $email) {
            $errors[] = 'email';
        } elseif (!is_email($email)) {
            $errors[] = 'email_invalid';
        }
    }

    if ($errors) {
        bhp_review_bounce_with_errors($errors, $values, $context);
    }

    /*
     * Everything WE check has passed. WordPress can still refuse this — duplicate
     * detection, flood control, a closed post — and each of those ends in the
     * same bare wp_die() screen. Catch them all and bounce them back to the form
     * too, so `-071` is closed for every path and not just the ones we validate.
     */
    $GLOBALS['bhp_review_pending'] = ['context' => $context, 'values' => $values];
    add_action('comment_duplicate_trigger', 'bhp_review_flag_duplicate');
    add_action('comment_flood_trigger', 'bhp_review_flag_flood');
    add_filter('wp_die_handler', 'bhp_review_wp_die_handler', 9999);
}

/** WordPress fires these immediately before its own wp_die(), so we get the reason. */
function bhp_review_flag_duplicate() {
    $GLOBALS['bhp_review_die_code'] = 'duplicate';
}
function bhp_review_flag_flood() {
    $GLOBALS['bhp_review_die_code'] = 'flood';
}

/** Swap WordPress's error screen for our own form, for this request only. */
function bhp_review_wp_die_handler($handler) {
    return 'bhp_review_graceful_die';
}

/**
 * The replacement wp_die() handler. Signature is WordPress's; it must not
 * return, and it does not — bhp_review_bounce_with_errors() exits.
 *
 * WordPress's own $message is deliberately discarded rather than shown. It is
 * written in WordPress's voice ("Duplicate comment detected; it looks as though
 * you've already said that!"), may contain markup, and its exact wording is not
 * a contract. The reason is taken from the two trigger actions instead.
 */
function bhp_review_graceful_die($message, $title = '', $args = []) {
    $pending = isset($GLOBALS['bhp_review_pending']) && is_array($GLOBALS['bhp_review_pending'])
        ? $GLOBALS['bhp_review_pending']
        : ['context' => 'product', 'values' => []];
    $code = isset($GLOBALS['bhp_review_die_code']) ? $GLOBALS['bhp_review_die_code'] : 'generic';

    bhp_review_bounce_with_errors([$code], $pending['values'], $pending['context']);
}

/**
 * Send the reviewer back to the page they came from, in a thank-you state,
 * instead of to WordPress's own "awaiting moderation" comment permalink.
 *
 * The return URL is run through wp_validate_redirect(), so a crafted
 * `bhp_review_return` cannot bounce anyone off-site.
 */
add_filter('comment_post_redirect', 'bhp_review_post_redirect', 10, 2);
function bhp_review_post_redirect($location, $comment) {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- see bhp_review_reject_honeypot().
    if (empty($_POST['bhp_review_return'])) {
        return $location;
    }
    $return = wp_validate_redirect(esc_url_raw(wp_unslash($_POST['bhp_review_return'])), '');
    // phpcs:enable
    if (!$return) {
        return $location;
    }
    // `bhp_rk` is stripped too: a reviewer who failed validation, fixed the form
    // and succeeded must not carry the stale error token into the thanks state.
    $return = remove_query_arg(['bhp_review', 'bhp_rk', 'unapproved', 'moderation-hash', 'approved'], $return);
    $return = add_query_arg('bhp_review', 'thanks', $return);

    return $return . '#bhp-review-thanks';
}

/** Did the visitor just arrive back from a successful submission? */
function bhp_review_just_submitted() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only presentation flag, carries no authority.
    return isset($_GET['bhp_review']) && 'thanks' === sanitize_key(wp_unslash($_GET['bhp_review']));
}

// ============================================================
// READING APPROVED REVIEWS
// ============================================================

/**
 * Approved reviews for a title, newest first. Only ever reads what is already
 * approved — a held review is invisible to the front end by construction.
 */
function bhp_review_get_approved($key, $limit = 10) {
    $target = bhp_review_target_id($key);
    if (!$target) {
        return [];
    }
    $comments = get_comments([
        'post_id'  => $target,
        'status'   => 'approve',
        'type__in' => ['review', 'comment', ''],
        'number'   => (int) $limit,
        'orderby'  => 'comment_date_gmt',
        'order'    => 'DESC',
    ]);
    return is_array($comments) ? $comments : [];
}

/** How many approved reviews this title has, straight from WooCommerce. */
function bhp_review_count($key) {
    $target  = bhp_review_target_id($key);
    $product = $target && function_exists('wc_get_product') ? wc_get_product($target) : null;
    return $product instanceof WC_Product ? (int) $product->get_review_count() : 0;
}

/** The live average, or 0.0 when there is nothing real to average. */
function bhp_review_average($key) {
    $target  = bhp_review_target_id($key);
    $product = $target && function_exists('wc_get_product') ? wc_get_product($target) : null;
    return $product instanceof WC_Product ? (float) $product->get_average_rating() : 0.0;
}

/**
 * Accessible star display. Renders a real, screen-reader-readable sentence
 * alongside the glyphs — the glyphs themselves are aria-hidden decoration.
 * Returns an empty string for a missing or zero rating rather than five empty
 * stars, so "no rating" never looks like "rated zero".
 */
function bhp_review_stars_html($rating) {
    $rating = (int) round((float) $rating);
    if ($rating < 1 || $rating > 5) {
        return '';
    }
    $glyphs = '';
    for ($i = 1; $i <= 5; $i++) {
        $glyphs .= $i <= $rating ? '&#9733;' : '&#9734;';
    }
    /* translators: %d: a star rating from 1 to 5. */
    $label = sprintf(__('Rated %d out of 5 stars', 'brave-hearts'), $rating);

    return '<span class="bhp-review-stars" role="img" aria-label="' . esc_attr($label) . '">'
        . '<span aria-hidden="true">' . $glyphs . '</span></span>';
}

// ============================================================
// RENDERING — the shared section, used by both routes
// ============================================================

/**
 * The whole review block for one title: approved reviews (if any) followed by
 * the write-a-review form.
 *
 * @param string $key     adventure key.
 * @param string $context 'product' (on the PDP) or 'standalone' (/review/…).
 */
function bhp_review_render_section($key, $context = 'product') {
    if (!bhp_review_target_id($key)) {
        return '';
    }
    ob_start();
    get_template_part('template-parts/reviews/review-section', null, [
        'key'     => $key,
        'context' => $context,
    ]);
    return ob_get_clean();
}

/** Just the form, for the standalone page which supplies its own chrome. */
function bhp_review_render_form($key, $context = 'standalone') {
    if (!bhp_review_target_id($key)) {
        return '';
    }
    ob_start();
    get_template_part('template-parts/reviews/review-form', null, [
        'key'     => $key,
        'context' => $context,
    ]);
    return ob_get_clean();
}

// ============================================================
// PRODUCT PAGE PLACEMENT
// ============================================================

/**
 * A single line directly beneath the Kirkus block, inside the purchase
 * column, pointing at the form below.
 *
 * Priority 35: the Kirkus section is 34, so this is literally the next thing
 * rendered after it — which is where Andrew asked for it. The FORM itself is
 * full-width further down for a reason recorded in this repo already: the
 * Amazon review showcase was moved OUT of this same column in an earlier
 * release because review cards inside it "made three-review Mariana cards
 * uncomfortably narrow and compressed and pushed the purchase column well
 * below the fold" (functions.php, bhp_woocommerce_product_amazon_reviews_
 * section). A star picker, a textarea and three fields would do the same
 * thing, only worse. So the invitation sits exactly where it was asked for
 * and the form sits where it works.
 */
function bhp_review_product_invite_link() {
    global $product;
    if (!$product instanceof WC_Product || !function_exists('bhp_get_adventure_key_for_product')) {
        return;
    }
    $key = bhp_get_adventure_key_for_product($product);
    if (!$key || !bhp_review_target_id($key)) {
        return;
    }
    $count = bhp_review_count($key);
    ?>
    <p class="bhp-review-invite">
        <a class="bhp-review-invite__link" href="#write-review">
            <?php echo esc_html(bhp_review_invitation_heading($key)); ?>
            <span class="bhp-review-invite__arrow" aria-hidden="true">&rarr;</span>
        </a>
        <?php if ($count > 0) : ?>
            <span class="bhp-review-invite__count">
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: number of reader reviews. */
                    _n('%s reader review', '%s reader reviews', $count, 'brave-hearts'),
                    number_format_i18n($count)
                ));
                ?>
            </span>
        <?php endif; ?>
    </p>
    <?php
}
add_action('woocommerce_single_product_summary', 'bhp_review_product_invite_link', 35);

/**
 * The full-width review section. `woocommerce_after_single_product_summary`
 * priority 4 — the first thing below the two-column product summary, ahead of
 * the Amazon customer-review showcase (5), the native tabs (10), the
 * collection upsell (15) and related products (20).
 */
function bhp_review_product_section() {
    global $product;
    if (!$product instanceof WC_Product || !function_exists('bhp_get_adventure_key_for_product')) {
        return;
    }
    $key = bhp_get_adventure_key_for_product($product);
    if (!$key) {
        return;
    }
    echo bhp_review_render_section($key, 'product'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the template part.
}
add_action('woocommerce_after_single_product_summary', 'bhp_review_product_section', 4);

/**
 * Remove the native WooCommerce "Reviews" tab outright.
 *
 * inc/audit-remediation.php already removed it while the review count was 0
 * (Fable audit finding #8) and let it return automatically once a real review
 * was approved. That return is now a defect rather than a feature: the tab
 * panel contains its own `<div id="reviews">`, and this file puts `id="reviews"`
 * on the section above — the first approved review would have produced a
 * DUPLICATE `id="reviews"` on the page, and the `#reviews` links in the
 * Mailchimp emails would resolve to whichever the browser found first. It
 * would also show every approved review twice.
 *
 * Priority 99 — after audit-remediation's filter at 98, so the two never
 * disagree about the outcome. The original filter is left exactly as it is.
 */
add_filter('woocommerce_product_tabs', 'bhp_review_remove_native_reviews_tab', 99);
function bhp_review_remove_native_reviews_tab($tabs) {
    unset($tabs['reviews']);
    return $tabs;
}

// ============================================================
// THE `/review/<slug>/` ROUTE — the two-click email destination
// ============================================================

add_action('init', 'bhp_review_register_route');
function bhp_review_register_route() {
    add_rewrite_rule('^review/([^/]+)/?$', 'index.php?bhp_review_book=$matches[1]', 'top');

    // Flush once per route version, so a deploy needs no manual `wp rewrite flush`.
    if (get_option('bhp_review_rewrite_version') !== BHP_REVIEW_ROUTE_VERSION) {
        flush_rewrite_rules(false);
        update_option('bhp_review_rewrite_version', BHP_REVIEW_ROUTE_VERSION);
    }
}

add_filter('query_vars', 'bhp_review_query_vars');
function bhp_review_query_vars($vars) {
    $vars[] = 'bhp_review_book';
    return $vars;
}

/**
 * Which title is this request's review page for? Empty string when the
 * request is not a review page at all, or when the slug is not registered —
 * an unregistered slug is left to 404 normally rather than silently
 * redirected somewhere plausible.
 */
function bhp_review_current_key() {
    $slug = get_query_var('bhp_review_book');
    if (!$slug) {
        return '';
    }
    return bhp_review_key_from_slug($slug);
}

/**
 * An unregistered `/review/<anything>/` becomes an ORDINARY WordPress 404,
 * before the main query is ever built.
 *
 * ⚠ CYCLE142-CX-073, and the history matters because the obvious fix is the
 *   wrong one. The rewrite rule matches `^review/<anything>/?$`, so an
 *   unregistered slug used to fall through to WordPress's default query and
 *   serve the BLOG ARCHIVE with HTTP 200. 1.19.163 fixed the status code by
 *   calling `$wp_query->set_404()` at `template_redirect`, and that worked —
 *   real 404, branded 404 template, no archive posts. But staging QA of
 *   1.19.164 then measured the `<title>` of `/review/not-a-real-book/` as
 *   **"Blog - Brave Hearts Publishing"**: by the time `template_redirect`
 *   fires, the archive query has already run and the title has already been
 *   computed from it. Patching the title afterwards would be a third plaster
 *   on the same wound.
 *
 * So the request is corrected at the `request` filter instead, which runs
 * BEFORE `WP_Query` is built. `WP_Query::parse_query()` treats
 * `error => '404'` by calling `set_404()` itself (verified in
 * wp-includes/class-wp-query.php on the live install, not assumed), so the
 * request becomes indistinguishable from any other unknown URL on the site —
 * same status, same template, same title, same robots. Nothing special to keep
 * in sync.
 */
add_filter('request', 'bhp_review_force_404_for_unknown_slug');
function bhp_review_force_404_for_unknown_slug($vars) {
    if (empty($vars['bhp_review_book'])) {
        return $vars;
    }
    return bhp_review_key_from_slug($vars['bhp_review_book']) ? $vars : ['error' => '404'];
}

/**
 * Status handling for the route.
 *
 * A REGISTERED slug is a real 200, not WordPress's default "no posts" state.
 *
 * The unregistered case is handled entirely by the `request` filter above and
 * can no longer reach this function. The `set_404()` fallback is kept anyway:
 * it costs nothing, and if a future change ever registers the query var by a
 * different route, serving a blog archive at `/review/…` must not be the way we
 * find out.
 */
add_action('template_redirect', 'bhp_review_route_status', 0);
function bhp_review_route_status() {
    $slug = get_query_var('bhp_review_book');
    if (!$slug) {
        return; // Not a review route at all.
    }

    global $wp_query;

    $key = bhp_review_key_from_slug($slug);

    if (!$key) {
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }

    /*
     * ⚠ CYCLE142-CX-075 — one page, one URL.
     *
     * bhp_review_key_from_slug() runs the slug through sanitize_title(), which
     * lowercases, so `/review/THE-MARIANA-TRENCH/` resolved to a valid key and
     * rendered a complete, correct page at 200 — a second live URL for the same
     * page. The page is noindex so the duplicate-content cost was nil, but two
     * URLs for one destination is a thing that gets pasted into an email once
     * and then diverges.
     *
     * A permanent redirect to the canonical form is better than a 404 here: the
     * URL is not wrong, only differently spelled, and a reviewer arriving from
     * an email should land on the form rather than on an error.
     */
    $canonical_slug = bhp_review_route_slugs()[$key];
    if ((string) $slug !== $canonical_slug) {
        $canonical = bhp_review_page_url($key);
        if ($canonical) {
            wp_safe_redirect($canonical, 301);
            exit;
        }
    }

    $wp_query->is_404      = false;
    $wp_query->is_home     = false;
    $wp_query->is_singular = false;
    status_header(200);
}

add_filter('template_include', 'bhp_review_template_include');
function bhp_review_template_include($template) {
    if (!bhp_review_current_key()) {
        return $template;
    }
    $custom = get_theme_file_path('template-parts/reviews/standalone-review-page.php');
    return file_exists($custom) ? $custom : $template;
}

/**
 * The review pages are a private, functional destination reached from an
 * email. They must never compete with the product page in search, and an
 * indexed thin page per title is a real SEO liability. Both filters are set
 * because WordPress writes the robots meta and Rank Math overwrites it.
 */
add_filter('wp_robots', 'bhp_review_robots');
function bhp_review_robots($robots) {
    if (!bhp_review_current_key()) {
        return $robots;
    }
    $robots['noindex']  = true;
    $robots['nofollow'] = true;
    unset($robots['index'], $robots['follow'], $robots['max-image-preview'], $robots['max-snippet'], $robots['max-video-preview']);
    return $robots;
}

add_filter('rank_math/frontend/robots', 'bhp_review_rankmath_robots');
function bhp_review_rankmath_robots($robots) {
    if (!bhp_review_current_key()) {
        return $robots;
    }
    return ['index' => 'noindex', 'follow' => 'nofollow'];
}

/** Keep the review pages out of the sitemap for the same reason. */
add_filter('rank_math/sitemap/exclude_post_type', 'bhp_review_sitemap_noop', 10, 2);
function bhp_review_sitemap_noop($exclude, $type) {
    return $exclude; // The route is not a post type; nothing to exclude. Kept explicit so a future reader does not go looking.
}

add_filter('document_title_parts', 'bhp_review_document_title');
function bhp_review_document_title($parts) {
    $key = bhp_review_current_key();
    if (!$key) {
        return $parts;
    }
    $parts['title'] = bhp_review_invitation_heading($key);
    unset($parts['tagline']);
    return $parts;
}

/**
 * Rank Math writes the `<title>` itself and ignores `document_title_parts`.
 *
 * ⚠ Found in staging QA, not inferred: with only the WordPress filter in
 *   place, `/review/the-mariana-trench/` rendered
 *   `<title>Blog - Brave Hearts Publishing</title>` — the archive title, on a
 *   page with no archive on it. Both filters are needed, for the same reason
 *   both robots filters are.
 */
add_filter('rank_math/frontend/title', 'bhp_review_rankmath_title');
function bhp_review_rankmath_title($title) {
    $key = bhp_review_current_key();
    if (!$key) {
        return $title;
    }
    return bhp_review_invitation_heading($key) . ' | ' . get_bloginfo('name');
}

/**
 * No popup on the review page.
 *
 * ⚠ Also found in staging QA: the sitewide Find Your Adventure quiz rendered
 *   on `/review/<slug>/` and is eligible to auto-open. A modal over a page
 *   whose entire purpose is "type two sentences and press submit" destroys the
 *   two-click promise the page exists to keep — and it is the same category of
 *   harm as a modal over checkout, which `bhp_should_show_any_popup()` already
 *   refuses for exactly that reason.
 *
 * `bhp_show_quiz_cta` is the existing, intended extension point. Suppressing
 * the CTA also suppresses the auto-open (bhp_should_autoopen_quiz() checks
 * bhp_should_show_quiz_cta() first) and stops the quiz assets loading at all.
 * ⛔ Nothing about quiz behaviour anywhere else on the site is changed.
 */
add_filter('bhp_show_quiz_cta', 'bhp_review_suppress_popups');
function bhp_review_suppress_popups($show) {
    return bhp_review_current_key() ? false : $show;
}

// ============================================================
// ASSETS
// ============================================================

add_action('wp_enqueue_scripts', 'bhp_review_enqueue_assets');
function bhp_review_enqueue_assets() {
    $on_review_route  = (bool) bhp_review_current_key();
    $on_product       = function_exists('is_product') && is_product();
    if (!$on_review_route && !$on_product) {
        return;
    }
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style(
        'bhp-reviews',
        get_template_directory_uri() . '/assets/css/reviews.css',
        ['bhp-style'],
        $version
    );
    wp_enqueue_script(
        'bhp-reviews',
        get_template_directory_uri() . '/assets/js/reviews.js',
        [],
        $version,
        true
    );
}
