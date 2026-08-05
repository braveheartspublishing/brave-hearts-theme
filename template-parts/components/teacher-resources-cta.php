<?php
/**
 * Teacher resources CTA section.
 *
 * Optional `link_cta_id` (2026-07-18, audit-fix): when supplied, the CTA
 * link carries the same data-bhp-event="contextual_cta_click" + data-bhp-cta-*
 * attribute set the CTA Engine already uses (see class-bhp-cta-engine.php),
 * read generically by the existing click handler in nav.js -- no new
 * analytics mechanism. Omitted entirely when not passed, so existing
 * callers (page-books.php, BHP_Campaign_Landing) keep an identical,
 * untracked link exactly as before.
 *
 * Optional `compact` + `secondary_link` (2026-07-18, audit-fix visual
 * revision): the default layout (large heading + link stacked under the
 * body copy, image on the right) reads as a full landing-page hero when
 * used as a supporting conversion band on a content hub. When `compact` is
 * true, the primary link and an optional `secondary_link` render in a
 * dedicated right-hand action column instead (no image slot in this mode),
 * and the section gets a `teacher-resources-cta--compact` class that a
 * smaller, tighter CSS treatment hooks off. Left entirely unset, behavior
 * and markup are byte-identical to before this addition.
 */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'items' => [], 'link' => [], 'image_id' => 0, 'image_alt' => '', 'class' => '',
    'link_cta_id' => '', 'link_cta_placement' => '', 'link_cta_destination' => '', 'link_cta_audience' => '', 'link_cta_funnel_stage' => '',
    'compact' => false, 'secondary_link' => [],
]);
if (!$args['title']) { return; }
$section_id = $args['id'] ?: wp_unique_id('teacher-resources-');
$heading_id = $section_id . '-title';
$link = wp_parse_args($args['link'], ['url' => '', 'label' => '']);
$link['url'] = bhp_get_safe_link_url($link['url']);
$is_compact = !empty($args['compact']);
$secondary_link = wp_parse_args($args['secondary_link'], ['url' => '', 'label' => '']);

$link_attrs = '';
if ($args['link_cta_id']) {
    $link_attrs = sprintf(
        ' data-bhp-event="contextual_cta_click" data-bhp-cta-id="%1$s" data-bhp-cta-placement="%2$s" data-bhp-cta-destination="%3$s" data-bhp-cta-audience="%4$s" data-bhp-cta-funnel-stage="%5$s"',
        esc_attr($args['link_cta_id']),
        esc_attr($args['link_cta_placement']),
        esc_attr($args['link_cta_destination']),
        esc_attr($args['link_cta_audience']),
        esc_attr($args['link_cta_funnel_stage'])
    );
}
$section_class = 'teacher-resources-cta section section--muted' . ($is_compact ? ' teacher-resources-cta--compact' : '') . ' ' . sanitize_html_class($args['class']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="<?php echo esc_attr(trim($section_class)); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
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
      <?php if (!$is_compact && $link['url'] && $link['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($link['url']); ?>"<?php echo $link_attrs; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_attr() calls above */ ?>><?php echo esc_html($link['label']); ?></a><?php endif; ?>
    </div>
    <?php if ($is_compact): ?>
      <div class="teacher-resources-cta__cta-col">
        <?php if ($link['url'] && $link['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($link['url']); ?>"<?php echo $link_attrs; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_attr() calls above */ ?>><?php echo esc_html($link['label']); ?></a><?php endif; ?>
        <?php if ($secondary_link['url'] && $secondary_link['label']): ?><a class="teacher-resources-cta__secondary-link" href="<?php echo esc_url($secondary_link['url']); ?>"><?php echo esc_html($secondary_link['label']); ?></a><?php endif; ?>
      </div>
    <?php elseif ($args['image_id']): ?>
      <figure class="teacher-resources-cta__media">
        <?php echo wp_get_attachment_image((int) $args['image_id'], 'large', false, ['alt' => $args['image_alt'], 'class' => 'teacher-resources-cta__image']); ?>
      </figure>
    <?php endif; ?>
  </div>
</section>
