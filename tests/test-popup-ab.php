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
 * 1. THE TRIGGER — ENGAGEMENT **AND** TIME
 * ================================================================== */

bhp_ab_assert(
    2 === substr_count($tpl, "'minDelay' => 15000"),
    "the dwell floor `'minDelay' => 15000` appears exactly twice, once per device (Andrew Signore: \"Make it 15 second delay\")",
    $failures
);

// Nothing may open sooner by another number.
bhp_ab_assert(
    0 === preg_match("/'minDelay'\s*=>\s*(?!15000)\d+/", $tpl),
    'no other dwell-floor value exists anywhere in the template',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'mode'\s*=>\s*'gated'/", $tpl) &&
    0 === preg_match("/'mode'\s*=>\s*'(simple|exit)'/", $tpl),
    "trigger mode is 'gated' — the scroll condition is inert until the floor elapses",
    $failures
);

/* ⛔ THE ASSERTION THIS WHOLE SECTION EXISTS FOR. Andrew Signore, 2026-08-19:
 *    "wait for engagement and time." The engine's `fallbackDelay` calls
 *    `trigger()` DIRECTLY, with no scroll test — it is a pure timer wearing a
 *    gated mode's clothes. Declaring one here would restore the exact
 *    time-only path the ruling removed, and no other assertion in this file
 *    would notice. */
/* ⚠ THE QUOTED KEY, NOT THE BARE WORD. The template's own docblock explains
 *   at length why there is no fallback, so it names the key twice in prose. A
 *   bare-substring test would trip on the explanation of the rule it is
 *   testing — which is the exact defect this file's neighbours have been
 *   caught by twice. Only a PHP array key can actually configure anything. */
bhp_ab_assert(
    false === strpos($tpl, "'fallbackDelay'"),
    'NO ungated fallback timer is configured — time alone can never open the popup',
    $failures
);

bhp_ab_assert(
    2 === preg_match_all("/'scrollPct'\s*=>\s*\d+/", $tpl),
    'an engagement threshold is configured for both devices',
    $failures
);

// The old pure-timer key must not survive anywhere: `simple` mode reads
// `delay` and would race the floor.
bhp_ab_assert(
    0 === preg_match("/'delay'\s*=>/", $tpl),
    "the pure-timer `'delay'` key is gone — mode 'simple' cannot be reached by a leftover literal",
    $failures
);

/* NEVER ON FIRST PAINT — asserted in the ENGINE, which is where it is
 * actually decided. In gated mode `minTimeElapsed` starts false and
 * `onScroll()` returns early while it is false, so no scroll event during the
 * first fifteen seconds can open anything. */
bhp_ab_assert(
    1 === preg_match('/var minTimeElapsed = \(mode === \x27simple\x27\);/', $js),
    'the engine starts gated mode with the dwell gate CLOSED (minTimeElapsed is true only in simple mode)',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/function onScroll\(\)\s*\{\s*if \(!minTimeElapsed \|\| typeof scrollPct !== \x27number\x27\) \{\s*return;/', $js),
    'the engine\'s scroll handler returns early until the dwell floor has elapsed — engagement cannot fire before time',
    $failures
);

/* =====================================================================
 * 2. THE COPY INVENTORY — THE "EXACTLY" TEST
 * ================================================================== */

$headline = 'Free 20 Minute Reluctant Reader Kit';

/* ⚠ Counted in the RENDERED markup, not in the template. The template also
 *   carries the founder's verbatim instruction in its docblock, and that
 *   quotation must not be edited to satisfy a string count — quoting him
 *   accurately is the reason the docblock exists. */
bhp_ab_assert(
    1 === substr_count($tpl, "esc_html_e('" . $headline . "', 'brave-hearts')"),
    'the template emits the founder\'s headline character for character (open compound "20 Minute", not "20-Minute")',
    $failures
);

ob_start();
get_template_part('template-parts/acquisition/parent-ab-popup');
$rendered = (string) ob_get_clean();

bhp_ab_assert('' !== trim($rendered), 'the template renders', $failures);

bhp_ab_assert(
    1 === substr_count($rendered, $headline),
    'the headline appears in the rendered dialog exactly once — it is the only sentence, not a repeated one',
    $failures
);

bhp_ab_assert(
    1 === substr_count($rendered, '<h2 id="parent-ab-popup-title">' . $headline . '</h2>'),
    'the rendered dialog carries exactly one h2, and it is the founder\'s headline',
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

bhp_ab_assert(
    1 === substr_count($body, '<p '),
    'exactly ONE paragraph renders in the dialog — the privacy line, and nothing else',
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

bhp_ab_assert(
    1 === substr_count($rendered, '>Join the Adventure</button>'),
    'the CTA reads "Join the Adventure", exactly once',
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

bhp_ab_assert(
    1 === substr_count($rendered, '<img'),
    'exactly ONE image renders in the dialog (found ' . substr_count($rendered, '<img') . ')',
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

bhp_ab_assert(
    1 === preg_match('/<img[^>]*width="173"[^>]*height="224"/s', $rendered),
    'the image carries its intrinsic dimensions, so the dialog cannot reflow as it loads',
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
bhp_ab_assert(
    1 === preg_match('/if \(!is_front_page\(\) && !is_singular\(\x27post\x27\)\) \{\s*return false;/', $fn),
    'the surface rule admits exactly two things: the front page and a single post',
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

bhp_ab_assert(
    false !== strpos($rendered, 'class="mariana-popup mariana-popup--ab"'),
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
foreach (['popup-ab__covers', 'popup-ab__inside', 'popup-ab__check', 'popup-ab__free'] as $dead) {
    bhp_ab_assert(
        1 >= substr_count($block, $dead),
        "no live rule survives for the deleted `{$dead}` element (only the note recording its removal)",
        $failures
    );
}

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
