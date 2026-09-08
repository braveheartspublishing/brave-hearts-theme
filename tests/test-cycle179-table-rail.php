<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.388 — WIDE TABLES SCROLL, AND THE BOOK RAIL STOPS SPLITTING A
 *      NUMBERED LIST ENTRY. `CYCLE179-LD-TABLE-RAIL-FIX`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The standing gates for the two defects `CYCLE179-LD-STAGING-RENDER-82-46`
 * measured in a browser at asserted `window.innerWidth`:
 *
 *   §5.1  staging 82 at 375 — the reading-level chart is 412px inside a 360px
 *         content box, `main.site-main` computes `overflow-x: clip`, and column
 *         5 ("Where a Charlotte and Henry book sits", 392…466) is unreachable.
 *   §5.3  staging 46 at 375 AND 1440 — `.bhp-book-rail` lands with
 *         `previousElementSibling` = "8. Adventures of Charlotte & Henry:
 *         Mount Everest" and `nextElementSibling` = that entry's first body
 *         paragraph. Title, commerce card, then the title's own text.
 *
 *   §0  versions
 *   §1  the table wrapper — wraps once, never twice, never a wp-block-table
 *   §2  the CSS that makes the wrapper scroll, and the clip it did NOT relax
 *   §3  "does this paragraph read as a numbered list entry's title"
 *   §4  the three-tier safe-ordinal search
 *   §5  the Magic Tree House SHAPE, end to end through `bhp_blog_rail_offset()`
 *   §6  the per-post override `_bhp_book_rail_position`
 *   §7  the additive `start` key did not disturb the existing contract
 *
 * ⛔ WHAT THIS SUITE CANNOT DO, SO IT IS NOT MISTAKEN FOR WHAT IT DOES. PHP
 *    does not evaluate CSS. §2 asserts that the shipped declarations EXIST IN
 *    THE ARTEFACT and are shaped correctly; whether column 5 is actually
 *    reachable by a thumb is proved in a real browser at an asserted
 *    `window.innerWidth`, and that measurement lives in the release record, not
 *    here. `CYCLE179-LD-354` is the standing lesson: a CSS rule that reads
 *    correctly can still compute to nothing.
 *
 * ⛔ IT WRITES ALMOST NOTHING. §6 creates ONE draft post, sets ONE meta key on
 *    it, and force-deletes that same post at the end of the section, guarded on
 *    the id it created. No option, no session, no cart, no order, no product,
 *    no price, no stock, no shipping setting. If the cleanup cannot confirm the
 *    post is gone, it SAYS SO rather than assuming.
 *
 * ⭐ INVOCATION, WITH `--url=` (`CYCLE179-LD-9`):
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-table-rail.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.388
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE.
 *
 * ⭐ Staging relays live through Google's SMTP relay, so any test that creates
 *    an order or moves one between statuses is an outbound-mail event. This
 *    suite has no mail path at all and includes the block anyway, so the next
 *    assertion added here does not have to remember.
 *
 * ⛔ NO ISO DATE APPEARS IN THIS BLOCK, AND THAT IS DELIBERATE. Two suites scan
 *    their OWN source for one and fail if they find it.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$GLOBALS['c388_pass']    = 0;
