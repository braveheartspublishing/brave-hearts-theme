<?php
/**
 * test-cycle179-build-391.php — theme 1.19.391, 2026-09-07.
 * `CYCLE179-CX-BUILD-391` · commerce-cx, under chief-of-staff.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 * Three of this build's four items. The fourth, the early cart capture, has
 * its own suite (`test-cycle179-cx-early-cart-capture-2.php`) and is not
 * duplicated here.
 *
 *   §1  the comparison table restyle (design-creative's Concept B)
 *   §2  the PDP order — purchase block above the table (Andrew, 2026-09-07)
 *   §3  the look-inside plates come up under the hero gallery
 *   §4  the "Look inside" cue, and the claim it is allowed to make
 *   §5  rails that must not have moved
 *
 * ⛔⛔ WHAT A PHP SUITE CANNOT PROVE, STATED HERE SO NOBODY READS IT AS PROVED.
 *     Nothing below observes a pixel. It cannot show that add-to-cart clears
 *     the fold at 375 or 1440, that the gold lane paints unbroken, or that the
 *     plates actually sit under the gallery rather than 513px below it —
 *     because grid TRACK SIZING is a rendering outcome, and a rule being
 *     present in a stylesheet is not the same claim as a box being where the
 *     rule intends. Those are browser measurements and they live in the build
 *     report with `window.innerWidth` asserted alongside them.
 *
 * ⭐ WHAT IT CAN PROVE, AND WHY THAT IS STILL WORTH RUNNING: that the hook
 *    priorities are what the founder ruled, that the markup carries the
 *    accessible strings unchanged, that no price is typed, that the cue cannot
 *    promise a video that does not exist, and that a later lane cannot quietly
 *    take any of it back.
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "This suite must run inside WordPress (wp eval-file).\n";
	return;
}

$c391_pass = 0;
$c391_fail = 0;

function c391_ok( $label, $cond, $detail = '' ) {
	global $c391_pass, $c391_fail;
	if ( $cond ) {
		++$c391_pass;
		echo "PASS  {$label}\n";
		return true;
	}
	++$c391_fail;
	echo "FAIL  {$label}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
	return false;
}

function c391_head( $t ) {
	echo "\n=== {$t} ===\n";
}

function c391_src( $rel ) {
	$f = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $f ) ? (string) file_get_contents( $f ) : '';
}

/**
 * Executable PHP only. The house rule, learned the expensive way in
 * `test-cycle168-early-cart-capture.php` §1.0: a raw `strpos()` over a whole
 * file cannot tell a CALL from a COMMENT EXPLAINING WHY NOT TO CALL, so it
 * punishes a well-documented file and invites someone to delete the prose that
 * prevents the defect. `token_get_all()` is the real lexer, not a guess at one.
 */
function c391_code( $rel ) {
	$raw = c391_src( $rel );
	if ( '' === $raw ) {
		return '';
	}
	$out = '';
	foreach ( token_get_all( $raw ) as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			$out .= $t[1];
			continue;
		}
		$out .= $t;
	}
	return $out;
}

function c391_prio( $hook, $fn ) {
	return has_action( $hook, $fn );
}

