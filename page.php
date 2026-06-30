<?php
/**
 * Brave Hearts Publishing — Standard Page Template
 */
get_header();

while (have_posts()): the_post();
  $slug = get_post_field('post_name', get_post());
?>

<div class="page-content page-<?php echo esc_attr($slug); ?>">
  <article id="post-<?php the_ID(); ?>" <?php post_class('entry-content flow'); ?>>
    <header class="page-header">
      <h1><?php the_title(); ?></h1>
    </header>
    <?php the_content(); ?>
  </article>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
