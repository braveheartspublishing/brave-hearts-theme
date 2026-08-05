<?php
/**
 * Centralized Amazon customer-review registry (Phase 3).
 *
 * Every approved excerpt lives in exactly one place --
 * bhp_get_amazon_review_registry() -- so no template ever hardcodes a
 * quote. Mirrors the Kirkus credibility component's architecture
 * (functions.php: bhp_get_kirkus_review_data()) for consistency.
 *
 * Source: publicly visible Amazon customer reviews, read directly from
 * each book's live Amazon product/review page on 2026-07-05. Reviewer
 * display names are deliberately omitted -- attribution uses restrained
 * "Amazon customer review" labels instead (Andrew's explicit preference).
 * No reviewer photos, profile histories, locations, or full review bodies
 * are retained -- only short excerpts preserving original meaning.
 *
 * Verified Purchase is only ever true when Amazon visibly labeled that
 * exact individual review as such -- confirmed per-review, not assumed.
 *
 * The Amazon Rainforest book (published 2026-06-26) has zero Amazon
 * customer reviews as of this writing -- confirmed by reading its live
 * product page ("There are 0 customer reviews."), not assumed. This is a
 * genuine no-review-state, not a placeholder gap -- no fixture or filler
 * review exists for this book, and none should be added until real
 * reviews exist and are verified the same way as the other two books.
 *
 * Every entry requires BOTH 'approved' => true AND environment safety
 * (see bhp_is_production_site()) to ever render -- see
 * bhp_get_approved_amazon_reviews_for_book().
 */

defined('ABSPATH') || exit;

/**
 * Reliable production/staging distinction for this specific setup --
 * wp_get_environment_type() returns 'local' identically on both
 * braveheartspublishing.com and staging2.braveheartspublishing.com here
 * (confirmed via direct check 2026-07-05, WP_ENVIRONMENT_TYPE is not
 * configured), so it cannot be used to gate anything. A direct host
 * comparison against the known production domain is used instead --
 * the same distinction already relied on throughout this project's
 * staging/production deploy workflow.
 */
function bhp_is_production_site() {
    return home_url() === 'https://braveheartspublishing.com';
}

/**
 * Valid book slugs for Amazon reviews -- intentionally the same
 * vocabulary as bhp_get_adventure_key_from_sku() so a review's book_slug
 * can be checked against a product's own adventure key with no mapping
 * table needed.
 */
const BHP_AMAZON_REVIEW_BOOK_SLUGS = ['mariana_trench', 'mount_everest', 'amazon_rainforest'];

const BHP_AMAZON_REVIEW_BOOK_TITLES = [
    'mariana_trench'   => 'Adventures of Charlotte & Henry: The Mariana Trench',
    'mount_everest'    => 'Adventures of Charlotte & Henry: Mount Everest',
    'amazon_rainforest' => 'Adventures of Charlotte & Henry: The Amazon',
];

