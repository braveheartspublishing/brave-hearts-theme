<?php
/**
 * Kirkus credibility component smoke test.
 *
 * No PHPUnit exists in this theme (see docs/RUNBOOK.md) -- verification
 * has always been wp-cli-driven. This follows that convention but goes
 * beyond a syntax check: it exercises the real render functions against
 * the real WordPress bootstrap and asserts on the actual output.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-kirkus-component.php --user=1
 *
 * Exits non-zero on any failure so it can gate a deploy.
 */
defined('ABSPATH') || exit;

$failures = [];

function bhp_test_assert(&$failures, $label, $condition) {
    if ($condition) {
        WP_CLI::log("PASS: $label");
    } else {
        WP_CLI::warning("FAIL: $label");
        $failures[] = $label;
    }
}

// ---------------------------------------------------------------------
// 1. Exact reviewed-title / attribution data
// ---------------------------------------------------------------------
$data = bhp_get_kirkus_review_data();
bhp_test_assert($failures, 'reviewed_title is exactly the Mariana Trench title',
    $data['reviewed_title'] === 'Adventures of Charlotte & Henry: The Mariana Trench');
bhp_test_assert($failures, 'attribution is exactly "Kirkus Reviews"',
    $data['attribution'] === 'Kirkus Reviews');
bhp_test_assert($failures, 'quote matches the approved excerpt exactly',
    $data['quote'] === 'Simple but effective storytelling to spark children’s curiosity and appreciation for the wider natural world.');
bhp_test_assert($failures, 'review_url is the official kirkusreviews.com URL',
    $data['review_url'] === 'https://www.kirkusreviews.com/book-reviews/andrew-signore/adventures-of-charlotte-and-henry/');

// ---------------------------------------------------------------------
// 2. Expanded mode renders quote, attribution, title, and link
// ---------------------------------------------------------------------
$expanded = bhp_render_kirkus_credibility('expanded', ['source' => 'test', 'show_link' => true]);
bhp_test_assert($failures, 'expanded mode contains a <blockquote>', strpos($expanded, '<blockquote') !== false);
bhp_test_assert($failures, 'expanded mode contains the approved quote text', strpos($expanded, 'Simple but effective storytelling') !== false);
bhp_test_assert($failures, 'expanded mode contains "Kirkus Reviews" attribution', strpos($expanded, 'Kirkus Reviews') !== false);
bhp_test_assert($failures, 'expanded mode contains the reviewed title', strpos($expanded, 'The Mariana Trench') !== false);
bhp_test_assert($failures, 'expanded mode links to the official review URL', strpos($expanded, 'kirkusreviews.com') !== false);
bhp_test_assert($failures, 'expanded mode link has accessible, descriptive text (not just "click here")', strpos($expanded, 'Read the full Kirkus review of') !== false);
bhp_test_assert($failures, 'expanded mode link opens in a new tab with rel=noopener', strpos($expanded, 'rel="noopener noreferrer"') !== false);
bhp_test_assert($failures, 'expanded mode has a stable click-tracking data attribute', strpos($expanded, 'data-bhp-event="kirkus_review_link_click"') !== false);
bhp_test_assert($failures, 'expanded mode has a stable impression-tracking data attribute', strpos($expanded, 'data-bhp-impression-event="kirkus_component_impression"') !== false);

// ---------------------------------------------------------------------
// 3. show_link=false omits the link entirely
// ---------------------------------------------------------------------
$no_link = bhp_render_kirkus_credibility('expanded', ['source' => 'test', 'show_link' => false]);
bhp_test_assert($failures, 'show_link=false omits the review link', strpos($no_link, 'kirkusreviews.com') === false);
bhp_test_assert($failures, 'show_link=false still shows the quote', strpos($no_link, 'Simple but effective storytelling') !== false);

// ---------------------------------------------------------------------
// 4. Compact mode omits the full quote (never repeat it on every card)
// ---------------------------------------------------------------------
$compact = bhp_render_kirkus_credibility('compact', ['source' => 'shop_card', 'show_link' => false]);
bhp_test_assert($failures, 'compact mode does NOT contain the full quote text', strpos($compact, 'Simple but effective storytelling') === false);
bhp_test_assert($failures, 'compact mode contains "Kirkus Reviews" attribution', strpos($compact, 'Kirkus Reviews') !== false);
bhp_test_assert($failures, 'compact mode has kirkus-credibility--compact class', strpos($compact, 'kirkus-credibility--compact') !== false);

