<?php
/**
 * CYCLE165-LD-VISIT-FLAG-EXPIRY — THE SCHOOL-VISIT FLAG EXPIRES.
 *
 * Run via WP-CLI, from the WordPress document root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-flag-expiry.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔⛔ NOTHING CHANGES FOR A REAL FAMILY TODAY. All three live visits are
 *      inside their ordering window as this ships. §2 asserts that against the
 *      REAL registry rather than against a date typed into this file, so a
 *      build that quietly closed a live window could not pass.
 *
 *   2. ⭐⭐ A VISITOR WHO ONCE OPENED A SCHOOL LINK GETS THE WHOLE SHOP BACK.
 *      Andrew Signore, 2026-08-19, ⛔ RELAYED through `chief-of-staff`, NOT
 *      witnessed first-hand by the agent that wrote this: "I only wanted the
 *      paperback to be the first choice when they land on the page, not
 *      removed all together" and "Yeah we need an expiration on that - I want
 *      them to come back and buy more books." §4 (window close) and §5 (hard
 *      TTL) are the two guards that deliver it; §6 is the manual escape hatch.
 *
 *   3. THE TWO GUARDS ARE INDEPENDENT. §5 proves the TTL fires on a visit that
 *      is WIDE OPEN, which is the only proof that it is a real backstop and not
 *      a restatement of the window check. A registry row with a valid but wrong
 *      far-future date is the case it exists for.
 *
 *   4. EVERY SURFACE AGREES. The product page, the shop grid, the collection
 *      page and the cart drawer all ask ONE predicate. §3 asserts they still
 *      route through it, so an expiry cannot half-apply.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO stock status, NO coupon, NO
 *    shipping, tax, pickup or payment setting, on any environment. §8 re-reads
 *    the raw `wp_options` rows through `$wpdb` — not `get_option()`, which the
 *    1.8.52 read-filters legitimately change during a flagged request — and
 *    asserts nothing was written.
 * ⛔ It NEVER writes the `bhp_school_visits` registry. That is the mistake that
 *    destroyed the three real visit rows on 2026-08-17. Every date this suite
 *    needs on the far side of a boundary is produced by MOVING THE CLOCK
 *    through the `bhp_school_visit_today` / `bhp_school_visit_now` filter
 *    seams, never by editing a row.
 * ⛔ It places NO order, delivers NO webhook and takes NO payment.
 * ⚠ It DOES set and clear two keys in this CLI request's own WooCommerce
 *    session. Both are session state, not stored settings, and §9 clears them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skips    = array();

function bhp_vfe_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_vfe_skip( $label, $reason, array &$skips ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$skips[] = $label;
}

/**
 * Raw stored option value, read past every filter.
 *
 * ⛔ NOT `get_option()`. `school-visit-pickup.php` filters the READS of the two
 *    WooCommerce local-pickup options so pickup exists for the duration of one
 *    flagged request. `get_option()` therefore legitimately returns different
 *    values inside and outside a flagged path, and a before/after comparison
 *    built on it reports a "change" where no byte moved on disk. The question
 *    §8 asks is "was anything WRITTEN", so it reads the row.
 *
 * @param string $name Option name.
 * @return string Raw stored value, or the literal 'ABSENT'.
 */
function bhp_vfe_raw_option( $name ) {
	global $wpdb;
	$val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ) ); // phpcs:ignore WordPress.DB
	return null === $val ? 'ABSENT' : (string) $val;
}

echo "\n=== CYCLE165 school-visit flag expiry — bundle plugin "
	. ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '?' )
	. " / theme " . wp_get_theme()->get( 'Version' ) . " ===\n\n";

$bhp_vfe_opts_before = array(
	'bhp_school_visits'                     => bhp_vfe_raw_option( 'bhp_school_visits' ),
	'woocommerce_pickup_location_settings'  => bhp_vfe_raw_option( 'woocommerce_pickup_location_settings' ),
	'pickup_location_pickup_locations'      => bhp_vfe_raw_option( 'pickup_location_pickup_locations' ),
	'woocommerce_flat_rate_1_settings'      => bhp_vfe_raw_option( 'woocommerce_flat_rate_1_settings' ),
);

/* =====================================================================
 * §1 — PRECONDITIONS. The build is present and wired.
 * ===================================================================== */
echo "--- §1 preconditions ---\n";

