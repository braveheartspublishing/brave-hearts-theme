<?php
/**
 * Brave Hearts — THE HOMEPAGE HERO CTA MUST LAND ON A SECTION THAT EXISTS.
 *
 * CYCLE165-LD-HERO-CTA-FALLBACK (2026-08-19, theme 1.19.255).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-hero-cta-fallback.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * THE DEFECT THIS SUITE EXISTS TO MAKE IMPOSSIBLE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-19, item 82 (RELAYED through the Chief of Staff,
 * NOT witnessed first-hand by the agent that wrote this file), verbatim:
 *
 *   "The main CTA on the home page doesnt even click to to first free pages.
 *    Bad link."
 *   "The pages 1,2,3 arent even on the homepage!"
 *
 * MECHANISM, verified live on production 2026-08-19 by a headless-Chrome DOM
 * read (NOT by curl, which SiteGround's edge answers differently, and NOT from
 * a document): the homepage carried exactly one `href="#home-open-the-book"`
 * and ZERO `id="home-open-the-book"`. From 1.19.243 the hero button hard-coded
 * that fragment, while the section emitting it gated on three Mariana page
 * attachments that exist on staging2 and do not exist on production. Deploy #4
 * moved theme files. Media is not theme files.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * FOUR SECTIONS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   §1  THE RENDERED PAGE     — the hero CTA's fragment target is IN the same
 *                               document. Environment-independent: it does not
 *                               care which of the three candidates won.
 *   §2  THE WHOLE DOCUMENT    — no fragment link anywhere on the homepage
 *                               points at an id the homepage does not emit.
 *                               This is the assertion that would have caught
 *                               the original defect, and it is deliberately
 *                               generic rather than a check for one id.
 *   §3  THE NO-MEDIA BRANCH   — with the spread spec filtered to empty, the
 *                               gate closes AND the anchor falls back to a
 *                               fragment the real document actually contains.
 *   §4  THE INVARIANTS        — copy, event name and data-bhp-source are
 *                               byte-identical, and the old hard-coded call
 *                               has not come back.
 *
 * ⛔ NOTHING IS WRITTEN, AND NO MEDIA IS REMOVED. The no-media state in §3 is
 *    simulated with `add_filter( 'bhp_home_open_the_book_spreads', ... )` and
 *    the filter is removed again. Deleting a real attachment to test a missing
 *    attachment is how a QA step becomes a data-loss incident. No product,
 *    price, coupon, stock level, shipping setting, cart, order, post, page,
 *    option or attachment is touched by any line in this file.
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED RATHER THAN GLOSSED. It proves the
 *    anchor has a target in the served markup. It does NOT prove the browser
 *    actually scrolled, that the landing position clears the sticky header, or
 *    that the section is visually correct. Those were checked separately in a
 *    real browser at an asserted `window.innerWidth`.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⭐ SET THE TEMPLATE GLOBAL BEFORE ANY CALL, AND THE ORDER IS LOAD-BEARING.
 *
 * `bhp_cx_current_template_basename()` (inc/collection-gallery.php) reads
 * `$GLOBALS['bhp_cx_template_file']`, which is normally set by a
 * `template_include` filter that NEVER FIRES under WP-CLI. It memoises its
 * answer in a static on first call, and `bhp_cx_collection_gallery_config()`
 * memoises on top of that. Without this line every in-process call below would
 * evaluate against "not one of the six pages" and the §3 fallback assertions
 * would be testing a WP-CLI artefact instead of the homepage.
 */
$GLOBALS['bhp_cx_template_file'] = get_template_directory() . '/front-page.php';

$failures = array();

function bhp_hcf_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_hcf_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** Does this document emit `id="<name>"`? Quote-agnostic on purpose. */
function bhp_hcf_has_id( $html, $id ) {
	return 1 === preg_match( '/\bid=(["\'])' . preg_quote( $id, '/' ) . '\1/', $html );
}

echo "\n=== §0 — THE DOCUMENT ===\n";

$home = bhp_hcf_fetch( home_url( '/' ) );
bhp_hcf_assert( '' !== $home, '§0.1 the homepage renders (HTTP 200, non-empty body)', $failures );

if ( '' === $home ) {
	WP_CLI::error( 'Could not fetch the homepage; no further assertion is meaningful.' );
}

bhp_hcf_assert(
	function_exists( 'bhp_home_first_pages_anchor' )
		&& function_exists( 'bhp_home_open_the_book_resolved' )
		&& function_exists( 'bhp_home_open_the_book_spreads' ),
	'§0.2 the three shared helpers are loaded from inc/book-media.php',
	$failures
);

echo "\n=== §1 — THE HERO CTA LANDS ON A SECTION THAT EXISTS ===\n";

/*
 * Parse the href off the button by its data-bhp-source rather than by its
 * class or its copy. The source attribute is the analytics contract and is the
 * one identifier this build promised not to touch.
 */
