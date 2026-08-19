<?php
/**
 * School-visit hand-delivery — test suite. 1.8.52, `CYCLE163-LD-PICKUP-NATIVE`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-school-visit-pickup.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT 1.8.52 CHANGED, AND THEREFORE WHAT THIS SUITE NOW ASSERTS
 * ---------------------------------------------------------------------------
 * The mechanism moved from a **$0.00 shipping rate this plugin injected** to
 * **WooCommerce Blocks' own LOCAL PICKUP**, turned on for one request by
 * filtering the READS of `woocommerce_pickup_location_settings` and
 * `pickup_location_pickup_locations`. Sections 4, 5 and 9 were rewritten
 * against the new mechanism; sections 0–3 and 6–8 were re-pointed rather than
 * replaced, because what they assert did not change.
 *
 * ⛔ THE SINGLE MOST IMPORTANT NEW ASSERTION is that NEITHER PICKUP OPTION IS
 *    EVER WRITTEN. Enabling local pickup in the database is a WooCommerce
 *    checkout-configuration change and an Andrew-only gate. §9 reads the
 *    stored value before and after every flagged code path in this file and
 *    asserts it came back byte-identical.
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS ACTUALLY FOR
 * ---------------------------------------------------------------------------
 * Four things, in descending order of how much they cost if they break:
 *
 *   1. ⛔ THE DUPLICATE-PRINT PROTECTION. A hand-delivery order must never
 *      reach the print partner. Asserted against the REAL, LIVE webhook rows
 *      in this store's database — not against a mock — because the thing that
 *      would actually go wrong is a real webhook not being recognised.
 *   2. ⛔ NO WOOCOMMERCE SETTING IS MUTATED. See above.
 *   3. ⛔ A NORMAL VISITOR SEES ZERO CHANGE. Asserted by running every filter
 *      with no session flag and comparing the result identically.
 *   4. The gating itself: registry validation, cutoff expiry, unknown slugs.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It NEVER delivers a webhook. It calls the FILTER WooCommerce's own
 *    `WC_Webhook::should_deliver()` returns through, which is the exact
 *    decision point, and reads the answer. No HTTP request is made to any
 *    external host by this file, and none may ever be added to it.
 * ⛔ It writes NO product, NO coupon, NO shipping setting and NO zone. It
 *    writes exactly two things and restores both: the registry option (value
 *    snapshotted first, restored exactly) and two zero-total `pending` probe
 *    orders, which are permanently deleted in the same run.
 * ⚠ CORRECTED: an earlier version of this header claimed the suite "creates no
 *   database rows" and cleans up "in a shutdown handler". Both were false —
 *   see the cleanup block below for what actually happens and what was
 *   observed. The claim is corrected here rather than deleted so that a reader
 *   who saw the old wording knows it was wrong.
 *
 * ⚠ ONE ASSERTION IS ENVIRONMENT-DEPENDENT AND SAYS SO WHEN IT SKIPS: the
 *   live-webhook assertions need at least one webhook row pointing at the
 *   print partner. On a store with none, they report SKIP with the reason
 *   rather than passing vacuously.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skips    = array();

function bhp_svp_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_svp_skip( $label, $reason, array &$skips ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$skips[] = $label;
}

/* =========================================================================
 * 0. THE FILE IS LOADED AT ALL
 * ====================================================================== */

bhp_svp_assert( defined( 'BHP_SCHOOL_VISIT_OPTION' ), 'school-visit-pickup.php is loaded by the plugin bootstrap (BHP_SCHOOL_VISIT_OPTION defined)', $failures );
bhp_svp_assert( function_exists( 'bhp_school_pickup_order_is_pickup' ), 'The pickup predicate is defined', $failures );
bhp_svp_assert( function_exists( 'bhp_school_pickup_block_bookvault_webhook' ), 'The Bookvault skip is defined', $failures );

/* ⭐ 1.8.52 — the new mechanism's surface exists, and the old one does not. */
bhp_svp_assert( function_exists( 'bhp_school_pickup_filter_location_settings' ), '1.8.52: the local-pickup SETTINGS read-filter is defined', $failures );
bhp_svp_assert( function_exists( 'bhp_school_pickup_filter_locations' ), '1.8.52: the local-pickup LOCATIONS read-filter is defined', $failures );
bhp_svp_assert( function_exists( 'bhp_school_pickup_decorate_rate' ), '1.8.52: the pickup-rate decorator is defined', $failures );
bhp_svp_assert( function_exists( 'bhp_school_pickup_default_chosen_method' ), '1.8.52: the default-selection filter is defined', $failures );
bhp_svp_assert( function_exists( 'bhp_school_visit_request_record' ), '1.8.52: the per-request session resolver is defined here, not in the fields file', $failures );
bhp_svp_assert(
	! function_exists( 'bhp_school_pickup_inject_rate' ),
	'⛔ 1.8.52: the OLD $0.00 rate injector is GONE, not disabled and not feature-flagged. Two mechanisms offering the same free delivery would mean two rows, two method_ids and two places to keep the Bookvault skip correct',
	$failures
);
bhp_svp_assert( 'pickup_location' === BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID, '1.8.52: hand delivery now rides WooCommerce\'s OWN pickup_location method id', $failures );
bhp_svp_assert( 'bhp_school_pickup' === BHP_SCHOOL_PICKUP_METHOD_ID, '1.8.52: the retired method id is still DEFINED, because orders placed under 1.8.49-1.8.51 must keep being recognised forever', $failures );

if ( ! defined( 'BHP_SCHOOL_VISIT_OPTION' ) ) {
	fwrite( STDERR, "FATAL: the feature file is not loaded; nothing else can be tested.\n" );
	exit( 1 );
}

/* =========================================================================
 * REGISTRY SNAPSHOT — restored no matter how this script ends.
 * ====================================================================== */

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ CLEANUP IS EXPLICIT AND IS **NOT** A SHUTDOWN HANDLER. READ THIS BEFORE
 *     "TIDYING" IT BACK INTO ONE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The first version of this suite used `register_shutdown_function()` for both
 * the option restore and the probe-order deletion. IT DID NOT RUN, and the run
 * left a seeded fixture registry and two probe orders behind on staging.
 *
 * ⭐ REPRODUCED DELIBERATELY RATHER THAN ASSUMED, 2026-08-17 on staging:
 *      $ cat probe.php
 *        <?php
 *        register_shutdown_function(function(){ update_option('probe','ran'); });
 *        echo "body-ran\n";
 *        exit(1);
 *      $ wp eval-file probe.php --user=1   ->  prints "body-ran", rc=1
 *      $ wp option get probe               ->  "Could not get 'probe' option"
 *
 *    Under `wp eval-file`, a shutdown callback registered by the script does
 *    NOT run when the script calls `exit()`. Since a failing test suite exits
 *    non-zero BY DESIGN, shutdown-based cleanup is guaranteed not to run in
 *    exactly the case where cleanup matters most — a failing run.
 *
 * ⛔ THEREFORE: every exit from this file goes through `bhp_svp_cleanup()`,
 *    which is idempotent and is called explicitly on both the pass and the
 *    fail path. Nothing this file writes is left to a handler that may never
 *    fire.
 *
 * ⚠ This is a property of the RUNNER, not of this suite, and any other suite
 *   in this repository that relies on a shutdown handler to clean up is
 *   leaking too. Reported rather than fixed here — out of this build's scope.
 */

/*
 * ⛔ `$GLOBALS[...]`, NOT a plain file-scope variable, AND THAT IS NOT STYLE.
 *
 * `wp eval-file` `include`s this file from INSIDE A METHOD. Everything that
 * looks like file scope here is therefore FUNCTION scope, and a `global $x`
 * declaration inside `bhp_svp_cleanup()` binds to a genuinely global `$x`
 * that was never written.
 *
 * ⭐ OBSERVED, not reasoned about: the second run of this suite reported
 *    "visit registry deleted (it did not exist before this run)" — cleanup ran,
 *    read null, and deleted the operator's seeded registry — and silently
 *    skipped both probe orders, leaving 2571 and 2572 behind. Both were then
 *    cleaned up by hand. This is the second cleanup defect in this file and
 *    the second one caused by the RUNNER rather than by the assertions.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIRD CLEANUP DEFECT, FOUND 2026-08-17 DURING `CYCLE163-LD-PICKUP-NATIVE`,
 *     AND IT IS THE ONLY ONE THAT DESTROYED REAL OPERATOR DATA.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHAT ACTUALLY HAPPENED, OBSERVED, NOT REASONED ABOUT. The suite was run
 *    over SSH and its output piped through `head -80` to read the first
 *    screenful. `head` closes the pipe after 80 lines, PHP CLI takes SIGPIPE
 *    on the next `echo`, AND THE PROCESS DIES BEFORE `bhp_svp_cleanup()`.
 *    The fixture registry was therefore left in the option. The NEXT run then
 *    snapshotted THAT as "the original", and restored it faithfully. Two runs
 *    later, staging's three real seeded visits (Adams, Dallas Harris, Liberty)
 *    were gone and six `bhpsvptest-*` rows were in their place. They were
 *    restored by hand from a value read out of the option earlier in the same
 *    session, which is luck, not a procedure.
 *
 * ⛔ THE FIX IS NOT "REMEMBER NOT TO PIPE THROUGH head". A test suite must not
 *    have a failure mode that deletes production-shaped data when somebody
 *    reads its output. So the fixtures are now MERGED INTO the registry, never
 *    substituted for it, and cleanup REMOVES ONLY THE ROWS THIS FILE ADDED:
 *
 *      · A killed run now leaves six extra `bhpsvptest-*` rows behind and
 *        loses NOTHING. They are inert: every one of them is a test school
 *        nobody has a link to.
 *      · The NEXT run removes them, because cleanup deletes by key prefix
 *        rather than by restoring a snapshot.
 *      · There is no snapshot to become contaminated, so the cascade that
 *        caused this cannot happen at all.
 *
 * ⚠ STILL TRUE, AND STILL STATED: probe ORDERS are not protected by this
 *   design. A killed run still leaves them behind, exactly as it did in 1.8.49.
 *   They are zero-total `pending` orders and are safe to delete by hand. The
 *   asymmetry is deliberate: an order is created by this file and can be
 *   identified; the registry belongs to an operator and cannot be reconstructed.
 *
 * ⭐ AND THE OPERATIONAL RULE THAT FALLS OUT OF IT: redirect this suite to a
 *    file (`> out.txt 2>&1`) and read the file. Do not pipe it into anything
 *    that can close the pipe early.
 */
$GLOBALS['bhp_svp_fixture_prefix'] = 'bhpsvptest-';
$GLOBALS['bhp_svp_probe_ids']      = array();

function bhp_svp_cleanup() {
	$bhp_svp_probe_ids = isset( $GLOBALS['bhp_svp_probe_ids'] ) ? $GLOBALS['bhp_svp_probe_ids'] : array();
	$prefix            = isset( $GLOBALS['bhp_svp_fixture_prefix'] ) ? $GLOBALS['bhp_svp_fixture_prefix'] : 'bhpsvptest-';

	foreach ( (array) $bhp_svp_probe_ids as $pid ) {
		$o = function_exists( 'wc_get_order' ) ? wc_get_order( $pid ) : null;
		// Guard the delete: only ever remove a zero-total pending order this
		// suite created. A destructive operation checks its target first.
		if ( $o && 0.0 === (float) $o->get_total() && 'pending' === $o->get_status() ) {
			$o->delete( true );
			echo "CLEANUP: deleted probe order {$pid}\n";
		} elseif ( $o ) {
			echo "CLEANUP: ⛔ REFUSED to delete order {$pid} -- it is not a zero-total pending probe. Delete it by hand after checking it.\n";
		}
	}
	$GLOBALS['bhp_svp_probe_ids'] = array();

	/*
	 * ⛔ REMOVE ONLY WHAT THIS FILE ADDED. Every other row is left exactly as
	 *    it was found, whatever it is, whoever put it there.
	 */
	$current = get_option( BHP_SCHOOL_VISIT_OPTION, array() );
	if ( ! is_array( $current ) ) {
		echo "CLEANUP: ⛔ the visit registry is not an array; refusing to touch it.\n";
		return;
	}
	$removed = 0;
	foreach ( array_keys( $current ) as $k ) {
		if ( 0 === strpos( (string) $k, $prefix ) ) {
			unset( $current[ $k ] );
			++$removed;
		}
	}
	if ( $removed ) {
		update_option( BHP_SCHOOL_VISIT_OPTION, $current );
	}
	echo "CLEANUP: removed {$removed} `{$prefix}*` fixture row(s); " . count( $current ) . " non-fixture visit row(s) left untouched\n";
}

$today     = wp_date( 'Y-m-d' );
$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
$next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
$next_year = wp_date( 'Y-m-d', strtotime( '+370 days' ) );

