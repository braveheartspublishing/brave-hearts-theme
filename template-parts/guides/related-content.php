<?php
/** Reciprocal guide and related-article continuation block. */
defined('ABSPATH') || exit;
$post = get_post($args['post'] ?? null);
$data = bhp_get_guide_post_data($post);
if (!$post) { return; }
// Phase 1D: posts outside the ~30-entry curated guide registry
// previously got NOTHING after the article (silent early return below).
// Fall back to the contextual CTA engine instead of leaving those posts
// with no end-of-article path forward. Registry posts are completely
// unaffected -- this only runs when $data is empty, so the curated
// guide-continuation experience below never changes for the posts it
// already serves.
if (!$data) {
    // Duplicate-CTA prevention: a post that already carries an explicit
    // [bhp_contextual_cta] shortcode (e.g. AI-generator drafts, or an
    // editor's own manual placement) gets that one CTA only -- the
    // automatic end-of-article fallback is skipped so the same page
    // never renders two near-identical CTA blocks. has_shortcode() is
    // WordPress's own parser, not text matching, so this is accurate
    // even if the shortcode has attributes or unusual whitespace.
    /*
     * ═══════════════════════════════════════════════════════════════════════
     * ⭐⭐ 1.19.344 (2026-08-31, `CYCLE173-LD-344`) — THIS FALLBACK NO LONGER
     *     RUNS ON A SINGLE BLOG POST. FOUNDER ORDER.
     * ═══════════════════════════════════════════════════════════════════════
     *
     * ⭐ Andrew Signore, 2026-08-31, ⚠ RELAYED by the Chief of Staff and NOT
     *    witnessed first-hand by the session that wrote this line (Standing
     *    Rules §9.2 rule 3 — the distinction is recorded, not glossed):
     *
     *      "There is Big redundancy on the blog pages! - we have FREE chapter
     *       for reluctant readers then another box saying get the free
     *       reluctant reader kit - Remove the Get the Free kit and keep the
     *       email capture one-"
     *
     * WHAT HE WAS LOOKING AT, MEASURED IN THE LIVE PRODUCTION DOM rather than
     * inferred from this template (`/blog/how-was-mount-everest-formed-for-
     * kids/`, real browser, 2026-08-31). Document order inside <article>:
     *
     *     .bhp-book-rail          "The book this came from"        (KEPT)
     *     .bhp-post-capture       "FREE Chapter for Reluctant       (KEPT)
     *                              Readers" + inline email form
     *     THIS BLOCK              "Get the Free Reluctant Reader   (REMOVED)
     *                              Adventure Kit" -> "Get the Free Kit"
     *
     * ⛔ BOTH ASKED FOR THE SAME LEAD MAGNET. The capture posts to Mailchimp
     *    for `reluctant_reader_adventure_kit`; this block's CTA resolves to
     *    `adventure_kit_signup` and links to the kit page. Two consecutive
     *    boxes, one offer. That is the redundancy, and it is real.
     *
     * ⭐⭐ THIS SUPERSEDES THE CARRIER-110/119 "TWO ASKS" DOCTRINE FOR SINGLE
     *     POSTS, AND THE DOCTRINE COMMENT IS DELIBERATELY NOT DELETED. It is
     *     at `inc/blog-post-template.php` ~917 and carries a dated supersession
     *     note pointing here. ⛔ Do not read this change as reopening item
     *     110/118: the BOOK RAIL is a book bridge, not an ask, and is
     *     untouched.
     *
     * ⚠ WHY THE DOCTRINE DID NOT CATCH THIS ITSELF — worth knowing before
     *   trusting its counter again. `tests/test-protected-elements.php` counts
     *   asks as `.bhp-post-capture` + the popup + the footer capture. It never
     *   counted a contextual-CTA block, so a third ask for the same magnet
     *   could sit on every non-registry post and the assertion still read
     *   exactly 2. The count was right about what it measured and blind to
     *   this block. Registered as `CYCLE173-LD-3`.
     *
     * ⛔ SUPPRESSED AT THE CALL SITE, NOT IN THE ENGINE. `BHP_CTA_Engine` and
     *    the `[bhp_contextual_cta]` shortcode are BYTE-UNTOUCHED, so landing
     *    pages, product surfaces and any editor-placed shortcode keep the
     *    exact behaviour they had at 1.19.343. The founder's order was about
     *    blog pages; retiring the CTA everywhere would have been a far larger
     *    change than he asked for.
     *
     * ⭐ AND IT IS A TWO-WAY SWITCH. `add_filter(
     *    'bhp_blog_end_cta_fallback_enabled', '__return_true' )` restores it in
     *    one line with no code deleted — the house rule from
     *    `bhp_blog_rail_enabled()`: "a switch that only travels one way is not
     *    a switch."
     *
     * ⚠ IN PRACTICE THIS TEMPLATE IS ONLY EVER LOADED FROM `single.php`, so
     *   the default below disables the fallback on every real page load today.
     *   The `is_singular('post')` test is still written explicitly rather than
     *   hardcoding `false`: the test suite calls this template part directly
     *   outside the loop, and a future non-post caller should inherit the
     *   pre-1.19.344 behaviour rather than this founder ruling about blogs.
     */
    $bhp_end_cta_enabled = ! is_singular('post');
    $bhp_end_cta_enabled = (bool) apply_filters('bhp_blog_end_cta_fallback_enabled', $bhp_end_cta_enabled, $post);

    if ($bhp_end_cta_enabled && class_exists('BHP_CTA_Engine') && !has_shortcode($post->post_content, 'bhp_contextual_cta')) {
        BHP_CTA_Engine::render_for_post($post->ID, 'blog_end_of_article');
    }
    return;
}
$hubs = bhp_get_guide_hubs();
$related = bhp_get_related_guide_posts($post, 4);
$adventures = function_exists('bhp_get_series_adventures') ? bhp_get_series_adventures() : [];
$book_urls = [
    'mariana-trench' => bhp_get_safe_link_url($adventures['mariana_trench']['primary_url'] ?? '', home_url('/books/')),
    'mount-everest' => bhp_get_safe_link_url($adventures['mount_everest']['primary_url'] ?? '', home_url('/books/')),
    'series-wide' => home_url('/books/'),
];
?>
<aside class="guide-continuation" aria-labelledby="guide-continuation-title">
  <p class="component-heading__eyebrow"><?php esc_html_e('Continue the expedition', 'brave-hearts'); ?></p>
  <h2 id="guide-continuation-title"><?php esc_html_e('Follow This Trail Further', 'brave-hearts'); ?></h2>
  <div class="guide-continuation__paths">
    <a href="<?php echo esc_url(bhp_get_guide_hub_url($data['primary'])); ?>"><?php echo esc_html(sprintf(__('Continue exploring %s', 'brave-hearts'), $hubs[$data['primary']] ?? $data['primary'])); ?></a>
    <?php if (!empty($data['destination'])): ?><a href="<?php echo esc_url(bhp_get_guide_hub_url($data['destination'])); ?>"><?php echo esc_html(sprintf(__('Visit the %s Expedition Guide', 'brave-hearts'), $hubs[$data['destination']] ?? $data['destination'])); ?></a><?php endif; ?>
    <?php if (!empty($book_urls[$data['book'] ?? ''])): ?><a href="<?php echo esc_url($book_urls[$data['book']]); ?>"><?php esc_html_e('Explore the related Brave Hearts book', 'brave-hearts'); ?></a><?php endif; ?>
    <?php if (in_array('Educators', $data['audiences'] ?? [], true)): ?><a href="<?php echo esc_url(bhp_get_guide_hub_url('educator-resources')); ?>"><?php esc_html_e('Find resources for educators', 'brave-hearts'); ?></a><a href="<?php echo esc_url(home_url('/educators-adventure-learning-toolkit/')); ?>"><?php esc_html_e('Get the free Adventure Learning Toolkit', 'brave-hearts'); ?></a><?php endif; ?>
    <?php if (in_array('Families', $data['audiences'] ?? [], true)): ?><a href="<?php echo esc_url(bhp_get_guide_hub_url('family-resources')); ?>"><?php esc_html_e('Continue exploring as a family', 'brave-hearts'); ?></a><?php endif; ?>
    <?php if (in_array('Gift Buyers', $data['audiences'] ?? [], true)): ?><a href="<?php echo esc_url(home_url('/gift-buyers-guide/')); ?>"><?php esc_html_e('Shopping for a meaningful gift?', 'brave-hearts'); ?></a><?php endif; ?>
    <?php if (in_array('Organizations', $data['audiences'] ?? [], true)): ?><a href="<?php echo esc_url(home_url('/organizations-community-reading-kit/')); ?>"><?php esc_html_e('Planning a reading program?', 'brave-hearts'); ?></a><?php endif; ?>
  </div>
  <?php if ($related): ?>
    <h3><?php esc_html_e('Related Field Notes', 'brave-hearts'); ?></h3>
    <div class="guide-article-grid guide-article-grid--related">
      <?php foreach ($related as $related_post) { get_template_part('template-parts/guides/article-card', null, ['post' => $related_post]); } ?>
    </div>
  <?php endif; ?>
</aside>
