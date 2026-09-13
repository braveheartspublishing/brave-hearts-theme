<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-418 — the four rulings of seal 1550.
 *
 * Theme 1.19.418. ⛔ THE BUNDLE PLUGIN DOES NOT MOVE IN THIS BUILD and §5.2
 * asserts it is still 1.8.94 rather than assuming it — `CYCLE180-LD-BUILD-413`
 * silently DOWNGRADED the plugin by two versions while reporting success, and
 * the only thing that caught it was reading the version back.
 *
 * ⚠ EVIDENCE CLASS OF THE RULINGS: RELAYED through `chief-of-staff`. ⛔ NOT
 *   witnessed first-hand by the agent that wrote this suite (Standing Rules
 *   §9.2 rule 2).
 *
 * THE FOUR RULINGS:
 *   1. the per-post Amazon affiliate disclosure, auto-detected           → §1
 *   2. post 78's duplicated old title, removed as an H3                  → §2
 *   3. the approved Sturm tactic (b): store links in the related block   → §3
 *   4. the Complete Collection hardcover row, PREPARED and shipped KEPT  → §4
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE THREE ROWS THAT CAN ACTUALLY FAIL ON A PLAUSIBLE BAD BUILD.
 *     Read these before adding a grep and calling this covered.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * §1.4 — THE GREEDY-`.*` TRAP, ASSERTED WITH A NEGATIVE CONTROL. The shared
 * pattern is `/amazon\.com\/.*\btag=/i`. `.*` matches any character except a
 * newline, so run against a WHOLE post body — which in WordPress is frequently
 * one enormous line — it bridges happily from the words "amazon.com" in one
 * sentence to a `tag=` in an unrelated URL much later. A build that "simplified"
 * the detector to `preg_match($pattern, $post->post_content)` would still pass
 * every positive test in this file. §1.4 feeds the detector a body that contains
 * both halves in SEPARATE anchors and asserts the answer is FALSE. ⭐ A false
 * disclosure is not a harmless extra line: it is an untrue statement about how
 * this business earns, which is §3's subject matter, not a display bug.
 *
 * §3.4 — THE ANCHOR TEXT IS THE TARGET'S OWN TITLE, CHARACTER FOR CHARACTER.
 * The ruling's constraint is "no new sentences beyond link text". A build that
 * decorated the anchor ("Read The Mariana Trench", "Buy the paperback") would
 * have invented customer-facing copy. §3.4 compares the rendered anchor text
 * against the product title the resolver returned and requires equality, not
 * containment.
 *
 * §4.2 — THE SWITCH TRAVELS BOTH WAYS. A constant that can only ever be read as
 * `true` is not a prepared removal, it is a comment. §4.2 flips the filter to
 * false, re-reads the resolver, and flips it back — so "prepared" is a checked
 * fact rather than a claim in a report.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE. §1 and §3 exercise PHP and render markup
 *   through the real template. They do NOT prove what a browser painted, and
 *   they say nothing about where the line sits on the page or whether it is
 *   legible at 390. The browser evidence — the disclosure present on the
 *   affiliate posts and absent on post 82, the store links in the block, post
 *   78's H3 gone — is captured separately at asserted `window.innerWidth` and
 *   filed with the build report. Neither substitutes for the other.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-418.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no variation,
 *    no attachment, no term, no option, no post, no comment, no cart, no order,
 *    no file. §2 READS post 78 and asserts what it contains; it never writes it.
 *    The two filters §1.4 and §4.2 add are REMOVED in the same breath they are
 *    added, and §4.3 re-reads the resolver afterwards to prove the removal took.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ COUNTERS LIVE IN $GLOBALS, NOT IN `global $x` — `wp eval-file` includes
 *     this file INSIDE A FUNCTION, so a helper declaring `global` would
 *     increment a DIFFERENT variable from the one the summary reads, printing
 *     "0 failed" however many assertions failed. Observed at 1.19.412.
 */
$GLOBALS['b418_passes']   = 0;
$GLOBALS['b418_failures'] = 0;
$GLOBALS['b418_skips']    = 0;

