<?php
/**
 * Featured books section.
 *
 * @param array $args Section ID, eyebrow, title, intro, books, and optional CTA.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'id'       => '',
    'eyebrow'  => '',
    'title'    => '',
    'intro'    => '',
    'books'    => [],
    'cta_link' => [],
    'class'    => '',
]);

if (!$args['title'] || !$args['books']) {
    return;
}

$section_id = $args['id'] ?: wp_unique_id('featured-books-');
$heading_id = $section_id . '-title';
$cta        = wp_parse_args($args['cta_link'], ['url' => '', 'label' => '']);
$cta['url'] = bhp_get_safe_link_url($cta['url']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="featured-books section <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container">
    <header class="component-heading component-heading--center">
      <?php if ($args['eyebrow']): ?><p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
      <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title']); ?></h2>
      <?php if ($args['intro']): ?><div class="component-heading__intro text-lead"><?php echo wp_kses_post($args['intro']); ?></div><?php endif; ?>
    </header>
    <div class="featured-books__grid card-grid">
      <?php foreach ($args['books'] as $book): ?>
        <?php get_template_part('template-parts/components/book-card', null, $book); ?>
      <?php endforeach; ?>
    </div>
    <?php if ($cta['url'] && $cta['label']): ?><div class="component-section-action"><a class="btn btn-secondary" href="<?php echo esc_url($cta['url']); ?>"><?php echo esc_html($cta['label']); ?></a></div><?php endif; ?>
  </div>
</section>
