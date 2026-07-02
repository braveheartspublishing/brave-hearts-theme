<?php
/** Curated Explorer Expedition Guides article card. */
defined('ABSPATH') || exit;
$post = get_post($args['post'] ?? null);
if (!$post) { return; }
$data = bhp_get_guide_post_data($post);
$hubs = bhp_get_guide_hubs();
$topic = $hubs[$data['primary'] ?? ''] ?? __('Field Note', 'brave-hearts');
?>
<article class="guide-article-card">
  <?php if (has_post_thumbnail($post)): ?>
    <a class="guide-article-card__media" href="<?php echo esc_url(get_permalink($post)); ?>" tabindex="-1" aria-hidden="true">
      <?php echo get_the_post_thumbnail($post, 'bhp-card-landscape', ['class' => 'guide-article-card__image', 'loading' => 'lazy']); ?>
    </a>
  <?php endif; ?>
  <div class="guide-article-card__body">
    <p class="card__eyebrow"><?php echo esc_html($topic); ?></p>
    <h3><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
    <p><?php echo esc_html(wp_trim_words(get_the_excerpt($post), 24)); ?></p>
    <a class="card__link" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(sprintf(__('Explore %s', 'brave-hearts'), get_the_title($post))); ?> <span aria-hidden="true">→</span></a>
  </div>
</article>
