<?php
/**
 * Brave Hearts Publishing — Phase 1D: content funnel classification.
 *
 * A deliberately SEPARATE taxonomy from bhp_get_audience_types()
 * (functions.php) — that 3-value enum (parents_families/teachers/
 * general_readers) is the live Mailchimp segmentation for lead
 * signups and is NOT touched here. Content targeting needs a richer,
 * more nuanced audience model (librarians and homeschool families are
 * real, distinct content audiences even though they currently share a
 * Mailchimp segment) — this class exists for that, and only for that.
 * A CTA that eventually leads to a real signup form still uses the
 * existing, unchanged audience_type when it gets there.
 *
 * Storage: plain post meta on whichever post types opt in (post, page
 * by default) -- no new taxonomy, no new table. Matches the registry +
 * normalize_*() pattern already established by bhp_get_audience_types()/
 * bhp_normalize_audience_type() and BHP_Order_Provenance's ORIGIN_*
 * constants: a fixed, filterable enum, a normalize function that never
 * returns an invalid/unknown value, and a safe default for anything
 * never explicitly classified.
 *
 * "Uncategorized content must fail gracefully" (Phase 1D requirement):
 * get_classification() NEVER returns null or throws -- every field has
 * a safe, documented default so CTA selection and analytics can always
 * proceed even for a post nobody has classified yet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Classification {

	const META_AUDIENCE       = '_bhp_content_audience';
	const META_FUNNEL_STAGE   = '_bhp_content_funnel_stage';
	const META_INTENT         = '_bhp_content_intent';
	const META_PRIMARY_GOAL   = '_bhp_content_primary_goal';
	const META_SECONDARY_GOAL = '_bhp_content_secondary_goal';
	const META_LEAD_OFFER     = '_bhp_content_lead_offer';
	const META_FEATURED_BOOK  = '_bhp_content_featured_book';

	const NONCE_ACTION = 'bhp_content_classification_save';
	const NONCE_FIELD  = 'bhp_content_classification_nonce';

	// Safe defaults -- deliberately the least assumptive option in each
	// dimension, never a value that would make an uncategorized post look
	// more "ready" than it actually is.
	const DEFAULT_AUDIENCE       = 'mixed';
	const DEFAULT_FUNNEL_STAGE   = 'awareness';
	const DEFAULT_INTENT         = 'educational';
	const DEFAULT_PRIMARY_GOAL   = 'related_content_engagement';
	const DEFAULT_SECONDARY_GOAL = 'another_resource';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		foreach ( self::eligible_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( __CLASS__, 'add_admin_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( __CLASS__, 'render_admin_column' ), 10, 2 );
		}
	}

	/**
	 * Post types this classification system applies to. Filterable so a
	 * future post type (e.g. a dedicated "resource" CPT) can opt in
	 * without editing this file.
	 */
	public static function eligible_post_types() {
		return apply_filters( 'bhp_content_classification_post_types', array( 'post', 'page' ) );
	}

	public static function audiences() {
		return apply_filters(
			'bhp_content_audiences',
			array(
				'parent'         => __( 'Parent', 'brave-hearts' ),
				'teacher'        => __( 'Teacher', 'brave-hearts' ),
				'librarian'      => __( 'Librarian', 'brave-hearts' ),
				'homeschool'     => __( 'Homeschool Family', 'brave-hearts' ),
				'grandparent_gift' => __( 'Grandparent / Gift Buyer', 'brave-hearts' ),
				'organization'   => __( 'Nonprofit / Community Organization', 'brave-hearts' ),
				'mixed'          => __( 'Mixed / General', 'brave-hearts' ),
			)
		);
	}

	public static function funnel_stages() {
		return apply_filters(
			'bhp_content_funnel_stages',
			array(
				'awareness'             => __( 'Awareness', 'brave-hearts' ),
				'problem_recognition'   => __( 'Problem Recognition', 'brave-hearts' ),
				'consideration'         => __( 'Consideration', 'brave-hearts' ),
				'product_discovery'     => __( 'Product Discovery', 'brave-hearts' ),
				'conversion'            => __( 'Conversion', 'brave-hearts' ),
				'retention_engagement'  => __( 'Retention / Engagement', 'brave-hearts' ),
			)
		);
	}

	public static function content_intents() {
		return apply_filters(
			'bhp_content_intents',
			array(
				'educational'          => __( 'Educational', 'brave-hearts' ),
				'reading_development'  => __( 'Reading Development', 'brave-hearts' ),
				'adventure_geography'  => __( 'Adventure / Geography', 'brave-hearts' ),
				'teacher_resource'     => __( 'Teacher Resource', 'brave-hearts' ),
				'book_recommendation'  => __( 'Book Recommendation', 'brave-hearts' ),
				'product_discovery'    => __( 'Product / Series Discovery', 'brave-hearts' ),
				'activity_resource'    => __( 'Activity / Resource', 'brave-hearts' ),
				'trust_authority'      => __( 'Trust / Authority', 'brave-hearts' ),
				'gift_occasion'        => __( 'Gift / Special Occasion', 'brave-hearts' ),
				'literacy_program'     => __( 'Reading Program / Community Literacy', 'brave-hearts' ),
				'homeschool_curriculum' => __( 'Homeschool Curriculum', 'brave-hearts' ),
			)
		);
	}

	public static function primary_goals() {
		return apply_filters(
			'bhp_content_primary_goals',
			array(
				'adventure_kit_signup'       => __( 'Adventure Kit Signup', 'brave-hearts' ),
				'educator_toolkit_signup'    => __( 'Educator Toolkit Signup', 'brave-hearts' ),
				'gift_guide_signup'          => __( 'Gift Guide Signup', 'brave-hearts' ),
				'community_reading_kit_signup' => __( 'Community Reading Kit Signup', 'brave-hearts' ),
				'visit_book_page'            => __( 'Visit a Book Page', 'brave-hearts' ),
				'visit_collection_page'      => __( 'Visit a Collection Page', 'brave-hearts' ),
				'direct_purchase'            => __( 'Direct Purchase', 'brave-hearts' ),
				'amazon_outbound_click'      => __( 'Amazon Outbound Click', 'brave-hearts' ),
				'related_content_engagement' => __( 'Related Content Engagement', 'brave-hearts' ),
			)
		);
	}

	public static function secondary_goals() {
		return apply_filters(
			'bhp_content_secondary_goals',
			array(
				'audience_identification' => __( 'Audience Identification', 'brave-hearts' ),
				'another_resource'        => __( 'Another Resource', 'brave-hearts' ),
				'another_book'            => __( 'Another Book', 'brave-hearts' ),
				'email_signup'            => __( 'Email Signup', 'brave-hearts' ),
				'bundle_discovery'        => __( 'Bundle Discovery', 'brave-hearts' ),
			)
		);
	}

	/** Normalize helpers -- never return an unknown/invalid value. */
	public static function normalize_audience( $value ) {
		return isset( self::audiences()[ $value ] ) ? $value : self::DEFAULT_AUDIENCE;
	}
	public static function normalize_funnel_stage( $value ) {
		return isset( self::funnel_stages()[ $value ] ) ? $value : self::DEFAULT_FUNNEL_STAGE;
	}
	public static function normalize_intent( $value ) {
		return isset( self::content_intents()[ $value ] ) ? $value : self::DEFAULT_INTENT;
	}
	public static function normalize_primary_goal( $value ) {
		return isset( self::primary_goals()[ $value ] ) ? $value : self::DEFAULT_PRIMARY_GOAL;
	}
	public static function normalize_secondary_goal( $value ) {
		return isset( self::secondary_goals()[ $value ] ) ? $value : self::DEFAULT_SECONDARY_GOAL;
	}

	public static function maybe_register_meta() {
		foreach ( self::eligible_post_types() as $post_type ) {
			foreach ( self::meta_keys() as $meta_key ) {
				register_post_meta(
					$post_type,
					$meta_key,
					array(
						'show_in_rest'  => false, // internal editorial metadata, not a public API surface
						'single'        => true,
						'type'          => 'string',
						'auth_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	private static function meta_keys() {
		return array(
			self::META_AUDIENCE,
			self::META_FUNNEL_STAGE,
			self::META_INTENT,
			self::META_PRIMARY_GOAL,
			self::META_SECONDARY_GOAL,
			self::META_LEAD_OFFER,
			self::META_FEATURED_BOOK,
		);
	}

	/**
	 * Whether a post has ANY classification explicitly set -- used by
	 * admin coverage reporting (Workstream 10) to distinguish "never
	 * classified" from "classified with defaults." A post can legitimately
	 * have every field equal to its default value on purpose; this flag
	 * is about whether anyone ever saved the meta box at all.
	 */
	public static function is_classified( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META_AUDIENCE, true )
			|| (bool) get_post_meta( $post_id, self::META_FUNNEL_STAGE, true );
	}

	/**
	 * The single entry point every consumer (CTA engine, analytics,
	 * admin reporting) should use. Always returns a complete, valid
	 * array -- never null, never a fatal error, regardless of whether
	 * the post has ever been classified.
	 *
	 * Fallback order: (1) explicit meta-box classification if ever
	 * saved, (2) a deterministic derivation from the EXISTING
	 * bhp_get_guide_registry() (functions.php) for the ~30 posts it
	 * already curates -- that registry and bhp_get_related_guide_posts()
	 * are untouched by this class; this only reads from it to give
	 * already-curated posts a smarter default than the flat fallback,
	 * (3) the documented flat defaults for anything neither classified
	 * nor in the registry (new/future posts).
	 *
	 * @return array{audience:string,funnel_stage:string,intent:string,primary_goal:string,secondary_goal:string,lead_offer:string,featured_book:string,is_classified:bool,source:string}
	 */
	public static function get_classification( $post_id ) {
		$post_id = absint( $post_id );

		if ( self::is_classified( $post_id ) ) {
			return array(
				'audience'       => self::normalize_audience( get_post_meta( $post_id, self::META_AUDIENCE, true ) ),
				'funnel_stage'   => self::normalize_funnel_stage( get_post_meta( $post_id, self::META_FUNNEL_STAGE, true ) ),
				'intent'         => self::normalize_intent( get_post_meta( $post_id, self::META_INTENT, true ) ),
				'primary_goal'   => self::normalize_primary_goal( get_post_meta( $post_id, self::META_PRIMARY_GOAL, true ) ),
				'secondary_goal' => self::normalize_secondary_goal( get_post_meta( $post_id, self::META_SECONDARY_GOAL, true ) ),
				'lead_offer'     => sanitize_key( (string) get_post_meta( $post_id, self::META_LEAD_OFFER, true ) ),
				'featured_book'  => sanitize_key( (string) get_post_meta( $post_id, self::META_FEATURED_BOOK, true ) ),
				'is_classified'  => true,
				'source'         => 'explicit',
			);
		}

		$derived = self::derive_from_guide_registry( $post_id );
		if ( null !== $derived ) {
			$derived['is_classified'] = false;
			$derived['source']        = 'guide_registry_derived';
			return $derived;
		}

		return array(
			'audience'       => self::DEFAULT_AUDIENCE,
			'funnel_stage'   => self::DEFAULT_FUNNEL_STAGE,
			'intent'         => self::DEFAULT_INTENT,
			'primary_goal'   => self::DEFAULT_PRIMARY_GOAL,
			'secondary_goal' => self::DEFAULT_SECONDARY_GOAL,
			'lead_offer'     => '',
			'featured_book'  => '',
			'is_classified'  => false,
			'source'         => 'flat_default',
		);
	}

	/**
	 * Deterministic mapping from an existing bhp_get_guide_registry()
	 * entry (functions.php) to this class's enum shape. Returns null for
	 * any post not present in that registry (including every post type
	 * other than 'post', since the registry is post-only) -- callers
	 * fall through to the flat default in that case.
	 */
	private static function derive_from_guide_registry( $post_id ) {
		if ( ! function_exists( 'bhp_get_guide_post_data' ) ) {
			return null;
		}
		$post = get_post( $post_id );
		if ( ! $post || 'post' !== $post->post_type ) {
			return null;
		}
		$entry = bhp_get_guide_post_data( $post );
		if ( empty( $entry ) ) {
			return null;
		}

		$registry_audiences = (array) ( $entry['audiences'] ?? array() );
		if ( in_array( 'Librarians', $registry_audiences, true ) ) {
			$audience = 'librarian';
		} elseif ( in_array( 'Educators', $registry_audiences, true ) ) {
			$audience = 'teacher';
		} elseif ( in_array( 'Families', $registry_audiences, true ) ) {
			$audience = 'parent';
		} else {
			$audience = self::DEFAULT_AUDIENCE;
		}

		$intent_map = array(
			'Reading resource'      => 'reading_development',
			'Educational article'   => 'educational',
			'Educator guide'        => 'teacher_resource',
			'Book-related article'  => 'book_recommendation',
		);
		$intent = $intent_map[ $entry['type'] ?? '' ] ?? self::DEFAULT_INTENT;
		if ( 'science-geography' === ( $entry['primary'] ?? '' ) ) {
			$intent = 'adventure_geography'; // more specific than the generic type-based mapping above
		}

		$destination    = (string) ( $entry['destination'] ?? '' );
		$book           = (string) ( $entry['book'] ?? '' );
		$featured_book  = $destination ?: ( 'series-wide' === $book ? '' : $book );
		$featured_book  = $featured_book ? str_replace( '-', '_', sanitize_key( $featured_book ) ) : '';

		if ( $destination ) {
			$funnel_stage = 'product_discovery';
			$primary_goal = 'visit_book_page';
		} elseif ( 'series-wide' === $book ) {
			$funnel_stage = 'consideration';
			$primary_goal = 'visit_collection_page';
		} else {
			$funnel_stage = self::DEFAULT_FUNNEL_STAGE;
			$primary_goal = self::DEFAULT_PRIMARY_GOAL;
		}

		return array(
			'audience'       => self::normalize_audience( $audience ),
			'funnel_stage'   => self::normalize_funnel_stage( $funnel_stage ),
			'intent'         => self::normalize_intent( $intent ),
			'primary_goal'   => self::normalize_primary_goal( $primary_goal ),
			'secondary_goal' => self::DEFAULT_SECONDARY_GOAL,
			'lead_offer'     => '',
			'featured_book'  => $featured_book,
		);
	}

	public static function add_meta_box() {
		foreach ( self::eligible_post_types() as $post_type ) {
			add_meta_box(
				'bhp_content_classification',
				__( 'Brave Hearts — Funnel Classification', 'brave-hearts' ),
				array( __CLASS__, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public static function render_meta_box( $post ) {
		$c = self::get_classification( $post->ID );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		$render_select = function ( $label, $name, $options, $current ) {
			echo '<p><label for="' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
			echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" style="width:100%;">';
			foreach ( $options as $value => $option_label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $value ),
					selected( $current, $value, false ),
					esc_html( $option_label )
				);
			}
			echo '</select></p>';
		};
		$render_select( __( 'Audience', 'brave-hearts' ), self::META_AUDIENCE, self::audiences(), $c['audience'] );
		$render_select( __( 'Funnel Stage', 'brave-hearts' ), self::META_FUNNEL_STAGE, self::funnel_stages(), $c['funnel_stage'] );
		$render_select( __( 'Content Intent', 'brave-hearts' ), self::META_INTENT, self::content_intents(), $c['intent'] );
		$render_select( __( 'Primary Conversion Goal', 'brave-hearts' ), self::META_PRIMARY_GOAL, self::primary_goals(), $c['primary_goal'] );
		$render_select( __( 'Secondary Conversion Goal', 'brave-hearts' ), self::META_SECONDARY_GOAL, self::secondary_goals(), $c['secondary_goal'] );
		echo '<p><label for="' . esc_attr( self::META_LEAD_OFFER ) . '"><strong>' . esc_html__( 'Lead Offer key (optional)', 'brave-hearts' ) . '</strong></label><br>';
		echo '<input type="text" style="width:100%;" name="' . esc_attr( self::META_LEAD_OFFER ) . '" id="' . esc_attr( self::META_LEAD_OFFER ) . '" value="' . esc_attr( $c['lead_offer'] ) . '" placeholder="e.g. reluctant_reader_adventure_kit"></p>';
		echo '<p><label for="' . esc_attr( self::META_FEATURED_BOOK ) . '"><strong>' . esc_html__( 'Featured Book key (optional)', 'brave-hearts' ) . '</strong></label><br>';
		echo '<input type="text" style="width:100%;" name="' . esc_attr( self::META_FEATURED_BOOK ) . '" id="' . esc_attr( self::META_FEATURED_BOOK ) . '" value="' . esc_attr( $c['featured_book'] ) . '" placeholder="e.g. mount_everest"></p>';
		echo '<p class="description">' . esc_html__( 'Not classifying this post is safe -- it will use documented defaults (mixed audience, awareness stage) everywhere this classification is read.', 'brave-hearts' ) . '</p>';
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_FIELD ] ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, self::eligible_post_types(), true ) ) {
			return;
		}

		$post_data = wp_unslash( $_POST );
		update_post_meta( $post_id, self::META_AUDIENCE, self::normalize_audience( $post_data[ self::META_AUDIENCE ] ?? '' ) );
		update_post_meta( $post_id, self::META_FUNNEL_STAGE, self::normalize_funnel_stage( $post_data[ self::META_FUNNEL_STAGE ] ?? '' ) );
		update_post_meta( $post_id, self::META_INTENT, self::normalize_intent( $post_data[ self::META_INTENT ] ?? '' ) );
		update_post_meta( $post_id, self::META_PRIMARY_GOAL, self::normalize_primary_goal( $post_data[ self::META_PRIMARY_GOAL ] ?? '' ) );
		update_post_meta( $post_id, self::META_SECONDARY_GOAL, self::normalize_secondary_goal( $post_data[ self::META_SECONDARY_GOAL ] ?? '' ) );
		update_post_meta( $post_id, self::META_LEAD_OFFER, sanitize_key( (string) ( $post_data[ self::META_LEAD_OFFER ] ?? '' ) ) );
		update_post_meta( $post_id, self::META_FEATURED_BOOK, sanitize_key( (string) ( $post_data[ self::META_FEATURED_BOOK ] ?? '' ) ) );
	}

	/**
	 * Phase 1D admin-dashboard summary: how many eligible posts are
	 * explicitly classified, registry-derived, or fully unclassified,
	 * plus how many are missing a lead_offer/featured_book/CTA-ready
	 * classification outright. Bounded to a finite posts_per_page cap
	 * (not -1) so this can never become an unbounded query as content
	 * grows -- caller is expected to cache the result (see
	 * BHP_KPI_Cache in the bundle-pricing plugin, already used
	 * elsewhere in the admin dashboard) rather than call this on every
	 * page load.
	 */
	public static function coverage_stats( $max_posts = 500 ) {
		$stats = array( 'total' => 0, 'explicit' => 0, 'registry_derived' => 0, 'unclassified' => 0, 'missing_lead_offer_or_book' => 0 );
		foreach ( self::eligible_post_types() as $post_type ) {
			$ids = get_posts( array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $max_posts,
				'fields'         => 'ids',
			) );
			foreach ( $ids as $post_id ) {
				$stats['total']++;
				$c = self::get_classification( $post_id );
				if ( 'explicit' === $c['source'] ) {
					$stats['explicit']++;
				} elseif ( 'guide_registry_derived' === $c['source'] ) {
					$stats['registry_derived']++;
				} else {
					$stats['unclassified']++;
				}
				if ( empty( $c['lead_offer'] ) && empty( $c['featured_book'] ) ) {
					$stats['missing_lead_offer_or_book']++;
				}
			}
		}
		return $stats;
	}

	public static function add_admin_column( $columns ) {
		$columns['bhp_classification'] = __( 'Funnel Classification', 'brave-hearts' );
		return $columns;
	}

	public static function render_admin_column( $column, $post_id ) {
		if ( 'bhp_classification' !== $column ) {
			return;
		}
		$c         = self::get_classification( $post_id );
		$audiences = self::audiences();
		$stages    = self::funnel_stages();
		$label     = esc_html( ( $audiences[ $c['audience'] ] ?? $c['audience'] ) . ' — ' . ( $stages[ $c['funnel_stage'] ] ?? $c['funnel_stage'] ) );

		if ( 'explicit' === $c['source'] ) {
			echo $label; // phpcs:ignore -- already escaped above
			return;
		}
		if ( 'guide_registry_derived' === $c['source'] ) {
			echo '<span style="color:#8a6d3b;">' . $label . '<br><small>' . esc_html__( '(derived from guide registry)', 'brave-hearts' ) . '</small></span>'; // phpcs:ignore -- $label already escaped
			return;
		}
		echo '<span style="color:#b32d2e;">' . esc_html__( 'Unclassified (using flat defaults)', 'brave-hearts' ) . '</span>';
	}
}

BHP_Content_Classification::init();
