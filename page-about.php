<?php
/**
 * Template Name: Brave Hearts About Page
 * Description: Mission, founder, audience, and brand story for Brave Hearts Publishing.
 */
defined('ABSPATH') || exit;
get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();
$about_field = static function ($key, $fallback = '') use ($page_id) {
    $field_name = 'bhp_about_' . sanitize_key($key);
    $stored = $page_id ? get_post_meta($page_id, $field_name, true) : '';
    $value = ($stored !== '') ? $stored : $fallback;
    return apply_filters('bhp_about_field_' . sanitize_key($key), $value, $page_id);
};

$hero_image_id = (int) $about_field('hero_image_id', 0);
$founder_image_id = (int) $about_field('founder_image_id', get_post_thumbnail_id($page_id));

get_template_part('template-parts/components/hero', null, [
    'id'       => 'about-hero',
    'class'    => 'about-hero',
    'eyebrow'  => $about_field('hero_eyebrow', __('Brave Hearts Publishing', 'brave-hearts')),
    'title'    => $about_field('hero_title', __('Big Places. Brave Hearts.', 'brave-hearts')),
    'text'     => $about_field('hero_text', __('Adventure books for curious kids who want to discover the real world.', 'brave-hearts')),
    'image_id' => $hero_image_id,
    'primary_link' => [
        'url'   => $about_field('hero_primary_url', home_url('/books/')),
        'label' => $about_field('hero_primary_label', __('Shop the Books', 'brave-hearts')),
    ],
    'secondary_link' => [
        'url'   => $about_field('hero_secondary_url', home_url('/teachers/')),
        'label' => $about_field('hero_secondary_label', __('Teacher Resources', 'brave-hearts')),
    ],
]);
?>
<section id="about-mission" class="about-section about-mission section" aria-labelledby="about-mission-title">
  <div class="container about-mission__layout">
    <div class="about-mission__heading">
      <p class="component-heading__eyebrow"><?php esc_html_e('Our mission', 'brave-hearts'); ?></p>
      <h2 id="about-mission-title" class="text-section-title"><?php echo esc_html($about_field('mission_title', __('Build Curiosity Through Adventure', 'brave-hearts'))); ?></h2>
    </div>
    <div class="about-mission__content text-lead flow">
      <p><?php echo esc_html($about_field('mission_text_1', __('Brave Hearts Publishing is a children’s adventure education brand built to help young readers become more curious about the world than they were yesterday.', 'brave-hearts'))); ?></p>
      <p><?php echo esc_html($about_field('mission_text_2', __('Every book begins with a real place and grows through real science, courage, curiosity, conservation, and unforgettable storytelling.', 'brave-hearts'))); ?></p>
      <p class="about-mission__tagline"><?php echo esc_html($about_field('mission_tagline', __('Big Places. Brave Hearts.', 'brave-hearts'))); ?></p>
    </div>
  </div>
</section>

