<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.390 — THE STICKY-HEADER ANCHOR OFFSET, AND THE TABLE CUE'S EXIT.
 *      `CYCLE179-LD-BUILD-390`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Two defects, both carried into this build by the Chief of Staff's addendum
 * to 389, both originating in a production check at seal 1188.
 *
 *   1. `.site-header` is `position: sticky; top: 0` at z-index 100 and every
 *      in-page anchor target computed `scroll-margin-top: 0px`. VERIFIED ON
 *      PRODUCTION 2026-09-07 before a line was written: on
 *      `/blog/books-like-magic-tree-house/` the in-body link "Skip straight to
 *      the eight books" targets `<h2 id="the-eight-books">`, and the browser
 *      scrolls it to y=0 where the header paints over it.
 *   2. `.bhp-table-scroll__hint` ("Scroll for more") is painted under every
 *      wrapped table below 768px and had no exit — a reader already at the
 *      last column was still being told to scroll.
 *
 *   §0  versions and the artefacts exist
 *   §1  the offset variable, and that it is NOT the stale `--header-height`
 *   §2  the `scroll-margin-top` rule itself, in source AND in the minified
 *       artefact the site actually serves
 *   §3  the rules the fix deliberately did NOT touch
 *   §4  the table cue retires, and the specificity that makes it win
 *   §5  `assets/js/anchor-offset.js` — enqueued sitewide, and shaped right
 *
 * ⛔ WHAT THIS SUITE CANNOT DO, STATED SO IT IS NOT MISTAKEN FOR WHAT IT DOES.
 *    PHP does not lay out a page. Every assertion below proves a declaration
 *    EXISTS IN THE SHIPPED ARTEFACT and is shaped correctly. Whether the
 *    heading is actually visible after the jump is proved in a real browser by
 *    `document.elementFromPoint()` at the heading's centre, at an asserted
 *    `window.innerWidth` — that measurement lives in the release record, not
 *    here. `CYCLE179-LD-354` is the standing lesson: a CSS rule that reads
 *    correctly can still compute to nothing.
 *
 * ⛔ IT WRITES NOTHING. No post, no option, no meta, no session, no cart, no
 *    order, no product, no price, no stock, no shipping setting. It reads four
 *    files off disk and asks the enqueue registry one question.
 *
 * ⭐ INVOCATION, WITH `--url=` (`CYCLE179-LD-9`):
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-build-390.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.390
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE. It has no mail
 *    path at all and includes the block anyway, so the next assertion added
 *    here does not have to remember. Staging relays live through Google's SMTP
 *    relay and a suite is not allowed to be one send away from a customer.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$GLOBALS['c390_pass']    = 0;
