<?php
/**
 * CYCLE170-LD-TRIPLE — three founder-sealed changes in one build. Theme
 * 1.19.331 (2026-08-30). STAGING ONLY.
 *
 * The three, each RELAYED THROUGH GANDALF, NOT WITNESSED BY THIS BUILD:
 *
 *   · item 507 — the photo carousel: *"too big for the screen... it should fit
 *     nicely between the nav bar and the bottom of the page to see the entire
 *     box."*
 *   · item 498 — the scheduler: *"look like a calendar that you can scroll by
 *     month."*
 *   · items 504/505 — coupon links: a `?coupon=` link should apply the discount
 *     by itself.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Three of these things can cost money or
 *    tell a customer something false, and those are the sections that matter.
 * ---------------------------------------------------------------------------
 *
 *   · **§3 — the month grid adds NO new selectable day.** The grid is drawn from
 *     whole calendar months, so for the first time the template renders squares
 *     for days that are NOT offered. If a square that should be dead ever
 *     renders a submittable `<input>`, the calendar has quietly widened what the
 *     form accepts. Asserted against a FIXED "today" so the answers never change
 *     with the real calendar.
 *   · **§5 — the coupon feature CREATES NOTHING.** Asserted by reading the
 *     source for every WordPress and WooCommerce write call. This is the
 *     assertion that stands between a URL parameter and Andrew's pricing.
 *   · **§6 — an invalid code is SILENT.** A red error box on a parent's screen,
 *     from a link Andrew sent them, is the failure this feature must not have.
 *   · **§7 — the notice never states an amount it did not read off the coupon.**
 *     A notice that says "10%" merely because a code's NAME ends in ten is a
 *     false price claim on a customer-facing page.
 *
 * ⛔ THE COUNTERS ARE IN $GLOBALS, NOT `global $x`. `wp eval-file` includes this
 *    file INSIDE A FUNCTION, so a top-level variable is that function's LOCAL
 *    and `global $x` binds a different, empty slot — printing "PASS: 0 FAIL: 0 /
 *    ALL PASS" over a visibly failing run. That happened for real on 2026-08-29
 *    (finding F8 of the 1.19.319 candidate).
 *
 * ⛔ THIS SUITE WRITES NOTHING AND SENDS NOTHING. It reads functions, reads both
 *    stylesheets, reads template source and fetches rendered pages. It never
 *    creates a coupon, never applies one to a real cart, never writes an option,
 *    never touches a post and never calls wp_mail().
 *
 * Run: wp eval-file tests/test-cycle170-triple.php --user=1
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
function bhp_tri_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_tri_pass'] ) ) {
		$GLOBALS['bhp_tri_pass'] = 0;
		$GLOBALS['bhp_tri_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_tri_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_tri_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

$bhp_tri_theme = get_template_directory();
$bhp_tri_css   = (string) @file_get_contents( $bhp_tri_theme . '/style.css' );
$bhp_tri_min   = (string) @file_get_contents( $bhp_tri_theme . '/style.min.css' );

/* ═══════════════════════════════════════════════════════════════════════════
   1 · EVERYTHING THIS BUILD ADDED EXISTS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 1 · THE THREE CHANGES LOADED ===\n";

foreach ( array(
	// Item 498 — the month grid.
	'bhp_readaloud_scheduler_build_months',
	'bhp_readaloud_scheduler_months',
	'bhp_readaloud_scheduler_weekday_headings',
	'bhp_readaloud_scheduler_reason_label',
	'bhp_enqueue_readaloud_calendar_assets',
	// Items 504/505 — coupon links.
	'bhp_coupon_url_normalise',
	'bhp_coupon_url_code_is_live',
	'bhp_coupon_url_request_is_eligible',
	'bhp_coupon_url_capture',
	'bhp_coupon_url_maybe_apply',
	'bhp_coupon_url_describe',
	'bhp_coupon_url_render_notice',
) as $fn ) {
	bhp_tri_assert( function_exists( $fn ), "{$fn}() exists" );
}

/* ⛔ THE PRE-EXISTING GATE IS STILL THERE. The month grid is a layout change and
      must not have moved, softened or replaced the server-side date check. */
bhp_tri_assert(
	function_exists( 'bhp_readaloud_scheduler_date_is_offered' ),
	'⭐⭐ the server-side date gate bhp_readaloud_scheduler_date_is_offered() still exists'
);
bhp_tri_assert(
	function_exists( 'bhp_readaloud_scheduler_build_dates' ),
	'⭐ the offered-date list bhp_readaloud_scheduler_build_dates() still exists'
);

bhp_tri_assert(
	file_exists( $bhp_tri_theme . '/assets/js/readaloud-calendar.js' ),
	'assets/js/readaloud-calendar.js ships'
);
bhp_tri_assert(
	file_exists( $bhp_tri_theme . '/assets/js/coupon-notice.js' ),
	'assets/js/coupon-notice.js ships'
);
bhp_tri_assert(
	file_exists( $bhp_tri_theme . '/inc/coupon-url-apply.php' ),
	'inc/coupon-url-apply.php ships'
);

bhp_tri_assert(
	false !== strpos( $bhp_tri_css, 'Version: 1.19.331' ) || '1.19.331' === wp_get_theme()->get( 'Version' ),
	'style.css declares 1.19.331'
);

/* ═══════════════════════════════════════════════════════════════════════════
   2 · ITEM 507 — THE CAROUSEL FITS THE VIEWPORT, AND STILL NEVER CROPS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 2 · ITEM 507 · CAROUSEL VIEWPORT CLAMP ===\n";

bhp_tri_assert(
	false !== strpos( $bhp_tri_css, '--bhp-pc-stage-max' ),
	'the viewport budget custom property is declared'
);
bhp_tri_assert(
	(bool) preg_match( '/\.bhp-photo-carousel__img\s*\{[^}]*max-height:\s*var\(--bhp-pc-stage-max\)/s', $bhp_tri_css ),
	'⭐ the clamp is applied to the IMAGE, which is the element that was too tall'
);
bhp_tri_assert(
	false !== strpos( $bhp_tri_css, '--bhp-pc-nav: 93px' ),
	'the sticky nav is reserved out of the budget (measured 93px at 375)'
);

/* ⛔⛔ THE ONE THAT MATTERS. The brief says "images object-fit: contain within
      (never crop)". A later pass that "fixes" the letterbox bands by switching
      to `cover` would crop about 20% off the archive photograph's height, with
      Andrew and the book near its top edge. */
