<?php
/**
 * Homepage audience gateway (2026-07-18, audit-fix Change 2).
 *
 * Compact, early-homepage routing module: "What brings you here today?"
 * plus 4 direct links to the audience landing pages and a secondary
 * prompt that scrolls to the existing intro-gated quiz section lower on
 * the page (`#find-your-adventure`, given a stable id via audience-quiz.php's
 * new `id` arg -- see front-page.php's call). Does not open a second quiz
 * instance or duplicate the quiz's own markup/JS; it reuses the one
 * that already exists, exactly per the audit's "use the current supported
 * architecture" instruction.
 *
 * Direct links reuse the same 4 destinations as the sitewide footer
 * cluster (footer.php) and the quiz's own intro-gate direct-links line
 * (audience-quiz.php), but with different copy suited to this earlier,
 * more decision-oriented placement -- not a duplicate of either.
 *
 * Analytics: direct links use the CTA Engine's existing
 * data-bhp-event="contextual_cta_click" + data-bhp-cta-* attribute set
 * (see class-bhp-cta-engine.php), read generically by nav.js's click
 * handler -- no new event name. The quiz prompt reuses the existing
 * quiz_cta_viewed / quiz_cta_clicked event names from quiz-entry-cta.php
 * so both scroll-to-quiz entry points aggregate under the same events,
 * distinguished by their `bhp_source`/`entry_location` value
 * ('homepage_gateway' here vs. 'footer'/page-type elsewhere).
 */
defined('ABSPATH') || exit;

$gateway_links = [
    [
        'label'     => __('Help a reluctant reader', 'brave-hearts'),
        'url'       => home_url('/reluctant-reader-adventure-kit/'),
        'audience'  => 'parents_families',
    ],
    [
        'label'     => __('Find classroom resources', 'brave-hearts'),
        'url'       => home_url('/educators-adventure-learning-toolkit/'),
        'audience'  => 'educators',
    ],
    [
        'label'     => __('Choose a meaningful gift', 'brave-hearts'),
        'url'       => home_url('/gift-buyers-guide/'),
        'audience'  => 'gift_buyers',
    ],
    [
        'label'     => __('Plan a community reading program', 'brave-hearts'),
        'url'       => home_url('/organizations-community-reading-kit/'),
        'audience'  => 'organizations',
    ],
];
?>
<section id="home-audience-gateway" class="homepage-section audience-gateway section" aria-labelledby="home-audience-gateway-title">
  <div class="container audience-gateway__inner">
    <h2 id="home-audience-gateway-title" class="audience-gateway__heading"><?php esc_html_e('What brings you here today?', 'brave-hearts'); ?></h2>
    <ul class="audience-gateway__links">
      <?php foreach ($gateway_links as $gateway_link): ?>
        <li>
          <a
            href="<?php echo esc_url($gateway_link['url']); ?>"
            data-bhp-event="contextual_cta_click"
            data-bhp-cta-id="audience_gateway_<?php echo esc_attr($gateway_link['audience']); ?>"
            data-bhp-cta-placement="homepage_gateway"
            data-bhp-cta-destination="audience_landing"
            data-bhp-cta-audience="<?php echo esc_attr($gateway_link['audience']); ?>"
            data-bhp-cta-funnel-stage="discovery"
          ><?php echo esc_html($gateway_link['label']); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="audience-gateway__quiz-prompt" data-bhp-impression-event="quiz_cta_viewed" data-bhp-source="homepage_gateway" data-bhp-entry-location="homepage_gateway">
      <?php esc_html_e('Not sure?', 'brave-hearts'); ?>
      <?php
      /*
       * B6 / `CYCLE142-CX-20` fix (2026-08-03). This href was
       * `#find-your-adventure`, which was correct when this module was
       * written and is WRONG NOW.
       *
       * The 2026-07-31 quiz consolidation removed the inline quiz section
       * from the homepage and moved the `find-your-adventure` anchor id onto
       * the FOOTER launcher (`footer.php:33-39` passes it on the homepage
       * only). So a visitor clicking "Take the 30-second quiz" mid-page was
       * scrolled past nine sections to the very bottom, to a button they
       * then still had to press.
       *
       * Pointing at the canonical page is deterministic, needs no JS, and
       * makes this link and the new "Start Here" nav entry converge on ONE
       * destination -- so both routes are measured against the same page
       * instead of two.
       */
      ?>
      <a
        href="<?php echo esc_url(home_url('/find-your-adventure/')); ?>"
        data-bhp-event="quiz_cta_clicked"
        data-bhp-source="homepage_gateway"
        data-bhp-entry-location="homepage_gateway"
      ><?php esc_html_e('Take the 30-second quiz.', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
