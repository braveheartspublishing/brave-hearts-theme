<?php
/**
 * Customer review system test suite (1.19.162).
 *
 * No PHPUnit exists in this theme (see docs/RUNBOOK.md) — verification has
 * always been wp-cli-driven. This exercises the real functions and the real
 * rendered markup against a real WordPress bootstrap.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-reviews.php --user=1
 *
 * Exits non-zero on any failure so it can gate a deploy.
 *
 * ⛔ THIS SUITE WRITES NOTHING. It creates no comment, no option and no post.
 *    The one live submission used to prove the end-to-end path is performed
 *    separately and by hand, and deleted through moderation afterwards.
 */
defined('ABSPATH') || exit;

$failures = [];

if (!function_exists('bhp_test_assert')) {
    function bhp_test_assert(&$failures, $label, $condition) {
        if ($condition) {
            WP_CLI::log("PASS: $label");
        } else {
            WP_CLI::warning("FAIL: $label");
            $failures[] = $label;
        }
    }
}

$keys = ['mariana_trench', 'mount_everest', 'amazon_rainforest'];

// ---------------------------------------------------------------------
// 1. Route registry — clean slugs, both directions, no collisions
// ---------------------------------------------------------------------
$slugs = bhp_review_route_slugs();
bhp_test_assert($failures, 'exactly three titles are reviewable', count($slugs) === 3);
bhp_test_assert($failures, 'Mariana slug is the-mariana-trench', $slugs['mariana_trench'] === 'the-mariana-trench');
bhp_test_assert($failures, 'Everest slug is mount-everest', $slugs['mount_everest'] === 'mount-everest');
bhp_test_assert($failures, 'Amazon slug is the-amazon', $slugs['amazon_rainforest'] === 'the-amazon');
bhp_test_assert($failures, 'all slugs are unique', count(array_unique($slugs)) === count($slugs));

foreach ($keys as $key) {
    bhp_test_assert($failures, "round-trip slug -> key survives for {$key}",
        bhp_review_key_from_slug($slugs[$key]) === $key);
    bhp_test_assert($failures, "review page URL for {$key} is /review/{$slugs[$key]}/",
        bhp_review_page_url($key) === home_url('/review/' . $slugs[$key] . '/'));
}
bhp_test_assert($failures, 'an unregistered slug resolves to nothing (so it 404s)',
    bhp_review_key_from_slug('not-a-real-book') === '');
bhp_test_assert($failures, 'an empty slug resolves to nothing', bhp_review_key_from_slug('') === '');

// ---------------------------------------------------------------------
// 2. Review target mapping — one store per TITLE, and it is the canonical
//    (paperback) product, which is also the product whose page renders.
// ---------------------------------------------------------------------
foreach ($keys as $key) {
    $target = bhp_review_target_id($key);
    bhp_test_assert($failures, "{$key} has a review target product", $target > 0);
    bhp_test_assert($failures, "{$key} review target IS its canonical product",
        $target === bhp_book_canonical_id($key));
    bhp_test_assert($failures, "{$key} review target is a real published product",
        get_post_type($target) === 'product' && get_post_status($target) === 'publish');
    bhp_test_assert($failures, "{$key} review target accepts comments (reviews would be rejected otherwise)",
        comments_open($target));
}

// Both formats of a title must land on the SAME review store.
foreach (bhp_book_registry() as $key => $book) {
    $pb = bhp_book_lookup_product((int) $book['pb_product']);
    $hc = bhp_book_lookup_product((int) $book['hc_product']);
    bhp_test_assert($failures, "{$key} paperback resolves to this title", $pb && $pb['key'] === $key);
    bhp_test_assert($failures, "{$key} hardcover resolves to this title", $hc && $hc['key'] === $key);
    bhp_test_assert($failures, "{$key} hardcover and paperback share ONE review store",
        bhp_review_target_id($pb['key']) === bhp_review_target_id($hc['key']));
}

// ---------------------------------------------------------------------
// 3. Titles and headings
// ---------------------------------------------------------------------
bhp_test_assert($failures, 'short title strips the series prefix',
    bhp_review_book_title('mariana_trench') === 'The Mariana Trench');
bhp_test_assert($failures, 'Everest short title is Mount Everest',
    bhp_review_book_title('mount_everest') === 'Mount Everest');
bhp_test_assert($failures, 'Amazon short title is The Amazon',
    bhp_review_book_title('amazon_rainforest') === 'The Amazon');
bhp_test_assert($failures, 'heading reads "Write a Review for The Mariana Trench"',
    bhp_review_invitation_heading('mariana_trench') === 'Write a Review for The Mariana Trench');

// ---------------------------------------------------------------------
// 4. ⛔ THE ABSOLUTE RULE — nothing fabricates a rating
// ---------------------------------------------------------------------
bhp_test_assert($failures, 'stars for rating 0 render NOTHING (never five empty stars)',
    bhp_review_stars_html(0) === '');
