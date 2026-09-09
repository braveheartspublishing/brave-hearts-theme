<?php
/**
 * CYCLE179-LD-BUILD-411 — theme 1.19.411.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * THREE APPROVED CHANGES TO THE PARENT KIT POPUP
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, seal 1451: *"Make all those changes to make it better
 * please"*. ⚠ RELAYED to this build through the Chief of Staff; NOT witnessed
 * first-hand (Standing Rules §9.2), and this suite claims no founder fact of
 * its own beyond the approved strings it pins.
 *
 *   1  DESKTOP (1440+): the headline and the sub-line go ONE TYPE STEP LARGER
 *      on the theme's OWN scale. No layout rule changes.
 *
 *   2  A NEW APPROVED CUSTOMER-FACING LINE at BOTH widths, between the
 *      headline and the sub-line:
 *          "A real chapter from The Mariana Trench. About 10 minutes."
 *      ⛔ THE WORDING IS PINNED BYTE-EXACT BELOW. §9 of the Standing Rules
 *         locks approved copy: propose changes, never make them. If a future
 *         pass wants different words it needs a founder decision, and this
 *         assertion is what forces that conversation instead of a silent edit.
 *
 *   3  PHONE (below 768px): the kit-card thumbnail is HIDDEN. It is KEPT on
 *      desktop.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE MUTATES: NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════
 * It renders one template part into an output buffer and reads two stylesheet
 * files off disk. No post, product, option, coupon, user or cart is written.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠ WHAT PHP CANNOT PROVE, AND SO IS NOT CLAIMED HERE.
 * ═══════════════════════════════════════════════════════════════════════════
 * PHP can prove the STRING is in the markup and the RULE is in the stylesheet.
 * It cannot prove a browser applied that rule, what the rendered font-size
 * was, that the thumbnail actually vanished at 390, or that the surface fits
 * without scrolling. Those are BROWSER claims. They are measured separately at
 * an asserted `window.innerWidth` (a hard abort in the harness) and recorded
 * in the build report with the numbers. ⛔ A source-read is never reported as
 * a live verification.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_c411_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_c411_note( $label ) {
	echo "NOTE: {$label}\n";
}

function bhp_c411_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Strip CSS comments before asserting on RULES.
 *
 * ⭐ WHY. This stylesheet writes essay-length comments on purpose, and §9f
 *    DELIBERATELY QUOTES the older *"IT MUST NOT GO AWAY"* rule it narrows, so
 *    the tension stays visible. A naive `strpos()` over the raw file would
 *    match prose and report a rule that does not exist. Every assertion below
 *    that claims "the stylesheet declares X" runs against comment-stripped
 *    CSS, so it is asserting CODE.
 */
function bhp_c411_css_code_only( $css ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
}

/**
 * Pull one at-rule block out by its prelude, braces balanced.
 *
 * ⛔ A regex of the `@media \([^)]*\) \{[^}]*\}` shape CANNOT read a media
 *    block that contains rules — it stops at the FIRST inner `}`. That is the
 *    trap this helper exists to avoid, and getting it wrong would make the two
 *    most important assertions in this file pass vacuously.
 */
/**
 * ⛔⛔ `$must_contain` IS NOT OPTIONAL POLISH — IT IS THE FIX FOR A REAL BUG
 *    THIS SUITE SHIPPED WITH ON ITS FIRST RUN. `@media (max-width: 767px)`
 *    appears TWICE in `style.css`: an unrelated block at ~line 9419 and the
 *    1.19.411 popup block at ~line 11255. Taking the FIRST match read the
 *    wrong block and reported the thumbnail rule missing when it was present
 *    and correct. Anchoring on a string the RIGHT block must contain makes the
 *    helper pick the intended block instead of the first one that matches a
 *    prelude, and the assertion below fails honestly if neither contains it.
 */
