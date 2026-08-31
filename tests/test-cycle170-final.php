<?php
/**
 * CYCLE170-LD-CHAIN — the final tweak build. Theme 1.19.336 (2026-08-30).
 * STAGING ONLY. `wp eval-file` from the site root.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE IS FOR. Four founder-sealed changes, and every one of them
 *    has a quiet failure mode that a page still renders happily around.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   1 · items 537/538/540 — THE SEPTEMBER CARDS. The quiet failure is a
 *       display-only card acquiring a `value`, a `name` or an `<input>` and
 *       becoming submittable. §1 and §5 assert the negative: there is nothing
 *       in a closed card a form can post, and the server still refuses
 *       September either way.
 *
 *   2 · item 538 — THE AUTO-HIGHLIGHT. The quiet failure is a highlight that
 *       exists in the stylesheet but distinguishes nothing, which is what
 *       1.19.335's two tints of one hue at 18% and 8% actually did. §4 asserts
 *       first and backup differ by HUE and each carries a non-colour cue.
 *
 *   3 · item 541 — THE FIFTH VISIT POINT. The quiet failure is a near-miss
 *       paraphrase. §2 asserts it character-exact and asserts it is LAST.
 *
 *   4 · THE ATTRIBUTION FIX. The quiet failure is the one that already
 *       happened: the email composes fine, looks fine, and silently carries no
 *       campaign at all. §3 composes with attribution and without it, and
 *       asserts BOTH shapes.
 *
 * ⛔ NOTHING HERE SENDS MAIL. §3 calls `bhp_readaloud_request_compose()`, which
 *    builds a string and returns it. It does not call `wp_mail()`, it does not
 *    reach the handler, and it does not touch the staging capture log.
 *
 * ⛔ NOTHING HERE WRITES. No option, no post, no meta, no file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bhp_fin_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_fin_pass'] ) ) {
		$GLOBALS['bhp_fin_pass'] = 0;
		$GLOBALS['bhp_fin_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_fin_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_fin_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

echo "\n=== CYCLE170-LD-CHAIN · theme 1.19.336 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · THE VERSION PIN
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 0 · VERSION ===\n";

$bhp_fin_style = (string) file_get_contents( get_template_directory() . '/style.css' );
preg_match( '/^Version:\s*(\S+)/m', $bhp_fin_style, $bhp_fin_vm );
$bhp_fin_ver = isset( $bhp_fin_vm[1] ) ? $bhp_fin_vm[1] : '';
/*
 * ⭐ PIN MOVED TO 1.19.337 BY `CYCLE170-LD-MICRO` (2026-08-30). This lane bumped
 *    the theme, so this lane owns the pin it broke — the discipline
 *    `CYCLE170-LD-CHAIN` §6a set and the reason four OTHER stale pins in this
 *    corpus are deliberately left alone.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM rather than corrected in place, so
 *    the movement is visible and the 1.19.336 attribution to `CYCLE170-LD-CHAIN`
 *    is not silently rewritten:
 *
 *      bhp_fin_assert( '1.19.336' === $bhp_fin_ver, "style.css declares 1.19.336, got '{$bhp_fin_ver}'" );
 */
/*
 * ⭐ PIN MOVED TO 1.19.339 BY `CYCLE170-LD-FINAL2` (2026-08-30). This lane
 *    bumped the theme, so this lane owns the pin it broke — the same discipline
 *    `CYCLE170-LD-CHAIN` §6a set. The four stale pins belonging to OTHER lanes
 *    (ship-prep, triple, school-readaloud, cycle169-funnel) are STILL deliberately
 *    left alone; moving them would silently adopt somebody else's stale suite.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM rather than corrected in place, so
 *    the movement is visible and the 1.19.337 attribution to `CYCLE170-LD-MICRO`
 *    is not rewritten:
 *
 *      bhp_fin_assert( '1.19.337' === $bhp_fin_ver, "style.css declares 1.19.337, got '{$bhp_fin_ver}'" );
 */
/*
 * ⭐ PIN MOVED TO 1.19.341 BY `CYCLE171-LD-341` (2026-08-31, later the same
 *    night). Same discipline the 1.19.340 note below records: the lane that
 *    bumps the theme owns every pin it breaks. The four stale pins belonging to
 *    OTHER lanes (ship-prep, triple, school-readaloud, cycle169-funnel) were
 *    already stale before this build and are STILL deliberately left alone.
 *    SUPERSEDED VALUE, PRESERVED: 1.19.340.
 * ⭐ PIN MOVED TO 1.19.340 BY `CYCLE170-LD-NAMEFIELD` (2026-08-31). This lane
 *    bumped the theme, so this lane owns the pin it broke. The four stale pins
 *    belonging to OTHER lanes are STILL deliberately left alone.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM:
 *
 *      bhp_fin_assert( '1.19.339' === $bhp_fin_ver, "style.css declares 1.19.339, got '{$bhp_fin_ver}'" );
 */