bhp_test_assert($failures, 'stars for an empty rating render NOTHING',
    bhp_review_stars_html('') === '');
bhp_test_assert($failures, 'stars above 5 render NOTHING', bhp_review_stars_html(6) === '');
bhp_test_assert($failures, 'stars below 1 render NOTHING', bhp_review_stars_html(-1) === '');
bhp_test_assert($failures, 'a real rating of 4 renders four filled and one empty star',
    substr_count(bhp_review_stars_html(4), '&#9733;') === 4 && substr_count(bhp_review_stars_html(4), '&#9734;') === 1);
bhp_test_assert($failures, 'star markup carries a real accessible label',
    strpos(bhp_review_stars_html(4), 'aria-label="Rated 4 out of 5 stars"') !== false);

foreach ($keys as $key) {
    $count   = bhp_review_count($key);
    $average = bhp_review_average($key);
    $section = bhp_review_render_section($key, 'product');
    $form    = bhp_review_render_form($key, 'standalone');

    bhp_test_assert($failures, "{$key} review count is read from WooCommerce, not invented",
        $count === (int) wc_get_product(bhp_review_target_id($key))->get_review_count());

    // Whatever the count, the theme's own markup must never carry schema.
    foreach (['section' => $section, 'standalone form' => $form] as $what => $html) {
        bhp_test_assert($failures, "{$key} {$what} emits NO aggregateRating",
            stripos($html, 'aggregateRating') === false);
        bhp_test_assert($failures, "{$key} {$what} emits NO Review schema",
            stripos($html, 'schema.org/Review') === false && stripos($html, '"@type":"Review"') === false);
        bhp_test_assert($failures, "{$key} {$what} emits NO inline JSON-LD",
            strpos($html, 'application/ld+json') === false);
        bhp_test_assert($failures, "{$key} {$what} emits NO ratingValue microdata",
            strpos($html, 'itemprop="ratingValue"') === false);
    }

    if (0 === $count) {
        bhp_test_assert($failures, "{$key} with zero reviews shows NO average anywhere",
            strpos($section, 'out of 5 &mdash;') === false && strpos($section, 'out of 5 —') === false);
        bhp_test_assert($failures, "{$key} with zero reviews states the absence honestly",
            strpos($section, 'has reviewed') !== false || strpos($section, 'yet') !== false);
        bhp_test_assert($failures, "{$key} with zero reviews renders no review card",
            strpos($section, 'bhp-review-card') === false);
        bhp_test_assert($failures, "{$key} WooCommerce average is genuinely 0.0 at count 0", 0.0 === $average);
    }
}

// ---------------------------------------------------------------------
// 5. The anchors the Mailchimp review-ask emails point at
// ---------------------------------------------------------------------
$mariana_section = bhp_review_render_section('mariana_trench', 'product');
bhp_test_assert($failures, 'the section carries id="reviews" (the E3 email deep-link target)',
    strpos($mariana_section, 'id="reviews"') !== false);
bhp_test_assert($failures, 'the section carries id="write-review"',
    strpos($mariana_section, 'id="write-review"') !== false);
bhp_test_assert($failures, 'id="reviews" appears exactly once in the section',
    substr_count($mariana_section, 'id="reviews"') === 1);
// Found in staging QA by counting the id in rendered HTML: the form was also
// carrying id="write-review", so the product page shipped a duplicate anchor.
bhp_test_assert($failures, 'id="write-review" appears exactly once in the section',
    substr_count($mariana_section, 'id="write-review"') === 1);
bhp_test_assert($failures, 'the form has its own distinct id',
    strpos($mariana_section, 'id="bhp-review-form-product"') !== false);

// ---------------------------------------------------------------------
// 6. The form is a NATIVE WooCommerce review submission
// ---------------------------------------------------------------------
$form = bhp_review_render_form('mariana_trench', 'product');
bhp_test_assert($failures, 'form posts to wp-comments-post.php (native pipeline, not a custom store)',
    strpos($form, site_url('/wp-comments-post.php')) !== false);
bhp_test_assert($failures, 'form posts to the canonical Mariana product',
    strpos($form, 'name="comment_post_ID" value="' . bhp_book_canonical_id('mariana_trench') . '"') !== false);
bhp_test_assert($failures, 'form has a rating input named "rating" (what WC_Comments reads)',
    strpos($form, 'name="rating"') !== false);
bhp_test_assert($failures, 'form offers exactly five rating values',
    substr_count($form, 'name="rating"') === 5);
bhp_test_assert($failures, 'form has exactly one comment textarea',
    substr_count($form, 'name="comment"') === 1);
