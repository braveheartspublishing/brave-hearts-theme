<?php
/**
 * Brave Hearts — cart and checkout experience corrections.
 *
 * Capstone fix wave, 2026-08-03. One file, because every item in it is the
 * same surface (the WooCommerce Blocks cart and checkout) and splitting them
 * across five files would hide the fact that they interact.
 *
 * ⛔ WHAT THIS FILE DELIBERATELY DOES NOT DO — read before extending it.
 *
 *    It changes NO WooCommerce setting, on any environment. Nothing here calls
 *    `update_option()`, `WC_Admin_Settings`, or writes to any product,
 *    variation, price, coupon, stock, shipping, tax or payment record. Where a
 *    behaviour is genuinely controlled by a stored option (the required-phone
 *    field), it is changed by filtering the option AS IT IS READ, at runtime,
 *    on the front end only. `wp option get woocommerce_checkout_phone_field`
 *    still returns `required` afterwards, and deleting one filter restores the
 *    previous behaviour exactly.
 *
 *    Everything else is presentation: CSS, one small enhancement script, and
 *    two server-side render filters.
 *
 * Items implemented here, with their punch-list IDs:
 *   D3  contiguous-US disclosure + a friendly AK/HI message
 *   D4  phone optional at checkout — ⛔ REVERSED 2026-08-03, see P2-4 below.
 *       The filter is gone; phone is REQUIRED again.
 *   D7  tax renders only after an address has actually been entered
 *   F3  centred checkout button label · coupon in its own visible row with a
 *       centred label · exactly ONE order summary on mobile checkout
 *   F8  cart line-item thumbnails stop cropping covers into squares
 *   F9  tap targets on the cart quantity, remove and coupon controls -> 44px
 *   F10 `shipping_postcode` gets `inputmode="numeric"`
 *   F11 empty-cart state
 *   F12 the two overlapping marketing opt-ins become one, and the
 *       teacher-funnel offer leaves the parent purchase path
 *   2C-1 the 44px quantity buttons stop overflowing their own box on /cart/
 *        (CSS only — see assets/css/checkout-experience.css)
 *   2C-4 per-line-item REMOVE control in the Blocks checkout order summary,
 *        plus a branded empty state when the last item goes
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

/* =====================================================================
 * D3 — CONTIGUOUS-US DISCLOSURE AND THE ALASKA / HAWAII MESSAGE
 * =====================================================================
 *
 * The defect (CYCLE142-CX-006, verified live): Alaska and Hawaii are
 * selectable in the state list, no shipping option resolves for them, the
 * order total silently drops to the goods price, the Place Order button stays
 * ENABLED, and the only message a customer sees is WooCommerce's generic
 * "Please verify the address is correct or try a different address" — which
 * invites someone to enter a false address rather than telling them the truth.
 *
 * ⭐ THE PLACEHOLDERS ARE GONE — 2026-08-03, supplement build.
 *    The two strings below are now the APPROVED wording (Andrew, relayed
 *    through `chief-of-staff`; NOT witnessed first-hand by this agent).
 *    Source deck: Business OS `WORKING-DRAFTS\commerce-cx\
 *    DRAFT-2026-08-03-HC-COPY-AND-SHIPPING-MESSAGES.md` §3.1 option A1 and
 *    §3.2 option B1.
 *
 *    The superseded placeholder wording is recorded here rather than deleted,
 *    so a reader can see what changed:
 *      scope:    "We currently ship to the contiguous United States only."
 *      offshore: "We are not able to ship to Alaska or Hawaii yet. Both books
 *                 are also available on Amazon, which does deliver there."
 *    ⚠️ The old offshore string carried an unverified claim — that Amazon
 *       delivers to Alaska and Hawaii. Nobody checked it. B1 deliberately
 *       says only that our books are available on Amazon, which is true and
 *       verifiable, and makes no delivery claim about a third party.
 *
 *    ⛔ THE ONE SENTENCE THAT MUST NOT BE CUT, per the approved deck:
 *       "Nothing is wrong with the address you entered. This is a limit on
 *       our side, and we are sorry." It is the sentence that stops the stock
 *       WooCommerce behaviour reading as "your address is wrong".
 */
function bhp_checkout_shipping_scope_notice() {
    return __('We currently ship within the contiguous United States.', 'brave-hearts');
}

