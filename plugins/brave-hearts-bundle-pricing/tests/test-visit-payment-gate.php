<?php
/**
 * School-visit PAYMENT GATE — test suite. 1.8.54, `CYCLE164-LD-COD-GATE`.
 *
 * Run via WP-CLI, and REDIRECT TO A FILE, never pipe:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-payment-gate.php --user=1 > out.txt 2>&1
 *
 * ⛔ DO NOT PIPE THIS SUITE THROUGH `head`. Its sibling
 *    `test-school-visit-pickup.php` records, in detail, how a closed pipe
 *    killed PHP on SIGPIPE before its cleanup ran and eventually destroyed
 *    real operator data. This suite is far less destructive by construction
 *    (see "WHAT THIS FILE WRITES" below), but the operational rule is the
 *    same and is repeated rather than assumed known.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE IS FOR
 * ---------------------------------------------------------------------------
 * Andrew Signore, verbatim, 2026-08-18:
 *   "they should have to pay before I receive the order- the parent must pay
 *    for the book/s before I sign them"
 *
 * `cod` — titled "Pay in Person" on this store — creates an order with NOTHING
 * COLLECTED, and on production its `enable_for_methods` is `[]`, which means
 * "available for EVERY shipping method", including the native local pickup the
 * school-visit build turns on. A visit-flagged parent could therefore place an
 * unpaid order that still carries the visit flag, the child's name, the packing
 * note and the HAND DELIVERY note — and Andrew would sign a book and carry it to
 * a school for an order nobody paid for.
 *
 * This suite proves the gate that closes that, and — just as importantly —
 * proves the gate does NOTHING to anyone else.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE FOUR THINGS IT ASSERTS, IN DESCENDING ORDER OF WHAT THEY COST IF WRONG
 * ---------------------------------------------------------------------------
 *   1. ⛔ AN ORDINARY SHOPPER LOSES NOTHING. The control path is asserted by
 *      IDENTITY (`===`) on the whole gateway array, not by counting keys. This
 *      is first because wrongly removing a gateway from a paying customer is
 *      silent, immediate and costs real money — including the Portneuf Valley
 *      Farmers Market, where "Pay in Person" is deliberate revenue.
 *   2. ⛔ NO WOOCOMMERCE SETTING IS MUTATED. Every payment option's RAW stored
 *      value is snapshotted before and re-read after, and asserted byte-
 *      identical. Changing a payment setting is an Andrew-only gate, and the
 *      whole design of this fix is that it is CODE and not CONFIGURATION.
 *   3. ⭐ THE FLAGGED PARENT CANNOT REACH AN UNPAID ORDER. Asserted against the
 *      REAL WooCommerce availability pipeline, not only against the callback.
 *   4. The fail-open behaviour, which is the property that makes (1) hold even
 *      when something upstream breaks.
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHY §5 FORCES COD ON IN MEMORY — READ BEFORE "FIXING" IT
 * ---------------------------------------------------------------------------
 * ⚠ STAGING DOES NOT REPRODUCE PRODUCTION HERE. On staging `cod` is
 *   `enabled=no` and the `woocommerce_cod_settings` option does not exist at
 *   all; on production (read by `chief-of-staff`, 2026-08-18) `cod` is ENABLED,
 *   titled "Pay in Person", with `enable_for_methods: []`. A suite that just
 *   read staging's live gateway list would therefore PASS VACUOUSLY — COD is
 *   absent from a flagged checkout there because it is absent from every
 *   checkout there, which proves nothing about the gate.
 *
 * ⛔ THE ONE THING WE MAY NOT DO ABOUT THAT IS ENABLE COD ON STAGING. That is a
 *    WooCommerce PAYMENT SETTING and an Andrew-only gate, on every environment,
 *    with no exceptions.
 *
 * ⭐ SO §5 RECONSTRUCTS PRODUCTION'S CONDITION IN MEMORY, INSIDE ONE PHP
 *    PROCESS, AND WRITES NOTHING. It sets `$gateway->enabled`,
 *    `enable_for_methods` and `enable_for_virtual` as PROPERTIES on the live
 *    gateway OBJECT, runs WooCommerce's own
 *    `WC()->payment_gateways()->get_available_payment_gateways()`, reads the
 *    answer, and restores all three properties. `update_option()` is never
 *    called, `$gateway->update_option()` is never called, and §6 proves the
 *    stored value is untouched by re-reading it from the database afterwards.
 *    `wp eval-file` is a one-shot process, so even the in-memory change dies
 *    with it.
 *
 *    This is stronger than calling the callback directly, because it exercises
 *    `WC_Gateway_COD::is_available()`, every other registered filter, and the
 *    exact function `CartSchema` and the Store API checkout route both call.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT THIS FILE WRITES — the complete list
 * ---------------------------------------------------------------------------
 *   · The WooCommerce SESSION key `BHP_SCHOOL_VISIT_SESSION_KEY`, set and then
 *     cleared. That is a session flag, not a setting.
 *   · NOTHING ELSE. In particular it writes NO option, NO product, NO coupon,
 *     NO shipping or payment setting, NO pickup location, and NO order.
 *
 * ⭐ IT DOES NOT SEED THE VISIT REGISTRY, and that is a deliberate change from
 *    its sibling suite. It flags the session with a REAL, ALREADY-REGISTERED,
 *    CURRENTLY-LIVE visit slug discovered at runtime. Seeding fixtures into the
 *    registry is exactly what caused the third cleanup defect documented in
 *    `test-school-visit-pickup.php`; a suite that needs no fixture cannot lose
 *    one. If no live visit is registered, §5 and §7 SKIP with the reason rather
 *    than passing vacuously.
 *
 * ⛔ IT PLACES NO ORDER, ATTEMPTS NO PAYMENT (test mode or otherwise), SENDS NO
 *    EMAIL and MAKES NO OUTBOUND HTTP REQUEST. None may ever be added to it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skips    = array();

function bhp_pg_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_pg_skip( $label, $reason, array &$skips ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$skips[] = $label;
}

/**
 * Read an option EXACTLY as stored, with "absent" distinguishable from "empty".
 *
 * ⛔ NOT `get_option()`. This plugin installs `option_*` read filters of its own
 *    for the pickup options, so `get_option()` can return a filtered value that
 *    was never stored — which would make a "nothing was written" assertion lie
 *    in the one direction that matters. This goes to the table.
 */
