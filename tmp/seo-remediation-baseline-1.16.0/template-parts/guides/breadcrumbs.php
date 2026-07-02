<?php
/** Accessible guide path for curated posts. Rank Math breadcrumbs remain untouched. */
defined('ABSPATH') || exit;
$post = get_post($args['post'] ?? null);
$data = bhp_get_guide_post_data($post);
if (!$post || !$data) { return; }
$hubs = bhp_get_guide_hubs();
$primary = $data['primary'];
?>
<nav class="guide-breadcrumbs" aria-label="<?php esc_attr_e('Explorer Expedition Guide path', 'brave-hearts'); ?>">
  <ol>
    <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'brave-hearts'); ?></a></li>
    <li><a href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explorer Expedition Guides', 'brave-hearts'); ?></a></li>
    <li><a href="<?php echo esc_url(bhp_get_guide_hub_url($primary)); ?>"><?php echo esc_html($hubs[$primary] ?? $primary); ?></a></li>
    <li><span aria-current="page"><?php echo esc_html(get_the_title($post)); ?></span></li>
  </ol>
</nav>
