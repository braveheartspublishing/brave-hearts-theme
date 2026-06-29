<?php
/** Book-specific classroom resource card. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'title' => '', 'destination' => '', 'description' => '', 'image_id' => 0, 'image_alt' => '',
    'primary_url' => '', 'resources_url' => '', 'themes' => [], 'subjects' => [], 'class' => '',
]);
if (!$args['title']) { return; }
$themes = is_array($args['themes']) ? array_filter($args['themes']) : [];
$subjects = is_array($args['subjects']) ? array_filter($args['subjects']) : [];
?>
<article class="teacher-book-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <div class="teacher-book-card__media">
    <?php if ($args['image_id']): ?>
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'bhp-book-card', false, [
          'class' => 'teacher-book-card__cover',
          'alt'   => $args['image_alt'] ?: $args['title'],
      ]); ?>
    <?php else: ?>
      <div class="teacher-book-card__cover-placeholder" role="img" aria-label="<?php echo esc_attr(sprintf(__('Cover placeholder for %s', 'brave-hearts'), $args['title'])); ?>">
        <span aria-hidden="true"><?php esc_html_e('Charlotte & Henry', 'brave-hearts'); ?></span>
      </div>
    <?php endif; ?>
  </div>
  <div class="teacher-book-card__body">
    <?php if ($args['destination']): ?><p class="card__eyebrow"><?php echo esc_html($args['destination']); ?></p><?php endif; ?>
    <h3 class="teacher-book-card__title"><?php echo esc_html($args['title']); ?></h3>
    <?php if ($args['description']): ?><div class="teacher-book-card__description"><?php echo wp_kses_post($args['description']); ?></div><?php endif; ?>
    <?php if ($themes): ?>
      <div class="teacher-book-card__themes">
        <h4><?php esc_html_e('Classroom themes', 'brave-hearts'); ?></h4>
        <ul><?php foreach ($themes as $theme): ?><li><?php echo esc_html($theme); ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <?php if ($subjects): ?>
      <div class="teacher-book-card__subjects">
        <h4><?php esc_html_e('Suggested subjects', 'brave-hearts'); ?></h4>
        <ul><?php foreach ($subjects as $subject): ?><li><?php echo esc_html($subject); ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <div class="teacher-book-card__actions">
      <?php if ($args['primary_url']): ?>
        <a class="btn btn-primary" href="<?php echo esc_url($args['primary_url']); ?>"><?php esc_html_e('View Book', 'brave-hearts'); ?></a>
      <?php else: ?>
        <span class="btn btn-primary is-disabled" aria-disabled="true"><?php esc_html_e('View Book', 'brave-hearts'); ?></span>
      <?php endif; ?>
      <?php if ($args['resources_url']): ?>
        <a class="btn btn-outline" href="<?php echo esc_url($args['resources_url']); ?>"><?php esc_html_e('View Resources', 'brave-hearts'); ?></a>
      <?php else: ?>
        <span class="btn btn-outline is-disabled" aria-disabled="true"><?php esc_html_e('Coming Resources', 'brave-hearts'); ?></span>
      <?php endif; ?>
    </div>
  </div>
</article>