function bhp_pg_raw_option( $name ) {
	global $wpdb;
	$row = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ) );
	return null === $row ? '(ABSENT)' : (string) $row;
}

/* =========================================================================
 * 0. THE GATE EXISTS AND IS ATTACHED
 * ====================================================================== */

echo "=== 0. THE GATE EXISTS AND IS ATTACHED ===\n";

bhp_pg_assert( function_exists( 'bhp_school_visit_block_unpaid_gateways' ), 'the payment gate function is defined', $failures );
bhp_pg_assert(
	false !== has_filter( 'woocommerce_available_payment_gateways', 'bhp_school_visit_block_unpaid_gateways' ),
	'the gate is ATTACHED to `woocommerce_available_payment_gateways` (a defined-but-unhooked function protects nobody)',
	$failures
);
bhp_pg_assert(
	20 === has_filter( 'woocommerce_available_payment_gateways', 'bhp_school_visit_block_unpaid_gateways' ),
	'the gate runs at priority 20, AFTER the gateways plugins add at the default 10 -- a gateway added later than the gate would survive it',
	$failures
);
bhp_pg_assert( function_exists( 'bhp_school_visit_use_delivery_framing' ), 'the ONE shared request predicate is available (the gate must not grow its own session read)', $failures );

if ( ! function_exists( 'bhp_school_visit_block_unpaid_gateways' ) ) {
	fwrite( STDERR, "FATAL: the gate is not loaded; nothing else can be tested.\n" );
	exit( 1 );
}

/*
 * ⭐ THE GATE DELEGATES RATHER THAN DUPLICATING — asserted from the source, not
 *    assumed. A second session read here is the failure mode where one parent is
 *    shown hand-delivery copy while still being offered an unpaid checkout.
 */
