<?php
/**
 * PDP REDESIGN PHASE 2 — CONCEPT A. THE CONTENT GATE. Theme 1.19.349.
 * `CYCLE179-LD-349`. Founder priority, seal 679.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-pdp-349.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE A PIXEL. PHP has no viewport, no layout engine and no
 *    fold. Nothing here shows that ADD TO CART clears 812px on the colouring
 *    book, that the plates render at the right size, or that the left column
 *    sits under the gallery.
 * ⭐ THAT PROOF IS THE BROWSER HARNESS — real Chromium, `window.innerWidth`
 *    and `window.innerHeight` ASSERTED IN-PAGE before every reading,
 *    `getBoundingClientRect()` for every number, at 1440x900, 1366x768 and
 *    375x812 on all six surfaces. It is the PRIMARY evidence and it lives in
 *    `CYCLE179-LD-349.md` with a before/after table and in
 *    `349-STAGING/AFTER-349-measurements.json`.
 * ⭐ THIS FILE IS THE REGRESSION GATE. It asserts the two things source CAN
 *    carry: what the SHIPPED ARTEFACTS declare, and the CLAIMS RAILS — which
 *    is the half that matters most here, because phase 2 is the release that
 *    put customer-facing sentences on five product surfaces.
 *
 * WHAT IT ASSERTS
 *   §1  the registry resolves for all five keys and returns the right counts
 *   §2  ⛔ THE CLAIMS RAILS, per key: no em dash, no en dash, no "5 to 9",
 *       no rating/review/star/award/proven, no page count on a chapter book
 *   §3  ⛔ LEXILE APPEARS ONCE ON MARIANA, ONCE ON EVEREST, AND NOWHERE ELSE.
 *       `CYCLE141-CX-40a` is a fabricated reading measurement and this is the
 *       assertion that stops one being introduced by a careless edit
 *   §4  ⛔ THE VOICE RULE, with its ONE audited carve-out: zero standalone
 *       "we"/"us"/"our" in every block except The Amazon, which carries
 *       exactly one "we" in "we are all connected to it" (story bank SB-C7)
 *   §5  ⛔ NO END-OF-BOOK "hard things" CLAIM anywhere. Founder seal 685
 *   §6  the look-inside plates exist ON DISK at all three widths, and the
 *       colouring pair is never called a "spread" in visible copy
 *   §7  the motif strings C, D and D2 are applied and the superseded wording
 *       is gone from the shipped code
 *   §8  the colouring rail note reads the PLUGIN's figure, and no shipping
 *       number is typed into any string
 *   §9  the shipped CSS carries the phase-2 placement, the one-price rule and
 *       the mobile fold work, each correctly scoped
 *  §10  ⛔ NOTHING THIS RELEASE PROMISED NOT TO DO WAS DONE — no price literal
 *       in CSS, no aggregateRating, no proof line, no product-meta write, and
 *       the forest CTA preview cannot run off a staging host
 *  §11  the build artefacts are FRESH (a stale minify ships nothing to anyone)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. A gate that cannot
 *    report failure is not a gate. Same reason, same fix, as
 *    `test-cycle179-pdp-348.php` and `test-shop-grid-2up-204.php`.
 */
$GLOBALS['p349_failures'] = 0;
$GLOBALS['p349_passes']   = 0;
function p349_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['p349_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['p349_failures']++;
		echo "FAIL: $label\n";
	}
}

$p349_theme = get_template_directory();

/** Read a shipped artefact, whitespace flattened, or '' when it is missing. */
function p349_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return preg_replace( '/\s+/', ' ', (string) file_get_contents( $path ) );
}

$p349_keys = array( 'mariana_trench', 'mount_everest', 'amazon_rainforest', 'collection', 'colouring_mariana' );

echo "\n=== §1 THE REGISTRY RESOLVES ===\n";

