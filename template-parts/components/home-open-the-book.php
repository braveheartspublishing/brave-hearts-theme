<?php
/**
 * Brave Hearts — "Open the book". Homepage, the read-three-lines moment.
 *
 * CYCLE164-LD-HOMEPAGE-WARMTH (2026-08-18, theme 1.19.241).
 *
 * WHAT THIS IS
 * ------------
 * Move 2 of three on the Homepage Warmth Board (`design-creative` / Legolas,
 * `FD-391`, 2026-08-17): the sample-pages moment promoted out of a commerce
 * card and given a cream section of its own, which also breaks the current
 * run of dark bands below the hero.
 *
 * ⭐ NO NEW ASSET, AND NO NEW MEDIA RECORD. The three interiors are resolved
 *    by SLUG out of the existing `complete_collection` registry in
 *    `inc/book-media.php` — the same three attachments, with the same
 *    registry alt text, that `/complete-collection/`, the product pages and
 *    the homepage's own Look Inside carousel already render. Nothing is
 *    generated, retouched, uploaded or copied.
 *
 * ⛔ THIS IS NOT A SECOND GALLERY. `template-parts/commerce/look-inside.php`
 *    is not called here and is not modified. The one-gallery-per-page rule
 *    that `inc/collection-gallery.php` enforces is untouched: the homepage
 *    still has exactly one interactive gallery, inside the Best Value box.
 *    These are three static photographs that LINK to the real thing.
 *
 * ⛔ FAIL CLOSED. A slug that does not resolve on an environment is skipped,
 *    and if none resolves the whole section renders nothing rather than an
 *    empty cream band with two buttons in it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️⚠️ EVERY SENTENCE BELOW IS NEW CUSTOMER-FACING COPY, WRITTEN BY LEGOLAS
 *      ON THE BOARD ANDREW APPROVED THE LOOK OF. NONE OF IT IS APPROVED AS
 *      COPY YET, AND THE BOARD SAYS SO ITSELF: "Nothing in this column is
 *      approved copy yet." ALL SIX STRINGS ARE LISTED IN THE BUILD REPORT
 *      FOR HIS EYE. NONE ADDS A CLAIM — every one is tone only.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE EM DASHES ARE GONE, AND THAT IS THE ONLY EDIT MADE TO LEGOLAS'S
 *    WORDS. Standing rule §9.1's rail is "no em dashes" in Andrew's copy, and
 *    `front-page.php` already carries the Wave F em-dash purge precedent
 *    (2026-08-03). Four board lines are restructured with a full stop, a
 *    colon or a comma; not one word is changed, added or removed.
 *      board: "Go on, open it —"                     here: "Go on, open it."
 *      board: "…here too — not the hardest…"         here: "…here too, not the hardest…"
 *      board: "The Mariana Trench — depth diagram…"  here: "The Mariana Trench: depth diagram…"
 *      board: "← the question I'd ask you…"          here: same line, arrow kept as a glyph
 *
 * ⛔ THE RED DASHED "FOUNDER REEL" PLACEHOLDER ON BOARD SHEET 4 IS NOT BUILT.
 *    The board marks it "Placeholder — must not survive a build" and states
 *    the Instagram still was never fetched. It is not here, and nothing was
 *    substituted for it.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

if (!function_exists('bhp_book_media_attachment_id')) {
    return;
}

/*
 * Slugs, captions and the order the board shows them in: Mariana, Everest,
 * Amazon. The ALT text is deliberately NOT written here — it is read off the
 * registry entry in `inc/book-media.php` so these three photographs are
 * described identically everywhere on the site, and so a future alt-text
 * correction lands in one place instead of two.
 */
