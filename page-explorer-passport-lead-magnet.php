<?php
/**
 * Template Name: Explorer Passport Lead Magnet Page
 * Description: Focused signup page for the Explorer Passport lead magnet.
 */
defined('ABSPATH') || exit;
get_header();
?>
<section class="passport-page-hero section section--dark" aria-labelledby="passport-lead-title">
  <div class="container container--content passport-page-hero__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('A free Adventure Club printable', 'brave-hearts'); ?></p>
    <h1 id="passport-lead-title" class="text-hero"><?php esc_html_e('Become an Official Brave Hearts Explorer.', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('The Explorer Passport will help readers track real destinations, celebrate completed books, and carry every adventure into the next one.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section" aria-labelledby="passport-lead-benefits-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="passport-lead-benefits-title" class="text-section-title"><?php esc_html_e('A Passport Built for Curious Readers', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php get_template_part('template-parts/explorer-passport/passport-card', null, ['feature' => 'world_explorer_map']); ?>
      <?php get_template_part('template-parts/explorer-passport/passport-card', null, ['feature' => 'adventure_stamps']); ?>
      <?php get_template_part('template-parts/explorer-passport/passport-card', null, ['feature' => 'reading_achievements']); ?>
    </div>
  </div>
</section>

<div class="container passport-signup-wrap section section--muted">
  <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
      'id'            => 'explorer-passport-focused-signup',
      'lead_magnet'   => 'explorer_passport',
      'audience_type' => 'parents_families',
      'title'         => __('Join the Adventure Club', 'brave-hearts'),
      'text'          => __('Be first to receive the Explorer Passport, new printable adventures, and early access to every new book.', 'brave-hearts'),
      'submit_label'  => __('Join the Adventure Club', 'brave-hearts'),
  ]); ?>
</div>
<?php get_footer(); ?>
