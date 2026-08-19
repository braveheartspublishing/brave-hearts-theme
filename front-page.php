<?php
/**
 * Brave Hearts Publishing — Adventure Gateway Homepage
 *
 * Content can be overridden with public bhp_home_* custom fields on the
 * static front page. Repeating card collections are filterable for future
 * structured-content integrations.
 */
defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
    the_post();
}

$page_id = get_queried_object_id();

// Load the live book collection once for the hero preview, destinations, and book grid.
$featured_books = bhp_get_homepage_books(-1);
$find_home_book = static function ($destination) use ($featured_books) {
    $fallback = [];
    foreach ($featured_books as $book) {
        if (stripos($book['title'], $destination) !== false) {
            $formats = is_array($book['formats'] ?? null) ? $book['formats'] : [];
            if (in_array('Paperback', $formats, true) || stripos($book['title'], 'paperback') !== false) {
                return $book;
            }
            if (!$fallback) {
                $fallback = $book;
            }
        }
    }
    return $fallback;
};

$hero_preview_books = array_values(array_filter([
    $find_home_book('Mariana Trench'),
    $find_home_book('Mount Everest'),
    $find_home_book('Amazon'),
], static function ($book) {
    return !empty($book['image_id']) && !empty($book['url']) && !empty($book['title']);
}));
$hero_preview_ids = array_map(static function ($book) {
    return (int) ($book['product_id'] ?? 0);
}, $hero_preview_books);
foreach ($featured_books as $book) {
    $book_id = (int) ($book['product_id'] ?? 0);
    if (count($hero_preview_books) >= 3) {
        break;
    }
    if (!empty($book['image_id']) && !empty($book['url']) && !empty($book['title']) && !in_array($book_id, $hero_preview_ids, true)) {
        $hero_preview_books[] = $book;
        $hero_preview_ids[] = $book_id;
    }
}

// 1. Hero: begin with wonder and invite visitors into the real world.

/*
 * Phase 1a (2026-07-31): product-data preparation hoisted here.
 * It previously sat AFTER the section consuming $adventure_cards; once
 * #explore-world moved above the editorial sections the loop ran before
 * this block existed and rendered zero cards. Now dependency-ordered:
 * data first, then every consuming section. Lookup rules, prices,
 * formats, URLs, images, filters and the paperback preference are
 * byte-identical -- a move, not a rewrite, and exactly one copy.
 */
// 4. Explore the World: destination gateways remain filterable as the series grows.
$mariana_book = $find_home_book('Mariana Trench');
$everest_book = $find_home_book('Mount Everest');
$amazon_book = $find_home_book('Amazon');

// Conversion correction (2026-07-06): these discovery cards now also
// function as commerce entries -- surface age range and both real,
// dynamically-fetched format prices rather than adding a second,
// hand-maintained price list. Every book on this site is sold as two
// separate WooCommerce products (paperback + hardcover, never a single
// variable product with both), so both must be looked up by title
// rather than assumed from one $find_home_book() result.
$find_formats_for_destination = static function ($destination) use ($featured_books) {
    $formats = [];
    foreach ($featured_books as $book) {
        if (stripos($book['title'], $destination) === false) {
            continue;
        }
        $label = stripos($book['title'], 'hardcover') !== false ? __('Hardcover', 'brave-hearts') : __('Paperback', 'brave-hearts');
        if (!empty($book['price'])) {
            $formats[$label] = $book['price'];
        }
    }
    return $formats;
};

/*
 * WAVE 1 (2026-08-04, theme 1.19.169) — PRICE CUE, NOW ON BY OWNER RULING.
 *
 * ⭐ Andrew Signore, 2026-08-04, verbatim: "Turn it on." He was shown the F2
 *    conflict below and reversed F2 knowingly, for the single cue only.
 *    `bhp_home_price_cues_enabled()` now defaults to TRUE, so one live
 *    "From $X" renders per card. NOT DRIFT — CYCLE143-LD-162, CLOSED.
 *
 * ⛔ THE THREE `F2 (Andrew, 2026-08-03)` COMMENTS BELOW ARE PRESERVED
 *    VERBATIM AND ARE STILL PARTLY OPERATIVE: what F2 removed was the
 *    two-line FORMAT PRICE LIST, and that stays removed. `formats_info` is
 *    `[]` on all three cards under any flag. Only the sentence "the price
 *    list is gone from this discovery module" is now narrower than it
 *    reads — the list is gone; a single live cue is back, by his ruling.
 *
 * ⚠️ HISTORICAL, from the 2026-08-04 build, preserved so the reversal is
 *    legible: "F2 ABOVE IS AN OWNER INSTRUCTION AND IT STILL STANDS ... this
 *    build obeys the OWNER and leaves the cue switched off ... each renders
 *    byte-identically to 1.19.167." True when written; superseded same day.
 *
 * ⛔ `formats_info` STAYS EMPTY EITHER WAY. The two-line format price list
 *    F2 removed is not coming back under any flag — the gated cue is a
 *    single "From $X", not a restoration of the list.
 *
 * ⛔ NO PRICE IS TYPED HERE. `bhp_get_home_price_cue()` reads the lowest
 *    LIVE format price already fetched for these cards and lets WooCommerce
 *    format it, so the cue cannot go stale against the store.
 *
 * Recorded for Andrew's decision as CYCLE143-LD-162.
 */
$home_price_cues_on = bhp_home_price_cues_enabled();
$home_price_cue = static function ($destination) use ($home_price_cues_on, $find_formats_for_destination) {
    if (!$home_price_cues_on) {
        return '';
    }
    return bhp_get_home_price_cue($find_formats_for_destination($destination));
};

$adventure_cards = apply_filters('bhp_homepage_adventure_cards', [
    [
        'eyebrow'   => __('Volume I', 'brave-hearts'),
        'title'     => __('The Mariana Trench', 'brave-hearts'),
        /* 1.19.187 (Andrew, 2026-08-05, ruling "imperial"): the hub card's
           stat converts to imperial so it matches the hero destination row's
           "Nearly 7 miles deep" instead of contradicting its units two
           modules apart. 10,935 m x 3.280839895 = 35,875.98 ft -> 35,876 ft.
           A UNIT CONVERSION of the already-approved 10,935 m figure, not a
           new claim -- the metre figure is unchanged and still governs. */
        'location'  => __('11°21\'N 142°12\'E - 35,876 ft down', 'brave-hearts'),
        'text'      => __('<p class="hub-card__question">What glows where sunlight has never reached?</p>', 'brave-hearts'),
        'url'       => !empty($mariana_book['url']) ? $mariana_book['url'] : home_url('/books/'),
        'cta_label' => __('Shop The Mariana Trench', 'brave-hearts'),
        'image_id'  => $mariana_book['image_id'] ?? 0,
        'age_range' => $mariana_book['age_range'] ?? __('Ages 6–9', 'brave-hearts'),
        /* F2 (Andrew, 2026-08-03): "remove the cost numbers" -- the price
           list is gone from this discovery module. Prices still live on
           /books/, on every product page and on /complete-collection/. */
        'formats_info' => [],
        'price_cue' => $home_price_cue('Mariana Trench'),
        'image_size'   => 'woocommerce_single', /* F7: bhp-book-card does not exist for attachments 16/19 -- see the hero note below */
        'image_sizes_attr' => '125px', /* CYCLE144-LD-206 (2026-08-05): MEASURED, not derived.
                                       `190px` was the CSS CAP, not the rendered width -- and it is the cap on
                                       the HEIGHT-driven tile, so it overstated the box by ~52%. A real Chrome
                                       measured `article.card img.card__image` at 124-125 CSS px wide at TEN
                                       viewports (360, 390, 480, 600, 760, 900, 1050, 1280, 1440, 1920) -- flat,
                                       every one of them, because `min(190px, 60%)` resolves to the 60% branch at
                                       every width the site supports. 125px is the truth; it never understates.
                                       Superseded comment, preserved: "B3: the cover tile is capped at
                                       min(190px, 60%) / min(176px, 62%) on home, so a single fixed value is now
                                       the accurate hint" -- the single-fixed-value reasoning was right, the
                                       value was the cap rather than the result. */
        'class'     => 'hub-card--destination',
    ],
    [
        'eyebrow'   => __('Volume II', 'brave-hearts'),
        'title'     => __('Mount Everest', 'brave-hearts'),
        /* 1.19.187: same ruling. 8,849 m = 29,032.15 ft, and 29,032 ft is the
           already-approved published summit figure carried by the hero row
           and by the Everest blog post -- so the card and the hero now read
           the identical number rather than two different unit systems. */
        'location'  => __('27°59\'N 86°55\'E - 29,032 ft up', 'brave-hearts'),
        'text'      => __('<p class="hub-card__question">What can you see from the top of the world?</p>', 'brave-hearts'),
        'url'       => !empty($everest_book['url']) ? $everest_book['url'] : home_url('/books/'),
        'cta_label' => __('Shop Mount Everest', 'brave-hearts'),
        'image_id'  => $everest_book['image_id'] ?? 0,
        'age_range' => $everest_book['age_range'] ?? __('Ages 6–9', 'brave-hearts'),
        /* F2 (Andrew, 2026-08-03): "remove the cost numbers" -- the price
           list is gone from this discovery module. Prices still live on
           /books/, on every product page and on /complete-collection/. */
        'formats_info' => [],
        'price_cue' => $home_price_cue('Mount Everest'),
        'image_size'   => 'woocommerce_single', /* F7: bhp-book-card does not exist for attachments 16/19 -- see the hero note below */
        'image_sizes_attr' => '125px', /* CYCLE144-LD-206 (2026-08-05): MEASURED, not derived.
                                       `190px` was the CSS CAP, not the rendered width -- and it is the cap on
                                       the HEIGHT-driven tile, so it overstated the box by ~52%. A real Chrome
                                       measured `article.card img.card__image` at 124-125 CSS px wide at TEN
                                       viewports (360, 390, 480, 600, 760, 900, 1050, 1280, 1440, 1920) -- flat,
                                       every one of them, because `min(190px, 60%)` resolves to the 60% branch at
                                       every width the site supports. 125px is the truth; it never understates.
                                       Superseded comment, preserved: "B3: the cover tile is capped at
                                       min(190px, 60%) / min(176px, 62%) on home, so a single fixed value is now
                                       the accurate hint" -- the single-fixed-value reasoning was right, the
                                       value was the cap rather than the result. */
        'class'     => 'hub-card--destination',
    ],
    [
        'eyebrow'   => __('Volume III', 'brave-hearts'),
        'title'     => __('The Amazon Rainforest', 'brave-hearts'),
        'location'  => __('3°28\'S 62°13\'W - The green heart', 'brave-hearts'),
        'text'      => __('<p class="hub-card__question">What secrets live in the world\'s green heart?</p>', 'brave-hearts'),
        'url'       => !empty($amazon_book['url']) ? $amazon_book['url'] : home_url('/books/'),
        'cta_label' => __('Shop The Amazon', 'brave-hearts'),
        'image_id'  => $amazon_book['image_id'] ?? 0,
        'age_range' => $amazon_book['age_range'] ?? __('Ages 6–9', 'brave-hearts'),
        /* F2 (Andrew, 2026-08-03): "remove the cost numbers" -- the price
           list is gone from this discovery module. Prices still live on
           /books/, on every product page and on /complete-collection/. */
        'formats_info' => [],
        'price_cue' => $home_price_cue('Amazon'),
        'image_size'   => 'woocommerce_single', /* F7: bhp-book-card does not exist for attachments 16/19 -- see the hero note below */
        'image_sizes_attr' => '125px', /* CYCLE144-LD-206 (2026-08-05): MEASURED, not derived.
                                       `190px` was the CSS CAP, not the rendered width -- and it is the cap on
                                       the HEIGHT-driven tile, so it overstated the box by ~52%. A real Chrome
                                       measured `article.card img.card__image` at 124-125 CSS px wide at TEN
                                       viewports (360, 390, 480, 600, 760, 900, 1050, 1280, 1440, 1920) -- flat,
                                       every one of them, because `min(190px, 60%)` resolves to the 60% branch at
                                       every width the site supports. 125px is the truth; it never understates.
                                       Superseded comment, preserved: "B3: the cover tile is capped at
                                       min(190px, 60%) / min(176px, 62%) on home, so a single fixed value is now
                                       the accurate hint" -- the single-fixed-value reasoning was right, the
                                       value was the cap rather than the result. */
        'class'     => 'hub-card--destination',
    ],
], $page_id);