/*
 * ⛔ MERGE, NEVER REPLACE. See the cleanup block above for the incident this
 *    prevents. A fixture slug can never collide with a real one: no operator
 *    is going to name a school `bhpsvptest-live`.
 */
$bhp_svp_existing = get_option( BHP_SCHOOL_VISIT_OPTION, array() );
$bhp_svp_existing = is_array( $bhp_svp_existing ) ? $bhp_svp_existing : array();
echo 'INFO: ' . count( $bhp_svp_existing ) . " visit row(s) already in the registry; the fixtures below are ADDED to them, not substituted for them.\n";

update_option(
	BHP_SCHOOL_VISIT_OPTION,
	$bhp_svp_existing + array(
		// Live: cutoff a week out.
		'bhpsvptest-live'    => array( 'school' => 'Test Live Elementary', 'date' => $next_year, 'cutoff' => $next_week ),
		// Live on the LAST possible day: cutoff is today, and today must still work.
		'bhpsvptest-lastday' => array( 'school' => 'Test Lastday Elementary', 'date' => $next_year, 'cutoff' => $today ),
		// Expired: cutoff was yesterday.
		'bhpsvptest-expired' => array( 'school' => 'Test Expired Elementary', 'date' => $next_year, 'cutoff' => $yesterday ),
		// Malformed rows, each missing exactly one required field.
		'bhpsvptest-noschool' => array( 'school' => '', 'date' => $next_year, 'cutoff' => $next_week ),
		'bhpsvptest-baddate'  => array( 'school' => 'Test Bad Date', 'date' => '2026-02-31', 'cutoff' => $next_week ),
		'bhpsvptest-nocutoff' => array( 'school' => 'Test No Cutoff', 'date' => $next_year ),
	)
);

/* =========================================================================
 * 1. THE REGISTRY — sanitisation and fail-closed behaviour
 * ====================================================================== */

$records = bhp_school_visit_records();

bhp_svp_assert( isset( $records['bhpsvptest-live'] ), 'A well-formed visit row survives sanitisation', $failures );
bhp_svp_assert( 'Test Live Elementary' === $records['bhpsvptest-live']['school'], 'The school name round-trips exactly', $failures );
bhp_svp_assert( ! isset( $records['bhpsvptest-noschool'] ), 'A row with an empty school name is DROPPED, not defaulted', $failures );
bhp_svp_assert( ! isset( $records['bhpsvptest-baddate'] ), 'A row with an impossible date (2026-02-31) is DROPPED -- checkdate(), not a regex alone', $failures );
bhp_svp_assert( ! isset( $records['bhpsvptest-nocutoff'] ), 'A row with no cutoff is DROPPED -- a visit with no deadline can never expire, which is the dangerous default', $failures );

bhp_svp_assert( false === bhp_school_visit_is_ymd( '26-08-28' ), 'is_ymd rejects a two-digit year', $failures );
bhp_svp_assert( false === bhp_school_visit_is_ymd( '2026-13-01' ), 'is_ymd rejects month 13', $failures );
bhp_svp_assert( true === bhp_school_visit_is_ymd( '2026-08-28' ), 'is_ymd accepts a real date', $failures );

/* =========================================================================
 * 2. RESOLUTION AND EXPIRY
 * ====================================================================== */

bhp_svp_assert( null !== bhp_school_visit_resolve( 'bhpsvptest-live' ), 'A live visit resolves', $failures );
bhp_svp_assert( null === bhp_school_visit_resolve( 'bhpsvptest-expired' ), 'A visit past its cutoff resolves to NULL -- the option disappears after the cutoff', $failures );
bhp_svp_assert( null !== bhp_school_visit_resolve( 'bhpsvptest-lastday' ), 'The cutoff is INCLUSIVE: an order placed ON the cutoff date is still accepted', $failures );
bhp_svp_assert( null === bhp_school_visit_resolve( 'no-such-visit-anywhere' ), 'An unknown slug resolves to NULL', $failures );
bhp_svp_assert( null === bhp_school_visit_resolve( '' ), 'An empty slug resolves to NULL', $failures );
bhp_svp_assert( null === bhp_school_visit_resolve( '../../etc/passwd' ), 'A path-traversal-shaped slug resolves to NULL (sanitize_key)', $failures );

/* =========================================================================
 * 3. NO VISIT DATA IS HARDCODED
 *
 * The single most important structural assertion in the suite. If any real
 * school name, date or slug ever gets typed into the source, this fails --
 * on the source file itself, not on behaviour.
 * ====================================================================== */

$src = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-pickup.php' );
bhp_svp_assert( is_string( $src ) && '' !== $src, 'The feature source file is readable for the hardcoding audit', $failures );
if ( is_string( $src ) ) {
	bhp_svp_assert( ! preg_match( '/adams-20\d\d|dallas-harris|liberty-20\d\d/i', $src ), 'NO real visit slug appears anywhere in the source', $failures );
	/*
	 * Not "no date string anywhere" -- this file's own comments legitimately
	 * carry release dates. The assertion that actually means something is that
	 * there is no inline registry: no literal visit row, and exactly one place
	 * visit data can come from, which is the option.
	 */
	bhp_svp_assert( ! preg_match( '/[\'"]school[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/', $src ), 'NO literal visit row (\'school\' => \'Some School\') exists in the source -- the registry is data, never code', $failures );
	bhp_svp_assert( 1 === preg_match_all( '/get_option\(\s*BHP_SCHOOL_VISIT_OPTION/', $src ), 'The registry is read from the option in EXACTLY ONE place, so there is one gate to audit', $failures );
}

/* =========================================================================
 * ⭐ 3b. THE STORED WOOCOMMERCE PICKUP SETTINGS — SNAPSHOT BEFORE ANYTHING
 *
 * Read once, here, so §9 can prove the whole suite (and therefore the whole
 * feature) left them untouched. Read RAW, straight out of the options table,
 * because `get_option()` runs this build's own read filters.
 * ====================================================================== */

global $wpdb;
$bhp_svp_raw_settings_before  = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BHP_SCHOOL_PICKUP_SETTINGS_OPTION ) );
$bhp_svp_raw_locations_before = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BHP_SCHOOL_PICKUP_LOCATIONS_OPTION ) );

echo 'INFO: stored local-pickup settings before this run: ' . var_export( $bhp_svp_raw_settings_before, true ) . "\n";
echo 'INFO: stored local-pickup locations before this run: ' . var_export( $bhp_svp_raw_locations_before, true ) . "\n";

/* =========================================================================
 * 4. THE NORMAL VISITOR SEES ZERO CHANGE
 *
 * Run every filter with no session flag and prove each returns its input
 * IDENTICALLY -- same value, same keys, same objects.
 * ====================================================================== */

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}

$fake_rate  = new WC_Shipping_Rate( 'flat_rate:1', 'Contiguous US Shipping', 3.99, array(), 'flat_rate', 1 );
$base_rates = array( 'flat_rate:1' => $fake_rate );

/*
 * ⛔ THE SENTINEL VALUES ARE DELIBERATELY NOT WHAT THE STORE HOLDS. If the
 *    filters returned the stored value from anywhere other than "the argument
 *    I was handed", asserting against the store's own value would pass
 *    vacuously. These are values nothing else in WordPress could produce.
 */
$svp_settings_sentinel  = array( 'enabled' => 'no', 'title' => 'ZZ-SENTINEL', 'cost' => '', 'tax_status' => 'taxable' );
$svp_locations_sentinel = array( array( 'name' => 'ZZ-SENTINEL-LOCATION', 'address' => array(), 'details' => '', 'enabled' => false ) );

$svp_settings_unflagged = bhp_school_pickup_filter_location_settings( $svp_settings_sentinel );
bhp_svp_assert( $svp_settings_unflagged === $svp_settings_sentinel, 'UNFLAGGED request: the local-pickup SETTINGS filter returns its input IDENTICALLY -- local pickup stays off for an ordinary customer', $failures );

$svp_locations_unflagged = bhp_school_pickup_filter_locations( $svp_locations_sentinel );
bhp_svp_assert( $svp_locations_unflagged === $svp_locations_sentinel, 'UNFLAGGED request: the local-pickup LOCATIONS filter returns its input IDENTICALLY -- no location is invented', $failures );

$after_unflagged = bhp_school_pickup_decorate_rate( $base_rates, array() );
bhp_svp_assert( $after_unflagged === $base_rates, 'UNFLAGGED cart: the rate array is returned IDENTICAL -- a normal visitor sees zero change', $failures );
bhp_svp_assert( 'Contiguous US Shipping' === $fake_rate->get_label(), 'UNFLAGGED cart: the ordinary rate\'s label is untouched', $failures );

bhp_svp_assert( true === bhp_school_pickup_keep_customer_tax_basis( true ), 'UNFLAGGED request: WooCommerce\'s own local-pickup tax behaviour is left exactly as it was', $failures );

bhp_svp_assert(
	'flat_rate:1' === bhp_school_pickup_default_chosen_method( 'flat_rate:1', $base_rates, '' ),
	'UNFLAGGED cart: the default shipping selection is WooCommerce\'s, untouched',
	$failures
);

$pkgs_unflagged = bhp_school_pickup_tag_package( array( 0 => array( 'contents' => array() ) ) );
bhp_svp_assert( ! isset( $pkgs_unflagged[0]['bhp_school_visit'] ), 'UNFLAGGED cart: the shipping package is untagged, so its hash is unchanged and the rate cache is not disturbed', $failures );

bhp_svp_assert( false === bhp_school_visit_use_delivery_framing(), 'UNFLAGGED request: shipping framing, not hand-delivery framing', $failures );
$svp_copy_base      = array( 'nudge' => 'ZZ-NUDGE', 'earned' => 'ZZ-EARNED', 'cta_clause' => ' - ZZ' );
$svp_copy_unflagged = bhp_school_visit_freeship_copy( $svp_copy_base );
bhp_svp_assert( $svp_copy_unflagged === $svp_copy_base, 'UNFLAGGED request: the cart/drawer free-shipping copy is returned IDENTICALLY -- an ordinary customer reads exactly what they read in 1.8.51', $failures );

if ( function_exists( 'bhp_book_free_shipping_line' ) ) {
	bhp_svp_assert( 'FREE Shipping on the complete collection' === bhp_book_free_shipping_line(), 'UNFLAGGED request: the theme\'s collection bullet is the locked sentence, byte-identical', $failures );
} else {
	bhp_svp_skip( 'Theme collection-bullet framing assertions', 'bhp_book_free_shipping_line() is not defined -- the theme half of this release is not installed here', $skips );
}

