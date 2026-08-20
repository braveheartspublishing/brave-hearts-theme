<?php
/**
 * Template Name: Brave Hearts Books Page
 * Description: Customer-facing Charlotte & Henry series page grouped by adventure.
 */
defined('ABSPATH') || exit;
get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();
$adventures = bhp_get_series_adventures();

foreach ($adventures as $key => &$adventure) {
    $url_override = bhp_get_safe_link_url(get_post_meta($page_id, 'bhp_books_' . $key . '_url', true));
    $amazon_url = bhp_get_safe_link_url(get_post_meta($page_id, 'bhp_books_' . $key . '_amazon_url', true));
    $amazon_host = strtolower((string) wp_parse_url($amazon_url, PHP_URL_HOST));
    $is_amazon_host = in_array($amazon_host, ['amzn.to', 'a.co'], true)
        || (bool) preg_match('/(^|\.)amazon\.(com|ca|de|fr|it|es|nl|in|sg|ae|sa|se|pl|co\.uk|com\.au|co\.jp|com\.br|com\.mx|com\.be|com\.tr)$/', $amazon_host);
    if ($amazon_url && !$is_amazon_host) {
        $amazon_url = '';
    }
    $image_override = (int) get_post_meta($page_id, 'bhp_books_' . $key . '_image_id', true);
    if ($url_override) {
        $adventure['primary_url'] = $url_override;
        $adventure['available'] = true;
    }
    if ($image_override) {
        $adventure['image_id'] = $image_override;
    }
    $adventure['amazon_url'] = $amazon_url;
}
unset($adventure);

$shop_url = home_url('/shop/');
$book_one_url = bhp_get_safe_link_url($adventures['mariana_trench']['primary_url'], $shop_url);