$c391_tpl      = c391_src( 'template-parts/commerce/compare-table.php' );
$c391_tpl_code = c391_code( 'template-parts/commerce/compare-table.php' );
$c391_ctrl     = c391_src( 'inc/compare-table.php' );
$c391_ctrl_code = c391_code( 'inc/compare-table.php' );
$c391_fmt      = c391_src( 'assets/css/book-formats.css' );
$c391_fmt_min  = c391_src( 'assets/css/book-formats.min.css' );
$c391_style    = c391_src( 'style.css' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE COMPARISON TABLE RESTYLE
 * ═══════════════════════════════════════════════════════════════════════════ */
c391_head( '§1 THE TABLE RESTYLE (Concept B)' );

c391_ok( '§1.1 the template and its controller both exist', '' !== $c391_tpl && '' !== $c391_ctrl );

foreach ( array(
	'bhp-compare__head'       => 'the navy header band',
	'bhp-compare__besttab'    => 'the gold Best value tab',
	'bhp-compare__collabel'   => 'the column label',
	'bhp-compare__groupfill'  => 'the gold-lane filler cell',
	'bhp-compare__price'      => 'the large price',
	'bhp-compare__save'       => 'the saving pill',
	'bhp-compare__free'       => 'the FREE badge',
	'bhp-compare__check'      => 'the check disc',
	'bhp-compare__cue'        => 'the Look inside cue',
) as $c391_cls => $c391_what ) {
	c391_ok( "§1.2 markup carries .{$c391_cls} ({$c391_what})", false !== strpos( $c391_tpl, $c391_cls ) );
	c391_ok( "§1.3 stylesheet carries .{$c391_cls}", false !== strpos( $c391_fmt, '.' . $c391_cls ) );
}

/*
 * ⛔ CONCEPT A WAS DROPPED, NOT COMMENTED OUT. A selector for a panel that no
 *    markup emits is dead weight that reads as a feature to the next person.
 */
c391_ok( '§1.4 ⛔ no Concept A selector survives in the stylesheet', false === strpos( $c391_fmt, 'bhp-compare__aside' ) && false === strpos( $c391_fmt, 'bhp-compare__spread' ) );
/*
 * ⛔ THESE READ THE CSS RULE TEXT AND THE RENDERED MARKUP, NOT THE WHOLE
 *    FILE, AND THE DISTINCTION IS THE POINT. The 1.19.391 stamp comment in
 *    `book-formats.css` NAMES `.bhp-compare__split` in order to record that it
 *    was REMOVED, and this template's header says "NO EM DASH", "NO TRACKING
 *    CLAIM" and "NO RATING, REVIEW, AWARD". A whole-file `strpos()` cannot tell
 *    a SELECTOR from a NOTE SAYING THE SELECTOR IS GONE, so it fails a build
 *    for documenting itself — the same failure shape
 *    `test-cycle168-early-cart-capture.php` §1.0 records for comment-blind
 *    matching, and the "fix" a hurried reader reaches for is deleting the note.
 *
 * ⭐ SO THE RAILS RUN ON WHAT SHIPS: the CSS with comments stripped, the
 *    template's HTML with comments and PHP stripped, and the template's
 *    executable PHP. A CSS comment cannot contain the close sequence, so the
 *    strip is exact rather than approximate.
 */
$c391_fmt_rules = (string) preg_replace( '#/[*].*?[*]/#s', '', $c391_fmt );
$c391_tpl_html  = (string) preg_replace( '#<[?]php.*?[?]>#s', '', preg_replace( '#/[*].*?[*]/#s', '', $c391_tpl ) );
c391_ok( '§1.5 ⛔ and no Concept A wrapper survives either', false === strpos( $c391_fmt_rules, 'bhp-compare__split' ) && false === strpos( $c391_tpl_code, 'bhp-compare__split' ) );

/*
 * ⛔ THE RADIUS RAIL. `tests/test-aesthetics-tokens.php` §6.4 forbids a 10px or
 *    12px border-radius literal anywhere in this stylesheet. Asserted here too
 *    so this build's own suite catches it rather than a neighbouring one.
 */
c391_ok( '§1.6 ⛔ no 10px/12px border-radius literal was introduced', false === strpos( $c391_fmt, 'border-radius: 10px' ) && false === strpos( $c391_fmt, 'border-radius: 12px' ) );

/* ⛔ Gold as INK on cream fails contrast. #D9A45F is only ever a ground here. */
c391_ok( '§1.7 ⛔ the superseded gold #c4a15c is not reintroduced as a value', 0 === preg_match( '/:\s*#c4a15c/i', $c391_fmt ) );

/* ⛔ NO PRICE LITERAL, still. This is 389 §3.34's rail, re-asserted because the
   template was rewritten wholesale and a rewrite is exactly when one slips in. */
c391_ok( '§1.8 ⛔⛔ the rewritten template contains no hardcoded dollar figure', 0 === preg_match( '/\$\s?\d+\.\d\d/', $c391_tpl_code ) );
c391_ok( '§1.9 ⛔⛔ the controller still contains none either', 0 === preg_match( '/\$\s?\d+\.\d\d/', $c391_ctrl_code ) );

/*
 * ⭐ THE RESTYLE IS NOT ALLOWED TO BE A COPY CHANGE. Every visual substitution
 *    must carry the original words in `.screen-reader-text`, and the visual
 *    must be `aria-hidden`. Both halves matter: sr-text without aria-hidden
 *    reads the sentence twice, aria-hidden without sr-text loses it entirely.
 */
c391_ok( '§1.10 ⭐ visual substitutions carry the original words in .screen-reader-text', substr_count( $c391_tpl, 'screen-reader-text' ) >= 4 );
c391_ok( '§1.11 ⭐ and the visual replacements are aria-hidden', substr_count( $c391_tpl, 'aria-hidden' ) >= 4 );

/*
 * ⛔ THE GHOST TAB MUST STAY aria-hidden. It reserves the box that lines the
 *    two column names up. If it ever reaches the accessible name, the page
 *    tells a screen-reader user that a single book is the "Best value", which
 *    is both wrong and a claim nobody made.
 */
c391_ok(
	'§1.12 ⛔⛔ the ghost Best value tab is aria-hidden (it must never say a single book is the best value)',
	(bool) preg_match( '/bhp-compare__besttab--ghost[^>]*aria-hidden="true"/', $c391_tpl )
);

/* ⛔ Group headings span 2, with a filler cell, so the gold lane runs unbroken. */
c391_ok( '§1.13 ⛔ group headings use colspan="2" plus a filler cell, never colspan="3"', false !== strpos( $c391_tpl_html, 'colspan="2"' ) && false === strpos( $c391_tpl_html, 'colspan="3"' ) );

/*
 * ⛔ NO `wp_kses_post()` ON THE CHECK DISC. `$allowedposttags` does not carry
 *    `path`, so kses would silently delete the glyph and leave an empty span.
 *    Everything interpolated into that markup is escaped where it is built.
 */
c391_ok( '§1.14 ⛔ the check disc is not passed through wp_kses_post (it would strip <path>)', false === strpos( $c391_tpl_code, 'wp_kses_post' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE PDP ORDER — ANDREW'S RULING, 2026-09-07
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ Verbatim, as carried in the build brief: the format cards, price,
 *    add-to-cart and the Stripe express element stay ABOVE THE FOLD and ABOVE
 *    the table; the table moves below the purchase block.
 * ═══════════════════════════════════════════════════════════════════════════ */
c391_head( '§2 THE PDP ORDER' );

$c391_p_cmp    = c391_prio( 'woocommerce_single_product_summary', 'bhp_compare_table_render' );
$c391_p_cards  = c391_prio( 'woocommerce_single_product_summary', 'bhp_book_render_format_selector' );
$c391_p_ece    = c391_prio( 'woocommerce_single_product_summary', 'bhp_express_bridge_render' );

c391_ok( '§2.1 the comparison table renders at priority 17', 17 === $c391_p_cmp, 'got ' . var_export( $c391_p_cmp, true ) );
c391_ok( '§2.2 the format selector still renders at 15', 15 === $c391_p_cards, 'got ' . var_export( $c391_p_cards, true ) );
c391_ok( '§2.3 the Stripe express bridge still renders at 16', 16 === $c391_p_ece, 'got ' . var_export( $c391_p_ece, true ) );
c391_ok( '§2.4 ⭐ ANDREW 2026-09-07: the table is BELOW the whole purchase block', $c391_p_cmp > $c391_p_cards && $c391_p_cmp > $c391_p_ece );

/*
 * ⛔ NOTHING WAS INSERTED BETWEEN THE PURCHASE BLOCK AND THE TABLE. If a later
 *    lane parks a band at 17 or 16.5 the table stops sitting directly under
 *    the buy box, which is the half of the instruction a priority number alone
 *    does not protect.
 */
global $wp_filter;
$c391_between = array();
if ( isset( $wp_filter['woocommerce_single_product_summary'] ) ) {
	foreach ( $wp_filter['woocommerce_single_product_summary']->callbacks as $c391_prio_key => $c391_cbs ) {
		if ( $c391_prio_key > 16 && $c391_prio_key < 17 ) {
			foreach ( $c391_cbs as $c391_cb ) {
				$c391_between[] = is_string( $c391_cb['function'] ) ? $c391_cb['function'] : 'closure';
			}
		}
	}
}
c391_ok( '§2.5 ⛔ nothing is hooked between the express element and the table', array() === $c391_between, implode( ', ', $c391_between ) );

/* ⭐ The 389 suite's own order assertions were superseded in place, not deleted. */
$c391_389 = c391_src( 'tests/test-cycle179-build-389.php' );
c391_ok( '§2.6 ⭐ the 389 suite records the supersession rather than hiding it', false !== strpos( $c391_389, 'SUPERSEDED 1.19.391' ) );
c391_ok( '§2.7 ⛔ and the 389 suite no longer asserts the old ABOVE order', false === strpos( $c391_389, "and it renders ABOVE the format cards in the actual HTML', false !== \$b389_p_cmp" ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · THE LOOK-INSIDE PLATES COME UP UNDER THE GALLERY
 * ═══════════════════════════════════════════════════════════════════════════ */
c391_head( '§3 THE PDP LEFT COLUMN' );

/*
 * ⭐ THE PLACEMENT DID NOT CHANGE AND MUST NOT. `.bhp-pdp-left` has been in
 *    column 1, row 2 since 1.19.349. The 1,015px gap Andrew reacted to was a
 *    TRACK SIZING outcome, not a placement error, and asserting placement here
 *    is what stops a later pass "fixing" it by moving the block.
 */
c391_ok(
	'§3.1 ⭐ .bhp-pdp-left is still explicitly placed in column 1, row 2',
	(bool) preg_match( '/\.woocommerce div\.product > \.bhp-pdp-left \{[^}]*grid-column:\s*1;[^}]*grid-row:\s*2;/s', $c391_style )
);
c391_ok(
	'§3.2 ⭐ row 2 is flexible, so a spanning .summary no longer inflates row 1',
	(bool) preg_match( '/\.woocommerce div\.product \{\s*grid-template-rows:\s*max-content 1fr;\s*\}/', $c391_style )
);
c391_ok(
	'§3.3 ⛔ the row rule lives inside the desktop media query, not sitewide',
	(bool) preg_match( '/@media \(min-width: 901px\)\s*\{(?:(?!@media).)*grid-template-rows:\s*max-content 1fr;/s', $c391_style )
);
/*
 * ⛔⛔ MOBILE IS UNTOUCHED, AND THAT IS A FOUNDER RULE, NOT A PREFERENCE.
 *     Seal 672: ADD TO CART clears the fold. Below 901px the block keeps
 *     `order: 10` and stays under the buy box. Pulling the plates up on a
 *     phone would put a picture of a page above the button that sells it.
 */
c391_ok(
	'§3.4 ⛔⛔ below 901px the plates still sort AFTER the buy box (order: 10, seal 672)',
	(bool) preg_match( '/body\.single-product\.woocommerce div\.product > \.bhp-pdp-left \{\s*order:\s*10;/', c391_src( 'assets/css/pdp-content.css' ) )
);
c391_ok(
	'§3.5 ⭐ the block still renders from its own hook and nothing moved it',
	3 === c391_prio( 'woocommerce_after_single_product_summary', 'bhp_pdp_render_product_left_column' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE "LOOK INSIDE" CUE
 * ═══════════════════════════════════════════════════════════════════════════ */
c391_head( '§4 THE LOOK INSIDE CUE' );

c391_ok( '§4.1 the cue is a real anchor, so it works with JavaScript off', (bool) preg_match( '/<a class="bhp-compare__cue" href="#/', $c391_tpl ) );
c391_ok( '§4.2 it targets the hero gallery\'s own id', false !== strpos( $c391_tpl, "'bhp-look-inside-' . sanitize_html_class" ) );
c391_ok(
	'§4.3 ⭐ the gallery is an anchor target with the sticky-header reservation',
	(bool) preg_match( '/\.bhp-look-inside\[id\],\s*\.bhp-look-inside\[id\] > \.bhp-gallery \{\s*scroll-margin-top:\s*var\(--bhp-anchor-offset/', $c391_style )
);
/*
 * ⛔⛔ §4.3a IS THE ASSERTION THAT WOULD HAVE CAUGHT THE REAL DEFECT, and it
 *     exists because measuring found what reading did not: at 375 the anchor
 *     target computes `display: contents` and therefore has NO BOX for
 *     `scroll-margin-top` to apply to. The child selector is what makes the
 *     reservation reach a real box on a phone. Deleting it as a duplicate
 *     would silently reintroduce a header overlapping the gallery at 375 only.
 */
c391_ok(
	'§4.3a ⛔⛔ the reservation also reaches the child gallery, because the section is display:contents at 375',
	false !== strpos( $c391_style, '.bhp-look-inside[id] > .bhp-gallery' )
);
/*
 * ⛔ THE 390 ANCHOR RULE WAS NOT EDITED. Its selector list and the minified
 *    form of it are asserted by string in `test-cycle179-build-390.php`.
 *    Extending that list would have been tidier and would have risked turning
 *    a green suite red for a cosmetic reason.
 */
c391_ok( '§4.4 ⛔ the 1.19.390 anchor rule itself is untouched', false !== strpos( $c391_style, "#main {\n  scroll-margin-top: var(--bhp-anchor-offset, 93px);\n}" ) );

c391_ok( '§4.5 the cue is gated on the title actually having a look-inside', false !== strpos( $c391_tpl_code, 'bhp_book_has_look_inside' ) );
c391_ok( '§4.6 the thumbnails come from the gallery\'s own resolved items', false !== strpos( $c391_tpl_code, 'bhp_book_media' ) );
c391_ok( '§4.7 ⛔ the thumbnails are decorative: alt is forced empty', (bool) preg_match( "/'alt'\s*=>\s*''/", $c391_tpl_code ) );

/*
 * ⛔⛔ THE CLAIM RAIL, AND IT IS THE MOST IMPORTANT ASSERTION IN THIS FILE.
 *     "Real pages and a flip-through video" is a statement about the product,
 *     on a purchase page. It may only be printed when `bhp_book_media()`
 *     actually resolved a video for this title. A title with stills only gets
 *     the stills wording. An unconditional sentence here would be a fabricated
 *     product claim, which is the never-invent rule, not a copy nicety.
 */
c391_ok(
	'§4.8 ⛔⛔ the video wording is conditional on a video actually resolving',
	false !== strpos( $c391_tpl_code, '$bhp_cmp_cue_video' )
		&& (bool) preg_match( '/if\s*\(\s*\$bhp_cmp_cue_video\s*\)/', $c391_tpl_code )
);
c391_ok(
	'§4.9 ⛔ and a stills-only title has wording of its own that omits the video',
	false !== strpos( $c391_tpl, 'Real pages, in the gallery at the top of this page.' )
);

/*
 * ⭐ THE KEY IS PASSED IN, NEVER RE-DERIVED. The 389 suite includes this
 *    partial with a hand-built `$bhp_compare` and no queried object; a
 *    `get_queried_object_id()` here would resolve to the wrong post or none.
 */
c391_ok( '§4.10 ⭐ the template never looks the product up for itself', false === strpos( $c391_tpl_code, 'get_queried_object_id' ) );
c391_ok( '§4.11 ⭐ the controller passes the registry key through', (bool) preg_match( "/'key'\s*=>\s*\(string\)\s*\\\$key/", $c391_ctrl_code ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · RAILS THAT MUST NOT HAVE MOVED
 * ═══════════════════════════════════════════════════════════════════════════ */
c391_head( '§5 RAILS' );

$c391_all = $c391_tpl . $c391_fmt;

c391_ok( '§5.1 ⛔ no em dash in the customer-facing markup or the PHP strings', false === strpos( $c391_tpl_html, "\xE2\x80\x94" ) && false === strpos( $c391_tpl_code, "\xE2\x80\x94" ) );
/*
 * ⚠ "us" IS NOT TESTED HERE, AND THE OMISSION IS DELIBERATE. The shipping
 *   cell reads "in the contiguous US". `test-cycle179-build-389.php` §3.30
 *   records the false positive that produced: a case-insensitive word match
 *   on "us" hit the STATE-GROUP ABBREVIATION and fired on correct copy. That
 *   suite still checks "us" case-sensitively over the RENDERED output, which
 *   is the right place for it; this one checks the SOURCE, where the
 *   abbreviation and the pronoun are indistinguishable without rendering.
 */
c391_ok(
	'§5.2 ⛔ no company "we"/"our" in the template source (VOICE §9.1)',
	0 === preg_match( '/\b(we|our)\b/i', $c391_tpl_html . $c391_tpl_code )
);
c391_ok( '§5.3 ⛔⛔ no tracking claim anywhere in the customer-facing markup', 0 === preg_match( '/\btracking\b/i', $c391_tpl_html . $c391_tpl_code ) );
c391_ok(
	'§5.4 ⛔ no rating, review, award, urgency or scarcity claim in the template strings',
	0 === preg_match( '/\b(rating|reviews?|award-winning|best-?sell\w*|hurry|only \d+ left|limited time)\b/i', $c391_tpl_html . $c391_tpl_code )
);
c391_ok( '§5.5 ⛔ no aggregateRating or review schema is emitted from here', false === strpos( $c391_tpl_code, 'aggregateRating' ) );

/* ⭐ FREE stays uppercase in the string, never by text-transform. */
c391_ok( '§5.6 ⭐ FREE is uppercase in the string', false !== strpos( $c391_tpl, "esc_html__('FREE'" ) );
c391_ok(
	'§5.7 ⛔ and the badge is not uppercased by CSS instead',
	0 === preg_match( '/\.bhp-compare__free \{[^}]*text-transform/s', $c391_fmt )
);

/* ⭐ The minified stylesheet was rebuilt from this source, not left behind. */
c391_ok( '§5.8 ⭐ book-formats.min.css carries the new cue class', false !== strpos( $c391_fmt_min, 'bhp-compare__cue' ) );
c391_ok( '§5.9 ⛔ and it carries no Concept A leftovers', false === strpos( $c391_fmt_min, 'bhp-compare__aside' ) );

/* ⭐ The version really moved. A suite that passes against the previous build
     proves nothing about this one. */
c391_ok(
	'§5.10 ⭐ style.css declares 1.19.391 or later',
	(bool) preg_match( '/Version:\s*1\.19\.(\d+)/', $c391_style, $c391_v ) && (int) $c391_v[1] >= 391,
	isset( $c391_v[1] ) ? $c391_v[1] : 'no version line'
);

echo "\n=== CYCLE179-BUILD-391 ===\nPASS {$c391_pass}  FAIL {$c391_fail}\n";