$hero_href = '';
if ( preg_match( '/<a\b[^>]*data-bhp-source="home_hero_open_book"[^>]*>/', $home, $m_tag ) ) {
	if ( preg_match( '/\bhref="([^"]*)"/', $m_tag[0], $m_href ) ) {
		$hero_href = $m_href[1];
	}
}

bhp_hcf_assert( '' !== $hero_href, '§1.1 the hero open-the-book CTA is on the page and has an href', $failures );

bhp_hcf_assert(
	'' !== $hero_href && '#' === substr( $hero_href, 0, 1 ) && strlen( $hero_href ) > 1,
	'§1.2 the hero CTA href is a same-page fragment, not an off-page URL (Andrew rejected /complete-collection/ for this button in 1.19.242)',
	$failures
);

/*
 * ⭐ THE ASSERTION THAT WOULD HAVE CAUGHT THE PRODUCTION DEFECT, and it is
 *    deliberately environment-independent: it does not care WHICH of the three
 *    candidates won, only that the winner is really in this document.
 */
$hero_target = ltrim( $hero_href, '#' );
bhp_hcf_assert(
	'' !== $hero_target && bhp_hcf_has_id( $home, $hero_target ),
	"§1.3 the hero CTA's fragment target id (#{$hero_target}) EXISTS in the rendered homepage",
	$failures
);

/* The three candidates and nothing else. A fourth would be an undeclared
   destination and should fail loudly rather than pass quietly. */
bhp_hcf_assert(
	in_array(
		$hero_href,
		array( '#home-open-the-book', '#bhp-look-inside-complete_collection', '#home-sales-paths' ),
		true
	),
	'§1.4 the hero CTA href is one of the three declared first-pages candidates',
	$failures
);

/* In-process agreement: the gate and the button read the same predicate. */
$resolved_now = bhp_home_open_the_book_resolved();
bhp_hcf_assert(
	$resolved_now
		? ( '#home-open-the-book' === bhp_home_first_pages_anchor() )
		: ( '#home-open-the-book' !== bhp_home_first_pages_anchor() ),
	'§1.5 bhp_home_first_pages_anchor() agrees with bhp_home_open_the_book_resolved() (one predicate, two readers)',
	$failures
);

/* And the section really is absent when the gate is shut, present when open. */
bhp_hcf_assert(
	$resolved_now === bhp_hcf_has_id( $home, 'home-open-the-book' ),
	'§1.6 the gate predicate matches reality: id="home-open-the-book" is present IFF the spreads resolve',
	$failures
);

echo "\n=== §2 — NO DEAD FRAGMENT LINK ANYWHERE ON THE HOMEPAGE ===\n";

/*
 * Every `href="#something"` in the document must have a matching id. Two
 * exclusions, both stated rather than silently dropped:
 *   - `href="#"` alone is the quiz result CTA's JS-populated placeholder
 *     (`data-bhp-quiz-result-cta`), not a navigation target.
 *   - `#main` is the skip link, whose target is the <main> element.
 * Everything else is a promise to the visitor and is checked.
 */
preg_match_all( '/\bhref="#([A-Za-z][A-Za-z0-9_:.\-]*)"/', $home, $frag_m );
$fragments = array_values( array_unique( $frag_m[1] ) );

$dead = array();
foreach ( $fragments as $frag ) {
	if ( ! bhp_hcf_has_id( $home, $frag ) ) {
		$dead[] = '#' . $frag;
	}
}

bhp_hcf_assert(
	! empty( $fragments ),
	'§2.1 the homepage contains at least one fragment link to check (the scan is not vacuously green)',
	$failures
);

bhp_hcf_assert(
	empty( $dead ),
	'§2.2 EVERY fragment link on the homepage resolves to an id on the homepage'
		. ( empty( $dead ) ? '' : ' — DEAD: ' . implode( ', ', $dead ) ),
	$failures
);

echo '     scanned fragments: ' . implode( ', ', array_map( function ( $f ) { return '#' . $f; }, $fragments ) ) . "\n";

echo "\n=== §3 — THE NO-MEDIA BRANCH: THE FALLBACK IS REAL, NOT THEORETICAL ===\n";

/*
 * ⛔ SIMULATED, NEVER ENACTED. The filter below makes the spread spec empty for
 *    the duration of these four assertions and is removed immediately after.
 *    NO attachment is deleted, detached, renamed or re-slugged on any
 *    environment by this file.
 */
add_filter( 'bhp_home_open_the_book_spreads', '__return_empty_array' );

bhp_hcf_assert(
	array() === bhp_home_open_the_book_spreads(),
	'§3.1 with the three slugs filtered to nothing, the spreads resolve to an empty array',
	$failures
);

bhp_hcf_assert(
	false === bhp_home_open_the_book_resolved(),
	'§3.2 the gate predicate goes false, so the component would return before emitting its id',
	$failures
);