bhp_tri_assert(
	(bool) preg_match( '/\.bhp-photo-carousel__img\s*\{[^}]*object-fit:\s*contain/s', $bhp_tri_css ),
	'⭐⭐ the carousel image is still object-fit: contain — IT NEVER CROPS'
);
bhp_tri_assert(
	! preg_match( '/\.bhp-photo-carousel__img\s*\{[^}]*object-fit:\s*cover/s', $bhp_tri_css ),
	'⭐⭐ the carousel image is NOT object-fit: cover anywhere'
);

/* The dots and caption live outside the image, so they have to come out of the
   budget or the founder still cannot "see the entire box". */
bhp_tri_assert(
	false !== strpos( $bhp_tri_css, '--bhp-pc-chrome' ),
	'⭐ the caption and dot row are reserved out of the budget, not just the photo'
);

/* `dvh` on a phone, `vh` as the fallback, and the fallback declared FIRST so a
   browser without dvh still gets a clamp rather than none. */
bhp_tri_assert(
	false !== strpos( $bhp_tri_css, '--bhp-pc-viewport: 100vh' )
		&& false !== strpos( $bhp_tri_css, '--bhp-pc-viewport: 100dvh' ),
	'⭐ both 100vh (fallback) and 100dvh (mobile-correct) are declared'
);
bhp_tri_assert(
	strpos( $bhp_tri_css, '--bhp-pc-viewport: 100vh' ) < strpos( $bhp_tri_css, '--bhp-pc-viewport: 100dvh' ),
	'⭐ the vh fallback is declared BEFORE the dvh override, so the cascade is right'
);

/* The minified artefact is what the site actually serves. A rebuild that was
   forgotten is invisible on the page and total at the stylesheet. */
bhp_tri_assert(
	false !== strpos( $bhp_tri_min, '--bhp-pc-stage-max' ),
	'⭐⭐ style.min.css was REBUILT — the clamp is in the artefact the site serves'
);

/* ═══════════════════════════════════════════════════════════════════════════
   3 · ITEM 498 — THE MONTH GRID ADDS A SHAPE AND NOT A PERMISSION
   ═══════════════════════════════════════════════════════════════════════════

   ⛔ EVERY ASSERTION BELOW USES A FIXED "today". The grid's correctness must not
      depend on the day the suite is run, and a horizon boundary that is only
      observable in one particular week is a boundary nobody ever checks.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 3 · ITEM 498 · MONTH GRID ===\n";

/* 2026-09-02 is a Wednesday. Lead 7, horizon 90 — the shipped defaults. */
$bhp_tri_today   = '2026-09-02';
$bhp_tri_months  = bhp_readaloud_scheduler_build_months( $bhp_tri_today, 7, 90 );
$bhp_tri_offered = bhp_readaloud_scheduler_build_dates( $bhp_tri_today, 7, 90 );

bhp_tri_assert( ! empty( $bhp_tri_months ), 'the grid builds at least one month' );

bhp_tri_assert(
	! empty( $bhp_tri_months ) && '2026-09' === $bhp_tri_months[0]['key'],
	'⭐ the CURRENT month is first — the founder\'s "current month first"'
);
bhp_tri_assert(
	! empty( $bhp_tri_months ) && 'September 2026' === $bhp_tri_months[0]['label'],
	'the month label is the human month and year'
);

/* Every week is exactly seven squares, or the weekday columns are a lie. */
$bhp_tri_ragged = 0;
foreach ( $bhp_tri_months as $m ) {
	foreach ( $m['weeks'] as $w ) {
		if ( 7 !== count( $w ) ) {
			++$bhp_tri_ragged;
		}
	}
}
bhp_tri_assert( 0 === $bhp_tri_ragged, '⭐ every week row is exactly 7 squares — the columns are real' );

bhp_tri_assert(
	7 === count( bhp_readaloud_scheduler_weekday_headings() ),
	'there are exactly 7 weekday column headings'
);

/*
 * ⛔⛔ THE ASSERTION THIS WHOLE SECTION EXISTS FOR.
 *
 * The set of squares the grid marks selectable must be EXACTLY the set of dates
 * the server offers. Not a superset (the grid would be showing a day the
 * handler will refuse, which is a broken form) and not a subset (a day Andrew
 * could have taken is unreachable). Compared as sorted lists, both directions.
 */
$bhp_tri_grid_days = array();
foreach ( $bhp_tri_months as $m ) {
	foreach ( $m['weeks'] as $w ) {
		foreach ( $w as $cell ) {
			if ( $cell['selectable'] ) {
				$bhp_tri_grid_days[] = $cell['ymd'];
			}
		}
	}
}
$bhp_tri_server_days = wp_list_pluck( $bhp_tri_offered, 'ymd' );
sort( $bhp_tri_grid_days );
sort( $bhp_tri_server_days );

bhp_tri_assert(
	$bhp_tri_grid_days === $bhp_tri_server_days,
	'⭐⭐ THE GRID OFFERS EXACTLY THE SERVER\'S OWN LIST — no day added, none lost'
);
bhp_tri_assert(
	count( $bhp_tri_grid_days ) === count( $bhp_tri_server_days ),
	sprintf( '⭐ selectable-square count (%d) equals offered-date count (%d)', count( $bhp_tri_grid_days ), count( $bhp_tri_server_days ) )
);

/* The brief calls it a 60-school-day horizon. Recorded rather than asserted to
   an exact number, because the horizon is a filterable judgement, not a ruling. */
bhp_tri_assert(
	count( $bhp_tri_server_days ) >= 55 && count( $bhp_tri_server_days ) <= 65,
	sprintf( 'the horizon is about 60 school days (actual: %d)', count( $bhp_tri_server_days ) )
);

/* ⛔ NO WEEKEND IS EVER SELECTABLE, and every weekend square says why. */
$bhp_tri_bad_weekend = 0;
$bhp_tri_weekends    = 0;
foreach ( $bhp_tri_months as $m ) {
	foreach ( $m['weeks'] as $w ) {
		foreach ( $w as $cell ) {
			if ( $cell['blank'] || '' === $cell['ymd'] ) {
				continue;
			}
			if ( ! bhp_readaloud_scheduler_is_school_day( $cell['ymd'] ) ) {
				++$bhp_tri_weekends;
				if ( $cell['selectable'] || 'weekend' !== $cell['reason'] ) {
					++$bhp_tri_bad_weekend;
				}
			}
		}
	}
}
bhp_tri_assert( $bhp_tri_weekends > 0, sprintf( 'the grid actually contains weekend squares (%d)', $bhp_tri_weekends ) );
bhp_tri_assert( 0 === $bhp_tri_bad_weekend, '⭐⭐ NO weekend square is selectable, and every one is marked "weekend"' );

