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

bhp_seoh_assert($failures, 'feed_links is STILL hooked at wp_head priority 2 — this release does not unhook it',
    2 === has_action('wp_head', 'feed_links'));

bhp_seoh_assert($failures, 'the posts-feed link is NOT suppressed',
    true === apply_filters('feed_links_show_posts_feed', true));

/*
 * ⚠ OBSERVED, PRE-EXISTING, AND RECORDED SO IT IS NOT MISREAD AS THIS
 *   RELEASE'S DOING. `feed_links()` returns early unless the theme declares
 *   `add_theme_support('automatic-feed-links')`, and this theme never has —
 *   verified on PRODUCTION at 1.19.216, before this release, where
 *   `current_theme_supports('automatic-feed-links')` is false and no page
 *   carries a site-feed <link>. `feed_links_extra()` has no such guard, which
 *   is why the ONLY feed links this site was ever emitting were the thin
 *   taxonomy ones this release removes.
 *
 *   The URL itself is unaffected either way: /feed/ still serves the real feed.
 *   Printed rather than asserted, because adding theme support later would be
 *   an improvement and must not turn into a red test.
 */
echo 'INFO: automatic-feed-links theme support = '
    . var_export(current_theme_supports('automatic-feed-links'), true)
    . " (pre-existing; false means no site-feed <link> is printed, by core's own guard)\n";

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

/* ------------------------------------------------------------------ *
 * 9. 1.19.272 (`CYCLE165-LD-ITERATE-8-FINAL`, `CYCLE165-MKT-302`, founder
 *    ruling item 121) — A URL THAT 301s NEVER ENTERS THE SITEMAP.
 *
 * The filter is exercised DIRECTLY with synthetic entries, because the three
 * limbs cannot all be observed in one environment's live sitemap: the Rank
 * Math bridge redirections live in the PRODUCTION database and do not exist on
 * staging2 (verified 2026-08-19). Exercising the function is the honest way to
 * prove the mechanism on either environment; the live sitemap fetch that
 * proves the OUTCOME is a separate, environment-specific check.
 * ------------------------------------------------------------------ */

$entry = function ($path) {
    return array('loc' => home_url($path), 'mod' => '2026-01-01T00:00:00+00:00');
};

/* limb 0 — /checkout/ is never advertised, redirect or no redirect. */
bhp_seoh_assert($failures, 'sitemap: /checkout/ is excluded by name (item 121; a checkout URL is never canonical)',
    array() === bhp_seo_exclude_redirected_from_sitemap($entry('/checkout/'), 'post', null));

/* limb 1 — the theme's own named 301. */
bhp_seoh_assert($failures, 'sitemap: /teachers-guide/ is still excluded (CYCLE164 rule survives the generalisation)',
    array() === bhp_seo_exclude_redirected_from_sitemap($entry('/teachers-guide/'), 'post', null));

/* THE FLOOR — the assertion that catches a broad redirect pattern gutting the
   sitemap. Every one of these must SURVIVE the filter. */
/*
 * ⭐⭐ 1.19.285 — CARRIER ITEM 209: `/books/` MOVES OFF THE FLOOR, BY THIS
 *     FILE'S OWN RULE. The superseded line, verbatim:
 *
 *       $floor = array('/', '/shop/', '/complete-collection/', '/blog/', '/teachers/', '/about/', '/books/');
 *
 * Andrew Signore, carrier item 209, 2026-08-21 (⚠️ RELAYED through
 * `chief-of-staff`, ⛔ NOT witnessed first-hand by the agent that wrote this).
 * `/books/` now answers a 301 to `/shop/` (`bhp_redirect_books_to_shop()`), so
 * the 1.19.272 rule this suite exists to enforce — **a URL that 301s never
 * enters the sitemap** — now applies to it.
 *
 * ⛔ THIS IS NOT A WEAKENING, IT IS THE RULE FIRING. Leaving /books/ on the
 *    floor would have asserted that a redirected URL MUST be advertised to
 *    Google — the suite demanding the precise defect it was written to end.
 *    It is not silently deleted either: it moves to the EXCLUSION side and is
 *    asserted there, so the coverage count does not drop.
 *
 * ⛔ /shop/ STAYS ON THE FLOOR AND THAT IS THE LOAD-BEARING HALF. It is the
 *    merge's destination; if the new registration ever over-matched and took
 *    /shop/ with it, the storefront would leave the sitemap and this row is
 *    what says so.
 */
$books_merged = function_exists('bhp_redirect_books_to_shop');
$floor = array('/', '/shop/', '/complete-collection/', '/blog/', '/teachers/', '/about/');
if (!$books_merged) {
    $floor[] = '/books/';
}
foreach ($floor as $keep) {
    $kept = bhp_seo_exclude_redirected_from_sitemap($entry($keep), 'post', null);
    bhp_seoh_assert($failures, "sitemap FLOOR: {$keep} is NOT excluded",
        is_array($kept) && !empty($kept['loc']));
}

