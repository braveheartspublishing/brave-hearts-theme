<?php
/**
 * READ-ALOUD TAKE-HOME LANDING SUITE — theme 1.19.291, 2026-08-24,
 * `CYCLE166-CX-READALOUD-COMBO` (amending `CYCLE166-CX-READALOUD-LANDING`).
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-read-aloud-landing.php --user=1
 *
 * WHAT THIS SUITE PROVES
 *   1. Both continuity tiles resolve, from DIFFERENT products, to DIFFERENT
 *      real attachments — the founder amendment ("show both") and the FD-549
 *      never-borrow rule, asserted rather than described.
 *   2. The Mailchimp tag set for this page is what it is meant to be, proven
 *      by CALLING the real filter chain, and the priority-20 override actually
 *      beats the parent-landing callback that shares this lead magnet.
 *   3. Funnel isolation held: the new files touch NO funnel storage prefix, no
 *      teacher event prefix, and mint no third funnel.
 *   4. The capture is the SHARED signup-form template part — no second
 *      endpoint, no AJAX, no duplicated Mailchimp path, no popup engine fork.
 *   5. The copy rails hold in the shipped source: no "we/us/our", no school
 *      name/date, no price literal, no 5-9 age range, no fabricated review or
 *      rating vocabulary.
 *   6. The page renders no automatic popup, and exit-intent is suppressed on
 *      this template ONLY.
 *   7. ⭐ 1.19.291 — THE COMBO IS THE EXISTING OFFER ON THE EXISTING CART PATH.
 *      No product id, no price literal, no second pricing path, no new nonce
 *      and no new endpoint; it opts into the cart side panel via the shipped
 *      `data-bhp-offer-panel` attribute and keeps the shipped no-JavaScript
 *      floor. Its picture is the composite of BOTH books and is asserted to be
 *      neither tile's cover. It renders BEFORE the two individual cards, which
 *      is the founder's positional ruling asserted rather than described.
 *   8. ⭐ 1.19.291 — AMERICAN SPELLING in everything a customer reads: this
 *      template's strings, the offer-engine copy this page pulls in, and the
 *      three attachments' real alt text. ⛔ Deliberately NOT the PHP
 *      identifiers, CSS modifier and media slug, which keep British spelling
 *      because they are contracts, not copy — see §12's own note.
 *
 * WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS
 *   It is a PHP + source-level suite, not a browser. It cannot observe layout,
 *   a cover painting, a tap target, console cleanliness, an iOS zoom, or a
 *   dataLayer push. Those are browser-QA claims and are recorded separately,
 *   with viewport evidence, in the handoff. It also cannot prove a Mailchimp
 *   contact was created.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ THE COUNTERS LIVE IN $GLOBALS EXPLICITLY, AND THAT IS A REPAIRED DEFECT,
 *     NOT A STYLE CHOICE. The first run of this suite printed six FAIL lines
 *     and then "PASS: 0   FAIL: 0 / SUITE PASS" underneath them.
 *
 *     WHY: `wp eval-file` executes the file inside a FUNCTION scope, not the
 *     global scope. `$pass = 0;` at the top of the file therefore created a
 *     LOCAL, and `global $pass;` inside the helper bound a DIFFERENT, unset
 *     global. The helper incremented one variable and the summary read
 *     another, so the summary was structurally incapable of ever reporting a
 *     failure.
 *
 * ⛔ A SUITE THAT CANNOT REPORT A FAILURE IS WORSE THAN NO SUITE. It is a
 *    fabricated verification — the same failure class as a fabricated review —
 *    and it would have shipped a green tick over six real defects. Any future
 *    edit to this harness must keep the counter and the summary reading the
 *    SAME storage.
 */
$GLOBALS['bhp_ra_pass'] = 0;
$GLOBALS['bhp_ra_fail'] = 0;

function bhp_ra_ok($label, $cond, $detail = '') {
    if ($cond) {
        $GLOBALS['bhp_ra_pass']++;
        echo "PASS  {$label}\n";
    } else {
        $GLOBALS['bhp_ra_fail']++;
        echo "FAIL  {$label}" . ($detail ? "  -- {$detail}" : '') . "\n";
    }
}

/**
 * Source with every PHP comment and every CSS comment removed.
 *
 * ⛔⛔ THE ISOLATION ASSERTIONS MUST READ CODE, NOT PROSE, AND THE FIRST RUN
 *     PROVED IT THE HARD WAY. Five of the six failures were the suite reading
 *     the shipped files' OWN DOCBLOCKS — which deliberately name the forbidden
 *     strings in order to record that they are forbidden. `inc/read-aloud-
 *     landing.php` says, in a comment, *"reads and writes nothing under
 *     `bhp_mariana_popup*`"*, and a naive `strpos()` over the raw file scored
 *     that sentence as a violation of itself.
 *
 * ⭐ SO THE ASSERTION IS NARROWED, NOT RELAXED. Stripping comments makes the
 *    test stricter about the thing it actually claims — that no CODE touches
 *    another funnel's storage — while letting the files keep the reasoning
 *    that makes them maintainable. ⛔ Do NOT "fix" a future failure here by
 *    deleting the docblock sentence instead; the comment is the record.
 */