get_template_part('template-parts/components/hero', null, [
    'id'       => 'books-hero',
    'class'    => 'books-hero',
    'eyebrow'  => __('The Adventures of Charlotte & Henry', 'brave-hearts'),
    'title'    => __('Explore the Charlotte & Henry Adventures', 'brave-hearts'),
    'text'     => __('Real places. Real science. Brave stories for curious kids ages 6–9.', 'brave-hearts'),
    'primary_link' => [
        'url'   => $book_one_url,
        'label' => __('Start with Book 1', 'brave-hearts'),
    ],
    /*
     * ⭐⭐ 1.19.269 (`CYCLE165-LD-ITERATE-5-SUBTRACTIONS`) — ONE PRIMARY ON
     *     THIS PAGE. `secondary_link` IS GONE.
     *
     * FOUNDER RULING, 2026-08-19, subtraction item 3. The line he answered
     * "Stage it" to, verbatim: "/books/'s second above-fold CTA ('Shop All'
     * beside 'Start with Book 1') — one primary."
     *
     * ⛔ WHAT WAS HERE, preserved so the movement is visible:
     *      'secondary_link' => [
     *          'url'   => $shop_url,                       // home_url('/shop/')
     *          'label' => __('Shop All Adventure Books', 'brave-hearts'),
     *      ],
     *
     * ⭐ NOTHING BECOMES UNREACHABLE. `/shop/` is still reached from the
     *    header, from the pruned footer's "The Books" entry, and from the
     *    adventure grid immediately below this hero — which is what a visitor
     *    who wanted "shop all" was actually going to scroll to anyway.
     *    `$shop_url` itself is KEPT: it is still the fallback destination for
     *    `$book_one_url` two lines above, so it is not an unused variable.
     *
     * ⛔ "START WITH BOOK 1" IS UNTOUCHED — same label, same destination, same
     *    position. The founder kept it by name.
     */
]);
?>
<section id="series-overview" class="books-section books-series-overview section" aria-labelledby="series-overview-title">
  <div class="container books-series-overview__layout">
    <div>
      <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, a slogan; the H2 already names the section:
              <p class="component-heading__eyebrow"><?php esc_html_e('One growing series. A world of discovery.', 'brave-hearts'); ?></p>
         The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
      <h2 id="series-overview-title" class="text-section-title"><?php esc_html_e('Stories That Travel Somewhere Real', 'brave-hearts'); ?></h2>
    </div>
    <div class="books-series-overview__content text-lead flow">
      <p><?php esc_html_e('Charlotte and Henry travel to extraordinary real-world places, discovering science, geography, wildlife, courage, and kindness along the way.', 'brave-hearts'); ?></p>
      <p><?php esc_html_e('Each book is created for growing readers ages 6–9, with short chapters, supportive illustrations, meaningful learning, and an adventure that keeps moving.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.171 (2026-08-05) — CYCLE144-LD-12. THIS BAND IS NOW THE HOMEPAGE'S.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (relayed through the Chief of Staff;
 * NOT witnessed first-hand by this agent):
 *
 *   "On the adventure books page - keep it consistent with the homepage -
 *    meaning put the same Best Value - The Complete section with the savings
 *    numbers where is says Get all Three adventures, use the same homepage
 *    one."
 *
 * WHAT WAS HERE: a locally-written `#books-complete-collection-banner` with
 * its own heading ("Get All Three Adventures"), its own body sentence, no
 * "Best Value" badge, no primacy line and NO SAVINGS NUMBERS. It shared only
 * the collection gallery and the CTA with the homepage band.
 *
 * WHAT IS HERE NOW: `template-parts/components/complete-collection-feature.php`
 * — literally the homepage's own markup, the same file the homepage renders.
 * Not a copy of it. Read that file's header for why a copy would have
 * re-created this defect.
 *
 * ⭐ B7 IS PRESERVED, and preserving it is why the partial takes a `cta`
 *    argument at all. Andrew, walk-3 2026-08-03, verbatim: "I want less steps
 *    to purchase." `'cta' => 'checkout'` keeps the exact behaviour this page
 *    already had: the same `complete_<format>_smart` POST, the same
 *    `bhp_bundle_default_format()` read, the same /checkout/ redirect, the
 *    same "Or read about the collection first" escape hatch, and the same
 *    plugin-inactive fallback to a plain link. One current-turn instruction
 *    does not silently repeal an earlier one.
 *
 * ⛔ IDS ARE PAGE-SCOPED SO NOTHING COLLIDES. The section keeps this page's
 *    own `#books-complete-collection-banner` id and its own heading id — the
 *    homepage's `#home-sales-paths` is not duplicated onto a second URL, and
 *    the existing `.books-complete-collection-banner` spacing rules keep
 *    applying through `section_class`.
 */
get_template_part('template-parts/components/complete-collection-feature', null, [
    'cta'           => 'checkout',
    'section_id'    => 'books-complete-collection-banner',
    'section_class' => 'books-section books-complete-collection-banner section--sm',
    'title_id'      => 'books-complete-collection-banner-title',
]);
?>

<section id="adventure-book-grid" class="books-section section section--muted" aria-labelledby="adventure-book-grid-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Choose an adventure', 'brave-hearts'); ?></p>
      <h2 id="adventure-book-grid-title" class="text-section-title"><?php esc_html_e('The Charlotte & Henry Series', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Begin at the deepest place on Earth, climb toward the highest, or journey into the world’s largest tropical rainforest.', 'brave-hearts'); ?></p>
    </header>
    <div class="adventure-book-grid">
      <?php foreach ($adventures as $adventure): ?>
        <?php get_template_part('template-parts/books/adventure-book-card', null, array_merge($adventure, ['shop_url' => $shop_url])); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="book-formats" class="books-section book-format-note section section--sm" aria-labelledby="book-formats-title">
  <div class="container container--content book-format-note__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Choose the format that fits your reader', 'brave-hearts'); ?></p>
    <h2 id="book-formats-title" class="text-section-title"><?php esc_html_e('Paperback and Hardcover', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('Available formats are shown on each published book card. Selection may vary by adventure as new editions are released.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php
get_template_part('template-parts/components/teacher-resources-cta', null, [
    'id'      => 'books-teacher-resources',
    'eyebrow' => __('Adventure books made for shared learning', 'brave-hearts'),
    'title'   => __('Bring Charlotte & Henry Into Your Classroom', 'brave-hearts'),
    'text'    => __('Use the adventures for read-alouds, geography, science, vocabulary, and thoughtful discussion with curious readers in Grades 1–3.', 'brave-hearts'),
    'items'   => [
        __('Classroom and family read-alouds', 'brave-hearts'),
        __('Geography and real-world science connections', 'brave-hearts'),
        __('Vocabulary and discussion support', 'brave-hearts'),
        __('Printables and teacher resources', 'brave-hearts'),
    ],
    'link'    => [
        'url'   => home_url('/teachers/'),
        'label' => __('Explore Educator Guides', 'brave-hearts'),
    ],
    'class'   => 'teacher-resources-cta--text-only',
]);
?>

<section id="books-final-cta" class="books-final-cta final-cta section" aria-labelledby="books-final-cta-title">
  <div class="container container--content final-cta__inner">
    <?php /* ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) — REMOVED, the brand line used as decoration above a CTA:
            <p class="component-heading__eyebrow"><?php esc_html_e('Big Places. Brave Hearts.', 'brave-hearts'); ?></p>
       The keep/remove test is stated once, in full, in `page-about.php`. */ ?>
    <h2 id="books-final-cta-title" class="text-section-title"><?php esc_html_e('Start the Adventure Today', 'brave-hearts'); ?></h2>
    <p class="text-lead final-cta__text"><?php esc_html_e('Choose the next Charlotte & Henry adventure, bring the books into your classroom, or join the Adventure Club.', 'brave-hearts'); ?></p>
    <div class="final-cta__actions cluster">
      <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Shop the Adventure Books', 'brave-hearts'); ?></a>
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explore Educator Guides', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Join the Adventure Club', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
