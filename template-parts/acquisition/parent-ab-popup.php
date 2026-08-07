<?php
/**
 * PARENT-FUNNEL A/B EMAIL-CAPTURE POPUP — theme 1.19.204, 2026-08-06.
 *
 * ⭐ Andrew Signore, current turn, verbatim: "I say build it now… Make it 15
 *    second delay." Two hooks, one offer, one form, 15 seconds.
 *
 * ⛔ THE TWO HEADINGS AND THE TWO SUBHEADS BELOW ARE LOCKED, APPROVED COPY.
 *    They are reproduced character-for-character from the brief. Do not
 *    rewrite, shorten, re-punctuate or "improve" either variant — an A/B
 *    test whose copy drifts measures nothing. `content_name` values are
 *    locked with them, because they are the join key between this popup,
 *    the Meta pixel's Lead event and the Mailchimp variant tag.
 *
 * ⚠ ONE CLAIM COLLISION IS RECORDED HERE RATHER THAN RESOLVED. The approved
 *   copy says "Free 20-Minute Reluctant Reader Kit". On 2026-08-03, finding
 *   A6 removed a "20-minute" duration claim from the older parent popup
 *   (see the comment in `parent-popup.php`). Andrew's current-turn approval
 *   of this copy is the later instruction and is what shipped. Flagged for
 *   the decisions register; NOT silently reconciled in either direction.
 *
 * FUNNEL RULES — `.claude/rules/funnels.md`, applied rather than reinvented:
 *   - Storage prefix `bhp_parent_popup`, event prefix `parent_popup`, lead
 *     magnet `reluctant_reader_adventure_kit`, thank-you path
 *     `adventure-kit-thank-you`. IDENTICAL to the timed parent popup and the
 *     exit-intent modal, deliberately: same funnel, same offer, so a visitor
 *     who signed up or dismissed through any of them is not asked again.
 *     Minting a new prefix would have created a second parent funnel.
 *   - ⛔ Nothing here reads or writes the teacher funnel's own storage prefix
 *     or thank-you path (deliberately not spelled out here — the suite
 *     asserts their ABSENCE from this file, and quoting one in a comment
 *     breaks that guard while changing no behaviour). The teacher funnel is
 *     untouched in both directions, and this popup never renders
 *     on `/teachers/` — enforced server-side in
 *     `bhp_should_show_parent_ab_popup()`, not by CSS or JS.
 *
 * CACHE SAFETY — the reason both variants are in the markup.
 *   This file renders BOTH variants for every visitor, byte-identically.
 *   Assignment is made in the browser from a first-party cookie
 *   (`bhp_popup_ab`) and the losing block is removed from the DOM before
 *   anything paints, while the popup is still `hidden`. Nothing about the
 *   assignment appears in the HTML, so a full-page cache cannot pin one
 *   variant to one cached page. Same discipline as the consent/pixel work.
 *
 * DELIVERY — nothing new is built. The form is the SAME
 *   `template-parts/acquisition/signup-form` used by the live parent popup,
 *   posting to the SAME `bhp_mailchimp_signup` endpoint, delivering the SAME
 *   Reluctant Reader Adventure Kit through the SAME redirect key. The only
 *   addition is one hidden `bhp_variant` field, stamped by the engine, which
 *   the server resolves against a fixed whitelist
 *   (`bhp_get_popup_ab_variants()`) exactly like the quiz's route map — the
 *   browser never sends a tag string, an audience or a URL.
 */
defined('ABSPATH') || exit;

$source_page = get_permalink(get_queried_object_id()) ?: home_url('/');
$form_id = 'parent-ab-popup-signup-form';

$submitted_form = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');

$variants = bhp_get_popup_ab_variants();

$ab_config = [
    'cookie'   => BHP_POPUP_AB_COOKIE,
    'days'     => 180,
    'field'    => 'bhp_variant',
    'variants' => [],
];
foreach ($variants as $key => $variant) {
    $ab_config['variants'][$key] = ['contentName' => $variant['content_name']];
}

/**
 * QA ONLY, AND ONLY ON STAGING. `?bhp_ab=A` / `?bhp_ab=B` forces a variant so
 * both can be reviewed without clearing cookies. Inert on production by
 * construction — `BHP_Analytics_Config::is_staging()` compares the real HTTP
 * host — and it is never persisted, so it cannot overwrite a real visitor's
 * sticky assignment.
 */
