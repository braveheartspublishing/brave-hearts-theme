<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE PAID-AD LANDING SUITE — theme 1.19.308, 2026-08-27,
 * `CYCLE167-LD-KIT-PAGE-REFRESH`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-kit-page.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT THIS SUITE GUARDS, AND WHY IT IS SEPARATE FROM THE COPY SUITE
 * ---------------------------------------------------------------------------
 *
 * `test-cycle167-capture-copy.php` now covers this page as its fourteenth
 * parent capture surface, and that is where every ONE-OFFER assertion lives.
 * This file exists for the three things that are true of THIS page and of no
 * other, because it is about to become the destination of paid traffic:
 *
 *   1 · ⭐⭐ THE MAILCHIMP PLUMBING RESOLVES TO THE SAME TAG TRIO AS BEFORE —
 *       proven by EXECUTING the filter chain, not by reading it. The founder's
 *       returning $0.25-CPC Meta ad (carrier item 330) points here, and the
 *       tag "Source: Parent Landing Page" is the join key his August signups
 *       already carry. A copy release that silently split that segment would
 *       cost him the ability to tell whether the ad worked, and nothing else
 *       in this repository would have failed.
 *
 *   2 · ⛔⛔ THE NIECE GUARD ON THE PHOTOGRAPH'S ACCESSIBLE NAME. Charlotte is
 *       Andrew's NIECE and he has no children (carrier item 285, his own
 *       words). On 2026-08-27 an inferred "daughter" reached the accessibility
 *       layer of a delivered PDF — the one layer no visual review ever meets —
 *       and he caught it himself. A comment cannot stop that. This can.
 *
 *   3 · ⭐ STANDING RULES §26, THE AFFILIATE COUNT-DECREASE TEST, in the half a
 *       PHP assertion can honestly perform.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * ⛔ IT DOES NOT PROVE THE FORM IS ABOVE THE FOLD. That is the single most
 *    important claim of this release and PHP cannot see it. It is a browser
 *    measurement at a stated `window.innerWidth`, recorded in the handoff with
 *    the number it measured. A PASS below is not evidence for it.
 * ⛔ IT DOES NOT PROVE THE GRADIENT OR THE SQUIGGLE RENDER. Same reason.
 * ⛔ IT DOES NOT PROVE A SUBSCRIBER IS CREATED OR AN EMAIL SENT. §3 executes a
 *    WordPress FILTER. It contacts nothing, subscribes nobody, and says
 *    nothing about whether Mailchimp is healthy.
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, and it leaves no permanent filter registered.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/*
 * ⛔ COUNTERS IN $GLOBALS. `wp eval-file` runs this file in FUNCTION scope, so
 *    a file-top `$pass = 0;` is a LOCAL and `global $pass;` inside the helper
 *    binds a different, unset global — the helper would increment one variable
 *    and the summary would read another, making the suite structurally
 *    incapable of reporting a failure. ⛔ A SUITE THAT CANNOT FAIL IS A
 *    FABRICATED VERIFICATION.
 */
$GLOBALS['bhp_kit_pass'] = 0;
$GLOBALS['bhp_kit_fail'] = 0;

function bhp_kit_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_kit_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_kit_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_kit_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * The template's CODE with every comment removed. Load-bearing, not tidy: this
 * file's own template quotes retired copy and forbidden kinship words in its
 * docblocks in order to explain what changed, and a raw `strpos()` would match
 * the EXPLANATION and report a defect that does not exist. Worse, an author who
 * hit that false positive would be tempted to delete the historical record to
 * make a test go green.
 */
function bhp_kit_code() {
	static $code = null;
	if ( null !== $code ) {
		return $code;
	}
	$path = get_template_directory() . '/page-reluctant-reader-adventure-kit.php';
	if ( ! file_exists( $path ) ) {
		$code = '';
		return $code;
	}
	$out = '';
	foreach ( token_get_all( (string) file_get_contents( $path ) ) as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			$out .= $t[1];
		} else {
			$out .= $t;
		}
	}
	$code = $out;
	return $code;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§0 PRECONDITIONS' );

bhp_kit_ok(
	'§0.1 theme version is 1.19.308 or later',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.308', '>=' ),
	'got ' . wp_get_theme()->get( 'Version' )
);

bhp_kit_ok( '§0.2 the landing template exists and is readable', '' !== bhp_kit_code() );

bhp_kit_ok(
	'§0.3 the comment stripper actually strips (it is what keeps §2 and §5 honest)',
	false === strpos( bhp_kit_code(), 'THE FOUNDER PHOTOGRAPH, THE GRADIENT' )
);

/* ⭐ THE PAGE IS REAL AND STILL AT THE URL THE AD WILL POINT AT. §9.2: a claim
 *    about live state is checked in the live system. This one is checkable in
 *    PHP because the instrument IS WordPress. */
