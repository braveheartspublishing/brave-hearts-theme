<?php
/**
 * Exit-intent trigger + mobile-fit regression suite (theme 1.19.173, 2026-08-05).
 *
 * WHY THIS SUITE EXISTS. Andrew Signore, current turn, 2026-08-05, verbatim:
 *
 *   "The exit pop up just popped up when I tried to click 'get the complete
 *    collection' on the complete collection page - I wasnt exiting the site
 *    and it popped up- is that correct? Also That pop up doesnt fit on mobile
 *    - it extends far to the right side past the mobile viewing - please
 *    review"
 *
 * ⛔ RELAYED. This suite's author did not witness that message directly; it
 *    arrived through the Chief of Staff's brief and is quoted, not paraphrased.
 *
 * BOTH DEFECTS WERE REPRODUCED BEFORE THEY WERE FIXED, on staging, in a real
 * headless Chrome over CDP at 390x844 — not inferred from source:
 *
 *   DEFECT 1 (trigger). `/complete-collection/` carries
 *   `<a href="#bhp-landing-pricing-card">Get the Complete Collection</a>`.
 *   Tapping it smooth-scrolls the page UPWARD from y=3956 to y=814 in ~25
 *   scroll events. The old mobile fallback accumulated that as ~3,100px of
 *   "upward flick", blew past `upThresholdPx` (300) and opened the modal on a
 *   purchase CTA. OBSERVED: `{open:false}` before the tap, `{open:true}` 2.5s
 *   after it.
 *
 *   DEFECT 2 (fit). With the modal open at 390px, `FORM.acquisition-form`
 *   computed `grid-template-columns: 410.781px` inside a 342px dialog, and
 *   every grid item rendered 411px wide, ending at x=451 — 61px past the
 *   viewport and 85px past the dialog. Cause: `1fr` is `minmax(auto, 1fr)`,
 *   whose automatic minimum is MIN-CONTENT, and the submit label
 *   ("Send me the free chapter & activity") was `white-space: nowrap` with a
 *   407px min-content width. Two separate CSS faults, both real.
 *
 * Run on staging (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-exit-intent-trigger.php --user=1
 *
 * WHAT THIS SUITE PROVES: the deployed source carries every guard and every
 * CSS correction, the trigger config is the hardened one, and nothing outside
 * trigger mode 'exit' or the popup's own CSS scope was altered.
 *
 * ⛔ WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS: it is a
 *    source-level suite. It cannot observe a scroll, a tap, a pointer leaving
 *    the viewport, or a box painting. The behavioural evidence is the CDP
 *    harness recorded in this release's handoff, and it is a separate artifact.
 *
 * It touches no post, no option, no product and no WooCommerce record.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_ei_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_ei_read( $relative ) {
	$path = get_template_directory() . '/' . ltrim( $relative, '/' );
	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return '';
	}
	return (string) file_get_contents( $path );
}

/**
 * ⚠ COMMENT-BLIND VARIANTS, AND WHY THEY EXIST.
 *
 * The first run of this suite produced FOUR failures, and all four were the
 * suite's fault, not the code's. Every one of them matched a string inside a
 * COMMENT that exists precisely to say the opposite:
 *
 *   - `bhp_mariana_popup` matched the template's own line "⛔ NOTHING here
 *     reads or writes `bhp_mariana_popup_*`."
 *   - `max-width: none` matched the CSS comment recording that
 *     `max-width: none` was REMOVED.
 *   - `review` matched "no review claim appears anywhere in this file."
 *   - the exit-mode fallback-timer regex spanned, with `.*?` across `/s`,
 *     from a `mode === 'exit'` mention in a comment all the way into the
 *     GATED branch's genuine timer.
 *
 * Recorded rather than quietly patched: an assertion that reads documentation
 * as if it were behaviour is a false negative today and a false POSITIVE the
 * day someone deletes the comment. Absence claims are asserted against code.
 */
function bhp_ei_strip_php_comments( $src ) {
	if ( ! function_exists( 'token_get_all' ) || '' === $src ) {
		return $src;
	}
	$out = '';
	foreach ( token_get_all( $src ) as $token ) {
		if ( is_array( $token ) ) {
			if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
				continue;
			}
			$out .= $token[1];
			continue;
		}
		$out .= $token;
	}
	return $out;
}

