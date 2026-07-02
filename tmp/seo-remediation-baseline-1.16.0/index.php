<?php
/**
 * Brave Hearts Publishing — Index (Blog/Archive fallback)
 */
get_header(); ?>

<header class="interior-hero interior-hero--parchment archive-hero">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e('Field Notes from the Real World', 'brave-hearts'); ?></p>
    <h1><?php
      if (is_home()) { echo esc_html__('Field Notes', 'brave-hearts'); }
      elseif (is_archive()) { the_archive_title(); }
      elseif (is_search()) { printf(esc_html__('Search results for: %s', 'brave-hearts'), esc_html(get_search_query())); }
      else { echo esc_html__('Posts', 'brave-hearts'); }
    ?></h1>
    <?php if (is_archive() && get_the_archive_description()): ?><div class="archive-hero__intro text-lead"><?php the_archive_description(); ?></div><?php endif; ?>
  </div>
</header>
<section class="site-container section blog-index">

  <div class="card-grid card-grid--posts">
  <?php if (have_posts()): while (have_posts()): the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('card blog-card'); ?>>
      <?php if (has_post_thumbnail()): ?>
        <a class="card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
          <?php the_post_thumbnail('medium_large', ['class' => 'card__image blog-card__image']); ?>
        </a>
      <?php endif; ?>
      <div class="card__body">
        <?php $categories = get_the_category_list(', '); if ($categories): ?><p class="card__eyebrow"><?php echo wp_kses_post($categories); ?></p><?php endif; ?>
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
    <div class="empty-state editorial-surface">
      <h2><?php esc_html_e('No field notes found on this trail.', 'brave-hearts'); ?></h2>
      <p><?php esc_html_e('Try another search, explore the Learning Hub, or return to the books.', 'brave-hearts'); ?></p>
      <?php get_search_form(); ?>
    </div>
  <?php endif; ?>
  </div>

  <div class="pagination-wrap">
    <?php the_posts_pagination(['mid_size' => 2]); ?>
  </div>
</section>

<?php get_footer(); ?>
