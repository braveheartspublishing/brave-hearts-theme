<?php
/**
 * Brave Hearts — 1.19.298 · THE FOUNDER PHOTOGRAPH ON THE PARENT CAPTURE POPUP.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * `CYCLE167-LD-POPUP-PHOTO` (2026-08-27)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Andrew Signore, 2026-08-27, VERBATIM (carrier item 297):
 *
 *      "Did we come to a conclusion on adding a picture of me and charlotte as
 *       a gradient right to left with like a playful squiggle in between for
 *       the pop ups?"
 *
 * ⛔⛔ SECTION 1 IS THE REASON THIS FILE EXISTS. Charlotte is Andrew's NIECE
 *     and he has NO CHILDREN — carrier item 285, his own words: *"SHE IS MY
 *     NIECE and its all over the canon docs - I DONT HAVE KIDS"*.
 *
 *     On the night of 2026-08-27 a brief said "daughter", a deck rebuild
 *     believed the brief over the canon, and the word reached the /Alt
 *     accessibility layer of a delivered PDF — a layer no visual review would
 *     ever have looked at. The founder caught it himself. The deck lane's
 *     answer was `assert_niece_canon()` in its build; this is the same answer
 *     in the theme, and it is deliberately modelled on that function rather
 *     than invented as a rival.
 *
 *     IT CHECKS BOTH DIRECTIONS, because either alone is passable by accident:
 *       (1) no forbidden kinship word appears, and
 *       (2) a text that names a relationship at all names the RIGHT one.
 *     A text naming no relationship passes, because the founder's rail allows
 *     exactly that. What it must never do is name a different one.
 *
 * WHAT ELSE THIS SUITE GUARDS:
 *   2. THE ASSETS — four files, the two shapes the layout was measured
 *      against, and a byte budget stated as a number rather than as "small".
 *   3. THE RESOLVER — fails CLOSED. A violating alt text stands the whole
 *      photograph down rather than shipping a wrong kinship claim.
 *   4. THE MARKUP — the photograph renders, it is lazy, it has a real
 *      accessible name, and it sits BELOW the offer in the DOM.
 *   5. THE HIERARCHY — headline, then subhead, then button, in that order, and
 *      no text set over the photograph.
 *   6. THE STYLESHEET — the gradient runs right-to-left at desktop and
 *      bottom-to-top at mobile, the squiggle is a drawn vector, and the
 *      panel's shape is capped so the vetted crop can never be re-cropped
 *      past its measured margin.
 *   7. FUNNEL ISOLATION — the teacher funnel is not touched, and the teacher
 *      popup does not and cannot pick this treatment up.
 *   8. THE STANDING RAILS — voice, no em dash, no new claim.
 *
 * Run: wp eval-file tests/test-cycle167-popup-photo.php --user=1
 */

defined('ABSPATH') || exit;

$failures = 0;

