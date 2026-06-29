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

// 1. Hero: the primary path always leads to books.
get_template_part('template-parts/components/hero', null, [
    'id'             => 'home-hero',
    'eyebrow'        => bhp_get_homepage_field('hero_eyebrow', __('Bridge books for ages 6–9', 'brave-hearts')),
    'title'          => bhp_get_homepage_field('hero_title', __('Big Places. Brave Hearts.', 'brave-hearts')),
    'text'           => bhp_get_homepage_field('hero_text', __('Early chapter STEM adventures that help growing readers build confidence through real places, real science, and real courage.', 'brave-hearts')),
    'image_id'       => (int) bhp_get_homepage_field('hero_image_id', 0),
    'primary_link'   => [
        'url'   => bhp_get_homepage_field('hero_primary_url', home_url('/books/')),
        'label' => bhp_get_homepage_field('hero_primary_label', __('Shop the Books', 'brave-hearts')),
    ],
    'secondary_link' => [
        'url'   => bhp_get_homepage_field('hero_secondary_url', '#explore-world'),
        'label' => bhp_get_homepage_field('hero_secondary_label', __('Explore Adventures', 'brave-hearts')),
    ],
]);

// 2. Featured Books: populated from featured products or the future Book post type.
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
get_template_part('template-parts/components/featured-books', null, [
    'id'       => 'featured-books',
    'eyebrow'  => bhp_get_homepage_field('books_eyebrow', __('The Adventures of Charlotte & Henry', 'brave-hearts')),
    'title'    => bhp_get_homepage_field('books_title', __('Books Built for the Reading Gap', 'brave-hearts')),
    'intro'    => bhp_get_homepage_field('books_intro', __('Short chapters, supportive illustrations, real STEM learning, and adventures written for children moving from picture books to independent reading.', 'brave-hearts')),
    'books'    => $featured_books,
    'cta_link' => [
        'url'   => bhp_get_homepage_field('books_cta_url', home_url('/books/')),
        'label' => bhp_get_homepage_field('books_cta_label', __('Explore All Books', 'brave-hearts')),
    ],
]);

// 3. Why Brave Hearts.
$why_cards = apply_filters('bhp_homepage_why_cards', [
    [
        'title' => __('Real Places', 'brave-hearts'),
        'text'  => __('Every adventure begins in an extraordinary place children can find on a map.', 'brave-hearts'),
        'link'  => ['url' => '#explore-world', 'label' => __('Explore the destinations', 'brave-hearts')],
        'class' => 'feature-card--place',
    ],
    [
        'title' => __('Real Science', 'brave-hearts'),
        'text'  => __('STEM facts are woven into the story so learning feels like part of the adventure.', 'brave-hearts'),
        'link'  => ['url' => '#learning-hub', 'label' => __('Keep learning', 'brave-hearts')],
        'class' => 'feature-card--science',
    ],
    [
        'title' => __('Real Adventure', 'brave-hearts'),
        'text'  => __('Fast-moving journeys invite growing readers to turn one more page.', 'brave-hearts'),
        'link'  => ['url' => '#featured-books', 'label' => __('Choose an adventure', 'brave-hearts')],
        'class' => 'feature-card--adventure',
    ],
    [
        'title' => __('Real Courage', 'brave-hearts'),
        'text'  => __('Charlotte is not fearless. She is curious, and she chooses to keep going.', 'brave-hearts'),
        'link'  => ['url' => '#featured-books', 'label' => __('Meet Charlotte and Henry', 'brave-hearts')],
        'class' => 'feature-card--courage',
    ],
    [
        'title' => __('Built for Ages 6–9', 'brave-hearts'),
        'text'  => __('Short chapters and readable pacing bridge the space between picture books and longer chapter books.', 'brave-hearts'),
        'link'  => ['url' => home_url('/books/'), 'label' => __('Explore the series', 'brave-hearts')],
        'class' => 'feature-card--readers',
    ],
    [
        'title' => __('For Classrooms and Families', 'brave-hearts'),
        'text'  => __('Stories, discussion ideas, and learning resources work equally well at school and at home.', 'brave-hearts'),
        'link'  => ['url' => home_url('/teachers/'), 'label' => __('See teacher resources', 'brave-hearts')],
        'class' => 'feature-card--classrooms',
    ],
], $page_id);
?>
<section id="why-brave-hearts" class="homepage-section section section--muted" aria-labelledby="why-brave-hearts-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('why_eyebrow', __('What makes the series different', 'brave-hearts'))); ?></p>
      <h2 id="why-brave-hearts-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('why_title', __('Real Learning. Real Wonder.', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('why_intro', __('Charlotte & Henry meets growing readers exactly where they are—with stories that respect their intelligence and make the real world feel exciting.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--values">
      <?php foreach ($why_cards as $card): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
