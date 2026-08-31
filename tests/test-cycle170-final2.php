<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CYCLE170-LD-FINAL2 — the founder-verdict pass. Theme 1.19.339 (2026-08-30).
 * STAGING ONLY.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Carrier item 562, relayed through Gandalf in the build brief. ⛔ RELAYED, NOT
 * WITNESSED FIRST-HAND BY THIS SUITE'S AUTHOR (§9.2 rule 3) — the same
 * provenance, stated the same way, as items 534 and 541.
 *
 *   1. COLLAPSE SEPTEMBER. The four September cards leave the picker. One quiet
 *      line stands in their place: "September is full. October onward is open."
 *      ⛔ Server-side validation UNCHANGED — September was already refused.
 *   2. TRIM "free" TO 2 IN THE HERO. The `<h1>` and the CTA button keep theirs.
 *      Chip 1 becomes "Boise-area schools". "There is no charge." is removed.
 *   3. MONTH SELECTOR replaces the flat week list. October by default,
 *      November and December behind their tabs. Selection, backup and every
 *      validation UNCHANGED.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ WHAT THIS SUITE IS FOR, AND WHERE IT DELIBERATELY STOPS.
 * ---------------------------------------------------------------------------
 * It asserts SOURCE and RENDERED HTML. ⛔ IT CANNOT PROVE WHAT A RULE COMPUTES
 * TO, and after `CYCLE170-LD-WALKFIX` that limitation is not theoretical: two of
 * four certified patches in that release parsed correctly, appeared in the
 * served stylesheet, passed every source-level assertion — AND LOST THE CASCADE.
 * ⭐ Every contrast and geometry claim about this release lives in the deploy
 * plan and was measured in a real browser with `window.innerWidth` asserted in
 * the same eval. A green run here is necessary and is not sufficient.
 *
 * ⛔ IT ALSO CANNOT PROVE KEYBOARD BEHAVIOUR. `role="tab"`, the roving
 *    `tabindex`, the arrow keys and the `required` move are all applied by
 *    `assets/js/readaloud-calendar.js` AT RUNTIME. This suite asserts the source
 *    of that file contains the mechanism; only the browser proves it runs. Both
 *    were done, and the browser evidence is in the deploy plan.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE NAIVE-NEEDLE TRAP, FIFTH AND SIXTH INSTANCES RECORDED, AND GUARDED HERE
 * ---------------------------------------------------------------------------
 * `style.css` quotes its own superseded declarations verbatim, everywhere, on
 * purpose — and so does this release. A `strpos()` for `There is no charge.` or
 * for `Free for Boise-area schools` over the RAW template finds the superseded
 * text INSIDE THE COMMENT THAT RETIRED IT and reports the exact opposite of the
 * truth. ⭐ EVERY SOURCE NEEDLE IN THIS FILE RUNS OVER COMMENT-STRIPPED CODE,
 * and the two functions that do the stripping are the first thing below.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

function bhp_f2_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_f2_pass'] ) ) {
		$GLOBALS['bhp_f2_pass'] = 0;
		$GLOBALS['bhp_f2_fail'] = 0;
	}
	if ( $cond ) {
		$GLOBALS['bhp_f2_pass']++;
		echo "  PASS  {$msg}\n";
	} else {
		$GLOBALS['bhp_f2_fail']++;
		echo "  FAIL  {$msg}\n";
	}
}

/** Strip `/* … *\/` comments. For CSS and for the PHP block comments in a template. */
function bhp_f2_strip_block_comments( $src ) {
	return (string) preg_replace( '#/\*.*?\*/#s', '', (string) $src );
}

/** Strip `//` and `#` line comments too, for JavaScript and PHP sources. */
function bhp_f2_code_only( $src ) {
	$src = bhp_f2_strip_block_comments( $src );
	return (string) preg_replace( '#^\s*//.*$#m', '', $src );
}

/** Pull one declaration out of the rule whose selector matches EXACTLY. */
function bhp_f2_decl_exact( $code, $selector, $prop ) {
	if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $code, $m, PREG_SET_ORDER ) ) {
		return null;
	}
	$found = null;
	foreach ( $m as $rule ) {
		$sel = trim( preg_replace( '/\s+/', ' ', $rule[1] ) );
		if ( $sel !== $selector ) {
			continue;
		}
		if ( preg_match( '/(?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*([^;]+)/', $rule[2], $d ) ) {
			$found = trim( $d[1] ); // LAST wins, as the cascade does.
		}
	}
	return $found;
}

echo "\n=== CYCLE170-LD-FINAL2 · theme 1.19.339 · carrier item 562 ===\n";

