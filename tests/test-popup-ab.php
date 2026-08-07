<?php
/**
 * Brave Hearts — the parent-funnel A/B capture popup (theme 1.19.204).
 *
 * WHAT THIS SUITE GUARDS, and why each guard exists:
 *
 *   1. THE 15-SECOND DELAY. Andrew Signore, current turn: "Make it 15 second
 *      delay." The literal is counted, per device, in the template — the same
 *      technique `test-wave1-capture.php` uses for the 20-second dwell floor,
 *      because a silent edit to a number is exactly the change no reviewer
 *      notices.
 *   2. THE LOCKED COPY. Both headings, both subheads and both `content_name`
 *      values are asserted character-for-character. An A/B test whose copy
 *      drifts measures nothing.
 *   3. CACHE SAFETY. The rendered markup must contain BOTH variants and must
 *      NOT contain the assigned one. If the server ever picks, a full-page
 *      cache pins one variant to one URL and the experiment is void.
 *   4. FUNNEL ISOLATION. Parent storage/event prefixes only; no
 *      `bhp_mariana_popup` key anywhere; never on /teachers/.
 *   5. THE PIXEL JOIN. The Meta bridge must prefer an explicit
 *      `content_name`, or the two variants land in Meta under one name.
 *   6. THE QUIZ IS RETIRED FROM THE AUTO-OPEN SLOT BUT STILL REACHABLE.
 *   7. (1.19.205) THE VISUAL POLISH, guarded as STRUCTURE — scope, laziness,
 *      decorative marking, surviving labels, and the fact that the cover strip
 *      and the "what's inside" trio sit outside both variant blocks so A and B
 *      cannot drift apart visually. A suite cannot assert taste; it can assert
 *      the five things whose silent removal would undo the pass.
 *
 * ⚠ This is a source- and render-level suite, not a browser. It cannot prove
 *   the popup actually appeared 15 seconds after page load — that was
 *   measured separately in a real browser and recorded in the release notes.
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

/* ---------------------------------------------------------------------
 * 1. THE 15-SECOND DELAY
 * ------------------------------------------------------------------ */

bhp_ab_assert(
    2 === substr_count($tpl, "'delay' => 15000"),
    "the delay literal `'delay' => 15000` appears exactly twice, once per device (Andrew Signore: \"Make it 15 second delay\")",
    $failures
);

bhp_ab_assert(
    0 === preg_match("/'delay'\s*=>\s*(?!15000)\d+/", $tpl),
    'no other delay value exists anywhere in the template',
    $failures
);

// A timer, and only a timer. A scroll or fallback trigger would let the
// popup open before 15 seconds by another path.
bhp_ab_assert(
    false === strpos($tpl, 'scrollPct') && false === strpos($tpl, 'fallbackDelay') && false === strpos($tpl, 'minDelay'),
    'no scroll, fallback or dwell-floor trigger is configured — the delay is the only path to an automatic open',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'mode'\s*=>\s*'simple'/", $tpl),
    "trigger mode is 'simple' (pure timer)",
    $failures
);

/* ---------------------------------------------------------------------
 * 2. THE LOCKED COPY AND THE LOCKED content_name VALUES
 * ------------------------------------------------------------------ */

$variants = function_exists('bhp_get_popup_ab_variants') ? bhp_get_popup_ab_variants() : [];

bhp_ab_assert(
    ['A', 'B'] === array_keys($variants),
    'bhp_get_popup_ab_variants() declares exactly two variants, A and B',
    $failures
);

$expected = [
    'A' => [
        'heading'      => "It's Heartbreaking to Watch Them Fall Further Behind",
        'sub'          => 'You can still change this. Get the Free 20-Minute Reluctant Reader Kit.',
        'content_name' => 'popup_hook_heartbreak',
    ],
    'B' => [
        'heading'      => 'Turn Reluctant Readers Into Willing Readers',
        'sub'          => 'The Free 20-Minute Reluctant Reader Kit shows you exactly where to start.',
        'content_name' => 'popup_hook_willing',
    ],
];

foreach ($expected as $key => $fields) {
    foreach ($fields as $field => $value) {
        bhp_ab_assert(
            isset($variants[$key][$field]) && $variants[$key][$field] === $value,
            "variant {$key} {$field} is locked approved copy, character for character",
            $failures
        );
    }
}

/* ---------------------------------------------------------------------
 * 3. CACHE SAFETY — the server renders both variants and picks neither
 * ------------------------------------------------------------------ */

$rendered = '';
if (function_exists('bhp_get_popup_ab_variants')) {
    ob_start();
    get_template_part('template-parts/acquisition/parent-ab-popup');
    $rendered = (string) ob_get_clean();
}