bhp_fin_assert( '1.19.341' === $bhp_fin_ver, "style.css declares 1.19.341, got '{$bhp_fin_ver}'" );

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE SEPTEMBER CARDS — items 537 / 538 / 540
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 1 · THE SEPTEMBER CARDS (display only) ===\n";

bhp_fin_assert( function_exists( 'bhp_readaloud_scheduler_closed_weeks' ), 'bhp_readaloud_scheduler_closed_weeks() exists' );
bhp_fin_assert( function_exists( 'bhp_readaloud_scheduler_closed_week_status_label' ), 'bhp_readaloud_scheduler_closed_week_status_label() exists' );

$bhp_fin_closed = function_exists( 'bhp_readaloud_scheduler_closed_weeks' )
	? bhp_readaloud_scheduler_closed_weeks( '2026-08-30' )
	: array();

bhp_fin_assert( 4 === count( $bhp_fin_closed ), 'FOUR September cards at today=2026-08-30, got ' . count( $bhp_fin_closed ) );

/*
 * ⛔ THE FOUR LABELS AND THE FOUR STATUSES ARE THE FOUNDER'S, VERBATIM. This is
 *    the character-exact gate: a card that drifts to "Week of Sept 1" or to
 *    "Fully booked" is a card he did not approve.
 */
$bhp_fin_want_closed = array(
	array( 'Week of September 1', 'booked', '2026-09-01', '2026-09-04' ),
	array( 'Week of September 14', 'booked', '2026-09-14', '2026-09-18' ),
	array( 'Week of September 21', 'unavailable', '2026-09-21', '2026-09-25' ),
	array( 'Week of September 28', 'unavailable', '2026-09-28', '2026-09-30' ),
);
foreach ( $bhp_fin_want_closed as $bhp_fin_i => $bhp_fin_w ) {
	$bhp_fin_row = isset( $bhp_fin_closed[ $bhp_fin_i ] ) ? $bhp_fin_closed[ $bhp_fin_i ] : array();
	bhp_fin_assert(
		isset( $bhp_fin_row['label'] ) && $bhp_fin_w[0] === $bhp_fin_row['label'],
		'card ' . ( $bhp_fin_i + 1 ) . ' label is CHARACTER-EXACT: "' . $bhp_fin_w[0] . '"'
	);
	bhp_fin_assert(
		isset( $bhp_fin_row['status'] ) && $bhp_fin_w[1] === $bhp_fin_row['status'],
		'card ' . ( $bhp_fin_i + 1 ) . ' status is "' . $bhp_fin_w[1] . '"'
	);
	bhp_fin_assert(
		isset( $bhp_fin_row['first'], $bhp_fin_row['last'] ) && $bhp_fin_w[2] === $bhp_fin_row['first'] && $bhp_fin_w[3] === $bhp_fin_row['last'],
		'card ' . ( $bhp_fin_i + 1 ) . ' spans ' . $bhp_fin_w[2] . ' to ' . $bhp_fin_w[3]
	);
}

/*
 * ⛔⛔ THE SEPARATION THAT MAKES THEM SAFE. A closed card must NEVER appear in
 *     the offer list, and the offer list's gate must never accept one.
 */
$bhp_fin_offered = function_exists( 'bhp_readaloud_scheduler_weeks' ) ? bhp_readaloud_scheduler_weeks() : array();
$bhp_fin_off_labels = array();
foreach ( $bhp_fin_offered as $bhp_fin_o ) {
	$bhp_fin_off_labels[] = $bhp_fin_o['label'];
}
$bhp_fin_leak = 0;
foreach ( $bhp_fin_closed as $bhp_fin_c ) {
	if ( in_array( $bhp_fin_c['label'], $bhp_fin_off_labels, true ) ) {
		++$bhp_fin_leak;
	}
	/* ⛔ A closed row carries NO `value` key at all — there is nothing to post. */
	if ( isset( $bhp_fin_c['value'] ) ) {
		++$bhp_fin_leak;
	}
}
bhp_fin_assert( 0 === $bhp_fin_leak, '⛔⛔ NOT ONE closed card reaches the offer list, and none carries a postable value' );