p349_assert( function_exists( 'bhp_book_whats_inside' ), '§1.1 bhp_book_whats_inside() exists' );
p349_assert( function_exists( 'bhp_pdp_look_inside_registry' ), '§1.2 bhp_pdp_look_inside_registry() exists' );
p349_assert( function_exists( 'bhp_pdp_content_key' ), '§1.3 bhp_pdp_content_key() exists' );
p349_assert( function_exists( 'bhp_pdp_look_inside_noun' ), '§1.4 bhp_pdp_look_inside_noun() exists' );

$p349_bullets = array();
foreach ( $p349_keys as $k ) {
	$p349_bullets[ $k ] = bhp_book_whats_inside( $k );
}

/*
 * ⭐ THE EXPECTED COUNTS ARE THE `commerce-cx` ACCEPTANCE CRITERION D3, NOT A
 *    NUMBER
 *    CHOSEN HERE. Seven on the colouring book is the top of the 5-to-7 range
 *    and is correct: those are the founder-approved 618 bullets, reused
 *    verbatim, and approved copy is not trimmed to hit a design target.
 */
$p349_expect = array(
	'mariana_trench'    => 6,
	'mount_everest'     => 6,
	'amazon_rainforest' => 6,
	'collection'        => 6,
	'colouring_mariana' => 7,
);
foreach ( $p349_expect as $k => $n ) {
	p349_assert( count( $p349_bullets[ $k ] ) === $n, "§1.5 {$k} returns {$n} bullets (D3)" );
}
p349_assert( array() === bhp_book_whats_inside( 'no_such_key' ), '§1.6 an unknown key returns [] so the block renders NOTHING' );

$p349_plates = bhp_pdp_look_inside_registry();
foreach ( array( 'mariana_trench' => 2, 'mount_everest' => 2, 'amazon_rainforest' => 2, 'colouring_mariana' => 2, 'collection' => 3 ) as $k => $n ) {
	p349_assert( isset( $p349_plates[ $k ] ) && count( $p349_plates[ $k ] ) === $n, "§1.7 {$k} has {$n} look-inside plates" );
}

echo "\n=== §2 THE CLAIMS RAILS ===\n";

foreach ( $p349_keys as $k ) {
	$text = implode( ' ', $p349_bullets[ $k ] );

	/* ⛔ Rule 608a / BOR-004 seal 681. NO em dash and NO en dash in new copy. */
	p349_assert( false === strpos( $text, "\xE2\x80\x94" ), "§2.1 {$k} carries NO em dash" );
	p349_assert( false === strpos( $text, "\xE2\x80\x93" ), "§2.2 {$k} carries NO en dash" );

	/* ⛔ Reading age is 6 to 9. NEVER 5 to 9. Standing Rules §9. */
	p349_assert( 0 === preg_match( '/5\s*(?:-|to|\x{2013})\s*9/iu', $text ), "§2.3 {$k} never says 5 to 9" );

	/* ⛔ The never-invent list, applied to the rendered strings themselves. */
	p349_assert( 0 === preg_match( '/\b(review|rating|star|award|proven|best[- ]sell|testimonial|Kirkus)\b/i', $text ), "§2.4 {$k} carries no review, rating, star, award, proof or Kirkus word" );

	/* ⛔ Page count for a CHAPTER book is UNAVAILABLE and must not be invented.
	 *    The colouring book is the deliberate exception: 118 pages is counted
	 *    from the md5-matched PDF and is founder-approved 618 copy. */
	if ( 'colouring_mariana' !== $k ) {
		p349_assert( 0 === preg_match( '/\b\d+\s*pages\b/i', $text ), "§2.5 {$k} states no page count (UNAVAILABLE, not invented)" );
	}

	p349_assert( '' !== trim( $text ), "§2.6 {$k} is non-empty" );
}
p349_assert( false !== strpos( implode( ' ', $p349_bullets['colouring_mariana'] ), '118 pages' ), '§2.7 the colouring block DOES carry 118 pages (counted from the PDF, approved 618 copy)' );