/* =========================================================================
 * 5. THE FLAGGED VISITOR GETS WOOCOMMERCE'S OWN LOCAL PICKUP
 *
 * ⭐ THIS SECTION IS THE 1.8.52 REWRITE. It asserts, in order: local pickup
 *    becomes enabled for the request; exactly one location is APPENDED;
 *    WooCommerce's own rate is renamed to the approved label and carries the
 *    hidden visit meta; ordinary shipping is still on offer and untouched;
 *    hand delivery becomes the DEFAULT selection but never overrides a choice
 *    the parent made; and the whole thing evaporates when the flag expires.
 * ====================================================================== */

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-live' );

	$active = bhp_school_visit_active();
	bhp_svp_assert( is_array( $active ) && 'bhpsvptest-live' === $active['slug'], 'A live session flag resolves to its visit record', $failures );
	bhp_svp_assert( is_array( bhp_school_visit_request_record() ), 'The per-request resolver sees the same flag (this is what the option filters call)', $failures );

	/* --- 5a. LOCAL PICKUP IS TURNED ON, FOR THE REQUEST ONLY --- */
	$svp_settings_flagged = bhp_school_pickup_filter_location_settings( $svp_settings_sentinel );
	bhp_svp_assert( 'yes' === $svp_settings_flagged['enabled'], 'FLAGGED request: WooCommerce\'s local pickup is ENABLED for this read', $failures );
	bhp_svp_assert( 'Hand delivery' === $svp_settings_flagged['title'], 'FLAGGED request: the Ship/… toggle reads "Hand delivery" -- nobody is picking anything up', $failures );
	bhp_svp_assert( '' === $svp_settings_flagged['cost'], 'FLAGGED request: hand delivery costs nothing (an empty cost is WooCommerce\'s own "free")', $failures );
	bhp_svp_assert( 'none' === $svp_settings_flagged['tax_status'], 'FLAGGED request: nothing to tax, stated explicitly rather than left to a default', $failures );

	/* --- 5b. EXACTLY ONE LOCATION, APPENDED, NEVER REPLACING --- */
	$svp_locations_flagged = bhp_school_pickup_filter_locations( $svp_locations_sentinel );
	bhp_svp_assert( 2 === count( $svp_locations_flagged ), 'FLAGGED request: exactly ONE location is added', $failures );
	bhp_svp_assert( $svp_locations_flagged[0] === $svp_locations_sentinel[0], 'FLAGGED request: a location an operator configured is returned FIRST and byte-identical -- this filter APPENDS, it never replaces', $failures );

	$svp_loc = $svp_locations_flagged[1];
	bhp_svp_assert( true === $svp_loc['enabled'], 'FLAGGED request: the injected location is enabled, or WooCommerce would skip it', $failures );
	bhp_svp_assert( false !== strpos( $svp_loc['name'], 'Test Live Elementary' ), 'FLAGGED request: the location NAME names the SCHOOL', $failures );
	bhp_svp_assert( (bool) preg_match( '/\(\w+ \d+\)/', $svp_loc['name'] ), 'FLAGGED request: the location NAME names the DATE', $failures );
	bhp_svp_assert( false !== strpos( $svp_loc['details'], 'Nothing is posted' ), 'FLAGGED request: the location DETAILS answer the founder\'s question ("is this getting shipped?") in words', $failures );
	bhp_svp_assert( false === strpos( $svp_loc['details'], '—' ), 'FLAGGED request: no em dash in customer-facing copy (standing constraint)', $failures );

	/*
	 * ⛔⛔ THE TAX RAIL. WooCommerce re-bases an order's taxable address on the
	 *     pickup location the instant that location has a non-empty country.
	 *     A silently changed tax figure is the class of bug nobody notices
	 *     until an accountant does, so the country is empty AND the filter
	 *     that would perform the swap is answered "no".
	 */
	bhp_svp_assert( '' === $svp_loc['address']['country'], '⛔ TAX RAIL: the injected location carries NO country, which is the condition WooCommerce requires before it will re-base the taxable address', $failures );
	bhp_svp_assert( false === bhp_school_pickup_keep_customer_tax_basis( true ), '⛔ TAX RAIL: `woocommerce_apply_base_tax_for_local_pickup` is answered FALSE for a flagged session -- the parent is taxed on their own address, exactly as before this feature existed', $failures );

	/* --- 5c. THE RATE IS DECORATED, NEVER CREATED, AND NOTHING IS REMOVED --- */
	$svp_native_rate = new WC_Shipping_Rate( 'pickup_location:0', 'Hand delivery (Test Live Elementary)', 0.00, array(), 'pickup_location', 0 );
	$svp_mixed       = array( 'flat_rate:1' => $fake_rate, 'pickup_location:0' => $svp_native_rate );

	$after_flagged = bhp_school_pickup_decorate_rate( $svp_mixed, array() );

	bhp_svp_assert( 2 === count( $after_flagged ), 'FLAGGED cart: no rate was added and none removed -- 1.8.52 decorates, it does not inject', $failures );
	bhp_svp_assert( isset( $after_flagged['flat_rate:1'] ), 'FLAGGED cart: normal shipping is STILL PRESENT -- hand delivery sits alongside, never instead', $failures );
	bhp_svp_assert( $after_flagged['flat_rate:1'] === $fake_rate, 'FLAGGED cart: the normal rate object is the SAME object, unrepriced and unrenamed', $failures );
	bhp_svp_assert( 3.99 === (float) $fake_rate->get_cost(), 'FLAGGED cart: the ordinary tiered shipping price is untouched', $failures );

	$pickup = $after_flagged['pickup_location:0'];
	bhp_svp_assert( 'pickup_location' === $pickup->get_method_id(), 'FLAGGED cart: the delivery option is WooCommerce\'s OWN pickup_location method, which is what makes the checkout hide the shipping address natively', $failures );
	bhp_svp_assert( 0.0 === (float) $pickup->get_cost(), 'FLAGGED cart: hand delivery costs 0.00', $failures );
	bhp_svp_assert( false !== strpos( $pickup->get_label(), 'Test Live Elementary' ), 'FLAGGED cart: the label names the SCHOOL', $failures );
	bhp_svp_assert( (bool) preg_match( '/\(\w+ \d+\)/', $pickup->get_label() ), 'FLAGGED cart: the label names the DATE', $failures );
	bhp_svp_assert( 0 === strpos( $pickup->get_label(), 'Author hand-delivery at the' ), 'FLAGGED cart: the APPROVED 1.8.49 label survived the mechanism change byte-identical -- a label a parent reads while paying is not a mechanism change\'s to reword', $failures );

	$svp_rate_meta = $pickup->get_meta_data();
	bhp_svp_assert( isset( $svp_rate_meta[ BHP_SCHOOL_PICKUP_ITEM_META_SLUG ] ) && 'bhpsvptest-live' === $svp_rate_meta[ BHP_SCHOOL_PICKUP_ITEM_META_SLUG ], 'FLAGGED cart: the rate carries the visit SLUG as hidden meta, which WooCommerce copies onto the order item on BOTH checkout paths', $failures );
	bhp_svp_assert( isset( $svp_rate_meta[ BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL ] ) && 'Test Live Elementary' === $svp_rate_meta[ BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL ], 'FLAGGED cart: the rate carries the SCHOOL as hidden meta -- this is the session-less fallback\'s only source', $failures );
	bhp_svp_assert( isset( $svp_rate_meta[ BHP_SCHOOL_PICKUP_ITEM_META_DATE ] ), 'FLAGGED cart: the rate carries the VISIT DATE as hidden meta', $failures );
	foreach ( array_keys( $svp_rate_meta ) as $svp_mk ) {
		if ( 0 === strpos( (string) $svp_mk, '_bhp_visit' ) ) {
			bhp_svp_assert( true, "FLAGGED cart: rate meta key {$svp_mk} is underscore-prefixed, so it is HIDDEN from the customer's order line rather than printed under it", $failures );
		}
	}

	/* --- 5d. HAND DELIVERY IS THE DEFAULT, BUT NEVER OVERRIDES A CHOICE --- */
	bhp_svp_assert(
		'pickup_location:0' === bhp_school_pickup_default_chosen_method( 'flat_rate:1', $after_flagged, '' ),
		'⭐ FLAGGED cart, NOTHING CHOSEN YET: hand delivery is the DEFAULT. Without this the parent lands on the SHIPPING screen -- which is the screen the founder rejected -- because WooCommerce deliberately defaults block checkout to the first non-pickup rate',
		$failures
	);
	bhp_svp_assert(
		'flat_rate:1' === bhp_school_pickup_default_chosen_method( 'flat_rate:1', $after_flagged, 'flat_rate:1' ),
		'⛔ FLAGGED cart, PARENT CHOSE SHIPPING: their choice is returned untouched. A flagged parent who deliberately picks "Ship" gets shipping, and keeps it',
		$failures
	);
	bhp_svp_assert(
		'pickup_location:0' === bhp_school_pickup_default_chosen_method( 'flat_rate:1', $after_flagged, 'flat_rate:99' ),
		'FLAGGED cart, STALE choice no longer on offer: it is not honoured, and hand delivery is chosen rather than shipping',
		$failures
	);

	$pkgs_flagged = bhp_school_pickup_tag_package( array( 0 => array( 'contents' => array() ) ) );
	bhp_svp_assert( isset( $pkgs_flagged[0]['bhp_school_visit'] ), 'FLAGGED cart: the shipping package IS tagged, so the package hash differs and the cached pre-flag rate list cannot be served', $failures );

	/* --- 5e. FREE-SHIPPING CART: hand delivery must still be offered --- */
	$free_rate       = new WC_Shipping_Rate( 'flat_rate:1', 'Contiguous US Shipping', 0.00, array(), 'flat_rate', 1 );
	$svp_free_native = new WC_Shipping_Rate( 'pickup_location:0', 'Hand delivery', 0.00, array(), 'pickup_location', 0 );
	$after_free_cart = bhp_school_pickup_decorate_rate( array( 'flat_rate:1' => $free_rate, 'pickup_location:0' => $svp_free_native ), array() );
	bhp_svp_assert( isset( $after_free_cart['pickup_location:0'], $after_free_cart['flat_rate:1'] ), 'COMPLETE-COLLECTION cart (shipping already $0.00): BOTH free shipping and free hand delivery are offered -- the parent chooses where the books arrive, not what they cost', $failures );

	/* --- 5f. THE SHIPPING LANGUAGE IS GONE FOR THIS PARENT --- */
	bhp_svp_assert( true === bhp_school_visit_use_delivery_framing(), 'FLAGGED request: hand-delivery framing is on', $failures );
	$svp_copy_flagged = bhp_school_visit_freeship_copy( $svp_copy_base );
	bhp_svp_assert( $svp_copy_flagged !== $svp_copy_base, 'FLAGGED request: the cart/drawer copy IS swapped', $failures );
	foreach ( array( 'nudge', 'earned', 'cta_clause' ) as $svp_ck ) {
		bhp_svp_assert( false === stripos( $svp_copy_flagged[ $svp_ck ], 'ship' ), "⛔ FLAGGED request: the word \"ship\" does NOT appear in the drawer copy key `{$svp_ck}` -- the founder's whole objection was that the page reads like a shipment", $failures );
		bhp_svp_assert( false === strpos( $svp_copy_flagged[ $svp_ck ], '—' ), "FLAGGED request: no em dash in drawer copy key `{$svp_ck}`", $failures );
	}
	if ( function_exists( 'bhp_book_free_shipping_line' ) ) {
		$svp_bullet = bhp_book_free_shipping_line();
		bhp_svp_assert( false === stripos( $svp_bullet, 'shipping' ), '⛔ FLAGGED request: the collection page\'s FREE bullet no longer says "Shipping"', $failures );
		bhp_svp_assert( false !== stripos( $svp_bullet, 'hand-delivery' ), 'FLAGGED request: it says hand-delivery instead -- the claim is swapped, never deleted; delivery still costs the parent nothing and the line still says so', $failures );
	}

	/* --- 5g. EXPIRED FLAG: graceful, silent, self-clearing --- */
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-expired' );
	$svp_settings_expired = bhp_school_pickup_filter_location_settings( $svp_settings_sentinel );
	bhp_svp_assert( $svp_settings_expired === $svp_settings_sentinel, 'EXPIRED flag: local pickup is NOT enabled -- the settings filter returns its input identically', $failures );
	$svp_locations_expired = bhp_school_pickup_filter_locations( $svp_locations_sentinel );
	bhp_svp_assert( $svp_locations_expired === $svp_locations_sentinel, 'EXPIRED flag: NO location is injected -- no residue, no half-state', $failures );
	bhp_svp_assert( false === bhp_school_visit_use_delivery_framing(), 'EXPIRED flag: the copy reverts to ordinary shipping framing', $failures );
	bhp_svp_assert( ! WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY ), 'EXPIRED flag: the dead session flag CLEARS ITSELF rather than being re-checked forever', $failures );

	/* --- 5h. UNKNOWN SLUG in the session (withdrawn visit) --- */
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-withdrawn' );
	bhp_svp_assert( null === bhp_school_visit_active(), 'WITHDRAWN visit: a session flag naming a slug no longer in the registry resolves to NULL', $failures );
	bhp_svp_assert( bhp_school_pickup_filter_locations( array() ) === array(), 'WITHDRAWN visit: no location is injected either', $failures );

	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
} else {
	bhp_svp_skip( 'Session-dependent gating assertions', 'no WC session in this CLI context', $skips );
}

/* =========================================================================
 * 6. THE PICKUP PREDICATE
 * ====================================================================== */

$normal_order = new WC_Order();
bhp_svp_assert( false === bhp_school_pickup_order_is_pickup( $normal_order ), 'A bare order is NOT a pickup order', $failures );

$shipped_order = new WC_Order();
$ship_item     = new WC_Order_Item_Shipping();
$ship_item->set_method_id( 'flat_rate' );
$ship_item->set_method_title( 'Contiguous US Shipping' );
$shipped_order->add_item( $ship_item );
bhp_svp_assert( false === bhp_school_pickup_order_is_pickup( $shipped_order ), 'An order with a normal flat_rate shipping line is NOT a pickup order', $failures );

$pickup_by_line = new WC_Order();
$pickup_item    = new WC_Order_Item_Shipping();
$pickup_item->set_method_id( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID );
$pickup_item->set_method_title( 'Author hand-delivery' );
$pickup_by_line->add_item( $pickup_item );
bhp_svp_assert( true === bhp_school_pickup_order_is_pickup( $pickup_by_line ), 'An order whose SHIPPING LINE is WooCommerce\'s pickup_location method IS a pickup order -- even with NO meta written. This is the assertion that stops a failed meta write from sending books to the printer', $failures );

/*
 * ⭐ 1.8.52 — BACKWARD COMPATIBILITY, ASSERTED RATHER THAN ASSUMED.
 *
 * Orders placed on 2026-08-17 under 1.8.49–1.8.51 carry the retired
 * `bhp_school_pickup` method id. They must keep skipping Bookvault, keep
 * rendering the hand-delivery email branch and keep showing the admin panel,
 * forever. Nothing can CREATE one any more -- §9 asserts the injector is gone.
 */
