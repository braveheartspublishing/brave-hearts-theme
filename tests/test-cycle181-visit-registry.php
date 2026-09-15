<?php
/**
 * ⭐⭐ CYCLE181-LD-VISIT-REGISTRY-AMITY-1007 — the visit-registry arithmetic
 *     tripwire. Theme 1.19.421.
 *
 * Run with:
 *   wp eval-file wp-content/themes/<slug>/tests/test-cycle181-visit-registry.php \
 *       --url=<site-url> --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY THIS SUITE EXISTS, AND IT IS NOT "A SECOND SCHOOL WAS ADDED".
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT CLASS IT GUARDS IS `CYCLE181-MKT-AVD-4`, LIVE AND UNFIXED SINCE
 *    2026-09-02 AND ESCALATED TWICE: `/author-visits/` prints a deadline
 *    derived from the row's own `cutoff` field, while the ordering gate closes
 *    at `visit - 2` computed from the row's `date`. ⛔ NOTHING IN THE CODE
 *    FORCES THE TWO TO AGREE — they are two fields of one hand-entered row —
 *    so a row typed at `visit - 1` makes two customer-facing surfaces state two
 *    different deadlines to the same family. That has already happened on a
 *    real production row (`CYCLE179-LD-350` §8.3).
 *
 * ⭐ `bhp_visit_deadline_display()` CONTAINS THE DAMAGE BUT DOES NOT CLOSE IT.
 *    It clamps the printed date to the online close when `cutoff` is LATER than
 *    `visit - 2`, so the page can never advertise a date past the gate. §2
 *    asserts that clamp in both directions. ⛔ What it cannot do is make a row
 *    typed EARLY agree with the gate: a `visit - 3` row still prints one day
 *    and closes on another, which is the state every legacy row is in.
 *
 * ⭐⭐ §3 IS THEREFORE THE ROW THAT MATTERS. It walks the LIVE registry on
 *     whatever environment it runs on and reports, per row, whether the printed
 *     deadline equals the computed close. ⛔ IT IS DELIBERATELY NOT A HARD
 *     FAILURE FOR LEGACY ROWS: four rows are already live at `visit - 3` or
 *     `visit - 1`, they are Andrew's data to edit, and a suite that went red on
 *     them would be switched off within a week and would then be protecting
 *     nothing. It FAILS only where the divergence is UNSAFE (printed later than
 *     the gate) and otherwise REPORTS. ⭐ The convergent row entered by this
 *     workstream is asserted EXACTLY, so the pattern cannot silently regress.
 *
 * ⛔⛔ §5 IS THE ONE THAT WOULD CATCH THE WORST PLAUSIBLE BUG. Two visits at the
 *     SAME SCHOOL now sit in the registry at once, one before its read-aloud and
 *     one after. The entitlement resolver and the after-visit resolver are
 *     mutually exclusive BY CONSTRUCTION, never by a flag, and if that ever
 *     stopped being true a family arriving after one visit could be granted free
 *     hand delivery against the other. §5 requires each slug to resolve through
 *     exactly one of the two, and requires the pair to disagree with each other.
 *
 * ⚠ NO REAL SLUG IS HARDCODED AS A REQUIREMENT. Every row that names one is
 *   SKIPPED, not failed, when that row is absent from the environment — the
 *   registry is DATA and a test that demanded production data would go red on
 *   any clean install. The arithmetic rows in §1 and §2 use synthetic records
 *   and run everywhere.
 *
 * ⚠ WHAT A GREEN RUN DOES NOT PROVE. This suite exercises PHP. It does NOT
 *   prove what `/shop/` or `/author-visits/` painted in a browser, and it does
 *   NOT prove the real-world calendar behind any row. Both are reported
 *   separately in `CYCLE181-LD-VISIT-REGISTRY-AMITY-1007`.
 *
 * @package BraveHearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['vr421_passes']   = 0;
$GLOBALS['vr421_failures'] = 0;
$GLOBALS['vr421_skips']    = 0;

function vr421_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['vr421_passes']++;
		printf( "  PASS  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '' );
	} else {
		$GLOBALS['vr421_failures']++;
		printf( "  FAIL  %s%s\n", $label, '' !== $detail ? "  [$detail]" : '' );
	}
	return (bool) $ok;
}

function vr421_skip( $label, $why ) {
	$GLOBALS['vr421_skips']++;
	printf( "  SKIP  %s  [%s]\n", $label, $why );
}

function vr421_note( $line ) {
	printf( "  ....  %s\n", $line );
}

/**
 * A synthetic record in exactly the shape `bhp_school_visit_records()` returns.
 * Nothing here touches the live option.
 */