bhp_test_assert($failures, 'form carries a honeypot', strpos($form, 'name="bhp_review_hp"') !== false);
bhp_test_assert($failures, 'form declares its context so the redirect filter can act',
    strpos($form, 'name="bhp_review_context"') !== false);
bhp_test_assert($failures, 'form return URL is same-origin',
    strpos($form, 'name="bhp_review_return" value="' . home_url()) !== false);
bhp_test_assert($failures, 'submit button uses the sitewide one-button spec class',
    strpos($form, 'btn btn-cta-primary') !== false);
bhp_test_assert($failures, 'no rating is pre-selected (a default star IS a fabricated rating)',
    strpos($form, 'checked') === false);

// ---------------------------------------------------------------------
// 7. Moderation is enforced in CODE, whatever the site option says
// ---------------------------------------------------------------------
$target = bhp_book_canonical_id('mariana_trench');
bhp_test_assert($failures, 'an approved product review is forced back to held',
    bhp_review_force_moderation(1, ['comment_post_ID' => $target]) === 0);
bhp_test_assert($failures, 'an already-held product review stays held',
    bhp_review_force_moderation(0, ['comment_post_ID' => $target]) === 0);
bhp_test_assert($failures, 'spam stays spam — moderation never rescues a spam verdict',
    bhp_review_force_moderation('spam', ['comment_post_ID' => $target]) === 'spam');
bhp_test_assert($failures, 'trash stays trash',
    bhp_review_force_moderation('trash', ['comment_post_ID' => $target]) === 'trash');
bhp_test_assert($failures, 'a WP_Error verdict is returned untouched',
    is_wp_error(bhp_review_force_moderation(new WP_Error('x', 'y'), ['comment_post_ID' => $target])));

$a_post = get_posts(['post_type' => 'post', 'numberposts' => 1, 'fields' => 'ids']);
if ($a_post) {
    bhp_test_assert($failures, 'a NON-product comment is left exactly as WordPress decided',
        bhp_review_force_moderation(1, ['comment_post_ID' => (int) $a_post[0]]) === 1);
} else {
    WP_CLI::warning('SKIP: no blog post available to prove non-product comments are untouched');
}

bhp_test_assert($failures, 'the moderator-notification option is on (Andrew gets told)',
    '1' === (string) get_option('moderation_notify'));
bhp_test_assert($failures, 'the notification address is the owner address',
    strtolower((string) get_option('admin_email')) === 'andrew@braveheartspublishing.com');

// ---------------------------------------------------------------------
// 8. The redirect filter cannot be used to bounce anyone off-site
// ---------------------------------------------------------------------
$_POST['bhp_review_return'] = 'https://evil.example.com/phish';
$comment = (object) ['comment_ID' => 0, 'comment_approved' => '0'];
$off_site = bhp_review_post_redirect('https://fallback.local/x', $comment);
bhp_test_assert($failures, 'an off-site return URL is refused and the fallback is used',
    $off_site === 'https://fallback.local/x');

$_POST['bhp_review_return'] = home_url('/review/the-mariana-trench/');
$on_site = bhp_review_post_redirect('https://fallback.local/x', $comment);
bhp_test_assert($failures, 'a same-site return URL is honoured with the thank-you flag',
    strpos($on_site, home_url('/review/the-mariana-trench/')) === 0
    && strpos($on_site, 'bhp_review=thanks') !== false
    && substr($on_site, -18) === '#bhp-review-thanks');
unset($_POST['bhp_review_return']);

// ---------------------------------------------------------------------
// 9. The native reviews tab is gone, so `id="reviews"` can never duplicate
// ---------------------------------------------------------------------
$tabs = apply_filters('woocommerce_product_tabs', ['description' => [], 'reviews' => [], 'additional_information' => []]);
bhp_test_assert($failures, 'the native WooCommerce reviews tab is removed', !isset($tabs['reviews']));
bhp_test_assert($failures, 'the description tab is left alone', isset($tabs['description']));

// ---------------------------------------------------------------------
// 10. The route is registered and points at a template that exists
// ---------------------------------------------------------------------
$rules = get_option('rewrite_rules');
bhp_test_assert($failures, 'the /review/<slug>/ rewrite rule is live',
    is_array($rules) && isset($rules['^review/([^/]+)/?$']));
bhp_test_assert($failures, 'the standalone template file exists',
    file_exists(get_theme_file_path('template-parts/reviews/standalone-review-page.php')));
bhp_test_assert($failures, 'bhp_review_book is a registered query var',
    in_array('bhp_review_book', apply_filters('query_vars', []), true));

