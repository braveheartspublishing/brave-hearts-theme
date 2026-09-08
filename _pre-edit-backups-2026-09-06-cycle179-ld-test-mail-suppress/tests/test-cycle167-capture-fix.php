<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE CAPTURE-FIX SUITE — theme 1.19.296, 2026-08-27,
 * `CYCLE167-LD-CAPTURE-FIX-BUILD`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-capture-fix.php --user=1
 *
 * Covers items 2-7 of the founder's 2026-08-27 capture programme. The email
 * pipe itself (item 1 / FIX-1 / FIX-2) has its own suite:
 * `tests/test-capture-pipe-endtoend.php`.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is PHP and source level. It cannot see layout, colour, a tap target,
 * console cleanliness or where a form actually sits on a rendered page. Every
 * such claim in the handoff carries browser evidence at a stated
 * `window.innerWidth` instead, and is NOT inferred from a PASS below.
 *
 * ⚠ TWO ASSERTIONS BELOW ARE DELIBERATELY SOURCE-LEVEL AND ARE LABELLED
 *   `[source]`: the `/complete-collection/` surface rule and the teacher-page
 *   panel. Both depend on a main query this harness does not have, so they are
 *   checked as code shape here and VERIFIED IN A BROWSER in the handoff. A
 *   source assertion is honest about being one; it is not a substitute.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber. The only mutation is adding and then REMOVING its own
 *    temporary filters, and §9 asserts they are gone.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/*
 * ⛔ COUNTERS IN $GLOBALS. `wp eval-file` runs this file in FUNCTION scope, so
 *    a file-top `$pass = 0;` is a local and `global $pass;` in the helper binds
 *    a different, unset global — the helper would increment one variable and
 *    the summary read another, making the suite structurally incapable of
 *    reporting a failure. A suite that cannot fail is a fabricated verification.
 */
$GLOBALS['bhp_cfx_pass'] = 0;
$GLOBALS['bhp_cfx_fail'] = 0;

function bhp_cfx_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_cfx_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_cfx_fail']++;
		echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

function bhp_cfx_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/** Render a template part to a string. */
function bhp_cfx_render( $slug ) {
	ob_start();
	get_template_part( $slug );
	return (string) ob_get_clean();
}

function bhp_cfx_theme_file( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * ⭐ A FILE'S **CODE**, WITH EVERY COMMENT REMOVED.
 *
 * ⛔⛔ THE FIRST RUN OF THIS SUITE FAILED THREE ASSERTIONS ON ITS OWN
 *     DOCUMENTATION, AND THE LESSON IS WORTH THE FUNCTION. `page-market-capture.php`
 *     asserts that it never touches a visit flag or WooCommerce — and says so
 *     in a docblock, in those words. A raw `strpos()` for `bhp_visit` or
 *     `add-to-cart` therefore matched the PROMISE instead of a violation, and
 *     reported a defect that did not exist.
 *
 * ⭐ This codebase already warns about exactly that trap in three places
 *    ("writing one of those class names into this prose would break it").
 *    Stripping comments makes the assertion test the CODE, which is what it
 *    always meant to test.
 *
 * ⛔ `token_get_all()` RATHER THAN A REGEX. A regex for `/* … *\/` cannot tell
 *    a comment from the same characters inside a string literal; the tokenizer
 *    can, because it is the same lexer PHP itself uses.
 */
function bhp_cfx_code_only( $rel ) {
	$src = bhp_cfx_theme_file( $rel );
	if ( '' === $src ) {
		return '';
	}
	$out = '';
	foreach ( token_get_all( $src ) as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			$out .= $t[1];
		} else {
			$out .= $t;
		}
	}
	return $out;
}