bhp_ab_assert('' !== trim($rendered), 'the template renders', $failures);

foreach ($expected as $key => $fields) {
    bhp_ab_assert(
        false !== strpos($rendered, esc_html($fields['heading'])),
        "rendered markup contains variant {$key}'s heading — BOTH variants ship in every response",
        $failures
    );
    bhp_ab_assert(
        1 === substr_count($rendered, 'data-bhp-variant="' . $key . '"'),
        "rendered markup carries exactly one data-bhp-variant=\"{$key}\" block",
        $failures
    );
}

bhp_ab_assert(
    false === strpos($rendered, 'data-bhp-variant-assigned'),
    'the SERVER never stamps an assignment — a cached page cannot pin a variant',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/name="bhp_variant"\s+value=""/', $rendered),
    'the bhp_variant hidden field ships EMPTY and is stamped in the browser',
    $failures
);

bhp_ab_assert(
    false !== strpos($rendered, 'popup_hook_heartbreak') && false !== strpos($rendered, 'popup_hook_willing'),
    'both content_name values reach the browser in the config (the pixel needs them client-side)',
    $failures
);

// The two ids that label the dialog must be distinct, or the markup is
// invalid for the moments before the losing block is removed.
bhp_ab_assert(
    1 === substr_count($rendered, 'id="parent-ab-popup-title-a"') &&
    1 === substr_count($rendered, 'id="parent-ab-popup-title-b"'),
    'each variant heading carries its own unique id (no duplicate-id markup)',
    $failures
);

/* ---------------------------------------------------------------------
 * 4. FUNNEL ISOLATION
 * ------------------------------------------------------------------ */

bhp_ab_assert(
    false === strpos($tpl, 'bhp_mariana_popup') && false === strpos($tpl, 'mariana-guide-thank-you'),
    'the template touches no teacher-funnel storage key or thank-you path',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'storagePrefix'\s*=>\s*'bhp_parent_popup'/", $tpl) &&
    1 === preg_match("/'eventPrefix'\s*=>\s*'parent_popup'/", $tpl),
    'the popup uses the parent funnel\'s existing storage and event prefixes — same funnel, same suppression',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'thankYouPath'\s*=>\s*'adventure-kit-thank-you'/", $tpl) &&
    1 === preg_match("/'lead_magnet'\s*=>\s*'reluctant_reader_adventure_kit'/", $tpl),
    'the popup delivers the existing, proven Reluctant Reader Kit — no new subscription mechanism',
    $failures
);

// The /teachers/ exclusion is enforced server-side, not by CSS or JS.
$fn = (string) file_get_contents($theme_dir . '/functions.php');
bhp_ab_assert(
    1 === preg_match('/function bhp_should_show_parent_ab_popup\(\)\s*\{.*?is_page\(\x27teachers\x27\).*?\}/s', $fn),
    'bhp_should_show_parent_ab_popup() excludes /teachers/ server-side',
    $failures
);

bhp_ab_assert(
    function_exists('bhp_should_show_parent_ab_popup'),
    'bhp_should_show_parent_ab_popup() is defined',
    $failures
);

/* ---------------------------------------------------------------------
 * 5. THE MAILCHIMP VARIANT TAG AND THE PIXEL JOIN
 * ------------------------------------------------------------------ */

foreach (['A' => 'popup_hook_heartbreak', 'B' => 'popup_hook_willing'] as $key => $content_name) {
    $_POST['bhp_variant'] = $key;
    $tags = apply_filters(
        'bhp_mailchimp_signup_tags',
        [],
        'parent_popup_ab',
        'parents_families',
        'reluctant_reader_adventure_kit',
        home_url('/')
    );
    bhp_ab_assert(
        in_array('Variant: ' . $content_name, (array) $tags, true),
        "variant {$key} produces the Mailchimp tag 'Variant: {$content_name}' — the same string the pixel sends",
        $failures
    );
    bhp_ab_assert(
        in_array('Reluctant Reader Adventure Kit', (array) $tags, true) &&
        in_array('Audience: Parent/Grandparent', (array) $tags, true) &&
        in_array('Source: Parent Popup A/B', (array) $tags, true),
        "variant {$key} keeps the resource, audience and source tags of the proven parent funnel",
        $failures
    );
}
unset($_POST['bhp_variant']);