/* ⛔ THE LEAD EDGE. 2026-09-02 + 7 = 2026-09-09. The 8th is a Tuesday — a real
      school day, and it must still be dead because it is inside the notice
      window. This is the boundary the flat list's own suite already guards, and
      the grid must not have re-opened it. */
$bhp_tri_by_ymd = array();
foreach ( $bhp_tri_months as $m ) {
	foreach ( $m['weeks'] as $w ) {
		foreach ( $w as $cell ) {
			if ( ! $cell['blank'] ) {
				$bhp_tri_by_ymd[ $cell['ymd'] ] = $cell;
			}
		}
	}
}
bhp_tri_assert(
	isset( $bhp_tri_by_ymd['2026-09-08'] ) && ! $bhp_tri_by_ymd['2026-09-08']['selectable'],
	'⭐⭐ a WEEKDAY inside the 7-day lead window (2026-09-08, a Tuesday) is NOT selectable'
);
bhp_tri_assert(
	isset( $bhp_tri_by_ymd['2026-09-08'] ) && 'past' === $bhp_tri_by_ymd['2026-09-08']['reason'],
	'⭐ and it is marked "too soon", which is the truthful reason rather than "weekend"'
);
bhp_tri_assert(
	isset( $bhp_tri_by_ymd['2026-09-09'] ) && $bhp_tri_by_ymd['2026-09-09']['selectable'],
	'⭐ the first day past the lead edge (2026-09-09) IS selectable'
);

/* ⛔ THE FAR EDGE. Anything past the horizon is dead and says so. */
$bhp_tri_last = end( $bhp_tri_server_days );
bhp_tri_assert(
	'' !== (string) $bhp_tri_last && isset( $bhp_tri_by_ymd[ $bhp_tri_last ] ) && $bhp_tri_by_ymd[ $bhp_tri_last ]['selectable'],
	sprintf( 'the last offered day (%s) is selectable', (string) $bhp_tri_last )
);
$bhp_tri_beyond = 0;
foreach ( $bhp_tri_by_ymd as $ymd => $cell ) {
	if ( $ymd > (string) $bhp_tri_last && $cell['selectable'] ) {
		++$bhp_tri_beyond;
	}
}
bhp_tri_assert( 0 === $bhp_tri_beyond, '⭐⭐ NOTHING beyond the horizon is selectable' );

/* Boundary: lead greater than horizon yields no offered day at all, and the
   grid must render the current month rather than exploding or looping. */
$bhp_tri_none = bhp_readaloud_scheduler_build_months( $bhp_tri_today, 120, 30 );
bhp_tri_assert( 1 === count( $bhp_tri_none ), '⭐ lead > horizon: exactly the current month is drawn' );
$bhp_tri_none_sel = 0;
foreach ( $bhp_tri_none[0]['weeks'] as $w ) {
	foreach ( $w as $cell ) {
		if ( $cell['selectable'] ) {
			++$bhp_tri_none_sel;
		}
	}
}
bhp_tri_assert( 0 === $bhp_tri_none_sel, '⭐ lead > horizon: not one square is selectable' );

/* A garbage "today" returns nothing rather than a PHP warning and a broken grid. */
bhp_tri_assert( array() === bhp_readaloud_scheduler_build_months( 'not-a-date', 7, 90 ), 'a malformed "today" yields an empty grid, not a warning' );

