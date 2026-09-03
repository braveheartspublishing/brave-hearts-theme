<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ PLUGIN 1.8.80, `CYCLE179-LD-10`: THE URL'S SCHOOL OWNS THE SESSION TOO.
 *      Closed by `CYCLE179-LD-355`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT, OBSERVED LIVE ON STAGING 1.19.353 AT AN ASSERTED 1440 AND
 *    REGISTERED AS `CYCLE179-LD-10` BEFORE A LINE OF THIS FIX WAS WRITTEN: with
 *    one school's session still held, an explicit URL for a DIFFERENT,
 *    already-closed school rendered the band as the URL's school while the
 *    per-card counters still counted the SESSION school's shelf, and
 *    `bhp-visit-active` stayed on the body. One page, two schools.
 *
 * ⭐ 1.19.353 fixed the BAND, which is display and is the theme's. THIS release
 *    fixes the SESSION, which is entitlement and is the plugin's.
 *
 * ⭐⭐ THE FOUR CASES THE BRIEF NAMES, AND THEY ARE THE DEFINITION OF DONE
 *     RATHER THAN A SELECTION FROM IT:
 *
 *       1. session A + URL slug B OPEN    -> the session becomes B    ('set')
 *       2. session A + URL slug B CLOSED  -> the session is dropped   ('clear')
 *       3. no slug, session A             -> A is untouched          ('ignore')
 *       4. no slug, no session            -> nothing happens         ('ignore')
 *
 * ⛔ AND THE CASE THAT MUST *NOT* MOVE, which is why §3 exists: a slug that is
 *    absent from the registry is STILL a no-op, so a truncated or mistyped URL
 *    cannot strip hand delivery from an entitled parent. That is the
 *    `commerce-cx` decision of 2026-08-19, and Andrew's word of 2026-09-02
 *    supersedes it ONLY for a slug that names a REGISTERED visit.
 *
 * ⛔ HOW IT STANDS THE CASES UP WITHOUT WRITING ANYTHING. There is no
 *    WooCommerce session under WP-CLI, and faking one would mean writing a
 *    session or a registry row, which is a DATA change this desk does not make.
 *    So the shipped code was split: `bhp_school_visit_capture_decide()` is a
 *    PURE function whose four inputs are all parameters, and §2 calls it
 *    directly. No stub, no session, no registry row, no write. The same shape,
 *    for the same reason, as `bhp_visit_band_decide()` and
 *    `tests/test-cycle179-visit-band-f10.php`.
 *
 * ⛔ NO REAL SCHOOL, SLUG OR DATE APPEARS ANYWHERE IN THIS FILE. The fixtures
 *    are invented names on invented dates, and §5 asserts that.
 *
 * ⛔ IT WRITES NOTHING: no option, no session, no cookie, no cart, no order, no
 *    product, no price, no stock, no shipping setting, no registry row.
 *
 * ⭐ INVOCATION, WITH `--url=` (`CYCLE179-LD-9`):
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-visit-capture-355.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.355
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, NOT LOCALS. `wp eval-file` runs this file's body INSIDE A
 *    FUNCTION, so a plain top-level counter is a LOCAL and a `global` inside a
 *    helper reaches a DIFFERENT slot. A suite that did that printed
 *    "passed: 0, failed: 0" and exited OK while an assertion had failed.
 */
$GLOBALS['vc355_pass']    = 0;
$GLOBALS['vc355_fail']    = 0;
$GLOBALS['vc355_skipped'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function vc355_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['vc355_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['vc355_fail'];
	echo "  FAIL  {$label}\n";
}

/**
 * A check that could not be performed, recorded as not performed.
 *
 * ⛔ A SKIP IS NEVER COUNTED AS A PASS.
 *
 * @param string $label  What was not checked.
 * @param string $reason Why not.
 * @return void
 */
function vc355_skip( $label, $reason ) {
	++$GLOBALS['vc355_skipped'];
	echo "  SKIP  {$label}  --  {$reason}\n";
}

/* =========================================================================
 * THE FIXTURES. Invented schools, invented dates, no real visit data.
 * ====================================================================== */

$vc355_slug_a = 'vc355-fixture-school-a-2032-05-11';
$vc355_slug_b = 'vc355-fixture-school-b-2032-05-18';
$vc355_bogus  = 'vc355-fixture-not-in-the-registry';

echo "\n=== CYCLE179-LD-355 · the URL's school owns the session (plugin 1.8.80) ===\n";

/* =========================================================================
 * §0 · SURFACE
 * ====================================================================== */

echo "\n=== §0 · SURFACE ===\n";

vc355_assert( function_exists( 'bhp_school_visit_capture_decide' ), '0.1 ⭐ 1.8.80 the pure decision function exists and is separately testable' );
vc355_assert( function_exists( 'bhp_school_visit_capture_intent' ), '0.2 the capture hook still exists' );
vc355_assert( function_exists( 'bhp_school_visit_clear_session' ), '0.3 the session clear still exists' );
vc355_assert( function_exists( 'bhp_school_visit_active' ), '0.4 the live-visit resolver still exists' );
vc355_assert( function_exists( 'bhp_school_visit_resolve' ), '0.5 the entitlement gate still exists' );
vc355_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.80', '>=' ),
	'0.6 the loaded plugin is at least 1.8.80 (' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ')'
);

