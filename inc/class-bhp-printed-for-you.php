<?php
/**
 * "Printed Just for You" print-on-demand expectation-setting notice.
 *
 * Objective: proactively and reassuringly set delivery-time expectations
 * now that end-to-end WooCommerce -> Bookvault testing has confirmed
 * print-on-demand orders arrive in roughly 8 days, but customers
 * currently have no on-site indication their book is printed to order.
 * This is not meant to slow purchases or create friction -- it's a
 * premium, single-source-of-truth reassurance component.
 *
 * Architecture mirrors the Kirkus/Amazon-review components exactly:
 * one centralized data function (bhp_get_printed_for_you_data()), one
 * reusable partial (template-parts/components/printed-for-you-notice.php),
 * one render wrapper (bhp_render_printed_for_you_notice()) that every
 * call site uses -- so the approved copy is authored in exactly one
 * place, never hardcoded per placement.
 *
 * Placements (staging only, per the approved sprint scope):
 *   - Single product page:      woocommerce_single_product_summary, priority 37
 *                                (between the existing "What Kids Will Learn"
 *                                block at 36 and the teacher/shipping links at 38)
 *   - Order Received/Thank You: woocommerce_thankyou, priority 25
 *   - Cart page (Blocks):        [bhp_printed_for_you] shortcode, placed once
 *                                as a block inside the Cart page's own
 *                                filled-cart-block content
 *   - Checkout page (Blocks):    same shortcode, placed inside the existing
 *                                checkout-additional-information-block slot,
 *                                replacing the older ad-hoc shipping note
 *                                paragraph it consolidates
 *
 * Classic WooCommerce template hooks (woocommerce_before_cart,
 * woocommerce_before_checkout_form, etc.) do not fire on this site's
 * actual Cart/Checkout pages, since both are built with the real
 * WooCommerce Cart/Checkout blocks, not the classic shortcodes -- a
 * shortcode block embedded in each page's own content is the only
 * proven mechanism for static informational content on those two pages.
 */
defined('ABSPATH') || exit;

/**
 * Single source of truth for the approved copy. Never hardcode any of
 * this text in a template or call site -- always read it from here.
 *
 * Revised 2026-07-13 (copy-revision sprint): 'tagline' and 'paragraphs'
 * entries may contain a literal <strong> tag for the approved emphasis
 * points -- this is trusted, developer-authored copy (not user input),
 * rendered via wp_kses() with a <strong>-only allowlist in the partial,
 * never esc_html() (which would print the tags as literal text). Do not
 * widen that allowlist or introduce any other markup here without also
 * updating the partial's escaping to match.
 */
function bhp_get_printed_for_you_data() {
    return apply_filters('bhp_printed_for_you_data', [
        'title'      => __('Printed Just for You', 'brave-hearts'),
        'tagline'    => __('<strong>Good things take time.</strong>', 'brave-hearts'),
        'paragraphs' => [
            __('Every Brave Hearts book is <strong>printed especially for you</strong> after your order is placed.', 'brave-hearts'),
            __('This helps us reduce waste, maintain exceptional quality, and continue publishing independently.', 'brave-hearts'),
            __('Each book is <strong>printed especially for your order</strong>. Production and delivery times can vary, so please order early for birthdays, holidays, and other special occasions.', 'brave-hearts'),
        ],
        'thanks'     => __('Thank you for supporting independent publishing.', 'brave-hearts'),
    ]);
}

/**
 * Renders the notice via the shared partial. Every placement (hooks and
 * the shortcode below) calls this one function -- never
 * get_template_part() directly -- so there is exactly one call path to
 * keep consistent if the args signature ever changes.
 */
function bhp_render_printed_for_you_notice($context = 'default', $args = []) {
    ob_start();
    get_template_part('template-parts/components/printed-for-you-notice', null, array_merge(['context' => $context], $args));
    return ob_get_clean();
}

/**
 * Product page: woocommerce_single_product_summary priority 37 -- sits
 * between the existing "What Kids Will Learn" block (36) and the
 * teacher/shipping links (38), both already established slots.
 */