/**
 * The AK/HI message, as STRUCTURE rather than one string.
 *
 * B1 is three paragraphs and contains a real email address that has to be a
 * working `mailto:` link, not typed-out text a customer has to retype on a
 * phone. Returning parts keeps every word in this one function while letting
 * the enhancement script build a link without ever assembling HTML from a
 * server string.
 */
function bhp_checkout_offshore_state_notice() {
    return [
        'heading' => __('We cannot ship to this address yet.', 'brave-hearts'),
        'body'    => __('Right now we can only ship within the contiguous United States, so Alaska and Hawaii are outside our delivery area. Nothing is wrong with the address you entered. This is a limit on our side, and we are sorry.', 'brave-hearts'),
        'helpBefore' => __('We would still love to get these books to your reader. Email us at ', 'brave-hearts'),
        'email'      => 'andrew@braveheartspublishing.com',
        'helpAfter'  => __(' and we will help, and we will let you know as soon as we can ship to you. Our books are also available on Amazon in the meantime.', 'brave-hearts'),
    ];
}

/* =====================================================================
 * ⛔ P2-4 (2026-08-03) — D4 IS REVERSED. THE PHONE FIELD IS REQUIRED AGAIN.
 * =====================================================================
 *
 * Andrew's ruling, relayed through the Chief of Staff and not witnessed by
 * this agent: the checkout phone field is REQUIRED again. His stated basis is
 * a founder verification that the phone number propagates to Bookvault orders.
 *
 * ⭐ WHY THE REVERSAL IS THE RIGHT CALL ON THE EVIDENCE, NOT ONLY ON AUTHORITY.
 *    D4's own code-inspection finding (preserved verbatim below) established
 *    exactly one thing: that nothing in the code WE control throws on an empty
 *    `billing.phone`. It explicitly did NOT establish what Bookvault's remote
 *    endpoint does with one, and it said so. Andrew's verification supplies the
 *    missing half of that question from the only place it was ever obtainable —
 *    a real order — and it points the other way. **The honest limit recorded in
 *    D4 is the reason this reversal is cheap: nobody ever claimed the optional
 *    phone was proven safe downstream.**
 *
 * WHAT THIS REVERSAL IS, MECHANICALLY: the removal of one `add_filter` line.
 * The D4 record below is kept in full and deliberately NOT deleted — it is the
 * only place the Bookvault plugin inspection is written down, it remains true
 * as a statement about the plugin's code, and a future session that deletes it
 * would re-derive it from scratch.
 *
 * ✅ THERE IS NO SECOND HALF, AND THAT WAS CHECKED RATHER THAN ASSUMED.
 *    Removing this filter is the ENTIRE reversal. No stored option has to be
 *    written back on any environment, and no WP-CLI step rides the production
 *    push list for it.
 *
 *    Verified after redeploying this file with the filter gone, so the read
 *    returns the true stored value rather than the filtered one:
 *      staging     `woocommerce_checkout_phone_field` = required
 *      production  `woocommerce_checkout_phone_field` = required  (read-only)
 *
 *    ⚠️ AND THAT IS EXACTLY WHY THE FILTER WAS THE RIGHT SHAPE FOR D4. While
 *    it was live, `wp option get woocommerce_checkout_phone_field` returned
 *    `optional` — because WP-CLI reads options through the `option_*` filters
 *    too. A reader could reasonably have concluded the setting itself had been
 *    changed; this build did, briefly, until the post-deploy read disproved
 *    it. D4's own header made the correct claim all along: it changed no
 *    WooCommerce setting on any environment. It didn't, and the reversal
 *    therefore costs one deleted filter and nothing else.
 *
 * ── D4's ORIGINAL RECORD, PRESERVED. The filter it describes is REMOVED. ──
 *
 * D4 — PHONE OPTIONAL AT CHECKOUT  [SUPERSEDED 2026-08-03 by P2-4 above]
 *
 * WooCommerce Blocks reads the required-ness of the phone field from
 * `get_option('woocommerce_checkout_phone_field')`, through
 * `CartCheckoutUtils::get_phone_field_visibility()`
 * (wp-content/plugins/woocommerce/src/Blocks/Utils/CartCheckoutUtils.php:289),
 * which is consumed by `CheckoutFields.php:792`. Filtering the option at read
 * time therefore changes both the rendered field AND the server-side
 * validation consistently — there is no window in which the browser thinks it
 * is optional and the Store API thinks it is required.
 *
 * ⭐ BOOKVAULT VERDICT, from code inspection of the installed plugin
 *    (bookvault 6.0.0, 616 lines total, read on staging 2026-08-03):
 *
 *    - The word "phone" does not appear ANYWHERE in the plugin. Not in a
 *      payload builder, not in a validator, not in a field map.
 *    - It has no automatic order-create hook at all. Orders reach Bookvault
 *      through WooCommerce's own `order.created` webhook to
 *      webhooks.bookvault.app; the plugin's only order-sending function,
 *      `bvlt_resendOrder()`, is a manual admin re-send that posts
 *      `$order->get_data()` WHOLESALE, JSON-encoded, with no field selection.
 *    - `$order->get_data()` always emits `billing.phone` as a key. An
 *      unsupplied phone makes it an empty string; it does not remove the key.
 *      The payload SHAPE is therefore identical before and after this change.
 *
 *    ⛔ HONEST LIMIT, stated rather than smoothed over: what Bookvault's
 *       REMOTE endpoint does with an empty `billing.phone` is not observable
 *       from this side, and proving it would require placing a real order.
 *       No such test was run and none is claimed. The finding is that nothing
 *       in the code we control can throw on an empty phone — not that
 *       Bookvault's API has been proven to accept one.
 *
 * Rollback is one line: delete this filter.   ← DONE, 2026-08-03. See P2-4.
 *
 * ⛔ THE FUNCTION AND ITS `add_filter` ARE DELETED, NOT COMMENTED OUT AND NOT
 *    LEFT REGISTERED BEHIND A FLAG. A dormant filter that looks like a feature
 *    is how a reversal quietly un-reverses itself six weeks later. To restore
 *    the optional-phone behaviour, re-add a filter on
 *    `option_woocommerce_checkout_phone_field` returning 'optional' — and get
 *    Andrew's ruling first, because this one is his.
 */

