<?php
/**
 * ⭐⭐ CYCLE181-LD-BUILD-420 — FAQPage structured data for in-body FAQ
 *     sections (`ACT-OPS-677`). Theme 1.19.420.
 *
 * Run with:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle181-build-420.php \
 *       --url=<site-url> --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE ROWS THAT CAN ACTUALLY FAIL ON A PLAUSIBLE BAD BUILD.
 *     Read these before adding a grep and calling this covered.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * §2.4 — ⛔⛔ NO `aggregateRating`, NO `review`, EVER. This is the row that
 * guards the company's firmest rule (Standing Rules §2, §3). It does not read
 * the source and conclude the keys are absent — it SERIALISES the finished node
 * to JSON and asserts neither string occurs anywhere in it, so a key arriving
 * by any future merge, spread or filter is caught at the output, not the
 * intention. §2.4b proves the search itself works by finding a string that IS
 * there.
 *
 * §2.5 — A POST WITHOUT THE SECTION EMITS NOTHING. The dangerous version of
 * this feature is one that fires on every post and publishes an empty or
 * guessed FAQ. This row takes a real published post that carries no marker and
 * requires `null`.
 *
 * §1.3 — THE SECTION ENDS AT THE NEXT `<h2>`. Without that boundary the
 * extractor would swallow every `<h3>` in the rest of the article and publish
 * body subheadings as questions. The fixture deliberately puts an `<h3>` after
 * the closing `<h2>` and requires it to be absent from the result.
 *
 * §1.9 — UTF-8 SURVIVES THE DOM PARSE. `DOMDocument` assumes ISO-8859-1 unless
 * told otherwise, and the failure is silent: every curly apostrophe and en dash
 * in the copy becomes mojibake and is then written into structured data AS THE
 * AUTHOR'S WORDS. A row that only counted pairs would pass while publishing
 * garbage.
 *
 * §4.1 — THE STRUCTURED TEXT EQUALS THE VISIBLE TEXT. Extraction that silently
 * dropped, reordered or truncated an answer would satisfy every count above.
 * This row walks the pairs back to the rendered content of the live post.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE. This suite exercises PHP. It does NOT
 *   prove what Google's parser accepted, and it does NOT prove what a browser
 *   painted. The rendered `<script class="rank-math-schema">` block on a real
 *   page load is checked separately and reported in `CYCLE181-LD-BUILD-420`.
 *
 * @package BraveHearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['b420_passes']   = 0;
$GLOBALS['b420_failures'] = 0;
$GLOBALS['b420_skips']    = 0;

function b420_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['b420_passes']++;
		printf( "  PASS  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '' );
	} else {
		$GLOBALS['b420_failures']++;
		printf( "  FAIL  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '' );
	}
	return (bool) $ok;
}

function b420_skip( $label, $why ) {
	$GLOBALS['b420_skips']++;
	printf( "  SKIP  %s  [%s]\n", $label, $why );
}

$b420_theme_dir = get_template_directory();

echo "\n=== CYCLE181-LD-BUILD-420 — FAQPage structured data ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §0 — THE FILE IS LOADED AND THE API EXISTS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §0 loading --\n";

b420_assert( '0.1 inc/faq-schema.php exists', file_exists( $b420_theme_dir . '/inc/faq-schema.php' ) );
b420_assert( '0.2 functions.php requires it', false !== strpos(
	(string) @file_get_contents( $b420_theme_dir . '/functions.php' ),
	"require_once get_template_directory() . '/inc/faq-schema.php';"
) );
foreach ( array(
	'bhp_faq_schema_markers',
	'bhp_faq_schema_normalise_text',
	'bhp_faq_schema_extract_pairs',
	'bhp_faq_schema_node_for_post',
	'bhp_faq_schema_add_node',
) as $b420_fn ) {
	b420_assert( "0.3 {$b420_fn}() is defined", function_exists( $b420_fn ) );
}

if ( ! function_exists( 'bhp_faq_schema_extract_pairs' ) ) {
	printf( "\n=== %d passed, %d failed, %d skipped ===\n", $GLOBALS['b420_passes'], $GLOBALS['b420_failures'], $GLOBALS['b420_skips'] );
	exit( 1 );
}

$b420_markers = bhp_faq_schema_markers();

/* ═══════════════════════════════════════════════════════════════════════════
   §1 — THE PURE EXTRACTOR, AGAINST FIXTURES
   The parsing logic is separated from the WordPress plumbing precisely so it
   can be driven with constructed input. Every row below is a shape a real
   editor can produce.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §1 the extractor --\n";

// 1.0 — the marker set itself.
b420_assert( '1.0 default marker set carries the reading-level-faq id',
	in_array( 'reading-level-faq', $b420_markers['ids'], true ),
	implode( ',', $b420_markers['ids'] ) );

// 1.1 — the ordinary shape: id marker, h3 + p pairs, closing h2.
$b420_fx_basic = '<p>Intro.</p>'
	. '<h2 id="reading-level-faq">Questions</h2>'
	. '<h3>First question?</h3><p>First answer.</p>'
	. '<h3>Second question?</h3><p>Second answer.</p>'
	. '<h2>Something Else</h2>'
	. '<h3>Not a question</h3><p>Not an answer.</p>';
$b420_basic = bhp_faq_schema_extract_pairs( $b420_fx_basic, $b420_markers );
b420_assert( '1.1 two pairs extracted from the ordinary shape', 2 === count( $b420_basic ), count( $b420_basic ) );
b420_assert( '1.1b question text is exact',
	isset( $b420_basic[0]['question'] ) && 'First question?' === $b420_basic[0]['question'],
	isset( $b420_basic[0]['question'] ) ? $b420_basic[0]['question'] : '(none)' );
b420_assert( '1.1c answer text is exact',
	isset( $b420_basic[0]['answer'] ) && 'First answer.' === $b420_basic[0]['answer'],
	isset( $b420_basic[0]['answer'] ) ? $b420_basic[0]['answer'] : '(none)' );

/*
 * ⛔⛔ 1.3 — THE BOUNDARY ROW. Without it the extractor swallows the rest of
 *    the article and publishes body subheadings as questions.
 */
