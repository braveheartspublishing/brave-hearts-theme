<?php
/**
 * Brave Hearts — the parent-funnel capture popup.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * REWRITTEN AT 1.19.267 (2026-08-19, `CYCLE165-LD-ITERATE-3-POPUP-SIMPLE`)
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FILE NAME IS UNCHANGED ON PURPOSE. This is still the popup's suite;
 *    the A/B experiment it was named for is now OFF, and section 4 below is
 *    what proves it is off rather than half-applied. Renaming the file would
 *    break three deploy scripts' artefact assertions to rename a file nobody
 *    reads a URL for.
 *
 * ⛔ WHAT THIS SUITE GUARDED BEFORE, AND WHY MOST OF IT IS GONE. 1.19.204's
 *    version asserted two headings, two subheads, two `content_name` values,
 *    a three-cover strip, a three-item "what's inside" trio and a trust
 *    caption, character for character. Every one of those elements was
 *    DELETED by the founder's 2026-08-19 ruling, so an assertion that they
 *    still render would now fail correctly and for the wrong reason. They are
 *    replaced by section 2's inventory, which asserts the OPPOSITE: that they
 *    are absent, and that nothing has crept back.
 *
 * WHAT THIS SUITE GUARDS NOW:
 *
 *   1. THE TRIGGER — engagement AND time. Andrew Signore, 2026-08-06: "Make
 *      it 15 second delay", and 2026-08-19: "wait for engagement and time."
 *      Both survive: the 15,000 ms floor is counted per device, the mode is
 *      `gated`, and there is NO ungated fallback timer, which is the one edit
 *      that would quietly restore a time-only open.
 *   2. THE COPY INVENTORY — the headline is the founder's string character
 *      for character, it is the ONLY sentence in the dialog, and eleven
 *      specific strings that used to render are asserted ABSENT. This is the
 *      "exactly" test: one h2, one paragraph, two visible fields, one submit,
 *      one close, no list, no link.
 *   3. THE PICTURE — one image, the real page-1 render of the real kit PDF,
 *      with a real accessible name, intrinsic dimensions and lazy loading.
 *   4. THE EXPERIMENT IS OFF, AND REVERSIBLY SO — no config block, no variant
 *      markup, no hidden field, no assignment; and every helper still defined.
 *   5. FUNNEL ISOLATION — parent storage/event prefixes only; no teacher-funnel
 *      key anywhere; never on /teachers/.
 *   6. THE SURFACE — homepage and blog posts, and NOT the selling pages.
 *      Driven through real WP_Query objects, not asserted from source.
 *   7. THE DELIVERY MECHANISM IS UNCHANGED — same form, same thank-you key,
 *      same Lead event. This is the assertion that would catch "simplified
 *      the popup and broke the funnel".
 *   8. THE STYLESHEET — scoped, and the close control takes 44px.
 *
 * ⚠ This is a source- and render-level suite, not a browser. It cannot prove
 *   the popup actually appeared after fifteen seconds and a scroll — that is
 *   measured separately in a real browser and filed in the QA evidence.
 *   Do not read a pass here as a timing observation.
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-popup-ab.php --user=1
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

$failures = 0;

function bhp_ab_assert($condition, $label, &$failures) {
    if ($condition) {
        echo "PASS: {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL: {$label}\n";
}

$theme_dir = get_template_directory();
$tpl_path  = $theme_dir . '/template-parts/acquisition/parent-ab-popup.php';
$js_path   = $theme_dir . '/assets/js/mariana-popup.js';
$px_path   = $theme_dir . '/inc/class-bhp-meta-pixel.php';

$tpl = is_readable($tpl_path) ? (string) file_get_contents($tpl_path) : '';
$js  = is_readable($js_path) ? (string) file_get_contents($js_path) : '';
$px  = is_readable($px_path) ? (string) file_get_contents($px_path) : '';

bhp_ab_assert('' !== $tpl, 'parent-ab-popup.php exists and is readable', $failures);
bhp_ab_assert('' !== $js, 'mariana-popup.js exists and is readable', $failures);
bhp_ab_assert('' !== $px, 'class-bhp-meta-pixel.php exists and is readable', $failures);

/* =====================================================================
 * 1. THE TRIGGER — TIME ONLY, AT FIFTEEN SECONDS
 *
 * ⭐⭐ REWRITTEN 1.19.300 (`CYCLE167-LD-POPUP-TIME-ONLY`) ON FOUNDER CARRIER
 *     ITEM 306, 2026-08-27, VERBATIM: "We also dont have the awareness or
 *     market share - I think we keep our pop ups time only."
 *     ⚠ RELAYED via the Chief of Staff; not witnessed by this suite's author.
 *
 * ⛔ THIS SECTION PREVIOUSLY ASSERTED THE OPPOSITE, and that is recorded
 *    rather than quietly overwritten. Until 1.19.299 it asserted mode `gated`
 *    plus two `scrollPct` thresholds, on Andrew's 2026-08-19 *"wait for
 *    engagement and time."* Item 306 supersedes that ruling by his own word,
 *    so the assertions invert with it. The suite is not being loosened — it
 *    asserts the NEW shape just as strictly, including the things that must
 *    now be ABSENT.
 * ================================================================== */

/* Andrew's number, unchanged since "Make it 15 second delay". Only the KEY
 * moved: `simple` mode reads `delay`, `gated` mode read the dwell floor. The
 * count-exactly-twice convention is kept — it is what catches a silent edit. */
bhp_ab_assert(
    2 === substr_count($tpl, "'delay' => 15000"),
    "item 306: the timer `'delay' => 15000` appears exactly twice, once per device (Andrew Signore: \"Make it 15 second delay\", his number unreduced)",
    $failures
);

// Nothing may open sooner by another number, under either key name.
bhp_ab_assert(
    0 === preg_match("/'(?:min)?[Dd]elay'\s*=>\s*(?!15000)\d+/", $tpl),
    'item 306: no other delay value exists anywhere in the template',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'mode'\s*=>\s*'simple'/", $tpl) &&
    0 === preg_match("/'mode'\s*=>\s*'(gated|exit)'/", $tpl),
    "item 306: trigger mode is 'simple' — time alone opens the popup",
    $failures
);

