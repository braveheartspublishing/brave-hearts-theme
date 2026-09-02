<?php
/**
 * CYCLE170-LD-SCHOOL-READALOUD-MERGE — the ONE read-aloud page. Theme 1.19.326
 * (2026-08-30). STAGING ONLY.
 *
 * ⭐ EXTENDED AT 1.19.327 by CYCLE170-LD-READALOUD-POLISH (§11 at the end of
 *    this file) for Andrew Signore's verdict on the 1.19.326 page: *"there is
 *    so much white space- bring it all a little closer in. Also I dont like 2
 *    of the pictures remove the one where you can only see the kids feet and
 *    the one with my mouth wide open there are other pictures we can use."*
 *    Two assertions in §10 changed rather than being added to, and BOTH are
 *    annotated in place with why: the version literal, and the block-capture
 *    regex that read to end-of-file and would have failed on correctly-scoped
 *    later code. Nothing else in §1–§10 was touched.
 *
 * Founder ruling, Sunday 2026-08-30, relayed in the build brief: *"Start the
 * merge of School read aloud page- should have old visits, new visits, a
 * gallery, and a way to schedule a read aloud via calendly or a self created
 * calendar schedule that shows morning or Afternoon option on the day they
 * want. That way I can do a morning visit or an afternoon visit ans possibly 2
 * in one day."*
 *
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Five of the things this page does are
 *    genuinely dangerous. Everything else is ordinary regression cover.
 *
 *    · **Section 3 — the date gate.** The scheduler accepts a date from a POST
 *      body. If `bhp_readaloud_scheduler_date_is_offered()` ever answers yes to
 *      a weekend, a past date or a date beyond the horizon, the form has no
 *      other defence. Every boundary is asserted against a FIXED "today" so the
 *      answers do not change with the calendar.
 *    · **Section 5 — the mail capture.** This form calls `wp_mail()` directly
 *      and therefore walks straight past `inc/staging-mail-guard.php`, which
 *      only reaches WooCommerce `WC_Email` classes. If the capture ever fails
 *      open on staging, every QA submission mails Andrew. That already happened
 *      once to this project with order emails and the emails could not be
 *      unsent.
 *    · **Section 6 — TENTATIVE.** The one string on this page that must never
 *      soften into "you are booked".
 *    · **Section 7 — the placeholders.** The page still ships unwritten copy.
 *    · **Section 8 — funnel isolation and no price.**
 *
 * ⛔ THE COUNTERS ARE IN $GLOBALS, NOT `global $x`. `wp eval-file` includes this
 *    file INSIDE A FUNCTION, so a top-level variable is that function's LOCAL
 *    and `global $x` binds a different, empty slot — which prints
 *    "PASS: 0 FAIL: 0 / ALL PASS" over a visibly failing run. That happened for
 *    real on 2026-08-29 (finding F8 of the 1.19.319 candidate).
 *
 * ⛔ THIS SUITE WRITES NOTHING AND SENDS NOTHING. It reads functions, reads both
 *    stylesheets, reads template source and fetches the rendered page. It never
 *    submits the form, never calls `wp_mail()`, never writes the capture option,
 *    never touches a post and never calls Mailchimp.
 *
 * Run: wp eval-file tests/test-cycle170-school-readaloud.php --user=1
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assert.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Message.
 */