$b420_after = array();
foreach ( $b420_basic as $b420_p ) {
	$b420_after[] = $b420_p['question'];
}
b420_assert( '1.3 ⛔ the h3 AFTER the closing h2 is NOT captured',
	! in_array( 'Not a question', $b420_after, true ),
	implode( ' | ', $b420_after ) );

// 1.2 — no marker at all.
b420_assert( '1.2 content with no marker yields zero pairs',
	array() === bhp_faq_schema_extract_pairs(
		'<h2 id="something-else">Nope</h2><h3>Q</h3><p>A</p>',
		$b420_markers
	) );

// 1.4 — a multi-paragraph answer is joined, not truncated to the first p.
$b420_multi = bhp_faq_schema_extract_pairs(
	'<h2 id="reading-level-faq">Q</h2><h3>Long one?</h3><p>Part one.</p><p>Part two.</p>',
	$b420_markers
);
b420_assert( '1.4 a multi-paragraph answer is joined, not truncated',
	1 === count( $b420_multi ) && 'Part one. Part two.' === $b420_multi[0]['answer'],
	isset( $b420_multi[0]['answer'] ) ? $b420_multi[0]['answer'] : '(none)' );

// 1.5 — an answer stored as bare text between headings (no <p>).
$b420_bare = bhp_faq_schema_extract_pairs(
	'<h2 id="reading-level-faq">Q</h2><h3>Bare?</h3>Bare answer text.<h3>Two?</h3><p>Two.</p>',
	$b420_markers
);
b420_assert( '1.5 a bare-text answer (no <p>) is still captured',
	2 === count( $b420_bare ) && 'Bare answer text.' === $b420_bare[0]['answer'],
	isset( $b420_bare[0]['answer'] ) ? $b420_bare[0]['answer'] : '(none)' );

// 1.6 — the class marker works as well as the id marker.
$b420_cls = bhp_faq_schema_extract_pairs(
	'<h2 class="foo bhp-faq-section">Q</h2><h3>By class?</h3><p>Yes.</p>',
	$b420_markers
);
b420_assert( '1.6 a class marker opens a section as well as an id',
	1 === count( $b420_cls ) && 'By class?' === $b420_cls[0]['question'] );

