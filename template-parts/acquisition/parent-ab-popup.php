<?php
/**
 * PARENT-FUNNEL A/B EMAIL-CAPTURE POPUP — theme 1.19.204, 2026-08-06.
 *
 * ⭐ Andrew Signore, current turn, verbatim: "I say build it now… Make it 15
 *    second delay." Two hooks, one offer, one form, 15 seconds.
 *
 * ⭐ 1.19.207 (2026-08-07) — "FREE", AND NOTHING ELSE, CHANGED.
 *    Andrew Signore's instruction (⛔ RELAYED through the Chief of Staff;
 *    NOT witnessed here): the word FREE reads ALL CAPS, bold and larger
 *    wherever it appears in this popup's copy. Three occurrences, one word
 *    each: both variant subheads, the submit label and the trust caption.
 *    The CASE is the only text edit; the WEIGHT and the SIZE are CSS
 *    (`.popup-ab__free`), applied by `bhp_popup_ab_emphasise_free()`.
 *    ⛔ The trio's "First chapter free" is deliberately UNTOUCHED — Andrew
 *      ruled the trio keeps normal weight, and shouting one of three
 *      parallel items would break the list's rhythm for no gain.
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
 *
 * ---------------------------------------------------------------------
 * 1.19.205 (2026-08-07) — VISUAL POLISH. NO BEHAVIOUR CHANGES.
 *
 * ⭐ Andrew Signore reviewed 1.19.204 on staging and ruled it "not pleasing
 *    to the eye" (RELAYED by chief-of-staff; this file's author did not
 *    witness it). Everything he approved is untouched: the engine, the
 *    assignment, the tracking, the locked copy, the fifteen-second delay and
 *    the suppression rules are unchanged in behaviour. What changed is
 *    presentation only, and it is confined to this file, one optional
 *    argument on the shared signup form, and one CSS block scoped to
 *    `.mariana-popup--ab`.
 *
 *   1. A COVER STRIP. Three existing product-cover attachments — the SAME
 *      media the homepage, the collection page and the parent landing page
 *      already serve — at the `medium` size WordPress generated for them
 *      when they were first uploaded. No new asset, no new upload, no new
 *      image generation. They are CSS BACKGROUNDS, not `<img>` elements, and
 *      the difference is measured rather than stylistic: see the block
 *      comment on the strip itself. Nothing about them is fetched until the
 *      popup opens, so the release's zero-LCP / zero-CLS property survives.
 *
 *   2. A "WHAT'S INSIDE" TRIO, and every item of it describes the resource
 *      that is actually delivered. The Adventure Kit is already described
 *      sitewide as "a complete sample chapter to read tonight, a printable
 *      explorer activity, and simple tips for reading it with a 6 to 9 year
 *      old" (`exit-intent-popup.php`, `footer-capture.php`) — which is what
 *      the three items say in three words each.
 *      ⚠ "20-minute" carries the SAME open duration-claim collision already
 *        recorded at the top of this file (finding A6, 2026-08-03). It is
 *        the same claim Andrew's approved subhead makes, not a new one. It
 *        is flagged again here and again NOT silently resolved either way.
 *
 *   3. PLACEHOLDER-LABELLED FIELDS. The `<label>` elements are STILL IN THE
 *      DOM and still correctly associated; they are visually hidden by CSS
 *      and their text is repeated as the placeholder. Deleting them would
 *      have made the form unusable with a screen reader to buy 48px.
 *
 *   4. THE SUBMIT TAKES THE SITE'S ESTABLISHED CTA TREATMENT by wearing the
 *      real class (`btn-cta-primary`) rather than a copy of its
 *      declarations — forest fill, ivory text, gold hairline: what the
 *      header CTA, the bundle submit and the cart checkout button all use.
 *      1.19.204 rendered `.btn-primary`, which computes to a flat gold fill
 *      on navy text, and that is what "flat tan" named.
 *
 * ⛔ BOTH VARIANTS ARE STYLED IDENTICALLY. The cover strip and the trio sit
 *    OUTSIDE the `[data-bhp-variant]` blocks precisely so the engine's
 *    removal of the losing block cannot take either with it, and so nothing
 *    but the headline and subhead can ever differ between A and B.
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

/**
 * 1.19.205. Three existing cover attachments, cached so a decorative strip in
 * a sitewide surface cannot put a product query on every page load. An empty
 * result renders no strip at all rather than a broken or placeholder one.
 */
$covers = function_exists('bhp_get_popup_ab_covers') ? bhp_get_popup_ab_covers() : [];

/**
 * ⛔ LOCKED WITH THE HEADLINES, FOR THE SAME REASON. These three items are the
 *    approved "what's inside" line and they are IDENTICAL for both variants —
 *    an A/B test in which anything but the hook differs measures the wrong
 *    thing. Each maps to a real component of the delivered Adventure Kit; see
 *    the file header.
 */
