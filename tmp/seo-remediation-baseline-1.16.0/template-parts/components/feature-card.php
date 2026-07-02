<?php
/** Feature/value card for reading, adventure, or education benefits. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'title' => '', 'text' => '', 'icon' => '', 'image_id' => 0, 'image_alt' => '', 'link' => [], 'class' => '',
]);
if (!$args['title']) { return; }
$link = wp_parse_args($args['link'], ['url' => '', 'label' => '']);
$link['url'] = bhp_get_safe_link_url($link['url']);
?>
<article class="feature-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <?php if ($args['icon']): ?><span class="feature-card__symbol feature-card__symbol--<?php echo esc_attr(sanitize_html_class($args['icon'])); ?>" aria-hidden="true"></span><?php endif; ?>
  <?php if ($args['image_id']): ?>
    <div class="feature-card__icon" aria-hidden="true"><?php echo wp_get_attachment_image((int) $args['image_id'], 'thumbnail', false, ['alt' => '']); ?></div>
  <?php endif; ?>
  <h3 class="feature-card__title"><?php echo esc_html($args['title']); ?></h3>
  <?php if ($args['text']): ?><div class="feature-card__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
  <?php if ($link['url'] && $link['label']): ?><a class="feature-card__link card__link" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?> →</a><?php endif; ?>
</article>