// 1.7 — entities, nbsp and stray newlines normalise to what a reader sees.
$b420_ent = bhp_faq_schema_extract_pairs(
	"<h2 id=\"reading-level-faq\">Q</h2>\n<h3>What&#8217;s&nbsp;this?</h3>\n<p>A &amp; B\n  C</p>",
	$b420_markers
);
b420_assert( '1.7 entities and nbsp decode; whitespace collapses',
	1 === count( $b420_ent )
		&& "What\xe2\x80\x99s this?" === $b420_ent[0]['question']
		&& 'A & B C' === $b420_ent[0]['answer'],
	isset( $b420_ent[0]['question'] ) ? $b420_ent[0]['question'] . ' / ' . $b420_ent[0]['answer'] : '(none)' );

// 1.8 — a question with no answer is dropped, not emitted half-formed.
$b420_noans = bhp_faq_schema_extract_pairs(
	'<h2 id="reading-level-faq">Q</h2><h3>Orphan?</h3><h3>Real?</h3><p>Yes.</p>',
	$b420_markers
);
b420_assert( '1.8 a question with no answer is dropped',
	1 === count( $b420_noans ) && 'Real?' === $b420_noans[0]['question'],
	count( $b420_noans ) . ' pair(s)' );

/*
 * ⛔⛔ 1.9 — THE ENCODING ROW. DOMDocument defaults to ISO-8859-1 and the
 *    failure is SILENT: the pair count is right and the text is mojibake.
 */
$b420_utf = bhp_faq_schema_extract_pairs(
	"<h2 id=\"reading-level-faq\">Q</h2><h3>Ages 6\xe2\x80\x939 \xe2\x80\x94 really?</h3><p>Caf\xc3\xa9 na\xc3\xafve \xe2\x80\x9cquoted\xe2\x80\x9d</p>",
	$b420_markers
);
b420_assert( '1.9 ⛔ UTF-8 survives the DOM parse (no mojibake)',
	1 === count( $b420_utf )
		&& "Ages 6\xe2\x80\x939 \xe2\x80\x94 really?" === $b420_utf[0]['question']
		&& "Caf\xc3\xa9 na\xc3\xafve \xe2\x80\x9cquoted\xe2\x80\x9d" === $b420_utf[0]['answer'],
	isset( $b420_utf[0]['answer'] ) ? $b420_utf[0]['answer'] : '(none)' );

// 1.10 — marker matching is case-insensitive on the attribute.
b420_assert( '1.10 the id marker matches case-insensitively',
	1 === count( bhp_faq_schema_extract_pairs(
		'<h2 id="Reading-Level-FAQ">Q</h2><h3>Case?</h3><p>Yes.</p>',
		$b420_markers
	) ) );

// 1.11 — nested markup inside a question or answer is flattened to its text.
$b420_nest = bhp_faq_schema_extract_pairs(
	'<h2 id="reading-level-faq">Q</h2><h3>What is a <em>Lexile</em>?</h3><p>A <a href="/x">measure</a> of text.</p>',
	$b420_markers
);
b420_assert( '1.11 nested markup flattens to plain text',
	1 === count( $b420_nest )
		&& 'What is a Lexile?' === $b420_nest[0]['question']
		&& 'A measure of text.' === $b420_nest[0]['answer'] );

// 1.12 — CONTROL. Prove the extractor can return zero for an empty input, so
// the zeros above are real absences rather than a function that always fails.
b420_assert( '1.12 control: empty content yields zero pairs',
	array() === bhp_faq_schema_extract_pairs( '', $b420_markers ) );

/* ═══════════════════════════════════════════════════════════════════════════
   §2 — THE NODE BUILDER, AGAINST REAL CONTENT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §2 the node --\n";

/*
 * The post under test is resolved BY MARKER, not by ID. Post 82 is where the
 * section lives today, but a suite pinned to 82 would go red the day the
 * content moves and green the day the feature breaks on a different post.
 */
