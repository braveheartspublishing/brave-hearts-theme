<?php
/**
 * THE AFTER-VISIT PHASE. Theme 1.19.357, bundle plugin 1.8.82.
 * `CYCLE179-LD-357`. Founder direction seal 868.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-after-visit-357.php \
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
 * ⛔ IT CANNOT PROVE THAT THE COUNTERS ARE ABSENT FROM THE SERVED PAGE. PHP has
 *    no request here, no WooCommerce session and no rendered catalog. What this
 *    file proves is the STRUCTURAL claim: that the after-visit flag lives
 *    outside the entitlement chain and that nothing in that chain reads it.
 *    ⭐ THE ABSENCE ITSELF IS PROVED IN A BROWSER, against the served HTML of
 *    the flagged URL at an asserted `window.innerWidth`, and that is the
 *    PRIMARY evidence for this release. It is filed under
 *    `pdp-redesign\357-STAGING\`.
 *
 * ⛔ IT CANNOT PROVE THE ORDER META EITHER. Writing it would mean creating a
 *    real order, which is a WooCommerce data change this desk does not make.
 *    What is asserted here is that the order-marking function CANNOT write the
 *    hand-delivery flag and CANNOT be reached on a pickup order, which is the
 *    half where a mistake would strand a paying customer's books.
 *
 * ⛔ IT ASSERTS NOTHING ABOUT ANY REAL SCHOOL. Every date case below is
 *    synthetic. The registry is DATA, it is read-only to this lane, and a real
 *    slug written into a source file in a PUBLIC repository is the defect
 *    `tests/test-author-visits-page.php` already exists to catch.
 *
 * WHAT IT ASSERTS
 *   §1  the versions moved, in every file that carries one
 *   §2  the after-window predicate, on both boundaries and both failure modes
 *   §3  the three states partition the calendar and cannot overlap
 *   §4  the band's decision matrix, INCLUDING that F-10 still holds
 *   §5  the separation: the entitlement chain cannot see the after-visit flag
 *   §6  `/author-visits/` rows across the visit-day and window-end boundaries
 *   §7  the copy: one place, no em dash, no company "we", "today" only today
 *   §8  the shipped CSS artefact is fresh and carries the new band
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
$GLOBALS['c357_failures'] = 0;
$GLOBALS['c357_passes']   = 0;
$GLOBALS['c357_skips']    = 0;

function c357_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['c357_passes']++;
		echo "PASS  {$label}\n";
		return;
	}
	$GLOBALS['c357_failures']++;
	echo "FAIL  {$label}\n";
}

function c357_skip( $label, $why ) {
	$GLOBALS['c357_skips']++;
	echo "SKIP  {$label} -- {$why}\n";
}

/*
 * ⛔ EVERY SOURCE READ IS NORMALISED TO \n BEFORE ANYTHING IS SEARCHED IN IT.
 *    `CYCLE179-LD-355` recorded a self-inflicted SKIP caused by exactly this:
 *    the plugin's `school-visit-pickup.php` is a CRLF file, so a needle written
 *    with "\n" matched nothing. A skip is not a pass, and normalising first
 *    removes the whole class of failure.
 */
