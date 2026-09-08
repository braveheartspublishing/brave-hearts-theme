<?php
/**
 * "One book, or all three" — the comparison table markup.
 * Theme 1.19.389, 2026-09-06, `CYCLE179-LD-BUILD-389`.
 *
 * Included by `bhp_compare_table_render()` in `inc/compare-table.php`, which
 * hands in `$bhp_compare` (every figure already read live) and renders nothing
 * at all if any of it could not be read. See that file's header for the
 * specification, the live-price discipline and the rails.
 *
 * ⭐ THE MOBILE SHAPE Merry's §1.6 asked for: below 640px the column headers
 *    drop out of view (they stay in the accessibility tree), each row becomes
 *    its own block with the row label on a full-width line, and the two answers
 *    sit side by side underneath it, each keeping its own label via
 *    `data-label`. Desktop stays an ordinary three column table.
 *
 * ⭐ "FREE" IS UPPERCASE IN THE STRING ITSELF, never by `text-transform`, so it
 *    survives the accessible name, the plain-text fallback and any copy audit.
 *
 * ⛔ NO EM DASH. NO "we"/"us"/"our". NO TRACKING CLAIM. NO OUTCOME CLAIM.
 */
defined('ABSPATH') || exit;

if (!isset($bhp_compare) || !is_array($bhp_compare)) {
    return;
}

/*
 * ⛔ THE ENTITY DECODE IS LOAD-BEARING, NOT COSMETIC, AND IT WAS CAUGHT ON
 *    STAGING RATHER THAN IN REVIEW. `wc_price()` emits the currency symbol as
 *    the HTML entity `&#36;`, so `wp_strip_all_tags()` alone returns the
 *    literal seven characters `&#36;11.99`. `esc_html()` at the print site then
 *    escapes the ampersand again and the customer reads `&amp;#36;11.99`.
 *    Decoding here yields a real `$`, which `esc_html()` passes through
 *    unchanged.
 *
 * ⭐ THE FIGURE ITSELF IS STILL WOOCOMMERCE'S. This helper formats; it never
 *    computes. `wc_price()` supplies the symbol, the separators and the decimal
 *    places from the store's own settings, so a currency or precision change
 *    moves this table with it.
 */
$bhp_cmp_price   = static function ($amount) {
    if (!function_exists('wc_price')) {
        return '$' . number_format((float) $amount, 2);
    }
    return html_entity_decode(wp_strip_all_tags(wc_price((float) $amount)), ENT_QUOTES, 'UTF-8');
};

$bhp_cmp_single      = $bhp_cmp_price($bhp_compare['single_paperback']);
$bhp_cmp_collection  = $bhp_cmp_price($bhp_compare['collection_price']);
$bhp_cmp_separately  = $bhp_cmp_price($bhp_compare['collection_separately']);
$bhp_cmp_saving      = $bhp_cmp_price($bhp_compare['collection_saving']);
$bhp_cmp_hardcover   = $bhp_cmp_price($bhp_compare['single_hardcover']);
$bhp_cmp_coll_hc     = $bhp_cmp_price($bhp_compare['collection_hardcover']);