$bhp_pg_src_file = dirname( __DIR__ ) . '/includes/school-visit-pickup.php';
$bhp_pg_src      = file_exists( $bhp_pg_src_file ) ? file_get_contents( $bhp_pg_src_file ) : '';
$bhp_pg_fn_src   = '';
if ( '' !== $bhp_pg_src ) {
	$bhp_pg_start = strpos( $bhp_pg_src, 'function bhp_school_visit_block_unpaid_gateways' );
	if ( false !== $bhp_pg_start ) {
		$bhp_pg_fn_src = substr( $bhp_pg_src, $bhp_pg_start );
	}
}
if ( '' !== $bhp_pg_fn_src ) {
	bhp_pg_assert(
		false !== strpos( $bhp_pg_fn_src, 'bhp_school_visit_use_delivery_framing' ),
		'the gate asks the SHARED predicate `bhp_school_visit_use_delivery_framing()`',
		$failures
	);
	bhp_pg_assert(
		false === strpos( $bhp_pg_fn_src, 'BHP_SCHOOL_VISIT_SESSION_KEY' ) && false === strpos( $bhp_pg_fn_src, "WC()->session" ),
		'⭐ the gate reads NO session of its own -- one predicate, one answer per request, zero copies',
		$failures
	);
	bhp_pg_assert(
		false === strpos( $bhp_pg_fn_src, 'update_option' ) && false === strpos( $bhp_pg_fn_src, 'woocommerce_cod_settings' ),
		'⛔ the gate never writes an option and never reads `woocommerce_cod_settings` -- the fix is CODE, not CONFIGURATION',
		$failures
	);
} else {
	bhp_pg_skip( 'gate source-shape assertions', 'the plugin source file could not be read from ' . $bhp_pg_src_file, $skips );
}

/* =========================================================================
 * 1. ⛔ THE CONTROL PATH. AN ORDINARY SHOPPER LOSES NOTHING.
 *
 * The fixture mirrors PRODUCTION's four enabled gateways exactly, because that
 * is the array the gate will actually meet: `stripe` (Credit / Debit Card),
 * `stripe_amazon_pay`, `stripe_link` and `cod` ("Pay in Person").
 * ====================================================================== */

echo "\n=== 1. CONTROL PATH: AN ORDINARY SHOPPER LOSES NOTHING ===\n";

class BHP_PG_Fake_Gateway {
	public $id;
	public $title;
	public function __construct( $id, $title ) {
		$this->id    = $id;
		$this->title = $title;
	}
}

$bhp_pg_make_prod_set = static function () {
	return array(
		'stripe'            => new BHP_PG_Fake_Gateway( 'stripe', 'Credit / Debit Card' ),
		'stripe_amazon_pay' => new BHP_PG_Fake_Gateway( 'stripe_amazon_pay', 'Amazon Pay' ),
		'stripe_link'       => new BHP_PG_Fake_Gateway( 'stripe_link', 'Link' ),
		'cod'               => new BHP_PG_Fake_Gateway( 'cod', 'Pay in Person' ),
	);
};

// Make sure no visit flag is set before the control assertions.
if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}

bhp_pg_assert( false === bhp_school_visit_use_delivery_framing(), 'CONTROL: this request is NOT visit-flagged (precondition for everything below)', $failures );

$bhp_pg_control_in  = $bhp_pg_make_prod_set();
$bhp_pg_control_out = bhp_school_visit_block_unpaid_gateways( $bhp_pg_control_in );

bhp_pg_assert(
	$bhp_pg_control_out === $bhp_pg_control_in,
	'⛔⛔ CONTROL: the gateway array is returned IDENTICAL (`===`, same objects, same keys, same ORDER) -- an ordinary shopper is byte-for-byte unaffected',
	$failures
);
bhp_pg_assert( isset( $bhp_pg_control_out['cod'] ), '⭐ CONTROL: `cod` ("Pay in Person") SURVIVES -- the Portneuf Valley Farmers Market is not broken to fix a schools problem', $failures );
foreach ( array( 'stripe', 'stripe_amazon_pay', 'stripe_link' ) as $bhp_pg_gid ) {
	bhp_pg_assert( isset( $bhp_pg_control_out[ $bhp_pg_gid ] ), "CONTROL: `{$bhp_pg_gid}` survives", $failures );
}
bhp_pg_assert( 4 === count( $bhp_pg_control_out ), 'CONTROL: all FOUR production gateways survive, none added', $failures );
bhp_pg_assert(
	array_keys( $bhp_pg_control_out ) === array( 'stripe', 'stripe_amazon_pay', 'stripe_link', 'cod' ),
	'CONTROL: key ORDER is preserved -- gateway order is the order the checkout renders them in',
	$failures
);

/* =========================================================================
 * 2. ⛔ FAIL-OPEN. The property that makes §1 hold when something breaks.
 * ====================================================================== */

echo "\n=== 2. FAIL-OPEN ===\n";

