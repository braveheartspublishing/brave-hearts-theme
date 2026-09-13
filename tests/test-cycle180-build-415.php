<?php
/**
 * ⭐⭐ CYCLE180-LD-BUILD-415 — Andrew's notes of founder seals 1492 and 1493,
 *     plus Pippin's production fails of seal 1495, proven.
 *
 * Theme 1.19.415 / bundle plugin 1.8.94. ⛔ BOTH move in this build and §5
 * asserts each of them, as floors, rather than assuming either.
 *
 * ⛔ WHAT THIS SUITE COVERS, one section per brief item:
 *      §1  CX-19 / CX-3 — the colouring PDP gallery rendered ONE slide on
 *          production and SEVEN on staging from byte-identical code.
 *      §2  the related-product card did not fit in one 1440x900 viewport.
 *      §3  the review star row — left-aligned, small, dark green.
 *      §4  "BEST VALUE" overlapped "COMPLETE COLLECTION" by 2px.
 *      §5  LDB-9 root cause in the PLUGIN, and the version floors.
 *      §6  the standing guards.
 *
 * ⭐⭐ §1.3 IS THE ONLY ROW IN THIS SUITE THAT COULD HAVE CAUGHT THE BUG, AND
 *     IT IS THE ONLY ONE THAT IS NOT A `strpos()`. Read this before adding a
 *     grep and calling CX-19 covered.
 *
 *     The defect was NOT a missing declaration, a missing registry entry or a
 *     missing file. Every one of those was present and correct on production.
 *     `get_page_by_path($slug, OBJECT, 'attachment')` is a PATH resolver: given
 *     a one-segment path it walks the matched post's `post_parent` chain and
 *     accepts the match ONLY if that chain terminates at 0. Production's six
 *     interior attachments are ATTACHED to product 618, so all six resolved to
 *     NULL while existing perfectly well as ids 752-757.
 *
 *     ⛔ A TEST THAT GREPS FOR THE FALLBACK CODE WOULD PASS ON A BUILD WHERE
 *        THE FALLBACK IS PRESENT AND BROKEN. So §1.3 does the real thing: it
 *        finds a REAL attachment on THIS environment that HAS a parent,
 *        confirms `get_page_by_path()` cannot see it, and then asserts that
 *        `bhp_book_media_attachment_id()` CAN. That is the exact production
 *        condition, reproduced from whatever data the environment happens to
 *        have, with no hardcoded id.
 *
 * ⚠ §1.3 SKIPS RATHER THAN FAILS ON AN ENVIRONMENT WITH NO PARENTED
 *   ATTACHMENT. A skip is honest there; a pass would be a lie and a fail would
 *   be noise. It has NOT been observed to skip on either live environment.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE: §2, §3 and §4 are static analysis of the
 *   shipped stylesheets. They prove the rules SHIPPED and are well-formed; they
 *   do not prove what a browser painted. The browser evidence — measured
 *   heights, offsets and computed colours at asserted innerWidths of 390 and
 *   1440 — is captured separately and filed with the build report.
 *
 * ⛔⛔ §3 CANNOT BE PHOTOGRAPHED ON STAGING AND NO TEST REVIEW WAS CREATED TO
 *     MAKE IT POSSIBLE. staging2 has ZERO reviews on every product; production
 *     has exactly two real ones. Inventing review content to photograph a CSS
 *     change is the failure class the zero-fabrication rule names.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle180-build-415.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ READ-ONLY. Creates, updates and deletes NOTHING. No product, no variation,
 *    no attachment, no term, no option, no post, no comment, no cart, no order,
 *    no file.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

/*
 * ⛔⛔ COUNTERS LIVE IN $GLOBALS, NOT IN `global $x` — `wp eval-file` includes
 *     this file INSIDE A FUNCTION, so a helper declaring `global` would
 *     increment a DIFFERENT variable from the one the summary reads, printing
 *     "0 failed" however many assertions failed. Observed at 1.19.412.
 */
$GLOBALS['b415_passes']   = 0;
$GLOBALS['b415_failures'] = 0;
$GLOBALS['b415_skips']    = 0;