if ( ! function_exists( 'bhp_school_visit_capture_decide' ) ) {
	echo "\n  The decision function is absent. Every case below would be a SKIP,\n";
	echo "  and a suite that skips its whole subject must not exit OK.\n";
	echo "\nFAILED\n";
	exit( 1 );
}

/* =========================================================================
 * §1 · THE FOUR CASES THE BRIEF NAMES
 * ====================================================================== */

echo "\n=== §1 · THE FOUR BRIEF CASES ===\n";

vc355_assert(
	'set' === bhp_school_visit_capture_decide( $vc355_slug_b, true, true, $vc355_slug_a ),
	'1.1 session A + URL slug B OPEN -> the session becomes B'
);
vc355_assert(
	'clear' === bhp_school_visit_capture_decide( $vc355_slug_b, true, false, $vc355_slug_a ),
	'1.2 ⭐⭐ session A + URL slug B CLOSED -> the session is CLEARED (this is CYCLE179-LD-10)'
);
vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( '', false, false, $vc355_slug_a ),
	'1.3 no slug + session A -> A is untouched'
);
vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( '', false, false, '' ),
	'1.4 no slug + no session -> nothing happens'
);

/* =========================================================================
 * §2 · THE EDGES AROUND THOSE FOUR
 * ====================================================================== */

echo "\n=== §2 · EDGES ===\n";

vc355_assert(
	'set' === bhp_school_visit_capture_decide( $vc355_slug_a, true, true, '' ),
	'2.1 an OPEN slug with no session still sets one (the ordinary flyer QR scan)'
);
vc355_assert(
	'set' === bhp_school_visit_capture_decide( $vc355_slug_a, true, true, $vc355_slug_a ),
	'2.2 an OPEN slug matching the held session re-arms it rather than clearing it'
);
vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( $vc355_slug_b, true, false, '' ),
	'2.3 a CLOSED registered slug with NO session is a no-op (nothing to clear, and no rate cache is bumped)'
);
vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( $vc355_slug_b, true, false, $vc355_slug_b ),
	'2.4 ⛔ a CLOSED slug matching the held session is left to bhp_school_visit_active() guard 2, not double-cleared here'
);

/* =========================================================================
 * §3 · THE 2026-08-19 PROTECTION THAT MUST NOT MOVE
 *
 * ⛔ This is the half of the old decision Andrew's word did NOT supersede, and
 *    it is the half that protects a real parent from a bad URL.
 * ====================================================================== */

echo "\n=== §3 · THE UNREGISTERED SLUG IS STILL A NO-OP ===\n";

vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( $vc355_bogus, false, false, $vc355_slug_a ),
	'3.1 ⛔ a slug ABSENT from the registry does NOT clear a held session (commerce-cx 2026-08-19, intact)'
);
vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( $vc355_bogus, false, false, '' ),
	'3.2 an unregistered slug with no session does nothing'
);
vc355_assert(
	'ignore' === bhp_school_visit_capture_decide( 'vc355-fixture-school-a-2032-05-1', false, false, $vc355_slug_a ),
	'3.3 ⛔ a TRUNCATED slug is unregistered, therefore a no-op: the exact case the 2026-08-19 decision exists for'
);

/* =========================================================================
 * §4 · THE EXPLICIT CLEAR TOKEN
 * ====================================================================== */

echo "\n=== §4 · THE CLEAR TOKEN ===\n";

if ( ! defined( 'BHP_SCHOOL_VISIT_CLEAR_TOKEN' ) ) {
	vc355_skip( '4.1 the clear token', 'BHP_SCHOOL_VISIT_CLEAR_TOKEN is not defined in this process' );
} else {
	vc355_assert(
		'clear' === bhp_school_visit_capture_decide( BHP_SCHOOL_VISIT_CLEAR_TOKEN, false, false, $vc355_slug_a ),
		'4.1 the clear token clears, whatever the registry says'
	);
	vc355_assert(
		'clear' === bhp_school_visit_capture_decide( BHP_SCHOOL_VISIT_CLEAR_TOKEN, false, false, '' ),
		'4.2 the clear token on an unflagged browser is still safe to hit'
	);
}

/* =========================================================================
 * §5 · SOURCE RAILS — what the shipped file must and must not contain
 * ====================================================================== */

echo "\n=== §5 · SOURCE RAILS ===\n";

