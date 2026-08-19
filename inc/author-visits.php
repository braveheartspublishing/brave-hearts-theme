<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * AUTHOR VISITS — THE PUBLIC LIST. Theme 1.19.233 (2026-08-17,
 * `CYCLE162-LD-VISITS-PAGE`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, RELAYED through the Chief of Staff and NOT witnessed by this
 * agent: *"we list the schools dates and times of the read alouds"*, with the
 * per-school links continuing to live in the pre-visit emails.
 *
 * WHAT THIS FILE IS. The data half of `/author-visits/`. It turns the
 * `bhp_school_visits` registry (owned by the bundle plugin's
 * `school-visit-pickup.php`) into an ordered list of rows a template can echo,
 * and it builds each row's shop link. `page-author-visits.php` holds the copy
 * and the markup; this file holds every decision.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE REGISTRY IS THE PLUGIN'S. THIS FILE ONLY READS IT.
 * ---------------------------------------------------------------------------
 * ⛔ NO VISIT DATA IS HARDCODED — no school, no date, no time, no slug. Same
 *    rule as the plugin, asserted structurally in
 *    `tests/test-author-visits-page.php` against this file's own source.
 * ⛔ IT WRITES NOTHING. No option, no meta, no session, no cookie, no order. A
 *    page view of `/author-visits/` is a read.
 * ⛔ IT DOES NOT SET THE VISIT FLAG. Landing on this page does not entitle a
 *    visitor to hand-delivery; clicking a row's button does, because the button
 *    carries `?bhp_visit=` and `bhp_school_visit_capture_intent()` is what
 *    reads it. That separation is deliberate: the entitlement has exactly one
 *    gate and this page is not a second one.
 * ⭐ IT DEGRADES TO THE EMPTY STATE IF THE PLUGIN IS INACTIVE. Every call into
 *    plugin territory is `function_exists()`-guarded, so a deactivated bundle
 *    plugin renders a page with no visits rather than a fatal.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THREE DATES, THREE DIFFERENT QUESTIONS. CONFUSING THEM IS THE ONE REAL BUG
 *    THIS FILE CAN HAVE. ⭐ REWRITTEN AT 1.19.239 — the third one is new.
 * ---------------------------------------------------------------------------
 *   · `date`        — the visit itself. It decides whether the row is LISTED AT
 *                     ALL. Past its date, a visit is history and disappears.
 *   · ONLINE CLOSE  — 00:00 site time on `date - 1`. It decides whether the
 *                     button is LIVE or GREY. Derived, never stored:
 *                     `bhp_school_visit_is_open_on()` in the bundle plugin.
 *   · `cutoff`      — THE STATED DEADLINE, three days before the visit. ⛔ FROM
 *                     1.19.239 IT IS DISPLAY ONLY. It is what the page prints as
 *                     "Order by ..." and what parents were emailed. It gates
 *                     nothing.
 *
 * ⛔ THE GAP BETWEEN THE STATED DEADLINE AND THE ONLINE CLOSE IS DELIBERATE AND
 *    IS NEVER ADVERTISED. Andrew Signore, RELAYED through the Chief of Staff and
 *    NOT witnessed by this agent: *"We say 3 days before but the online cutoff
 *    is 1 day before so they can sneak in after their deadline. Gives them a
 *    time crunch they need to meet."* NO COPY ON THIS PAGE MAY MENTION THE
 *    EXTRA DAY. Full reasoning lives in one place —
 *    `bhp_school_visit_last_order_date()`'s docblock in the plugin.
 *
 * ⭐ SO THERE IS A REAL, DELIBERATE MIDDLE STATE: ordering closed, visit not yet
 *    happened. The row STAYS ON THE PAGE and keeps its full shape — school,
 *    date, time, the stated "Order by ..." line and the button — with the button
 *    GREYED AND UNCLICKABLE rather than removed. Andrew, same relay: *"then just
 *    make the button unclickable - keep it up to keep a trust record of all the
 *    read alouds I will be doing"*. Hiding the row, or stripping the button out
 *    of it, would be worse for the parent reading a QR code taped to a classroom
 *    door the day before the visit.
 *
 * ⛔ THE BUTTON AND THE CHECKOUT OPTION AGREE BY CONSTRUCTION, and 1.19.239
 *    tightened that from "the same comparison written twice" to "the same
 *    function called twice". `open` here is `bhp_school_visit_is_open_on()`,
 *    which is exactly what `bhp_school_visit_resolve()` calls before it will set
 *    the session flag. A button that appears when the flag would be refused is
 *    the failure mode that matters, and `tests/test-author-visits-page.php`
 *    asserts the two agree on the same day rather than trusting that they were
 *    written to.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/** The UTM campaign prefix for a visit link. `visit-<slug>`. */
if ( ! defined( 'BHP_AUTHOR_VISITS_CAMPAIGN_PREFIX' ) ) {
	define( 'BHP_AUTHOR_VISITS_CAMPAIGN_PREFIX', 'visit-' );
}

/**
 * Today, in the SITE's timezone, as `Y-m-d`.
 *
 * ⛔ Prefers the plugin's own `bhp_school_visit_today()` rather than
 *    reimplementing it. Two functions answering "what day is it" that can drift
 *    apart is exactly how the page and the checkout would come to disagree
 *    about whether ordering is still open.
 *
 * @return string
 */
function bhp_author_visits_today() {
	if ( function_exists( 'bhp_school_visit_today' ) ) {
		return bhp_school_visit_today();
	}
	if ( function_exists( 'wp_date' ) ) {
		return wp_date( 'Y-m-d' );
	}
	return gmdate( 'Y-m-d' );
}

/**
 * The last day an order may be placed online, INCLUSIVE. `visit date - 2`.
 *
 * ⛔ A FALLBACK, NOT A SECOND SOURCE OF TRUTH. When the bundle plugin is active
 *    — which it is on both environments — `bhp_school_visit_last_order_date()`
 *    answers this and nothing here runs. This exists so `/author-visits/`
 *    degrades to a correct page rather than a fatal if the plugin is ever
 *    deactivated, exactly as `bhp_author_visits_today()` does for "today".
 *
 * ⛔ IT FAILS CLOSED. An unusable date returns '' and the caller treats that as
 *    closed. A page that greys a button it should not have greyed is a support
 *    email; a page that offers an entitlement the checkout will refuse is a
 *    parent who thinks Andrew is bringing their child a book.
 *
 * @param string $visit_date Registry `date`, `Y-m-d`.
 * @return string `Y-m-d`, or ''.
 */
function bhp_author_visits_last_order_date( $visit_date ) {
	if ( function_exists( 'bhp_school_visit_last_order_date' ) ) {
		return bhp_school_visit_last_order_date( $visit_date );
	}
	$visit_date = (string) $visit_date;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $visit_date ) ) {
		return '';
	}
	// Anchored at midnight UTC so a DST transition inside the interval cannot
	// shift the answer. Same reasoning as the plugin's own helper.
	$ts = strtotime( $visit_date . ' 00:00:00 UTC' );
	return ( false === $ts ) ? '' : gmdate( 'Y-m-d', $ts - ( 2 * DAY_IN_SECONDS ) );
}