<?php
$book_values = apply_filters('bhp_about_book_values', [
    [
        'title' => __('Real Places', 'brave-hearts'),
        'text'  => __('From the Mariana Trench to Mount Everest, each story begins somewhere children can discover on a map.', 'brave-hearts'),
        'class' => 'feature-card--place',
    ],
    [
        'title' => __('Real Science', 'brave-hearts'),
        'text'  => __('Geography, wildlife, exploration, and conservation are woven naturally into each adventure.', 'brave-hearts'),
        'class' => 'feature-card--science',
    ],
    [
        'title' => __('Curiosity and Courage', 'brave-hearts'),
        'text'  => __('Charlotte asks questions, feels uncertain, and keeps going—showing children that courage can grow one choice at a time.', 'brave-hearts'),
        'class' => 'feature-card--courage',
    ],
    [
        'title' => __('Care for the Natural World', 'brave-hearts'),
        'text'  => __('The stories invite readers to notice, understand, and protect the remarkable world around them.', 'brave-hearts'),
        'class' => 'feature-card--conservation',
    ],
], $page_id);
?>
<section id="why-these-books" class="about-section section section--muted" aria-labelledby="why-these-books-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Why these books exist', 'brave-hearts'); ?></p>
      <h2 id="why-these-books-title" class="text-section-title"><?php echo esc_html($about_field('books_title', __('Stories That Make Children Want to Know More', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html($about_field('books_intro', __('Brave Hearts books are created to inspire children to read, ask better questions, explore real places, and build confidence through meaningful adventure.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--4 about-values-grid">
      <?php foreach ($book_values as $value): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $value); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="meet-the-founder" class="about-section about-founder section" aria-labelledby="meet-founder-title">
  <div class="container about-founder__layout">
    <figure class="about-founder__media">
      <?php if ($founder_image_id): ?>
        <?php echo wp_get_attachment_image($founder_image_id, 'large', false, [
            'class' => 'about-founder__image',
            'alt'   => __('Andrew Signore, founder of Brave Hearts Publishing', 'brave-hearts'),
        ]); ?>
      <?php else: ?>
        <div class="about-founder__placeholder" role="img" aria-label="<?php esc_attr_e('Founder photograph placeholder for Andrew Signore', 'brave-hearts'); ?>">
          <span aria-hidden="true">AS</span>
          <small><?php esc_html_e('Founder photo placeholder', 'brave-hearts'); ?></small>
        </div>
      <?php endif; ?>
    </figure>
    <div class="about-founder__content flow">
      <p class="component-heading__eyebrow"><?php esc_html_e('Meet the founder', 'brave-hearts'); ?></p>
      <h2 id="meet-founder-title" class="text-section-title"><?php echo esc_html($about_field('founder_title', __('Andrew Signore', 'brave-hearts'))); ?></h2>
      <p class="text-lead"><?php echo esc_html($about_field('founder_intro', __('Founder of Brave Hearts Publishing and author of Adventures of Charlotte and Henry.', 'brave-hearts'))); ?></p>
      <p><?php echo esc_html($about_field('founder_text_1', __('Andrew created Brave Hearts Publishing to help children fall in love with reading, exploration, and the natural world.', 'brave-hearts'))); ?></p>
      <p><?php echo esc_html($about_field('founder_text_2', __('His stories bring together firsthand curiosity about big places, respect for real science, and a belief that the right adventure can help a growing reader see themselves as brave.', 'brave-hearts'))); ?></p>
    </div>
  </div>
</section>

<section id="for-families-and-educators" class="about-section about-audiences section section--muted" aria-labelledby="about-audiences-title">
  <div class="container about-audiences__layout">
    <div class="about-audiences__content">
      <p class="component-heading__eyebrow"><?php esc_html_e('For parents and teachers', 'brave-hearts'); ?></p>
      <h2 id="about-audiences-title" class="text-section-title"><?php echo esc_html($about_field('audiences_title', __('Made for the Places Children Learn Best', 'brave-hearts'))); ?></h2>
      <p class="text-lead"><?php echo esc_html($about_field('audiences_intro', __('Brave Hearts books meet curious kids ages 6–9 at home, in classrooms, and anywhere a story can open the door to discovery.', 'brave-hearts'))); ?></p>
    </div>
    <ul class="about-audiences__list">
      <li><?php esc_html_e('Engaging family and classroom read-alouds', 'brave-hearts'); ?></li>
      <li><?php esc_html_e('Classroom discussion, vocabulary, and STEM connections', 'brave-hearts'); ?></li>
      <li><?php esc_html_e('Flexible homeschool learning and unit studies', 'brave-hearts'); ?></li>
      <li><?php esc_html_e('Family reading that continues beyond the final page', 'brave-hearts'); ?></li>
      <li><?php esc_html_e('Curiosity-driven education rooted in the real world', 'brave-hearts'); ?></li>
    </ul>
  </div>
</section>

<section id="about-final-cta" class="about-final-cta final-cta section" aria-labelledby="about-final-cta-title">
  <div class="container container--content final-cta__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Books first. Adventure second. Education always.', 'brave-hearts'); ?></p>
    <h2 id="about-final-cta-title" class="text-section-title"><?php esc_html_e('Start the Adventure', 'brave-hearts'); ?></h2>
    <p class="text-lead final-cta__text"><?php esc_html_e('Choose a book, bring an adventure into the classroom, or join the community that keeps exploring after the final page.', 'brave-hearts'); ?></p>
    <div class="final-cta__actions cluster">
      <a class="btn btn-primary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Shop the Books', 'brave-hearts'); ?></a>
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explore Teacher Resources', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/#adventure-club')); ?>"><?php esc_html_e('Join the Adventure Club', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