/* ⭐ THE OTHER HALF OF THE ITEM-209 MOVE. /books/ left the floor above; it is
   asserted HERE rather than dropped, so the suite still says something about
   it and a registration that silently stopped working would fail. */
if ($books_merged) {
    bhp_seoh_assert($failures, 'sitemap: /books/ IS excluded — it 301s to /shop/ (carrier item 209, 1.19.285)',
        array() === bhp_seo_exclude_redirected_from_sitemap($entry('/books/'), 'post', null));
}

/* limb 2 — every NON-CANONICAL format edition is excluded, by rule, not by
   slug; and every CANONICAL one survives. This is the three hardcover product
   URLs from MKT-302 §2.3, generalised. */
if (function_exists('bhp_get_series_adventures') && function_exists('bhp_book_lookup_product')) {
    $checked_hc = 0;
    $checked_pb = 0;
    foreach (get_posts(array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true)) as $prod) {
        $found = bhp_book_lookup_product($prod->ID);
        if (!is_array($found)) {
            continue;
        }
        $e = array('loc' => get_permalink($prod), 'mod' => '2026-01-01T00:00:00+00:00');
        $r = bhp_seo_exclude_redirected_from_sitemap($e, 'post', $prod);
        if (empty($found['canonical'])) {
            ++$checked_hc;
            bhp_seoh_assert($failures, "sitemap: non-canonical format edition excluded — {$prod->post_name}",
                array() === $r);
        } else {
            ++$checked_pb;
            bhp_seoh_assert($failures, "sitemap: CANONICAL edition kept — {$prod->post_name}",
                is_array($r) && !empty($r['loc']));
        }
    }
    bhp_seoh_assert($failures, "sitemap: at least one non-canonical and one canonical edition were actually tested (hc={$checked_hc}, pb={$checked_pb})",
        $checked_hc > 0 && $checked_pb > 0);
} else {
    bhp_seoh_skip($skipped, 'format-edition sitemap exclusion', 'book registry unavailable');
}

/* limb 3 — Rank Math's own active 3xx redirections, matched with Rank Math's
   own matcher. Environment-dependent by design: whatever redirections THIS
   environment holds, their sources must be excluded and their destinations
   must not be. */
if (class_exists('\RankMath\Redirections\DB') && method_exists('\RankMath\Redirections\DB', 'get_redirections')) {
    $rows = \RankMath\Redirections\DB::get_redirections(array('limit' => 200, 'status' => 'active'));
    $rows = isset($rows['redirections']) ? (array) $rows['redirections'] : array();
    bhp_seoh_assert($failures, sprintf('sitemap: Rank Math redirections are readable (%d active on this environment)', count($rows)), true);

    $tested = 0;
    foreach ($rows as $row) {
        $code = (int) ($row['header_code'] ?? 0);
        if ($code < 300 || $code > 399) {
            continue;
        }
        foreach ((array) maybe_unserialize($row['sources'] ?? '') as $src) {
            $pattern = (string) ($src['pattern'] ?? '');
            if ('' === $pattern || 'exact' !== (string) ($src['comparison'] ?? '')) {
                continue; // Only exact sources can be turned back into a URL to test.
            }
            ++$tested;
            bhp_seoh_assert($failures, "sitemap: an active Rank Math 3xx source is excluded — /{$pattern}",
                array() === bhp_seo_exclude_redirected_from_sitemap($entry('/' . ltrim($pattern, '/')), 'post', null));

            $to = untrailingslashit((string) wp_parse_url((string) ($row['url_to'] ?? ''), PHP_URL_PATH));
            if ('' !== $to && '/' !== $to) {
                $kept = bhp_seo_exclude_redirected_from_sitemap($entry($to . '/'), 'post', null);
                bhp_seoh_assert($failures, "sitemap: …and its DESTINATION {$to}/ is kept",
                    is_array($kept) && !empty($kept['loc']));
            }
        }
    }
    if (0 === $tested) {
        bhp_seoh_skip($skipped, 'Rank Math redirection exclusion', 'no active exact-match 3xx redirection on this environment');
    }
} else {
    bhp_seoh_skip($skipped, 'Rank Math redirection exclusion', 'Redirections module unavailable');
}

/* ------------------------------------------------------------------ */

echo "\n";
echo "SKIPPED: {$skipped}\n";
echo "FAILURES: {$failures}\n";

if ($failures > 0) {
    exit(1);
}
echo "ALL SEO CRAWL-HYGIENE ASSERTIONS PASS\n";
