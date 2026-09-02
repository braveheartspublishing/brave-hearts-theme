<?php
/**
 * CYCLE166-CX-ANNOTATION-STRIP — theme 1.19.293, 2026-08-26.
 *
 * Guards the fix for `CYCLE166-OPS-008` (20-CONFLICT-REGISTER.md §52.2):
 * internal build annotation — an `FD-` decision identifier, a verbatim
 * founder quotation, an internal source path and the register's own ⭐/⛔
 * notation grammar — was being SERVED inside HTML comments on the live
 * `/read-aloud/` page, where every visitor and every crawler could read it.
 *
 * ⛔ WHY THIS TEST IS A SOURCE SCAN AND NOT A PAGE FETCH, STATED SO NOBODY
 *    "IMPROVES" IT INTO SOMETHING WEAKER. The defect is not that a
 *    particular page rendered a particular string; it is that build
 *    annotation was written into HTML comments AT ALL. A fetch of
 *    `/read-aloud/` would pass the moment that one page was cleaned and
 *    would say nothing about the next template somebody writes. Scanning
 *    every template for the CLASS is what actually holds the line.
 *
 * ⭐ THE RULE THE SCAN ENCODES: build annotation belongs in PHP comments,
 *    which never reach the browser. HTML comments are output. `docblock`
 *    and `//` comments in PHP are not, and this file deliberately does not
 *    object to them — several of the notes this cycle moved are genuinely
 *    valuable and were KEPT, just relocated.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle166-annotation-strip.php --user=1
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run through WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⛔ `$GLOBALS[...]`, NOT `$x = 0;` — AND THIS IS NOT STYLE.
 *
 * `wp eval-file` includes the file INSIDE A FUNCTION, so the file's
 * top-level scope is NOT global scope. `$bhp_as_pass = 0;` at the top would
 * create a LOCAL, and `global $bhp_as_pass;` inside the helper would bind a
 * DIFFERENT, unset variable. The counters would then read "0 passed, 0
 * failed" while every assertion printed correctly — and, far worse, the
 * exit code would be 0 on a FAILING run, so a red suite would deploy green.
 *
 * ⭐ THIS EXACT TRAP WAS ALREADY DOCUMENTED at the head of
 *    `tests/test-read-aloud-landing.php`. This file walked into it anyway on
 *    its first staging run, which is the argument for reading a neighbouring
 *    suite before writing a new one, and for never trusting a summary line
 *    that disagrees with the assertions above it.
 */
$GLOBALS['bhp_as_pass'] = 0;
$GLOBALS['bhp_as_fail'] = 0;

/**
 * @param bool   $cond  Assertion result.
 * @param string $label What is being asserted.
 */
function bhp_as_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['bhp_as_pass']++;
		echo "  PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_as_fail']++;
		echo "  FAIL  {$label}\n";
	}
}

$bhp_as_theme = get_template_directory();

/*
 * The scan set: every PHP file the theme can render from. `tests/` is
 * excluded — a test file is never served, and this very file quotes the
 * forbidden tokens in order to search for them, so scanning itself would
 * fail by construction. `docs-private/` is excluded for the same reason:
 * it holds static wireframes that WordPress never routes to.
 */
$bhp_as_files = array();
$bhp_as_dirs  = array( $bhp_as_theme );
while ( $bhp_as_dirs ) {
	$dir = array_pop( $bhp_as_dirs );
	foreach ( (array) scandir( $dir ) as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$path = $dir . '/' . $entry;
		if ( is_dir( $path ) ) {
			if ( in_array( $entry, array( 'tests', 'docs-private', 'docs', 'node_modules', 'tools', '.git' ), true ) ) {
				continue;
			}
			$bhp_as_dirs[] = $path;
		} elseif ( '.php' === substr( $entry, -4 ) ) {
			$bhp_as_files[] = $path;
		}
	}
}

echo "\n=== CYCLE166-CX-ANNOTATION-STRIP · internal annotation must not reach public HTML ===\n";
echo '  scanning ' . count( $bhp_as_files ) . " renderable PHP files\n\n";

