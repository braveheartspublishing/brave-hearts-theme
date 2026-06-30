<?php
/**
 * Brave Hearts Publishing — Adventure Gateway Homepage
 *
 * Content can be overridden with public bhp_home_* custom fields on the
 * static front page. Repeating card collections are filterable for future
 * structured-content integrations.
 */
defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();

// Load the live book collection once for the hero preview, destinations, and book grid.
$featured_books = bhp_get_homepage_books(-1);
$find_home_book = static function ($destination) use ($featured_books) {
    $fallback = [];
    foreach ($featured_books as $book) {
        if (stripos($book['title'], $destination) !== false) {
            $formats = is_array($book['formats'] ?? null) ? $book['formats'] : [];
            if (in_array('Paperback', $formats, true) || stripos($book['title'], 'paperback') !== false) {
                return $book;
            }
            if (!$fallback) {
                $fallback = $book;
            }
        }
    }
    return $fallback;
};

$hero_preview_books = array_values(array_filter([
    $find_home_book('Mariana Trench'),
    $find_home_book('Mount Everest'),
    $find_home_book('Amazon Rainforest'),
], static function ($book) {
    return !empty($book['image_id']) && !empty($book['url']) && !empty($book['title']);
}));
$hero_preview_ids = array_map(static function ($book) {
    return (int) ($book['product_id'] ?? 0);
}, $hero_preview_books);
foreach ($featured_books as $book) {
    $book_id = (int) ($book['product_id'] ?? 0);
    if (count($hero_preview_books) >= 3) {
        break;
    }
    if (!empty($book['image_id']) && !empty($book['url']) && !empty($book['title']) && !in_array($book_id, $hero_preview_ids, true)) {
        $hero_preview_books[] = $book;
        $hero_preview_ids[] = $book_id;
    }
}

// 1. Hero: begin with wonder and invite visitors into the real world.
$hero_title = bhp_get_homepage_field('hero_title', __("Big Places.\nBrave Hearts.", 'brave-hearts'));
if (preg_match('/^Big Places\.\s*Brave Hearts\.$/i', trim($hero_title))) {
    $hero_title = __("Big Places.\nBrave Hearts.", 'brave-hearts');
}
$hero_eyebrow = bhp_get_homepage_field('hero_eyebrow', __('Stories that begin on the page and continue outside', 'brave-hearts'));
if (trim($hero_eyebrow) === 'Bridge books for ages 6–9') {
    $hero_eyebrow = __('Stories that begin on the page and continue outside', 'brave-hearts');
}
$hero_text = bhp_get_homepage_field('hero_text', __('<p>A child closes the book. The next morning, the sky, birds, and trees are the same—but now they notice, wonder, ask, and explore.</p><ul class="home-hero__destinations"><li>Ocean depths</li><li>Mountain heights</li><li>Rainforest trails</li></ul>', 'brave-hearts'));
if (stripos(wp_strip_all_tags($hero_text), 'STEM') !== false) {
    $hero_text = __('<p>A child closes the book. The next morning, the sky, birds, and trees are the same—but now they notice, wonder, ask, and explore.</p><ul class="home-hero__destinations"><li>Ocean depths</li><li>Mountain heights</li><li>Rainforest trails</li></ul>', 'brave-hearts');
}

