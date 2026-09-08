<?php
/**
 * THE UNLIMITED AFTER-VISIT PHASE, AND THE "HOW IT WORKS" DISPLAY GATE.
 * Theme 1.19.358, bundle plugin 1.8.83. `CYCLE179-LD-358`.
 * Founder direction seals 870 and 874.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-358.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ `--url=` IS NOT OPTIONAL. Without it WP-CLI resolves the wrong site for a
 *    multi-host install and every URL this suite builds is wrong in a way that
 *    still looks plausible.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE WHAT `/author-visits/` ACTUALLY RENDERS TODAY. PHP here has
 *    no request and no rendered page, and the live registry is DATA this lane
 *    reads and never writes. What is proved here is the PURE decision:
 *    `bhp_author_visits_has_open_row()` on synthetic rows, plus the source
 *    shape of the template. ⭐ THE RENDERED PAGE IS PROVED IN A BROWSER, at an
 *    asserted `window.innerWidth`, filed under `pdp-redesign\358-STAGING\`.
 *
 * ⛔ IT ASSERTS NOTHING ABOUT ANY REAL SCHOOL. Every row below is synthetic.
 *
 * ⛔ "UNLIMITED" IS PROVED BY SAMPLING, NOT BY EXHAUSTION. A finite suite
 *    cannot test every future date. It samples visit + 31, + 365, + 3650 and
 *    a full 401-day sweep, and it additionally asserts the SOURCE SHAPE that
 *    makes the bound impossible: that the unlimited branch returns true before
 *    any end date is consulted.
 *
 * WHAT IT ASSERTS
 *   §1  the versions moved in every file that carries one
 *   §2  CHANGE 1: the after phase has no upper bound, by value and by source
 *   §3  CHANGE 1: the constant and filter express "unlimited" cleanly
 *   §4  CHANGE 2: the "How It Works" gate, in BOTH states
 *   §5  CHANGE 2: the copy is untouched and the gate is display-only
 *   §6  the guardrails this release must not have crossed
 *
 * @package Brave_Hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build.
 */
$GLOBALS['c358_failures'] = 0;
$GLOBALS['c358_passes']   = 0;
$GLOBALS['c358_skips']    = 0;

function c358_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['c358_passes']++;
		echo "PASS  {$label}\n";
		return;
	}
	$GLOBALS['c358_failures']++;
	echo "FAIL  {$label}\n";
}

function c358_skip( $label, $why ) {
	$GLOBALS['c358_skips']++;
	echo "SKIP  {$label} -- {$why}\n";
}

/*
 * ⛔ EVERY SOURCE READ IS NORMALISED TO \n BEFORE ANYTHING IS SEARCHED IN IT.
 *    `CYCLE179-LD-355` recorded a self-inflicted SKIP caused by exactly this:
 *    the plugin's `school-visit-pickup.php` is a CRLF file, so a needle written
 *    with "\n" matched nothing. A skip is not a pass.
 */
function c358_read( $relative_to_theme ) {
	$path = get_template_directory() . '/' . ltrim( $relative_to_theme, '/' );
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
}

/*
 * ⛔ THE PLUGIN IS NOT INSIDE THE THEME ON A DEPLOYED SITE. `CYCLE179-LD-356`
 *    learned this the expensive way: reading a plugin file through the theme
 *    path returns '', and an "this literal is ABSENT" assertion is satisfied by
 *    an empty string, so three checks reported PASS while reading nothing.
 *    Every plugin assertion below is gated on a non-empty source and reports
 *    SKIP, never PASS, when it cannot be read.
 */
function c358_read_plugin( $relative_to_plugin ) {
	$candidates = array();
	if ( defined( 'WP_PLUGIN_DIR' ) ) {
		$candidates[] = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/' . ltrim( $relative_to_plugin, '/' );
	}
	$candidates[] = get_template_directory() . '/plugins/brave-hearts-bundle-pricing/' . ltrim( $relative_to_plugin, '/' );

	foreach ( $candidates as $path ) {
		if ( file_exists( $path ) ) {
			return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
		}
	}
	return '';
}