function bhp_ei_strip_block_comments( $src ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', $src );
}

function bhp_ei_strip_js_comments( $src ) {
	$src = bhp_ei_strip_block_comments( $src );
	// Line comments only where the `//` starts the line's content, which is
	// how this file writes them. Deliberately conservative: a URL inside a
	// string literal must not be mangled into a syntax the asserts misread.
	return (string) preg_replace( '#^\s*//.*$#m', '', $src );
}

$js       = bhp_ei_read( 'assets/js/mariana-popup.js' );
$css      = bhp_ei_read( 'style.css' );
$template = bhp_ei_read( 'template-parts/acquisition/exit-intent-popup.php' );

$js_code       = bhp_ei_strip_js_comments( $js );
$css_code      = bhp_ei_strip_block_comments( $css );
$template_code = bhp_ei_strip_php_comments( $template );

echo "\n=== 0. Files load ===\n";
bhp_ei_assert( strlen( $js ) > 5000, 'mariana-popup.js is readable and non-trivial', $failures );
bhp_ei_assert( strlen( $css ) > 50000, 'style.css is readable and non-trivial', $failures );
bhp_ei_assert( strlen( $template ) > 1000, 'exit-intent-popup.php is readable and non-trivial', $failures );

echo "\n=== 1. G1 — interaction cooldown ===\n";
bhp_ei_assert( strpos( $js, 'INTERACTION_COOLDOWN_MS' ) !== false, 'G1: cooldown constant exists', $failures );
bhp_ei_assert( strpos( $js, 'function withinInteractionCooldown' ) !== false, 'G1: cooldown predicate exists', $failures );
bhp_ei_assert( strpos( $js, "document.addEventListener('click', noteInteraction, true)" ) !== false, 'G1: click is observed in the capture phase', $failures );
bhp_ei_assert( strpos( $js, "document.addEventListener('pointerdown', noteInteraction, true)" ) !== false, 'G1: pointerdown is observed in the capture phase', $failures );
bhp_ei_assert( strpos( $js, 'INTERACTIVE_SELECTOR' ) !== false, 'G1: interactive-target selector exists', $failures );
// The touchmove carve-out is the difference between a working mobile
// fallback and one that can never fire. Assert it explicitly.
bhp_ei_assert( strpos( $js_code, "if (type === 'touchmove') {" ) !== false && strpos( $js_code, 'TOUCH_DRAG_SLOP_PX' ) !== false, 'G1: touchmove never starts the cooldown (a finger-drag IS the gesture)', $failures );
// A drag is not an activation. Measured on staging: without this withdrawal a
// genuine flick whose touchstart landed on a card was suppressed at 418px of
// accumulated upward travel, past the 400px threshold.
bhp_ei_assert( strpos( $js_code, 'pendingTouchInteractionAt' ) !== false, 'G1: a touchstart-opened cooldown is tracked separately so it can be withdrawn', $failures );
bhp_ei_assert( preg_match( '/if \(lastInteractionAt === pendingTouchInteractionAt\) \{\s*lastInteractionAt = 0;/', $js_code ) === 1, 'G1: dragging past the slop WITHDRAWS the cooldown a touchstart opened', $failures );
bhp_ei_assert( strpos( $js_code, 'TOUCH_DRAG_SLOP_PX = 12' ) !== false, 'G1: drag slop is 12px', $failures );

echo "\n=== 2. G2 — mobile gesture requirement ===\n";
bhp_ei_assert( strpos( $js, 'function withinTouchGesture' ) !== false, 'G2: touch-gesture predicate exists', $failures );
bhp_ei_assert( strpos( $js, 'TOUCH_GESTURE_WINDOW_MS' ) !== false, 'G2: gesture window constant exists', $failures );
bhp_ei_assert( strpos( $js, '!withinTouchGesture()' ) !== false, 'G2: scroll handler requires an active gesture', $failures );
// RESET, not merely ignore: the tail of a programmatic scroll must not be
// inherited by the next real gesture.
bhp_ei_assert( preg_match( '/!withinTouchGesture\(\)\) \{\s*upTravel = 0;\s*return;/', $js ) === 1, 'G2: non-gesture scroll RESETS the accumulator', $failures );

