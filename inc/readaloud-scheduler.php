<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE READ-ALOUD REQUEST SCHEDULER — 1.19.326 (2026-08-30,
 * `CYCLE170-LD-SCHOOL-READALOUD-MERGE`). STAGING ONLY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, founder ruling of Sunday 2026-08-30, relayed in the build
 * brief: *"a way to schedule a read aloud via calendly or a self created
 * calendar schedule that shows morning or Afternoon option on the day they
 * want. That way I can do a morning visit or an afternoon visit ans possibly 2
 * in one day."*
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THIS IS A REQUEST FORM, NOT A BOOKING SYSTEM, AND THE DIFFERENCE IS THE
 *     MOST IMPORTANT THING IN THIS FILE.
 * ---------------------------------------------------------------------------
 * Submitting it books nothing, reserves nothing, and blocks nothing on anyone's
 * calendar. It sends Andrew a message and tells the requester, in plain words on
 * the thank-you state, that the request is TENTATIVE until he replies. ⭐ THAT IS
 * HIS OWN STATED GATE — he confirms manually — and it is why v1 has no
 * availability sync. A page that says "booked" when nothing is booked is the
 * exact trust failure this whole surface exists to avoid.
 *
 * ⭐ WHY SELF-BUILT AND NOT CALENDLY. His ruling permits either. Self-built was
 *    chosen for three reasons, stated so the choice can be reversed knowingly:
 *    the AM/PM-with-both-allowed model is his and is not a shape Calendly's
 *    event types express naturally; the requester's data stays on his own
 *    server rather than in a third party's; and it costs nothing and adds no
 *    external dependency, script or consent surface to a page that currently
 *    has none. ⛔ CALENDLY CAN REPLACE THIS LATER — the form is one section and
 *    one handler, and nothing else on the page depends on it.
 *
 * ---------------------------------------------------------------------------
 * ⛔ NO JAVASCRIPT IS REQUIRED TO USE IT, AND THAT IS DELIBERATE.
 * ---------------------------------------------------------------------------
 * ⚠ 1.19.335: the picker is now WEEK CARDS (carrier item 534). The control is
 *   still exactly what it was — two radio groups (`visit_week`,
 *   `visit_week_backup`) and checkboxes — only the unit changed.
 *
 * A radio group is inherently a one-of-many control and needs no script to
 * behave like one. So the whole scheduler works with JS off, works in a screen
 * reader, and — the reason that actually matters here — **the set of weeks a
 * visitor can choose is generated and re-validated ON THE SERVER.** A JS
 * datepicker that "only allows school days" allows whatever a POST body says.
 *
 * ⭐ TWO INDEPENDENT SLOTS PER DAY, AND BOTH MAY BE TICKED. Morning and
 *    afternoon are CHECKBOXES, not a radio pair, because his ruling explicitly
 *    contemplates two visits in one day. "Either one" is expressed by ticking
 *    both, and the email says so in those words.
 *
 * ---------------------------------------------------------------------------
 * ⛔ NO PRICE, NO FEE, NO RATE, AND NO PROMISE OF ACCEPTANCE APPEARS ANYWHERE
 *    IN THIS FILE. Read-alouds are currently free (carrier item 481) and no
 *    price exists to print. Nothing here says he will say yes.
 * ---------------------------------------------------------------------------
 *
 * ⛔ COPY RAILS. Andrew's I-voice — no "we", "us" or "our" in any visible
 *    string (§9.1). No em dashes. No outcome claim, no reaction, no statistic,
 *    no testimonial. No child, school or librarian is named by this file.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin-post action name for a read-aloud request.
 *
 * ⛔ THE PATTERN IS THE THEME'S OWN, NOT A NEW ONE. `bhp_handle_contact_submit()`
 *    in `inc/audit-remediation.php` already does exactly this shape — an
 *    `admin_post_nopriv_` endpoint, a WordPress nonce, a honeypot, strict
 *    server-side validation, a SERVER-CONTROLLED recipient that never comes
 *    from user input, `wp_mail()` with the visitor's address in `Reply-To` and
 *    the `From` left on this domain, then a redirect back to the page with a
 *    status in the query string. This file reuses that shape deliberately
 *    rather than inventing a second form convention for one page to maintain.
 */
const BHP_READALOUD_REQUEST_ACTION = 'bhp_readaloud_request';

/**
 * How far ahead a visitor may ask for, in days.
 *
 * @return int
 */
function bhp_readaloud_scheduler_horizon_days() {
	$days = (int) apply_filters( 'bhp_readaloud_scheduler_horizon_days', 90 );
	return ( $days > 0 && $days <= 365 ) ? $days : 90;
}

/**
 * The shortest notice accepted, in days.
 *
 * ⛔ NOT ZERO. A school cannot usefully ask him to come tomorrow morning, and an
 *    offer that lets them costs him a message he has to decline. Seven days is
 *    a judgement, it is filterable, and it is not a founder ruling.
 *
 * @return int
 */
function bhp_readaloud_scheduler_lead_days() {
	$days = (int) apply_filters( 'bhp_readaloud_scheduler_lead_days', 7 );
	return ( $days >= 0 && $days <= 120 ) ? $days : 7;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE CALENDAR FLOOR — 1.19.334 (2026-08-30, `CYCLE170-LD-MVP`).
 *     Bundle finding #1, RULED BY `chief-of-staff` from founder items 412 and 429.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THE DEFECT THIS CLOSES IS AN HONESTY DEFECT, NOT A LAYOUT ONE. The hero
 *     lead and the item-522 chip both say **"October onward"**. The rolling
 *     7-day lead offered **September** days. So the page stated a rule and then
 *     offered a teacher days that broke it, and the founder would have had to
 *     decline every September request by hand.
 *
 * ⭐ "October onward" IS HIS ATTESTED AVAILABILITY (items 412 / 429), so the
 *    calendar is brought to the copy rather than the copy to the calendar.
 *    ⛔ THE COPY IS NOT EDITED BY THIS LANE. After this change the hero lead and
 *    the chip are LITERALLY TRUE, which `tests/test-cycle170-mvp.php` asserts as
 *    a consistency check rather than leaving to a careful reading.
 *
 * ⭐⭐ IT EXPIRES BY ITSELF, AND THAT IS WHY IT IS A DATE AND NOT A FLAG. A floor
 *     in the past is a floor that never binds: once the 7-day lead edge passes
 *     2026-10-01 the `max()` below always returns the lead edge, the rolling
 *     horizon resumes untouched, and nobody has to remember to remove anything.
 *     ⛔ A boolean "september_closed" switch would have needed a human to turn it
 *     off, and would have silently closed September 2027 if nobody did.
 *
 * ⛔ THE ROLLING HORIZON LOGIC IS NOT REPLACED. `build_dates()` still measures
 *    90 days from TODAY and still applies the 7-day lead; the floor only removes
 *    days from the front of that range. It cannot ADD a day, so no day can
 *    become offerable that the horizon did not already allow.
 *
 * @return string `Y-m-d`.
 */
function bhp_readaloud_scheduler_floor_date() {
	$floor = (string) apply_filters( 'bhp_readaloud_scheduler_floor_date', '2026-10-01' );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $floor ) ? $floor : '2026-10-01';
}

/**
 * Today, `Y-m-d`, in the SITE's timezone.
 *
 * ⛔ DEFERS TO THE EXISTING CHAIN rather than answering "what day is it" a third
 *    time. `bhp_author_visits_today()` already prefers the bundle plugin's
 *    `bhp_school_visit_today()`. Two functions that can drift apart about the
 *    date is how the visits list and this form would come to disagree.
 *
 * @return string
 */
function bhp_readaloud_scheduler_today() {
	if ( function_exists( 'bhp_author_visits_today' ) ) {
		return bhp_author_visits_today();
	}
	if ( function_exists( 'wp_date' ) ) {
		return (string) wp_date( 'Y-m-d' );
	}
	return gmdate( 'Y-m-d' );
}

/**
 * Is this `Y-m-d` a school day?
 *
 * ⚠️ MONDAY TO FRIDAY, AND THAT IS ALL IT KNOWS. It does NOT know about school
 *   holidays, teacher in-service days, spring break or a district calendar,
 *   and it deliberately does not pretend to: a wrong closure would hide a day a
 *   school actually wanted, and Andrew confirms every request by hand anyway.
 *   A district calendar is a real feature and it is not this one.
 *
 * @param string $ymd Date.
 * @return bool
 */
function bhp_readaloud_scheduler_is_school_day( $ymd ) {
	$ymd = (string) $ymd;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return false;
	}
	// Anchored at midday UTC so no DST transition can move the weekday.
	$ts = strtotime( $ymd . ' 12:00:00 UTC' );
	if ( ! $ts ) {
		return false;
	}
	$dow = (int) gmdate( 'N', $ts ); // 1 = Monday .. 7 = Sunday.
	return $dow >= 1 && $dow <= 5;
}

/**
 * Every date a visitor may choose, in order, grouped-ready.
 *
 * ⭐ PURE ON PURPOSE. "Today" comes in as an argument so every boundary case —
 *    the lead-day edge, a Friday, a Saturday, the last day of the horizon — is
 *    a plain assertion in the suite instead of something that can only be
 *    observed by waiting for a particular date.
 *
 * ⭐ 1.19.334: `$floor` IS A FOURTH ARGUMENT AND DEFAULTS TO `''` (NO FLOOR),
 *    WHICH IS DELIBERATE AND IS NOT AN OVERSIGHT. This function is the PURE one;
 *    every existing boundary assertion in `test-cycle170-school-readaloud.php`
 *    and `test-cycle170-triple.php` calls it with three arguments and fixed
 *    dates, and a defaulted-ON floor would have rewritten what those suites
 *    measure — lead-edge and horizon arithmetic — instead of adding to it.
 *    ⛔ THE LIVE FLOOR IS APPLIED BY THE WRAPPERS `bhp_readaloud_scheduler_dates()`
 *    AND `bhp_readaloud_scheduler_months()`, which are the two functions that
 *    read live config anyway. Everything downstream of them inherits it,
 *    INCLUDING THE SERVER GATE: `bhp_readaloud_scheduler_date_is_offered()`
 *    calls the wrapper, so a POSTed September date is refused by the handler and
 *    not merely absent from the grid.
 *
 * @param string $today  `Y-m-d`.
 * @param int    $lead   Minimum notice, days.
 * @param int    $horizon Days ahead to offer.
 * @param string $floor  Earliest offerable `Y-m-d`, or '' for none.
 * @return array<int,array{ymd:string,weekday:string,day:string,month_key:string,month_label:string}>
 */
