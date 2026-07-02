<?php
/** Reusable Explorer Passport feature card. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'feature' => '', 'title' => '', 'text' => '', 'image_id' => 0, 'image_alt' => '', 'class' => '',
]);
$features = bhp_get_explorer_passport_features();
$feature_key = sanitize_key($args['feature']);
$feature = isset($features[$feature_key]) ? $features[$feature_key] : [];
$title = $args['title'] ?: ($feature['title'] ?? '');
$text = $args['text'] ?: ($feature['description'] ?? '');
if (!$title) { return; }
?>
<article class="passport-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <?php if ($args['image_id']): ?>
    <div class="passport-card__media">
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'medium', false, [
          'class' => 'passport-card__image',
          'alt'   => $args['image_alt'] ?: $title,
      ]); ?>
    </div>
  <?php endif; ?>
  <div class="passport-card__body">
    <h3 class="passport-card__title"><?php echo esc_html($title); ?></h3>
    <?php if ($text): ?><div class="passport-card__text"><?php echo wp_kses_post($text); ?></div><?php endif; ?>
  </div>
</article>