function bhp_c411_at_rule_block( $css, $prelude, $must_contain = '' ) {
	$start  = 0;
	$offset = 0;
	while ( true ) {
		$start = strpos( $css, $prelude, $offset );
		if ( false === $start ) {
			return '';
		}
		$found = bhp_c411_block_at( $css, $start );
		if ( '' === $must_contain || ( '' !== $found && false !== strpos( $found, $must_contain ) ) ) {
			return $found;
		}
		$offset = $start + strlen( $prelude );
	}
}

function bhp_c411_block_at( $css, $start ) {
	$open = strpos( $css, '{', $start );
	if ( false === $open ) {
		return '';
	}
	$depth = 0;
	$len   = strlen( $css );
	for ( $i = $open; $i < $len; $i++ ) {
		if ( '{' === $css[ $i ] ) {
			$depth++;
		} elseif ( '}' === $css[ $i ] ) {
			$depth--;
			if ( 0 === $depth ) {
				return substr( $css, $open, $i - $open + 1 );
			}
		}
	}
	return '';
}

$style_raw  = bhp_c411_read( 'style.css' );
$style_code = bhp_c411_css_code_only( $style_raw );
$min_raw    = bhp_c411_read( 'style.min.css' );
$min_code   = bhp_c411_css_code_only( $min_raw );

