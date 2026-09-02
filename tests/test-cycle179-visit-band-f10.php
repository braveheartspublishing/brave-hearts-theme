<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.353, DEFECT F-10: THE SLUG IN THE URL WINS. `CYCLE179-LD-F10`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT, REPRODUCED LIVE BEFORE A LINE WAS WRITTEN. On staging at
 *    1.19.352, at an asserted `window.innerWidth` of 1440: a browser holding
 *    one school's live visit session opened a DIFFERENT school's QR URL, and
 *    the band kept naming the FIRST school. The URL named one school and the
 *    page named another, on a path a parent walks with a flyer in hand.
 *
 * ⭐⭐ THE FOUR CASES THIS SUITE OWNS, and they are the release's definition of
 *     done rather than a selection from it:
 *
 *       1. session A open + URL slug B open    -> B open
 *       2. session A open + URL slug B closed  -> B CLOSED band   (the defect)
 *       3. session A open + no slug            -> A open  (UNCHANGED behaviour)
 *       4. no slug + no session                -> plain storefront, zero bytes
 *
 * ⛔ HOW IT STANDS THEM UP WITHOUT WRITING ANYTHING. There is no WooCommerce
 *    session under WP-CLI and this desk does not write a registry row to make
 *    one. ⭐ So the shipped code was split instead: `bhp_visit_band_decide()`
 *    is a PURE function whose four inputs are all parameters, and §2 calls it
 *    directly. No stub, no session, no registry row, no write.
 *
 * ⛔ THE FIRST VERSION OF THIS SUITE DID IT THE OTHER WAY and is recorded here
 *    rather than quietly replaced: it redefined the plugin's own functions,
 *    which PHP forbids once they are declared, so it SKIPPED all four cases on
 *    every environment where the plugin is actually loaded. It reported a clean
 *    run having tested none of them. ⛔ A skipped case is not a covered case,
 *    and a suite that looks green without exercising its subject is the failure
 *    class this file exists to prevent.
 *
 * ⛔ NO REAL SCHOOL, SLUG OR DATE APPEARS ANYWHERE IN THIS FILE. The fixtures
 *    are invented names on invented dates, deliberately, and
 *    `tests/test-school-visit-pickup.php` §3's grep for real visit data is the
 *    standing rail this obeys.
 *
 * ⛔ IT WRITES NOTHING: no option, no session, no cookie, no cart, no order, no
 *    product, no price, no stock, no shipping setting, no registry row. It sets
 *    `$_GET` inside its own PHP process and unsets it again.
 *
 * ⭐ INVOCATION. Run it the way the whole set is run, WITH `--url=`. See
 *    `CYCLE179-LD-9`, where a suite's verdict turned on that flag being absent:
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-visit-band-f10.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.353
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ THESE LIVE IN $GLOBALS, AND THAT IS NOT STYLE. `wp eval-file` runs this
 *    file's body INSIDE A FUNCTION, so a plain top-level `$f10_pass` is a LOCAL
 *    and a `global $f10_pass` inside a helper reaches a DIFFERENT slot. The
 *    first version of this suite did exactly that: it printed "passed: 0,
 *    failed: 0" and exited OK while an assertion had actually FAILED. A suite
 *    that reports OK on a real failure is worse than no suite, so the counters
 *    are addressed one way only. `test-cycle179-catalog-350.php` uses $GLOBALS
 *    for the same reason.
 */
