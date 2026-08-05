<?php
/**
 * Brave Hearts Publishing — required contextual-links gate, added after
 * Andrew's explicit policy: every blog article must contain (1) at
 * least one contextual link to the relevant main-topic hub/collection/
 * category/resource page, and (2) at least one contextual link to a
 * relevant book/format-selection page, the series/collection page, or
 * an approved Amazon affiliate link. The automatic end-of-article CTA
 * does not satisfy either requirement by itself. See
 * docs/required-links-policy.md.
 *
 * Reuses BHP_CTA_Collision_Detector's contextual-link detection (a link
 * embedded in real sentence prose, not a promotional CTA block) so this
 * gate and the collision detector never disagree about what counts as
 * a contextual link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Required_Links_Gate {

	const META_OVERRIDE        = '_bhp_required_links_override'; // '', 'topic_hub', 'book_link', 'both'
	const META_OVERRIDE_REASON = '_bhp_required_links_override_reason';

	/**
	 * @param int    $post_id
	 * @param string $post_content
	 * @return array {
	 *     @type string $state 'pass' | 'fail' | 'overridden'
	 *     @type bool   $topic_hub_link_present
	 *     @type bool   $book_link_present
	 *     @type bool   $amazon_affiliate_link_present
	 *     @type array  $missing Which required classes are missing and not overridden.
	 *     @type array  $overridden Which required classes were explicitly waived.
	 *     @type string $override_reason
	 * }
	 */
	public static function check( $post_id, $post_content ) {
		if ( ! class_exists( 'BHP_CTA_Collision_Detector' ) ) {
			return array(
				'state'   => 'fail',
				'missing' => array( 'topic_hub', 'book_link' ),
				'error'   => 'BHP_CTA_Collision_Detector must be loaded first -- this gate reuses its contextual-link detection.',
			);
		}

		$cta_check = BHP_CTA_Collision_Detector::check( $post_id, $post_content );
		$topic_hub_present = ! empty( $cta_check['contextual_topic_hub_link_present'] );
		$book_link_present = ! empty( $cta_check['contextual_book_link_present'] ) || ! empty( $cta_check['contextual_amazon_affiliate_link_present'] );

		$override_raw = (string) get_post_meta( $post_id, self::META_OVERRIDE, true );
		$override_reason = (string) get_post_meta( $post_id, self::META_OVERRIDE_REASON, true );
		$override_topic_hub = in_array( $override_raw, array( 'topic_hub', 'both' ), true );
		$override_book_link = in_array( $override_raw, array( 'book_link', 'both' ), true );

		$missing = array();
		$overridden = array();

		if ( ! $topic_hub_present ) {
			if ( $override_topic_hub ) {
				$overridden[] = 'topic_hub';
			} else {
				$missing[] = 'topic_hub';
			}
		}
		if ( ! $book_link_present ) {
			if ( $override_book_link ) {
				$overridden[] = 'book_link';
			} else {
				$missing[] = 'book_link';
			}
		}

		if ( ! empty( $missing ) ) {
			$state = 'fail';
		} elseif ( ! empty( $overridden ) ) {
			$state = 'overridden';
		} else {
			$state = 'pass';
		}

		return array(
			'state'                          => $state,
			'topic_hub_link_present'         => $topic_hub_present,
			'book_link_present'              => $book_link_present,
			'amazon_affiliate_link_present'  => ! empty( $cta_check['contextual_amazon_affiliate_link_present'] ),
			'missing'                        => $missing,
			'overridden'                     => $overridden,
			'override_reason'                => $override_reason,
		);
	}
}