// ---------------------------------------------------------------------
// 5. series_note mode never implies Everest/Amazon were reviewed
// ---------------------------------------------------------------------
$series_note = bhp_render_kirkus_credibility('series_note', ['source' => 'test', 'show_link' => true]);
bhp_test_assert($failures, 'series_note mentions the actual reviewed title', strpos($series_note, 'The Mariana Trench') !== false);
bhp_test_assert($failures, 'series_note does NOT contain the full quote (would overstate the claim)', strpos($series_note, 'Simple but effective storytelling') === false);
bhp_test_assert($failures, 'series_note does not mention Everest or Amazon by name (would wrongly imply they were reviewed)',
    stripos($series_note, 'Everest') === false && stripos($series_note, 'Amazon') === false);

// ---------------------------------------------------------------------
// 6. Escaping: a malicious value in the data filter must never render raw
// ---------------------------------------------------------------------
$inject = function ($data) {
    $data['quote'] = '<script>alert(1)</script>';
    $data['attribution'] = '"><img src=x onerror=alert(1)>';
    return $data;
};
add_filter('bhp_kirkus_review_data', $inject);
$escaped = bhp_render_kirkus_credibility('expanded', ['source' => 'test', 'show_link' => false]);
remove_filter('bhp_kirkus_review_data', $inject);
bhp_test_assert($failures, 'injected <script> is escaped, not rendered raw', strpos($escaped, '<script>alert(1)</script>') === false);
bhp_test_assert($failures, 'injected onerror payload is neutralized -- no live <img> tag exists in the output (angle brackets are HTML-entity-encoded)', strpos($escaped, '<img') === false && strpos($escaped, '&lt;img') !== false);

// ---------------------------------------------------------------------
// 7. Missing configuration renders nothing (fails safe, not broken)
// ---------------------------------------------------------------------
$blank = function ($data) {
    $data['quote'] = '';
    return $data;
};
add_filter('bhp_kirkus_review_data', $blank);
$empty_output = bhp_render_kirkus_credibility('expanded', ['source' => 'test']);
remove_filter('bhp_kirkus_review_data', $blank);
bhp_test_assert($failures, 'missing quote configuration renders nothing (no broken partial markup)', trim($empty_output) === '');

// ---------------------------------------------------------------------
// 8. No Review / AggregateRating schema is ever emitted by this component
// ---------------------------------------------------------------------
bhp_test_assert($failures, 'no itemprop="reviewBody" microdata', strpos($expanded, 'itemprop="reviewBody"') === false);
bhp_test_assert($failures, 'no itemtype Review schema', stripos($expanded, 'schema.org/Review') === false);
bhp_test_assert($failures, 'no AggregateRating schema', stripos($expanded, 'AggregateRating') === false);
bhp_test_assert($failures, 'no inline JSON-LD script tag', strpos($expanded, 'application/ld+json') === false);

// ---------------------------------------------------------------------
// 9. Adventure-key routing: only Mariana Trench's SKU maps to the review
// ---------------------------------------------------------------------
bhp_test_assert($failures, 'Mariana Trench SKU maps to mariana_trench', bhp_get_adventure_key_from_sku('BHP-MT-PB') === 'mariana_trench');
bhp_test_assert($failures, 'Everest SKU does not map to mariana_trench (must get series_note, not the quote)', bhp_get_adventure_key_from_sku('BHP-EVE-PB') !== 'mariana_trench');
bhp_test_assert($failures, 'Unrelated SKU maps to nothing (no Kirkus section at all)', bhp_get_adventure_key_from_sku('SOME-OTHER-SKU') === '');

// ---------------------------------------------------------------------
// Result
// ---------------------------------------------------------------------
if ($failures) {
    WP_CLI::error(sprintf('%d Kirkus component test(s) failed: %s', count($failures), implode('; ', $failures)));
} else {
    WP_CLI::success('All Kirkus credibility component tests passed.');
}
