<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE BLOG LAYOUT SUITE — theme 1.19.323, 2026-08-29,
 * `CYCLE169-LD-R3-IMGCAP-ATTRIBUTION` (round 2 `…-BLOG-LAYOUT-R2` at 1.19.322;
 * written at 1.19.321, `…-TEMPLATE`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle169-blog-layout.php --user=1
 *
 * Covers the limbs of the standard blog-post layout pattern:
 *   1. no book-sales module at the top of the article
 *   2. a slim inline capture band after the SECOND TOP-LEVEL PARAGRAPH
 *   3. the book-sales module at roughly the three-quarter mark
 *   4. the footer capture untouched
 *   5. in-body content images capped — ⭐ 1.19.323: BY HEIGHT, on every markup path
 *   6. ⭐ 1.19.322 — the FEATURED image inside the content column, cropped short
 *   7. ⭐⭐ 1.19.323 — FORM-MOMENT ATTRIBUTION CAPTURE (behavioural)
 *   8. ⭐⭐ 1.19.323 — the featured-image hide toggle, shipped OFF
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠⚠ ASSERTIONS CHANGED AT 1.19.323 — READ THIS BEFORE TRUSTING A GREEN RUN
 * ═══════════════════════════════════════════════════════════════════════════
 * FIVE assertions were rewritten and one version floor was raised. The round-2
 * list below it is preserved verbatim and is still the record for 1.19.322.
 *
 *   §0.5   floor 1.19.322 -> 1.19.323.                      (version bump)
 *   §5.1   "a 60% cap exists" -> "a HEIGHT cap exists, clamp(200px,45vh,460px)".
 *          ⛔ SPEC CHANGE, and the one that would look like a weakening if read
 *          alone. The round-3 brief specifies max-width 100% + max-height ~45vh.
 *          The 60% WIDTH cap was never what bounded a tall graphic: MEASURED IN
 *          A BROWSER at 1440x900 on post 5089, the in-body image rendered
 *          312 x 473 px — 53% of the viewport HEIGHT — while obeying the 60%
 *          width rule perfectly. §5.1b and §5.1c were ADDED so the retired cap
 *          is proven GONE rather than merely unasserted.
 *   §5.2   selector widened: `:is(img, figure, .wp-block-image)` ->
 *          `:is(img, picture, video, svg)` on the replaced elements, with the
 *          wrappers handled separately.  SPEC CHANGE — markup-path agnosticism
 *          is the round-3 requirement. §5.2b ADDED to assert the height cap is
 *          NOT on the <figure>, which would clip a caption.
 *   §5.5   restore selector widened to match, and now also asserts the two NEW
 *          reset properties (`max-height: none`, `object-fit`). §5.5b ADDED for
 *          the book rail's `width: 100%`, which the new (0,3,0) `width: auto`
 *          would otherwise defeat.
 *   §5.6   "the cap is released on narrow viewports" -> "the cap is
 *          VIEWPORT-RELATIVE with a floor".  ⛔ THE OLD ONE WOULD STILL HAVE
 *          PASSED — it matched the string `@media (max-width: 640px)`, which
 *          still exists in the file for an unrelated reason — WHILE ASSERTING
 *          SOMETHING NO LONGER TRUE. A green test whose label lies is worse
 *          than a red one, which is why it was replaced rather than left.
 *
 * ⭐ NOT ONE ASSERTION WAS RELAXED TO MAKE A FAILING BUILD PASS, AND NONE WAS
 *    DELETED WITHOUT A REPLACEMENT COVERING THE SAME PROPERTY. §5.3, §5.4a,
 *    §5.4b, §5.7–§5.16 and every §1–§4 and §6 assertion are UNTOUCHED.
 *    ~50 assertions were ADDED (§5.1b–§5.6b, all of §7, all of §8).
 *
 * ⚠⚠ AND ONE ASSERTION WRITTEN IN THIS RELEASE WAS ITSELF CHANGED IN THE SAME
 *    SITTING. §5.3b pinned `width: auto` + `object-fit: contain` and was GREEN;
 *    the expression was replaced with `width: 100%` + `object-fit: scale-down`
 *    because an unloaded image under `width: auto` has a 0 x 0 box and the
 *    article reflows around every picture as it lands. §5.3c was added as the
 *    regression guard.
 *
 * ⛔⛔ A CLAIM MADE DURING THAT CHANGE WAS WITHDRAWN AND THE WITHDRAWAL IS
 *     RECORDED RATHER THAN DELETED: the first version of §5.3b's comment said
 *     `width: auto` also stopped lazy images loading at all, citing a staging
 *     observation. The observation was real; the CAUSE was the harness
 *     (`document.hidden === true`, and Chrome does not lazy-load in a hidden
 *     document), and the same image also failed to load under `width: 100%`.
 *     ⭐ A check that did not measure what it claimed to measure is a
 *     FABRICATED CHECK, in the same failure class as a fabricated review.
 *     Full detail at §5.3b's own comment.
 *
 * ⛔ NO OTHER SUITE'S ASSERTIONS WERE TOUCHED BY THIS RELEASE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠⚠ ASSERTIONS CHANGED AT 1.19.322 — READ THIS BEFORE TRUSTING A GREEN RUN
 * ═══════════════════════════════════════════════════════════════════════════
 * This suite tests a feature whose SPECIFICATION legitimately changed after the
 * founder reviewed 1.19.321 on staging. Eight assertions were rewritten and one
 * version floor was raised. ⛔ EVERY ONE IS LISTED HERE WITH ITS OLD TEXT, ITS
 * NEW TEXT AND THE REASON, because "I updated the tests" is the sentence behind
 * which a silently weakened suite hides:
 *
 *   §0.5   floor 1.19.321 -> 1.19.322.                     (version bump)
 *   §2.1   the tunable renamed: `…_after_section` (h2 count, default 2) ->
 *          `…_after_paragraph` (paragraph count, default 2).  SPEC CHANGE.
 *   §2.2   was "anchors on the heading that opens section 3"; now "anchors just
 *          after the second top-level `</p>`".                SPEC CHANGE.
 *   §2.3   was "refuses a post with too few HEADINGS"; that refusal no longer
 *          exists — a heading-less post is now a perfectly good band host. It is
 *          REPLACED, not deleted, by the refusal that DOES still exist: a post
 *          with no clean top-level paragraph at all.           SPEC CHANGE.
 *   §2.4   was "refuses a post with no headings at all"; a two-paragraph post
 *          now gets a band unless the max-ratio guard stands it down. Replaced
 *          by a direct test of that guard.                     SPEC CHANGE.
 *   §3.5b  CASE A was "a band anchor past the max ratio stands the band down",
 *          built on a fixture whose SECTION 2 ran long. A long section 2 is no
 *          longer a band-placement risk, so the fixture would no longer prove
 *          anything. Rebuilt on a SHORT post, which is where the same guard now
 *          bites.                                              SPEC CHANGE.
 *   §3.5c  CASE A's rail band widened 60–85% -> 60–90%: with the ask no longer
 *          competing for room, the rail is free to sit at its own anchor.
 *   §3.5d  CASE B was "the band renders between 30% and 85%"; the entire point
 *          of this release is that it now renders EARLY, so the window is
 *          0–25%. ⛔ THIS IS THE ONE THAT WOULD LOOK LIKE A WEAKENING IF READ
 *          ALONE AND IS NOT: the bound moved DOWN the page-depth axis, which is
 *          the direction the founder asked for, and §3.5e still requires a real
 *          gap between the two controls.                       SPEC CHANGE.
 *   §5.4b  was "the pre-existing masthead rule is still present, i.e. this
 *          release did not disturb featured-image handling". 1.19.322 DOES
 *          disturb featured-image handling, on instruction, so that label is now
 *          a false claim. Replaced by §5.7–§5.12, which assert the new shape
 *          positively instead of asserting an absence of change.
 *
 * ⭐ NOT ONE ASSERTION WAS RELAXED TO MAKE A FAILING BUILD PASS, and none was
 *    deleted without a replacement covering the same property. §5.4a, §2.6,
 *    §3.1–§3.4 and every §26 assertion are untouched.
 * ⛔ NO OTHER SUITE'S ASSERTIONS WERE EDITED BY THIS RELEASE.
 *
 * ⚠ SEPARATELY FROM THE SPEC CHANGES ABOVE, ONE NEWLY-WRITTEN ASSERTION WAS
 *   CORRECTED IN THE SAME SITTING BECAUSE **I** HAD IT WRONG, NOT THE BUILD —
 *   §2.4c, where I miscounted the paragraphs in my own fixture (3 asserted,
 *   5 actual). The build was verified directly on staging BEFORE the assertion
 *   was touched; the full note is at the assertion. This is the same failure
 *   class as the two round-1 corrections recorded at §2.8b and §5.4a, and it is
 *   recorded for the same reason: a test author who quietly edits a number until
 *   it goes green has stopped testing anything.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is PHP and source level. It cannot see layout, colour, a tap target,
 * console cleanliness, or what an image actually measures on a rendered page.
 * Limb 5 in particular is asserted here as CSS SHAPE ONLY and is labelled
 * `[source]`; the rendered width is measured in a browser at a stated
 * `window.innerWidth` and reported separately. A source assertion is honest
 * about being one; it is not a substitute for the measurement.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, no post_content on any environment.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['bhp_c169_pass'] = 0;
$GLOBALS['bhp_c169_fail'] = 0;

function bhp_c169_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_c169_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_c169_fail']++;
		echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}
function bhp_c169_head( $title ) {
	echo "\n=== {$title} ===\n";
}
function bhp_c169_file( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}
function bhp_c169_render( $slug ) {
	ob_start();
	get_template_part( $slug );
	return (string) ob_get_clean();
}
function bhp_c169_affiliate_count( $html ) {
	return preg_match_all( '#https?://(?:[a-z0-9.-]*\.)?(?:amzn\.to|amazon\.[a-z.]+)/#i', (string) $html );
}
/** Visible-text percentage at which `$needle` sits inside `$html`. */
function bhp_c169_text_pct( $html, $needle ) {
	$at = strpos( $html, $needle );
	if ( false === $at ) {
		return -1.0;
	}
	$total = strlen( wp_strip_all_tags( $html ) );
	if ( $total < 1 ) {
		return -1.0;
	}
	return round( 100 * strlen( wp_strip_all_tags( substr( $html, 0, $at ) ) ) / $total, 1 );
}

$blog_src = bhp_c169_file( 'inc/blog-post-template.php' );
$band_src = bhp_c169_file( 'template-parts/acquisition/post-capture-band.php' );
$css_src  = bhp_c169_file( 'assets/css/blog-post.css' );

/* ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§0 — sources present' );

bhp_c169_ok( '§0.1 the component source is readable', '' !== $blog_src );
bhp_c169_ok( '§0.2 the band template exists', '' !== $band_src );
bhp_c169_ok( '§0.3 the stylesheet is readable', '' !== $css_src );
bhp_c169_ok( '§0.4 the band defaults ON', function_exists( 'bhp_blog_capture_band_enabled' ) && bhp_blog_capture_band_enabled() );
/* ⚠ CHANGED AT 1.19.322 — floor raised from 1.19.321. A version floor that is
     never raised stops proving the build under test is the build deployed. */
