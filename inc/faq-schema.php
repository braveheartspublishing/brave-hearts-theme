<?php
/**
 * FAQPage structured data for in-body FAQ sections.
 *
 * 1.19.420 — CYCLE181-LD-BUILD-420, ACT-OPS-677.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SOLVES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Several blog posts carry a VISIBLE, human-readable FAQ section written as
 * plain HTML inside `post_content`: an `<h2>` carrying a known marker, then a
 * run of `<h3>` question / answer pairs, ending at the next `<h2>`.
 *
 * Rank Math emits no `FAQPage` node for that, because it only builds FAQ
 * schema from its own FAQ block — and this content is classic HTML, not a
 * block. The result is a page that answers eight questions to a reader and
 * zero to a search engine.
 *
 * This file closes that gap by reading the section back out of `post_content`
 * at render time and adding ONE `FAQPage` node to Rank Math's existing
 * `@graph`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE HARD RULE THIS FILE OBEYS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT NEVER EMITS `aggregateRating` OR `review`, IN ANY FORM, EVER.
 *    A `FAQPage` has no legitimate use for either, and the house rule
 *    (`.claude/rules/schema.md`; Standing Rules §2, §3) is absolute: no
 *    rating, review, testimonial or aggregate score is ever synthesised.
 *    The node this file builds is a closed shape — `FAQPage` → `Question` →
 *    `Answer` — and there is no code path that can add another key. The test
 *    suite asserts the emitted JSON contains neither string.
 *
 * ⛔ IT INVENTS NO TEXT. Every question and every answer is the plain-text
 *    rendering of markup a reader can already see on the page. If the visible
 *    text and the structured data ever disagree, that is a defect, and
 *    `tests/test-cycle181-build-420.php` is what catches it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ GENERIC BY MARKER, NOT HARD-CODED TO A POST
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The gate is the MARKER, never a post ID. Any post or page whose content
 * contains an `<h2>` with one of the marker ids — or one of the marker CSS
 * classes — is eligible, on every environment, with no code change. Post 82 is
 * simply the first piece of content that carries one.
 *
 * Two filters exist so this can be extended or switched off without editing
 * this file:
 *
 *     add_filter( 'bhp_faq_schema_markers', function ( $m ) {
 *         $m['ids'][] = 'shipping-faq';          // <h2 id="shipping-faq">
 *         $m['classes'][] = 'bhp-faq';           // <h2 class="bhp-faq">
 *         return $m;
 *     } );
 *
 *     add_filter( 'bhp_faq_schema_enabled', '__return_false' );   // off, no deploy
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHY RANK MATH'S FILTER AND NOT A SEPARATE <script> TAG
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The brief allowed a standalone `<script type="application/ld+json">` if
 * Rank Math's filter turned out to be unsuitable. It is not unsuitable, and
 * the filter is the better answer on three counts:
 *
 *   1. ONE graph. A second, sibling JSON-LD document would leave the FAQPage
 *      orphaned from the `WebPage` and `BlogPosting` nodes Rank Math already
 *      emits. Inside the graph it can carry `isPartOf` and `mainEntityOfPage`
 *      pointing at the real `#webpage` `@id`, which is what ties the FAQ to
 *      the page in the eyes of a consumer.
 *   2. ONE emission point. Rank Math already owns when and whether structured
 *      data is printed (it suppresses it on noindex pages, feeds, previews and
 *      404s). A separate `wp_head` echo would have to re-derive all of that,
 *      and would get it wrong somewhere.
 *   3. NO SECOND CODE PATH. `functions.php` documents at length how a schema
 *      callback in this theme silently became dead code for weeks. A fallback
 *      emitter that only runs when Rank Math is absent — which is never, on
 *      either environment — is by construction untested code. It is
 *      deliberately not written.
 *
 * Consequence, stated rather than hidden: if Rank Math is ever deactivated,
 * this file emits nothing at all. That is the intended behaviour.
 *
 * Registered at priority 999 for the reason `.claude/rules/schema.md` gives:
 * Rank Math builds its `@graph` progressively across many callbacks on this
 * same filter, and at the default priority 10 `$data` is still empty.
 *
 * @package BraveHearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The markers that identify an in-body FAQ section.
 *
 * `ids`     — matched against the `<h2>`'s `id` attribute, case-insensitively.
 * `classes` — matched against the `<h2>`'s class list, case-insensitively.
 *
 * A heading matching EITHER opens a section. The first match in document order
 * wins; a page with two marked sections contributes only the first, because a
 * page may carry only one `FAQPage` node and merging two authored sections
 * into one would silently change what the author wrote.
 *
 * @return array{ids:string[],classes:string[]}
 */