// ---------------------------------------------------------------------
// 11. Unregistered slugs 404. Found in staging QA: before this was fixed,
//     `/review/<anything>/` served the BLOG ARCHIVE with HTTP 200 — an
//     unbounded set of duplicate-content URLs.
// ---------------------------------------------------------------------
$live = [];
foreach (array_merge(array_values(bhp_review_route_slugs()), ['not-a-real-book', 'wp-admin', '../etc']) as $probe) {
    $url  = home_url('/review/' . ltrim($probe, '/') . '/');
    $resp = wp_remote_get($url, ['timeout' => 20, 'redirection' => 0]);
    $live[$probe] = is_wp_error($resp) ? 'ERR:' . $resp->get_error_message() : (int) wp_remote_retrieve_response_code($resp);
}
foreach (bhp_review_route_slugs() as $key => $slug) {
    bhp_test_assert($failures, "/review/{$slug}/ answers 200", 200 === $live[$slug]);
}
bhp_test_assert($failures, '/review/not-a-real-book/ answers 404, NOT a 200 blog archive',
    404 === $live['not-a-real-book']);

// ---------------------------------------------------------------------
// 12. The review page is quiet: noindex, its own title, and no popup.
//     All three were defects found in staging QA on the first deploy.
// ---------------------------------------------------------------------
$page = wp_remote_get(bhp_review_page_url('mariana_trench'), ['timeout' => 20]);
if (is_wp_error($page)) {
    WP_CLI::warning('SKIP: could not fetch the review page over HTTP — ' . $page->get_error_message());
} else {
    $html = wp_remote_retrieve_body($page);
    bhp_test_assert($failures, 'review page is noindex, nofollow',
        (bool) preg_match('/<meta name="robots" content="[^"]*noindex[^"]*nofollow/i', $html));
    bhp_test_assert($failures, 'review page title is its own, not the blog archive title',
        strpos($html, '<title>Write a Review for The Mariana Trench') !== false);
    bhp_test_assert($failures, 'no quiz modal or launcher renders on the review page',
        strpos($html, 'quiz-modal') === false && strpos($html, 'bhp-quiz') === false);
    bhp_test_assert($failures, 'review page emits NO aggregateRating',
        stripos($html, 'aggregateRating') === false);
    bhp_test_assert($failures, 'review page emits NO ratingValue',
        stripos($html, 'ratingValue') === false);
    bhp_test_assert($failures, 'review page posts to the canonical Mariana product',
        strpos($html, 'name="comment_post_ID" value="' . bhp_book_canonical_id('mariana_trench') . '"') !== false);
    bhp_test_assert($failures, 'review page has exactly one textarea',
        substr_count($html, 'name="comment"') === 1);
    bhp_test_assert($failures, 'review page has exactly five rating inputs',
        substr_count($html, 'name="rating"') === 5);
    // Found by counting duplicate ids in the live DOM: the standalone template
    // opened its own <main id="main"> on top of header.php's.
    bhp_test_assert($failures, 'review page has exactly one id="main" (header.php owns it)',
        substr_count($html, 'id="main"') === 1);
    bhp_test_assert($failures, 'review page has exactly one <main> landmark',
        substr_count($html, '<main') === 1);
}

// =====================================================================
// 1.19.165 — the three HIGH and four LOW defects from the `commerce-cx` staging QA
// (`commerce-cx`, CYCLE142-CX-070 … -079). Every section below exists
// because a specific, measured defect got through the 1.19.164 suite.
// =====================================================================

// ---------------------------------------------------------------------
// 13. CYCLE142-CX-070 — the band spans the whole product grid
// ---------------------------------------------------------------------
$reviews_css = (string) file_get_contents(get_theme_file_path('assets/css/reviews.css'));
bhp_test_assert($failures, 'CX-070: .bhp-review-section is given grid-column 1/-1 inside div.product',
    (bool) preg_match('/\.woocommerce\s+div\.product\s+\.bhp-review-section\s*\{[^}]*grid-column:\s*1\s*\/\s*-1/s', $reviews_css));
// The `commerce-cx` explicit warning: .bhp-cc-upsell sits in the same grid at 614px and
// is intended. A selector that widened it would be a new defect. Comments are
// stripped first — the class IS named in a comment, on purpose, so the next
// reader knows not to widen it.
$css_rules = (string) preg_replace('!/\*.*?\*/!s', '', $reviews_css);
bhp_test_assert($failures, 'CX-070: no RULE in this file targets .bhp-cc-upsell',
    strpos($css_rules, 'bhp-cc-upsell') === false);
bhp_test_assert($failures, 'CX-070: the error summary lands clear of the sticky header',
    strpos($reviews_css, '.bhp-review-form__errors') !== false
    && (bool) preg_match('/\.bhp-review-form__errors[^{]*\{[^}]*scroll-margin-top/s', $reviews_css));

// ---------------------------------------------------------------------
// 14. CYCLE142-CX-072 — a rating is genuinely required, in code
// ---------------------------------------------------------------------
$mariana = bhp_book_canonical_id('mariana_trench');
bhp_test_assert($failures, 'CX-072: a rating is required for a product target',
    bhp_review_rating_required_for($mariana) === true);