function bhp_readaloud_scheduler_build_dates( $today, $lead, $horizon, $floor = '' ) {
	$today = (string) $today;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $today ) ) {
		return array();
	}
	$start = strtotime( $today . ' 12:00:00 UTC' );
	if ( ! $start ) {
		return array();
	}

	/*
	 * ⛔ A MALFORMED FLOOR IS IGNORED, NOT TREATED AS "1970-01-01". A string
	 *    comparison against garbage would silently pass every date, so the shape
	 *    is checked before the value is ever used.
	 */
	$floor = (string) $floor;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $floor ) ) {
		$floor = '';
	}

	/*
	 * ⛔⛔ THE HORIZON IS MEASURED FROM TODAY, NOT FROM THE LEAD EDGE, AND THE
	 *     FIRST VERSION OF THIS LOOP GOT IT WRONG. It read
	 *     `$i <= $lead + $horizon`, which quietly offered `lead` extra days —
	 *     97 rather than 90 at the shipped defaults. Nothing on the page would
	 *     have looked wrong; the list simply ran a week further than the horizon
	 *     it claimed. The suite's boundary assertion is the only reason it was
	 *     found, which is exactly why those assertions use a FIXED "today".
	 *
	 * ⭐ IF `$lead` EXCEEDS `$horizon` THIS YIELDS NOTHING, deliberately. An
	 *    empty list renders the honest empty state rather than a form with no
	 *    day to pick.
	 */
	$out = array();
	for ( $i = (int) $lead; $i <= (int) $horizon; $i++ ) {
		$ts  = $start + ( $i * DAY_IN_SECONDS );
		$ymd = gmdate( 'Y-m-d', $ts );
		if ( ! bhp_readaloud_scheduler_is_school_day( $ymd ) ) {
			continue;
		}
		/*
		 * ⭐ THE FLOOR. `Y-m-d` is lexicographically ordered, so a plain string
		 *    comparison is the date comparison — no parsing, no timezone, no
		 *    second way for this to disagree with the loop above.
		 *
		 * ⛔ IT SKIPS, IT DOES NOT BREAK. The floor removes days from the FRONT
		 *    of the range; breaking would end the range at the first floored day
		 *    and offer nothing at all.
		 */
		if ( '' !== $floor && $ymd < $floor ) {
			continue;
		}
		$out[] = array(
			'ymd'         => $ymd,
			'weekday'     => gmdate( 'D', $ts ),
			'day'         => gmdate( 'j', $ts ),
			'month_key'   => gmdate( 'Y-m', $ts ),
			'month_label' => gmdate( 'F Y', $ts ),
		);
	}
	return $out;
}

/**
 * The selectable dates for right now.
 *
 * @return array<int,array<string,string>>
 */
function bhp_readaloud_scheduler_dates() {
	return bhp_readaloud_scheduler_build_dates(
		bhp_readaloud_scheduler_today(),
		bhp_readaloud_scheduler_lead_days(),
		bhp_readaloud_scheduler_horizon_days(),
		bhp_readaloud_scheduler_floor_date()
	);
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE MONTH GRID — 1.19.331 (`CYCLE170-LD-TRIPLE`, carrier item 498).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, carrier item 498, ⛔ RELAYED THROUGH `chief-of-staff`, NOT WITNESSED BY
 * THIS BUILD: *"look like a calendar that you can scroll by month."*
 *
 * ⭐ THIS FUNCTION ADDS A SHAPE. IT ADDS NO PERMISSION. The set of days a
 *    visitor may choose is still, exactly and only,
 *    `bhp_readaloud_scheduler_build_dates()` — this walks whole calendar months
 *    and asks that list whether each square is selectable. ⛔ A day this grid
 *    calls selectable but that list does not contain CANNOT EXIST, because the
 *    membership test below is a lookup INTO that list rather than a second
 *    implementation of the same rule. That is the whole reason the offered
 *    dates are passed in rather than recomputed here.
 *
 * ⛔ AND THE SERVER GATE IS UNCHANGED AND UNMOVED.
 *    `bhp_readaloud_scheduler_date_is_offered()` still re-derives the list on
 *    POST and still refuses anything outside it. Nothing about drawing a grid
 *    makes the handler trust the browser more than it did at 1.19.326. A
 *    calendar that "only shows school days" still accepts whatever a POST body
 *    contains; that is why the check lives there and not here.
 *
 * ⭐ WEEKS RUN SUNDAY TO SATURDAY. US school convention, and the readers of this
 *    form are US school staff.
 *
 * ⭐ EVERY SQUARE CARRIES WHY IT IS DEAD, not merely that it is. `weekend`,
 *    `past` and `beyond` are three different facts to a teacher looking for a
 *    day, and the template prints a different accessible name for each.
 *
 * ⭐ PURE, AND FOR THE SAME REASON `build_dates()` IS. "Today" is an argument, so
 *    every boundary - the lead edge, the first of a month, a month whose days
 *    have all passed, the last month of the horizon - is a flat assertion in the
 *    suite rather than something only observable on one particular date.
 *
 * ⭐ 1.19.334: `$floor` IS PASSED STRAIGHT THROUGH TO `build_dates()` AND IS
 *    USED FOR NOTHING ELSE HERE. That is the whole reason the floored months
 *    cannot disagree with the floored offer list: this function still asks that
 *    one list whether a square is selectable, so a floored day loses its input
 *    by the same mechanism a weekend does. ⛔ IF A LATER PASS ADDS A SECOND
 *    FLOOR TEST INSIDE THIS LOOP, it has created the divergence the whole
 *    "pass the dates in" design exists to make impossible.
 *
 * @param string $today   `Y-m-d`.
 * @param int    $lead    Minimum notice, days.
 * @param int    $horizon Days ahead to offer.
 * @param string $floor   Earliest offerable `Y-m-d`, or '' for none.
 * @return array<int,array{key:string,label:string,weeks:array,offered:int}>
 */
function bhp_readaloud_scheduler_build_months( $today, $lead, $horizon, $floor = '' ) {
	$today = (string) $today;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $today ) ) {
		return array();
	}

	$dates = bhp_readaloud_scheduler_build_dates( $today, $lead, $horizon, $floor );

	/*
	 * ⛔ A SET, KEYED BY DATE. The membership test has to run once per square of
	 *    every rendered month - roughly 120 squares at the shipped horizon - and
	 *    a linear scan of the offered list per square is quadratic for no reason.
	 *    More importantly it keeps "is this selectable" a single lookup into the
	 *    one authoritative list, which is what makes divergence impossible.
	 */
	$offered = array();
	foreach ( $dates as $row ) {
		$offered[ $row['ymd'] ] = true;
	}

	/*
	 * ⭐ THE CURRENT MONTH IS ALWAYS FIRST AND IS ALWAYS RENDERED, EVEN WHEN IT
	 *    HAS NO SELECTABLE DAY LEFT IN IT. That is the founder's "current month
	 *    first" read literally: he opens the page in August and sees August,
	 *    greyed out, with the arrow pointing at September - not a calendar that
	 *    silently starts in a month he did not ask for and has to reconcile.
	 */
	$first_ts = strtotime( substr( $today, 0, 7 ) . '-01 12:00:00 UTC' );
	if ( ! $first_ts ) {
		return array();
	}

	/* The last month to draw is the month of the last offered date - or, when
	   nothing at all is offered, the current month alone. */
	$last_key = $dates ? substr( $dates[ count( $dates ) - 1 ]['ymd'], 0, 7 ) : substr( $today, 0, 7 );

	$months  = array();
	$cursor  = $first_ts;
	$guard   = 0;
	while ( $guard++ < 24 ) { // A hard stop. 24 months is far beyond any legal horizon.
		$key   = gmdate( 'Y-m', $cursor );
		$label = gmdate( 'F Y', $cursor );

		$days_in = (int) gmdate( 't', $cursor );
		$lead_in = (int) gmdate( 'w', $cursor ); // 0 = Sunday .. 6 = Saturday.

		$cells = array();

		/* Padding squares before the 1st. They are not days and carry no date. */
		for ( $p = 0; $p < $lead_in; $p++ ) {
			$cells[] = array(
				'blank'      => true,
				'ymd'        => '',
				'day'        => '',
				'selectable' => false,
				'reason'     => '',
			);
		}

		for ( $d = 1; $d <= $days_in; $d++ ) {
			$ymd        = sprintf( '%s-%02d', $key, $d );
			$selectable = isset( $offered[ $ymd ] );

			$reason = '';
			if ( ! $selectable ) {
				if ( ! bhp_readaloud_scheduler_is_school_day( $ymd ) ) {
					$reason = 'weekend';
				} elseif ( $dates && $ymd < $dates[0]['ymd'] ) {
					/* ⭐ "past" MEANS "EARLIER THAN THE FIRST DAY I CAN OFFER", not
					   "before today". A weekday inside the lead window is not in
					   the past, but it is equally unpickable, and telling a
					   teacher it is "too soon" is the truthful version. */
					$reason = 'past';
				} elseif ( $dates && $ymd > $dates[ count( $dates ) - 1 ]['ymd'] ) {
					$reason = 'beyond';
				} else {
					$reason = 'past'; // No dates offered at all: everything is unavailable.
				}
			}

			$cells[] = array(
				'blank'      => false,
				'ymd'        => $ymd,
				'day'        => (string) $d,
				'selectable' => $selectable,
				'reason'     => $reason,
			);
		}

		/* Trailing padding, so every week is exactly seven squares and the grid
		   cannot go ragged on the last row. */
		while ( count( $cells ) % 7 !== 0 ) {
			$cells[] = array(
				'blank'      => true,
				'ymd'        => '',
				'day'        => '',
				'selectable' => false,
				'reason'     => '',
			);
		}

		$weeks     = array_chunk( $cells, 7 );
		$n_offered = 0;
		foreach ( $cells as $cell ) {
			if ( $cell['selectable'] ) {
				$n_offered++;
			}
		}

		$months[] = array(
			'key'     => $key,
			'label'   => $label,
			'weeks'   => $weeks,
			'offered' => $n_offered,
		);

		if ( $key === $last_key ) {
			break;
		}
		$next = strtotime( $key . '-01 12:00:00 UTC +1 month' );
		if ( ! $next ) {
			break;
		}
		$cursor = $next;
	}

	return $months;
}

/**
 * The month grid for right now.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_readaloud_scheduler_months() {
	return bhp_readaloud_scheduler_build_months(
		bhp_readaloud_scheduler_today(),
		bhp_readaloud_scheduler_lead_days(),
		bhp_readaloud_scheduler_horizon_days(),
		bhp_readaloud_scheduler_floor_date()
	);
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE WEEK PICKER — 1.19.335 (2026-08-30, `CYCLE170-LD-WEEKPICKER`).
 *      Carrier item **534**, founder-sealed, ⛔ RELAYED THROUGH `chief-of-staff` AND NOT
 *      WITNESSED BY THIS BUILD.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THIS REPLACES THE DAY GRID AS THE THING A VISITOR PICKS, AND THE REASON
 *     IS A FACT ABOUT THE FOUNDER'S LIFE RATHER THAN A UI PREFERENCE.
 *
 * Item 534, verbatim, and it is printed on the page above the picker:
 *
 *   *"I'm an ICU nurse, and my hospital schedule posts about a month at a time.
 *   So pick the week that works for your class, and I'll confirm the exact day
 *   and time as soon as my schedule comes out."*
 *
 * ⭐ A DAY GRID ASKED A TEACHER FOR A COMMITMENT HE CANNOT MATCH. He does not
 *    know his own Tuesdays a month out, so every day-level request was a
 *    negotiation he had to open by hand. A week is the unit he can actually
 *    answer, which is why the granularity moved rather than the copy being
 *    softened around a control that asked the wrong question.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT DID **NOT** CHANGE, AND THIS IS THE LOAD-BEARING PART.
 * ---------------------------------------------------------------------------
 * `bhp_readaloud_scheduler_build_dates()` is the ONE authority on which school
 * days can be asked for, and it is BYTE-UNTOUCHED by this release. So is the
 * floor, so is the lead, so is `build_months()`, and so is
 * `bhp_readaloud_scheduler_date_is_offered()`. ⭐ A WEEK IS DERIVED FROM THAT
 * LIST — it is a grouping of days that were already offerable — so this release
 * CANNOT make a day askable that 1.19.334 refused. The floor, the weekend rule
 * and the lead all reach the week list through the day list, by the same
 * mechanism `build_months()` already uses.
 *
 * ⭐ AND THE SERVER GATE MOVED WITH THE CONTROL, NOT AFTER IT.
 *    `bhp_readaloud_scheduler_week_is_offered()` re-derives the week list on
 *    POST and refuses anything outside it. A posted week from September, a
 *    posted week beyond the horizon and a posted string that merely looks like a
 *    Monday are all rejected by the handler, exactly as a posted September day
 *    was at 1.19.334.
 *
 * @package BraveHearts
 */

