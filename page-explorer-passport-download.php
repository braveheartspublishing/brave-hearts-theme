<?php
/**
 * Template Name: Explorer Passport Download Success Page
 * Description: Approved destination for confirmed Explorer Passport delivery.
 */
defined('ABSPATH') || exit;
get_header();
?>
<section class="passport-status-page section" aria-labelledby="passport-download-page-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Adventure Club resource', 'brave-hearts'); ?></p>
    <h1 id="passport-download-page-title"><?php esc_html_e('Your Explorer Passport Is Ready', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('This page will become the confirmed download destination after the printable Passport and delivery workflow are approved.', 'brave-hearts'); ?></p>
  </div>
</section>

<div class="container container--content passport-download-wrap section section--sm">
  <?php get_template_part('template-parts/explorer-passport/download-cta', null, [
      'id'    => 'passport-success-download',
      'title' => __('Become an Official Brave Hearts Explorer.', 'brave-hearts'),
      'text'  => __('The approved Explorer Passport PDF will be available from this protected success page.', 'brave-hearts'),
  ]); ?>
</div>

<section class="passport-section section section--muted" aria-labelledby="passport-download-next-title">
  <div class="container container--content align-center">
    <h2 id="passport-download-next-title" class="text-section-title"><?php esc_html_e('Choose the Adventure That Starts Your Passport', 'brave-hearts'); ?></h2>
    <p class="text-lead"><?php esc_html_e('Every completed Charlotte & Henry book will create another reason to explore, learn, and celebrate.', 'brave-hearts'); ?></p>
    <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Explore the Books', 'brave-hearts'); ?></a>
  </div>
</section>
<?php get_footer(); ?>