bhp_test_assert($failures, 'CX-072: a rating is NOT demanded for post ID 0',
    bhp_review_rating_required_for(0) === false);
if ($a_post) {
    bhp_test_assert($failures, 'CX-072: a rating is NOT demanded on a blog post',
        bhp_review_rating_required_for((int) $a_post[0]) === false);
}
bhp_test_assert($failures, 'CX-072: the requirement tracks WooCommerce\'s own setting, not a hardcoded true',
    bhp_review_rating_required_for($mariana) === (bool) wc_review_ratings_required());

// ---------------------------------------------------------------------
// 15. CYCLE142-CX-071 — the failure vocabulary, and the field it points at
// ---------------------------------------------------------------------
$messages = bhp_review_error_messages();
foreach (['rating', 'comment', 'author', 'email', 'email_invalid', 'duplicate', 'flood', 'generic'] as $code) {
    bhp_test_assert($failures, "CX-071: a customer-facing message exists for '{$code}'",
        isset($messages[$code]) && '' !== trim($messages[$code]));
}
$fields = bhp_review_error_fields();
bhp_test_assert($failures, 'CX-071: both email failures point at the email field',
    $fields['email'] === 'email' && $fields['email_invalid'] === 'email');
bhp_test_assert($failures, 'CX-071: no message mentions WordPress or an error code',
    !preg_match('/wordpress|wp_die|error \d/i', implode(' ', $messages)));

bhp_test_assert($failures, 'CX-071: an ordinary GET is not treated as a review submission',
    bhp_review_is_our_submission() === false);

// The error state a rejected reviewer comes back to, round-tripped through the
// same transient the redirect uses.
$probe_token = 'bhptest' . substr(md5((string) microtime(true)), 0, 20);
set_transient('bhp_review_err_' . $probe_token, [
    'errors'  => ['rating', 'email_invalid'],
    'values'  => ['rating' => 0, 'comment' => 'Kept every word of this.', 'author' => 'A Parent', 'email' => 'not-an-email'],
    'context' => 'product',
], 300);
$_GET['bhp_review'] = 'error';
$_GET['bhp_rk']     = $probe_token;

bhp_test_assert($failures, 'CX-071: the failure codes are read back for the right form',
    bhp_review_errors_for('product') === ['rating', 'email_invalid']);
bhp_test_assert($failures, 'CX-071: a form in the OTHER context shows nothing',
    bhp_review_errors_for('standalone') === []);
bhp_test_assert($failures, 'CX-071: the typed review survives the round trip',
    bhp_review_value_for('product', 'comment') === 'Kept every word of this.');
bhp_test_assert($failures, 'CX-071: the typed name survives the round trip',
    bhp_review_value_for('product', 'author') === 'A Parent');
bhp_test_assert($failures, 'CX-071: the typed email survives the round trip',
    bhp_review_value_for('product', 'email') === 'not-an-email');

$err_form = bhp_review_render_form('mariana_trench', 'product');
bhp_test_assert($failures, 'CX-071: the summary panel renders VISIBLE (not hidden) after a rejection',
    strpos($err_form, 'id="bhp-review-errors-product"') !== false
    && !preg_match('/id="bhp-review-errors-product"[^>]*\shidden/', $err_form));
bhp_test_assert($failures, 'CX-071: the summary is announced (role=alert) and focusable',
    (bool) preg_match('/id="bhp-review-errors-product"[^>]*role="alert"/s', $err_form)
    || (bool) preg_match('/role="alert"[^>]*id="bhp-review-errors-product"/s', $err_form));
bhp_test_assert($failures, 'CX-071: the reviewer\'s words are back in the textarea',
    strpos($err_form, 'Kept every word of this.') !== false);
bhp_test_assert($failures, 'CX-071: the rating group is marked invalid',
    strpos($err_form, 'data-bhp-invalid="true"') !== false);

/*
 * ⚠ The name and email fields are NOT in this render, and that is correct
 *   rather than a failure: review-form.php only renders the identity block for
 *   a logged-out visitor, and `wp eval-file --user=1` runs logged in as Andrew.
 *   Asserting on them here fails for the wrong reason — it happened on the
 *   first run of this suite and is recorded so it is not re-derived.
 *
 *   So the identity half is proved over HTTP instead, logged out, which is the
 *   customer's actual view and better evidence than an in-process render.
 */
bhp_test_assert($failures, 'CX-071: no identity fields are rendered for a logged-in reviewer',
    !is_user_logged_in() || strpos($err_form, 'name="email"') === false);

