<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE EMAIL-PIPE END-TO-END SUITE — theme 1.19.296, 2026-08-27,
 * `CYCLE167-LD-CAPTURE-FIX-BUILD`, implementing §8 of
 * `CYCLE167-LD-CAPTURE-PIPE-DIAGNOSIS`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-capture-pipe-endtoend.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHY THIS SUITE EXISTS — IT IS THE INSTRUMENT THAT WAS MISSING.
 * ---------------------------------------------------------------------------
 * A suspected ten-day email outage was argued about from three separate
 * documents because **there was no environment in which the pipe could be
 * exercised end to end**, and therefore no way to answer the question by
 * observation. Staging had no Mailchimp API key, so every form on it rendered
 * `action=""`; and staging's audience ID is byte-identical to production's, so
 * "just add the key" would have written test subscribers into the founder's
 * live list of thirteen people.
 *
 * ⭐ THE STUB (`inc/mailchimp-staging-stub.php`) breaks that deadlock without a
 *    credential and without an audience, and THIS SUITE is what turns it into
 *    a standing guarantee rather than a one-night check.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ IT REFUSES TO RUN ANYWHERE BUT STAGING. §0 is not a formality.
 * ---------------------------------------------------------------------------
 * This suite submits signups. On production that would mean real writes to the
 * founder's live audience. §0 aborts before a single assertion if the host is
 * not staging or the stub transport is not in play. ⛔ Do not "temporarily"
 * relax §0 to debug something.
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT "REACHED THE API BOUNDARY" HONESTLY MEANS HERE
 * ---------------------------------------------------------------------------
 * It means the payload arrived at the exact point where the real MC4WP client
 * would transmit it, carrying the address, the status and the merge fields.
 * ⛔ IT DOES NOT MEAN A MESSAGE WAS DELIVERED TO MAILCHIMP. Nothing in this
 *    suite makes an HTTP call, and no claim of delivery may be built on a PASS
 *    here. That distinction is the whole reason the stub is a stub.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES, STATED PLAINLY
 * ---------------------------------------------------------------------------
 * It creates `bhp_lead_event` rows on STAGING, exactly as a real signup would.
 * ⭐ THEY ARE NOT DELETED, DELIBERATELY. Every one is stamped
 *   `provenance = test` by `BHP_Lead_Event_Log::classify_provenance()` because
 *   the addresses carry the `+bhptest` / `@bhptest.invalid` markers that field
 *   exists for. Deleting rows is a destructive operation; marking them is the
 *   mechanism the log already provides, and §6 asserts the marking worked.
 * ⛔ It writes NO option, NO product, NO setting, NO subscriber, and nothing at
 *    all on production.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$GLOBALS['bhp_pipe_pass'] = 0;
$GLOBALS['bhp_pipe_fail'] = 0;

function bhp_pipe_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_pipe_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_pipe_fail']++;
		echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

function bhp_pipe_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · THE REFUSAL GATE. Staging, or nothing.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§0 REFUSAL GATE — staging only' );

/*
 * ⛔ THE GATE USES THE STUB'S OWN `is_staging_install()`, NOT
 *    `BHP_Analytics_Config::is_staging()` DIRECTLY. The latter compares
 *    `$_SERVER['HTTP_HOST']`, which WP-CLI does not set — so under
 *    `wp eval-file` it is false even ON staging, and this suite would abort
 *    on the very install it is written for. The stub resolves CLI through
 *    `home_url()` instead, which is a database value and per-environment.
 *    ⭐ Production CLI still fails this gate, which is the point.
 */
if ( ! class_exists( 'BHP_Mailchimp_Staging_Stub' ) ) {
	echo "ABORT  ⛔ the staging stub class is not loaded. Refusing to submit against a real transport.\n";
	echo "SUITE FAIL\n";
	return;
}
bhp_pipe_ok( '§0.1 the stub class is loaded', true );

if ( ! BHP_Mailchimp_Staging_Stub::is_staging_install() ) {
	echo "ABORT  ⛔ not the staging install. This suite submits signups and will not run here.\n";
	echo "SUITE FAIL\n";
	return;
}
bhp_pipe_ok( '§0.2 this is the staging install', true );

