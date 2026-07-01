<?php
/**
 * Brave Hearts Publishing — Single Post
 */
get_header();

while (have_posts()): the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-post section'); ?>>
  <header class="post-header">
    <?php $category_list = get_the_category_list(', '); ?>
    <p class="post-header__meta text-caption">
      <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
      <span aria-hidden="true"> · </span>
      <span class="post-header__author"><?php esc_html_e('By', 'brave-hearts'); ?> <?php the_author_posts_link(); ?></span>
      <?php if ($category_list): ?>
        <span aria-hidden="true"> · </span>
        <span class="post-header__categories"><?php echo wp_kses_post($category_list); ?></span>
      <?php endif; ?>
    </p>
    <p class="component-heading__eyebrow"><?php esc_html_e('Explorer’s Journal', 'brave-hearts'); ?></p>
    <h1 class="post-header__title"><?php the_title(); ?></h1>
    <?php if (has_post_thumbnail()): ?>
      <?php the_post_thumbnail('large', ['class' => 'post-header__image']); ?>
    <?php endif; ?>
  </header>

  <div class="post-content entry-content flow content-narrow editorial-surface">
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

  <aside class="post-expedition-cta" aria-labelledby="post-expedition-title">
    <p class="component-heading__eyebrow"><?php esc_html_e('Keep exploring', 'brave-hearts'); ?></p>
    <h2 id="post-expedition-title"><?php esc_html_e('Follow Curiosity Into the Real World', 'brave-hearts'); ?></h2>
    <div class="cluster"><a class="btn btn-primary" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Explore the Books', 'brave-hearts'); ?></a><a class="btn btn-outline" href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Visit the Learning Hub', 'brave-hearts'); ?></a></div>
  </aside>
</article>

<?php endwhile; ?>
<?php get_footer(); ?>