$GLOBALS['c390_fail']    = 0;
$GLOBALS['c390_skipped'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function c390_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['c390_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['c390_fail'];
	echo "  FAIL  {$label}\n";
}

/**
 * A check that could not be performed, recorded as not performed.
 *
 * @param string $label  What was not checked.
 * @param string $reason Why not.
 * @return void
 */
function c390_skip( $label, $reason ) {
	++$GLOBALS['c390_skipped'];
	echo "  SKIP  {$label}  --  {$reason}\n";
}

/**
 * Read a theme file, or '' when it is not there.
 *
 * @param string $rel Path relative to the theme root.
 * @return string
 */
function c390_src( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Collapse whitespace so an assertion is about the declaration and not about
 * how someone happened to wrap the line.
 *
 * @param string $css Raw CSS.
 * @return string
 */
function c390_flat( $css ) {
	return (string) preg_replace( '/\s+/', ' ', $css );
}

$c390_css  = c390_src( 'style.css' );
$c390_min  = c390_src( 'style.min.css' );
$c390_js   = c390_src( 'assets/js/anchor-offset.js' );
$c390_fns  = c390_src( 'functions.php' );
$c390_fcss = c390_flat( $c390_css );
$c390_fmin = c390_flat( $c390_min );

/* =========================================================================
 * §0 · VERSIONS AND ARTEFACTS
 * ====================================================================== */
echo "\n§0 versions and artefacts\n";

$c390_version = wp_get_theme()->get( 'Version' );

c390_assert(
	version_compare( $c390_version, '1.19.390', '>=' ),
	"0.1 theme is at or past 1.19.390 (reads {$c390_version})"
);

c390_assert( '' !== $c390_css, '0.2 style.css is readable' );

/*
 * ⚠ The site does not serve style.css. From 1.19.201 `bhp_minified_style_src()`
 *   serves style.min.css and falls back silently, so a rule that exists only in
 *   the source is a rule no reader ever gets. Every §2 and §4 assertion is made
 *   against BOTH files for that reason.
 */
c390_assert( '' !== $c390_min, '0.3 style.min.css is readable — it is what the site serves' );

c390_assert( '' !== $c390_js, '0.4 assets/js/anchor-offset.js exists' );

/*
 * ⭐⭐ GENERALISED IN 1.19.391 (`CYCLE179-CX-BUILD-391`), AND THE ASSERTION
 *     GOT STRONGER RATHER THAN LOOSER.
 *
 * ⛔ THE SUPERSEDED LINE, PRESERVED:
 *      ~~false !== strpos( $c390_min, 'Version: 1.19.390' )~~
 *
 * ⛔ WHY IT HAD TO CHANGE: it pins a LITERAL VERSION, so it goes red on the
 *    very next bump even when the artefact is perfectly fresh. That is not a
 *    hypothetical — it fired on 1.19.391, whose `style.min.css` HAD been
 *    rebuilt, and this same suite file already carries a sibling failure of
 *    exactly this shape ("1.1 style.css Version: is 1.19.359"). An assertion
 *    that fires on a correct build teaches the next reader to ignore a red
 *    suite, which is how a real regression later gets waved through.
 *
 * ⭐ WHAT IT ALWAYS MEANT was "the minified artefact is not stale relative to
 *    its source". That is now tested directly, by comparing the two version
 *    lines to each other instead of to a number typed in a test — so it holds
 *    at every future version without anyone remembering to edit it.
 */
$c390_v_src = ( preg_match( '/^Version:\s*(\S+)/m', $c390_css, $c390_vm ) ) ? $c390_vm[1] : '';
$c390_v_min = ( preg_match( '/^\s*Version:\s*(\S+)/m', $c390_min, $c390_vn ) ) ? $c390_vn[1] : '';
c390_assert(
	'' !== $c390_v_src && $c390_v_src === $c390_v_min,
	'0.5 style.min.css was REBUILT for this version, not left at the previous one'
		. " (source {$c390_v_src}, minified {$c390_v_min})"
);

/* =========================================================================
 * §1 · THE OFFSET VARIABLE
 * ====================================================================== */
echo "\n§1 the offset variable\n";

c390_assert(
	false !== strpos( $c390_fcss, '--bhp-anchor-offset: 93px' ),
	'1.1 --bhp-anchor-offset is declared with the 93px static fallback'
);

c390_assert(
	false !== strpos( $c390_fmin, '--bhp-anchor-offset: 93px' )
		|| false !== strpos( $c390_fmin, '--bhp-anchor-offset:93px' ),
	'1.2 the fallback survives minification into the served artefact'
);

/*
 * ⛔ THE ASSERTION THAT MATTERS MOST IN THIS SUITE, AND IT IS A NEGATIVE.
 *
 *    `--header-height` is declared 70px while the header renders 92.8px at 375
 *    and 80.0px at 1440 (both measured; recorded at style.css :9027 and
 *    :12140). A fix written as `scroll-margin-top: var(--header-height)` reads
 *    correct, passes a naive "the rule exists" check, and STILL leaves 23px of
 *    the heading behind the nav on a phone. This asserts the offset rule does
 *    not reach for it.
 */
$c390_rule_at = strpos( $c390_fcss, '#main { scroll-margin-top: var(--bhp-anchor-offset' );
c390_assert(
	false !== $c390_rule_at,
	'1.3 the offset rule reads from --bhp-anchor-offset'
);

if ( false !== $c390_rule_at ) {
	$c390_rule_txt = substr( $c390_fcss, $c390_rule_at, 120 );
	c390_assert(
		false === strpos( $c390_rule_txt, '--header-height' ),
		'1.4 it does NOT read the stale 70px --header-height (the 23px trap)'
	);
} else {
	c390_skip( '1.4 the --header-height trap', 'the rule in 1.3 was not found to inspect' );
}

c390_assert(
	false !== strpos( $c390_fcss, 'var(--bhp-anchor-offset, 93px)' ),
	'1.5 the consuming rule carries its own 93px fallback, so a lost :root block still lands below the header'
);

/* =========================================================================
 * §2 · THE scroll-margin-top RULE
 * ====================================================================== */
echo "\n§2 the scroll-margin-top rule\n";

/*
 * The selector list, asserted member by member rather than as one string, so a
 * future reorder of the list does not fail a correct artefact.
 */
$c390_targets = array(
	'.entry-content h2[id]',
	'.entry-content h3[id]',
	'.entry-content h4[id]',
	'#main',
);

foreach ( $c390_targets as $c390_i => $c390_sel ) {
	c390_assert(
		false !== strpos( $c390_fcss, $c390_sel . ',' ) || false !== strpos( $c390_fcss, $c390_sel . ' {' ),
		sprintf( '2.%d `%s` is in the offset selector list', $c390_i + 1, $c390_sel )
	);
}

c390_assert(
	false !== strpos( $c390_fmin, '#main { scroll-margin-top: var(--bhp-anchor-offset' )
		|| false !== strpos( $c390_fmin, '#main{scroll-margin-top:var(--bhp-anchor-offset' ),
	'2.5 the rule survives minification into the served artefact'
);

c390_assert(
	false !== strpos( $c390_fmin, '.entry-content h2[id]' ),
	'2.6 the heading half of the selector survives minification too'
);

/*
 * ⭐ `.entry-content h2[id]` and not `.entry-content h2`. The attribute
 *   selector is load-bearing: a heading with no id is not an anchor target and
 *   giving it 93px of scroll margin would change nothing visible but would put
 *   the theme on the hook for every heading on the site.
 */
c390_assert(
	false === strpos( $c390_fcss, '.entry-content h2, .entry-content h3, .entry-content h4, .entry-content h5, .entry-content h6, #main {' ),
	'2.7 the selector is scoped to headings that CARRY an id, not to every heading'
);

/* =========================================================================
 * §3 · WHAT THE FIX DELIBERATELY DID NOT TOUCH
 * ====================================================================== */
echo "\n§3 what was deliberately left alone\n";

/*
 * ⛔ `scroll-padding-top` on the scroller would have caught every anchor target
 *    sitewide in one line — and would ADD to the existing
 *    `scroll-margin-top: calc(var(--header-height) + ...)` rules on
 *    `.homepage-section`, `.about-section`, `.books-section`,
 *    `.teachers-section`, `.contact-section`, `.guides-hub-section` and
 *    `.passport-status-page`, double-offsetting seven page families to fix one.
 *    Container padding and target margin both apply; neither overrides the
 *    other. This asserts the cheap-looking option was not taken.
 */
c390_assert(
	false === strpos( $c390_fcss, 'html { scroll-padding-top' )
		&& false === strpos( $c390_fcss, ':root { scroll-padding-top' ),
	'3.1 no sitewide scroll-padding-top was introduced (it would double-offset seven page families)'
);

$c390_existing = array(
	'.homepage-section',
	'.about-section',
	'.books-section',
	'.teachers-section',
	'.contact-section',
);

foreach ( $c390_existing as $c390_j => $c390_ex ) {
	c390_assert(
		false !== strpos( $c390_fcss, $c390_ex . ' { scroll-margin-top: calc(var(--header-height)' ),
		sprintf( '3.%d `%s` keeps its pre-existing --header-height offset, unchanged', $c390_j + 2, $c390_ex )
	);
}

/* =========================================================================
 * §4 · THE TABLE CUE RETIRES
 * ====================================================================== */
echo "\n§4 the table scroll cue retires\n";

c390_assert(
	false !== strpos( $c390_fcss, '.entry-content .bhp-table-scroll.is-scroll-end .bhp-table-scroll__hint { display: none; }' ),
	'4.1 the retire rule exists, on the WRAPPER class'
);

c390_assert(
	false !== strpos( $c390_fmin, '.bhp-table-scroll.is-scroll-end .bhp-table-scroll__hint' ),
	'4.2 it survives minification into the served artefact'
);

/*
 * ⭐ THE SPECIFICITY ARITHMETIC, ASSERTED RATHER THAN TRUSTED.
 *
 *   The rule it must beat is `.entry-content .bhp-table-scroll__hint {
 *   display: block }`, which lives INSIDE `@media (max-width: 768px)`. A media
 *   query contributes NOTHING to specificity, so the retire rule wins only
 *   because it is 0,4,0 against 0,2,0 — two classes deeper. If someone later
 *   "simplifies" it to `.bhp-table-scroll.is-scroll-end .hint` (0,3,0) it still
 *   wins; if they simplify it to a single class it silently stops working and
 *   the cue never goes away. This counts the class selectors.
 */
$c390_retire_sel = '.entry-content .bhp-table-scroll.is-scroll-end .bhp-table-scroll__hint';
c390_assert(
	4 === substr_count( $c390_retire_sel, '.' ) - 0
		&& 4 === preg_match_all( '/\.[a-z][a-z0-9_-]*/i', $c390_retire_sel ),
	'4.3 the retire selector carries FOUR classes — it outranks the 0,2,0 rule inside the media query regardless of source order'
);

c390_assert(
	false !== strpos( $c390_fcss, '.entry-content .bhp-table-scroll__hint { display: none; }' ),
	'4.4 the base hint rule is still display:none above 768px — the cue was not turned on everywhere'
);

$c390_hint_block_at = strpos( $c390_fcss, '.entry-content .bhp-table-scroll__hint { display: block' );
c390_assert(
	false !== $c390_hint_block_at,
	'4.5 the mobile hint is still painted below 768px — the cue was retired on scroll, not deleted'
);

/*
 * ⛔ THE SHADOW IS NOT RETIRED WITH THE SENTENCE, AND THE ASYMMETRY IS THE
 *    POINT. The right-edge shadow is painted by the `background-attachment:
 *    local, local, scroll, scroll` pair and already vanishes at the end of the
 *    pane with no JS — and returns when the reader scrolls back, which is
 *    correct for a shadow and wrong for a line of words.
 */
c390_assert(
	false !== strpos( $c390_fcss, 'background-attachment: local, local, scroll, scroll;' ),
	'4.6 the CSS-only right-edge shadow is untouched — it retires and returns on its own'
);

/* =========================================================================
 * §5 · THE SCRIPT
 * ====================================================================== */
echo "\n§5 assets/js/anchor-offset.js\n";

c390_assert(
	false !== strpos( $c390_fns, "wp_enqueue_script('bhp-anchor-offset'" ),
	'5.1 the script is enqueued'
);

/*
 * ⭐ SITEWIDE AND UNGATED, because `#main` is in `header.php` on every page —
 *   the skip link is not a blog concern. This asserts the enqueue is not
 *   wrapped in a conditional by finding it in the same unconditional run of
 *   enqueues as `bhp-nav`, which the theme already loads on every page.
 */
$c390_nav_at    = strpos( $c390_fns, "wp_enqueue_script('bhp-nav'" );
$c390_anchor_at = strpos( $c390_fns, "wp_enqueue_script('bhp-anchor-offset'" );
if ( false !== $c390_nav_at && false !== $c390_anchor_at ) {
	$c390_between = substr( $c390_fns, $c390_nav_at, $c390_anchor_at - $c390_nav_at );
	c390_assert(
		false === strpos( $c390_between, 'if ( ' ) && false === strpos( $c390_between, 'if(' ),
		'5.2 it sits in the same unconditional run as bhp-nav — no page gate between them'
	);
} else {
	c390_skip( '5.2 the enqueue is unconditional', 'bhp-nav or bhp-anchor-offset enqueue not located' );
}

c390_assert(
	false !== strpos( $c390_fns, "anchor-offset.js', [], \$theme_version, true)" ),
	'5.3 it is versioned on the theme version and loaded in the footer'
);

c390_assert(
	false !== strpos( $c390_js, "querySelector( '.site-header' )" ),
	'5.4 the script measures the real .site-header'
);

/*
 * ⚠ `offsetHeight` ALONE IS NOT THE OCCLUDED STRIP. With the WordPress admin
 *   bar the header's own `top` is 32px (46px below 783px), so a logged-in
 *   editor's heading would land 32px behind the bar. The measurement is the
 *   sum, and this asserts the second term is still there.
 */
c390_assert(
	false !== strpos( $c390_js, 'offsetHeight' ) && false !== strpos( $c390_js, "getComputedStyle( header ).top" ),
	'5.5 it measures offsetHeight PLUS the computed top — the admin bar is part of the occluded strip'
);

c390_assert(
	false !== strpos( $c390_js, "setProperty( '--bhp-anchor-offset'" ),
	'5.6 it writes the measured value onto the same variable the CSS reads'
);

c390_assert(
	false !== strpos( $c390_js, "addEventListener(\n\t\t'resize'" )
		|| false !== strpos( c390_flat( $c390_js ), "addEventListener( 'resize'" ),
	'5.7 it re-measures on resize — the header is 93px at 375 and 80px at 1440'
);

c390_assert(
	false !== strpos( $c390_js, 'is-scroll-end' ),
	'5.8 it sets the table cue class'
);

/*
 * ⭐ THE NO-OVERFLOW CASE. A narrow table on a wide phone never scrolls, so a
 *   scroll listener alone would leave "Scroll for more" under a table that has
 *   nothing more. The retire check runs once on wiring for exactly that.
 */
c390_assert(
	false !== strpos( $c390_js, 'scrollWidth - pane.clientWidth <= END_SLOP' ),
	'5.9 a pane that does not overflow retires the cue immediately, without waiting for a scroll that never comes'
);

/*
 * ⛔ `arguments.callee` IS A TypeError UNDER `use strict`, WHICH THIS FILE OPTS
 *    INTO. The first draft of this script used it to unbind the scroll
 *    listener and would have thrown on the first swipe. Recorded rather than
 *    quietly swapped, and asserted so it cannot come back.
 */
c390_assert(
	false !== strpos( $c390_js, "'use strict'" ) && false === strpos( $c390_js, 'arguments.callee' ),
	'5.10 strict mode is on and arguments.callee is absent (it throws under strict mode)'
);

c390_assert(
	false === strpos( $c390_js, 'jQuery' ) && false === strpos( $c390_js, '$(' ),
	'5.11 no jQuery dependency — the enqueue declares none'
);

/*
 * ⭐ `ceil`, NOT `round`. MEASURED on staging at 1.19.390 before the line
 *   existed: `round` reserved 80px against a header bottom at 80.0 and the
 *   heading landed at 79.67 — 0.33px behind the header, and 0.01px at 375.
 *   The rounding error is always in the same direction, and the direction that
 *   hurts is under-reserving. Asserted so a later tidy-up cannot put `round`
 *   back and re-open a defect nobody would see in a screenshot.
 */
c390_assert(
	false !== strpos( $c390_js, 'Math.ceil( height + top )' )
		&& false === strpos( $c390_js, 'Math.round( height + top )' ),
	'5.12 the measurement rounds UP — it can never reserve less than the real header'
);

/*
 * ⭐⭐ THE OBSERVER, AND IT IS NOT BELT-AND-BRACES. OBSERVED on staging at
 *    1.19.390: a 1440 -> 375 viewport change fired NO `resize` event, the
 *    header grew 80 -> 93, and the variable stayed at 80 — the precise 13px
 *    under-reservation this file exists to prevent. A synthetic `resize`
 *    corrected it instantly, proving the listener was right and its TRIGGER
 *    was wrong. A web font finishing, the admin bar appearing, or a container
 *    query re-laying the nav are the same class and none must fire `resize`.
 */
c390_assert(
	false !== strpos( $c390_js, 'ResizeObserver' )
		&& false !== strpos( $c390_js, '.observe( headerEl )' ),
	'5.13 a ResizeObserver watches the header box — the measurement follows the header, not a window event'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */

echo "\n=== CYCLE179-LD-BUILD-390 RESULT ===\n";
echo "  passed:  {$GLOBALS['c390_pass']}\n";
echo "  failed:  {$GLOBALS['c390_fail']}\n";
echo "  skipped: {$GLOBALS['c390_skipped']}\n";

if ( $GLOBALS['c390_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