function bhp_ra_strip_comments($src) {
    /* /* … *\/ and // … and # … , in that order. */
    $src = preg_replace('!/\*.*?\*/!s', ' ', (string) $src);
    $src = preg_replace('!^\s*//.*$!m', ' ', $src);
    return (string) $src;
}

$theme_dir = get_template_directory();
$inc_src   = (string) @file_get_contents($theme_dir . '/inc/read-aloud-landing.php');
$tpl_src   = (string) @file_get_contents($theme_dir . '/page-read-aloud.php');
$css_src   = (string) @file_get_contents($theme_dir . '/assets/css/read-aloud.css');

echo "\n===== 1. FILES AND WIRING =====\n";
bhp_ra_ok('inc/read-aloud-landing.php exists', '' !== $inc_src);
bhp_ra_ok('page-read-aloud.php exists', '' !== $tpl_src);
bhp_ra_ok('assets/css/read-aloud.css exists', '' !== $css_src);
bhp_ra_ok('minified CSS artefact built', file_exists($theme_dir . '/assets/css/read-aloud.min.css'));
bhp_ra_ok('template declares a Template Name', false !== strpos($tpl_src, 'Template Name:'));
bhp_ra_ok('bhp_read_aloud_continuity_pair() loaded', function_exists('bhp_read_aloud_continuity_pair'));
bhp_ra_ok('bhp_read_aloud_mailchimp_tags() loaded', function_exists('bhp_read_aloud_mailchimp_tags'));
bhp_ra_ok('bhp_is_read_aloud_landing() loaded', function_exists('bhp_is_read_aloud_landing'));

echo "\n===== 2. THE FOUNDER AMENDMENT: BOTH COVERS, NEVER SHARED =====\n";
$pair = bhp_read_aloud_continuity_pair();
bhp_ra_ok('pair has exactly two tiles', is_array($pair) && 2 === count($pair), 'got ' . (is_array($pair) ? count($pair) : 'non-array'));
$chapter   = $pair[0] ?? [];
$colouring = $pair[1] ?? [];
bhp_ra_ok('tile 1 is the CHAPTER book', ($chapter['key'] ?? '') === 'chapter');
bhp_ra_ok('tile 2 is the COLOURING book', ($colouring['key'] ?? '') === 'colouring');
bhp_ra_ok('chapter cover resolves to a real attachment',
    !empty($chapter['image_id']) && 'attachment' === get_post_type((int) $chapter['image_id']),
    'image_id=' . var_export($chapter['image_id'] ?? null, true));
bhp_ra_ok('colouring cover resolves to a real attachment',
    !empty($colouring['image_id']) && 'attachment' === get_post_type((int) $colouring['image_id']),
    'image_id=' . var_export($colouring['image_id'] ?? null, true));
/*
 * ⛔ THE ONE ASSERTION THIS WHOLE STRUCTURE EXISTS FOR. A chapter-book cover
 *    under "your coloring page came from this book" is a false claim built
 *    from two true facts (FD-549).
 */
bhp_ra_ok('the two tiles do NOT share an attachment',
    (int) ($chapter['image_id'] ?? 0) !== (int) ($colouring['image_id'] ?? 0),
    'both resolved to ' . (int) ($chapter['image_id'] ?? 0));
bhp_ra_ok('the two tiles do NOT share a URL',
    (string) ($chapter['url'] ?? 'a') !== (string) ($colouring['url'] ?? 'b'));
bhp_ra_ok('chapter tile links at a real product',
    !empty($chapter['url']) && false !== strpos((string) $chapter['url'], '/product/'));
bhp_ra_ok('colouring tile links at a real product',
    !empty($colouring['url']) && false !== strpos((string) $colouring['url'], '/product/'));
/* No attachment id may be hardcoded — ids are environment-local. */
bhp_ra_ok('no hardcoded attachment/product id in the inc file',
    !preg_match('/image_id[\'"]?\s*=>\s*[1-9]\d+/', $inc_src) && !preg_match('/\b(4065|4066|618|619|333|13)\b\s*[,;)]/', $inc_src));

echo "\n===== 3. MAILCHIMP TAGS — BY CALLING THE REAL FILTER =====\n";
$ra_tags = apply_filters(
    'bhp_mailchimp_signup_tags',
    [],
    'read_aloud_landing',
    'parents_families',
    'reluctant_reader_adventure_kit',
    home_url('/read-aloud/')
);
bhp_ra_ok('read-aloud tag set has exactly 3 tags', is_array($ra_tags) && 3 === count($ra_tags), print_r($ra_tags, true));
bhp_ra_ok('resource tag names the kit that is actually delivered',
    in_array('Reluctant Reader Adventure Kit', (array) $ra_tags, true));
