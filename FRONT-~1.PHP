<?php
/**
 * Brave Hearts Publishing — Front Page
 * WordPress uses this file when a static front page is set.
 */
get_header();

while (have_posts()): the_post(); ?>
  <div class="front-page-content">
    <?php the_content(); ?>
  </div>
<?php endwhile; ?>

<?php get_footer(); ?>