bhp_c411_assert( '' !== trim( $style_raw ), '0.1: style.css is readable', $failures );
bhp_c411_assert( '' !== trim( $min_raw ), '0.2: style.min.css is readable', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE NEW LINE — the approved string, byte-exact, in the RENDERED markup
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n-- 1. THE APPROVED LEDE --\n";

/**
 * ⛔ THE ONE STRING THIS WHOLE SUITE EXISTS TO PROTECT. Byte-exact, including
 *    both full stops, the capital T of "The", and "About" rather than a bare
 *    number. Do not "tidy" it.
 */
$approved_lede = 'A real chapter from The Mariana Trench. About 10 minutes.';

ob_start();
get_template_part( 'template-parts/acquisition/parent-ab-popup' );
$rendered = (string) ob_get_clean();

bhp_c411_assert( '' !== trim( $rendered ), '1.0: the popup template renders', $failures );

bhp_c411_assert(
	false !== strpos( $rendered, $approved_lede ),
	'1.1: ⭐ the approved lede renders VERBATIM: "' . $approved_lede . '"',
	$failures
);

bhp_c411_assert(
	1 === substr_count( $rendered, 'class="popup-ab__lede"' ),
	'1.2: exactly ONE lede element renders (not zero, not a duplicate)',
	$failures
);

/**
 * ⛔ ORDER IS PART OF THE APPROVAL. "Between the headline and the existing
 *    sub-line" is not decoration: a lede rendered after the sub-line, or above
 *    the headline, would be a different surface. Asserted by byte offset, so
 *    it cannot pass on presence alone.
 */
$pos_h2   = strpos( $rendered, 'id="parent-ab-popup-title"' );
$pos_lede = strpos( $rendered, 'class="popup-ab__lede"' );
$pos_sub  = strpos( $rendered, 'class="popup-ab__subhead"' );
bhp_c411_assert(
	false !== $pos_h2 && false !== $pos_lede && false !== $pos_sub
		&& $pos_h2 < $pos_lede && $pos_lede < $pos_sub,
	'1.3: ⭐ the lede sits BETWEEN the headline and the sub-line, by byte offset',
	$failures
);

/**
 * ⛔ IT IS A PARAGRAPH, NOT A SECOND HEADING. The dialog is labelled by the
 *    `h2` through `aria-labelledby`; a second heading between that label and
 *    the form gives a screen reader two competing headings on a surface whose
 *    whole job is one offer.
 */
bhp_c411_assert(
	1 === preg_match( '#<p class="popup-ab__lede">#', $rendered ),
	'1.4: the lede is a <p>, not a heading element',
	$failures
);

/**
 * ⭐ NO WIDTH GATE ON THE MARKUP. The line is approved at BOTH widths, so it
 *    must not be emitted behind a device check — and it must not be hidden by
 *    a `display: none` in any media block either. The second half is asserted
 *    in section 3.
 */
bhp_c411_assert(
	false === strpos( $rendered, 'popup-ab__lede--desktop' )
		&& false === strpos( $rendered, 'popup-ab__lede--mobile' ),
	'1.5: the lede is ONE element for both widths, not a desktop/mobile pair',
	$failures
);

/* ⛔ CONTENT RAILS THAT APPLY TO EVERY CUSTOMER-FACING STRING. These are cheap
 *    to check and expensive to miss, and they are checked on the RENDERED
 *    string rather than on the intention. */
bhp_c411_assert(
	false === strpos( $approved_lede, "\xE2\x80\x94" ) && false === strpos( $approved_lede, "\xE2\x80\x93" ),
	'1.6: no em dash or en dash in the approved lede',
	$failures
);
bhp_c411_assert(
	0 === preg_match( '/\b(we|us|our)\b/i', $approved_lede ),
	'1.7: §9.1 — no "we / us / our" in the customer-facing lede',
	$failures
);
bhp_c411_assert(
	0 === preg_match( '/\b(colour|favourite|realise|recognise|organise|centre|travelling|labelled|grey|catalogue|towards|whilst|learnt|maths)\b/i', $approved_lede ),
	'1.8: §9.4 — American spelling in the customer-facing lede',
	$failures
);

/**
 * ⭐ THE PROVENANCE CLAIM IS SOURCED, NOT INFERRED FROM THE CSS CLASS NAME.
 *    The lede asserts the chapter is from THE MARIANA TRENCH. The kit cover's
 *    own alt text — written against the real artwork in `kit-instant-modal.php`
 *    — describes the cover as carrying the Mariana Trench book. If that ever
 *    stops being true, this line becomes a false claim to a parent, and this
 *    assertion is what catches it.
 * ⚠ THIS IS A CONSISTENCY CHECK BETWEEN TWO FILES, NOT A READ OF THE PDF.
 *    It is not evidence about the delivered document's contents.
 */
$kit_modal = bhp_c411_read( 'inc/kit-instant-modal.php' );
bhp_c411_assert(
	'' !== $kit_modal && false !== stripos( $kit_modal, 'Mariana Trench' ),
	'1.9: the lede\'s "The Mariana Trench" agrees with the kit cover\'s own description',
	$failures
);

bhp_c411_note( '"About 10 minutes" is a FOUNDER-APPROVED read-time estimate, hedged by "About". It is NOT independently measured and is not asserted as a verified figure anywhere in this suite.' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE DESKTOP TYPE STEP — 1440 and wider, and NO layout rule moves
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n-- 2. THE DESKTOP TYPE STEP (1440+) --\n";

$desktop_block = bhp_c411_at_rule_block( $style_code, '@media (min-width: 1440px)', '.popup-ab__subhead' );

bhp_c411_assert(
	'' !== $desktop_block,
	'2.1: a @media (min-width: 1440px) block exists in style.css',
	$failures
);

bhp_c411_assert(
	'' !== $desktop_block
		&& 1 === preg_match( '/\.mariana-popup--ab \.mariana-popup__dialog h2\s*\{[^}]*font-size:\s*var\(--h3-size\)/s', $desktop_block ),
	'2.2: ⭐ at 1440+ the headline steps to var(--h3-size) — one rung on the theme\'s own scale',
	$failures
);

bhp_c411_assert(
	'' !== $desktop_block
		&& 1 === preg_match( '/\.mariana-popup--ab \.popup-ab__subhead\s*\{[^}]*font-size:\s*var\(--text-base\)/s', $desktop_block ),
	'2.3: ⭐ at 1440+ the sub-line steps var(--text-sm) -> var(--text-base)',
	$failures
);

/**
 * ⛔ "NO LAYOUT CHANGE" IS AN ACCEPTANCE CRITERION, SO IT IS ASSERTED RATHER
 *    THAN PROMISED IN A COMMENT. Nothing in the 1440 block may declare a
 *    geometry property. A future pass that "just nudges the padding" here
 *    breaks this test, which is the point.
 */
bhp_c411_assert(
	'' !== $desktop_block
		&& 0 === preg_match( '/\b(width|max-width|min-width|height|max-height|padding|margin|grid-template|column-gap|row-gap|gap|display|position|inset|top|left|right|bottom)\s*:/', $desktop_block ),
	'2.4: ⛔ the 1440 block declares ONLY font-size — no layout property of any kind',
	$failures
);

/**
 * ⭐ THE STEP IS A REAL STEP. `--h3-size` must resolve larger than the
 *    dialog's own bespoke headline clamp, or the "one type step larger"
 *    criterion is cosmetic. Both tokens are read from the stylesheet rather
 *    than typed here.
 */
bhp_c411_assert(
	1 === preg_match( '/--h3-size:\s*clamp\([^;]*1\.7rem\s*\)/', $style_code ),
	'2.5: --h3-size caps at 1.7rem (27.2px at 1440) — larger than the dialog\'s 1.4rem clamp cap',
	$failures
);
bhp_c411_assert(
	1 === preg_match( '/--text-base:\s*1\.125rem/', $style_code )
		&& 1 === preg_match( '/--text-sm:\s*0\.9rem/', $style_code ),
	'2.6: --text-sm 0.9rem -> --text-base 1.125rem is a real step on the existing scale',
	$failures
);

/**
 * ⛔ THE BASE RULES ARE NOT REWRITTEN. The step is ADDITIVE, inside a media
 *    query. If a future pass "simplifies" it by editing the base clamp, every
 *    width below 1440 moves — which is precisely what the brief forbade.
 */
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--ab \.mariana-popup__dialog h2\s*\{[^}]*font-size:\s*clamp\(1\.25rem,\s*2vw,\s*1\.4rem\)/s', $style_code ),
	'2.7: ⛔ the BASE headline clamp is untouched — the step is additive, not a rewrite',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE KIT THUMBNAIL — hidden below 768, kept on desktop, still in the DOM
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n-- 3. THE KIT THUMBNAIL --\n";

$phone_block = bhp_c411_at_rule_block( $style_code, '@media (max-width: 767px)', '.popup-ab__kit-cover' );

bhp_c411_assert(
	'' !== $phone_block,
	'3.1: a @media (max-width: 767px) block exists in style.css',
	$failures
);

bhp_c411_assert(
	'' !== $phone_block
		&& 1 === preg_match( '/\.mariana-popup--ab \.popup-ab__kit-cover\s*\{[^}]*display:\s*none/s', $phone_block ),
	'3.2: ⭐ BELOW 768 the kit thumbnail is display:none',
	$failures
);

/**
 * ⛔⛔ THE MARKUP IS NOT DELETED, AND THIS IS THE MOST IMPORTANT ASSERTION IN
 *    SECTION 3. Andrew Signore's standing instruction of 2026-08-19 — *"Keep a
 *    small picture of the actual front page of the kit on the pop up"* — is
 *    narrowed by viewport under his later seal 1451, NOT overturned. The
 *    element renders at every width; only its phone PRESENTATION changed. Two
 *    other suites already assert exactly one `.popup-ab__kit-cover` renders,
 *    and they keep passing HONESTLY rather than being edited to permit an
 *    absence.
 */
bhp_c411_assert(
	1 === substr_count( $rendered, 'class="popup-ab__kit-cover"' ),
	'3.3: ⛔ the kit thumbnail is STILL IN THE DOM at every width — hidden by CSS, not removed from the template',
	$failures
);

/**
 * ⛔ NO GLOBAL HIDE. The rule must be inside a max-width query. An unscoped
 *    `display: none` would take the picture off DESKTOP too and break the
 *    founder instruction outright.
 */
$cover_hide_hits = preg_match_all( '/\.popup-ab__kit-cover[^{]*\{[^}]*display:\s*none/s', $style_code );
bhp_c411_assert(
	1 === $cover_hide_hits,
	'3.4: ⛔ exactly ONE display:none rule targets the kit cover, and section 3.2 proved it is inside the phone query',
	$failures
);

/**
 * ⭐ IT IS KEPT ON DESKTOP, ASSERTED FROM THE OTHER END. The desktop sizing
 *    rules survive, so the picture still has a declared render at 1440.
 */
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--ab \.popup-ab__kit-cover img\s*\{[^}]*width:\s*160px/s', $style_code ),
	'3.5: the desktop kit-cover rule survives — the picture is KEPT above 768',
	$failures
);
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--photo \.popup-ab__kit-cover img\s*\{\s*width:\s*108px/s', $style_code ),
	'3.6: the 700+ two-column kit-cover step-down rule is untouched',
	$failures
);

/**
 * ⭐ THE LEDE IS NEVER HIDDEN. It is approved at BOTH widths, so no media
 *    block anywhere may take it away.
 */
bhp_c411_assert(
	0 === preg_match( '/\.popup-ab__lede[^{]*\{[^}]*display:\s*none/s', $style_code ),
	'3.7: ⭐ NO rule anywhere hides the lede — it renders at both widths',
	$failures
);

/**
 * ⛔ THE SUB-LINE'S ORIGINAL NEGATIVE TOP MARGIN IS NOT EDITED. It was written
 *    to tuck under the `h2`; with a lede between them it is overridden ONLY by
 *    an adjacent-sibling rule, so filtering the lede to '' restores the old
 *    spacing with nothing to unwind.
 */
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--ab \.popup-ab__lede \+ \.popup-ab__subhead\s*\{[^}]*margin-top/s', $style_code ),
	'3.8: the sub-line spacing is fixed by an adjacent-sibling override, not by editing its base rule',
	$failures
);
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--ab \.popup-ab__subhead\s*\{[^}]*margin:\s*calc\(-1 \* var\(--space-2\)\)/s', $style_code ),
	'3.9: the sub-line\'s ORIGINAL margin rule is byte-intact',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · EVERYTHING ELSE IS BYTE-UNCHANGED
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ The brief said "everything else byte-unchanged". That is asserted, not
 *    asserted-in-a-comment.
 */

