<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE TIME-ONLY POPUP SUITE — theme 1.19.300, 2026-08-27,
 * `CYCLE167-LD-POPUP-TIME-ONLY`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-popup-time-only.php --user=1
 *
 * ⭐ THE RULING THIS SUITE EXISTS TO ENFORCE — Andrew Signore, 2026-08-27,
 *    carrier item 306, VERBATIM:
 *
 *      "We also dont have the awareness or market share - I think we keep
 *       our pop ups time only."
 *
 *    ⚠ RELAYED to this desk through the Chief of Staff, who states he read it
 *      first-hand. NOT witnessed by this suite's author, and the carrier file
 *      itself was searched for on both mounts and NOT FOUND. This suite
 *      therefore enforces a RELAYED ruling and says so, rather than implying a
 *      first-hand reading it cannot claim.
 *
 * ⛔ IT SUPERSEDES, IT DOES NOT CORRECT. Andrew's 2026-08-19 ruling — *"Agree
 *    on the first paint day google recs - wait for engagement and time."* —
 *    was implemented exactly as written and held for eight days. Only the
 *    founder can supersede the founder. Both templates PRESERVE the old
 *    rationale in an annotated supersession block, and §4 below asserts that
 *    preservation, because deleting the history is the failure mode this
 *    house cares about most.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is PHP and source level. It CANNOT prove the popup actually opened at
 * fifteen seconds in a real browser with no scrolling — that claim carries
 * browser evidence at a stated `window.innerWidth` in the handoff and is NOT
 * inferred from a PASS below. What this suite proves is that the CONFIGURATION
 * and the ENGINE PATH it selects can only express time-only behaviour.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, no file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$GLOBALS['bhp_pto_pass'] = 0;
$GLOBALS['bhp_pto_fail'] = 0;

function bhp_pto_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_pto_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_pto_fail']++;
		echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

function bhp_pto_head( $title ) {
	echo "\n=== {$title} ===\n";
}