function b418_assert($label, $ok, $detail = '') {
    if ($ok) {
        $GLOBALS['b418_passes']++;
        printf("  PASS  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '');
    } else {
        $GLOBALS['b418_failures']++;
        printf("  FAIL  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '');
    }
    return (bool) $ok;
}

function b418_skip($label, $why) {
    $GLOBALS['b418_skips']++;
    printf("  SKIP  %s  [%s]\n", $label, $why);
}

/**
 * Strip CSS comments so a needle can never match an essay ABOUT a rule.
 *
 * ⛔ THIS THEME'S STYLESHEETS ARE MOSTLY COMMENTARY BY BYTE COUNT and every
 *    superseded value in them is preserved in prose on purpose. Every assertion
 *    about a DECLARATION runs against this function's output.
 */
function b418_css_code($css) {
    return preg_replace('!/\*.*?\*/!s', '', $css);
}

$b418_theme_dir = get_template_directory();

printf("\n=== CYCLE180-LD-BUILD-418 — theme %s ===\n", wp_get_theme()->get('Version'));

/*
 * ⛔ A FLOOR, NOT AN EQUALITY, AND THIS BUILD LEARNED IT THE HARD WAY.
 *    `test-cycle180-build-417.php` pinned its own version with `===` and went
 *    red the moment 1.19.418 installed — 55 correct assertions and 2 red rows
 *    that said nothing except that time had passed. A floor still catches the
 *    failure that matters (a deploy landing an OLDER theme, the 413 incident)
 *    and does not go stale. `test-cycle180-build-416.php` §6.1 is the house
 *    pattern; this follows it.
 */
$b418_live_ver = (string) wp_get_theme(get_template())->get('Version');
b418_assert('0.1 the active theme reports 1.19.418 or later',
    '' !== $b418_live_ver && version_compare($b418_live_ver, '1.19.418', '>='),
    "live: {$b418_live_ver}");

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · RULING 1 — THE PER-POST AMAZON AFFILIATE DISCLOSURE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §1 the per-post affiliate disclosure --\n";

$b418_fns = array(
    'bhp_affiliate_disclosure_text',
    'bhp_affiliate_extract_hrefs',
    'bhp_affiliate_url_pattern',
    'bhp_post_has_affiliate_links',
    'bhp_affiliate_disclosure_enabled',
    'bhp_affiliate_disclosure_html',
);
$b418_missing = array();
foreach ($b418_fns as $b418_fn) {
    if (!function_exists($b418_fn)) {
        $b418_missing[] = $b418_fn;
    }
}
b418_assert('1.1 every disclosure helper is loaded', empty($b418_missing), $b418_missing ? implode(', ', $b418_missing) : 'all 6');

if ($b418_missing) {
    b418_skip('1.2 - 1.9 the disclosure behaviour', 'helpers missing; the include did not ship');
} else {

    /*
     * ⛔ THE APPROVED STRING, BYTE FOR BYTE. This is the row that fails if
     *    anyone "improves" the wording. It is APPROVED COPY (§9: approved copy
     *    is never silently rewritten) and the only lawful way past this
     *    assertion is a new founder approval and an edit to BOTH places.
     */
    $b418_expected = 'Some links on this page go to Amazon and earn me a small commission at no cost to you.';
    b418_assert('1.2 ⛔ the approved disclosure sentence is byte-for-byte intact',
        $b418_expected === bhp_affiliate_disclosure_text(),
        bhp_affiliate_disclosure_text());

    /* The three standing rails, checked against the FINISHED STRING (§9.4 cl.3). */
    $b418_text = bhp_affiliate_disclosure_text();
    b418_assert('1.2a no em dash in the disclosure', false === strpos($b418_text, "\xE2\x80\x94"));
    b418_assert('1.2b §9.1 voice: no "we", "us" or "our" in the disclosure',
        0 === preg_match('/\b(we|us|our)\b/i', $b418_text));
    b418_assert('1.2c §9.1 voice: it speaks as "me"', false !== strpos($b418_text, ' me '));

    /*
     * ⛔ THE SITEWIDE FOOTER DISCLOSURE IS A DIFFERENT STATEMENT AND STAYS.
     *    The ruling says so explicitly. If a future build "deduplicates" the
     *    two, this row goes red.
     */
    if (function_exists('bhp_get_amazon_disclosure_text')) {
        $b418_footer = bhp_get_amazon_disclosure_text();
        b418_assert('1.3 ⛔ the sitewide footer disclosure still exists and is a DIFFERENT string',
            'As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.' === $b418_footer
                && $b418_footer !== $b418_text,
            $b418_footer);
    } else {
        b418_assert('1.3 the sitewide footer disclosure still exists', false, 'bhp_get_amazon_disclosure_text() is gone');
    }

    /* ── 1.4 THE NEGATIVE CONTROL. See the file docblock. ─────────────────
     *
     * ⛔⛔ THE TRAP BODY IS THE TEST, AND THE FIRST VERSION OF IT WAS NOT A
     *     TRAP. It read "I buy a lot on amazon.com and read a lot of reviews"
     *     — BARE HOSTNAME, NO SLASH — and the pattern requires `amazon\.com\/`.
     *     The whole-document match therefore did not fire, 1.4 passed by
     *     testing nothing, and only the control below caught it. ⭐ That is the
     *     RUNBOOK's rule ("a check that cannot fail is not a check") catching a
     *     check written in this very file.
     *
     * ⭐ THE CORRECTED BODY IS ALSO THE REALISTIC ONE: a PLAIN Amazon product
     *    link that earns nothing, and a separate non-Amazon link that happens
     *    to carry a `tag=` query parameter, both on ONE LINE — which is how
     *    WordPress stores post content. A whole-document match bridges from the
     *    first to the second and reports affiliate income that does not exist.
     */
    $b418_trap = '<p>Read the <a href="https://www.amazon.com/dp/AAA">hardcover listing</a> for yourself.</p>'
        . '<p>And here is <a href="https://example.com/search?tag=nothing">an unrelated link</a>.</p>';
    $b418_pattern = bhp_affiliate_url_pattern();

    /*
     * ⭐ THE CONTROL FOR THE CONTROL. Prove the trap body really would fool a
     *    whole-document match, so 1.4 cannot pass by testing nothing.
     */
    b418_assert('1.4a control: a whole-document match on the trap body WOULD have fired (so 1.4 is a real test)',
        1 === preg_match($b418_pattern, $b418_trap));

    $b418_trap_urls = bhp_affiliate_extract_hrefs($b418_trap);
    $b418_trap_hit  = false;
    foreach ($b418_trap_urls as $b418_u) {
        if (preg_match($b418_pattern, $b418_u)) {
            $b418_trap_hit = true;
        }
    }
    b418_assert('1.4 ⛔⛔ per-anchor matching refuses the greedy-.* trap (no false disclosure)',
        false === $b418_trap_hit,
        count($b418_trap_urls) . ' href(s) extracted');
    b418_assert('1.4b control: the trap body really does contain TWO anchors (so 1.4 examined both)',
        2 === count($b418_trap_urls), implode(' | ', $b418_trap_urls));
    /*
     * ⭐ AND THE DETECTOR STILL SAYS YES TO A REAL ONE. Without this row, 1.4
     *    could be satisfied by a detector that answers "no" to everything.
     */
    $b418_real = '<p>Here is <a href="https://www.amazon.com/dp/BBB?tag=braveheart-20">the book</a>.</p>';
    $b418_real_hit = false;
    foreach (bhp_affiliate_extract_hrefs($b418_real) as $b418_u2) {
        if (preg_match($b418_pattern, $b418_u2)) {
            $b418_real_hit = true;
        }
    }
    b418_assert('1.4c control: a REAL affiliate anchor is still detected (the detector is not simply "no")',
        true === $b418_real_hit);

    /* ── 1.5 the extractor handles both quote styles ─────────────────────── */
    $b418_q = '<a href="https://www.amazon.com/dp/AAA?tag=x">d</a> <a href=\'https://www.amazon.com/dp/BBB?tag=y\'>s</a>';
    $b418_qurls = bhp_affiliate_extract_hrefs($b418_q);
    b418_assert('1.5 hrefs are extracted from both double- and single-quoted anchors',
        2 === count($b418_qurls), implode(' | ', $b418_qurls));

    /* ── 1.6 / 1.7 the real posts, read live ─────────────────────────────── */
    $b418_aff_posts = array();
    $b418_clean_posts = array();
    foreach (get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids')) as $b418_pid) {
        if (bhp_post_has_affiliate_links($b418_pid)) {
            $b418_aff_posts[] = $b418_pid;
        } else {
            $b418_clean_posts[] = $b418_pid;
        }
    }
    b418_assert('1.6 at least one published post is detected as carrying affiliate links',
        count($b418_aff_posts) > 0, 'ids: ' . implode(',', $b418_aff_posts));
    b418_assert('1.6a detection is SELECTIVE — some published posts carry none',
        count($b418_clean_posts) > 0, count($b418_clean_posts) . ' clean');

    /*
     * ⛔ POST 82 IS THE BRIEF'S NAMED NEGATIVE. It is the site's strongest
     *    ranking page and it carries no affiliate link; the disclosure must be
     *    ABSENT there. Skipped rather than failed where the id does not exist,
     *    because this suite runs on more than one environment.
     */
    $b418_p82 = get_post(82);
    if ($b418_p82 && 'post' === $b418_p82->post_type) {
        b418_assert('1.7 ⛔ post 82 (reading-level chart) renders NO disclosure',
            '' === bhp_affiliate_disclosure_html(82),
            $b418_p82->post_name);
    } else {
        b418_skip('1.7 post 82 renders no disclosure', 'post 82 is absent on this environment');
    }

    if ($b418_aff_posts) {
        $b418_one = $b418_aff_posts[0];
        $b418_html = bhp_affiliate_disclosure_html($b418_one);
        b418_assert('1.8 an affiliate post renders the disclosure markup',
            '' !== $b418_html && false !== strpos($b418_html, 'bhp-affiliate-disclosure'),
            'post ' . $b418_one);
        b418_assert('1.8a it carries the approved sentence, HTML-escaped, and role="note"',
            false !== strpos($b418_html, esc_html($b418_expected)) && false !== strpos($b418_html, 'role="note"'));
        /*
         * ⛔ `.text-caption` IS UPPERCASE. A 15-word legal sentence set in all
         *    caps is a worse disclosure. If someone reaches for the utility,
         *    this goes red.
         */
        b418_assert('1.8b ⛔ it does NOT use the uppercase .text-caption utility',
            false === strpos($b418_html, 'text-caption'));
    } else {
        b418_skip('1.8 an affiliate post renders the disclosure', 'no affiliate post on this environment');
    }

    /* ── 1.9 pages and other post types never claim it ───────────────────── */
    $b418_pages = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 3, 'fields' => 'ids'));
    if ($b418_pages) {
        $b418_page_hit = false;
        foreach ($b418_pages as $b418_pg) {
            if ('' !== bhp_affiliate_disclosure_html($b418_pg)) {
                $b418_page_hit = true;
            }
        }
        b418_assert('1.9 ⛔ the disclosure never renders on a page (post type is gated)', false === $b418_page_hit);
    } else {
        b418_skip('1.9 the disclosure never renders on a page', 'no published pages found');
    }

    /* ── 1.10 the call site exists in single.php, below BOTH header branches ─ */
    $b418_single = (string) @file_get_contents($b418_theme_dir . '/single.php');
    b418_assert('1.10 single.php calls the disclosure helper',
        false !== strpos($b418_single, 'bhp_affiliate_disclosure_html'));
    b418_assert('1.10a it is function_exists()-gated (a partial deploy degrades, never fatals)',
        false !== strpos($b418_single, "function_exists('bhp_affiliate_disclosure_html')"));

    $b418_call_at = strpos($b418_single, 'echo bhp_affiliate_disclosure_html');
    $b418_h1_last = strrpos($b418_single, 'post-header__title');
    $b418_img_at  = strpos($b418_single, 'post-header__image');
    b418_assert('1.10b ⛔ the call is AFTER the last H1 (under the title in BOTH header orderings)',
        false !== $b418_call_at && false !== $b418_h1_last && $b418_call_at > $b418_h1_last,
        "h1@$b418_h1_last call@$b418_call_at");
    b418_assert('1.10c ⛔ and BEFORE the featured image (the reader meets it before the links)',
        false !== $b418_call_at && false !== $b418_img_at && $b418_call_at < $b418_img_at,
        "call@$b418_call_at img@$b418_img_at");

    /* ── 1.11 the stylesheet rule shipped, outside any media query ───────── */
    $b418_style = b418_css_code((string) @file_get_contents($b418_theme_dir . '/style.css'));
    $b418_rule_at = strpos($b418_style, '.bhp-affiliate-disclosure {');
    b418_assert('1.11 the .bhp-affiliate-disclosure rule is in style.css', false !== $b418_rule_at);
    if (false !== $b418_rule_at) {
        /*
         * ⛔ BRACE DEPTH AT THE RULE'S POSITION MUST BE 0. Depth > 0 means the
         *    rule is nested inside a media query, where it would silently do
         *    nothing at one of the two widths the ruling names. This is the
         *    exact trap 1.19.417 recorded against `book-formats.css`.
         */
        $b418_before = substr($b418_style, 0, $b418_rule_at);
        $b418_depth  = substr_count($b418_before, '{') - substr_count($b418_before, '}');
        b418_assert('1.11a ⛔ it is NOT inside a media query — brace depth 0 at its position (both widths)',
            0 === $b418_depth, "depth $b418_depth");
        b418_assert('1.11b it explicitly cancels the inherited uppercase treatment',
            false !== strpos(substr($b418_style, $b418_rule_at, 400), 'text-transform: none'));
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · RULING 2 — POST 78's DUPLICATED OLD TITLE
   ═══════════════════════════════════════════════════════════════════════════
   ⛔ READ-ONLY. This section asserts the STATE of post 78 on whatever
      environment it runs against. It never writes the post. On an environment
      where the edit has not been applied it FAILS, which is correct: that is
      the fact it exists to report.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §2 post 78, the duplicated old title --\n";

$b418_78 = get_post(78);
if (!$b418_78 || 'post' !== $b418_78->post_type) {
    b418_skip('2.1 - 2.4 post 78', 'post 78 is absent on this environment');
} else {
    $b418_78c = $b418_78->post_content;
    $b418_old = "My Child Got a Lexile Score - Now What? Here's Exactly What to Do";

    preg_match_all('/<h3[^>]*>(.*?)<\/h3>/is', $b418_78c, $b418_h3s);
    $b418_h3_texts = array();
    foreach ($b418_h3s[1] as $b418_h3) {
        $b418_h3_texts[] = trim(wp_strip_all_tags($b418_h3));
    }

    b418_assert('2.1 ⛔ the old title no longer sits in post 78 as an H3',
        !in_array($b418_old, $b418_h3_texts, true),
        'h3s: ' . (implode(' | ', $b418_h3_texts) ?: '(none)'));

    /*
     * ⭐ THE SURGERY WAS NARROW, AND THIS IS THE ROW THAT PROVES IT. The OTHER
     *    H3 is real content and must survive. A build that stripped every H3
     *    would pass 2.1 and fail here.
     */
    b418_assert('2.2 ⭐ the post\'s OTHER H3 survived (the edit was one element, not a purge)',
        in_array('Ready to Find the Right Book?', $b418_h3_texts, true),
        count($b418_h3_texts) . ' h3(s) remain');

    /*
     * ⛔ AND NOTHING ELSE WAS TOUCHED. The H4 immediately below the removed H3
     *    is the article's real opening line.
     */
    b418_assert('2.3 the article\'s opening H4 is untouched',
        false !== strpos($b418_78c, 'You got a note home from school.'));

    b418_assert('2.4 the byline paragraph is untouched',
        false !== strpos($b418_78c, 'By Andrew Signore'));
}

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · RULING 3 — THE STORE LINKS IN THE END-OF-POST RELATED BLOCK
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §3 the related books and posts block --\n";

$b418_rb_ok = function_exists('bhp_related_books_for_post') && function_exists('bhp_related_books_heading');
b418_assert('3.1 the related-books helpers are loaded', $b418_rb_ok);

if (!$b418_rb_ok) {
    b418_skip('3.2 - 3.7 the block behaviour', 'helpers missing; the include did not ship');
} else {
    /*
     * THE FIVE RANKING POSTS, BY SLUG. ⛔ SLUGS, NOT IDS — post ids differ
     *    between environments (the Magic Tree House post is 5089 on staging and
     *    638 on production), and this suite must mean the same thing on both.
     */
    $b418_five = array(
        'reading-level-by-grade-chart',
        'what-to-read-after-dog-man',
        'books-like-magic-tree-house',
        'mount-everest-facts-for-kids',
        'how-deep-is-the-mariana-trench-for-kids',
    );

    $b418_no_books = array();
    $b418_checked  = 0;
    foreach ($b418_five as $b418_slug) {
        $b418_p = get_page_by_path($b418_slug, OBJECT, 'post');
        if (!$b418_p || 'publish' !== $b418_p->post_status) {
            continue;
        }
        $b418_checked++;
        if (!bhp_related_books_for_post($b418_p)) {
            $b418_no_books[] = $b418_slug;
        }
    }
    b418_assert('3.2 the five ranking posts were found on this environment', $b418_checked > 0, "$b418_checked of 5");
    b418_assert('3.3 ⭐ EVERY one of them now resolves at least one store link',
        empty($b418_no_books), $b418_no_books ? 'no links: ' . implode(', ', $b418_no_books) : "$b418_checked/$b418_checked");

    /* ── 3.4 the anchor text IS the target's own title, exactly ──────────── */
    $b418_sample = get_page_by_path('reading-level-by-grade-chart', OBJECT, 'post');
    if (!$b418_sample) {
        $b418_sample = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1));
        $b418_sample = $b418_sample ? $b418_sample[0] : null;
    }
    if ($b418_sample) {
        $b418_books = bhp_related_books_for_post($b418_sample);

        ob_start();
        get_template_part('template-parts/guides/related-content', null, array('post' => $b418_sample));
        $b418_render = (string) ob_get_clean();

        b418_assert('3.4a the block rendered a store list', false !== strpos($b418_render, 'guide-continuation__books'), $b418_sample->post_name);

        $b418_exact = true;
        $b418_detail = array();
        if (preg_match('/<ul class="guide-continuation__books">(.*?)<\/ul>/is', $b418_render, $b418_ul)) {
            preg_match_all('/<a\s+href="([^"]*)"[^>]*>(.*?)<\/a>/is', $b418_ul[1], $b418_anchors, PREG_SET_ORDER);
            b418_assert('3.4b the store list carries one anchor per resolved book',
                count($b418_anchors) === count($b418_books),
                count($b418_anchors) . ' anchors / ' . count($b418_books) . ' books');
            foreach ($b418_anchors as $b418_i => $b418_a) {
                $b418_txt = html_entity_decode(trim(wp_strip_all_tags($b418_a[2])), ENT_QUOTES, 'UTF-8');
                $b418_want = $b418_books[$b418_i]['title'] ?? '';
                $b418_detail[] = $b418_txt;
                if ($b418_txt !== $b418_want) {
                    $b418_exact = false;
                }
            }
        } else {
            $b418_exact = false;
            $b418_detail[] = 'no <ul> found';
        }
        b418_assert('3.4 ⛔⛔ every anchor text EQUALS the target\'s own title — no added words',
            $b418_exact, implode(' | ', $b418_detail));

        /*
         * ⛔ AND THE DESTINATIONS ARE STORE URLs. An anchor whose text is a book
         *    title and whose href is not that book's page is the `FD-549`
         *    failure shape: two true facts assembled into a false one.
         */
        $b418_bad_url = array();
        foreach ($b418_books as $b418_b) {
            if (!preg_match('#/(product|complete-collection|shop|books)/#i', $b418_b['url'])) {
                $b418_bad_url[] = $b418_b['title'] . ' -> ' . $b418_b['url'];
            }
        }
        b418_assert('3.4c every destination is a store URL', empty($b418_bad_url), implode(' | ', $b418_bad_url));

        /*
         * ⭐ THE SIBLING-POST HALF IS UNTOUCHED BY THIS BUILD and must still be
         *    there. The tactic needs both halves; this build only added one.
         */
        b418_assert('3.5 ⭐ the pre-existing "Related Field Notes" sibling grid still renders',
            false !== strpos($b418_render, 'Related Field Notes')
                && false !== strpos($b418_render, 'guide-article-grid--related'));

        /*
         * ⛔ ONE BLOCK, NOT TWO. Andrew's 2026-08-31 redundancy ruling is the
         *    reason the store links went INSIDE the existing aside. If a later
         *    build splits them into a second <aside>, this goes red.
         */
        b418_assert('3.6 ⛔ exactly ONE <aside> is emitted (the 2026-08-31 redundancy ruling)',
            1 === substr_count($b418_render, '<aside'), substr_count($b418_render, '<aside') . ' aside(s)');
    } else {
        b418_skip('3.4 - 3.6 the rendered block', 'no published post available to render');
    }

    /* ── 3.7 no typed ids, skus or urls in the resolver ──────────────────── */
    $b418_rbsrc = (string) @file_get_contents($b418_theme_dir . '/inc/related-books.php');
    $b418_rbcode = preg_replace('!/\*.*?\*/!s', '', $b418_rbsrc);
    $b418_rbcode = preg_replace('!^\s*//.*$!m', '', $b418_rbcode);
    b418_assert('3.7 ⛔ the resolver contains no typed http(s) URL',
        0 === preg_match('#https?://#', $b418_rbcode));
    b418_assert('3.7a ⛔ and no typed product id or SKU-shaped literal',
        0 === preg_match('/\b\d{4,}\b/', $b418_rbcode));

    /*
     * ⭐ THE HEADING IS A REUSED APPROVED STRING, NOT A NEW ONE. It is sourced
     *    from bhp_blog_rail_eyebrow(), so this row proves no fresh
     *    customer-facing copy was minted for it.
     */
    if (function_exists('bhp_blog_rail_eyebrow')) {
        b418_assert('3.8 ⭐ the heading is the EXISTING approved rail eyebrow string, singular case',
            bhp_related_books_heading(1) === bhp_blog_rail_eyebrow(array('kind' => 'book')),
            bhp_related_books_heading(1));
        b418_assert('3.8a and the plural case',
            bhp_related_books_heading(3) === bhp_blog_rail_eyebrow(array('kind' => 'series')),
            bhp_related_books_heading(3));
    } else {
        b418_skip('3.8 the heading is a reused approved string', 'bhp_blog_rail_eyebrow() unavailable');
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · RULING 4 — THE HARDCOVER ROW, PREPARED AND SHIPPED KEPT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §4 the Complete Collection hardcover row --\n";

b418_assert('4.1a the constant is defined', defined('BHP_SHOP_CARD_HARDCOVER_ROW'));
b418_assert('4.1b the resolver exists', function_exists('bhp_shop_card_hardcover_row_enabled'));

if (defined('BHP_SHOP_CARD_HARDCOVER_ROW') && function_exists('bhp_shop_card_hardcover_row_enabled')) {
    /* ═══════════════════════════════════════════════════════════════════════
     * ⛔ CORRECTED 2026-09-13 (`CYCLE180-LD-BUILD-419`) — §4.1, §4.2 AND §4.3
     *    ASSERTED THE *KEEP* DEFAULT. ANDREW HAS SINCE RULED "REMOVE IT", SO
     *    THE CORRECT ANSWER MOVED AND THESE THREE ROWS MOVE WITH IT.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⭐⭐ THIS IS NOT THE 417 DEFECT REPEATED. §0.4/§0.5 of the 417 suite went
     *     red because TIME PASSED — an equality assertion on a version number
     *     that no correct build could ever satisfy again. These three rows were
     *     CORRECT when written and go red because a DECISION CHANGED. ⛔ The
     *     remedy is therefore the opposite of a floor: the assertion stays
     *     exact, and it is re-pointed at the new right answer. A build that
     *     silently reverted to KEEP must still go red.
     *
     * ⛔ NOTHING IS RELAXED AND NOTHING IS DELETED. The switch, the filter and
     *    the single call site are unchanged; §4.4–§4.6 below are BYTE-UNTOUCHED
     *    by this edit and still prove the gate reaches exactly one row on one
     *    card. **The superseded assertions are preserved immediately below
     *    rather than deleted, so the movement stays visible:**
     *
     *   // SUPERSEDED 2026-09-13 by the founder ruling of that date (RELAYED)
     *   b418_assert('4.1 ⛔⛔ the row SHIPS KEPT — the constant defaults to true',
     *       true === BHP_SHOP_CARD_HARDCOVER_ROW && true === bhp_shop_card_hardcover_row_enabled(),
     *       var_export(BHP_SHOP_CARD_HARDCOVER_ROW, true));
     *   add_filter('bhp_shop_card_hardcover_row', '__return_false');
     *   $b418_flipped = bhp_shop_card_hardcover_row_enabled();
     *   remove_filter('bhp_shop_card_hardcover_row', '__return_false');
     *   b418_assert('4.2 ⛔ the prepared removal really travels — the filter flips it to false',
     *       false === $b418_flipped, var_export($b418_flipped, true));
     *   b418_assert('4.3 ⭐ and the flip was undone — the resolver reads true again',
     *       true === bhp_shop_card_hardcover_row_enabled());
     *
     * ⚠ THIS EDIT TOUCHES ONLY §4.1, §4.2 AND §4.3. Every other assertion in
     *   this file is byte-untouched.
     * ═══════════════════════════════════════════════════════════════════════ */
    b418_assert('4.1 ⛔⛔ the row SHIPS REMOVED — the constant defaults to false (1.19.419)',
        false === BHP_SHOP_CARD_HARDCOVER_ROW && false === bhp_shop_card_hardcover_row_enabled(),
        var_export(BHP_SHOP_CARD_HARDCOVER_ROW, true));

    /* ── 4.2 THE SWITCH STILL TRAVELS BOTH WAYS, AND NOW IT IS THE ROUTE BACK.
     *      ⭐ With the default at REMOVE, the direction worth proving is the
     *      RESTORE: one filter brings the row back with no deploy and no code
     *      change, which is the whole reason 418 built a gate instead of
     *      deleting the block. ───────────────────────────────────────────── */
    add_filter('bhp_shop_card_hardcover_row', '__return_true');
    $b418_flipped = bhp_shop_card_hardcover_row_enabled();
    remove_filter('bhp_shop_card_hardcover_row', '__return_true');
    b418_assert('4.2 ⛔ the removal is REVERSIBLE — the filter flips it back to true',
        true === $b418_flipped, var_export($b418_flipped, true));

    b418_assert('4.3 ⭐ and the flip was undone — the resolver reads false again',
        false === bhp_shop_card_hardcover_row_enabled());

    /*
     * ⭐ THE GATE IS THE FIRST TERM OF THE CONDITION, so with the row off the
     *    hardcover collection data is not even fetched. Asserted in the source
     *    because the alternative — rendering the whole shop loop — would need a
     *    WooCommerce query this read-only suite has no business running.
     */
    $b418_bf = (string) @file_get_contents($b418_theme_dir . '/inc/book-formats.php');
    b418_assert('4.4 the gate is the FIRST term of the shop card\'s hardcover branch',
        false !== strpos($b418_bf, '$bhp_cc_alt = (bhp_shop_card_hardcover_row_enabled()'));
    /*
     * ⭐ AND THE THREE PRE-EXISTING CONDITIONS ARE STILL ALL REQUIRED. The new
     *    term only ever makes the row LESS likely to render, never more.
     */
    b418_assert('4.4a the three original conditions survive the rewrite',
        false !== strpos($b418_bf, "'paperback' === \$collection['format']")
            && false !== strpos($b418_bf, "function_exists('bhp_book_hardcover_is_offerable')")
            && false !== strpos($b418_bf, 'bhp_book_hardcover_is_offerable()'));

    /*
     * ⛔ AND IT GOVERNS ONE ROW ON ONE CARD. The pair card's own hardcover swap
     *    and the colouring line's upsell string are different surfaces and are
     *    NOT gated by this constant. If a later build widens it, this goes red.
     */
    /*
     * ⛔⛔ COUNT THE CALL SITE, NOT THE NAME — AND THIS ROW WAS WRONG FIRST TIME.
     *     Counting every occurrence of the bare name reads 3 on a CORRECT build:
     *     the function definition, one mention inside the explanatory docblock,
     *     and the one real call. The entry gate caught it failing a correct
     *     artefact, which the RUNBOOK names as the more expensive failure —
     *     it teaches the next builder to distrust a passing build. The fix is a
     *     sharper assertion, not a relaxed one.
     */
    $b418_call = '$bhp_cc_alt = (bhp_shop_card_hardcover_row_enabled()';
    b418_assert('4.5 ⛔ exactly ONE call site (one row on one card, not a sitewide kill switch)',
        1 === substr_count($b418_bf, $b418_call),
        substr_count($b418_bf, $b418_call) . ' call site(s)');
    b418_assert('4.5a exactly one function definition',
        1 === preg_match_all('/^function bhp_shop_card_hardcover_row_enabled/m', $b418_bf));

    $b418_cl = (string) @file_get_contents($b418_theme_dir . '/inc/colouring-line.php');
    b418_assert('4.6 the colouring line\'s own hardcover upsell string is untouched by this gate',
        false !== strpos($b418_cl, 'Prefer the hardcover? %s')
            && false === strpos($b418_cl, 'bhp_shop_card_hardcover_row_enabled'));
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · REGRESSION GUARDS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §5 regression guards --\n";

/*
 * ⛔ 1.19.417's THREE CHANGES MUST STILL BE THERE. This build touched style.css
 *    and inc/book-formats.php, both of which 417 also changed.
 */
$b418_style_raw = (string) @file_get_contents($b418_theme_dir . '/style.css');
$b418_style_code = b418_css_code($b418_style_raw);
b418_assert('5.1a 1.19.417 cover well is still 180px', 1 === substr_count($b418_style_code, '--bhp-cover-well: 180px'));
b418_assert('5.1b the ≤640px mobile well 132px is still present', false !== strpos($b418_style_code, '--bhp-cover-well: 132px'));
$b418_base_at = strpos($b418_style_code, '--bhp-cover-well: 180px');
$b418_mob_at  = strpos($b418_style_code, '--bhp-cover-well: 132px');
b418_assert('5.1c ⛔ and the 417 SOURCE-ORDER guard holds: base precedes mobile',
    false !== $b418_base_at && false !== $b418_mob_at && $b418_base_at < $b418_mob_at,
    "base@$b418_base_at mobile@$b418_mob_at");

$b418_bfcss = b418_css_code((string) @file_get_contents($b418_theme_dir . '/assets/css/book-formats.css'));
b418_assert('5.1d the 417 related/upsell centring rule is still there',
    false !== strpos($b418_bfcss, 'align-self: center;'));

/* The minified artefacts must match their sources, or the deploy ships stale CSS. */
foreach (array('style.css|style.min.css', 'assets/css/book-formats.css|assets/css/book-formats.min.css') as $b418_pair) {
    list($b418_src, $b418_min) = explode('|', $b418_pair);
    $b418_srcp = $b418_theme_dir . '/' . $b418_src;
    $b418_minp = $b418_theme_dir . '/' . $b418_min;
    if (!file_exists($b418_srcp) || !file_exists($b418_minp)) {
        b418_assert("5.2 source-md5 stamp for $b418_src", false, 'a file is missing');
        continue;
    }
    $b418_md5 = md5_file($b418_srcp);
    $b418_stamp = '';
    if (preg_match('/source-md5:\s*([0-9a-f]{32})/', (string) @file_get_contents($b418_minp), $b418_sm)) {
        $b418_stamp = $b418_sm[1];
    }
    /* ⛔ FAIL CLOSED. Two empty strings compared equal is the 1.19.417 gate bug. */
    b418_assert("5.2 $b418_min carries a non-empty stamp matching $b418_src",
        '' !== $b418_stamp && '' !== $b418_md5 && $b418_stamp === $b418_md5,
        "src=$b418_md5 stamp=" . ($b418_stamp ?: '(none)'));
}

if (function_exists('get_plugins')) {
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $b418_plug = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/brave-hearts-bundle-pricing.php';
    if (file_exists($b418_plug)) {
        $b418_pv = get_plugin_data($b418_plug, false, false);
        /*
         * ⛔ THE GUARD IS AGAINST A DOWNGRADE, WHICH IS WHAT ACTUALLY HAPPENED
         *    AT 1.19.413 — the plugin silently went back two versions while the
         *    installer reported success. A FLOOR catches that. An equality also
         *    catches it, and additionally goes red the first time the plugin is
         *    legitimately upgraded, which is the trap §0.1 above records.
         *    ⭐ THE BUILD REPORT STILL STATES THE EXACT VERSION READ BACK; the
         *    assertion is the floor, the evidence is the number.
         */
        b418_assert('5.3 ⛔ the bundle plugin is 1.8.94 or later — never a silent downgrade (the 413 incident)',
            '' !== $b418_pv['Version'] && version_compare($b418_pv['Version'], '1.8.94', '>='),
            $b418_pv['Version']);
        b418_assert('5.3a ⭐ and this build did not move it: it is exactly 1.8.94, read back rather than assumed',
            '1.8.94' === $b418_pv['Version'], $b418_pv['Version']);
    } else {
        b418_skip('5.3 the bundle plugin is still 1.8.94', 'bundle plugin not installed on this environment');
    }
} else {
    b418_skip('5.3 the bundle plugin is still 1.8.94', 'plugin API unavailable');
}

/* ═══════════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════════ */
printf(
    "\n=== CYCLE180-LD-BUILD-418: %d passed, %d failed, %d skipped ===\n",
    $GLOBALS['b418_passes'],
    $GLOBALS['b418_failures'],
    $GLOBALS['b418_skips']
);

if ($GLOBALS['b418_failures'] > 0) {
    exit(1);
}
