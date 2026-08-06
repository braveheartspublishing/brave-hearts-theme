<?php
/**
 * Brave Hearts Bundle Pricing — the ACTIVITY BOOK checkbox order bump.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS IS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-04, verbatim (RELAYED through the Chief of Staff;
 * ⛔ NOT witnessed first-hand by the agent that wrote this file):
 *
 *   "Activity book approved for $5 upsell on all cart and checkout pages
 *    for any individual book, combo or collection."
 *
 *   "simple checkbox add-on"  ...  checkbox + tiny thumbnail + one-line
 *    benefit label.
 *
 *   PERMANENT and UNIVERSAL: all checkout pages, the cart page, and the
 *   cart drawer, regardless of cart contents.
 *
 * So: an always-present, always-honest, always-optional checkbox on three
 * surfaces. Ticking it adds the product; unticking it removes the product.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ NO DARK PATTERNS. THIS IS A CONSTRAINT ON THE CODE, NOT A SENTIMENT.
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - UNCHECKED BY DEFAULT, always. The checkbox reflects the cart; it
 *     never pre-selects, and nothing in this module ever adds the product
 *     without a real click. There is no "opt-out" state anywhere in it.
 *   - UNCHECKING REMOVES IT. A control that can only add is a trap. The
 *     remove path is the same click, reversed, on all three surfaces.
 *   - THE PRICE SHOWN IS WOOCOMMERCE'S OWN FORMATTED PRICE for the real
 *     product, read at render time. It cannot drift from what is charged,
 *     because it is not a second copy of the number.
 *   - EVERY FACTUAL CLAIM IN THE LABEL IS VERIFIED AGAINST THE ARTEFACT.
 *     See `bhp_bundle_addon_copy()`.
 *   - NO EM DASHES in customer-facing copy (the sitewide purge, commit
 *     3ef65be, applies to new copy too). Hyphens and middots only.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - It computes no price, discount, shipping amount, tax or total.
 *   - It changes NO WooCommerce setting, product, variation, price,
 *     coupon, stock, shipping, tax or payment record on any environment.
 *     It calls no `update_option()` and no `update_post_meta()`.
 *   - It creates no product. The product is a data record, created once
 *     under Andrew's approval, and this file only READS it by SKU.
 *   - It registers no block and adds no React dependency.
 *   - It touches no funnel storage key and no funnel analytics prefix.
 *     Its events are commerce events in their own `addon_upsell_*`
 *     namespace; the parent and teacher funnels are untouched and
 *     unreachable from here (`.claude/rules/funnels.md`).
 *
 * ✅ IT FAILS CLOSED. If the SKU does not resolve, or the product is not
 *    purchasable, `bhp_bundle_addon_data()` returns null, nothing is
 *    enqueued and no panel is drawn anywhere. That is the state of
 *    production until Andrew approves the live product, and it is a
 *    no-op rather than a broken control.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The live add-on product, or null.
 *
 * Resolved from the SKU allowlist in `bundle-data.php` — the SAME list
 * `bhp_bundle_cart_has_unrelated_items()` exempts. One list, one source of
 * truth: a product can never be offered by this module while still being
 * treated as an unrelated item by the shipping override, or vice versa.
 *
 * @return WC_Product|null
 */
