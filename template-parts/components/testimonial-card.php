<?php
/** Parent, teacher, school, or review testimonial card. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'quote' => '', 'name' => '', 'role' => '', 'source' => '', 'class' => '',
]);
if (!$args['quote']) { return; }
?>
<figure class="testimonial-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <blockquote class="testimonial-card__quote">
    <p><?php echo esc_html($args['quote']); ?></p>
  </blockquote>
  <?php if ($args['name'] || $args['role'] || $args['source']): ?>
    <figcaption class="testimonial-card__attribution">
      <?php if ($args['name']): ?><cite class="testimonial-card__name"><?php echo esc_html($args['name']); ?></cite><?php endif; ?>
      <?php if ($args['role']): ?><span class="testimonial-card__role"><?php echo esc_html($args['role']); ?></span><?php endif; ?>
      <?php if ($args['source']): ?><span class="testimonial-card__source"><?php echo esc_html($args['source']); ?></span><?php endif; ?>
    </figcaption>
  <?php endif; ?>
</figure>
