<?php
/**
 * "Printed Just for You" print-on-demand expectation notice.
 *
 * One reusable partial rendered from a single centralized data source
 * (bhp_get_printed_for_you_data()) so the approved copy is never
 * hardcoded more than once. Called from four places via
 * bhp_render_printed_for_you_notice(): the single product page, the
 * order-received/thank-you page, and (via the [bhp_printed_for_you]
 * shortcode) the WooCommerce Blocks Cart and Checkout pages.
 *
 * $args:
 *   'context' string  Where this is rendering ('product', 'cart',
 *                      'checkout', 'thankyou') -- purely a CSS/data hook
 *                      for future contextual styling; the copy itself
 *                      never varies by context.
 *   'class'   string  Extra class(es) appended to the wrapper.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'context' => 'default',
    'class'   => '',
]);

$data = bhp_get_printed_for_you_data();
if (empty($data['title']) || empty($data['paragraphs'])) {
    return; // Missing configuration -- render nothing rather than a broken/empty block.
}

$context = sanitize_html_class($args['context']);
$wrapper_classes = 'bhp-printed-for-you bhp-printed-for-you--' . $context;
if ($args['class']) {
    $wrapper_classes .= ' ' . sanitize_html_class($args['class']);
}

// 'tagline' and 'paragraphs' entries may contain the approved <strong>
// emphasis -- allow only that one tag, strip everything else. 'title'
// and 'thanks' never contain markup, so they stay on plain esc_html().
$allowed_emphasis = ['strong' => []];
?>
<div class="<?php echo esc_attr($wrapper_classes); ?>">
  <span class="bhp-printed-for-you__icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M6 9V3.5C6 3.22386 6.22386 3 6.5 3H17.5C17.7761 3 18 3.22386 18 3.5V9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      <rect x="3.5" y="9" width="17" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
      <path d="M6 15.5H18V20.5C18 20.7761 17.7761 21 17.5 21H6.5C6.22386 21 6 20.7761 6 20.5V15.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
      <circle cx="16.25" cy="11.75" r="0.75" fill="currentColor"/>
    </svg>
  </span>
  <div class="bhp-printed-for-you__body">
    <p class="bhp-printed-for-you__title"><?php echo esc_html($data['title']); ?></p>
    <p class="bhp-printed-for-you__tagline"><?php echo wp_kses($data['tagline'], $allowed_emphasis); ?></p>
    <?php foreach ($data['paragraphs'] as $paragraph): ?>
      <p class="bhp-printed-for-you__text"><?php echo wp_kses($paragraph, $allowed_emphasis); ?></p>
    <?php endforeach; ?>
    <p class="bhp-printed-for-you__thanks"><?php echo esc_html($data['thanks']); ?></p>
  </div>
</div>
