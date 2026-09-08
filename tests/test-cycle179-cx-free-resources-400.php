<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * `/free-resources/` PHONE LAYOUT SUITE — theme 1.19.400,
 * `CYCLE179-CX-BUILD-400-FREE-RESOURCES`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PREPARED OFF-TREE, NOT INSTALLED. Destination once locks 398 and 399 release:
 *   brave-hearts-theme/tests/test-cycle179-cx-free-resources-400.php
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-cx-free-resources-400.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHY THIS SUITE EXISTS: Andrew, from an iPhone 16 Pro on staging,
 *    "This isnt very centered and it looks bad". Four separate causes sat
 *    behind that one sentence, and three of them are the kind that no existing
 *    test in this repository could see.
 * ---------------------------------------------------------------------------
 *
 * ⛔⛔ ONE — A USER-AGENT DEFAULT, NOT A LAYOUT BUG. The jump bar `ul` sets
 *     `margin: 0` and `padding-block`, and never touches `padding-inline`. The
 *     UA default `padding-inline-start: 40px` survives, so `justify-content:
 *     center` centers each row inside a box that is itself 40px off. MEASURED
 *     live before the fix: row delta 40.33 / 40.00 / 39.99 / 39.99 / 39.67 at
 *     375 / 390 / 402 / 430 / 1280. §1 pins the reset in BOTH artefacts.
 *
 * ⛔⛔ TWO — A CROP THAT CROPS NOTHING. The shipped rule pins `aspect-ratio:
 *     4 / 5` and its own comment claims it shows "the head of the page". 4/5 is
 *     0.8000 and US Letter is 0.7727, so `cover` removes 3.5 percent and the
 *     reader gets the whole sheet at thumbnail size. The comment was wrong and
 *     the test that would have caught it did not exist. §3 asserts the ratio is
 *     landscape and that 4/5 is gone.
 *
 * ⛔⛔ THREE — A SPECIFICITY LOSS THAT FAILS SILENTLY. The eyebrow is governed
 *     by `body:not(.home) .component-heading__eyebrow`, specificity (0,2,1). A
 *     natural override written as `.free-resources-hero .component-heading__
 *     eyebrow` is (0,2,0) and LOSES, with no error anywhere. That override was
 *     written, injected into the live page, and measured as having no effect
 *     before this was found. §5 asserts the winning selector by its literal
 *     text, because that is the only thing that distinguishes it from the
 *     version that does nothing.
 *
 * ⛔ FOUR — a rule can be correct in `style.css` and absent from the file the
 *    browser actually loads. `style.min.css` is the enqueued artefact. EVERY
 *    section below asserts against BOTH. `tests/test-style-minification.php`
 *    separately proves the minified file is not stale; this suite does not
 *    duplicate that and does not replace it.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is CSS source level. It cannot see layout, wrapping, tap targets or
 * where anything sits on a phone. It proves the declarations SHIP; it does not
 * prove they RENDER. The rendered claim carries browser evidence at an asserted
 * `window.innerWidth`, produced by `400-measure.js` beside this file, and is
 * NOT inferred from a PASS below.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, and it leaves no filter registered. It reads two files.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$GLOBALS['bhp_fr400_pass'] = 0;
$GLOBALS['bhp_fr400_fail'] = 0;

/*
 * ⛔ THE COUNTERS ARE GLOBALS ON PURPOSE. A file-top `$pass = 0;` under
 *    `wp eval-file` is a LOCAL, and a helper that says `global $pass;` then
 *    reads a different variable reports zero failures forever. A suite that
 *    cannot fail is worse than no suite. Same discipline as the 1.19.301 hub
 *    suite, which documents the same trap.
 */