$fallback_anchor = bhp_home_first_pages_anchor();

bhp_hcf_assert(
	'#home-open-the-book' !== $fallback_anchor,
	'§3.3 the hero CTA STOPS pointing at #home-open-the-book once the section cannot render',
	$failures
);

bhp_hcf_assert(
	in_array( $fallback_anchor, array( '#bhp-look-inside-complete_collection', '#home-sales-paths' ), true ),
	"§3.4 the fallback is a declared candidate (got {$fallback_anchor})",
	$failures
);

/*
 * ⭐ THE ONE THAT MATTERS. The fallback is checked against the REAL rendered
 *    homepage, not against a list of strings this file wrote. A fallback that
 *    is itself a dead anchor is the same bug with a different id.
 */
bhp_hcf_assert(
	bhp_hcf_has_id( $home, ltrim( $fallback_anchor, '#' ) ),
	"§3.5 the fallback target ({$fallback_anchor}) EXISTS in the rendered homepage",
	$failures
);

remove_filter( 'bhp_home_open_the_book_spreads', '__return_empty_array' );

bhp_hcf_assert(
	! has_filter( 'bhp_home_open_the_book_spreads', '__return_empty_array' ),
	'§3.6 the simulation filter was removed again (the suite leaves no state behind)',
	$failures
);

/*
 * The floor, asserted against the document rather than trusted. `front-page.php`
 * renders the collection band unconditionally and passes only `cta`, so the
 * component's `section_id` default (`home-sales-paths`) applies. If that ever
 * stops being true, the last resort in `bhp_home_first_pages_anchor()` becomes
 * a dead anchor and this assertion is what says so.
 */
bhp_hcf_assert(
	bhp_hcf_has_id( $home, 'home-sales-paths' ),
	'§3.7 the ultimate floor id="home-sales-paths" is emitted unconditionally by the homepage',
	$failures
);

echo "\n=== §4 — THE INVARIANTS: COPY, TRACKING, AND NO REGRESSION ===\n";

/* Copy is Andrew's and is locked. This build changed a variable's value. */
bhp_hcf_assert(
	1 === substr_count( $home, 'Open the book. Read the first pages free' ),
	'§4.1 the CTA label is byte-identical and appears exactly once',
	$failures
);

bhp_hcf_assert(
	1 === substr_count( $home, 'data-bhp-source="home_hero_open_book"' ),
	'§4.2 data-bhp-source="home_hero_open_book" is unchanged and appears exactly once',
	$failures
);

bhp_hcf_assert(
	1 === preg_match(
		'/<a\b[^>]*data-bhp-event="contextual_cta_click"[^>]*data-bhp-source="home_hero_open_book"/',
		$home
	),
	'§4.3 the contextual_cta_click event is still on the same anchor (no GTM change needed)',
	$failures
);

$fp_src = (string) file_get_contents( get_template_directory() . '/front-page.php' );

bhp_hcf_assert(
	'' !== $fp_src && false !== strpos( $fp_src, 'bhp_home_first_pages_anchor' ),
	'§4.4 front-page.php computes the hero href through the shared helper',
	$failures
);

/*
 * The regression guard, and it is checked as EXECUTABLE code rather than as a
 * substring of the whole file: the superseded call is quoted verbatim inside
 * the docblock above it on purpose, so a naive `strpos` over the file would
 * fail forever. This strips block comments first.
 */
$fp_code = (string) preg_replace( '#/\*.*?\*/#s', '', $fp_src );
bhp_hcf_assert(
	false === strpos( $fp_code, "bhp_get_safe_link_url('#home-open-the-book'" ),
	'§4.5 the hard-coded bhp_get_safe_link_url(\'#home-open-the-book\', ...) call is GONE from executable code',
	$failures
);

$cmp_src = (string) file_get_contents(
	get_template_directory() . '/template-parts/components/home-open-the-book.php'
);
bhp_hcf_assert(
	'' !== $cmp_src && false !== strpos( $cmp_src, 'bhp_home_open_the_book_spreads()' ),
	'§4.6 the component gates on the SAME shared helper the hero reads (no second copy of the slug list)',
	$failures
);

$cmp_code = (string) preg_replace( '#/\*.*?\*/#s', '', $cmp_src );
bhp_hcf_assert(
	false === strpos( $cmp_code, "'mariana-trench-page-2'" ),
	'§4.7 the component no longer carries its own copy of the spread slugs',
	$failures
);

if ( ! empty( $failures ) ) {
	echo "FAILED ASSERTIONS:\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	WP_CLI::error( count( $failures ) . ' hero-CTA-fallback assertion(s) failed.' );
}
WP_CLI::success( 'Hero CTA fallback: the rendered anchor, the whole-document fragment scan, the no-media branch and the invariants all pass.' );