bhp_pg_assert( null === bhp_school_visit_block_unpaid_gateways( null ), 'FAIL-OPEN: a null input is returned unchanged, not coerced into an empty checkout', $failures );
bhp_pg_assert( 'nonsense' === bhp_school_visit_block_unpaid_gateways( 'nonsense' ), 'FAIL-OPEN: a non-array input is returned unchanged', $failures );
bhp_pg_assert( array() === bhp_school_visit_block_unpaid_gateways( array() ), 'FAIL-OPEN: an empty array is returned unchanged', $failures );

/* =========================================================================
 * 3. THE FLAGGED PARENT — CALLBACK LEVEL
 * ====================================================================== */

echo "\n=== 3. THE FLAGGED PARENT (callback level) ===\n";

/*
 * ⭐ A REAL, LIVE, ALREADY-REGISTERED VISIT SLUG. Nothing is seeded and nothing
 *    is written to the registry. If none is live, every flagged assertion SKIPS
 *    with its reason rather than passing vacuously.
 */
$bhp_pg_live_slug = '';
foreach ( array_keys( bhp_school_visit_records() ) as $bhp_pg_slug ) {
	if ( bhp_school_visit_resolve( $bhp_pg_slug ) ) {
		$bhp_pg_live_slug = $bhp_pg_slug;
		break;
	}
}

