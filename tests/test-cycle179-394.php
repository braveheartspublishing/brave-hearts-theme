<?php
/**
 * test-cycle179-394.php — theme 1.19.394, 2026-09-07.
 * `CYCLE179-CX-BUILD-394` · commerce-cx (Pippin), under chief-of-staff.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE COVERS
 * ═══════════════════════════════════════════════════════════════════════════
 * The six CODE items of the seven-item brief. Item 7 is a contact sheet of
 * browser screenshots and is not a thing PHP can assert; it lives in the build
 * report.
 *
 *   §1  the PDP hero comes down so the thumbnail carousel clears the fold
 *   §2  the three format boxes centre under the title
 *   §3  the blog card frame stops cropping the first letter
 *   §4  the archive band is set like the shop's Expedition Catalog bar
 *   §5  the "Look inside" cue sits under the purchase block, not at the foot
 *   §6  "Request a Read-Aloud" leaves the contact page
 *   §7  rails that must not have moved
 *
 * ⛔⛔ WHAT A PHP SUITE CANNOT PROVE, SAID HERE SO IT IS NOT READ AS PROVED.
 *     Nothing below observes a pixel. It cannot show that the carousel clears
 *     an 830px fold, that the cards centre on 1083, or that no blog card
 *     shears its first letter, because every one of those is a RENDERING
 *     OUTCOME and a rule being present in a stylesheet is a different claim
 *     from a box being where the rule intends. Those are browser measurements
 *     with `window.innerWidth` and `window.innerHeight` asserted beside them,
 *     and they live in the build report, not here.
 *
 * ⭐ WHAT IT CAN PROVE: that the rules are live rather than commented, that
 *    the superseded values are struck rather than still applying, that the
 *    relocated cue was moved rather than duplicated, that the read-aloud
 *    buttons point somewhere that can take a request, and that no locked
 *    string, age band or schema rail was traded away to win a measurement.
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "This suite must run inside WordPress (wp eval-file).\n";
	return;
}

$c394_pass = 0;
$c394_fail = 0;

function c394_ok( $label, $cond, $detail = '' ) {
	global $c394_pass, $c394_fail;
	if ( $cond ) {
		++$c394_pass;
		echo "PASS  {$label}\n";
		return true;
	}
	++$c394_fail;
	echo "FAIL  {$label}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
	return false;
}

function c394_head( $t ) {
	echo "\n=== {$t} ===\n";
}

function c394_src( $rel ) {
	$f = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $f ) ? (string) file_get_contents( $f ) : '';
}

/**
 * Executable PHP only — the house rule from `test-cycle179-build-391.php`:
 * a raw `strpos()` cannot tell a CALL from a COMMENT EXPLAINING WHY NOT TO
 * CALL, so it punishes a well-documented file and invites someone to delete
 * the prose that prevents the defect.
 */
