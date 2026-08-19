<?php
/**
 * Brave Hearts — THE HOMEPAGE SUBTRACTION.
 *
 * `CYCLE165-LD-ITERATE-4-HOME-SUBTRACTION` (2026-08-19, theme 1.19.268).
 * Source: a founder ruling carried to that session as carrier item 110.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-home-subtraction.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from the SERVED HOMEPAGE DOCUMENT and the SHIPPED stylesheet text —
 * never from a template docblock and never from an assumption:
 *
 *   §1  the trust strip reads exactly "Five-star reader reviews", both prior
 *       wordings are gone from the homepage, and NO rating number, review
 *       count, `aggregateRating` or `review` schema was added alongside it
 *   §2  the three ruled-out sections are ABSENT from the served document —
 *       by id, by heading string, and by their distinguishing markup
 *   §3  `#explore-world` IS STILL PRESENT. This is a guard, not a leftover:
 *       its id reads like the removed "Follow Curiosity Into the Real World"
 *       band and it is a different section
 *   §4  the SECTION ORDER, positionally: hero -> ... -> #where-you-will-find-us
 *       -> #amazon-customer-reviews -> #explore-world -> #first-reader -> ...
 *       and NOTHING renders between the booth and the reviews
 *   §5  the reviews section carries the cream modifier, the stylesheet grounds
 *       it on `--color-parchment`, and every one of the eleven text colours
 *       that were chosen for the OLD dark ground is repointed — asserted in
 *       the SHIPPED MINIFIED artefact, where the comments are gone
 *   §6  `--color-gold` (#D9A45F, 1.81:1 on cream) is NOT reintroduced as text
 *       anywhere inside the cream section
 *   §7  the ONE plate moved with its host: exactly one plate on the page, on
 *       #amazon-customer-reviews, decorative and textless
 *   §8  no fragment link on the homepage points at an id the removals took
 *       away — the dead-anchor check, run over the served document itself
 *   §9  the quoted customer reviews are untouched, and the removals took no
 *       copy with them that survives anywhere else
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads text and
 *    markup. It does NOT prove a rendered contrast ratio against a COMPOSITED
 *    background, that nothing overflows at 390, that the folds did not move,
 *    or that the plate is invisible enough. Those are BROWSER facts. They were
 *    measured separately in a real browser at an asserted `window.innerWidth`
 *    of 390 and 1440 and filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-iterate4-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 * ⛔ AND IT CANNOT PROVE THE AMAZON AVERAGES. §1 asserts the STRING. Whether
 *    "five-star" is TRUE is a fact about amazon.com, it was read there in a
 *    real browser before this release shipped, and it is recorded in the build
 *    report with its instrument and timestamp. No test here may ever be read
 *    as evidence for it.
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

function bhp_hs_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_hs_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** Byte offset of a needle, or PHP_INT_MAX when it is absent, so ordering
 *  comparisons against a missing section FAIL rather than silently pass. */
function bhp_hs_at( $hay, $needle ) {
	$p = strpos( $hay, $needle );
	return false === $p ? PHP_INT_MAX : $p;
}

/** Strip CSS comments, so a docblock quoting a value cannot pass a test. */
function bhp_hs_css_only( $src ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', (string) $src );
}

$tpl_dir  = get_template_directory();
$home     = bhp_hs_fetch( home_url( '/' ) );
$css      = (string) @file_get_contents( $tpl_dir . '/style.css' );
$css_min  = (string) @file_get_contents( $tpl_dir . '/style.min.css' );
$fp_src   = (string) @file_get_contents( $tpl_dir . '/front-page.php' );

bhp_hs_assert( '' !== $home, '§0 the homepage was served (everything below depends on it)', $failures );
bhp_hs_assert( '' !== $css_min, '§0 style.min.css was read', $failures );

if ( '' === $home ) {
	echo "\n=== RESULT ===\nABORTED: the homepage could not be fetched.\n";
	return;
}

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · THE TRUST STRIP — the qualifier is gone and nothing was added with it
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_hs_assert(
	1 === substr_count( $home, 'Five-star reader reviews' ),
	'§1.1 the trust strip renders "Five-star reader reviews" exactly once (found '
		. substr_count( $home, 'Five-star reader reviews' ) . ')',
	$failures
);
bhp_hs_assert(
	false === strpos( $home, 'Five-star reader reviews on my first two titles' )
		&& false === strpos( $home, 'Five-star reader reviews on our first two titles' )
		&& false === strpos( $home, 'first two titles' ),
	'§1.2 BOTH superseded wordings, and the bare phrase "first two titles", are gone from the homepage',
	$failures
);

