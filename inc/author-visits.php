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

		/*
		 * ⭐⭐ 1.19.350-FIX (`CYCLE179-LD-350-FIX` fix 1) — THE PRINTED DEADLINE
		 *    NOW COMES FROM THE SAME FUNCTION THE SHOP BAND READS.
		 *
		 * ⛔ THE DEFECT IT CLOSES: this page printed `cutoff` while the shop
		 *    band computed `visit - 2`, with no shared code between them. Two
		 *    customer-facing surfaces could state two different deadlines for
		 *    the same visit, and on one live production row they did.
		 *
		 * ⚠️ 1.19.352 (`CYCLE179-LD-1`) - THE SENTENCE ABOVE NAMED THAT ROW BY
		 *    ITS REAL SLUG, AND THAT WAS A DEFECT THIS FILE'S OWN TEST CAUGHT.
		 *    `tests/test-author-visits-page.php` and
		 *    `tests/test-cycle169-visits-trust-gallery.php` both assert that NO
		 *    real visit slug appears in this file or the template, because the
		 *    registry is DATA and this file is CODE. A slug in a comment is
		 *    still a slug in the source: it dates the file, it survives into a
		 *    public repository, and it invites the next reader to treat one
		 *    school's row as the shape every row has. ⛔ The two suites were
		 *    RIGHT and the comment was WRONG, so the comment moved and neither
		 *    assertion was touched. The row it referred to is identified in the
		 *    release record, which is where a specific school belongs.
		 *
		 * ⭐⭐ THE STATED DEADLINE IS STILL WHAT PRINTS ON EVERY CONVENTIONALLY
		 *    ENTERED ROW, AND THAT IS A PROVABLE PROPERTY RATHER THAN AN
		 *    INTENTION. `bhp_visit_deadline_display()` returns the stated
		 *    `cutoff` whenever `cutoff <= visit - 2`, and the online close
		 *    otherwise — so its result is NEVER LATER than the stated cutoff.
		 *    ⛔ It therefore cannot advertise the grace window under any row,
		 *    which is the founder instruction quoted in this file's header and
		 *    on `bhp_school_visit_last_order_date()`. All three `visit - 3` rows
		 *    print byte-identically to before this change.
		 *
		 * ⚠️ WHAT DOES MOVE: a hand-entered row whose `cutoff` falls AFTER the
		 *    online close stops printing a deadline the button has already
		 *    refused. `cutoff` itself is untouched, still sanitised, still
		 *    carried in this row for anything that needs the stated value.
		 *
		 * ⛔ NO REGISTRY ROW IS READ FOR WRITING, EDITED OR DEFAULTED HERE. The
		 *    fallback exists only so a deactivated plugin renders a page instead
		 *    of a fatal, the same shape as `bhp_author_visits_today()` above.
		 */
		$deadline = function_exists( 'bhp_visit_deadline_display' )
			? bhp_visit_deadline_display( array( 'date' => $date, 'cutoff' => $cutoff ) )
			: '';
		if ( '' === $deadline ) {
			$deadline = $cutoff;
		}

		$rows[] = array(
			'slug'         => $slug,
			'school'       => $school,
			'date'         => $date,
			'date_display' => bhp_author_visits_format_date( $date ),
			'time'         => $time,
			'cutoff'       => $cutoff,
			'deadline'     => $deadline,
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

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * PAST READ-ALOUDS — THE TRUST RECORD. Theme 1.19.319 (2026-08-29,
 * `CYCLE169-LD-READALOUD-TRUST-GALLERY`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, verbatim, 2026-08-29 (carrier item 432, first-hand to the
 * Chief of Staff, commissioning this agent BY NAME).
 *
 * ⚠ ONE EDITORIAL SUBSTITUTION IN THE QUOTE BELOW, MARKED WITH SQUARE
 *   BRACKETS AS SUBSTITUTIONS ALWAYS ARE. He used this desk's internal call
 *   name; internal call names may not appear in this repository, which is
 *   public (Standing Rules §14.5), so the bracket carries the technical agent
 *   ID instead. NOTHING ELSE IN HIS SENTENCE IS ALTERED, and the bracket is
 *   visible precisely so no future reader mistakes it for his own wording.
 *
 *   *"Also I would like [lead-developer] to work on putting a column for past read
 *   alouds on the read-aloud site- I want more trust on that and lets put a
 *   picture gallery of the read alouds on that page too."*
 *
 * ⛔ THIS IS THE OTHER HALF OF THE `$date < $today` BRANCH ABOVE, NOT A CHANGE
 *    TO IT. `bhp_author_visits_build_rows()` still drops a past visit from the
 *    UPCOMING list, byte-for-byte as it did at 1.19.239. Nothing about the
 *    upcoming list, the open/closed window or the greyed button moved. A past
 *    visit stopped vanishing from the PAGE; it did not stop vanishing from the
 *    LIST, and conflating those two would have broken the ordering window.
 *
 * ⛔ THE SECOND OPTION EXISTS BECAUSE THE FIRST ONE MAY NOT GROW. The registry
 *    `bhp_school_visits` is the BUNDLE PLUGIN's and it drives checkout
 *    entitlement — `bhp_school_visit_resolve()` reads it before it will hand a
 *    parent free author delivery. Editorial matter (a sentence about what
 *    happened, a link to a recap, a list of photographs) has no business in the
 *    structure a checkout gate reads. So notes live in their OWN option,
 *    `bhp_school_visit_notes`, keyed by the same slug and joined at render time.
 *    A malformed or absent notes option degrades to "no note, no photos" and
 *    the past row still renders its school and date.
 *
 * ⛔ AND IT IS AN OPTION RATHER THAN AN ARRAY IN THIS FILE BECAUSE
 *    `tests/test-author-visits-page.php` FORBIDS THE ALTERNATIVE, correctly:
 *    it asserts that no school name, no visit slug and no literal visit row
 *    appears anywhere in this file's or the template's source. Writing a real
 *    school's name here to give it a caption would fail that assertion — and
 *    the assertion is right, because visit data is data.
 *
 * ⛔ EVERY WORD OF EVERY NOTE IS FOUNDER-ATTESTED. The note text shipped for
 *    the first past visit is drawn from carrier items 368 and 377, which are
 *    Andrew's own first-hand account of that morning. NOTHING may be added to a
 *    note that he did not say: no parent reaction, no teacher reaction, no
 *    child reaction, no outcome, no count that he did not give. The librarian
 *    is NEVER named (his standing instruction, and she is a private individual).
 *    No child is ever named. Standing Rules §3.
 */

/**
 * The editorial notes and photo sets for past visits, keyed by visit slug.
 *
 * ⛔ READ-ONLY, AND IT WRITES NOTHING. Shape, per slug:
 *      note      string  One factual sentence or two. Founder-attested only.
 *      recap_url string  Absolute or site-root-relative URL, or ''.
 *      photos    array   [ { file, alt, w, h }, ... ]
 *
 * ⛔ `file` IS A BARE BASENAME INSIDE `assets/img/read-alouds/`, AND IT IS
 *    FORCED TO ONE BY `basename()` PLUS AN EXTENSION WHITELIST. An option is
 *    editable by anyone who can reach WP-CLI or the options table, so a value
 *    like `../../../wp-config.php` must not be able to become a URL. It cannot:
 *    the path is rebuilt from the basename, never concatenated from the input.
 *
 * @return array<string,array<string,mixed>>
 */
function bhp_author_visits_notes() {
	$raw = get_option( 'bhp_school_visit_notes', array() );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$out = array();
	foreach ( $raw as $slug => $entry ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! is_array( $entry ) ) {
			continue;
		}

		$photos = array();
		if ( isset( $entry['photos'] ) && is_array( $entry['photos'] ) ) {
			foreach ( $entry['photos'] as $photo ) {
				if ( ! is_array( $photo ) ) {
					continue;
				}
				// ⛔ basename() FIRST, always. Never trust the stored string as a path.
				$file = isset( $photo['file'] ) ? basename( wp_strip_all_tags( (string) $photo['file'] ) ) : '';
				$ext  = strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) );
				if ( '' === $file || ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
					continue;
				}
				// ⛔ AN IMAGE WITH NO ALT TEXT IS DROPPED, NOT RENDERED EMPTY. Andrew
				//    asked for "meta tags of course"; a decorative-looking photograph
				//    of real children is not decorative, and an empty alt attribute
				//    would be a lie to a screen reader.
				$alt = isset( $photo['alt'] ) ? trim( wp_strip_all_tags( (string) $photo['alt'] ) ) : '';
				if ( '' === $alt ) {
					continue;
				}
				$photos[] = array(
					'file' => $file,
					'alt'  => $alt,
					'w'    => isset( $photo['w'] ) ? absint( $photo['w'] ) : 0,
					'h'    => isset( $photo['h'] ) ? absint( $photo['h'] ) : 0,
				);
			}
		}

		$out[ $slug ] = array(
			'note'      => isset( $entry['note'] ) ? trim( wp_strip_all_tags( (string) $entry['note'] ) ) : '',
			'recap_url' => isset( $entry['recap_url'] ) ? esc_url_raw( (string) $entry['recap_url'] ) : '',
			'photos'    => $photos,
		);
	}

	return $out;
}