function bhp_get_amazon_review_registry() {
    return apply_filters('bhp_amazon_review_registry', [
        [
            'id'                => 'amz-mariana-01',
            'book_slug'         => 'mariana_trench',
            'excerpt'           => 'The adventure is exciting, the facts are woven in so fun and seamlessly, and the length is PERFECT—just right to keep them engaged the whole time without losing interest.',
            'review_title'      => 'Big places need Brave hearts!!',
            'rating'            => 5,
            'verified_purchase' => true,
            'review_date'       => '2026-03-22',
            'source_type'       => 'amazon_customer_review',
            'source_label'      => 'Amazon customer review',
            'source_url'        => 'https://www.amazon.com/portal/customer-reviews/B0GQCCPZLL/ref=cm_cr_dp_d_show_all_top?_encoding=UTF8&ie=UTF8&reviewerType=all_reviews',
            'theme'             => 'reading_accessibility',
            'buyer_concern'     => 'Are the chapters manageable? Is it educational without feeling like schoolwork?',
            'reason_selected'   => 'Directly names chapter length as "PERFECT" and describes facts as woven in "seamlessly" -- concrete evidence against the two most common hesitations (too long, too textbook-like).',
            'approved'          => true,
            'environment'       => 'production',
        ],
        [
            'id'                => 'amz-mariana-02',
            'book_slug'         => 'mariana_trench',
            'excerpt'           => 'The adventure is fun, educational, and extremely well written with a nice flow to read aloud. We read a few chapters each night and gave him something to look forward to at bedtime.',
            'review_title'      => 'Great Adventure Book for Young Readers!!',
            'rating'            => 5,
            'verified_purchase' => true,
            'review_date'       => '2026-04-10',
            'source_type'       => 'amazon_customer_review',
            'source_label'      => 'Amazon customer review',
            'source_url'        => 'https://www.amazon.com/portal/customer-reviews/B0GQCCPZLL/ref=cm_cr_dp_d_show_all_top?_encoding=UTF8&ie=UTF8&reviewerType=all_reviews',
            'theme'             => 'read_aloud_family',
            'buyer_concern'     => 'Is this a good transition/read-aloud book? Will it become a routine?',
            'reason_selected'   => 'Names the exact use case (nightly read-aloud, chapter-a-night bedtime routine) parents searching for a transition book are looking for.',
            'approved'          => true,
            'environment'       => 'production',
        ],
        [
            'id'                => 'amz-mariana-03',
            'book_slug'         => 'mariana_trench',
            'excerpt'           => 'It has quickly become a nightly request before the kids go to bed. They always want to read adventures of Charlotte and Henry.',
            'review_title'      => 'Greatest children’s book ever',
            'rating'            => 5,
            'verified_purchase' => true,
            'review_date'       => '2026-04-21',
            'source_type'       => 'amazon_customer_review',
            'source_label'      => 'Amazon customer review',
            'source_url'        => 'https://www.amazon.com/portal/customer-reviews/B0GQCCPZLL/ref=cm_cr_dp_d_show_all_top?_encoding=UTF8&ie=UTF8&reviewerType=all_reviews',
            'theme'             => 'child_engagement',
            'buyer_concern'     => 'Will my child actually stay interested, or ask for it again?',
            'reason_selected'   => 'Concrete, repeated-engagement evidence ("nightly request", "always want to read") rather than a one-time reaction.',
            'approved'          => true,
            'environment'       => 'production',
        ],
        [
            // Andrew's explicit direction (2026-07-05) for the Complete
            // Collection page testimonial specifically -- includes a
            // reviewer first name ("Payton"), which departs from this
            // registry's usual restrained "Amazon customer review" label
            // (see the file header note). This is a deliberate one-off
            // exception for this entry only, not a change to the general
            // convention; other entries keep omitting reviewer names.
            'id'                => 'amz-mariana-04',
            'book_slug'         => 'mariana_trench',
            'reviewer_name'     => 'Payton',
            'excerpt'           => 'My students were drawn to the vivid setting and sense of exploration. It’s engaging, educational, and a great addition to any classroom or home library.',
            'review_title'      => '',
            'rating'            => 5,
            'verified_purchase' => true,
            'review_date'       => '2026-07-05',
            'source_type'       => 'amazon_customer_review',
            'source_label'      => 'Verified Amazon review',
            'source_url'        => 'https://www.amazon.com/portal/customer-reviews/B0GQCCPZLL/ref=cm_cr_arp_d_paging_btm_2?_encoding=UTF8&ie=UTF8&reviewerType=all_reviews&pageNumber=2&nextPageToken=MjAyNi0wNy0wNVQyMDo0MzozOS41MDM2MjIyNTZaADEw',
            'theme'             => 'classroom_use',
            'buyer_concern'     => 'Is this a good fit for a classroom or homeschool read-aloud, not just casual reading at home?',
            'reason_selected'   => 'Names a teacher-specific use case (classroom or home library) directly, complementing the existing parent/child-focused reviews above.',
            'approved'          => true,
            'environment'       => 'production',
        ],
        [
            'id'                => 'amz-everest-01',
            'book_slug'         => 'mount_everest',
            'excerpt'           => '…an exciting and inspiring story that encourages kids to dream big and embrace adventure. The journey to Mount Everest is filled with fun, learning, and teamwork, making it both entertaining and meaningful for young readers.',
            'review_title'      => 'A thrilling journey to the top of the world!',
            'rating'            => 5,
            'verified_purchase' => true,
            'review_date'       => '2026-05-08',
            'source_type'       => 'amazon_customer_review',
            'source_label'      => 'Amazon customer review',
            'source_url'        => 'https://www.amazon.com/portal/customer-reviews/B0GWJ4PNPZ/ref=cm_cr_dp_d_show_all_top?_encoding=UTF8&ie=UTF8&reviewerType=all_reviews',
            'theme'             => 'educational_value',
            'buyer_concern'     => 'Is this educational without feeling like schoolwork? Does it build character?',
            'reason_selected'   => 'Names both the educational framing (learning) and character framing (teamwork, dreaming big) in one short excerpt -- the strongest available evidence for this title given its small review base.',
            'approved'          => true,
            'environment'       => 'production',
        ],
        [
            'id'                => 'amz-everest-02',
            'book_slug'         => 'mount_everest',
            'excerpt'           => 'Good fun book to read to kids! Easy reading and has great learning qualities to teach young people.',
            'review_title'      => 'Great kids book!',
            'rating'            => 5,
            'verified_purchase' => false, // Not shown as Verified Purchase on Amazon -- must never be displayed as verified.
            'review_date'       => '2026-05-25',
            'source_type'       => 'amazon_customer_review',
            'source_label'      => 'Amazon customer review',
            'source_url'        => 'https://www.amazon.com/portal/customer-reviews/B0GWJ4PNPZ/ref=cm_cr_dp_d_show_all_top?_encoding=UTF8&ie=UTF8&reviewerType=all_reviews',
            'theme'             => 'reading_accessibility',
            'buyer_concern'     => 'Is this easy enough for my child to read independently?',
            'reason_selected'   => 'Directly addresses reading accessibility ("easy reading"); included despite lacking a Verified Purchase badge because Everest’s review base is small (4 ratings, 3 written) and the excerpt is safe, on-topic, and non-misleading -- the missing badge is disclosed, never hidden.',
            'approved'          => true,
            'environment'       => 'production',
        ],
        // Amazon Rainforest ("The Amazon"): zero real reviews exist as of
        // 2026-07-05 (confirmed on the live product page: "There are 0
        // customer reviews."). No entry is added here -- see
        // bhp_get_approved_amazon_reviews_for_book(), which returns an
        // empty array for this book_slug until a real, verified review is
        // added through the same process as the other two books.
    ], BHP_AMAZON_REVIEW_BOOK_SLUGS); // second filter arg for context, matches bhp_homepage_books pattern
}