$inside = [
    __('20-minute activity', 'brave-hearts'),
    __('First chapter free', 'brave-hearts'),
    __('Parent quick-start', 'brave-hearts'),
];
?>
<div
  id="parent-ab-popup"
  class="mariana-popup mariana-popup--ab"
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

    <?php if ($covers): ?>
      <?php
      /*
       * ⛔ CSS BACKGROUNDS, NOT <img>. The reason is narrower than an earlier
       *    version of this comment claimed, and the correction is recorded
       *    here rather than quietly applied, because the earlier claim was a
       *    MISREAD MEASUREMENT and that is the more useful thing to leave
       *    behind.
       *
       *    WHAT WAS CLAIMED, AND WITHDRAWN: that an `<img loading="lazy">`
       *    inside this popup was still fetched during page load — "observed"
       *    at 4,450 ms against a popup that opened at 19,599 ms. ⛔ THOSE
       *    REQUESTS WERE NOT THE POPUP'S. They were the HOMEPAGE's own book
       *    covers, whose `srcset` carries a 196w candidate that resolves to
       *    the very same file this strip uses. The probe could not tell the
       *    two apart and the conclusion drawn from it was wrong.
       *
       *    WHAT WAS ACTUALLY MEASURED, in a direct experiment: inside a
       *    `display:none` subtree, this Chromium fetches NEITHER a
       *    `loading="lazy"` image NOR a background-image, and fetches both
       *    the moment the subtree is shown. Confirmed against the real popup
       *    on `/about/` — a page with no covers of its own, so nothing can be
       *    misattributed: ZERO cover requests before the open, all three at
       *    16,467 ms against an open at 16,475 ms.
       *
       *    SO WHY BACKGROUNDS. Not because lazy failed — it did not. Because
       *    a background on a non-rendered element is deferred BY DEFINITION
       *    (no box is generated, so no background is ever needed), while
       *    `loading="lazy"` deferral inside `display:none` is an engine
       *    behaviour this session could verify in Chromium ONLY. Safari and
       *    Firefox were NOT tested from this machine and are not claimed. On
       *    a surface that renders on every page of the site, the guarantee
       *    that does not depend on an untested engine is the right one — and
       *    it costs nothing here, because these covers are decoration: no
       *    <img> means no alt to get wrong, and the wrapper is hidden from
       *    assistive technology since the dialog is already labelled by the
       *    headline and three book titles read ahead of it would be noise.
       *
       *    `aspect-ratio` carries the attachment's real dimensions, so the box
       *    is the right shape before the background arrives and the strip
       *    cannot shift as it loads.
       */
      ?>
      <div class="popup-ab__covers" aria-hidden="true">
        <?php foreach ($covers as $cover): ?>
          <span
            class="popup-ab__cover"
            style="background-image:url('<?php echo esc_url($cover['url']); ?>');aspect-ratio:<?php echo (int) $cover['width']; ?>/<?php echo (int) $cover['height']; ?>;"
          ></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php foreach ($variants as $key => $variant): ?>
      <div data-bhp-variant="<?php echo esc_attr($key); ?>">
        <h2 id="<?php echo esc_attr($title_ids[$key]); ?>"><?php echo esc_html($variant['heading']); ?></h2>
        <?php /* 1.19.207: escaped inside bhp_popup_ab_emphasise_free() BEFORE the
                 one <span> is added, so this echo is safe and the copy is still
                 the plain string the suite compares against. */ ?>
        <p id="<?php echo esc_attr($desc_ids[$key]); ?>" class="mariana-popup__text"><?php echo bhp_popup_ab_emphasise_free($variant['sub']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source, see the helper. ?></p>
      </div>
    <?php endforeach; ?>

    <ul class="popup-ab__inside">
      <?php foreach ($inside as $item): ?>
        <li><span class="popup-ab__check" aria-hidden="true">&#10003;</span><?php echo esc_html($item); ?></li>
      <?php endforeach; ?>
    </ul>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        'context'              => 'parent_popup_ab',
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => true,
        // 1.19.205: the labels stay in the DOM and are hidden visually; these
        // repeat their text where the eye is, which is inside the field.
        'name_placeholder'     => __('First name', 'brave-hearts'),
        'email_placeholder'    => __('Email address', 'brave-hearts'),
        // 1.19.207: caps only. The submit control is already the site's
        // bold, large converting-CTA treatment, so it needs no extra span.
        'submit_label'         => __('Send Me the FREE Kit', 'brave-hearts'),
        // The site's established converting-CTA treatment, by class rather
        // than by a re-declaration of it. See the file header, point 4.
        'submit_class'         => 'btn-cta-primary',
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        // Stamped by the engine at assignment time. Empty in the served HTML,
        // which is what keeps the page byte-identical for every visitor.
        'hidden_fields'        => ['bhp_variant' => ''],
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php echo bhp_popup_ab_emphasise_free(__('FREE printable PDF. No purchase required.', 'brave-hearts')); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped at source. ?></p>
    <button type="button" class="mariana-popup__dismiss" data-bhp-popup-dismiss><?php esc_html_e('No thanks', 'brave-hearts'); ?></button>
  </div>
</div>
