<?php
/**
 * Brave Hearts Publishing — content HTML sanitation and structural
 * validation gate, added after Weekly Production Cycle 1's Amazon
 * rainforest article shipped Squarespace-migration markup
 * (`sqsrte-` classes, `data-rte-preserve-empty`, inline
 * `white-space:pre-wrap` styling) and a nested `<p><p>...</p></p>`
 * defect into a brand-new WordPress draft. See
 * docs/weekly-cycle-1-qa-failure-audit.md for the root-cause writeup.
 *
 * This does not touch existing published posts — legacy content keeps
 * whatever markup it already has. This gate only blocks *new* draft
 * content from carrying that legacy pattern forward.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_HTML_Sanitizer {

	const CONTAMINATION_PATTERNS = array(
		'sqsrte_class'          => '/class="[^"]*\bsqsrte-[a-z]+\b[^"]*"/i',
		'data_rte_preserve'     => '/data-rte-preserve-empty\s*=\s*"[^"]*"/i',
		'inline_white_space'    => '/style="[^"]*white-space\s*:\s*pre-wrap[^"]*"/i',
	);

	/**
	 * Known staging hostnames for this project (checked against
	 * brave-hearts-seo-engine's .env and this theme's codebase — no
	 * other staging environment is currently configured). The generic
	 * pattern below also catches any future stagingN.braveheartspublishing.com
	 * without needing a new entry here every time one is spun up.
	 */
	const KNOWN_STAGING_HOSTNAMES = array(
		'staging2.braveheartspublishing.com',
	);
	const GENERIC_STAGING_HOSTNAME_PATTERN = '/\bstaging[0-9]*\.braveheartspublishing\.com\b/i';

	/**
	 * @param string $html     Raw draft HTML (never Gutenberg-serialized
	 *                         — this site's content is plain HTML,
	 *                         matching the existing non-block convention).
	 * @param array  $metadata Optional flat array of other package
	 *                         fields to scan for the same staging-host
	 *                         leakage the body gets checked for --
	 *                         e.g. ['seo_title' => ..., 'meta_description' => ...,
	 *                         'cta_destination_url' => ..., 'pinterest_destination_url' => ...].
	 *                         Field names are only used in the violation
	 *                         message, not matched against a fixed list,
	 *                         so any metadata field can be passed in.
	 * @return array List of human-readable violation strings. Empty
	 *               array = clean, safe to include in a WordPress
	 *               readiness package.
	 */
	public static function validate( $html, array $metadata = array() ) {
		$html = (string) $html;
		$violations = array();

		foreach ( self::CONTAMINATION_PATTERNS as $key => $pattern ) {
			if ( preg_match( $pattern, $html ) ) {
				$violations[] = "Squarespace-migration markup detected ({$key}) — new draft content must not carry this pattern forward.";
			}
		}

		if ( preg_match( '/<p[^>]*>\s*<p[^>]*>/i', $html ) ) {
			$violations[] = 'Nested <p><p> detected — a paragraph element is opened directly inside another paragraph element without closing the first. This is invalid HTML and a known symptom of a str_replace() edit that targeted inner text instead of a complete element.';
		}

		if ( preg_match( '/<p[^>]*>(\s|&nbsp;)*<\/p>/i', $html ) ) {
			$violations[] = 'Empty <p></p> element detected (no content, or only whitespace/&nbsp;).';
		}

		$tag_balance_errors = self::check_tag_balance( $html );
		foreach ( $tag_balance_errors as $err ) {
			$violations[] = $err;
		}

		$heading_errors = self::check_heading_hierarchy( $html );
		foreach ( $heading_errors as $err ) {
			$violations[] = $err;
		}

		$link_errors = self::check_links( $html );
		foreach ( $link_errors as $err ) {
			$violations[] = $err;
		}

		foreach ( self::check_environment_leakage( $html, 'body content' ) as $err ) {
			$violations[] = $err;
		}
		foreach ( $metadata as $field_name => $field_value ) {
			foreach ( self::check_environment_leakage( (string) $field_value, "metadata field '{$field_name}'" ) as $err ) {
				$violations[] = $err;
			}
		}

		if ( preg_match( '/\[PLACEHOLDER[^\]]*\]/i', $html ) || preg_match( '/\bTODO\b|\bFIXME\b|\bXXX\b/', $html ) ) {
			$violations[] = 'Editor-only instruction or placeholder marker found in the body — must be resolved before this counts as WordPress-ready.';
		}

		return $violations;
	}

	/**
	 * Catches a staging hostname appearing ANYWHERE in the given text —
	 * not just inside an href attribute, since a staging URL can leak
	 * into plain body text, an SEO title/description, a CTA destination
	 * field, or a Pinterest destination_url field just as easily as an
	 * <a> tag. Production-ready content must never reference a staging
	 * hostname in any of these places.
	 */
	private static function check_environment_leakage( $text, $context_label ) {
		$violations = array();
		foreach ( self::KNOWN_STAGING_HOSTNAMES as $host ) {
			if ( false !== stripos( $text, $host ) ) {
				$violations[] = "Staging hostname '{$host}' found in {$context_label} — production-ready content must never reference a staging environment.";
			}
		}
		if ( preg_match( self::GENERIC_STAGING_HOSTNAME_PATTERN, $text, $m ) ) {
			$found = $m[0];
			if ( ! in_array( strtolower( $found ), array_map( 'strtolower', self::KNOWN_STAGING_HOSTNAMES ), true ) ) {
				$violations[] = "Unrecognized staging-pattern hostname '{$found}' found in {$context_label} — production-ready content must never reference a staging environment.";
			}
		}
		return $violations;
	}

	/**
	 * Simple stack-based tag-balance check for the small set of block
	 * elements this site's content actually uses (p, h1-h6, a, strong,
	 * em, ul, ol, li, blockquote, div, span, section, aside). Void
	 * elements (br, img, hr) are ignored. Not a full HTML5 parser --
	 * deliberately scoped to the tag vocabulary this theme's content
	 * actually contains.
	 */
	private static function check_tag_balance( $html ) {
		$errors = array();
		$block_tags = array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'blockquote', 'div', 'span', 'section', 'aside' );
		$pattern = '/<(\/?)(' . implode( '|', $block_tags ) . ')\b[^>]*>/i';
		preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER );

		$stack = array();
		foreach ( $matches as $m ) {
			$is_closing = '/' === $m[1];
			$tag = strtolower( $m[2] );
			if ( $is_closing ) {
				if ( empty( $stack ) ) {
					$errors[] = "Unmatched closing tag </{$tag}> with no corresponding open tag.";
					continue;
				}
				$top = array_pop( $stack );
				if ( $top !== $tag ) {
					$errors[] = "Mismatched closing tag: expected </{$top}>, found </{$tag}>.";
					// Best-effort recovery: put the mismatched open tag
					// back so a single typo doesn't cascade into dozens
					// of false positives for the rest of the document.
					$stack[] = $top;
				}
			} else {
				$stack[] = $tag;
			}
		}
		if ( ! empty( $stack ) ) {
			$errors[] = 'Unclosed tag(s) at end of document: <' . implode( '>, <', array_unique( $stack ) ) . '>.';
		}
		return $errors;
	}

	/**
	 * This theme renders the post title as the page's only <h1> (see
	 * single.php). Body content must never introduce a second <h1>, and
	 * heading levels used in the body should be internally consistent
	 * (this site's real published posts mix H2-H4 freely without strict
	 * top-down nesting, so this check only catches the two concrete,
	 * confirmed-real defects: a stray body <h1>, and more than one
	 * distinct top-level heading level opening the very first heading
	 * of the article at an unusually deep level like H5/H6, which has
	 * never appeared in any real published post checked this session).
	 */
	private static function check_heading_hierarchy( $html ) {
		$errors = array();
		if ( preg_match( '/<h1[\s>]/i', $html ) ) {
			$errors[] = 'Body content contains an <h1> — the theme already renders the post title as the page <h1> (single.php); a second one is invalid heading hierarchy.';
		}
		if ( preg_match_all( '/<h([1-6])[\s>]/i', $html, $m ) ) {
			$first_level = (int) $m[1][0];
			if ( $first_level >= 5 ) {
				$errors[] = "First heading in the body opens at H{$first_level} — unusually deep for an opening section heading, likely a structural mistake.";
			}
		}
		return $errors;
	}

	/**
	 * Every href must be non-empty, must not be a bare "#", and must
	 * not use a javascript: scheme. Internal links are also checked
	 * against the site's own established non-www convention (see
	 * Weekly Slate #1's www-link cleanup — new content must not
	 * reintroduce the same defect).
	 */
	private static function check_links( $html ) {
		$errors = array();
		preg_match_all( '/<a\b[^>]*href="([^"]*)"[^>]*>/i', $html, $matches );
		foreach ( $matches[1] as $href ) {
			if ( '' === trim( $href ) || '#' === trim( $href ) ) {
				$errors[] = 'Empty or bare "#" href found in a link.';
			}
			if ( 0 === stripos( trim( $href ), 'javascript:' ) ) {
				$errors[] = 'javascript: URL found in a link — not permitted.';
			}
			if ( false !== strpos( $href, 'www.braveheartspublishing.com' ) ) {
				$errors[] = "Internal link uses the www hostname ({$href}) — site convention is non-www (see Weekly Slate #1).";
			}
		}
		return $errors;
	}
}
