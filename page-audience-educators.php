<?php
/**
 * Template Name: Audience Landing - Teachers, Librarians & Homeschool
 * Description: Educator-facing landing page for the Adventure Learning
 * Toolkit lead magnet. Audience type 'educators' (distinct from the
 * existing 'teachers' Mariana classroom-guide funnel -- kept isolated so
 * neither funnel's tags/tracking mix). Live on staging as of 2026-07-16
 * with the real 8-page toolkit PDF set under Settings -> Lead Magnets
 * (see bhp_get_teacher_toolkit_download()) -- the signup form activates
 * automatically once that setting has a value; no separate flag to flip.
 *
 * Shares the audience-landing design system (assets/css/audience-landing.css
 * + assets/js/audience-landing.js) with the other 3 core audience pages --
 * same proven component patterns as the Parent template, this page's own
 * copy/content. All lead capture runs through the site's real signup
 * pipeline (template-parts/acquisition/lead-magnet-cta.php ->
 * signup-form.php -> bhp_mailchimp_signup), never a fork of it.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_teacher_toolkit_download();
$adventures = bhp_get_series_adventures();
$complete_collection_url = home_url('/complete-collection/');
$contact_url = home_url('/contact/');

if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    $bhp_educator_landing_payload = wp_json_encode([
        'event'      => 'educator_landing_view',
        'funnel'     => 'educators',
        'page_type'  => 'landing_page',
        'lead_offer' => 'teacher_adventure_toolkit',
        'audience'   => 'educators',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_educator_landing_payload; ?>);
    </script>
    <?php
endif;

$mariana = $adventures['mariana_trench'] ?? [];
$everest = $adventures['mount_everest'] ?? [];
$amazon  = $adventures['amazon_rainforest'] ?? [];

$pain_points = [
    __('Independent reading time turns into a fight, not a habit.', 'brave-hearts'),
    __('Chapter books feel too long for reluctant readers to commit to.', 'brave-hearts'),
    __('It’s hard to find a book that connects to geography, science, or history.', 'brave-hearts'),
    __('Read-aloud time competes with everything else on the schedule.', 'brave-hearts'),
];

$solution_points = [
    __('<strong>Story-led learning</strong> - real places and real science woven into an adventure, not a worksheet.', 'brave-hearts'),
    __('<strong>Cross-curricular hooks</strong> - geography, natural science, history, and vocabulary in every book.', 'brave-hearts'),
    __('<strong>Discussion-ready chapters</strong> - 12 short chapters, natural stopping points for a lesson block.', 'brave-hearts'),
    __('<strong>Read-aloud or independent</strong> - flexible enough for a whole-class read-aloud or an independent reading corner.', 'brave-hearts'),
    __('<strong>Character education</strong> - courage, curiosity, and teamwork show up inside the adventure, not bolted on.', 'brave-hearts'),
];

$cross_curricular = [
    ['title' => __('Geography', 'brave-hearts'), 'text' => __('Real places - an ocean trench, a mountain, a rainforest - anchor every story in the actual world.', 'brave-hearts')],
    ['title' => __('Science', 'brave-hearts'), 'text' => __('Real wildlife, ecosystems, and natural phenomena woven into the adventure, not a sidebar.', 'brave-hearts')],
    ['title' => __('History', 'brave-hearts'), 'text' => __('Real exploration history connects the story to the people who came before.', 'brave-hearts')],
    ['title' => __('Vocabulary', 'brave-hearts'), 'text' => __('New words introduced in context, with a glossary to reinforce them.', 'brave-hearts')],
    ['title' => __('Discussion', 'brave-hearts'), 'text' => __('Twelve short chapters give natural stopping points for class discussion.', 'brave-hearts')],
    ['title' => __('Character education', 'brave-hearts'), 'text' => __('Courage, teamwork, and curiosity show up inside the adventure itself.', 'brave-hearts')],
];

$faqs = [
    [__('What age or grade level are the books for?', 'brave-hearts'), __('They’re written for readers roughly ages 6–9 (about 1st–3rd grade) - approachable for independent reading and rich enough for a whole-class read-aloud.', 'brave-hearts')],
    [__('What’s in the Adventure Learning Toolkit?', 'brave-hearts'), __('Classroom-ready resources connecting the series to geography, science, history, vocabulary, and discussion.', 'brave-hearts')],
    [__('Are the facts in the books real?', 'brave-hearts'), __('Yes. Each adventure is built around a real place, real wildlife, and real science or history.', 'brave-hearts')],
    [__('Can I use these for a whole-class read-aloud?', 'brave-hearts'), __('Yes - the chapter length and discussion-ready structure work for both a read-aloud and independent reading corners.', 'brave-hearts')],
    [__('What’s included in the Complete Collection?', 'brave-hearts'), __('All three adventures - The Mariana Trench, Mount Everest, and The Amazon - in one purchase and one shipment.', 'brave-hearts')],
    [__('Can we order class sets or larger quantities for a school or library?', 'brave-hearts'), __('Yes - schools, teachers, librarians, and homeschool organizations are welcome to reach out about classroom, library, and larger-volume purchases. Bulk-order requests are handled individually through direct inquiry - contact us and we’ll follow up.', 'brave-hearts')],
    [__('How often will I receive emails?', 'brave-hearts'), __('Occasional classroom resource updates for educators - never spam, and you can unsubscribe anytime.', 'brave-hearts')],
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
<div class="audience-landing" data-audience-landing>

<!-- ===================== HERO ===================== -->
<section class="audience-landing-hero audience-landing-hero--funnel">
  <div class="audience-landing-hero__bg" aria-hidden="true"></div>
  <div class="audience-landing__inner audience-landing-hero__grid">
    <div>
      <span class="audience-landing-eyebrow audience-landing-hero__badge"><?php esc_html_e('For teachers, librarians & homeschool educators', 'brave-hearts'); ?></span>
      <h1><?php esc_html_e('Turn reading time into real-world discovery.', 'brave-hearts'); ?></h1>
      <p class="audience-landing__lead"><?php esc_html_e('Story-led adventure books that connect literacy with geography, science, history, and discussion - built for the classroom, the library, and the homeschool table.', 'brave-hearts'); ?></p>
      <?php
      /*
       * ⭐ 1.19.213 (CYCLE150-LD) — ONE CTA, NOT TWO. The secondary
       *    "Explore the Complete Collection" outline button is REMOVED, not
       *    hidden: Andrew Signore, relayed by `chief-of-staff` (⛔ not
       *    witnessed here), "it will distract from the main CTA."
       *    Its `educator_hero_secondary_cta_click` event goes with it; the
       *    primary CTA's event, source and href are byte-unchanged, and the
       *    collection is still reachable from the fast-purchase band, the
       *    raised Best Value card and the sticky bar.
       */
      ?>
      <div class="audience-landing-hero__ctas">
        <a class="btn btn-primary" href="#free" data-audience-free-cta data-bhp-signup-modal-open="educator-toolkit-modal" data-bhp-signup-modal-source="hero" data-bhp-event="educator_hero_primary_cta_click" data-bhp-source="educator_landing"><?php esc_html_e('Get the Free Adventure Learning Toolkit', 'brave-hearts'); ?></a>
      </div>
      <?php
      /*
       * ⭐ 1.19.213 — THE COLLECTION CAROUSEL, DIRECTLY UNDER THE PRIMARY CTA.
       *    Founder slot 4. Full spec and the two new placement keys are stated
       *    ONCE in `inc/collection-gallery.php`, not repeated per template.
       * ⛔ A MOVE, NOT AN ADDITION — the identical call inside #collection is
       *    gone. Still exactly one instance, one DOM id, one lightbox.
       */
      if (function_exists('bhp_cx_render_collection_gallery')) {
          echo '<div class="audience-landing-hero__gallery">';
          bhp_cx_render_collection_gallery();
          echo '</div>';
      }
      ?>
      <div class="audience-landing-hero__proof">
        <?php /* 2026-08-10 (Andrew): Kirkus moved into the scanbar checkmarks;
                 "Placed in classrooms across Boise" removed. */ ?>
        <span><?php esc_html_e('Three complete adventures', 'brave-hearts'); ?></span>
      </div>
      <?php
      /*
       * ⭐ 1.19.213 — THE STATIC THREE-COVER HERO ART IS REMOVED. Slide 1 of
       *    the carousel above IS the three-book image, so the lockup was the
       *    same picture twice in one eyeful and was the single largest thing
       *    pushing the CTA under the fold. ⚠ The covers are NOT gone from the
       *    page — still the `audience-landing-books` grid in #collection, and
       *    still every slide of the carousel.
       */
      ?>
    </div>
  </div>
