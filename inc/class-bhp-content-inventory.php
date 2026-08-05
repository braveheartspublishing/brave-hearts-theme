<?php
/**
 * Brave Hearts Publishing — Phase 1E: read-only content + SEO inventory.
 *
 * Builds a structured, machine-readable snapshot of every blog post,
 * resource page, and product/collection page already on the site by
 * reading (never writing) the systems Phase 1D already built:
 * BHP_Content_Classification, BHP_CTA_Engine's registry, the guide
 * registry (bhp_get_guide_registry()), and Rank Math's own postmeta.
 * This class never changes a slug, canonical, or metadata value -- it
 * only reads and reports gaps (see docs/phase1e-content-intelligence-architecture.md).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Inventory {

	/**
	 * Builds the full inventory. Capped at $limit posts to avoid an
	 * unbounded query on a site that grows content over time; the
	 * production queue and opportunity engine operate incrementally, not
	 * by re-scanning everything on every request.
	 */
	public static function build( $limit = 300 ) {
		$items = array();

		foreach ( get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $limit ),
		) ) as $post ) {
			$items[] = self::describe_post( $post );
		}

		return array(
			'generated_at' => current_time( 'mysql', true ),
			'content_count' => count( $items ),
			'items'         => $items,
			'gaps'          => self::detect_gaps( $items ),
		);
	}

	public static function describe_post( WP_Post $post ) {
		$classification = class_exists( 'BHP_Content_Classification' )
			? BHP_Content_Classification::get_classification( $post->ID )
			: array();

		$registry_entry = self::guide_registry_entry_for( $post );

		$rank_math_title       = get_post_meta( $post->ID, 'rank_math_title', true );
		$rank_math_description = get_post_meta( $post->ID, 'rank_math_description', true );
		$rank_math_focus_kw    = get_post_meta( $post->ID, 'rank_math_focus_keyword', true );
		$canonical             = get_post_meta( $post->ID, 'rank_math_canonical_url', true );

		$content       = (string) $post->post_content;
		$internal_links = self::extract_internal_links( $content );
		$external_links = self::extract_external_links( $content );

		$item = array(
			'content_id'          => $post->ID,
			'url'                 => get_permalink( $post ),
			'title'               => get_the_title( $post ),
			'page_type'           => 'page' === $post->post_type ? 'page' : 'blog_post',
			'audience'            => $classification['audience'] ?? '',
			'funnel_stage'        => $classification['funnel_stage'] ?? '',
			'content_intent'      => $classification['intent'] ?? '',
			'primary_topic'       => $registry_entry['primary'] ?? '',
			'primary_keyword'     => $rank_math_focus_kw ?: '',
			'secondary_keywords'  => array(),
			'featured_book'       => $classification['featured_book'] ?? ( $registry_entry['book'] ?? '' ),
			'primary_cta'         => $classification['primary_goal'] ?? '',
			'secondary_cta'       => $classification['secondary_goal'] ?? '',
			'lead_offer'          => $classification['lead_offer'] ?? '',
			'internal_link_targets' => $internal_links,
			'external_links'      => $external_links,
			'existing_pinterest_brief' => self::has_pinterest_brief( $post ),
			'analytics_identifiers' => array(
				'url_normalized' => class_exists( 'BHP_Analytics_Adapter' ) ? BHP_Analytics_Adapter::normalize_url( get_permalink( $post ) ) : get_permalink( $post ),
			),
			'last_modified_date'  => $post->post_modified_gmt,
			'seo' => array(
				'title'       => $rank_math_title ?: get_the_title( $post ),
				'description' => $rank_math_description ?: '',
				'canonical'   => $canonical ?: get_permalink( $post ),
				'schema_type' => 'page' === $post->post_type ? 'WebPage' : 'Article',
			),
			'seo_risk_flags'      => self::seo_risk_flags( $post, $rank_math_title, $rank_math_description, $rank_math_focus_kw ),
			'conversion_readiness_status' => self::classification_source_status( $classification ),
		);

		return $item;
	}

	private static function guide_registry_entry_for( WP_Post $post ) {
		if ( ! function_exists( 'bhp_get_guide_registry' ) ) {
			return null;
		}
		$registry = bhp_get_guide_registry();
		$slug     = $post->post_name;
		return isset( $registry[ $slug ] ) ? $registry[ $slug ] : null;
	}

	private static function classification_source_status( array $classification ) {
		$source = $classification['source'] ?? 'flat_default';
		if ( 'explicit' === $source ) {
			return 'classified';
		}
		if ( 'guide_registry_derived' === $source ) {
			return 'derived_from_registry';
		}
		return 'unclassified';
	}

	private static function extract_internal_links( $html ) {
		$home = home_url();
		if ( ! preg_match_all( '/href=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			return array();
		}
		$links = array();
		foreach ( $matches[1] as $href ) {
			if ( 0 === strpos( $href, '/' ) || 0 === strpos( $href, $home ) ) {
				$links[] = class_exists( 'BHP_Analytics_Adapter' ) ? BHP_Analytics_Adapter::normalize_url( $href ) : $href;
			}
		}
		return array_values( array_unique( $links ) );
	}

	private static function extract_external_links( $html ) {
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! preg_match_all( '/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches ) ) {
			return array();
		}
		$links = array();
		foreach ( $matches[1] as $href ) {
			$host = wp_parse_url( $href, PHP_URL_HOST );
			if ( $host && $host !== $home ) {
				$links[] = $href;
			}
		}
		return array_values( array_unique( $links ) );
	}

	private static function has_pinterest_brief( WP_Post $post ) {
		$path = get_template_directory() . '/content-engine/blogs/' . $post->post_name . '/design-brief.json';
		return file_exists( $path );
	}

	/**
	 * Read-only SEO risk detection. Never a rewrite -- only a flag for a
	 * human to review, per the "no bulk-edit" boundary.
	 */
	private static function seo_risk_flags( WP_Post $post, $title, $description, $focus_kw ) {
		$flags = array();
		if ( empty( $title ) ) {
			$flags[] = 'missing_seo_title';
		} elseif ( strlen( $title ) > 60 ) {
			$flags[] = 'seo_title_too_long';
		}
		if ( empty( $description ) ) {
			$flags[] = 'missing_meta_description';
		} elseif ( strlen( $description ) > 160 ) {
			$flags[] = 'meta_description_too_long';
		}
		if ( empty( $focus_kw ) ) {
			$flags[] = 'missing_focus_keyword';
		}
		return $flags;
	}

	/**
	 * Cross-item detection: orphans (no other item links to it) and
	 * overlapping-topic candidates (same non-empty primary_keyword).
	 * This is a coarse, honest signal, not a precise algorithm -- exposed
	 * as a "possible cannibalization" flag for human review, matching
	 * the "do not hide uncertainty behind false precision" principle.
	 */
	public static function detect_gaps( array $items ) {
		$linked_to  = array();
		foreach ( $items as $item ) {
			foreach ( $item['internal_link_targets'] as $target ) {
				$linked_to[ $target ] = true;
			}
		}

		$orphans        = array();
		$missing_meta   = array();
		$missing_class  = array();
		$missing_cta    = array();
		$keyword_groups = array();

		foreach ( $items as $item ) {
			$normalized_url = $item['analytics_identifiers']['url_normalized'];
			if ( empty( $linked_to[ $normalized_url ] ) ) {
				$orphans[] = $item['url'];
			}
			if ( ! empty( $item['seo_risk_flags'] ) ) {
				$missing_meta[] = $item['url'];
			}
			if ( 'unclassified' === $item['conversion_readiness_status'] ) {
				$missing_class[] = $item['url'];
			}
			if ( empty( $item['primary_cta'] ) ) {
				$missing_cta[] = $item['url'];
			}
			if ( ! empty( $item['primary_keyword'] ) ) {
				$keyword_groups[ strtolower( $item['primary_keyword'] ) ][] = $item['url'];
			}
		}

		$duplicate_topics = array();
		foreach ( $keyword_groups as $keyword => $urls ) {
			if ( count( $urls ) > 1 ) {
				$duplicate_topics[] = array( 'keyword' => $keyword, 'urls' => $urls );
			}
		}

		return array(
			'orphan_pages'                 => $orphans,
			'missing_metadata'             => $missing_meta,
			'missing_classification'       => $missing_class,
			'missing_cta_alignment'        => $missing_cta,
			'possible_keyword_cannibalization' => $duplicate_topics,
		);
	}

	/** Writes the inventory to a JSON file for external tooling/review. */
	public static function export_json( $limit = 300 ) {
		$inventory = self::build( $limit );
		$dir       = get_template_directory() . '/content-engine/reports';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$path = $dir . '/content-inventory.json';
		file_put_contents( $path, wp_json_encode( $inventory, JSON_PRETTY_PRINT ) ); // phpcs:ignore -- local build artifact, not user-facing output.
		return $path;
	}
}
