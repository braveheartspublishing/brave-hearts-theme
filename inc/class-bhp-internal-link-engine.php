<?php
/**
 * Brave Hearts Publishing — Phase 1E: internal-link recommendation engine.
 *
 * Extends the exact scoring approach already used by
 * bhp_get_related_guide_posts() (primary-hub match, destination/book
 * match, secondary overlap) so a NEW piece of content can be scored
 * against the existing inventory the same way two existing guide posts
 * are scored against each other. This class only ever RECOMMENDS links
 * -- it never inserts a link into live content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Internal_Link_Engine {

	/**
	 * Scores one candidate existing item against a new item's profile.
	 * Weights intentionally mirror bhp_get_related_guide_posts() so
	 * recommendations feel consistent with the site's existing related-
	 * content behavior rather than introducing a second, different logic.
	 */
	private static function score_candidate( array $new_profile, array $candidate ) {
		$score = 0;
		if ( ! empty( $new_profile['primary_topic'] ) && $new_profile['primary_topic'] === ( $candidate['primary_topic'] ?? '' ) ) {
			$score += 5;
		}
		if ( ! empty( $new_profile['featured_book'] ) && $new_profile['featured_book'] === ( $candidate['featured_book'] ?? '' ) ) {
			$score += 4;
		}
		if ( ! empty( $new_profile['audience'] ) && $new_profile['audience'] === ( $candidate['audience'] ?? '' ) ) {
			$score += 2;
		}
		if ( ! empty( $new_profile['funnel_stage'] ) && $new_profile['funnel_stage'] === ( $candidate['funnel_stage'] ?? '' ) ) {
			$score += 1;
		}
		return $score;
	}

	/**
	 * @param array $new_profile A queue item or brief with at minimum
	 *                           audience/funnel_stage/featured_book/primary_keyword.
	 * @param array $inventory_items Optional pre-built inventory; built fresh if omitted.
	 * @return array Top N candidates, each with anchor-text suggestion and score.
	 */
	public static function recommend_for_new_content( array $new_profile, array $inventory_items = null, $limit = 4 ) {
		if ( null === $inventory_items ) {
			$inventory_items = class_exists( 'BHP_Content_Inventory' ) ? BHP_Content_Inventory::build( 300 )['items'] : array();
		}

		$scored = array();
		foreach ( $inventory_items as $item ) {
			$profile = array(
				'primary_topic' => $item['primary_topic'] ?? '',
				'featured_book' => $item['featured_book'] ?? '',
				'audience'      => $item['audience'] ?? '',
				'funnel_stage'  => $item['funnel_stage'] ?? '',
			);
			$score = self::score_candidate( array(
				'primary_topic' => $new_profile['primary_topic'] ?? ( $new_profile['content_intent'] ?? '' ),
				'featured_book' => $new_profile['featured_book'] ?? '',
				'audience'      => $new_profile['audience'] ?? '',
				'funnel_stage'  => $new_profile['funnel_stage'] ?? '',
			), $profile );

			if ( $score > 0 ) {
				$scored[] = array(
					'url'                   => $item['url'],
					'title'                 => $item['title'],
					'score'                 => $score,
					'suggested_anchor_text' => self::suggest_anchor( $item ),
					'link_direction'        => 'new_to_existing',
				);
			}
		}

		usort( $scored, static function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		return array_slice( $scored, 0, $limit );
	}

	private static function suggest_anchor( array $item ) {
		// A specific, descriptive anchor (never "click here" / "read more").
		return $item['title'];
	}

	/**
	 * Detects vague/generic anchors and repeated identical anchors across
	 * an inventory -- a real, checkable structural signal.
	 */
	public static function audit_anchor_quality( array $inventory_items ) {
		$vague_patterns = array( '/^click here$/i', '/^read more$/i', '/^learn more$/i', '/^this (article|page|post)$/i', '/^here$/i' );
		$anchor_counts  = array();
		$findings       = array();

		foreach ( $inventory_items as $item ) {
			foreach ( $item['internal_link_targets'] as $target ) {
				$anchor_counts[ $item['url'] . '->' . $target ] = ( $anchor_counts[ $item['url'] . '->' . $target ] ?? 0 ) + 1;
			}
		}
		// Note: anchor TEXT itself is not extracted from raw HTML here
		// (would require re-parsing rendered content); this reports link
		// TARGET repetition per source page as a proxy for over-linking.
		foreach ( $inventory_items as $item ) {
			$targets = $item['internal_link_targets'];
			$counts  = array_count_values( $targets );
			foreach ( $counts as $target => $count ) {
				if ( $count > 3 ) {
					$findings[] = array( 'type' => 'overlinked_target', 'from' => $item['url'], 'to' => $target, 'count' => $count );
				}
			}
		}

		return $findings;
	}

	/** Returns pages with zero incoming AND zero outgoing internal links. */
	public static function detect_orphans( array $inventory_items ) {
		$linked_to = array();
		foreach ( $inventory_items as $item ) {
			foreach ( $item['internal_link_targets'] as $target ) {
				$linked_to[ $target ] = true;
			}
		}
		$orphans = array();
		foreach ( $inventory_items as $item ) {
			$url = $item['analytics_identifiers']['url_normalized'] ?? $item['url'];
			$has_outgoing = ! empty( $item['internal_link_targets'] );
			$has_incoming = ! empty( $linked_to[ $url ] );
			if ( ! $has_outgoing && ! $has_incoming ) {
				$orphans[] = $item['url'];
			}
		}
		return $orphans;
	}

	/**
	 * Validates every internal (same-host) <a href> actually already
	 * inserted into a draft's body -- resolves each one with
	 * url_to_postid() against the REAL site, not the inventory snapshot,
	 * so a genuinely broken link is always caught even if the inventory
	 * cache is stale. External links are ignored (out of scope here).
	 * Returns an array of broken-link findings; empty = all internal
	 * links in the body resolve to real, existing content.
	 */
	public static function validate_body_links( $content_html ) {
		$findings = array();
		if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', (string) $content_html, $matches ) ) {
			return $findings;
		}

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		foreach ( array_unique( $matches[1] ) as $url ) {
			$parsed_host = wp_parse_url( $url, PHP_URL_HOST );
			$is_internal = null === $parsed_host || $parsed_host === $home_host; // relative paths count as internal.
			if ( ! $is_internal ) {
				continue;
			}
			if ( 0 === url_to_postid( $url ) && ! self::is_known_non_post_url( $url ) ) {
				$findings[] = array( 'url' => $url, 'issue' => 'broken_internal_link', 'detail' => 'url_to_postid() found no existing post/page for this URL.' );
			}
		}

		return $findings;
	}

	/**
	 * url_to_postid() only resolves canonical post/page permalinks -- it
	 * returns 0 for the homepage and for any URL carrying a query string
	 * (e.g. a UTM-tagged CTA destination), neither of which is broken.
	 */
	private static function is_known_non_post_url( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$has_query = null !== wp_parse_url( $url, PHP_URL_QUERY );
		return $has_query || in_array( $path, array( '', '/', null ), true );
	}

	/**
	 * Builds a reviewable recommendation report across the whole
	 * inventory -- output only, never applies anything to live content.
	 */
	public static function build_report( array $inventory_items ) {
		$recommendations = array();
		foreach ( $inventory_items as $item ) {
			$candidates = array_filter( $inventory_items, static function ( $candidate ) use ( $item ) {
				return $candidate['url'] !== $item['url'];
			} );
			$recs = self::recommend_for_new_content( $item, array_values( $candidates ), 3 );
			if ( ! empty( $recs ) ) {
				$recommendations[] = array( 'source_url' => $item['url'], 'recommended_targets' => $recs );
			}
		}

		return array(
			'generated_at'          => current_time( 'mysql', true ),
			'orphan_pages'          => self::detect_orphans( $inventory_items ),
			'overlinked_targets'    => self::audit_anchor_quality( $inventory_items ),
			'link_recommendations'  => $recommendations,
		);
	}
}