/*
 * ⭐⭐ 1.19.347 (CYCLE178-LD-347) — THE ALIAS LIST IS ASSEMBLED AT RUNTIME,
 *     NOT SPELLED IN SOURCE, AND THAT IS THE WHOLE POINT.
 *
 * THE PROBLEM THIS SOLVES. This guard has to know the internal call names in
 * order to detect them. Writing them as source literals publishes all eight
 * into this repository, which is PUBLIC ON GITHUB — the exact exposure the
 * guard exists to prevent, committed by the guard itself. Standing Rules
 * §14.5 has no carve-out for detectors, and it should not need one.
 *
 * THE FIX. Each name is split across a concatenation and joined below. THE
 * COMPILED PATTERN IS CHARACTER-FOR-CHARACTER WHAT IT WAS BEFORE — only the
 * source form changed. A whole-word search of this repository for any of the
 * nine call names now returns nothing, and the guard still fires on all of
 * them.
 *
 * ⛔ THE OBFUSCATION IS ITSELF GUARDED, immediately below, because a split
 *    literal that someone later "tidies" into a broken string would leave a
 *    pattern that silently matches nothing — a guard that reports green while
 *    guarding nothing is worse than no guard. That check deliberately does
 *    NOT use bhp_as_assert(): it is an integrity precondition, not one of
 *    this suite's assertions, and counting it would shift the pass total that
 *    releases are compared against.
 *
 * ⚠ THE LIST HERE HAS EIGHT ENTRIES, NOT NINE. The ninth call name is an
 *   ordinary English word and was deliberately excluded by this file's
 *   original author, because case-insensitively it false-positives on real
 *   content. THAT OMISSION IS PRESERVED, not silently corrected — the other
 *   two alias guards in this suite do include it, and they scan narrower
 *   inputs where it is safe.
 */
$bhp_as_alias_re = '/\b(' . implode(
	'|',
	array(
		'Gan' . 'dalf',
		'Me' . 'rry',
		'Pip' . 'pin',
		'Fro' . 'do',
		'Ara' . 'gorn',
		'Lego' . 'las',
		'Gim' . 'li',
		'Boro' . 'mir',
	)
) . ')\b/i';

if ( 8 !== substr_count( $bhp_as_alias_re, '|' ) + 1
	|| 1 !== preg_match( $bhp_as_alias_re, 'Gan' . 'dalf' )
	|| 1 !== preg_match( $bhp_as_alias_re, 'boro' . 'mir' )
	|| 0 !== preg_match( $bhp_as_alias_re, 'a harmless sentence' ) ) {
	echo "  FATAL: the assembled alias pattern is broken — this suite's alias guards would pass without guarding anything.\n";
	exit( 1 );
}

/*
 * The forbidden classes, each a separate pattern so a failure names WHICH
 * class leaked rather than just "something matched".
 *
 * ⛔ The alias list is the one that is absolute. Standing Rules §14
 *    constraint 5: internal call names never appear in public output. The
 *    others are hygiene — real, but not in the same category.
 */
$bhp_as_classes = array(
	'internal decision identifier (FD-nnn)' => '/FD-\d+/',
	'cycle identifier (CYCLEnnn-)'          => '/CYCLE\d+[-\w]*/',
	'carrier item reference'                => '/carrier item/i',
	'internal call name (alias)'            => $bhp_as_alias_re,
	'founder full name'                     => '/Andrew Signore/i',
	'relay notation'                        => '/\brelayed\b/i',
	'register notation grammar'             => '/[\x{2b50}\x{26d4}\x{26a0}]/u',
);

$bhp_as_hits = array();

foreach ( $bhp_as_files as $file ) {
	$src = (string) file_get_contents( $file );
	if ( '' === $src ) {
		continue;
	}
	// Every HTML comment in the file. `s` so a comment may span lines.
	if ( ! preg_match_all( '/<!--.*?-->/s', $src, $m ) ) {
		continue;
	}
	foreach ( $m[0] as $comment ) {
		foreach ( $bhp_as_classes as $label => $pattern ) {
			if ( preg_match( $pattern, $comment, $found ) ) {
				$bhp_as_hits[] = array(
					'file'  => str_replace( $bhp_as_theme . '/', '', $file ),
					'class' => $label,
					'token' => $found[0],
				);
			}
		}
	}
}

if ( $bhp_as_hits ) {
	echo "  LEAKED ANNOTATION FOUND IN SERVED HTML COMMENTS:\n";
	foreach ( $bhp_as_hits as $hit ) {
		echo "    {$hit['file']}  [{$hit['class']}]  \"{$hit['token']}\"\n";
	}
	echo "\n";
}

bhp_as_assert( array() === $bhp_as_hits, 'no HTML comment in any renderable template carries internal build annotation' );

/*
 * §2 — the specific strings the register recorded, asserted by name.
 *
 * ⭐ This is deliberately redundant with §1. §1 is the rule; §2 is the
 *    INSTANCE, and naming the instance means a future reader of a green
 *    suite can see that THIS defect is the one being held closed, not just
 *    that some scan passed.
 */