// 4. Explore the World: destination gateways remain filterable as the series grows.
$mariana_book = $find_home_book('Mariana Trench');
$everest_book = $find_home_book('Mount Everest');
$amazon_book = $find_home_book('Amazon Rainforest');

$adventure_cards = apply_filters('bhp_homepage_adventure_cards', [
    [
        'eyebrow'   => __('The deepest place on Earth', 'brave-hearts'),
        'title'     => __('Mariana Trench', 'brave-hearts'),
        'text'      => __('Descend seven miles beneath the Pacific and discover deep-sea creatures, ocean science, conservation, and the courage to keep going.', 'brave-hearts'),
        'url'       => $mariana_book['url'] ?? home_url('/books/'),
        'cta_label' => __('Explore the book', 'brave-hearts'),
        'image_id'  => $mariana_book['image_id'] ?? 0,
    ],
    [
        'eyebrow'   => __('The highest place on Earth', 'brave-hearts'),
        'title'     => __('Mount Everest', 'brave-hearts'),
        'text'      => __('Climb into the Himalayas for a story of mountain geography, resilience, teamwork, and brave hearts working together.', 'brave-hearts'),
        'url'       => $everest_book['url'] ?? home_url('/books/'),
        'cta_label' => __('Explore the book', 'brave-hearts'),
        'image_id'  => $everest_book['image_id'] ?? 0,
    ],
    [
        'eyebrow'   => __('The world’s largest rainforest', 'brave-hearts'),
        'title'     => __('Amazon Rainforest', 'brave-hearts'),
        'text'      => __('Journey into a living world of remarkable animals, interconnected ecosystems, conservation, and discovery.', 'brave-hearts'),
        'url'       => $amazon_book['url'] ?? home_url('/books/'),
        'cta_label' => __('Explore the book', 'brave-hearts'),
        'image_id'  => $amazon_book['image_id'] ?? 0,
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
// 5. Learning Hub: educational depth supports trust and leads back to the books.
$learning_cards = apply_filters('bhp_homepage_learning_cards', [
    ['title' => __('Animals', 'brave-hearts'), 'text' => __('Meet the wildlife behind the adventures.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('animals'), 'label' => __('Explore animals', 'brave-hearts')]],
    ['title' => __('Science', 'brave-hearts'), 'text' => __('Understand the forces shaping our world.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('science'), 'label' => __('Explore science', 'brave-hearts')]],
    ['title' => __('Geography', 'brave-hearts'), 'text' => __('Find the real places behind every journey.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('geography'), 'label' => __('Explore geography', 'brave-hearts')]],
    ['title' => __('Conservation', 'brave-hearts'), 'text' => __('Learn how curiosity can become care.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('conservation'), 'label' => __('Explore conservation', 'brave-hearts')]],
    ['title' => __('Explorers', 'brave-hearts'), 'text' => __('Meet courageous thinkers past and present.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('explorers'), 'label' => __('Meet explorers', 'brave-hearts')]],
    ['title' => __('Activities', 'brave-hearts'), 'text' => __('Keep learning with hands-on discoveries.', 'brave-hearts'), 'link' => ['url' => bhp_get_learning_category_url('activities'), 'label' => __('Try an activity', 'brave-hearts')]],
], $page_id);
?>
<section id="learning-hub" class="homepage-section section section--muted" aria-labelledby="learning-hub-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('learning_eyebrow', __('Education always', 'brave-hearts'))); ?></p>
      <h2 id="learning-hub-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('learning_title', __('The Learning Hub', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('learning_intro', __('Follow the questions sparked by Charlotte & Henry into animals, geography, science, conservation, explorers, and hands-on activities.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--learning">
      <?php foreach ($learning_cards as $card): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
    <div class="component-section-action">
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Read the Stories That Inspired It All', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php
// 6. Teacher Resources.
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

// 7. Testimonials: verified reader excerpts; editable through front-page fields.
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

// 8. Adventure Club Newsletter.
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

// 9. Final Call-to-Action.
get_template_part('template-parts/components/final-cta', null, [
    'id'       => 'home-final-cta',
    'eyebrow'  => bhp_get_homepage_field('final_eyebrow', __('Books first', 'brave-hearts')),
    'title'    => bhp_get_homepage_field('final_title', __('Ready for the Next Adventure?', 'brave-hearts')),
    'text'     => bhp_get_homepage_field('final_text', __('Choose a story, open the first page, and discover where curiosity can lead.', 'brave-hearts')),
    'primary_link' => [
        'url'   => bhp_get_homepage_field('final_primary_url', home_url('/books/')),
        'label' => bhp_get_homepage_field('final_primary_label', __('Explore the Books', 'brave-hearts')),
    ],
    'secondary_link' => [
        'url'   => bhp_get_homepage_field('final_secondary_url', home_url('/about/')),
        'label' => bhp_get_homepage_field('final_secondary_label', __('Our Mission', 'brave-hearts')),
    ],
]);

// 10. Footer.
get_footer();