/**
 * The shop URL for one visit.
 *
 * `?bhp_visit=<slug>` is the entitlement param and is the only part of this URL
 * that changes behaviour. The three UTM params are measurement only.
 *
 * ⭐ `utm_source` / `utm_medium` / `utm_campaign` TOGETHER, not the campaign
 *    alone. GA4 attributes a session with a campaign but no source/medium to
 *    `(not set)`, which would make the whole point of the tagging — telling
 *    Andrew which school's parents actually ordered — unanswerable. The trio
 *    copies the shape already used by `template-parts/quiz/audience-quiz.php`
 *    (`utm_source=quiz`, `utm_medium=onsite`), so this is the house pattern
 *    rather than a new one.
 *
 * ⭐ THE DESTINATION IS RESOLVED, NOT ASSUMED. WooCommerce's own shop-page
 *    permalink is used when WooCommerce is loaded, so renaming or moving the
 *    shop page cannot silently produce a 404 on a link that is going onto
 *    PRINTED QR CODES. `/shop/` is the fallback, not the first choice.
 *
 * @param string $slug Visit slug. Sanitised here; callers need not pre-clean.
 * @return string Absolute URL, or '' when the slug is unusable.
 */
function bhp_author_visits_shop_url( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return '';
	}

	$base = '';
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$base = (string) wc_get_page_permalink( 'shop' );
	}
	if ( '' === $base || ! is_string( $base ) ) {
		$base = home_url( '/shop/' );
	}

	$param = defined( 'BHP_SCHOOL_VISIT_PARAM' ) ? BHP_SCHOOL_VISIT_PARAM : 'bhp_visit';

	return add_query_arg(
		array(
			$param         => $slug,
			'utm_source'   => 'author-visits',
			'utm_medium'   => 'onsite',
			'utm_campaign' => BHP_AUTHOR_VISITS_CAMPAIGN_PREFIX . $slug,
		),
		$base
	);
}

/**
 * Format a `Y-m-d` for a parent to read. "Friday, August 28".
 *
 * ⛔ NOON, not midnight, in `strtotime()`. A midnight timestamp formatted in a
 *    different timezone can render the previous day, and this string sits next
 *    to a deadline.
 * ⛔ Falls back to the raw `Y-m-d` rather than to an empty string. A parent
 *    seeing "2026-08-28" is inconvenienced; a parent seeing a blank where the
 *    date should be is misinformed.
 *
 * @param string $ymd Date.
 * @return string
 */
function bhp_author_visits_format_date( $ymd ) {
	$ymd = (string) $ymd;
	$ts  = $ymd ? strtotime( $ymd . ' 12:00:00' ) : false;
	if ( ! $ts || ! function_exists( 'wp_date' ) ) {
		return $ymd;
	}
	return (string) wp_date( 'l, F j', $ts );
}