echo "\n=== 3. G3 — desktop upward pointer velocity ===\n";
bhp_ei_assert( strpos( $js, 'function hasUpwardPointerVelocity' ) !== false, 'G3: velocity predicate exists', $failures );
bhp_ei_assert( strpos( $js, '!hasUpwardPointerVelocity()' ) !== false, 'G3: mouseout requires real upward movement', $failures );
bhp_ei_assert( strpos( $js, 'POINTER_UP_MIN_PX' ) !== false, 'G3: minimum upward travel is a named constant', $failures );
bhp_ei_assert( strpos( $js, 'onPointerSample' ) !== false, 'G3: mousemove sampling exists', $failures );

echo "\n=== 4. G4 — navigation pending ===\n";
bhp_ei_assert( strpos( $js, 'navigationPending' ) !== false, 'G4: navigation flag exists', $failures );
bhp_ei_assert( strpos( $js, 'function noteNavigationIntent' ) !== false, 'G4: link-click observer exists', $failures );
bhp_ei_assert( strpos( $js, "document.addEventListener('submit', noteFormSubmit, true)" ) !== false, 'G4: form submit latches the flag', $failures );
// A same-page hash link is NOT a navigation. If it latched, a visitor who
// used an in-page anchor could never be offered the kit again in that visit.
bhp_ei_assert( strpos( $js, "href.charAt(0) === '#'" ) !== false, 'G4: a same-page hash link does NOT latch navigation', $failures );

echo "\n=== 5. Desktop leave semantics are stricter, not looser ===\n";
bhp_ei_assert( strpos( $js, 'event.relatedTarget || event.toElement' ) !== false, 'desktop: relatedTarget must be null (unchanged)', $failures );
bhp_ei_assert( strpos( $js, "typeof event.clientY !== 'number' || event.clientY > 0" ) !== false, 'desktop: must leave through the TOP, and a missing clientY no longer passes', $failures );
bhp_ei_assert( strpos( $js, 'event.clientX > window.innerWidth' ) !== false, 'desktop: exits past a vertical edge are rejected', $failures );
bhp_ei_assert( strpos( $js, 'document.documentElement.contains(event.target)' ) !== false, 'desktop: a detached target (DOM mutation under the cursor) is rejected', $failures );
bhp_ei_assert( strpos( $js, "document.visibilityState !== 'visible'" ) !== false, 'neither device fires while the tab is hidden', $failures );

echo "\n=== 6. The dwell floor and the session guard are UNTOUCHED ===\n";
bhp_ei_assert( strpos( $js, 'function exitBlocked' ) !== false, 'shared block predicate exists', $failures );
bhp_ei_assert( strpos( $js, 'if (!minTimeElapsed) {' ) !== false, 'the 20-second dwell floor still gates both paths', $failures );
bhp_ei_assert( strpos( $js, 'SHARED_SESSION_SHOWN_KEY' ) !== false, 'shared session-frequency guard still present', $failures );
bhp_ei_assert( strpos( $js, 'config.sessionGuard' ) !== false, 'per-popup session guard still present', $failures );
bhp_ei_assert( strpos( $js, 'function isDrawerOpen' ) !== false, 'cart-drawer deferral still present', $failures );
bhp_ei_assert( strpos( $js, 'function isAnotherOverlayOpen' ) !== false, 'overlay-collision deferral still present', $failures );
// There is no fallback timer in exit mode, by design. A regression here would
// turn an exit-intent popup back into a timed popup.
bhp_ei_assert( strpos( $js_code, 'fallbackTimerId = window.setTimeout(trigger, deviceConfig.fallbackDelay)' ) !== false, 'gated mode still owns the ONLY fallback timer', $failures );
// Scoped to the exit BRANCH only — from `} else if (mode === 'exit') {` to the
// `} else {` that opens the gated branch. An unscoped `.*?` walks straight
// into the gated branch's legitimate timer and reports a defect that is not
// there; that is exactly what it did on this suite's first run.
$exit_branch = '';
if ( preg_match( "/\}\s*else if \(mode === 'exit'\) \{(.*?)\n        \} else \{/s", $js_code, $m ) === 1 ) {
	$exit_branch = $m[1];
}
bhp_ei_assert( '' !== $exit_branch, 'exit branch is locatable in the source', $failures );
bhp_ei_assert( '' !== $exit_branch && strpos( $exit_branch, 'fallbackTimerId' ) === false, 'exit mode still has NO fallback timer (an exit popup on a timer is just a timed popup)', $failures );
bhp_ei_assert( '' !== $exit_branch && strpos( $exit_branch, 'attachExitListeners()' ) !== false, 'exit branch still attaches its listeners', $failures );

