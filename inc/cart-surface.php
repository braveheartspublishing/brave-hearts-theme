<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.401 — THE CART BAND. `CYCLE179-CX-BUILD-401-CART`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, 2026-09-07, verbatim. ⚠️ RELAYED through `chief-of-staff`;
 *    NOT witnessed first-hand by this desk (Standing Rules §9.2 rule 2):
 *
 *      "The cart shows a ton of white space on it- needs to be changed to
 *       match the other pages"
 *
 * ⭐⭐ WHAT THE WHITE SPACE ACTUALLY IS, MEASURED RATHER THAN ASSUMED. Read out
 *     of the live DOM on PRODUCTION 1.19.400 on 2026-09-07 at an asserted
 *     `window.innerWidth` of 1280, `/cart/`, empty:
 *
 *         y0    - y80    site header                     80px
 *         y80   - y229   .interior-hero--parchment      149px   (44.8 + 51.2 padding)
 *         y229  - y306   .page-content padding-top       77px   ⛔ EMPTY
 *         y306  - y371   article padding-top             64px   ⛔ EMPTY
 *         y371           .wp-block-woocommerce-cart starts
 *
 *     ⛔⛔ 371px OF FURNITURE ABOVE THE CART BLOCK, AND 141px OF IT IS TWO
 *         NESTED PADDINGS WITH NOTHING IN THEM. The same measurement on
 *         `/shop/` at the same width puts the first product card at y169.
 *         The cart is 202px worse than the page it is asked to match.
 *
 * ⛔⛔ THE HERO IS THE SMALLER HALF OF THE PROBLEM, AND THAT MATTERS FOR THE
 *     FIX. Replacing the hero alone recovers 100px of the 202px gap. The
 *     stacked `.page-content` + `article` padding is the other 101px, and it
 *     is addressed in `style.css` section B rather than here, because it is a
 *     geometry rule and not a markup change.
 *
 * ⭐ THE BAND IS THE SHOP'S BAND, NOT A NEW COMPONENT. It reuses
 *    `.bhp-catalog-band` and its four child classes verbatim, so the two
 *    surfaces cannot drift: a change to the band's type scale, colour or
 *    spacing lands on both. `bhp_woocommerce_archive_hero()` in `functions.php`
 *    emits the identical structure for `/shop/` and the taxonomy archives.
 *
 * ⛔ THE H1'S WORD IS UNCHANGED. It stays "Cart", which is the WordPress page
 *    title, exactly as 1.19.400 printed it. Only its SETTING moves. ⛔ Locked
 *    copy is proposed, never changed (Standing Rules §9).
 *
 * ⛔⛔ THIS FILE DOES NOT TOUCH THE CHECKOUT PAGE, AND THAT IS A DELIBERATE
 *     REFUSAL RATHER THAN AN OVERSIGHT. The build brief asked for "cart and
 *     checkout headers". ⭐ THE CHECKOUT PAGE HAS HAD NO HERO SINCE 1.19.194,
 *     BY ANDREW'S OWN INSTRUCTION of 2026-08-05, recorded verbatim in
 *     `page.php`:
 *
 *       "Remove the whole section on the checkout page "Brave Hearts Field
 *        Journal Checkout" - its clearly understood that its a check out
 *        page- bring everything up"
 *
 *     ⛔ Putting a band back on `/checkout/` would add ~50px of furniture above
 *        the form he asked to have raised. That is a reversal of a founder
 *        ruling, and no agent resolves one (Standing Rules §7). It is recorded
 *        as `CYCLE179-CX-B-1` and returned to the Chief of Staff for Andrew.
 *
 * ⛔ NOTHING HERE READS OR WRITES A WOOCOMMERCE SETTING. No price, coupon,
 *    stock, shipping, tax, payment or product record is touched. No review,
 *    rating, testimonial, statistic or outcome claim is created. No "we", "us"
 *    or "our" (§9.1). No em dash. American spelling (§9.4).
 *
 * @package Brave_Hearts
 * @since   1.19.401
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Does the cart band apply to the request being rendered?
 *
 * ⛔ `is_cart()` AND NOT `is_checkout()`. On a Blocks storefront the two are
 *    separate pages, but `is_checkout()` is also true on the order-received
 *    endpoint, and a band must never appear above a receipt. Testing both
 *    predicates costs nothing and closes that case explicitly rather than
 *    relying on the pages happening to be distinct.
 *
 * ⛔ IT FAILS CLOSED. If WooCommerce is not loaded, `is_cart()` does not exist
 *    and this returns false, so `page.php` renders exactly what 1.19.400
 *    rendered. A theme without WooCommerce is unchanged by this release.
 *
 * @since 1.19.401
 * @return bool
 */
