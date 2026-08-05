<?php
/**
 * Brave Hearts Publishing — Phase 1D: reusable campaign landing-page
 * framework.
 *
 * Composes ONE configurable page shape out of components that already
 * exist and already carry the approved design system (hero.php,
 * signup-form.php, final-cta.php, teacher-resources-cta.php, the Amazon
 * review showcase, the Amazon affiliate section) -- this class does not
 * introduce any new visual design. It exists so a new campaign (parent
 * lead magnet, teacher resource, future offer) can be described as one
 * config array instead of a hand-built template file, while every field
 * stays inspectable, testable, and machine-readable for analytics.
 *
 * This does NOT replace or regenerate the existing, already-approved
 * /reluctant-reader-adventure-kit/ or /teachers/ pages -- see the two
 * example configs at the bottom of this file, which describe those same
 * audiences/offers for demonstration and future reuse without touching
 * either live page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Campaign_Landing {

	/**
	 * Required top-level config keys. A config missing any of these
	 * fails closed (render() returns an explanatory HTML comment in
	 * debug contexts, empty string otherwise) rather than emitting a
	 * half-built page.
	 */
	const REQUIRED_KEYS = array( 'campaign_id', 'audience', 'funnel_stage', 'lead_offer', 'cta_goal', 'blocks' );

	/**
	 * Recognized block types, in the fixed render order the workstream
	 * specifies: hero, benefits, trust, resource_preview, product,
	 * signup_form, amazon_alt, related_content. A config may omit any
	 * block entirely (it simply isn't rendered) but may not introduce
	 * an unrecognized block type -- validate() flags that as an error
	 * rather than silently ignoring a typo.
	 */
	const BLOCK_TYPES = array( 'hero', 'benefits', 'trust', 'resource_preview', 'product', 'signup_form', 'amazon_alt', 'related_content' );

	/**
	 * Validates a config array's shape without rendering anything.
	 * Returns a list of human-readable error strings; empty means valid.
	 * Intentionally strict about unknown block types (typos should be
	 * loud, not silently dropped) but permissive about which recognized
	 * blocks are present (a landing page legitimately might not need a
	 * product block, for example).
	 */
	public static function validate( array $config ) {
		$errors = array();
		foreach ( self::REQUIRED_KEYS as $key ) {
			// 'blocks' only needs to be present as an array -- unlike the
			// other required keys, an explicitly empty array is a valid
			// (if pointless) config, not a missing one.
			if ( 'blocks' === $key ) {
				if ( ! isset( $config['blocks'] ) || ! is_array( $config['blocks'] ) ) {
					$errors[] = "Missing required config key: {$key}";
				}
				continue;
			}
			if ( empty( $config[ $key ] ) ) {
				$errors[] = "Missing required config key: {$key}";
			}
		}
		if ( isset( $config['blocks'] ) && is_array( $config['blocks'] ) ) {
			foreach ( array_keys( $config['blocks'] ) as $block_type ) {
				if ( ! in_array( $block_type, self::BLOCK_TYPES, true ) ) {
					$errors[] = "Unrecognized block type: {$block_type}";
				}
			}
		}
		return $errors;
	}

	/**
	 * Renders a full landing page body from a config array. Returns ''
	 * (never fatals) if the config fails validate() -- callers should
	 * check validate() themselves during authoring/testing, but render()
	 * stays safe even if a bad config reaches production.
	 *
	 * Blocks render in a fixed order regardless of array order in
	 * $config['blocks'], so campaign configs stay comparable to each
	 * other and the page always reads hero -> benefits -> trust ->
	 * resource_preview -> product -> signup_form -> amazon_alt ->
	 * related_content.
	 */
	public static function render( array $config ) {
		if ( self::validate( $config ) ) {
			return '';
		}
		$blocks = $config['blocks'];
		ob_start();

		echo '<div class="bhp-campaign-landing" data-bhp-campaign-id="' . esc_attr( $config['campaign_id'] ) . '" data-bhp-campaign-audience="' . esc_attr( $config['audience'] ) . '" data-bhp-campaign-funnel-stage="' . esc_attr( $config['funnel_stage'] ) . '" data-bhp-campaign-variant="' . esc_attr( $config['variant'] ?? '' ) . '" data-bhp-campaign-source="' . esc_attr( $config['source_channel'] ?? '' ) . '" data-bhp-impression-event="landing_page_view" data-bhp-source="' . esc_attr( $config['campaign_id'] ) . '">';

		if ( ! empty( $blocks['hero'] ) ) {
			get_template_part( 'template-parts/components/hero', null, $blocks['hero'] );
		}

		if ( ! empty( $blocks['benefits'] ) ) {
			self::render_benefits_block( $blocks['benefits'] );
		}

		if ( ! empty( $blocks['trust'] ) ) {
			self::render_trust_block( $blocks['trust'] );
		}

		if ( ! empty( $blocks['resource_preview'] ) ) {
			get_template_part( 'template-parts/components/teacher-resources-cta', null, $blocks['resource_preview'] );
		}

		if ( ! empty( $blocks['product'] ) && function_exists( 'bhp_get_series_adventures' ) ) {
			self::render_product_block( $blocks['product'] );
		}

		if ( ! empty( $blocks['signup_form'] ) ) {
			$form_args = wp_parse_args( $blocks['signup_form'], array(
				'lead_magnet' => $config['lead_offer'],
			) );
			get_template_part( 'template-parts/acquisition/signup-form', null, $form_args );
		}

		if ( ! empty( $blocks['amazon_alt'] ) && function_exists( 'bhp_render_amazon_affiliate_section' ) ) {
			$amazon_args = $blocks['amazon_alt'];
			if ( ! empty( $amazon_args['adventure_key'] ) && ! empty( $amazon_args['book_name'] ) ) {
				echo bhp_render_amazon_affiliate_section( $amazon_args['adventure_key'], $amazon_args['book_name'], array( // phpcs:ignore -- already-escaped component output
					'heading' => $amazon_args['heading'] ?? __( 'Also Available on Amazon', 'brave-hearts' ),
					'source'  => 'campaign_landing_' . $config['campaign_id'],
				) );
			}
		}

		if ( ! empty( $blocks['related_content'] ) ) {
			get_template_part( 'template-parts/components/final-cta', null, $blocks['related_content'] );
		}

		echo '</div>';

		return ob_get_clean();
	}

	private static function render_benefits_block( $args ) {
		$args = wp_parse_args( $args, array( 'title' => '', 'items' => array() ) );
		if ( ! $args['title'] || ! $args['items'] ) {
			return;
		}
		?>
		<section class="bhp-campaign-landing__benefits section" aria-label="<?php echo esc_attr( $args['title'] ); ?>">
			<div class="container container--content">
				<h2 class="text-section-title"><?php echo esc_html( $args['title'] ); ?></h2>
				<ul class="bhp-campaign-landing__benefit-list">
					<?php foreach ( (array) $args['items'] as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}

	private static function render_trust_block( $args ) {
		$args = wp_parse_args( $args, array( 'badges' => array() ) );
		$badges = array_filter( (array) $args['badges'] );
		if ( ! $badges ) {
			return;
		}
		?>
		<div class="bhp-product-trust-row">
			<?php foreach ( $badges as $badge ) : ?>
				<span class="bhp-trust-badge"><?php echo esc_html( $badge ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_product_block( $args ) {
		$args = wp_parse_args( $args, array( 'featured_book' => '' ) );
		$adventures = bhp_get_series_adventures();
		$book = $adventures[ $args['featured_book'] ] ?? null;
		if ( ! $book || empty( $book['primary_url'] ) ) {
			return;
		}
		get_template_part( 'template-parts/components/final-cta', null, array(
			'title'        => sprintf( __( 'Read %s', 'brave-hearts' ), $book['title'] ?? '' ),
			'text'         => $book['description'] ?? '',
			'primary_link' => array(
				'url'   => $book['primary_url'],
				'label' => __( 'See This Book', 'brave-hearts' ),
				'attrs' => array( 'data-bhp-event' => 'landing_page_cta_click', 'data-bhp-source' => 'campaign_landing_product_block' ),
			),
			'class'        => 'bhp-campaign-landing__product',
		) );
	}

	/**
	 * Example config: parent/family Adventure Kit lead-magnet campaign.
	 * Demonstrates the framework's shape using only real, already-
	 * approved copy/URLs -- does not create or replace any live page.
	 */
	public static function example_adventure_kit_config() {
		return array(
			'campaign_id'    => 'adventure_kit_parent_organic',
			'audience'       => 'parent',
			'funnel_stage'   => 'consideration',
			'lead_offer'     => 'adventure_kit_parent',
			'featured_book'  => '',
			'source_channel' => 'organic_blog',
			'cta_goal'       => 'adventure_kit_signup',
			'variant'        => 'a',
			'blocks'         => array(
				'hero' => array(
					'eyebrow' => __( 'Free Printable Guide', 'brave-hearts' ),
					'title'   => __( 'Get the Free Reluctant Reader Adventure Kit', 'brave-hearts' ),
					'text'    => __( 'A free printable guide to help your young reader dive into their first big adventure.', 'brave-hearts' ),
				),
				'benefits' => array(
					'title' => __( 'What\'s Inside', 'brave-hearts' ),
					'items' => array(
						__( 'A printable reading-adventure activity sheet', 'brave-hearts' ),
						__( 'Discussion prompts for read-aloud time', 'brave-hearts' ),
						__( 'A first-chapter preview from the series', 'brave-hearts' ),
					),
				),
				'trust' => array(
					'badges' => array( __( 'Five-Star Reader Reviews', 'brave-hearts' ), __( 'Independent Reading & Read-Aloud Friendly', 'brave-hearts' ) ),
				),
				'signup_form' => array(
					'context'       => 'adventure_kit',
					'audience_type' => 'parents_families',
					'require_name'  => true,
				),
			),
		);
	}

	/**
	 * Example config: teacher/classroom resource campaign.
	 * Same non-destructive demonstration purpose as above.
	 */
	public static function example_teacher_guide_config() {
		return array(
			'campaign_id'    => 'teacher_classroom_guide_organic',
			'audience'       => 'teacher',
			'funnel_stage'   => 'awareness',
			'lead_offer'     => 'mariana_classroom_guide',
			'featured_book'  => 'mariana_trench',
			'source_channel' => 'organic_blog',
			'cta_goal'       => 'teacher_resource',
			'variant'        => 'a',
			'blocks'         => array(
				'hero' => array(
					'eyebrow' => __( 'Free Classroom Resource', 'brave-hearts' ),
					'title'   => __( 'Free Mariana Trench Classroom Guide', 'brave-hearts' ),
					'text'    => __( 'A free discussion and activity guide for reading this series aloud in the classroom.', 'brave-hearts' ),
				),
				'resource_preview' => array(
					'title' => __( 'Built for Read-Alouds', 'brave-hearts' ),
					'items' => array(
						__( 'Discussion questions by chapter', 'brave-hearts' ),
						__( 'A vocabulary and science-facts companion', 'brave-hearts' ),
					),
				),
				'product' => array( 'featured_book' => 'mariana_trench' ),
				'signup_form' => array(
					'context'       => 'mariana_classroom_guide',
					'audience_type' => 'teachers',
				),
			),
		);
	}
}