function c357_read( $relative_to_theme ) {
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
function c357_read_plugin( $relative_to_plugin ) {
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

/**
 * The body of one top-level function, so an assertion can be scoped to it.
 *
 * ⛔⛔ THE FIRST VERSION OF THIS HELPER COUNTED BRACES, AND IT WAS WRONG IN THE
 *     SILENT DIRECTION. It walked the source incrementing on `{` and
 *     decrementing on `}`, which is correct for code and WRONG for this
 *     codebase, because the docblocks here quote SUPERSEDED CODE. The body of
 *     `bhp_school_visit_resolve()` contains this line inside a comment:
 *
 *         *        if ( bhp_school_visit_today() > $record['cutoff'] ) {
 *
 *     One unbalanced `{`. The counter never returned to zero, the helper
 *     returned '', and the three assertions gated on it — 5.1, 5.2 and 5.3,
 *     which are the ones that prove the ENTITLEMENT GATE was not widened —
 *     DID NOT RUN AT ALL. Only the "was located" guard failed, so the run said
 *     one failure where three checks had quietly evaporated.
 *
 * ⭐ THAT GUARD IS WHY THE DEFECT WAS VISIBLE, and it is the reason every
 *    extraction in this file is asserted to be non-empty BEFORE anything is
 *    asserted about its contents. A check that cannot run is not a check, and
 *    `CYCLE179-LD-356` recorded the same failure class from the other
 *    direction: three "this literal is absent" assertions passing while reading
 *    an empty string.
 *
 * ⭐ THE FIX READS THE CLOSING BRACE OFF COLUMN ZERO. Every top-level function
 *    in this codebase closes with `}` at the start of a line, and no comment
 *    line here begins with one, because comment lines begin with ` *`. It is a
 *    narrower assumption than balanced braces and, unlike balanced braces, it
 *    is true of this source.
 */
function c357_function_body( $source, $signature ) {
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

$c357_css     = c357_read( 'style.css' );
$c357_min     = c357_read( 'style.min.css' );
$c357_band    = c357_read( 'inc/visit-band.php' );
$c357_visits  = c357_read( 'inc/author-visits.php' );
$c357_tmpl    = c357_read( 'page-author-visits.php' );
$c357_pickup  = c357_read_plugin( 'includes/school-visit-pickup.php' );
$c357_boot    = c357_read_plugin( 'brave-hearts-bundle-pricing.php' );

echo "\n=== CYCLE179-LD-357 - the after-visit phase ===\n\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE VERSIONS
 * ═══════════════════════════════════════════════════════════════════════════ */

/*
 * ⚠️⚠️ 1.19.358 / 1.8.83 (`CYCLE179-LD-358`) — §1's FIVE EQUALITY PINS BECAME A
 *     DRIFT CHECK PLUS `version_compare` '>=', AND THE SUPERSEDED LINES ARE
 *     PRESERVED VERBATIM RATHER THAN DELETED:
 *
 *        c357_assert( false !== strpos( $c357_css, 'Version: 1.19.357' ), '1.2 style.css declares 1.19.357' );
 *        c357_assert( '1.19.357' === wp_get_theme()->get( 'Version' ), '1.3 the ACTIVE theme reports 1.19.357' );
 *        c357_assert( false !== strpos( $c357_boot, 'Version: 1.8.82' ), '1.4 the plugin header declares 1.8.82' );
 *        c357_assert( false !== strpos( $c357_boot, "BHP_BUNDLE_PRICING_VERSION', '1.8.82'" ), '1.5 the plugin CONSTANT declares 1.8.82' );
 *        c357_assert( '1.8.82' === BHP_BUNDLE_PRICING_VERSION, '1.6 the LOADED plugin constant is 1.8.82' );
 *
 * ⭐ THIS IS THE SAME CORRECTION `CYCLE179-LD-357` ITSELF APPLIED TO
 *    `test-cycle179-catalog-356.php` §1, made to its own suite one release
 *    later, and it is a CORRECTION rather than a weakening for the reason
 *    recorded there: what §1 is FOR is that the version moved at all and that
 *    the plugin's two copies of its own version have not DRIFTED APART. Both
 *    claims survive intact. An equality against one frozen release additionally
 *    asserted "no later release exists", which was never the intended claim and
 *    which EVERY future release must falsify.
 *
 * ⛔ NO OTHER ASSERTION IN §1 WAS TOUCHED.
 */
c357_assert( '' !== $c357_css, '1.1 style.css is readable' );

$c357_live_theme = (string) wp_get_theme()->get( 'Version' );
c357_assert(
	false !== strpos( $c357_css, 'Version: ' . $c357_live_theme ),
	'1.2 style.css declares the running theme version (reads: ' . $c357_live_theme . ')'
);
c357_assert(
	version_compare( $c357_live_theme, '1.19.357', '>=' ),
	'1.3 the ACTIVE theme reports 1.19.357 or later (reads: ' . $c357_live_theme . ')'
);

$c357_live_plugin = defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? (string) BHP_BUNDLE_PRICING_VERSION : '';

if ( '' === $c357_boot || '' === $c357_live_plugin ) {
	c357_skip( '1.4 the plugin header matches the LOADED constant', 'the plugin bootstrap or constant could not be read' );
	c357_skip( '1.5 the plugin CONSTANT matches the LOADED constant', 'the plugin bootstrap or constant could not be read' );
} else {
	c357_assert( false !== strpos( $c357_boot, 'Version: ' . $c357_live_plugin ), '1.4 the plugin header matches the LOADED constant (reads: ' . $c357_live_plugin . ')' );
	c357_assert( false !== strpos( $c357_boot, "BHP_BUNDLE_PRICING_VERSION', '" . $c357_live_plugin . "'" ), '1.5 the plugin CONSTANT in the file matches the LOADED constant' );
}
if ( '' !== $c357_live_plugin ) {
	c357_assert( version_compare( $c357_live_plugin, '1.8.82', '>=' ), '1.6 the LOADED plugin constant is 1.8.82 or later (reads: ' . $c357_live_plugin . ')' );
} else {
	c357_skip( '1.6 the LOADED plugin constant is 1.8.82 or later', 'BHP_BUNDLE_PRICING_VERSION is not defined in this context' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE AFTER-WINDOW PREDICATE, ON BOTH BOUNDARIES
 *
 * ⭐ EVERY DATE HERE IS SYNTHETIC AND EVERY CALL IS PURE. No registry row is
 *    read, no clock is consulted, nothing is written. That is the whole reason
 *    `bhp_school_visit_is_after_on()` takes both dates as parameters.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_school_visit_is_after_on' ) ) {
	c357_skip( '2.x the after-window predicate', 'bundle plugin 1.8.82 is not loaded' );
} else {
	/*
	 * ⚠️⚠️⚠️ 1.8.83 (`CYCLE179-LD-358`) — §2 IS REWRITTEN FROM THE BOUNDED
	 *     WINDOW TO AN UNBOUNDED ONE, AND THE SUPERSEDED ASSERTIONS ARE
	 *     PRESERVED VERBATIM HERE RATHER THAN DELETED:
	 *
	 *        $n = ... bhp_school_visit_after_days() ...;
	 *        c357_assert( 30 === $n, '2.0 the window is 30 days by default' );
	 *        $last = gmdate( 'Y-m-d', strtotime( $v . ' 00:00:00 UTC' ) + ( $n * 86400 ) );
	 *        $past = gmdate( 'Y-m-d', strtotime( $v . ' 00:00:00 UTC' ) + ( ( $n + 1 ) * 86400 ) );
	 *        c357_assert( true === bhp_school_visit_is_after_on( $v, $last ), '2.5 visit + N is the LAST day inside the window' );
	 *        c357_assert( false === bhp_school_visit_is_after_on( $v, $past ), '2.6 visit + N+1 is OUTSIDE it' );
	 *        c357_assert( 30 === bhp_school_visit_after_days(), '2.11 a filter returning nonsense is DISCARDED, not trusted' );
	 *        c357_assert( 30 === bhp_school_visit_after_days(), '2.12 zero is refused too' );
	 *
	 * ⛔ EVERY ONE OF THOSE WAS RIGHT ABOUT 1.8.82 AND IS WRONG ABOUT 1.8.83.
	 *    Andrew ruled the phase stays open INDEFINITELY (seal 870, RELAYED, NOT
	 *    witnessed first-hand by this agent). 2.6 in particular asserted the
	 *    exact behaviour he removed, so leaving it would have made a correct
	 *    build look like a regression. ⭐ 2.12 INVERTED rather than
	 *    disappeared: zero was refused because forever was the danger; zero now
	 *    MEANS forever, which is the ruling.
	 *
	 * ⭐⭐ NOT ONE ASSERTION THAT STILL HOLDS WAS LOOSENED. 2.1 through 2.4,
	 *    2.7, 2.8, 2.9 and 2.10 are byte-identical, and they are the ones that
	 *    prove the OPENING bound and both fail-closed branches did not move.
	 */
	$v = '2026-04-15';                                    // a synthetic visit date
	$n = function_exists( 'bhp_school_visit_after_days' ) ? bhp_school_visit_after_days() : null;

	c357_assert( null === $n, '2.0 ⭐⭐ the window is UNLIMITED by default - bhp_school_visit_after_days() returns null (seal 870)' );

	c357_assert( false === bhp_school_visit_is_after_on( $v, '2026-04-13' ), '2.1 visit - 2 (ordering open) is NOT after' );
	c357_assert( false === bhp_school_visit_is_after_on( $v, '2026-04-14' ), '2.2 ⛔ visit - 1 (the closed day) is NOT after' );
	c357_assert( true === bhp_school_visit_is_after_on( $v, '2026-04-15' ), '2.3 ⭐ the VISIT DATE ITSELF is after, from 00:00 site time' );
	c357_assert( true === bhp_school_visit_is_after_on( $v, '2026-04-16' ), '2.4 the day after the visit is after' );

	/*
	 * ⭐⭐ THE BOUNDARY CASES THE BRIEF NAMES BY NUMBER. visit + 30 was the last
	 *    day of the old window and visit + 31 was the first day outside it, so
	 *    31 is the one date that proves the bound is gone rather than merely
	 *    moved. 365 proves it is not a bigger bound either.
	 */
	$c357_day = static function ( $offset ) use ( $v ) {
		return gmdate( 'Y-m-d', strtotime( $v . ' 00:00:00 UTC' ) + ( $offset * 86400 ) );
	};
	c357_assert( true === bhp_school_visit_is_after_on( $v, $c357_day( 30 ) ), '2.5 ⭐ visit + 30 (the OLD last day) is still after (' . $c357_day( 30 ) . ')' );
	c357_assert( true === bhp_school_visit_is_after_on( $v, $c357_day( 31 ) ), '2.6 ⭐⭐ visit + 31 IS AFTER - the 30-day bound is GONE, not moved (' . $c357_day( 31 ) . ')' );
	c357_assert( true === bhp_school_visit_is_after_on( $v, $c357_day( 365 ) ), '2.6b ⭐⭐ visit + 365 IS AFTER - there is no larger bound either (' . $c357_day( 365 ) . ')' );
	c357_assert( true === bhp_school_visit_is_after_on( $v, $c357_day( 3650 ) ), '2.6c ⭐ visit + 3650 is after too - "indefinitely" is meant literally' );

	// ⛔ Both failure modes fail CLOSED. An unusable date and an absent clock
	//    must never be read as "the read-aloud has happened".
	c357_assert( false === bhp_school_visit_is_after_on( 'not-a-date', '2026-04-16' ), '2.7 ⛔ an unusable visit date fails CLOSED' );
	c357_assert( false === bhp_school_visit_is_after_on( $v, '' ), '2.8 ⛔ an empty today fails CLOSED (it does NOT mirror is_open_on)' );

	/*
	 * ⛔ THE FILTER MUST STILL BE ABLE TO IMPOSE A BOUND AND MUST NOT BE ABLE
	 *    TO BREAK THE RULING. 1.8.82 protected against a hook leaving a band up
	 *    forever; 1.8.83 protects the opposite direction, because forever is now
	 *    the instruction and a broken hook must not silently CLOSE the window.
	 */
	$c357_five = static function () {
		return 5;
	};
	add_filter( 'bhp_school_visit_after_days', $c357_five );
	c357_assert( 5 === bhp_school_visit_after_days(), '2.9 the filter can still impose a bounded window' );
	c357_assert( false === bhp_school_visit_is_after_on( $v, '2026-04-21' ), '2.10 ...and the imposed window actually closes' );
	remove_filter( 'bhp_school_visit_after_days', $c357_five );

	c357_assert( null === bhp_school_visit_after_days(), '2.10b ⛔ removing the filter restores UNLIMITED - the bound did not stick' );

	$c357_junk = static function () {
		return 'thirty';
	};
	add_filter( 'bhp_school_visit_after_days', $c357_junk );
	c357_assert( null === bhp_school_visit_after_days(), '2.11 ⛔ a filter returning nonsense is DISCARDED back to the default, which is now UNLIMITED' );
	c357_assert( true === bhp_school_visit_is_after_on( $v, $c357_day( 400 ) ), '2.11b ⛔⛔ ...so a broken hook can NOT close a window the founder ordered left open' );
	remove_filter( 'bhp_school_visit_after_days', $c357_junk );

	$c357_zero = static function () {
		return 0;
	};
	add_filter( 'bhp_school_visit_after_days', $c357_zero );
	c357_assert( null === bhp_school_visit_after_days(), '2.12 ⭐ ZERO NOW MEANS UNLIMITED - the semantics inverted with the ruling' );
	remove_filter( 'bhp_school_visit_after_days', $c357_zero );

	$c357_null = static function () {
		return null;
	};
	add_filter( 'bhp_school_visit_after_days', $c357_null );
	c357_assert( null === bhp_school_visit_after_days(), '2.13 ⭐ null means unlimited too - both spellings are accepted' );
	remove_filter( 'bhp_school_visit_after_days', $c357_null );

	$c357_neg = static function () {
		return -7;
	};
	add_filter( 'bhp_school_visit_after_days', $c357_neg );
	c357_assert( null === bhp_school_visit_after_days(), '2.14 ⛔ a negative resolves to unlimited rather than to a third behaviour' );
	remove_filter( 'bhp_school_visit_after_days', $c357_neg );

	/*
	 * ⛔ THE `null` / `''` SPLIT ON THE END DATE IS THE ONE THING A CARELESS
	 *    EDIT WOULD COLLAPSE, AND COLLAPSING IT WOULD BE SILENT. `null` means
	 *    "no end exists"; `''` means "the date is unusable, fail closed". They
	 *    are opposite answers and `empty()` cannot tell them apart.
	 */
	if ( function_exists( 'bhp_school_visit_after_end_date' ) ) {
		c357_assert( null === bhp_school_visit_after_end_date( $v ), '2.15 ⭐ an unlimited window has NO end date - null, not ""' );
		c357_assert( '' === bhp_school_visit_after_end_date( 'not-a-date' ) || null === bhp_school_visit_after_end_date( 'not-a-date' ), '2.16 an unusable date yields no usable end date' );
		add_filter( 'bhp_school_visit_after_days', $c357_five );
		c357_assert( '2026-04-20' === bhp_school_visit_after_end_date( $v ), '2.17 ...and a BOUNDED window still yields the real last day' );
		c357_assert( '' === bhp_school_visit_after_end_date( 'not-a-date' ), '2.18 ⛔ under a bounded window an unusable date is "" - the fail-closed branch is still reachable and still distinct from null' );
		remove_filter( 'bhp_school_visit_after_days', $c357_five );
	} else {
		c357_skip( '2.15-2.18 the end-date null/empty split', 'bhp_school_visit_after_end_date() is not loaded' );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · THE THREE STATES PARTITION THE CALENDAR
 *
 * ⛔⛔ THIS IS THE SECTION THE RELEASE EXISTS FOR. If `open` and `after` could
 *     ever be true on the same day, a parent whose visit is still taking
 *     hand-delivery orders could be told the read-aloud already happened.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_school_visit_is_after_on' ) || ! function_exists( 'bhp_school_visit_is_open_on' ) ) {
	c357_skip( '3.x the state partition', 'bundle plugin 1.8.82 is not loaded' );
} else {
	$v        = '2026-04-15';
	$base     = strtotime( $v . ' 00:00:00 UTC' );
	$both     = array();
	$neither  = array();

	for ( $d = -6; $d <= 34; $d++ ) {
		$day   = gmdate( 'Y-m-d', $base + ( $d * 86400 ) );
		$open  = (bool) bhp_school_visit_is_open_on( $v, $day );
		$after = (bool) bhp_school_visit_is_after_on( $v, $day );
		if ( $open && $after ) {
			$both[] = $day;
		}
		if ( ! $open && ! $after ) {
			$neither[] = $day;
		}
	}

	c357_assert( array() === $both, '3.1 ⛔⛔ NO day is both open and after - the entitlement and the thank-you can never both be on one page' );

	/*
	 * ⭐ THE DAYS IN NEITHER STATE ARE EXACTLY TWO KINDS, AND BOTH ARE
	 *    DELIBERATE: `visit - 1`, which is the CLOSED band, and everything past
	 *    the window's end, which is the plain storefront.
	 *
	 * ⚠️⚠️ 1.8.83 (`CYCLE179-LD-358`) — THE PARAGRAPH ABOVE IS PRESERVED
	 *     VERBATIM AND THE SECOND KIND NO LONGER EXISTS. There is no window end,
	 *     so `visit - 1` is now the ONLY day in neither state, for ever. The
	 *     SUPERSEDED assertions, preserved rather than deleted:
	 *
	 *        $n = bhp_school_visit_after_days();
	 *        $first_tail = gmdate( 'Y-m-d', $base + ( ( $n + 1 ) * 86400 ) );
	 *        c357_assert( ! empty( $tail ) && $tail[0] === $first_tail, '3.3 the only OTHER day in neither state is the first day past the window' );
	 *        c357_assert( count( $neither ) === 1 + count( $tail ), '3.4 there is exactly one gap day inside the series' );
	 *
	 * ⛔ 3.1 AND 3.2 ARE BYTE-IDENTICAL AND THEY ARE THE TWO THAT MATTER: the
	 *    states still cannot overlap, and `visit - 1` is still the closed band.
	 *    Only the claim about the TAIL of the series changed, because the series
	 *    no longer has one.
	 */
	$expect_gap = gmdate( 'Y-m-d', $base - 86400 );
	c357_assert( in_array( $expect_gap, $neither, true ), '3.2 ⭐ visit - 1 is in NEITHER state - it is the closed band, unchanged from 1.19.351' );

	c357_assert(
		array( $expect_gap ) === $neither,
		'3.3 ⭐⭐ visit - 1 is now the ONLY day in neither state across the whole series - there is no tail past a window end any more'
	);
	c357_assert( 1 === count( $neither ), '3.4 there is exactly one gap day, and exactly one' );

	/*
	 * ⭐ AND THE SERIES IS SWEPT FAR PAST WHERE THE OLD WINDOW ENDED, because a
	 *    41-day sweep alone could not tell "unlimited" from "a bound of 40".
	 */
	$far_gap = array();
	for ( $d = 0; $d <= 400; $d++ ) {
		$day = gmdate( 'Y-m-d', $base + ( $d * 86400 ) );
		if ( ! bhp_school_visit_is_after_on( $v, $day ) ) {
			$far_gap[] = $day;
		}
	}
	c357_assert( array() === $far_gap, '3.5 ⭐⭐ every one of the 401 days from the visit onward is AFTER - no day falls out of the phase' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE BAND'S DECISION MATRIX, AND F-10 STILL HOLDS
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_visit_band_decide' ) ) {
	c357_skip( '4.x the band decision matrix', 'inc/visit-band.php is not loaded' );
} else {
	$A = array( 'slug' => 'alpha-2026-04-15', 'school' => 'Alpha School', 'date' => '2026-04-15' );
	$B = array( 'slug' => 'beta-2026-05-20', 'school' => 'Beta School', 'date' => '2026-05-20' );

	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, $A, null, null, null );
	c357_assert( 'open' === $r['state'] && $r['record'] === $A, '4.1 a URL naming a LIVE visit is open' );

	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, null, $B, null, null );
	c357_assert( 'closed' === $r['state'] && $r['record'] === $A, '4.2 ⭐ F-10 PRESERVED: a URL naming a CLOSED visit beats an open session, and names the URL school' );

	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, $A, $B, null, null );
	c357_assert( 'open' === $r['state'] && $r['record'] === $A, '4.3 ⭐ F-10 PRESERVED: the URL outranks the session on the open-to-open hop too' );

	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, null, null, $A, null );
	c357_assert( 'after' === $r['state'] && $r['record'] === $A, '4.4 ⭐ a URL inside the AFTER window is the third state' );

	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, null, $B, $A, null );
	c357_assert( 'after' === $r['state'] && $r['record'] === $A, '4.5 ⭐ an after URL beats an open session, exactly as a closed URL does' );

	/*
	 * ⛔⛔ THE ORDERING ASSERTION. Even handed BOTH a live record and an after
	 *    record for one slug - which the plugin's two predicates make
	 *    impossible - the ENTITLEMENT must win. This is the guard that survives
	 *    somebody later widening one of those windows.
	 */
	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, $A, null, $A, null );
	c357_assert( 'open' === $r['state'], '4.6 ⛔⛔ open OUTRANKS after even when both records are supplied' );

	$r = bhp_visit_band_decide( '', null, null, $B, null, null );
	c357_assert( 'open' === $r['state'] && $r['record'] === $B, '4.7 no URL: the entitlement session still decides' );

	$r = bhp_visit_band_decide( '', null, null, null, null, $A );
	c357_assert( 'after' === $r['state'] && $r['record'] === $A, '4.8 ⭐ no URL: the after-visit session keeps the band on a category archive' );

	$r = bhp_visit_band_decide( '', null, null, $B, null, $A );
	c357_assert( 'open' === $r['state'] && $r['record'] === $B, '4.9 ⛔ if a session ever held both, the one that GRANTS something wins' );

	$r = bhp_visit_band_decide( '', null, null, null, null, null );
	c357_assert( 'none' === $r['state'] && null === $r['record'], '4.10 nothing named, nothing rendered' );

	$r = bhp_visit_band_decide( 'not-a-registered-slug', null, null, $B, null, null );
	c357_assert( 'open' === $r['state'] && $r['record'] === $B, '4.11 ⛔ an UNKNOWN slug is still a no-op and does not blank a flagged parent' );

	/*
	 * ⛔ THE FOUR-ARGUMENT CALL MUST STILL BEHAVE LIKE 1.19.353. The two new
	 *    parameters default to null precisely so that every existing call site
	 *    and every existing assertion keeps its answer byte for byte.
	 */
	$r = bhp_visit_band_decide( 'alpha-2026-04-15', $A, null, $B );
	c357_assert( 'closed' === $r['state'], '4.12 ⭐ the 4-argument call is unchanged from 1.19.353' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · THE SEPARATION - THE ENTITLEMENT CHAIN CANNOT SEE THE AFTER FLAG
 *
 * ⛔⛔ THESE ARE THE HIGHEST-VALUE ASSERTIONS IN THE FILE. Everything the
 *     founder asked to be ABSENT in this phase - the counters, the "Only N
 *     left", the hand delivery, the paperback-only gate - is absent because
 *     `bhp_school_visit_resolve()` refuses, and it refuses because nothing here
 *     widened it. These assertions are what stop a later pass widening it.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( '' === $c357_pickup ) {
	c357_skip( '5.x the entitlement separation', 'the plugin source could not be read' );
} else {
	$resolve = c357_function_body( $c357_pickup, 'function bhp_school_visit_resolve( $slug )' );
	$active  = c357_function_body( $c357_pickup, 'function bhp_school_visit_active()' );
	$reqrec  = c357_function_body( $c357_pickup, 'function bhp_school_visit_request_record()' );
	$markaft = c357_function_body( $c357_pickup, 'function bhp_school_visit_mark_after_order( $order )' );

	c357_assert( '' !== $resolve, '5.0a bhp_school_visit_resolve() was located' );
	c357_assert( '' !== $active, '5.0b bhp_school_visit_active() was located' );
	c357_assert( '' !== $reqrec, '5.0c bhp_school_visit_request_record() was located' );
	c357_assert( '' !== $markaft, '5.0d bhp_school_visit_mark_after_order() was located' );

	if ( '' !== $resolve ) {
		c357_assert( false === strpos( $resolve, 'AFTER' ), '5.1 ⛔⛔ resolve() does not read ANY after-visit symbol' );
		c357_assert( false !== strpos( $resolve, 'bhp_school_visit_is_open_on' ), '5.2 ⛔ resolve() still gates on the ONLINE CLOSE, unchanged' );
		c357_assert( false === strpos( $resolve, 'is_after_on' ), '5.3 ⛔⛔ resolve() does not consult the after-window' );
	}
	if ( '' !== $active ) {
		c357_assert( false === strpos( $active, 'AFTER' ), '5.4 ⛔⛔ active() does not read the after-visit session key' );
		c357_assert( false !== strpos( $active, 'bhp_school_visit_resolve( $slug )' ), '5.5 active() still re-validates through resolve() on every request' );
	}
	if ( '' !== $reqrec ) {
		c357_assert( false === strpos( $reqrec, 'after' ), '5.6 ⛔⛔ request_record() - the one predicate every gate funnels through - cannot see the after flag' );
	}

	/*
	 * ⛔⛔⛔ THE SINGLE MOST DANGEROUS MISTAKE THIS RELEASE COULD HAVE MADE.
	 *     `BHP_SCHOOL_PICKUP_META_FLAG` is what the Bookvault webhook block
	 *     keys on. An after-visit order carrying it would be silently withheld
	 *     from the printer and the customer-visible symptom would be a parent
	 *     who paid and never received a book.
	 */
	if ( '' !== $markaft ) {
		c357_assert(
			false === strpos( $markaft, 'update_meta_data( BHP_SCHOOL_PICKUP_META_FLAG' ),
			'5.7 ⛔⛔⛔ the after-visit order marker NEVER writes the hand-delivery flag'
		);
		c357_assert(
			false !== strpos( $markaft, 'bhp_school_pickup_order_is_pickup( $order )' ),
			'5.8 ⛔ ...and it refuses to run on a pickup order at all'
		);
		c357_assert(
			false !== strpos( $markaft, 'BHP_SCHOOL_VISIT_PHASE_AFTER' ),
			'5.9 ⭐ it writes the phase marker the brief asked for'
		);
		c357_assert(
			false !== strpos( $markaft, 'BHP_SCHOOL_PICKUP_META_SLUG' ),
			'5.10 ⭐ ...and the bhp_visit slug, so the school keeps its attribution'
		);
	}

	c357_assert(
		defined( 'BHP_SCHOOL_VISIT_META_PHASE' ) && '_bhp_school_visit_phase' === BHP_SCHOOL_VISIT_META_PHASE,
		'5.11 the phase meta key is the one the brief named'
	);
	c357_assert(
		defined( 'BHP_SCHOOL_VISIT_PHASE_AFTER' ) && 'after' === BHP_SCHOOL_VISIT_PHASE_AFTER,
		'5.12 ...and its post-visit value is "after"'
	);
	c357_assert(
		defined( 'BHP_SCHOOL_VISIT_AFTER_SESSION_KEY' )
			&& defined( 'BHP_SCHOOL_VISIT_SESSION_KEY' )
			&& BHP_SCHOOL_VISIT_AFTER_SESSION_KEY !== BHP_SCHOOL_VISIT_SESSION_KEY,
		'5.13 ⛔ the two session keys are genuinely different keys'
	);

	/*
	 * ⭐ THE CAPTURE DECISION, AS A PURE FUNCTION. The 1.8.80 answers must be
	 *    unchanged and the new one must be unreachable while the visit is open.
	 */
	if ( function_exists( 'bhp_school_visit_capture_decide' ) ) {
		c357_assert( 'set' === bhp_school_visit_capture_decide( 'x', true, true, '', true ), '5.14 ⛔⛔ a slug that RESOLVES is still "set", even when the after flag is also true' );
		c357_assert( 'after' === bhp_school_visit_capture_decide( 'x', true, false, '', true ), '5.15 ⭐ a registered, closed, in-window slug is "after"' );
		c357_assert( 'ignore' === bhp_school_visit_capture_decide( 'x', false, false, '', true ), '5.16 ⛔ an UNREGISTERED slug cannot open the after phase either' );
		c357_assert( 'clear' === bhp_school_visit_capture_decide( 'x', true, false, 'y', false ), '5.17 the 1.8.80 clear branch is unchanged' );
		c357_assert( 'ignore' === bhp_school_visit_capture_decide( 'x', true, false, '', false ), '5.18 the 1.8.80 no-op is unchanged' );
		c357_assert( 'clear' === bhp_school_visit_capture_decide( 'clear', false, false, 'y' ), '5.19 the clear token is unchanged, and the 4-argument call still works' );
	} else {
		c357_skip( '5.14-5.19 the capture decision', 'bhp_school_visit_capture_decide() is not loaded' );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · /author-visits/ ACROSS BOTH BOUNDARIES
 *
 * ⭐ SYNTHETIC RECORDS ONLY. `bhp_author_visits_build_rows()` and
 *    `bhp_author_visits_build_past_rows()` are pure for exactly this reason, so
 *    the visit-day boundary is an assertion rather than something you can only
 *    observe by waiting for a Tuesday.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_author_visits_build_rows' ) || ! function_exists( 'bhp_author_visits_build_past_rows' ) ) {
	c357_skip( '6.x the visits page rows', 'inc/author-visits.php is not loaded' );
} else {
	$rec = array(
		'sample-visit-2026-04-15' => array(
			'slug'   => 'sample-visit-2026-04-15',
			'school' => 'Sample Elementary',
			'date'   => '2026-04-15',
			'cutoff' => '2026-04-12',
			'time'   => '9:00 AM',
		),
	);

	$pick = static function ( $rows ) {
		return isset( $rows[0] ) ? $rows[0] : null;
	};

	// ── ordering still open ──────────────────────────────────────────────
	$row = $pick( bhp_author_visits_build_rows( $rec, '2026-04-12' ) );
	c357_assert( $row && ! empty( $row['open'] ) && empty( $row['after'] ), '6.1 while ordering is open the row is open and NOT after' );
	c357_assert( $row && '' !== $row['url'], '6.2 ...and it carries the hand-delivery link' );

	// ── the one closed day ───────────────────────────────────────────────
	$row = $pick( bhp_author_visits_build_rows( $rec, '2026-04-14' ) );
	c357_assert( $row && empty( $row['open'] ) && empty( $row['after'] ), '6.3 ⭐ visit - 1 is CLOSED and not after - the greyed button is unchanged' );
	c357_assert( $row && '' === $row['url'], '6.4 ⛔ a CLOSED row still carries NO url at all' );

	// ── the visit day itself ─────────────────────────────────────────────
	$rows = bhp_author_visits_build_rows( $rec, '2026-04-15' );
	$row  = $pick( $rows );
	c357_assert( 1 === count( $rows ), '6.5 ⭐ on the visit day the school is STILL in the upcoming list (date >= today)' );
	c357_assert( $row && empty( $row['open'] ) && ! empty( $row['after'] ), '6.6 ⭐⭐ ...and it is in the AFTER state, not the closed one' );
	c357_assert( $row && '' !== $row['url'], '6.7 ⭐⭐ ...and the funnel link is back, per seal 868' );

	$past = bhp_author_visits_build_past_rows( $rec, '2026-04-15' );
	c357_assert( array() === $past, '6.8 ⛔ the partition holds: on the visit day it is NOT also in the past list' );

	// ── the day after ────────────────────────────────────────────────────
	$rows = bhp_author_visits_build_rows( $rec, '2026-04-16' );
	c357_assert( array() === $rows, '6.9 the day after the visit it leaves the upcoming list, unchanged from 1.19.319' );

	$past = bhp_author_visits_build_past_rows( $rec, '2026-04-16' );
	$prow = $pick( $past );
	c357_assert( $prow && ! empty( $prow['after'] ), '6.10 ⭐ ...and arrives in the past list still inside the window' );
	c357_assert( $prow && '' !== $prow['url'], '6.11 ⭐⭐ ...carrying the shipping link, which is where the funnel lives for all but one day of the window' );
	c357_assert( $prow && isset( $prow['date_display'] ) && '' !== $prow['date_display'], '6.12 ⛔ the past read-aloud story card is NOT lost' );

	/*
	 * ── past where the window USED to end ───────────────────────────────
	 *
	 * ⚠️⚠️ 1.19.358 / 1.8.83 (`CYCLE179-LD-358`) — 6.13 AND 6.14 ARE INVERTED,
	 *     AND THE SUPERSEDED ASSERTIONS ARE PRESERVED VERBATIM:
	 *
	 *        $n    = ... bhp_school_visit_after_days() ...;
	 *        $out  = gmdate( 'Y-m-d', strtotime( '2026-04-15 00:00:00 UTC' ) + ( ( $n + 1 ) * 86400 ) );
	 *        c357_assert( $prow && empty( $prow['after'] ), '6.13 past the window the row is a pure trust record again' );
	 *        c357_assert( $prow && '' === $prow['url'], '6.14 ...and the ordering link is GONE, with no manual step needed' );
	 *
	 * ⛔ THEY ASSERTED THE EXPIRY ANDREW REMOVED (seal 870, RELAYED, NOT
	 *    witnessed first-hand by this agent), so they now assert its ABSENCE at
	 *    the same two dates rather than being deleted. ⭐ 6.15 IS UNCHANGED:
	 *    the school and the date survive either way, which was always the point
	 *    of that one.
	 */
	$out  = gmdate( 'Y-m-d', strtotime( '2026-04-15 00:00:00 UTC' ) + ( 31 * 86400 ) );
	$prow = $pick( bhp_author_visits_build_past_rows( $rec, $out ) );
	c357_assert( $prow && ! empty( $prow['after'] ), "6.13 ⭐⭐ at visit + 31 the past row is STILL in the after phase ({$out}) - the expiry is gone" );
	c357_assert( $prow && '' !== $prow['url'], '6.14 ⭐⭐ ...and it STILL carries the shipping link, with no manual step needed to keep it' );
	c357_assert( $prow && isset( $prow['school'] ) && 'Sample Elementary' === $prow['school'], '6.15 ...while the school and the date survive, as they always have' );

	$far  = gmdate( 'Y-m-d', strtotime( '2026-04-15 00:00:00 UTC' ) + ( 365 * 86400 ) );
	$prow = $pick( bhp_author_visits_build_past_rows( $rec, $far ) );
	c357_assert( $prow && ! empty( $prow['after'] ), "6.16 ⭐ and at visit + 365 too ({$far})" );
	c357_assert( $prow && '' !== $prow['url'], '6.17 ⭐ ...still carrying the link. "Indefinitely" reaches the page, not only the predicate' );

	/*
	 * ⭐ THE BOUNDED WINDOW IS STILL REACHABLE THROUGH THE FILTER, AND THE ROW
	 *    STILL EXPIRES UNDER IT. This is what proves 6.13/6.14 inverted because
	 *    the DEFAULT moved, not because the expiry code was deleted.
	 */
	$c357_p5 = static function () {
		return 5;
	};
	add_filter( 'bhp_school_visit_after_days', $c357_p5 );
	$prow = $pick( bhp_author_visits_build_past_rows( $rec, $out ) );
	c357_assert( $prow && empty( $prow['after'] ), '6.18 ⛔ under a filtered 5-day window the same row at visit + 31 is a pure trust record again' );
	c357_assert( $prow && '' === $prow['url'], '6.19 ⛔ ...with the ordering link GONE - the expiry path still works, it is just no longer the default' );
	remove_filter( 'bhp_school_visit_after_days', $c357_p5 );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · THE COPY
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( ! function_exists( 'bhp_visit_band_after_line' ) ) {
	c357_skip( '7.x the after-visit copy', 'inc/visit-band.php is not loaded' );
} else {
	$rec = array( 'school' => 'Sample Elementary', 'date' => '2026-04-15' );

	$today_is = static function ( $ymd ) {
		return static function () use ( $ymd ) {
			return $ymd;
		};
	};

	$on_day = $today_is( '2026-04-15' );
	add_filter( 'bhp_school_visit_today', $on_day );
	$line_today = bhp_visit_band_after_line( $rec );
	remove_filter( 'bhp_school_visit_today', $on_day );

	$later = $today_is( '2026-04-20' );
	add_filter( 'bhp_school_visit_today', $later );
	$line_later = bhp_visit_band_after_line( $rec );
	remove_filter( 'bhp_school_visit_today', $later );

	c357_assert( false !== strpos( $line_today, 'Sample Elementary' ), '7.1 the line names the school from the record' );
	c357_assert( false !== strpos( $line_today, ' today.' ), '7.2 ⭐ on the visit date the line says "today"' );
	c357_assert( false === strpos( $line_later, ' today.' ), '7.3 ⭐⭐ ...and on any later day it does NOT' );
	c357_assert( false !== strpos( $line_later, 'April 15' ), '7.4 ...it names the date instead' );
	c357_assert(
		false !== strpos( $line_today, 'shipped to your home' ) && false !== strpos( $line_later, 'shipped to your home' ),
		'7.5 ⭐ both forms state the offer: ordered here and SHIPPED'
	);

	// ⛔ A record with no usable date must not produce a date claim.
	$line_bare = bhp_visit_band_after_line( array( 'school' => 'Sample Elementary' ) );
	c357_assert( false === strpos( $line_bare, 'today' ) && false !== strpos( $line_bare, 'Sample Elementary' ), '7.6 ⛔ no usable date, no date claim, and the offer still stands' );
	c357_assert( '' === bhp_visit_band_after_line( array() ), '7.7 ⛔ no school, no sentence - it fails closed' );

	/*
	 * ⛔ THE NEVER-INVENT RULE, AS AN ASSERTION. Nothing in this band may claim
	 *    how the read-aloud went, quote anybody, or count anything.
	 */
	$all = $line_today . ' ' . $line_later . ' ' . $line_bare;
	c357_assert( false === strpos( $all, '—' ), '7.8 ⛔ no em dash in the band copy (608a)' );
	foreach ( array( ' we ', ' our ', ' us ', 'We ', 'Our ' ) as $needle ) {
		c357_assert( false === strpos( $all, $needle ), "7.9 ⛔ no company \"we\" in the band copy ({$needle})" );
	}
	foreach ( array( 'loved', 'enjoyed', 'amazing', 'everyone', 'best', 'only ', 'left' ) as $needle ) {
		c357_assert( false === stripos( $all, $needle ), "7.10 ⛔ no reaction, superlative or counter language ({$needle})" );
	}

	if ( function_exists( 'bhp_visit_band_after_link_label' ) ) {
		$label = bhp_visit_band_after_link_label();
		c357_assert( false !== stripos( $label, 'shipped' ), '7.11 ⭐ the link label says SHIPPED' );
		c357_assert( false === stripos( $label, 'signed' ), '7.12 ⛔⛔ ...and never SIGNED - nobody signs a posted book' );
	} else {
		c357_skip( '7.11-7.12 the link label', 'bhp_visit_band_after_link_label() is not loaded' );
	}
}

/*
 * ⛔ THE COPY LIVES IN ONE PLACE. The brief asked for it, and a second copy of
 *    a sentence is how two surfaces come to say different things.
 */
if ( '' !== $c357_tmpl && '' !== $c357_band ) {
	c357_assert(
		false === strpos( $c357_tmpl, 'shipped to your home' ) || false !== strpos( $c357_tmpl, 'bhp_visit_band_after_link_label' ),
		'7.13 ⭐ the template reads the link label from the one helper rather than restating it'
	);
	/*
	 * ⛔ THE FIRST VERSION OF THIS ASSERTION COUNTED THE BARE SENTENCE AND
	 *    FAILED HONESTLY, because the sentence appears TWICE in the file: once
	 *    as the translated string, and once inside the docblock that quotes the
	 *    brief's draft wording. ⭐ The docblock quote is deliberate and stays —
	 *    the words are pending the founder's approval and a reader has to be
	 *    able to see what was approved against what was built. What the claim is
	 *    actually about is that the STRING exists once, so that is what is now
	 *    counted.
	 */
	c357_assert(
		substr_count( $c357_band, "'Books can still be ordered here and shipped to your home.', 'brave-hearts'" ) === 1,
		'7.14 ⭐ the band offer STRING is authored EXACTLY ONCE'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · THE SHIPPED ARTEFACT, AND THE STANDING RAILS
 * ═══════════════════════════════════════════════════════════════════════════ */

c357_assert( '' !== $c357_min, '8.1 style.min.css is readable' );
c357_assert( false !== strpos( $c357_min, 'bhp-visit-band--after' ), '8.2 ⭐ the after band is SERVED, not only authored' );
c357_assert( false !== strpos( $c357_css, '.bhp-visit-band--after' ), '8.3 ...and declared in the source' );

$c357_src = get_template_directory() . '/style.css';
if ( file_exists( $c357_src ) ) {
	$c357_hash = md5( str_replace( "\r\n", "\n", (string) file_get_contents( $c357_src ) ) );
	c357_assert( false !== strpos( $c357_min, $c357_hash ), '8.4 ⛔ style.min.css was built from the CURRENT style.css' );
}
c357_assert( false !== strpos( $c357_min, 'Version: ' . wp_get_theme()->get( 'Version' ) ), '8.5 ...and rebuilt for THIS release' );

/*
 * ⛔ NO REAL SCHOOL, SLUG OR DATE IN ANY SOURCE THIS PASS TOUCHED. The registry
 *    is DATA and these files are CODE, in a PUBLIC repository. The needles are
 *    read from the LIVE registry so this assertion cannot go stale when a new
 *    visit is added.
 */
if ( function_exists( 'bhp_school_visit_records' ) ) {
	/*
	 * ⚠️⚠️ THIS ASSERTION FAILED ON ITS FIRST RUN, AND IT WAS RIGHT TO. It found
	 *     a REAL production visit slug in a comment at `inc/visit-band.php`,
	 *     written by the 1.19.350-FIX pass, in a repository that is PUBLIC.
	 *     ⛔ IT IS REGISTERED AS `CYCLE179-LD-22` AND IS DELIBERATELY NOT FIXED
	 *     HERE. Rewriting another release's preserved comment is exactly the
	 *     unscoped edit this lane's brief forbids, and it is the same call the
	 *     `LD-18` em dash got for the same reason. It is reported to the Chief
	 *     of Staff rather than quietly corrected.
	 *
	 * ⭐ THE ASSERTION IS THEREFORE SCOPED TO THE LINES THIS PASS AUTHORED,
	 *    which is the claim it can honestly make, and the pre-existing leak is
	 *    counted and PRINTED so that narrowing the scope cannot hide it.
	 *    ⛔ The needles are read from the LIVE registry, so this cannot go stale
	 *    when a new visit is added.
	 */
	$leak     = array();
	$inherited = array();
	$sources  = array(
		'inc/visit-band.php'      => $c357_band,
		'inc/author-visits.php'   => $c357_visits,
		'page-author-visits.php'  => $c357_tmpl,
	);
	foreach ( bhp_school_visit_records() as $slug => $r ) {
		foreach ( $sources as $name => $src ) {
			if ( '' === $src ) {
				continue;
			}
			foreach ( explode( "\n", $src ) as $i => $line ) {
				if ( false === strpos( $line, (string) $slug ) ) {
					continue;
				}
				$where = $name . ':' . ( $i + 1 );
				if ( false !== strpos( $line, '1.19.357' ) || false !== strpos( $line, 'CYCLE179-LD-357' ) ) {
					$leak[] = $where;
				} else {
					$inherited[] = $where;
				}
			}
		}
	}
	c357_assert( array() === $leak, '8.6 ⛔ no real visit slug on any line THIS PASS authored' );
	/*
	 * ⛔ THIS IS A REPORT LINE, NOT AN ASSERTION, AND THAT IS DELIBERATE. Making
	 *    "the leak is still there" a PASS would mean the suite FAILS on the day
	 *    somebody fixes it, which is the wrong incentive in the wrong direction.
	 *    It prints, every run, until the line is gone.
	 */
	if ( ! empty( $inherited ) ) {
		echo 'NOTE  CYCLE179-LD-22 - a real visit slug is present in INHERITED source, not written by this pass and not fixed by it: ' . implode( ', ', $inherited ) . "\n";
	} else {
		echo "NOTE  CYCLE179-LD-22 appears to be CLOSED - no inherited visit slug found.\n";
	}
} else {
	c357_skip( '8.6 no real visit slug in source', 'the registry reader is not loaded' );
}

/*
 * ⛔⛔ NO EM DASH IN ANY CUSTOMER-FACING STRING THIS PASS AUTHORED (608a).
 *
 * ⚠️ THE FIRST VERSION OF THIS ASSERTION FAILED, AND THE ASSERTION WAS WRONG
 *    RATHER THAN THE CODE. It flagged every line that NAMED this release and
 *    contained an em dash, which caught this pass's own COMMENT headings. Rule
 *    608a governs the words a customer reads; it does not govern a docblock,
 *    and this file's every neighbouring comment uses em dashes as its house
 *    style. An assertion that checks more than its label claims is the exact
 *    defect `CYCLE179-LD-356` recorded against its own suite, so it is narrowed
 *    to what the rule actually covers rather than the code being churned to
 *    satisfy a bad test.
 *
 * ⭐ WHAT IT NOW CHECKS: every NEW translated string this pass authored, found
 *    by matching the translation calls on the lines this release added. The
 *    band copy is additionally covered, from the rendered side, by 7.8.
 */
$c357_new_strings = array(
	'Books can still be ordered here and shipped to your home.',
	'Thank you for having me at %1$s today. %2$s',
	'Thank you for having me at %1$s on %2$s. %3$s',
	'Thank you for having me at %1$s. %2$s',
	'Order books shipped to your home',
	'Read-aloud done. Books can still be ordered and shipped to your home.',
	'SHIP AS NORMAL. Ordered after the read-aloud at %1$s on %2$s (visit: %3$s). This order is shipped and is NOT excluded from the Bookvault print/fulfilment push.',
);
$c357_emdash = array();
foreach ( $c357_new_strings as $s ) {
	if ( false !== strpos( $s, '—' ) ) {
		$c357_emdash[] = $s;
	}
}
c357_assert( array() === $c357_emdash, '8.7 ⛔ no em dash in any customer-facing string this pass authored (608a)' );

/*
 * ⛔ AND EVERY ONE OF THOSE STRINGS IS ACTUALLY IN THE SHIPPED SOURCE. Without
 *    this, 8.7 would be a list of strings in a test file rather than a claim
 *    about the site, and it would keep passing after the code changed.
 */
$c357_missing = array();
$c357_haystack = $c357_band . "\n" . $c357_tmpl . "\n" . $c357_pickup;
foreach ( $c357_new_strings as $s ) {
	if ( '' !== $c357_haystack && false === strpos( $c357_haystack, $s ) ) {
		$c357_missing[] = $s;
	}
}
if ( '' === $c357_pickup ) {
	c357_skip( '8.8 every string 8.7 checked is really in the shipped source', 'the plugin source could not be read' );
} else {
	c357_assert( array() === $c357_missing, '8.8 ⛔ every string 8.7 checked is really in the shipped source' );
}

echo "\n============================================================\n";
echo "CYCLE179-LD-357 - {$GLOBALS['c357_passes']} passed, {$GLOBALS['c357_failures']} failed, {$GLOBALS['c357_skips']} skipped\n";
echo "============================================================\n";