/* ⛔ The four standing-flagged founder specifics, on every key. */
foreach ( $p349_keys as $k ) {
	$text = implode( ' ', $p349_bullets[ $k ] );
	p349_assert(
		0 === preg_match( '/Island Peak|\bJiri\b|20,?000 feet|without (?:supplemental )?oxygen/i', $text ),
		"§2.8 {$k} carries none of the four flagged founder specifics"
	);
}

echo "\n=== §3 LEXILE — THE FABRICATED-MEASUREMENT GUARD ===\n";

$p349_lex = array();
foreach ( $p349_keys as $k ) {
	$p349_lex[ $k ] = preg_match_all( '/Lexile/i', implode( ' ', $p349_bullets[ $k ] ) );
}
p349_assert( 1 === $p349_lex['mariana_trench'], '§3.1 Mariana carries Lexile exactly once' );
p349_assert( 1 === $p349_lex['mount_everest'], '§3.2 Everest carries Lexile exactly once' );
p349_assert( 0 === $p349_lex['amazon_rainforest'], '§3.3 ⛔ The Amazon carries NO Lexile (no measure exists — CYCLE141-CX-40a)' );
p349_assert( 0 === $p349_lex['collection'], '§3.4 the Collection carries no Lexile' );
p349_assert( 0 === $p349_lex['colouring_mariana'], '§3.5 the colouring book carries no Lexile' );
p349_assert( false !== strpos( implode( ' ', $p349_bullets['mariana_trench'] ), '580L' ), '§3.6 Mariana publishes 580L, the figure its live PDP already publishes' );
p349_assert( false !== strpos( implode( ' ', $p349_bullets['mount_everest'] ), '500L' ), '§3.7 Everest publishes 500L, the figure its live PDP already publishes' );

echo "\n=== §4 THE VOICE RULE AND ITS ONE AUDITED CARVE-OUT ===\n";

foreach ( $p349_keys as $k ) {
	$text = implode( ' ', $p349_bullets[ $k ] );
	p349_assert( 0 === preg_match( '/\bour\b/i', $text ), "§4.1 {$k} carries no standalone \"our\"" );
	p349_assert( 0 === preg_match( '/\bus\b/i', $text ), "§4.2 {$k} carries no standalone \"us\"" );

	$we = preg_match_all( '/\bwe\b/i', $text );
	if ( 'amazon_rainforest' === $k ) {
		/*
		 * ⭐ EXACTLY ONE, AND IT IS AUDITED. "we are all connected to it" is
		 *    HUMANITY inside the founder's own quoted intention, cleared by
		 *    `23-FOUNDER-STORY-BANK.md` SB-C7. §9.1 forbids the CORPORATE
		 *    "we" because he is the sole operator; this is not that.
		 * ⛔ ASSERTED AS ONE RATHER THAN SKIPPED, so a second stray "we" on
		 *    this key still fails the suite.
		 */
		p349_assert( 1 === $we, '§4.3 The Amazon carries EXACTLY ONE "we" (the audited SB-C7 carve-out)' );
		p349_assert( false !== strpos( $text, 'we are all connected to it' ), '§4.4 and it is that exact clause, not some other "we"' );
	} else {
		p349_assert( 0 === $we, "§4.5 {$k} carries no \"we\"" );
	}
}

echo "\n=== §5 THE END-OF-BOOK CLAIM IS GONE (founder seal 685) ===\n";

/*
 * ⛔ "Every book ends with the same four words: I can do hard things" is FALSE.
 *    The three books end on three DIFFERENT motif lines and the phrase appears
 *    nowhere in any of the three typeset interiors of record
 *    (`MOTIF-MANTRA-AUDIT.md` §1, md5-matched files, pages rendered and read).
 * ⭐ THE COLOURING BOOK IS THE ONE PLACE THE PHRASE IS TRUE AND IS ALLOWED:
 *    it is set on that book's quote pages, verified off the rendered artwork,
 *    and the bullet says so about the quote pages rather than about an ending.
 */
