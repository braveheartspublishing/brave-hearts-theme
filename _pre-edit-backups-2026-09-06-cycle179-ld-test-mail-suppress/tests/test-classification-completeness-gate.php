<?php
/**
 * Weekly Production Cycle 1 QA hardening —
 * BHP_Classification_Completeness_Gate test suite. Uses the real,
 * currently-unclassified staging drafts (posts 545/546) as negative
 * fixtures, plus a synthetic complete-classification scenario as the
 * positive fixture.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-classification-completeness-gate.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * The "complete" positive-path test temporarily sets postmeta on a
 * disposable test post created and deleted within this script (never
 * post 28/545/546) so no real draft's classification state is touched
 * by running this suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! class_exists( 'BHP_Classification_Completeness_Gate' ) ) {
	require_once get_template_directory() . '/inc/class-bhp-classification-completeness-gate.php';
}

$failures = array();

function bhp_classification_gate_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_Classification_Completeness_Gate' ) ) {
	bhp_classification_gate_test_assert( false, 'BHP_Classification_Completeness_Gate class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== LIVE FIXTURE: post 546 (Amazon facts) -- unset classification ====================
// NOTE: an earlier session turn discovered post 546's category taxonomy
// was a single malformed comma-joined term (caused by
// `wp post term set <id> category 'A,B,C'` -- WP-CLI's <term>... is a
// variadic argument requiring separate values, not one comma-joined
// string). That data defect was repaired as its own authorized task
// (see the taxonomy-repair report): post 546 now carries two real,
// separate category terms ("Adventure" and "science for kids"), and
// explicitly does NOT carry "Book recommendations" per that repair's
// requirements. The assertions below check the CURRENT, corrected data.
$post_546 = get_post( 546 );
if ( $post_546 ) {
	$result = BHP_Classification_Completeness_Gate::check( 546, 'facts_article' );
	bhp_classification_gate_test_assert( 'fail' === $result['state'], 'LIVE FIXTURE: post 546 (Amazon facts, unclassified) fails the gate as-is', $failures );
	bhp_classification_gate_test_assert( in_array( '_bhp_content_audience', $result['missing_fields'], true ), 'LIVE FIXTURE: post 546 is missing _bhp_content_audience', $failures );
	bhp_classification_gate_test_assert( in_array( '_bhp_content_funnel_stage', $result['missing_fields'], true ), 'LIVE FIXTURE: post 546 is missing _bhp_content_funnel_stage', $failures );
	$real_categories = wp_get_post_categories( 546, array( 'fields' => 'names' ) );
	bhp_classification_gate_test_assert(
		2 === count( $real_categories ) && ! in_array( true, array_map( fn( $n ) => false !== strpos( $n, ',' ), $real_categories ), true ),
		'REPAIR CONFIRMATION: post 546 now has 2 real, separate category terms (no comma-joined malformed term remains)',
		$failures
	);
	bhp_classification_gate_test_assert(
		! in_array( 'Book recommendations', $real_categories, true ),
		'REPAIR CONFIRMATION: post 546 (a facts article) is not categorized as "Book recommendations", per the taxonomy repair requirement',
		$failures
	);
}

// ==================== LIVE FIXTURE: post 545 (SEL/STEM) -- unset classification ====================
$post_545 = get_post( 545 );
if ( $post_545 ) {
	$result = BHP_Classification_Completeness_Gate::check( 545, 'book_list' );
	bhp_classification_gate_test_assert( 'fail' === $result['state'], 'LIVE FIXTURE: post 545 (SEL/STEM, unclassified) fails the gate as-is', $failures );
	bhp_classification_gate_test_assert( ! empty( $result['missing_fields'] ), 'LIVE FIXTURE: post 545 has missing classification fields', $failures );
	// book_list content type is not in the disallowed-category ruleset,
	// so no category_conflicts are expected here even though the gate
	// still fails on missing_fields.
	bhp_classification_gate_test_assert( empty( $result['category_conflicts'] ), 'LIVE FIXTURE: post 545 (a genuine book list) has no category_conflicts under the book_list rule', $failures );
}

// ==================== Synthetic positive fixture: complete classification on a disposable test post ====================
$test_post_id = wp_insert_post( array(
	'post_type'    => 'post',
	'post_status'  => 'draft',
	'post_title'   => 'BHP QA harness — classification gate test post (safe to delete)',
	'post_content' => '<p>Disposable test fixture for BHP_Classification_Completeness_Gate. Not real content, not linked from anywhere, deleted at the end of this test run.</p>',
) );

if ( is_wp_error( $test_post_id ) || ! $test_post_id ) {
	bhp_classification_gate_test_assert( false, 'Could not create a disposable test post for the positive-path fixture', $failures );
} else {
	update_post_meta( $test_post_id, '_bhp_content_audience', 'parent' );
	update_post_meta( $test_post_id, '_bhp_content_funnel_stage', 'consideration' );
	update_post_meta( $test_post_id, '_bhp_content_intent', 'educational' );
	update_post_meta( $test_post_id, '_bhp_content_primary_goal', 'visit_book_page' );

	$result = BHP_Classification_Completeness_Gate::check( $test_post_id, 'facts_article' );
	bhp_classification_gate_test_assert( empty( $result['missing_fields'] ), 'Disposable test post with all 4 required fields set has zero missing_fields', $failures );
	bhp_classification_gate_test_assert( 'pass' === $result['state'], 'Disposable test post with complete classification and no category conflict passes the gate', $failures );

	// Now attach the disallowed category to the same disposable post to
	// confirm the category-conflict rule fires independently of the
	// classification-completeness rule.
	$book_rec_term = get_term_by( 'name', 'Book recommendations', 'category' );
	if ( $book_rec_term ) {
		wp_set_post_categories( $test_post_id, array( $book_rec_term->term_id ) );
		$result_with_conflict = BHP_Classification_Completeness_Gate::check( $test_post_id, 'facts_article' );
		bhp_classification_gate_test_assert( 'fail' === $result_with_conflict['state'], 'Fully-classified test post still fails once "Book recommendations" is attached as a facts_article category', $failures );
		bhp_classification_gate_test_assert( ! empty( $result_with_conflict['category_conflicts'] ), 'The category_conflicts array is populated once the mismatch is introduced', $failures );
	} else {
		bhp_classification_gate_test_assert( false, 'Precondition: the real "Book recommendations" category term must exist on this install to run the conflict half of this test', $failures );
	}

	// Cleanup -- always runs, even if earlier assertions in this block failed.
	wp_delete_post( $test_post_id, true );
	$still_exists = get_post( $test_post_id );
	bhp_classification_gate_test_assert( null === $still_exists, 'Disposable test post was fully deleted (force-deleted, not trashed) after the test', $failures );
}

// ==================== Summary ====================
if ( empty( $failures ) ) {
	echo "Success: All classification completeness gate tests passed.\n";
	exit( 0 );
}
echo count( $failures ) . " TEST(S) FAILED\n";
exit( 1 );
