<?php
/**
 * Brave Hearts Publishing — Index (Blog/Archive fallback)
 */
get_header(); ?>

<section class="site-container section section--sm blog-index">
  <header class="page-header">
    <h1><?php
      if (is_home()) { echo esc_html__('Latest Posts', 'brave-hearts'); }
      elseif (is_archive()) { the_archive_title(); }
      elseif (is_search()) { printf(esc_html__('Search results for: %s', 'brave-hearts'), esc_html(get_search_query())); }
      else { echo esc_html__('Posts', 'brave-hearts'); }
    ?></h1>
  </header>

  <div class="card-grid card-grid--posts">
  <?php if (have_posts()): while (have_posts()): the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('card blog-card'); ?>>
      <?php if (has_post_thumbnail()): ?>
        <a class="card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
          <?php the_post_thumbnail('medium_large', ['class' => 'card__image blog-card__image']); ?>
        </a>
      <?php endif; ?>
      <div class="card__body">
        <p class="card__meta">
          <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
          <span aria-hidden="true"> · </span>
          <span><?php esc_html_e('By', 'brave-hearts'); ?> <?php the_author_posts_link(); ?></span>
        </p>
        <h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="card__excerpt"><?php the_excerpt(); ?></div>
        <a class="card__link" href="<?php the_permalink(); ?>"><?php esc_html_e('Read more', 'brave-hearts'); ?> →</a>
      </div>
    </article>
  <?php endwhile;
  else: ?>
    <p><?php esc_html_e('No posts found.', 'brave-hearts'); ?></p>
  <?php endif; ?>
  </div>

  <div class="pagination-wrap">
    <?php the_posts_pagination(['mid_size' => 2]); ?>
  </div>
</section>

<?php get_footer(); ?>
