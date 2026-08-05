<?php
/**
 * Brave Hearts Publishing — CTA collision detector, added after Weekly
 * Production Cycle 1 shipped a manual CTA/product-path block inside a
 * draft's post_content on top of the automatic end-of-article CTA that
 * `template-parts/guides/related-content.php` renders for every post
 * NOT in the curated guide registry (`bhp_get_guide_registry()`).
 *
 * The collision is conditional, not universal: registry-member posts
 * get the curated "guide-continuation" block instead of the CTA engine
 * fallback, so a manual CTA there stacks alongside a *different* UI
 * element (still worth flagging as crowding, lower severity). Non-member
 * posts get the CTA-engine fallback directly, so a manual CTA there is a
 * near-duplicate of the same underlying component.
 *
 * See docs/weekly-cycle-1-qa-failure-audit.md, defect #12.
 *
 * REVISED after the required-contextual-links policy (2026-07-11): a
 * plain in-body sentence link to a book/product-category/collection page
 * is now a REQUIRED part of every article (see
 * docs/required-links-policy.md), not a collision risk by itself. The
 * original version of this class flagged any `product-category/*` URL
 * as a "manual CTA" purely by destination, which would have wrongly
 * rejected the new required links. Detection is now structural: a link
 * only counts as a promotional CTA block if it's a real rendered
 * CTA-engine component, carries the `final-cta` styling class, uses
 * imperative promotional anchor text, or is the sole content of its own
 * paragraph (a "punchy CTA line") rather than embedded in a sentence
 * with real surrounding prose. A link with descriptive anchor text
 * inside a paragraph that also contains other sentence text is treated
 * as a contextual link and reported separately, never as a collision.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_CTA_Collision_Detector {

	/**
	 * Signature patterns that indicate a manually-authored, CTA-shaped
	 * (promotional) block already exists in post_content -- a real
	 * rendered CTA-engine component or a hand-authored impersonation of
	 * one. Destination URL alone is deliberately NOT a signature here;
	 * see the class docblock.
	 */
	const MANUAL_CTA_SIGNATURES = array(
		'/id="bhp-cta-[a-z_]+-[a-z_]+"/i',                     // baked-in CTA-engine render
		'/class="[^"]*final-cta[^"]*"/i',
	);

	/**
	 * Imperative/promotional anchor-text phrases that mark a link as a
	 * CTA even when it isn't wrapped in a recognized CTA class -- e.g. a
	 * hand-authored "Buy Now" button-styled link with no `final-cta`
	 * class at all.
	 */
	const PROMOTIONAL_ANCHOR_PHRASES = array(
		'buy now', 'buy the book', 'shop now', 'get your copy', 'grab your copy',
		'order now', 'get the kit', 'get the adventure kit', 'shop the collection',
		'add to cart', 'purchase now',
	);

	/**
	 * URL patterns recognized as legitimate contextual-link destinations
	 * for the required-links policy (topic hub OR book/product/series).
	 * Used both to detect contextual links here and by
	 * BHP_Required_Links_Gate for the separate completeness check.
	 */
	const TOPIC_HUB_URL_PATTERNS = array(
		'/\/blog\/category\/[a-z0-9-]+\//i',
		'/\/blog\/tag\/[a-z0-9-]+\//i',
		'/\/(mariana-trench|bridge-books)\/?"/i', // curated hub pages
	);

	const BOOK_OR_PRODUCT_URL_PATTERNS = array(
		'/\/product-category\/[a-z0-9-]+\//i',
		'/\/product\/[a-z0-9-]+\//i',
		'/\/books\/?"/i',
		'/\/shop\/?"/i',
		'/\/complete-collection\/?"/i',
	);

	const AMAZON_AFFILIATE_URL_PATTERN = '/amazon\.com\/.*\btag=/i';

	/**
	 * @param int    $post_id
	 * @param string $post_content Draft body HTML (may not be saved yet).
	 * @return array {
	 *     @type bool   $manual_cta_present     Whether a manual CTA-shaped block was found in the body.
	 *     @type bool   $automatic_cta_will_fire Whether related-content.php's automatic fallback will render for this post.
	 *     @type bool   $registry_member         Whether the post is in the curated guide registry.
	 *     @type string $collision_state         'none' | 'crowding' | 'duplicate'
	 *     @type array  $required_decision       Enumerated resolution options the package must explicitly choose from.
	 * }
	 */
	public static function check( $post_id, $post_content ) {
		$content = (string) $post_content;
		$manual_cta_present = self::detect_manual_cta( $content );
		$contextual_links    = self::detect_contextual_links( $content );

		$registry_member = false;
		if ( function_exists( 'bhp_get_guide_registry' ) ) {
			$post = get_post( $post_id );
			$slug = $post ? $post->post_name : '';
			$registry_member = $slug && array_key_exists( $slug, bhp_get_guide_registry() );
		}

		// The automatic fallback (BHP_CTA_Engine::render_for_post(...,
		// 'blog_end_of_article')) only fires for non-registry posts --
		// registry members get the curated guide-continuation block
		// instead, a structurally different element.
		$automatic_cta_will_fire = ! $registry_member;

		if ( $manual_cta_present && $automatic_cta_will_fire ) {
			$collision_state = 'duplicate';
		} elseif ( $manual_cta_present && $registry_member ) {
			$collision_state = 'crowding';
		} else {
			$collision_state = 'none';
		}

		return array(
			'manual_cta_present'      => $manual_cta_present,
			'automatic_cta_will_fire' => $automatic_cta_will_fire,
			'registry_member'         => $registry_member,
			'collision_state'         => $collision_state,
			'required_decision'       => 'none' === $collision_state ? array() : array( 'manual_only', 'automatic_only', 'both_with_distinct_roles', 'no_cta' ),
			'malformed_cta_markup'    => self::check_malformed_cta_markup( $content ),
			'contextual_topic_hub_link_present' => $contextual_links['topic_hub'],
			'contextual_book_link_present'      => $contextual_links['book_or_product'],
			'contextual_amazon_affiliate_link_present' => $contextual_links['amazon_affiliate'],
		);
	}

	private static function detect_manual_cta( $content ) {
		foreach ( self::MANUAL_CTA_SIGNATURES as $pattern ) {
			if ( preg_match( $pattern, $content ) ) {
				return true;
			}
		}
		// Promotional anchor text is a CTA signal regardless of the
		// wrapping markup (e.g. a hand-styled "Buy Now" link, possibly
		// with an inline <strong>/<em> wrapper and no final-cta class).
		if ( preg_match_all( '/<a\s+[^>]*>(.*?)<\/a>/is', $content, $anchors ) ) {
			foreach ( $anchors[1] as $anchor_html ) {
				$anchor_text = trim( wp_strip_all_tags( $anchor_html ) );
				$anchor_text = trim( $anchor_text, " \t\n\r\0\x0B→»›" );
				if ( in_array( strtolower( $anchor_text ), self::PROMOTIONAL_ANCHOR_PHRASES, true ) ) {
					return true;
				}
			}
		}
		// A link that is the ONLY content of its own paragraph (a "punchy
		// CTA line") is promotional in shape even with plain anchor text
		// -- distinct from a link embedded in a sentence with real
		// surrounding prose.
		if ( preg_match( '/<p[^>]*>\s*<a\s+[^>]*>.*?<\/a>\.?\s*<\/p>/is', $content ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Detects links embedded in running prose (a paragraph containing
	 * other sentence text besides the anchor) that point to a topic-hub,
	 * book/product, or Amazon-affiliate destination -- the required
	 * contextual links this policy expects, reported separately from
	 * promotional CTA detection so a normal in-body book link is never
	 * misclassified as a collision. See docs/required-links-policy.md.
	 *
	 * @return array{topic_hub:bool,book_or_product:bool,amazon_affiliate:bool}
	 */
	private static function detect_contextual_links( $content ) {
		$result = array( 'topic_hub' => false, 'book_or_product' => false, 'amazon_affiliate' => false );

		if ( ! preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $content, $paragraphs ) ) {
			return $result;
		}

		foreach ( $paragraphs[1] as $paragraph ) {
			if ( ! preg_match_all( '/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $paragraph, $links, PREG_SET_ORDER ) ) {
				continue;
			}
			// A "punchy CTA line" paragraph (link is the whole paragraph)
			// never counts as contextual, even if the destination matches.
			$prose_without_links = trim( wp_strip_all_tags( preg_replace( '/<a\s+[^>]*>.*?<\/a>/is', '', $paragraph ) ) );
			if ( '' === $prose_without_links || strlen( $prose_without_links ) < 15 ) {
				continue;
			}
			foreach ( $links as $link ) {
				$url = $link[1];
				foreach ( self::TOPIC_HUB_URL_PATTERNS as $pattern ) {
					if ( preg_match( $pattern, $url ) ) { $result['topic_hub'] = true; }
				}
				foreach ( self::BOOK_OR_PRODUCT_URL_PATTERNS as $pattern ) {
					if ( preg_match( $pattern, $url ) ) { $result['book_or_product'] = true; }
				}
				if ( preg_match( self::AMAZON_AFFILIATE_URL_PATTERN, $url ) ) { $result['amazon_affiliate'] = true; }
			}
		}
		return $result;
	}

	/**
	 * A real CTA-engine render (see BHP_CTA_Engine::render()) always
	 * produces a `<section id="bhp-cta-{type}-{placement}" class="...
	 * bhp-contextual-cta ...">` wrapper. If body content contains the
	 * `final-cta` class (the CTA component's own styling hook) without
	 * that matching id/class pair, it's either a hand-truncated copy of
	 * a real render or a manually-authored block impersonating the
	 * component's markup without going through the real engine --
	 * either way, a structural defect worth flagging distinctly from a
	 * simple collision.
	 */
	private static function check_malformed_cta_markup( $content ) {
		$errors = array();
		if ( preg_match( '/class="[^"]*final-cta[^"]*"/i', $content )
			&& ! preg_match( '/id="bhp-cta-[a-z_]+-[a-z_]+"[^>]*class="[^"]*bhp-contextual-cta[^"]*"/i', $content ) ) {
			$errors[] = "Content contains the 'final-cta' CTA component class without the matching id=\"bhp-cta-{type}-{placement}\" / class=\"bhp-contextual-cta\" pair a real BHP_CTA_Engine::render() call always produces — likely a hand-authored or truncated CTA block, not a real engine render.";
		}
		return $errors;
	}
}