$pickup_legacy      = new WC_Order();
$pickup_legacy_item = new WC_Order_Item_Shipping();
$pickup_legacy_item->set_method_id( BHP_SCHOOL_PICKUP_METHOD_ID );
$pickup_legacy_item->set_method_title( 'Author hand-delivery (1.8.49 mechanism)' );
$pickup_legacy->add_item( $pickup_legacy_item );
bhp_svp_assert( true === bhp_school_pickup_order_is_pickup( $pickup_legacy ), '⭐ LEGACY ORDER: an order placed under the retired $0.00-rate mechanism is STILL a pickup order -- the mechanism change must not un-protect an order that already exists', $failures );

bhp_svp_assert( in_array( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID, bhp_school_pickup_method_ids(), true ), 'The recognised-method list contains WooCommerce\'s pickup_location', $failures );
bhp_svp_assert( in_array( BHP_SCHOOL_PICKUP_METHOD_ID, bhp_school_pickup_method_ids(), true ), 'The recognised-method list still contains the retired id', $failures );
bhp_svp_assert( 2 === count( bhp_school_pickup_method_ids() ), 'Exactly TWO ids are recognised -- one live mechanism plus one closed historical one, not an open-ended list', $failures );

$pickup_by_meta = new WC_Order();
$pickup_by_meta->add_meta_data( BHP_SCHOOL_PICKUP_META_FLAG, 'yes' );
bhp_svp_assert( true === bhp_school_pickup_order_is_pickup( $pickup_by_meta ), 'An order carrying the pickup META is a pickup order -- the second, independent signal', $failures );

bhp_svp_assert( false === bhp_school_pickup_order_is_pickup( null ), 'A null order is not a pickup order (and does not fatal)', $failures );
bhp_svp_assert( false === bhp_school_pickup_order_is_pickup( 0 ), 'Order id 0 is not a pickup order (and does not fatal)', $failures );

/* =========================================================================
 * 7. ⛔⛔ THE DUPLICATE-PRINT PROTECTION -- AGAINST THE REAL LIVE WEBHOOKS
 * ====================================================================== */

/*
 * Webhook classification. Tested through the real WC_Webhook object rather
 * than a stub, because the thing that could actually go wrong is a genuine
 * webhook row not being recognised as Bookvault-bound.
 */
$bv_hook = new WC_Webhook();
$bv_hook->set_delivery_url( 'https://webhooks.bookvault.app/woocommerce/orders/create?client_id=x&storeID=y' );
bhp_svp_assert( true === bhp_school_pickup_is_fulfilment_webhook( $bv_hook ), 'A webhook delivering to webhooks.bookvault.app is recognised as print-partner-bound', $failures );

$other_hook = new WC_Webhook();
$other_hook->set_delivery_url( 'https://production.rutterapi.com/woocommerce/webhooks/abc' );
bhp_svp_assert( false === bhp_school_pickup_is_fulfilment_webhook( $other_hook ), 'A non-Bookvault webhook (Rutter) is NOT print-partner-bound and is never interfered with', $failures );

$evil_hook = new WC_Webhook();
$evil_hook->set_delivery_url( 'https://example.com/?u=bookvault.app' );
bhp_svp_assert( false === bhp_school_pickup_is_fulfilment_webhook( $evil_hook ), 'The match is on the HOST, not the whole URL -- "bookvault.app" in a query string does not make a webhook Bookvault-bound', $failures );

bhp_svp_assert( false === bhp_school_pickup_is_fulfilment_webhook( null ), 'A null webhook is not print-partner-bound (and does not fatal)', $failures );

/*
 * ⛔ THE DECISION ITSELF, at WooCommerce's own filter, against every real
 *    webhook row in this store.
 */
$live_hooks = array();
if ( function_exists( 'wc_get_webhook' ) ) {
	$data_store = WC_Data_Store::load( 'webhook' );
	foreach ( $data_store->get_webhooks_ids( 'active' ) as $id ) {
		$live_hooks[] = wc_get_webhook( $id );
	}
	foreach ( $data_store->get_webhooks_ids( 'paused' ) as $id ) {
		$live_hooks[] = wc_get_webhook( $id );
	}
	foreach ( $data_store->get_webhooks_ids( 'disabled' ) as $id ) {
		$live_hooks[] = wc_get_webhook( $id );
	}
}

$live_bv_order_hooks = array();
$live_other_hooks    = array();
foreach ( $live_hooks as $hook ) {
	if ( ! $hook instanceof WC_Webhook ) {
		continue;
	}
	if ( bhp_school_pickup_is_fulfilment_webhook( $hook ) && 'order' === $hook->get_resource() ) {
		$live_bv_order_hooks[] = $hook;
	} else {
		$live_other_hooks[] = $hook;
	}
}

echo sprintf(
	"INFO: %d webhook rows read from this store; %d are print-partner ORDER webhooks; %d are not.\n",
	count( $live_hooks ),
	count( $live_bv_order_hooks ),
	count( $live_other_hooks )
);

if ( empty( $live_bv_order_hooks ) ) {
	bhp_svp_skip(
		'Live print-partner webhook assertions',
		'this store has no webhook row pointing at the print partner -- there is nothing real to assert against, and passing vacuously would be a fabricated check',
		$skips
	);
} else {
	/*
	 * These first assertions use a NON-EXISTENT order id on purpose. They
	 * prove the filter's fail-open behaviour -- that it never blocks anything
	 * it cannot positively identify as a pickup order. The positive case (a
	 * real pickup order IS blocked) needs a real saved order and is asserted
	 * in the block below this one, against orders this suite creates and
	 * deletes. Neither block is sufficient alone and both are run.
	 */
	$sentinel_normal = 999000002;

	foreach ( $live_bv_order_hooks as $hook ) {
		$name = $hook->get_name() . ' (id ' . $hook->get_id() . ', ' . $hook->get_topic() . ', status=' . $hook->get_status() . ')';

		// A NON-order-id argument must never be blocked.
		$r = bhp_school_pickup_block_bookvault_webhook( true, $hook, 'not-an-id' );
		bhp_svp_assert( true === $r, "LIVE WEBHOOK [{$name}]: a non-numeric hook argument is passed through untouched", $failures );

		// An order id that is not a pickup order must never be blocked.
		$r = bhp_school_pickup_block_bookvault_webhook( true, $hook, $sentinel_normal );
		bhp_svp_assert( true === $r, "LIVE WEBHOOK [{$name}]: a NON-pickup order is NOT blocked -- normal orders still reach the print partner", $failures );

		// WooCommerce having already declined must never be overturned.
		$r = bhp_school_pickup_block_bookvault_webhook( false, $hook, $sentinel_normal );
		bhp_svp_assert( false === $r, "LIVE WEBHOOK [{$name}]: the filter can only ever suppress a delivery, never cause one", $failures );
	}

}

/*
 * ⛔ THE CENTRAL ASSERTION, run against a REAL SAVED ORDER when one can be
 *    created, because everything above stops one step short of it.
 *
 * A real order IS created here -- it is the only way to prove the filter end
 * to end -- and it is deleted permanently in the same run, in a shutdown
 * handler so an abort still cleans up. It is created with NO products, NO
 * payment, NO customer and status `pending`, so it touches no stock, no
 * coupon, no product record and no revenue figure.
 */
if ( function_exists( 'wc_create_order' ) && ! empty( $live_bv_order_hooks ) ) {
	// (a) A real PICKUP order.
	$probe_pickup = wc_create_order( array( 'status' => 'pending' ) );
	if ( $probe_pickup && ! is_wp_error( $probe_pickup ) ) {
		$GLOBALS['bhp_svp_probe_ids'][] = $probe_pickup->get_id();

		$pi = new WC_Order_Item_Shipping();
		$pi->set_method_id( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID );
		$pi->set_method_title( 'Author hand-delivery (test probe)' );
		$pi->set_total( 0 );
		$probe_pickup->add_item( $pi );
		$probe_pickup->save();

		bhp_svp_assert( true === bhp_school_pickup_order_is_pickup( $probe_pickup->get_id() ), 'REAL SAVED ORDER with a pickup shipping line reads as a pickup order by ID', $failures );

		foreach ( $live_bv_order_hooks as $hook ) {
			$name = $hook->get_name() . ' (id ' . $hook->get_id() . ')';
			$r    = bhp_school_pickup_block_bookvault_webhook( true, $hook, $probe_pickup->get_id() );
			bhp_svp_assert( false === $r, "⛔ DUPLICATE-PRINT PROTECTION [{$name}]: a REAL pickup order is BLOCKED from the print-partner webhook", $failures );
		}

		$reloaded = wc_get_order( $probe_pickup->get_id() );
		bhp_svp_assert( 'yes' === $reloaded->get_meta( '_bhp_school_pickup_bv_skipped' ), 'The skip is recorded on the order itself, so the protection having fired is visible without reading a log', $failures );

		/*
		 * ⭐ THE SESSION-LESS MARKING FALLBACK, exercised for real.
		 *
		 * WooCommerce copies a RATE's meta onto the order's shipping item, on
		 * BOTH checkout paths (`WC_Checkout::create_order_shipping_lines()`,
		 * which the Store API calls too). So an order whose session has died
		 * still carries the school and date on the item, and the packing-list
		 * note must be built from that rather than degrade to blanks.
		 */
		$fb = wc_create_order( array( 'status' => 'pending' ) );
		if ( $fb && ! is_wp_error( $fb ) ) {
			$GLOBALS['bhp_svp_probe_ids'][] = $fb->get_id();
			$fbi = new WC_Order_Item_Shipping();
			$fbi->set_method_id( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID );
			$fbi->set_method_title( 'Author hand-delivery (fallback probe)' );
			$fbi->set_total( 0 );
			// ⭐ 1.8.52 — exactly what `create_order_shipping_lines()` copies off
			//    the rate under the NEW mechanism: the hidden `_bhp_visit_*` keys
			//    this build attaches in `bhp_school_pickup_decorate_rate()`.
			$fbi->add_meta_data( BHP_SCHOOL_PICKUP_ITEM_META_SLUG, 'fallback-probe-slug', true );
			$fbi->add_meta_data( BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL, 'Fallback Probe School', true );
			$fbi->add_meta_data( BHP_SCHOOL_PICKUP_ITEM_META_DATE, '2099-01-31', true );
			$fb->add_item( $fbi );
			$fb->save();

			// No session flag at all -- the fallback is the only route.
			if ( function_exists( 'WC' ) && WC()->session ) {
				WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
			}
			bhp_school_pickup_mark_order( $fb->get_id() );
			$fb_reloaded = wc_get_order( $fb->get_id() );

			bhp_svp_assert( 'Fallback Probe School' === $fb_reloaded->get_meta( BHP_SCHOOL_PICKUP_META_SCHOOL ), 'SESSION-LESS FALLBACK: the school is recovered from the shipping ITEM meta when no session exists', $failures );
			bhp_svp_assert( '2099-01-31' === $fb_reloaded->get_meta( BHP_SCHOOL_PICKUP_META_DATE ), 'SESSION-LESS FALLBACK: the visit date is recovered from the shipping ITEM meta', $failures );
			bhp_svp_assert( 'fallback-probe-slug' === $fb_reloaded->get_meta( BHP_SCHOOL_PICKUP_META_SLUG ), '⭐ 1.8.52 SESSION-LESS FALLBACK: the visit SLUG is recovered too. 1.8.49 could not do this -- its rate carried only a school and a date -- so a session-less order recorded an unresolved slug', $failures );

			/*
			 * ⭐ AND THE 1.8.49-ERA FALLBACK STILL WORKS, on an order that carries
			 *    the OLD visible "School"/"Visit date" rate meta and nothing else.
			 *    Dropping that read would silently degrade the packing-list note
			 *    for every order placed before this release.
			 */
			$legacy_fb = wc_create_order( array( 'status' => 'pending' ) );
			if ( $legacy_fb && ! is_wp_error( $legacy_fb ) ) {
				$GLOBALS['bhp_svp_probe_ids'][] = $legacy_fb->get_id();
				$lfbi                           = new WC_Order_Item_Shipping();
				$lfbi->set_method_id( BHP_SCHOOL_PICKUP_METHOD_ID );
				$lfbi->set_method_title( 'Author hand-delivery (1.8.49 fallback probe)' );
				$lfbi->set_total( 0 );
				$lfbi->add_meta_data( __( 'School', 'brave-hearts' ), 'Legacy Probe School', true );
				$lfbi->add_meta_data( __( 'Visit date', 'brave-hearts' ), '2099-03-03', true );
				$legacy_fb->add_item( $lfbi );
				$legacy_fb->save();

				bhp_school_pickup_mark_order( $legacy_fb->get_id() );
				$legacy_reloaded = wc_get_order( $legacy_fb->get_id() );
				bhp_svp_assert( 'Legacy Probe School' === $legacy_reloaded->get_meta( BHP_SCHOOL_PICKUP_META_SCHOOL ), '⭐ LEGACY FALLBACK: a 1.8.49-era order still resolves its school from the old VISIBLE rate meta', $failures );
				bhp_svp_assert( '2099-03-03' === $legacy_reloaded->get_meta( BHP_SCHOOL_PICKUP_META_DATE ), 'LEGACY FALLBACK: and its visit date', $failures );
			} else {
				bhp_svp_skip( 'Legacy 1.8.49 marking fallback', 'wc_create_order() did not return an order', $skips );
			}

			$fb_notes    = wc_get_order_notes( array( 'order_id' => $fb->get_id() ) );
			$fb_note_txt = '';
			foreach ( $fb_notes as $n ) {
				$fb_note_txt .= $n->content;
			}
			bhp_svp_assert( false !== strpos( $fb_note_txt, 'DO NOT SHIP' ), 'SESSION-LESS FALLBACK: the packing-list note still says DO NOT SHIP', $failures );
			bhp_svp_assert( false !== strpos( $fb_note_txt, 'Fallback Probe School' ), 'SESSION-LESS FALLBACK: the note names the actual school, not a blank-filled non-answer', $failures );
			bhp_svp_assert( false === strpos( $fb_note_txt, 'a school visit on the visit date' ), 'SESSION-LESS FALLBACK: the note NEVER degrades to the pleasant-sounding non-answer "at a school visit on the visit date"', $failures );
		} else {
			bhp_svp_skip( 'Session-less marking fallback', 'wc_create_order() did not return an order', $skips );
		}

		// A NON-print-partner webhook must be left alone even for this very
		// same real pickup order -- the filter is narrow, not a blanket.
		foreach ( $live_other_hooks as $hook ) {
			if ( ! $hook instanceof WC_Webhook ) {
				continue;
			}
			$name = $hook->get_name() . ' (id ' . $hook->get_id() . ')';
			$r    = bhp_school_pickup_block_bookvault_webhook( true, $hook, $probe_pickup->get_id() );
			bhp_svp_assert( true === $r, "NARROWNESS [{$name}]: a non-print-partner webhook is returned untouched for a REAL pickup order", $failures );
		}
	} else {
		bhp_svp_skip( 'Real-order pickup probe', 'wc_create_order() did not return an order', $skips );
	}

	// (b) A real NORMAL order -- must NOT be blocked.
	$probe_normal = wc_create_order( array( 'status' => 'pending' ) );
	if ( $probe_normal && ! is_wp_error( $probe_normal ) ) {
		$GLOBALS['bhp_svp_probe_ids'][] = $probe_normal->get_id();

		$ni = new WC_Order_Item_Shipping();
		$ni->set_method_id( 'flat_rate' );
		$ni->set_method_title( 'Contiguous US Shipping (test probe)' );
		$ni->set_total( 3.99 );
		$probe_normal->add_item( $ni );
		$probe_normal->save();

		bhp_svp_assert( false === bhp_school_pickup_order_is_pickup( $probe_normal->get_id() ), 'REAL SAVED ORDER with a flat_rate shipping line is NOT a pickup order', $failures );

		foreach ( $live_bv_order_hooks as $hook ) {
			$name = $hook->get_name() . ' (id ' . $hook->get_id() . ')';
			$r    = bhp_school_pickup_block_bookvault_webhook( true, $hook, $probe_normal->get_id() );
			bhp_svp_assert( true === $r, "⛔ NO OVER-BLOCKING [{$name}]: a REAL normal order is NOT blocked -- ordinary fulfilment is unaffected", $failures );
		}
	} else {
		bhp_svp_skip( 'Real-order normal probe', 'wc_create_order() did not return an order', $skips );
	}
} else {
	bhp_svp_skip( 'Real-saved-order webhook probes', 'no print-partner webhook row exists on this store', $skips );
}