/* =====================================================================
 * D7 — TAX ONLY AFTER AN ADDRESS HAS BEEN ENTERED
 * =====================================================================
 *
 * The defect (CYCLE142-LD-13, verified live): before a customer types
 * anything, the mobile checkout shows a totals line reading
 * "Idaho Sales Tax $0.72". That is WooCommerce behaving exactly as configured
 * — `woocommerce_default_customer_address` is `base`, so the customer object
 * starts life at the store's own address — but a Colorado buyer's first sight
 * of their total is an Idaho tax figure, and the number then jumps when they
 * enter their real address (which is also half of CX-011's "+$2.94 surprise").
 *
 * WHY THIS IS A THEME FILTER AND NOT A SETTING CHANGE. The obvious fix is to
 * set `woocommerce_default_customer_address` to "no address". That is a stored
 * WooCommerce setting and therefore an Andrew gate, and it would also change
 * shipping-rate defaults, not just tax. `woocommerce_customer_taxable_address`
 * is a runtime filter that changes ONLY which address tax is calculated
 * against, leaves shipping alone, and disappears the moment this file does.
 *
 * THE TEST FOR "HAS ENTERED AN ADDRESS" IS THE POSTCODE, DELIBERATELY.
 * WooCommerce's base-address default populates country and state but NEVER a
 * postcode, so a non-empty postcode is a reliable signal that the value came
 * from the customer rather than from the store's own address. Country/state
 * would both be false positives.
 *
 * Returning an empty country makes `WC_Tax::find_rates()` match nothing, so no
 * tax row is emitted at all — the line does not render as "$0.00", it is
 * absent, which is the honest representation of "not known yet".
 */
function bhp_defer_tax_until_address_entered($address) {
    if (is_admin() && !wp_doing_ajax() && !defined('REST_REQUEST')) {
        return $address;
    }
    if (!function_exists('WC') || !WC()->customer) {
        return $address;
    }
    $customer = WC()->customer;
    $postcode = '';
    if ('billing' === get_option('woocommerce_tax_based_on')) {
        $postcode = (string) $customer->get_billing_postcode();
    } else {
        $postcode = (string) $customer->get_shipping_postcode();
        if ('' === trim($postcode)) {
            $postcode = (string) $customer->get_billing_postcode();
        }
    }
    if ('' !== trim($postcode)) {
        return $address; // A real address exists. Tax as normal.
    }
    // country, state, postcode, city — all blank. No rate can match.
    return ['', '', '', ''];
}
add_filter('woocommerce_customer_taxable_address', 'bhp_defer_tax_until_address_entered', 20);