/* ⛔ THE SERVER GATE STILL REFUSES EVERY SEPTEMBER DAY, unchanged from 1.19.334.
      Drawing a card grants nothing; this is the assertion that says so. */
$bhp_fin_gate_bad = 0;
foreach ( array( '2026-09-01', '2026-09-04', '2026-09-14', '2026-09-21', '2026-09-28', '2026-09-30' ) as $bhp_fin_d ) {
	if ( function_exists( 'bhp_readaloud_scheduler_week_is_offered' ) && bhp_readaloud_scheduler_week_is_offered( $bhp_fin_d ) ) {
		++$bhp_fin_gate_bad;
	}
	if ( function_exists( 'bhp_readaloud_scheduler_date_is_offered' ) && bhp_readaloud_scheduler_date_is_offered( $bhp_fin_d ) ) {
		++$bhp_fin_gate_bad;
	}
}
bhp_fin_assert( 0 === $bhp_fin_gate_bad, '⛔⛔ the SERVER still refuses every September date and every September week' );

/* ⭐ IT EXPIRES BY ITSELF. Nobody has to remember to remove it. */
$bhp_fin_oct = function_exists( 'bhp_readaloud_scheduler_closed_weeks' ) ? bhp_readaloud_scheduler_closed_weeks( '2026-10-01' ) : array( 'x' );
bhp_fin_assert( 0 === count( $bhp_fin_oct ), '⭐ ZERO September cards remain at today=2026-10-01 — the block self-expires' );
$bhp_fin_mid = function_exists( 'bhp_readaloud_scheduler_closed_weeks' ) ? bhp_readaloud_scheduler_closed_weeks( '2026-09-19' ) : array();
bhp_fin_assert( 2 === count( $bhp_fin_mid ), 'exactly TWO cards remain at today=2026-09-19 (the two past weeks drop), got ' . count( $bhp_fin_mid ) );

