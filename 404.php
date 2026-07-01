<?php get_header(); ?>
<section class="error-page interior-hero interior-hero--atmospheric">
 <div class="container container--content">
  <p class="error-page__code" aria-hidden="true">404</p>
  <h1><?php esc_html_e('This Trail Ends Here', 'brave-hearts'); ?></h1>
  <p class="error-page__message"><?php esc_html_e('The expedition continues in another direction. Search the journal or choose a familiar trail.', 'brave-hearts'); ?></p>
  <?php get_search_form(); ?>
  <div class="cluster error-page__actions"><a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary"><?php esc_html_e('Return Home', 'brave-hearts'); ?></a><a href="<?php echo esc_url(home_url('/books/')); ?>" class="btn btn-light"><?php esc_html_e('Explore Books', 'brave-hearts'); ?></a><a href="<?php echo esc_url(home_url('/blog/')); ?>" class="btn btn-light"><?php esc_html_e('Learning Hub', 'brave-hearts'); ?></a></div>
 </div>
</section>
<?php get_footer(); ?>