/* =====================================================================
 * F12 — ONE MARKETING OPT-IN, AND NO TEACHER OFFER IN THE PARENT PATH
 * =====================================================================
 *
 * Before (verified live at checkout, both boxes rendered 60px apart):
 *   - "Send me announcements when a new Charlotte and Henry book, edition, or
 *      bundle is released."
 *   - "Send me new book announcements, free teacher guides, family activities,
 *      outdoor education ideas, Expedition Guides, and occasional Brave Hearts
 *      Publishing news."
 * Both promise new-book announcements, so a customer who wants that has to
 * decide which of two boxes means it. The second also puts a TEACHER-funnel
 * offer ("free teacher guides", "Expedition Guides") into a parent's purchase
 * path, which the funnel-isolation rule exists to prevent.
 *
 * After: ONE box. `brave-hearts/new-book-releases` is kept as the surviving
 * field id ON PURPOSE — it is the id already written into existing orders'
 * meta, so historical consent records keep resolving. The
 * `explorer-updates` field is no longer REGISTERED, which means it stops
 * rendering; its `_bhp_explorer_updates_optin` meta on orders already placed
 * is untouched and still readable.
 *
 * Implemented as a filter on the definition list rather than by deleting the
 * entry, so the previous state is one filter-removal away and the definition
 * itself stays in `functions.php` as the record of what existed.
 */
function bhp_merge_marketing_consent_fields($fields) {
    if (isset($fields['explorer_updates'])) {
        unset($fields['explorer_updates']);
    }
    if (isset($fields['new_book_releases'])) {
        $fields['new_book_releases']['label'] = __(
            'Email me when a new Charlotte and Henry book or edition is released, plus the occasional family reading idea.',
            'brave-hearts'
        );
    }
    return $fields;
}
add_filter('bhp_marketing_consent_field_definitions', 'bhp_merge_marketing_consent_fields');

/* =====================================================================
 * F8 (PARTIAL) — CART THUMBNAILS: THE BOX IS FIXED, THE FILE IS NOT
 * =====================================================================
 *
 * ⚠ READ THIS BEFORE RE-ATTEMPTING IT. Three approaches were built, deployed
 *   and MEASURED here, and all three failed. They are removed rather than left
 *   registered as dead code that looks like a fix, and recorded rather than
 *   silently dropped.
 *
 * THE DEFECT. Cart and checkout line-item images render from
 * `woocommerce_thumbnail`, which for these covers is a HARD-CROPPED 300x300
 * square cut out of a 1303x1999 portrait. The bottom of the illustration and
 * the author's name are not squeezed — they are absent from the FILE.
 *
 * WHAT IS FIXED, and it is verified live: the display box. It was 64x64 with
 * `object-fit: fill` against `aspect-ratio: 80/80`, which stretched the square
 * as well as cropping it. It is now a 2:3 portrait box with `object-fit:
 * contain` (assets/css/checkout-experience.css), so the image is no longer
 * distorted and sits in a book-shaped frame. Measured after: 40x60, `contain`,
 * `aspect-ratio: 2 / 3`.
 *
 * WHAT IS NOT FIXED: the served file is still `...-300x300.jpg`, so the part
 * of the cover the crop removed is still missing.
 *
 * THE THREE FAILED ATTEMPTS, with why:
 *   1. `wp_get_attachment_image_src` -> return the `woocommerce_single` array,
 *      gated on `REST_REQUEST` + a `/wc/store/` route. No effect: Blocks
 *      PRELOADS the first cart payload during the ordinary page render, not
 *      over REST.
 *   2. Same filter, gate widened to `is_cart() || is_checkout()`. No effect.
 *   3. Additionally dropping the square `srcset` via `wp_calculate_image_srcset`
 *      so the browser would fall back to `src`. No effect either — measured
 *      `currentSrc` unchanged at `-300x300.jpg` on both cart and checkout.
 *
 * THE REAL FIX IS A WOOCOMMERCE IMAGE SETTING, WHICH IS AN ANDREW GATE:
 * set the product-image cropping to "uncropped" and regenerate thumbnails, so
 * `woocommerce_thumbnail` stops being a square. That changes a stored setting
 * and rewrites derivative files on both environments, so it is escalated, not
 * performed here. Recorded for the punch list.
 */