// An unknown key must yield NO variant tag rather than a wrong one.
$_POST['bhp_variant'] = 'Z';
$tags = apply_filters('bhp_mailchimp_signup_tags', [], 'parent_popup_ab', 'parents_families', 'reluctant_reader_adventure_kit', home_url('/'));
bhp_ab_assert(
    0 === count(preg_grep('/^Variant: /', (array) $tags)),
    'an unrecognised variant key produces no variant tag at all — never a guessed one',
    $failures
);
unset($_POST['bhp_variant']);

// Every other context is byte-untouched by the new filter.
$tags = apply_filters('bhp_mailchimp_signup_tags', [], 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url('/'));
bhp_ab_assert(
    in_array('Source: Parent Popup', (array) $tags, true) &&
    0 === count(preg_grep('/^Variant: /', (array) $tags)),
    'the pre-existing parent-popup tag set is unchanged',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/if \( payload && payload\.content_name \) \{ return String\( payload\.content_name \); \}/', $px),
    'the Meta pixel Lead bridge prefers an explicit payload content_name',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'parent_popup_success'\s*=>\s*array\(\s*'Lead'/", $px),
    'parent_popup_success still maps to Lead — the popup reuses the existing Lead mechanism',
    $failures
);

/* ---------------------------------------------------------------------
 * 6. THE ENGINE EXTENSION IS ADDITIVE, AND THE QUIZ STILL EXISTS
 * ------------------------------------------------------------------ */

bhp_ab_assert(
    1 === preg_match('/function parseAbTest\(/', $js) && 1 === preg_match('/function assignVariant\(/', $js),
    'the engine gained abTest support rather than a second engine file',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/abTest:\s*null/', $js),
    'abTest defaults to null — a popup that declares none behaves exactly as it did in 1.19.203',
    $failures
);

// Cookie, not localStorage/sessionStorage. The assignment must survive a new
// session, and it must not sit under a funnel suppression prefix.
bhp_ab_assert(
    false !== strpos($js, 'readCookie(ab.cookie)') && false !== strpos($js, 'writeCookie(ab.cookie'),
    'variant assignment is read from and written to a cookie, not localStorage or sessionStorage',
    $failures
);

bhp_ab_assert(
    1 === preg_match("/'cookie'\s*=>\s*BHP_POPUP_AB_COOKIE/", $tpl) && defined('BHP_POPUP_AB_COOKIE') && BHP_POPUP_AB_COOKIE === 'bhp_popup_ab',
    'the A/B cookie name is bhp_popup_ab and is declared in exactly one place',
    $failures
);

// Assignment must happen before anything can paint.
bhp_ab_assert(
    strpos($js, 'A/B ASSIGNMENT (1.19.204)') !== false &&
    strpos($js, 'A/B ASSIGNMENT (1.19.204)') < strpos($js, 'function show()'),
    'assignment runs at init, before show() is even defined — no variant flicker by construction',
    $failures
);

bhp_ab_assert(
    function_exists('bhp_should_show_quiz_cta') && false !== has_filter('bhp_show_quiz_autoopen', '__return_false'),
    'the quiz AUTO-OPEN is retired',
    $failures
);

bhp_ab_assert(
    (bool) apply_filters('bhp_show_quiz_cta', true) === true &&
    false === has_filter('bhp_show_quiz_cta', '__return_false'),
    'the quiz launcher is NOT retired — the quiz stays reachable as a page and a link',
    $failures
);

bhp_ab_assert(
    false === apply_filters('bhp_show_quiz_autoopen', true),
    'bhp_show_quiz_autoopen resolves to false',
    $failures
);

/* ---------------------------------------------------------------------
 * 7. THE 1.19.205 VISUAL POLISH — GUARDED AS STRUCTURE, NOT AS TASTE
 *
 * A suite cannot assert that something is pleasing. It can assert the four
 * things that would silently undo this pass:
 *
 *   a. the popup still carries its OWN modifier class, so the polish stays
 *      scoped and the teacher/exit-intent/legacy popups keep their styling;
 *   b. the cover strip is DECORATIVE and LAZY — the release's zero-LCP
 *      property depends on the second of those, and the first is why three
 *      book titles are not read out before the headline;
 *   c. the labels are still in the DOM. They are hidden with CSS; a future
 *      "cleanup" that deletes them would break the form for a screen reader
 *      and nothing else would notice;
 *   d. the trio and the cover strip sit OUTSIDE the variant blocks, so the
 *      engine's removal of the losing variant cannot take them with it and
 *      A and B cannot drift apart visually.
 * ------------------------------------------------------------------ */

bhp_ab_assert(
    false !== strpos($rendered, 'class="mariana-popup mariana-popup--ab"'),
    'the popup carries its own modifier class — the polish is scoped and cannot reach the teacher or exit-intent popups',
    $failures
);

$css    = (string) file_get_contents($theme_dir . '/style.css');
$polish = (string) strstr($css, 'PARENT A/B CAPTURE POPUP — VISUAL POLISH');

bhp_ab_assert(
    '' !== $polish && substr_count($polish, '.mariana-popup--ab') >= 20,
    'the polish block exists in style.css',
    $failures
);

// ⛔ EVERY reference to a SHARED `.mariana-popup__*` class inside the polish
//    block must be preceded by the `--ab` scope. One unscoped rule here would
//    restyle the teacher popup, the timed parent popup and the exit-intent
//    modal — three surfaces this brief does not touch — and nothing else in
//    the suite would catch it.
bhp_ab_assert(
    substr_count($polish, '.mariana-popup__') === substr_count($polish, '.mariana-popup--ab .mariana-popup__'),
    'every shared .mariana-popup__* class in the polish block is scoped by .mariana-popup--ab',
    $failures
);

bhp_ab_assert(
    function_exists('bhp_get_popup_ab_covers'),
    'bhp_get_popup_ab_covers() is defined',
    $failures
);

$covers = function_exists('bhp_get_popup_ab_covers') ? bhp_get_popup_ab_covers() : [];
bhp_ab_assert(
    is_array($covers) && count($covers) === 3,
    'the cover strip resolves to exactly three existing attachments (found ' . count((array) $covers) . ')',
    $failures
);

$strip = '';
if (preg_match('/<div class="popup-ab__covers"[^>]*>(.*?)<\/div>/s', $rendered, $mm)) {
    $strip = $mm[1];
}

bhp_ab_assert(
    1 === substr_count($rendered, 'class="popup-ab__covers"') &&
    3 === substr_count($strip, 'class="popup-ab__cover"'),
    'the rendered strip contains exactly three cover images in one wrapper',
    $failures
);

// ⛔ THE ZERO-LCP GUARD. The popup is display:none until the timer fires, so a
//    LAZY image inside it is never fetched during page load. An eager one
//    would be — and would put three product covers on the critical path of
//    every page on the site.
bhp_ab_assert(
    3 === substr_count($strip, 'loading="lazy"'),
    'all three cover images are lazy — no popup asset is fetched during page load',
    $failures
);

bhp_ab_assert(
    1 === preg_match('/<div class="popup-ab__covers" aria-hidden="true">/', $rendered),
    'the cover strip is hidden from assistive technology (decorative, and the dialog is already labelled)',
    $failures
);

bhp_ab_assert(
    3 === substr_count($strip, 'alt=""') && 0 === preg_match('/alt="[^"]+"/', $strip),
    'every cover image carries an empty alt — decorative, matching the aria-hidden wrapper',
    $failures
);

foreach (['20-minute activity', 'First chapter free', 'Parent quick-start'] as $item) {
    bhp_ab_assert(
        1 === substr_count($rendered, $item),
        "the \"what's inside\" trio renders \"{$item}\" exactly once",
        $failures
    );
}

// (d) — identical for both variants, by position in the markup.
bhp_ab_assert(
    strpos($rendered, 'popup-ab__covers') < strpos($rendered, 'data-bhp-variant="A"') &&
    strpos($rendered, 'popup-ab__inside') > strpos($rendered, 'data-bhp-variant="B"'),
    'the cover strip and the trio sit OUTSIDE both variant blocks — A and B cannot differ except in headline and subhead',
    $failures
);

// (c) — the labels are hidden by CSS, never deleted.
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

// The site's shipped CTA treatment, worn as a class rather than re-declared.
bhp_ab_assert(
    1 === preg_match('/class="btn btn-primary acquisition-form__submit btn-cta-primary"/', $rendered),
    'the submit wears the established site CTA class rather than a copy of its colours',
    $failures
);

// ⛔ AND THE SHARED FORM IS UNCHANGED FOR EVERY OTHER CALLER. `submit_class`
//    defaults to '', so a form that does not opt in emits exactly the class
//    list it emitted in 1.19.204.
ob_start();
get_template_part('template-parts/acquisition/signup-form', null, ['id' => 'bhp-submitclass-regression-probe']);
$probe = (string) ob_get_clean();
bhp_ab_assert(
    1 === substr_count($probe, 'class="btn btn-primary acquisition-form__submit"'),
    'a caller that passes no submit_class renders the 1.19.204 class list, byte for byte',
    $failures
);

echo "\n";
if ($failures > 0) {
    echo "RESULT: {$failures} failure(s)\n";
    exit(1);
}
echo "RESULT: all assertions passed\n";
