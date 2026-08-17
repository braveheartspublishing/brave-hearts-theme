<?php
/**
 * School-visit hand-delivery — test suite. 1.8.49, `CYCLE162-LD-SCHOOL-PICKUP`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-school-visit-pickup.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS ACTUALLY FOR
 * ---------------------------------------------------------------------------
 * Three things, in descending order of how much they cost if they break:
 *
 *   1. ⛔ THE DUPLICATE-PRINT PROTECTION. A hand-delivery order must never
 *      reach the print partner. Asserted against the REAL, LIVE webhook rows
 *      in this store's database — not against a mock — because the thing that
 *      would actually go wrong is a real webhook not being recognised.
 *   2. ⛔ A NORMAL VISITOR SEES ZERO CHANGE. Asserted by running the shipping
 *      filter with no session flag and comparing the rate array identically.
 *   3. The gating itself: registry validation, cutoff expiry, unknown slugs.
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
$GLOBALS['bhp_svp_original_visits'] = get_option( BHP_SCHOOL_VISIT_OPTION, null );
$GLOBALS['bhp_svp_probe_ids']       = array();

function bhp_svp_cleanup() {
	$bhp_svp_original_visits = isset( $GLOBALS['bhp_svp_original_visits'] ) ? $GLOBALS['bhp_svp_original_visits'] : null;
	$bhp_svp_probe_ids       = isset( $GLOBALS['bhp_svp_probe_ids'] ) ? $GLOBALS['bhp_svp_probe_ids'] : array();

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

	if ( null === $bhp_svp_original_visits ) {
		delete_option( BHP_SCHOOL_VISIT_OPTION );
		echo "CLEANUP: visit registry deleted (it did not exist before this run)\n";
	} else {
		update_option( BHP_SCHOOL_VISIT_OPTION, $bhp_svp_original_visits );
		echo "CLEANUP: visit registry restored to its pre-run value\n";
	}
}

$today     = wp_date( 'Y-m-d' );
$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
$next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
$next_year = wp_date( 'Y-m-d', strtotime( '+370 days' ) );

update_option(
	BHP_SCHOOL_VISIT_OPTION,
	array(
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
 * 4. THE NORMAL VISITOR SEES ZERO CHANGE
 *
 * Run the injector with no session flag and prove the rate array comes back
 * identical -- same count, same keys, same objects.
 * ====================================================================== */

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}

$fake_rate  = new WC_Shipping_Rate( 'flat_rate:1', 'Contiguous US Shipping', 3.99, array(), 'flat_rate', 1 );
$base_rates = array( 'flat_rate:1' => $fake_rate );

$after_unflagged = bhp_school_pickup_inject_rate( $base_rates, array() );
bhp_svp_assert( $after_unflagged === $base_rates, 'UNFLAGGED cart: the rate array is returned IDENTICAL -- a normal visitor sees zero change', $failures );
bhp_svp_assert( ! isset( $after_unflagged[ BHP_SCHOOL_PICKUP_METHOD_ID ] ), 'UNFLAGGED cart: no pickup rate is present', $failures );

$pkgs_unflagged = bhp_school_pickup_tag_package( array( 0 => array( 'contents' => array() ) ) );
bhp_svp_assert( ! isset( $pkgs_unflagged[0]['bhp_school_visit'] ), 'UNFLAGGED cart: the shipping package is untagged, so its hash is unchanged and the rate cache is not disturbed', $failures );

