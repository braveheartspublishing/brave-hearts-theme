<?php
/**
 * Weekly Production Cycle 1 QA hardening — BHP_CTA_Collision_Detector
 * test suite. Uses the real live guide registry and the real, current
 * staging drafts (posts 28, 545, 546) as fixtures, since the whole
 * point of this detector is registry-membership-dependent behavior
 * that only a real WordPress environment can exercise correctly.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-cta-collision-detector.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * Read-only: get_post()/get_post_field() only, no writes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! class_exists( 'BHP_CTA_Collision_Detector' ) ) {
	require_once get_template_directory() . '/inc/class-bhp-cta-collision-detector.php';
}

$failures = array();

function bhp_cta_collision_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_CTA_Collision_Detector' ) ) {
	bhp_cta_collision_test_assert( false, 'BHP_CTA_Collision_Detector class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}
if ( ! function_exists( 'bhp_get_guide_registry' ) ) {
	bhp_cta_collision_test_assert( false, 'bhp_get_guide_registry() must be loadable (functions.php)', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Precondition: confirm registry membership assumptions ====================
$registry = bhp_get_guide_registry();
bhp_cta_collision_test_assert( array_key_exists( 'best-books-for-7-year-olds', $registry ), 'Precondition: best-books-for-7-year-olds is a real guide-registry member', $failures );
bhp_cta_collision_test_assert( ! array_key_exists( 'best-sel-and-stem-books-for-kids', $registry ), 'Precondition: best-sel-and-stem-books-for-kids is NOT a registry member (new draft)', $failures );
bhp_cta_collision_test_assert( ! array_key_exists( 'amazon-rainforest-facts-for-kids', $registry ), 'Precondition: amazon-rainforest-facts-for-kids is NOT a registry member (new draft)', $failures );

// ==================== LIVE FIXTURE: post 28 (registry member + manual CTA baked in body = crowding, not exact duplicate) ====================
$content_28 = get_post_field( 'post_content', 28 );
if ( ! empty( $content_28 ) ) {
	$result = BHP_CTA_Collision_Detector::check( 28, $content_28 );
	bhp_cta_collision_test_assert( true === $result['registry_member'], 'LIVE FIXTURE: post 28 correctly detected as a registry member', $failures );
	bhp_cta_collision_test_assert( false === $result['automatic_cta_will_fire'], 'LIVE FIXTURE: post 28 correctly predicted to NOT receive the automatic CTA-engine fallback (gets curated guide-continuation instead)', $failures );
	bhp_cta_collision_test_assert( true === $result['manual_cta_present'], 'LIVE FIXTURE: post 28 correctly detected as having a manual CTA/product-path block baked into its body', $failures );
	bhp_cta_collision_test_assert( 'crowding' === $result['collision_state'], "LIVE FIXTURE: post 28's collision_state is 'crowding' (manual CTA + curated block, not an exact duplicate)", $failures );
}

// ==================== LIVE FIXTURE: post 545 (non-registry + manual CTA-engine render baked in = duplicate) ====================
$content_545 = get_post_field( 'post_content', 545 );
if ( ! empty( $content_545 ) ) {
	$result = BHP_CTA_Collision_Detector::check( 545, $content_545 );
	bhp_cta_collision_test_assert( false === $result['registry_member'], 'LIVE FIXTURE: post 545 correctly detected as NOT a registry member', $failures );
	bhp_cta_collision_test_assert( true === $result['automatic_cta_will_fire'], 'LIVE FIXTURE: post 545 correctly predicted to receive the automatic CTA-engine fallback', $failures );
	bhp_cta_collision_test_assert( true === $result['manual_cta_present'], 'LIVE FIXTURE: post 545 correctly detected as having a manual CTA baked into its body (the rendered adventure_kit_signup card)', $failures );
	bhp_cta_collision_test_assert( 'duplicate' === $result['collision_state'], "LIVE FIXTURE: post 545's collision_state is 'duplicate' (real near-duplicate CTA risk)", $failures );
	bhp_cta_collision_test_assert( in_array( 'manual_only', $result['required_decision'], true ), 'LIVE FIXTURE: post 545 collision requires an explicit resolution decision to be recorded', $failures );
}

// ==================== LIVE FIXTURE: post 546 (non-registry + plain contextual product link = REQUIRED link, not a collision) ====================
// REVISED 2026-07-11: after the required-contextual-links policy, a
// plain in-body sentence link to a product-category page is no longer
// treated as a manual CTA by itself -- it's the new required behavior.
// See docs/required-links-policy.md and the class docblock for why this
// assertion changed from the original "duplicate" expectation.
$content_546 = get_post_field( 'post_content', 546 );
if ( ! empty( $content_546 ) ) {
	$result = BHP_CTA_Collision_Detector::check( 546, $content_546 );
	bhp_cta_collision_test_assert( false === $result['registry_member'], 'LIVE FIXTURE: post 546 correctly detected as NOT a registry member', $failures );
	bhp_cta_collision_test_assert( false === $result['manual_cta_present'], 'REVISED: post 546\'s plain contextual product-path link is NOT flagged as a manual CTA (no CTA-engine markup, no promotional phrasing, real surrounding prose)', $failures );
	bhp_cta_collision_test_assert( 'none' === $result['collision_state'], "REVISED: post 546's collision_state is 'none' -- a contextual link and the automatic CTA are expected to coexist", $failures );
	bhp_cta_collision_test_assert( true === $result['contextual_book_link_present'], 'LIVE FIXTURE: post 546 correctly detected as having a contextual book/product link', $failures );
}

// ==================== Synthetic: no collision when no manual CTA present ====================
$no_cta_fixture_id = 546; // reuse a real, non-registry post ID for registry lookup context
$clean_content = '<p>Just plain article body text with no CTA-shaped markup and no product-category link anywhere in it.</p>';
$result = BHP_CTA_Collision_Detector::check( $no_cta_fixture_id, $clean_content );
bhp_cta_collision_test_assert( false === $result['manual_cta_present'], 'Synthetic clean content (no CTA markup) is correctly detected as having no manual CTA', $failures );
bhp_cta_collision_test_assert( 'none' === $result['collision_state'], 'Synthetic clean content on a non-registry post has collision_state "none" (the automatic CTA will still fire, but nothing collides with it)', $failures );
bhp_cta_collision_test_assert( empty( $result['required_decision'] ), 'No collision means no required_decision is forced', $failures );
bhp_cta_collision_test_assert( false === $result['contextual_book_link_present'], 'Synthetic clean content with no links has no contextual book link detected', $failures );

// ==================== Contextual vs. promotional link distinction (2026-07-11 policy) ====================
// A plain sentence link to the topic-hub AND a book/product page, both
// embedded in real prose -- exactly the pattern now required on every
// article -- must never be flagged as a manual CTA or collision.
$contextual_fixture = '<p>Want to keep exploring? Visit our <a href="https://braveheartspublishing.com/blog/category/adventure/">science and adventure resources for kids</a> for more real-world discoveries, or step into the rainforest with <a href="https://braveheartspublishing.com/product-category/the-amazon/">Adventures of Charlotte and Henry: The Amazon</a>.</p>';
$result = BHP_CTA_Collision_Detector::check( 'amazon-rainforest-facts-for-kids', $contextual_fixture );
bhp_cta_collision_test_assert( false === $result['manual_cta_present'], 'A required contextual paragraph (topic-hub link + book link, real prose) is NOT flagged as a manual CTA', $failures );
bhp_cta_collision_test_assert( 'none' === $result['collision_state'], 'A required contextual paragraph produces collision_state "none"', $failures );
bhp_cta_collision_test_assert( true === $result['contextual_topic_hub_link_present'], 'The topic-hub link in the required contextual paragraph is detected', $failures );
bhp_cta_collision_test_assert( true === $result['contextual_book_link_present'], 'The book link in the required contextual paragraph is detected', $failures );

// A promotional "Buy Now" link (even with an inline <strong> wrapper and
// no final-cta class) IS still correctly flagged as a manual CTA.
$promo_fixture = '<p>Ready for the adventure? <a href="https://braveheartspublishing.com/product-category/the-amazon/"><strong>Buy Now →</strong></a></p>';
$result = BHP_CTA_Collision_Detector::check( 'amazon-rainforest-facts-for-kids', $promo_fixture );
bhp_cta_collision_test_assert( true === $result['manual_cta_present'], 'A "Buy Now" promotional link (even nested inside <strong>) is still flagged as a manual CTA', $failures );

// A link that is the SOLE content of its own paragraph (a "punchy CTA
// line") is flagged as promotional even with plain, non-imperative
// anchor text -- distinct from a link embedded in a real sentence.
$punchy_line_fixture = '<p><a href="https://braveheartspublishing.com/product-category/the-amazon/">Adventures of Charlotte and Henry: The Amazon</a></p>';
$result = BHP_CTA_Collision_Detector::check( 'amazon-rainforest-facts-for-kids', $punchy_line_fixture );
bhp_cta_collision_test_assert( true === $result['manual_cta_present'], 'A link that is the sole content of its own paragraph (no surrounding prose) is flagged as a promotional CTA line, not a contextual link', $failures );

// ==================== Malformed CTA markup detection ====================
$malformed_cta_fixture = '<p class="final-cta">Buy the book now!</p>'; // has the styling class, but not a real engine render
$result = BHP_CTA_Collision_Detector::check( 546, $malformed_cta_fixture );
bhp_cta_collision_test_assert( ! empty( $result['malformed_cta_markup'] ), 'A "final-cta" class without the matching real CTA-engine id/class pair is flagged as malformed', $failures );

$real_cta_render_fixture = '<section id="bhp-cta-adventure_kit_signup-blog_inline" class="final-cta section bhp-contextual-cta bhp-contextual-cta--blog_inline"><h2>Get the Kit</h2></section>';
$result = BHP_CTA_Collision_Detector::check( 546, $real_cta_render_fixture );
bhp_cta_collision_test_assert( empty( $result['malformed_cta_markup'] ), 'A real, complete CTA-engine render (matching id/class pair) is NOT flagged as malformed', $failures );

// ==================== Summary ====================
if ( empty( $failures ) ) {
	echo "Success: All CTA collision detector tests passed.\n";
	exit( 0 );
}
echo count( $failures ) . " TEST(S) FAILED\n";
exit( 1 );