function bhp_sra_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_sra_pass'] ) ) {
		$GLOBALS['bhp_sra_pass'] = 0;
		$GLOBALS['bhp_sra_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_sra_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_sra_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

echo "\n=== 1 · THE HELPERS EXIST AND THE MERGE FORKED NOTHING ===\n";

foreach ( array(
	'bhp_school_readalouds_slug',
	'bhp_school_readalouds_url',
	'bhp_school_readalouds_cta',
	'bhp_school_readalouds_unify_redirects',
	'bhp_school_readalouds_merged_slugs',
	'bhp_school_readalouds_render_scheduler',
	'bhp_readaloud_scheduler_dates',
	'bhp_readaloud_scheduler_build_dates',
	'bhp_readaloud_scheduler_is_school_day',
	'bhp_readaloud_scheduler_date_is_offered',
	'bhp_readaloud_scheduler_slots',
	'bhp_readaloud_scheduler_cities',
	'bhp_readaloud_request_compose',
	'bhp_readaloud_request_should_capture',
	'bhp_readaloud_request_status_message',
	'bhp_handle_readaloud_request',
) as $fn ) {
	bhp_sra_assert( function_exists( $fn ), "{$fn}() exists" );
}

/* ⭐ THE WHOLE POINT OF THE MERGE: it COMPOSES, it does not copy. If these
      helpers stopped existing the merged page would silently lose a section,
      and if the merge had forked them the suites that assert them would be
      asserting dead code. */
foreach ( array(
	'bhp_author_visits_rows',
	'bhp_author_visits_past_rows',
	'bhp_gallery_sections',
	'bhp_readaloud_funnel_copy_slots',
	'bhp_readaloud_funnel_render_slot',
	'bhp_readaloud_funnel_show_pricing',
	'bhp_readaloud_funnel_cta',
) as $fn ) {
	bhp_sra_assert( function_exists( $fn ), "the pre-existing helper {$fn}() is still present and is REUSED, not forked" );
}

bhp_sra_assert( 'school-read-alouds' === bhp_school_readalouds_slug(), 'the slug is school-read-alouds' );

$bhp_cta = bhp_school_readalouds_cta();
bhp_sra_assert( '#readaloud-scheduler' === $bhp_cta['href'], '⭐ the hero CTA now SCROLLS to the scheduler' );
bhp_sra_assert( 0 !== strpos( (string) $bhp_cta['href'], 'mailto:' ), '⭐ the hero CTA is NO LONGER a mailto:' );
bhp_sra_assert( 'Book a FREE read-aloud' === $bhp_cta['label'], "the label is Andrew's own wording, capitals included (item 481)" );

/* ⛔ /gallery/'s own CTA must be UNTOUCHED. Changing it would have edited
      another surface's contract and broken its 165-assertion suite. */
$bhp_old = bhp_readaloud_funnel_cta();
bhp_sra_assert( 0 === strpos( (string) $bhp_old['href'], 'mailto:' ), "⛔ /gallery/'s own CTA is STILL a mailto: — the 1.19.325 contract was not edited" );

echo "\n=== 2 · SCHOOL DAYS. Monday to Friday, and nothing else. ===\n";

/* Fixed dates so the answers never change with the calendar. */
bhp_sra_assert( true === bhp_readaloud_scheduler_is_school_day( '2026-09-07' ), '2026-09-07 is a Monday: school day' );
bhp_sra_assert( true === bhp_readaloud_scheduler_is_school_day( '2026-09-11' ), '2026-09-11 is a Friday: school day' );
bhp_sra_assert( false === bhp_readaloud_scheduler_is_school_day( '2026-09-12' ), '⛔ 2026-09-12 is a Saturday: NOT offered' );
bhp_sra_assert( false === bhp_readaloud_scheduler_is_school_day( '2026-09-13' ), '⛔ 2026-09-13 is a Sunday: NOT offered' );
bhp_sra_assert( false === bhp_readaloud_scheduler_is_school_day( 'not-a-date' ), 'garbage is not a school day' );
bhp_sra_assert( false === bhp_readaloud_scheduler_is_school_day( '' ), 'an empty string is not a school day' );
bhp_sra_assert( false === bhp_readaloud_scheduler_is_school_day( '2026-13-45' ), 'an impossible date is not a school day' );

echo "\n=== 3 · THE DATE GATE. PURE, so every boundary is an assertion. ===\n";

/* today = Tuesday 2026-09-01, lead 7 days, horizon 30 days. */
$bhp_built = bhp_readaloud_scheduler_build_dates( '2026-09-01', 7, 30 );
$bhp_ymds  = wp_list_pluck( $bhp_built, 'ymd' );

bhp_sra_assert( ! empty( $bhp_built ), 'the builder returns dates' );
bhp_sra_assert( ! in_array( '2026-09-01', $bhp_ymds, true ), '⛔ today itself is NOT offered (the lead time is respected)' );
bhp_sra_assert( ! in_array( '2026-09-07', $bhp_ymds, true ), '⛔ the day BEFORE the lead edge is not offered' );
bhp_sra_assert( in_array( '2026-09-08', $bhp_ymds, true ), '⭐ the FIRST day past the 7 day lead IS offered' );
bhp_sra_assert( ! in_array( '2026-08-31', $bhp_ymds, true ), '⛔ a date in the PAST is never offered' );

$bhp_weekend = 0;
foreach ( $bhp_ymds as $y ) {
	if ( ! bhp_readaloud_scheduler_is_school_day( $y ) ) {
		++$bhp_weekend;
	}
}
bhp_sra_assert( 0 === $bhp_weekend, '⭐⭐ NOT ONE weekend date appears in the offered list' );

/* The horizon edge, both sides.

   ⭐⭐ THESE TWO ASSERTIONS FOUND A REAL DEFECT ON THE FIRST RUN. The loop read
       `$i <= $lead + $horizon` and so offered `lead` days more than the horizon
       it advertised — 97 rather than 90 at the shipped defaults. The horizon is
       measured FROM TODAY. Recorded here rather than quietly corrected, because
       the reason is worth more than the green number. */
$bhp_last = end( $bhp_ymds );
bhp_sra_assert( $bhp_last <= '2026-10-01', '⛔⛔ nothing beyond today + horizon is offered (the horizon is measured from TODAY, not from the lead edge)' );
bhp_sra_assert( ! in_array( '2026-10-05', $bhp_ymds, true ), '⛔ a date past the horizon is not offered' );
bhp_sra_assert( array() === bhp_readaloud_scheduler_build_dates( '2026-09-01', 30, 7 ), '⛔ a lead longer than the horizon yields NO dates rather than a nonsense range' );

/* A zero-length list must be a REFUSAL, not an accident. */
bhp_sra_assert( array() === bhp_readaloud_scheduler_build_dates( 'garbage', 7, 30 ), 'a garbage "today" yields no dates rather than a crash' );

/* The live gate. */
bhp_sra_assert( false === bhp_readaloud_scheduler_date_is_offered( '2026-01-01' ), '⛔⛔ the LIVE gate rejects a date in the past' );
bhp_sra_assert( false === bhp_readaloud_scheduler_date_is_offered( '2099-01-02' ), '⛔⛔ the LIVE gate rejects a date past the horizon' );
bhp_sra_assert( false === bhp_readaloud_scheduler_date_is_offered( '' ), '⛔⛔ the LIVE gate rejects an empty date' );
bhp_sra_assert( false === bhp_readaloud_scheduler_date_is_offered( "2026-09-08' OR 1=1" ), '⛔⛔ the LIVE gate rejects an injection-shaped string' );

$bhp_live = bhp_readaloud_scheduler_dates();
bhp_sra_assert( ! empty( $bhp_live ), 'the live offered list is not empty today' );
if ( ! empty( $bhp_live ) ) {
	bhp_sra_assert( true === bhp_readaloud_scheduler_date_is_offered( $bhp_live[0]['ymd'] ), 'the live gate ACCEPTS a date the form actually rendered' );
	$bhp_weekend_live = 0;
	foreach ( $bhp_live as $r ) {
		if ( ! bhp_readaloud_scheduler_is_school_day( $r['ymd'] ) ) {
			++$bhp_weekend_live;
		}
	}
	bhp_sra_assert( 0 === $bhp_weekend_live, '⭐ not one weekend date is offered on the LIVE list either' );
}

echo "\n=== 4 · MORNING AND AFTERNOON. Two slots, and BOTH may be picked. ===\n";

$bhp_slots = bhp_readaloud_scheduler_slots();
bhp_sra_assert( 2 === count( $bhp_slots ), 'there are exactly two slots' );
bhp_sra_assert( isset( $bhp_slots['morning'] ), 'the morning slot exists' );
bhp_sra_assert( isset( $bhp_slots['afternoon'] ), 'the afternoon slot exists' );
bhp_sra_assert( 'Morning' === $bhp_slots['morning'], 'the morning label reads Morning' );
bhp_sra_assert( 'Afternoon' === $bhp_slots['afternoon'], 'the afternoon label reads Afternoon' );

$bhp_cities = bhp_readaloud_scheduler_cities();
foreach ( array( 'boise', 'meridian', 'nampa', 'eagle' ) as $c ) {
	bhp_sra_assert( isset( $bhp_cities[ $c ] ), "the city {$c} is offered" );
}
bhp_sra_assert( isset( $bhp_cities['other'] ), '⭐ the "somewhere else" branch exists, so a school outside the area is told the truth rather than blocked' );
bhp_sra_assert( false === strpos( bhp_readaloud_scheduler_area_note(), '25' ), '⛔ NO mileage figure is printed — whether the 25 mile radius is public is an OPEN founder decision' );

echo "\n=== 5 · THE MAIL CAPTURE. It must NOT fail open on staging. ===\n";

bhp_sra_assert( function_exists( 'bhp_staging_mail_guard_is_staging' ), 'the EXISTING staging detector is present and is what the capture defers to' );

if ( function_exists( 'bhp_staging_mail_guard_is_staging' ) ) {
	$bhp_is_staging = bhp_staging_mail_guard_is_staging();
	$bhp_captures   = bhp_readaloud_request_should_capture();
	bhp_sra_assert(
		$bhp_is_staging === $bhp_captures,
		'⭐⭐ capture tracks the staging detector EXACTLY (staging=' . var_export( $bhp_is_staging, true ) . ', capture=' . var_export( $bhp_captures, true ) . ')'
	);
}

/* ⛔ The recipient is SERVER-CONTROLLED. A form that carries its own recipient
      is an open relay. Asserted by proving the filter, not the POST, decides. */
$bhp_probe = function () {
	return 'probe@example.invalid';
};
add_filter( 'bhp_readaloud_request_recipient', $bhp_probe, 99 );
bhp_sra_assert( 'probe@example.invalid' === bhp_readaloud_scheduler_recipient(), 'the recipient is decided by a server-side filter' );
remove_filter( 'bhp_readaloud_request_recipient', $bhp_probe, 99 );
bhp_sra_assert( is_email( bhp_readaloud_scheduler_recipient() ), '⭐ the filter was removed and the recipient is a valid address again' );

/* The handler source, read as text. Cheap, and it catches a whole class of
   mistake that no unit assertion would.

   ⛔⛔ COMMENTS ARE STRIPPED BEFORE ANY COUNTING, AND THE FIRST VERSION OF THIS
       SECTION DID NOT DO THAT. It counted `wp_mail(` in the RAW source and
       failed, because the scheduler's own documentation explains at length why
       there is exactly one `wp_mail()` call and where the guard sits. A source
       grep cannot tell a prohibition from an occurrence — the identical trap
       the 1.19.325 lane hit on its funnel-token check, and the identical fix. */
$bhp_sched_raw = (string) file_get_contents( get_template_directory() . '/inc/readaloud-scheduler.php' );
$bhp_sched_src = '';
foreach ( token_get_all( $bhp_sched_raw ) as $bhp_tok ) {
	if ( is_array( $bhp_tok ) && in_array( $bhp_tok[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
		continue;
	}
	$bhp_sched_src .= is_array( $bhp_tok ) ? $bhp_tok[1] : $bhp_tok;
}
bhp_sra_assert( '' !== $bhp_sched_src, 'the scheduler source is readable' );
bhp_sra_assert( strlen( $bhp_sched_src ) < strlen( $bhp_sched_raw ), 'comments were actually stripped before counting' );
bhp_sra_assert( false !== strpos( $bhp_sched_src, 'wp_verify_nonce' ), 'the handler verifies a nonce' );
bhp_sra_assert( false !== strpos( $bhp_sched_src, 'bhp_readaloud_hp' ), 'the handler checks a honeypot' );
bhp_sra_assert( false !== strpos( $bhp_sched_src, 'wp_safe_redirect' ), 'the handler uses wp_safe_redirect, never a raw Location header' );
bhp_sra_assert( false === strpos( $bhp_sched_src, "\$_POST['to']" ), '⛔ the recipient is never read from the POST body' );
bhp_sra_assert( 1 === preg_match_all( '/\bwp_mail\s*\(/', $bhp_sched_src ), '⛔ there is EXACTLY ONE wp_mail() call, so there is exactly one path to guard' );
/* The single send must sit BEHIND the capture branch, not beside it. */
$bhp_cap_pos  = strpos( $bhp_sched_src, 'bhp_readaloud_request_should_capture()' );
$bhp_mail_pos = strpos( $bhp_sched_src, 'wp_mail(' );
bhp_sra_assert(
	false !== $bhp_cap_pos && false !== $bhp_mail_pos && $bhp_cap_pos < $bhp_mail_pos,
	'⭐⭐ the capture check runs BEFORE the only wp_mail() call'
);

echo "\n=== 6 · TENTATIVE. The string that must never soften. ===\n";

$bhp_ok = bhp_readaloud_request_status_message( 'success' );
bhp_sra_assert( is_array( $bhp_ok ), 'the success state has a message' );
bhp_sra_assert( false !== stripos( $bhp_ok['title'] . ' ' . $bhp_ok['text'], 'TENTATIVE' ), '⭐⭐ the success state says TENTATIVE' );
bhp_sra_assert( false !== stripos( $bhp_ok['title'], 'Nothing is booked' ), '⭐⭐ the success TITLE says nothing is booked' );
bhp_sra_assert( false !== stripos( $bhp_ok['text'], 'confirm' ), '⭐ the success state says Andrew confirms' );
foreach ( array( 'you are booked', 'your visit is confirmed', 'booking confirmed', 'reserved' ) as $bad ) {
	bhp_sra_assert( false === stripos( $bhp_ok['title'] . ' ' . $bhp_ok['text'], $bad ), "⛔ the success state does NOT say \"{$bad}\"" );
}
bhp_sra_assert( bhp_readaloud_request_status_message( 'captured' ) === $bhp_ok, 'the captured state is worded identically to success, so QA reads what a visitor reads' );
bhp_sra_assert( null === bhp_readaloud_request_status_message( 'nonsense' ), 'an unknown status yields no banner rather than an empty one' );
foreach ( array( 'noslot', 'baddate', 'invalid', 'error' ) as $st ) {
	$m = bhp_readaloud_request_status_message( $st );
	bhp_sra_assert( is_array( $m ) && 'error' === $m['tone'], "the {$st} state is an error state" );
	bhp_sra_assert( is_array( $m ) && '' !== trim( $m['title'] ) && '' !== trim( $m['text'] ), "the {$st} state actually says something" );
}

echo "\n=== 7 · THE COMPOSED EMAIL. Every field Andrew needs, and no price. ===\n";

$bhp_req = array(
	'school'      => 'Example Elementary',
	'city'        => 'nampa',
	'contact'     => 'A Teacher',
	'email'       => 'teacher@example.invalid',
	'grades'      => 'First and second grade',
	/* ⛔⛔ AMENDED AT 1.19.336 (`CYCLE170-LD-CHAIN`). THIS FIXTURE WAS THE ONE
	      NET-NEW FAILURE 1.19.335 INTRODUCED, and it was a STALE TEST, not a
	      code regression — confirmed by diffing this suite's full failure list
	      at 1.19.334 against 1.19.335 on staging: exactly one line moved.

	      ⛔ SUPERSEDED FIXTURE KEY, quoted rather than deleted:

	            'date'        => '2026-10-06',

	      ⭐ WHY IT WENT RED: item 534 moved the request from a DAY to a WEEK, so
	      `bhp_readaloud_request_compose()` reads `week` / `week_label` /
	      `week_range` and no longer echoes a bare `date`. The assertion below
	      that looked for "2026-10-06" in the body was therefore asserting a
	      field the email had stopped having.

	      ⭐ THE PROPERTY IS UNCHANGED: the email must carry the timing the
	      teacher actually asked for. The fixture now supplies the week the way
	      the handler does, and the needle list asserts the week VALUE, the week
	      LABEL and the week RANGE — which is three checks where there was one. */
	'week'        => '2026-10-05',
	'week_label'  => 'Week of October 5',
	'week_range'  => 'Monday, October 5 to Friday, October 9',
	'slots'       => array( 'morning', 'afternoon' ),
	'notes'       => 'A note from the school.',
	'source_page' => home_url( '/school-read-alouds/' ),
);
$bhp_msg = bhp_readaloud_request_compose( $bhp_req );

bhp_sra_assert( false !== strpos( $bhp_msg['subject'], 'TENTATIVE' ), '⭐⭐ the SUBJECT carries TENTATIVE, so his inbox list says it without opening' );
bhp_sra_assert( false !== strpos( $bhp_msg['subject'], 'Example Elementary' ), 'the subject names the school' );
/* ⛔ AMENDED AT 1.19.336. WAS: ... 'First and second grade', '2026-10-06', 'Morning' ...
      The bare day is replaced by the three strings the week model actually
      composes. See the fixture note above. */
foreach ( array( 'Example Elementary', 'Nampa', 'A Teacher', 'teacher@example.invalid', 'First and second grade', '2026-10-05', 'Week of October 5', 'Monday, October 5 to Friday, October 9', 'Morning', 'Afternoon', 'A note from the school.' ) as $needle ) {
	bhp_sra_assert( false !== strpos( $bhp_msg['body'], $needle ), "⭐ the body carries \"{$needle}\"" );
}
bhp_sra_assert( false !== stripos( $bhp_msg['body'], 'either one works' ), '⭐ ticking BOTH slots is spelled out as "either one works", not left as two words' );
bhp_sra_assert( false !== stripos( $bhp_msg['body'], 'Nothing is booked' ), '⭐⭐ the body itself says nothing is booked' );

/* One slot only must NOT claim either-one-works. */
$bhp_one = bhp_readaloud_request_compose( array_merge( $bhp_req, array( 'slots' => array( 'morning' ) ) ) );
bhp_sra_assert( false === stripos( $bhp_one['body'], 'either one works' ), '⛔ a single-slot request does NOT claim either one works' );
bhp_sra_assert( false === strpos( $bhp_one['body'], 'Afternoon' ), '⛔ a morning-only request does not mention Afternoon' );

/* "Somewhere else" must be loud in his inbox. */
$bhp_far = bhp_readaloud_request_compose( array_merge( $bhp_req, array( 'city' => 'other' ) ) );
bhp_sra_assert( false !== strpos( $bhp_far['body'], 'OUTSIDE THE FOUR LISTED CITIES' ), '⭐ an out-of-area request is flagged in the email he reads' );

/* An unknown slot key must be dropped by compose, not echoed. */
$bhp_junk = bhp_readaloud_request_compose( array_merge( $bhp_req, array( 'slots' => array( 'evening', 'morning' ) ) ) );
bhp_sra_assert( false === stripos( $bhp_junk['body'], 'evening' ), '⛔ an unknown slot key is dropped, never echoed into the email' );

/* ⛔ NO PRICE ANYWHERE IN THE EMAIL EITHER. */
foreach ( array( '$', 'fee', 'price', 'cost', 'invoice', 'rate' ) as $money ) {
	bhp_sra_assert( false === stripos( $bhp_msg['subject'] . ' ' . $bhp_msg['body'], $money ), "⛔ the composed email carries no \"{$money}\"" );
}

echo "\n=== 8 · THE REDIRECT UNIFICATION. Off on production by construction. ===\n";

$bhp_merged = bhp_school_readalouds_merged_slugs();
bhp_sra_assert( in_array( 'gallery', $bhp_merged, true ), '/gallery/ folds into the merged page' );
bhp_sra_assert( in_array( 'author-visits', $bhp_merged, true ), '/author-visits/ folds into the merged page' );
bhp_sra_assert( 2 === count( $bhp_merged ), '⛔ EXACTLY TWO slugs fold in, and the list is literals rather than a pattern' );
bhp_sra_assert( ! in_array( 'read-aloud', $bhp_merged, true ), '⛔⛔ /read-aloud/ (the QR take-home page PRINTED MATERIAL points at) is NOT redirected' );
bhp_sra_assert( ! in_array( 'read-alouds', $bhp_merged, true ), '⛔⛔ /read-alouds/ (page 108, a different older page) is NOT redirected' );
bhp_sra_assert( ! in_array( bhp_school_readalouds_slug(), $bhp_merged, true ), '⛔ the destination never redirects onto itself' );

$bhp_src_sra = (string) file_get_contents( get_template_directory() . '/inc/school-read-alouds.php' );
bhp_sra_assert( false !== strpos( $bhp_src_sra, 'wp_safe_redirect' ), 'the redirect uses wp_safe_redirect' );
bhp_sra_assert( false !== strpos( $bhp_src_sra, '301' ), '⭐ it is a 301, so the equity moves rather than being split' );
bhp_sra_assert( false !== strpos( $bhp_src_sra, 'bhp_staging_mail_guard_is_staging' ), '⭐⭐ the redirect DEFAULT is the staging detector, so it is OFF on production by construction' );
bhp_sra_assert( false !== strpos( $bhp_src_sra, 'get_page_by_path' ), '⛔ it refuses to redirect when the destination page does not exist' );

if ( function_exists( 'bhp_staging_mail_guard_is_staging' ) ) {
	bhp_sra_assert(
		bhp_school_readalouds_unify_redirects() === bhp_staging_mail_guard_is_staging(),
		'the live redirect flag matches the staging detector'
	);
}

echo "\n=== 9 · THE RENDERED PAGE ===\n";

$bhp_page = get_page_by_path( bhp_school_readalouds_slug() );
bhp_sra_assert( $bhp_page instanceof WP_Post, 'the school-read-alouds page record exists' );

if ( $bhp_page instanceof WP_Post ) {
	bhp_sra_assert( 'publish' === $bhp_page->post_status, 'it is published' );
	bhp_sra_assert(
		'page-school-read-alouds.php' === get_post_meta( $bhp_page->ID, '_wp_page_template', true ),
		'it uses the page-school-read-alouds.php template'
	);
	bhp_sra_assert( '' !== (string) get_post_meta( $bhp_page->ID, 'rank_math_title', true ), 'a Rank Math title is set' );
	bhp_sra_assert( '' !== (string) get_post_meta( $bhp_page->ID, 'rank_math_description', true ), 'a Rank Math description is set' );

	$bhp_res  = wp_remote_get( get_permalink( $bhp_page ), array( 'timeout' => 30, 'sslverify' => false ) );
	$bhp_html = is_wp_error( $bhp_res ) ? '' : (string) wp_remote_retrieve_body( $bhp_res );
	bhp_sra_assert( '' !== $bhp_html, 'the page renders' );

	if ( '' !== $bhp_html ) {
		/* ── SECTION ORDER. Founder-ruled, so it is asserted as ORDER and not
		      merely as presence. A page with every section in the wrong order
		      is a different page. */
		$bhp_order = array(
			'school-readalouds-hero-title'     => 'a · the hero',
			'school-readalouds-founder-title'  => 'b · the founder intro',
			'school-readalouds-upcoming-title' => 'c · UPCOMING visits (new visits)',
			'school-readalouds-past-title'     => 'd · PAST read-alouds (old visits)',
			'school-readalouds-gallery-'       => 'e · the photo gallery',
			/* ⛔ `id="…"`, NOT the bare anchor name. The first version of this
			     map searched for `readaloud-scheduler` and matched the HERO
			     CTA's `href="#readaloud-scheduler"` near the top of the
			     document, which made the scheduler look out of order when it
			     was not. The href is the very thing this lane added, so the
			     naive string was guaranteed to collide with it. */
			'id="readaloud-scheduler"'         => 'f · THE SCHEDULER',
			'school-readalouds-capture-title'  => 'g · the educator capture',
			'school-readalouds-pricing'        => 'h · the pricing slot',
		);
		$bhp_prev = -1;
		foreach ( $bhp_order as $anchor => $label ) {
			$pos = strpos( $bhp_html, $anchor );
			bhp_sra_assert( false !== $pos, "the rendered page contains {$label}" );
			if ( false !== $pos ) {
				bhp_sra_assert( $pos > $bhp_prev, "⭐ {$label} is in the founder-ruled ORDER" );
				$bhp_prev = $pos;
			}
		}

		/* ── The upcoming rows carry their ORDER BUTTONS. Printed QR codes
		      depend on this once /author-visits/ 301s here. */
		$bhp_rows = bhp_author_visits_rows();
		if ( ! empty( $bhp_rows ) ) {
			foreach ( $bhp_rows as $r ) {
				bhp_sra_assert( false !== strpos( $bhp_html, esc_html( $r['school'] ) ), "the upcoming visit \"{$r['school']}\" is on the merged page" );
				if ( ! empty( $r['open'] ) && '' !== $r['url'] ) {
					bhp_sra_assert( false !== strpos( $bhp_html, 'bhp_visit=' . $r['slug'] ), "⭐⭐ \"{$r['school']}\" keeps its ORDER BUTTON — the printed-QR ordering path survives the merge" );
				}
			}
		}

		/* ── The past rows. */
		$bhp_pasts = bhp_author_visits_past_rows();
		foreach ( $bhp_pasts as $p ) {
			bhp_sra_assert( false !== strpos( $bhp_html, esc_html( $p['school'] ) ), "the past read-aloud \"{$p['school']}\" is on the merged page" );
		}
		bhp_sra_assert( false === strpos( $bhp_html, 'author-visits-past__item' ) || ! empty( $bhp_pasts ), 'the past column only renders when there are past visits' );

		/* ── The gallery. Approved assets, unchanged. */
		foreach ( bhp_author_visits_gallery_photos() as $ph ) {
			bhp_sra_assert( false !== strpos( $bhp_html, $ph['file'] ), "the approved photograph {$ph['file']} is on the merged page" );
			bhp_sra_assert( false !== strpos( $bhp_html, esc_attr( $ph['alt'] ) ), "its approved alt text is reused byte-for-byte, not rewritten" );
		}

		/* ── THE SCHEDULER, in the rendered DOM.
		   ⛔ AMENDED AT 1.19.335 (`CYCLE170-LD-WEEKPICKER`, carrier item 534).
		      WAS: `strpos( $bhp_html, 'name="visit_date"' )`, "the scheduler
		      renders a date control". The founder's schedule posts about a month
		      at a time, so the control is now a WEEK picker. The property this
		      assertion guards - the scheduler renders a real, server-generated
		      choice control - is unchanged and is what the replacement asserts. */
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="visit_week"' ), 'the scheduler renders a week control' );
		bhp_sra_assert( false === strpos( $bhp_html, 'name="visit_date"' ), '⛔ 1.19.335: no visit_date control is rendered any more' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="slots[]"' ), 'the scheduler renders the slot controls' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'value="morning"' ), 'morning is selectable' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'value="afternoon"' ), 'afternoon is selectable' );
		bhp_sra_assert( 2 === substr_count( $bhp_html, 'type="checkbox"' . '' ) || false !== strpos( $bhp_html, 'readaloud-sched__slot-input' ), 'the slots are checkboxes' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="bhp_readaloud_nonce"' ), 'the form carries a nonce field' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="bhp_readaloud_hp"' ), 'the form carries the honeypot' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="school"' ), 'the form asks for the school' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="city"' ), 'the form asks for the city' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="contact"' ), 'the form asks for a contact name' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'name="grades"' ), 'the form asks for the grades' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'admin-post.php' ), 'the form posts to the admin-post endpoint' );
		bhp_sra_assert( false !== strpos( $bhp_html, 'value="' . BHP_READALOUD_REQUEST_ACTION . '"' ), 'the form declares the right action' );

		/* ⛔ THE SLOTS MUST BE CHECKBOXES, NOT RADIOS. Radios would make the
		      founder's "possibly 2 in one day" model unexpressible. */
		bhp_sra_assert(
			(bool) preg_match( '/type="checkbox"[^>]*name="slots\[\]"|name="slots\[\]"[^>]*type="checkbox"/', $bhp_html ),
			'⭐⭐ the slots are CHECKBOXES so BOTH can be ticked — the founder can take two visits in one day'
		);

		/* ⛔ THE TENTATIVE LINE IS ON THE PAGE BEFORE ANY SUBMISSION.
		   ⛔ AMENDED AT 1.19.335. WAS: `'It does not book the day'`. Item 534
		      changed the unit; the sentence is otherwise unmoved and still sits
		      under the button rather than only on the thank-you state. */
		bhp_sra_assert( false !== stripos( $bhp_html, 'It does not book the week' ), '⭐⭐ the page says the button does not book the week, BEFORE the click' );

		/* ⛔ NO WEEKEND IS RENDERED AS A CHOICE.
		   ⛔ AMENDED AT 1.19.335. WAS a scan of `name="visit_date" value="…"`.
		      The same scan now runs over the week values, and it asserts MORE
		      than it did: not merely that each rendered value is a school day,
		      but that each one passes the server's own week gate. That is the
		      assertion that would catch a template and a gate drifting apart. */
		preg_match_all( '/name="visit_week"\s+value="(\d{4}-\d{2}-\d{2})"/', $bhp_html, $bhp_dm );
		bhp_sra_assert( ! empty( $bhp_dm[1] ), 'the rendered page offers at least one week' );
		$bhp_bad_rendered = 0;
		$bhp_ungated      = 0;
		foreach ( $bhp_dm[1] as $d ) {
			if ( ! bhp_readaloud_scheduler_is_school_day( $d ) ) {
				++$bhp_bad_rendered;
			}
			if ( function_exists( 'bhp_readaloud_scheduler_week_is_offered' ) && ! bhp_readaloud_scheduler_week_is_offered( $d ) ) {
				++$bhp_ungated;
			}
		}
		bhp_sra_assert( 0 === $bhp_bad_rendered, '⭐⭐ NOT ONE weekend is rendered as a choice (' . count( $bhp_dm[1] ) . ' weeks checked)' );
		bhp_sra_assert( 0 === $bhp_ungated, '⭐⭐ EVERY rendered week passes the server gate (' . count( $bhp_dm[1] ) . ' weeks checked)' );

		/* ── PLACEHOLDERS STILL UGLY AND STILL VISIBLE. */
		bhp_sra_assert( false !== strpos( $bhp_html, '[PENDING READ-BACK' ), '⭐⭐ the PENDING READ-BACK placeholders are STILL VISIBLE' );
		bhp_sra_assert( substr_count( $bhp_html, 'bhp-copy-placeholder' ) >= 3, 'at least the three founder-intro placeholder blocks render' );

		/* ── FUNNEL ISOLATION. */
		bhp_sra_assert( false !== strpos( $bhp_html, 'teacher_adventure_toolkit' ) || empty( $bhp_toolkit['ready'] ), 'the capture uses the TEACHER lead magnet key' );
		$bhp_tpl_src = (string) file_get_contents( get_template_directory() . '/page-school-read-alouds.php' );
		/* Strip comments so a prohibition written in prose is not read as an occurrence. */
		$bhp_code_only = '';
		foreach ( token_get_all( $bhp_tpl_src ) as $tok ) {
			if ( is_array( $tok ) && in_array( $tok[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$bhp_code_only .= is_array( $tok ) ? $tok[1] : $tok;
		}
		foreach ( array( 'reluctant_reader_adventure_kit', 'bhp_parent_popup', 'parent_popup', 'adventure_kit_parent' ) as $tok ) {
			bhp_sra_assert( false === strpos( $bhp_code_only, $tok ), "⛔ the template emits NO parent-funnel token \"{$tok}\"" );
		}

		/* ── NO PRICE. */
		bhp_sra_assert( false === strpos( $bhp_html, 'school-readalouds-pricing-title">Booking details</h2><p' ), 'the pricing slot carries no prose' );
		if ( preg_match( '/<section[^>]*id="school-readalouds-pricing".*?<\/section>/s', $bhp_html, $bhp_pm ) ) {
			bhp_sra_assert( false !== strpos( $bhp_pm[0], 'hidden' ), '⭐ the pricing section renders with the hidden attribute' );
			bhp_sra_assert( false !== strpos( $bhp_pm[0], 'data-readaloud-pricing="off"' ), '⭐ the pricing gate reads off' );
			bhp_sra_assert( ! preg_match( '/[$£€]\s?\d/', $bhp_pm[0] ), '⛔⛔ the pricing section carries NO currency figure' );
		}
		bhp_sra_assert( false === bhp_readaloud_funnel_show_pricing(), '⛔ the pricing gate is FALSE' );

		/* ── §9.1 THE VOICE RULE, on this template's own words only. */
		bhp_sra_assert( ! preg_match( '/\b(we|our|us)\b\s+(read|visit|bring|offer|sign)/i', $bhp_code_only ), '⭐ §9.1 — no company "we" in this template\'s own strings' );
	}
}

echo "\n=== 10 · STYLESHEET HYGIENE ===\n";

$bhp_css = (string) file_get_contents( get_template_directory() . '/style.css' );
$bhp_min = (string) file_get_contents( get_template_directory() . '/style.min.css' );

/* ⚠ UPDATED 1.19.326 → 1.19.327 by CYCLE170-LD-READALOUD-POLISH, and recorded
      rather than quietly bumped. This line is stale by construction on EVERY
      version bump — the same defect that took `test-cycle169-readaloud-funnel`
      red when 1.19.326 shipped (that lane's finding F1). It is left as a
      literal on purpose: a suite that reads the version out of the file it is
      checking asserts nothing at all.
   ⚠ BUMPED AGAIN 1.19.327 -> 1.19.328 IN THE SAME LANE, deliberately. Three
      different byte-sets of style.min.css were installed on staging under the
      one query string `?ver=1.19.327` while this fix was iterated, and that
      string IS the browser cache key. A reviewer who had loaded any earlier one
      would have been served stale CSS and would correctly have reported that
      the spacing fix did not land. The version was bumped rather than asking
      anyone to hard-refresh: "hard-refresh and look again" is not a deploy
      verification. Recorded so the jump from the brief's stated 1.19.327 is
      visible rather than looking like a mistake. */
/* ⚠ BUMPED AGAIN 1.19.328 -> 1.19.329 by CYCLE170-LD-READALOUD-HERO-PHOTO.
      Still a LITERAL, for the reason recorded above: a suite that reads the
      version out of the very file it is checking asserts nothing at all. */
/* ⚠ BUMPED AGAIN 1.19.329 -> 1.19.330 by CYCLE170-LD-READALOUD-CAROUSEL.
      Still a LITERAL on purpose: a version read from `wp_get_theme()` would
      assert that the file agrees with itself, which is always true. */
bhp_sra_assert( false !== strpos( $bhp_css, 'Version: 1.19.330' ), 'style.css declares 1.19.330' );
foreach ( array(
	'.readaloud-sched__day',
	'.readaloud-sched__slot',
	'.readaloud-sched__hp',
	'.readaloud-sched__tentative',
	'--bhp-sched-touch',
) as $sel ) {
	bhp_sra_assert( false !== strpos( $bhp_css, $sel ), "style.css carries {$sel}" );
	bhp_sra_assert( false !== strpos( $bhp_min, $sel ), "style.min.css carries {$sel} (the built artefact is current)" );
}

/* ⛔ EVERY CUSTOM PROPERTY THE NEW BLOCK USES MUST ACTUALLY BE DECLARED, or the
      rule silently resolves to nothing. That exact defect shipped at 1.19.325
      (`--space-5`, which does not exist) and only a suite caught it. */
/* ⛔⛔ THE CAPTURE IS BOUNDED AT ITS OWN BLOCK, NOT AT END OF FILE — CORRECTED
      1.19.327, AND THIS IS THE EXACT DEFECT THAT TOOK ANOTHER SUITE RED.
      This regex previously read `…1\.19\.326(.*)$/s`, i.e. "from my header to
      the end of the stylesheet". That is only correct while my block is the
      last thing in the file. The moment ANY later block is appended — as
      1.19.327's spacing block now is — the later block's selectors fall inside
      this capture and the "everything is scoped to .readaloud-sched"
      assertion below fails on code that is perfectly well scoped to something
      else. `test-cycle169-readaloud-funnel` failed in precisely this way when
      1.19.326 appended after it (that lane's finding F1, still open).
      Fixed here rather than inherited: stop at the next block header. */
if ( preg_match( '/THE READ-ALOUD REQUEST SCHEDULER — 1\.19\.326(.*?)(?=\/\* =+\s*\n\s+SCHOOL READ-ALOUDS — VERTICAL RHYTHM|$)/s', $bhp_css, $bhp_blk ) ) {
	preg_match_all( '/var\((--[a-z0-9-]+)\)/i', $bhp_blk[1], $bhp_vars );
	foreach ( array_unique( $bhp_vars[1] ) as $v ) {
		bhp_sra_assert( false !== strpos( $bhp_css, $v . ':' ), "the custom property {$v} is actually declared somewhere in style.css" );
	}
	bhp_sra_assert( false === strpos( $bhp_blk[1], 'var(--space-5)' ), '⛔ the new block does NOT use the non-existent --space-5' );

	/* ⛔ NOTHING IN THE NEW BLOCK LEAKS ONTO ANOTHER PAGE. */
	bhp_sra_assert(
		! preg_match( '/^\s*\.(?!readaloud-sched)[a-z]/mi', $bhp_blk[1] ),
		'⭐⭐ EVERY selector in the new block is scoped to .readaloud-sched — nothing leaks'
	);

	/* ⛔ THE TOUCH TARGET FLOOR. The founder asked for finger-friendly and this
	      is the number that keeps it true. */
	bhp_sra_assert( (bool) preg_match( '/--bhp-sched-touch:\s*(4[4-9]|[5-9]\d)px/', $bhp_blk[1] ), '⭐⭐ the touch target is at least 44px' );

	/* ⛔ THE OFF-SCREEN INPUTS ARE NOT display:none. That would take them out of
	      the accessibility tree and out of the keyboard tab order. */
	bhp_sra_assert(
		! preg_match( '/\.readaloud-sched__(day|slot)-input[^{]*\{[^}]*display:\s*none/s', $bhp_blk[1] ),
		'⭐⭐ the radio and checkbox inputs are moved off-screen, NEVER display:none'
	);
}

echo "\n=== 11 · 1.19.327 POLISH — SPACING AND THE PHOTO SWAP ===\n";

/*
 * Andrew Signore's verdict on the 1.19.326 staging page, relayed in the fix
 * brief: *"there is so much white space- bring it all a little closer in. Also
 * I dont like 2 of the pictures remove the one where you can only see the kids
 * feet and the one with my mouth wide open."*
 *
 * ⛔ WHAT THIS SECTION CAN AND CANNOT PROVE. It reads the stylesheet and the
 *    rendered HTML. It CANNOT compute a used `clamp()` value — that needs a
 *    layout engine. The actual pixel figures at 1440 and 375 were measured in a
 *    real browser with `window.innerWidth` asserted in the same eval, and they
 *    live in the deploy record, NOT in a comment here pretending to be a test.
 */

/* ── 11a · The spacing block exists, is scoped, and uses real tokens.
      ⚠⚠ THE CAPTURE WAS BOUNDED AT 1.19.329 BY CYCLE170-LD-READALOUD-HERO-PHOTO,
         AND THIS IS THE SAME DEFECT THIS SUITE ALREADY FIXED ONCE, ONE BLOCK
         EARLIER, AND THEN REINTRODUCED IN ITS OWN NEW CODE. It read `(.*)$` —
         "from my block header to the end of the stylesheet" — which is only
         correct while this block is LAST in the file. The hero-photograph block
         appended after it at 1.19.329 fell inside the capture, so every rule of
         it was handed to the scope, token and `--space-5` checks below as if it
         were part of the spacing block.
         ⛔ The lesson recorded rather than re-derived: a bounded regex is not a
            style preference, it is the only thing that keeps an append-only
            stylesheet's per-block assertions honest. Bound EVERY new block's
            capture at the next block header on the day you write it, not on the
            day something appends after it. Third instance in this project. */
if ( preg_match( '/SCHOOL READ-ALOUDS — VERTICAL RHYTHM TIGHTENING(.*?)(?=\/\* =+\s*\n\s+SCHOOL READ-ALOUDS — HERO PHOTOGRAPH|$)/s', $bhp_css, $bhp_pol ) ) {
	bhp_sra_assert( true, 'the 1.19.327 spacing block is present in style.css' );

	/* ⛔⛔ NOTHING IN IT LEAKS. Every selector must be scoped to this page's own
	      wrapper class. `--section-space` is a SITEWIDE token — an unscoped
	      redeclaration would retune the vertical rhythm of every non-home page
	      on the site, which is the single largest blast radius in this file. */
	/* ⛔⛔ COMMENTS ARE STRIPPED FIRST, AND THAT IS NOT TIDINESS — IT IS THE FIX
	      FOR THIS ASSERTION'S OWN FIRST-RUN FAILURE. Run 1 of this suite went
	      RED 277/7 on exactly this line: the extractor read `/^[^\s@\/][^{]*\{/m`
	      straight over the block's prose, so a `}` at column 0 followed by four
	      lines of explanation and then the next rule's `{` was handed to the
	      scope check AS IF IT WERE A SELECTOR. Every one of the 7 was a sentence,
	      not a rule; the stylesheet was correct the whole time.
	      This is the third time this project has been bitten by the same class of
	      bug — a raw source scan that cannot tell code from the words about it
	      (1.19.325's funnel-token check, 1.19.326's `wp_mail(` count, this).
	      A test that fails on prose teaches nothing and trains people to ignore
	      it, which is worse than having no test. */
	/* ⚠ THE `'/*' .` PREFIX WAS ADDED AT 1.19.329 BY CYCLE170-LD-READALOUD-HERO-PHOTO.
	      This line had the SAME LATENT DEFECT as the one §12a hit for real: the
	      capture starts inside a comment, so the header prose has no visible
	      opening delimiter and survives the strip. It happened to pass here only
	      because this block's header prose contains no line starting at column 0
	      with a `{` after it. That is luck, not correctness, and the next comment
	      edit above would have turned it red on code that was never wrong.
	      ⛔ NO ASSERTION CHANGED — this makes an existing one honest, and the run
	        is green before and after. */
	$bhp_pol_code = preg_replace( '!/\*.*?\*/!s', '', '/*' . $bhp_pol[1] );
	preg_match_all( '/^([^\s@\/}][^{}]*)\{/m', $bhp_pol_code, $bhp_pol_sel );
	bhp_sra_assert( count( $bhp_pol_sel[1] ) >= 5, 'the scope check actually found rules to check (' . count( $bhp_pol_sel[1] ) . ') — an extractor that matches nothing asserts nothing' );
	foreach ( $bhp_pol_sel[1] as $bhp_sel_line ) {
		foreach ( explode( ',', $bhp_sel_line ) as $bhp_one ) {
			$bhp_one = trim( $bhp_one );
			if ( '' === $bhp_one ) {
				continue;
			}
			bhp_sra_assert(
				false !== strpos( $bhp_one, '.school-readalouds' ),
				"⭐⭐ scoped to .school-readalouds — nothing leaks: {$bhp_one}"
			);
		}
	}

	/* ⛔ Same trap as 1.19.325's `--space-5`: a token that is not declared makes
	      the whole declaration silently vanish. */
	preg_match_all( '/var\((--[a-z0-9-]+)\)/i', $bhp_pol[1], $bhp_pol_vars );
	foreach ( array_unique( $bhp_pol_vars[1] ) as $v ) {
		bhp_sra_assert( false !== strpos( $bhp_css, $v . ':' ), "the spacing block's {$v} is actually declared in style.css" );
	}
	bhp_sra_assert( false === strpos( $bhp_pol[1], 'var(--space-5)' ), '⛔ the spacing block does NOT use the non-existent --space-5' );

	/* ⛔ THE TIGHTENING IS REAL, NOT COSMETIC. The sitewide figures are
	      clamp(5rem, 9vw, 8rem) and clamp(2.5rem, 5vw, 4.5rem). Every number the
	      page substitutes must be SMALLER than the one it replaces, at the floor
	      and at the ceiling both — otherwise a phone or a wide monitor keeps the
	      air the founder objected to. */
	bhp_sra_assert( (bool) preg_match( '/--section-space:\s*clamp\(\s*3\.25rem\s*,\s*6vw\s*,\s*5\.25rem\s*\)/', $bhp_pol[1] ), '⭐ --section-space is retuned to clamp(3.25rem, 6vw, 5.25rem) — floor and ceiling both below the sitewide 5rem/8rem' );
	bhp_sra_assert( (bool) preg_match( '/\.school-readalouds \.component-heading\s*\{[^}]*margin-bottom:\s*clamp\(\s*1\.75rem\s*,\s*3vw\s*,\s*2\.75rem\s*\)/s', $bhp_pol[1] ), '⭐ heading margin-bottom is retuned below the sitewide clamp(2.5rem,5vw,4.5rem)' );

	/* ⛔⛔ THE HERO MAY ONLY EVER GET TIGHTER. The founder-ruled requirement is
	      that the CTA sits above the fold at 1440 AND 375. 1.19.326 shipped
	      clamp(2rem, 4.5vw, 3.5rem); anything larger than that here would push
	      the button DOWN the screen and could break the ruling silently. */
	if ( preg_match( '/\.readaloud-funnel__hero\.section\s*\{[^}]*padding-block:\s*clamp\(\s*([\d.]+)rem\s*,\s*([\d.]+)vw\s*,\s*([\d.]+)rem\s*\)/s', $bhp_pol[1], $bhp_hero_pad ) ) {
		bhp_sra_assert( (float) $bhp_hero_pad[1] < 2.0, '⭐⭐ the hero floor is BELOW 1.19.326\'s 2rem — the CTA can only move up' );
		bhp_sra_assert( (float) $bhp_hero_pad[2] < 4.5, '⭐⭐ the hero vw term is BELOW 1.19.326\'s 4.5vw' );
		bhp_sra_assert( (float) $bhp_hero_pad[3] < 3.5, '⭐⭐ the hero ceiling is BELOW 1.19.326\'s 3.5rem' );
	} else {
		bhp_sra_assert( false, 'the hero padding override parses' );
	}

	/* ⛔ THE PLACEHOLDER STAYS UGLY. Tightening the gap BETWEEN placeholders is
	      the brief; restyling the placeholder itself is not. A block that starts
	      looking like finished design is how unapproved copy reaches a customer. */
	bhp_sra_assert( ! preg_match( '/\.bhp-copy-placeholder\s*\{[^}]*(background|border|font-family|color)\s*:/s', $bhp_pol[1] ), '⛔ the pending-copy placeholder\'s own hazard styling is NOT touched' );

	/* The built artefact is current with the source. */
	bhp_sra_assert( false !== strpos( $bhp_min, '.school-readalouds' ), 'style.min.css carries .school-readalouds (the built artefact is current)' );
} else {
	bhp_sra_assert( false, 'the 1.19.327 spacing block is present in style.css' );
}

/* ── 11b · The two rejected photographs are GONE from the page. */
$bhp_rejected = array(
	/* "the one where you can only see the kids feet" — a strip crop, 1000x261,
	   children photographed from the shoulders down. */
	'adams-elementary-signed-books.jpg',
	/* "the one with my mouth wide open" — Andrew seated on the library couch,
	   585x756, caught mid-word. */
	'adams-elementary-read-aloud-questions.jpg',
);
foreach ( $bhp_rejected as $bhp_gone ) {
	bhp_sra_assert(
		! in_array( $bhp_gone, array_column( bhp_author_visits_gallery_photos(), 'file' ), true ),
		"⭐⭐ the founder-rejected photograph {$bhp_gone} is NOT in the gallery set"
	);
	if ( isset( $bhp_html ) && is_string( $bhp_html ) && '' !== $bhp_html ) {
		bhp_sra_assert( false === strpos( $bhp_html, $bhp_gone ), "⭐⭐ {$bhp_gone} does not appear anywhere in the rendered page" );
	}
}

/* ── 11c · The replacements are there, shipped as theme assets, and captioned
      by the SAME registry join as the photograph that was kept — no new caption
      mechanism, no hand-written caption string. */
$bhp_new_photos = array( 'adams-elementary-read-aloud-reading.jpg', 'adams-elementary-read-aloud-class.jpg' );
$bhp_set        = bhp_author_visits_gallery_photos();
foreach ( $bhp_new_photos as $bhp_np ) {
	$bhp_row = null;
	foreach ( $bhp_set as $bhp_r ) {
		if ( $bhp_np === $bhp_r['file'] ) {
			$bhp_row = $bhp_r;
		}
	}
	bhp_sra_assert( null !== $bhp_row, "the replacement photograph {$bhp_np} is in the gallery set" );
	bhp_sra_assert( file_exists( get_template_directory() . '/assets/img/read-alouds/' . $bhp_np ), "⛔ {$bhp_np} ships as a THEME ASSET — attachment IDs exist only on production and would render broken on staging" );
	if ( null !== $bhp_row ) {
		/* ⛔ AN IMAGE WITH NO ALT TEXT IS A LIE TO A SCREEN READER, and these are
		      photographs of real children. `bhp_author_visits_notes()` drops a
		      row with empty alt rather than rendering one — assert it did not
		      have to. */
		bhp_sra_assert( '' !== trim( (string) $bhp_row['alt'] ), "{$bhp_np} carries non-empty alt text" );
		bhp_sra_assert( strlen( (string) $bhp_row['alt'] ) > 60, "{$bhp_np}'s alt text actually describes the photograph" );
		/* ⛔ NO CHILD IS NAMED AND THE LIBRARIAN IS NEVER NAMED. Standing Rules
		      §3 and Andrew's standing instruction. */
		bhp_sra_assert( ! preg_match( '/\b(librarian|teacher)\s+[A-Z][a-z]+/', (string) $bhp_row['alt'] ), "{$bhp_np}'s alt text names no school staff" );
		/* ⛔ NO OUTCOME, REACTION OR RESULT CLAIM. An alt text may say what is in
		      the frame. It may not say what the visit did to anybody. */
		bhp_sra_assert( ! preg_match( '/\b(loved|enjoyed|inspired|excited|thrilled|engaged|delighted|hooked)\b/i', (string) $bhp_row['alt'] ), "⛔ {$bhp_np}'s alt text makes no reaction or outcome claim" );
		/* Intrinsic dimensions are recorded so the box is reserved and the page
		   does not shift as the photographs load. */
		bhp_sra_assert( $bhp_row['w'] > 0 && $bhp_row['h'] > 0, "{$bhp_np} carries width and height, so it reserves its box" );
		$bhp_dim = @getimagesize( get_template_directory() . '/assets/img/read-alouds/' . $bhp_np );
		if ( is_array( $bhp_dim ) ) {
			bhp_sra_assert( (int) $bhp_dim[0] === (int) $bhp_row['w'] && (int) $bhp_dim[1] === (int) $bhp_row['h'], "⭐ {$bhp_np}'s recorded dimensions match the actual file ({$bhp_dim[0]}x{$bhp_dim[1]}) — a wrong pair distorts the photograph" );
			bhp_sra_assert( (int) $bhp_dim[0] === 1200, "{$bhp_np} is processed to the shipped gallery width of 1200px" );
		}
	}
	if ( isset( $bhp_html ) && is_string( $bhp_html ) && '' !== $bhp_html ) {
		bhp_sra_assert( false !== strpos( $bhp_html, $bhp_np ), "{$bhp_np} renders on the merged page" );
	}
}

/* ── 11d · The photograph Andrew did NOT reject is still there, untouched. */
bhp_sra_assert(
	in_array( 'adams-elementary-read-aloud-group.jpg', array_column( $bhp_set, 'file' ), true ),
	'⭐ the KEPT photograph adams-elementary-read-aloud-group.jpg is still in the gallery set'
);
bhp_sra_assert( 3 === count( $bhp_set ), 'the gallery is 3 photographs — one kept, two replaced' );

/* ── 11e · Every photograph in the set is captioned by the registry join, so
      all three read "Adams Elementary, August 28, 2026" without a caption
      string existing anywhere in the template or in this file. */
foreach ( $bhp_set as $bhp_r ) {
	bhp_sra_assert( '' !== trim( (string) $bhp_r['school'] ), "{$bhp_r['file']} inherits its school from the visit registry" );
	bhp_sra_assert( '' !== trim( (string) $bhp_r['date_display'] ), "{$bhp_r['file']} inherits its date from the visit registry" );
}

echo "\n=== 12 · 1.19.329 — THE SMALL HERO PHOTOGRAPH ===\n";

/*
 * Andrew Signore, carrier item 492, relayed in the fix brief: *"there should be
 * a small photo on the upper right hand side of the header where the free read
 * aloud CTA is of me reading to the kids."*
 *
 * ⛔ WHAT THIS SECTION CAN AND CANNOT PROVE, stated so nobody reads more into a
 *    green run than is there. It reads the stylesheet, the template source, the
 *    rendered HTML and the image file on disk. It CANNOT compute a used
 *    `clamp()`, cannot lay out a grid, and therefore CANNOT prove the CTA is
 *    above the fold. That was measured in a real browser at 1440 and at 375
 *    with `window.innerWidth` asserted in the same eval, and the figures live in
 *    the deploy record — NOT in a comment here dressed up as a test.
 *
 * ⭐ WHAT IT CAN PROVE, and it is the part that actually protects the ruling:
 *    that the figure comes AFTER the CTA in both the template and the rendered
 *    output, and that no `order`, `row-reverse` or absolute positioning exists
 *    to break that. Source order is the mechanism; these assertions are its
 *    guard rail.
 */

/* ── 12a · The CSS block exists, is scoped, and leaks nothing.
      ⛔ THE CAPTURE IS SELF-BOUNDING BY DESIGN — it stops at the next
         `/* ====` block header of any name, or at end of file. §11a above had
         to be repaired twice because it read to EOF and swallowed whatever was
         appended after it. This one cannot acquire that defect later. */
if ( preg_match( '/SCHOOL READ-ALOUDS — HERO PHOTOGRAPH(.*?)(?=\/\* ={10,}\s*\n\s+[A-Z]|$)/s', $bhp_css, $bhp_hp ) ) {
	bhp_sra_assert( true, 'the 1.19.329 hero-photograph block is present in style.css' );

	/* Comments stripped FIRST — the same fix §11a needed. A scope check that
	   reads prose as selectors fails on correct code and teaches nothing. */
	/* ⛔⛔ THE `'/*' .` PREFIX IS NOT COSMETIC AND IT IS WHY THIS RUN IS GREEN.
	      `$bhp_hp[1]` starts immediately after the block-header TEXT, which is
	      itself INSIDE a comment — so the capture opens with an UNCLOSED comment
	      fragment. A stripper looking for `/* … *​/` pairs cannot see that
	      fragment's opening delimiter, leaves the whole header prose in place,
	      and hands sentences to the selector extractor below.
	      ⚠ FIRST RUN OF THIS SECTION WAS RED — 333/14 — AND ALL FOURTEEN WERE
	        THIS, NOT THE STYLESHEET. Nine were English sentences reported as
	        unscoped selectors; one was the "no `row-reverse`" assertion matching
	        the words "no `row-reverse`" in the comment that promises there is
	        none. The stylesheet was correct throughout, at every single one.
	      ⛔ FOURTH INSTANCE IN THIS PROJECT of one bug class — a raw source scan
	        that cannot tell code from the words about it (1.19.325's funnel-token
	        check, 1.19.326's `wp_mail(` count, 1.19.327's scope check, this).
	        Synthesising the missing delimiter closes it for good.
	      ⭐ `^\s*` AND NOT §11a's `^`, AND THE TWO ARE ONLY BOTH RIGHT BECAUSE THE
	        STRIPPING ABOVE IS NOW CORRECT. §11a's block is entirely at column 0;
	        THIS block puts four of its six rules inside a `@media`, indented. An
	        anchored-at-column-0 extractor found 2 of 6 here and reported green on
	        the four it never looked at — caught by the `>= 5` floor below, which
	        is exactly the assertion the polish lane added for this reason. The
	        `@media` line itself is still excluded by the `@` in the class. */
	$bhp_hp_code = preg_replace( '!/\*.*?\*/!s', '', '/*' . $bhp_hp[1] );
	preg_match_all( '/^\s*([^\s@\/}][^{}]*)\{/m', $bhp_hp_code, $bhp_hp_sel );
	bhp_sra_assert( count( $bhp_hp_sel[1] ) >= 5, 'the scope check actually found rules to check (' . count( $bhp_hp_sel[1] ) . ') — an extractor that matches nothing asserts nothing' );
	foreach ( $bhp_hp_sel[1] as $bhp_hp_line ) {
		foreach ( explode( ',', $bhp_hp_line ) as $bhp_one ) {
			$bhp_one = trim( $bhp_one );
			if ( '' === $bhp_one ) {
				continue;
			}
			bhp_sra_assert(
				false !== strpos( $bhp_one, '.school-readalouds' ),
				"⭐⭐ scoped to .school-readalouds — nothing leaks: {$bhp_one}"
			);
		}
	}

	/* Same undeclared-token trap as 1.19.325's `--space-5` and 1.19.327's. */
	preg_match_all( '/var\((--[a-z0-9-]+)\)/i', $bhp_hp[1], $bhp_hp_vars );
	foreach ( array_unique( $bhp_hp_vars[1] ) as $v ) {
		bhp_sra_assert( false !== strpos( $bhp_css, $v . ':' ), "the hero-photo block's {$v} is actually declared in style.css" );
	}
	bhp_sra_assert( false === strpos( $bhp_hp[1], 'var(--space-5)' ), '⛔ the hero-photo block does NOT use the non-existent --space-5' );

	/* ⛔⛔ THE ABOVE-THE-FOLD GUARD RAIL. Source order is what keeps the CTA
	      safe on a phone. Any of these three would sever visual order from DOM
	      order and put a founder ruling at the mercy of a breakpoint. */
	bhp_sra_assert( ! preg_match( '/(^|[;{\s])order\s*:/', $bhp_hp_code ), '⛔⛔ no `order:` — visual order stays DOM order, so the figure can never be lifted above the CTA' );
	bhp_sra_assert( false === strpos( $bhp_hp_code, 'row-reverse' ), '⛔⛔ no `row-reverse`' );
	bhp_sra_assert( false === strpos( $bhp_hp_code, 'position: absolute' ), '⛔⛔ the figure is not absolutely positioned out of flow' );

	/* ⭐ SMALL MEANS SUPPORTING. The brief's range is 260–320px at desktop. */
	if ( preg_match( '/grid-template-columns:\s*minmax\(\s*0\s*,\s*1fr\s*\)\s+([\d.]+)rem/', $bhp_hp[1], $bhp_col ) ) {
		$bhp_px = (float) $bhp_col[1] * 16;
		bhp_sra_assert( $bhp_px >= 260 && $bhp_px <= 320, "⭐ the desktop photo column is {$bhp_px}px — inside the briefed 260–320px" );
	} else {
		bhp_sra_assert( false, 'the desktop photo column parses as minmax(0, 1fr) <rem>' );
	}

	/* ⛔ `minmax(0, 1fr)` NOT a bare `1fr`: a bare `1fr` is minmax(auto, 1fr),
	      whose auto minimum is the longest unbreakable word in the copy column
	      and can push the grid wider than its own container. */
	bhp_sra_assert( false !== strpos( $bhp_hp[1], 'minmax( 0, 1fr )' ) || false !== strpos( $bhp_hp[1], 'minmax(0, 1fr)' ), '⛔ the copy column is minmax(0, 1fr), not a bare 1fr' );
	bhp_sra_assert( (bool) preg_match( '/align-items:\s*start/', $bhp_hp[1] ), '⛔ align-items: start — the photograph sits at the TOP of column two, not stretched down it' );
	bhp_sra_assert( 2 === preg_match_all( '/grid-row:\s*1\s*;/', $bhp_hp[1] ), '⛔ BOTH grid children are pinned to row 1 — without it the figure auto-places under the copy' );

	/* The photograph keeps its intrinsic ratio once the width/height attributes
	   have reserved the box — the identical pair the shipped gallery rule uses. */
	bhp_sra_assert( (bool) preg_match( '/\.school-readalouds__hero-photo-img\s*\{[^}]*height:\s*auto/s', $bhp_hp[1] ), '⛔ the image carries height: auto, so the width/height attributes cannot distort it' );
	bhp_sra_assert( (bool) preg_match( '/border-radius:\s*var\(--radius-(sm|md|lg)\)/', $bhp_hp[1] ), '⭐ rounded with a brand radius token, not a literal — the site\'s card language' );

	/* The built artefact is current with the source. */
	bhp_sra_assert( false !== strpos( $bhp_min, 'school-readalouds__hero-grid' ), 'style.min.css carries the hero grid (the built artefact is current)' );
	bhp_sra_assert( false !== strpos( $bhp_min, 'school-readalouds__hero-photo' ), 'style.min.css carries the hero figure' );
} else {
	bhp_sra_assert( false, 'the 1.19.329 hero-photograph block is present in style.css' );
}

/* ── 12b · The image file itself. */
$bhp_hero_file = get_template_directory() . '/assets/img/read-alouds/adams-elementary-read-aloud-hero.jpg';
bhp_sra_assert( file_exists( $bhp_hero_file ), 'the hero photograph ships as a THEME ASSET (renders on any environment; an attachment ID would not)' );
if ( file_exists( $bhp_hero_file ) ) {
	$bhp_dim = getimagesize( $bhp_hero_file );
	bhp_sra_assert( is_array( $bhp_dim ) && 640 === $bhp_dim[0] && 640 === $bhp_dim[1], '⭐ the file is really 640x640 — the width/height attributes match the pixels, so the reserved box is the right shape' );
	bhp_sra_assert( filesize( $bhp_hero_file ) < 160000, 'the hero photograph is under 160KB — it loads above the fold (' . filesize( $bhp_hero_file ) . ' bytes)' );

	/* ⛔ IT IS NOT ALSO A GALLERY ROW. The gallery is registry-driven; this is
	      a template asset. If it ever entered the option the page would show the
	      same photograph twice and §11d's count of 3 would break silently. */
	bhp_sra_assert(
		! in_array( 'adams-elementary-read-aloud-hero.jpg', array_column( bhp_author_visits_gallery_photos(), 'file' ), true ),
		'⛔ the hero photograph is NOT in the gallery set — one photograph, one job'
	);
}

/* ── 12c · The template: DOM order, and the alt text. */
$bhp_hp_tpl = (string) file_get_contents( get_template_directory() . '/page-school-read-alouds.php' );
$bhp_pos_cta   = strpos( $bhp_hp_tpl, 'readaloud-funnel__hero-cta' );
$bhp_pos_photo = strpos( $bhp_hp_tpl, 'school-readalouds__hero-photo' );
bhp_sra_assert( false !== $bhp_pos_photo, 'the template renders the hero figure' );
bhp_sra_assert(
	false !== $bhp_pos_cta && false !== $bhp_pos_photo && $bhp_pos_cta < $bhp_pos_photo,
	'⭐⭐ IN THE TEMPLATE the CTA comes BEFORE the photograph — the phone case cannot push the button down'
);

/* ⛔ THE ALT TEXT IS FACTUAL AND IT IS THE EXACT STRING THE BRIEF SPECIFIED.
      An empty alt on a photograph of real children is a lie to a screen reader;
      an embellished one is a claim nobody can source. */
$bhp_alt = 'Andrew Signore reading to a classroom at Adams Elementary';
bhp_sra_assert( false !== strpos( $bhp_hp_tpl, $bhp_alt ), '⭐ the alt text is the briefed factual string, verbatim' );

/* ⛔ NEVER-INVENT (Standing Rules §3) AND THE HOUSE COPY RAILS, ENFORCED RATHER
      THAN PROMISED. The alt says what is in the frame and stops: no child named,
      no staff member named, and NO reaction, outcome or result claim about what
      the visit did to anybody. */
foreach ( array( 'loved', 'enjoyed', 'excited', 'engaged', 'inspired', 'thrilled', 'favorite', 'best', 'amazing', 'librarian', 'teacher', 'we ' ) as $bhp_bad ) {
	bhp_sra_assert( false === stripos( $bhp_alt, $bhp_bad ), "⛔ the alt text carries no '{$bhp_bad}' — no reaction, outcome or third party named" );
}
bhp_sra_assert( '' !== trim( $bhp_alt ), '⛔ the alt is not empty — this photograph is content, not decoration' );

/* ── 12d · The rendered page agrees with the template, and NOT ONE WORD of the
      hero copy moved. The wrappers are new; the strings are 1.19.328's. */
if ( isset( $bhp_html ) && is_string( $bhp_html ) && '' !== $bhp_html ) {
	$bhp_h_cta   = strpos( $bhp_html, 'readaloud-funnel__hero-cta' );
	$bhp_h_photo = strpos( $bhp_html, 'school-readalouds__hero-photo' );
	bhp_sra_assert( false !== $bhp_h_photo, 'the hero photograph renders on the live page' );
	bhp_sra_assert(
		false !== $bhp_h_cta && false !== $bhp_h_photo && $bhp_h_cta < $bhp_h_photo,
		'⭐⭐ IN THE RENDERED HTML the CTA comes BEFORE the photograph'
	);
	bhp_sra_assert( false !== strpos( $bhp_html, 'adams-elementary-read-aloud-hero.jpg' ), 'the rendered page points at the hero photograph file' );
	bhp_sra_assert( false !== strpos( $bhp_html, $bhp_alt ), 'the rendered page carries the factual alt text' );
	bhp_sra_assert( false !== strpos( $bhp_html, 'school-readalouds__hero-grid' ), 'the hero container carries the grid class' );
	bhp_sra_assert( false !== strpos( $bhp_html, 'school-readalouds__hero-copy' ), 'the hero copy wrapper renders' );

	/* ⛔ THE COPY IS UNTOUCHED. These strings are 1.19.328's, byte-for-byte.
	      A layout lane that quietly reworded a founder-approved line would be a
	      worse failure than one that shipped no photograph at all.

	   ⛔⛔ AMENDED AT 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562, the `chief-of-staff`
	       implementation ruling). "There is no charge." IS REMOVED FROM THE HERO
	       AND IS THEREFORE REMOVED FROM THIS LIST.

	   ⛔ SUPERSEDED LIST, QUOTED VERBATIM RATHER THAN DELETED:

	          'Read-alouds',
	          'Book a free read-aloud',
	          'My calendar is open for Boise-area classroom read-alouds from October onward.',
	          'There is no charge.',

	   ⭐ THE OTHER THREE ARE UNCHANGED AND ARE STILL ASSERTED CHARACTER-EXACT, so
	      this amendment removes one string and weakens nothing about the rest.
	   ⭐ THE REMOVAL IS ASSERTED POSITIVELY, not merely dropped: §1 of
	      `tests/test-cycle170-final2.php` requires the string to be ABSENT from
	      the rendered hero, so "it stopped being checked" and "it left" are two
	      different results here and only one of them passes. */
	foreach ( array(
		'Read-alouds',
		'Book a free read-aloud',
		'My calendar is open for Boise-area classroom read-alouds from October onward.',
	) as $bhp_str ) {
		bhp_sra_assert( false !== strpos( $bhp_html, $bhp_str ), "⛔ hero copy unchanged: \"{$bhp_str}\"" );
	}

	/* Still no price, fee or rate anywhere in the hero — item 481 is unchanged. */
	$bhp_hero_html = '';
	if ( preg_match( '/<section[^>]*readaloud-funnel__hero.*?<\/section>/s', $bhp_html, $bhp_hs ) ) {
		$bhp_hero_html = $bhp_hs[0];
		bhp_sra_assert( ! preg_match( '/\$\s?\d/', $bhp_hero_html ), '⛔ no price, fee or rate appears in the hero' );
		bhp_sra_assert( false !== strpos( $bhp_hero_html, 'adams-elementary-read-aloud-hero.jpg' ), '⭐ the photograph is INSIDE the hero section, not somewhere else on the page' );
	} else {
		bhp_sra_assert( false, 'the hero section parses out of the rendered page' );
	}
}


echo "\n=== 13 · 1.19.330 THE PHOTO CAROUSEL ===\n";

/*
 * Andrew Signore, carrier item 497, relayed in the build brief and ⛔ NOT
 * witnessed here: *"I want a large carousel gallery on the read aloud page and
 * I want you to add the read aloud pictures from last year too."*
 *
 * ⛔ WHAT THIS SECTION CAN AND CANNOT PROVE. It reads functions, reads the
 *    stylesheet, reads two template sources and one script source, stats three
 *    image files, and fetches the rendered page. It CANNOT prove that a swipe
 *    moves the rail, that a dot lights up, or that the snap lands cleanly —
 *    those are browser facts and they are evidenced by DOM reads in the deploy
 *    record, not asserted here. What it CAN prove is that the mechanism which
 *    makes those things work is present, scoped, and not quietly removable.
 *
 * ⛔ THE TWO REJECTED PHOTOGRAPHS ARE ASSERTED ABSENT, NOT MERELY UNUSED.
 *    Andrew removed `-questions.jpg` (his mouth open) and `-signed-books.jpg`
 *    (children's feet only) at 1.19.328. Both files still SHIP in the theme
 *    (finding P4, still open), so "the carousel does not happen to list them"
 *    is not good enough — a future edit to the archive array could reach for
 *    them by name. 13a and 13d assert they appear in neither the data nor the
 *    rendered page.
 */

/* ── 13a · THE DATA. Composition, order, and the two rejected files. ------- */

foreach ( array(
	'bhp_readaloud_carousel_photos',
	'bhp_readaloud_archive_photos',
	'bhp_enqueue_readaloud_carousel_assets',
) as $bhp_fn ) {
	bhp_sra_assert( function_exists( $bhp_fn ), "{$bhp_fn}() exists" );
}

/* ⛔ THE REGISTRY IS UNTOUCHED AND `/gallery/` STILL OWNS ITS OWN STRUCTURE.
      This lane stopped CALLING `bhp_gallery_sections()` for the photo list; it
      did not edit it, empty it, or take its heading away. If a later edit
      deletes the markets category or the read-alouds title, /gallery/ breaks
      and this page's heading changes with it — so both are asserted here. */
bhp_sra_assert( function_exists( 'bhp_gallery_sections' ), '⛔ bhp_gallery_sections() still exists — inc/gallery-page.php was NOT edited' );
$bhp_secs = bhp_gallery_sections();
bhp_sra_assert( isset( $bhp_secs['read-alouds'] ), "⛔ the 'read-alouds' category still exists" );
bhp_sra_assert( isset( $bhp_secs['markets'] ), "⛔ the 'markets' category still exists" );
bhp_sra_assert( empty( $bhp_secs['markets']['photos'] ), '⛔ the markets category is STILL empty by fact — nothing was invented to fill it' );
bhp_sra_assert( 'School read-alouds' === $bhp_secs['read-alouds']['title'], "⭐ the section heading is still the registry's own 'School read-alouds' — retyping it here is what would let the two surfaces drift" );

$bhp_reg  = bhp_author_visits_gallery_photos();
$bhp_arch = bhp_readaloud_archive_photos();
$bhp_car  = bhp_readaloud_carousel_photos();

bhp_sra_assert( count( $bhp_reg ) === 3, '⛔ the registry still yields exactly THREE Adams photographs — the founder-approved set is unchanged' );
bhp_sra_assert( count( $bhp_car ) === count( $bhp_reg ) + count( $bhp_arch ), 'the carousel is exactly the registry rows plus the archive rows, with nothing added or dropped' );
bhp_sra_assert( count( $bhp_car ) > 1, '⛔ more than one photograph — a one-slide carousel would render its controls hidden and the founder asked for a carousel' );

/* ⭐ ORDER IS THE BRIEF'S AND IT IS ASSERTED AS ORDER: newest first (the
      registry, which is itself newest-first), then the older archive. A set
      that contains the right photographs in the wrong order is a different
      answer to the instruction. */
$bhp_order_ok = true;
foreach ( $bhp_reg as $bhp_i => $bhp_r ) {
	if ( ! isset( $bhp_car[ $bhp_i ] ) || $bhp_car[ $bhp_i ]['file'] !== $bhp_r['file'] ) {
		$bhp_order_ok = false;
	}
}
bhp_sra_assert( $bhp_order_ok, '⭐⭐ the registry photographs come FIRST, in the registry\'s own order — the Adams three keep the order they already had' );

$bhp_arch_ok = true;
foreach ( $bhp_arch as $bhp_i => $bhp_a ) {
	$bhp_at = count( $bhp_reg ) + $bhp_i;
	if ( ! isset( $bhp_car[ $bhp_at ] ) || $bhp_car[ $bhp_at ]['file'] !== $bhp_a['file'] ) {
		$bhp_arch_ok = false;
	}
}
bhp_sra_assert( $bhp_arch_ok, '⭐ the archive photographs come AFTER, in their own order' );

/* ⛔ THE TWO PHOTOGRAPHS ANDREW REJECTED CAN NEVER COME BACK THROUGH THIS LIST. */
$bhp_banned = array( 'adams-elementary-read-aloud-questions.jpg', 'adams-elementary-signed-books.jpg' );
foreach ( $bhp_car as $bhp_p ) {
	foreach ( $bhp_banned as $bhp_b ) {
		bhp_sra_assert( $bhp_p['file'] !== $bhp_b, "⛔⛔ the carousel does not carry {$bhp_b} — Andrew removed it at 1.19.328" );
	}
}

/* Every row is renderable, and every row is described. An undescribed
   photograph of real children is a lie to a screen reader. */
foreach ( $bhp_car as $bhp_i => $bhp_p ) {
	bhp_sra_assert( '' !== trim( (string) $bhp_p['file'] ), "row {$bhp_i} has a file" );
	bhp_sra_assert( '' !== trim( (string) $bhp_p['alt'] ), "row {$bhp_i} has non-empty alt text" );
	bhp_sra_assert( (int) $bhp_p['w'] > 0 && (int) $bhp_p['h'] > 0, "row {$bhp_i} carries explicit width and height — the box is reserved before the photograph arrives" );
	bhp_sra_assert( '' !== trim( (string) $bhp_p['caption'] ), "row {$bhp_i} has a caption" );
}

/* ⛔ THE ADAMS CAPTIONS AND ALT TEXT ARE THE FOUNDER'S, READ LIVE FROM THE
      OPTION. The composition is the shipped figcaption's, so not one visible
      character changed for them when the layout did. */
foreach ( $bhp_reg as $bhp_i => $bhp_r ) {
	$bhp_expected = trim( (string) $bhp_r['school'] . ( '' !== (string) $bhp_r['date_display'] ? ', ' . $bhp_r['date_display'] : '' ) );
	bhp_sra_assert( $bhp_car[ $bhp_i ]['caption'] === $bhp_expected, "⭐ Adams caption {$bhp_i} is still \"{$bhp_expected}\" — the shipped figcaption composition, unchanged" );
	bhp_sra_assert( $bhp_car[ $bhp_i ]['alt'] === (string) $bhp_r['alt'], "⛔ Adams alt {$bhp_i} is the founder-published string, passed through and not rewritten" );
}

/* ── 13a2 · THE ARCHIVE ROWS — never-invent, and the file on disk. --------- */

/*
 * ⛔ NO SCHOOL IS NAMED, AND THAT IS THE POINT OF THIS ASSERTION.
 *    The May 2026 event's school is NOT known to this build: the folder is
 *    flat, the filenames are camera serials, and the EXIF carries a timestamp
 *    and a camera model. A guessed school name on a photograph of real children
 *    is exactly the never-invent failure. The caption says the month, the year
 *    and nothing else.
 *
 * ⛔ AND THE VERB IS "VISIT", NOT "READ-ALOUD". In all three frames Andrew is
 *    standing and talking beside a projected slide; he is not shown reading
 *    from a book in any of them. The SECTION is headed "School read-alouds";
 *    the CAPTION claims only what the photograph shows.
 */
$bhp_theme_dir = get_template_directory();
foreach ( $bhp_arch as $bhp_i => $bhp_a ) {
	$bhp_cap = (string) $bhp_a['caption'];
	bhp_sra_assert( 'A school visit, May 2026' === $bhp_cap, "⭐⭐ archive caption {$bhp_i} names NO school — it is the generic truthful string" );
	bhp_sra_assert( false === stripos( $bhp_cap, 'elementary' ), "⛔ archive caption {$bhp_i} contains no school-name word" );
	bhp_sra_assert( false === stripos( $bhp_cap, 'adams' ), "⛔ archive caption {$bhp_i} does not borrow the Adams name" );

	/* ⛔ NEVER-INVENT (Standing Rules §3) AND THE HOUSE COPY RAILS, ENFORCED.
	      The alt says what is in the frame and stops: no child named, no staff
	      member named, and NO reaction, outcome or result claim. `we ` is §9.1 —
	      although alt text is third-person factual, asserting it costs nothing
	      and the rail is the thing that must not erode. */
	foreach ( array( 'loved', 'enjoyed', 'excited', 'engaged', 'inspired', 'thrilled', 'favorite', 'best', 'amazing', 'librarian', 'teacher', 'we ', 'our ' ) as $bhp_bad ) {
		bhp_sra_assert( false === stripos( (string) $bhp_a['alt'], $bhp_bad ), "⛔ archive alt {$bhp_i} carries no '{$bhp_bad}'" );
	}

	/* ⛔ A THEME ASSET, NOT AN ATTACHMENT. An attachment ID exists on one
	      environment and renders broken on the other — 1.19.329 §7's reason,
	      unchanged. So the file has to actually be in the theme. */
	$bhp_path = $bhp_theme_dir . '/assets/img/read-alouds/' . $bhp_a['file'];
	bhp_sra_assert( file_exists( $bhp_path ), "⛔ archive photograph {$bhp_a['file']} exists as a THEME ASSET" );
	if ( file_exists( $bhp_path ) ) {
		$bhp_sz = @getimagesize( $bhp_path );
		bhp_sra_assert( is_array( $bhp_sz ) && 1200 === (int) $bhp_sz[0] && 675 === (int) $bhp_sz[1], "{$bhp_a['file']} is 1200x675 — exactly the 16/9 stage, so it fills the frame with no bands" );
		bhp_sra_assert( is_array( $bhp_sz ) && (int) $bhp_sz[0] === (int) $bhp_a['w'] && (int) $bhp_sz[1] === (int) $bhp_a['h'], "{$bhp_a['file']}'s declared width/height match the file on disk — a wrong pair would reserve the wrong box" );
		$bhp_bytes = (int) filesize( $bhp_path );
		$bhp_bpp   = $bhp_bytes / ( 1200 * 675 );
		/* The shipped set runs 0.263 to 0.317 bytes/pixel. Asserting a BAND
		   rather than a ceiling catches both directions: a bloated re-encode
		   and a quality collapse that would show as mush on a large stage. */
		bhp_sra_assert( $bhp_bpp > 0.20 && $bhp_bpp < 0.35, sprintf( '%s compresses at %.4f B/px — inside the shipped set\'s 0.26-0.32 band', $bhp_a['file'], $bhp_bpp ) );
		bhp_sra_assert( $bhp_bytes < 262144, sprintf( '%s is %d bytes, under 256KB', $bhp_a['file'], $bhp_bytes ) );
	}
}

/* ── 13b · THE STYLESHEET BLOCK. Scoped, self-bounded, and honest. --------- */

/*
 * ⭐ THE CAPTURE IS SELF-BOUNDING, exactly as §12a's is, so the NEXT lane to
 *    append a block cannot make this section read to end-of-file and start
 *    asserting somebody else's rules. That defect has now been introduced and
 *    fixed three times in this file (§10, §11a, §12a); writing the guard in
 *    from the start is the only version of the fix that does not recur.
 *
 * ⛔ AND THE STRIPPER IS HANDED ITS MISSING DELIMITER — `'/*' . $capture` —
 *    because the capture begins immediately after the block-header TEXT, which
 *    is itself inside a comment. Without the synthesised `/*` the stripper
 *    cannot see the comment it is standing in and hands English prose to the
 *    selector extractor. That is 1.19.329's finding, at its fourth occurrence
 *    in this project; this section is written already carrying the fix.
 */
if ( preg_match( '/SCHOOL READ-ALOUDS — PHOTO CAROUSEL(.*?)(?=\/\* ={10,}\s*\n\s+[A-Z]|$)/s', $bhp_css, $bhp_pc ) ) {
	bhp_sra_assert( true, '⭐ the carousel stylesheet block is present in style.css' );

	$bhp_pc_code = preg_replace( '!/\*.*?\*/!s', '', '/*' . $bhp_pc[1] );

	/* ⛔ EVERY SELECTOR IS SCOPED. Leading whitespace is allowed because four of
	      this block\'s rules live inside `@media` and are indented — an
	      extractor anchored at column 0 would have found a handful and reported
	      green on the rest. That is 1.19.329's SECOND self-inflicted red, and
	      the `>= 5` floor below is the assertion that caught it. */
	/* ⚠️⚠️ FIXED AFTER A RED RUN, RECORDED RATHER THAN QUIETLY REPLACED.
	      The first version of this extractor was `([^\s@{][^{]*)` - and `[^{]`
	      MATCHES A NEWLINE, A SEMICOLON AND A CLOSING BRACE. So after the last
	      rule before an `@media` it ran from `display: none;` through `}` and a
	      blank line to the `@media`'s own `{`, and reported "display: none;"
	      as an unscoped selector. Excluding `;` and `}` from BOTH character
	      classes bounds every match inside one selector, which is the only
	      place a selector can be. */
	preg_match_all( '/^[ \t]*([^\s@{};][^{};]*)\{/m', $bhp_pc_code, $bhp_pc_rules );
	$bhp_pc_n = count( $bhp_pc_rules[1] );
	bhp_sra_assert( $bhp_pc_n >= 15, "the carousel block yields {$bhp_pc_n} rules to check — an extractor that matches nothing asserts nothing" );
	foreach ( $bhp_pc_rules[1] as $bhp_sel ) {
		$bhp_sel = trim( $bhp_sel );
		bhp_sra_assert(
			false !== strpos( $bhp_sel, '.bhp-photo-carousel' ),
			"⛔ selector is scoped to .bhp-photo-carousel: \"{$bhp_sel}\""
		);
	}

	/* Every token it uses must really be declared, or the rule silently
	   resolves to nothing and the component renders unstyled. */
	preg_match_all( '/var\((--[a-z0-9-]+)\)/i', $bhp_pc[1], $bhp_pc_vars );
	foreach ( array_unique( $bhp_pc_vars[1] ) as $bhp_v ) {
		bhp_sra_assert( false !== strpos( $bhp_css, $bhp_v . ':' ), "the carousel block's {$bhp_v} is a declared token" );
	}

	/* ⭐⭐ THE MECHANISM. These four declarations ARE the carousel: without them
	         the rail is a stack of photographs and every control is a no-op.
	         They are asserted individually so a "tidy-up" cannot remove one. */
	bhp_sra_assert( (bool) preg_match( '/scroll-snap-type:\s*x\s+mandatory/', $bhp_pc[1] ), '⭐⭐ the rail declares scroll-snap-type: x mandatory — this is what makes the native swipe land on a whole photograph' );
	bhp_sra_assert( (bool) preg_match( '/overflow-x:\s*auto/', $bhp_pc[1] ), '⭐⭐ the rail is a real horizontal SCROLLER — the swipe is the browser\'s, not a touchstart handler' );
	bhp_sra_assert( (bool) preg_match( '/flex:\s*0\s+0\s+100%/', $bhp_pc[1] ), '⭐ each slide is exactly one rail-width, which is what makes scrollLeft/slideWidth a reliable index' );
	bhp_sra_assert( (bool) preg_match( '/scroll-snap-align:\s*start/', $bhp_pc[1] ), '⭐ slides snap to start' );
	bhp_sra_assert( (bool) preg_match( '/aspect-ratio:\s*16\s*\/\s*9/', $bhp_pc[1] ), '⭐ the stage is a fixed 16/9 box — the height is reserved before any photograph arrives, so nothing shifts' );
	bhp_sra_assert( (bool) preg_match( '/object-fit:\s*contain/', $bhp_pc[1] ), '⭐⭐ object-fit: contain — no photograph is cropped or distorted by the stage; `cover` would have cut ~20% off the reading photograph, where Andrew and the book are' );
	bhp_sra_assert( (bool) preg_match( '/border-radius:\s*var\(--radius-(sm|md|lg)\)/', $bhp_pc[1] ), '⭐ rounded with a brand radius TOKEN, not a literal' );

	/* ⛔ THE THREE ABSENCES THAT PROTECT DOM ORDER. Each of these would sever
	      visual order from source order and let a breakpoint invert the
	      newest-first order the founder asked for. */
	/* ⚠️⚠️ ALSO FIXED AFTER A RED RUN. `(^|[^-])order\s*:` matches the
	      `order:` inside `border: 0;` - the `b` satisfies `[^-]`. This block
	      declares `border: 0` on the arrow, so a correct stylesheet was
	      reported as carrying a forbidden `order:`. A lookbehind for any word
	      character or hyphen is the correct test and cannot be fooled by
	      `border`. */
	bhp_sra_assert( ! preg_match( '/(?<![\w-])order\s*:/m', $bhp_pc_code ), '⛔ no `order:` anywhere in the block — visual order is DOM order' );
	bhp_sra_assert( false === strpos( $bhp_pc_code, 'row-reverse' ), '⛔ no reversed flex direction' );
	bhp_sra_assert( false === strpos( $bhp_pc_code, 'column-reverse' ), '⛔ no reversed column direction' );

	/* The ONE thing that is absolutely positioned is the arrow, and only the
	   arrow. A slide that escaped flow would break the scroller entirely. */
	bhp_sra_assert( ! preg_match( '/\.bhp-photo-carousel__slide\s*\{[^}]*position:\s*absolute/s', $bhp_pc_code ), '⛔ no slide is absolutely positioned' );
	bhp_sra_assert( ! preg_match( '/\.bhp-photo-carousel__rail\s*\{[^}]*position:\s*absolute/s', $bhp_pc_code ), '⛔ the rail is not absolutely positioned' );

	/* Reduced motion is honoured in CSS so the script does not need its own
	   media query — it reads the computed scroll-behavior. */
	bhp_sra_assert( (bool) preg_match( '/prefers-reduced-motion[^{]*\{[^}]*scroll-behavior:\s*auto/s', $bhp_pc[1] ), '⭐ prefers-reduced-motion turns the RAIL\'s own smooth scrolling off' );

	/* Touch targets. Both controls must clear 44px. */
	bhp_sra_assert( 2 <= preg_match_all( '/(width|height):\s*44px/', $bhp_pc[1] ), '⭐ the arrow and the dot both carry a 44px touch target' );
} else {
	bhp_sra_assert( false, 'the carousel stylesheet block parses out of style.css' );
}

/* ⛔ THE BUILT ARTEFACT IS CURRENT. `style.min.css` is what the browser loads;
      a hand-edited style.css with a stale minified twin ships nothing. */
$bhp_min = @file_get_contents( $bhp_theme_dir . '/style.min.css' );
bhp_sra_assert( is_string( $bhp_min ) && false !== strpos( $bhp_min, '.bhp-photo-carousel__rail' ), '⛔ style.min.css was REBUILT and carries the carousel rules' );
/* ⚠️ FIXED AFTER A RED RUN. The first version guessed the minifier's
      output spacing (`scroll-snap-type:x`) and mixed `&&` with `||` without
      parentheses. The minifier actually keeps `scroll-snap-type: x mandatory`
      WITH the space. A whitespace-tolerant regex asserts the DECLARATION
      rather than one minifier's formatting of it. */
bhp_sra_assert( is_string( $bhp_min ) && (bool) preg_match( '/scroll-snap-type:\s*x\s+mandatory/', $bhp_min ), '⛔ the minified artefact kept the snap declaration' );

/* ── 13c · THE TEMPLATE AND THE SCRIPT. ----------------------------------- */

$bhp_pc_tpl = @file_get_contents( $bhp_theme_dir . '/template-parts/media/photo-carousel.php' );
bhp_sra_assert( is_string( $bhp_pc_tpl ) && '' !== $bhp_pc_tpl, 'the carousel template part exists' );

if ( is_string( $bhp_pc_tpl ) && '' !== $bhp_pc_tpl ) {
	/* ⭐⭐ THE PROGRESSIVE-ENHANCEMENT CONTRACT, AND IT IS THE INVERSE OF THE
	         THEME'S OTHER GALLERY. `look-inside.php` prints every slide after the
	         first with `hidden` and relies on JS to reveal them, so a visitor
	         whose script failed sees ONE item. Here NO SLIDE is hidden and the
	         CONTROLS are, because a control that cannot work must not be shown
	         and a photograph that cannot be reached must not be concealed. */
	bhp_sra_assert( ! preg_match( '/data-bhp-pc-slide[^>]*\bhidden\b/s', $bhp_pc_tpl ), '⭐⭐ NO SLIDE is printed `hidden` — every photograph is reachable with the script absent' );
	bhp_sra_assert( (bool) preg_match( '/data-bhp-pc-prev\s+hidden/', $bhp_pc_tpl ), '⭐ the prev arrow is printed `hidden` and is unhidden by the script' );
	bhp_sra_assert( (bool) preg_match( '/data-bhp-pc-next\s+hidden/', $bhp_pc_tpl ), '⭐ the next arrow is printed `hidden` and is unhidden by the script' );
	bhp_sra_assert( (bool) preg_match( '/data-bhp-pc-dots\s+hidden/', $bhp_pc_tpl ), '⭐ the dot strip is printed `hidden` and is unhidden by the script' );

	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, "loading=\"<?php echo \$pc_first ? 'eager' : 'lazy'; ?>\"" ), '⭐ slide 1 is eager and every other slide is LAZY — a six-photograph carousel fetches one photograph on load' );
	/* ⚠️⚠️ SCANNED WITH COMMENTS STRIPPED, AND THIS IS THE FIFTH
	      INSTANCE OF ONE BUG CLASS IN THIS PROJECT (1.19.325, .326, .327,
	      .329, this). The first version searched the RAW template for
	      'fetchpriority' and matched the header comment that PROMISES there is
	      no `fetchpriority` - a raw source scan that cannot tell code from the
	      words about it. Closed the same way it was closed for the stylesheet:
	      strip the comments, then scan. */
	$bhp_pc_tpl_code = (string) preg_replace( '!/\*.*?\*/!s', '', $bhp_pc_tpl );
	bhp_sra_assert( false === strpos( $bhp_pc_tpl_code, 'fetchpriority' ), '⛔ no fetchpriority=high — this carousel is BELOW the fold and must not compete with the hero for LCP' );
	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, 'decoding="async"' ), 'images decode asynchronously' );

	/* ⛔ NO ATTACHMENT COUPLING ANYWHERE. This is why look-inside.php could not
	      simply be called: every one of its slides is wp_get_attachment_image(),
	      and an attachment ID that exists on production renders broken on
	      staging. */
	bhp_sra_assert( false === strpos( $bhp_pc_tpl, 'wp_get_attachment' ), '⛔ no attachment lookup — every src is a theme-asset URL, identical on both environments' );
	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, 'bhp_author_visits_photo_url' ), 'the template resolves URLs through the shipped theme-asset helper' );

	/* Accessibility scaffolding that a later edit could quietly drop. */
	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, 'tabindex="0"' ), '⛔ the rail is focusable — a scroll container is not focusable by default in Firefox, and an unfocusable scroller cannot be driven by the keyboard at all' );
	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, 'aria-live="polite"' ), 'one polite live region announces the slide change' );
	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, 'screen-reader-text' ), 'every control carries an accessible name' );
	bhp_sra_assert( false !== strpos( $bhp_pc_tpl, 'aria-current' ), 'the current dot is marked with aria-current' );
}