function bhp_woocommerce_product_printed_for_you_section() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    echo bhp_render_printed_for_you_notice('product'); // phpcs:ignore -- partial fully escapes its own output
}
add_action('woocommerce_single_product_summary', 'bhp_woocommerce_product_printed_for_you_section', 37);

/**
 * Order Received / Thank You page: woocommerce_thankyou fires reliably
 * on this site regardless of the Blocks-based checkout (confirmed via
 * existing bhp_order_confirmation_expedition_links() usage at priority
 * 30). Priority 25 places this notice just above that block.
 */
function bhp_woocommerce_thankyou_printed_for_you_notice($order_id) {
    if (!$order_id) {
        return;
    }
    echo bhp_render_printed_for_you_notice('thankyou'); // phpcs:ignore -- partial fully escapes its own output
}
add_action('woocommerce_thankyou', 'bhp_woocommerce_thankyou_printed_for_you_notice', 25);

/**
 * [bhp_printed_for_you] shortcode -- the only mechanism for the Cart and
 * Checkout pages, since both are built from the real WooCommerce Blocks
 * (not the classic shortcode templates) and classic action hooks do not
 * fire on them. Embedding this shortcode as a block in each page's own
 * content still routes through the same single render function as every
 * other placement, so nothing is hardcoded per page.
 */
function bhp_shortcode_printed_for_you($atts) {
    $atts = shortcode_atts(['context' => 'default'], $atts, 'bhp_printed_for_you');

    /*
     * B5 (2026-08-03). Andrew, walk-3, verbatim: the print-on-demand
     * explainer is "too much information when trying to purchase", and it
     * belongs BELOW the Place Order button.
     *
     * ⛔ WHY THIS IS DONE IN THE THEME AND NOT BY EDITING THE CHECKOUT PAGE.
     *    The notice is placed by a [bhp_printed_for_you] block that sits
     *    INSIDE `woocommerce/checkout-additional-information-block`, which is
     *    an inner block of `woocommerce/checkout-fields-block`, above
     *    `woocommerce/checkout-actions-block`. Moving it by editing the page
     *    would be a checkout-configuration change on a live environment, which
     *    is an Andrew gate. Suppressing it here and re-emitting it after the
     *    whole checkout block (see the render_block filter below) achieves the
     *    same rendered order, changes no stored record on any environment, and
     *    reverses by deleting two blocks of code.
     *
     * The cart page, the product page and the thank-you page are untouched:
     * only `is_checkout()` short-circuits, and the order-received endpoint
     * (which is_checkout() also matches) is deliberately excluded so the
     * post-purchase reassurance still renders where it belongs.
     */
    if (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) {
        return '';
    }

    return bhp_render_printed_for_you_notice(sanitize_html_class($atts['context']));
}
add_shortcode('bhp_printed_for_you', 'bhp_shortcode_printed_for_you');

/**
 * B5, second half — re-emit the notice AFTER the entire checkout block.
 *
 * `render_block` runs for the outermost `woocommerce/checkout` block after
 * all of its inner blocks have rendered, so appending here places the notice
 * below the fields column (which ends in the Place Order button) and below
 * the order-summary column. On a phone, where the two columns stack, that is
 * unambiguously below Place Order; on desktop it is below both columns.
 *
 * Fails closed: if the shortcode already returned nothing because the notice
 * itself is empty, this appends an empty wrapper's worth of nothing.
 */
function bhp_checkout_printed_for_you_after_actions($block_content, $block) {
    if (empty($block['blockName']) || 'woocommerce/checkout' !== $block['blockName']) {
        return $block_content;
    }
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return $block_content;
    }
    $notice = bhp_render_printed_for_you_notice('checkout');
    if ('' === trim($notice)) {
        return $block_content;
    }
    return $block_content . '<div class="bhp-checkout-after-actions">' . $notice . '</div>';
}
add_filter('render_block', 'bhp_checkout_printed_for_you_after_actions', 10, 2);
