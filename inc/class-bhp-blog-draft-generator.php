<?php
/**
 * Brave Hearts Publishing — Phase 1E: blog content assembly.
 *
 * Two distinct outputs, never conflated:
 *
 *  - generate() produces a STRUCTURAL SCAFFOLD ONLY -- section headings
 *    in the right order with [PLACEHOLDER: ...] markers where real prose
 *    belongs. This is an internal planning aid, written to
 *    draft-scaffold.json. It must NEVER be passed to
 *    BHP_WP_Draft_Workflow::create_draft() -- every placeholder marker is
 *    an explicit refusal signal, enforced by that class.
 *
 *  - assemble_article_draft() produces the actual ARTICLE DRAFT from real
 *    prose supplied by a human writer/editor. It refuses (returns
 *    WP_Error) if any supplied section still contains a placeholder
 *    marker, so a scaffold can never be smuggled through as a finished
 *    draft. Only this method's output is eligible for WordPress draft
 *    creation.
 *
 * Both outputs are built from the same set of block-builder helpers
 * below, which are validated against real, currently-published Gutenberg
 * markup on this exact WordPress install (post ID 119, WP 7.0) rather
 * than assumption -- modern core/list requires each <li> individually
 * wrapped in its own <!-- wp:list-item --> block; a flat <ul><li> is
 * invalid and triggers the block editor's "unexpected or invalid
 * content" recovery prompt. validate_markup() encodes that same rule so
 * it can be checked automatically instead of only in the live editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Blog_Draft_Generator {

	const PLACEHOLDER_MARKER = '[PLACEHOLDER:';

	/**
	 * Structural scaffold only -- see class docblock. Every section is a
	 * placeholder; nothing here is real prose.
	 */
	public static function generate( array $brief ) {
		$placeholders = array();
		$blocks       = array();

		$blocks[] = self::paragraph( '[PLACEHOLDER: opening hook naming the reader\'s specific problem — ' . $brief['reader_problem'] . ']' );
		$placeholders[] = 'opening_hook';

		foreach ( $brief['recommended_outline']['sections'] as $section ) {
			if ( 0 === strpos( $section, 'H2:' ) ) {
				$heading = trim( substr( $section, 3 ) );
				$blocks[] = self::heading( $heading );
				$blocks[] = self::paragraph( '[PLACEHOLDER: original content for "' . $heading . '" — must satisfy at least ' . ( $brief['originality_requirement']['minimum_required'] ?? 2 ) . ' of the originality requirements listed in the brief]' );
				$placeholders[] = 'section:' . $heading;
			}
		}

		if ( ! empty( $brief['factual_claims_requiring_verification'] ) ) {
			$blocks[] = self::heading( 'Fact check before publishing' );
			$blocks[] = self::fact_box( $brief['factual_claims_requiring_verification'] );
			$placeholders[] = 'factual_claims';
		}

		if ( ! empty( $brief['internal_link_targets'] ) ) {
			$blocks[] = self::heading( 'Keep exploring' );
			$blocks[] = self::related_links_block( $brief['internal_link_targets'] );
		}

		if ( ! empty( $brief['primary_cta'] ) && ! empty( $brief['primary_cta']['id'] ) ) {
			$blocks[] = self::cta_marker( $brief['primary_cta']['id'] );
		}

		if ( ! empty( $brief['faq_opportunities'] ) ) {
			$blocks[] = self::heading( 'Frequently asked questions' );
			$blocks[] = self::faq_block( $brief['faq_opportunities'] );
		}

		return array(
			'blog_slug'          => $brief['blog_slug'],
			'content_html'       => implode( "\n\n", $blocks ),
			'placeholders'       => $placeholders,
			'image_placeholders' => array( array( 'filename' => $brief['blog_slug'] . '-featured.jpg', 'alt_text_placeholder' => '[PLACEHOLDER: literal image description]' ) ),
		);
	}

	/**
	 * Real article draft, assembled from actual prose. This is the ONLY
	 * method whose output may be passed to
	 * BHP_WP_Draft_Workflow::create_draft().
	 *
	 * @param array $brief BHP_Content_Brief_Generator::generate() output.
	 * @param array $prose array(
	 *     'opening_hook' => 'plain-text prose, no placeholder markers',
	 *     'sections'     => array( array( 'heading' => 'H2 text', 'body' => 'plain-text prose' ), ... ),
	 *     'faq_answers'  => array( array( 'question' => '...', 'answer' => 'plain-text prose' ), ... ) // optional
	 * )
	 * @return array|WP_Error Same shape as generate(), with 'placeholders'
	 *                        always empty on success, or WP_Error if any
	 *                        supplied prose still contains a placeholder
	 *                        marker (refuses rather than smuggling a
	 *                        scaffold through as a finished draft).
	 */
	public static function assemble_article_draft( array $brief, array $prose ) {
		if ( empty( $prose['opening_hook'] ) || self::contains_editorial_instructions( $prose['opening_hook'] ) ) {
			return new WP_Error( 'bhp_bdg_missing_opening_hook', 'opening_hook must be real prose with no placeholder markers.' );
		}
		if ( empty( $prose['sections'] ) || ! is_array( $prose['sections'] ) ) {
			return new WP_Error( 'bhp_bdg_missing_sections', 'At least one section with real prose is required.' );
		}
		foreach ( $prose['sections'] as $i => $section ) {
			if ( empty( $section['heading'] ) || empty( $section['body'] ) ) {
				return new WP_Error( 'bhp_bdg_incomplete_section', "Section {$i} is missing a heading or body." );
			}
			if ( self::contains_editorial_instructions( $section['heading'] ) || self::contains_editorial_instructions( $section['body'] ) ) {
				return new WP_Error( 'bhp_bdg_placeholder_in_section', "Section \"{$section['heading']}\" still contains a placeholder marker; supply real prose." );
			}
		}
		foreach ( (array) ( $prose['faq_answers'] ?? array() ) as $i => $faq ) {
			if ( self::contains_editorial_instructions( $faq['question'] ?? '' ) || self::contains_editorial_instructions( $faq['answer'] ?? '' ) ) {
				return new WP_Error( 'bhp_bdg_placeholder_in_faq', "FAQ item {$i} still contains a placeholder marker; supply real prose." );
			}
		}

		$blocks   = array();
		$blocks[] = self::paragraph( $prose['opening_hook'] );

		foreach ( $prose['sections'] as $section ) {
			$blocks[] = self::heading( $section['heading'] );
			$blocks[] = self::paragraph( $section['body'] );
		}

		if ( ! empty( $brief['internal_link_targets'] ) ) {
			$blocks[] = self::heading( 'Keep exploring' );
			$blocks[] = self::related_links_block( $brief['internal_link_targets'] );
		}

		if ( ! empty( $brief['primary_cta'] ) && ! empty( $brief['primary_cta']['id'] ) ) {
			$blocks[] = self::cta_marker( $brief['primary_cta']['id'] );
		}

		if ( ! empty( $prose['faq_answers'] ) ) {
			$blocks[] = self::heading( 'Frequently asked questions' );
			foreach ( $prose['faq_answers'] as $faq ) {
				$blocks[] = self::heading( $faq['question'], 3 );
				$blocks[] = self::paragraph( $faq['answer'] );
			}
		}

		return array(
			'blog_slug'          => $brief['blog_slug'],
			'content_html'       => implode( "\n\n", $blocks ),
			'placeholders'       => array(),
			'image_placeholders' => array(),
		);
	}

	public static function contains_editorial_instructions( $text ) {
		return false !== strpos( wp_strip_all_tags( (string) $text ), self::PLACEHOLDER_MARKER );
	}

	/**
	 * Structural Gutenberg-block validity check -- returns an array of
	 * human-readable errors (empty array = valid). Grounded in the real
	 * parse_blocks() tree of an existing, currently-published post on
	 * this install (verified: core/list always carries one core/list-item
	 * child per <li>, and no block ever carries a raw <li> in its own,
	 * non-block innerContent). This is a structural check, not a full
	 * reimplementation of the editor's client-side save()-output
	 * comparison -- it catches the class of defect that produced post 460
	 * (flat lists / raw list markup inside a group), not every possible
	 * block-editor validation failure.
	 */
	public static function validate_markup( $html ) {
		$errors = array();
		self::validate_block_list( parse_blocks( $html ), $errors );
		return $errors;
	}

	private static function validate_block_list( array $blocks, array &$errors, $path = '' ) {
		foreach ( $blocks as $block ) {
			if ( null === $block['blockName'] ) {
				continue; // whitespace/text between blocks, not itself a block.
			}
			$own_html = implode( '', array_filter( (array) $block['innerContent'], 'is_string' ) );
			if ( false !== strpos( $own_html, '<li' ) && 'core/list-item' !== $block['blockName'] ) {
				$errors[] = "{$path}{$block['blockName']}: contains raw <li> markup directly in its own content instead of nested core/list-item blocks.";
			}
			if ( 'core/list' === $block['blockName'] ) {
				if ( empty( $block['innerBlocks'] ) ) {
					$errors[] = "{$path}core/list: has no nested core/list-item blocks (a flat, unwrapped list is invalid in this WordPress version).";
				} else {
					foreach ( $block['innerBlocks'] as $child ) {
						if ( 'core/list-item' !== $child['blockName'] ) {
							$errors[] = "{$path}core/list: contains a non-list-item child block '{$child['blockName']}'.";
						}
					}
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				self::validate_block_list( $block['innerBlocks'], $errors, $path . $block['blockName'] . ' > ' );
			}
		}
	}

	private static function heading( $text, $level = 2 ) {
		$level_attr = 2 === (int) $level ? '' : ' {"level":' . (int) $level . '}';
		return '<!-- wp:heading' . $level_attr . ' --><h' . (int) $level . '>' . esc_html( $text ) . '</h' . (int) $level . '><!-- /wp:heading -->';
	}

	private static function paragraph( $text ) {
		return '<!-- wp:paragraph --><p>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
	}

	/**
	 * Builds a valid, nested wp:list block from an array of already-safe
	 * inner-HTML strings for each list item's content (e.g. an anchor
	 * tag, or plain escaped text) -- every item gets its own
	 * <!-- wp:list-item --> wrapper, matching real ground-truth markup
	 * from this install (see class docblock).
	 */
	private static function list_block( array $item_inner_html ) {
		$item_inner_html = array_values( array_filter( $item_inner_html ) );
		if ( empty( $item_inner_html ) ) {
			return '';
		}
		$items = array();
		foreach ( $item_inner_html as $inner ) {
			$items[] = "<!-- wp:list-item -->\n<li>{$inner}</li>\n<!-- /wp:list-item -->";
		}
		return "<!-- wp:list -->\n<ul>" . implode( "\n\n", $items ) . "</ul>\n<!-- /wp:list -->";
	}

	private static function fact_box( array $claims ) {
		$items = array();
		foreach ( $claims as $claim ) {
			$items[] = esc_html( $claim );
		}
		return '<!-- wp:group {"className":"bhp-fact-box"} -->' . "\n" . '<div class="wp-block-group bhp-fact-box">' . self::list_block( $items ) . '</div>' . "\n" . '<!-- /wp:group -->';
	}

	private static function related_links_block( array $targets ) {
		$items = array();
		foreach ( $targets as $target ) {
			$url   = is_array( $target ) ? ( $target['url'] ?? '' ) : $target;
			$title = is_array( $target ) ? ( $target['title'] ?? $url ) : $target;
			if ( ! $url ) {
				continue;
			}
			$items[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
		}
		return self::list_block( $items );
	}

	private static function cta_marker( $cta_id ) {
		// Rendered marker only -- actual CTA HTML is resolved at render
		// time by BHP_CTA_Engine::render_for_post(), never duplicated here.
		return '<!-- wp:shortcode -->[bhp_contextual_cta id="' . esc_attr( $cta_id ) . '"]<!-- /wp:shortcode -->';
	}

	private static function faq_block( array $faqs ) {
		$items = '';
		foreach ( $faqs as $faq ) {
			$question = is_array( $faq ) ? ( $faq['question'] ?? '' ) : $faq;
			$items   .= self::heading( $question, 3 ) . "\n\n";
			$items   .= self::paragraph( '[PLACEHOLDER: verified answer]' );
		}
		return $items;
	}

	public static function write_draft_file( array $draft ) {
		$dir = get_template_directory() . '/content-engine/blogs/' . $draft['blog_slug'];
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		file_put_contents( $dir . '/draft-scaffold.json', wp_json_encode( $draft, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local build artifact.
		return $dir . '/draft-scaffold.json';
	}

	public static function write_article_draft_file( array $draft ) {
		$dir = get_template_directory() . '/content-engine/blogs/' . $draft['blog_slug'];
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		file_put_contents( $dir . '/article-draft.json', wp_json_encode( $draft, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local build artifact.
		return $dir . '/article-draft.json';
	}
}
