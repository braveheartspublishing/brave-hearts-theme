<?php
/**
 * Template Name: Audience Landing - Gift Buyers
 * Description: Gift-buyer-facing landing page (grandparents, aunts/uncles,
 * family friends, birthday/holiday shoppers) for the Meaningful Gift Guide
 * lead magnet. Stays in a "coming soon" state -- form wired but inactive --
 * until the guide PDF is set under Settings -> Lead Magnets (see
 * bhp_get_gift_guide_download()).
 *
 * Shares the audience-landing design system (assets/css/audience-landing.css
 * + assets/js/audience-landing.js) with the other 3 core audience pages.
 * All lead capture runs through the site's real signup pipeline
 * (template-parts/acquisition/lead-magnet-cta.php -> signup-form.php ->
 * bhp_mailchimp_signup), never a fork of it. No public coupon code.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_gift_guide_download();
$adventures = bhp_get_series_adventures();
$complete_collection_url = home_url('/complete-collection/');

if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    $bhp_gift_landing_payload = wp_json_encode([
        'event'      => 'gift_landing_view',
        'funnel'     => 'gift_buyers',
        'page_type'  => 'landing_page',
        'lead_offer' => 'meaningful_gift_guide',
        'audience'   => 'gift_buyers',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_gift_landing_payload; ?>);
    </script>
    <?php
endif;

$mariana = $adventures['mariana_trench'] ?? [];
$everest = $adventures['mount_everest'] ?? [];
$amazon  = $adventures['amazon_rainforest'] ?? [];

$pain_points = [
    __('Another toy that’s forgotten within a week.', 'brave-hearts'),
    __('Screens are easy, but they don’t leave much to talk about later.', 'brave-hearts'),
    __('It’s hard to know what a kid you don’t see every day will actually enjoy.', 'brave-hearts'),
    __('You want the gift to mean something, not just fill a box.', 'brave-hearts'),
];

$solution_points = [
    __('<strong>An adventure, not just an object</strong> - a story a child steps into and remembers.', 'brave-hearts'),
    __('<strong>Something to talk about</strong> - real places and discoveries that spark conversation at the next visit.', 'brave-hearts'),
    __('<strong>Approachable chapters</strong> - short enough that finishing feels possible for any reading level.', 'brave-hearts'),
    __('<strong>A gift that keeps going</strong> - three adventures to grow into, not one thing used once.', 'brave-hearts'),
    __('<strong>Charlotte &amp; Henry</strong> - familiar companions a child looks forward to seeing again.', 'brave-hearts'),
];

$occasions = [
    ['title' => __('Birthdays', 'brave-hearts'), 'text' => __('A gift that starts a story, not just gets unwrapped and set aside.', 'brave-hearts')],
    ['title' => __('Holidays', 'brave-hearts'), 'text' => __('Something under the tree that turns into weeks of reading together.', 'brave-hearts')],
    ['title' => __('Grandparent visits', 'brave-hearts'), 'text' => __('A shared adventure to read together during the visit - and talk about after.', 'brave-hearts')],
    ['title' => __('Milestones', 'brave-hearts'), 'text' => __('Finishing a school year, a new reading level, a first chapter book - a gift that marks the moment.', 'brave-hearts')],
    ['title' => __('Classroom & teacher gifts', 'brave-hearts'), 'text' => __('A meaningful addition to a classroom library or reading corner.', 'brave-hearts')],
    ['title' => __('“Just because”', 'brave-hearts'), 'text' => __('A small, meaningful way to say you were thinking of them.', 'brave-hearts')],
];

$faqs = [
    [__('What age is this gift right for?', 'brave-hearts'), __('The books are written for readers roughly ages 6–9 - approachable for a new independent reader and rich enough for a shared read-aloud with a grandparent or family friend.', 'brave-hearts')],
    [__('What’s in the Meaningful Gift Guide?', 'brave-hearts'), __('A free guide to choosing a gift that sparks curiosity and shared memories - plus a look at what’s inside the books themselves.', 'brave-hearts')],
    [__('What’s included in the Complete Collection?', 'brave-hearts'), __('All three adventures - The Mariana Trench, Mount Everest, and The Amazon - in one purchase and one shipment. The primary way to give the whole series as a gift.', 'brave-hearts')],
    [__('Paperback or hardcover?', 'brave-hearts'), __('Both include the same three complete stories. Paperback is lightweight and easy for small hands; hardcover is a durable keepsake edition - often the better gift choice.', 'brave-hearts')],
    [__('How does shipping work?', 'brave-hearts'), __('Books are printed and shipped from the USA with tracking. You’ll receive one shipment with all three books. Because each book is printed to order, we suggest ordering early - especially around busy holidays.', 'brave-hearts')],
    [__('Ordering for a birthday or holiday - how far ahead should I order?', 'brave-hearts'), __('Since books are printed to order, we recommend ordering at least 2 weeks before the date you need it, especially around busy holiday seasons.', 'brave-hearts')],
    [__('Is gift wrapping or a gift note available?', 'brave-hearts'), __('Not currently - books ship in protective packaging without gift wrap or a gift note. We’re noting this as something to consider for the future.', 'brave-hearts')],
    [__('Can I buy just one book instead of the whole Collection?', 'brave-hearts'), __('Yes - each book is also available individually if you’d rather start with one adventure. The Complete Collection is simply the most popular way to give the whole series as a gift.', 'brave-hearts')],
    [__('Can I unsubscribe from emails?', 'brave-hearts'), __('Anytime, with one click at the bottom of any email. Getting the free guide never requires a purchase.', 'brave-hearts')],
];

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

// Sprint A, Phase 4: swapped from amz-mariana-04 (a teacher/classroom
// review -- the wrong voice for a gift buyer) to amz-mariana-02, a real,
// already-approved family/bedtime-reading review from the same registry
// bhp_get_amazon_review_registry() uses everywhere else on the site.
$testimonial_url = '';
if (function_exists('bhp_get_amazon_review_registry')) {
    foreach (bhp_get_amazon_review_registry() as $review) {
        if ('amz-mariana-02' === $review['id']) {
            $testimonial_url = $review['source_url'];
            break;
        }
    }
}
?>
<div class="audience-landing" data-audience-landing>

<!-- ===================== HERO ===================== -->
<section class="audience-landing-hero audience-landing-hero--funnel">
  <div class="audience-landing-hero__bg" aria-hidden="true"></div>
  <div class="audience-landing__inner audience-landing-hero__grid">
    <div>
      <span class="audience-landing-eyebrow audience-landing-hero__badge"><?php esc_html_e('For grandparents, aunts, uncles & family friends', 'brave-hearts'); ?></span>
      <h1><?php esc_html_e('Give a gift they’ll actually remember.', 'brave-hearts'); ?></h1>
      <p class="audience-landing__lead"><?php esc_html_e('An adventure a child steps into, not another toy that’s forgotten by next week - real places, real discoveries, and a story worth talking about.', 'brave-hearts'); ?></p>
      <?php
      /*
       * ⭐ 1.19.213 (CYCLE150-LD) — ONE CTA, NOT TWO. The secondary
       *    "Give the Complete Collection" outline button is REMOVED, not
       *    hidden: Andrew Signore, relayed by `chief-of-staff` (⛔ not
       *    witnessed here), "it will distract from the main CTA."
       *    Its `gift_hero_secondary_cta_click` event goes with it; the
       *    primary CTA's event, source and href are byte-unchanged, and the
       *    collection is still reachable from the scanbar's fast-purchase
       *    band, the raised Best Value card and the sticky bar.
       */
      ?>
      <div class="audience-landing-hero__ctas">
        <a class="btn btn-primary" href="#free" data-audience-free-cta data-bhp-event="gift_hero_primary_cta_click" data-bhp-source="gift_landing"><?php esc_html_e('Get the Meaningful Gift Guide', 'brave-hearts'); ?></a>
      </div>
      <?php
      /*
       * ⭐ 1.19.213 — THE COLLECTION CAROUSEL, DIRECTLY UNDER THE PRIMARY CTA.
       *    This is the founder's slot 4. The full spec, the reason the static
       *    three-book hero lockup was deleted rather than moved, and the two
       *    new placement keys are stated ONCE in `inc/collection-gallery.php`,
       *    not repeated on four templates.
       *
       * ⛔ THIS IS A MOVE, NOT AN ADDITION. The identical call that used to
       *    sit inside #collection below is gone. `bhp_cx_render_collection_
       *    gallery()` renders once per request, so a leftover second call
       *    would be a silent no-op rather than a visible bug — which is
       *    exactly why the old one was removed rather than left in place.
       */
      if (function_exists('bhp_cx_render_collection_gallery')) {
          echo '<div class="audience-landing-hero__gallery">';
          bhp_cx_render_collection_gallery();
          echo '</div>';
      }
      ?>
      <div class="audience-landing-hero__proof">
        <span>&#9733; <?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('Three complete adventures', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('A keepsake, not a throwaway gift', 'brave-hearts'); ?></span>
      </div>
      <?php
      /*
       * ⭐ 1.19.213 — THE STATIC THREE-COVER HERO ART IS REMOVED.
       *    Andrew, relayed: slide 1 of the carousel above IS the three-book
       *    image, so the lockup was the same picture twice in one eyeful and
       *    it was the single largest thing pushing the CTA under the fold.
       *    The proof row above keeps its position relative to the CTA block;
       *    the "Ocean · Mountain · Rainforest" caption and the hero logo went
       *    with the art column. ⚠ The three covers are NOT gone from the page
       *    — they are still the `audience-landing-books` grid inside
       *    #collection, and still every slide of the carousel.
       */
      ?>
    </div>
  </div>
