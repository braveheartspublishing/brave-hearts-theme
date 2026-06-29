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
    'eyebrow'        => bhp_get_homepage_field('hero_eyebrow', __('Books first. Adventure always.', 'brave-hearts')),
    'title'          => bhp_get_homepage_field('hero_title', __('Big Places. Brave Hearts.', 'brave-hearts')),
    'text'           => bhp_get_homepage_field('hero_text', __('Beautiful stories rooted in real places, real science, and the courage to explore.', 'brave-hearts')),
    'image_id'       => (int) bhp_get_homepage_field('hero_image_id', 0),
    'primary_link'   => [
        'url'   => bhp_get_homepage_field('hero_primary_url', home_url('/books/')),
        'label' => bhp_get_homepage_field('hero_primary_label', __('Shop the Books', 'brave-hearts')),
    ],
    'secondary_link' => [
        'url'   => bhp_get_homepage_field('hero_secondary_url', '#explore-world'),
        'label' => bhp_get_homepage_field('hero_secondary_label', __('Start the Adventure', 'brave-hearts')),
    ],
]);

// 2. Featured Books: populated from featured products or the future Book post type.
$featured_books = bhp_get_homepage_books((int) apply_filters('bhp_homepage_featured_book_count', 3));
get_template_part('template-parts/components/featured-books', null, [
    'id'       => 'featured-books',
    'eyebrow'  => bhp_get_homepage_field('books_eyebrow', __('The adventure begins here', 'brave-hearts')),
    'title'    => bhp_get_homepage_field('books_title', __('Start With a Story. Discover a World.', 'brave-hearts')),
    'intro'    => bhp_get_homepage_field('books_intro', __('Each Brave Hearts book opens a door to real places, meaningful learning, and conversations that continue after the final page.', 'brave-hearts')),
    'books'    => $featured_books,
    'cta_link' => [
        'url'   => bhp_get_homepage_field('books_cta_url', home_url('/books/')),
        'label' => bhp_get_homepage_field('books_cta_label', __('Explore All Books', 'brave-hearts')),
    ],
]);

// 3. Why Brave Hearts.
$why_cards = apply_filters('bhp_homepage_why_cards', [
    [
        'title' => __('Stories Worth Returning To', 'brave-hearts'),
        'text'  => __('Warm, engaging adventures designed to help children fall in love with reading.', 'brave-hearts'),
        'link'  => ['url' => home_url('/books/'), 'label' => __('Discover the stories', 'brave-hearts')],
        'class' => 'feature-card--reading',
    ],
    [
        'title' => __('The Real World Awaits', 'brave-hearts'),
        'text'  => __('Every journey begins with authentic places, wildlife, exploration, and wonder.', 'brave-hearts'),
        'link'  => ['url' => '#explore-world', 'label' => __('Explore the world', 'brave-hearts')],
        'class' => 'feature-card--adventure',
    ],
    [
        'title' => __('Learning Woven Into Every Journey', 'brave-hearts'),
        'text'  => __('Geography, science, conservation, and character grow naturally from the story.', 'brave-hearts'),
        'link'  => ['url' => '#learning-hub', 'label' => __('Continue learning', 'brave-hearts')],
        'class' => 'feature-card--education',
    ],
], $page_id);
?>
<section id="why-brave-hearts" class="homepage-section section section--muted" aria-labelledby="why-brave-hearts-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('why_eyebrow', __('Reading. Adventure. Education.', 'brave-hearts'))); ?></p>
      <h2 id="why-brave-hearts-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('why_title', __('Why Families Choose Brave Hearts', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('why_intro', __('Books that inspire curiosity, teach something real, and help courage grow.', 'brave-hearts'))); ?></p>
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
$adventure_cards = apply_filters('bhp_homepage_adventure_cards', [
    [
        'eyebrow'   => __('Adventure destination', 'brave-hearts'),
        'title'     => __('Oceans and Deep Places', 'brave-hearts'),
        'text'      => __('Meet the remarkable places and discoveries that inspire Brave Hearts stories.', 'brave-hearts'),
        'url'       => home_url('/books/'),
        'cta_label' => __('Find the book', 'brave-hearts'),
        'image_id'  => (int) bhp_get_homepage_field('adventure_ocean_image_id', 0),
    ],
    [
        'eyebrow'   => __('Adventure destination', 'brave-hearts'),
        'title'     => __('Mountains and Wild Landscapes', 'brave-hearts'),
        'text'      => __('Follow young explorers into big landscapes shaped by courage and curiosity.', 'brave-hearts'),
        'url'       => home_url('/books/'),
        'cta_label' => __('Find the book', 'brave-hearts'),
        'image_id'  => (int) bhp_get_homepage_field('adventure_mountain_image_id', 0),
    ],
    [
        'eyebrow'   => __('Adventure destination', 'brave-hearts'),
        'title'     => __('Wildlife and Conservation', 'brave-hearts'),
        'text'      => __('Discover the animals, habitats, and real-world ideas behind every adventure.', 'brave-hearts'),
        'url'       => home_url('/books/'),
        'cta_label' => __('Find the book', 'brave-hearts'),
        'image_id'  => (int) bhp_get_homepage_field('adventure_wildlife_image_id', 0),
    ],
], $page_id);
?>
<section id="explore-world" class="homepage-section section" aria-labelledby="explore-world-title">
  <div class="container">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('explore_eyebrow', __('Every book begins somewhere real', 'brave-hearts'))); ?></p>
      <h2 id="explore-world-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('explore_title', __('Explore the World Behind the Stories', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('explore_intro', __('Travel beyond the page and discover the places, wildlife, and science that make each story possible.', 'brave-hearts'))); ?></p>
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
    ['title' => __('Animals', 'brave-hearts'), 'text' => __('Meet the wildlife behind the adventures.', 'brave-hearts'), 'link' => ['url' => home_url('/learning-hub/animals/'), 'label' => __('Explore animals', 'brave-hearts')]],
    ['title' => __('Science', 'brave-hearts'), 'text' => __('Understand the forces shaping our world.', 'brave-hearts'), 'link' => ['url' => home_url('/learning-hub/science/'), 'label' => __('Explore science', 'brave-hearts')]],
    ['title' => __('Geography', 'brave-hearts'), 'text' => __('Find the real places behind every journey.', 'brave-hearts'), 'link' => ['url' => home_url('/learning-hub/geography/'), 'label' => __('Explore geography', 'brave-hearts')]],
    ['title' => __('Conservation', 'brave-hearts'), 'text' => __('Learn how curiosity can become care.', 'brave-hearts'), 'link' => ['url' => home_url('/learning-hub/conservation/'), 'label' => __('Explore conservation', 'brave-hearts')]],
    ['title' => __('Explorers', 'brave-hearts'), 'text' => __('Meet courageous thinkers past and present.', 'brave-hearts'), 'link' => ['url' => home_url('/learning-hub/explorers/'), 'label' => __('Meet explorers', 'brave-hearts')]],
    ['title' => __('Activities', 'brave-hearts'), 'text' => __('Keep learning with hands-on discoveries.', 'brave-hearts'), 'link' => ['url' => home_url('/learning-hub/activities/'), 'label' => __('Try an activity', 'brave-hearts')]],
], $page_id);
?>
<section id="learning-hub" class="homepage-section section section--muted" aria-labelledby="learning-hub-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('learning_eyebrow', __('Education always', 'brave-hearts'))); ?></p>
      <h2 id="learning-hub-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('learning_title', __('Keep the Adventure Going', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('learning_intro', __('Explore the real-world ideas introduced by the books and give curious readers a meaningful next step.', 'brave-hearts'))); ?></p>
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
    'eyebrow'   => bhp_get_homepage_field('teachers_eyebrow', __('For classrooms and curious minds', 'brave-hearts')),
    'title'     => bhp_get_homepage_field('teachers_title', __('Bring the Adventure Into Your Classroom', 'brave-hearts')),
    'text'      => bhp_get_homepage_field('teachers_text', __('Extend each book with practical resources created to support discussion, discovery, and confident learning.', 'brave-hearts')),
    'items'     => apply_filters('bhp_homepage_teacher_resource_items', [
        __('Lesson plans and discussion guides', 'brave-hearts'),
        __('Printables and classroom activities', 'brave-hearts'),
        __('Geography, science, and vocabulary connections', 'brave-hearts'),
    ], $page_id),
    'link'      => [
        'url'   => bhp_get_homepage_field('teachers_url', home_url('/teachers-guide/')),
        'label' => bhp_get_homepage_field('teachers_label', __('Explore Teacher Resources', 'brave-hearts')),
    ],
    'image_id'  => $teacher_image_id,
    'image_alt' => bhp_get_homepage_field('teachers_image_alt', ''),
    'class'     => $teacher_image_id ? '' : 'teacher-resources-cta--text-only',
]);

