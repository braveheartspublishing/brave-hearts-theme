<?php
/** Adventure Club landing page, selected automatically for the adventure-club slug. */
defined('ABSPATH') || exit;
get_header();
?>
<section class="interior-hero interior-hero--atmospheric" aria-labelledby="adventure-club-title">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e('Join the Expedition', 'brave-hearts'); ?></p>
    <h1 id="adventure-club-title"><?php esc_html_e('The Adventure Continues Beyond the Book', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Field notes, printable expeditions, new-book news, and real-world discoveries for families and educators.', 'brave-hearts'); ?></p>
  </div>
</section>
<section class="section" aria-labelledby="adventure-club-receive-title">
  <div class="container">
    <header class="component-heading component-heading--center"><p class="component-heading__eyebrow"><?php esc_html_e('What arrives in your field journal', 'brave-hearts'); ?></p><h2 id="adventure-club-receive-title" class="text-section-title"><?php esc_html_e('A Quiet Invitation to Keep Exploring', 'brave-hearts'); ?></h2></header>
    <div class="grid grid--3">
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Printable Expeditions', 'brave-hearts'), 'text' => __('Activities and field notes that carry a story into the real world.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('New Adventure News', 'brave-hearts'), 'text' => __('Thoughtful updates when a new book or destination is ready to explore.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Family and Educator Resources', 'brave-hearts'), 'text' => __('Practical ways to continue reading, questioning, and discovering together.', 'brave-hearts')]); ?>
    </div>
  </div>
</section>
<div class="container section section--sm">
  <?php get_template_part('template-parts/acquisition/adventure-club-signup', null, ['id' => 'adventure-club-page-signup', 'source_page' => get_permalink()]); ?>
</div>
<?php get_footer(); ?>