if ($hero_preview_books) {
    ob_start();
    ?>
    <div class="home-hero__book-preview" role="group" aria-labelledby="home-hero-books-label">
      <p id="home-hero-books-label" class="home-hero__book-preview-label"><?php esc_html_e('Real places. Doors into wonder.', 'brave-hearts'); ?></p>
      <ul class="home-hero__book-stack">
        <?php foreach (array_slice($hero_preview_books, 0, 3) as $book_index => $book): ?>
          <li>
            <a href="<?php echo esc_url($book['url']); ?>">
              <?php echo wp_get_attachment_image((int) $book['image_id'], 'bhp-book-card', false, [
                  'class'   => 'home-hero__book-cover',
                  'alt'     => $book['image_alt'] ?: sprintf(__('%s book cover', 'brave-hearts'), $book['title']),
                  'loading' => $book_index === 0 ? 'eager' : 'lazy',
                  'sizes'   => '(max-width: 640px) 28vw, 180px',
              ]); ?>
              <span><?php echo esc_html($book['title']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php
    $hero_text .= ob_get_clean();
}

$hero_primary_label = bhp_get_homepage_field('hero_primary_label', __('Choose Your First Adventure', 'brave-hearts'));
if (in_array(trim($hero_primary_label), ['Shop the Books', 'Choose a Real-World Adventure'], true)) {
    $hero_primary_label = __('Choose Your First Adventure', 'brave-hearts');
}

get_template_part('template-parts/components/hero', null, [
    'id'             => 'home-hero',
    'eyebrow'        => $hero_eyebrow,
    'title'          => $hero_title,
    'text'           => $hero_text,
    'image_id'       => (int) bhp_get_homepage_field('hero_image_id', 0),
    'class'          => $hero_preview_books ? 'home-hero--with-books' : '',
    'primary_link'   => [
        'url'   => bhp_get_homepage_field('hero_primary_url', '#explore-world'),
        'label' => $hero_primary_label,
    ],
    'secondary_link' => [],
]);

// 2. Philosophy: connect the opening sense of wonder to the purpose behind every story.
?>
<section id="home-philosophy" class="homepage-section home-philosophy section" aria-labelledby="home-philosophy-title">
  <div class="container home-philosophy__inner">
    <header class="component-heading component-heading--center home-philosophy__heading">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('philosophy_eyebrow', __('Our philosophy', 'brave-hearts'))); ?></p>
      <h2 id="home-philosophy-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('philosophy_title', __('Nature is the greatest classroom on Earth.', 'brave-hearts'))); ?></h2>
      <p class="home-philosophy__declaration"><?php echo esc_html(bhp_get_homepage_field('philosophy_declaration', __('The real world is still wild enough.', 'brave-hearts'))); ?></p>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('philosophy_intro', __('A Brave Hearts story is a beginning: adventure awakens curiosity, truth gives it somewhere to go, and character helps a child carry each discovery into the world.', 'brave-hearts'))); ?></p>
    </header>
    <ul class="home-philosophy__pillars" aria-label="<?php esc_attr_e('How Brave Hearts stories guide young readers', 'brave-hearts'); ?>">
      <li>
        <span class="home-philosophy__marker" aria-hidden="true"><span></span></span>
        <span><?php esc_html_e('Adventure opens the door.', 'brave-hearts'); ?></span>
      </li>
      <li>
        <span class="home-philosophy__marker" aria-hidden="true"><span></span></span>
        <span><?php esc_html_e('Truth deepens the wonder.', 'brave-hearts'); ?></span>
      </li>
      <li>
        <span class="home-philosophy__marker" aria-hidden="true"><span></span></span>
        <span><?php esc_html_e('Character carries it home.', 'brave-hearts'); ?></span>
      </li>
    </ul>
    <p class="home-philosophy__closing"><?php esc_html_e('The last page is not the end.', 'brave-hearts'); ?><br><strong><?php esc_html_e('It is an invitation to look up.', 'brave-hearts'); ?></strong></p>
  </div>
</section>
<?php

// 3. Founder origin: a compact trust bridge from philosophy to the adventures.
?>
<section id="first-reader" class="homepage-section home-origin" aria-labelledby="first-reader-title">
  <div class="container">
    <div class="home-origin__card">
      <div class="home-origin__mark" aria-hidden="true">
        <span>C</span><span>H</span>
      </div>
      <div class="home-origin__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('The first reader', 'brave-hearts'); ?></p>
        <h2 id="first-reader-title"><?php esc_html_e('It Began With One Child and One Loyal Dog', 'brave-hearts'); ?></h2>
        <p><?php esc_html_e('Charlotte is real. Henry is real. These stories began as a gift for one child—and became an invitation for many.', 'brave-hearts'); ?></p>
        <a class="home-origin__link" href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('Meet the story behind Brave Hearts', 'brave-hearts'); ?> <span aria-hidden="true">→</span></a>
      </div>
    </div>
  </div>