// 7. Testimonials: intentionally empty until verified quotations are supplied.
$testimonials = apply_filters('bhp_homepage_testimonials', array_values(array_filter([
    [
        'quote' => bhp_get_homepage_field('testimonial_1_quote', ''),
        'name'  => bhp_get_homepage_field('testimonial_1_name', ''),
        'role'  => bhp_get_homepage_field('testimonial_1_role', ''),
        'source'=> bhp_get_homepage_field('testimonial_1_source', ''),
    ],
    [
        'quote' => bhp_get_homepage_field('testimonial_2_quote', ''),
        'name'  => bhp_get_homepage_field('testimonial_2_name', ''),
        'role'  => bhp_get_homepage_field('testimonial_2_role', ''),
        'source'=> bhp_get_homepage_field('testimonial_2_source', ''),
    ],
    [
        'quote' => bhp_get_homepage_field('testimonial_3_quote', ''),
        'name'  => bhp_get_homepage_field('testimonial_3_name', ''),
        'role'  => bhp_get_homepage_field('testimonial_3_role', ''),
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
    'title'             => bhp_get_homepage_field('newsletter_title', __('Raise a Reader Who Wants to Know More', 'brave-hearts')),
    'text'              => bhp_get_homepage_field('newsletter_text', __('Get printable adventures, new-book news, and thoughtful classroom resources delivered occasionally.', 'brave-hearts')),
    'form_action'       => bhp_get_homepage_field('newsletter_form_action', ''),
    'email_name'        => bhp_get_homepage_field('newsletter_email_name', 'email'),
    'email_label'       => bhp_get_homepage_field('newsletter_email_label', __('Email address', 'brave-hearts')),
    'email_placeholder' => bhp_get_homepage_field('newsletter_placeholder', __('you@example.com', 'brave-hearts')),
    'submit_label'      => bhp_get_homepage_field('newsletter_submit_label', __('Join the Adventure Club', 'brave-hearts')),
    'privacy_text'      => bhp_get_homepage_field('newsletter_privacy', __('Useful ideas only. Unsubscribe anytime.', 'brave-hearts')),
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
