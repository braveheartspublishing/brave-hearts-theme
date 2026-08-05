<?php
/**
 * Amazon customer-review showcase smoke test (Phase 3).
 *
 * Follows the same wp-cli-driven convention as
 * tests/test-kirkus-component.php (no PHPUnit exists in this theme).
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-amazon-review-showcase.php --user=1
 *
 * Exits non-zero on any failure so it can gate a deploy.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_test_assert2(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

// ---------------------------------------------------------------------
// 1. Registry integrity: unique IDs, valid book_slug, no cross-mapping
// ---------------------------------------------------------------------
$registry = bhp_get_amazon_review_registry();
$ids = array_column($registry, 'id');
bhp_test_assert2($failures, 'no duplicate review IDs in the registry', count($ids) === count(array_unique($ids)));

$all_slugs_valid = true;
foreach ($registry as $r) {
    if (!in_array($r['book_slug'] ?? '', BHP_AMAZON_REVIEW_BOOK_SLUGS, true)) {
        $all_slugs_valid = false;
    }
}
bhp_test_assert2($failures, 'every registry entry has a valid book_slug', $all_slugs_valid);

// ---------------------------------------------------------------------
// 2. Cross-book regression tests -- explicit, one per required pairing
// ---------------------------------------------------------------------
$mariana = bhp_get_approved_amazon_reviews_for_book('mariana_trench');
$everest = bhp_get_approved_amazon_reviews_for_book('mount_everest');
$amazon_book = bhp_get_approved_amazon_reviews_for_book('amazon_rainforest');

$mariana_ids = array_column($mariana, 'id');
$everest_ids = array_column($everest, 'id');
$amazon_book_ids = array_column($amazon_book, 'id');

bhp_test_assert2($failures, 'no Mariana Trench review appears under Mount Everest',
    !array_intersect($mariana_ids, $everest_ids));
bhp_test_assert2($failures, 'no Mount Everest review appears under The Amazon',
    !array_intersect($everest_ids, $amazon_book_ids));
bhp_test_assert2($failures, 'no The Amazon review appears under Mariana Trench',
    !array_intersect($amazon_book_ids, $mariana_ids));

foreach ($mariana as $r) {
    bhp_test_assert2($failures, "mariana_trench review {$r['id']} is actually mapped to mariana_trench", $r['book_slug'] === 'mariana_trench');
}
foreach ($everest as $r) {
    bhp_test_assert2($failures, "mount_everest review {$r['id']} is actually mapped to mount_everest", $r['book_slug'] === 'mount_everest');
}

// ---------------------------------------------------------------------
// 3. The Amazon Rainforest book: genuine no-review state
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'The Amazon (amazon_rainforest) has zero approved reviews (confirmed live: 0 real Amazon reviews exist)', $amazon_book === []);
$amazon_render = bhp_render_amazon_review_showcase('amazon_rainforest', 'expanded', ['source' => 'test']);
bhp_test_assert2($failures, 'rendering the showcase for a book with zero reviews produces no output (no placeholder, no broken markup)', trim($amazon_render) === '');

// ---------------------------------------------------------------------
// 4. Unknown / invalid book_slug fails closed
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'an unrecognized book_slug returns zero reviews', bhp_get_approved_amazon_reviews_for_book('some_other_book') === []);
$invalid_render = bhp_render_amazon_review_showcase('not_a_real_book', 'expanded', ['source' => 'test']);
bhp_test_assert2($failures, 'an unrecognized book_slug renders nothing', trim($invalid_render) === '');

// ---------------------------------------------------------------------
// 5. Approval-state and environment-safety enforcement
// ---------------------------------------------------------------------
$fixture_filter = function ($registry) {
    $registry[] = [
        'id' => 'fixture-unapproved', 'book_slug' => 'mariana_trench',
        'excerpt' => 'This should never render.', 'source_type' => 'amazon_customer_review',
        'source_label' => 'Amazon customer review', 'source_url' => 'https://www.amazon.com/example',
        'approved' => false, 'environment' => 'production',
    ];
    $registry[] = [
        'id' => 'fixture-staging-only', 'book_slug' => 'mariana_trench',
        'excerpt' => 'This should never render on production.', 'source_type' => 'amazon_customer_review',
        'source_label' => 'Amazon customer review', 'source_url' => 'https://www.amazon.com/example',
        'approved' => true, 'environment' => 'staging_only',
    ];
    return $registry;
};
add_filter('bhp_amazon_review_registry', $fixture_filter);
$with_fixtures = bhp_get_approved_amazon_reviews_for_book('mariana_trench');
$fixture_ids = array_column($with_fixtures, 'id');
remove_filter('bhp_amazon_review_registry', $fixture_filter);

bhp_test_assert2($failures, 'an unapproved review never renders regardless of environment', !in_array('fixture-unapproved', $fixture_ids, true));
bhp_test_assert2($failures, 'a staging_only fixture never renders on production (confirmed: ' . (bhp_is_production_site() ? 'currently ON production' : 'currently NOT on production, so this check is about the code path, not the live host') . ')',
    bhp_is_production_site() ? !in_array('fixture-staging-only', $fixture_ids, true) : true);

// A duplicate ID (same ID as a real approved review) must not double-render.
$dup_filter = function ($registry) {
    $registry[] = ['id' => 'amz-mariana-01', 'book_slug' => 'mariana_trench', 'excerpt' => 'Duplicate ID row.',
        'source_type' => 'amazon_customer_review', 'source_label' => 'Amazon customer review',
        'source_url' => 'https://www.amazon.com/example', 'approved' => true, 'environment' => 'production'];
    return $registry;
};
add_filter('bhp_amazon_review_registry', $dup_filter);
$with_dup = bhp_get_approved_amazon_reviews_for_book('mariana_trench');
remove_filter('bhp_amazon_review_registry', $dup_filter);
$dup_count = count(array_filter(array_column($with_dup, 'id'), fn($id) => $id === 'amz-mariana-01'));
bhp_test_assert2($failures, 'a duplicate review ID is only kept once, not rendered twice', $dup_count === 1);

// A review missing a required field (excerpt/source_url/id) fails closed.
$incomplete_filter = function ($registry) {
    $registry[] = ['id' => 'fixture-incomplete', 'book_slug' => 'mariana_trench', 'excerpt' => '',
        'source_type' => 'amazon_customer_review', 'source_label' => 'Amazon customer review',
        'source_url' => 'https://www.amazon.com/example', 'approved' => true, 'environment' => 'production'];
    return $registry;
};
add_filter('bhp_amazon_review_registry', $incomplete_filter);
$with_incomplete = bhp_get_approved_amazon_reviews_for_book('mariana_trench');
remove_filter('bhp_amazon_review_registry', $incomplete_filter);
bhp_test_assert2($failures, 'a review with a missing excerpt is excluded rather than rendered broken', !in_array('fixture-incomplete', array_column($with_incomplete, 'id'), true));

// ---------------------------------------------------------------------
// 6. Rendering: expanded mode
// ---------------------------------------------------------------------
$expanded = bhp_render_amazon_review_showcase('mariana_trench', 'expanded', ['source' => 'test']);
bhp_test_assert2($failures, 'expanded mode renders a blockquote', strpos($expanded, '<blockquote') !== false);
bhp_test_assert2($failures, 'expanded mode shows "Amazon customer review" attribution, never a reviewer name', strpos($expanded, 'Amazon customer review') !== false);
bhp_test_assert2($failures, 'expanded mode shows individual star rating markup', strpos($expanded, 'amazon-review-card__stars') !== false);
bhp_test_assert2($failures, 'expanded mode shows Verified Purchase for the verified review', strpos($expanded, 'Verified Purchase') !== false);
bhp_test_assert2($failures, 'expanded mode has a stable impression-tracking data attribute', strpos($expanded, 'data-bhp-impression-event="customer_review_impression"') !== false);
bhp_test_assert2($failures, 'expanded mode has a stable source-click data attribute', strpos($expanded, 'data-bhp-event="customer_review_source_click"') !== false);
bhp_test_assert2($failures, 'expanded mode renders as a multiple-review grid for a book with 3 reviews', strpos($expanded, 'amazon-review-showcase--multiple') !== false);

// The one non-verified Everest review must never show a Verified Purchase badge.
$everest_render = bhp_render_amazon_review_showcase('mount_everest', 'expanded', ['source' => 'test']);
bhp_test_assert2($failures, 'Everest showcase contains both a verified and a non-verified review', substr_count($everest_render, 'Verified Purchase') === 1);

// ---------------------------------------------------------------------
// 7. Rendering: compact mode
// ---------------------------------------------------------------------
$compact = bhp_render_amazon_review_showcase('mariana_trench', 'compact', ['source' => 'test', 'max_reviews' => 1]);
bhp_test_assert2($failures, 'compact mode omits the review title', strpos($compact, 'amazon-review-card__title') === false);
bhp_test_assert2($failures, 'compact mode with max_reviews=1 renders exactly one card', substr_count($compact, 'amazon-review-card__quote') === 1);
bhp_test_assert2($failures, 'compact mode renders as single-review layout when capped to 1', strpos($compact, 'amazon-review-showcase--single') !== false);

// ---------------------------------------------------------------------
// 8. show_book_title / show_product_link options
// ---------------------------------------------------------------------
$with_title = bhp_render_amazon_review_showcase('mariana_trench', 'compact', ['source' => 'test', 'show_book_title' => true]);
bhp_test_assert2($failures, 'show_book_title=true renders the book title', strpos($with_title, 'amazon-review-showcase__book-title') !== false && strpos($with_title, 'The Mariana Trench') !== false);
$without_title = bhp_render_amazon_review_showcase('mariana_trench', 'compact', ['source' => 'test', 'show_book_title' => false]);
bhp_test_assert2($failures, 'show_book_title=false (default) omits the book-title element', strpos($without_title, 'amazon-review-showcase__book-title') === false);

$with_product_link = bhp_render_amazon_review_showcase('mariana_trench', 'compact', ['source' => 'test', 'show_product_link' => true]);
bhp_test_assert2($failures, 'show_product_link=true renders a product-click link with the correct event name', strpos($with_product_link, 'data-bhp-event="customer_review_product_click"') !== false);

// ---------------------------------------------------------------------
// 9. Escaping
// ---------------------------------------------------------------------
$inject = function ($registry) {
    $registry[] = ['id' => 'fixture-xss', 'book_slug' => 'amazon_rainforest',
        'excerpt' => '<script>alert(1)</script>', 'review_title' => '"><img src=x onerror=alert(1)>',
        'source_type' => 'amazon_customer_review', 'source_label' => '<b>fake</b>',
        'source_url' => 'https://www.amazon.com/example', 'rating' => 5,
        'approved' => true, 'environment' => 'production'];
    return $registry;
};
add_filter('bhp_amazon_review_registry', $inject);
$escaped = bhp_render_amazon_review_showcase('amazon_rainforest', 'expanded', ['source' => 'test']);
remove_filter('bhp_amazon_review_registry', $inject);
bhp_test_assert2($failures, 'injected <script> in excerpt is escaped, not executable', strpos($escaped, '<script>alert(1)</script>') === false);
bhp_test_assert2($failures, 'injected onerror payload in review_title is neutralized (no live <img> tag)', strpos($escaped, '<img') === false);
bhp_test_assert2($failures, 'injected markup in source_label is escaped', strpos($escaped, '<b>fake</b>') === false);

// ---------------------------------------------------------------------
// 10. Invalid / missing source URL handled gracefully
// ---------------------------------------------------------------------
$bad_url_filter = function ($registry) {
    $registry[] = ['id' => 'fixture-bad-url', 'book_slug' => 'amazon_rainforest',
        'excerpt' => 'Has a bad url.', 'source_type' => 'amazon_customer_review',
        'source_label' => 'Amazon customer review', 'source_url' => 'javascript:alert(1)',
        'approved' => true, 'environment' => 'production'];
    return $registry;
};
add_filter('bhp_amazon_review_registry', $bad_url_filter);
$bad_url_render = bhp_render_amazon_review_showcase('amazon_rainforest', 'expanded', ['source' => 'test']);
remove_filter('bhp_amazon_review_registry', $bad_url_filter);
bhp_test_assert2($failures, 'a javascript: URL is never emitted as an href (esc_url strips disallowed schemes)', strpos($bad_url_render, 'href="javascript:') === false);

// ---------------------------------------------------------------------
// 11. No AggregateRating / third-party Review schema anywhere in output
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'no AggregateRating schema in rendered output', stripos($expanded, 'AggregateRating') === false);
bhp_test_assert2($failures, 'no schema.org Review type in rendered output', stripos($expanded, 'schema.org/Review') === false);
bhp_test_assert2($failures, 'no inline JSON-LD script tag in rendered output', strpos($expanded, 'application/ld+json') === false);
bhp_test_assert2($failures, 'no itemprop="reviewBody" microdata', strpos($expanded, 'itemprop="reviewBody"') === false);

// ---------------------------------------------------------------------
// 12. Kirkus separation -- the two components never share markup or data
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'Amazon showcase output never contains the Kirkus quote text', strpos($expanded, 'Simple but effective storytelling') === false);
bhp_test_assert2($failures, 'Amazon showcase output never contains "Kirkus"', stripos($expanded, 'kirkus') === false);
$kirkus_output = bhp_render_kirkus_credibility('expanded', ['source' => 'test']);
bhp_test_assert2($failures, 'Kirkus component output never contains "Amazon customer review"', strpos($kirkus_output, 'Amazon customer review') === false);

// ---------------------------------------------------------------------
// 13. Analytics data attributes never carry reviewer names or review text
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'data-bhp-book values are slugs, not review text or names', preg_match('/data-bhp-book="[a-z_]+"/', $expanded) === 1);
$has_only_safe_data_attrs = !preg_match('/data-bhp-[a-z]+="[^"]*(Adventures of Charlotte|nightly|bedtime)/i', $expanded);
bhp_test_assert2($failures, 'no review text leaks into a data-bhp-* attribute value', $has_only_safe_data_attrs);

// ---------------------------------------------------------------------
// 14. show_eyebrow option (staging correction pass: product-page full-width
//     section supplies its own <h2>/eyebrow wrapper and suppresses the
//     component's internal one to avoid duplicate headings)
// ---------------------------------------------------------------------
$no_eyebrow = bhp_render_amazon_review_showcase('mariana_trench', 'expanded', ['source' => 'test', 'show_eyebrow' => false]);
bhp_test_assert2($failures, 'show_eyebrow=false omits the internal eyebrow paragraph', strpos($no_eyebrow, 'amazon-review-showcase__eyebrow') === false);
$with_eyebrow = bhp_render_amazon_review_showcase('mariana_trench', 'expanded', ['source' => 'test']);
bhp_test_assert2($failures, 'show_eyebrow=true (default) still renders the internal eyebrow paragraph', strpos($with_eyebrow, 'amazon-review-showcase__eyebrow') !== false);

// ---------------------------------------------------------------------
// 15. Short, accessible source-link labels (staging correction pass:
//     visible text shortened to "Read on Amazon", full context moved to
//     an aria-label naming the book, per Andrew's Part 3 requirement)
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'source link visible text is the short "Read on Amazon" label', strpos($expanded, '>Read on Amazon<') !== false);

/*
 * ⭐ CYCLE144-LD-211 (2026-08-05, theme 1.19.201) — THIS ASSERTION WAS REWRITTEN
 *    BECAUSE IT PINNED A STRING INSTEAD OF THE REQUIREMENT, AND THE STRING WAS
 *    WHAT WAS WRONG.
 *
 * SUPERSEDED assertion, preserved verbatim so the movement is visible:
 *
 *   bhp_test_assert2($failures, 'source link has an aria-label naming the specific book',
 *     strpos($expanded, 'aria-label="Read this Amazon customer review for Adventures of Charlotte &amp; Henry: The Mariana Trench') !== false);
 *
 * It asserted one literal accessible name. That name FAILED Lighthouse 12.8.2 /
 * axe's `label-content-name-mismatch` audit on the staging home page, twice:
 * the visible label is "Read on Amazon" and the accessible name did not contain
 * it. That is WCAG 2.5.3 (Label in Name) — a speech-input user saying the words
 * they can SEE could not activate the link.
 *
 * ⚠ SO THE OLD TEST PASSED ON A GENUINELY DEFECTIVE BUILD, AND WOULD HAVE
 *   BLOCKED THE FIX. It is replaced with the two properties that actually
 *   matter, which is a STRICTER contract than before, not a looser one:
 *     1. the accessible name CONTAINS the visible label verbatim (WCAG 2.5.3);
 *     2. the accessible name still names the specific book, which is the
 *        original intent — three cards on one page must stay distinguishable.
 * Written against the rendered output rather than the wording, so a future copy
 * change cannot fail this for the wrong reason.
 */
