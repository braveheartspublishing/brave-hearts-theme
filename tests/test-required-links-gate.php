<?php
/**
 * Required-contextual-links policy test suite (added 2026-07-11). See
 * docs/required-links-policy.md and inc/class-bhp-required-links-gate.php.
 * Every article needs a topic-hub link and a book/product link embedded
 * in real sentence prose -- the automatic end-of-article CTA does not
 * satisfy either requirement by itself.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-required-links-gate.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * All writes happen on a single disposable test post created and
 * force-deleted within this script.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! class_exists( 'BHP_CTA_Collision_Detector' ) ) {
	require_once get_template_directory() . '/inc/class-bhp-cta-collision-detector.php';
}
if ( ! class_exists( 'BHP_Required_Links_Gate' ) ) {
	require_once get_template_directory() . '/inc/class-bhp-required-links-gate.php';
}

$failures = array();

function bhp_required_links_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$test_post_id = wp_insert_post( array(
	'post_type'    => 'post',
	'post_status'  => 'draft',
	'post_title'   => 'BHP QA harness — required links gate test post (safe to delete)',
	'post_content' => '<p>Disposable test fixture for BHP_Required_Links_Gate. Not real content, deleted at the end of this test run.</p>',
) );

if ( is_wp_error( $test_post_id ) || ! $test_post_id ) {
	bhp_required_links_test_assert( false, 'Could not create a disposable test post', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Fails when both required link classes are missing ====================
$no_links_content = '<p>Just plain article text with no links of any kind, not even to an internal page.</p>';
$result = BHP_Required_Links_Gate::check( $test_post_id, $no_links_content );
bhp_required_links_test_assert( 'fail' === $result['state'], 'Content with zero links fails the gate', $failures );
bhp_required_links_test_assert( in_array( 'topic_hub', $result['missing'], true ), 'Missing topic_hub is reported', $failures );
bhp_required_links_test_assert( in_array( 'book_link', $result['missing'], true ), 'Missing book_link is reported', $failures );

// ==================== Fails when only ONE required link class is present ====================
$only_hub_content = '<p>Check out our <a href="https://braveheartspublishing.com/blog/category/adventure/">adventure collection</a> for more real stories like this one.</p>';
$result = BHP_Required_Links_Gate::check( $test_post_id, $only_hub_content );
bhp_required_links_test_assert( 'fail' === $result['state'], 'Content with only a topic-hub link (no book link) still fails the gate', $failures );
bhp_required_links_test_assert( true === $result['topic_hub_link_present'], 'The topic-hub link is correctly detected as present', $failures );
bhp_required_links_test_assert( in_array( 'book_link', $result['missing'], true ), 'The missing book_link requirement is reported', $failures );

// ==================== Passes when both required link classes are present ====================
$both_links_content = '<p>Want to keep exploring? Visit our <a href="https://braveheartspublishing.com/blog/category/adventure/">science and adventure resources for kids</a> for more real-world discoveries, or step into the rainforest with <a href="https://braveheartspublishing.com/product-category/the-amazon/">Adventures of Charlotte and Henry: The Amazon</a>.</p>';
$result = BHP_Required_Links_Gate::check( $test_post_id, $both_links_content );
bhp_required_links_test_assert( 'pass' === $result['state'], 'Content with both a topic-hub link and a book link passes the gate', $failures );
bhp_required_links_test_assert( empty( $result['missing'] ), 'No missing link classes when both are present', $failures );

// ==================== A punchy CTA-line link does NOT count toward the requirement ====================
$cta_only_content = '<p><a href="https://braveheartspublishing.com/product-category/the-amazon/">Adventures of Charlotte and Henry: The Amazon</a></p>';
$result = BHP_Required_Links_Gate::check( $test_post_id, $cta_only_content );
bhp_required_links_test_assert( 'fail' === $result['state'], 'A link that is the sole content of its own paragraph (CTA-shaped) does not satisfy the contextual book-link requirement', $failures );

// ==================== Explicit override bypasses the fail state ====================
update_post_meta( $test_post_id, '_bhp_required_links_override', 'both' );
update_post_meta( $test_post_id, '_bhp_required_links_override_reason', 'Andrew: this is a non-editorial utility page, links not applicable.' );
$result = BHP_Required_Links_Gate::check( $test_post_id, $no_links_content );
bhp_required_links_test_assert( 'overridden' === $result['state'], 'An explicit override on a post with zero links produces state "overridden", not "fail"', $failures );
bhp_required_links_test_assert( in_array( 'topic_hub', $result['overridden'], true ) && in_array( 'book_link', $result['overridden'], true ), 'Both overridden requirements are reported by name', $failures );
bhp_required_links_test_assert( ! empty( $result['override_reason'] ), 'The override reason is readable back from the gate result', $failures );

// Partial override: only topic_hub waived, book_link still required and still missing.
update_post_meta( $test_post_id, '_bhp_required_links_override', 'topic_hub' );
$result = BHP_Required_Links_Gate::check( $test_post_id, $no_links_content );
bhp_required_links_test_assert( 'fail' === $result['state'], 'A partial override (topic_hub only) still fails the gate while book_link remains missing', $failures );
bhp_required_links_test_assert( in_array( 'topic_hub', $result['overridden'], true ), 'topic_hub is reported as overridden', $failures );
bhp_required_links_test_assert( in_array( 'book_link', $result['missing'], true ), 'book_link is still reported as missing (not overridden)', $failures );

// ==================== BHP_Content_QA_Gate wiring: 'overridden' counts as a passing check state ====================
if ( class_exists( 'BHP_Content_QA_Gate' ) ) {
	delete_post_meta( $test_post_id, '_bhp_required_links_override' );
	delete_post_meta( $test_post_id, '_bhp_required_links_override_reason' );
	$brief = array( 'target_audience' => 'parents', 'funnel_stage' => 'consideration' );
	$draft = array( 'content_html' => $both_links_content );
	$seo = array( 'validation' => array( 'state' => 'pass', 'findings' => array() ) );
	$pinterest = array( 'validation' => array( 'state' => 'pass', 'findings' => array() ) );
	$qa_result = BHP_Content_QA_Gate::evaluate( $brief, $draft, $seo, $pinterest, array(), array(), $test_post_id, 'facts_article' );
	bhp_required_links_test_assert( isset( $qa_result['checks']['required_links'] ), 'BHP_Content_QA_Gate::evaluate() includes a required_links check when a post_id is supplied', $failures );
	bhp_required_links_test_assert( 'pass' === ( $qa_result['checks']['required_links']['state'] ?? '' ), 'The required_links check passes within the full QA gate when both link classes are present', $failures );
} else {
	bhp_required_links_test_assert( false, 'BHP_Content_QA_Gate must be loadable to verify the wiring', $failures );
}

// ==================== Cleanup ====================
wp_delete_post( $test_post_id, true );
$still_exists = get_post( $test_post_id );
bhp_required_links_test_assert( null === $still_exists, 'Disposable test post was fully deleted (force-deleted, not trashed) after the test', $failures );

// ==================== Summary ====================
if ( empty( $failures ) ) {
	echo "Success: All required-links gate tests passed.\n";
	exit( 0 );
}
echo count( $failures ) . " TEST(S) FAILED\n";
exit( 1 );
