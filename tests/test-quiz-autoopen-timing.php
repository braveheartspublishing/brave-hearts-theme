<?php
/**
 * Quiz auto-open dwell-floor regression suite (theme 1.19.167, 2026-08-04).
 *
 * Owner ruling, Andrew Signore, 2026-08-04: the sitewide quiz modal
 * "needs more time for people to peruse the site" -- "20 seconds please".
 * This suite exists so that ruling cannot be silently regressed by a later
 * edit to assets/js/quiz-modal.js.
 *
 * Run on staging (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-quiz-autoopen-timing.php --user=1
 *
 * WHAT THIS SUITE PROVES, precisely:
 *   It reads the DEPLOYED assets/js/quiz-modal.js from the active theme
 *   directory on the environment it is run against, and asserts the
 *   structure that makes the 20-second floor true -- the two constants, the
 *   single floor gate every automatic open passes through, the absence of
 *   any surviving pre-floor open path, and the behaviours that had to
 *   survive the change (session key, overlay-pending retry, pagehide
 *   cleanup, analytics events, funnel isolation).
 *
 * WHAT IT DOES NOT PROVE, stated plainly so no one over-reads a PASS:
 *   It is a source-level assertion suite, not a browser. It cannot observe
 *   a real 20-second wall-clock wait, a real scroll, or a real modal
 *   painting. Runtime timing evidence must come from a real browser on
 *   staging, or from the Node behavioural harness recorded in the release
 *   handoff. A PASS here means "the deployed file still has the shape that
 *   enforces the floor", nothing more.
 *
 * It touches no post, no option, no product and no WooCommerce record, and
 * writes nothing anywhere. It is read-only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_quiz_timing_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$js_path = get_template_directory() . '/assets/js/quiz-modal.js';

if ( ! file_exists( $js_path ) || ! is_readable( $js_path ) ) {
	bhp_quiz_timing_assert( false, "assets/js/quiz-modal.js must exist and be readable at {$js_path}", $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

$js = file_get_contents( $js_path );
bhp_quiz_timing_assert( '' !== trim( (string) $js ), 'quiz-modal.js is non-empty', $failures );

// Normalise line endings -- the deploy artefact carries CRLF (git archive on
// Windows), the repo copy carries LF. Every assertion below must hold for
// both, so no test may depend on a particular newline.
$js = str_replace( "\r\n", "\n", (string) $js );

// ==================== 1. THE TWO NUMBERS ====================

bhp_quiz_timing_assert(
	1 === preg_match( '/var\s+AUTO_OPEN_DELAY_MS\s*=\s*20000\s*;/', $js ),
	'AUTO_OPEN_DELAY_MS is exactly 20000 (Andrew Signore, 2026-08-04: "20 seconds please")',
	$failures
);

bhp_quiz_timing_assert(
	0 === preg_match( '/var\s+AUTO_OPEN_DELAY_MS\s*=\s*(?!20000)\d+\s*;/', $js ),
	'No other AUTO_OPEN_DELAY_MS value survives (8000/9000 are gone)',
	$failures
);

bhp_quiz_timing_assert(
	1 === preg_match( '/var\s+SCROLL_THRESHOLD\s*=\s*0\.60?\s*;/', $js ),
	'SCROLL_THRESHOLD is 0.60',
	$failures
);

bhp_quiz_timing_assert(
	0 === preg_match( '/var\s+SCROLL_THRESHOLD\s*=\s*0\.40?\s*;/', $js ),
	'The old 0.40 scroll threshold is gone',
	$failures
);

// ==================== 2. THE FLOOR GATE ====================
// The floor is enforced by construction: dwellFloorReached is written true
// in exactly one place, and evaluateScrollTrigger() returns early whenever
// it is false. Both facts are asserted, because either one alone can be
// true while the floor leaks.

bhp_quiz_timing_assert(
	false !== strpos( $js, 'var dwellFloorReached = false;' ),
	'dwellFloorReached is declared per launcher instance and starts false',
	$failures
);

bhp_quiz_timing_assert(
	1 === preg_match_all( '/dwellFloorReached\s*=\s*true/', $js ),
	'dwellFloorReached is set true in EXACTLY ONE place (the floor timer callback)',
	$failures
);

// Isolate evaluateScrollTrigger()'s body and prove the gate precedes the fire.
$eval_body = '';
if ( preg_match( '/function evaluateScrollTrigger\(\)\s*\{(.*?)\n    \}/s', $js, $m ) ) {
	$eval_body = $m[1];
}
bhp_quiz_timing_assert( '' !== $eval_body, 'evaluateScrollTrigger() body is locatable for inspection', $failures );

$gate_pos = strpos( $eval_body, 'if (!dwellFloorReached)' );
$fire_pos = strpos( $eval_body, 'fireAutoTrigger' );
bhp_quiz_timing_assert(
	false !== $gate_pos,
	'evaluateScrollTrigger() contains the !dwellFloorReached gate',
	$failures
);
bhp_quiz_timing_assert(
	false !== $gate_pos && false !== $fire_pos && $gate_pos < $fire_pos,
	'The floor gate appears BEFORE the only fireAutoTrigger() call in evaluateScrollTrigger()',
	$failures
);
bhp_quiz_timing_assert(
	1 === substr_count( $eval_body, 'fireAutoTrigger' ),
	'evaluateScrollTrigger() has exactly one fireAutoTrigger() call site',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $eval_body, 'scrollIntentPending = true' ),
	'Crossing the threshold before the floor records intent instead of opening',
	$failures
);

// The arm-time one-shot evaluation is the path that used to let a short page
// open near-instantly. It must still be called (so intent is captured) and it
// must go through the same gated function.
bhp_quiz_timing_assert(
	false !== strpos( $js, 'evaluateScrollTrigger();' ),
	'The arm-time one-shot evaluation is still called (it now only records intent)',
	$failures
);
bhp_quiz_timing_assert(
	0 === preg_match( '/armAutoTrigger[\s\S]{0,2000}?fireAutoTrigger\(\s*[\'"]scroll/', $js ),
	'armAutoTrigger() never fires a scroll trigger directly at arm time',
	$failures
);

// The floor timer is the only setTimeout that can release the gate, and it
// must be armed with AUTO_OPEN_DELAY_MS, not a literal.
bhp_quiz_timing_assert(
	1 === preg_match( '/setTimeout\(function \(\)\s*\{[^}]*dwellFloorReached\s*=\s*true;[\s\S]*?\},\s*AUTO_OPEN_DELAY_MS\);/', $js ),
	'The floor timer is armed with AUTO_OPEN_DELAY_MS and sets dwellFloorReached',
	$failures
);

// ==================== 3. HONEST ANALYTICS ====================

// The retired reason name is deliberately still DESCRIBED in the file's
// header comment (that is how a future reader learns what changed), so the
// "it is gone" assertion is made against executable code only. Comments are
// stripped first; a comment mentioning the old name must never fail a build.
$js_code = preg_replace( '#/\*.*?\*/#s', '', $js );
$js_code = preg_replace( '#(^|\n)\s*//[^\n]*#', '$1', (string) $js_code );

