<?php
/**
 * PARENT-FUNNEL EMAIL-CAPTURE POPUP.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.267 (2026-08-19, `CYCLE165-LD-ITERATE-3-POPUP-SIMPLE`) — BACK TO
 *    BARE BONES. THE FOUNDER'S SPEC, AND IT IS SUBTRACTION, NOT REDESIGN.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, 2026-08-19, VERBATIM (carrier items 107 / 108 / 108a,
 *    relayed to this file's author by the Chief of Staff as first-hand
 *    founder wording):
 *
 *      "our original Pop up that got leads right away was bare bones …
 *       Lets go back to being simple again. Keep a small picture of the
 *       actual front page of the kit on the pop up- but the only words
 *       should be 'Free 20 Minute Reluctant Reader Kit' - one small
 *       picture - name and email- CTA - Join the Adventure"
 *
 *      "KISS rule"
 *
 *      "Yes 20 min is about the kit and yes its about that long"
 *
 *      "Agree with the no span comment under the CTA"   → the one privacy
 *       line below the button
 *
 *      "Agree on the first paint day google recs - wait for engagement and
 *       time."                                          → the trigger
 *
 * ⛔ WHAT WAS REMOVED, LISTED SO THE SUBTRACTION IS LEGIBLE RATHER THAN
 *    ARCHAEOLOGY. Every one of these rendered in 1.19.266 and none of them
 *    renders now: the eyebrow line · the two A/B headline variants and their
 *    two subheads · the three-book decorative cover strip · the "what's
 *    inside" trio of ticked items · the "FREE printable PDF. No purchase
 *    required." trust caption · the "No thanks" dismiss control · the whole
 *    `abTest` config block. What is left is a headline, a picture, two
 *    fields, a button, one line of small print, and a way out.
 *
 * ⛔ THE PRIOR COPY WAS LOCKED, AND IT IS SUPERSEDED RATHER THAN BROKEN.
 *    1.19.204's header said the two headings and two subheads were "LOCKED,
 *    APPROVED COPY … Do not rewrite, shorten, re-punctuate or 'improve'
 *    either variant." That lock was real and it held for thirteen days. It is
 *    superseded by the founder's own later instruction above, which is the
 *    only thing that can supersede it. The superseded strings are NOT deleted
 *    from the codebase — they remain in `bhp_get_popup_ab_variants()` in
 *    `functions.php`, which is what makes this reversible in one commit.
 *
 * ⭐ THE 20-MINUTE CLAIM IS FOUNDER-ATTESTED AND ALSO PRINTED ON THE
 *    ARTEFACT. This matters because finding A6 (2026-08-03) stripped a
 *    "20-minute" duration claim out of the older parent popup, and 1.19.204
 *    recorded the resulting collision without resolving it. Two things now
 *    stand behind the claim: Andrew Signore's own words, quoted above, and
 *    page 1 of the delivered PDF itself, which reads "A Free 20-Minute
 *    Reading Adventure for Curious Kids Ages 6–9" — OBSERVED by rendering
 *    page 1 of the live `Reluctant-Reader-Adventure-Kit.pdf` during this
 *    build, not read from a document. ⛔ The A6 collision is REPORTED to the
 *    Chief of Staff, not declared closed here: an agent does not resolve a
 *    register entry.
 *
 * ⛔ THE HEADLINE IS THE FOUNDER'S STRING, CHARACTER FOR CHARACTER, AND THAT
 *    INCLUDES "20 Minute" WITH NO HYPHEN. Prior copy wrote "20-Minute"; he
 *    wrote it open. Do not "correct" it — reproducing his words is the point,
 *    and the suite compares this string character for character.
 *
 * ⭐ ONE PICTURE, AND IT IS THE REAL FRONT PAGE OF THE REAL PDF.
 *    `bhp_get_lead_magnet_cover()` (1.19.224) already resolves a page-1 render
 *    of the actual `Reluctant-Reader-Adventure-Kit.pdf`, so this release
 *    UPLOADS, GENERATES AND COMPOSITES NOTHING. VERIFIED THIS BUILD, not
 *    trusted from that function's docblock: the kit PDF was fetched from the
 *    staging document root, page 1 re-rendered at 4x with PyMuPDF and
 *    downsampled to 173x224, and compared pixel-for-pixel against the shipped
 *    asset — mean absolute difference 1.371/255 against the PNG and 3.006/255
 *    against the WebP, i.e. resampling and lossy-compression noise. It is the
 *    same artwork.
 *
 * ⛔ THE OLD DECORATIVE STRIP WAS THREE BOOK COVERS AND THIS IS NOT THAT.
 *    That strip was `aria-hidden` decoration and correctly had no alt text.
 *    This image IS the offer — the visitor's only sight of the document they
 *    are handing an email address over for — so it is an `<img>` with a real
 *    accessible name, exactly as `signup-modal.php` argues at length for the
 *    same reason. `loading="lazy"` is what actually keeps it off the page
 *    load: Chromium's preload scanner speculatively fetches an eager
 *    `<img src>` before any stylesheet has told it this subtree is
 *    `display:none`, and leaves a lazy one to machinery that never schedules
 *    an image inside a non-rendered subtree. That was measured in
 *    `CYCLE158-LD-SIGNUP-POPUP` and is re-asserted by this release's suite.
 *
 * ⭐ THE TRIGGER — ENGAGEMENT **AND** TIME, WHICH IS A REAL BEHAVIOUR CHANGE.
 *    1.19.204–1.19.266 ran trigger mode `simple`: a bare 15-second timer, on
 *    Andrew's own "Make it 15 second delay". His 2026-08-19 ruling adds the
 *    second condition, so this moves to mode `gated`, which the shared engine
 *    has carried since before this popup existed:
 *
 *      TIME       — `minDelay`, kept at 15000 ms on BOTH devices. His number
 *                   is not reduced. Nothing can open before it, by any path.
 *      ENGAGEMENT — `scrollPct`, and the scroll condition is INERT until the
 *                   floor has elapsed (`onScroll()` returns early while
 *                   `minTimeElapsed` is false).
 *
 * ⭐ WHY `scrollPct` IS 20 ON DESKTOP AND 12 ON MOBILE — MEASURED ON STAGING,
 *    NOT REASONED ABOUT, AND THE FIRST ANSWER THIS BUILD REACHED WAS WRONG.
 *
 *    The engine's `getScrollPercent()` is NOT "fraction of the article read".
 *    It is `(scrollY + clientHeight) / scrollHeight`, so the number it reports
 *    before a finger moves already includes one whole viewport. That makes the
 *    percentage a poor proxy on its own and the real question "how many
 *    screens of scrolling does this threshold cost on the page that matters".
 *
 *    ⛔ THE WRONG ANSWER, WRITTEN OUT SO IT IS NOT RE-DERIVED: an earlier draft
 *       of this file used 25 on BOTH devices and argued that "the formula is
 *       already viewport-relative, so a phone's shorter screen makes the
 *       identical percentage a shorter scroll." That is backwards. The
 *       homepage is TALLER on a phone than on a desktop (19,607 px against
 *       12,518 px), and the taller page more than cancels the shorter
 *       viewport. 25% on a phone costs 4.81 screens — a deep read demanded
 *       before a free offer is even made.
 *
 *    MEASURED on staging with every lazy image forced to resolve first, so the
 *    heights are the real ones rather than a half-loaded page's:
 *
 *      homepage @1440  12,518 px tall, 900 px viewport, 7.2% at rest
 *                      → 20% costs 1,604 px  = 1.78 screens
 *      homepage @390   19,607 px tall, 844 px viewport, 4.3% at rest
 *                      → 12% costs 1,509 px  = 1.79 screens
 *
 *    So the two numbers differ precisely SO THAT the ask does not: about one
 *    and three-quarter screens of real scrolling on the primary surface,
 *    whichever device a parent is holding. That is engagement. Four screens is
 *    a deep read, and asking for a deep read before making a free offer is how
 *    a popup earns nothing.
 *
 * ⚠ AN HONEST LIMIT OF THE SHARED ENGINE, REPORTED RATHER THAN PAPERED OVER.
 *   Because the formula includes one viewport, ANY page shorter than roughly
 *   `100/scrollPct` viewports already reads above the threshold at rest, and
 *   on such a page the trigger degrades to the 15-second floor alone. Measured
 *   examples on staging: a 7,431 px page at 1440 reads 12.1% at rest, so the
 *   desktop threshold of 20 still requires scrolling there, but a shorter one
 *   would not. Short blog posts are the realistic case. FIXING that would need
 *   an absolute pixel floor — a `scrollPx` config key alongside `scrollPct` —
 *   which is a schema extension to an engine four surfaces share and is
 *   therefore its own piece of work, not a side effect of a copy change.
 *   FLAGGED to the Chief of Staff; NOT absorbed here.
 *
 * ⛔ THERE IS DELIBERATELY NO `fallbackDelay`. The engine treats a fallback as
 *    an UNGATED timer — it calls `trigger()` directly, with no scroll test —
 *    so declaring one would reintroduce exactly the time-only path the
 *    founder's ruling removes. "Engagement AND time" is read as AND.
 *    ⚠ CONSEQUENCE, STATED RATHER THAN HIDDEN: a visitor who lands and never
 *      scrolls at all is never asked. On a page too short to scroll that is
 *      not what happens — the engine's `getScrollPercent()` floors the
 *      scrollable height at 1, so an unscrollable page reads ~100% and the
 *      popup opens when the 15s floor elapses. The loss is confined to long
 *      pages read without a single scroll event, and it is the price of the
 *      ruling, not an oversight.
 *
 * ⛔ THE A/B EXPERIMENT IS OFF, AND IT IS OFF BY ABSENCE. There is no
 *    `abTest` block in the config below, so `parseAbTest()` returns null and
 *    the engine takes the pre-1.19.204 code path: no cookie is read, no
 *    cookie is written, no variant is assigned, no block is removed from the
 *    DOM, and no `variant` / `content_name` field rides on any event. The
 *    engine itself is UNTOUCHED — this is a config change, which is what
 *    `.claude/rules/funnels.md` asks for ("extend the config schema instead").
 *    The variant map, the resolver, the cookie constant and the Mailchimp
 *    variant-tag filter all remain in `functions.php`, unreferenced by this
 *    file, so re-enabling the experiment is one commit rather than one
 *    reconstruction.
 *
 * ⚠ WHAT WAS DELIBERATELY **NOT** RENAMED, AND WHY. This file, the element
 *   `id`, the `mariana-popup--ab` modifier class, the form `id`, and the
 *   `parent_popup_ab` context all keep their names even though the A/B they
 *   were named for is now off. The context string is the join key for the
 *   Mailchimp tag `Source: Parent Popup A/B`; renaming it would mint a NEW
 *   tag in a live audience and split this surface's segment in two. That is a
 *   Mailchimp decision, not an engineering one, and it is Andrew's. FLAGGED
 *   to the Chief of Staff, not absorbed.
 *
 * ---------------------------------------------------------------------
 * FUNNEL RULES — `.claude/rules/funnels.md`, applied rather than reinvented,
 * and BYTE-UNCHANGED by this release:
 *   - Storage prefix `bhp_parent_popup`, event prefix `parent_popup`, lead
 *     magnet `reluctant_reader_adventure_kit`, thank-you path
 *     `adventure-kit-thank-you`. Identical to the timed parent popup and the
 *     exit-intent modal, deliberately: same funnel, same offer, so a visitor
 *     who signed up or dismissed through any of them is not asked again.
 *   - ⛔ Nothing here reads or writes the teacher funnel's own storage prefix
 *     or thank-you path (deliberately not spelled out here — the suite
 *     asserts their ABSENCE from this file, and quoting one in a comment
 *     breaks that guard while changing no behaviour). This popup never
 *     renders on `/teachers/` — enforced server-side in
 *     `bhp_should_show_parent_ab_popup()`, not by CSS or JS.
 *
 * DELIVERY — nothing new is built, and that is the point of reusing the
 *   shared form. It is the SAME `template-parts/acquisition/signup-form`,
 *   posting to the SAME `bhp_mailchimp_signup` endpoint, delivering the SAME
 *   Reluctant Reader Adventure Kit through the SAME redirect key, so the
 *   thank-you page, its `lead_signup_success` handling and the Meta pixel's
 *   Lead event are reached exactly as they were.
 */