/*
 * ⭐ 1.19.243 (2026-08-19) — CYCLE164-LD-HOMEPAGE-WARMTH-PASS2. THESE ARE THE
 *    REAL FIRST PAGES NOW. 1.19.242 SHIPPED THE WRONG PHOTOGRAPHS.
 *
 * Andrew, on his phone, on 1.19.242: "there isnt the 3 pages of the MT book.
 * The 3 pages are on the same home page which is fine but those pages are not
 * the 1,2,3 first pages of the book. They are the diargrams and learning
 * pages." And on the photographs themselves: "Keep the hands. Pages go in
 * 1,2,3 order."
 *
 * He was right. 1.19.242 resolved three MID-BOOK diagram / Brave Learning
 * spreads — one per title — under a heading that promises "Read the first
 * pages". The section said "first pages" and showed page 40. These three are
 * pages 1, 2 and 3 of The Mariana Trench, in that order.
 *
 * ⭐ THE SLOT CHOICE IS ANDREW'S, NOT MINE. `ASSET-NOTES.txt` left it open as
 *    A) all three slots = the real pages 1-2-3, or B) slot 1 = page 1 and
 *    keep Everest/Amazon in slots 2-3. "Pages go in 1,2,3 order" is option A,
 *    so option A is built. ⚠️ IT COSTS THE THREE-TITLE SHOWCASE: this section
 *    now shows one book. The Everest and Amazon interiors are NOT deleted —
 *    they are still in the registry and still render in the Look Inside
 *    carousel and on /complete-collection/. Raised in the build report.
 *
 * ⭐ SOURCE: real photographs Andrew supplied 2026-08-18, processed by Legolas
 *    under CYCLE164-DES-FIRST-PAGE-PHOTOS (colour conversion, deskew, exposure
 *    lift, unsharp mask, metadata stripped — no AI, no retouching of printed
 *    content, no hand removal). Uploaded to the STAGING media library only,
 *    2026-08-19, as attachments 3382/3383/3384.
 *
 * ⛔ THE ALT TEXT IS NOT WRITTEN HERE, AND THAT IS DELIBERATE. It lives on the
 *    media record (`_wp_attachment_image_alt`), which `wp_get_attachment_image()`
 *    reads by itself. 1.19.242 hardcoded alt into this array while its own
 *    docblock claimed the registry owned it — two sources for one string. One
 *    source now, and it is the one a WordPress editor can actually see.
 *
 * ⛔ CAPTIONS ARE "Page 1/2/3" AND NOTHING MORE. Standing rule §9.1: no claim
 *    about what a page does to a child, no outcome language, no "we".
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.245 (2026-08-19) — CYCLE164-LD-HOMEPAGE-WARMTH-PASS3.
 *    SLOT 1 SHOWS THE WHOLE PAGE. SLOTS 2 AND 3 KEEP THE SQUARE CROP.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew, on his phone, on 1.19.244 (⛔ RELAYED, not witnessed first-hand),
 * verbatim:
 *
 *   "The first page of MT is zoomed in on the chapter icon- this screen shot
 *    to have the icon and all the words on the page. I like the page 2 and 3
 *    cropped images."
 *
 * ⭐ HE IS DESCRIBING A REAL CROP, AND IT WAS CONFIRMED BY OPENING BOTH FILES
 *    RATHER THAN INFERRED FROM THE FILENAMES. The 1200x1200 square crop of
 *    page 1 ends mid-stanza: it holds the open-book chapter icon, "Chapter 1
 *    / The Book", and only the first three lines of body text. The remaining
 *    five printed lines are outside the crop. The full-page master
 *    (`mt-first-pages-01-chapter1-opening-1600.webp`, 1600x2133) holds the
 *    icon AND every printed line, which is exactly what he asked for.
 *
 * ⭐ WHY A PER-SLOT ASPECT RATHER THAN A SECTION-WIDE ONE: his sentence has
 *    two halves and they point in OPPOSITE directions. Page 1 must stop
 *    being cropped; pages 2 and 3 must STAY cropped, because he said he
 *    likes them. So the section now mixes one 3:4 page with two 1:1 crops,
 *    and `aspect` travels per row instead of being a rule of the section.
 *
 * ⛔ THE SQUARE ATTACHMENTS ARE NOT EDITED, REPLACED OR DELETED. 3382 (the
 *    square page 1) still exists untouched; slot 1 simply resolves a
 *    DIFFERENT attachment. If Andrew wants the square page 1 back it is a
 *    one-line slug change.
 *
 * ⭐ THE NEW ATTACHMENT: staging 3385, slug `mariana-trench-page-1-full`,
 *    imported 2026-08-19 from the same Legolas-processed master that produced
 *    the square crop (`CYCLE164-DES-FIRST-PAGE-PHOTOS`; colour conversion,
 *    deskew, exposure lift, unsharp mask, metadata stripped — no AI, no
 *    retouching of printed content, no hand removal). Source md5 verified
 *    identical on both sides of the copy: 27df0ca5a8fc488c01c79616ee337b2d.
 *    STAGING MEDIA LIBRARY ONLY. ⛔ Nothing was uploaded to production.
 *
 * ⛔ THE ALT TEXT IS NOT WRITTEN HERE — same rule as before, it lives on the
 *    media record. 3385's alt was copied VERBATIM from 3382 so the two
 *    photographs of the same page are described identically wherever either
 *    is used.
 *
 * ⛔ NO LIGHTBOX WAS ADDED, AND THE OMISSION IS DELIBERATE RATHER THAN
 *    FORGOTTEN. The brief allowed a tap-to-enlarge lightbox "if the theme
 *    already has one". It does — but the only one is the Look Inside gallery
 *    (`inc/collection-gallery.php` + `assets/js/book-media.js`), and that file
 *    ENFORCES one interactive gallery per page. The homepage's single
 *    gallery is already spent inside the Best Value box, so wiring this
 *    section into it would break the very rule this component was written to
 *    respect (see the docblock at the top of this file). Legibility is
 *    therefore paid for with column width and the `sizes` hint instead, and
 *    the trade is raised in the build report rather than decided here.
 */