/*
 * Phase 1a (2026-07-31): the H1 now states what is actually sold. "Big
 * Places. Brave Hearts." is memorable but never explained the product, so it
 * moves to a visible brand signature beneath the supporting copy (see
 * $hero_details below) instead of carrying the H1 alone. No bhp_home_*
 * metadata exists on the front page, so these PHP fallbacks ARE the
 * authoritative homepage content source — no DB update is required.
 */
$hero_title = bhp_get_homepage_field('hero_title', __('Adventure Books That Turn Curiosity Into Courage', 'brave-hearts'));
if (preg_match('/^Big Places\.\s*Brave Hearts\.$/i', trim($hero_title))) {
    $hero_title = __('Adventure Books That Turn Curiosity Into Courage', 'brave-hearts');
}
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.245 (2026-08-19) — CYCLE164-LD-HOMEPAGE-WARMTH-PASS3.
 *    THE HERO EYEBROW IS SUPPRESSED ON THE HOMEPAGE. IT IS NOT DELETED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew, on his phone, on 1.19.244 (⛔ RELAYED through the Chief of Staff,
 * NOT witnessed first-hand by this agent), verbatim:
 *
 *   "For the homepage. The 'Real World...6-9' needs to be removed - it looks
 *    to out of place."
 *
 * ⭐ SUPPRESSION, NOT DELETION, AND THE DISTINCTION IS LOAD-BEARING.
 *    `$hero_eyebrow` is still computed exactly as before and is still a live
 *    variable. What changes is that the homepage stops PASSING it to the hero
 *    component (see the `'eyebrow' => ''` argument at the call site below).
 *    Three reasons this is the right shape:
 *
 *    1. THE COMPONENT IS SHARED. `template-parts/components/hero.php` also
 *       renders /about/, /books/, /contact/, /teachers/,
 *       /explorer-passport/ and every campaign landing page, and each passes
 *       its OWN eyebrow. Removing the eyebrow from the component to satisfy
 *       a homepage instruction would strip six other pages. The component is
 *       NOT touched by this build.
 *    2. THE INSTRUCTION IS EXPLICITLY HOMEPAGE-SCOPED — "For the homepage."
 *       It says nothing about anywhere else, so nowhere else moves.
 *    3. `bhp_get_homepage_field('hero_eyebrow', …)` still reads any DB value
 *       an editor may have set. Deleting the reader would silently discard
 *       editor content and make the field un-restorable without a code
 *       change; leaving it means Andrew can have the strip back by reverting
 *       ONE argument.
 *
 * ⛔ THE AGE CLAIM IS NOT LOST FROM THE PAGE, AND THIS WAS VERIFIED RATHER
 *    THAN ASSUMED. The `Ages 6–9` pill still renders one section below in
 *    `#home-trust-proof` — where Andrew's own 2026-08-05 instruction put it
 *    ("Put the big places. brave hearts under that box along with the Ages
 *    6-9.... Featuring a kirkus reviewed title"). Measured on staging
 *    1.19.244 at a real 390px viewport: the string "REAL-WORLD ADVENTURE
 *    BOOKS FOR AGES" occurred exactly ONCE on the whole page, so this
 *    removes a duplicate framing device and not the only statement of the
 *    reading age. Reading age stays 6–9, never 5–9 (standing rule §9).
 *
 * ⛔ NO COPY IS REWRITTEN. Not one word of the eyebrow string is changed; it
 *    simply stops being rendered here.
 */
$hero_eyebrow = bhp_get_homepage_field('hero_eyebrow', __('REAL-WORLD ADVENTURE BOOKS FOR AGES 6–9', 'brave-hearts'));
if (trim($hero_eyebrow) === 'Bridge books for ages 6–9') {
    $hero_eyebrow = __('REAL-WORLD ADVENTURE BOOKS FOR AGES 6–9', 'brave-hearts');
}
/*
 * Wave F (2026-08-03), item 11 -- EM-DASH PURGE, homepage copy.
 * "…and the Amazon—story-led adventures…" is restructured with a full stop
 * rather than a hyphen pair. Never "--" in customer-facing copy.
 *
 * Wave F, item 9 -- IMPERIAL UNITS. "Nearly 11 km deep" -> "Nearly 7 miles
 * deep" (10,935 m = 6.795 mi, so "nearly 7" is the honest rounding, matching
 * the existing "Nearly 11 km" convention). "8,849 m high" -> "29,032 ft high"
 * (8,849 m = 29,031.8 ft; 29,032 ft is the figure the summit is published at).
 * "A living canopy" is not a unit and is unchanged, per the brief.
 */
$hero_text = __('<p>Follow Charlotte and Henry from the Mariana Trench to Mount Everest and the Amazon. Story-led adventures for family read-alouds and growing independent readers.</p>', 'brave-hearts');
$hero_details = __('<p class="home-hero__signature">Big Places. Brave Hearts.</p><ul class="home-hero__destinations"><li><span>Nearly 7 miles deep</span><small>Mariana Trench</small></li><li><span>29,032 ft high</span><small>Mount Everest</small></li><li><span>A living canopy</span><small>The Amazon</small></li></ul>', 'brave-hearts');

$hero_books_markup = '';
if ($hero_preview_books) {
    ob_start();
    ?>
    <div class="home-hero__book-preview" role="group" aria-labelledby="home-hero-books-label">
      <p id="home-hero-books-label" class="home-hero__book-preview-label"><?php esc_html_e('Real places. Doors into wonder.', 'brave-hearts'); ?></p>
      <ul class="home-hero__book-stack">
        <?php foreach (array_slice($hero_preview_books, 0, 3) as $book): ?>
          <li>
            <a href="<?php echo esc_url($book['url']); ?>" aria-label="<?php echo esc_attr(sprintf(__('Explore %s', 'brave-hearts'), $book['title'])); ?>">
              <?php
              /*
               * F7 / CYCLE142-LD-15 (2026-08-03) — THE SINGLE HEAVIEST
               * DEFECT ON THE MOBILE HOMEPAGE, AND ITS ROOT CAUSE.
               *
               * MEASURED before, at 390 x 844 / DPR 3:
               *   Mariana  ...-417x640.jpg   86 KB  ->  119x173 box  (srcset present)
               *   Everest  ...Everest.jpg   274 KB  ->  124x187 box  (srcset: NONE)
               *   Amazon   ...Cover.jpg     526 KB  ->  119x171 box  (srcset: NONE)
               * A 1318 px-wide JPEG painting a 124 px box is x10.6 linear and
               * roughly x113 in pixel area, on the three covers a phone buyer
               * sees FIRST.
               *
               * ROOT CAUSE, verified rather than inferred: `bhp-book-card` was
               * registered AFTER attachments 16 (Everest) and 19 (The Amazon)
               * were uploaded, so neither has that derivative. WordPress then
               * falls back to the full-size original and emits no srcset at
               * all. Attachment 13 (Mariana) does have it, which is exactly
               * why one of the three behaved correctly and two did not.
               *
               * `woocommerce_single` (600x910 / 600x920) VERIFIED-EXISTS on all
               * three, so all three now get a real srcset and the browser
               * picks from it. No artwork is generated, regenerated, resized
               * or retouched, and no media record is written — this changes
               * only which existing derivative is requested.
               */
              /*
               * CYCLE144-LD-203 (2026-08-05) — THE `sizes` STRING WAS
               * OVERSTATING THE BOX, AND THAT IS WHAT PAID FOR 600 px
               * COVERS.
               *
               * `sizes` is a promise to the browser about how wide this
               * image will actually be laid out. The old value promised
               * `33vw` on phones and `200px` above that. The CSS says
               * otherwise, and the CSS is the truth:
               *
               * ⛔ AND THE CSS IS NOT READ OFF THE STYLESHEET EITHER —
               *    the values below were MEASURED in a real headless
               *    Chrome at ten viewports, because three overlapping
               *    `clamp()` rules across four breakpoints are exactly the
               *    kind of thing that is easy to read wrongly. A first
               *    pass at this comment did read one wrongly: it took the
               *    `@media (max-width: 480px) { width: 92px }` rule at
               *    face value and wrote `92px`, which UNDERSTATES the real
               *    box (97-120 px) at those widths. Measured, per
               *    viewport, widest of the three covers:
               *
               *      360 -> 112    390 -> 120    480 -> 120    600 -> 120
               *      760 -> 124    900 -> 166   1050 -> 166   1280 -> 201
               *     1440 -> 201   1920 -> 201
               *
               * The old value promised `33vw` on phones and `200px` above
               * that. `33vw` on a 390 px phone claims 129 px for a 120 px
               * box, and the browser multiplies that overstatement by
               * device pixel ratio — which is what pushed phones past the
               * 300 w rung onto the 600 w one. Measured cost: 478 KB of
               * covers.
               *
               * The value below states each band's measured CEILING, so
               * it can never claim less space than the layout uses.
               * ⛔ IT DELIBERATELY DOES NOT UNDERSTATE, in either
               *    direction — understating ships soft covers to retina
               *    screens, which is the same defect pointed the other
               *    way. Worked through against the real ladder
               *    (196/198w, 300w, [400w], 417w Mariana only, 600w, ...):
               *      390 px @ DPR 2   -> 125 x 2 = 250 -> picks 300 w
               *      390 px @ DPR 3   -> 125 x 3 = 375 -> picks 400 w if
               *                          the `bhp-hero-cover` derivative
               *                          exists, else 600 w — i.e. exactly
               *                          today's behaviour, never worse
               *      1440 px @ DPR 1  -> 210     = 210 -> picks 300 w
               *      1440 px @ DPR 2  -> 210 x 2 = 420 -> picks 600 w
               *    Retina desktop keeps the full-resolution file. Only
               *    devices that were being over-served stop being.
               *    Nothing about the artwork, the derivative set or the
               *    rendered geometry changes — only the size HINT is
               *    corrected, and only toward the measured truth.
               *
               * Preserved for the record — the superseded value was:
               *   'sizes' => '(max-width: 600px) 33vw, 200px'
               */
              echo wp_get_attachment_image(
                  (int) $book['image_id'],
                  'woocommerce_single',
                  false,
                  [
                      'class'    => 'home-hero__book-cover',
                      'loading'  => 'eager',
                      'decoding' => 'async',
                      'alt'      => $book['title'],
                      'sizes'    => '(max-width: 760px) 125px, (max-width: 1050px) 170px, 210px',
                  ]
              );
              ?>
              <span class="screen-reader-text"><?php echo esc_html($book['title']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php
    $hero_books_markup = ob_get_clean();
}

/*
 * =======================================================================
 * 1.19.241 (2026-08-18) -- CYCLE164-LD-HOMEPAGE-WARMTH.
 *    THE HERO GAINS A PERSON, A DRAWN UNDERLINE, AND TWO INVITATIONS.
 * =======================================================================
 *
 * SOURCE: the Homepage Warmth Board (`design-creative` / Legolas, `FD-391`,
 * 2026-08-17), `Business OS\WORKING-DRAFTS\design-creative\
 * homepage-warmth-board\`. Andrew Signore opened the taste gate on it
 * 2026-08-18 (RELAYED through the Chief of Staff, NOT witnessed first-hand
 * by this agent), verbatim: "Its really good - needs a few tweaks but I
 * really like the redesign lets mock it up on staging then I can nit-pick
 * the details."
 *
 * THIS IS A STAGING MOCK-UP FOR HIS NIT-PICK. It is not an approved
 * homepage, and no line of new copy in it is approved copy.
 *
 * WHAT THE BOARD MEASURED, and why the chip exists: Andrew's face first
 * appears 4,177 px down on desktop and 5,371 px (6.4 screens) down on a
 * phone. The booth first appears at 6,311 px / 8,537 px. The board's own
 * conclusion: "The booth is already on the homepage. It is just 6,311
 * pixels below the fold" -- so the work is promotion and composition, not
 * invention, and it needs no new photograph.
 *
 * THE HERO BACKGROUND PHOTOGRAPH IS DELIBERATELY KEPT. The board's
 * `after-home.html` mock-up drew the hero on a flat radial gradient with no
 * image, because it was a standalone HTML file with no access to the
 * theme's hero media -- not because it proposed removing the ocean. The
 * board's own "what it deliberately keeps" list does not mention it either
 * way, so this is AMBIGUOUS and current behaviour wins: `hero_image_id` is
 * passed exactly as before. Flagged for Andrew.
 *
 * NO HERO TRUST STRIP IS BUILT, and the omission is deliberate rather than
 * forgotten. Three reasons, each independently sufficient:
 *   1. THE BOARD ITSELF SAYS SO. Sheet 1, move 3, verbatim: "The trust
 *      anchors stay exactly where they are." The strip in the mock-up is
 *      that file standing in for `#home-trust-proof`, which it did not
 *      contain.
 *   2. TWO OF ITS FOUR ITEMS ARE ALREADY ON THIS PAGE one section below, in
 *      `#home-trust-proof`: the `Ages 6-9` pill and the "Featuring a
 *      Kirkus-reviewed title" link. Rendering them twice, two screens
 *      apart, is duplication rather than reinforcement -- and that pill's
 *      link to `#kirkus-credibility-home` is what keeps F19's
 *      claim-beside-its-evidence rule working.
 *   3. "30-Day Guarantee - nothing to send back" IS NOT LIVE HOMEPAGE COPY.
 *      It is live on the bundle landing page. The board flags its own
 *      qualifier -- the policy says within 30 days OF DELIVERY, and "if
 *      this strip ships, that anchor goes with it" -- and the word
 *      "guarantee" sits on this theme's OWN forbidden-claim list
 *      (`inc/class-bhp-conversion-scoring.php:61`; and
 *      `class-bhp-author-fingerprint-package.php:256` flags "guarantee
 *      language"). Adding it to the hero on a design pass, without the
 *      qualifier and without Andrew, is not this build's call.
 *   ALL OF THIS IS IN THE BUILD REPORT. If Andrew wants the strip it is a
 *   small additive change, and the reasoning above is what he overrules.
 */
$hero_lead = '';
ob_start();
get_template_part('template-parts/components/home-founder-chip');
$hero_lead = trim(ob_get_clean());

/*
 * THE TWO INVITATIONS.
 *
 * THIS DOES NOT REINSTATE THE HERO CTA ANDREW REMOVED. The superseded
 * arguments block below records exactly why the old hero button went: it
 * and the Best Value box "sold the SAME offer one screen apart". Neither
 * button here sells the collection -- one opens the sample pages, one opens
 * the quiz. The Best Value box, its paperback default and its first-seen
 * price are NOT touched by this change, and its CTA is still the only buy
 * button above the fold.
 *
 * COPY PROVENANCE, string by string:
 *   "Open the book. Read the first pages free"
 *       -- the board's "Open the book - read the first pages free", with the
 *          em dash restructured to a full stop (standing rule 9.1 rail: no
 *          em dashes; and this file already carries the Wave F em-dash purge
 *          precedent of 2026-08-03). NEW COPY. Flagged for Andrew.
 *   "Take the 30-second quiz."
 *       -- NOT new. This exact string, full stop included, is already live
 *          in `template-parts/components/audience-gateway.php:97`, which
 *          renders further down THIS page. Reused verbatim rather than
 *          reworded, so the page says one thing about the quiz.
 *
 * THE SECOND BUTTON IS A LINK TO `/find-your-adventure/`, NOT A SECOND QUIZ
 * LAUNCHER. `assets/js/quiz-modal.js:685` binds `initLauncher` to EVERY
 * `[data-bhp-quiz-launcher]`, and `initLauncher` arms its own auto-open
 * timer, dwell floor and scroll trigger per launcher. A second launcher
 * pointing at the footer's modal would arm that machinery twice and double
 * the modal dataLayer pushes. The canonical quiz page is a real destination
 * and costs nothing.
 *
 * TRACKING: BOTH REUSE EXISTING EVENT NAMES. `contextual_cta_click` and
 * `quiz_cta_clicked` are both already in this site's dataLayer vocabulary,
 * so this needs NO new GTM variable and NO new tag; they are distinguished
 * by `data-bhp-source`. The delegated handler at `assets/js/nav.js:78`
 * picks them up with no JS change at all. NO EXISTING EVENT IS REMOVED,
 * RENAMED OR RE-SOURCED anywhere in this build.
 */
/*
 * ⭐ 1.19.243 (2026-08-19) — CYCLE164-LD-HOMEPAGE-WARMTH-PASS2. THIS LINK NO
 *    LONGER LEAVES THE HOMEPAGE, AND THAT IS THE WHOLE POINT OF THE CHANGE.
 *
 * Andrew, on his phone, on 1.19.242: "When you hit read the first pages it
 * goes direct to the collection page... Also the 'read the first pages' CTA
 * goes to the collection page again - this is all incorrect."
 *
 * He was right, and the defect was mine: 1.19.242 built #home-open-the-book
 * on this very page and then pointed the button that promises it at
 * /complete-collection/. A button labelled "Read the first pages" must show
 * the first pages. It now scrolls to the section that holds them.
 *
 * ⛔ NO JS IS ADDED FOR THIS. `html { scroll-behavior: smooth }` is already
 *    declared at style.css:184 (with the prefers-reduced-motion override at
 *    :2259), and `.home .homepage-section` already carries
 *    `scroll-margin-top: calc(var(--header-height) + var(--space-4))` at
 *    :3411 — so the landing position already clears the sticky header
 *    without a single line of script.
 *
 * ⭐ `bhp_get_safe_link_url()` PASSES FRAGMENTS DELIBERATELY: functions.php
 *    :1192 whitelists `^#[A-Za-z][A-Za-z0-9_-]*$` and returns it untouched.
 *    `#home-open-the-book` matches. The fallback is the same fragment rather
 *    than a URL, so a future rename fails to a dead scroll rather than
 *    silently reopening the exact bug Andrew just caught.
 */
/*
 * ⛔ 1.19.255 (2026-08-19) — CYCLE165-LD-HERO-CTA-FALLBACK. THE LINE BELOW WAS
 *    A DEAD ANCHOR ON PRODUCTION FOR SIX DAYS AND THE FOUNDER FOUND IT.
 *
 * Andrew Signore, 2026-08-19, item 82 (RELAYED through the Chief of Staff,
 * NOT witnessed first-hand by this agent), verbatim: "The main CTA on the home
 * page doesnt even click to to first free pages. Bad link." / "The pages 1,2,3
 * arent even on the homepage!"
 *
 * ⭐ THE SUPERSEDED LINE, QUOTED RATHER THAN SILENTLY REPLACED, because the
 *    comment block above it is still correct about everything EXCEPT this:
 *
 *        $hero_open_url = bhp_get_safe_link_url('#home-open-the-book', '#home-open-the-book');
 *
 *    Its own reasoning said the fallback was "the same fragment rather than a
 *    URL, so a future rename fails to a dead scroll rather than silently
 *    reopening the exact bug Andrew just caught." That reasoning covered a
 *    RENAME. It did not cover the case that actually happened: the id was
 *    never emitted at all, because `home-open-the-book.php` gates on three
 *    Mariana page attachments that exist on staging2 and DO NOT EXIST on
 *    production. Deploy #4 moved theme files; media is not theme files.
 *
 *    ⚠️ MEASURED, NOT ASSUMED — headless-Chrome DOM read of
 *    https://braveheartspublishing.com/ on 2026-08-19: exactly one
 *    `href="#home-open-the-book"`, and ZERO `id="home-open-the-book"`. The
 *    only other fragment links on that document are `#main`,
 *    `#kirkus-credibility-home` and the quiz's JS-populated `href="#"`, and
 *    the first two targets both exist.
 *
 * ⭐ WHAT CHANGES: the VALUE of one variable. Nothing else on this button
 *    moves — not the label, not `data-bhp-event`, not
 *    `data-bhp-source="home_hero_open_book"`, not a class, not the wrapper.
 *    `assets/js/nav.js`'s delegated handler and every GTM tag are untouched.
 *
 * ⭐ `bhp_home_first_pages_anchor()` (inc/book-media.php) picks the best
 *    fragment that WILL be in this document: the first-pages section if its
 *    own gate opens, else the Look Inside gallery inside the collection band,
 *    else that band's own section id. All three are homepage sections and the
 *    last one is emitted unconditionally, so this expression cannot produce a
 *    fragment with no target. The literal second argument is the same floor,
 *    stated again for the case where the helper is somehow absent.
 *
 * ⛔ `/complete-collection/` IS DELIBERATELY NOT A CANDIDATE. Andrew rejected
 *    that exact destination for this exact button in 1.19.242.
 */
$hero_open_url = bhp_get_safe_link_url(
    function_exists('bhp_home_first_pages_anchor') ? bhp_home_first_pages_anchor() : '#home-sales-paths',
    '#home-sales-paths'
);
$hero_quiz_url = bhp_get_safe_link_url(home_url('/find-your-adventure/'), home_url('/find-your-adventure/'));
/*
 * ⭐ 1.19.251 (2026-08-19) — CYCLE164-LD-HOMEPAGE-WARMTH-PASS8. THE TWO
 *    INVITATIONS NOW RENDER IN TWO CONTAINERS, IN TWO DIFFERENT HERO SLOTS.
 *
 * Andrew, on 1.19.250, his own devices, verbatim:
 *   "The books on mobile are still too small and the CTA on desktop is still
 *    below the fold. Put the CTA above the paragraph then on desktop."
 *
 * ⛔ NOT ONE CHARACTER OF EITHER BUTTON'S COPY, href, EVENT NAME OR
 *    `data-bhp-*` ATTRIBUTE CHANGES HERE. The same two anchors, the same two
 *    strings, the same `contextual_cta_click` / `quiz_cta_clicked` events and
 *    the same `data-bhp-source` values. Only the wrapper they sit in moves,
 *    so `assets/js/nav.js`'s delegated handler still picks both up with no JS
 *    change and no GTM change.
 *
 * WHY A DOM SPLIT RATHER THAN CSS `order` ON ONE CONTAINER, and it is an
 * ACCESSIBILITY answer, not a taste one. The covers are THREE REAL LINKS
 * (`.home-hero__book-stack li > a`, one per product page) -- probed in the
 * browser, not read from source. Reordering a single invitations container
 * with `order` at <=600px therefore puts the primary invitation VISUALLY
 * above three links that still precede it in the DOM, so a keyboard user
 * tabs the covers first and then jumps back up to the button. MEASURED: tab
 * sequence cover/cover/cover/PRIMARY/GHOST against a visual sequence of
 * PRIMARY/cover/cover/cover/GHOST. Splitting the containers puts the primary
 * invitation before the fan IN THE DOM instead, so tab order and visual order
 * agree at every width at or below 1050px.
 *
 * PASS7's rule still holds and is not being abandoned: the ONE element still
 * moved by `order` is `.home-hero__text`, which contains ZERO focusable
 * elements -- and that move is now confined to >=1051px, where the desktop
 * layout wants both buttons above the paragraph.
 */
ob_start();
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.263 (2026-08-19) — CYCLE165-LD-DIRECTION1-STEP4-HOME. THE FIELD
 *    RULE ENTERS THE HERO, AND NOTHING ELSE IN THE HERO MOVES.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Board sheet 4, homepage panel: "A drawn field rule sits between headline
 * and CTA." Board sheet 3, marker ③: "A drawn field rule. Dive mask, ice
 * axe, river — one per book, from the existing line-art set."
 *
 * ⭐ WHY IT IS PREPENDED TO `$hero_after_title` RATHER THAN GIVEN ITS OWN
 *    HERO ARGUMENT. `template-parts/components/hero.php` is SHARED by
 *    /about/, /books/, /contact/, /teachers/ and the audience landing
 *    pages. `after_title` already renders in exactly the slot the board
 *    asks for — after the H1, before the aside — and it already defaults to
 *    '' for every other caller. Adding a seventh argument would be a
 *    sitewide component change made to satisfy a homepage-only instruction,
 *    which is the trade CYCLE144-LD-70 wrote down and refused. THE HERO
 *    COMPONENT IS NOT TOUCHED BY THIS STEP.
 *
 * ⛔ NOT ONE CHARACTER OF HERO COPY CHANGES HERE, AND THAT IS THE WHOLE
 *    CONSTRAINT ON THIS STEP. The chip, the founder line (FD-460, locked),
 *    trust line A (FD-469, locked), the H1, the primary CTA's label, the
 *    quiz CTA's label and the subcopy are byte-identical to 1.19.262. The
 *    rule is `aria-hidden`, contains no text node and is not a link.
 *
 * ⛔ THE BOARD'S WORDS BESIDE THIS RULE ARE NOT SHIPPED. Sheet 4's 1440 mock
 *    sets "three real places" in italic to the right of the marks, and sheet
 *    3's 390 mock sets "WHAT WOULD YOU FIND DOWN THERE?" under the covers.
 *    Both are UNAPPROVED COPY — the board's own README classes them as
 *    proposals. They are carried to Gandalf in the build report as PROPOSED
 *    and are deliberately absent from this build.
 *
 * ⛔ IT CANNOT BECOME A SECOND ABOVE-FOLD PRIMARY. It is a `<div>` with
 *    three `<span>`s: no `<a>`, no `<button>`, no href, no `data-bhp-event`,
 *    no text node. The above-fold primary count at 390 is 1 before and 1
 *    after — measured, not asserted.
 *
 * ⛔ THE MARKS ARE A BACKGROUND IMAGE, NOT INLINE SVG, AND THAT IS FORCED BY
 *    THIS SLOT. `hero.php` runs `after_title` through `wp_kses_post()`, whose
 *    'post' allowlist contains no `svg` element — inline marks would be
 *    silently stripped here exactly as `srcset` was in CYCLE144-LD-205. The
 *    alternative was widening the SITEWIDE kses allowlist to admit SVG for
 *    the sake of a decoration. See `inc/field-marks.php`'s header.
 *
 * MEASURED CONSEQUENCE, recorded because "it adds height" is exactly the
 * kind of claim that should carry a number. staging2 1.19.262, headless
 * Chrome, asserted innerWidth 390, scrollY 0: H1 240..345, primary CTA top
 * 361, quiz CTA top 847 against an 844 fold. The rule occupies the 16px gap
 * plus its own margins, so everything below it moves DOWN — which moves the
 * quiz FURTHER below the fold, never toward it. The after-measurement is in
 * the build report.
 */
?>
<?php echo function_exists( 'bhp_field_rule' ) ? bhp_field_rule( 'home-hero' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup built in inc/field-marks.php from esc_attr()'d path data ?>
<div class="home-hero__invitations home-hero__invitations--primary">
  <a class="btn home-hero__invite home-hero__invite--primary"
     href="<?php echo esc_url($hero_open_url); ?>"
     data-bhp-event="contextual_cta_click"
     data-bhp-source="home_hero_open_book"><?php esc_html_e('Open the book. Read the first pages free', 'brave-hearts'); ?></a>
</div>
<?php
$hero_after_title = trim(ob_get_clean());
ob_start();
?>
<div class="home-hero__invitations home-hero__invitations--ghost">
  <a class="btn home-hero__invite home-hero__invite--ghost"
     href="<?php echo esc_url($hero_quiz_url); ?>"
     data-bhp-event="quiz_cta_clicked"
     data-bhp-source="home_hero_quiz"
     data-bhp-entry-location="home_hero"><?php esc_html_e('Take the 30-second quiz.', 'brave-hearts'); ?></a>
</div>
<?php
$hero_after_text = trim(ob_get_clean());

/*
 * SUPERSEDED 2026-08-05 by CYCLE144-LD-70 (below). Preserved verbatim
 * because it records a real earlier instruction, not an accident:
 *
 *   // Conversion correction (2026-07-06): Complete Collection is now the
 *   // principal commercial action in the hero, matching the header CTA and
 *   // the sales-paths card below it. "Choose Your First Adventure" moves to
 *   // a secondary link leading to individual-book discovery, rather than
 *   // being the hero's only/primary action.
 *   $hero_primary_label = bhp_get_homepage_field('hero_primary_label', __('Get the Complete Collection', 'brave-hearts'));
 *   if (in_array(trim($hero_primary_label), ['Shop the Books', 'Choose a Real-World Adventure', 'Choose Your First Adventure'], true)) {
 *       $hero_primary_label = __('Get the Complete Collection', 'brave-hearts');
 *   }
 */
/*
 * Wave F (2026-08-03), item 7 -- HERO DEDUPE. The second descriptive
 * paragraph is REMOVED, not reworded: it restated the first paragraph's job
 * ("for read-alouds, growing independent readers") almost verbatim, and the
 * ages 6-9 claim it carried is already stated in the hero eyebrow directly
 * above the H1 ("REAL-WORLD ADVENTURE BOOKS FOR AGES 6-9"). Nothing factual
 * is lost from the page.
 *
 * What now bridges the copy to the CTAs is the line already rendered by
 * `.home .home-hero__text > p::after` in style.css -- "It is an invitation to
 * look up." It sat BELOW this paragraph and is now the last thing read before
 * the buttons, which is what it was written to do.
 *
 * ⭐ AMENDED 2026-08-05 (1.19.179), NOT corrected: there are no buttons any
 *    more. The sentence above is preserved because its POINT survives intact
 *    and is now literally true of the whole hero -- "It is an invitation to
 *    look up." is the LAST THING IN THE HERO, and what it now bridges to is
 *    the Best Value box immediately below. See CYCLE144-LD-70.
 *
 * The hero component's `commercial_subtext` ARGUMENT is deliberately left in
 * place (default ''), because /about/, /books/, /contact/ and /teachers/ all
 * call the same component; removing the argument would be a shared-component
 * change to satisfy a homepage-only instruction.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.179 (2026-08-05) — CYCLE144-LD-70. THE HERO LOSES BOTH BUTTONS,
 *    AND THE BRAND LINE MOVES BELOW THE BEST VALUE BOX.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the
 * Chief of Staff and witnessed by the main session — NOT witnessed
 * first-hand by the agent that wrote this), verbatim:
 *
 *   "Remove the hero section CTA 'Get the complete collection' and 'Find
 *    their first adventure' - Because right below is the best value box
 *    with the collection.- Right under 'Its an invitation to look up. Put
 *    the Best Value box. Too much redundancy. Put the big places. brave
 *    hearts under that box along with the Ages 6-9.... Featuring a kirkus
 *    reviewed title"
 *
 * ⭐ THE REASON IS THE INSTRUCTION'S OWN, AND IT IS CORRECT ON THE CODE:
 *    the hero's primary button and the Best Value box sold the SAME offer
 *    one screen apart, and since 1.19.177 the box's own CTA adds the three
 *    books and lands on /checkout/ while the hero button only LINKED to
 *    /complete-collection/. The weaker duplicate goes. Nothing is added to
 *    replace it and no new copy enters the page.
 *
 * ⛔ NOTHING IS DELETED, ONLY MOVED OR DROPPED WHERE ANDREW SAID TO DROP IT.
 *    - "Find Their First Adventure" pointed at `#explore-world`. That
 *      section still renders lower on this page and is still reachable from
 *      the audience-gateway band and the three destination cards. One
 *      duplicate link to it is removed; no anchor, section or route is.
 *    - `$hero_details` ("Big Places. Brave Hearts." + the three destination
 *      stats) is MOVED, not copied and not rewritten — it is rendered
 *      exactly once, below the band, in `#home-trust-proof`. Its markup
 *      string is byte-identical to what the hero was passing.
 *
 * ⭐ THE HERO COMPONENT IS NOT CHANGED. `template-parts/components/hero.php`
 *    already renders `.home-hero__actions` only when a link is passed, and
 *    only when `details` is non-empty. /about/, /books/, /contact/ and
 *    /teachers/ all pass their own and are untouched. Dropping three
 *    arguments HERE is a homepage-only change; editing the component would
 *    have been a sitewide one to satisfy a homepage instruction.
 *
 * SUPERSEDED ARGUMENTS, quoted rather than silently dropped:
 *
 *   'details'        => $hero_details,
 *   'primary_link'   => [
 *       'url'   => bhp_get_homepage_field('hero_primary_url', home_url('/complete-collection/')),
 *       'label' => $hero_primary_label,
 *   ],
 *   'secondary_link' => [
 *       'url'   => '#explore-world',
 *       'label' => __('Find Their First Adventure', 'brave-hearts'),
 *   ],
 */

get_template_part('template-parts/components/hero', null, [
    'id'             => 'home-hero',
    /*
     * ⭐ 1.19.245 — PASS3. The homepage passes an EMPTY eyebrow so the
     * component's own `if ($args['eyebrow'])` guard skips the <p> entirely.
     * `$hero_eyebrow` above is deliberately still computed and still holds
     * the string — see the long block at its assignment for why this is a
     * suppression rather than a deletion, and for the verification that the
     * `Ages 6–9` claim still appears on this page in `#home-trust-proof`.
     * ⛔ Restoring the strip is a one-token change: `'' ` -> `$hero_eyebrow`.
     */
    'eyebrow'        => '',
    'title'          => $hero_title,
    'text'           => $hero_text,
    'image_id'       => (int) bhp_get_homepage_field('hero_image_id', 0),
    'class'          => $hero_preview_books ? 'home-hero--with-books' : '',
    'aside'          => $hero_books_markup,
    // Mobile reading order (2026-07-31): covers directly under the H1, so a
    // phone visitor sees the actual product before any further copy. Real DOM
    // move, homepage only -- every other hero caller keeps the default false.
    'aside_after_title' => true,
    /*
     * CYCLE164-LD-HOMEPAGE-WARMTH (1.19.241). Three homepage-only slots on
     * the shared hero component, every one of which defaults to '' for the
     * six other callers. See the block above `$hero_lead` for provenance,
     * and for what was deliberately NOT built.
     *
     * `title_emphasis` is the ONE word the drawn underline sits under; the
     * board's rule is "one word per headline, never two". If a
     * `bhp_home_hero_title` override ever removes that word, `hero.php`
     * renders the plain heading -- it fails to unmarked, never to broken.
     */
    'lead'              => $hero_lead,
    'title_emphasis'    => __('Curiosity', 'brave-hearts'),
    // 1.19.251 PASS8 -- primary invitation, DOM-placed before the book fan.
    'after_title'       => $hero_after_title,
    'after_text'        => $hero_after_text,
]);

/*
 * 1b. THE BEST VALUE BOX NOW FOLLOWS THE HERO IMMEDIATELY — see the
 * CYCLE144-LD-70 block above. The trust-proof strip that used to sit here
 * has moved BELOW it, and the brand signature travels with it.
 *
 * SUPERSEDED placement note, preserved verbatim:
 *
 *   // 1b. Trust proof near the first purchase decision -- approved claims only.
 *   // Placed before the sales-path commerce section per the approved homepage
 *   // sequence (trust/proof strip precedes the Choose Adventure / Complete
 *   // Collection commerce section).
 */

// 1c. Sales paths: route visitors into one of three clear paths, per the
// approved Conversion UX Addendum. A new, distinct section rather than a
// change to the hero component above (homepage hero is Andrew's approved
// design source-of-truth) -- purely additive. The complete-series path is
// visually strongest (gold "Best Value" treatment, first position),
// matching the addendum's explicit instruction.
?>
<?php
/*
 * Phase 1a (2026-07-31): this band was three competing pathway cards
 * (Complete Collection / "Choose Your First Adventure" / Teachers). It is now
 * ONE focused Complete Collection feature, because the three real product
 * cards immediately below it now serve the "choose your first adventure" job
 * and the teacher path already exists in the Teachers & Families section
 * lower on the page. Same section id and same base classes, so existing
 * spacing/CSS keeps applying; the destination and the "Best Value" treatment
 * are unchanged. No pricing, bundle or plugin logic is touched here.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.171 (2026-08-05) — THE MARKUP MOVED, IT DID NOT CHANGE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew, 2026-08-05: "On the adventure books page - keep it consistent with
 * the homepage ... use the same homepage one." Everything that used to be
 * inline here is now `template-parts/components/complete-collection-feature.php`
 * and `/books/` calls the SAME file. Nothing was reworded, re-ordered,
 * re-classed or re-ided in the move: the section id, the heading id, every
 * class, both currency literals, the owner gate and the gallery call are all
 * exactly what shipped in 1.19.170. Diff the partial against 1.19.170's
 * front-page.php to confirm.
 *
 * ⛔ THE SAVINGS LINE IS STILL BEHIND `bhp_home_price_cues_enabled()`, which
 *    the partial reads for itself. Moving an owner gate is how an owner gate
 *    gets lost, so the partial does not accept it as an argument.
 */
/*
 * ⭐ 1.19.177 (2026-08-05) — CYCLE144-LD-51. `'link'` → `'checkout'`.
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the
 * Chief of Staff and witnessed by the main session — NOT witnessed
 * first-hand by this agent): the homepage "Get the Complete Collection"
 * CTA must add the collection to the cart and land on the checkout page,
 * like the funnel-page CTAs, instead of linking to /complete-collection/.
 *
 * ⭐ ONE ARGUMENT CHANGED. Nothing else on this page moved: not the
 *    section id, not the heading, not a class, not a currency literal, not
 *    the owner gate, not the gallery call, not the Kirkus block below.
 *    The band component owns the CTA, the new format toggle and the
 *    plugin-inactive fallback, and /books/ gets the identical treatment
 *    from the identical file — which is exactly why the band was made a
 *    shared partial in 1.19.171 rather than copied.
 *
 * ⛔ THIS PAGE STILL COMPUTES NO PRICE, DISCOUNT, SHIPPING FIGURE OR
 *    TOTAL, and this change writes nothing. The three books, the bundle
 *    discount and the FREE collection shipping remain the plugin's.
 *
 * ⭐ THE ROUTE TO /complete-collection/ IS NOT LOST. The band renders a
 *    "Or read about the collection first" link directly beneath the CTA
 *    for a visitor who wants to read before buying (the B7 pattern).
 */
get_template_part('template-parts/components/complete-collection-feature', null, [
    'cta' => 'checkout',
]);
?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.179 (2026-08-05) — CYCLE144-LD-70. THE SAME SECTION, ONE PLACE
 *    LOWER, PLUS THE BRAND LINE THE HERO USED TO CARRY.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the
 * Chief of Staff and witnessed by the main session — NOT witnessed
 * first-hand by the agent that wrote this), verbatim: "Put the big places.
 * brave hearts under that box along with the Ages 6-9.... Featuring a
 * kirkus reviewed title".
 *
 * ⭐ A MOVE, NOT A REWRITE. Every badge below — the `Ages 6–9` pill, the
 *    Boise placement pill, the five-star pill with its screen-reader text,
 *    and the Kirkus pill with its `#kirkus-credibility-home` anchor — is
 *    byte-identical to what shipped in 1.19.178, comments included. The
 *    section id, the section classes, the `aria-label` and the inner
 *    container are unchanged, so every CSS rule and every bookmarked
 *    `#home-trust-proof` hash keeps working.
 *
 * ⭐ THE KIRKUS PILL IS STILL A LINK TO THE SAME SECTION, and that section
 *    still renders immediately below this one. The claim and its evidence
 *    are still one click and one section apart — moving both together is
 *    what preserves F19 (Andrew, walk-2 2026-08-03: Kirkus sits against the
 *    Collection offer). The quote still renders exactly once.
 *
 * ⭐ `$hero_details` IS RENDERED HERE INSTEAD OF INSIDE THE HERO, ONCE.
 *    It is the same string, unchanged: the `.home-hero__signature` brand
 *    line Andrew named, followed by the three `.home-hero__destinations`
 *    stats that have always lived in the same block with it. It is wrapped
 *    in `.home-brand-proof` rather than `.home-hero__details` DELIBERATELY:
 *    `style.css` hides `.home .home-hero__details` at ≤768px and re-shows it
 *    only under `#home-hero`, so reusing that class here would have made the
 *    brand line vanish on every phone. The new wrapper reproduces the old
 *    responsive behaviour explicitly (stats hidden ≤768px, signature not).
 *
 * ⚠ JUDGEMENT CALL, DISCLOSED RATHER THAN BURIED: Andrew named the brand
 *   line and the proof pills. He did not mention the three destination
 *   stats, which live inside the same `$hero_details` markup string. They
 *   travel with it, because splitting the string would have left them as
 *   the hero's new last element and broken the "hero ends at 'It is an
 *   invitation to look up.'" half of the same instruction. Nothing is
 *   duplicated and nothing is dropped. If Andrew wants the stats to stay in
 *   the hero instead, it is a one-line revert of this echo plus the
 *   `.home-brand-proof` rules in style.css.
 */
?>
<section id="home-trust-proof" class="homepage-section home-trust-proof" aria-label="<?php esc_attr_e('Why parents trust Brave Hearts', 'brave-hearts'); ?>">
  <div class="container home-brand-proof"><?php echo wp_kses_post($hero_details); ?></div>
  <div class="container home-trust-proof__inner">
    <span class="home-trust-proof__badge"><?php esc_html_e('Ages 6–9', 'brave-hearts'); ?></span>
    <?php
    /*
     * N4 (2026-08-03) — THE NUMBER LEAVES THE CLAIM, THE CLAIM STAYS TRUE.
     *
     * Andrew, production walk, verbatim: "It was placed in 40 boise
     * classrooms - that number is going to change constantly - whats another
     * way to say it without a number", and of the options put to him:
     * "number 1 for sure" — i.e. "Placed in classrooms across Boise".
     * (Relayed through the Chief of Staff; NOT witnessed first-hand here.)
     *
     * ⭐ THIS IS NOT A SOFTENING AND NOT A RETRACTION. The claim was attested
     *    TRUE at 40 as of 2026-08-03. What changes is that the standing form
     *    no longer carries a count that goes stale between page loads and
     *    school terms — which is what makes it durable rather than a figure
     *    somebody has to remember to re-verify. C24 (the "40 classrooms"
     *    worked case) closes on this wording.
     *
     * ⛔ NO NEW CLAIM IS INTRODUCED. "Placed" is the same verb the 2026-07-16
     *    Sprint A correction settled on deliberately: placement is the
     *    defensible fact. Classroom USE, reading frequency and outcomes are
     *    still not claimed anywhere, and nothing here starts claiming them.
     */
    ?>
    <span class="home-trust-proof__badge"><?php esc_html_e('Placed in classrooms across Boise', 'brave-hearts'); ?></span>
    <?php /* 2026-07-30: this pill previously carried the --gold modifier, which
             gave it a different background, border and text colour from its
             three neighbours. It now uses the shared badge treatment, with gold
             confined to the stars themselves. The stars are decorative
             (aria-hidden) because they are a glyph run, not text a screen
             reader should spell out; the rating is conveyed by real text
             instead. */ ?>
    <span class="home-trust-proof__badge">
      <span class="home-trust-proof__stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
      <span class="screen-reader-text"><?php esc_html_e('5 out of 5 stars', 'brave-hearts'); ?></span>
      <?php /* 2026-08-02: scoped from the unqualified "Five-star reader
               reviews". Three titles are on sale; only two of them have any
               reader reviews at all. The approved registry
               (inc/amazon-reviews.php) holds four 5-star reviews for The
               Mariana Trench and two for Mount Everest, and ZERO for The
               Amazon -- so an unqualified badge on a page showing all three
               covers implied review proof that does not exist for the newest
               book. The stars, the screen-reader text and the schema are
               deliberately UNCHANGED: whether a bare glyph run reads as an
               aggregate rating is CYCLE140-CX-9, which is Andrew's call, not
               this change's. */ ?>
      <?php /* ⭐ 1.19.251 PASS8 -- "our" -> "my", standing rule 9.1 (the voice
               rule), on Andrew Signore's explicit approval of 2026-08-19:
               "I agree to make the change from we to I".

               ⭐ THE "FIVE-STAR" CLAIM WAS RE-VERIFIED LIVE BEFORE THIS EDIT,
               because rewording a review claim without checking it is the
               never-invent failure class. Read from the live Amazon product
               pages in a real browser on 2026-08-19 (DOM read of
               `#averageCustomerReviews` / `#acrPopover`, not a document, not
               the registry in inc/amazon-reviews.php):
                 The Mariana Trench (B0GQCCPZLL)  5.0 out of 5 stars, 26 ratings
                 Mount Everest      (B0GWJ4PNPZ)  5.0 out of 5 stars,  4 ratings
               Both averages are exactly 5.0, so "Five-star" is TRUE of both of
               the first two titles and the wording stands. Had either been
               below 5.0 the phrase would have had to change, not just the
               pronoun. Recheck when either rating count moves. */ ?>
      <?php esc_html_e('Five-star reader reviews on my first two titles', 'brave-hearts'); ?>
    </span>
    <?php
    /*
     * WAVE 1 (2026-08-04) — PROOF DENSITY, WITHOUT A SECOND KIRKUS BLOCK.
     *
     * The brief asks for the Kirkus quote inside this band. ⚠️ IT IS NOT
     * PUT HERE, and the reason is an owner instruction: F19 (Andrew,
     * walk-2, 2026-08-03) moved the Kirkus section to sit IMMEDIATELY BELOW
     * the Complete Collection gallery — "put it right below or above the
     * complete collection gallery" — which is the very next section after
     * this band. Rendering the quote here as well would put two Kirkus
     * quotes one section apart and would duplicate a block a PROTECTED pass
     * deliberately placed. Recorded as CYCLE143-LD-163.
     *
     * What this pill does instead is CONNECT the two: it becomes an
     * in-page link to the existing `#kirkus-credibility-home` section, so
     * the claim and its evidence are one click apart and the quote still
     * renders exactly once, still from `bhp_get_kirkus_review_data()`.
     *
     * ⛔ NOT ONE WORD OF ANY BADGE STRING IS CHANGED. No number, no rating,
     *    no review count is added — the Amazon review count could not be
     *    verified today and no string here carries one (CYCLE143-MKT-132).
     *    No Review or AggregateRating schema is emitted, before or after.
     */
    ?>
    <a class="home-trust-proof__badge home-trust-proof__badge--link" href="#kirkus-credibility-home"><?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></a>
  </div>
</section>

<?php
/*
 * F19 (2026-08-03) — KIRKUS MOVED AND ENLARGED.
 *
 * Andrew, walk-2, verbatim: "not prominent - lets make it better or bigger
 * and put it right below or above the complete collection gallery."
 *
 * It sat two sections lower, after "Choose Your Adventure". It now renders
 * IMMEDIATELY BELOW the Complete Collection gallery it corroborates, so the
 * strongest third-party proof the company owns is read in the same eyeful as
 * the highest-value offer. `--prominent` is a styling modifier only.
 *
 * THE QUOTE IS UNTOUCHED. Text, attribution, reviewed title and review URL
 * all still come from `bhp_get_kirkus_review_data()`; the Kirkus verdict
 * "GET IT" is real and sourced and not one word of it is altered here. No
 * Review or AggregateRating microdata is emitted, before or after.
 */
?>
<section id="kirkus-credibility-home" class="homepage-section kirkus-credibility-home--prominent" aria-label="<?php esc_attr_e('Editorial review', 'brave-hearts'); ?>">
  <div class="container">
    <?php bhp_homepage_kirkus_section(); ?>
  </div>
</section>

<?php
/*
 * =======================================================================
 * 1.19.241 (2026-08-18) -- CYCLE164-LD-HOMEPAGE-WARMTH.
 *    TWO SECTIONS ENTER THE PAGE HERE, AND ONE OF THEM IS A MOVE.
 * =======================================================================
 *
 * ORDER, BEFORE -> AFTER (section index on the rendered page):
 *    1 hero                     ->  1 hero                    (unchanged)
 *    2 complete-collection band ->  2 complete-collection band (unchanged)
 *    3 #home-trust-proof        ->  3 #home-trust-proof        (unchanged)
 *    4 #kirkus-credibility-home ->  4 #kirkus-credibility-home (unchanged)
 *                                   5 #home-open-the-book      NEW
 *    9 #where-you-will-find-us  ->  6 #where-you-will-find-us   MOVED UP
 *    5 audience-gateway         ->  7 audience-gateway
 *    6 #explore-world           ->  8 #explore-world
 *    7 #first-reader            ->  9 #first-reader
 *    8 #home-philosophy         -> 10 #home-philosophy
 *   (everything from #learning-hub down is untouched)
 *
 * WHY HERE AND NOT HIGHER, WHICH IS WHAT THE BOARD'S SHEET 4 ASKS FOR.
 * The board promotes the booth "from position 9 to position 3". Position 3
 * is not available without breaking three separate recorded owner rulings,
 * and a design pass does not get to overturn those silently:
 *   - the Best Value box sits DIRECTLY after the hero (Andrew, 2026-08-05,
 *     "Right under 'Its an invitation to look up'", CYCLE144-LD-70);
 *   - the brand signature + proof pills sit DIRECTLY under that box (same
 *     ruling, "Put the big places. brave hearts under that box");
 *   - Kirkus sits against the Collection offer (F19, walk-2, a PROTECTED
 *     pass).
 * Slot 5 is the first slot that breaks none of them. Measured effect is
 * still most of what the board was after: the booth moves from 6,311 px to
 * roughly 3,300 px on desktop, and the founder -- the actual subject of the
 * board's first and largest move -- is now above the fold in the hero
 * rather than at 4,177 px.
 * THE FULL-PROMOTION ORDER IS ONE ARGUMENT AWAY if Andrew wants it. It is
 * in the build report as an explicit question, not buried as a decision.
 *
 * WHY THE PLACEMENT COMMENT ON THE BOOTH BLOCK BELOW IS NOW PARTLY
 * SUPERSEDED, AND IS PRESERVED VERBATIM RATHER THAN CORRECTED IN PLACE.
 * That comment quotes `commerce-cx`'s trust audit: the strip belongs
 * "BELOW `#first-reader` ... NOT above the fold -- it is provenance, not
 * product proof", because it answers the same question the founder card
 * answers and should therefore follow it. The RULE still holds and is still
 * honoured; what changed is WHERE the founder first appears. Since
 * 1.19.241 he is in the hero, so provenance at slot 6 still lands after the
 * founder, not before him. It is also still well below the fold.
 * THE OTHER HALF OF THAT COMMENT IS UNCHANGED AND STILL BINDING: no CTA in
 * this section, no claim about attendance, footfall, sales, queues,
 * popularity or reactions, and the market and city are still NOT named.
 *
 * WHAT WAS NOT DONE TO THE BOOTH, DELIBERATELY -- THREE THINGS:
 *   1. THE PHOTOGRAPH IS NOT CROPPED. The board proposes cropping to the
 *      canopy to push the retired sunrise-heart roll-up banner out of frame
 *      (FD-32). This template already records the OPPOSITE decision, in
 *      the preserved comment below: "it is a dated documentary photograph
 *      of a real event, and retouching a logo out of one would be the
 *      dishonest act. Andrew approved the banner as-is." Two sources
 *      disagree, one of them records an owner approval, and the resolution
 *      is Andrew's -- so nothing is picked here. The full frame, the
 *      approved alt text and the existing caption all render unchanged.
 *   2. THE HANDWRITTEN QUOTE IS NOT SHIPPED. The board's "If you were
 *      standing here, I'd hand you one and let your kid read the first
 *      page." is marked on its own sheet 6 as "NOT VERIFIED -- written by
 *      me ... Andrew has not said it." Attributing an unsaid sentence to
 *      him is the never-invent rule, which outranks warmth.
 *   3. THE THREE TRUST ANCHORS ARE NOT REPEATED HERE, for the same reason
 *      the hero strip was not built. See the block above `$hero_lead`.
 *   And the board's red-dashed "Founder Reel" placeholder, which the board
 *   itself marks "must not survive a build", is not here and nothing was
 *   substituted for it.
 */
get_template_part('template-parts/components/home-open-the-book');
?>

<?php
/*
 * 5b. "Where you'll find us" — the farmers-market provenance element.
 *
 * PLACEMENT is `commerce-cx`'s, verbatim from its trust audit and NOT
 * reinterpreted here: "a `Where you'll find us` strip on the homepage, BELOW
 * `#first-reader` ... NOT above the fold — it is provenance, not product
 * proof." It sits FIFTH in that audit's six-rank trust hierarchy, immediately
 * after the founder photograph, because it answers the same question the
 * founder card answers — "is there a real company behind this?" — rather than
 * "is this book right for my child", which ranks 1–3 and is already answered
 * higher up the page.
 *
 * ⛔ NO CTA, deliberately. Provenance earns nothing by asking for a click, and
 *    the section it follows already carries the one link this part of the page
 *    should have.
 *
 * THE PHOTOGRAPH. `assets/images/handoff/farmers-market-2026-05.webp` is a
 * derivative of Andrew's own iPhone photograph, EXIF `DateTimeOriginal`
 * 2026:05:23 10:28:34. Rotation and crop ONLY — no colour grading, no
 * retouching, no object removal — because its whole value is that it is
 * unmodified evidence of a real event. The crop deliberately excludes an
 * acrylic price sign that was in the original frame and carried THREE claims
 * that contradict live state ("PAPERBACK $8.99" against a live $11.99, "BOTH
 * BOOKS $16" for a catalogue that now has three titles, and a superseded
 * "save $1.98"). Removing them from a customer-facing photograph is the point
 * of the crop, not tidiness (`CYCLE141-CX-45`).
 *
 * ⚠️ THE BANNER CARRIES THE RETIRED SUNRISE-HEART LOGO (`CYCLE141-CX-46`).
 *    That is not an oversight: it is a dated documentary photograph of a real
 *    event, and retouching a logo out of one would be the dishonest act.
 *    Andrew approved the banner as-is. The caption carries the date so the
 *    banner reads as history rather than as a competing identity.
 *
 * ⛔ WHAT IS NOT CLAIMED HERE, AND MUST NOT BE ADDED: attendance, footfall,
 *    sales, queues, popularity, reactions, "meeting readers", "signing books",
 *    or how the day went. None of it is sourced, and in the photograph Andrew
 *    is holding two drink cups with no customer at the table — a caption
 *    describing him serving a reader would be a fabricated scene. The market
 *    and city are NOT named: the file carries no GPS and a named location is a
 *    factual claim. The copy describes presence, not activity.
 */
?>
<section id="where-you-will-find-us" class="homepage-section home-market section" aria-labelledby="home-market-title">
  <div class="container">
    <div class="home-market__card">
      <figure class="home-market__figure">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/farmers-market-2026-05.webp'); ?>"
             alt="<?php esc_attr_e('Andrew Signore standing behind a Brave Hearts Publishing table at an outdoor farmers market, with a pop-up canopy, a roll-up banner for Adventures of Charlotte and Henry, copies of the paperbacks laid out, and a plush dog on the table.', 'brave-hearts'); ?>"
             width="1400" height="1867" loading="lazy" decoding="async">
        <figcaption class="home-market__caption"><?php esc_html_e('Brave Hearts at a farmers market, May 2026.', 'brave-hearts'); ?></figcaption>
      </figure>
      <div class="home-market__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('Where you\'ll find us', 'brave-hearts'); ?></p>
        <h2 id="home-market-title" class="home-market__title"><?php esc_html_e('A canopy, a folding table, and the same books that ship to your door.', 'brave-hearts'); ?></h2>
        <?php /* 1.19.251 PASS8 -- "we do" -> "I do", standing rule 9.1, on
                 Andrew's explicit approval of 2026-08-19. Nothing else in the
                 sentence moves. */ ?>
        <p><?php esc_html_e('Brave Hearts is a small independent publisher. Some of what I do happens at a table outdoors, with the paperbacks laid out where a child can pick one up.', 'brave-hearts'); ?></p>
      </div>
    </div>
  </div>
</section>

<?php
/*
 * B6 (2026-08-03) — MID-PAGE AUDIENCE BAND, REINSTATED.
 *
 * Spec: Business OS `WORKING-DRAFTS\commerce-cx\
 * DRAFT-PHASE1-2026-08-03-START-HERE-ACCESS-SPEC.md` Part B (R8-R15).
 *
 * `template-parts/components/audience-gateway.php` was removed from this
 * template on 2026-07-31 as COLLATERAL in the quiz-consolidation pass. That
 * pass targeted duplicate `[data-bhp-quiz]` instances on the homepage.
 *
 * ⭐ THE LOAD-BEARING POINT: this module is NOT a quiz instance. It renders
 *    four `<a>` elements and one link. Reinstating it therefore cannot
 *    reintroduce a duplicate quiz, a duplicate DOM id or a second modal --
 *    which is why this is safe and why the file was never deleted. Its CSS
 *    was never removed either and is live at `style.css:2614-2626` and
 *    `:5047`, so this adds ZERO new CSS.
 *
 * PLACEMENT, and why here rather than where the spec's DOM listing shows it.
 * The spec puts it directly after `#home-sales-paths`. F19 has since moved
 * Kirkus into that gap on Andrew's walk-2 instruction ("put it right below
 * or above the complete collection gallery"), and F19 is a PROTECTED pass.
 * So the band goes after Kirkus and immediately before `#explore-world`,
 * which preserves both instructions: Kirkus still sits against the
 * Collection offer, and the band still catches the visitor at the moment
 * they have read the offer, read the proof, and not clicked.
 *
 * Homepage only (R14). Not on the audience landing pages (a visitor already
 * through the door does not need the door), not on `/books/` or
 * `/complete-collection/` (purchase-intent pages), and not on `/teachers/`
 * -- that page runs the separate teacher popup and a third routing surface
 * there would push against the parent/teacher funnel isolation rule.
 */
get_template_part('template-parts/components/audience-gateway');
?>

<section id="explore-world" class="homepage-section home-destinations section" aria-labelledby="explore-world-title">
  <div class="container">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('explore_eyebrow', __('From the deepest ocean to the highest mountain', 'brave-hearts'))); ?></p>
      <h2 id="explore-world-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('explore_title', __('Choose Your Adventure', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('explore_intro', __('Each adventure begins with a real place - and opens a door into wonder.', 'brave-hearts'))); ?></p>
      <p class="home-destinations__promise"><?php esc_html_e('The question comes first. The book is the passport.', 'brave-hearts'); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--adventures">
      <?php foreach ($adventure_cards as $card): ?>
        <?php get_template_part('template-parts/components/hub-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="container home-destinations__action">
    <a class="home-section-action" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('EXPLORE EVERY FORMAT AND EDITION', 'brave-hearts'); ?></a>
  </div>
</section>


<?php
/*
 * F20 (2026-08-03) — SECTION ORDER.
 *
 * Andrew, walk-2: the "First reader" section moves ABOVE "Our philosophy".
 * A reader meets the person and the real first reader before the statement
 * of belief, which is the order the story actually happened in.
 *
 * This is a MOVE of two complete <section> blocks. Not one character of
 * either section's markup, copy, ids, classes or images was changed by the
 * move -- diff the two blocks against 1.19.149 to confirm.
 *
 * 2D (2026-08-03) -- DEFECT INTRODUCED BY THAT MOVE, AND FIXED HERE.
 * The section marker below travelled with the block but its PHP open/close
 * tags did not, so the literal text
 *   "// 3. Founder origin: a compact trust bridge from philosophy to the
 *    adventures."
 * was echoed to the browser as visible page copy, directly under the Choose
 * Your Adventure area. Andrew reported it on walk-4 (#6). The marker is
 * REFENCED here rather than deleted, so the section numbering that every
 * other marker in this file uses stays complete.
 *
 * A whole-theme sweep for the same species was run rather than fixing only
 * the reported line: every .php file outside docs, reports and tests was
 * split into PHP and HTML regions, and every HTML-region line opening with a
 * comment marker was listed. Nine hits; eight are real JavaScript comments
 * inside script blocks (page-adventure-kit-thank-you.php,
 * inc/class-bhp-analytics-debug.php, template-parts/acquisition/
 * signup-form.php). This was the only leak.
 *
 * 3. Founder origin: a compact trust bridge from philosophy to the adventures.
 */
?>

<section id="first-reader" class="homepage-section home-origin" aria-labelledby="first-reader-title">
  <div class="container">
    <div class="home-origin__card">
      <div class="home-origin__visual">
        <div class="home-origin__journal">
          <span class="home-origin__journal-kicker"><?php esc_html_e('Field Journal - Entry 01', 'brave-hearts'); ?></span>
          <?php /* 2026-08-02 (P1a): the journal frame previously held the
                   `charlotte-henry.webp` ILLUSTRATION. It now holds the real
                   founder photograph that is already published on /about/ and
                   on the Adventure Kit page -- same file, same media asset,
                   same approved alt text, no new asset and no new claim. The
                   caption is updated to describe what the frame now actually
                   contains; leaving "Charlotte and Henry" under a photograph of
                   Andrew and Charlotte would have been a false caption. */ ?>
          <div class="home-origin__portrait home-origin__portrait--photo">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/founder-and-charlotte.webp'); ?>" alt="<?php esc_attr_e('Andrew Signore with Charlotte and a Brave Hearts book', 'brave-hearts'); ?>" width="1400" height="1867" loading="lazy" decoding="async">
            <small><?php esc_html_e('Andrew and Charlotte', 'brave-hearts'); ?></small>
          </div>
          <strong><?php esc_html_e('One child. One loyal dog.', 'brave-hearts'); ?><br><?php esc_html_e('One lasting gift.', 'brave-hearts'); ?></strong>
          <span class="home-origin__journal-meta"><?php esc_html_e('Andrew - Founder', 'brave-hearts'); ?></span>
        </div>
      </div>
      <div class="home-origin__content">
        <p class="component-heading__eyebrow"><?php esc_html_e('The first reader', 'brave-hearts'); ?></p>
        <h2 id="first-reader-title"><?php esc_html_e('It Began With One Child and One Loyal Dog', 'brave-hearts'); ?></h2>
        <p><?php esc_html_e('Before there was a company, a website, or a single illustration, there was one little girl. Her name is Charlotte. She is my niece - and she is real.', 'brave-hearts'); ?></p>
        <p><?php esc_html_e('Henry is real, too. He carries a piece of Toby - the small dog of my own childhood, who used to climb into my backpack because he wanted to come along. A companion who never solves the problem for you, but never leaves while you solve it yourself.', 'brave-hearts'); ?></p>
        <blockquote><?php esc_html_e('I wanted to give her something that would outlast every birthday - not just a story, but a compass. A way of looking at the world.', 'brave-hearts'); ?></blockquote>
        <p class="home-origin__byline"><?php esc_html_e('Andrew - Founder, Brave Hearts Publishing', 'brave-hearts'); ?></p>
        <a class="home-origin__link" href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('Read the story behind Brave Hearts', 'brave-hearts'); ?> <span aria-hidden="true">&rarr;</span></a>
      </div>
    </div>
  </div>
</section>

<?php
// 1e. Audience gateway REMOVED (2026-07-31, quiz consolidation). It was a
// second, competing routing surface: "What brings you here today?" plus four
// direct audience links and a prompt scrolling to the inline quiz below. With
// the homepage now carrying exactly one quiz entry point -- the sitewide
// launcher + auto-opening modal -- this module duplicated that job earlier in
// the page. The component file is deliberately NOT deleted; it is simply no
// longer rendered here, so it remains available and fully reversible.
//
// 2. Philosophy: connect the opening sense of wonder to the purpose behind every story.
?>
<section id="home-philosophy" class="homepage-section home-philosophy section" aria-labelledby="home-philosophy-title">
  <div class="container home-philosophy__inner">
    <header class="component-heading component-heading--center home-philosophy__heading">
      <?php /* 1.19.251 PASS8 -- "Our philosophy" -> "My philosophy", standing
               rule 9.1, on Andrew's explicit approval of 2026-08-19. The brief
               offered "The philosophy" as an alternative if "my" read oddly in
               caps; it does not. The eyebrow renders uppercase, so this sets
               "MY PHILOSOPHY", and the section that follows is a statement of
               what HE believes about how children read -- "THE PHILOSOPHY"
               would be the more detached of the two and would give back the
               first-person voice this whole pass exists to establish.
               ⚠️ This is the DEFAULT only. `bhp_get_homepage_field()` still
               wins if a `philosophy_eyebrow` override is ever set; none is set
               on staging (`wp option get bhp_homepage_fields` returns "Could
               not get ... option. Does it exist?" -- verified 2026-08-19). */ ?>
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('philosophy_eyebrow', __('My philosophy', 'brave-hearts'))); ?></p>
      <h2 id="home-philosophy-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('philosophy_title', __('Nature is the greatest classroom on Earth.', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('philosophy_intro', __('A Brave Hearts story is a beginning: adventure awakens curiosity, truth gives it somewhere to go, and character helps a child carry each discovery into the world.', 'brave-hearts'))); ?></p>
    </header>
    <ul class="home-philosophy__pillars" aria-label="<?php esc_attr_e('How Brave Hearts stories guide young readers', 'brave-hearts'); ?>">
      <li>
        <span class="home-philosophy__sequence" aria-hidden="true">01</span>
        <span><strong><?php esc_html_e('Adventure', 'brave-hearts'); ?></strong><small><?php esc_html_e('opens the door.', 'brave-hearts'); ?></small></span>
      </li>
      <li>
        <span class="home-philosophy__sequence" aria-hidden="true">02</span>
        <span><strong><?php esc_html_e('Truth', 'brave-hearts'); ?></strong><small><?php esc_html_e('deepens the wonder.', 'brave-hearts'); ?></small></span>
      </li>
      <li>
        <span class="home-philosophy__sequence" aria-hidden="true">03</span>
        <span><strong><?php esc_html_e('Character', 'brave-hearts'); ?></strong><small><?php esc_html_e('carries it home.', 'brave-hearts'); ?></small></span>
      </li>
    </ul>
    <p class="home-philosophy__closing"><?php esc_html_e('The last page is not the end.', 'brave-hearts'); ?><br><strong><?php esc_html_e('It is an invitation to look up.', 'brave-hearts'); ?></strong></p>
  </div>
</section>
<?php
// 6. Learning Hub: educational depth extends curiosity beyond the books.
$learning_cards = apply_filters('bhp_homepage_learning_cards', [
    ['title' => __('Animals', 'brave-hearts'), 'text' => __('The wildlife behind every adventure.', 'brave-hearts'), 'icon' => 'paw', 'link' => ['url' => bhp_get_learning_category_url('animals'), 'label' => __('Explore animals', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Science', 'brave-hearts'), 'text' => __('The forces that shape our world.', 'brave-hearts'), 'icon' => 'flask', 'link' => ['url' => bhp_get_learning_category_url('science'), 'label' => __('Explore science', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Geography', 'brave-hearts'), 'text' => __('The real places behind each journey.', 'brave-hearts'), 'icon' => 'globe', 'link' => ['url' => bhp_get_learning_category_url('geography'), 'label' => __('Explore geography', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Conservation', 'brave-hearts'), 'text' => __('How curiosity becomes care.', 'brave-hearts'), 'icon' => 'sprout', 'link' => ['url' => bhp_get_learning_category_url('conservation'), 'label' => __('Explore conservation', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Explorers', 'brave-hearts'), 'text' => __('The people who trek, study, protect.', 'brave-hearts'), 'icon' => 'telescope', 'link' => ['url' => bhp_get_learning_category_url('explorers'), 'label' => __('Meet explorers', 'brave-hearts')], 'class' => 'feature-card--field-note'],
    ['title' => __('Activities', 'brave-hearts'), 'text' => __('Hands-on discoveries to try.', 'brave-hearts'), 'icon' => 'pencil', 'link' => ['url' => bhp_get_learning_category_url('activities'), 'label' => __('Try an activity', 'brave-hearts')], 'class' => 'feature-card--field-note'],
], $page_id);
foreach ($learning_cards as &$learning_card) {
    $topic_slug = sanitize_title($learning_card['title'] ?? '');
    $fallback_url = bhp_get_learning_category_url($topic_slug);
    $learning_link = is_array($learning_card['link'] ?? null) ? $learning_card['link'] : [];
    $learning_link['url'] = bhp_get_safe_link_url($learning_link['url'] ?? '', $fallback_url);
    $learning_card['link'] = $learning_link;
}
unset($learning_card);
?>
<section id="learning-hub" class="homepage-section learning-hub--ecosystem section section--muted" aria-labelledby="learning-hub-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php echo esc_html(bhp_get_homepage_field('learning_eyebrow', __('The Learning Hub', 'brave-hearts'))); ?></p>
      <h2 id="learning-hub-title" class="text-section-title"><?php echo esc_html(bhp_get_homepage_field('learning_title', __('Follow Curiosity Into the Real World', 'brave-hearts'))); ?></h2>
      <p class="component-heading__intro text-lead"><?php echo esc_html(bhp_get_homepage_field('learning_intro', __('Field notes, guides, and activities that turn a story into a lifetime of looking closer - at home and in the classroom.', 'brave-hearts'))); ?></p>
    </header>
    <div class="grid grid--3 homepage-grid homepage-grid--learning">
      <?php foreach ($learning_cards as $card): ?>
        <?php get_template_part('template-parts/components/feature-card', null, $card); ?>
      <?php endforeach; ?>
    </div>
    <div class="component-section-action">
      <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Open the Expedition Guides', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php // 7. Teachers and Families. ?>
<section id="teacher-resources" class="homepage-section home-together section" aria-labelledby="home-together-title">
  <div class="container home-together__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Teachers & Families', 'brave-hearts'); ?></p>
    <h2 id="home-together-title" class="text-section-title"><?php esc_html_e('Continue the Adventure Together', 'brave-hearts'); ?></h2>
    <p class="home-together__intro"><?php esc_html_e('The best classroom has no walls. Bring Charlotte and Henry\'s expeditions into your room and your home with guides built to spark real questions - not fill worksheets.', 'brave-hearts'); ?></p>
    <div class="home-together__paths">
      <div>
        <h3><?php esc_html_e('For Classrooms', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('Read-alouds, discussion prompts, and expedition projects for young explorers.', 'brave-hearts'); ?></p>
      </div>
      <div>
        <h3><?php esc_html_e('For Families', 'brave-hearts'); ?></h3>
        <p><?php esc_html_e('Weekend adventures and backyard field notes to try after the last page.', 'brave-hearts'); ?></p>
      </div>
    </div>
    <a class="btn btn-primary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Explore teacher & family guides', 'brave-hearts'); ?></a>
  </div>
</section>

<?php // 8. Trust is expressed through verifiable publishing principles and real, verified customer reviews -- never invented ones. ?>
<section id="trust" class="homepage-section home-trust section" aria-labelledby="home-trust-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Why parents trust Brave Hearts', 'brave-hearts'); ?></p>
      <?php /* Wave F item 11: em dash removed. "follows—naturally" becomes a
               comma, which keeps the beat of the line without the dash. */ ?>
      <h2 id="home-trust-title" class="text-section-title"><?php esc_html_e('Wonder first. Learning follows, naturally.', 'brave-hearts'); ?></h2>
    </header>
    <div class="home-trust__pillars">
      <article><span aria-hidden="true">△</span><h3><?php esc_html_e('Real places, real research', 'brave-hearts'); ?></h3><p><?php esc_html_e('Every destination is a place a child could truly stand one day. The science is checked, not invented.', 'brave-hearts'); ?></p></article>
      <article><span aria-hidden="true">◇</span><h3><?php esc_html_e('Character that carries home', 'brave-hearts'); ?></h3><p><?php esc_html_e('Courage, patience, and kindness are lived through the story - never lectured at the reader.', 'brave-hearts'); ?></p></article>
      <article><span aria-hidden="true">⊙</span><h3><?php esc_html_e('Screens down, eyes up', 'brave-hearts'); ?></h3><p><?php esc_html_e('The books are written to end outside - a walk, a sky, a question a child brings to you.', 'brave-hearts'); ?></p></article>
    </div>
    <div class="home-trust__share">
      <p><?php esc_html_e("Have your reader's story to tell?", 'brave-hearts'); ?></p>
      <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Share it with the expedition', 'brave-hearts'); ?> <span aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
</section>

<?php // 8b. Genuine Amazon customer reviews -- kept several sections away from the Kirkus editorial block above so the two trust signals never visually collide, per Andrew's separation rule. Renders nothing if no book has an approved review (defensive; both featured books currently do). ?>
<?php $amazon_reviews_home = bhp_homepage_amazon_reviews_section(); ?>
<?php if (trim($amazon_reviews_home)): ?>
<section id="amazon-customer-reviews" class="homepage-section section" aria-label="<?php esc_attr_e('Amazon customer reviews', 'brave-hearts'); ?>">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('From real readers', 'brave-hearts'); ?></p>
      <h2 class="text-section-title"><?php esc_html_e('What Families Are Saying', 'brave-hearts'); ?></h2>
    </header>
    <div class="amazon-review-showcase--homepage-row">
      <?php echo $amazon_reviews_home; // phpcs:ignore -- already escaped by the component itself ?>
    </div>
    <?php /* ⭐ 2C-2 (2026-08-03) — ONE BUTTON REPLACES TWO LINK CLUSTERS.

             Andrew, final staging walk, verbatim: "Remove the 'shop adventures
             of Charlotte and henry: The mariana trench / everest' and 'Get all
             three adventures: The complete collection' from below the two
             reviews and put a call to action button 'Get the collection Here' -
             then it goes to the collection page". (Relayed through the Chief of
             Staff; NOT witnessed first-hand by this agent.)

             The two "Shop <book title> →" links are gone at their source --
             `bhp_homepage_amazon_reviews_section()` now passes
             `show_product_link => false`. This paragraph carried the third
             link, and it is replaced by the single button.

             ⛔ THE DESTINATION IS THE LANDING PAGE, NOT CHECKOUT. Andrew's own
                routing: "then it goes to the collection page". `/complete-
                collection/` is the landing page; it is deliberately NOT one of
                the one-click add-to-cart CTAs built in the supplement wave, and
                must not be "upgraded" into one without his word.

             ⛔ BUTTON TEXT IS EXACT, INCLUDING THE LOWER-CASE "the" AND THE
                CAPITAL "H" IN "Here". It is Andrew's string, quoted, not
                title-cased into house style.

             It uses THE button spec (`.btn .btn-cta-primary`: 8px radius,
             --btn-font/Archivo, forest fill, 1.5px gold border) -- no new
             button variant is introduced. The old paragraph's Wave F item 4
             recolouring note is superseded by that and is preserved in
             style.css beside the rule it described. */ ?>
    <p class="home-reviews__collection-cta">
      <a class="btn btn-cta-primary home-reviews__collection-btn" href="<?php echo esc_url(home_url('/complete-collection/')); ?>"><?php esc_html_e('Get the collection Here', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
<?php endif; ?>

<?php
// 9. Inline homepage quiz REMOVED (2026-07-31, quiz consolidation).
// The homepage previously rendered the full intro-gated quiz here AND the
// sitewide auto-opening modal from footer.php, i.e. two live [data-bhp-quiz]
// instances on one page. The homepage now carries exactly one: the single
// sitewide launcher plus its hidden modal.
//
// The '#find-your-adventure' deep-link contract is preserved -- footer.php
// passes that id to the launcher on the homepage only (see
// template-parts/components/quiz-entry-cta.php's `id` arg), so existing
// in-page anchors still land on a real quiz entry point and the id is still
// rendered exactly once per page.
//
// The quiz template part itself is untouched and still used by the modal and
// by the canonical /find-your-adventure/ page.

// 11. Footer.
get_footer();
