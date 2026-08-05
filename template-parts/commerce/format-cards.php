<?php
/**
 * Amazon-style format selector for a canonical book page.
 *
 * Interaction pattern only — the visual identity stays Brave Hearts (cream,
 * forest green, navy, gold). Four bordered cards act as a single-select
 * group of real <button>s with aria-pressed, so there is no dropdown and no
 * radio circle anywhere.
 *
 * Expects $data (bhp_book_purchase_data) and $initial (format key).
 *
 * Every price below is echoed straight from WooCommerce's own price HTML.
 * The Kindle card deliberately has no price.
 */
defined('ABSPATH') || exit;

if (empty($data)) {
    return;
}
/*
 * 2D (2026-08-03): the fallback default is the site-wide one (hardcover), not
 * a literal. The caller already passes bhp_book_incoming_format(); this only
 * matters if some future caller does not.
 */
$initial = isset($initial) ? $initial
    : (function_exists('bhp_book_default_format') ? bhp_book_default_format() : 'hardcover');
$uid = 'bhp-fmt-' . (int) $data['paperback']['product_id'];

/*
 * A1 / CX-008 (2026-08-03) — THE FORMAT-REACTIVE SPEC LINE.
 *
 * Architectural fact this exists because of, verified live by `commerce-cx`
 * 2026-08-02 and re-confirmed here: all three HARDCOVER product URLs 301 to
 * the PAPERBACK product with `?bhp_format=hardcover`. There is exactly ONE
 * `post_content` per title and it is served to both formats. So a hardcover
 * buyer was reading "Paperback. Illustrated." on the page they were buying a
 * hardcover from, and no amount of editing a hardcover product record could
 * have fixed it, because that record's page is never served.
 *
 * The fix is therefore a THEME string that swaps with the selector, beside
 * the `note` string that already swaps on this page. Rendered server-side for
 * `$initial` below so first paint is already correct and CLS stays 0; JS only
 * changes it when the customer changes format.
 *
 * Wording is Andrew-approved (relayed through `chief-of-staff`, not witnessed
 * by this agent). Source deck: Business OS `WORKING-DRAFTS\commerce-cx\
 * DRAFT-2026-08-03-HC-COPY-AND-SHIPPING-MESSAGES.md` §1.2.
 */
$bhp_format_specs = [
    'paperback'  => __('Paperback. Illustrated. 12 short chapters. Ages 6–9.', 'brave-hearts'),
    'hardcover'  => __('Hardcover. Illustrated. 12 short chapters. Ages 6–9. Sturdy, library-quality binding, built to last for years of rereading.', 'brave-hearts'),
    'kindle'     => __('Kindle edition. Delivered by Amazon to your Kindle app or device.', 'brave-hearts'),
    'collection' => __('All three adventures. Illustrated. 12 short chapters each. Ages 6–9. Choose paperback or hardcover.', 'brave-hearts'),
];

/*
 * A4 / CX-016 (2026-08-03) — SHIPPING-LINE HARMONISATION.
 *
 * The old string said "Flat-rate shipping in the contiguous US." Shipping has
 * been TIERED $1.99 to $4.99 since the owner ruling of 2026-08-02
 * (`.claude/rules/woocommerce.md`), and the live Shipping Policy page already
 * said so — the PDP and the policy page contradicted each other, live.
 *
 * ⭐ PART 2B (2026-08-03) — NOW PER-FORMAT, ON ANDREW'S APPROVAL.
 *
 * A4 shipped one string on all four cards and FLAGGED (in the comment this
 * replaces) that "from $1.99" was a store-wide floor, not a per-format one: a
 * cart holding one HARDCOVER resolves to $2.99. Andrew approved the per-format
 * variant — relayed through `chief-of-staff`, NOT witnessed by this agent.
 *
 * ⛔ THE NUMBERS ARE NOT HARDCODED IN COPY. They are read at render time from
 *    the bundle plugin's own approved tables, so the sentence can never drift
 *    away from what the cart actually charges:
 *      PAPERBACK  -> bhp_bundle_single_shipping('paperback')  = 1.99
 *      HARDCOVER  -> bhp_bundle_single_shipping('hardcover')  = 2.99
 *      COLLECTION -> bhp_bundle_rules('paperback')[3]['shipping'] = 3.99
 *    KINDLE has no shipping line at all and never did — Amazon fulfils it.
 *
 * ⚠️ WHY THE COLLECTION CARD MOVES $1.99 -> $3.99, FLAGGED RATHER THAN
 *    ABSORBED: that card sells "all three adventures", and the cheapest way to
 *    receive three books is the 3-paperback tier at $3.99. $1.99 is
 *    unreachable for anything this card can put in a cart, so leaving it would
 *    have left a knowingly-wrong number behind while fixing its neighbour.
 *    Andrew's approval named the hardcover card; this is the same defect on
 *    the same line and is reported for his call, not presented as approved.
 *
 * The fallbacks below are the same figures, verified against bundle-data.php
 * this session, and only ever apply if the bundle plugin is deactivated.
 */