/*
 * ⛔ THE CLOSING BRACE IS READ OFF COLUMN ZERO, NOT BY COUNTING BRACES. The
 *    357 suite's first extractor counted braces and returned '' silently
 *    because a docblock in this codebase quotes superseded code containing one
 *    unbalanced `{`. Three assertions did not run at all. Every extraction
 *    below is asserted NON-EMPTY before anything is asserted about it.
 */
function c358_function_body( $source, $signature ) {
	$start = strpos( $source, $signature );
	if ( false === $start ) {
		return '';
	}
	$end = strpos( $source, "\n}\n", $start );
	if ( false === $end ) {
		return '';
	}
	return substr( $source, $start, $end - $start + 2 );
}

/**
 * Strip comments so a "the source contains X" check cannot be satisfied by a
 * docblock that QUOTES X while superseding it.
 *
 * ⛔ THIS IS NOT DECORATION IN THIS CODEBASE. Both files edited by this release
 *    preserve their superseded lines verbatim inside comments, by house rule.
 *    A naive `strpos()` for the old condition would find it in the comment and
 *    report that nothing changed.
 */
function c358_code_only( $source ) {
	$out = preg_replace( '!/\*.*?\*/!s', '', (string) $source );
	$out = preg_replace( '!^\s*//.*$!m', '', (string) $out );
	return (string) $out;
}

$c358_css    = c358_read( 'style.css' );
$c358_tmpl   = c358_read( 'page-author-visits.php' );
$c358_visits = c358_read( 'inc/author-visits.php' );
$c358_pickup = c358_read_plugin( 'includes/school-visit-pickup.php' );
$c358_boot   = c358_read_plugin( 'brave-hearts-bundle-pricing.php' );

echo "\n=== CYCLE179-LD-358 - unlimited after-visit phase + the How It Works gate ===\n\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE VERSIONS
 *
 * ⭐ A DRIFT CHECK PLUS `>=`, NOT AN EQUALITY PIN. The house pattern, already
 *    used at `test-cycle179-visit-band-f10.php` §0.5 and applied to
 *    `test-cycle179-catalog-356.php` §1 by the previous lane: what §1 is FOR is
 *    that the version moved and that the plugin's two copies of its own version
 *    have not drifted apart. Pinning one frozen number additionally asserts
 *    "no later release exists", which every future release must falsify.
 * ═══════════════════════════════════════════════════════════════════════════ */

c358_assert( '' !== $c358_css, '1.1 style.css is readable' );

$c358_live_theme = (string) wp_get_theme()->get( 'Version' );
c358_assert(
	false !== strpos( $c358_css, 'Version: ' . $c358_live_theme ),
	'1.2 style.css declares the running theme version (reads: ' . $c358_live_theme . ')'
);
c358_assert(
	version_compare( $c358_live_theme, '1.19.358', '>=' ),
	'1.3 the ACTIVE theme reports 1.19.358 or later (reads: ' . $c358_live_theme . ')'
);

$c358_live_plugin = defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? (string) BHP_BUNDLE_PRICING_VERSION : '';