/**
 * The public URL of a read-aloud photograph shipped with the theme.
 *
 * ⭐ THE PHOTOGRAPHS ARE THEME ASSETS, NOT MEDIA-LIBRARY ATTACHMENTS, and that
 *    is a deliberate decision worth recording because the obvious alternative
 *    looks easier and is wrong. The three Adams photographs already exist in
 *    the PRODUCTION media library as attachments 668, 669 and 670 — but those
 *    IDs exist ONLY on production. Staging has no recap post and no such
 *    attachments (verified live 2026-08-29). A gallery keyed by attachment ID
 *    would render on production and render BROKEN on staging, which is the one
 *    environment QA actually looks at. Theme assets deploy inside the ZIP, so
 *    both environments hold byte-identical files at a byte-identical path.
 *
 * ⚠ CONSEQUENCE, STATED RATHER THAN DISCOVERED LATER: Rank Math's sitemap
 *   setting `include_images` is `on`, but it harvests images out of POST
 *   CONTENT. Template-rendered images are not in post content, so these
 *   photographs are NOT in the XML sitemap and will not be by this route.
 *   That is a phase-2 item and is written up in the gallery spec, not silently
 *   accepted here.
 *
 * @param string $file Bare basename, already validated by bhp_author_visits_notes().
 * @return string
 */