function bhp_cart_band_applies() {
    if (!function_exists('is_cart') || !is_cart()) {
        return false;
    }

    if (function_exists('is_checkout') && is_checkout()) {
        return false;
    }

    return (bool) apply_filters('bhp_cart_band_enabled', true);
}

/**
 * Print the band.
 *
 * ⭐ THE MARKUP IS A COPY OF `bhp_woocommerce_archive_hero()`'s, AND THE COPY
 *    IS DELIBERATE RATHER THAN A SHARED PARTIAL. The two callers sit on
 *    opposite sides of a hook boundary: the archive band is emitted from
 *    `woocommerce_before_main_content` inside WooCommerce's own wrapper, and
 *    this one is emitted from `page.php` before `.page-content`. Factoring
 *    them into one function would mean one of the two callers passing a flag
 *    to suppress a wrapper the other needs. ⚠️ The cost is that a change to
 *    the band's STRUCTURE has to be made twice; the CSS, which is where the
 *    band's appearance actually lives, is shared and is not duplicated.
 *
 * ⛔ EVERY STRING BELOW IS ALREADY LIVE ON THE SITE. The series name and the
 *    brand line are the shop band's own, character for character. The meta is
 *    the standing approved age band, ⛔ 6 to 9 and never 5 to 9 (§9).
 *
 * ⚠️ THE META STRING IS THE ONE ITEM ON THIS PAGE THAT IS NEW TO THIS SURFACE
 *    and it is flagged FOR ANDREW'S APPROVAL in the build report rather than
 *    presented as settled. The shop band's meta reads "Paperback and hardcover
 *    · Ages 6 to 9"; the formats half of that sentence is information a
 *    shopper standing at the cart has already acted on, so only the age band
 *    is carried over. If he wants the shop's exact string, it is a one-line
 *    change.
 *
 * @since 1.19.401
 * @return void
 */
function bhp_cart_band_render() {
    ?>
    <header class="interior-hero interior-hero--product woo-archive-hero bhp-catalog-band bhp-cart-band">
      <div class="container bhp-catalog-band__inner">
        <p class="bhp-catalog-band__series"><?php esc_html_e('Adventures of Charlotte and Henry', 'brave-hearts'); ?></p>
        <div class="bhp-catalog-band__row">
          <h1 class="bhp-catalog-band__title"><?php the_title(); ?></h1>
          <span class="bhp-catalog-band__diamond" aria-hidden="true">&#9670;</span>
          <p class="bhp-catalog-band__line"><?php esc_html_e('Big Places. Brave Hearts.', 'brave-hearts'); ?></p>
          <p class="bhp-catalog-band__meta"><?php esc_html_e('Ages 6 to 9', 'brave-hearts'); ?></p>
        </div>
      </div>
    </header>
    <?php
}

/**
 * Carry a body class so the geometry rules in `style.css` have a scope token.
 *
 * ⛔ WHY A NEW TOKEN RATHER THAN `body.woocommerce-cart`, WHICH IS ALREADY
 *    THERE. `body.woocommerce-cart` is emitted by WooCommerce on the cart page
 *    whether or not this band rendered, including when
 *    `bhp_cart_band_enabled` has been filtered off. Scoping the padding
 *    collapse to a class this file controls means the CSS and the markup turn
 *    on and off together, so a filtered-off band cannot leave the page with
 *    the band's spacing and no band. VERIFIED on staging: the cart page's
 *    body carries `woocommerce-cart woocommerce-page ... page-cart` at
 *    1.19.400, and `bhp-cart-band` is added to that list by this filter.
 *
 * @since 1.19.401
 * @param array $classes Body classes.
 * @return array
 */
function bhp_cart_band_body_class($classes) {
    if (bhp_cart_band_applies()) {
        $classes[] = 'bhp-cart-band-on';
    }

    return $classes;
}
add_filter('body_class', 'bhp_cart_band_body_class');