foreach ( $p349_keys as $k ) {
	$text = implode( ' ', $p349_bullets[ $k ] );
	p349_assert( 0 === preg_match( '/ends? with .{0,40}hard things/i', $text ), "§5.1 {$k} makes no end-of-book \"hard things\" claim" );
	if ( 'colouring_mariana' !== $k ) {
		p349_assert( 0 === preg_match( '/hard things/i', $text ), "§5.2 {$k} does not mention \"hard things\" at all" );
	}
}
p349_assert(
	false !== strpos( implode( ' ', $p349_bullets['colouring_mariana'] ), 'quote pages' ),
	'§5.3 the colouring bullet attributes the line to the QUOTE PAGES, which is where it is printed'
);

echo "\n=== §6 THE PLATES EXIST ON DISK, AND THE COLOURING PAIR IS NOT A SPREAD ===\n";

$p349_missing = 0;
$p349_files   = 0;
foreach ( $p349_plates as $k => $list ) {
	foreach ( $list as $plate ) {
		foreach ( array( 800, 1200, 1600 ) as $w ) {
			$p349_files++;
			$f = $p349_theme . '/assets/look-inside/' . $plate['stem'] . '-' . $w . '.jpg';
			if ( ! file_exists( $f ) || filesize( $f ) < 10000 ) {
				$p349_missing++;
				echo "  MISSING OR TRUNCATED: {$plate['stem']}-{$w}.jpg\n";
			}
		}
	}
}
p349_assert( 0 === $p349_missing, "§6.1 every look-inside file is deployed at 800/1200/1600 ({$p349_files} files, {$p349_missing} missing)" );

foreach ( $p349_plates as $k => $list ) {
	foreach ( $list as $plate ) {
		p349_assert( ! empty( $plate['alt'] ) && strlen( $plate['alt'] ) > 40, "§6.2 {$plate['stem']} carries real alt text" );
		p349_assert( false === strpos( $plate['alt'], "\xE2\x80\x94" ), "§6.3 {$plate['stem']} alt carries no em dash" );
		p349_assert( 0 === preg_match( '/\b(review|rating|star|award)\b/i', $plate['alt'] ), "§6.4 {$plate['stem']} alt carries no proof word" );
	}
}

/*
 * ⛔ THE COLOURING PAIRS ARE NOT FACING PAGES. Every design in that book sits
 *    on a recto facing a BLANK verso, so 95/101 and 99/109 never faced each
 *    other. Calling them a "spread" in visible copy would be a small false
 *    claim about the printed object.
 */
p349_assert( false !== strpos( bhp_pdp_look_inside_noun( 'colouring_mariana' ), 'Two coloring pages' ), '§6.5 the colouring caption reads "Two coloring pages", never "spread"' );
p349_assert( false === stripos( bhp_pdp_look_inside_noun( 'colouring_mariana' ), 'spread' ), '§6.6 the colouring caption contains no "spread"' );
foreach ( $p349_plates['colouring_mariana'] as $plate ) {
	p349_assert( false === stripos( $plate['alt'], 'spread' ), '§6.7 the colouring alt text contains no "spread"' );
}

echo "\n=== §7 THE MOTIF STRINGS (founder seal 685, audit strings C, D, D2) ===\n";

$p349_fn = (string) file_get_contents( $p349_theme . '/functions.php' );