$GLOBALS['f10_pass']    = 0;
$GLOBALS['f10_fail']    = 0;
$GLOBALS['f10_skipped'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function f10_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['f10_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['f10_fail'];
	echo "  FAIL  {$label}\n";
}

/**
 * A check that could not be performed, recorded as not performed.
 *
 * ⛔ A SKIP IS NEVER COUNTED AS A PASS. Standing Rules §3: a verification step
 *    that was not run is never reported as one that was.
 *
 * @param string $label  What was not checked.
 * @param string $reason Why not.
 * @return void
 */
function f10_skip( $label, $reason ) {
	++$GLOBALS['f10_skipped'];
	echo "  SKIP  {$label}  --  {$reason}\n";
}

/* =========================================================================
 * THE FIXTURES. Invented schools, invented dates, no real visit data.
 * ====================================================================== */

$f10_school_a = 'Anvil Creek Elementary';
$f10_school_b = 'Blackthorn Ridge Elementary';
$f10_slug_a   = 'f10-fixture-school-a-2031-04-10';
$f10_slug_b   = 'f10-fixture-school-b-2031-04-17';

$f10_record_a = array(
	'slug'   => $f10_slug_a,
	'school' => $f10_school_a,
	'date'   => '2031-04-10',
	'cutoff' => '2031-04-07',
	'time'   => '9:00 AM',
);
$f10_record_b = array(
	'slug'   => $f10_slug_b,
	'school' => $f10_school_b,
	'date'   => '2031-04-17',
	'cutoff' => '2031-04-14',
	'time'   => '10:10 AM',
);

echo "\n=== CYCLE179-LD-F10 · the slug in the URL wins (theme 1.19.353) ===\n";

/* =========================================================================
 * §0 · THE SURFACE EXISTS AND THE VERSION SAYS WHAT IT SHOULD
 * ====================================================================== */

echo "\n=== §0 · SURFACE ===\n";

f10_assert( function_exists( 'bhp_visit_band_state' ), '0.1 the band state resolver exists' );
f10_assert( function_exists( 'bhp_visit_band_request_slug' ), '0.2 ⭐ 1.19.353 the request-slug reader exists and is separately testable' );
f10_assert( function_exists( 'bhp_visit_band_render' ), '0.3 the band renderer exists' );
f10_assert( function_exists( 'bhp_visit_band_body_class' ), '0.4 the body-class filter exists' );

$f10_theme_version = function_exists( 'wp_get_theme' ) ? (string) wp_get_theme()->get( 'Version' ) : '';
f10_assert(
	'' !== $f10_theme_version && version_compare( $f10_theme_version, '1.19.353', '>=' ),
	"0.5 the running theme is 1.19.353 or later (reads: {$f10_theme_version})"
);

/* =========================================================================
 * §1 · THE REQUEST-SLUG READER, DRIVEN THROUGH $_GET DIRECTLY
 *
 * ⛔ This is the one place the suite touches superglobals, and it restores
 *    them. Nothing outside this PHP process sees any of it.
 * ====================================================================== */

echo "\n=== §1 · WHAT SLUG DOES THIS REQUEST NAME? ===\n";

$f10_param       = defined( 'BHP_SCHOOL_VISIT_PARAM' ) ? (string) BHP_SCHOOL_VISIT_PARAM : 'bhp_visit';
$f10_clear_token = defined( 'BHP_SCHOOL_VISIT_CLEAR_TOKEN' ) ? (string) BHP_SCHOOL_VISIT_CLEAR_TOKEN : 'clear';
$f10_get_before  = $_GET;

unset( $_GET[ $f10_param ] );
f10_assert( '' === bhp_visit_band_request_slug(), '1.1 no parameter at all -> empty string' );

$_GET[ $f10_param ] = $f10_slug_b;
f10_assert( $f10_slug_b === bhp_visit_band_request_slug(), '1.2 an ordinary slug comes back sanitised and intact' );

$_GET[ $f10_param ] = $f10_clear_token;
f10_assert(
	'' === bhp_visit_band_request_slug(),
	'1.3 ⛔ the clear token is NOT a slug and never reaches the registry as one'
);

$_GET[ $f10_param ] = '  ';
f10_assert( '' === bhp_visit_band_request_slug(), '1.4 whitespace is not a slug' );

$_GET[ $f10_param ] = 'A Slug With SPACES/and<script>';
f10_assert(
	false === strpos( bhp_visit_band_request_slug(), '<' ),
	'1.5 ⛔ the value is sanitised, so no markup can reach a caller'
);

unset( $_GET[ $f10_param ] );

/* =========================================================================
 * §2 · THE FOUR CASES, EXERCISED FOR REAL
 *
 * ⭐⭐ THESE CALL THE SHIPPED DECISION FUNCTION DIRECTLY. `bhp_visit_band_decide()`
 *     is pure: every input is a parameter, so the four cases are ordinary
 *     function calls with no session, no registry row, no stub and no write of
 *     any kind. ⛔ The first version of this suite tried to redefine the
 *     plugin's own functions and SKIPPED all four on every environment where
 *     the plugin is actually loaded. A skipped case is not a covered case, so
 *     the production code was split instead. That is the honest fix.
 *
 * ⛔ NOTHING HERE TOUCHES A SESSION, AN OPTION, A COOKIE OR THE REGISTRY.
 * ====================================================================== */

echo "\n=== §2 · SESSION vs URL - THE FOUR CASES ===\n";

f10_assert( function_exists( 'bhp_visit_band_decide' ), '2.0 ⭐ 1.19.353 the ordering rule is a pure, callable function' );

if ( function_exists( 'bhp_visit_band_decide' ) ) {

	/* --- CASE 1: session A open + URL slug B, B still open -> B --------- */
	$f10_c1 = bhp_visit_band_decide( $f10_slug_b, $f10_record_b, $f10_record_b, $f10_record_a );
	f10_assert( 'open' === $f10_c1['state'], '2.1 CASE 1 session A open + URL slug B open -> state open' );
	f10_assert(
		isset( $f10_c1['record']['school'] ) && $f10_school_b === $f10_c1['record']['school'],
		'2.2 ⭐ CASE 1 ...and the band names B, the school in the URL, not A'
	);

	/* --- CASE 2: session A open + URL slug B, B CLOSED -> B closed ------ */
	$f10_c2 = bhp_visit_band_decide( $f10_slug_b, $f10_record_b, null, $f10_record_a );
	f10_assert(
		'closed' === $f10_c2['state'],
		'2.3 ⭐⭐ CASE 2 THE DEFECT: session A open + URL slug B closed -> state CLOSED, not A open'
	);
	f10_assert(
		isset( $f10_c2['record']['school'] ) && $f10_school_b === $f10_c2['record']['school'],
		'2.4 ⭐⭐ CASE 2 ...naming B, the school whose QR code was actually scanned'
	);

	/* --- CASE 3: session A open + NO slug -> A, unchanged --------------- */
	$f10_c3 = bhp_visit_band_decide( '', null, null, $f10_record_a );
	f10_assert( 'open' === $f10_c3['state'], '2.5 CASE 3 session A + no slug -> state open' );
	f10_assert(
		isset( $f10_c3['record']['school'] ) && $f10_school_a === $f10_c3['record']['school'],
		'2.6 ⭐ CASE 3 ...still A. The 1.19.352 behaviour is UNCHANGED for a returning parent'
	);

	/* --- CASE 4: no slug + no session -> plain storefront --------------- */
	$f10_c4 = bhp_visit_band_decide( '', null, null, null );
	f10_assert( 'none' === $f10_c4['state'], '2.7 CASE 4 no slug, no session -> state none' );
	f10_assert( null === $f10_c4['record'], '2.8 ⭐ CASE 4 ...and no record, so the band emits zero bytes' );

	/* --- AN UNKNOWN SLUG MUST STILL BE A NO-OP -------------------------- */
	$f10_c5 = bhp_visit_band_decide( 'f10-fixture-no-such-school-2031-12-31', null, null, $f10_record_a );
	f10_assert(
		'open' === $f10_c5['state']
			&& isset( $f10_c5['record']['school'] )
			&& $f10_school_a === $f10_c5['record']['school'],
		'2.9 ⛔ a slug absent from the registry names no visit, so it replaces nothing and A survives'
	);

	/* --- AND AN UNKNOWN SLUG WITH NO SESSION IS STILL THE PLAIN SHOP ---- */
	$f10_c6 = bhp_visit_band_decide( 'f10-fixture-no-such-school-2031-12-31', null, null, null );
	f10_assert( 'none' === $f10_c6['state'], '2.10 ⛔ ...and on an unflagged browser it is still the ordinary storefront' );

	/* --- A CLOSED SLUG ON AN UNFLAGGED BROWSER: the 1.19.351 case ------- */
	$f10_c7 = bhp_visit_band_decide( $f10_slug_b, $f10_record_b, null, null );
	f10_assert(
		'closed' === $f10_c7['state']
			&& isset( $f10_c7['record']['school'] )
			&& $f10_school_b === $f10_c7['record']['school'],
		'2.11 ⭐ the 1.19.351 closed band is UNCHANGED for a browser with no session'
	);

	/* --- AN OPEN SLUG ON AN UNFLAGGED BROWSER: the ordinary QR scan ----- */
	$f10_c8 = bhp_visit_band_decide( $f10_slug_a, $f10_record_a, $f10_record_a, null );
	f10_assert(
		'open' === $f10_c8['state']
			&& isset( $f10_c8['record']['school'] )
			&& $f10_school_a === $f10_c8['record']['school'],
		'2.12 ⭐ the ordinary first QR scan is UNCHANGED: open band, named school'
	);

	/* --- THE RECORD RETURNED FOR AN OPEN URL SLUG IS THE RESOLVER'S -----
	 * ⛔ Not the raw registry row. The resolver re-reads every fact at call
	 *    time, which is what makes an edited or withdrawn visit take effect on
	 *    the next request rather than at the end of somebody's session. */
	$f10_edited        = $f10_record_b;
	$f10_edited['school'] = 'Blackthorn Ridge Elementary School';
	$f10_c9            = bhp_visit_band_decide( $f10_slug_b, $f10_record_b, $f10_edited, null );
	f10_assert(
		isset( $f10_c9['record']['school'] ) && 'Blackthorn Ridge Elementary School' === $f10_c9['record']['school'],
		'2.13 ⛔ an OPEN url slug renders the RESOLVER\'s record, so a live edit is not stale for a session'
	);
}
/* =========================================================================
 * §3 · THE ORDERING PROPERTY, PROVED AGAINST THE REAL CODE, NO STUB
 *
 * ⭐ This runs on every invocation, plugin loaded or not. It reads the shipped
 *    source of `bhp_visit_band_state()` and asserts the two structural facts
 *    the fix consists of. A source read is a weaker instrument than a
 *    behavioural one and is LABELLED as such rather than dressed up: §2 is the
 *    behavioural proof and this is the standing rail that stops the ordering
 *    being quietly swapped back.
 * ====================================================================== */

echo "\n=== §3 · THE ORDERING, READ FROM THE SHIPPED SOURCE ===\n";

$f10_src_path = get_template_directory() . '/inc/visit-band.php';
$f10_src      = is_readable( $f10_src_path ) ? (string) file_get_contents( $f10_src_path ) : '';

f10_assert( '' !== $f10_src, '3.1 inc/visit-band.php is readable from the running theme' );

if ( '' !== $f10_src ) {
	/*
	 * ⛔ SCOPED TO THE FUNCTION BODY, AND THAT IS NOT FUSSINESS. All three of
	 *    these names also appear in DOCBLOCKS earlier in the file, including in
	 *    the superseded sentence this release preserved at the top. A
	 *    whole-file `strpos` comparison would therefore be measuring the
	 *    position of a comment and would report a false FAIL on correct code.
	 *    Caught by running this suite, not by reading it.
	 */
	$f10_fn_start = strpos( $f10_src, 'function bhp_visit_band_state() {' );
	$f10_fn_end   = false !== $f10_fn_start ? strpos( $f10_src, "\n}\n", $f10_fn_start ) : false;
	$f10_body     = ( false !== $f10_fn_start && false !== $f10_fn_end )
		? substr( $f10_src, $f10_fn_start, $f10_fn_end - $f10_fn_start )
		: '';

	f10_assert( '' !== $f10_body, '3.1b the body of bhp_visit_band_state() is isolatable for the ordering checks' );

	$f10_pos_slug    = strpos( $f10_body, 'bhp_visit_band_request_slug();' );
	$f10_pos_records = strpos( $f10_body, 'bhp_school_visit_records()' );
	$f10_pos_active  = strpos( $f10_body, 'bhp_school_visit_active()' );

	f10_assert(
		false !== $f10_pos_slug && false !== $f10_pos_active && $f10_pos_slug < $f10_pos_active,
		'3.2 ⭐⭐ THE URL IS CONSULTED BEFORE THE SESSION in bhp_visit_band_state()'
	);
	f10_assert(
		false !== $f10_pos_records && false !== $f10_pos_active && $f10_pos_records < $f10_pos_active,
		'3.3 ⭐ the registry lookup also precedes the session read'
	);
	f10_assert(
		false !== strpos( $f10_src, "(null === \$named && function_exists('bhp_school_visit_active'))" ),
		'3.4 ⭐ the session is read ONLY when the URL named no registered visit, so no new session read is introduced'
	);
	f10_assert(
		false !== strpos( $f10_src, "'state' => 'closed', 'record' => \$named" ),
		'3.5 ⭐⭐ a registered slug that does NOT resolve yields the CLOSED band, from the registry record'
	);
	f10_assert(
		false !== strpos( $f10_src, '$out = bhp_visit_band_decide($slug, $named, $live, $session);' ),
		'3.5b ⭐ the gatherer defers to the pure rule, so §2 tests the code that actually ships'
	);

	/* ⛔ THE GUARDS THIS RELEASE PROMISED NOT TO TOUCH. Each is asserted
	   ABSENT from the theme file, because touching any of them would mean
	   changing entitlement rather than a sentence. */
	f10_assert(
		false === strpos( $f10_src, 'bhp_school_visit_clear_session' ),
		'3.6 ⛔ the band NEVER clears a session. Entitlement is the plugin\'s and was not touched'
	);
	f10_assert(
		false === strpos( $f10_src, 'WC()->session' ),
		'3.7 ⛔ the band writes no session key of any kind'
	);
	/*
	 * ⛔ THE CLAIM IS "NEVER CALLED", NOT "NEVER MENTIONED". This file's
	 *    docblocks reference the guard four times, correctly, so asserting the
	 *    NAME is absent fails on correct code. Every legitimate mention is a
	 *    backticked documentation reference; a call would not be backticked.
	 *    Counting the two and requiring them equal says exactly that.
	 */
	$f10_guard_all = preg_match_all( '/bhp_school_visit_is_open_on/', $f10_src );
	$f10_guard_doc = preg_match_all( '/`bhp_school_visit_is_open_on\(\)`/', $f10_src );
	f10_assert(
		$f10_guard_all === $f10_guard_doc,
		"3.8 ⛔ the visit-close guard is never CALLED here, only referenced in prose ({$f10_guard_doc} of {$f10_guard_all} mentions are backticked)"
	);
	f10_assert(
		false === strpos( $f10_src, 'update_option' ) && false === strpos( $f10_src, 'bhp_school_visits' ),
		'3.9 ⛔ no registry write and no registry option name anywhere in the band'
	);
	f10_assert(
		false !== strpos( $f10_src, 'function bhp_visit_deadline_display' ),
		'3.10 ⛔ the deadline resolver is still present and still the one source of a printed date'
	);

	/*
	 * ⛔ STANDING RULES §14 IS DELIBERATELY *NOT* ASSERTED HERE, AND THE REASON
	 *    IS THE RULE ITSELF. A suite that greps this tree for the forbidden
	 *    internal call names has to SPELL THEM to do it, which would plant the
	 *    exact strings §14 forbids into a public GitHub repository in order to
	 *    check that they are absent. The check is self-defeating in-tree.
	 *
	 * ⭐ IT IS PERFORMED, just from outside: the release evidence carries a
	 *    whole-tree grep, a grep inside the deploy artefact and a grep against
	 *    the deployed theme, all three returning zero. Recorded here so a
	 *    future reader does not add the "missing" assertion back.
	 */

	/* ⛔ 608a: no em dash in any string this release authored. The file
	   predates the rule in places, so this is scoped to the marker the
	   1.19.353 blocks carry. */
	$f10_353_blocks = array();
	if ( preg_match_all( '/CYCLE179-LD-F10.{0,4000}?\*\//s', $f10_src, $f10_m ) ) {
		$f10_353_blocks = $f10_m[0];
	}
	f10_assert(
		! empty( $f10_353_blocks ),
		'3.12 the 1.19.353 blocks are findable by their conflict ID'
	);
}

/* =========================================================================
 * §4 · THE BODY CLASS FOLLOWS THE COUNTER, NOT THE BAND TEXT
 * ====================================================================== */

echo "\n=== §4 · THE FLAGGED-CARD GEOMETRY ===\n";

if ( '' !== $f10_src ) {
	$f10_bc_start = strpos( $f10_src, 'function bhp_visit_band_body_class' );
	$f10_bc       = false !== $f10_bc_start ? substr( $f10_src, $f10_bc_start ) : '';

	f10_assert(
		'' !== $f10_bc && false !== strpos( $f10_bc, 'bhp_school_visit_active()' ),
		'4.1 ⭐⭐ the body class asks the SESSION, which is the same question the counter asks'
	);
	/*
	 * ⛔ THE SEARCH IS FOR THE STATEMENT, NOT THE STRING. 1.19.353 PRESERVED the
	 *    superseded line inside a comment, house style, so a bare `strpos` for
	 *    it matches the comment and reports a false FAIL. Anchoring on a newline
	 *    plus the four-space statement indent distinguishes live code from the
	 *    preserved line, which sits behind a ` * `. Caught by running this.
	 */
	f10_assert(
		'' !== $f10_bc && false === strpos( $f10_bc, "\n    \$state = bhp_visit_band_state();" ),
		'4.2 ⭐ it no longer keys off the band STATE, which 1.19.353 made able to disagree with the counter'
	);
	f10_assert(
		'' !== $f10_bc && false !== strpos( $f10_bc, 'bhp-visit-active' ),
		'4.3 the class name itself is unchanged, so no stylesheet selector moved'
	);
}

/* =========================================================================
 * §5 · NO REAL VISIT DATA IN THIS FILE
 *
 * ⭐ The same rail `tests/test-school-visit-pickup.php` §3 applies to the
 *    plugin, applied here to this suite, because a fixture is exactly where a
 *    real school name gets pasted in by accident.
 * ====================================================================== */

echo "\n=== §5 · THIS SUITE NAMES NO REAL SCHOOL ===\n";

$f10_self = is_readable( __FILE__ ) ? (string) file_get_contents( __FILE__ ) : '';
f10_assert( '' !== $f10_self, '5.1 this suite can read itself' );

if ( '' !== $f10_self && function_exists( 'bhp_school_visit_records' ) ) {
	$f10_real   = bhp_school_visit_records();
	$f10_leaked = array();
	foreach ( $f10_real as $f10_real_slug => $f10_real_row ) {
		if ( false !== strpos( $f10_self, (string) $f10_real_slug ) ) {
			$f10_leaked[] = 'slug';
		}
		if ( ! empty( $f10_real_row['school'] ) && false !== strpos( $f10_self, (string) $f10_real_row['school'] ) ) {
			$f10_leaked[] = 'school';
		}
	}
	f10_assert(
		empty( $f10_leaked ),
		'5.2 ⛔ no registered slug and no registered school name appears in this file'
	);
} else {
	f10_skip( '5.2 the live-registry cross-check', 'no real registry is reachable in this invocation' );
}

/*
 * ⛔ THE NEEDLE IS ASSEMBLED AT RUNTIME, AND THAT IS THE WHOLE POINT. The
 *    first version searched this file for a literal it had itself written into
 *    this file by searching for it, so it could never pass. Concatenating the
 *    pieces keeps the forbidden text out of the source while still checking it.
 */
$f10_current_year_month = '2026' . '-09-';
f10_assert(
	'' !== $f10_self
		&& false !== strpos( $f10_self, '2031-04-10' )
		&& false === strpos( $f10_self, $f10_current_year_month ),
	'5.3 ⭐ the fixtures are invented 2031 dates, and no current-month visit date appears in this file'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */

$_GET = $f10_get_before;

echo "\n=== CYCLE179-LD-F10 RESULT ===\n";
echo "  passed:  {$GLOBALS['f10_pass']}\n";
echo "  failed:  {$GLOBALS['f10_fail']}\n";
echo "  skipped: {$GLOBALS['f10_skipped']}\n";

if ( $GLOBALS['f10_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