/**
 * Turn registry records into ordered display rows. PURE.
 *
 * ⭐ PURE ON PURPOSE — records and "today" both come in as arguments, so every
 *    date-boundary case (the day before the cutoff, the cutoff itself, the day
 *    after it, the visit day, the day after the visit) is a plain assertion in
 *    the test suite instead of something that can only be observed by waiting.
 *
 * @param array  $records Records shaped like `bhp_school_visit_records()`.
 * @param string $today   `Y-m-d` in the site's timezone.
 * @return array<int,array{slug:string,school:string,date:string,date_display:string,time:string,cutoff:string,open:bool,url:string}>
 */
function bhp_author_visits_build_rows( $records, $today ) {
	if ( ! is_array( $records ) ) {
		return array();
	}
	$today = (string) $today;

	$rows = array();
	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			continue;
		}
		$slug   = isset( $record['slug'] ) ? sanitize_key( (string) $record['slug'] ) : '';
		$school = isset( $record['school'] ) ? trim( wp_strip_all_tags( (string) $record['school'] ) ) : '';
		$date   = isset( $record['date'] ) ? (string) $record['date'] : '';
		$cutoff = isset( $record['cutoff'] ) ? (string) $record['cutoff'] : '';
		$time   = isset( $record['time'] ) ? trim( wp_strip_all_tags( (string) $record['time'] ) ) : '';

		// Anything the plugin's own sanitiser would have dropped is dropped
		// again here rather than trusted, because this function is public and
		// may be handed records from somewhere else one day.
		if ( '' === $slug || '' === $school || '' === $date || '' === $cutoff ) {
			continue;
		}

		// PAST VISITS DISAPPEAR. The comparison is on the VISIT date, not the
		// cutoff, and it is inclusive: the school still appears on the morning
		// of the day Andrew is standing in the classroom.
		if ( '' !== $today && $date < $today ) {
			continue;
		}

		/*
		 * ⭐ 1.19.239 — OPEN IS THE ONLINE CLOSE, NOT THE STATED CUTOFF.
		 *
		 * ⛔ SUPERSEDED LINE, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
		 *    RE-DERIVED:
		 *
		 *        $open = ( '' === $today ) ? true : ( $today <= $cutoff );
		 *
		 * ⛔ THE PLUGIN OWNS THE ANSWER. Calling its function rather than
		 *    re-deriving `date - 2` here is the whole point: two places computing
		 *    the same window is precisely how the page and the checkout would
		 *    come to disagree. The local fallback below exists ONLY so a
		 *    deactivated plugin renders a page instead of a fatal, and it is a
		 *    deliberate, documented duplicate — the same shape as
		 *    `bhp_author_visits_today()` above.
		 */
		if ( function_exists( 'bhp_school_visit_is_open_on' ) ) {
			$open = bhp_school_visit_is_open_on( $date, $today );
		} else {
			$last = bhp_author_visits_last_order_date( $date );
			$open = ( '' === $last ) ? false : ( ( '' === $today ) ? true : ( $today <= $last ) );
		}

		$rows[] = array(
			'slug'         => $slug,
			'school'       => $school,
			'date'         => $date,
			'date_display' => bhp_author_visits_format_date( $date ),
			'time'         => $time,
			'cutoff'       => $cutoff,
			'open'         => $open,
			// ⛔ A CLOSED ROW CARRIES NO URL AT ALL. The string is empty, so a
			//    template CANNOT render a link to an entitlement the site would
			//    refuse — not a hidden one, not a greyed one that is still an
			//    `<a href>`, and not one behind JavaScript.
			// ⭐ 1.19.239: THE BUTTON STILL APPEARS ON A CLOSED ROW, and that is
			//    not a contradiction. `page-author-visits.php` renders a NON-ANCHOR,
			//    non-focusable, `aria-disabled` control for the closed state. It is
			//    a greyed button because Andrew wants the read-aloud to stay on the
			//    page as a trust record; it is not a link because there is no URL.
			'url'          => $open ? bhp_author_visits_shop_url( $slug ) : '',
		);
	}

	// Soonest first. The slug is the tiebreak so two visits on one day have a
	// stable order rather than whatever order the option happened to hold.
	usort(
		$rows,
		static function ( $a, $b ) {
			if ( $a['date'] === $b['date'] ) {
				return strcmp( $a['slug'], $b['slug'] );
			}
			return strcmp( $a['date'], $b['date'] );
		}
	);

	return $rows;
}

/**
 * The rows to render right now, read live from the registry.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_author_visits_rows() {
	$records = function_exists( 'bhp_school_visit_records' ) ? bhp_school_visit_records() : array();

	/**
	 * Filter the rows rendered on `/author-visits/`.
	 *
	 * Exists so the page can be previewed or exercised without writing the
	 * live option. It is NOT a route for hardcoding visits.
	 *
	 * @param array $rows    Built rows.
	 * @param array $records The raw records they came from.
	 */
	return apply_filters(
		'bhp_author_visits_rows',
		bhp_author_visits_build_rows( $records, bhp_author_visits_today() ),
		$records
	);
}