p349_assert( false === strpos( $p349_fn, 'hard moments: stop, breathe, think, choose' ), '§7.1 string C: the superseded "stop, breathe, think, choose" bullet is GONE' );
p349_assert( 3 === substr_count( $p349_fn, 'hard moments: stay calm, breathe slowly, think, then choose' ), '§7.2 string C: the replacement is present on all three titles' );
p349_assert( false === strpos( $p349_fn, 'The four words from the story, big enough to pin up' ), '§7.3 string D: the superseded "from the story" card description is GONE' );
p349_assert( false !== strpos( $p349_fn, 'The four words I teach at read-alouds, big enough to pin up' ), '§7.4 string D: the replacement is present' );
p349_assert( false === strpos( $p349_fn, 'order printed in the books and on the sheet' ), '§7.5 string D2: the "printed in the books" comment is corrected' );
p349_assert( false === strpos( $p349_fn, 'series\' "stop, breathe, think, choose" resilience refrain' ), '§7.6 string D2: the docblock that made the bullet look sourced is corrected' );
p349_assert( false !== strpos( $p349_fn, 'item 685' ), '§7.7 the superseding founder item is cited in the code, so it is not re-derived' );

/*
 * ⛔ THE FOUNDER GATES ARE NOT APPLIED, AND THIS ASSERTS THAT THEY ARE NOT.
 *    A/B live in WooCommerce product records, F/G in production post content
 *    and H in a PDF. None of them is a theme string, so none of them may be
 *    touched by a theme release, and a future pass must not "finish the job".
 */
p349_assert( false === strpos( $p349_fn, 'ends the way every book in the series ends' ), '§7.8 ⛔ string A (product record, ANDREW GATE) was NOT applied in theme code' );
p349_assert( false === strpos( $p349_fn, 'That is the mantra I teach at read-alouds, and it comes out of what Sylvie' ), '§7.9 ⛔ string B (product record, ANDREW GATE) was NOT applied in theme code' );

echo "\n=== §8 THE COLOURING SHIPPING SENTENCE READS THE PLUGIN ===\n";

$p349_col = (string) file_get_contents( $p349_theme . '/inc/colouring-line.php' );
p349_assert( false !== strpos( $p349_col, "'rail_note'" ), '§8.1 the colouring rail declares a rail_note' );
p349_assert( false !== strpos( $p349_col, 'bhp_colouring_single_shipping' ), '§8.2 and it reads the PLUGIN function, not a literal' );
p349_assert( false !== strpos( $p349_col, 'Shipping is $%s for this book on its own in the contiguous US' ), '§8.3 the approved seal-687 wording is present, with the figure as a token' );
p349_assert( false !== strpos( $p349_col, 'Ships from my print partner' ), '§8.4 the protected I-voice string survives (manifest §3.3)' );
p349_assert( 0 === preg_match( '/Shipping is \$2\.99 for this book/', $p349_col ), '§8.5 ⛔ the figure is NEVER typed into the sentence' );

if ( function_exists( 'bhp_colouring_single_shipping' ) && function_exists( 'bhp_colouring_draft_copy' ) ) {
	$p349_live_note = bhp_colouring_draft_copy( 'rail_note', array( number_format( (float) bhp_colouring_single_shipping(), 2 ) ) );
	p349_assert(
		false !== strpos( $p349_live_note, '$' . number_format( (float) bhp_colouring_single_shipping(), 2 ) ),
		'§8.6 the rendered sentence carries the plugin\'s CURRENT figure (' . number_format( (float) bhp_colouring_single_shipping(), 2 ) . ')'
	);
	p349_assert( false === strpos( $p349_live_note, '$1.99' ), '§8.7 ⛔ the inherited $1.99 understatement is gone' );
	p349_assert( false === strpos( $p349_live_note, "\xE2\x80\x94" ), '§8.8 the sentence carries no em dash' );
	/*
	 * ⚠️ THIS ASSERTION FAILED ON ITS FIRST RUN AND THE TEST WAS WRONG, NOT THE
	 *    STRING. The needle was `/\b(we|us|our)\b/i`, and the `i` flag made
	 *    `\bus\b` match the "US" in "in the contiguous US" — a COUNTRY, in a
	 *    clause `21-PROTECTED-ELEMENTS-MANIFEST.md` §3.3 requires to be there.
	 *    ⭐ VERIFIED INDEPENDENTLY BEFORE THE TEST WAS CHANGED: the rendered
	 *       sentence was read off the live staging colouring PDP at an asserted
	 *       1440x900 and contains no company "we", "us" or "our".
	 *    ⭐ SO THE PRONOUN CHECK IS CASE-SENSITIVE FOR THE LOWERCASE WORD and
	 *       case-insensitive for the two that have no country collision.
	 */
	p349_assert( 0 === preg_match( '/\b(we|our)\b/i', $p349_live_note ), '§8.9a the sentence carries no company "we" or "our"' );
	p349_assert( 0 === preg_match( '/\bus\b/', $p349_live_note ), '§8.9b and no lowercase "us" (the uppercase "US" is the country, and is required)' );
	p349_assert( false !== strpos( $p349_live_note, 'contiguous US' ), '§8.9c "contiguous US" is retained (ads-knowledge doctrine row 6)' );
} else {
	echo "SKIP: §8.6 to §8.9 need the bundle plugin active (it is not)\n";
}

