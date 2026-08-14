<?php
/**
 * Template Name: Reluctant Reader Adventure Kit Landing Page
 * Description: Sitewide-facing parent/grandparent landing page for the
 * Reluctant Reader Adventure Kit lead magnet -- the entry point for the
 * Mailchimp Parent - Acquisition Funnel (automation id 89, trigger tag
 * "Reluctant Reader Adventure Kit"). The lead-magnet form (see
 * bhp_get_reluctant_reader_download()) falls back to a "coming soon" state
 * only if the Adventure Kit PDF is ever unset under Settings -> Lead
 * Magnets; the PDF has been live and verified downloadable since
 * 2026-07-15, so the real signup form is what currently renders. Never
 * substitutes the Mariana classroom guide or applies teacher tags to a
 * parent lead.
 *
 * Visual design (2026-07-14): custom cream/dark-green/gold one-page layout
 * supplied by Andrew, scoped entirely under .parent-landing (see
 * assets/css/parent-landing.css + assets/js/parent-landing.js). All lead
 * capture still runs through the site's real signup pipeline
 * (template-parts/acquisition/lead-magnet-cta.php -> signup-form.php ->
 * bhp_mailchimp_signup), the real Mailchimp tag mapping in functions.php
 * (bhp_mailchimp_signup_tags filter, lead_magnet = 'reluctant_reader_
 * adventure_kit'), and the same thank-you redirect -- nothing here
 * duplicates or forks that wiring.
 *
 * ⭐ CORRECTED 2026-08-13, theme 1.19.223 (`CYCLE158-LD-SIGNUP-POPUP`). The
 *    superseded sentence here read: *"'Get the free chapter' CTAs scroll to
 *    the embedded signup panel rather than opening a popup."* It is
 *    preserved verbatim in this note rather than silently deleted, because
 *    a reader who finds it elsewhere needs to know it has moved.
 *
 *    Those CTAs now OPEN A SIGNUP MODAL with focus in the email field —
 *    Andrew Signore, current turn, relayed by `chief-of-staff`: "no
 *    scrolling, immediate capture". See the block above the
 *    `signup-modal` template part at the foot of this file.
 *
 *    THE TWO CLAUSES THAT DID NOT CHANGE, and both still govern:
 *      - This page is still excluded from the sitewide parent popup by
 *        `bhp_should_show_any_popup()`, because it IS the dedicated signup
 *        destination. The new modal is not a lead-magnet popup: it has no
 *        timer, no scroll trigger and no exit trigger, and opens only on a
 *        deliberate CTA click.
 *      - `assets/js/mariana-popup.js` is still not forked. The modal binds
 *        `[data-bhp-signup-modal]`, that engine binds `[data-bhp-popup]`,
 *        and no funnel storage prefix or analytics prefix is minted.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_reluctant_reader_download();
$adventures = bhp_get_series_adventures();
$thank_you_url = home_url('/adventure-kit-thank-you/');
$complete_collection_url = home_url('/complete-collection/');

// Parent Funnel Phase 1: landing-page impression event, same gate and
// dedup-free "fire once per real page load" pattern as every other
// pageview-scoped event on this site (e.g. bundle_page_view). No PII.
if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    $bhp_parent_landing_payload = wp_json_encode([
        'event'      => 'parent_landing_view',
        'funnel'     => 'parent',
        'page_type'  => 'landing_page',
        'lead_offer' => 'reluctant_reader_adventure_kit',
        'audience'   => 'parents_families',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_parent_landing_payload; ?>);
    </script>
    <?php
endif;

$mariana = $adventures['mariana_trench'] ?? [];
$everest = $adventures['mount_everest'] ?? [];
$amazon  = $adventures['amazon_rainforest'] ?? [];

$pain_points = [
    __('The story takes too long to get exciting.', 'brave-hearts'),
    __('The chapter feels too long to finish.', 'brave-hearts'),
    __('The topic doesn’t feel like it’s for them.', 'brave-hearts'),
    __('They haven’t had that first satisfying win yet.', 'brave-hearts'),
];

$solution_points = [
    __('<strong>12 approachable chapters</strong> - short enough that finishing feels possible.', 'brave-hearts'),
    __('<strong>Black-and-white illustrations</strong> on the page to carry the story along.', 'brave-hearts'),
    __('<strong>Real places, animals, science &amp; history</strong> - discovery woven into adventure.', 'brave-hearts'),
    __('<strong>Charlotte &amp; Henry</strong> - familiar companions across every book.', 'brave-hearts'),
    __('<strong>Adventure and heart</strong> woven through every chapter, not just the ending.', 'brave-hearts'),
    __('<strong>Chapters they can actually finish</strong> - and finishing builds momentum.', 'brave-hearts'),
];

$inside_points = [
    __('<strong>12 short chapters</strong> - a clear finish line each sitting', 'brave-hearts'),
    __('<strong>Black-and-white illustrations</strong> throughout', 'brave-hearts'),
    __('<strong>Real explorer facts</strong>, maps &amp; diagrams', 'brave-hearts'),
    __('<strong>A glossary</strong> of new words', 'brave-hearts'),
    __('<strong>Try-it-yourself</strong> activity ideas', 'brave-hearts'),
];

// Replaces the removed interior-spread/media-grid image placeholders --
// answers "why is my child likely to enjoy and finish these books" in
// words instead of an unfinished image gallery. Careful wording ("Adventure
// features include...", "Designed with...") since not every exact feature
// is confirmed present in identical form across all three titles.
$feature_grid = [
    ['title' => __('Short, approachable chapters', 'brave-hearts'), 'text' => __('Twelve chapters give a clear finish line each sitting, so finishing one feels achievable, not overwhelming.', 'brave-hearts')],
    ['title' => __('Black-and-white illustrations', 'brave-hearts'), 'text' => __('Adventure features include illustrated pages that break up the text and keep the story moving.', 'brave-hearts')],
    ['title' => __('Real-world explorer facts', 'brave-hearts'), 'text' => __('Across the series, readers encounter real places, animals, and science woven into the adventure.', 'brave-hearts')],
    ['title' => __('Maps for geography discovery', 'brave-hearts'), 'text' => __('Designed with maps and diagrams that turn each adventure into a real-world geography lesson.', 'brave-hearts')],
    ['title' => __('Activities that extend the adventure', 'brave-hearts'), 'text' => __('Adventure features include try-it-yourself activity ideas that carry the story beyond the last page.', 'brave-hearts')],
];

$how_it_works = [
    ['title' => __('Approachable chapters', 'brave-hearts'), 'text' => __('Twelve short chapters so each reading session has a clear finish line.', 'brave-hearts')],
    ['title' => __('Illustrations that help', 'brave-hearts'), 'text' => __('Black-and-white art breaks up the page and keeps the story moving.', 'brave-hearts')],
    ['title' => __('Real-world learning', 'brave-hearts'), 'text' => __('Actual science, geography, and history - the kind kids repeat at dinner.', 'brave-hearts')],
    ['title' => __('Continuing characters', 'brave-hearts'), 'text' => __('Charlotte and Henry return in every book, so the next one feels familiar.', 'brave-hearts')],
    ['title' => __('Meaningful challenges', 'brave-hearts'), 'text' => __('Courage, teamwork, and kindness show up in the middle of the adventure.', 'brave-hearts')],
    ['title' => __('Three destinations', 'brave-hearts'), 'text' => __('Ocean, mountain, rainforest - three complete journeys to grow into.', 'brave-hearts')],
];

$faqs = [
    [__('What age are the books for?', 'brave-hearts'), __('They’re written for readers roughly ages 6–9 (about 1st–3rd grade) - approachable enough for a new independent reader and rich enough for a family read-aloud.', 'brave-hearts')],
    [__('Are they good for reluctant readers?', 'brave-hearts'), __('That’s exactly who they’re designed for. Short chapters, illustrations, and real adventure give kids an achievable win early, so finishing feels good instead of overwhelming.', 'brave-hearts')],
    [__('How long are the chapters?', 'brave-hearts'), __('Each book has 12 short chapters, so a child (or a parent reading aloud) can reach a natural stopping point in one sitting.', 'brave-hearts')],
    [__('Are the facts real?', 'brave-hearts'), __('Yes. The adventures are built around real places, animals, science, and history - the kind of details kids love repeating at the dinner table.', 'brave-hearts')],
    [__('What’s included in the Complete Collection?', 'brave-hearts'), __('All three adventures - The Mariana Trench, Mount Everest, and The Amazon - in one purchase and one shipment.', 'brave-hearts')],
    [__('Paperback or hardcover?', 'brave-hearts'), __('Both include the same three complete stories. Paperback is lightweight and easy for small hands; hardcover is a durable keepsake edition. Your choice.', 'brave-hearts')],
    [__('How does print-on-demand shipping work?', 'brave-hearts'), __('Books are printed and shipped with tracking, and the complete collection ships free. You’ll receive one shipment with all three books.', 'brave-hearts')],
    [__('What comes with the free sample?', 'brave-hearts'), __('You’ll get Chapter 7 from The Mariana Trench, a matching printable explorer activity, and a few simple tips for reading it together.', 'brave-hearts')],
    [__('How often will I receive emails?', 'brave-hearts'), __('After the free chapter, you’ll get occasional Adventure Club updates and reading resources for parents - never spam.', 'brave-hearts')],
    [__('Can I unsubscribe?', 'brave-hearts'), __('Anytime, with one click at the bottom of any email. Signing up for the free sample never requires a purchase.', 'brave-hearts')],
];

// Real Complete Collection pricing -- same single source of truth as
// bundle-landing-page.php (bundle-data.php). Never hardcoded here.
$bundle_available = function_exists('bhp_bundle_expected_price') && function_exists('bhp_bundle_rules') && function_exists('bhp_bundle_catalog');
$formats = [];
if ($bundle_available) {
    foreach (['paperback', 'hardcover'] as $format) {
        $unit = bhp_bundle_expected_price($format);
        $rule = bhp_bundle_rules($format)[3];
        $formats[$format] = [
            'combined'   => 3 * $unit,
            'collection' => (3 * $unit) - $rule['discount'],
            'shipping'   => $rule['shipping'],
            'save'       => $rule['discount'],
            'name'       => ucfirst($format),
        ];
    }
}

/*
 * 2D (2026-08-03) -- HARDCOVER-FIRST. Andrew, walk-4, verbatim (RELAYED
 * through the Chief of Staff, NOT witnessed by this agent): "all the funnel
 * pages and collection pages should default to the hardcovers not paperback".
 *
 * Read, never restated: bhp_book_format_order() and bhp_book_default_format()
 * in inc/book-formats.php both delegate to the bundle plugin's own
 * bhp_bundle_default_format(), which is the same function the Complete
 * Collection landing page and the /books/ one-click CTA already read. One
 * decision, one owner, five surfaces.
 *
 * ONLY the presentation order and the pre-selected control change. Every
 * price, saving and shipping figure below is still computed from
 * bhp_bundle_expected_price() and bhp_bundle_rules() at render time, so the
 * numbers follow the default instead of being pinned beside it.
 */