function c394_code( $rel ) {
	$raw = c394_src( $rel );
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

/**
 * ⭐ THE SAME PRINCIPLE FOR CSS, AND THIS BUILD IS EXACTLY WHY IT IS NEEDED.
 *    394.1 keeps `~~min-height: 560px~~` inside a comment so the movement
 *    stays readable. A raw `strpos()` for "560px" would read that struck,
 *    superseded value as though it were still applying and would pass a
 *    regression that had actually happened — or fail a build that is correct.
 *    Comments come out before any CSS rule is asserted.
 */
function c394_css( $rel ) {
	$raw = c394_src( $rel );
	return '' === $raw ? '' : (string) preg_replace( '!/\*.*?\*/!s', '', $raw );
}

$c394_pt      = c394_css( 'assets/css/product-template.css' );
$c394_pt_min  = c394_src( 'assets/css/product-template.min.css' );
$c394_style   = c394_css( 'style.css' );
$c394_style_r = c394_src( 'style.css' );
$c394_st_min  = c394_src( 'style.min.css' );
$c394_index   = c394_src( 'index.php' );
$c394_idx_c   = c394_code( 'index.php' );
$c394_cmp     = c394_src( 'template-parts/commerce/compare-table.php' );
$c394_cmp_c   = c394_code( 'template-parts/commerce/compare-table.php' );
$c394_contact = c394_code( 'page-contact.php' );
$c394_teach   = c394_code( 'page-teachers.php' );

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · THE PDP HERO COMES DOWN
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§1 PDP hero height (item 1)' );

c394_ok(
	'§1.1 ⭐ the gallery stage is sized by a bounded clamp, not a fixed pixel height',
	1 === preg_match( '/\.bhp-gallery__stage\s*\{[^}]*min-height:\s*clamp\(\s*360px\s*,\s*52vh\s*,\s*470px\s*\)/s', $c394_pt )
);

c394_ok(
	'§1.2 ⛔ the superseded 393 value (min-height: 560px) no longer applies anywhere live',
	0 === preg_match( '/min-height:\s*560px/', $c394_pt ),
	'still live outside a comment'
);

c394_ok(
	'§1.3 ⚠ and it is preserved struck in the prose, so the movement stays readable',
	false !== strpos( c394_src( 'assets/css/product-template.css' ), '~~min-height: 560px~~' )
);

/* ⭐ The clamp is bounded at BOTH ends on purpose: the floor stops a collapse
     back toward the 391 letterbox, the ceiling stops the 393 complaint. */
c394_ok(
	'§1.4 ⭐ the clamp has a floor and a ceiling (it can neither collapse nor regrow)',
	1 === preg_match( '/clamp\(\s*(\d+)px\s*,\s*\d+vh\s*,\s*(\d+)px\s*\)/', $c394_pt, $c394_cl )
		&& (int) $c394_cl[1] >= 300 && (int) $c394_cl[2] <= 500 && (int) $c394_cl[1] < (int) $c394_cl[2],
	isset( $c394_cl[0] ) ? $c394_cl[0] : 'no clamp found'
);

/* ⚠ The phone is deliberately untouched: 393.6's <=900px half and 393.7's
     sticky buy bar are the phone's answer and are not re-opened here. */
c394_ok(
	'§1.5 ⚠ the change is gated at 901px, so the phone layout is not re-opened',
	1 === preg_match( '/@media\s*\(\s*min-width:\s*901px\s*\)\s*\{[^{]*\.bhp-media-gallery--hero\s+\.bhp-gallery__stage/s', $c394_pt )
);

c394_ok(
	'§1.6 ⭐ the minified stylesheet was rebuilt from this source, not left behind',
	false !== strpos( $c394_pt_min, 'clamp(360px,52vh,470px)' )
		|| false !== strpos( $c394_pt_min, 'clamp(360px, 52vh, 470px)' ),
	'product-template.min.css is stale'
);

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · THE THREE FORMAT BOXES CENTRE UNDER THE TITLE
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§2 format buttons centred (item 4)' );

c394_ok(
	'§2.1 ⭐ the format rail is a centred flex row',
	1 === preg_match( '/\.bhp-formats__grid\s*\{[^}]*display:\s*flex[^}]*justify-content:\s*center/s', $c394_pt )
);

/* ⭐⭐ Card COUNT-AGNOSTIC on purpose. `repeat(3, ...)` would fix today's three
     cards and break on one or two — the empty-track bug with a new number. */
c394_ok(
	'§2.2 ⛔ it is NOT a hardcoded three-track grid (that repeats the bug with a new number)',
	0 === preg_match( '/\.bhp-formats__grid\s*\{[^}]*grid-template-columns:\s*repeat\(\s*3\s*,/s', $c394_pt )
);

c394_ok(
	'§2.3 ⭐ the rail wraps, so a wrapped last row also centres',
	1 === preg_match( '/\.bhp-formats__grid\s*\{[^}]*flex-wrap:\s*wrap/s', $c394_pt )
);

/* ⚠ 601px is the floor: below it the rail is `display: contents` inside
     `.bhp-formats`' own grid, where cards are placed by explicit grid-row /
     grid-column. Reaching into that would dismantle 1.19.349's mobile order. */
c394_ok(
	'§2.4 ⚠ the floor is 601px, so the measured mobile order is not dismantled',
	1 === preg_match( '/@media\s*\(\s*min-width:\s*601px\s*\)\s*\{[^{]*\.bhp-formats__grid\s*\{[^}]*justify-content:\s*center/s', $c394_pt )
);

/* ⛔ `test-cro-iterate1.php` §2.3/§2.4 read from the FIRST min-width:601px in
     this file to END OF FILE, so every future append lands in their window. */
c394_ok(
	'§2.5 ⛔ no `order:` or `display: none` was added inside that suite\'s window',
	0 === preg_match( '/@media\s*\(\s*min-width:\s*601px\s*\).*\.bhp-formats__grid.*?(\border\s*:\s*-?\d|display:\s*none)/s', $c394_pt )
);

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · THE BLOG CARD STOPS CROPPING THE FIRST LETTER
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§3 blog card 16:9 frame (item 2)' );

c394_ok(
	'§3.1 ⭐ the card image frame is 16:9, matching the source plates exactly',
	1 === preg_match( '/\.blog-card\s+\.card__image\s*\{[^}]*aspect-ratio:\s*16\s*\/\s*9/s', $c394_style )
);

/* ⚠ THE ONE THAT MAKES THE FIX REAL. Three earlier rules set an explicit pixel
     height on this element (.card__image 220px, .blog-card 250px, and the
     <=640px 220px override). An `aspect-ratio` beside a DEFINITE height is
     ignored, so without this reset the frame keeps cropping and the fix looks
     applied while doing nothing. */
c394_ok(
	'§3.2 ⚠ height is reset to auto, or the aspect-ratio is silently ignored',
	1 === preg_match( '/\.blog-card\s+\.card__image\s*\{[^}]*height:\s*auto/s', $c394_style )
);

c394_ok(
	'§3.3 ⭐ the media wrapper carries the same ratio so the frame cannot letterbox',
	1 === preg_match( '/\.blog-card\s+\.card__media\s*\{[^}]*aspect-ratio:\s*16\s*\/\s*9/s', $c394_style )
);

/* ⛔ NO IMAGE IS RE-EXPORTED. The images were never wrong; the frame was. */
c394_ok(
	'§3.4 ⭐ object-fit stays cover (with matching ratios it now has nothing to crop)',
	1 === preg_match( '/\.blog-card\s+\.card__image\s*\{[^}]*object-fit:\s*cover/s', $c394_style )
);

/*
 * ⚠ THE ASSERTION MUST BE MULTILINE, AND THE FIRST DRAFT OF IT WAS NOT.
 *   `tools/build-css.mjs` strips comments and collapses blank lines. THAT IS
 *   ALL — it deliberately does NOT collapse whitespace inside rules (the tool
 *   says so at its head, and the restraint is a measured decision). So the
 *   minified rule is still spread over five lines and still reads
 *   `aspect-ratio: 16 / 9` with its spaces. A single-line pattern, or a
 *   `strpos()` for `aspect-ratio:16/9`, finds nothing and reports a correctly
 *   rebuilt stylesheet as stale — which is the expensive kind of wrong,
 *   because it teaches the next builder to distrust a passing artefact.
 */
c394_ok(
	'§3.5 ⭐ style.min.css was rebuilt and carries the blog-card frame',
	1 === preg_match( '/\.blog-card\s+\.card__image\s*\{[^}]*aspect-ratio:\s*16\s*\/\s*9/s', $c394_st_min ),
	'style.min.css is stale'
);

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · THE ARCHIVE BAND
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§4 archive band matched to the shop bar (item 3)' );

c394_ok( '§4.1 ⭐ the band renders', false !== strpos( $c394_index, 'bhp-archive-band' ) );

/* ⛔ THE H1'S WORDS AND ITS LOGIC DO NOT MOVE. Locked copy is proposed,
     never changed — only the setting changes. All four branches must survive. */
c394_ok( '§4.2 ⛔ branch 1 of 4 — "Field Notes" on the index', false !== strpos( $c394_index, "esc_html__('Field Notes', 'brave-hearts')" ) );
c394_ok( '§4.3 ⛔ branch 2 of 4 — the_archive_title() on an archive', false !== strpos( $c394_idx_c, 'the_archive_title()' ) );
c394_ok( '§4.4 ⛔ branch 3 of 4 — the search string on a search', false !== strpos( $c394_idx_c, 'get_search_query()' ) );
c394_ok( '§4.5 ⛔ branch 4 of 4 — "Posts" as the fallback', false !== strpos( $c394_index, "esc_html__('Posts', 'brave-hearts')" ) );

/* ⛔ NO NEW CLAIM AND NO NEW STRING: the series line and the age band are the
     two strings the shop band already prints. */
c394_ok( '§4.6 ⭐ the series line is the shop band\'s own string', false !== strpos( $c394_index, 'Adventures of Charlotte and Henry' ) );
c394_ok( '§4.7 ⛔ the age band reads 6 to 9, never 5 to 9', false !== strpos( $c394_index, 'Ages 6 to 9' ) && false === strpos( $c394_index, 'Ages 5 to 9' ) );

/* ⚠ The archive description is KEPT and compacted, not dropped. Losing real
     content to win a height measurement would be the wrong trade. */
c394_ok( '§4.8 ⚠ the archive description survives the reset', false !== strpos( $c394_idx_c, 'the_archive_description()' ) );

/* ⭐ 1.19.269 item 5 (founder ruling) REMOVED a decorative eyebrow above this
     H1. THAT REMOVAL STANDS and is not reversed by carrying the series row. */
c394_ok(
	'§4.9 ⛔ the eyebrow removed by the 2026-08-19 founder ruling is NOT reinstated',
	false === strpos( $c394_idx_c, 'Field Notes from the Real World' )
);

c394_ok( '§4.10 ⭐ the band is styled, not just marked up', false !== strpos( $c394_style, '.bhp-archive-band__row' ) );
c394_ok( '§4.11 ⭐ and the minified sheet carries it', false !== strpos( $c394_st_min, 'bhp-archive-band' ), 'style.min.css is stale' );

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · THE "LOOK INSIDE" CUE MOVES UP
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§5 look-inside cue under the purchase block (item 5)' );

/* ⛔⛔ THE FAILURE THIS SECTION EXISTS FOR IS DUPLICATION, NOT ABSENCE. A
     relocation done by copy-then-forget leaves TWO cues, and the page still
     "looks right" from the top. Exactly one anchor, or this fails. */
c394_ok(
	'§5.1 ⛔ exactly ONE cue anchor exists (a relocation must not duplicate)',
	1 === substr_count( $c394_cmp_c, 'class="bhp-compare__cue"' ),
	substr_count( $c394_cmp_c, 'class="bhp-compare__cue"' ) . ' found'
);

$c394_cue_at  = strpos( $c394_cmp_c, 'class="bhp-compare__cue"' );
$c394_grid_at = strpos( $c394_cmp_c, 'bhp-compare__every' );
$c394_note_at = strpos( $c394_cmp_c, 'bhp-compare__note' );

c394_ok(
	'§5.2 ⭐ the cue now precedes the comparison body (it is at the head of the section)',
	false !== $c394_cue_at && false !== $c394_grid_at && $c394_cue_at < $c394_grid_at,
	"cue@{$c394_cue_at} body@{$c394_grid_at}"
);

c394_ok(
	'§5.3 ⛔ it is no longer at the foot, above the hardcover note',
	false !== $c394_note_at && $c394_cue_at < $c394_note_at
);

/* ⭐⭐ THE DOM MOVED, NOT THE PAINT ORDER. A CSS `order: -1` would have made
     the same picture and left reading order and tab order at the old place.
     The cue is a LINK; a link read last and painted first is a defect only
     some people can see. */
c394_ok(
	'§5.4 ⭐⭐ the move is in the DOM, not faked with CSS order on the cue',
	0 === preg_match( '/\.bhp-compare__cue\s*\{[^}]*\border\s*:\s*-?\d/s', $c394_style )
		&& 0 === preg_match( '/\.bhp-compare\s*\{[^}]*\border\s*:\s*-?\d/s', $c394_style )
);

/* ⛔ THE JUMP BEHAVIOUR IS UNTOUCHED, AND THAT WAS THE RISK OF MOVING IT. */
c394_ok( '§5.5 ⛔ the gallery-jump hook moved with it', false !== strpos( $c394_cmp_c, 'data-bhp-gallery-jump' ) );
c394_ok( '§5.6 ⛔ the anchor target is unchanged', false !== strpos( $c394_cmp_c, "'bhp-look-inside-'" ) );
c394_ok( '§5.7 ⛔ the thumbnail chips moved with it', false !== strpos( $c394_cmp_c, 'bhp-compare__cuethumb' ) );

/* ⛔ The sub-line names a flip-through video ONLY when one actually resolved.
     That is a product claim on a purchase page, not a caption. */
c394_ok(
	'§5.8 ⛔ the video sub-line is still conditional on a video existing',
	false !== strpos( $c394_cmp_c, '$bhp_cmp_cue_video' )
		&& false !== strpos( $c394_cmp, 'Real pages, in the gallery' )
		&& false !== strpos( $c394_cmp, 'Real pages and a flip-through video' )
);

/* ⚠ A reader who knows where the cue used to be should not be left searching. */
c394_ok( '§5.9 ⚠ a sign-post is left at the old site', false !== strpos( $c394_cmp, 'USED TO BE HERE' ) );

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · "REQUEST A READ-ALOUD" LEAVES THE CONTACT PAGE
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§6 read-aloud button destination (item 6)' );

c394_ok(
	'§6.1 ⭐ the contact page sends it to the page that can take a request',
	false !== strpos( $c394_contact, "home_url('/school-read-alouds/')" )
);

/* ⛔⛔ THE DEFECT WAS SELF-REFERENCE: the button reloaded the page the reader
     was already standing on. That is a TRUST defect, not a cosmetic one. */
c394_ok(
	'§6.2 ⛔ it no longer resolves to the contact page it sits on',
	0 === preg_match( "/\\\$read_aloud_url\s*=\s*add_query_arg\(\s*'inquiry'\s*,\s*'read-aloud'/", $c394_contact )
);

/* ⛔ The destination is /school-read-alouds/ and NOT /read-aloud/. The brief
     guessed /read-aloud/; that page is the post-visit take-home landing page
     and cannot book anything. Verified live before the change. */
c394_ok(
	'§6.3 ⛔ and it is not pointed at /read-aloud/, which cannot book anything',
	0 === preg_match( "!\\\$read_aloud_url\s*=\s*home_url\(\s*'/read-aloud/'!", $c394_contact )
);

c394_ok(
	'§6.4 ⭐ the teachers page uses the same destination, so the same label means the same thing',
	false !== strpos( $c394_teach, "home_url('/school-read-alouds/')" )
);

/* ⛔ THIS IS THE FALLBACK ONLY. The post meta and its filter still win, so the
     destination is reversible from the database without another release. */
c394_ok(
	'§6.5 ⛔ the teachers page keeps its meta override, so this stays reversible without a release',
	false !== strpos( $c394_teach, "read_aloud_url" ) && false !== strpos( $c394_teach, 'bhp_get_safe_link_url' )
);

/* ⛔ A schools-and-media inquiry and a general question DO belong in the form
     on the contact page. Nothing in the instruction reaches them. */
c394_ok( '§6.6 ⛔ the school-library inquiry still uses the contact form', false !== strpos( $c394_contact, "'school-library'" ) );
c394_ok( '§6.7 ⛔ the general inquiry still uses the contact form', false !== strpos( $c394_contact, '$general_url' ) );

/* ⭐ The site already agreed: the primary nav "Read-Alouds" item points here. */
c394_ok(
	'§6.8 ⭐ the destination agrees with the primary navigation, so the site is self-consistent',
	false !== strpos( c394_code( 'functions.php' ), 'school-read-alouds' )
);

/* ═══════════════════════════════════════════════════════════════════════════
   §7 · RAILS THAT MUST NOT HAVE MOVED
   ═══════════════════════════════════════════════════════════════════════════ */
c394_head( '§7 rails' );

$c394_new = $c394_index . $c394_cmp . c394_src( 'page-contact.php' ) . c394_src( 'page-teachers.php' );

c394_ok( '§7.1 ⛔ no aggregateRating or review schema is emitted from any file this build touched', false === strpos( $c394_new, 'aggregateRating' ) );

c394_ok(
	'§7.2 ⛔ no rating, review, award, urgency or scarcity claim in the touched templates',
	0 === preg_match( '/\b(award-winning|best-?sell\w*|hurry|only \d+ left|limited time|\d+ ?(?:star|reviews?)\b)/i', $c394_new )
);

/* ⛔ No "we/us/our" in customer-facing strings (§9.1), checked on the
     TRANSLATED STRINGS only — the prose above them is a record for the next
     reader and is not shown to a parent. */
$c394_strings = '';
if ( preg_match_all( "/esc_html_e\(\s*'([^']+)'|esc_html__\(\s*'([^']+)'/", $c394_index . $c394_cmp, $c394_m ) ) {
	$c394_strings = implode( ' | ', array_filter( array_merge( $c394_m[1], $c394_m[2] ) ) );
}
/*
 * ⚠⚠ THE `i` FLAG MADE THIS RULE FIRE ON A COUNTRY, AND THE FIX IS NOT TO
 *    WEAKEN THE RULE. Case-insensitively, `\bus\b` matches the **US** in
 *    "Starts at %s in the contiguous US" — an approved shipping string that
 *    contains no first person at all. A false positive on a §9.1 rail is
 *    dangerous in a specific way: the cheapest way to make it green is to
 *    reword a correct customer-facing string, so the test would have caused
 *    the very defect it exists to prevent.
 *
 * ⭐ MATCHED CASE-SENSITIVELY INSTEAD. First-person "we", "us" and "our" are
 *    lower case in running copy; the country is upper case. "We" at the head
 *    of a sentence is still caught by the explicit alternation below, so
 *    nothing is given up to buy the fix.
 */
c394_ok(
	'§7.3 ⛔ no "we", "us" or "our" in any customer-facing string this build emits',
	0 === preg_match( '/\b(we|us|our|We|Us|Our|we\'re|We\'re|we\'ve|We\'ve)\b/', $c394_strings ),
	$c394_strings
);

c394_ok( '§7.4 ⛔ no em dash in any customer-facing string this build emits', false === strpos( $c394_strings, "\xe2\x80\x94" ) );

/* ⛔ American spelling in customer-facing strings. */
c394_ok(
	'§7.5 ⛔ American spelling in the emitted strings',
	0 === preg_match( '/\b(colour\w*|behaviour\w*|centre|favourite|organis\w+|recognis\w+)\b/i', $c394_strings ),
	$c394_strings
);

/* ⛔ Hardcover is intentionally out of stock and nothing here re-opens it. */
c394_ok(
	'§7.6 ⛔ nothing in this build writes stock status',
	false === strpos( $c394_new, 'set_stock_status' ) && false === strpos( $c394_new, "update_post_meta" )
);

/*
 * ⛔ No price is typed into a template.
 *
 * ⚠ READ FROM THE COMMENT-STRIPPED CODE, NOT THE RAW FILE. The raw templates
 *   carry `/* translators: %s is the amount saved, e.g. $3.98 * /` and
 *   `e.g. $1.99` — translator notes, which are exactly the documentation that
 *   keeps a real price OUT of the markup. Matching them would punish the
 *   comment for describing the thing it prevents, which is the same mistake
 *   `c394_code()` exists at the head of this file to avoid.
 */
$c394_new_code = $c394_idx_c . $c394_cmp_c . $c394_contact . $c394_teach;
c394_ok(
	'§7.7 ⛔ no hardcoded dollar price in the touched templates',
	0 === preg_match( '/\$\d+\.\d{2}/', $c394_new_code )
);

/* ⛔ BookVAULT Shipping must never be zoned, and nothing here goes near it. */
c394_ok( '§7.8 ⛔ nothing here touches shipping methods or zones', false === stripos( $c394_new, 'BookVAULT Shipping' ) && false === strpos( $c394_new, 'WC_Shipping_Zone' ) );

/* ⭐ The version really moved. A suite that passes against the previous build
     proves nothing about this one. */
c394_ok(
	'§7.9 ⭐ style.css declares 1.19.394 or later',
	(bool) preg_match( '/Version:\s*1\.19\.(\d+)/', $c394_style_r, $c394_v ) && (int) $c394_v[1] >= 394,
	isset( $c394_v[1] ) ? $c394_v[1] : 'no version line'
);

/* ⭐⭐ THE ARTEFACT-INTEGRITY RAIL, AND THIS BUILD IS WHY IT IS HERE.
     The first 1.19.394 artefact installed on staging was CRLF-corrupted: the
     shipped style.css no longer matched the `source-md5:` stamp inside the
     shipped style.min.css, exactly as RUNBOOK's 2026-09-05 correction warns.
     Asserting it from INSIDE the deployed theme is the only check that reads
     the bytes that actually landed on the server. */
if ( preg_match( '/source-md5:\s*([0-9a-f]{32})/', $c394_st_min, $c394_sm ) ) {
	c394_ok(
		'§7.10 ⭐⭐ the DEPLOYED style.css matches the source-md5 stamp in the DEPLOYED style.min.css',
		md5( $c394_style_r ) === $c394_sm[1],
		'stamp ' . $c394_sm[1] . ' vs shipped ' . md5( $c394_style_r )
	);
} else {
	c394_ok( '§7.10 ⭐⭐ style.min.css carries a source-md5 stamp', false, 'no stamp found' );
}

c394_ok(
	'§7.11 ⛔ no CR bytes survived into the deployed style.css',
	false === strpos( $c394_style_r, "\r" )
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠⚠ THE SUMMARY IS READ OUT OF `$GLOBALS`, AND THAT IS NOT A STYLE CHOICE.
 * ═══════════════════════════════════════════════════════════════════════════
 * `wp eval-file` includes this file INSIDE A METHOD. Everything written at the
 * "top level" here is therefore a LOCAL variable, while `c394_ok()` increments
 * the counters it declared `global`. The two are different variables that
 * happen to share a name, so the tally printed at the end was
 * **PASS 0  FAIL 0 while individual FAIL lines were printing above it** —
 * observed on staging 2026-09-07, not reasoned about.
 *
 * ⛔ THAT IS THE WORST AVAILABLE FAILURE MODE FOR A TEST SUITE: a green-looking
 *    total sitting underneath real failures. Anyone reading only the last line
 *    of the run — which is what a summary line is FOR — is told the opposite of
 *    the truth.
 *
 * ⚠ THIS IS NOT UNIQUE TO THIS FILE. The same `global`-plus-top-level-echo
 *   idiom is used by the other suites in this directory, so their tallies are
 *   very likely reporting zero as well. Reported upward rather than fixed
 *   across 140 files from this desk, which is not this build's scope.
 */
$c394_pass_total = isset( $GLOBALS['c394_pass'] ) ? (int) $GLOBALS['c394_pass'] : 0;
$c394_fail_total = isset( $GLOBALS['c394_fail'] ) ? (int) $GLOBALS['c394_fail'] : 0;

echo "\n=== CYCLE179-CX-BUILD-394 ===\nPASS {$c394_pass_total}  FAIL {$c394_fail_total}\n";
