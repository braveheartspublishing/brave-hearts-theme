<?php
/** Reusable teacher resource category card with an optional destination. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'title' => '', 'text' => '', 'url' => '', 'label' => '', 'class' => '',
]);
if (!$args['title']) { return; }
$args['url'] = bhp_get_safe_link_url($args['url']);
?>
<article class="teacher-resource-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <h3 class="teacher-resource-card__title"><?php echo esc_html($args['title']); ?></h3>
  <?php if ($args['text']): ?><div class="teacher-resource-card__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
  <?php if ($args['url']): ?>
    <div class="teacher-resource-card__action">
      <a class="card__link" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['label'] ?: __('View Resource', 'brave-hearts')); ?> →</a>
    </div>
  <?php endif; ?>
</article>