$bhp_default_format = function_exists('bhp_book_default_format') ? bhp_book_default_format() : 'hardcover';
$bhp_format_order   = function_exists('bhp_book_format_order') ? bhp_book_format_order() : array('paperback', 'hardcover');

// Verified testimonial, same registry entry bundle-landing-page.php uses
// (amz-mariana-04) -- one real source of truth, never a fabricated quote.
$testimonial_url = '';
if (function_exists('bhp_get_amazon_review_registry')) {
    foreach (bhp_get_amazon_review_registry() as $review) {
        if ('amz-mariana-04' === $review['id']) {
            $testimonial_url = $review['source_url'];
            break;
        }
    }
}
?>
<div class="parent-landing" data-parent-landing>

<!-- ===================== HERO ===================== -->
<section class="parent-landing-hero">
  <div class="parent-landing-hero__bg" aria-hidden="true"></div>
  <div class="parent-landing__inner parent-landing-hero__grid">
    <div>
      <span class="parent-landing-eyebrow parent-landing-hero__badge"><?php esc_html_e('For parents of readers ages 6–9', 'brave-hearts'); ?></span>
      <h1><?php esc_html_e('Help your child see reading as an adventure.', 'brave-hearts'); ?></h1>
      <p class="parent-landing__lead"><?php esc_html_e('Short chapters, illustrated discoveries, and real-world adventures - designed to give a young reader a reason to keep turning the page.', 'brave-hearts'); ?></p>
      <?php
      /*
       * ⭐ 1.19.213 (CYCLE150-LD) — ONE CTA, NOT TWO. The secondary
       *    "Explore the collection" outline button is REMOVED, not hidden:
       *    Andrew Signore, relayed by `chief-of-staff` (⛔ not witnessed
       *    here), "it will distract from the main CTA." This is the page his
       *    example named — the kit funnel's primary CTA stays exactly as it
       *    is, "Get the free chapter & activity", with its event, source and
       *    `data-parent-free-cta` scroll hook byte-unchanged.
       *
       *    Its `parent_hero_secondary_cta_click` event goes with it. The
       *    collection is still reachable from the mobile fast-purchase band,
       *    the raised Best Value card, the #collection section itself and the
       *    sticky mini-CTA.
       */
      ?>
      <div class="parent-landing-hero__ctas">
        <a class="btn btn-primary" href="#free" data-parent-free-cta data-bhp-signup-modal-open="adventure-kit-modal" data-bhp-signup-modal-source="hero" data-bhp-event="parent_hero_primary_cta_click" data-bhp-source="adventure_kit_landing"><?php esc_html_e('Get the free chapter & activity', 'brave-hearts'); ?></a>
      </div>
      <?php
      /*
       * ⭐ 1.19.213 — THE COLLECTION CAROUSEL, DIRECTLY UNDER THE PRIMARY CTA.
       *    Founder slot 4. Full spec and the two new placement keys are stated
       *    ONCE in `inc/collection-gallery.php`, not repeated per template.
       * ⛔ A MOVE, NOT AN ADDITION — the identical call that used to sit in the
       *    SEE INSIDE section further down is gone. Still exactly one
       *    instance, one DOM id, one lightbox per request.
       */
      if (function_exists('bhp_cx_render_collection_gallery')) {
          echo '<div class="parent-landing-hero__gallery">';
          bhp_cx_render_collection_gallery();
          echo '</div>';
      }
      ?>
      <?php
      /*
       * 2026-08-10 (Andrew, current-turn): the hero proof strip is retired on
       * this page — Kirkus moved into the scanbar checkmarks, "Placed in
       * classrooms across Boise" removed, "Read-aloud & independent friendly"
       * removed as redundant with the scanbar's own "Read aloud or independent".
       */
      ?>
      <?php
      /*
       * ⭐ 1.19.213 — THE STATIC THREE-COVER HERO ART COLUMN IS REMOVED.
       *    Andrew Signore, relayed by `chief-of-staff` (⛔ not witnessed
       *    here): slide 1 of the carousel above IS the three-book image, so
       *    the lockup was the same picture twice in one eyeful — and it was
       *    the single largest thing pushing the CTA under the fold.
       *
       * ⛔ WHAT WENT WITH IT, NAMED RATHER THAN LEFT TO BE DISCOVERED: the
       *    `.parent-landing-hero__covers` block and the "Ocean · Mountain ·
       *    Rainforest" caption. ⚠ WAVE G's note below says explicitly "THE
       *    CAPTION STAYS" — that instruction was about the LOGO removal of
       *    2026-08-03 and is superseded here only because the whole column it
       *    lived in is gone. It is flagged, not absorbed: if Andrew wants that
       *    line back it belongs under the carousel and is a one-line addition.
       *
       * ⚠ The three covers are NOT gone from the page — they are still the
       *   `parent-landing-books` grid inside #collection, and still every
       *   slide of the carousel.
       *
       * WAVE G's note is preserved verbatim below because it is the only
       * remaining record of why the hero logo left and why the dead
       * `.parent-landing-hero__logo` rule in `parent-landing.css` was kept.
       */
      /*
       * WAVE G (2026-08-03) — THE HERO LOCKUP IS REMOVED, NOT RESIZED.
       * Andrew Signore, relayed via chief-of-staff: the large logo was
       * pressing onto the covers. The books own this space now.
       *
       * `the_custom_logo()` used to render here, directly above
       * `.parent-landing-hero__covers`. It is deleted rather than
       * shrunk or given clearance because the hero already identifies
       * the brand three times over — the site header lockup sits a few
       * hundred pixels above it, the three covers all carry the series
       * wordmark, and the caption below names the destinations. A
       * fourth mark earned nothing and cost the covers their room.
       *
       * ⛔ THE CAPTION STAYS. "Ocean · Mountain · Rainforest" is the one
       *    element in this column that says something the covers do not,
       *    and the brief keeps it explicitly.
       *
       * SCOPE: this page only. The other four audience landing pages
       * (educators, gift-buyers, organizations, retailers) use a
       * DIFFERENT hero — `.audience-landing-hero`, which never rendered
       * a logo here — so none of them is touched by this change. Their
       * identical `audience-landing-hero__caption` is also untouched.
       *
       * ⚠ WHY IT WAS OVERSIZED IN THE FIRST PLACE — verified, because it
       *   explains the symptom and stops the wrong fix being tried later.
       *   parent-landing.css:113 sizes the hero mark with
       *     `.parent-landing-hero__art img.parent-landing-hero__logo`
       *   but `the_custom_logo()` emits `<a class="custom-logo-link">
       *   <img class="custom-logo">` — it NEVER carries
       *   `.parent-landing-hero__logo`. Confirmed by search: the string
       *   "custom-logo" does not appear anywhere in parent-landing.css.
       *   So the only page-specific sizing rule for this mark has never
       *   matched it, and the image rendered at whatever the global
       *   cascade gave it. Resizing was therefore never going to be a
       *   one-line change, and removal is both what was asked for and
       *   the smaller edit.
       *
       * Line 113's now-dead rule is deliberately LEFT IN PLACE rather
       * than deleted in the same pass: it is the only remaining record
       * of the intended treatment, and restoring the mark later is a
       * two-line change (emit the class, keep the rule).
       */
      ?>
    </div>
  </div>