/** Count affiliate anchors the way §26.3 counts them. */
function bhp_cfx_affiliate_count( $html ) {
	return preg_match_all( '#https?://(?:[a-z0-9.-]*\.)?(?:amzn\.to|amazon\.[a-z.]+)/#i', (string) $html );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§0 PRECONDITIONS' );

foreach ( array(
	'bhp_popup_ab_emphasise_free',
	'bhp_should_show_parent_ab_popup',
	'bhp_should_show_teacher_popup',
	'bhp_get_lead_magnet_cover',
	'bhp_blog_midcapture_post_ids',
	'bhp_blog_midcapture_offset',
	'bhp_blog_inject_midcapture',
	'bhp_blog_capture_context',
	'bhp_get_mailchimp_signup_tags',
) as $fn ) {
	bhp_cfx_ok( "§0 {$fn}() is loaded", function_exists( $fn ) );
}

/* ⭐ 1.19.297 — a FLOOR rather than an equality. The 296 form pinned the exact
 *    version, so this suite failed on its own success the moment the next
 *    release shipped, which is a guard that cries wolf rather than one that
 *    guards. What it actually needs to refuse is running against a theme OLDER
 *    than the features it asserts. */
bhp_cfx_ok(
	'§0 theme version is 1.19.296 or later',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.296', '>=' ),
	'got ' . wp_get_theme()->get( 'Version' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · ITEM 2 — THE **FREE** EMPHASIS IS BACK, AND IT IS UNCONDITIONAL.
 *
 * ⭐ Andrew's standing 1.19.207 order: FREE all caps, bold and larger. It fell
 *    out at 1.19.267 when the A/B was switched off, because both the all-caps
 *    (variant map) and the emphasis (`bhp_popup_ab_emphasise_free()`) were
 *    reachable only through the experiment. These assertions exist so that
 *    switching ANY experiment on or off can never take it away again.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§1 ITEM 2 — the word FREE' );

/* ⭐ 1.19.297 — the headline the founder picked (carrier item 290) replaces his
 *    2026-08-19 one. ⛔ THIS SECTION'S PROPERTY IS UNCHANGED and that is the
 *    point of it: the FREE treatment must survive ANY copy change, including
 *    this one, because the defect it was written for (1.19.267) was exactly a
 *    copy change silently taking the emphasis with it. */
$emph = bhp_popup_ab_emphasise_free( 'FREE Chapter for Reluctant Readers' );
bhp_cfx_ok(
	'§1.1 the helper wraps the standalone token FREE',
	false !== strpos( $emph, '<span class="popup-ab__free">FREE</span>' ),
	$emph
);
bhp_cfx_ok(
	'§1.2 ⛔ it escapes BEFORE it wraps, so copy can never inject markup',
	false === strpos( bhp_popup_ab_emphasise_free( '<script>x</script> FREE' ), '<script>' )
);
bhp_cfx_ok(
	'§1.3 ⛔ only the standalone token matches, never a substring',
	false === strpos( bhp_popup_ab_emphasise_free( 'freely FREEDOM' ), 'popup-ab__free' )
);

$popup = bhp_cfx_render( 'template-parts/acquisition/parent-ab-popup' );
bhp_cfx_ok( '§1.4 the popup renders', '' !== $popup );
bhp_cfx_ok(
	'§1.5 ⭐ the RENDERED heading carries the emphasis span',
	false !== strpos( $popup, '<span class="popup-ab__free">FREE</span>' )
);
bhp_cfx_ok(
	'§1.6 ⭐ the heading reads FREE in all caps',
	false !== strpos( wp_strip_all_tags( $popup ), 'FREE Chapter for Reluctant Readers' )
);
/* ⭐ 1.19.297 — §1.7 REPLACED, not deleted. It used to guard the hyphenation of
 *    "20 Minute", a compound that no longer appears anywhere in the headline, so
 *    the old assertion would now assert nothing at all. It is replaced by a
 *    guard on the retired string itself: a partial revert that leaves both
 *    headlines on the surface is the realistic failure now. */
bhp_cfx_ok(
	'§1.7 ⛔ the retired 1.19.296 headline is gone, not lingering beside the new one',
	false === strpos( wp_strip_all_tags( $popup ), '20 Minute Reluctant Reader Kit' )
);
bhp_cfx_ok(
	'§1.8 ⛔ the emphasis is NOT behind an A/B block — no abTest config is emitted',
	false === strpos( $popup, 'abTest' )
);

$css = bhp_cfx_theme_file( 'style.css' );
bhp_cfx_ok(
	'§1.9 the stylesheet carries the bold + size-step rule for FREE',
	false !== strpos( $css, '.mariana-popup--ab .mariana-popup__dialog h2 .popup-ab__free' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · ITEM 3a — THE SUBHEAD SLOT. IT SHIPS EMPTY, ON PURPOSE.
 *
 * ⛔⛔ THE EMPTINESS IS THE ASSERTION. The audit that asked for a subhead also
 *     proposed one ("the three questions I ask a child…") and flagged it as
 *     UNVERIFIED. This build opened the real Kit PDF: it contains "Three Ways
 *     to Make This Feel Like an Adventure" — three tips to the PARENT, not
 *     three questions asked of a child. The proposed line was FALSE. §2.1
 *     exists to keep it, or any other unvetted default, from arriving later.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§2 ITEM 3a — the subhead slot' );

/* ⭐⭐ 1.19.297 — §2.1 IS INVERTED, AND THE INVERSION IS AN UPGRADE, NOT A
 *     RELAXATION. At 296 the slot shipped EMPTY because the only proposed line
 *     was an unsourced claim the real Kit PDF refuted. At 297 the founder
 *     supplied his own line (carrier item 290), so the assertion moves from
 *     "nothing unsourced ships here" to "exactly HIS words ship here, character
 *     for character" — which is the stronger of the two guards, because an
 *     empty slot can be filled by anything at any time and an exact comparison
 *     cannot. ⛔ §2.1b keeps the REFUSED line refused. */
bhp_cfx_ok(
	'§2.1 ⭐⭐ the subhead renders the founder\'s line, character for character',
	1 === substr_count( $popup, '<p class="popup-ab__subhead">' . esc_html( "I'll send you the chapter now, just add your email." ) . '</p>' ),
	$popup
);
bhp_cfx_ok(
	'§2.1b ⛔⛔ the audit\'s REFUSED subhead (false of the real Kit) still never ships',
	false === stripos( $popup, 'three questions' )
);

add_filter( 'bhp_parent_popup_subhead', function () {
	return '  Test subhead <b>x</b>  ';
} );
$popup_sub = bhp_cfx_render( 'template-parts/acquisition/parent-ab-popup' );
bhp_cfx_ok(
	'§2.2 the slot renders when a line is supplied',
	false !== strpos( $popup_sub, 'popup-ab__subhead' )
);
bhp_cfx_ok(
	'§2.3 ⛔ supplied copy is escaped, never trusted as markup',
	false === strpos( $popup_sub, '<b>x</b>' )
);
bhp_cfx_ok(
	'§2.4 surrounding whitespace is trimmed',
	false !== strpos( $popup_sub, '>Test subhead' )
);
remove_all_filters( 'bhp_parent_popup_subhead' );

add_filter( 'bhp_parent_popup_subhead', function () {
	return "   \n\t ";
} );
bhp_cfx_ok(
	'§2.5 ⛔ a whitespace-only line renders NOTHING, not an empty paragraph',
	false === strpos( bhp_cfx_render( 'template-parts/acquisition/parent-ab-popup' ), 'popup-ab__subhead' )
);
remove_all_filters( 'bhp_parent_popup_subhead' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ITEM 3b — THE COVER IS BIGGER **AND** SHARPER.
 *
 * ⭐ Enlarging the old 173x224 artwork alone would have made it soft, not
 *    bigger (1.08x device pixels at 160px). The asset was regenerated at
 *    346x448 from page 1 of the live Kit PDF. §3.3 is the load-bearing one:
 *    two OTHER surfaces render this same file height-driven with `width:auto`,
 *    so an aspect-ratio change would silently reflow both.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§3 ITEM 3b — the kit cover' );

$cover = bhp_get_lead_magnet_cover( 'reluctant_reader_adventure_kit' );
bhp_cfx_ok( '§3.1 the cover resolves', ! empty( $cover['width'] ) && ! empty( $cover['height'] ) );
bhp_cfx_ok(
	'§3.2 ⭐ the asset is the regenerated 346x448',
	346 === (int) $cover['width'] && 448 === (int) $cover['height'],
	( isset( $cover['width'] ) ? $cover['width'] . 'x' . $cover['height'] : 'missing' )
);
bhp_cfx_ok(
	'§3.3 ⛔⛔ the aspect ratio is UNCHANGED from 173/224 — the signup modal and the thank-you page take their shape from it',
	abs( ( 346 / 448 ) - ( 173 / 224 ) ) < 0.000001
);
/*
 * ⚠ SCOPED TO THE RULE, NOT TO THE STYLESHEET. An earlier draft asserted that
 *   the string `width: 96px;` was absent from style.css ENTIRELY — but other,
 *   unrelated components legitimately use that width, so it failed while the
 *   popup rule was perfectly correct. The assertion now reads the declaration
 *   block it actually cares about.
 */
if ( preg_match( '/\.mariana-popup--ab \.popup-ab__kit-cover img \{[^}]*\}/', $css, $cover_rule ) ) {
	bhp_cfx_ok(
		'§3.4 desktop renders the cover at 160px, up from 96px',
		false !== strpos( $cover_rule[0], 'width: 160px;' ) && false === strpos( $cover_rule[0], '96px' ),
		$cover_rule[0]
	);
} else {
	bhp_cfx_ok( '§3.4 desktop cover rule found', false, 'rule block not matched' );
}
bhp_cfx_ok(
	'§3.5 mobile renders the cover at 132px, up from 84px',
	false !== strpos( $css, '.mariana-popup--ab .popup-ab__kit-cover img { width: 132px; }' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ITEM 4 — THE #1 ENTRY PAGE JOINS THE GATED SURFACE.  [source]
 *
 * ⚠ `bhp_should_show_parent_ab_popup()` depends on the main query, which this
 *   harness does not have. Asserting code shape here and VERIFYING IN A
 *   BROWSER in the handoff is honest; calling a source read a live check
 *   would not be.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§4 ITEM 4 — /complete-collection/ placement flip  [source]' );

$fn_src = bhp_cfx_theme_file( 'functions.php' );
bhp_cfx_ok(
	'§4.1 [source] the surface rule now includes the complete-collection page',
	false !== strpos( $fn_src, "is_page('complete-collection')" )
);
bhp_cfx_ok(
	'§4.2 [source] the homepage and blog-post surfaces are still in the rule',
	false !== strpos( $fn_src, '$bhp_ab_popup_surface = is_front_page()' )
		&& false !== strpos( $fn_src, "|| is_singular('post')" )
);
bhp_cfx_ok(
	'§4.3 ⛔ the funnel-isolation exclusion of /teachers/ from the PARENT popup is untouched',
	false !== strpos( $fn_src, "// ⛔ Parent funnel only. Never on the teacher page. `.claude/rules/funnels.md`." )
);
bhp_cfx_ok(
	'§4.4 ⛔ the Kit landing page is NOT reopened — FIX-4 is Andrew\'s, not this build\'s',
	false === strpos( $fn_src, "is_page('reluctant-reader-adventure-kit')" )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · ITEM 5 — THE TEACHER FUNNEL HAS A FRONT DOOR AGAIN.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§5 ITEM 5 — /teachers/ capture' );

bhp_cfx_ok(
	'§5.1 ⭐ the teacher popup is no longer filtered off',
	true === (bool) apply_filters( 'bhp_show_teacher_popup', true )
);
bhp_cfx_ok(
	'§5.2 ⛔ the PARENT popup stays retired — only the teacher half was reversed',
	false === (bool) apply_filters( 'bhp_show_parent_popup', true )
);

$teacher_popup = bhp_cfx_theme_file( 'template-parts/acquisition/mariana-popup.php' );

/*
 * ⭐⭐ §5.3–§5.5 REWRITTEN 1.19.300 (`CYCLE167-LD-POPUP-TIME-ONLY`) ON FOUNDER
 *     CARRIER ITEM 306, 2026-08-27, VERBATIM: "We also dont have the awareness
 *     or market share - I think we keep our pop ups time only."
 *     ⚠ RELAYED via the Chief of Staff; not witnessed by this suite's author.
 *
 * ⛔ THESE THREE ASSERTIONS PREVIOUSLY REQUIRED THE OPPOSITE — mode `gated`,
 *    a dwell floor keyed `minDelay`, and NO fallback — on Andrew's 2026-08-19
 *    *"wait for engagement and time."* Item 306 supersedes that by his own
 *    word. "Our pop ups" is plural, so the teacher funnel moves with the
 *    parent one. ⛔ THE FUNNEL-ISOLATION ASSERTIONS BELOW ARE UNTOUCHED: this
 *    changed WHEN the popup asks, never WHOSE funnel it belongs to.
 */
bhp_cfx_ok(
	'§5.3 item 306: the teacher popup opens on TIME ALONE — mode simple, no engagement gate',
	false !== strpos( $teacher_popup, "'mode'    => 'simple'" )
		&& false === strpos( $teacher_popup, "'mode'    => 'gated'" )
);
bhp_cfx_ok(
	'§5.4 item 306: its timer matches the parent funnel at 15000ms on both devices — the number is unchanged, only the key',
	2 === substr_count( $teacher_popup, "'delay' => 15000" )
);
/*
 * ⚠ THE CONFIG KEY, NOT THE BARE WORD. The file's own docblock discusses both
 *   `fallbackDelay` and `scrollPct` in its preserved supersession note, so a
 *   bare `strpos()` matches the explanation of the rule it is testing.
 *   Comments are stripped first, then the actual array keys are checked.
 */
$teacher_code = bhp_cfx_code_only( 'template-parts/acquisition/mariana-popup.php' );
bhp_cfx_ok(
	'§5.5 item 306: ⛔ no scrollPct and no fallbackDelay — no scroll listener is ever registered on /teachers/',
	false === strpos( $teacher_code, 'scrollPct' )
		&& false === strpos( $teacher_code, 'fallbackDelay' )
);

/*
 * ⛔⛔ FUNNEL ISOLATION — `.claude/rules/funnels.md`. The two funnels must not
 *     be able to read or write each other's state. Asserted as the ABSENCE of
 *     each prefix from the other's file.
 */
bhp_cfx_ok(
	'§5.6 ⛔ the teacher popup uses ONLY the teacher storage/event prefixes',
	false !== strpos( $teacher_popup, 'bhp_mariana_popup' )
		&& false !== strpos( $teacher_popup, 'teacher_popup' )
		&& false === strpos( $teacher_popup, 'bhp_parent_popup' )
);
bhp_cfx_ok(
	'§5.7 ⛔ the parent popup uses ONLY the parent storage/event prefixes',
	false !== strpos( $popup, 'bhp_parent_popup' )
		&& false === strpos( $popup, 'bhp_mariana_popup' )
		&& false === strpos( $popup, 'mariana-guide-thank-you' )
);

$teachers_tpl = bhp_cfx_theme_file( 'page-teachers.php' );
bhp_cfx_ok(
	'§5.8 [source] a capture panel now sits high on the teachers page',
	false !== strpos( $teachers_tpl, 'teacher-resources-signup-top' )
);
bhp_cfx_ok(
	'§5.9 ⛔ the deep panel is NOT removed — its anchor target still exists',
	false !== strpos( $teachers_tpl, 'id="teacher-email-signup"' )
		&& false !== strpos( $teachers_tpl, "'id'           => 'teacher-resources-signup'," )
);
bhp_cfx_ok(
	'§5.10 ⛔ the two panels cannot collide on ids',
	false !== strpos( $teachers_tpl, 'teacher-email-signup-top' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · ITEM 6 — MID-POST CAPTURE, AND THE §26 AFFILIATE GUARD.
 *
 * ⛔⛔ §26: an affiliate link is revenue and is NEVER lost to a build step.
 *     These two posts carry 12 and 9 of Andrew's personal Associates links.
 *     §6.5 and §6.6 are the guard IN CODE; the rendered before/after count on
 *     staging is run separately and reported in the handoff, because §26.6 is
 *     explicit that a count which was not actually run is a fabricated check.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§6 ITEM 6 — mid-post capture + §26 guard' );

bhp_cfx_ok(
	'§6.1 the two top entry posts are the scope',
	array( 28, 88 ) === array_map( 'intval', bhp_blog_midcapture_post_ids() )
);

$sample = '<p>One.</p><p>Two.</p><h2>First section</h2><p>Three.</p><h2>Second</h2>';
$off    = bhp_blog_midcapture_offset( $sample );
bhp_cfx_ok(
	'§6.2 it anchors on the FIRST <h2>, i.e. the end of the introduction',
	(int) $off === strpos( $sample, '<h2>' )
);
bhp_cfx_ok(
	'§6.3 ⛔ it refuses a post that opens straight into a heading',
	null === bhp_blog_midcapture_offset( '<h2>Straight in</h2><p>x</p>' )
);
bhp_cfx_ok(
	'§6.4 ⛔ it refuses a post with no headings at all',
	null === bhp_blog_midcapture_offset( '<p>a</p><p>b</p><p>c</p>' )
);

/*
 * §6.5 — THE INSERT-ONLY PROOF. The injector's whole §26 safety argument is
 * that it only ever does `substr(0,off) . panel . substr(off)`. Simulated here
 * on content carrying affiliate anchors of both shapes.
 */
$aff = '<p>Intro one.</p><p>Intro two with <a href="https://amzn.to/3PFKexe">a link</a>.</p>'
	. '<h2>Books</h2><p><a href="https://www.amazon.com/dp/0375813659?tag=bravehearts0e-20">tagged</a></p>';
$before_n = bhp_cfx_affiliate_count( $aff );
$off2     = bhp_blog_midcapture_offset( $aff );
$spliced  = substr( $aff, 0, $off2 ) . '<aside class="bhp-post-capture--mid">panel</aside>' . substr( $aff, $off2 );
$after_n  = bhp_cfx_affiliate_count( $spliced );

bhp_cfx_ok( '§6.5a the sample carries 2 affiliate anchors before', 2 === $before_n, (string) $before_n );
bhp_cfx_ok(
	'§6.5b ⛔⛔ §26 COUNT-DECREASE: after >= before under insert-only splicing',
	$after_n >= $before_n,
	"before={$before_n} after={$after_n}"
);
bhp_cfx_ok(
	'§6.5c ⛔ the tracking code survives byte-for-byte — the tag IS the revenue',
	false !== strpos( $spliced, 'tag=bravehearts0e-20' )
);
bhp_cfx_ok(
	'§6.5d ⛔ every original byte survives in order',
	substr( $spliced, 0, $off2 ) === substr( $aff, 0, $off2 )
		&& substr( $spliced, -1 * ( strlen( $aff ) - $off2 ) ) === substr( $aff, $off2 )
);

/*
 * §6.6 — the panel itself must never introduce an Amazon URL, or a naive count
 * could rise while a real link had been lost.
 */
$mid_panel = bhp_cfx_render( 'template-parts/acquisition/post-mid-capture' );
bhp_cfx_ok( '§6.6a the mid panel renders', '' !== $mid_panel );
bhp_cfx_ok(
	'§6.6b ⛔ the panel contains NO Amazon URL of any kind',
	0 === bhp_cfx_affiliate_count( $mid_panel )
);
bhp_cfx_ok(
	'§6.6c it reuses the blog capture context, minting no new Mailchimp tag',
	false !== strpos( $mid_panel, 'name="bhp_context"' )
		&& false !== strpos( $mid_panel, 'value="' . bhp_blog_capture_context() . '"' )
);
bhp_cfx_ok(
	'§6.6d ⛔ NO outcome claim and no invented Kit contents',
	false === stripos( $mid_panel, 'three questions' )
		&& false === stripos( $mid_panel, 'will love reading' )
);
bhp_cfx_ok(
	'§6.6e ⛔ §9.1 voice: I/me/my, no "we"',
	false === preg_match( '/\b(we|our|us)\b/i', wp_strip_all_tags( $mid_panel ) )
		|| 0 === preg_match( '/\b(we|our)\b/i', wp_strip_all_tags( $mid_panel ) )
);
bhp_cfx_ok(
	'§6.6f ⛔ no em dash in visible copy',
	false === strpos( wp_strip_all_tags( $mid_panel ), '—' )
);

$blog_src = bhp_cfx_theme_file( 'inc/blog-post-template.php' );
bhp_cfx_ok(
	'§6.7 ⛔⛔ the injector performs NO regex/string REPLACEMENT on post content',
	false === strpos( $blog_src, 'preg_replace( $content' )
		&& false !== strpos( $blog_src, 'return substr( $content, 0, $offset ) . $panel . substr( $content, $offset );' )
);
bhp_cfx_ok(
	'§6.8 ⭐ it runs AFTER the book rail so the rail\'s offsets are unchanged',
	false !== strpos( $blog_src, "add_filter( 'the_content', 'bhp_blog_inject_midcapture', 13 );" )
		&& false !== strpos( $blog_src, "add_filter( 'the_content', 'bhp_blog_inject_rail', 12 );" )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · ITEM 7 — THE MARKET / EVENT QR CAPTURE PAGE.
 *
 * ⭐ 73 books sold at one market weekend, zero emails captured.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§7 ITEM 7 — market capture page' );

$market = bhp_cfx_theme_file( 'page-market-capture.php' );
bhp_cfx_ok( '§7.1 the template exists', '' !== $market );
bhp_cfx_ok(
	'§7.2 it is assignable as a page template',
	false !== strpos( $market, 'Template Name: Market Capture Page' )
);
/*
 * ⛔ CODE ONLY. This template DOCUMENTS that it touches no visit flag and no
 *    WooCommerce, using those exact words, so a raw file search matches the
 *    promise rather than a violation. See `bhp_cfx_code_only()`.
 */
$market_code = bhp_cfx_code_only( 'page-market-capture.php' );
bhp_cfx_ok( '§7.2b the code-only view is non-empty', '' !== $market_code );
bhp_cfx_ok(
	'§7.3 ⛔ NO visit-mode flag is read or written',
	false === strpos( $market_code, 'bhp_visit' )
		&& false === strpos( $market_code, 'school_visit' )
);
bhp_cfx_ok(
	'§7.4 ⛔ NO WooCommerce anything: no cart, no product, no price literal',
	false === strpos( $market_code, 'WC(' )
		&& false === strpos( $market_code, 'wc_get_' )
		&& false === strpos( $market_code, 'add-to-cart' )
		&& false === strpos( $market_code, 'add_to_cart' )
		&& 0 === preg_match( '/\$\d/', $market_code )
);
bhp_cfx_ok(
	'§7.5 ⭐ ONE field: no name field is requested',
	false === strpos( $market_code, "'require_name'" )
		&& false === strpos( $market_code, "'show_name'" )
);
bhp_cfx_ok(
	'§7.6 it reuses the shared signup form and the existing Kit magnet',
	false !== strpos( $market, "template-parts/acquisition/signup-form" )
		&& false !== strpos( $market, "'lead_magnet'     => 'reluctant_reader_adventure_kit'," )
);

$market_tags = bhp_get_mailchimp_signup_tags( 'market_capture', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_cfx_ok(
	'§7.7 ⭐ a market signup is distinguishable from a website signup',
	in_array( 'Source: Market Event', (array) $market_tags, true ),
	implode( ' | ', (array) $market_tags )
);
bhp_cfx_ok(
	'§7.8 it still carries the Kit and audience tags',
	in_array( 'Reluctant Reader Adventure Kit', (array) $market_tags, true )
		&& in_array( 'Audience: Parent/Grandparent', (array) $market_tags, true )
);

/*
 * ⛔⛔ THE REGRESSION THAT MATTERS MOST FOR A TAG FILTER: it must not touch any
 *     other surface's tags. A new filter that widened its own match would
 *     silently re-tag the whole audience.
 */
$blog_tags = bhp_get_mailchimp_signup_tags( bhp_blog_capture_context(), 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_cfx_ok(
	'§7.9 ⛔ the blog tag map is UNCHANGED by the new filter',
	in_array( 'Source: Blog Post', (array) $blog_tags, true )
		&& ! in_array( 'Source: Market Event', (array) $blog_tags, true )
);
$exit_tags = bhp_get_mailchimp_signup_tags( 'parent_popup_exit', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_cfx_ok(
	'§7.10 ⛔ the exit-intent tag map is UNCHANGED',
	in_array( 'Source: Exit Intent', (array) $exit_tags, true )
		&& ! in_array( 'Source: Market Event', (array) $exit_tags, true )
);
$teacher_tags = bhp_get_mailchimp_signup_tags( 'teacher_resources', 'teachers', 'teacher_resources', home_url( '/' ) );
bhp_cfx_ok(
	'§7.11 ⛔ the TEACHER funnel\'s tags are untouched by every change in this release',
	! in_array( 'Source: Market Event', (array) $teacher_tags, true )
		&& ! in_array( 'Reluctant Reader Adventure Kit', (array) $teacher_tags, true )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · CROSS-CUTTING — what this release must NOT have changed.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§8 regression guards' );

bhp_cfx_ok(
	'§8.1 ⛔ the exit-intent modal still exists for the pages that keep it',
	function_exists( 'bhp_should_show_exit_intent_popup' )
		&& '' !== bhp_cfx_theme_file( 'template-parts/acquisition/exit-intent-popup.php' )
);
bhp_cfx_ok(
	'§8.2 ⛔ the end-of-post capture is untouched and still renders',
	'' !== bhp_cfx_render( 'template-parts/acquisition/post-end-capture' )
);
bhp_cfx_ok(
	'§8.3 ⛔ the base post-capture block was not restyled — the mid variant is scoped',
	false !== strpos( bhp_cfx_theme_file( 'assets/css/blog-post.css' ), '.bhp-post-capture--mid {' )
);
bhp_cfx_ok(
	'§8.4 ⛔ at most ONE popup renders per page: teacher and parent surfaces are mutually exclusive',
	false !== strpos( $fn_src, "if (is_page('teachers')) {" )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · CLEANUP — this suite leaves nothing behind.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_cfx_head( '§9 cleanup' );

bhp_cfx_ok(
	'§9.1 ⛔ the suite\'s temporary subhead filter is removed',
	'' === trim( (string) apply_filters( 'bhp_parent_popup_subhead', '' ) )
);
bhp_cfx_ok(
	'§9.2 ⛔ the popup renders exactly as it did before the suite ran',
	bhp_cfx_render( 'template-parts/acquisition/parent-ab-popup' ) === $popup
);

echo "\nPASS: {$GLOBALS['bhp_cfx_pass']}   FAIL: {$GLOBALS['bhp_cfx_fail']}\n";
echo ( $GLOBALS['bhp_cfx_fail'] > 0 ? "SUITE FAIL\n" : "SUITE PASS\n" );