</section>

<!-- ===================== QUICK-SCAN BAR ===================== -->
<section class="audience-landing-scanbar">
  <div class="audience-landing__inner audience-landing__inner--narrow audience-landing-scanbar__row">
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Ages 6–9 · a gift that grows with them', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Hardcover keepsake option', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('One shipment, gift-ready', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Three complete adventures', 'brave-hearts'); ?></span>
  </div>
</section>

<?php
/*
 * ⭐ 1.19.210 (2026-08-09, CYCLE148-LD-02) — THE FAST-PURCHASE BAND, right
 *    below the checkmarks. Andrew Signore's words and the reasoning for
 *    building an ADDITION rather than moving the price card live in the
 *    template part's own header — stated once, there, not repeated on every
 *    funnel page.
 *
 * ⛔ MOBILE-ONLY BY CSS, never by a server-side device test: a
 *    `wp_is_mobile()` branch would be cached wrong by SiteGround's page
 *    cache the first time a desktop visitor warmed the page.
 *
 * ✅ SELF-GATING. With no `$formats` map on this page the part renders
 *    nothing and the page is byte-identical to the release before it.
 */
$prefix = 'audience-landing';
$event  = 'gift_fastbuy_cta_click';
$source = 'gift_landing';
require locate_template('template-parts/commerce/funnel-fast-purchase.php');
?>

<!-- ===================== COMPLETE COLLECTION (PRIMARY GIFT) ===================== -->
<section id="collection" class="audience-landing__section audience-landing__section--major">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('The complete collection', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Three complete adventures - the whole gift.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php echo wp_kses_post(__('Beneath the ocean, to the highest mountain, and deep into the rainforest - the full <em>Adventures of Charlotte &amp; Henry</em> collection in one shipment.', 'brave-hearts')); ?></p>
      <p class="audience-landing__lead" style="font-size:15px;"><?php esc_html_e('Printed to order and shipped directly to you. Ordering for a specific birthday or holiday? We suggest ordering early so it arrives in time.', 'brave-hearts'); ?></p>
    </div>

    <?php
    /*
     * ⭐ 1.19.213 — THE CAROUSEL CALL THAT STOOD HERE HAS MOVED TO THE HERO,
     *    directly under the primary CTA (founder slot 4). It is a MOVE: there
     *    is still exactly one call, one instance and one DOM id per request.
     *
     * The 2026-08-03 note that lived here is preserved because its reasoning
     * is still live and still correct — "the three cover thumbnails below are
     * the same flat ebook JPEGs that appear on every page; the composite and
     * the two flip-throughs are the only assets that show a physical printed
     * book. They belong between the promise and the price." ⚠ What changed is
     * only WHICH promise: the founder's structure puts them under the hero's
     * promise instead of this section's, and puts the price directly under the
     * checkmarks so a decided visitor never scrolls past a gallery to buy.
     *
     * ⭐ AND THE BOOKS GRID MOVES BELOW THE PRICE CARD — founder slot 6, "the
     *    Best Value buy section, raised… So they can buy easier on the page."
     *    Moved STRUCTURALLY, not with CSS `order`, so keyboard and reading
     *    order still match the visible order. Nothing is deleted: the grid
     *    renders unchanged immediately after the card.
     */
    ?>
    <?php if ($bundle_available): ?>
      <div class="audience-landing-pricecard" data-audience-pricing-card>
        <span class="audience-landing-pricecard__badge">&#9733; <?php esc_html_e('The complete gift - all three books', 'brave-hearts'); ?></span>

        <div class="audience-landing-format-toggle" role="radiogroup" aria-label="<?php esc_attr_e('Choose your format', 'brave-hearts'); ?>">
          <?php foreach ($bhp_format_order as $format):
            $is_pb      = 'paperback' === $format;
            $is_default = $bhp_default_format === $format;
          ?>
            <button type="button" role="radio" aria-checked="<?php echo $is_default ? 'true' : 'false'; ?>"
              class="audience-landing-format-btn<?php echo $is_default ? ' is-selected' : ''; ?>"
              data-audience-format-btn="<?php echo esc_attr($format); ?>">
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
              : __('Hardcover · sewn binding · a keepsake gift edition that lasts', 'brave-hearts');
        ?>
          <div data-audience-format-panel="<?php echo esc_attr($format); ?>" <?php echo $is_default ? '' : 'hidden'; ?>>
            <h3><?php echo esc_html('Complete ' . $f['name'] . ' Collection'); ?></h3>
            <p class="subtitle"><?php echo esc_html($desc); ?></p>
            <div class="audience-landing-pricecard__included">
              <?php if ($mariana): ?><div class="audience-landing-pricecard__included-row"><span class="check">&#10003;</span> <?php esc_html_e('Adventures of Charlotte & Henry: The Mariana Trench', 'brave-hearts'); ?></div><?php endif; ?>
              <?php if ($everest): ?><div class="audience-landing-pricecard__included-row"><span class="check">&#10003;</span> <?php esc_html_e('Adventures of Charlotte & Henry: Mount Everest', 'brave-hearts'); ?></div><?php endif; ?>
              <?php if ($amazon): ?><div class="audience-landing-pricecard__included-row"><span class="check">&#10003;</span> <?php esc_html_e('Adventures of Charlotte & Henry: The Amazon', 'brave-hearts'); ?></div><?php endif; ?>
            </div>
            <div class="audience-landing-pricecard__foot">
              <div class="audience-landing-pricecard__badges">
                <span class="audience-landing-pricecard__badge-pill audience-landing-pricecard__badge-pill--save"><?php echo esc_html(sprintf(__('Save $%s', 'brave-hearts'), number_format($f['save'], 2))); ?></span>
                <span class="audience-landing-pricecard__badge-pill audience-landing-pricecard__badge-pill--muted">&#10003; <?php esc_html_e('One shipment', 'brave-hearts'); ?></span>
                <span class="audience-landing-pricecard__badge-pill audience-landing-pricecard__badge-pill--muted">&#10003; <?php esc_html_e('Three complete adventures', 'brave-hearts'); ?></span>
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
               * opposite of emphasis. The pill is REMOVED here and the same
               * fact is re-stated below, in bold, on its own line, with the
               * "$5.00 savings" wording Andrew asked for in the same week.
               *
               * ⛔ REMOVED FROM THIS ROW, NOT FROM THE PAGE, and still gated
               *    on the plugin's live offer state.
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
              <div class="audience-landing-pricecard__price-row">
                <span class="label"><?php esc_html_e('Complete Collection price', 'brave-hearts'); ?></span>
                <span><span class="audience-landing-pricecard__price-strike">$<?php echo esc_html(number_format($f['combined'], 2)); ?></span><span class="audience-landing-pricecard__price-final">$<?php echo esc_html(number_format($f['collection'], 2)); ?></span></span>
              </div>
              <p class="audience-landing-pricecard__ship-note"><?php echo esc_html(bhp_book_landing_ship_note($f['shipping'], '' !== $bhp_free_bullets)); ?></p>
              <?php
              /*
               * 2026-08-05 — Andrew: "2 click journey to purchase". Was an <a>
               * to /complete-collection/; now adds this panel's three real books
               * and lands on /checkout/. Label ("Give the ___ Collection"),
               * classes and both analytics attributes are unchanged.
               * See inc/collection-cta.php.
               */
              echo bhp_collection_add_to_cart_cta([
                  'format'     => $format,
                  'label'      => sprintf(__('Give the %s Collection', 'brave-hearts'), $f['name']),
                  'class'      => 'btn btn-primary',
                  'form_class' => 'audience-landing-pricecard__cta-form',
                  'event'      => 'gift_collection_cta_click',
                  'source'     => 'gift_landing',
              ]);
              ?>
              <p class="audience-landing-pricecard__link-row"><?php esc_html_e('Secure checkout · Tracking provided ·', 'brave-hearts'); ?> <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('View individual books', 'brave-hearts'); ?></a></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="align-center" style="text-align:center;margin-top:40px;">
        <a class="btn btn-primary" href="<?php echo esc_url($complete_collection_url); ?>"><?php esc_html_e('Give the Complete Collection', 'brave-hearts'); ?></a>
      </p>
    <?php endif; ?>

    <?php /* 1.19.213 — the three-book grid, relocated to sit AFTER the price
             card. Same markup, same covers, same copy; only its position in
             the section changed. See the note above the price card. */ ?>
    <div class="audience-landing-books">
      <?php if ($mariana): ?>
        <div class="audience-landing-book"><?php echo bhp_parent_landing_cover($mariana); ?><p class="eyebrow-line"><?php esc_html_e('Book One · Ocean', 'brave-hearts'); ?></p><h3><?php echo esc_html($mariana['title'] ?? 'The Mariana Trench'); ?></h3><p class="desc"><?php esc_html_e('Deep-sea science and courage in the unknown.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
      <?php if ($everest): ?>
        <div class="audience-landing-book"><?php echo bhp_parent_landing_cover($everest); ?><p class="eyebrow-line"><?php esc_html_e('Book Two · Mountain', 'brave-hearts'); ?></p><h3><?php echo esc_html($everest['title'] ?? 'Mount Everest'); ?></h3><p class="desc"><?php esc_html_e('Historic explorers, teamwork, and perseverance.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
      <?php if ($amazon): ?>
        <div class="audience-landing-book"><?php echo bhp_parent_landing_cover($amazon); ?><p class="eyebrow-line"><?php esc_html_e('Book Three · Rainforest', 'brave-hearts'); ?></p><h3><?php echo esc_html($amazon['title'] ?? 'The Amazon'); ?></h3><p class="desc"><?php esc_html_e('Rainforest wildlife, river systems, and kindness.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===================== PROBLEM ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Sound familiar?', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Disposable gifts don’t leave much behind.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('It’s not about spending more - it’s about choosing something that actually lasts in a child’s memory:', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-grid">
      <?php foreach ($pain_points as $i => $point): ?>
        <div class="audience-landing-card"><div class="audience-landing-card__num"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></div><p><?php echo esc_html($point); ?></p></div>
      <?php endforeach; ?>
    </div>
    <p class="audience-landing__pull-quote"><?php esc_html_e('A book a child finishes and loves becomes a memory tied to you - the person who gave it to them.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== SOLUTION ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing-split">
    <div>
      <span class="audience-landing-eyebrow"><?php esc_html_e('Why books create lasting experiences', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('A gift that starts an adventure, not just gets unwrapped.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Brave Hearts books give a child curiosity, reading confidence, and a story worth remembering - the kind of gift that keeps being talked about long after the wrapping paper is gone.', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-checklist">
      <?php foreach ($solution_points as $point): ?>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== OCCASIONS ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Perfect for', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Gift occasions that call for something meaningful.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-grid audience-landing-grid--cols-3">
      <?php foreach ($occasions as $item): ?>
        <div class="audience-landing-card"><h3><?php echo esc_html($item['title']); ?></h3><p class="desc"><?php echo esc_html($item['text']); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== FREE LEAD MAGNET ===================== -->
<section id="free" class="audience-landing__section">
  <div class="audience-landing__inner">
    <div class="audience-landing-lead">
      <div class="audience-landing-lead__content">
        <span class="audience-landing-eyebrow"><?php esc_html_e('Free gift guide', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('Choose a gift that means something.', 'brave-hearts'); ?></h2>
        <p class="audience-landing__lead"><?php echo wp_kses_post(__('Get the free <strong>Meaningful Gift Guide</strong> - a guide to choosing a gift that sparks curiosity and creates a shared memory, not just fills a box.', 'brave-hearts')); ?></p>
        <div class="audience-landing-checklist audience-landing-checklist--compact audience-landing-lead__checklist">
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('What makes a gift memorable for a 6–9 year old', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('A look inside what the child receives', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Simple ideas for gifting and reading together', 'brave-hearts'); ?></span></div>
        </div>

        <?php if ($download['ready']): ?>
          <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
              'id'                   => 'gift-guide-signup',
              'lead_magnet'          => 'meaningful_gift_guide',
              'audience_type'        => 'gift_buyers',
              'title'                => __('Send Me the Meaningful Gift Guide', 'brave-hearts'),
              'text'                 => __('A free guide to choosing a gift that sparks curiosity and creates a shared memory.', 'brave-hearts'),
              'submit_label'         => __('Get the Meaningful Gift Guide', 'brave-hearts'),
              'source_page'          => $source_page,
              'require_name'         => true,
              // BH-04: route a successful signup to the dedicated gift-guide
              // thank-you page instead of the generic inline "Welcome to the
              // Adventure Club" message.
              'success_redirect_key' => 'gift_guide_thank_you',
          ]); ?>
          <p class="audience-landing-lead__fine-print"><?php esc_html_e('Free printable PDF · No purchase required · Occasional gift-guide and reading updates. Unsubscribe anytime.', 'brave-hearts'); ?></p>
        <?php else: ?>
          <div class="audience-landing-coming-soon">
            <p class="audience-landing-eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
            <h3><?php esc_html_e('Send Me the Meaningful Gift Guide', 'brave-hearts'); ?></h3>
            <p class="audience-landing__lead" style="font-size:15px;"><?php esc_html_e('The Gift Guide is still being finished. Check back soon to get your free copy by email.', 'brave-hearts'); ?></p>
            <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="audience-landing-lead__art">
        <div>
          <?php /* 2026-07-17: real cover in place of the "in progress" placeholder.
             Source PDF (production Media Library attachment #392, Ultimate-Gift.pdf)
             is the only approved asset for this lead magnet; its cover art itself
             reads "The Ultimate Children's Book Gift Guide" -- an internal/asset
             title that predates the page's current "Meaningful Gift Guide" public
             name. Andrew's explicit direction: use this cover as-is (no redesign),
             keep all page copy/CTAs/Mailchimp as "Meaningful Gift Guide", and alt
             text describes the actual image rather than the marketing name. */ ?>
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/gift-guide-cover.webp'); ?>" alt="<?php esc_attr_e('Front cover of the Meaningful Gift Guide (free gift guide)', 'brave-hearts'); ?>" loading="lazy" decoding="async">
          <p class="tag"><?php esc_html_e('Free · Meaningful Gift Guide', 'brave-hearts'); ?></p>
          <p class="sub"><?php esc_html_e('Printable PDF guide', 'brave-hearts'); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== WHAT THE CHILD RECEIVES ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('What they’ll actually receive', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Easy to open. Hard to put down.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-grid audience-landing-grid--features">
      <div class="audience-landing-card"><h3><?php esc_html_e('Approachable chapters', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('Twelve short chapters so each reading session has a clear finish line.', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('Illustrations that help', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('Black-and-white art breaks up the page and keeps the story moving.', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('Real-world discovery', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('Actual science, geography, and history - the kind kids repeat at dinner.', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('Continuing characters', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('Charlotte and Henry return in every book, so the next one feels familiar.', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('A keepsake option', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('Hardcover editions built to last on a shelf, not get tossed out.', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('Three destinations', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('Ocean, mountain, rainforest - three complete journeys to grow into.', 'brave-hearts'); ?></p></div>
    </div>
  </div>
</section>

<!-- ===================== TRUST ===================== -->
<section class="audience-landing__section audience-landing__section--dark">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <p class="audience-landing-trust-eyebrow"><?php esc_html_e('Trusted by families & reviewers', 'brave-hearts'); ?></p>
    <div class="audience-landing-stat-grid">
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">3</div><p class="audience-landing-stat__label"><?php esc_html_e('complete adventures in one gift', 'brave-hearts'); ?></p></div>
      <?php /* 2026-07-17: was 5 literal star glyphs (&#9733; x5) reusing
         .audience-landing-stat__num, whose 51px font-size is sized for
         short numbers/words ("3", "Kirkus") -- at that size the 5-glyph
         string overflowed the fixed-width grid cell and rendered outside
         the card border. Plain "5-star" text renders at the exact same
         size/weight/color as every other card with zero overflow risk and
         no new CSS; the full star treatment stays on the review quote
         below, which is the primary star display per Andrew's direction. */ ?>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('5-star', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('verified family reviews', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Kirkus', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('featured title', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">2</div><p class="audience-landing-stat__label"><?php esc_html_e('formats - paperback & hardcover keepsake', 'brave-hearts'); ?></p></div>
    </div>
    <div class="audience-landing-review">
      <div class="audience-landing-review__stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
      <blockquote><p>&ldquo;The adventure is fun, educational, and extremely well written with a nice flow to read aloud. We read a few chapters each night and gave him something to look forward to at bedtime.&rdquo;</p></blockquote>
      <cite><?php if ($testimonial_url): ?><a href="<?php echo esc_url($testimonial_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Amazon customer review - Verified Purchase', 'brave-hearts'); ?></a><?php else: ?><?php esc_html_e('Amazon customer review - Verified Purchase', 'brave-hearts'); ?><?php endif; ?></cite>
    </div>
  </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--content">
    <h2 style="text-align:center;margin-bottom:40px;"><?php esc_html_e('Questions gift buyers ask', 'brave-hearts'); ?></h2>
    <div class="audience-landing-faq">
      <?php foreach ($faqs as $faq): ?>
        <details class="audience-landing-faq__item" data-question="<?php echo esc_attr($faq[0]); ?>">
          <summary><?php echo esc_html($faq[0]); ?><span class="icon" aria-hidden="true">+</span></summary>
          <p><?php echo esc_html($faq[1]); ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== COMPASS (FD-30) ===================== -->
<?php
/*
 * FD-30, Andrew Signore 2026-08-03 (relayed): "approve compass module".
 * Gift-buyers page ONLY, ONE placement, deliberately scarce.
 * The dedication is reproduced VERBATIM from the printed front matter of
 * Volume I (The Mariana Trench). Do not edit, reflow or "improve" it.
 * NOTE: the dedication differs per book -- Volume I reads "Be brave.",
 * Volume II (Mount Everest) reads "Be strong." (CYCLE141-CX-38). This
 * module deliberately uses Volume I's wording. A future editor
 * "correcting" it against Everest would be introducing an error.
 * NO CTA, NO link, NO analytics event inside this section -- by design.
 * The compass mark is decorative: the text carries all of the meaning.
 * The mark is served byte-unchanged from the approved brand export and
 * must never be recoloured, cropped, rotated, animated or composited.
 */
?>
<section class="audience-landing__section audience-landing__section--major bhp-compass">
  <div class="audience-landing__inner audience-landing__inner--content">
    <h2 class="screen-reader-text"><?php esc_html_e('Why these books exist', 'brave-hearts'); ?></h2>

    <img class="bhp-compass__mark"
         src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/brand/brave-hearts-compass-icon.png'); ?>"
         alt="" aria-hidden="true" width="48" height="48"
         loading="lazy" decoding="async">

    <blockquote class="bhp-compass__dedication">
      <p><?php echo wp_kses_post(__('Dedicated to my beautiful niece, Charlotte.<br>Always -<br>Be kind.<br>Be brave.<br>Seek adventure.<br>Love, Uncle Andrew.', 'brave-hearts')); ?></p>
    </blockquote>

    <?php
    /*
     * The second sentence is wrapped in a nowrap span so the line can only
     * ever break BETWEEN the two sentences, never inside "A compass."
     * Measured defect this fixes: with the plain string, every viewport
     * from 320px to 1440px broke it as "Not just a story. A / compass." --
     * greedy wrapping always fits the orphan "A" onto line one, and no
     * max-width narrow enough to prevent that leaves the line readable.
     * `text-wrap: balance` also fixes it but is not universally supported
     * and, combined with this span, balances to a WORSE split -- both were
     * measured before choosing. The rendered text is unchanged, character
     * for character: "Not just a story. A compass."
     */
    ?>
    <p class="bhp-compass__line"><?php echo wp_kses_post(__('Not just a story. <span class="bhp-compass__nb">A compass.</span>', 'brave-hearts')); ?></p>

    <p class="bhp-compass__context"><?php esc_html_e('Every Brave Hearts book began as a gift to one little girl.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== FINAL CTA ===================== -->
<section class="audience-landing__section audience-landing__section--major audience-landing-final">
  <div class="audience-landing__inner audience-landing-final__inner">
    <h2><?php esc_html_e('Give a gift they’ll still be talking about next year.', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Start with the free gift guide - or give all three adventures at once.', 'brave-hearts'); ?></p>
    <div class="audience-landing-final__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta data-bhp-event="gift_final_cta_click" data-bhp-source="gift_landing"><?php esc_html_e('Get the Meaningful Gift Guide', 'brave-hearts'); ?></a>
      <?php
      /*
       * 2026-08-05 — was `href="#collection"`. The collection section now sits
       * at the TOP of the page, so the anchor would have scrolled the customer
       * backwards to reach a button. It is now the button. Label unchanged.
       * `sync => true` keeps it on the format the price card is showing.
       */
      echo bhp_collection_add_to_cart_cta([
          'label'      => __('Give the Complete Collection', 'brave-hearts'),
          'class'      => 'btn btn-outline-light',
          'form_class' => 'audience-landing-collection-form',
          'sync'       => true,
          'event'      => 'gift_final_collection_cta_click',
          'source'     => 'gift_landing',
      ]);
      ?>
    </div>
  </div>
</section>

<!-- ===================== STICKY MINI-CTA ===================== -->
<div class="audience-landing-stickybar" data-audience-stickybar>
  <div class="audience-landing-stickybar__row">
    <span class="audience-landing-stickybar__text"><?php esc_html_e('Free Meaningful Gift Guide - no purchase needed.', 'brave-hearts'); ?></span>
    <div class="audience-landing-stickybar__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta><?php esc_html_e('Get it free', 'brave-hearts'); ?></a>
      <?php
      /*
       * 2026-08-05 — the footer-bar "Collection" control Andrew named. Was an
       * in-page anchor; now adds the set and lands on /checkout/. Label kept
       * verbatim — see the identical note on the educators page.
       */
      echo bhp_collection_add_to_cart_cta([
          'label'      => __('Collection', 'brave-hearts'),
          'class'      => 'btn btn-outline-light',
          'form_class' => 'audience-landing-collection-form',
          'sync'       => true,
          'event'      => 'gift_stickybar_collection_cta_click',
          'source'     => 'gift_landing',
      ]);
      ?>
    </div>
  </div>
</div>

</div>
<?php get_footer(); ?>