$bhp_cmp_one_label  = __('One book', 'brave-hearts');
$bhp_cmp_all_label  = __('Complete Collection', 'brave-hearts');
?>
<section class="bhp-compare" aria-labelledby="bhp-compare-title">
  <h2 id="bhp-compare-title" class="bhp-compare__title"><?php esc_html_e('One book, or all three', 'brave-hearts'); ?></h2>
  <p class="bhp-compare__lead"><?php esc_html_e('Here is exactly what changes if you take all three together, and what comes with your order either way.', 'brave-hearts'); ?></p>

  <div class="bhp-compare__scroll">
    <table class="bhp-compare__table">
      <caption class="screen-reader-text"><?php esc_html_e('A single paperback compared with the Complete Paperback Collection', 'brave-hearts'); ?></caption>
      <thead>
        <tr>
          <td class="bhp-compare__corner"></td>
          <th scope="col" class="bhp-compare__col"><?php echo esc_html($bhp_cmp_one_label); ?></th>
          <th scope="col" class="bhp-compare__col bhp-compare__col--best"><?php echo esc_html($bhp_cmp_all_label); ?></th>
        </tr>
      </thead>
      <tbody>
        <tr class="bhp-compare__group">
          <th scope="rowgroup" colspan="3" class="bhp-compare__grouphead"><?php esc_html_e('What changes', 'brave-hearts'); ?></th>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Price, paperback', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php echo esc_html($bhp_cmp_single); ?></td>
          <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php
            /* translators: 1: collection price, 2: the three books bought separately, 3: the amount saved */
            printf(
                esc_html__('%1$s for all three. Bought separately they are %2$s, so that is %3$s off.', 'brave-hearts'),
                esc_html($bhp_cmp_collection),
                esc_html($bhp_cmp_separately),
                esc_html($bhp_cmp_saving)
            );
          ?></td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Shipping', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php
            /*
             * ⛔ THE FIGURE IS THE PLUGIN'S OWN APPROVED SINGLE-ITEM AMOUNT, not
             *    a typed number, and the sentence is only printed when it can be
             *    read. `bhp_book_ship_note_single()` is deliberately NOT reused:
             *    it is a pure function asserted against its exact approved
             *    wording in `tests/test-book-formats.php`, and this is a table
             *    cell rather than that sentence.
             */
            if (null !== $bhp_compare['ship_single']) {
                /* translators: %s is a dollar amount, e.g. $1.99 */
                printf(
                    esc_html__('Starts at %s in the contiguous US', 'brave-hearts'),
                    esc_html($bhp_cmp_price($bhp_compare['ship_single']))
                );
            } else {
                esc_html_e('Shown at checkout', 'brave-hearts');
            }
          ?></td>
          <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php
            /* ⛔ A LIVE READ, NOT A COPY DECISION. If a tier ever moves off
               $0.00 this cell stops saying Free in the same deploy. */
            echo $bhp_compare['collection_ships_free']
                ? esc_html__('Free', 'brave-hearts')
                : esc_html__('Shown at checkout', 'brave-hearts');
          ?></td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Your shipment', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php esc_html_e('One book', 'brave-hearts'); ?></td>
          <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php esc_html_e('All three books, together in one shipment', 'brave-hearts'); ?></td>
        </tr>

        <tr class="bhp-compare__group">
          <th scope="rowgroup" colspan="3" class="bhp-compare__grouphead"><?php esc_html_e('The same either way', 'brave-hearts'); ?></th>
        </tr>
        <?php if ($bhp_compare['activity_book_live']): ?>
          <tr>
            <th scope="row" class="bhp-compare__row"><?php esc_html_e('FREE Adventure Activity Book, printable', 'brave-hearts'); ?></th>
            <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php esc_html_e('Yes, with any book order', 'brave-hearts'); ?></td>
            <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php esc_html_e('Yes', 'brave-hearts'); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($bhp_compare['vocab_cards_live']): ?>
          <tr>
            <th scope="row" class="bhp-compare__row"><?php esc_html_e('FREE Vocabulary Card Activity, printable', 'brave-hearts'); ?></th>
            <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php esc_html_e('Yes, with any book order', 'brave-hearts'); ?></td>
            <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php esc_html_e('Yes', 'brave-hearts'); ?></td>
          </tr>
        <?php endif; ?>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('30 day guarantee, keep the books', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php esc_html_e('Yes', 'brave-hearts'); ?></td>
          <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php esc_html_e('Yes', 'brave-hearts'); ?></td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Printed for your order', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php esc_html_e('Yes', 'brave-hearts'); ?></td>
          <td data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php esc_html_e('Yes', 'brave-hearts'); ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <p class="bhp-compare__note"><?php
    /* translators: 1: single hardcover price, 2: hardcover collection price */
    printf(
        esc_html__('Prices are paperback. Hardcover is %1$s for one book and %2$s for the collection.', 'brave-hearts'),
        esc_html($bhp_cmp_hardcover),
        esc_html($bhp_cmp_coll_hc)
    );
  ?></p>
</section>
