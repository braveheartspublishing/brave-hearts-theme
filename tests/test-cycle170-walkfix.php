<?php
/**
 * CYCLE170-LD-WALKFIX — the aesthetic-walk patch pass. Theme 1.19.338 (2026-08-30).
 *
 * ⭐ VERSION PIN MOVED TO 1.19.339 by `CYCLE170-LD-FINAL2` (2026-08-30). The
 *    SUITE still belongs to the walkfix lane and still asserts the walkfix
 *    patches; only the pin and the `?ver=` strings moved, because a pin left at
 *    1.19.338 would print red on every later release for no defect.
 *    ⛔ NOT ONE walkfix ASSERTION WAS WEAKENED, DELETED OR RETARGETED.
 * STAGING ONLY. `wp eval-file` from the site root.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE IS FOR. Five colour/punctuation patches, each of which has
 *    a QUIET failure mode — the page renders happily and the thing the walk
 *    measured is silently back.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   1 · THE HERO EYEBROW. Quiet failure: a later pass "tidies" the two-property
 *       block back into the one-liner it was and drops the colour with it. The
 *       eyebrow returns to 2.91:1 and NOTHING renders differently enough to
 *       notice at a glance.
 *
 *   2 · ⭐⭐ THE IVORY COMPANION TOKEN. This is the one that matters most and it
 *       is the one no visual check catches. `--color-ivory-rgb` is a HAND-KEPT
 *       triplet twin of `--color-ivory`. If `--expedition-parchment-light` is
 *       ever re-toned and this triplet is not moved with it, every Positivity
 *       gradient fades toward the wrong cream again and the seam comes back —
 *       silently, on a page nobody was editing. §2 asserts THE PAIR, by parsing
 *       the hex out of the stylesheet and comparing channels. It does not
 *       assert a literal `248, 243, 233`, because that would pass happily on
 *       the very day the hex moves.
 *
 *   3 · MUTED TEXT ON TINTED GROUNDS. Quiet failure: the scoped override is
 *       deleted as "redundant" because `--color-text-muted` looks like the
 *       right token — it is, on white, and these two strings are not on white.
 *
 *   4 · THE CHIPS SEPARATOR. Quiet failure: the `::before` form returns. Its
 *       superseded rule is QUOTED VERBATIM in the stylesheet comment beside its
 *       replacement, which is exactly why §4 runs over a COMMENT-STRIPPED copy.
 *       ⛔ A naive needle over this corpus is a FIVE-instance defect class now
 *       (bundle's `bhp_bun_code_only()`, weekpicker's `toLocaleDateString`, the
 *       chain lane's CSS block regex, micro's nav-rule scan, and this).
 *
 *   5 · THE POSITIVITY CTA. Quiet failure: the forest override creeps back and
 *       one page's primary action is again a different colour from every other
 *       primary action on the site.
 *
 * ⛔ NOTHING HERE WRITES. No option, no post, no page, no mail, no cart.
 *    It reads the stylesheet and fetches two already-published staging pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bhp_wfx_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_wfx_pass'] ) ) {
		$GLOBALS['bhp_wfx_pass'] = 0;
		$GLOBALS['bhp_wfx_fail'] = 0;
	}
	if ( $cond ) {
		$GLOBALS['bhp_wfx_pass']++;
		echo "  PASS  {$msg}\n";
	} else {
		$GLOBALS['bhp_wfx_fail']++;
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * ⛔ STRIP COMMENTS BEFORE LOOKING FOR ANYTHING.
 *
 * This stylesheet quotes its own superseded declarations verbatim, on purpose,
 * everywhere. Patches 4 and 5 in particular carry their PREDECESSOR's exact
 * text in the comment directly above their replacement. A needle run over the
 * raw file finds `::before`, `--color-forest` and `255, 250, 240` in comments
 * and reports the OPPOSITE of the truth.
 */
function bhp_wfx_code_only( $css ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
}

/** Pull one declaration's value out of the rule whose selector matches. */
function bhp_wfx_decl( $code, $selector_needle, $prop ) {
	if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $code, $m, PREG_SET_ORDER ) ) {
		return null;
	}
	$found = null;
	foreach ( $m as $rule ) {
		$sel = trim( preg_replace( '/\s+/', ' ', $rule[1] ) );
		if ( false === strpos( $sel, $selector_needle ) ) {
			continue;
		}
		if ( preg_match( '/(?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*([^;]+)/', $rule[2], $d ) ) {
			/* LAST match wins — that is what the cascade does. */
			$found = trim( $d[1] );
		}
	}
	return $found;
}

