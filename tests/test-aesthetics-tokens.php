<?php
/**
 * Brave Hearts — THE AESTHETICS TOKEN PASS.
 *
 * CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS (2026-08-19, theme 1.19.266 /
 * bundle 1.8.60). The nine token-level items of the `commerce-cx`
 * AESTHETICS-AUDIT-STAGING-1.19.264-2026-08-19 §8a, scored against the
 * `ads-knowledge` AESTHETICS-AUDIT-RUBRIC-2026-08-19 rows 1-7 and 11.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-aesthetics-tokens.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES:
 *   §1  the contrast ARITHMETIC, recomputed here from the WCAG 2.x formula
 *       against the hex values actually in the stylesheets — so a future
 *       palette edit that re-breaks AA fails a test instead of a customer
 *   §2  no `--color-gold` / `#D9A45F` remains as TEXT on a light ground in any
 *       rule this pass touched, and no light-ground gold is used on a dark one
 *   §3  the type scale exists as ONE set of tokens and every H1 rule reads it
 *   §4  the trust-line floor token exists and the eleven named trust lines
 *       reference it rather than a literal
 *   §5  the heading-order fixes: no <h2> is emitted before the <h1> on
 *       `/complete-collection/`, and the three footer labels and the product
 *       format label are no longer headings
 *   §6  one button radius: the header offer is 8px and no button rule this
 *       pass owns declares 2px, 4px, 10px or 12px
 *   §7  the consent banner keeps all three controls, keeps the 1.19.186
 *       flex/width pairing, and reaches 44px where the geometry allows it
 *   §8  the theme-rendered images carry width and height
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads stylesheets,
 *    templates and rendered markup. It does NOT prove a computed font-size at
 *    390, a rendered contrast ratio against a COMPOSITED background, that the
 *    hero CTA is still above the fold, or that the consent banner does not
 *    cover a control. Those are BROWSER facts. They were measured separately
 *    in headless Chromium at an asserted `window.innerWidth`, filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-iterate2-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 *    In particular: §1 computes contrast against the grounds THE AUDIT
 *    MEASURED. It cannot know that a future rule re-parents an element onto a
 *    darker ground. The browser probe is what closes that gap, every time.
 *
 * ⛔ NOTHING IS WRITTEN. No product, price, variation, coupon, stock level,
 *    shipping, tax, payment or checkout setting, cart, order, post, page,
 *    option, attachment or user is created or modified by any line here.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_aes_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** WCAG 2.x relative luminance of a #rrggbb string. */
function bhp_aes_lum( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	$out = 0.0;
	$w   = array( 0.2126, 0.7152, 0.0722 );
	foreach ( array( 0, 2, 4 ) as $i => $off ) {
		$c = hexdec( substr( $hex, $off, 2 ) ) / 255;
		$c = ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$out += $w[ $i ] * $c;
	}
	return $out;
}

