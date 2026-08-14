<?php
/**
 * CTA-triggered signup modal — theme 1.19.224, 2026-08-13,
 * `CYCLE158-LD-SIGNUP-POPUP`.
 *
 * WHAT IT IS. A modal that a visitor OPENS BY CLICKING A CTA, so they can
 * type an email address and subscribe without scrolling to the inline
 * capture panel. Andrew Signore, current turn, relayed through Gandalf
 * (`chief-of-staff`): the funnel CTAs that currently scroll down to the
 * signup panel must instead open a signup popup — "no scrolling, immediate
 * capture".
 *
 * ⛔ IT IS NOT A LEAD-MAGNET POPUP AND IT DOES NOT REVERSE THE 2026-07-19
 *    ONE-POPUP RULING. Nothing here opens on a timer, on scroll depth, on
 *    exit intent or on any automatic trigger whatsoever. It opens only when
 *    a visitor activates a CTA they can see, and it is the SAME offer, the
 *    SAME funnel and the SAME submission handler as the inline panel that
 *    CTA used to scroll to. `assets/js/mariana-popup.js` never initialises
 *    it — that engine binds `[data-bhp-popup]`, and this element carries
 *    `data-bhp-signup-modal` instead, deliberately.
 *
 * ⭐ THE SUBMISSION PATH IS NOT RE-IMPLEMENTED, IT IS RE-RENDERED. This file
 *    calls `template-parts/acquisition/signup-form.php` with context args,
 *    exactly like `lead-magnet-cta.php`, `parent-popup.php` and
 *    `exit-intent-popup.php` do. There is no second endpoint, no AJAX, no
 *    duplicated validation and no second Mailchimp path. Lead-event
 *    analytics (`lead_form_view`, `lead_form_start`, `lead_signup_success`,
 *    `signup_error`) and the Meta pixel's Lead attribution all live
 *    downstream of that one handler and are untouched by this file.
 *
 * ⭐ MAILCHIMP TAGS ARE BYTE-IDENTICAL TO THE INLINE PANEL, BY CONSTRUCTION.
 *    `bhp_mailchimp_signup_tags` keys off `lead_magnet` for all four
 *    audience magnets, and for `reluctant_reader_adventure_kit` it branches
 *    on `$context === 'parent_popup'` only. This modal's context defaults to
 *    `lead_magnet_modal`, which is not `parent_popup`, so the parent tag set
 *    resolves to the same 'Source: Parent Landing Page' the inline panel
 *    produces. A distinct context still gives analytics a `placement` value
 *    that separates modal captures from inline ones. Verified by reading
 *    both filter callbacks in functions.php, and asserted by
 *    `tests/test-signup-modal.php`.
 *
 * ⛔ FUNNEL ISOLATION. This file reads and writes NOTHING under any funnel
 *    storage prefix. It does not touch `bhp_parent_popup_*` or
 *    `bhp_mariana_popup_*` in either direction. The only session key its JS
 *    writes is the SHARED frequency flag `bhp_popup_shown_session`, which
 *    carries no funnel identity — see the note in `assets/js/signup-modal.js`.
 *
 * ACCESSIBILITY. `role="dialog"`, `aria-modal="true"`, a labelled and
 * described dialog, a focus trap, ESC and overlay-click close, focus
 * returned to the triggering CTA, and background scroll lock. Focus lands
 * in the EMAIL input on open, which is the whole point of the change.
 *
 * NO-JS FALLBACK. The element is `hidden` and stays hidden without
 * JavaScript. Every CTA keeps its original `href="#free"`, so with JS off
 * the CTA still scrolls to the inline panel, which still renders. Nothing
 * about the deep-link `#free` anchor changes.
 *
 * STYLE. Reuses the shipped `.mariana-popup` modal system in style.css
 * (overlay, dialog, close button, mobile dvh/safe-area treatment) plus the
 * `.mariana-popup--signup` variant added in the same release. No new font,
 * no new colour, no new button spec.
 */
defined('ABSPATH') || exit;