$p349_fmt = (string) file_get_contents( $p349_theme . '/template-parts/commerce/format-cards.php' );
p349_assert( false !== strpos( $p349_fmt, "\$data['rail_note']" ), '§8.10 the card template honours rail_note' );

echo "\n=== §9 THE SHIPPED CSS CARRIES PHASE 2 ===\n";

$p349_style = p349_read( 'style.min.css' );
$p349_pdp   = p349_read( 'assets/css/pdp-content.min.css' );
$p349_bf    = p349_read( 'assets/css/book-formats.min.css' );
$p349_pt    = p349_read( 'assets/css/product-template.min.css' );

p349_assert( '' !== $p349_pdp, '§9.1 pdp-content.min.css is built and deployed' );
p349_assert( false !== strpos( $p349_style, '.bhp-pdp-left' ), '§9.2 the shipped root stylesheet places .bhp-pdp-left' );
p349_assert( false !== strpos( $p349_style, 'grid-row: 1 / span 2' ), '§9.3 the summary spans both rows, so row 1 sizes to the GALLERY' );
p349_assert( false !== strpos( $p349_style, '.bhp-format-card.is-selected .bhp-format-card__price { display: none; }' ), '§9.4 the one-price rule is shipped' );
p349_assert( false !== strpos( $p349_pdp, '.bhp-pdp-plate__img' ), '§9.5 the plate styles are shipped' );
p349_assert( false === strpos( $p349_pdp, 'object-fit' ), '§9.6 ⛔ NO object-fit on a plate: the printed margins are never cropped' );
p349_assert( false !== strpos( $p349_bf, 'max-height: 235px' ), '§9.7 the phone gallery cap is shipped' );
p349_assert( false !== strpos( $p349_pt, 'bhp-formats--single' ), '§9.8 the one-card price row is shipped' );

/*
 * ⛔ SCOPING, ASSERTED RATHER THAN TRUSTED. The desktop placement must not
 *    reach a phone, and the phone gallery cap must not reach the desktop. A
 *    rule that leaks is how a fold fix becomes a fold regression.
 */
$p349_style_raw = (string) file_get_contents( $p349_theme . '/style.min.css' );
$p349_at        = strpos( $p349_style_raw, '.bhp-pdp-left' );
$p349_before    = false === $p349_at ? '' : substr( $p349_style_raw, 0, $p349_at );
p349_assert(
	false !== $p349_at && false !== strrpos( $p349_before, '@media (min-width: 901px)' )
		&& strrpos( $p349_before, '@media (min-width: 901px)' ) > strrpos( $p349_before, '@media (max-width' ),
	'§9.9 the .bhp-pdp-left placement sits inside a min-width: 901px block'
);

