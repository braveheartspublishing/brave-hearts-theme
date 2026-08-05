<?php
/**
 * Homepage hero component.
 *
 * @param array $args {
 *   @type string $id             Optional section ID.
 *   @type string $eyebrow        Short brand or series label.
 *   @type string $title          Required hero heading.
 *   @type string $text           Supporting copy; safe inline HTML allowed.
 *   @type array  $primary_link   URL and label for the book-first action.
 *   @type array  $secondary_link URL and label for the adventure action.
 *   @type int    $image_id       Decorative cinematic image attachment ID.
 *   @type string $class          Additional section class.
 *   @type string $aside          Optional supporting visual HTML.
 *   @type bool   $aside_after_title When true, $aside is rendered immediately
 *                                     after the <h1> instead of at the end of
 *                                     the hero content. Defaults to false, so
 *                                     every existing caller keeps its current
 *                                     DOM order untouched. The aside markup is
 *                                     rendered exactly ONCE either way -- this
 *                                     moves the single node, it does not
 *                                     duplicate it or hide a second copy.
 *   @type string $details        Optional supporting detail row HTML.
 *   @type string $commercial_subtext Optional one-line plain-text commercial
 *                                     explanation shown near the CTAs, distinct
 *                                     from the brand-voice $text above it.
 * }
 *
 * Homepage mobile reading order (2026-07-31): the homepage passes
 * `aside_after_title` so the three-book preview sits directly beneath the H1,
 * giving a visitor the product before any further copy. This is a real DOM
 * move, not a CSS reorder -- `order`, absolute positioning and transforms are
 * deliberately NOT used to fake it, so the keyboard/tab order on a phone
 * matches what is on screen. Desktop and tablet are unaffected visually
 * because the preview is absolutely positioned at those widths and is
 * therefore out of flow regardless of where it sits among its siblings.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'id'                 => '',
    'eyebrow'            => '',
    'title'              => '',
    'text'               => '',
    'primary_link'       => [],
    'secondary_link'     => [],
    'image_id'           => 0,
    'class'              => '',
    'aside'              => '',
    'aside_after_title'  => false,
    'details'            => '',
    'commercial_subtext' => '',
]);

if (!$args['title']) {
    return;
}

// Only meaningful when there is actually an aside to move.
$aside_after_title = !empty($args['aside_after_title']) && $args['aside'] !== '';

$section_id = $args['id'] ?: wp_unique_id('home-hero-');
$heading_id = $section_id . '-title';
$classes    = trim(
    'home-hero section--dark '
    . ($aside_after_title ? 'home-hero--aside-after-title ' : '')
    . sanitize_html_class($args['class'])
);
$primary    = wp_parse_args($args['primary_link'], ['url' => '', 'label' => '']);
$secondary  = wp_parse_args($args['secondary_link'], ['url' => '', 'label' => '']);
$primary['url'] = bhp_get_safe_link_url($primary['url']);
$secondary['url'] = bhp_get_safe_link_url($secondary['url']);
?>
<section id="<?php echo esc_attr($section_id); ?>" class="<?php echo esc_attr($classes); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <?php if ($args['image_id']): ?>
    <div class="home-hero__media" aria-hidden="true">
      <?php echo wp_get_attachment_image((int) $args['image_id'], 'full', false, [
          'class'         => 'home-hero__image',
          'alt'           => '',
          'loading'       => 'eager',
          'fetchpriority' => 'high',
      ]); ?>
    </div>
  <?php endif; ?>
  <div class="home-hero__overlay" aria-hidden="true"></div>
  <div class="container home-hero__content">
    <?php if ($args['eyebrow']): ?><p class="home-hero__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h1 id="<?php echo esc_attr($heading_id); ?>" class="text-hero home-hero__title"><?php echo esc_html($args['title']); ?></h1>
    <?php /* The aside renders here OR at the end of this container -- never
             both. See the $aside_after_title guard on the closing block. */ ?>
    <?php if ($aside_after_title): ?><?php echo wp_kses_post($args['aside']); ?><?php endif; ?>
    <?php if ($args['text']): ?><div class="text-lead home-hero__text"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <?php if ($args['commercial_subtext']): ?><p class="home-hero__commercial-subtext"><?php echo esc_html($args['commercial_subtext']); ?></p><?php endif; ?>
    <?php if (($primary['url'] && $primary['label']) || ($secondary['url'] && $secondary['label'])): ?>
      <div class="home-hero__actions cluster">
        <?php if ($primary['url'] && $primary['label']): ?><a class="btn btn-primary" href="<?php echo esc_url($primary['url']); ?>"><?php echo esc_html($primary['label']); ?></a><?php endif; ?>
        <?php if ($secondary['url'] && $secondary['label']): ?><a class="btn btn-outline" href="<?php echo esc_url($secondary['url']); ?>"><?php echo esc_html($secondary['label']); ?></a><?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($args['details']): ?><div class="home-hero__details"><?php echo wp_kses_post($args['details']); ?></div><?php endif; ?>
    <?php if ($args['aside'] && !$aside_after_title): ?><?php echo wp_kses_post($args['aside']); ?><?php endif; ?>
  </div>
</section>
