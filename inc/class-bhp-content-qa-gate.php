<?php
/**
 * Brave Hearts Publishing — Phase 1E: unified content QA gate.
 *
 * Wraps the existing BHP_Conversion_Scoring (Phase 1D) together with
 * originality, SEO, Pinterest-readiness, and cannibalization checks
 * into one gate with a single, explainable status. This class NEVER
 * auto-passes factual accuracy through code alone -- that dimension is
 * always returned as 'requires_human_review', regardless of what every
 * other automated check says.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_QA_Gate {

	const STATUSES = array( 'fail', 'revise', 'editorial_review_required', 'pass_for_wp_draft', 'pass_for_publishing_review' );

	/**
	 * @param array $brief   BHP_Content_Brief_Generator::generate() output.
	 * @param array $draft   BHP_Blog_Draft_Generator::generate() or ::assemble_article_draft() output.
	 * @param array $seo     BHP_SEO_Metadata_Package::generate() output (already includes ['validation']).
	 * @param array $pinterest BHP_Pinterest_Variant_Generator::generate() output.
	 * @param array $manual_originality_confirmations List of confirmed checklist keys from a human editor.
	 * @param array $editorial_confirmations Optional array( 'factual_accuracy' => '<name>', 'audience_fit' => '<name>' ).
	 *              These two checks are NEVER auto-passed by any inference in this
	 *              method -- the only way either becomes 'pass' is a named human
	 *              explicitly asserting it here, the same evidentiary standard as
	 *              $approved_by elsewhere in this system. Omitting a key leaves that
	 *              check at 'requires_human_review'.
	 * @param int|null $post_id Draft post ID, required for the
	 *              classification-completeness and CTA-collision checks
	 *              added after Weekly Production Cycle 1 (see
	 *              docs/weekly-cycle-1-qa-failure-audit.md). Both checks
	 *              are skipped (not auto-passed) if omitted.
	 * @param string $content_type One of BHP_Classification_Completeness_Gate's
	 *              recognized types ('facts_article', 'book_list',
	 *              'guide', 'personal_essay', 'other'), supplied
	 *              explicitly by the package assembler.
	 */
	public static function evaluate( array $brief, array $draft, array $seo, array $pinterest, array $manual_originality_confirmations = array(), array $editorial_confirmations = array(), $post_id = null, $content_type = 'other' ) {
		$checks = array();

		// --- deterministic ---
		$checks['seo_metadata'] = array(
			'check_type' => 'deterministic',
			'state'      => $seo['validation']['state'],
			'detail'     => $seo['validation']['findings'],
		);
		$checks['pinterest_readiness'] = array(
			'check_type' => 'deterministic',
			'state'      => $pinterest['validation']['state'],
			'detail'     => $pinterest['validation']['findings'],
		);
		$prohibited_hits = class_exists( 'BHP_Content_Brief_Generator' )
			? BHP_Content_Brief_Generator::detect_prohibited_claims( wp_strip_all_tags( $draft['content_html'] ) )
			: array();
		$checks['unsupported_claim_risk'] = array(
			'check_type' => 'deterministic',
			'state'      => empty( $prohibited_hits ) ? 'pass' : 'fail',
			'detail'     => $prohibited_hits,
		);
		$placeholders_remaining = ! empty( $draft['placeholders'] ) || ( class_exists( 'BHP_Blog_Draft_Generator' ) && BHP_Blog_Draft_Generator::contains_editorial_instructions( $draft['content_html'] ?? '' ) );
		$checks['placeholders_resolved'] = array(
			'check_type' => 'deterministic',
			'state'      => $placeholders_remaining ? 'revise' : 'pass',
			'detail'     => $placeholders_remaining ? array( 'Draft still contains unresolved placeholder sections or internal editorial instructions: ' . implode( ', ', $draft['placeholders'] ?? array() ) ) : array(),
		);
		$markup_errors = class_exists( 'BHP_Blog_Draft_Generator' ) ? BHP_Blog_Draft_Generator::validate_markup( $draft['content_html'] ?? '' ) : array( 'Validator unavailable.' );
		$checks['gutenberg_markup_valid'] = array(
			'check_type' => 'deterministic',
			'state'      => empty( $markup_errors ) ? 'pass' : 'fail',
			'detail'     => $markup_errors,
		);

		// Added after Weekly Production Cycle 1 (see
		// docs/weekly-cycle-1-qa-failure-audit.md) -- catches
		// Squarespace-migration markup, nested/empty <p> tags, tag
		// imbalance, stray body <h1>, and www-hostname internal links
		// that none of the checks above were scoped to catch.
		$html_violations = class_exists( 'BHP_Content_HTML_Sanitizer' )
			? BHP_Content_HTML_Sanitizer::validate( $draft['content_html'] ?? '' )
			: array( 'Validator unavailable.' );
		$checks['html_sanitation'] = array(
			'check_type' => 'deterministic',
			'state'      => empty( $html_violations ) ? 'pass' : 'fail',
			'detail'     => $html_violations,
		);

		// --- heuristic ---
		$originality_findings = class_exists( 'BHP_Content_Originality' )
			? BHP_Content_Originality::check_draft( wp_strip_all_tags( $draft['content_html'] ) )
			: array();
		$has_fail = ! empty( array_filter( $originality_findings, static function ( $f ) {
			return 'fail' === $f['severity'];
		} ) );
		$checks['originality_structural'] = array(
			'check_type' => 'heuristic',
			'state'      => $has_fail ? 'fail' : ( empty( $originality_findings ) ? 'pass' : 'revise' ),
			'detail'     => $originality_findings,
		);
		$checks['cannibalization_risk'] = array(
			'check_type' => 'heuristic',
			'state'      => strpos( (string) ( $brief['cannibalization_warnings'] ?? '' ), 'shares a primary keyword' ) !== false ? 'revise' : 'pass',
			'detail'     => $brief['cannibalization_warnings'] ?? '',
		);

		// --- inferred (reuses Phase 1D's own scoring, itself already
		// tagged automated/inferred/manual per criterion) ---
		if ( class_exists( 'BHP_Conversion_Scoring' ) ) {
			$conversion_scoring = BHP_Conversion_Scoring::score_page( 'blog', $draft['content_html'], array(
				'source' => 'explicit',
				'audience' => $brief['target_audience'],
				'funnel_stage' => $brief['funnel_stage'],
			), array() );
			$checks['conversion_readiness'] = array(
				'check_type' => 'inferred',
				'state'      => $conversion_scoring['overall_state'],
				'detail'     => array( 'overall_score' => $conversion_scoring['overall_score'] ),
			);
		}

		// --- manual / human editorial judgment (never auto-passed) ---
		$originality_manual = class_exists( 'BHP_Content_Originality' )
			? BHP_Content_Originality::check_manual_originality_checklist( $brief, $manual_originality_confirmations )
			: array( 'pass' => false );
		$checks['originality_editorial_checklist'] = array(
			'check_type' => 'manual',
			'state'      => $originality_manual['pass'] ? 'pass' : 'revise',
			'detail'     => $originality_manual,
		);
		$factual_confirmed_by = trim( (string) ( $editorial_confirmations['factual_accuracy'] ?? '' ) );
		$checks['factual_accuracy'] = array(
			'check_type' => 'manual',
			// Never inferred or auto-passed by any check above -- this can
			// only become 'pass' when a named human explicitly confirms it
			// via $editorial_confirmations, recorded below for audit.
			'state'      => '' !== $factual_confirmed_by ? 'pass' : 'requires_human_review',
			'detail'     => '' !== $factual_confirmed_by
				? array( 'confirmed_by' => sanitize_text_field( $factual_confirmed_by ), 'confirmed_at' => current_time( 'mysql', true ), 'claims_reviewed' => $brief['factual_claims_requiring_verification'] ?? array() )
				: ( $brief['factual_claims_requiring_verification'] ?? array() ),
		);
		$audience_confirmed_by = trim( (string) ( $editorial_confirmations['audience_fit'] ?? '' ) );
		$checks['audience_fit_editorial'] = array(
			'check_type' => 'manual',
			'state'      => '' !== $audience_confirmed_by ? 'pass' : 'requires_human_review',
			'detail'     => '' !== $audience_confirmed_by
				? array( 'confirmed_by' => sanitize_text_field( $audience_confirmed_by ), 'confirmed_at' => current_time( 'mysql', true ) )
				: 'Editor must confirm tone/reading-level fit for ' . $brief['target_audience'],
		);

		// Added after Weekly Production Cycle 1 -- both require a real
		// $post_id (the checks operate on live postmeta/registry state,
		// not draft arrays), so they're skipped rather than auto-passed
		// when a package is evaluated before the post exists yet.
		if ( null !== $post_id ) {
			$classification = class_exists( 'BHP_Classification_Completeness_Gate' )
				? BHP_Classification_Completeness_Gate::check( $post_id, $content_type )
				: array( 'state' => 'skipped', 'missing_fields' => array(), 'category_conflicts' => array() );
			$checks['classification_completeness'] = array(
				'check_type' => 'deterministic',
				'state'      => 'skipped' === ( $classification['state'] ?? '' ) ? 'requires_human_review' : $classification['state'],
				'detail'     => array( 'missing_fields' => $classification['missing_fields'] ?? array(), 'category_conflicts' => $classification['category_conflicts'] ?? array() ),
			);

			$cta_collision = class_exists( 'BHP_CTA_Collision_Detector' )
				? BHP_CTA_Collision_Detector::check( $post_id, $draft['content_html'] ?? '' )
				: array( 'collision_state' => 'unknown' );
			$checks['cta_collision'] = array(
				'check_type' => 'deterministic',
				'state'      => 'none' === ( $cta_collision['collision_state'] ?? 'unknown' ) ? 'pass' : 'fail',
				'detail'     => $cta_collision,
			);

			// Required-contextual-links policy (2026-07-11) -- every
			// article needs a topic-hub link and a book/product link
			// in-body, distinct from the automatic CTA. 'overridden'
			// counts as passing the gate but is surfaced in detail so the
			// override reason stays visible in QA reports.
			$required_links = class_exists( 'BHP_Required_Links_Gate' )
				? BHP_Required_Links_Gate::check( $post_id, $draft['content_html'] ?? '' )
				: array( 'state' => 'skipped', 'missing' => array() );
			$checks['required_links'] = array(
				'check_type' => 'deterministic',
				'state'      => in_array( ( $required_links['state'] ?? '' ), array( 'pass', 'overridden' ), true ) ? 'pass' : 'fail',
				'detail'     => $required_links,
			);
		} else {
			$checks['classification_completeness'] = array( 'check_type' => 'deterministic', 'state' => 'requires_human_review', 'detail' => array( 'reason' => 'No post_id supplied to evaluate() -- cannot check live classification metadata.' ) );
			$checks['cta_collision'] = array( 'check_type' => 'deterministic', 'state' => 'requires_human_review', 'detail' => array( 'reason' => 'No post_id supplied to evaluate() -- cannot check guide-registry membership or automatic-CTA behavior.' ) );
			$checks['required_links'] = array( 'check_type' => 'deterministic', 'state' => 'requires_human_review', 'detail' => array( 'reason' => 'No post_id supplied to evaluate() -- cannot check in-body contextual links.' ) );
		}

		return array(
			'checks'        => $checks,
			'overall_status' => self::compute_overall_status( $checks ),
			'evaluated_at'  => current_time( 'mysql', true ),
		);
	}

	private static function compute_overall_status( array $checks ) {
		foreach ( $checks as $check ) {
			if ( 'fail' === $check['state'] ) {
				return 'fail';
			}
		}
		foreach ( $checks as $check ) {
			if ( 'requires_human_review' === $check['state'] ) {
				return 'editorial_review_required';
			}
		}
		foreach ( $checks as $check ) {
			if ( 'revise' === $check['state'] ) {
				return 'revise';
			}
		}
		return 'pass_for_wp_draft'; // 'pass_for_publishing_review' is only ever set manually by an editor after this gate, never computed here.
	}
}