$p349_bf_raw = (string) file_get_contents( $p349_theme . '/assets/css/book-formats.min.css' );
$p349_capat  = strpos( $p349_bf_raw, 'max-height: 235px' );
p349_assert(
	false !== $p349_capat && false !== strpos( substr( $p349_bf_raw, 0, $p349_capat ), '@media (max-width: 782px)' ),
	'§9.10 the phone gallery cap sits inside a max-width: 782px block'
);
p349_assert(
	false !== $p349_capat && false !== strpos( substr( $p349_bf_raw, max( 0, $p349_capat - 3000 ), 3000 ), 'bhp-gallery-multi' ),
	'§9.11 and it is gated on body.bhp-gallery-multi, so the chapter books cannot be reached'
);

echo "\n=== §10 THE BOUNDARIES THIS RELEASE PROMISED NOT TO CROSS ===\n";

/* ⛔ No price literal may reach a stylesheet. A price belongs to WooCommerce. */
foreach ( array( 'style.min.css' => $p349_style, 'assets/css/pdp-content.min.css' => $p349_pdp, 'assets/css/book-formats.min.css' => $p349_bf, 'assets/css/product-template.min.css' => $p349_pt ) as $name => $css ) {
	p349_assert( 0 === preg_match( '/content:\s*["\'][^"\']*\$\s?\d/', $css ), "§10.1 {$name} contains no price literal" );
	p349_assert( 0 === preg_match( '/\b(22\.99|28\.99|12\.99|11\.99|17\.99|31\.99)\b/', $css ), "§10.2 {$name} contains no bundle or product price" );
}

/*
 * ⛔ Proof lines are OFF until ACT-OPS-333 clears. "Off" means NOT ADDED.
 *
 * ⚠️ §10.4 FAILED ON ITS FIRST RUN AND THE TEST WAS WRONG, NOT THE TEMPLATE.
 *    It scanned the raw PHP, and the partial's own ⛔ comment block NAMES
 *    "Kirkus" and "rating" in the paragraph explaining why neither is there.
 *    ⭐ THIS IS A KNOWN DEFECT CLASS IN THIS REPOSITORY, not a new one:
 *       `1.19.344` recorded "the blog-link suite was reading its own CSS
 *       comments", and `1.19.348` §6.5 was the same mistake in the 348 suite.
 *       A gate that reads its own documentation is not reading the code.
 *    ⭐ VERIFIED INDEPENDENTLY BEFORE THE TEST WAS CHANGED: the RENDERED
 *       pages carry zero rating, star or Kirkus words in the left column at
 *       every viewport, read out of the live DOM
 *       (`349-STAGING/AFTER-349-measurements.json`, `railScan.ratingWord` = 0
 *       on all six surfaces at all three viewports).
 *    ⭐ THE FIX IS TO STRIP COMMENTS BEFORE SCANNING, so the assertion is
 *       about what the file EMITS rather than about what it explains.
 */
$p349_tpl_raw = (string) file_get_contents( $p349_theme . '/template-parts/commerce/pdp-left-column.php' );
$p349_tpl     = preg_replace( array( '#/\*.*?\*/#s', '#//[^\n]*#' ), '', $p349_tpl_raw );
p349_assert( 0 === preg_match( '/aggregateRating|ratingValue|reviewCount/i', $p349_tpl ), '§10.3 the new partial emits no rating schema' );
p349_assert( 0 === preg_match( '/five[- ]star|\d+\s*(?:ratings|reviews)|Kirkus/i', $p349_tpl ), '§10.4 the new partial emits no proof line (comments stripped first)' );
p349_assert( false !== strpos( $p349_tpl_raw, 'amazon-review-card__quote' ), '§10.5a the partial documents that the PROTECTED review section is left alone in both directions' );

/* ⛔ No product record is written, on any environment, by anything shipped. */
$p349_bfp = (string) file_get_contents( $p349_theme . '/inc/book-formats.php' );
p349_assert( false === strpos( $p349_bfp, 'update_post_meta' ), '§10.5 the registry NEVER writes product meta (Andrew gate)' );
p349_assert( false !== strpos( $p349_bfp, "get_post_meta" ), '§10.6 it only READS the bhp_whats_inside override' );

