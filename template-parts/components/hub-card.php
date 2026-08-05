<?php
/** Adventure or Learning Hub card. */
defined('ABSPATH') || exit;
/*
 * F2 (2026-08-03) — `image_size` and `image_sizes_attr` are new, optional and
 * BACKWARD-COMPATIBLE. Every existing caller that passes neither renders
 * byte-identically to before (`bhp-card-landscape`, no `sizes` override).
 *
 * They exist because the homepage "Choose Your Adventure" module now shows the
 * three BOOK COVERS instead of three landscape photographs, and a cover is a
 * 2:3 portrait: cropping it into a 640x420 landscape box is exactly the
 * cover-damage this wave is fixing elsewhere.
 */
$args = wp_parse_args($args ?? [], [
    'title' => '', 'url' => '', 'eyebrow' => '', 'location' => '', 'text' => '', 'image_id' => 0, 'image_alt' => '', 'cta_label' => '', 'class' => '', 'age_range' => '', 'formats_info' => [],
    'image_size' => 'bhp-card-landscape', 'image_sizes_attr' => '',
    /*
     * Wave 1 (2026-08-04) — `price_cue` is a single optional string such as
     * "From $11.99". OPTIONAL and BACKWARD-COMPATIBLE: every existing caller
     * passes nothing and renders exactly as before. It is distinct from
     * `formats_info`, which is the two-line format price LIST Andrew removed
     * from the homepage discovery cards on 2026-08-03 (F2) and which this
     * does not reinstate. The homepage passes '' unless
     * `bhp_home_price_cues_enabled()` is switched on — see front-page.php.
     */
    'price_cue' => '',
]);
$args['url'] = bhp_get_safe_link_url($args['url']);
if (!$args['title'] || !$args['url']) { return; }
$hub_img_attrs = ['class' => 'card__image', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async'];
if ($args['image_sizes_attr']) {
    $hub_img_attrs['sizes'] = $args['image_sizes_attr'];
}
?>
<article class="card destination-card hub-card <?php echo esc_attr(sanitize_html_class($args['class'])); ?>">
  <?php if ($args['image_id']): ?>
    <div class="card__media hub-card__media" aria-hidden="true">
      <?php echo wp_get_attachment_image((int) $args['image_id'], $args['image_size'], false, $hub_img_attrs); ?>
    </div>
  <?php endif; ?>
  <div class="card__body">
    <?php if ($args['eyebrow']): ?><p class="card__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p><?php endif; ?>
    <h3 class="card__title"><a href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['title']); ?></a></h3>
    <?php if ($args['location']): ?><p class="hub-card__location"><?php echo esc_html($args['location']); ?></p><?php endif; ?>
    <?php if ($args['text']): ?><div class="card__excerpt"><?php echo wp_kses_post($args['text']); ?></div><?php endif; ?>
    <?php if ($args['age_range'] || $args['price_cue'] || !empty($args['formats_info'])): ?>
      <div class="hub-card__commerce">
        <?php if ($args['age_range']): ?><span class="hub-card__age"><?php echo esc_html($args['age_range']); ?></span><?php endif; ?>
        <?php if ($args['price_cue']): ?><span class="hub-card__price-cue"><?php echo esc_html($args['price_cue']); ?></span><?php endif; ?>
        <?php if (!empty($args['formats_info'])): ?>
          <ul class="hub-card__formats">
            <?php foreach ($args['formats_info'] as $format_label => $format_price): ?>
              <li><?php echo esc_html($format_label); ?> <span class="hub-card__format-price"><?php echo esc_html($format_price); ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <a class="hub-card__link" href="<?php echo esc_url($args['url']); ?>"><?php echo esc_html($args['cta_label'] ?: __('Explore', 'brave-hearts')); ?> →</a>
  </div>
</article>