$bhp_ship_single = function ($format, $fallback) {
    return function_exists('bhp_bundle_single_shipping')
        ? (float) bhp_bundle_single_shipping($format)
        : $fallback;
};
$bhp_ship_three = function ($format, $fallback) {
    if (!function_exists('bhp_bundle_rules')) {
        return $fallback;
    }
    $rules = bhp_bundle_rules($format);
    return isset($rules[3]['shipping']) ? (float) $rules[3]['shipping'] : $fallback;
};

/*
 * ⭐ CYCLE143-LD-171 (2026-08-04) — THE SENTENCE NOW HAS A FREE BRANCH.
 *
 * Bundle plugin 1.8.23 took the three-book tier to $0.00 (Andrew's Option B
 * ruling), and this file had exactly one branch, so the collection card began
 * rendering "Shipping from $0.00 in the contiguous US." live on staging. The
 * per-format single-book figures ($1.99 / $2.99) are unaffected and still
 * render as dollars — but they are routed through the same helper so that if a
 * future tier ever goes to zero, the sentence follows it without an edit here.
 *
 * The wording and the free/dollar decision live in inc/book-formats.php
 * (bhp_book_ship_note_single / bhp_book_ship_note_collection), because four
 * landing pages need the same decision and five copies of one branch is how
 * the six hardcoded format defaults drifted before 2D. The threshold itself is
 * the PLUGIN's (bhp_bundle_shipping_is_free()), guarded by function_exists.
 *
 * The fallbacks below are unchanged, and still only apply if the bundle plugin
 * is deactivated.
 */
$bhp_shipping_note_paperback = bhp_book_ship_note_single($bhp_ship_single('paperback', 1.99));
$bhp_shipping_note_hardcover = bhp_book_ship_note_single($bhp_ship_single('hardcover', 2.99));

/*
 * ⭐ 2D (2026-08-03) — THE COLLECTION CARD'S SHIPPING NOW FOLLOWS THE DEFAULT
 *    FORMAT, because its PRICE already did and the two disagreed.
 *
 *    bhp_book_collection_data() has read bhp_bundle_default_format() since
 *    2026-07-30, so this card has been rendering the HARDCOVER collection
 *    price ($48.99) beside the PAPERBACK three-book shipping figure ($3.99).
 *    The hardcover three-book tier is $4.99. One card, two formats, live.
 *
 *    2C flagged the neighbouring $1.99 -> $3.99 move for Andrew's call and did
 *    not absorb it. This is the SAME line and the SAME defect class, and it is
 *    resolved in the direction his walk-4 instruction points ("default to the
 *    hardcovers"), by reading the default rather than by hardcoding either
 *    number. If he sets the default back to paperback, this reads $3.99 again
 *    with no edit here. Flagged in the handoff, not presented as pre-approved.
 */
$bhp_collection_format = function_exists('bhp_book_default_format') ? bhp_book_default_format() : 'hardcover';

$bhp_shipping_note_collection = bhp_book_ship_note_collection(
    $bhp_ship_three($bhp_collection_format, 'paperback' === $bhp_collection_format ? 3.99 : 4.99)
);

/*
 * ⭐ 1.19.194 (2026-08-05) — THE PDP COLLECTION CARD SAYS THE ACTIVITY BOOK
 *    IS FREE. CYCLE144-LD-224.
 *
 * Andrew Signore, 2026-08-05 (⛔ RELAYED, not witnessed first-hand): "I want
 * it clear that you get Free Shipping and a Free Activity book with
 * Collection purchase- on all collection pages and boxes". This card is the
 * collection box on all three product pages.
 *
 * ⛔ APPENDED, NOT MERGED INTO THE SHIPPING SENTENCE. bhp_book_ship_note_
 *    collection() is a PURE function of a shipping figure, asserted against
 *    its exact approved wording in tests/test-book-formats.php for both the
 *    free and the dollar branch. Rewriting it to also carry an add-on claim
 *    would make one string answer two independent questions and would break
 *    an approved-copy assertion that is doing its job.
 *
 * ⛔ bhp_book_free_addon_note() RETURNS '' WHEN THE OFFER IS NOT LIVE, so
 *    this concatenation is unconditional in the code and conditional in the
 *    output. On an environment without the product the note is byte-identical
 *    to 1.19.193.
 */
