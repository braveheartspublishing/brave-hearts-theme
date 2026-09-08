<?php
/**
 * Brave Hearts Publishing — Phase 1E: content intelligence engine test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file tests/test-content-intelligence-engine.php --user=1 --url=<staging-host>
 *
 * Covers: analytics import validation/dedup, opportunity scoring
 * (including missing-data/confidence behavior), production-queue status
 * transitions + approval gating + pagination, brief generation required
 * fields, SEO metadata validation, originality checks, Pinterest
 * 4-variant validation, draft-workflow safety (draft-only, duplicate
 * slug, provenance-gated deletion), and the QA gate's refusal to
 * auto-pass factual accuracy. Cleans up every fixture it creates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
function bhp_cie_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$cleanup_post_ids = array();

// ==================== Analytics Adapter ====================

$valid = BHP_Analytics_Adapter::import_rows( 'gsc', array(
	array( 'page' => '/blog/test-a/', 'impressions' => 100, 'clicks' => 5, 'ctr' => 0.05, 'position' => 6.2, 'date' => '2026-06-01' ),
), array( 'label' => 'unit-test', 'is_fixture' => true ) );
bhp_cie_assert( 1 === $valid['imported'] && 0 === $valid['rejected'], 'Analytics import: a fully valid CSV/array row imports successfully', $failures );
if ( $valid['import_id'] ) {
	$cleanup_post_ids[] = $valid['import_id'];
}

$missing_field = BHP_Analytics_Adapter::import_rows( 'gsc', array(
	array( 'page' => '/blog/test-b/', 'impressions' => 100, 'clicks' => 5, 'ctr' => 0.05, 'date' => '2026-06-01' ), // no 'position'
), array( 'is_fixture' => true ) );
bhp_cie_assert( 0 === $missing_field['imported'] && 1 === $missing_field['rejected'], 'Analytics import: a row missing a required field is rejected, never defaulted to zero', $failures );

$dup_result = BHP_Analytics_Adapter::import_rows( 'gsc', array(
	array( 'page' => '/blog/test-c/', 'impressions' => 100, 'clicks' => 5, 'ctr' => 0.05, 'position' => 6.2, 'date' => '2026-06-01' ),
	array( 'page' => '/blog/test-c/', 'impressions' => 200, 'clicks' => 9, 'ctr' => 0.045, 'position' => 6.1, 'date' => '2026-06-01' ), // exact dup key (same page+date+query)
), array( 'is_fixture' => true ) );
bhp_cie_assert( 1 === $dup_result['imported'] && 1 === $dup_result['rejected'], 'Analytics import: duplicate (source+url+date) row within a batch is rejected', $failures );
if ( $dup_result['import_id'] ) {
	$cleanup_post_ids[] = $dup_result['import_id'];
}

$malformed_url = BHP_Analytics_Adapter::import_rows( 'gsc', array(
	array( 'page' => '', 'impressions' => 100, 'clicks' => 5, 'ctr' => 0.05, 'position' => 6.2, 'date' => '2026-06-01' ),
), array( 'is_fixture' => true ) );
bhp_cie_assert( 0 === $malformed_url['imported'], 'Analytics import: empty/malformed URL is rejected', $failures );

$bad_date = BHP_Analytics_Adapter::import_rows( 'ga4', array(
	array( 'landing_page' => '/blog/test-d/', 'sessions' => 10, 'date' => 'not-a-date' ),
), array( 'is_fixture' => true ) );
bhp_cie_assert( 0 === $bad_date['imported'], 'Analytics import: malformed date is rejected', $failures );

$csv = "page,impressions,clicks,ctr,position,date\n/blog/test-e/,500,20,0.04,4.5,2026-06-15\n";
$csv_result = BHP_Analytics_Adapter::import_csv( 'gsc', $csv, array( 'is_fixture' => true ) );
bhp_cie_assert( 1 === $csv_result['imported'], 'Analytics import: CSV parsing handles a real header + one data row', $failures );
if ( $csv_result['import_id'] ) {
	$cleanup_post_ids[] = $csv_result['import_id'];
}

bhp_cie_assert( '/blog/foo' === BHP_Analytics_Adapter::normalize_url( 'https://www.example.com/blog/foo/' ), 'URL normalization strips scheme/host/trailing slash', $failures );
bhp_cie_assert( '/blog/foo' === BHP_Analytics_Adapter::normalize_url( '/blog/foo' ), 'URL normalization accepts a bare path unchanged', $failures );

// ==================== Content Opportunity Engine ====================

$item_no_data = array( 'content_id' => 1, 'url' => '/x', 'audience' => '', 'funnel_stage' => '', 'featured_book' => '', 'lead_offer' => '', 'primary_cta' => '', 'internal_link_targets' => array() );
$score_no_data = BHP_Content_Opportunity_Engine::score_item( $item_no_data, array(), array() );
bhp_cie_assert( 'low' === $score_no_data['confidence'], 'Opportunity engine: an item with no analytics data and no classification gets low confidence', $failures );
bhp_cie_assert( in_array( 'demand', $score_no_data['missing_signals'], true ), 'Opportunity engine: missing GSC data is recorded in missing_signals, not silently zeroed', $failures );

$item_rich = array( 'content_id' => 2, 'url' => '/y', 'audience' => 'parent', 'funnel_stage' => 'awareness', 'featured_book' => 'mariana_trench', 'lead_offer' => 'adventure_kit_parent', 'primary_cta' => 'adventure_kit_signup', 'internal_link_targets' => array( '/z' ), 'last_modified_date' => gmdate( 'Y-m-d H:i:s' ) );
$gsc_rows = array( array( 'page' => '/y', 'impressions' => 6000, 'clicks' => 60, 'position' => 12, 'date' => '2026-06-01' ) );
$ga4_rows = array( array( 'landing_page' => '/y', 'sessions' => 500, 'cta_clicks' => 40, 'lead_signups' => 20, 'date' => '2026-06-01' ) );
$score_rich = BHP_Content_Opportunity_Engine::score_item( $item_rich, $gsc_rows, $ga4_rows );
bhp_cie_assert( 'high' === $score_rich['confidence'], 'Opportunity engine: an item with full analytics + classification data gets high confidence', $failures );
bhp_cie_assert( null !== $score_rich['score'] && $score_rich['score'] > 0, 'Opportunity engine: a fully-signaled item produces a numeric score', $failures );
bhp_cie_assert( is_array( $score_rich['recommendation'] ) && ! empty( $score_rich['recommendation']['reason'] ), 'Opportunity engine: every recommendation includes a plain-text reason', $failures );

$item_orphan = array( 'content_id' => 3, 'url' => '/orphan', 'internal_link_targets' => array() );
$score_orphan = BHP_Content_Opportunity_Engine::score_item( $item_orphan, array(), array() );
bhp_cie_assert( 'strengthen_internal_links' === $score_orphan['recommendation']['type'], 'Opportunity engine: a page with zero outgoing links is recommended for internal-link strengthening', $failures );

// ==================== Production Queue ====================

$queue_id = BHP_Content_Production_Queue::add_item( array(
	'recommendation_type' => 'create_new_article',
	'target_url'          => '',
	'proposed_slug'       => 'test-queue-item-' . wp_generate_password( 6, false ),
	'audience'            => 'parent',
	'funnel_stage'        => 'awareness',
	'content_intent'      => 'reading_development',
	'primary_keyword'     => 'test keyword',
	'featured_book'       => 'mariana_trench',
	'lead_offer'          => 'adventure_kit_parent',
	'cta_goal'            => 'adventure_kit_signup',
	'confidence'          => 'low',
) );
$cleanup_post_ids[] = $queue_id;

bhp_cie_assert( $queue_id > 0, 'Production queue: add_item() returns a valid post ID', $failures );
$fresh_item = BHP_Content_Production_Queue::get_item( $queue_id );
bhp_cie_assert( 'discovered' === $fresh_item['status'], 'Production queue: a new item always starts at "discovered" regardless of input', $failures );
bhp_cie_assert( 'pending' === $fresh_item['approval_status'], 'Production queue: a new item starts with approval_status=pending', $failures );

$blocked = BHP_Content_Production_Queue::transition( $queue_id, 'approved' ); // no approver
bhp_cie_assert( is_wp_error( $blocked ), 'Production queue: transitioning to "approved" without an approving user is refused', $failures );

$allowed = BHP_Content_Production_Queue::transition( $queue_id, 'approved', 'unit-test-approver' );
bhp_cie_assert( true === $allowed, 'Production queue: transitioning to "approved" WITH an approving user succeeds', $failures );
$approved_item = BHP_Content_Production_Queue::get_item( $queue_id );
bhp_cie_assert( 'approved' === $approved_item['approval_status'] && 'unit-test-approver' === $approved_item['approved_by'], 'Production queue: approval metadata is recorded correctly', $failures );

$bad_status = BHP_Content_Production_Queue::transition( $queue_id, 'not_a_real_status', 'unit-test-approver' );
bhp_cie_assert( is_wp_error( $bad_status ), 'Production queue: an unknown status is refused', $failures );

$list_result = BHP_Content_Production_Queue::list_items( array( 'audience' => 'parent' ), 1, 5 );
bhp_cie_assert( is_array( $list_result['items'] ) && $list_result['total'] >= 1, 'Production queue: list_items() filters by audience and returns pagination metadata', $failures );
bhp_cie_assert( isset( $list_result['total_pages'] ), 'Production queue: list_items() always exposes total_pages (never an unbounded query)', $failures );

// ==================== Content Brief Generator ====================

$brief = BHP_Content_Brief_Generator::generate( $queue_id );
bhp_cie_assert( ! is_wp_error( $brief ), 'Brief generator: generates successfully for a queue item with a primary keyword', $failures );
if ( ! is_wp_error( $brief ) ) {
	bhp_cie_assert( ! empty( $brief['blog_slug'] ) && ! empty( $brief['primary_keyword'] ), 'Brief generator: required fields are populated', $failures );
	bhp_cie_assert( isset( $brief['factual_claims_requiring_verification'] ) && isset( $brief['originality_requirement'] ), 'Brief generator: factual-review and originality gates are present', $failures );
	bhp_cie_assert( false !== strpos( $brief['unique_angle'], '[PLACEHOLDER' ), 'Brief generator: never fabricates a unique angle -- leaves an explicit placeholder', $failures );
	$requeued = BHP_Content_Production_Queue::get_item( $queue_id );
	bhp_cie_assert( 'brief_ready' === $requeued['status'], 'Brief generator: successful generation transitions the queue item to brief_ready', $failures );
}

$no_keyword_queue_id = BHP_Content_Production_Queue::add_item( array( 'recommendation_type' => 'monitor', 'proposed_slug' => 'no-keyword-test' ) );
$cleanup_post_ids[]  = $no_keyword_queue_id;
$brief_error = BHP_Content_Brief_Generator::generate( $no_keyword_queue_id );
bhp_cie_assert( is_wp_error( $brief_error ), 'Brief generator: refuses to generate a brief with no primary_keyword', $failures );

$prohibited = BHP_Content_Brief_Generator::detect_prohibited_claims( 'This is clinically proven and award-winning, guaranteed to work!' );
bhp_cie_assert( count( $prohibited ) >= 3, 'Brief generator: detects multiple prohibited claim patterns in one string', $failures );

// ==================== SEO Metadata Package ====================

if ( ! is_wp_error( $brief ) ) {
	$seo = BHP_SEO_Metadata_Package::generate( $brief, array() );
	bhp_cie_assert( strlen( $seo['seo_title'] ) <= 60, 'SEO package: generated title never exceeds 60 characters', $failures );
	bhp_cie_assert( strlen( $seo['meta_description'] ) <= 160, 'SEO package: generated description never exceeds 160 characters', $failures );
	bhp_cie_assert( 'pass' === $seo['validation']['state'] || 'revise' === $seo['validation']['state'], 'SEO package: validation always returns a defined state', $failures );

	$colliding_inventory = array( array( 'url' => home_url( '/blog/' . $seo['proposed_slug'] . '/' ), 'seo' => array( 'title' => $seo['seo_title'], 'description' => '' ), 'primary_keyword' => $seo['primary_keyword'] ) );
	$seo_collision = BHP_SEO_Metadata_Package::generate( $brief, $colliding_inventory );
	$issues = wp_list_pluck( $seo_collision['validation']['findings'], 'issue' );
	bhp_cie_assert( in_array( 'slug_collision', $issues, true ), 'SEO package: detects a slug collision against existing inventory', $failures );
	bhp_cie_assert( in_array( 'cannibalization_risk', $issues, true ), 'SEO package: detects a primary-keyword cannibalization risk', $failures );
	bhp_cie_assert( 'fail' === $seo_collision['validation']['state'], 'SEO package: a slug collision or cannibalization risk forces overall state to fail', $failures );
}

// ==================== Originality ====================

$generic_text = 'In today\'s world, reading matters more than ever. In this article, we will explore chapter books.';
$originality_findings = BHP_Content_Originality::check_draft( $generic_text );
bhp_cie_assert( ! empty( array_filter( $originality_findings, static function ( $f ) { return 'generic_opening' === $f['type']; } ) ), 'Originality: detects a known generic-AI-content opening pattern', $failures );

$repetitive = str_repeat( 'The child reads a book. ', 8 );
$rep_findings = BHP_Content_Originality::check_draft( $repetitive );
bhp_cie_assert( ! empty( array_filter( $rep_findings, static function ( $f ) { return 'repetitive_sentence_structure' === $f['type']; } ) ), 'Originality: detects repetitive sentence-opener structure', $failures );

$existing = array( array( 'url' => '/existing', 'text' => 'The Mariana Trench is the deepest part of the ocean on planet Earth today' ) );
$dup_findings = BHP_Content_Originality::check_draft( 'Something else. The Mariana Trench is the deepest part of the ocean on planet Earth today. More text.', $existing );
bhp_cie_assert( ! empty( array_filter( $dup_findings, static function ( $f ) { return 'duplicate_phrases' === $f['type']; } ) ), 'Originality: detects a verbatim shared 8-word phrase against existing published content', $failures );

$checklist_pass = BHP_Content_Originality::check_manual_originality_checklist( array( 'originality_requirement' => array( 'options' => array( 'a', 'b', 'c' ), 'minimum_required' => 2 ) ), array( 'a', 'b' ) );
bhp_cie_assert( true === $checklist_pass['pass'], 'Originality: manual checklist passes when enough valid items are confirmed', $failures );
$checklist_fail = BHP_Content_Originality::check_manual_originality_checklist( array( 'originality_requirement' => array( 'options' => array( 'a', 'b', 'c' ), 'minimum_required' => 2 ) ), array( 'a' ) );
bhp_cie_assert( false === $checklist_fail['pass'], 'Originality: manual checklist fails when too few items are confirmed', $failures );

// ==================== Pinterest Variant Generator ====================

if ( ! is_wp_error( $brief ) && isset( $seo ) ) {
	$pinterest = BHP_Pinterest_Variant_Generator::generate( $brief, $seo );
	bhp_cie_assert( 4 === count( $pinterest['variants'] ), 'Pinterest generator: produces exactly 4 variants', $failures );
	$types = wp_list_pluck( $pinterest['variants'], 'variant_type' );
	bhp_cie_assert( count( array_unique( $types ) ) === 4, 'Pinterest generator: all 4 variant types are distinct', $failures );
	foreach ( $pinterest['variants'] as $v ) {
		bhp_cie_assert( preg_match( '/^blog-.+_(problem-led|outcome-led|curiosity-led|resource-led)_v\d+$/', $v['utm_content'] ), "Pinterest generator: utm_content for {$v['variant_type']} matches the required pattern", $failures );
	}

	$bad_variants = array(
		array( 'variant_type' => 'problem-led', 'headline' => 'Same headline', 'supporting_copy' => 'x', 'pinterest_title' => 'x', 'pinterest_description' => 'x', 'alt_text' => 'x', 'destination_url' => 'https://x', 'utm_content' => 'blog-x_problem-led_v1' ),
		array( 'variant_type' => 'outcome-led', 'headline' => 'Same headline', 'supporting_copy' => 'x', 'pinterest_title' => 'x', 'pinterest_description' => 'x', 'alt_text' => 'x', 'destination_url' => 'https://x', 'utm_content' => 'blog-x_outcome-led_v1' ),
	);
	$bad_validation = BHP_Pinterest_Variant_Generator::validate( $bad_variants );
	bhp_cie_assert( 'revise' === $bad_validation['state'], 'Pinterest generator: duplicate headlines across variants fail validation', $failures );
	bhp_cie_assert( ! empty( array_filter( $bad_validation['findings'], static function ( $f ) { return false !== strpos( $f, 'Missing required variant type' ); } ) ), 'Pinterest generator: missing required variant types are flagged', $failures );
}

// ==================== Gutenberg block-markup validity (regression: post 460) ====================
// Root cause: modern core/list requires each <li> individually wrapped in its
// own <!-- wp:list-item --> block; a flat <ul><li> is invalid and triggers the
// block editor's "unexpected or invalid content" recovery prompt. Verified
// against real, currently-published post_content on this exact WordPress
// install (WP 7.0) before writing these assertions -- not assumed.

$scaffold_for_markup_check = ! is_wp_error( $brief ) ? BHP_Blog_Draft_Generator::generate( $brief ) : null;
if ( $scaffold_for_markup_check ) {
	$scaffold_markup_errors = BHP_Blog_Draft_Generator::validate_markup( $scaffold_for_markup_check['content_html'] );
	bhp_cie_assert( empty( $scaffold_markup_errors ), 'Draft generator: scaffold output parses without any invalid-block structural errors (' . implode( '; ', $scaffold_markup_errors ) . ')', $failures );
	bhp_cie_assert( false !== strpos( $scaffold_for_markup_check['content_html'], 'wp:heading' ), 'Draft generator: produces WordPress block-compatible heading markup', $failures );
	bhp_cie_assert( (bool) preg_match( '/<!-- wp:heading -->\s*<h2>/', $scaffold_for_markup_check['content_html'] ), 'Draft generator: headings use a valid Gutenberg comment immediately wrapping the <h2>', $failures );
	bhp_cie_assert( (bool) preg_match( '/<!-- wp:paragraph -->\s*<p>/', $scaffold_for_markup_check['content_html'] ), 'Draft generator: paragraphs use a valid Gutenberg comment immediately wrapping the <p>', $failures );
	bhp_cie_assert( ! empty( $scaffold_for_markup_check['placeholders'] ), 'Draft generator: a freshly generated scaffold always carries unresolved placeholders (never fabricated prose)', $failures );
}

$broken_flat_list = '<!-- wp:list --><ul><li><a href="https://example.com/a">A</a></li><li><a href="https://example.com/b">B</a></li></ul><!-- /wp:list -->';
$broken_errors = BHP_Blog_Draft_Generator::validate_markup( $broken_flat_list );
bhp_cie_assert( ! empty( $broken_errors ), 'Markup validator: correctly flags a flat wp:list with no wp:list-item nesting as invalid (this is the exact post-460 defect)', $failures );

$broken_group_list = '<!-- wp:group {"className":"bhp-fact-box"} --><div class="wp-block-group bhp-fact-box"><ul><li>Claim one</li></ul></div><!-- /wp:group -->';
$broken_group_errors = BHP_Blog_Draft_Generator::validate_markup( $broken_group_list );
bhp_cie_assert( ! empty( $broken_group_errors ), 'Markup validator: correctly flags raw <li> markup embedded directly inside a wp:group as invalid', $failures );

$related_links_html = ! is_wp_error( $brief ) ? BHP_Blog_Draft_Generator::generate( array_merge( $brief, array( 'internal_link_targets' => array( array( 'url' => 'https://example.com/a', 'title' => 'A' ), array( 'url' => 'https://example.com/b', 'title' => 'B' ) ), 'factual_claims_requiring_verification' => array( 'Test claim one' ) ) ) ) : null;
if ( $related_links_html ) {
	bhp_cie_assert( (bool) preg_match( '/<!-- wp:list -->\s*<ul><!-- wp:list-item -->/', $related_links_html['content_html'] ), 'Draft generator: related-links list nests each item in its own wp:list-item (matches real ground-truth markup from this install)', $failures );
	bhp_cie_assert( empty( BHP_Blog_Draft_Generator::validate_markup( $related_links_html['content_html'] ) ), 'Draft generator: related-links + fact-box output together still parses with zero invalid-block errors', $failures );
}

$cta_html = self_test_cta_marker_probe();
bhp_cie_assert( (bool) preg_match( '/^<!-- wp:shortcode -->\[bhp_contextual_cta id="[^"]+"\]<!-- \/wp:shortcode -->$/', $cta_html ), 'Draft generator: CTA shortcode uses a valid wp:shortcode block (matches real ground-truth markup from this install)', $failures );

$placeholder_free = '<!-- wp:paragraph --><p>Real prose here.</p><!-- /wp:paragraph -->';
bhp_cie_assert( false === BHP_Blog_Draft_Generator::contains_editorial_instructions( $placeholder_free ), 'Editorial-instruction detector: real prose is not flagged', $failures );
$placeholder_present = '<!-- wp:paragraph --><p>[PLACEHOLDER: something]</p><!-- /wp:paragraph -->';
bhp_cie_assert( true === BHP_Blog_Draft_Generator::contains_editorial_instructions( $placeholder_present ), 'Editorial-instruction detector: a placeholder marker inside otherwise-valid block markup is still caught (no placeholder block can pass silently)', $failures );

function self_test_cta_marker_probe() {
	$ref = new ReflectionClass( 'BHP_Blog_Draft_Generator' );
	$m   = $ref->getMethod( 'cta_marker' );
	$m->setAccessible( true );
	return $m->invoke( null, 'test_cta_id' );
}

// ==================== Article draft assembly (real prose, no placeholders) ====================

if ( ! is_wp_error( $brief ) ) {
	$real_prose = array(
		'opening_hook' => 'Plenty of kids stall out right after picture books -- this is what actually gets them reading chapter books.',
		'sections'     => array(
			array( 'heading' => 'Start with a short, illustrated chapter book', 'body' => 'Short chapters with a picture every few pages keep the momentum going without feeling babyish.' ),
			array( 'heading' => 'Read the first chapter aloud together', 'body' => 'Reading the opening chapter aloud removes the biggest barrier: getting started.' ),
		),
	);
	$article = BHP_Blog_Draft_Generator::assemble_article_draft( $brief, $real_prose );
	bhp_cie_assert( ! is_wp_error( $article ), 'Article assembler: succeeds with real, placeholder-free prose', $failures );
	if ( ! is_wp_error( $article ) ) {
		bhp_cie_assert( empty( $article['placeholders'] ), 'Article assembler: output never carries unresolved placeholders', $failures );
		bhp_cie_assert( empty( BHP_Blog_Draft_Generator::validate_markup( $article['content_html'] ) ), 'Article assembler: output parses without any invalid-block errors', $failures );
		bhp_cie_assert( false === BHP_Blog_Draft_Generator::contains_editorial_instructions( $article['content_html'] ), 'Article assembler: output contains no internal editorial instruction markers', $failures );
	}

	$prose_with_placeholder = $real_prose;
	$prose_with_placeholder['sections'][0]['body'] = '[PLACEHOLDER: fill this in later]';
	$refused_article = BHP_Blog_Draft_Generator::assemble_article_draft( $brief, $prose_with_placeholder );
	bhp_cie_assert( is_wp_error( $refused_article ), 'Article assembler: refuses a section that still contains a placeholder marker rather than smuggling a scaffold through as a finished draft', $failures );

	$missing_hook = $real_prose;
	unset( $missing_hook['opening_hook'] );
	bhp_cie_assert( is_wp_error( BHP_Blog_Draft_Generator::assemble_article_draft( $brief, $missing_hook ) ), 'Article assembler: refuses when opening_hook is missing entirely', $failures );
}

// ==================== WP Draft Workflow ====================

if ( ! is_wp_error( $brief ) && isset( $seo ) ) {
	$draft = BHP_Blog_Draft_Generator::generate( $brief );
	bhp_cie_assert( false !== strpos( $draft['content_html'], 'wp:heading' ), 'Draft generator: produces WordPress block-compatible markup', $failures );
	bhp_cie_assert( ! empty( $draft['placeholders'] ), 'Draft generator: a freshly generated draft always carries unresolved placeholders (never fabricated prose)', $failures );

	BHP_Content_Production_Queue::transition( $queue_id, 'ready_for_wp_draft', 'unit-test-approver' );

	$refused_no_approver = BHP_WP_Draft_Workflow::create_draft( $queue_id, $draft, $seo, $brief, '' ); // no approver
	bhp_cie_assert( is_wp_error( $refused_no_approver ), 'Draft workflow: refuses to create a draft without an explicit approving user', $failures );

	// --- The core post-460 regression: a placeholder-laden scaffold must
	// never become a WordPress post, no matter how it is approved. ---
	$fully_cleared_qa = array( 'overall_status' => 'pass_for_wp_draft', 'checks' => array() );
	$refused_placeholders = BHP_WP_Draft_Workflow::create_draft( $queue_id, $draft, $seo, $brief, 'unit-test-approver', $fully_cleared_qa );
	bhp_cie_assert( is_wp_error( $refused_placeholders ) && 'bhp_wpdw_placeholders_remaining' === $refused_placeholders->get_error_code(), 'Draft workflow: refuses to create a WordPress draft from a placeholder-laden scaffold even when approved and QA is (falsely) reported clear -- this is exactly what let post 460 through before', $failures );

	$refused_no_qa = BHP_WP_Draft_Workflow::create_draft( $queue_id, array( 'content_html' => '<!-- wp:paragraph --><p>Real prose.</p><!-- /wp:paragraph -->', 'placeholders' => array() ), $seo, $brief, 'unit-test-approver' );
	bhp_cie_assert( is_wp_error( $refused_no_qa ) && 'bhp_wpdw_qa_not_evaluated' === $refused_no_qa->get_error_code(), 'Draft workflow: refuses to create a draft when no QA gate result was supplied at all', $failures );

	$invalid_markup_draft = array( 'content_html' => '<!-- wp:list --><ul><li>No list-item wrapping</li></ul><!-- /wp:list -->', 'placeholders' => array() );
	$refused_invalid_markup = BHP_WP_Draft_Workflow::create_draft( $queue_id, $invalid_markup_draft, $seo, $brief, 'unit-test-approver', $fully_cleared_qa );
	bhp_cie_assert( is_wp_error( $refused_invalid_markup ) && 'bhp_wpdw_invalid_markup' === $refused_invalid_markup->get_error_code(), 'Draft workflow: refuses to create a draft with invalid Gutenberg block markup even when QA is (falsely) reported clear', $failures );

	$blocked_qa = array( 'overall_status' => 'editorial_review_required', 'checks' => array() );
	$placeholder_free_draft = array( 'content_html' => '<!-- wp:paragraph --><p>Real prose.</p><!-- /wp:paragraph -->', 'placeholders' => array() );
	$refused_qa_status = BHP_WP_Draft_Workflow::create_draft( $queue_id, $placeholder_free_draft, $seo, $brief, 'unit-test-approver', $blocked_qa );
	bhp_cie_assert( is_wp_error( $refused_qa_status ) && 'bhp_wpdw_qa_not_cleared' === $refused_qa_status->get_error_code(), 'Draft workflow: refuses to create a draft while QA overall_status is editorial_review_required', $failures );

	// --- The only path that should succeed: placeholder-free, valid markup, QA cleared. ---
	$post_id = BHP_WP_Draft_Workflow::create_draft( $queue_id, $placeholder_free_draft, $seo, $brief, 'unit-test-approver', $fully_cleared_qa );
	bhp_cie_assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Draft workflow: creates a post once approved, placeholder-free, valid-markup, and QA-cleared', $failures );
	if ( ! is_wp_error( $post_id ) ) {
		$created_post = get_post( $post_id );
		bhp_cie_assert( 'draft' === $created_post->post_status, 'Draft workflow: created post always has post_status=draft', $failures );

		$prov = BHP_WP_Draft_Workflow::get_draft_provenance( $post_id );
		bhp_cie_assert( true === $prov['is_phase1e_generated'], 'Draft workflow: created post carries the Phase 1E provenance marker', $failures );

		// Duplicate-slug protection: creating a second draft with the same slug must not collide.
		$post_id_2 = BHP_WP_Draft_Workflow::create_draft( $queue_id, $placeholder_free_draft, $seo, $brief, 'unit-test-approver', $fully_cleared_qa );
		bhp_cie_assert( ! is_wp_error( $post_id_2 ) && $post_id_2 !== $post_id, 'Draft workflow: a second draft with the same base slug gets a unique slug, not a collision', $failures );
		if ( ! is_wp_error( $post_id_2 ) ) {
			$cleanup_post_ids[] = $post_id_2;
		}

		// Rollback safety: cannot delete a non-draft, non-provenance-marked post.
		$unrelated_post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Unrelated real post (never touch)' ), true );
		$refused_delete = BHP_WP_Draft_Workflow::delete_synthetic_draft( $unrelated_post_id );
		bhp_cie_assert( is_wp_error( $refused_delete ), 'Draft workflow: refuses to delete a post without the Phase 1E draft provenance marker', $failures );
		wp_delete_post( $unrelated_post_id, true ); // manual cleanup of the throwaway control post

		$queue_status_before_delete = BHP_Content_Production_Queue::get_item( $queue_id )['status'];
		bhp_cie_assert( 'wp_draft_created' === $queue_status_before_delete, 'Draft workflow: queue item correctly shows wp_draft_created immediately after creation', $failures );

		$deleted = BHP_WP_Draft_Workflow::delete_synthetic_draft( $post_id );
		bhp_cie_assert( ! is_wp_error( $deleted ), 'Draft workflow: successfully rolls back its own synthetic draft', $failures );
		bhp_cie_assert( null === get_post( $post_id ), 'Draft workflow: rolled-back draft is actually gone', $failures );

		$queue_status_after_delete = BHP_Content_Production_Queue::get_item( $queue_id )['status'];
		bhp_cie_assert( 'ready_for_wp_draft' === $queue_status_after_delete, 'Draft workflow: rollback resets the queue item so it no longer falsely reports wp_draft_created', $failures );
	}
}

// ==================== QA Gate ====================

if ( ! is_wp_error( $brief ) && isset( $seo, $draft, $pinterest ) ) {
	$qa = BHP_Content_QA_Gate::evaluate( $brief, $draft, $seo, $pinterest );
	bhp_cie_assert( 'requires_human_review' === $qa['checks']['factual_accuracy']['state'], 'QA gate: factual_accuracy is NEVER auto-passed by code alone', $failures );
	bhp_cie_assert( 'editorial_review_required' === $qa['overall_status'], 'QA gate: overall status reflects the unresolved human-review requirement', $failures );
	bhp_cie_assert( 'deterministic' === $qa['checks']['seo_metadata']['check_type'], 'QA gate: each check exposes its check_type (deterministic/heuristic/inferred/manual)', $failures );
	bhp_cie_assert( array_key_exists( 'gutenberg_markup_valid', $qa['checks'] ), 'QA gate: exposes a dedicated gutenberg_markup_valid check', $failures );
	bhp_cie_assert( 'pass' === $qa['checks']['gutenberg_markup_valid']['state'], 'QA gate: the (now-fixed) scaffold itself passes gutenberg_markup_valid', $failures );

	$claim_draft = array( 'content_html' => '<p>This book is clinically proven and guaranteed to improve reading.</p>', 'placeholders' => array() );
	$qa_with_claims = BHP_Content_QA_Gate::evaluate( $brief, $claim_draft, $seo, $pinterest );
	bhp_cie_assert( 'fail' === $qa_with_claims['checks']['unsupported_claim_risk']['state'], 'QA gate: a draft with prohibited claim language fails the unsupported-claim check', $failures );
	bhp_cie_assert( 'fail' === $qa_with_claims['overall_status'], 'QA gate: any single failing check forces overall_status to fail', $failures );

	$invalid_markup_for_qa = array( 'content_html' => '<!-- wp:list --><ul><li>flat</li></ul><!-- /wp:list -->', 'placeholders' => array() );
	$qa_with_invalid_markup = BHP_Content_QA_Gate::evaluate( $brief, $invalid_markup_for_qa, $seo, $pinterest );
	bhp_cie_assert( 'fail' === $qa_with_invalid_markup['checks']['gutenberg_markup_valid']['state'], 'QA gate: invalid Gutenberg markup fails the gutenberg_markup_valid check', $failures );
	bhp_cie_assert( 'fail' === $qa_with_invalid_markup['overall_status'], 'QA gate: invalid markup forces overall_status to fail', $failures );

	$qa_with_confirmations = BHP_Content_QA_Gate::evaluate( $brief, $draft, $seo, $pinterest, array(), array( 'factual_accuracy' => 'Andrew', 'audience_fit' => 'Andrew' ) );
	bhp_cie_assert( 'pass' === $qa_with_confirmations['checks']['factual_accuracy']['state'], 'QA gate: factual_accuracy becomes pass ONLY when a named human explicitly confirms it via $editorial_confirmations -- never inferred', $failures );
	bhp_cie_assert( 'Andrew' === $qa_with_confirmations['checks']['factual_accuracy']['detail']['confirmed_by'], 'QA gate: records exactly who confirmed factual accuracy, for audit', $failures );
	bhp_cie_assert( 'pass' === $qa_with_confirmations['checks']['audience_fit_editorial']['state'], 'QA gate: audience_fit_editorial becomes pass ONLY with an explicit human confirmation', $failures );
}

// ==================== Feedback Loop ====================

$feedback_no_data = BHP_Content_Feedback_Loop::evaluate_url( '/blog/never-imported-url/', 28 );
bhp_cie_assert( false === $feedback_no_data['data_available'], 'Feedback loop: correctly reports no data available rather than fabricating a metric', $failures );
bhp_cie_assert( 'monitor_longer' === $feedback_no_data['recommendation']['type'], 'Feedback loop: recommends monitoring longer when no data exists yet', $failures );

$fixture_import = BHP_Analytics_Adapter::import_rows( 'gsc', array(
	array( 'page' => '/blog/feedback-test/', 'impressions' => 5000, 'clicks' => 30, 'ctr' => 0.006, 'position' => 3.0, 'date' => gmdate( 'Y-m-d' ) ),
), array( 'is_fixture' => true ) );
$cleanup_post_ids[] = $fixture_import['import_id'];
$feedback_with_data = BHP_Content_Feedback_Loop::evaluate_url( '/blog/feedback-test/', 28 );
bhp_cie_assert( true === $feedback_with_data['data_available'], 'Feedback loop: reports data available once a matching row has been imported', $failures );
bhp_cie_assert( true === $feedback_with_data['data_is_fixture'], 'Feedback loop: correctly flags fixture-derived data as fixture, never presented as live', $failures );
bhp_cie_assert( 'improve_title' === $feedback_with_data['recommendation']['type'], 'Feedback loop: high impressions + very low CTR recommends improving the title', $failures );

// ==================== Internal Link Engine ====================

$inventory_pair = array(
	array( 'url' => '/a', 'title' => 'Page A', 'primary_topic' => 'ocean', 'featured_book' => 'mariana_trench', 'audience' => 'parent', 'funnel_stage' => 'awareness', 'internal_link_targets' => array(), 'analytics_identifiers' => array( 'url_normalized' => '/a' ) ),
	array( 'url' => '/b', 'title' => 'Page B', 'primary_topic' => 'ocean', 'featured_book' => 'mariana_trench', 'audience' => 'parent', 'funnel_stage' => 'awareness', 'internal_link_targets' => array( '/a' ), 'analytics_identifiers' => array( 'url_normalized' => '/b' ) ),
);
$recs = BHP_Internal_Link_Engine::recommend_for_new_content( array( 'primary_topic' => 'ocean', 'featured_book' => 'mariana_trench', 'audience' => 'parent', 'funnel_stage' => 'awareness' ), $inventory_pair, 4 );
bhp_cie_assert( ! empty( $recs ), 'Internal link engine: produces recommendations when topic/book/audience overlap exists', $failures );

$orphans = BHP_Internal_Link_Engine::detect_orphans( $inventory_pair );
bhp_cie_assert( in_array( '/a', $orphans, true ) === false, 'Internal link engine: a page with an incoming link is not an orphan even with no outgoing links', $failures );

// ==================== Cleanup ====================

foreach ( array_unique( $cleanup_post_ids ) as $post_id ) {
	if ( $post_id ) {
		wp_delete_post( $post_id, true );
	}
}
// Remove the generated content-engine artifacts for the unit-test blog slugs, if any leaked.
$test_dirs = glob( get_template_directory() . '/content-engine/blogs/test-*' );
foreach ( (array) $test_dirs as $dir ) {
	array_map( 'unlink', glob( $dir . '/*' ) );
	@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- best-effort cleanup of a local test artifact directory.
}

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL CONTENT INTELLIGENCE ENGINE TESTS PASSED\n";