$bhp_f2_tpl_dir  = get_template_directory();
$bhp_f2_css_raw  = (string) file_get_contents( $bhp_f2_tpl_dir . '/style.css' );
$bhp_f2_css      = bhp_f2_strip_block_comments( $bhp_f2_css_raw );
$bhp_f2_min      = (string) file_get_contents( $bhp_f2_tpl_dir . '/style.min.css' );
$bhp_f2_js_raw   = (string) file_get_contents( $bhp_f2_tpl_dir . '/assets/js/readaloud-calendar.js' );
$bhp_f2_js       = bhp_f2_code_only( $bhp_f2_js_raw );
$bhp_f2_page_raw = (string) file_get_contents( $bhp_f2_tpl_dir . '/page-school-read-alouds.php' );
$bhp_f2_page     = bhp_f2_strip_block_comments( $bhp_f2_page_raw );
$bhp_f2_inc_raw  = (string) file_get_contents( $bhp_f2_tpl_dir . '/inc/school-read-alouds.php' );
$bhp_f2_inc      = bhp_f2_strip_block_comments( $bhp_f2_inc_raw );

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · VERSION, AND THE ARTEFACT THE BROWSER ACTUALLY LOADS
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 0 · VERSION ===\n";

preg_match( '/^Version:\s*(\S+)/m', $bhp_f2_css_raw, $bhp_f2_vm );
$bhp_f2_ver = isset( $bhp_f2_vm[1] ) ? $bhp_f2_vm[1] : '';
/*
 * ⭐ PIN MOVED TO 1.19.341 BY `CYCLE171-LD-341` (2026-08-31, later the same
 *    night). Same discipline the 1.19.340 note below records: the lane that
 *    bumps the theme owns every pin it breaks. The four stale pins belonging to
 *    OTHER lanes (ship-prep, triple, school-readaloud, cycle169-funnel) were
 *    already stale before this build and are STILL deliberately left alone.
 *    SUPERSEDED VALUE, PRESERVED: 1.19.340.
 * ⭐ PIN MOVED TO 1.19.340 BY `CYCLE170-LD-NAMEFIELD` (2026-08-31). This lane
 *    bumped the theme, so this lane owns the pin it broke — the same discipline
 *    every CYCLE170 lane before it followed. ⛔ The pins that were ALREADY red
 *    at 1.19.339 (ship-prep 1.19.332, triple 1.19.331, school-readaloud
 *    1.19.330) are STILL deliberately left alone: those are other lanes' debt,
 *    and adopting them would hide it.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM:
 *
 *      bhp_f2_assert( '1.19.339' === $bhp_f2_ver, "style.css declares 1.19.339, got '{$bhp_f2_ver}'" );
 */
bhp_f2_assert( '1.19.341' === $bhp_f2_ver, "style.css declares 1.19.341, got '{$bhp_f2_ver}'" );

/* ⭐ THE MINIFIED FILE IS WHAT SHIPS. A rule that lands in style.css and never
      reaches style.min.css is invisible on the live page and passes every
      source assertion above. The builder embeds the md5 of its input. */