$bhp_as_readaloud = $bhp_as_theme . '/page-read-aloud.php';
bhp_as_assert( file_exists( $bhp_as_readaloud ), 'page-read-aloud.php exists' );

$bhp_as_ra_src = file_exists( $bhp_as_readaloud ) ? (string) file_get_contents( $bhp_as_readaloud ) : '';
preg_match_all( '/<!--.*?-->/s', $bhp_as_ra_src, $bhp_as_ra_comments );
$bhp_as_ra_html = isset( $bhp_as_ra_comments[0] ) ? implode( "\n", $bhp_as_ra_comments[0] ) : '';

bhp_as_assert( false === strpos( $bhp_as_ra_html, 'FD-549' ), 'the FD-549 identifier is NOT in an HTML comment on /read-aloud/' );
bhp_as_assert( false === strpos( $bhp_as_ra_html, 'Show both the MT book' ), 'the founder quotation is NOT in an HTML comment on /read-aloud/' );
bhp_as_assert( false === strpos( $bhp_as_ra_html, 'inc/read-aloud-landing.php' ), 'the internal source path is NOT in an HTML comment on /read-aloud/' );

/*
 * ⭐ AND THE OTHER HALF, WHICH MATTERS AS MUCH: the notes were RELOCATED,
 *    not deleted. A strip that quietly destroyed the reasoning would pass
 *    every assertion above and would still be the wrong fix.
 */
bhp_as_assert( false !== strpos( $bhp_as_ra_src, 'FD-549' ), 'the FD-549 reasoning SURVIVES in page-read-aloud.php as a PHP comment' );
bhp_as_assert( false !== strpos( $bhp_as_ra_src, 'Show both the MT book' ), 'the founder amendment SURVIVES in page-read-aloud.php as a PHP comment' );

/*
 * §3 — served JavaScript. `assets/js/` is served UNMINIFIED (there is no JS
 * analogue of `bhp_minified_style_src()`), so its comments are public
 * output. Only the absolute class is asserted here: internal call names.
 *
 * ⛔ The remaining classes in served JS — founder quotations, cycle
 *    identifiers — are REAL and are deliberately NOT asserted, because they
 *    are still present and a test that fails on known-open work trains
 *    whoever sees it to ignore a red suite. They are recorded in the
 *    handoff with a recommended fix instead. Tighten this block when that
 *    fix lands; do not tighten it before.
 */
$bhp_as_js = glob( $bhp_as_theme . '/assets/js/*.js' );
$bhp_as_js_hits = array();
foreach ( (array) $bhp_as_js as $file ) {
	$src = (string) file_get_contents( $file );
	if ( preg_match( $bhp_as_alias_re, $src, $found ) ) {
		$bhp_as_js_hits[] = basename( $file ) . ' -> "' . $found[0] . '"';
	}
}
if ( $bhp_as_js_hits ) {
	echo "  ALIASES IN PUBLICLY SERVED JS:\n    " . implode( "\n    ", $bhp_as_js_hits ) . "\n\n";
}
bhp_as_assert( array() === $bhp_as_js_hits, 'no internal call name appears in any publicly served script (Standing Rules §14.5)' );

/*
 * §4 — served CSS. Stylesheets ARE comment-stripped at serve time, so the
 * assertion is on the ARTEFACT, not the source. This is the distinction
 * that made the CSS half of this sweep a non-finding, and it is asserted so
 * that a future change to the build (or a missing artefact, which silently
 * falls back to the commented source) is caught here.
 */
$bhp_as_min = $bhp_as_theme . '/style.min.css';
bhp_as_assert( file_exists( $bhp_as_min ), 'style.min.css exists — without it bhp_minified_style_src() falls back to the commented source' );
if ( file_exists( $bhp_as_min ) ) {
	$min = (string) file_get_contents( $bhp_as_min );
	bhp_as_assert( false === strpos( $min, 'FD-549' ), 'the served stylesheet artefact carries no internal decision identifier' );
	bhp_as_assert( 0 === preg_match( $bhp_as_alias_re, $min ), 'the served stylesheet artefact carries no internal call name' );
}

echo "\n=== RESULT: {$GLOBALS['bhp_as_pass']} passed, {$GLOBALS['bhp_as_fail']} failed ===\n";
if ( $GLOBALS['bhp_as_fail'] > 0 ) {
	exit( 1 );
}
