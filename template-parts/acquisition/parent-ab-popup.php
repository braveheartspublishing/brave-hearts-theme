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
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.300 (2026-08-27, `CYCLE167-LD-POPUP-TIME-ONLY`) — THE TRIGGER IS
 *     TIME ONLY. THE SCROLL REQUIREMENT IS REMOVED BY FOUNDER RULING.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, 2026-08-27, carrier item 306, VERBATIM:
 *
 *      "We also dont have the awareness or market share - I think we keep
 *       our pop ups time only."
 *
 *    ⚠ RELAYED to this build through the Chief of Staff, who states he read
 *      it first-hand. NOT witnessed by this desk, and the carrier file itself
 *      was searched for on both mounts and NOT FOUND — so this docblock
 *      records a relay, not a first-hand reading, and says so.
 *
 * ⛔⛔ THIS SUPERSEDES A REAL FOUNDER RULING, AND ONLY THE FOUNDER COULD.
 *    The 2026-08-19 ruling below — *"Agree on the first paint day google recs
 *    - wait for engagement and time."* — was correct, was implemented exactly
 *    as written, and held for eight days. It is SUPERSEDED, NOT WRONG, and it
 *    is NOT deleted from this file. Item 306 is Andrew overruling Andrew,
 *    which is the only thing that can move a founder ruling.
 *
 * ⭐ WHAT CHANGED, MECHANICALLY: mode `gated` → mode `simple`, and the two
 *    `scrollPct` thresholds are GONE rather than raised to something
 *    permissive. In `simple` mode the engine reads the `delay` key, sets
 *    `minTimeElapsed` true at init, and — this is the load-bearing part —
 *    NEVER REGISTERS A SCROLL LISTENER AT ALL (the listener is behind a
 *    `typeof scrollPct === 'number'` guard, engine line ~1192). So the popup
 *    is not merely scroll-INDEPENDENT, it is provably scroll-FREE: there is no
 *    scroll code path left to reach it by.
 *
 * ⭐ ANDREW'S NUMBER IS UNTOUCHED. 15000 ms on BOTH devices, exactly as it has
 *    been since his "Make it 15 second delay". Only the KEY NAME changes, and
 *    only because the engine reads a different key in this mode. The value is
 *    not reduced, raised or rounded.
 *
 * ⭐ THIS IS A RESTORATION, NOT AN INVENTION. 1.19.204–1.19.266 already ran
 *    mode `simple` at 15 seconds. Item 306 returns the popup to the exact
 *    configuration that produced the one paid subscriber this funnel has.
 *
 * ⛔ THE ENGINE IS NOT TOUCHED. `.claude/rules/funnels.md` asks for config
 *    changes rather than engine forks; this needs not even a schema extension,
 *    because it selects a mode the engine has always carried.
 *
 * ---------------------------------------------------------------------
 * ⛔ SUPERSEDED BY ITEM 306 — PRESERVED VERBATIM SO THE MOVEMENT IS LEGIBLE
 *    AND REVERSIBLE IN ONE COMMIT. Everything from here to the end of this
 *    section described the 1.19.267–1.19.299 behaviour and NO LONGER
 *    DESCRIBES THIS FILE. It is kept because the measurements in it are real,
 *    were expensive to obtain, and are exactly what a future reader would
 *    otherwise re-derive:
 *
 *  > ⭐ THE TRIGGER — ENGAGEMENT **AND** TIME, WHICH IS A REAL BEHAVIOUR
 *  >    CHANGE. 1.19.204–1.19.266 ran trigger mode `simple`: a bare 15-second
 *  >    timer, on Andrew's own "Make it 15 second delay". His 2026-08-19
 *  >    ruling adds the second condition, so this moves to mode `gated`,
 *  >    which the shared engine has carried since before this popup existed:
 *  >
 *  >      TIME       — the dwell floor, kept at 15000 ms on BOTH devices. His
 *  >                   number is not reduced. Nothing can open before it.
 *  >      ENGAGEMENT — `scrollPct`, and the scroll condition is INERT until
 *  >                   the floor has elapsed (`onScroll()` returns early while
 *  >                   `minTimeElapsed` is false).
 *  >
 *  > ⭐ WHY `scrollPct` WAS 20 ON DESKTOP AND 12 ON MOBILE — MEASURED ON
 *  >    STAGING, NOT REASONED ABOUT, AND THE FIRST ANSWER THAT BUILD REACHED
 *  >    WAS WRONG.
 *  >
 *  >    The engine's `getScrollPercent()` is NOT "fraction of the article
 *  >    read". It is `(scrollY + clientHeight) / scrollHeight`, so the number
 *  >    it reports before a finger moves already includes one whole viewport.
 *  >    That makes the percentage a poor proxy on its own and the real
 *  >    question "how many screens of scrolling does this threshold cost on
 *  >    the page that matters".
 *  >
 *  >    ⛔ THE WRONG ANSWER, WRITTEN OUT SO IT IS NOT RE-DERIVED: an earlier
 *  >       draft used 25 on BOTH devices and argued that "the formula is
 *  >       already viewport-relative, so a phone's shorter screen makes the
 *  >       identical percentage a shorter scroll." That is backwards. The
 *  >       homepage is TALLER on a phone than on a desktop (19,607 px against
 *  >       12,518 px), and the taller page more than cancels the shorter
 *  >       viewport. 25% on a phone costs 4.81 screens — a deep read demanded
 *  >       before a free offer is even made.
 *  >
 *  >    MEASURED on staging with every lazy image forced to resolve first, so
 *  >    the heights are the real ones rather than a half-loaded page's:
 *  >
 *  >      homepage @1440  12,518 px tall, 900 px viewport, 7.2% at rest
 *  >                      → 20% costs 1,604 px  = 1.78 screens
 *  >      homepage @390   19,607 px tall, 844 px viewport, 4.3% at rest
 *  >                      → 12% costs 1,509 px  = 1.79 screens
 *  >
 *  >    So the two numbers differed precisely SO THAT the ask did not: about
 *  >    one and three-quarter screens of real scrolling on the primary
 *  >    surface, whichever device a parent is holding.
 *  >
 *  > ⚠ AN HONEST LIMIT OF THE SHARED ENGINE, REPORTED RATHER THAN PAPERED
 *  >   OVER. Because the formula includes one viewport, ANY page shorter than
 *  >   roughly `100/scrollPct` viewports already read above the threshold at
 *  >   rest, and on such a page the trigger degraded to the 15-second floor
 *  >   alone. FIXING that would have needed an absolute pixel floor — a
 *  >   `scrollPx` config key — which is a schema extension to an engine four
 *  >   surfaces share.
 *  >   ⭐ ITEM 306 DISSOLVES THIS PROBLEM RATHER THAN SOLVING IT: with no
 *  >     scroll threshold at all, there is no page-height edge case left, and
 *  >     the `scrollPx` extension is no longer needed by THIS surface. It is
 *  >     therefore WITHDRAWN as a flag from this file. (`exit-intent-popup.php`
 *  >     still uses a scroll threshold on mobile and still has the limit.)
 *  >
 *  > ⛔ THERE IS DELIBERATELY NO `fallbackDelay`. The engine treats a fallback
 *  >    as an UNGATED timer — it calls `trigger()` directly, with no scroll
 *  >    test — so declaring one would reintroduce exactly the time-only path
 *  >    the founder's ruling removes.
 *  >    ⚠ CONSEQUENCE, STATED RATHER THAN HIDDEN: a visitor who lands and
 *  >      never scrolls at all is never asked.
 *  >    ⭐ THAT CONSEQUENCE IS PRECISELY WHAT ITEM 306 OVERTURNS. This desk's
 *  >      `CYCLE167-LD-POPUP-TRIGGER-TRACE` (2026-08-27) measured the cost at
 *  >      1.78/1.79 screens and reported it as a FUNNEL DECISION for Andrew
 *  >      rather than changing it unilaterally. He decided. A visitor who
 *  >      lands and never scrolls is now asked, at fifteen seconds.
 *  >    ⛔ NOTE FOR ANYONE RESTORING THE GATE: the reason there is no fallback
 *  >      is still true of `gated` mode and would still apply. Time-only is
 *  >      reached here by mode `simple`, NOT by adding a fallback to a gated
 *  >      config — those two produce the same timing but leave a dead scroll
 *  >      listener and a redundant second timer on the page.
 *  ---------------------------------------------------------------------
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
        // ⚠ THE TWO DELAY ASSIGNMENTS BELOW MUST STAY ON ONE LINE EACH,
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
        // ⭐ MODE `simple` = TIME ONLY, ON FOUNDER CARRIER ITEM 306
        //   ("I think we keep our pop ups time only"). See the file header for
        //   the full supersession note over his 2026-08-19 engagement ruling.
        //
        // ⛔ NO `scrollPct` ON EITHER DEVICE, AND ITS ABSENCE IS THE WHOLE
        //   CHANGE. The engine puts its scroll listener behind a
        //   `typeof scrollPct === 'number'` guard, so omitting the key means no
        //   scroll listener is ever registered — the popup is scroll-FREE, not
        //   merely scroll-independent. Re-adding a threshold here would make
        //   `simple` mode RACE time against scroll and could open the popup
        //   EARLIER than fifteen seconds, which is not what he asked for.
        //
        // ⛔ NO `fallbackDelay` EITHER — it is a `gated`-mode key and does
        //   nothing in this mode. Reaching time-only by adding one to a gated
        //   config would leave a dead scroll listener and a redundant timer.
        'mode'    => 'simple',
        'desktop' => ['delay' => 15000],
        'mobile'  => ['delay' => 15000],
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

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.298 (2026-08-27, `CYCLE167-LD-POPUP-PHOTO`) — THE FOUNDER'S OWN
 *     PHOTOGRAPH JOINS THIS POPUP, AND IT IS HIS OWN SPEC.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, 2026-08-27, VERBATIM (carrier item 297):
 *
 *      "Did we come to a conclusion on adding a picture of me and charlotte as
 *       a gradient right to left with like a playful squiggle in between for
 *       the pop ups?"
 *
 *    Three instructions, and all three are implemented literally rather than
 *    interpreted into something more designed:
 *      "a picture of me and charlotte" — the real photograph, unretouched.
 *      "as a gradient right to left"   — the photo sits at the RIGHT and its
 *                                        left edge dissolves into the content
 *                                        field. The gradient is a CSS mask
 *                                        applied over the image at render
 *                                        time; it is NOT baked into the file,
 *                                        so the photograph that ships is the
 *                                        photograph he took.
 *      "a playful squiggle in between" — a hand-drawn-feel SVG path BETWEEN
 *                                        the copy and the photo, drawn as a
 *                                        vector below.
 *
 * ⛔ NOT AI. The squiggle is a bezier path authored by hand in this file. The
 *    photograph is real and is altered only by crop, scale and the render-time
 *    gradient. No fill, no background extension, no retouch. The founder's
 *    no-AI-imagery direction is not bent for a divider.
 *
 * ⛔⛔ THE NIECE RAIL. Charlotte is his NIECE and he has NO CHILDREN (carrier
 *     item 285, his own correction of an agent's inference made the same
 *     night). The accessible name comes from `bhp_get_founder_photo_alt()` and
 *     is checked by `bhp_niece_canon_violations()` BEFORE it renders — a
 *     violating string stands the whole photograph down rather than shipping a
 *     wrong kinship claim about a real child. See `functions.php`.
 *
 * ⛔ THE HIERARCHY DOES NOT MOVE. Headline, then subhead, then button. The
 *    photograph is a companion to the offer, never a competitor to it: it
 *    occupies its own column at desktop and its own band at mobile, it carries
 *    no text over either face, and it sits BELOW the copy in the DOM so that a
 *    screen reader meets the offer first.
 *
 * ⛔ IT COSTS THE PAGE LOAD NOTHING. `.mariana-popup[hidden]` is `display:none`
 *    from parse, and the image is `loading="lazy"` — the mechanism measured in
 *    `CYCLE158-LD-SIGNUP-POPUP` and re-asserted by this release's suite:
 *    Chromium's preload scanner speculatively fetches an EAGER `<img src>`
 *    before any stylesheet has told it the subtree is not rendered, and leaves
 *    a LAZY one to machinery that never schedules an image inside a
 *    non-rendered subtree. LCP is untouched, and the popup is on a 15-second
 *    engagement gate regardless.
 *
 * ⭐ ONE CROP PER VISITOR. The `<picture>` below pairs each crop with a `media`
 *    query, so a phone fetches the band and a desktop fetches the portrait —
 *    never both, and the JPEG fallbacks are fetched by neither unless the
 *    browser cannot decode WebP.
 *
 * ⛔ AN ABSENT ASSET STANDS THE TREATMENT DOWN COMPLETELY. If any of the four
 *    files is missing, `bhp_get_founder_photo()` returns empty, the `--photo`
 *    modifier is not emitted, and this popup renders exactly as 1.19.297 did.
 *    A mis-deployed photograph must never become a broken box on the surface
 *    that collects the email addresses.
 */