</section>

<!-- ===================== QUICK-SCAN BAR ===================== -->
<section class="audience-landing-scanbar">
  <div class="audience-landing__inner audience-landing__inner--narrow audience-landing-scanbar__row">
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Ages 6–9 · 1st–3rd grade', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('12 short chapters', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Cross-curricular hooks', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Read aloud or independent', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></span>
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
/* 2026-08-10 (Andrew, current-turn): the mobile fast-purchase band is RETIRED
   on this page - its 'Best value' box and 'Add the ... Collection' CTA duplicated
   the raised Best Value pricing card directly below (his words: 'very redundant').
   The band template itself is preserved unused. */
?>

<!-- ===================== COMPLETE COLLECTION ===================== -->
<section id="collection" class="audience-landing__section audience-landing__section--major">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('The complete collection', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Three complete adventures.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php echo wp_kses_post(__('Beneath the ocean, to the highest mountain, and deep into the rainforest - the full <em>Adventures of Charlotte &amp; Henry</em> collection in one shipment.', 'brave-hearts')); ?></p>
    </div>

    <?php
    /*
     * ⭐ 1.19.213 — THE CAROUSEL CALL THAT STOOD HERE HAS MOVED TO THE HERO,
     *    directly under the primary CTA (founder slot 4). A MOVE: still one
     *    call, one instance, one DOM id per request.
     *
     * ⚠ The "INTERIORS ONLY on this page" note that lived here was already
     *   HISTORY before this release — the 2026-08-09 parity ruling replaced
     *   the interiors-only list with the same ten slides every other surface
     *   shows, and `inc/collection-gallery.php` records that at length,
     *   including the artefact flag it re-opened. It is not restated here.
     *
     * ⭐ AND THE BOOKS GRID MOVES BELOW THE PRICE CARD — founder slot 6, "the
     *    Best Value buy section, raised… So they can buy easier on the page."
     *    Moved STRUCTURALLY, not with CSS `order`, so keyboard and reading
     *    order still match the visible order. Nothing is deleted.
     *
     * Still untouched, and deliberately named: the toolkit-preview module, the
     * teacher_toolkit lead magnet, and the teacher funnel's popup / storage /
     * analytics prefixes.
     */
    ?>
    <?php if ($bundle_available): ?>
      <div class="audience-landing-pricecard" data-audience-pricing-card>
        <span class="audience-landing-pricecard__badge">&#9733; <?php esc_html_e('Best value - all three books', 'brave-hearts'); ?></span>

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
              : __('Hardcover · sewn binding · a keepsake edition that lasts', 'brave-hearts');
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
               * 2026-08-05 — Andrew: "2 click journey to purchase". This was an
               * <a> to /complete-collection/, which made buying the set a
               * three-page trip. It now adds the three real books of THIS panel's
               * format and lands on /checkout/. Label, classes and both analytics
               * attributes are carried over unchanged; only the element and the
               * destination differ. See inc/collection-cta.php.
               */
              echo bhp_collection_add_to_cart_cta([
                  'format'     => $format,
                  'label'      => sprintf(__('Add the %s Collection', 'brave-hearts'), $f['name']),
                  'class'      => 'btn btn-primary',
                  'form_class' => 'audience-landing-pricecard__cta-form',
                  'event'      => 'educator_collection_cta_click',
                  'source'     => 'educator_landing',
              ]);
              ?>
              <p class="audience-landing-pricecard__link-row"><?php esc_html_e('Secure checkout · Tracking provided ·', 'brave-hearts'); ?> <a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('View individual books', 'brave-hearts'); ?></a></p>
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
      <h2><?php esc_html_e('Independent reading time is a struggle for some readers.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('It’s rarely about ability - it’s about the book in front of them. Here’s what shows up in the classroom:', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-grid">
      <?php foreach ($pain_points as $i => $point): ?>
        <div class="audience-landing-card"><div class="audience-landing-card__num"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></div><p><?php echo esc_html($point); ?></p></div>
      <?php endforeach; ?>
    </div>
    <p class="audience-landing__pull-quote"><?php esc_html_e('Story-led learning gives reluctant readers a reason to keep going - and gives you a book that already does double duty as a lesson.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== SOLUTION ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing-split">
    <div>
      <span class="audience-landing-eyebrow"><?php esc_html_e('Why story-led learning works', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('A chapter book that teaches without feeling like homework.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Brave Hearts books connect literacy with geography, science, history, and vocabulary - through approachable chapters, illustrations, and real-world discovery.', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-checklist">
      <?php foreach ($solution_points as $point): ?>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== CROSS-CURRICULAR ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('One book, many subjects', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Books as cross-curricular resources.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-grid audience-landing-grid--features">
      <?php foreach ($cross_curricular as $item): ?>
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
        <span class="audience-landing-eyebrow"><?php esc_html_e('Free for educators', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('Bring the adventure into your classroom.', 'brave-hearts'); ?></h2>
        <p class="audience-landing__lead"><?php echo wp_kses_post(__('Get the free <strong>Adventure Learning Toolkit</strong> - classroom-ready resources connecting the series to geography, science, history, vocabulary, and discussion.', 'brave-hearts')); ?></p>
        <div class="audience-landing-checklist audience-landing-checklist--compact audience-landing-lead__checklist">
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Discussion questions tied to real chapters', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Vocabulary and geography connections', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Read-aloud and independent reading tips', 'brave-hearts'); ?></span></div>
        </div>

        <?php if ($download['ready']): ?>
          <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
              'id'                   => 'educator-toolkit-signup',
              'lead_magnet'          => 'teacher_adventure_toolkit',
              'audience_type'        => 'educators',
              'title'                => __('Send Me the Free Adventure Learning Toolkit', 'brave-hearts'),
              'text'                 => __('Classroom-ready resources connecting the series to geography, science, history, vocabulary, and discussion.', 'brave-hearts'),
              'submit_label'         => __('Get the Free Adventure Learning Toolkit', 'brave-hearts'),
              'source_page'          => $source_page,
              'require_name'         => true,
          ]); ?>
          <p class="audience-landing-lead__fine-print"><?php esc_html_e('Free printable PDF · No purchase required · Occasional classroom resource updates. Unsubscribe anytime.', 'brave-hearts'); ?></p>
        <?php else: ?>
          <div class="audience-landing-coming-soon">
            <p class="audience-landing-eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
            <h3><?php esc_html_e('Send Me the Free Adventure Learning Toolkit', 'brave-hearts'); ?></h3>
            <p class="audience-landing__lead" style="font-size:15px;"><?php esc_html_e('The Adventure Learning Toolkit is still being finished. Check back soon to get your free copy by email.', 'brave-hearts'); ?></p>
            <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="audience-landing-lead__art">
        <div>
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/educator-toolkit-cover.webp'); ?>" alt="<?php esc_attr_e('Adventure Learning Toolkit cover', 'brave-hearts'); ?>" loading="lazy" decoding="async">
          <p class="tag"><?php esc_html_e('Free · Adventure Learning Toolkit', 'brave-hearts'); ?></p>
          <p class="sub"><?php esc_html_e('8 pages · printable PDF', 'brave-hearts'); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== TOOLKIT PREVIEW (educator-specific module) ===================== -->
<!-- Updated for the finished 8-page toolkit (Andrew-approved v1.0, 2026-07-16):
     real cover image in place of the "design in progress" placeholder, and
     copy updated to reflect a delivered asset rather than a planned one. -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing-split audience-landing-split--media-lead">
    <div class="audience-landing-media audience-landing-media--tall">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/educator-toolkit-cover.webp'); ?>" alt="<?php esc_attr_e('Adventure Learning Toolkit cover', 'brave-hearts'); ?>" loading="lazy" decoding="async">
    </div>
    <div>
      <span class="audience-landing-eyebrow"><?php esc_html_e('What’s inside', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Eight pages, built around The Mariana Trench.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Here’s exactly what’s inside the free toolkit:', 'brave-hearts'); ?></p>
      <div class="audience-landing-checklist audience-landing-checklist--compact" style="margin-top:20px;">
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Cover page', 'brave-hearts'); ?></span></div>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Discussion questions', 'brave-hearts'); ?></span></div>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Vocabulary & geography', 'brave-hearts'); ?></span></div>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Science spotlight', 'brave-hearts'); ?></span></div>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Hands-on classroom activity', 'brave-hearts'); ?></span></div>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Reproducible student field journal', 'brave-hearts'); ?></span></div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== TRUST ===================== -->
<section class="audience-landing__section audience-landing__section--dark">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <p class="audience-landing-trust-eyebrow"><?php esc_html_e('Trusted by teachers, librarians & reviewers', 'brave-hearts'); ?></p>
    <div class="audience-landing-stat-grid">
      <?php
      /*
       * N4 (2026-08-03) — numberless stat tile. ZERO NEW WORDS: "Boise" moves
       * into `__num`, the numeral 40 is dropped, the label keeps its remaining
       * words exactly. See page-audience-organizations.php's N4 note.
       *
       * ⚠ FLAGGED, NOT SILENTLY FIXED: this label reads "classrooms placed the
       *   series", which is the awkward phrasing that page-audience-
       *   organizations.php explicitly corrected to "received the series" on
       *   2026-07-17 ("'placed the series' read as if the classrooms did the
       *   placing"). That correction was never carried across to this page.
       *   It is a PRE-EXISTING copy defect, it is outside N4's scope, and
       *   changing approved copy is not this agent's call — so it is reported
       *   to Andrew rather than absorbed into this build.
       */
      ?>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Boise', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('classrooms placed the series', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">3</div><p class="audience-landing-stat__label"><?php esc_html_e('published adventures', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Kirkus', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('featured title', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">4</div><p class="audience-landing-stat__label"><?php esc_html_e('subjects connected - geography, science, history & vocabulary', 'brave-hearts'); ?></p></div>
    </div>
    <div class="audience-landing-review">
      <div class="audience-landing-review__stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
      <blockquote><p>&ldquo;My students were drawn to the vivid setting and sense of exploration. It&rsquo;s engaging, educational, and a great addition to any classroom or home library.&rdquo;</p></blockquote>
      <cite><?php esc_html_e('Payton, elementary teacher', 'brave-hearts'); ?> - <?php if ($testimonial_url): ?><a href="<?php echo esc_url($testimonial_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Verified Amazon review', 'brave-hearts'); ?></a><?php else: ?><?php esc_html_e('Verified Amazon review', 'brave-hearts'); ?><?php endif; ?></cite>
    </div>
  </div>
</section>

<!-- ===================== READ-ALOUD INVITATION ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing__inner--content" style="text-align:center;">
    <span class="audience-landing-eyebrow"><?php esc_html_e('Bring the author to your classroom', 'brave-hearts'); ?></span>
    <h2 style="margin-top:18px;margin-bottom:16px;"><?php esc_html_e('Invite Andrew for a read-aloud.', 'brave-hearts'); ?></h2>
    <p class="audience-landing__lead" style="margin-inline:auto;max-width:52ch;"><?php esc_html_e('Andrew, the series author, is available for classroom and library read-alouds. Reach out to check availability for your school or library.', 'brave-hearts'); ?></p>
    <p style="margin-top:28px;"><a class="btn btn-outline" href="<?php echo esc_url($contact_url); ?>" data-bhp-event="educator_readaloud_invite_click" data-bhp-source="educator_landing"><?php esc_html_e('Invite Andrew for a Read-Aloud', 'brave-hearts'); ?></a></p>
  </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--content">
    <h2 style="text-align:center;margin-bottom:40px;"><?php esc_html_e('Questions educators ask', 'brave-hearts'); ?></h2>
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

<!-- ===================== FINAL CTA ===================== -->
<section class="audience-landing__section audience-landing__section--major audience-landing-final">
  <div class="audience-landing__inner audience-landing-final__inner">
    <h2><?php esc_html_e('Give your students a book that teaches without feeling like homework.', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Start with the free Adventure Learning Toolkit tonight - or bring home all three adventures at once.', 'brave-hearts'); ?></p>
    <div class="audience-landing-final__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta data-bhp-signup-modal-open="educator-toolkit-modal" data-bhp-signup-modal-source="final_cta" data-bhp-event="educator_final_cta_click" data-bhp-source="educator_landing"><?php esc_html_e('Get the Free Adventure Learning Toolkit', 'brave-hearts'); ?></a>
      <?php
      /*
       * 2026-08-05 — this was `href="#collection"`. The collection section now
       * sits at the TOP of the page, so the anchor would have scrolled the
       * customer BACKWARDS past everything they just read to reach a button.
       * It is now the button. `sync => true` marks its hidden action field so
       * the format toggle up in the price card keeps it in step — the format
       * the customer selected is the format that reaches the cart.
       */
      echo bhp_collection_add_to_cart_cta([
          'label'      => __('Get the Complete Collection', 'brave-hearts'),
          'class'      => 'btn btn-outline-light',
          'form_class' => 'audience-landing-collection-form',
          'sync'       => true,
          'event'      => 'educator_final_collection_cta_click',
          'source'     => 'educator_landing',
      ]);
      ?>
    </div>
  </div>
</section>

<!-- ===================== STICKY MINI-CTA ===================== -->
<div class="audience-landing-stickybar" data-audience-stickybar>
  <div class="audience-landing-stickybar__row">
    <span class="audience-landing-stickybar__text"><?php esc_html_e('Free Adventure Learning Toolkit - no purchase needed.', 'brave-hearts'); ?></span>
    <div class="audience-landing-stickybar__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta data-bhp-signup-modal-open="educator-toolkit-modal" data-bhp-signup-modal-source="sticky_bar"><?php esc_html_e('Get it free', 'brave-hearts'); ?></a>
      <?php
      /*
       * 2026-08-05 — Andrew named THIS control: "the 'Collection' CTA in the
       * footer bar pop up ... should automatically add the books to your cart
       * and take you to the checkout page". It was an in-page anchor. It is now
       * a real add-the-set-and-checkout button.
       *
       * The label stays the single word "Collection", byte for byte. Andrew
       * identified the button by that word; renaming it in the same change that
       * alters its behaviour would make his own instruction unverifiable.
       * A better label is a copy decision, not an engineering one.
       */
      echo bhp_collection_add_to_cart_cta([
          'label'      => __('Collection', 'brave-hearts'),
          'class'      => 'btn btn-outline-light',
          'form_class' => 'audience-landing-collection-form',
          'sync'       => true,
          'event'      => 'educator_stickybar_collection_cta_click',
          'source'     => 'educator_landing',
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
 * Every "Get the Free Adventure Learning Toolkit" CTA on this page now OPENS this dialog with the caret
 * already in the email field, instead of scrolling the visitor down to
 * #free. Andrew Signore, current turn, relayed by `chief-of-staff`: "no
 * scrolling, immediate capture".
 *
 * THE INLINE #free PANEL ABOVE IS NOT REMOVED AND MUST NOT BE. It is the
 * no-JS fallback, it is what the CTAs' `href="#free"` still points at, it
 * keeps the `#free` deep link working, and it keeps the capture copy in the
 * indexable page body.
 *
 * GATED ON THE SAME `$download['ready']` FLAG AS THE PANEL. If the PDF is
 * ever unset under Settings -> Lead Magnets, this modal does not render at
 * all, the CTAs find no modal to open, and they fall back to scrolling to
 * the "coming soon" block -- which is the correct behaviour, and is why the
 * JS resolves its target BEFORE it calls preventDefault().
 *
 * NOT A LEAD-MAGNET POPUP. No timer, no scroll trigger, no exit trigger; it
 * opens only on a deliberate CTA click, so it does not reverse the
 * 2026-07-19 one-popup ruling. It renders the SAME signup-form.php handler
 * with the SAME lead-magnet key, audience type and Mailchimp tags as the
 * inline panel -- never a fork of that pipeline.
 *
 * Copy is reused VERBATIM from the inline panel above. The same offer must
 * not be described in two different ways, and no new claim, number,
 * duration or count is introduced here.
 */
if ($download['ready']) {
    get_template_part('template-parts/acquisition/signup-modal', null, [
        'id'                   => 'educator-toolkit-modal',
        'lead_magnet'          => 'teacher_adventure_toolkit',
        'audience_type'        => 'educators',
        'source_page'          => $source_page,
        'eyebrow'              => __('Free for educators', 'brave-hearts'),
        'title'                => __('Send Me the Free Adventure Learning Toolkit', 'brave-hearts'),
        'text'                 => __('Classroom-ready resources connecting the series to geography, science, history, vocabulary, and discussion.', 'brave-hearts'),
        'submit_label'         => __('Get the Free Adventure Learning Toolkit', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'trust_text'           => __('Free printable PDF. No purchase required.', 'brave-hearts'),
    ]);
}
?>

</div>
<?php get_footer(); ?>
