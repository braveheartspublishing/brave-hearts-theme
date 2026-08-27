<?php
/**
 * Brave Hearts — affiliate links are never touched (Standing Rules section 26).
 *
 * CYCLE166-CX-AFFILIATE-RESTORE (2026-08-26, theme 1.19.294).
 *
 * ⛔ THIS SUITE EXISTS BECAUSE AN AFFILIATE LINK IS A PAYMENT INSTRUMENT THAT
 *    HAPPENS TO LOOK LIKE AN ANCHOR TAG.
 *
 * The governing rule is Standing Rules section 26, sealed at FD-694, and it is
 * deliberately NOT restated in this public repository -- read it there.
 *
 * Why it needs a test at all: these links earn into the owner's personal Amazon
 * Associates account. When one is lost, nothing breaks, no test goes red, and
 * no error is logged -- the money simply stops, silently, until a human
 * notices. In the incident that produced this suite, weeks passed before
 * anyone did, and it was the owner who noticed, not the build.
 *
 * Section 26.3 makes the rule checkable with a deliberately crude test:
 *
 *     COUNT THE AFFILIATE LINKS BEFORE. COUNT THEM AFTER.
 *     THE AFTER NUMBER MAY NEVER BE LOWER.
 *
 * This suite is the automated form of that count, pinned to the inventory
 * recovered from production revision rows 559 (post 28) and 480 (post 88) and
 * hash-validated before use:
 *     5f8639bd321e3832e36f1da2bbad0db5  post 28 rev 559  12 anchors
 *     714ff15548a17369c222ae7dc4a786d2  post 88 rev 480   9 anchors
 *
 * ⚠ IF THIS SUITE FAILS ON A COUNT, DO NOT "FIX" IT BY LOWERING THE EXPECTED
 *   NUMBER. A drop means a placement was lost and revenue went with it. Find
 *   what removed it. Section 26.6: a before/after count that was not actually
 *   run is a FABRICATED CHECK and sits in the same failure class as a
 *   fabricated review.
 *
 * ⚠ HONEST LIMIT, stated so nobody over-trusts this file: `amzn.to` short
 *   links hide their `tag=` behind a redirect, so a source-side count cannot
 *   confirm the tracking code is intact. This suite proves the ANCHOR is
 *   present and unaltered. Proving the CODE still earns is link resolution or
 *   the founder's own Associates dashboard, which is his instrument, not this
 *   file's. Resolution was performed by hand on 2026-08-26 for all 14 distinct
 *   shortlinks; every one returned 301 carrying `tag=bravehearts0e-20`.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle166-affiliate-restore.php --user=1
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

$failures = 0;

function bhp_aff_assert(&$failures, $label, $condition) {
    if ($condition) {
        echo "PASS: {$label}\n";
        return true;
    }
    $failures++;
    echo "FAIL: {$label}\n";
    return false;
}

function bhp_aff_inventory($html) {
    $out = [];
    if (preg_match_all('~amzn\.to/([A-Za-z0-9]+)~i', (string) $html, $m)) {
        foreach ($m[1] as $slug) {
            $out[$slug] = isset($out[$slug]) ? $out[$slug] + 1 : 1;
        }
    }
    ksort($out);
    return $out;
}

// ---------------------------------------------------------------------------
// 1. THE COUNT-DECREASE GUARD — the reason this file exists.
// ---------------------------------------------------------------------------
/*
 * Expected inventories are per-shortlink, not just totals. A naive total would
 * pass if one link were swapped for a duplicate of another, which is exactly
 * the silent-substitution failure that loses a placement without losing a count.
 */
$expected = [
    28 => [
        '3PFKexe' => 1, '3RlKS3x' => 1, '42RbPP0' => 1, '4mptuGv' => 3,
        '4svChYL' => 3, '4urr79a' => 1, '4uzvoYn' => 1, '4wPVjfQ' => 1,
    ],
    88 => [
        '3QpnT7d' => 1, '4cJ8BkZ' => 1, '4mptuGv' => 1, '4svChYL' => 2,
        '4tBPd0L' => 1, '4u958TM' => 1, '4uakesg' => 1, '4vOBwgt' => 1,
    ],
];