$bhp_free_addon_note = function_exists('bhp_book_free_addon_note') ? bhp_book_free_addon_note() : '';
if ('' !== $bhp_free_addon_note) {
    $bhp_shipping_note_collection .= ' ' . $bhp_free_addon_note;
}

/*
 * ⭐ CYCLE143-CX-2 / CYCLE143-CX-24 (2026-08-04) — ONE PAYLOAD, TWO CONSUMERS.
 *
 * This array used to be built inline inside the <script> block at the bottom
 * of the file, which was fine while the CTA and the selected price were
 * rendered ONLY by JavaScript. They are now also rendered server-side for
 * $initial (see below), so the array is hoisted here and both consumers read
 * the same variable. A server-rendered CTA that disagreed with the JSON the
 * script re-applies a moment later would be a worse defect than the one being
 * fixed, and hoisting is what makes that structurally impossible rather than
 * merely unlikely.
 *
 * Nothing in the payload's CONTENT changed in this pass — same keys, same
 * sources, same escaping, prices still read live from WooCommerce.
 */
$bhp_format_payload = [
    'paperback' => [
        'priceHtml' => $data['paperback']['price_html'],
        'addUrl'    => $data['paperback']['add_url'],
        'inStock'   => (bool) $data['paperback']['in_stock'],
        'sku'       => $data['paperback']['sku'],
        'productId' => $data['paperback']['product_id'],
        'variationId' => $data['paperback']['variation_id'],
        'ctaLabel'  => __('ADD PAPERBACK TO CART', 'brave-hearts'),
        'formatSpec' => $bhp_format_specs['paperback'],
        'note'      => $bhp_shipping_note_paperback,
    ],
    'hardcover' => [
        'priceHtml' => $data['hardcover']['price_html'],
        'addUrl'    => $data['hardcover']['add_url'],
        'inStock'   => (bool) $data['hardcover']['in_stock'],
        'sku'       => $data['hardcover']['sku'],
        'productId' => $data['hardcover']['product_id'],
        'variationId' => 0,
        'ctaLabel'  => __('ADD HARDCOVER TO CART', 'brave-hearts'),
        'formatSpec' => $bhp_format_specs['hardcover'],
        'note'      => $bhp_shipping_note_hardcover,
    ],
    'kindle' => [
        'priceHtml' => '<span class="bhp-formats__external">' . esc_html__('Available on Amazon', 'brave-hearts') . '</span>',
        'addUrl'    => $data['kindle']['url'],
        'inStock'   => true,
        'sku'       => '',
        'productId' => 0,
        'variationId' => 0,
        'ctaLabel'  => __('VIEW KINDLE ON AMAZON', 'brave-hearts'),
        'formatSpec' => $bhp_format_specs['kindle'],
        'note'      => __('Opens Amazon.com in a new tab. Kindle pricing and delivery are handled by Amazon.', 'brave-hearts'),
        'external'  => true,
    ],
    'collection' => [
        'priceHtml' => $data['collection']['price_html'],
        'addUrl'    => $data['collection']['url'],
        'inStock'   => true,
        'sku'       => '',
        'productId' => 0,
        'variationId' => 0,
        'ctaLabel'  => __('GET THE COMPLETE COLLECTION', 'brave-hearts'),
        'formatSpec' => $bhp_format_specs['collection'],
        'note'      => $bhp_shipping_note_collection,
    ],
];

/*
 * The card the page opens on, resolved once. If $initial names a format this
 * page cannot render (it cannot, today — the caller whitelists four keys — but
 * a future caller might), fall back to the site-wide default rather than
 * emitting a selector with nothing pressed.
 */