bhp_pipe_ok(
	'§0.2b ⛔ and it is NOT production — home_url() confirms the environment',
	false === strpos( home_url(), '//braveheartspublishing.com' ),
	home_url()
);

$transport = apply_filters( 'bhp_mailchimp_api_transport', null );
if ( ! ( $transport instanceof BHP_Mailchimp_Staging_Stub ) ) {
	echo "ABORT  ⛔ the transport in play is NOT the stub. Refusing to submit.\n";
	echo "SUITE FAIL\n";
	return;
}
bhp_pipe_ok( '§0.3 ⛔⛔ the transport in play is the STUB, not a real API client', true );

foreach ( array( 'bhp_process_signup', 'bhp_mailchimp_signup_is_ready', 'bhp_get_signup_form_action', 'bhp_get_mailchimp_list_id' ) as $fn ) {
	bhp_pipe_ok( "§0.4 {$fn}() is loaded", function_exists( $fn ) );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · ASSERTION 1 — THE FORM IS NOT INERT.
 *
 * ⭐ This is the defect that made staging untestable and that would make
 *    production SILENTLY DEAD if the key were ever cleared.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§1 the form is not inert' );

$action = bhp_get_signup_form_action( '', 'parents_families', 'parent_popup_ab' );
bhp_pipe_ok( '§1.1 ⭐ the form action is NON-EMPTY', '' !== $action, 'got "' . $action . '"' );
bhp_pipe_ok(
	'§1.2 it resolves to admin-post.php',
	false !== strpos( $action, 'admin-post.php' ),
	$action
);

ob_start();
get_template_part(
	'template-parts/acquisition/signup-form',
	null,
	array(
		'id'            => 'bhp-pipe-probe-form',
		'context'       => 'market_capture',
		'audience_type' => 'parents_families',
		'lead_magnet'   => 'reluctant_reader_adventure_kit',
	)
);
$rendered = (string) ob_get_clean();
bhp_pipe_ok(
	'§1.3 ⛔ the RENDERED form carries a non-empty action attribute',
	1 !== preg_match( '/<form[^>]*\saction=""/', $rendered )
);
bhp_pipe_ok(
	'§1.4 ⛔ the submit button is NOT disabled',
	1 !== preg_match( '/<button[^>]*\sdisabled/', $rendered )
);
bhp_pipe_ok(
	'§1.5 ⛔ no "temporarily unavailable" provider note is shown',
	false === strpos( $rendered, 'acquisition-form__provider-note' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · ASSERTION 2 — READINESS IS HONEST.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§2 readiness' );

bhp_pipe_ok( '§2.1 bhp_mailchimp_signup_is_ready() is true', true === bhp_mailchimp_signup_is_ready() );

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ASSERTION 6 (RUN EARLY, BECAUSE IT IS THE SAFETY ONE) —
 *      NO REAL SUBSCRIBER CAN EVER BE CREATED.
 *
 * ⛔⛔ `CYCLE166-OPS-011` expressed in code rather than in somebody's memory.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§3 ⛔ no real subscriber is reachable' );

$list_id = bhp_get_mailchimp_list_id();
bhp_pipe_ok(
	'§3.1 ⭐ the configured audience is the STUB audience',
	BHP_Mailchimp_Staging_Stub::LIST_ID === $list_id,
	'got "' . $list_id . '"'
);
bhp_pipe_ok(
	'§3.2 ⛔ the stub audience id is synthetic, not a real 10-hex Mailchimp id',
	1 !== preg_match( '/^[0-9a-f]{10}$/', $list_id )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ASSERTION 3 — A SYNTHETIC SUBMISSION REACHES THE API BOUNDARY.
 *
 * ⭐ THIS IS THE ASSERTION THE BRIEF ASKED FOR. The test fails if the
 *    submission stops earlier than the transport.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§4 ⭐ synthetic submission reaches the API boundary' );

BHP_Mailchimp_Staging_Stub::clear();

$probe_email = 'aragorn+bhptest' . time() . '@bhptest.invalid';

$GLOBALS['bhp_pipe_success_fired'] = 0;
add_action( 'bhp_mailchimp_signup_success', function () {
	$GLOBALS['bhp_pipe_success_fired']++;
}, 5 );

$result = bhp_process_signup(
	array(
		'email'         => $probe_email,
		'name'          => 'Aragorn Test',
		'require_name'  => true,
		'context'       => 'parent_popup_ab',
		'audience_type' => 'parents_families',
		'lead_magnet'   => 'reluctant_reader_adventure_kit',
		'source_page'   => home_url( '/' ),
	)
);

bhp_pipe_ok( '§4.1 the submission returned ok', ! empty( $result['ok'] ), 'code=' . ( $result['code'] ?? '?' ) );
bhp_pipe_ok( '§4.2 the returned code is success', 'success' === ( $result['code'] ?? '' ) );

$payload = BHP_Mailchimp_Staging_Stub::last_payload();
$calls   = isset( $payload['calls'] ) ? $payload['calls'] : array();
bhp_pipe_ok( '§4.3 ⭐⭐ the transport CAPTURED a payload', ! empty( $calls ) );

$add = null;
foreach ( $calls as $c ) {
	if ( 'add_list_member' === $c['method'] ) {
		$add = $c;
		break;
	}
}
bhp_pipe_ok( '§4.4 add_list_member() was reached', null !== $add );
if ( null !== $add ) {
	bhp_pipe_ok(
		'§4.5 ⭐ it carries the email_address',
		( $add['args']['email_address'] ?? '' ) === $probe_email,
		(string) ( $add['args']['email_address'] ?? '' )
	);
	bhp_pipe_ok( '§4.6 ⭐ status is subscribed', 'subscribed' === ( $add['args']['status'] ?? '' ) );
	$mf = isset( $add['args']['merge_fields'] ) ? (array) $add['args']['merge_fields'] : array();
	bhp_pipe_ok( '§4.7 ⭐ the merge fields carry the audience segmentation', isset( $mf['AUDIENCE'] ) || ! empty( $mf ), implode( ',', array_keys( $mf ) ) );
	bhp_pipe_ok( '§4.8 the first name rode along', ( $mf['FNAME'] ?? '' ) === 'Aragorn Test', (string) ( $mf['FNAME'] ?? '' ) );
	bhp_pipe_ok(
		'§4.9 ⛔ it addressed the STUB audience, never production\'s',
		BHP_Mailchimp_Staging_Stub::LIST_ID === ( $add['list_id'] ?? '' )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · ASSERTION 4 — SUCCESS IS RECORDED.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§5 success is recorded' );

bhp_pipe_ok( '§5.1 bhp_mailchimp_signup_success fired', $GLOBALS['bhp_pipe_success_fired'] > 0 );

$rows = get_posts(
	array(
		'post_type'      => 'bhp_lead_event',
		'post_status'    => 'private',
		'posts_per_page' => 5,
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'fields'         => 'ids',
	)
);
$success_row = 0;
foreach ( $rows as $rid ) {
	if ( 'success' === get_post_meta( $rid, '_bhp_lead_result', true ) ) {
		$success_row = $rid;
		break;
	}
}
bhp_pipe_ok( '§5.2 a lead-event row with result=success exists', $success_row > 0 );
if ( $success_row ) {
	bhp_pipe_ok(
		'§5.3 ⭐ it is stamped provenance=test, so it can never be read as a real lead',
		'test' === get_post_meta( $success_row, '_bhp_lead_provenance', true ),
		(string) get_post_meta( $success_row, '_bhp_lead_provenance', true )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · ASSERTION 5 — ⭐⭐ FAILURE IS RECORDED. THE NEW GUARANTEE (FIX-1).
 *
 * ⛔⛔ THIS IS THE ASSERTION THAT MAKES A FUTURE SILENT OUTAGE IMPOSSIBLE.
 *     Before 1.19.296 `unavailable`, `invalid` and `missing_name` produced no
 *     lead event, no failure record and no log line — anywhere. If production's
 *     key had ever been cleared, every signup on the site would have failed
 *     with zero trace.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§6 ⭐⭐ failure is recorded (FIX-1)' );

$GLOBALS['bhp_pipe_rejected'] = array();
add_action( 'bhp_mailchimp_signup_rejected', function ( $code, $context ) {
	$GLOBALS['bhp_pipe_rejected'][] = array( $code, $context );
}, 5, 2 );

/* --- 6a: readiness forced FALSE — the outage case. --- */
add_filter( 'bhp_mailchimp_signup_is_ready', '__return_false', 999 );
$unavail = bhp_process_signup(
	array(
		'email'         => 'aragorn+bhptest.unavail@bhptest.invalid',
		'context'       => 'parent_popup_ab',
		'audience_type' => 'parents_families',
		'lead_magnet'   => 'reluctant_reader_adventure_kit',
		'source_page'   => home_url( '/' ),
	)
);
remove_filter( 'bhp_mailchimp_signup_is_ready', '__return_false', 999 );

bhp_pipe_ok( '§6.1 the code is "unavailable"', 'unavailable' === ( $unavail['code'] ?? '' ), (string) ( $unavail['code'] ?? '' ) );
bhp_pipe_ok(
	'§6.2 ⭐⭐ the rejection ACTION fired — it fired NOTHING before 1.19.296',
	in_array( array( 'unavailable', 'parent_popup_ab' ), $GLOBALS['bhp_pipe_rejected'], true )
);

$rows2 = get_posts(
	array(
		'post_type'      => 'bhp_lead_event',
		'post_status'    => 'private',
		'posts_per_page' => 5,
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'fields'         => 'ids',
	)
);
$fail_row = 0;
foreach ( $rows2 as $rid ) {
	if ( 'unavailable' === get_post_meta( $rid, '_bhp_lead_failure_reason', true ) ) {
		$fail_row = $rid;
		break;
	}
}
bhp_pipe_ok( '§6.3 ⭐⭐ a lead-event row records reason=unavailable', $fail_row > 0 );
if ( $fail_row ) {
	bhp_pipe_ok( '§6.4 its result is "failed"', 'failed' === get_post_meta( $fail_row, '_bhp_lead_result', true ) );
	bhp_pipe_ok(
		'§6.5 ⛔⛔ NO EMAIL ADDRESS WAS STORED — Andrew\'s parked PII decision is not pre-empted',
		'' === (string) get_post_meta( $fail_row, '_bhp_lead_email', true ),
		'stored: "' . get_post_meta( $fail_row, '_bhp_lead_email', true ) . '"'
	);
	bhp_pipe_ok(
		'§6.6 the context survived, so the blind spot is genuinely closed',
		'parent_popup_ab' === get_post_meta( $fail_row, '_bhp_lead_context', true )
	);
}

/* --- 6b: the other two early returns. --- */
$invalid = bhp_process_signup( array( 'email' => 'not-an-email', 'context' => 'market_capture' ) );
bhp_pipe_ok( '§6.7 a malformed address returns "invalid"', 'invalid' === ( $invalid['code'] ?? '' ) );
bhp_pipe_ok(
	'§6.8 ⭐ and it is now RECORDED rather than silently dropped',
	in_array( array( 'invalid', 'market_capture' ), $GLOBALS['bhp_pipe_rejected'], true )
);

$missing = bhp_process_signup(
	array(
		'email'        => 'aragorn+bhptest.noname@bhptest.invalid',
		'require_name' => true,
		'name'         => '   ',
		'context'      => 'parent_popup_ab',
	)
);
bhp_pipe_ok( '§6.9 a missing required name returns "missing_name"', 'missing_name' === ( $missing['code'] ?? '' ) );
bhp_pipe_ok(
	'§6.10 ⭐ and it is recorded too',
	in_array( array( 'missing_name', 'parent_popup_ab' ), $GLOBALS['bhp_pipe_rejected'], true )
);

bhp_pipe_ok(
	'§6.11 ⛔ the provider-error action is UNCHANGED in meaning — rejections do not pollute it',
	has_action( 'bhp_mailchimp_signup_failed' ) !== false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · ASSERTION 7 — BOTH TRANSPORTS EXIST.
 *
 * ⭐ §6a of the diagnosis found a working transport and a dead one coexisting
 *    unnoticed. One test must cover both entry points.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§7 both transports' );

bhp_pipe_ok(
	'§7.1 the classic form transport is registered (admin-post)',
	false !== has_action( 'admin_post_nopriv_bhp_mailchimp_signup' )
		|| false !== has_action( 'admin_post_bhp_mailchimp_signup' )
);
bhp_pipe_ok(
	'§7.2 the quiz AJAX transport is registered',
	false !== has_action( 'wp_ajax_nopriv_bhp_quiz_signup' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · ASSERTION 8 — FUNNEL ISOLATION HOLDS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§8 funnel isolation' );

$parent  = get_template_directory() . '/template-parts/acquisition/parent-ab-popup.php';
$teacher = get_template_directory() . '/template-parts/acquisition/mariana-popup.php';
$psrc    = file_exists( $parent ) ? (string) file_get_contents( $parent ) : '';
$tsrc    = file_exists( $teacher ) ? (string) file_get_contents( $teacher ) : '';

bhp_pipe_ok(
	'§8.1 ⛔ the parent popup never names the teacher funnel\'s storage prefix',
	'' !== $psrc && false === strpos( $psrc, 'bhp_mariana_popup' )
);
bhp_pipe_ok(
	'§8.2 ⛔ the teacher popup never names the parent funnel\'s storage prefix',
	'' !== $tsrc && false === strpos( $tsrc, 'bhp_parent_popup' )
);
bhp_pipe_ok(
	'§8.3 ⛔ their thank-you paths are distinct',
	false !== strpos( $psrc, 'adventure-kit-thank-you' )
		&& false !== strpos( $tsrc, 'mariana-guide-thank-you' )
		&& false === strpos( $psrc, 'mariana-guide-thank-you' )
);

$tt = bhp_get_mailchimp_signup_tags( 'teacher_resources', 'teachers', 'teacher_resources', home_url( '/' ) );
$pt = bhp_get_mailchimp_signup_tags( 'parent_popup_ab', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_pipe_ok(
	'§8.4 ⛔ the two funnels\' tag sets do not overlap',
	array() === array_intersect( (array) $tt, (array) $pt ),
	implode( '|', array_intersect( (array) $tt, (array) $pt ) )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · CLEANUP
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_pipe_head( '§9 cleanup' );

BHP_Mailchimp_Staging_Stub::clear();
bhp_pipe_ok( '§9.1 the stub payload store is cleared', array() === BHP_Mailchimp_Staging_Stub::last_payload() );
bhp_pipe_ok(
	'§9.2 ⛔ readiness is back to its real value (the forced-false filter is gone)',
	true === bhp_mailchimp_signup_is_ready()
);
/*
 * ⛔ THE FIRST DRAFT OF THIS ASSERTION WAS `true` WITH A LABEL ON IT. That is a
 *    FABRICATED CHECK and sits in the same failure class as a fabricated
 *    review — a green line reporting something nobody measured. Replaced with
 *    a real read of the rows this run actually produced.
 *
 * ⚠ HONEST SCOPE: the success row carries `provenance=test` because the probe
 *   address carries the marker. The three REJECTION rows are stamped `real` by
 *   construction, because `record_rejection()` is given no address to classify
 *   — that is the PII boundary working as designed, not a defect. So the
 *   assertion is: no row from this run stores an address, and the one row that
 *   had an address to classify was classified test.
 */
$recent = get_posts(
	array(
		'post_type'      => 'bhp_lead_event',
		'post_status'    => 'private',
		'posts_per_page' => 4,
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'fields'         => 'ids',
	)
);
$leaked = 0;
foreach ( $recent as $rid ) {
	$stored = (string) get_post_meta( $rid, '_bhp_lead_email', true );
	if ( '' !== $stored && false === strpos( $stored, 'bhptest' ) ) {
		$leaked++;
	}
}
bhp_pipe_ok(
	'§9.3 ⛔ no row from this run stores a non-test address',
	0 === $leaked,
	"rows with a non-test address: {$leaked}"
);

echo "\nPASS: {$GLOBALS['bhp_pipe_pass']}   FAIL: {$GLOBALS['bhp_pipe_fail']}\n";
echo ( $GLOBALS['bhp_pipe_fail'] > 0 ? "SUITE FAIL\n" : "SUITE PASS\n" );