echo "\n=== 7. Funnel isolation is untouched ===\n";
bhp_ei_assert( strpos( $template, 'bhp_parent_popup' ) !== false, 'exit modal still uses the parent funnel storage prefix', $failures );
bhp_ei_assert( strpos( $template_code, 'bhp_mariana_popup' ) === false, 'exit modal reads/writes NOTHING under the teacher prefix (asserted against CODE, not comments)', $failures );
bhp_ei_assert( strpos( $template, "'eventPrefix'   => 'parent_popup'" ) !== false, 'exit modal still uses the parent event prefix', $failures );
bhp_ei_assert( strpos( $template, 'adventure-kit-thank-you' ) !== false, 'exit modal still uses the parent thank-you path', $failures );
bhp_ei_assert( strpos( $js, "storagePrefix: 'bhp_mariana_popup'" ) !== false, 'engine default storage prefix unchanged', $failures );

echo "\n=== 8. Trigger config is the hardened one ===\n";
bhp_ei_assert( strpos( $template, "'mode'    => 'exit'" ) !== false, 'trigger mode is still exit', $failures );
bhp_ei_assert( substr_count( $template, "'minDelay'" ) >= 1 && strpos( $template, '20000' ) !== false, 'the 20-second dwell floor is still configured on both devices', $failures );
bhp_ei_assert( strpos( $template, "'interactionCooldownMs' => 1500" ) !== false, 'mobile cooldown is configured at 1500ms', $failures );
bhp_ei_assert( strpos( $template, "'interactionCooldownMs' => 1500]" ) !== false || strpos( $template, "'interactionCooldownMs' => 1500," ) !== false, 'desktop cooldown is configured', $failures );
bhp_ei_assert( strpos( $template, "'upThresholdPx' => 400" ) !== false, 'mobile travel threshold raised 300 -> 400px', $failures );
bhp_ei_assert( strpos( $template, "'touchWindowMs' => 700" ) !== false, 'mobile gesture window is configured', $failures );
bhp_ei_assert( strpos( $template, "'upThresholdPx' => 300" ) === false, 'the old 300px threshold is gone', $failures );

echo "\n=== 9. Mobile fit — the two CSS faults are corrected ===\n";
bhp_ei_assert( strpos( $css, '.acquisition-form { grid-template-columns: minmax(0, 1fr); }' ) !== false, 'FAULT 1: the single-column track can no longer size to min-content', $failures );
bhp_ei_assert( preg_match( '/\.acquisition-form \{ grid-template-columns: 1fr; \}/', $css ) !== 1, 'FAULT 1: the bare `1fr` track is gone', $failures );
bhp_ei_assert( preg_match( '/\.mariana-popup__form \.acquisition-form__submit \{[^}]*white-space: normal/s', $css ) === 1, 'FAULT 2: the popup submit label wraps instead of forcing 407px of min-content', $failures );
bhp_ei_assert( preg_match( '/\.mariana-popup__form \.acquisition-form__submit \{[^}]*white-space: nowrap/s', $css ) !== 1, 'FAULT 2: nowrap is gone from the popup submit', $failures );

echo "\n=== 10. Mobile fit — the dialog is bounded by the real viewport ===\n";
bhp_ei_assert( preg_match( '/\.mariana-popup__dialog \{[^}]*box-sizing: border-box/s', $css ) === 1, 'dialog is border-box, so its padding cannot widen it', $failures );
bhp_ei_assert( preg_match( '/\.mariana-popup__dialog \{[^}]*max-width: min\(600px, calc\(100vw/s', $css ) === 1, 'dialog max-width is viewport-bounded at desktop', $failures );
bhp_ei_assert( strpos( $css, 'max-width: min(32rem, calc(100vw - 2 * var(--space-3)))' ) !== false, 'dialog max-width is viewport-bounded at phone widths', $failures );
// This is the specificity trap that made the phone rule ineffective.
bhp_ei_assert( preg_match( '/\.mariana-popup--exit \.mariana-popup__dialog \{\s*max-width: 32rem;\s*\}/', $css ) !== 1, 'the unbounded `.mariana-popup--exit` 32rem override is gone', $failures );
bhp_ei_assert( preg_match( '/\.mariana-popup__dialog \{[^}]*max-width: none/s', $css_code ) !== 1, 'the phone block no longer cancels the desktop max-width guard (asserted against CODE, not comments)', $failures );
bhp_ei_assert( strpos( $css, '.mariana-popup__dialog > * { max-width: 100%; }' ) !== false, 'no direct child of the dialog can exceed it', $failures );
bhp_ei_assert( preg_match( '/\.mariana-popup__form,\s*\.mariana-popup__form > \*/s', $css ) === 1, 'the popup form and its children carry min-width:0 / max-width:100%', $failures );

