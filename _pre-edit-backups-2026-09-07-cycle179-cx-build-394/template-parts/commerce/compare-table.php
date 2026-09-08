<?php
/**
 * "One book, or all three" — the comparison table markup, brand system build.
 * Theme 1.19.391, 2026-09-07, `CYCLE179-CX-BUILD-391`.
 *
 * Included by `bhp_compare_table_render()` in `inc/compare-table.php`, which
 * hands in `$bhp_compare` (every figure already read live) and renders nothing
 * at all if any of it could not be read. See that file's header for the
 * specification, the live-price discipline and the rails.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT CHANGED IN 1.19.391, AND WHAT DID NOT.
 * ═══════════════════════════════════════════════════════════════════════════
 * Design of record: `ANDREW-REVIEW\2026-09-07\PDP-COMPARE\SPEC-FOR-ARAGORN.md`
 * (`CYCLE179-DES-PDP-COMPARE`, design-creative), CONCEPT B. Andrew, seal 1208:
 * "bring up the look inside next to the table ... The table needs to look more
 * professional its just bland text and white - make it brand worthy and
 * emphasize FREE etc".
 *
 * ⛔⛔ NOT ONE ROW'S TRUTH CHANGED. Where a visual treatment replaces words —
 *     a check disc for "Yes", a large price and a saving pill for the saving
 *     sentence, a gold badge for "Free" — THE ORIGINAL STRING IS CARRIED
 *     VERBATIM in `.screen-reader-text` and the visual is `aria-hidden`. The
 *     accessible reading of every row is byte for byte what 1.19.390 printed.
 *     ⭐ That is not a courtesy to screen readers; it is what makes this a
 *        restyle rather than a copy change, and copy changes are Andrew's.
 *
 * ⭐ THE ONE PLACE THE VISIBLE STRING IS NOT THE ACCESSIBLE ONE, stated so it
 *    is not discovered later: the two "Yes, with any book order" cells show a
 *    check disc and the words "with any book order" only. A screen reader
 *    still hears the whole original sentence from the sibling
 *    `.screen-reader-text`. `with any book order` is therefore a NEW
 *    translatable string; it is the same words, split.
 *
 * ⛔ NOT ONE PRICE IS TYPED. Every figure comes from `$bhp_compare`, which
 *    `bhp_compare_table_data()` reads live from WooCommerce and the bundle
 *    plugin on every render. `tests/test-cycle179-build-389.php` §3.34 fails
 *    this file the moment a dollar figure is typed into it.
 *
 * ⭐ "FREE" IS UPPERCASE IN THE STRING ITSELF, never by `text-transform`, so it
 *    survives the accessible name, the plain-text fallback and any copy audit.
 *    ⚠ The row labels now render it as a gold badge, so the rendered HTML has
 *      a `<span>` between "FREE" and "Adventure". §3.24 and §3.26 of the 389
 *      suite compared the raw HTML and are superseded there to strip tags
 *      first — the claim is unchanged, the instrument was tag-blind.
 *
 * ⭐ THE "LOOK INSIDE" CUE IS A REAL LINK, NOT A SCRIPTED CONTROL. It is an
 *    `<a href="#bhp-look-inside-<key>">` to the hero gallery that already sits
 *    beside the buy box, so it works with JavaScript off and needs no new
 *    script at all. The sticky-header overlap is handled by the 1.19.390
 *    `--bhp-anchor-offset` mechanism, extended to `.bhp-look-inside[id]` in
 *    `style.css`. ⛔ NO LIGHTBOX IS FORCED OPEN. The design note suggested
 *    also opening `[data-bhp-gallery-lightbox]`; the build brief says the cue
 *    "jumps to the gallery", and throwing a modal at a reader who asked to be
 *    taken somewhere is a different interaction from the one they requested.
 *    The lightbox is one click away once they arrive, which is where every
 *    competitor puts it. Departure recorded rather than absorbed.
 *
 * ⛔⛔ THE CUE NEVER CLAIMS A VIDEO THAT IS NOT THERE. Its sub-line names a
 *     flip-through video only when `bhp_book_media()` actually resolved one
 *     for this title. A title with stills only gets the stills wording. An
 *     unearned "and a flip-through video" would be a fabricated product claim
 *     on a purchase page, which is the one thing this house never ships.
 *
 * ⛔ THE THUMBNAILS ARE THE GALLERY'S OWN ITEMS, resolved through
 *    `bhp_book_media()`. No new printed page is exposed anywhere by this file,
 *    and the cue therefore cannot show a page the gallery does not open.
 *
 * ⛔ NO EM DASH. NO "we"/"us"/"our". NO TRACKING CLAIM. NO OUTCOME CLAIM. NO
 *    RATING, REVIEW, AWARD, SCARCITY OR URGENCY.
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

/*
 * The check disc. One markup string, built once and reused, because six copies
 * of an inline SVG in a template is six places to fix a path. It is decorative
 * in every cell it appears in: the word it replaces is always beside it in
 * `.screen-reader-text`.
 */