/* =====================================================================
 * F11 — THE EMPTY CART
 * =====================================================================
 *
 * Before: a ~280px solid navy circle with two eyes, a tear and a frown — the
 * single largest element on the page — above "Your cart is currently empty!".
 * That is WooCommerce Blocks' stock illustration, and against a brand built on
 * a compass, Cormorant Garamond and expedition restraint it is tonally alien,
 * at the one point in the funnel where tone matters most.
 *
 * After: a compass mark, an invitation, and the two routes a visitor with an
 * empty cart actually wants.
 *
 * Rendered SERVER-SIDE by filtering the block's own output, so there is no
 * flash of the stock state and no JavaScript dependency. THE PAGE'S DATABASE
 * CONTENT IS NOT EDITED — the stock block still exists in `post_content` and
 * still renders below this; only its crying icon is suppressed, in CSS. Remove
 * this filter and the page returns to exactly what it was.
 *
 * NO NEW ARTWORK. The compass is an inline SVG built from strokes and a
 * four-point star, in brand tokens. Nothing was generated and nothing was
 * commissioned.
 */
/**
 * The empty-cart invitation's COPY, in one place.
 *
 * ⭐ 2C-4 (2026-08-03) — extracted from `bhp_empty_cart_invitation()` below so
 *    that the Blocks CHECKOUT's own empty state can show the same words when a
 *    customer removes their last line item from the order summary. Two
 *    surfaces, one string set, and no second copy to drift.
 *
 *    ⛔ NOT ONE WORD IS NEW. Every string here is the approved copy that has
 *       been rendering on /cart/ since the capstone wave (F11); this function
 *       only moves it. Nothing was written, softened or invented for the
 *       checkout surface.
 */
function bhp_empty_cart_copy() {
    return [
        'title'          => __('Your expedition pack is empty', 'brave-hearts'),
        'text'           => __('Three real places are waiting: the deepest ocean trench, the top of the world, and the green heart of the Amazon. Pick a starting point.', 'brave-hearts'),
        'primaryLabel'   => __('Get the Complete Collection', 'brave-hearts'),
        'primaryUrl'     => home_url('/complete-collection/'),
        'secondaryLabel' => __('Browse every book', 'brave-hearts'),
        'secondaryUrl'   => home_url('/books/'),
    ];
}

function bhp_empty_cart_invitation($block_content, $block) {
    if (empty($block['blockName']) || 'woocommerce/empty-cart-block' !== $block['blockName']) {
        return $block_content;
    }
    /*
     * ⚠ SCOPED TO AN ACTUALLY-EMPTY CART, and the markup goes INSIDE the
     *   block's own wrapper.
     *
     *   A first pass prepended it and was verified live rendering the
     *   invitation on a cart that HAD an item in it, at y=1508 — because
     *   WooCommerce Blocks always renders the empty-cart block and hides it
     *   client-side, so anything placed OUTSIDE that wrapper is not hidden
     *   with it. Two independent guards now: the server-side cart check
     *   below, and injection inside the wrapper so Blocks' own hiding
     *   applies to this panel exactly as it does to the stock one.
     */
    if (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
        return $block_content;
    }
    $copy = bhp_empty_cart_copy();
    ob_start();
    ?>
    <div class="bhp-empty-cart">
      <span class="bhp-empty-cart__mark" aria-hidden="true">
        <svg viewBox="0 0 64 64" focusable="false" aria-hidden="true">
          <circle cx="32" cy="32" r="27" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".55"/>
          <circle cx="32" cy="32" r="21" fill="none" stroke="currentColor" stroke-width="1" opacity=".3"/>
          <path d="M32 8v6M32 50v6M8 32h6M50 32h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity=".55"/>
          <path d="M32 15l4.4 12.6L49 32l-12.6 4.4L32 49l-4.4-12.6L15 32l12.6-4.4z" fill="currentColor"/>
        </svg>
      </span>
      <h2 class="bhp-empty-cart__title"><?php echo esc_html($copy['title']); ?></h2>
      <p class="bhp-empty-cart__text"><?php echo esc_html($copy['text']); ?></p>
      <div class="bhp-empty-cart__actions">
        <a class="btn btn-cta-primary bhp-empty-cart__cta" href="<?php echo esc_url($copy['primaryUrl']); ?>"><?php echo esc_html($copy['primaryLabel']); ?></a>
        <a class="btn bhp-empty-cart__cta bhp-empty-cart__cta--quiet" href="<?php echo esc_url($copy['secondaryUrl']); ?>"><?php echo esc_html($copy['secondaryLabel']); ?></a>
      </div>
    </div>
    <?php
    $panel = ob_get_clean();
    // Insert immediately after the block wrapper's opening tag.
    $cut = strpos($block_content, '>');
    if (false === $cut) {
        return $panel . $block_content;
    }
    return substr($block_content, 0, $cut + 1) . $panel . substr($block_content, $cut + 1);
}
add_filter('render_block', 'bhp_empty_cart_invitation', 10, 2);