bhp_vfe_assert( defined( 'BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY' ), '§1 the TTL stamp has its own session key (the old slug key keeps its old meaning)', $failures );
bhp_vfe_assert( defined( 'BHP_SCHOOL_VISIT_TTL_SECONDS' ), '§1 the hard TTL is a named constant', $failures );
bhp_vfe_assert( defined( 'BHP_SCHOOL_VISIT_CLEAR_TOKEN' ), '§1 the clear token is a named constant', $failures );
bhp_vfe_assert( function_exists( 'bhp_school_visit_now' ), '§1 the movable clock for the TTL is exposed', $failures );
bhp_vfe_assert( function_exists( 'bhp_school_visit_ttl_seconds' ), '§1 the TTL length is a function, not a literal at the call site', $failures );
bhp_vfe_assert( function_exists( 'bhp_school_visit_ttl_expired' ), '§1 the TTL comparison is a pure, testable function', $failures );
bhp_vfe_assert( function_exists( 'bhp_school_visit_set_at_stamp' ), '§1 the stamp reader (with legacy adoption) is exposed', $failures );
bhp_vfe_assert( function_exists( 'bhp_school_visit_clear_session' ), '§1 there is ONE author of "clear this session"', $failures );

bhp_vfe_assert(
	14 * 86400 === (int) BHP_SCHOOL_VISIT_TTL_SECONDS,
	'§1 the TTL is fourteen days (' . (int) BHP_SCHOOL_VISIT_TTL_SECONDS . 's)',
	$failures
);
bhp_vfe_assert( 'clear' === BHP_SCHOOL_VISIT_CLEAR_TOKEN, '§1 the clear token is the word "clear"', $failures );

/*
 * ⭐ THE HOOK IS ASSERTED TO BE REGISTERED, not assumed. A file whose
 *    add_action line was lost in an edit would pass every functional assertion
 *    below by being called directly, while doing nothing on a real request.
 */
bhp_vfe_assert(
	false !== has_action( 'template_redirect', 'bhp_school_visit_capture_intent' ),
	'§1 ⭐ the link handler is registered on template_redirect (the clear path only works on a real request if it is)',
	$failures
);

/* =====================================================================
 * §2 — ⛔⛔ THE THREE REAL VISITS ARE OPEN TODAY. NOTHING CHANGES FOR A
 *      REAL FAMILY BECAUSE OF THIS BUILD.
 * ===================================================================== */
echo "\n--- §2 the three live visits (read from the REGISTRY, not typed here) ---\n";

$bhp_vfe_today   = bhp_school_visit_today();
$bhp_vfe_records = bhp_school_visit_records();

echo "    today (site timezone " . wp_timezone_string() . "): {$bhp_vfe_today}\n";

bhp_vfe_assert( count( $bhp_vfe_records ) >= 1, '§2 the registry resolves at least one visit', $failures );

/*
 * ⛔ THE EXPECTED DATES ARE ASSERTED, because "all rows are open" is vacuously
 *    true of an empty or silently-truncated registry. These three are the rows
 *    Gandalf verified live on 2026-08-19; if the registry no longer matches,
 *    that is a finding and not something for this suite to paper over.
 */
$bhp_vfe_expected = array(
	'adams-2026-08-28'         => '2026-08-28',
	'dallas-harris-2026-09-03' => '2026-09-03',
	'liberty-2026-09-04'       => '2026-09-04',
);
foreach ( $bhp_vfe_expected as $bhp_vfe_slug => $bhp_vfe_date ) {
	if ( ! isset( $bhp_vfe_records[ $bhp_vfe_slug ] ) ) {
		bhp_vfe_skip( "§2 {$bhp_vfe_slug}", 'not in this environment\'s registry', $skips );
		continue;
	}
	$bhp_vfe_row   = $bhp_vfe_records[ $bhp_vfe_slug ];
	$bhp_vfe_last  = bhp_school_visit_last_order_date( $bhp_vfe_row['date'] );
	$bhp_vfe_close = bhp_school_visit_online_close_date( $bhp_vfe_row['date'] );
	$bhp_vfe_open  = bhp_school_visit_is_open_on( $bhp_vfe_row['date'], $bhp_vfe_today );

	printf(
		"    %-26s visit %s  stated cutoff %s  last order %s  closes %s  OPEN NOW: %s\n",
		$bhp_vfe_slug,
		$bhp_vfe_row['date'],
		$bhp_vfe_row['cutoff'],
		$bhp_vfe_last,
		$bhp_vfe_close,
		$bhp_vfe_open ? 'yes' : 'NO'
	);

	bhp_vfe_assert( $bhp_vfe_date === $bhp_vfe_row['date'], "§2 {$bhp_vfe_slug} still carries visit date {$bhp_vfe_date}", $failures );
	bhp_vfe_assert( $bhp_vfe_open, "§2 ⛔⛔ {$bhp_vfe_slug} is OPEN today — this build changes nothing for its families", $failures );
	bhp_vfe_assert( null !== bhp_school_visit_resolve( $bhp_vfe_slug ), "§2 {$bhp_vfe_slug} still RESOLVES (the entitlement is intact)", $failures );
}