bhp_ra_ok('audience is Parent/Grandparent (NOT educator)',
    in_array('Audience: Parent/Grandparent', (array) $ra_tags, true));
bhp_ra_ok('source is distinctly Read-Aloud Visit',
    in_array('Source: Read-Aloud Visit', (array) $ra_tags, true));
/* ⛔ The priority-20 override must actually beat functions.php:2460. */
bhp_ra_ok('priority 20 BEATS the parent-landing source tag',
    !in_array('Source: Parent Landing Page', (array) $ra_tags, true)
    && !in_array('Source: Parent Popup', (array) $ra_tags, true));
bhp_ra_ok('no educator/teacher tag leaked into the read-aloud set',
    !preg_grep('/Teacher|Librarian|Educator|Classroom/i', (array) $ra_tags));
bhp_ra_ok('filter registered at priority 20',
    false !== strpos($inc_src, "'bhp_read_aloud_mailchimp_tags', 20, 5"));

echo "\n===== 4. EXISTING FUNNELS ARE BYTE-UNCHANGED =====\n";
$parent_landing = apply_filters('bhp_mailchimp_signup_tags', [], 'lead_magnet', 'parents_families', 'reluctant_reader_adventure_kit', home_url('/'));
bhp_ra_ok('parent LANDING tags still resolve to Parent Landing Page',
    in_array('Source: Parent Landing Page', (array) $parent_landing, true), print_r($parent_landing, true));
$parent_popup = apply_filters('bhp_mailchimp_signup_tags', [], 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url('/'));
bhp_ra_ok('parent POPUP tags still resolve to Parent Popup',
    in_array('Source: Parent Popup', (array) $parent_popup, true), print_r($parent_popup, true));
$teacher = apply_filters('bhp_mailchimp_signup_tags', [], 'teacher_popup', 'educators', 'mariana_trench_classroom_guide', home_url('/teachers/'));
bhp_ra_ok('TEACHER tags still resolve to the teacher funnel',
    in_array('Audience: Teacher/Librarian', (array) $teacher, true), print_r($teacher, true));
bhp_ra_ok('read-aloud source tag did NOT leak into the teacher set',
    !in_array('Source: Read-Aloud Visit', (array) $teacher, true));
$school_visit = apply_filters('bhp_mailchimp_signup_tags', [], 'school_visit', 'parents_families', '', home_url('/'));
bhp_ra_ok('school-visit checkout tags unaffected',
    in_array('Source: School Visit', (array) $school_visit, true), print_r($school_visit, true));

echo "\n===== 5. FUNNEL ISOLATION — NO THIRD FUNNEL MINTED =====\n";
/* Raw source, for assertions about what the files SAY (schema, claims). */
$raw_src = $inc_src . "\n" . $tpl_src . "\n" . $css_src;
/* Comment-stripped source, for assertions about what the files DO. */
$new_src = bhp_ra_strip_comments($inc_src) . "\n" . bhp_ra_strip_comments($tpl_src) . "\n" . bhp_ra_strip_comments($css_src);
bhp_ra_ok('no bhp_parent_popup storage key touched', false === strpos($new_src, 'bhp_parent_popup'));
bhp_ra_ok('no bhp_mariana_popup storage key touched', false === strpos($new_src, 'bhp_mariana_popup'));
bhp_ra_ok('no teacher_popup event prefix emitted', false === strpos($new_src, 'teacher_popup'));
bhp_ra_ok('no data-popup-config (the popup engine is not driven)', false === strpos($new_src, 'data-popup-config'));
bhp_ra_ok('no data-bhp-popup (mariana-popup.js will not adopt anything here)', false === strpos($new_src, 'data-bhp-popup'));
bhp_ra_ok('no new lead magnet key invented',
    false === strpos($new_src, 'read_aloud_kit') && false === strpos($new_src, 'readaloud_magnet'));
bhp_ra_ok('reuses the existing parent lead magnet',
    false !== strpos($tpl_src, 'reluctant_reader_adventure_kit'));
bhp_ra_ok('reuses the existing parent thank-you redirect key',
    false !== strpos($tpl_src, 'adventure_kit_thank_you'));

echo "\n===== 6. ONE CAPTURE, THROUGH THE SHARED HANDLER =====\n";
bhp_ra_ok('renders the SHARED signup-form template part',
    false !== strpos($tpl_src, "template-parts/acquisition/signup-form"));
bhp_ra_ok('exactly one signup-form call on the page',
    1 === substr_count($tpl_src, "template-parts/acquisition/signup-form"),
    'found ' . substr_count($tpl_src, "template-parts/acquisition/signup-form"));
bhp_ra_ok('uses the distinct read_aloud_landing context',
    false !== strpos($tpl_src, "'read_aloud_landing'"));