echo "\n-- 4. WHAT MUST NOT HAVE MOVED --\n";

/**
 * ⛔ ASSERTED AGAINST THE ESCAPED FORM, WHICH IS WHAT ACTUALLY RENDERS. The
 *    sub-line passes through `esc_html()`, so its apostrophe reaches the page
 *    as `&#039;`. This suite's first run asserted the raw string and FAILED on
 *    correct output — a test bug, not a copy regression. `esc_html()` is
 *    applied here so the assertion tracks the template's own escaping instead
 *    of hard-coding one entity spelling.
 */
$existing_subhead = "I'll send you the chapter now, just add your email.";
bhp_c411_assert(
	false !== strpos( $rendered, esc_html( $existing_subhead ) ),
	'4.1: the existing sub-line copy is unchanged',
	$failures
);
/**
 * ⛔ THE HEADLINE IS NOT A PLAIN STRING IN THE MARKUP AND MUST NOT BE ASSERTED
 *    AS ONE. `bhp_popup_ab_emphasise_free()` wraps the standalone token FREE in
 *    a span — Andrew Signore's standing 1.19.207 order that the word read ALL
 *    CAPS, bold and larger. So the rendered headline is the span plus the rest
 *    of the sentence. This suite's first run asserted the unwrapped string and
 *    FAILED on correct output; asserting the wrapper as well is strictly
 *    STRONGER, because it now also proves the FREE treatment survived 411.
 */