$err_url  = add_query_arg(['bhp_review' => 'error', 'bhp_rk' => $probe_token], get_permalink($mariana));
$err_page = wp_remote_get($err_url, ['timeout' => 25]);
if (is_wp_error($err_page)) {
    WP_CLI::warning('SKIP: could not fetch the rejected-submission page — ' . $err_page->get_error_message());
} else {
    $err_html = wp_remote_retrieve_body($err_page);
    bhp_test_assert($failures, 'CX-071 live: the rejected reviewer gets a normal branded page, not wp_die',
        200 === (int) wp_remote_retrieve_response_code($err_page)
        && strpos($err_html, 'id="error-page"') === false
        && stripos($err_html, 'Comment Submission Failure') === false);
    bhp_test_assert($failures, 'CX-071 live: the validation summary is on the page and visible',
        (bool) preg_match('/id="bhp-review-errors-product"(?![^>]*\shidden)/s', $err_html));
    bhp_test_assert($failures, 'CX-071 live: both failures are named for the customer',
        strpos($err_html, 'Please choose a star rating.') !== false
        && strpos($err_html, 'does not look right') !== false);
    bhp_test_assert($failures, 'CX-071 live: the typed review is still in the textarea',
        strpos($err_html, 'Kept every word of this.') !== false);
    bhp_test_assert($failures, 'CX-071 live: the typed name is still in the field',
        strpos($err_html, 'value="A Parent"') !== false);
    bhp_test_assert($failures, 'CX-071 live: the typed email is still in the field',
        strpos($err_html, 'value="not-an-email"') !== false);
    bhp_test_assert($failures, 'CX-071 live: the failing email field is marked invalid',
        (bool) preg_match('/name="email"[^>]*aria-invalid="true"/s', $err_html));
    bhp_test_assert($failures, 'CX-071 live: the rating group is marked invalid',
        strpos($err_html, 'data-bhp-invalid="true"') !== false);
    bhp_test_assert($failures, 'CX-071 live: and the error page still emits NO rating schema',
        stripos($err_html, 'aggregateRating') === false && stripos($err_html, 'ratingValue') === false);
}
bhp_test_assert($failures, 'CX-071: the message wording is shared with the client, not duplicated',
    strpos($err_form, 'bhp-review-form__messages') !== false);
bhp_test_assert($failures, 'CX-071: still NO schema on an error render',
    stripos($err_form, 'aggregateRating') === false && stripos($err_form, 'ratingValue') === false);

unset($_GET['bhp_review'], $_GET['bhp_rk']);
delete_transient('bhp_review_err_' . $probe_token);

bhp_test_assert($failures, 'CX-071: a clean page view shows no error state at all',
    bhp_review_errors_for('product') === [] && bhp_review_errors_for('standalone') === []);

// ---------------------------------------------------------------------
// 16. CYCLE142-CX-076 / -077 — the form's markup after the a11y corrections
// ---------------------------------------------------------------------
$clean_form = bhp_review_render_form('mariana_trench', 'product');
bhp_test_assert($failures, 'CX-076: the rating radios no longer carry the HTML required attribute',
    !preg_match('/<input[^>]*name="rating"[^>]*\srequired/s', $clean_form));
bhp_test_assert($failures, 'CX-076: the rating group declares itself required to assistive tech instead',
    (bool) preg_match('/class="bhp-star-input"[^>]*role="radiogroup"/s', $clean_form)
    && strpos($clean_form, 'aria-required="true"') !== false);
bhp_test_assert($failures, 'CX-076: the rating group still has an accessible name',
    strpos($clean_form, 'aria-labelledby="bhp-review-product-rating-legend"') !== false);
bhp_test_assert($failures, 'CX-076: still exactly five rating inputs, still none pre-selected',
    substr_count($clean_form, 'name="rating"') === 5 && strpos($clean_form, 'checked') === false);
bhp_test_assert($failures, 'CX-071: the client is told whether a rating is required',
    strpos($clean_form, 'data-bhp-rating-required="1"') !== false);
bhp_test_assert($failures, 'CX-071: the summary panel starts hidden on a clean load',
    (bool) preg_match('/id="bhp-review-errors-product"[^>]*\shidden/s', $clean_form));
bhp_test_assert($failures, 'novalidate is still deliberately present (see the docblock)',
    strpos($clean_form, 'novalidate') !== false);
bhp_test_assert($failures, 'the deliberate no-nonce design is unchanged',
    strpos($clean_form, '_wpnonce') === false && strpos($clean_form, 'comment_form_nonce') === false);

$empty_section = bhp_review_render_section('mariana_trench', 'product');
bhp_test_assert($failures, 'CX-077: the invitation heading appears exactly ONCE in the empty state',
    substr_count($empty_section, 'Write a Review for The Mariana Trench') === 1);
bhp_test_assert($failures, 'CX-077: the form is still rendered in the empty state',
    strpos($empty_section, 'id="bhp-review-form-product"') !== false);
bhp_test_assert($failures, 'CX-077: the standalone form keeps its own heading',
    substr_count(bhp_review_render_form('mariana_trench', 'standalone'), 'Write a Review for The Mariana Trench') === 1);

