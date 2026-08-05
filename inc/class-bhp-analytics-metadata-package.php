<?php
/**
 * Brave Hearts Publishing — Phase 1E: analytics/attribution metadata package.
 *
 * Persists the identifiers needed to later evaluate a published draft's
 * performance via the existing BHP_Content_Feedback_Loop / analytics
 * adapter -- never a new, parallel analytics concept. This class stores
 * IDs and labels only; it never stores or accepts anything resembling
 * personally identifiable information (an individual reader's name,
 * email, IP, or device identifier) -- validate() actively scans for
 * common PII shapes as a safety net, not just a naming convention.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Analytics_Metadata_Package {

	/**
	 * @param array $queue_item BHP_Content_Production_Queue::get_item() output.
	 * @param array $brief      BHP_Content_Brief_Generator::generate() output.
	 */
	public static function build( array $queue_item, array $brief ) {
		$package = array(
			'analytics_content_id'      => 'blog:' . ( $brief['blog_slug'] ?? '' ),
			'opportunity_record_id'     => $queue_item['source_evidence']['opportunity_id'] ?? null,
			'queue_id'                  => $queue_item['queue_id'] ?? 0,
			'brief_id'                  => $brief['blog_slug'] ?? '',
			'campaign_id'               => class_exists( 'BHP_Pinterest_Variant_Generator' ) ? self::infer_campaign( $brief ) : '',
			'cta_ids'                   => array_filter( array( $brief['primary_cta']['id'] ?? '' ) ),
			'audience'                  => $brief['target_audience'] ?? '',
			'funnel_stage'              => $brief['funnel_stage'] ?? '',
			'content_intent'            => $brief['content_intent'] ?? '',
			'featured_book'             => $brief['featured_book'] ?? '',
			'lead_offer'                => $brief['lead_offer'] ?? '',
			'data_source'               => $queue_item['source_evidence']['note'] ?? 'unspecified',
			'is_fixture_derived'        => false !== stripos( (string) ( $queue_item['source_evidence']['note'] ?? '' ), 'fixture' ),
			'monitoring_windows_days'   => array( 7, 28, 90 ), // matches BHP_Content_Feedback_Loop's existing review windows.
			'expected_ga4_events'       => array( 'page_view', 'view_item_list', 'related_content_click', 'lead_form_submit' ),
			'expected_conversion_path'  => ( $brief['funnel_stage'] ?? '' ) . ' -> ' . ( $brief['lead_offer'] ?: 'no_lead_offer' ) . ' -> product_page',
		);

		$package['validation'] = self::validate( $package );

		return $package;
	}

	private static function infer_campaign( array $brief ) {
		$book_campaign_map = array(
			'mariana_trench'    => 'ocean_explorers',
			'mount_everest'     => 'mountain_explorers',
			'amazon_rainforest' => 'rainforest_explorers',
		);
		return $book_campaign_map[ $brief['featured_book'] ?? '' ] ?? 'reluctant_reader_transition';
	}

	/**
	 * Deterministic: every string value in the package is scanned for
	 * email addresses and phone-number-shaped strings. This is a safety
	 * net, not a guarantee -- but it makes "no PII" a checked property
	 * instead of only an intention.
	 */
	public static function validate( array $package ) {
		$findings = array();
		array_walk_recursive( $package, static function ( $value, $key ) use ( &$findings ) {
			if ( ! is_string( $value ) ) {
				return;
			}
			if ( preg_match( '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $value ) ) {
				$findings[] = array( 'field' => $key, 'issue' => 'possible_pii_email', 'detail' => 'Value resembles an email address.' );
			}
			if ( preg_match( '/\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/', $value ) ) {
				$findings[] = array( 'field' => $key, 'issue' => 'possible_pii_phone', 'detail' => 'Value resembles a phone number.' );
			}
		} );

		return array(
			'findings' => $findings,
			'state'    => empty( $findings ) ? 'pass' : 'fail', // PII is never a "revise later" issue.
		);
	}
}