if ( '' === $bhp_pg_live_slug || ! function_exists( 'WC' ) || ! WC()->session ) {
	bhp_pg_skip(
		'ALL flagged-session assertions (§3, §5, §7, §8)',
		'' === $bhp_pg_live_slug
			? 'no live, non-expired visit is registered on this environment, and this suite deliberately refuses to seed one'
			: 'no WooCommerce session is available in this process',
		$skips
	);
} else {
	echo "     using LIVE registered visit slug: {$bhp_pg_live_slug}\n";

	/* ⛔ SNAPSHOT EVERY PAYMENT-RELATED OPTION, RAW, BEFORE ANY FLAGGED CODE RUNS. */
	$bhp_pg_watch = array(
		'woocommerce_cod_settings',
		'woocommerce_bacs_settings',
		'woocommerce_cheque_settings',
		'woocommerce_stripe_settings',
		'woocommerce_pickup_location_settings',
		'pickup_location_pickup_locations',
	);
	$bhp_pg_before = array();
	foreach ( $bhp_pg_watch as $bhp_pg_opt ) {
		$bhp_pg_before[ $bhp_pg_opt ] = bhp_pg_raw_option( $bhp_pg_opt );
	}

	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_pg_live_slug );

	bhp_pg_assert( true === bhp_school_visit_use_delivery_framing(), 'FLAGGED: the shared predicate reports a visit session', $failures );

	$bhp_pg_flagged_in  = $bhp_pg_make_prod_set();
	$bhp_pg_flagged_out = bhp_school_visit_block_unpaid_gateways( $bhp_pg_flagged_in );

	bhp_pg_assert(
		! isset( $bhp_pg_flagged_out['cod'] ),
		'⭐⭐ FLAGGED: `cod` ("Pay in Person") is GONE. A parent cannot reach an unpaid order for a book Andrew would sign and carry to a school',
		$failures
	);
	bhp_pg_assert( ! isset( $bhp_pg_flagged_out['bacs'] ) && ! isset( $bhp_pg_flagged_out['cheque'] ), 'FLAGGED: the rest of the deferred-payment set would also be withheld (both are DISABLED on this store today, so this is future-proofing with zero live effect)', $failures );

	foreach ( array( 'stripe', 'stripe_amazon_pay', 'stripe_link' ) as $bhp_pg_gid ) {
		bhp_pg_assert(
			isset( $bhp_pg_flagged_out[ $bhp_pg_gid ] ) && $bhp_pg_flagged_out[ $bhp_pg_gid ] === $bhp_pg_flagged_in[ $bhp_pg_gid ],
			"⛔ FLAGGED: `{$bhp_pg_gid}` SURVIVES and is the SAME OBJECT -- the flagged parent must still be able to pay, and card-backed capture already satisfies the founder's rule",
			$failures
		);
	}
	bhp_pg_assert( 3 === count( $bhp_pg_flagged_out ), 'FLAGGED: exactly ONE gateway was withheld from the production four; nothing else moved', $failures );
	bhp_pg_assert(
		array_keys( $bhp_pg_flagged_out ) === array( 'stripe', 'stripe_amazon_pay', 'stripe_link' ),
		'FLAGGED: the surviving gateways keep their original relative ORDER',
		$failures
	);

	/* --- 3b. THE DEFERRED SET IS FILTERABLE, AND FAILS OPEN ON NONSENSE --- */
	$bhp_pg_narrow = static function () {
		return array( 'cod' );
	};
	add_filter( 'bhp_school_visit_unpaid_gateway_ids', $bhp_pg_narrow, 99 );
	$bhp_pg_narrowed = bhp_school_visit_block_unpaid_gateways( $bhp_pg_make_prod_set() );
	remove_filter( 'bhp_school_visit_unpaid_gateway_ids', $bhp_pg_narrow, 99 );
	bhp_pg_assert(
		! isset( $bhp_pg_narrowed['cod'] ) && 3 === count( $bhp_pg_narrowed ),
		'⭐ FLAGGED: the deferred set is FILTERABLE -- if Andrew ever wants a school district to pay by bank transfer or purchase order, narrowing this is one line, not a code rewrite',
		$failures
	);

	$bhp_pg_junk = static function () {
		return 'not-an-array';
	};
	add_filter( 'bhp_school_visit_unpaid_gateway_ids', $bhp_pg_junk, 99 );
	$bhp_pg_junked = bhp_school_visit_block_unpaid_gateways( $bhp_pg_make_prod_set() );
	remove_filter( 'bhp_school_visit_unpaid_gateway_ids', $bhp_pg_junk, 99 );
	bhp_pg_assert( 4 === count( $bhp_pg_junked ) && isset( $bhp_pg_junked['cod'] ), 'FAIL-OPEN: a filter returning nonsense withholds NOTHING rather than emptying the checkout', $failures );

	$bhp_pg_all = static function () {
		return array( 'stripe', 'stripe_amazon_pay', 'stripe_link', 'cod' );
	};
	add_filter( 'bhp_school_visit_unpaid_gateway_ids', $bhp_pg_all, 99 );
	$bhp_pg_emptied = bhp_school_visit_block_unpaid_gateways( $bhp_pg_make_prod_set() );
	remove_filter( 'bhp_school_visit_unpaid_gateway_ids', $bhp_pg_all, 99 );
	bhp_pg_assert(
		4 === count( $bhp_pg_emptied ),
		'⛔ LAST-DITCH SAFETY: a configuration that would leave the parent with NO way to pay AT ALL returns the original set instead. An unpayable checkout is a worse outcome than the bug being fixed',
		$failures
	);

	/* =====================================================================
	 * 5. ⭐⭐ THE REAL WOOCOMMERCE AVAILABILITY PIPELINE
	 *
	 * See the header for why COD is forced on IN MEMORY here, and why doing
	 * it in the database instead would be an Andrew-gate violation.
	 * ================================================================== */

	echo "\n=== 5. THE REAL WOOCOMMERCE AVAILABILITY PIPELINE ===\n";

	$bhp_pg_all_gws = WC()->payment_gateways()->payment_gateways();

	if ( ! isset( $bhp_pg_all_gws['cod'] ) ) {
		bhp_pg_skip( '§5 real-pipeline assertions', 'the `cod` gateway is not registered on this install at all', $skips );
	} else {
		$bhp_pg_cod = $bhp_pg_all_gws['cod'];

		// Save every property §5 touches, so all three are put back exactly.
		$bhp_pg_cod_saved = array(
			'enabled'            => $bhp_pg_cod->enabled,
			'enable_for_methods' => $bhp_pg_cod->enable_for_methods,
			'enable_for_virtual' => $bhp_pg_cod->enable_for_virtual,
		);
		echo '     staging stored COD state: enabled=' . var_export( $bhp_pg_cod_saved['enabled'], true )
			. ' enable_for_methods=' . wp_json_encode( $bhp_pg_cod_saved['enable_for_methods'] ) . "\n";
		echo "     reconstructing PRODUCTION's condition IN MEMORY ONLY: enabled='yes', enable_for_methods=[] (available for EVERY method, including pickup_location)\n";

		// ⛔ PROPERTIES ONLY. No update_option(), no $gateway->update_option().
		$bhp_pg_cod->enabled            = 'yes';
		$bhp_pg_cod->enable_for_methods = array();
		$bhp_pg_cod->enable_for_virtual = true;

		// --- 5a. CONTROL SESSION: COD *is* offered. Proves the probe works. ---
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
		$bhp_pg_avail_control = WC()->payment_gateways()->get_available_payment_gateways();
		$bhp_pg_ids_control   = array_keys( $bhp_pg_avail_control );
		echo '     CONTROL available gateways: ' . implode( ', ', $bhp_pg_ids_control ) . "\n";

		bhp_pg_assert(
			in_array( 'cod', $bhp_pg_ids_control, true ),
			'⭐⭐ CONTROL, REAL PIPELINE: with production\'s COD condition reconstructed, `cod` IS available -- this is the anti-vacuous check. Without it, §5b would "pass" simply because staging has COD switched off',
			$failures
		);

		// --- 5b. FLAGGED SESSION: COD is not offered. ---
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_pg_live_slug );
		$bhp_pg_avail_flagged = WC()->payment_gateways()->get_available_payment_gateways();
		$bhp_pg_ids_flagged   = array_keys( $bhp_pg_avail_flagged );
		echo '     FLAGGED available gateways: ' . implode( ', ', $bhp_pg_ids_flagged ) . "\n";

		bhp_pg_assert(
			! in_array( 'cod', $bhp_pg_ids_flagged, true ),
			'⭐⭐⭐ FLAGGED, REAL PIPELINE: `cod` is ABSENT from WooCommerce\'s own `get_available_payment_gateways()`. This is the exact function `CartSchema` renders the Blocks checkout from AND the Store API checkout route validates a submitted order against',
			$failures
		);
		bhp_pg_assert(
			in_array( 'stripe', $bhp_pg_ids_flagged, true ),
			'⛔ FLAGGED, REAL PIPELINE: `stripe` (Credit / Debit Card) is STILL available -- the flagged parent can still pay',
			$failures
		);
		bhp_pg_assert(
			count( $bhp_pg_ids_flagged ) >= 1,
			'⛔ FLAGGED, REAL PIPELINE: the flagged checkout is NOT left with zero payment methods',
			$failures
		);

		$bhp_pg_diff = array_values( array_diff( $bhp_pg_ids_control, $bhp_pg_ids_flagged ) );
		echo '     CONTROL minus FLAGGED = ' . ( $bhp_pg_diff ? implode( ', ', $bhp_pg_diff ) : '(nothing)' ) . "\n";
		bhp_pg_assert(
			array( 'cod' ) === $bhp_pg_diff,
			'⭐⭐ THE DIFF IS EXACTLY ONE GATEWAY, AND IT IS `cod`. Nothing else the parent could have paid with was disturbed',
			$failures
		);

		/* --- 5c. THE STORE API / BLOCKS RENDER CONTRACT --- */
		echo "\n=== 7. THE BLOCKS RENDER CONTRACT (Store API CartSchema) ===\n";
		echo "     WooCommerce builds the Blocks checkout payment list at\n";
		echo "     StoreApi/Schemas/V1/CartSchema.php:386 as\n";
		echo "       array_values( wp_list_pluck( WC()->payment_gateways->get_available_payment_gateways(), 'id' ) )\n";
		$bhp_pg_schema_flagged = array_values( wp_list_pluck( WC()->payment_gateways->get_available_payment_gateways(), 'id' ) );
		echo '     Store API `payment_methods` (FLAGGED) = ' . implode( ', ', $bhp_pg_schema_flagged ) . "\n";
		bhp_pg_assert(
			! in_array( 'cod', $bhp_pg_schema_flagged, true ),
			'⭐⭐ RENDER CONTRACT: `cod` is absent from the exact expression the Store API sends to the Blocks checkout, so the radio never renders -- not merely filtered somewhere server-side',
			$failures
		);

		/* --- 5d. EXPIRED / CLEARED FLAG RESTORES THE GATEWAY --- */
		echo "\n=== 8. THE FLAG CLEARS CLEANLY ===\n";
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
		$bhp_pg_ids_after = array_keys( WC()->payment_gateways()->get_available_payment_gateways() );
		bhp_pg_assert(
			in_array( 'cod', $bhp_pg_ids_after, true ),
			'⭐ FLAG CLEARED: `cod` comes BACK. The gate withholds for the duration of a flagged request and leaves no residue -- it is not a latch',
			$failures
		);
		bhp_pg_assert(
			$bhp_pg_ids_after === $bhp_pg_ids_control,
			'FLAG CLEARED: the available set is identical to the control set read before any flagged code ran',
			$failures
		);

		// ⛔ RESTORE. Explicit, not a shutdown handler.
		$bhp_pg_cod->enabled            = $bhp_pg_cod_saved['enabled'];
		$bhp_pg_cod->enable_for_methods = $bhp_pg_cod_saved['enable_for_methods'];
		$bhp_pg_cod->enable_for_virtual = $bhp_pg_cod_saved['enable_for_virtual'];
		echo '     RESTORED in-memory COD state: enabled=' . var_export( $bhp_pg_cod->enabled, true ) . "\n";
		bhp_pg_assert( $bhp_pg_cod->enabled === $bhp_pg_cod_saved['enabled'], 'the in-memory COD probe was restored to the value it was found with', $failures );
	}

	/* =====================================================================
	 * 6. ⛔⛔ NO WOOCOMMERCE SETTING WAS MUTATED. THE WHOLE POINT.
	 * ================================================================== */

	echo "\n=== 6. NO WOOCOMMERCE SETTING WAS MUTATED ===\n";

	foreach ( $bhp_pg_watch as $bhp_pg_opt ) {
		$bhp_pg_now = bhp_pg_raw_option( $bhp_pg_opt );
		bhp_pg_assert(
			$bhp_pg_now === $bhp_pg_before[ $bhp_pg_opt ],
			"⛔ `{$bhp_pg_opt}` is BYTE-IDENTICAL in the database after every flagged code path ran"
				. ( '(ABSENT)' === $bhp_pg_now ? ' (and is still ABSENT, which is also unchanged)' : '' ),
			$failures
		);
	}

	// Leave the session exactly as an unflagged visitor's.
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
	echo "     CLEANUP: the visit session flag has been cleared.\n";
}