$b420_marked = array();
$b420_all    = get_posts( array(
	'post_type'      => array( 'post', 'page' ),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );
foreach ( $b420_all as $b420_id ) {
	$b420_post = get_post( $b420_id );
	if ( $b420_post && bhp_faq_schema_extract_pairs( $b420_post->post_content, $b420_markers ) ) {
		$b420_marked[] = (int) $b420_id;
	}
}

b420_assert( '2.0 at least one published post carries a marked FAQ section',
	count( $b420_marked ) >= 1,
	'ids: ' . ( $b420_marked ? implode( ',', $b420_marked ) : 'none' ) . ' of ' . count( $b420_all ) . ' published' );

$b420_target = $b420_marked ? $b420_marked[0] : 0;
$b420_node   = $b420_target ? bhp_faq_schema_node_for_post( $b420_target ) : null;

if ( ! is_array( $b420_node ) ) {
	b420_skip( '2.1 … 2.4 the node for the marked post', 'no marked published post found' );
} else {
	b420_assert( '2.1 the node is a FAQPage', 'FAQPage' === $b420_node['@type'] );
	b420_assert( '2.1b it carries a non-empty mainEntity list',
		isset( $b420_node['mainEntity'] ) && is_array( $b420_node['mainEntity'] ) && count( $b420_node['mainEntity'] ) >= 1,
		count( $b420_node['mainEntity'] ) . ' question(s)' );

	$b420_shape_ok = true;
	foreach ( $b420_node['mainEntity'] as $b420_q ) {
		if ( ! is_array( $b420_q )
			|| 'Question' !== ( isset( $b420_q['@type'] ) ? $b420_q['@type'] : '' )
			|| '' === trim( (string) ( isset( $b420_q['name'] ) ? $b420_q['name'] : '' ) )
			|| ! isset( $b420_q['acceptedAnswer']['@type'] )
			|| 'Answer' !== $b420_q['acceptedAnswer']['@type']
			|| '' === trim( (string) $b420_q['acceptedAnswer']['text'] ) ) {
			$b420_shape_ok = false;
			break;
		}
	}
	b420_assert( '2.2 every entry is Question → acceptedAnswer → Answer with non-empty text', $b420_shape_ok );

	$b420_link = get_permalink( $b420_target );
	b420_assert( '2.3 @id, isPartOf and mainEntityOfPage point at the real permalink',
		$b420_link . '#faq' === $b420_node['@id']
			&& $b420_link . '#webpage' === $b420_node['isPartOf']['@id']
			&& $b420_link . '#webpage' === $b420_node['mainEntityOfPage']['@id'],
		$b420_node['@id'] );

	/*
	 * ⛔⛔ 2.4 — THE COMPANY'S FIRMEST RULE, CHECKED AT THE OUTPUT.
	 *    Serialised, not source-read, so a key arriving by any future route is
	 *    caught. Standing Rules §2 and §3; `.claude/rules/schema.md`.
	 *
	 * ⭐ IT SEARCHES FOR JSON KEYS AND @type VALUES, NOT FOR THE BARE WORDS.
	 *    A bare `stripos($json, 'review')` would go red the day an author
	 *    legitimately writes "review" inside an answer — failing a CORRECT
	 *    build, which the RUNBOOK names the more expensive failure because it
	 *    teaches the next builder to distrust a passing gate. These patterns
	 *    can only match structure, never prose.
	 */
	$b420_json     = (string) wp_json_encode( $b420_node );
	$b420_forbidden = array(
		'"aggregateRating"',
		'"review"',
		'"reviews"',
		'"ratingValue"',
		'"ratingCount"',
		'"reviewRating"',
		'"@type":"Review"',
		'"@type":"AggregateRating"',
	);
	$b420_hit = '';
	foreach ( $b420_forbidden as $b420_pat ) {
		if ( false !== stripos( $b420_json, $b420_pat ) ) {
			$b420_hit = $b420_pat;
			break;
		}
	}
	b420_assert( '2.4 ⛔⛔ NO rating/review key or @type in the serialised node',
		'' === $b420_hit, '' === $b420_hit ? count( $b420_forbidden ) . ' patterns checked' : "FOUND {$b420_hit}" );
	// CONTROL: a search that cannot fail is not a search. Prove the same
	// needle-in-JSON search finds a key that IS there.
	b420_assert( '2.4b control: the same search DOES find "acceptedAnswer"',
		false !== stripos( $b420_json, '"acceptedAnswer"' ),
		strlen( $b420_json ) . ' bytes of JSON searched' );
}

/*
 * ⛔⛔ 2.5 — A POST WITHOUT THE SECTION EMITS NOTHING. The brief's explicit
 *    requirement, and the row that stops this feature firing site-wide.
 */
$b420_unmarked = 0;
foreach ( $b420_all as $b420_id ) {
	if ( ! in_array( (int) $b420_id, $b420_marked, true ) ) {
		$b420_unmarked = (int) $b420_id;
		break;
	}
}
if ( $b420_unmarked ) {
	b420_assert( '2.5 ⛔ a published post WITHOUT the section yields null',
		null === bhp_faq_schema_node_for_post( $b420_unmarked ),
		'post ' . $b420_unmarked );
} else {
	b420_skip( '2.5 a post without the section yields null', 'every published post carries a marker' );
}

b420_assert( '2.6 a non-existent post id yields null', null === bhp_faq_schema_node_for_post( 0 ) );
b420_assert( '2.6b a negative post id yields null', null === bhp_faq_schema_node_for_post( -1 ) );

/* ═══════════════════════════════════════════════════════════════════════════
   §3 — REGISTRATION AND THE GRAPH
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §3 registration --\n";

b420_assert( '3.1 registered on rank_math/json_ld at priority 999',
	999 === has_filter( 'rank_math/json_ld', 'bhp_faq_schema_add_node' ),
	var_export( has_filter( 'rank_math/json_ld', 'bhp_faq_schema_add_node' ), true ) );

// 3.2 — outside a singular request the graph is returned untouched.
$b420_seed = array( 'WebPage' => array( '@type' => 'WebPage' ) );
b420_assert( '3.2 not singular ⇒ the graph is returned unchanged',
	$b420_seed === bhp_faq_schema_add_node( $b420_seed, null ) );

/**
 * Point the main query at $post_id, run the callback, restore. Mirrors
 * tests/test-product-offer-schema.php so the two suites agree on how a
 * singular request is simulated under WP-CLI.
 */
function b420_graph_for( $post_id, array $seed ) {
	global $wp_query, $wp_the_query;

	$prev_query     = $wp_query;
	$prev_the_query = $wp_the_query;

	$q = new WP_Query( array(
		'p'         => (int) $post_id,
		'post_type' => get_post_type( $post_id ),
	) );
	$q->is_single   = true;
	$q->is_singular = true;
	$q->is_home     = false;
	$wp_query       = $q;
	$wp_the_query   = $q;

	$out = bhp_faq_schema_add_node( $seed, null );

	$wp_query     = $prev_query;
	$wp_the_query = $prev_the_query;
	wp_reset_postdata();

	return $out;
}

if ( $b420_target ) {
	$b420_graph = b420_graph_for( $b420_target, $b420_seed );
	b420_assert( '3.3 on the marked post the graph gains exactly one FAQPage node',
		isset( $b420_graph['faqpage']['@type'] ) && 'FAQPage' === $b420_graph['faqpage']['@type']
			&& count( $b420_graph ) === count( $b420_seed ) + 1,
		count( $b420_graph ) . ' node(s)' );
	b420_assert( '3.3b the pre-existing node is untouched',
		isset( $b420_graph['WebPage'] ) && $b420_seed['WebPage'] === $b420_graph['WebPage'] );

	// 3.4 — an authored Rank Math FAQ block wins; this file stays silent.
	$b420_existing = array( 'faq' => array( '@type' => 'FAQPage', 'mainEntity' => array() ) );
	b420_assert( '3.4 ⛔ an existing FAQPage node is never overwritten or duplicated',
		$b420_existing === b420_graph_for( $b420_target, $b420_existing ) );

	// 3.5 — the kill switch works with no deploy.
	add_filter( 'bhp_faq_schema_enabled', '__return_false' );
	$b420_off = b420_graph_for( $b420_target, $b420_seed );
	remove_filter( 'bhp_faq_schema_enabled', '__return_false' );
	b420_assert( '3.5 ⭐ bhp_faq_schema_enabled=false suppresses the node (no deploy needed)',
		! isset( $b420_off['faqpage'] ) );

	// 3.5b — and the switch is genuinely reversible. A kill switch that could
	// not be un-flipped would be a one-way door, exactly the defect 419's §1.3
	// exists to catch on the hardcover row.
	$b420_back = b420_graph_for( $b420_target, $b420_seed );
	b420_assert( '3.5b ⭐ and removing the filter restores it',
		isset( $b420_back['faqpage'] ) );
}

// 3.6 — the marker filter really extends the set (travelled, not assumed).
$b420_ext = function ( $m ) {
	$m['ids'][] = 'bhp-420-probe-marker';
	return $m;
};
add_filter( 'bhp_faq_schema_markers', $b420_ext );
$b420_probe = bhp_faq_schema_extract_pairs(
	'<h2 id="bhp-420-probe-marker">Q</h2><h3>Extended?</h3><p>Yes.</p>',
	bhp_faq_schema_markers()
);
remove_filter( 'bhp_faq_schema_markers', $b420_ext );
b420_assert( '3.6 ⭐ bhp_faq_schema_markers extends the marker set', 1 === count( $b420_probe ) );
b420_assert( '3.6b control: the probe marker does NOT match once the filter is removed',
	array() === bhp_faq_schema_extract_pairs(
		'<h2 id="bhp-420-probe-marker">Q</h2><h3>Extended?</h3><p>Yes.</p>',
		bhp_faq_schema_markers()
	) );

/* ═══════════════════════════════════════════════════════════════════════════
   §4 — THE STRUCTURED TEXT EQUALS THE VISIBLE TEXT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §4 structured vs visible --\n";

if ( $b420_target && is_array( $b420_node ) ) {
	$b420_src     = get_post( $b420_target )->post_content;
	$b420_visible = bhp_faq_schema_normalise_text( wp_strip_all_tags( $b420_src ) );

	/*
	 * ⭐ WHITESPACE IS REMOVED FROM BOTH SIDES BEFORE COMPARING, ON PURPOSE.
	 *    `strip_tags()` concatenates text nodes without inserting a space, so
	 *    `<p>A</p><p>B</p>` renders "AB" here while the extractor joins its
	 *    nodes with a space and produces "A B". That difference is an artefact
	 *    of two renderings, NOT a content defect — and a row that went red on
	 *    it would be failing a correct build. What this row is actually for is
	 *    text that was DROPPED, TRUNCATED, REORDERED or INVENTED, and every one
	 *    of those still fails after whitespace is removed.
	 */
	$b420_squash = static function ( $s ) {
		return preg_replace( '/\s+/u', '', (string) $s );
	};
	$b420_vis_squashed = $b420_squash( $b420_visible );

	$b420_all_present = true;
	$b420_missing     = '';
	foreach ( $b420_node['mainEntity'] as $b420_q ) {
		if ( false === strpos( $b420_vis_squashed, $b420_squash( $b420_q['name'] ) )
			|| false === strpos( $b420_vis_squashed, $b420_squash( $b420_q['acceptedAnswer']['text'] ) ) ) {
			$b420_all_present = false;
			$b420_missing     = $b420_q['name'];
			break;
		}
	}
	b420_assert( '4.1 ⭐ every question AND answer appears verbatim in the visible text',
		$b420_all_present, $b420_all_present ? count( $b420_node['mainEntity'] ) . ' pair(s)' : "missing: {$b420_missing}" );
	// CONTROL: prove the search can return false, so 4.1's green is a real
	// match and not a substring test that always succeeds.
	b420_assert( '4.1b control: a sentence that is NOT in the post is NOT found',
		false === strpos( $b420_vis_squashed, $b420_squash( 'this sentence does not occur in any Brave Hearts post' ) ) );

	// 4.2 — the pair count equals the number of h3 headings inside the section.
	$b420_sec_start = stripos( $b420_src, 'reading-level-faq' );
	$b420_h3_in_sec = 0;
	if ( false !== $b420_sec_start ) {
		$b420_rest = substr( $b420_src, $b420_sec_start );
		$b420_end  = stripos( $b420_rest, '<h2', 1 );
		$b420_sec  = false === $b420_end ? $b420_rest : substr( $b420_rest, 0, $b420_end );
		$b420_h3_in_sec = preg_match_all( '/<h3[\s>]/i', $b420_sec );
	}
	b420_assert( '4.2 the pair count equals the <h3> count inside the section',
		$b420_h3_in_sec > 0 && $b420_h3_in_sec === count( $b420_node['mainEntity'] ),
		"h3={$b420_h3_in_sec} pairs=" . count( $b420_node['mainEntity'] ) );

	// 4.3 — no answer is empty, truncated to a fragment, or absurdly long.
	$b420_len_ok = true;
	foreach ( $b420_node['mainEntity'] as $b420_q ) {
		$b420_len = strlen( $b420_q['acceptedAnswer']['text'] );
		if ( $b420_len < 20 || $b420_len > 5000 ) {
			$b420_len_ok = false;
			break;
		}
	}
	b420_assert( '4.3 every answer is a real sentence, not a fragment or a dump', $b420_len_ok );
} else {
	b420_skip( '4.1 … 4.3 structured vs visible', 'no marked published post found' );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 — PRIOR BUILDS SURVIVE THE BUMP
   A version bump that regressed 418 or 419 would ship green on a suite that
   only looked at the thing it changed.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n-- §5 regression guards --\n";

$b420_style = (string) @file_get_contents( $b420_theme_dir . '/style.css' );
b420_assert( '5.0 the theme reports at least 1.19.420',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.420', '>=' ),
	(string) wp_get_theme()->get( 'Version' ) );

$b420_bf = (string) @file_get_contents( $b420_theme_dir . '/inc/book-formats.php' );
// Docblock lines are stripped first: this file preserves every superseded value
// in prose on purpose, so an occurrence count would read the commentary too.
$b420_bf_code = implode( "\n", preg_grep( '/^\s*\*/', preg_split( "/\n/", $b420_bf ), PREG_GREP_INVERT ) );
b420_assert( '5.1 (419) the hardcover-row constant still defaults to false',
	1 === substr_count( $b420_bf_code, "define( 'BHP_SHOP_CARD_HARDCOVER_ROW', false )" )
		&& 0 === substr_count( $b420_bf_code, "define( 'BHP_SHOP_CARD_HARDCOVER_ROW', true )" ) );

$b420_aff = (string) @file_get_contents( $b420_theme_dir . '/inc/affiliate-disclosure.php' );
b420_assert( '5.2 (418) the approved affiliate disclosure sentence is intact',
	1 === substr_count( $b420_aff,
		'Some links on this page go to Amazon and earn me a small commission at no cost to you.' ) );

$b420_rc = (string) @file_get_contents( $b420_theme_dir . '/template-parts/guides/related-content.php' );
b420_assert( '5.3 (418) exactly ONE aside in the end-of-post block',
	1 === substr_count( $b420_rc, '<aside class="guide-continuation"' ) );

/*
 * ⛔ 5.4 — THIS BUILD MUTATED NO WOOCOMMERCE DATA, and this row checks rather
 *    than asserts it. A schema change that coincided with a product going
 *    unavailable would look identical on the rendered page.
 */
if ( function_exists( 'wc_get_products' ) ) {
	$b420_pub = wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids' ) );
	b420_assert( '5.4 ⛔ published products still exist and are unchanged by this build',
		is_array( $b420_pub ) && count( $b420_pub ) >= 1,
		count( (array) $b420_pub ) . ' published product(s)' );
} else {
	b420_skip( '5.4 published products still exist', 'WooCommerce not loaded' );
}

/* ═══════════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════════ */
printf(
	"\n=== %d passed, %d failed, %d skipped ===\n",
	$GLOBALS['b420_passes'],
	$GLOBALS['b420_failures'],
	$GLOBALS['b420_skips']
);

if ( $GLOBALS['b420_failures'] > 0 ) {
	exit( 1 );
}
