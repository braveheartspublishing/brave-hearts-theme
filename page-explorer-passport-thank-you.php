<?php
/**
 * Template Name: Explorer Passport Thank-You Page
 * Description: Post-signup confirmation page for future provider redirects.
 */
defined('ABSPATH') || exit;
get_header();
?>
<section class="passport-status-page section" aria-labelledby="passport-thank-you-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Welcome to the Adventure Club', 'brave-hearts'); ?></p>
    <h1 id="passport-thank-you-title"><?php esc_html_e('You’re on the Explorer List', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('When email delivery is connected, this page will confirm signup and explain exactly where to find the Explorer Passport.', 'brave-hearts'); ?></p>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="passport-thank-you-next-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="passport-thank-you-next-title" class="text-section-title"><?php esc_html_e('Continue the Adventure', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Check Your Inbox', 'brave-hearts'), 'text' => __('Future delivery instructions and the approved Passport link will arrive by email.', 'brave-hearts')]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Choose a Book', 'brave-hearts'), 'text' => __('Start with a Charlotte & Henry adventure and meet the real place behind the story.', 'brave-hearts'), 'link' => ['url' => home_url('/books/'), 'label' => __('Explore books', 'brave-hearts')]]); ?>
      <?php get_template_part('template-parts/components/feature-card', null, ['title' => __('Keep Exploring', 'brave-hearts'), 'text' => __('Return for future stamps, achievements, certificates, and destination badges.', 'brave-hearts'), 'link' => ['url' => home_url('/explorer-passport/'), 'label' => __('Visit the Passport', 'brave-hearts')]]); ?>
    </div>
  </div>
</section>

<div class="container container--content passport-download-wrap section section--sm">
  <?php get_template_part('template-parts/explorer-passport/download-cta', null, [
      'id'    => 'passport-thank-you-download',
      'title' => __('Explorer Passport Download', 'brave-hearts'),
      'text'  => __('The download remains unavailable until the printable file and delivery process are approved.', 'brave-hearts'),
  ]); ?>
</div>
<?php get_footer(); ?>
