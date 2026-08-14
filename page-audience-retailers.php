<?php
/**
 * Template Name: Audience Landing - Bookstores & Retailers
 * Description: Wholesale-buyer-facing landing page (independent bookstores,
 * museum stores, national park stores, visitor centers, nature centers,
 * gift shops, educational retailers, children's boutiques) for the
 * Wholesale Guide lead magnet. Stays in a "coming soon" state -- form
 * wired but inactive -- until the guide PDF is set under
 * Settings -> Lead Magnets (see bhp_get_bookstore_guide_download()).
 *
 * Deliberately does NOT route wholesale ordering through WooCommerce --
 * the primary CTA is a wholesale inquiry (contact), not Add to Cart.
 * Ingram distribution readiness has not been confirmed anywhere in the
 * project's canonical docs as of this writing (docs/WORKLOG/2026-07-13.md
 * explicitly lists Ingram as out of scope / not yet touched) -- this page
 * must never claim active Ingram availability; it says "coming soon"
 * instead. Do not change that wording without a canonical-docs update
 * confirming real Ingram readiness first.
 *
 * Shares the audience-landing design system (assets/css/audience-landing.css
 * + assets/js/audience-landing.js) with the other 3 core audience pages.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_bookstore_guide_download();
$adventures = bhp_get_series_adventures();
$contact_url = home_url('/contact/');

if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    $bhp_retailer_landing_payload = wp_json_encode([
        'event'      => 'retailer_landing_view',
        'funnel'     => 'retailers',
        'page_type'  => 'landing_page',
        'lead_offer' => 'bookstore_wholesale_guide',
        'audience'   => 'retailers',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_retailer_landing_payload; ?>);
    </script>
    <?php
endif;

$mariana = $adventures['mariana_trench'] ?? [];
$everest = $adventures['mount_everest'] ?? [];
$amazon  = $adventures['amazon_rainforest'] ?? [];

$shelf_reasons = [
    __('<strong>Visually distinctive</strong> - illustrated covers built around real, recognizable destinations.', 'brave-hearts'),
    __('<strong>A multi-destination series</strong> - three titles now (ocean, mountain, rainforest), with room to grow.', 'brave-hearts'),
    __('<strong>Natural fit for destination retail</strong> - museum stores, park stores, and nature centers can shelve the matching title for their location.', 'brave-hearts'),
    __('<strong>Featuring a Kirkus-reviewed title</strong> - independent editorial credibility for a young middle-grade series.', 'brave-hearts'),
    __('<strong>Gift-ready</strong> - hardcover keepsake edition available alongside paperback.', 'brave-hearts'),
];

$reader_profile = [
    ['title' => __('Ages 6–9', 'brave-hearts'), 'text' => __('Early-to-middle grade readers and the adults buying for them.', 'brave-hearts')],
    ['title' => __('Gift & destination shoppers', 'brave-hearts'), 'text' => __('Visitors looking for a meaningful takeaway tied to where they are.', 'brave-hearts')],
    ['title' => __('Educators & homeschool families', 'brave-hearts'), 'text' => __('Story-led, cross-curricular reading that connects to geography and science.', 'brave-hearts')],
];

$faqs = [
    [__('What age range are the books for?', 'brave-hearts'), __('Readers roughly ages 6–9 (1st–3rd grade) - approachable for independent readers and rich enough for a shared read-aloud.', 'brave-hearts')],
    [__('What editions are available?', 'brave-hearts'), __('Paperback and hardcover editions of all three current titles: The Mariana Trench, Mount Everest, and The Amazon.', 'brave-hearts')],
    [__('What’s in the Wholesale Guide?', 'brave-hearts'), __('Series overview, reader profile, and ordering details for retailers considering the series for their shelves.', 'brave-hearts')],
    [__('Is there a fourth book coming?', 'brave-hearts'), __('The series is designed with room to grow beyond the current three destinations - reach out for the latest on future titles.', 'brave-hearts')],
    [__('How do I place a wholesale order?', 'brave-hearts'), __('Use the contact form below to start a wholesale conversation - we’ll follow up with current ordering details.', 'brave-hearts')],
    [__('Do you offer wholesale discounts or standard trade terms?', 'brave-hearts'), __('Wholesale pricing and terms are worked out directly with each retailer rather than published here - start a wholesale inquiry and we’ll walk through current terms together.', 'brave-hearts')],
    [__('Is there a minimum order quantity?', 'brave-hearts'), __('There’s no published minimum yet - reach out and we’ll figure out what makes sense for your shelf space and expected sell-through.', 'brave-hearts')],
    [__('Can I get a review or desk copy before ordering?', 'brave-hearts'), __('Yes - mention this in your wholesale inquiry and we’ll arrange a copy for you to look over before you commit to an order.', 'brave-hearts')],
    [__('When will Ingram distribution be ready?', 'brave-hearts'), __('There’s no confirmed date yet - Ingram distribution is still in progress. Direct wholesale ordering is available in the meantime through the inquiry form.', 'brave-hearts')],
];
?>
<div class="audience-landing" data-audience-landing>

<!-- ===================== HERO ===================== -->
<section class="audience-landing-hero">
  <div class="audience-landing-hero__bg" aria-hidden="true"></div>
  <div class="audience-landing__inner audience-landing-hero__grid">
    <div>
      <span class="audience-landing-eyebrow audience-landing-hero__badge"><?php esc_html_e('For bookstores, museum stores & educational retailers', 'brave-hearts'); ?></span>
      <h1><?php esc_html_e('A visually distinctive adventure series for your shelves.', 'brave-hearts'); ?></h1>
      <p class="audience-landing__lead"><?php esc_html_e('Illustrated middle-grade adventures built around real destinations - a natural fit for independent bookstores, museum and park stores, nature centers, and educational retailers.', 'brave-hearts'); ?></p>
      <div class="audience-landing-hero__ctas">
        <a class="btn btn-primary" href="#free" data-audience-free-cta data-bhp-signup-modal-open="retailer-wholesale-guide-modal" data-bhp-signup-modal-source="hero" data-bhp-event="retailer_hero_primary_cta_click" data-bhp-source="retailer_landing"><?php esc_html_e('Get the Wholesale Guide', 'brave-hearts'); ?></a>
        <a class="btn btn-outline" href="#contact" data-bhp-event="retailer_hero_secondary_cta_click" data-bhp-source="retailer_landing"><?php esc_html_e('Start a Wholesale Inquiry', 'brave-hearts'); ?></a>
      </div>
      <div class="audience-landing-hero__proof">
        <span>&#9733; <?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('3 published titles, multi-destination series', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('Paperback & hardcover editions', 'brave-hearts'); ?></span>
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
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('3 titles, multi-destination series', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Paperback & hardcover', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Kirkus-reviewed', 'brave-hearts'); ?></span>
  </div>
</section>

<!-- ===================== WHY IT BELONGS ON SHELVES ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing-split">
    <div>
      <span class="audience-landing-eyebrow"><?php esc_html_e('Why the series belongs on shelves', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('A series built for destination retail.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Each title is anchored to a real place - giving a museum store, park store, or nature center a title that matches exactly what a visitor just experienced.', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-checklist">
      <?php foreach ($shelf_reasons as $point): ?>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== READER PROFILE ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Who buys this series', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Your customer profile.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-grid audience-landing-grid--cols-3">
      <?php foreach ($reader_profile as $item): ?>
        <div class="audience-landing-card"><h3><?php echo esc_html($item['title']); ?></h3><p class="desc"><?php echo esc_html($item['text']); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== PRODUCT / DISPLAY APPEAL ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('On the shelf', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Current titles.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Three complete adventures now, with future destinations planned as the series continues.', 'brave-hearts'); ?></p>
    </div>
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
    <p class="audience-landing__pull-quote"><?php esc_html_e('A multi-destination series with future-series potential - one shelf placement now, more titles to grow with later.', 'brave-hearts'); ?></p>
    <div class="audience-landing-grid audience-landing-grid--cols-3" style="margin-top:32px;">
      <?php /*
        2D (2026-08-03) -- HARDCOVER-FIRST. Andrew, walk-4 (RELAYED through the
        Chief of Staff, NOT witnessed by this agent): the funnel pages default
        to hardcover. This page carries no bundle price card and no format
        toggle, so the only offer presentation it has is these two edition
        cards; the hardcover card is MOVED into first position.

        ⛔ ORDER ONLY. Not one character of either card's approved copy, and
        neither list price, is changed -- $11.99 and $17.99 are the same
        strings, in the same nodes, in the opposite order. The third card and
        the wholesale-pricing disclaimer below are untouched.
      */ ?>
      <div class="audience-landing-card"><h3><?php esc_html_e('Hardcover', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('$17.99 current consumer list price per title · keepsake gift edition', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('Paperback', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('$11.99 current consumer list price per title · softcover, matte finish', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('3 titles × 2 formats', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('6 orderable editions across the current series', 'brave-hearts'); ?></p></div>
    </div>
    <p class="audience-landing__lead" style="font-size:14px;margin-top:12px;"><?php esc_html_e('Prices shown are current consumer list prices on braveheartspublishing.com, not wholesale or trade pricing - wholesale terms, margins, and minimums are discussed directly and are not yet published.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== FREE LEAD MAGNET ===================== -->
<section id="free" class="audience-landing__section">
  <div class="audience-landing__inner">
    <div class="audience-landing-lead">
      <div class="audience-landing-lead__content">
        <span class="audience-landing-eyebrow"><?php esc_html_e('Free for retailers', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('Get the details before you order.', 'brave-hearts'); ?></h2>
        <p class="audience-landing__lead"><?php echo wp_kses_post(__('Get the free <strong>Wholesale Guide</strong> - series overview, reader profile, and ordering details for retailers.', 'brave-hearts')); ?></p>
        <div class="audience-landing-checklist audience-landing-checklist--compact audience-landing-lead__checklist">
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Full series overview and reader profile', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Edition and pricing details', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Ordering and contact information', 'brave-hearts'); ?></span></div>
        </div>

        <?php if ($download['ready']): ?>
          <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
              'id'                   => 'retailer-wholesale-guide-signup',
              'lead_magnet'          => 'bookstore_wholesale_guide',
              'audience_type'        => 'retailers',
              'title'                => __('Send Me the Wholesale Guide', 'brave-hearts'),
              'text'                 => __('Series overview, reader profile, and ordering details for retailers.', 'brave-hearts'),
              'submit_label'         => __('Get the Wholesale Guide', 'brave-hearts'),
              'source_page'          => $source_page,
              'require_name'         => true,
          ]); ?>
          <p class="audience-landing-lead__fine-print"><?php esc_html_e('Free PDF · No purchase required · Occasional wholesale updates. Unsubscribe anytime.', 'brave-hearts'); ?></p>
        <?php else: ?>
          <div class="audience-landing-coming-soon">
            <p class="audience-landing-eyebrow"><?php esc_html_e('Coming soon', 'brave-hearts'); ?></p>
            <h3><?php esc_html_e('Send Me the Wholesale Guide', 'brave-hearts'); ?></h3>
            <p class="audience-landing__lead" style="font-size:15px;"><?php esc_html_e('The Wholesale Guide is still being finished. Use the wholesale inquiry below in the meantime.', 'brave-hearts'); ?></p>
            <span class="btn btn-primary" aria-disabled="true"><?php esc_html_e('Coming Soon', 'brave-hearts'); ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="audience-landing-lead__art">
        <div>
          <div class="audience-landing-lead__placeholder-cover">
            <span class="audience-landing-lead__placeholder-label"><?php esc_html_e('Guide cover in progress', 'brave-hearts'); ?></span>
          </div>
          <p class="tag"><?php esc_html_e('Free · Wholesale Guide', 'brave-hearts'); ?></p>
          <p class="sub"><?php esc_html_e('cover design coming soon', 'brave-hearts'); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== ORDERING ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--content">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Ordering', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Ordering through Ingram - coming soon.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Wholesale distribution through Ingram is in progress and not yet active. In the meantime, reach out directly using the wholesale inquiry below and we’ll follow up with current ordering details.', 'brave-hearts'); ?></p>
      <p class="audience-landing__lead" style="font-size:15px;"><?php esc_html_e('Retailer and wholesale terms are being finalized alongside our Ingram rollout - reach out and we’ll share current pricing and ordering details directly.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<!-- ===================== TRUST (Sprint A, Phase 8 -- new for Retailers) ===================== -->
<section class="audience-landing__section audience-landing__section--dark">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <p class="audience-landing-trust-eyebrow"><?php esc_html_e('Series credibility', 'brave-hearts'); ?></p>
    <div class="audience-landing-stat-grid">
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">3</div><p class="audience-landing-stat__label"><?php esc_html_e('published titles, room to grow', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">2</div><p class="audience-landing-stat__label"><?php esc_html_e('formats - paperback & hardcover', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Kirkus', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('featured title', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">6&ndash;9</div><p class="audience-landing-stat__label"><?php esc_html_e('early-to-middle grade age range', 'brave-hearts'); ?></p></div>
    </div>
    <p class="audience-landing__lead" style="margin-top:20px;text-align:center;"><?php esc_html_e('Every title is printed and fulfilled through an established print-on-demand partner in the USA - consistent quality and turnaround across the series.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing__inner--content">
    <h2 style="text-align:center;margin-bottom:40px;"><?php esc_html_e('Questions retailers ask', 'brave-hearts'); ?></h2>
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

<!-- ===================== CONTACT / WHOLESALE CTA ===================== -->
<section id="contact" class="audience-landing__section audience-landing__section--major audience-landing-final">
  <div class="audience-landing__inner audience-landing-final__inner">
    <h2><?php esc_html_e('Interested in carrying the series?', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Start a wholesale conversation and we’ll follow up with current details.', 'brave-hearts'); ?></p>
    <div class="audience-landing-final__ctas">
      <a class="btn btn-gold" href="<?php echo esc_url($contact_url); ?>" data-bhp-event="retailer_wholesale_contact_click" data-bhp-source="retailer_landing"><?php esc_html_e('Start a Wholesale Inquiry', 'brave-hearts'); ?></a>
      <a class="btn btn-outline-light" href="#free"><?php esc_html_e('Get the Wholesale Guide', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<!-- ===================== STICKY MINI-CTA ===================== -->
<div class="audience-landing-stickybar" data-audience-stickybar>
  <div class="audience-landing-stickybar__row">
    <span class="audience-landing-stickybar__text"><?php esc_html_e('Free Wholesale Guide - no obligation.', 'brave-hearts'); ?></span>
    <div class="audience-landing-stickybar__ctas">
      <a class="btn btn-gold" href="#free" data-audience-free-cta data-bhp-signup-modal-open="retailer-wholesale-guide-modal" data-bhp-signup-modal-source="sticky_bar"><?php esc_html_e('Get it free', 'brave-hearts'); ?></a>
      <a class="btn btn-outline-light" href="#contact"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
    </div>
  </div>
</div>

<?php
/*
 * ===================== CTA-TRIGGERED SIGNUP MODAL =====================
 * theme 1.19.223, 2026-08-13, `CYCLE158-LD-SIGNUP-POPUP`.
 *
 * Every "Get the Wholesale Guide" CTA on this page now OPENS this dialog with the caret
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
        'id'                   => 'retailer-wholesale-guide-modal',
        'lead_magnet'          => 'bookstore_wholesale_guide',
        'audience_type'        => 'retailers',
        'source_page'          => $source_page,
        'eyebrow'              => __('Free for retailers', 'brave-hearts'),
        'title'                => __('Send Me the Wholesale Guide', 'brave-hearts'),
        'text'                 => __('Series overview, reader profile, and ordering details for retailers.', 'brave-hearts'),
        'submit_label'         => __('Get the Wholesale Guide', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'trust_text'           => __('Free PDF. No purchase required.', 'brave-hearts'),
    ]);
}
?>
</div>
<?php get_footer(); ?>
