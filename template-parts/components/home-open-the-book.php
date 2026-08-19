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
$bhp_spreads = [
    [
        'slug'    => 'mariana-look-03-depth-diagram-brave-learning',
        'caption' => __('The Mariana Trench: depth diagram and Brave Learning questions', 'brave-hearts'),
        'alt'     => __('Interior spread showing a labelled How Deep Is the Mariana Trench diagram beside the Brave Learning STEM and SEL companion questions.', 'brave-hearts'),
    ],
    [
        'slug'    => 'everest-look-03-how-tall-diagram',
        'caption' => __('Mount Everest: the altitude zones, held open', 'brave-hearts'),
        'alt'     => __('Interior page with a labelled How Tall Is Mount Everest diagram marking the altitude zones from Base Zone to Summit.', 'brave-hearts'),
    ],
    [
        'slug'    => 'amazon-look-02-brave-learning-navy-v2',
        'caption' => __('The Amazon: the canopy, and the questions after it', 'brave-hearts'),
        'alt'     => __('Interior spread showing the Brave Learning companion questions beside a labelled Connected Amazon ecology diagram.', 'brave-hearts'),
    ],
];

$bhp_resolved = [];
foreach ($bhp_spreads as $bhp_spread) {
    $bhp_id = (int) bhp_book_media_attachment_id($bhp_spread['slug']);
    if ($bhp_id > 0) {
        $bhp_spread['id'] = $bhp_id;
        $bhp_resolved[]   = $bhp_spread;
    }
}
unset($bhp_spread);

if (!$bhp_resolved) {
    return; // The gate.
}

$bhp_collection_url = home_url('/complete-collection/');
$bhp_quiz_url       = home_url('/find-your-adventure/');
if (function_exists('bhp_get_safe_link_url')) {
    $bhp_collection_url = bhp_get_safe_link_url($bhp_collection_url, home_url('/complete-collection/'));
    $bhp_quiz_url       = bhp_get_safe_link_url($bhp_quiz_url, home_url('/find-your-adventure/'));
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
      <?php foreach ($bhp_resolved as $bhp_i => $bhp_item): ?>
        <li class="home-open-book__spread">
          <a class="home-open-book__spread-link"
             href="<?php echo esc_url($bhp_collection_url); ?>"
             data-bhp-event="contextual_cta_click"
             data-bhp-source="home_open_the_book_spread">
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
            echo wp_get_attachment_image($bhp_item['id'], 'large', false, [
                'class'    => 'home-open-book__spread-img',
                'alt'      => $bhp_item['alt'],
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 820px) 350px, 360px',
            ]);
            ?>
            <span class="home-open-book__spread-caption"><?php echo esc_html($bhp_item['caption']); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="home-open-book__actions">
      <a class="btn home-open-book__btn home-open-book__btn--primary"
         href="<?php echo esc_url($bhp_collection_url); ?>"
         data-bhp-event="contextual_cta_click"
         data-bhp-source="home_open_the_book">
        <?php esc_html_e('Read the first pages free', 'brave-hearts'); ?>
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
