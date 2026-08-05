<?php
/**
 * Brave Hearts Publishing — Phase 1E: image metadata package.
 *
 * Builds a structured, explicit image-metadata record for a draft's
 * featured image and any inline images. Every image field has an
 * explicit status -- there is no "silently missing" state; a caller
 * that hasn't produced an image yet must say so with
 * 'pending_generation' or 'pending_upload' rather than leaving the
 * field empty. This class never generates or uploads an actual image;
 * it only records the metadata/status describing what still needs to
 * happen, for the draft gate and admin panel to surface.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Image_Metadata_Package {

	const STATUSES = array( 'complete', 'pending_generation', 'pending_upload', 'not_required' );

	/**
	 * @param array $brief BHP_Content_Brief_Generator::generate() output.
	 * @param array $overrides Optional explicit overrides, e.g. once a
	 *                         real featured image has been uploaded:
	 *                         array( 'featured_image' => array( 'status' => 'complete', 'attachment_id' => 123 ) ).
	 */
	public static function build( array $brief, array $overrides = array() ) {
		$slug = $brief['blog_slug'] ?? '';

		$featured = array_merge( array(
			'status'        => 'pending_generation',
			'attachment_id' => 0,
			'brief'         => 'Featured image for "' . ( $brief['working_title'] ?? $slug ) . '" -- see content-engine/config/brand-guidelines.yaml for voice/color/typography constraints.',
			'prompt'        => '[PLACEHOLDER: literal, specific image description -- must not depict a real, identifiable child or a fabricated classroom scene]',
			'filename'      => $slug . '-featured.jpg',
			'alt_text'      => '[PLACEHOLDER: literal alt text describing the image for a screen-reader user]',
			'caption'       => '',
			'credit'        => '',
		), $overrides['featured_image'] ?? array() );

		$inline = array_map( static function ( $item ) {
			return array_merge( array(
				'status'   => 'pending_generation',
				'filename' => '',
				'alt_text' => '[PLACEHOLDER: literal alt text describing the image for a screen-reader user]',
				'caption'  => '',
				'credit'   => '',
			), (array) $item );
		}, $overrides['inline_images'] ?? array() );

		return array(
			'featured_image'          => $featured,
			'inline_images'           => $inline,
			'inline_image_requirement' => empty( $inline ) ? 'not_required' : 'required',
			'pinterest_image_direction' => '[PLACEHOLDER: see content-engine Pinterest package for the 4 variant-specific image directions]',
			'social_image_direction'  => 'Reuses the featured image for Open Graph/Twitter unless a dedicated social crop is supplied.',
			'validation'              => self::validate( $featured, $inline ),
		);
	}

	/**
	 * Deterministic check: every image record must have one of the
	 * defined statuses, and 'complete' requires a real attachment_id and
	 * a non-placeholder alt text -- never silently passes.
	 */
	public static function validate( array $featured, array $inline_images ) {
		$findings = array();

		$findings = array_merge( $findings, self::validate_one( $featured, 'featured_image' ) );
		foreach ( $inline_images as $i => $image ) {
			$findings = array_merge( $findings, self::validate_one( $image, "inline_images[{$i}]" ) );
		}

		return array(
			'findings' => $findings,
			'state'    => empty( $findings ) ? 'pass' : 'revise',
		);
	}

	private static function validate_one( array $image, $label ) {
		$findings = array();
		$status   = $image['status'] ?? '';

		if ( ! in_array( $status, self::STATUSES, true ) ) {
			$findings[] = array( 'field' => $label, 'issue' => 'invalid_or_missing_status', 'detail' => "Status must be one of: " . implode( ', ', self::STATUSES ) . '.' );
			return $findings; // no point checking sub-fields against an unrecognized status
		}

		if ( 'complete' === $status ) {
			if ( empty( $image['attachment_id'] ) ) {
				$findings[] = array( 'field' => $label, 'issue' => 'complete_without_attachment', 'detail' => 'Status is complete but no attachment_id is set.' );
			}
			if ( empty( $image['alt_text'] ) || false !== strpos( $image['alt_text'], '[PLACEHOLDER' ) ) {
				$findings[] = array( 'field' => $label, 'issue' => 'complete_with_placeholder_alt', 'detail' => 'Status is complete but alt_text is still a placeholder.' );
			}
		}

		return $findings;
	}
}
