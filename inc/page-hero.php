<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.355 — THE PLAIN PAGE HERO. `CYCLE179-LD-355`, brief item 4.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Two defects from the 2026-09-02 aesthetic review, both of them properties of
 * `page.php` rather than of any one page, which is why the fix is a pair of
 * functions and not a pair of content edits.
 *
 *   `D1`  THE SAME <h1> RENDERS TWICE. MEASURED on staging 1.19.354, headless
 *         Chrome, `window.innerWidth` asserted 1440: `/read-alouds/` reports
 *         `h1_count = 2`, both reading "Read-Aloud Books & Classroom Resources
 *         for Grades 1-3", at y130 in the hero and again at y518 as the first
 *         heading of the content card. The review records the same count on
 *         four SEO hub pages. One document, two top-level headings, on pages
 *         built to be found in search.
 *
 *   `D8`  THE DECORATIVE COORDINATE "FIELD NOTE · BHP" RENDERS ABOVE UTILITY
 *         PAGE TITLES. MEASURED on the same run: present on `/my-account/`
 *         (y215) and on `/privacy-policy/`. On a login form it is decoration
 *         standing where an explanation would go.
 *
 * ⛔ NEITHER FUNCTION TOUCHES POST CONTENT IN THE DATABASE. `D1` is fixed at
 *    RENDER, on the output of `the_content`, so the stored article is exactly
 *    what its author wrote and reverting is one file. Editing live copy is
 *    listed OUT OF SCOPE in the brief and is Andrew's.
 *
 * ⛔ NEITHER IS A `the_content` FILTER. `docs\DECISIONS.md` and this theme's
 *    own rules record the removed Teachers-page `the_content` filter and say it
 *    must not come back; a global content filter also reaches feeds, the REST
 *    API, excerpts and every other template. Both functions below are called
 *    explicitly from `page.php` and run nowhere else.
 *
 * @package Brave_Hearts
 * @since   1.19.355
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normalise a heading or a title down to the thing worth comparing.
 *
 * ⭐ WHY A HELPER RATHER THAN A COMPARISON IN PLACE. The two strings arrive
 *    from different places and are shaped differently by the time they meet:
 *    `the_title()` has been through `wptexturize()`, while the content's
 *    heading has been through `wptexturize()` AND `wpautop()` AND whatever the
 *    editor stored, so one may carry a curly apostrophe and the other a
 *    straight one, and either may carry `&amp;` where the other carries `&`.
 *    ⛔ A naive `===` on those two produces a FALSE NEGATIVE, which fails in
 *    the safe direction (nothing is removed) and would therefore have shipped
 *    silently doing nothing.
 *
 * @since 1.19.355
 * @param string $value Raw heading text or title, markup allowed.
 * @return string Lower-cased, entity-decoded, tag-free, whitespace-collapsed.
 */