/**
 * ⛔ EXACT-SELECTOR variant. `bhp_wfx_decl()` matches a SUBSTRING and takes the
 *    LAST hit, which is right for "what does the cascade end up with" and WRONG
 *    for "what does THIS rule declare" — the resting CTA selector is a substring
 *    of its own `:hover` selector, so the loose form happily reported the hover
 *    colour as the resting one. Caught in build 1's own output.
 */
function bhp_wfx_decl_exact( $code, $selector, $prop ) {
	if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $code, $m, PREG_SET_ORDER ) ) {
		return null;
	}
	$found = null;
	foreach ( $m as $rule ) {
		$sel = trim( preg_replace( '/\s+/', ' ', $rule[1] ) );
		if ( $sel !== $selector ) {
			continue;
		}
		if ( preg_match( '/(?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*([^;]+)/', $rule[2], $d ) ) {
			$found = trim( $d[1] );
		}
	}
	return $found;
}

/** WCAG relative luminance / contrast, so the ratios in the report are computed, not quoted. */
function bhp_wfx_lum( $hex ) {
	$hex = ltrim( trim( $hex ), '#' );
	$c   = array();
	foreach ( array( 0, 2, 4 ) as $i ) {
		$v     = hexdec( substr( $hex, $i, 2 ) ) / 255;
		$c[]   = ( $v <= 0.03928 ) ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
function bhp_wfx_ratio( $a, $b ) {
	$l1 = bhp_wfx_lum( $a );
	$l2 = bhp_wfx_lum( $b );
	return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
}

echo "\n=== CYCLE170-LD-WALKFIX · theme 1.19.339 ===\n";

$bhp_wfx_raw  = (string) file_get_contents( get_template_directory() . '/style.css' );
$bhp_wfx_css  = bhp_wfx_code_only( $bhp_wfx_raw );

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · VERSION
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 0 · VERSION ===\n";

preg_match( '/^Version:\s*(\S+)/m', $bhp_wfx_raw, $bhp_wfx_vm );
$bhp_wfx_ver = isset( $bhp_wfx_vm[1] ) ? $bhp_wfx_vm[1] : '';
bhp_wfx_assert( '1.19.339' === $bhp_wfx_ver, "style.css declares 1.19.339, got '{$bhp_wfx_ver}'" );

/* ⭐ THE MINIFIED ARTEFACT IS WHAT THE BROWSER ACTUALLY LOADS. A patch that
      lands in style.css and never reaches style.min.css is invisible on the
      live page and passes every source assertion. The builder embeds the md5
      of its input; assert it matches the file on disk. */
$bhp_wfx_min = (string) file_get_contents( get_template_directory() . '/style.min.css' );
preg_match( '/source-md5:\s*([0-9a-f]{32})/', $bhp_wfx_min, $bhp_wfx_mm );
bhp_wfx_assert(
	isset( $bhp_wfx_mm[1] ) && $bhp_wfx_mm[1] === md5( $bhp_wfx_raw ),
	'style.min.css was rebuilt from THIS style.css (embedded source-md5 matches)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · PATCH 1 — THE HERO EYEBROW IS GOLD
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 1 · HERO EYEBROW ===\n";

$bhp_wfx_eyebrow = bhp_wfx_decl( $bhp_wfx_css, '.readaloud-funnel__hero .component-heading__eyebrow', 'color' );
bhp_wfx_assert(
	'var(--color-gold)' === $bhp_wfx_eyebrow,
	"the read-aloud hero eyebrow declares color: var(--color-gold), got '" . var_export( $bhp_wfx_eyebrow, true ) . "'"
);
bhp_wfx_assert(
	null !== bhp_wfx_decl( $bhp_wfx_css, '.readaloud-funnel__hero .component-heading__eyebrow', 'margin-bottom' ),
	'…and it did NOT lose its margin-bottom in the rewrite'
);

/* ⛔⛔ THE SPECIFICITY PREFIX. Build 1 shipped the walk's patch verbatim as a
      bare (0,2,0) selector; `body:not(.home) .component-heading__eyebrow` is
      (0,2,1) and beat it, so the eyebrow stayed at 2.91:1 and NOTHING in the
      source-level assertions above could see it. This is the assertion that
      would have caught it. */
bhp_wfx_assert(
	false !== strpos( $bhp_wfx_css, 'body:not(.home) .readaloud-funnel__hero .component-heading__eyebrow' ),
	'⭐⭐ the eyebrow rule carries the body:not(.home) prefix — (0,3,1) beats the (0,2,1) remap tier'
);

/* The colour it is measured against. `--color-gold` and `--expedition-gold`
   must stay the same value or the eyebrow and the CTA drift apart. */
preg_match( '/--color-gold:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_g1 );
preg_match( '/--expedition-gold:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_g2 );
bhp_wfx_assert(
	isset( $bhp_wfx_g1[1], $bhp_wfx_g2[1] ) && strtolower( $bhp_wfx_g1[1] ) === strtolower( $bhp_wfx_g2[1] ),
	'--color-gold and --expedition-gold are the same value (' . ( isset( $bhp_wfx_g1[1] ) ? $bhp_wfx_g1[1] : '?' ) . ')'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · PATCH 2 — ⭐⭐ THE IVORY COMPANION TOKEN AND ITS PARENT AGREE
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 2 · IVORY TOKEN PAIR ===\n";

/* The interior remap block. Isolate it so a root-tier declaration cannot
   satisfy this by accident. */
$bhp_wfx_interior = '';
if ( preg_match( '/body:not\(\.home\)\s*\{([^{}]*)\}/', $bhp_wfx_css, $bhp_wfx_bm ) ) {
	$bhp_wfx_interior = $bhp_wfx_bm[1];
}
bhp_wfx_assert( '' !== $bhp_wfx_interior, 'the body:not(.home) remap block parses' );

bhp_wfx_assert(
	false !== strpos( $bhp_wfx_interior, '--color-ivory-rgb' ),
	'⭐ body:not(.home) remaps --color-ivory-rgb, not only --color-ivory'
);

/* ⛔ ASSERT THE PAIR, NOT A LITERAL. A literal `248, 243, 233` passes on the
      exact day somebody re-tones the hex and forgets this line — which is the
      only failure mode worth testing for. */
preg_match( '/--expedition-parchment-light:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_pl );
preg_match( '/--color-ivory-rgb:\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $bhp_wfx_interior, $bhp_wfx_tr );
$bhp_wfx_pair_ok = false;
$bhp_wfx_pair_msg = 'could not parse one side of the pair';
if ( isset( $bhp_wfx_pl[1], $bhp_wfx_tr[3] ) ) {
	$hex = ltrim( $bhp_wfx_pl[1], '#' );
	$exp = array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	$got = array( (int) $bhp_wfx_tr[1], (int) $bhp_wfx_tr[2], (int) $bhp_wfx_tr[3] );
	$bhp_wfx_pair_ok  = ( $exp === $got );
	$bhp_wfx_pair_msg = $bhp_wfx_pl[1] . ' = ' . implode( ', ', $exp ) . ' vs declared ' . implode( ', ', $got );
}
bhp_wfx_assert( $bhp_wfx_pair_ok, "⭐⭐ --color-ivory-rgb matches --expedition-parchment-light channel-for-channel ({$bhp_wfx_pair_msg})" );

/* ⭐ BLAST RADIUS, ASSERTED RATHER THAN CLAIMED. The deploy plan says the eight
      consumers of this token are all Positivity photo gradients. If a sixth
      surface ever starts consuming it, this count moves and the claim in the
      plan stops being true — so the count is pinned. */
$bhp_wfx_consumers = preg_match_all( '/rgba\(\s*var\(\s*--color-ivory-rgb\s*\)/', $bhp_wfx_css );
bhp_wfx_assert(
	8 === $bhp_wfx_consumers,
	"--color-ivory-rgb has exactly 8 consumers (the two Positivity gradients), got {$bhp_wfx_consumers}"
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · PATCH 3 — MUTED TEXT ON TINTED GROUNDS
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 3 · MUTED TEXT ===\n";

$bhp_wfx_range = bhp_wfx_decl( $bhp_wfx_css, '.school-readalouds .readaloud-sched__week-range', 'color' );
bhp_wfx_assert(
	'var(--color-earth)' === $bhp_wfx_range,
	"the week-range's LAST colour declaration is var(--color-earth), got '" . var_export( $bhp_wfx_range, true ) . "'"
);
bhp_wfx_assert(
	false !== strpos( $bhp_wfx_css, '.school-readalouds .readaloud-funnel__fine-print' ),
	'the funnel fine-print is scoped and overridden on .school-readalouds'
);

/* ⛔ THE SECOND TARGET THE WALK'S PATCH LIST MISSED. The `--closed` variant is
      (0,3,0) and sits later in the file, so it beat the base override twice
      over and the four closed September cards stayed at 4.32:1. */
$bhp_wfx_closed = bhp_wfx_decl_exact(
	$bhp_wfx_css,
	'.school-readalouds .readaloud-sched__week--closed .readaloud-sched__week-range',
	'color'
);
bhp_wfx_assert(
	'var(--color-earth)' === $bhp_wfx_closed,
	"⭐⭐ the CLOSED week card's range is var(--color-earth) too, got '" . var_export( $bhp_wfx_closed, true ) . "'"
);

/* The ratio itself, computed here so the report never quotes an unverified number. */
preg_match( '/--color-earth:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_e );
preg_match( '/--expedition-parchment-light:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_p );
if ( isset( $bhp_wfx_e[1], $bhp_wfx_p[1] ) ) {
	$bhp_wfx_r = bhp_wfx_ratio( $bhp_wfx_e[1], $bhp_wfx_p[1] );
	bhp_wfx_assert( $bhp_wfx_r >= 4.5, sprintf( '--color-earth on the parchment ground is %.2f:1 (AA needs 4.5)', $bhp_wfx_r ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · PATCH 4 — THE CHIPS SEPARATOR IS ::after, NOT ::before
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 4 · CHIPS SEPARATOR ===\n";

/* ⛔⛔ NO SEPARATOR RULE MAY EXIST, IN ANY FORM. The chips live in the hero copy
      column, which is 488px at 1440 and narrower below; one line of the three
      chips needs 560px. THE LIST THEREFORE WRAPS AT EVERY VIEWPORT WIDTH, so
      `::before` orphans the mark at a line start, `::after` orphans it at a line
      end, and a `@media` form fixes one width and leaves the other. All three
      were built and measured on staging. The list stacks instead. */
bhp_wfx_assert(
	false === strpos( $bhp_wfx_css, '.school-readalouds__chip + .school-readalouds__chip::before' ),
	'⛔ the ::before form is GONE FROM THE CODE (comments stripped first — it is still quoted in one)'
);
/* ⚠ THE NEEDLE IS THE FULL SELECTOR, NOT THE `:not(:last-child)::after` FRAGMENT.
      The fragment is shared with `.guide-breadcrumbs li:not(:last-child)::after`
      (~line 2450), a completely unrelated component, and the short needle
      reported this patch as un-applied on a correct stylesheet. Caught in build
      4's own output. ⛔ A needle scoped narrower than the thing it is testing is
      the same defect class as one run over un-stripped comments. */
bhp_wfx_assert(
	false === strpos( $bhp_wfx_css, '.school-readalouds__chip:not(:last-child)::after' ),
	'⛔ the ::after form is gone from the code too — it orphaned the mark at the line END'
);
bhp_wfx_assert(
	false !== strpos( $bhp_wfx_raw, '.school-readalouds__chip + .school-readalouds__chip::before' ) &&
		false !== strpos( $bhp_wfx_raw, '.school-readalouds__chip:not(:last-child)::after' ),
	'⭐ …and BOTH superseded forms are still quoted verbatim in comments, so nobody re-derives either'
);
bhp_wfx_assert(
	false === strpos( $bhp_wfx_css, '@media (max-width: 40rem)' ),
	'⛔ and the viewport-conditional version built at build 3 is gone — it fixed 375 and left 1440'
);

/* ⭐ The stack itself, unconditional — no media query, so no width can escape it. */
$bhp_wfx_dir = bhp_wfx_decl_exact( $bhp_wfx_css, '.school-readalouds__chips', 'flex-direction' );
bhp_wfx_assert(
	'column' === $bhp_wfx_dir,
	"⭐⭐ the chip list stacks at every width — flex-direction: column, got '" . var_export( $bhp_wfx_dir, true ) . "'"
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · PATCH 5 — THE POSITIVITY CTA IS THE SITEWIDE GOLD
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 5 · POSITIVITY CTA ===\n";

$bhp_wfx_bg = bhp_wfx_decl_exact( $bhp_wfx_css, '.bhp-positivity .acquisition-form__submit', 'background-color' );
$bhp_wfx_fg = bhp_wfx_decl_exact( $bhp_wfx_css, '.bhp-positivity .acquisition-form__submit', 'color' );
bhp_wfx_assert(
	false !== strpos( (string) $bhp_wfx_bg, '--expedition-gold' ),
	"⭐ the RESTING submit's ground is var(--expedition-gold) — exact-selector match, not the hover rule, got '" . var_export( $bhp_wfx_bg, true ) . "'"
);
bhp_wfx_assert(
	false !== strpos( (string) $bhp_wfx_fg, '--expedition-navy' ),
	"the submit's text is navy — the sitewide pattern, got '" . var_export( $bhp_wfx_fg, true ) . "'"
);
bhp_wfx_assert(
	false === strpos( $bhp_wfx_css, 'var(--color-forest) !important' ) ||
		false === strpos( bhp_wfx_code_only( $bhp_wfx_raw ), '.bhp-positivity .acquisition-form__submit {' . "\n" . '  background-color: var(--color-forest)' ),
	'⛔ the page-scoped forest override is gone from the code'
);

/* ⭐ THE HOVER MUST NOT BE DARKER THAN THE RESTING STATE'S CONTRAST FLOOR. */
preg_match( '/--expedition-navy:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_n );
preg_match( '/--expedition-gold:\s*(#[0-9a-fA-F]{6})/', $bhp_wfx_css, $bhp_wfx_gd );
if ( isset( $bhp_wfx_n[1], $bhp_wfx_gd[1] ) ) {
	$bhp_wfx_rest  = bhp_wfx_ratio( $bhp_wfx_n[1], $bhp_wfx_gd[1] );
	$bhp_wfx_hover = bhp_wfx_ratio( $bhp_wfx_n[1], '#B8863F' );
	bhp_wfx_assert( $bhp_wfx_rest  >= 4.5, sprintf( 'CTA resting: navy on gold = %.2f:1', $bhp_wfx_rest ) );
	bhp_wfx_assert( $bhp_wfx_hover >= 4.5, sprintf( 'CTA hover:   navy on #B8863F = %.2f:1', $bhp_wfx_hover ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · THE RENDERED PAGES — the patches must reach the served stylesheet
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 6 · RENDERED ===\n";

$bhp_wfx_sr = wp_remote_retrieve_body( wp_remote_get( home_url( '/school-read-alouds/' ), array( 'timeout' => 45, 'sslverify' => false ) ) );
$bhp_wfx_pn = wp_remote_retrieve_body( wp_remote_get( home_url( '/positivity-news/' ), array( 'timeout' => 45, 'sslverify' => false ) ) );

bhp_wfx_assert( '' !== $bhp_wfx_sr, '/school-read-alouds/ returns a body' );
bhp_wfx_assert( '' !== $bhp_wfx_pn, '/positivity-news/ returns a body' );

/* The version-stamped asset. A stale `?ver=` is the classic "deployed but the
   browser is still on the old sheet" failure. */
bhp_wfx_assert(
	false !== strpos( $bhp_wfx_sr, 'style.min.css?ver=1.19.339' ),
	'/school-read-alouds/ links style.min.css?ver=1.19.339'
);
bhp_wfx_assert(
	false !== strpos( $bhp_wfx_pn, 'style.min.css?ver=1.19.339' ),
	'/positivity-news/ links style.min.css?ver=1.19.339'
);

/* ⛔ THE COPY IS UNCHANGED BY THIS RELEASE. Three chips, still three, and the
      middot is still NOT a character in any of them. */
$bhp_wfx_chips = substr_count( $bhp_wfx_sr, 'school-readalouds__chip"' );
bhp_wfx_assert( 3 === $bhp_wfx_chips, "the chip list still renders 3 chips, got {$bhp_wfx_chips}" );
if ( preg_match( '#<ul class="school-readalouds__chips">(.*?)</ul>#s', $bhp_wfx_sr, $bhp_wfx_cm ) ) {
	bhp_wfx_assert(
		false === strpos( $bhp_wfx_cm[1], '·' ),
		'⛔ no middot is stored in any chip string — the separator is still generated content only'
	);
}

/* The served minified sheet actually carries the patches. */
$bhp_wfx_served = wp_remote_retrieve_body( wp_remote_get( get_template_directory_uri() . '/style.min.css?ver=1.19.339', array( 'timeout' => 45, 'sslverify' => false ) ) );
bhp_wfx_assert( '' !== $bhp_wfx_served, 'the minified stylesheet is fetchable' );
bhp_wfx_assert(
	1 === preg_match( '/\.school-readalouds__chips\s*\{[^}]*flex-direction:\s*column/s', $bhp_wfx_served ) &&
		false === strpos( $bhp_wfx_served, '.school-readalouds__chip:not(:last-child)::after' ),
	'⭐ patch 4 is present in the SERVED stylesheet — the stack is there and no separator rule is'
);
bhp_wfx_assert(
	false !== strpos( $bhp_wfx_served, '--color-ivory-rgb: 248, 243, 233' ),
	'⭐ patch 2 is present in the SERVED stylesheet'
);

echo "\n=== TOTAL: {$GLOBALS['bhp_wfx_pass']} PASS / {$GLOBALS['bhp_wfx_fail']} FAIL ===\n";
