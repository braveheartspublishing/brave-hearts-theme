<?php
/**
 * Brave Hearts Publishing — Standard Page Template
 */
get_header();

while (have_posts()): the_post();
  $slug = get_post_field('post_name', get_post());
?>

<header class="interior-hero interior-hero--parchment">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e('Brave Hearts Field Journal', 'brave-hearts'); ?></p>
    <h1><?php the_title(); ?></h1>
    <span class="interior-hero__coordinate" aria-hidden="true">FIELD NOTE · BHP</span>
  </div>
</header>
<div class="page-content page-<?php echo esc_attr($slug); ?>">
  <article id="post-<?php the_ID(); ?>" <?php post_class('entry-content flow editorial-surface'); ?>>
    <?php the_content(); ?>
  </article>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