$kit_page = get_page_by_path( 'reluctant-reader-adventure-kit' );
bhp_kit_ok( '§0.4 ⭐ /reluctant-reader-adventure-kit/ exists as a page', $kit_page instanceof WP_Post );
if ( $kit_page instanceof WP_Post ) {
	bhp_kit_ok( '§0.5 ⭐ it is published', 'publish' === $kit_page->post_status, $kit_page->post_status );
	bhp_kit_ok(
		'§0.6 ⭐⭐ it still uses THIS template (the ad-ready URL and the refreshed page are the same object)',
		'page-reluctant-reader-adventure-kit.php' === get_page_template_slug( $kit_page ),
		get_page_template_slug( $kit_page )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE OFFER, ON ALL THREE FORMS THIS PAGE CARRIES.
 *
 * ⭐ The cross-surface comparison lives in `test-cycle167-capture-copy.php`.
 *    What is asserted HERE is the count: this page has THREE capture paths and
 *    the release changed all three. A future edit that fixes one and forgets
 *    the others is the defect shape this page is uniquely exposed to.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§1 THREE FORMS, ONE OFFER' );

$code     = bhp_kit_code();
$headline = 'FREE Chapter for Reluctant Readers';
$button   = 'Send me the chapter';
$bridge   = "I'll send you the chapter now, just add your email."
	. ' It arrives inside my free Reluctant Reader Adventure Kit, along with a printable activity'
	. ' and tips for reading it with a 6 to 9 year old.';

bhp_kit_ok(
	'§1a the headline appears on the hero, the inline panel and the modal',
	3 <= substr_count( $code, $headline ),
	'occurrences: ' . substr_count( $code, $headline )
);
bhp_kit_ok(
	'§1b all three submit controls carry the send-imperative',
	3 === substr_count( $code, "'" . $button . "'" ),
	'occurrences: ' . substr_count( $code, "'" . $button . "'" )
);
/*
 * ⚠ FOUR, NOT THREE, AND THE FIRST RUN OF THIS SUITE CAUGHT THE ARITHMETIC
 *   RATHER THAN A DEFECT. The sentence appears in four places, verified by
 *   reading the file at lines 282, 991, 1015 and 1300: the hero's own lead
 *   paragraph, the #free panel's lead paragraph, the panel form's `text`, and
 *   the modal's `text`. The hero form takes NO `text` argument — the paragraph
 *   directly above it does that job — so "three forms" and "four occurrences"
 *   are both correct and describe different things.
 * ⭐ THE ASSERTION WAS LOOSENED ONLY AFTER THE FILE WAS READ AND FOUND RIGHT.
 *   Relaxing a failing assertion before checking what it caught is how a suite
 *   stops being evidence.
 */
bhp_kit_ok(
	'§1c every surface that says what arrives carries the full support + bridge sentence',
	4 === substr_count( $code, $bridge ),
	'occurrences: ' . substr_count( $code, $bridge )
);

/* ⛔ THE HERO FORM IS A FORM, NOT A BUTTON THAT OPENS ONE. This is the release
 *    in one assertion: a paid click should not have to buy a second click. */
bhp_kit_ok(
	'§1d ⭐⭐ the hero renders a real signup form, not a modal trigger',
	false !== strpos( $code, "'parent-landing-hero-signup'" )
		&& false !== strpos( $code, "template-parts/acquisition/signup-form" )
);
bhp_kit_ok(
	'§1e ⛔ the hero form is gated on the Kit PDF being set (fail-closed)',
	false !== strpos( $code, "\$download['ready']" )
);

/* ⛔ THE MODAL IS NOT RETIRED. The final CTA and the sticky bar still open it,
 *    and removing it while removing the hero button would have left them
 *    pointing at nothing. */
bhp_kit_ok(
	'§1f ⛔ the CTA modal survives for the far-from-hero CTAs',
	false !== strpos( $code, "'adventure-kit-modal'" )
		&& 1 === substr_count( $code, "template-parts/acquisition/signup-modal" )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · ⛔⛔ THE PHOTOGRAPH AND THE NIECE GUARD.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§2 THE FOUNDER PHOTOGRAPH AND THE NIECE GUARD' );

bhp_kit_ok( '§2.0 the photo helpers exist', function_exists( 'bhp_get_founder_photo' ) && function_exists( 'bhp_niece_canon_violations' ) );

$photo = function_exists( 'bhp_get_founder_photo' ) ? bhp_get_founder_photo() : array();

bhp_kit_ok(
	'§2a ⭐ all four crops resolve (a missing file stands the whole treatment down)',
	! empty( $photo['portrait_webp'] ) && ! empty( $photo['portrait_jpg'] )
		&& ! empty( $photo['band_webp'] ) && ! empty( $photo['band_jpg'] )
);
bhp_kit_ok(
	'§2b intrinsic dimensions were read from the files, not typed',
	! empty( $photo['portrait_width'] ) && ! empty( $photo['band_width'] )
		&& (int) $photo['portrait_height'] > (int) $photo['portrait_width']
		&& (int) $photo['band_width'] > (int) $photo['band_height'],
	'portrait ' . ( $photo['portrait_width'] ?? '?' ) . 'x' . ( $photo['portrait_height'] ?? '?' )
		. ', band ' . ( $photo['band_width'] ?? '?' ) . 'x' . ( $photo['band_height'] ?? '?' )
);
bhp_kit_ok(
	'§2c ⭐ the URLs carry the theme-version cache-buster (1.19.299 learned this the hard way)',
	! empty( $photo['portrait_jpg'] ) && false !== strpos( (string) $photo['portrait_jpg'], 'ver=' )
);

$alt = isset( $photo['alt'] ) ? (string) $photo['alt'] : '';
bhp_kit_ok( '§2d the photograph has an accessible name at all', '' !== trim( $alt ) );

$violations = function_exists( 'bhp_niece_canon_violations' ) ? bhp_niece_canon_violations( $alt ) : array( 'guard unavailable' );
bhp_kit_ok(
	'§2e ⛔⛔ the alt text passes the niece canon guard',
	empty( $violations ),
	implode( '; ', $violations ) . '  |  alt: ' . $alt
);
bhp_kit_ok(
	'§2f ⭐ and it names the relationship correctly rather than deleting it',
	false !== stripos( $alt, 'niece' )
);

/* Voice and claim rails on the one customer-facing string this release adds to
 * the accessibility layer. ⛔ Checked on the resolved value, not on the source,
 * because a filter may have changed it. */
bhp_kit_ok( '§2g ⛔ no em dash in the alt text', false === strpos( $alt, "\xE2\x80\x94" ) );
bhp_kit_ok(
	'§2h ⛔ first person, never "we" (VOICE §9.1 — he is the sole operator)',
	0 === preg_match( '/\bwe\b/i', $alt ) && 1 === preg_match( '/\b(I|my|me)\b/', $alt )
);
bhp_kit_ok(
	'§2i ⛔ no outcome claim — it describes a frame, it does not say what a book does to a child',
	0 === preg_match( '/\b(will (?:love|read|improve)|turns? your|makes? your child|guaranteed|proven|thanks to|because of)\b/i', $alt )
);

/* The markup side. ⛔ NO TEXT OVER THE IMAGE and NO CAPTION: the accessible name
 * is the alt text, and a caption would put words beside a child's face for no
 * gain the alt text does not already give. */
bhp_kit_ok(
	'§2j the hero renders the squiggle in BOTH orientations (one shown at a time by CSS)',
	false !== strpos( $code, 'parent-landing-hero__squiggle--v' )
		&& false !== strpos( $code, 'parent-landing-hero__squiggle--h' )
);
bhp_kit_ok(
	'§2k ⛔ the seam is decorative and is hidden from assistive tech',
	1 === preg_match( '/parent-landing-hero__seam"\s+aria-hidden="true"/', $code )
);
bhp_kit_ok(
	'§2l ⛔ the figure carries no figcaption',
	false === strpos( $code, 'figcaption' )
);
bhp_kit_ok(
	'§2m ⛔ the treatment fails closed — no photo, no markup, never a broken box',
	false !== strpos( $code, "empty(\$bhp_kit_founder_photo['portrait_webp'])" )
);

/* ⛔ EXACTLY ONE FOUNDER PHOTOGRAPH ON THE PAGE. The 2026-07-17 correction on
 *   this same template removed a duplicate for this reason; 1.19.308 removed
 *   the second one again after adding the hero frame. */
bhp_kit_ok(
	'§2n ⭐ exactly one founder photograph renders on this page',
	false === strpos( $code, 'founder-and-charlotte.webp' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2o-§2s · ⭐⭐ THE FACE SAFE ZONE — added 1.19.310,
 *           `CYCLE167-LD-KIT-PHOTO-CROP-FIX`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT THIS EXISTS TO CATCH SHIPPED TO PRODUCTION AND THE FOUNDER
 *    FOUND IT ON HIS OWN SCREEN. At 1.19.309 the desktop panel rendered with
 *    `object-position: 50% 42%` — a value inherited from the popup lane, where
 *    the panel is NARROWER than the file and the vertical component is inert.
 *    Here the panel is WIDER than the file, so that number became the whole
 *    vertical crop and put 82 source pixels above the frame. Andrew, verbatim:
 *    "My face is cutoff on desktop."
 *
 * ⭐ THIS IS THE HALF OF "FACES SURVIVE EVERY CROP" THAT A PHP ASSERTION CAN
 *    HONESTLY PERFORM. It does not look at a photograph. It re-derives the
 *    `object-fit: cover` geometry from THREE MEASURED INPUTS — the file's
 *    intrinsic size read from disk, and the panel's `aspect-ratio` and
 *    `object-position` PARSED OUT OF THE SHIPPING STYLESHEET — and checks the
 *    resulting visible window against the safe zone below. Every one of the
 *    three is read rather than assumed, so editing any of them re-runs the
 *    arithmetic instead of quietly invalidating it.
 *
 * ⛔ WHAT IT DOES NOT PROVE, stated so a PASS is not over-read: it does not
 *    prove the panel RENDERS at that aspect. The `aspect-ratio` is capped by
 *    `max-height: 560px` against a `max-width: 1160px` grid, and that the two
 *    caps meet exactly at 448x560 is a BROWSER measurement, recorded in the
 *    handoff at seven widths. This row proves the DECLARED shape is safe; the
 *    browser proves the declared shape is the rendered one.
 *
 * ⭐ THE SAFE ZONE, MEASURED 2026-08-27 off a gridded copy of the shipping
 *    file at 5% intervals — not estimated, and filed with the handoff:
 *      · Andrew's forehead begins at y = 0. THE SOURCE FILE ALREADY CUTS HIS
 *        HAIR AT ITS OWN TOP EDGE, so there is no headroom to spend and the
 *        only safe top trim is zero.
 *      · his eyes sit at y ~ 35-50, which is why 42% (82px) took them
 *      · the cover title block runs y ~ 285 - 400
 *      · the "Andrew" byline and the book's lower edge sit by y ~ 620
 *    ⛔ THE COORDINATES BELONG TO THIS EXACT FILE. §2s pins the intrinsic size
 *       so that swapping the photograph FAILS here rather than passing on
 *       coordinates that no longer describe anything.
 */
$pl_css_path = get_template_directory() . '/assets/css/parent-landing.css';
$pl_css_raw  = is_readable( $pl_css_path ) ? (string) file_get_contents( $pl_css_path ) : '';

/* ⛔⛔ COMMENTS ARE STRIPPED BEFORE ANYTHING IS PARSED, AND THIS FILE IS THE
 *    REASON. The first version of this section parsed the raw stylesheet and
 *    FAILED on a correct fix: the comment that explains the bug quotes the old
 *    value verbatim ("the base rule in §3 sets `object-position: 50% 42%`"),
 *    the regex reached the PROSE before the DECLARATION, and the suite reported
 *    the defect as still present when it had already been fixed.
 * ⭐ THE GENERAL LESSON, and `tools/build-css.mjs` records the same one from the
 *    other direction: a stylesheet in this repository carries essay-length
 *    comments that quote real CSS. ANY regex run against it must strip comments
 *    first or it is reading documentation and calling it code. §2o2 below
 *    asserts the strip actually happened rather than assuming it. */
$pl_css = (string) preg_replace( '#/\*.*?\*/#s', '', $pl_css_raw );

bhp_kit_ok(
	'§2o2 ⛔ the CSS comment stripper actually strips (it is what keeps §2p-§2r honest)',
	'' !== $pl_css_raw
		&& false !== strpos( $pl_css_raw, 'FOUNDER-REPORTED LIVE' )
		&& false === strpos( $pl_css, 'FOUNDER-REPORTED LIVE' )
);

/* Pull the >=901px block out by brace-matching from the media query, so a
 * value that happens to appear elsewhere in a 57 KB stylesheet cannot be
 * mistaken for the desktop one. */
$desktop_css = '';
if ( '' !== $pl_css && preg_match( '/@media\s*\(\s*min-width:\s*901px\s*\)\s*\{/', $pl_css, $m, PREG_OFFSET_CAPTURE ) ) {
	$i     = $m[0][1] + strlen( $m[0][0] );
	$depth = 1;
	$len   = strlen( $pl_css );
	$start = $i;
	while ( $i < $len && $depth > 0 ) {
		if ( '{' === $pl_css[ $i ] ) {
			$depth++;
		} elseif ( '}' === $pl_css[ $i ] ) {
			$depth--;
		}
		$i++;
	}
	$desktop_css = substr( $pl_css, $start, $i - $start - 1 );
}

bhp_kit_ok(
	'§2o the >=901px block was located in the shipping stylesheet',
	'' !== $desktop_css && false !== strpos( $desktop_css, 'parent-landing-hero__photo' )
);

$pl_ar_w = $pl_ar_h = $pl_pos_y = null;
if ( preg_match( '/\.parent-landing-hero__photo\s*\{[^}]*?aspect-ratio:\s*([\d.]+)\s*\/\s*([\d.]+)/s', $desktop_css, $m2 ) ) {
	$pl_ar_w = (float) $m2[1];
	$pl_ar_h = (float) $m2[2];
}
if ( preg_match( '/object-position:\s*[\d.]+%\s+([\d.]+)%/', $desktop_css, $m3 ) ) {
	$pl_pos_y = (float) $m3[1];
}

bhp_kit_ok(
	'§2p the desktop panel declares both an aspect-ratio and an object-position',
	null !== $pl_ar_w && null !== $pl_ar_h && null !== $pl_pos_y,
	'aspect-ratio ' . ( $pl_ar_w ?? '?' ) . '/' . ( $pl_ar_h ?? '?' ) . ', object-position Y ' . ( $pl_pos_y ?? '?' ) . '%'
);

$src_w = (int) ( $photo['portrait_width'] ?? 0 );
$src_h = (int) ( $photo['portrait_height'] ?? 0 );

bhp_kit_ok(
	'§2s ⛔ the safe-zone coordinates still describe the shipping file (560x896)',
	560 === $src_w && 896 === $src_h,
	$src_w . 'x' . $src_h . ' — if this failed, the photograph changed and §2q/§2r must be re-measured, not adjusted'
);

if ( $pl_ar_w && $pl_ar_h && null !== $pl_pos_y && $src_w > 0 && $src_h > 0 ) {
	$panel_aspect = $pl_ar_w / $pl_ar_h;   // 0.800
	$src_aspect   = $src_w / $src_h;       // 0.625

	/* ⛔ THE PRECONDITION OF THE WHOLE CALCULATION. `cover` trims on the axis
	 *    the panel is PROPORTIONALLY LONGER in. Only while the panel is wider
	 *    than the file does the vertical component of `object-position` control
	 *    anything at all; flip that and this row fails loudly rather than
	 *    letting §2q/§2r pass on arithmetic that no longer applies. */
	bhp_kit_ok(
		'§2q ⭐ the panel is wider than the file, so the trim is VERTICAL and object-position Y governs',
		$panel_aspect > $src_aspect,
		sprintf( 'panel %.4f vs file %.4f', $panel_aspect, $src_aspect )
	);

	$visible_frac = $src_aspect / $panel_aspect;              // 0.78125
	$trim_px      = ( 1.0 - $visible_frac ) * $src_h;         // 196.0
	$window_top   = ( $pl_pos_y / 100.0 ) * $trim_px;         // 0.0 at 0%
	$window_bot   = $window_top + ( $visible_frac * $src_h ); // 700.0 at 0%

	$face_top_px     = 0.0;   // his forehead is at the file's own top edge
	$book_bottom_px  = 620.0; // byline + lower edge of the cover

	bhp_kit_ok(
		'§2r ⭐⭐ FACE SAFE ZONE: the crop keeps both faces AND the cover title in frame',
		$window_top <= $face_top_px + 0.5 && $window_bot >= $book_bottom_px,
		sprintf(
			'visible source rows %.1f..%.1f of %d; need top <=%.0f (forehead) and bottom >=%.0f (byline). '
				. 'At the 1.19.309 value of 42%% this was %.1f..%.1f — %.0fpx of his face above the frame.',
			$window_top,
			$window_bot,
			$src_h,
			$face_top_px,
			$book_bottom_px,
			0.42 * $trim_px,
			0.42 * $trim_px + ( $visible_frac * $src_h ),
			0.42 * $trim_px
		)
	);
} else {
	bhp_kit_ok( '§2q ⭐ the panel is wider than the file, so the trim is VERTICAL and object-position Y governs', false, 'inputs unavailable' );
	bhp_kit_ok( '§2r ⭐⭐ FACE SAFE ZONE: the crop keeps both faces AND the cover title in frame', false, 'inputs unavailable' );
}

/* ⚠ THE <=900px BAND IS DELIBERATELY NOT ASSERTED HERE, and the reason is that
 *   there is nothing to assert: the band file is 720x576 = 1.25 and the band
 *   box is `aspect-ratio: 5 / 4` = 1.25, so `cover` trims NOTHING and no
 *   `object-position` value can move a crop that does not exist. §2b already
 *   pins the band's landscape orientation. A row here would look like coverage
 *   and prove nothing. */

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ⭐⭐⭐ THE PLUMBING, PROVEN BY EXECUTION.
 *
 * ⛔⛔ THE MOST LOAD-BEARING SECTION IN THIS FILE. Everything else here is a
 *     source assertion. This one RUNS the filter chain WordPress will run when
 *     a real parent submits the hero form, and compares the resulting tag list
 *     to the one the inline panel produces. If they ever differ, the founder's
 *     paid traffic lands in a different Mailchimp segment from his organic
 *     traffic and he loses the ability to tell the two apart — silently, with
 *     no error anywhere.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§3 THE MAILCHIMP TAG TRIO — EXECUTED, NOT READ' );

$source_page = home_url( '/reluctant-reader-adventure-kit/' );

$resolve = function ( $context ) use ( $source_page ) {
	return apply_filters(
		'bhp_mailchimp_signup_tags',
		array(),
		$context,
		'parents_families',
		'reluctant_reader_adventure_kit',
		$source_page
	);
};

$hero_tags  = $resolve( 'parent_landing_hero' );
$panel_tags = $resolve( 'lead_magnet' );
$modal_tags = $resolve( 'lead_magnet_modal' );
$popup_tags = $resolve( 'parent_popup' );

bhp_kit_ok(
	'§3a ⭐⭐ the hero form resolves to "Source: Parent Landing Page"',
	in_array( 'Source: Parent Landing Page', $hero_tags, true ),
	implode( ' | ', $hero_tags )
);
bhp_kit_ok(
	'§3b ⭐⭐ the hero trio is BYTE-IDENTICAL to the inline panel trio',
	$hero_tags === $panel_tags,
	implode( ' | ', $hero_tags ) . '   vs   ' . implode( ' | ', $panel_tags )
);
bhp_kit_ok(
	'§3c ⭐ and to the modal trio',
	$hero_tags === $modal_tags,
	implode( ' | ', $modal_tags )
);
bhp_kit_ok(
	'§3d the trio is the existing three tags and NOT a fourth',
	array( 'Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Parent Landing Page' ) === $hero_tags,
	implode( ' | ', $hero_tags )
);
/* ⭐ THE CONTROL. If this row ever matched the rows above, the branch that
 *   distinguishes popup captures from landing captures would have silently
 *   stopped working and every assertion above would be passing for the wrong
 *   reason. A test whose control does not differ is not testing anything. */
bhp_kit_ok(
	'§3e ⭐ CONTROL: the popup context still resolves DIFFERENTLY',
	$popup_tags !== $hero_tags && in_array( 'Source: Parent Popup', $popup_tags, true ),
	implode( ' | ', $popup_tags )
);

/* The rest of the wiring, source-side. Renaming any of these changes WHICH FILE
 * IS SENT, not just what the page calls it. */
/*
 * ⚠ FOUR AGAIN, AND FOR A DIFFERENT AND MORE USEFUL REASON. Three forms carry
 *   each key, and the page's `parent_landing_view` dataLayer push carries a
 *   fourth copy of both — `lead_offer` and `audience` at lines 64 and 65.
 *   Verified by reading the file, not by relaxing until it passed.
 * ⭐ SO THE COUNT IS ASSERTED AT FOUR DELIBERATELY: it now also guards the
 *   ANALYTICS payload. If somebody renamed the magnet in the three forms and
 *   forgot the dataLayer, the GA4 event and the Mailchimp tag would disagree
 *   about what was offered, and this row would fail.
 */
bhp_kit_ok( '§3f the lead magnet key is unchanged on all three forms AND in the dataLayer', 4 === substr_count( $code, "'reluctant_reader_adventure_kit'" ), 'occurrences: ' . substr_count( $code, "'reluctant_reader_adventure_kit'" ) );
bhp_kit_ok( '§3g the audience is unchanged on all three forms AND in the dataLayer', 4 === substr_count( $code, "'parents_families'" ), 'occurrences: ' . substr_count( $code, "'parents_families'" ) );
bhp_kit_ok( '§3h the thank-you redirect is unchanged on all three forms', 3 === substr_count( $code, "'adventure_kit_thank_you'" ) );

/* ⛔ FUNNEL ISOLATION (`.claude/rules/funnels.md`). A sweep whose instruction is
 *   "make this page consistent with the popup" is exactly the kind of
 *   instruction that walks a popup's storage prefix onto a page by momentum. */
bhp_kit_ok(
	'§3i ⛔⛔ no funnel storage prefix and no popup analytics prefix is minted here',
	false === strpos( $code, 'bhp_parent_popup' )
		&& false === strpos( $code, 'bhp_mariana_popup' )
		&& false === strpos( $code, 'mariana_trench_classroom_guide' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE CONTENTS CLAIM. Nothing is promised that the real PDF lacks.
 *
 * ⭐ SOURCE OF THE CONTENTS, and it is second-hand, which is stated rather than
 *    smoothed over: the 1.19.296 lane opened the live
 *    `Reluctant-Reader-Adventure-Kit-1.pdf` from the production document root
 *    and read all seven pages — one real chapter (Chapter 7, "The Swordfish",
 *    from The Mariana Trench), a printable explorer activity, and "Three Ways
 *    to Make This Feel Like an Adventure", three tips to the PARENT. ⛔ THIS
 *    PASS DID NOT RE-OPEN THAT PDF. The count "three" is SOURCED, not observed
 *    here, and the handoff says so.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§4 THE CONTENTS CLAIM' );

bhp_kit_ok(
	'§4a ⛔ nothing is promised that the Kit does not contain',
	0 === preg_match( '/\b(workbook|worksheets?|audiobook|audio ?book|poster|stickers?|lesson plans?|flashcards?|curriculum)\b/i', $code )
);
bhp_kit_ok(
	'§4b the three real contents are all named',
	false !== stripos( $code, 'Chapter 7 from The Mariana Trench' )
		&& false !== stripos( $code, 'printable explorer activity' )
		&& false !== stripos( $code, 'ways to make it feel like an adventure' )
);
bhp_kit_ok(
	'§4c ⛔ no duration claim (retired 2026-08-03 under his own rule)',
	0 === preg_match( '/\b\d+\s*(?:-|\s)?\s*(?:minute|min|hour)s?\b/i', $code )
);
bhp_kit_ok(
	'§4d ⛔ reading age is 6 to 9, never 5 to 9',
	false === strpos( $code, '5–9' ) && false === strpos( $code, '5 to 9' ) && false === strpos( $code, '5-9' )
);
bhp_kit_ok(
	'§4e ⛔ no fabricated rating, review count, award or scarcity claim',
	0 === preg_match( '/\b(\d+\s*(?:star|reviews?|ratings?)|award-winning|best-?sell\w*|hurry|only \d+ left|limited time)\b/i', $code )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · ⭐ STANDING RULES §26 — THE AFFILIATE COUNT-DECREASE TEST.
 *
 * ⚠ THIS IS THE HALF A PHP ASSERTION CAN HONESTLY PERFORM, AND THE LIMIT IS
 *   STATED RATHER THAN HIDDEN. §26 requires counting affiliate anchors in the
 *   RENDERED output before and after. This section can only prove that the
 *   TEMPLATE introduces and removes none, which is a necessary condition and
 *   not a sufficient one. The rendered before/after counts are a browser
 *   measurement and are in the handoff with the numbers.
 * ⛔ A COUNT THAT WAS NOT ACTUALLY RUN IS A FABRICATED CHECK and sits in the
 *   same failure class as a fabricated review. "Not checked" would have been an
 *   acceptable result; a claimed check is not.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§5 AFFILIATE PRESERVATION (Standing Rules §26)' );

bhp_kit_ok(
	'§5a the template contains no affiliate anchor to lose (before and after both zero)',
	0 === preg_match_all( '/amzn\.to|amazon\.[a-z.]+\/dp\/|[?&]tag=/i', $code )
);
bhp_kit_ok(
	'§5b ⛔ this release removed no anchor of any kind that carried a tracking parameter',
	0 === preg_match( '/[?&]tag=/i', $code )
);
/*
 * ⚠ THE FIRST VERSION OF THIS ROW COUNTED `amzn.to` OCCURRENCES AND EXPECTED
 *   THREE. It found six, and the six are all legitimate — three affiliate URLs
 *   at functions.php 4993-4995, plus one explanatory comment (6688) and two
 *   guards in the affiliate-preservation code itself (6725, 6740) that MATCH on
 *   the string in order to protect it. Verified by reading those lines.
 *
 * ⛔⛔ A COUNT OF A SUBSTRING WAS THE WRONG INSTRUMENT ANYWAY, AND §26 SAYS SO
 *     IN ITS OWN WORDS: "EQUAL IS NOT AUTOMATICALLY PASS… The tracking code is
 *     the revenue; check that the code is intact, not merely that a link
 *     exists." So the row now asserts THE THREE ACTUAL SHORTLINKS, each by its
 *     full path. A rewrite that swapped one shortlink for another resolving to
 *     the same ASIN would have passed the old row and fails this one.
 *
 * ⚠ AND THE HONEST LIMIT, because §26 states it and this suite must not imply
 *   more than it checks: `amzn.to` links hide their `tag=` behind a redirect,
 *   so NO source-side assertion can confirm the tracking code survives. That is
 *   resolution, or the founder's own Associates dashboard, which is his
 *   instrument and not an agent's. This row proves the links are present and
 *   unaltered in the theme. It does not prove they still earn.
 */
$fn_src = (string) file_get_contents( get_template_directory() . '/functions.php' );
bhp_kit_ok(
	'§5c ⭐ all three Amazon shortlinks are present and byte-unaltered in the theme',
	function_exists( 'bhp_get_series_adventures' )
		&& false !== strpos( $fn_src, 'https://amzn.to/4svChYL' )
		&& false !== strpos( $fn_src, 'https://amzn.to/4mptuGv' )
		&& false !== strpos( $fn_src, 'https://amzn.to/4va9me7' )
);
bhp_kit_ok(
	'§5d ⛔ the affiliate-preservation guards themselves are still installed',
	false !== strpos( $fn_src, "stripos( \$content, 'amzn.to' )" )
		|| false !== strpos( $fn_src, "stripos(\$content, 'amzn.to')" )
);


/* ═══════════════════════════════════════════════════════════════════════════
 * §5e · ⭐⭐ THE DESKTOP FOLD BUDGET — theme 1.19.311,
 *        `CYCLE167-LD-KIT-FOLD-FIX`. Founder-reported, RELAYED by `chief-of-staff`:
 *        "Also the CTA is not above the fold on desktop"
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ READ THIS BEFORE READING A PASS BELOW. THESE ASSERTIONS DO NOT MEASURE
 *    THE FOLD. PHP has no layout engine and cannot know where the submit
 *    button's bottom edge lands. What they do is guard the CSS CONTRACT that
 *    produced the measured result — so that a later edit which quietly puts
 *    the padding, the leading or the centring back FAILS HERE instead of
 *    shipping to a paid landing page unnoticed.
 *
 * ⭐ THE MEASUREMENT ITSELF is a browser instrument, not this file:
 *      tests/js/kit-fold-harness.js
 *    run at the staged URL, asserting `getBoundingClientRect().bottom +
 *    scrollY` on the submit control against a stated viewport height. Its
 *    recorded output for this release is in the release record. A PASS in
 *    this section is evidence that the RULES ARE STILL THERE, and is not
 *    evidence that the button is above the fold.
 *
 * ⭐ THE BUDGET BEING GUARDED: first name + email + submit bottom inside
 *    630px of viewport (1366x768 laptop, the binding case) with the sticky
 *    nav rendered, at every width 901..2560. The 1920x1080 budget (~940px)
 *    was ALREADY met at 1.19.309 with 306px of slack and is not the target —
 *    §5e-f guards against anyone re-deriving the weaker one.
 */
bhp_kit_head( '§5e DESKTOP FOLD BUDGET (CSS CONTRACT — NOT A MEASUREMENT)' );

$bhp_pl_css_raw  = (string) file_get_contents( get_template_directory() . '/assets/css/parent-landing.css' );
$bhp_fold_marker = 'CYCLE167-LD-KIT-FOLD-FIX';
$bhp_fold_pos    = strpos( $bhp_pl_css_raw, $bhp_fold_marker );
$bhp_fold_raw    = false === $bhp_fold_pos ? '' : substr( $bhp_pl_css_raw, $bhp_fold_pos );

/*
 * ⭐⭐ COMMENTS ARE STRIPPED BEFORE ANY ABSENCE IS ASSERTED, AND THIS DESK
 *    LEARNED IT THE USEFUL WAY: the first draft of §5e-p FAILED against its
 *    own correct CSS, because the block's commentary EXPLAINS that it does not
 *    touch `object-position`, `aspect-ratio` or `max-height` — and a raw
 *    `strpos` cannot tell a promise not to do a thing from the thing itself.
 * ⛔ AN ABSENCE ASSERTION OVER UNSTRIPPED SOURCE IS NOT AN ABSENCE ASSERTION.
 *    It is a search for a word, and in a codebase whose house style is heavy
 *    commentary it will be wrong in whichever direction the prose happens to
 *    fall. Every check below therefore runs against DECLARATIONS ONLY.
 */
$bhp_css_strip  = static function ( $css ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', (string) $css );
};

/*
 * ⛔⛔ AND THE SECOND HALF OF THE SAME LESSON, WHICH THE FIRST STAGING RUN OF
 *    THIS SUITE CAUGHT AND THE LOCAL DRY-RUN DID NOT — recorded because the
 *    near-miss is more instructive than the fix.
 *    `$bhp_fold_raw` begins at the MARKER, and the marker lives INSIDE the
 *    block's own opening comment. So the substring starts mid-comment: its
 *    comment-OPENING delimiter is behind it, the strip regex never sees a
 *    comment to open, and the entire header comment — every word of prose
 *    promising not to touch `object-position`, `aspect-ratio` or `max-height`
 *    — survived into the "declarations only" text. §5e-p duly FAILED against
 *    CSS that was completely correct.
 * ⭐ THE FIX: drop the partial comment head FIRST (everything up to the first
 *    comment-CLOSING delimiter), then strip whole comments.
 * ⭐ THE TRANSFERABLE LESSON: a regex
 *    that assumes balanced delimiters is wrong the moment you hand it a
 *    substring, and a dry-run over the WHOLE file cannot expose that — the
 *    dry run started at byte 0 and the real assertion did not.
 */
$bhp_fold_body  = (string) preg_replace( '#^.*?\*/#s', '', $bhp_fold_raw, 1 );
$bhp_pl_css     = $bhp_css_strip( $bhp_pl_css_raw );
$bhp_fold_block = $bhp_css_strip( $bhp_fold_body );

bhp_kit_ok(
	'§5e-a the 1.19.311 fold block is present in parent-landing.css',
	'' !== $bhp_fold_raw
);

/*
 * ⭐⭐ THE LOAD-BEARING DECLARATION. §4 sets `align-items: center` on the hero
 *    grid. Once the copy column is shorter than the 560px photo panel, a
 *    centred copy column gives back HALF of every pixel this release saves.
 *    Measured: 76px of savings delivered 76px of lift with this rule and
 *    would have delivered 38 without it. If one assertion in this section is
 *    worth keeping, it is this one.
 */
bhp_kit_ok(
	'§5e-b ⭐⭐ the copy column is start-aligned (without it every saving halves)',
	(bool) preg_match( '/\.parent-landing-hero__copy\s*\{[^}]*align-self:\s*start/', $bhp_fold_block )
);

$bhp_fold_rules = [
	'§5e-c hero top padding is pinned to 20px'          => '/\.parent-landing-hero__grid\s*\{[^}]*padding-block-start:\s*20px/',
	'§5e-d headline leading tightened to 1.05'          => '/parent-landing-hero h1\s*\{[^}]*line-height:\s*1\.05/',
	'§5e-e lead-sentence leading tightened to 1.4'      => '/parent-landing__lead\s*\{[^}]*line-height:\s*1\.4/',
	'§5e-f capture block margin-top is 14px'            => '/\.parent-landing-hero__capture\s*\{[^}]*margin-top:\s*14px/',
	'§5e-g form row-gap is 9px'                         => '/\.parent-landing-hero__form\s*\{[^}]*row-gap:\s*9px/',
	'§5e-h field label-to-input gap is 5px'             => '/acquisition-form__field\s*\{[^}]*row-gap:\s*5px/',
	'§5e-i field label leading is 1.35'                 => '/acquisition-form__field label\s*\{[^}]*line-height:\s*1\.35/',
	'§5e-j text/email inputs take 9px block padding'    => '/input\[type="email"\]\s*\{[^}]*padding-top:\s*9px/',
	'§5e-k submit control takes 15px block padding'     => '/acquisition-form__submit\s*\{[^}]*padding-top:\s*15px/',
];
foreach ( $bhp_fold_rules as $bhp_label => $bhp_re ) {
	bhp_kit_ok( $bhp_label, (bool) preg_match( $bhp_re, $bhp_fold_block ) );
}

/*
 * ⭐⭐ THE SCOPE PROOF, AND IT IS MECHANICAL RATHER THAN A COMMENT.
 *    Every declaration above must sit inside `min-width: 901px`, because that
 *    is what makes "mobile is unchanged" true BY CONSTRUCTION instead of by
 *    hope. The block is asserted to open exactly one media query, and for it
 *    to be the desktop one. A stray rule added outside it — the natural way
 *    this regresses — fails here.
 */
bhp_kit_ok(
	'§5e-l ⛔ the fold block opens exactly ONE media query',
	1 === substr_count( $bhp_fold_block, '@media' )
);
bhp_kit_ok(
	'§5e-m ⛔ and that media query is the desktop breakpoint (min-width: 901px)',
	(bool) preg_match( '/@media\s*\(\s*min-width:\s*901px\s*\)/', $bhp_fold_block )
);

/*
 * ⭐⭐ THE CROP FIX MUST SURVIVE THIS RELEASE. `CYCLE167-LD-KIT-PHOTO-CROP-FIX`
 *    (1.19.310) is correct only while the photo panel keeps a constant 0.800
 *    shape — its own note: "the panel only ever SCALES; it never CHANGES
 *    SHAPE." The fold fix is required not to touch the photograph, and these
 *    three assertions are what stop a future spacing pass from doing so while
 *    believing it is only adjusting whitespace.
 */
bhp_kit_ok(
	'§5e-n ⛔ the crop fix survives: object-position 50% 0% still set at desktop',
	(bool) preg_match( '/\.parent-landing-hero__photo img\s*\{\s*object-position:\s*50%\s+0%/', $bhp_pl_css )
);
bhp_kit_ok(
	'§5e-o ⛔ the photo panel keeps its 4/5 aspect and 560px cap (the crop premise)',
	false !== strpos( $bhp_pl_css, 'aspect-ratio: 4 / 5' )
		&& false !== strpos( $bhp_pl_css, 'max-height: 560px' )
);
bhp_kit_ok(
	'§5e-p ⛔ the fold block itself DECLARES nothing about the photograph',
	'' !== $bhp_fold_raw
		&& false === strpos( $bhp_fold_block, 'object-position' )
		&& false === strpos( $bhp_fold_block, 'aspect-ratio' )
		&& false === strpos( $bhp_fold_block, 'max-height' )
		&& false === strpos( $bhp_fold_block, 'mask-image' )
);

/*
 * ⛔ AND THE COPY IS UNCUT — the rail the brief put above every other one.
 *    Spacing first, scale second, copy never. The headline, the lead sentence
 *    and the button label are asserted verbatim in §1/§2 of this suite and in
 *    test-cycle167-capture-copy.php; this row asserts the one thing those
 *    cannot: that the fold release did not reach for the font size.
 */
bhp_kit_ok(
	'§5e-q ⛔ no font-size is DECLARED anywhere in the fold block (scale never paid)',
	'' !== $bhp_fold_raw && false === strpos( $bhp_fold_block, 'font-size' )
);
/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THIS SUITE MUTATED NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_kit_head( '§6 NO SIDE EFFECTS' );

bhp_kit_ok(
	'§6a no founder-photo filter was left registered by this run',
	! has_filter( 'bhp_founder_photo_alt' ) && ! has_filter( 'bhp_niece_canon_forbidden_terms' )
);
bhp_kit_ok(
	'§6b ⛔ the theme version on disk is unchanged by running tests',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.308', '>=' )
);

echo "\n============================================================\n";
printf(
	"KIT PAID-AD LANDING: %d passed, %d failed\n",
	(int) $GLOBALS['bhp_kit_pass'],
	(int) $GLOBALS['bhp_kit_fail']
);
echo "============================================================\n";
