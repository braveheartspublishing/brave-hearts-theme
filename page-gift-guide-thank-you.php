<?php
/**
 * Template Name: Gift Guide Thank-You Page
 * Description: BH-04 — gift-buyer post-signup confirmation for the Meaningful
 * Gift Guide. Modeled on the Adventure Kit thank-you page (page-adventure-kit-
 * thank-you.php): confirms the exact guide requested, sets the delivery
 * expectation, points to Promotions/Spam, welcomes the visitor to the Adventure
 * Club only as secondary context, and offers the Complete Collection as the
 * natural next step. Only the whitelisted 'gift_guide_thank_you' redirect key
 * in inc/mailchimp.php resolves to this page.
 */
defined('ABSPATH') || exit;
get_header();

$complete_collection_url = home_url('/complete-collection/');
?>
<?php
// Lead-conversion signal, fired only on this dedicated confirmation page
// (reachable only via the whitelisted redirect key), with a client-side
// sessionStorage dedup so a refresh/back-nav does not refire it. No PII.
if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::should_render_analytics() ) {
    $bhp_gg_payload = wp_json_encode(
        array(
            'event'         => 'gift_guide_signup',
            'funnel'        => 'gift',
            'page_type'     => 'thank_you',
            'lead_offer'    => 'meaningful_gift_guide',
            'audience'      => 'gift_buyers',
            'placement'     => 'gift_guide_thank_you_page',
            'signup_method' => 'form',
        )
    );
    ?>
    <script>
    (function () {
        var DEDUP_KEY = 'bhp_gift_guide_signup_fired';
        try {
            if (sessionStorage.getItem(DEDUP_KEY)) { return; }
            sessionStorage.setItem(DEDUP_KEY, '1');
        } catch (e) {}
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(<?php echo $bhp_gg_payload; ?>);
    })();
    </script>
    <?php
}
?>
<section class="passport-status-page section" aria-labelledby="gift-guide-thank-you-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Your gift guide is on the way', 'brave-hearts'); ?></p>
    <h1 id="gift-guide-thank-you-title"><?php esc_html_e('Your Meaningful Gift Guide Is on Its Way', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Check your inbox for the Meaningful Gift Guide. Please allow up to 15 minutes for it to arrive, and check your promotions or spam folder if you don’t see it.', 'brave-hearts'); ?></p>
    <p><?php esc_html_e('You’re also on the Adventure Club list now - occasional gift ideas and reading updates, never spam, and you can unsubscribe anytime.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php // BH-04: the Complete Collection is the natural next step for a gift buyer,
      // after the download confirmation above. No prices hardcoded here. ?>
<section class="passport-section section" aria-labelledby="gift-guide-collection-title">
  <div class="container container--content">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Give the whole adventure', 'brave-hearts'); ?></p>
      <h2 id="gift-guide-collection-title" class="text-section-title"><?php esc_html_e('The Complete Collection - the Whole Series in One Gift', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('The Mariana Trench, Mount Everest, and the Amazon - all three adventures together in one shipment, and often the most memorable way to give the series.', 'brave-hearts'); ?></p>
    </header>
    <p class="align-center">
      <a class="btn btn-primary" href="<?php echo esc_url($complete_collection_url); ?>" data-bhp-event="collection_upsell_click" data-bhp-format="collection" data-bhp-source="gift_guide_thank_you"><?php esc_html_e('See the Complete Collection', 'brave-hearts'); ?></a>
    </p>
    <p class="align-center">
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('Prefer one book? Browse the individual adventures', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
<?php get_footer(); ?>