/* =====================================================================
 * ⭐ 1.19.220 (2026-08-12) — THE THREE FREE BULLETS REACH THE CHECKOUT PAGE.
 *    CYCLE155-LD-01.
 * =====================================================================
 *
 * Andrew Signore, 2026-08-12, verbatim (⛔ RELAYED through the Chief of Staff
 * in the build brief and NOT witnessed first-hand by this agent):
 *
 *   "Oh the FREE vocab stuff isnt on the checkout page either- sorry!"
 *
 * ⭐ WHAT THE CHECKOUT ACTUALLY SHOWED BEFORE THIS, MEASURED RATHER THAN
 *    ASSUMED. Staging 1.19.219 / plugin 1.8.40, real Blocks checkout, real
 *    headless Chromium, 1440px, cart = one Everest paperback plus the
 *    auto-granted activity book. Every visible node on the page containing
 *    the string "free" was enumerated. There were exactly two, and both were
 *    the SAME free item:
 *
 *      .bhp-addon-upsell__head       "Add The Adventure Activity Book - FREE
 *                                     with your book order (a $5.00 savings)"
 *      order-summary item detail     "Included: FREE with your book order -
 *                                     a $5.00 savings"
 *
 *    ⛔ ZERO `.bhp-free-bullets` elements. ⛔ ZERO occurrences of "FREE
 *       Vocabulary Card Activity". ⛔ ZERO free-shipping line. The one page
 *       every buyer must pass through was the only commerce surface still
 *       carrying the pre-2026-08-06 shape of this message.
 *
 * ⭐ WHY THE SHARED HELPER AND NOTHING ELSE. `bhp_book_free_bullets_markup()`
 *    is the single author of this list on the homepage, the collection
 *    feature, the fast-purchase band and all four funnel pages. Writing the
 *    three lines out here would have created a seventh copy of the copy and a
 *    seventh place for the next free item to be forgotten — which is exactly
 *    the drift that produced this instruction. One helper, one list.
 *
 * ⭐ EVERY LINE KEEPS ITS OWN LIVE PREDICATE, because the helper keeps them.
 *    Verified on staging by `wp eval` before this was written, not inferred:
 *    `bhp_book_collection_ships_free()` = true, `bhp_bundle_addon_free_with_
 *    collection()` = true, `bhp_bundle_vocab_cards_live()` = true, and
 *    `bhp_book_free_bullet_lines('collection')` returns the three approved
 *    lines in the fixed order. On an environment where the vocabulary-cards
 *    PDF is absent the third bullet disappears here with no code change, and
 *    if nothing at all is free the helper returns '' and this panel does not
 *    render — the same fail-closed shape every other caller relies on.
 *
 * ⛔ NO NEW CUSTOMER-FACING COPY IS WRITTEN BY THIS BLOCK. Not one word. No
 *    heading, no eyebrow, no framing sentence. That is a deliberate choice
 *    rather than an omission: the first bullet is conditional by its own
 *    wording ("FREE Shipping on the complete collection"), so any heading
 *    general enough to sit above all three — "included with your order",
 *    "your free extras" — would assert of a one-book cart something the
 *    totals column does not agree with. The bullets each state their own
 *    condition and claim nothing beyond it, so they are shipped alone.
 *
 * ⛔ NO WOOCOMMERCE RECORD IS TOUCHED, AND THE CHECKOUT PAGE'S OWN CONTENT IS
 *    NOT EDITED. Placing this by editing the checkout page would be a
 *    checkout-configuration change on a live environment, which is an Andrew
 *    gate. It is prepended to the outermost `woocommerce/checkout` block's
 *    rendered output instead — the identical mechanism B5 already uses to
 *    move the print-on-demand notice (see class-bhp-printed-for-you.php).
 *    It reverses by deleting one function and one `add_filter`, and
 *    `wp post get <checkout_page_id> --field=post_content` is byte-identical
 *    afterwards.
 *
 * ⛔ IT SITS OUTSIDE THE REACT ROOT, ON PURPOSE. `$block_content` for the
 *    outer `woocommerce/checkout` block is the server-rendered wrapper that
 *    Blocks hydrates; prepending BEFORE it puts this panel outside anything
 *    React owns, so no re-render can remove it and this code never has to
 *    fight a hydration pass. It touches no field, no total, no shipping rate,
 *    no coupon, no payment method and no submit handler.
 *
 * ⛔ THE ORDER-RECEIVED ENDPOINT IS EXCLUDED, for the reason recorded twice
 *    already in this codebase: `is_checkout()` returns TRUE on the thank-you
 *    page. Without the exclusion, somebody who had already paid would be
 *    shown an offer list for a cart that no longer exists.
 */