function bhp_page_normalise_heading($value) {
    $value = wp_strip_all_tags((string) $value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Curly quotes and dashes back to their plain forms so texturised and
    // untexturised copies of the same sentence compare equal.
    $value = strtr($value, [
        "\xE2\x80\x98" => "'",
        "\xE2\x80\x99" => "'",
        "\xE2\x80\x9C" => '"',
        "\xE2\x80\x9D" => '"',
        "\xE2\x80\x93" => '-',
        "\xE2\x80\x94" => '-',
        "\xC2\xA0"     => ' ',
    ]);
    $value = preg_replace('/\s+/u', ' ', $value);

    return function_exists('mb_strtolower')
        ? mb_strtolower(trim($value), 'UTF-8')
        : strtolower(trim($value));
}

/**
 * Remove the content's own <h1> when it merely repeats the hero's.
 *
 * ⭐⭐ IT REMOVES ONE HEADING, AND ONLY WHEN IT IS A DUPLICATE. The test is
 *     equality after normalisation, not similarity and not position. A page
 *     whose first content heading says something different keeps it, because
 *     that heading is carrying information; a page whose first content heading
 *     restates the title keeps nothing, because it was carrying none.
 *
 * ⛔ ONLY THE FIRST MATCH IS EVER REMOVED, and only if it is the first <h1> in
 *    the content. A page with two identical <h1>s inside its content has a
 *    different problem and this function must not quietly half-fix it.
 *
 * ⛔ IT FAILS OPEN, EVERYWHERE. No `<h1>` in the content, a title that does not
 *    match, a `preg_replace` that returns null on a backtrack limit: every one
 *    of those returns the content byte-for-byte unchanged. Removing a heading
 *    wrongly costs a page its subject line; leaving one costs a duplicate.
 *
 * ⚠️ THE HEADING'S OWN ATTRIBUTES GO WITH IT. If a duplicate <h1> carried an
 *    `id` used as an in-page anchor target, that anchor stops resolving. ⭐ No
 *    such anchor exists on the five affected pages: checked by reading the
 *    served DOM for `href="#"` targets matching the removed nodes' ids, and
 *    the removed nodes carry no `id` at all. Recorded rather than assumed.
 *
 * @since 1.19.355
 * @param string $content Rendered post content.
 * @param string $title   The title the hero is already printing.
 * @return string
 */
function bhp_page_drop_duplicate_h1($content, $title) {
    $content = (string) $content;
    $needle  = bhp_page_normalise_heading($title);

    if ('' === $needle || false === stripos($content, '<h1')) {
        return $content;
    }

    if (!preg_match('/<h1\b[^>]*>(.*?)<\/h1\s*>/is', $content, $m, PREG_OFFSET_CAPTURE)) {
        return $content;
    }

    if (bhp_page_normalise_heading($m[1][0]) !== $needle) {
        return $content; // A heading that says something else. It stays.
    }

    $out = substr_replace($content, '', $m[0][1], strlen($m[0][0]));

    /**
     * The page content after a duplicated top-level heading has been dropped.
     *
     * ⛔ A SEAM FOR TESTS AND FOR A FOUNDER RULING, not a configuration point.
     *    It changes what is PRINTED and nothing else.
     *
     * @since 1.19.355
     * @param string $out     Content with the duplicate removed.
     * @param string $content The content as it arrived.
     * @param string $title   The hero title it was compared against.
     */
    return (string) apply_filters('bhp_page_dropped_duplicate_h1', $out, $content, $title);
}

/**
 * Does this page get the decorative "FIELD NOTE · BHP" coordinate?
 *
 * ⭐⭐ THE RULE, IN ONE SENTENCE: a page WordPress or WooCommerce has designated
 *     as a functional or legal page does not get an ornament above its title.
 *
 * ⛔ THE SET IS READ FROM OPTIONS, NEVER FROM HARDCODED IDs. Every id below is
 *    resolved from the option that owns it, so a site that moves its privacy
 *    policy or rebuilds its account page keeps working with no code change:
 *
 *      wp_page_for_privacy_policy          the privacy policy
 *      woocommerce_myaccount_page_id       my account and every endpoint
 *      woocommerce_cart_page_id            the cart
 *      woocommerce_checkout_page_id        the checkout
 *      woocommerce_shop_page_id            the shop
 *      woocommerce_terms_page_id           terms and conditions
 *      woocommerce_refund_returns_page_id  refunds and returns
 *
 * ⚠️ ONE PAGE IN THIS GROUP HAS NO OPTION BEHIND IT AND IS THEREFORE RESOLVED
 *    BY SLUG: `/shipping-policy/`. It is an ordinary page that nothing in
 *    WordPress or WooCommerce marks as a policy, so there is no id to read. The
 *    slug lookup is cached in a static for the request and fails silently when
 *    the page is absent. ⛔ IT IS THE ONE BRITTLE LINE IN THIS FUNCTION and it
 *    is flagged rather than hidden: renaming that page's slug quietly restores
 *    its coordinate. The alternative was to leave a policy page carrying
 *    "FIELD NOTE · BHP", which is the defect.
 *
 * ⛔ IT SUPPRESSES DECORATION AND NOTHING ELSE. The eyebrow this replaces is
 *    `aria-hidden="true"` and carries no information, so nothing is lost to a
 *    reader or to assistive technology. The `<h1>`, the hero and the page body
 *    are untouched. The brief allows "nothing or a page-appropriate eyebrow";
 *    ⭐ NOTHING IS CHOSEN, because inventing a new eyebrow string for seven
 *    pages would be authoring customer-facing copy, which is Andrew's.
 *
 * @since 1.19.355
 * @param int $page_id The page being rendered.
 * @return bool
 */
function bhp_page_hero_shows_coordinate($page_id) {
    $page_id = (int) $page_id;
    $show    = true;

    if (function_exists('is_cart') && function_exists('is_checkout') && function_exists('is_account_page')) {
        if (is_cart() || is_checkout() || is_account_page()) {
            $show = false;
        }
    }

    if ($show && $page_id > 0) {
        static $utility_ids = null;

        if (null === $utility_ids) {
            $utility_ids = [];

            foreach ([
                'wp_page_for_privacy_policy',
                'woocommerce_myaccount_page_id',
                'woocommerce_cart_page_id',
                'woocommerce_checkout_page_id',
                'woocommerce_shop_page_id',
                'woocommerce_terms_page_id',
                'woocommerce_refund_returns_page_id',
            ] as $bhp_option) {
                $bhp_id = (int) get_option($bhp_option, 0);
                if ($bhp_id > 0) {
                    $utility_ids[] = $bhp_id;
                }
            }

            // The one page in the group with no option behind it. See above.
            $bhp_shipping = get_page_by_path('shipping-policy');
            if ($bhp_shipping instanceof WP_Post) {
                $utility_ids[] = (int) $bhp_shipping->ID;
            }

            $utility_ids = array_values(array_unique(array_filter($utility_ids)));
        }

        if (in_array($page_id, $utility_ids, true)) {
            $show = false;
        }
    }

    /**
     * Whether the plain page hero prints its decorative coordinate.
     *
     * ⛔ DECORATION ONLY. Filtering this adds or removes an `aria-hidden`
     *    ornament; it opens no gate, changes no heading and moves no content.
     *
     * @since 1.19.355
     * @param bool $show
     * @param int  $page_id
     */
    return (bool) apply_filters('bhp_page_hero_coordinate', $show, $page_id);
}
