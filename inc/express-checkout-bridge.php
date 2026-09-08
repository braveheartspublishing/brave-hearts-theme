<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE EXPRESS CHECKOUT BRIDGE — why no wallet button has ever rendered
 *      on a book product page, and the smallest thing that fixes it.
 *      Theme 1.19.389, 2026-09-06, `CYCLE179-LD-BUILD-389`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * THE DEFECT, DIAGNOSED AT SOURCE THIS BUILD RATHER THAN INFERRED.
 *
 * `connected-operator` observed on 2026-09-06 that the production product page
 * loads `build/express-checkout.js`, defines `window.Stripe`, and publishes
 * `wc_stripe_express_checkout_params` with `is_express_checkout_enabled: true`,
 * `is_product_page: "1"` and a correctly priced `product.displayItems` — and
 * yet `#wc-stripe-express-checkout-element` is **absent from the DOM**. That
 * desk recorded a hypothesis and labelled it a hypothesis
 * (`ANDREW-REVIEW\2026-09-06\STRIPE-EXPRESS-READ-0906.md` §3, §9 item 4).
 *
 * ⭐ THE HYPOTHESIS IS CORRECT, AND HERE IS THE PROOF, READ OUT OF THE PLUGIN
 *    ON THE STAGING SERVER THIS BUILD:
 *
 *      `includes/payment-methods/class-wc-stripe-express-checkout-element.php`
 *      line 111:
 *          add_action( 'woocommerce_after_add_to_cart_form',
 *                      [ $this, 'display_express_checkout_button_html' ], 1 );
 *
 *      `display_express_checkout_button_html()` (line 798) is the ONLY thing
 *      that prints `<div id="wc-stripe-express-checkout-element">`.
 *
 *    And `bhp_book_remove_default_add_to_cart()` (`inc/book-formats.php`)
 *    removes `woocommerce_template_single_add_to_cart` on every canonical book
 *    page, because this theme's purchase block is a format rail plus an
 *    ANCHOR, not a `form.cart`. **No form, no `woocommerce_after_add_to_cart_
 *    form`, no container, no button.** The feature was never broken; it was
 *    never invited.
 *
 * ⭐ VERIFIED, NOT ASSUMED, THAT FIRING THE HOOK IS SAFE: the hook's live
 *    callback list was dumped from `$wp_filter` on staging this build and it
 *    has **exactly one subscriber** —
 *    `WC_Stripe_Express_Checkout_Element->display_express_checkout_button_html`
 *    at priority 1. Nothing else on this site listens to it, so firing it does
 *    one thing and one thing only.
 *
 * WHAT THIS FILE DOES, IN TWO PARTS.
 *
 *   1. A **scaffold** `form.cart`, visually hidden, carrying the two controls
 *      the plugin's own JS reads on a product page: `input.qty` (quantity) and
 *      `button.single_add_to_cart_button` whose `value` is the id to buy. Read
 *      out of `build/express-checkout.js` this build, not guessed:
 *          let e = t(".single_add_to_cart_button").val(), ...
 *          const o = { qty: t(<qty selector>).val() };
 *          const a = t("form.cart").serializeArray();
 *      ⛔ The scaffold is NOT a second buy button. It is `aria-hidden`,
 *         `tabindex="-1"` and inside a `display:none` wrapper, so it is
 *         unreachable by pointer, keyboard and screen reader. jQuery `.val()`
 *         and `serializeArray()` both read hidden controls, which is precisely
 *         why a scaffold works where a visible duplicate would be a defect.
 *
 *   2. `do_action('woocommerce_after_add_to_cart_form')`, fired INSIDE a
 *      visible wrapper so the container the plugin prints can be un-hidden by
 *      its own JS when a wallet is available.
 *
 * ⛔ WHAT IT DOES NOT DO, stated so the boundary is legible:
 *      · it does not add, remove or re-order a format card
 *      · it does not touch the existing add-to-cart ANCHOR, its href, its
 *        label, its `data-bhp-cart-add` hook or the drawer that intercepts it
 *      · it does not restore `woocommerce_template_single_add_to_cart`
 *      · it does not enable, disable or configure any payment method, any
 *        wallet, any Stripe setting or any domain registration — all of those
 *        are Andrew's gates and none was crossed
 *      · it prints nothing at all when the Stripe express element class is
 *        absent, so a site without the plugin is byte-identical to 1.19.388
 *
 * ⚠ WHETHER A BUTTON ACTUALLY PAINTS IS A BROWSER-AND-ACCOUNT QUESTION, NOT A
 *   THEME QUESTION. Stripe only mounts a wallet the visitor's browser can
 *   actually offer, on a domain registered as a payment method domain. This
 *   file makes the container exist; it cannot make a wallet exist. The render
 *   evidence is in the build report, from a real browser, at a stated
 *   `window.innerWidth`.
 */

defined('ABSPATH') || exit;

/**
 * True when this request is a single-product page whose native add-to-cart form
 * this theme has removed.
 *
 * ⭐ THE TEST IS THE ABSENCE OF THE HOOK, NOT A LIST OF PRODUCT IDS. Two
 *    subsystems remove that action — `bhp_book_remove_default_add_to_cart()`
 *    for the six canonical editions and `inc/colouring-line.php` for the
 *    colouring line — and a future third would be covered automatically.
 *    Asking the hook is also self-correcting: if the native form is ever
 *    restored, this bridge stops rendering in the same request rather than
 *    printing a second container beside the plugin's own.
 *
 * @return bool
 */
