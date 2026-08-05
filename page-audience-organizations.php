<?php
/**
 * Template Name: Audience Landing - Organizations
 * Description: Organization-facing landing page (children's hospitals,
 * literacy nonprofits, Boys & Girls Clubs, YMCA, Scouts, outdoor education,
 * nature centers, youth groups, libraries, foundations, community programs)
 * for the Community Reading Kit lead magnet. Stays in a "coming soon" state
 * -- form wired but inactive -- until the kit PDF is set under
 * Settings -> Lead Magnets (see bhp_get_community_kit_download()).
 *
 * Deliberately does not invent bulk pricing, program results, or
 * partnership history -- bulk-quantity and partnership conversations route
 * to a real contact inquiry, and the Complete Collection pricing shown is
 * the same live per-set WooCommerce pricing used everywhere else on the
 * site (bhp_bundle_expected_price()/bhp_bundle_rules()), not a fabricated
 * bulk-discount table.
 *
 * Shares the audience-landing design system (assets/css/audience-landing.css
 * + assets/js/audience-landing.js) with the other 3 core audience pages.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_community_kit_download();
$adventures = bhp_get_series_adventures();
$complete_collection_url = home_url('/complete-collection/');
$contact_url = home_url('/contact/');

if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    $bhp_org_landing_payload = wp_json_encode([
        'event'      => 'organization_landing_view',
        'funnel'     => 'organizations',
        'page_type'  => 'landing_page',
        'lead_offer' => 'community_reading_kit',
        'audience'   => 'organizations',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_org_landing_payload; ?>);
    </script>
    <?php
endif;

$mariana = $adventures['mariana_trench'] ?? [];
$everest = $adventures['mount_everest'] ?? [];
$amazon  = $adventures['amazon_rainforest'] ?? [];

$program_challenges = [
    __('Finding books that hold a group’s attention during a read-aloud.', 'brave-hearts'),
    __('Giving kids a reason to keep reading between program sessions.', 'brave-hearts'),
    __('Connecting literacy time to something bigger - science, geography, real discovery.', 'brave-hearts'),
    __('Sourcing books meaningful enough to justify a bulk gifting moment.', 'brave-hearts'),
];

$support_points = [
    __('<strong>Story-led engagement</strong> - real places and real science woven into a 12-chapter adventure.', 'brave-hearts'),
    __('<strong>Read-aloud friendly</strong> - short chapters give a group a natural stopping point each session.', 'brave-hearts'),
    __('<strong>A gift that means something</strong> - a keepsake hardcover option for bulk-gifting moments.', 'brave-hearts'),
    __('<strong>Cross-curricular hooks</strong> - geography, science, history, and vocabulary in every book.', 'brave-hearts'),
    __('<strong>A growing series</strong> - three adventures now, more to grow into over time.', 'brave-hearts'),
];

$use_cases = [
    ['title' => __('Literacy programs', 'brave-hearts'), 'text' => __('An engaging chapter book for reluctant or emerging readers in a structured program.', 'brave-hearts')],
    ['title' => __('Read-alouds & events', 'brave-hearts'), 'text' => __('Short chapters built for group read-aloud sessions and one-time events.', 'brave-hearts')],
    ['title' => __('Bulk gifting', 'brave-hearts'), 'text' => __('A meaningful book to send kids home with after a program or event.', 'brave-hearts')],
    ['title' => __('Community partnerships', 'brave-hearts'), 'text' => __('A series that fits naturally into ongoing literacy and youth-engagement work.', 'brave-hearts')],
];

$faqs = [
    [__('What age range are the books for?', 'brave-hearts'), __('Readers roughly ages 6–9 (1st–3rd grade) - approachable for independent reading and rich enough for a group read-aloud.', 'brave-hearts')],
    [__('What’s in the Community Reading Kit?', 'brave-hearts'), __('Free resources for literacy programs, events, and bulk gifting.', 'brave-hearts')],
    [__('Can we order in bulk for our program?', 'brave-hearts'), __('Yes - reach out through the contact link below and we’ll follow up to discuss quantities and details for your program.', 'brave-hearts')],
    [__('Can we discuss sponsoring books for kids who couldn’t otherwise afford them?', 'brave-hearts'), __('Yes - reach out to start a conversation about a sponsored-book arrangement for your program. This is discussed case by case, not a standing formal program.', 'brave-hearts')],
    [__('What’s included in the Complete Collection?', 'brave-hearts'), __('All three adventures - The Mariana Trench, Mount Everest, and The Amazon - in one purchase and one shipment.', 'brave-hearts')],
    [__('Do you have existing partnerships with organizations?', 'brave-hearts'), __('We’re actively building relationships with literacy programs and community organizations - reach out to start a conversation.', 'brave-hearts')],
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
?>
<div class="audience-landing" data-audience-landing>

<!-- ===================== HERO ===================== -->
<section class="audience-landing-hero">
  <div class="audience-landing-hero__bg" aria-hidden="true"></div>
  <div class="audience-landing__inner audience-landing-hero__grid">
    <div>
      <span class="audience-landing-eyebrow audience-landing-hero__badge"><?php esc_html_e('For literacy programs, nonprofits & youth organizations', 'brave-hearts'); ?></span>
      <h1><?php esc_html_e('Story-led adventure, built for community programs.', 'brave-hearts'); ?></h1>
      <p class="audience-landing__lead"><?php esc_html_e('Approachable chapter books that support literacy programs, read-alouds, events, and bulk gifting - for hospitals, nonprofits, youth clubs, and libraries.', 'brave-hearts'); ?></p>
      <div class="audience-landing-hero__ctas">
        <a class="btn btn-primary" href="#free" data-audience-free-cta data-bhp-event="org_hero_primary_cta_click" data-bhp-source="organization_landing"><?php esc_html_e('Get the Community Reading Kit', 'brave-hearts'); ?></a>
        <a class="btn btn-outline" href="#contact" data-bhp-event="org_hero_secondary_cta_click" data-bhp-source="organization_landing"><?php esc_html_e('Start a Partnership Conversation', 'brave-hearts'); ?></a>
      </div>
      <div class="audience-landing-hero__proof">
        <span>&#9733; <?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <?php /* N4 (2026-08-03) — numberless standing form. See front-page.php's
                 N4 note for Andrew's wording and why the count is dropped. */ ?>
        <span><?php esc_html_e('Placed in classrooms across Boise', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('Paperback & hardcover options', 'brave-hearts'); ?></span>
      </div>
    </div>
    <div class="audience-landing-hero__art">
      <?php if (has_custom_logo()): the_custom_logo(); endif; ?>
      <div class="audience-landing-hero__covers">
        <?php if ($mariana): ?><div class="audience-landing-hero__cover--side audience-landing-hero__cover--left"><?php echo bhp_parent_landing_cover($mariana); ?></div><?php endif; ?>
        <?php if ($everest): ?><div class="audience-landing-hero__cover--center"><?php echo bhp_parent_landing_cover($everest); ?></div><?php endif; ?>
        <?php if ($amazon): ?><div class="audience-landing-hero__cover--side audience-landing-hero__cover--right"><?php echo bhp_parent_landing_cover($amazon); ?></div><?php endif; ?>
      </div>
      <p class="audience-landing-hero__caption"><?php esc_html_e('Ocean &middot; Mountain &middot; Rainforest', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<!-- ===================== QUICK-SCAN BAR ===================== -->
<section class="audience-landing-scanbar">
  <div class="audience-landing__inner audience-landing__inner--narrow audience-landing-scanbar__row">
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Ages 6–9 · 1st–3rd grade', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('12 short chapters', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Group read-aloud friendly', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Bulk & partnership inquiries welcome', 'brave-hearts'); ?></span>
  </div>
</section>

<!-- ===================== BULK PURCHASES ===================== -->
<section id="collection" class="audience-landing__section audience-landing__section--major">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Bulk purchases', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Bringing the collection to your program.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('The Complete Collection below shows our standard per-set pricing. For bulk-quantity orders for your program or event, start a conversation and we’ll follow up with details.', 'brave-hearts'); ?></p>
    </div>

    <?php
    /*
     * A programme lead is spending someone else's money and has to justify it.
     * What was missing here was not more copy but proof the product is
     * finished and real: the composite shows what a set looks like, the
     * flip-through shows it is a printed book rather than a PDF, and the
     * diagram shows there is genuine instructional content inside.
     *
     * Fails closed. The #contact partnership CTA, the sponsored-book FAQ row
     * and the bulk-ordering link are untouched. See `inc/collection-gallery.php`.
     */
    if (function_exists('bhp_cx_render_collection_gallery')) {
        bhp_cx_render_collection_gallery();
    }
    ?>

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

    <?php if ($bundle_available): ?>
      <div class="audience-landing-pricecard" data-audience-pricing-card>
        <span class="audience-landing-pricecard__badge">&#9733; <?php esc_html_e('Standard collection pricing - all three books', 'brave-hearts'); ?></span>

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
              $bhp_free_addon_badge = function_exists('bhp_book_free_addon_badge') ? bhp_book_free_addon_badge() : '';
              ?>
              <?php if ('' !== $bhp_free_addon_badge): ?>
                <span class="audience-landing-pricecard__badge-pill audience-landing-pricecard__badge-pill--muted">&#10003; <?php echo esc_html($bhp_free_addon_badge); ?></span>
              <?php endif; ?>
              </div>
              <div class="audience-landing-pricecard__price-row">
                <span class="label"><?php esc_html_e('Complete Collection price', 'brave-hearts'); ?></span>
                <span><span class="audience-landing-pricecard__price-strike">$<?php echo esc_html(number_format($f['combined'], 2)); ?></span><span class="audience-landing-pricecard__price-final">$<?php echo esc_html(number_format($f['collection'], 2)); ?></span></span>
              </div>
              <p class="audience-landing-pricecard__ship-note"><?php echo esc_html(bhp_book_landing_ship_note($f['shipping'])); ?></p>
              <?php
              /*
               * 2026-08-05 — Andrew: "2 click journey to purchase". Was an <a>
               * to /complete-collection/; now adds this panel's three real books
               * and lands on /checkout/. The BULK route below is untouched: an
               * organization ordering in quantity still goes to /contact/.
               * See inc/collection-cta.php.
               */
              echo bhp_collection_add_to_cart_cta([
                  'format'     => $format,
                  'label'      => sprintf(__('Add the %s Collection', 'brave-hearts'), $f['name']),
                  'class'      => 'btn btn-primary',
                  'form_class' => 'audience-landing-pricecard__cta-form',
                  'event'      => 'org_collection_cta_click',
                  'source'     => 'organization_landing',
              ]);
              ?>
              <p class="audience-landing-pricecard__link-row"><?php esc_html_e('Ordering in bulk for a program?', 'brave-hearts'); ?> <a href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Start a conversation', 'brave-hearts'); ?></a></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="align-center" style="text-align:center;margin-top:40px;">
        <a class="btn btn-primary" href="<?php echo esc_url($complete_collection_url); ?>"><?php esc_html_e('Explore the Complete Collection', 'brave-hearts'); ?></a>
      </p>
    <?php endif; ?>
  </div>
</section>

<!-- ===================== PROGRAM CHALLENGE ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Sound familiar?', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Keeping a group engaged with a book is hard.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Programs need books that hold attention, connect to real learning, and feel worth giving. Here’s what shows up most often:', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-grid">
      <?php foreach ($program_challenges as $i => $point): ?>
        <div class="audience-landing-card"><div class="audience-landing-card__num"><?php echo esc_html(sprintf('%02d', $i + 1)); ?></div><p><?php echo esc_html($point); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== HOW BRAVE HEARTS SUPPORTS ENGAGEMENT ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing-split">
    <div>
      <span class="audience-landing-eyebrow"><?php esc_html_e('How Brave Hearts supports engagement', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Story-led adventure that fits your program.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Brave Hearts books give kids curiosity, reading confidence, and a story worth finishing - designed to fit into literacy programs, events, and community engagement work.', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-checklist">
      <?php foreach ($support_points as $point): ?>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== PROGRAM USE CASES ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Where it fits', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Program use cases.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-grid">
      <?php foreach ($use_cases as $item): ?>
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
        <span class="audience-landing-eyebrow"><?php esc_html_e('Free for organizations', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('Bring the adventure to your program.', 'brave-hearts'); ?></h2>
        <p class="audience-landing__lead"><?php echo wp_kses_post(__('Get the free <strong>Community Reading Kit</strong> - resources for literacy programs, events, and bulk gifting.', 'brave-hearts')); ?></p>
        <div class="audience-landing-checklist audience-landing-checklist--compact audience-landing-lead__checklist">
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Read-aloud and group session tips', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Discussion starters for group settings', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('An introduction to bulk and partnership options', 'brave-hearts'); ?></span></div>
        </div>

        <?php if ($download['ready']): ?>
          <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
              'id'                   => 'org-reading-kit-signup',
              'lead_magnet'          => 'community_reading_kit',
              'audience_type'        => 'organizations',
              'title'                => __('Send Me the Community Reading Kit', 'brave-hearts'),
              'text'                 => __('Resources for literacy programs, events, and bulk gifting.', 'brave-hearts'),
              'submit_label'         => __('Get the Community Reading Kit', 'brave-hearts'),
              'source_page'          => $source_page,
              'require_name'         => true,
          ]); ?>
          <p class="audience-landing-lead__fine-print"><?php esc_html_e('Free PDF · No purchase required · Occasional program resource updates. Unsubscribe anytime.', 'brave-hearts'); ?></p>
        <?php else: ?>
          <div class="audience-landing-coming-soon">
            <p class="audience-landing-eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
            <h3><?php esc_html_e('Send Me the Community Reading Kit', 'brave-hearts'); ?></h3>
            <p class="audience-landing__lead" style="font-size:15px;"><?php esc_html_e('The Community Reading Kit is still being finished. Check back soon to get your free copy by email.', 'brave-hearts'); ?></p>
            <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="audience-landing-lead__art">
        <div>
          <?php /* 2026-07-17: real cover in place of the "in progress" placeholder.
             Source PDF (production Media Library attachment #389,
             Community-Resource-Page.pdf) is the approved asset for this lead
             magnet -- its cover art reads "The Community Reading Kit", a clean
             match to this page's public name, so no naming caveat is needed
             here (contrast the Gift Buyer page's cover, which does need one). */ ?>
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/community-reading-kit-cover.webp'); ?>" alt="<?php esc_attr_e('Front cover of the free Community Reading Kit', 'brave-hearts'); ?>" loading="lazy" decoding="async">
          <p class="tag"><?php esc_html_e('Free · Community Reading Kit', 'brave-hearts'); ?></p>
          <p class="sub"><?php esc_html_e('Printable PDF guide', 'brave-hearts'); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== TRUST (Sprint A, Phase 9 -- new for Organizations) ===================== -->
<section class="audience-landing__section audience-landing__section--dark">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <p class="audience-landing-trust-eyebrow"><?php esc_html_e('Series credibility', 'brave-hearts'); ?></p>
    <div class="audience-landing-stat-grid">
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">3</div><p class="audience-landing-stat__label"><?php esc_html_e('published adventures', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">2</div><p class="audience-landing-stat__label"><?php esc_html_e('formats - paperback & hardcover', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Kirkus', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('featured title', 'brave-hearts'); ?></p></div>
      <?php /* 2026-07-17: "placed the series" read as if the classrooms did
         the placing, and implied ongoing active classroom use with no
         evidence to support that stronger claim. "Received the series" is
         the same underlying fact (books were placed/distributed), stated
         accurately and without the awkward grammar. */ ?>
      <?php
      /*
       * N4 (2026-08-03) — the same numberless rule, applied to the stat tile.
       *
       * ⭐ ZERO NEW WORDS. The word "Boise" moves from the label into the
       *    `__num` slot and the numeral 40 is dropped. Nothing is rewritten,
       *    softened or invented: the label below is the 2026-07-17 approved
       *    string with its first word removed, and the tile reads
       *    "Boise / classrooms received the series".
       *
       * ⭐ A WORD IN `__num` IS THIS COMPONENT'S OWN ESTABLISHED PATTERN, not
       *    a new one — the "Kirkus / featured title" tile in this very grid
       *    already does it, and so does "Verified / Amazon reviews" on the
       *    parent landing page. No CSS change is needed or made.
       */
      ?>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Boise', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('classrooms received the series', 'brave-hearts'); ?></p></div>
    </div>
    <?php /* 2026-07-17: this sentence reused the sitewide .audience-landing__lead
       class, whose color: var(--al-text-muted) (#514f45, tuned for the light
       cream background) is nearly unreadable against this section's dark-green
       background -- computed contrast ~1.7:1, far under WCAG AA. Fixed with a
       new class scoped to this exact component only; --al-text-muted and the
       base .audience-landing__lead rule are untouched everywhere else on the
       site (see assets/css/audience-landing.css). Copy also replaced per
       Andrew's approved wording -- previous text ("building relationships...
       one conversation at a time") read as tentative rather than a clear
       statement of how bulk/partnership requests are actually handled. */ ?>
    <p class="audience-landing-trust-note"><?php esc_html_e('Bulk purchases and partnerships are handled personally based on each program’s needs.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--content">
    <h2 style="text-align:center;margin-bottom:40px;"><?php esc_html_e('Questions organizations ask', 'brave-hearts'); ?></h2>
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

<!-- ===================== CONTACT / PARTNERSHIP CTA ===================== -->
<section id="contact" class="audience-landing__section audience-landing__section--major audience-landing-final">
  <div class="audience-landing__inner audience-landing-final__inner">
    <h2><?php esc_html_e('Let’s bring story-led reading to your program.', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Start the free Community Reading Kit - or reach out to talk through bulk orders and partnership opportunities.', 'brave-hearts'); ?></p>
    <p><?php esc_html_e('Organizations are welcome to contact us about literacy programs, classroom or community sponsorships, reading initiatives, event or program partnerships, and bulk book purchases. Every request is reviewed individually.', 'brave-hearts'); ?></p>
    <div class="audience-landing-final__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta data-bhp-event="org_final_cta_click" data-bhp-source="organization_landing"><?php esc_html_e('Get the Community Reading Kit', 'brave-hearts'); ?></a>
      <a class="btn btn-outline-light" href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Start a Partnership Conversation', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<!-- ===================== STICKY MINI-CTA ===================== -->
<div class="audience-landing-stickybar" data-audience-stickybar>
  <div class="audience-landing-stickybar__row">
    <span class="audience-landing-stickybar__text"><?php esc_html_e('Free Community Reading Kit - no purchase needed.', 'brave-hearts'); ?></span>
    <div class="audience-landing-stickybar__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta><?php esc_html_e('Get it free', 'brave-hearts'); ?></a>
      <a class="btn btn-outline-light" href="#contact"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
    </div>
  </div>
</div>

</div>
<?php get_footer(); ?>
