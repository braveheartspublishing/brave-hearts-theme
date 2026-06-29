<?php
/** Reusable teacher resource category card with honest availability state. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'title' => '', 'text' => '', 'url' => '', 'label' => '', 'status' => 'placeholder', 'class' => '',
]);
if (!$args['title']) { return; }
$is_available = $args['status'] === 'available' && (bool) $args['url'];
?>
<article class="teacher-resource-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <p class="teacher-resource-card__status"><?php echo esc_html($is_available ? __('Available resource', 'brave-hearts') : __('Resource coming soon', 'brave-hearts')); ?></p>
  <h3 class="teacher-resource-card__title"><?php echo esc_html($args['title']); ?></h3>
  <?php if ($args['text']): ?><div class="teacher-resource-card__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
  <div class="teacher-resource-card__action">
    <?php if ($is_available): ?>
      <a class="card__link" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['label'] ?: __('View Resource', 'brave-hearts')); ?> →</a>
    <?php else: ?>
      <span class="card__link is-disabled" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
    <?php endif; ?>
  </div>
</article>