/**
 * Returns only approved, environment-safe reviews for one book, already
 * validated (unknown book_slug, duplicate IDs, or a review with a
 * book_slug outside BHP_AMAZON_REVIEW_BOOK_SLUGS are all excluded --
 * fail closed, never render something that can't be confidently mapped).
 */
function bhp_get_approved_amazon_reviews_for_book($book_slug) {
    if (!in_array($book_slug, BHP_AMAZON_REVIEW_BOOK_SLUGS, true)) {
        return [];
    }

    $is_production = bhp_is_production_site();
    $seen_ids = [];
    $out = [];

    foreach (bhp_get_amazon_review_registry() as $review) {
        if (($review['book_slug'] ?? '') !== $book_slug) {
            continue;
        }
        if (empty($review['approved'])) {
            continue;
        }
        if ($is_production && ($review['environment'] ?? '') !== 'production') {
            continue; // staging_only fixtures never render on production
        }
        if (empty($review['id']) || empty($review['excerpt']) || empty($review['source_url'])) {
            continue; // fail closed on incomplete data rather than render a broken card
        }
        if (isset($seen_ids[$review['id']])) {
            continue; // duplicate ID -- keep only the first occurrence
        }
        $seen_ids[$review['id']] = true;
        $out[] = $review;
    }

    return $out;
}

function bhp_get_amazon_review_book_title($book_slug) {
    return BHP_AMAZON_REVIEW_BOOK_TITLES[$book_slug] ?? '';
}

/**
 * Resolves a book_slug to its live WooCommerce product page, by SKU
 * prefix (the same prefixes bhp_get_adventure_key_from_sku() reads) --
 * never a hardcoded URL, so a future slug/permalink change can't leave a
 * silently broken "Shop this book" link behind.
 */
function bhp_get_book_product_url($book_slug) {
    $sku_prefixes = ['mariana_trench' => 'BHP-MT-', 'mount_everest' => 'BHP-EVE-', 'amazon_rainforest' => 'BHP-AMZ-'];
    $prefix = $sku_prefixes[$book_slug] ?? '';
    if (!$prefix || !function_exists('wc_get_product')) {
        return '';
    }

    global $wpdb;
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s LIMIT 1",
        $wpdb->esc_like($prefix) . '%'
    ));
    if (!$post_id) {
        return '';
    }

    $url = get_permalink((int) $post_id);
    return function_exists('bhp_get_safe_link_url') ? bhp_get_safe_link_url($url) : ($url ?: '');
}