bhp_ra_ok('no second endpoint / AJAX / admin-ajax',
    false === stripos($new_src, 'admin-ajax') && false === stripos($new_src, 'wp_ajax') && false === stripos($new_src, 'fetch('));
bhp_ra_ok('no direct Mailchimp API call',
    false === stripos($new_src, 'api.mailchimp') && false === stripos($new_src, 'mc4wp_') && false === stripos($new_src, 'bhp_mailchimp_subscribe'));
bhp_ra_ok('gated on the kit PDF actually being available',
    false !== strpos($tpl_src, "bhp_get_reluctant_reader_download"));
/*
 * ⛔ REPAIRED AFTER THE SECOND RUN. The superseded assertion searched for the
 *    bare substring `wp_enqueue_script` and failed on
 *    `add_action('wp_enqueue_scriptS', …)` — the CORE HOOK NAME, which
 *    contains the function name as a prefix. The feature enqueues a
 *    stylesheet on that hook and no script at all, so the FAIL was the test's,
 *    not the code's. The open paren is what distinguishes a call from a hook.
 */
bhp_ra_ok('no JavaScript file enqueued by this feature',
    false === strpos(bhp_ra_strip_comments($inc_src), 'wp_enqueue_script('));
bhp_ra_ok('a stylesheet IS enqueued, on the standard hook',
    false !== strpos(bhp_ra_strip_comments($inc_src), 'wp_enqueue_style(')
    && false !== strpos(bhp_ra_strip_comments($inc_src), "'wp_enqueue_scripts'"));

echo "\n===== 7. SCHOOL-AGNOSTIC =====\n";
bhp_ra_ok('no bhp_visit argument read or emitted', false === strpos($new_src, 'bhp_visit'));
bhp_ra_ok('no visit slug / registry read', false === strpos($new_src, 'bhp_school_visit'));
bhp_ra_ok('no school named in source', !preg_match('/\bAdams\b|\bElementary School\b/i', $new_src));
bhp_ra_ok('no hardcoded date in customer copy', !preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2}\b/', $tpl_src));

echo "\n===== 8. COPY RAILS =====\n";
/* Visible strings only: the docblocks legitimately discuss "we" in reasoning. */
$visible = '';
if (preg_match_all('/(?:esc_html_e|esc_html__|__|_e|wp_kses_post)\(\s*[\'"](.*?)[\'"]\s*(?:,|\))/s', $tpl_src, $m)) {
    $visible = implode(' | ', $m[1]);
}
bhp_ra_ok('customer-facing copy was extracted', '' !== $visible);
bhp_ra_ok('I-voice: no "we/us/our" in customer-facing copy',
    !preg_match('/\b(we|we\'re|we\'ve|us|our|ours)\b/i', $visible),
    $visible);
bhp_ra_ok('reading age is 6-9, never 5-9',
    false === strpos($visible, '5–9') && false === strpos($visible, '5-9'));
bhp_ra_ok('reading age 6-9 is stated', false !== strpos($visible, '6–9') || false !== strpos($visible, '6-9'));
bhp_ra_ok('no price literal in customer-facing copy',
    !preg_match('/\$\s?\d/', $visible), $visible);
bhp_ra_ok('no review / rating / testimonial vocabulary',
    !preg_match('/\b(review|reviews|rating|ratings|stars?|testimonial|bestsell|award-winning|parents say|teachers say|loved by)\b/i', $visible));
bhp_ra_ok('no aggregateRating or review schema emitted',
    false === strpos($new_src, 'aggregateRating') && false === strpos($new_src, '"review"'));
bhp_ra_ok('no unconfirmed founder specifics (Island Peak / Jiri / 20,000 feet / without oxygen)',
    !preg_match('/Island Peak|\bJiri\b|20,000 feet|without oxygen/i', $new_src));
bhp_ra_ok('no chapter/page/design count restated',
    !preg_match('/\b\d+\s+(chapters|pages|designs)\b/i', $visible));

echo "\n===== 9. NO AUTOMATIC POPUP ON THIS PAGE =====\n";
bhp_ra_ok('exit-intent suppressed via the SHIPPED filter, not a functions.php edit',
    false !== strpos($inc_src, "add_filter('bhp_show_exit_intent_popup'"));
bhp_ra_ok('suppression is template-scoped (returns $show off-template)',
    false !== strpos($inc_src, 'bhp_is_read_aloud_landing() ? false : $show'));
bhp_ra_ok('exit-intent still enabled elsewhere',
    true === (bool) apply_filters('bhp_show_exit_intent_popup', true));
bhp_ra_ok('no timer / scroll / exit trigger declared by this feature',
    false === stripos($new_src, 'setTimeout') && false === stripos($new_src, 'scrollDepth') && false === stripos($new_src, 'mouseleave'));