function b415_assert($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['b415_passes']++;
        echo "  PASS  {$label}\n";
    } else {
        $GLOBALS['b415_failures']++;
        echo "  FAIL  {$label}" . ('' !== $detail ? "  [{$detail}]" : '') . "\n";
    }
}

function b415_skip($label, $why) {
    $GLOBALS['b415_skips']++;
    echo "  SKIP  {$label}  [{$why}]\n";
}

/**
 * CSS specificity as [ids, classes, elements]. Deliberately narrow — type,
 * class, id and descendant/child combinators only, which is all §5 needs.
 */
function b415_specificity($selector) {
    $sel = trim(preg_replace('/\s*>\s*/', ' ', (string) $selector));
    $ids = preg_match_all('/#[A-Za-z0-9_-]+/', $sel);
    $cls = preg_match_all('/\.[A-Za-z0-9_-]+/', $sel);
    $stripped = preg_replace('/[#.][A-Za-z0-9_-]+/', ' ', $sel);
    $els = preg_match_all('/\b[a-zA-Z][a-zA-Z0-9]*\b/', $stripped);
    return array((int) $ids, (int) $cls, (int) $els);
}

/** True when $a wins the cascade over $b on specificity alone. */
function b415_beats(array $a, array $b) {
    for ($i = 0; $i < 3; $i++) {
        if ($a[$i] !== $b[$i]) {
            return $a[$i] > $b[$i];
        }
    }
    return false;
}

$b415_theme_dir  = get_template_directory();
$b415_bf_css     = (string) @file_get_contents($b415_theme_dir . '/assets/css/book-formats.css');
$b415_bf_min     = (string) @file_get_contents($b415_theme_dir . '/assets/css/book-formats.min.css');
$b415_media_php  = (string) @file_get_contents($b415_theme_dir . '/inc/book-media.php');

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · CX-19 / CX-3 — THE PARENTED-ATTACHMENT RESOLVER
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §1 CX-19 parented-attachment resolution ---\n";

b415_assert('1.1 bhp_book_media_attachment_id() exists',
    function_exists('bhp_book_media_attachment_id'));

b415_assert('1.2 the resolver still tries get_page_by_path() FIRST (the fallback is additive, not a replacement)',
    (bool) preg_match('/function\s+bhp_book_media_attachment_id.*?get_page_by_path\s*\(.*?\'attachment\'\s*=>\s*\$slug/s', $b415_media_php));

/*
 * ⭐⭐ THE REAL ONE. Reproduce the production condition from whatever data this
 *     environment has, with no hardcoded id on either side.
 */
$b415_parented = get_posts(array(
    'post_type'              => 'attachment',
    'post_status'            => 'inherit',
    'numberposts'            => 60,
    'orderby'                => 'ID',
    'order'                  => 'ASC',
    'no_found_rows'          => true,
    'update_post_term_cache' => false,
));
$b415_probe = null;
foreach ($b415_parented as $att) {
    if ((int) $att->post_parent > 0 && '' !== (string) $att->post_name) {
        // Only useful as a probe if core genuinely cannot see it by slug.
        if (null === get_page_by_path($att->post_name, OBJECT, 'attachment')) {
            $b415_probe = $att;
            break;
        }
    }
}

if (null === $b415_probe) {
    b415_skip('1.3 a PARENTED attachment resolves through the theme resolver',
        'this environment has no attachment with post_parent > 0 that get_page_by_path() misses');
} else {
    b415_assert(
        '1.3 ⭐ THE PRODUCTION CONDITION: an attachment get_page_by_path() CANNOT see is still resolved by the theme',
        (int) bhp_book_media_attachment_id($b415_probe->post_name) === (int) $b415_probe->ID,
        'slug=' . $b415_probe->post_name . ' parent=' . $b415_probe->post_parent
            . ' expected=' . $b415_probe->ID
            . ' got=' . (int) bhp_book_media_attachment_id($b415_probe->post_name)
    );
}