$bhp_pc_js = @file_get_contents( $bhp_theme_dir . '/assets/js/photo-carousel.js' );
bhp_sra_assert( is_string( $bhp_pc_js ) && '' !== $bhp_pc_js, 'the carousel script exists' );

if ( is_string( $bhp_pc_js ) && '' !== $bhp_pc_js ) {
	/* ⛔⛔ NO THIRD-PARTY LIBRARY. The brief's hard constraint, asserted rather
	         than promised: no import, no require, no CDN host, no global that
	         belongs to somebody else's slider. */
	foreach ( array( 'import ', 'require(', 'cdn.', 'unpkg', 'jsdelivr', 'Swiper', 'Slick', 'Flickity', 'Glide' ) as $bhp_lib ) {
		bhp_sra_assert( false === strpos( $bhp_pc_js, $bhp_lib ), "⛔ the script contains no '{$bhp_lib}' — vanilla, no third-party carousel" );
	}
	bhp_sra_assert( false !== strpos( $bhp_pc_js, "'use strict'" ), 'strict mode, matching the theme\'s other scripts' );

	/* ⚠️⚠️ SAME FIX, SAME CLASS, on the script: the two assertions
	      below both matched the file's own header comment, which states in
	      prose that there is no `touchstart` handler and no `dataLayer` push.
	      Strip the block comments and the line comments, then scan the code. */
	$bhp_pc_js_code = (string) preg_replace( '!/\*.*?\*/!s', '', $bhp_pc_js );
	$bhp_pc_js_code = (string) preg_replace( '!^\s*//.*$!m', '', $bhp_pc_js_code );

	/* ⭐ THE SWIPE IS THE BROWSER'S. Asserting the ABSENCE of a touch handler is
	      the assertion that matters: a touchstart threshold is what fights the
	      page's vertical scroll, and this component deliberately has none. */
	bhp_sra_assert( false === strpos( $bhp_pc_js_code, 'touchstart' ), '⭐⭐ no touchstart handler — the swipe is the browser\'s own scroll gesture, with real momentum and no conflict with vertical scrolling' );

	bhp_sra_assert( false !== strpos( $bhp_pc_js, 'ArrowRight' ) && false !== strpos( $bhp_pc_js, 'ArrowLeft' ), '⭐ arrow keys step whole slides' );
	bhp_sra_assert( false !== strpos( $bhp_pc_js, "'Home'" ) && false !== strpos( $bhp_pc_js, "'End'" ), 'Home and End jump to the ends' );
	bhp_sra_assert( false !== strpos( $bhp_pc_js, 'data-bhp-photo-carousel' ), 'the script guards on its own root attribute, so a missed enqueue is a no-op rather than an error' );

	/* ⛔ NO ANALYTICS. Adding a second emitter to a component the theme already
	      instruments elsewhere is how two components start double-firing. */
	bhp_sra_assert( false === strpos( $bhp_pc_js_code, 'dataLayer' ), '⛔ the carousel emits no analytics event and touches no dataLayer' );

	/* ⭐ STATE IS MEASURED, NOT STORED. There is no `current` variable a swipe
	      could desynchronise from — which is why a swipe, an arrow, a dot and a
	      trackpad flick all produce identical dot state. */
	bhp_sra_assert( false !== strpos( $bhp_pc_js, 'function activeIndex' ), '⭐ the active slide is MEASURED from scrollLeft, never remembered' );

	/* ⚠️⚠️ THE SETTLE GUARD, AND IT EXISTS BECAUSE OF AN OBSERVED FAILURE,
	      NOT A THEORY. Measured live on staging 1.19.330 at an asserted
	      `innerWidth` of 1440: `scrollTo({ behavior: 'smooth' })` NEVER
	      COMPLETED - `scrollLeft` was still 0 after 2,500ms, through every
	      arrow, every dot and every arrow key. ⭐ THE ROOT CAUSE WAS THE QA
	      ENVIRONMENT: `document.visibilityState` was 'hidden' and the browser
	      had suspended its animation frames. It is NOT established that a
	      visible browser has this problem and nothing here claims it does. The
	      script now asks for smooth and then VERIFIES the rail arrived, jumping
	      it there if it did not - a guard, not a workaround for a proven bug.
	      ⛔ THE SWIPE -> DOT HOP COULD NOT BE OBSERVED AT ALL, and the reason
	      is worth writing down: in that hidden state Chrome dispatches NO
	      `scroll` event whatsoever - measured with a probe listener, a
	      programmatic scroll that moved `scrollLeft` from 0 to 686 produced
	      zero scroll events in 1,500ms. Every OTHER route to `sync()` (arrow,
	      dot, Home, End, arrow keys) WAS observed updating the dot, the counter
	      and the live region, so the function is proven; the unverified hop is
	      the event delivery, and it needs a visible browser.
	   ⭐ These three assertions are what stop that fix being tidied away by
	      someone who reads `jump()` as redundant. */
	bhp_sra_assert( false !== strpos( $bhp_pc_js_code, 'function jump' ), '⭐⭐ the script carries an instant-jump fallback' );
	bhp_sra_assert( (bool) preg_match( '/addEventListener\(\s*.scroll./', $bhp_pc_js_code ), '⭐ a scroll listener keeps the dots, the counter and the live region following a SWIPE, not just the buttons' );
	bhp_sra_assert( (bool) preg_match( '/\{\s*passive:\s*true\s*\}/', $bhp_pc_js_code ), '⛔ the scroll listener is passive - it can never block the scroll it is listening to' );
	bhp_sra_assert( false !== strpos( $bhp_pc_js_code, 'settleTimer' ), '⭐⭐ the script VERIFIES the smooth scroll arrived and forces it if not' );
	bhp_sra_assert( (bool) preg_match( '/Math\.abs\(\s*rail\.scrollLeft\s*-\s*left\s*\)/', $bhp_pc_js_code ), '⭐ the verification compares the real scrollLeft against the target' );
	bhp_sra_assert( false !== strpos( $bhp_pc_js_code, "matchMedia('(prefers-reduced-motion: reduce)')" ), '⭐ reduced motion is read from matchMedia - an explicit statement, not an inference off a computed style' );
	bhp_sra_assert( false === strpos( $bhp_pc_js_code, "behavior: 'auto'" ), '⛔ the script never passes behavior: auto to scrollTo - per CSSOM View that means "use the CSS value", which is exactly how the controls came to inherit an animation that never finished' );
}

