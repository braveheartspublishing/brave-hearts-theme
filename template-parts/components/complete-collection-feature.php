<?php
/**
 * The "Best Value — The Complete Collection" feature band.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS FILE EXISTS — 2026-08-05, theme 1.19.171, CYCLE144-LD-12
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (relayed through the Chief of Staff,
 * witnessed by the main session, NOT witnessed first-hand by this agent):
 *
 *   "On the adventure books page - keep it consistent with the homepage -
 *    meaning put the same Best Value - The Complete section with the savings
 *    numbers where is says Get all Three adventures, use the same homepage
 *    one."
 *
 * ⭐ "USE THE SAME HOMEPAGE ONE" IS TAKEN LITERALLY. This file is the
 *    homepage's own markup, MOVED here verbatim — badge, title, body,
 *    primacy line, owner-gated savings line, gallery call and CTA, in that
 *    order, with the same element ids and the same class names. It is not a
 *    lookalike rebuilt from a screenshot, and it is deliberately NOT a fork:
 *    `front-page.php` and `page-books.php` both call THIS file, so the two
 *    surfaces cannot drift apart again. A copy would have been faster and
 *    would have re-created the exact defect Andrew is reporting.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO — read before extending it
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - It computes NO price, discount, shipping figure, tax or total, and it
 *     writes nothing. The two currency figures are the SAME approved
 *     literals the homepage has been carrying since 1.19.169, which come
 *     from `bhp_bundle_rules()` ($4.98 hardcover / $3.98 paperback), and
 *     they stay behind the SAME single owner gate,
 *     `bhp_home_price_cues_enabled()`. One switch, one owner question, now
 *     two surfaces.
 *   - It prints NO SHIPPING FIGURE, before or after this change. That is
 *     what makes it free-shipping-safe under plugin 1.8.23, which took the
 *     complete-collection tier to $0.00: a band that never quoted a
 *     shipping number cannot now be quoting a stale one. (Verified rather
 *     than assumed — `bhp_bundle_shipping_amount()` returns 0.00 on
 *     `is_complete_collection` before the tier table is consulted.)
 *   - It adds NO new customer-facing copy. Every string below already ships
 *     on the homepage today.
 *
 * ⭐ AMENDED 2026-08-05, theme 1.19.174, CYCLE144-LD-42 — the three bullets
 *    above are preserved verbatim and TWO of them are now qualified rather
 *    than corrected, so a reader can see what changed:
 *
 *      · "It PRINTS NO SHIPPING FIGURE" is STILL TRUE and still load-bearing.
 *        No dollar amount of shipping appears anywhere in this file, and
 *        tests/test-wave1-capture.php now asserts that directly rather than
 *        by banning the word.
 *      · "It adds NO new customer-facing copy" is SUPERSEDED by exactly one
 *        sentence — "The complete collection ships free." — added on Andrew
 *        Signore's current-turn order of 2026-08-05 (relayed through the
 *        Chief of Staff; ⚠ RELAYED, not witnessed by this agent). It renders
 *        ONLY while bhp_book_collection_ships_free() confirms the plugin's
 *        own rule is actually $0.00. See the note at the savings line.
 *        ⭐ AMENDED AGAIN 1.19.179 (2026-08-05, CYCLE144-LD-70): that same
 *        sentence now reads "The complete collection ships FREE." with FREE
 *        in a real <strong>, on Andrew's current-turn order. Same gate, same
 *        condition, same absence of any figure — emphasis only.
 *   - It emits no Review or AggregateRating schema, quotes no rating, count,
 *     statistic or urgency claim.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * THE ONE PARAMETER, AND WHY IT IS NOT A FORK
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `cta` selects between the two CTA behaviours that ALREADY EXISTED on the
 * two pages, because both were Andrew's own instructions and neither may be
 * silently discarded by making the pages "the same":
 *
 *   'link'      (default, the homepage)  — a link to /complete-collection/.
 *   'checkout'  (/books/)                — B7, Andrew walk-3 2026-08-03,
 *                                          verbatim: "I want less steps to
 *                                          purchase." Posts the SAME
 *                                          `complete_<format>_smart` action
 *                                          the Collection page posts, with
 *                                          the format read from
 *                                          `bhp_bundle_default_format()`,
 *                                          and lands on /checkout/. Falls
 *                                          back to the plain link if the
 *                                          bundle plugin is not active, so
 *                                          this can never render a dead
 *                                          button.
 *
 * Everything ABOVE the CTA — which is all of what Andrew was looking at — is
 * byte-identical on both pages.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ AMENDED 2026-08-05, theme 1.19.177, CYCLE144-LD-51 — BOTH SURFACES BUY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the
 * Chief of Staff and witnessed by the main session — NOT witnessed
 * first-hand by the agent that wrote this): the homepage "Get the Complete
 * Collection" CTA, and its /books/ twin, must add the collection to the
 * cart and land on the checkout page, like the funnel-page CTAs — not link
 * to /complete-collection/.
 *
 * ⭐ THE `cta` PARAMETER IS NOT REMOVED AND ITS 'link' BRANCH IS NOT
 *    DELETED. The two callers now BOTH pass 'checkout'; the 'link' branch
 *    survives as the plugin-inactive fallback and as the honest record of
 *    what the homepage did before today. The header text above is preserved
 *    verbatim and qualified here rather than rewritten, so a reader can see
 *    that the homepage's old behaviour was a real instruction that a later
 *    real instruction replaced — not an accident being tidied away.
 *
 * ⭐ THE FORMAT TOGGLE IS THE POINT OF THE CHANGE, not decoration. A CTA
 *    that adds a fixed format would put a parent who wants paperback into a
 *    checkout holding three hardcovers. So the band now renders the same
 *    two-button radiogroup the funnel price cards use, pre-selected on
 *    `bhp_book_default_format()` (hardcover, Andrew 2026-07-30), and the
 *    CTA posts `complete_<SELECTED>_smart` via the shipped
 *    `data-bhp-collection-action` sync contract. Same contract, same JS
 *    shape, third surface.
 *
 * ⛔ THE TOGGLE PRINTS NO PRICE, and that is deliberate rather than an
 *    omission. The funnel price cards put a collection total on each format
 *    button; this band must not, because the paperback collection total is
 *    DERIVED and not verified live (CYCLE143-MKT-133) and the band's own
 *    header note already forbids printing one. The buttons say "Hardcover"
 *    and "Paperback" and nothing else.
 *
 * ⛔ STILL NO PRICE, DISCOUNT, SHIPPING FIGURE, TAX OR TOTAL IS COMPUTED
 *    HERE, and nothing is written. The CTA delegates entirely to
 *    `bhp_collection_add_to_cart_cta()` (inc/collection-cta.php), which is
 *    a thin renderer over the bundle plugin's own live contract. The three
 *    books, the discount and the FREE collection shipping are the plugin's,
 *    exactly as before. This file changed WHERE THE CUSTOMER LANDS.
 *
 * ⭐ THE "read about the collection first" ESCAPE HATCH SURVIVES ON BOTH
 *    PAGES (the B7 pattern). A customer who wants to read before buying
 *    still has one click to /complete-collection/.
 *
 * @package brave-hearts
 */
