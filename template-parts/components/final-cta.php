<?php
/** Gentle final homepage call to action. */
defined('ABSPATH') || exit;
$args = wp_parse_args($args ?? [], [
    'id' => '', 'eyebrow' => '', 'title' => '', 'text' => '', 'primary_link' => [], 'secondary_link' => [], 'class' => '',
]);
if (!$args['title']) { return; }
$section_id = $args['id'] ?: wp_unique_id('final-cta-');
$heading_id = $section_id . '-title';
$primary = wp_parse_args($args['primary_link'], ['url' => '', 'label' => '', 'attrs' => []]);
$secondary = wp_parse_args($args['secondary_link'], ['url' => '', 'label' => '', 'attrs' => []]);
$primary['url'] = bhp_get_safe_link_url($primary['url']);
$secondary['url'] = bhp_get_safe_link_url($secondary['url']);
// 'attrs' (Phase 1D) is an optional flat array of extra HTML attributes
// (e.g. data-bhp-event, data-bhp-cta-id) -- every existing caller that
// never sets it renders identically to before this addition.
$render_extra_attrs = function ($attrs) {
    $out = '';
    foreach ((array) $attrs as $name => $value) {
        $name = preg_replace('/[^a-zA-Z0-9-]/', '', (string) $name);
        if ($name === '') { continue; }
        $out .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
    }
    return $out;
};
?>
<section id="<?php echo esc_attr($section_id); ?>" class="final-cta section <?php echo esc_attr(sanitize_html_class($args['class'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container container--content final-cta__inner">
    <?php if ($args['eyebrow']): ?><p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h2 id="<?php echo esc_attr($heading_id); ?>" class="text-section-title"><?php echo esc_html($args['title']); ?></h2>
    <?php if ($args['text']): ?><div class="text-lead final-cta__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <div class="final-cta__actions cluster">
      <?php if ($primary['url'] && $primary['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($primary['url']); ?>"<?php echo $render_extra_attrs($primary['attrs']); ?>><?php echo esc_html($primary['label']); ?></a><?php endif; ?>
      <?php if ($secondary['url'] && $secondary['label']): ?><a class="btn btn-ghost" href="<?php echo esc_url($secondary['url']); ?>"<?php echo $render_extra_attrs($secondary['attrs']); ?>><?php echo esc_html($secondary['label']); ?></a><?php endif; ?>
    </div>
  </div>
</section>
