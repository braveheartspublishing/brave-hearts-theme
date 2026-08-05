<?php
/**
 * Brave Hearts Publishing — classification completeness and
 * category-semantic-fit gate, added after Weekly Production Cycle 1
 * shipped two WordPress drafts with every _bhp_content_* classification
 * field unset (falling through to BHP_Content_Classification's
 * flat_default, exactly the "Unclassified" state this gate exists to
 * block) and a facts article carrying a "Book recommendations" category
 * it didn't semantically earn. See
 * docs/weekly-cycle-1-qa-failure-audit.md, defects #10 and #11.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Classification_Completeness_Gate {

	const REQUIRED_META_KEYS = array(
		'_bhp_content_audience',
		'_bhp_content_funnel_stage',
		'_bhp_content_intent',
		'_bhp_content_primary_goal',
	);

	/**
	 * Category names that a facts-format article (numbered facts about
	 * a real place/subject, not a curated book list) must not carry as
	 * a primary category purely because it mentions a book once at the
	 * end. This is a targeted rule for the exact defect found, not an
	 * attempt at general-purpose content-type inference.
	 */
	const FACTS_ARTICLE_DISALLOWED_CATEGORIES = array( 'Book recommendations' );

	/**
	 * @param int    $post_id
	 * @param string $content_type One of: 'facts_article', 'book_list',
	 *                              'guide', 'personal_essay', 'other'.
	 *                              Supplied explicitly by whoever is
	 *                              assembling the package -- this gate
	 *                              does not infer it, matching the
	 *                              existing BHP_Content_QA_Gate pattern
	 *                              of never auto-inferring editorial
	 *                              judgment calls.
	 * @return array {
	 *     @type array $missing_fields   Required meta keys with no value set.
	 *     @type array $category_conflicts Category names present that conflict with $content_type.
	 *     @type string $state 'pass' | 'fail'
	 * }
	 */
	public static function check( $post_id, $content_type ) {
		$missing_fields = array();
		foreach ( self::REQUIRED_META_KEYS as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' === trim( (string) $value ) ) {
				$missing_fields[] = $key;
			}
		}

		$category_conflicts = array();
		if ( 'facts_article' === $content_type ) {
			$categories = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
			foreach ( $categories as $cat ) {
				if ( in_array( $cat, self::FACTS_ARTICLE_DISALLOWED_CATEGORIES, true ) ) {
					$category_conflicts[] = "Category '{$cat}' assigned to a facts_article — a facts article does not earn 'Book recommendations' by mentioning a book once at the end; that category describes articles whose primary intent is recommending books.";
				}
			}
		}

		$state = ( empty( $missing_fields ) && empty( $category_conflicts ) ) ? 'pass' : 'fail';

		return array(
			'missing_fields'      => $missing_fields,
			'category_conflicts'  => $category_conflicts,
			'state'               => $state,
		);
	}
}
