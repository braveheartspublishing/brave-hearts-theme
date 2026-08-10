<?php
/**
 * Brave Hearts — crawl hygiene for thin, machine-generated URLs.
 *
 * CYCLE152-LD-SEO-FEED-HYGIENE (2026-08-10, theme 1.19.217).
 *
 * ⭐ WHY THIS FILE EXISTS — the evidence, not a theory.
 *
 * The founder's Google Search Console export shows 102 URLs under
 * "Crawled - currently not indexed". Every one of them is a machine-generated
 * URL that no human was ever meant to open:
 *
 *   - taxonomy RSS feeds  (`/blog/tag/<term>/feed/`, `/blog/category/<term>/feed/`)
 *   - product-tag feeds   (`/product-tag/<term>/feed/`)
 *   - author feeds        (`/author/<slug>/feed/`)
 *   - one `?wc-ajax=` endpoint URL
 *
 * The count went from roughly zero to over a hundred in early July, when the
 * blog tag taxonomy expanded. There are 137 `post_tag` terms and 12
 * `product_tag` terms on this site (counted live, 2026-08-10), and WordPress
 * mints a feed URL for every single one of them, advertises it in the archive's
 * `<head>`, and serves it at HTTP 200. Google crawls them, finds a duplicate of
 * content it already has, and files them as crawled-not-indexed.
 *
 * ⛔ WHAT THIS FILE DOES NOT CLAIM. It does not claim that these URLs caused
 *    the two article ranking demotions. That is a hypothesis and it is recorded
 *    as one. What is observed is narrower and sufficient on its own: a hundred
 *    thin duplicate URLs are being crawled, they carry no value to any reader,
 *    and removing them costs nothing.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ⭐ THE THREE DECISIONS, AND WHY EACH WENT THE WAY IT DID
 * ─────────────────────────────────────────────────────────────────────────
 *
 * 1. **301 REDIRECT, NOT 404, NOT 410.**
 *
 *    A 404 tells Google "this never existed", which is false — these URLs
 *    existed and were crawled. A 410 says "permanently gone", which is true
 *    but throws away the one useful thing the URL still has: it points at a
 *    real archive that does exist. The WordPress-ecosystem-idiomatic answer,
 *    and the one Yoast's own "remove feeds" feature uses, is a **301 to the
 *    feed's parent**: `/blog/tag/x/feed/` -> `/blog/tag/x/`. Google resolves
 *    the thin URL to a URL it already knows, consolidates any signal, and
 *    stops re-crawling it. Nothing 404s, so no error appears in Search Console
 *    that a future reader has to triage.
 *
 *    ⚠ The parent archives for tags, product tags and authors are themselves
 *    `noindex` (Rank Math, verified live 2026-08-10). Redirecting a feed onto
 *    a noindex page is deliberate and correct — the point is to stop the crawl
 *    and to create no new indexable URL, not to promote the archive.
 *
 * 2. **THE MAIN POST FEED LIVES. Only the per-term, per-author and per-search
 *    feeds die.**
 *
 *    `/feed/`, `/blog/feed/` and their `rss`/`rss2`/`atom`/`rdf` variants are
 *    untouched and still advertised in `<head>`. They are a real subscription
 *    surface with real subscribers, and killing them to fix a crawl-budget
 *    problem would be trading a reader for a robot. Singular post feeds are
 *    the comment feed for that post and are covered by the comment rule below;
 *    post-type-archive feeds are deliberately left alone (see the note at the
 *    end of this file).
 *
 * 3. **REMOVE THE `<head>` LINKS TOO, NOT JUST THE URLS.**
 *
 *    A redirect stops the crawl after discovery. Removing
 *    `feed_links_extra()` stops the discovery. Both, or the fix is half a fix:
 *    every archive page would keep publishing a machine-readable invitation to
 *    a URL that immediately bounces.
 *
 *    `feed_links_extra` (core, `wp_head`, priority 3) emits the category, tag,
 *    custom-taxonomy, author, search and per-post-comment feed links.
 *    `feed_links` (priority 2) emits the site feed and the global comments
 *    feed. We remove the first entirely and suppress only the comments half of
 *    the second, which is exactly the split we want.
 *
 *    ⚠ MEASURED ON PRODUCTION AT 1.19.216, BEFORE THIS RELEASE, AND RECORDED
 *      SO NOBODY LATER READS IT AS THIS RELEASE'S DOING: this theme has never
 *      called `add_theme_support('automatic-feed-links')`, and `feed_links()`
 *      returns early without it — so the site-feed `<link>` was ALREADY absent
 *      from every page. `feed_links_extra()` carries no such guard, which is
 *      why the only feed links this site was ever publishing were the thin
 *      taxonomy ones. `feed_links` is deliberately left hooked at priority 2:
 *      the day someone adds theme support, the site feed link comes back and
 *      this file will not be in its way. The URL is unaffected regardless —
 *      `/feed/` serves the real feed before and after.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ⭐ ROBOTS.TXT — EXTEND, NEVER CLOBBER
 * ─────────────────────────────────────────────────────────────────────────
 *
 * This site has **no physical `robots.txt`** (verified live 2026-08-10:
 * `ls robots.txt` in the production document root returns "No such file").
 * The file is generated by WordPress core's `do_robots()`, and three parties
 * contribute to it through the `robots_txt` filter: core (`/wp-admin/`),
 * WooCommerce (the `wc-logs`, `woocommerce_uploads` and `add-to-cart` rules)
 * and Rank Math (the `Sitemap:` line). **Rank Math has no custom robots.txt
 * content configured** — verified by reading `rank-math-options-general`, so
 * there is nothing of its to overwrite and the core filter is the correct
 * mechanism.
 *
 * ⛔ THE RULE THIS CODE FOLLOWS: it only ever INSERTS lines. It never rewrites,
 *    reorders or drops anything another party contributed, and it is
 *    idempotent — if its rules are already present it returns the input
 *    untouched. `bhp_seo_inject_robots_rules()` is a pure function so the test
 *    suite can prove that property against real captured output rather than
 *    inferring it.
 *
 * ⚠ `Disallow: /search/` is honest here because search result URLs on this
 *   site really are `/search/<query>/` (verified: `get_search_link('reading')`
 *   returns `https://braveheartspublishing.com/search/reading/`) and no page
 *   uses the slug `search` (verified: 0 pages). Search pages are already
 *   `noindex` (`noindex_search: on`); the disallow saves the crawl of a
 *   combinatorially unbounded URL space.
 *
 * ⚠ `Disallow: /*?wc-ajax=` blocks a WooCommerce transport endpoint from
 *   CRAWLERS ONLY. It has no effect on a real browser, on the Store API, or on
 *   checkout — robots.txt is not an access control. The one visible cost is
 *   that Google's renderer will not fetch cart-fragment refreshes while
 *   rendering a page; those fragments contain a cart item count and nothing
 *   any search engine should be indexing. WooCommerce already disallows its
 *   sibling `?add-to-cart=` parameter for the same reason, and these lines
 *   deliberately mirror its two-pattern style.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

/* ==========================================================================
 * 1. THE `<head>` LINKS
 * ========================================================================== */