function bhp_author_visits_photo_url( $file ) {
	$file = basename( (string) $file );
	if ( '' === $file ) {
		return '';
	}
	return get_theme_file_uri( 'assets/img/read-alouds/' . $file );
}

/**
 * Turn registry records into PAST display rows. PURE, for the same reason
 * `bhp_author_visits_build_rows()` is: every date boundary becomes an assertion
 * instead of something you can only observe by waiting for a Tuesday.
 *
 * ⛔ THE BOUNDARY IS THE EXACT COMPLEMENT OF THE UPCOMING LIST'S. Upcoming keeps
 *    a row while `$date >= $today`; past takes it while `$date < $today`. The
 *    two conditions are written to be complements so a visit can never appear
 *    twice and can never disappear from both. `tests/test-cycle169-visits-trust-
 *    gallery.php` asserts exactly that partition across the visit-day boundary
 *    rather than trusting this comment.
 *
 * ⭐ MOST RECENT FIRST. The upcoming list is soonest-first because the next
 *    visit is the useful one; the past list is newest-first for the same reason.
 *
 * @param array  $records Records shaped like `bhp_school_visit_records()`.
 * @param string $today   `Y-m-d` in the site's timezone.
 * @param array  $notes   Notes map from `bhp_author_visits_notes()`.
 * @return array<int,array{slug:string,school:string,date:string,date_display:string,note:string,recap_url:string,photos:array}>
 */
