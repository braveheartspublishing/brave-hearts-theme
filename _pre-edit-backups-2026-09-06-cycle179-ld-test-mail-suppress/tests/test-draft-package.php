<?php
/**
 * Brave Hearts Publishing — Phase 1E: full draft-package test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file tests/test-draft-package.php --user=1 --url=<staging-host>
 *
 * Covers the full publishing-package expansion: taxonomy inventory +
 * assignment (real existing terms only, never fabricated/created),
 * SEO field mapping to real Rank Math postmeta, classification/CTA/
 * lead-offer persistence, image metadata, internal-link validation,
 * Pinterest linkage, analytics/attribution (no PII), editorial
 * governance, the strict full-package draft gate (field-by-field
 * blocking report), and rollback safety. Cleans up every fixture it
 * creates and never touches an existing post or taxonomy term.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
function bhp_dp_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$cleanup_post_ids = array();

// ==================== Fixture pipeline: queue -> brief -> real prose -> SEO -> Pinterest -> QA ====================

$category_count_before = wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
$tag_count_before      = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );

$queue_id = BHP_Content_Production_Queue::add_item( array(
	'recommendation_type' => 'create_new_article',
	'proposed_slug'       => 'bhp-test-draft-package-bridge-books',
	'audience'            => 'parent',
	'funnel_stage'        => 'awareness',
	'content_intent'      => 'reading_development',
	'primary_keyword'     => 'bridge books for kids ages 6-9',
	'secondary_keywords'  => array( 'bridge books for struggling readers' ),
	'featured_book'       => 'mariana_trench',
	'lead_offer'          => 'adventure_kit_parent',
	'cta_goal'            => 'adventure_kit_signup',
	'confidence'          => 'high',
	'owner'               => 'test-suite',
) );
BHP_Content_Production_Queue::transition( $queue_id, 'approved', 'unit-test-approver' );

$brief = BHP_Content_Brief_Generator::generate( $queue_id );
bhp_dp_assert( ! is_wp_error( $brief ), 'Fixture: brief generates successfully', $failures );

$real_prose = array(
	'opening_hook' => 'Plenty of kids stall out right after picture books -- bridge books are what actually gets them into full chapter books.',
	'sections'     => array(
		array( 'heading' => 'What makes a bridge book different', 'body' => 'Shorter chapters, more white space, and a picture every few pages keep momentum going without feeling babyish.' ),
		array( 'heading' => 'How to pick the right one', 'body' => 'Match the reading level to the child, not the age on the cover, and let them choose the cover that excites them.' ),
	),
);
$article_draft = BHP_Blog_Draft_Generator::assemble_article_draft( $brief, $real_prose );
bhp_dp_assert( ! is_wp_error( $article_draft ), 'Fixture: real-prose article draft assembles successfully', $failures );

$inventory_items = BHP_Content_Inventory::build()['items'];
$seo             = BHP_SEO_Metadata_Package::generate( $brief, $inventory_items );
$pinterest       = BHP_Pinterest_Variant_Generator::generate( $brief, $seo );
$originality_confirmations = array_slice( $brief['originality_requirement']['options'], 0, $brief['originality_requirement']['minimum_required'] );
$qa              = BHP_Content_QA_Gate::evaluate( $brief, $article_draft, $seo, $pinterest, $originality_confirmations, array(
	'factual_accuracy' => 'Andrew (test)',
	'audience_fit'     => 'Andrew (test)',
) );

// ==================== Workstream 1: Taxonomy inventory ====================

$taxonomy_inventory = BHP_Taxonomy_Inventory::build( true );
bhp_dp_assert( $taxonomy_inventory['category_count'] > 0, 'Taxonomy inventory: reports a non-zero real category count', $failures );
bhp_dp_assert( $taxonomy_inventory['tag_count'] > 0, 'Taxonomy inventory: reports a non-zero real tag count', $failures );
bhp_dp_assert( is_bool( $taxonomy_inventory['primary_category_field_enabled'] ), 'Taxonomy inventory: reports whether the SEO plugin\'s primary-category field is enabled', $failures );
bhp_dp_assert( 'rank_math_primary_category' === $taxonomy_inventory['primary_category_meta_key'], 'Taxonomy inventory: records the real Rank Math primary-category meta key', $failures );
$bridge_cat = array_filter( $taxonomy_inventory['categories'], static function ( $c ) { return 'bridge-books' === $c['slug']; } );
bhp_dp_assert( ! empty( $bridge_cat ), 'Taxonomy inventory: finds the real, existing "Bridge Books" category', $failures );

// ==================== Workstreams 2-3: Category + tag assignment ====================

$taxonomy_assignment = BHP_Taxonomy_Assignment_Engine::assign( $brief, $taxonomy_inventory );
bhp_dp_assert( $taxonomy_assignment['primary_category_id'] > 0, 'Taxonomy assignment: assigns a primary category', $failures );
bhp_dp_assert( 'uncategorized' !== get_term( $taxonomy_assignment['primary_category_id'] )->slug, 'Taxonomy assignment: never falls back to Uncategorized', $failures );
bhp_dp_assert( ! in_array( $taxonomy_assignment['primary_category_id'], $taxonomy_assignment['secondary_category_ids'], true ), 'Taxonomy assignment: primary category never duplicated in secondary list', $failures );
bhp_dp_assert( count( $taxonomy_assignment['secondary_category_ids'] ) === count( array_unique( $taxonomy_assignment['secondary_category_ids'] ) ), 'Taxonomy assignment: no duplicate secondary categories', $failures );
bhp_dp_assert( ! empty( $taxonomy_assignment['tag_ids'] ), 'Taxonomy assignment: assigns at least one existing tag', $failures );
bhp_dp_assert( count( $taxonomy_assignment['tag_ids'] ) === count( array_unique( $taxonomy_assignment['tag_ids'] ) ), 'Taxonomy assignment: no duplicate tags', $failures );
bhp_dp_assert( count( $taxonomy_assignment['tag_ids'] ) <= BHP_Taxonomy_Assignment_Engine::MAX_TAGS, 'Taxonomy assignment: tag count never exceeds the configured cap', $failures );
bhp_dp_assert( 'approved' === $taxonomy_assignment['taxonomy_approval_status'], 'Taxonomy assignment: existing-term matches need no further approval', $failures );

// No-match case: a nonsense keyword profile should never fabricate a term or fall back to Uncategorized.
$no_match_brief = array_merge( $brief, array( 'primary_keyword' => 'zzz_no_real_taxonomy_overlap_zzz', 'secondary_keywords' => array(), 'featured_book' => '', 'content_intent' => 'zzz', 'target_audience' => 'zzz' ) );
$no_match_assignment = BHP_Taxonomy_Assignment_Engine::assign( $no_match_brief, $taxonomy_inventory );
bhp_dp_assert( 0 === $no_match_assignment['primary_category_id'], 'Taxonomy assignment: no fabricated category when nothing matches', $failures );
bhp_dp_assert( 'approval_required' === $no_match_assignment['taxonomy_approval_status'], 'Taxonomy assignment: flags approval_required rather than guessing when no existing term matches', $failures );
bhp_dp_assert( 'create_new_category_recommended' === $no_match_assignment['category_recommendation']['action'], 'Taxonomy assignment: recommends (never creates) a new category when nothing matches', $failures );

// Near-duplicate singular/plural tag rejection.
$dup_tags = array(
	array( 'term_id' => 1001, 'name' => 'reluctant reader', 'slug' => 'reluctant-reader-x', 'parent' => 0, 'description' => '', 'post_count' => 5 ),
	array( 'term_id' => 1002, 'name' => 'reluctant readers', 'slug' => 'reluctant-readers-x', 'parent' => 0, 'description' => '', 'post_count' => 3 ),
);
$dup_result = BHP_Taxonomy_Assignment_Engine::assign( array( 'primary_keyword' => 'reluctant reader books', 'secondary_keywords' => array(), 'featured_book' => '', 'content_intent' => '', 'target_audience' => '' ), array( 'categories' => array(), 'tags' => $dup_tags ) );
bhp_dp_assert( 1 === count( $dup_result['tag_ids'] ), 'Taxonomy assignment: near-duplicate singular/plural tags collapse to one', $failures );
bhp_dp_assert( ! empty( array_filter( $dup_result['rejected_tag_candidates'], static function ( $r ) { return 'near_duplicate_of_already_selected_tag' === $r['reason']; } ) ), 'Taxonomy assignment: records the rejected near-duplicate with a reason', $failures );

// ==================== Workstream 5: SEO metadata mapping ====================

foreach ( array( 'seo_title', 'meta_description', 'primary_keyword', 'canonical_recommendation', 'robots_recommendation', 'schema_type_recommendation', 'breadcrumb_title', 'open_graph_title', 'open_graph_description', 'twitter_title', 'twitter_description' ) as $field ) {
	bhp_dp_assert( ! empty( $seo[ $field ] ), "SEO package: {$field} is populated", $failures );
}
$postmeta_map = BHP_SEO_Metadata_Package::to_rank_math_postmeta( $seo, $taxonomy_assignment['primary_category_id'] );
bhp_dp_assert( $postmeta_map['rank_math_title'] === $seo['seo_title'], 'SEO mapping: rank_math_title maps to seo_title', $failures );
bhp_dp_assert( $postmeta_map['rank_math_description'] === $seo['meta_description'], 'SEO mapping: rank_math_description maps to meta_description', $failures );
bhp_dp_assert( $postmeta_map['rank_math_focus_keyword'] === $seo['primary_keyword'], 'SEO mapping: rank_math_focus_keyword maps to primary_keyword', $failures );
bhp_dp_assert( $postmeta_map['rank_math_canonical_url'] === $seo['canonical_recommendation'], 'SEO mapping: rank_math_canonical_url is mapped', $failures );
bhp_dp_assert( $postmeta_map['rank_math_facebook_title'] === $seo['open_graph_title'], 'SEO mapping: Open Graph title maps to rank_math_facebook_title (Rank Math\'s real field name)', $failures );
bhp_dp_assert( $postmeta_map['rank_math_twitter_title'] === $seo['twitter_title'], 'SEO mapping: Twitter title maps to rank_math_twitter_title', $failures );
bhp_dp_assert( (string) $taxonomy_assignment['primary_category_id'] === $postmeta_map['rank_math_primary_category'], 'SEO mapping: primary category ID is mapped to rank_math_primary_category', $failures );
$placeholder_seo = array( 'seo_title' => 'x', 'meta_description' => '[PLACEHOLDER: not real]', 'primary_keyword' => 'x', 'canonical_recommendation' => 'x', 'breadcrumb_title' => 'x', 'open_graph_title' => 'x', 'open_graph_description' => 'x', 'twitter_title' => 'x', 'twitter_description' => 'x' );
bhp_dp_assert( ! isset( BHP_SEO_Metadata_Package::to_rank_math_postmeta( $placeholder_seo )['rank_math_description'] ), 'SEO mapping: never writes a literal placeholder string into a real postmeta value', $failures );

// Duplicate/slug-collision/cannibalization detection (reuses existing inventory-based checks).
$dup_inventory = array( array( 'url' => home_url( '/' . $seo['proposed_slug'] . '/' ), 'seo' => array( 'title' => $seo['seo_title'], 'description' => '' ), 'primary_keyword' => $seo['primary_keyword'] ) );
$dup_validation = BHP_SEO_Metadata_Package::validate( $seo, $dup_inventory );
bhp_dp_assert( 'fail' === $dup_validation['state'], 'SEO validation: slug collision + keyword cannibalization against an existing item forces state to fail', $failures );

// ==================== Workstream 6: Classification metadata ====================

// Synthetic, schema-valid Author Fingerprint package -- required as of the
// Author Fingerprint integration; see tests/test-author-fingerprint-package.php
// for the dedicated corpus/connection/fingerprint/voice/handoff test suite.
$test_author_package = array(
	'schema_version'   => 1,
	'package_uuid'     => 'test-draft-package-suite-' . wp_generate_uuid4(),
	'content_brief_id' => 1,
	'generated_at'     => current_time( 'mysql', true ),
	'checksum_sha256'  => str_repeat( 'a', 64 ),
	'provenance'       => array( 'generator' => 'brave-hearts-seo-engine', 'exported_by' => 'test-suite' ),
	'brief'            => array( 'working_title' => 'test topic', 'status' => 'brief_approved', 'research_packet_id' => 1 ),
	'research_packet'  => array( 'topic' => 'test topic', 'status' => 'research_packet_approved', 'corpus_gate_passed' => true, 'seo_opportunity_id' => null ),
	'corpus_manifest'  => array(
		array( 'source_id' => 'SRC-001', 'title' => 'Brand Skill', 'canonical_status' => 'canonical', 'mandatory_key' => 'brand_identity', 'checksum_sha256' => str_repeat( 'b', 64 ) ),
		array( 'source_id' => 'SRC-002', 'title' => 'Life Story Skill', 'canonical_status' => 'canonical', 'mandatory_key' => 'founder_life_story', 'checksum_sha256' => str_repeat( 'c', 64 ) ),
		array( 'source_id' => 'SRC-003', 'title' => 'Volume I', 'canonical_status' => 'canonical', 'mandatory_key' => 'volume_1_manuscript', 'checksum_sha256' => str_repeat( 'd', 64 ) ),
		array( 'source_id' => 'SRC-004', 'title' => 'Volume II', 'canonical_status' => 'canonical_provisional', 'mandatory_key' => 'volume_2_manuscript', 'checksum_sha256' => str_repeat( 'e', 64 ) ),
	),
	'brand_voice_profile' => array( 'source_id' => 'SRC-001', 'source_title' => 'Brave Hearts Publishing -- Brand Skill', 'approved_taglines' => array( 'Big Places. Brave Hearts.' ) ),
	'author_connection' => array(
		'anecdote_id' => 1, 'anecdote_key' => 'la-ventana-hammock-first-book',
		'full_text' => 'I wrote the first one in a hammock in Baja California, watching the Sea of Cortez.',
		'source_document_id' => 2, 'source_passage' => 'BRAND MOMENTS section, verbatim quote.',
		'verification_state' => 'confirmed', 'prohibited_uses' => null,
		'topic_categories' => array( 'origin_story' ), 'reuse_count' => 0, 'prior_uses' => array(),
	),
	'author_fingerprint_check' => array( 'has_author_connection' => true, 'prohibited_matches' => array(), 'overused_anecdotes' => array(), 'passed' => true ),
	'book_corpus_grounding' => array( 'status' => 'not_yet_populated', 'note' => 'No structured book-fact registry exists yet.' ),
	'global_prohibited_uses' => array( 'Do not name a specific Himalayan peak.' ),
);

$package = BHP_Draft_Package_Builder::build( BHP_Content_Production_Queue::get_item( $queue_id ), $brief, $article_draft, $seo, $pinterest, $qa, array(), array(), $test_author_package );
bhp_dp_assert( 'parent' === $package['classification']['audience'], 'Classification: audience persisted from the brief', $failures );
bhp_dp_assert( 'awareness' === $package['classification']['funnel_stage'], 'Classification: funnel_stage persisted', $failures );
bhp_dp_assert( 'reading_development' === $package['classification']['content_intent'], 'Classification: content_intent persisted', $failures );
bhp_dp_assert( 'mariana_trench' === $package['classification']['featured_book'], 'Classification: featured_book persisted', $failures );
bhp_dp_assert ( ! empty( $package['classification']['primary_cta'] ), 'Classification: primary CTA resolved', $failures );
bhp_dp_assert( 'adventure_kit_parent' === $package['classification']['lead_offer'], 'Classification: lead_offer persisted', $failures );
bhp_dp_assert( $queue_id === $package['classification']['queue_id'], 'Classification: queue_id linkage correct', $failures );
bhp_dp_assert( $brief['blog_slug'] === $package['classification']['brief_id'], 'Classification: brief_id linkage correct', $failures );

// ==================== Workstream 7: Images ====================

bhp_dp_assert( in_array( $package['images']['featured_image']['status'], BHP_Image_Metadata_Package::STATUSES, true ), 'Images: featured image status is one of the defined enum values', $failures );
$complete_no_alt = BHP_Image_Metadata_Package::validate( array( 'status' => 'complete', 'attachment_id' => 5, 'alt_text' => '[PLACEHOLDER: x]' ), array() );
bhp_dp_assert( 'revise' === $complete_no_alt['state'], 'Images: status=complete with a still-placeholder alt text is flagged, never silently passed', $failures );
$missing_status = BHP_Image_Metadata_Package::validate( array( 'status' => 'not_a_real_status' ), array() );
bhp_dp_assert( 'revise' === $missing_status['state'], 'Images: an invalid/missing status is flagged rather than silently accepted', $failures );

// ==================== Workstream 8: Internal links ====================

$broken_body = '<!-- wp:paragraph --><p>See <a href="' . home_url( '/this-page-does-not-exist-anywhere/' ) . '">this guide</a>.</p><!-- /wp:paragraph -->';
$broken_findings = BHP_Internal_Link_Engine::validate_body_links( $broken_body );
bhp_dp_assert( ! empty( $broken_findings ), 'Internal links: a link to a non-existent internal URL is flagged as broken', $failures );

$existing_url  = home_url( '/' );
$real_post_ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids' ) );
if ( ! empty( $real_post_ids ) ) {
	$good_url  = get_permalink( $real_post_ids[0] );
	$good_body = '<!-- wp:paragraph --><p>See <a href="' . esc_url( $good_url ) . '">this guide</a>.</p><!-- /wp:paragraph -->';
	bhp_dp_assert( empty( BHP_Internal_Link_Engine::validate_body_links( $good_body ) ), 'Internal links: a link to a real, existing published post is NOT flagged as broken', $failures );
}
bhp_dp_assert( empty( BHP_Internal_Link_Engine::validate_body_links( '<!-- wp:paragraph --><p>No links here.</p><!-- /wp:paragraph -->' ) ), 'Internal links: body with no links produces no findings', $failures );

// ==================== Workstream 9: Pinterest linkage ====================

bhp_dp_assert( 4 === $package['pinterest']['variant_count'], 'Pinterest linkage: exactly 4 variants linked', $failures );
bhp_dp_assert( 'not_published' === $package['pinterest']['publishing_status'], 'Pinterest linkage: publishing_status is always not_published (this system never publishes pins)', $failures );
foreach ( $package['pinterest']['variants'] as $variant ) {
	bhp_dp_assert( false !== strpos( $variant['utm_content'], $brief['blog_slug'] ), "Pinterest linkage: {$variant['variant_type']} utm_content references the correct blog slug", $failures );
}
$types = wp_list_pluck( $package['pinterest']['variants'], 'variant_type' );
bhp_dp_assert( count( $types ) === count( array_unique( $types ) ), 'Pinterest linkage: no duplicate variant types', $failures );

// ==================== Workstream 10: Analytics/attribution ====================

bhp_dp_assert( false !== strpos( $package['analytics']['analytics_content_id'], $brief['blog_slug'] ), 'Analytics: content ID references the correct blog slug', $failures );
bhp_dp_assert( $queue_id === $package['analytics']['queue_id'], 'Analytics: queue_id linkage correct', $failures );
bhp_dp_assert( 'ocean_explorers' === $package['analytics']['campaign_id'], 'Analytics: campaign_id inferred correctly for the featured book (mariana_trench -> ocean_explorers)', $failures );
bhp_dp_assert( in_array( 7, $package['analytics']['monitoring_windows_days'], true ) && in_array( 28, $package['analytics']['monitoring_windows_days'], true ) && in_array( 90, $package['analytics']['monitoring_windows_days'], true ), 'Analytics: monitoring windows match the existing 7/28/90-day feedback-loop convention', $failures );
bhp_dp_assert( 'pass' === $package['analytics']['validation']['state'], 'Analytics: clean package passes the PII scan', $failures );
$pii_package = BHP_Analytics_Metadata_Package::validate( array( 'note' => 'contact parent at jane.doe@example.com for details' ) );
bhp_dp_assert( 'fail' === $pii_package['state'], 'Analytics: an embedded email address is caught by the PII scan', $failures );
$phone_package = BHP_Analytics_Metadata_Package::validate( array( 'note' => 'call 555-123-4567 for details' ) );
bhp_dp_assert( 'fail' === $phone_package['state'], 'Analytics: an embedded phone number is caught by the PII scan', $failures );

// ==================== Workstream 11: Editorial governance ====================

bhp_dp_assert( true === $package['editorial']['factual_review_complete'], 'Editorial governance: factual_review_complete reflects the QA gate\'s confirmed state', $failures );
bhp_dp_assert( 'Andrew (test)' === $package['editorial']['factual_reviewer'], 'Editorial governance: factual reviewer name is read from the QA gate, not re-entered separately', $failures );
bhp_dp_assert( true === $package['editorial']['audience_fit_review_complete'], 'Editorial governance: audience_fit_review_complete reflects the QA gate', $failures );
bhp_dp_assert( 'phase1e_content_engine' === $package['editorial']['created_by_system'], 'Editorial governance: created_by_system recorded', $failures );
bhp_dp_assert( is_array( $package['editorial']['factual_claims_requiring_review'] ), 'Editorial governance: factual claims list is present', $failures );

$html_body = $article_draft['content_html'];
bhp_dp_assert( false === strpos( $html_body, 'editor_notes' ) && false === strpos( $html_body, 'reviewer' ), 'Editorial governance: no governance field text leaks into the public article body', $failures );

// ==================== Workstream 14: Strict full-package draft gate ====================

$issues_complete = BHP_Draft_Package_Builder::validate_complete( $package, $article_draft, $qa );
bhp_dp_assert( empty( $issues_complete ), 'Draft gate: a fully complete package produces zero blocking issues (' . wp_json_encode( $issues_complete ) . ')', $failures );

// Missing category.
$pkg_no_category = $package;
$pkg_no_category['taxonomy']['primary_category_id'] = 0;
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_no_category, $article_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'taxonomy.primary_category' === $i['field']; } ) ), 'Draft gate: missing primary category blocks', $failures );

// Missing tags.
$pkg_no_tags = $package;
$pkg_no_tags['taxonomy']['tag_ids'] = array();
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_no_tags, $article_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'taxonomy.tags' === $i['field']; } ) ), 'Draft gate: missing tags blocks', $failures );

// Missing SEO title.
$pkg_no_seo_title = $package;
$pkg_no_seo_title['seo']['seo_title'] = '';
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_no_seo_title, $article_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'seo.seo_title' === $i['field']; } ) ), 'Draft gate: missing SEO title blocks', $failures );

// Missing meta description.
$pkg_no_desc = $package;
$pkg_no_desc['seo']['meta_description'] = '';
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_no_desc, $article_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'seo.meta_description' === $i['field']; } ) ), 'Draft gate: missing meta description blocks', $failures );

// Missing CTA.
$pkg_no_cta = $package;
$pkg_no_cta['classification']['primary_cta'] = '';
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_no_cta, $article_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'classification.primary_cta' === $i['field']; } ) ), 'Draft gate: missing primary CTA blocks', $failures );

// Missing analytics content ID.
$pkg_no_analytics = $package;
$pkg_no_analytics['analytics']['analytics_content_id'] = '';
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_no_analytics, $article_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'analytics.content_id' === $i['field']; } ) ), 'Draft gate: missing analytics content ID blocks', $failures );

// Incomplete factual review.
$qa_incomplete_factual = $qa;
$qa_incomplete_factual['checks']['factual_accuracy']['state'] = 'requires_human_review';
$pkg_incomplete_factual = $package;
$pkg_incomplete_factual['editorial']['factual_review_complete'] = false;
$pkg_incomplete_factual['qa'] = $qa_incomplete_factual;
$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_incomplete_factual, $article_draft, $qa_incomplete_factual );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'editorial.factual_review' === $i['field']; } ) ), 'Draft gate: incomplete factual review blocks', $failures );

// Unresolved placeholders in body.
$scaffold = BHP_Blog_Draft_Generator::generate( $brief );
$issues = BHP_Draft_Package_Builder::validate_complete( $package, $scaffold, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'body.placeholders' === $i['field']; } ) ), 'Draft gate: unresolved placeholders in the body block', $failures );

// Invalid Gutenberg markup.
$invalid_markup_draft = array( 'content_html' => '<!-- wp:list --><ul><li>flat, no list-item wrapping</li></ul><!-- /wp:list -->', 'placeholders' => array() );
$issues = BHP_Draft_Package_Builder::validate_complete( $package, $invalid_markup_draft, $qa );
bhp_dp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'body.gutenberg_markup' === $i['field']; } ) ), 'Draft gate: invalid Gutenberg markup blocks', $failures );

// ==================== End-to-end: successful full-package draft creation ====================

BHP_Content_Production_Queue::transition( $queue_id, 'ready_for_wp_draft', 'unit-test-approver' );

$refused_incomplete = BHP_WP_Draft_Workflow::create_full_package_draft( $queue_id, $article_draft, $pkg_no_category, 'unit-test-approver' );
bhp_dp_assert( is_wp_error( $refused_incomplete ) && 'bhp_wpdw_package_incomplete' === $refused_incomplete->get_error_code(), 'Draft gate: create_full_package_draft() refuses an incomplete package with a structured field-by-field report', $failures );
bhp_dp_assert( is_array( $refused_incomplete->get_error_data() ) && ! empty( $refused_incomplete->get_error_data() ), 'Draft gate: refusal carries a non-empty structured issues array via get_error_data()', $failures );

// Confirm an incomplete package really never reaches wp_insert_post(): no new post exists with our proposed slug yet.
bhp_dp_assert( null === get_page_by_path( $package['core']['proposed_slug'], OBJECT, 'post' ), 'Draft gate: incomplete package never actually creates a post (wp_insert_post never reached)', $failures );

$post_id = BHP_WP_Draft_Workflow::create_full_package_draft( $queue_id, $article_draft, $package, 'unit-test-approver' );
bhp_dp_assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Draft gate: a fully complete package successfully creates the draft', $failures );

if ( ! is_wp_error( $post_id ) ) {
	$created = get_post( $post_id );
	bhp_dp_assert( 'draft' === $created->post_status, 'Full package draft: post_status is draft', $failures );

	$full_pkg_readback = BHP_WP_Draft_Workflow::get_full_package( $post_id );
	bhp_dp_assert( (int) $taxonomy_assignment['primary_category_id'] === (int) $full_pkg_readback['primary_category_id'], 'Full package draft: primary category persisted and reads back correctly', $failures );
	bhp_dp_assert( in_array( (int) $taxonomy_assignment['tag_ids'][0], wp_list_pluck( $full_pkg_readback['tags'], 'term_id' ), true ), 'Full package draft: assigned tags persisted and read back correctly', $failures );
	bhp_dp_assert( $seo['seo_title'] === $full_pkg_readback['seo']['title'], 'Full package draft: SEO title persisted as real rank_math_title postmeta', $failures );
	bhp_dp_assert( 'parent' === $full_pkg_readback['classification']['audience'], 'Full package draft: audience persisted', $failures );
	bhp_dp_assert( $queue_id === (int) BHP_Content_Production_Queue::get_item( $queue_id )['queue_id'], 'Full package draft: queue linkage intact', $failures );
	bhp_dp_assert( 'wp_draft_created' === BHP_Content_Production_Queue::get_item( $queue_id )['status'], 'Full package draft: queue transitioned to wp_draft_created', $failures );

	// ==================== Rollback safety ====================

	$unrelated_post_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Unrelated real post (draft-package suite, never touch)' ), true );
	$refused_delete = BHP_WP_Draft_Workflow::delete_synthetic_draft( $unrelated_post_id );
	bhp_dp_assert( is_wp_error( $refused_delete ), 'Rollback: refuses to delete a post without the Phase 1E provenance marker', $failures );
	wp_delete_post( $unrelated_post_id, true );

	$term_before_delete = get_term( $taxonomy_assignment['primary_category_id'] );
	$deleted = BHP_WP_Draft_Workflow::delete_synthetic_draft( $post_id );
	bhp_dp_assert( ! is_wp_error( $deleted ), 'Rollback: successfully deletes its own synthetic draft', $failures );
	bhp_dp_assert( null === get_post( $post_id ), 'Rollback: post is actually gone', $failures );
	$term_after_delete = get_term( $taxonomy_assignment['primary_category_id'] );
	bhp_dp_assert( $term_before_delete->name === $term_after_delete->name, 'Rollback: the real category term used by the draft is completely untouched by deletion', $failures );
	bhp_dp_assert( 'ready_for_wp_draft' === BHP_Content_Production_Queue::get_item( $queue_id )['status'], 'Rollback: queue state resets to ready_for_wp_draft', $failures );
}

// ==================== No unauthorized taxonomy creation across the whole run ====================

$category_count_after = wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
$tag_count_after      = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );
bhp_dp_assert( $category_count_before === $category_count_after, 'No unauthorized creation: category count is identical before and after the entire test run', $failures );
bhp_dp_assert( $tag_count_before === $tag_count_after, 'No unauthorized creation: tag count is identical before and after the entire test run', $failures );

// ==================== Cleanup ====================

foreach ( $cleanup_post_ids as $id ) {
	wp_delete_post( $id, true );
}
wp_delete_post( $queue_id, true ); // the bhp_content_queue fixture item itself.

echo "\n";
if ( empty( $failures ) ) {
	echo "ALL DRAFT PACKAGE TESTS PASSED\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo " - {$f}\n";
	}
	exit( 1 );
}