defined('ABSPATH') || exit;

$source_page = get_permalink(get_queried_object_id()) ?: home_url('/');
$form_id = 'parent-ab-popup-signup-form';

$submitted_form = isset($_GET['bhp_form']) ? sanitize_html_class(wp_unslash($_GET['bhp_form'])) : '';
$submitted_status = isset($_GET['bhp_signup']) ? sanitize_key(wp_unslash($_GET['bhp_signup'])) : '';
$force_open = ($submitted_form === $form_id && $submitted_status && $submitted_status !== 'success');

$popup_config = wp_json_encode([
    'eventPrefix'   => 'parent_popup',
    'source'        => 'parent_popup_ab',
    'storagePrefix' => 'bhp_parent_popup',
    'thankYouPath'  => 'adventure-kit-thank-you',
    // One capture modal per session, whichever got there first.
    'sessionGuard'  => ['bhp_quiz_auto_shown', 'bhp_popup_shown_session'],
    'trigger'       => [
        // ⚠ THE TWO minDelay ASSIGNMENTS BELOW MUST STAY ON ONE LINE EACH,
        //   WITH SINGLE SPACES AROUND THE ARROW, AND MUST BE THE ONLY TWO
        //   OCCURRENCES IN THIS FILE — ONE PER DEVICE.
        //   `tests/test-popup-ab.php` COUNTS THEM to guard Andrew's "Make it
        //   15 second delay" against a silent edit. Aligning the arrows breaks
        //   that guard while changing no behaviour, and SO DOES QUOTING THE
        //   ASSIGNMENT IN A COMMENT — which is why this note describes it
        //   instead of reproducing it. Both mistakes were made on the
        //   equivalent guard in exit-intent-popup.php, and both were caught by
        //   running the suite rather than by review.
        //
        // ⛔ NO `fallbackDelay` ON EITHER DEVICE. See the file header: the
        //   engine's fallback is an UNGATED timer, and an ungated timer is
        //   precisely the time-only path the founder's ruling removes.
        'mode'    => 'gated',
        'desktop' => ['minDelay' => 15000, 'scrollPct' => 20],
        'mobile'  => ['minDelay' => 15000, 'scrollPct' => 12],
    ],
]);

