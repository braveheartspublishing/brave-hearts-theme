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

$read_aloud_url = bhp_get_safe_link_url(
    $teacher_field('read_aloud_url', ''),
    add_query_arg('inquiry', 'read-aloud', home_url('/contact/'))
);
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
    $resource_url = bhp_get_safe_link_url($teacher_field($key . '_url', ''));
    $resource['url'] = $resource_url;
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
    'eyebrow'  => __('The Brave Hearts field guide library', 'brave-hearts'),
    'title'    => __('Explorer Expedition Guides', 'brave-hearts'),
    'text'     => __('Explore real-world articles, reading guidance, destination field notes, educator resources, and family paths—all connected to the questions behind the books.', 'brave-hearts'),
    'image_id' => $hero_image_id,
    'primary_link' => [
        'url'   => '#explore-topics',
        'label' => __('Explore the Guides', 'brave-hearts'),
    ],
    'secondary_link' => [
        'url'   => '#educator-resources',
        'label' => __('For Educators', 'brave-hearts'),
    ],
]);
?>
<section id="explore-topics" class="guides-hub-section section" aria-labelledby="explore-topics-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Choose a trail', 'brave-hearts'); ?></p>
      <h2 id="explore-topics-title" class="text-section-title"><?php esc_html_e('Explore by Topic', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Begin with the question your reader, family, or classroom is already asking.', 'brave-hearts'); ?></p>
    </header>
    <div class="guide-topic-grid">
      <?php foreach (['reading-growing','science-geography','educator-resources','book-brand-stories'] as $hub_key): $hub_posts = bhp_get_guide_posts($hub_key); ?>
        <a class="guide-topic-card" href="#<?php echo esc_attr($hub_key); ?>">
          <span class="guide-topic-card__count"><?php echo esc_html(sprintf(_n('%d field note', '%d field notes', count($hub_posts), 'brave-hearts'), count($hub_posts))); ?></span>
          <h3><?php echo esc_html(bhp_get_guide_hubs()[$hub_key]); ?></h3>
          <span><?php esc_html_e('Open this guide', 'brave-hearts'); ?> →</span>
        </a>
      <?php endforeach; ?>
      <a class="guide-topic-card" href="#family-resources">
        <span class="guide-topic-card__count"><?php esc_html_e('Family path', 'brave-hearts'); ?></span>
        <h3><?php esc_html_e('For Families', 'brave-hearts'); ?></h3>
        <span><?php esc_html_e('Explore together at home', 'brave-hearts'); ?> →</span>
      </a>
    </div>
    <div class="guide-search"><?php get_search_form(); ?></div>
  </div>
</section>

<section class="guides-hub-section guides-hub-section--destinations section section--dark" aria-labelledby="guide-destinations-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Real places behind the stories', 'brave-hearts'); ?></p>
      <h2 id="guide-destinations-title" class="text-section-title"><?php esc_html_e('Explore by Destination', 'brave-hearts'); ?></h2>
    </header>
    <div class="guide-destination-grid">
      <?php foreach (['mariana-trench','mount-everest'] as $destination_key): $destination_posts = bhp_get_guide_posts($destination_key); ?>
        <a class="guide-destination-card guide-destination-card--<?php echo esc_attr($destination_key); ?>" href="#<?php echo esc_attr($destination_key); ?>">
          <p><?php echo esc_html(sprintf(_n('%d connected field note', '%d connected field notes', count($destination_posts), 'brave-hearts'), count($destination_posts))); ?></p>
          <h3><?php echo esc_html(bhp_get_guide_hubs()[$destination_key]); ?></h3>
          <span><?php esc_html_e('Enter the destination guide', 'brave-hearts'); ?> →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php foreach (['reading-growing','science-geography','book-brand-stories','mariana-trench','mount-everest','family-resources'] as $hub_key): $hub_posts = bhp_get_guide_posts($hub_key); if (!$hub_posts) { continue; } ?>
<section id="<?php echo esc_attr($hub_key); ?>" class="guides-hub-section guide-collection section<?php echo $hub_key === 'science-geography' ? ' section--muted' : ''; ?>" aria-labelledby="<?php echo esc_attr($hub_key); ?>-title">
  <div class="container">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php esc_html_e('Curated expedition guide', 'brave-hearts'); ?></p>
      <h2 id="<?php echo esc_attr($hub_key); ?>-title" class="text-section-title"><?php echo esc_html(bhp_get_guide_hubs()[$hub_key]); ?></h2>
    </header>
    <div class="guide-article-grid">
      <?php foreach ($hub_posts as $guide_post) { get_template_part('template-parts/guides/article-card', null, ['post' => $guide_post]); } ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<section id="educator-resources" class="teachers-section section" aria-labelledby="teacher-resource-categories-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Classroom-ready support', 'brave-hearts'); ?></p>
      <h2 id="teacher-resource-categories-title" class="text-section-title"><?php esc_html_e('Resources That Continue the Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Use Charlotte & Henry for read-alouds, discussion, writing prompts, vocabulary, science, geography, and curiosity-driven learning.', 'brave-hearts'); ?></p>
    </header>
    <?php $educator_posts = bhp_get_guide_posts('educator-resources'); ?>
    <?php if ($educator_posts): ?>
      <div class="guide-article-grid guide-article-grid--educators">
        <?php foreach ($educator_posts as $guide_post) { get_template_part('template-parts/guides/article-card', null, ['post' => $guide_post]); } ?>
      </div>
    <?php endif; ?>
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
            'resources_url' => bhp_get_safe_link_url($teacher_field($key . '_resources_url', '')),
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
