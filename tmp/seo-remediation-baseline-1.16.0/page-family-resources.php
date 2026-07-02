<?php
/** Family Resources page, selected automatically for the family-resources slug. */
defined('ABSPATH') || exit;
get_header();
?>
<section class="interior-hero interior-hero--atmospheric" aria-labelledby="family-resources-title">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e('For families', 'brave-hearts'); ?></p>
    <h1 id="family-resources-title"><?php esc_html_e('Bring the Expedition Home', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Simple ways to keep noticing, questioning, reading, and exploring together after the final page.', 'brave-hearts'); ?></p>
  </div>
</section>
<section class="section" aria-labelledby="family-paths-title">
  <div class="container">
    <header class="component-heading component-heading--center"><p class="component-heading__eyebrow"><?php esc_html_e('Screens down. Eyes up.', 'brave-hearts'); ?></p><h2 id="family-paths-title" class="text-section-title"><?php esc_html_e('Follow Curiosity Into Your Own Backyard', 'brave-hearts'); ?></h2></header>
    <div class="grid grid--3">
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Weekend Field Notes', 'brave-hearts'), 'text' => __('Choose one small corner of the real world and record what your family notices.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Wonder Aloud', 'brave-hearts'), 'text' => __('Use a question from the story to begin a family conversation or outdoor search.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Book-Based Exploration', 'brave-hearts'), 'text' => __('Connect each Charlotte and Henry destination to maps, wildlife, weather, and place.', 'brave-hearts')]); ?>
    </div>
  </div>
</section>
<section class="final-cta section" aria-labelledby="family-next-title"><div class="container container--content final-cta__inner"><p class="component-heading__eyebrow"><?php esc_html_e('The next trail', 'brave-hearts'); ?></p><h2 id="family-next-title" class="text-section-title"><?php esc_html_e('Choose a Real-World Adventure', 'brave-hearts'); ?></h2><div class="cluster"><a class="btn btn-primary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Explore the Books', 'brave-hearts'); ?></a><a class="btn btn-outline" href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Visit the Learning Hub', 'brave-hearts'); ?></a></div></div></section>
<?php get_footer(); ?>