/**
 * The one picture. An absent or mis-deployed asset renders NO image rather
 * than a broken box or a stand-in — `bhp_get_lead_magnet_cover()` returns an
 * empty array when either file is missing, and never substitutes a
 * neighbouring magnet's cover.
 */
$cover = function_exists('bhp_get_lead_magnet_cover')
    ? bhp_get_lead_magnet_cover('reluctant_reader_adventure_kit')
    : [];
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
    aria-labelledby="parent-ab-popup-title"
    tabindex="-1"
  >
    <?php /* The ONLY way out other than the overlay and Escape, now that the
             "No thanks" control is gone. Its hit area is taken to 44px in
             both directions by the stylesheet — the shared rule is 36px on
             desktop and this surface must not be. */ ?>
    <button type="button" class="mariana-popup__close" data-bhp-popup-close aria-label="<?php esc_attr_e('Close', 'brave-hearts'); ?>">
      <span aria-hidden="true">&times;</span>
    </button>

    <?php if ($cover): ?>
      <picture class="popup-ab__kit-cover">
        <source srcset="<?php echo esc_url($cover['url']); ?>" type="image/webp">
        <img
          src="<?php echo esc_url($cover['fallback']); ?>"
          width="<?php echo (int) $cover['width']; ?>"
          height="<?php echo (int) $cover['height']; ?>"
          alt="<?php echo esc_attr($cover['alt']); ?>"
          loading="lazy"
          decoding="async"
        >
      </picture>
    <?php endif; ?>

    <?php /* ⛔ THE ONLY SENTENCE IN THIS DIALOG. Andrew Signore, 2026-08-19:
             "the only words should be 'Free 20 Minute Reluctant Reader Kit'".
             Character for character, open compound and all. */ ?>
    <h2 id="parent-ab-popup-title"><?php esc_html_e('Free 20 Minute Reluctant Reader Kit', 'brave-hearts'); ?></h2>

    <?php get_template_part('template-parts/acquisition/signup-form', null, [
        'id'                   => $form_id,
        'context'              => 'parent_popup_ab',
        'audience_type'        => 'parents_families',
        'lead_magnet'          => 'reluctant_reader_adventure_kit',
        'source_page'          => $source_page,
        'success_redirect_key' => 'adventure_kit_thank_you',
        'require_name'         => true,
        // The labels stay in the DOM and are hidden visually; these repeat
        // their text where the eye is, which is inside the field. Deleting
        // the labels would buy vertical space by breaking the form for a
        // screen reader.
        'name_placeholder'     => __('First name', 'brave-hearts'),
        'email_placeholder'    => __('Email address', 'brave-hearts'),
        'submit_label'         => __('Join the Adventure', 'brave-hearts'),
        // The site's established converting-CTA treatment, by class rather
        // than by a re-declaration of it.
        'submit_class'         => 'btn-cta-primary',
        // ⭐ Andrew Signore, 2026-08-19: "Agree with the no span comment under
        //    the CTA". The shared form renders `privacy_text` inside
        //    `.acquisition-form__notes`, which sits after the submit button —
        //    so this line is under the CTA by construction, not by CSS.
        'privacy_text'         => __('No spam. Unsubscribe anytime.', 'brave-hearts'),
        'class'                => 'mariana-popup__form',
        'aria_labelledby'      => 'parent-ab-popup-title',
    ]); ?>
  </div>
</div>