</section>

<!-- ===================== QUICK-SCAN BAR ===================== -->
<section class="parent-landing-scanbar">
  <div class="parent-landing__inner parent-landing__inner--narrow parent-landing-scanbar__row">
    <span class="parent-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Ages 6–9 · 1st–3rd grade', 'brave-hearts'); ?></span>
    <span class="parent-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('12 short chapters', 'brave-hearts'); ?></span>
    <?php /* A6 (2026-08-03): "60–90 minute read" removed under Andrew's
             duration-claim rule. The slot is FILLED rather than left empty --
             a three-item scanbar in a four-item row reads as a bug -- with the
             approved replacement line, which says the true thing the removed
             claim was pretending to say. */ ?>
    <span class="parent-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Your reader sets the pace', 'brave-hearts'); ?></span>
    <span class="parent-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Read aloud or independent', 'brave-hearts'); ?></span>
    <span class="parent-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></span>
  </div>
</section>

<?php
/*
 * ⭐ 1.19.210 (2026-08-09, CYCLE148-LD-02) — THE FAST-PURCHASE BAND, right
 *    below the checkmarks, exactly where Andrew asked for it after looking
 *    at this page on his phone. His words, and the reasoning for building an
 *    ADDITION rather than moving the price card, are in the template part's
 *    own header — stated once, there, not repeated on five pages.
 *
 * ⛔ MOBILE-ONLY BY CSS, and deliberately not by a server-side device test.
 *    The band is in the DOM on every viewport; `parent-landing.css` hides it
 *    above the mobile breakpoint. A PHP `wp_is_mobile()` branch would be
 *    cached wrong by SiteGround's page cache the first time a desktop
 *    visitor warmed the page, which is the documented cache-safety rule this
 *    theme already follows for consent mode.
 */
