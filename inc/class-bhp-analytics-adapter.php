<?php
/**
 * Brave Hearts Publishing — Phase 1E: provider-agnostic analytics ingestion.
 *
 * This is intentionally NOT a live API client. Andrew has not connected
 * Search Console, GA4 reporting API, or Pinterest API credentials, and
 * this system must not require them to be useful (see
 * docs/phase1e-content-intelligence-architecture.md, design principle 9:
 * "Avoid requiring a paid service for the core system"). Instead this
 * class defines one normalized row shape per source and accepts data via
 * CSV/JSON import (manual export from each provider's own UI) or via a
 * fixture array for testing. A future live adapter only needs to produce
 * the same normalized row shape and call import_rows() -- nothing
 * downstream (opportunity engine, feedback loop) needs to change.
 *
 * Storage: one private CPT post per import BATCH (`bhp_analytics_import`),
 * with the normalized rows serialized as JSON in a single postmeta field.
 * A batch-per-post (not row-per-post) avoids row-explosion for a
 * mid-size content site's GSC export, matching this codebase's existing
 * preference for post/postmeta storage over new database tables (see
 * BHP_Lead_Event_Log). Lookups always read the newest batch per source
 * covering a given URL, never blindly summing every historical import.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Analytics_Adapter {

	const POST_TYPE = 'bhp_analytics_import';

	const META_SOURCE      = '_bhp_ai_source';       // 'gsc' | 'ga4' | 'pinterest' | 'woocommerce'
	const META_ROWS        = '_bhp_ai_rows';         // JSON-encoded array of normalized rows
	const META_ROW_COUNT   = '_bhp_ai_row_count';
	const META_DATE_START  = '_bhp_ai_date_start';
	const META_DATE_END    = '_bhp_ai_date_end';
	const META_IMPORTED_AT = '_bhp_ai_imported_at';
	const META_IS_FIXTURE  = '_bhp_ai_is_fixture';    // '1' for test/fixture data, '' for real import
	const META_ERRORS      = '_bhp_ai_errors';        // JSON-encoded array of {row, reason}
	const META_LABEL       = '_bhp_ai_label';

	const SOURCE_GSC        = 'gsc';
	const SOURCE_GA4        = 'ga4';
	const SOURCE_PINTEREST  = 'pinterest';
	const SOURCE_WOOCOMMERCE = 'woocommerce';

	public static function sources() {
		return array( self::SOURCE_GSC, self::SOURCE_GA4, self::SOURCE_PINTEREST, self::SOURCE_WOOCOMMERCE );
	}

	/**
	 * Required + optional fields per source. Rows missing a required
	 * field are rejected (recorded in the batch's error list), never
	 * silently defaulted to zero -- a missing impression count must
	 * never be indistinguishable from a real zero.
	 */
	public static function field_schema( $source ) {
		$schemas = array(
			self::SOURCE_GSC => array(
				'required' => array( 'page', 'impressions', 'clicks', 'ctr', 'position', 'date' ),
				'optional' => array( 'query', 'country', 'device' ),
			),
			self::SOURCE_GA4 => array(
				'required' => array( 'landing_page', 'sessions', 'date' ),
				'optional' => array(
					'engaged_sessions', 'engagement_rate', 'avg_engagement_time',
					'cta_clicks', 'lead_form_starts', 'lead_signups', 'product_views',
					'add_to_carts', 'checkout_starts', 'purchases', 'revenue',
					'assisted_conversions',
				),
			),
			self::SOURCE_PINTEREST => array(
				'required' => array( 'pin', 'destination_url', 'date' ),
				'optional' => array( 'campaign', 'variant', 'impressions', 'saves', 'outbound_clicks', 'outbound_ctr' ),
			),
			self::SOURCE_WOOCOMMERCE => array(
				'required' => array( 'date' ),
				'optional' => array(
					'orders', 'revenue', 'contribution_value', 'coupon', 'product',
					'collection', 'source_attribution', 'provenance',
				),
			),
		);
		return isset( $schemas[ $source ] ) ? $schemas[ $source ] : null;
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Analytics Imports', 'brave-hearts' ),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title' ),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * URL normalization shared by every source, so a GSC row for
	 * "https://www.braveheartspublishing.com/blog/foo/" and a GA4 row for
	 * "/blog/foo" resolve to the same content item downstream.
	 */
	public static function normalize_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		// Accept bare paths (GA4 landing-page exports are often path-only).
		if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
			$url = home_url( '/' . ltrim( $url, '/' ) );
		}
		$parts = wp_parse_url( $url );
		if ( ! $parts || empty( $parts['path'] ) ) {
			return untrailingslashit( $url );
		}
		$path = untrailingslashit( $parts['path'] );
		return '' === $path ? '/' : $path;
	}

	/**
	 * Parses a CSV string (first row = header) into associative rows.
	 * Header names are lower-cased and non-alphanumeric characters
	 * collapsed to underscores, so "Average Position" and
	 * "average_position" both map to `average_position`.
	 */
	public static function parse_csv( $csv_string ) {
		$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $csv_string ) );
		$lines = array_values( array_filter( $lines, static function ( $line ) {
			return '' !== trim( $line );
		} ) );
		if ( count( $lines ) < 2 ) {
			return array();
		}
		$header = str_getcsv( array_shift( $lines ) );
		$header = array_map( array( __CLASS__, 'normalize_field_name' ), $header );

		$rows = array();
		foreach ( $lines as $line ) {
			$values = str_getcsv( $line );
			$row    = array();
			foreach ( $header as $index => $key ) {
				if ( '' === $key ) {
					continue;
				}
				$row[ $key ] = isset( $values[ $index ] ) ? $values[ $index ] : '';
			}
			$rows[] = $row;
		}
		return $rows;
	}

	public static function normalize_field_name( $name ) {
		$name = strtolower( trim( (string) $name ) );
		$name = preg_replace( '/[^a-z0-9]+/', '_', $name );
		return trim( $name, '_' );
	}

	/**
	 * Validates and normalizes a raw row against a source's schema.
	 * Returns array( 'row' => normalized_row_or_null, 'error' => string_or_null ).
	 * Never fabricates a missing metric as zero -- a required field that
	 * is missing/blank is a hard rejection, not a silent default.
	 */
	public static function validate_row( $source, array $raw_row ) {
		$schema = self::field_schema( $source );
		if ( ! $schema ) {
			return array( 'row' => null, 'error' => 'Unknown source: ' . $source );
		}

		$normalized = array();
		foreach ( $schema['required'] as $field ) {
			if ( ! isset( $raw_row[ $field ] ) || '' === trim( (string) $raw_row[ $field ] ) ) {
				return array( 'row' => null, 'error' => "Missing required field '{$field}'" );
			}
			$normalized[ $field ] = self::coerce_value( $field, $raw_row[ $field ] );
		}
		foreach ( $schema['optional'] as $field ) {
			if ( isset( $raw_row[ $field ] ) && '' !== trim( (string) $raw_row[ $field ] ) ) {
				$normalized[ $field ] = self::coerce_value( $field, $raw_row[ $field ] );
			}
		}

		// URL-bearing fields get normalized so downstream lookups are stable.
		foreach ( array( 'page', 'landing_page', 'destination_url' ) as $url_field ) {
			if ( isset( $normalized[ $url_field ] ) ) {
				$normalized[ $url_field ] = self::normalize_url( $normalized[ $url_field ] );
				if ( '' === $normalized[ $url_field ] ) {
					return array( 'row' => null, 'error' => "Malformed URL in field '{$url_field}'" );
				}
			}
		}

		$date = self::normalize_date( $normalized['date'] );
		if ( ! $date ) {
			return array( 'row' => null, 'error' => "Malformed date '{$raw_row['date']}'" );
		}
		$normalized['date'] = $date;

		return array( 'row' => $normalized, 'error' => null );
	}

	private static function coerce_value( $field, $value ) {
		$numeric_fields = array(
			'impressions', 'clicks', 'ctr', 'position', 'sessions', 'engaged_sessions',
			'engagement_rate', 'avg_engagement_time', 'cta_clicks', 'lead_form_starts',
			'lead_signups', 'product_views', 'add_to_carts', 'checkout_starts',
			'purchases', 'revenue', 'assisted_conversions', 'saves', 'outbound_clicks',
			'outbound_ctr', 'orders', 'contribution_value',
		);
		if ( in_array( $field, $numeric_fields, true ) ) {
			$clean = preg_replace( '/[^0-9.\-]/', '', (string) $value );
			return '' === $clean ? 0.0 : (float) $clean;
		}
		return sanitize_text_field( (string) $value );
	}

	private static function normalize_date( $value ) {
		$value = trim( (string) $value );
		$ts    = strtotime( $value );
		if ( ! $ts ) {
			return null;
		}
		return gmdate( 'Y-m-d', $ts );
	}

	/**
	 * Imports an array of raw associative rows for a source. Deduplicates
	 * exact (source, url_field, date) matches within the same batch, and
	 * returns a report -- never throws, so a partially malformed export
	 * still imports every valid row and clearly reports the rest.
	 *
	 * @param string $source
	 * @param array  $raw_rows
	 * @param array  $args     'label' (string), 'is_fixture' (bool)
	 * @return array {
	 *     @type int    $import_id
	 *     @type int    $imported
	 *     @type int    $rejected
	 *     @type array  $errors
	 * }
	 */
	public static function import_rows( $source, array $raw_rows, array $args = array() ) {
		if ( ! in_array( $source, self::sources(), true ) ) {
			return array( 'import_id' => 0, 'imported' => 0, 'rejected' => count( $raw_rows ), 'errors' => array( 'Unknown source: ' . $source ) );
		}

		$normalized_rows = array();
		$errors          = array();
		$seen_keys       = array();
		$url_field       = self::url_field_for_source( $source );

		foreach ( $raw_rows as $index => $raw_row ) {
			$result = self::validate_row( $source, is_array( $raw_row ) ? $raw_row : array() );
			if ( null === $result['row'] ) {
				$errors[] = array( 'row' => $index + 1, 'reason' => $result['error'] );
				continue;
			}
			$row = $result['row'];
			$dedup_key = ( $url_field && isset( $row[ $url_field ] ) ? $row[ $url_field ] : '' ) . '|' . $row['date'] . '|' . ( isset( $row['query'] ) ? $row['query'] : '' ) . '|' . ( isset( $row['variant'] ) ? $row['variant'] : '' );
			if ( isset( $seen_keys[ $dedup_key ] ) ) {
				$errors[] = array( 'row' => $index + 1, 'reason' => 'Duplicate of an earlier row in this import' );
				continue;
			}
			$seen_keys[ $dedup_key ] = true;
			$normalized_rows[]       = $row;
		}

		if ( empty( $normalized_rows ) ) {
			return array( 'import_id' => 0, 'imported' => 0, 'rejected' => count( $errors ), 'errors' => $errors );
		}

		$dates       = wp_list_pluck( $normalized_rows, 'date' );
		$date_start  = min( $dates );
		$date_end    = max( $dates );
		$label       = isset( $args['label'] ) ? sanitize_text_field( $args['label'] ) : ( strtoupper( $source ) . ' import' );
		$is_fixture  = ! empty( $args['is_fixture'] );

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s (%s to %s)%s', $label, $date_start, $date_end, $is_fixture ? ' [FIXTURE]' : '' ),
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return array( 'import_id' => 0, 'imported' => 0, 'rejected' => count( $raw_rows ), 'errors' => array( 'Failed to create import record.' ) );
		}

		update_post_meta( $post_id, self::META_SOURCE, $source );
		update_post_meta( $post_id, self::META_ROWS, wp_json_encode( $normalized_rows ) );
		update_post_meta( $post_id, self::META_ROW_COUNT, count( $normalized_rows ) );
		update_post_meta( $post_id, self::META_DATE_START, $date_start );
		update_post_meta( $post_id, self::META_DATE_END, $date_end );
		update_post_meta( $post_id, self::META_IMPORTED_AT, current_time( 'mysql', true ) );
		update_post_meta( $post_id, self::META_IS_FIXTURE, $is_fixture ? '1' : '' );
		update_post_meta( $post_id, self::META_ERRORS, wp_json_encode( $errors ) );
		update_post_meta( $post_id, self::META_LABEL, $label );

		return array(
			'import_id' => $post_id,
			'imported'  => count( $normalized_rows ),
			'rejected'  => count( $errors ),
			'errors'    => $errors,
		);
	}

	public static function import_json( $source, $json_string, array $args = array() ) {
		$decoded = json_decode( (string) $json_string, true );
		if ( ! is_array( $decoded ) ) {
			return array( 'import_id' => 0, 'imported' => 0, 'rejected' => 0, 'errors' => array( 'Invalid JSON: expected an array of row objects.' ) );
		}
		return self::import_rows( $source, $decoded, $args );
	}

	public static function import_csv( $source, $csv_string, array $args = array() ) {
		return self::import_rows( $source, self::parse_csv( $csv_string ), $args );
	}

	private static function url_field_for_source( $source ) {
		$map = array(
			self::SOURCE_GSC       => 'page',
			self::SOURCE_GA4       => 'landing_page',
			self::SOURCE_PINTEREST => 'destination_url',
			self::SOURCE_WOOCOMMERCE => null,
		);
		return isset( $map[ $source ] ) ? $map[ $source ] : null;
	}

	/**
	 * Returns every row (across every import batch) for a given source
	 * within an optional date range, capped at $limit batches read (not
	 * rows) to bound worst-case query cost -- callers needing a specific
	 * URL should use get_rows_for_url() instead, which is far cheaper.
	 */
	public static function get_rows( $source, $date_start = null, $date_end = null, $limit = 50 ) {
		$posts = get_posts( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => max( 1, (int) $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array( 'key' => self::META_SOURCE, 'value' => $source ),
			),
		) );

		$all_rows = array();
		foreach ( $posts as $post ) {
			$rows = json_decode( get_post_meta( $post->ID, self::META_ROWS, true ), true );
			if ( ! is_array( $rows ) ) {
				continue;
			}
			foreach ( $rows as $row ) {
				if ( $date_start && $row['date'] < $date_start ) {
					continue;
				}
				if ( $date_end && $row['date'] > $date_end ) {
					continue;
				}
				$row['_import_id']  = $post->ID;
				$row['_is_fixture'] = (bool) get_post_meta( $post->ID, self::META_IS_FIXTURE, true );
				$all_rows[]         = $row;
			}
		}
		return $all_rows;
	}

	/**
	 * Cheapest lookup: every row across every source that matches a
	 * specific, already-normalized URL. This is the primary entry point
	 * for the opportunity engine and feedback loop, both of which
	 * operate one content item at a time.
	 */
	public static function get_rows_for_url( $url, $sources = null ) {
		$url     = self::normalize_url( $url );
		$sources = $sources ? (array) $sources : self::sources();
		$matches = array();

		foreach ( $sources as $source ) {
			$field = self::url_field_for_source( $source );
			if ( ! $field ) {
				continue;
			}
			foreach ( self::get_rows( $source, null, null, 50 ) as $row ) {
				if ( isset( $row[ $field ] ) && $row[ $field ] === $url ) {
					$row['_source'] = $source;
					$matches[]      = $row;
				}
			}
		}
		return $matches;
	}

	/** Lists recent import batches for the admin screen / WP-CLI. */
	public static function list_imports( $limit = 50 ) {
		$posts = get_posts( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => max( 1, (int) $limit ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		return array_map( static function ( $post ) {
			return array(
				'id'          => $post->ID,
				'label'       => get_post_meta( $post->ID, self::META_LABEL, true ),
				'source'      => get_post_meta( $post->ID, self::META_SOURCE, true ),
				'row_count'   => (int) get_post_meta( $post->ID, self::META_ROW_COUNT, true ),
				'date_start'  => get_post_meta( $post->ID, self::META_DATE_START, true ),
				'date_end'    => get_post_meta( $post->ID, self::META_DATE_END, true ),
				'imported_at' => get_post_meta( $post->ID, self::META_IMPORTED_AT, true ),
				'is_fixture'  => (bool) get_post_meta( $post->ID, self::META_IS_FIXTURE, true ),
				'error_count' => count( (array) json_decode( get_post_meta( $post->ID, self::META_ERRORS, true ), true ) ),
			);
		}, $posts );
	}

	/**
	 * Safe fixtures for testing and for demonstrating the pipeline before
	 * live credentials exist. Every fixture row is flagged is_fixture=1
	 * on import and every downstream report must surface that flag --
	 * fixture data must never be presented as if it were live.
	 */
	public static function fixture_rows( $source ) {
		$fixtures = array(
			self::SOURCE_GSC => array(
				array( 'page' => '/blog/mariana-trench-facts-for-kids/', 'query' => 'mariana trench facts for kids', 'impressions' => 4200, 'clicks' => 84, 'ctr' => 0.02, 'position' => 8.4, 'date' => '2026-06-01' ),
				array( 'page' => '/blog/mariana-trench-facts-for-kids/', 'query' => 'how deep is the mariana trench', 'impressions' => 3100, 'clicks' => 39, 'ctr' => 0.0126, 'position' => 11.2, 'date' => '2026-06-01' ),
				array( 'page' => '/blog/reluctant-reader-chapter-books/', 'query' => 'chapter books for reluctant readers', 'impressions' => 2600, 'clicks' => 130, 'ctr' => 0.05, 'position' => 5.1, 'date' => '2026-06-01' ),
				array( 'page' => '/blog/lexile-levels-explained/', 'query' => 'what is a lexile level', 'impressions' => 5400, 'clicks' => 54, 'ctr' => 0.01, 'position' => 14.8, 'date' => '2026-06-01' ),
			),
			self::SOURCE_GA4 => array(
				array( 'landing_page' => '/blog/mariana-trench-facts-for-kids/', 'sessions' => 310, 'engaged_sessions' => 190, 'engagement_rate' => 0.61, 'cta_clicks' => 9, 'lead_form_starts' => 4, 'lead_signups' => 2, 'product_views' => 22, 'date' => '2026-06-01' ),
				array( 'landing_page' => '/blog/reluctant-reader-chapter-books/', 'sessions' => 480, 'engaged_sessions' => 350, 'engagement_rate' => 0.73, 'cta_clicks' => 41, 'lead_form_starts' => 22, 'lead_signups' => 14, 'product_views' => 60, 'date' => '2026-06-01' ),
			),
			self::SOURCE_PINTEREST => array(
				array( 'pin' => 'pin_demo_1', 'destination_url' => '/blog/reluctant-reader-chapter-books/', 'campaign' => 'reluctant_reader_transition', 'variant' => 'problem-led', 'impressions' => 8200, 'saves' => 140, 'outbound_clicks' => 96, 'outbound_ctr' => 0.0117, 'date' => '2026-06-01' ),
			),
			self::SOURCE_WOOCOMMERCE => array(
				array( 'date' => '2026-06-01', 'orders' => 3, 'revenue' => 47.97, 'product' => 'mariana-trench-paperback', 'source_attribution' => 'organic', 'provenance' => 'real' ),
			),
		);
		return isset( $fixtures[ $source ] ) ? $fixtures[ $source ] : array();
	}
}

BHP_Analytics_Adapter::init();