echo "\n===== 10. WRITES NOTHING =====\n";
bhp_ra_ok('no option write', false === strpos($inc_src, 'update_option') && false === strpos($tpl_src, 'update_option'));
bhp_ra_ok('no meta write', false === strpos($new_src, 'update_post_meta') && false === strpos($new_src, 'add_post_meta'));
bhp_ra_ok('no cookie / session write', false === strpos($new_src, 'setcookie') && false === strpos($new_src, '$_SESSION'));
/*
 * ⚠️ LABEL CORRECTED AT 1.19.291, BECAUSE THE OLD ONE WOULD NOW BE READ AS
 *    PROVING SOMETHING IT NEVER PROVED. It used to read "no WooCommerce
 *    mutation", on a page whose every buying control was a link. The page now
 *    renders a real ADD TO CART, so the honest claim is narrower and sharper:
 *    this FEATURE'S OWN SOURCE mutates nothing. The button that does mutate a
 *    cart is the offer engine's, posting to the plugin's existing handler —
 *    asserted in §13, not hidden behind a stale label here.
 */
bhp_ra_ok('this feature\'s source performs no WooCommerce mutation itself',
    false === strpos($new_src, 'set_price') && false === strpos($new_src, 'set_stock_status') && false === strpos($new_src, 'add_to_cart'));
bhp_ra_ok('no BookVAULT shipping reference anywhere', false === stripos($new_src, 'bookvault'));
/* ⛔ No second pricing path, no second cart path. The engine owns both. */
bhp_ra_ok('no cart object, fee or product lookup opened by this feature',
    false === strpos($new_src, 'WC()->cart')
    && false === strpos($new_src, 'add_fee(')
    && false === strpos($new_src, 'wc_get_product('));
bhp_ra_ok('no currency literal anywhere in the feature\'s PHP',
    !preg_match('/\b\d{1,3}\.\d{2}\b/', bhp_ra_strip_comments($inc_src) . bhp_ra_strip_comments($tpl_src)));

echo "\n===== 11. MOBILE-FIRST CSS, SCOPED =====\n";
/*
 * ⛔ REPAIRED AFTER THE FIRST RUN. The superseded pattern was
 *      /^\s*(?!\.read-aloud|@|\/\*|\s*\*|\})\S[^{]*\{/m
 *    and it reported a FAIL against a stylesheet in which every single
 *    selector IS scoped (confirmed independently with
 *    `grep -nE '^[^[:space:]}@/*][^{]*\{'`, which listed only `.read-aloud*`).
 *
 *    THE BUG: `[^{]` matches a NEWLINE. So the pattern would start on an
 *    ordinary indented DECLARATION line (`  background: …;`), pass the
 *    lookahead, and then run `[^{]*` forward across many lines until it found
 *    the NEXT rule's opening brace — reporting a match that had nothing to do
 *    with an unscoped selector. A negative assertion built on a greedy
 *    cross-line class is not a weaker test, it is a test of something else.
 *
 * ⭐ THE REPLACEMENT COLLECTS EVERY COLUMN-ZERO BLOCK OPENER AND NAMES THE
 *    OFFENDER, so a future failure is diagnosable instead of just red.
 */
$css_lines   = preg_split('/\R/', $css_src);
$css_leaks   = [];
$css_in_at   = false;
foreach ($css_lines as $css_line) {
    if ('' === trim($css_line) || preg_match('/^\s/', $css_line)) {
        continue; // indented: inside a block or an at-rule
    }
    if (preg_match('/^(\}|\/\*|\*|@)/', $css_line)) {
        continue; // close brace, comment, at-rule
    }
    if (false === strpos($css_line, '{') && false === strpos($css_line, ',')) {
        continue; // not a selector line
    }
    if (0 !== strpos(ltrim($css_line), '.read-aloud')) {
        $css_leaks[] = trim($css_line);
    }
}
bhp_ra_ok('every rule is scoped under .read-aloud',
    empty($css_leaks), implode(' | ', $css_leaks));
bhp_ra_ok('at least 20 scoped rules were actually inspected',
    substr_count($css_src, '.read-aloud') >= 20, (string) substr_count($css_src, '.read-aloud'));
bhp_ra_ok('base grid is two columns (pair reads as a pair on a phone)',
    false !== strpos($css_src, 'grid-template-columns: repeat(2, minmax(0, 1fr))'));
bhp_ra_ok('only min-width media queries (mobile-first)',
    false === strpos($css_src, 'max-width:') || 0 === substr_count($css_src, '@media (max-width'));
bhp_ra_ok('inputs are >= 16px (no iOS focus zoom)', false !== strpos($css_src, 'font-size: 1rem'));
bhp_ra_ok('no new colour literal outside tokens',
    !preg_match('/:\s*#[0-9a-f]{3,8}\b/i', $css_src));