function bhp_checkout_free_bullets_panel($block_content, $block) {
    if (empty($block['blockName']) || 'woocommerce/checkout' !== $block['blockName']) {
        return $block_content;
    }
    if (!function_exists('is_checkout') || !is_checkout()) {
        return $block_content;
    }
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return $block_content;
    }
    if (!function_exists('bhp_book_free_bullets_markup')) {
        return $block_content;
    }

    $bullets = bhp_book_free_bullets_markup('collection', 'bhp-free-bullets--checkout');
    if ('' === trim($bullets)) {
        return $block_content;
    }

    return '<div class="bhp-checkout-free">' . $bullets . '</div>' . $block_content;
}
add_filter('render_block', 'bhp_checkout_free_bullets_panel', 10, 2);

/* =====================================================================
 * PRESENTATION AND FIELD BEHAVIOUR — F3, F9, F10, D3's rendering
 * =====================================================================
 *
 * The Blocks cart and checkout are React. Three of the items in this wave
 * cannot be reached from PHP at all — a field attribute WooCommerce does not
 * expose (F10), a duplicate rendered only in the mobile layout (F3), and a
 * message that has to appear in response to a state selection (D3). They are
 * done in one small stylesheet and one small script, loaded on cart and
 * checkout only.
 *
 * The script is deliberately DUMB: it sets one attribute, moves nothing that
 * React owns, reads no consent state, and never touches a value, a total or a
 * submit handler. It re-applies on mutation because React re-renders.
 */
function bhp_enqueue_checkout_experience() {
    if (!function_exists('is_cart') || (!is_cart() && !is_checkout())) {
        return;
    }
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style(
        'bhp-checkout-experience',
        get_template_directory_uri() . '/assets/css/checkout-experience.css',
        ['bhp-style'],
        $version
    );
    wp_enqueue_script(
        'bhp-checkout-experience',
        get_template_directory_uri() . '/assets/js/checkout-experience.js',
        [],
        $version,
        true
    );
    wp_localize_script('bhp-checkout-experience', 'bhpCheckoutCopy', [
        'shippingScope'  => bhp_checkout_shipping_scope_notice(),
        'offshoreNotice' => bhp_checkout_offshore_state_notice(),
        'offshoreStates' => ['AK', 'HI'],
        /*
         * 2C-4 — the order-summary remove control and the checkout's own
         * empty state. Strings only; the behaviour is in the script.
         */
        'removeLabel'    => __('Remove', 'brave-hearts'),
        'removingLabel'  => __('Removing…', 'brave-hearts'),
        /* translators: %s: product name */
        'removeAria'     => __('Remove %s from your order', 'brave-hearts'),
        'removeFailed'   => __('That item could not be removed. Please try again.', 'brave-hearts'),
        'emptyCart'      => bhp_empty_cart_copy(),
    ]);
}
add_action('wp_enqueue_scripts', 'bhp_enqueue_checkout_experience', 20);
