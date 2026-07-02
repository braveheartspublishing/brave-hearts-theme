<?php
/** Customer-facing adventure card grouped across product-format SKUs. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'title' => '', 'destination' => '', 'age_range' => '', 'description' => '', 'formats' => [],
    'image_id' => 0, 'image_alt' => '', 'primary_url' => '', 'formats_url' => '', 'format_urls' => [],
    'amazon_url' => '', 'shop_url' => '',
    'available' => false, 'class' => '',
]);
if (!$args['title']) { return; }
$formats = is_array($args['formats']) ? array_filter($args['formats']) : [];
$format_urls = is_array($args['format_urls']) ? $args['format_urls'] : [];
$configured_primary_url = bhp_get_safe_link_url($args['primary_url']);
$primary_url = bhp_get_safe_link_url($configured_primary_url, $args['shop_url']);
$formats_url = $configured_primary_url ? bhp_get_safe_link_url($args['formats_url'], $args['shop_url']) : '';
$amazon_url = bhp_get_safe_link_url($args['amazon_url']);
?>
<article class="adventure-book-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <div class="adventure-book-card__media">
    <?php if ($args['image_id']): ?>
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'bhp-book-card', false, [
          'class' => 'adventure-book-card__cover',
          'alt'   => $args['image_alt'] ?: $args['title'],
      ]); ?>
    <?php else: ?>
      <div class="adventure-book-card__cover-placeholder" role="img" aria-label="<?php echo esc_attr($args['title']); ?>">
        <span aria-hidden="true"><?php esc_html_e('Charlotte & Henry', 'brave-hearts'); ?></span>
      </div>
    <?php endif; ?>
  </div>
  <div class="adventure-book-card__body">
    <?php if ($args['destination']): ?><p class="card__eyebrow"><?php echo esc_html($args['destination']); ?></p><?php endif; ?>
    <h3 class="adventure-book-card__title"><?php echo esc_html($args['title']); ?></h3>
    <p class="adventure-book-card__age text-caption"><?php echo esc_html($args['age_range'] ?: __('Ages 6–9', 'brave-hearts')); ?></p>
    <?php if ($args['description']): ?><div class="adventure-book-card__description"><?php echo wp_kses_post($args['description']); ?></div><?php endif; ?>
    <?php if ($formats): ?>
      <div class="adventure-book-card__formats" aria-label="<?php echo esc_attr($args['available'] ? __('Available formats', 'brave-hearts') : __('Planned formats', 'brave-hearts')); ?>">
        <h4><?php echo esc_html($args['available'] ? __('Available formats', 'brave-hearts') : __('Planned formats', 'brave-hearts')); ?></h4>
        <ul>
          <?php foreach ($formats as $format): ?>
            <?php $format_url = bhp_get_safe_link_url($format_urls[$format] ?? ''); ?>
            <li>
              <?php if ($format_url): ?>
                <a href="<?php echo esc_url($format_url); ?>"><?php echo esc_html($format); ?></a>
              <?php else: ?>
                <?php echo esc_html($format); ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <div class="adventure-book-card__actions">
      <?php if ($primary_url): ?>
        <a
          class="btn btn-primary"
          href="<?php echo esc_url($primary_url); ?>"
          data-bhp-event="bhp_direct_purchase_click"
          data-bhp-book="<?php echo esc_attr($args['key'] ?? ''); ?>"
          data-bhp-source="books_page"
        ><?php echo esc_html($configured_primary_url ? __('Buy Direct', 'brave-hearts') : __('Browse the Shop', 'brave-hearts')); ?></a>
      <?php endif; ?>
      <?php if ($formats_url): ?>
        <a class="btn btn-outline" href="<?php echo esc_url($formats_url); ?>"><?php esc_html_e('Shop Formats', 'brave-hearts'); ?></a>
      <?php endif; ?>
    </div>
    <?php if (!$args['available']): ?><p class="adventure-book-card__availability"><?php esc_html_e('This adventure is not currently available for purchase.', 'brave-hearts'); ?></p><?php endif; ?>
    <?php if (function_exists('bhp_render_amazon_affiliate_section')): ?>
      <?php echo bhp_render_amazon_affiliate_section($args['key'] ?? '', $args['title'], ['source' => 'books_page']); // phpcs:ignore ?>
    <?php endif; ?>
  </div>
</article>
