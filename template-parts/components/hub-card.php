<?php
/** Adventure or Learning Hub card. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'title' => '', 'url' => '', 'eyebrow' => '', 'location' => '', 'text' => '', 'image_id' => 0, 'image_alt' => '', 'cta_label' => '', 'class' => '',
]);
$args['url'] = bhp_get_safe_link_url($args['url']);
if (!$args['title'] || !$args['url']) { return; }
?>
<article class="card destination-card hub-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <?php if ($args['image_id']): ?>
    <div class="card__media hub-card__media" aria-hidden="true">
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'bhp-card-landscape', false, ['class' => 'card__image', 'alt' => '']); ?>
    </div>
  <?php endif; ?>
  <div class="card__body">
    <?php if ($args['eyebrow']): ?><p class="card__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h3 class="card__title"><a href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['title']); ?></a></h3>
    <?php if ($args['location']): ?><p class="hub-card__location"><?php echo esc_html($args['location']); ?></p><?php endif; ?>
    <?php if ($args['text']): ?><div class="card__excerpt"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <a class="hub-card__link" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['cta_label'] ?: __('Explore', 'brave-hearts')); ?> →</a>
  </div>
</article>
