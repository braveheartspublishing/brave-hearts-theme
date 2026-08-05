<?php
/**
 * Amazon customer-review showcase component (Phase 3).
 *
 * Semantically and visually distinct from the Kirkus credibility
 * component (template-parts/components/kirkus-credibility.php):
 * Kirkus is a professional editorial review of one specific title;
 * this component shows genuine Amazon customer feedback, always
 * attributed as customer reviews, never as editorial ones, and never
 * mixed with the Kirkus quote in the same card or carousel.
 *
 * Modes:
 *   'expanded' Fuller cards (review title, excerpt, stars, Verified
 *              Purchase badge when applicable, date, source link).
 *              Used on product pages.
 *   'compact'  Smaller cards, no review title, shorter footprint.
 *              Used on the homepage summary and shop page.
 *
 * States (all handled without a slider or auto-rotation, per the
 * project's visual-design rules -- a stable grid/stack only):
 *   - No approved reviews for this book_slug: renders nothing at all
 *     (fails closed, same pattern as the Kirkus component's missing-
 *     config behavior). This is the real, current state for
 *     'amazon_rainforest' -- not a bug.
 *   - One review: single card, no grid wrapper needed.
 *   - Multiple reviews: a stable CSS grid, never a carousel.
 *
 * No aggregate rating or review count is ever displayed here -- only
 * each individual review's own visible star rating, and only when that
 * exact review showed one. See docs/DECISIONS.md for why.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'book_slug'        => '',
    'mode'             => 'expanded',
    'source'           => '',
    'show_link'        => true,
    'show_book_title'  => false, // set true where the book isn't otherwise visually obvious (e.g. homepage)
    'show_product_link' => false, // "Shop this book" CTA -- only where it isn't redundant with the page you're already on
    'show_eyebrow'     => true, // set false where a caller already supplies its own <h2>/eyebrow wrapper (e.g. the product-page full-width section)
    'show_verified_badge' => true, // set false in tight catalog-card contexts where it doesn't fit cleanly alongside a short excerpt
    'max_reviews'      => 0, // 0 = no cap
    'max_excerpt_words' => 0, // 0 = no cap; set e.g. 18 for catalog/grid cards so excerpts don't dominate the card
    'class'            => '',
]);

$book_slug = sanitize_key($args['book_slug']);
$reviews = $book_slug ? bhp_get_approved_amazon_reviews_for_book($book_slug) : [];
if (!$reviews) {
    return; // No-review state: render nothing rather than an empty shell or placeholder text.
}

if ((int) $args['max_reviews'] > 0) {
    $reviews = array_slice($reviews, 0, (int) $args['max_reviews']);
}

$book_title = bhp_get_amazon_review_book_title($book_slug);
$mode       = $args['mode'] === 'compact' ? 'compact' : 'expanded';
$source     = sanitize_key($args['source']);
$multiple   = count($reviews) > 1;

$wrapper_classes = 'amazon-review-showcase amazon-review-showcase--' . $mode
    . ($multiple ? ' amazon-review-showcase--multiple' : ' amazon-review-showcase--single');
if ($args['class']) {
    $wrapper_classes .= ' ' . sanitize_html_class($args['class']);
}
?>
<div
  class="<?php echo esc_attr($wrapper_classes); ?>"
  data-bhp-impression-event="customer_review_impression"
  data-bhp-book="<?php echo esc_attr($book_slug); ?>"
  data-bhp-source="<?php echo esc_attr($source); ?>"
>
  <?php if ($mode === 'expanded' && $args['show_eyebrow']): ?>
    <p class="amazon-review-showcase__eyebrow"><?php esc_html_e('Real feedback from Amazon customers', 'brave-hearts'); ?></p>
  <?php endif; ?>
  <?php if ($args['show_book_title'] && $book_title): ?>
    <p class="amazon-review-showcase__book-title"><?php echo esc_html($book_title); ?></p>
  <?php endif; ?>
  <div class="amazon-review-showcase__grid">
    <?php foreach ($reviews as $review):
        $rating = isset($review['rating']) ? max(1, min(5, (int) $review['rating'])) : 0;
        $stars  = $rating ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : '';
        $date_display = '';
        if (!empty($review['review_date'])) {
            $timestamp = strtotime($review['review_date']);
            $date_display = $timestamp ? date_i18n(get_option('date_format'), $timestamp) : '';
        }
        $source_label = !empty($review['source_label']) ? $review['source_label'] : __('Amazon customer review', 'brave-hearts');
        $review_url = !empty($review['source_url']) ? esc_url_raw($review['source_url']) : '';

        $word_limit = (int) $args['max_excerpt_words'];
        $words = preg_split('/\s+/', trim($review['excerpt']));
        $truncated = $word_limit > 0 && count($words) > $word_limit;
        $excerpt_html = $truncated
            ? esc_html(implode(' ', array_slice($words, 0, $word_limit))) . '&hellip;'
            : esc_html($review['excerpt']);
    ?>
      <figure class="amazon-review-card">
        <?php if ($rating): ?>
          <?php
          /*
           * CYCLE144-LD-210 (2026-08-05, theme 1.19.201) — `role="img"` is
           * REQUIRED here, it is not decoration.
           *
           * MEASURED, Lighthouse 12.8.2, staging home page, mobile emulation:
           * audit `aria-prohibited-attr` FAILED on exactly this element, twice,
           * with axe's own explanation: "aria-label attribute cannot be used on
           * a div with no valid role attribute." A bare `<div>` has an implicit
           * role of `generic`, and `generic` prohibits every naming attribute —
           * so the label below was being DISCARDED by assistive technology and
           * the star glyphs were announced as nothing at all.
           *
           * `role="img"` is the correct role for a glyph run that conveys a
           * rating visually, and it is what makes the existing `aria-label`
           * legal. The inner `<span aria-hidden="true">` already hides the raw
           * star characters, so the label is now the element's only accessible
           * name, which is exactly the intent this markup always had.
           *
           * ⛔ NOTHING VISUAL CHANGES. `role` has no rendering. The rating text,
           *    the glyphs, the class names and the CSS are untouched, and no
           *    rating value is invented anywhere — `$rating` is the real value
           *    carried by the review record.
           */
          ?>
          <div class="amazon-review-card__stars" role="img" aria-label="<?php echo esc_attr(sprintf(
              /* translators: %d: 1-5 star rating of this individual review */
              __('Rated %d out of 5 stars', 'brave-hearts'), $rating
          )); ?>">
            <span aria-hidden="true"><?php echo esc_html($stars); ?></span>
          </div>
        <?php endif; ?>
        <?php if ($mode === 'expanded' && !empty($review['review_title'])): ?>
          <p class="amazon-review-card__title"><?php echo esc_html($review['review_title']); ?></p>
        <?php endif; ?>
        <blockquote class="amazon-review-card__quote">
          <p>&ldquo;<?php echo $excerpt_html; // phpcs:ignore -- already escaped above ?>&rdquo;</p>
        </blockquote>
        <figcaption class="amazon-review-card__attribution">
          <cite class="amazon-review-card__source"><?php echo esc_html($source_label); ?></cite>
          <?php if ($args['show_verified_badge'] && !empty($review['verified_purchase'])): ?>
            <span class="amazon-review-card__verified"><?php esc_html_e('Verified Purchase', 'brave-hearts'); ?></span>
          <?php endif; ?>
          <?php if ($mode === 'expanded' && $date_display): ?>
            <span class="amazon-review-card__date"><?php echo esc_html($date_display); ?></span>
          <?php endif; ?>
          <?php if ($args['show_link'] && $review_url):
              /*
               * CYCLE144-LD-211 (2026-08-05, theme 1.19.201) — the accessible
               * name must CONTAIN the visible label, and it did not.
               *
               * MEASURED, Lighthouse 12.8.2, staging home page, mobile: audit
               * `label-content-name-mismatch` FAILED on this anchor, twice,
               * with axe's explanation "Text inside the element is not included
               * in the accessible name". The visible label is "Read on Amazon";
               * the old aria-label began "Read this Amazon customer review…",
               * which shares words but does not contain that string.
               *
               * That is WCAG 2.5.3 (Label in Name), and it is a real defect
               * rather than a lint nit: a speech-input user who says the words
               * they can SEE — "Read on Amazon" — cannot activate a control
               * whose accessible name does not contain them.
               *
               * The fix is to lead with the visible label verbatim and keep the
               * extra context after it, so the name is both compliant and still
               * distinguishes the three cards from one another.
               *
               * ⛔ NOTHING VISIBLE CHANGES. The link text, href, target, rel,
               *    analytics attributes and styling are untouched.
               */
              $link_aria_label = sprintf(
                  /* translators: %1$s: the link's visible label, which must be preserved verbatim and first (WCAG 2.5.3 Label in Name). %2$s: book title. */
                  __('%1$s — customer review for %2$s (opens in a new tab)', 'brave-hearts'),
                  __('Read on Amazon', 'brave-hearts'),
                  $book_title
              );
          ?>
            <a
              class="amazon-review-card__link"
              href="<?php echo esc_url($review_url); ?>"
              rel="noopener noreferrer"
              target="_blank"
              aria-label="<?php echo esc_attr($link_aria_label); ?>"
              data-bhp-event="customer_review_source_click"
              data-bhp-book="<?php echo esc_attr($book_slug); ?>"
              data-bhp-source="<?php echo esc_attr($source); ?>"
            ><?php esc_html_e('Read on Amazon', 'brave-hearts'); ?></a>
          <?php endif; ?>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
  <?php if ($args['show_product_link']):
      $product_url = bhp_get_book_product_url($book_slug);
      if ($product_url): ?>
    <p class="amazon-review-showcase__product-link">
      <a
        href="<?php echo esc_url($product_url); ?>"
        data-bhp-event="customer_review_product_click"
        data-bhp-book="<?php echo esc_attr($book_slug); ?>"
        data-bhp-source="<?php echo esc_attr($source); ?>"
      ><?php
          printf(
              /* translators: %s: book title */
              esc_html__('Shop %s', 'brave-hearts'),
              esc_html($book_title)
          );
      ?> <span aria-hidden="true">&rarr;</span></a>
    </p>
  <?php endif; endif; ?>
</div>
