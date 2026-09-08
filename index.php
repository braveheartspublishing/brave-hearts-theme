<?php
/**
 * Brave Hearts Publishing — Index (Blog/Archive fallback)
 */
get_header(); ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.394 — THE ARCHIVE BAND. `CYCLE179-CX-BUILD-394`, item 3.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, 2026-09-07, verbatim. ⚠ RELAYED through the supervising
 *    session — NOT witnessed first-hand here (Standing Rules §9.2 rule 2):
 *    *"using the third photo you can see that we have simplified and narrowed
 *    the Expedition catalog bar - That should be done similarly on the blog
 *    page for 'Field notes'."*
 *
 * ⛔⛔ THE SUPERSEDED HEADER, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
 *     RE-DERIVED. Until this release this template rendered:
 *
 *       <header class="interior-hero interior-hero--parchment archive-hero">
 *         <div class="container container--content">
 *           <h1>{Field Notes | archive title | search string | Posts}</h1>
 *           {archive description, when present}
 *         </div>
 *       </header>
 *
 *     — MEASURED LIVE on staging 1.19.393 at a CSS viewport of 1536 x 830:
 *     y80 to y245, **165px**, against the shop band's **74px** for strictly
 *     more content. The height came from `.interior-hero`'s
 *     `padding: clamp(5.5rem, 10vw, 9rem)` and an H1 still taking the
 *     interior hero's display size.
 *
 * ⛔ THE H1'S WORDS DO NOT MOVE, AND NEITHER DOES ITS LOGIC. The four-branch
 *    conditional below is byte-for-byte the one that was here. "Field Notes"
 *    on the index, `the_archive_title()` on an archive, the search string on
 *    a search, "Posts" as the fallback. ⛔ Locked copy is proposed, never
 *    changed — only the setting changes.
 *
 * ⛔ NO NEW CLAIM AND NO NEW STRING. The series eyebrow and the brand line are
 *    the SAME two strings the shop band already prints (`functions.php`,
 *    `bhp_woocommerce_archive_hero()`), and "Ages 6 to 9" is the standing
 *    approved age band. ⛔ 6 to 9, never 5 to 9. ⛔ No "we" (§9.1). ⛔ No em
 *    dash. ⛔ No outcome claim. ⛔ No review, rating or result.
 *
 * ⭐ ONE TEMPLATE, THREE SURFACES. This file serves the blog index, every
 *    category / tag / author / date archive, and the search results page.
 *    Verified live 2026-09-07: `/blog/` and `/?s=ocean` both render from it.
 *
 * ⚠ THE ARCHIVE DESCRIPTION IS KEPT. It moves below the row and is compacted
 *   by `.bhp-archive-band__intro`; it is not dropped. Losing real content to
 *   win a height measurement would be the wrong trade.
 *
 * ⭐ 1.19.269 item 5 (founder ruling, 2026-08-19) REMOVED a decorative eyebrow
 *    above this H1 — `<p class="component-heading__eyebrow">Field Notes from
 *    the Real World</p>`. ⛔ THAT REMOVAL STANDS AND IS NOT REVERSED HERE. The
 *    series line below is not that eyebrow: it is the shop band's own series
 *    row, carried across so the two bands match. The keep/remove test is
 *    stated once, in full, in `page-about.php`.
 */
?>
<header class="interior-hero interior-hero--parchment archive-hero bhp-archive-band">
  <div class="container bhp-archive-band__inner">
    <p class="bhp-archive-band__series"><?php esc_html_e('Adventures of Charlotte and Henry', 'brave-hearts'); ?></p>
    <div class="bhp-archive-band__row">
      <h1 class="bhp-archive-band__title"><?php
        if (is_home()) { echo esc_html__('Field Notes', 'brave-hearts'); }
        elseif (is_archive()) { the_archive_title(); }
        elseif (is_search()) { printf(esc_html__('Search results for: %s', 'brave-hearts'), esc_html(get_search_query())); }
        else { echo esc_html__('Posts', 'brave-hearts'); }
      ?></h1>
      <span class="bhp-archive-band__diamond" aria-hidden="true">&#9670;</span>
      <p class="bhp-archive-band__line"><?php esc_html_e('Big Places. Brave Hearts.', 'brave-hearts'); ?></p>
      <p class="bhp-archive-band__meta"><?php esc_html_e('Ages 6 to 9', 'brave-hearts'); ?></p>
    </div>
    <?php if (is_archive() && get_the_archive_description()): ?><div class="archive-hero__intro bhp-archive-band__intro"><?php the_archive_description(); ?></div><?php endif; ?>
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