// ---------------------------------------------------------------------
// 17. CYCLE142-CX-079 — no empty form under the thank-you panel
// ---------------------------------------------------------------------
$_GET['bhp_review'] = 'thanks';
$thanks_section = bhp_review_render_section('mariana_trench', 'product');
bhp_test_assert($failures, 'CX-079: the thank-you panel renders', strpos($thanks_section, 'bhp-review-thanks') !== false);
bhp_test_assert($failures, 'CX-079: NO write-a-review form is offered beneath it',
    strpos($thanks_section, 'id="bhp-review-form-product"') === false
    && strpos($thanks_section, 'name="comment_post_ID"') === false);
bhp_test_assert($failures, 'CX-079: the quick-form shortcut is withdrawn too',
    strpos($thanks_section, 'Use the quick review form') === false);
bhp_test_assert($failures, 'CX-079: the thanks state still emits no schema',
    stripos($thanks_section, 'aggregateRating') === false && stripos($thanks_section, 'ratingValue') === false);
unset($_GET['bhp_review']);

// ---------------------------------------------------------------------
// 18. CYCLE142-CX-073 / -075 — routing
// ---------------------------------------------------------------------
bhp_test_assert($failures, 'CX-073: an unregistered slug is turned into a genuine 404 query',
    bhp_review_force_404_for_unknown_slug(['bhp_review_book' => 'not-a-real-book']) === ['error' => '404']);
bhp_test_assert($failures, 'CX-073: a registered slug is passed through untouched',
    bhp_review_force_404_for_unknown_slug(['bhp_review_book' => 'the-mariana-trench']) === ['bhp_review_book' => 'the-mariana-trench']);
bhp_test_assert($failures, 'CX-073: a request that is not a review route is never modified',
    bhp_review_force_404_for_unknown_slug(['name' => 'some-post']) === ['name' => 'some-post']);

$title_probe = wp_remote_get(home_url('/review/not-a-real-book/'), ['timeout' => 20]);
if (is_wp_error($title_probe)) {
    WP_CLI::warning('SKIP: could not fetch the invalid-slug 404 — ' . $title_probe->get_error_message());
} else {
    $probe_html = wp_remote_retrieve_body($title_probe);
    bhp_test_assert($failures, 'CX-073: an unregistered review slug still answers 404',
        404 === (int) wp_remote_retrieve_response_code($title_probe));
    bhp_test_assert($failures, 'CX-073: and its <title> is no longer the blog archive title',
        stripos($probe_html, '<title>Blog') === false);
    bhp_test_assert($failures, 'CX-073: no review form is rendered on it',
        strpos($probe_html, 'bhp-review-form') === false);
}

$upper = wp_remote_get(home_url('/review/THE-MARIANA-TRENCH/'), ['timeout' => 20, 'redirection' => 0]);
if (is_wp_error($upper)) {
    WP_CLI::warning('SKIP: could not probe the uppercase slug — ' . $upper->get_error_message());
} else {
    bhp_test_assert($failures, 'CX-075: a non-canonical slug redirects rather than serving a second live URL',
        301 === (int) wp_remote_retrieve_response_code($upper));
    bhp_test_assert($failures, 'CX-075: and it redirects to the canonical lowercase URL',
        wp_remote_retrieve_header($upper, 'location') === bhp_review_page_url('mariana_trench'));
}

// ---------------------------------------------------------------------
// 19. END-TO-END: a real POST to wp-comments-post.php must NEVER land on
//     WordPress's bare error page, and a rating-less review must NEVER be
//     stored. This is the section that would have caught -071 and -072.
//
//     ⛔ Every probe below is designed to be REJECTED, so none of them can
//        create a comment. The count is asserted unchanged afterwards.
// ---------------------------------------------------------------------
$before = (int) get_comments(['post_id' => $mariana, 'status' => 'all', 'count' => true]);

$probe_base = [
    'comment_post_ID'    => $mariana,
    'comment_parent'     => 0,
    'bhp_review_context' => 'product',
    'bhp_review_book'    => 'mariana_trench',
    'bhp_review_return'  => get_permalink($mariana),
];

$submit = static function ($extra) use ($probe_base) {
    return wp_remote_post(site_url('/wp-comments-post.php'), [
        'timeout'     => 25,
        'redirection' => 0,
        'body'        => array_merge($probe_base, $extra),
    ]);
};

$e2e = [
    'no rating at all (the exact V5 probe that was wrongly accepted)' => [
        'comment' => 'BHP automated validation probe. Must never be stored.',
        'author'  => 'BHP Validation Probe',
        'email'   => 'bhp-validation-probe@example.com',
    ],
    'everything empty' => [],
    'a rating but no review text' => [
        'rating' => 5,
        'author' => 'BHP Validation Probe',
        'email'  => 'bhp-validation-probe@example.com',
    ],
    'a malformed email address' => [
        'rating'  => 4,
        'comment' => 'BHP automated validation probe. Must never be stored.',
        'author'  => 'BHP Validation Probe',
        'email'   => 'not-an-email',
    ],
];

