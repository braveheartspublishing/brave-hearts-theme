<?php
/**
 * Brave Hearts Publishing — Phase 1E: read-only taxonomy inventory.
 *
 * Builds a structured, machine-readable snapshot of the site's ACTUAL
 * WordPress categories and tags (term_id/slug/parent/count/description),
 * plus current usage on existing blog posts and whether Rank Math's
 * primary-category feature is enabled for the 'post' post type. This
 * class never creates, renames, merges, or deletes a term -- it only
 * reads and reports, the same read-only contract as
 * BHP_Content_Inventory. BHP_Taxonomy_Assignment_Engine (which DOES
 * choose terms for a new draft) is built entirely on top of this
 * inventory rather than guessing plausible-sounding category names.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Taxonomy_Inventory {

	const CACHE_KEY = 'bhp_taxonomy_inventory_snapshot';
	const CACHE_TTL = 900; // 15 minutes -- same convention as BHP_Content_Engine_Admin.

	/**
	 * @param bool $force_refresh Bypasses the cache -- used by the CLI
	 *                            export command so it always reflects the
	 *                            current live state.
	 */
	public static function build( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$categories = self::describe_terms( 'category' );
		$tags       = self::describe_terms( 'post_tag' );

		$snapshot = array(
			'generated_at'                  => current_time( 'mysql', true ),
			'categories'                    => $categories,
			'tags'                          => $tags,
			'category_count'                => count( $categories ),
			'tag_count'                     => count( $tags ),
			'primary_category_field_enabled' => self::primary_category_enabled(),
			'primary_category_meta_key'     => 'rank_math_primary_category',
			'existing_post_taxonomy_usage'  => self::existing_post_usage(),
		);

		set_transient( self::CACHE_KEY, $snapshot, self::CACHE_TTL );

		return $snapshot;
	}

	private static function describe_terms( $taxonomy ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$described = array();
		foreach ( $terms as $term ) {
			$described[] = array(
				'term_id'     => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'parent'      => (int) $term->parent,
				'description' => $term->description,
				'post_count'  => (int) $term->count,
			);
		}
		return $described;
	}

	/**
	 * Rank Math's primary-taxonomy feature is per-post-type and can be
	 * off even when the meta key would otherwise work -- reading the
	 * real live setting rather than assuming it is on. See
	 * seo-by-rank-math/includes/class-common.php::get_primary_term()
	 * and includes/admin/metabox/class-post-screen.php::get_primary_term_id(),
	 * both of which early-bail on this exact setting.
	 */
	public static function primary_category_enabled( $post_type = 'post' ) {
		if ( ! function_exists( 'rank_math' ) ) {
			return null; // Rank Math not active/loaded -- unknown, not false.
		}
		return (bool) rank_math()->settings->get( "titles.pt_{$post_type}_primary_taxonomy" );
	}

	/**
	 * Per-post category/tag usage for existing published blog posts --
	 * capped, matching BHP_Content_Inventory's own query ceiling.
	 */
	private static function existing_post_usage( $limit = 300 ) {
		$usage = array();
		foreach ( get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $limit ),
			'fields'         => 'ids',
		) ) as $post_id ) {
			$categories = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
			$tags       = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );
			$usage[]    = array(
				'post_id'          => $post_id,
				'category_ids'     => is_wp_error( $categories ) ? array() : $categories,
				'tag_ids'          => is_wp_error( $tags ) ? array() : $tags,
				'primary_category_id' => (int) get_post_meta( $post_id, 'rank_math_primary_category', true ),
			);
		}
		return $usage;
	}

	/** Looks up an existing term by exact slug (never creates one). */
	public static function find_by_slug( $taxonomy, $slug ) {
		$term = get_term_by( 'slug', sanitize_title( $slug ), $taxonomy );
		return $term instanceof WP_Term ? $term : null;
	}

	/** Case-insensitive lookup by name -- for matching a keyword phrase to an existing term. */
	public static function find_by_name( $taxonomy, $name ) {
		$term = get_term_by( 'name', $name, $taxonomy );
		return $term instanceof WP_Term ? $term : null;
	}

	public static function export_json( $force_refresh = true ) {
		$snapshot = self::build( $force_refresh );
		$dir = get_template_directory() . '/content-engine/taxonomy';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		file_put_contents( $dir . '/taxonomy-inventory.json', wp_json_encode( $snapshot, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local build artifact.
		return $dir . '/taxonomy-inventory.json';
	}
}