/* =========================================================================
 * 5. THE FLAGGED VISITOR GETS THE OPTION -- ALONGSIDE, NEVER INSTEAD
 * ====================================================================== */

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-live' );

	$active = bhp_school_visit_active();
	bhp_svp_assert( is_array( $active ) && 'bhpsvptest-live' === $active['slug'], 'A live session flag resolves to its visit record', $failures );

	$after_flagged = bhp_school_pickup_inject_rate( $base_rates, array() );
	bhp_svp_assert( isset( $after_flagged[ BHP_SCHOOL_PICKUP_METHOD_ID ] ), 'FLAGGED cart: the pickup rate is present', $failures );
	bhp_svp_assert( isset( $after_flagged['flat_rate:1'] ), 'FLAGGED cart: normal shipping is STILL PRESENT -- the option is added alongside, never instead', $failures );
	bhp_svp_assert( $after_flagged['flat_rate:1'] === $fake_rate, 'FLAGGED cart: the normal rate object is the SAME object, unrepriced and unrenamed', $failures );
	bhp_svp_assert( 2 === count( $after_flagged ), 'FLAGGED cart: exactly one rate was added and none removed', $failures );

	$pickup = $after_flagged[ BHP_SCHOOL_PICKUP_METHOD_ID ];
	bhp_svp_assert( 0.0 === (float) $pickup->get_cost(), 'FLAGGED cart: the pickup rate costs 0.00', $failures );
	bhp_svp_assert( array() === $pickup->get_taxes(), 'FLAGGED cart: the pickup rate carries no tax', $failures );
	bhp_svp_assert( BHP_SCHOOL_PICKUP_METHOD_ID === $pickup->get_method_id(), 'FLAGGED cart: the pickup rate has its own method_id, distinct from flat_rate', $failures );
	bhp_svp_assert( false !== strpos( $pickup->get_label(), 'Test Live Elementary' ), 'FLAGGED cart: the label names the SCHOOL', $failures );
	bhp_svp_assert( (bool) preg_match( '/\(\w+ \d+\)/', $pickup->get_label() ), 'FLAGGED cart: the label names the DATE', $failures );

	$pkgs_flagged = bhp_school_pickup_tag_package( array( 0 => array( 'contents' => array() ) ) );
	bhp_svp_assert( isset( $pkgs_flagged[0]['bhp_school_visit'] ), 'FLAGGED cart: the shipping package IS tagged, so the package hash differs and the cached pre-flag rate list cannot be served', $failures );

	/* --- 5b. FREE-SHIPPING CART: the pickup option must still be offered --- */
	$free_rate       = new WC_Shipping_Rate( 'flat_rate:1', 'Contiguous US Shipping', 0.00, array(), 'flat_rate', 1 );
	$after_free_cart = bhp_school_pickup_inject_rate( array( 'flat_rate:1' => $free_rate ), array() );
	bhp_svp_assert( isset( $after_free_cart[ BHP_SCHOOL_PICKUP_METHOD_ID ] ) && isset( $after_free_cart['flat_rate:1'] ), 'COMPLETE-COLLECTION cart (shipping already $0.00): BOTH free shipping and free pickup are offered -- the parent chooses where the books arrive, not what they cost', $failures );

	/* --- 5c. EXPIRED FLAG: graceful, silent, self-clearing --- */
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-expired' );
	$after_expired = bhp_school_pickup_inject_rate( $base_rates, array() );
	bhp_svp_assert( ! isset( $after_expired[ BHP_SCHOOL_PICKUP_METHOD_ID ] ), 'EXPIRED flag: the pickup option DISAPPEARS', $failures );
	bhp_svp_assert( $after_expired === $base_rates, 'EXPIRED flag: the rate array is identical to the unflagged case -- no residue, no half-state', $failures );
	bhp_svp_assert( ! WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY ), 'EXPIRED flag: the dead session flag CLEARS ITSELF rather than being re-checked forever', $failures );

	/* --- 5d. UNKNOWN SLUG in the session (withdrawn visit) --- */
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, 'bhpsvptest-withdrawn' );
	bhp_svp_assert( null === bhp_school_visit_active(), 'WITHDRAWN visit: a session flag naming a slug no longer in the registry resolves to NULL', $failures );

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
$pickup_item->set_method_id( BHP_SCHOOL_PICKUP_METHOD_ID );
$pickup_item->set_method_title( 'Author hand-delivery' );
$pickup_by_line->add_item( $pickup_item );
bhp_svp_assert( true === bhp_school_pickup_order_is_pickup( $pickup_by_line ), 'An order whose SHIPPING LINE is the pickup method IS a pickup order -- even with NO meta written. This is the assertion that stops a failed meta write from sending books to the printer', $failures );

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
		$pi->set_method_id( BHP_SCHOOL_PICKUP_METHOD_ID );
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
	bhp_svp_assert( ! preg_match( '/woocommerce_shipping_methods|add_shipping_method|woocommerce_shipping_init/', $src ), 'The source REGISTERS no shipping method and ZONES nothing -- it injects a rate into one filtered array and nothing else', $failures );
	bhp_svp_assert( ! preg_match( '/update_option\s*\(\s*[\'"]woocommerce_/', $src ), 'The source writes NO woocommerce_* option', $failures );
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