bhp_c411_assert(
	1 === preg_match(
		'#<h2 id="parent-ab-popup-title"><span class="popup-ab__free">FREE</span> Chapter for Reluctant Readers</h2>#',
		$rendered
	),
	'4.2: the headline copy is unchanged, INCLUDING the 1.19.296 FREE emphasis span',
	$failures
);
bhp_c411_assert(
	false !== strpos( $rendered, 'Send me the chapter' ),
	'4.3: the button text is unchanged',
	$failures
);
bhp_c411_assert(
	false !== strpos( $rendered, 'No spam. Unsubscribe anytime.' ),
	'4.4: the footer privacy line is unchanged',
	$failures
);
bhp_c411_assert(
	false !== strpos( $rendered, 'First name' ) && false !== strpos( $rendered, 'Email address' ),
	'4.5: the two field placeholders are unchanged',
	$failures
);
bhp_c411_assert(
	false !== strpos( $rendered, '<figure class="popup-ab__photo">' ),
	'4.6: the founder photograph still renders',
	$failures
);

/**
 * ⭐ THE 1.19.410 CLOSE-CONTROL FIX IS STILL THERE. It fixed a defect that was
 *    LIVE ON PRODUCTION; a regression here would be worse than anything 411
 *    adds.
 */
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--ab \.mariana-popup__close\s*\{[^}]*z-index:\s*2/s', $style_code ),
	'4.7: ⛔ the 1.19.410 close-control z-index fix survives',
	$failures
);
bhp_c411_assert(
	1 === preg_match( '/\.mariana-popup--photo\.mariana-popup--ab \.mariana-popup__close\s*\{/s', $style_code ),
	'4.8: the 1.19.410 opaque disc + ring rule survives',
	$failures
);