/* Every reason has a spoken label, so a dead square is never silently dead. */
foreach ( array( 'weekend', 'past', 'beyond' ) as $bhp_tri_r ) {
	bhp_tri_assert(
		'' !== trim( bhp_readaloud_scheduler_reason_label( $bhp_tri_r ) ),
		"the \"{$bhp_tri_r}\" square has a spoken reason for assistive technology"
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   4 · ITEM 498 — THE RENDERED CALENDAR
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 4 · ITEM 498 · RENDERED MARKUP ===\n";

$bhp_tri_src = (string) @file_get_contents( $bhp_tri_theme . '/inc/school-read-alouds.php' );

/* ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ AMENDED AT 1.19.335 (`CYCLE170-LD-WEEKPICKER`, carrier item 534).
 *     SIX ASSERTIONS IN THIS SECTION MOVED. NOT ONE WAS DELETED, AND THE
 *     ORIGINAL TEXT OF EACH IS QUOTED BESIDE ITS REPLACEMENT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Item 534 replaces the item-498 MONTH GRID with a WEEK PICKER, so six
 * assertions here were measuring markup the template no longer emits: the month
 * arrows, the weekday `<th>` columns, the dead-day `<span>`, the printed-hidden
 * arrow, the live `data-bhp-cal-month` and the live `visit_date` radio group.
 *
 * ⛔ THEY ARE AMENDED RATHER THAN REMOVED BECAUSE EACH ONE GUARDS A PROPERTY
 *    THAT STILL MATTERS — it is the CONTROL that changed, not the property. "An
 *    unofferable day is not a submittable control" is still the rule; at
 *    1.19.335 it holds because an unofferable WEEK is not rendered at all.
 *
 * ⚠ WHAT THIS SECTION NO LONGER PROVES: item 498's month grid is no longer on
 *   `/school-read-alouds/`. `bhp_readaloud_scheduler_build_months()` is still
 *   live code and §2 above still asserts its arithmetic in full — but nothing
 *   renders it today. Stated plainly rather than left for a reader to infer
 *   from a passing suite.
 * ═══════════════════════════════════════════════════════════════════════════ */

bhp_tri_assert( false !== strpos( $bhp_tri_src, 'data-bhp-cal' ), 'the picker root attribute is emitted' );

/* WAS: `'⭐ prev and next month arrows are emitted'` (data-bhp-cal-prev/next).
   The week picker has twelve cards on one screen and needs no pager. */
bhp_tri_assert(
	false === strpos( $bhp_tri_src, 'data-bhp-cal-prev' ) && false === strpos( $bhp_tri_src, 'data-bhp-cal-next' ),
	'⭐ 1.19.335: the month arrows are GONE - a twelve-card list needs no pager'
);

bhp_tri_assert( false !== strpos( $bhp_tri_src, 'readaloud-sched__grid' ), 'the shared grid class is still emitted (the "Who and where" block)' );

/* WAS: `'⭐ weekday columns are real table headers, not styled divs'`
   (`<th scope="col"`). A week picker is one-dimensional, so the honest
   structure is a `<ul>` of cards rather than a table with one real axis. */
bhp_tri_assert(
	false !== strpos( $bhp_tri_src, 'readaloud-sched__weeklist' ) && false !== strpos( $bhp_tri_src, '<li class="readaloud-sched__week"' ),
	'⭐ 1.19.335: the picker is a real <ul>/<li> list, not a table with one axis'
);

/* ⛔⛔ WAS: `'⭐⭐ an unofferable day is a <span>, NOT a disabled input'`.
      THE PROPERTY IS UNCHANGED AND IS WHAT THIS NOW ASSERTS: there must be no
      submittable control in the DOM for a slot the server will refuse. At
      1.19.334 that was guaranteed by rendering a dead day as a `<span>`; at
      1.19.335 it is guaranteed more strongly, by not rendering an unofferable
      week at all. */
bhp_tri_assert(
	false === strpos( $bhp_tri_src, 'readaloud-sched__day--off' ),
	'⭐⭐ 1.19.335: no dead-square markup at all - an unofferable week is simply not a card'
);
bhp_tri_assert(
	! preg_match( '/<input[^>]*name="visit_week[^"]*"[^>]*disabled/i', $bhp_tri_src ),
	'⭐⭐ no disabled week input is ever printed'
);
/* Kept verbatim: the old field name must not reappear anywhere in the source. */
bhp_tri_assert(
	! preg_match( '/name="visit_date"/i', $bhp_tri_src ),
	'⭐⭐ no visit_date input is printed at all any more'
);

/* WAS: `'⭐ the prev arrow is printed hidden and is revealed by the script'`.
   The replacement guards the same underlying rule - a control that cannot work
   is never shown - by asserting the summary line is printed `hidden` and empty. */
bhp_tri_assert(
	(bool) preg_match( '/data-bhp-cal-summary[\s\S]{0,400}?hidden/', $bhp_tri_src ),
	'⭐ 1.19.335: the selection summary is printed hidden and is revealed by the script'
);

/* ⛔ THE STYLESHEET MUST HOLD THE TOUCH FLOOR. The founder asked for
      finger-friendly, and a seven-column grid cannot reflow to stay large. */
bhp_tri_assert(
	(bool) preg_match( '/--bhp-sched-touch:\s*(\d+)px/', $bhp_tri_css, $bhp_tri_touch ) && (int) $bhp_tri_touch[1] >= 44,
	sprintf( '⭐⭐ the day touch target is >= 44px (declared: %s)', isset( $bhp_tri_touch[1] ) ? $bhp_tri_touch[1] . 'px' : 'MISSING' )
);
bhp_tri_assert(
	false !== strpos( $bhp_tri_min, 'readaloud-sched__cal-arrow' ),
	'⭐ style.min.css was rebuilt with the calendar block'
);

/* The page renders, and it renders a calendar. */
$bhp_tri_page = get_page_by_path( function_exists( 'bhp_school_readalouds_slug' ) ? bhp_school_readalouds_slug() : 'school-read-alouds' );
if ( $bhp_tri_page instanceof WP_Post ) {
	$bhp_tri_res  = wp_remote_get( get_permalink( $bhp_tri_page ), array( 'timeout' => 45, 'redirection' => 5 ) );
	$bhp_tri_body = is_wp_error( $bhp_tri_res ) ? '' : (string) wp_remote_retrieve_body( $bhp_tri_res );

	bhp_tri_assert( '' !== $bhp_tri_body, 'the read-aloud page fetches' );

	/* ⛔ AMENDED AT 1.19.335 — three LIVE-page assertions, same treatment as the
	   source assertions above. WAS, in order:
	     '⭐ the LIVE page renders month grids'                     (data-bhp-cal-month)
	     '⭐ the LIVE page renders weekday column headers'           (<th scope="col"> x7)
	     '⭐⭐ the LIVE page still emits the visit_date radio group'
	   Item 534 replaced the grid with week cards. The property each one guarded
	   - the page renders a real, server-generated, submittable picker - is what
	   the three replacements assert. */
	bhp_tri_assert( false === strpos( $bhp_tri_body, 'data-bhp-cal-month' ), '⭐ 1.19.335: the LIVE page renders NO month grid' );
	bhp_tri_assert( substr_count( $bhp_tri_body, 'name="visit_week"' ) >= 10, '⭐ the LIVE page renders at least 10 week cards' );
	bhp_tri_assert( false === strpos( $bhp_tri_body, 'name="visit_date"' ), '⭐⭐ the LIVE page emits NO visit_date radio group' );
	bhp_tri_assert( false !== strpos( $bhp_tri_body, 'name="visit_week_backup"' ), '⭐ the LIVE page emits the backup-week group' );
	bhp_tri_assert( false !== strpos( $bhp_tri_body, 'name="slots[]"' ), '⭐⭐ Morning/Afternoon checkboxes are UNCHANGED beneath the calendar' );
	bhp_tri_assert( false !== strpos( $bhp_tri_body, 'bhp_readaloud_hp' ), '⭐⭐ the HONEYPOT survived the calendar rewrite' );
	bhp_tri_assert( false !== strpos( $bhp_tri_body, 'bhp_readaloud_nonce' ), '⭐⭐ the NONCE survived the calendar rewrite' );
	bhp_tri_assert( false !== strpos( $bhp_tri_body, 'readaloud-calendar.js' ), '⭐ the calendar script is enqueued on the page' );

	/* ⛔ NO PRICE ON THIS PAGE. Read-alouds are free and no price exists to print. */
	bhp_tri_assert(
		! preg_match( '/readaloud-sched__cal[\s\S]{0,4000}?\$\d/', $bhp_tri_body ),
		'⭐ no dollar figure appears inside the calendar'
	);
} else {
	bhp_tri_assert( false, 'the read-aloud page record exists (NOT FOUND — the four render assertions below could not run)' );
}

/* ═══════════════════════════════════════════════════════════════════════════
   5 · ITEMS 504/505 — THE COUPON FEATURE CREATES AND CHANGES NOTHING
   ═══════════════════════════════════════════════════════════════════════════

   ⛔⛔ THIS IS THE SECTION THAT STANDS BETWEEN A URL PARAMETER AND ANDREW'S
       PRICING. Read the source and prove, mechanically, that there is no write.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 5 · ITEMS 504/505 · THE FEATURE WRITES NOTHING ===\n";

$bhp_tri_cpn = (string) @file_get_contents( $bhp_tri_theme . '/inc/coupon-url-apply.php' );
bhp_tri_assert( '' !== $bhp_tri_cpn, 'the coupon source reads' );

/**
 * The source with every comment removed.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS EXISTS BECAUSE THE FIRST VERSION OF THIS SUITE FAILED SIX OF ITS OWN
 *     ASSERTIONS ON A CORRECT BUILD, AND THE HONEST REASON IS WORTH KEEPING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The §5 assertions below prove a NEGATIVE: that `inc/coupon-url-apply.php`
 * contains no write call and no hardcoded coupon code. The first version tested
 * that by grepping the raw file — and the file's own header explains, at length
 * and by name, that it never calls `wp_insert_post`, never calls
 * `update_post_meta`, and deliberately carries no list of campaign codes.
 *
 * ⭐ SO THE SUITE FAILED ON THE DOCUMENTATION OF THE VERY PROPERTY IT WAS
 *    CHECKING. Six FAILs, staging run of 1.19.331, every one a suite defect and
 *    not a build defect: the needles were in comments, never in code.
 *
 * ⛔ THE FIX IS NOT TO SOFTEN THE ASSERTIONS AND IT IS NOT TO EDIT THE COMMENTS.
 *    Both would be the wrong repair — the first weakens the only mechanical
 *    guarantee between a URL parameter and Andrew's pricing, and the second
 *    deletes an explanation a future reader needs in order to keep the property
 *    true. `token_get_all()` is PHP's own lexer: it separates comments from code
 *    exactly the way the interpreter does, so the assertions get stricter here
 *    rather than looser. A grep-based stripper would be a fourth-best regex
 *    guess at something the language already answers precisely.
 *
 * @param string $src PHP source.
 * @return string Code with comments and inline HTML stripped.
 */
function bhp_tri_code_only( $src ) {
	if ( ! function_exists( 'token_get_all' ) ) {
		return $src; // No lexer: fall back to the raw source rather than lying.
	}
	$out = '';
	foreach ( token_get_all( $src ) as $tok ) {
		if ( is_array( $tok ) ) {
			if ( T_COMMENT === $tok[0] || T_DOC_COMMENT === $tok[0] ) {
				continue;
			}
			$out .= $tok[1];
		} else {
			$out .= $tok;
		}
	}
	return $out;
}

$bhp_tri_cpn_code = bhp_tri_code_only( $bhp_tri_cpn );

/* ⭐ THE STRIPPER IS ITSELF ASSERTED, because a stripper that silently returned
      an empty string would make every negative assertion below pass vacuously —
      which is the failure mode of a test that proves an absence. */
bhp_tri_assert(
	strlen( $bhp_tri_cpn_code ) > 1000 && strlen( $bhp_tri_cpn_code ) < strlen( $bhp_tri_cpn ),
	sprintf(
		'⭐⭐ the comment stripper produced real code, not an empty string (%d bytes of code from %d bytes of source)',
		strlen( $bhp_tri_cpn_code ),
		strlen( $bhp_tri_cpn )
	)
);
bhp_tri_assert(
	false !== strpos( $bhp_tri_cpn_code, 'function bhp_coupon_url_maybe_apply' )
		&& false === strpos( $bhp_tri_cpn_code, 'CREATES NOTHING' ),
	'⭐ the stripper kept the code and removed the prose'
);

foreach ( array(
	'wp_insert_post'      => 'no post is ever created',
	'wp_update_post'      => 'no post is ever updated',
	'wp_delete_post'      => 'no post is ever deleted',
	'update_post_meta'    => 'no post meta is ever written',
	'delete_post_meta'    => 'no post meta is ever deleted',
	'update_option'       => 'no option is ever written',
	'delete_option'       => 'no option is ever deleted',
	'update_user_meta'    => 'no user meta is ever written',
	'->set_amount('       => 'no coupon amount is ever set',
	'->set_discount_type' => 'no coupon type is ever set',
	'->set_code('         => 'no coupon code is ever set',
	'->save()'            => 'nothing is ever saved',
	'$wpdb'               => 'no direct database access',
) as $bhp_tri_needle => $bhp_tri_why ) {
	bhp_tri_assert( false === strpos( $bhp_tri_cpn_code, $bhp_tri_needle ), "⭐⭐ {$bhp_tri_why} ({$bhp_tri_needle} absent from the CODE)" );
}

/*
 * ⛔⛔ NO ALLOW-LIST, AND THE CHECK NAMES NO CODE.
 *
 * The brief: "and any future code without enumeration". If a code literal ever
 * appears in the feature, a new coupon Andrew makes will silently fail to work
 * and nobody will know why.
 *
 * ⭐ THE CODES ARE READ OUT OF WOOCOMMERCE, NOT WRITTEN INTO THIS FILE, and that
 *    is a PRIVACY requirement as much as a correctness one. Standing Rules §4.1
 *    lists coupon codes as PRIVATE and never permitted in a public file in any
 *    form — and `C:\BHP\brave-hearts-theme` is a PUBLIC GitHub repository.
 *    Business OS **C6** is the standing instance of exactly this leak.
 *
 * ⭐ IT IS ALSO A STRICTLY STRONGER TEST than the hardcoded list it replaces: it
 *    checks EVERY published coupon on whatever environment it runs, including
 *    codes created after this file was written, rather than four that were known
 *    on one day.
 */
$bhp_tri_all_codes = array();
foreach ( get_posts(
	array(
		'post_type'        => 'shop_coupon',
		'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
		'numberposts'      => 100,
		'suppress_filters' => false,
		'fields'           => 'ids',
	)
) as $bhp_tri_cid2 ) {
	$bhp_tri_t = get_post_field( 'post_title', $bhp_tri_cid2 );
	if ( is_string( $bhp_tri_t ) && strlen( trim( $bhp_tri_t ) ) >= 3 ) {
		$bhp_tri_all_codes[] = trim( $bhp_tri_t );
	}
}

$bhp_tri_leaked = 0;
foreach ( array( 'inc/coupon-url-apply.php', 'functions.php', 'assets/js/coupon-notice.js', 'tests/test-cycle170-triple.php' ) as $bhp_tri_pf ) {
	$bhp_tri_pfs = (string) @file_get_contents( $bhp_tri_theme . '/' . $bhp_tri_pf );
	foreach ( $bhp_tri_all_codes as $bhp_tri_cc ) {
		if ( false !== stripos( $bhp_tri_pfs, $bhp_tri_cc ) ) {
			++$bhp_tri_leaked;
			/* ⛔ The offending CODE is deliberately not printed — this output is
			   filed as evidence, and printing it would commit the leak the
			   assertion exists to catch. The FILE is named; that is enough to
			   fix it. */
			echo "  LEAK  a live coupon code appears in {$bhp_tri_pf}\n";
		}
	}
}
bhp_tri_assert(
	0 === $bhp_tri_leaked,
	sprintf(
		'⭐⭐ NO ALLOW-LIST AND NO LEAK: not one of this environment\'s %d coupon codes appears in any file this build ships (§4.1 — the theme repo is PUBLIC)',
		count( $bhp_tri_all_codes )
	)
);

/* The read-only lookup is the only gate, and both halves of it are present:
   the ID lookup AND the published-status check that makes "disabled" mean
   something. `wc_get_coupon_id_by_code()` returns trashed coupons too. */
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'wc_get_coupon_id_by_code' ), '⭐ the code is looked up against WooCommerce\'s own index' );
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, "'publish' !== \$post->post_status" ), '⭐⭐ a non-published (draft/trashed/disabled) coupon is refused' );
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'is_coupon_valid' ), '⭐⭐ WooCommerce\'s SILENT validator is used, not apply_coupon\'s noisy one' );
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'has_discount' ), '⭐⭐ the already-applied guard exists — no duplicate stacking on revisit' );
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'is_empty()' ), '⭐ the empty-cart case keeps the intent for later' );