$bhp_spreads = [
    ['slug' => 'mariana-trench-page-1-full', 'caption' => __('Page 1', 'brave-hearts'), 'aspect' => 'tall'],
    ['slug' => 'mariana-trench-page-2',      'caption' => __('Page 2', 'brave-hearts'), 'aspect' => 'square'],
    ['slug' => 'mariana-trench-page-3',      'caption' => __('Page 3', 'brave-hearts'), 'aspect' => 'square'],
];

/*
 * ⭐ THE PAGE-1 FALLBACK, AND WHY IT IS A FALLBACK RATHER THAN A SECOND ROW.
 *
 * `mariana-trench-page-1-full` exists on STAGING. It does not exist on
 * production, and it will not until Andrew approves that media moving. On an
 * environment where the full-page attachment is absent, slot 1 falls back to
 * the SQUARE page 1 (3382) so the section still opens with page 1 — a
 * cropped page 1 is worse than the full page, but a MISSING page 1 would
 * start the sequence at page 2, which is a worse failure than the one
 * Andrew reported. The fallback is per-slot and silent by design: it fails
 * to the previous behaviour, never to an empty frame.
 */
$bhp_fallbacks = [
    'mariana-trench-page-1-full' => ['slug' => 'mariana-trench-page-1', 'aspect' => 'square'],
];

$bhp_resolved = [];
foreach ($bhp_spreads as $bhp_spread) {
    $bhp_id = (int) bhp_book_media_attachment_id($bhp_spread['slug']);

    if ($bhp_id <= 0 && isset($bhp_fallbacks[$bhp_spread['slug']])) {
        $bhp_fallback = $bhp_fallbacks[$bhp_spread['slug']];
        $bhp_id       = (int) bhp_book_media_attachment_id($bhp_fallback['slug']);
        if ($bhp_id > 0) {
            // The aspect travels WITH the attachment. A 1:1 crop rendered in
            // a 3:4 frame would letterbox or crop it, which is the defect
            // this whole change exists to remove.
            $bhp_spread['aspect'] = $bhp_fallback['aspect'];
        }
    }

    if ($bhp_id > 0) {
        $bhp_spread['id'] = $bhp_id;
        $bhp_resolved[]   = $bhp_spread;
    }
}
unset($bhp_spread, $bhp_fallback);

if (!$bhp_resolved) {
    return; // The gate.
}