function bhp_express_bridge_should_render() {
    if (is_admin() || !function_exists('is_product') || !is_product()) {
        return false;
    }

    // The plugin is what prints the container. No plugin, nothing to bridge.
    if (!class_exists('WC_Stripe_Express_Checkout_Element')) {
        return false;
    }

    // The native form is still on the page: WooCommerce will fire the hook
    // itself, and firing it twice would print two containers.
    if (has_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart')) {
        return false;
    }

    $product = function_exists('wc_get_product') ? wc_get_product(get_queried_object_id()) : null;
    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
        return false;
    }

    return true;
}

/**
 * The id the wallet should buy for the format the page currently shows.
 *
 * ⭐ ONE SOURCE, NOT TWO. It is read from the same `bhp_book_purchase_data()`
 *    payload the format rail renders from, resolved through the same
 *    `bhp_book_incoming_format()` the rail uses for its initial card, so the
 *    scaffold and the visible CTA cannot disagree at first paint. After that,
 *    `assets/js/express-checkout-bridge.js` mirrors the CTA's own
 *    `data-product-id` / `data-variation-id` on every format change, so they
 *    cannot disagree later either.
 *
 * @return int 0 when it cannot be resolved.
 */
function bhp_express_bridge_initial_id() {
    $product_id = get_queried_object_id();

    if (function_exists('bhp_book_lookup_product') && function_exists('bhp_book_purchase_data')) {
        $found = bhp_book_lookup_product($product_id);
        if ($found && !empty($found['canonical'])) {
            $data = bhp_book_purchase_data($found['key']);
            $fmt  = function_exists('bhp_book_incoming_format') ? bhp_book_incoming_format() : 'paperback';
            if (!in_array($fmt, ['paperback', 'hardcover'], true)) {
                $fmt = 'paperback';
            }
            if ($data && isset($data[$fmt])) {
                $variation = (int) $data[$fmt]['variation_id'];
                $parent    = (int) $data[$fmt]['product_id'];
                return $variation > 0 ? $variation : $parent;
            }
        }
    }

    return (int) $product_id;
}

/**
 * Print the scaffold and fire the plugin's own hook.
 *
 * Priority 16: immediately after the format rail at 15, so the wallet sits in
 * the purchase block under the add-to-cart control rather than below the trust
 * row at 32.
 */
function bhp_express_bridge_render() {
    /*
     * ⭐ 1.19.393 (`CYCLE179-CX-BUILD-391-1`) — PRINT ONCE, HOWEVER REACHED.
     *
     * `template-parts/commerce/format-cards.php` now calls this function
     * DIRECTLY, from inside `.bhp-formats`, so that the wallet renders under
     * ADD TO CART instead of two thousand pixels below it. It also removes the
     * priority-16 hook before calling. This guard is the second lock: any
     * future template, hook or partial that reaches this function again gets
     * nothing, because two containers would be two wallets and the Stripe
     * plugin mounts into the FIRST `#wc-stripe-express-checkout-element` it
     * finds.
     *
     * ⚠ `$printed` IS SET AFTER THE TWO EARLY RETURNS, DELIBERATELY. A page
     *    where the bridge legitimately declines to render (no Stripe plugin,
     *    product not purchasable, native form still present, no buy id) must
     *    not be permanently poisoned for a later, valid call.
     */
    static $printed = false;
    if ($printed) {
        return;
    }

    if (!bhp_express_bridge_should_render()) {
        return;
    }

    $buy_id = bhp_express_bridge_initial_id();
    if ($buy_id <= 0) {
        return;
    }

    $printed = true;
    ?>
    <div class="bhp-express" data-bhp-express>
      <?php
      /*
       * ⛔ THE SCAFFOLD. Hidden from every input modality; present only so the
       *    Stripe plugin's JS can read a product id and a quantity. It is NOT
       *    submitted by anything: the visible control is still the anchor.
       */
      ?>
      <div class="bhp-express__scaffold" aria-hidden="true">
        <form class="cart bhp-express__form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="quantity" class="qty" value="1">
          <button type="submit"
                  class="single_add_to_cart_button button alt bhp-express__ghost"
                  name="add-to-cart"
                  tabindex="-1"
                  data-bhp-express-buy
                  value="<?php echo esc_attr($buy_id); ?>"><?php esc_html_e('Add to cart', 'brave-hearts'); ?></button>
        </form>
      </div>
      <?php
      /*
       * ⭐ THE ONE LINE THAT ACTUALLY FIXES THE DEFECT. Verified this build to
       *    have exactly one subscriber on this site:
       *    WC_Stripe_Express_Checkout_Element->display_express_checkout_button_html
       *    at priority 1.
       */
      do_action('woocommerce_after_add_to_cart_form');
      ?>
    </div>
    <?php
}
add_action('woocommerce_single_product_summary', 'bhp_express_bridge_render', 16);

/**
 * The mirror script and its stylesheet. Loaded only where the bridge renders.
 */
function bhp_express_bridge_assets() {
    if (!bhp_express_bridge_should_render()) {
        return;
    }
    $ver = wp_get_theme()->get('Version');
    wp_enqueue_script(
        'bhp-express-checkout-bridge',
        get_stylesheet_directory_uri() . '/assets/js/express-checkout-bridge.js',
        [],
        $ver,
        true
    );
}
add_action('wp_enqueue_scripts', 'bhp_express_bridge_assets', 20);