if (!preg_match('/<a\b[^>]*class="amazon-review-card__link"[^>]*>/', $expanded, $link_match)) {
    $link_match = array('');
}
$link_tag = $link_match[0];
$link_name = preg_match('/aria-label="([^"]*)"/', $link_tag, $name_match) ? $name_match[1] : '';

bhp_test_assert2(
    $failures,
    'source link accessible name CONTAINS its visible label verbatim (WCAG 2.5.3 Label in Name)',
    $link_name !== '' && strpos($link_name, 'Read on Amazon') !== false
);
bhp_test_assert2(
    $failures,
    'source link accessible name still names the specific book',
    $link_name !== '' && strpos($link_name, 'Adventures of Charlotte &amp; Henry: The Mariana Trench') !== false
);
/*
 * The stars container carries `role="img"`. Without a role, a <div>'s implicit
 * role is `generic`, which PROHIBITS aria-label — axe's `aria-prohibited-attr`
 * failed on exactly this element and the label was being discarded, so the
 * rating was announced as nothing at all.
 */
bhp_test_assert2(
    $failures,
    'star-rating container has role="img", so its aria-label is not discarded',
    preg_match('/<div class="amazon-review-card__stars" role="img" aria-label="[^"]+"/', $expanded) === 1
);

// ---------------------------------------------------------------------
// 16. max_excerpt_words truncation (staging correction pass: shop/catalog
//     cards cap excerpts to ~12-18 words so cards stay compact and scannable)
// ---------------------------------------------------------------------
$full_excerpt_review = $mariana[0];
$word_count = count(preg_split('/\s+/', trim($full_excerpt_review['excerpt'])));
bhp_test_assert2($failures, 'fixture assumption: the first Mariana review excerpt is longer than 18 words (so truncation is actually exercised)', $word_count > 18);
$truncated = bhp_render_amazon_review_showcase('mariana_trench', 'compact', ['source' => 'test', 'max_reviews' => 1, 'max_excerpt_words' => 18]);
bhp_test_assert2($failures, 'max_excerpt_words=18 shortens a long excerpt and appends an ellipsis', strpos($truncated, '&hellip;') !== false);
$truncated_word_count_ok = true;
if (preg_match('/<p>&ldquo;(.*?)&hellip;&rdquo;/s', $truncated, $m)) {
    $truncated_word_count_ok = count(preg_split('/\s+/', trim($m[1]))) <= 18;
}
bhp_test_assert2($failures, 'the truncated excerpt itself contains at most 18 words', $truncated_word_count_ok);
$untruncated = bhp_render_amazon_review_showcase('mariana_trench', 'expanded', ['source' => 'test', 'max_reviews' => 1]);
bhp_test_assert2($failures, 'max_excerpt_words=0 (default) leaves the excerpt untruncated, no ellipsis added', strpos($untruncated, '&hellip;') === false);

