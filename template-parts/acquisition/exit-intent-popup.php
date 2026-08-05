<?php
/**
 * Exit-intent capture modal — PARENT FUNNEL, free Reluctant Reader
 * Adventure Kit. Wave 1, 2026-08-04, theme 1.19.168.
 *
 * ⛔ SHIPPED DISABLED. `bhp_should_show_exit_intent_popup()` defaults to
 *    FALSE. See the block above that function in functions.php: Andrew
 *    Signore retired every lead-magnet popup sitewide on 2026-07-19 ("the
 *    quiz modal becomes the ONLY popup on the site"), and this file does
 *    not reverse an owner ruling. It is built, wired, tested and one
 *    filter away from live.
 *
 * FUNNEL RULES, all of them from `.claude/rules/funnels.md`, applied here
 * rather than reinvented:
 *   - Storage prefix `bhp_parent_popup`, event prefix `parent_popup`,
 *     lead magnet key `reluctant_reader_adventure_kit`, thank-you path
 *     `adventure-kit-thank-you`. IDENTICAL to the timed parent popup,
 *     deliberately: this is the SAME funnel and the SAME offer, so a
 *     visitor who dismissed or joined through one must not be asked again
 *     through the other. Minting a new prefix would have created a second
 *     parent funnel, which is exactly what the isolation rule forbids.
 *   - ⛔ NOTHING here reads or writes `bhp_mariana_popup_*`. The teacher
 *     funnel is untouched in both directions.
 *   - ⛔ Never on `/teachers/` — enforced server-side in
 *     `bhp_should_show_exit_intent_popup()`, not by CSS or JS.
 *
 * TIMING: `minDelay` 20000 on BOTH devices. Andrew Signore, 2026-08-04:
 * "20 seconds please". The quiz modal's dwell floor and this one are the
 * same number on purpose — a visitor cannot be interrupted by anything
 * automatic inside the first 20 seconds of a page.
 *
 * STACKING: `sessionGuard` blocks this modal outright if the quiz modal or
 * any other engine popup has already been shown in this session.
 *
 * COPY: every string below is from Merry's approved set,
 * `WORKING-DRAFTS\marketing-growth\DRAFT-2026-08-04-WAVE1-CAPTURE-COPY.md`
 * §1.2 (H1/S1 recommended). `submit_label` and `privacy_text` are reused
 * VERBATIM from the live kit page so the modal and the landing page say
 * the same thing in the same words. No number, no count, no urgency, no
 * scarcity, no review claim appears anywhere in this file.
 */
defined('ABSPATH') || exit;

$source_page = get_permalink(get_queried_object_id()) ?: home_url('/');
$form_id = 'exit-intent-signup-form';

$submitted_form = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');

$popup_config = wp_json_encode([
    'eventPrefix'   => 'parent_popup',
    'source'        => 'parent_popup_exit',
    'storagePrefix' => 'bhp_parent_popup',
    'thankYouPath'  => 'adventure-kit-thank-you',
    // One capture modal per session, whichever got there first.
    'sessionGuard'  => ['bhp_quiz_auto_shown', 'bhp_popup_shown_session'],
    'trigger'       => [
        'mode'    => 'exit',
        // 20s dwell floor on both devices. Desktop uses leave-intent only.
        // `interactionCooldownMs` (1.19.173): nothing may open within this
        // many ms of the visitor activating a link, button or form control.
        // Andrew Signore, 2026-08-05: the modal opened while he was clicking
        // "Get the Complete Collection". A CTA click is the opposite of exit
        // intent, and the engine now refuses to confuse the two.
        'desktop' => ['minDelay' => 20000, 'interactionCooldownMs' => 1500],
        // Mobile has no pointer to leave the viewport, so a fast upward
        // flick AFTER 45% depth stands in for it. Conservative on purpose,
        // and MORE conservative since 1.19.173: the travel threshold is
        // raised 300 -> 400 px, and travel now only counts while a real
        // finger gesture is in progress (`touchWindowMs`). A page that
        // scrolls ITSELF — an anchor jump from a CTA tap, a scroll-into-
        // view, a drawer locking the body — accumulates nothing.
        //
        // ⚠ THE DWELL-FLOOR LINE MUST STAY ON ONE LINE, WITH SINGLE SPACES
        //   AROUND ITS ARROW, IN BOTH DEVICE ARRAYS — AND THAT EXACT STRING
        //   MUST APPEAR EXACTLY TWICE IN THIS FILE, ONCE PER DEVICE.
        //   `tests/test-wave1-capture.php` counts its occurrences to guard
        //   Andrew's "20 seconds please" against a silent edit. Aligning the
        //   arrows breaks that guard while changing no behaviour; so does
        //   quoting the line in a comment. Both were done and both were
        //   caught by running that suite, 2026-08-05.
        'mobile'  => [
            'minDelay' => 20000,
            'scrollPct' => 45,
            'upThresholdPx' => 400,
            'upWindowMs' => 600,
            'interactionCooldownMs' => 1500,
            'touchWindowMs' => 700,
        ],
    ],
]);
?>
<div
  id="exit-intent-popup"
  class="mariana-popup mariana-popup--exit"
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
    aria-labelledby="exit-intent-popup-title"
    aria-describedby="exit-intent-popup-desc"
    tabindex="-1"
  >
    <button type="button" class="mariana-popup__close" data-bhp-popup-close aria-label="<?php esc_attr_e('Close and keep browsing', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <p class="component-heading__eyebrow"><?php esc_html_e('Free for parents', 'brave-hearts'); ?></p>
    <h2 id="exit-intent-popup-title"><?php esc_html_e('Before you go, take the free kit.', 'brave-hearts'); ?></h2>
    <p id="exit-intent-popup-desc" class="mariana-popup__text">
      <?php esc_html_e('The Reluctant Reader Adventure Kit: a complete sample chapter to read tonight, a printable explorer activity, and simple tips for reading it with a 6 to 9 year old.', 'brave-hearts'); ?>
    </p>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        // Its own context, so the Mailchimp source tag distinguishes an
        // exit-intent capture from the timed popup and from the landing
        // page without changing either of their tag sets.
        'context'              => 'parent_popup_exit',
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => false,
        'name_label'           => __('First name (optional)', 'brave-hearts'),
        'submit_label'         => __('Send me the free chapter & activity', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        'aria_labelledby'      => 'exit-intent-popup-title',
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php esc_html_e('Free printable PDF. No purchase required.', 'brave-hearts'); ?></p>
    <button type="button" class="mariana-popup__dismiss" data-bhp-popup-dismiss><?php esc_html_e('No thanks, not tonight', 'brave-hearts'); ?></button>
  </div>
</div>
