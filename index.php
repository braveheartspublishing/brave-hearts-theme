<?php
/**
 * Brave Hearts Publishing — Index (Blog/Archive fallback)
 */
get_header(); ?>

<div class="site-container" style="padding-top:60px;padding-bottom:60px;">
  <header class="page-header" style="margin-bottom:40px;">
    <h1><?php
      if (is_home()) { echo 'Latest Posts'; }
      elseif (is_archive()) { the_archive_title(); }
      elseif (is_search()) { printf('Search results for: %s', get_search_query()); }
      else { echo 'Posts'; }
    ?></h1>
  </header>

  <div class="posts-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:32px;">
  <?php if (have_posts()): while (have_posts()): the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?> style="background:#fff;border:1px solid #e0e8f0;border-radius:8px;overflow:hidden;">
      <?php if (has_post_thumbnail()): ?>
        <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium_large', ['style' => 'width:100%;height:200px;object-fit:cover;']); ?></a>
      <?php endif; ?>
      <div style="padding:24px;">
        <p style="font-size:0.8rem;color:#6b8fa8;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.08em;">
          <?php echo get_the_date(); ?>
        </p>
        <h2 style="font-size:1.2rem;margin-bottom:12px;"><a href="<?php the_permalink(); ?>" style="color:#0d1b2a;"><?php the_title(); ?></a></h2>
        <p style="color:#4a5568;font-size:0.95rem;"><?php the_excerpt(); ?></p>
        <a href="<?php the_permalink(); ?>" style="color:#0d5fa6;font-size:0.9rem;font-weight:bold;">Read more →</a>
      </div>
    </article>
  <?php endwhile;
  else: ?>
    <p>No posts found.</p>
  <?php endif; ?>
  </div>

  <div style="margin-top:40px;text-align:center;">
    <?php the_posts_pagination(['mid_size' => 2]); ?>
  </div>
</div>

<?php get_footer(); ?>