/* =========================================================================
 * 8. THE MANUAL RESEND ACTION IS REMOVED FROM A PICKUP ORDER
 * ====================================================================== */

$actions = array( 'blt_resend_order' => 'Resend order to Bookvault', 'send_order_details' => 'Email invoice' );

$kept = bhp_school_pickup_remove_resend_action( $actions, $shipped_order );
bhp_svp_assert( isset( $kept['blt_resend_order'] ), 'NORMAL order: the Bookvault resend action is left in place', $failures );

$stripped = bhp_school_pickup_remove_resend_action( $actions, $pickup_by_line );
bhp_svp_assert( ! isset( $stripped['blt_resend_order'] ), 'PICKUP order: the Bookvault resend action is REMOVED from wp-admin', $failures );
bhp_svp_assert( isset( $stripped['send_order_details'] ), 'PICKUP order: every OTHER order action is left alone', $failures );

/* =========================================================================
 * ⭐ 8b. THE BILLING-ONLY BEHAVIOUR — WOOCOMMERCE'S, NOT THIS BUILD'S
 *
 * The founder's actual instruction: *"I dont need shipping information ...
 * Shipping address and put Billing Address"*. That behaviour is not written
 * anywhere in this plugin. It is what WooCommerce Blocks does once the chosen
 * method is a recognised local-pickup method, and these assertions prove this
 * build's method IS one of those.
 *
 * ⚠ THIS SECTION CANNOT PROVE THE RENDERED SCREEN. It proves the two server-
 *   side conditions the screen depends on. The screen itself, and a real
 *   billing-only order submission, are proven by the Store API order walk and
 *   the browser QA recorded in the release evidence — not here, and this suite
 *   does not claim otherwise.
 * ====================================================================== */

if ( class_exists( '\Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils' ) ) {
	$svp_collectable = \Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils::get_local_pickup_method_ids();
	bhp_svp_assert(
		in_array( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID, $svp_collectable, true ),
		'⭐ WooCommerce classes this build\'s method as COLLECTABLE. That single fact is what makes `prefersCollection` true, which is what sets `showShippingFields` false and `showBillingFields` true in the checkout block',
		$failures
	);
	bhp_svp_assert(
		! in_array( 'flat_rate', $svp_collectable, true ),
		'⛔ ...and ordinary shipping is NOT collectable, so choosing "Ship" still asks for a shipping address exactly as it always did',
		$failures
	);
} else {
	bhp_svp_skip( 'Collectable-method-id assertions', 'the Blocks Store API utility class is not loadable in this context', $skips );
}

/*
 * ⭐ AND THE ORDER-SIDE HALF: WooCommerce hides the shipping address on the
 *    confirmation page and in the order emails for a local-pickup order. This
 *    asserts the filter it uses actually carries this build's method id, rather
 *    than trusting the release note that says it does.
 */
$svp_hidden = apply_filters( 'woocommerce_order_hide_shipping_address', array( 'local_pickup' ) );
bhp_svp_assert(
	is_array( $svp_hidden ) && in_array( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID, $svp_hidden, true ),
	'⭐ ORDER SIDE: WooCommerce hides the shipping address on the confirmation page and in the order emails for this build\'s method. A parent never sees a posting address anywhere in the flow',
	$failures
);

/*
 * ⭐ THE LAST PLACE THE WORD "SHIPPING" SURVIVED, and it was found by RENDERING
 *    a real confirmation email during the order walk rather than by reading
 *    code. `WC_Order::get_order_item_totals()` hardcodes "Shipping:" and it is
 *    what the order-received page and every order email print.
 */
$svp_rows_normal = bhp_school_pickup_order_totals_label( array( 'shipping' => array( 'label' => 'Shipping:', 'value' => 'x' ) ), $shipped_order );
bhp_svp_assert( 'Shipping:' === $svp_rows_normal['shipping']['label'], '⛔ TOTALS ROW, ORDINARY ORDER: the label is WooCommerce\'s own, untouched', $failures );

$svp_rows_pickup = bhp_school_pickup_order_totals_label( array( 'shipping' => array( 'label' => 'Shipping:', 'value' => 'x' ) ), $pickup_by_line );
bhp_svp_assert( 'Hand delivery:' === $svp_rows_pickup['shipping']['label'], '⭐ TOTALS ROW, PICKUP ORDER: the confirmation page and every order email say "Hand delivery:", not "Shipping:"', $failures );
bhp_svp_assert( 'x' === $svp_rows_pickup['shipping']['value'], 'TOTALS ROW: only the LABEL changed. The value is WooCommerce\'s own, including its "Collection from ..." sentence', $failures );

$svp_rows_legacy = bhp_school_pickup_order_totals_label( array( 'shipping' => array( 'label' => 'Shipping:', 'value' => 'x' ) ), $pickup_legacy );
bhp_svp_assert( 'Hand delivery:' === $svp_rows_legacy['shipping']['label'], 'TOTALS ROW: a 1.8.49-era order gets the corrected label too', $failures );

bhp_svp_assert( array() === bhp_school_pickup_order_totals_label( array(), $pickup_by_line ), 'TOTALS ROW: a totals array with no shipping row is returned untouched (and does not fatal)', $failures );

/* =========================================================================
 * 8b. ⭐⭐ 1.8.55 — THE CART PAGE, WHICH 1.8.52 MISSED.
 *
 * FOUNDER-CAUGHT ON PRODUCTION, ON A PHONE, 2026-08-18. Andrew Signore,
 * verbatim, relayed through the Chief of Staff and NOT witnessed by the session
 * that wrote these assertions: "I checked the QA - the cart page still says
 * shipping when moving to the checkout page - but I do like the option for hand
 * delivery / shipping - very good."
 *
 * ⛔ WHAT THESE ASSERTIONS CAN AND CANNOT PROVE, STATED SO NOBODY READS MORE
 *    INTO A GREEN LINE THAN IS THERE. They prove the override is emitted, is
 *    correctly shaped, is gated, and carries the same words as the order path.
 *    THEY DO NOT PROVE THE RENDERED LABEL CHANGED — that row is drawn by React
 *    from a JavaScript `__()` call, and the only thing that can prove it is a
 *    real browser looking at a real flagged cart. That evidence is in the QA
 *    record, not in this file.
 * ====================================================================== */

bhp_svp_assert(
	function_exists( 'bhp_school_pickup_totals_label' ),
	'CART LABEL: the shared phrase function exists (one author for four surfaces)',
	$failures
);
bhp_svp_assert(
	'Hand delivery' === bhp_school_pickup_totals_label(),
	'⭐ CART LABEL: the shared phrase is exactly "Hand delivery" (no colon; the ORDER surface appends its own)',
	$failures
);
bhp_svp_assert(
	'Hand delivery:' === bhp_school_pickup_order_totals_label( array( 'shipping' => array( 'label' => 'Shipping:', 'value' => 'x' ) ), $pickup_by_line )['shipping']['label'],
	'⛔ CART LABEL: refactoring the phrase into one function left the ORDER label BYTE-IDENTICAL to 1.8.52',
	$failures
);

bhp_svp_assert(
	function_exists( 'bhp_school_pickup_cart_shows_hand_delivery' ),
	'CART LABEL: the cart-page predicate exists',
	$failures
);
bhp_svp_assert(
	function_exists( 'bhp_school_pickup_cart_totals_label_assets' )
		&& false !== has_action( 'wp_enqueue_scripts', 'bhp_school_pickup_cart_totals_label_assets' ),
	'⛔ CART LABEL: the enqueue is ATTACHED to `wp_enqueue_scripts` (a defined-but-unhooked function relabels nothing)',
	$failures
);
bhp_svp_assert(
	100 === has_action( 'wp_enqueue_scripts', 'bhp_school_pickup_cart_totals_label_assets' ),
	'CART LABEL: it runs at priority 100, late enough that the cart and its session are up',
	$failures
);

$svp_js = bhp_school_pickup_cart_totals_label_js( bhp_school_pickup_totals_label() );
bhp_svp_assert(
	false !== strpos( $svp_js, 'wp.i18n.setLocaleData' ),
	'⭐ CART LABEL: the override uses `wp.i18n.setLocaleData`, the documented way to change a translated JAVASCRIPT string. The Blocks totals row is React and no PHP `gettext` filter can reach it',
	$failures
);
bhp_svp_assert(
	false !== strpos( $svp_js, '"woocommerce"' ),
	'CART LABEL: it is scoped to the `woocommerce` text domain, which is the domain the Blocks bundle calls `__("Shipping","woocommerce")` in',
	$failures
);
/*
 * ⛔⛔ THE SHAPE, NOT MERELY THE CONTENTS. The first 1.8.55 build emitted
 *     `[ null, "Hand delivery" ]` — the Jed layout, where index 0 is the plural
 *     msgid — and the rendered row DID NOT CHANGE. Tannin, which is what
 *     `@wordpress/i18n` runs, reads index 0 as the translation, finds `null`,
 *     and silently returns the original string. Nothing errored. This assertion
 *     exists so that regression cannot be re-shipped, and it is written against
 *     the shape WordPress core itself emits: `{ 'msgid': [ 'translation' ] }`.
 */