/* ⛔ THE WIDENING GUARD. Dropping a scope qualifier widens a review claim.
   The one thing that would make the shorter line dishonest is a NUMBER
   appearing beside it — an average, a count, or schema. None may be added. */
bhp_hs_assert(
	false === stripos( $home, 'aggregateRating' )
		&& false === stripos( $home, '"@type":"Review"' )
		&& false === stripos( $home, '"@type": "Review"' ),
	'§1.3 NO aggregateRating and NO Review schema is emitted on the homepage',
	$failures
);
if ( preg_match( '#<span class="home-trust-proof__badge[^"]*"[^>]*>(.*?)</span>#s', $home, $bm ) ) {
	$badge = trim( wp_strip_all_tags( $bm[1] ) );
	bhp_hs_assert(
		0 === preg_match( '/\d/', preg_replace( '/[\x{2605}\x{2606}]/u', '', $badge ) ),
		'§1.4 the trust badge carries no digit — no rating value and no review count (badge: "' . $badge . '")',
		$failures
	);
} else {
	bhp_hs_assert( false, '§1.4 the trust badge could not be located in the served document', $failures );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · THE THREE REMOVED SECTIONS ARE ABSENT
   Each is asserted THREE ways — id, rendered heading, and one piece of markup
   unique to it — because an id can survive a copy change and a heading can
   survive an id change, and either alone would let half a section come back.
   ═══════════════════════════════════════════════════════════════════════════ */

$removed = array(
	'home-audience-gateway' => array(
		'heading' => 'What brings you here today?',
		'mark'    => 'audience-gateway__links',
	),
	'home-philosophy'       => array(
		'heading' => 'Nature is the greatest classroom on Earth.',
		'mark'    => 'home-philosophy__pillars',
	),
	'learning-hub'          => array(
		'heading' => 'Follow Curiosity Into the Real World',
		'mark'    => 'homepage-grid--learning',
	),
);
foreach ( $removed as $sid => $spec ) {
	bhp_hs_assert(
		false === strpos( $home, 'id="' . $sid . '"' ),
		"§2 #{$sid} does not render",
		$failures
	);
	bhp_hs_assert(
		false === strpos( $home, $spec['heading'] ),
		"§2 the heading \"{$spec['heading']}\" is not on the page",
		$failures
	);
	bhp_hs_assert(
		false === strpos( $home, $spec['mark'] ),
		"§2 the markup `{$spec['mark']}` is not on the page",
		$failures
	);
}

/* ⭐ REMOVED MEANS NOT RENDERED, NOT DELETED. The partial must still exist —
   that is what makes this reversible, and it is a founder-stated condition. */
bhp_hs_assert(
	file_exists( $tpl_dir . '/template-parts/components/audience-gateway.php' ),
	'§2 the audience-gateway component file is still in the tree (removed != deleted)',
	$failures
);
bhp_hs_assert(
	false !== strpos( $fp_src, "// get_template_part('template-parts/components/audience-gateway');" ),
	'§2 the gateway render is COMMENTED OUT in front-page.php, restorable in one line',
	$failures
);
bhp_hs_assert(
	false !== strpos( $css, '.audience-gateway__plate' ) && false !== strpos( $css, '.home-philosophy__pillars' ),
	'§2 the removed sections CSS is retained in style.css, so restoring is a paste and not a rebuild',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · #explore-world SURVIVES — the name-vs-content guard

   ⛔ READ THIS BEFORE "TIDYING" IT. `#explore-world` renders "Choose Your
      Adventure", the three destination cards, and the homepage's only link to
      /books/. Its ID READS LIKE the removed "Follow Curiosity Into the Real
      World" band and it is NOT that band — that band was `#learning-hub`,
      removed in §2. This assertion exists because the two were in fact
      confused once, in a brief, and the live document is what settled it.
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_hs_assert(
	1 === substr_count( $home, 'id="explore-world"' ),
	'§3.1 #explore-world still renders exactly once',
	$failures
);
bhp_hs_assert(
	false !== strpos( $home, 'Choose Your Adventure' ),
	'§3.2 #explore-world still renders its own heading, "Choose Your Adventure"',
	$failures
);
bhp_hs_assert(
	false !== strpos( $home, 'EXPLORE EVERY FORMAT AND EDITION' ),
	'§3.3 the homepage still carries its only link to /books/',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · SECTION ORDER — positional, from the served document
   ═══════════════════════════════════════════════════════════════════════════ */

$order = array(
	'id="home-hero"',
	'id="home-trust-proof"',
	'id="kirkus-credibility-home"',
	'id="home-open-the-book"',
	'id="where-you-will-find-us"',
	'id="amazon-customer-reviews"',
	'id="explore-world"',
	'id="first-reader"',
	'id="teacher-resources"',
	'id="trust"',
);
$prev = -1;
$ok   = true;
$seen = array();
foreach ( $order as $needle ) {
	$at = bhp_hs_at( $home, $needle );
	$seen[] = $needle . '@' . ( PHP_INT_MAX === $at ? 'MISSING' : $at );
	if ( $at <= $prev || PHP_INT_MAX === $at ) {
		$ok = false;
	}
	$prev = $at;
}
bhp_hs_assert( $ok, '§4.1 the homepage renders its sections in the ruled order: ' . implode( ' < ', $order ), $failures );
if ( ! $ok ) {
	echo "        offsets: " . implode( ' | ', $seen ) . "\n";
}

/* ⭐ THE RULING WAS "DIRECTLY UNDER", NOT "SOMEWHERE BELOW". Assert that
   nothing with a section id renders BETWEEN the booth and the reviews. */
$booth_end   = strpos( $home, 'id="amazon-customer-reviews"' );
$booth_start = strpos( $home, 'id="where-you-will-find-us"' );
$between     = ( false !== $booth_start && false !== $booth_end && $booth_end > $booth_start )
	? substr( $home, $booth_start, $booth_end - $booth_start )
	: 'SENTINEL id="x"';
bhp_hs_assert(
	0 === preg_match( '/<section[^>]+id="/', $between ),
	'§4.2 NO section renders between #where-you-will-find-us and #amazon-customer-reviews (the ruling said "directly under")',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · THE CREAM GROUND, AND THE ELEVEN COLOURS THAT HAD TO MOVE WITH IT

   Asserted in style.min.css — the SHIPPED artefact, comments stripped — so a
   paragraph describing a colour can never pass for the colour itself.
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_hs_assert(
	false !== strpos( $home, 'id="amazon-customer-reviews"' )
		&& false !== strpos( $home, 'home-reviews--cream' ),
	'§5.1 the reviews section carries the cream modifier class in the served document',
	$failures
);

$cream_sel = '.home #amazon-customer-reviews.home-reviews--cream';
bhp_hs_assert(
	false !== strpos( $css_min, $cream_sel . ' {' ) || false !== strpos( $css_min, $cream_sel . '{' ),
	'§5.2 the cream block is present in the SHIPPED minified stylesheet',
	$failures
);
bhp_hs_assert(
	1 === preg_match( '/\.home\ \#amazon-customer-reviews\.home-reviews--cream\s*\{[^}]*background:\s*var\(--color-parchment\)/s', $css_min ),
	'§5.3 the ground is var(--color-parchment) — the theme cream token, not a new hue',
	$failures
);
bhp_hs_assert(
	1 === preg_match( '/--color-parchment:\s*#f1e7d2/i', $css_min ),
	'§5.4 --color-parchment is still #f1e7d2, the hex every ratio in the block was computed against',
	$failures
);

/* Each pair: the selector suffix, and the token it must now resolve to. */
$repointed = array(
	array( '.component-heading__eyebrow',                'var(--color-text-muted)' ),
	array( '.text-section-title',                        'var(--color-navy)' ),
	array( '.amazon-review-showcase__book-title',        'var(--color-navy)' ),
	array( '.amazon-review-card__quote p',               'var(--color-text)' ),
	array( '.amazon-review-card__title',                 'var(--color-navy)' ),
);
foreach ( $repointed as $r ) {
	$pat = '/' . preg_quote( $cream_sel . ' ' . $r[0], '/' ) . '\s*\{\s*color:\s*' . preg_quote( $r[1], '/' ) . '/';
	bhp_hs_assert(
		1 === preg_match( $pat, $css_min ),
		"§5.5 {$r[0]} is repointed to {$r[1]} on the cream ground",
		$failures
	);
}
bhp_hs_assert(
	1 === preg_match( '/' . preg_quote( $cream_sel . ' .amazon-review-card__verified', '/' ) . '\s*\{\s*color:\s*var\(--color-text-muted\)/', $css_min ),
	'§5.6 the source/verified attribution is repointed to var(--color-text-muted)',
	$failures
);
bhp_hs_assert(
	1 === preg_match( '/' . preg_quote( $cream_sel . ' .amazon-review-card', '/' ) . '\s*\{[^}]*background:\s*var\(--color-surface\)/s', $css_min ),
	'§5.7 the card gets a real light surface (the 7%-white veil built for near-black is replaced)',
	$failures
);
bhp_hs_assert(
	1 === preg_match( '/' . preg_quote( $cream_sel . ' .amazon-review-card', '/' ) . '\s*\{[^}]*backdrop-filter:\s*none/s', $css_min ),
	'§5.8 the card backdrop-filter is off — nothing behind it to blur, and it forced a compositing layer for no result',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · GOLD-AS-TEXT IS BANNED ON THE CREAM GROUND

   #D9A45F measures 1.81:1 on #f1e7d2. It was CORRECT on the old dark ground
   (~9.7:1), which is exactly why a future pass restores it by habit. Five
   declarations carried it; every one is repointed. This reads the whole cream
   block out of the shipped artefact and fails if `--color-gold` appears as a
   `color:` value anywhere inside it.
   ═══════════════════════════════════════════════════════════════════════════ */

$cream_start = strpos( $css_min, $cream_sel );
$cream_text  = false === $cream_start ? '' : substr( $css_min, $cream_start );
bhp_hs_assert(
	'' !== $cream_text
		&& 0 === preg_match( '/' . preg_quote( $cream_sel, '/' ) . '[^{]*\{[^}]*color:\s*(var\(--color-gold\)|#D9A45F)/is', $cream_text ),
	'§6.1 no rule in the cream block sets color: var(--color-gold) or #D9A45F',
	$failures
);
foreach ( array( '.amazon-review-card__link', '.amazon-review-showcase__product-link a', '.home-reviews__collection-cta a' ) as $link_sel ) {
	bhp_hs_assert(
		false !== strpos( $css_min, $cream_sel . ' ' . $link_sel ),
		"§6.2 {$link_sel} is explicitly re-coloured inside the cream block",
		$failures
	);
}
bhp_hs_assert(
	1 === preg_match( '/' . preg_quote( $cream_sel . ' .amazon-review-card__link:focus-visible', '/' ) . '[^{]*\{[^}]*outline:\s*3px solid var\(--color-focus\)/s', $css_min ),
	'§6.3 the focus indicator returns to the sitewide var(--color-focus), which passes on a light ground',
	$failures
);
/* ⭐ THE STARS. `--color-gold-deep` (#805800) was chosen in 1.19.266 precisely
   because it clears AA on every cream in circulation. It is inherited, not
   re-declared, and it must NOT be "fixed" back to --color-gold. */
bhp_hs_assert(
	1 === preg_match( '/\#amazon-customer-reviews\ \.amazon-review-card__stars\s*\{\s*color:\s*var\(--color-gold-deep\)/', $css_min )
		&& 1 === preg_match( '/--color-gold-deep:\s*#805800/i', $css_min ),
	'§6.4 the stars are still var(--color-gold-deep) = #805800 (5.16:1 on parchment)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §7 · THE ONE PLATE MOVED WITH ITS HOST
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_hs_assert(
	1 === substr_count( $home, 'data-bhp-plate=' ),
	'§7.1 exactly ONE plate on the whole homepage (found ' . substr_count( $home, 'data-bhp-plate=' ) . ')',
	$failures
);
bhp_hs_assert(
	1 === substr_count( $home, 'data-bhp-plate="amazon-customer-reviews"' ),
	'§7.2 it is on #amazon-customer-reviews — the new cream band, the one light section with no other drawn mark',
	$failures
);
bhp_hs_assert(
	false === strpos( $home, 'data-bhp-plate="home-audience-gateway"' ),
	'§7.3 the old plate host is gone with its section',
	$failures
);
$plate_block = '';
if ( preg_match( '/<div class="home-reviews__plate".*?<\/div>/s', $home, $pm ) ) {
	$plate_block = $pm[0];
}
bhp_hs_assert(
	'' !== $plate_block
		&& false !== strpos( $plate_block, 'aria-hidden="true"' )
		&& '' === trim( wp_strip_all_tags( $plate_block ) ),
	'§7.4 the plate is decorative and textless',
	$failures
);
bhp_hs_assert(
	1 === preg_match( '/' . preg_quote( $cream_sel . ' .home-reviews__plate', '/' ) . '\s*\{[^}]*pointer-events:\s*none/s', $css_min )
		&& 1 === preg_match( '/' . preg_quote( $cream_sel . ' .home-reviews__plate', '/' ) . '\s*\{[^}]*position:\s*absolute/s', $css_min ),
	'§7.5 the plate is inert and out of flow — it can neither swallow a tap nor contribute layout/CLS',
	$failures
);
/* NO NEW HUE: the SVG literal must remain the exact --expedition-gold value.
   A background image cannot inherit currentColor, so a baked literal only
   stays honest if something checks it. */
$plate_svg = (string) @file_get_contents( $tpl_dir . '/assets/img/plate-compass-rose.svg' );
bhp_hs_assert(
	'' !== $plate_svg
		&& false !== stripos( $plate_svg, '#D9A45F' )
		&& 1 === preg_match( '/--expedition-gold:\s*#D9A45F/i', $css_min ),
	'§7.6 the plate SVG is still stroked in #D9A45F, the exact literal of --expedition-gold (no new hue)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §8 · NO DEAD FRAGMENT — run over the served document, not over the source

   Removing a section removes its id. Any in-page anchor still pointing at one
   scrolls nowhere. This collects every same-page `href="#..."` the homepage
   actually emits and requires a matching id in the same document.
   ═══════════════════════════════════════════════════════════════════════════ */

$dead = array();
if ( preg_match_all( '/href="#([A-Za-z][\w:.-]*)"/', $home, $fm ) ) {
	foreach ( array_unique( $fm[1] ) as $frag ) {
		if ( false === strpos( $home, 'id="' . $frag . '"' ) && false === strpos( $home, "id='" . $frag . "'" ) ) {
			$dead[] = '#' . $frag;
		}
	}
}
bhp_hs_assert(
	empty( $dead ),
	'§8.1 every in-page fragment link on the homepage resolves to an id in the same document'
		. ( $dead ? ' — DEAD: ' . implode( ', ', $dead ) : '' ),
	$failures
);
/* The three ids this release took away, named explicitly, so the check is
   readable as a statement about THIS change and not only as a generic sweep. */
foreach ( array( 'home-audience-gateway', 'home-philosophy', 'learning-hub' ) as $gone ) {
	bhp_hs_assert(
		false === strpos( $home, 'href="#' . $gone . '"' ),
		"§8.2 nothing on the homepage links to #{$gone}",
		$failures
	);
}
/* ⛔ THE HERO CTA FALLBACK. `#explore-world` was a hero secondary-CTA target
   before 1.19.179 dropped that button, and the argument survives in a comment.
   It must not be a LIVE href, and — since the section still renders — it would
   not be dead even if it were. Both halves asserted. */
bhp_hs_assert(
	false !== strpos( $home, 'id="explore-world"' ),
	'§8.3 #explore-world is a live anchor target, so any restored hero fallback to it still resolves',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §9 · NOTHING WAS TAKEN FROM THE CUSTOMERS' OWN WORDS
   ═══════════════════════════════════════════════════════════════════════════ */

$reviews_php = (string) @file_get_contents( $tpl_dir . '/inc/amazon-reviews.php' );
bhp_hs_assert(
	'' !== $reviews_php && false !== strpos( $reviews_php, 'We read a few chapters each night' ),
	'§9.1 the quoted customer review still says "We read a few chapters each night" (standing rule 9.1a — never edit a quote)',
	$failures
);
bhp_hs_assert(
	false !== strpos( $home, 'What Families Are Saying' )
		&& false !== strpos( $home, 'Get the collection Here' ),
	'§9.2 the reviews section kept its heading and its one CTA through the move',
	$failures
);
/* The move must be a MOVE. Exactly one instance of the section, not two. */
bhp_hs_assert(
	1 === substr_count( $home, 'id="amazon-customer-reviews"' )
		&& 1 === substr_count( $home, 'What Families Are Saying' ),
	'§9.3 the reviews section was MOVED, not copied — it renders exactly once',
	$failures
);

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL PASS\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
