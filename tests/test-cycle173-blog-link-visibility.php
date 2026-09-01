<?php
/**
 * Brave Hearts — BLOG POST BODY LINK VISIBILITY.
 *
 * `CYCLE173-LD-344` (2026-08-31, theme 1.19.344).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle173-blog-link-visibility.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHY THIS SUITE EXISTS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER ORDER, 2026-08-31. ⚠ RELAYED by the Chief of Staff and NOT
 *    witnessed first-hand by the session that wrote this file (Standing Rules
 *    §9.2 rule 3 — recorded, not glossed):
 *
 *      "So all the blogs have this light green the the URL links- very hard to
 *       see on mobile - we need to make that color much brighter and
 *       underlined so they understand it a link- must be done on all blog
 *       pages."
 *
 * ⛔ THE DIAGNOSIS CORRECTED THE BRIEF, AND THE CORRECTION IS THE REASON THIS
 *    SUITE ASSERTS WHAT IT ASSERTS. The brief expected some other rule to be
 *    beating `.entry-content a:not(.btn)` and painting the links light green.
 *    Measured on the live production post
 *    `/blog/how-was-mount-everest-formed-for-kids/` in a real browser
 *    (2026-08-31), `getComputedStyle` on an in-body anchor returned
 *    `rgb(23, 63, 47)` — the `--expedition-forest` rule WAS winning, and a
 *    survey of every anchor on the page found no light green anywhere.
 *
 *    The two real defects were:
 *      1. `textDecorationLine` computed to `none`. The rule set only the
 *         decoration COLOUR, so it had been tinting a line never drawn.
 *      2. Link #173f2f against body copy #342f28 measured **1.13:1** — the
 *         link was invisible AS A LINK, which is precisely what he described.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES
 * ═══════════════════════════════════════════════════════════════════════════
 *   §1  the tokens exist and are literal hexes, not aliases of another token
 *   §2  the contrast ARITHMETIC, recomputed here from the WCAG 2.x formula
 *       against the hex actually in the stylesheet — against the COMPOSITED
 *       ground measured live, and against the three harsher creams the
 *       1.19.266 audit enumerated. A future palette edit that re-breaks AA
 *       fails a test instead of a reader
 *   §3  the link separates from the PROSE, which is the half a
 *       contrast-against-background check cannot see and the half the founder
 *       actually complained about
 *   §4  the rule really turns the underline ON — `text-decoration-line`, not
 *       just `text-decoration-color`, which is the exact defect being fixed
 *   §5  the rule is scoped to blog post bodies and the hold-outs are present,
 *       so the rail and the capture bands are not restyled
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads the
 *    stylesheet. It does NOT prove a COMPUTED colour, a computed
 *    `text-decoration-line`, or a rendered contrast against whatever ground an
 *    element actually lands on in a browser. Those are BROWSER facts and were
 *    measured separately at an asserted `window.innerWidth`; the evidence
 *    paths are in this release's DEPLOY-PLAN.md. A markup test that claimed
 *    them would be a fabricated verification (Standing Rules §3).
 *
 * ⛔ NOTHING IS WRITTEN. No post, page, product, price, option, coupon, stock
 *    level, shipping, tax, payment or checkout setting is created or modified
 *    by any line in this file.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_lv_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** WCAG 2.x relative luminance of a #rrggbb string. */
function bhp_lv_lum( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	$out = 0.0;
	$w   = array( 0.2126, 0.7152, 0.0722 );
	foreach ( array( 0, 2, 4 ) as $i => $off ) {
		$c    = hexdec( substr( $hex, $off, 2 ) ) / 255;
		$c    = ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$out += $w[ $i ] * $c;
	}
	return $out;
}