/*
 * ⛔⛔ THE REGRESSION GUARD FOR A DEFECT REAL QA FOUND, 2026-08-30, staging.
 *
 * The first build cleared the session immediately after a failed
 * `is_coupon_valid()`. Observed consequence, in a real cart: a visitor opening
 * a link carrying an audience code, with an empty cart, and adding books ONE AT
 * A TIME lost the discount permanently on the FIRST book, because that code carries
 * `_bhp_audience_coupon` and the bundle plugin refuses it until all three titles
 * are present. Books 1 and 2 are refusals; book 3 would have been a yes.
 *
 * ⭐ THE ASSERTION IS STRUCTURAL, not textual: it locates the validation branch
 *    and proves the statement inside it is a bare `return false;` and NOT a
 *    session clear. A comment cannot satisfy it and a reworded comment cannot
 *    break it, because it runs against the comment-stripped code.
 */
$bhp_tri_at = strpos( $bhp_tri_cpn_code, 'is_coupon_valid' );
if ( false !== $bhp_tri_at ) {
	/* ⭐ THE WINDOW IS BOUNDED SEMANTICALLY, NOT BY A CHARACTER COUNT, and the
	   first version of this assertion got that wrong: a fixed 220-character
	   window reached past the refusal branch into the SUCCESS branch, which
	   legitimately DOES clear the session — so the guard failed on correct code.
	   The refusal branch is exactly the code between the validation call and the
	   `apply_coupon()` that follows it. */
	$bhp_tri_apply  = strpos( $bhp_tri_cpn_code, 'apply_coupon', $bhp_tri_at );
	$bhp_tri_branch = false !== $bhp_tri_apply
		? substr( $bhp_tri_cpn_code, $bhp_tri_at, $bhp_tri_apply - $bhp_tri_at )
		: substr( $bhp_tri_cpn_code, $bhp_tri_at, 120 );
	bhp_tri_assert(
		false === strpos( $bhp_tri_branch, 'BHP_COUPON_URL_SESSION_KEY' )
			&& false !== strpos( $bhp_tri_branch, 'return false' ),
		'⭐⭐ A REFUSAL KEEPS THE INTENT — the validation branch is a bare return and does NOT clear the session (guards the 2026-08-30 QA defect: an incremental collection cart lost its discount on book 1)'
	);
} else {
	bhp_tri_assert( false, '⛔ could not locate the is_coupon_valid branch to check the intent is kept' );
}

