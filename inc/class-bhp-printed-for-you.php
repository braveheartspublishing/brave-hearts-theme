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
            /*
             * CYCLE164-CX-02 (2026-08-18) — standing rule §9.1, the voice rule,
             * adopted by Andrew Signore on 2026-08-18: "when you are putting
             * front facing words to customers, there is no 'we'. I am the sole
             * operator of the company."
             *
             * SUPERSEDED wording, recorded rather than deleted so the movement
             * is visible and is not re-derived:
             *   "This helps us reduce waste, maintain exceptional quality, and
             *    continue publishing independently."
             * One word changed: "us" -> "me". Meaning is unchanged.
             */
            __('This helps me reduce waste, maintain exceptional quality, and continue publishing independently.', 'brave-hearts'),
            __('Each book is <strong>printed especially for your order</strong>. Production and delivery times can vary, so please order early for birthdays, holidays, and other special occasions.', 'brave-hearts'),
        ],
        'thanks'     => __('Thank you for supporting independent publishing.', 'brave-hearts'),
    ]);
}

/**
 * ⭐⭐ CYCLE164-CX-01 — is THIS request a school-visit hand-delivery session?
 *
 * ⛔ WHY THIS EXISTS. On a visit-flagged checkout the parent is being told that
 *    Andrew carries the signed book to their child's school on a named day. The
 *    print-on-demand notice sitting under that checkout told the same parent the
 *    book is "printed especially for you after your order is placed" and that
 *    "Production and delivery times can vary, so please order early." That is the
 *    shipping-and-waiting mental model the hand-delivery build exists to remove,
 *    and Andrew rejected it in his own words on 2026-08-17: "They will think its
 *    getting shipped." Found on live production by `commerce-cx` and recorded as
 *    CYCLE164-CX-01.
 *
 * ⭐ IT IS NOT A SECOND SOURCE OF TRUTH. It delegates to
 *    `bhp_school_visit_use_delivery_framing()` in the bundle plugin
 *    (`includes/school-visit-pickup.php`), which is the ONE predicate the pickup
 *    machine already uses on every other surface — the collection page, the cart
 *    drawer, the checkout cross-sell and the theme's own delivery bullet in
 *    `inc/book-formats.php`. Adding a parallel session read here is exactly how
 *    two surfaces end up telling one parent two different stories.
 *
 * ⛔ IT FAILS OPEN, DELIBERATELY. With the plugin deactivated, or on any request
 *    that is not a live flagged visit session, this returns FALSE and every
 *    caller renders byte-identically to 1.19.236. The control path — ordinary
 *    paying customers who have nothing to do with a school — is the thing most
 *    expensive to break here, so the guard can only ever REMOVE the notice from a
 *    flagged session and can never add, move or alter it for anyone else.
 *
 * @return bool
 */
function bhp_printed_for_you_is_visit_session() {
    return function_exists('bhp_school_visit_use_delivery_framing')
        && (bool) bhp_school_visit_use_delivery_framing();
}

/**
 * ⭐⭐ CYCLE164-LD-COPY-GATE-PASS-2 — is THIS ORDER a hand-delivery order?
 *
 * ⛔ WHY A SECOND PREDICATE EXISTS AND WHY IT IS NOT A SECOND SOURCE OF TRUTH.
 *    `bhp_printed_for_you_is_visit_session()` above answers a question about the
 *    REQUEST: "is the person looking at this page in a visit-flagged session?"
 *    The order-received page asks a DIFFERENT question — "was the order that was
 *    just paid for a hand-delivery order?" — and the session is the wrong
 *    instrument for it. A session flag can expire, be cleared, or belong to a
 *    different browser than the one opening the confirmation link from an email.
 *    The order itself cannot.
 *
 * ⭐ IT ADDS NO NEW LOGIC. It delegates to `bhp_school_pickup_order_is_pickup()`
 *    in the bundle plugin, which is the ONE order-scoped predicate the pickup
 *    machine already uses everywhere an ORDER is the subject — the Bookvault
 *    fulfilment skip, the resend-action removal, the order-totals row rename, and
 *    the confirmation email's own branch at
 *    `woocommerce/emails/customer-processing-order.php:139`. Two predicates for two
 *    different subjects, both owned by the plugin. Zero copies of either.
 *
 * ⛔ IT FAILS OPEN, exactly like its session twin: no plugin, no resolvable order,
 *    or an ordinary shipped order all return FALSE and the caller renders exactly
 *    what 1.19.237 rendered.
 *
 * @param WC_Order|int|mixed $order Order or order id.
 * @return bool
 */
