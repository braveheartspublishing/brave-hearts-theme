<?php
/**
 * Brave Hearts Publishing — Phase 1E: originality and editorial-quality checks.
 *
 * These are structural/editorial heuristics, NOT an "AI detection"
 * score -- this codebase deliberately does not use unreliable
 * AI-detection tooling as a quality metric (see design principle and
 * Workstream 6 instructions). Every check here is either a deterministic
 * pattern match (generic openings, keyword stuffing, duplicate phrases
 * against already-published content) or an explicit checklist a human
 * must confirm -- never a single opaque "originality score."
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Content_Originality {

	const GENERIC_OPENING_PATTERNS = array(
		'/^in today\'?s world/i',
		'/^in this article,? we will/i',
		'/^have you ever wondered/i',
		'/^let\'?s dive in/i',
		'/^as an? (ai|assistant|language model)/i',
		'/^in conclusion,/im',
	);

	/**
	 * Runs deterministic checks against a plain-text draft body. Returns
	 * an array of finding objects; an empty array does not mean the
	 * content is original -- it means no automated red flag fired. The
	 * manual originality checklist (from the brief) still requires human
	 * confirmation.
	 */
	public static function check_draft( $text, array $existing_published_texts = array() ) {
		$findings = array();

		foreach ( self::GENERIC_OPENING_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, trim( $text ) ) ) {
				$findings[] = array( 'type' => 'generic_opening', 'severity' => 'revise', 'detail' => 'Opening matches a known generic AI-content pattern.' );
				break;
			}
		}

		$findings = array_merge( $findings, self::check_repetitive_sentence_structure( $text ) );
		$findings = array_merge( $findings, self::check_keyword_stuffing( $text ) );
		$findings = array_merge( $findings, self::check_duplicate_phrases( $text, $existing_published_texts ) );

		if ( self::is_shallow_listicle( $text ) ) {
			$findings[] = array( 'type' => 'shallow_listicle', 'severity' => 'revise', 'detail' => 'Content is mostly a bare list with little explanatory synthesis between items.' );
		}

		return $findings;
	}

	private static function sentences( $text ) {
		$sentences = preg_split( '/(?<=[.!?])\s+/', wp_strip_all_tags( $text ) );
		return array_values( array_filter( array_map( 'trim', (array) $sentences ) ) );
	}

	/**
	 * Flags when many consecutive sentences start with the exact same
	 * first word/phrase (a common generative-writing tell), rather than
	 * relying on any external "AI detector."
	 */
	private static function check_repetitive_sentence_structure( $text ) {
		$sentences = self::sentences( $text );
		if ( count( $sentences ) < 6 ) {
			return array();
		}
		$openers = array_map( static function ( $s ) {
			$words = preg_split( '/\s+/', $s );
			return strtolower( $words[0] ?? '' );
		}, $sentences );

		$counts = array_count_values( $openers );
		arsort( $counts );
		$top_opener = array_key_first( $counts );
		$top_count  = $counts[ $top_opener ] ?? 0;

		if ( $top_count >= max( 4, (int) ceil( count( $sentences ) * 0.25 ) ) ) {
			return array( array(
				'type'     => 'repetitive_sentence_structure',
				'severity' => 'revise',
				'detail'   => sprintf( '%d of %d sentences begin with the same word ("%s").', $top_count, count( $sentences ), $top_opener ),
			) );
		}
		return array();
	}

	/**
	 * Flags a keyword appearing far more densely than natural prose --
	 * a coarse density check, not a precise SEO-spam classifier.
	 */
	private static function check_keyword_stuffing( $text, $keyword = null ) {
		if ( ! $keyword ) {
			return array();
		}
		$word_count = str_word_count( wp_strip_all_tags( $text ) );
		if ( $word_count < 50 ) {
			return array();
		}
		$occurrences = substr_count( strtolower( $text ), strtolower( $keyword ) );
		$density     = $occurrences / $word_count;
		if ( $density > 0.03 ) {
			return array( array(
				'type'     => 'keyword_stuffing',
				'severity' => 'revise',
				'detail'   => sprintf( 'Keyword "%s" appears %d times (%.1f%% density) -- likely over-optimized.', $keyword, $occurrences, $density * 100 ),
			) );
		}
		return array();
	}

	/**
	 * Compares against a list of ALREADY-PUBLISHED plain-text bodies
	 * (e.g. from BHP_Content_Inventory) for verbatim shared phrases of
	 * 8+ words -- a real duplication signal, not a paraphrase detector.
	 */
	private static function check_duplicate_phrases( $text, array $existing_texts, $shingle_size = 8 ) {
		$findings = array();
		$new_shingles = self::shingles( $text, $shingle_size );
		foreach ( $existing_texts as $existing ) {
			$existing_shingles = self::shingles( $existing['text'] ?? $existing, $shingle_size );
			$overlap = array_intersect( $new_shingles, $existing_shingles );
			if ( count( $overlap ) >= 2 ) {
				$findings[] = array(
					'type'     => 'duplicate_phrases',
					'severity' => 'fail',
					'detail'   => sprintf( 'Shares %d verbatim %d-word phrase(s) with existing content%s.', count( $overlap ), $shingle_size, isset( $existing['url'] ) ? ' at ' . $existing['url'] : '' ),
				);
			}
		}
		return $findings;
	}

	private static function shingles( $text, $size ) {
		$words = preg_split( '/\s+/', strtolower( wp_strip_all_tags( (string) $text ) ) );
		$words = array_values( array_filter( $words ) );
		$shingles = array();
		for ( $i = 0; $i <= count( $words ) - $size; $i++ ) {
			$shingles[] = implode( ' ', array_slice( $words, $i, $size ) );
		}
		return $shingles;
	}

	/**
	 * A "shallow listicle" here means: mostly list items (<li> or
	 * markdown "- "/"1. " lines) with very little prose between them --
	 * a structural proxy, not a subjective quality judgment.
	 */
	private static function is_shallow_listicle( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$list_lines = 0;
		$prose_lines = 0;
		foreach ( $lines as $line ) {
			$trimmed = trim( $line );
			if ( '' === $trimmed ) {
				continue;
			}
			if ( preg_match( '/^(<li|[-*]\s|\d+\.\s)/', $trimmed ) ) {
				++$list_lines;
			} elseif ( str_word_count( $trimmed ) > 8 ) {
				++$prose_lines;
			}
		}
		return $list_lines > 4 && $prose_lines < $list_lines;
	}

	/**
	 * Verifies the brief's originality checklist (see
	 * BHP_Content_Brief_Generator::originality_requirements()) has the
	 * required minimum number of confirmed items -- this is a manual
	 * confirmation gate, not something code can determine on its own.
	 */
	public static function check_manual_originality_checklist( array $brief, array $confirmed_items ) {
		$minimum = $brief['originality_requirement']['minimum_required'] ?? 2;
		$valid_confirmed = array_intersect( $confirmed_items, $brief['originality_requirement']['options'] ?? array() );
		return array(
			'required'  => $minimum,
			'confirmed' => count( $valid_confirmed ),
			'pass'      => count( $valid_confirmed ) >= $minimum,
			'items'     => $valid_confirmed,
		);
	}
}
