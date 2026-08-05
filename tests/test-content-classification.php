<?php
/**
 * Phase 1D — BHP_Content_Classification test suite.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-content-classification.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * Uses a real, already-published guide-registry post (ID 76,
 * "mount-everest-facts-for-kids") to test the registry-derivation
 * bridge, and always restores its meta state to exactly what it was
 * before this test ran -- deterministic cleanup at the end of the
 * script, NOT relying on register_shutdown_function (confirmed
 * unreliable inside wp eval-file in a prior session -- see
 * docs/analytics-validation.md and this file's own cleanup below).
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cc_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_Content_Classification' ) ) {
	bhp_cc_test_assert( false, 'BHP_Content_Classification class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Enum normalization never returns an invalid value ====================
bhp_cc_test_assert( 'mixed' === BHP_Content_Classification::normalize_audience( 'not-a-real-audience' ), 'An unknown audience value falls back to the documented default (mixed)', $failures );
bhp_cc_test_assert( 'teacher' === BHP_Content_Classification::normalize_audience( 'teacher' ), 'A valid audience value passes through unchanged', $failures );
bhp_cc_test_assert( 'awareness' === BHP_Content_Classification::normalize_funnel_stage( 'bogus' ), 'An unknown funnel stage falls back to the documented default (awareness)', $failures );
bhp_cc_test_assert( 'educational' === BHP_Content_Classification::normalize_intent( '' ), 'An empty intent value falls back to the documented default (educational)', $failures );
bhp_cc_test_assert( 'related_content_engagement' === BHP_Content_Classification::normalize_primary_goal( 'xyz' ), 'An unknown primary goal falls back to the documented default', $failures );

// ==================== Flat-default fallback for content with no registry entry and no explicit meta ====================
$flat = BHP_Content_Classification::get_classification( 999999999 ); // guaranteed-nonexistent post ID -- no real post is touched
bhp_cc_test_assert( 'mixed' === $flat['audience'], 'Nonexistent/unclassified content defaults to mixed audience', $failures );
bhp_cc_test_assert( 'awareness' === $flat['funnel_stage'], 'Nonexistent/unclassified content defaults to awareness funnel stage', $failures );
bhp_cc_test_assert( false === $flat['is_classified'], 'Nonexistent/unclassified content is correctly flagged as not classified', $failures );
bhp_cc_test_assert( 'flat_default' === $flat['source'], 'Nonexistent/unclassified content reports source=flat_default (never fatal, never null)', $failures );

// ==================== Registry-derivation bridge (real, already-published post) ====================
$registry_post = get_page_by_path( 'mount-everest-facts-for-kids', OBJECT, 'post' );
bhp_cc_test_assert( null !== $registry_post, 'Test fixture: the real guide-registry post "mount-everest-facts-for-kids" exists on this site', $failures );

if ( $registry_post ) {
	// Capture the ORIGINAL meta state so it can be restored exactly,
	// regardless of what this test writes to it.
	$original_audience     = get_post_meta( $registry_post->ID, BHP_Content_Classification::META_AUDIENCE, true );
	$original_funnel_stage = get_post_meta( $registry_post->ID, BHP_Content_Classification::META_FUNNEL_STAGE, true );
	$original_intent       = get_post_meta( $registry_post->ID, BHP_Content_Classification::META_INTENT, true );

	bhp_cc_test_assert( ! BHP_Content_Classification::is_classified( $registry_post->ID ), 'Precondition: this fixture post has never been explicitly classified via the meta box (verifies the derivation path is actually being tested, not the explicit path)', $failures );

	$derived = BHP_Content_Classification::get_classification( $registry_post->ID );
	bhp_cc_test_assert( 'guide_registry_derived' === $derived['source'], 'An already-curated guide-registry post derives its classification from that registry, not the flat default', $failures );
	bhp_cc_test_assert( 'parent' === $derived['audience'], 'The registry\'s "Families" audience for this post correctly maps to the parent audience', $failures );
	bhp_cc_test_assert( 'adventure_geography' === $derived['intent'], 'The registry\'s science-geography hub correctly maps to the adventure_geography intent', $failures );
	bhp_cc_test_assert( 'mount_everest' === $derived['featured_book'], 'The registry\'s destination (mount-everest) is normalized to the underscore book-key convention (mount_everest)', $failures );
	bhp_cc_test_assert( 'visit_book_page' === $derived['primary_goal'], 'A registry entry with a real destination derives visit_book_page as the primary goal', $failures );
	bhp_cc_test_assert( false === $derived['is_classified'], 'A registry-derived classification is correctly flagged as NOT explicitly classified (distinguishes "smart default" from "an editor actually set this")', $failures );

	// ==================== Explicit classification overrides the registry derivation ====================
	update_post_meta( $registry_post->ID, BHP_Content_Classification::META_AUDIENCE, 'teacher' );
	update_post_meta( $registry_post->ID, BHP_Content_Classification::META_FUNNEL_STAGE, 'conversion' );
	$explicit = BHP_Content_Classification::get_classification( $registry_post->ID );
	bhp_cc_test_assert( 'explicit' === $explicit['source'], 'Once explicitly classified, source becomes "explicit," not "guide_registry_derived"', $failures );
	bhp_cc_test_assert( 'teacher' === $explicit['audience'], 'An explicit audience override takes precedence over the registry-derived value', $failures );
	bhp_cc_test_assert( true === $explicit['is_classified'], 'Explicitly classified content is flagged is_classified=true', $failures );

	// Deterministic cleanup -- restore this real post to EXACTLY its
	// original state, whether or not it had any of these meta keys
	// before this test ran.
	if ( '' === $original_audience ) {
		delete_post_meta( $registry_post->ID, BHP_Content_Classification::META_AUDIENCE );
	} else {
		update_post_meta( $registry_post->ID, BHP_Content_Classification::META_AUDIENCE, $original_audience );
	}
	if ( '' === $original_funnel_stage ) {
		delete_post_meta( $registry_post->ID, BHP_Content_Classification::META_FUNNEL_STAGE );
	} else {
		update_post_meta( $registry_post->ID, BHP_Content_Classification::META_FUNNEL_STAGE, $original_funnel_stage );
	}
	if ( '' === $original_intent ) {
		delete_post_meta( $registry_post->ID, BHP_Content_Classification::META_INTENT );
	} else {
		update_post_meta( $registry_post->ID, BHP_Content_Classification::META_INTENT, $original_intent );
	}

	bhp_cc_test_assert( ! BHP_Content_Classification::is_classified( $registry_post->ID ), 'Cleanup verified: the real fixture post is restored to its original (never-explicitly-classified) state', $failures );
}

// ==================== Admin column never fatals on either code path ====================
ob_start();
BHP_Content_Classification::render_admin_column( 'bhp_classification', 999999999 );
$unclassified_column_output = ob_get_clean();
bhp_cc_test_assert( '' !== $unclassified_column_output, 'Admin column renders something (not a blank/fatal) for unclassified content', $failures );

if ( $registry_post ) {
	ob_start();
	BHP_Content_Classification::render_admin_column( 'bhp_classification', $registry_post->ID );
	$derived_column_output = ob_get_clean();
	bhp_cc_test_assert( false !== strpos( $derived_column_output, 'derived from guide registry' ), 'Admin column correctly labels a registry-derived (not explicitly set) classification', $failures );
}

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL CONTENT CLASSIFICATION TESTS PASSED\n";
