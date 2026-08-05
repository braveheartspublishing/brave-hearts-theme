<?php
/**
 * Brave Hearts Publishing — Phase 1E: editorial governance metadata.
 *
 * Persists everything an editor/auditor needs to trust a draft's
 * provenance and review history -- sources, unresolved questions,
 * approval history, versioning -- entirely as postmeta, never inside
 * the public article body. Reviewer names/dates are NOT re-entered here
 * -- they are read from BHP_Content_QA_Gate::evaluate()'s own
 * factual_accuracy / audience_fit_editorial checks (the single place a
 * human confirmation is ever recorded), so there is exactly one place
 * that fact can be wrong, not two parallel copies that can drift.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Editorial_Governance {

	/**
	 * @param array $brief     BHP_Content_Brief_Generator::generate() output.
	 * @param array $qa_result BHP_Content_QA_Gate::evaluate() output for this exact draft.
	 * @param array $overrides Optional: editor_notes, unresolved_questions, rollback_identifier, revision_notes.
	 */
	public static function build( array $brief, array $qa_result, array $overrides = array() ) {
		$factual_check  = $qa_result['checks']['factual_accuracy'] ?? array();
		$audience_check = $qa_result['checks']['audience_fit_editorial'] ?? array();

		return array_merge( array(
			'sources_used'                    => array(), // populated by the human writer alongside real prose; never fabricated by this system.
			'source_urls'                     => array(),
			'factual_claims_requiring_review'  => $brief['factual_claims_requiring_verification'] ?? array(),
			'factual_review_complete'         => 'pass' === ( $factual_check['state'] ?? '' ),
			'factual_reviewer'                => $factual_check['detail']['confirmed_by'] ?? '',
			'factual_review_confirmed_at'      => $factual_check['detail']['confirmed_at'] ?? '',
			'audience_fit_review_complete'     => 'pass' === ( $audience_check['state'] ?? '' ),
			'audience_fit_reviewer'            => $audience_check['detail']['confirmed_by'] ?? '',
			'audience_fit_confirmed_at'        => $audience_check['detail']['confirmed_at'] ?? '',
			'unresolved_questions'            => array(),
			'editor_notes'                    => '',
			'prohibited_claims_checked'        => array( 'fabricated reviews', 'fabricated statistics', 'fabricated awards', 'fabricated classroom testimonials', 'unverified "clinically proven"/"guaranteed" language' ),
			'approval_history'                => array(),
			'created_by_system'               => 'phase1e_content_engine',
			'created_date'                    => current_time( 'mysql', true ),
			'last_generated_date'             => current_time( 'mysql', true ),
			'last_reviewed_date'              => $factual_check['detail']['confirmed_at'] ?? '',
			'content_version'                 => 1,
			'rollback_identifier'             => '',
			'qa_reasons'                      => self::summarize_qa_reasons( $qa_result ),
			'revision_notes'                  => '',
		), $overrides );
	}

	private static function summarize_qa_reasons( array $qa_result ) {
		$reasons = array();
		foreach ( $qa_result['checks'] ?? array() as $name => $check ) {
			if ( in_array( $check['state'] ?? '', array( 'fail', 'revise', 'requires_human_review' ), true ) ) {
				$reasons[] = "{$name}: {$check['state']}";
			}
		}
		return $reasons;
	}
}