if ( '' === $c358_boot || '' === $c358_live_plugin ) {
	c358_skip( '1.4 the plugin header matches the LOADED constant', 'the plugin bootstrap or constant could not be read' );
	c358_skip( '1.5 the plugin CONSTANT matches the LOADED constant', 'the plugin bootstrap or constant could not be read' );
	c358_skip( '1.6 the LOADED plugin constant is 1.8.83 or later', 'the plugin bootstrap or constant could not be read' );
} else {
	c358_assert( false !== strpos( $c358_boot, 'Version: ' . $c358_live_plugin ), '1.4 the plugin header matches the LOADED constant (reads: ' . $c358_live_plugin . ')' );
	c358_assert( false !== strpos( $c358_boot, "BHP_BUNDLE_PRICING_VERSION', '" . $c358_live_plugin . "'" ), '1.5 the plugin CONSTANT in the file matches the LOADED constant' );
	c358_assert( version_compare( $c358_live_plugin, '1.8.83', '>=' ), '1.6 ⭐ the LOADED plugin constant is 1.8.83 or later (reads: ' . $c358_live_plugin . ') - change 1 lives in the PLUGIN, so it had to move' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · CHANGE 1 — THE AFTER PHASE HAS NO UPPER BOUND
 *
 * Andrew Signore, RELAYED through the `chief-of-staff` and NOT witnessed
 * first-hand by this agent (Standing Rules 9.2 rule 2), seal 870: the
 * after-visit state stays open INDEFINITELY, no 30-day expiry. Seal 874: the
 * state machine is GENERIC and AUTOMATIC for every registry visit now and in
 * future, built as date logic on the registry row and never as a per-school
 * flag.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_school_visit_is_after_on' ) ) {
	c358_skip( '2.x the unbounded after phase', 'bundle plugin 1.8.83 is not loaded' );
} else {
	$v   = '2026-04-15';                                  // synthetic
	$day = static function ( $offset ) use ( $v ) {
		return gmdate( 'Y-m-d', strtotime( $v . ' 00:00:00 UTC' ) + ( $offset * 86400 ) );
	};

	/* ⭐ THE OPENING BOUND DID NOT MOVE, AND IT IS ASSERTED FIRST so a reader
	      sees the half that is unchanged before the half that changed. */
	c358_assert( false === bhp_school_visit_is_after_on( $v, $day( -2 ) ), '2.1 visit - 2 (ordering open) is NOT after - unchanged' );
	c358_assert( false === bhp_school_visit_is_after_on( $v, $day( -1 ) ), '2.2 ⛔ visit - 1 (the closed band) is NOT after - unchanged' );
	c358_assert( true === bhp_school_visit_is_after_on( $v, $day( 0 ) ), '2.3 ⭐ the VISIT DATE ITSELF is after, from 00:00 site time - unchanged' );

	/* ⭐⭐ THE BOUNDARY CASES THE BRIEF NAMES. visit + 30 was the last day of
	       the old window; visit + 31 was the first day outside it and is the
	       one date that proves the bound is GONE rather than merely moved. */
	c358_assert( true === bhp_school_visit_is_after_on( $v, $day( 30 ) ), '2.4 visit + 30 (the OLD last day) is after (' . $day( 30 ) . ')' );
	c358_assert( true === bhp_school_visit_is_after_on( $v, $day( 31 ) ), '2.5 ⭐⭐ visit + 31 IS AFTER (' . $day( 31 ) . ') - the 30-day expiry is REMOVED' );
	c358_assert( true === bhp_school_visit_is_after_on( $v, $day( 365 ) ), '2.6 ⭐⭐ visit + 365 IS AFTER (' . $day( 365 ) . ') - and it is not a larger bound either' );
	c358_assert( true === bhp_school_visit_is_after_on( $v, $day( 3650 ) ), '2.7 ⭐ visit + 3650 is after - "indefinitely" is meant literally' );

	/* ⛔ A SWEEP, because three sampled dates cannot distinguish "unlimited"
	      from "a bound that happens to sit between the samples". */
	$fell_out = array();
	for ( $d = 0; $d <= 400; $d++ ) {
		if ( ! bhp_school_visit_is_after_on( $v, $day( $d ) ) ) {
			$fell_out[] = $day( $d );
		}
	}
	c358_assert( array() === $fell_out, '2.8 ⭐⭐ all 401 days from the visit onward are AFTER - not one falls out of the phase' );

	/* ⛔ BOTH FAIL-CLOSED BRANCHES SURVIVED THE EDIT. Removing an upper bound
	      must not have removed a guard. */
	c358_assert( false === bhp_school_visit_is_after_on( 'not-a-date', $day( 1 ) ), '2.9 ⛔ an unusable visit date still fails CLOSED' );
	c358_assert( false === bhp_school_visit_is_after_on( $v, '' ), '2.10 ⛔ an empty today still fails CLOSED (it does NOT mirror is_open_on)' );

	/* ⭐ SEAL 874, ASSERTED RATHER THAN ASSUMED: date logic on the row, no
	      per-school flag. The predicate answers from two dates and nothing
	      else, so a second visit with a different date behaves identically
	      with nothing switched on for it. */
	$v2 = '2019-11-04';
	c358_assert( true === bhp_school_visit_is_after_on( $v2, '2026-04-15' ), '2.11 ⭐ seal 874: a DIFFERENT synthetic visit is after too, with no per-school flag set anywhere' );
	c358_assert( false === bhp_school_visit_is_after_on( '2099-01-01', '2026-04-15' ), '2.12 ⭐ ...and a future one is not, from the same two dates alone' );

	/* ⛔ THE SOURCE SHAPE THAT MAKES THE BOUND IMPOSSIBLE. A finite suite
	      cannot sample every date, so the structural claim is asserted too:
	      the unlimited branch returns TRUE before any end date is compared. */
	if ( '' === $c358_pickup ) {
		c358_skip( '2.13 the unlimited branch precedes the end-date comparison', 'school-visit-pickup.php could not be read' );
	} else {
		$body = c358_function_body( $c358_pickup, 'function bhp_school_visit_is_after_on(' );
		c358_assert( '' !== $body, '2.13a bhp_school_visit_is_after_on() was located' );
		if ( '' !== $body ) {
			$code = c358_code_only( $body );
			$pos_null_true = strpos( $code, 'null === $end' );
			$pos_cmp       = strpos( $code, '$today <= $end' );
			c358_assert( false !== $pos_null_true, '2.13b the source carries an explicit `null === $end` branch' );
			c358_assert( false !== $pos_cmp, '2.13c ...and still carries the bounded comparison for a filtered window' );
			c358_assert(
				false !== $pos_null_true && false !== $pos_cmp && $pos_null_true < $pos_cmp,
				'2.13d ⭐⭐ the UNLIMITED branch is reached BEFORE the end-date comparison, so no bound can be applied when there is no end'
			);
			c358_assert(
				false === strpos( $code, 'empty( $end )' ),
				'2.13e ⛔⛔ `empty( $end )` appears NOWHERE - it cannot tell "no end exists" (null) from "unusable date" (""), which are OPPOSITE answers'
			);
		}
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · CHANGE 1 — THE CONSTANT AND THE FILTER EXPRESS "UNLIMITED" CLEANLY
 *
 * ⭐ THE BRIEF'S OWN TEST FOR KEEPING THEM WAS EXACTLY THIS. They survive only
 *    because `0`/`null` says "no limit" without a second constant, a sentinel
 *    string or a special case in every caller.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_school_visit_after_days' ) ) {
	c358_skip( '3.x the constant and filter semantics', 'bundle plugin 1.8.83 is not loaded' );
} else {
	c358_assert( null === bhp_school_visit_after_days(), '3.1 ⭐⭐ THE DEFAULT IS UNLIMITED - bhp_school_visit_after_days() returns null' );
	c358_assert( defined( 'BHP_SCHOOL_VISIT_AFTER_DAYS' ), '3.2 the constant still exists, as the brief allows' );
	if ( defined( 'BHP_SCHOOL_VISIT_AFTER_DAYS' ) ) {
		c358_assert( 0 === (int) BHP_SCHOOL_VISIT_AFTER_DAYS, '3.3 ⭐ the constant ships as 0, and 0 MEANS NO LIMIT' );
	}

	$bound = static function () {
		return 5;
	};
	add_filter( 'bhp_school_visit_after_days', $bound );
	c358_assert( 5 === bhp_school_visit_after_days(), '3.4 the filter can still impose a bounded window' );
	remove_filter( 'bhp_school_visit_after_days', $bound );
	c358_assert( null === bhp_school_visit_after_days(), '3.5 ⛔ ...and removing it returns to UNLIMITED - the bound did not stick' );

	foreach ( array(
		'zero'     => 0,
		'null'     => null,
		'negative' => -7,
	) as $name => $value ) {
		$f = static function () use ( $value ) {
			return $value;
		};
		add_filter( 'bhp_school_visit_after_days', $f );
		c358_assert( null === bhp_school_visit_after_days(), "3.6 ⭐ a filter returning {$name} means UNLIMITED" );
		remove_filter( 'bhp_school_visit_after_days', $f );
	}

	$junk = static function () {
		return 'thirty';
	};
	add_filter( 'bhp_school_visit_after_days', $junk );
	c358_assert( null === bhp_school_visit_after_days(), '3.7 ⛔ a filter returning nonsense is DISCARDED back to the default' );
	remove_filter( 'bhp_school_visit_after_days', $junk );

	/*
	 * ⛔⛔ THE FAILURE DIRECTION IS INVERTED FROM 1.8.82, AND THAT INVERSION IS
	 *     THE POINT. 1.8.82 discarded a bad filter value to 30 days so a broken
	 *     hook could not leave a band up forever. Forever is now the RULING, so
	 *     a broken hook must not be able to CLOSE a window the founder ordered
	 *     left open. Same discard, opposite default.
	 */
	add_filter( 'bhp_school_visit_after_days', $junk );
	if ( function_exists( 'bhp_school_visit_is_after_on' ) ) {
		c358_assert(
			true === bhp_school_visit_is_after_on( '2026-04-15', '2027-04-15' ),
			'3.8 ⛔⛔ under a BROKEN hook the phase is still open a year later - a bad filter cannot revoke seal 870'
		);
	} else {
		c358_skip( '3.8 a broken hook cannot revoke seal 870', 'bhp_school_visit_is_after_on() is not loaded' );
	}
	remove_filter( 'bhp_school_visit_after_days', $junk );

	/* ⛔ THE null / "" SPLIT ON THE END DATE. Opposite answers; `empty()`
	      cannot tell them apart, which is why 2.13e exists. */
	if ( function_exists( 'bhp_school_visit_after_end_date' ) ) {
		c358_assert( null === bhp_school_visit_after_end_date( '2026-04-15' ), '3.9 ⭐ an unlimited window has NO end date - null' );
		add_filter( 'bhp_school_visit_after_days', $bound );
		c358_assert( '2026-04-20' === bhp_school_visit_after_end_date( '2026-04-15' ), '3.10 a bounded window yields the real last day' );
		c358_assert( '' === bhp_school_visit_after_end_date( 'not-a-date' ), '3.11 ⛔ an unusable date yields "" - distinct from null, and still fail-closed' );
		remove_filter( 'bhp_school_visit_after_days', $bound );
	} else {
		c358_skip( '3.9-3.11 the end-date null/empty split', 'bhp_school_visit_after_end_date() is not loaded' );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · CHANGE 2 — THE "HOW IT WORKS" DISPLAY GATE, IN BOTH STATES
 *
 * `CYCLE179-LD-23`, raised by this lane at 1.19.357 and OBSERVED on the live
 * page rather than inferred: `/author-visits/` could show a "Read-aloud done"
 * card and the hand-delivery "How It Works" steps ON ONE SCREEN. Step 2 tells a
 * parent to choose free author hand-delivery at checkout; after the read-aloud
 * that option is gone and the order ships.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_author_visits_has_open_row' ) ) {
	c358_skip( '4.x the How It Works gate', 'bhp_author_visits_has_open_row() is not loaded - theme older than 1.19.358' );
} else {
	/* ── STATE A: at least one visit is OPEN. The block RENDERS. ────────── */
	c358_assert(
		true === bhp_author_visits_has_open_row( array( array( 'open' => true ) ) ),
		'4.1 ⭐ STATE A: one open row -> the steps render'
	);
	c358_assert(
		true === bhp_author_visits_has_open_row( array( array( 'open' => false, 'after' => true ), array( 'open' => true ) ) ),
		'4.2 ⭐ STATE A: an after-visit row NEXT TO an open row still renders them - one live hand-delivery visit is enough'
	);
	c358_assert(
		true === bhp_author_visits_has_open_row( array( array( 'open' => false ), array( 'open' => false ), array( 'open' => true ) ) ),
		'4.3 the open row is found wherever it sits in the list'
	);

	/* ── STATE B: NO visit is open. The block is NOT rendered. ──────────── */
	c358_assert(
		false === bhp_author_visits_has_open_row( array() ),
		'4.4 ⭐ STATE B: no rows at all -> no steps (this case behaved correctly before 1.19.358 too)'
	);
	c358_assert(
		false === bhp_author_visits_has_open_row( array( array( 'open' => false, 'after' => true ) ) ),
		'4.5 ⭐⭐ STATE B: an AFTER-VISIT row alone -> NO steps. THIS IS CYCLE179-LD-23, AND IT IS THE CASE THAT WAS WRONG BEFORE 1.19.358'
	);
	c358_assert(
		false === bhp_author_visits_has_open_row( array( array( 'open' => false, 'after' => false ) ) ),
		'4.6 ⭐⭐ STATE B: a CLOSED row alone (visit - 1, books already packed) -> NO steps. Also wrong before 1.19.358'
	);

	/* ⛔ MALFORMED INPUT FAILS TO "DO NOT SHOW". Showing hand-delivery
	      instructions is the harmful direction, so that is the direction the
	      guard closes. */
	c358_assert( false === bhp_author_visits_has_open_row( 'not-an-array' ), '4.7 ⛔ a non-array fails CLOSED' );
	c358_assert( false === bhp_author_visits_has_open_row( null ), '4.8 ⛔ null fails CLOSED' );
	c358_assert( false === bhp_author_visits_has_open_row( array( 'not-a-row' ) ), '4.9 ⛔ a non-array row is skipped, not truthy-tested' );
	c358_assert( false === bhp_author_visits_has_open_row( array( array( 'school' => 'Sample Elementary' ) ) ), '4.10 ⛔ a row with no `open` key at all fails CLOSED' );

	/* ⭐ AND THE GATE IS DRIVEN BY REAL BUILT ROWS, not only by hand-made
	      arrays, so a change to the row SHAPE would be caught here too. */
	if ( function_exists( 'bhp_author_visits_build_rows' ) ) {
		$rec = array(
			'sample' => array(
				'slug'   => 'sample',
				'school' => 'Sample Elementary',
				'date'   => '2026-04-15',
				'cutoff' => '2026-04-12',
				'time'   => '',
			),
		);
		c358_assert(
			true === bhp_author_visits_has_open_row( bhp_author_visits_build_rows( $rec, '2026-04-10' ) ),
			'4.11 ⭐ built rows, ordering OPEN (visit - 5): the steps render'
		);
		c358_assert(
			false === bhp_author_visits_has_open_row( bhp_author_visits_build_rows( $rec, '2026-04-14' ) ),
			'4.12 ⭐⭐ built rows, the CLOSED day (visit - 1): NO steps'
		);
		c358_assert(
			false === bhp_author_visits_has_open_row( bhp_author_visits_build_rows( $rec, '2026-04-15' ) ),
			'4.13 ⭐⭐ built rows, the VISIT DAY itself (after phase, still in the upcoming column): NO steps. This is the exact one-screen collision LD-23 named'
		);
		c358_assert(
			false === bhp_author_visits_has_open_row( bhp_author_visits_build_rows( $rec, '2026-06-15' ) ),
			'4.14 ⭐ built rows, two months later: NO steps, and no row is upcoming any more either'
		);

		/* ⛔ GANDALF NOTE 3, CONFIRMED RATHER THAN ASSUMED: the visited school
		      leaves the UPCOMING list the day AFTER the visit. It is the
		      pre-existing `date < today` comparison and this release did not
		      touch it. */
		c358_assert(
			1 === count( bhp_author_visits_build_rows( $rec, '2026-04-15' ) ),
			'4.15 ⭐ note 3: on the VISIT DAY the school is still in the upcoming list (acceptable for that day only)'
		);
		c358_assert(
			array() === bhp_author_visits_build_rows( $rec, '2026-04-16' ),
			'4.16 ⭐⭐ note 3: the day AFTER the visit it has LEFT the upcoming list - already true before 1.19.358, unchanged by it'
		);
	} else {
		c358_skip( '4.11-4.16 the gate over real built rows', 'bhp_author_visits_build_rows() is not loaded' );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · CHANGE 2 IS A DISPLAY GATE. THE COPY IS UNTOUCHED.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( '' === $c358_tmpl ) {
	c358_skip( '5.x the template shape', 'page-author-visits.php could not be read' );
} else {
	$code = c358_code_only( $c358_tmpl );

	c358_assert(
		false !== strpos( $code, 'bhp_author_visits_has_open_row( $bhp_visit_rows )' ),
		'5.1 ⭐ the template gates the block on the new predicate'
	);
	c358_assert(
		false === strpos( $code, 'if ( ! empty( $bhp_visit_rows ) ) :' ),
		'5.2 ⛔ the superseded condition is GONE FROM THE CODE (it survives only inside the preserved comment, which c358_code_only strips)'
	);
	c358_assert(
		false !== strpos( $c358_tmpl, 'if ( ! empty( $bhp_visit_rows ) ) :' ),
		'5.3 ⭐ ...and it IS still preserved verbatim in a comment, so the movement is visible rather than re-derived'
	);

	/*
	 * ⛔⛔ THE THREE STEPS ARE BYTE-IDENTICAL. Copy is Andrew's gate and this
	 *     release did not touch a word of it. Asserted as exact literals, so a
	 *     single changed character fails.
	 */
	foreach ( array(
		'How It Works',
		'Find your child’s school above and open the shop from that button.',
		'At checkout, choose the free author hand-delivery option and tell me your child’s first name.',
		'I sign the books and hand them to your child at the school on the day of the visit.',
	) as $needle ) {
		c358_assert( false !== strpos( $c358_tmpl, $needle ), '5.4 ⛔ copy UNTOUCHED: ' . substr( $needle, 0, 46 ) );
	}

	/* ⭐ AND THE GATE IS NARROWER THAN THE OLD ONE, NEVER WIDER: it reads
	      `open`, which is a strict subset of "a row exists". Asserted as a
	      property of the predicate rather than as prose. */
	if ( function_exists( 'bhp_author_visits_has_open_row' ) ) {
		$rows_any  = array( array( 'open' => false ), array( 'open' => false ) );
		$rows_open = array( array( 'open' => false ), array( 'open' => true ) );
		c358_assert(
			! bhp_author_visits_has_open_row( $rows_any ) && bhp_author_visits_has_open_row( $rows_open ),
			'5.5 ⭐ the new gate is strictly NARROWER than "a row exists" - it never renders where the old one did not'
		);
	}

	/* ⛔ NO EM DASH IN ANY TRANSLATABLE STRING ON THIS TEMPLATE (rule 608a).
	      ⚠️ SCOPED TO STRINGS, NOT TO THE WHOLE FILE, AND DELIBERATELY: the
	      file's pre-existing comments contain em dashes, they are not customer
	      copy, and this release neither added nor removed one. Widening this
	      check to the whole file would fail on prose no release authored. */
	$c358_new_strings = array();
	if ( preg_match_all( '/(?:esc_html_e|esc_html__|__)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $c358_tmpl, $m ) ) {
		$c358_new_strings = $m[1];
	}
	$c358_emdash = array();
	foreach ( $c358_new_strings as $s ) {
		if ( false !== strpos( $s, "\xE2\x80\x94" ) ) {
			$c358_emdash[] = $s;
		}
	}
	c358_assert( array() === $c358_emdash, '5.6 ⛔ NO em dash in any translatable string on this template' );

	/* ⛔ NO COMPANY "we" IN THE GATED COPY (Standing Rules 9.1). The steps are
	      in the founder I-voice and this release must not have changed that. */
	$c358_steps = '';
	if ( preg_match( '/author-visits-how__steps(.*?)<\/ol>/s', $c358_tmpl, $sm ) ) {
		$c358_steps = strtolower( $sm[1] );
	}
	c358_assert( '' !== $c358_steps, '5.7 the three steps were isolated for their own checks' );
	c358_assert( ! preg_match( '/\b(we|us|our)\b/', $c358_steps ), '5.8 ⛔ the steps are in the founder I-voice - no company "we"' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THE GUARDRAILS THIS RELEASE MUST NOT HAVE CROSSED
 *
 * ⛔ TWO CHANGES, NOTHING ELSE. These assertions are what stop this release
 *    having quietly done a third thing.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( '' === $c358_pickup ) {
	c358_skip( '6.x the entitlement chain is untouched', 'school-visit-pickup.php could not be read' );
} else {
	foreach ( array(
		'bhp_school_visit_resolve('      => '6.1 resolve()',
		'bhp_school_visit_is_open_on('   => '6.2 is_open_on()',
		'bhp_school_visit_active('       => '6.3 active()',
	) as $sig => $label ) {
		$body = c358_function_body( $c358_pickup, 'function ' . $sig );
		c358_assert( '' !== $body, $label . ' was located' );
		if ( '' !== $body ) {
			$code = c358_code_only( $body );
			c358_assert(
				false === stripos( $code, 'after' ),
				$label . ' ⛔⛔ carries NO after-visit symbol - removing the expiry did not widen the entitlement gate'
			);
		}
	}

	/* ⛔ THE ORDERING CUTOFF DID NOT MOVE. The hand-delivery close is a
	      different rule from the after phase and this release touched neither
	      its dates nor its copy. */
	if ( function_exists( 'bhp_school_visit_is_open_on' ) ) {
		c358_assert( true === bhp_school_visit_is_open_on( '2026-04-15', '2026-04-13' ), '6.4 ⛔ the ordering window still closes at 00:00 on visit - 1: visit - 2 is OPEN' );
		c358_assert( false === bhp_school_visit_is_open_on( '2026-04-15', '2026-04-14' ), '6.5 ⛔ ...and visit - 1 is CLOSED, exactly as at 1.8.82' );
	}

	/* ⛔ NO REAL SCHOOL SLUG, NAME OR DATE IN ANYTHING THIS RELEASE AUTHORED.
	      The registry is DATA and this repository is PUBLIC. */
	c358_assert(
		1 !== preg_match( '/\b(19|20)\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\b/', c358_code_only( $c358_tmpl ) ),
		'6.6 ⛔ no literal calendar date in the executable source of page-author-visits.php'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * SUMMARY
 * ═══════════════════════════════════════════════════════════════════════════ */

printf(
	"\n=== CYCLE179-LD-358: %d passed, %d FAILED, %d skipped ===\n",
	(int) $GLOBALS['c358_passes'],
	(int) $GLOBALS['c358_failures'],
	(int) $GLOBALS['c358_skips']
);

if ( $GLOBALS['c358_failures'] > 0 ) {
	exit( 1 );
}
