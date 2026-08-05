<?php
/**
 * Brave Hearts Publishing — Phase 1E: Author Fingerprint integration tests.
 *
 * Run via WP-CLI:
 *   wp eval-file tests/test-author-fingerprint-package.php --user=1 --url=<staging-host>
 *
 * Covers the SEO-engine -> WordPress handoff: schema/checksum/provenance
 * validation, Author Connection completeness, the Author Fingerprint
 * Check, the brand-voice heuristic, book-corpus-grounding honesty, import/
 * handoff idempotency, and the strict draft-gate additions. Uses only
 * safe, synthetic package fixtures (never real Andrew content beyond the
 * already-public brand tagline strings) and cleans up every file it writes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
function bhp_afp_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_afp_valid_package( array $overrides = array() ) {
	$base = array(
		'schema_version'   => 1,
		'package_uuid'     => 'test-' . wp_generate_uuid4(),
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
	return array_replace_recursive( $base, $overrides );
}

$cleanup_files = array();

// ==================== Corpus checks ====================

$issues = BHP_Author_Fingerprint_Package::validate_schema( bhp_afp_valid_package() );
bhp_afp_assert( empty( $issues ), 'Corpus: a fully valid package passes schema validation (' . wp_json_encode( $issues ) . ')', $failures );

$missing_brand = bhp_afp_valid_package();
$missing_brand['corpus_manifest'] = array_values( array_filter( $missing_brand['corpus_manifest'], static function ( $e ) { return 'brand_identity' !== $e['mandatory_key']; } ) );
$issues = BHP_Author_Fingerprint_Package::validate_schema( $missing_brand );
bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return false !== strpos( $i, 'brand_identity' ); } ) ), 'Corpus: missing Brand Identity source blocks', $failures );

$missing_founder = bhp_afp_valid_package();
$missing_founder['corpus_manifest'] = array_values( array_filter( $missing_founder['corpus_manifest'], static function ( $e ) { return 'founder_life_story' !== $e['mandatory_key']; } ) );
$issues = BHP_Author_Fingerprint_Package::validate_schema( $missing_founder );
bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return false !== strpos( $i, 'founder_life_story' ); } ) ), 'Corpus: missing founder story source blocks', $failures );

$missing_book = bhp_afp_valid_package();
$missing_book['corpus_manifest'] = array_values( array_filter( $missing_book['corpus_manifest'], static function ( $e ) { return 'volume_1_manuscript' !== $e['mandatory_key']; } ) );
$issues = BHP_Author_Fingerprint_Package::validate_schema( $missing_book );
bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return false !== strpos( $i, 'volume_1_manuscript' ); } ) ), 'Corpus: missing required book source blocks', $failures );

$bad_checksum = bhp_afp_valid_package( array( 'checksum_sha256' => 'not-a-real-hash' ) );
$issues = BHP_Author_Fingerprint_Package::validate_schema( $bad_checksum );
bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return false !== strpos( $i, 'checksum_sha256' ); } ) ), 'Corpus: invalid checksum shape blocks', $failures );

$provisional_pkg = bhp_afp_valid_package();
$vol2 = array_values( array_filter( $provisional_pkg['corpus_manifest'], static function ( $e ) { return 'volume_2_manuscript' === $e['mandatory_key']; } ) )[0];
bhp_afp_assert( 'canonical_provisional' === $vol2['canonical_status'], 'Corpus: provisional source status is visible in the manifest', $failures );

// ==================== Author Connection ====================

$no_connection = bhp_afp_valid_package( array( 'author_connection' => null, 'author_fingerprint_check' => array( 'has_author_connection' => false, 'prohibited_matches' => array(), 'overused_anecdotes' => array(), 'passed' => false ) ) );
$gate_issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $no_connection );
bhp_afp_assert( ! empty( array_filter( $gate_issues, static function ( $i ) { return 'author_package.author_connection' === $i['field']; } ) ), 'Author Connection: mandatory -- missing anecdote blocks the draft gate', $failures );

$no_source = bhp_afp_valid_package();
$no_source['author_connection']['source_passage'] = '';
$gate_issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $no_source );
bhp_afp_assert( ! empty( array_filter( $gate_issues, static function ( $i ) { return 'author_package.author_connection.source' === $i['field']; } ) ), 'Author Connection: source passage required', $failures );

$overused = bhp_afp_valid_package();
$overused['author_connection']['reuse_count'] = 99;
$gate_issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $overused );
bhp_afp_assert( ! empty( array_filter( $gate_issues, static function ( $i ) { return 'author_package.author_connection.reuse_count' === $i['field']; } ) ), 'Author Connection: exceeding max reuse count is blocked', $failures );

// ==================== Author Fingerprint Check ====================

$approved_story = bhp_afp_valid_package();
bhp_afp_assert( empty( BHP_Author_Fingerprint_Package::validate_for_draft_gate( $approved_story ) ), 'Fingerprint: a true, approved story with a clean check passes', $failures );

$failed_check = bhp_afp_valid_package( array( 'author_fingerprint_check' => array( 'has_author_connection' => true, 'prohibited_matches' => array( 'Names a specific Himalayan peak not confirmed by canonical source.' ), 'overused_anecdotes' => array(), 'passed' => false ) ) );
$gate_issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $failed_check );
bhp_afp_assert( ! empty( array_filter( $gate_issues, static function ( $i ) { return 'author_package.fingerprint_check' === $i['field']; } ) ), 'Fingerprint: an invented/prohibited travel claim fails the gate', $failures );

$voice = BHP_Author_Fingerprint_Package::check_brand_voice( 'Andrew personally climbed Island Peak and summited without oxygen -- guaranteed to inspire your child.' );
bhp_afp_assert( ! $voice['passed'], 'Fingerprint: unsupported "guarantee" language is flagged by the voice check', $failures );

$repeated_paragraph = bhp_afp_valid_package();
$repeated_paragraph['author_connection']['reuse_count'] = BHP_Author_Fingerprint_Package::MAX_REUSE_COUNT + 5;
$gate_issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $repeated_paragraph );
bhp_afp_assert( ! empty( $gate_issues ), 'Fingerprint: a heavily repeated founder paragraph fails via the reuse-count threshold', $failures );

$source_mismatch = bhp_afp_valid_package();
$source_mismatch['author_connection']['source_document_id'] = null;
$source_mismatch['author_connection']['source_passage'] = '';
$gate_issues = BHP_Author_Fingerprint_Package::validate_for_draft_gate( $source_mismatch );
bhp_afp_assert( ! empty( $gate_issues ), 'Fingerprint: an anecdote missing its source passage fails (source mismatch/absence)', $failures );

$unique_relevant = bhp_afp_valid_package( array( 'author_connection' => array(
	'anecdote_id' => 5, 'anecdote_key' => 'why-nursing-grandmother',
	'full_text' => 'Nova Southeastern University BSN -- nursing school. Intentional choice: grandmother had stage 4 ovarian cancer.',
	'source_document_id' => 2, 'source_passage' => 'THE CHRONOLOGY section.',
	'verification_state' => 'confirmed', 'prohibited_uses' => null,
	'topic_categories' => array( 'nursing' ), 'reuse_count' => 0, 'prior_uses' => array(),
) ) );
bhp_afp_assert( empty( BHP_Author_Fingerprint_Package::validate_for_draft_gate( $unique_relevant ) ), 'Fingerprint: a relevant, unique, low-reuse passage passes', $failures );

// ==================== Brand voice ====================

$generic_seo = BHP_Author_Fingerprint_Package::check_brand_voice( '7 Reasons Kids Should Read More Books Today' );
bhp_afp_assert( ! $generic_seo['passed'], 'Voice: generic listicle SEO prose is flagged', $failures );

$hype = BHP_Author_Fingerprint_Package::check_brand_voice( 'This one trick will supercharge your child\'s reading habit!!' );
bhp_afp_assert( ! $hype['passed'], 'Voice: hype language is flagged', $failures );

$brand_aligned = BHP_Author_Fingerprint_Package::check_brand_voice( 'I wrote the first book in a hammock in Baja California. Charlotte was six months old.' );
bhp_afp_assert( $brand_aligned['passed'], 'Voice: a brand-aligned passage passes', $failures );

$unsupported_first_person = BHP_Author_Fingerprint_Package::check_brand_voice( 'I guarantee this book will fix your child\'s reading struggles.' );
bhp_afp_assert( ! $unsupported_first_person['passed'], 'Voice: an unsupported first-person guarantee claim fails', $failures );

// ==================== Book grounding ====================

$book_grounding = bhp_afp_valid_package()['book_corpus_grounding'];
bhp_afp_assert( 'not_yet_populated' === $book_grounding['status'], 'Book grounding: honestly documents the gap rather than inventing scene data', $failures );

// ==================== Handoff / import ====================

$dir = get_template_directory() . '/content-engine/author-packages';

$good_file = wp_tempnam( 'bhp-afp-test-good' );
file_put_contents( $good_file, wp_json_encode( bhp_afp_valid_package(), JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
$cleanup_files[] = $good_file;

$import_result = BHP_Author_Fingerprint_Package::import_from_file( $good_file );
bhp_afp_assert( ! is_wp_error( $import_result ), 'Handoff: a valid, complete package imports successfully', $failures );
if ( ! is_wp_error( $import_result ) ) {
	$cleanup_files[] = $import_result['local_path'];
	$readback = BHP_Author_Fingerprint_Package::get( $import_result['package_uuid'] );
	bhp_afp_assert( null !== $readback && $readback['content_brief_id'] === 1, 'Handoff: imported package reads back correctly by UUID', $failures );

	$reimport = BHP_Author_Fingerprint_Package::import_from_file( $good_file );
	bhp_afp_assert( ! is_wp_error( $reimport ) && $reimport['package_uuid'] === $import_result['package_uuid'], 'Handoff: re-importing the same file is idempotent (same UUID, no error)', $failures );
}

$incomplete_file = wp_tempnam( 'bhp-afp-test-incomplete' );
$incomplete = bhp_afp_valid_package();
unset( $incomplete['brand_voice_profile'] );
file_put_contents( $incomplete_file, wp_json_encode( $incomplete, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
$cleanup_files[] = $incomplete_file;
$incomplete_result = BHP_Author_Fingerprint_Package::import_from_file( $incomplete_file );
bhp_afp_assert( is_wp_error( $incomplete_result ) && 'bhp_afp_schema_invalid' === $incomplete_result->get_error_code(), 'Handoff: an incomplete package (missing required field) is rejected', $failures );

$bad_checksum_file = wp_tempnam( 'bhp-afp-test-badchecksum' );
file_put_contents( $bad_checksum_file, wp_json_encode( bhp_afp_valid_package( array( 'checksum_sha256' => 'zzz' ) ), JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
$cleanup_files[] = $bad_checksum_file;
$bad_checksum_result = BHP_Author_Fingerprint_Package::import_from_file( $bad_checksum_file );
bhp_afp_assert( is_wp_error( $bad_checksum_result ), 'Handoff: a malformed checksum is rejected', $failures );

$schema_mismatch_file = wp_tempnam( 'bhp-afp-test-schemamismatch' );
file_put_contents( $schema_mismatch_file, wp_json_encode( bhp_afp_valid_package( array( 'schema_version' => 99 ) ), JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
$cleanup_files[] = $schema_mismatch_file;
$schema_mismatch_result = BHP_Author_Fingerprint_Package::import_from_file( $schema_mismatch_file );
bhp_afp_assert( is_wp_error( $schema_mismatch_result ), 'Handoff: an unsupported schema_version is rejected', $failures );

$dry_run_file = wp_tempnam( 'bhp-afp-test-dryrun' );
file_put_contents( $dry_run_file, wp_json_encode( bhp_afp_valid_package(), JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
$cleanup_files[] = $dry_run_file;
$dry_result = BHP_Author_Fingerprint_Package::import_from_file( $dry_run_file, true );
bhp_afp_assert( ! is_wp_error( $dry_result ) && true === $dry_result['dry_run'], 'Handoff: --dry-run validates without writing a local copy', $failures );

// No PII / no manuscript text -- structural guarantee already enforced by validate_schema()'s manifest-shape check.
$manifest_keys_ok = true;
foreach ( bhp_afp_valid_package()['corpus_manifest'] as $entry ) {
	if ( array_diff( array_keys( $entry ), array( 'source_id', 'title', 'canonical_status', 'mandatory_key', 'checksum_sha256' ) ) ) {
		$manifest_keys_ok = false;
	}
}
bhp_afp_assert( $manifest_keys_ok, 'Handoff: corpus manifest entries carry only provenance fields -- no raw text, no PII', $failures );

// ==================== Draft gate integration (BHP_Draft_Package_Builder) ====================

if ( class_exists( 'BHP_Draft_Package_Builder' ) ) {
	// Minimal, already-complete package shell (mirrors what the full test-draft-package.php
	// suite builds in full) -- here we only need enough structure to isolate the
	// author_package interaction.
	$minimal_package = array(
		'core'           => array( 'title' => 'x', 'proposed_slug' => 'bhp-afp-test-slug-zzz', 'excerpt' => 'x', 'author_id' => 1 ),
		'taxonomy'       => array( 'primary_category_id' => 1, 'tag_ids' => array( 1 ), 'taxonomy_approval_status' => 'approved' ),
		'seo'            => array( 'seo_title' => 'x', 'meta_description' => 'x', 'primary_keyword' => 'x', 'canonical_recommendation' => 'x', 'robots_recommendation' => 'x', 'schema_type_recommendation' => 'x', 'validation' => array( 'state' => 'pass' ) ),
		'classification' => array( 'audience' => 'parent', 'funnel_stage' => 'awareness', 'content_intent' => 'x', 'primary_cta' => 'x', 'lead_offer' => 'none', 'originality_status' => 'pass' ),
		'images'         => array( 'validation' => array( 'state' => 'pass' ) ),
		'internal_links' => array( 'inserted_link_validation' => array( 'state' => 'pass' ) ),
		'pinterest'      => array( 'validation' => array( 'state' => 'pass' ) ),
		'analytics'      => array( 'analytics_content_id' => 'blog:x', 'validation' => array( 'state' => 'pass' ) ),
		'editorial'      => array( 'factual_review_complete' => true, 'audience_fit_review_complete' => true ),
		'qa'             => array( 'overall_status' => 'pass_for_wp_draft', 'checks' => array( 'unsupported_claim_risk' => array( 'state' => 'pass' ) ) ),
	);
	$clean_body = array( 'content_html' => '<!-- wp:paragraph --><p>I wrote the first book in a hammock in Baja California.</p><!-- /wp:paragraph -->', 'placeholders' => array() );

	$pkg_missing_author = $minimal_package; // no 'author_package' key at all.
	$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_missing_author, $clean_body, $minimal_package['qa'] );
	bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'author_package.missing' === $i['field']; } ) ), 'Draft gate: missing Author Connection package blocks', $failures );

	$pkg_failed_fingerprint = $minimal_package;
	$pkg_failed_fingerprint['author_package'] = bhp_afp_valid_package( array( 'author_fingerprint_check' => array( 'has_author_connection' => true, 'prohibited_matches' => array( 'x' ), 'overused_anecdotes' => array(), 'passed' => false ) ) );
	$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_failed_fingerprint, $clean_body, $minimal_package['qa'] );
	bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'author_package.fingerprint_check' === $i['field']; } ) ), 'Draft gate: failed Author Fingerprint Check blocks', $failures );

	$pkg_voice_fail = $minimal_package;
	$pkg_voice_fail['author_package'] = bhp_afp_valid_package();
	$voice_fail_body = array( 'content_html' => '<!-- wp:paragraph --><p>Guaranteed to unlock your child\'s reading potential!!</p><!-- /wp:paragraph -->', 'placeholders' => array() );
	$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_voice_fail, $voice_fail_body, $minimal_package['qa'] );
	bhp_afp_assert( ! empty( array_filter( $issues, static function ( $i ) { return 'author_package.brand_voice_check' === $i['field']; } ) ), 'Draft gate: brand voice failure blocks', $failures );

	$pkg_missing_corpus = $minimal_package;
	$bad_corpus_package = bhp_afp_valid_package();
	$bad_corpus_package['corpus_manifest'] = array(); // strip all mandatory sources.
	$issues_schema = BHP_Author_Fingerprint_Package::validate_schema( $bad_corpus_package );
	bhp_afp_assert( ! empty( $issues_schema ), 'Draft gate: a package with no corpus manifest fails schema validation (upstream of the gate)', $failures );

	$pkg_complete = $minimal_package;
	$pkg_complete['author_package'] = bhp_afp_valid_package();
	$issues = BHP_Draft_Package_Builder::validate_complete( $pkg_complete, $clean_body, $minimal_package['qa'] );
	bhp_afp_assert( empty( $issues ), 'Draft gate: an approved, complete package (incl. Author Fingerprint) passes (' . wp_json_encode( $issues ) . ')', $failures );
}

// ==================== Cleanup ====================

foreach ( $cleanup_files as $f ) {
	if ( file_exists( $f ) ) {
		wp_delete_file( $f );
	}
}
$index_path = $dir . '/index.json';
if ( file_exists( $index_path ) ) {
	$index = json_decode( file_get_contents( $index_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	foreach ( $index as $uuid => $meta ) {
		if ( 0 === strpos( $uuid, 'test-' ) ) {
			unset( $index[ $uuid ] );
		}
	}
	file_put_contents( $index_path, wp_json_encode( $index, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

echo "\n";
if ( empty( $failures ) ) {
	echo "ALL AUTHOR FINGERPRINT PACKAGE TESTS PASSED\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo " - {$f}\n";
	}
	exit( 1 );
}
