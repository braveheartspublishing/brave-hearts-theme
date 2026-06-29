<?php get_header(); ?>
<section class="site-container error-page">
  <p class="error-page__code" aria-hidden="true">404</p>
  <h1><?php esc_html_e('This page went somewhere big.', 'brave-hearts'); ?></h1>
  <p class="error-page__message"><?php esc_html_e("Probably the Mariana Trench. Let's get you back.", 'brave-hearts'); ?></p>
  <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary"><?php esc_html_e('Back to Home', 'brave-hearts'); ?></a>
</section>
<?php get_footer(); ?>
