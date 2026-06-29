<?php
/**
 * Homepage book card component.
 * Accepts live product content, formats, pricing, and review data through $args.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'title'        => '',
    'url'          => '',
    'image_id'     => 0,
    'image_alt'    => '',
    'badge'        => '',
    'age_range'    => '',
    'formats'      => [],
    'rating'       => 0,
    'review_count' => 0,
    'review'       => '',
    'description'  => '',
    'price'        => '',
    'cta_label'    => '',
]);

if (!$args['title'] || !$args['url']) {
    return;
}

$formats      = is_array($args['formats']) ? implode(', ', array_filter($args['formats'])) : $args['formats'];
$rating       = max(0, min(5, (float) $args['rating']));
$review_count = max(0, (int) $args['review_count']);
$filled_stars = (int) round($rating);
$stars        = str_repeat('★', $filled_stars) . str_repeat('☆', 5 - $filled_stars);
$rating_label = $review_count
    ? sprintf(__('Rated %1$s out of 5 from %2$d reviews', 'brave-hearts'), number_format_i18n($rating, 1), $review_count)
    : sprintf(__('Rated %s out of 5', 'brave-hearts'), number_format_i18n($rating, 1));
?>
<article class="card book-card homepage-book-card">
  <?php if ($args['image_id']): ?>
    <a class="card__media homepage-book-card__media" href="<?php echo esc_url($args['url']); ?>">
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'bhp-book-card', false, [
          'class' => 'card__image homepage-book-card__image',
          'alt'   => $args['image_alt'] ?: $args['title'],
      ]); ?>
    </a>
  <?php endif; ?>
  <div class="card__body">
    <?php if ($args['badge']): ?><p class="card__eyebrow"><?php echo esc_html($args['badge']); ?></p><?php endif; ?>
    <h3 class="card__title"><a href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['title']); ?></a></h3>
    <p class="homepage-book-card__details text-caption">
      <?php echo esc_html(implode(' · ', array_filter([$args['age_range'], $formats, $args['review']]))); ?>
    </p>
    <?php if ($rating > 0): ?>
      <p class="homepage-book-card__rating" aria-label="<?php echo esc_attr($rating_label); ?>">
        <span aria-hidden="true"><?php echo esc_html($stars); ?></span>
        <?php if ($review_count): ?><span class="homepage-book-card__review-count" aria-hidden="true">(<?php echo esc_html(number_format_i18n($review_count)); ?>)</span><?php endif; ?>
      </p>
    <?php endif; ?>
    <?php if ($args['description']): ?><div class="card__excerpt"><?php echo wp_kses_post($args['description']); ?></div><?php endif; ?>
    <div class="card__footer">
      <?php if ($args['price']): ?><span class="book-card__price"><?php echo esc_html($args['price']); ?></span><?php endif; ?>
      <a class="card__link" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['cta_label'] ?: __('View book', 'brave-hearts')); ?> →</a>
    </div>
  </div>
</article>
