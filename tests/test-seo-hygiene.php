<?php
/**
 * Brave Hearts — the crawl-hygiene layer must kill the thin feeds and nothing else.
 *
 * CYCLE152-LD-SEO-FEED-HYGIENE (2026-08-10, theme 1.19.217).
 *
 * ⭐ THE TWO INVARIANTS EVERYTHING HERE DEFENDS:
 *
 *   1. Every per-term, per-author, per-search and per-comment feed is a 301 to
 *      its parent, and is no longer advertised in `<head>`.
 *   2. **The main post feed and every line of robots.txt this theme did not
 *      write survive untouched.** This is the half that matters: the failure
 *      mode of an SEO "cleanup" is not that it does too little, it is that it
 *      silently disallows something that was earning money.
 *
 * ⭐ WHY THE ROBOTS TESTS RUN AGAINST CAPTURED PRODUCTION OUTPUT. Asserting
 *    that our three lines appear is easy and proves almost nothing. The
 *    assertion with teeth is that WooCommerce's five rules, core's two and
 *    Rank Math's `Sitemap:` line are all still present, byte for byte, in the
 *    output our filter returns. The fixture below is the real body captured
 *    from `https://braveheartspublishing.com/robots.txt` on 2026-08-10, before
 *    this release.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-seo-hygiene.php --user=1
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

$failures = 0;
$skipped  = 0;

function bhp_seoh_assert(&$failures, $label, $condition) {
    if ($condition) {
        echo "PASS: {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL: {$label}\n";
}

function bhp_seoh_skip(&$skipped, $label, $why) {
    $skipped++;
    echo "SKIP: {$label} — {$why}\n";
}

echo "=== SEO crawl hygiene ===\n";

/* ------------------------------------------------------------------ *
 * 0. The module is actually loaded.
 * ------------------------------------------------------------------ */

bhp_seoh_assert($failures, 'seo-hygiene.php is loaded (classifier exists)',
    function_exists('bhp_seo_thin_feed_kind'));
bhp_seoh_assert($failures, 'seo-hygiene.php is loaded (robots injector exists)',
    function_exists('bhp_seo_inject_robots_rules'));

if (!function_exists('bhp_seo_thin_feed_kind')) {
    echo "\nFATAL: module not loaded; remaining assertions cannot run.\n";
    exit(1);
}

/* ------------------------------------------------------------------ *
 * 1. THE CLASSIFIER — every branch, including the ones this site
 *    cannot currently produce.
 * ------------------------------------------------------------------ */

$scenarios = array(
    // label,                                   state,                                                   expected
    array('a non-feed request is never thin',   array(),                                                 ''),
    array('a non-feed tag archive is never thin', array('is_tag' => true),                                ''),
    array('THE MAIN SITE FEED IS NOT THIN',     array('is_feed' => true),                                ''),
    array('a post-type-archive feed is left alone', array('is_feed' => true, 'is_post_type_archive' => true), ''),
    array('a category feed is thin',            array('is_feed' => true, 'is_category' => true),          'term'),
    array('a tag feed is thin',                 array('is_feed' => true, 'is_tag' => true),               'term'),
    array('a product-tag feed is thin',         array('is_feed' => true, 'is_tax' => true),               'term'),
    array('an author feed is thin',             array('is_feed' => true, 'is_author' => true),            'author'),
    array('a search feed is thin',              array('is_feed' => true, 'is_search' => true),            'search'),
    array('a comment feed is thin',             array('is_feed' => true, 'is_comment_feed' => true),      'comment'),
    array('a comment feed on a term archive files as a comment feed, not a term feed',
        array('is_feed' => true, 'is_comment_feed' => true, 'is_tag' => true),                            'comment'),
    array('a singular post comment feed is thin',
        array('is_feed' => true, 'is_comment_feed' => true, 'is_singular' => true),                       'comment'),
    // The flags are booleans in WordPress but arrive as truthy/falsy values in
    // enough code paths that treating '0' and null as false is worth pinning.
    array('falsy flags do not trip the classifier',
        array('is_feed' => true, 'is_tag' => 0, 'is_tax' => null, 'is_author' => false, 'is_search' => ''), ''),
);

foreach ($scenarios as $s) {
    list($label, $state, $expected) = $s;
    $got = bhp_seo_thin_feed_kind($state);
    bhp_seoh_assert($failures, "classifier: {$label} (expected '{$expected}', got '{$got}')",
        $got === $expected);
}

