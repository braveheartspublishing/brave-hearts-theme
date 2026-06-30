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
    if ($amazon_url && $amazon_host !== 'amzn.to' && !preg_match('/(^|\.)amazon\.[a-z.]+$/', $amazon_host)) {
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
    'secondary_link' => [
        'url'   => $shop_url,
        'label' => __('Shop All Books', 'brave-hearts'),
    ],
]);
?>
<section id="series-overview" class="books-section books-series-overview section" aria-labelledby="series-overview-title">
  <div class="container books-series-overview__layout">
    <div>
      <p class="component-heading__eyebrow"><?php esc_html_e('One growing series. A world of discovery.', 'brave-hearts'); ?></p>
      <h2 id="series-overview-title" class="text-section-title"><?php esc_html_e('Stories That Travel Somewhere Real', 'brave-hearts'); ?></h2>
    </div>
    <div class="books-series-overview__content text-lead flow">
      <p><?php esc_html_e('Charlotte and Henry travel to extraordinary real-world places, discovering science, geography, wildlife, courage, and kindness along the way.', 'brave-hearts'); ?></p>
      <p><?php esc_html_e('Each book is created for growing readers ages 6–9, with short chapters, supportive illustrations, meaningful learning, and an adventure that keeps moving.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

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
    <h2 id="book-formats-title" class="text-section-title"><?php esc_html_e('Kindle, Paperback, and Hardcover', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('Available formats are shown on each published book card. Selection may vary by adventure as new editions are released.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php
get_template_part('template-parts/components/teacher-resources-cta', null, [
    'id'      => 'books-teacher-resources',
    'eyebrow' => __('Books made for shared learning', 'brave-hearts'),
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
        'label' => __('Explore Teacher Resources', 'brave-hearts'),
    ],
    'class'   => 'teacher-resources-cta--text-only',
]);
?>

<section id="books-final-cta" class="books-final-cta final-cta section" aria-labelledby="books-final-cta-title">
  <div class="container container--content final-cta__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Big Places. Brave Hearts.', 'brave-hearts'); ?></p>
    <h2 id="books-final-cta-title" class="text-section-title"><?php esc_html_e('Start the Adventure Today', 'brave-hearts'); ?></h2>
    <p class="text-lead final-cta__text"><?php esc_html_e('Choose the next Charlotte & Henry adventure, bring the books into your classroom, or join the Adventure Club.', 'brave-hearts'); ?></p>
    <div class="final-cta__actions cluster">
      <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Shop the Books', 'brave-hearts'); ?></a>
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explore Teacher Resources', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/#adventure-club')); ?>"><?php esc_html_e('Join the Adventure Club', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