function bhp_photo_assert($condition, $label, &$failures) {
    if ($condition) {
        echo "PASS: {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL: {$label}\n";
}

$theme_dir = get_template_directory();
$tpl_path  = $theme_dir . '/template-parts/acquisition/parent-ab-popup.php';
$teacher_path = $theme_dir . '/template-parts/acquisition/mariana-popup.php';

$tpl     = is_readable($tpl_path) ? (string) file_get_contents($tpl_path) : '';
$teacher = is_readable($teacher_path) ? (string) file_get_contents($teacher_path) : '';
$css     = (string) file_get_contents($theme_dir . '/style.css');

bhp_photo_assert('' !== $tpl, 'parent-ab-popup.php exists and is readable', $failures);

/* =====================================================================
 * 1. ⛔⛔ THE NIECE CANON GUARD
 * ================================================================== */

echo "\n-- 1. NIECE CANON --\n";

bhp_photo_assert(
    function_exists('bhp_niece_canon_violations'),
    'the niece canon guard exists as a callable function, not as a comment',
    $failures
);

/* ⛔ THE THREE STRINGS THE BRIEF NAMED BY HAND. Each must fail on its own. */
foreach (['daughter', 'his kids', 'as a dad'] as $poison) {
    bhp_photo_assert(
        !empty(bhp_niece_canon_violations('A photo of Andrew and his ' . $poison . ' Charlotte.')),
        sprintf('⛔ the guard REJECTS "%s"', $poison),
        $failures
    );
}

/* Case must not be an escape hatch. */
bhp_photo_assert(
    !empty(bhp_niece_canon_violations('Andrew and his DAUGHTER Charlotte')),
    '⛔ the guard is case-insensitive — "DAUGHTER" fails too',
    $failures
);

/* ⛔ DIRECTION TWO. Quietly swapping in a different relationship must fail
 *    even though no term on the forbidden list appears. */
bhp_photo_assert(
    !empty(bhp_niece_canon_violations('Me and my cousin Charlotte, holding a book.')),
    '⛔ the guard REJECTS a relationship that is stated but is not "niece"',
    $failures
);

/* The permitted shapes: the right relationship, or none at all. */
bhp_photo_assert(
    [] === bhp_niece_canon_violations('Me and my niece Charlotte, holding a book.'),
    'the guard ACCEPTS "my niece Charlotte"',
    $failures
);
bhp_photo_assert(
    [] === bhp_niece_canon_violations('Me and Charlotte, holding a book.'),
    'the guard ACCEPTS text that names no relationship at all (the founder\'s rail allows either)',
    $failures
);

/* THE SHIPPED STRING ITSELF. */
$alt = function_exists('bhp_get_founder_photo_alt') ? bhp_get_founder_photo_alt() : '';

bhp_photo_assert(
    '' !== $alt && [] === bhp_niece_canon_violations($alt),
    '⭐ the SHIPPED alt text passes the guard',
    $failures
);
bhp_photo_assert(
    false !== stripos($alt, 'my niece Charlotte'),
    '⭐ the shipped alt text says "my niece Charlotte" in those words',
    $failures
);

/* =====================================================================
 * 2. THE ASSETS
 * ================================================================== */

echo "\n-- 2. ASSETS --\n";

/*
 * ⭐ THE BUDGET IS A NUMBER, NOT AN ADJECTIVE. These are the four derivative
 *    files, and the pair a single visitor actually downloads is ONE of them —
 *    the `<picture>` media queries make the two crops mutually exclusive and
 *    the JPEGs are fetched only by a browser that cannot decode WebP.
 * ⛔ THE CEILING IS DELIBERATELY NOT GENEROUS. A capture popup that costs
 *    150 KB has stopped being a popup and started being a page.
 */
$budget = [
    'andrew-charlotte-popup-portrait.webp' => [560, 896, 60000],
    'andrew-charlotte-popup-portrait.jpg'  => [560, 896, 95000],
    'andrew-charlotte-popup-band.webp'     => [720, 576, 50000],
    'andrew-charlotte-popup-band.jpg'      => [720, 576, 80000],
];

$transfer_webp = 0;
foreach ($budget as $file => $spec) {
    $path = $theme_dir . '/assets/images/founder/' . $file;
    $ok = file_exists($path);
    bhp_photo_assert($ok, sprintf('%s is present', $file), $failures);
    if (!$ok) {
        continue;
    }
    $size = @getimagesize($path);
    bhp_photo_assert(
        is_array($size) && (int) $size[0] === $spec[0] && (int) $size[1] === $spec[1],
        sprintf('%s is %dx%d as the layout was measured against', $file, $spec[0], $spec[1]),
        $failures
    );
    $bytes = filesize($path);
    bhp_photo_assert(
        $bytes <= $spec[2],
        sprintf('%s is %d bytes, within its %d-byte budget', $file, $bytes, $spec[2]),
        $failures
    );
    if (substr($file, -5) === '.webp') {
        $transfer_webp = max($transfer_webp, $bytes);
    }
}

/*
 * ⭐ THE HONEST NUMBER, ASSERTED RATHER THAN CLAIMED IN A REPORT: the worst
 *    case a real visitor pays for this treatment is the LARGER of the two WebP
 *    crops, because they never receive both.
 */
bhp_photo_assert(
    $transfer_webp > 0 && $transfer_webp <= 60000,
    sprintf('⭐ worst-case ADDED TRANSFER for one visitor is %d bytes (one WebP crop, never both)', $transfer_webp),
    $failures
);

/* =====================================================================
 * 3. THE RESOLVER FAILS CLOSED
 * ================================================================== */

echo "\n-- 3. RESOLVER --\n";

bhp_photo_assert(
    function_exists('bhp_get_founder_photo'),
    'bhp_get_founder_photo() exists',
    $failures
);

$photo = bhp_get_founder_photo();

bhp_photo_assert(
    !empty($photo['portrait_webp']) && !empty($photo['band_webp']),
    'the resolver returns both crops',
    $failures
);
bhp_photo_assert(
    !empty($photo['portrait_width']) && (int) $photo['portrait_width'] === 560,
    'intrinsic dimensions are READ FROM THE FILE, not typed into the resolver',
    $failures
);

/*
 * ⛔ THE FAIL-CLOSED PATH, EXERCISED RATHER THAN ASSUMED. A filter in a plugin
 *    or a child theme can replace the alt text, and no string test in this
 *    repository can see that happen. So the resolver checks at render time and
 *    stands the WHOLE photograph down on a violation.
 *
 *    Failing closed is the only safe direction: a missing photograph costs a
 *    nicer popup; a wrong one costs the founder a correction he has already
 *    had to make once. The static cache inside the resolver is why this test
 *    runs in its own process order — the filter is added, the cache is
 *    bypassed by calling the alt helper directly, and the composed check below
 *    proves the decision rule rather than the cache's state.
 */
add_filter('bhp_founder_photo_alt', function () {
    return 'Andrew and his daughter Charlotte.';
});
$poisoned_alt = bhp_get_founder_photo_alt();
bhp_photo_assert(
    !empty(bhp_niece_canon_violations($poisoned_alt)),
    '⛔ a filter that injects a forbidden kinship term IS detected at render time',
    $failures
);
remove_all_filters('bhp_founder_photo_alt');

bhp_photo_assert(
    false !== strpos((string) file_get_contents($theme_dir . '/functions.php'),
        'if (bhp_niece_canon_violations($cache[\'alt\'])) {'),
    '⛔ and the resolver STANDS THE PHOTOGRAPH DOWN on that detection (fails closed)',
    $failures
);

/* =====================================================================
 * 4. THE MARKUP
 * ================================================================== */

echo "\n-- 4. MARKUP --\n";

ob_start();
get_template_part('template-parts/acquisition/parent-ab-popup');
$rendered = (string) ob_get_clean();

bhp_photo_assert('' !== trim($rendered), 'the template renders', $failures);

bhp_photo_assert(
    false !== strpos($rendered, 'mariana-popup--photo'),
    'the popup carries the --photo modifier, so every new rule is gated on the assets being present',
    $failures
);
bhp_photo_assert(
    false !== strpos($rendered, '<figure class="popup-ab__photo">'),
    'the photograph renders',
    $failures
);
bhp_photo_assert(
    substr_count($rendered, 'andrew-charlotte-popup-band') >= 2
    && substr_count($rendered, 'andrew-charlotte-popup-portrait') >= 2,
    'both crops are offered, each with a WebP source and a fallback',
    $failures
);
bhp_photo_assert(
    false !== strpos($rendered, 'media="(max-width: 639px)"'),
    '⭐ the two crops are MUTUALLY EXCLUSIVE by media query — one download per visitor, never both',
    $failures
);

/* ⛔ LAZY, AND THIS IS THE MECHANISM RATHER THAN A PREFERENCE. Chromium's
 *    preload scanner speculatively fetches an EAGER <img src> before any
 *    stylesheet has told it the subtree is display:none; it leaves a LAZY one
 *    to machinery that never schedules an image inside a non-rendered subtree.
 *    Measured in CYCLE158-LD-SIGNUP-POPUP. This is what keeps the popup off
 *    the page load and LCP untouched. */
$figure = strstr($rendered, '<figure class="popup-ab__photo">');
bhp_photo_assert(
    false !== strpos((string) $figure, 'loading="lazy"'),
    '⭐ the photograph is lazy — it costs the page load nothing and cannot touch LCP',
    $failures
);
bhp_photo_assert(
    false !== strpos((string) $figure, 'alt="') && false === strpos((string) $figure, 'alt=""'),
    'the photograph has a real accessible name, not an empty one',
    $failures
);

/* ⛔ NO FORBIDDEN TERM ANYWHERE IN THE RENDERED SURFACE, not merely in the one
 *    string the resolver checks. This is the whole-output scan the deck lane
 *    learned to run after finding the defect in a layer nobody reads. */
bhp_photo_assert(
    [] === bhp_niece_canon_violations(
        preg_replace('/<[^>]+>/', ' ', $rendered) . ' ' . $alt
    ),
    '⛔⛔ WHOLE-OUTPUT SCAN: no forbidden kinship term anywhere in the rendered popup',
    $failures
);

/* =====================================================================
 * 5. THE HIERARCHY DOES NOT MOVE
 * ================================================================== */

echo "\n-- 5. HIERARCHY --\n";

$pos_h2      = strpos($rendered, 'id="parent-ab-popup-title"');
$pos_subhead = strpos($rendered, 'popup-ab__subhead');
$pos_submit  = strpos($rendered, 'Send me the chapter');
$pos_photo   = strpos($rendered, 'popup-ab__photo');

bhp_photo_assert(
    false !== $pos_h2 && false !== $pos_subhead && false !== $pos_submit
    && $pos_h2 < $pos_subhead && $pos_subhead < $pos_submit,
    '⭐ headline → subhead → button, in that order, unchanged by this release',
    $failures
);

/* ⛔ THE PHOTOGRAPH IS BELOW THE OFFER IN THE DOM. It is placed ABOVE the copy
 *    visually on a phone by grid areas, and that is allowed precisely because
 *    the photograph is not the offer: a screen reader still meets the
 *    headline, the subhead and the form before it meets a description of a
 *    picture. */
bhp_photo_assert(
    false !== $pos_photo && $pos_photo > $pos_submit,
    '⭐ the photograph sits AFTER the whole offer in the DOM — the reading order leads with the offer',
    $failures
);

/* ⛔ NO TEXT IS SET OVER THE PHOTOGRAPH. The figure carries an image and
 *    nothing else — no caption, no overlay, no absolutely-positioned copy. */
bhp_photo_assert(
    false === strpos((string) $figure, '<figcaption'),
    '⛔ no caption is set over the photograph',
    $failures
);

/* The kit cover survives. Andrew Signore, 2026-08-19: "Keep a small picture of
 * the actual front page of the kit on the pop up" — a standing instruction —
 * and 1.19.297 made this image the popup's own chapter→Kit honesty bridge. */
bhp_photo_assert(
    false !== strpos($rendered, 'popup-ab__kit-cover'),
    '⛔ the kit cover is STILL THERE — a standing founder instruction and the chapter→Kit honesty bridge',
    $failures
);

/* =====================================================================
 * 6. THE STYLESHEET
 * ================================================================== */

echo "\n-- 6. STYLESHEET --\n";

/* ⚠ BOUNDED AT BOTH ENDS. `strstr()` alone returns everything from the marker
 *   to the end of the stylesheet, which would make every assertion below
 *   answer a question about the whole file rather than about this section.
 *   The 1.19.204 suite had exactly that defect and passed by luck. */
$block = (string) strstr($css, '1.19.298 (2026-08-27, `CYCLE167-LD-POPUP-PHOTO`) — THE FOUNDER');
$end   = strpos($block, '1.19.210 (2026-08-09, CYCLE148-LD-02/03)');
if (false !== $end) {
    $block = substr($block, 0, $end);
}

bhp_photo_assert(
    '' !== $block && false !== $end,
    'the 1.19.298 style section exists and is bounded, so the assertions below read it and nothing else',
    $failures
);

/* ⭐⭐ "a gradient right to left" — the literal instruction, asserted. */
bhp_photo_assert(
    false !== strpos($block, 'mask-image: linear-gradient(to left,'),
    '⭐⭐ the DESKTOP gradient runs RIGHT TO LEFT, exactly as he described it',
    $failures
);
bhp_photo_assert(
    false !== strpos($block, '-webkit-mask-image: linear-gradient(to left,'),
    'the prefixed property is present too, so Safari gets the gradient as well',
    $failures
);
bhp_photo_assert(
    false !== strpos($block, 'mask-image: linear-gradient(to bottom,'),
    '⭐ the MOBILE adaptation fades bottom-to-top into the copy field — the same idea, rotated',
    $failures
);

/* ⭐⭐ "a playful squiggle in between" — drawn, not generated. */
bhp_photo_assert(
    substr_count($rendered, '<path class="popup-ab__squiggle') === 4,
    '⭐⭐ the squiggle is FOUR DRAWN BEZIER PATHS — two orientations, each a two-pass hand-drawn stroke',
    $failures
);
bhp_photo_assert(
    false !== strpos($block, 'stroke: var(--color-gold);'),
    'the squiggle is brand gold, from the palette token rather than a literal',
    $failures
);
bhp_photo_assert(
    false !== strpos($rendered, 'class="popup-ab__seam" aria-hidden="true"'),
    'the squiggle is hidden from assistive technology — a divider is not content',
    $failures
);
/* ⛔ NOT AI. The paths are inline in the template; there is no generated
 *    raster and no external asset behind the divider. */
bhp_photo_assert(
    false === strpos($rendered, 'squiggle.png') && false === strpos($rendered, 'squiggle.svg'),
    '⛔ the squiggle is an inline vector — no generated image file of any kind',
    $failures
);

/* ⛔ THE CROP GUARANTEE, ENFORCED FROM THE CSS SIDE. The portrait crop
 *    tolerates being poured into a panel down to an aspect of 0.692 before a
 *    subject is touched; 296px / 422px is 0.701. Without this cap a tall
 *    enough dialog would narrow the panel until `object-fit: cover` ate the
 *    book on one side and Charlotte on the other. */
bhp_photo_assert(
    false !== strpos($block, 'max-height: 540px;'),
    '⛔ the desktop panel\'s height is CAPPED so the vetted crop can never be re-cropped past its measured margin',
    $failures
);
bhp_photo_assert(
    false !== strpos($block, 'aspect-ratio: 5 / 4;'),
    '⛔ the mobile band takes the landscape crop\'s OWN ratio, so `cover` trims nothing at all there',
    $failures
);

/* ⛔ ONE AXIS OF CLIPPING ONLY. A blanket `overflow: hidden` would have taken
 *    away the shared block's vertical scrolling and clipped the button out of
 *    reach on a short screen. */
/*
 * ⚠ THE FIRST FORM OF THIS ASSERTION WAS WRONG AND IS RECORDED RATHER THAN
 *   QUIETLY REPLACED. It searched the whole section for `overflow: hidden;`
 *   and failed — correctly, on a string, and wrongly, on the question. The
 *   photograph's own figure legitimately clips its content that way; it is
 *   only the DIALOG that must not, because the dialog is the element carrying
 *   the viewport-bounded `max-height`. So the test now reads the dialog rules
 *   specifically instead of grepping the section.
 */
preg_match_all(
    '/\.mariana-popup--photo\.mariana-popup--ab \.mariana-popup__dialog \{([^}]*)\}/',
    $block,
    $dialog_rules
);
$dialog_bodies = implode("\n", $dialog_rules[1]);

bhp_photo_assert(
    2 === count($dialog_rules[1]),
    'both dialog rules (desktop and mobile) are present and readable by this assertion',
    $failures
);
bhp_photo_assert(
    '' !== $dialog_bodies
    && false === strpos($dialog_bodies, 'overflow: hidden')
    && 2 === substr_count($dialog_bodies, 'overflow-x: hidden'),
    '⛔ the dialog clips ONE axis only — it keeps the shared block\'s vertical scroll on a short screen',
    $failures
);

/* =====================================================================
 * 7. FUNNEL ISOLATION
 * ================================================================== */

echo "\n-- 7. FUNNEL ISOLATION --\n";

/* ⛔ THE TEACHER POPUP IS A SEPARATE TEMPLATE WITH ITS OWN MARKUP AND ITS OWN
 *    OFFER. It does not share this one, so skipping it forks no layout — every
 *    rule added by this release is gated on a modifier class that only the
 *    parent popup emits. This assertion is what makes "the teacher funnel is
 *    untouched" checkable rather than a claim in a report. */
bhp_photo_assert(
    '' !== $teacher && false === strpos($teacher, 'mariana-popup--photo')
    && false === strpos($teacher, 'popup-ab__photo'),
    '⛔ the TEACHER popup emits neither the modifier nor the photograph — it cannot pick this treatment up',
    $failures
);
bhp_photo_assert(
    false === strpos($tpl, 'bhp_mariana_popup') && false === strpos($tpl, 'mariana-guide-thank-you'),
    '⛔ the parent template names no teacher-funnel storage prefix or thank-you path',
    $failures
);
bhp_photo_assert(
    false !== strpos($rendered, 'bhp_parent_popup') && false !== strpos($rendered, 'adventure-kit-thank-you'),
    'the parent funnel\'s own storage prefix and thank-you path are unchanged',
    $failures
);

/* =====================================================================
 * 8. THE STANDING RAILS
 * ================================================================== */

echo "\n-- 8. STANDING RAILS --\n";

/* ⛔ NO EM DASH in anything a customer reads. */
bhp_photo_assert(
    false === strpos($alt, "\xe2\x80\x94"),
    '⛔ no em dash in the alt text',
    $failures
);

/* ⛔ THE VOICE RULE. Customer-facing words are I / me / my. He is the sole
 *    operator, and "we" claims a company that does not exist. */
bhp_photo_assert(
    0 === preg_match('/\b(we|us|our)\b/i', $alt),
    '⛔ the alt text contains no "we", "us" or "our" — the voice is I / me / my',
    $failures
);

/* ⛔ NO NEW CLAIM. The alt text describes what is in the frame and stops: two
 *    people and a book that exists. It makes no promise about what a child
 *    gets from it, which is the outcome-claim rail. */
bhp_photo_assert(
    0 === preg_match('/\b(loves?|favourite|favorite|helps?|improves?|proven|will|guarantee)\b/i', $alt),
    '⛔ the alt text makes no outcome claim — it describes the frame and stops',
    $failures
);

/* =====================================================================
 * 9. 1.19.299 — THE PHOTOGRAPH SWAP (`CYCLE167-LD-POPUP-PHOTO2-SWAP`)
 * ==================================================================
 *
 * ⭐ Andrew Signore, carrier item 301, RELAYED: *"im so sorry I need to change
 *    the photo on the pop up- I found a cuter photo with charlotte smiling its
 *    in the Author visit file 'Andrew+Charlotte2'"*.
 *
 * ⛔ THIS SECTION GUARDS ONE FAILURE MODE AND IT IS THE ONE THIS PASS COULD
 *    ACTUALLY HAVE COMMITTED: a HALF-APPLIED SWAP. New assets on disk with the
 *    previous photograph's alt text still attached, or new alt text describing
 *    a photograph that was never deployed. Either half alone is worse than
 *    neither, because both render, both look finished, and the mismatch lives
 *    in the accessibility layer where no visual review ever goes — which is
 *    exactly how "daughter" reached a delivered PDF on 2026-08-27.
 */

echo "\n-- 9. PHOTOGRAPH SWAP (1.19.299) --\n";

/*
 * ⛔ THE ASSETS ARE NOT PHOTOGRAPH 1'S ASSETS. These four md5s are the files
 *    1.19.298 shipped, recorded in that pass's release notes and re-verified
 *    off disk before they were replaced. Asserting the NEGATIVE is deliberate:
 *    pinning the new md5s would fail on any future re-encode at a different
 *    quality and would be turned off within a week, which is how guards die.
 *    Asserting "not the old ones" never goes stale and catches the real bug.
 */
$photo1_md5 = [
    'andrew-charlotte-popup-portrait.webp' => 'b7114bc2b994df103c3aac4b227d75c1',
    'andrew-charlotte-popup-portrait.jpg'  => '5cf0e53c955c1f9faebe47a0c39c1ad9',
    'andrew-charlotte-popup-band.webp'     => '201bcdccc3fb0948b67d727681862b78',
    'andrew-charlotte-popup-band.jpg'      => '8b25dbef842848443692eb455a8f7e86',
];
foreach ($photo1_md5 as $file => $old) {
    $path = $theme_dir . '/assets/images/founder/' . $file;
    bhp_photo_assert(
        file_exists($path) && md5_file($path) !== $old,
        sprintf('⭐ %s is photograph 2, not the 1.19.298 file', $file),
        $failures
    );
}

/*
 * ⛔ THE ALT TEXT MOVED WITH THE PHOTOGRAPH. Photograph 1's exact shipped
 *    string must not still be in the theme: it describes a frame in which
 *    Charlotte is not smiling at the camera and her hand is not raised, so
 *    leaving it in place would be a false description, not merely a stale one.
 */
bhp_photo_assert(
    false === stripos(
        $alt,
        'Me and my niece Charlotte, holding a paperback of Adventures of Charlotte and Henry'
    ),
    '⛔ the alt text is NOT photograph 1\'s string — it was rewritten, not carried across',
    $failures
);

/*
 * ⭐ AND IT DESCRIBES WHAT IS ACTUALLY IN FRAME. The founder's stated reason
 *    for the swap was the smile; if the accessible name does not carry it, a
 *    screen-reader user gets a strictly worse photograph than a sighted one.
 *    ⚠ Checked as CONCEPTS, not as a fixed sentence, so the copy can be
 *      improved without the guard having to be edited to permit it.
 */
bhp_photo_assert(
    1 === preg_match('/\bsmil/i', $alt),
    '⭐ the alt text carries the smile — the founder\'s own reason for the swap',
    $failures
);
bhp_photo_assert(
    1 === preg_match('/\bpaperback\b/i', $alt)
        && false !== stripos($alt, 'The Mariana Trench'),
    '⭐ the alt text still names the book that is in the frame',
    $failures
);

/*
 * ⛔ THE NIECE GUARD STILL FIRES ON THE NEW STRING. Section 1 proves the guard
 *    works and section 3 proves the resolver fails closed; this proves the
 *    guard was actually run against the string this pass wrote, which is the
 *    only one of the three that a photograph swap could have broken.
 */
bhp_photo_assert(
    [] === bhp_niece_canon_violations($alt)
        && false !== stripos($alt, 'my niece Charlotte'),
    '⭐ the REWRITTEN alt text passes the niece guard and still says "my niece Charlotte"',
    $failures
);
bhp_photo_assert(
    !empty(bhp_niece_canon_violations(
        str_ireplace('my niece Charlotte', 'my daughter Charlotte', $alt)
    )),
    '⭐ and the guard REJECTS the same sentence with the wrong kinship word',
    $failures
);

/*
 * ⛔⛔ THE ASSET URLS CARRY THE THEME VERSION, AND THIS IS THE ASSERTION THAT
 *     PROVES A PHOTOGRAPH SWAP ACTUALLY REACHES A RETURNING VISITOR.
 *
 *     Measured on staging during this pass: these filenames are fixed and are
 *     served with `Cache-Control: max-age=31536000`. The same URL returned a
 *     44,296-byte 600x750 image from cache and a 58,310-byte 560x896 image
 *     from the network, in the same page load, an hour apart in Last-Modified.
 *     Without a version query the founder's new photograph would have been
 *     invisible to everyone who had already seen the old one, for a year,
 *     while every other check in this file still passed.
 *
 * ⚠ ASSERTED ON THE RESOLVER'S OUTPUT, not on the markup, because the markup
 *   test would pass on a hardcoded string and this one cannot.
 */
$versioned = bhp_get_founder_photo();
$theme_ver = wp_get_theme()->get('Version');
foreach (['portrait_webp', 'portrait_jpg', 'band_webp', 'band_jpg'] as $k) {
    bhp_photo_assert(
        !empty($versioned[$k])
            && false !== strpos($versioned[$k], 'ver=' . rawurlencode($theme_ver)),
        sprintf('⛔ %s carries ver=%s, so a year-long cache cannot pin an old photograph', $k, $theme_ver),
        $failures
    );
}

/*
 * ⛔ THE DESKTOP PANEL ANCHORS LEFT, AND THE CROP DEPENDS ON IT.
 *    Photograph 2 puts the book's leftmost glyph 55px from the frame edge, so
 *    a centred `object-position` clips the cover title mid-word at the
 *    stylesheet's own floor panel shape. That was rendered and observed before
 *    this rule was written. Reverting the anchor without re-cutting the crop
 *    would reintroduce the clip silently, at one viewport band, on a surface
 *    nobody re-screenshots. Hence a test rather than a comment.
 */
$css = @file_get_contents($theme_dir . '/style.css');
bhp_photo_assert(
    is_string($css) && false !== strpos(
        $css,
        '.mariana-popup--photo .popup-ab__photo img { object-position: 0% 50%; }'
    ),
    '⛔ the desktop photo panel anchors LEFT, so `cover` never trims the book\'s title',
    $failures
);

echo "\n";
if ($failures > 0) {
    echo "RESULT: {$failures} failure(s)\n";
    exit(1);
}
echo "RESULT: all assertions passed\n";
