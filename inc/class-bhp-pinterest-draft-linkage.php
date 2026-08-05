<?php
/**
 * Brave Hearts Publishing — Phase 1E: Pinterest package <-> WordPress draft linkage.
 *
 * Wraps BHP_Pinterest_Variant_Generator's existing 4-variant output into
 * the exact shape a WordPress draft package needs to reference it --
 * this class generates no new creative content and never publishes a
 * pin; it only records which variants belong to which draft, for
 * storage as postmeta and display in the admin panel/CLI inspector.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Pinterest_Draft_Linkage {

	/**
	 * @param array $pinterest_package BHP_Pinterest_Variant_Generator::generate() output.
	 */
	public static function build( array $pinterest_package ) {
		$variants = array();
		foreach ( $pinterest_package['variants'] ?? array() as $variant ) {
			$variants[] = array(
				'variant_type'           => $variant['variant_type'],
				'title'                  => $variant['pinterest_title'],
				'description'            => $variant['pinterest_description'],
				'alt_text'               => $variant['alt_text'],
				'image_direction'        => $variant['visual_direction'],
				'destination_url'        => $variant['destination_url'],
				'utm_source'             => $variant['utm_source'],
				'utm_medium'             => $variant['utm_medium'],
				'utm_campaign'           => $variant['utm_campaign'],
				'utm_content'            => $variant['utm_content'],
				'approval_status'        => 'not_yet_reviewed', // publishing/approval is always a separate, explicit human action outside this system.
				'claim_validation_status' => $variant['claim_validation_status'] ?? 'pending_review',
			);
		}

		return array(
			'campaign_id'      => $pinterest_package['variants'][0]['campaign_id'] ?? '',
			'variant_count'    => count( $variants ),
			'variants'         => $variants,
			'publishing_status' => 'not_published', // this system never publishes pins.
			'validation'       => self::validate( $variants, $pinterest_package['validation'] ?? array() ),
		);
	}

	/**
	 * Reuses BHP_Pinterest_Variant_Generator::validate()'s findings (4
	 * distinct types, no duplicate headlines, valid utm_content) rather
	 * than re-checking the same rules a second way, and additionally
	 * confirms exactly 4 variants are actually linked to the draft.
	 */
	public static function validate( array $variants, array $generator_validation ) {
		$findings = $generator_validation['findings'] ?? array();
		if ( 4 !== count( $variants ) ) {
			$findings[] = 'Draft must link exactly 4 Pinterest variants; found ' . count( $variants ) . '.';
		}
		return array(
			'findings' => $findings,
			'state'    => empty( $findings ) ? 'pass' : 'revise',
		);
	}
}
