<?php
/**
 * Brave Hearts Publishing — Single Post
 */
get_header();

while (have_posts()): the_post(); ?>

<div class="site-container">
  <article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?> style="max-width:720px;margin:0 auto;padding:60px 0;">

    <header class="post-header" style="margin-bottom:40px;">
      <p style="font-size:0.85rem;color:#6b8fa8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">
        <?php echo get_the_date(); ?> &nbsp;·&nbsp; <?php the_category(', '); ?>
      </p>
      <h1 style="margin-bottom:24px;"><?php the_title(); ?></h1>
      <?php if (has_post_thumbnail()): ?>
        <?php the_post_thumbnail('large', ['style' => 'width:100%;border-radius:8px;margin-bottom:32px;']); ?>
      <?php endif; ?>
    </header>

    <div class="post-content entry-content">
      <?php the_content(); ?>
    </div>

    <footer class="post-footer" style="margin-top:48px;padding-top:24px;border-top:1px solid #e0e8f0;">
      <p style="font-size:0.9rem;color:#6b8fa8;">
        <?php the_tags('Tags: ', ', '); ?>
      </p>
      <div style="margin-top:24px;">
        <?php the_post_navigation(['prev_text' => '← %title', 'next_text' => '%title →']); ?>
      </div>
    </footer>

  </article>
</div>

<?php endwhile; ?>
<?php get_footer(); ?>
