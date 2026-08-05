<?php
/**
 * Weekly Production Cycle 1 QA hardening — BHP_Content_HTML_Sanitizer
 * test suite. Fixtures below include synthetic reproductions of the
 * exact patterns found in the real "10 Amazon Rainforest Facts for
 * Kids" draft (post 546) plus LIVE negative fixtures pulled directly
 * from the current, still-defective staging drafts (posts 545/546) so
 * this test fails honestly if those drafts are ever "fixed" by
 * accident without actually clearing the underlying defects. See
 * docs/weekly-cycle-1-qa-failure-audit.md.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-content-html-sanitizer.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * Read-only: get_post_field() only, no writes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

// Self-contained require -- this validator is NOT yet wired into the
// live functions.php require chain (intentionally not deployed), so
// this test loads it directly rather than relying on class_exists()
// against the theme's normal bootstrap.
if ( ! class_exists( 'BHP_Content_HTML_Sanitizer' ) ) {
	require_once get_template_directory() . '/inc/class-bhp-content-html-sanitizer.php';
}

$failures = array();

function bhp_html_sanitizer_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_Content_HTML_Sanitizer' ) ) {
	bhp_html_sanitizer_test_assert( false, 'BHP_Content_HTML_Sanitizer class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Synthetic pattern fixtures ====================

$sqsrte_fixture = '<p style="white-space:pre-wrap;" class="sqsrte-large" data-rte-preserve-empty="true">Real Amazon rainforest fact text.</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $sqsrte_fixture );
bhp_html_sanitizer_test_assert( ! empty( $violations ), 'Squarespace sqsrte-large fixture is rejected', $failures );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'sqsrte_class' ) ), 'Violation specifically names sqsrte_class', $failures );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'data_rte_preserve' ) ), 'Violation specifically names data_rte_preserve', $failures );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'inline_white_space' ) ), 'Violation specifically names inline_white_space', $failures );

$nested_p_fixture = '<p style="white-space:pre-wrap;"><p style="white-space:pre-wrap;">If this made your kid want to go there, visit the series.</p><p><em>Big Places. Brave Hearts.</em></p></p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $nested_p_fixture );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'Nested <p><p>' ) ), 'Nested <p><p> fixture (matches the real Amazon-draft closing-block defect) is rejected', $failures );

$empty_p_fixture = '<p>Real content.</p><p></p><p>&nbsp;</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $empty_p_fixture );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'Empty <p></p>' ) ), 'Empty/whitespace-only <p> elements are rejected', $failures );

$unclosed_fixture = '<p>Opens a paragraph<div>and a div that never closes';
$violations = BHP_Content_HTML_Sanitizer::validate( $unclosed_fixture );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'Unclosed tag' ) ), 'Unclosed tags at end of document are rejected', $failures );

$mismatched_fixture = '<p>Text<strong>bold</p></strong>';
$violations = BHP_Content_HTML_Sanitizer::validate( $mismatched_fixture );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'Mismatched closing tag' ) ), 'Mismatched closing tags are rejected', $failures );

$h1_fixture = '<h1>A stray heading that duplicates the theme title H1</h1><p>Body text.</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $h1_fixture );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, '<h1>' ) ), 'A stray body <h1> is rejected', $failures );

$www_fixture = '<p>Read more: <a href="https://www.braveheartspublishing.com/blog/some-post">this guide</a>.</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $www_fixture );
bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'www hostname' ) ), 'Internal www-hostname links are rejected', $failures );

// ==================== Staging-hostname leakage (new) ====================

$staging_body_fixture = '<p>Preview this on <a href="https://staging2.braveheartspublishing.com/blog/amazon-rainforest-facts-for-kids/">staging</a> first.</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $staging_body_fixture );
bhp_html_sanitizer_test_assert(
	(bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'staging2.braveheartspublishing.com' ) && false !== strpos( $v, 'body content' ) ),
	'Known staging hostname (staging2.braveheartspublishing.com) in body content is rejected',
	$failures
);

$generic_staging_fixture = '<p>See <a href="https://staging7.braveheartspublishing.com/blog/test/">staging7</a>.</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $generic_staging_fixture );
bhp_html_sanitizer_test_assert(
	(bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'staging7.braveheartspublishing.com' ) ),
	'An unrecognized but staging-pattern hostname (staging7...) is also caught by the generic pattern',
	$failures
);

$plain_text_leakage_fixture = '<p>Once this goes live, replace staging2.braveheartspublishing.com references before publishing.</p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $plain_text_leakage_fixture );
bhp_html_sanitizer_test_assert(
	! empty( array_filter( $violations, fn( $v ) => false !== strpos( $v, 'staging2.braveheartspublishing.com' ) ) ),
	'Staging hostname leaking in plain body text (not inside an <a> tag) is still caught',
	$failures
);

$metadata_leakage_fixture = array(
	'seo_title'           => 'Amazon Rainforest Facts %sep% Brave Hearts Publishing (staging2.braveheartspublishing.com preview)',
	'cta_destination_url'  => 'https://staging2.braveheartspublishing.com/product-category/the-amazon/',
);
$violations = BHP_Content_HTML_Sanitizer::validate( '<p>Clean body.</p>', $metadata_leakage_fixture );
bhp_html_sanitizer_test_assert(
	(bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, "metadata field 'seo_title'" ) ),
	'Staging hostname leaking into an SEO title metadata field is caught',
	$failures
);
bhp_html_sanitizer_test_assert(
	(bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, "metadata field 'cta_destination_url'" ) ),
	'Staging hostname leaking into a CTA destination URL metadata field is caught',
	$failures
);

// ==================== LIVE negative fixtures: real, currently-defective staging drafts ====================

$amazon_draft = get_post_field( 'post_content', 546 );
bhp_html_sanitizer_test_assert( ! empty( $amazon_draft ), 'Precondition: post 546 (Amazon facts draft) exists and has content on this install', $failures );
if ( ! empty( $amazon_draft ) ) {
	$violations = BHP_Content_HTML_Sanitizer::validate( $amazon_draft );
	bhp_html_sanitizer_test_assert( ! empty( $violations ), 'LIVE FIXTURE: the real, current Amazon facts draft (post 546) is correctly rejected as-is', $failures );
	bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'sqsrte_class' ) ), 'LIVE FIXTURE: real Amazon draft still carries sqsrte contamination', $failures );
	bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'Nested <p><p>' ) ), 'LIVE FIXTURE: real Amazon draft still has the nested <p><p> closing-block defect', $failures );
}

$selstem_draft = get_post_field( 'post_content', 545 );
bhp_html_sanitizer_test_assert( ! empty( $selstem_draft ), 'Precondition: post 545 (SEL/STEM draft) exists and has content on this install', $failures );
if ( ! empty( $selstem_draft ) ) {
	$violations = BHP_Content_HTML_Sanitizer::validate( $selstem_draft );
	bhp_html_sanitizer_test_assert( ! empty( $violations ), 'LIVE FIXTURE: the real, current SEL/STEM draft (post 545) is correctly rejected as-is', $failures );
	bhp_html_sanitizer_test_assert( (bool) array_filter( $violations, fn( $v ) => false !== strpos( $v, 'sqsrte_class' ) ), 'LIVE FIXTURE: real SEL/STEM draft still carries sqsrte contamination', $failures );
}

// ==================== Positive fixture: clean, corrected content passes ====================
// A synthetic reproduction of what clean WordPress-native content for
// this site looks like -- no Squarespace classes, no inline styles, no
// nested/empty paragraphs, no www or staging hostnames, real internal
// link, balanced tags, no stray body H1. This does NOT modify or
// replace the real articles' prose (Andrew's explicit instruction) --
// it is a minimal structural fixture proving the validator accepts
// genuinely clean input.
$clean_fixture = '<p>The Amazon River carries more water than any other river in the world.</p>'
	. '<h4>A real fact heading</h4>'
	. '<p>A real fact body with a proper <a href="https://braveheartspublishing.com/product-category/the-amazon/">internal link</a> and no leftover migration markup.</p>'
	. '<h3>One More Thing</h3>'
	. '<p><em>Big Places. Brave Hearts.</em></p>';
$violations = BHP_Content_HTML_Sanitizer::validate( $clean_fixture );
bhp_html_sanitizer_test_assert( empty( $violations ), 'Clean, properly-structured, non-www, non-staging content produces zero violations', $failures );

// ==================== Summary ====================
if ( empty( $failures ) ) {
	echo "Success: All content HTML sanitizer tests passed.\n";
	exit( 0 );
}
echo count( $failures ) . " TEST(S) FAILED\n";
exit( 1 );
