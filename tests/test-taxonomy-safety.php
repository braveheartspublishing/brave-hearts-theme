<?php
/**
 * Weekly Production Cycle 1 QA hardening — BHP_Taxonomy_Safety test
 * suite. Added after a `wp post term set <id> <taxonomy> 'A,B,C'`
 * comma-joined-string command silently created a single malformed
 * term instead of assigning three real terms, reaching production
 * (post 90) before being caught. See the taxonomy-repair session
 * report for the full incident and docs/weekly-cycle-1-qa-failure-audit.md.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-taxonomy-safety.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * All writes in this suite happen on a single disposable test post
 * created and force-deleted within this script -- no real post's
 * taxonomy is touched by running this suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! class_exists( 'BHP_Taxonomy_Safety' ) ) {
	require_once get_template_directory() . '/inc/class-bhp-taxonomy-safety.php';
}

$failures = array();

function bhp_taxonomy_safety_test_assert( $condition, $label, array &$failures ) {
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
	'post_title'   => 'BHP QA harness — taxonomy safety test post (safe to delete)',
	'post_content' => '<p>Disposable test fixture for BHP_Taxonomy_Safety. Not real content, not linked from anywhere, deleted at the end of this test run.</p>',
) );

if ( is_wp_error( $test_post_id ) || ! $test_post_id ) {
	bhp_taxonomy_safety_test_assert( false, 'Could not create a disposable test post', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Three requested tags become three existing tag assignments ====================
$result = BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'post_tag', array( 97, 90, 36 ) );
bhp_taxonomy_safety_test_assert( true === $result['success'], 'Three requested existing tag IDs (97, 90, 36) assign successfully', $failures );
bhp_taxonomy_safety_test_assert( array( 36, 90, 97 ) === $result['actual_ids'], 'Post-write readback confirms exactly the 3 requested tag IDs, sorted, no extras', $failures );

// Same result by NAME instead of ID, on a fresh assignment.
$result = BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'post_tag', array( 'bridge books for kids', 'picture books to chapter books', 'early chapter books' ) );
bhp_taxonomy_safety_test_assert( true === $result['success'], 'The same three tags requested by NAME instead of ID also assign successfully', $failures );
bhp_taxonomy_safety_test_assert( array( 36, 90, 97 ) === $result['actual_ids'], 'Name-based resolution produces the identical verified ID set', $failures );

// ==================== A comma-joined string is rejected ====================
$result = BHP_Taxonomy_Safety::resolve_terms( 'post_tag', array( 'bridge books for kids,picture books to chapter books,early chapter books' ) );
bhp_taxonomy_safety_test_assert( false === $result['success'], 'A single comma-joined string (the exact original defect shape) is rejected by resolve_terms()', $failures );
bhp_taxonomy_safety_test_assert( empty( $result['resolved_ids'] ), 'No term IDs are returned when a comma-joined string is rejected', $failures );
bhp_taxonomy_safety_test_assert(
	(bool) array_filter( $result['errors'], fn( $e ) => false !== strpos( $e, 'comma' ) ),
	'The rejection error specifically names the comma as the reason',
	$failures
);

// Confirm assign_terms() also refuses to write anything when given a comma-joined string.
$before_ids = wp_get_post_terms( $test_post_id, 'post_tag', array( 'fields' => 'ids' ) );
sort( $before_ids );
$result = BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'post_tag', array( 'a,b,c' ) );
bhp_taxonomy_safety_test_assert( false === $result['success'], 'assign_terms() also refuses a comma-joined string rather than writing anything', $failures );
$after_ids = wp_get_post_terms( $test_post_id, 'post_tag', array( 'fields' => 'ids' ) );
sort( $after_ids );
bhp_taxonomy_safety_test_assert( $before_ids === $after_ids, 'The post is left completely unmodified after a rejected comma-joined request (no partial write)', $failures );

// ==================== Missing terms cause a failure rather than term creation ====================
$fake_name = 'BHP QA harness nonexistent tag ' . wp_generate_password( 8, false );
$tag_count_before = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
$result = BHP_Taxonomy_Safety::resolve_terms( 'post_tag', array( $fake_name ) );
bhp_taxonomy_safety_test_assert( false === $result['success'], 'A term name that does not exist fails resolution', $failures );
$tag_count_after = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
bhp_taxonomy_safety_test_assert( $tag_count_before === $tag_count_after, 'No new tag term was created as a side effect of the failed resolution (term creation is prohibited)', $failures );

$result = BHP_Taxonomy_Safety::resolve_terms( 'post_tag', array( 999999 ) );
bhp_taxonomy_safety_test_assert( false === $result['success'], 'A numeric term ID that does not exist also fails resolution rather than being silently accepted', $failures );

// ==================== Unexpected extra terms are detected ====================
// Simulate a scenario where something else adds a term to the post
// after resolution but the caller still expects only the original set
// -- assign_terms()'s own readback should catch a mismatch if the
// underlying write function ever returns success while leaving stray
// terms attached (e.g. append=true bugs). We verify this by directly
// checking that wp_set_object_terms() is called with append=false
// (replace, not append) by confirming a second assign_terms() call with
// a SMALLER set actually shrinks the assigned terms rather than adding
// to them.
BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'post_tag', array( 97, 90, 36 ) );
$result = BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'post_tag', array( 97 ) );
bhp_taxonomy_safety_test_assert( true === $result['success'], 'Re-assigning a smaller set (just tag 97) succeeds', $failures );
bhp_taxonomy_safety_test_assert( array( 97 ) === $result['actual_ids'], 'The post now has exactly tag 97 -- confirms terms are REPLACED, not appended, so stray extra terms from a prior assignment cannot survive undetected', $failures );

// ==================== The approved post 90 taxonomy set passes ====================
$result_cat = BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'category', array( 49 ) );
bhp_taxonomy_safety_test_assert( true === $result_cat['success'], 'The approved post-90 category (ID 49, Bridge Books) assigns and verifies successfully', $failures );
bhp_taxonomy_safety_test_assert( array( 49 ) === $result_cat['actual_ids'], 'Exactly category 49 is assigned, nothing else', $failures );

$result_tags = BHP_Taxonomy_Safety::assign_terms( $test_post_id, 'post_tag', array( 97, 90, 36 ) );
bhp_taxonomy_safety_test_assert( true === $result_tags['success'], 'The approved post-90 tag set (IDs 97, 90, 36) assigns and verifies successfully', $failures );
bhp_taxonomy_safety_test_assert( array( 36, 90, 97 ) === $result_tags['actual_ids'], 'Exactly the 3 approved tag IDs are assigned, matching the real corrected production post 90 state', $failures );

// ==================== Cleanup ====================
wp_delete_post( $test_post_id, true );
$still_exists = get_post( $test_post_id );
bhp_taxonomy_safety_test_assert( null === $still_exists, 'Disposable test post was fully deleted (force-deleted, not trashed) after the test', $failures );

// ==================== Summary ====================
if ( empty( $failures ) ) {
	echo "Success: All taxonomy safety tests passed.\n";
	exit( 0 );
}
echo count( $failures ) . " TEST(S) FAILED\n";
exit( 1 );
