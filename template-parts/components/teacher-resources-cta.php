<?php
/** Teacher resources CTA section. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'items' => [], 'link' => [], 'image_id' => 0, 'image_alt' => '', 'class' => '',
]);
if (!$args['title']) { return; }
$section_id = $args['id'] ?: wp_unique_id('teacher-resources-');
$heading_id = $section_id . '-title';
$link = wp_parse_args($args['link'], ['url' => '', 'label' => '']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="teacher-resources-cta section section--muted <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container teacher-resources-cta__layout">
    <div class="teacher-resources-cta__content">
      <?php if ($args['eyebrow']): ?><p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
      <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title']); ?></h2>
      <?php if ($args['text']): ?><div class="text-lead teacher-resources-cta__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
      <?php if ($args['items']): ?>
        <ul class="teacher-resources-cta__list">
          <?php foreach ($args['items'] as $item): ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($link['url'] && $link['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a><?php endif; ?>
    </div>
    <?php if ($args['image_id']): ?>
      <figure class="teacher-resources-cta__media">
        <?php echo wp_get_attachment_image((int) $args['image_id'], 'large', false, ['alt' => $args['image_alt'], 'class' => 'teacher-resources-cta__image']); ?>
      </figure>
    <?php endif; ?>
  </div>
</section>