/* ⛔⛔ THE ASSERTION THIS WHOLE SECTION NOW EXISTS FOR, AND IT IS THE EXACT
 *    MIRROR OF THE ONE IT REPLACED. A `scrollPct` key here would do two bad
 *    things at once: it would make the engine register a scroll listener, and
 *    in `simple` mode it would RACE the timer rather than gate it — so the
 *    popup could open EARLIER than fifteen seconds on a fast scroll. Item 306
 *    asks for time only, and time only means no scroll path in either
 *    direction.
 * ⚠ THE QUOTED KEY, NOT THE BARE WORD — the template's docblock discusses
 *   `scrollPct` at length in the preserved supersession note, and a
 *   bare-substring test would trip on the explanation of the rule it tests.
 *   That defect has been caught on this file's neighbours twice. */
bhp_ab_assert(
    0 === preg_match("/'scrollPct'\s*=>/", $tpl),
    'item 306: NO scroll threshold is configured — no scroll listener is ever registered, so the popup is scroll-FREE, not merely scroll-independent',
    $failures
);

/* A gated-mode fallback is still forbidden, for a NEW reason. It is now a
 * dead key in `simple` mode, and reaching time-only through it would mean
 * mode `gated` plus a fallback — same timing, but a live scroll listener and
 * a redundant second timer shipped to every visitor. */
bhp_ab_assert(
    false === strpos($tpl, "'fallbackDelay'"),
    'item 306: no `fallbackDelay` — time-only is reached by mode simple, not by bolting an ungated timer onto a gated config',
    $failures
);

/* ⭐ THE ENGINE HALF, which is where "time only" is actually decided. Asserted
 * in the JS rather than inferred from config, because a config key means
 * nothing if the engine stopped honouring it. */
bhp_ab_assert(
    1 === preg_match('/var minTimeElapsed = \(mode === \x27simple\x27\);/', $js),
    'item 306: the engine opens the gate at init in simple mode (minTimeElapsed starts true), so the timer alone governs',
    $failures
);

/* ⛔ THE LOAD-BEARING ENGINE LINE. The scroll listener is registered ONLY
 * when a numeric threshold is present. With `scrollPct` absent from the
 * config (asserted above), this guard is what makes the claim "no scroll
 * listener exists" true rather than merely likely. */
bhp_ab_assert(
    1 === preg_match('/if \(typeof scrollPct === \x27number\x27\) \{\s*window\.addEventListener\(\x27scroll\x27, onScroll, \{ passive: true \}\);/', $js),
    'item 306: the engine registers its scroll listener only behind a numeric-threshold guard — with no threshold configured, none is ever attached',
    $failures
);

/* The simple-mode timer path itself: the engine must still arm a timer from
 * the `delay` key, or the popup would never open at all. */
bhp_ab_assert(
    1 === preg_match('/if \(typeof deviceConfig\.delay === \x27number\x27\) \{\s*minTimeTimerId = window\.setTimeout\(trigger, deviceConfig\.delay\);/', $js),
    'item 306: the engine arms the simple-mode timer from the `delay` key — the popup does open, on time alone',
    $failures
);