$bhp_cmp_check = '<span class="bhp-compare__check" aria-hidden="true">'
    . '<svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">'
    . '<path d="M1.5 6.4 4.5 9.4 10.5 2.8"/></svg></span>';

/* The gold FREE badge. Uppercase in the string, never by CSS. */
$bhp_cmp_free_badge = '<span class="bhp-compare__free">' . esc_html__('FREE', 'brave-hearts') . '</span>';

/*
 * A "Yes" answer cell. `$qualifier` is the visible words that follow the check;
 * `$spoken` is the ORIGINAL, UNCHANGED sentence a screen reader hears.
 */
$bhp_cmp_yes_cell = static function ($spoken, $qualifier = '') use ($bhp_cmp_check) {
    $out  = '<span class="screen-reader-text">' . esc_html($spoken) . '</span>';
    $out .= '<span class="bhp-compare__yes" aria-hidden="true">' . $bhp_cmp_check;
    if ('' !== $qualifier) {
        $out .= '<span class="bhp-compare__qual">' . esc_html($qualifier) . '</span>';
    }
    $out .= '</span>';
    return $out;
};

/* Everything the "Look inside" cue needs, or nothing at all. */
$bhp_cmp_key       = isset($bhp_compare['key']) ? (string) $bhp_compare['key'] : '';
$bhp_cmp_cue       = false;
$bhp_cmp_cue_thumbs = array();
$bhp_cmp_cue_video  = false;