/*
 * ⛔ THE STATED DEADLINE MUST STILL BE THE ONE THE PAGE PRINTS. 1.8.56 made
 *    `cutoff` display-only and the grace window is never advertised; a build
 *    that started printing the real close would advertise it.
 */
bhp_vfe_assert(
	! isset( $bhp_vfe_records['adams-2026-08-28'] )
		|| '2026-08-25' === $bhp_vfe_records['adams-2026-08-28']['cutoff'],
	'§2 the STATED cutoff is untouched by this build (the grace window stays unadvertised)',
	$failures
);

/* =====================================================================
 * §3 — THE PURE TTL BOUNDARY. No session, no registry, no clock.
 * ===================================================================== */
echo "\n--- §3 the TTL comparison, asserted to the second ---\n";

$bhp_vfe_ttl = bhp_school_visit_ttl_seconds();
$bhp_vfe_t0  = 1755600000; // An arbitrary fixed instant. Nothing reads the wall clock here.

bhp_vfe_assert( 14 * 86400 === $bhp_vfe_ttl, '§3 the TTL resolves to fourteen days with nothing hooked', $failures );

bhp_vfe_assert( false === bhp_school_visit_ttl_expired( $bhp_vfe_t0, $bhp_vfe_t0 ), '§3 a stamp set this instant is NOT expired', $failures );
bhp_vfe_assert( false === bhp_school_visit_ttl_expired( $bhp_vfe_t0, $bhp_vfe_t0 + $bhp_vfe_ttl - 1 ), '§3 ⭐ one second BEFORE the boundary: still valid', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( $bhp_vfe_t0, $bhp_vfe_t0 + $bhp_vfe_ttl ), '§3 ⭐ exactly ON the boundary: expired', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( $bhp_vfe_t0, $bhp_vfe_t0 + $bhp_vfe_ttl + 1 ), '§3 one second after: expired', $failures );

// Fails CLOSED on everything unusable. Each of these is a real corruption mode.
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( 0, $bhp_vfe_t0 ), '§3 fails CLOSED on a zero stamp', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( -1, $bhp_vfe_t0 ), '§3 fails CLOSED on a negative stamp', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( null, $bhp_vfe_t0 ), '§3 fails CLOSED on a null stamp', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( 'yesterday', $bhp_vfe_t0 ), '§3 fails CLOSED on a non-numeric stamp', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( array( $bhp_vfe_t0 ), $bhp_vfe_t0 ), '§3 fails CLOSED on an array stamp', $failures );
bhp_vfe_assert( true === bhp_school_visit_ttl_expired( $bhp_vfe_t0 + 60, $bhp_vfe_t0 ), '§3 ⭐ fails CLOSED on a FUTURE stamp (a corrupted stamp is not a longer entitlement)', $failures );
bhp_vfe_assert( false === bhp_school_visit_ttl_expired( (string) $bhp_vfe_t0, $bhp_vfe_t0 + 60 ), '§3 a numeric STRING stamp is honoured (WC session round-tripping)', $failures );

// The two filters are seams and cannot be used to break the default.
add_filter( 'bhp_school_visit_ttl_seconds', function () { return 0; } );
bhp_vfe_assert( 14 * 86400 === bhp_school_visit_ttl_seconds(), '§3 ⛔ a filter returning 0 CANNOT expire every session — the default stands', $failures );
remove_all_filters( 'bhp_school_visit_ttl_seconds' );

add_filter( 'bhp_school_visit_now', function () { return 'not-a-timestamp'; } );
bhp_vfe_assert( is_int( bhp_school_visit_now() ) && bhp_school_visit_now() > 0, '§3 ⛔ a broken now-filter falls back to the real clock', $failures );
remove_all_filters( 'bhp_school_visit_now' );

/* =====================================================================
 * §4 — WINDOW OPEN vs WINDOW CLOSED, through a REAL session.
 *      The clock moves. The registry does not.
 * ===================================================================== */