defined('ABSPATH') || exit;

$bhp_cc = wp_parse_args($args ?? [], [
    // 'link' | 'checkout'
    'cta'      => 'link',
    // Section id. Kept per-page so the two pages' existing CSS, anchors and
    // any bookmarked #hash keep working — the homepage has shipped
    // `#home-sales-paths` since Phase 1a.
    'section_id' => 'home-sales-paths',
    // Extra class on the <section>, for page-scoped spacing only.
    'section_class' => '',
    // Heading id, unique per page (two ids would be a duplicate-id defect if
    // both bands ever rendered on one document).
    'title_id' => 'home-collection-feature-title',
]);

$bhp_cc_price_cues_on = function_exists('bhp_home_price_cues_enabled')
    ? bhp_home_price_cues_enabled()
    : false;

$bhp_cc_collection_url = home_url('/complete-collection/');

/*
 * 1.19.177 — can this band render a REAL add-and-checkout control right now?
 *
 * Three conditions, all of them checked rather than assumed, because the
 * failure mode of assuming is a dead button on the highest-value offer on
 * the site:
 *
 *   1. the caller asked for it (`cta === 'checkout'`);
 *   2. the shared renderer is loaded (`inc/collection-cta.php`);
 *   3. the bundle plugin's own contract is actually available on this
 *      install (`bhp_collection_cta_available()` — nonce input, checkout
 *      redirect input and a resolvable catalog).
 *
 * If ANY of them is false the band falls all the way back to the plain
 * `<a href="/complete-collection/">` it shipped with before today. A
 * plugin-less environment sees the pre-2026-08-05 homepage, not a broken
 * form. `bhp_collection_add_to_cart_cta()` fails closed the same way on its
 * own, so this is belt AND braces on purpose: the toggle must not render
 * either, and the toggle is this file's business.
 */
