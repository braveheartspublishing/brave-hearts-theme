<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.354: THE /author-visits/ FOLD, AND THE SPECIFICITY THAT CARRIES IT.
 *     `CYCLE179-LD-354`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT THIS RELEASE FIXED, MEASURED ON STAGING AT 1.19.353 BEFORE A
 *    LINE WAS WRITTEN, headless Chrome, `innerWidth`/`innerHeight` asserted in
 *    the same evaluation as the rectangles:
 *
 *      1440x900  the navy hero stood 444px tall to carry an eyebrow, a headline
 *                and two lines, then 128px of empty cream before the column
 *                headings. The first visit card ran 763 to 984 against a fold
 *                of 900, so its order-by line and its status pill were CUT OFF.
 *       375x812  card 652 to 850 against a fold of 812. Same cut, lower down.
 *
 *    This page is reached from PRINTED QR CODES taped to classroom doors. The
 *    thing a parent scanned the code to find was below the fold in the week of
 *    the visit it advertises.
 *
 * ⭐ AFTER, same instrument, same asserted viewports, theme 1.19.354:
 *      1440x900  hero 332px, card 587 to 808. Fully above a 900 fold.
 *       375x812  hero 362px, card 568 to 766. Fully above an 812 fold.
 *
 * ⛔⛔ THE ASSERTION THIS SUITE EXISTS FOR IS §2, AND IT IS ABOUT SPECIFICITY,
 *     NOT ABOUT NUMBERS. The obvious way to write this fix is:
 *
 *       .author-visits-hero { padding-block: … }          <- 0,1,0. DEAD.
 *
 *     It is dead on arrival because `body:not(.home) .section` is (0,2,1):
 *     `body` contributes an element and `:not(.home)` contributes a class. No
 *     amount of being LATER in the sheet beats it. The shipped rules are
 *     written at (0,3,1) for that reason.
 *
 *     ⛔ THIS IS NOT HYPOTHETICAL. A THIRD RULE IN THE SAME EDIT WAS WRITTEN AT
 *        (0,2,0), SHIPPED TO STAGING, AND MEASURED AS A NO-OP:
 *        `.author-visits-list-section .component-heading` computed to 72px
 *        rather than the 24px it asked for, because
 *        `body:not(.home) .component-heading` is (0,2,1) and won. It was
 *        removed rather than forced, and the reasoning is preserved in
 *        `style.css` at the block this suite guards. ⭐ A rule that silently
 *        does nothing is the failure class this file is here to catch, because
 *        it passes every review that reads the source and fails only a ruler.
 *
 * ⛔ WHAT THIS SUITE DELIBERATELY DOES NOT DO. It cannot measure a rendered
 *    rectangle: there is no browser under WP-CLI. The fold numbers above were
 *    measured in one, and the evidence is filed as PNG plus JSON rather than
 *    asserted here. ⭐ Standing Rules §3: a verification that was not run is
 *    never reported as one that was. This suite guards the CSS that produces
 *    those numbers, and says so.
 *
 * ⛔ IT WRITES NOTHING: no option, no post, no product, no price, no stock, no
 *    shipping setting, no session, no cart, no registry row. It reads files.
 *
 * ⭐ INVOCATION. Run it the way the whole set is run, WITH `--url=`. See
 *    `CYCLE179-LD-9`, where a suite's verdict turned on that flag being absent:
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-author-visits-fold-354.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.354
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ COUNTERS IN $GLOBALS, AND THAT IS NOT STYLE. `wp eval-file` runs this
 *    file's body INSIDE A FUNCTION, so a plain top-level variable is a LOCAL
 *    and a `global` inside a helper reaches a different slot. Two suites in
 *    this directory have already reported "failed: 0" while an assertion had
 *    genuinely failed, for exactly that reason.
 */