function bhp_faq_schema_markers() {
	$markers = array(
		'ids'     => array( 'reading-level-faq' ),
		'classes' => array( 'bhp-faq-section' ),
	);

	$filtered = apply_filters( 'bhp_faq_schema_markers', $markers );

	if ( ! is_array( $filtered ) ) {
		return $markers;
	}

	return array(
		'ids'     => isset( $filtered['ids'] ) && is_array( $filtered['ids'] )
			? array_values( array_filter( array_map( 'strval', $filtered['ids'] ) ) )
			: array(),
		'classes' => isset( $filtered['classes'] ) && is_array( $filtered['classes'] )
			? array_values( array_filter( array_map( 'strval', $filtered['classes'] ) ) )
			: array(),
	);
}

/**
 * Normalise a DOM node's rendered text the way a reader sees it.
 *
 * Entities are decoded, every run of whitespace (including the newlines the
 * editor stores between tags) collapses to one space, and the result is
 * trimmed. This is what makes "the structured data equals the visible text" a
 * checkable claim rather than an approximate one.
 *
 * @param string $text Raw text content.
 * @return string
 */
function bhp_faq_schema_normalise_text( $text ) {
	$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	// Non-breaking space is whitespace to a reader; \s does not match it.
	$text = str_replace( "\xc2\xa0", ' ', $text );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return trim( (string) $text );
}

/**
 * Extract the question/answer pairs of a marked FAQ section from raw content.
 *
 * ⭐ THIS FUNCTION IS PURE. It takes a string and returns an array, touches no
 *    global state, and hits no database — which is the only reason the test
 *    suite can run it against fixtures without a page load, and is why the
 *    parsing logic and the WordPress plumbing are separate functions.
 *
 * The section is defined structurally, exactly as a reader sees it:
 *   · it OPENS at the first `<h2>` carrying a marker;
 *   · it CLOSES at the next `<h2>` at any depth, or at the end of the content;
 *   · inside it, each `<h3>` opens a question and everything up to the next
 *     `<h3>` (or the end of the section) is that question's answer.
 *
 * Both element and text nodes are collected for the answer, so content stored
 * with explicit `<p>` tags and content stored as bare text between headings
 * both work. A pair with an empty question or an empty answer is dropped
 * rather than emitted half-formed.
 *
 * @param string $content Raw `post_content`.
 * @param array  $markers Result of bhp_faq_schema_markers().
 * @return array<int,array{question:string,answer:string}> In document order.
 */
function bhp_faq_schema_extract_pairs( $content, array $markers ) {
	$content = (string) $content;

	if ( '' === trim( $content ) || ! class_exists( 'DOMDocument' ) ) {
		return array();
	}

	$ids     = array_map( 'strtolower', isset( $markers['ids'] ) ? $markers['ids'] : array() );
	$classes = array_map( 'strtolower', isset( $markers['classes'] ) ? $markers['classes'] : array() );

	if ( empty( $ids ) && empty( $classes ) ) {
		return array();
	}

	/*
	 * ⛔ CHEAP REJECT FIRST. This callback runs on every singular request, and
	 *    the overwhelming majority of posts carry no marker at all. Building a
	 *    DOM for all of them to discover that is waste. A substring test is not
	 *    a correctness check — the DOM walk below is — it only decides whether
	 *    the DOM walk is worth doing.
	 */
	$haystack = strtolower( $content );
	$maybe    = false;
	foreach ( array_merge( $ids, $classes ) as $needle ) {
		if ( '' !== $needle && false !== strpos( $haystack, $needle ) ) {
			$maybe = true;
			break;
		}
	}
	if ( ! $maybe ) {
		return array();
	}

	$previous = libxml_use_internal_errors( true );
	$doc      = new DOMDocument();

	/*
	 * ⛔ THE ENCODING PROLOGUE IS LOAD-BEARING, NOT DECORATION. Without it
	 *    DOMDocument assumes ISO-8859-1 and every curly apostrophe, en dash and
	 *    "6–9" in the copy comes back as mojibake — which would then be written
	 *    into structured data as the author's words. The prologue is stripped
	 *    from the tree by libxml itself, so it never reaches any output.
	 */
	$loaded = $doc->loadHTML(
		'<?xml encoding="UTF-8">' . '<div id="bhp-faq-root">' . $content . '</div>',
		LIBXML_NOWARNING | LIBXML_NOERROR
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return array();
	}

	$headings = $doc->getElementsByTagName( 'h2' );
	$start    = null;

	foreach ( $headings as $h2 ) {
		$id         = strtolower( trim( (string) $h2->getAttribute( 'id' ) ) );
		$class_attr = strtolower( trim( (string) $h2->getAttribute( 'class' ) ) );
		$class_list = '' === $class_attr ? array() : preg_split( '/\s+/', $class_attr );

		if ( '' !== $id && in_array( $id, $ids, true ) ) {
			$start = $h2;
			break;
		}
		if ( ! empty( $class_list ) && array_intersect( $class_list, $classes ) ) {
			$start = $h2;
			break;
		}
	}

	if ( null === $start ) {
		return array();
	}

	$pairs    = array();
	$question = null;
	$answer   = array();

	$flush = static function () use ( &$pairs, &$question, &$answer ) {
		if ( null === $question ) {
			return;
		}
		$q = bhp_faq_schema_normalise_text( $question );
		$a = bhp_faq_schema_normalise_text( implode( ' ', $answer ) );

		if ( '' !== $q && '' !== $a ) {
			$pairs[] = array(
				'question' => $q,
				'answer'   => $a,
			);
		}
		$question = null;
		$answer   = array();
	};

	for ( $node = $start->nextSibling; null !== $node; $node = $node->nextSibling ) {
		if ( XML_ELEMENT_NODE === $node->nodeType ) {
			$tag = strtolower( $node->nodeName );

			// The section ends at the next h2. Nothing after it belongs here.
			if ( 'h2' === $tag ) {
				break;
			}

			if ( 'h3' === $tag ) {
				$flush();
				$question = $node->textContent;
				continue;
			}

			if ( null !== $question ) {
				$answer[] = $node->textContent;
			}
			continue;
		}

		if ( XML_TEXT_NODE === $node->nodeType && null !== $question ) {
			$answer[] = $node->nodeValue;
		}
	}

	$flush();

	return $pairs;
}