function vr421_row( $slug, $date, $cutoff, $school = 'Test Elementary', $time = '9:00 AM', $hide = false ) {
	return array(
		'slug'       => $slug,
		'school'     => $school,
		'date'       => $date,
		'cutoff'     => $cutoff,
		'time'       => $time,
		'hide_stock' => (bool) $hide,
	);
}

echo "\n=== CYCLE181-LD-VISIT-REGISTRY — theme 1.19.421 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §0 — THE FUNCTIONS THIS SUITE DEPENDS ON ARE REALLY LOADED.
   ⛔ Without this, every row below could "pass" by being skipped, and a suite
      that skips itself green is worse than no suite.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§0 · Dependencies\n";
$vr421_need = array(
	'bhp_school_visit_records',
	'bhp_school_visit_last_order_date',
	'bhp_school_visit_is_open_on',
	'bhp_school_visit_is_after_on',
	'bhp_school_visit_resolve',
	'bhp_school_visit_resolve_after',
	'bhp_school_visit_record_hides_stock',
	'bhp_visit_deadline_display',
	'bhp_author_visits_build_rows',
	'bhp_author_visits_build_past_rows',
);
$vr421_have = true;
foreach ( $vr421_need as $fn ) {
	$ok = function_exists( $fn );
	vr421_assert( "0.1 $fn() is loaded", $ok );
	$vr421_have = $vr421_have && $ok;
}