/**
 * How many weeks the picker offers.
 *
 * ⛔ THIS IS THE BINDING BOUND, AND THE 90-DAY DAY-HORIZON IS NOT. The brief
 *    asks for a rolling ten-to-twelve weeks; twelve is the top of that range and
 *    it is filterable. `bhp_readaloud_scheduler_horizon_days()` is deliberately
 *    LEFT ALONE at 90 so that every pre-existing boundary assertion in the
 *    1.19.326 / 1.19.331 / 1.19.334 suites still measures the arithmetic it was
 *    written to measure.
 *
 * @return int
 */
function bhp_readaloud_scheduler_week_count() {
	$n = (int) apply_filters( 'bhp_readaloud_scheduler_week_count', 12 );
	return ( $n > 0 && $n <= 52 ) ? $n : 12;
}

/**
 * The raw day range the WEEK list is grouped from, in days.
 *
 * ⛔ A SEPARATE NUMBER FROM `bhp_readaloud_scheduler_horizon_days()`, ON PURPOSE.
 *    112 days is sixteen weeks of raw range, which is comfortably more than the
 *    twelve the cap above will keep — so the WEEK COUNT is what decides how far
 *    ahead the form reaches, and a horizon change cannot silently shorten the
 *    card list to nine weeks the way the 90-day horizon would have today.
 *
 * ⭐ THE CAP IS APPLIED AFTER GROUPING, so a partial first week (the week that
 *    holds the floor) counts as one of the twelve rather than as a fraction.
 *
 * @return int
 */
function bhp_readaloud_scheduler_week_horizon_days() {
	$days = (int) apply_filters( 'bhp_readaloud_scheduler_week_horizon_days', 112 );
	return ( $days > 0 && $days <= 365 ) ? $days : 112;
}

/**
 * The Monday of the week that contains `$ymd`.
 *
 * ⭐ MONDAY, NOT SUNDAY, AND IT IS NOT THE SAME CHOICE THE MONTH GRID MADE. The
 *    month grid draws Sunday-first columns because that is how a US wall
 *    calendar is printed. A SCHOOL week is Monday to Friday, and a card reading
 *    "Week of October 5" that actually began on Sunday the 4th would be a card
 *    whose label disagrees with the week a teacher is thinking about.
 *
 * @param string $ymd Date.
 * @return string `Y-m-d`, or '' if the input is malformed.
 */
function bhp_readaloud_scheduler_week_start( $ymd ) {
	$ymd = (string) $ymd;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return '';
	}
	$ts = strtotime( $ymd . ' 12:00:00 UTC' );
	if ( ! $ts ) {
		return '';
	}
	$dow = (int) gmdate( 'N', $ts ); // 1 = Monday .. 7 = Sunday.
	return gmdate( 'Y-m-d', $ts - ( ( $dow - 1 ) * DAY_IN_SECONDS ) );
}

/**
 * Every week a visitor may ask for. PURE.
 *
 * ⭐ PURE FOR THE SAME REASON `build_dates()` AND `build_months()` ARE. "Today"
 *    comes in as an argument, so the first-week boundary, the floor's partial
 *    week and the twelfth-week cutoff are all flat assertions in the suite
 *    rather than facts only observable on one particular date.
 *
 * ⛔⛔ THE LABEL IS THE FIRST **OFFERABLE** DAY OF THE WEEK, NOT THE MONDAY, AND
 *     THAT IS THE ONE PIECE OF THIS FUNCTION THAT IS A JUDGEMENT RATHER THAN
 *     ARITHMETIC.
 *
 *     Today's first offerable week is the week of Monday **2026-09-28**, because
 *     the 1.19.334 floor opens on Thursday **2026-10-01** and that Thursday
 *     falls inside it. Labelling that card "Week of September 28" would print
 *     the word September on a page whose hero, whose chip and whose calendar all
 *     say October onward — and a teacher reading it would reasonably try to ask
 *     for the 28th. ⭐ Labelling it "Week of October 1" is literally true: the
 *     1st IS the first day of that week he can be asked for. Every full week
 *     after it is labelled by its Monday anyway, because its Monday IS its first
 *     offerable day, which is why the brief's own example ("Week of October 5")
 *     comes out exactly right.
 *
 * ⛔⛔ TWO KEYS, AND THE DISTINCTION IS LOAD-BEARING. `start` IS THE MONDAY AND
 *     IS NEVER POSTED. `value` IS THE WEEK'S FIRST OFFERABLE DAY AND IS WHAT THE
 *     RADIO CARRIES AND WHAT THE GATE CHECKS.
 *
 *     ⭐ THIS WAS CORRECTED DURING THE 1.19.335 BUILD RATHER THAN SHIPPED WRONG,
 *     AND THE REASON IS RECORDED SO IT IS NOT RE-DERIVED. The first draft posted
 *     the MONDAY. Today that Monday is **2026-09-28**, so the rendered page would
 *     have carried `value="2026-09-28"` on a page whose hero, whose item-522 chip
 *     and whose every card label say **October onward** — and
 *     `tests/test-cycle170-mvp.php` asserts, in as many words, that ZERO
 *     `value="2026-09-` strings reach the rendered page. That assertion is the
 *     1.19.334 floor's own safety property, and a week picker that quietly broke
 *     it would have looked exactly like the September defect coming back.
 *
 *     ⛔ POSTING THE FIRST OFFERABLE DAY MAKES THE LABEL, THE POSTED VALUE, THE
 *     GATE AND THE EMAIL'S RAW BRACKET ALL AGREE ON ONE STRING. There is no
 *     second identifier for anything to drift against.
 *
 *     ⚠ THE COST, STATED RATHER THAN HIDDEN: the value CAN move day to day while
 *     the floor is partial (at today=2026-09-25 the first card's value is
 *     2026-10-02, not 2026-10-01), so a form served from a stale cache can post a
 *     value the current list no longer holds. ⭐ THAT IS HANDLED CORRECTLY AND
 *     NOT SILENTLY: the gate refuses it and the visitor gets "Pick a week from
 *     the list." The offer genuinely changed; saying so is the honest response.
 *
 * @param string $today   `Y-m-d`.
 * @param int    $lead    Minimum notice, days.
 * @param int    $horizon Raw days ahead to group from.
 * @param string $floor   Earliest offerable `Y-m-d`, or '' for none.
 * @param int    $cap     Maximum number of weeks to return. 0 for no cap.
 * @return array<int,array{value:string,start:string,label:string,range:string,first:string,last:string,days:array<int,string>,count:int}>
 */
function bhp_readaloud_scheduler_build_weeks( $today, $lead, $horizon, $floor = '', $cap = 0 ) {
	$dates = bhp_readaloud_scheduler_build_dates( $today, $lead, $horizon, $floor );
	if ( ! $dates ) {
		return array();
	}

	/* Group the ALREADY-OFFERED days by their Monday. Insertion order is date
	   order because `build_dates()` yields in date order, so no sort is needed
	   and none is done - a sort would be a second ordering rule that could
	   disagree with the first. */
	$buckets = array();
	foreach ( $dates as $row ) {
		$start = bhp_readaloud_scheduler_week_start( $row['ymd'] );
		if ( '' === $start ) {
			continue;
		}
		if ( ! isset( $buckets[ $start ] ) ) {
			$buckets[ $start ] = array();
		}
		$buckets[ $start ][] = $row['ymd'];
	}

	$fmt = function ( $ymd ) {
		/* ⛔ THE THEME'S ONE DATE FORMATTER, not a second one. A `wp_date()` call
		   with its own format string here is how the card and the email would
		   come to print the same day two different ways. */
		return function_exists( 'bhp_author_visits_format_date' )
			? bhp_author_visits_format_date( $ymd )
			: (string) $ymd;
	};

	$out = array();
	foreach ( $buckets as $start => $days ) {
		$first = $days[0];
		$last  = $days[ count( $days ) - 1 ];

		$ts_first = strtotime( $first . ' 12:00:00 UTC' );
		$label    = $ts_first
			? sprintf(
				/* translators: %s: a date such as "October 5". */
				__( 'Week of %s', 'brave-hearts' ),
				gmdate( 'F j', $ts_first )
			)
			: (string) $start;

		$range = ( $first === $last )
			? $fmt( $first )
			: sprintf(
				/* translators: 1: first school day of the week, 2: last school day of the week. */
				__( '%1$s to %2$s', 'brave-hearts' ),
				$fmt( $first ),
				$fmt( $last )
			);

		$out[] = array(
			/* ⛔ `value` IS WHAT THE FORM POSTS AND WHAT THE GATE CHECKS. `start`
			   is the Monday, kept because it is the grouping key and because it
			   is the stable way to say "which week" in a log or a report - but it
			   is never rendered into an input. See the block comment above. */
			'value' => $first,
			'start' => (string) $start,
			'label' => $label,
			'range' => $range,
			'first' => $first,
			'last'  => $last,
			'days'  => $days,
			'count' => count( $days ),
		);

		if ( $cap > 0 && count( $out ) >= (int) $cap ) {
			break;
		}
	}

	return $out;
}