/* =====================================================================
 * 2. THE COPY INVENTORY — THE "EXACTLY" TEST
 * ================================================================== */

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ UPDATED 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) — THE WORD
 *    **FREE** IS BACK IN CAPS, AND FIVE ASSERTIONS IN THIS FILE MOVED WITH IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ NOTHING HERE WAS RELAXED TO MAKE A BUILD GO GREEN. Every changed assertion
 *    below still asserts exactly what it always asserted — that the founder's
 *    headline renders once, character for character, as the dialog's only
 *    sentence. What changed is WHICH characters, and that came from him:
 *
 *      · 1.19.207 (his standing order): *"Free" → "FREE" … the word must read
 *        ALL CAPS, bold and larger wherever it appears in the popup copy.*
 *      · 2026-08-27, carrier item 279: *"Is FREE not big enough…"* — he raised
 *        it again, from the outside, because it had silently reverted.
 *
 * ⭐ WHY IT REVERTED, AND WHY THAT MATTERS TO THIS SUITE: the caps lived in the
 *    A/B variant map and the bold-and-larger in `bhp_popup_ab_emphasise_free()`,
 *    so switching the experiment off at 1.19.267 took a STANDING FOUNDER
 *    INSTRUCTION with it as collateral damage — and this suite then locked the
 *    reverted state in as if it were the intent. ⛔ THAT IS THE REAL LESSON:
 *    a test can preserve a regression. The emphasis is now unconditional and
 *    scoped to the surface rather than to the experiment, so no future
 *    on/off switch can remove it again.
 *
 * ⚠ HIS OPEN COMPOUND IS UNTOUCHED: "20 Minute", no hyphen. Only the case of
 *   the single word FREE moved.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ UPDATED AGAIN AT 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`)
 *     — THE HEADLINE ITSELF IS REPLACED. HE PICKED A NEW ONE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ AGAIN, NOTHING IS RELAXED. This suite's job has never been to preserve one
 *    particular sentence — it is to guarantee that the FOUNDER'S CURRENT
 *    headline renders EXACTLY ONCE, CHARACTER FOR CHARACTER, as the dialog's
 *    only sentence, and that no agent edits it quietly. Every one of those
 *    properties is still asserted below. What moved is which sentence is his.
 *
 * ⭐ THE AUTHORITY, carrier item 290, 2026-08-27, verbatim:
 *      "FREE Chapter for Reluctant Readers - I'll send you the chapter now,
 *       just add your email - Something like that"
 *    ⚠ RELAYED through the Chief of Staff, who witnessed it. Not witnessed by
 *      the desk that wrote this file.
 *
 * ⭐ NOTE WHAT SURVIVES THE REPLACEMENT UNCHANGED, because it is the point of
 *    the 1.19.296 lesson recorded above: the FREE emphasis is still asserted,
 *    still unconditional, still not behind an A/B flag. The new headline still
 *    opens with the standalone token FREE, so the same guard catches the same
 *    class of regression against a completely different sentence. A guard tied
 *    to the PROPERTY outlives the string; a guard tied to the string would have
 *    had to be rewritten from scratch tonight.
 *
 * ⚠ THE "20 Minute" OPEN-COMPOUND GUARD IS RETIRED WITH THE STRING IT GUARDED.
 *   It is not deleted silently: the words "20 Minute" no longer appear in the
 *   headline at all, so an assertion about their hyphenation would assert
 *   nothing. §297.1 below replaces it with a guard that the RETIRED headline
 *   does not linger anywhere in the rendered dialog.
 */
$headline = 'FREE Chapter for Reluctant Readers';

/*
 * The headline as it appears in RENDERED markup: the word FREE is wrapped by
 * `bhp_popup_ab_emphasise_free()`, which escapes before it wraps.
 */
$headline_rendered = '<span class="popup-ab__free">FREE</span> Chapter for Reluctant Readers';

/* ⛔ THE SUPERSEDED HEADLINE, KEPT AS A NAMED CONSTANT RATHER THAN DELETED, so
 *    that a future reader can see what this surface used to say and so §297.1
 *    can assert it is gone. It is his 2026-08-19 string, superseded by his own
 *    2026-08-27 pick. */
$headline_retired_296 = 'FREE 20 Minute Reluctant Reader Kit';

/* ⚠ Counted in the RENDERED markup, not in the template. The template also
 *   carries the founder's verbatim instruction in its docblock, and that
 *   quotation must not be edited to satisfy a string count — quoting him
 *   accurately is the reason the docblock exists. */
/* ⭐ 1.19.296: the template now routes the headline through
 *    `bhp_popup_ab_emphasise_free()` so the caps carry the emphasis he asked
 *    for. The string is still asserted character for character. */
bhp_ab_assert(
    1 === substr_count($tpl, "bhp_popup_ab_emphasise_free(__('" . $headline . "', 'brave-hearts'))"),
    'the template emits the founder\'s headline character for character',
    $failures
);

ob_start();
get_template_part('template-parts/acquisition/parent-ab-popup');
$rendered = (string) ob_get_clean();

bhp_ab_assert('' !== trim($rendered), 'the template renders', $failures);

/* ⭐ 1.19.296: compared against the RENDERED form of the headline, which now
 *    carries the emphasis span around FREE. Still exactly once, still the only
 *    sentence in the dialog. */
bhp_ab_assert(
    1 === substr_count($rendered, $headline_rendered),
    'the headline appears in the rendered dialog exactly once — it is the only sentence, not a repeated one',
    $failures
);

bhp_ab_assert(
    1 === substr_count($rendered, '<h2 id="parent-ab-popup-title">' . $headline_rendered . '</h2>'),
    'the rendered dialog carries exactly one h2, and it is the founder\'s headline',
    $failures
);

/* ⭐ 1.19.296 — NEW, AND IT IS THE GUARD THAT WOULD HAVE CAUGHT THE 1.19.267
 *    REGRESSION. The emphasis must be present and must NOT depend on any
 *    experiment being live. */
bhp_ab_assert(
    1 === substr_count($rendered, '<span class="popup-ab__free">FREE</span>'),
    '⭐ the word FREE carries its emphasis span unconditionally — not behind an A/B flag',
    $failures
);

/* ⭐ 1.19.296 — the subhead SLOT existed but rendered NOTHING until Andrew
 *    picked a line. The audit's proposed subhead was an unsourced claim about
 *    the Kit's contents and was refused; that assertion asserted it did not
 *    sneak in as a default.
 *
 * ⭐⭐ 1.19.297 — HE PICKED THE LINE, so the slot now renders. The assertion is
 *     INVERTED RATHER THAN DELETED, and it is worth being clear about why that
 *     is not the same as relaxing it:
 *       · the 296 assertion said "nothing unsourced ships here";
 *       · the 297 assertion says "exactly HIS line ships here, verbatim".
 *     Both refuse an invented sentence. The second is the STRONGER of the two,
 *     because an empty slot could be filled by anything at any time, whereas a
 *     character-for-character comparison against his words cannot.
 *
 * ⛔ THE REFUSED LINE IS ASSERTED ABSENT BELOW TOO. The audit's proposal ("the
 *    three questions I ask a child who says reading is boring") was FALSE of the
 *    real Kit, which carries three tips to the PARENT. It must not arrive later
 *    through a filter, a default or a copy-paste. */
$subhead = "I'll send you the chapter now, just add your email.";

bhp_ab_assert(
    1 === substr_count($rendered, '<p class="popup-ab__subhead">' . esc_html($subhead) . '</p>'),
    '⭐ the subhead renders exactly once and is the founder\'s line character for character',
    $failures
);

bhp_ab_assert(
    false === strpos($rendered, 'three questions'),
    '⛔ the audit\'s REFUSED subhead (false of the real Kit) has not arrived through any door',
    $failures
);

/* ⛔ THE FILTER MUST STILL BE THE OVERRIDE POINT. His words are the DEFAULT
 *    argument to `apply_filters()`, not a hardcoded echo, so blanking the slot
 *    stays a one-filter change. Asserted by actually blanking it and
 *    re-rendering — not by reading the template and inferring. */
add_filter('bhp_parent_popup_subhead', '__return_empty_string', 99);
ob_start();
get_template_part('template-parts/acquisition/parent-ab-popup');
$rendered_blank_subhead = (string) ob_get_clean();
remove_filter('bhp_parent_popup_subhead', '__return_empty_string', 99);

bhp_ab_assert(
    false === strpos($rendered_blank_subhead, 'popup-ab__subhead'),
    '⛔ filtering the subhead to empty renders NO element at all — no empty <p>, no reserved gap',
    $failures
);

/* ⭐ §297.1 — THE RETIRED HEADLINE IS GONE FROM THE RENDERED DIALOG. His
 *    2026-08-19 string is superseded by his 2026-08-27 pick; this catches a
 *    partial revert that leaves both sentences on the surface at once. */
bhp_ab_assert(
    false === strpos(wp_strip_all_tags($rendered), $headline_retired_296)
        && false === strpos(wp_strip_all_tags($rendered), '20 Minute Reluctant Reader Kit'),
    '§297.1 ⛔ the retired 1.19.296 headline does not linger anywhere in the dialog',
    $failures
);

/* ⛔ ELEVEN STRINGS THAT USED TO RENDER AND MUST NOT NOW. Andrew Signore:
 *    "the only words should be 'Free 20 Minute Reluctant Reader Kit'". Each of
 *    these was live in 1.19.266. */
$forbidden = [
    "It's Heartbreaking to Watch Them Fall Further Behind",
    'Turn Reluctant Readers Into Willing Readers',
    'You can still change this.',
    'shows you exactly where to start.',
    '20-minute activity',
    'First chapter free',
    'Parent quick-start',
    'printable PDF. No purchase required.',
    'No thanks',
    'Free reading adventure',
    'popup-ab__covers',
];
foreach ($forbidden as $gone) {
    bhp_ab_assert(
        false === strpos($rendered, $gone) && false === strpos($rendered, esc_html($gone)),
        "removed element is absent from the rendered dialog: \"{$gone}\"",
        $failures
    );
}

/* The provider note renders ONLY where no Mailchimp audience is connected,
 * which is the normal condition on staging and has never been true on
 * production. It is stripped before the paragraph count so the same suite
 * gives the same answer on both environments, then asserted separately. */
$form_ready = (bool) bhp_get_signup_form_action('', 'parents_families', 'parent_popup_ab');
$body       = preg_replace('/<p class="acquisition-form__provider-note".*?<\/p>/s', '', $rendered);

bhp_ab_assert(
    $form_ready === (false === strpos($rendered, 'acquisition-form__provider-note')),
    'the disabled-form notice renders if and only if no Mailchimp audience is connected (form_ready=' . ($form_ready ? 'yes' : 'no') . ')',
    $failures
);

/* ⭐ 1.19.297 — TWO paragraphs now, not one, and the second one is the reason.
 *    ⛔ THIS IS THE ASSERTION MOST AT RISK OF BEING QUIETLY LOOSENED, so it is
 *    tightened instead: the count moves 1 -> 2 AND both paragraphs are then
 *    identified by class immediately below, so "two paragraphs" cannot silently
 *    become "the privacy line plus whatever an agent added". Andrew's
 *    2026-08-19 "the only words should be…" bare-bones rule still governs — his
 *    own item-290 pick is what added the second line, and nothing else may. */
bhp_ab_assert(
    2 === substr_count($body, '<p '),
    'exactly TWO paragraphs render in the dialog — the founder\'s subhead and the privacy line, and nothing else',
    $failures
);

bhp_ab_assert(
    1 === substr_count($body, '<p class="popup-ab__subhead">')
        && 1 === substr_count($body, '<p class="acquisition-form__privacy">'),
    '⛔ and those two paragraphs are exactly those two, identified by class, not merely counted',
    $failures
);

/* The subhead sits BETWEEN the headline and the form, by markup order. */
bhp_ab_assert(
    strpos($body, 'parent-ab-popup-title') < strpos($body, 'popup-ab__subhead')
        && strpos($body, 'popup-ab__subhead') < strpos($body, 'acquisition-form__submit'),
    'the subhead renders after the headline and before the button, in document order',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/<p class="acquisition-form__privacy">No spam\. Unsubscribe anytime\.<\/p>/', $body),
    'that paragraph is the privacy line, verbatim ("Agree with the no span comment under the CTA")',
    $failures
);

// UNDER the CTA, by markup order rather than by a stylesheet.
bhp_ab_assert(
    strpos($body, 'acquisition-form__submit') < strpos($body, 'acquisition-form__privacy'),
    'the privacy line comes AFTER the submit button in the document — under the CTA without depending on CSS',
    $failures
);

/* ⚠ MATCHED WITH THE SURROUNDING WHITESPACE ALLOWED FOR. The shared form puts
 *   the label on its own indented line, so the rendered markup is
 *   `>\n    Send me the chapter  </button>` and never
 *   `>Send me the chapter</button>`. The tighter assertion was written first
 *   and would have failed on correct output.
 *
 * ⭐ 1.19.297 — the CTA was "Join the Adventure" (his 2026-08-19 string) and is
 *    now the send-imperative from carrier item 290. ⛔ The assertion's SHAPE is
 *    unchanged: one submit button, that string, exactly once. */
bhp_ab_assert(
    1 === preg_match_all('/<button[^>]*type="submit"[^>]*>\s*Send me the chapter\s*<\/button>/s', $rendered),
    'the CTA reads "Send me the chapter", on exactly one submit button',
    $failures
);

bhp_ab_assert(
    1 === substr_count($rendered, 'Send me the chapter'),
    'that CTA string appears exactly once in the dialog',
    $failures
);

/* ⛔ 1.19.297 — FREE NEVER APPEARS ON THE BUTTON. This is the magnet-teardown
 *    pattern the founder agreed with, and it is asserted rather than trusted to
 *    review: FREE belongs in the headline, where it is the offer, and not on the
 *    control, where it competes with the action. */
bhp_ab_assert(
    1 === preg_match('/<button[^>]*type="submit"[^>]*>(.*?)<\/button>/s', $rendered, $m_submit)
        && false === stripos($m_submit[1], 'free'),
    '⛔ the submit button carries no form of the word "free"',
    $failures
);

/* Two visible fields, and only two. The honeypot is aria-hidden decoration
 * for bots and is removed before counting; every remaining non-hidden input
 * is something a parent can see and type into. */
$no_honeypot = preg_replace('/<div class="bhp-form-honeypot".*?<\/div>/s', '', $body);
preg_match_all('/<input\b(?![^>]*type="hidden")/', $no_honeypot, $visible_inputs);

bhp_ab_assert(
    2 === count($visible_inputs[0]),
    'exactly TWO visible fields render (found ' . count($visible_inputs[0]) . ') — first name and email, nothing else',
    $failures
);

bhp_ab_assert(
    1 === substr_count($no_honeypot, 'type="email"') &&
    1 === substr_count($no_honeypot, 'autocomplete="email"') &&
    1 === substr_count($no_honeypot, 'autocomplete="given-name"'),
    'the email field is type="email" and both fields carry the right autocomplete token',
    $failures
);

// The labels are hidden by CSS, never deleted.
bhp_ab_assert(
    1 === substr_count($rendered, '>First name</label>') &&
    1 === substr_count($rendered, '>Email address</label>'),
    'both field labels are still in the DOM (hidden visually, not removed — screen readers still get them)',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/placeholder="First name"/', $rendered) &&
    1 === preg_match('/placeholder="Email address"/', $rendered),
    'both fields carry the placeholder that replaces the visible label',
    $failures
);

/* NO SECONDARY LINKS, NO LISTS. Both were named in the brief as things to
 * remove, and both are the kind of thing that creeps back one element at a
 * time. */
bhp_ab_assert(
    false === strpos($rendered, '<a ') && false === strpos($rendered, '<a>'),
    'the dialog contains no links at all — no secondary path out of the offer',
    $failures
);

bhp_ab_assert(
    false === strpos($rendered, '<ul') && false === strpos($rendered, '<li'),
    'the dialog contains no list — the bullets are gone, not restyled',
    $failures
);

/* Two buttons: the submit, and the way out. The dismiss control is gone, so
 * the close control is the only non-submit button and it must exist. */
bhp_ab_assert(
    2 === substr_count($rendered, '<button'),
    'exactly two buttons render (found ' . substr_count($rendered, '<button') . ') — the CTA and the close control',
    $failures
);

bhp_ab_assert(
    1 === substr_count($rendered, 'data-bhp-popup-close') &&
    false === strpos($rendered, 'data-bhp-popup-dismiss'),
    'the close control is present and the "No thanks" dismiss control is gone',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/data-bhp-popup-close aria-label="Close"/', $rendered),
    'the close control has an accessible name even though it shows only a glyph',
    $failures
);

/* =====================================================================
 * 3. THE PICTURE — ONE IMAGE, AND IT IS THE REAL ARTEFACT
 * ================================================================== */

$cover = function_exists('bhp_get_lead_magnet_cover')
    ? bhp_get_lead_magnet_cover('reluctant_reader_adventure_kit')
    : [];

bhp_ab_assert(
    !empty($cover['url']) && !empty($cover['fallback']),
    'bhp_get_lead_magnet_cover() resolves the kit cover — the page-1 render of the real PDF, not a placeholder',
    $failures
);

foreach (['webp', 'png'] as $ext) {
    $path = $theme_dir . '/assets/images/lead-magnets/reluctant-reader-adventure-kit-cover.' . $ext;
    bhp_ab_assert(
        file_exists($path) && filesize($path) > 1000,
        "the {$ext} cover asset ships in the artefact and is not a stub",
        $failures
    );
}

/*
 * ⭐ UPDATED 1.19.298 (2026-08-27, `CYCLE167-LD-POPUP-PHOTO`) — THE DIALOG NOW
 *    CARRIES **TWO** IMAGES, AND THE SECOND ONE IS THE FOUNDER'S OWN REQUEST.
 *
 * ⛔ THIS ASSERTION WAS RIGHT AND IS SUPERSEDED, NOT BROKEN. It enforced
 *    Andrew Signore's 2026-08-19 spec — *"one small picture"* — and it held
 *    for eight days. Carrier item 297, 2026-08-27, is his own later
 *    instruction and the only thing that can supersede it:
 *
 *      "Did we come to a conclusion on adding a picture of me and charlotte as
 *       a gradient right to left with like a playful squiggle in between for
 *       the pop ups?"
 *
 * ⛔ THE COUNT IS STILL EXACT, WHICH IS THE WHOLE POINT OF KEEPING IT. It is
 *    now TWO and not "at least one": the kit cover, which is a standing
 *    instruction and the popup's chapter→Kit honesty bridge, and the
 *    photograph. A third image creeping in still fails here, which is the
 *    regression this assertion has always existed to catch.
 *
 * ⚠ IT IS DELIBERATELY CONDITIONAL ON THE PHOTOGRAPH ACTUALLY RESOLVING. If
 *   the four image files are absent from the artefact the treatment stands
 *   down by design and the dialog is back to one image — and this suite must
 *   assert the popup that is actually rendering, not the one that was
 *   intended. `tests/test-cycle167-popup-photo.php` is where the assets'
 *   PRESENCE is required; that is the right place for it and this is not.
 */
$photo_renders = function_exists('bhp_get_founder_photo') && !empty(bhp_get_founder_photo());
$expected_images = $photo_renders ? 2 : 1;

bhp_ab_assert(
    $expected_images === substr_count($rendered, '<img'),
    'exactly ' . $expected_images . ' image(s) render in the dialog — the kit cover'
        . ($photo_renders ? ' and the founder photograph' : '')
        . ' (found ' . substr_count($rendered, '<img') . ')',
    $failures
);

bhp_ab_assert(
    1 === substr_count($rendered, 'class="popup-ab__kit-cover"') &&
    1 === preg_match('/<source srcset="[^"]+\.webp" type="image\/webp">/', $rendered),
    'the image is served as WebP with a PNG fallback through one <picture>',
    $failures
);

/* ⭐ A REAL ACCESSIBLE NAME. This image IS the offer — the visitor's only
 *    sight of the document they are handing an email address over for — so an
 *    empty alt would be wrong here even though it was right for the decorative
 *    three-book strip it replaced. */
bhp_ab_assert(
    1 === preg_match('/alt="Front cover of The Reluctant Reader Adventure Kit"/', $rendered),
    'the image names the document it depicts, by the magnet\'s canonical title',
    $failures
);

bhp_ab_assert(
    0 === preg_match('/<img[^>]*alt=""/', $rendered),
    'the image is not marked decorative',
    $failures
);

/* ⛔ THE PAGE-LOAD GUARD. Chromium's preload scanner speculatively fetches an
 *    EAGER <img src> and <source srcset> while the document is still being
 *    parsed — before any stylesheet has told it this subtree is
 *    `display:none`. `loading="lazy"` is what actually defers it: the scanner
 *    leaves a lazy image to the lazy-loading machinery, which never schedules
 *    an image inside a non-rendered subtree. Measured in
 *    `CYCLE158-LD-SIGNUP-POPUP`; re-measured for this release in a real
 *    browser and filed in the QA evidence. */
bhp_ab_assert(
    1 === preg_match('/<img[^>]*loading="lazy"/s', $rendered),
    'the image is lazy — it costs the page load nothing, and this is the attribute that makes that true',
    $failures
);

/*
 * ⭐ 1.19.296 — ASSERTED AGAINST THE RESOLVED COVER, NOT AGAINST TWO TYPED
 *    NUMBERS. The artwork was regenerated at 346x448 so the popup could render
 *    it at 160px without going soft, and this assertion's hardcoded 173/224 is
 *    exactly the kind of duplicated constant that then fails for the wrong
 *    reason. ⭐ The property that actually matters — the img carries its true
 *    intrinsic dimensions, so the dialog cannot reflow as the bytes arrive —
 *    is now tested against whatever `bhp_get_lead_magnet_cover()` resolves,
 *    which is itself now read from the file rather than typed.
 */
$cover_now = bhp_get_lead_magnet_cover('reluctant_reader_adventure_kit');
bhp_ab_assert(
    !empty($cover_now['width']) && !empty($cover_now['height'])
        && 1 === preg_match(
            '/<img[^>]*width="' . (int) $cover_now['width'] . '"[^>]*height="' . (int) $cover_now['height'] . '"/s',
            $rendered
        ),
    'the image carries its intrinsic dimensions, so the dialog cannot reflow as it loads',
    $failures
);

/* ⭐ 1.19.296 — and the aspect ratio must not have moved, because the signup
 *    modal and the Adventure Kit thank-you page render this same asset
 *    height-driven with width:auto and would silently reflow if it had. */
bhp_ab_assert(
    !empty($cover_now['height'])
        && abs(($cover_now['width'] / $cover_now['height']) - (173 / 224)) < 0.000001,
    '⛔ the cover aspect ratio is unchanged — two other surfaces take their shape from it',
    $failures
);

/* =====================================================================
 * 4. THE EXPERIMENT IS OFF — AND REVERSIBLY SO
 * ================================================================== */

/* ⚠ The quoted key again, and for the same reason: the docblock explains that
 *   the block is absent, so it names it. */
bhp_ab_assert(
    false === strpos($tpl, "'abTest'") && false === strpos($tpl, 'BHP_POPUP_AB_COOKIE'),
    'the template declares no abTest block — the engine takes its pre-experiment path',
    $failures
);

bhp_ab_assert(
    false === strpos($rendered, 'data-bhp-variant') &&
    false === strpos($rendered, 'name="bhp_variant"'),
    'no variant markup and no variant field reach the browser',
    $failures
);

bhp_ab_assert(
    false === strpos($rendered, 'popup_hook_heartbreak') && false === strpos($rendered, 'popup_hook_willing'),
    'no content_name reaches the browser — there is one message, so there is nothing to tell apart',
    $failures
);

/* ⛔ OFF, NOT DEMOLISHED. Andrew's approved 1.19.204 copy and the whitelist
 *    that keeps a posted variant key from becoming an arbitrary Mailchimp tag
 *    both stay in the codebase, so re-running the experiment is one commit
 *    rather than a reconstruction. */
foreach ([
    'bhp_get_popup_ab_variants',
    'bhp_resolve_popup_ab_variant',
    'bhp_popup_ab_emphasise_free',
    'bhp_get_popup_ab_covers',
] as $helper) {
    bhp_ab_assert(
        function_exists($helper),
        "{$helper}() is retained for reversibility, not deleted",
        $failures
    );
}

bhp_ab_assert(
    defined('BHP_POPUP_AB_COOKIE') && BHP_POPUP_AB_COOKIE === 'bhp_popup_ab',
    'the A/B cookie constant is retained and unchanged',
    $failures
);

$variants = bhp_get_popup_ab_variants();
bhp_ab_assert(
    isset($variants['A']['heading'], $variants['B']['heading']) &&
    $variants['A']['heading'] === "It's Heartbreaking to Watch Them Fall Further Behind" &&
    $variants['B']['heading'] === 'Turn Reluctant Readers Into Willing Readers',
    'the superseded approved copy survives in the variant map, character for character',
    $failures
);

// The whitelist still refuses to guess.
bhp_ab_assert(
    '' === bhp_resolve_popup_ab_variant('Z') && 'A' === bhp_resolve_popup_ab_variant('a'),
    'the variant whitelist still resolves a known key and refuses an unknown one',
    $failures
);

/* =====================================================================
 * 5. FUNNEL ISOLATION
 * ================================================================== */

bhp_ab_assert(
    false === strpos($tpl, 'bhp_mariana_popup') && false === strpos($tpl, 'mariana-guide-thank-you'),
    'the template touches no teacher-funnel storage key or thank-you path',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'storagePrefix'\s*=>\s*'bhp_parent_popup'/", $tpl) &&
    1 === preg_match("/'eventPrefix'\s*=>\s*'parent_popup'/", $tpl),
    'the popup keeps the parent funnel\'s existing storage and event prefixes — same funnel, same suppression',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'sessionGuard'\s*=>\s*\['bhp_quiz_auto_shown', 'bhp_popup_shown_session'\]/", $tpl),
    'one capture modal per session — the shared session guard is unchanged',
    $failures
);

/* =====================================================================
 * 6. THE SURFACE — HOMEPAGE AND BLOG POSTS, NOT THE SELLING PAGES
 *
 * ⭐ DRIVEN THROUGH REAL WP_Query OBJECTS, NOT ASSERTED FROM SOURCE. A regex
 *    over `functions.php` proves a line exists; it does not prove the function
 *    answers correctly, and the difference is the whole point of a surface
 *    rule. The global query is swapped and restored around each case.
 * ================================================================== */

bhp_ab_assert(
    function_exists('bhp_should_show_parent_ab_popup'),
    'bhp_should_show_parent_ab_popup() is defined',
    $failures
);

// The /teachers/ exclusion stays enforced server-side, not by CSS or JS.
$fn = (string) file_get_contents($theme_dir . '/functions.php');
bhp_ab_assert(
    1 === preg_match('/function bhp_should_show_parent_ab_popup\(\)\s*\{.*?is_page\(\x27teachers\x27\).*?\}/s', $fn),
    'bhp_should_show_parent_ab_popup() excludes /teachers/ server-side',
    $failures
);

/**
 * Run the surface rule against a real query, then put the globals back
 * exactly as they were. Returns null when the environment has no post of the
 * requested shape, so a missing fixture reports as a skip rather than as a
 * false pass.
 *
 * ⛔ THE CURRENT USER IS DROPPED FOR THE DURATION, AND WITHOUT THAT THIS TEST
 *    IS WORTHLESS. `bhp_should_show_any_popup()` — which every surface rule
 *    sits on top of — returns false outright for a logged-in administrator,
 *    and this suite runs as `--user=1`. Left alone, every case below would
 *    answer false and three of the four assertions would pass for entirely
 *    the wrong reason. The user is restored before returning.
 */
function bhp_ab_surface_answer(array $query_args) {
    $ids = get_posts(array_merge(
        ['posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'publish'],
        $query_args
    ));
    if (empty($ids)) {
        return null;
    }

    $type = $query_args['post_type'] ?? 'post';
    // Pages are queried by `page_id`; `p` would set is_single rather than
    // is_page, and is_front_page() reads the is_page flag.
    $args = ('page' === $type) ? ['page_id' => $ids[0]] : ['p' => $ids[0], 'post_type' => $type];

    global $wp_query, $post;
    $saved_query = $wp_query;
    $saved_post  = $post;
    $saved_user  = get_current_user_id();

    wp_set_current_user(0);
    $wp_query = new WP_Query($args);

    $answer = null;
    if ($wp_query->have_posts()) {
        $wp_query->the_post();
        $answer = bhp_should_show_parent_ab_popup();
        wp_reset_postdata();
    }

    $wp_query = $saved_query;
    $post     = $saved_post;
    wp_set_current_user($saved_user);

    return $answer;
}

$on_post = bhp_ab_surface_answer(['post_type' => 'post']);
bhp_ab_assert(
    true === $on_post,
    'the popup RENDERS on a single blog post — 1.19.241 flagged that blog traffic had lost the offer, and this is that flag discharged'
    . (null === $on_post ? ' [SKIPPED: no published post on this environment]' : ''),
    $failures
);

$on_product = bhp_ab_surface_answer(['post_type' => 'product']);
bhp_ab_assert(
    false === $on_product,
    'the popup DOES NOT render on a product page — no capture overlay over somebody reading a price'
    . (null === $on_product ? ' [SKIPPED: no published product on this environment]' : ''),
    $failures
);

$on_page = bhp_ab_surface_answer(['post_type' => 'page', 'post__not_in' => [(int) get_option('page_on_front')]]);
bhp_ab_assert(
    false === $on_page,
    'the popup DOES NOT render on an ordinary page — the widening is blog posts only, not "everything except products"'
    . (null === $on_page ? ' [SKIPPED: no non-front page on this environment]' : ''),
    $failures
);

/* The homepage case is asserted from the rule itself: `is_front_page()`
 * depends on `show_on_front` / `page_on_front` options rather than on the
 * queried object alone, so a synthesised query is not a faithful stand-in.
 * The live homepage render is verified in the browser QA instead. */
/*
 * ⭐ UPDATED 1.19.296 — THE SURFACE IS NOW THREE THINGS, NOT TWO.
 *    `/complete-collection/` joins the front page and single posts.
 *
 * ⭐ WHY: it is the **#1 human entry page on the site** (134 entries/30d,
 *    production access logs) and it carried the HARDEST gate we run — it fell
 *    through to exit-intent, whose mobile trigger is 20s dwell AND 45% scroll
 *    AND a 400px up-flick inside 600ms. Authorised by Andrew Signore, carrier
 *    item 280 (2026-08-27), naming the placement flip on the top entry page.
 *    ⚠ RELAYED through the Chief of Staff, not witnessed by this suite.
 *
 * ⛔ THE EXCLUSIONS THAT MUST NOT DRIFT ARE ASSERTED SEPARATELY BELOW, so
 *    widening this rule cannot quietly become "everything".
 */
bhp_ab_assert(
    1 === preg_match('/\$bhp_ab_popup_surface = is_front_page\(\)\s*\|\| is_singular\(\x27post\x27\)\s*\|\| is_page\(\x27complete-collection\x27\);/', $fn),
    'the surface rule admits exactly three things: the front page, a single post, and the #1 entry page',
    $failures
);

/* ⛔ AND IT ADMITS NOTHING ELSE. The Kit landing page keeps its deliberate
 *    suppression (it IS this offer's destination), and no product/shop surface
 *    is named. The pipe diagnosis's FIX-4 proposes reopening the Kit page and
 *    is explicitly Andrew's decision, not this build's. */
bhp_ab_assert(
    false === strpos($fn, "is_page('reluctant-reader-adventure-kit')")
        && false === strpos($fn, "is_page('shop')"),
    '⛔ the surface rule did not quietly widen to the Kit landing page or /shop/',
    $failures
);

/* =====================================================================
 * 7. THE DELIVERY MECHANISM IS UNCHANGED
 *
 * ⛔ THE ASSERTIONS THAT WOULD CATCH "SIMPLIFIED THE POPUP AND BROKE THE
 *    FUNNEL". Nothing about the offer, the endpoint, the thank-you page or
 *    the Lead event was in scope for this release, so all of it must be
 *    exactly what it was.
 * ================================================================== */

bhp_ab_assert(
    1 === preg_match("/'thankYouPath'\s*=>\s*'adventure-kit-thank-you'/", $tpl) &&
    1 === preg_match("/'lead_magnet'\s*=>\s*'reluctant_reader_adventure_kit'/", $tpl) &&
    1 === preg_match("/'success_redirect_key'\s*=>\s*'adventure_kit_thank_you'/", $tpl),
    'the popup still delivers the existing, proven Reluctant Reader Kit through the existing redirect key',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/name="lead_magnet" value="reluctant_reader_adventure_kit"/', $rendered) &&
    1 === preg_match('/name="audience_type" value="parents_families"/', $rendered),
    'the rendered form posts the same offer and audience it always did',
    $failures
);

$tags = apply_filters(
    'bhp_mailchimp_signup_tags',
    [],
    'parent_popup_ab',
    'parents_families',
    'reluctant_reader_adventure_kit',
    home_url('/')
);
bhp_ab_assert(
    in_array('Reluctant Reader Adventure Kit', (array) $tags, true) &&
    in_array('Audience: Parent/Grandparent', (array) $tags, true) &&
    in_array('Source: Parent Popup A/B', (array) $tags, true),
    'the Mailchimp tag set for this surface is unchanged — the segment is not split by turning the experiment off',
    $failures
);

bhp_ab_assert(
    0 === count(preg_grep('/^Variant: /', (array) $tags)),
    'no variant tag is applied now that no variant is assigned — never a guessed one',
    $failures
);

// Every other context is byte-untouched.
$tags = apply_filters('bhp_mailchimp_signup_tags', [], 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url('/'));
bhp_ab_assert(
    in_array('Source: Parent Popup', (array) $tags, true) &&
    0 === count(preg_grep('/^Variant: /', (array) $tags)),
    'the pre-existing parent-popup tag set is unchanged',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'parent_popup_success'\s*=>\s*array\(\s*'Lead'/", $px),
    'parent_popup_success still maps to Lead — the popup reuses the existing Meta Lead mechanism',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/if \( payload && payload\.content_name \) \{ return String\( payload\.content_name \); \}/', $px),
    'the Meta pixel Lead bridge still prefers an explicit content_name, and falls back to its own per-funnel name when none rides along',
    $failures
);

// ⛔ AND THE SHARED FORM IS UNCHANGED FOR EVERY OTHER CALLER.
ob_start();
get_template_part('template-parts/acquisition/signup-form', null, ['id' => 'bhp-submitclass-regression-probe']);
$probe = (string) ob_get_clean();
bhp_ab_assert(
    1 === substr_count($probe, 'class="btn btn-primary acquisition-form__submit"'),
    'a caller that passes no submit_class renders the unmodified class list, byte for byte',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/class="btn btn-primary acquisition-form__submit btn-cta-primary"/', $rendered),
    'the popup submit wears the established site CTA class rather than a copy of its colours',
    $failures
);

// The quiz is still retired from the auto-open slot and still reachable.
bhp_ab_assert(
    false === apply_filters('bhp_show_quiz_autoopen', true),
    'the quiz AUTO-OPEN is still retired',
    $failures
);

bhp_ab_assert(
    (bool) apply_filters('bhp_show_quiz_cta', true) === true &&
    false === has_filter('bhp_show_quiz_cta', '__return_false'),
    'the quiz launcher is NOT retired — the quiz stays reachable as a page and a link',
    $failures
);

/* =====================================================================
 * 8. THE STYLESHEET
 * ================================================================== */

/*
 * ⭐ UPDATED 1.19.298 (`CYCLE167-LD-POPUP-PHOTO`) — THE MATCH IS NO LONGER
 *    EXACT-STRING, AND THE RELAXATION IS DELIBERATE AND BOUNDED.
 *
 * ⛔ IT USED TO ASSERT THE CLOSING QUOTE: `class="mariana-popup
 *    mariana-popup--ab"`. 1.19.298 appends a SECOND modifier — the one that
 *    gates the founder photograph on all four image files being present — so
 *    the attribute now legitimately ends differently, and the old form would
 *    have failed for the right reason at the wrong time.
 *
 * ⭐ WHAT THE ASSERTION IS ACTUALLY FOR IS UNCHANGED AND IS STILL ENFORCED:
 *    this popup carries a modifier of its own, so its styling is scoped and
 *    cannot reach the teacher popup, the timed parent popup or the
 *    exit-intent modal. Dropping the trailing quote weakens nothing about
 *    that — the two class names and their order are still matched exactly.
 */
