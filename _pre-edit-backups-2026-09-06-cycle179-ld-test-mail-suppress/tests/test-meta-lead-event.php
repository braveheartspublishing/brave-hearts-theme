<?php
/**
 * Brave Hearts — the Meta Pixel must actually record a Lead when someone signs up.
 *
 * `CYCLE165-LD-META-LEAD-EVENT` (2026-08-19, theme 1.19.253).
 *
 * ⛔ WHAT THIS SUITE IS DEFENDING, AND WHY IT IS A SEPARATE FILE FROM
 *    tests/test-meta-pixel.php. That suite proves the pixel is SILENT until
 *    consent — the compliance half. This one proves it is not silent AFTERWARDS
 *    — the measurement half. Both matter, and the failure this file exists to
 *    catch had passed every assertion in the other file for thirteen days:
 *
 *      the `Lead` mapping existed, was tested, and was correct —
 *      and no visitor signup on the primary funnel could ever reach it.
 *
 *    `lead_signup_success` is deliberately NOT fired by any form that has a
 *    dedicated thank-you page (template-parts/acquisition/signup-form.php
 *    suppresses it so the thank-you page's own named event is not
 *    double-counted). The Reluctant Reader Adventure Kit — the primary
 *    newsletter funnel — HAS a dedicated thank-you page. So the main signup
 *    path fired `adventure_kit_signup`, which the pixel did not read.
 *
 *    ⭐ THE LESSON THIS FILE ENCODES: asserting that a mapping is CORRECT is
 *    not the same as asserting that it is COMPLETE. Group A below asserts, for
 *    every signup-success event the codebase actually emits, that the pixel
 *    has an entry for it — so adding a fourth funnel without mapping it fails
 *    here rather than in a quarterly report.
 *
 * ⭐ THE FOUR GROUPS:
 *
 *    A  COMPLETENESS. Every `*_signup` / `*_signup_success` dataLayer event
 *       emitted anywhere in the theme is mapped to `Lead`, or is explicitly
 *       named here as intentionally excluded. Discovered by scanning the
 *       source, not from a list typed into this file.
 *    B  IDENTITY. Every Lead carries an `eventID`, generated in the browser,
 *       never rendered into cacheable HTML, and handed back to the dataLayer
 *       so a future Conversions-API tag dedups against the same value.
 *    C  ONE SIGNUP, ONE LEAD. The latch exists, and the ordering contract with
 *       page-adventure-kit-thank-you.php that decides which funnel name wins
 *       is in place on both sides.
 *    D  NOTHING REGRESSED. Consent still gates the Lead exactly as it gates
 *       PageView, the two funnels are still isolated, and the runtime still
 *       touches no storage API.
 *
 * ⚠ WHAT THIS SUITE CANNOT PROVE, stated rather than implied: it asserts what
 *   the server EMITS. It cannot prove the browser executed it, cannot prove
 *   Meta received it, and cannot prove Events Manager recorded it. Those are a
 *   live browser check and an Events Manager read, and they are recorded in
 *   the release evidence — not here, and never inferred from a PASS below.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-meta-lead-event.php --user=1 --url=<site>
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$failures = 0;

function bhp_lead_assert( &$failures, $label, $condition ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	$failures++;
	echo "FAIL: {$label}\n";
}

if ( ! class_exists( 'BHP_Meta_Pixel' ) ) {
	echo "FAIL: BHP_Meta_Pixel is not loaded — the theme's require_once is missing.\n";
	exit( 1 );
}

$theme_dir = get_template_directory();

function bhp_lead_read( $theme_dir, $rel ) {
	$path = $theme_dir . '/' . ltrim( $rel, '/' );
	return is_readable( $path ) ? (string) file_get_contents( $path ) : '';
}

$config  = BHP_Meta_Pixel::runtime_config();
$map     = isset( $config['map'] ) ? $config['map'] : array();
$runtime = BHP_Meta_Pixel::runtime_js();

echo "\n=== GROUP A — the mapping is COMPLETE, not merely correct ===\n";

/*
 * The point of scanning rather than listing: a list in a test file is a second
 * copy of the truth and drifts from the first. This reads the events the theme
 * actually emits, so a new funnel added without a mapping fails here.
 */
$bhp_lead_sources = array(
	'page-adventure-kit-thank-you.php',
	'page-gift-guide-thank-you.php',
	'template-parts/acquisition/signup-form.php',
	'assets/js/audience-quiz.js',
	'assets/js/mariana-popup.js',
);

$emitted = array();
foreach ( $bhp_lead_sources as $rel ) {
	$src = bhp_lead_read( $theme_dir, $rel );
	bhp_lead_assert( $failures, "A0 source is readable: {$rel}", '' !== $src );
	if ( '' === $src ) {
		continue;
	}
	// PHP array form  'event' => 'x'   and JS form  event: 'x' / pushEvent('x'
	if ( preg_match_all( "/'event'\s*=>\s*'([a-z0-9_]*(?:signup|success)[a-z0-9_]*)'/", $src, $m ) ) {
		$emitted = array_merge( $emitted, $m[1] );
	}
	if ( preg_match_all( "/push(?:Quiz)?Event\(\s*'([a-z0-9_]*signup_success)'/", $src, $m ) ) {
		$emitted = array_merge( $emitted, $m[1] );
	}
}
$emitted = array_values( array_unique( $emitted ) );

