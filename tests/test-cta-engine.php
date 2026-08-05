<?php
/**
 * Phase 1D — BHP_CTA_Engine test suite.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-cta-engine.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * All assertions call select_cta()/render() directly with plain context
 * arrays -- no real post is created or modified, so no cleanup is
 * required for the scoring tests. The one render() test that touches
 * output buffering is read-only (ob_start/ob_get_clean around a render
 * call) and writes nothing to the database, so still no cleanup state
 * to restore -- confirmed by inspection of BHP_CTA_Engine::render(),
 * which only calls get_template_part() and has no side effects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cta_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_CTA_Engine' ) ) {
	bhp_cta_test_assert( false, 'BHP_CTA_Engine class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Registry shape ====================
$registry = BHP_CTA_Engine::registry();
bhp_cta_test_assert( is_array( $registry ) && count( $registry ) >= 7, 'Registry contains at least the 7 required destination types', $failures );
foreach ( $registry as $id => $def ) {
	bhp_cta_test_assert( ! empty( $def['headline'] ) && ! empty( $def['cta_label'] ), "Registry entry '{$id}' has non-empty headline and CTA label (no blank/placeholder copy)", $failures );
	bhp_cta_test_assert( is_callable( $def['resolve_url'] ), "Registry entry '{$id}' has a callable resolve_url", $failures );
}

// ==================== Audience-matched selection ====================
$teacher_context = array(
	'audience'     => 'teacher',
	'funnel_stage' => 'awareness',
	'intent'       => 'teacher_resource',
);
$selected = BHP_CTA_Engine::select_cta( $teacher_context );
bhp_cta_test_assert( null !== $selected, 'A teacher-audience context always resolves to a CTA (never null)', $failures );
bhp_cta_test_assert( 'teacher_resource' === $selected['id'], 'A teacher/awareness/teacher_resource context selects the teacher_resource CTA over generic alternatives', $failures );
bhp_cta_test_assert( false !== strpos( $selected['url'], '/teachers/' ), 'The selected teacher_resource CTA resolves to the real /teachers/ URL', $failures );

$parent_kit_context = array(
	'audience'     => 'parent',
	'funnel_stage' => 'awareness',
	'intent'       => 'reading_development',
);
$selected2 = BHP_CTA_Engine::select_cta( $parent_kit_context );
bhp_cta_test_assert( 'adventure_kit_signup' === $selected2['id'], 'A parent/awareness/reading_development context selects the Adventure Kit signup CTA', $failures );

// ==================== Book-page destination resolves via real helper data ====================
$book_context = array(
	'audience'      => 'parent',
	'funnel_stage'  => 'product_discovery',
	'intent'        => 'adventure_geography',
	'featured_book' => 'mount_everest',
);
$selected3 = BHP_CTA_Engine::select_cta( $book_context );
bhp_cta_test_assert( 'book_page' === $selected3['id'], 'A product_discovery context with a real featured_book selects the book_page CTA', $failures );
bhp_cta_test_assert( ! empty( $selected3['url'] ) && false === strpos( $selected3['url'], 'amazon' ), 'The book_page CTA resolves to a real on-site product URL (not empty, not an Amazon link)', $failures );

// ==================== Unresolvable destinations are never selected ====================
$no_book_context = array(
	'audience'     => 'parent',
	'funnel_stage' => 'product_discovery',
	'intent'       => 'adventure_geography',
	// no featured_book supplied -- book_page and amazon_listing must both be skipped
);
$selected4 = BHP_CTA_Engine::select_cta( $no_book_context );
bhp_cta_test_assert( 'book_page' !== $selected4['id'] && 'amazon_listing' !== $selected4['id'], 'Without a featured_book, unresolvable destinations (book_page, amazon_listing) are never selected', $failures );
bhp_cta_test_assert( ! empty( $selected4['url'] ), 'A context with no resolvable book still falls back to a CTA with a real, non-empty URL', $failures );

// ==================== Unclassified/unknown context still resolves (safe defaults) ====================
$unknown_context = array(
	'audience'     => 'not_a_real_value',
	'funnel_stage' => 'not_a_real_value',
	'intent'       => 'not_a_real_value',
);
$selected5 = BHP_CTA_Engine::select_cta( $unknown_context );
bhp_cta_test_assert( null !== $selected5 && ! empty( $selected5['url'] ), 'A completely unrecognized context still resolves to a real CTA with a real URL (graceful failure, never fatal)', $failures );

// ==================== select_specific() for deliberate (non-scored) placements ====================
$specific = BHP_CTA_Engine::select_specific( 'adventure_kit_signup', array( 'audience' => 'parent' ) );
bhp_cta_test_assert( null !== $specific && 'adventure_kit_signup' === $specific['id'], 'select_specific() returns exactly the named registry entry regardless of scoring', $failures );
bhp_cta_test_assert( false !== strpos( $specific['url'], '/reluctant-reader-adventure-kit/' ), 'select_specific() resolves the real Adventure Kit URL', $failures );

$unresolvable_specific = BHP_CTA_Engine::select_specific( 'book_page', array() ); // no featured_book -- must fail to resolve
bhp_cta_test_assert( null === $unresolvable_specific, 'select_specific() returns null (never a broken CTA) when the named entry cannot resolve a URL for the given context', $failures );

$unknown_id_specific = BHP_CTA_Engine::select_specific( 'not_a_real_registry_id', array() );
bhp_cta_test_assert( null === $unknown_id_specific, 'select_specific() returns null for an unknown registry id rather than a fatal error', $failures );

// ==================== Missing context keys fall back to Content Classification defaults ====================
$empty_context = array();
$selected6 = BHP_CTA_Engine::select_cta( $empty_context );
bhp_cta_test_assert( null !== $selected6 && ! empty( $selected6['url'] ), 'An empty context array resolves to a real CTA using documented defaults, never a PHP notice/fatal', $failures );

// ==================== render() produces markup only when a URL is resolved, never fatals ====================
ob_start();
BHP_CTA_Engine::render( $selected2, 'blog_inline', 'variant_a' );
$rendered = ob_get_clean();
bhp_cta_test_assert( false !== strpos( $rendered, 'data-bhp-cta-id="adventure_kit_signup"' ), 'render() emits the data-bhp-cta-id tracking attribute for nav.js to pick up', $failures );
bhp_cta_test_assert( false !== strpos( $rendered, 'data-bhp-cta-placement="blog_inline"' ), 'render() emits the placement passed in, sanitized via sanitize_key', $failures );
bhp_cta_test_assert( false !== strpos( $rendered, 'data-bhp-impression-event="contextual_cta_view"' ), 'render() wires the impression-tracking attribute nav.js already knows how to observe', $failures );
bhp_cta_test_assert( false === strpos( $rendered, 'reviews' ) && false === strpos( $rendered, 'award' ), 'Rendered CTA markup contains no fabricated review/award language', $failures );

$empty_selected = array( 'url' => '' );
ob_start();
BHP_CTA_Engine::render( $empty_selected, 'blog_inline' );
$empty_rendered = ob_get_clean();
bhp_cta_test_assert( '' === $empty_rendered, 'render() is a safe no-op (no markup, no fatal) when passed a CTA with an empty resolved URL', $failures );

// ==================== render_for_post() bridges classification + selection safely for a nonexistent post ====================
ob_start();
BHP_CTA_Engine::render_for_post( 999999999, 'blog_end_of_article' );
$post_rendered = ob_get_clean();
bhp_cta_test_assert( false !== strpos( $post_rendered, 'data-bhp-cta-placement="blog_end_of_article"' ), 'render_for_post() on a nonexistent/unclassified post ID still renders a safe-default CTA without fataling', $failures );

// ==================== related-content.php fallback for non-registry posts ====================
// Every currently-published post happens to already be in the guide
// registry (verified: 35 posts, 35 registry entries), so this exercises
// the future-post fallback path directly by cloning a real, already-
// published post and giving the clone a slug that is guaranteed not to
// exist in bhp_get_guide_registry(). No real post is created or
// modified -- get_post() is cloned in memory only.
$real_posts = get_posts( array( 'post_type' => 'post', 'numberposts' => 1, 'post_status' => 'publish' ) );
if ( $real_posts && function_exists( 'bhp_get_guide_post_data' ) ) {
	$fake_post = clone $real_posts[0];
	$fake_post->post_name = 'zzz-guaranteed-not-in-guide-registry-' . wp_generate_password( 8, false );
	bhp_cta_test_assert( ! bhp_get_guide_post_data( $fake_post ), 'Test fixture: the fabricated slug is confirmed absent from the guide registry (so the fallback path is actually exercised)', $failures );

	ob_start();
	get_template_part( 'template-parts/guides/related-content', null, array( 'post' => $fake_post ) );
	$fallback_rendered = ob_get_clean();
	bhp_cta_test_assert( false !== strpos( $fallback_rendered, 'data-bhp-cta-id' ), 'A non-registry post now gets a contextual CTA after the article instead of the previous silent empty return', $failures );
	bhp_cta_test_assert( false !== strpos( $fallback_rendered, 'data-bhp-cta-placement="blog_end_of_article"' ), 'The related-content.php fallback tags its CTA with the blog_end_of_article placement', $failures );
	bhp_cta_test_assert( false === strpos( $fallback_rendered, 'guide-continuation' ), 'The fallback path never renders the curated guide-continuation markup meant only for registry posts', $failures );
}

// ==================== related_content_click event wiring on article-card.php ====================
// Uses the real, already-published registry post (ID 76) so the
// "Related Field Notes" grid actually has cards to render.
$registry_post_for_cards = get_page_by_path( 'mount-everest-facts-for-kids', OBJECT, 'post' );
if ( $registry_post_for_cards ) {
	ob_start();
	get_template_part( 'template-parts/guides/related-content', null, array( 'post' => $registry_post_for_cards ) );
	$registry_related_html = ob_get_clean();
	bhp_cta_test_assert( false !== strpos( $registry_related_html, 'data-bhp-event="related_content_click"' ), 'Related Field Notes article cards carry the related_content_click event attribute', $failures );
}

// ==================== Phase 8B blocker remediation: shortcode 'id' attribute ====================
global $post;
$original_post = $post;

if ( $real_posts ) {
	// 1. Valid explicit id renders exactly the intended CTA, exactly once.
	$post = clone $real_posts[0];
	setup_postdata( $post );
	$out_valid = do_shortcode( '[bhp_contextual_cta id="teacher_resource"]' );
	bhp_cta_test_assert( false !== strpos( $out_valid, 'data-bhp-cta-id="teacher_resource"' ), 'Valid explicit id="teacher_resource" renders exactly that CTA', $failures );
	bhp_cta_test_assert( 1 === substr_count( $out_valid, 'data-bhp-cta-id=' ), 'Valid explicit id renders exactly one CTA block', $failures );

	// 2. Invalid/unknown id fails safe: renders nothing, never a fabricated CTA, never a fatal.
	$out_invalid = do_shortcode( '[bhp_contextual_cta id="not_a_real_registry_id"]' );
	bhp_cta_test_assert( '' === trim( $out_invalid ), 'Invalid/unknown id renders nothing (safe failure), never a substituted or fabricated CTA', $failures );

	// 3. Empty id="" falls back to the existing classification-based behavior, unchanged.
	$out_empty_id = do_shortcode( '[bhp_contextual_cta id=""]' );
	bhp_cta_test_assert( false !== strpos( $out_empty_id, 'data-bhp-cta-id=' ), 'Empty id="" falls back to classification-based selection and still renders a real CTA', $failures );

	// No id attribute at all: placement/variant behavior must be unchanged from before this fix.
	$out_no_id = do_shortcode( '[bhp_contextual_cta placement="blog_inline"]' );
	bhp_cta_test_assert( false !== strpos( $out_no_id, 'data-bhp-cta-placement="blog_inline"' ), 'Shortcode with no id attribute preserves the existing placement-based classification behavior', $failures );

	wp_reset_postdata();
	$post = $original_post;
}

// ==================== Phase 8B blocker remediation: duplicate-CTA prevention ====================
if ( $real_posts && function_exists( 'bhp_get_guide_post_data' ) ) {
	// 4. Explicit shortcode present -> automatic end-of-article fallback must NOT also render.
	$post_with_shortcode = clone $real_posts[0];
	$post_with_shortcode->post_name    = 'zzz-generator-style-draft-' . wp_generate_password( 8, false );
	$post_with_shortcode->post_content = 'Some generated content. [bhp_contextual_cta id="book_page"] More content.';
	bhp_cta_test_assert( ! bhp_get_guide_post_data( $post_with_shortcode ), 'Test fixture: fabricated slug confirmed absent from the guide registry', $failures );

	ob_start();
	get_template_part( 'template-parts/guides/related-content', null, array( 'post' => $post_with_shortcode ) );
	$with_shortcode_rendered = ob_get_clean();
	bhp_cta_test_assert( '' === trim( $with_shortcode_rendered ), 'A post with an explicit [bhp_contextual_cta] shortcode gets NO automatic end-of-article fallback (duplicate prevented)', $failures );

	// 5. No explicit shortcode -> automatic fallback still renders, exactly once.
	$post_without_shortcode = clone $real_posts[0];
	$post_without_shortcode->post_name    = 'zzz-no-shortcode-draft-' . wp_generate_password( 8, false );
	$post_without_shortcode->post_content = 'Plain content with no shortcode at all.';
	ob_start();
	get_template_part( 'template-parts/guides/related-content', null, array( 'post' => $post_without_shortcode ) );
	$without_shortcode_rendered = ob_get_clean();
	bhp_cta_test_assert( 1 === substr_count( $without_shortcode_rendered, 'data-bhp-cta-id' ), 'A post with no explicit shortcode still gets exactly one automatic fallback CTA', $failures );

	// 6. AI-generator-style draft (explicit id shortcode amid generated paragraphs) -- the
	// exact verified defect from the Phase 8B audit: confirms body + end-of-article combined
	// render exactly one CTA total, not two.
	$generator_post = clone $real_posts[0];
	$generator_post->post_name    = 'zzz-ai-generator-draft-' . wp_generate_password( 8, false );
	$generator_post->post_content = '<p>Paragraph one.</p><p>Paragraph two.</p>[bhp_contextual_cta id="adventure_kit_signup"]<p>Paragraph three.</p>';
	$body_expanded = do_shortcode( $generator_post->post_content );
	ob_start();
	get_template_part( 'template-parts/guides/related-content', null, array( 'post' => $generator_post ) );
	$generator_combined = $body_expanded . ob_get_clean();
	bhp_cta_test_assert( 1 === substr_count( $generator_combined, 'data-bhp-cta-id' ), 'AI-generator-style draft renders exactly ONE CTA total across body + end-of-article -- verified duplicate-CTA defect is fixed', $failures );
	bhp_cta_test_assert( false !== strpos( $generator_combined, 'data-bhp-cta-id="adventure_kit_signup"' ), 'The one CTA that renders is the editorially-intended id from the shortcode, not a different classification-based guess', $failures );
}

// 7. Registry-curated post: preserve current curated behavior, unaffected by the shortcode-detection fix.
if ( $registry_post_for_cards ) {
	ob_start();
	get_template_part( 'template-parts/guides/related-content', null, array( 'post' => $registry_post_for_cards ) );
	$registry_curated_rendered = ob_get_clean();
	bhp_cta_test_assert( false !== strpos( $registry_curated_rendered, 'guide-continuation' ), 'Registry-curated posts still render the curated guide-continuation block', $failures );
	bhp_cta_test_assert( false === strpos( $registry_curated_rendered, 'data-bhp-cta-id' ), 'Registry-curated posts do not also receive an automatic CTA engine render', $failures );
}

// 8. Product-page behavior: confirm the WooCommerce hook this fix did not touch is still
// registered exactly as before (distinct two-CTA behavior lives in functions.php, untouched).
bhp_cta_test_assert(
	has_action( 'woocommerce_after_single_product_summary', 'bhp_woocommerce_product_adventure_kit_cta' ) !== false,
	'Product-page Adventure Kit CTA hook remains registered on woocommerce_after_single_product_summary (unaffected by this fix)',
	$failures
);

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL CTA ENGINE TESTS PASSED\n";