bhp_ab_assert(
    false !== strpos($rendered, 'class="mariana-popup mariana-popup--ab'),
    'the popup carries its own modifier class — the styling is scoped and cannot reach the teacher or exit-intent popups',
    $failures
);

/* ⚠ THE BLOCK IS BOUNDED AT BOTH ENDS. `strstr()` alone returns everything
 *   from the marker to the END OF THE STYLESHEET — roughly two thousand lines
 *   of unrelated rules — which would make the scoping count below answer a
 *   question about the whole file rather than about this block. The 1.19.204
 *   suite had that defect and passed anyway, by luck. */
$css   = (string) file_get_contents($theme_dir . '/style.css');
$block = (string) strstr($css, 'PARENT CAPTURE POPUP — BARE BONES');
$next  = strpos($block, '1.19.210 (2026-08-09, CYCLE148-LD-02/03)');
if (false !== $next) {
    $block = substr($block, 0, $next);
}

bhp_ab_assert(
    false !== $next,
    'the popup style block is bounded by the next section marker, so the assertions below read this block and nothing else',
    $failures
);

bhp_ab_assert(
    '' !== $block && substr_count($block, '.mariana-popup--ab') >= 20,
    'the popup style block exists in style.css',
    $failures
);

/* ⛔ EVERY reference to a class of the SHARED popup block, inside this block,
 *    must be preceded by the `--ab` scope. One unscoped rule here would
 *    restyle the teacher popup, the timed parent popup and the exit-intent
 *    modal — three surfaces this brief does not touch — and nothing else in
 *    the suite would catch it. The count includes prose, which is why the
 *    block's own comments describe selectors rather than quoting them. */