echo "\n--- §4 window open -> variant; window closed -> ordinary storefront ---\n";

$bhp_vfe_live_slug = '';
foreach ( $bhp_vfe_records as $bhp_vfe_s => $bhp_vfe_r ) {
	if ( bhp_school_visit_is_open_on( $bhp_vfe_r['date'], $bhp_vfe_today ) ) {
		$bhp_vfe_live_slug = $bhp_vfe_s;
		break;
	}
}

if ( ! function_exists( 'WC' ) ) {
	bhp_vfe_skip( '§4 to §7', 'WooCommerce is not loaded', $skips );
} elseif ( '' === $bhp_vfe_live_slug ) {
	bhp_vfe_skip( '§4 to §7', 'no live visit in the registry to flag a session with', $skips );
} else {
	if ( ! WC()->session && is_callable( array( WC(), 'initialize_session' ) ) ) {
		WC()->initialize_session();
	}

	if ( ! WC()->session ) {
		bhp_vfe_skip( '§4 to §7', 'no WooCommerce session available under WP-CLI', $skips );
	} else {
		$bhp_vfe_row  = $bhp_vfe_records[ $bhp_vfe_live_slug ];
		$bhp_vfe_last = bhp_school_visit_last_order_date( $bhp_vfe_row['date'] );

		// Flag the session exactly as an arrival through the link would.
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, bhp_school_visit_now() );

		/* ---- 4a · WINDOW OPEN ---- */
		bhp_vfe_assert(
			true === bhp_school_visit_use_delivery_framing(),
			"§4a window OPEN ({$bhp_vfe_live_slug}): the shared predicate is TRUE — the visit variant renders",
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_school_visit_paperback_only' ) && true === bhp_school_visit_paperback_only(),
			'§4a window OPEN: the paperback-only gate is ON',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_book_free_shipping_line' )
				&& bhp_school_visit_delivery_bullet() === bhp_book_free_shipping_line(),
			'§4a window OPEN: the theme bullet says hand-delivery, not shipping',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_printed_for_you_is_visit_session' ) && true === bhp_printed_for_you_is_visit_session(),
			'§4a window OPEN: the "printed for you" shipping notice is suppressed',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_bundle_hardcover_is_offerable' ) && false === bhp_bundle_hardcover_is_offerable(),
			'§4a window OPEN: hardcover is not offered (paperback-only, by design, for a pre-order)',
			$failures
		);

		/* ---- 4b · THE LAST OPEN DAY, at the boundary ---- */
		add_filter( 'bhp_school_visit_today', function () use ( $bhp_vfe_last ) { return $bhp_vfe_last; } );
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, bhp_school_visit_now() );
		bhp_vfe_assert(
			true === bhp_school_visit_use_delivery_framing(),
			"§4b ⭐ on the LAST open day ({$bhp_vfe_last}) the entitlement still stands",
			$failures
		);
		remove_all_filters( 'bhp_school_visit_today' );

		/* ---- 4c · THE FIRST CLOSED DAY. The whole point of the build. ---- */
		$bhp_vfe_closed_day = bhp_school_visit_online_close_date( $bhp_vfe_row['date'] );
		add_filter( 'bhp_school_visit_today', function () use ( $bhp_vfe_closed_day ) { return $bhp_vfe_closed_day; } );

		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, bhp_school_visit_now() );

		bhp_vfe_assert(
			false === bhp_school_visit_use_delivery_framing(),
			"§4c ⭐⭐ on the FIRST closed day ({$bhp_vfe_closed_day}) the flag is treated as ABSENT",
			$failures
		);
		bhp_vfe_assert(
			null === WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY ),
			'§4c ⭐ the expired session SELF-HEALS — the slug key is cleared, not merely ignored',
			$failures
		);
		bhp_vfe_assert(
			null === WC()->session->get( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY ),
			'§4c the stamp key is cleared with it (no orphan left behind)',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_school_visit_paperback_only' ) && false === bhp_school_visit_paperback_only(),
			'§4c ⭐⭐ HARDCOVER IS BACK: the paperback-only gate is OFF',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_bundle_hardcover_is_offerable' ) && true === bhp_bundle_hardcover_is_offerable(),
			'§4c ⭐⭐ hardcover is offerable again on the ordinary storefront',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_book_free_shipping_line' )
				&& 'FREE Shipping on the complete collection or 3 or more books purchased' === bhp_book_free_shipping_line(),
			'§4c the locked shipping sentence is back, byte-identical',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_printed_for_you_is_visit_session' ) && false === bhp_printed_for_you_is_visit_session(),
			'§4c the ordinary "printed for you" notice is restored',
			$failures
		);
		remove_all_filters( 'bhp_school_visit_today' );

		/* =============================================================
		 * §5 — THE HARD TTL, ON A VISIT THAT IS WIDE OPEN.
		 *      This is the section that proves the two guards are
		 *      genuinely independent.
		 * ============================================================= */
		echo "\n--- §5 the hard TTL, fired on an OPEN window ---\n";

		$bhp_vfe_stamp = bhp_school_visit_now();
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, $bhp_vfe_stamp );

		bhp_vfe_assert( true === bhp_school_visit_use_delivery_framing(), '§5 baseline: fresh stamp, open window, entitlement stands', $failures );

		// Day 13 — inside the TTL.
		add_filter( 'bhp_school_visit_now', function () use ( $bhp_vfe_stamp ) { return $bhp_vfe_stamp + ( 13 * 86400 ); } );
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, $bhp_vfe_stamp );
		bhp_vfe_assert( true === bhp_school_visit_use_delivery_framing(), '§5 day 13: inside the TTL, entitlement stands', $failures );
		remove_all_filters( 'bhp_school_visit_now' );

		// Day 15 — past it, with the visit STILL OPEN.
		add_filter( 'bhp_school_visit_now', function () use ( $bhp_vfe_stamp ) { return $bhp_vfe_stamp + ( 15 * 86400 ); } );
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, $bhp_vfe_stamp );

		bhp_vfe_assert(
			true === bhp_school_visit_is_open_on( $bhp_vfe_row['date'], bhp_school_visit_today() ),
			'§5 ⭐ the visit is still OPEN at this point — so only the TTL can be doing the work',
			$failures
		);
		bhp_vfe_assert(
			false === bhp_school_visit_use_delivery_framing(),
			'§5 ⭐⭐ day 15: the HARD TTL fires on its own and the flag is treated as absent',
			$failures
		);
		bhp_vfe_assert(
			null === WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY ),
			'§5 the TTL clears the session too (a dateless or far-future row cannot pin it)',
			$failures
		);
		remove_all_filters( 'bhp_school_visit_now' );

		// The legacy session: a slug with NO stamp adopts one rather than dying.
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, null );
		bhp_vfe_assert(
			true === bhp_school_visit_use_delivery_framing(),
			'§5 ⭐⭐ a PRE-1.8.59 session (slug, no stamp) inside an open window KEEPS its entitlement — a pre-order in flight is not broken by the upgrade',
			$failures
		);
		$bhp_vfe_adopted = WC()->session->get( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
		bhp_vfe_assert(
			is_int( $bhp_vfe_adopted ) && $bhp_vfe_adopted > 0,
			'§5 the legacy session ADOPTS a stamp once, so it is governed by the TTL from here on',
			$failures
		);

		/* =============================================================
		 * §6 — THE EXPLICIT CLEAR PATH.
		 * ============================================================= */
		echo "\n--- §6 ?bhp_visit=clear ---\n";

		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_vfe_live_slug );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, bhp_school_visit_now() );
		bhp_vfe_assert( true === bhp_school_visit_use_delivery_framing(), '§6 baseline: the session is flagged', $failures );

		/*
		 * ⛔ THE REGRESSION GUARD, AND IT IS THE DEFECT THAT STARTED THIS BUILD.
		 *    `commerce-cx` found on 2026-08-19 that `?bhp_visit=<bogus>` does
		 *    NOT clear a flag. That is deliberate and stays deliberate: a
		 *    truncated or mistyped URL must not strip hand delivery from a
		 *    parent who is entitled to it.
		 */
		$_GET[ BHP_SCHOOL_VISIT_PARAM ] = 'not-a-real-visit-at-all';
		bhp_school_visit_capture_intent();
		bhp_vfe_assert(
			true === bhp_school_visit_use_delivery_framing(),
			'§6 ⛔ a BOGUS slug is still a no-op and leaves the flag alone (a mistyped URL cannot strip an entitlement)',
			$failures
		);

		$_GET[ BHP_SCHOOL_VISIT_PARAM ] = BHP_SCHOOL_VISIT_CLEAR_TOKEN;
		bhp_school_visit_capture_intent();
		bhp_vfe_assert(
			false === bhp_school_visit_use_delivery_framing(),
			'§6 ⭐⭐ ?bhp_visit=clear puts the browser back on the ordinary storefront',
			$failures
		);
		bhp_vfe_assert(
			null === WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY )
				&& null === WC()->session->get( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY ),
			'§6 both session keys are cleared',
			$failures
		);
		bhp_vfe_assert(
			function_exists( 'bhp_bundle_hardcover_is_offerable' ) && true === bhp_bundle_hardcover_is_offerable(),
			'§6 ⭐ hardcover is offerable immediately after the clear',
			$failures
		);

		// Idempotent, and harmless on a session that never had a flag.
		bhp_school_visit_capture_intent();
		bhp_vfe_assert(
			false === bhp_school_visit_use_delivery_framing(),
			'§6 clearing an already-clear session is a harmless no-op',
			$failures
		);

		// And the link still ARMS the flag, with a fresh stamp.
		$_GET[ BHP_SCHOOL_VISIT_PARAM ] = $bhp_vfe_live_slug;
		bhp_school_visit_capture_intent();
		bhp_vfe_assert(
			true === bhp_school_visit_use_delivery_framing(),
			'§6 ⭐ the real link still works after a clear (the clear is not sticky)',
			$failures
		);
		$bhp_vfe_rearm = WC()->session->get( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
		bhp_vfe_assert(
			is_int( $bhp_vfe_rearm ) && $bhp_vfe_rearm > 0,
			'§6 ⭐ arriving through the link RE-ARMS the TTL (an early bird can never be locked out while the window is open)',
			$failures
		);
		unset( $_GET[ BHP_SCHOOL_VISIT_PARAM ] );

		/* =============================================================
		 * §7 — ONE PREDICATE, EVERY SURFACE.
		 * ============================================================= */
		echo "\n--- §7 every surface reads the same predicate ---\n";

		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
		WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, null );

		$bhp_vfe_surfaces = array(
			'bhp_school_visit_paperback_only'      => false,
			'bhp_printed_for_you_is_visit_session' => false,
			'bhp_school_visit_use_delivery_framing' => false,
		);
		foreach ( $bhp_vfe_surfaces as $bhp_vfe_fn => $bhp_vfe_want ) {
			if ( ! function_exists( $bhp_vfe_fn ) ) {
				bhp_vfe_skip( "§7 {$bhp_vfe_fn}", 'not defined in this environment', $skips );
				continue;
			}
			bhp_vfe_assert(
				$bhp_vfe_want === (bool) call_user_func( $bhp_vfe_fn ),
				"§7 with no flag, {$bhp_vfe_fn}() is FALSE — the control path is untouched",
				$failures
			);
		}
		bhp_vfe_assert(
			function_exists( 'bhp_book_free_shipping_line' )
				&& 'FREE Shipping on the complete collection or 3 or more books purchased' === bhp_book_free_shipping_line(),
			'§7 with no flag, an ordinary shopper reads the locked shipping sentence',
			$failures
		);
	}
}