foreach ($expected as $post_id => $want) {
    $post = get_post($post_id);
    if (!bhp_aff_assert($failures, "post {$post_id} exists", $post instanceof WP_Post)) {
        continue;
    }

    $raw  = bhp_aff_inventory($post->post_content);
    $want_total = array_sum($want);
    $raw_total  = array_sum($raw);

    bhp_aff_assert(
        $failures,
        "post {$post_id} stored affiliate anchor count is {$want_total} (found {$raw_total})",
        $raw_total >= $want_total
    );
    bhp_aff_assert(
        $failures,
        "post {$post_id} stored per-shortlink inventory matches the recovered revision row",
        $raw === $want
    );

    // The RENDERED count is what section 26.3 actually asks for -- a filter or a
    // build step can strip a link that is present in the database.
    $rendered = apply_filters('the_content', $post->post_content);
    $rendered_total = array_sum(bhp_aff_inventory($rendered));
    bhp_aff_assert(
        $failures,
        "post {$post_id} RENDERED affiliate anchor count is not lower than stored ({$rendered_total} vs {$raw_total})",
        $rendered_total >= $raw_total
    );
}

// ---------------------------------------------------------------------------
// 2. THE TRACKING FILTER MUST NEVER ALTER AN href.
// ---------------------------------------------------------------------------
if (bhp_aff_assert($failures, 'bhp_affiliate_content_tracking() is defined', function_exists('bhp_affiliate_content_tracking'))) {

    $sample = '<p>A <a href="https://amzn.to/4svChYL">book</a> and '
            . '<a href="https://amzn.to/3PFKexe" rel="nofollow">another</a> and '
            . '<a href="https://braveheartspublishing.com/books/">an internal one</a>.</p>';

    // Filters that gate on is_singular()/in_the_loop() cannot be exercised
    // directly here, so the anchor-rewriting core is checked through the same
    // WP_HTML_Tag_Processor path the filter uses, with the guards satisfied by
    // calling the filter inside a real post context below.
    $before_hrefs = [];
    preg_match_all('~href="([^"]+)"~', $sample, $m);
    $before_hrefs = $m[1];

    $post_28 = get_post(28);
    if ($post_28 instanceof WP_Post) {
        /*
         * ⚠ `$wp_the_query` MUST BE SET ALONGSIDE `$wp_query`, AND THIS COST A
         *   FALSE FAILURE THE FIRST TIME THIS SUITE RAN.
         *
         * `bhp_affiliate_content_tracking()` guards on `is_main_query()`, which
         * compares `$wp_query` against `$wp_the_query`. Building a fresh
         * WP_Query and assigning only `$wp_query` leaves them different objects,
         * so the guard returns false, the filter returns the content untouched,
         * and the suite reports "the filter does not add tracking" about code
         * that works perfectly on a real page load. It was verified working in a
         * browser on staging at the moment this comment was written: all 12
         * anchors on post 28 carried data-bhp-event, and every href was intact.
         *
         * A test that fails for its own reasons is worse than no test — it
         * trains whoever sees it to ignore a red suite.
         */
        global $wp_query, $wp_the_query, $post;
        $original_query     = $wp_query;
        $original_the_query = $wp_the_query;
        $original_post      = $post;

        $wp_query     = new WP_Query(['p' => 28, 'post_type' => 'post']);
        $wp_the_query = $wp_query;
        if ($wp_query->have_posts()) {
            $wp_query->the_post();
            $filtered = bhp_affiliate_content_tracking($post_28->post_content);

            preg_match_all('~href="([^"]+)"~', $post_28->post_content, $src_m);
            preg_match_all('~href="([^"]+)"~', $filtered, $out_m);

            bhp_aff_assert(
                $failures,
                'tracking filter leaves every href byte-identical',
                $src_m[1] === $out_m[1]
            );
            bhp_aff_assert(
                $failures,
                'tracking filter adds data-bhp-event to every affiliate anchor',
                substr_count($filtered, 'data-bhp-event="amazon_outbound_click"') === array_sum(bhp_aff_inventory($post_28->post_content))
            );
            /*
             * Asserted on `noopener` rather than `sponsored`: the restored post
             * content already ships `rel="nofollow sponsored"`, so a `sponsored`
             * assertion would pass vacuously whether or not the filter ran.
             * `noopener` is added ONLY by the filter, so it actually discriminates.
             */
            bhp_aff_assert(
                $failures,
                'tracking filter completes rel on affiliate anchors (noopener added)',
                substr_count($filtered, 'noopener') >= array_sum(bhp_aff_inventory($post_28->post_content))
            );
            // Idempotence: running it twice must not stack attributes.
            $twice = bhp_affiliate_content_tracking($filtered);
            bhp_aff_assert(
                $failures,
                'tracking filter is idempotent',
                substr_count($twice, 'data-bhp-event="amazon_outbound_click"') === substr_count($filtered, 'data-bhp-event="amazon_outbound_click"')
            );
        }
        wp_reset_postdata();
        $wp_query     = $original_query;
        $wp_the_query = $original_the_query;
        $post         = $original_post;
    }
    unset($before_hrefs);
}

