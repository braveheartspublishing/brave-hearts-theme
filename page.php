<?php
/**
 * Brave Hearts Publishing — Standard Page Template
 */
get_header();

while (have_posts()): the_post();
  $slug = get_post_field('post_name', get_post());
  $is_home_page = ($slug === 'home' || is_front_page());
?>

<?php if ($is_home_page): ?>
  <?php the_content(); ?>
<?php else: ?>
  <div class="page-content page-<?php echo esc_attr($slug); ?>">
    <?php the_content(); ?>
  </div>
<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