/* ── 13d · THE RENDERED PAGE. --------------------------------------------- */

if ( isset( $bhp_html ) && is_string( $bhp_html ) && '' !== $bhp_html ) {
	bhp_sra_assert( false !== strpos( $bhp_html, 'data-bhp-photo-carousel' ), '⭐ the carousel renders on the live page' );
	bhp_sra_assert( false !== strpos( $bhp_html, 'photo-carousel.js' ), '⭐ the carousel script is enqueued on this template' );

	$bhp_n_slides = substr_count( $bhp_html, 'data-bhp-pc-slide=' );
	bhp_sra_assert( $bhp_n_slides === count( $bhp_car ), "the rendered page carries {$bhp_n_slides} slides, one per data row" );
	$bhp_n_dots = substr_count( $bhp_html, 'data-bhp-pc-dot=' );
	bhp_sra_assert( $bhp_n_dots === count( $bhp_car ), "the rendered page carries {$bhp_n_dots} dots, one per slide" );
	bhp_sra_assert( false !== strpos( $bhp_html, 'data-bhp-pc-count="' . count( $bhp_car ) . '"' ), 'the carousel declares its own count' );

	/*
	 * ⛔⛔ AMENDED AT 1.19.337 (`CYCLE170-LD-MICRO`) FOR CARRIER ITEM 552.
	 *
	 * ⭐⭐ FOUNDER, verbatim, 2026-08-30 (RELAYED, read first-hand at the
	 *    carrier): *"put it with the 'About' MAKE THE CAROUSEL A LITTLE SMALLER
	 *    SO IT FITS"*. The carousel now shares one `container--content` with the
	 *    About passage, so the 1000px breakout it used to require is exactly
	 *    what he asked to be undone.
	 *
	 * ⛔ STALE TEST, NOT A REGRESSION — MEASURED, not assumed: the suite was run
	 *    at 1.19.336 and 1.19.337 and the failure lists diffed. This line and one
	 *    other appeared; one disappeared; nothing about the photographs, their
	 *    order, their alt text or the rail changed.
	 *
	 * ⭐ SUPERSEDED ASSERTION, PRESERVED VERBATIM so the 1.19.330 breakout
	 *    decision stays legible rather than being re-derived as a lost feature:
	 *
	 *      bhp_sra_assert( (bool) preg_match( '/readaloud-funnel__gallery.*?container container--wide/s', $bhp_html ),
	 *        '⭐⭐ the carousel section uses container--wide (1000px), not container--content (780px)' );
	 *
	 * ⭐ WHAT REPLACES IT ASSERTS THE NEW ARRANGEMENT POSITIVELY rather than
	 *    simply dropping a check: the gallery block is inside the proof pair's
	 *    grid, and the pair uses `container--content`.
	 */
	bhp_sra_assert(
		(bool) preg_match( '/school-readalouds__proof-grid.*?readaloud-funnel__gallery/s', $bhp_html ),
		'⭐⭐ item 552: the carousel is INSIDE the About + carousel proof pair'
	);
	bhp_sra_assert(
		(bool) preg_match( '/container container--content school-readalouds__proof-grid/', $bhp_html ),
		'⭐ item 552: the pair sits in container--content (780px) - the carousel is sized down, as ruled'
	);
	bhp_sra_assert(
		false === strpos( $bhp_html, 'readaloud-funnel__gallery' ) || 1 === substr_count( $bhp_html, 'data-bhp-photo-carousel' ),
		'⛔ the carousel still renders EXACTLY ONCE - moved, never copied'
	);

	/* ⛔ THE OLD STATIC GRID IS GONE FROM THIS PAGE. If both rendered, the page
	      would show every photograph twice. */
	bhp_sra_assert( false === strpos( $bhp_html, 'author-visits-gallery__item' ), '⛔ the static three-up grid no longer renders on this page — it was REPLACED, not supplemented' );

	/* The heading survived the replacement. */
	bhp_sra_assert( false !== strpos( $bhp_html, 'School read-alouds' ), '⛔ the approved section heading is unchanged' );
	bhp_sra_assert( false !== strpos( $bhp_html, 'id="school-readalouds-gallery-read-alouds"' ), 'the section keeps a school-readalouds-gallery- id, so the §3 order check still resolves' );

	/* Every photograph is on the page, and IN ORDER. */
	$bhp_prev_pos = -1;
	$bhp_seq_ok   = true;
	foreach ( $bhp_car as $bhp_p ) {
		$bhp_at = strpos( $bhp_html, $bhp_p['file'] );
		bhp_sra_assert( false !== $bhp_at, "the rendered page carries {$bhp_p['file']}" );
		if ( false === $bhp_at || $bhp_at < $bhp_prev_pos ) {
			$bhp_seq_ok = false;
		}
		$bhp_prev_pos = (int) $bhp_at;
	}
	bhp_sra_assert( $bhp_seq_ok, '⭐⭐ the photographs appear in the rendered HTML in the SAME order as the data — newest (Adams) first' );

	/* ⛔ AND THE TWO REJECTED PHOTOGRAPHS ARE NOT ON THE PAGE AT ALL. */
	foreach ( $bhp_banned as $bhp_b ) {
		bhp_sra_assert( false === strpos( $bhp_html, $bhp_b ), "⛔⛔ {$bhp_b} does not appear anywhere on the rendered page" );
	}

	/* Alt text reaches the page for every photograph. */
	foreach ( $bhp_car as $bhp_i => $bhp_p ) {
		bhp_sra_assert( false !== strpos( $bhp_html, esc_attr( $bhp_p['alt'] ) ), "the rendered page carries row {$bhp_i}'s alt text" );
	}

	/* ⛔ THE CTA STILL COMES FIRST. The hero and its button are what this page
	      exists for; a gallery that climbed above them would be a regression
	      against the ruling 1.19.329 was built on. */
	$bhp_cta_at = strpos( $bhp_html, 'readaloud-funnel__hero-cta' );
	$bhp_car_at = strpos( $bhp_html, 'data-bhp-photo-carousel' );
	bhp_sra_assert(
		false !== $bhp_cta_at && false !== $bhp_car_at && $bhp_cta_at < $bhp_car_at,
		'⭐⭐ IN THE RENDERED HTML the hero CTA still comes BEFORE the carousel'
	);

	/*
	 * Still no price, fee or rate in the carousel section — item 481 unchanged.
	 *
	 * ⛔⛔ AMENDED AT 1.19.337 (`CYCLE170-LD-MICRO`) FOR CARRIER ITEM 552, AND
	 *     THE AMENDMENT IS TO THE SELECTOR ONLY. The two checks below — no price
	 *     and no "we" — are BYTE-UNCHANGED and still run; only the way the block
	 *     is located had to move.
	 *
	 * ⛔ WHAT ACTUALLY BROKE: this looked for a `<section …readaloud-funnel__gallery…>`.
	 *    Item 552 folds the carousel into the About pair, so the gallery block is
	 *    now a `<div>` inside the proof `<section>` — the regex found no
	 *    `<section>` and fell to the `else`, reporting a PARSE failure on a page
	 *    that renders perfectly. ⭐ THAT IS THE STALE-TEST SIGNATURE, and it was
	 *    confirmed by diffing the 1.19.336 and 1.19.337 failure lists rather than
	 *    by reading the code.
	 *
	 * ⭐ SUPERSEDED MATCHER, PRESERVED VERBATIM:
	 *
	 *      preg_match( '/<section[^>]*readaloud-funnel__gallery.*?<\/section>/s', $bhp_html, $bhp_gs )
	 *
	 * ⭐ THE REPLACEMENT MATCHES EITHER SHAPE — a `<section>` or a `<div>` —
	 *    so this assertion cannot break again the next time the block is moved,
	 *    while still failing loudly if the gallery vanishes altogether.
	 */
	if ( preg_match( '/<(section|div)[^>]*readaloud-funnel__gallery.*?<\/\1>/s', $bhp_html, $bhp_gs ) ) {
		bhp_sra_assert( ! preg_match( '/\$\s?\d/', $bhp_gs[0] ), '⛔ no price, fee or rate appears in the carousel section' );
		bhp_sra_assert( false === stripos( $bhp_gs[0], ' we ' ), '⛔ no "we" in the carousel section (§9.1)' );
	} else {
		bhp_sra_assert( false, 'the carousel section parses out of the rendered page' );
	}
}

/* ------------------------------------------------------------------------ */
$bhp_pass = isset( $GLOBALS['bhp_sra_pass'] ) ? (int) $GLOBALS['bhp_sra_pass'] : 0;
$bhp_fail = isset( $GLOBALS['bhp_sra_fail'] ) ? (int) $GLOBALS['bhp_sra_fail'] : 0;

echo "\n" . str_repeat( '=', 72 ) . "\n";
echo "PASS: {$bhp_pass}   FAIL: {$bhp_fail}\n";
/* A run that asserted nothing at all is a FAILURE, not a pass. */
echo ( 0 === $bhp_fail && $bhp_pass > 0 ) ? "ALL PASS\n" : "FAILURES PRESENT\n";