if ('' !== $bhp_cmp_key
    && function_exists('bhp_book_has_look_inside')
    && bhp_book_has_look_inside($bhp_cmp_key)
    && function_exists('bhp_book_media')
) {
    $bhp_cmp_media = bhp_book_media($bhp_cmp_key);
    if (!empty($bhp_cmp_media['has_any']) && !empty($bhp_cmp_media['items'])) {
        foreach ($bhp_cmp_media['items'] as $bhp_cmp_item) {
            if ('video' === $bhp_cmp_item['type']) {
                $bhp_cmp_cue_video = true;
                continue;
            }
            if ('image' === $bhp_cmp_item['type'] && count($bhp_cmp_cue_thumbs) < 2 && !empty($bhp_cmp_item['id'])) {
                $bhp_cmp_cue_thumbs[] = (int) $bhp_cmp_item['id'];
            }
        }
        $bhp_cmp_cue = true;
    }
}
?>
<section class="bhp-compare" aria-labelledby="bhp-compare-title">

  <div class="bhp-compare__head">
    <h2 id="bhp-compare-title" class="bhp-compare__title"><?php esc_html_e('One book, or all three', 'brave-hearts'); ?></h2>
    <p class="bhp-compare__lead"><?php esc_html_e('Here is exactly what changes if you take all three together, and what comes with your order either way.', 'brave-hearts'); ?></p>
  </div>

  <div class="bhp-compare__scroll">
    <table class="bhp-compare__table">
      <caption class="screen-reader-text"><?php esc_html_e('A single paperback compared with the Complete Paperback Collection', 'brave-hearts'); ?></caption>
      <thead>
        <tr>
          <td class="bhp-compare__corner"></td>
          <th scope="col" class="bhp-compare__col">
            <?php
            /*
             * ⭐ THE GHOST TAB IS A SPACER, NOT A LABEL. The "One book" column
             *    has no gold tab above it, so without a reserved box its name
             *    sits a tab's height higher than the Collection's and the two
             *    column names do not line up. It is `visibility: hidden` so it
             *    still reserves the box, and `aria-hidden` so it never reaches
             *    the accessible name of the column. It must never be read as
             *    calling a single book the best value.
             */
            ?>
            <span class="bhp-compare__besttab bhp-compare__besttab--ghost" aria-hidden="true"><?php esc_html_e('Best value', 'brave-hearts'); ?></span>
            <span class="bhp-compare__collabel"><?php echo esc_html($bhp_cmp_one_label); ?></span>
          </th>
          <th scope="col" class="bhp-compare__col bhp-compare__col--best">
            <span class="bhp-compare__besttab"><?php esc_html_e('Best value', 'brave-hearts'); ?></span>
            <span class="bhp-compare__collabel"><?php echo esc_html($bhp_cmp_all_label); ?></span>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php
        /*
         * ⛔ THE GROUP HEADING SPANS TWO COLUMNS, NOT THREE, AND THE EMPTY
         *    THIRD CELL IS NOT PADDING. The Collection column is one gold-edged
         *    lane running the full height of the table. A `colspan="3"` heading
         *    paints straight across that lane and the framing reads as
         *    unfinished. The filler cell carries the lane through the heading
         *    row. It is empty by design and is hidden entirely on the phone
         *    card list, where there is no lane to keep continuous.
         */
        ?>
        <tr class="bhp-compare__group">
          <th scope="rowgroup" colspan="2" class="bhp-compare__grouphead"><?php esc_html_e('What changes', 'brave-hearts'); ?></th>
          <td class="bhp-compare__cell--best bhp-compare__groupfill"></td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Price, paperback', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>">
            <span class="bhp-compare__price"><?php echo esc_html($bhp_cmp_single); ?></span>
            <span class="bhp-compare__pricesub"><?php esc_html_e('Paperback', 'brave-hearts'); ?></span>
          </td>
          <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>">
            <?php
            /*
             * ⛔ THE SPOKEN CELL IS 1.19.390's SENTENCE, CHARACTER FOR
             *    CHARACTER, with the same three live figures in the same three
             *    slots. The visual beside it is a restatement of exactly those
             *    figures and adds no claim: no "best value" in prose, no
             *    percentage, no comparison to anything that is not on this
             *    page. `evidence-verification` §5, the derived-claim trap: the
             *    saving is recomputed live every render, never carried.
             */
            ?>
            <span class="screen-reader-text"><?php
                /* translators: 1: collection price, 2: the three books bought separately, 3: the amount saved */
                printf(
                    esc_html__('%1$s for all three. Bought separately they are %2$s, so that is %3$s off.', 'brave-hearts'),
                    esc_html($bhp_cmp_collection),
                    esc_html($bhp_cmp_separately),
                    esc_html($bhp_cmp_saving)
                );
            ?></span>
            <span aria-hidden="true">
              <span class="bhp-compare__price"><?php echo esc_html($bhp_cmp_collection); ?></span>
              <span class="bhp-compare__pricesub"><?php esc_html_e('For all three', 'brave-hearts'); ?></span>
              <span class="bhp-compare__was"><?php
                /* translators: %s is the three books bought separately, wrapped in a strikethrough. */
                printf(
                    esc_html__('instead of %s bought separately', 'brave-hearts'),
                    '<s class="bhp-compare__strike">' . esc_html($bhp_cmp_separately) . '</s>'
                );
              ?></span>
              <span class="bhp-compare__save"><?php
                /* translators: %s is the amount saved, e.g. $3.98 */
                printf(esc_html__('Save %s', 'brave-hearts'), esc_html($bhp_cmp_saving));
              ?></span>
            </span>
          </td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Shipping', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><span class="bhp-compare__plain"><?php
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
          ?></span></td>
          <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php
            /* ⛔ A LIVE READ, NOT A COPY DECISION. If a tier ever moves off
               $0.00 this cell stops saying Free in the same deploy, and the
               gold badge goes with it rather than outliving the fact. */
            if ($bhp_compare['collection_ships_free']) {
                echo '<span class="screen-reader-text">' . esc_html__('Free', 'brave-hearts') . '</span>';
                echo '<span aria-hidden="true"><span class="bhp-compare__free bhp-compare__free--lg">' . esc_html__('FREE', 'brave-hearts') . '</span></span>';
            } else {
                echo '<span class="bhp-compare__plain">' . esc_html__('Shown at checkout', 'brave-hearts') . '</span>';
            }
          ?></td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Your shipment', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><span class="bhp-compare__plain"><?php esc_html_e('One book', 'brave-hearts'); ?></span></td>
          <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><span class="bhp-compare__plain"><?php esc_html_e('All three books, together in one shipment', 'brave-hearts'); ?></span></td>
        </tr>

        <tr class="bhp-compare__group">
          <th scope="rowgroup" colspan="2" class="bhp-compare__grouphead"><?php esc_html_e('The same either way', 'brave-hearts'); ?></th>
          <td class="bhp-compare__cell--best bhp-compare__groupfill"></td>
        </tr>
        <?php if ($bhp_compare['activity_book_live']): ?>
          <tr>
            <th scope="row" class="bhp-compare__row"><?php
              /*
               * ⛔ NOT `wp_kses_post()`, AND THE REASON IS NOT CONVENIENCE.
               *    Every value inside `$bhp_cmp_free_badge` and inside the
               *    format string is escaped where it is built, so kses has
               *    nothing left to sanitise; what it WOULD do is strip the
               *    `<svg>` and `<path>` in the check discs below, because
               *    `$allowedposttags` does not carry `path`. A filter that
               *    silently deletes correct markup is worse than no filter.
               */
              printf(
                  /* translators: %s is the word FREE, rendered as a gold badge. */
                  esc_html__('%s Adventure Activity Book, printable', 'brave-hearts'),
                  $bhp_cmp_free_badge
              );
            ?></th>
            <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php
              echo $bhp_cmp_yes_cell(
                  __('Yes, with any book order', 'brave-hearts'),
                  __('with any book order', 'brave-hearts')
              );
            ?></td>
            <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php
              echo $bhp_cmp_yes_cell(__('Yes', 'brave-hearts'));
            ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($bhp_compare['vocab_cards_live']): ?>
          <tr>
            <th scope="row" class="bhp-compare__row"><?php
              printf(
                  /* translators: %s is the word FREE, rendered as a gold badge. */
                  esc_html__('%s Vocabulary Card Activity, printable', 'brave-hearts'),
                  $bhp_cmp_free_badge
              );
            ?></th>
            <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php
              echo $bhp_cmp_yes_cell(
                  __('Yes, with any book order', 'brave-hearts'),
                  __('with any book order', 'brave-hearts')
              );
            ?></td>
            <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php
              echo $bhp_cmp_yes_cell(__('Yes', 'brave-hearts'));
            ?></td>
          </tr>
        <?php endif; ?>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('30 day guarantee, keep the books', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php echo $bhp_cmp_yes_cell(__('Yes', 'brave-hearts')); ?></td>
          <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php echo $bhp_cmp_yes_cell(__('Yes', 'brave-hearts')); ?></td>
        </tr>
        <tr>
          <th scope="row" class="bhp-compare__row"><?php esc_html_e('Printed for your order', 'brave-hearts'); ?></th>
          <td data-label="<?php echo esc_attr($bhp_cmp_one_label); ?>"><?php echo $bhp_cmp_yes_cell(__('Yes', 'brave-hearts')); ?></td>
          <td class="bhp-compare__cell--best" data-label="<?php echo esc_attr($bhp_cmp_all_label); ?>"><?php echo $bhp_cmp_yes_cell(__('Yes', 'brave-hearts')); ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <?php
  /*
   * ═══════════════════════════════════════════════════════════════════════════
   * ⭐⭐ 1.19.393 (`CYCLE179-CX-BUILD-391-1`) — THE PHONE GETS TWO SUMMARY
   *     CARDS INSTEAD OF THE STACKED TABLE.
   * ═══════════════════════════════════════════════════════════════════════════
   *
   * Andrew asked for an alternative to the phone table; the mockup he chose is
   * the right-hand panel of
   * `ANDREW-REVIEW\2026-09-07\BUILD-391-1\mobile-table-options.png`, and
   * It was sealed (1274) at widths up to 812. MEASURED at a real 375 CSS
   * viewport on staging 1.19.391: the stacked table is 1,688px tall; these two
   * cards are 567px.
   *
   * ⛔ THE DESKTOP TABLE IS UNTOUCHED. Concept B renders exactly as it did at
   *    1.19.392 from 813px up. This block adds a second rendering of the SAME
   *    figures for narrow screens and nothing else.
   *
   * ⛔ NOT ONE FIGURE IS TYPED. Every price, saving and shipping amount comes
   *    from `$bhp_compare`, which `bhp_compare_table_data()` reads live from
   *    WooCommerce and the bundle plugin on every render — the same array the
   *    table above reads. §3.34 of the 389 suite fails this file the moment a
   *    dollar figure is typed into it.
   *
   * ⛔ EXACTLY ONE OF THE TWO SHAPES IS IN THE DOCUMENT AT ANY WIDTH, and it is
   *    `display: none` that does it, NOT `aria-hidden`. `display: none` removes
   *    a subtree from the accessibility tree as well as the layout, so a screen
   *    reader hears the table on a desktop and the cards on a phone, never both
   *    and never a hidden duplicate.
   *
   * ⭐ THE CARDS CARRY THE FOUR DIFFERENCES AND NOTHING ELSE: price, savings,
   *    shipping, what ships. The four "same either way" rows collapse into the
   *    single "What comes with every order" line at the foot, which is the
   *    whole reason the shape is a third of the height. ⛔ NO ROW'S TRUTH
   *    CHANGES: the free printables are still gated on
   *    `activity_book_live` / `vocab_cards_live`, so a printable that is not
   *    live is not promised.
   *
   * ⛔ NO EM DASH. NO "we"/"us"/"our". NO RATING, REVIEW, AWARD, OUTCOME,
   *    SCARCITY OR URGENCY CLAIM. "FREE" is uppercase in the string itself,
   *    never by `text-transform`.
   */
  ?>
  <div class="bhp-compare__cards">

    <div class="bhp-compare__card">
      <p class="bhp-compare__cardname"><?php echo esc_html($bhp_cmp_one_label); ?></p>
      <p class="bhp-compare__cardprice"><?php echo esc_html($bhp_cmp_single); ?></p>
      <p class="bhp-compare__cardsub"><?php esc_html_e('Paperback', 'brave-hearts'); ?></p>
      <dl class="bhp-compare__cardrows">
        <div class="bhp-compare__cardrow">
          <dt><?php esc_html_e('Shipping', 'brave-hearts'); ?></dt>
          <dd><?php
            if (null !== $bhp_compare['ship_single']) {
                /* translators: %s is a dollar amount, e.g. $1.99 */
                printf(
                    esc_html__('Starts at %s in the contiguous US', 'brave-hearts'),
                    esc_html($bhp_cmp_price($bhp_compare['ship_single']))
                );
            } else {
                esc_html_e('Shown at checkout', 'brave-hearts');
            }
          ?></dd>
        </div>
        <div class="bhp-compare__cardrow">
          <dt><?php esc_html_e('What ships', 'brave-hearts'); ?></dt>
          <dd><?php esc_html_e('One book', 'brave-hearts'); ?></dd>
        </div>
      </dl>
    </div>

    <div class="bhp-compare__card bhp-compare__card--best">
      <span class="bhp-compare__cardbadge"><?php esc_html_e('BEST VALUE', 'brave-hearts'); ?></span>
      <p class="bhp-compare__cardname"><?php echo esc_html($bhp_cmp_all_label); ?></p>
      <p class="bhp-compare__cardprice"><?php echo esc_html($bhp_cmp_collection); ?></p>
      <p class="bhp-compare__cardsave"><?php
        /*
         * ⛔ THE DERIVED-CLAIM RAIL. The saving is recomputed live on every
         *    render from the same three live figures the table speaks, never
         *    carried from a draft. It states the amount and the comparison and
         *    makes no further claim.
         */
        /* translators: 1: the amount saved, 2: the three books bought separately */
        printf(
            esc_html__('Save %1$s. Bought separately they are %2$s.', 'brave-hearts'),
            esc_html($bhp_cmp_saving),
            esc_html($bhp_cmp_separately)
        );
      ?></p>
      <dl class="bhp-compare__cardrows">
        <div class="bhp-compare__cardrow">
          <dt><?php esc_html_e('Shipping', 'brave-hearts'); ?></dt>
          <dd class="bhp-compare__cardfree"><?php
            /* ⛔ A LIVE READ, NOT A COPY DECISION — the same gate the table
               cell uses. If a tier ever moves off $0.00 this stops saying FREE
               in the same deploy. */
            if ($bhp_compare['collection_ships_free']) {
                esc_html_e('FREE', 'brave-hearts');
            } else {
                esc_html_e('Shown at checkout', 'brave-hearts');
            }
          ?></dd>
        </div>
        <div class="bhp-compare__cardrow">
          <dt><?php esc_html_e('What ships', 'brave-hearts'); ?></dt>
          <dd><?php esc_html_e('All three books, together in one shipment', 'brave-hearts'); ?></dd>
        </div>
      </dl>
    </div>

    <div class="bhp-compare__every">
      <p class="bhp-compare__everyhead"><?php esc_html_e('What comes with every order', 'brave-hearts'); ?></p>
      <ul class="bhp-compare__everylist">
        <?php if ($bhp_compare['activity_book_live']): ?>
          <li><?php esc_html_e('The FREE printable Adventure Activity Book', 'brave-hearts'); ?></li>
        <?php endif; ?>
        <?php if ($bhp_compare['vocab_cards_live']): ?>
          <li><?php esc_html_e('The FREE printable Vocabulary Card Activity', 'brave-hearts'); ?></li>
        <?php endif; ?>
        <li><?php esc_html_e('A 30 day guarantee, and the books are kept either way', 'brave-hearts'); ?></li>
        <li><?php esc_html_e('Printed for that order', 'brave-hearts'); ?></li>
      </ul>
    </div>

  </div>

  <?php if ($bhp_cmp_cue): ?>
    <a class="bhp-compare__cue" href="#<?php echo esc_attr('bhp-look-inside-' . sanitize_html_class($bhp_cmp_key)); ?>" data-bhp-gallery-jump>
      <?php if (!empty($bhp_cmp_cue_thumbs)): ?>
        <span class="bhp-compare__cuethumbs" aria-hidden="true"><?php
          foreach ($bhp_cmp_cue_thumbs as $bhp_cmp_i => $bhp_cmp_thumb_id) {
              /*
               * ⛔ ALT IS DELIBERATELY EMPTY. These are 40px decorative chips
               *    inside an `aria-hidden` wrapper, and the link they sit in
               *    already has an accessible name. `wp_get_attachment_image()`
               *    would otherwise print the attachment's full descriptive alt
               *    text into a decoration.
               */
              echo wp_get_attachment_image(
                  $bhp_cmp_thumb_id,
                  'thumbnail',
                  false,
                  array(
                      'class'    => 'bhp-compare__cuethumb' . (0 === $bhp_cmp_i ? '' : ' bhp-compare__cuethumb--2'),
                      'alt'      => '',
                      'width'    => 40,
                      'height'   => 40,
                      'loading'  => 'lazy',
                      'decoding' => 'async',
                  )
              );
          }
        ?></span>
      <?php endif; ?>
      <span class="bhp-compare__cuetext">
        <span class="bhp-compare__cuelabel"><?php esc_html_e('Look inside', 'brave-hearts'); ?></span>
        <span class="bhp-compare__cuesub"><?php
          /*
           * ⛔⛔ THE SUB-LINE NAMES A VIDEO ONLY WHEN ONE RESOLVED. This is a
           *     product claim on a purchase page, not a caption. A title whose
           *     gallery is stills-only says so.
           */
          if ($bhp_cmp_cue_video) {
              esc_html_e('Real pages and a flip-through video, in the gallery at the top of this page.', 'brave-hearts');
          } else {
              esc_html_e('Real pages, in the gallery at the top of this page.', 'brave-hearts');
          }
        ?></span>
      </span>
      <svg class="bhp-compare__cuearrow" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3l5 5-5 5"/></svg>
    </a>
  <?php endif; ?>

  <p class="bhp-compare__note"><?php
    /* translators: 1: single hardcover price, 2: hardcover collection price */
    printf(
        esc_html__('Prices are paperback. Hardcover is %1$s for one book and %2$s for the collection.', 'brave-hearts'),
        esc_html($bhp_cmp_hardcover),
        esc_html($bhp_cmp_coll_hc)
    );
  ?></p>
</section>