/**
 * Stop advertising per-term, per-author, per-search and per-post-comment
 * feeds. The site feed link is emitted by `feed_links()` at priority 2 and is
 * deliberately left in place.
 */
add_action('after_setup_theme', 'bhp_seo_remove_extra_feed_links');
function bhp_seo_remove_extra_feed_links() {
    remove_action('wp_head', 'feed_links_extra', 3);
}

/**
 * Suppress the global comments feed link without touching the posts feed link.
 * Core checks both flags separately inside `feed_links()`, so this removes
 * exactly one `<link>` and leaves the subscription surface intact.
 */
add_filter('feed_links_show_comments_feed', '__return_false');

/* ==========================================================================
 * 2. THE FEED URLS THEMSELVES
 * ========================================================================== */

/**
 * Classify a feed request from a plain array of query flags.
 *
 * Deliberately pure and deliberately taking its state as an argument: every
 * branch below is reachable from the test suite without booting a real query,
 * including the branches this site cannot currently produce.
 *
 * @param array $state Booleans keyed `is_feed`, `is_comment_feed`, `is_category`,
 *                     `is_tag`, `is_tax`, `is_author`, `is_search`.
 * @return string One of `comment`, `term`, `author`, `search`, or '' for
 *                "leave this feed alone".
 */