$bhp_cc_can_checkout = ('checkout' === $bhp_cc['cta'])
    && function_exists('bhp_collection_add_to_cart_cta')
    && function_exists('bhp_collection_cta_available')
    && bhp_collection_cta_available();

/*
 * The pre-selected format and the button order both come from
 * `bhp_book_default_format()` / `bhp_book_format_order()` — the theme's
 * SINGLE source of truth, which itself delegates to the bundle plugin. No
 * literal 'hardcover' is written into this file, so this band can never be
 * the surface that disagrees with the Collection page about which format
 * leads.
 */
$bhp_cc_format = function_exists('bhp_book_default_format') ? bhp_book_default_format() : 'hardcover';
$bhp_cc_format_order = function_exists('bhp_book_format_order')
    ? bhp_book_format_order()
    : [$bhp_cc_format, ('hardcover' === $bhp_cc_format ? 'paperback' : 'hardcover')];

/*
 * Two bands never render on one document today, but the ids here are
 * per-page anyway (see `title_id` above) and the toggle's radiogroup needs
 * its own label id. Derived from the section id so it cannot collide.
 */
$bhp_cc_toggle_id = $bhp_cc['section_id'] . '-format-label';
?>
<?php
/*
 * `trim()` is not cosmetic housekeeping. With an empty `section_class` a
 * naive concatenation emits `class="... section "`, and that single trailing
 * space was the ONLY difference between the rendered homepage band before and
 * after this refactor across all 273 lines of it. Trimming it makes the
 * before/after diff exactly empty, which is the evidence that the markup
 * moved without changing.
 */