function bhp_pto_file( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/**
 * ⭐ A FILE'S **CODE**, WITH EVERY COMMENT REMOVED — `token_get_all()`, the
 *    same lexer PHP itself uses, rather than a regex that cannot tell a
 *    comment from the same characters inside a string literal.
 *
 * ⛔⛔ THIS IS NOT OPTIONAL FOR THIS SUITE, IT IS LOAD-BEARING. Both templates
 *     now carry long supersession blocks that DISCUSS `scrollPct`,
 *     `fallbackDelay` and `gated` by name, because preserving the reasoning is
 *     the point. A raw `strpos()` for those tokens would match the explanation
 *     of the rule instead of a violation of it, and would report a defect that
 *     does not exist. That exact trap has been sprung on this file's
 *     neighbours twice.
 */
function bhp_pto_code_only( $rel ) {
	$src = bhp_pto_file( $rel );
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

/** The two timed capture surfaces this ruling governs, and their funnels. */
function bhp_pto_surfaces() {
	return array(
		'parent'  => 'template-parts/acquisition/parent-ab-popup.php',
		'teacher' => 'template-parts/acquisition/mariana-popup.php',
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pto_head( '§0 preconditions' );

foreach ( bhp_pto_surfaces() as $funnel => $rel ) {
	bhp_pto_ok( "§0.1 {$funnel} template is readable", '' !== bhp_pto_file( $rel ), $rel );
}
$bhp_pto_js = bhp_pto_file( 'assets/js/mariana-popup.js' );
bhp_pto_ok( '§0.2 the shared popup engine is readable', '' !== $bhp_pto_js );

if ( $GLOBALS['bhp_pto_fail'] > 0 ) {
	echo "\nABORT: preconditions failed; every later assertion would be meaningless.\n";
	exit( 1 );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE CONFIG IS TIME-ONLY ON BOTH SURFACES.
 *
 * ⭐ "our pop ups" is PLURAL in item 306, which is why the teacher funnel is
 *    held to the same shape as the parent funnel here. Each keeps its own
 *    storage, events and thank-you path — §3 asserts that separately.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pto_head( '§1 item 306 — the trigger is TIME ONLY on both surfaces' );

foreach ( bhp_pto_surfaces() as $funnel => $rel ) {
	$src  = bhp_pto_file( $rel );
	$code = bhp_pto_code_only( $rel );

	bhp_pto_ok(
		"§1.1 [{$funnel}] item 306: trigger mode is 'simple' — time alone opens it",
		1 === preg_match( "/'mode'\s*=>\s*'simple'/", $code )
			&& 0 === preg_match( "/'mode'\s*=>\s*'(gated|exit)'/", $code )
	);

	/* Andrew's own number, unreduced. The count-exactly-twice convention is
	 * carried over from the assertion this replaces — it is what catches a
	 * silent edit to one device only. */
	bhp_pto_ok(
		"§1.2 [{$funnel}] item 306: the 15-second timer appears exactly twice, once per device",
		2 === substr_count( $code, "'delay' => 15000" ),
		'found ' . substr_count( $code, "'delay' => 15000" )
	);

	bhp_pto_ok(
		"§1.3 [{$funnel}] item 306: no OTHER delay value exists, under either key name",
		0 === preg_match( "/'(?:min)?[Dd]elay'\s*=>\s*(?!15000)\d+/", $code )
	);

	/* ⛔⛔ THE ASSERTION THE WHOLE PASS TURNS ON. In `simple` mode a scroll
	 *    threshold does not GATE the timer, it RACES it — so a leftover
	 *    `scrollPct` would let the popup open EARLIER than fifteen seconds on a
	 *    fast scroll, which is the opposite of what item 306 asks for. It would
	 *    also make the engine register a scroll listener. Comments stripped, so
	 *    this tests the code and not the supersession note that explains it. */
	bhp_pto_ok(
		"§1.4 [{$funnel}] item 306: ⛔ NO scrollPct anywhere in the code — the scroll requirement is GONE, not merely relaxed",
		false === strpos( $code, 'scrollPct' )
	);

	bhp_pto_ok(
		"§1.5 [{$funnel}] item 306: ⛔ no fallbackDelay — a dead gated-mode key, and the wrong way to reach time-only",
		false === strpos( $code, 'fallbackDelay' )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE ENGINE PATH THAT CONFIG SELECTS.
 *
 * ⛔ A CONFIG KEY MEANS NOTHING IF THE ENGINE STOPPED HONOURING IT. §1 proves
 *    what we ASKED for; §2 proves the engine still DOES it. The engine is
 *    read-only in this workstream and must remain byte-unchanged.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pto_head( '§2 the shared engine honours simple mode, and is NOT forked' );

bhp_pto_ok(
	'§2.1 simple mode opens the dwell gate at init, so the timer alone governs',
	1 === preg_match( '/var minTimeElapsed = \(mode === \x27simple\x27\);/', $bhp_pto_js )
);

bhp_pto_ok(
	'§2.2 simple mode arms its timer from the `delay` key — the popup does open, on time alone',
	1 === preg_match( '/if \(typeof deviceConfig\.delay === \x27number\x27\) \{\s*minTimeTimerId = window\.setTimeout\(trigger, deviceConfig\.delay\);/', $bhp_pto_js )
);

/* ⭐⭐ THE LINE THAT MAKES "SCROLL-FREE" A FACT RATHER THAN A HOPE. The engine
 *    registers its scroll listener ONLY behind a numeric-threshold guard. With
 *    no threshold configured (§1.4), no listener is ever attached — so there is
 *    no scroll code path left that could reach `trigger()`. */
bhp_pto_ok(
	'§2.3 ⭐ the scroll listener is registered only behind a numeric-threshold guard — with no threshold, none is ever attached',
	1 === preg_match( '/if \(typeof scrollPct === \x27number\x27\) \{\s*window\.addEventListener\(\x27scroll\x27, onScroll, \{ passive: true \}\);/', $bhp_pto_js )
);

/* The gated path must still exist and still be correct — exit-intent and any
 * future surface rely on it, and this workstream did not touch the engine. */
bhp_pto_ok(
	'§2.4 ⛔ the engine was NOT forked: gated mode and its fallback timer are still intact for other surfaces',
	false !== strpos( $bhp_pto_js, 'fallbackTimerId = window.setTimeout(trigger, deviceConfig.fallbackDelay)' )
		&& 1 === preg_match( '/function onScroll\(\)\s*\{\s*if \(!minTimeElapsed \|\| typeof scrollPct !== \x27number\x27\) \{\s*return;/', $bhp_pto_js )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · WHAT MUST NOT HAVE MOVED.
 *
 * ⭐ The brief's word was "keep everything else". This section is that
 *    sentence made executable. A trigger change that quietly re-pointed a
 *    funnel would be a far worse defect than the one it fixed.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pto_head( '§3 session guards, dismissal memory, funnel isolation, the photo, the copy' );

$bhp_pto_parent  = bhp_pto_code_only( 'template-parts/acquisition/parent-ab-popup.php' );
$bhp_pto_teacher = bhp_pto_code_only( 'template-parts/acquisition/mariana-popup.php' );

/* PARENT funnel keys — `.claude/rules/funnels.md`. */
foreach ( array(
	"'eventPrefix'   => 'parent_popup'",
	"'source'        => 'parent_popup_ab'",
	"'storagePrefix' => 'bhp_parent_popup'",
	"'thankYouPath'  => 'adventure-kit-thank-you'",
) as $bhp_pto_key ) {
	bhp_pto_ok( "§3.1 parent funnel key unchanged: {$bhp_pto_key}", false !== strpos( $bhp_pto_parent, $bhp_pto_key ) );
}

/* TEACHER funnel keys — deliberately its own, and deliberately NOT renamed. */
foreach ( array(
	"'eventPrefix'   => 'teacher_popup'",
	"'source'        => 'teacher_popup'",
	"'storagePrefix' => 'bhp_mariana_popup'",
	"'thankYouPath'  => 'mariana-guide-thank-you'",
) as $bhp_pto_key ) {
	bhp_pto_ok( "§3.2 teacher funnel key unchanged: {$bhp_pto_key}", false !== strpos( $bhp_pto_teacher, $bhp_pto_key ) );
}

/* ⛔⛔ ISOLATION, ASSERTED AS ABSENCE IN BOTH DIRECTIONS. Neither file may name
 *    the other funnel's storage prefix or thank-you path. Comments stripped,
 *    because a docblock that PROMISES isolation would otherwise fail the test
 *    for isolation. */
bhp_pto_ok(
	'§3.3 ⛔ the parent popup names no teacher-funnel storage key or thank-you path',
	false === strpos( $bhp_pto_parent, 'bhp_mariana_popup' )
		&& false === strpos( $bhp_pto_parent, 'mariana-guide-thank-you' )
		&& false === strpos( $bhp_pto_parent, 'teacher_popup' )
);
bhp_pto_ok(
	'§3.4 ⛔ the teacher popup names no parent-funnel storage key or thank-you path',
	false === strpos( $bhp_pto_teacher, 'bhp_parent_popup' )
		&& false === strpos( $bhp_pto_teacher, 'adventure-kit-thank-you' )
		&& false === strpos( $bhp_pto_teacher, 'parent_popup' )
);

/* SESSION GUARDS — one capture modal per session, whichever got there first.
 * A trigger change is exactly the kind of edit that drops these by accident. */
bhp_pto_ok(
	'§3.5 the parent popup still declares BOTH session guards',
	false !== strpos( $bhp_pto_parent, "'sessionGuard'" )
		&& false !== strpos( $bhp_pto_parent, 'bhp_quiz_auto_shown' )
		&& false !== strpos( $bhp_pto_parent, 'bhp_popup_shown_session' )
);

/* DISMISSAL MEMORY lives in the engine, not the config — assert it there. */
bhp_pto_ok(
	'§3.6 dismissal memory is intact: a suppressed popup arms NO timer, NO scroll and NO exit listener',
	false !== strpos( $bhp_pto_js, 'if (autoSuppressed) {' )
);

/* THE FOUNDER PHOTOGRAPH (1.19.298 / 1.19.299) must survive a trigger change.
 * ⚠ VERIFIED AGAINST THE TEMPLATE, NOT ASSUMED: the resolver is
 *   `bhp_get_founder_photo()` and the render is gated on its `portrait_webp`
 *   key. An earlier draft of this suite guessed a different function name and
 *   would have passed vacuously on the `||` branch — corrected before first
 *   run rather than after. */
bhp_pto_ok(
	'§3.7 the founder photograph is still resolved and rendered by the parent popup',
	false !== strpos( $bhp_pto_parent, 'bhp_get_founder_photo' )
		&& false !== strpos( $bhp_pto_parent, "\$founder_photo['band_webp']" )
);

/* ⛔ THE NIECE GUARD. Andrew Signore, carrier item 285: "SHE IS MY NIECE and
 *    its all over the canon docs - I DONT HAVE KIDS". The guard runs inside
 *    the alt-text resolver in `functions.php`, NOT in this template — so it is
 *    asserted where it actually lives, and the template is asserted to consume
 *    the guarded alt rather than writing its own string. */
bhp_pto_ok(
	'§3.8 ⛔ the niece guard still exists and still runs on the photo alt text',
	function_exists( 'bhp_niece_canon_violations' )
		&& false !== strpos( (string) bhp_pto_file( 'functions.php' ), 'bhp_niece_canon_violations' )
);
bhp_pto_ok(
	'§3.9 ⛔ the template consumes the GUARDED alt text rather than hardcoding its own',
	false !== strpos( $bhp_pto_parent, "esc_attr(\$founder_photo['alt'])" )
);

/* THE COPY. The FREE Chapter headline (item 290 / 1.19.297) and the "FREE"
 * emphasis treatment must both survive. ⚠ NOTE FOR A FUTURE READER: the older
 * "Free 20 Minute Reluctant Reader Kit" headline was SUPERSEDED at 1.19.297 by
 * the founder's own later wording — do not "restore" it on the strength of the
 * 2026-08-19 docblock still quoted higher up this file. */
bhp_pto_ok(
	'§3.10 the FREE Chapter headline is untouched by this pass',
	false !== strpos( $bhp_pto_parent, 'FREE Chapter for Reluctant Readers' )
);
bhp_pto_ok(
	'§3.11 the FREE emphasis treatment is still applied unconditionally',
	false !== strpos( $bhp_pto_parent, 'bhp_popup_ab_emphasise_free' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE HISTORY IS ANNOTATED, NOT DELETED.
 *
 * ⭐⭐ THIS SECTION IS THE HOUSE RULE MADE EXECUTABLE, and it is the reason the
 *     suite exists as its own file rather than three edits to an existing one.
 *     The brief was explicit: the comment block documenting the OLD rationale
 *     "must be UPDATED to cite item 306 as the superseding authority, not
 *     deleted — annotate the history." A future pass that "tidies up" the
 *     superseded block would erase measurements that cost a full staging
 *     instrumentation run to obtain. This makes that tidy-up fail loudly.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pto_head( '§4 the superseded 2026-08-19 rationale is PRESERVED, not deleted' );

$bhp_pto_parent_raw  = bhp_pto_file( 'template-parts/acquisition/parent-ab-popup.php' );
$bhp_pto_teacher_raw = bhp_pto_file( 'template-parts/acquisition/mariana-popup.php' );

bhp_pto_ok(
	'§4.1 the parent popup names item 306 as the superseding authority',
	false !== strpos( $bhp_pto_parent_raw, 'item 306' )
		|| false !== strpos( $bhp_pto_parent_raw, 'carrier item 306' )
);
bhp_pto_ok(
	'§4.2 the teacher popup names item 306 as the superseding authority',
	false !== strpos( $bhp_pto_teacher_raw, 'item 306' )
		|| false !== strpos( $bhp_pto_teacher_raw, 'carrier item 306' )
);

bhp_pto_ok(
	'§4.3 ⭐ the founder\'s item-306 words are quoted verbatim in the parent popup',
	false !== strpos( $bhp_pto_parent_raw, 'we keep our pop ups time only' )
);

bhp_pto_ok(
	'§4.4 ⛔ the SUPERSEDED 2026-08-19 ruling is still quoted, not deleted',
	false !== strpos( $bhp_pto_parent_raw, 'wait for engagement and time' )
);

/* The expensive measurements. These are the specific lines a cleanup would
 * remove first, and the specific lines nobody should have to re-derive. */
bhp_pto_ok(
	'§4.5 ⛔ the measured scroll costs survive the supersession (1.78 / 1.79 screens)',
	false !== strpos( $bhp_pto_parent_raw, '1.78 screens' )
		&& false !== strpos( $bhp_pto_parent_raw, '1.79 screens' )
);
bhp_pto_ok(
	'§4.6 ⛔ the measured page heights survive the supersession (12,518 / 19,607 px)',
	false !== strpos( $bhp_pto_parent_raw, '12,518' )
		&& false !== strpos( $bhp_pto_parent_raw, '19,607' )
);

bhp_pto_ok(
	'§4.7 ⛔ the teacher popup preserves its 1.19.296 Google search-landing reasoning',
	false !== strpos( $bhp_pto_teacher_raw, 'search landing' )
);

/* ⚠ RELAY HONESTY. The ruling reached this build second-hand, and every file
 *   that acts on it says so. An agent that launders a relayed instruction into
 *   an apparently first-hand one is the failure this asserts against. */
bhp_pto_ok(
	'§4.8 ⚠ both templates disclose that item 306 was RELAYED, not witnessed',
	false !== stripos( $bhp_pto_parent_raw, 'RELAYED' )
		&& false !== stripos( $bhp_pto_teacher_raw, 'RELAYED' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · WHAT THIS PASS DELIBERATELY DID NOT TOUCH.
 *
 * ⛔ The exit-intent modal is the SAME ENGINE but a DIFFERENT MODE, and
 *    converting it would not make it time-only — it would delete the surface
 *    and leave a duplicate timed popup in the same funnel with the same offer.
 *    It also renders only where the parent popup does not (product pages,
 *    /shop/, /books/), so timing it would reverse the `commerce-cx`
 *    CYCLE164-CX #3 finding, which Andrew has NOT overturned.
 *    ⭐ ROUTED to the Chief of Staff as a decision for Andrew; NOT resolved
 *    here. An agent does not resolve a contradiction that belongs to the owner.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pto_head( '§5 out-of-scope surfaces are provably untouched' );

$bhp_pto_exit = bhp_pto_code_only( 'template-parts/acquisition/exit-intent-popup.php' );
bhp_pto_ok(
	'§5.1 ⛔ the exit-intent modal still runs mode `exit` at its own 20s floor',
	false !== strpos( $bhp_pto_exit, "'mode'    => 'exit'" )
		&& false !== strpos( $bhp_pto_exit, '20000' )
);

$bhp_pto_legacy = bhp_pto_code_only( 'template-parts/acquisition/parent-popup.php' );
bhp_pto_ok(
	'§5.2 ⛔ the RETIRED legacy parent popup is untouched dead config, still gated',
	false !== strpos( $bhp_pto_legacy, "'mode'    => 'gated'" )
);
bhp_pto_ok(
	'§5.3 ⛔ and it is still filtered off, so nothing this pass did can reach a visitor through it',
	false === (bool) apply_filters( 'bhp_show_parent_popup', true )
);

/* ⭐ /complete-collection/ NEEDS NO SEPARATE TRIGGER CHANGE, and this asserts
 *   why rather than assuming it. Its 1.19.296 "flip" (item 280) was a SURFACE
 *   ELIGIBILITY change in `bhp_should_show_parent_ab_popup()` — the page was
 *   moved onto the parent A/B popup, which carries the config §1 just proved.
 *   [source] — the eligibility itself depends on a main query this harness
 *   does not have, and is verified in a browser in the handoff. */
bhp_pto_ok(
	'§5.4 [source] /complete-collection/ is routed to the parent A/B popup, so it inherits the time-only trigger',
	false !== strpos( (string) bhp_pto_file( 'functions.php' ), 'complete-collection' )
		&& function_exists( 'bhp_should_show_parent_ab_popup' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * SUMMARY
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n" . str_repeat( '=', 74 ) . "\n";
printf(
	"CYCLE167-LD-POPUP-TIME-ONLY (item 306):  %d passed, %d failed\n",
	$GLOBALS['bhp_pto_pass'],
	$GLOBALS['bhp_pto_fail']
);
echo str_repeat( '=', 74 ) . "\n";
echo "⚠ A PASS HERE IS SOURCE-LEVEL. It does NOT prove the popup opened at 15s\n";
echo "  with zero scrolling in a real browser — that evidence is in the handoff.\n";

if ( $GLOBALS['bhp_pto_fail'] > 0 ) {
	exit( 1 );
}