/* Input handling. */
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'sanitize_text_field' ), '⭐ the URL value is sanitised' );
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'wc_format_coupon_code' ), '⭐ and normalised the way WooCommerce itself normalises' );
bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, 'wp_unslash' ), 'the URL value is unslashed before use' );

/* ⛔⛔ NEVER ECHO RAW. Every echo in the notice is escaped, and the code that is
      printed is read back off the COUPON RECORD rather than off the URL. */
bhp_tri_assert(
	false !== strpos( $bhp_tri_cpn_code, 'esc_html( $phrase )' ),
	'⭐⭐ the notice escapes what it prints'
);
bhp_tri_assert(
	false !== strpos( $bhp_tri_cpn_code, '$coupon->get_code()' ),
	'⭐⭐ the printed code comes from the COUPON RECORD, never from the URL'
);
bhp_tri_assert(
	! preg_match( '/echo\s+\$_GET/', $bhp_tri_cpn ) && ! preg_match( '/echo[^;\n]*BHP_COUPON_URL_PARAM/', $bhp_tri_cpn ),
	'⭐⭐ the raw parameter is never echoed'
);

/* Context guards: a coupon link is a front-end page view, nothing else. */
foreach ( array( 'is_admin()', 'wp_doing_ajax()', 'wp_doing_cron()', 'REST_REQUEST' ) as $bhp_tri_guard ) {
	bhp_tri_assert( false !== strpos( $bhp_tri_cpn_code, $bhp_tri_guard ), "⭐ {$bhp_tri_guard} is excluded" );
}

/* ═══════════════════════════════════════════════════════════════════════════
   6 · ITEMS 504/505 — BEHAVIOUR, ASSERTED WITHOUT TOUCHING A REAL CART
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 6 · ITEMS 504/505 · BEHAVIOUR ===\n";

/* Normalisation: case-insensitive, and junk is refused rather than looked up. */
if ( function_exists( 'wc_format_coupon_code' ) ) {
	bhp_tri_assert(
		bhp_coupon_url_normalise( 'SPRINGSALE' ) === bhp_coupon_url_normalise( 'springsale' ),
		'⭐ an upper-case and a lower-case spelling normalise to the same code'
	);
	bhp_tri_assert( '' === bhp_coupon_url_normalise( '' ), 'an empty parameter normalises to nothing' );
	bhp_tri_assert( '' === bhp_coupon_url_normalise( str_repeat( 'a', 200 ) ), '⭐ an over-long parameter is refused before any lookup' );
	bhp_tri_assert(
		false === strpos( bhp_coupon_url_normalise( '<script>alert(1)</script>' ), '<' ),
		'⭐⭐ markup in the parameter cannot survive normalisation'
	);
}