/* ⛔ TWO STATUS WORDS AND NO THIRD. An unknown status prints nothing. */
bhp_fin_assert( 'Booked' === bhp_readaloud_scheduler_closed_week_status_label( 'booked' ), 'the booked status word is "Booked"' );
bhp_fin_assert( 'Unavailable' === bhp_readaloud_scheduler_closed_week_status_label( 'unavailable' ), 'the unavailable status word is "Unavailable"' );
bhp_fin_assert( '' === bhp_readaloud_scheduler_closed_week_status_label( 'sold-out' ), '⛔ an unknown status prints NOTHING rather than echoing its key' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE FIFTH VISIT POINT — item 541
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 2 · THE FIFTH VISIT POINT (item 541) ===\n";

$bhp_fin_points = function_exists( 'bhp_readaloud_visit_shape_points' ) ? bhp_readaloud_visit_shape_points() : array();
bhp_fin_assert( 5 === count( $bhp_fin_points ), 'exactly FIVE points, got ' . count( $bhp_fin_points ) );
bhp_fin_assert(
	isset( $bhp_fin_points[4] ) && 'I leave a signed copy for your classroom library, free.' === $bhp_fin_points[4],
	'⭐⭐ the fifth point is item 541 VERBATIM and character-exact'
);
bhp_fin_assert(
	isset( $bhp_fin_points[3] ) && 'I read one book, and I answer every question they have.' === $bhp_fin_points[3],
	'⛔ the previous fourth point is UNTOUCHED and still fourth'
);

/* ⛔ THE HOUSE RAILS, applied to the new string specifically. */
$bhp_fin_new = isset( $bhp_fin_points[4] ) ? $bhp_fin_points[4] : '';
bhp_fin_assert( false === strpos( $bhp_fin_new, '—' ) && false === strpos( $bhp_fin_new, '–' ), '⛔ no em dash and no en dash in the new point' );
bhp_fin_assert( 0 === preg_match( '/\b(we|us|our)\b/i', $bhp_fin_new ), '⛔⛔ §9.1: the new point contains no "we", "us" or "our"' );
bhp_fin_assert(
	0 === preg_match( '/\b(loved|thrilled|proven|guarantee|award|rated|reviews?|will help|improves?)\b/i', $bhp_fin_new ),
	'⛔⛔ never-invent: the new point claims no reaction, result, rating or award'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE ATTRIBUTION FIX
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 3 · UTM / ATTRIBUTION IN THE NOTIFICATION EMAIL ===\n";

bhp_fin_assert( function_exists( 'bhp_get_form_moment_attribution' ), 'the signup pipe\'s bhp_get_form_moment_attribution() is reachable from here' );
bhp_fin_assert( function_exists( 'bhp_get_signup_attribution_field_value' ), 'the hidden-field helper is reachable from here' );
bhp_fin_assert( function_exists( 'bhp_extract_attribution_params' ), 'the whitelist/sanitiser is reachable from here' );

/* ⭐ THE WHITELIST ACTUALLY CARRIES WHAT THE BRIEF NAMED: utm_* and fbclid. */
$bhp_fin_parsed = function_exists( 'bhp_extract_attribution_params' )
	? bhp_extract_attribution_params( 'https://example.invalid/school-read-alouds/?utm_source=facebook&utm_medium=paid&utm_campaign=teacher-sept&fbclid=IwAR_test_123&irrelevant=drop' )
	: array();
bhp_fin_assert( isset( $bhp_fin_parsed['utm_source'] ) && 'facebook' === $bhp_fin_parsed['utm_source'], 'utm_source survives the whitelist' );
bhp_fin_assert( isset( $bhp_fin_parsed['utm_medium'] ) && 'paid' === $bhp_fin_parsed['utm_medium'], 'utm_medium survives the whitelist' );
bhp_fin_assert( isset( $bhp_fin_parsed['utm_campaign'] ) && 'teacher-sept' === $bhp_fin_parsed['utm_campaign'], 'utm_campaign survives the whitelist' );
bhp_fin_assert( isset( $bhp_fin_parsed['fbclid'] ) && 'IwAR_test_123' === $bhp_fin_parsed['fbclid'], 'fbclid survives the whitelist' );
bhp_fin_assert( ! isset( $bhp_fin_parsed['irrelevant'] ), '⛔ an unlisted parameter is DROPPED, not forwarded' );

$bhp_fin_req = array(
	'school'       => 'Example Elementary',
	'city'         => 'nampa',
	'contact'      => 'A Teacher',
	'email'        => 'teacher@example.invalid',
	'grades'       => 'First and second grade',
	'week'         => '2026-10-05',
	'week_label'   => 'Week of October 5',
	'week_range'   => 'Monday, October 5 to Friday, October 9',
	'slots'        => array( 'morning' ),
	'notes'        => '',
	'source_page'  => home_url( '/school-read-alouds/' ),
);

/* 3a · WITH a campaign. */
$bhp_fin_with = bhp_readaloud_request_compose( array_merge( $bhp_fin_req, array( 'attribution' => $bhp_fin_parsed ) ) );
bhp_fin_assert( false !== strpos( $bhp_fin_with['body'], 'Campaign:' ), '⭐⭐ the email carries a Campaign line when the visitor arrived on one' );
foreach ( array( 'utm_source=facebook', 'utm_medium=paid', 'utm_campaign=teacher-sept', 'fbclid=IwAR_test_123' ) as $bhp_fin_n ) {
	bhp_fin_assert( false !== strpos( $bhp_fin_with['body'], $bhp_fin_n ), "⭐ the Campaign line carries {$bhp_fin_n}" );
}

/*
 * ⛔⛔ THE REGRESSION GUARD FOR THE ACTUAL DEFECT. `source_page` is still the
 *     clean canonical URL and is NOT widened to carry query parameters — it is
 *     host-checked and reused as a redirect target, and appending client input
 *     to a redirect target is how an open redirect gets built by accident.
 */
bhp_fin_assert(
	false !== strpos( $bhp_fin_with['body'], 'Requested from: ' . home_url( '/school-read-alouds/' ) ),
	'⛔ source_page is unchanged and still the clean canonical URL'
);
bhp_fin_assert(
	false === strpos( $bhp_fin_with['body'], 'Requested from: ' . home_url( '/school-read-alouds/' ) . '?' ),
	'⛔⛔ the campaign is NOT appended to source_page — it rides its own line'
);

/* 3b · WITHOUT a campaign. The line must be ABSENT, not empty. */
$bhp_fin_without = bhp_readaloud_request_compose( $bhp_fin_req );
bhp_fin_assert( false === strpos( $bhp_fin_without['body'], 'Campaign:' ), '⛔ NO empty Campaign line on an organic request' );
bhp_fin_assert( false !== strpos( $bhp_fin_without['body'], 'Week of October 5' ), 'the organic email still carries the week label' );

/* 3c · A malformed attribution value must not fatal and must not print. */
$bhp_fin_junk = bhp_readaloud_request_compose( array_merge( $bhp_fin_req, array( 'attribution' => array( 'utm_source' => '', 'utm_term' => array( 'nested' ) ) ) ) );
bhp_fin_assert( false === strpos( $bhp_fin_junk['body'], 'Campaign:' ), '⛔ an empty/nested attribution prints no Campaign line and does not fatal' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · THE AUTO-HIGHLIGHT — item 538
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 4 · THE AUTO-HIGHLIGHT (item 538) ===\n";

/*
 * ⛔⛔ CSS COMMENTS ARE STRIPPED FIRST, AND THIS SUITE LEARNED IT THE HARD WAY.
 *
 * ⭐ On the FIRST real run of this file, three assertions below went red on a
 *    CORRECT build. The 1.19.336 stylesheet quotes its own SUPERSEDED rules
 *    inside a comment (house practice — the movement must stay legible), so the
 *    naive block-extraction regex matched the 1.19.335 rule out of the comment
 *    and measured the OLD declarations instead of the live ones.
 *
 * ⛔ IT IS THE THIRD INSTANCE OF ONE FAILURE CLASS IN THIS CORPUS: the bundle
 *    suite's `bhp_bun_code_only()`, the weekpicker suite's `toLocaleDateString`
 *    needle, and now this. ⭐ THE RULE IT ESTABLISHES: never run a needle or a
 *    block regex over a file that documents its own history. Strip the
 *    commentary, then measure what actually executes.
 */
$bhp_fin_css = (string) preg_replace( '#/\*.*?\*/#s', '', $bhp_fin_style );

bhp_fin_assert(
	false !== strpos( $bhp_fin_css, '.readaloud-sched__week[data-bhp-week-state="first"]' ),
	'the first-choice card rule exists' );
bhp_fin_assert(
	false !== strpos( $bhp_fin_css, '.readaloud-sched__week[data-bhp-week-state="backup"]' ),
	'the backup card rule exists' );

/*
 * ⛔⛔ THE ASSERTION THAT ACTUALLY MEANS SOMETHING. 1.19.335 had both rules and
 *     still failed the founder's "unmistakable": they were two tints of ONE hue
 *     at 18% and 8%. This asserts the two states differ by HUE.
 */
preg_match( '/\[data-bhp-week-state="first"\]\s*\{(.*?)\}/s', $bhp_fin_css, $bhp_fin_fm );
preg_match( '/\[data-bhp-week-state="backup"\]\s*\{(.*?)\}/s', $bhp_fin_css, $bhp_fin_bm );
$bhp_fin_fblock = isset( $bhp_fin_fm[1] ) ? $bhp_fin_fm[1] : '';
$bhp_fin_bblock = isset( $bhp_fin_bm[1] ) ? $bhp_fin_bm[1] : '';

bhp_fin_assert( '' !== $bhp_fin_fblock && '' !== $bhp_fin_bblock, 'both state rules have a declaration block' );
bhp_fin_assert(
	false !== strpos( $bhp_fin_fblock, '--color-gold-rgb' ) && false !== strpos( $bhp_fin_bblock, '--color-navy-rgb' ),
	'⭐⭐ first choice and backup differ by HUE (gold vs navy), not by opacity of one hue'
);
bhp_fin_assert(
	false !== strpos( $bhp_fin_fblock, 'box-shadow' ) && false !== strpos( $bhp_fin_bblock, 'box-shadow' ),
	'⭐ each state also carries a NON-COLOUR cue (an inset edge bar), so the distinction is not colour alone'
);
bhp_fin_assert(
	false !== strpos( $bhp_fin_fblock, 'inset' ) && false !== strpos( $bhp_fin_bblock, 'inset' ),
	'⛔ both rings are INSET, so nothing shifts by the ring width when a card is picked'
);

/* ⛔ THE `:has()` FALLBACK IS A SEPARATE RULE SET. A browser that drops it must
      not take the working attribute-driven highlight down with it. */
bhp_fin_assert(
	false !== strpos( $bhp_fin_css, ':has(.readaloud-sched__week-input--first:checked)' ),
	'⭐ a no-script :has() fallback exists for the first choice' );
bhp_fin_assert(
	false === strpos( $bhp_fin_css, '[data-bhp-week-state="first"]:has(' ),
	'⛔ the :has() fallback is a SEPARATE rule, never merged into the attribute rule' );

/* ⛔ THE CLOSED CARD MUST NOT USE `opacity` — it would dim the text contrast. */
preg_match( '/\.readaloud-sched__week--closed\s*\{(.*?)\}/s', $bhp_fin_css, $bhp_fin_cm );
$bhp_fin_cblock = isset( $bhp_fin_cm[1] ) ? $bhp_fin_cm[1] : '';
bhp_fin_assert( '' !== $bhp_fin_cblock, 'the closed-card rule exists' );
bhp_fin_assert( false === strpos( $bhp_fin_cblock, 'opacity' ), '⛔ the closed card does NOT use opacity (it would dim the text with the ground)' );
bhp_fin_assert( false !== strpos( $bhp_fin_css, '.readaloud-sched__week-status' ), 'the status badge has a rule' );

/* ⛔ THE HELPER STILL SETS THE STATE, and still creates nothing. */
$bhp_fin_js = (string) file_get_contents( get_template_directory() . '/assets/js/readaloud-calendar.js' );
bhp_fin_assert( false !== strpos( $bhp_fin_js, 'data-bhp-week-state' ), 'the helper still sets the card state attribute' );
bhp_fin_assert( false === strpos( $bhp_fin_js, 'createElement' ), '⛔⛔ the helper still creates NO control' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE RENDERED PAGE
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 5 · THE RENDERED PAGE ===\n";

$bhp_fin_url = function_exists( 'bhp_school_readalouds_url' ) ? bhp_school_readalouds_url() : home_url( '/school-read-alouds/' );
$bhp_fin_res = wp_remote_get( $bhp_fin_url, array( 'timeout' => 45, 'sslverify' => false ) );
$bhp_fin_b   = wp_remote_retrieve_body( $bhp_fin_res );
$bhp_fin_code = (int) wp_remote_retrieve_response_code( $bhp_fin_res );

bhp_fin_assert( 200 === $bhp_fin_code, "the page returns 200, got {$bhp_fin_code}" );

if ( 200 === $bhp_fin_code && '' !== $bhp_fin_b ) {

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⛔⛔ 5a · AMENDED AT 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562).
	 *     THE FOUR SEPTEMBER CARDS NO LONGER RENDER, SO THIS SECTION NOW
	 *     ASSERTS THEIR ABSENCE INSTEAD OF THEIR PRESENCE.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ SUPERSEDED ASSERTIONS, QUOTED VERBATIM RATHER THAN DELETED, so a reader
	 *    can see that the cards left deliberately and can restore the block if
	 *    the founder wants cards back for a future month:
	 *
	 *      foreach ( array( 'Week of September 1', 'Week of September 14', 'Week of September 21', 'Week of September 28' ) as $bhp_fin_lbl ) {
	 *          bhp_fin_assert( false !== strpos( $bhp_fin_b, $bhp_fin_lbl ), "⭐ the page renders \"{$bhp_fin_lbl}\"" );
	 *      }
	 *      bhp_fin_assert( 2 === substr_count( $bhp_fin_b, 'readaloud-sched__week--booked' ), … );
	 *      bhp_fin_assert( 2 === substr_count( $bhp_fin_b, 'readaloud-sched__week--unavailable' ), … );
	 *      bhp_fin_assert( 4 === substr_count( $bhp_fin_b, 'readaloud-sched__week-status' ), … );
	 *      bhp_fin_assert( 4 === substr_count( $bhp_fin_b, 'readaloud-sched__week--closed' ), … );
	 *
	 * ⭐⭐ §2 OF THIS SUITE IS UNTOUCHED AND STILL ASSERTS ALL FOUR ROWS, their
	 *     two status words, their four spans and their self-expiry, because
	 *     `bhp_readaloud_scheduler_closed_weeks()` still returns them. ⛔ THE
	 *     FOUNDER-SEALED RECORD (items 537/538/540) DID NOT MOVE. Only the
	 *     rendering stopped, and this is the only section that had to change.
	 *
	 * ⛔ ABSENCE IS ASSERTED, NOT ASSUMED. Dropping the four positive assertions
	 *    would have left a suite that passes whether the cards render or not.
	 */
	foreach ( array( 'Week of September 1', 'Week of September 14', 'Week of September 21', 'Week of September 28' ) as $bhp_fin_lbl ) {
		bhp_fin_assert( false === strpos( $bhp_fin_b, $bhp_fin_lbl ), "⛔ 1.19.339: the page does NOT render \"{$bhp_fin_lbl}\"" );
	}
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'readaloud-sched__week--booked' ), '⛔ ZERO booked cards render, got ' . substr_count( $bhp_fin_b, 'readaloud-sched__week--booked' ) );
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'readaloud-sched__week--unavailable' ), '⛔ ZERO unavailable cards render, got ' . substr_count( $bhp_fin_b, 'readaloud-sched__week--unavailable' ) );
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'readaloud-sched__week-status' ), '⛔ ZERO status badges render, got ' . substr_count( $bhp_fin_b, 'readaloud-sched__week-status' ) );
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'readaloud-sched__week--closed' ), '⛔ ZERO closed cards render, got ' . substr_count( $bhp_fin_b, 'readaloud-sched__week--closed' ) );

	/* ⭐ AND THE ONE LINE THAT REPLACED THEM IS ON THE PAGE, in the sealed
	      wording, so "the cards left" and "nothing took their place" are two
	      different results and only one of them passes. */
	bhp_fin_assert(
		false !== strpos( $bhp_fin_b, 'September is full. October onward is open.' ),
		'⭐⭐ 1.19.339: the one September line renders VERBATIM in the cards\' place'
	);

	/*
	 * ⛔⛔ THE ONE THAT MATTERS. The September cards must add NOTHING
	 *     submittable. These four counts are unchanged from 1.19.335.
	 */
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'value="2026-09-' ), '⛔⛔ ZERO September values reach the rendered page' );
	bhp_fin_assert( 12 === substr_count( $bhp_fin_b, 'name="visit_week"' ), 'still exactly TWELVE first-choice radios, got ' . substr_count( $bhp_fin_b, 'name="visit_week"' ) );
	bhp_fin_assert( 12 === substr_count( $bhp_fin_b, 'name="visit_week_backup"' ), 'still exactly TWELVE backup radios, got ' . substr_count( $bhp_fin_b, 'name="visit_week_backup"' ) );
	bhp_fin_assert( 12 === substr_count( $bhp_fin_b, 'data-bhp-week="' ), 'exactly TWELVE cards carry data-bhp-week — the closed cards carry none, got ' . substr_count( $bhp_fin_b, 'data-bhp-week="' ) );

	/*
	 * ⛔ NO INPUT INSIDE ANY CLOSED CARD. Scanned per card, not globally.
	 *
	 * ⛔⛔ AMENDED AT 1.19.339: there are no closed cards, so the per-card scan
	 *     asserts the SET IS EMPTY. ⭐ THE PROPERTY IT GUARDED IS NOW GUARANTEED
	 *     BY CONSTRUCTION AND IS STILL PROVEN: zero closed cards contain zero
	 *     inputs, and the `value="2026-09-` count above is the assertion that
	 *     actually mattered — it is UNCHANGED and still runs.
	 *
	 * ⛔ SUPERSEDED ASSERTIONS, QUOTED VERBATIM:
	 *
	 *      bhp_fin_assert( 4 === count( (array) $bhp_fin_cards[0] ), 'the per-card scan found all FOUR closed cards, got ' . … );
	 *      bhp_fin_assert( 0 === $bhp_fin_card_inputs, '⛔⛔ NOT ONE input, name or value attribute exists inside ANY closed card' );
	 */
	preg_match_all( '#<li class="readaloud-sched__week readaloud-sched__week--closed.*?</li>#s', $bhp_fin_b, $bhp_fin_cards );
	$bhp_fin_card_inputs = 0;
	foreach ( (array) $bhp_fin_cards[0] as $bhp_fin_card ) {
		$bhp_fin_card_inputs += substr_count( $bhp_fin_card, '<input' );
		$bhp_fin_card_inputs += substr_count( $bhp_fin_card, 'name=' );
		$bhp_fin_card_inputs += substr_count( $bhp_fin_card, 'value=' );
	}
	bhp_fin_assert( 0 === count( (array) $bhp_fin_cards[0] ), '⛔ 1.19.339: the per-card scan finds ZERO closed cards, got ' . count( (array) $bhp_fin_cards[0] ) );
	bhp_fin_assert( 0 === $bhp_fin_card_inputs, '⛔⛔ NOT ONE input, name or value attribute exists inside ANY closed card' );

	/* 5b · The fifth visit point renders. */
	bhp_fin_assert(
		false !== strpos( $bhp_fin_b, 'I leave a signed copy for your classroom library, free.' ),
		'⭐⭐ the fifth visit point renders VERBATIM on the page'
	);
	$bhp_fin_pt_rendered = 0;
	foreach ( $bhp_fin_points as $bhp_fin_p ) {
		if ( false !== strpos( $bhp_fin_b, esc_html( $bhp_fin_p ) ) || false !== strpos( $bhp_fin_b, $bhp_fin_p ) ) {
			++$bhp_fin_pt_rendered;
		}
	}
	bhp_fin_assert( 5 === $bhp_fin_pt_rendered, 'all FIVE visit points render, got ' . $bhp_fin_pt_rendered );

	/* 5c · The masthead no longer contradicts its own list. */
	bhp_fin_assert( false === strpos( $bhp_fin_b, 'Weeks I can be asked for' ), '⛔ the superseded masthead is gone (it was false once September cards joined the list)' );
	bhp_fin_assert( false !== strpos( $bhp_fin_b, 'The weeks ahead' ), 'the amended masthead renders' );

	/* 5d · Nothing regressed. */
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'PENDING READ-BACK' ), '⛔ ZERO placeholders' );
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'data-popup-config' ), '⛔ ZERO popups — funnel isolation holds on the teacher page' );
	bhp_fin_assert( 1 === substr_count( $bhp_fin_b, 'summited Island Peak' ), 'the approved founder passage is intact' );
	bhp_fin_assert( false !== strpos( $bhp_fin_b, 'ICU nurse' ), 'the honest line is still above the picker' );
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'name="visit_date"' ), '⛔ no day control has come back' );

	/*
	 * ⛔ THE HIDDEN ATTRIBUTION FIELD IS ABSENT ON A CLEAN URL. This is the
	 *    cache-poisoning guard: nothing to serve to the next visitor.
	 */
	bhp_fin_assert( 0 === substr_count( $bhp_fin_b, 'name="bhp_attr_now"' ), '⛔⛔ NO bhp_attr_now field on a clean URL — a cached clean page carries nothing to leak' );

	/* ⭐ AND IT APPEARS WHEN THE URL ACTUALLY CARRIES ONE. */
	$bhp_fin_res2 = wp_remote_get( add_query_arg( array( 'utm_source' => 'facebook', 'utm_medium' => 'paid' ), $bhp_fin_url ), array( 'timeout' => 45, 'sslverify' => false ) );
	$bhp_fin_b2   = wp_remote_retrieve_body( $bhp_fin_res2 );
	bhp_fin_assert(
		false !== strpos( $bhp_fin_b2, 'name="bhp_attr_now"' ) && false !== strpos( $bhp_fin_b2, 'utm_source' ),
		'⭐⭐ the bhp_attr_now field DOES appear when the landing URL carries a campaign'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · §26 — THE AFFILIATE COUNT-DECREASE TEST
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 6 · §26 AFFILIATE SWEEP (counts, printed for the record) ===\n";

foreach ( array( '/blog/why-i-wrote-this-book/', '/shop/', '/school-read-alouds/', '/author-visits/', '/complete-collection/' ) as $bhp_fin_p ) {
	$bhp_fin_r = wp_remote_get( home_url( $bhp_fin_p ), array( 'timeout' => 45, 'sslverify' => false ) );
	$bhp_fin_pb = wp_remote_retrieve_body( $bhp_fin_r );
	printf(
		"  NOTE  %-32s http=%s amzn.to=%d tag=%d\n",
		$bhp_fin_p,
		wp_remote_retrieve_response_code( $bhp_fin_r ),
		substr_count( $bhp_fin_pb, 'amzn.to' ),
		substr_count( $bhp_fin_pb, 'tag=' )
	);
}
/* ⛔ THE COMPARISON IS THE DEPLOY PLAN'S, NOT THIS FILE'S. A suite cannot hold
      the BEFORE count across a deploy, so it prints the AFTER counts and the
      plan records both. §26.6: a count that was not actually run is a
      fabricated check, so this prints what it measured and claims nothing. */

/* ═══════════════════════════════════════════════════════════════════════════
 * TOTALS
 * ═══════════════════════════════════════════════════════════════════════════ */

$bhp_fin_p_n = isset( $GLOBALS['bhp_fin_pass'] ) ? (int) $GLOBALS['bhp_fin_pass'] : 0;
$bhp_fin_f_n = isset( $GLOBALS['bhp_fin_fail'] ) ? (int) $GLOBALS['bhp_fin_fail'] : 0;

echo "\n=== TOTALS ===\n";
echo "  PASS: {$bhp_fin_p_n}\n";
echo "  FAIL: {$bhp_fin_f_n}\n";
echo ( 0 === $bhp_fin_f_n ? "  RESULT: ALL PASS\n" : "  RESULT: FAILURES PRESENT\n" );