/** WCAG contrast ratio between two #rrggbb strings. */
function bhp_lv_ratio( $a, $b ) {
	$la = bhp_lv_lum( $a );
	$lb = bhp_lv_lum( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/**
 * Alpha-composite #rrggbb over #rrggbb.
 *
 * ⭐ REQUIRED, NOT A FLOURISH. `.editorial-surface` paints
 *    `rgba(255,250,240,.75)`, so the ground a blog link actually sits on is
 *    not any hex in the palette — it is that wash over the page background.
 *    Testing against `#fffaf0` would flatter the result.
 */
function bhp_lv_over( $fg, $alpha, $bg ) {
	$fg  = ltrim( (string) $fg, '#' );
	$bg  = ltrim( (string) $bg, '#' );
	$out = '';
	foreach ( array( 0, 2, 4 ) as $off ) {
		$f    = hexdec( substr( $fg, $off, 2 ) );
		$b    = hexdec( substr( $bg, $off, 2 ) );
		$out .= sprintf( '%02x', (int) round( $alpha * $f + ( 1 - $alpha ) * $b ) );
	}
	return '#' . $out;
}

/** The declared value of a custom property in a stylesheet body. */
function bhp_lv_token( $css, $name ) {
	if ( preg_match( '/' . preg_quote( $name, '/' ) . '\s*:\s*([^;]+);/', $css, $m ) ) {
		return trim( $m[1] );
	}
	return null;
}

$theme_dir = get_template_directory();
$style     = (string) @file_get_contents( $theme_dir . '/style.css' );

bhp_lv_assert( '' !== $style, 'style.css is readable', $failures );

/* ── §1 · THE TOKENS ─────────────────────────────────────────────────────── */

$lv_link  = bhp_lv_token( $style, '--expedition-link' );
$lv_hover = bhp_lv_token( $style, '--expedition-link-hover' );

bhp_lv_assert( null !== $lv_link, '§1.1 --expedition-link is declared in style.css', $failures );
bhp_lv_assert( null !== $lv_hover, '§1.2 --expedition-link-hover is declared in style.css', $failures );
bhp_lv_assert(
	is_string( $lv_link ) && preg_match( '/^#[0-9a-f]{6}$/i', $lv_link ),
	sprintf( '§1.3 --expedition-link is a LITERAL hex, not a var() alias (got: %s)  ⭐ it deliberately does NOT alias --expedition-success: the two share a value today by arithmetic coincidence, and a future change to one must not silently move the other', (string) $lv_link ),
	$failures
);
bhp_lv_assert(
	is_string( $lv_hover ) && preg_match( '/^#[0-9a-f]{6}$/i', $lv_hover ),
	sprintf( '§1.4 --expedition-link-hover is a LITERAL hex (got: %s)', (string) $lv_hover ),
	$failures
);

/* ── §2 · THE CONTRAST ARITHMETIC, RECOMPUTED ────────────────────────────── */

/*
 * ⭐ THE FIRST GROUND IS THE ONE MEASURED IN THE BROWSER, not one chosen for
 *    convenience: `.editorial-surface`'s rgba(255,250,240,.75) composited over
 *    the body background, which computed to rgb(248,243,233) on the live post.
 *    The other three are the harsher creams the 1.19.266 audit enumerated —
 *    included so this cannot pass on the friendliest surface alone.
 */
$lv_grounds = array(
	'composited editorial-surface (measured live)' => bhp_lv_over( '#fffaf0', 0.75, '#f8f3e9' ),
	'--expedition-parchment-light'                 => '#f8f3e9',
	'--expedition-parchment'                       => '#f1e7d2',
	'darkest cream in circulation'                 => '#efdcc1',
);

if ( is_string( $lv_link ) && preg_match( '/^#[0-9a-f]{6}$/i', $lv_link ) ) {
	foreach ( $lv_grounds as $lv_label => $lv_ground ) {
		$lv_r = bhp_lv_ratio( $lv_link, $lv_ground );
		bhp_lv_assert(
			$lv_r >= 4.5,
			sprintf( '§2.1 %s on %s (%s) = %.2f:1 — clears WCAG AA 4.5:1', $lv_link, $lv_ground, $lv_label, $lv_r ),
			$failures
		);
	}
}

if ( is_string( $lv_hover ) && preg_match( '/^#[0-9a-f]{6}$/i', $lv_hover ) && is_string( $lv_link ) ) {
	$lv_ground_live = bhp_lv_over( '#fffaf0', 0.75, '#f8f3e9' );
	bhp_lv_assert(
		bhp_lv_ratio( $lv_hover, $lv_ground_live ) >= bhp_lv_ratio( $lv_link, $lv_ground_live ),
		sprintf(
			'§2.2 the hover ink is never LESS legible than the resting ink (%.2f:1 vs %.2f:1)  ⛔ a hover state that dims is a regression disguised as an affordance',
			bhp_lv_ratio( $lv_hover, $lv_ground_live ),
			bhp_lv_ratio( $lv_link, $lv_ground_live )
		),
		$failures
	);
}

/* ── §3 · SEPARATION FROM THE PROSE — the half §2 cannot see ─────────────── */

/*
 * ⛔ THIS IS THE ASSERTION THAT WOULD HAVE CAUGHT THE ORIGINAL DEFECT, and
 *    nothing in the suite before 1.19.344 was looking at it. The old ink
 *    #173f2f passed every background-contrast check on this page at 11.09:1
 *    and was STILL unreadable as a link, because it sat 1.13:1 from the body
 *    copy it was embedded in. Contrast against the ground is a legibility
 *    question; contrast against the NEIGHBOURS is the affordance question, and
 *    they are not the same test.
 *
 * ⚠ 1.5:1 IS A FLOOR CHOSEN TO CATCH THE FAILURE MODE, NOT A WCAG FIGURE.
 *   WCAG has no "link vs surrounding text" ratio at AA for underlined links —
 *   the underline is what satisfies 1.4.1, and §4 asserts the underline
 *   independently. This limb exists so a future edit cannot quietly return the
 *   link ink to something indistinguishable from prose while still passing §2.
 *   Stated plainly rather than dressed up as a standard.
 */
$lv_body_copy = '#342f28'; // measured live: getComputedStyle(.entry-content p).color
if ( is_string( $lv_link ) && preg_match( '/^#[0-9a-f]{6}$/i', $lv_link ) ) {
	$lv_sep = bhp_lv_ratio( $lv_link, $lv_body_copy );
	bhp_lv_assert(
		$lv_sep >= 1.5,
		sprintf( '§3.1 the link ink separates from body copy %s: %.2f:1 (was 1.13:1 at 1.19.343, which is why the founder could not see it)', $lv_body_copy, $lv_sep ),
		$failures
	);
	bhp_lv_assert(
		bhp_lv_lum( $lv_link ) > bhp_lv_lum( '#173f2f' ),
		sprintf( '§3.2 the link ink is genuinely BRIGHTER than the 1.19.343 forest it replaces (L %.4f vs %.4f) — the founder asked for "much brighter", so this asserts the direction, not just the ratio', bhp_lv_lum( $lv_link ), bhp_lv_lum( '#173f2f' ) ),
		$failures
	);
}

/* ── §4 · THE UNDERLINE IS ACTUALLY TURNED ON ────────────────────────────── */

/*
 * ⛔ THE EXACT DEFECT, ASSERTED DIRECTLY. The 1.19.343 rule set
 *    `text-decoration-color` and `text-underline-offset` and never set
 *    `text-decoration-line`, so the base `a { text-decoration: none }` won and
 *    no line was ever drawn. A test that only checked "the rule mentions
 *    text-decoration" would have passed on the broken code.
 */
if ( preg_match( '/\.post-content\.entry-content a:not\(\.btn\)\s*\{([^}]*)\}/', $style, $lv_m ) ) {
	$lv_rule = $lv_m[1];

	bhp_lv_assert(
		(bool) preg_match( '/text-decoration-line\s*:\s*underline/', $lv_rule ),
		'§4.1 the blog-post link rule sets text-decoration-line: underline EXPLICITLY  ⛔ this is the 1.19.343 defect: the old rule styled the colour of a line that was never drawn',
		$failures
	);
	bhp_lv_assert(
		(bool) preg_match( '/text-decoration-thickness\s*:\s*([2-9]|\d{2,})px/', $lv_rule ),
		'§4.2 …and the underline is at least 2px — a real rule, not the hairline the founder said he could not see',
		$failures
	);
	bhp_lv_assert(
		(bool) preg_match( '/text-decoration-color\s*:\s*currentColor/i', $lv_rule ),
		'§4.3 …and the underline is the LINK\'s colour, not the old gold (--expedition-gold measures 1.79-2.23:1 on cream and was never legible as a line)',
		$failures
	);
	bhp_lv_assert(
		(bool) preg_match( '/color\s*:\s*var\(\s*--expedition-link\s*\)/', $lv_rule ),
		'§4.4 …and the ink comes from the token, so §2\'s arithmetic actually governs the rendered colour rather than describing an unrelated literal',
		$failures
	);
} else {
	bhp_lv_assert( false, '§4.0 the .post-content.entry-content a:not(.btn) rule exists in style.css', $failures );
}

/* ── §5 · SCOPE AND HOLD-OUTS ────────────────────────────────────────────── */

/*
 * ⭐ THE COMPOUND SELECTOR IS THE SCOPE MECHANISM. `.post-content` and
 *    `.entry-content` appear together in exactly one place in the theme —
 *    `single.php` — so the rule reaches blog post bodies and not `page.php`.
 */
bhp_lv_assert(
	1 === substr_count( (string) @file_get_contents( $theme_dir . '/single.php' ), 'post-content entry-content' ),
	'§5.1 single.php still carries the "post-content entry-content" class pair the rule is scoped to  ⛔ renaming it silently un-scopes the founder\'s fix',
	$failures
);

$lv_page_php = (string) @file_get_contents( $theme_dir . '/page.php' );
bhp_lv_assert(
	'' !== $lv_page_php && false === strpos( $lv_page_php, 'post-content entry-content' ),
	'§5.2 page.php does NOT carry that pair, so ordinary pages keep the link styling they had  ⭐ the order named "all blog pages"; widening to every editorial surface would exceed it',
	$failures
);

/*
 * ⛔ THE HOLD-OUTS ARE LOAD-BEARING. The book rail, the mid-post capture and
 *    the capture band are INJECTED INTO the_content(), so they sit inside
 *    `.post-content.entry-content` even though they are chrome, not prose.
 *    Verified in the live DOM, not assumed: `.entry-content.contains(.bhp-book-rail)`
 *    returned true and 3 of the 9 anchors inside `.entry-content` were the
 *    rail's. Without these rules the founder's underline would repaint the
 *    rail, which the brief explicitly excludes.
 */
foreach ( array( 'bhp-book-rail', 'bhp-capture-band', 'bhp-post-capture' ) as $lv_holdout ) {
	bhp_lv_assert(
		(bool) preg_match( '/\.post-content\.entry-content \.' . preg_quote( $lv_holdout, '/' ) . ' a:not\(\.btn\)\s*[,{]/', $style ),
		sprintf( '§5.3 .%s links are held out of the new blog-link styling  ⛔ it is injected INTO the_content(), so it is inside the scope and must be pinned back explicitly', $lv_holdout ),
		$failures
	);
}

/*
 * ⭐ AND THE HOVER HALF OF THE HOLD-OUT. Setting text-decoration-line: none at
 *    (0,3,1) also outranks `a:hover` and `.bhp-book-rail__title a:hover`, so
 *    writing only the resting state would have SILENTLY REMOVED the rail's
 *    existing hover underline — a regression introduced by a rule whose whole
 *    purpose was to change nothing about the rail.
 */
bhp_lv_assert(
	(bool) preg_match( '/\.post-content\.entry-content \.bhp-book-rail a:not\(\.btn\):hover/', $style ),
	'§5.4 the hold-out restores the rail\'s HOVER underline as well as pinning its resting state',
	$failures
);

/*
 * ⛔ NO COMPLEX :not() IN THIS PATH. Complex `:not()` arguments only reached
 *    Safari in 16.4; a stale iOS would drop the WHOLE selector and take the
 *    founder's fix off exactly the mobile browsers he was complaining about.
 */
bhp_lv_assert(
	! preg_match( '/\.post-content\.entry-content[^{]*:not\([^)]*[ >+~][^)]*\)/', $style ),
	'§5.5 the blog-link rules use no COMPLEX :not() argument  ⛔ Safari < 16.4 drops the entire selector, which would remove this fix from the mobile browsers the order was about',
	$failures
);

/* ── Result ──────────────────────────────────────────────────────────────── */
if ( $failures ) {
	echo "\n" . count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "\nALL BLOG LINK VISIBILITY TESTS PASSED\n";