function bhp_seo_thin_feed_kind(array $state) {
    if (empty($state['is_feed'])) {
        return '';
    }

    // Order matters. A singular post's feed is a comment feed, and a comment
    // feed on a term archive is still a comment feed — the comment test runs
    // first so neither is misfiled as a term feed.
    if (!empty($state['is_comment_feed'])) {
        return 'comment';
    }

    if (!empty($state['is_category']) || !empty($state['is_tag']) || !empty($state['is_tax'])) {
        return 'term';
    }

    if (!empty($state['is_author'])) {
        return 'author';
    }

    if (!empty($state['is_search'])) {
        return 'search';
    }

    // The site feed, the posts-page feed, post-type-archive feeds: untouched.
    return '';
}

/**
 * Read the current WordPress query into the flag array `bhp_seo_thin_feed_kind()`
 * consumes. Split out so the classifier stays testable and this stays trivial.
 *
 * @return array
 */
function bhp_seo_current_feed_state() {
    return array(
        'is_feed'         => is_feed(),
        'is_comment_feed' => is_comment_feed(),
        'is_category'     => is_category(),
        'is_tag'          => is_tag(),
        'is_tax'          => is_tax(),
        'is_author'       => is_author(),
        'is_search'       => is_search(),
    );
}

/**
 * Resolve the URL a thin feed should be sent to.
 *
 * Every branch falls back to the home page rather than to an empty string: a
 * redirect to nowhere is worse than a redirect to somewhere honest, and an
 * unexpected queried object must not produce a header with no location.
 *
 * @param string $kind Result of `bhp_seo_thin_feed_kind()`.
 * @return string Absolute URL, or '' if this feed is not ours to touch.
 */
function bhp_seo_thin_feed_target($kind) {
    $home = home_url('/');

    switch ($kind) {
        case 'comment':
            // A post's comment feed belongs to the post. The site-wide comment
            // feed belongs to the site.
            if (is_singular()) {
                $permalink = get_permalink();
                return $permalink ? $permalink : $home;
            }
            return $home;

        case 'term':
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $link = get_term_link($term);
                if (!is_wp_error($link) && $link) {
                    return $link;
                }
            }
            return $home;

        case 'author':
            $author = get_queried_object();
            if ($author instanceof WP_User) {
                $link = get_author_posts_url($author->ID);
                if ($link) {
                    return $link;
                }
            }
            return $home;

        case 'search':
            $query = get_search_query();
            if ('' !== (string) $query) {
                $link = get_search_link($query);
                if ($link) {
                    return $link;
                }
            }
            return $home;
    }

    return '';
}

/**
 * Send every thin feed to its parent with a 301.
 *
 * Priority 1 on `template_redirect` so the decision is made before any other
 * layer starts rendering a feed template.
 */
add_action('template_redirect', 'bhp_seo_redirect_thin_feeds', 1);
function bhp_seo_redirect_thin_feeds() {
    if (!is_feed()) {
        return;
    }

    $kind = bhp_seo_thin_feed_kind(bhp_seo_current_feed_state());
    if ('' === $kind) {
        return;
    }

    $target = bhp_seo_thin_feed_target($kind);
    if ('' === $target) {
        return;
    }

    // Never redirect a URL onto itself — that is an infinite loop, and it is
    // the one way this function could take the site down.
    $current = home_url(add_query_arg(array()));
    if (untrailingslashit($current) === untrailingslashit($target)) {
        return;
    }

    wp_safe_redirect($target, 301);
    exit;
}

/* ==========================================================================
 * 3. ROBOTS.TXT
 * ========================================================================== */

/**
 * The lines this theme contributes to robots.txt.
 *
 * @return array
 */
function bhp_seo_robots_rules() {
    return array(
        'Disallow: /*?wc-ajax=',
        'Disallow: /*?*wc-ajax=',
        'Disallow: /search/',
    );
}

/**
 * Insert this theme's rules into an existing robots.txt body without disturbing
 * a single character anyone else contributed.
 *
 * Pure, and separated from the filter so the suite can feed it real captured
 * output and assert that every pre-existing line survives.
 *
 * @param string $output Robots.txt body as assembled so far.
 * @return string
 */