/* ------------------------------------------------------------------ *
 * 2. THE MAIN FEED SURFACE IS UNCHANGED.
 * ------------------------------------------------------------------ */

$rss2 = get_bloginfo('rss2_url');
bhp_seoh_assert($failures, "the site feed URL is still /feed/ ({$rss2})",
    (bool) preg_match('#/feed/?$#', (string) $rss2));

bhp_seoh_assert($failures, 'the site feed link is still emitted (feed_links hooked at 2)',
    2 === has_action('wp_head', 'feed_links'));

bhp_seoh_assert($failures, 'the posts-feed link is NOT suppressed',
    true === apply_filters('feed_links_show_posts_feed', true));

/* ------------------------------------------------------------------ *
 * 3. THE EXTRA FEED LINKS ARE GONE.
 * ------------------------------------------------------------------ */

// `after_setup_theme` has already fired by the time wp eval-file runs.
bhp_seoh_assert($failures, 'feed_links_extra is no longer hooked to wp_head',
    false === has_action('wp_head', 'feed_links_extra'));

bhp_seoh_assert($failures, 'the global comments-feed link is suppressed',
    false === apply_filters('feed_links_show_comments_feed', true));

/* ------------------------------------------------------------------ *
 * 4. THE REDIRECT IS WIRED, EARLY.
 * ------------------------------------------------------------------ */

bhp_seoh_assert($failures, 'the thin-feed redirect runs on template_redirect at priority 1',
    1 === has_action('template_redirect', 'bhp_seo_redirect_thin_feeds'));

/* ------------------------------------------------------------------ *
 * 5. ROBOTS.TXT — against the REAL captured body.
 * ------------------------------------------------------------------ */

$captured = "User-agent: *\n"
    . "Disallow: /wp-content/uploads/wc-logs/\n"
    . "Disallow: /wp-content/uploads/woocommerce_transient_files/\n"
    . "Disallow: /wp-content/uploads/woocommerce_uploads/\n"
    . "Disallow: /*?add-to-cart=\n"
    . "Disallow: /*?*add-to-cart=\n"
    . "Disallow: /wp-admin/\n"
    . "Allow: /wp-admin/admin-ajax.php\n"
    . "\n"
    . "Sitemap: https://braveheartspublishing.com/sitemap_index.xml\n";

$injected = bhp_seo_inject_robots_rules($captured);

foreach (bhp_seo_robots_rules() as $rule) {
    bhp_seoh_assert($failures, "robots: our rule is present — {$rule}",
        false !== strpos($injected, $rule));
}

// The one that matters: nothing anyone else wrote may go missing.
$preexisting = array_filter(array_map('trim', explode("\n", $captured)));
$lost = array();
foreach ($preexisting as $line) {
    if (false === strpos($injected, $line)) {
        $lost[] = $line;
    }
}
bhp_seoh_assert($failures,
    'robots: EVERY pre-existing line survives (' . count($preexisting) . ' checked, ' . count($lost) . ' lost)',
    empty($lost));
if ($lost) {
    foreach ($lost as $line) {
        echo "      lost: {$line}\n";
    }
}

bhp_seoh_assert($failures, 'robots: the Sitemap line survives',
    false !== strpos($injected, 'Sitemap: https://braveheartspublishing.com/sitemap_index.xml'));

bhp_seoh_assert($failures, 'robots: exactly one User-agent group is present',
    1 === substr_count($injected, 'User-agent:'));

// Our rules must land INSIDE the wildcard group, not after the Sitemap line.
$ua_at   = strpos($injected, 'User-agent: *');
$rule_at = strpos($injected, 'Disallow: /*?wc-ajax=');
$map_at  = strpos($injected, 'Sitemap:');
bhp_seoh_assert($failures, 'robots: our rules sit inside the User-agent group, above the Sitemap line',
    false !== $ua_at && false !== $rule_at && false !== $map_at && $ua_at < $rule_at && $rule_at < $map_at);

// Idempotency — a second pass must be a byte-for-byte no-op.
bhp_seoh_assert($failures, 'robots: injection is idempotent',
    bhp_seo_inject_robots_rules($injected) === $injected);

// A body that already blocks the whole site must be left undiluted, and the
// guard must key on the BODY, not on blog_public — staging is blog_public='0'
// and still receives a public-form body from Rank Math.
$blocked = "User-agent: *\nDisallow: /\n";
bhp_seoh_assert($failures, 'robots: a site-wide block is detected',
    true === bhp_seo_robots_is_site_blocked($blocked));
bhp_seoh_assert($failures, 'robots: a normal body is not mistaken for a site-wide block',
    false === bhp_seo_robots_is_site_blocked($captured));