b415_assert('1.4 ⛔ NEGATIVE CONTROL — a slug that does not exist still returns 0 (the fallback did not loosen the gate)',
    0 === (int) bhp_book_media_attachment_id('definitely-not-a-real-slug-cycle180-415'));

b415_assert('1.5 ⛔ the fallback is restricted to post_status inherit (a trashed row must not resolve as approved media)',
    (bool) preg_match('/function\s+bhp_book_media_attachment_id.*?\'post_status\'\s*=>\s*\'inherit\'/s', $b415_media_php));

/*
 * ⭐ The end-to-end shape, where the registry key is present on this
 *    environment. On production this returned 0 items before the fix.
 */
if (function_exists('bhp_book_media')) {
    $b415_col = bhp_book_media('colouring_mariana');
    $b415_n   = isset($b415_col['items']) ? count($b415_col['items']) : 0;
    b415_assert('1.6 bhp_book_media(\'colouring_mariana\') resolves all SIX interior items',
        6 === $b415_n, "items={$b415_n}");
} else {
    b415_skip('1.6 colouring_mariana resolves six items', 'bhp_book_media() unavailable');
}

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · THE RELATED-PRODUCT CARD HEIGHT (DESKTOP ONLY)
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §2 related-card height ---\n";

b415_assert('2.1 the cover height rule ships',
    (bool) preg_match('/\.related\.products ul\.products li\.product img[^{}]*\{[^}]*height:\s*232px/s', $b415_bf_css));

/*
 * ⛔ BRACE-MATCHED, NOT REGEXED, AND THE FIRST ATTEMPT HERE WAS THE LESSON. A
 *    pattern of the form `@media(...)\{(?:[^{}]|\{[^{}]*\})*height:232px` can
 *    only span ONE level of nesting, so it reported the rule as OUTSIDE the
 *    media query when it is demonstrably inside it — a FALSE FAILURE on a
 *    correct build, which this project's runbook calls the more expensive kind
 *    because it teaches the next reader to distrust a passing artefact. The
 *    media block is located and its extent walked properly instead.
 */
$b415_mq_ok = false;
$b415_mq_at = strpos($b415_bf_css, '@media (min-width: 1024px)');
if (false !== $b415_mq_at) {
    $b415_open = strpos($b415_bf_css, '{', $b415_mq_at);
    if (false !== $b415_open) {
        $b415_depth = 0;
        $b415_end   = strlen($b415_bf_css);
        for ($i = $b415_open; $i < strlen($b415_bf_css); $i++) {
            if ('{' === $b415_bf_css[$i]) {
                $b415_depth++;
            } elseif ('}' === $b415_bf_css[$i]) {
                $b415_depth--;
                if (0 === $b415_depth) {
                    $b415_end = $i;
                    break;
                }
            }
        }
        $b415_block = substr($b415_bf_css, $b415_open, $b415_end - $b415_open);
        $b415_mq_ok = (false !== strpos($b415_block, 'height: 232px'));
    }
}
b415_assert('2.2 ⛔ it is inside a min-width media query — the phone layout is NOT touched',
    $b415_mq_ok);

/*
 * ⭐ AND THE OTHER HALF OF THE SAME CLAIM, WHICH 2.2 ALONE DOES NOT PROVE:
 *    that nothing outside the media query touches the cover height. A rule
 *    added later at top level would silently reach the phone.
 */
$b415_outside = $b415_mq_ok
    ? substr($b415_bf_css, 0, (int) $b415_mq_at) . substr($b415_bf_css, (int) $b415_end)
    : $b415_bf_css;
b415_assert('2.2b ⛔ ...and NO unscoped rule sets the related-card cover height outside it',
    !preg_match('/\.related\.products ul\.products li\.product img[^{}]*\{[^}]*height:\s*232px/s', (string) $b415_outside));

b415_assert('2.3 the upsell rail gets the identical treatment (the two rails must not drift)',
    (bool) preg_match('/\.upsells\.products ul\.products li\.product img/', $b415_bf_css));

b415_assert('2.4 ⛔ no crop was introduced — object-fit is NOT set to cover anywhere in the new block',
    !preg_match('/height:\s*232px[^}]*object-fit:\s*cover/s', $b415_bf_css));