echo "\n===== 12. AMERICAN SPELLING IN CUSTOMER-FACING TEXT =====\n";
/*
 * ⛔⛔ FOUNDER STANDING RULE, 2026-08-24, relayed: "coloring", never
 *     "colouring", in anything a customer reads.
 *
 * ⭐ THE ASSERTION IS DELIBERATELY SPLIT FROM THE IDENTIFIERS, AND THAT SPLIT
 *    IS THE WHOLE POINT OF THIS SECTION. `bhp_colouring_product_ids()`, the
 *    `'colouring'` tile key, the `--colouring` CSS modifier and the attachment
 *    slug `bhp-bundle-composite-mariana-pb-colouring` all keep their British
 *    spelling on purpose: they are a PHP API owned by another workstream, a
 *    CSS hook and a WordPress media record looked up by exact string. ⛔ A
 *    naive scan of the raw source for "colour" would fail on every one of them
 *    and would push a future session into renaming a live contract to fix a
 *    spelling nobody sees.
 *
 * ⭐ SO THIS SECTION SCANS ONLY WHAT IS RENDERED: this template's extracted
 *    strings, the offer-engine copy this page now pulls in, and the real alt
 *    text of the three attachments it paints.
 */
$bhp_ra_british = '/\b\w*(colour|honour|favour|realise|realis|organis|centre|licence|grey|whilst|towards)\w*\b/i';

bhp_ra_ok('no British spelling in this template\'s customer-facing copy',
    !preg_match($bhp_ra_british, $visible), $visible);

/* The offer engine's strings are another desk's copy, but this page RENDERS
   them — so this page verifies them. ⛔ It does not edit them; a failure here
   is a finding to route, not a file to change. */
$bhp_ra_engine = [];
foreach (['offer_card_title', 'offer_descriptor', 'offer_card_price_label', 'offer_card_cta', 'offer_saving'] as $bhp_ra_k) {
    $bhp_ra_engine[] = function_exists('bhp_colouring_draft_copy') ? (string) bhp_colouring_draft_copy($bhp_ra_k) : '';
}
$bhp_ra_engine_blob = implode(' | ', $bhp_ra_engine);
bhp_ra_ok('offer-engine copy rendered on this page is American',
    '' !== trim($bhp_ra_engine_blob) && !preg_match($bhp_ra_british, $bhp_ra_engine_blob), $bhp_ra_engine_blob);

/* Alt text IS customer-facing — a screen reader speaks it and a broken image
   prints it. Read off the REAL attachments, live, not from source. */
$bhp_ra_alts = [
    'chapter tile'  => (string) ($chapter['alt'] ?? ''),
    'coloring tile' => (string) ($colouring['alt'] ?? ''),
];
$bhp_ra_comp_id = function_exists('bhp_offer_composite_attachment_id') && function_exists('bhp_read_aloud_combo_key')
    ? (int) bhp_offer_composite_attachment_id(bhp_read_aloud_combo_key())
    : 0;
if ($bhp_ra_comp_id) {
    $bhp_ra_alts['combo composite'] = (string) get_post_meta($bhp_ra_comp_id, '_wp_attachment_image_alt', true);
}
foreach ($bhp_ra_alts as $bhp_ra_where => $bhp_ra_alt) {
    bhp_ra_ok("alt text is present and American ({$bhp_ra_where})",
        '' !== trim($bhp_ra_alt) && !preg_match($bhp_ra_british, $bhp_ra_alt), $bhp_ra_alt);
}

echo "\n===== 13. THE COMBO — THE EXISTING OFFER, THE EXISTING CART PATH =====\n";
/*
 * Andrew, after his staging walk, relayed: *"add the combo of both of them as
 * well - like if they didnt buy MT they could buy both in one swoop - It
 * should be the first option as well"*.
 *
 * ⛔ WHAT THIS SECTION IS REALLY FOR: proving the combo did NOT become a second
 *    pricing path. Every assertion below is a way of asking "is this still the
 *    shop's own offer engine?" — because the cheap way to satisfy the founder's
 *    request would have been to hardcode two product ids and a price, and that
 *    would have passed a browser walk and rotted the first time he changed one.
 */
bhp_ra_ok('bhp_read_aloud_combo_key() loaded', function_exists('bhp_read_aloud_combo_key'));
bhp_ra_ok('bhp_read_aloud_combo() loaded', function_exists('bhp_read_aloud_combo'));

$bhp_ra_key   = function_exists('bhp_read_aloud_combo_key') ? bhp_read_aloud_combo_key() : '';
$bhp_ra_combo = function_exists('bhp_read_aloud_combo') ? bhp_read_aloud_combo() : [];
$bhp_ra_html  = (string) ($bhp_ra_combo['html'] ?? '');

bhp_ra_ok('the combo key names a real row in bhp_offer_catalog()',
    function_exists('bhp_offer_catalog') && isset(bhp_offer_catalog()[$bhp_ra_key]), $bhp_ra_key);