// ---------------------------------------------------------------------------
// 3. THE APPROVED-LINK SOURCE OF TRUTH.
// ---------------------------------------------------------------------------
if (bhp_aff_assert($failures, 'bhp_get_amazon_affiliate_urls() is defined', function_exists('bhp_get_amazon_affiliate_urls'))) {
    $urls = bhp_get_amazon_affiliate_urls();
    foreach (['mariana_trench', 'mount_everest', 'amazon_rainforest'] as $key) {
        bhp_aff_assert(
            $failures,
            "approved affiliate URL present and https for {$key}",
            !empty($urls[$key]) && 0 === strpos($urls[$key], 'https://amzn.to/')
        );
    }
}

// ---------------------------------------------------------------------------
// 4. THE SHOP-ARCHIVE BLOCK.
// ---------------------------------------------------------------------------
if (bhp_aff_assert($failures, 'bhp_shop_amazon_availability_block() is defined', function_exists('bhp_shop_amazon_availability_block'))) {
    bhp_aff_assert(
        $failures,
        'shop block is hooked to woocommerce_after_shop_loop',
        false !== has_action('woocommerce_after_shop_loop', 'bhp_shop_amazon_availability_block')
    );

    /*
     * The block short-circuits on !is_shop(), so it cannot simply be echoed
     * here. Its markup is asserted through the source instead -- deliberately
     * narrow, and stated as such rather than dressed up as a render test.
     */
    $src = file_get_contents(get_template_directory() . '/functions.php');
    $start = strpos($src, 'function bhp_shop_amazon_availability_block');
    $block = false === $start ? '' : substr($src, $start);

    bhp_aff_assert($failures, 'shop block emits no aggregateRating', false === stripos($block, 'aggregateRating'));
    bhp_aff_assert($failures, 'shop block emits no review schema',   false === stripos($block, '"Review"'));
    bhp_aff_assert($failures, 'shop block carries the sitewide disclosure', false !== strpos($block, 'bhp_get_amazon_disclosure_text'));
    bhp_aff_assert($failures, 'shop block marks links rel=sponsored', false !== strpos($block, 'nofollow sponsored noopener'));
    /*
     * Hardcover and Kindle stay unadvertised until their royalty economics are
     * documented. The block names titles only, never a format.
     */
    bhp_aff_assert($failures, 'shop block never names hardcover', false === stripos($block, 'hardcover'));
    bhp_aff_assert($failures, 'shop block never names Kindle',    false === stripos($block, 'kindle'));
    /*
     * The block must not hardcode a second copy of the links -- it reads the
     * existing single source of truth.
     */
    bhp_aff_assert($failures, 'shop block does not hardcode a shortlink', false === strpos($block, 'amzn.to/'));
}

// ---------------------------------------------------------------------------
// 5. NO AFFILIATE LINK IS HARDCODED INTO A TEMPLATE OUTSIDE THE APPROVED LIST.
// ---------------------------------------------------------------------------
/*
 * Section 26.4 observed ZERO hardcoded `tag=...-20` strings in theme PHP, which
 * is what makes post revisions the right recovery instrument. This pins that
 * property so a future pass cannot quietly scatter raw tagged URLs into
 * templates, where they would be invisible to a content-side audit.
 */
$php_files = glob(get_template_directory() . '/*.php');
$tagged = [];
foreach ((array) $php_files as $file) {
    $body = (string) file_get_contents($file);
    if (preg_match('~tag=[A-Za-z0-9]+-20~', $body)) {
        $tagged[] = basename($file);
    }
}
bhp_aff_assert(
    $failures,
    'no raw tag=...-20 affiliate string is hardcoded in top-level theme PHP',
    empty($tagged)
);

echo "\n";
if ($failures > 0) {
    echo "RESULT: {$failures} failure(s)\n";
    exit(1);
}
echo "RESULT: all affiliate-preservation checks passed\n";
