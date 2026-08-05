<?php
/**
 * Brave Hearts Publishing — Phase 1D: contextual CTA decision engine.
 *
 * A centralized registry of CTA definitions plus deterministic selection
 * logic, so copy/destinations/analytics metadata live in ONE place
 * instead of being hand-coded per template. Rendering reuses the
 * EXISTING template-parts/components/final-cta.php (extended this phase
 * with an optional 'attrs' array, backward compatible) rather than
 * inventing new markup — same CSS, same accessibility behavior, same
 * design system.
 *
 * This engine makes no network calls, has no side effects, and is fully
 * testable by calling select_cta() directly with a plain array — no
 * WordPress page rendering required to test the decision logic itself.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_CTA_Engine {

	public static function init() {
		add_shortcode( 'bhp_contextual_cta', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * [bhp_contextual_cta] -- lets an editor opt a specific post into a
	 * mid-article ("blog inline") CTA by placing the shortcode at the
	 * exact point in their own content where it won't break reading
	 * flow, rather than the engine auto-splicing markup into
	 * the_content() for every post (which would risk breaking cached
	 * output and editorial pacing on posts where it doesn't fit).
	 * Attributes: id (optional, a registry key -- see select_specific()),
	 * placement (default 'blog_inline'), variant (optional).
	 *
	 * With an explicit id: resolves that exact registry entry via
	 * select_specific() so an editorially-chosen destination is never
	 * silently swapped for the classification-based best-guess. An
	 * unknown or currently-unresolvable id fails safe -- renders
	 * nothing, never fabricates a different CTA the editor didn't ask
	 * for.
	 *
	 * Without an id: falls back to the existing classification-based
	 * selection (render_for_post()), unchanged from prior behavior.
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id'        => '',
			'placement' => 'blog_inline',
			'variant'   => '',
		), $atts, 'bhp_contextual_cta' );

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$id = sanitize_key( $atts['id'] );

		ob_start();
		if ( '' !== $id ) {
			$context  = class_exists( 'BHP_Content_Classification' )
				? BHP_Content_Classification::get_classification( $post_id )
				: array();
			$selected = self::select_specific( $id, $context );
			if ( null !== $selected ) {
				self::render( $selected, $atts['placement'], $atts['variant'] );
			}
			// Unknown/unresolvable id: intentionally render nothing.
		} else {
			self::render_for_post( $post_id, $atts['placement'], $atts['variant'] );
		}
		return ob_get_clean();
	}

	/**
	 * The CTA registry. Each entry:
	 * - destination_type: one of the Phase 1D destination types
	 * - presentation_style: educational_resource|problem_solution|outcome_benefit|product_discovery|series_discovery|gift_premium|teacher_resource
	 * - headline/benefit_text/cta_label: real, non-fabricated copy
	 * - audiences/funnel_stages/intents: arrays of values this CTA is a
	 *   good fit for (used for scoring, not an exclusive filter)
	 * - resolve_url: callable(array $context): string -- computed at
	 *   selection time, never a fabricated/placeholder URL. Returns ''
	 *   if the destination genuinely isn't available (e.g. no featured
	 *   book given), which select_cta() treats as "this CTA can't apply
	 *   right now" and skips it entirely.
	 */
	public static function registry() {
		return apply_filters( 'bhp_cta_registry', array(
			'adventure_kit_signup' => array(
				'destination_type'   => 'adventure_kit_signup',
				'presentation_style' => 'educational_resource',
				'headline'           => __( 'Get the Free Reluctant Reader Adventure Kit', 'brave-hearts' ),
				'benefit_text'       => __( 'A free printable guide to help your young reader dive into their first big adventure.', 'brave-hearts' ),
				'cta_label'          => __( 'Get the Free Kit', 'brave-hearts' ),
				'audiences'          => array( 'parent', 'grandparent_gift', 'homeschool', 'mixed' ),
				'funnel_stages'      => array( 'awareness', 'problem_recognition', 'consideration' ),
				'intents'            => array( 'reading_development', 'educational', 'activity_resource' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/reluctant-reader-adventure-kit/' );
				},
			),
			'book_page' => array(
				'destination_type'   => 'book_page',
				'presentation_style' => 'product_discovery',
				'headline'           => __( 'Meet Charlotte and Henry', 'brave-hearts' ),
				'benefit_text'       => __( 'Follow the adventure this article is about, told through short chapters and real places.', 'brave-hearts' ),
				'cta_label'          => __( 'See This Book', 'brave-hearts' ),
				'audiences'          => array( 'parent', 'grandparent_gift', 'homeschool', 'mixed' ),
				'funnel_stages'      => array( 'product_discovery', 'consideration', 'conversion' ),
				'intents'            => array( 'adventure_geography', 'book_recommendation', 'product_discovery' ),
				'resolve_url'        => function ( $context ) {
					$book = $context['featured_book'] ?? '';
					if ( ! $book || ! function_exists( 'bhp_get_series_adventures' ) ) {
						return '';
					}
					$adventures = bhp_get_series_adventures();
					return $adventures[ $book ]['primary_url'] ?? '';
				},
			),
			'collection_paperback' => array(
				'destination_type'   => 'collection_paperback',
				'presentation_style' => 'series_discovery',
				'headline'           => __( 'Collect the Complete Paperback Series', 'brave-hearts' ),
				'benefit_text'       => __( 'All three adventures together, at the configured complete-set price -- see the exact savings on the collection page.', 'brave-hearts' ),
				'cta_label'          => __( 'See the Complete Collection', 'brave-hearts' ),
				'audiences'          => array( 'parent', 'grandparent_gift', 'homeschool', 'mixed' ),
				'funnel_stages'      => array( 'consideration', 'conversion' ),
				'intents'            => array( 'product_discovery', 'book_recommendation' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/complete-collection/' );
				},
			),
			'collection_hardcover' => array(
				'destination_type'   => 'collection_hardcover',
				'presentation_style' => 'gift_premium',
				'headline'           => __( 'The Complete Hardcover Collection', 'brave-hearts' ),
				'benefit_text'       => __( 'A durable, gift-ready set of all three adventures -- see the exact price and savings on the collection page.', 'brave-hearts' ),
				'cta_label'          => __( 'See the Hardcover Set', 'brave-hearts' ),
				'audiences'          => array( 'grandparent_gift', 'parent', 'mixed' ),
				'funnel_stages'      => array( 'consideration', 'conversion' ),
				'intents'            => array( 'product_discovery', 'book_recommendation' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/complete-collection/' );
				},
			),
			'amazon_listing' => array(
				'destination_type'   => 'amazon_listing',
				'presentation_style' => 'product_discovery',
				'headline'           => __( 'Also Available on Amazon', 'brave-hearts' ),
				'benefit_text'       => __( 'Prefer to shop on Amazon? This book is available there too.', 'brave-hearts' ),
				'cta_label'          => __( 'View on Amazon', 'brave-hearts' ),
				'audiences'          => array( 'parent', 'grandparent_gift', 'mixed' ),
				'funnel_stages'      => array( 'conversion' ),
				'intents'            => array( 'product_discovery' ),
				'resolve_url'        => function ( $context ) {
					$book = $context['featured_book'] ?? '';
					if ( ! $book || ! function_exists( 'bhp_get_amazon_affiliate_url' ) ) {
						return '';
					}
					return bhp_get_amazon_affiliate_url( $book );
				},
			),
			'educator_toolkit_signup' => array(
				'destination_type'   => 'educator_toolkit_signup',
				'presentation_style' => 'teacher_resource',
				'headline'           => __( 'Get the Free Adventure Learning Toolkit', 'brave-hearts' ),
				'benefit_text'       => __( 'Discussion questions, geography and vocabulary activities, and read-aloud guidance built around the series -- for classrooms, homeschool days, and library programs alike.', 'brave-hearts' ),
				'cta_label'          => __( 'Get the Toolkit', 'brave-hearts' ),
				'audiences'          => array( 'teacher', 'librarian', 'homeschool' ),
				'funnel_stages'      => array( 'awareness', 'problem_recognition', 'consideration' ),
				'intents'            => array( 'homeschool_curriculum', 'activity_resource' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/educators-adventure-learning-toolkit/' );
				},
			),
			'gift_guide_signup' => array(
				'destination_type'   => 'gift_guide_signup',
				'presentation_style' => 'gift_premium',
				'headline'           => __( 'Get the Meaningful Gift Guide', 'brave-hearts' ),
				'benefit_text'       => __( 'A short guide to choosing a book gift a reluctant reader will actually want to open -- for birthdays, holidays, and any occasion in between.', 'brave-hearts' ),
				'cta_label'          => __( 'Get the Gift Guide', 'brave-hearts' ),
				'audiences'          => array( 'grandparent_gift', 'mixed' ),
				'funnel_stages'      => array( 'awareness', 'problem_recognition', 'consideration' ),
				'intents'            => array( 'gift_occasion', 'book_recommendation' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/gift-buyers-guide/' );
				},
			),
			'community_reading_kit_signup' => array(
				'destination_type'   => 'community_reading_kit_signup',
				'presentation_style' => 'teacher_resource',
				'headline'           => __( 'Get the Community Reading Kit', 'brave-hearts' ),
				'benefit_text'       => __( 'A free resource for literacy programs, nonprofits, and youth organizations planning a read-aloud, event, or bulk gifting.', 'brave-hearts' ),
				'cta_label'          => __( 'Get the Reading Kit', 'brave-hearts' ),
				'audiences'          => array( 'organization', 'mixed' ),
				'funnel_stages'      => array( 'awareness', 'problem_recognition', 'consideration' ),
				'intents'            => array( 'literacy_program', 'trust_authority' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/organizations-community-reading-kit/' );
				},
			),
			'teacher_resource' => array(
				'destination_type'   => 'teacher_resource',
				'presentation_style' => 'teacher_resource',
				'headline'           => __( 'Free Classroom Guide', 'brave-hearts' ),
				'benefit_text'       => __( 'A free discussion and activity guide for reading this series aloud in the classroom.', 'brave-hearts' ),
				'cta_label'          => __( 'Get the Classroom Guide', 'brave-hearts' ),
				'audiences'          => array( 'teacher', 'librarian' ),
				'funnel_stages'      => array( 'awareness', 'problem_recognition', 'consideration' ),
				'intents'            => array( 'teacher_resource', 'educational' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/teachers/' );
				},
			),
			'related_content' => array(
				'destination_type'   => 'related_content',
				'presentation_style' => 'educational_resource',
				'headline'           => __( 'Explore More Adventures', 'brave-hearts' ),
				'benefit_text'       => __( 'More stories, reading tips, and real-place facts for young explorers.', 'brave-hearts' ),
				'cta_label'          => __( 'Keep Exploring', 'brave-hearts' ),
				'audiences'          => array( 'parent', 'teacher', 'librarian', 'homeschool', 'grandparent_gift', 'mixed' ),
				'funnel_stages'      => array( 'awareness', 'retention_engagement' ),
				'intents'            => array( 'educational', 'reading_development', 'trust_authority', 'activity_resource' ),
				'resolve_url'        => function ( $context ) {
					return home_url( '/blog/' );
				},
			),
		) );
	}

	/**
	 * Resolves ONE specific, named registry entry rather than scoring
	 * the whole registry -- for placements where the destination is a
	 * deliberate editorial/architectural decision (e.g. "always offer
	 * the Adventure Kit here"), not a contextual best-guess. Still goes
	 * through the same resolve_url() gate, so it returns null rather
	 * than a broken CTA if the destination genuinely isn't resolvable
	 * for this context (e.g. amazon_listing with no featured_book).
	 *
	 * @param string $id      a key from self::registry()
	 * @param array  $context passed to resolve_url(); audience/funnel_stage
	 *                        default the same as select_cta() if omitted
	 * @return array|null
	 */
	public static function select_specific( $id, array $context = array() ) {
		$registry = self::registry();
		if ( ! isset( $registry[ $id ] ) ) {
			return null;
		}
		$def = $registry[ $id ];
		$url = call_user_func( $def['resolve_url'], $context );
		if ( '' === $url ) {
			return null;
		}
		$def['id']           = $id;
		$def['url']          = $url;
		$def['score']        = null;
		$def['audience']     = $context['audience'] ?? BHP_Content_Classification::DEFAULT_AUDIENCE;
		$def['funnel_stage'] = $context['funnel_stage'] ?? BHP_Content_Classification::DEFAULT_FUNNEL_STAGE;
		return $def;
	}

	/**
	 * Deterministic selection: scores every registry entry against the
	 * given context (audience/funnel_stage/intent match worth 3/2/2
	 * points respectively -- audience weighted highest since a
	 * mismatched audience is the most visible kind of "wrong CTA"),
	 * discards any entry whose resolve_url() returns empty for this
	 * context (destination genuinely unavailable), and returns the
	 * highest-scoring entry. Ties are broken by registry array order
	 * (stable, deterministic -- never random). 'related_content' is
	 * registered as low-scoring-but-always-resolvable, so it is the
	 * guaranteed final fallback if nothing else matches or resolves.
	 *
	 * @param array $context audience, funnel_stage, intent, featured_book (optional), placement (for the caller's own use, not scored)
	 * @return array|null the selected+resolved CTA, or null only if
	 *                     even the related_content fallback failed to
	 *                     resolve a URL (should not happen in practice)
	 */
	public static function select_cta( array $context ) {
		$audience     = $context['audience'] ?? BHP_Content_Classification::DEFAULT_AUDIENCE;
		$funnel_stage = $context['funnel_stage'] ?? BHP_Content_Classification::DEFAULT_FUNNEL_STAGE;
		$intent       = $context['intent'] ?? BHP_Content_Classification::DEFAULT_INTENT;

		$best_id    = null;
		$best_score = -1;
		$best_url   = '';

		foreach ( self::registry() as $id => $def ) {
			$url = call_user_func( $def['resolve_url'], $context );
			if ( '' === $url ) {
				continue; // destination unavailable in this context -- never select an unresolvable CTA
			}
			$score = 0;
			$score += in_array( $audience, $def['audiences'], true ) ? 3 : 0;
			$score += in_array( $funnel_stage, $def['funnel_stages'], true ) ? 2 : 0;
			$score += in_array( $intent, $def['intents'], true ) ? 2 : 0;
			if ( $score > $best_score ) {
				$best_score = $score;
				$best_id    = $id;
				$best_url   = $url;
			}
		}

		if ( null === $best_id ) {
			return null; // only possible if even related_content's home_url() call failed, which it never does
		}

		$selected               = self::registry()[ $best_id ];
		$selected['id']          = $best_id;
		$selected['url']         = $best_url;
		$selected['score']       = $best_score;
		$selected['audience']    = $audience;
		$selected['funnel_stage'] = $funnel_stage;
		return $selected;
	}

	/**
	 * Renders a selected CTA via the existing final-cta.php template
	 * part, with tracking attributes attached to the primary link so
	 * the generic nav.js handler (extended this phase) can fire
	 * contextual_cta_view / contextual_cta_click without any new JS
	 * file.
	 *
	 * @param array  $selected  the return value of select_cta()
	 * @param string $placement e.g. 'blog_inline', 'blog_end_of_article',
	 *                          'resource_hub', 'product_page' -- purely
	 *                          descriptive, carried through to analytics
	 * @param string $variant   optional experiment-variant identifier
	 */
	public static function render( array $selected, $placement, $variant = '' ) {
		if ( empty( $selected['url'] ) ) {
			return;
		}
		$attrs = array(
			'data-bhp-event'           => 'contextual_cta_click',
			'data-bhp-impression-event' => 'contextual_cta_view',
			'data-bhp-cta-id'          => $selected['id'],
			'data-bhp-cta-placement'   => sanitize_key( $placement ),
			'data-bhp-cta-destination' => $selected['destination_type'],
			'data-bhp-cta-audience'    => $selected['audience'],
			'data-bhp-cta-funnel-stage' => $selected['funnel_stage'],
			'data-bhp-cta-variant'     => sanitize_key( $variant ),
		);
		get_template_part(
			'template-parts/components/final-cta',
			null,
			array(
				'id'          => 'bhp-cta-' . sanitize_html_class( $selected['id'] ) . '-' . sanitize_html_class( $placement ),
				'title'       => $selected['headline'],
				'text'        => $selected['benefit_text'],
				'primary_link' => array(
					'url'   => $selected['url'],
					'label' => $selected['cta_label'],
					'attrs' => $attrs,
				),
				'class'       => 'bhp-contextual-cta bhp-contextual-cta--' . sanitize_html_class( $placement ),
			)
		);
	}

	/**
	 * Convenience wrapper: classify a post, select, and render in one
	 * call -- what most templates should actually use.
	 */
	public static function render_for_post( $post_id, $placement, $variant = '' ) {
		if ( ! class_exists( 'BHP_Content_Classification' ) ) {
			return;
		}
		$classification = BHP_Content_Classification::get_classification( $post_id );
		$selected        = self::select_cta( $classification );
		if ( null === $selected ) {
			return;
		}
		self::render( $selected, $placement, $variant );
	}
}

BHP_CTA_Engine::init();
