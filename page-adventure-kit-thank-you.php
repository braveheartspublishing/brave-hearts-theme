<?php
/**
 * Template Name: Adventure Kit Thank-You Page
 * Description: Parent-specific post-signup confirmation for the Reluctant
 * Reader Adventure Kit. Deliberately separate from the Mariana teacher/
 * parent thank-you page — no teacher or author-visit messaging here, and
 * it must never be reachable via an arbitrary redirect (only the
 * whitelisted 'adventure_kit_thank_you' key in inc/mailchimp.php resolves
 * to this page).
 */
defined('ABSPATH') || exit;
get_header();

$adventures = bhp_get_series_adventures();
?>
<?php
// Analytics Phase 1B/1C: adventure_kit_signup fires ONLY on this page, which
// (per this file's own docblock) is only reachable via the whitelisted
// 'adventure_kit_thank_you' redirect key in inc/mailchimp.php -- the same
// "only the real confirmation page can fire this" trust boundary already
// used for the `purchase` event. No email address or other PII is
// included; this is a lead-conversion signal only.
//
// Phase 1C addition: a page REFRESH or back-navigation to this exact URL
// must not refire the conversion event a second time (matching the same
// dedup requirement already solved for `purchase`). This page has no
// order ID to key a server-side dedup flag off, so the guard runs
// client-side via sessionStorage -- scoped to the tab/session, matching
// the "once per real conversion" intent without needing any new PHP
// state. The event is also enriched with lead_offer/audience/placement
// so it is directly comparable to Phase 1C's other conversion events.
if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::should_render_analytics() ) {
    $bhp_akty_payload = wp_json_encode(
        array(
            'event'       => 'adventure_kit_signup',
            'funnel'      => 'parent',
            'page_type'   => 'thank_you',
            'lead_offer'  => 'reluctant_reader_adventure_kit',
            'audience'    => 'parents_families',
            'placement'   => 'adventure_kit_thank_you_page',
            'signup_method' => 'form',
        )
    );
    ?>
    <script>
    (function () {
        var DEDUP_KEY = 'bhp_adventure_kit_signup_fired';
        try {
            if (sessionStorage.getItem(DEDUP_KEY)) {
                return; // already fired once this session -- refresh/back-nav, not a new conversion
            }
            sessionStorage.setItem(DEDUP_KEY, '1');
        } catch (e) {
            // Private-browsing or storage disabled -- fail safe by still
            // firing once for this load rather than silently dropping the
            // conversion signal entirely.
        }
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(<?php echo $bhp_akty_payload; ?>);
    })();
    </script>
    <?php
}
?>
<section class="passport-status-page section" aria-labelledby="adventure-kit-thank-you-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Your adventure kit is on the way', 'brave-hearts'); ?></p>
    <h1 id="adventure-kit-thank-you-title"><?php esc_html_e('Your Reluctant Reader Adventure Kit Is on Its Way', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Your guide is on the way. Please allow up to 15 minutes for it to arrive, and check your promotions or spam folder if you don’t see it.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php // Finding #22: the Complete Collection is the primary next step after the
      // download instructions above — a compact "Continue the adventure" module.
      // Individual books remain available below as the secondary path. No prices
      // are hardcoded here (the collection page is the canonical price source). ?>
<section class="passport-section section" aria-labelledby="adventure-kit-thank-you-collection-title">
  <div class="container container--content">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Continue the adventure', 'brave-hearts'); ?></p>
      <h2 id="adventure-kit-thank-you-collection-title" class="text-section-title"><?php esc_html_e('Get All Three Adventures in the Complete Collection', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('The Mariana Trench, Mount Everest, and the Amazon - bundled together, shipped in one order, for less than buying each on its own.', 'brave-hearts'); ?></p>
    </header>
    <p class="align-center">
      <a class="btn btn-primary" href="<?php echo esc_url(home_url('/complete-collection/')); ?>" data-bhp-event="collection_upsell_click" data-bhp-format="collection" data-bhp-source="parent_thank_you"><?php esc_html_e('See the Complete Collection', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="adventure-kit-thank-you-choose-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="adventure-kit-thank-you-choose-title" class="text-section-title"><?php esc_html_e('Let Your Child Choose Their Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Prefer to start with a single story? Begin with any one of the three.', 'brave-hearts'); ?></p>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php foreach (['mariana_trench', 'mount_everest', 'amazon_rainforest'] as $key):
        $adventure = $adventures[$key] ?? [];
        if (empty($adventure['primary_url'])) {
            continue;
        }
      ?>
        <?php get_template_part('template-parts/components/book-card', null, [
            'title'       => $adventure['title'] ?? '',
            'url'         => $adventure['primary_url'],
            'image_id'    => $adventure['image_id'] ?? 0,
            'image_alt'   => $adventure['image_alt'] ?? '',
            'description' => $adventure['description'] ?? '',
            'age_range'   => $adventure['age_range'] ?? '',
            'cta_label'   => __('View the book', 'brave-hearts'),
        ]); ?>
      <?php endforeach; ?>
    </div>
    <p class="align-center">
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('See the Full Series', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
<?php get_footer(); ?>
