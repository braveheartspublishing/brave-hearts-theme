<?php
/** Reciprocal guide and related-article continuation block. */
defined('ABSPATH') || exit;
$post = get_post($args['post'] ?? null);
$data = bhp_get_guide_post_data($post);
if (!$post || !$data) { return; }
$hubs = bhp_get_guide_hubs();
$related = bhp_get_related_guide_posts($post, 4);
$adventures = function_exists('bhp_get_series_adventures') ? bhp_get_series_adventures() : [];
$book_urls = [
    'mariana-trench' => bhp_get_safe_link_url($adventures['mariana_trench']['primary_url'] ?? '', home_url('/books/')),
    'mount-everest' => bhp_get_safe_link_url($adventures['mount_everest']['primary_url'] ?? '', home_url('/books/')),
    'series-wide' => home_url('/books/'),
];
?>
<aside class="guide-continuation" aria-labelledby="guide-continuation-title">
  <p class="component-heading__eyebrow"><?php esc_html_e('Continue the expedition', 'brave-hearts'); ?></p>
  <h2 id="guide-continuation-title"><?php esc_html_e('Follow This Trail Further', 'brave-hearts'); ?></h2>
  <div class="guide-continuation__paths">
    <a href="<?php echo esc_url(bhp_get_guide_hub_url($data['primary'])); ?>"><?php echo esc_html(sprintf(__('Continue exploring %s', 'brave-hearts'), $hubs[$data['primary']] ?? $data['primary'])); ?></a>
    <?php if (!empty($data['destination'])): ?><a href="<?php echo esc_url(bhp_get_guide_hub_url($data['destination'])); ?>"><?php echo esc_html(sprintf(__('Visit the %s Expedition Guide', 'brave-hearts'), $hubs[$data['destination']] ?? $data['destination'])); ?></a><?php endif; ?>
    <?php if (!empty($book_urls[$data['book'] ?? ''])): ?><a href="<?php echo esc_url($book_urls[$data['book']]); ?>"><?php esc_html_e('Explore the related Brave Hearts book', 'brave-hearts'); ?></a><?php endif; ?>
    <?php if (in_array('Educators', $data['audiences'] ?? [], true)): ?><a href="<?php echo esc_url(bhp_get_guide_hub_url('educator-resources')); ?>"><?php esc_html_e('Find resources for educators', 'brave-hearts'); ?></a><?php endif; ?>
    <?php if (in_array('Families', $data['audiences'] ?? [], true)): ?><a href="<?php echo esc_url(bhp_get_guide_hub_url('family-resources')); ?>"><?php esc_html_e('Continue exploring as a family', 'brave-hearts'); ?></a><?php endif; ?>
  </div>
  <?php if ($related): ?>
    <h3><?php esc_html_e('Related Field Notes', 'brave-hearts'); ?></h3>
    <div class="guide-article-grid guide-article-grid--related">
      <?php foreach ($related as $related_post) { get_template_part('template-parts/guides/article-card', null, ['post' => $related_post]); } ?>
    </div>
  <?php endif; ?>
</aside>