$GLOBALS['av354_pass'] = 0;
$GLOBALS['av354_fail'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function av354_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['av354_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['av354_fail'];
	echo "  FAIL  {$label}\n";
}

$av354_root     = get_template_directory();
$av354_css      = (string) @file_get_contents( $av354_root . '/style.css' );
$av354_min      = (string) @file_get_contents( $av354_root . '/style.min.css' );
$av354_template = (string) @file_get_contents( $av354_root . '/page-author-visits.php' );
$av354_version  = (string) wp_get_theme()->get( 'Version' );

echo "\n=== CYCLE179-LD-354 /author-visits/ fold gate - theme {$av354_version} ===\n";

/* =========================================================================
 * 1. THE SOURCES ARE READABLE AND THE VERSION CARRIES THE CHANGE
 * ====================================================================== */
echo "\n1. Preconditions\n";
av354_assert( '' !== $av354_css, '1.1 style.css is readable' );
av354_assert( '' !== $av354_min, '1.2 style.min.css is readable' );
av354_assert( '' !== $av354_template, '1.3 page-author-visits.php is readable' );
av354_assert(
	version_compare( $av354_version, '1.19.354', '>=' ),
	"1.4 the running theme is 1.19.354 or later (reads: {$av354_version})"
);

/* =========================================================================
 * 2. THE SPECIFICITY RAIL. This is the section that matters.
 * ====================================================================== */
echo "\n2. Specificity: the padding rules must OUTRANK body:not(.home) .section\n";

$av354_hero_rule = 'body:not(.home) .author-visits-hero.section';
$av354_list_rule = 'body:not(.home) .author-visits-list-section.section';

av354_assert(
	false !== strpos( $av354_css, $av354_hero_rule ),
	'2.1 ⛔ style.css declares the hero padding at (0,3,1): ' . $av354_hero_rule
);
av354_assert(
	false !== strpos( $av354_css, $av354_list_rule ),
	'2.2 ⛔ style.css declares the list-section padding at (0,3,1): ' . $av354_list_rule
);

/*
 * ⛔ THE RULE BEING OUTRANKED MUST STILL BE THERE. If `body:not(.home) .section`
 *    were ever removed, the two rules above would still work but for the wrong
 *    reason, and the comment explaining them would become misleading.
 */
av354_assert(
	false !== strpos( $av354_css, 'body:not(.home) .section' ),
	'2.3 the rule these outrank, body:not(.home) .section, is still present'
);

/*
 * ⛔ AND THE LOSING FORM MUST NOT COME BACK. A bare `.author-visits-hero {`
 *    block that declares padding-block is (0,1,0) and is a silent no-op.
 */
$av354_bare_hero = preg_match(
	'/(^|\})\s*\.author-visits-hero\s*\{[^}]*padding-block/s',
	$av354_css
);
av354_assert(
	0 === $av354_bare_hero,
	'2.4 ⛔ no bare .author-visits-hero { padding-block } block (0,1,0 is a silent no-op)'
);

$av354_bare_list = preg_match(
	'/(^|\})\s*\.author-visits-list-section\s*\{[^}]*padding-block/s',
	$av354_css
);
av354_assert(
	0 === $av354_bare_list,
	'2.5 ⛔ no bare .author-visits-list-section { padding-block } block'
);

/* =========================================================================
 * 3. THE HERO BODY COLOUR IS A BRAND TOKEN, AND THE SITEWIDE TOKEN IS INTACT
 * ====================================================================== */
echo "\n3. Colour: a kit token on this page, and nothing repointed sitewide\n";

av354_assert(
	1 === preg_match(
		'/\.author-visits-hero\s+\.text-lead\s*\{[^}]*color:\s*var\(--color-parchment\)/s',
		$av354_css
	),
	'3.1 ⭐ the hero body copy is var(--color-parchment), a colour the brand kit carries'
);

/*
 * ⛔ THE SITEWIDE DARK-SECTION COLOUR IS NOT REPOINTED. `--color-sky` is what
 *    every other dark section on the site inherits. Fixing one page by moving
 *    a sitewide token would move all of them.
 */
av354_assert(
	1 === preg_match(
		'/\.section--dark,\s*\n?\s*\.content-section-dark\s*\{[^}]*color:\s*var\(--color-sky\)/s',
		$av354_css
	),
	'3.2 ⛔ .section--dark still resolves to var(--color-sky): the sitewide token was NOT repointed'
);

/*
 * ⛔ NO HARDCODED HEX. A literal #f1e7d2 here would stop being a token the
 *    moment the token moved, which is a defect this sheet has already recorded
 *    once, on the homepage aside.
 */
av354_assert(
	0 === preg_match(
		'/\.author-visits-hero\s+\.text-lead\s*\{[^}]*#[0-9a-fA-F]{3,8}/s',
		$av354_css
	),
	'3.3 the hero lead colour is a token, not a hardcoded hex'
);

/* =========================================================================
 * 4. THE BUILT ARTEFACT CARRIES THE SAME THREE RULES
 * ====================================================================== */
echo "\n4. Artefact parity: style.min.css is what the browser actually loads\n";

av354_assert(
	false !== strpos( $av354_min, $av354_hero_rule ),
	'4.1 style.min.css carries the hero padding rule'
);
av354_assert(
	false !== strpos( $av354_min, $av354_list_rule ),
	'4.2 style.min.css carries the list-section padding rule'
);
av354_assert(
	1 === preg_match(
		'/\.author-visits-hero\s+\.text-lead\s*\{[^}]*color:\s*var\(--color-parchment\)/s',
		$av354_min
	),
	'4.3 style.min.css carries the hero lead colour'
);
av354_assert(
	false !== strpos( $av354_min, 'Version: ' . $av354_version ),
	"4.4 ⭐ style.min.css was built from a style.css naming the ACTIVE version ({$av354_version})"
);

/* =========================================================================
 * 5. SCOPE: THE CHANGE CANNOT REACH ANOTHER PAGE
 * ====================================================================== */
echo "\n5. Blast radius: these two classes live in exactly one template\n";

$av354_carriers_hero = array();
$av354_carriers_list = array();
$av354_php = array_merge(
	(array) glob( $av354_root . '/*.php' ),
	(array) glob( $av354_root . '/inc/*.php' ),
	(array) glob( $av354_root . '/template-parts/*.php' ),
	(array) glob( $av354_root . '/template-parts/*/*.php' ),
	(array) glob( $av354_root . '/woocommerce/*.php' ),
	(array) glob( $av354_root . '/woocommerce/*/*.php' )
);
foreach ( $av354_php as $av354_file ) {
	$av354_body = (string) @file_get_contents( $av354_file );
	if ( false !== strpos( $av354_body, 'class="section section--dark author-visits-hero' ) ) {
		$av354_carriers_hero[] = basename( $av354_file );
	}
	if ( false !== strpos( $av354_body, 'author-visits-list-section"' ) ) {
		$av354_carriers_list[] = basename( $av354_file );
	}
}
av354_assert(
	array( 'page-author-visits.php' ) === $av354_carriers_hero,
	'5.1 ⛔ author-visits-hero is rendered by page-author-visits.php and nothing else (found: '
		. ( $av354_carriers_hero ? implode( ', ', $av354_carriers_hero ) : 'none' ) . ')'
);
av354_assert(
	array( 'page-author-visits.php' ) === $av354_carriers_list,
	'5.2 ⛔ author-visits-list-section is rendered by page-author-visits.php and nothing else (found: '
		. ( $av354_carriers_list ? implode( ', ', $av354_carriers_list ) : 'none' ) . ')'
);

/*
 * ⛔ /school-read-alouds/ REUSES TWO NEIGHBOURING author-visits BLOCKS. They
 *    must keep working, which means this release must NOT have renamed them.
 */
av354_assert(
	false !== strpos( $av354_css, '.author-visits-gallery' )
		&& false !== strpos( $av354_css, '.author-visits-past' ),
	'5.3 the blocks /school-read-alouds/ reuses (.author-visits-gallery, .author-visits-past) still exist'
);

/* =========================================================================
 * 6. THE COPY AND THE LOGIC ARE UNTOUCHED
 * ====================================================================== */
echo "\n6. This was a spacing and colour release, and nothing else\n";

av354_assert(
	false !== strpos( $av354_template, 'Read-alouds in Idaho classrooms' ),
	'6.1 the hero eyebrow string is unchanged'
);
av354_assert(
	false !== strpos( $av354_template, 'Where I Am Reading Next' ),
	'6.2 the hero headline is unchanged'
);
av354_assert(
	false !== strpos( $av354_template, 'I visit Idaho classrooms to read aloud.' )
		&& false !== strpos( $av354_template, 'I sign each book to your child by name' ),
	'6.3 both hero body lines are unchanged'
);
av354_assert(
	false !== strpos( $av354_template, 'Upcoming Visits' ),
	'6.4 the Upcoming Visits heading is unchanged'
);
av354_assert(
	function_exists( 'bhp_author_visits_rows' )
		&& function_exists( 'bhp_author_visits_past_rows' ),
	'6.5 the visit data resolvers are present and were not touched'
);

/*
 * ⛔ NO FONT SIZE OR LINE HEIGHT MOVED. Compression that shrinks the type is a
 *    readability change wearing a spacing change's clothes, and this sheet has
 *    written that rule down once already for /school-read-alouds/.
 */
av354_assert(
	0 === preg_match(
		'/body:not\(\.home\) \.author-visits-(hero|list-section)\.section\s*\{[^}]*(font-size|line-height)/s',
		$av354_css
	),
	'6.6 ⛔ neither new rule touches a font-size or a line-height'
);

/* =========================================================================
 * 7. THIS FILE'S OWN RAILS
 * ====================================================================== */
echo "\n7. Rails on this file itself\n";

$av354_self = (string) @file_get_contents( __FILE__ );

/*
 * ⛔ STANDING RULES §14.5: the internal call names are internal. This
 *    repository is PUBLIC on GitHub. Assembled from fragments so the assertion
 *    cannot trip on its own source.
 */
$av354_aliases = array( 'Gand' . 'alf', 'Leg' . 'olas', 'Ara' . 'gorn', 'Me' . 'rry',
	'Pip' . 'pin', 'Fro' . 'do', 'Bor' . 'omir', 'Gim' . 'li' );
$av354_alias_hits = array();
foreach ( $av354_aliases as $av354_alias ) {
	if ( false !== strpos( $av354_self, $av354_alias ) ) {
		$av354_alias_hits[] = $av354_alias;
	}
}
av354_assert(
	array() === $av354_alias_hits,
	'7.1 ⛔ no internal call name in this file (public repository)'
);

/*
 * ⛔ NO REAL SCHOOL NAME, SLUG OR VISIT DATE. `test-school-visit-pickup.php` §3
 *    is the standing rail this obeys. The fold numbers above are geometry, not
 *    visit data, and the one school this release was measured against is named
 *    in the evidence file rather than in the repository.
 */
/*
 * ⛔ ASSEMBLED FROM FRAGMENTS, LIKE THE ALIAS LIST ABOVE, AND FOR THE SAME
 *    REASON. The first version of this assertion wrote the needle as a plain
 *    literal and therefore FAILED ON ITS OWN SOURCE: the word it was searching
 *    for was sitting inside the search. Caught by running the suite rather than
 *    by reading it, which is the whole argument for running it.
 */
$av354_school_word = 'Elemen' . 'tary';
av354_assert(
	false === strpos( $av354_self, $av354_school_word )
		&& 0 === preg_match( '/\b20\d\d-\d\d-\d\d\b/', $av354_self ),
	'7.2 ⛔ no real school name and no visit date in this file'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */
echo "\n=== CYCLE179-LD-354 RESULT ===\n";
echo "  passed:  {$GLOBALS['av354_pass']}\n";
echo "  failed:  {$GLOBALS['av354_fail']}\n";

if ( $GLOBALS['av354_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