/*
 * ⛔ THE INVALID-CODE PATH, ASSERTED AGAINST A CODE THAT CANNOT EXIST. This is
 *    the "silently ignore" requirement, and it is checked as a real answer from
 *    the real lookup rather than as a source string.
 */
$bhp_tri_fake = 'bhp-no-such-coupon-' . substr( md5( (string) time() ), 0, 10 );
bhp_tri_assert(
	false === bhp_coupon_url_code_is_live( $bhp_tri_fake ),
	'⭐⭐ AN UNKNOWN CODE IS REFUSED — a typo\'d link does nothing'
);
bhp_tri_assert( false === bhp_coupon_url_code_is_live( '' ), 'an empty code is refused' );

/* And it is refused SILENTLY: no WooCommerce notice was queued by asking. */
if ( function_exists( 'wc_get_notices' ) && function_exists( 'WC' ) && WC()->session ) {
	$bhp_tri_notices = wc_get_notices( 'error' );
	bhp_tri_assert(
		empty( $bhp_tri_notices ),
		'⭐⭐ asking about an unknown code queued NO customer-facing error notice'
	);
}

/*
 * ⭐ THE POSITIVE PATH, AGAINST WHATEVER COUPONS THIS ENVIRONMENT ACTUALLY HAS.
 *
 * ⛔ THIS SUITE DOES NOT CREATE A COUPON TO TEST WITH. It reports what it found
 *    instead. A suite that creates its own fixture proves the fixture works;
 *    reporting "this environment has N published coupons and here is how the
 *    gate answered for each" is the honest version, and on an environment with
 *    none it says so rather than passing vacuously.
 */
$bhp_tri_live_coupons = get_posts(
	array(
		'post_type'        => 'shop_coupon',
		'post_status'      => 'publish',
		'numberposts'      => 10,
		'suppress_filters' => false,
		'fields'           => 'ids',
	)
);
echo '  NOTE  published coupons visible in this environment: ' . count( $bhp_tri_live_coupons ) . "\n";

if ( ! empty( $bhp_tri_live_coupons ) ) {
	$bhp_tri_ok = 0;
	foreach ( $bhp_tri_live_coupons as $bhp_tri_cid ) {
		$bhp_tri_c = new WC_Coupon( $bhp_tri_cid );
		$bhp_tri_k = bhp_coupon_url_normalise( $bhp_tri_c->get_code() );
		if ( '' !== $bhp_tri_k && bhp_coupon_url_code_is_live( $bhp_tri_k ) ) {
			++$bhp_tri_ok;
		}
		/* ⛔ Codes are NOT printed. They are live discounts and this output is
		   filed as evidence in a corpus that has already leaked three of them
		   (Business OS C6). The count is the finding; the code is not. */
	}
	bhp_tri_assert(
		$bhp_tri_ok === count( $bhp_tri_live_coupons ),
		sprintf( '⭐⭐ every published coupon in this environment is accepted by the gate (%d/%d)', $bhp_tri_ok, count( $bhp_tri_live_coupons ) )
	);

	/* The notice phrase, derived from a real coupon record. */
	$bhp_tri_phrase = bhp_coupon_url_describe( new WC_Coupon( $bhp_tri_live_coupons[0] ) );
	bhp_tri_assert( '' !== $bhp_tri_phrase, 'the notice phrase is derived from a real coupon record' );
	bhp_tri_assert(
		false !== strpos( $bhp_tri_phrase, 'discount code' ),
		'⭐ the phrase reads "... discount code CODE", the brief\'s shape'
	);
} else {
	echo "  NOTE  no published coupon exists here, so the positive path was NOT exercised.\n";
	bhp_tri_assert( false, '⛔ NOT VERIFIED: no published coupon in this environment to exercise the accept path against' );
}

/* ⛔ THE AMOUNT IS NEVER INFERRED FROM THE CODE'S NAME. */
bhp_tri_assert(
	false !== strpos( $bhp_tri_cpn_code, '$coupon->get_amount()' ),
	'⭐⭐ the discount amount is READ OFF THE COUPON, never inferred from the code name'
);
bhp_tri_assert(
	false !== strpos( $bhp_tri_cpn_code, "'percent' === \$type" ),
	'⭐ a percentage coupon is described as a percentage'
);

/* ═══════════════════════════════════════════════════════════════════════════
   7 · COPY RAILS ON THE ONE NEW CUSTOMER-FACING STRING
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 7 · COPY RAILS (Standing Rules §9.1) ===\n";

/* ⛔ §9.1: no "we", "us" or "our" in customer-facing words. Andrew is the sole
      operator. Checked against the VISIBLE strings only — the file's comments
      are internal prose, where §9.1 explicitly does not reach. */
preg_match_all( "/(?:esc_html__|esc_html_e|esc_attr__|esc_attr_e|__)\(\s*'([^']+)'/", $bhp_tri_cpn_code, $bhp_tri_strings );
$bhp_tri_visible = isset( $bhp_tri_strings[1] ) ? $bhp_tri_strings[1] : array();
bhp_tri_assert( ! empty( $bhp_tri_visible ), sprintf( 'the coupon notice has translatable visible strings (%d)', count( $bhp_tri_visible ) ) );

$bhp_tri_we = array();
foreach ( $bhp_tri_visible as $bhp_tri_s ) {
	if ( preg_match( '/\b(we|us|our|we\'ve|we\'re)\b/i', $bhp_tri_s ) ) {
		$bhp_tri_we[] = $bhp_tri_s;
	}
}
bhp_tri_assert( empty( $bhp_tri_we ), '⭐⭐ NO "we", "us" or "our" in any customer-facing string (§9.1)' );

$bhp_tri_dash = array();
foreach ( $bhp_tri_visible as $bhp_tri_s ) {
	if ( false !== strpos( $bhp_tri_s, '—' ) || false !== strpos( $bhp_tri_s, '–' ) ) {
		$bhp_tri_dash[] = $bhp_tri_s;
	}
}
bhp_tri_assert( empty( $bhp_tri_dash ), '⭐ no em or en dash in any customer-facing string' );

/* ⛔ NO OUTCOME OR URGENCY CLAIM. The notice states one fact about the cart. */
$bhp_tri_hype = array();
foreach ( $bhp_tri_visible as $bhp_tri_s ) {
	if ( preg_match( '/\b(hurry|expires soon|limited time|don\'t miss|best|guaranteed|proven)\b/i', $bhp_tri_s ) ) {
		$bhp_tri_hype[] = $bhp_tri_s;
	}
}
bhp_tri_assert( empty( $bhp_tri_hype ), '⭐ no urgency or superlative in the notice copy' );