/*
 * ⭐ 1.19.243 — `$bhp_collection_url` IS DELETED, not left dangling. Both of
 *    its consumers (the spread wrapper and the primary button) stopped
 *    pointing at /complete-collection/ in this build, so the variable had no
 *    reader left. A dead URL builder sitting in a file is how the wrong link
 *    gets reintroduced by the next person who sees a convenient variable.
 */
$bhp_quiz_url = home_url('/find-your-adventure/');
if (function_exists('bhp_get_safe_link_url')) {
    $bhp_quiz_url = bhp_get_safe_link_url($bhp_quiz_url, home_url('/find-your-adventure/'));
}
?>
<section id="home-open-the-book" class="homepage-section home-open-book" aria-labelledby="home-open-book-title">
  <div class="container">

    <p class="home-open-book__hand"><?php esc_html_e('Go on, open it.', 'brave-hearts'); ?></p>
    <h2 id="home-open-book-title" class="home-open-book__title"><?php esc_html_e('Read the first pages before you decide.', 'brave-hearts'); ?></h2>

    <?php /* The drawn divider, board sheet 5. Decorative only: it replaces a
             horizontal rule and carries no information, so it is hidden from
             assistive technology. Gold on a light ground is a FLOURISH, never
             something a reader must read (Brand Identity Kit §1.4), and the
             stroke uses gold-deep #9A6A00 rather than #D9A45F for exactly
             that reason -- #D9A45F measures 1.81:1 on parchment. */ ?>
    <svg class="home-open-book__divider" viewBox="0 0 210 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <path d="M3 11.5c30-6 62-7.5 92-5 27 2.2 54 5.6 82 1.4" stroke="#9A6A00" stroke-width="2.4" stroke-linecap="round"/>
      <circle cx="196" cy="7.6" r="2.6" fill="#9A6A00"/>
    </svg>

    <p class="home-open-book__lede"><?php esc_html_e('At the market, people pick a book up and read a few lines before they buy. That should be the easiest thing to do here too, not the hardest thing to find.', 'brave-hearts'); ?></p>

    <ul class="home-open-book__spreads" role="list">
      <?php
      foreach ($bhp_resolved as $bhp_i => $bhp_item):
          /*
           * ⭐ 1.19.245 — THE REAL INTRINSIC RATIO TRAVELS WITH THE ROW, AND
           *    IT EXISTS TO STOP A LAYOUT SHIFT, NOT TO STYLE ANYTHING.
           *
           * ⚠️ THIS FIXES A DEFECT THIS BUILD INTRODUCED AND THEN MEASURED.
           *    The first attempt simply set `aspect-ratio: auto` on the tall
           *    slot to undo the section's `aspect-ratio: 1/1`. Measured on
           *    staging at a real 390px viewport, the page-1 box came back
           *    329.9 x 8.1 px — a collapsed sliver. `aspect-ratio: auto` in
           *    author CSS overrides the UA stylesheet's `auto <ratio>` form,
           *    which is the mechanism by which an <img>'s width/height
           *    ATTRIBUTES reserve space before the file arrives. Killing it
           *    meant a lazy image ~2,400px down the page reserved no height
           *    at all and then jumped ~440px on load. The two square slots
           *    never showed this because their fixed 1/1 ratio reserved
           *    space by itself.
           *
           * ⛔ `aspect-ratio: revert` WAS REJECTED even though it is the
           *    tidiest expression of the intent. If a browser does not
           *    support `revert` the declaration is dropped, the section's
           *    `1/1` survives, and page 1 is silently CROPPED again — a
           *    failure mode that lands on exactly the thing Andrew reported.
           *    An explicit ratio degrades to a correct box everywhere.
           *
           * The value is read off the attachment's own metadata, so it is the
           * file's real shape rather than an assumption about it, and a
           * future replacement photograph of any proportion works unchanged.
           */
          $bhp_ratio_style = '';
          $bhp_src         = wp_get_attachment_image_src($bhp_item['id'], 'full');
          if (is_array($bhp_src) && !empty($bhp_src[1]) && !empty($bhp_src[2])) {
              $bhp_ratio_style = sprintf(
                  'style="--bhp-spread-ratio: %d / %d;"',
                  (int) $bhp_src[1],
                  (int) $bhp_src[2]
              );
          }
      ?>
        <li class="home-open-book__spread home-open-book__spread--<?php echo esc_attr($bhp_item['aspect']); ?>" <?php echo $bhp_ratio_style; // phpcs:ignore WordPress.Security.EscapeOutput -- built from two (int) casts only ?>>
          <?php /*
           * ⭐ 1.19.243 — THE LINK WRAPPER IS GONE, ON PURPOSE.
           *
           * Until 1.19.242 each photograph was an <a> to /complete-collection/.
           * That is half of the exact defect Andrew reported: a reader who
           * clicks a picture of page 1 expecting to read page 1 was taken to a
           * shop page. Now that these ARE pages 1, 2 and 3, there is no better
           * destination to send them to — the content is already on screen.
           * A link that lands somewhere the label did not promise is worse
           * than no link, so it is a plain figure.
           *
           * ⚠️ TRACKING NOTE, stated rather than buried: this removes the
           *    `contextual_cta_click` / `home_open_the_book_spread` emitter.
           *    It is the ONLY event removed in this build. No event is
           *    renamed or re-sourced, and the section's two button emitters
           *    (`home_open_the_book`, `home_open_the_book_quiz`) are untouched.
           */ ?>
          <figure class="home-open-book__spread-figure">
            <?php
            /*
             * `sizes` states the MEASURED ceiling of the rendered box rather
             * than a vw guess -- the same discipline `front-page.php` records
             * for the hero covers after CYCLE144-LD-203. Three columns inside
             * a 1180px container give roughly 360px each on desktop; the
             * column stacks full-width below 820px, where the container is
             * viewport minus 40px, so 350px is the ceiling on a 390px phone.
             * Every spread below the fold stays lazy: this section is never
             * the LCP element.
             */
            /*
             * ⭐ 1.19.245 — THE TALL SLOT REQUESTS `full`, THE SQUARE SLOTS
             *    KEEP `large`, AND THAT ASYMMETRY IS THE POINT.
             *
             * WordPress's `large` is a 1024x1024 BOUNDING box, so a 1600x2133
             * portrait comes out of it at 768x1024 — 768 real pixels of page.
             * The rendered column is ~338px on a 390px phone, which at DPR 3
             * wants ~1014px. 768 would land UNDER that and soften exactly the
             * printed body text Andrew asked to be able to read. `full`
             * (1600x2133, 142 KB) is the only rung above it, and the browser
             * still chooses off the emitted srcset rather than being forced.
             *
             * The two square slots are unchanged at `large`: their 1200x1200
             * source yields 1024x1024, comfortably above the ~1005px a 335px
             * column at DPR 3 asks for. Nothing about pages 2 and 3 moves.
             *
             * ⛔ THE COST IS ACCEPTED KNOWINGLY, NOT OVERLOOKED: this section
             *    sits ~2,600px down the page, every spread stays `lazy`, and
             *    this element is never the LCP. The page-weight trade buys the
             *    one thing the section exists for — legible printed text.
             */
            $bhp_is_tall = ('tall' === $bhp_item['aspect']);
            echo wp_get_attachment_image($bhp_item['id'], $bhp_is_tall ? 'full' : 'large', false, [
                'class'    => 'home-open-book__spread-img',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 820px) 350px, 360px',
            ]);
            ?>
            <figcaption class="home-open-book__spread-caption"><?php echo esc_html($bhp_item['caption']); ?></figcaption>
          </figure>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="home-open-book__actions">
      <?php /*
       * ═══════════════════════════════════════════════════════════════════
       * ⭐ 1.19.245 (2026-08-19) — PASS3. THE SELF-REFERENTIAL CTA IS GONE.
       *    THIS BUTTON NOW SELLS, BECAUSE THE READING HAS ALREADY HAPPENED.
       * ═══════════════════════════════════════════════════════════════════
       *
       * Andrew, on his phone, on 1.19.244 (⛔ RELAYED, not witnessed
       * first-hand), verbatim:
       *
       *   "There is no need to have the same 'Read the first pages free' CTA
       *    right below the actual pages 1-3 thats just not a good CTA - Put
       *    Shop the books and send to shop page."
       *
       * ⭐ HE IS SETTLING THE QUESTION 1.19.243 EXPLICITLY RAISED AND LEFT
       *    OPEN. The PASS2 block that stood here flagged this exact defect
       *    ("a button still labelled 'Read the first pages free' is promising
       *    something the reader has already been given"), fixed only the
       *    broken LINK, and put two replacement labels in the report because
       *    copy is not this agent's to change. He has now chosen — and he
       *    chose neither of the two offered, he supplied his own. His words
       *    are used verbatim, sentence-cased for a button: "Shop the books".
       *    ⛔ The two labels this agent proposed are NOT used and are not
       *       recorded here as alternatives; the founder's own wording wins.
       *
       * ⭐ THE LINK GOES THROUGH WOOCOMMERCE'S OWN RESOLVER, NOT A HARDCODED
       *    PATH. `wc_get_page_permalink('shop')` reads the shop page
       *    WooCommerce actually has configured, so this cannot rot if the
       *    page is ever moved or re-slugged. VERIFIED on staging rather than
       *    assumed: `woocommerce_shop_page_id` is 6, post_name `shop`, and
       *    the resolver returns https://staging2.braveheartspublishing.com/shop/.
       *    ⛔ NO WooCommerce setting was written to check this — the option
       *       and the permalink were READ.
       *
       * ⚠️ TRACKING: THE EMITTER IS RENAMED, HONESTLY AND DELIBERATELY.
       *      before: contextual_cta_click | home_open_the_book
       *      after:  contextual_cta_click | home_open_the_book_shop
       *    The EVENT NAME is unchanged, so no new GTM variable and no new tag
       *    is needed and the delegated handler at `assets/js/nav.js:78` picks
       *    it up untouched. The SOURCE changes because the control changed
       *    what it does: `home_open_the_book` recorded taps on a
       *    read-the-sample control, and continuing to report shop-page traffic
       *    under that name would make the historic series silently
       *    discontinuous. A renamed source shows up as a new series starting
       *    2026-08-19, which is the truth. ⛔ This is the ONLY event changed in
       *    this build; the ghost button's `quiz_cta_clicked` /
       *    `home_open_the_book_quiz` pair is untouched, and no event is
       *    removed.
       */
      $bhp_shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
      if (!$bhp_shop_url) {
          $bhp_shop_url = home_url('/shop/');
      }
      if (function_exists('bhp_get_safe_link_url')) {
          $bhp_shop_url = bhp_get_safe_link_url($bhp_shop_url, home_url('/shop/'));
      }
      ?>
      <a class="btn home-open-book__btn home-open-book__btn--primary"
         href="<?php echo esc_url($bhp_shop_url); ?>"
         data-bhp-event="contextual_cta_click"
         data-bhp-source="home_open_the_book_shop">
        <?php esc_html_e('Shop the books', 'brave-hearts'); ?>
      </a>
      <a class="btn home-open-book__btn home-open-book__btn--ghost"
         href="<?php echo esc_url($bhp_quiz_url); ?>"
         data-bhp-event="quiz_cta_clicked"
         data-bhp-source="home_open_the_book_quiz"
         data-bhp-entry-location="home_open_the_book">
        <?php esc_html_e('Which adventure fits your reader?', 'brave-hearts'); ?>
      </a>
      <?php /* The aside arrow, board sheet 5: "Used once per screen at most --
               it is a wink, and a page full of winks is a smirk." The arrow
               glyph is decorative; the sentence is real text so a screen
               reader gets it. */ ?>
      <p class="home-open-book__aside">
        <span aria-hidden="true">&larr;</span>
        <?php esc_html_e('the question I’d ask you at the table', 'brave-hearts'); ?>
      </p>
    </div>

  </div>
</section>