$args = wp_parse_args($args ?? [], [
    'id'                   => '',
    'lead_magnet'          => '',
    'audience_type'        => '',
    'source_page'          => '',
    'success_redirect_key' => '',
    'context'              => 'lead_magnet_modal',
    'eyebrow'              => '',
    'title'                => '',
    'text'                 => '',
    'submit_label'         => '',
    'privacy_text'         => '',
    'trust_text'           => '',
    'show_name'            => true,
    'require_name'         => false,
    'name_label'           => '',
]);

$magnets    = bhp_get_lead_magnets();
$magnet_key = sanitize_key($args['lead_magnet']);
if (!isset($magnets[$magnet_key])) {
    return;
}
$magnet = $magnets[$magnet_key];

$modal_id      = sanitize_html_class($args['id'] ?: wp_unique_id('bhp-signup-modal-'));
$form_id       = $modal_id . '-form';
$title_id      = $modal_id . '-title';
$desc_id       = $modal_id . '-desc';
$audience_type = $args['audience_type'] ?: $magnet['audience_type'];
$source_page   = $args['source_page'] ?: (get_permalink(get_queried_object_id()) ?: home_url('/'));

/*
 * Server-side validation bounce. `bhp_process_signup()` redirects back with
 * `bhp_form=<form id>` when a submission fails validation, and
 * `signup-form.php` then renders the error inside THIS form. If the modal
 * stayed closed the visitor would never see their own error message — the
 * same defect `exit-intent-popup.php` solves with `data-force-open`, solved
 * here the same way rather than a second way.
 */
$submitted_form   = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open       = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');

/*
 * ⭐ THE FRONT COVER OF THIS FUNNEL'S OWN PDF. Andrew Signore, relayed through
 *    Gandalf, 2026-08-13: each page's popup carries the front cover of its own
 *    lead magnet, top right. `bhp_get_lead_magnet_cover()` resolves it from a
 *    page-1 render of the REAL PDF and returns [] when there is no cover — the
 *    retailers modal never renders at all (no PDF on either environment), so
 *    this is a guard, not a code path anyone reaches today.
 *
 * ⛔ NEVER A PLACEHOLDER. An absent cover renders no <picture> element; it does
 *    not fall back to a book cover, a neighbouring magnet's cover, or generic
 *    art. Substituting imagery would make the modal claim to show a document
 *    it is not showing.
 */
$cover = function_exists('bhp_get_lead_magnet_cover') ? bhp_get_lead_magnet_cover($magnet_key) : [];
?>
<div
  id="<?php echo esc_attr($modal_id); ?>"
  class="mariana-popup mariana-popup--signup"
  data-bhp-signup-modal
  data-bhp-lead-offer="<?php echo esc_attr($magnet_key); ?>"
  data-bhp-form-audience="<?php echo esc_attr($audience_type); ?>"
  data-page-type="<?php echo esc_attr(bhp_get_page_type_for_analytics()); ?>"
  data-force-open="<?php echo $force_open ? '1' : '0'; ?>"
  <?php if ($cover): ?>data-bhp-cover-preload="<?php echo esc_url($cover['url']); ?>"<?php endif; ?>
  hidden