function bhp_printed_for_you_order_is_visit($order) {
    return function_exists('bhp_school_pickup_order_is_pickup')
        && (bool) bhp_school_pickup_order_is_pickup($order);
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
    /*
     * ⭐⭐ CYCLE164-LD-COPY-GATE-PASS-2, THE PRODUCT-PAGE GATE.
     *
     * ⛔ A parent who arrived from their school's pre-visit link is told, on the
     *    collection page and in the cart drawer, that Andrew carries the signed book
     *    to the school on a named day. Telling that same session, on the same product
     *    page, that the book is "printed especially for you after your order is
     *    placed" and that "Production and delivery times can vary, so please order
     *    early" is the shipping-and-waiting story the hand-delivery build exists to
     *    remove. Andrew rejected it in his own words on 2026-08-17: "They will think
     *    its getting shipped."
     *
     * ⭐ EARLY RETURN, NOT AN EMPTY ECHO. Nothing is emitted at all, so priority 37
     *    simply does not occupy its slot and the blocks either side of it — "What
     *    Kids Will Learn" at 36 and the teacher/shipping links at 38 — close up
     *    exactly as on any page where this component was never registered.
     *
     * ⛔ CONTROL PATH: for every shopper who has never touched a school link the
     *    predicate is FALSE, this branch is not taken, and the notice renders
     *    byte-identically to 1.19.237.
     */
    if (bhp_printed_for_you_is_visit_session()) {
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
    /*
     * ⭐⭐ CYCLE164-LD-COPY-GATE-PASS-2, THE ORDER-RECEIVED GATE — the surface where
     *    the wrong copy costs the MOST, not the least. The parent has already paid.
     *    The page above this notice tells them Andrew is bringing the signed books to
     *    the school by hand and that nothing is being posted. A "Printed Just for You
     *    / production and delivery times can vary" panel directly underneath
     *    contradicts that at the exact moment they are deciding whether they
     *    understood what they just bought.
     *
     * ⭐ THE ORDER IS ASKED FIRST AND IT IS THE AUTHORITATIVE SIGNAL.
     *    `bhp_printed_for_you_order_is_visit()` reads the order's own shipping line
     *    (and, second, its meta flag) through the plugin's
     *    `bhp_school_pickup_order_is_pickup()` — a permanent record of what was
     *    actually bought. The session read is a FALLBACK ONLY, for a request where no
     *    order object resolves; it can never contradict the order, because it is only
     *    reached when there is no order answer to contradict.
     *
     * ⛔ FAILS OPEN both ways: no plugin, no order, or an ordinary shipped order all
     *    render the notice exactly as 1.19.237 did.
     */
    if (bhp_printed_for_you_order_is_visit($order_id) || bhp_printed_for_you_is_visit_session()) {
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
     * ⭐⭐ CYCLE164-LD-COPY-GATE-PASS-2, THE CART-PAGE GATE — and it is FIRST in this
     *    function deliberately, above the B5 checkout short-circuit, because it is the
     *    broader condition. This shortcode is the only mechanism that reaches the Cart
     *    page (the classic `woocommerce_before_cart` hooks do not fire on a Blocks
     *    cart), so gating here gates the cart.
     *
     * ⛔ THE DEFECT THIS CLOSES. `/cart/` is ONE CLICK BEFORE the checkout that
     *    1.19.237 already cleaned, and a visit-flagged walk was OBSERVED rendering the
     *    print-on-demand notice there on every school, at both viewports
     *    (`pfyOnCartPage: true`, pass-1 evidence). The parent therefore met the
     *    "printed especially for you … production and delivery times can vary" promise
     *    BEFORE reaching the screen where it had been removed. Same defect, earlier
     *    screen. Andrew rejected exactly this confusion on 2026-08-17.
     *
     * ⭐ RETURNING '' IS THE CORRECT SUPPRESSION HERE, and it is not the same act as
     *    the render_block filter below returning its input unchanged. There, the string
     *    carries WooCommerce's ENTIRE checkout and emptying it would destroy the page.
     *    Here, the shortcode's whole output IS the notice — '' is precisely "this
     *    component contributed nothing", which is what the B5 branch immediately below
     *    has already returned on the checkout page since 2026-08-03.
     *
     * ⛔ CONTROL PATH: an ordinary shopper's predicate is FALSE, this branch is not
     *    taken, and the cart renders byte-identically to 1.19.237.
     */
    if (bhp_printed_for_you_is_visit_session()) {
        return '';
    }

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

    /*
     * ⭐ CYCLE164-CX-01, first half, PRESERVED AS HISTORY. On the CHECKOUT page this
     *    shortcode already returns '' unconditionally above (B5), so the visit gate
     *    still has nothing to do for checkout here. The gate that suppresses the
     *    notice on a flagged CHECKOUT remains
     *    bhp_checkout_printed_for_you_after_actions() below, which is the one place
     *    the notice reaches a checkout screen.
     *
     * ⛔ THE SCOPE NOTE THAT STOOD HERE IN 1.19.237 IS NOW CLOSED, and is recorded
     *   rather than deleted so the movement stays visible. It read: "the CART page and
     *   the PRODUCT page still render this notice to a flagged visitor, and so does the
     *   order-received page. That is OUT OF SCOPE for CYCLE164-CX-01, which is
     *   checkout-only". All three are gated as of 1.19.238 — the cart by the guard at
     *   the top of this function, the product page in
     *   bhp_woocommerce_product_printed_for_you_section(), and the order-received page
     *   in bhp_woocommerce_thankyou_printed_for_you_notice(). It was in fact exactly
     *   "one call to bhp_printed_for_you_is_visit_session() at the top of this
     *   function", as that note predicted.
     */

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
    /*
     * ⭐⭐ CYCLE164-CX-01, THE GATE. A school-visit parent is not waiting for a
     *    printer and must not be told they are. Returning the block content
     *    UNCHANGED (not an empty string, not a modified string) is what keeps
     *    this a pure suppression: on a flagged session the checkout renders
     *    exactly as WooCommerce built it, with nothing appended.
     *
     * ⛔ For every other shopper `bhp_printed_for_you_is_visit_session()` is
     *    false, this branch is not taken, and the notice is appended byte-for-byte
     *    as it was in 1.19.236.
     */
    if (bhp_printed_for_you_is_visit_session()) {
        return $block_content;
    }
    $notice = bhp_render_printed_for_you_notice('checkout');
    if ('' === trim($notice)) {
        return $block_content;
    }
    return $block_content . '<div class="bhp-checkout-after-actions">' . $notice . '</div>';
}
add_filter('render_block', 'bhp_checkout_printed_for_you_after_actions', 10, 2);