$bhp_initial_conf = isset($bhp_format_payload[$initial]) ? $bhp_format_payload[$initial] : null;
if (null === $bhp_initial_conf) {
    $initial = $bhp_collection_format;
    $bhp_initial_conf = $bhp_format_payload[$initial];
}
?>
<div class="bhp-formats" data-bhp-formats
     data-bhp-format-initial="<?php echo esc_attr($initial); ?>"
     data-bhp-kindle-url="<?php echo esc_url($data['kindle']['url']); ?>"
     data-bhp-collection-url="<?php echo esc_url($data['collection']['url']); ?>">

  <h2 class="bhp-formats__heading" id="<?php echo esc_attr($uid); ?>-label">
    <?php esc_html_e('Choose your format', 'brave-hearts'); ?>
  </h2>

  <div class="bhp-formats__grid" role="group" aria-labelledby="<?php echo esc_attr($uid); ?>-label">

    <?php
    /*
     * 2D (2026-08-03) — the two physical cards are emitted in
     * bhp_book_format_order(), i.e. the site default FIRST. Previously
     * PAPERBACK was hardcoded into the first slot, so once the default moved
     * to hardcover the pressed card would have sat in the second position -
     * a half-applied default, which reads to a customer as a bug rather than
     * as a choice. Kindle and Complete Collection keep their positions.
     *
     * The initial card is also pressed SERVER-SIDE. book-formats.js re-presses
     * exactly the same card on DOMContentLoaded, so this only removes the
     * unpressed flash before it runs, and keeps the selection visible if the
     * script never executes.
     *
     * ⭐ CYCLE143-CX-2 (2026-08-04): the order now follows $initial, not the
     *    site-wide default. 2D's own reasoning is why — it moved the order to
     *    follow the default precisely because "a half-applied default reads to
     *    a customer as a bug rather than as a choice". On a paperback URL the
     *    selected card is now PAPERBACK, so leaving HARDCOVER in the first slot
     *    would reintroduce that exact split, with the pressed card sitting
     *    second. bhp_book_format_order() itself is UNCHANGED and still governs
     *    every funnel/landing surface; it is simply not the right question on a
     *    page that already knows which format the visitor asked for. It remains
     *    the fallback for a non-physical $initial (kindle/collection), where
     *    there is no URL format to follow.
     */
    $bhp_format_labels = [
        'paperback' => __('PAPERBACK', 'brave-hearts'),
        'hardcover' => __('HARDCOVER', 'brave-hearts'),
    ];
    $bhp_default_order = function_exists('bhp_book_format_order') ? bhp_book_format_order() : ['paperback', 'hardcover'];
    $bhp_card_order = in_array($initial, ['paperback', 'hardcover'], true)
        ? ['paperback' === $initial ? 'paperback' : 'hardcover', 'paperback' === $initial ? 'hardcover' : 'paperback']
        : $bhp_default_order;
    foreach ($bhp_card_order as $bhp_fmt):
        $bhp_on = ($bhp_fmt === $initial);
    ?>
    <button type="button" class="bhp-format-card<?php echo $bhp_on ? ' is-selected' : ''; ?>" data-bhp-format="<?php echo esc_attr($bhp_fmt); ?>" aria-pressed="<?php echo $bhp_on ? 'true' : 'false'; ?>">
      <span class="bhp-format-card__name"><?php echo esc_html($bhp_format_labels[$bhp_fmt]); ?></span>
      <span class="bhp-format-card__price"><?php echo wp_kses_post($data[$bhp_fmt]['price_html']); ?></span>
    </button>
    <?php endforeach; ?>

    <?php if ($data['kindle']['url']): ?>
    <button type="button" class="bhp-format-card<?php echo 'kindle' === $initial ? ' is-selected' : ''; ?>" data-bhp-format="kindle" aria-pressed="<?php echo 'kindle' === $initial ? 'true' : 'false'; ?>">
      <span class="bhp-format-card__name"><?php esc_html_e('KINDLE', 'brave-hearts'); ?></span>
      <?php /* No price: Amazon controls it and none is stored anywhere. */ ?>
      <span class="bhp-format-card__price bhp-format-card__price--external"><?php esc_html_e('VIEW ON AMAZON', 'brave-hearts'); ?></span>
    </button>
    <?php endif; ?>

    <button type="button" class="bhp-format-card bhp-format-card--collection<?php echo 'collection' === $initial ? ' is-selected' : ''; ?>" data-bhp-format="collection" aria-pressed="<?php echo 'collection' === $initial ? 'true' : 'false'; ?>">
      <span class="bhp-format-card__badge"><?php esc_html_e('BEST VALUE', 'brave-hearts'); ?></span>
      <span class="bhp-format-card__name"><?php esc_html_e('COMPLETE COLLECTION', 'brave-hearts'); ?></span>
      <span class="bhp-format-card__price"><?php echo wp_kses_post($data['collection']['price_html']); ?></span>
    </button>
  </div>

  <?php
  /*
   * ⭐ CYCLE143-CX-2 / CYCLE143-CX-24 (2026-08-04) — THE PRICE AND THE CTA ARE
   *    NOW SERVER-RENDERED FOR $initial. Three reasons, in order of weight:
   *
   *    1. CORRECTNESS AT FIRST PAINT. Both elements shipped EMPTY
   *       (`href="#"`, no label) and were filled in by book-formats.js on
   *       DOMContentLoaded. Every other element in this component — cards,
   *       pressed state, spec line — was already server-rendered for exactly
   *       this reason; the CTA, the one element a customer clicks, was the
   *       hole in that policy. If the script is blocked, deferred behind a
   *       slow third-party, or throws before it runs, the visitor is left with
   *       a dead `#` link on the purchase page. That is not hypothetical here:
   *       `CYCLE143-CX-1` recorded a live product-page JS breakage on 2026-08-04
   *       (three exceptions from a lodash/underscore collision) which, had it
   *       hit this component, would have left the add-to-cart button empty.
   *
   *    2. NO EMPTY-TO-FILLED FLASH, and one less layout shift on the most
   *       important element on the page.
   *
   *    3. IT MAKES THE FIX VERIFIABLE WITHOUT A BROWSER. The defect's visible
   *       symptom is the CTA reading "ADD HARDCOVER TO CART" on a paperback
   *       URL. With an empty server-side CTA, a GET could only ever see the
   *       card state and had to infer the button. Now the label and the real
   *       add-to-cart URL are both in the HTML.
   *
   *    book-formats.js re-applies the identical values from the same payload
   *    below (hoisted to $bhp_format_payload precisely so the two cannot
   *    diverge), so this ADDS a correct first paint and changes no behaviour
   *    after the script runs.
   */
  $bhp_cta_disabled = (isset($bhp_initial_conf['inStock']) && false === $bhp_initial_conf['inStock']);
  $bhp_cta_external = !empty($bhp_initial_conf['external']);
  ?>
  <p class="bhp-formats__selected-price" data-bhp-format-price aria-live="polite"><?php echo wp_kses_post($bhp_initial_conf['priceHtml']); ?></p>

  <p class="bhp-formats__cta-wrap">
    <a class="btn btn-primary bhp-formats__cta<?php echo $bhp_cta_disabled ? ' is-disabled' : ''; ?>"
       data-bhp-format-cta
       href="<?php echo esc_url($bhp_initial_conf['addUrl'] ? $bhp_initial_conf['addUrl'] : '#'); ?>"
       <?php echo $bhp_cta_external ? 'target="_blank" rel="noopener nofollow sponsored"' : ''; ?>
       <?php echo $bhp_cta_disabled ? 'aria-disabled="true"' : ''; ?>><?php echo esc_html($bhp_initial_conf['ctaLabel']); ?></a>
  </p>

  <?php /* A1: server-rendered for the initial format so first paint is already
           correct and this line can never cause a layout shift.
           CYCLE143-CX-2: now read from $bhp_initial_conf rather than re-deriving
           the key from $bhp_format_specs, so the spec line, the CTA, the price
           and the JSON below all come from one array. */ ?>
  <p class="bhp-formats__spec" data-bhp-format-spec><?php echo esc_html($bhp_initial_conf['formatSpec']); ?></p>

  <?php /* CYCLE143-CX-2: the shipping note is server-rendered for the same
           reason as the CTA above — it was the last empty element in the
           component, and a blank shipping line on first paint is exactly the
           question a parent is trying to answer. */ ?>
  <p class="bhp-formats__note" data-bhp-format-note><?php echo esc_html(isset($bhp_initial_conf['note']) ? $bhp_initial_conf['note'] : ''); ?></p>

  <?php /* Per-format data for the selector. Prices are rendered server-side
           above; these payloads carry only IDs, URLs and already-escaped
           price HTML so nothing is recalculated in the browser. */ ?>
  <script type="application/json" data-bhp-format-data>
    <?php
    /* CYCLE143-CX-2: emitted from the hoisted $bhp_format_payload above, which
       is the same array the server-rendered price, CTA, spec and note read
       from. The payload's contents are unchanged from the inline literal this
       replaces. */
    echo wp_json_encode($bhp_format_payload);
    ?>
  </script>
</div>