/* ═══════════════════════════════════════════════════════════════════════════
   8 · §26 — AFFILIATE LINKS ARE NEVER TOUCHED
   ═══════════════════════════════════════════════════════════════════════════

   Standing Rules §26 (FD-694, Andrew Signore 2026-08-27, RELAYED): an affiliate
   link is never removed, rewritten or stripped of its tracking code, including
   by a theme deploy. §26.3's count-decrease test: after may never be lower than
   before. §26.4's first-hand baseline: /blog/why-i-wrote-this-book/ carries
   exactly 2 `amzn.to` links, which is why it is the control page.

   ⚠️ §26.3'S OWN STATED LIMIT, CARRIED FORWARD RATHER THAN GLOSSED: an
      `amzn.to` shortlink hides its tag behind a redirect, so a source-side count
      CANNOT confirm the tracking code survived — only that the link did.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 8 · §26 AFFILIATE SWEEP ===\n";

foreach ( array(
	'/blog/why-i-wrote-this-book/' => 2,
	'/school-read-alouds/'         => 0,
	'/read-aloud/'                 => 0,
) as $bhp_tri_path => $bhp_tri_expect ) {
	$bhp_tri_r = wp_remote_get( home_url( $bhp_tri_path ), array( 'timeout' => 45, 'redirection' => 5 ) );
	if ( is_wp_error( $bhp_tri_r ) ) {
		bhp_tri_assert( false, "§26 sweep could not fetch {$bhp_tri_path} — NOT VERIFIED" );
		continue;
	}
	$bhp_tri_b = (string) wp_remote_retrieve_body( $bhp_tri_r );
	$bhp_tri_n = preg_match_all( '/amzn\.to/i', $bhp_tri_b );
	printf(
		"  NOTE  %-32s http=%d  amzn.to=%d  bytes=%d\n",
		$bhp_tri_path,
		(int) wp_remote_retrieve_response_code( $bhp_tri_r ),
		(int) $bhp_tri_n,
		strlen( $bhp_tri_b )
	);
	bhp_tri_assert(
		(int) $bhp_tri_n >= (int) $bhp_tri_expect,
		sprintf( '⭐⭐ §26: %s carries >= %d affiliate links (found %d) — the count did not DECREASE', $bhp_tri_path, $bhp_tri_expect, (int) $bhp_tri_n )
	);
}

/* ⛔ AND THE THREE CHANGES TOUCH NO LINK AT ALL. None of the new source rewrites
      an href, and that is asserted rather than assumed. */
foreach ( array( 'inc/coupon-url-apply.php', 'inc/school-read-alouds.php', 'assets/js/readaloud-calendar.js', 'assets/js/coupon-notice.js' ) as $bhp_tri_f ) {
	$bhp_tri_fs = (string) @file_get_contents( $bhp_tri_theme . '/' . $bhp_tri_f );
	bhp_tri_assert(
		false === strpos( $bhp_tri_fs, 'amzn.to' )
			&& false === strpos( $bhp_tri_fs, 'tag=' )
			&& ! preg_match( '/\.href\s*=/', $bhp_tri_fs ),
		"⭐ §26: {$bhp_tri_f} contains no affiliate string and rewrites no href"
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   9 · NOTHING ELSE MOVED
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 9 · BLAST RADIUS ===\n";

/* ⛔ FUNNEL ISOLATION (.claude/rules/funnels.md). The coupon notice renders on
      every page; it must not have touched either funnel's storage or event
      prefixes. */
foreach ( array( 'bhp_parent_popup', 'bhp_mariana_popup', 'parent_popup', 'teacher_popup' ) as $bhp_tri_key ) {
	bhp_tri_assert(
		false === strpos( $bhp_tri_cpn_code, $bhp_tri_key ),
		"⭐ the coupon feature does not touch the {$bhp_tri_key} funnel namespace"
	);
}

/* ⛔ NO SHIPPING, TAX, PRICE OR PRODUCT SETTING IS READ OR WRITTEN. */
foreach ( array( 'flat_rate', 'shipping_zone', 'woocommerce_calc_taxes', 'set_price', '_stock_status', 'BookVAULT' ) as $bhp_tri_needle ) {
	bhp_tri_assert(
		false === strpos( $bhp_tri_cpn_code, $bhp_tri_needle ),
		"⭐⭐ the coupon feature never touches {$bhp_tri_needle}"
	);
}

/* ⛔ THE BUNDLE PLUGIN'S OWN AUTO-COUPON IS UNTOUCHED AND USES A DIFFERENT KEY.
      Two features applying coupons to one cart must not share a session slot. */
if ( defined( 'BHP_TYP_AUTO_COUPON_SESSION_KEY' ) ) {
	bhp_tri_assert(
		BHP_TYP_AUTO_COUPON_SESSION_KEY !== BHP_COUPON_URL_SESSION_KEY,
		'⭐⭐ the URL coupon and the Complete Collection auto-coupon use DIFFERENT session keys'
	);
	bhp_tri_assert(
		false === strpos( $bhp_tri_cpn_code, 'BHP_TYP_AUTO_COUPON' ),
		'⭐ neither feature reads the other\'s session state'
	);
}

/* ⛔ NO FABRICATED REVIEW OR RATING SCHEMA ANYWHERE IN THE NEW SOURCE. */
foreach ( array( 'inc/coupon-url-apply.php', 'inc/school-read-alouds.php', 'inc/readaloud-scheduler.php' ) as $bhp_tri_f ) {
	$bhp_tri_fs = (string) @file_get_contents( $bhp_tri_theme . '/' . $bhp_tri_f );
	bhp_tri_assert(
		false === strpos( $bhp_tri_fs, 'aggregateRating' ) && false === strpos( $bhp_tri_fs, '"review"' ),
		"⭐ {$bhp_tri_f} emits no rating or review schema"
	);
}

/* ═══════════════════════════════════════════════════════════════════════════ */
$bhp_tri_p = isset( $GLOBALS['bhp_tri_pass'] ) ? (int) $GLOBALS['bhp_tri_pass'] : 0;
$bhp_tri_f = isset( $GLOBALS['bhp_tri_fail'] ) ? (int) $GLOBALS['bhp_tri_fail'] : 0;
echo "\n═══════════════════════════════════════════════\n";
echo "  PASS: {$bhp_tri_p}   FAIL: {$bhp_tri_f}\n";
echo ( 0 === $bhp_tri_f ) ? "  ALL PASS\n" : "  FAILURES ABOVE\n";
echo "═══════════════════════════════════════════════\n";