/* ⛔ FD-579 — an offer is a NAME for real catalogue items, never a product. */
$bhp_ra_row = function_exists('bhp_offer_catalog') ? (bhp_offer_catalog()[$bhp_ra_key] ?? []) : [];
bhp_ra_ok('the offer carries NO product_id / sku / isbn (FD-579)',
    !isset($bhp_ra_row['product_id']) && !isset($bhp_ra_row['sku']) && !isset($bhp_ra_row['isbn']));
bhp_ra_ok('the offer is priced by the engine, not by this page',
    function_exists('bhp_offer_price') && null !== bhp_offer_price($bhp_ra_key));
bhp_ra_ok('the combo resolves and is buyable on this environment',
    !empty($bhp_ra_combo), 'empty — bhp_offer_render_module() returned \'\'');

/* ── The founder\'s positional ruling, asserted rather than described. ── */
$bhp_ra_combo_at = strpos($tpl_src, 'read-aloud-combo');
$bhp_ra_grid_at  = strpos($tpl_src, 'read-aloud-pair__grid');
bhp_ra_ok('the combo is rendered BEFORE the two individual cards ("first option")',
    false !== $bhp_ra_combo_at && false !== $bhp_ra_grid_at && $bhp_ra_combo_at < $bhp_ra_grid_at);

/* ── R1.4: the gate, and the ORDER that enforces it. ── */
bhp_ra_ok('every combo element sits inside the buyability guard (R1.4)',
    false !== strpos($tpl_src, 'if (!empty($combo))'));
bhp_ra_ok('the resolver renders the module BEFORE assembling any copy',
    false !== strpos(bhp_ra_strip_comments($inc_src), "if ('' === \$html)"));

/* ── The cart path: the side panel, and the shipped no-JS floor. ── */
bhp_ra_ok('ADD TO CART opts into the cart side panel (data-bhp-offer-panel)',
    false !== strpos($bhp_ra_html, 'data-bhp-offer-panel'));
bhp_ra_ok('card mode: NOT the straight-to-checkout redirect field',
    false === strpos($bhp_ra_html, 'bhp_bundle_redirect'));
bhp_ra_ok('the no-JavaScript floor field is present',
    false !== strpos($bhp_ra_html, 'name="bhp_offer_panel"'));
bhp_ra_ok('posts the EXISTING plugin action for this offer',
    false !== strpos($bhp_ra_html, 'name="bhp_bundle_action"')
    && false !== strpos($bhp_ra_html, 'value="offer_' . $bhp_ra_key . '"'));
bhp_ra_ok('carries the EXISTING plugin nonce (no new nonce minted)',
    false !== strpos($bhp_ra_html, 'name="bhp_bundle_nonce"')
    && false === strpos(bhp_ra_strip_comments($inc_src) . bhp_ra_strip_comments($tpl_src), 'wp_create_nonce'));
/*
 * ⛔ REPAIRED AFTER THE FIRST STAGING RUN — THE TEST'S DEFECT, NOT THE CODE'S,
 *    AND IT IS THE THIRD OF ITS EXACT KIND IN THIS FILE. The superseded
 *    assertion was `preg_match('/\$\s?\d/', $bhp_ra_html)` and it FAILED
 *    against a module that was printing $22.99 perfectly.
 *
 *    WHY: `wc_price()` emits the currency symbol as the HTML ENTITY `&#36;`,
 *    inside `<span class="woocommerce-Price-currencySymbol">`. There is no
 *    literal `$` character anywhere in the markup, so a regex looking for one
 *    was searching for something WooCommerce never writes. ⛔ The dollar sign a
 *    human reads on the page and the dollar sign in the source are not the same
 *    byte, and a test that conflates them tests nothing.
 *
 * ⭐ THE REPLACEMENT IS STRICTER, NOT LOOSER. Rather than asking "is there a
 *    price?", it decodes the rendered text and asserts it contains EXACTLY the
 *    figure `bhp_offer_price()` returns, formatted by `wc_price()` — which is
 *    the claim that actually matters: the number on the page is the ENGINE'S,
 *    live, and not a literal that drifted. ⛔ It would now catch a hardcoded
 *    $22.99 that happened to be right today and wrong after Andrew re-prices.
 */
$bhp_ra_rendered = html_entity_decode(wp_strip_all_tags($bhp_ra_html), ENT_QUOTES, 'UTF-8');
$bhp_ra_expected = html_entity_decode(
    wp_strip_all_tags(function_exists('wc_price') ? wc_price(bhp_offer_price($bhp_ra_key)) : ''),
    ENT_QUOTES,
    'UTF-8'
);
bhp_ra_ok('the rendered figure IS bhp_offer_price(), not a literal',
    '' !== trim($bhp_ra_expected) && false !== strpos($bhp_ra_rendered, $bhp_ra_expected),
    'expected "' . $bhp_ra_expected . '" in rendered text');