function bhp_bundle_addon_product() {
	static $product = null;
	static $looked_up = false;
	if ( $looked_up ) {
		return $product;
	}
	$looked_up = true;

	$ids = bhp_bundle_addon_product_ids();
	if ( empty( $ids ) || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	$candidate = wc_get_product( $ids[0] );
	if ( ! $candidate || ! $candidate->is_purchasable() || ! $candidate->is_in_stock() ) {
		return null;
	}
	$product = $candidate;
	return $product;
}

/**
 * The module's customer-facing copy, in ONE place.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ EVERY CLAIM BELOW WAS VERIFIED AGAINST THE ACTUAL ARTEFACT BEFORE IT
 *    WAS WRITTEN. It is not repeated from a brief or a design report.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Artefact: `ACTIVITY-BOOK-v4.pdf`, md5 `14fb42404f717ef18b46c9611525457e`,
 * 9,652,166 bytes.
 *
 *   "26 pages"  — VERIFIED by counting `/Type /Page` objects in the PDF
 *                 itself (26) and by the page-tree `/Count` values, which
 *                 sum 8 + 8 + 8 + 2 = 26. Two independent reads of the
 *                 binary, not a number taken from a document about it.
 *
 *   "coloring, mazes and word searches"
 *               — VERIFIED against the artefact's own page inventory: 7
 *                 coloring pages, 2 mazes, 3 word searches, plus a
 *                 symmetry-drawing page, explorer profiles, together
 *                 activities and a 2-page answer key.
 *
 *   ⛔ "crossword" IS DELIBERATELY NOT CLAIMED. v3 had three crosswords;
 *      Andrew ordered them removed and v4 has none. The generic word
 *      "puzzles" was available and is avoided in favour of naming what is
 *      actually in the file.
 *
 *   ⛔ THE TITLE IS "The Adventure Activity Book", NOT "Ocean Activity
 *      Book". The pattern in the brief used "Ocean", but the artefact
 *      covers all three books — the Mariana Trench, Mount Everest AND the
 *      Amazon. Calling it an ocean book would be a false description of
 *      the product on the page that takes the money.
 *
 *   ⛔ NO price string is hardcoded here. The price comes from
 *      WooCommerce's own formatted price for the real product.
 *
 *   ⛔ NO reading age, no outcome claim, no parent/teacher reaction, no
 *      rating, no "best", no "loved by". Nothing on the never-invent list
 *      appears in any string in this file.
 */
function bhp_bundle_addon_copy() {
	return array(
		/*
		 * translators: %1$s: product title, %2$s: formatted price, e.g. $5.00
		 *
		 * ⚠ "Add %1$s", NOT "Add the %1$s". Corrected after rendering the
		 *   real string rather than reading the template: the product is
		 *   titled "The Adventure Activity Book", so the article in the
		 *   template produced, verbatim, "Add the The Adventure Activity
		 *   Book - $5.00". The brief's example pattern ("Add the Ocean
		 *   Activity Book") assumed a title without its own article; this
		 *   one has one.
		 */
		'label'     => __( 'Add %1$s - %2$s', 'bhp-bundle-pricing' ),
		'benefit'   => __( '26 pages of coloring, mazes and word searches · instant download', 'bhp-bundle-pricing' ),
		'adding'    => __( 'Adding...', 'bhp-bundle-pricing' ),
		'removing'  => __( 'Removing...', 'bhp-bundle-pricing' ),
		'added'     => __( 'Added to your order.', 'bhp-bundle-pricing' ),
		'removed'   => __( 'Removed from your order.', 'bhp-bundle-pricing' ),
		'failedAdd' => __( 'That could not be added. Please try again.', 'bhp-bundle-pricing' ),
		'failedDel' => __( 'That could not be removed. Please try again.', 'bhp-bundle-pricing' ),
		/* translators: %s: product title */
		'aria'      => __( 'Add %s to your order', 'bhp-bundle-pricing' ),
	);
}

/**
 * Everything the three front-end surfaces need, or null if there is no
 * offerable product.
 *
 * The thumbnail is a real, already-generated derivative, never the
 * full-size image scaled down in CSS. "tiny thumbnail" was Andrew's word
 * and a 9 MB source image shrunk with `width:44px` would satisfy the
 * letter of it and none of the intent.
 *
 * ⚠ THE SIZE IS `medium`, NOT `woocommerce_gallery_thumbnail`, AND THAT
 *   WAS A CORRECTION MADE FROM OBSERVED OUTPUT. The first build used the
 *   gallery thumbnail, which on this store is a HARD-CROPPED 100x100
 *   square. The cover is portrait (800x1035), so the square crop cut the
 *   title off the artwork. `medium` is 232x300 on this store, an uncropped
 *   0.773 aspect that matches the 44x57 box the CSS draws almost exactly.
 *   Checked against the real attachment's generated sizes rather than
 *   assumed from the size name.
 *
 * @return array|null
 */
function bhp_bundle_addon_data() {
	$product = bhp_bundle_addon_product();
	if ( ! $product ) {
		return null;
	}

	$thumb = '';
	$image_id = (int) $product->get_image_id();
	if ( $image_id ) {
		$src = wp_get_attachment_image_src( $image_id, 'medium' );
		if ( $src && ! empty( $src[0] ) ) {
			$thumb = $src[0];
		}
	}

	return array(
		'productId' => (int) $product->get_id(),
		'title'     => $product->get_name(),
		/*
		 * `wc_price( get_price() )` rather than `get_price_html()`: the
		 * html variant can carry a del/ins sale construction and a screen
		 * reader suffix, which inside a checkbox label reads as noise. The
		 * number is still WooCommerce's, for the real product.
		 *
		 * ⚠ `html_entity_decode()` IS LOAD-BEARING AND WAS ADDED AFTER
		 *   READING THE ACTUAL OUTPUT, not from reasoning about it.
		 *   `wc_price()` returns the currency symbol as the HTML entity
		 *   `&#36;`, and `wp_strip_all_tags()` removes TAGS but does not
		 *   decode ENTITIES. The front end sets this string with
		 *   `textContent` (deliberately, so a server string can never be
		 *   parsed as markup), and `textContent` does not decode entities
		 *   either. The label would therefore have read, literally, on the
		 *   page that takes the money:
		 *
		 *       Add the Adventure Activity Book - &#36;5.00
		 *
		 *   Decoding here is the correct fix: the string stays plain text
		 *   all the way to the DOM, and the XSS-safety of `textContent` is
		 *   untouched because the value never becomes markup.
		 */
		'price'     => html_entity_decode(
			wp_strip_all_tags( wc_price( (float) $product->get_price() ) ),
			ENT_QUOTES,
			'UTF-8'
		),
		'thumb'     => $thumb,
		'copy'      => bhp_bundle_addon_copy(),
		/*
		 * ═══════════════════════════════════════════════════════════════
		 * ⭐ 1.8.27 — THE FREE-WITH-COLLECTION STATE. CYCLE144-LD-220.
		 * ═══════════════════════════════════════════════════════════════
		 *
		 * ⛔ TWO SETS OF COPY ARE SHIPPED, AND THE SERVER DOES NOT PICK.
		 *    It cannot: this data is localized once at page load, and on a
		 *    Blocks cart the customer can complete their collection three
		 *    seconds later without a page load. A server-chosen label would
		 *    then be stale on the one screen that takes the money — reading
		 *    "$5.00" beside a line WooCommerce is charging $0.00 for.
		 *
		 * ⭐ THE SCRIPT DECIDES, FROM THE LIVE CART, on every render, using
		 *    `window.bhpBundleCrossSell.compute()` — the drawer's own
		 *    exported evaluation, which is the JS mirror of
		 *    `bhp_bundle_evaluate_cart()`. One predicate, both languages.
		 *
		 * `freeNow` is the state at page load only, and is used for nothing
		 * except the first paint before the cart store resolves.
		 */
		'freeEnabled' => function_exists( 'bhp_bundle_addon_free_enabled' ) && bhp_bundle_addon_free_enabled(),
		'freeNow'     => function_exists( 'bhp_bundle_cart_earns_free_addon' )
			&& function_exists( 'WC' ) && WC()->cart
			&& bhp_bundle_cart_earns_free_addon( WC()->cart ),
		'freeCopy'    => function_exists( 'bhp_bundle_addon_free_copy' ) ? bhp_bundle_addon_free_copy() : array(),
	);
}

/**
 * Enqueue SITEWIDE, deliberately, with one exclusion.
 *
 * ⭐ WHY SITEWIDE AND NOT "cart and checkout only". The third surface is
 *    the cart DRAWER, which `bundle-drawer.php` renders in `wp_footer` on
 *    every page of the site. The drawer's own script calls into this
 *    module's exported panel builder so the three surfaces cannot drift
 *    into three different checkboxes. Scoping this to the cart and
 *    checkout pages would leave the drawer without it everywhere else —
 *    which is exactly the "regardless of cart contents, permanent and
 *    universal" requirement, failed.
 *
 * ⛔ `is_order_received_page()` IS EXCLUDED and it is not defensive
 *    padding. `is_checkout()` returns true on the thank-you page, because
 *    order-received is a checkout endpoint. Without this, the module would
 *    offer an add-on to somebody who has ALREADY PAID, and ticking it
 *    would modify a cart that no longer has anything to do with their
 *    completed order. That is the single worst failure this file could
 *    have, so it is guarded at the enqueue and again in the script.
 */
function bhp_bundle_addon_assets() {
	if ( is_admin() || ! function_exists( 'WC' ) ) {
		return;
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return;
	}

	$data = bhp_bundle_addon_data();
	if ( ! $data ) {
		return; // Fails closed: no product, no assets, no panel.
	}

	wp_enqueue_style(
		'bhp-addon-upsell',
		BHP_BUNDLE_PRICING_URL . 'assets/addon-upsell.css',
		array( 'bhp-cart-drawer' ),
		BHP_BUNDLE_PRICING_VERSION
	);
	wp_enqueue_script(
		'bhp-addon-upsell',
		BHP_BUNDLE_PRICING_URL . 'assets/addon-upsell.js',
		array( 'bhp-cart-drawer' ),
		BHP_BUNDLE_PRICING_VERSION,
		true
	);
	wp_localize_script( 'bhp-addon-upsell', 'bhpAddonUpsellData', $data );
}
add_action( 'wp_enqueue_scripts', 'bhp_bundle_addon_assets', 20 );