/**
 * The weeks on offer right now.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_readaloud_scheduler_weeks() {
	return bhp_readaloud_scheduler_build_weeks(
		bhp_readaloud_scheduler_today(),
		bhp_readaloud_scheduler_lead_days(),
		bhp_readaloud_scheduler_week_horizon_days(),
		bhp_readaloud_scheduler_floor_date(),
		bhp_readaloud_scheduler_week_count()
	);
}

/**
 * ⭐⭐ THE SEPTEMBER CARDS — 1.19.336 (`CYCLE170-LD-CHAIN`, founder-sealed items
 *     537 / 538 / 540). ⛔ DISPLAY ONLY. NOT AN OFFER. NOT A CONTROL.
 *
 * ⛔⛔ THESE FOUR CARDS GRANT NOTHING AND CAN GRANT NOTHING. They are a
 *     SEPARATE list from `bhp_readaloud_scheduler_weeks()`, they never reach
 *     `build_dates()`, `week_is_offered()` or `week_by_value()`, and the
 *     template renders them with NO `<input>`, NO `value`, NO `name` and NO
 *     `data-bhp-week`. ⭐ There is therefore nothing here for a devtools panel
 *     to re-enable — the same property the week list earned by not drawing an
 *     unofferable week at all, reached the same way.
 *
 * ⭐ WHY THEY EXIST AT ALL, given the floor already refuses September: a teacher
 *    who opens the page in August and sees the list start at October cannot
 *    tell whether September is FULL or whether the page is simply broken. These
 *    cards answer that question in his own words, and they answer it without
 *    making September askable.
 *
 * ⛔⛔ THE LABELS AND THE STATUSES ARE THE FOUNDER'S, VERBATIM, AND THIS BUILD
 *     AUTHORED NEITHER. Items 537/538/540: "Week of September 1 — Booked",
 *     "Week of September 14 — Booked", "Week of September 21 — Unavailable",
 *     "Week of September 28 — Unavailable". ⛔ RELAYED THROUGH `chief-of-staff` IN THE
 *     BRIEF, NOT WITNESSED FIRST-HAND BY THIS BUILD (§9.2 rule 3).
 *
 * ⛔ NO SCHOOL IS NAMED ON A CARD. The brief records WHY two weeks are booked
 *    (Dallas Harris 9/3 and Liberty 9/4; Amity 9/14) but the sealed card text is
 *    "Booked" and nothing more. Printing a school name here would be copy he did
 *    not approve, on a public page, about a third party.
 *
 * ⛔ THE SPANS ARE STORED, NOT DERIVED. Every `first`/`last` below is written
 *    out so it can be read and checked rather than recomputed from a label by a
 *    rule that could drift. `Week of September 1` is a TUESDAY start (his label,
 *    not a Monday), which is exactly the kind of thing a derivation gets wrong.
 *
 * ⚠️⚠️ ONE OVERLAP, FLAGGED RATHER THAN RESOLVED — see the deploy plan's
 *      findings. "Week of September 28" and the offerable "Week of October 1"
 *      are the SAME Monday-to-Friday calendar week (Mon 28 Sep to Fri 2 Oct).
 *      The two cards do not contradict each other — the September span really is
 *      unavailable and 1 to 2 October really is offerable — and each card states
 *      its OWN span for exactly that reason. ⛔ But whether the founder wants two
 *      cards for one calendar week is HIS call, not this build's, and the sealed
 *      list is shipped unedited until he rules.
 *
 * ⭐ IT EXPIRES BY ITSELF, like the 1.19.334 floor. A card whose `last` day is
 *    already past is dropped, so the whole block empties on its own once October
 *    arrives and nobody has to remember to remove it.
 *
 * @param string $today `Y-m-d` to evaluate against. '' uses the live today.
 * @return array<int,array{label:string,status:string,first:string,last:string}>
 */
function bhp_readaloud_scheduler_closed_weeks( $today = '' ) {
	$today = ( '' === $today ) ? bhp_readaloud_scheduler_today() : (string) $today;

	$rows = array(
		array(
			'label'  => __( 'Week of September 1', 'brave-hearts' ),
			'status' => 'booked',
			'first'  => '2026-09-01',
			'last'   => '2026-09-04',
		),
		array(
			'label'  => __( 'Week of September 14', 'brave-hearts' ),
			'status' => 'booked',
			'first'  => '2026-09-14',
			'last'   => '2026-09-18',
		),
		array(
			'label'  => __( 'Week of September 21', 'brave-hearts' ),
			'status' => 'unavailable',
			'first'  => '2026-09-21',
			'last'   => '2026-09-25',
		),
		array(
			/* ⚠️ Clipped at 30 September on purpose. 1 and 2 October belong to
			   the OFFERABLE "Week of October 1" card that follows, and a span
			   running to 2 October would print a bookable day inside a card
			   marked Unavailable. */
			'label'  => __( 'Week of September 28', 'brave-hearts' ),
			'status' => 'unavailable',
			'first'  => '2026-09-28',
			'last'   => '2026-09-30',
		),
	);

	$out = array();
	foreach ( $rows as $row ) {
		if ( $row['last'] < $today ) {
			continue;
		}
		$out[] = $row;
	}

	/* ⛔ FILTERABLE FOR TESTS AND FOR A FUTURE MONTH, never for adding an
	   offerable week: nothing downstream of this function can make a date
	   askable, so the worst a bad filter can do is print a wrong card. */
	return (array) apply_filters( 'bhp_readaloud_scheduler_closed_weeks', $out, $today );
}

/**
 * The reader-facing status word for a closed card.
 *
 * ⛔ TWO STATUSES, AND NO THIRD. An unknown status returns '' rather than
 *    echoing the raw key, so a typo prints nothing instead of printing
 *    "unavailble" to a teacher.
 *
 * @param string $status `booked` or `unavailable`.
 * @return string Translated word, or '' when the status is not one of the two.
 */
function bhp_readaloud_scheduler_closed_week_status_label( $status ) {
	switch ( (string) $status ) {
		case 'booked':
			return __( 'Booked', 'brave-hearts' );
		case 'unavailable':
			return __( 'Unavailable', 'brave-hearts' );
	}
	return '';
}

/**
 * Is this week one the form actually offered?
 *
 * ⛔⛔ THE SERVER'S ANSWER, AND THE ONLY ONE THAT COUNTS. It re-derives the week
 *     list on POST and checks membership against `value`. It never accepts a
 *     string because it "looks like a date" — a September day, a day from next
 *     year, a day inside the lead window and a mid-week Wednesday are all
 *     refused here, and none of them could have been rendered as a card either.
 *
 * ⛔ IT MATCHES `value` (THE WEEK'S FIRST OFFERABLE DAY), NOT `start` (THE
 *    MONDAY). Matching the Monday would accept a value the form never printed.
 *
 * @param string $value Posted week value.
 * @return bool
 */
function bhp_readaloud_scheduler_week_is_offered( $value ) {
	return null !== bhp_readaloud_scheduler_week_by_value( $value );
}

/**
 * Look one offered week up by the value the form posts.
 *
 * @param string $value Week value (its first offerable day).
 * @return array<string,mixed>|null
 */
function bhp_readaloud_scheduler_week_by_value( $value ) {
	$value = (string) $value;
	if ( '' === $value ) {
		return null;
	}
	foreach ( bhp_readaloud_scheduler_weeks() as $week ) {
		if ( $week['value'] === $value ) {
			return $week;
		}
	}
	return null;
}

/**
 * ⭐⭐ THE HONEST LINE — carrier item 534, VERBATIM, and it is a founder
 *     statement about himself rather than copy this build wrote.
 *
 * ⛔ IT IS RELAYED THROUGH `chief-of-staff` AND WAS NOT WITNESSED BY THIS BUILD, and it
 *    is recorded that way here for the same reason item 498 is: a founder fact
 *    that entered through a brief is evidence of a relay, not of a first-hand
 *    hearing.
 *
 * ⛔ DO NOT EDIT ONE WORD OF IT. It is the reason the picker asks for a week,
 *    and softening it would leave the control asking for a week for no stated
 *    reason. The only characters that differ from the brief's plain-text form
 *    are the two apostrophes, which are U+2019 to match every other visible
 *    string on this page (the 1.19.334 ruling).
 *
 * ⛔ NO "we". His I-voice, §9.1. No em dash. No outcome claim.
 *
 * @return string
 */
function bhp_readaloud_scheduler_honest_line() {
	return __( 'I’m an ICU nurse, and my hospital schedule posts about a month at a time. So pick the week that works for your class, and I’ll confirm the exact day and time as soon as my schedule comes out.', 'brave-hearts' );
}

/**
 * ⭐⭐ THE SEPTEMBER LINE — 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562).
 *     IT REPLACES THE FOUR SEPTEMBER CARDS. IT IS NOT AN ADDITION TO THEM.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT LEFT, AND WHAT DID NOT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHAT LEFT: four `<li>` cards of card UI at the top of the picker, each
 *    carrying a label, a span and a status word ("Booked" / "Unavailable").
 *    They were display-only at 1.19.336 and they are gone from the RENDER only.
 *
 * ⭐ WHAT DID NOT MOVE, AND THIS IS THE PART THAT MATTERS:
 *      · `bhp_readaloud_scheduler_closed_weeks()` STILL RETURNS ALL FOUR ROWS,
 *        unedited, and still self-expires. It is kept rather than deleted
 *        because the founder's sealed items 537/538/540 are a RECORD, and a
 *        future month may want cards again. Deleting the data because one
 *        release stopped drawing it is how a sealed record is lost.
 *      · `bhp_readaloud_scheduler_closed_week_status_label()` is untouched.
 *      · ⛔⛔ THE SERVER FLOOR IS ENTIRELY UNTOUCHED. `floor_date()` is still
 *        2026-10-01, `build_dates()` still refuses every September day, and
 *        `week_is_offered()` still re-derives the list on POST. September was
 *        refused before this change and is refused identically after it. ⭐ THIS
 *        RELEASE REMOVES NO VALIDATION AND ADDS NO PERMISSION. It removes card
 *        UI and prints one sentence.
 *
 * ⭐ WHY A LINE BEATS FOUR CARDS HERE. The cards existed to answer "is September
 *    full, or is this page broken?" (the 1.19.336 reasoning, and it was right).
 *    ⛔ But four greyed rows carrying two DIFFERENT status words at the top of a
 *    twelve-row picker is four rows of card UI spent on a month nobody can pick,
 *    and it is the first thing a teacher's eye lands on. One sentence answers the
 *    same question in the same place and spends no card on it.
 *
 * ⛔⛔ THE WORDS ARE THE FOUNDER'S, RELAYED THROUGH `chief-of-staff` IN THE CARRIER-562
 *     BRIEF, AND ⛔ NOT WITNESSED FIRST-HAND BY THIS BUILD (§9.2 rule 3) — the
 *     same provenance, stated the same way, as item 534's honest line and item
 *     541's fifth visit point.
 *
 * ⚠️⚠️ ONE HONESTY NUANCE, FLAGGED TO `chief-of-staff` RATHER THAN SETTLED HERE, BECAUSE
 *      IT IS A COPY QUESTION AND COPY IS NOT THIS LANE'S TO DECIDE. The sealed
 *      September rows are TWO "Booked" and TWO "Unavailable". "September is full."
 *      is literally true as a statement about AVAILABILITY — no September week can
 *      be asked for, which is the only thing the sentence claims and the only
 *      thing a teacher needs from it. ⚠️ It could also be read as "all four weeks
 *      are booked", which the record does not say. ⛔ THE SEALED LINE IS SHIPPED
 *      UNEDITED. This build does not soften founder copy on its own reading, and
 *      it does not invent a bookings claim by paraphrasing it either. Recorded in
 *      the deploy plan's findings.
 *
 * ⛔ ONE STRING, FROM ONE FUNCTION, SO IT CANNOT DRIFT — the same discipline the
 *    honest line has. This is the only place these words exist in the theme, and
 *    the suite asserts them character by character.
 *
 * ⛔ NO "we". His I-voice, §9.1. No em dash. No outcome claim. No price.
 *
 * @return string
 */
function bhp_readaloud_scheduler_september_line() {
	return __( 'September is full. October onward is open.', 'brave-hearts' );
}