</section>
<?php

// Prepare the main book collection for the books step later in the page journey.
$featured_books_args = [
    'id'       => 'featured-books',
    'eyebrow'  => bhp_get_homepage_field('books_eyebrow', __('The Adventures of Charlotte & Henry', 'brave-hearts')),
    'title'    => bhp_get_homepage_field('books_title', __('Choose the Story That Takes You Somewhere Real', 'brave-hearts')),
    'intro'    => bhp_get_homepage_field('books_intro', __('Travel with Charlotte and Henry through real places, true science, and brave choices in stories created for children growing into independent readers.', 'brave-hearts')),
    'books'    => $featured_books,
    'cta_link' => [
        'url'   => bhp_get_homepage_field('books_cta_url', home_url('/books/')),
        'label' => bhp_get_homepage_field('books_cta_label', __('Discover Every Adventure', 'brave-hearts')),
    ],
];

// 4. Explore the World: destination gateways remain filterable as the series grows.
$mariana_book = $find_home_book('Mariana Trench');
$everest_book = $find_home_book('Mount Everest');
$amazon_book = $find_home_book('Amazon Rainforest');

$adventure_cards = apply_filters('bhp_homepage_adventure_cards', [
    [
        'eyebrow'   => __('Western Pacific · Nearly 11,000 m deep', 'brave-hearts'),
        'title'     => __('Mariana Trench', 'brave-hearts'),
        'text'      => __('<p>Descend seven miles beneath the Pacific and discover deep-sea creatures, ocean science, conservation, and the courage to keep going.</p><p class="hub-card__question"><span class="hub-card__question-label">Wonder aloud</span>What glows where sunlight has never reached?</p>', 'brave-hearts'),
        'url'       => !empty($mariana_book['url']) ? $mariana_book['url'] : home_url('/books/'),
        'cta_label' => __('Explore the book', 'brave-hearts'),
        'image_id'  => $mariana_book['image_id'] ?? 0,
        'class'     => 'hub-card--destination',
    ],
    [
        'eyebrow'   => __('The Himalayas · 8,849 m high', 'brave-hearts'),
        'title'     => __('Mount Everest', 'brave-hearts'),
        'text'      => __('<p>Climb into the Himalayas for a story of mountain geography, resilience, teamwork, and brave hearts working together.</p><p class="hub-card__question"><span class="hub-card__question-label">Wonder aloud</span>How do climbers breathe where the air is thin?</p>', 'brave-hearts'),
        'url'       => !empty($everest_book['url']) ? $everest_book['url'] : home_url('/books/'),
        'cta_label' => __('Explore the book', 'brave-hearts'),
        'image_id'  => $everest_book['image_id'] ?? 0,
        'class'     => 'hub-card--destination',
    ],
    [
        'eyebrow'   => __('Equatorial South America · Largest tropical rainforest', 'brave-hearts'),
        'title'     => __('Amazon Rainforest', 'brave-hearts'),
        'text'      => __('<p>Journey into a living world of remarkable animals, interconnected ecosystems, conservation, and discovery.</p><p class="hub-card__question"><span class="hub-card__question-label">Wonder aloud</span>What can a rainforest teach us when we slow down and listen?</p>', 'brave-hearts'),
        'url'       => !empty($amazon_book['url']) ? $amazon_book['url'] : home_url('/books/'),
        'cta_label' => __('Explore the book', 'brave-hearts'),
        'image_id'  => $amazon_book['image_id'] ?? 0,
        'class'     => 'hub-card--destination',
    ],
], $page_id);
?>
<section id="explore-world" class="homepage-section section" aria-labelledby="explore-world-title">
  <div class="container">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('explore_eyebrow', __('From the deepest ocean to the highest mountain', 'brave-hearts'))); ?></p>
      <h2 id="explore-world-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('explore_title', __('Explore Charlotte & Henry’s Adventures', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('explore_intro', __('Each adventure begins with a real destination and opens into science, geography, wildlife, and character-building discovery.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--adventures">
      <?php foreach ($adventure_cards as $card): ?>
        <?php get_template_part('template-parts/components/hub-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
    <div class="component-section-action">
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Choose Your Next Adventure', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php
// 5. Books: offer the stories after visitors understand their purpose and destinations.
get_template_part('template-parts/components/featured-books', null, $featured_books_args);

// 6. Learning Hub: educational depth extends curiosity beyond the books.
$learning_cards = apply_filters('bhp_homepage_learning_cards', [
    ['title' => __('Animals', 'brave-hearts'), 'text' => __('Meet the wildlife behind the adventures.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('animals'), 'label' => __('Explore animals', 'brave-hearts')]],
    ['title' => __('Science', 'brave-hearts'), 'text' => __('Understand the forces shaping our world.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('science'), 'label' => __('Explore science', 'brave-hearts')]],
    ['title' => __('Geography', 'brave-hearts'), 'text' => __('Find the real places behind every journey.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('geography'), 'label' => __('Explore geography', 'brave-hearts')]],
    ['title' => __('Conservation', 'brave-hearts'), 'text' => __('Learn how curiosity can become care.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('conservation'), 'label' => __('Explore conservation', 'brave-hearts')]],
    ['title' => __('Explorers', 'brave-hearts'), 'text' => __('Meet courageous thinkers past and present.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('explorers'), 'label' => __('Meet explorers', 'brave-hearts')]],
    ['title' => __('Activities', 'brave-hearts'), 'text' => __('Keep learning with hands-on discoveries.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('activities'), 'label' => __('Try an activity', 'brave-hearts')]],
], $page_id);
foreach ($learning_cards as &$learning_card) {
    $topic_slug = sanitize_title($learning_card['title'] ?? '');
    $fallback_url = bhp_get_learning_category_url($topic_slug);
    $learning_link = is_array($learning_card['link'] ?? null) ? $learning_card['link'] : [];
    $learning_link['url'] = bhp_get_safe_link_url($learning_link['url'] ?? '', $fallback_url);
    $learning_card['link'] = $learning_link;
}
unset($learning_card);
?>
<section id="learning-hub" class="homepage-section learning-hub--ecosystem section section--muted" aria-labelledby="learning-hub-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('learning_eyebrow', __('The story is only the beginning', 'brave-hearts'))); ?></p>
      <h2 id="learning-hub-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('learning_title', __('Follow Curiosity Into the Real World', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('learning_intro', __('Every question a story awakens can become a new trail to follow—through wildlife, geography, science, conservation, explorers, and hands-on discovery.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--learning">
      <?php foreach ($learning_cards as $card): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
    <div class="component-section-action">
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Explore the Learning Hub', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php
// 7. Teacher Resources.
$teacher_image_id = (int) bhp_get_homepage_field('teachers_image_id', 0);
get_template_part('template-parts/components/teacher-resources-cta', null, [
    'id'        => 'teacher-resources',
    'eyebrow'   => bhp_get_homepage_field('teachers_eyebrow', __('Classroom-ready and no-prep', 'brave-hearts')),
    'title'     => bhp_get_homepage_field('teachers_title', __('Teach Reading, STEM, and Courage Through Story', 'brave-hearts')),
    'text'      => bhp_get_homepage_field('teachers_text', __('Turn Charlotte & Henry into a complete classroom experience with practical resources for Grades 1–3, whole-class read alouds, small groups, literacy centers, and homeschool learning.', 'brave-hearts')),
    'items'     => apply_filters('bhp_homepage_teacher_resource_items', [
        __('Lesson Plans', 'brave-hearts'),
        __('Vocabulary', 'brave-hearts'),
        __('Printables', 'brave-hearts'),
        __('Discussion Guides', 'brave-hearts'),
        __('Read Aloud Resources', 'brave-hearts'),
    ], $page_id),
    'link'      => [
        'url'   => bhp_get_homepage_field('teachers_url', home_url('/teachers/')),
        'label' => bhp_get_homepage_field('teachers_label', __('Explore Teacher Resources', 'brave-hearts')),
    ],
    'image_id'  => $teacher_image_id,
    'image_alt' => bhp_get_homepage_field('teachers_image_alt', ''),
    'class'     => $teacher_image_id ? '' : 'teacher-resources-cta--text-only',
]);

// 8. Testimonials: verified reader excerpts; editable through front-page fields.
$testimonials = apply_filters('bhp_homepage_testimonials', array_values(array_filter([
    [
        'quote' => bhp_get_homepage_field('testimonial_1_quote', __('What a great read, my heart was warm with adventure. I can’t wait for this series to continue.', 'brave-hearts')),
        'name'  => bhp_get_homepage_field('testimonial_1_name', 'Jonathan Hansen'),
        'role'  => bhp_get_homepage_field('testimonial_1_role', __('Verified Purchase', 'brave-hearts')),
        'source'=> bhp_get_homepage_field('testimonial_1_source', ''),
    ],
    [
        'quote' => bhp_get_homepage_field('testimonial_2_quote', __('A really sweet adventure story for young readers. The ocean facts add a fun learning element while keeping the story engaging.', 'brave-hearts')),
        'name'  => bhp_get_homepage_field('testimonial_2_name', 'cgking90'),
        'role'  => bhp_get_homepage_field('testimonial_2_role', __('Verified Purchase', 'brave-hearts')),
        'source'=> bhp_get_homepage_field('testimonial_2_source', ''),
    ],
    [
        'quote' => bhp_get_homepage_field('testimonial_3_quote', __('A wonderful book to share with my granddaughter. The pictures are bright, cheery, and inquisitive, and I would highly recommend it.', 'brave-hearts')),
        'name'  => bhp_get_homepage_field('testimonial_3_name', 'Debra Savage'),
        'role'  => bhp_get_homepage_field('testimonial_3_role', __('Verified Purchase', 'brave-hearts')),
        'source'=> bhp_get_homepage_field('testimonial_3_source', ''),
    ],
], static function ($testimonial) {
    return !empty($testimonial['quote']);
})), $page_id);

if ($testimonials): ?>
<section id="testimonials" class="homepage-section section" aria-labelledby="testimonials-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('testimonials_eyebrow', __('Trusted by families and educators', 'brave-hearts'))); ?></p>
      <h2 id="testimonials-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('testimonials_title', __('What Readers Are Saying', 'brave-hearts'))); ?></h2>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--testimonials">
      <?php foreach ($testimonials as $testimonial): ?>
        <?php get_template_part('template-parts/components/testimonial-card', null, $testimonial); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif;

// 9. Adventure Club Newsletter.
get_template_part('template-parts/components/newsletter-signup', null, [
    'id'                => 'adventure-club',
    'eyebrow'           => bhp_get_homepage_field('newsletter_eyebrow', __('The Adventure Club', 'brave-hearts')),
    'title'             => bhp_get_homepage_field('newsletter_title', __('Join the Adventure Club', 'brave-hearts')),
    'text'              => bhp_get_homepage_field('newsletter_text', __('Receive new adventures, printable activities, teacher resources and early access to every new book.', 'brave-hearts')),
    'form_action'       => bhp_get_homepage_field('newsletter_form_action', ''),
    'email_name'        => bhp_get_homepage_field('newsletter_email_name', 'email'),
    'email_label'       => bhp_get_homepage_field('newsletter_email_label', __('Email address', 'brave-hearts')),
    'email_placeholder' => bhp_get_homepage_field('newsletter_placeholder', __('you@example.com', 'brave-hearts')),
    'submit_label'      => bhp_get_homepage_field('newsletter_submit_label', __('Join the Adventure Club', 'brave-hearts')),
    'privacy_text'      => bhp_get_homepage_field('newsletter_privacy', __('Useful adventures only. Unsubscribe anytime.', 'brave-hearts')),
    'audience_type'    => 'parents_families',
    'lead_magnet'      => 'explorer_passport',
    'source_page'      => get_permalink($page_id),
    'hidden_fields'     => apply_filters('bhp_homepage_newsletter_hidden_fields', [], $page_id),
]);

// 10. Footer.
get_footer();
