<?php
/**
 * Brave Hearts Publishing — Single Post
 *
 * Direction 1, "Expedition field notes", board build step 2 of 4.
 * The rules, the measurements and every decision behind this ordering live in
 * `inc/blog-post-template.php`. This file places; it does not decide.
 *
 * ⭐ THE HEADER ORDER CHANGED, AND THE ORDER IS THE FIX.
 *    1.19.260 rendered: breadcrumb (4 lines, ending in the post title) -> meta
 *    -> eyebrow -> H1. Measured on staging2 at an asserted 390: 289 px between
 *    the site header and the top of the H1, of which 92 px was a breadcrumb
 *    whose last crumb repeated the headline sitting 100 px below it.
 *
 *    1.19.261 renders: compact breadcrumb (one line, no title crumb) -> eyebrow
 *    -> H1 -> deck -> meta. The reader meets the thing, then the reader, then
 *    the date. Everything that is not the headline moved below it or was
 *    removed as duplication.
 *
 * ⛔ THE FEATURED IMAGE STAYS BELOW THE MASTHEAD BLOCK, unchanged. The rubric's
 *    "no above-fold image" note is a DESIGN option for step 4, not this step,
 *    and hoisting a 650 px-tall image above the H1 would undo the very dead
 *    band this step exists to reclaim.
 *
 * @package brave-hearts
 */
get_header();

while (have_posts()): the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-post section'); ?>>
  <?php
  /*
   * One faint line-art mark, positioned against the article box. Emitted first
   * so it paints behind the masthead without a z-index war; it is
   * `pointer-events: none` and `aria-hidden` in the stylesheet.
   */
  echo bhp_blog_plate_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup, no data
  ?>

  <?php get_template_part('template-parts/guides/breadcrumbs', null, ['post' => get_post()]); ?>

  <header class="post-header<?php echo bhp_blog_template_active() ? ' post-header--field-note' : ''; ?>">
    <?php if (bhp_blog_template_active()): ?>

      <p class="component-heading__eyebrow bhp-post-eyebrow"><?php echo esc_html(bhp_blog_eyebrow_text()); ?></p>
      <h1 class="post-header__title"><?php the_title(); ?></h1>

      <?php $bhp_deck = bhp_blog_deck_text(); ?>
      <?php if ($bhp_deck): ?>
        <p class="bhp-post-deck"><?php echo esc_html($bhp_deck); ?></p>
      <?php endif; ?>

      <?php $bhp_meta = bhp_blog_meta_parts(); ?>
      <?php if ($bhp_meta): ?>
        <p class="post-header__meta text-caption">
          <?php $bhp_first = true; ?>
          <?php foreach ($bhp_meta as $bhp_key => $bhp_value): ?>
            <?php if (!$bhp_first): ?><span aria-hidden="true"> · </span><?php endif; ?>
            <?php if ('date' === $bhp_key): ?>
              <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html($bhp_value); ?></time>
            <?php else: ?>
              <span class="post-header__<?php echo esc_attr($bhp_key); ?>"><?php echo esc_html($bhp_value); ?></span>
            <?php endif; ?>
            <?php $bhp_first = false; ?>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>

    <?php else: ?>
      <?php /* 1.19.260 ordering, preserved verbatim for `bhp_blog_template_enabled` => false. */ ?>
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
    <?php endif; ?>

    <?php if (has_post_thumbnail()): ?>
      <?php the_post_thumbnail('large', ['class' => 'post-header__image']); ?>
    <?php endif; ?>
  </header>

  <div class="post-content entry-content flow content-narrow editorial-surface">
    <?php the_content(); ?>
  </div>

  <?php
  /*
   * The end-of-post capture sits BEFORE the related-content block: the ask
   * belongs at the end of the reading, not after a grid of other articles the
   * reader has already been offered as an exit.
   */
  echo bhp_blog_capture_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template part
  ?>

  <?php get_template_part('template-parts/guides/related-content', null, ['post' => get_post()]); ?>

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