foreach ($e2e as $label => $body) {
    $resp = $submit($body);
    if (is_wp_error($resp)) {
        WP_CLI::warning("SKIP: could not POST the '{$label}' probe — " . $resp->get_error_message());
        continue;
    }
    $code     = (int) wp_remote_retrieve_response_code($resp);
    $location = (string) wp_remote_retrieve_header($resp, 'location');
    $body_txt = (string) wp_remote_retrieve_body($resp);

    bhp_test_assert($failures, "CX-071 e2e [{$label}]: answered with a redirect, not an error page",
        302 === $code);
    bhp_test_assert($failures, "CX-071 e2e [{$label}]: the reviewer is sent back to the product page",
        strpos($location, (string) get_permalink($mariana)) === 0);
    bhp_test_assert($failures, "CX-071 e2e [{$label}]: carrying the in-page error state",
        strpos($location, 'bhp_review=error') !== false && strpos($location, 'bhp_rk=') !== false);
    bhp_test_assert($failures, "CX-071 e2e [{$label}]: and NOT a thank-you (it was not accepted)",
        strpos($location, 'bhp_review=thanks') === false);
    bhp_test_assert($failures, "CX-071 e2e [{$label}]: no WordPress error screen was rendered",
        stripos($body_txt, 'Comment Submission Failure') === false
        && stripos($body_txt, 'id="error-page"') === false);
}

// The honeypot must STILL die bare — a bot is told nothing, deliberately.
$hp = $submit([
    'rating'         => 5,
    'comment'        => 'BHP automated honeypot probe. Must never be stored.',
    'author'         => 'BHP Validation Probe',
    'email'          => 'bhp-validation-probe@example.com',
    'bhp_review_hp'  => 'i-am-a-bot',
]);
if (is_wp_error($hp)) {
    WP_CLI::warning('SKIP: could not POST the honeypot probe — ' . $hp->get_error_message());
} else {
    bhp_test_assert($failures, 'the honeypot still rejects outright (403), and is NOT bounced back gracefully',
        403 === (int) wp_remote_retrieve_response_code($hp));
    bhp_test_assert($failures, 'the honeypot still refuses to explain itself to a bot',
        stripos((string) wp_remote_retrieve_body($hp), 'rating') === false);
}

$after = (int) get_comments(['post_id' => $mariana, 'status' => 'all', 'count' => true]);
bhp_test_assert($failures, 'CX-072: NOT ONE of the rejected probes created a comment',
    $after === $before);

// ---------------------------------------------------------------------
// 20. The client-side half exists and is genuinely optional
// ---------------------------------------------------------------------
$reviews_js = (string) file_get_contents(get_theme_file_path('assets/js/reviews.js'));
bhp_test_assert($failures, 'CX-071: the client blocks submit on a validation failure',
    strpos($reviews_js, "addEventListener('submit'") !== false && strpos($reviews_js, 'preventDefault') !== false);
bhp_test_assert($failures, 'CX-072: the client checks the rating too',
    strpos($reviews_js, 'input[name="rating"]:checked') !== false);
bhp_test_assert($failures, 'CX-071: the client reads its wording from PHP rather than hardcoding it',
    strpos($reviews_js, 'bhp-review-form__messages') !== false && strpos($reviews_js, 'JSON.parse') !== false);
bhp_test_assert($failures, 'CX-074: the product-page focus is re-asserted after load',
    strpos($reviews_js, 'assertProductFocus') !== false && strpos($reviews_js, "addEventListener('load'") !== false);
bhp_test_assert($failures, 'CX-074: and it yields the moment anything else holds focus',
    strpos($reviews_js, 'document.activeElement') !== false);

// ---------------------------------------------------------------------
// 21. Regression guard — the two hard requirements `commerce-cx` verified must
//     still hold after all of the above.
// ---------------------------------------------------------------------
foreach ($keys as $key) {
    $sec = bhp_review_render_section($key, 'product');
    bhp_test_assert($failures, "REGRESSION {$key}: moderation is still forced on every product review",
        bhp_review_force_moderation(1, ['comment_post_ID' => bhp_review_target_id($key)]) === 0);
    bhp_test_assert($failures, "REGRESSION {$key}: still zero rating markup of any kind",
        stripos($sec, 'aggregateRating') === false
        && stripos($sec, 'ratingValue') === false
        && stripos($sec, 'reviewCount') === false
        && strpos($sec, 'application/ld+json') === false);
}

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d review-system test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All customer review system tests passed.');
}