/* The saving is a DERIVED claim — recomputed by the engine, never inherited. */
bhp_ra_ok('the rendered saving IS bhp_offer_saving(), recomputed live',
    null === bhp_offer_saving($bhp_ra_key)
    || false !== strpos($bhp_ra_rendered, html_entity_decode(wp_strip_all_tags(wc_price(bhp_offer_saving($bhp_ra_key))), ENT_QUOTES, 'UTF-8')));
bhp_ra_ok('the module carries no heading of its own (no duplicate title)',
    false === strpos($bhp_ra_html, 'bhp-offer__heading'));

/* ── FD-549 / R2.3 at the one place it would be easiest to break. ── */
bhp_ra_ok('the composite resolves to a real attachment',
    $bhp_ra_comp_id > 0 && 'attachment' === get_post_type($bhp_ra_comp_id),
    'composite id=' . $bhp_ra_comp_id);
/*
 * ⛔⛔ THE ASSERTION THIS SECTION EXISTS FOR. A chapter-book cover beside the
 *     combo's price states that that book costs the combo's price. The combo's
 *     picture must be the composite of BOTH books and must never equal either
 *     tile's cover.
 */
bhp_ra_ok('the combo picture is NEITHER tile\'s cover (FD-549 / R2.3)',
    $bhp_ra_comp_id !== (int) ($chapter['image_id'] ?? 0)
    && $bhp_ra_comp_id !== (int) ($colouring['image_id'] ?? 0),
    'composite=' . $bhp_ra_comp_id . ' chapter=' . (int) ($chapter['image_id'] ?? 0) . ' colouring=' . (int) ($colouring['image_id'] ?? 0));
bhp_ra_ok('the composite is resolved by SLUG, never by a hardcoded id',
    !preg_match('/\b4570\b/', bhp_ra_strip_comments($inc_src) . bhp_ra_strip_comments($tpl_src)));
bhp_ra_ok('the combo degrades rather than substituting when art is missing',
    false !== strpos($tpl_src, "'' !== \$combo['art']"));

/* ── The copy is the engine's, not a second copy of it. ── */
bhp_ra_ok('title and descriptor are RESOLVED from the engine\'s copy table',
    false !== strpos(bhp_ra_strip_comments($inc_src), "bhp_colouring_draft_copy('offer_card_title')")
    && false !== strpos(bhp_ra_strip_comments($inc_src), "bhp_colouring_draft_copy('offer_descriptor')"));
/*
 * ⛔⛔ COMMENT-STRIPPED, AND THIS IS THE **THIRD** TIME THIS EXACT TRAP HAS BEEN
 *     SPRUNG IN THIS FILE — see the `bhp_ra_strip_comments()` docblock and the
 *     §5 note. The raw-source version FAILED on a template that was perfectly
 *     correct, because the template's own comment QUOTES the engine title in
 *     order to explain why it must not be re-typed. The prose that records the
 *     rule kept scoring as a violation of it.
 *
 * ⭐ THE PATTERN IS NOW EXPLICIT AND SHOULD BE TREATED AS A HOUSE RULE FOR THIS
 *    SUITE: every assertion of the form "this string must not appear" reads
 *    `bhp_ra_strip_comments()`, never the raw source. ⛔ Do NOT ever fix a
 *    failure of this shape by deleting the explanatory comment — the comment is
 *    the record, and the test is the thing that is wrong.
 */
bhp_ra_ok('the engine\'s title is NOT re-typed into the template',
    false === strpos(bhp_ra_strip_comments($tpl_src), 'book + coloring book'));
/*
 * ⛔ THE DESCRIPTOR IS RESOLVED BUT NOT PRINTED — a deliberate copy decision
 *    (three restatements of one fact in one block), recorded in the template.
 *    Asserted so a future edit that re-adds it is a conscious reversal rather
 *    than an accident.
 */
bhp_ra_ok('the engine descriptor is resolved but NOT rendered (no third restatement)',
    isset($bhp_ra_combo['descriptor']) && '' !== $bhp_ra_combo['descriptor']
    && false === strpos(bhp_ra_strip_comments($tpl_src), 'read-aloud-combo__descriptor'));

/* ── The combo adds no asset and no funnel. ── */
bhp_ra_ok('still no JavaScript enqueued by this feature',
    false === strpos(bhp_ra_strip_comments($inc_src), 'wp_enqueue_script('));
bhp_ra_ok('combo CSS is scoped and does not repaint the shop grid',
    false !== strpos($css_src, '.read-aloud-combo__offer .bhp-offer__cta')
    && !preg_match('/^\s*\.bhp-offer/m', $css_src));
bhp_ra_ok('--space-5 is not referenced (it does not exist in the token scale)',
    false === strpos($css_src, 'var(--space-5)'));

echo "\n=====================================\n";
echo "PASS: {$GLOBALS['bhp_ra_pass']}   FAIL: {$GLOBALS['bhp_ra_fail']}\n";
echo (0 === $GLOBALS['bhp_ra_fail'] ? "SUITE PASS\n" : "SUITE FAIL\n");