function bhp_seo_inject_robots_rules($output) {
    $rules = bhp_seo_robots_rules();

    // Idempotent: if every rule is already present, change nothing at all.
    $missing = array();
    foreach ($rules as $rule) {
        if (false === strpos($output, $rule)) {
            $missing[] = $rule;
        }
    }
    if (empty($missing)) {
        return $output;
    }

    $block  = implode("\n", $missing) . "\n";
    $anchor = "User-agent: *\n";
    $at     = strpos($output, $anchor);

    if (false !== $at) {
        // Inside the wildcard group, where a Disallow has to live to apply.
        $insert = $at + strlen($anchor);
        return substr($output, 0, $insert) . $block . substr($output, $insert);
    }

    // No wildcard group found — do not guess at another agent's group.
    // Append our own, which is additive and cannot mis-scope someone else's.
    return rtrim($output, "\n") . "\n\nUser-agent: *\n" . $block;
}

/**
 * Is this body already a site-wide block?
 *
 * ⭐ THIS FUNCTION EXISTS BECAUSE THE OBVIOUS TEST WAS WRONG, AND STAGING
 *    PROVED IT. The first version of the filter below guarded on the `$public`
 *    argument (`blog_public`), on the reasoning that a discouraged site emits
 *    `Disallow: /` and must not have that diluted. That reasoning is sound and
 *    the implementation was still wrong, because on this site `$public` does
 *    not describe the body being filtered.
 *
 *    Measured on staging 2026-08-10: `blog_public` is `'0'`, and the robots.txt
 *    body arriving at this filter contains **no `Disallow: /` at all** — Rank
 *    Math (priority 10) has already rewritten it into the public form,
 *    `Disallow: /wp-admin/` and `Allow: /wp-admin/admin-ajax.php` included.
 *    Guarding on `$public` therefore skipped the injection on the one
 *    environment where the change had to be verified before production.
 *
 *    So the guard now tests the thing it actually cares about: does this body
 *    contain a literal site-wide `Disallow: /`? If it does, we add nothing —
 *    additional narrower rules next to a total block are noise at best and a
 *    contradictory signal at worst. If it does not, our three rules apply.
 *
 * ⚠ SEPARATE FINDING, FLAGGED NOT FIXED: this means `staging2`'s robots.txt
 *   does not discourage crawlers even though WordPress is set to discourage
 *   them. Staging's protection against indexing rests on its `noindex` meta
 *   tag, not on robots.txt. That is a pre-existing condition of the Rank Math
 *   configuration, it is not this release's to change, and it is reported
 *   rather than silently corrected.
 *
 * @param string $output Robots.txt body.
 * @return bool
 */
function bhp_seo_robots_is_site_blocked($output) {
    return 1 === preg_match('/^\s*Disallow:\s*\/\s*$/m', (string) $output);
}

add_filter('robots_txt', 'bhp_seo_filter_robots_txt', 20, 2);
/**
 * @param string $output Robots.txt body.
 * @param bool   $public `blog_public`. Received for signature completeness and
 *                       deliberately not used as the guard — see above.
 * @return string
 */
function bhp_seo_filter_robots_txt($output, $public) {
    unset($public);

    if (bhp_seo_robots_is_site_blocked($output)) {
        return $output;
    }

    return bhp_seo_inject_robots_rules($output);
}

/*
 * ─────────────────────────────────────────────────────────────────────────
 * ⚠ DELIBERATELY NOT DONE HERE — named so a future reader does not assume it
 *   was missed.
 *
 * - **Post-type archive feeds** (e.g. `/shop/feed/`) are left alone. They are
 *   the same class of thin URL, but not one of them appears in the Search
 *   Console export, and widening a redirect rule past its evidence is how a
 *   fix acquires a regression it cannot justify. Flagged, not absorbed.
 *
 * - **The Rank Math sitemap** was audited read-only and needed no change:
 *   `tax_post_tag_sitemap`, `tax_product_tag_sitemap`, `tax_product_cat_sitemap`
 *   and `tax_product_brand_sitemap` are all already `off`, `author-sitemap.xml`
 *   already 404s, and the only taxonomy in the sitemap index is `category`,
 *   which is `index`. There is no noindex-in-sitemap contradiction to fix.
 *
 * - **No taxonomy term was created, deleted, merged or edited.** The 137 blog
 *   tags are a content decision that belongs to Andrew.
 *
 * - **`/review/<slug>/`** is a theme-owned virtual route (`inc/reviews.php`),
 *   a private review-request form reached only from a post-purchase email. Its
 *   `noindex, nofollow` is deliberate and correct, and Search Console listing
 *   it under "Excluded by noindex" is the system working. Nothing to fix.
 * ─────────────────────────────────────────────────────────────────────────
 */