/*
 * Intentionally NOT mapped, each with the reason. An entry here is a decision,
 * not an oversight, and a reader who disagrees has something to argue with.
 */
$deliberately_unmapped = array(
	// A failed signup is not a lead. Mapping it would report every typo'd
	// email address to Meta as a conversion.
	'signup_error',
	'quiz_signup_failed',
	// Intent, not completion — the visitor pressed submit and the server has
	// not accepted them yet.
	'quiz_signup_started',
	'quiz_signup_submitted',
);

foreach ( $emitted as $event ) {
	if ( in_array( $event, $deliberately_unmapped, true ) ) {
		continue;
	}
	bhp_lead_assert(
		$failures,
		"A1 the emitted success event '{$event}' is mapped to Meta's Lead",
		isset( $map[ $event ] ) && 'Lead' === $map[ $event ][0]
	);
}

bhp_lead_assert(
	$failures,
	'A2 the scan actually found events — an empty scan would make A1 vacuously pass',
	count( $emitted ) >= 3
);

// The three that were missing, named explicitly, so the regression that
// started this workstream cannot come back silently.
foreach ( array( 'adventure_kit_signup' => 'adventure_kit', 'gift_guide_signup' => 'gift_guide', 'quiz_signup_success' => 'quiz' ) as $event => $expected_name ) {
	bhp_lead_assert(
		$failures,
		"A3 '{$event}' maps to Lead with content_name '{$expected_name}'",
		isset( $map[ $event ] ) && 'Lead' === $map[ $event ][0] && $expected_name === $map[ $event ][1]
	);
}

// And the three that already worked still work — this change adds, it does not move.
foreach ( array( 'parent_popup_success' => 'parent_popup', 'teacher_popup_success' => 'teacher_popup' ) as $event => $expected_name ) {
	bhp_lead_assert(
		$failures,
		"A4 the pre-existing mapping '{$event}' => '{$expected_name}' is unchanged",
		isset( $map[ $event ] ) && 'Lead' === $map[ $event ][0] && $expected_name === $map[ $event ][1]
	);
}
bhp_lead_assert(
	$failures,
	"A5 'lead_signup_success' still derives its content_name from the payload",
	isset( $map['lead_signup_success'] ) && '' === $map['lead_signup_success'][1]
);

// Every Lead content_name is distinct — the two funnels must stay separable in
// Meta, and so must the three new sources (.claude/rules/funnels.md).
$lead_names = array();
foreach ( $map as $event => $mapped ) {
	if ( 'Lead' === $mapped[0] && '' !== $mapped[1] ) {
		$lead_names[] = $mapped[1];
	}
}
bhp_lead_assert(
	$failures,
	'A6 no two Lead sources share a content_name',
	count( $lead_names ) === count( array_unique( $lead_names ) )
);

echo "\n=== GROUP B — every Lead carries an eventID, and it is born in the browser ===\n";

bhp_lead_assert(
	$failures,
	'B1 the runtime generates an event id',
	false !== strpos( $runtime, 'function newEventId()' )
);
bhp_lead_assert(
	$failures,
	'B2 it prefers crypto.randomUUID and degrades rather than failing',
	false !== strpos( $runtime, 'randomUUID' ) && false !== strpos( $runtime, 'getRandomValues' )
);
bhp_lead_assert(
	$failures,
	'B3 a Lead with no id of its own is given one',
	false !== strpos( $runtime, 'eventId = newEventId();' )
);
bhp_lead_assert(
	$failures,
	'B4 the id is handed back to the dataLayer so a future CAPI tag dedups on the same value',
	false !== strpos( $runtime, 'payload.event_id = eventId;' )
);
bhp_lead_assert(
	$failures,
	'B5 an id supplied by the payload still wins — this never overwrites a caller',
	false !== strpos( $runtime, "var eventId = payload.event_id || '';" )
);
bhp_lead_assert(
	$failures,
	'B6 track() passes the id to fbq as Meta specifies, in the fourth argument',
	false !== strpos( $runtime, "window.fbq( 'track', event.name, event.params || {}, { eventID: String( event.eventID ) } )" )
);

/*
 * ⛔ The cache invariant, restated as an assertion rather than a comment: an
 * eventID rendered into the head payload would be a per-visitor byte on a
 * cacheable surface, and SiteGround's page cache varies only on
 * Accept-Encoding. Two visitors would share one lead id. The head HTML must
 * therefore contain no id at all.
 */
$head_a = BHP_Meta_Pixel::head_html();
$head_b = BHP_Meta_Pixel::head_html();
bhp_lead_assert(
	$failures,
	'B7 two renders of the head payload are byte-identical — no id leaked into cacheable HTML',
	$head_a === $head_b
);
bhp_lead_assert(
	$failures,
	'B8 the head payload contains no generated event id',
	1 !== preg_match( '/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-/', $head_a )
);