/**
 * ⭐⭐ THE MONTH GROUPING — 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562).
 *     PURE, AND IT DERIVES NOTHING IT COULD READ.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ IT GROUPS ON `value`, NOT ON `start`, AND NOT ON `last`. THE GROUPING KEY
 *     IS THE SAME DAY THE CARD'S OWN LABEL IS PRINTED FROM.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THAT IS THE ONE DECISION IN THIS FUNCTION AND IT IS LOAD-BEARING. Two of
 *    the twelve weeks STRADDLE A MONTH BOUNDARY, and each of the three candidate
 *    keys puts them somewhere different:
 *
 *      · "Week of October 1"    Monday 2026-09-28, first offerable day
 *                               2026-10-01, last 2026-10-02.
 *                               `start` -> SEPTEMBER. `value` -> OCTOBER. ⭐
 *      · "Week of November 30"  Monday 2026-11-30, first offerable day
 *                               2026-11-30, last 2026-12-04.
 *                               `last` -> DECEMBER. `value` -> NOVEMBER. ⭐
 *
 *    ⛔ GROUPING ON `start` WOULD PUT A CARD READING "Week of October 1" UNDER A
 *       SEPTEMBER TAB — on the one page whose whole floor exists to keep the word
 *       September off the offer list. It would re-open, in a tab label, exactly
 *       the defect the 1.19.334 floor closed.
 *    ⛔ GROUPING ON `last` WOULD PUT "Week of November 30" UNDER DECEMBER, so the
 *       card's own label and the tab above it would disagree in front of the
 *       reader.
 *    ⭐ GROUPING ON `value` MAKES THE TAB AND EVERY CARD LABEL UNDER IT AGREE BY
 *       CONSTRUCTION, because `build_weeks()` prints the label from `value` too.
 *       There is no second rule for the two to drift apart under.
 *
 * ⚠️ THE COST, STATED RATHER THAN HIDDEN: the week of 30 November genuinely runs
 *    into December, so a teacher who opens the December tab does not see it. ⭐ Her
 *    card is not hidden from her — it is under November, where its own label says
 *    it is, and its printed span ("Monday, November 30 to Friday, December 4")
 *    states the real days on the card itself. A card in two tabs would be a
 *    second copy of one radio, which is a submittable-duplicate bug, not a
 *    convenience.
 *
 * ⛔ THE MONTH SET IS DERIVED FROM THE WEEKS, NEVER HARDCODED. Nothing here
 *    knows the word "October". When the floor moves, when the cap moves, or when
 *    the calendar simply rolls forward, the tabs follow the offer list and cannot
 *    print a month with no weeks under it or omit one that has weeks.
 *
 * ⛔ INSERTION ORDER IS DATE ORDER AND NO SORT IS DONE. `build_weeks()` yields in
 *    date order, so the buckets come out in date order. A sort here would be a
 *    second ordering rule that could disagree with the first.
 *
 * ⭐ PURE. The weeks come in as an argument, so every property above is a flat
 *    assertion in the suite rather than a fact only observable on one date.
 *
 * @param array<int,array<string,mixed>> $weeks Rows from `build_weeks()`.
 * @return array<int,array{key:string,label:string,full:string,weeks:array<int,array<string,mixed>>}>
 */
function bhp_readaloud_scheduler_group_weeks_by_month( $weeks ) {
	$buckets = array();

	foreach ( (array) $weeks as $week ) {
		if ( ! is_array( $week ) || empty( $week['value'] ) ) {
			continue;
		}
		$value = (string) $week['value'];
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			continue;
		}

		$key = substr( $value, 0, 7 ); // `Y-m`.
		if ( ! isset( $buckets[ $key ] ) ) {
			$ts = strtotime( $value . ' 12:00:00 UTC' );

			$buckets[ $key ] = array(
				'key' => $key,
				/* ⛔ The bare month name is the TAB. Short, because a tab strip of
				   three "October 2026"s wraps on a phone and the year is already
				   unambiguous on every card span underneath. */
				'label' => $ts ? gmdate( 'F', $ts ) : $key,
				/* ⛔ The month AND year is the PANEL's accessible name, because a
				   screen-reader user meets the panel without the card spans in
				   view and "October" alone would not say which October. */
				'full'  => $ts ? gmdate( 'F Y', $ts ) : $key,
				'weeks' => array(),
			);
		}

		$buckets[ $key ]['weeks'][] = $week;
	}

	return array_values( $buckets );
}

/**
 * The optional weekday preferences, Monday to Friday.
 *
 * ⭐ OPTIONAL, AND THE HANDLER TREATS THEM THAT WAY. A teacher who does not know
 *    which days work yet must still be able to send the request; the founder
 *    confirms the exact day by reply either way, so an empty answer costs him a
 *    question he was going to ask anyway and a required answer would cost him
 *    the request.
 *
 * ⛔ MONDAY TO FRIDAY ONLY, which is the same set `is_school_day()` allows. A
 *    sixth key here would be a preference for a day the form can never offer.
 *
 * @return array<string,string>
 */
function bhp_readaloud_scheduler_weekdays() {
	return array(
		'mon' => __( 'Monday', 'brave-hearts' ),
		'tue' => __( 'Tuesday', 'brave-hearts' ),
		'wed' => __( 'Wednesday', 'brave-hearts' ),
		'thu' => __( 'Thursday', 'brave-hearts' ),
		'fri' => __( 'Friday', 'brave-hearts' ),
	);
}

/**
 * The seven column headings, Sunday first.
 *
 * ⚠ 1.19.335: NOTHING ON `/school-read-alouds/` CALLS THIS ANY MORE — the week
 *   picker has no weekday columns. It is kept rather than deleted because
 *   `bhp_readaloud_scheduler_build_months()` is kept, three suites assert it,
 *   and a helper that is one `foreach` away from being needed again costs
 *   nothing to leave in place. Flagged rather than tidied.
 *
 * ⭐ TWO STRINGS PER COLUMN. The short one is printed; the long one is the
 *    column header's accessible name, because "Mo" is not a word a screen
 *    reader should be made to say.
 *
 * @return array<int,array{short:string,full:string}>
 */
function bhp_readaloud_scheduler_weekday_headings() {
	return array(
		array( 'short' => _x( 'Su', 'Sunday, calendar column', 'brave-hearts' ), 'full' => __( 'Sunday', 'brave-hearts' ) ),
		array( 'short' => _x( 'Mo', 'Monday, calendar column', 'brave-hearts' ), 'full' => __( 'Monday', 'brave-hearts' ) ),
		array( 'short' => _x( 'Tu', 'Tuesday, calendar column', 'brave-hearts' ), 'full' => __( 'Tuesday', 'brave-hearts' ) ),
		array( 'short' => _x( 'We', 'Wednesday, calendar column', 'brave-hearts' ), 'full' => __( 'Wednesday', 'brave-hearts' ) ),
		array( 'short' => _x( 'Th', 'Thursday, calendar column', 'brave-hearts' ), 'full' => __( 'Thursday', 'brave-hearts' ) ),
		array( 'short' => _x( 'Fr', 'Friday, calendar column', 'brave-hearts' ), 'full' => __( 'Friday', 'brave-hearts' ) ),
		array( 'short' => _x( 'Sa', 'Saturday, calendar column', 'brave-hearts' ), 'full' => __( 'Saturday', 'brave-hearts' ) ),
	);
}

/**
 * Why a square cannot be picked, as a sentence for assistive technology.
 *
 * ⛔ NO REASON STRING PROMISES A DAY WILL EVER OPEN. "beyond" says how far ahead
 *    the form goes; it does not say to come back later.
 *
 * @param string $reason One of weekend|past|beyond.
 * @return string
 */
function bhp_readaloud_scheduler_reason_label( $reason ) {
	switch ( (string) $reason ) {
		case 'weekend':
			return __( 'Not a school day', 'brave-hearts' );
		case 'past':
			return __( 'Too soon to ask for', 'brave-hearts' );
		case 'beyond':
			return __( 'Further ahead than I am taking requests', 'brave-hearts' );
	}
	return __( 'Not available', 'brave-hearts' );
}

/**
 * Is this date one the form actually offered?
 *
 * ⛔ THE SERVER'S ANSWER, AND THE ONLY ONE THAT COUNTS. The handler re-derives
 *    the list and checks membership; it never trusts the posted value because
 *    it "looks like a weekday".
 *
 * @param string $ymd Posted date.
 * @return bool
 */
function bhp_readaloud_scheduler_date_is_offered( $ymd ) {
	$ymd = (string) $ymd;
	foreach ( bhp_readaloud_scheduler_dates() as $row ) {
		if ( $row['ymd'] === $ymd ) {
			return true;
		}
	}
	return false;
}

/**
 * The two slots. Keys are stored and emailed; labels are shown.
 *
 * @return array<string,string>
 */
function bhp_readaloud_scheduler_slots() {
	return array(
		'morning'   => __( 'Morning', 'brave-hearts' ),
		'afternoon' => __( 'Afternoon', 'brave-hearts' ),
	);
}

/**
 * The cities inside the stated service area, plus the honest escape hatch.
 *
 * ⭐ THE FOUR NAMED CITIES ARE THE BRIEF'S, and the brief's are the founder's.
 *    `other` is not a fifth city: it is the branch that tells a school outside
 *    the area the truth before they spend time on a form.
 *
 * ⛔ NO MILEAGE FIGURE IS PRINTED. Carrier item 309 carries a 25 mile radius,
 *    and whether that number is stated publicly is an OPEN founder decision
 *    (the `marketing-growth` read-back sheet, decision 5). Printing it here would decide it
 *    for him. The wording below states the four cities and stops.
 *
 * @return array<string,string>
 */
function bhp_readaloud_scheduler_cities() {
	return apply_filters(
		'bhp_readaloud_scheduler_cities',
		array(
			'boise'    => __( 'Boise', 'brave-hearts' ),
			'meridian' => __( 'Meridian', 'brave-hearts' ),
			'nampa'    => __( 'Nampa', 'brave-hearts' ),
			'eagle'    => __( 'Eagle', 'brave-hearts' ),
			'other'    => __( 'Somewhere else', 'brave-hearts' ),
		)
	);
}

/**
 * The warning shown when "Somewhere else" is chosen.
 *
 * ⛔ IT WARNS, IT DOES NOT REFUSE. The form still submits. Turning a school away
 *    at the door on a rule no founder statement supports would lose him a visit
 *    he might have wanted to take.
 *
 * @return string
 */
function bhp_readaloud_scheduler_area_note() {
	return __( 'I read in the Boise area. If your school is somewhere else, send the request anyway and I will tell you honestly whether I can get there.', 'brave-hearts' );
}

/**
 * Where the form posts.
 *
 * @return string
 */
function bhp_readaloud_scheduler_form_action() {
	return admin_url( 'admin-post.php' );
}

/**
 * The address a request is sent to.
 *
 * ⛔⛔ SERVER-CONTROLLED, ALWAYS. It is never read from the POST body, never
 *     from a query string, and never from a hidden field. A form that carries
 *     its own recipient is an open relay with a nice stylesheet.
 *
 * @return string
 */
function bhp_readaloud_scheduler_recipient() {
	$to = sanitize_email( (string) apply_filters( 'bhp_readaloud_request_recipient', get_option( 'admin_email' ) ) );
	if ( ! is_email( $to ) ) {
		$to = 'andrew@braveheartspublishing.com';
	}
	return $to;
}

/**
 * The option the STAGING capture writes to.
 */
const BHP_READALOUD_REQUEST_LOG_OPTION = 'bhp_readaloud_request_log';

/**
 * How many captured requests are kept.
 */
const BHP_READALOUD_REQUEST_LOG_MAX = 20;