/*
 * ⛔ THE FOREST CTA PREVIEW MUST BE UNREACHABLE ON PRODUCTION. The gate is the
 *    HOST, not the query parameter, and it is asserted here rather than
 *    trusted, because a preview that leaked would repaint the live buy button
 *    for anyone who was sent the link.
 */
/*
 * ⚠️ §10.7 FAILED ON ITS FIRST RUN AND THE TEST WAS WRONG, NOT THE GATE. The
 *    needle was a literal with WordPress-style spaces inside the parentheses,
 *    `strpos( $bhp_host, 'staging2.' )`; the shipped line is written in
 *    `functions.php`'s own compact style, `strpos($bhp_host, 'staging2.')`.
 *    An assertion that depends on whitespace is asserting formatting.
 *    ⭐ VERIFIED INDEPENDENTLY BEFORE THE TEST WAS CHANGED, in a real browser
 *       on staging at an asserted 1440x900:
 *         ?bhp_cta=forest  -> body.bhp-cta-forest present, CTA background
 *                             rgb(23, 63, 47) = forest #173f2f, text #f8f3e9
 *         no parameter     -> class ABSENT, CTA background rgb(217, 164, 95),
 *                             i.e. GOLD, which is and remains the default
 *    ⭐ THE REPLACEMENT IS WHITESPACE-INSENSITIVE and asserts the SHAPE of the
 *       gate: a `strpos` of `$bhp_host` against `staging2.` compared to 0, so
 *       a prefix match rather than a substring match anywhere in the host.
 */
p349_assert(
	1 === preg_match( '/0\s*===\s*strpos\(\s*\$bhp_host\s*,\s*[\'"]staging2\.[\'"]\s*\)/', $p349_fn ),
	'§10.7 the forest CTA preview is gated on a staging2. host PREFIX (0 === strpos)'
);
p349_assert( false !== strpos( $p349_style, 'body.bhp-cta-forest' ), '§10.8 and every forest rule is prefixed with that body class, so gold is the default' );
if ( function_exists( 'bhp_body_classes' ) ) {
	$p349_probe = bhp_body_classes( array() );
	p349_assert( ! in_array( 'bhp-cta-forest', (array) $p349_probe, true ), '§10.9 with no bhp_cta parameter the class is absent (gold stays the default)' );
}

echo "\n=== §11 THE BUILD ARTEFACTS ARE FRESH ===\n";

/*
 * ⛔ A STALE MINIFY SHIPS VERIFIED CSS TO THE REPOSITORY AND NOTHING TO A
 *    CUSTOMER. `test-style-minification.php` owns the full check; this asserts
 *    the two artefacts THIS release depends on, so a 349 failure is legible
 *    here rather than only in another suite.
 */
foreach ( array( 'style', 'assets/css/pdp-content', 'assets/css/book-formats', 'assets/css/product-template' ) as $stem ) {
	$src = $p349_theme . '/' . $stem . '.css';
	$min = $p349_theme . '/' . $stem . '.min.css';
	if ( ! file_exists( $src ) || ! file_exists( $min ) ) {
		p349_assert( false, "§11.1 {$stem}: both source and artefact exist" );
		continue;
	}
	/* Line endings normalised before hashing — the artefact records the md5 of
	   its source, and `git archive` rewrites CRLF on export. Same normalisation
	   as tools/build-css.mjs and test-style-minification.php. */
	$hash = md5( str_replace( "\r\n", "\n", (string) file_get_contents( $src ) ) );
	p349_assert( false !== strpos( (string) file_get_contents( $min ), $hash ), "§11.2 {$stem}.min.css was built from the CURRENT source" );
}

echo "\n============================================================\n";
echo "CYCLE179-LD-349 — {$GLOBALS['p349_passes']} passed, {$GLOBALS['p349_failures']} failed\n";
echo "============================================================\n";