bhp_c169_ok(
	'§0.5 the theme version is at least 1.19.323',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.323', '>=' ),
	(string) wp_get_theme()->get( 'Version' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 — LIMBS 1 AND 3: THE BOOK-SALES MODULE IS AT ~3/4, NOT AT THE TOP.
 *
 * A synthetic article of six sections, so the anchor arithmetic is exercised
 * against a known shape rather than against whatever a real post happens to be.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§1 LIMBS 1+3 — the sales module sits at roughly three quarters' );

$body = '<p>Intro one, a sentence of reasonable length for a field note.</p>'
	. '<p>Intro two, also a sentence of reasonable length for a field note.</p>';
for ( $i = 1; $i <= 6; $i++ ) {
	$body .= "<h2>Section {$i}</h2>";
	for ( $j = 1; $j <= 4; $j++ ) {
		$body .= "<p>Section {$i} paragraph {$j}, written long enough to carry real weight in a visible-text measurement.</p>";
	}
}

bhp_c169_ok( '§1.1 the ratio tunable exists and defaults to 0.75', function_exists( 'bhp_blog_rail_position_ratio' ) && 0.75 === bhp_blog_rail_position_ratio() );

$rail_off = bhp_blog_rail_offset( $body );
$rail_pct = ( null === $rail_off ) ? -1.0 : round( 100 * strlen( wp_strip_all_tags( substr( $body, 0, $rail_off ) ) ) / strlen( wp_strip_all_tags( $body ) ), 1 );

bhp_c169_ok( '§1.2 an offset is chosen on a six-section article', null !== $rail_off );
bhp_c169_ok(
	'§1.3 ⭐ LIMB 3: it lands between 65% and 80% of the visible text',
	$rail_pct >= 65.0 && $rail_pct <= 80.0,
	"{$rail_pct}%"
);
bhp_c169_ok(
	'§1.4 ⛔ LIMB 1: it is NOT at the top — nowhere near the old second-<h2> anchor',
	$rail_pct > 40.0,
	"{$rail_pct}%"
);
bhp_c169_ok(
	'§1.5 the chosen offset is a tag boundary, never inside an attribute',
	null !== $rail_off && ( '<' === substr( $body, $rail_off, 1 ) || '>' === substr( $body, $rail_off - 1, 1 ) )
);
bhp_c169_ok(
	'§1.6 the "later of two anchors" invariant survives (max, never min)',
	false !== strpos( $blog_src, 'max( $h2_offset, $p_offset )' )
);

/* The tunable must actually tune, or it is decoration. */
add_filter( 'bhp_blog_rail_position_ratio', 'bhp_c169_half' );
function bhp_c169_half() {
	return 0.5; }
$half_off = bhp_blog_rail_offset( $body );
$half_pct = ( null === $half_off ) ? -1.0 : round( 100 * strlen( wp_strip_all_tags( substr( $body, 0, $half_off ) ) ) / strlen( wp_strip_all_tags( $body ) ), 1 );
remove_filter( 'bhp_blog_rail_position_ratio', 'bhp_c169_half' );

bhp_c169_ok( '§1.7 the ratio filter genuinely moves the rail', $half_pct > 0 && $half_pct < $rail_pct, "0.5 -> {$half_pct}% vs 0.75 -> {$rail_pct}%" );
bhp_c169_ok( '§1.8 the filter is removed again', 0.75 === bhp_blog_rail_position_ratio() );

/* A ratio filter must never be able to push a commerce control above the fold. */
add_filter( 'bhp_blog_rail_position_ratio', 'bhp_c169_zero' );
function bhp_c169_zero() {
	return 0.0; }
bhp_c169_ok( '§1.9 ⛔ an out-of-range ratio is clamped, never obeyed', bhp_blog_rail_position_ratio() >= 0.05 );
remove_filter( 'bhp_blog_rail_position_ratio', 'bhp_c169_zero' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 — LIMB 2: THE SLIM BAND, AND ITS PLACEMENT.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§2 LIMB 2 — the slim capture band' );

/*
 * ⚠ §2.1 CHANGED AT 1.19.322. It asserted `bhp_blog_capture_band_after_section`
 *   (an `<h2>` count). That function NO LONGER EXISTS — the anchor is now a
 *   paragraph count, and the old filter name was removed rather than left
 *   returning a value nothing reads. This assertion follows the rename.
 */
/*
 * ⚠⚠ §2.1 CHANGED AGAIN AT 1.19.341 (`CYCLE171-LD-341` item 4): DEFAULT 2 -> 5.
 *    Founder, 2026-08-31, having seen the result on the Adams post: the band at
 *    paragraph 2 splits the opening argument, and the article's opening must
 *    read uninterrupted. `CYCLE171-MKT-2` found the same thing independently.
 *    This MOVES an ask; it does not add one — the two-asks-one-bridge doctrine
 *    (carrier 110/119) is unchanged and §2.6 still guards the band's shape.
 */
bhp_c169_ok( '§2.1 the paragraph tunable exists and defaults to 5', function_exists( 'bhp_blog_capture_band_after_paragraph' ) && 5 === bhp_blog_capture_band_after_paragraph() );
bhp_c169_ok(
	'§2.1b ⛔ the RETIRED heading tunable is genuinely gone, not left as a no-op',
	! function_exists( 'bhp_blog_capture_band_after_section' )
);

/*
 * ⚠ §2.2 CHANGED AT 1.19.322 — SPEC CHANGE, not a weakening. It asserted
 *   `$band_off === $h2s[2]`, i.e. the heading that opened section 3. The
 *   founder's round-2 instruction moves the ask to "right after the first
 *   paragraph or second paragraph", so the anchor is now the byte just past the
 *   second top-level `</p>`. Computed here from the FIXTURE rather than from the
 *   implementation's own helper, so the assertion cannot pass by agreeing with
 *   a bug in the code it is testing.
 */
/*
 * ⚠ §2.2 / §2.2b FOLLOW THE 1.19.341 SPEC CHANGE. The anchor is the byte just
 *   past the FIFTH top-level `</p>`, not the second. Computed here from the
 *   FIXTURE rather than from the implementation's own helper, so the assertion
 *   still cannot pass by agreeing with a bug in the code it is testing.
 */
$band_off = bhp_blog_capture_band_offset( $body );
preg_match_all( '/<\/p>/i', $body, $mp2, PREG_OFFSET_CAPTURE );
$fifth_p_end = (int) $mp2[0][4][1] + 4;

bhp_c169_ok( '§2.2 ⭐ it anchors immediately after the FIFTH top-level paragraph', (int) $band_off === $fifth_p_end, "band={$band_off} expected={$fifth_p_end}" );

/*
 * ⚠⚠ §2.2b IS THE ASSERTION THAT REVERSED, AND IT IS NOW A BAND RATHER THAN A
 *    CEILING. At 1.19.322 it read `< 15.0` and its label was "and that is EARLY
 *    in the article — the whole point of 1.19.322". Item 4 is the founder
 *    ruling that early was TOO early: the ask split the opening argument.
 *
 * ⛔ A BARE `< 35` WOULD STILL PASS AT PARAGRAPH ONE, which is the regression
 *    this release exists to prevent — so the FLOOR is the load-bearing half.
 *    The window says: past the opening, still nowhere near the end.
 */
$band_pct = bhp_c169_text_pct( substr( $body, 0, $band_off ) . '<!--BAND-->' . substr( $body, $band_off ), '<!--BAND-->' );
bhp_c169_ok(
	'§2.2b ⭐⭐ the opening now reads UNINTERRUPTED — the band clears the first tenth of the article (item 4) and still sits well inside the first third',
	$band_pct > 10.0 && $band_pct < 35.0,
	$band_pct . '%'
);

/*
 * ⚠ §2.3 REPLACED AT 1.19.322. It asserted that a post with too few HEADINGS got
 *   no band. That refusal is gone by design — a heading-less post is now a
 *   perfectly good host, because the anchor no longer counts headings. Deleting
 *   the assertion outright would have left the refusal path uncovered, so it is
 *   REPLACED by the refusal that still exists: no clean top-level paragraph.
 */
bhp_c169_ok(
	'§2.3 ⛔ it refuses an article with no clean top-level paragraph at all',
	null === bhp_blog_capture_band_offset( '<ul><li><p>buried</p></li><li><p>also buried</p></li></ul>' )
);
bhp_c169_ok( '§2.3b ⛔ and it refuses content with no paragraph markup whatsoever', null === bhp_blog_capture_band_offset( '<h2>Only a heading</h2><div>loose text</div>' ) );

/*
 * ⚠ §2.4 REPLACED AT 1.19.322. It asserted that `<p>a</p><p>b</p>` got NO band
 *   (too few headings). That fixture now DOES clear the paragraph rule — and is
 *   still refused, but for a different and better reason: the max-ratio guard,
 *   because paragraph two is the whole article and the end-of-post capture sits
 *   directly under it. The assertion now names the guard it is actually testing.
 */
bhp_c169_ok( '§2.4 ⛔ the max-ratio guard stands the band down on a post that is over before it starts', null === bhp_blog_capture_band_offset( '<p>a</p><p>b</p>' ) );
bhp_c169_ok( '§2.4b the max-ratio tunable is still honoured', 0.85 === min( 0.95, max( 0.1, (float) apply_filters( 'bhp_blog_capture_band_max_ratio', 0.85 ) ) ) );

/* ── ⭐ NEW AT 1.19.322 — the buried-paragraph fallback, which is the half of
      the new rule a naive implementation gets wrong. ───────────────────────── */
$buried = '<p>A clean opening paragraph, long enough to be measured properly.</p>'
	. '<blockquote><p>A pulled quote, which is NOT a top-level paragraph.</p></blockquote>'
	. '<p>The paragraph after the quote, also of a realistic length.</p>'
	. '<h2>Onward</h2><p>Body one, written at a realistic editorial length.</p>'
	. '<p>Body two, written at a realistic editorial length.</p>'
	. '<p>Body three, written at a realistic editorial length.</p>';
$buried_off = bhp_blog_capture_band_offset( $buried );
$first_p_end = strpos( $buried, '</p>' ) + 4;

/*
 * ⚠ CORRECTED IN THE SAME SITTING IT WAS WRITTEN, AND THE ERROR WAS MINE, NOT
 *   THE BUILD'S. This first asserted that the fixture held THREE clean
 *   top-level paragraphs and FAILED on the first run against 1.19.322. I
 *   miscounted the fixture when writing it: it holds SIX paragraphs, of which
 *   exactly one — the pull-quote — is buried, leaving FIVE clean.
 *
 * ⛔ THE BUILD WAS VERIFIED BEFORE THE ASSERTION WAS TOUCHED, rather than the
 *   number being nudged until it went green: `bhp_blog_capture_band_paragraphs()`
 *   was run directly on staging and printed `#2 end=140 top=no` with every other
 *   paragraph `top=YES`, and `bhp_blog_capture_band_offset()` returned 70, which
 *   is exactly the end of the first clean paragraph. That is the specified
 *   fallback behaving correctly, and §2.4d asserts it independently.
 *
 * ⭐ The assertion is now SHAPE-BASED as well as count-based, which is what it
 *    was always trying to say: it pins WHICH paragraph is buried, so a future
 *    walker that miscounted in a compensating direction could not pass it.
 */
$buried_ps = bhp_blog_capture_band_paragraphs( $buried );
bhp_c169_ok(
	'§2.4c ⭐ the walker sees the blockquote paragraph — and only it — as BURIED',
	6 === count( $buried_ps )
		&& false === $buried_ps[1]['top']
		&& 5 === count( array_filter( $buried_ps, function ( $p ) { return ! empty( $p['top'] ); } ) ),
	count( $buried_ps ) . ' paragraphs'
);
/*
 * ⚠⚠ §2.4d REVERSED DIRECTION AT 1.19.341, AND THIS IS THE SINGLE MOST
 *    IMPORTANT ASSERTION IN ITEM 4 — the half a version-bump-only change would
 *    have got silently wrong.
 *
 * ⛔ WHAT IT USED TO ASSERT, PRESERVED IN WORDS: *"when paragraph two is inside
 *    a blockquote, it falls back to after the FIRST clean one — earlier, never
 *    later"*, i.e. a BACKWARD search to the top of the article. That was right
 *    for a target of 2, where "earlier" meant paragraph one and the release
 *    wanted early.
 *
 * ⛔ AT A TARGET OF 5 THE SAME RULE IS THE DEFECT. Any article whose fifth
 *    paragraph sits in a list — routine in this blog's listicles — would have
 *    dumped the ask after paragraph ONE, which is exactly what the founder
 *    ruled against, reached by a path nobody would think to look at. So step 2
 *    now falls FORWARD.
 *
 * ⭐ The `$buried` fixture no longer exercises the fallback at all (its fifth
 *    paragraph is clean, so step 1 wins), so it is asserted for what it now
 *    proves, and a PURPOSE-BUILT fixture below exercises both fallback limbs.
 */
$buried_fifth_end = (int) ( strpos( $buried, 'Body two, written at a realistic editorial length.</p>' ) + strlen( 'Body two, written at a realistic editorial length.</p>' ) );
bhp_c169_ok(
	'§2.4d ⭐ with a clean fifth paragraph, step 1 wins and the blockquote earlier in the article is simply skipped over',
	(int) $buried_off === $buried_fifth_end,
	"got={$buried_off} expected={$buried_fifth_end}"
);

/* ── ⭐⭐ NEW AT 1.19.341 — THE FORWARD FALLBACK, both limbs. ─────────────── */

/* Four clean paragraphs, then the FIFTH buried in a list, then two more clean.
   The band must land after paragraph SIX (the next clean one going forward),
   never after paragraph one. */
$fwd = '<p>Opening paragraph, written at a realistic editorial length for the measurement.</p>'
	. '<p>Second paragraph, written at a realistic editorial length for the measurement.</p>'
	. '<p>Third paragraph, written at a realistic editorial length for the measurement.</p>'
	. '<p>Fourth paragraph, written at a realistic editorial length for the measurement.</p>'
	. '<ul><li><p>Fifth paragraph, buried inside a list exactly like a listicle item.</p></li></ul>'
	. '<p>Sixth paragraph, clean and top level, written at a realistic length.</p>'
	. '<p>Seventh paragraph, clean and top level, written at a realistic length.</p>'
	. '<p>Eighth paragraph, clean and top level, written at a realistic length.</p>'
	. '<p>Ninth paragraph, clean and top level, written at a realistic length.</p>';
$fwd_off       = bhp_blog_capture_band_offset( $fwd );
$fwd_sixth_end = (int) ( strpos( $fwd, 'Sixth paragraph, clean and top level, written at a realistic length.</p>' ) + strlen( 'Sixth paragraph, clean and top level, written at a realistic length.</p>' ) );
$fwd_first_end = (int) ( strpos( $fwd, '</p>' ) + 4 );

bhp_c169_ok(
	'§2.4d-1 ⭐⭐ FORWARD FALLBACK: a buried fifth paragraph sends the band to the SIXTH, the next clean one going forward',
	(int) $fwd_off === $fwd_sixth_end,
	"got={$fwd_off} expected={$fwd_sixth_end}"
);
bhp_c169_ok(
	'§2.4d-2 ⛔⛔ AND EMPHATICALLY NOT BACK TO PARAGRAPH ONE — the pre-1.19.341 backward fallback would have landed here, splitting the opening the founder ruled must read uninterrupted',
	(int) $fwd_off !== $fwd_first_end,
	"got={$fwd_off} para1={$fwd_first_end}"
);

/* The last-resort limb: a SHORT article can never reach paragraph five, so it
   looks backward to the LAST clean paragraph rather than refusing outright and
   silently dropping the mid-post ask from every short post on the blog. Padded
   with a long tail so the max-ratio guard does not stand it down first. */
$short = '<p>First paragraph of a short post, written at a realistic editorial length.</p>'
	. '<p>Second paragraph of a short post, written at a realistic editorial length.</p>'
	. '<p>Third paragraph of a short post, written at a realistic editorial length.</p>'
	. '<figure><p>' . str_repeat( 'A long trailing caption that carries most of the visible text. ', 40 ) . '</p></figure>';
$short_off      = bhp_blog_capture_band_offset( $short );
$short_third_end = (int) ( strpos( $short, 'Third paragraph of a short post, written at a realistic editorial length.</p>' ) + strlen( 'Third paragraph of a short post, written at a realistic editorial length.</p>' ) );
bhp_c169_ok(
	'§2.4d-3 ⭐ LAST RESORT: a post too short to reach paragraph five anchors on its LAST clean paragraph, as deep as the article allows',
	(int) $short_off === $short_third_end,
	"got={$short_off} expected={$short_third_end}"
);
bhp_c169_ok( '§2.4e ⭐ a list buries paragraphs the same way a blockquote does', 1 === count( array_filter( bhp_blog_capture_band_paragraphs( '<p>top</p><ul><li><p>in a list</p></li></ul>' ), function ( $p ) { return ! empty( $p['top'] ); } ) ) );
bhp_c169_ok( '§2.4f ⭐ and so does a figure, which is what a WordPress embed renders as', 1 === count( array_filter( bhp_blog_capture_band_paragraphs( '<p>top</p><figure class="wp-block-embed"><p>caption-ish</p></figure>' ), function ( $p ) { return ! empty( $p['top'] ); } ) ) );
bhp_c169_ok(
	'§2.4g ⛔ THE ALTERNATION TRAP: <picture> and <pre> are not parsed as a <p>',
	2 === count( bhp_blog_capture_band_paragraphs( '<p>one</p><picture><source></picture><pre>code</pre><p>two</p>' ) )
);
/* The tunable must actually tune, or it is decoration — the same test §1.7
   applies to the rail's ratio. Set it to 1 and the anchor must move UP to the
   first paragraph's end; then it must return to paragraph two when removed. */
add_filter( 'bhp_blog_capture_band_after_paragraph', 'bhp_c169_para_one' );
function bhp_c169_para_one() {
	return 1; }
$para1_off = bhp_blog_capture_band_offset( $body );
remove_filter( 'bhp_blog_capture_band_after_paragraph', 'bhp_c169_para_one' );

bhp_c169_ok( '§2.4h ⭐ the paragraph tunable genuinely moves the band', (int) $para1_off === (int) ( strpos( $body, '</p>' ) + 4 ), "1 -> {$para1_off}" );
bhp_c169_ok( '§2.4i ⭐ and it moved UP, never down', (int) $para1_off < (int) $band_off, "p1={$para1_off} p5={$band_off}" );
bhp_c169_ok( '§2.4j the filter is removed again and the 1.19.341 default is restored', 5 === bhp_blog_capture_band_after_paragraph() && (int) bhp_blog_capture_band_offset( $body ) === (int) $band_off );

$band = bhp_c169_render( 'template-parts/acquisition/post-capture-band' );
bhp_c169_ok( '§2.5 the band renders', '' !== $band );
bhp_c169_ok(
	'§2.6 ⛔⛔ THE LOAD-BEARING CONSTRAINT: the band emits NO <h2>, so the rail\'s heading arithmetic is undisturbed',
	0 === preg_match( '/<h2[\s>]/i', $band )
);
bhp_c169_ok( '§2.7 it has a real accessible name via aria-labelledby', false !== strpos( $band, 'aria-labelledby' ) && false !== strpos( $band, 'bhp-capture-band__line' ) );
bhp_c169_ok( '§2.8a exactly one email field', 1 === preg_match_all( '/type="email"/', $band ) );
/*
 * ⚠ CORRECTED IN THE SAME SITTING IT WAS WRITTEN. This assertion first read
 *   "and it is the only text input", which FAILED on the first run. The cause
 *   was the assertion, not the build: `signup-form.php` emits a HONEYPOT
 *   (`name="bhp_website"`, `tabindex="-1"`, `autocomplete="off"`), which is a
 *   `type="text"` input by design and is the shared form's spam trap. Asserting
 *   it away would have pushed a future build toward removing real spam
 *   protection. The assertion now pins the honeypot IN PLACE instead.
 */
bhp_c169_ok(
	'§2.8b the only type="text" input is the shared form\'s honeypot, and it must stay',
	1 === preg_match_all( '/type="text"/', $band )
		&& false !== strpos( $band, 'name="bhp_website"' )
		&& false !== strpos( $band, 'tabindex="-1"' )
);
bhp_c169_ok( '§2.9 no segment selector and no name field — one row, one ask', 0 === preg_match_all( '/<select/i', $band ) );

/* ── The reuse requirement: the SAME pipe as the footer capture. ─────────── */
bhp_c169_ok( '§2.10 ⭐ it posts to the EXISTING signup mechanism', false !== strpos( $band, 'name="action" value="bhp_mailchimp_signup"' ) );
bhp_c169_ok( '§2.11 the nonce is present', false !== strpos( $band, 'name="bhp_signup_nonce"' ) );
bhp_c169_ok( '§2.12 the lead magnet is the Adventure Kit, same as the footer capture', false !== strpos( $band, 'value="reluctant_reader_adventure_kit"' ) );
bhp_c169_ok(
	'§2.13 ⛔ it mints NO new Mailchimp tag: same context as the other blog captures',
	false !== strpos( $band, 'value="' . bhp_blog_capture_context() . '"' )
);
$footer_action = function_exists( 'bhp_get_signup_form_action' ) ? bhp_get_signup_form_action( '', 'parents_families', 'footer_capture' ) : null;
$band_action   = function_exists( 'bhp_get_signup_form_action' ) ? bhp_get_signup_form_action( '', 'parents_families', bhp_blog_capture_context() ) : null;
bhp_c169_ok( '§2.14 ⭐ the band and the footer capture resolve the SAME form action', null !== $band_action && $footer_action === $band_action, (string) $band_action );
bhp_c169_ok(
	'§2.15 ⛔ NO success_redirect_key, so lead_signup_success still fires inline',
	false === strpos( $band, 'name="bhp_success_redirect_key"' )
);
bhp_c169_ok( '§2.16 ⛔ the band contains NO Amazon URL of any kind', 0 === bhp_c169_affiliate_count( $band ) );

/* ── The copy gate. The line is DRAFT; the rails are not. ────────────────── */
$band_text = wp_strip_all_tags( $band );
bhp_c169_ok( '§2.17 the draft line is in exactly one filterable place', function_exists( 'bhp_blog_capture_band_line' ) && false !== strpos( $band, esc_html( bhp_blog_capture_band_line() ) ) );
add_filter( 'bhp_blog_capture_band_line', 'bhp_c169_line' );
function bhp_c169_line() {
	return 'A one line change'; }
bhp_c169_ok( '§2.18 ⭐ the copy really is a one-line change', 'A one line change' === bhp_blog_capture_band_line() );
remove_filter( 'bhp_blog_capture_band_line', 'bhp_c169_line' );
bhp_c169_ok( '§2.19 ⛔ §9.1 voice: no company "we", "us" or "our"', 0 === preg_match( '/\b(we|us|our)\b/i', $band_text ) );
bhp_c169_ok( '§2.20 ⛔ no em dash in visible copy', false === strpos( $band_text, "\xE2\x80\x94" ) );
bhp_c169_ok( '§2.21 ⛔ never "5 to 9" or "5-9"', false === strpos( $band_text, '5-9' ) && false === strpos( $band_text, '5–9' ) && false === stripos( $band_text, '5 to 9' ) );
bhp_c169_ok(
	'§2.22 ⛔ no outcome claim',
	false === stripos( $band_text, 'will love' ) && false === stripos( $band_text, 'proven' ) && false === stripos( $band_text, 'studies show' )
);
bhp_c169_ok(
	'§2.23 ⭐ the button carries the sitewide offer string (item 290 consistency)',
	false !== strpos( $band, 'Send me the chapter' )
);

/* ── ⭐⭐ NEW AT 1.19.322 — THE COPY IS STANDARDIZED, AND ROUND 1's B1 CLOSES.
      1.19.321 shipped a THIRTEENTH wording of one offer and flagged it. The
      founder ruled: use the item-290 strings. These assertions are what stop a
      fourteenth from appearing. ───────────────────────────────────────────── */
$offer_headline = 'FREE Chapter for Reluctant Readers';

bhp_c169_ok( '§2.24 ⭐⭐ the LINE is now the standardized item-290 headline', false !== strpos( $band_text, $offer_headline ), $offer_headline );
bhp_c169_ok(
	'§2.25 ⭐ byte-identical to the other capture surfaces, not merely similar',
	false !== strpos( bhp_c169_file( 'template-parts/acquisition/post-end-capture.php' ), "'" . $offer_headline . "'" )
		&& false !== strpos( bhp_c169_file( 'template-parts/acquisition/footer-capture.php' ), "'" . $offer_headline . "'" )
		&& false !== strpos( bhp_c169_file( 'template-parts/acquisition/post-mid-capture.php' ), "'" . $offer_headline . "'" )
);
bhp_c169_ok( '§2.26 ⭐ and it is the same string the item-290 copy suite pins as $offer_headline', false !== strpos( bhp_c169_file( 'tests/test-cycle167-capture-copy.php' ), "\$offer_headline = '" . $offer_headline . "'" ) );
/*
 * ⛔ AN EXPLICIT CONSTRAINT OF THE ROUND-2 BRIEF: *"the word 'test' must appear
 *    nowhere"*. Asserted on the RENDERED, TAG-STRIPPED band — the only place the
 *    claim is about — and with a WORD BOUNDARY, so a future "latest" or
 *    "greatest" is not reported as a violation it is not.
 */
bhp_c169_ok( '§2.27 ⛔ the word "test" appears nowhere in the rendered band', 0 === preg_match( '/\btest/i', $band_text ) );
bhp_c169_ok( '§2.28 ⛔ and the superseded draft line is gone entirely', false === stripos( $band_text, 'kiddo' ) );
/* The retired offer names from CYCLE167. A partial revert must leave a FAILING
   test rather than a site quietly offering two things again. */
foreach ( array( 'Send me the Kit', 'Send me the free kit', 'Try a chapter tonight', '20 Minute Reluctant Reader Kit' ) as $bhp_retired ) {
	bhp_c169_ok( '§2.29 ⛔ retired offer name absent: "' . $bhp_retired . '"', false === strpos( $band_text, $bhp_retired ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 — ORDER, INSERT-ONLY, AND §26.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§3 — injection order and the §26 insert-only proof' );

bhp_c169_ok(
	'§3.1 ⭐ the band runs at 11, BEFORE the rail at 12',
	false !== strpos( $blog_src, "add_filter( 'the_content', 'bhp_blog_inject_capture_band', 11 );" )
		&& false !== strpos( $blog_src, "add_filter( 'the_content', 'bhp_blog_inject_rail', 12 );" )
);
bhp_c169_ok(
	'§3.2 ⛔⛔ the band injector performs NO replacement on post content',
	false === strpos( $blog_src, 'preg_replace( $content' )
		&& false !== strpos( $blog_src, 'return substr( $content, 0, $offset ) . $band . substr( $content, $offset );' )
);

/*
 * The insert-only proof, simulated on content carrying both anchor shapes.
 *
 * ⚠⚠ FIXTURE LENGTHENED AT 1.19.341 (`CYCLE171-LD-341` item 4), AND THE REASON
 *    MATTERS MORE THAN THE EDIT. The original fixture held exactly FIVE
 *    top-level paragraphs, ending on the last one. When the band's anchor moved
 *    from paragraph 2 to paragraph 5, that anchor landed at 100% of the visible
 *    text, the max-ratio guard CORRECTLY stood the band down, and
 *    `bhp_blog_capture_band_offset()` returned `null` — so §3.3d was no longer
 *    splicing anything and the §26 insert-only proof had quietly stopped
 *    proving its own subject.
 *
 * ⛔ THE ASSERTION WAS NOT RELAXED TO ACCOMMODATE THAT. §26 is the rule that
 *    protects live affiliate revenue on real posts; a guard that passes because
 *    it tested nothing is worse than a failing one, because it reports success.
 *    The FIXTURE is extended so the band genuinely renders again, and the
 *    assertion below is unchanged from the form it has always had.
 *
 * ⭐ BOTH AFFILIATE ANCHORS ARE UNTOUCHED, including the tagged one §3.3c reads
 *    byte-for-byte — the added paragraphs carry no links, so `$before_n` is
 *    still 2 and the shape §3.3a pins is preserved.
 */
$aff = '<p>Intro one.</p><p>Intro two with <a href="https://amzn.to/3PFKexe">a link</a>.</p>'
	. '<h2>One</h2><p><a href="https://www.amazon.com/dp/0375813659?tag=bravehearts0e-20">tagged</a></p>'
	. '<h2>Two</h2><p>More.</p><h2>Three</h2><p>End of the original fixture.</p>'
	. '<h2>Four</h2><p>A sixth paragraph, added at 1.19.341 so the paragraph-five anchor is not the last thing in the article.</p>'
	. '<p>A seventh paragraph, likewise, giving the max-ratio guard room to let the band render.</p>'
	. '<p>An eighth paragraph, carrying no link of its own.</p>'
	. '<p>A ninth and final paragraph, closing the sample out.</p>';
$before_n = bhp_c169_affiliate_count( $aff );
$off3     = bhp_blog_capture_band_offset( $aff );
$spliced  = substr( $aff, 0, $off3 ) . '<aside class="bhp-capture-band">band</aside>' . substr( $aff, $off3 );
$after_n  = bhp_c169_affiliate_count( $spliced );

bhp_c169_ok( '§3.3a the sample carries 2 affiliate anchors before', 2 === $before_n, (string) $before_n );
bhp_c169_ok( '§3.3b ⛔⛔ §26 COUNT-DECREASE: after >= before', $after_n >= $before_n, "before={$before_n} after={$after_n}" );
bhp_c169_ok( '§3.3c ⛔ the tracking code survives byte-for-byte — the tag IS the revenue', false !== strpos( $spliced, 'tag=bravehearts0e-20' ) );
bhp_c169_ok(
	'§3.3d ⛔ every original byte survives in order',
	substr( $spliced, 0, $off3 ) === substr( $aff, 0, $off3 )
		&& substr( $spliced, -1 * ( strlen( $aff ) - $off3 ) ) === substr( $aff, $off3 )
);

/* Same proof for the relocated rail, whose anchor moved this release. */
$off4     = bhp_blog_rail_offset( $aff );
$spliced2 = substr( $aff, 0, $off4 ) . '<aside class="bhp-book-rail">rail</aside>' . substr( $aff, $off4 );
bhp_c169_ok( '§3.4a ⛔ the relocated rail is still insert-only', bhp_c169_affiliate_count( $spliced2 ) >= $before_n );
bhp_c169_ok( '§3.4b ⛔ and its tracking code survives too', false !== strpos( $spliced2, 'tag=bravehearts0e-20' ) );

/*
 * §3.5 — THE COLLISION GUARD. Regression cover for a defect a BROWSER found and
 * this suite did not: on staging post 28 the band anchored at 70.6% and the rail
 * at 72.3%, stacking an email ask on a buy control. Reproduced here with a
 * lopsided article whose second section runs to two thirds of the body.
 */
bhp_c169_ok( '§3.5a the gap tunable exists and defaults to 0.08', function_exists( 'bhp_blog_rail_min_gap_ratio' ) && 0.08 === bhp_blog_rail_min_gap_ratio() );

/*
 * ⚠⚠ CASE A REBUILT AT 1.19.322. The old fixture was a LOPSIDED article whose
 *    SECTION 2 ran to two thirds of the body, and it proved that the max-ratio
 *    guard stood the band down. That fixture proves nothing any more: with a
 *    paragraph anchor, a long section 2 is irrelevant — the band lands in the
 *    first tenth regardless of how the headings fall. ⛔ THE GUARD STILL EXISTS
 *    AND STILL MUST BE COVERED, so the fixture is rebuilt on the shape where the
 *    guard now bites: a SHORT post, where paragraph two is already most of the
 *    article and the end-of-post capture sits directly beneath it.
 *
 *    ⭐ The old fixture is NOT discarded — it is kept below as CASE A2, where it
 *       now proves the OPPOSITE and more valuable thing: that round 1's finding
 *       B3 (a 41-point spread in band position between two real posts, caused by
 *       heading shape) is structurally fixed.
 */
$short = '<p>A single opening paragraph of ordinary editorial length for this blog.</p>'
	. '<p>A second paragraph, after which there is almost nothing left to read.</p>'
	. '<p>Tiny.</p>';

bhp_c169_ok( '§3.5b ⛔ CASE A: on a post that is over before it starts, the max-ratio guard stands the band down', null === bhp_blog_capture_band_offset( $short ) );

/* CASE A2 — the old lopsided fixture, repurposed. */
$lop = '<p>Intro one, long enough to matter in a text measurement.</p><p>Intro two, likewise.</p><h2>Short first</h2><p>One short paragraph.</p><h2>The long list</h2>';
for ( $k = 1; $k <= 14; $k++ ) {
	$lop .= "<p>Book {$k}, described at the kind of length a roundup entry actually runs to on this blog.</p>";
}
$lop .= '<h2>Third</h2><p>After the list.</p><h2>Fourth</h2><p>Nearly the end.</p><p>The end.</p>';

$lop_band = bhp_blog_capture_band_offset( $lop );
$lop_bpct = ( null === $lop_band ) ? -1.0 : round( 100 * bhp_blog_visible_text_length( $lop, $lop_band ) / strlen( wp_strip_all_tags( $lop ) ), 1 );
/*
 * ⚠ §3.5b2's WINDOW FOLLOWS ITEM 4 (1.19.341): was `< 15.0`. Round 1 finding B3
 *   is still what this asserts — the lopsided roundup must not push the band to
 *   70.6% — and that is still structurally fixed by counting paragraphs instead
 *   of headings. What changed is that "early" is no longer the target: the
 *   founder ruled on 2026-08-31 that the opening must read uninterrupted. The
 *   FLOOR is the new half and the CEILING is the original protection.
 */
bhp_c169_ok(
	'§3.5b2 ⭐⭐ CASE A2: ROUND 1 FINDING B3 STAYS FIXED — the lopsided roundup that pushed the band to 70.6% now anchors in the same window as every other post, clear of the opening',
	null !== $lop_band && $lop_bpct > 10.0 && $lop_bpct < 35.0,
	"{$lop_bpct}%"
);

/*
 * ⚠ §3.5c BAND WIDENED AT 1.19.322: 60–85% -> 60–90%. The rail is measured on
 *   content that now contains an EARLY band rather than no band, so the
 *   denominator and the gap floor both shift slightly. The lower bound — the
 *   one that actually protects the founder's "not at the top" requirement — is
 *   unchanged at 60%.
 */
$lop_rail = bhp_blog_rail_offset( $lop );
$lop_pct  = ( null === $lop_rail ) ? -1.0 : round( 100 * bhp_blog_visible_text_length( $lop, $lop_rail ) / strlen( wp_strip_all_tags( $lop ) ), 1 );
bhp_c169_ok( '§3.5c ⭐ CASE A2: the rail keeps its ordinary 3/4 position regardless', $lop_pct >= 60.0 && $lop_pct <= 90.0, "{$lop_pct}%" );

/* CASE B — a band that DOES render, mid-article. The rail must be pushed clear
   of it, downward, by at least the minimum gap. This is the regression cover
   for the defect a BROWSER found on staging post 28: band 70.6%, rail 72.3%. */
$mid = '<p>Intro one, long enough to matter in a text measurement.</p><p>Intro two, likewise long.</p><h2>One</h2>';
for ( $k = 1; $k <= 2; $k++ ) {
	$mid .= "<p>Section one paragraph {$k}, at a realistic editorial length for this blog.</p>";
}
$mid .= '<h2>Two</h2>';
for ( $k = 1; $k <= 6; $k++ ) {
	$mid .= "<p>Section two paragraph {$k}, at a realistic editorial length for this blog.</p>";
}
foreach ( array( 'Three', 'Four', 'Five' ) as $h ) {
	$mid .= "<h2>{$h}</h2>";
	for ( $k = 1; $k <= 2; $k++ ) {
		$mid .= "<p>Section {$h} paragraph {$k}, at a realistic editorial length for this blog.</p>";
	}
}

$band_at   = bhp_blog_capture_band_offset( $mid );
$mid_band  = ( null === $band_at ) ? $mid : substr( $mid, 0, $band_at ) . '<aside class="bhp-capture-band">band</aside>' . substr( $mid, $band_at );
$mid_rail  = bhp_blog_rail_offset( $mid_band );
$mid_total = strlen( wp_strip_all_tags( $mid_band ) );
$b_pct     = round( 100 * bhp_blog_visible_text_length( $mid_band, (int) strpos( $mid_band, 'bhp-capture-band' ) ) / $mid_total, 1 );
$r_pct     = ( null === $mid_rail ) ? 100.0 : round( 100 * bhp_blog_visible_text_length( $mid_band, $mid_rail ) / $mid_total, 1 );

/*
 * ⚠⚠ §3.5d HAS NOW MOVED TWICE, AND THE HISTORY IS THE POINT. It is the one
 *    assertion in this suite that looks like a weakening whichever direction it
 *    goes, so each move is recorded with the ruling that caused it:
 *
 *      1.19.321  30–85%  — "an ask somewhere in the middle"
 *      1.19.322  0–25%   — founder: the ask belongs "much high on the page"
 *      1.19.341  10–40%  — founder, 2026-08-31, having SEEN the result on the
 *                          Adams post: paragraph 2 splits the opening argument;
 *                          the article's opening must read uninterrupted
 *
 * ⛔ THE FLOOR IS WHAT ITEM 4 ADDED, AND IT IS THE HALF THAT NOW DOES WORK. A
 *    window of `0–40%` would still pass with the band after paragraph one,
 *    which is the exact placement the founder ruled against. `>= 0.0` could
 *    never fail; `> 10.0` can.
 *
 * ⭐ The bound that does the real protective work against the ORIGINAL defect
 *    (band and rail stacked) is §3.5e's gap requirement, and it is UNCHANGED at
 *    8 points across all three moves.
 */
bhp_c169_ok( '§3.5d ⭐ CASE B: the band clears the opening and still renders well inside the first half', null !== $band_at && $b_pct > 10.0 && $b_pct < 40.0, "{$b_pct}%" );
bhp_c169_ok(
	'§3.5e ⛔⛔ REGRESSION: the rail is never stacked on the band (>= the minimum gap)',
	( $r_pct - $b_pct ) >= 8.0,
	"band={$b_pct}% rail={$r_pct}% gap=" . round( $r_pct - $b_pct, 1 ) . '%'
);
bhp_c169_ok( '§3.5f ⭐ and the rail yielded DOWNWARD, never upward', null === $mid_rail || $mid_rail > (int) $band_at );
bhp_c169_ok(
	'§3.5g ⛔ REGRESSION: the short-post paragraph fallback can no longer put the rail near the top',
	$r_pct > 40.0,
	"{$r_pct}%"
);

bhp_c169_ok( '§3.6 the shortcode fallback is registered', shortcode_exists( 'bhp_capture_band' ) );
bhp_c169_ok(
	'§3.7 ⭐ the injector yields to an editor-placed marker',
	false !== strpos( $blog_src, "if ( false !== strpos( \$content, 'bhp-capture-band' ) ) {" )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 — LIMB 4: THE FOOTER CAPTURE IS UNTOUCHED.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§4 LIMB 4 — the footer capture is not changed by this release' );

$footer_block = bhp_c169_render( 'template-parts/acquisition/footer-capture' );
bhp_c169_ok( '§4.1 the footer capture still renders', '' !== $footer_block );
bhp_c169_ok( '§4.2 it still carries its own context, not the blog one', false !== strpos( $footer_block, 'value="footer_capture"' ) );
bhp_c169_ok( '§4.3 it still carries its segment selector (the big version)', 1 === preg_match_all( '/<select/i', $footer_block ) );
bhp_c169_ok( '§4.4 it still redirects to its thank-you page', false !== strpos( $footer_block, 'name="bhp_success_redirect_key"' ) );
bhp_c169_ok(
	'§4.5 ⚠ ITS BLOG-POST SUPPRESSION IS UNCHANGED — 1.19.269, founder subtraction item 1. '
		. 'This release did NOT re-enable it on posts, and did not disable it anywhere either.',
	1 === preg_match( '/function bhp_should_show_footer_capture\(\).*?is_singular\(\s*\'post\'\s*\)/s', bhp_c169_file( 'functions.php' ) )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 — LIMB 5: IN-BODY IMAGES.  [source] — CSS SHAPE ONLY.
 *
 * ⛔ THIS SECTION CANNOT MEASURE A RENDERED IMAGE. It asserts that the rule
 *    exists, is scoped to the article body, and does not reach the featured
 *    image. The actual rendered width is measured in a browser at a stated
 *    `window.innerWidth` and reported in the handoff, never inferred from here.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§5 LIMB 5 — in-body images [source]' );

/*
 * ⚠⚠ §5.1 REPLACED AT 1.19.323 — SPEC CHANGE, AND IT IS THE ONE THAT WOULD LOOK
 *    LIKE A WEAKENING IF READ ALONE, SO READ THIS FIRST.
 *
 *    It asserted `max-width: 60%;`. That cap is GONE ON INSTRUCTION: the
 *    round-3 brief specifies *"max-width 100% of the text column, max-height
 *    ~45vh"*. The 60% width ceiling was never what bounded a tall graphic —
 *    MEASURED IN A BROWSER at 1440×900 on post 5089 the in-body image rendered
 *    312 × 473 px, which is 53% of the viewport HEIGHT while obeying the 60%
 *    WIDTH rule perfectly. ⛔ THE HEIGHT CAP IS THE REAL CONSTRAINT AND IT IS
 *    WHAT §5.1 NOW ASSERTS. Deleting the old assertion without a replacement
 *    would have left "images are bounded" untested; this replaces it with the
 *    bound that actually holds.
 */
bhp_c169_ok(
	'§5.1 ⭐⭐ [source] a HEIGHT cap exists and is the ~45vh the brief specifies, clamped into the 420-480px desktop band',
	false !== strpos( $css_src, '--bhp-inbody-max-h: clamp(200px, 45vh, 460px);' )
		&& false !== strpos( $css_src, 'max-height: var(--bhp-inbody-max-h);' )
);
bhp_c169_ok(
	'§5.1b ⭐ [source] and the width ceiling is now the full text column, not 60% — the deliberate round-3 spec change',
	1 === preg_match( '/\.single-post \.post-content :is\(img, picture, video, svg\) \{[^}]*max-width: 100%;/s', $css_src )
);
bhp_c169_ok(
	'§5.1c ⛔ [source] the retired 60% cap is genuinely GONE, not left as a dead rule that could still win a cascade',
	false === strpos( $css_src, 'max-width: 60%;' )
);
bhp_c169_ok(
	'§5.2 ⭐⭐ [source] MARKUP-PATH AGNOSTIC: the cap names the replaced elements themselves, so figure/srcset/picture/video/svg/Squarespace-div all inherit it without a per-block selector',
	false !== strpos( $css_src, '.single-post .post-content :is(img, picture, video, svg)' )
);
bhp_c169_ok(
	'§5.2b ⛔ [source] the height cap is NOT put on the <figure> — a figure is not a replaced element and a max-height on it would clip a caption instead of scaling the picture',
	1 === preg_match( '/\.single-post \.post-content :is\(figure, \.wp-block-image\) \{(?:(?!\}).)*\}/s', $css_src, $bhp_c169_figm )
		&& false === strpos( $bhp_c169_figm[0], 'max-height' )
);
bhp_c169_ok( '§5.3 [source] images are centred', false !== strpos( $css_src, 'margin-inline: auto;' ) );
/*
 * ⚠⚠ §5.3b WAS CHANGED IN THE SAME SITTING IT WAS WRITTEN, BECAUSE THE BUILD
 *    IT PINNED WAS CHANGED. It first asserted `width: auto;` +
 *    `object-fit: contain;`. Both were in the shipped rule and it was GREEN.
 *
 *    ⛔ THE PROBLEM WAS NOT THE ASSERTION, IT WAS THE EXPRESSION: an image that
 *    has not loaded yet has no intrinsic width, so under `width: auto` its box
 *    is 0 x 0 until the bytes arrive and the article reflows around every
 *    picture as it lands. The width/height attributes supply an aspect RATIO,
 *    not a width basis, so they cannot rescue it. The rule now reserves the box
 *    with `width: 100%` and fits the picture inside it with
 *    `object-fit: scale-down` — the SMALLER of `none` and `contain`, so it
 *    never crops and, unlike plain `contain`, never upscales either.
 *    ⛔ THE PROPERTY BEING ASSERTED IS UNCHANGED — aspect preserved, content
 *    never cropped. Only the expression that delivers it moved.
 *
 * ⚠⚠ AND A WITHDRAWN CLAIM, RECORDED RATHER THAN QUIETLY DELETED. An earlier
 *    draft of this comment said `width: auto` ALSO stopped `loading="lazy"`
 *    images loading at all, citing a staging observation at innerWidth 375.
 *    ⛔ WITHDRAWN. The observation was real — complete=false, naturalWidth=0 —
 *    but the CAUSE was the test harness, not the CSS: `document.hidden` was
 *    `true` for the whole session, and Chrome does not run lazy-image loading
 *    in a hidden document. The same image also failed to load under
 *    `width: 100%`. ⭐ A CHECK THAT DID NOT MEASURE WHAT IT CLAIMED TO MEASURE
 *    IS A FABRICATED CHECK, and correcting it is not optional. Whether a
 *    zero-size lazy image also fails to load is NOT VERIFIED and NOT CLAIMED.
 */
bhp_c169_ok(
	'§5.3b ⭐⭐ [source] ASPECT IS PRESERVED AND CONTENT IS NEVER CROPPED: object-fit is scale-down — the smaller of none and contain, so it never crops (cover) and never upscales (contain)',
	1 === preg_match( '/\.single-post \.post-content :is\(img, picture, video, svg\) \{(?:(?!\}).)*\}/s', $css_src, $bhp_c169_repl )
		&& false !== strpos( $bhp_c169_repl[0], 'height: auto;' )
		&& false !== strpos( $bhp_c169_repl[0], 'object-fit: scale-down;' )
		&& false === strpos( $bhp_c169_repl[0], 'object-fit: cover;' )
);
bhp_c169_ok(
	'§5.3c ⛔ REGRESSION GUARD: the rule must NOT say `width: auto` — an unloaded image then has a 0x0 box and the article reflows around every picture as it lands',
	1 === preg_match( '/\.single-post \.post-content :is\(img, picture, video, svg\) \{(?:(?!\}).)*\}/s', $css_src, $bhp_c169_wid )
		&& false === strpos( $bhp_c169_wid[0], 'width: auto;' )
		&& false !== strpos( $bhp_c169_wid[0], 'width: 100%;' )
);
/*
 * ⚠ CORRECTED IN THE SAME SITTING IT WAS WRITTEN. This first asserted that the
 *   string `.post-header__image` was ABSENT FROM THE WHOLE FILE, and FAILED on
 *   the first run. The cause was the assertion, not the build: blog-post.css has
 *   styled the field-note masthead image since 1.19.261, at a line 350 above
 *   anything this release added. The property actually worth pinning is that the
 *   new image cap never REACHES that element, so that is what is now asserted.
 */
bhp_c169_ok(
	'§5.4a ⛔ [source] no rule pairs .post-content with the masthead image — the featured image is outside the body and uncapped',
	0 === preg_match( '/^[^{\n]*\.post-content[^{\n]*\.post-header__image/m', $css_src )
);
/*
 * ⚠⚠ §5.4b REPLACED AT 1.19.322, AND ITS OLD LABEL WOULD NOW BE A FALSE CLAIM.
 *    It read: "the pre-existing masthead rule is still present, i.e. this
 *    release did NOT disturb featured-image handling". 1.19.322 disturbs
 *    featured-image handling deliberately and on instruction — the founder's
 *    round-2 complaint is that the image renders at 139% of the content box
 *    ("the pictures are still HUGE... its ostentatious"). An assertion whose
 *    LABEL asserts non-change would pass on the string while lying about the
 *    build. Replaced by §5.7–§5.12, which assert the NEW shape positively.
 */
bhp_c169_ok(
	'§5.4b [source] the masthead rule still exists as the single place featured-image sizing is decided',
	false !== strpos( $css_src, '.post-header--field-note .post-header__image {' )
);
/*
 * ⚠ §5.5 UPDATED AT 1.19.323 — the restore selector gained the same element
 *   list the cap gained. Same property asserted, wider selector.
 */
bhp_c169_ok(
	'§5.5 [source] the theme\'s own injected blocks are restored explicitly, INCLUDING the two new properties (max-height, object-fit) so a 45vh cap cannot reach artwork sized by its own slot',
	false !== strpos( $css_src, '.single-post .post-content .bhp-book-rail :is(img, picture, video, svg, figure)' )
		&& 1 === preg_match( '/\.single-post \.post-content \.bhp-capture-band :is\(img, picture, video, svg, figure\) \{(?:(?!\}).)*\}/s', $css_src, $bhp_c169_rest )
		&& false !== strpos( $bhp_c169_rest[0], 'max-height: none;' )
);
bhp_c169_ok(
	'§5.5b ⛔ [source] the book rail\'s cover pins its own width:100% — redundant today by accident of the load-safety fix, kept deliberately so a future retune of the shared rule cannot collapse a 176px composite',
	1 === preg_match( '/\.single-post \.post-content \.bhp-book-rail :is\(img, picture, svg\) \{\s*width: 100%;\s*\}/s', $css_src )
);
/*
 * ⚠⚠ §5.6 REPLACED AT 1.19.323, AND ITS OLD LABEL WOULD NOW BE A FALSE CLAIM.
 *    It read "the cap is released on narrow viewports" and passed by finding
 *    the string `@media (max-width: 640px)` — which still exists in this file
 *    for a completely unrelated reason (`--bhp-editorial-inset: 1.5rem`). So
 *    the OLD assertion would still be GREEN while asserting something that is
 *    no longer true: 1.19.321 released the 60% width cap below 640px, and there
 *    is no width cap left to release. A green test whose label lies is worse
 *    than a red one. ⭐ The property that replaces it is the one that actually
 *    protects a phone now: the cap is viewport-relative, so it scales down with
 *    the screen instead of holding one desktop number at both ends, and its
 *    200px floor stops a landscape phone shrinking an image below legibility.
 */
bhp_c169_ok(
	'§5.6 ⭐ [source] the cap is VIEWPORT-RELATIVE with a floor, so no media query is needed to make it proportionate on a phone',
	1 === preg_match( '/--bhp-inbody-max-h:\s*clamp\(\s*200px\s*,\s*45vh\s*,\s*460px\s*\)/', $css_src )
);
bhp_c169_ok(
	'§5.6b ⛔ [source] the tunable is declared in ONE place, on .post-content, so a ruling on the height stays a one-value change',
	1 === substr_count( $css_src, '--bhp-inbody-max-h: clamp(' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5b — ⭐⭐ NEW AT 1.19.322: THE FEATURED IMAGE.  [source] — CSS SHAPE ONLY.
 *
 * ⛔ THE SAME HONESTY LIMIT AS §5, RESTATED BECAUSE IT MATTERS MORE HERE. This
 *    section CANNOT measure a rendered image and does not claim to. The founder
 *    complained about a MEASUREMENT (139% of the content box), so the claim that
 *    answers him is a browser measurement at a stated `window.innerWidth`,
 *    reported in the deliverable. What follows asserts only that the rule which
 *    should produce that measurement exists and is shaped correctly.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§5b — the featured image [source]' );

/* The rule body, isolated so the assertions below cannot accidentally match a
   property belonging to some other selector elsewhere in the file. */
$feat = '';
if ( preg_match( '/\.post-header--field-note \.post-header__image \{(.*?)\}/s', $css_src, $mf ) ) {
	$feat = $mf[1];
}

bhp_c169_ok( '§5.7 [source] the featured-image rule is found and non-empty', '' !== $feat );
/*
 * ⚠⚠ §5.8 / §5.9 / §5.10 AMENDED AT 1.19.341 (`CYCLE171-LD-341` item 2). The
 *    founder reversed item 474 and asked for a SMALL heading picture. Un-hiding
 *    the image unchanged would have re-shipped the crop defect that produced
 *    item 474: measured on staging 2026-08-31, 23 of 37 published posts carry a
 *    683x1024 PORTRAIT poster with its headline baked into the artwork, and
 *    `object-fit: cover` in a 400px band shows the middle ~39% of it.
 *
 *    So the rule now caps HEIGHT and lets width follow the picture's aspect
 *    ratio. Three assertions move with it, and each keeps its original INTENT:
 *      §5.8  no breakout      -> the same token expression, on `max-width`
 *      §5.9  a small cap      -> 320px, was 400px
 *      §5.10 no squash        -> now also NO CROP: `contain`, was `cover`
 */
bhp_c169_ok(
	'§5.8 ⭐⭐ [source] NO BREAKOUT: the measure is the article measure MINUS the editorial inset, in tokens, never a hardcoded pixel value — on max-width since 1.19.341',
	false !== strpos( preg_replace( '/\s+/', ' ', $feat ), 'max-width: calc( min(100% - (2 * var(--gutter)), var(--container-content)) - (2 * var(--bhp-editorial-inset)) );' )
);
bhp_c169_ok(
	'§5.8b ⛔ [source] the measure half is the SAME expression .content-narrow uses in style.css',
	false !== strpos( bhp_c169_file( 'style.css' ), 'width: min(100% - (2 * var(--gutter)), var(--container-content));' )
);
bhp_c169_ok(
	'§5.8c ⭐⭐ [source] 1.19.341: width and height are AUTO, so the element hugs the picture — this is what keeps style.css\'s box-shadow off 690px of empty space beside a portrait poster',
	false !== strpos( $feat, 'width: auto;' ) && false !== strpos( $feat, 'height: auto;' )
);
bhp_c169_ok( '§5.9 ⭐ [source] it is capped at 320px tall on desktop — "small" is the founder\'s word', false !== strpos( $feat, '--bhp-featured-max-h: 320px;' ) && false !== strpos( $feat, 'max-height: var(--bhp-featured-max-h);' ) );
bhp_c169_ok( '§5.10 ⭐⭐ [source] the height is NEVER A CROP — 23 of 37 posts are portrait posters with baked-in headlines', false !== strpos( $feat, 'object-fit: contain;' ) && false !== strpos( $feat, 'object-position: center;' ) );
bhp_c169_ok(
	'§5.10b ⛔⛔ [source] AND `cover` MUST NOT COME BACK to this selector — it is the exact declaration that decapitated the posters and produced founder item 474',
	false === strpos( $feat, 'object-fit: cover;' )
);
bhp_c169_ok( '§5.11 ⭐ [source] it is centred under the title and meta', false !== strpos( $feat, 'margin-inline: auto;' ) && false !== strpos( $feat, 'display: block;' ) );
bhp_c169_ok(
	'§5.12 ⭐ [source] mobile is PROPORTIONATE and SHORTER than desktop, expressed as a clamp rather than one number at both ends',
	1 === preg_match( '/@media \(max-width: 767px\) \{\s*\.post-header--field-note \.post-header__image \{\s*--bhp-featured-max-h: clamp\(/s', $css_src )
);
/* ⛔ The scope guard, stated as a second, independent assertion rather than
      trusted from §5.4a: the featured rule must not reach the article body. */
bhp_c169_ok( '§5.13 ⛔ [source] the featured rule never mentions .post-content', false === strpos( $feat, '.post-content' ) );
bhp_c169_ok(
	'§5.14 ⛔ [source] the minified stylesheet carries the calc() intact — a minifier that strips spaces inside calc() would ship a broken rule',
	false !== strpos( preg_replace( '/\s+/', ' ', bhp_c169_file( 'assets/css/blog-post.min.css' ) ), 'max-width: calc( min(100% - (2 * var(--gutter)), var(--container-content)) - (2 * var(--bhp-editorial-inset)) );' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ §5.15–§5.16 — THE DRIFT ALARM ON A DELIBERATE DUPLICATE.
 *
 * `blog-post.css` mirrors `.editorial-surface`'s padding into
 * `--bhp-editorial-inset`, because `.post-header` is a SIBLING of
 * `.post-content` and cannot inherit it. That duplicate is the one real
 * liability this release introduces. These two assertions are what make it a
 * LOUD liability instead of a silent one: retune `.editorial-surface` in
 * `style.css` and this suite goes red the same day, instead of the featured
 * image quietly drifting off the text column again months later.
 *
 * ⚠ THEY ASSERT AGREEMENT, NOT CORRECTNESS. If someone changed BOTH files in
 *   the same wrong way these would still pass. The rendered width is measured
 *   in a browser at a stated `window.innerWidth`; that measurement is the claim.
 * ═══════════════════════════════════════════════════════════════════════════ */
$style_src = bhp_c169_file( 'style.css' );
bhp_c169_ok(
	'§5.15 ⛔ the desktop inset still matches .editorial-surface in style.css',
	false !== strpos( $css_src, '--bhp-editorial-inset: clamp(2rem, 5vw, 4rem);' )
		&& false !== strpos( $style_src, '.editorial-surface { padding: clamp(2rem,5vw,4rem);' )
);
bhp_c169_ok(
	'§5.16 ⛔ and the <=640px inset still matches its override in style.css',
	false !== strpos( $css_src, '--bhp-editorial-inset: 1.5rem;' )
		&& false !== strpos( $style_src, '.editorial-surface { padding: 1.5rem; }' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 — ABOVE-FOLD SAFETY AND THE MID-CAPTURE STAND-DOWN.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§6 — above-fold safety and the stand-down' );

bhp_c169_ok(
	'§6.1 ⛔ the band is neither fixed nor sticky (rubric row 1 stays green)',
	! preg_match( '/\.bhp-capture-band[^{]*\{[^}]*position:\s*(fixed|sticky)/s', $css_src )
);
bhp_c169_ok(
	'§6.2 ⚠ the posts 28/88 mid capture stands down while the band is on — FLAGGED, reversible in one line',
	false !== strpos( $blog_src, 'if ( bhp_blog_capture_band_enabled() ) {' )
);
bhp_c169_ok( '§6.3 ⭐ nothing was deleted: the mid capture\'s own scope survives', array( 28, 88 ) === array_map( 'intval', bhp_blog_midcapture_post_ids() ) );
bhp_c169_ok( '§6.4 ⭐ and its template still renders, so the reversal needs no code restored', '' !== bhp_c169_render( 'template-parts/acquisition/post-mid-capture' ) );
bhp_c169_ok( '§6.5 ⭐ its own offset function is untouched', (int) bhp_blog_midcapture_offset( '<p>a</p><p>b</p><h2>H</h2>' ) === strpos( '<p>a</p><p>b</p><h2>H</h2>', '<h2>' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 — ⭐⭐ NEW AT 1.19.323: FORM-MOMENT ATTRIBUTION CAPTURE.
 *
 * ⛔ THESE ARE BEHAVIOURAL, NOT STRING-MATCHES. Each one drives the real
 *    `bhp_get_signup_traffic_source()` with a constructed request state and
 *    asserts the value it returns. A source-shape assertion could not tell the
 *    difference between "the click ID is read" and "the click ID is read and
 *    then discarded by the precedence rules", which is the whole risk here.
 *
 * ⛔ NO COOKIE IS WRITTEN BY THIS SUITE AND NONE IS LEFT BEHIND. `$_COOKIE` is
 *    a PHP superglobal, not a browser cookie: setting it here changes what this
 *    process reads and nothing else. Every superglobal touched is restored.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§7 — form-moment attribution [behavioural]' );

/* Save and restore, so this suite cannot leak state into any later suite in
   the same `wp eval-file` process. */
$bhp_c169_saved = array(
	'get'     => isset( $_GET ) ? $_GET : array(),
	'post'    => isset( $_POST ) ? $_POST : array(),
	'cookie'  => isset( $_COOKIE ) ? $_COOKIE : array(),
	'referer' => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null,
);
$bhp_c169_reset = static function () {
	$_GET    = array();
	$_POST   = array();
	$_COOKIE = array();
	unset( $_SERVER['HTTP_REFERER'] );
};
$bhp_c169_cookie = static function ( array $last = null, array $first = null ) {
	if ( null !== $last ) {
		$_COOKIE['bhp_attr_last'] = wp_json_encode( $last );
	}
	if ( null !== $first ) {
		$_COOKIE['bhp_attr_first'] = wp_json_encode( $first );
	}
};

bhp_c169_ok(
	'§7.0 ⛔ the class the whole pipe depends on is loaded — without it every assertion below would pass vacuously by returning \'\'',
	class_exists( 'BHP_UTM_Attribution' )
);

/* ── §7.1 THE HEADLINE CASE THE BRIEF ASKS FOR ─────────────────────────────
   A URL carrying fbclid, no cookie of any kind, produces a facebook-sourced
   merge field. This is the 53-of-54-blank case the change exists to close. */
$bhp_c169_reset();
$_GET = array( 'fbclid' => 'IwAR0testvalue123' );
bhp_c169_ok(
	'§7.1 ⭐⭐ a URL with fbclid and NO cookie produces "facebook / cpc" — the consent-declining paid visitor is no longer invisible',
	'facebook / cpc' === bhp_get_signup_traffic_source()
);

$bhp_c169_reset();
$_GET = array( 'gclid' => 'abc123' );
bhp_c169_ok( '§7.1b ⭐ gclid names google', 'google / cpc' === bhp_get_signup_traffic_source() );
$bhp_c169_reset();
$_GET = array( 'ttclid' => 'abc123' );
bhp_c169_ok( '§7.1c ⭐ ttclid names tiktok', 'tiktok / cpc' === bhp_get_signup_traffic_source() );
$bhp_c169_reset();
$_GET = array( 'msclkid' => 'abc123' );
bhp_c169_ok( '§7.1d ⭐ msclkid names microsoft', 'microsoft / cpc' === bhp_get_signup_traffic_source() );

$bhp_c169_reset();
$_GET = array(
	'utm_source'   => 'pinterest',
	'utm_medium'   => 'social',
	'utm_campaign' => 'reluctant-readers',
);
bhp_c169_ok(
	'§7.1e ⭐ a same-page UTM triple is formatted by the SAME formatter the cookie path uses',
	'pinterest / social / reluctant-readers' === bhp_get_signup_traffic_source()
);

/* ── §7.2 THE DISTINCTION THAT MUST SURVIVE ────────────────────────────────
   ⛔ Clean URL + no cookie is ABSENT, not "direct". 1.19.211 built that
      distinction deliberately: a consent-declining visitor has no cookie, and
      calling them "direct" invents a fact about where they came from. The
      round-3 brief names preserving it as a requirement. */
$bhp_c169_reset();
bhp_c169_ok(
	'§7.2 ⛔⛔ clean URL + NO cookie returns \'\' — the field is ABSENT, and is still NOT "direct"',
	'' === bhp_get_signup_traffic_source()
);
bhp_c169_ok(
	'§7.2b ⛔ and \'\' is genuinely dropped from the payload rather than sent empty — the merge loop skips it exactly like an empty lead magnet',
	1 === preg_match( '/\$field_values\[\$field\] !== \'\'/', bhp_c169_file( 'inc/mailchimp.php' ) )
);

/* ── §7.3 THE COOKIE STILL WINS ────────────────────────────────────────────*/
$bhp_c169_reset();
$_GET = array( 'fbclid' => 'IwAR0testvalue123' );
$bhp_c169_cookie( array( 'utm_source' => 'newsletter', 'utm_medium' => 'email' ) );
bhp_c169_ok(
	'§7.3 ⭐⭐ COOKIE PRECEDENCE HOLDS: a last-touch cookie carrying a real campaign signal beats a same-page fbclid, because it carries cross-page history a single URL cannot',
	'newsletter / email' === bhp_get_signup_traffic_source()
);

$bhp_c169_reset();
$_GET = array( 'fbclid' => 'IwAR0testvalue123' );
$bhp_c169_cookie( array( 'timestamp' => '123' ), array( 'utm_source' => 'kirkus', 'utm_medium' => 'referral' ) );
bhp_c169_ok(
	'§7.3b ⭐ first touch also outranks the form moment when last touch is silent',
	'kirkus / referral' === bhp_get_signup_traffic_source()
);

/*
 * ⚠⚠ §7.3c IS THIS DESK'S ORDERING CALL AND IS FLAGGED AS SUCH IN THE ROUND-3
 *    REPORT. A cookie that exists but carries NO campaign signal resolves to
 *    "direct". Ranking that above a live click ID would file a paid conversion
 *    as direct traffic — a WRONG fact rather than a missing one. So the form
 *    moment sits ABOVE "direct" and BELOW any real cookie signal. If Andrew or
 *    Gandalf rules the other way this is the one assertion that flips.
 */
$bhp_c169_reset();
$_GET = array( 'fbclid' => 'IwAR0testvalue123' );
$bhp_c169_cookie( array( 'timestamp' => '123', 'landing_page' => '/blog/' ) );
bhp_c169_ok(
	'§7.3d ⚠ a SIGNAL-LESS cookie does not beat a live click ID — "facebook / cpc", not "direct" (this desk\'s ordering call, flagged for review)',
	'facebook / cpc' === bhp_get_signup_traffic_source()
);
$bhp_c169_reset();
$bhp_c169_cookie( array( 'timestamp' => '123', 'landing_page' => '/blog/' ) );
bhp_c169_ok(
	'§7.3e ⛔ but with a clean URL that same signal-less cookie still resolves to "direct" — 1.19.211\'s behaviour is unchanged',
	'direct' === bhp_get_signup_traffic_source()
);

/* ── §7.4 THE REFERER READING — the one that carries a classic form POST ───*/
$bhp_c169_reset();
$_SERVER['HTTP_REFERER'] = home_url( '/blog/best-books-for-7-year-olds/?fbclid=IwAR0x' );
bhp_c169_ok(
	'§7.4 ⭐⭐ the REFERER is read: a POST to admin-post.php has an empty $_GET, and the referer is the page the visitor was actually standing on',
	'facebook / cpc' === bhp_get_signup_traffic_source()
);
$bhp_c169_reset();
$_SERVER['HTTP_REFERER'] = 'https://evil.example.com/?utm_source=injected&utm_medium=cpc';
bhp_c169_ok(
	'§7.4b ⛔ an OFF-SITE referer is rejected on host, so nothing external can inject a value',
	'' === bhp_get_signup_traffic_source()
);

/* ── §7.5 THE HIDDEN FIELD — last-choice reading ───────────────────────────*/
$bhp_c169_reset();
$_POST = array( 'bhp_attr_now' => 'utm_source=meta&utm_medium=paid_social' );
bhp_c169_ok(
	'§7.5 ⭐ the posted hidden field is read when neither $_GET nor the referer knows anything',
	'meta / paid_social' === bhp_get_signup_traffic_source()
);
$bhp_c169_reset();
$_SERVER['HTTP_REFERER'] = home_url( '/blog/?gclid=live123' );
$_POST                   = array( 'bhp_attr_now' => 'fbclid=stale456' );
bhp_c169_ok(
	'§7.5b ⛔⛔ THE FRESH REFERER OUTRANKS THE POSSIBLY-CACHED HIDDEN FIELD — this is the whole mitigation for a page cache serving one visitor\'s parameters to the next',
	'google / cpc' === bhp_get_signup_traffic_source()
);

/* ── §7.6 THE PRIVACY BOUNDARY, ASSERTED RATHER THAN PROMISED ──────────────*/
$bhp_c169_reset();
bhp_c169_ok(
	'§7.6 ⛔⛔ landing_page IS NOT IN THE CAPTURE WHITELIST — the existing privacy exclusion is preserved, and the new path cannot even read it',
	! in_array( 'landing_page', bhp_get_attribution_capture_params(), true )
);
bhp_c169_ok(
	'§7.6b ⛔ nor is any other URL-shaped or timestamp field: the whitelist is exactly the 5 UTMs and the 4 click IDs',
	array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'ttclid', 'msclkid' )
		=== bhp_get_attribution_capture_params()
);
$bhp_c169_reset();
$_GET = array(
	'fbclid'       => 'ok123',
	'email'        => 'parent@example.com',
	'landing_page' => 'https://example.com/private?token=abc',
	'name'         => 'Jane',
);
$bhp_c169_captured = bhp_get_form_moment_attribution();
bhp_c169_ok(
	'§7.6c ⛔⛔ NO PII CAN CROSS: an email, a name and a landing_page sitting in the same URL as the click ID are all dropped, and only fbclid survives',
	array( 'fbclid' => 'ok123' ) === $bhp_c169_captured
);
bhp_c169_ok(
	'§7.6d ⛔ and the value that would reach Mailchimp names the platform, never the opaque per-click ID itself',
	'facebook / cpc' === bhp_get_signup_traffic_source()
		&& false === strpos( bhp_get_signup_traffic_source(), 'ok123' )
);

/* ── §7.7 NO NEW COOKIE, NO CONSENT CHANGE — the two the brief forbids ─────*/
bhp_c169_ok(
	'§7.7 ⛔⛔ THE NEW CODE WRITES NO COOKIE: setcookie() appears nowhere in the pipe file',
	false === strpos( bhp_c169_file( 'inc/mailchimp.php' ), 'setcookie' )
);
bhp_c169_ok(
	'§7.7b ⛔ and the consent-gated capture script is BYTE-UNTOUCHED by this release — still fail-closed',
	false !== strpos( bhp_c169_file( 'assets/js/bhp-attribution.js' ), 'bhp_attr_last' )
		&& false === strpos( bhp_c169_file( 'assets/js/bhp-attribution.js' ), 'bhp_attr_now' )
);

/* ── §7.8 EVERY CAPTURE SURFACE INHERITS IT — the "pipe level" requirement ─*/
bhp_c169_ok(
	'§7.8 ⭐⭐ THE FIX IS AT THE PIPE, NOT PER-SURFACE: bhp_process_signup() is the single place traffic_source is computed, so popup, inline, band, footer and end-of-post all inherit it',
	1 === substr_count( bhp_c169_file( 'inc/mailchimp.php' ), "'traffic_source' => bhp_get_signup_traffic_source(" )
);
$bhp_c169_surfaces = array(
	'template-parts/acquisition/parent-popup.php',
	'template-parts/acquisition/post-capture-band.php',
	'template-parts/acquisition/post-end-capture.php',
	'template-parts/acquisition/footer-capture.php',
	'template-parts/acquisition/inline-blog-signup.php',
);
$bhp_c169_all_shared = true;
foreach ( $bhp_c169_surfaces as $bhp_c169_surface ) {
	if ( false === strpos( bhp_c169_file( $bhp_c169_surface ), 'template-parts/acquisition/signup-form' ) ) {
		$bhp_c169_all_shared = false;
	}
}
bhp_c169_ok(
	'§7.8b ⭐ and all five named capture surfaces render the ONE shared form template, so the hidden field reaches every one of them from a single edit',
	$bhp_c169_all_shared
);

/* ── §7.9 THE FORM FIELD CARRIES NO SERVER-RENDERED VALUE, EVER ────────────*/
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ INVERTED 1.19.342 (`CYCLE172-LD-FUNNEL-FIX`, audit gap G-A). THE
 *     ASSERTION THAT USED TO LIVE HERE ENCODED THE BROKEN DESIGN, AND IT
 *     PASSED THROUGHOUT THE PERIOD PRODUCTION WAS LEAKING. Preserved verbatim
 *     so the movement stays visible:
 *
 *       bhp_c169_ok(
 *         '§7.9 ⭐⭐ ON A CLEAN URL NO FIELD IS EMITTED AT ALL — every existing
 *          form's rendered markup is byte-identical to 1.19.322 for an ordinary
 *          visitor',
 *         '' === bhp_get_signup_attribution_field_value()
 *           && false === strpos( bhp_c169_render( … ), 'bhp_attr_now' ) );
 *       bhp_c169_ok(
 *         '§7.9b ⭐ and on a click-ID URL it carries exactly the whitelisted
 *          fragment, no URL and no landing page',
 *         'fbclid=IwAR0testvalue123' === bhp_get_signup_attribution_field_value() );
 *
 * ⭐ BOTH WERE TRUE OF A FRESH PHP RENDER AND BOTH WERE IRRELEVANT TO WHAT
 *    VISITORS RECEIVED. SiteGround's full-page cache strips `utm_*`/`fbclid`
 *    from the cache key, so the §7.9b render — the one that DOES carry a value
 *    — was stored under the clean URL and served to everybody. The suite was
 *    asserting that the poison was correctly manufactured.
 *
 * ⭐ THE REPLACEMENT ASSERTS THE INVARIANT THAT MAKES THE CACHE IRRELEVANT:
 *    no query string, of any shape, may put a value into the rendered HTML.
 * ═══════════════════════════════════════════════════════════════════════════
 */
$bhp_c169_reset();
$bhp_c169_clean_render = bhp_c169_render( 'template-parts/acquisition/inline-blog-signup' );
bhp_c169_ok(
	'§7.9 ⭐ on a clean URL the field renders EMPTY (unconditional markup is what makes the page honestly cacheable)',
	false === strpos( $bhp_c169_clean_render, 'bhp_attr_now' )
		|| false !== strpos( $bhp_c169_clean_render, 'name="bhp_attr_now" value=""' )
);
$bhp_c169_reset();
$_GET                  = array( 'fbclid' => 'IwAR0testvalue123', 'utm_source' => 'facebook' );
$bhp_c169_dirty_render = bhp_c169_render( 'template-parts/acquisition/inline-blog-signup' );
bhp_c169_ok(
	'§7.9b ⛔⛔ THE G-A ASSERTION: a click ID in $_GET reaches NO rendered form field — there is nothing for the cache to capture',
	false === strpos( $bhp_c169_dirty_render, 'IwAR0testvalue123' )
);
/*
 * ⭐ THE FORM-INSTANCE COUNTER IS NORMALISED OUT BEFORE COMPARING, AND THAT IS
 *    A TEST ARTEFACT RATHER THAN A CONCESSION. The template assigns each form a
 *    per-request sequence number (`inline-blog-signup-1`, `-2`, …) so that two
 *    forms on one page get unique ids and label associations. Rendering the
 *    partial TWICE inside this one test request therefore increments it —
 *    which has nothing to do with the query string.
 *
 * ⛔ VERIFIED, NOT ASSUMED: the two renders were diffed chunk-by-chunk on
 *    staging 2026-08-31. Both are 2,581 bytes and the ONLY differences are the
 *    instance number in `id`/`aria-labelledby`/`aria-describedby`. No campaign
 *    value, no click ID, no length change. Normalising the counter is what
 *    lets this assert the thing that actually matters.
 */
/*
 * ⛔ THE NONCE IS NORMALISED TOO, AND FOR THE SAME REASON — IT IS DOWNSTREAM OF
 *    THE COUNTER, NOT OF THE QUERY STRING. The signup nonce action is
 *    `bhp_mailchimp_signup_<form_id>`, so a different instance number produces
 *    a different hash. ⭐ It is NOT sensitive to `$_GET` in any way, which is
 *    the only property this assertion depends on.
 */
$bhp_c169_norm = static function ( $html ) {
	$html = preg_replace( '/inline-blog-signup-\d+/', 'inline-blog-signup-N', (string) $html );
	return preg_replace( '/name="bhp_signup_nonce" value="[^"]*"/', 'name="bhp_signup_nonce" value="N"', (string) $html );
};
bhp_c169_ok(
	'§7.9b-ii ⭐⭐ the campaign render and the clean render are IDENTICAL apart from the per-request form counter — the two are cache-interchangeable, which is exactly what SiteGround assumes',
	$bhp_c169_norm( $bhp_c169_clean_render ) === $bhp_c169_norm( $bhp_c169_dirty_render )
);
bhp_c169_ok(
	'§7.9c ⛔ the field name is RESERVED, so a caller\'s own hidden_fields cannot shadow it with a value the pipe would then trust',
	false !== strpos( bhp_c169_file( 'template-parts/acquisition/signup-form.php' ), "'bhp_attr_now'," )
);

/* ── §7.10 EXPLICIT OVERRIDE, WHICH IS WHAT MAKES THE ABOVE ISOLABLE ───────*/
$bhp_c169_reset();
$_GET = array( 'fbclid' => 'IwAR0testvalue123' );
bhp_c169_ok(
	'§7.10 ⭐ passing [] suppresses the form moment entirely, so a caller can opt out and a test can isolate the cookie path',
	'' === bhp_get_signup_traffic_source( array() )
);

/* Restore every superglobal this section touched. */
$_GET    = $bhp_c169_saved['get'];
$_POST   = $bhp_c169_saved['post'];
$_COOKIE = $bhp_c169_saved['cookie'];
if ( null === $bhp_c169_saved['referer'] ) {
	unset( $_SERVER['HTTP_REFERER'] );
} else {
	$_SERVER['HTTP_REFERER'] = $bhp_c169_saved['referer'];
}
bhp_c169_ok( '§7.11 ⭐ the suite left no request state behind', array() === array_diff_key( $_COOKIE, $bhp_c169_saved['cookie'] ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 — ⭐⭐ NEW AT 1.19.323: THE FEATURED-IMAGE HIDE TOGGLE, SHIPPED **OFF**.
 *
 * ⛔ THE POINT OF THIS SECTION IS TO PROVE TWO THINGS AT ONCE: that the toggle
 *    WORKS, and that IT IS OFF. A toggle that is only proven to exist is not
 *    proven flippable; a toggle only proven to default true is not proven to do
 *    anything when flipped. Both halves are asserted.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c169_head( '§8 — featured-image hide toggle [behavioural, shipped OFF]' );

bhp_c169_ok( '§8.1 the helper exists', function_exists( 'bhp_blog_featured_image_on_single' ) );
/*
 * ⚠⚠ §8.2 AND §8.4 REVERSED AT 1.19.341 (`CYCLE171-LD-341` item 2), AND THE
 *    HISTORY OF THIS PAIR IS WORTH KEEPING BECAUSE IT HAS NOW MOVED TWICE:
 *
 *      1.19.323  shipped the toggle ON  -> the helper resolved TRUE
 *      1.19.324  founder item 474 "Hide them" (2026-08-29) added
 *                `__return_false` -> the helper resolved FALSE
 *      1.19.341  founder REVERSED item 474 (2026-08-31, "all the blogs should
 *                have a small heading picture") -> the line is removed and the
 *                helper resolves TRUE again
 *
 * ⛔ THE ASSERTIONS FOLLOW THE RULED STATE, WHICH IS THE ONLY HONEST THING THEY
 *    CAN DO — but note what has NOT changed across all three moves: §8.3 still
 *    proves the toggle actually does something when flipped, and §8.7 still
 *    proves it cannot reach a card, a social image or the OG tag. A toggle
 *    proven only to hold its current value is not proven to be a toggle.
 */
bhp_c169_ok(
	'§8.2 ⭐⭐ IT RESOLVES TRUE — founder REVERSAL of item 474 (2026-08-31) removed the shipped __return_false at 1.19.341; the image is back, small',
	true === bhp_blog_featured_image_on_single()
);

$bhp_c169_flip = static function () {
	return false;
};
add_filter( 'bhp_blog_featured_image_on_single', $bhp_c169_flip, 10, 1 );
bhp_c169_ok( '§8.3 ⭐⭐ FLIPPING IT WORKS — one line turns it off', false === bhp_blog_featured_image_on_single() );
remove_filter( 'bhp_blog_featured_image_on_single', $bhp_c169_flip, 10 );
bhp_c169_ok( '§8.4 ⭐ and removing the test filter returns to the shipped state — TRUE since 1.19.341 removed item 474’s __return_false from the theme', true === bhp_blog_featured_image_on_single() );
/*
 * ⚠ §8.4b IS ANCHORED TO THE START OF A LINE, AND THE FIRST DRAFT OF IT WAS NOT
 *   — IT FAILED ON ITS FIRST RUN AGAINST A CORRECT BUILD, AND THE ERROR WAS
 *   MINE, NOT THE BUILD'S. An unanchored pattern also matches the 1.19.323
 *   docblock, which quotes `add_filter( 'bhp_blog_featured_image_on_single',
 *   '__return_false' );` verbatim as the documented one-line flip. That comment
 *   is DOCUMENTATION and must stay. A real registration sits at column zero;
 *   the docblock's copy is indented behind ` * `.
 *
 * ⛔ THE BUILD WAS CHECKED BEFORE THE ASSERTION WAS TOUCHED rather than the
 *   pattern being loosened until it went green: §8.2 and §8.4 both resolve the
 *   helper to TRUE at runtime, which is only possible with no such filter
 *   registered. This assertion adds the SOURCE-level proof that the runtime
 *   result is not merely a later filter out-competing a surviving one.
 */
bhp_c169_ok(
	'§8.4b ⛔⛔ AND THE __return_false LINE IS GENUINELY GONE FROM THE SOURCE — not merely out-competed by a later filter at another priority',
	0 === preg_match( "/^add_filter\(\s*'bhp_blog_featured_image_on_single',\s*'__return_false'/m", bhp_c169_file( 'inc/blog-post-template.php' ) )
);

$bhp_c169_single = bhp_c169_file( 'single.php' );
bhp_c169_ok(
	'§8.5 ⭐ single.php actually consults it before rendering the masthead image',
	1 === preg_match( '/\$bhp_show_featured\s*&&\s*has_post_thumbnail\(\)/', $bhp_c169_single )
);
bhp_c169_ok(
	'§8.6 ⛔ and it is null-guarded, so a partial deploy degrades to the old behaviour instead of fatalling',
	false !== strpos( $bhp_c169_single, "function_exists('bhp_blog_featured_image_on_single')" )
);
/*
 * ⛔⛔ §8.7 IS THE SCOPE GUARD AND IT IS THE ASSERTION THAT MATTERS MOST HERE.
 *    Cards, the related-article grid, Open Graph and schema must be UNREACHABLE
 *    from this filter. Each of those reads the thumbnail through its own call;
 *    if any of them ever routed through the helper, turning the toggle off
 *    would silently strip the site's social images — a much larger blast radius
 *    than the founder asked for.
 */
$bhp_c169_others = array( 'index.php', 'template-parts/guides/article-card.php' );
$bhp_c169_clean  = true;
foreach ( $bhp_c169_others as $bhp_c169_other ) {
	if ( false !== strpos( bhp_c169_file( $bhp_c169_other ), 'bhp_blog_featured_image_on_single' ) ) {
		$bhp_c169_clean = false;
	}
}
bhp_c169_ok(
	'§8.7 ⛔⛔ CARDS AND THE RELATED GRID NEVER CONSULT IT — the toggle cannot reach a card, a social image or the OG tag',
	$bhp_c169_clean
);
bhp_c169_ok(
	'§8.8 ⛔ it is NOT gated on bhp_blog_template_active() — turning the blog component off must not silently change featured-image behaviour as a side effect',
	1 === preg_match( '/function bhp_blog_featured_image_on_single\([^)]*\) \{\s*return \(bool\) apply_filters\(/', bhp_c169_file( 'inc/blog-post-template.php' ) )
);

/* ═══════════════════════════════════════════════════════════════════════════ */
printf(
	"\n=== CYCLE169 BLOG LAYOUT: %d passed, %d FAILED ===\n",
	(int) $GLOBALS['bhp_c169_pass'],
	(int) $GLOBALS['bhp_c169_fail']
);
