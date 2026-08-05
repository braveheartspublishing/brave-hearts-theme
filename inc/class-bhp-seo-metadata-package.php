<?php
/**
 * Brave Hearts Publishing — Phase 1E: SEO metadata package generator + validator.
 *
 * Generates a complete, human-reviewable SEO metadata package for a
 * NEW draft. This class never writes to an existing published post's
 * Rank Math fields -- it only reads existing metadata (via
 * BHP_Content_Inventory) for collision/duplication checks, and only
 * ever writes metadata onto a brand-new staging draft created through
 * BHP_WP_Draft_Workflow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_SEO_Metadata_Package {

	const TITLE_MAX       = 60;
	const DESCRIPTION_MAX = 160;

	/**
	 * @param array $brief   Output of BHP_Content_Brief_Generator::generate().
	 * @param array $inventory_items Existing BHP_Content_Inventory items, for collision checks.
	 */
	public static function generate( array $brief, array $inventory_items = array() ) {
		$primary = $brief['primary_keyword'];
		$title_base = ucwords( $primary );

		$seo_title = self::truncate( $title_base . ' | Brave Hearts Publishing', self::TITLE_MAX );
		$meta_description = self::truncate(
			'A ' . $brief['target_audience'] . '-friendly guide to ' . $primary . ', from the makers of the Adventures of Charlotte and Henry series.',
			self::DESCRIPTION_MAX
		);
		$slug = sanitize_title( $brief['blog_slug'] );

		$package = array(
			'seo_title'          => $seo_title,
			'meta_description'   => $meta_description,
			'proposed_slug'      => $slug,
			'primary_keyword'    => $primary,
			'secondary_keyword_set' => $brief['secondary_keywords'],
			'h1'                 => $brief['working_title'],
			'outline'            => $brief['recommended_outline'],
			'excerpt'            => self::truncate( $meta_description, 155 ),
			// Category/tag assignment is owned entirely by
			// BHP_Taxonomy_Assignment_Engine (real existing WordPress
			// terms only) -- this class does not fabricate taxonomy names.
			'canonical_recommendation' => home_url( '/' . $slug . '/' ),
			'robots_recommendation'  => 'index, follow', // site default; drafts are never publicly indexed regardless.
			'breadcrumb_title'   => $brief['working_title'],
			'schema_type_recommendation' => 'article', // site-wide Rank Math default for post type 'post' -- verified via titles.pt_post_default_rich_snippet; no explicit rank_math_schema_* postmeta needed unless overriding this default.
			'faq_schema_recommendation' => ! empty( $brief['faq_opportunities'] )
				? 'FAQPage content present, but requires Rank Math\'s own FAQ block (rank-math/faq-block) in the body to actually emit FAQ schema -- not yet produced by the generator; documented limitation.'
				: 'none',
			'open_graph_title'   => $seo_title,
			'open_graph_description' => $meta_description,
			'open_graph_image_recommendation' => '[PLACEHOLDER: reuses featured image unless a dedicated OG crop is supplied -- see BHP_Image_Metadata_Package]',
			'twitter_title'      => $seo_title,
			'twitter_description' => $meta_description,
			'twitter_image_recommendation' => '[PLACEHOLDER: reuses featured image unless a dedicated Twitter crop is supplied -- see BHP_Image_Metadata_Package]',
			'social_image_guidance'  => $brief['featured_image_direction'] ?? '',
			'internal_link_recommendations' => $brief['internal_link_targets'] ?? array(),
			'external_source_recommendations' => $brief['authoritative_external_source_requirements'] ?? '',
			'pinterest_title'    => self::truncate( $title_base, 100 ),
			'pinterest_description' => self::truncate( $meta_description, 500 ),
			'pinterest_alt_text' => '[PLACEHOLDER: describe the pin image literally]',
		);

		$package['validation'] = self::validate( $package, $inventory_items );

		return $package;
	}

	/**
	 * Maps this package to the REAL Rank Math postmeta keys, verified
	 * directly against the seo-by-rank-math plugin source on this
	 * install (includes/admin/importers/class-yoast.php's field-mapping
	 * table) rather than assumed from general SEO-plugin conventions.
	 * Only fields with a real, non-placeholder value are included --
	 * callers must never write a literal "[PLACEHOLDER" string into a
	 * live postmeta value.
	 */
	public static function to_rank_math_postmeta( array $package, $primary_category_id = 0 ) {
		$map = array(
			'rank_math_title'               => $package['seo_title'] ?? '',
			'rank_math_description'         => $package['meta_description'] ?? '',
			'rank_math_focus_keyword'       => $package['primary_keyword'] ?? '',
			'rank_math_canonical_url'       => $package['canonical_recommendation'] ?? '',
			'rank_math_breadcrumb_title'    => $package['breadcrumb_title'] ?? '',
			'rank_math_facebook_title'      => $package['open_graph_title'] ?? '',
			'rank_math_facebook_description' => $package['open_graph_description'] ?? '',
			'rank_math_twitter_title'       => $package['twitter_title'] ?? '',
			'rank_math_twitter_description' => $package['twitter_description'] ?? '',
		);
		if ( $primary_category_id > 0 ) {
			// Only meaningful when Rank Math's primary-taxonomy feature is
			// enabled for 'post' (verified via BHP_Taxonomy_Inventory::primary_category_enabled())
			// -- writing it when disabled is harmless (Rank Math just won't read it) but never assumed to take effect.
			$map['rank_math_primary_category'] = (string) $primary_category_id;
		}
		return array_filter( $map, static function ( $value ) {
			return '' !== $value && false === strpos( (string) $value, '[PLACEHOLDER' );
		} );
	}

	private static function truncate( $text, $max ) {
		$text = trim( (string) $text );
		if ( strlen( $text ) <= $max ) {
			return $text;
		}
		return rtrim( substr( $text, 0, $max - 1 ) ) . '…';
	}

	/**
	 * Deterministic checks only. Never auto-corrects -- every finding is
	 * surfaced for the human editor to fix in the draft before it moves
	 * past the QA gate.
	 */
	public static function validate( array $package, array $inventory_items = array() ) {
		$findings = array();

		if ( strlen( $package['seo_title'] ) > self::TITLE_MAX ) {
			$findings[] = array( 'field' => 'seo_title', 'issue' => 'too_long', 'detail' => strlen( $package['seo_title'] ) . ' characters (max ' . self::TITLE_MAX . ')' );
		}
		if ( strlen( $package['meta_description'] ) > self::DESCRIPTION_MAX ) {
			$findings[] = array( 'field' => 'meta_description', 'issue' => 'too_long', 'detail' => strlen( $package['meta_description'] ) . ' characters (max ' . self::DESCRIPTION_MAX . ')' );
		}
		if ( empty( $package['primary_keyword'] ) ) {
			$findings[] = array( 'field' => 'primary_keyword', 'issue' => 'missing', 'detail' => 'No primary keyword set.' );
		}
		if ( trim( strtolower( $package['h1'] ) ) === trim( strtolower( $package['seo_title'] ) ) ) {
			$findings[] = array( 'field' => 'h1', 'issue' => 'duplicates_seo_title', 'detail' => 'H1 and SEO title are identical -- vary them per site convention.' );
		}
		// Image alt-text completeness is validated separately by
		// BHP_Image_Metadata_Package -- not duplicated here.

		foreach ( $inventory_items as $item ) {
			if ( isset( $item['seo']['title'] ) && strtolower( trim( $item['seo']['title'] ) ) === strtolower( trim( $package['seo_title'] ) ) ) {
				$findings[] = array( 'field' => 'seo_title', 'issue' => 'duplicate_title_risk', 'detail' => 'Matches existing title at ' . $item['url'] );
			}
			if ( isset( $item['seo']['description'] ) && $item['seo']['description'] && strtolower( trim( $item['seo']['description'] ) ) === strtolower( trim( $package['meta_description'] ) ) ) {
				$findings[] = array( 'field' => 'meta_description', 'issue' => 'duplicate_description_risk', 'detail' => 'Matches existing description at ' . $item['url'] );
			}
			if ( isset( $item['url'] ) && false !== strpos( $item['url'], '/' . $package['proposed_slug'] . '/' ) ) {
				$findings[] = array( 'field' => 'proposed_slug', 'issue' => 'slug_collision', 'detail' => 'Slug already used at ' . $item['url'] );
			}
			if ( ! empty( $item['primary_keyword'] ) && strtolower( $item['primary_keyword'] ) === strtolower( $package['primary_keyword'] ) ) {
				$findings[] = array( 'field' => 'primary_keyword', 'issue' => 'cannibalization_risk', 'detail' => 'Same primary keyword already targeted by ' . $item['url'] );
			}
		}

		foreach ( $package['internal_link_recommendations'] as $target ) {
			$target_url = is_array( $target ) ? ( $target['url'] ?? '' ) : $target;
			if ( $target_url && ! self::url_exists_in_inventory( $target_url, $inventory_items ) ) {
				$findings[] = array( 'field' => 'internal_link_recommendations', 'issue' => 'broken_internal_link_target', 'detail' => 'No existing content found at ' . $target_url );
			}
		}

		return array(
			'findings' => $findings,
			'state'    => empty( array_filter( $findings, static function ( $f ) {
				return in_array( $f['issue'], array( 'slug_collision', 'cannibalization_risk' ), true );
			} ) ) ? ( empty( $findings ) ? 'pass' : 'revise' ) : 'fail',
		);
	}

	private static function url_exists_in_inventory( $url, array $inventory_items ) {
		foreach ( $inventory_items as $item ) {
			if ( isset( $item['url'] ) && untrailingslashit( $item['url'] ) === untrailingslashit( $url ) ) {
				return true;
			}
		}
		return false;
	}
}