$forced = bhp_get_popup_ab_forced_variant();
if ($forced) {
    $ab_config['force'] = $forced;
}

$popup_config = wp_json_encode([
    'eventPrefix'   => 'parent_popup',
    'source'        => 'parent_popup_ab',
    'storagePrefix' => 'bhp_parent_popup',
    'thankYouPath'  => 'adventure-kit-thank-you',
    // One capture modal per session, whichever got there first.
    'sessionGuard'  => ['bhp_quiz_auto_shown', 'bhp_popup_shown_session'],
    'abTest'        => $ab_config,
    'trigger'       => [
        // A pure timer. No scroll trigger, no fallback, no exit gesture:
        // Andrew asked for a delay, and a delay is the whole rule.
        //
        // ⚠ THE TWO DELAY ASSIGNMENTS BELOW MUST STAY ON ONE LINE EACH, WITH
        //   SINGLE SPACES AROUND THE ARROW, AND MUST BE THE ONLY TWO
        //   OCCURRENCES IN THIS FILE — ONE PER DEVICE.
        //   `tests/test-popup-ab.php` COUNTS THEM to guard Andrew's "Make it
        //   15 second delay" against a silent edit. Aligning the arrows breaks
        //   that guard while changing no behaviour, and SO DOES QUOTING THE
        //   ASSIGNMENT IN A COMMENT — which is why this note describes it
        //   instead of reproducing it. Both mistakes were made on the
        //   equivalent guard in exit-intent-popup.php, and both were caught by
        //   running the suite rather than by review.
        'mode'    => 'simple',
        'desktop' => ['delay' => 15000],
        'mobile'  => ['delay' => 15000],
    ],
]);

$title_ids = [];
$desc_ids  = [];
foreach (array_keys($variants) as $key) {
    $title_ids[$key] = 'parent-ab-popup-title-' . strtolower($key);
    $desc_ids[$key]  = 'parent-ab-popup-desc-' . strtolower($key);
}
?>
<div
  id="parent-ab-popup"
  class="mariana-popup"
  data-bhp-popup
  data-page-type="<?php echo esc_attr(bhp_get_page_type_for_analytics()); ?>"
  data-force-open="<?php echo $force_open ? '1' : '0'; ?>"
  data-popup-config="<?php echo esc_attr($popup_config); ?>"
  hidden
>
  <div class="mariana-popup__overlay" data-bhp-popup-overlay></div>
  <div
    class="mariana-popup__dialog"
    role="dialog"
    aria-modal="true"
    <?php /* Multiple IDREFs are valid ARIA and missing ones are ignored, so
             the dialog stays correctly labelled after the engine removes the
             losing variant. This is why each variant carries its OWN id
             rather than a shared one — two elements with the same id would
             be invalid HTML for the moments before assignment runs. */ ?>
    aria-labelledby="<?php echo esc_attr(implode(' ', $title_ids)); ?>"
    aria-describedby="<?php echo esc_attr(implode(' ', $desc_ids)); ?>"
    tabindex="-1"
  >
    <button type="button" class="mariana-popup__close" data-bhp-popup-close aria-label="<?php esc_attr_e('Close', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <?php foreach ($variants as $key => $variant): ?>
      <div data-bhp-variant="<?php echo esc_attr($key); ?>">
        <h2 id="<?php echo esc_attr($title_ids[$key]); ?>"><?php echo esc_html($variant['heading']); ?></h2>
        <p id="<?php echo esc_attr($desc_ids[$key]); ?>" class="mariana-popup__text"><?php echo esc_html($variant['sub']); ?></p>
      </div>
    <?php endforeach; ?>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        'context'              => 'parent_popup_ab',
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => true,
        'submit_label'         => __('Send Me the Free Kit', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        // Stamped by the engine at assignment time. Empty in the served HTML,
        // which is what keeps the page byte-identical for every visitor.
        'hidden_fields'        => ['bhp_variant' => ''],
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php esc_html_e('Free printable PDF. No purchase required.', 'brave-hearts'); ?></p>
    <button type="button" class="mariana-popup__dismiss" data-bhp-popup-dismiss><?php esc_html_e('No thanks', 'brave-hearts'); ?></button>
  </div>
</div>