/**
 * Build the FAQPage node for one post, or null when there is nothing to say.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function bhp_faq_schema_node_for_post( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return null;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return null;
	}

	$pairs = bhp_faq_schema_extract_pairs( $post->post_content, bhp_faq_schema_markers() );
	if ( empty( $pairs ) ) {
		return null;
	}

	$permalink = get_permalink( $post_id );
	if ( ! is_string( $permalink ) || '' === $permalink ) {
		return null;
	}

	$questions = array();
	foreach ( $pairs as $i => $pair ) {
		$questions[] = array(
			'@type'          => 'Question',
			'@id'            => $permalink . '#faq-q' . ( $i + 1 ),
			'name'           => $pair['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pair['answer'],
			),
		);
	}

	/*
	 * ⛔ THE SHAPE IS CLOSED AND IT IS CLOSED ON PURPOSE. Every key that can
	 *    appear in this node appears literally below. There is no merge, no
	 *    spread and no filter over the finished node, so `aggregateRating` and
	 *    `review` cannot arrive by any route.
	 */
	return array(
		'@type'            => 'FAQPage',
		'@id'              => $permalink . '#faq',
		'url'              => $permalink,
		'isPartOf'         => array( '@id' => $permalink . '#webpage' ),
		'mainEntityOfPage' => array( '@id' => $permalink . '#webpage' ),
		'mainEntity'       => $questions,
	);
}

/**
 * Add the FAQPage node to Rank Math's JSON-LD graph.
 *
 * @param array $data  The graph, keyed by node name.
 * @param mixed $jsonld Rank Math's JsonLD instance (unused).
 * @return array
 */
function bhp_faq_schema_add_node( $data, $jsonld = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	unset( $jsonld );

	if ( ! is_array( $data ) ) {
		return $data;
	}

	if ( ! apply_filters( 'bhp_faq_schema_enabled', true ) ) {
		return $data;
	}

	// Singular content only. An archive has no single post_content to read.
	if ( ! is_singular() ) {
		return $data;
	}

	// ⛔ Never overwrite an existing FAQ node. If Rank Math's own FAQ block is
	//    ever used on a page that ALSO carries a marked section, the authored
	//    block wins and this file stays silent rather than emitting a second,
	//    competing FAQPage.
	foreach ( $data as $node ) {
		if ( is_array( $node ) && isset( $node['@type'] ) && 'FAQPage' === $node['@type'] ) {
			return $data;
		}
	}

	$node = bhp_faq_schema_node_for_post( get_queried_object_id() );
	if ( null === $node ) {
		return $data;
	}

	$data['faqpage'] = $node;

	return $data;
}
add_filter( 'rank_math/json_ld', 'bhp_faq_schema_add_node', 999, 2 );