$GLOBALS['c388_fail']    = 0;
$GLOBALS['c388_skipped'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function c388_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['c388_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['c388_fail'];
	echo "  FAIL  {$label}\n";
}

/**
 * A check that could not be performed, recorded as not performed.
 *
 * @param string $label  What was not checked.
 * @param string $reason Why not.
 * @return void
 */
function c388_skip( $label, $reason ) {
	++$GLOBALS['c388_skipped'];
	echo "  SKIP  {$label}  --  {$reason}\n";
}

/**
 * Read a theme file, or '' when it is not there.
 *
 * @param string $rel Path relative to the theme root.
 * @return string
 */
function c388_theme_src( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Collapse whitespace so a CSS assertion is not defeated by reformatting.
 *
 * @param string $css Stylesheet source.
 * @return string
 */
function c388_flat( $css ) {
	return (string) preg_replace( '/\s+/', ' ', $css );
}

/**
 * Build an article of `$n` top-level paragraphs, with numbered titles at the
 * 1-based ordinals in `$titles` and a heading inserted after the ordinals in
 * `$headings_after`.
 *
 * ⭐ DECLARED UP HERE, NOT BESIDE ITS USE: `wp eval-file` runs this file's body
 *    inside a function, so a nested declaration does not exist until execution
 *    reaches it.
 *
 * @param int   $n              How many top-level paragraphs.
 * @param array $titles         1-based ordinals that are numbered titles.
 * @param array $headings_after 1-based ordinals followed by an `<h3>`.
 * @return string
 */
function c388_article( $n, $titles = array(), $headings_after = array() ) {
	$html = '';
	for ( $i = 1; $i <= $n; $i++ ) {
		if ( in_array( $i, $titles, true ) ) {
			$html .= '<p><strong>' . $i . '. Entry number ' . $i . '</strong></p>' . "\n";
		} else {
			$html .= '<p>Body paragraph ' . $i . ', which is ordinary prose.</p>' . "\n";
		}
		if ( in_array( $i, $headings_after, true ) ) {
			$html .= '<h3>A section heading after ' . $i . '</h3>' . "\n";
		}
	}
	return $html;
}

echo "\n=== CYCLE179-LD-TABLE-RAIL-FIX · theme 1.19.388 ===\n";

/* =========================================================================
 * §0 · VERSIONS
 * ====================================================================== */

echo "\n=== §0 · VERSIONS ===\n";

$c388_version = function_exists( 'wp_get_theme' ) ? (string) wp_get_theme()->get( 'Version' ) : '';
c388_assert(
	'' !== $c388_version && version_compare( $c388_version, '1.19.388', '>=' ),
	'0.1 the active theme is at least 1.19.388 (' . ( '' === $c388_version ? 'unknown' : $c388_version ) . ')'
);
c388_assert( function_exists( 'bhp_content_wrap_tables' ), '0.2 bhp_content_wrap_tables() exists' );
c388_assert( function_exists( 'bhp_blog_rail_safe_ordinal' ), '0.3 bhp_blog_rail_safe_ordinal() exists' );
c388_assert( function_exists( 'bhp_blog_rail_position_override' ), '0.4 bhp_blog_rail_position_override() exists' );

/* =========================================================================
 * §1 · THE TABLE WRAPPER
 * ====================================================================== */

echo "\n=== §1 · THE TABLE WRAPPER ===\n";

$c388_table = '<table>' . "\n"
	. '<thead><tr><th>Grade</th><th>Typical age</th><th>Typical Lexile range</th>'
	. '<th>What that looks like in a book</th><th>Where a Charlotte and Henry book sits</th></tr></thead>' . "\n"
	. '<tbody><tr><td>Second grade</td><td>7 to 8</td><td>420L to 650L</td><td>Short chapters</td>'
	. '<td>Mount Everest, 500L</td></tr></tbody>' . "\n"
	. '</table>';

$c388_raw     = '<p>Find the grade. Read across.</p>' . "\n\n" . $c388_table . "\n\n" . '<p>After the chart.</p>';
$c388_wrapped = bhp_content_wrap_tables( $c388_raw );

c388_assert(
	1 === substr_count( $c388_wrapped, '<div class="bhp-table-scroll">' ),
	'1.1 a raw <table> is wrapped exactly once'
);
c388_assert(
	1 === substr_count( $c388_wrapped, 'bhp-table-scroll__pane' ),
	'1.2 exactly one scroll pane is emitted'
);
c388_assert(
	false !== strpos( $c388_wrapped, $c388_table ),
	'1.3 the table itself is spliced in BYTE-IDENTICAL, not re-serialised'
);
c388_assert(
	false !== strpos( $c388_wrapped, '<thead><tr><th>Grade</th>' )
		&& false !== strpos( $c388_wrapped, 'Where a Charlotte and Henry book sits' ),
	'1.4 the header row and the fifth column survive the wrap'
);
c388_assert(
	bhp_content_wrap_tables( $c388_wrapped ) === $c388_wrapped,
	'1.5 ⭐ running the filter on its own output is a NO-OP — it never double-wraps'
);
c388_assert(
	1 === substr_count( bhp_content_wrap_tables( $c388_wrapped ), '<div class="bhp-table-scroll">' ),
	'1.6 and the wrapper count is still exactly 1 after a second pass'
);

$c388_figure = '<figure class="wp-block-table"><table><thead><tr><th>A</th><th>B</th></tr></thead>'
	. '<tbody><tr><td>1</td><td>2</td></tr></tbody></table><figcaption>Caption</figcaption></figure>';
$c388_fig_out = bhp_content_wrap_tables( '<p>Before.</p>' . $c388_figure . '<p>After.</p>' );

c388_assert(
	false === strpos( $c388_fig_out, 'bhp-table-scroll' ),
	'1.7 ⭐ a wp-block-table figure is NEVER wrapped — no second container inside it'
);
c388_assert(
	false !== strpos( $c388_fig_out, $c388_figure ),
	'1.8 and that figure comes back byte-identical'
);

$c388_mixed = '<p>One.</p>' . $c388_figure . '<p>Two.</p>' . $c388_table . '<p>Three.</p>';
$c388_mixed_out = bhp_content_wrap_tables( $c388_mixed );
c388_assert(
	1 === substr_count( $c388_mixed_out, '<div class="bhp-table-scroll">' )
		&& false !== strpos( $c388_mixed_out, $c388_figure ),
	'1.9 ⭐ a document with BOTH wraps only the bare table and leaves the figure alone'
);

c388_assert(
	2 === substr_count( bhp_content_wrap_tables( $c388_table . '<p>Gap.</p>' . $c388_table ), '<div class="bhp-table-scroll">' ),
	'1.10 two bare tables get two wrappers'
);

$c388_no_table = '<p>No table anywhere in this article.</p>';
c388_assert(
	bhp_content_wrap_tables( $c388_no_table ) === $c388_no_table,
	'1.11 content with no table is returned unchanged, byte for byte'
);
c388_assert(
	bhp_content_wrap_tables( '<div class="bhp-table-scroll">already</div>' . $c388_table )
		=== '<div class="bhp-table-scroll">already</div>' . $c388_table,
	'1.12 content that already carries bhp-table-scroll is left entirely alone'
);

c388_assert(
	false !== strpos( $c388_wrapped, 'role="region"' ) && false !== strpos( $c388_wrapped, 'tabindex="0"' )
		&& false !== strpos( $c388_wrapped, 'aria-label="' ),
	'1.13 the pane is a focusable labelled region, so a keyboard reaches column 5 too'
);
c388_assert(
	false === strpos( str_replace( $c388_table, '', bhp_content_wrap_tables( $c388_table ) ), '<p' ),
	'1.14 ⛔ the wrapper markup itself emits NO <p> — it runs at 13, but it must never be ABLE to move a paragraph ordinal'
);
c388_assert(
	13 === has_filter( 'the_content', 'bhp_content_table_scroll_filter' ),
	'1.15 the seam is on the_content at priority 13 — after do_shortcode (11), the band (11) and the rail (12)'
);
c388_assert(
	12 === has_filter( 'the_content', 'bhp_blog_inject_rail' ),
	'1.16 and the rail is still at 12, unmoved by this release'
);

/* =========================================================================
 * §2 · THE CSS — AND THE CLIP THAT WAS **NOT** RELAXED
 * ====================================================================== */

echo "\n=== §2 · THE CSS ===\n";

$c388_css      = c388_theme_src( 'style.css' );
$c388_css_flat = c388_flat( $c388_css );

c388_assert( '' !== $c388_css, '2.0 style.css is readable out of the deployed theme' );
c388_assert(
	false !== strpos( $c388_css_flat, '.entry-content .bhp-table-scroll__pane { overflow-x: auto;' ),
	'2.1 the pane scrolls horizontally'
);
c388_assert(
	false !== strpos( $c388_css_flat, '> table { width: 43rem' ),
	'2.2 ⭐ the table lays out at the width it has on a desktop (43rem vs a measured 691px at 1440) — without a fixed width a table shrinks to its 412px min-content and the pane scrolls to a cramped table'
);
/*
 * ⚠ THIS IS THE ASSERTION THAT ACTUALLY PROTECTS DESKTOP, AND A LOOSER FORM OF
 *   IT WAS WRITTEN FIRST AND REJECTED. "these three strings appear in this
 *   order" is trivially true of a stylesheet with thirteen 768px media queries
 *   in it and would have gone green on a table width shipped at top
 *   level — which is exactly the regression it exists to catch. So it finds the
 *   NEAREST PRECEDING `@media` and asserts THAT one is the narrow query.
 */
$c388_mc_at = strpos( $c388_css_flat, '> table { width: 43rem' );
$c388_at_at = ( false === $c388_mc_at ) ? false : strrpos( substr( $c388_css_flat, 0, $c388_mc_at ), '@media' );
c388_assert(
	false !== $c388_mc_at && false !== $c388_at_at
		&& 0 === strpos( substr( $c388_css_flat, $c388_at_at, 32 ), '@media (max-width: 768px)' ),
	'2.3 ⛔ the NEAREST ENCLOSING at-rule for the table width is @media (max-width: 768px) — desktop cannot reach it'
);
$c388_hint_at = strpos( $c388_css_flat, '.bhp-table-scroll__hint { display: block' );
c388_assert(
	false !== $c388_hint_at && false !== $c388_at_at && $c388_hint_at > $c388_at_at,
	'2.3b and the visible cue is inside that same narrow query'
);
$c388_pane_at = strpos( $c388_css_flat, '.entry-content .bhp-table-scroll__pane { overflow-x: auto;' );
c388_assert(
	false !== $c388_pane_at && false !== $c388_at_at && $c388_pane_at < $c388_at_at,
	'2.3c while `overflow-x: auto` itself is at top level — a no-op on a desktop box whose content already fits'
);
c388_assert(
	false !== strpos( $c388_css_flat, '.entry-content .bhp-table-scroll__hint { display: none; }' ),
	'2.4 the cue is hidden by default, so a desktop reader never sees it'
);
c388_assert(
	false !== strpos( $c388_css_flat, 'background-attachment: local' ),
	'2.5 the scroll fade is painted with background-attachment: local — no JS, correct on first paint'
);
c388_assert(
	false !== strpos( $c388_css_flat, '.entry-content figure.wp-block-table { overflow-x: auto;' ),
	'2.6 the block-editor table block scrolls on its own wrapper, since the filter deliberately does not nest one'
);
c388_assert(
	false !== strpos( $c388_css_flat, 'body:not(.home) .site-main { overflow: clip; }' ),
	'2.7 ⛔⛔ THE SITE-MAIN CLIP IS STILL THERE. This release fixed the table WITHOUT relaxing the one declaration that keeps 68 interior pages from growing a document scrollbar.'
);

/* =========================================================================
 * §3 · IS THIS PARAGRAPH A NUMBERED LIST ENTRY'S TITLE?
 * ====================================================================== */

echo "\n=== §3 · NUMBERED-TITLE DETECTION ===\n";

/**
 * The first paragraph record for a snippet.
 *
 * @param string $html Snippet.
 * @return array
 */
function c388_first_p( $html ) {
	$ps = bhp_blog_capture_band_paragraphs( $html );
	return isset( $ps[0] ) ? $ps[0] : array();
}

$c388_title_html = '<p><strong>8. Adventures of Charlotte &amp; Henry: Mount Everest</strong></p>';
c388_assert(
	bhp_blog_paragraph_is_numbered_title( $c388_title_html, c388_first_p( $c388_title_html ) ),
	'3.1 ⭐ THE ACTUAL DEFECT STRING — "8. Adventures of Charlotte & Henry: Mount Everest" reads as a title'
);

$c388_body_html = '<p>The second book in the series takes Charlotte and Henry to the highest place on Earth.</p>';
c388_assert(
	! bhp_blog_paragraph_is_numbered_title( $c388_body_html, c388_first_p( $c388_body_html ) ),
	'3.2 that entry\'s body paragraph does not'
);

$c388_paren = '<p>3) Dragon Masters (Branches/Scholastic)</p>';
c388_assert(
	bhp_blog_paragraph_is_numbered_title( $c388_paren, c388_first_p( $c388_paren ) ),
	'3.3 "3)" counts too — both forms are in circulation in hand-written round-ups'
);

$c388_bare = '<p>8.</p>';
c388_assert(
	! bhp_blog_paragraph_is_numbered_title( $c388_bare, c388_first_p( $c388_bare ) ),
	'3.4 a paragraph that is ONLY "8." is a stray, not a title'
);

$c388_year = '<p>2026. was the year the third book shipped.</p>';
c388_assert(
	! bhp_blog_paragraph_is_numbered_title( $c388_year, c388_first_p( $c388_year ) ),
	'3.5 a four-digit year is not an entry number (the pattern caps at three digits)'
);

$c388_prose = '<p>There are 8. Books in the list, I mean.</p>';
c388_assert(
	! bhp_blog_paragraph_is_numbered_title( $c388_prose, c388_first_p( $c388_prose ) ),
	'3.6 a number mid-sentence is not a title — the match is anchored at the start'
);

/* =========================================================================
 * §4 · THE THREE-TIER SAFE-ORDINAL SEARCH
 * ====================================================================== */

echo "\n=== §4 · SAFE ORDINAL ===\n";

/*
 * ⛔⛔ THE REGRESSION GUARD, AND IT IS FIRST BECAUSE IT IS THE ONE THAT MATTERS
 *     MOST. The first version of `bhp_blog_rail_safe_ordinal()` ran the
 *     three-tier search unconditionally, so the "prefer a block edge"
 *     preference walked the rail forward on EVERY post — including posts with
 *     no numbered list in them at all. `test-cycle169-blog-layout.php` §1.3b
 *     and §3.5c caught it on the same build (the rail reached 96.4% of the
 *     lopsided-roundup fixture). A guard must not change the case it was not
 *     written for.
 */
$c388_clean  = c388_article( 20, array(), array( 9 ) );
$c388_cleanp = bhp_blog_ask_top_paragraphs( $c388_clean );
c388_assert(
	13 === bhp_blog_rail_safe_ordinal( $c388_clean, $c388_cleanp, 13 ),
	'4.0a ⛔⛔ an article with NO numbered entries keeps its computed target EXACTLY — the guard does not move a rail it was not written to move'
);
$c388_farfrom  = c388_article( 20, array( 5 ), array( 9 ) );
$c388_farfromp = bhp_blog_ask_top_paragraphs( $c388_farfrom );
c388_assert(
	13 === bhp_blog_rail_safe_ordinal( $c388_farfrom, $c388_farfromp, 13 ),
	'4.0b ⛔ and so does a target that sits well clear of the numbered entry above it'
);

/*
 * Tier 1 — a block edge wins. 20 paragraphs, a numbered title at 5, a heading
 * after 9. The computed want lands ON the title; the search must walk to 9.
 */
$c388_t1  = c388_article( 20, array( 5 ), array( 9 ) );
$c388_t1p = bhp_blog_ask_top_paragraphs( $c388_t1 );
c388_assert( 20 === count( $c388_t1p ), '4.0 the fixture builder produces the paragraph count it claims' );
c388_assert(
	9 === bhp_blog_rail_safe_ordinal( $c388_t1, $c388_t1p, 5 ),
	'4.1 ⭐ TIER 1 — a want that lands on a numbered title walks forward to the BLOCK EDGE (heading after 9)'
);
c388_assert(
	5 !== bhp_blog_rail_safe_ordinal( $c388_t1, $c388_t1p, 5 ),
	'4.2 ⛔ and it is never the title ordinal itself — this is the defect, stated as an assertion'
);

/*
 * Tier 2 — no heading anywhere in the window, so "at least two paragraphs
 * after the title" decides.
 */
$c388_t2  = c388_article( 20, array( 5 ) );
$c388_t2p = bhp_blog_ask_top_paragraphs( $c388_t2 );
c388_assert(
	7 === bhp_blog_rail_safe_ordinal( $c388_t2, $c388_t2p, 5 ),
	'4.3 ⭐ TIER 2 — with no block edge in range, the rail sits at least ' . bhp_blog_ask_min_paragraph_gap() . ' paragraphs past the title'
);
c388_assert(
	6 !== bhp_blog_rail_safe_ordinal( $c388_t2, $c388_t2p, 5 ),
	'4.4 and NOT one paragraph past it, which would still land inside the entry\'s body'
);

/*
 * Tier 3 — alternating titles defeat both preferred tiers. The fallback is
 * the first boundary that is not itself a title, which is better than nothing
 * and is honestly labelled as a fallback.
 */
$c388_t3  = c388_article( 12, array( 5, 7, 9, 11 ) );
$c388_t3p = bhp_blog_ask_top_paragraphs( $c388_t3 );
c388_assert(
	6 === bhp_blog_rail_safe_ordinal( $c388_t3, $c388_t3p, 5 ),
	'4.5 TIER 3 — when neither preferred tier can match, the first non-title boundary is taken'
);

/* Bounds. */
$c388_short  = c388_article( 3 );
$c388_shortp = bhp_blog_ask_top_paragraphs( $c388_short );
c388_assert(
	null === bhp_blog_rail_safe_ordinal( $c388_short, $c388_shortp, 3 ),
	'4.6 ⛔ a want past the last usable paragraph returns null — the caller APPENDS rather than wedging'
);

$c388_all_titles  = c388_article( 10, array( 4, 5, 6, 7, 8, 9, 10 ) );
$c388_all_titlesp = bhp_blog_ask_top_paragraphs( $c388_all_titles );
c388_assert(
	null === bhp_blog_rail_safe_ordinal( $c388_all_titles, $c388_all_titlesp, 4 ),
	'4.7 ⛔ nothing sane in range returns null too — failing upward is what this file already paid for once'
);

$c388_far = bhp_blog_rail_safe_ordinal( $c388_t2, $c388_t2p, 5 );
c388_assert(
	null !== $c388_far && $c388_far <= count( $c388_t2p ) - 1,
	'4.8 the rail never takes the final paragraph — one always survives before the end-of-post capture'
);
c388_assert(
	null !== $c388_far && $c388_far - 5 <= bhp_blog_rail_max_advance(),
	'4.9 ⚠ the forward walk is BOUNDED (' . bhp_blog_rail_max_advance() . ') — an unbounded block-edge search would move the rail to ~95% of a post with one long final section'
);

/* The structural guard. */
c388_assert(
	bhp_blog_rail_boundary_ok( $c388_t2, $c388_t2p, 7 ),
	'4.10 a boundary at the end of a </p> is accepted'
);
$c388_after_h = "<p>one</p>\n<h3>Heading</h3>\n<p>two</p>";
$c388_fake    = array( array( 'start' => 0, 'end' => strpos( $c388_after_h, '</h3>' ) + 5, 'top' => true ) );
c388_assert(
	! bhp_blog_rail_boundary_ok( $c388_after_h, $c388_fake, 1 ),
	'4.11 ⛔ an offset sitting directly after a heading is REJECTED, even though the arithmetic cannot currently produce one'
);

/* =========================================================================
 * §5 · THE MAGIC TREE HOUSE SHAPE, END TO END
 * ====================================================================== */

echo "\n=== §5 · THE MAGIC TREE HOUSE SHAPE ===\n";

/*
 * ⭐ THE SHAPE OF STAGING POST 46, REBUILT RATHER THAN LOADED. The real body is
 *    a review draft that does not live in this repository and must not; what
 *    matters for placement is the SHAPE, and the shape is reproduced exactly:
 *    six bridge entries, a heading, entries 7 and 8 with five paragraphs each,
 *    a comment, two un-numbered paragraphs about the third book, the capture
 *    band, then the "If you want mine" heading.
 */
$c388_mth  = c388_article( 18, array( 1, 4, 7, 10, 13, 16 ), array( 18 ) );
$c388_mth .= '<p><strong>7. Adventures of Charlotte &amp; Henry: The Mariana Trench</strong></p>' . "\n";
$c388_mth .= '<p>This is the one I wrote for my niece Charlotte.</p>' . "\n";
$c388_mth .= '<p>They go to the deepest place on Earth.</p>' . "\n";
$c388_mth .= '<p>The science is real.</p>' . "\n";
$c388_mth .= '<p>Short chapters, like Magic Tree House.</p>' . "\n";
$c388_mth .= '<p><a href="#">Get it direct from me</a></p>' . "\n";
$c388_mth .= '<p><strong>8. Adventures of Charlotte &amp; Henry: Mount Everest</strong></p>' . "\n";
$c388_mth .= '<p>The second book takes them to the highest place on Earth.</p>' . "\n";
$c388_mth .= '<p>I have hiked that trail.</p>' . "\n";
$c388_mth .= '<p>When Charlotte reads about what it takes, she is getting something I believe in.</p>' . "\n";
$c388_mth .= '<p>Twelve short chapters, illustrated throughout.</p>' . "\n";
$c388_mth .= '<p><a href="#">Get it direct from me</a></p>' . "\n";
$c388_mth .= '<!-- book three, deliberately not numbered -->' . "\n";
$c388_mth .= '<p>There is a third one, and it is the reason the two above are a series.</p>' . "\n";
$c388_mth .= '<p><a href="#">Get The Amazon direct from me</a></p>' . "\n";
$c388_mth .= '<div class="bhp-capture-band"><span>FREE Chapter for Reluctant Readers</span></div>' . "\n";
$c388_mth .= '<h3>If you want mine, here is what buying them from me actually gets you</h3>' . "\n";
$c388_mth .= c388_article( 4 );

$c388_mthp   = bhp_blog_ask_top_paragraphs( $c388_mth );
$c388_mth_n  = count( $c388_mthp );
$c388_item8  = 0;
foreach ( $c388_mthp as $i => $p ) {
	if ( bhp_blog_paragraph_is_numbered_title( $c388_mth, $p )
		&& false !== strpos( substr( $c388_mth, (int) $p['start'], (int) $p['end'] - (int) $p['start'] ), 'Mount Everest' ) ) {
		$c388_item8 = $i + 1;
	}
}

c388_assert( $c388_item8 > 0, '5.0 entry 8\'s title paragraph is found in the fixture (ordinal ' . $c388_item8 . ' of ' . $c388_mth_n . ')' );

$c388_safe8 = bhp_blog_rail_safe_ordinal( $c388_mth, $c388_mthp, $c388_item8 );
c388_assert(
	null !== $c388_safe8 && $c388_safe8 !== $c388_item8,
	'5.1 ⭐⭐ THE DEFECT IS CLOSED — a want landing on entry 8\'s title does not stay there (moved to ' . var_export( $c388_safe8, true ) . ')'
);
c388_assert(
	null !== $c388_safe8 && $c388_safe8 > $c388_item8 + 1,
	'5.2 ⭐ and it is not one paragraph later either, which would still split the entry'
);
c388_assert(
	null !== $c388_safe8 && bhp_blog_rail_boundary_at_block_edge( $c388_mth, $c388_mthp, $c388_safe8 - 1 ),
	'5.3 ⭐ it lands on a BLOCK EDGE — the boundary a heading opens after, past the band'
);
c388_assert(
	null !== $c388_safe8 && ! bhp_blog_paragraph_is_numbered_title( $c388_mth, $c388_mthp[ $c388_safe8 - 1 ] ),
	'5.4 the paragraph the rail follows is not itself a numbered title'
);

/*
 * And through the real entry point, with the computed target rather than a
 * forced one, so the whole path is exercised.
 */
$c388_off = bhp_blog_rail_offset( $c388_mth );
if ( null === $c388_off ) {
	c388_skip( '5.5 bhp_blog_rail_offset() on the fixture', 'returned null (append), so there is no offset to test' );
} else {
	$c388_before = rtrim( substr( $c388_mth, 0, $c388_off ) );
	c388_assert(
		(bool) preg_match( '#</p>$#i', $c388_before ),
		'5.5 the offset from bhp_blog_rail_offset() sits at the end of a paragraph'
	);
	$c388_lands_on_title = false;
	foreach ( $c388_mthp as $p ) {
		if ( (int) $p['end'] === (int) $c388_off && bhp_blog_paragraph_is_numbered_title( $c388_mth, $p ) ) {
			$c388_lands_on_title = true;
		}
	}
	c388_assert(
		! $c388_lands_on_title,
		'5.6 ⭐⭐ and the computed placement never follows a numbered title on this shape'
	);
}

/* =========================================================================
 * §6 · THE PER-POST OVERRIDE `_bhp_book_rail_position`
 * ====================================================================== */

echo "\n=== §6 · THE PER-POST OVERRIDE ===\n";

c388_assert(
	null === bhp_blog_rail_position_override( 0 ),
	'6.0 no post means no override'
);

$c388_post_id = wp_insert_post(
	array(
		'post_title'   => 'CYCLE179 TABLE-RAIL FIXTURE — safe to delete',
		'post_content' => '<p>Fixture.</p>',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);

if ( is_wp_error( $c388_post_id ) || ! $c388_post_id ) {
	c388_skip( '6.1 – 6.8 the override reader', 'could not create the draft fixture post' );
} else {
	update_post_meta( $c388_post_id, '_bhp_book_rail_position', '12' );
	c388_assert( 12 === bhp_blog_rail_position_override( $c388_post_id ), '6.1 a positive integer comes back as an int' );

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', 'off' );
	c388_assert( 'off' === bhp_blog_rail_position_override( $c388_post_id ), '6.2 "off" comes back as the string off' );

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', '  OFF  ' );
	c388_assert( 'off' === bhp_blog_rail_position_override( $c388_post_id ), '6.3 and it is trimmed and case-insensitive' );

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', '0' );
	c388_assert( null === bhp_blog_rail_position_override( $c388_post_id ), '6.4 zero is not an ordinal and is ignored' );

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', 'somewhere in the middle' );
	c388_assert( null === bhp_blog_rail_position_override( $c388_post_id ), '6.5 an unparseable value is ignored, not guessed at' );

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', '-4' );
	c388_assert( null === bhp_blog_rail_position_override( $c388_post_id ), '6.6 a negative value is ignored' );

	/* The override actually steers the offset. */
	$c388_prev_post   = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$GLOBALS['post']  = get_post( $c388_post_id );

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', '4' );
	c388_assert(
		(int) $c388_t2p[3]['end'] === (int) bhp_blog_rail_offset( $c388_t2 ),
		'6.7 ⭐ an in-range ordinal wins over the computed placement, guards deliberately OFF — the editor already decided'
	);

	update_post_meta( $c388_post_id, '_bhp_book_rail_position', '9999' );
	c388_assert(
		(int) bhp_blog_rail_offset( $c388_t2 ) !== 0 && (int) $c388_t2p[3]['end'] !== (int) bhp_blog_rail_offset( $c388_t2 ),
		'6.8 ⚠ an OUT-OF-RANGE ordinal is IGNORED, not clamped — clamping would place the rail somewhere nobody asked for and report success'
	);

	if ( null === $c388_prev_post ) {
		unset( $GLOBALS['post'] );
	} else {
		$GLOBALS['post'] = $c388_prev_post;
	}

	/* Cleanup, and it reports rather than assumes. */
	wp_delete_post( $c388_post_id, true );
	c388_assert(
		null === get_post( $c388_post_id ),
		'6.9 the fixture post created by this suite is gone again (id ' . (int) $c388_post_id . ')'
	);
}

/*
 * ⭐ THE `off` LIMB IS ASSERTED ON SOURCE, AND THE LIMITATION IS STATED RATHER
 *    THAN HIDDEN. `bhp_blog_inject_rail()` returns early on four loop guards
 *    (`in_the_loop()`, `is_main_query()`, feed, REST) that `wp eval-file`
 *    cannot satisfy, so calling it here would exercise the guards and not the
 *    suppression. This asserts the check EXISTS and sits BEFORE the rail is
 *    built. The behaviour itself is proved in a browser and recorded there.
 */
$c388_rail_src = c388_theme_src( 'inc/blog-post-template.php' );
$c388_off_at   = strpos( $c388_rail_src, "'off' === bhp_blog_rail_position_override()" );
$c388_build_at = strpos( $c388_rail_src, '$rail = bhp_blog_rail_html();' );
c388_assert(
	false !== $c388_off_at && false !== $c388_build_at && $c388_off_at < $c388_build_at,
	'6.10 the "off" suppression is checked in bhp_blog_inject_rail() BEFORE the rail markup is built (source assertion, not behavioural)'
);

/* =========================================================================
 * §7 · THE ADDITIVE `start` KEY DID NOT DISTURB ANYTHING
 * ====================================================================== */

echo "\n=== §7 · THE PARAGRAPH SCANNER CONTRACT ===\n";

$c388_buried = '<p>top</p><ul><li><p>in a list</p></li></ul>';
$c388_bp     = bhp_blog_capture_band_paragraphs( $c388_buried );
c388_assert(
	2 === count( $c388_bp ) && true === $c388_bp[0]['top'] && false === $c388_bp[1]['top'],
	'7.1 ⛔ the `top` contract test-cycle169-blog-layout §2.4e asserts is UNCHANGED'
);
c388_assert(
	isset( $c388_bp[0]['start'] ) && 0 === (int) $c388_bp[0]['start'] && 10 === (int) $c388_bp[0]['end'],
	'7.2 `start` is the offset of the opening <p, and `end` is still just past </p>'
);
c388_assert(
	1 === count(
		array_filter(
			bhp_blog_capture_band_paragraphs( '<p>top</p><figure class="wp-block-embed"><p>caption-ish</p></figure>' ),
			function ( $p ) {
				return ! empty( $p['top'] );
			}
		)
	),
	'7.3 ⛔ and §2.4f (a figure buries paragraphs) still holds'
);
c388_assert(
	2 === count( bhp_blog_capture_band_paragraphs( '<p>one</p><picture><source></picture><pre>code</pre><p>two</p>' ) ),
	'7.4 ⛔ and the alternation-order guard (§2.4g) still holds'
);

$c388_stray = bhp_blog_capture_band_paragraphs( 'text</p><p>real</p>' );
c388_assert(
	/*
	 * ⚠ `array_key_exists`, NOT `isset`. `isset()` is FALSE for a key whose
	 *   value is null, so the first version of this assertion tested the
	 *   opposite of what its label claimed and went red against correct code.
	 *   Recorded rather than quietly swapped: the code was never wrong here.
	 */
	isset( $c388_stray[0] ) && array_key_exists( 'start', $c388_stray[0] ) && null === $c388_stray[0]['start'],
	'7.5 a stray closing </p> with no opener records a null start rather than a wrong offset'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */

echo "\n=== CYCLE179-LD-TABLE-RAIL-FIX RESULT ===\n";
echo "  passed:  {$GLOBALS['c388_pass']}\n";
echo "  failed:  {$GLOBALS['c388_fail']}\n";
echo "  skipped: {$GLOBALS['c388_skipped']}\n";

if ( $GLOBALS['c388_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