/**
 * Should this request's mail be CAPTURED instead of sent?
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS EXISTS BECAUSE QA HAS ALREADY MAILED ANDREW ONCE, AND THE EMAILS
 *     COULD NOT BE UNSENT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ The purchase-flow QA round of 2026-08-21 put real staging orders through
 *    the real checkout and fired real WooCommerce admin emails into his inbox.
 *    `inc/staging-mail-guard.php` was written that day and closes it — but ONLY
 *    for WooCommerce `WC_Email` classes, through
 *    `woocommerce_email_enabled_{$id}`. ⛔ A hand-rolled `wp_mail()` like this
 *    one walks straight past that guard, exactly as that file's own header warns
 *    about the review-ask email. So this form would have mailed him on every
 *    single QA submission.
 *
 * ⭐ THE DETECTOR IS THE EXISTING ONE, NOT A SECOND OPINION.
 *    `bhp_staging_mail_guard_is_staging()` is reused verbatim, so there is one
 *    definition of "is this staging" in the theme and it FAILS TOWARDS
 *    PRODUCTION: no detector, or any host that is not the staging literal,
 *    means the mail is SENT normally. There is no value of any option, constant
 *    or environment variable that can make a real request from a real teacher on
 *    braveheartspublishing.com be swallowed.
 *
 * ⛔ CAPTURE IS NOT SILENCE. The captured message is stored in full — recipient,
 *    subject, body, headers, timestamp — so QA can read exactly what would have
 *    been sent, and an admin-bar node says on screen that the capture is on.
 *    A suppression nobody can see is a trap; that is the same lesson the mail
 *    guard's own admin-bar node was written for.
 *
 * @return bool
 */
function bhp_readaloud_request_should_capture() {
	if ( ! function_exists( 'bhp_staging_mail_guard_is_staging' ) ) {
		return false; // ⛔ No detector, no capture. Fail towards production.
	}
	return (bool) apply_filters( 'bhp_readaloud_request_capture', bhp_staging_mail_guard_is_staging() );
}

/**
 * Store one captured request.
 *
 * @param string   $to      Recipient.
 * @param string   $subject Subject.
 * @param string   $body    Body.
 * @param string[] $headers Headers.
 * @return void
 */