function bhp_author_visits_build_past_rows( $records, $today, $notes = array() ) {
	if ( ! is_array( $records ) ) {
		return array();
	}
	$today = (string) $today;
	$notes = is_array( $notes ) ? $notes : array();

	// ⛔ WITH NO "TODAY" THERE IS NO PAST. An empty $today makes every upcoming
	//    row render; it must not simultaneously make every row render as history.
	if ( '' === $today ) {
		return array();
	}

	$rows = array();
	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			continue;
		}
		$slug   = isset( $record['slug'] ) ? sanitize_key( (string) $record['slug'] ) : '';
		$school = isset( $record['school'] ) ? trim( wp_strip_all_tags( (string) $record['school'] ) ) : '';
		$date   = isset( $record['date'] ) ? (string) $record['date'] : '';

		// ⛔ `cutoff` IS NOT REQUIRED HERE and its absence must not drop a past
		//    row. The ordering window is meaningless for a visit that already
		//    happened; requiring it would silently lose history.
		if ( '' === $slug || '' === $school || '' === $date ) {
			continue;
		}

		if ( $date >= $today ) {
			continue;
		}

		$note = isset( $notes[ $slug ] ) ? $notes[ $slug ] : array();

		$rows[] = array(
			'slug'         => $slug,
			'school'       => $school,
			'date'         => $date,
			'date_display' => bhp_author_visits_format_past_date( $date ),
			'note'         => isset( $note['note'] ) ? (string) $note['note'] : '',
			'recap_url'    => isset( $note['recap_url'] ) ? (string) $note['recap_url'] : '',
			'photos'       => isset( $note['photos'] ) && is_array( $note['photos'] ) ? $note['photos'] : array(),
		);
	}

	// Most recent first; slug as the stable tiebreak, mirroring the upcoming list.
	usort(
		$rows,
		static function ( $a, $b ) {
			if ( $a['date'] === $b['date'] ) {
				return strcmp( $a['slug'], $b['slug'] );
			}
			return strcmp( $b['date'], $a['date'] );
		}
	);

	return $rows;
}

/**
 * Format a past date. "August 28, 2026".
 *
 * ⛔ THE YEAR IS PRESENT, and that is the whole difference from
 *    `bhp_author_visits_format_date()`. "Friday, August 28" is right for a
 *    visit that is about to happen and wrong for a trust record that will still
 *    be on the page in two years. Weekday is dropped for the same reason: which
 *    weekday it fell on stops being useful the moment it is history.
 *
 * @param string $ymd Date.
 * @return string
 */
function bhp_author_visits_format_past_date( $ymd ) {
	$ymd = (string) $ymd;
	$ts  = $ymd ? strtotime( $ymd . ' 12:00:00' ) : false;
	if ( ! $ts || ! function_exists( 'wp_date' ) ) {
		return $ymd;
	}
	return (string) wp_date( 'F j, Y', $ts );
}

/**
 * The past rows to render right now, read live from the registry and notes.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_author_visits_past_rows() {
	$records = function_exists( 'bhp_school_visit_records' ) ? bhp_school_visit_records() : array();

	/**
	 * Filter the past rows rendered on `/author-visits/`.
	 *
	 * Exists so the trust column can be previewed or exercised without writing
	 * either live option. It is NOT a route for hardcoding visits.
	 *
	 * @param array $rows    Built rows.
	 * @param array $records The raw records they came from.
	 */
	return apply_filters(
		'bhp_author_visits_past_rows',
		bhp_author_visits_build_past_rows( $records, bhp_author_visits_today(), bhp_author_visits_notes() ),
		$records
	);
}

/**
 * Every photo across every past visit, flattened, newest visit first.
 *
 * ⭐ THE GALLERY IS DERIVED FROM THE PAST ROWS, NOT LISTED SEPARATELY. One
 *    source of truth means a visit cannot appear in the trust column with no
 *    photographs while its photographs appear in the gallery under no visit.
 *    It is also exactly the shape phase 2's `/gallery/` page needs, which is
 *    why it is a public function rather than a loop inside the template.
 *
 * @param array|null $past_rows Optional pre-built rows; read live when omitted.
 * @return array<int,array{file:string,alt:string,w:int,h:int,school:string,date_display:string,slug:string}>
 */
function bhp_author_visits_gallery_photos( $past_rows = null ) {
	$rows = is_array( $past_rows ) ? $past_rows : bhp_author_visits_past_rows();
	$out  = array();

	foreach ( $rows as $row ) {
		if ( empty( $row['photos'] ) || ! is_array( $row['photos'] ) ) {
			continue;
		}
		foreach ( $row['photos'] as $photo ) {
			$out[] = array(
				'file'         => isset( $photo['file'] ) ? (string) $photo['file'] : '',
				'alt'          => isset( $photo['alt'] ) ? (string) $photo['alt'] : '',
				'w'            => isset( $photo['w'] ) ? (int) $photo['w'] : 0,
				'h'            => isset( $photo['h'] ) ? (int) $photo['h'] : 0,
				'school'       => isset( $row['school'] ) ? (string) $row['school'] : '',
				'date_display' => isset( $row['date_display'] ) ? (string) $row['date_display'] : '',
				'slug'         => isset( $row['slug'] ) ? (string) $row['slug'] : '',
			);
		}
	}

	return $out;
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