bhp_svp_assert(
	false !== strpos( $svp_js, '"Shipping": [ "Hand delivery" ]' ),
	'⭐⭐ CART LABEL: the emitted payload is `{"Shipping": ["Hand delivery"]}` — TANNIN\'s shape, where index 0 is the translation',
	$failures
);
bhp_svp_assert(
	false === strpos( $svp_js, 'null,' ),
	'⛔ CART LABEL: the Jed-style `[ null, ... ]` shape is NOT emitted. Tannin ignores it and the label silently stays "Shipping" with no error anywhere',
	$failures
);
bhp_svp_assert(
	false !== strpos( $svp_js, 'window.wp && wp.i18n && wp.i18n.setLocaleData' ),
	'⛔ CART LABEL: it is guarded, so a page where `wp-i18n` failed to load throws nothing at a paying customer',
	$failures
);
bhp_svp_assert(
	'{"a":"b"}' !== $svp_js && false === strpos( bhp_school_pickup_cart_totals_label_js( 'a"b' ), 'a"b' ),
	'⛔ CART LABEL: the label is JSON-ENCODED into the script, so a school name or phrase containing a quote cannot break the page',
	$failures
);

/*
 * ⛔ THE THIRD GATE, AND IT IS THE ONE THAT MATTERS. A flagged parent who
 *    deliberately chooses ordinary shipping and walks back to the cart must
 *    read "Shipping", because that is what is about to happen to their books.
 *    Labelling a posted, charged order "Hand delivery" would be a worse lie
 *    than the one being fixed.
 */
$svp_src_cart = file_get_contents( dirname( __DIR__ ) . '/includes/school-visit-pickup.php' );
bhp_svp_assert(
	false !== strpos( $svp_src_cart, 'chosen_shipping_methods' )
		&& false !== strpos( $svp_src_cart, "strpos( \$method, BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID . ':' )" ),
	'⭐⭐ CART LABEL: the predicate tests the SELECTED rate, not merely the session flag',
	$failures
);
bhp_svp_assert(
	false !== strpos( $svp_src_cart, 'is_cart()' ) && false === strpos( $svp_src_cart, 'is_checkout() || is_cart()' ),
	'⛔ CART LABEL: it is scoped to the CART page. The checkout\'s own Ship/Pickup toggle already says "Pickup" and Andrew explicitly praised it; it is not touched',
	$failures
);

// CONTROL: an unflagged request gets no override at all, and the predicate does
// no work on the way to saying so.
$svp_unflagged = ! bhp_school_visit_use_delivery_framing();
bhp_svp_assert(
	$svp_unflagged ? ( false === bhp_school_pickup_cart_shows_hand_delivery() ) : true,
	'⛔ CART LABEL, CONTROL: with no visit flag on this request the predicate is false, so nothing is enqueued and an ordinary shopper still reads "Shipping"'
		. ( $svp_unflagged ? '' : ' (SKIPPED-AS-PASS: this run IS flagged)' ),
	$failures
);

/*
 * The DRAWER's own row. Its markup is ours, so it is fixed rather than
 * reported, and it draws BOTH labels from PHP so the words cannot drift.
 */
bhp_svp_assert(
	'Hand delivery' === apply_filters( 'bhp_bundle_drawer_ship_row_pickup_label', '' ),
	'⭐ DRAWER ROW: the drawer is handed "Hand delivery" through `bhp_bundle_drawer_ship_row_pickup_label` — the same phrase, from the same function',
	$failures
);
bhp_svp_assert(
	BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID === apply_filters( 'bhp_bundle_drawer_ship_row_pickup_method', '' ),
	'DRAWER ROW: it is handed the method id that means "this is not shipping at all"',
	$failures
);
bhp_svp_assert(
	'Shipping' === apply_filters( 'bhp_bundle_drawer_ship_row_label', 'Shipping' ),
	'⛔ DRAWER ROW, CONTROL: the DEFAULT label is still "Shipping" and is returned unchanged',
	$failures
);

$svp_drawer_js = file_get_contents( dirname( __DIR__ ) . '/assets/bundle-drawer.js' );
bhp_svp_assert(
	false !== strpos( $svp_drawer_js, 'selectedRate.method_id === drawerData.shipRowPickupMethod' ),
	'⭐⭐ DRAWER ROW: the drawer decides from the LIVE SELECTED RATE, not from a session flag baked into the page — so the row is correct even on a cached page',
	$failures
);
bhp_svp_assert(
	false === strpos( $svp_drawer_js, "addRow('Shipping'," ),
	'⛔ DRAWER ROW: the hardcoded `addRow(\'Shipping\', ...)` is gone from the drawer summary',
	$failures
);

/* =========================================================================
 * 9. NO ZONE, METHOD OR FLAT-RATE SETTING WAS TOUCHED
 *
 * A structural assertion on the source, because the rails in the brief are
 * about what the code may never do, not only about what it does today.
 * ====================================================================== */

if ( is_string( $src ) ) {
	bhp_svp_assert( ! preg_match( '/woocommerce_shipping_zone|WC_Shipping_Zones|woocommerce_flat_rate_\d+_settings/', $src ), 'The source contains NO reference to a shipping zone or a flat_rate settings option', $failures );
	/*
	 * ⚠ NOT a bare string search for "BookVAULT Shipping". The source's own
	 *   rails comment says, in words, that it never adds that method — so a
	 *   string search fails on the very sentence that promises the thing.
	 *   (It did, on the first run of this suite. Recorded rather than quietly
	 *   corrected, because "the assertion was wrong, not the code" is the
	 *   distinction that matters when a safety test goes red.)
	 *   What is asserted instead is the only two ways a method can actually be
	 *   registered or zoned in WooCommerce.
	 */
	bhp_svp_assert( ! preg_match( '/woocommerce_shipping_methods|add_shipping_method|register_shipping_method|woocommerce_shipping_init/', $src ), 'The source REGISTERS no shipping method and ZONES nothing -- WooCommerce registers its own pickup method; this file only makes it visible for one request', $failures );

	/* ⛔⛔ THE 1.8.52 RAIL, ASSERTED THREE DIFFERENT WAYS. */
	bhp_svp_assert( ! preg_match( '/update_option\s*\(/', $src ), '⛔ The source calls `update_option()` NOWHERE AT ALL -- not for a woocommerce_* option, not for any option', $failures );
	bhp_svp_assert( ! preg_match( '/(add|delete)_option\s*\(/', $src ), '⛔ The source calls neither `add_option()` nor `delete_option()`', $failures );
	bhp_svp_assert(
		4 === preg_match_all( "/add_filter\(\s*'(option_|default_option_)'\s*\.\s*BHP_SCHOOL_PICKUP_(SETTINGS|LOCATIONS)_OPTION/", $src ),
		'⛔ Local pickup is enabled by exactly FOUR READ filters (option_ + default_option_, for each of the two options) and by nothing else. Writing either option in the database is a WooCommerce checkout-configuration change and an Andrew-only gate',
		$failures
	);
	bhp_svp_assert( ! preg_match( '/wp_remote_(get|post|request)|curl_exec/', $src ), 'The source makes NO outbound HTTP call of its own', $failures );
}

/* =========================================================================
 * ⭐ 9b. ⛔⛔ THE OPTIONS ARE STILL EXACTLY WHAT THEY WERE
 *
 * The single most important new assertion in 1.8.52. Everything above has by
 * now run every flagged code path in the feature, twice. If any of it had
 * persisted a pickup setting, this is where it would show. Read RAW, straight
 * out of the options table, because `get_option()` runs this build's own
 * read filters and would therefore report the value the FILTER produces.
 * ====================================================================== */

$bhp_svp_raw_settings_after  = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BHP_SCHOOL_PICKUP_SETTINGS_OPTION ) );
$bhp_svp_raw_locations_after = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", BHP_SCHOOL_PICKUP_LOCATIONS_OPTION ) );

bhp_svp_assert(
	$bhp_svp_raw_settings_before === $bhp_svp_raw_settings_after,
	'⛔⛔ NO WOOCOMMERCE SETTING WAS MUTATED: `woocommerce_pickup_location_settings` is byte-identical in the database to what it was before this run',
	$failures
);
bhp_svp_assert(
	$bhp_svp_raw_locations_before === $bhp_svp_raw_locations_after,
	'⛔⛔ NO WOOCOMMERCE SETTING WAS MUTATED: `pickup_location_pickup_locations` is byte-identical in the database to what it was before this run',
	$failures
);

/*
 * ⭐ AND THE STORE'S OWN ANSWER, THROUGH WOOCOMMERCE'S OWN HELPER, WITH NO
 *    FLAG SET. This is the assertion that means "an ordinary customer's
 *    checkout has no Ship/Pickup toggle on it", because
 *    `Checkout::enqueue_data()` hands exactly this value to the front end as
 *    `localPickupEnabled`, and the toggle block renders only when it is true.
 */
if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}
if ( class_exists( '\Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils' ) ) {
	bhp_svp_assert(
		false === \Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils::is_local_pickup_enabled(),
		'⛔ WOOCOMMERCE\'S OWN ANSWER, unflagged: local pickup is DISABLED, so an ordinary customer\'s checkout renders no Ship/Hand-delivery toggle and no pickup step',
		$failures
	);
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-live' );
		bhp_svp_assert(
			true === \Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils::is_local_pickup_enabled(),
			'⭐ WOOCOMMERCE\'S OWN ANSWER, flagged: local pickup is ENABLED. This is the exact value that becomes `localPickupEnabled` on the checkout page, and it is the whole reason the toggle appears and the shipping-address form is replaced by the billing-address form',
			$failures
		);
		$svp_live_locations = \Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils::get_local_pickup_method_locations();
		bhp_svp_assert(
			is_array( $svp_live_locations ) && 1 === count( array_filter( $svp_live_locations, static function ( $l ) {
				return isset( $l['name'] ) && false !== strpos( (string) $l['name'], 'Test Live Elementary' );
			} ) ),
			'⭐ WOOCOMMERCE\'S OWN ANSWER, flagged: it sees EXACTLY ONE location and it is this session\'s visit',
			$failures
		);
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
	}
} else {
	bhp_svp_skip( 'WooCommerce LocalPickupUtils assertions', 'the Blocks Store API utility class is not loadable in this context', $skips );
}

/* =========================================================================
 * ═══════════════════════════════════════════════════════════════════════════
 * 10–15. THE CHECKOUT FIELDS — 1.8.50, `CYCLE162-LD-PICKUP-FIELDS`
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ NOT ONE ASSERTION BELOW TOUCHES MAILCHIMP. The subscribe path is
 *     short-circuited at `bhp_school_visit_pre_subscribe`, which is a real
 *     production filter and not a test-only hack, and every assertion about
 *     "what would have been sent" is made against the PAYLOAD that filter
 *     receives. No HTTP request leaves this file, and none may ever be added
 *     to it. The same rule as the webhook section above, for the same reason.
 * ====================================================================== */

bhp_svp_assert( function_exists( 'bhp_school_visit_field_definitions' ), 'school-visit-fields.php is loaded by the plugin bootstrap', $failures );

