<?php
/**
 * Brave Hearts Publishing — Single Post
 */
get_header();

while (have_posts()): the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-post content-narrow section section--sm'); ?>>
  <header class="post-header">
    <p class="post-header__meta text-caption">
      <?php echo esc_html(get_the_date()); ?> &nbsp;·&nbsp; <?php the_category(', '); ?>
    </p>
    <h1 class="post-header__title"><?php the_title(); ?></h1>
    <?php if (has_post_thumbnail()): ?>
      <?php the_post_thumbnail('large', ['class' => 'post-header__image']); ?>
    <?php endif; ?>
  </header>

  <div class="post-content entry-content flow">
    <?php the_content(); ?>
  </div>

  <footer class="post-footer">
    <div class="post-footer__tags">
      <?php the_tags(esc_html__('Tags: ', 'brave-hearts'), ', '); ?>
    </div>
    <div class="post-footer__navigation">
      <?php the_post_navigation([
        'prev_text' => '← %title',
        'next_text' => '%title →',
      ]); ?>
    </div>
  </footer>
</article>

<?php endwhile; ?>
<?php get_footer(); ?>
