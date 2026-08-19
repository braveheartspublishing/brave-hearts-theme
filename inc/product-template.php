<?php
/**
 * THE PRODUCT TEMPLATE — Direction 1, step 3.
 * ============================================================================
 *
 * Andrew Signore, 2026-08-19, ⛔ RELAYED through `chief-of-staff` in the
 * `CYCLE165-LD-DIRECTION1-STEP3-PRODUCT` brief; NOT witnessed first-hand by the
 * agent that wrote this file.
 *
 *   FD-521 — Direction 1, "Expedition field notes", built in the board's own
 *   order: (1) the mobile-header offer, (2) the blog post template, (3) THIS,
 *   the product template, (4) the homepage. Step 3 is this file, the stylesheet
 *   beside it, and the small edits it names below. Step 4 is not in scope.
 *
 *   Item 96(7) — a free-sample / lead CTA counts as PRIMARY under FD-479
 *   limb 3. That is what makes the header-offer suppression in
 *   `inc/header-offer.php` a requirement of this step rather than a nicety.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT THIS STEP CHANGES, AND WHERE
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. `assets/css/product-template.css` — the <=600px reorder. That file carries
 *    the measurements and the reasoning; they are not repeated here.
 * 2. This file — the drawn mark beside "Ages 6-9", and the stylesheet enqueue.
 * 3. `inc/header-offer.php` — single-product pages join the suppression list,
 *    because after this step the product page carries its own above-fold
 *    primary and two would be an FD-479 limb 3 failure.
 * 4. `inc/book-formats.php` — the shipping sentence loses "our" and gains the
 *    cost (standing rule §9.1).
 * 5. `template-parts/commerce/look-inside.php` + `assets/css/book-media.css` —
 *    the slide counter leaves the image, and the gallery thumbnails gain real
 *    alt text.
 * 6. `template-parts/reviews/review-form.php` — the five rating labels lose
 *    their em dashes.
 *
 * ⛔ WHAT IT DOES NOT DO. It changes no price, discount, shipping tier, coupon,
 *    tax, stock status, product record, variation, SKU or WooCommerce setting;
 *    it writes no review and edits no customer's words (§9.1a); it hides no
 *    copy; and it emits no price literal — every figure on the page still comes
 *    from WooCommerce and the bundle plugin at render time.
 *
 * VOICE: standing rule §9.1. No "we". No em dash in customer-facing copy.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The whole component behind one filter, default ON.
 *
 * Turning it off returns the product page to its 1.19.261 behaviour: the
 * stylesheet is not enqueued so every rule in it matches nothing, and the mark
 * is not rendered.
 *
 * @return bool
 */
function bhp_product_template_enabled() {
	return (bool) apply_filters( 'bhp_product_template_enabled', true );
}

/**
 * True on a single product page, when the template is enabled.
 *
 * ⛔ `is_product()` is a WooCommerce function and this is a theme file. The
 *    guard is not decoration: with WooCommerce deactivated the theme must lose
 *    a stylesheet, never take a fatal error.
 *
 * @return bool
 */
function bhp_product_template_active() {
	if ( ! bhp_product_template_enabled() ) {
		return false;
	}
	return function_exists( 'is_product' ) && is_product();
}

/**
 * The one drawn mark beside "Ages 6-9".
 *
 * ⭐ PROVENANCE, because Standing Rules §9 and FD-376 both bear on it. This is
 *    `icon-map.svg` from the EXISTING hand-authored line-art set built for the
 *    activity book — Business OS `WORKING-DRAFTS\design-creative\activity-book\
 *    line-art\icon-map.svg`, the same set the Direction 1 board draws from. The
 *    path data is copied byte for byte. **Nothing was generated, traced from a
 *    model, or drawn by this session.** The only edit is `stroke` moving from
 *    the source's literal `#071522` to `currentColor`, so the mark takes its
 *    colour from the stylesheet instead of pinning one.
 *
 * ⭐ WHY INLINE RATHER THAN A SHIPPED FILE. A second copy of the same artwork
 *    in `assets/img/` would be a second definition that can drift from the
 *    source set, and an `<img>` cannot inherit `currentColor`. One definition,
 *    one request saved, provenance recorded here.
 *
 * ⛔ DECORATIVE. `aria-hidden` and `focusable="false"`: the words "Ages 6-9"
 *    beside it already carry the meaning, and announcing a map twice would be
 *    noise. It is not an image and has no alt text to be missing.
 *
 * @return string Pre-escaped SVG markup.
 */
function bhp_product_ages_mark_html() {
	return '<svg class="bhp-product-value-prop__mark" viewBox="0 0 200 200"'
		. ' aria-hidden="true" focusable="false" fill="none" stroke="currentColor"'
		. ' stroke-width="10" stroke-linecap="round" stroke-linejoin="round">'
		. '<path d="M20,55 L70,35 L130,55 L180,35 L180,145 L130,165 L70,145 L20,165 Z"/>'
		. '<path d="M70,35 L70,145"/>'
		. '<path d="M130,55 L130,165"/>'
		. '<path d="M40,120 C62,92 88,122 112,92" stroke-dasharray="9 11"/>'
		. '<path d="M146,74 L162,90 M162,74 L146,90"/>'
		. '</svg>';
}

/**
 * The template's stylesheet, on single product pages only.
 *
 * Every other page gets no extra bytes.
 */
function bhp_product_template_enqueue() {
	if ( ! bhp_product_template_active() ) {
		return;
	}
	wp_enqueue_style(
		'bhp-product-template',
		get_template_directory_uri() . '/assets/css/product-template.css',
		array( 'bhp-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_product_template_enqueue' );