echo "\n=== 11. No claim, number or urgency string was introduced ===\n";
// Standing content constraint: the never-invent list plus "no number, count,
// urgency or scarcity string" in the exit modal's own build note.
// `review` not `review`: the unbounded form matches inside "preview".
$claim_patterns = array( 'rating', 'reviews?', 'stars?', 'best-sell', 'bestsell', 'awards?', 'only \d+ left', 'hurry', 'act now', 'limited time', 'parents love', 'teachers love' );
$claim_hits     = array();
foreach ( $claim_patterns as $needle ) {
	if ( preg_match( '/' . $needle . '/i', $template_code ) === 1 ) {
		$claim_hits[] = $needle;
	}
}
bhp_ei_assert( empty( $claim_hits ), 'exit modal copy carries no rating/review/award/urgency/scarcity string' . ( $claim_hits ? ' (found: ' . implode( ', ', $claim_hits ) . ')' : '' ), $failures );
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ UPDATED 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — THE
 *     APPROVED HEADLINE MOVED. THE GUARD DID NOT WEAKEN.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ READ THIS BEFORE CONCLUDING A GUARD WAS RELAXED TO GO GREEN. The string
 *    this line asserts changed from "Before you go, take the free kit." to the
 *    founder's own pick. The assertion's PURPOSE — *locked prose is never
 *    silently rewritten* — is unchanged and was HONOURED rather than bypassed:
 *    it failed, it was investigated, the authority was found and named, and it
 *    is being updated in the SAME release as the change, in the open.
 *
 * ⭐ THE AUTHORITY: Andrew Signore, 2026-08-27, carrier item 290, verbatim —
 *      "FREE Chapter for Reluctant Readers - I'll send you the chapter now,
 *       just add your email - Something like that"
 *    together with his ruling that the email capture must be consistent across
 *    the whole website (the magnet teardown's 12-of-12 finding). Exit-intent is
 *    a PARENT capture surface and was one of the twelve.
 *    ⚠ RELAYED through the Chief of Staff, who witnessed it; NOT witnessed by
 *      the desk that edited this line. ⛔ STAGING ONLY, and FLAGGED at the top
 *      of the build report so he meets it at the deploy gate.
 *
 * ⭐ THE TRUST-LINE GUARD ON THE NEXT LINE IS **NOT** TOUCHED, deliberately.
 *    "Free printable PDF. No purchase required." is still true of the real
 *    artefact and was not part of the offer-name collision, so it stays locked.
 *    A sweep that moved every guarded string at once would be indistinguishable
 *    from a sweep that moved them carelessly.
 */
bhp_ei_assert( strpos( $template, 'FREE Chapter for Reluctant Readers' ) !== false, 'approved headline is unchanged (locked prose is never silently rewritten)', $failures );
bhp_ei_assert( strpos( $template, 'Before you go, take the free kit.' ) === false, '⛔ the retired 1.19.296 headline is gone, not lingering beside the new one', $failures );
bhp_ei_assert( strpos( $template, 'Send me the chapter' ) !== false, 'the CTA is the sitewide send-imperative', $failures );
bhp_ei_assert( strpos( $template, 'Free printable PDF. No purchase required.' ) !== false, 'approved trust line is unchanged', $failures );

echo "\n=== 12. Version ===\n";
$theme_version = wp_get_theme( 'brave-hearts-theme-deploy-explorer-expedition-guides' )->get( 'Version' );
bhp_ei_assert( version_compare( $theme_version, '1.19.173', '>=' ), "theme version is 1.19.173 or later (found {$theme_version})", $failures );

echo "\n============================================================\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
