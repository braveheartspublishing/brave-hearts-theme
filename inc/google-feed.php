<?php
/**
 * Brave Hearts — GOOGLE PRODUCT FEED ATTRIBUTES (theme 1.19.221).
 * CYCLE156-CX-01
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * All six book products are DISAPPROVED in Google Merchant Center with the
 * code `ebooks_policy_violation` — "Digital books or eBooks are not supported
 * and cannot be listed on Shopping". Google is classifying three physically
 * printed children's chapter books as ebooks.
 *
 * Read live from GLA's own cached issue table on production 2026-08-13:
 *
 *     SELECT * FROM ugc_gla_merchant_issues
 *     -> 6 rows, code `ebooks_policy_violation`, severity DISAPPROVED
 *
 * The audit that found it also found that the feed sends NO
 * `googleProductCategory`, NO `condition` and NO `brand` — the GLA
 * attribute-mapping-rules table is empty and the theme registers no GLA
 * filter at all. Google therefore has no merchant-supplied signal that these
 * are physical books and must auto-classify. Google's spec makes all three
 * optional for books; optional is not the same as advisable when
 * auto-classification is actively going wrong.
 *
 * This file supplies the three attributes. It is the cheapest possible
 * reinforcement of "this is a physical book" and it changes nothing a
 * customer sees.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO — read before extending it
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   - It does NOT change any WooCommerce product record. It is a read-time
 *     filter on the outgoing feed payload only. Product titles, descriptions,
 *     prices, categories, tags, stock and brand terms are untouched, and every
 *     one of those is an Andrew gate.
 *   - It does NOT touch price, shipping, tax or availability. The whole
 *     shipping question — the tiered $0.00–$4.99 customer rate that a
 *     per-item feed cannot express — is deliberately out of scope here.
 *   - It does NOT apply to the downloadable Activity Book, or to any product
 *     that is not one of the six approved book editions. A digital PDF
 *     entering the Google feed would be a genuine, merchant-caused ebooks
 *     policy violation — the class that escalates from product disapproval to
 *     account-level enforcement. The guard below is the whole point of the
 *     file being written as an allowlist rather than a catch-all.
 *   - It does NOT emit `aggregateRating`, `review` or any rating signal.
 *     Zero real reviews exist; the feed and the page schema are both clean and
 *     must stay clean.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHY AN ALLOWLIST KEYED ON bhp_book_registry()
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `bhp_book_lookup_product()` in `inc/book-formats.php` is already the
 * theme's single definition of "is this one of the six editions". Re-deriving
 * that here — by SKU, by category, by title match — would create a second
 * definition that drifts. It would also be wrong: the SKUs differ between
 * environments (production carries ISBN-13s, staging still carries the old
 * BHP-* codes), so a SKU test would silently match nothing on one of them.
 *
 * The registry lists PARENT product IDs. The feed sends VARIATIONS: Mariana's
 * paperback reaches Google as `gla_334`, the variation, never as the variable
 * parent 333, which GLA explicitly refuses to sync. So a variation must be
 * resolved to its parent before the lookup, which is what
 * bhp_google_feed_is_book() does and what a naive lookup would miss.
 *
 * @package Brave_Hearts
 */

defined('ABSPATH') || exit;

/**
 * Google's product taxonomy category for physical books.
 *
 * "Media > Books" is taxonomy ID 784. Google's product data specification
 * accepts either the numeric ID or the full string path; the string is used
 * here because it is legible in a payload dump during QA, which is the only
 * place a human will ever read it.
 */
const BHP_GOOGLE_FEED_CATEGORY = 'Media > Books';

/** Google `condition`. These are new printed books, not used or refurbished. */
const BHP_GOOGLE_FEED_CONDITION = 'new';

/**
 * Google `brand`.
 *
 * The `product_brand` taxonomy already carries this exact term name on the
 * products; GLA 3.9.0 does not map that taxonomy to the feed's `brand`
 * attribute, which is why it arrives empty. The string is written out rather
 * than read from the taxonomy so that a term rename — an Andrew-gated content
 * decision — cannot silently change what is transmitted to Google.
 */
const BHP_GOOGLE_FEED_BRAND = 'Brave Hearts Publishing';

/**
 * Is this WooCommerce product one of the six approved book editions?
 *
 * Resolves a variation to its parent first, because the feed sends variations.
 *
 * @param WC_Product|mixed $product
 * @return bool
 */
function bhp_google_feed_is_book($product) {
    if (!function_exists('bhp_book_lookup_product')) {
        return false;
    }
    if (!is_object($product) || !method_exists($product, 'get_id')) {
        return false;
    }

    $id = (int) $product->get_id();

    // A variation is never in the registry under its own ID — resolve upward.
    if (method_exists($product, 'get_parent_id')) {
        $parent = (int) $product->get_parent_id();
        if ($parent > 0) {
            $id = $parent;
        }
    }

    return bhp_book_lookup_product($id) !== null;
}

/**
 * Add googleProductCategory / condition / brand to the outgoing feed payload
 * for the six book products, and for nothing else.
 *
 * `woocommerce_gla_product_attribute_values` (GLA >= 1.4.0, present in both
 * 3.7.1 on staging and 3.9.0 on production) is applied last, in
 * WCProductAdapter::override_attributes(), and its return value is passed
 * straight to the Google client's mapTypes(). Array keys must therefore be
 * exact `\Google\Service\ShoppingContent\Product` property names — all three
 * used here were verified present in the vendored client on BOTH environments.
 *
 * Existing keys are never overwritten. If another callback — or a future GLA
 * attribute-mapping rule Andrew sets in the admin — has already supplied one
 * of these, that value wins. This filter fills gaps; it does not fight.
 *
 * @param array            $attributes Overridden attributes so far.
 * @param WC_Product|mixed $product    The WooCommerce product being adapted.
 * @return array
 */
function bhp_google_feed_attributes($attributes, $product = null) {
    if (!is_array($attributes)) {
        $attributes = [];
    }

    if (!bhp_google_feed_is_book($product)) {
        return $attributes;
    }

    $additions = [
        'googleProductCategory' => BHP_GOOGLE_FEED_CATEGORY,
        'condition'             => BHP_GOOGLE_FEED_CONDITION,
        'brand'                 => BHP_GOOGLE_FEED_BRAND,
    ];

    foreach ($additions as $key => $value) {
        if (!isset($attributes[$key]) || '' === $attributes[$key]) {
            $attributes[$key] = $value;
        }
    }

    return $attributes;
}
add_filter('woocommerce_gla_product_attribute_values', 'bhp_google_feed_attributes', 10, 2);