bhp_ab_assert(
    substr_count($block, '.mariana-popup__') === substr_count($block, '.mariana-popup--ab .mariana-popup__'),
    'every shared popup class in the block is scoped by .mariana-popup--ab',
    $failures
);

/* ⛔ THE 44px CLOSE CONTROL. The shared rule is 36x36 above the phone
 *    breakpoint. That was survivable while a large "No thanks" button offered
 *    a second way out; this release deleted it, so the × is the whole exit. */
bhp_ab_assert(
    1 === preg_match('/\.mariana-popup--ab \.mariana-popup__close \{\s*width: 44px;\s*height: 44px;\s*\}/', $block),
    'the close control takes the 44px minimum tap target at every width, scoped to this popup',
    $failures
);

/* The deleted elements must not keep dead rules describing a popup that no
 * longer exists. */
/*
 * ⭐ UPDATED 1.19.296 — `popup-ab__free` IS NO LONGER A DEAD ELEMENT AND HAS
 *    BEEN REMOVED FROM THIS LIST.
 *
 * ⛔ IT WAS NEVER SUPPOSED TO BE ON IT. The 1.19.267 pass deleted the rule
 *    along with the A/B markup and recorded it here as "deleted" — but the
 *    element it styled carries a STANDING FOUNDER INSTRUCTION (1.19.207: FREE
 *    all caps, bold, larger) that no experiment switch was ever authorised to
 *    revoke. This assertion then held the regression in place: any attempt to
 *    restore his emphasis would have failed the suite and looked like a defect.
 *
 * ⭐ THE OTHER THREE ARE GENUINELY DELETED and stay asserted — the three-book
 *    cover strip, the "what's inside" trio and its tick. Those really were
 *    subtraction he asked for, and dead rules for them would misdescribe the
 *    popup to the next reader.
 */
foreach (['popup-ab__covers', 'popup-ab__inside', 'popup-ab__check'] as $dead) {
    bhp_ab_assert(
        1 >= substr_count($block, $dead),
        "no live rule survives for the deleted `{$dead}` element (only the note recording its removal)",
        $failures
    );
}

/* ⭐ 1.19.296 — the positive assertion that replaces it: the emphasis rule must
 *    EXIST, scoped to this popup, so the founder's instruction is enforced by
 *    the suite rather than merely permitted by it. */
bhp_ab_assert(
    false !== strpos($block, '.mariana-popup--ab .mariana-popup__dialog h2 .popup-ab__free'),
    '⭐ the FREE emphasis rule exists and is scoped to this popup',
    $failures
);

bhp_ab_assert(
    false !== strpos($block, '.mariana-popup--ab .popup-ab__kit-cover img'),
    'the one kit-cover image has a rule of its own',
    $failures
);

echo "\n";
if ($failures > 0) {
    echo "RESULT: {$failures} failure(s)\n";
    exit(1);
}
echo "RESULT: all assertions passed\n";
