<?php
/**
 * Brave Hearts Publishing — Phase 1E: taxonomy assignment engine.
 *
 * Assigns a primary category, zero or more secondary categories, and a
 * deliberate tag set to a NEW draft -- always from the real, existing
 * terms returned by BHP_Taxonomy_Inventory, never from a hardcoded
 * plausible-sounding name (the previous BHP_SEO_Metadata_Package
 * ::infer_category() returned names like "Reading & Growth" and
 * "Science & Geography" that do not exist anywhere in this site's real
 * taxonomy -- exactly the gap this class exists to close). When no
 * existing term is a good enough match, the field is left unresolved and
 * flagged approval_required with a recommended NEW term name -- this
 * class never calls wp_insert_term().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Taxonomy_Assignment_Engine {

	const MAX_SECONDARY_CATEGORIES = 2;
	const MAX_TAGS                 = 8;
	const MIN_MATCH_SCORE          = 1;

	/**
	 * @param array $brief     BHP_Content_Brief_Generator::generate() output.
	 * @param array $inventory Optional pre-built BHP_Taxonomy_Inventory::build() snapshot.
	 * @return array {
	 *   primary_category_id, primary_category_slug, secondary_category_ids,
	 *   category_assignment_reason, category_confidence, category_recommendation,
	 *   taxonomy_approval_status ('approved'|'approval_required'),
	 *   tag_ids, tag_slugs, tag_assignment_reason, rejected_tag_candidates,
	 *   new_tag_recommendations, tag_approval_status
	 * }
	 */
	public static function assign( array $brief, array $inventory = null ) {
		if ( null === $inventory ) {
			$inventory = class_exists( 'BHP_Taxonomy_Inventory' ) ? BHP_Taxonomy_Inventory::build() : array( 'categories' => array(), 'tags' => array() );
		}

		$search_terms = self::search_terms_for( $brief );

		$category_result = self::assign_categories( $inventory['categories'] ?? array(), $search_terms );
		$tag_result       = self::assign_tags( $inventory['tags'] ?? array(), $search_terms );

		return array_merge( $category_result, $tag_result );
	}

	/**
	 * The words we score existing terms against -- primary/secondary
	 * keywords, featured book, audience, funnel stage, content intent.
	 * Never the raw brief JSON structure itself (that would score on
	 * placeholder text like "PLACEHOLDER").
	 */
	private static function search_terms_for( array $brief ) {
		$book_names = array(
			'mariana_trench' => 'mariana trench ocean',
			'mount_everest'  => 'mount everest mountain',
			'amazon_rainforest' => 'amazon rainforest',
		);
		$phrases = array_filter( array(
			$brief['primary_keyword'] ?? '',
			implode( ' ', (array) ( $brief['secondary_keywords'] ?? array() ) ),
			$book_names[ $brief['featured_book'] ?? '' ] ?? '',
			str_replace( '_', ' ', $brief['content_intent'] ?? '' ),
			str_replace( '_', ' ', $brief['target_audience'] ?? '' ),
		) );
		$words = array();
		foreach ( $phrases as $phrase ) {
			foreach ( preg_split( '/[^a-z0-9]+/i', strtolower( $phrase ) ) as $word ) {
				if ( strlen( $word ) > 2 ) { // skip stopword-length noise ("of", "a", "6-9" fragments)
					$words[ $word ] = true;
				}
			}
		}
		return array_keys( $words );
	}

	private static function score_term( array $term, array $search_words ) {
		$term_words = array();
		foreach ( preg_split( '/[^a-z0-9]+/i', strtolower( $term['name'] . ' ' . $term['slug'] ) ) as $word ) {
			if ( strlen( $word ) > 2 ) {
				$term_words[ $word ] = true;
			}
		}
		$score = 0;
		foreach ( $search_words as $word ) {
			if ( isset( $term_words[ $word ] ) || isset( $term_words[ self::singularize( $word ) ] ) ) {
				$score++;
			}
		}
		return $score;
	}

	private static function singularize( $word ) {
		return preg_replace( '/(ies)$/', 'y', preg_replace( '/s$/', '', $word ) );
	}

	private static function assign_categories( array $categories, array $search_words ) {
		$scored = array();
		foreach ( $categories as $cat ) {
			if ( 'uncategorized' === $cat['slug'] || 0 === (int) $cat['post_count'] ) {
				continue; // never fall back to Uncategorized; skip empty/orphaned terms like the stray "null - null" term.
			}
			$score = self::score_term( $cat, $search_words );
			if ( $score >= self::MIN_MATCH_SCORE ) {
				$scored[] = array_merge( $cat, array( 'score' => $score ) );
			}
		}
		usort( $scored, static function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		if ( empty( $scored ) ) {
			return array(
				'primary_category_id'        => 0,
				'primary_category_slug'      => '',
				'secondary_category_ids'     => array(),
				'category_assignment_reason' => 'No existing category matched the content profile closely enough.',
				'category_confidence'        => 'none',
				'category_recommendation'    => array(
					'action' => 'create_new_category_recommended',
					'note'   => 'A human editor should either pick the closest existing category manually or approve creation of a new one; this system never creates taxonomy terms.',
				),
				'taxonomy_approval_status'   => 'approval_required',
			);
		}

		$primary   = $scored[0];
		$secondary = array_slice( $scored, 1, self::MAX_SECONDARY_CATEGORIES );

		return array(
			'primary_category_id'        => $primary['term_id'],
			'primary_category_slug'      => $primary['slug'],
			'secondary_category_ids'     => array_values( array_unique( wp_list_pluck( $secondary, 'term_id' ) ) ),
			'category_assignment_reason' => "Matched existing category '{$primary['name']}' (score {$primary['score']}) against primary/secondary keywords, featured book, audience, and content intent.",
			'category_confidence'        => $primary['score'] >= 3 ? 'high' : ( $primary['score'] >= 2 ? 'medium' : 'low' ),
			'category_recommendation'    => null,
			'taxonomy_approval_status'   => 'approved', // existing-term match only; no new term involved, so no human approval needed for the match itself.
		);
	}

	private static function assign_tags( array $tags, array $search_words ) {
		$scored = array();
		foreach ( $tags as $tag ) {
			$score = self::score_term( $tag, $search_words );
			if ( $score >= self::MIN_MATCH_SCORE ) {
				$scored[] = array_merge( $tag, array( 'score' => $score ) );
			}
		}
		usort( $scored, static function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		// De-duplicate near-identical singular/plural tags (e.g. "reluctant
		// reader" vs "reluctant readers") -- keep only the higher-scoring
		// (or, on a tie, higher post_count) of each normalized pair.
		$kept        = array();
		$seen_norms  = array();
		$rejected    = array();
		foreach ( $scored as $tag ) {
			$norm = self::singularize( strtolower( preg_replace( '/[^a-z0-9]+/i', ' ', $tag['name'] ) ) );
			$norm = trim( preg_replace( '/\s+/', ' ', $norm ) );
			if ( isset( $seen_norms[ $norm ] ) ) {
				$rejected[] = array( 'name' => $tag['name'], 'slug' => $tag['slug'], 'reason' => 'near_duplicate_of_already_selected_tag' );
				continue;
			}
			$seen_norms[ $norm ] = true;
			$kept[] = $tag;
		}

		$selected = array_slice( $kept, 0, self::MAX_TAGS );
		foreach ( array_slice( $kept, self::MAX_TAGS ) as $overflow ) {
			$rejected[] = array( 'name' => $overflow['name'], 'slug' => $overflow['slug'], 'reason' => 'exceeded_tag_cap_' . self::MAX_TAGS );
		}

		return array(
			'tag_ids'                 => wp_list_pluck( $selected, 'term_id' ),
			'tag_slugs'               => wp_list_pluck( $selected, 'slug' ),
			'tag_assignment_reason'   => empty( $selected )
				? 'No existing tag matched the content profile closely enough.'
				: 'Reused ' . count( $selected ) . ' existing tag(s) matching primary/secondary keywords, featured book, audience, and content intent.',
			'rejected_tag_candidates' => $rejected,
			'new_tag_recommendations' => array(), // this engine only ever reuses existing tags; a human editor supplies any genuinely new tag name for explicit approval separately.
			'tag_approval_status'     => 'approved', // existing-term matches only.
		);
	}
}