$vc355_plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : '';
$vc355_pickup     = $vc355_plugin_dir . '/brave-hearts-bundle-pricing/includes/school-visit-pickup.php';
$vc355_src        = ( '' !== $vc355_plugin_dir && file_exists( $vc355_pickup ) ) ? (string) file_get_contents( $vc355_pickup ) : '';

if ( '' === $vc355_src ) {
	vc355_skip( '5.x the source rails', 'school-visit-pickup.php could not be read from ' . $vc355_pickup );
} else {
	/*
	 * ⛔ THE EARLY CLEAR RETURN IS THE INVARIANT "`clear` never reaches
	 *    resolve()". The decision function ALSO returns 'clear' for the token,
	 *    which makes the early return look redundant. It is not: the decision
	 *    function is only reached after two registry reads. Deleting the early
	 *    return would send the literal token through `bhp_school_visit_records()`
	 *    and `bhp_school_visit_resolve()`, which this file has forbidden since
	 *    1.8.59. This assertion is what stops a future tidy-up doing it.
	 */
	$vc355_early = strpos( $vc355_src, 'if ( BHP_SCHOOL_VISIT_CLEAR_TOKEN === $slug ) {' );
	$vc355_reads = strpos( $vc355_src, '$bhp_records       = bhp_school_visit_records();' );
	vc355_assert(
		false !== $vc355_early && false !== $vc355_reads && $vc355_early < $vc355_reads,
		'5.1 ⛔ the explicit clear return still precedes the registry reads inside capture_intent()'
	);

	// The superseded decision is preserved in place, never deleted.
	vc355_assert(
		false !== strpos( $vc355_src, '2026-08-19' ),
		'5.2 the superseded commerce-cx decision of 2026-08-19 is still recorded in the file'
	);
	vc355_assert(
		false !== strpos( $vc355_src, 'truncated or mistyped URL' ),
		'5.3 its reasoning is preserved verbatim rather than paraphrased away'
	);

	// The relayed founder word is recorded AS relayed (Standing Rules 9.2).
	vc355_assert(
		false !== strpos( $vc355_src, 'RELAYED, NOT WITNESSED' ),
		'5.4 ⭐ the founder word carried by the brief is labelled RELAYED, not witnessed'
	);

	/*
	 * ⛔ THE GUARDS THE BRIEF SAYS MUST NOT MOVE. Each is asserted by the
	 *    presence of its own function, not by a comment claiming it survived.
	 */
	vc355_assert( function_exists( 'bhp_school_visit_ttl_expired' ), '5.5 the hard TTL guard is still present' );
	vc355_assert( function_exists( 'bhp_school_visit_is_open_on' ), '5.6 the visit-close guard is still present' );
	vc355_assert( function_exists( 'bhp_school_visit_last_order_date' ), '5.7 the deadline resolver is still present' );
	vc355_assert( function_exists( 'bhp_school_visit_paperback_only' ), '5.8 the paperback-only gate is still present' );
	/*
	 * ⚠️ THE `?bhp_shiphome=` PATH IS THE THEME'S, NOT THIS FILE'S, AND THE
	 *    FIRST DRAFT OF THIS ASSERTION LOOKED FOR IT HERE AND WOULD HAVE
	 *    FAILED. It lives in `inc/colouring-line.php` behind
	 *    `BHP_COLOURING_SHIPHOME_PARAM`. Found by running the suite, not by
	 *    reading it. It is asserted where it actually is.
	 */
	$vc355_colouring = get_template_directory() . '/inc/colouring-line.php';
	if ( ! file_exists( $vc355_colouring ) ) {
		vc355_skip( '5.9 the ?bhp_shiphome= confirmation path', 'inc/colouring-line.php could not be read' );
	} else {
		$vc355_col_src = (string) file_get_contents( $vc355_colouring );
		vc355_assert(
			false !== strpos( $vc355_col_src, 'BHP_COLOURING_SHIPHOME_PARAM' )
				&& false !== strpos( $vc355_col_src, 'bhp_shiphome' ),
			'5.9 the ?bhp_shiphome= confirmation path is untouched and still present in the theme'
		);
	}

	/*
	 * ⛔ THE DECISION FUNCTION READS NOTHING. It is the one property that makes
	 *    §1 to §4 honest: if it consulted a superglobal, a session or the
	 *    registry, the cases above would be testing a stub of themselves.
	 */
	/*
	 * ⚠️ THE SOURCE IS NORMALISED TO \n FIRST, AND THE FIRST RUN OF THIS SUITE
	 *    IS WHY. `school-visit-pickup.php` IS A CRLF FILE (`file` reports
	 *    "with CRLF line terminators"), so the boundary search below, written
	 *    as "\n}\n", matched nothing and this assertion reported SKIP rather
	 *    than PASS or FAIL. ⛔ A SKIP IS NOT A PASS, so the run correctly
	 *    refused to claim the check had happened; the fix is to look for the
	 *    brace the file actually contains.
	 */
	$vc355_lf         = str_replace( "\r\n", "\n", $vc355_src );
	$vc355_decide_at  = strpos( $vc355_lf, 'function bhp_school_visit_capture_decide(' );
	$vc355_decide_end = ( false !== $vc355_decide_at ) ? strpos( $vc355_lf, "\n}\n", $vc355_decide_at ) : false;
	if ( false === $vc355_decide_at || false === $vc355_decide_end ) {
		vc355_skip( '5.10 the decision function body', 'its boundaries could not be located in the source' );
	} else {
		$vc355_body = substr( $vc355_lf, $vc355_decide_at, $vc355_decide_end - $vc355_decide_at );
		$vc355_body = preg_replace( '#/\*.*?\*/#s', '', $vc355_body ); // Strip its own prose first.
		$vc355_body = preg_replace( '#//.*#', '', $vc355_body );

		$vc355_forbidden = array( '$_GET', '$_POST', '$_COOKIE', 'WC()', 'get_option', 'update_option', 'bhp_school_visit_records', 'bhp_school_visit_resolve', 'time(' );
		$vc355_hits      = array();
		foreach ( $vc355_forbidden as $vc355_needle ) {
			if ( false !== strpos( $vc355_body, $vc355_needle ) ) {
				$vc355_hits[] = $vc355_needle;
			}
		}
		vc355_assert(
			empty( $vc355_hits ),
			'5.10 ⭐ the decision function reads no superglobal, session, option, registry or clock'
				. ( empty( $vc355_hits ) ? '' : ' -- found: ' . implode( ', ', $vc355_hits ) )
		);
	}

	/*
	 * ⛔ IT MUST NEVER WRITE THE REGISTRY. Clearing a session is not a data
	 *    change; writing `bhp_school_visits` would be, and that option is
	 *    Andrew's.
	 */
	vc355_assert(
		false === strpos( $vc355_src, "update_option( 'bhp_school_visits'" )
			&& false === strpos( $vc355_src, 'update_option( BHP_SCHOOL_VISITS' ),
		'5.11 ⛔ nothing in this file writes the bhp_school_visits registry option'
	);
}