bhp_quiz_timing_assert(
	false === strpos( (string) $js_code, 'scroll_40' ),
	'The stale open_reason/cancel_reason string "scroll_40" no longer appears in executable code',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, "'scroll_60_after_floor'" ),
	'The honest replacement reason "scroll_60_after_floor" is emitted',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, "'quiz_auto_trigger_scroll_intent'" ),
	'quiz_auto_trigger_scroll_intent is emitted when 60% is reached under the floor',
	$failures
);

foreach ( array( 'quiz_auto_trigger_armed', 'quiz_auto_trigger_cancelled', 'quiz_modal_opened', 'quiz_modal_closed' ) as $event ) {
	bhp_quiz_timing_assert(
		false !== strpos( $js, "'{$event}'" ),
		"PRESERVED analytics event: {$event}",
		$failures
	);
}

bhp_quiz_timing_assert(
	1 === preg_match( '/quiz_auto_trigger_armed[\s\S]{0,200}dwell_floor_ms:\s*AUTO_OPEN_DELAY_MS/', $js ),
	'quiz_auto_trigger_armed reports the active dwell_floor_ms, so the floor is observable in analytics',
	$failures
);

// ==================== 4. WHAT HAD TO SURVIVE ====================

bhp_quiz_timing_assert(
	false !== strpos( $js, "var AUTO_SHOWN_KEY = 'bhp_quiz_auto_shown';" ),
	'PRESERVED: the once-per-session sessionStorage key is unchanged (bhp_quiz_auto_shown)',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, 'watchForOverlayClear' ) && false !== strpos( $js, 'OVERLAY_POLL_MS' ),
	'PRESERVED: the overlay-pending retry (MutationObserver + slow poll) is intact',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, "addEventListener('pagehide'" ),
	'PRESERVED: pagehide cleanup is intact',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, 'hasVisibleConsentUI' ) && false !== strpos( $js, 'wpconsent-container' ),
	'PRESERVED: the WPConsent shadow-root overlay detection is intact',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, 'scrollToInstant' ),
	'PRESERVED: the on-close scroll-position restoration is intact',
	$failures
);
bhp_quiz_timing_assert(
	false !== strpos( $js, "openModal('manual')" ),
	'PRESERVED: a manual launcher click still opens immediately (the floor is for AUTOMATIC opens only)',
	$failures
);

// ==================== 5. FUNNEL ISOLATION (.claude/rules/funnels.md) ====================
// This file must not touch either lead-magnet funnel's storage or analytics
// namespace. It is sessionStorage-only and owns exactly one key.

bhp_quiz_timing_assert(
	false === strpos( $js, 'localStorage' ),
	'FUNNEL ISOLATION: quiz-modal.js reads and writes no localStorage at all',
	$failures
);
foreach ( array( 'bhp_parent_popup', 'bhp_mariana_popup', 'parent_popup', 'teacher_popup' ) as $foreign ) {
	bhp_quiz_timing_assert(
		false === strpos( $js, $foreign ),
		"FUNNEL ISOLATION: no reference to the funnel namespace '{$foreign}'",
		$failures
	);
}

// ==================== 6. THE VERSION THAT SHIPS IT ====================

$theme_version = wp_get_theme( get_template() )->get( 'Version' );
bhp_quiz_timing_assert(
	version_compare( $theme_version, '1.19.167', '>=' ),
	"Active theme version is >= 1.19.167 (found {$theme_version})",
	$failures
);

echo "\n";
if ( empty( $failures ) ) {
	echo "ALL QUIZ AUTO-OPEN TIMING TESTS PASSED\n";
	exit( 0 );
}
echo count( $failures ) . " TEST(S) FAILED\n";
foreach ( $failures as $f ) {
	echo " - {$f}\n";
}
exit( 1 );