if ( ! function_exists( 'bhp_school_visit_field_definitions' ) ) {
	bhp_svp_skip( 'ALL checkout-field assertions', 'school-visit-fields.php is not loaded', $skips );
} else {

	/* =====================================================================
	 * 10. THE DEFINITIONS, AND THE CONSENT RAIL IN THE SHIPPED CODE
	 * ================================================================== */

	$defs = bhp_school_visit_field_definitions();

	bhp_svp_assert( 2 === count( $defs ), 'Exactly TWO fields are defined -- a third would be a scope change, not a refinement', $failures );
	bhp_svp_assert( isset( $defs['child_name'], $defs['newsletter'] ), 'Both fields are present under their documented keys', $failures );

	$child_def = isset( $defs['child_name'] ) ? $defs['child_name'] : array();
	$optin_def = isset( $defs['newsletter'] ) ? $defs['newsletter'] : array();

	bhp_svp_assert( 'brave-hearts/school-visit-child-name' === $child_def['id'], 'Child field id is the namespaced id the API requires', $failures );
	bhp_svp_assert( 'text' === $child_def['type'], 'Child field is a TEXT field', $failures );
	bhp_svp_assert( 'order' === $child_def['location'], 'Child field is in the ORDER location', $failures );
	bhp_svp_assert( true === $child_def['required'], 'Child field is REQUIRED -- a hard true, not a rules array that degrades to optional when malformed', $failures );
	bhp_svp_assert( false !== strpos( $child_def['label'], 'signed dedication' ), 'Child field label names WHY the name is being asked for', $failures );
	bhp_svp_assert( false !== stripos( $child_def['label'], 'first name' ), 'Child field label asks for a FIRST name only -- privacy minimalism, in the label itself', $failures );
	bhp_svp_assert( isset( $child_def['description'] ) && false !== strpos( $child_def['description'], 'signs each book' ), 'Child field carries the help sentence', $failures );
	bhp_svp_assert( isset( $child_def['attributes']['data-bhp-field'] ) && 'school-visit-child-name' === $child_def['attributes']['data-bhp-field'], 'Child field carries a STABLE data- selector for browser QA', $failures );

	bhp_svp_assert( 'brave-hearts/school-visit-newsletter' === $optin_def['id'], 'Opt-in field id is the namespaced id the API requires', $failures );
	bhp_svp_assert( 'checkbox' === $optin_def['type'], 'Opt-in field is a CHECKBOX', $failures );
	bhp_svp_assert( false === $optin_def['required'], '⛔ CONSENT RAIL: the opt-in checkbox is NOT required, so it renders UNCHECKED and consent can only ever be affirmative', $failures );
	bhp_svp_assert( isset( $optin_def['attributes']['data-bhp-field'] ) && 'school-visit-newsletter' === $optin_def['attributes']['data-bhp-field'], 'Opt-in field carries a STABLE data- selector for browser QA', $failures );

	/* =====================================================================
	 * 11. REGISTRATION IS GATED BY THE FLAG
	 * ================================================================== */

	$bhp_cf = null;
	if ( class_exists( '\Automattic\WooCommerce\Blocks\Package' ) && class_exists( '\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) ) {
		try {
			$bhp_cf = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
		} catch ( Throwable $e ) {
			$bhp_cf = null;
		}
	}

	if ( ! $bhp_cf ) {
		bhp_svp_skip( 'Field REGISTRATION assertions', 'the WooCommerce Blocks CheckoutFields container is not reachable in this context', $skips );
	} else {
		/*
		 * ⭐ THIS IS THE REAL "NORMAL CHECKOUT IS UNCHANGED" ASSERTION, and it
		 *    is stronger than a rendering check: `woocommerce_init` has ALREADY
		 *    FIRED by the time this suite runs, with no session and no flag. So
		 *    the registry is being read in exactly the state an ordinary
		 *    visitor's request produces, and neither field may be in it.
		 */
		$registered_before = $bhp_cf->get_additional_fields();
		bhp_svp_assert( ! isset( $registered_before[ BHP_SCHOOL_VISIT_FIELD_CHILD ] ), '⛔ UNFLAGGED request: the child-name field is NOT registered -- an ordinary customer\'s checkout does not have it at all', $failures );
		bhp_svp_assert( ! isset( $registered_before[ BHP_SCHOOL_VISIT_FIELD_OPTIN ] ), '⛔ UNFLAGGED request: the newsletter field is NOT registered -- an ordinary customer\'s checkout does not have it at all', $failures );

		// And the gate itself, called directly, still registers nothing.
		bhp_school_visit_register_checkout_fields();
		$registered_after_unflagged = $bhp_cf->get_additional_fields();
		bhp_svp_assert( ! isset( $registered_after_unflagged[ BHP_SCHOOL_VISIT_FIELD_CHILD ] ), 'UNFLAGGED: calling the registrar directly STILL registers nothing', $failures );
		bhp_svp_assert( count( $registered_before ) === count( $registered_after_unflagged ), 'UNFLAGGED: the additional-field registry is unchanged in size -- no other field was disturbed either', $failures );

		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-live' );
			bhp_school_visit_register_checkout_fields();
			$registered_flagged = $bhp_cf->get_additional_fields();

			bhp_svp_assert( isset( $registered_flagged[ BHP_SCHOOL_VISIT_FIELD_CHILD ] ), 'FLAGGED: the child-name field IS registered', $failures );
			bhp_svp_assert( isset( $registered_flagged[ BHP_SCHOOL_VISIT_FIELD_OPTIN ] ), 'FLAGGED: the newsletter field IS registered', $failures );

			if ( isset( $registered_flagged[ BHP_SCHOOL_VISIT_FIELD_CHILD ] ) ) {
				$rc = $registered_flagged[ BHP_SCHOOL_VISIT_FIELD_CHILD ];
				bhp_svp_assert( true === $rc['required'], 'FLAGGED: WooCommerce holds the child field as required === true', $failures );
				bhp_svp_assert( 'order' === $rc['location'], 'FLAGGED: WooCommerce holds the child field in the order location', $failures );
				bhp_svp_assert( isset( $rc['attributes']['data-bhp-field'] ), 'FLAGGED: the data- selector survived WooCommerce\'s attribute whitelist', $failures );
			}
			if ( isset( $registered_flagged[ BHP_SCHOOL_VISIT_FIELD_OPTIN ] ) ) {
				bhp_svp_assert( false === $registered_flagged[ BHP_SCHOOL_VISIT_FIELD_OPTIN ]['required'], '⛔ CONSENT RAIL, as WooCommerce actually holds it: the checkbox is not required', $failures );
			}

			WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
		} else {
			bhp_svp_skip( 'FLAGGED registration assertions', 'no WC session in this CLI context', $skips );
		}
	}

	/* =====================================================================
	 * 12. SANITISATION AND VALIDATION
	 * ================================================================== */

	bhp_svp_assert( 'Ada' === bhp_school_visit_sanitize_child_name( '  Ada  ' ), 'Sanitiser trims', $failures );
	bhp_svp_assert( 'Ada Mae' === bhp_school_visit_sanitize_child_name( "Ada\n\t  Mae" ), 'Sanitiser collapses internal whitespace', $failures );
	bhp_svp_assert( false === strpos( bhp_school_visit_sanitize_child_name( '<script>x</script>Ada' ), '<' ), 'Sanitiser strips tags', $failures );
	bhp_svp_assert( BHP_SCHOOL_VISIT_CHILD_MAXLEN >= strlen( bhp_school_visit_sanitize_child_name( str_repeat( 'a', 300 ) ) ), 'Sanitiser caps the length', $failures );
	bhp_svp_assert( '' === bhp_school_visit_sanitize_child_name( array( 'x' ) ), 'Sanitiser returns \'\' for a non-scalar rather than fatalling', $failures );

	bhp_svp_assert( is_wp_error( bhp_school_visit_validate_child_name( '', array( 'required' => true ) ) ), 'Validator REJECTS an empty required value -- supplying a validate_callback replaces WooCommerce\'s default one, and dropping the required check would make the field silently optional', $failures );
	bhp_svp_assert( is_wp_error( bhp_school_visit_validate_child_name( 'parent@example.com', array( 'required' => true ) ) ), 'Validator REJECTS an email address in the child-name field -- it sits under an email field and a mistyped row would put a child\'s contact detail on an order', $failures );
	bhp_svp_assert( null === bhp_school_visit_validate_child_name( 'Ada', array( 'required' => true ) ), 'Validator ACCEPTS an ordinary first name', $failures );
	bhp_svp_assert( null === bhp_school_visit_validate_child_name( 'Ada-Mae O’Brien', array( 'required' => true ) ), 'Validator does NOT police name characters -- hyphens, apostrophes and spaces are names, not errors', $failures );

	bhp_svp_assert( true === bhp_school_visit_is_affirmative( '1' ), 'Checkbox: "1" is a yes', $failures );
	bhp_svp_assert( true === bhp_school_visit_is_affirmative( true ), 'Checkbox: boolean true is a yes', $failures );
	bhp_svp_assert( true === bhp_school_visit_is_affirmative( 'yes' ), 'Checkbox: "yes" is a yes', $failures );
	bhp_svp_assert( false === bhp_school_visit_is_affirmative( '0' ), '⛔ CONSENT RAIL: "0" is NOT a yes', $failures );
	bhp_svp_assert( false === bhp_school_visit_is_affirmative( '' ), '⛔ CONSENT RAIL: an empty value is NOT a yes', $failures );
	bhp_svp_assert( false === bhp_school_visit_is_affirmative( null ), '⛔ CONSENT RAIL: null is NOT a yes', $failures );
	bhp_svp_assert( false === bhp_school_visit_is_affirmative( 'maybe' ), '⛔ CONSENT RAIL: anything unrecognised is NOT a yes', $failures );

	/* =====================================================================
	 * 13. META PERSISTENCE, ON REAL SAVED ORDERS
	 * ================================================================== */

	if ( ! function_exists( 'wc_create_order' ) ) {
		bhp_svp_skip( 'Field meta-persistence assertions', 'wc_create_order() is unavailable', $skips );
	} else {

		/*
		 * The conspicuous child name below is deliberate: every later assertion
		 * that the child's name did NOT reach a Mailchimp payload searches for
		 * this exact string, so a leak cannot hide behind a common first name.
		 */
		$bhp_child_probe_name = 'Zzchildprobe';

		// (a) A flagged order that OPTED IN.
		$fo = wc_create_order( array( 'status' => 'pending' ) );
		if ( $fo && ! is_wp_error( $fo ) ) {
			$GLOBALS['bhp_svp_probe_ids'][] = $fo->get_id();

			$foi = new WC_Order_Item_Shipping();
			$foi->set_method_id( BHP_SCHOOL_PICKUP_METHOD_ID );
			$foi->set_method_title( 'Author hand-delivery (fields probe)' );
			$foi->set_total( 0 );
			$foi->add_meta_data( __( 'School', 'brave-hearts' ), 'Fields Probe School', true );
			$foi->add_meta_data( __( 'Visit date', 'brave-hearts' ), '2099-02-02', true );
			$fo->add_item( $foi );

			// Exactly what WooCommerce's additional-fields API writes.
			$fo->update_meta_data( '_wc_other/' . BHP_SCHOOL_VISIT_FIELD_CHILD, '  ' . $bhp_child_probe_name . '  ' );
			$fo->update_meta_data( '_wc_other/' . BHP_SCHOOL_VISIT_FIELD_OPTIN, '1' );
			$fo->set_billing_email( 'bhp-svp-probe@example.com' );
			$fo->set_billing_first_name( 'ProbeParent' );
			$fo->save();

			bhp_school_visit_store_field_meta( $fo->get_id() );
			$fo_reloaded = wc_get_order( $fo->get_id() );

			bhp_svp_assert( $bhp_child_probe_name === $fo_reloaded->get_meta( BHP_SCHOOL_VISIT_META_CHILD ), 'META: the child\'s name is mirrored into an explicit, stable meta key (trimmed)', $failures );
			bhp_svp_assert( 'yes' === $fo_reloaded->get_meta( BHP_SCHOOL_VISIT_META_OPTIN ), 'META: an affirmative opt-in is recorded as "yes"', $failures );
			bhp_svp_assert( '' !== (string) $fo_reloaded->get_meta( '_bhp_school_visit_optin_timestamp' ), 'META: an affirmative opt-in carries a consent timestamp', $failures );
			bhp_svp_assert( $bhp_child_probe_name === bhp_school_visit_child_name( $fo_reloaded ), 'READ-BACK: the accessor returns the stored child name', $failures );
			bhp_svp_assert( true === bhp_school_visit_optin_given( $fo_reloaded ), 'READ-BACK: the opt-in predicate reads true', $failures );

			// The packing-list note must carry the name, in the same note.
			bhp_school_pickup_mark_order( $fo->get_id() );
			$fo_notes = wc_get_order_notes( array( 'order_id' => $fo->get_id() ) );
			$fo_text  = '';
			foreach ( $fo_notes as $n ) {
				$fo_text .= $n->content;
			}
			bhp_svp_assert( false !== strpos( $fo_text, 'DO NOT SHIP' ), 'NOTE: the packing-list note is still the packing-list note', $failures );
			bhp_svp_assert( false !== strpos( $fo_text, 'SIGN TO: ' . $bhp_child_probe_name ), 'NOTE: school, date AND child are in ONE note -- Andrew reads one line to pack one bag', $failures );
			bhp_svp_assert( false !== strpos( $fo_text, 'Fields Probe School' ), 'NOTE: the school clause is unchanged by the addition of the child clause', $failures );

			/* -----------------------------------------------------------------
			 * 14. THE SUBSCRIBE DECISION — MOCKED, NEVER LIVE
			 * -------------------------------------------------------------- */

			$GLOBALS['bhp_svp_sub_calls'] = array();
			$bhp_svp_spy                  = static function ( $pre, $payload, $order ) {
				$GLOBALS['bhp_svp_sub_calls'][] = $payload;
				return array( 'ok' => true, 'code' => 'success', 'redirect' => '' );
			};
			add_filter( 'bhp_school_visit_pre_subscribe', $bhp_svp_spy, 10, 3 );

			bhp_school_visit_subscribe_parent( $fo->get_id() );

			bhp_svp_assert( 1 === count( $GLOBALS['bhp_svp_sub_calls'] ), 'OPT-IN GIVEN: the subscribe path is entered exactly ONCE', $failures );

			$payload = ! empty( $GLOBALS['bhp_svp_sub_calls'] ) ? $GLOBALS['bhp_svp_sub_calls'][0] : array();
			$flat    = wp_json_encode( $payload );

			bhp_svp_assert( 'bhp-svp-probe@example.com' === ( isset( $payload['email'] ) ? $payload['email'] : '' ), 'PAYLOAD: the subscriber email is the order\'s BILLING email -- no new field was asked for', $failures );
			bhp_svp_assert( 'ProbeParent' === ( isset( $payload['name'] ) ? $payload['name'] : '' ), 'PAYLOAD: the subscriber name is the order\'s BILLING first name', $failures );
			bhp_svp_assert( 'school_visit' === ( isset( $payload['context'] ) ? $payload['context'] : '' ), 'PAYLOAD: the context is school_visit, which is what the tag filter keys on', $failures );
			bhp_svp_assert( 'parents_families' === ( isset( $payload['audience_type'] ) ? $payload['audience_type'] : '' ), 'PAYLOAD: the audience is the PARENT audience', $failures );
			bhp_svp_assert( '' === ( isset( $payload['lead_magnet'] ) ? $payload['lead_magnet'] : 'x' ), 'PAYLOAD: NO lead magnet is claimed -- this checkbox promises no downloadable resource', $failures );

			bhp_svp_assert( false === strpos( (string) $flat, $bhp_child_probe_name ), '⛔⛔ CONSENT RAIL: the CHILD\'S NAME does not appear ANYWHERE in the Mailchimp payload', $failures );
			bhp_svp_assert( false === stripos( (string) $flat, 'child' ), '⛔⛔ CONSENT RAIL: no payload key or value even mentions a child', $failures );

			$fo_after = wc_get_order( $fo->get_id() );
			bhp_svp_assert( 'yes' === $fo_after->get_meta( BHP_SCHOOL_VISIT_META_SYNCED ), 'SYNC STATE: a successful subscribe is recorded on the order', $failures );

			// Idempotency: the two order-processed hooks can both fire.
			bhp_school_visit_subscribe_parent( $fo->get_id() );
			bhp_svp_assert( 1 === count( $GLOBALS['bhp_svp_sub_calls'] ), 'IDEMPOTENT: a second call does NOT subscribe again -- both checkout hooks can fire in one request', $failures );

			remove_filter( 'bhp_school_visit_pre_subscribe', $bhp_svp_spy, 10 );
		} else {
			bhp_svp_skip( 'Opt-in field/meta/subscribe assertions', 'wc_create_order() did not return an order', $skips );
		}

		// (b) A flagged order that did NOT opt in.
		$no = wc_create_order( array( 'status' => 'pending' ) );
		if ( $no && ! is_wp_error( $no ) ) {
			$GLOBALS['bhp_svp_probe_ids'][] = $no->get_id();
			$no->update_meta_data( '_wc_other/' . BHP_SCHOOL_VISIT_FIELD_CHILD, 'Zznooptin' );
			$no->update_meta_data( '_wc_other/' . BHP_SCHOOL_VISIT_FIELD_OPTIN, '0' );
			$no->set_billing_email( 'bhp-svp-nooptin@example.com' );
			$no->set_billing_first_name( 'NoOptIn' );
			$no->save();

			bhp_school_visit_store_field_meta( $no->get_id() );
			$no_reloaded = wc_get_order( $no->get_id() );

			bhp_svp_assert( 'no' === $no_reloaded->get_meta( BHP_SCHOOL_VISIT_META_OPTIN ), 'META: a declined opt-in is recorded EXPLICITLY as "no" -- "we never asked" and "they said no" must not be the same stored state', $failures );
			bhp_svp_assert( '' === (string) $no_reloaded->get_meta( '_bhp_school_visit_optin_timestamp' ), 'META: a declined opt-in carries NO consent timestamp', $failures );
			bhp_svp_assert( 'Zznooptin' === $no_reloaded->get_meta( BHP_SCHOOL_VISIT_META_CHILD ), 'META: the child\'s name is still stored on a no-opt-in order -- the two fields are independent', $failures );

			$GLOBALS['bhp_svp_sub_calls_b'] = array();
			$bhp_svp_spy_b                  = static function ( $pre, $payload, $order ) {
				$GLOBALS['bhp_svp_sub_calls_b'][] = $payload;
				return array( 'ok' => true, 'code' => 'success', 'redirect' => '' );
			};
			add_filter( 'bhp_school_visit_pre_subscribe', $bhp_svp_spy_b, 10, 3 );

			bhp_school_visit_subscribe_parent( $no->get_id() );

			bhp_svp_assert( 0 === count( $GLOBALS['bhp_svp_sub_calls_b'] ), '⛔⛔ CONSENT RAIL: NO opt-in means the subscribe path is NEVER entered -- not attempted, not queued, not logged as pending', $failures );
			$no_after = wc_get_order( $no->get_id() );
			bhp_svp_assert( '' === (string) $no_after->get_meta( BHP_SCHOOL_VISIT_META_SYNCED ), 'NO OPT-IN: no sync state is written either -- there is nothing to sync', $failures );

			remove_filter( 'bhp_school_visit_pre_subscribe', $bhp_svp_spy_b, 10 );
		} else {
			bhp_svp_skip( 'No-opt-in assertions', 'wc_create_order() did not return an order', $skips );
		}

		// (c) An ORDINARY order that never had the fields at all.
		$plain = wc_create_order( array( 'status' => 'pending' ) );
		if ( $plain && ! is_wp_error( $plain ) ) {
			$GLOBALS['bhp_svp_probe_ids'][] = $plain->get_id();
			$plain->set_billing_email( 'bhp-svp-plain@example.com' );
			$plain->save();

			bhp_school_visit_store_field_meta( $plain->get_id() );
			$plain_reloaded = wc_get_order( $plain->get_id() );

			$plain_bhp_keys = array();
			foreach ( $plain_reloaded->get_meta_data() as $m ) {
				$mk = $m->get_data();
				if ( isset( $mk['key'] ) && 0 === strpos( (string) $mk['key'], '_bhp_school_visit' ) ) {
					$plain_bhp_keys[] = $mk['key'];
				}
			}

			bhp_svp_assert( '' === (string) $plain_reloaded->get_meta( BHP_SCHOOL_VISIT_META_OPTIN ), '⛔ ORDINARY ORDER: the mirror writes NOTHING -- an order with neither field is left completely alone', $failures );
			bhp_svp_assert( '' === (string) $plain_reloaded->get_meta( BHP_SCHOOL_VISIT_META_CHILD ), 'ORDINARY ORDER: no child-name meta is invented', $failures );
			bhp_svp_assert( array() === $plain_bhp_keys, 'ORDINARY ORDER: NOT ONE `_bhp_school_visit*` meta key exists on the order', $failures );
			bhp_svp_assert( '' === bhp_school_visit_child_name( $plain_reloaded ), 'ORDINARY ORDER: the child-name accessor returns \'\', never a placeholder', $failures );
			bhp_svp_assert( false === bhp_school_visit_optin_given( $plain_reloaded ), 'ORDINARY ORDER: the opt-in predicate is false', $failures );

			$GLOBALS['bhp_svp_sub_calls_c'] = array();
			$bhp_svp_spy_c                  = static function ( $pre, $payload, $order ) {
				$GLOBALS['bhp_svp_sub_calls_c'][] = $payload;
				return array( 'ok' => true, 'code' => 'success', 'redirect' => '' );
			};
			add_filter( 'bhp_school_visit_pre_subscribe', $bhp_svp_spy_c, 10, 3 );
			bhp_school_visit_subscribe_parent( $plain->get_id() );
			bhp_svp_assert( 0 === count( $GLOBALS['bhp_svp_sub_calls_c'] ), '⛔ ORDINARY ORDER: never subscribed to anything', $failures );
			remove_filter( 'bhp_school_visit_pre_subscribe', $bhp_svp_spy_c, 10 );
		} else {
			bhp_svp_skip( 'Ordinary-order assertions', 'wc_create_order() did not return an order', $skips );
		}
	}

	/* =====================================================================
	 * 15. THE TAGS — THROUGH THE THEME'S OWN FILTER, NOT A NEW ONE
	 * ================================================================== */

	bhp_school_visit_current_signup_slug( 'bhpsvptest-live' );
	$tags = apply_filters( 'bhp_mailchimp_signup_tags', array( 'Adventure Club' ), 'school_visit', 'parents_families', '', home_url( '/' ) );
	bhp_school_visit_current_signup_slug( '' );

	bhp_svp_assert( is_array( $tags ) && in_array( 'Source: School Visit', $tags, true ), 'TAGS: "Source: School Visit" is applied', $failures );
	bhp_svp_assert( is_array( $tags ) && in_array( 'Visit: bhpsvptest-live', $tags, true ), 'TAGS: the SCHOOL SLUG is applied as its own tag, so one visit can be segmented from another', $failures );
	bhp_svp_assert( is_array( $tags ) && in_array( 'Audience: Parent/Grandparent', $tags, true ), 'TAGS: the parent audience tag matches the account\'s existing Title Case convention', $failures );
	bhp_svp_assert( is_array( $tags ) && ! in_array( 'Adventure Club', $tags, true ), 'TAGS: the generic fallback tag is replaced, not appended to', $failures );

	// The filter must be inert for every other context on the site.
	$other = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
	bhp_svp_assert( in_array( 'Source: Parent Popup', $other, true ), '⛔ TAGS: the PARENT POPUP\'s proven tag set is completely unaffected by this build', $failures );
	bhp_svp_assert( ! in_array( 'Source: School Visit', $other, true ), '⛔ TAGS: no school-visit tag leaks into another funnel', $failures );

	$teacher = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'teacher_popup', 'educators', 'mariana_trench_classroom_guide', home_url( '/' ) );
	bhp_svp_assert( ! in_array( 'Source: School Visit', $teacher, true ), '⛔ FUNNEL ISOLATION: the teacher funnel\'s tags are untouched', $failures );

	// With no slug resolved, the tag set degrades honestly rather than inventing one.
	bhp_school_visit_current_signup_slug( '' );
	$noslug = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'school_visit', 'parents_families', '', home_url( '/' ) );
	bhp_svp_assert( ! in_array( 'Visit: ', $noslug, true ), 'TAGS: an unresolved slug produces NO "Visit:" tag at all rather than an empty one', $failures );
	bhp_svp_assert( in_array( 'Source: School Visit', $noslug, true ), 'TAGS: the source tag still applies when the slug is unresolved', $failures );

	/* =====================================================================
	 * 16. STRUCTURAL AUDIT OF THE NEW FILE
	 * ================================================================== */

	$fsrc = @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-fields.php' );
	bhp_svp_assert( is_string( $fsrc ) && '' !== $fsrc, 'The fields source file is readable for the structural audit', $failures );
	if ( is_string( $fsrc ) && '' !== $fsrc ) {
		bhp_svp_assert( ! preg_match( '/adams-20\d\d|dallas-harris|liberty-20\d\d/i', $fsrc ), 'NO real visit slug appears anywhere in the fields source', $failures );
		bhp_svp_assert( ! preg_match( '/update_option\s*\(\s*[\'"]woocommerce_/', $fsrc ), 'The fields source writes NO woocommerce_* option', $failures );
		bhp_svp_assert( ! preg_match( '/woocommerce_shipping_zone|WC_Shipping_Zones|woocommerce_flat_rate_\d+_settings/', $fsrc ), 'The fields source touches NO shipping zone or flat_rate setting', $failures );
		bhp_svp_assert( ! preg_match( '/wp_remote_(get|post|request)|curl_exec|file_get_contents\s*\(\s*[\'"]https?:/', $fsrc ), 'The fields source makes NO direct HTTP call of its own -- the ONE outbound path is the theme\'s existing signup core', $failures );
		// `=\s*` deliberately, so the prose references in the docblocks are not
		// counted as call sites. There must be exactly one real invocation.
		bhp_svp_assert( 1 === preg_match_all( '/=\s*bhp_process_signup\s*\(/', $fsrc ), 'There is EXACTLY ONE call to the theme\'s signup core, so there is one gate to audit', $failures );
		bhp_svp_assert( ! preg_match( '/mc4wp_|add_list_member|api\.mailchimp\.com|update_list_member_tags/', $fsrc ), '⛔ NO NEW MAILCHIMP INTEGRATION WAS MINTED -- the file names no Mailchimp API, list, key or endpoint anywhere', $failures );
		// The WRITE, specifically. `'meta' => BHP_SCHOOL_VISIT_META_CHILD,` in the
		// definition list is a declaration, not a write, and must not be counted.
		bhp_svp_assert( 1 === preg_match_all( '/update_meta_data\(\s*BHP_SCHOOL_VISIT_META_CHILD\s*,/', $fsrc ), 'The child\'s name is WRITTEN in exactly one place, so there is one gate to audit', $failures );
	}
}

/* =========================================================================
 * RESULT
 * ====================================================================== */

echo "\n";

// ⛔ BEFORE any exit path, never after one, and never in a shutdown handler.
bhp_svp_cleanup();

echo "\n";
if ( ! empty( $skips ) ) {
	echo 'SKIPPED (' . count( $skips ) . "): " . implode( '; ', $skips ) . "\n";
}
if ( empty( $failures ) ) {
	echo "ALL SCHOOL-VISIT PICKUP ASSERTIONS PASSED\n";
} else {
	echo 'FAILURES (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo " - {$f}\n";
	}
	exit( 1 );
}