if ( ! $vr421_have ) {
	echo "\n⛔ Required functions missing — the bundle plugin or the theme's visit files are not loaded.\n";
	printf(
		"\n=== %d passed, %d failed, %d skipped ===\n",
		$GLOBALS['vr421_passes'],
		$GLOBALS['vr421_failures'],
		$GLOBALS['vr421_skips']
	);
	exit( 1 );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §1 — THE ARITHMETIC. Synthetic rows only; runs on every environment.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§1 · The close is visit minus 2, and the boundaries are exact\n";

vr421_assert( '1.1 last order date of a 2026-10-07 visit is 2026-10-05',
	'2026-10-05' === (string) bhp_school_visit_last_order_date( '2026-10-07' ),
	(string) bhp_school_visit_last_order_date( '2026-10-07' ) );

// ⛔ A month boundary, because "minus 2" implemented with string maths would
//    pass every same-month row and break on the first of a month.
vr421_assert( '1.2 it crosses a month boundary (2026-11-02 -> 2026-10-31)',
	'2026-10-31' === (string) bhp_school_visit_last_order_date( '2026-11-02' ),
	(string) bhp_school_visit_last_order_date( '2026-11-02' ) );

vr421_assert( '1.3 open on the day before the close', true === bhp_school_visit_is_open_on( '2026-10-07', '2026-10-04' ) );
vr421_assert( '1.4 open ON the close itself (inclusive)', true === bhp_school_visit_is_open_on( '2026-10-07', '2026-10-05' ) );
vr421_assert( '1.5 CLOSED the morning after the close', false === bhp_school_visit_is_open_on( '2026-10-07', '2026-10-06' ) );
vr421_assert( '1.6 closed on the visit day itself', false === bhp_school_visit_is_open_on( '2026-10-07', '2026-10-07' ) );

// ⭐ THE ONE DAY IN NEITHER STATE. `visit - 1` is not open and not after, and
//    that gap is what the closed band exists to speak into. A change that made
//    the two windows adjacent would silently delete the closed band.
vr421_assert( '1.7 visit-1 is NOT open', false === bhp_school_visit_is_open_on( '2026-10-07', '2026-10-06' ) );
vr421_assert( '1.8 visit-1 is NOT after either — the deliberate gap',
	false === bhp_school_visit_is_after_on( '2026-10-07', '2026-10-06' ) );
vr421_assert( '1.9 after opens ON the visit day', true === bhp_school_visit_is_after_on( '2026-10-07', '2026-10-07' ) );
vr421_assert( '1.10 after is still true a month later (no expiry by default)',
	true === bhp_school_visit_is_after_on( '2026-10-07', '2026-11-07' ) );

// ⛔ Fail-closed on garbage, asserted rather than assumed.
vr421_assert( '1.11 an unusable date is not open', false === bhp_school_visit_is_open_on( 'not-a-date', '2026-10-05' ) );
vr421_assert( '1.12 an unusable date is not after', false === bhp_school_visit_is_after_on( 'not-a-date', '2026-10-05' ) );
vr421_assert( '1.13 an impossible date is rejected (2026-02-30)',
	false === bhp_school_visit_is_open_on( '2026-02-30', '2026-02-01' ) );

/* ═══════════════════════════════════════════════════════════════════════════
   §2 — THE PRINTED DEADLINE vs THE GATE. The AVD-4 clamp, both directions.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§2 · bhp_visit_deadline_display() — the clamp, in both directions\n";

$vr421_conv  = vr421_row( 'synthetic-convergent', '2026-10-07', '2026-10-05' );
$vr421_early = vr421_row( 'synthetic-early', '2026-10-07', '2026-10-04' );
$vr421_late  = vr421_row( 'synthetic-late', '2026-10-07', '2026-10-06' );

vr421_assert( '2.1 a convergent row (cutoff == visit-2) prints the gate date',
	'2026-10-05' === (string) bhp_visit_deadline_display( $vr421_conv ),
	(string) bhp_visit_deadline_display( $vr421_conv ) );

vr421_assert( '2.2 an EARLY row prints its own stated cutoff (never later than the gate)',
	'2026-10-04' === (string) bhp_visit_deadline_display( $vr421_early ),
	(string) bhp_visit_deadline_display( $vr421_early ) );

// ⛔⛔ THE ROW THAT GUARDS A REAL CUSTOMER HARM. A row typed at `visit - 1` must
//    NOT print `visit - 1`: that would tell a family they may order on a day the
//    button is already grey.
vr421_assert( '2.3 ⛔ a LATE row is CLAMPED to the gate, not printed as typed',
	'2026-10-05' === (string) bhp_visit_deadline_display( $vr421_late ),
	(string) bhp_visit_deadline_display( $vr421_late ) );

vr421_assert( '2.3b ...and the clamp really moved it (control)',
	'2026-10-06' !== (string) bhp_visit_deadline_display( $vr421_late ) );

/* ═══════════════════════════════════════════════════════════════════════════
   §3 — THE LIVE REGISTRY, ROW BY ROW. The AVD-4 census.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§3 · Live registry census — printed deadline vs computed close\n";

$vr421_records = bhp_school_visit_records();
vr421_note( sprintf( '%d row(s) in this environment\'s registry', count( $vr421_records ) ) );

$vr421_diverged  = 0;
$vr421_convergent = 0;
foreach ( $vr421_records as $slug => $rec ) {
	$gate    = (string) bhp_school_visit_last_order_date( $rec['date'] );
	$printed = (string) bhp_visit_deadline_display( $rec );

	// ⛔ THE HARD RULE, AND THE ONLY ONE: the page may never print a deadline
	//    LATER than the gate. Anything else is reported, not failed.
	vr421_assert(
		sprintf( '3.1 [%s] printed deadline is not later than the gate', $slug ),
		'' !== $printed && '' !== $gate && $printed <= $gate,
		"printed $printed / gate $gate"
	);

	if ( $printed === $gate ) {
		$vr421_convergent++;
	} else {
		$vr421_diverged++;
		vr421_note( sprintf(
			'⚠️ AVD-4 divergence on [%s]: /author-visits/ prints %s, ordering closes %s (row cutoff %s, visit %s)',
			$slug, $printed, $gate, $rec['cutoff'], $rec['date']
		) );
	}
}
vr421_note( sprintf( '%d convergent, %d diverged (AVD-4 is a DATA defect and is Andrew\'s to edit)',
	$vr421_convergent, $vr421_diverged ) );

/* ═══════════════════════════════════════════════════════════════════════════
   §4 — THE ROW THIS WORKSTREAM ENTERED. Skipped where absent.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§4 · The convergent row entered by CYCLE181-LD-VISIT-REGISTRY-AMITY-1007\n";

$vr421_new = 'amity-2026-10-07';
if ( ! isset( $vr421_records[ $vr421_new ] ) ) {
	vr421_skip( '4.x the new row', "$vr421_new is not in this environment's registry" );
} else {
	$r = $vr421_records[ $vr421_new ];
	vr421_assert( '4.1 visit date is 2026-10-07', '2026-10-07' === (string) $r['date'], (string) $r['date'] );
	vr421_assert( '4.2 stated cutoff is 2026-10-05', '2026-10-05' === (string) $r['cutoff'], (string) $r['cutoff'] );
	vr421_assert( '4.3 ⭐ stated cutoff EQUALS the computed close — AVD-4 cannot arise on this row',
		(string) $r['cutoff'] === (string) bhp_school_visit_last_order_date( $r['date'] ) );
	vr421_assert( '4.4 ...and the printed deadline is that same date',
		'2026-10-05' === (string) bhp_visit_deadline_display( $r ),
		(string) bhp_visit_deadline_display( $r ) );
	vr421_assert( '4.5 the school is named', '' !== trim( (string) $r['school'] ), (string) $r['school'] );
	vr421_assert( '4.6 a display time is carried', '' !== trim( (string) $r['time'] ), (string) $r['time'] );

	// ⭐ RECORDED, NOT ASSERTED EITHER WAY. Whether a SECOND visit at a school
	//    whose first visit was founder-ruled stock-hidden should inherit that
	//    ruling is ANDREW'S, not this suite's. The row states what is true today
	//    so a change is visible rather than silent.
	vr421_note( sprintf( '[%s] hides stock: %s (registry hide_stock=%s)',
		$vr421_new,
		bhp_school_visit_record_hides_stock( $r ) ? 'YES' : 'no',
		var_export( (bool) $r['hide_stock'], true ) ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 — TWO VISITS AT ONE SCHOOL. The mutual exclusion that protects the gate.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§5 · Two live rows for one school cannot bleed into each other\n";

$vr421_old = 'amity-2026-09-14';
if ( ! isset( $vr421_records[ $vr421_new ] ) || ! isset( $vr421_records[ $vr421_old ] ) ) {
	vr421_skip( '5.x the two-visit pair', 'both rows are not present in this environment' );
} else {
	$open_new  = bhp_school_visit_resolve( $vr421_new );
	$after_new = bhp_school_visit_resolve_after( $vr421_new );
	$open_old  = bhp_school_visit_resolve( $vr421_old );
	$after_old = bhp_school_visit_resolve_after( $vr421_old );

	// ⛔⛔ EACH SLUG RESOLVES THROUGH EXACTLY ONE WINDOW. Never both.
	vr421_assert( '5.1 ⛔ the upcoming row is not ALSO an after-visit row',
		! ( is_array( $open_new ) && is_array( $after_new ) ) );
	vr421_assert( '5.2 ⛔ the past row is not ALSO an entitlement row',
		! ( is_array( $open_old ) && is_array( $after_old ) ) );

	// ⭐ AND THE TWO DISAGREE WITH EACH OTHER — which is what proves the
	//    resolvers are reading the row and not the school name.
	vr421_assert( '5.3 the two rows are in DIFFERENT states',
		is_array( $open_new ) !== is_array( $open_old ) );

	vr421_assert( '5.4 both rows survive sanitisation as distinct records',
		$vr421_records[ $vr421_new ]['date'] !== $vr421_records[ $vr421_old ]['date'] );

	// ⛔ The after-visit resolver must never hand back an entitlement shape that
	//    a caller could mistake for the open one: assert the slug it returns.
	if ( is_array( $after_old ) ) {
		vr421_assert( '5.5 resolve_after() returns the row it was asked for',
			$vr421_old === (string) $after_old['slug'], (string) $after_old['slug'] );
	}
	if ( is_array( $open_new ) ) {
		vr421_assert( '5.6 resolve() returns the row it was asked for',
			$vr421_new === (string) $open_new['slug'], (string) $open_new['slug'] );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   §6 — /author-visits/ COLUMN PLACEMENT. PURE — synthetic records and a fixed
        "today", so no waiting and no live data required.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§6 · The page puts a row in exactly one column, on the visit date\n";

$vr421_set = array(
	'synthetic-future' => vr421_row( 'synthetic-future', '2026-10-07', '2026-10-05' ),
	'synthetic-past'   => vr421_row( 'synthetic-past', '2026-09-14', '2026-09-12' ),
);
$vr421_today = '2026-09-15';

$up   = bhp_author_visits_build_rows( $vr421_set, $vr421_today );
$past = bhp_author_visits_build_past_rows( $vr421_set, $vr421_today, array() );

$up_slugs   = wp_list_pluck( $up, 'slug' );
$past_slugs = wp_list_pluck( $past, 'slug' );

vr421_assert( '6.1 the future row is UPCOMING', in_array( 'synthetic-future', $up_slugs, true ) );
vr421_assert( '6.2 the future row is NOT also past', ! in_array( 'synthetic-future', $past_slugs, true ) );
vr421_assert( '6.3 the past row is PAST', in_array( 'synthetic-past', $past_slugs, true ) );
vr421_assert( '6.4 the past row is NOT also upcoming', ! in_array( 'synthetic-past', $up_slugs, true ) );

// ⭐ THE PARTITION IS ON THE VISIT DATE AND IT IS INCLUSIVE — a school is still
//    in the upcoming column on the morning of the read-aloud.
$on_the_day = bhp_author_visits_build_rows( $vr421_set, '2026-10-07' );
vr421_assert( '6.5 a visit is still UPCOMING on the morning of the visit itself',
	in_array( 'synthetic-future', wp_list_pluck( $on_the_day, 'slug' ), true ) );

// ⛔ The upcoming row's `open` flag comes from the plugin's gate, not the cutoff.
foreach ( $up as $row ) {
	if ( 'synthetic-future' === $row['slug'] ) {
		vr421_assert( '6.6 the upcoming row is open on 2026-09-15', true === (bool) $row['open'] );
	}
}
$closed_day = bhp_author_visits_build_rows( $vr421_set, '2026-10-06' );
foreach ( $closed_day as $row ) {
	if ( 'synthetic-future' === $row['slug'] ) {
		vr421_assert( '6.7 ...and CLOSED on 2026-10-06, the day after the gate',
			false === (bool) $row['open'] );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   §7 — THE REGISTRY IS DATA. No real slug may appear in the visit SOURCE files.
   ⛔ This repeats an assertion two existing suites already make, deliberately:
      this workstream's whole deliverable is a registry row, and a row that got
      "helpfully" hardcoded into a template is the exact mistake it invites.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§7 · No live visit slug leaked into the source\n";

/*
 * ⚠️⚠️ THE TWO FILES ARE GRADED DIFFERENTLY, AND THE ASYMMETRY IS A FINDING
 *     RATHER THAN A PREFERENCE. `inc/author-visits.php` is already asserted
 *     clean by `tests/test-author-visits-page.php` and
 *     `tests/test-cycle169-visits-trust-gallery.php`, so this row is HARD.
 *     ⛔ `inc/visit-band.php` IS COVERED BY NEITHER, AND IT CURRENTLY CARRIES
 *     ONE REAL SLUG IN A COMMENT (the row that motivated the AVD-4 clamp).
 *     That is a PRE-EXISTING condition in a file this workstream did not
 *     touch, so it is REPORTED, not failed: shipping a red suite for something
 *     outside the brief would train the next reader to ignore the suite.
 *     ⭐ Recorded in `CYCLE181-LD-VISIT-REGISTRY-AMITY-1007` as a finding for
 *     Gandalf to route.
 */
$vr421_files = array(
	get_template_directory() . '/inc/author-visits.php' => 'hard',
	get_template_directory() . '/inc/visit-band.php'    => 'report',
);
foreach ( $vr421_files as $file => $grade ) {
	if ( ! is_readable( $file ) ) {
		vr421_skip( '7.x ' . basename( $file ), 'not readable' );
		continue;
	}
	$src = (string) file_get_contents( $file );
	vr421_assert( sprintf( '7.1 %s was really read (control)', basename( $file ) ),
		strlen( $src ) > 1000, strlen( $src ) . ' bytes' );

	$hits = array();
	foreach ( array_keys( $vr421_records ) as $slug ) {
		if ( false !== strpos( $src, (string) $slug ) ) {
			$hits[] = $slug;
		}
	}

	if ( 'hard' === $grade ) {
		vr421_assert( sprintf( '7.2 ⛔ no live registry slug appears in %s', basename( $file ) ),
			empty( $hits ), empty( $hits ) ? 'clean' : implode( ',', $hits ) );
	} else {
		vr421_note( sprintf( '7.3 %s carries %d live slug(s) in source: %s — PRE-EXISTING, reported not failed',
			basename( $file ), count( $hits ), empty( $hits ) ? 'none' : implode( ',', $hits ) ) );
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   §8 — NOTHING COMMERCIAL MOVED. This lane wrote a registry row and a test; it
        must not have touched a product, a price or the shipping zone.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n§8 · No WooCommerce record was disturbed\n";

if ( ! function_exists( 'wc_get_products' ) ) {
	vr421_skip( '8.x WooCommerce assertions', 'WooCommerce not loaded' );
} else {
	$pub = wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids' ) );
	vr421_assert( '8.1 published products still exist', is_array( $pub ) && count( $pub ) >= 1,
		count( (array) $pub ) . ' published' );

	$zones = class_exists( 'WC_Shipping_Zones' ) ? WC_Shipping_Zones::get_zones() : array();
	$bv    = 0;
	foreach ( (array) $zones as $z ) {
		foreach ( (array) ( isset( $z['shipping_methods'] ) ? $z['shipping_methods'] : array() ) as $m ) {
			if ( false !== stripos( (string) $m->get_method_title(), 'bookvault' ) ) {
				$bv++;
			}
		}
	}
	vr421_assert( '8.2 ⛔ no BookVAULT shipping method is zoned', 0 === $bv, "$bv found" );
}

/* ═══════════════════════════════════════════════════════════════════════════
   SUMMARY
   ═══════════════════════════════════════════════════════════════════════════ */
printf(
	"\n=== %d passed, %d failed, %d skipped ===\n",
	$GLOBALS['vr421_passes'],
	$GLOBALS['vr421_failures'],
	$GLOBALS['vr421_skips']
);

if ( $GLOBALS['vr421_failures'] > 0 ) {
	exit( 1 );
}