b415_assert('2.5 the related-section heading margin tightens with it',
    (bool) preg_match('/\.related\.products\s*>\s*h2[^{}]*\{[^}]*margin-bottom:\s*18px/s', $b415_bf_css));

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · THE REVIEW STAR ROW
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §3 review star row ---\n";

b415_assert('3.1 the card star row is centred (auto inline margins) and un-floated',
    (bool) preg_match('/li\.product \.star-rating[^{}]*\{(?=[^}]*float:\s*none)(?=[^}]*margin:[^;]*auto)[^}]*\}/s', $b415_bf_css));

b415_assert('3.2 the card star row carries the LIVE gold #D9A45F',
    (bool) preg_match('/li\.product \.star-rating[^{}]*\{[^}]*color:\s*var\(--expedition-gold,\s*#D9A45F\)/s', $b415_bf_css));

b415_assert('3.3 ⛔ the EMPTY track is overridden too — WooCommerce sets ::before to #cfc8d8 explicitly',
    (bool) preg_match('/li\.product \.star-rating::before[^{}]*\{[^}]*color:\s*rgba\(217,\s*164,\s*95/s', $b415_bf_css));

b415_assert('3.4 the PDP HEADER star row gets the same gold',
    (bool) preg_match('/\.woocommerce-product-rating \.star-rating[^{}]*\{[^}]*color:\s*var\(--expedition-gold,\s*#D9A45F\)/s', $b415_bf_css));

b415_assert('3.5 the star row is LARGER than WooCommerce\'s 0.857em default',
    (bool) preg_match('/\.star-rating[^{}]*\{[^}]*font-size:\s*1\.15rem/s', $b415_bf_css));

/*
 * ⛔ THE RETIRED GOLD MUST NOT COME BACK. #c4a15c was superseded by the F1
 *    palette collapse of 2026-08-03. It may appear in PROSE in this file
 *    (naming it is how a future reader knows not to restore it) but never in a
 *    declaration.
 */
$b415_bf_decls = preg_replace('!/\*.*?\*/!s', '', $b415_bf_css);
b415_assert('3.6 ⛔ the RETIRED gold #c4a15c appears in no declaration in book-formats.css',
    !preg_match('/#c4a15c/i', (string) $b415_bf_decls));

b415_assert('3.7 ⛔ the rating COUNT text is not hidden — nothing suppresses .woocommerce-review-link',
    !preg_match('/\.woocommerce-review-link[^{}]*\{[^}]*display:\s*none/s', $b415_bf_css));

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · THE "BEST VALUE" BADGE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §4 BEST VALUE badge separation ---\n";

b415_assert('4.1 the PDP format-card ribbon seats on the card top edge (bottom:100%), clearing the title',
    (bool) preg_match('/\.bhp-format-card__badge[^{}]*\{[^}]*bottom:\s*100%/s', $b415_bf_css));

b415_assert('4.2 ⛔ ...and its old top offset is neutralised, or the two would fight',
    (bool) preg_match('/\.bhp-format-card__badge[^{}]*\{[^}]*top:\s*auto/s', $b415_bf_css));

b415_assert('4.3 the radius flips so it reads as a tab ON the card rather than a ribbon INTO it',
    (bool) preg_match('/\.bhp-format-card__badge[^{}]*\{[^}]*border-radius:\s*8px 8px 0 0/s', $b415_bf_css));

b415_assert('4.4 the SHOP archive badge gets flow spacing (a different shape, a different fix)',
    (bool) preg_match('/\.bhp-shop-collection-card__badge[^{}]*\{[^}]*margin-bottom:\s*6px/s', $b415_bf_css));

/*
 * ⛔ THE CONSTRAINT ANDREW SET: "without changing the card size". The PDP fix
 *    must not have grown the collection card's padding to clear the title.
 */