>
  <div class="mariana-popup__overlay" data-bhp-signup-modal-overlay></div>
  <div
    class="mariana-popup__dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="<?php echo esc_attr($title_id); ?>"
    aria-describedby="<?php echo esc_attr($desc_id); ?>"
    tabindex="-1"
  >
    <button type="button" class="mariana-popup__close" data-bhp-signup-modal-close aria-label="<?php esc_attr_e('Close and keep reading', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <?php
    /*
     * THE HEAD LOCKUP — 1.19.224. The eyebrow and headline occupy the left
     * column; the PDF's front cover occupies the right. Two consequences are
     * deliberate and are the reason this is a flex row rather than a floated
     * image:
     *
     *   - The cover costs vertical space ONLY where it is taller than the two
     *     lines of text beside it, which is what keeps the submit button above
     *     the fold at 1366x768 and at 360x740. A block-level image above the
     *     headline would have cost its full height on every viewport.
     *   - The description stays OUTSIDE the row, full width. Wrapping body
     *     copy into a ~60%-width column at 360px produces five-word lines and
     *     costs more height than the cover saves.
     *
     * ⛔ NO COPY WAS REMOVED TO MAKE ROOM. Eyebrow, headline, description,
     *    optional first name, email, submit, privacy line and trust line are
     *    all still here and all still rendered on every viewport; the fold
     *    requirement was met by compaction in style.css, not by deletion.
     *    Nothing in the indexable page body was touched at all — this modal
     *    adds, it never replaces.
     */
    ?>
    <div class="signup-modal__head">
      <div class="signup-modal__intro">
        <p class="component-heading__eyebrow"><?php echo esc_html($args['eyebrow'] ?: __('Free printable resource', 'brave-hearts')); ?></p>
        <h2 id="<?php echo esc_attr($title_id); ?>"><?php echo esc_html($args['title'] ?: $magnet['title']); ?></h2>
      </div>

      <?php if ($cover): ?>
        <?php
        /*
         * ⭐ AN <img>, NOT A CSS BACKGROUND — and the difference from
         *    `parent-ab-popup.php`'s cover strip is intentional, not an
         *    inconsistency. That strip is DECORATION (three book covers,
         *    `aria-hidden`, no information a screen-reader user loses). This
         *    one is the OFFER: it is the visitor's only sight of the document
         *    they are handing over an email address for, so it needs a real
         *    accessible name, and only an <img> has one.
         *
         *    Width and height are on the element, so the box is the right
         *    shape before the bytes arrive and the head row cannot reflow as
         *    it loads.
         *
         * ⭐ IT COSTS THE PAGE LOAD NOTHING. The modal root is `hidden`, i.e.
         *    `display: none` from the moment the document parses, and no image
         *    inside a non-rendered subtree is fetched. `assets/js/signup-modal.js`
         *    warms it on the FIRST hover/focus/touch of any CTA that opens this
         *    dialog, so by the time the click lands the bytes are in cache —
         *    measured, not assumed; see the QA evidence folder.
         *
         *    `loading="eager"` is explicit rather than default because the
         *    element must never be treated as below-the-fold once the dialog
         *    is shown: at that point it IS the fold.
         */
        ?>
        <picture class="signup-modal__cover">
          <source srcset="<?php echo esc_url($cover['url']); ?>" type="image/webp">
          <img
            src="<?php echo esc_url($cover['fallback']); ?>"
            width="<?php echo (int) $cover['width']; ?>"
            height="<?php echo (int) $cover['height']; ?>"
            alt="<?php echo esc_attr($cover['alt']); ?>"
            loading="eager"
            decoding="async"
          >
        </picture>
      <?php endif; ?>
    </div>

    <div id="<?php echo esc_attr($desc_id); ?>" class="mariana-popup__text"><?php echo wp_kses_post($args['text'] ?: $magnet['description']); ?></div>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        'context'              => $args['context'],
        'audience_type'        => $audience_type,
        'lead_magnet'          => $magnet_key,
        'source_page'          => $source_page,
        'success_redirect_key' => $args['success_redirect_key'],
        /*
         * The inline panels on all five funnel pages pass
         * `require_name => true`. This modal renders the same field but
         * OPTIONAL, on purpose and recorded rather than assumed:
         *
         *   - Focus lands in the email input, so a visitor who types an
         *     address and presses Enter must not be bounced by a validation
         *     error on a field they were never focused in. That bounce is
         *     precisely the friction this change exists to remove.
         *   - Keeping the field (rather than dropping it, as the shipped
         *     exit-intent modal does) preserves the FNAME merge value for
         *     every visitor who chooses to fill it, so no Mailchimp journey
         *     that greets by first name loses its data source.
         *
         * Flagged for Andrew in the return packet as a one-line, reversible
         * decision: `require_name => true` restores parity with the panel.
         */
        'show_name'            => (bool) $args['show_name'],
        'require_name'         => (bool) $args['require_name'],
        'name_label'           => $args['name_label'] ?: __('First name (optional)', 'brave-hearts'),
        'submit_label'         => $args['submit_label'] ?: __('Get the Free Resource', 'brave-hearts'),
        'privacy_text'         => $args['privacy_text'] ?: __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        'aria_labelledby'      => $title_id,
    ]); ?>

    <p class="mariana-popup__trust text-caption"><?php echo esc_html($args['trust_text'] ?: __('Free printable PDF. No purchase required.', 'brave-hearts')); ?></p>
  </div>
</div>