/**
 * ⛔ THE TEACHER POPUP IS OUT OF SCOPE AND MUST NOT HAVE BEEN TOUCHED. Every
 *    rule this build added is scoped to `--ab`; none may reach the shared
 *    popup block unscoped.
 */
$block_9f = '';
if ( preg_match( '/9f · 1\.19\.411.*?(?=\n\/\* ═|\z)/s', $style_raw, $m9f ) ) {
	$block_9f = bhp_c411_css_code_only( $m9f[0] );
}
bhp_c411_assert(
	'' !== $block_9f,
	'4.9: the 1.19.411 stylesheet section is locatable',
	$failures
);
bhp_c411_assert(
	'' !== $block_9f
		&& 0 === preg_match( '/(?<!--ab )(?<!--photo\.mariana-popup--ab )\.mariana-popup__(dialog|close|overlay|form)\b/', $block_9f ),
	'4.10: ⛔ every reference to the SHARED popup block in section 9f is --ab scoped — the teacher, timed and exit-intent popups cannot be reached',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · VERSIONS
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n-- 5. VERSIONS --\n";

bhp_c411_assert( 1 === preg_match( '/^Version:\s*1\.19\.411\s*$/m', $style_raw ), '5.1: theme style.css declares 1.19.411', $failures );
bhp_c411_assert( '1.19.411' === wp_get_theme()->get( 'Version' ), '5.2: LIVE — the RUNNING theme reports 1.19.411', $failures );

$c411_stamp = '';
if ( preg_match( '/source-md5:\s*([0-9a-f]{32})/', $min_raw, $c411_m ) ) {
	$c411_stamp = $c411_m[1];
}
bhp_c411_assert( '' !== $c411_stamp && $c411_stamp === md5( $style_raw ), '5.3: style.min.css source-md5 matches the shipped style.css — the built artefact is current', $failures );

/**
 * ⭐ THE THREE CHANGES ARE IN THE MINIFIED ARTEFACT TOO. The site serves
 *    `style.min.css`, not `style.css`. A change present only in the source is
 *    a change the visitor never sees — which is the exact class of defect the
 *    source-md5 stamp above exists for, checked here from the other end.
 */
bhp_c411_assert( false !== strpos( $min_code, '.popup-ab__lede' ), '5.4: the lede rule is in the SHIPPED style.min.css', $failures );
bhp_c411_assert( false !== strpos( $min_code, '@media (min-width: 1440px)' ), '5.5: the 1440 step is in the SHIPPED style.min.css', $failures );
bhp_c411_assert( false !== strpos( $min_code, '@media (max-width: 767px)' ), '5.6: the phone thumbnail rule is in the SHIPPED style.min.css', $failures );

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