$founder_photo = function_exists('bhp_get_founder_photo') ? bhp_get_founder_photo() : [];
$has_photo = !empty($founder_photo['portrait_webp']);

$popup_classes = 'mariana-popup mariana-popup--ab' . ($has_photo ? ' mariana-popup--photo' : '');
?>
<div
  id="parent-ab-popup"
  class="<?php echo esc_attr($popup_classes); ?>"
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

    <?php /* ⭐ 1.19.298 — THE COPY COLUMN. Everything the visitor has to read
             or type lives inside this one element, so the photograph can be
             given its own column beside it without a single copy element
             moving relative to another. At 1.19.297 these were bare children of
             the dialog; the wrapper is additive and changes no descendant
             selector, because every rule in the stylesheet that targets them
             is a DESCENDANT rule and none is a child rule. Verified by reading
             them, not assumed. */ ?>
    <div class="popup-ab__content">

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

    <?php /* ═══════════════════════════════════════════════════════════════
             ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) —
                 THE OFFER REFRAMES FROM KIT TO CHAPTER. THE FOUNDER PICKED
                 THIS LINE HIMSELF.
             ═══════════════════════════════════════════════════════════════

             ⭐ Andrew Signore, 2026-08-27, VERBATIM (carrier item 290, his own
                remix of the magnet-teardown candidates):

                  "FREE Chapter for Reluctant Readers - I'll send you the
                   chapter now, just add your email - Something like that"

                "Something like that" grants polish latitude. It is used HERE
                for terminal punctuation on the subhead and for nothing else.
                The headline is his string character for character.

             ⛔ THE 2026-08-19 HEADLINE IS SUPERSEDED, NOT BROKEN. That day he
                said "the only words should be 'Free 20 Minute Reluctant Reader
                Kit'", and `tests/test-popup-ab.php` asserted it character for
                character for eight days. That lock was real and it held. It is
                superseded by his own later instruction, which is the only thing
                that can supersede it.

             ⭐ WHY THE REFRAME IS HONEST RATHER THAN A BAIT. The hero is now
                the sample chapter, and the sample chapter IS IN THE DELIVERED
                FILE — the 296 lane read all seven pages of the live
                `Reluctant-Reader-Adventure-Kit-1.pdf` from the production
                document root and found one real chapter (Chapter 7, "The
                Swordfish", from *The Mariana Trench*), a printable explorer
                activity and tips to the parent. The visitor is promised the
                chapter and receives the chapter plus more. That is
                over-delivery, and it is only honest because every surface that
                says WHAT ARRIVES bridges chapter -> Kit. ⭐ THIS POPUP'S OWN
                BRIDGE IS THE COVER IMAGE ABOVE (whose accessible name is the
                Kit) AND `page-adventure-kit-thank-you.php`, which names the Kit
                in its H1 and explains the chapter is inside it. The popup's two
                text lines are HIS, and they are not padded with a third.

             ⛔ "now" IS A SPEED PROMISE AND IT WAS NOT SHIPPED ON FAITH. Item
                290 made it conditional on the delivery mechanism being verified
                end to end; items 292/293/294 record the founder reading his own
                Mailchimp journey builder (id=89) and finding "Parent -
                Acquisition Funnel" ACTIVE since 2026-08-03, triggered by the
                "Reluctant Reader Adventure Kit" tag, step 1 an immediate send
                ("Every day as soon as possible") of "Your Reluctant Reader
                Adventure Kit is Here!", SENT 11. He unpaused its one paused
                step himself. ⚠ THAT IS HIS IN-SYSTEM READ, CARRIED. This build
                made no Mailchimp call of any kind and claims no Mailchimp fact
                of its own.

             ⭐ THE FREE EMPHASIS TREATMENT IS KEPT, NOT REBUILT. 1.19.296
                restored it unconditionally and `bhp_popup_ab_emphasise_free()`
                matches the standalone token `\bFREE\b`, which the new headline
                still opens with — so the weight-800 / 1.28em step in
                `style.css` lands on the same word with no CSS change.
             ═══════════════════════════════════════════════════════════════
             ⭐ 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — THE
                FREE EMPHASIS IS RESTORED, UNCONDITIONALLY.
             ═══════════════════════════════════════════════════════════════
             ⭐ HIS STANDING 1.19.207 ORDER, recorded in `functions.php` at the
                variant-map header: *"Free" → "FREE" IN BOTH SUBHEADS … the word
                must read ALL CAPS, bold and larger wherever it appears in the
                popup copy.* The all-caps lived in the A/B variant map and the
                bold-and-larger were applied by `bhp_popup_ab_emphasise_free()`.
                When the experiment was switched off at 1.19.267 the popup fell
                back to non-variant copy and BOTH halves went with it — the
                function is documented in-source as UNREACHED from this popup.
             ⭐ HE NOTICED FROM THE OUTSIDE, which is how he noticed the
                affiliate links too. Verified live on production by this build
                before the change: this `<h2>`'s innerHTML was the plain string
                with no emphasis node of any kind.
             ⛔ RESTORED AS AN UNCONDITIONAL TREATMENT, NOT BEHIND A TEST FLAG.
                The A/B stays off and this file still emits no `abTest` block.
                The emphasis no longer depends on an experiment being live,
                which is the defect that lost it in the first place.
             ⛔ `bhp_popup_ab_emphasise_free()` ESCAPES FIRST, THEN WRAPS, and
                matches only the standalone token — so the echo below is
                unescaped BY DESIGN and no copy string can inject markup.
             ⭐ 1.19.297 CLOSES THE CASE TENSION THAT 1.19.296 HAD TO RECORD.
                1.19.296 flagged a collision between his 2026-08-19 sentence
                (which writes "Free") and his 1.19.207 order (which says the
                word must read "FREE"). Item 290 writes it "FREE" in his own
                hand, so the two instructions now agree and the tension is
                CLOSED BY HIM rather than adjudicated by an agent. */ ?>
    <h2 id="parent-ab-popup-title"><?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
        echo bhp_popup_ab_emphasise_free(__('FREE Chapter for Reluctant Readers', 'brave-hearts'));
    ?></h2>

    <?php
    /**
     * ⭐⭐ 1.19.297 — THE SUBHEAD SLOT IS FILLED, AND ANDREW FILLED IT.
     *
     * ⭐ HIS LINE, carrier item 290, verbatim: *"I'll send you the chapter now,
     *    just add your email"*. The only latitude taken is the full stop, which
     *    is what "Something like that" was granted for.
     *
     * ⭐ WHY THIS SLOT SHIPPED EMPTY FOR ONE RELEASE, KEPT SO THE RESTRAINT IS
     *    LEGIBLE RATHER THAN ARCHAEOLOGY. The audit that asked for a subhead
     *    also proposed one — *"the three questions I ask a child who says
     *    reading is boring"* — and flagged it in its own claims table as
     *    ⛔ UNVERIFIED, MUST NOT SHIP. The 296 lane OPENED THE REAL KIT PDF and
     *    found the line FALSE: the Kit carries "Three Ways to Make This Feel
     *    Like an Adventure" (three tips to the PARENT), not three questions
     *    asked of a child. So the structure shipped and the sentence did not,
     *    and the sentence that fills it now is the founder's own.
     *
     * ⛔ THE FILTER IS STILL THE OVERRIDE POINT. His words are the DEFAULT
     *    argument, not a hardcoded echo, so `bhp_parent_popup_subhead` can still
     *    change or blank this line without touching the template. Passing '' to
     *    the filter renders nothing, exactly as before.
     *
     * ⛔ NO OUTCOME CLAIM AND NO CONTENTS CLAIM IS MADE HERE. The line promises
     *    a send and a speed. What the file CONTAINS is stated where it lands:
     *    the cover image above (accessible name: the Kit) and the thank-you
     *    page's own bridge.
     */
    $popup_subhead = trim((string) apply_filters(
        'bhp_parent_popup_subhead',
        __("I'll send you the chapter now, just add your email.", 'brave-hearts')
    ));
    if ($popup_subhead !== '') :
    ?>
      <p class="popup-ab__subhead"><?php echo esc_html($popup_subhead); ?></p>
    <?php endif; ?>

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
        /*
         * ⭐ 1.19.297 — THE BUTTON. Was "Join the Adventure" (his 2026-08-19
         *    string); now the send-imperative that matches the offer.
         * ⛔ THE WORD "FREE" IS DELIBERATELY ABSENT FROM THE BUTTON. That is the
         *    magnet-teardown pattern the founder agreed with: FREE belongs in
         *    the headline, where it is the offer, and never on the control,
         *    where it competes with the action. The button says what pressing it
         *    does.
         * ⛔ IT SAYS "chapter", NOT "Kit", so the button matches the headline it
         *    sits under. The Kit is named where the file lands.
         */
        'submit_label'         => __('Send me the chapter', 'brave-hearts'),
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

    </div><?php /* /.popup-ab__content */ ?>

    <?php if ($has_photo): ?>
      <?php /* ⭐ 1.19.298 — "a playful squiggle in between".
               ⛔ DRAWN, NOT GENERATED. Two bezier chains per orientation, the
                  second offset by a pixel and drawn fainter, which is what
                  gives a vector the look of a pen that went over the line
                  twice. Round caps and joins, no dashes, deliberately uneven
                  wave amplitudes — a perfectly regular sine reads as a
                  machine, and he asked for playful.
               ⛔ TWO ORIENTATIONS, ONE SHOWN AT A TIME. A vertical squiggle
                  between two columns at desktop; a horizontal one between the
                  band and the copy at mobile. `preserveAspectRatio="none"`
                  lets each stretch to the seam it divides without the path
                  being re-authored. Both are inline, so neither costs a
                  request, and both are `aria-hidden` — a divider is not
                  content, and announcing it would put noise between the
                  headline and the field a visitor has to type in.
               ⭐ BRAND GOLD, and it is TWO golds: `--color-gold` for the body
                  of the stroke and `--color-gold-deep` for the fainter second
                  pass, because the stylesheet's own palette note records that
                  `--color-gold` alone washes out on cream and this popup's
                  ground is `--color-ivory`. */ ?>
      <div class="popup-ab__seam" aria-hidden="true">
        <svg class="popup-ab__squiggle popup-ab__squiggle--v" viewBox="0 0 28 400" preserveAspectRatio="none" focusable="false" role="presentation">
          <path class="popup-ab__squiggle-ghost" d="M15 12 C 24 47, 6 78, 14 113 S 23 161, 12 199 S 4 247, 16 285 S 24 333, 13 369 S 9 389, 15 397" />
          <path class="popup-ab__squiggle-ink" d="M14 10 C 23 46, 5 76, 13 112 S 22 160, 11 198 S 3 246, 15 284 S 23 332, 12 368 S 8 388, 14 396" />
        </svg>
        <svg class="popup-ab__squiggle popup-ab__squiggle--h" viewBox="0 0 400 28" preserveAspectRatio="none" focusable="false" role="presentation">
          <path class="popup-ab__squiggle-ghost" d="M9 15 C 47 5, 77 25, 113 16 S 161 6, 199 15 S 247 26, 285 14 S 333 4, 369 16 S 389 22, 397 15" />
          <path class="popup-ab__squiggle-ink" d="M8 14 C 46 4, 76 24, 112 15 S 160 5, 198 14 S 246 25, 284 13 S 332 3, 368 15 S 388 21, 396 14" />
        </svg>
      </div>

      <?php /* ⭐ 1.19.298 — THE PHOTOGRAPH.
               ⭐ `<figure>` with no `<figcaption>`: the accessible name is the
                  image's own alt text, which is the string the niece guard
                  checks. A caption would put words next to a child's face for
                  no gain the alt text does not already give.
               ⛔ NO TEXT IS RENDERED OVER THIS IMAGE, at any viewport. The
                  gradient that blends its edge is painted by a sibling
                  pseudo-element in the stylesheet, not by an overlay carrying
                  copy.
               ⛔ THE INTRINSIC width/height ATTRIBUTES ARE READ FROM THE FILES
                  by `bhp_get_founder_photo()`, so the box is the right shape
                  before the bytes arrive and the dialog cannot reflow as it
                  loads — and replacing the artwork stays a file operation
                  rather than a file operation plus a code edit somebody has to
                  remember. */ ?>
      <figure class="popup-ab__photo">
        <picture>
          <source
            media="(max-width: 639px)"
            srcset="<?php echo esc_url($founder_photo['band_webp']); ?>"
            width="<?php echo (int) $founder_photo['band_width']; ?>"
            height="<?php echo (int) $founder_photo['band_height']; ?>"
            type="image/webp"
          >
          <source
            media="(max-width: 639px)"
            srcset="<?php echo esc_url($founder_photo['band_jpg']); ?>"
            width="<?php echo (int) $founder_photo['band_width']; ?>"
            height="<?php echo (int) $founder_photo['band_height']; ?>"
            type="image/jpeg"
          >
          <source
            srcset="<?php echo esc_url($founder_photo['portrait_webp']); ?>"
            width="<?php echo (int) $founder_photo['portrait_width']; ?>"
            height="<?php echo (int) $founder_photo['portrait_height']; ?>"
            type="image/webp"
          >
          <img
            src="<?php echo esc_url($founder_photo['portrait_jpg']); ?>"
            width="<?php echo (int) $founder_photo['portrait_width']; ?>"
            height="<?php echo (int) $founder_photo['portrait_height']; ?>"
            alt="<?php echo esc_attr($founder_photo['alt']); ?>"
            loading="lazy"
            decoding="async"
          >
        </picture>
      </figure>
    <?php endif; ?>
  </div>
</div>