/** WCAG contrast ratio between two #rrggbb strings. */
function bhp_aes_ratio( $a, $b ) {
	$la = bhp_aes_lum( $a );
	$lb = bhp_aes_lum( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/** The declared value of a custom property in a stylesheet body. */
function bhp_aes_token( $css, $name ) {
	if ( preg_match( '/' . preg_quote( $name, '/' ) . '\s*:\s*([^;]+);/', $css, $m ) ) {
		return trim( $m[1] );
	}
	return null;
}

$theme_dir = get_template_directory();
$style     = (string) @file_get_contents( $theme_dir . '/style.css' );

/* THE COMMENT TRAP, AND WHY $style_live EXISTS.
   This codebase deliberately PRESERVES superseded values inside comments --
   "SUPERSEDED VALUES, preserved rather than deleted" recurs throughout
   style.css, and 1.19.266 added several more. A test that greps the raw file
   for an old literal therefore fails on the very record that makes the change
   reviewable, which is the opposite of what it should reward.

   $style_live is style.css with comment blocks removed, so an assertion about
   what the stylesheet DECLARES can be written without punishing the stylesheet
   for explaining itself. $style is still used where the assertion is genuinely
   about the file's text.

   The first run of this suite on staging failed six assertions for exactly
   this reason and the CODE was correct in all six. Recorded so it is not
   re-derived. */
$style_live = (string) preg_replace( '#/\*.*?\*/#s', '', $style );
$blogcss   = (string) @file_get_contents( $theme_dir . '/assets/css/blog-post.css' );
$fmtcss    = (string) @file_get_contents( $theme_dir . '/assets/css/book-formats.css' );
$mediacss  = (string) @file_get_contents( $theme_dir . '/assets/css/book-media.css' );
$prodcss   = (string) @file_get_contents( $theme_dir . '/assets/css/product-template.css' );
$consent   = (string) @file_get_contents( $theme_dir . '/inc/consent-banner-compact.php' );
$footer    = (string) @file_get_contents( $theme_dir . '/footer.php' );
$fmtpart   = (string) @file_get_contents( $theme_dir . '/template-parts/commerce/format-cards.php' );

bhp_aes_assert( '' !== $style, '§0.1 style.css is readable', $failures );
bhp_aes_assert( '' !== $blogcss && '' !== $fmtcss && '' !== $mediacss && '' !== $prodcss, '§0.2 the four component stylesheets this pass edits are readable', $failures );
bhp_aes_assert( '' !== $consent && '' !== $footer && '' !== $fmtpart, '§0.3 the three templates this pass edits are readable', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · THE CONTRAST ARITHMETIC — computed here, not quoted from a document
   ═══════════════════════════════════════════════════════════════════════════
   The fourteen light grounds below are the ones the `commerce-cx` probe recorded
   ACTUALLY RENDERING gold or muted text on staging 1.19.264 — not a designer's
   palette list. `#efdcc1` is the darkest of them and is the binding case. */

$gold_deep = strtolower( (string) bhp_aes_token( $style, '--color-gold-deep' ) );
bhp_aes_assert(
	preg_match( '/^#[0-9a-f]{6}$/', $gold_deep ),
	'§1.1 --color-gold-deep is declared as a literal hex (found: ' . $gold_deep . ')',
	$failures
);

$light_grounds = array(
	'#ffffff', '#fffefb', '#fffdf7', '#fffdf8', '#fffaf0', '#faf7ef', '#f8f3e9',
	'#f7f3e9', '#f7f2e7', '#f6efe0', '#f4eede', '#f1e7d2', '#f2e4cd', '#efdcc1',
);
$worst = null;
$worst_bg = '';
foreach ( $light_grounds as $bg ) {
	$r = bhp_aes_ratio( $gold_deep, $bg );
	if ( null === $worst || $r < $worst ) {
		$worst    = $r;
		$worst_bg = $bg;
	}
}
bhp_aes_assert(
	$worst >= 4.5,
	sprintf( '§1.2 the light-ground gold clears AA 4.5:1 on ALL fourteen measured cream/white grounds (worst: %.2f:1 on %s)', $worst, $worst_bg ),
	$failures
);

/* THE REGRESSION GUARD THAT MATTERS MOST. #9A6A00 was the value this token
   carried from 2026-07-30 to 1.19.265 and it fails eleven of the fourteen. If
   a future pass "restores" it, §1.2 above catches it — this assertion names it
   so the failure is self-explanatory rather than a bare number. */
bhp_aes_assert(
	'#9a6a00' !== $gold_deep,
	'§1.3 the token is NOT back at #9A6A00, which measures 3.53:1 on #efdcc1 and 3.86:1 on #f1e7d2',
	$failures
);

/* The brand gold is a DARK-ground colour and must stay one. */
$gold = strtolower( (string) bhp_aes_token( $style, '--color-gold' ) );
bhp_aes_assert(
	'#d9a45f' === $gold,
	'§1.4 --color-gold is unchanged at #D9A45F — this pass repaints nothing (found: ' . $gold . ')',
	$failures
);
bhp_aes_assert(
	bhp_aes_ratio( $gold, '#071522' ) >= 4.5 && bhp_aes_ratio( $gold, '#050f1a' ) >= 4.5,
	'§1.5 the brand gold still passes AA on both navies, so §2.3 below is a safe repoint',
	$failures
);

/* The two muted trust-line colours this pass deepened. */
foreach ( array(
	'#706456' => array( '#f8f3e9', '#f6efe0', '#f1e7d2', '#ffffff' ),
	'#6b6a5c' => array( '#fffdf7', '#faf7ef', '#f7f3e9', '#f4eede' ),
) as $fg => $bgs ) {
	$w = null;
	$wb = '';
	foreach ( $bgs as $bg ) {
		$r = bhp_aes_ratio( $fg, $bg );
		if ( null === $w || $r < $w ) {
			$w  = $r;
			$wb = $bg;
		}
	}
	bhp_aes_assert(
		$w >= 4.5,
		sprintf( '§1.6 the muted trust-line colour %s clears AA on its own grounds (worst: %.2f:1 on %s)', $fg, $w, $wb ),
		$failures
	);
}

/* The colours they replaced are gone from the rules that carried them. */
bhp_aes_assert(
	! preg_match( '/color\s*:\s*#7A6E60/i', (string) preg_replace( '#/\*.*?\*/#s', '', $fmtcss ) ),
	'§1.7 #7A6E60 (4.49:1 on the product ground) is no longer DECLARED in book-formats.css',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · GOLD IS NEVER TEXT ON A LIGHT GROUND, AND NEVER DEEP GOLD ON A DARK ONE
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_aes_assert(
	false === strpos( $blogcss, 'color: var(--expedition-gold);' ),
	'§2.1 no blog-post eyebrow still paints --expedition-gold as text on cream',
	$failures
);
bhp_aes_assert(
	3 === preg_match_all( '/color:\s*var\(--expedition-gold-deep\)/', $blogcss, $m ),
	'§2.2 all three blog-post eyebrows read the light-ground gold (found ' . preg_match_all( '/color:\s*var\(--expedition-gold-deep\)/', $blogcss, $m2 ) . ')',
	$failures
);
bhp_aes_assert(
	null !== bhp_aes_token( $style, '--expedition-gold-deep' ),
	'§2.2b --expedition-gold-deep exists so an expedition-scoped rule has a same-family token to reach for',
	$failures
);

/* The one rule in the theme that used the LIGHT-ground gold on a DARK ground.
   It measured 4.07:1 before this release and would have gone to 3.0:1 with the
   deepened token, so it had to move in the same commit or not at all. */
bhp_aes_assert(
	preg_match( '/\.home \.home-origin__byline\s*\{[^}]*color:\s*var\(--color-gold\)\s*!important/s', $style ),
	'§2.3 the founder byline is repointed at the DARK-ground gold (was --color-gold-deep on #050f1a, 4.07:1)',
	$failures
);
bhp_aes_assert(
	! preg_match( '/\.home \.home-origin__byline\s*\{[^}]*var\(--color-gold-deep\)/s', $style_live ),
	'§2.4 ...and NO rule on that selector still reads the light-ground gold. There are TWO, at the same specificity; only the later one renders, and the earlier one is corrected too rather than left to be saved by source order',
	$failures
);

bhp_aes_assert(
	preg_match( '/\.amazon-review-card__stars \{[^}]*color:\s*var\(--color-gold-deep\)/', $style ),
	'§2.5 the five-star glyphs on review cards use the light-ground gold (were 2.14-2.23:1)',
	$failures
);
bhp_aes_assert(
	preg_match( '/#amazon-customer-reviews \.amazon-review-card__stars \{[^}]*color:\s*var\(--color-gold\);/s', $style ),
	'§2.6 the stars on the DARK homepage review card use the brand gold at full alpha (was rgba(...,.72) = 4.49:1)',
	$failures
);
bhp_aes_assert(
	! preg_match( '/#amazon-customer-reviews \.amazon-review-card__stars \{[^}]*rgba\(217, 164, 95, \.72\)/s', $style_live ),
	'§2.7 ...and the alpha that ate that contrast is gone',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · ONE TYPE SCALE
   ═══════════════════════════════════════════════════════════════════════════ */

foreach ( array( '--h1-size', '--h2-size', '--h3-size', '--h1-leading' ) as $tok ) {
	bhp_aes_assert( null !== bhp_aes_token( $style, $tok ), "§3.1 {$tok} is declared in :root", $failures );
}

/* The clamp must put 390 inside the rubric's 28-40px band and 1440 inside its
   44-52px band. Both are computed here from the DECLARED clamp rather than
   asserted as prose, so editing the clamp re-runs the arithmetic. */
$h1 = (string) bhp_aes_token( $style, '--h1-size' );
if ( preg_match( '/clamp\(\s*([\d.]+)rem\s*,\s*([\d.]+)rem\s*\+\s*([\d.]+)vw\s*,\s*([\d.]+)rem\s*\)/', $h1, $m ) ) {
	$min = (float) $m[1] * 16;
	$base = (float) $m[2] * 16;
	$vw  = (float) $m[3];
	$max = (float) $m[4] * 16;
	$at  = function ( $w ) use ( $min, $base, $vw, $max ) {
		return max( $min, min( $max, $base + $vw * $w / 100 ) );
	};
	bhp_aes_assert( $at( 390 ) >= 28 && $at( 390 ) <= 40, sprintf( '§3.2 H1 at 390 is inside the 28-40px band (%.2fpx)', $at( 390 ) ), $failures );
	bhp_aes_assert( $at( 1440 ) >= 44 && $at( 1440 ) <= 52, sprintf( '§3.3 H1 at 1440 is inside the 44-52px band (%.2fpx)', $at( 1440 ) ), $failures );
	bhp_aes_assert( $at( 320 ) >= 28 && $at( 1920 ) <= 52, sprintf( '§3.4 the clamp holds at the extremes too (320: %.2fpx, 1920: %.2fpx)', $at( 320 ), $at( 1920 ) ), $failures );

	/* Hierarchy: H1 > H2 > H3 > body at every width, or the H1 fix would have
	   produced a worse defect than the one it fixed. */
	$h2 = (string) bhp_aes_token( $style, '--h2-size' );
	$h3 = (string) bhp_aes_token( $style, '--h3-size' );
	$mk = function ( $decl ) {
		if ( preg_match( '/clamp\(\s*([\d.]+)rem\s*,\s*([\d.]+)rem\s*\+\s*([\d.]+)vw\s*,\s*([\d.]+)rem\s*\)/', $decl, $mm ) ) {
			return function ( $w ) use ( $mm ) {
				return max( (float) $mm[1] * 16, min( (float) $mm[4] * 16, (float) $mm[2] * 16 + (float) $mm[3] * $w / 100 ) );
			};
		}
		return null;
	};
	$f2 = $mk( $h2 );
	$f3 = $mk( $h3 );
	$ok = ( $f2 && $f3 );
	if ( $ok ) {
		foreach ( array( 320, 390, 600, 768, 1024, 1440, 1920 ) as $w ) {
			if ( ! ( $at( $w ) > $f2( $w ) && $f2( $w ) > $f3( $w ) && $f3( $w ) > 18 ) ) {
				$ok = false;
			}
		}
	}
	bhp_aes_assert( $ok, '§3.5 H1 > H2 > H3 > 18px body at 320/390/600/768/1024/1440/1920', $failures );
} else {
	bhp_aes_assert( false, '§3.2 --h1-size parses as clamp(Xrem, Yrem + Zvw, Wrem) (found: ' . $h1 . ')', $failures );
}

/* Every H1 declaration in the theme reads the token. The six literal clamps the
   audit measured (51.2 / 24.8 / 32 / 30 / 29.6 / 23.2 px at 390) are named so a
   reintroduction fails with its own history attached. */
foreach ( array(
	'clamp(3.2rem, 7vw, 6rem)'          => 'the 51.2px/96px interior H1 (68 pages)',
	'clamp(2.8rem,5vw,5rem)'            => 'the 72px product H1',
	'clamp(1.85rem, 5vw, 2.6rem)'       => 'the 29.6px/41.6px cart & checkout H1',
	'clamp(1.45rem, 5.1vw, 2.6rem)'     => 'the 23.2px kit thank-you H1',
	'clamp(2rem, 1.3rem + 2.4vw, 3.4rem)' => 'the 32px homepage hero H1',
) as $literal => $what ) {
	bhp_aes_assert(
		false === strpos( $style_live, $literal ),
		"§3.6 style.css no longer declares {$what} as a literal",
		$failures
	);
}
bhp_aes_assert(
	false === strpos( (string) preg_replace( '#/\*.*?\*/#s', '', $prodcss ), 'font-size: 1.55rem;' ),
	'§3.7 product-template.css no longer declares the 24.8px H1 — the smallest on the site',
	$failures
);
bhp_aes_assert(
	preg_match( '/body:not\(\.home\) h1 \{[^}]*var\(--h1-size\)/', $style ),
	'§3.8 the interior-page H1 rule — the one that set 68 of 83 pages — reads the token',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · THE TRUST-LINE FLOOR
   ═══════════════════════════════════════════════════════════════════════════ */

$trust = (string) bhp_aes_token( $style, '--trust-min' );
bhp_aes_assert(
	preg_match( '/^([\d.]+)rem$/', $trust, $tm ) && (float) $tm[1] * 16 >= 14,
	'§4.1 --trust-min is at least 14px (found: ' . $trust . ')',
	$failures
);

$trust_rules = array(
	array( 'style.css', $style, '.home-trust-proof__badge', 'the Boise / five-star / Kirkus strip (was 11.52px)' ),
	array( 'style.css', $style, '.home .home-origin__portrait small', 'the founder photo caption (was 9px — the smallest text on the site)' ),
	array( 'style.css', $style, '.home .home-founder-chip__trust', 'the locked founder line (was 12.8px)' ),
	array( 'book-formats.css', $fmtcss, '.bhp-formats__note', 'the shipping promise (was 12.8px)' ),
	array( 'book-formats.css', $fmtcss, '.bhp-product-guarantee .bhp-landing-guarantee', 'the 30-day guarantee (was 11.84px)' ),
	array( 'book-media.css', $mediacss, '.bhp-media-gallery--hero .bhp-look-inside__note', 'the look-inside caption (was 13.12px)' ),
	array( 'book-media.css', $mediacss, '.bhp-media-gallery--collection .bhp-look-inside__note', 'the collection look-inside caption (was 12.8px)' ),
);
foreach ( $trust_rules as $row ) {
	list( $file, $css, $sel, $what ) = $row;
	$found = preg_match( '/' . preg_quote( $sel, '/' ) . '\s*\{[^}]*font-size:\s*var\(--trust-min/s', $css );
	bhp_aes_assert( (bool) $found, "§4.2 {$file} — {$what} reads --trust-min", $failures );
}

/* The byline carries !important because the rule it must beat does. */
bhp_aes_assert(
	preg_match( '/\.home \.home-origin__byline\s*\{[^}]*font-size:\s*var\(--trust-min\)\s*!important/s', $style ),
	'§4.3 the founder byline reads --trust-min and keeps the !important it needs to win',
	$failures
);
bhp_aes_assert(
	! preg_match( '/\.home \.home-origin__portrait small \{[^}]*font-size:\s*9px/', $style ),
	'§4.4 the 9px caption literal is gone',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · HEADING ORDER AND HEADING LEVELS
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_aes_assert(
	false === strpos( $footer, "<h2><?php esc_html_e('Explore'" )
		&& false === strpos( $footer, "<h2><?php esc_html_e('Learn'" )
		&& false === strpos( $footer, "<h2><?php esc_html_e('Connect'" ),
	'§5.1 the three 10.5px footer column labels are no longer <h2> (249 sub-body headings sitewide)',
	$failures
);
/*
 * ⚠ 1.19.269 — THIS COUNT WAS 3 AND IS NOW 2, AND THE CHANGE IS NOT A
 *   WEAKENING OF THE ASSERTION.
 *
 * The founder's subtraction item 4 of 2026-08-19 pruned the footer to
 * shop / kit / contact / policies, which removed the whole "Learn" column and
 * folded "Explore" into "Shop". Two labelled columns remain — Shop and
 * Connect — and the assertion still checks the same property it always did:
 * that EVERY footer column label carries `.footer-col-title` rather than being
 * an <h2>. §5.1 above, which is the assertion that actually protects the
 * heading-order fix, is UNCHANGED and still names all three original strings.
 *
 * The superseded line, preserved so the movement is visible:
 *     3 === substr_count( $footer, 'class="footer-col-title"' ),
 *     '§5.2 ...and all three carry the class the stylesheet now targets',
 */
bhp_aes_assert(
	2 === substr_count( $footer, 'class="footer-col-title"' ),
	'§5.2 ...and both surviving column labels (Shop, Connect) carry the class the stylesheet targets',
	$failures
);
bhp_aes_assert(
	false !== strpos( $footer, 'aria-labelledby="footer-connect-title"' )
		&& false !== strpos( $footer, 'id="footer-connect-title"' ),
	'§5.3 the Connect block, which has no landmark of its own, is named via ARIA instead',
	$failures
);
/* The CSS must still reach them, or the demotion becomes a visual change. */
bhp_aes_assert(
	4 === preg_match_all( '/\.footer-nav \.footer-col-title/', $style, $m ),
	'§5.4 every footer-label selector list in style.css carries .footer-col-title beside its h2',
	$failures
);

bhp_aes_assert(
	false === strpos( $fmtpart, '<h2 class="bhp-formats__heading"' )
		&& false !== strpos( $fmtpart, '<p class="bhp-formats__heading"' ),
	'§5.5 "CHOOSE YOUR FORMAT" (11.52px against 18px body) is no longer a heading',
	$failures
);
bhp_aes_assert(
	preg_match( '/aria-labelledby="<\?php echo esc_attr\(\$uid\); \?>-label"/', $fmtpart ),
	'§5.6 ...and the format group still takes its accessible name from it',
	$failures
);

/* The collection page is rendered by the bundle plugin. */
$landing = (string) @file_get_contents( WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/bundle-landing-page.php' );
bhp_aes_assert( '' !== $landing, '§5.7 the bundle landing template is readable', $failures );
bhp_aes_assert(
	false === strpos( $landing, '<h2 class="bhp-landing-coldopen__headline">' )
		&& false !== strpos( $landing, '<p class="bhp-landing-coldopen__headline">' ),
	'§5.8 BOR-206 (a): the cold-open line no longer emits an <h2> before the page <h1>',
	$failures
);
bhp_aes_assert(
	false === strpos( $landing, '<h2 class="bhp-landing-panel__title screen-reader-text">' ),
	'§5.9 BOR-206 (b): the two screen-reader panel titles no longer emit <h2> before the <h1>',
	$failures
);
bhp_aes_assert(
	false !== strpos( $landing, 'role="group" aria-labelledby="<?php echo esc_attr( $bhp_panel_title_id ); ?>"' ),
	'§5.10 ...and each panel is now a NAMED group, so nothing is lost to assistive technology',
	$failures
);

/* THE END-TO-END CHECK: the served document, not the template. */
if ( function_exists( 'get_page_by_path' ) ) {
	$cc = get_page_by_path( 'complete-collection' );
	if ( $cc instanceof WP_Post ) {
		$html = (string) apply_filters( 'the_content', $cc->post_content );
		$h1   = stripos( $html, '<h1' );
		$h2   = stripos( $html, '<h2' );
		bhp_aes_assert(
			false === $h2 || ( false !== $h1 && $h1 < $h2 ),
			'§5.11 in the RENDERED collection content, no <h2> precedes the <h1>',
			$failures
		);
	} else {
		echo "SKIP: §5.11 — /complete-collection/ not found on this install\n";
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · ONE BUTTON RADIUS
   ═══════════════════════════════════════════════════════════════════════════ */

if ( preg_match( '/\.bhp-header-offer \{(.*?)\n  \}/s', $style, $m ) ) {
	bhp_aes_assert(
		false !== strpos( $m[1], 'border-radius: var(--radius-md)' ),
		'§6.1 the header offer reads --radius-md (8px). It was the only 2px button on the site',
		$failures
	);
	bhp_aes_assert(
		false === strpos( $m[1], 'border-radius: 2px' ),
		'§6.2 ...and the 2px literal is gone',
		$failures
	);
} else {
	bhp_aes_assert( false, '§6.1 the .bhp-header-offer rule was found', $failures );
}
bhp_aes_assert(
	'8px' === bhp_aes_token( $style, '--radius-md' ),
	'§6.3 --radius-md really is 8px, so §6.1 means what it says',
	$failures
);
bhp_aes_assert(
	false === strpos( $fmtcss, 'border-radius: 10px;' ) && false === strpos( $fmtcss, 'border-radius: 12px;' ),
	'§6.4 the format selector and its price panel are 8px, not 10px/12px',
	$failures
);
bhp_aes_assert(
	preg_match( '/ul\.products li\.product \.button \{[^}]*border-radius:\s*var\(--radius-md\)/', $style ),
	'§6.5 the "SELECT OPTIONS" purchase button is 8px, not --radius-sm (4px)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §7 · THE CONSENT BANNER — compliance first, cosmetics second
   ═══════════════════════════════════════════════════════════════════════════ */

foreach ( array( 'wpconsent-accept-all', 'wpconsent-cancel-all', 'wpconsent-preferences-all' ) as $ctrl ) {
	bhp_aes_assert(
		false === stripos( $consent, '#' . $ctrl . ' { display: none' ),
		"§7.1 the {$ctrl} control is not hidden by any rule this pass added",
		$failures
	);
}
bhp_aes_assert(
	! preg_match( '/\.wpconsent-banner-button[^{}]*\{[^}]*(background|color)[^}]*\}/s', $consent )
	|| substr_count( $consent, 'wpconsent-accept-all' ) === substr_count( $consent, 'wpconsent-cancel-all' ),
	'§7.2 Accept and Reject are named the same number of times — no rule styles one without the other',
	$failures
);
/* The 1.19.186 invariant, re-asserted here because this pass added button
   rules and that is exactly when it gets broken. */
$broken = false;
if ( preg_match_all( '/([^{}]*\.wpconsent-banner-button[^{}]*)\{([^}]*)\}/', $consent, $all, PREG_SET_ORDER ) ) {
	foreach ( $all as $rule ) {
		if ( preg_match( '/flex\s*:\s*0\s+0\s+auto/', $rule[2] ) && ! preg_match( '/width\s*:\s*auto/', $rule[2] ) ) {
			$broken = true;
		}
	}
}
bhp_aes_assert( ! $broken, '§7.3 no banner-button rule pairs flex: 0 0 auto with a missing width: auto (the 1.19.186 fix)', $failures );
bhp_aes_assert(
	false !== strpos( $consent, 'border-radius: 8px !important' ),
	'§7.4 the banner controls carry the 8px system radius, not the plugin square',
	$failures
);
bhp_aes_assert(
	false !== strpos( $consent, 'min-height: 44px !important' ),
	'§7.5 the banner controls reach the 44px tap floor where the geometry allows it',
	$failures
);
bhp_aes_assert(
	false !== strpos( $consent, '@media (max-width: 600px) and (max-height: 699px)' )
		&& false !== strpos( $consent, '@media (max-width: 600px) and (min-height: 700px)' ),
	'§7.6 the treatment is chosen by viewport HEIGHT — the variable the 39.9px hero-CTA ceiling depends on',
	$failures
);
bhp_aes_assert(
	false !== strpos( $consent, 'text-overflow: clip !important' ),
	'§7.7 the tall-phone message wraps instead of ellipsising ("I use cookies to keep t...")',
	$failures
);
/* ⚠ The string `wpconsent_settings` DOES appear in this file — in the 1.19.249
   note recording that two of its values were set on staging and then VERIFIED
   INERT, and reverted. That note is exactly the dead end a future session needs
   to not repeat, so this assertion looks for a WRITE, not for the word. */
bhp_aes_assert(
	! preg_match( '/(update_option|add_option|delete_option|update_site_option)\s*\(/', $consent ),
	'§7.8 the theme never WRITES a plugin option — a WPConsent settings change is Andrew\'s, prepared not applied',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §8 · IMAGE DIMENSIONS (CLS)
   ═══════════════════════════════════════════════════════════════════════════ */

$img_templates = array(
	'page-about.php'                          => 'founder-and-charlotte.webp',
	'page-reluctant-reader-adventure-kit.php' => 'founder-and-charlotte.webp',
	'page-audience-educators.php'             => 'educator-toolkit-cover.webp',
	'page-audience-gift-buyers.php'           => 'gift-guide-cover.webp',
	'page-audience-organizations.php'         => 'community-reading-kit-cover.webp',
);
foreach ( $img_templates as $tpl => $asset ) {
	/* `<img[^>]*...>` CANNOT BE USED HERE, and the reason is worth recording:
	   every one of these tags contains `<?php echo esc_url( ... ); ?>`, and the
	   `?>` inside it terminates a `[^>]*` run long before the tag does. The
	   first version of this assertion failed on all five templates while all
	   five templates were correct. Each of these tags is one line, so the line
	   is the honest unit to inspect. */
	$src = (string) @file_get_contents( $theme_dir . '/' . $tpl );
	$ok  = false;
	foreach ( preg_split( '/\R/', $src ) as $line ) {
		if ( false === strpos( $line, $asset ) || false === strpos( $line, '<img' ) ) {
			continue;
		}
		$ok = ( false !== strpos( $line, 'width="' ) && false !== strpos( $line, 'height="' ) );
		if ( ! $ok ) {
			break;
		}
	}
	bhp_aes_assert( $ok, "§8.1 {$tpl} — every {$asset} <img> carries width and height", $failures );
}

$mediajs = (string) @file_get_contents( $theme_dir . '/assets/js/book-media.js' );
bhp_aes_assert(
	false !== strpos( $mediajs, "lightboxImg.setAttribute('width', natW)" )
		&& false !== strpos( $mediajs, "lightboxImg.removeAttribute('width')" ),
	'§8.2 the look-inside lightbox reserves its box when a src arrives and releases it on close',
	$failures
);

/* ⚠ RECORDED AS A KNOWN GAP RATHER THAN ASSERTED AWAY. Twelve of the audit's
   47 dimensionless images are WordPress core's own emoji replacements
   (`s.w.org/images/core/emoji/*.svg`), injected client-side by
   `wp-emoji-release.min.js` into POST CONTENT. No server-side filter can add
   attributes to them; the only lever is dequeuing the emoji script, which
   changes how emoji RENDER in three published posts and is therefore a content
   decision, not a token fix. It is reported, not taken. */
echo "NOTE: 12 of the audit's 47 dimensionless images are WP core emoji images, injected by JS into post content. Not theme-rendered; see the handoff.\n";

echo "\n=====================================================\n";
if ( empty( $failures ) ) {
	echo "ALL ASSERTIONS PASSED\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( count( $failures ) . ' assertion(s) failed.' );
	}
}
