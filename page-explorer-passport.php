<?php
/**
 * Template Name: Explorer Passport Landing Page
 * Description: Main overview and signup page for the Explorer Passport.
 */
defined('ABSPATH') || exit;
get_header();
$passport_features = bhp_get_explorer_passport_features();

get_template_part('template-parts/components/hero', null, [
    'id'       => 'explorer-passport-hero',
    'eyebrow'  => __('The Adventure Club presents', 'brave-hearts'),
    'title'    => __('Become an Official Brave Hearts Explorer.', 'brave-hearts'),
    'text'     => __('Read the books. Explore real places. Collect achievements. Build a passport filled with curiosity, courage, and every adventure still to come.', 'brave-hearts'),
    'primary_link' => ['url' => '#explorer-passport-signup', 'label' => __('Join the Adventure Club', 'brave-hearts')],
    'secondary_link' => ['url' => '#passport-features', 'label' => __('See What’s Inside', 'brave-hearts')],
]);
?>
<section id="passport-features" class="passport-section section" aria-labelledby="passport-features-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Your adventures, all in one place', 'brave-hearts'); ?></p>
      <h2 id="passport-features-title" class="text-section-title"><?php esc_html_e('The Explorer Passport Grows With Every Book', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('The first printable edition will create a foundation for maps, stamps, achievements, certificates, and future badges.', 'brave-hearts'); ?></p>
    </header>
    <div class="passport-grid">
      <?php foreach ($passport_features as $feature_key => $feature): ?>
        <?php get_template_part('template-parts/explorer-passport/passport-card', null, ['feature' => $feature_key]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="passport-journey-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Books first', 'brave-hearts'); ?></p>
      <h2 id="passport-journey-title" class="text-section-title"><?php esc_html_e('Read. Discover. Earn. Explore Again.', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Read an Adventure', 'brave-hearts'), 'text' => __('Begin with a Charlotte & Henry book and discover a real place through story.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Complete the Challenge', 'brave-hearts'), 'text' => __('Finish the reading activity, map, or science challenge connected to the book.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Mark the Passport', 'brave-hearts'), 'text' => __('Celebrate the achievement with a stamp, certificate, or future destination badge.', 'brave-hearts')]); ?>
    </div>
  </div>
</section>

<div id="explorer-passport-signup" class="container passport-signup-wrap section">
  <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
      'id'            => 'explorer-passport-lead-magnet',
      'lead_magnet'   => 'explorer_passport',
      'audience_type' => 'parents_families',
      'title'         => __('Become an Official Brave Hearts Explorer.', 'brave-hearts'),
      'text'          => __('Join the Adventure Club to receive the Explorer Passport when the first printable edition is ready.', 'brave-hearts'),
      'submit_label'  => __('Join the Adventure Club', 'brave-hearts'),
  ]); ?>
</div>

<div class="container container--content passport-download-wrap section section--sm">
  <?php get_template_part('template-parts/explorer-passport/download-cta', null, [
      'id' => 'explorer-passport-download',
  ]); ?>
</div>
<?php get_footer(); ?>