preg_match( '/source-md5:\s*([0-9a-f]{32})/', $bhp_f2_min, $bhp_f2_mm );
$bhp_f2_embedded = isset( $bhp_f2_mm[1] ) ? $bhp_f2_mm[1] : '';
bhp_f2_assert(
	$bhp_f2_embedded === md5( $bhp_f2_css_raw ),
	'⭐⭐ style.min.css was rebuilt FROM THIS style.css (embedded source-md5 matches)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · COLLAPSE SEPTEMBER — the line, and everything that did NOT move with it
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 1 · COLLAPSE SEPTEMBER ===\n";

bhp_f2_assert( function_exists( 'bhp_readaloud_scheduler_september_line' ), 'bhp_readaloud_scheduler_september_line() exists' );

$bhp_f2_sept = function_exists( 'bhp_readaloud_scheduler_september_line' )
	? bhp_readaloud_scheduler_september_line()
	: '';

/* ⛔ CHARACTER-EXACT, against an independently typed literal — never against the
      function's own return value, which would be the code comparing itself. */
bhp_f2_assert(
	'September is full. October onward is open.' === $bhp_f2_sept,
	'⭐⭐ the September line is item 562 VERBATIM, character-exact'
);

/* ⛔ THE HOUSE COPY RAILS, ON THE CONSTANT, so an HTML entity cannot defeat them. */
bhp_f2_assert( false === strpos( $bhp_f2_sept, "\xe2\x80\x94" ), '⛔ no em dash in the September line' );
bhp_f2_assert( false === strpos( $bhp_f2_sept, "\xe2\x80\x93" ), '⛔ no en dash in the September line' );
bhp_f2_assert( ! preg_match( '/\b(we|us|our)\b/i', $bhp_f2_sept ), '⛔ §9.1: no "we", "us" or "our" in the September line' );
bhp_f2_assert( ! preg_match( '/\$\s?\d/', $bhp_f2_sept ), '⛔ no price, fee or rate in the September line' );

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE PART THAT MATTERS MOST IN THIS WHOLE SUITE.
 *     THE CARDS LEFT THE RENDER. THE RECORD AND THE GATE DID NOT MOVE.
 * ═══════════════════════════════════════════════════════════════════════════
 */
bhp_f2_assert( function_exists( 'bhp_readaloud_scheduler_closed_weeks' ), '⭐ closed_weeks() STILL EXISTS — the sealed record was not deleted' );
$bhp_f2_closed = function_exists( 'bhp_readaloud_scheduler_closed_weeks' )
	? bhp_readaloud_scheduler_closed_weeks( '2026-08-30' )
	: array();
bhp_f2_assert( 4 === count( $bhp_f2_closed ), '⭐⭐ ALL FOUR founder-sealed September rows are still returned, got ' . count( $bhp_f2_closed ) );
bhp_f2_assert(
	function_exists( 'bhp_readaloud_scheduler_closed_week_status_label' )
		&& 'Booked' === bhp_readaloud_scheduler_closed_week_status_label( 'booked' )
		&& 'Unavailable' === bhp_readaloud_scheduler_closed_week_status_label( 'unavailable' ),
	'⭐ the two status words are still exactly "Booked" and "Unavailable"'
);

/*
 * ⛔⛔ THE GATE. THIS IS THE ASSERTION THAT SAYS THIS RELEASE REMOVED NO
 *     VALIDATION. Every September Monday, and a September Thursday for good
 *     measure, is still refused by the SERVER's own offer check — the same one
 *     that refused them at 1.19.338.
 */
$bhp_f2_gate_bad = 0;
if ( function_exists( 'bhp_readaloud_scheduler_week_is_offered' ) ) {
	foreach ( array( '2026-09-01', '2026-09-07', '2026-09-14', '2026-09-21', '2026-09-28', '2026-09-03', '2026-09-30' ) as $bhp_f2_d ) {
		if ( bhp_readaloud_scheduler_week_is_offered( $bhp_f2_d ) ) {
			++$bhp_f2_gate_bad;
		}
	}
} else {
	$bhp_f2_gate_bad = 99;
}
bhp_f2_assert( 0 === $bhp_f2_gate_bad, '⛔⛔ the SERVER still refuses every September week — collapsing the cards moved NO gate' );

bhp_f2_assert(
	function_exists( 'bhp_readaloud_scheduler_floor_date' ) && '2026-10-01' === bhp_readaloud_scheduler_floor_date(),
	'⛔ the 1.19.334 calendar floor is still 2026-10-01, untouched'
);

/* ⛔ THE CARD MARKUP IS GONE FROM THE TEMPLATE'S CODE. Needle run on
      comment-stripped source, because the retirement comment names the class. */
bhp_f2_assert(
	false === strpos( $bhp_f2_inc, 'readaloud-sched__week--closed' ),
	'⛔ the template no longer PRINTS a closed card (comment-stripped source)'
);
bhp_f2_assert(
	false !== strpos( $bhp_f2_inc, 'bhp_readaloud_scheduler_september_line()' ),
	'the template prints the September line through the one function'
);

/*
 * ⚠️ THE CLOSED-CARD CSS IS KEPT ON PURPOSE and this asserts it, so a later
 *    tidy-up pass that deletes "dead" rules fails here instead of silently
 *    removing the 1.19.338 contrast fix that lives inside one of them.
 */
bhp_f2_assert(
	false !== strpos( $bhp_f2_css, '.school-readalouds .readaloud-sched__week--closed' ),
	'⚠️ the closed-card CSS is DELIBERATELY KEPT (walkfix asserts a contrast fix inside it)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · TRIM "free" TO EXACTLY TWO IN THE HERO
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 2 · THE HERO SAYS \"free\" EXACTLY TWICE ===\n";

bhp_f2_assert( function_exists( 'bhp_readaloud_hero_chips' ), 'bhp_readaloud_hero_chips() exists' );
$bhp_f2_chips = function_exists( 'bhp_readaloud_hero_chips' ) ? array_values( bhp_readaloud_hero_chips() ) : array();

bhp_f2_assert(
	array( 'Boise-area schools', 'October onward', 'I confirm every request personally' ) === $bhp_f2_chips,
	'⭐ the three chips are character-exact, in order, and chip 1 has dropped "Free"'
);
bhp_f2_assert( 3 === count( $bhp_f2_chips ), 'still exactly THREE chips, got ' . count( $bhp_f2_chips ) );
bhp_f2_assert(
	! preg_match( '/free/i', implode( ' ', $bhp_f2_chips ) ),
	'⛔ NOT ONE chip contains the word "free" in any case'
);

/* ⛔ THE CHIPS STILL CARRY NO PUNCTUATION OF THEIR OWN. Unchanged property from
      1.19.333; re-asserted because chip 1's string moved. */
$bhp_f2_chip_punct = 0;
foreach ( $bhp_f2_chips as $bhp_f2_chip ) {
	if ( false !== strpos( $bhp_f2_chip, '·' ) || false !== strpos( $bhp_f2_chip, '|' ) || $bhp_f2_chip !== trim( $bhp_f2_chip ) ) {
		++$bhp_f2_chip_punct;
	}
}
bhp_f2_assert( 0 === $bhp_f2_chip_punct, '⛔ no chip carries a separator character or stray padding' );

/* ⛔ THE HERO NOTE IS GONE FROM THE TEMPLATE'S CODE — comment-stripped, because
      the superseded block is quoted verbatim in the comment that retired it. */
bhp_f2_assert(
	false === strpos( $bhp_f2_page, 'There is no charge.' ),
	'⛔ "There is no charge." no longer appears in template CODE (it survives in the retirement comment, which is correct)'
);
bhp_f2_assert(
	false !== strpos( $bhp_f2_page_raw, 'There is no charge.' ),
	'⭐ and the superseded string IS still quoted in a comment, so the removal is legible rather than silent'
);

/* ⭐ THE TWO SURVIVING SAYINGS ARE STILL ITEM 481'S OWN WORDS, UNTOUCHED. */
bhp_f2_assert(
	false !== strpos( $bhp_f2_page, "esc_html_e( 'Book a free read-aloud', 'brave-hearts' )" ),
	'⭐ the <h1> still reads "Book a free read-aloud" (saying 1 of 2)'
);
bhp_f2_assert(
	function_exists( 'bhp_school_readalouds_cta' )
		&& 'Book a FREE read-aloud' === bhp_school_readalouds_cta()['label'],
	'⭐ the CTA button still reads "Book a FREE read-aloud" (saying 2 of 2)'
);

/* ⭐ AND THE FACT IS STILL STATED FURTHER DOWN THE PAGE, OUTSIDE THE HERO. The
      ruling scoped the hero; item 541's fifth visit point is untouched. */
bhp_f2_assert(
	function_exists( 'bhp_readaloud_visit_shape_points' )
		&& in_array( 'I leave a signed copy for your classroom library, free.', bhp_readaloud_visit_shape_points(), true ),
	'⭐ item 541 is untouched — the offer is unchanged, only the hero count moved'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE MONTH SELECTOR — the grouping, PURE, and its two straddling weeks
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 3 · THE MONTH GROUPING (pure) ===\n";

bhp_f2_assert( function_exists( 'bhp_readaloud_scheduler_group_weeks_by_month' ), 'bhp_readaloud_scheduler_group_weeks_by_month() exists' );

if ( function_exists( 'bhp_readaloud_scheduler_group_weeks_by_month' ) && function_exists( 'bhp_readaloud_scheduler_build_weeks' ) ) {

	/* ⭐ PURE INPUTS. "Today" is an argument, so every number below is a flat
	      assertion rather than a fact only true on one date. */
	$bhp_f2_weeks  = bhp_readaloud_scheduler_build_weeks( '2026-08-30', 7, 112, '2026-10-01', 12 );
	$bhp_f2_months = bhp_readaloud_scheduler_group_weeks_by_month( $bhp_f2_weeks );

	bhp_f2_assert( 12 === count( $bhp_f2_weeks ), 'the fixture yields TWELVE weeks, got ' . count( $bhp_f2_weeks ) );
	bhp_f2_assert( 3 === count( $bhp_f2_months ), 'those twelve weeks group into THREE months, got ' . count( $bhp_f2_months ) );

	$bhp_f2_keys = array();
	$bhp_f2_sum  = 0;
	foreach ( $bhp_f2_months as $bhp_f2_m ) {
		$bhp_f2_keys[] = $bhp_f2_m['key'];
		$bhp_f2_sum   += count( $bhp_f2_m['weeks'] );
	}
	bhp_f2_assert( array( '2026-10', '2026-11', '2026-12' ) === $bhp_f2_keys, '⭐ the months come out in DATE ORDER: October, November, December' );

	/*
	 * ⛔⛔ NO WEEK IS LOST AND NO WEEK IS DUPLICATED. A card in two tabs would be
	 *     a second copy of one radio, which is a submittable-duplicate bug.
	 */
	bhp_f2_assert( 12 === $bhp_f2_sum, '⛔⛔ every one of the twelve weeks lands in exactly ONE month, total ' . $bhp_f2_sum );

	$bhp_f2_counts = array_map( function ( $m ) { return count( $m['weeks'] ); }, $bhp_f2_months );
	bhp_f2_assert( array( 5, 5, 2 ) === array_values( $bhp_f2_counts ), 'the split is 5 / 5 / 2, got ' . implode( ' / ', $bhp_f2_counts ) );

	bhp_f2_assert( 'October' === $bhp_f2_months[0]['label'], 'the first tab reads "October", got "' . $bhp_f2_months[0]['label'] . '"' );
	bhp_f2_assert( 'October 2026' === $bhp_f2_months[0]['full'], 'the first panel is named "October 2026", got "' . $bhp_f2_months[0]['full'] . '"' );

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ THE TWO STRADDLING WEEKS. THIS IS THE ONE JUDGEMENT IN THE GROUPING
	 *     AND IT IS WHY IT KEYS ON `value` RATHER THAN ON `start` OR `last`.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ "Week of October 1" starts on MONDAY 2026-09-28. Keying on `start` would
	 *    file it under a SEPTEMBER tab — on the one page whose whole floor exists
	 *    to keep the word September off the offer list.
	 * ⛔ "Week of November 30" ends on FRIDAY 2026-12-04. Keying on `last` would
	 *    file it under DECEMBER, and the tab would then disagree with the card's
	 *    own printed label in front of the reader.
	 */
	$bhp_f2_first = $bhp_f2_months[0]['weeks'][0];
	bhp_f2_assert( '2026-10-01' === $bhp_f2_first['value'], 'the first October card still posts 2026-10-01' );
	bhp_f2_assert( '2026-09-28' === $bhp_f2_first['start'], 'and its Monday really is 2026-09-28 — the straddle is real, not hypothetical' );
	bhp_f2_assert(
		false !== strpos( $bhp_f2_first['label'], 'October' ) && false === strpos( $bhp_f2_first['label'], 'September' ),
		'⛔⛔ the straddling first week is filed under OCTOBER and its label says October'
	);

	$bhp_f2_nov = $bhp_f2_months[1]['weeks'];
	$bhp_f2_nov_last = $bhp_f2_nov[ count( $bhp_f2_nov ) - 1 ];
	bhp_f2_assert( '2026-11-30' === $bhp_f2_nov_last['value'], 'the last November card posts 2026-11-30' );
	bhp_f2_assert(
		false !== strpos( $bhp_f2_nov_last['last'], '2026-12' ),
		'and it really does run into December — the second straddle is real too'
	);
	bhp_f2_assert( '2026-11' === $bhp_f2_months[1]['key'], '⛔⛔ the week of 30 November is filed under NOVEMBER, where its own label says it is' );

	/* ⛔ NO TAB LABEL ANYWHERE SAYS SEPTEMBER. */
	$bhp_f2_sept_tabs = 0;
	foreach ( $bhp_f2_months as $bhp_f2_m ) {
		if ( false !== stripos( $bhp_f2_m['label'], 'September' ) || false !== stripos( $bhp_f2_m['full'], 'September' ) ) {
			++$bhp_f2_sept_tabs;
		}
	}
	bhp_f2_assert( 0 === $bhp_f2_sept_tabs, '⛔⛔ NOT ONE month tab is named September' );

	/* ⭐ THE MONTH SET IS DERIVED, NEVER HARDCODED. A different floor must produce
	      a different first month with no code change. */
	$bhp_f2_alt = bhp_readaloud_scheduler_group_weeks_by_month(
		bhp_readaloud_scheduler_build_weeks( '2026-10-20', 7, 112, '2026-11-02', 12 )
	);
	bhp_f2_assert(
		! empty( $bhp_f2_alt ) && '2026-11' === $bhp_f2_alt[0]['key'] && 'November' === $bhp_f2_alt[0]['label'],
		'⭐ move the floor to November and the FIRST TAB becomes November — nothing is hardcoded'
	);

	/* ⛔ AN EMPTY OFFER LIST GROUPS TO NOTHING, rather than to one empty month
	      whose tab a teacher could click into a blank panel. */
	bhp_f2_assert( array() === bhp_readaloud_scheduler_group_weeks_by_month( array() ), '⛔ zero weeks group into ZERO months, not one empty one' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · THE TAB MARKUP AND THE SCRIPT — progressive enhancement, asserted
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 4 · PROGRESSIVE ENHANCEMENT ===\n";

/*
 * ⛔⛔ THE THREE PROPERTIES THAT MAKE THIS SAFE WITH NO SCRIPT, ASSERTED ON THE
 *     TEMPLATE SOURCE RATHER THAN TRUSTED TO THE COMMENT THAT CLAIMS THEM.
 */
bhp_f2_assert(
	false !== strpos( $bhp_f2_inc, 'href="#bhp-monthpanel-' ),
	'⭐⭐ the tabs are printed as REAL anchors with a real href — with no script they jump to their month'
);
bhp_f2_assert(
	! preg_match( '/monthpanel[^>]*\bhidden\b/s', $bhp_f2_inc ),
	'⛔⛔ NO panel is printed with a `hidden` attribute — a scriptless browser sees every week'
);
bhp_f2_assert(
	false === strpos( $bhp_f2_js, 'createElement' ),
	'⛔⛔ the script STILL creates no element and no control'
);
bhp_f2_assert(
	false === strpos( $bhp_f2_js, 'toLocaleDateString' ) && false === strpos( $bhp_f2_js, 'new Date' ),
	'⛔ the script STILL does no date arithmetic — the months were grouped on the server'
);

/* ⭐ THE ARIA MECHANISM IS IN THE SCRIPT. Asserted as source; proven in a browser. */
foreach ( array( "'role', 'tablist'", "'role', 'tab'", "'role', 'tabpanel'", 'aria-selected', 'aria-controls', 'ArrowRight', 'ArrowLeft', "'Home'", "'End'" ) as $bhp_f2_needle ) {
	bhp_f2_assert( false !== strpos( $bhp_f2_js, $bhp_f2_needle ), "the script applies/handles {$bhp_f2_needle}" );
}

/*
 * ⛔⛔ THE `required` MOVE. THIS IS A REAL BROWSER DEFECT BEING HANDLED. A
 *     `required` radio inside a `hidden` container cannot be focused, so Chrome
 *     refuses to submit and logs "not focusable" to the console and nowhere
 *     else. Deleting this breaks submission SILENTLY.
 */
bhp_f2_assert( false !== strpos( $bhp_f2_js, 'syncRequired' ), '⛔⛔ the script moves `required` onto the visible panel' );
bhp_f2_assert(
	false !== strpos( $bhp_f2_js, "removeAttribute('required')" ) && false !== strpos( $bhp_f2_js, "setAttribute('required'" ),
	'⛔⛔ and it does it by moving the attribute, not by disabling anything'
);
bhp_f2_assert(
	false === strpos( $bhp_f2_js, '.disabled = true' ) && false === strpos( $bhp_f2_js, "setAttribute('disabled'" ),
	'⛔⛔ NOTHING is disabled — a disabled input would stop posting, and a pick made under another tab must still post'
);

/* ⛔ THE CSS `[hidden]` GUARD. The base rule declares `display: block`, which
      out-specifies the user agent's own `[hidden] { display: none }`, so the
      guard has to be restated. Losing it silently un-hides every panel. */
bhp_f2_assert(
	'none' === bhp_f2_decl_exact( $bhp_f2_css, '.school-readalouds .readaloud-sched__monthpanel[hidden]', 'display' ),
	'⛔⛔ the `[hidden]` panel rule exists and declares display:none'
);

/* ⛔ THE 48px TOUCH MINIMUM AT 375. Founder requirement for this form. */
bhp_f2_assert(
	'3rem' === bhp_f2_decl_exact( $bhp_f2_css, '.school-readalouds .readaloud-sched__monthtab', 'min-height' ),
	'⛔ a month tab is at least 3rem / 48px tall'
);
bhp_f2_assert(
	false !== strpos( $bhp_f2_css, '.readaloud-sched__monthtab[aria-selected="true"]' ),
	'⭐ the selected state is driven by `aria-selected` as well as by a class, so the eye and the screen reader read one source'
);
bhp_f2_assert(
	false !== strpos( $bhp_f2_css, '.school-readalouds .readaloud-sched__monthtab:focus-visible' ),
	'⛔ a month tab has a visible focus ring'
);

/* ⛔ NO `opacity` ANYWHERE IN THE NEW TAB RULES — it dims text with the ground
      and takes the contrast with it, which is why the closed card refuses it. */
if ( preg_match_all( '/\.school-readalouds \.readaloud-sched__month[a-z]*[^{}]*\{([^{}]*)\}/', $bhp_f2_css, $bhp_f2_mb ) ) {
	$bhp_f2_op = 0;
	foreach ( $bhp_f2_mb[1] as $bhp_f2_block ) {
		if ( false !== strpos( $bhp_f2_block, 'opacity' ) ) {
			++$bhp_f2_op;
		}
	}
	bhp_f2_assert( 0 === $bhp_f2_op, '⛔ no month-selector rule uses `opacity`' );
}

/* ⭐ AND THE RULES REACHED THE MINIFIED ARTEFACT. */
foreach ( array( 'readaloud-sched__monthtab', 'readaloud-sched__monthpanel', 'readaloud-sched__september' ) as $bhp_f2_cls ) {
	bhp_f2_assert( false !== strpos( $bhp_f2_min, $bhp_f2_cls ), "⭐ .{$bhp_f2_cls} reached style.min.css" );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE RENDERED PAGE
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 5 · THE RENDERED PAGE ===\n";

$bhp_f2_url  = function_exists( 'bhp_school_readalouds_url' ) ? bhp_school_readalouds_url() : home_url( '/school-read-alouds/' );
$bhp_f2_res  = wp_remote_get( $bhp_f2_url, array( 'timeout' => 45, 'sslverify' => false ) );
$bhp_f2_code = (int) wp_remote_retrieve_response_code( $bhp_f2_res );
$bhp_f2_b    = wp_remote_retrieve_body( $bhp_f2_res );

bhp_f2_assert( 200 === $bhp_f2_code, "the page returns 200, got {$bhp_f2_code}" );

if ( 200 === $bhp_f2_code && '' !== $bhp_f2_b ) {

	/* ── 5a · September ───────────────────────────────────────────────────── */
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'September is full. October onward is open.' ), '⭐⭐ the September line renders VERBATIM' );
	bhp_f2_assert( 1 === substr_count( $bhp_f2_b, 'readaloud-sched__september' ), 'exactly ONE September line renders, got ' . substr_count( $bhp_f2_b, 'readaloud-sched__september' ) );
	bhp_f2_assert( 0 === substr_count( $bhp_f2_b, 'readaloud-sched__week--closed' ), '⛔ ZERO September cards render, got ' . substr_count( $bhp_f2_b, 'readaloud-sched__week--closed' ) );
	bhp_f2_assert( 0 === substr_count( $bhp_f2_b, 'Week of September' ), '⛔ the words "Week of September" appear ZERO times, got ' . substr_count( $bhp_f2_b, 'Week of September' ) );
	bhp_f2_assert( 0 === substr_count( $bhp_f2_b, 'value="2026-09-' ), '⛔⛔ ZERO September values reach the rendered page — unchanged from 1.19.335' );

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐⭐ 5b · THE "free" COUNT. THE FOUNDER'S RULING, ASSERTED AS A NUMBER.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE HERO SECTION IS PARSED OUT OF THE RENDERED PAGE FIRST. Counting
	 *    "free" over the whole document would count the capture band, the fifth
	 *    visit point and the toolkit copy, none of which the ruling scopes.
	 *
	 * ⚠️ THE COUNT IS TAKEN OVER THE HERO'S TEXT, NOT ITS HTML. A count over raw
	 *    markup would also count a class name or an href that happened to
	 *    contain the letters — "free" is a substring that turns up in URLs. The
	 *    tags are stripped and the entities decoded first.
	 */
	if ( preg_match( '/<section[^>]*readaloud-funnel__hero.*?<\/section>/s', $bhp_f2_b, $bhp_f2_hs ) ) {
		$bhp_f2_hero_html = $bhp_f2_hs[0];
		$bhp_f2_hero_txt  = html_entity_decode( wp_strip_all_tags( $bhp_f2_hero_html ), ENT_QUOTES, 'UTF-8' );
		$bhp_f2_free      = preg_match_all( '/free/i', $bhp_f2_hero_txt );

		bhp_f2_assert( 2 === $bhp_f2_free, "⭐⭐⭐ the hero says \"free\" EXACTLY TWICE, got {$bhp_f2_free}" );
		bhp_f2_assert( false !== strpos( $bhp_f2_hero_txt, 'Book a free read-aloud' ), '⭐ saying 1 of 2 is the <h1>' );
		bhp_f2_assert( false !== strpos( $bhp_f2_hero_txt, 'Book a FREE read-aloud' ), '⭐ saying 2 of 2 is the CTA button' );
		bhp_f2_assert( false === strpos( $bhp_f2_hero_txt, 'There is no charge.' ), '⛔ "There is no charge." is gone from the hero' );
		bhp_f2_assert( false !== strpos( $bhp_f2_hero_txt, 'Boise-area schools' ), '⭐ chip 1 renders as "Boise-area schools"' );
		bhp_f2_assert( false === strpos( $bhp_f2_hero_txt, 'Free for Boise-area schools' ), '⛔ the superseded chip does not render' );

		/* Unchanged rails: no price in the hero, and the photograph is still inside it. */
		bhp_f2_assert( ! preg_match( '/\$\s?\d/', $bhp_f2_hero_html ), '⛔ no price, fee or rate appears in the hero' );
		bhp_f2_assert( false !== strpos( $bhp_f2_hero_html, 'adams-elementary-read-aloud-hero.jpg' ), '⭐ the photograph is still INSIDE the hero section' );
		bhp_f2_assert( 3 === substr_count( $bhp_f2_hero_html, 'school-readalouds__chip"' ), 'still exactly THREE chips render, got ' . substr_count( $bhp_f2_hero_html, 'school-readalouds__chip"' ) );
		bhp_f2_assert( 0 === substr_count( $bhp_f2_hero_html, 'readaloud-funnel__hero-note' ), '⛔ the hero-note paragraph is gone from the markup entirely' );
	} else {
		bhp_f2_assert( false, 'the hero section parses out of the rendered page' );
	}

	/* ── 5c · The month selector ──────────────────────────────────────────── */
	bhp_f2_assert( 1 === substr_count( $bhp_f2_b, 'data-bhp-monthtabs' ), 'exactly ONE tab strip renders, got ' . substr_count( $bhp_f2_b, 'data-bhp-monthtabs' ) );
	bhp_f2_assert( 3 === substr_count( $bhp_f2_b, 'data-bhp-monthtab="' ), 'exactly THREE tabs render, got ' . substr_count( $bhp_f2_b, 'data-bhp-monthtab="' ) );
	bhp_f2_assert( 3 === substr_count( $bhp_f2_b, 'data-bhp-monthpanel="' ), 'exactly THREE panels render, got ' . substr_count( $bhp_f2_b, 'data-bhp-monthpanel="' ) );
	bhp_f2_assert( 3 === substr_count( $bhp_f2_b, '<ul class="readaloud-sched__weeklist">' ), 'exactly THREE week lists render, one per month, got ' . substr_count( $bhp_f2_b, '<ul class="readaloud-sched__weeklist">' ) );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'id="bhp-monthpanel-2026-10"' ), '⭐ the October panel renders with its own id' );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'id="bhp-monthpanel-2026-11"' ), '⭐ the November panel renders with its own id' );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'id="bhp-monthpanel-2026-12"' ), '⭐ the December panel renders with its own id' );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'href="#bhp-monthpanel-2026-10"' ), '⭐⭐ the October tab is a REAL anchor with a real href (the no-script path)' );

	/*
	 * ⛔⛔ THE FIRST TAB IS OCTOBER AND IT IS THE ONE MARKED ACTIVE IN THE
	 *     SERVER'S OWN OUTPUT, so the default does not depend on the script.
	 */
	bhp_f2_assert(
		preg_match( '/<a class="readaloud-sched__monthtab is-active"[^>]*href="#bhp-monthpanel-2026-10"/', $bhp_f2_b ),
		'⭐⭐ OCTOBER is the tab the server marks active — the default is not a script decision'
	);
	bhp_f2_assert(
		1 === preg_match_all( '/readaloud-sched__monthtab is-active/', $bhp_f2_b ),
		'exactly ONE tab is marked active, got ' . preg_match_all( '/readaloud-sched__monthtab is-active/', $bhp_f2_b )
	);

	/*
	 * ⛔⛔ AND NOTHING IS HIDDEN IN THE SERVER'S OUTPUT. This is the assertion
	 *     that proves the no-script path: a scriptless browser gets all twelve
	 *     week cards, not three.
	 */
	bhp_f2_assert(
		! preg_match( '/<div class="readaloud-sched__monthpanel"[^>]*\bhidden\b/', $bhp_f2_b ),
		'⛔⛔ NOT ONE panel is served `hidden` — with no script every week is on the page'
	);

	/* ── 5d · The mechanics did NOT change ────────────────────────────────── */
	bhp_f2_assert( 12 === substr_count( $bhp_f2_b, 'name="visit_week"' ), 'still exactly TWELVE first-choice radios, got ' . substr_count( $bhp_f2_b, 'name="visit_week"' ) );
	bhp_f2_assert( 12 === substr_count( $bhp_f2_b, 'name="visit_week_backup"' ), 'still exactly TWELVE backup radios, got ' . substr_count( $bhp_f2_b, 'name="visit_week_backup"' ) );
	bhp_f2_assert( 12 === substr_count( $bhp_f2_b, 'data-bhp-week="' ), 'still exactly TWELVE week cards, got ' . substr_count( $bhp_f2_b, 'data-bhp-week="' ) );
	bhp_f2_assert( 12 === substr_count( $bhp_f2_b, 'readaloud-sched__week-input--first' ), 'still TWELVE first-choice inputs, got ' . substr_count( $bhp_f2_b, 'readaloud-sched__week-input--first' ) );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'data-bhp-cal-summary' ), 'the running summary still renders' );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'ICU nurse' ), 'the honest line (item 534) is still above the picker' );
	bhp_f2_assert( 0 === substr_count( $bhp_f2_b, 'name="visit_date"' ), '⛔ no day control has come back' );
	bhp_f2_assert( 0 === substr_count( $bhp_f2_b, 'PENDING READ-BACK' ), '⛔ ZERO placeholders' );
	bhp_f2_assert( 0 === substr_count( $bhp_f2_b, 'data-popup-config' ), '⛔ ZERO popups — funnel isolation holds on the teacher page' );

	/* ⭐ THE SCRIPT IS ACTUALLY ENQUEUED ON THIS PAGE. A tab strip whose
	      enhancement never loads is a tab strip that only ever jumps. */
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'readaloud-calendar.js' ), '⭐ the enhancement script is enqueued on the page' );
	bhp_f2_assert( false !== strpos( $bhp_f2_b, 'style.min.css?ver=1.19.341' ), '⭐ the page links style.min.css?ver=1.19.341' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · WHAT THIS RELEASE DID NOT TOUCH
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== 6 · UNCHANGED ===\n";

/* ⛔ `/author-visits/` CAN NEVER BE 301'd (item 524). Re-asserted on every
      release that touches this page, because it is the one irreversible mistake
      available here: printed QR codes point at it. */
bhp_f2_assert(
	function_exists( 'bhp_school_readalouds_merged_slugs' )
		&& ! in_array( 'author-visits', bhp_school_readalouds_merged_slugs(), true ),
	'⛔⛔ `author-visits` is STILL absent from the merged-slug list — printed paper is safe'
);

bhp_f2_assert(
	function_exists( 'bhp_readaloud_scheduler_honest_line' )
		&& false !== strpos( bhp_readaloud_scheduler_honest_line(), 'ICU nurse' ),
	'⛔ item 534\'s honest line is untouched'
);
bhp_f2_assert(
	function_exists( 'bhp_readaloud_scheduler_week_count' ) && 12 === bhp_readaloud_scheduler_week_count(),
	'⛔ the twelve-week cap is unchanged'
);
bhp_f2_assert(
	function_exists( 'bhp_readaloud_scheduler_weekdays' ) && 5 === count( bhp_readaloud_scheduler_weekdays() ),
	'⛔ the five weekday preferences are unchanged'
);
bhp_f2_assert(
	function_exists( 'bhp_readaloud_scheduler_slots' ) && 2 === count( bhp_readaloud_scheduler_slots() ),
	'⛔ Morning / Afternoon are unchanged'
);

$bhp_f2_p = isset( $GLOBALS['bhp_f2_pass'] ) ? $GLOBALS['bhp_f2_pass'] : 0;
$bhp_f2_f = isset( $GLOBALS['bhp_f2_fail'] ) ? $GLOBALS['bhp_f2_fail'] : 0;
echo "\n=== CYCLE170-LD-FINAL2 RESULT: {$bhp_f2_p} PASS / {$bhp_f2_f} FAIL ===\n";
