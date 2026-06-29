<?php
/**
 * Template Name: Brave Hearts Teacher Resources Page
 * Description: Classroom resources, read-aloud outreach, and teacher signup.
 */
defined('ABSPATH') || exit;
get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();
$teacher_field = static function ($key, $fallback = '') use ($page_id) {
    $field_name = 'bhp_teachers_' . sanitize_key($key);
    $stored = $page_id ? get_post_meta($page_id, $field_name, true) : '';
    $value = ($stored !== '') ? $stored : $fallback;
    return apply_filters('bhp_teachers_field_' . sanitize_key($key), $value, $page_id);
};

$read_aloud_url = $teacher_field('read_aloud_url', add_query_arg('inquiry', 'read-aloud', home_url('/contact/')));
$hero_image_id = (int) $teacher_field('hero_image_id', 0);
$adventures = bhp_get_series_adventures();

$resource_categories = apply_filters('bhp_teacher_resource_categories', [
    'lesson_plans' => [
        'title' => __('Lesson Plans', 'brave-hearts'),
        'text' => __('Flexible lesson structures that connect each story to reading, writing, science, geography, and reflection.', 'brave-hearts'),
    ],
    'discussion_guides' => [
        'title' => __('Discussion Guides', 'brave-hearts'),
        'text' => __('Questions that help students discuss courage, curiosity, kindness, teamwork, and the real world behind the story.', 'brave-hearts'),
    ],
    'vocabulary' => [
        'title' => __('Vocabulary', 'brave-hearts'),
        'text' => __('Kid-friendly vocabulary support drawn from each destination, scientific idea, and chapter adventure.', 'brave-hearts'),
    ],
    'maps' => [
        'title' => __('Maps', 'brave-hearts'),
        'text' => __('Printable geography connections that help readers locate every extraordinary destination.', 'brave-hearts'),
    ],
    'printables' => [
        'title' => __('Printables', 'brave-hearts'),
        'text' => __('Low-prep activities that extend reading into observation, writing, science, and creative exploration.', 'brave-hearts'),
    ],
    'read_aloud_resources' => [
        'title' => __('Read-Aloud Resources', 'brave-hearts'),
        'text' => __('Prompts, stopping points, and discussion support for classroom, library, homeschool, and family read-alouds.', 'brave-hearts'),
    ],
], $page_id);

foreach ($resource_categories as $key => &$resource) {
    $resource_url = $teacher_field($key . '_url', '');
    $resource['url'] = $resource_url;
    $resource['status'] = $resource_url ? 'available' : 'placeholder';
}
unset($resource);

$book_resources = [
    'mariana_trench' => [
        'themes' => [__('Ocean science', 'brave-hearts'), __('Conservation', 'brave-hearts'), __('Courage', 'brave-hearts'), __('Kindness', 'brave-hearts')],
        'subjects' => [__('Science', 'brave-hearts'), __('Geography', 'brave-hearts'), __('ELA', 'brave-hearts'), __('SEL', 'brave-hearts')],
    ],
    'mount_everest' => [
        'themes' => [__('Mountain geography', 'brave-hearts'), __('Resilience', 'brave-hearts'), __('Teamwork', 'brave-hearts'), __('Courage', 'brave-hearts')],
        'subjects' => [__('Geography', 'brave-hearts'), __('Science', 'brave-hearts'), __('ELA', 'brave-hearts'), __('SEL', 'brave-hearts')],
    ],
    'amazon_rainforest' => [
        'themes' => [__('Ecosystems', 'brave-hearts'), __('Biodiversity', 'brave-hearts'), __('Conservation', 'brave-hearts'), __('Curiosity', 'brave-hearts')],
        'subjects' => [__('Science', 'brave-hearts'), __('Geography', 'brave-hearts'), __('ELA', 'brave-hearts'), __('Environmental learning', 'brave-hearts')],
    ],
];