/* =====================================================================
 * §8 — ⛔ NOTHING WAS WRITTEN. Not one WooCommerce setting, not the registry.
 * ===================================================================== */
echo "\n--- §8 no stored setting and no registry row was written ---\n";

foreach ( $bhp_vfe_opts_before as $bhp_vfe_name => $bhp_vfe_val ) {
	bhp_vfe_assert(
		$bhp_vfe_val === bhp_vfe_raw_option( $bhp_vfe_name ),
		"§8 ⛔ {$bhp_vfe_name} is byte-identical to before this suite ran",
		$failures
	);
}

/* =====================================================================
 * §9 — CLEANUP. Session state only; there is nothing else to undo.
 * ===================================================================== */
echo "\n--- §9 cleanup ---\n";

remove_all_filters( 'bhp_school_visit_today' );
remove_all_filters( 'bhp_school_visit_now' );
remove_all_filters( 'bhp_school_visit_ttl_seconds' );
unset( $_GET[ BHP_SCHOOL_VISIT_PARAM ] );

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, null );
	bhp_vfe_assert(
		null === WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY )
			&& null === WC()->session->get( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY ),
		'§9 both visit session keys are cleared',
		$failures
	);
}

echo "\n=== RESULT ===\n";
if ( $skips ) {
	echo count( $skips ) . " SKIPPED:\n";
	foreach ( $skips as $bhp_vfe_s ) {
		echo "  - {$bhp_vfe_s}\n";
	}
}
if ( $failures ) {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $bhp_vfe_f ) {
		echo "  - {$bhp_vfe_f}\n";
	}
} else {
	echo "ALL ASSERTIONS PASSED\n";
}