/* 2026-08-10 (Andrew, current-turn): the mobile fast-purchase band is RETIRED
   on this page - its 'Best value' box and 'Add the ... Collection' CTA duplicated
   the raised Best Value pricing card directly below (his words: 'very redundant').
   The band template itself is preserved unused. */
?>

<!-- ===================== COMPLETE COLLECTION ===================== -->
<section id="collection" class="parent-landing__section parent-landing__section--major">
  <div class="parent-landing__inner">
    <div class="parent-landing__header-block">
      <span class="parent-landing-eyebrow"><?php esc_html_e('The complete collection', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Three complete adventures.', 'brave-hearts'); ?></h2>
      <p class="parent-landing__lead"><?php echo wp_kses_post(__('Beneath the ocean, to the highest mountain, and deep into the rainforest - the full <em>Adventures of Charlotte &amp; Henry</em> collection in one shipment.', 'brave-hearts')); ?></p>
    </div>

    <?php
    /*
     * ⭐ 1.19.213 — THE BOOKS GRID MOVES BELOW THE PRICE CARD, so the Best
     *    Value buy section follows the checkmark scanbar directly. Founder
     *    slot 6, relayed: "So they can buy easier on the page."
     *
     * ⛔ MOVED STRUCTURALLY, NOT WITH CSS `order` — keyboard and reading order
     *    still match the visible order, which this theme has kept true through
     *    every hero reordering (see the 1.19.120 note in `docs/RELEASES/`).
     *    Nothing is deleted: the grid renders unchanged immediately after the
     *    card, with the same covers and the same copy.
     */
    ?>
    <?php if ($bundle_available): ?>
      <div class="parent-landing-pricecard" data-parent-pricing-card>
        <span class="parent-landing-pricecard__badge">&#9733; <?php esc_html_e('Best value - all three books', 'brave-hearts'); ?></span>

        <div class="parent-landing-format-toggle" role="radiogroup" aria-label="<?php esc_attr_e('Choose your format', 'brave-hearts'); ?>">
          <?php foreach ($bhp_format_order as $format):
            $is_pb      = 'paperback' === $format;
            $is_default = $bhp_default_format === $format;
          ?>
            <button type="button" role="radio" aria-checked="<?php echo $is_default ? 'true' : 'false'; ?>"
              class="parent-landing-format-btn<?php echo $is_default ? ' is-selected' : ''; ?>"
              data-parent-format-btn="<?php echo esc_attr($format); ?>">
              <?php echo esc_html(ucfirst($format)); ?>
              <span class="price">$<?php echo esc_html(number_format($formats[$format]['collection'], 2)); ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <?php foreach ($bhp_format_order as $format):
          $f = $formats[$format];
          $is_pb      = 'paperback' === $format;
          $is_default = $bhp_default_format === $format;
          $desc = $is_pb
              ? __('Softcover · durable matte cover · lightweight and easy to read', 'brave-hearts')
              : __('Hardcover · sewn binding · a keepsake edition that lasts', 'brave-hearts');
        ?>
          <div data-parent-format-panel="<?php echo esc_attr($format); ?>" <?php echo $is_default ? '' : 'hidden'; ?>>
            <h3><?php echo esc_html('Complete ' . $f['name'] . ' Collection'); ?></h3>
            <p class="subtitle"><?php echo esc_html($desc); ?></p>
            <div class="parent-landing-pricecard__included">
              <?php if ($mariana): ?><div class="parent-landing-pricecard__included-row"><span class="check">&#10003;</span> <?php esc_html_e('Adventures of Charlotte & Henry: The Mariana Trench', 'brave-hearts'); ?></div><?php endif; ?>
              <?php if ($everest): ?><div class="parent-landing-pricecard__included-row"><span class="check">&#10003;</span> <?php esc_html_e('Adventures of Charlotte & Henry: Mount Everest', 'brave-hearts'); ?></div><?php endif; ?>
              <?php if ($amazon): ?><div class="parent-landing-pricecard__included-row"><span class="check">&#10003;</span> <?php esc_html_e('Adventures of Charlotte & Henry: The Amazon', 'brave-hearts'); ?></div><?php endif; ?>
            </div>
            <div class="parent-landing-pricecard__foot">
              <div class="parent-landing-pricecard__badges">
                <span class="parent-landing-pricecard__badge-pill parent-landing-pricecard__badge-pill--save"><?php echo esc_html(sprintf(__('Save $%s', 'brave-hearts'), number_format($f['save'], 2))); ?></span>
                <span class="parent-landing-pricecard__badge-pill parent-landing-pricecard__badge-pill--muted">&#10003; <?php esc_html_e('One shipment', 'brave-hearts'); ?></span>
                <span class="parent-landing-pricecard__badge-pill parent-landing-pricecard__badge-pill--muted">&#10003; <?php esc_html_e('Three complete adventures', 'brave-hearts'); ?></span>
              <?php
              /*
               * 1.19.194 (2026-08-05, CYCLE144-LD-225) -- the free activity
               * book, in the badge row where "One shipment" already lives.
               *
               * Andrew Signore, 2026-08-05 (RELAYED, not witnessed first-hand
               * by this agent): "I want it clear that you get Free Shipping and
               * a Free Activity book with Collection purchase- on all
               * collection pages and boxes". This is one of those boxes.
               *
               * bhp_book_free_addon_badge() returns '' unless the PLUGIN
               * confirms the offer is on AND BHP-ACTIVITY-BOOK-01 resolves to a
               * real, purchasable, in-stock product on this environment, so on
               * an environment without the product this pill does not render
               * and the card is byte-identical to 1.19.193.
               */
              /*
               * ⭐ 1.19.210 (2026-08-09, CYCLE148-LD-03) — THE FREE ITEMS COME
               *    OUT OF THE PILL ROW AND BECOME BOLD BULLET LINES.
               *
               * Andrew Signore, 2026-08-06, relayed (⛔ NOT witnessed
               * first-hand by this agent): "FREE-items emphasis on ALL funnel
               * + collection pages: bold, each free item its own bullet line,
               * never combined sentences."
               *
               * A muted pill in a row of three other muted pills is the exact
               * opposite of emphasis — it makes the free thing look like the
               * least important of four equal facts. The pill is therefore
               * REMOVED here and the same fact is re-stated below, in the
               * bullet list, in bold, with the "$5.00 savings" wording Andrew
               * asked for in the same week.
               *
               * ⛔ REMOVED FROM THIS ROW, NOT FROM THE PAGE. The claim still
               *    renders, still gated on the plugin's live offer state, and
               *    still disappears entirely on an environment where the
               *    Activity Book does not resolve.
               */
              ?>
              </div>
              <?php
              $bhp_free_bullets = function_exists('bhp_book_free_bullets_markup')
                  ? bhp_book_free_bullets_markup('collection', 'bhp-free-bullets--card')
                  : '';
              ?>
              <?php if ('' !== $bhp_free_bullets): ?>
                <?php echo $bhp_free_bullets; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in bhp_book_free_bullets_markup(). ?>
              <?php endif; ?>
              <div class="parent-landing-pricecard__price-row">
                <span class="label"><?php esc_html_e('Complete Collection price', 'brave-hearts'); ?></span>
                <span><span class="parent-landing-pricecard__price-strike">$<?php echo esc_html(number_format($f['combined'], 2)); ?></span><span class="parent-landing-pricecard__price-final">$<?php echo esc_html(number_format($f['collection'], 2)); ?></span></span>
              </div>
              <?php
              /*
               * `true` = do not repeat "FREE shipping" here. It is already a
               * bold bullet immediately above, and repeating it inside a
               * run-on line is the "combined sentence" the ruling forbids.
               * When shipping is NOT free this argument does nothing and the
               * flat-rate sentence is unchanged.
               */
              ?>
              <p class="parent-landing-pricecard__ship-note"><?php echo esc_html(bhp_book_landing_ship_note($f['shipping'], '' !== $bhp_free_bullets)); ?></p>
              <?php
              /*
               * 2026-08-05 — Andrew: "2 click journey to purchase". Was an <a>
               * to /complete-collection/; now adds this panel's three real books
               * and lands on /checkout/. Label, classes and both analytics
               * attributes are unchanged. See inc/collection-cta.php.
               */
              echo bhp_collection_add_to_cart_cta([
                  'format'     => $format,
                  'label'      => sprintf(__('Add the %s Collection', 'brave-hearts'), $f['name']),
                  'class'      => 'btn btn-primary',
                  'form_class' => 'parent-landing-pricecard__cta-form',
                  'event'      => 'parent_collection_cta_click',
                  'source'     => 'adventure_kit_landing',
              ]);
              ?>
              <p class="parent-landing-pricecard__link-row"><?php esc_html_e('Secure checkout · Tracking provided ·', 'brave-hearts'); ?> <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('View individual books', 'brave-hearts'); ?></a></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="align-center" style="text-align:center;margin-top:40px;">
        <a class="btn btn-primary" href="<?php echo esc_url($complete_collection_url); ?>"><?php esc_html_e('Explore the Complete Collection', 'brave-hearts'); ?></a>
      </p>
    <?php endif; ?>

    <?php /* 1.19.213 — the three-book grid, relocated to sit AFTER the price
             card. Same markup, same covers, same copy; only its position in
             the section changed. See the note above the price card. */ ?>
    <div class="parent-landing-books">
      <?php if ($mariana): ?>
        <div class="parent-landing-book"><?php echo bhp_parent_landing_cover($mariana); ?><p class="eyebrow-line"><?php esc_html_e('Book One · Ocean', 'brave-hearts'); ?></p><h3><?php echo esc_html($mariana['title'] ?? 'The Mariana Trench'); ?></h3><p class="desc"><?php esc_html_e('Deep-sea science and courage in the unknown.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
      <?php if ($everest): ?>
        <div class="parent-landing-book"><?php echo bhp_parent_landing_cover($everest); ?><p class="eyebrow-line"><?php esc_html_e('Book Two · Mountain', 'brave-hearts'); ?></p><h3><?php echo esc_html($everest['title'] ?? 'Mount Everest'); ?></h3><p class="desc"><?php esc_html_e('Historic explorers, teamwork, and perseverance.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
      <?php if ($amazon): ?>
        <div class="parent-landing-book"><?php echo bhp_parent_landing_cover($amazon); ?><p class="eyebrow-line"><?php esc_html_e('Book Three · Rainforest', 'brave-hearts'); ?></p><h3><?php echo esc_html($amazon['title'] ?? 'The Amazon'); ?></h3><p class="desc"><?php esc_html_e('Rainforest wildlife, river systems, and kindness.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===================== PROBLEM ===================== -->
<section class="parent-landing__section parent-landing__section--muted">
  <div class="parent-landing__inner parent-landing__inner--narrow">
    <div class="parent-landing__header-block">
      <span class="parent-landing-eyebrow"><?php esc_html_e('Sound familiar?', 'brave-hearts'); ?></span>
      <h2><?php echo wp_kses_post(__('When your child says<br>&ldquo;reading is boring&rdquo;&hellip;', 'brave-hearts')); ?></h2>
      <p class="parent-landing__lead"><?php esc_html_e('&hellip;it usually isn’t really about reading at all. It’s about the book in front of them. Here’s what &ldquo;boring&rdquo; often actually means:', 'brave-hearts'); ?></p>
    </div>
    <div class="parent-landing-grid">
      <?php foreach ($pain_points as $i => $point): ?>
        <div class="parent-landing-card"><div class="parent-landing-card__num"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></div><p><?php echo esc_html($point); ?></p></div>
      <?php endforeach; ?>
    </div>
    <p class="parent-landing__pull-quote"><?php esc_html_e('None of this means your child doesn’t like reading. It usually means they haven’t met the right book yet.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== SOLUTION ===================== -->
<section class="parent-landing__section">
  <div class="parent-landing__inner parent-landing-split">
    <div>
      <span class="parent-landing-eyebrow"><?php esc_html_e('A different kind of chapter book', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Built for the reader who gives up on page two.', 'brave-hearts'); ?></h2>
      <p class="parent-landing__lead"><?php esc_html_e('Brave Hearts books help young readers build curiosity, reading confidence, and a sense of adventure - through approachable chapters, illustrations, real-world discovery, and stories that give them a reason to keep reading.', 'brave-hearts'); ?></p>
    </div>
    <div class="parent-landing-checklist">
      <?php foreach ($solution_points as $point): ?>
        <div class="parent-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== SEE INSIDE ===================== -->
<!-- Sprint (2026-07-16): the interior-spread photo and 4-item media grid
     placeholders were removed rather than left as "coming soon" on a live
     page -- no real interior photography exists yet (confirmed: nothing in
     assets/images qualifies, and repurposing the unused destination photos
     in assets/images/wild-places would misrepresent this black-and-white
     illustrated book). Replaced with a text/feature grid using the same
     .parent-landing-card component already used elsewhere on this page
     (no new CSS), answering "why will my child enjoy and finish these
     books" instead of showing an unfinished image gallery. Heading, lead,
     checklist, and pull-quote are unchanged per the approved plan. Real
     interior photography can replace this grid later with no template
     rework needed. -->
<section class="parent-landing__section parent-landing__section--muted">
  <div class="parent-landing__inner">
    <div class="parent-landing__header-block">
      <span class="parent-landing-eyebrow"><?php esc_html_e('See inside the books', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('You can tell in one flip-through.', 'brave-hearts'); ?></h2>
      <p class="parent-landing__lead"><?php esc_html_e('Illustrations on nearly every spread, real explorer facts and maps, and short chapters with plenty of white space - the page never feels like a wall of text.', 'brave-hearts'); ?></p>
      <div class="parent-landing-checklist parent-landing-checklist--compact" style="margin-top:24px;">
        <?php foreach ($inside_points as $point): ?>
          <div class="parent-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    /*
     * ⭐ 1.19.213 — THE CAROUSEL CALL THAT STOOD HERE HAS MOVED TO THE HERO,
     *    directly under the primary CTA (founder slot 4). A MOVE: still one
     *    call, one instance, one DOM id per request — the one-gallery-per-page
     *    constraint the note below names is unchanged and still enforced by
     *    `bhp_cx_render_collection_gallery()`'s render-once guard.
     *
     * ⚠ THE COST, STATED RATHER THAN HIDDEN. This section's <h2> is literally
     *   "You can tell in one flip-through.", and the 2026-08-03 note preserved
     *   below put the gallery here precisely because that claim was made in
     *   text and never shown. Moving the gallery to the hero re-opens that
     *   gap: the flip-through is now ABOVE this heading rather than under it.
     *   The founder's structure is explicit that the carousel goes directly
     *   under the primary CTA and that there is exactly one per page, so this
     *   is a deliberate trade, not an oversight. ⭐ Flagged for Andrew: the
     *   clean fix is a copy change to this section's heading, and copy is
     *   locked, so nothing was rewritten here.
     *
     * The 2026-08-03 note, preserved verbatim:
     *
     *   "The evidence for this section's own promise. Its <h2> is literally
     *    'You can tell in one flip-through.' and the page carried no
     *    flip-through — the claim was made in text and never shown. The real
     *    flip-through video leads the subset for that reason.
     *
     *    Deliberately NOT placed in the #collection section further down:
     *    exactly one gallery instance may exist per page, because the
     *    component derives its DOM id from the media key."
     *
     * The #free lead-magnet block and the parent funnel's popup, storage keys
     * and analytics prefixes remain untouched.
     */
    ?>
    <div class="parent-landing-grid parent-landing-grid--features" style="margin-top:32px;">
      <?php foreach ($feature_grid as $feature): ?>
        <div class="parent-landing-card"><h3><?php echo esc_html($feature['title']); ?></h3><p class="desc"><?php echo esc_html($feature['text']); ?></p></div>
      <?php endforeach; ?>
    </div>
    <p class="parent-landing__pull-quote"><?php esc_html_e('Charlotte and Henry are right there on the page for every discovery - the friendly faces that make a young reader want to turn to chapter two.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== RESULT ===================== -->
<section class="parent-landing__section parent-landing__section--dark">
  <div class="parent-landing__inner parent-landing__inner--narrow">
    <div class="parent-landing__header-block parent-landing__header-block--dark">
      <span class="parent-landing-eyebrow parent-landing-eyebrow--on-dark"><?php esc_html_e('The real result', 'brave-hearts'); ?></span>
      <h2><?php echo wp_kses_post(__('You&rsquo;re not buying a book.<br>You&rsquo;re giving them momentum.', 'brave-hearts')); ?></h2>
    </div>
    <div class="parent-landing-flow">
      <span class="parent-landing-flow__pill"><?php esc_html_e('One chapter', 'brave-hearts'); ?></span><span class="parent-landing-flow__arrow">&rarr;</span>
      <span class="parent-landing-flow__pill"><?php esc_html_e('A small win', 'brave-hearts'); ?></span><span class="parent-landing-flow__arrow">&rarr;</span>
      <span class="parent-landing-flow__pill"><?php esc_html_e('More curiosity', 'brave-hearts'); ?></span><span class="parent-landing-flow__arrow">&rarr;</span>
      <span class="parent-landing-flow__pill"><?php esc_html_e('Growing confidence', 'brave-hearts'); ?></span><span class="parent-landing-flow__arrow">&rarr;</span>
      <span class="parent-landing-flow__pill parent-landing-flow__pill--final"><?php esc_html_e('The next adventure', 'brave-hearts'); ?></span>
    </div>
    <div class="parent-landing-outcomes">
      <div class="parent-landing-outcomes__item"><h3><?php esc_html_e('Curiosity', 'brave-hearts'); ?></h3><p><?php esc_html_e('They start wondering about the world beyond the page.', 'brave-hearts'); ?></p></div>
      <div class="parent-landing-outcomes__item"><h3><?php esc_html_e('Reading confidence', 'brave-hearts'); ?></h3><p><?php esc_html_e('Finishing a chapter proves they can do it - again.', 'brave-hearts'); ?></p></div>
      <div class="parent-landing-outcomes__item"><h3><?php esc_html_e('Conversation', 'brave-hearts'); ?></h3><p><?php esc_html_e('Real places and animals spark questions worth answering.', 'brave-hearts'); ?></p></div>
      <div class="parent-landing-outcomes__item"><h3><?php esc_html_e('Shared time', 'brave-hearts'); ?></h3><p><?php esc_html_e('A read-aloud that a parent actually looks forward to.', 'brave-hearts'); ?></p></div>
    </div>
    <p class="parent-landing__disclaimer"><?php esc_html_e('Every child is different - these books are designed to spark curiosity and confidence, not to promise a particular academic result.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== FREE LEAD MAGNET ===================== -->
<section id="free" class="parent-landing__section">
  <div class="parent-landing__inner">
    <div class="parent-landing-lead">
      <div class="parent-landing-lead__content">
        <span class="parent-landing-eyebrow"><?php esc_html_e('Start free today', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('See it work before you buy a thing.', 'brave-hearts'); ?></h2>
        <p class="parent-landing__lead"><?php echo wp_kses_post(__('Get a free sample of <strong>Chapter 7 from The Mariana Trench</strong> - a real chapter from a full 12-chapter adventure - plus a matching explorer activity to try together.', 'brave-hearts')); ?></p>
        <div class="parent-landing-checklist parent-landing-checklist--compact parent-landing-lead__checklist">
          <div class="parent-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('A complete sample chapter to read tonight', 'brave-hearts'); ?></span></div>
          <div class="parent-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('A printable explorer activity that extends the story', 'brave-hearts'); ?></span></div>
          <div class="parent-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Simple tips for reading it with a 6–9 year old', 'brave-hearts'); ?></span></div>
        </div>

        <?php if ($download['ready']): ?>
          <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
              'id'                   => 'adventure-kit-signup',
              'lead_magnet'          => 'reluctant_reader_adventure_kit',
              'audience_type'        => 'parents_families',
              'title'                => __('Send Me the Free Adventure Kit', 'brave-hearts'),
              /* A6 (2026-08-03): duration claim removed. Was "A free 20-minute
                 reading adventure with a sample chapter, …". */
              'text'                 => __('A free reading adventure with a sample chapter, an explorer activity, and simple ways to make reading feel fun again.', 'brave-hearts'),
              'submit_label'         => __('Send me the free chapter & activity', 'brave-hearts'),
              'source_page'          => $source_page,
              'success_redirect_key' => 'adventure_kit_thank_you',
              'require_name'         => true,
          ]); ?>
          <p class="parent-landing-lead__fine-print"><?php esc_html_e('Free printable PDF · No purchase required · Signing up begins the Adventure Club email journey for parents. Unsubscribe anytime.', 'brave-hearts'); ?></p>
        <?php else: ?>
          <div class="parent-landing-coming-soon">
            <p class="parent-landing-eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
            <h3><?php esc_html_e('Send Me the Free Adventure Kit', 'brave-hearts'); ?></h3>
            <p class="parent-landing__lead" style="font-size:15px;"><?php esc_html_e('The Adventure Kit is still being finished. Check back soon to get your free copy by email.', 'brave-hearts'); ?></p>
            <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="parent-landing-lead__art">
        <div>
          <?php echo $mariana ? bhp_parent_landing_cover($mariana, 'large') : ''; ?>
          <p class="tag"><?php esc_html_e('Free · Chapter 7 sample', 'brave-hearts'); ?></p>
          <p class="sub"><?php esc_html_e('from The Mariana Trench', 'brave-hearts'); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== HOW THE BOOKS WORK ===================== -->
<section class="parent-landing__section parent-landing__section--muted">
  <div class="parent-landing__inner">
    <div class="parent-landing__header-block">
      <span class="parent-landing-eyebrow"><?php esc_html_e('How it works on the page', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Easy to pick up. Hard to put down.', 'brave-hearts'); ?></h2>
    </div>
    <div class="parent-landing-grid parent-landing-grid--features">
      <?php foreach ($how_it_works as $item): ?>
        <div class="parent-landing-card"><h3><?php echo esc_html($item['title']); ?></h3><p class="desc"><?php echo esc_html($item['text']); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== TRUST ===================== -->
<section class="parent-landing__section parent-landing__section--dark">
  <div class="parent-landing__inner parent-landing__inner--narrow">
    <p class="parent-landing-trust-eyebrow"><?php esc_html_e('Trusted by parents, teachers & reviewers', 'brave-hearts'); ?></p>
    <div class="parent-landing-stat-grid">
      <?php /* N4 (2026-08-03) — numberless stat tile. ZERO NEW WORDS: "Boise"
               moves into `__num`, the numeral 40 is dropped. The sibling tile
               "Verified / Amazon reviews" already uses a word in this slot, so
               no CSS changes. See page-audience-organizations.php's N4 note. */ ?>
      <div class="parent-landing-stat"><div class="parent-landing-stat__num"><?php esc_html_e('Boise', 'brave-hearts'); ?></div><p class="parent-landing-stat__label"><?php esc_html_e('classrooms with the series', 'brave-hearts'); ?></p></div>
      <div class="parent-landing-stat"><div class="parent-landing-stat__num parent-landing-stat__num--word"><?php esc_html_e('Verified', 'brave-hearts'); ?></div><p class="parent-landing-stat__label"><?php esc_html_e('Amazon reviews', 'brave-hearts'); ?></p></div>
      <div class="parent-landing-stat"><div class="parent-landing-stat__num"><?php esc_html_e('Kirkus', 'brave-hearts'); ?></div><p class="parent-landing-stat__label"><?php esc_html_e('reviewed title', 'brave-hearts'); ?></p></div>
      <div class="parent-landing-stat"><div class="parent-landing-stat__num">6&ndash;9</div><p class="parent-landing-stat__label"><?php esc_html_e('read-aloud & independent', 'brave-hearts'); ?></p></div>
    </div>
    <div class="parent-landing-review">
      <div class="parent-landing-review__stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
      <blockquote><p>&ldquo;My students were drawn to the vivid setting and sense of exploration. It&rsquo;s engaging, educational, and a great addition to any classroom or home library.&rdquo;</p></blockquote>
      <cite><?php esc_html_e('Payton, elementary teacher', 'brave-hearts'); ?> - <?php if ($testimonial_url): ?><a href="<?php echo esc_url($testimonial_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Verified Amazon review', 'brave-hearts'); ?></a><?php else: ?><?php esc_html_e('Verified Amazon review', 'brave-hearts'); ?><?php endif; ?></cite>
    </div>
  </div>
</section>

<!-- ===================== AUTHOR ===================== -->
<section class="parent-landing__section">
  <div class="parent-landing__inner parent-landing__inner--narrow">
    <div class="parent-landing-author">
      <div class="parent-landing-media parent-landing-media--tall parent-landing-author__photo">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/founder-and-charlotte.webp'); ?>" alt="<?php esc_attr_e('Andrew, author of Adventures of Charlotte and Henry, with Charlotte', 'brave-hearts'); ?>" loading="lazy" decoding="async">
      </div>
      <div>
        <span class="parent-landing-eyebrow"><?php esc_html_e('Why these books exist', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('Written for one real kid first.', 'brave-hearts'); ?></h2>
        <p><?php echo wp_kses_post(__('Andrew created the <em>Adventures of Charlotte and Henry</em> for his niece, Charlotte - with Henry, the real family dog, along for every expedition. The goal was simple: give a young reader stories exciting enough to finish, and true enough to spark real questions about the world.', 'brave-hearts')); ?></p>
        <p><?php esc_html_e('Every adventure starts from something true - a real trench, a real mountain, a real river - so the wonder on the page is wonder your child can go look up for themselves.', 'brave-hearts'); ?></p>
        <blockquote>&ldquo;<?php esc_html_e('The real world is still wild enough. Go look up.', 'brave-hearts'); ?>&rdquo;</blockquote>
      </div>
    </div>
  </div>
</section>

<!-- ===================== FOUNDER CARD (video-block replacement) ===================== -->
<!-- Sprint (2026-07-16): no approved 60-90s author video exists yet (confirmed:
     no video file anywhere in the repo, no doc records one as production-ready).
     Per the approved plan, replaced the "video coming soon" placeholder with a
     compact founder card using the existing approved founder photo. Copy here
     is deliberately short and distinct from the longer "Why These Books Exist"
     narrative above -- not a duplicate. When a real video is produced, this
     section can be swapped back to a video player with no other template change.

     Corrected (2026-07-17): the founder photo above (assets/images/handoff/
     founder-and-charlotte.webp) was also being rendered here, so the exact
     same photo appeared in two consecutive sections. Removed the image from
     this section only -- kept in the "Written for one real kid first."
     section above, per the approved correction. No replacement/stock/
     generated image introduced. Reuses the existing centered
     .parent-landing__header-block pattern (already used elsewhere on this
     page, e.g. the PROBLEM and HOW-IT-WORKS sections) instead of the 2-column
     .parent-landing-author grid, so removing the photo doesn't leave an
     empty grid column -- a single centered text column reads as intentional,
     not empty, on both desktop and mobile. Copy unchanged. -->
<section class="parent-landing__section parent-landing__section--muted">
  <div class="parent-landing__inner parent-landing__inner--narrow">
    <div class="parent-landing__header-block">
      <span class="parent-landing-eyebrow"><?php esc_html_e('A quick hello', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Hi, I’m Andrew.', 'brave-hearts'); ?></h2>
      <p class="parent-landing__lead"><?php esc_html_e('I created Charlotte and Henry because I wanted children to fall in love with exploring the real world through stories. Every adventure begins in a real place and is designed to encourage curiosity, courage, and kindness.', 'brave-hearts'); ?></p>
      <p class="parent-landing__lead"><?php esc_html_e('I hope these stories inspire your family to discover someplace new together.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="parent-landing__section parent-landing__section--muted">
  <div class="parent-landing__inner parent-landing__inner--content">
    <h2 style="text-align:center;margin-bottom:40px;"><?php esc_html_e('Questions parents ask', 'brave-hearts'); ?></h2>
    <div class="parent-landing-faq">
      <?php foreach ($faqs as $faq): ?>
        <details class="parent-landing-faq__item" data-question="<?php echo esc_attr($faq[0]); ?>">
          <summary><?php echo esc_html($faq[0]); ?><span class="icon" aria-hidden="true">+</span></summary>
          <p><?php echo esc_html($faq[1]); ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== FINAL CTA ===================== -->
<section class="parent-landing__section parent-landing__section--major parent-landing-final">
  <div class="parent-landing__inner parent-landing-final__inner">
    <h2><?php esc_html_e('Tonight’s chapter could be the one that changes how they feel about reading.', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Start with one free chapter tonight - or bring home all three adventures at once.', 'brave-hearts'); ?></p>
    <div class="parent-landing-final__ctas">
      <a class="btn btn-gold" href="#free" data-parent-free-cta data-bhp-signup-modal-open="adventure-kit-modal" data-bhp-signup-modal-source="final_cta" data-bhp-event="parent_final_cta_click" data-bhp-source="adventure_kit_landing"><?php esc_html_e('Get the free chapter & activity', 'brave-hearts'); ?></a>
      <?php
      /*
       * 2026-08-05 — was `href="#collection"`. The collection section now sits
       * at the TOP of the page, so the anchor would have scrolled the customer
       * backwards to reach a button. It is now the button. `sync => true` keeps
       * it on whichever format the price card is showing.
       */
      echo bhp_collection_add_to_cart_cta([
          'label'      => __('Get the Complete Collection', 'brave-hearts'),
          'class'      => 'btn btn-outline-light',
          'form_class' => 'parent-landing-collection-form',
          'sync'       => true,
          'event'      => 'parent_final_collection_cta_click',
          'source'     => 'adventure_kit_landing',
      ]);
      ?>
    </div>
  </div>
</section>

<!-- ===================== STICKY MINI-CTA ===================== -->
<div class="parent-landing-stickybar" data-parent-stickybar>
  <div class="parent-landing-stickybar__row">
    <span class="parent-landing-stickybar__text"><?php esc_html_e('Free Chapter 7 + explorer activity - no purchase needed.', 'brave-hearts'); ?></span>
    <div class="parent-landing-stickybar__ctas">
      <a class="btn btn-gold" href="#free" data-parent-free-cta data-bhp-signup-modal-open="adventure-kit-modal" data-bhp-signup-modal-source="sticky_bar"><?php esc_html_e('Get it free', 'brave-hearts'); ?></a>
      <?php
      /*
       * 2026-08-05 — the footer-bar "Collection" control Andrew named. Was an
       * in-page anchor; now adds the set and lands on /checkout/. Label kept
       * verbatim — see the identical note on the educators page.
       */
      echo bhp_collection_add_to_cart_cta([
          'label'      => __('Collection', 'brave-hearts'),
          'class'      => 'btn btn-outline-light',
          'form_class' => 'parent-landing-collection-form',
          'sync'       => true,
          'event'      => 'parent_stickybar_collection_cta_click',
          'source'     => 'adventure_kit_landing',
      ]);
      ?>
    </div>
  </div>
</div>

<?php
/*
 * ===================== CTA-TRIGGERED SIGNUP MODAL =====================
 * theme 1.19.223, 2026-08-13, `CYCLE158-LD-SIGNUP-POPUP`.
 *
 * Every "get the free chapter" CTA on this page now OPENS this dialog with
 * the caret already in the email field, instead of scrolling the visitor
 * down to #free. Andrew Signore, current turn, relayed by `chief-of-staff`:
 * "no scrolling, immediate capture".
 *
 * ⛔ THE INLINE #free PANEL ABOVE IS NOT REMOVED AND MUST NOT BE. It is the
 *    no-JS fallback, it is what the CTAs' `href="#free"` still points at, it
 *    keeps the `/reluctant-reader-adventure-kit/#free` deep link working,
 *    and it keeps the capture copy in the indexable page body.
 *
 * ⛔ GATED ON THE SAME `$download['ready']` FLAG AS THE PANEL. If the kit PDF
 *    is ever unset, this modal does not render at all, the CTAs find no
 *    modal to open, and they fall back to scrolling to the "coming soon"
 *    block — which is the correct behaviour and is why the JS resolves its
 *    target before it calls preventDefault().
 *
 * ⛔ NOT A LEAD-MAGNET POPUP. It has no timer, no scroll trigger and no exit
 *    trigger, so it does not reverse the 2026-07-19 one-popup ruling. This
 *    page is still excluded from the sitewide parent popup by
 *    `bhp_should_show_any_popup()`, unchanged.
 *
 * Copy is reused VERBATIM from the inline panel and the shipped exit-intent
 * modal — the same offer must not be described in two different ways, and no
 * new claim, number or duration is introduced here.
 */
if ($download['ready']) {
    get_template_part('template-parts/acquisition/signup-modal', null, [
        'id'                   => 'adventure-kit-modal',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'audience_type'        => 'parents_families',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'eyebrow'              => __('Free for parents', 'brave-hearts'),
        'title'                => __('Send Me the Free Adventure Kit', 'brave-hearts'),
        'text'                 => __('A free reading adventure with a sample chapter, an explorer activity, and simple ways to make reading feel fun again.', 'brave-hearts'),
        'submit_label'         => __('Send me the free chapter & activity', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'trust_text'           => __('Free printable PDF. No purchase required.', 'brave-hearts'),
    ]);
}
?>

</div>
<?php get_footer(); ?>