/* =========================================================================
 * §6 · THE FIXTURES ARE INVENTED
 * ====================================================================== */

echo "\n=== §6 · NO REAL VISIT DATA IN THIS FILE ===\n";

/*
 * ⚠️ THE FIRST DRAFT OF THIS SECTION ASSERTED "no `2026-09-` anywhere in this
 *    file" and WOULD HAVE FAILED ON ITS OWN PROSE, because the founder ruling
 *    this release implements is dated in that month and is quoted in the header
 *    above. Caught by reading the assertion against the file it asserts on.
 * ⭐ THE PRECISE TEST IS THE ONE THAT WAS WANTED ALL ALONG: no REAL school
 *    slug, from the live registry, appears in a test fixture. A ruling date in
 *    a comment is provenance, not visit data.
 */
/*
 * ⚠️ AND THE NEEDLES ARE ASSEMBLED FROM FRAGMENTS, BECAUSE THE FIRST RUN OF
 *    THIS ASSERTION FAILED AGAINST ITS OWN SOURCE: written as plain literals,
 *    the three real slug prefixes were IN this file, so the file it was
 *    checking for leaks was the leak it found. Caught by running the suite, not
 *    by reading it. `CYCLE179-LD-354` hit the identical trap with a different
 *    literal and fixed it the same way.
 */
$vc355_self = (string) @file_get_contents( __FILE__ );
$vc355_real = array( 'lib' . 'erty-', 'dallas' . '-harris-', 'ad' . 'ams-' );
$vc355_leak = array();
foreach ( $vc355_real as $vc355_needle ) {
	if ( '' !== $vc355_self && false !== strpos( $vc355_self, $vc355_needle ) ) {
		$vc355_leak[] = $vc355_needle;
	}
}
vc355_assert(
	'' !== $vc355_self && empty( $vc355_leak ),
	'6.1 ⭐ no real school-visit slug appears in this file'
		. ( empty( $vc355_leak ) ? '' : ' -- found: ' . implode( ', ', $vc355_leak ) )
);
vc355_assert(
	'' !== $vc355_self && false !== strpos( $vc355_self, '2032-05-11' ),
	'6.2 the fixtures it does use are invented 2032 dates'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */

echo "\n=== CYCLE179-LD-355 CAPTURE RESULT ===\n";
echo "  passed:  {$GLOBALS['vc355_pass']}\n";
echo "  failed:  {$GLOBALS['vc355_fail']}\n";
echo "  skipped: {$GLOBALS['vc355_skipped']}\n";

if ( $GLOBALS['vc355_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