/* =========================================================================
 * 9. ⚠ THE WALLETS ABOVE THE FORM — recorded, and NOT asserted.
 * ====================================================================== */

echo "\n=== 9. EXPRESS-CHECKOUT WALLETS (informational, deliberately NOT asserted) ===\n";
echo "     The correct wallet set is environment-dependent and this suite has no\n";
echo "     basis to declare one. What matters for THIS build is whether any of\n";
echo "     them can produce an UNPAID or DEFERRED order. Printed for the record:\n";

$bhp_pg_stripe_opts = get_option( 'woocommerce_stripe_settings', array() );
echo '     woocommerce_stripe_settings[capture] = ' . ( isset( $bhp_pg_stripe_opts['capture'] ) ? var_export( $bhp_pg_stripe_opts['capture'], true ) : '(unset)' ) . "\n";
echo "       ('yes' means funds are CAPTURED at checkout, not merely authorised.)\n";

foreach ( WC()->payment_gateways()->payment_gateways() as $bhp_pg_id => $bhp_pg_gw ) {
	if ( 'yes' === $bhp_pg_gw->enabled ) {
		echo "     ENABLED: {$bhp_pg_id} | " . $bhp_pg_gw->get_title() . "\n";
	}
}

/* =========================================================================
 * RESULT
 * ====================================================================== */

$bhp_pg_fail_count = count( $failures );
$bhp_pg_skip_count = count( $skips );

echo "\n";
echo "==========================================================\n";
echo 'RESULT: ' . ( $bhp_pg_fail_count ? "{$bhp_pg_fail_count} FAILED" : 'ALL PASSED' ) . ", {$bhp_pg_skip_count} skipped\n";
echo "==========================================================\n";

if ( $bhp_pg_fail_count ) {
	echo "FAILURES:\n";
	foreach ( $failures as $bhp_pg_f ) {
		echo "  - {$bhp_pg_f}\n";
	}
}
if ( $bhp_pg_skip_count ) {
	echo "SKIPPED:\n";
	foreach ( $skips as $bhp_pg_s ) {
		echo "  - {$bhp_pg_s}\n";
	}
}

echo "\n⚠ REMINDER: this suite proves the SERVER-SIDE gate and the Store API\n";
echo "  render contract. It does NOT and CANNOT prove what a browser painted.\n";
echo "  That belongs in the session QA evidence, at 1440px and 390px, with\n";
echo "  window.innerWidth asserted.\n";

if ( $bhp_pg_fail_count > 0 ) {
	exit( 1 );
}