get_template_part('template-parts/components/hero', null, [
    'id'       => 'teachers-hero',
    'class'    => 'teachers-hero',
    'eyebrow'  => __('For classrooms, libraries, homeschoolers, and families', 'brave-hearts'),
    'title'    => __('Bring the Adventure Into Your Classroom', 'brave-hearts'),
    'text'     => __('Real-world adventure books and classroom resources for curious readers ages 6–9.', 'brave-hearts'),
    'image_id' => $hero_image_id,
    'primary_link' => [
        'url'   => home_url('/books/'),
        'label' => __('Explore the Books', 'brave-hearts'),
    ],
    'secondary_link' => [
        'url'   => $read_aloud_url,
        'label' => __('Request a Read-Aloud', 'brave-hearts'),
    ],
]);
?>
<section id="teacher-resource-categories" class="teachers-section section" aria-labelledby="teacher-resource-categories-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Classroom-ready support', 'brave-hearts'); ?></p>
      <h2 id="teacher-resource-categories-title" class="text-section-title"><?php esc_html_e('Resources That Continue the Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Use Charlotte & Henry for read-alouds, discussion, writing prompts, vocabulary, science, geography, and curiosity-driven learning.', 'brave-hearts'); ?></p>
    </header>
    <div class="teacher-resource-grid">
      <?php foreach ($resource_categories as $resource): ?>
        <?php get_template_part('template-parts/teachers/resource-card', null, $resource); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="book-based-resources" class="teachers-section section section--muted" aria-labelledby="book-based-resources-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Teach through story', 'brave-hearts'); ?></p>
      <h2 id="book-based-resources-title" class="text-section-title"><?php esc_html_e('Resources for Every Charlotte & Henry Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Each destination creates a natural path into real science, geography, vocabulary, conservation, discussion, and writing.', 'brave-hearts'); ?></p>
    </header>
    <div class="teacher-book-grid">
      <?php foreach ($adventures as $key => $adventure): ?>
        <?php
        $card = array_merge($adventure, $book_resources[$key], [
            'resources_url' => $teacher_field($key . '_resources_url', ''),
        ]);
        get_template_part('template-parts/teachers/book-resource-card', null, $card);
        ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="read-aloud-visits" class="teachers-section teacher-read-aloud section" aria-labelledby="read-aloud-visits-title">
  <div class="container teacher-read-aloud__layout">
    <div class="teacher-read-aloud__content">
      <p class="component-heading__eyebrow"><?php esc_html_e('School-year outreach', 'brave-hearts'); ?></p>
      <h2 id="read-aloud-visits-title" class="text-section-title"><?php esc_html_e('Read-Alouds and Author Visits', 'brave-hearts'); ?></h2>
      <p class="text-lead"><?php esc_html_e('Brave Hearts Publishing supports classroom read-alouds and author visits where scheduling and location allow.', 'brave-hearts'); ?></p>
      <p><?php esc_html_e('Visits can connect a Charlotte & Henry story to real geography, science, vocabulary, courage, and questions students are excited to explore together.', 'brave-hearts'); ?></p>
    </div>
    <div class="teacher-read-aloud__action">
      <a class="btn btn-primary" href="<?php echo esc_url($read_aloud_url); ?>"><?php esc_html_e('Request a Read-Aloud', 'brave-hearts'); ?></a>
      <p><?php esc_html_e('Availability depends on location, timing, audience, and school-year schedule.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<div id="teacher-email-signup" class="container teacher-signup-wrap section section--muted">
  <?php get_template_part('template-parts/acquisition/teacher-resource-signup', null, [
      'id'           => 'teacher-resources-signup',
      'lead_magnet'  => 'teacher_resources',
      'source_page'  => get_permalink($page_id),
      'title'        => __('Bring the adventure into your classroom', 'brave-hearts'),
      'text'         => __('Join the teacher list for resource updates, read-aloud ideas, classroom printables, and new-book news.', 'brave-hearts'),
      'submit_label' => __('Get Teacher Resource Updates', 'brave-hearts'),
  ]); ?>
</div>

<section id="teachers-final-cta" class="teachers-final-cta final-cta section" aria-labelledby="teachers-final-cta-title">
  <div class="container container--content final-cta__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Books first. Learning always.', 'brave-hearts'); ?></p>
    <h2 id="teachers-final-cta-title" class="text-section-title"><?php esc_html_e('Start a Classroom Adventure', 'brave-hearts'); ?></h2>
    <p class="text-lead final-cta__text"><?php esc_html_e('Choose a book, invite a read-aloud, or join the Adventure Club for future resources and new adventures.', 'brave-hearts'); ?></p>
    <div class="final-cta__actions cluster">
      <a class="btn btn-primary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Shop the Books', 'brave-hearts'); ?></a>
      <a class="btn btn-secondary" href="<?php echo esc_url($read_aloud_url); ?>"><?php esc_html_e('Request a Read-Aloud', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/#adventure-club')); ?>"><?php esc_html_e('Join the Adventure Club', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