$bhp_cc_classes = trim('homepage-section home-sales-paths section ' . $bhp_cc['section_class']);
?>
<section id="<?php echo esc_attr($bhp_cc['section_id']); ?>" class="<?php echo esc_attr($bhp_cc_classes); ?>" aria-labelledby="<?php echo esc_attr($bhp_cc['title_id']); ?>">
  <div class="container home-sales-paths__inner">
    <div class="home-collection-feature">
      <span class="home-sales-paths__badge"><?php esc_html_e('Best Value', 'brave-hearts'); ?></span>
      <h2 id="<?php echo esc_attr($bhp_cc['title_id']); ?>" class="home-collection-feature__title"><?php esc_html_e('The Complete Collection', 'brave-hearts'); ?></h2>
      <?php /* Wave F item 11: two em dashes removed. The parenthetical list is
               restructured into two sentences rather than hyphenated. */ ?>
      <p class="home-collection-feature__text"><?php esc_html_e('All three adventures: the Mariana Trench, Mount Everest and the Amazon. In paperback or hardcover, at the best price.', 'brave-hearts'); ?></p>
      <?php
      /*
       * WAVE 1 (2026-08-04) — COLLECTION PRIMACY LINE.
       *
       * ⭐ THE CLAIM-FREE VARIANT SHIPS. Merry's C3, verbatim:
       *    "Best value: all three adventures in one Complete Collection."
       *    It carries no dollar figure, so it is unaffected by F2's "remove
       *    the cost numbers" and stays true through any price change.
       *
       * The FIGURE-BEARING variant (Merry's recommended line, with the
       * approved literals "Save $4.98 in hardcover, $3.98 in paperback"
       * from `bhp_bundle_rules()`) is behind the SAME single flag as the
       * card price cues, because it is the same owner question. Hardcover
       * is named FIRST and that order is load-bearing: the Collection page
       * opens on hardcover by Andrew's 2026-07-30 decision
       * (`bhp_bundle_default_format()`), and the two surfaces must not
       * disagree about which format leads.
       *
       * ⛔ NO COLLECTION TOTAL APPEARS. $48.99 hardcover is verified but
       *    the paperback total is DERIVED, not verified live
       *    (CYCLE143-MKT-133), and a derived total presented as a price is
       *    the derived-claim trap. Neither is printed.
       *
       * ⛔ NO SHIPPING FIGURE APPEARS. See the header note: this is what
       *    keeps the band correct under the 1.8.23 free-collection ruling.
       *
       * ═══════════════════════════════════════════════════════════════════
       * ⭐ 1.19.182 (2026-08-05) — CYCLE144-LD-110. THE PRIMACY LINE IS CUT.
       * ═══════════════════════════════════════════════════════════════════
       *
       * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through
       * the Chief of Staff — NOT witnessed first-hand by the agent that
       * wrote this), verbatim: "fix all the other issues on the homepage
       * while you're at it - redundancies etc", adopting Gandalf's reviewed
       * recommendation to remove this line specifically.
       *
       * ⭐ THE REASON IS THAT EVERY WORD OF IT IS ALREADY ON SCREEN, WITHIN
       *    THE SAME BOX, ABOVE IT:
       *      · "Best value"            → the `home-sales-paths__badge` pill
       *      · "one Complete Collection" → the <h2> two lines up
       *      · "all three adventures"  → the description directly above it,
       *        which also NAMES the three ("the Mariana Trench, Mount
       *        Everest and the Amazon"), so it is strictly more informative.
       *    The line restated three facts in one sentence and added none.
       *    Removing it loses NO information from the page.
       *
       * ⛔ THE SAVINGS LINE IS NOT TOUCHED AND MUST NOT BE. It is the only
       *    line in this box carrying NUMBERS ($4.98 / $3.98) and the FREE
       *    shipping fact, none of which appears anywhere else here. It is
       *    the reason the box is called Best Value, rather than a repeat of
       *    the claim that it is.
       *
       * ⚠ THIS IS A SHARED PARTIAL, SO /books/ LOSES THE SAME LINE — stated
       *   rather than discovered later. That is the direction Andrew already
       *   ruled on 2026-08-05 ("keep it consistent with the homepage ... use
       *   the same homepage one"), and forking the partial to keep one copy
       *   of a redundant line would re-create the defect that instruction
       *   removed.
       *
       * ⛔ THE `.home-collection-feature__primacy` RULE IN style.css IS
       *    DELIBERATELY LEFT IN PLACE. It is inert with no element to match,
       *    and leaving it makes restoring the line a one-line revert of the
       *    markup below rather than a two-file change. Flagged, not absorbed.
       *
       * SUPERSEDED markup, preserved rather than deleted. The PHP open/close
       * tags are written out in words on purpose: a literal pair inside this
       * block comment is legal PHP but is a trap for the comment-stripping
       * regex the test suite runs over this file, and for anyone copying it.
       *
       *   <p class="home-collection-feature__primacy">
       *     [php] esc_html_e('Best value: all three adventures in one
       *                       Complete Collection.', 'brave-hearts'); [/php]
       *   </p>
       */
      ?>
      <?php
      /*
       * ⭐ 1.19.174 (2026-08-05) — CYCLE144-LD-42. THE BAND NOW SAYS THE
       *    COLLECTION SHIPS FREE.
       *
       * Andrew Signore, 2026-08-05, current-turn order relayed through the
       * Chief of Staff (⚠ RELAYED — not witnessed first-hand by this agent):
       * the free-shipping fact was missing from this band, which is the one
       * place on both the homepage and /books/ where the collection is sold.
       *
       * ⛔ THE SENTENCE IS NOT A HARDCODED PROMISE, and the header note above
       *    is amended rather than contradicted by it. That note says this file
       *    "prints NO SHIPPING FIGURE", and it still does not: there is no
       *    dollar amount in the new sentence, before or after this change.
       *    What it now prints is a CONDITION, and only while the condition
       *    holds. `bhp_book_collection_ships_free()` (inc/book-formats.php,
       *    added by this release) asks the PLUGIN whether all three routes to
       *    a complete collection are currently $0.00 — the paperback tier, the
       *    hardcover tier, and the mixed-format `is_complete_collection`
       *    branch. If any of them stops being free, or the plugin is not
       *    loaded, the sentence disappears on the next page load with no copy
       *    edit and no deploy. That is the 1.19.170 discipline applied one
       *    surface further out: the theme never decides what "free" means.
       *
       * ⛔ NO NEW FIGURE ENTERS THE SITE. "$4.98" and "$3.98" are the same
       *    approved literals this line has carried since 1.19.169, unchanged
       *    and in the same order. The new sentence adds a fact, not a number.
       *
       * ⛔ NO EM DASH, no urgency, no scarcity, no countdown, no rating, no
       *    statistic, no collection total.
       */
      ?>
      <?php if ($bhp_cc_price_cues_on): ?>
        <p class="home-collection-feature__savings">
          <?php esc_html_e('Save $4.98 in hardcover, $3.98 in paperback.', 'brave-hearts'); ?>
          <?php if (function_exists('bhp_book_collection_ships_free') && bhp_book_collection_ships_free()): ?>
            <?php
            /*
             * ⭐ 1.19.179 (2026-08-05) — CYCLE144-LD-70. "free" → "FREE", BOLD.
             *
             * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED
             * through the Chief of Staff and witnessed by the main session —
             * NOT witnessed first-hand by the agent that wrote this),
             * verbatim: "In the best value box, make 'The complete collection
             * ships FREE' and put FREE in BOLD."
             *
             * ⛔ BOLD IN MARKUP, NOT IN CSS, and the distinction is the whole
             *    implementation. A `font-weight` rule on the paragraph would
             *    not carry into an email, a scrape, a reader-mode view or a
             *    screen reader's emphasis handling; `<strong>` does. Equally,
             *    the capitals are TYPED, not produced by `text-transform:
             *    uppercase` — a CSS transform leaves the DOM text as "free",
             *    which is what a copy-paste, a translation string and an
             *    assistive technology actually read.
             *
             * ⛔ THE GATE IS UNTOUCHED. This still renders ONLY while
             *    `bhp_book_collection_ships_free()` confirms the plugin's own
             *    rule is $0.00, still inside the same `$bhp_cc_price_cues_on`
             *    owner gate, and still prints NO dollar figure. The sentence
             *    gained emphasis, not a claim.
             *
             * SUPERSEDED line, preserved verbatim:
             *   esc_html_e('The complete collection ships free.', 'brave-hearts');
             *
             * ⚠ THE FULL STOP IS KEPT and sits OUTSIDE the <strong>. Andrew's
             *   quoted target string carries no terminal period, but this
             *   sentence follows "Save $4.98 in hardcover, $3.98 in paperback."
             *   inside the same paragraph, and dropping the stop there would
             *   read as a typo rather than as a decision. Flagged for Andrew;
             *   it is a one-character change either way.
             */
            /*
             * ═══════════════════════════════════════════════════════════
             * ⭐ 1.19.194 (2026-08-05) — AND THE ACTIVITY BOOK. CYCLE144-LD-223.
             * ═══════════════════════════════════════════════════════════
             *
             * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the
             * Chief of Staff; NOT witnessed first-hand by this agent):
             * "I want it clear that you get Free Shipping and a Free
             * Activity book with Collection purchase- on all collection
             * pages and boxes". This band is the shared Best Value box on
             * BOTH the homepage and /books/, so it is one of those boxes.
             *
             * ⭐ TWO GATES, NOT ONE, AND THEY ARE INDEPENDENT. The shipping
             *    half still renders exactly as it did whenever
             *    `bhp_book_collection_ships_free()` is true. The activity
             *    book clause is added ONLY when
             *    `bhp_book_collection_includes_free_addon()` is ALSO true —
             *    i.e. only while the plugin confirms a real, purchasable,
             *    in-stock `BHP-ACTIVITY-BOOK-01` and the offer is on. If
             *    the product is withdrawn, the sentence reverts to the
             *    1.19.179 wording on the next page load with no edit here.
             *
             * ⛔ THE 1.19.179 SENTENCE IS PRESERVED AS THE ELSE BRANCH,
             *    character for character, including the bold FREE, the
             *    typed capitals and the terminal stop outside the
             *    <strong>. This adds a second true fact; it does not
             *    rewrite approved copy.
             *
             * ⛔ NO EM DASH. No number. No urgency, scarcity or countdown.
             *    Both FREEs are typed capitals inside <strong>, for the
             *    same reason the first one is (a `text-transform` leaves
             *    the DOM text lowercase for a screen reader and a copy
             *    -paste).
             */
            $bhp_cc_free_bold = '<strong class="home-collection-feature__free">' . esc_html__('FREE', 'brave-hearts') . '</strong>';

            if (function_exists('bhp_book_collection_includes_free_addon') && bhp_book_collection_includes_free_addon()) {
                printf(
                    /* translators: 1: the word FREE, rendered bold. 2: the word FREE, rendered bold. */
                    esc_html__('The complete collection ships %1$s and includes the Activity Book %2$s.', 'brave-hearts'),
                    $bhp_cc_free_bold,
                    $bhp_cc_free_bold
                );
            } else {
                printf(
                    /* translators: %s: the word FREE, rendered bold. */
                    esc_html__('The complete collection ships %s.', 'brave-hearts'),
                    $bhp_cc_free_bold
                );
            }
            ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <?php
      /*
       * The composite photograph of all three printed books, between the claim
       * and the ask. This section was four lines of text and a button — a
       * "Best Value" claim with no product in it.
       *
       * Renders nothing at all if the media does not resolve on this
       * environment, so the section degrades to exactly its previous markup.
       * See `inc/collection-gallery.php`.
       */
      if (function_exists('bhp_cx_render_collection_gallery')) {
          bhp_cx_render_collection_gallery();
      }
      ?>
      <?php if ($bhp_cc_can_checkout) : ?>
        <?php
        /*
         * ⭐ THE FORMAT TOGGLE — 1.19.177.
         *
         * `role="radiogroup"` + `role="radio"` + `aria-checked`, exactly the
         * pattern already shipping in the funnel price cards
         * (`data-audience-format-btn`), so a screen reader meets a control
         * it has already met elsewhere on this site. `type="button"` is
         * load-bearing: inside a band that also contains a POST form, a
         * default-type button would SUBMIT, and choosing a format would buy
         * the other one.
         *
         * ⛔ THE TOGGLE IS OUTSIDE THE <form>, NOT INSIDE IT. It carries no
         *    name, posts nothing and is not a fallback route to the cart.
         *    The only thing it changes is the value of the form's hidden
         *    action field, via `data-bhp-collection-action`.
         *
         * ⚠ NO-JS BEHAVIOUR, STATED RATHER THAN GLOSSED: with JavaScript
         *   off the buttons do nothing and the CTA buys the DEFAULT format
         *   (hardcover). That is a degraded choice, not a broken purchase —
         *   the customer still reaches a correct three-book checkout, and
         *   the escape-hatch link below reaches the Collection page where
         *   both formats can be bought server-side. Making the toggle a
         *   no-JS control would mean a second POST target, and a second way
         *   to add to a cart is the thing this whole chain exists to remove.
         */
        ?>
        <span id="<?php echo esc_attr($bhp_cc_toggle_id); ?>" class="home-collection-feature__format-label"><?php esc_html_e('Choose your format', 'brave-hearts'); ?></span>
        <div class="home-collection-feature__format-toggle" role="radiogroup" aria-labelledby="<?php echo esc_attr($bhp_cc_toggle_id); ?>" data-bhp-collection-band>
          <?php foreach ($bhp_cc_format_order as $bhp_cc_fmt) :
              $bhp_cc_is_selected = ($bhp_cc_fmt === $bhp_cc_format);
          ?>
            <button type="button" role="radio"
              aria-checked="<?php echo $bhp_cc_is_selected ? 'true' : 'false'; ?>"
              tabindex="<?php echo $bhp_cc_is_selected ? '0' : '-1'; ?>"
              class="home-collection-feature__format-btn<?php echo $bhp_cc_is_selected ? ' is-selected' : ''; ?>"
              data-bhp-band-format-btn="<?php echo esc_attr($bhp_cc_fmt); ?>"><?php
                echo esc_html('paperback' === $bhp_cc_fmt ? __('Paperback', 'brave-hearts') : __('Hardcover', 'brave-hearts'));
            ?></button>
          <?php endforeach; ?>
        </div>
        <?php
        /*
         * The CTA itself is the SHARED renderer — the same function the four
         * funnel pages and the product-page upsell already post through, so
         * there is exactly one place where the nonce, the action and the
         * checkout-redirect flag are decided.
         *
         * `sync => true` marks the hidden action field so the toggle above
         * can rewrite it. That is why this control must NOT be treated as an
         * "in-panel" control: there is no per-format panel here, one button
         * serves both formats, and without the marker a customer who chose
         * paperback would check out holding hardcovers.
         *
         * `bhp-bundle-form` (added by the shared renderer) is what lets
         * bundle-drawer.js intercept, add over the Store API and
         * `location.assign()` to /checkout/ — and its
         * `initBundleFormFeedback()` disables the button on first submit, so
         * a double-tap cannot double-add. With JS off the server handler
         * runs instead and redirects after POST, which is what makes a
         * browser refresh safe. The inline form this replaces carried
         * NEITHER guard, because it lacked that class.
         */
        echo bhp_collection_add_to_cart_cta([ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes every component
            'format'     => $bhp_cc_format,
            'label'      => __('GET THE COMPLETE COLLECTION', 'brave-hearts'),
            'class'      => 'btn btn-primary home-collection-feature__cta',
            'form_class' => 'home-collection-feature__form books-complete-collection-banner__form',
            'sync'       => true,
            'event'      => 'collection_band_add_to_cart',
            'source'     => $bhp_cc['section_id'],
        ]);
        ?>
        <a class="home-collection-feature__browse books-complete-collection-banner__browse" href="<?php echo esc_url($bhp_cc_collection_url); ?>"><?php esc_html_e('Or read about the collection first', 'brave-hearts'); ?></a>
      <?php else : ?>
        <a class="btn btn-primary home-collection-feature__cta" href="<?php echo esc_url($bhp_cc_collection_url); ?>"><?php esc_html_e('GET THE COMPLETE COLLECTION', 'brave-hearts'); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