function bhp_readaloud_request_capture_store( $to, $subject, $body, $headers ) {
	$log = get_option( BHP_READALOUD_REQUEST_LOG_OPTION, array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift(
		$log,
		array(
			'captured_at' => gmdate( 'c' ),
			'to'          => (string) $to,
			'subject'     => (string) $subject,
			'body'        => (string) $body,
			'headers'     => array_map( 'strval', (array) $headers ),
		)
	);
	update_option( BHP_READALOUD_REQUEST_LOG_OPTION, array_slice( $log, 0, BHP_READALOUD_REQUEST_LOG_MAX ), false );
}

/**
 * Read the capture log.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_readaloud_request_log() {
	$log = get_option( BHP_READALOUD_REQUEST_LOG_OPTION, array() );
	return is_array( $log ) ? $log : array();
}

/**
 * Compose the request email. PURE.
 *
 * ⭐ SEPARATED FROM THE HANDLER SO IT CAN BE ASSERTED WITHOUT A POST, WITHOUT A
 *    NONCE AND WITHOUT SENDING ANYTHING. Every field that reaches Andrew is
 *    checked in the suite against a plain array, which is the only way to prove
 *    "all fields arrive" without submitting the form to his address.
 *
 * ⛔ THE SUBJECT CARRIES THE WORD TENTATIVE. He should be able to tell from his
 *    inbox list, without opening it, that nothing is booked.
 *
 * @param array $req Normalised request fields.
 * @return array{subject:string,body:string}
 */
function bhp_readaloud_request_compose( $req ) {
	$slots = bhp_readaloud_scheduler_slots();
	$picked = array();
	foreach ( (array) ( isset( $req['slots'] ) ? $req['slots'] : array() ) as $key ) {
		if ( isset( $slots[ $key ] ) ) {
			$picked[] = $slots[ $key ];
		}
	}
	$slot_line = $picked ? implode( ' and ', $picked ) : '';
	if ( 2 === count( $picked ) ) {
		$slot_line .= ' ' . __( '(either one works for them)', 'brave-hearts' );
	}

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐ 1.19.335 — THE EMAIL NOW CARRIES A WEEK, A BACKUP WEEK AND THE DAYS
	 *    THAT COULD WORK. Carrier item 534.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE LABEL AND THE RANGE ARE PASSED IN, NOT RECOMPUTED HERE, and the
	 *    reason is that this function is PURE and is asserted against a plain
	 *    array. Re-deriving them would make the composer depend on live config
	 *    and would make "does every field arrive" untestable without a POST.
	 *
	 * ⛔ THE RAW WEEK START IS STILL PRINTED, in parentheses, exactly as the
	 *    raw date was at 1.19.334. He needs to be able to read the machine value
	 *    out of his own inbox when a label and a calendar disagree.
	 */
	$week_label = (string) ( isset( $req['week_label'] ) ? $req['week_label'] : '' );
	$week_range = (string) ( isset( $req['week_range'] ) ? $req['week_range'] : '' );
	$week_start = (string) ( isset( $req['week'] ) ? $req['week'] : '' );

	$week_line = $week_label;
	if ( '' !== $week_range ) {
		$week_line .= '  (' . $week_range . ')';
	}
	if ( '' !== $week_start ) {
		$week_line .= '  [' . $week_start . ']';
	}

	$backup_label = (string) ( isset( $req['backup_label'] ) ? $req['backup_label'] : '' );
	$backup_range = (string) ( isset( $req['backup_range'] ) ? $req['backup_range'] : '' );
	$backup_start = (string) ( isset( $req['backup'] ) ? $req['backup'] : '' );

	$backup_line = $backup_label;
	if ( '' !== $backup_line && '' !== $backup_range ) {
		$backup_line .= '  (' . $backup_range . ')';
	}
	if ( '' !== $backup_line && '' !== $backup_start ) {
		$backup_line .= '  [' . $backup_start . ']';
	}
	if ( '' === $backup_line ) {
		/* ⛔ SAID PLAINLY RATHER THAN LEFT BLANK. An empty line beside a filled
		   one reads as a field that failed to arrive. */
		$backup_line = __( 'none given', 'brave-hearts' );
	}

	$weekday_labels = bhp_readaloud_scheduler_weekdays();
	$weekday_picked = array();
	foreach ( (array) ( isset( $req['weekdays'] ) ? $req['weekdays'] : array() ) as $key ) {
		if ( isset( $weekday_labels[ $key ] ) ) {
			$weekday_picked[] = $weekday_labels[ $key ];
		}
	}
	$weekday_line = $weekday_picked
		? implode( ', ', $weekday_picked )
		: __( 'no preference given', 'brave-hearts' );

	$subject = sprintf(
		/* translators: 1: school name, 2: the requested week, e.g. "Week of October 5". */
		__( '[TENTATIVE read-aloud request] %1$s - %2$s', 'brave-hearts' ),
		(string) ( isset( $req['school'] ) ? $req['school'] : '' ),
		$week_label
	);

	$city_labels = bhp_readaloud_scheduler_cities();
	$city_key    = isset( $req['city'] ) ? (string) $req['city'] : '';
	$city_label  = isset( $city_labels[ $city_key ] ) ? $city_labels[ $city_key ] : $city_key;
	if ( 'other' === $city_key ) {
		$city_label .= ' — ' . __( 'OUTSIDE THE FOUR LISTED CITIES', 'brave-hearts' );
	}

	$lines = array(
		__( 'A school has asked for a read-aloud week. Nothing is booked until you reply with a day.', 'brave-hearts' ),
		'',
		'School:        ' . (string) ( isset( $req['school'] ) ? $req['school'] : '' ),
		'City:          ' . $city_label,
		'Week wanted:   ' . $week_line,
		'Backup week:   ' . $backup_line,
		'Days that work: ' . $weekday_line,
		'Slot wanted:   ' . $slot_line,
		'Grades:        ' . (string) ( isset( $req['grades'] ) ? $req['grades'] : '' ),
		'',
		'Contact name:  ' . (string) ( isset( $req['contact'] ) ? $req['contact'] : '' ),
		'Contact email: ' . (string) ( isset( $req['email'] ) ? $req['email'] : '' ),
		'',
		'Requested from: ' . (string) ( isset( $req['source_page'] ) ? $req['source_page'] : '' ),
	);

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.19.336 — WHERE THE TEACHER CAME FROM. `CYCLE170-LD-CHAIN`.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE DEFECT THIS CLOSES, found by the campaign lane: `source_page` is
	 *    rendered by the template as `bhp_school_readalouds_url()` — a CLEAN
	 *    canonical URL. Every `utm_*` and every `fbclid` the teacher actually
	 *    arrived on was therefore stripped before the email was composed, so a
	 *    request from a paid click and a request from a bookmark looked
	 *    identical in his inbox. `source_page` is deliberately NOT widened to
	 *    carry them: it is host-checked and reused as a REDIRECT target, and a
	 *    redirect target is the wrong place to start appending client input.
	 *
	 * ⭐ IT IS THE SIGNUP PIPE'S OWN PATTERN, REUSED RATHER THAN REBUILT.
	 *    `bhp_get_form_moment_attribution()` (1.19.323, `inc/mailchimp.php`)
	 *    already reads $_GET, then the same-origin referer, then the posted
	 *    hidden field, and passes all three through one whitelist and one
	 *    sanitiser. ⛔ NO COOKIE IS READ OR WRITTEN, no consent posture is
	 *    touched, and nothing is persisted — this is one line of an email.
	 *
	 * ⛔ THE LINE IS OMITTED ENTIRELY WHEN THERE IS NOTHING TO SAY, rather than
	 *    printed empty. An empty "Campaign:" row in his inbox would be noise on
	 *    every organic request, which is most of them.
	 */
	$attribution = isset( $req['attribution'] ) && is_array( $req['attribution'] ) ? $req['attribution'] : array();
	if ( $attribution ) {
		$pairs = array();
		foreach ( $attribution as $attr_key => $attr_value ) {
			if ( is_scalar( $attr_value ) && '' !== (string) $attr_value ) {
				$pairs[] = $attr_key . '=' . (string) $attr_value;
			}
		}
		if ( $pairs ) {
			$lines[] = 'Campaign:      ' . implode( ' ', $pairs );
		}
	}

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐ 1.19.337 — WHETHER SHE ALSO ASKED FOR THE TOOLKIT. Items 553 / 554.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ PRINTED IN BOTH DIRECTIONS, unlike the `Campaign:` line above which is
	 *    omitted when empty. The distinction is deliberate: an absent campaign
	 *    means "organic", which is the common case and needs no row. An absent
	 *    opt-in line would be ambiguous between "she unticked it" and "this
	 *    release is not deployed yet" — and the founder needs to know which
	 *    teacher is on his educator list before he replies to her.
	 *
	 * ⛔ IT REPORTS THE TEACHER'S CHOICE, NOT THE SIGNUP RESULT. Compose is a
	 *    PURE function asserted against a plain array; reaching into Mailchimp's
	 *    outcome from here would make it depend on a network call. The signup
	 *    outcome is on the `bhp_readaloud_optin_processed` action instead.
	 */
	$lines[] = 'Toolkit opt-in: ' . ( ! empty( $req['optin'] )
		? __( 'YES - also added to the educator list', 'brave-hearts' )
		: __( 'no - booking request only', 'brave-hearts' ) );

	if ( '' !== (string) ( isset( $req['notes'] ) ? $req['notes'] : '' ) ) {
		$lines[] = '';
		$lines[] = 'Anything else they said:';
		$lines[] = (string) $req['notes'];
	}

	$lines[] = '';
	$lines[] = __( 'Reply to this message with the exact day and time, or offer another week.', 'brave-hearts' );

	return array(
		'subject' => $subject,
		'body'    => implode( "\n", $lines ),
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.337 (2026-08-30, `CYCLE170-LD-MICRO`) — THE TEACHER FUNNEL WIRE.
 *     CARRIER ITEMS 553 AND 554.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐⭐ FOUNDER, item 553: *"If a teacher fills out their information for the read
 *    aloud- they should be put into the funnel"* — with the "smuggle the consent"
 *    half REJECTED by `chief-of-staff` and the rejection ACCEPTED by Andrew in the same
 *    exchange (*"Ok thats great"*), then re-confirmed at item 554. The visible
 *    pre-checked control is in `inc/school-read-alouds.php`; read its block
 *    comment for the consent position. ⛔ RELAYED, read first-hand at the carrier.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ IT REUSES THE EDUCATOR PATH EXACTLY. NOTHING IS FORKED AND NOTHING IS NEW.
 * ---------------------------------------------------------------------------
 * The brief's requirement is that a ticked requester enters the pipe *"with the
 * educator tags exactly as an educators-page signup"*. That is not a claim this
 * file makes about itself — it is the same three arguments, traced to source:
 *
 *   `page-audience-educators.php` line ~480 passes to `lead-magnet-cta.php`:
 *       lead_magnet   = 'teacher_adventure_toolkit'
 *       audience_type = 'educators'
 *   `template-parts/acquisition/lead-magnet-cta.php` then passes to
 *   `signup-form.php`, which posts to `bhp_mailchimp_signup`:
 *       context       = 'lead_magnet'
 *
 * ⭐ THOSE THREE VALUES ARE WHAT `bhp_get_mailchimp_signup_tags()` BRANCHES ON.
 *    Passing the identical three into `bhp_process_signup()` is therefore not
 *    "similar tagging" — it is the SAME tag computation, over the same filter
 *    chain, at the same priorities. ⛔ NO NEW TAG IS INVENTED HERE, no
 *    `bhp_mailchimp_signup_tags` callback is added by this feature, and the
 *    educator journey is not touched. ⭐ VERIFIED, not asserted: the staging
 *    Mailchimp stub records the payload and the tag list is read back out of it
 *    in the release record.
 *
 * ⚠️ THE KNOWN CONSEQUENCE, CARRIED FORWARD RATHER THAN QUIETLY FIXED: because
 *   the context is the shared `lead_magnet`, the source tag will read
 *   *"Source: Educator Landing Page"* for a signup that happened on the booking
 *   form. `page-school-read-alouds.php` section g already records this exact
 *   consequence for the page's tail capture and the same answer applies: per-
 *   request attribution is NOT lost, because `source_page` carries the read-
 *   aloud page's own URL into the `SOURCE` merge field, which is a different
 *   field from the tags. ⛔ A dedicated source tag changes Mailchimp
 *   segmentation and is Andrew's call. Reported, not patched — and deliberately
 *   NOT patched here, because a new tag would be the one thing that stops this
 *   being "exactly as an educators-page signup".
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ IT CAN NEVER BREAK A BOOKING REQUEST. THIS IS THE LOAD-BEARING PROPERTY.
 * ---------------------------------------------------------------------------
 * `inc/checkout-optin-sync.php` states the same rule for the same reason: *"An
 * unsubscribed buyer is a marketing loss; a failed order is a business loss."*
 * Here the currency is a teacher's request. ⛔ Every path is wrapped, every
 * failure is swallowed into an action, and this function is called AFTER the
 * mail has been sent or captured, never before. A Mailchimp outage costs a
 * newsletter subscription and cannot cost Andrew a school.
 *
 * ⛔ AND IT NEVER RUNS ON AN INVALID SUBMISSION. It is called past the nonce,
 *    past the honeypot and past every validation redirect, so a bot that trips
 *    the honeypot is answered `success` and is NOT subscribed.
 *
 * @param string $email   Validated contact email.
 * @param string $name    Contact name, for FNAME.
 * @param string $source  The page URL the request came from.
 * @param bool   $opted   Did the teacher leave the box ticked?
 * @return string Status token, for the action and for tests.
 */
function bhp_readaloud_request_enroll_educator( $email, $name, $source, $opted ) {
	if ( ! $opted ) {
		return 'declined'; // ⛔ Unticked is a real answer. Nothing happens.
	}
	if ( ! function_exists( 'bhp_process_signup' ) ) {
		/* `inc/mailchimp.php` is a sibling include. A missing sibling degrades to
		   "no enrolment", never to a fatal on a live form POST. */
		return 'unavailable';
	}
	if ( ! is_email( $email ) ) {
		return 'invalid_email';
	}

	$status = 'failed';

	try {
		$result = bhp_process_signup(
			array(
				/* ⛔ THE THREE VALUES ARE THE EDUCATOR LANDING PAGE'S OWN. See the
				   trace in the block comment above. Do not "tidy" any of them. */
				'context'       => 'lead_magnet',
				'audience_type' => 'educators',
				'lead_magnet'   => 'teacher_adventure_toolkit',
				'email'         => $email,
				'name'          => $name,
				/* ⭐ THIS is what keeps per-request attribution: the read-aloud
				   page's own URL lands in the SOURCE merge field. */
				'source_page'   => $source,
				/* ⛔ FALSE. The educator panel requires a name because its own form
				   asks for one; here the name is already validated as `contact` by
				   the booking form's own rules, and a `missing_name` rejection must
				   never be able to appear on a request that was otherwise complete. */
				'require_name'  => false,
			)
		);

		$status = ( ! empty( $result['ok'] ) )
			? 'ok'
			: 'failed:' . sanitize_key( (string) ( isset( $result['code'] ) ? $result['code'] : 'unknown' ) );
	} catch ( Throwable $exception ) {
		/* ⛔ SWALLOWED DELIBERATELY AND RECORDED RATHER THAN HIDDEN — the same
		   posture, and the same reason, as `bhp_checkout_optin_sync()`. */
		$status = 'exception';
		do_action( 'bhp_readaloud_optin_exception', $exception, $email );
	}

	/**
	 * Fires after a read-aloud requester's toolkit opt-in has been processed.
	 *
	 * ⭐ THE OBSERVABILITY HOOK, and the only thing a test needs to prove the
	 *    wire ran without a network call. ⛔ NO PII BEYOND THE ADDRESS THE
	 *    TEACHER JUST TYPED INTO THIS FORM IS PASSED, and nothing is persisted.
	 *
	 * @param string $status Status token.
	 * @param string $email  The address.
	 * @param string $source Source page URL.
	 */
	do_action( 'bhp_readaloud_optin_processed', $status, $email, $source );

	return $status;
}

add_action( 'admin_post_nopriv_' . BHP_READALOUD_REQUEST_ACTION, 'bhp_handle_readaloud_request' );
add_action( 'admin_post_' . BHP_READALOUD_REQUEST_ACTION, 'bhp_handle_readaloud_request' );

/**
 * Handle a read-aloud request submission.
 *
 * ⛔ ORDER OF CHECKS IS LOAD-BEARING: nonce, then honeypot, then validation,
 *    then send. The honeypot answers SUCCESS rather than an error, because an
 *    error tells a bot which field trapped it.
 *
 * @return void
 */
function bhp_handle_readaloud_request() {
	// Keep every redirect on-site: only trust a source_page under our own host.
	$source = isset( $_POST['source_page'] ) ? esc_url_raw( wp_unslash( $_POST['source_page'] ) ) : '';
	if ( '' === $source || 0 !== strpos( $source, home_url() ) ) {
		$source = home_url( '/school-read-alouds/' );
	}

	$redirect = function ( $status ) use ( $source ) {
		wp_safe_redirect( add_query_arg( 'bhp_readaloud', rawurlencode( $status ), $source ) . '#readaloud-scheduler' );
		exit;
	};

	// 1 · Nonce.
	if ( ! isset( $_POST['bhp_readaloud_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhp_readaloud_nonce'] ) ), BHP_READALOUD_REQUEST_ACTION ) ) {
		$redirect( 'error' );
	}

	/*
	 * 2 · Honeypot. A real visitor never sees this field and never fills it.
	 *     Report SUCCESS and send nothing: an error response is a free oracle
	 *     telling the next bot which field to leave alone.
	 */
	if ( ! empty( $_POST['bhp_readaloud_hp'] ) ) {
		$redirect( 'success' );
	}

	// 3 · Read and sanitise. Nothing below is trusted until it is validated.
	$school  = isset( $_POST['school'] ) ? sanitize_text_field( wp_unslash( $_POST['school'] ) ) : '';
	$city    = isset( $_POST['city'] ) ? sanitize_key( wp_unslash( $_POST['city'] ) ) : '';
	$contact = isset( $_POST['contact'] ) ? sanitize_text_field( wp_unslash( $_POST['contact'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$grades  = isset( $_POST['grades'] ) ? sanitize_text_field( wp_unslash( $_POST['grades'] ) ) : '';
	$notes   = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

	/*
	 * ⭐ 1.19.335 — THE FIELD IS A WEEK. `visit_date` IS GONE FROM THIS HANDLER
	 *    AND IS NOT SILENTLY ACCEPTED AS A FALLBACK. A form that still honoured
	 *    the old field name would let a stale cached page post a single day into
	 *    a week-level flow, and the founder would receive a request for a day he
	 *    never agreed to be asked for.
	 */
	$week   = isset( $_POST['visit_week'] ) ? sanitize_text_field( wp_unslash( $_POST['visit_week'] ) ) : '';
	$backup = isset( $_POST['visit_week_backup'] ) ? sanitize_text_field( wp_unslash( $_POST['visit_week_backup'] ) ) : '';

	/*
	 * ⭐ 1.19.337 — THE TOOLKIT OPT-IN. Items 553 / 554.
	 *
	 * ⛔ AN UNCHECKED CHECKBOX POSTS NOTHING AT ALL, so absence IS the "no". The
	 *    box ships pre-checked, so a request that arrives WITHOUT this key is
	 *    either a teacher who deliberately unticked it or a client that dropped
	 *    it — and both must be read as no. ⛔ NEVER default this to true on
	 *    absence: that would turn a form error into a consent claim, which is
	 *    exactly the "smuggled consent" the founder's own ruling rejected.
	 */
	$optin = isset( $_POST['toolkit_optin'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['toolkit_optin'] ) );

	$slots_in  = isset( $_POST['slots'] ) ? (array) wp_unslash( $_POST['slots'] ) : array();
	$allowed   = bhp_readaloud_scheduler_slots();
	$slots     = array();
	foreach ( $slots_in as $raw ) {
		$key = sanitize_key( $raw );
		if ( isset( $allowed[ $key ] ) && ! in_array( $key, $slots, true ) ) {
			$slots[] = $key;
		}
	}

	/* ⭐ OPTIONAL. Unknown keys are DROPPED, not rejected - an unrecognised
	   weekday is a stale form or a bot, and neither is worth an error page to a
	   teacher whose real answer arrived intact alongside it. */
	$weekdays_in  = isset( $_POST['weekdays'] ) ? (array) wp_unslash( $_POST['weekdays'] ) : array();
	$weekday_keys = bhp_readaloud_scheduler_weekdays();
	$weekdays     = array();
	foreach ( array_keys( $weekday_keys ) as $key ) {
		foreach ( $weekdays_in as $raw ) {
			if ( sanitize_key( $raw ) === $key && ! in_array( $key, $weekdays, true ) ) {
				$weekdays[] = $key; // Kept in Monday-to-Friday order, not POST order.
			}
		}
	}

	// 4 · Validate. Every rule here also exists on the form; neither is trusted alone.
	$cities = bhp_readaloud_scheduler_cities();
	if ( '' === $school || '' === $contact || ! is_email( $email ) || '' === $grades ) {
		$redirect( 'invalid' );
	}
	if ( ! isset( $cities[ $city ] ) ) {
		$redirect( 'invalid' );
	}
	if ( empty( $slots ) ) {
		$redirect( 'noslot' );
	}
	/*
	 * ⛔⛔ 1.19.335 — VALIDATION MOVED FROM DAY LEVEL TO WEEK LEVEL, AND IT MOVED
	 *     IN FULL. The week is checked against the server's own re-derived list,
	 *     not against a regex and not against "is it a Monday". A posted week
	 *     from September, a posted week beyond the twelfth card, a posted
	 *     mid-week date and an injection-shaped string are all rejected here even
	 *     though no rendered control could have produced any of them.
	 */
	$week_row = bhp_readaloud_scheduler_week_by_value( $week );
	if ( null === $week_row ) {
		$redirect( 'badweek' );
	}

	/*
	 * ⭐ THE BACKUP IS OPTIONAL, AND AN INVALID ONE IS AN ERROR RATHER THAN A
	 *    SILENT DROP. Dropping it would send him a request whose backup line the
	 *    teacher believes she filled in and he never sees.
	 */
	$backup_row = null;
	if ( '' !== $backup ) {
		if ( $backup === $week ) {
			$redirect( 'sameweek' );
		}
		$backup_row = bhp_readaloud_scheduler_week_by_value( $backup );
		if ( null === $backup_row ) {
			$redirect( 'badweek' );
		}
	}

	// 5 · Compose.
	$composed = bhp_readaloud_request_compose(
		array(
			'school'       => $school,
			'city'         => $city,
			'contact'      => $contact,
			'email'        => $email,
			'grades'       => $grades,
			'week'         => $week,
			'week_label'   => $week_row['label'],
			'week_range'   => $week_row['range'],
			'backup'       => $backup_row ? $backup : '',
			'backup_label' => $backup_row ? $backup_row['label'] : '',
			'backup_range' => $backup_row ? $backup_row['range'] : '',
			'weekdays'     => $weekdays,
			'slots'        => $slots,
			'notes'        => $notes,
			'source_page'  => $source,
			/* ⭐ 1.19.337 — so he can see, in his own inbox, whether this teacher
			   is also now on the educator list. See the compose block comment. */
			'optin'        => $optin,
			/* ⭐ 1.19.336 — the campaign the teacher arrived on. See the block
			   comment in `bhp_readaloud_request_compose()`. ⛔ Guarded by
			   `function_exists()` because `inc/mailchimp.php` is a sibling
			   include and a missing sibling must degrade to "no Campaign line",
			   never to a fatal on a live form POST. */
			'attribution'  => function_exists( 'bhp_get_form_moment_attribution' )
				? (array) bhp_get_form_moment_attribution()
				: array(),
		)
	);

	$to      = bhp_readaloud_scheduler_recipient();
	$headers = array( 'Reply-To: ' . $contact . ' <' . $email . '>' );

	// 6 · Send, or capture on staging. See bhp_readaloud_request_should_capture().
	if ( bhp_readaloud_request_should_capture() ) {
		bhp_readaloud_request_capture_store( $to, $composed['subject'], $composed['body'], $headers );
		/*
		 * ⭐ 1.19.337 — THE OPT-IN RUNS ON THE CAPTURED PATH TOO, AND THAT IS
		 *    DELIBERATE RATHER THAN AN OVERSIGHT. Capture suppresses the MAIL,
		 *    not the feature. Skipping the enrolment on staging would make the
		 *    one path QA can exercise the one path that never runs — which is
		 *    the exact blindness `CYCLE167-LD-CAPTURE-PIPE-DIAGNOSIS` recorded.
		 *    ⛔ Staging is disconnected from the live Mailchimp audience (no API
		 *    key) and `inc/mailchimp-staging-stub.php` records the payload
		 *    instead of transmitting it, so no real contact can be created here.
		 */
		bhp_readaloud_request_enroll_educator( $email, $contact, $source, $optin );
		$redirect( 'captured' );
	}

	$sent = wp_mail( $to, $composed['subject'], $composed['body'], $headers );

	/*
	 * ⛔ AFTER THE MAIL, ALWAYS, AND UNCONDITIONALLY ON `$sent`. Two reasons,
	 *    both deliberate: the request reaching Andrew is the thing that must not
	 *    be delayed or endangered by a network call to Mailchimp; and a teacher
	 *    who ticked the box asked for the Toolkit whether or not his SMTP was
	 *    healthy at that instant. ⭐ The function itself is wrapped and cannot
	 *    throw past this line.
	 */
	bhp_readaloud_request_enroll_educator( $email, $contact, $source, $optin );

	$redirect( $sent ? 'success' : 'error' );
}

/**
 * The status message shown after a redirect back to the page.
 *
 * ⛔⛔ THE SUCCESS STATE SAYS TENTATIVE, IN THOSE WORDS, AND SAYS ANDREW
 *     CONFIRMS BY REPLY. This is his stated gate and it is the one string on
 *     this page that must never be softened into "you are booked".
 *
 * @param string $status Query-string status.
 * @return array{tone:string,title:string,text:string}|null
 */
function bhp_readaloud_request_status_message( $status ) {
	switch ( (string) $status ) {
		case 'success':
		case 'captured':
			/*
			 * ⛔⛔ THE WORD **TENTATIVE** STAYS, AND SO DOES "until I confirm by
			 *     reply". That is the founder's own gate and it is the one string
			 *     on this page that must never be softened into "you are booked".
			 *     1.19.335 adds only what item 534 adds: what he is confirming is
			 *     now the exact DAY inside the week, not the week itself.
			 */
			return array(
				'tone'  => 'success',
				'title' => __( 'Request sent. Nothing is booked yet.', 'brave-hearts' ),
				'text'  => __( 'Your request is TENTATIVE until I confirm it by reply. I read every one of these myself, and I will email you back at the address you gave me with the exact day and time as soon as my hospital schedule comes out.', 'brave-hearts' ),
			);
		case 'noslot':
			return array(
				'tone'  => 'error',
				'title' => __( 'Pick morning, afternoon, or both.', 'brave-hearts' ),
				'text'  => __( 'I need to know which part of the day suits you. Tick both if either one works.', 'brave-hearts' ),
			);
		case 'badweek':
			return array(
				'tone'  => 'error',
				'title' => __( 'Pick a week from the list.', 'brave-hearts' ),
				'text'  => __( 'That week is not one I can offer. Choose one of the weeks shown.', 'brave-hearts' ),
			);
		case 'sameweek':
			return array(
				'tone'  => 'error',
				'title' => __( 'Your backup week is the same as your first choice.', 'brave-hearts' ),
				'text'  => __( 'Pick a different week as the backup, or leave the backup blank.', 'brave-hearts' ),
			);
		case 'baddate':
			/* ⚠ 1.19.335: NO LONGER REACHABLE FROM THE HANDLER, which now
			   redirects `badweek`. It is KEPT, not deleted, because a bookmarked
			   or cached `?bhp_readaloud=baddate` URL from 1.19.326-1.19.334 must
			   still render a sentence rather than a silent blank. */
			return array(
				'tone'  => 'error',
				'title' => __( 'Pick a week from the list.', 'brave-hearts' ),
				'text'  => __( 'That day is not one I can offer. Choose one of the weeks shown.', 'brave-hearts' ),
			);
		case 'invalid':
			return array(
				'tone'  => 'error',
				'title' => __( 'Some details are missing.', 'brave-hearts' ),
				'text'  => __( 'I need your school, city, name, a working email address and the grades you have in mind.', 'brave-hearts' ),
			);
		case 'error':
			return array(
				'tone'  => 'error',
				'title' => __( 'That did not go through.', 'brave-hearts' ),
				'text'  => __( 'Something went wrong on my end. Please email me directly and I will pick it up from there.', 'brave-hearts' ),
			);
	}
	return null;
}

/**
 * ⭐ AN HONEST, VISIBLE MARK ON STAGING WHEN CAPTURE IS ON.
 *
 * ⛔ Same reasoning as `bhp_staging_mail_guard_admin_bar()`: somebody will test
 *    this form on staging, watch no email arrive, and file a defect against the
 *    mail system. This says on the screen that the capture is why. It renders
 *    ONLY where capture is active and ONLY for an administrator, so it can
 *    never reach a visitor or a production page.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 * @return void
 */
function bhp_readaloud_request_admin_bar( $bar ) {
	if ( ! bhp_readaloud_request_should_capture() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! is_object( $bar ) || ! method_exists( $bar, 'add_node' ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'bhp-readaloud-request-capture',
			'title' => 'Staging: read-aloud requests CAPTURED',
			'meta'  => array( 'title' => 'Read-aloud request emails are stored in the bhp_readaloud_request_log option instead of being sent. Production is unaffected.' ),
		)
	);
}
add_action( 'admin_bar_menu', 'bhp_readaloud_request_admin_bar', 999 );

/**
 * Enqueue the week picker's helper script, on the one template that renders it.
 *
 * ⚠ 1.19.335 — THE FILE PATH, THE HANDLE AND THIS FUNCTION'S NAME ARE ALL
 *   DELIBERATELY UNCHANGED even though the script inside is now the week
 *   picker's helper rather than the month pager. Renaming an asset that three
 *   suites and one enqueue assertion already name would have made a paint-level
 *   release look like a plumbing release, and the theme version already busts
 *   the cache. ⛔ The comment is corrected instead of the name being churned.
 *
 * ⛔ SCOPED TO THE TEMPLATE, so no other page pays for it — the same rule and the
 *    same shape as `bhp_enqueue_readaloud_carousel_assets()` in
 *    `inc/readaloud-carousel.php`, which serves the other component on this page.
 *
 * ⭐ A MISSED ENQUEUE DEGRADES TO A WORKING PICKER, NOT TO A BROKEN ONE. The
 *    script's own guard is `[data-bhp-cal]`, every week card is server-rendered
 *    and visible, and every radio is a real radio. So the failure mode is "the
 *    running summary line never appears", never a JavaScript error and never an
 *    unreachable week.
 *
 * ⛔ IN THE FOOTER, and with no dependencies. It touches nothing before
 *    `DOMContentLoaded` and shares no global with any other script on the page.
 *
 * @return void
 */
function bhp_enqueue_readaloud_calendar_assets() {
	if ( ! is_page_template( 'page-school-read-alouds.php' ) ) {
		return;
	}
	wp_enqueue_script(
		'bhp-readaloud-calendar',
		get_template_directory_uri() . '/assets/js/readaloud-calendar.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_enqueue_readaloud_calendar_assets' );