function bhp_fr400_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_fr400_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_fr400_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_fr400_head( $t ) {
	echo "\n--- {$t} ---\n";
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ An unreadable file makes every `strpos` below return false, which reads as
 *    a clean sweep of failures with no cause named. Stop instead.
 */
bhp_fr400_head( '§0 PRECONDITIONS' );

$src_path = get_template_directory() . '/style.css';
$min_path = get_template_directory() . '/style.min.css';

$src = is_readable( $src_path ) ? (string) file_get_contents( $src_path ) : '';
$min = is_readable( $min_path ) ? (string) file_get_contents( $min_path ) : '';

bhp_fr400_ok( '§0.1 style.css is readable and non-trivial', strlen( $src ) > 100000, strlen( $src ) . ' bytes' );
bhp_fr400_ok( '§0.2 style.min.css is readable and non-trivial', strlen( $min ) > 50000, strlen( $min ) . ' bytes' );

if ( '' === $src || '' === $min ) {
	echo "\nABORT: a stylesheet could not be read. No further assertion would mean anything.\n";
	return;
}

bhp_fr400_ok(
	'§0.3 the theme version carries this build',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.400', '>=' ),
	(string) wp_get_theme()->get( 'Version' )
);

/*
 * ⭐ Whitespace-insensitive matching. The minifier collapses spaces and drops
 *    the final semicolon in a block, so a literal needle that passes against
 *    style.css can fail against style.min.css for no reason a reader would
 *    accept. Both files are squeezed before every comparison below.
 */
$squeeze = static function ( $s ) {
	return preg_replace( '/\s+/', '', (string) $s );
};
$src_z = $squeeze( $src );
$min_z = $squeeze( $min );

/*
 * ⛔⛔ COMMENT-STRIPPED COPIES, AND THE REASON IS A FALSE PASS AND FOUR FALSE
 *     FAILS THIS SUITE ACTUALLY PRODUCED ON ITS FIRST REAL RUN (1.19.400).
 *
 *     This file was written OFF-TREE and had never been executed. Four of its
 *     assertions failed against a build that was correct, because they matched
 *     TEXT INSIDE CSS COMMENTS rather than declarations:
 *
 *       §3.2 searched the preview rule for `aspect-ratio: 4 / 5` and found it
 *            in the SUPERSESSION COMMENT that documents its removal.
 *       §4.4 looked for `.free-resource-card__cta{margin-top:auto` and missed
 *            it because a comment sits between the selector and the
 *            declaration in `style.css`.
 *       §6.1 tested a SUBSTRING that the correctly-scoped selector also ends
 *            with, so a properly scoped rule read as an unscoped one.
 *       §4.3 allowed leading whitespace, so it matched the rule INSIDE the
 *            phone media query it was written to prove was absent OUTSIDE it.
 *
 * ⭐ THE GENERAL LESSON: an unrun test is source, not evidence. Three of those
 *    four would have been "fixed" by weakening the build instead of the
 *    assertion, which is how a suite teaches the wrong lesson forever.
 *    Structural assertions below run against `$src_nc` (comments removed).
 */
$strip_comments = static function ( $s ) {
	return preg_replace( '#/\*.*?\*/#s', '', (string) $s );
};
$src_nc  = $strip_comments( $src );
$min_nc  = $strip_comments( $min );
$src_ncz = $squeeze( $src_nc );
$min_ncz = $squeeze( $min_nc );

$in_both_nc = static function ( $needle ) use ( $src_ncz, $min_ncz, $squeeze ) {
	$n = $squeeze( $needle );
	return false !== strpos( $src_ncz, $n ) && false !== strpos( $min_ncz, $n );
};

$in_both = static function ( $needle ) use ( $src_z, $min_z, $squeeze ) {
	$n = $squeeze( $needle );
	return false !== strpos( $src_z, $n ) && false !== strpos( $min_z, $n );
};

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE JUMP BAR IS CENTERED BECAUSE THE UA PADDING IS RESET.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr400_head( '§1 JUMP BAR CENTERING' );

bhp_fr400_ok(
	'§1.1 ⛔ .free-resources-jump__list resets padding-inline, in BOTH artefacts',
	$in_both( '.free-resources-jump__list{padding-inline:0' )
	|| $in_both( 'padding-inline:0' ) && false !== strpos( $src_z, '.free-resources-jump__list{padding-inline:0' )
);

/*
 * ⛔ THE REGRESSION THIS PINS: someone tidies the rule back to shorthand
 *    `padding: 16px 0` and the UA 40px returns, because `padding-block` alone
 *    does not touch the inline axis. The bar goes 20px off center again and
 *    nothing else in this repository notices.
 */
bhp_fr400_ok(
	'§1.2 the shipped padding-block rule still exists (the reset added to it, not replacing it)',
	false !== strpos( $src_z, 'padding-block:var(--space-4)' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE TAB STRIP DOES NOT WRAP RAGGED ON A PHONE.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ Equal 1fr cells, so the split is identical at every phone width. Before
 *    this, four labels of four widths wrapped 2+2 at 375 and 390 but 3+1 at
 *    402 and 430 — and 402 is the width Andrew was looking at.
 */
bhp_fr400_head( '§2 TAB STRIP OVERFLOW' );

bhp_fr400_ok(
	'§2.1 ⛔ the phone breakpoint gives the list an equal two-column grid, in BOTH artefacts',
	$in_both( 'grid-template-columns:repeat(2,minmax(0,1fr))' )
);

bhp_fr400_ok(
	'§2.2 the rule is inside a max-width phone query, not sitewide',
	1 === preg_match(
		'/@media[^{]*max-width:\s*600px[^{]*\{(?:[^{}]|\{[^{}]*\})*\.free-resources-jump__list\s*\{[^}]*grid-template-columns/s',
		$src
	)
);

bhp_fr400_ok(
	'§2.3 the labels fill their cell, so the tap target is the half-row not the ink',
	$in_both( '.free-resources-jump__lista{display:block;text-align:center' )
);

/*
 * ⛔ THE DECISION THIS PINS, so a later lane does not quietly reverse it: the
 *    shipped component comment rejected horizontal scrolling by name, as a
 *    hidden-overflow affordance "that nobody discovers". The brief for 1.19.400
 *    allowed either that or a 2x2 grid. The grid was taken. If a future build
 *    wants the scroller it must delete this assertion deliberately.
 */
bhp_fr400_ok(
	'§2.4 ⛔ the bar is NOT converted to a horizontal scroller',
	false === strpos( $src_z, '.free-resources-jump__list{overflow-x:auto' )
	&& false === strpos( $src_z, '.free-resources-jump__list{display:grid;overflow-x:scroll' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · THE PRINTABLE PREVIEW IS A THUMBNAIL, NOT A PHOTOGRAPH OF A PAGE.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr400_head( '§3 PRINTABLE PREVIEW CROP' );

bhp_fr400_ok(
	'§3.1 ⛔ the preview frame is landscape 3/2, in BOTH artefacts',
	$in_both( '.free-resource-card__previewimg{' ) && $in_both( 'aspect-ratio:3/2' )
);

/*
 * ⛔ THE WHOLE DEFECT IN ONE ASSERTION. 4/5 is 0.8000; a US Letter sheet is
 *    0.7727. `cover` on a 4/5 frame removes 3.5 percent of the page and shows
 *    the reader everything, tiny. Any ratio at or below about 1.0 reintroduces
 *    it, so the portrait value is banned by name rather than by measurement.
 */
bhp_fr400_ok(
	'§3.2 ⛔ the portrait 4/5 frame is gone from the preview DECLARATION (comments stripped — the supersession note names 4/5 on purpose)',
	1 !== preg_match( '/\.free-resource-card__preview\s+img\s*\{[^}]*aspect-ratio:\s*4\s*\/\s*5/s', $src_nc )
);

/*
 * ⭐ EXACTLY ONE aspect-ratio DECLARATION FOR THIS ELEMENT. 1.19.400 changed
 *    the SHIPPED rule in place rather than overriding it from the foot of the
 *    file. An override would also have rendered correctly, by source order,
 *    while leaving a dead `4 / 5` behind — and the dead declaration IS the
 *    defect, because deleting what looks like an additive tail block silently
 *    restores it.
 */
bhp_fr400_ok(
	'§3.2b ⛔ the preview rule declares aspect-ratio exactly ONCE',
	1 === preg_match_all( '/\.free-resource-card__preview\s+img\s*\{[^}]*?aspect-ratio/s', $src_nc )
);

bhp_fr400_ok(
	'§3.3 ⭐ object-fit cover and object-position top survive — the crop must keep the title band',
	1 === preg_match( '/\.free-resource-card__preview\s+img\s*\{[^}]*object-fit:\s*cover/s', $src )
	&& 1 === preg_match( '/\.free-resource-card__preview\s+img\s*\{[^}]*object-position:\s*top/s', $src )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE CARD IS CENTERED ON PHONES AND LEFT ALIGNED ON DESKTOP.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr400_head( '§4 CARD ALIGNMENT' );

bhp_fr400_ok(
	'§4.1 ⛔ .free-resource-card is centered inside a max-width:768px query, in BOTH artefacts',
	$in_both( '.free-resource-card{text-align:center' )
	&& 1 === preg_match(
		'/@media[^{]*max-width:\s*768px[^{]*\{(?:[^{}]|\{[^{}]*\})*\.free-resource-card\s*\{[^}]*text-align:\s*center/s',
		$src
	)
);

bhp_fr400_ok(
	'§4.2 the CTA stretches on phones, so its two side insets are equal by construction',
	$in_both( '.free-resource-card__cta{align-self:stretch' )
);

/*
 * ⛔ DESKTOP MUST NOT BE CENTERED. In the three column grid each card has a
 *    left edge to hang from and the shipped left alignment is correct there.
 *    A sitewide `text-align: center` on this card is a regression, not a tidy.
 */
bhp_fr400_ok(
	'§4.3 ⛔ no unscoped centering leaked outside the phone query (anchored at column 0 — an indented match IS the in-query rule)',
	1 !== preg_match( '/^\.free-resource-card\s*\{[^}]*text-align:\s*center/m', $src_nc )
);

bhp_fr400_ok(
	'§4.4 the shipped flex column and the CTA margin-top:auto baseline are untouched',
	1 === preg_match( '/^\.free-resource-card\s*\{[^}]*flex-direction:\s*column/ms', $src_nc )
	&& false !== strpos( $src_ncz, '.free-resource-card__cta{margin-top:auto' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · THE HERO EYEBROW OVERRIDE ACTUALLY WINS.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS IS THE ASSERTION THAT MATTERS MOST IN THIS FILE, and the one a
 *     reader is most likely to "simplify". The governing shipped rule is
 *     `body:not(.home) .component-heading__eyebrow`, specificity (0,2,1).
 *     Dropping the `body:not(.home)` prefix from the override below leaves a
 *     (0,2,0) selector that loses the cascade and changes nothing at all, with
 *     no error and no failing test anywhere else in the repository. That exact
 *     mistake was made during this build and was caught only by reading the
 *     computed style in a live browser.
 */
bhp_fr400_head( '§5 HERO EYEBROW' );

bhp_fr400_ok(
	'§5.1 ⛔ the override carries the body:not(.home) prefix that beats (0,2,1)',
	$in_both( 'body:not(.home).free-resources-hero.component-heading__eyebrow{' )
);

bhp_fr400_ok(
	'§5.2 it sets both the size and the tracking — either alone still wraps',
	1 === preg_match(
		'/body:not\(\.home\)\s+\.free-resources-hero\s+\.component-heading__eyebrow\s*\{[^}]*font-size:\s*12px[^}]*letter-spacing:\s*\.06em/s',
		$src_nc
	)
);

/*
 * ⛔⛔ §5.2b IS THE ASSERTION THIS BUILD LEARNED THE HARD WAY, AND IT IS THE
 *     MOST IMPORTANT ONE IN THIS SECTION.
 *
 *     The candidate prepared off-tree used **11px / 0.1em**. It rendered
 *     correctly, it held one line at 375, and it BROKE A HOUSE RULE:
 *     `tests/test-cro-iterate5.php` §5.3 asserts that NO eyebrow or label rule
 *     in `style.css` may declare a font-size under 12px, with exactly ONE
 *     exclusion — the homepage hero eyebrow, excluded BY FOUNDER DECISION
 *     (FD-460 / FD-469) and tracked as the open item `CYCLE165-LD-50`.
 *
 *     The 11px build INSTALLED CLEANLY and was caught only by running the full
 *     suite and diffing fail lines against the previous build. Nothing in this
 *     file would have caught it, which is why the floor is now pinned HERE too
 *     rather than left to another lane's suite to notice.
 *
 * ⭐ THE RESOLUTION, MEASURED LIVE AT AN ASSERTED 375 (column 295.3px):
 *       12.48px / 0.20em -> 2 lines (the shipped wrap)
 *       11.00px / 0.10em -> 1 line, ink 275.4   ⛔ BREAKS THE 12px FLOOR
 *       12.00px / 0.10em -> 2 lines, second line 8.3px  ⛔ A WIDOW ("9" alone)
 *       12.00px / 0.08em -> 1 line, ink 291.5   ⚠ only 3.8px of headroom
 *       12.00px / 0.06em -> 1 line, ink 282.6   ⭐ TAKEN, 12.7px of headroom
 *
 *     The trade-off therefore MOVED: it is no longer "smaller than the house
 *     floor" but "less tracking on this one hero", 0.2em -> 0.06em. That is a
 *     taste call, it is recorded in the build report, and it is reversible.
 */
bhp_fr400_ok(
	'§5.2b ⛔ the eyebrow override respects the >=12px house floor (test-cro-iterate5 §5.3, FD-460/469)',
	1 !== preg_match(
		'/\.free-resources-hero\s+\.component-heading__eyebrow\s*\{[^}]*font-size:\s*(?:[0-9]|1[01])(?:\.[0-9]+)?px/s',
		$src_nc
	)
);

bhp_fr400_ok(
	'§5.3 ⛔ it is scoped to this hero and does not restyle the sitewide eyebrow',
	1 !== preg_match( '/^\s*\.component-heading__eyebrow\s*\{[^}]*font-size:\s*11px/m', $src )
);

bhp_fr400_ok(
	'§5.4 it is inside a phone query, so desktop keeps the 12.48px / 0.2em eyebrow',
	1 === preg_match(
		'/@media[^{]*max-width:\s*430px[^{]*\{(?:[^{}]|\{[^{}]*\})*body:not\(\.home\)\s+\.free-resources-hero/s',
		$src
	)
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · NOTHING LEAKED ONTO A PAGE THAT WAS ALREADY CORRECT.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ MEASURED live at 390 before this build: /read-aloud/, /school-read-alouds/
 *    and /blog/ carry no wrapping eyebrow, no padded centered list and no
 *    portrait cover thumbnail. They needed no change and must receive none.
 *    Every selector this build adds is scoped to a `free-resource` class, so
 *    the guard is that no bare component selector was touched.
 */
bhp_fr400_head( '§6 NO SITEWIDE LEAK' );

/*
 * ⛔⛔ THIS GUARD USED TO BE A SUBSTRING TEST AND IT FIRED ON A CORRECT BUILD.
 *     It searched the squeezed source for `.component-heading__eyebrow{font-
 *     size:11px`. The properly scoped selector squeezes to
 *     `body:not(.home).free-resources-hero.component-heading__eyebrow{font-
 *     size:11px` — which CONTAINS that substring. A correctly scoped rule was
 *     therefore indistinguishable from the unscoped rule the guard exists to
 *     forbid, and the guard reported the scoped one as a leak.
 *
 * ⭐ THE FIX IS TO ANCHOR ON THE SELECTOR, NOT ON A TAIL OF IT. A sitewide
 *    restyle is a rule whose selector STARTS at the component class with no
 *    scoping compound in front of it. `^` at column 0 with comments stripped
 *    is what distinguishes the two, and it is the same anchor §4.3 needed.
 */
foreach ( array(
	'.component-heading__eyebrow',
	'.card__eyebrow',
) as $forbidden_sel ) {
	bhp_fr400_ok(
		"§6.1 ⛔ sitewide component not restyled at the root selector: {$forbidden_sel}",
		1 !== preg_match(
			'/^' . preg_quote( $forbidden_sel, '/' ) . '\s*(?:,[^{]*)?\{[^}]*font-size:\s*(?:[0-9]|1[01])(?:\.[0-9]+)?px/m',
			$src_nc
		)
	);
}

/*
 * ⭐ AND THE POSITIVE HALF: the override that DOES exist must still carry a
 *    scoping compound in front of the component class. Without this, deleting
 *    the scope would pass §6.1 (no root-anchored rule) while restyling the
 *    component everywhere through a different selector shape.
 */
bhp_fr400_ok(
	'§6.2 ⭐ the only eyebrow size override on this page is scoped by .free-resources-hero',
	1 === preg_match(
		'/body:not\(\.home\)\s+\.free-resources-hero\s+\.component-heading__eyebrow\s*\{/s',
		$src_nc
	)
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · THIS SUITE MUTATED NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_fr400_head( '§7 NO SIDE EFFECTS' );

bhp_fr400_ok(
	'§7.1 ⛔ the theme version on disk is unchanged by running tests',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.400', '>=' )
);

bhp_fr400_ok(
	'§7.2 the hub template still renders its jump bar and its card class',
	false !== strpos( (string) file_get_contents( get_template_directory() . '/page-free-resources.php' ), 'free-resources-jump__list' )
	&& false !== strpos( (string) file_get_contents( get_template_directory() . '/page-free-resources.php' ), 'free-resource-card__preview' )
);


/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · THE PREVIEW DERIVATIVES, AND THE ALT TEXT THAT DESCRIBES THEM.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE FINDING THIS SECTION EXISTS FOR IS NOT A SHARPNESS FINDING. The
 *     shipped `mariana-trench-coloring-pages-preview.jpg` DISPLAYED THE
 *     SENTENCE "Four words from the story". That wording was corrected in the
 *     PDF (rebuilt 2026-09-02, it now reads "Four words from the coloring
 *     book's quote pages") and in the card copy — and the correction never
 *     reached the PICTURE. The wrong words sat on `/free-resources/` rendered
 *     as an image, where NO string search and NO test in this repository could
 *     ever have found them. Verified first-hand at 1.19.400 build time by
 *     reading both images and by extracting the PDF's own page-1 text.
 *
 * ⭐ THE GENERAL LESSON, WHICH OUTLIVES THIS INSTANCE: copy baked into a raster
 *    asset is copy that no grep governs. The only durable defences are (a)
 *    render previews FROM the shipped source at build time, and (b) assert the
 *    intrinsic geometry, which is what changes when someone re-renders. §8.1
 *    and §8.2 do (b). They cannot read the words, and they do not pretend to.
 *
 * ⛔ §8.3 IS THE ONE THAT PROTECTS THE READER. The frame moved to a 3:2 TOP
 *    BAND — the top 51.52 percent of the sheet. The SHIPPED alt strings named
 *    content that band does not contain ("a checklist of titles", "two
 *    drawings to color at the foot of the page"). Shipping them unchanged would
 *    have put a described-but-absent claim in front of every screen-reader user
 *    on the page. The replacements name the crop AS a crop.
 */
bhp_fr400_head( '§8 PREVIEW DERIVATIVES AND ALT TEXT' );

$fr400_stems = array(
	'mariana-trench-coloring-pages',
	'stop-breathe-think-act-poster',
	'backyard-expedition',
	'reading-ladder',
	'how-did-she-do-reading-it',
);

$fr400_missing  = array();
$fr400_portrait = array();
foreach ( $fr400_stems as $stem ) {
	foreach ( array( 'jpg', 'webp' ) as $ext ) {
		$p = get_template_directory() . '/assets/images/free-resources/' . $stem . '-preview.' . $ext;
		if ( ! is_readable( $p ) ) {
			$fr400_missing[] = $stem . '.' . $ext;
			continue;
		}
		$d = @getimagesize( $p );
		if ( ! is_array( $d ) || empty( $d[0] ) || empty( $d[1] ) || $d[0] <= $d[1] ) {
			$fr400_portrait[] = $stem . '.' . $ext . ' (' . ( is_array( $d ) ? $d[0] . 'x' . $d[1] : 'unreadable' ) . ')';
		}
	}
}

bhp_fr400_ok(
	'§8.1 all ten preview derivatives exist and are readable',
	empty( $fr400_missing ),
	implode( ', ', $fr400_missing )
);

/*
 * ⛔ LANDSCAPE IS THE ASSERTION, NOT AN EXACT PIXEL SIZE. A fixed 1800x1200
 *    would go stale the first time somebody re-exports at a different size and
 *    would then be "corrected" downward by whoever trusted it — the same
 *    failure mode `docs/RUNBOOK.md` records for its own entry-count numbers.
 *    What must never come back is a PORTRAIT source under a 3:2 frame, because
 *    that is the whole-sheet thumbnail this build removed.
 */
bhp_fr400_ok(
	'§8.2 ⛔ every derivative is LANDSCAPE — a portrait source under a 3/2 frame is the defect this build removed',
	empty( $fr400_portrait ),
	implode( ' | ', $fr400_portrait )
);

/*
 * ⛔ THE RESOLVER READS INTRINSIC WIDTH AND HEIGHT FROM THE JPG and refuses to
 *    emit a preview at all when either is zero. So a jpg that stops being
 *    readable does not degrade the card, it DELETES the picture — silently.
 */
$fr400_rows = function_exists( 'bhp_free_resources_downloads' ) ? bhp_free_resources_downloads() : array();

bhp_fr400_ok(
	'§8.3 the resolver still emits a preview for every row, with non-zero intrinsic dimensions and non-empty alt',
	! empty( $fr400_rows ) && count( array_filter(
		$fr400_rows,
		static function ( $r ) {
			return ! empty( $r['preview'] )
				&& ! empty( $r['preview']['width'] )
				&& ! empty( $r['preview']['height'] )
				&& '' !== trim( (string) $r['preview']['alt'] );
		}
	) ) === count( $fr400_rows ),
	'rows ' . count( $fr400_rows )
);

/*
 * ⛔ THE ALT STRINGS MUST NAME THE CROP. Every replacement opens "The top of
 *    the ...". This is asserted on the RENDERED value the resolver hands the
 *    template, not on the source literal, because the template is what a
 *    screen reader meets.
 */
$fr400_uncropped = array();
foreach ( $fr400_rows as $r ) {
	$alt = isset( $r['preview']['alt'] ) ? (string) $r['preview']['alt'] : '';
	if ( 0 !== stripos( $alt, 'The top of the' ) ) {
		$fr400_uncropped[] = substr( $alt, 0, 50 );
	}
}
bhp_fr400_ok(
	'§8.4 ⛔ every preview_alt names the crop as a crop ("The top of the ...")',
	empty( $fr400_uncropped ),
	implode( ' | ', $fr400_uncropped )
);

/*
 * ⛔ THE SUPERSEDED WHOLE-PAGE PHRASES ARE BANNED BY NAME. Each of these
 *    described something the 3:2 band does not contain. If a later lane
 *    restores a full-page frame it must restore these strings DELIBERATELY,
 *    by deleting this assertion — not by pasting old copy back over new art.
 */
$fr400_alt_all = '';
foreach ( $fr400_rows as $r ) {
	$fr400_alt_all .= ' ' . ( isset( $r['preview']['alt'] ) ? (string) $r['preview']['alt'] : '' );
}
foreach ( array(
	'at the foot of the page',
	'followed by a checklist of titles',
	'each with a short checklist of books',
	'spot ten things',
	'Page one of the file',
) as $fr400_stale ) {
	bhp_fr400_ok(
		"§8.5 ⛔ whole-page alt phrase is gone: \"{$fr400_stale}\"",
		false === stripos( $fr400_alt_all, $fr400_stale )
	);
}

/*
 * ⚠️ WHAT THIS SECTION CANNOT DO, STATED RATHER THAN IMPLIED: nothing here
 *    reads the words INSIDE the image. If someone re-renders the preview from
 *    a stale PDF, every assertion above still passes. The defence against that
 *    is the render provenance recorded in the build report, not this file.
 */

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · THE PAIR PAGE H1 IS CENTERED — AND THE CAUSE WAS SPECIFICITY.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ `text-align` WAS NEVER THE PROBLEM AND A FUTURE READER WILL ASSUME IT
 *     WAS. The h1 computed `text-align: center` the whole time. What it did NOT
 *     compute was `margin-inline: auto`:
 *
 *       .bhp-landing h1           (0,1,1)  margin: 0    <- PLUGIN
 *       .bhp-pair-landing__title  (0,1,0)  margin: 0 auto 0.5rem
 *
 *     The plugin's selector carries a class AND an element, so it wins, and
 *     `auto` never survives. With `max-width: 20ch` the box then hugged the
 *     left edge of the 1180px hero inner while the text sat centered INSIDE
 *     that narrow box. MEASURED live at 1.19.399, gapL vs gapR:
 *       375 -> 16.00 vs 36.68   402 -> 16.07 vs 60.24   1280 -> 74.33 vs 753.55
 *
 * ⭐ `.bhp-pair-landing__sub` is the control that proves the diagnosis: same
 *    file, same `margin: 0 auto`, and it centered correctly at every width —
 *    because no `.bhp-landing p` rule exists to outrank it.
 */
bhp_fr400_head( '§9 PAIR PAGE H1 CENTERING' );

$fr400_pair_src = get_template_directory() . '/assets/css/bundle-pair-landing.css';
$fr400_pair_min = get_template_directory() . '/assets/css/bundle-pair-landing.min.css';
$fr400_pair_s   = is_readable( $fr400_pair_src ) ? (string) file_get_contents( $fr400_pair_src ) : '';
$fr400_pair_m   = is_readable( $fr400_pair_min ) ? (string) file_get_contents( $fr400_pair_min ) : '';
$fr400_pair_sz  = preg_replace( '/\s+/', '', $fr400_pair_s );
$fr400_pair_mz  = preg_replace( '/\s+/', '', $fr400_pair_m );

bhp_fr400_ok(
	'§9.0 the pair stylesheet and its minified artefact are both readable',
	strlen( $fr400_pair_s ) > 2000 && strlen( $fr400_pair_m ) > 1000,
	strlen( $fr400_pair_s ) . ' / ' . strlen( $fr400_pair_m ) . ' bytes'
);

/*
 * ⛔⛔ THE TWO-CLASS SELECTOR IS THE ENTIRE FIX AND IS THE THING MOST LIKELY TO
 *     BE "TIDIED". Written as the obvious `.bhp-pair-landing__title` alone it
 *     is (0,1,0), it LOSES to the plugin's (0,1,1), and it changes NOTHING —
 *     no error, no warning, no other failing test. Exactly the silent-loss
 *     class §5 of this file already documents for the eyebrow.
 */
bhp_fr400_ok(
	'§9.1 ⛔ the override is the TWO-CLASS selector that beats the plugin (0,1,1), in BOTH artefacts',
	false !== strpos( $fr400_pair_sz, '.bhp-pair-landing__hero.bhp-pair-landing__title{margin-inline:auto' )
	&& false !== strpos( $fr400_pair_mz, '.bhp-pair-landing__hero.bhp-pair-landing__title{margin-inline:auto' )
);

/*
 * ⭐ NO `!important`. It would have worked and would have hidden the reason.
 *    A later reader seeing `!important` learns nothing; seeing two classes
 *    learns that something else names this element.
 */
bhp_fr400_ok(
	'§9.2 ⭐ the fix is specificity, not !important',
	1 !== preg_match( '/\.bhp-pair-landing__title\s*\{[^}]*margin[^}]*!important/s', $fr400_pair_s )
);

/*
 * ⛔ `margin-inline` ONLY. The plugin's `margin: 0` also flattens the 0.5rem
 *    bottom margin the shipped rule asked for. That is a REAL, SEPARATE and
 *    UNFIXED finding, recorded in the build report. It is deliberately NOT
 *    repaired inside a centring change — a vertical-rhythm shift is not
 *    something a reviewer of "centre the H1" would be looking for.
 */
bhp_fr400_ok(
	'§9.3 ⛔ the override touches the inline axis ONLY — no block margin is restored here',
	1 !== preg_match(
		'/\.bhp-pair-landing__hero\s+\.bhp-pair-landing__title\s*\{[^}]*margin-(block|top|bottom)/s',
		$fr400_pair_s
	)
);

bhp_fr400_ok(
	'§9.4 the shipped max-width:20ch measure is untouched — this centres the box, it does not widen it',
	false !== strpos( $fr400_pair_sz, 'max-width:20ch' )
);

/*
 * ⛔ /complete-collection/ WAS CHECKED AND IS NOT AFFECTED. Its heading is
 *    `.bhp-landing-hero__title` inside `.bhp-landing-hero__intro`, which
 *    centres via the WRAPPER, so the plugin's `margin: 0` never mattered
 *    there. Measured live at 1280: gapL 222.33 vs gapR 222.67. This build must
 *    not reach it, and every selector added above is scoped under
 *    `.bhp-pair-landing__hero`.
 */
bhp_fr400_ok(
	'§9.5 ⛔ nothing added here reaches /complete-collection/ — no unscoped .bhp-landing-hero__title rule was introduced',
	1 !== preg_match( '/^\s*\.bhp-landing-hero__title\s*\{/m', $fr400_pair_s )
);

echo "\n============================================================\n";
printf(
	"FREE RESOURCES PHONE LAYOUT 400: %d passed, %d failed\n",
	(int) $GLOBALS['bhp_fr400_pass'],
	(int) $GLOBALS['bhp_fr400_fail']
);
echo "============================================================\n";