// ---------------------------------------------------------------------
// 17. show_verified_badge option (staging correction pass: shop/catalog
//     cards suppress the Verified Purchase badge so a short excerpt +
//     stars + source label doesn't get crowded by a third line)
// ---------------------------------------------------------------------
$no_badge = bhp_render_amazon_review_showcase('mariana_trench', 'compact', ['source' => 'test', 'show_verified_badge' => false]);
bhp_test_assert2($failures, 'show_verified_badge=false omits the Verified Purchase badge even for a verified review', strpos($no_badge, 'Verified Purchase') === false);
$badge_default = bhp_render_amazon_review_showcase('mariana_trench', 'expanded', ['source' => 'test', 'max_reviews' => 1]);
bhp_test_assert2($failures, 'show_verified_badge=true (default) shows the badge when the review itself is Verified Purchase', strpos($badge_default, 'Verified Purchase') !== false);

// ---------------------------------------------------------------------
// 18. Product-page placement moved to a full-width section (staging
//     correction pass: hooked to woocommerce_after_single_product_summary
//     at priority 5, not the narrow woocommerce_single_product_summary
//     column, so Mariana's three-review grid and Everest's two-review grid
//     render at full page width instead of the narrow purchase column)
// ---------------------------------------------------------------------
bhp_test_assert2($failures, 'bhp_woocommerce_product_amazon_reviews_section is hooked to woocommerce_after_single_product_summary at priority 5', has_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_amazon_reviews_section') === 5);
bhp_test_assert2($failures, 'bhp_woocommerce_product_amazon_reviews_section is no longer hooked to the narrow woocommerce_single_product_summary column', has_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_amazon_reviews_section') === false);

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d Amazon review showcase test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All Amazon customer review showcase tests passed.');
}