bhp_seoh_assert($failures, 'robots: `Disallow: /wp-admin/` is NOT mistaken for a site-wide block',
    false === bhp_seo_robots_is_site_blocked("User-agent: *\nDisallow: /wp-admin/\n"));
bhp_seoh_assert($failures, 'robots: a body that already blocks the whole site is left completely alone',
    bhp_seo_filter_robots_txt($blocked, true) === $blocked
    && bhp_seo_filter_robots_txt($blocked, false) === $blocked);
bhp_seoh_assert($failures, 'robots: the guard does NOT key on blog_public',
    bhp_seo_filter_robots_txt($captured, false) === bhp_seo_filter_robots_txt($captured, true));

// No wildcard group present: append our own rather than mis-scoping someone else's.
$no_anchor = bhp_seo_inject_robots_rules("User-agent: Googlebot\nDisallow: /private/\n");
bhp_seoh_assert($failures, 'robots: with no wildcard group, another agent\'s group is not hijacked',
    false !== strpos($no_anchor, "User-agent: Googlebot\nDisallow: /private/\n")
    && strpos($no_anchor, 'Disallow: /*?wc-ajax=') > strpos($no_anchor, 'User-agent: *'));

/* ------------------------------------------------------------------ *
 * 6. SAFETY: what our rules must NEVER say.
 * ------------------------------------------------------------------ */

$rules = bhp_seo_robots_rules();

bhp_seoh_assert($failures, 'safety: no rule blocks the whole site',
    !in_array('Disallow: /', $rules, true));

$forbidden = array('/blog/', '/feed/', '/complete-collection/', '/books/', '/product/', '/cart/', '/checkout/');
$bad = array();
foreach ($rules as $rule) {
    foreach ($forbidden as $path) {
        if (false !== strpos($rule, $path)) {
            $bad[] = "{$rule} touches {$path}";
        }
    }
}
bhp_seoh_assert($failures, 'safety: no rule touches the blog, the main feed or a commerce path',
    empty($bad));
foreach ($bad as $b) {
    echo "      {$b}\n";
}

bhp_seoh_assert($failures, 'safety: every rule is a Disallow, never an Allow override',
    count($rules) === count(array_filter($rules, function ($r) { return 0 === strpos($r, 'Disallow: '); })));

/* ------------------------------------------------------------------ *
 * 7. LIVE: the real generated robots.txt still carries everyone's rules.
 * ------------------------------------------------------------------ */

if (function_exists('do_robots')) {
    ob_start();
    do_robots();
    $live = ob_get_clean();

    $expect_live = array(
        'Disallow: /*?wc-ajax=',
        'Disallow: /search/',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /*?add-to-cart=',
        'Sitemap:',
    );
    foreach ($expect_live as $needle) {
        bhp_seoh_assert($failures, "live robots.txt contains: {$needle}",
            false !== strpos($live, $needle));
    }
    bhp_seoh_assert($failures, 'live robots.txt does NOT disallow the whole site',
        0 === preg_match('/^Disallow:\s*\/\s*$/m', $live));
} else {
    bhp_seoh_skip($skipped, 'live robots.txt generation', 'do_robots() unavailable');
}

/* ------------------------------------------------------------------ *
 * 8. LIVE: the redirect targets resolve to real, existing archives.
 * ------------------------------------------------------------------ */

$tags = get_terms(array('taxonomy' => 'post_tag', 'number' => 1, 'hide_empty' => false));
if (!is_wp_error($tags) && !empty($tags)) {
    $link = get_term_link($tags[0]);
    bhp_seoh_assert($failures, "a real tag archive link resolves ({$link})",
        !is_wp_error($link) && false !== strpos((string) $link, '/tag/'));
    bhp_seoh_assert($failures, 'the tag archive link is NOT itself a feed URL',
        !preg_match('#/feed/?$#', (string) $link));
} else {
    bhp_seoh_skip($skipped, 'real tag archive link', 'no post_tag terms found');
}

$author_link = get_author_posts_url(1);
bhp_seoh_assert($failures, "a real author archive link resolves ({$author_link})",
    is_string($author_link) && false !== strpos($author_link, '/author/'));

/* ------------------------------------------------------------------ */

echo "\n";
echo "SKIPPED: {$skipped}\n";
echo "FAILURES: {$failures}\n";

if ($failures > 0) {
    exit(1);
}
echo "ALL SEO CRAWL-HYGIENE ASSERTIONS PASS\n";
