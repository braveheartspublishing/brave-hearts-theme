<?php
/**
 * Homepage book card component.
 * Accepts title, url, image_id, image_alt, badge, age_range, review,
 * description, price, and cta_label through $args.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'title'       => '',
    'url'         => '',
    'image_id'    => 0,
    'image_alt'   => '',
    'badge'       => '',
    'age_range'   => '',
    'review'      => '',
    'description' => '',
    'price'       => '',
    'cta_label'   => '',
]);

if (!$args['title'] || !$args['url']) {
    return;
}
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
    <?php if ($args['age_range'] || $args['review']): ?>
      <p class="homepage-book-card__details text-caption">
        <?php echo esc_html(implode(' · ', array_filter([$args['age_range'], $args['review']]))); ?>
      </p>
    <?php endif; ?>
    <?php if ($args['description']): ?><div class="card__excerpt"><?php echo wp_kses_post($args['description']); ?></div><?php endif; ?>
    <div class="card__footer">
      <?php if ($args['price']): ?><span class="book-card__price"><?php echo esc_html($args['price']); ?></span><?php endif; ?>
      <a class="card__link" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['cta_label'] ?: __('View book', 'brave-hearts')); ?> →</a>
    </div>
  </div>
</article>