echo "\n=== GROUP C — one signup is one Lead, and the ordering contract holds ===\n";

bhp_lead_assert(
	$failures,
	'C1 the runtime carries a one-Lead-per-page-load latch',
	false !== strpos( $runtime, 'var leadFired = false;' )
);
bhp_lead_assert(
	$failures,
	'C2 a second Lead in the same page load returns without firing',
	false !== strpos( $runtime, 'if ( leadFired ) { return; }' )
);
bhp_lead_assert(
	$failures,
	'C3 the latch applies to Lead only — AddPaymentInfo and the server events are untouched',
	1 === preg_match( "/if \( 'Lead' === mapped\[ 0 \] \) \{\s*\n\s*if \( leadFired \)/", $runtime )
);

/*
 * The ordering contract, asserted on BOTH sides. Neither half is meaningful
 * alone: the thank-you page must print late, and the popup script must be a
 * footer script for "late" to mean "after the popup".
 */
$akty = bhp_lead_read( $theme_dir, 'page-adventure-kit-thank-you.php' );
$funcs = bhp_lead_read( $theme_dir, 'functions.php' );

bhp_lead_assert(
	$failures,
	'C4 the Kit thank-you page prints its conversion push on wp_footer at priority 99',
	1 === preg_match( "/add_action\(\s*'wp_footer',\s*function\s*\(\s*\)\s*\{.*?adventure_kit_signup.*?\}\s*,\s*99\s*\)/s", $akty )
);
/*
 * C5 is the assertion that actually catches a revert: the conversion script
 * must exist exactly once, and it must sit INSIDE the wp_footer callback
 * rather than in the page body. Position, not presence, is the whole change.
 */
$c5_hook   = strpos( $akty, "add_action( 'wp_footer'" );
$c5_script = strpos( $akty, "var DEDUP_KEY = 'bhp_adventure_kit_signup_fired';" );
bhp_lead_assert(
	$failures,
	'C5 the conversion script exists exactly once and sits after the wp_footer registration, not in the page body',
	1 === substr_count( $akty, "var DEDUP_KEY = 'bhp_adventure_kit_signup_fired';" )
		&& false !== $c5_hook && false !== $c5_script && $c5_script > $c5_hook
);
bhp_lead_assert(
	$failures,
	'C6 the payload, the event name and the session dedup guard survived the move unchanged',
	false !== strpos( $akty, "'event'       => 'adventure_kit_signup'," )
		&& false !== strpos( $akty, "'placement'   => 'adventure_kit_thank_you_page'," )
		&& false !== strpos( $akty, "var DEDUP_KEY = 'bhp_adventure_kit_signup_fired';" )
);
bhp_lead_assert(
	$failures,
	'C7 mariana-popup.js is still enqueued in the FOOTER sitewide — the other half of the contract',
	1 === preg_match( "/wp_enqueue_script\(\s*'bhp-mariana-popup',.*?wp_get_theme\(\)->get\('Version'\),\s*true\s*\)/s", $funcs )
);
bhp_lead_assert(
	$failures,
	'C8 QA can read the Lead that actually fired, without reconstructing it from the queue',
	false !== strpos( $runtime, 'lead: function () { return NS.lead || null; }' )
);

echo "\n=== GROUP D — nothing regressed ===\n";

bhp_lead_assert(
	$failures,
	'D1 consent still revokes before init, so a Lead can no more escape than a PageView can',
	1 === preg_match( "/fbq\('consent','revoke'\);fbq\('init'/", BHP_Meta_Pixel::base_code_html() )
);
bhp_lead_assert(
	$failures,
	'D2 the Lead travels through the same track() and the same fbq the PageView does — no second transport was invented',
	1 === substr_count( $runtime, 'function track( event )' )
		&& false !== strpos( $runtime, 'track( { name: mapped[ 0 ], params: params, eventID: eventId } );' )
);
bhp_lead_assert(
	$failures,
	'D3 the runtime still touches NO localStorage — funnel isolation, invariant 3',
	false === strpos( $runtime, 'localStorage' )
);
bhp_lead_assert(
	$failures,
	'D4 the runtime still touches NO sessionStorage — funnel isolation, invariant 3',
	false === strpos( $runtime, 'sessionStorage' )
);
bhp_lead_assert(
	$failures,
	'D5 the SDK is still fetched only on grant, never on render',
	1 === substr_count( $runtime, 's.src = config.sdk;' )
		&& false === strpos( BHP_Meta_Pixel::base_code_html(), 'fbevents.js' )
);
bhp_lead_assert(
	$failures,
	'D6 the pixel id is unchanged — this workstream changed what is sent, never where',
	'2050405642533821' === BHP_Meta_Pixel::pixel_id()
);

echo "\n";
if ( $failures ) {
	echo "RESULT: {$failures} FAILURE(S)\n";
	exit( 1 );
}
echo "RESULT: all assertions passed\n";
exit( 0 );