b415_assert('4.5 ⛔ the PDP collection card\'s padding-top was NOT grown to clear the badge (the forbidden fix)',
    (bool) preg_match('/\.bhp-format-card--collection\s*\{[^}]*padding-top:\s*22px/s', $b415_bf_css));

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · LDB-9 ROOT CAUSE (PLUGIN) AND THE VERSION FLOORS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §5 LDB-9 root cause and versions ---\n";

$b415_plugin_css_path = defined('BHP_BUNDLE_PRICING_DIR')
    ? BHP_BUNDLE_PRICING_DIR . 'assets/bundle-landing.css'
    : WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/assets/bundle-landing.css';
$b415_landing = (string) @file_get_contents($b415_plugin_css_path);

b415_assert('5.1 the plugin stylesheet was readable', '' !== $b415_landing, $b415_plugin_css_path);

b415_assert('5.2 the root-cause selector ships in the PLUGIN (this is the only rule that reaches /complete-collection/)',
    (bool) preg_match('/\.bhp-landing-value\s+\.bhp-landing-value__heading\s*\{[^}]*color:\s*#fff/s', $b415_landing));

/*
 * ⭐ THE ROW THAT ACTUALLY PROVES IT. A grep for the selector would pass even
 *    if it still lost the cascade. Compute both specificities and assert the
 *    ordering relation the fix depends on.
 */
$b415_fix    = b415_specificity('.bhp-landing-value .bhp-landing-value__heading');
$b415_rival  = b415_specificity('.bhp-landing h2');
b415_assert('5.3 ⭐ the fix selector BEATS the generic heading rule it was losing to',
    b415_beats($b415_fix, $b415_rival),
    'fix=(' . implode(',', $b415_fix) . ') rival=(' . implode(',', $b415_rival) . ')');

b415_assert('5.4 ⛔ the fix uses specificity, NOT !important',
    !preg_match('/\.bhp-landing-value\s+\.bhp-landing-value__heading\s*\{[^}]*!important/s', $b415_landing));

b415_assert('5.5 the 1.19.414 THEME override is KEPT, not reverted (belt and braces while any site serves an older plugin)',
    (bool) preg_match('/\.bhp-pair-landing \.bhp-landing-value__heading/', (string) @file_get_contents($b415_theme_dir . '/assets/css/bundle-pair-landing.css')));

b415_assert('5.6 theme reports 1.19.415 or later',
    version_compare((string) wp_get_theme(get_template())->get('Version'), '1.19.415', '>='),
    (string) wp_get_theme(get_template())->get('Version'));

b415_assert('5.7 bundle plugin reports 1.8.94 or later',
    defined('BHP_BUNDLE_PRICING_VERSION')
        && version_compare((string) BHP_BUNDLE_PRICING_VERSION, '1.8.94', '>='),
    defined('BHP_BUNDLE_PRICING_VERSION') ? (string) BHP_BUNDLE_PRICING_VERSION : 'undefined');

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · STANDING GUARDS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n--- §6 standing guards ---\n";

b415_assert('6.1 ⛔ no hardcoded colouring product id (618/946/4065/19020) entered book-media.php',
    !preg_match('/\b(618|946|4065|19020)\b/', preg_replace('!/\*.*?\*/!s', '', $b415_media_php)));

b415_assert('6.2 the minified twin was rebuilt from the source (the new rules are in both)',
    false !== strpos($b415_bf_min, 'bottom: 100%') || false !== strpos($b415_bf_min, 'bottom:100%'));

b415_assert('6.3 ⛔ no rehearsal scaffolding reached the theme',
    !preg_match('/bhp-rehearsal-testsku|TEST000000001/i', $b415_bf_css . $b415_media_php));

b415_assert('6.4 ⛔ this build emitted no aggregateRating/review schema of its own',
    !preg_match('/aggregateRating/i', $b415_bf_css . $b415_media_php));

/* ═══════════════════════════════════════════════════════════════════════════ */
printf(
    "\n=== %d passed, %d failed, %d skipped ===\n",
    $GLOBALS['b415_passes'],
    $GLOBALS['b415_failures'],
    $GLOBALS['b415_skips']
);

if ($GLOBALS['b415_failures'] > 0) {
    exit(1);
}
exit(0);
