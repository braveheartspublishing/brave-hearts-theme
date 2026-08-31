<?php
/**
 * CYCLE166-LD-VISIT-STOCK-GATE (carrier item 235) — A CHAPTER TITLE CLOSES
 * FOR SCHOOL VISITS WHEN ITS SHELF STOCK REACHES 1.
 *
 * Run via WP-CLI, from the WordPress document root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-shelf-stock-gate.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔⛔ THE CONTROL PATH. An ordinary shopper must be able to buy every
 *      chapter paperback, every box and every offer, exactly as in 1.8.70,
 *      no matter how low the shelf is. The shelf is Andrew's personal stock
 *      for hand delivery; an ordinary order prints on demand at Bookvault
 *      and consumes none of it. A regression here hits every customer on the
 *      site; the thing being built helps the parents of two schools. §2, §7.
 *
 *   2. ⛔⛔ IT IS NOT WOOCOMMERCE INVENTORY. `_stock_status` on all six core
 *      products must be untouched, and no option may be written. §8 proves
 *      both by re-reading the raw rows before and after.
 *
 *   3. ⭐⭐ THE ARITHMETIC IS THE RULING. `remaining <= 1` closes, not
 *      `<= 0`. The last copy is deliberately held back as buffer. §3.
 *
 *   4. ⭐⭐ THE SERVER REFUSAL IS REAL, NOT A HIDDEN BUTTON. §5 puts a REAL
 *      closed title through the REAL hooks and asserts each one refuses.
 *
 *   5. ⭐ THE BOX CLOSES WHEN ANY ONE TITLE CLOSES. §6.
 *
 *   6. ⛔ NO HARDCODED PRODUCT IDS. §1 asserts every id is resolved through
 *      `bhp_bundle_catalog()`. ⭐ THIS IS THE PORTABILITY ASSERTION: the six
 *      core ids happen to match across environments today, but their SKUs do
 *      NOT (production carries ISBNs, staging carries `BHP-*` codes), so a
 *      SKU-keyed or id-keyed shelf would pass vacuously on one of them.
 *
 *   7. ⛔ THE VOICE RULE. Standing rule §9.1: no "we"/"us"/"our" standing for
 *      the company, no em dash, American spelling, and no restock promise.
 *      Asserted on the two strings this build adds. §4.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO stock status, NO coupon, NO
 *    shipping, tax, pickup or payment setting, on any environment.
 *    ⭐ THE CLOSED STATE IS FORCED THROUGH THE `bhp_visit_shelf_title_is_closed`
 *    FILTER, NOT BY WRITING THE BASELINE OPTION. That is the whole reason the
 *    filter runs even when no baseline exists: a suite that had to write
 *    `bhp_visit_shelf_stock` to test anything could not be run safely on a
 *    live environment, and would be skipped exactly when it mattered.
 * ⛔ It places NO order, modifies NO order, delivers NO webhook, takes NO
 *    payment. It READS the real orders in §3 and asserts nothing about them
 *    beyond the count the gate computes.
 * ⛔ It touches NO visit registry row.
 * ⚠ It DOES set one key in the WooCommerce session and DOES put items in the
 *    CLI request's own cart. Both are session state, not stored settings, and
 *    both are cleared in §9. It is the only way to exercise the real hooks
 *    rather than a mock.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⚠️ THE COUNTERS LIVE IN `$GLOBALS`, EXPLICITLY, AND THAT IS NOT STYLE.
 *
 * `wp eval-file` includes this file from INSIDE a method, so a bare top-level
 * `$pass = 0;` is a LOCAL variable of that method, not a global. A `global
 * $pass;` inside the helper below then binds to a DIFFERENT, empty variable,
 * every increment is lost, and the suite reports "0 passed, 0 failed" while
 * printing a screen of PASS and FAIL lines.
 *
 * ⛔ THAT IS THE WORST POSSIBLE FAILURE MODE FOR A TEST SUITE: it reports
 *    success-shaped output regardless of what happened, and the exit code is
 *    always 0, so a CI step or a reviewer skimming the last line sees a pass.
 *    Observed first-hand on staging, 2026-08-24, on this suite's first run.
 */
$GLOBALS['bhp_pass'] = 0;
$GLOBALS['bhp_fail'] = 0;
$GLOBALS['bhp_skip'] = 0;

/**
 * Assert helper.
 *
 * @param string $label What is being asserted.
 * @param bool   $cond  The result.
 * @param string $detail Extra context printed either way.
 */
function bhp_t( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		++$GLOBALS['bhp_pass'];
		echo "  PASS  $label" . ( '' !== $detail ? "   [$detail]" : '' ) . "\n";
	} else {
		++$GLOBALS['bhp_fail'];
		echo "  FAIL  $label" . ( '' !== $detail ? "   [$detail]" : '' ) . "\n";
	}
}

function bhp_skip( $label, $why ) {
	++$GLOBALS['bhp_skip'];
	echo "  SKIP  $label   [$why]\n";
}

function bhp_h( $title ) {
	echo "\n" . str_repeat( '=', 78 ) . "\n$title\n" . str_repeat( '=', 78 ) . "\n";
}

echo "\nCYCLE166-LD-VISIT-STOCK-GATE — visit shelf stock suite\n";
echo 'SITE: ' . home_url() . "\n";
echo 'PLUGIN: ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '?' ) . "\n";

/* =========================================================================
 * ⭐⭐⭐ §0a — 1.8.76: THE BACKORDER ALLOWANCE, AND WHY THE REST OF THIS
 *     SUITE NOW RUNS WITH IT SWITCHED OFF.
 *     (`CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`)
 * =========================================================================
 *
 * ⛔⛔ READ THIS BEFORE CHANGING ANYTHING BELOW. Andrew Signore, 2026-08-28,
 *     RELAYED (carrier item 363): "I think we allow backorders and we will get
 *     the new books in latest by Sept 10th."
 *
 * That ruling means an EXHAUSTED title is no longer automatically a REFUSED
 * title. `bhp_visit_shelf_title_is_closed()` — which every surface and all five
 * refusal seams ask — now returns false for an exhausted title while backorders
 * are on, which is the entire point of the release.
 *
 * ⭐⭐ SO §2 THROUGH §7b WOULD ALL FAIL AGAINST THE NEW DEFAULT, AND THEY ARE
 *     **NOT** DELETED AND **NOT** WEAKENED. They assert the sold-out gate, which
 *     is still real, still shipped, still one WP-CLI line away, and is what a
 *     parent meets the moment Andrew decides a title genuinely cannot be
 *     backordered. ⛔ DELETING THEM WOULD DISCARD THE ONLY COVERAGE OF THE FIVE
 *     SERVER REFUSAL SEAMS THIS PLUGIN HAS. Instead they run with backorders
 *     explicitly OFF, which is a real supported configuration and not a stub.
 *
 * ⭐ THE NEW DEFAULT — backorders ON — IS ASSERTED HERE, BEFORE ANY FILTER IS
 *   INSTALLED, and exercised end to end in the new §7c. Between the two, both
 *   modes are covered rather than one being traded for the other.
 * ====================================================================== */
bhp_h( '§0a — 1.8.76 backorder allowance: the default, the switch, and the split' );

$bhp_backorder_module = function_exists( 'bhp_visit_shelf_backorder_allowed' );
bhp_t( 'the backorder module is loaded', $bhp_backorder_module );

if ( $bhp_backorder_module ) {
	foreach ( array(
		'bhp_visit_shelf_backorder_allowed',
		'bhp_visit_shelf_title_is_backordered_for_request',
		'bhp_visit_shelf_backorder_label',
		'bhp_visit_shelf_backorder_message',
		'bhp_visit_shelf_render_backorder_line',
	) as $fn ) {
		bhp_t( "1.8.76 function exists: {$fn}()", function_exists( $fn ) );
	}

	/*
	 * ⭐ THE DEFAULT IS ON, AND IT IS ASSERTED AGAINST THE REAL, UNFILTERED
	 *    STATE OF THIS ENVIRONMENT. If somebody sets the option to `no` on a
	 *    server, this assertion is SUPPOSED to fail there, loudly, because that
	 *    means the founder's ruling is not in force on that environment and
	 *    somebody needs to know.
	 */
	bhp_t(
		'DEFAULT: backorders are ALLOWED with the option unset (founder item 363 in force on this environment)',
		true === bhp_visit_shelf_backorder_allowed(),
		'option bhp_visit_shelf_backorders = ' . var_export( get_option( 'bhp_visit_shelf_backorders', null ), true )
	);

	// The switch works in both directions, through the filter, with no write.
	add_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 99 );
	bhp_t( 'the allowance can be switched OFF by filter', false === bhp_visit_shelf_backorder_allowed() );
	remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 99 );
	bhp_t( 'removing the filter restores the default ON', true === bhp_visit_shelf_backorder_allowed() );

	// ⭐ THE SPLIT ITSELF: two functions, two questions, both present.
	bhp_t( 'the pure shelf fact is its own function: bhp_visit_shelf_title_is_exhausted()', function_exists( 'bhp_visit_shelf_title_is_exhausted' ) );
	bhp_t( 'the purchase gate kept its old name and signature: bhp_visit_shelf_title_is_closed()', function_exists( 'bhp_visit_shelf_title_is_closed' ) );
}

/*
 * ⛔⛔ THE SUITE-WIDE SWITCH. Everything from §2 to §7b asserts the SOLD-OUT
 *     mode. It is removed again in §7c and re-checked in §9.
 */
add_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );
echo "\n  NOTE  §2..§7b run with backorders FORCED OFF (1.8.75 gate semantics). §7c tests the 1.8.76 default.\n";

/* =========================================================================
 * §0 — THE MODULE LOADED AT ALL
 * ====================================================================== */
bhp_h( '§0 — module present' );

$required = array(
	'bhp_visit_shelf_baseline',
	'bhp_visit_shelf_committed',
	'bhp_visit_shelf_remaining',
	'bhp_visit_shelf_title_is_closed',
	'bhp_visit_shelf_title_is_closed_for_request',
	'bhp_visit_shelf_closed_titles',
	'bhp_visit_shelf_closed_map_for_request',
	'bhp_visit_shelf_open_title_count',
	'bhp_visit_shelf_identify_title',
	'bhp_visit_shelf_is_closed_item',
	'bhp_visit_shelf_sold_out_label',
	'bhp_visit_shelf_sold_out_message',
	// 1.8.72 — the counter.
	'bhp_visit_shelf_title_counter',
	'bhp_visit_shelf_counter_for_request',
	'bhp_visit_shelf_counter_map_for_request',
	'bhp_visit_shelf_constraining_title_for_request',
	'bhp_visit_shelf_counter_on_complete_box',
	'bhp_visit_shelf_counter_label',
	'bhp_visit_shelf_counter_label_named',
	'bhp_visit_shelf_render_counter',
	'bhp_school_visit_is_sold_out_title',
	'bhp_school_visit_refusal_message',
	'bhp_school_visit_cart_refusal_message',
);
foreach ( $required as $fn ) {
	bhp_t( "function exists: $fn", function_exists( $fn ) );
}
bhp_t( 'BHP_VISIT_SHELF_BUFFER is defined and equals 1 (the founder ruling)', defined( 'BHP_VISIT_SHELF_BUFFER' ) && 1 === (int) BHP_VISIT_SHELF_BUFFER, defined( 'BHP_VISIT_SHELF_BUFFER' ) ? (string) BHP_VISIT_SHELF_BUFFER : 'undefined' );
bhp_t( 'BHP_VISIT_SHELF_OPTION is defined', defined( 'BHP_VISIT_SHELF_OPTION' ), defined( 'BHP_VISIT_SHELF_OPTION' ) ? BHP_VISIT_SHELF_OPTION : 'undefined' );
bhp_t( 'BHP_VISIT_SHELF_COUNTER_MAX is defined and equals 10 (the founder addition)', defined( 'BHP_VISIT_SHELF_COUNTER_MAX' ) && 10 === (int) BHP_VISIT_SHELF_COUNTER_MAX, defined( 'BHP_VISIT_SHELF_COUNTER_MAX' ) ? (string) BHP_VISIT_SHELF_COUNTER_MAX : 'undefined' );
bhp_t(
	'the counter window is bounded BY THE BUFFER, not by a second literal 1',
	defined( 'BHP_VISIT_SHELF_COUNTER_MAX' ) && defined( 'BHP_VISIT_SHELF_BUFFER' )
		&& (int) BHP_VISIT_SHELF_COUNTER_MAX > (int) BHP_VISIT_SHELF_BUFFER,
	'buffer=' . BHP_VISIT_SHELF_BUFFER . ' ceiling=' . BHP_VISIT_SHELF_COUNTER_MAX
);
bhp_t( 'the three-book box counter is OFF by default (the recommendation, asserted)', false === bhp_visit_shelf_counter_on_complete_box() );

/* =========================================================================
 * §1 — REGISTRY-DRIVEN. NO HARDCODED PRODUCT IDS.
 * ====================================================================== */
bhp_h( '§1 — every id resolves through bhp_bundle_catalog(), nothing is hardcoded' );

$catalog = bhp_bundle_catalog();
$slugs   = bhp_visit_shelf_title_slugs();

bhp_t(
	'title slugs come from the catalog paperback keys',
	$slugs === array_keys( $catalog['paperback'] ),
	implode( ',', $slugs )
);

foreach ( $catalog['paperback'] as $slug => $ed ) {
	$pid = (int) $ed['product_id'];
	$vid = (int) $ed['variation_id'];

	bhp_t( "identify_title resolves product id $pid -> $slug", $slug === bhp_visit_shelf_identify_title( $pid, 0 ) );
	if ( $vid ) {
		bhp_t( "identify_title resolves variation id $vid -> $slug", $slug === bhp_visit_shelf_identify_title( $pid, $vid ) );
		bhp_t( "identify_title resolves a bare variation id $vid -> $slug", $slug === bhp_visit_shelf_identify_title( $vid, 0 ) );
	}
}

// A hardcover, the activity book and the colouring book must NOT resolve to a
// chapter title: they are not on the shelf this gate counts.
foreach ( $catalog['hardcover'] as $slug => $ed ) {
	bhp_t(
		"hardcover {$slug} (id {$ed['product_id']}) is NOT a shelf-counted chapter title",
		null === bhp_visit_shelf_identify_title( (int) $ed['product_id'], 0 )
	);
}

/* =========================================================================
 * §2 — ⛔⛔ THE CONTROL PATH. NO VISIT FLAG -> ABSOLUTELY NOTHING CHANGES.
 * ====================================================================== */
bhp_h( '§2 — CONTROL PATH: an ordinary shopper is untouched, however low the shelf' );

// Make sure no visit flag is set for this part.
if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
	WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
}

bhp_t( 'no visit flag: bhp_school_visit_paperback_only() is false', ! bhp_school_visit_paperback_only() );

// Force EVERY title closed at the shelf level, and assert the ordinary
// shopper still sees and can buy everything.
$force_all_closed = function () {
	return true; };
add_filter( 'bhp_visit_shelf_title_is_closed', $force_all_closed, 99 );

bhp_t(
	'shelf reports every title closed (the forced state is actually in effect)',
	count( bhp_visit_shelf_closed_titles() ) === count( $slugs ),
	implode( ',', bhp_visit_shelf_closed_titles() )
);

foreach ( $slugs as $slug ) {
	bhp_t( "CONTROL: {$slug} is NOT closed for an unflagged request", ! bhp_visit_shelf_title_is_closed_for_request( $slug ) );
}
bhp_t( 'CONTROL: closed map for an unflagged request is empty', array() === bhp_visit_shelf_closed_map_for_request() );

/*
 * ⛔⛔ THE CONTROL PATH IS ASSERTED AT THE **SEAMS**, NOT ON
 *     `bhp_school_visit_is_refused_item()`, AND THE DISTINCTION IS REAL.
 *
 * ⚠️ AN EARLIER DRAFT OF THIS SUITE ASSERTED `! is_refused_item( $paperback )`
 *    HERE AND FAILED ON STAGING. The assertion was wrong, not the code, and
 *    the correction is recorded rather than quietly deleted:
 *
 *    `bhp_school_visit_is_refused_item()` IS DELIBERATELY SESSION-AGNOSTIC.
 *    It answers "is this item on the refused list", which is a fact about the
 *    ITEM. It has behaved this way since 1.8.65 and it returns TRUE for a
 *    hardcover on an ordinary shopper's request too. ⭐ EVERY ONE OF THE FIVE
 *    SEAMS APPLIES THE SESSION GATE ITSELF, as its SECOND test, and that is
 *    what protects the ordinary shopper. Verified by reading all five in
 *    `school-visit-paperback-only.php` this build.
 *
 * ⭐ SO THE HONEST CONTROL-PATH TEST IS: drive the real seams with every title
 *    forced closed, and prove they let an unflagged shopper through anyway.
 */
$eve_pb = (int) $catalog['paperback']['everest']['product_id'];
$mar_pb = (int) $catalog['paperback']['mariana']['product_id'];
$mar_vid = (int) $catalog['paperback']['mariana']['variation_id'];
$hc_eve = (int) $catalog['hardcover']['everest']['product_id'];

bhp_t(
	'CONTROL: SEAM 1 passes an everest paperback add for an unflagged request',
	true === bhp_school_visit_block_hardcover_add( true, $eve_pb, 1, 0 )
);
bhp_t(
	'CONTROL: SEAM 1 passes a mariana paperback add for an unflagged request',
	true === bhp_school_visit_block_hardcover_add( true, $mar_pb, 1, $mar_vid )
);
bhp_t(
	'CONTROL: SEAM 1 still passes a HARDCOVER add for an unflagged request',
	true === bhp_school_visit_block_hardcover_add( true, $hc_eve, 1, 0 )
);

$control_threw = false;
try {
	bhp_school_visit_block_hardcover_cart_add( array(), $eve_pb, 0 );
	bhp_school_visit_block_hardcover_cart_add( array(), $hc_eve, 0 );
} catch ( Exception $e ) {
	$control_threw = true;
}
bhp_t( 'CONTROL: SEAM 5 throws for nothing on an unflagged request', ! $control_threw );

if ( function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();
	$ok_pb = WC()->cart->add_to_cart( $eve_pb, 1 );
	$ok_hc = WC()->cart->add_to_cart( $hc_eve, 1 );
	bhp_t( 'CONTROL: an unflagged shopper CAN add a "closed" paperback to a real cart', (bool) $ok_pb );
	bhp_t( 'CONTROL: an unflagged shopper CAN add a hardcover to a real cart', (bool) $ok_hc );

	$control_errors = new WP_Error();
	bhp_school_visit_hardcover_store_api_cart_error( $control_errors, WC()->cart );
	bhp_t( 'CONTROL: SEAM 3 raises NO checkout error for an unflagged cart', ! $control_errors->has_errors() );
	WC()->cart->empty_cart();
	wc_clear_notices();
}
bhp_t(
	'CONTROL: the "any two" card still offers 3 open titles when unflagged',
	3 === bhp_visit_shelf_open_title_count( 'paperback' ),
	(string) bhp_visit_shelf_open_title_count( 'paperback' )
);

remove_filter( 'bhp_visit_shelf_title_is_closed', $force_all_closed, 99 );

/* =========================================================================
 * §3 — THE ARITHMETIC, AGAINST THE REAL ORDERS ON THIS ENVIRONMENT
 * ====================================================================== */
bhp_h( '§3 — baseline, committed and remaining' );

$baseline  = bhp_visit_shelf_baseline();
$committed = bhp_visit_shelf_committed();
$remaining = bhp_visit_shelf_remaining();

echo '  committed statuses: ' . implode( ', ', bhp_visit_shelf_committed_statuses() ) . "\n";
echo '  baseline as_of:     ' . ( '' !== $baseline['as_of'] ? $baseline['as_of'] : '(none set)' ) . "\n";
foreach ( $slugs as $slug ) {
	printf(
		"  %-10s baseline=%-6s committed=%-4d remaining=%-6s closed=%s\n",
		$slug,
		isset( $baseline['counts'][ $slug ] ) ? (string) $baseline['counts'][ $slug ] : '(none)',
		isset( $committed[ $slug ] ) ? (int) $committed[ $slug ] : 0,
		isset( $remaining[ $slug ] ) ? (string) $remaining[ $slug ] : '(n/a)',
		bhp_visit_shelf_title_is_closed( $slug ) ? 'YES' : 'no'
	);
}

// Committed must equal an INDEPENDENT recount of the same orders, done here
// with a different query shape so a bug in one is not mirrored by the other.
$independent = array();
$orders      = wc_get_orders(
	array(
		'limit'  => -1,
		'status' => 'any',
	)
);
foreach ( $orders as $o ) {
	if ( 'yes' !== (string) $o->get_meta( '_bhp_school_pickup' ) ) {
		continue;
	}
	if ( ! in_array( $o->get_status(), bhp_visit_shelf_committed_statuses(), true ) ) {
		continue;
	}
	foreach ( $o->get_items() as $item ) {
		$slug = bhp_visit_shelf_identify_title( (int) $item->get_product_id(), (int) $item->get_variation_id() );
		if ( null === $slug ) {
			continue;
		}
		$independent[ $slug ] = ( isset( $independent[ $slug ] ) ? $independent[ $slug ] : 0 ) + (int) $item->get_quantity();
	}
}
ksort( $independent );
$sorted_committed = $committed;
ksort( $sorted_committed );
bhp_t(
	'committed count matches an independent recount of the same orders',
	$sorted_committed === $independent,
	'gate=' . wp_json_encode( $sorted_committed ) . ' recount=' . wp_json_encode( $independent )
);

// The buffer rule itself, exercised through the filter rather than a write.
$fake_remaining = null;
$probe          = function ( $closed, $slug, $rem ) use ( &$fake_remaining ) {
	if ( 'everest' !== $slug || null === $fake_remaining ) {
		return $closed;
	}
	return $fake_remaining <= (int) BHP_VISIT_SHELF_BUFFER;
};
add_filter( 'bhp_visit_shelf_title_is_closed', $probe, 50, 3 );
foreach ( array( 5 => false, 3 => false, 2 => false, 1 => true, 0 => true, -1 => true ) as $rem => $expect_closed ) {
	$fake_remaining = $rem;
	bhp_t(
		sprintf( 'remaining=%d  =>  closed=%s', $rem, $expect_closed ? 'YES' : 'no' ),
		bhp_visit_shelf_title_is_closed( 'everest' ) === $expect_closed
	);
}
$fake_remaining = null;
remove_filter( 'bhp_visit_shelf_title_is_closed', $probe, 50 );

// Fail-open: a title with no baseline is never closed by arithmetic.
$unbaselined = array_diff( $slugs, array_keys( $baseline['counts'] ) );
if ( empty( $unbaselined ) ) {
	bhp_skip( 'fail-open: an uncounted title is never closed', 'every title has a baseline on this environment' );
} else {
	foreach ( $unbaselined as $slug ) {
		bhp_t( "fail-open: uncounted title {$slug} is not closed", ! bhp_visit_shelf_title_is_closed( $slug ) );
	}
}

/* =========================================================================
 * §4 — THE VOICE RULE ON THE TWO NEW STRINGS
 * ====================================================================== */
bhp_h( '§4 — customer-facing copy: §9.1 voice, no em dash, American spelling, no restock promise' );

foreach ( array(
	'sold-out label'   => bhp_visit_shelf_sold_out_label(),
	'sold-out message' => bhp_visit_shelf_sold_out_message(),
) as $what => $string ) {
	echo "  $what: \"$string\"\n";

	bhp_t( "$what: no company \"we\"", ! preg_match( '/\b(we|us|our|we\'re|we\'ve|we\'ll)\b/i', $string ) );
	bhp_t( "$what: no em dash", false === strpos( $string, "\xE2\x80\x94" ) );
	bhp_t( "$what: no en dash", false === strpos( $string, "\xE2\x80\x93" ) );
	bhp_t( "$what: American spelling (no \"colour\")", ! preg_match( '/colour/i', $string ) );
	bhp_t( "$what: no restock date or promise", ! preg_match( '/\b(restock|back in stock|next week|september|sept\b|shortly|soon)\b/i', $string ) );
	bhp_t( "$what: non-empty", '' !== trim( $string ) );
}
bhp_t(
	'the sold-out message is NOT the paperback-only message',
	bhp_visit_shelf_sold_out_message() !== bhp_school_visit_paperback_only_message()
);

/* =========================================================================
 * §4a — ⭐⭐ 1.8.72: THE COUNTER WINDOW, SWEPT END TO END
 *
 * ⛔ THE WHOLE FEATURE IS THE SHAPE OF THIS WINDOW, so it is swept rather than
 *    spot-checked. The three states the founder named, plus both boundaries
 *    and both boundary-adjacent values, plus the states nobody named
 *    (0, negative, uncounted) which are exactly where a display bug reaches a
 *    parent as a nonsense number.
 *
 * ⛔ FORCED THROUGH THE `bhp_visit_shelf_title_counter` FILTER'S `$live`
 *    ARGUMENT, NOT BY WRITING `bhp_visit_shelf_stock`. Same discipline as §5:
 *    a suite that had to write the baseline option could not run safely on a
 *    live environment.
 * ====================================================================== */
bhp_h( '§4a — the counter window: nothing above 10, the number in 2..10, sold out at 1 and below' );

$probe_slug = $slugs[0];

/*
 * Force a specific `remaining` for ONE title, through the two filters that
 * already exist for exactly this purpose, and read back what the display layer
 * decides. `$forced_remaining` is closed over and re-pointed per case.
 */
$forced_remaining = null;

$force_remaining = function ( $out, $slug, $live, $ceiling ) use ( &$forced_remaining, $probe_slug ) {
	if ( $slug !== $probe_slug || null === $forced_remaining ) {
		return $out;
	}
	$n = (int) $forced_remaining;
	// Re-run the REAL rule against the forced number rather than asserting a
	// hand-computed answer -- the point is the window, not the arithmetic twice.
	return ( $n > (int) BHP_VISIT_SHELF_BUFFER && $n <= (int) $ceiling ) ? $n : null;
};
$force_closed_by_remaining = function ( $closed, $slug ) use ( &$forced_remaining, $probe_slug ) {
	if ( $slug !== $probe_slug || null === $forced_remaining ) {
		return $closed;
	}
	return (int) $forced_remaining <= (int) BHP_VISIT_SHELF_BUFFER;
};

add_filter( 'bhp_visit_shelf_title_counter', $force_remaining, 99, 4 );
add_filter( 'bhp_visit_shelf_title_is_closed', $force_closed_by_remaining, 99, 2 );

echo "  probe title: $probe_slug\n";

$window_cases = array(
	// remaining => expected counter (null means "print nothing"), expected closed
	array( 500, null, false, 'far above the ceiling' ),
	array( 12, null, false, 'above the ceiling' ),
	array( 11, null, false, 'ONE above the ceiling - the boundary that must stay silent' ),
	array( 10, 10, false, 'AT the ceiling - the highest number that prints' ),
	array( 9, 9, false, 'inside the window' ),
	array( 6, 6, false, "the founder's own example number" ),
	array( 3, 3, false, 'inside the window' ),
	array( 2, 2, false, 'AT the floor - the lowest number that prints' ),
	array( 1, null, true, 'AT the buffer - sold out, and NO counter' ),
	array( 0, null, true, 'empty shelf - sold out, and NO counter' ),
	array( -4, null, true, 'oversold - sold out, and NO counter, never a negative number' ),
);

foreach ( $window_cases as $case ) {
	list( $remaining_in, $expect_counter, $expect_closed, $why ) = $case;
	$forced_remaining = $remaining_in;

	$got_counter = bhp_visit_shelf_title_counter( $probe_slug );
	$got_closed  = bhp_visit_shelf_title_is_closed( $probe_slug );

	bhp_t(
		sprintf( 'remaining=%d -> counter %s   (%s)', $remaining_in, null === $expect_counter ? 'NOTHING' : (string) $expect_counter, $why ),
		$expect_counter === $got_counter,
		'got ' . ( null === $got_counter ? 'null' : (string) $got_counter )
	);
	bhp_t(
		sprintf( 'remaining=%d -> closed=%s', $remaining_in, $expect_closed ? 'YES' : 'no' ),
		$expect_closed === $got_closed
	);
	bhp_t(
		sprintf( 'remaining=%d -> the two states are MUTUALLY EXCLUSIVE', $remaining_in ),
		! ( $got_closed && null !== $got_counter ),
		$got_closed ? 'closed' : 'open'
	);
}

/*
 * ⛔ THE ONE THAT MATTERS MOST HERE: a title closed BY HAND at a healthy
 *    remaining must go silent. Andrew can close a title through
 *    `bhp_visit_shelf_title_is_closed` without touching the count, and
 *    "Only 6 left" printed beside a sold-out badge would be the worst output
 *    this feature could produce.
 */
$forced_remaining = null;
$hand_close       = function ( $closed, $slug ) use ( $probe_slug ) {
	return $slug === $probe_slug ? true : $closed; };
add_filter( 'bhp_visit_shelf_title_is_closed', $hand_close, 100, 2 );
bhp_t(
	'a title closed BY HAND at a healthy count prints NO counter (closed outranks counted)',
	null === bhp_visit_shelf_title_counter( $probe_slug )
);
remove_filter( 'bhp_visit_shelf_title_is_closed', $hand_close, 100 );

remove_filter( 'bhp_visit_shelf_title_counter', $force_remaining, 99 );
remove_filter( 'bhp_visit_shelf_title_is_closed', $force_closed_by_remaining, 99 );

// An uncounted title (no baseline row at all) must never produce a number.
foreach ( $slugs as $slug ) {
	if ( ! isset( bhp_visit_shelf_remaining()[ $slug ] ) ) {
		bhp_t( "fail-silent: uncounted title {$slug} has NO counter", null === bhp_visit_shelf_title_counter( $slug ) );
	}
}

/* =========================================================================
 * §4b — THE COUNTER'S WORDS: voice, American spelling, and NO URGENCY THEATER
 * ====================================================================== */
bhp_h( '§4b — counter copy: §9.1 voice, no em dash, American spelling, no urgency device' );

foreach ( array( 2, 6, 10 ) as $n ) {
	$string = bhp_visit_shelf_counter_label( $n );
	echo "  counter($n): \"$string\"\n";

	bhp_t( "counter($n): contains the live number itself", false !== strpos( $string, (string) $n ) );
	bhp_t( "counter($n): no company \"we\"", ! preg_match( '/\b(we|us|our|we\'re|we\'ve|we\'ll)\b/i', $string ) );
	bhp_t( "counter($n): no em dash", false === strpos( $string, "\xE2\x80\x94" ) );
	bhp_t( "counter($n): no en dash", false === strpos( $string, "\xE2\x80\x93" ) );
	bhp_t( "counter($n): American spelling (no \"colour\")", ! preg_match( '/colour/i', $string ) );
	bhp_t( "counter($n): no restock date or promise", ! preg_match( '/\b(restock|back in stock|next week|september|sept\b|shortly|soon)\b/i', $string ) );

	/*
	 * ⛔ THE FOUNDER'S CONSTRAINT, ASSERTED RATHER THAN TRUSTED: "never styled
	 *    as urgency theater beyond the plain fact."
	 */
	bhp_t( "counter($n): no urgency device", ! preg_match( '/\b(hurry|act (now|fast)|going fast|selling fast|last chance|don\'t miss|dont miss|almost gone|while supplies last|limited time|final)\b/i', $string ) );
	bhp_t( "counter($n): no exclamation mark", false === strpos( $string, '!' ) );
	bhp_t( "counter($n): not shouted in capitals", $string !== strtoupper( $string ) );
	bhp_t( "counter($n): says it is for the school visit, not the shop", false !== stripos( $string, 'school visit' ) );
	bhp_t( "counter($n): non-empty", '' !== trim( $string ) );
}

bhp_t(
	'the counter sentence is NOT the sold-out sentence',
	bhp_visit_shelf_counter_label( 6 ) !== bhp_visit_shelf_sold_out_message()
		&& bhp_visit_shelf_counter_label( 6 ) !== bhp_visit_shelf_sold_out_label()
);
bhp_t(
	'the counter number is not baked into the string: 2 and 10 differ',
	bhp_visit_shelf_counter_label( 2 ) !== bhp_visit_shelf_counter_label( 10 )
);

$named = bhp_visit_shelf_counter_label_named( $slugs[0], 4 );
echo "  named counter: \"$named\"\n";
/*
 * ⛔ THE SERIES PREFIX MUST BE GONE. Asserted both ways so neither half can
 *    regress silently: the distinctive part of the title is present, and the
 *    "Adventures of Charlotte and Henry:" prefix is not.
 */
$named_parts = explode( ': ', $catalog['paperback'][ $slugs[0] ]['label'] );
$named_short = trim( (string) array_pop( $named_parts ) );
bhp_t( 'the named counter carries the book title, not the slug', false !== strpos( $named, $named_short ), $named_short );
bhp_t( 'the named counter drops the series prefix (it is unreadable in a sentence)', false === stripos( $named, 'Adventures of Charlotte and Henry:' ) );
bhp_t( 'the named counter says "copies", so the number is unambiguous', false !== stripos( $named, 'copies' ) );
bhp_t( 'the named counter carries the number', false !== strpos( $named, '4' ) );
bhp_t( 'the named counter has no em dash', false === strpos( $named, "\xE2\x80\x94" ) );
bhp_t( 'the named counter has no exclamation mark', false === strpos( $named, '!' ) );

/* =========================================================================
 * §5 — ⭐⭐ THE REAL SEAMS, ON A REAL FLAGGED SESSION
 * ====================================================================== */
bhp_h( '§5 — server-side refusal through the real hooks' );

/*
 * ⭐⭐ THE SUITE ADAPTS TO THE ENVIRONMENT'S REAL BASELINE INSTEAD OF ASSUMING
 *     ONE, AND THIS IS A CORRECTION, NOT A CONVENIENCE.
 *
 * ⚠️ AN EARLIER DRAFT HARDCODED `everest` AS THE TITLE IT FORCES CLOSED, AND
 *    ASSUMED §7's "healthy shelf" meant NOTHING was closed. Both broke the
 *    moment the staging baseline was seeded with a genuinely low everest count
 *    during QA: §5 could not add the book it needed for the stale-cart seam
 *    (the REAL baseline refused it, not the filter), and §7 reported two
 *    failures for behaviour that was completely correct.
 *
 * ⛔ A SUITE THAT ONLY PASSES ON AN ENVIRONMENT WITH A PARTICULAR OPTION VALUE
 *    IS A SUITE THAT WILL BE WRONG THE FIRST TIME ANDREW RESTOCKS. So the
 *    victim title is CHOSEN from whatever is genuinely open here, and the
 *    "healthy shelf" section SKIPS, loudly, when the environment itself closes
 *    something.
 */
$env_closed = bhp_visit_shelf_closed_titles();
$env_open   = array_values( array_diff( $slugs, $env_closed ) );

echo "\n  environment's own closed titles: " . ( empty( $env_closed ) ? '(none)' : implode( ',', $env_closed ) ) . "\n";
echo '  environment\'s own open titles:   ' . ( empty( $env_open ) ? '(none)' : implode( ',', $env_open ) ) . "\n";

$records = bhp_school_visit_records();
$live    = null;
foreach ( array_keys( $records ) as $slug ) {
	if ( null !== bhp_school_visit_resolve( $slug ) ) {
		$live = $slug;
		break;
	}
}

if ( null === $live || ! function_exists( 'WC' ) || ! WC()->session || count( $env_open ) < 2 ) {
	$why = null === $live
		? 'no LIVE non-expired visit in the registry'
		: ( count( $env_open ) < 2
			? 'fewer than two titles are genuinely open on this environment, so there is no victim + control pair'
			: 'no WooCommerce session in CLI' );
	bhp_skip( '§5 seam assertions', $why );
} else {
	echo "  using live visit: $live\n";
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $live );
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, time() );

	bhp_t( 'flagged session: bhp_school_visit_paperback_only() is true', bhp_school_visit_paperback_only() );

	/*
	 * Close exactly ONE genuinely-open title. The others must stay fully
	 * available: that asymmetry is what proves the gate is per-title and not
	 * a blanket switch.
	 */
	$victim  = $env_open[0];
	$control = $env_open[1];
	echo "  forcing CLOSED: $victim   |   control (must stay open): $control\n";

	$close_victim = function ( $closed, $slug ) use ( $victim ) {
		return $victim === $slug ? true : $closed; };
	add_filter( 'bhp_visit_shelf_title_is_closed', $close_victim, 99, 2 );

	$eve = $catalog['paperback'][ $victim ];
	$mar = $catalog['paperback'][ $control ];

	$expected_map = array_fill_keys( array_unique( array_merge( $env_closed, array( $victim ) ) ), true );
	ksort( $expected_map );
	$actual_map = bhp_visit_shelf_closed_map_for_request();
	ksort( $actual_map );
	bhp_t( "closed map now names exactly the environment's closed set plus $victim", $expected_map === $actual_map, wp_json_encode( $actual_map ) );
	bhp_t(
		'open title count dropped by exactly one',
		( count( $slugs ) - count( $expected_map ) ) === bhp_visit_shelf_open_title_count( 'paperback' ),
		(string) bhp_visit_shelf_open_title_count( 'paperback' )
	);

	// -- the product-level predicates
	bhp_t( "$victim paperback IS a sold-out title", bhp_school_visit_is_sold_out_title( (int) $eve['product_id'], 0 ) );
	bhp_t( "$victim paperback IS a refused item", bhp_school_visit_is_refused_item( (int) $eve['product_id'], 0 ) );
	bhp_t( "$control paperback is NOT refused (per-title, not a blanket switch)", ! bhp_school_visit_is_refused_item( (int) $mar['product_id'], (int) $mar['variation_id'] ) );

	// -- seam 1: the classic add filter
	bhp_t(
		"SEAM 1 refuses a closed $victim add",
		false === bhp_school_visit_block_hardcover_add( true, (int) $eve['product_id'], 1, 0 )
	);
	bhp_t(
		"SEAM 1 still passes an open $control add",
		true === bhp_school_visit_block_hardcover_add( true, (int) $mar['product_id'], 1, (int) $mar['variation_id'] )
	);

	// -- seam 5: WC_Cart::add_to_cart()'s own hook
	$threw = false;
	try {
		bhp_school_visit_block_hardcover_cart_add( array(), (int) $eve['product_id'], 0 );
	} catch ( Exception $e ) {
		$threw = ( bhp_visit_shelf_sold_out_message() === $e->getMessage() );
	}
	bhp_t( 'SEAM 5 throws the SOLD-OUT sentence for a closed title', $threw );

	$threw_open = false;
	try {
		bhp_school_visit_block_hardcover_cart_add( array(), (int) $mar['product_id'], (int) $mar['variation_id'] );
	} catch ( Exception $e ) {
		$threw_open = true;
	}
	bhp_t( 'SEAM 5 does NOT throw for an open title', ! $threw_open );

	// -- the message picker
	bhp_t(
		'refusal message for a closed chapter title is the SOLD-OUT sentence',
		bhp_visit_shelf_sold_out_message() === bhp_school_visit_refusal_message( (int) $eve['product_id'], 0 )
	);
	$hc = $catalog['hardcover']['mariana'];
	bhp_t(
		'refusal message for a HARDCOVER is still the paperback-only sentence',
		bhp_school_visit_paperback_only_message() === bhp_school_visit_refusal_message( (int) $hc['product_id'], 0 )
	);

	// -- seam 3/4 via a REAL cart holding a REAL closed title
	if ( WC()->cart ) {
		WC()->cart->empty_cart();
		// Add it while the gate is lifted, to simulate the stale-cart path: a
		// parent who added it in an ordinary session and only then clicked the
		// school link. This is the seam that makes the gate a fix, not a UI hint.
		remove_filter( 'bhp_visit_shelf_title_is_closed', $close_victim, 99 );
		/*
		 * ⚠️ PASS THE VARIATION ID WHEN THERE IS ONE. The Mariana paperback is
		 *    the catalogue's one VARIABLE product (333, variation 334), and
		 *    `add_to_cart( 333, 1 )` with no variation silently returns false.
		 *    An earlier draft always added the parent id, so whenever the
		 *    chosen victim happened to be Mariana this whole seam SKIPPED and
		 *    the stale-cart path went untested without anything looking wrong.
		 */
		$added = (int) $eve['variation_id']
			? WC()->cart->add_to_cart( (int) $eve['product_id'], 1, (int) $eve['variation_id'] )
			: WC()->cart->add_to_cart( (int) $eve['product_id'], 1 );
		add_filter( 'bhp_visit_shelf_title_is_closed', $close_victim, 99, 2 );

		if ( $added ) {
			bhp_t( 'stale cart holding a now-closed title is detected', bhp_school_visit_cart_has_refused_item( WC()->cart ) );
			bhp_t(
				'the cart-level sentence is the SOLD-OUT one, not the format one',
				bhp_visit_shelf_sold_out_message() === bhp_school_visit_cart_refusal_message( WC()->cart )
			);

			$errors = new WP_Error();
			bhp_school_visit_hardcover_store_api_cart_error( $errors, WC()->cart );
			bhp_t( 'SEAM 3 (Store API cart errors) blocks checkout on a stale closed cart', $errors->has_errors() );
			bhp_t(
				'SEAM 3 carries the sold-out sentence',
				bhp_visit_shelf_sold_out_message() === $errors->get_error_message( 'bhp_school_visit_paperback_only' )
			);
			WC()->cart->empty_cart();
		} else {
			bhp_skip( 'SEAM 3 stale-cart assertions', "could not add the $victim paperback to the CLI cart" );
		}
	} else {
		bhp_skip( 'SEAM 3 stale-cart assertions', 'no WooCommerce cart in CLI' );
	}

	/* =====================================================================
	 * §6 — THE BOX CLOSES WHEN **ANY ONE** TITLE CLOSES
	 * ================================================================== */
	bhp_h( '§6 — the 3-book box closes when any one of its titles is closed' );

	if ( WC()->cart ) {
		WC()->cart->empty_cart();
		wc_clear_notices();
		// The complete collection: all three titles, one of which is closed.
		bhp_bundle_add_titles_to_cart( 'paperback', array_keys( $catalog['paperback'] ) );

		bhp_t(
			'the complete-collection add put NOTHING in the cart',
			0 === WC()->cart->get_cart_contents_count(),
			'count=' . WC()->cart->get_cart_contents_count()
		);

		$notices  = wc_get_notices( 'error' );
		$messages = array();
		foreach ( $notices as $n ) {
			$messages[] = is_array( $n ) && isset( $n['notice'] ) ? $n['notice'] : (string) $n;
		}
		bhp_t(
			'the box refusal printed the SOLD-OUT sentence',
			in_array( bhp_visit_shelf_sold_out_message(), $messages, true ),
			implode( ' | ', $messages )
		);
		wc_clear_notices();
		WC()->cart->empty_cart();

		/*
		 * A two-title box made of two OPEN titles must still work.
		 *
		 * ⚠️ ASSERT THE TWO CHAPTER TITLES ARE PRESENT, NOT A RAW CART COUNT.
		 *    ⭐ MEASURED ON STAGING, 2026-08-24: this cart comes back with
		 *    THREE line items, because `addon-upsell.php` / the free-with-
		 *    collection logic adds The Adventure Activity Book alongside them.
		 *    That is PRE-EXISTING 1.8.70 behaviour, entirely unrelated to this
		 *    build, and it is also why all 13 live visit orders on production
		 *    carry the activity book. An earlier draft asserted `count === 2`
		 *    and failed on it; the assertion was wrong, not the code.
		 */
		/*
		 * ⛔ THE PAIR IS BUILT ONLY FROM TITLES THAT ARE GENUINELY OPEN AFTER
		 *    THE VICTIM IS CLOSED. If fewer than two remain, there IS no valid
		 *    two-open-title box on this environment and the assertion SKIPS.
		 *
		 * ⚠️ AN EARLIER DRAFT "FELL BACK" to any two non-victim titles when the
		 *    open set was too small, and on a staging baseline where everest
		 *    was genuinely down to its last copy it therefore asked for a box
		 *    containing everest and then FAILED because the box was correctly
		 *    refused. The test was demanding that the feature not work.
		 */
		$open_pair = array_values( array_diff( $env_open, array( $victim ) ) );

		if ( count( $open_pair ) < 2 ) {
			bhp_skip(
				'two-OPEN-title box assertion',
				'only [' . implode( ',', $open_pair ) . '] remains open once ' . $victim . ' is forced closed, so no valid two-open-title box exists here'
			);
		} else {
			$open_pair = array_slice( $open_pair, 0, 2 );
			bhp_bundle_add_titles_to_cart( 'paperback', $open_pair );
			$in_cart = array();
			foreach ( WC()->cart->get_cart() as $ci ) {
				$s = bhp_visit_shelf_identify_title( (int) $ci['product_id'], (int) $ci['variation_id'] );
				if ( null !== $s ) {
					$in_cart[ $s ] = true;
				}
			}
			ksort( $in_cart );
			$expected_pair = array_fill_keys( $open_pair, true );
			ksort( $expected_pair );
			bhp_t(
				'a box of two OPEN titles adds exactly those two chapter titles',
				$expected_pair === $in_cart,
				'asked for ' . implode( ',', $open_pair ) . '; got ' . implode( ',', array_keys( $in_cart ) ) . '; raw line count=' . WC()->cart->get_cart_contents_count()
			);
			bhp_t(
				"the closed title ($victim) did NOT sneak into the two-title box",
				! isset( $in_cart[ $victim ] )
			);
		}
		wc_clear_notices();
		WC()->cart->empty_cart();
	} else {
		bhp_skip( '§6 box assertions', 'no WooCommerce cart in CLI' );
	}

	/*
	 * The offer panel must not offer what the gate refuses. Pick an offer that
	 * genuinely contains the victim title rather than naming one by hand.
	 */
	if ( function_exists( 'bhp_offer_is_offerable' ) && function_exists( 'bhp_offer_catalog' ) ) {
		$offer_with_victim = null;
		foreach ( bhp_offer_catalog() as $key => $offer ) {
			if ( ! empty( $offer['chapter'] ) && in_array( $victim, (array) $offer['chapter'], true ) ) {
				$offer_with_victim = $key;
				break;
			}
		}
		if ( null === $offer_with_victim ) {
			bhp_skip( 'offer panel assertion', "no offer in the catalogue contains $victim" );
		} else {
			bhp_t(
				"offer '$offer_with_victim' (contains $victim) is NOT offerable",
				! bhp_offer_is_offerable( $offer_with_victim )
			);
		}
	} else {
		bhp_skip( 'offer panel assertion', 'offer engine not loaded' );
	}

	remove_filter( 'bhp_visit_shelf_title_is_closed', $close_victim, 99 );

	/* =====================================================================
	 * §7 — CONTROL PATH AGAIN, WITH THE FLAG STILL SET AND NOTHING FORCED
	 * ================================================================== */
	bhp_h( '§7 — a flagged session with a healthy shelf is unchanged from 1.8.70' );

	/*
	 * ⛔ THIS SECTION IS ONLY MEANINGFUL WHEN THE ENVIRONMENT ITSELF CLOSES
	 *    NOTHING. If Andrew's real baseline has a title down to its last copy,
	 *    a "nothing is refused" assertion here would be asserting that the
	 *    feature does NOT work. SKIP LOUDLY rather than report a false failure
	 *    — or, worse, be "fixed" later by loosening the assertion.
	 */
	if ( ! empty( $env_closed ) ) {
		bhp_skip(
			'§7 healthy-shelf assertions',
			'this environment genuinely closes [' . implode( ',', $env_closed ) . '] from its own baseline, so there is no healthy-shelf state to assert'
		);
	} else {
		foreach ( $catalog['paperback'] as $slug => $ed ) {
			bhp_t(
				"flagged + healthy shelf: {$slug} paperback is NOT refused",
				! bhp_school_visit_is_refused_item( (int) $ed['product_id'], (int) $ed['variation_id'] )
			);
		}
		bhp_t( 'flagged + healthy shelf: closed map is empty', array() === bhp_visit_shelf_closed_map_for_request(), wp_json_encode( bhp_visit_shelf_closed_map_for_request() ) );
	}

	// This one holds regardless of the shelf: the 1.8.65 format gate is untouched.
	bhp_t(
		'flagged: a hardcover is STILL refused (1.8.65 unchanged, shelf-independent)',
		bhp_school_visit_is_refused_item( (int) $catalog['hardcover']['everest']['product_id'], 0 )
	);

	/* ==================================================================
	 * §7a — ⭐⭐ 1.8.72: THE COUNTER ACTUALLY REACHES THE RENDERED HTML
	 *
	 * ⛔ THE FUNCTION RETURNING 6 IS NOT THE FEATURE. The feature is the
	 *    number appearing in the markup a parent's browser receives, in the
	 *    row for the right title, on all the surfaces that carry it. A unit
	 *    assertion on the predicate would have passed just as happily against
	 *    a template that never called it.
	 * ================================================================== */
	bhp_h( '§7a — flagged: the number reaches the RENDERED markup of every surface' );

	$counted        = $env_open[0];
	$counted_label  = $catalog['paperback'][ $counted ]['label'];
	$forced_display = 6;

	$force_counter = function ( $out, $slug ) use ( $counted, $forced_display ) {
		return $slug === $counted ? $forced_display : $out; };
	add_filter( 'bhp_visit_shelf_title_counter', $force_counter, 99, 4 );

	$expected_sentence = bhp_visit_shelf_counter_label( $forced_display );
	echo "  forcing $counted to display $forced_display; expecting: \"$expected_sentence\"\n";

	bhp_t(
		'flagged: the counter map names the forced title',
		isset( bhp_visit_shelf_counter_map_for_request()[ $counted ] )
			&& $forced_display === bhp_visit_shelf_counter_map_for_request()[ $counted ],
		wp_json_encode( bhp_visit_shelf_counter_map_for_request() )
	);

	foreach ( array(
		'[bhp_shop_the_series] (the /shop-the-series/ surface)' => 'bhp_bundle_render_shop_series',
		'[bhp_bundle_offers]   (the /book-bundles/ surface)'    => 'bhp_bundle_render_offers',
	) as $surface => $renderer ) {
		if ( ! function_exists( $renderer ) ) {
			bhp_skip( "$surface renders the counter", "$renderer not loaded" );
			continue;
		}
		// See §7b: both renderers open with wc_print_notices(), which clears.
		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}
		$html = (string) call_user_func( $renderer );

		bhp_t( "$surface: the counter sentence is in the HTML", false !== strpos( $html, $expected_sentence ) );
		bhp_t( "$surface: it carries the counter class", false !== strpos( $html, 'bhp-bundle-stock-counter' ) );
		bhp_t(
			"$surface: the counted title's own row carries it, not another",
			preg_match( '/' . preg_quote( $counted_label, '/' ) . '.{0,400}?bhp-bundle-stock-counter/s', $html ) === 1,
			$counted_label
		);
		bhp_t(
			"$surface: the number is not repeated on a title that is not low",
			substr_count( $html, 'bhp-bundle-stock-counter' ) === substr_count( $html, $expected_sentence ),
			'class=' . substr_count( $html, 'bhp-bundle-stock-counter' ) . ' sentence=' . substr_count( $html, $expected_sentence )
		);
		bhp_t(
			"$surface: the three-book box carries NO counter by default (the recommendation)",
			false === strpos( $html, 'bhp-bundle-stock-counter--box' )
		);
	}

	/*
	 * ⭐ AND THE FLIP WORKS. The recommendation is "nothing on the box", but a
	 *    recommendation Andrew cannot act on in one line is not a
	 *    recommendation, so the true branch is exercised here rather than left
	 *    as untested code that would fail the day he flips it.
	 */
	add_filter( 'bhp_visit_shelf_counter_on_complete_box', '__return_true', 99 );
	if ( function_exists( 'bhp_bundle_render_shop_series' ) ) {
		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}
		$html_flipped = (string) bhp_bundle_render_shop_series();
		bhp_t( 'the box-counter FLIP renders the box counter', false !== strpos( $html_flipped, 'bhp-bundle-stock-counter--box' ) );
		bhp_t(
			'the box counter NAMES the constraining title (an unnamed number on a set card is the ambiguity)',
			false !== strpos( $html_flipped, bhp_visit_shelf_counter_label_named( $counted, $forced_display ) ),
			bhp_visit_shelf_counter_label_named( $counted, $forced_display )
		);
	}
	remove_filter( 'bhp_visit_shelf_counter_on_complete_box', '__return_true', 99 );
	bhp_t( 'the flip is removable and the default returns to OFF', false === bhp_visit_shelf_counter_on_complete_box() );

	remove_filter( 'bhp_visit_shelf_title_counter', $force_counter, 99 );
	bhp_t( 'with the force removed the counter map returns to the environment truth', is_array( bhp_visit_shelf_counter_map_for_request() ) );
}

/* =========================================================================
 * §7b — ⛔⛔⛔ THE ABSOLUTE: A NORMAL SESSION SHOWS ZERO STOCK MARKUP,
 *              AT EVERY BASELINE STATE INCLUDING THE LOW ONES.
 *
 * ⛔ THIS IS THE SECTION THAT COSTS THE MOST IF IT BREAKS, so it runs OUTSIDE
 *    the flagged block, unconditionally, on every environment, and it runs
 *    AFTER §9 would otherwise have cleared the flag -- it clears the flag
 *    itself first rather than assuming an earlier section left it clear.
 *
 * ⛔ THE FOUNDER'S ABSOLUTE, CARRIED: "no counter, no stock hint, on any
 *    non-visit surface - /read-aloud/, /shop/ normal mode, product pages,
 *    anywhere POD-shipped."
 *
 * ⭐⭐ AND IT SWEEPS THE BASELINE, WHICH IS THE POINT. A negative test run only
 *     against a healthy shelf proves nothing: the shelf on this environment is
 *     at 21/17/21, so EVERY title is silently above the ceiling and a template
 *     that leaked the counter unconditionally would still pass. So the shelf is
 *     forced to 0, 1, 2, 6, 10, 11 and 40 in turn, and the assertion is that an
 *     unflagged shopper's HTML is byte-identical across all of them.
 *
 * ⛔ BYTE-IDENTICAL IS THE ASSERTION, NOT "CONTAINS NO COUNTER". A hidden span,
 *    an empty element, a `data-` attribute or a stray class would all pass a
 *    substring check and all leak Andrew's private shelf position into a
 *    stranger's page source. Comparing the whole rendered document to the
 *    healthy-shelf render catches every one of them at once.
 * ====================================================================== */
bhp_h( '§7b — ABSOLUTE: an ordinary shopper sees no stock markup at ANY shelf level' );

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
	WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
}
if ( function_exists( 'wc_clear_notices' ) ) {
	wc_clear_notices();
}

bhp_t(
	'§7b precondition: this request is NOT visit-flagged',
	! function_exists( 'bhp_school_visit_paperback_only' ) || ! bhp_school_visit_paperback_only()
);

// The forbidden fingerprints, in the order they would most likely leak.
$forbidden = array(
	'bhp-bundle-stock-counter'      => 'the counter class',
	'bhp-bundle-sold-out-label'     => 'the sold-out badge class',
	'bhp-bundle-card--sold-out'     => 'the sold-out card class',
	'bhp-bundle-title--sold-out'    => 'the sold-out row class',
	'left for the school visit'     => 'the counter sentence',
	'Sold out for the school visit' => 'the sold-out sentence',
	'disabled="disabled"'           => 'a disabled purchase control',
);

$sweep = array( 40, 11, 10, 6, 2, 1, 0 );

foreach ( array(
	'[bhp_shop_the_series]' => 'bhp_bundle_render_shop_series',
	'[bhp_bundle_offers]'   => 'bhp_bundle_render_offers',
) as $surface => $renderer ) {

	if ( ! function_exists( $renderer ) ) {
		bhp_skip( "$surface negative sweep", "$renderer not loaded" );
		continue;
	}

	$baseline_html = null;

	foreach ( $sweep as $level ) {
		/*
		 * Force EVERY title to this remaining level, through both filters, so
		 * the shelf is uniformly at $level for the duration of one render.
		 */
		$force_all_to = function ( $out, $slug, $live, $ceiling ) use ( $level ) {
			return ( $level > (int) BHP_VISIT_SHELF_BUFFER && $level <= (int) $ceiling ) ? $level : null; };
		$close_all_at = function ( $closed ) use ( $level ) {
			return $level <= (int) BHP_VISIT_SHELF_BUFFER; };

		add_filter( 'bhp_visit_shelf_title_counter', $force_all_to, 99, 4 );
		add_filter( 'bhp_visit_shelf_title_is_closed', $close_all_at, 99, 1 );

		/*
		 * ⚠️ CLEAR NOTICES BEFORE EVERY RENDER, or this whole section lies.
		 *    Both renderers open with `wc_print_notices()`, which PRINTS AND
		 *    THEN CLEARS. The first render would emit a notice the second one
		 *    could not, the byte-identity assertion would fail, and it would
		 *    fail for a reason that has nothing to do with stock.
		 */
		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}

		$html = (string) call_user_func( $renderer );

		remove_filter( 'bhp_visit_shelf_title_counter', $force_all_to, 99 );
		remove_filter( 'bhp_visit_shelf_title_is_closed', $close_all_at, 99 );

		foreach ( $forbidden as $needle => $what ) {
			bhp_t(
				sprintf( '%s @ shelf=%-2d : NO %s', $surface, $level, $what ),
				false === strpos( $html, $needle )
			);
		}

		if ( null === $baseline_html ) {
			$baseline_html = $html;
			echo sprintf( "  %s: captured the shelf=%d render as the comparison baseline (%d bytes)\n", $surface, $level, strlen( $html ) );
		} else {
			bhp_t(
				sprintf( '%s @ shelf=%-2d : rendered HTML is BYTE-IDENTICAL to the healthy-shelf render', $surface, $level ),
				$baseline_html === $html,
				$baseline_html === $html ? '' : 'differs by ' . abs( strlen( $baseline_html ) - strlen( $html ) ) . ' bytes'
			);
		}
	}
}

/*
 * ⛔ AND THE PREDICATES THEMSELVES, not only the templates. A surface written
 *    tomorrow will call these; they must be empty off a visit flag no matter
 *    how low the shelf is.
 */
$force_low = function () {
	return 3; };
add_filter( 'bhp_visit_shelf_title_counter', $force_low, 99, 4 );
bhp_t( 'unflagged: bhp_visit_shelf_counter_map_for_request() is EMPTY even at remaining=3', array() === bhp_visit_shelf_counter_map_for_request(), wp_json_encode( bhp_visit_shelf_counter_map_for_request() ) );
foreach ( $slugs as $slug ) {
	bhp_t( "unflagged: bhp_visit_shelf_counter_for_request('$slug') is null even at remaining=3", null === bhp_visit_shelf_counter_for_request( $slug ) );
}
bhp_t( 'unflagged: constraining title for the paperback box is null', null === bhp_visit_shelf_constraining_title_for_request( 'paperback' ) );
remove_filter( 'bhp_visit_shelf_title_counter', $force_low, 99 );

/*
 * ⭐ THE STRUCTURAL GUARD: the counter markup has exactly ONE owner in PHP.
 *
 * ⛔ This is what stops a fourth surface from growing its own
 *    `<span class="bhp-bundle-stock-counter">` that forgets the visit gate.
 *    The class may appear in the CSS and in this suite; in PHP it may appear
 *    only in `school-visit-shelf-stock.php` (the renderer) and in
 *    `bundle-shortcode.php` (the box variant, which is a different element).
 */
$plugin_dir = defined( 'BHP_BUNDLE_PRICING_DIR' ) ? BHP_BUNDLE_PRICING_DIR : dirname( __DIR__ ) . '/';
$owners     = array();
foreach ( glob( $plugin_dir . 'includes/*.php' ) as $php ) {
	$src = (string) file_get_contents( $php );
	if ( false !== strpos( $src, 'class="bhp-bundle-stock-counter' ) ) {
		$owners[] = basename( $php );
	}
}
sort( $owners );
bhp_t(
	'the counter element is emitted from exactly two known places, both audited',
	array( 'bundle-shortcode.php', 'school-visit-shelf-stock.php' ) === $owners,
	implode( ',', $owners )
);

/*
 * ⭐ 1.8.76 — THE SAME STRUCTURAL GUARD FOR THE NEW BACKORDER ELEMENT, and it
 *    is TIGHTER than the counter's: exactly ONE owner, not two. There is no box
 *    variant and there must never be a second copy.
 */
$bo_owners = array();
foreach ( glob( $plugin_dir . 'includes/*.php' ) as $php ) {
	$src = (string) file_get_contents( $php );
	if ( false !== strpos( $src, 'class="bhp-bundle-backorder-label' ) ) {
		$bo_owners[] = basename( $php );
	}
}
sort( $bo_owners );
bhp_t(
	'1.8.76: the backorder element is emitted from exactly ONE place',
	array( 'school-visit-backorder.php' ) === $bo_owners,
	implode( ',', $bo_owners )
);

/* =========================================================================
 * ⭐⭐⭐ §7c — 1.8.76: THE BACKORDER ALLOWANCE, ON, END TO END.
 *     (`CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`, founder item 363)
 * =========================================================================
 *
 * ⭐ THE SUITE-WIDE "OFF" FILTER IS LIFTED HERE and everything below runs
 *   against the SHIPPED DEFAULT.
 *
 * ⛔ IT FORCES THE SHELF THROUGH THE `bhp_visit_shelf_title_is_closed` FILTER,
 *    NEVER BY WRITING `bhp_visit_shelf_stock`. Same discipline as §4a and §5:
 *    this suite runs on live environments and writes no option. §8 proves it.
 *
 * ⭐⭐ THE FOUR THINGS THAT MUST ALL BE TRUE AT ONCE, and the reason this
 *    section exists rather than a single "is it relaxed" assertion:
 *      1. the SHELF still reports exhausted (the fact did not change);
 *      2. the PURCHASE GATE reports open (the policy did);
 *      3. NO NUMBER is displayed anywhere (the honesty rule is untouched);
 *      4. the words that DO appear promise no restock and no date.
 * ====================================================================== */
bhp_h( '§7c — 1.8.76 DEFAULT: an exhausted title is orderable, and still shows no number' );

remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );
bhp_t( 'the suite-wide OFF filter is lifted; the shipped default is back', true === bhp_visit_shelf_backorder_allowed() );

if ( ! function_exists( 'bhp_visit_shelf_backorder_allowed' ) ) {
	bhp_skip( '§7c', 'backorder module not loaded' );
} else {
	$bo_slug = $slugs[0];

	// Force THIS ONE TITLE exhausted, by filter, without touching a count.
	$bo_force = function ( $closed, $slug ) use ( $bo_slug ) {
		return ( $slug === $bo_slug ) ? true : $closed;
	};
	add_filter( 'bhp_visit_shelf_title_is_closed', $bo_force, 99, 2 );

	// ── 1. THE SHELF FACT IS UNCHANGED ────────────────────────────────────
	bhp_t(
		"the SHELF still reports {$bo_slug} exhausted (the physical fact did not move)",
		true === bhp_visit_shelf_title_is_exhausted( $bo_slug )
	);

	// ── 2. THE PURCHASE GATE RELAXES ──────────────────────────────────────
	bhp_t(
		"the PURCHASE GATE reports {$bo_slug} OPEN, because backorders are allowed (item 363)",
		false === bhp_visit_shelf_title_is_closed( $bo_slug )
	);
	bhp_t(
		'the closed-titles list no longer contains it, so no surface and no seam refuses it',
		! in_array( $bo_slug, bhp_visit_shelf_closed_titles(), true ),
		implode( ',', bhp_visit_shelf_closed_titles() )
	);

	/*
	 * ⛔ THE SEAM PREDICATE ITSELF, not just the slug list. This is the exact
	 *    function all five server refusals in `school-visit-paperback-only.php`
	 *    call, asked about a real product id resolved from the catalog.
	 */
	$bo_pid = (int) ( $catalog['paperback'][ $bo_slug ]['variation_id'] ?? 0 );
	if ( ! $bo_pid ) {
		$bo_pid = (int) $catalog['paperback'][ $bo_slug ]['product_id'];
	}
	bhp_t(
		'the REFUSAL SEAM predicate accepts the exhausted item (bhp_visit_shelf_is_closed_item)',
		false === bhp_visit_shelf_is_closed_item( $bo_pid, 0 ),
		"product/variation id {$bo_pid}"
	);
	if ( function_exists( 'bhp_school_visit_is_sold_out_title' ) ) {
		bhp_t(
			'and the paperback-only file agrees, through its own delegating predicate',
			false === bhp_school_visit_is_sold_out_title( $bo_pid, 0 )
		);
	}

	// ── 3. THE HONESTY RULE: STILL NO NUMBER, ANYWHERE ────────────────────
	bhp_t(
		'the COUNTER is still silent for an exhausted title (exhausted outranks counted)',
		null === bhp_visit_shelf_title_counter( $bo_slug )
	);
	bhp_t(
		'and silent through the request gate too',
		null === bhp_visit_shelf_counter_for_request( $bo_slug )
	);
	bhp_t(
		'the counter map does not carry the exhausted title',
		! array_key_exists( $bo_slug, bhp_visit_shelf_counter_map_for_request() )
	);

	/*
	 * ⛔⛔ THE REGRESSION THIS SPLIT EXISTS TO PREVENT, ASSERTED DIRECTLY.
	 *     A title Andrew closes BY HAND at a healthy count must STILL print no
	 *     number, even though backorders are on and the purchase gate is open.
	 *     If `bhp_visit_shelf_title_counter()` ever asks the relaxed gate again,
	 *     this is the assertion that catches it.
	 */
	if ( count( $slugs ) > 1 ) {
		$hand_slug  = $slugs[1];
		$hand_close = function ( $closed, $slug ) use ( $hand_slug ) {
			return ( $slug === $hand_slug ) ? true : $closed;
		};
		add_filter( 'bhp_visit_shelf_title_is_closed', $hand_close, 100, 2 );
		bhp_t(
			"a HAND-CLOSED title ({$hand_slug}) prints no number even with backorders on",
			null === bhp_visit_shelf_title_counter( $hand_slug )
		);
		bhp_t(
			'and it is still orderable, because a hand-close is a shelf fact and not a refusal now',
			false === bhp_visit_shelf_title_is_closed( $hand_slug )
		);
		remove_filter( 'bhp_visit_shelf_title_is_closed', $hand_close, 100 );
	}

	// ── 4. THE WORDS ──────────────────────────────────────────────────────
	$bo_label = bhp_visit_shelf_backorder_label();
	$bo_msg   = bhp_visit_shelf_backorder_message();

	bhp_t( 'the backorder label is non-empty', '' !== trim( $bo_label ), $bo_label );
	bhp_t( 'the backorder message is non-empty', '' !== trim( $bo_msg ) );

	foreach ( array( 'label' => $bo_label, 'message' => $bo_msg ) as $what => $str ) {
		$low = strtolower( $str );

		// ⛔ §9.1 voice. Word-boundary matched so "between"/"however" do not trip it.
		bhp_t( "backorder {$what}: no 'we'/'us'/'our' standing for the company", 0 === preg_match( '/\b(we|us|our|we\'re|we\'ll|we\'ve)\b/i', $str ), $str );
		bhp_t( "backorder {$what}: no em dash", false === strpos( $str, "\xE2\x80\x94" ) );
		bhp_t( "backorder {$what}: no exclamation mark (no urgency theater)", false === strpos( $str, '!' ) );

		// ⛔ NO RESTOCK DATE AND NO RESTOCK PROMISE. The standing constraint on
		//    every customer-facing string in this subsystem. "arriving before
		//    the visit" is a restock promise with the number filed off, and it
		//    is FALSE for Dallas Harris (09-03) and Liberty (09-04), both of
		//    which fall BEFORE the Sept 7-11 restock.
		bhp_t( "backorder {$what}: names no month", 0 === preg_match( '/\b(january|february|march|april|may|june|july|august|september|october|november|december|sept|sep\.)\b/i', $str ), $str );
		bhp_t( "backorder {$what}: carries no digit (no date, no count)", 0 === preg_match( '/\d/', $str ), $str );
		bhp_t( "backorder {$what}: makes no 'before the visit' arrival promise", false === strpos( $low, 'before the visit' ), $str );
		bhp_t( "backorder {$what}: does not promise a restock", 0 === preg_match( '/\b(restock|arriving|on its way|in transit|shipping soon)\b/i', $str ), $str );

		// ⛔ AMERICAN SPELLING, per the founder's standing rule of 2026-08-24.
		bhp_t( "backorder {$what}: American spelling", 0 === preg_match( '/\b(colour|favourite|apologise|organise|centre|despatch)\w*/i', $str ), $str );
	}

	// ⭐ It must not read as sold out, and it must not read as in stock.
	bhp_t( 'the backorder label does not say "sold out"', false === strpos( strtolower( $bo_label ), 'sold out' ) );
	bhp_t( 'the backorder label is not the sold-out label', $bo_label !== bhp_visit_shelf_sold_out_label() );
	bhp_t(
		'the message carries the founder\'s own accepted worst case (delivery a few days after)',
		false !== stripos( $bo_msg, 'few days' ),
		$bo_msg
	);

	// ── THE RENDERER: exactly one element, and only on a flagged session ──
	$bo_flagged = function_exists( 'bhp_school_visit_paperback_only' ) && bhp_school_visit_paperback_only();

	ob_start();
	bhp_visit_shelf_render_backorder_line( $bo_slug );
	$bo_html = ob_get_clean();

	if ( $bo_flagged ) {
		bhp_t( 'FLAGGED: the backorder line renders', false !== strpos( $bo_html, 'bhp-bundle-backorder-label' ), $bo_html );
		bhp_t( 'FLAGGED: it carries no digit', 0 === preg_match( '/\d/', wp_strip_all_tags( $bo_html ) ), $bo_html );
		bhp_t( 'FLAGGED: the full sentence rides along for screen readers', false !== strpos( $bo_html, 'screen-reader-text' ) );

		// ⭐ AND THROUGH THE SHARED RENDERER THE THREE SURFACES ACTUALLY CALL.
		ob_start();
		bhp_visit_shelf_render_counter( $bo_slug );
		$bo_via_counter = ob_get_clean();
		bhp_t(
			'the shared render_counter() falls through to the backorder line, so all surfaces get it',
			false !== strpos( $bo_via_counter, 'bhp-bundle-backorder-label' ),
			$bo_via_counter
		);
		bhp_t(
			'and it never prints the counter and the backorder line together',
			false === strpos( $bo_via_counter, 'bhp-bundle-stock-counter' )
		);
	} else {
		bhp_t( 'UNFLAGGED: the backorder line renders NOTHING AT ALL, not even an empty span', '' === $bo_html, var_export( $bo_html, true ) );
	}

	// ⛔ THE ABSOLUTE, RE-ASSERTED FOR THE NEW MARKUP: an unflagged session must
	//    never receive it, whatever the shelf says.
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
		WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
	}
	bhp_t(
		'ABSOLUTE: with the visit flag cleared, the exhausted title is NOT backordered-for-request',
		false === bhp_visit_shelf_title_is_backordered_for_request( $bo_slug )
	);
	ob_start();
	bhp_visit_shelf_render_backorder_line( $bo_slug );
	bhp_t( 'ABSOLUTE: and renders zero bytes for an ordinary shopper', '' === ob_get_clean() );

	remove_filter( 'bhp_visit_shelf_title_is_closed', $bo_force, 99 );
	bhp_t( 'the force is removed and the environment truth returns', is_array( bhp_visit_shelf_closed_titles() ) );
}

/* =========================================================================
 * §8 — ⛔⛔ NOTHING WAS WRITTEN. NOT AN OPTION, NOT A STOCK STATUS.
 * ====================================================================== */
bhp_h( '§8 — no option and no product record was written by this build or this suite' );

global $wpdb;

$opt_rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN (%s,%s,%s,%s,%s)",
		'bhp_visit_shelf_stock',
		'bhp_school_visits',
		'bhp_visit_shelf_backorders',
		'woocommerce_pickup_location_settings',
		'pickup_location_pickup_locations'
	),
	ARRAY_A
);
$before = wp_json_encode( $opt_rows );

$stock_before = array();
foreach ( array_merge( array_values( $catalog['paperback'] ), array_values( $catalog['hardcover'] ) ) as $ed ) {
	$p = wc_get_product( (int) $ed['product_id'] );
	if ( $p ) {
		$stock_before[ (int) $ed['product_id'] ] = $p->get_stock_status();
	}
}

// Exercise the whole gate once more, which is the thing that must not write.
bhp_visit_shelf_closed_titles();
bhp_visit_shelf_remaining();
bhp_visit_shelf_closed_map_for_request();

$opt_rows_after = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN (%s,%s,%s,%s,%s)",
		'bhp_visit_shelf_stock',
		'bhp_school_visits',
		'bhp_visit_shelf_backorders',
		'woocommerce_pickup_location_settings',
		'pickup_location_pickup_locations'
	),
	ARRAY_A
);
bhp_t( 'the five related options are byte-identical after exercising the gate', $before === wp_json_encode( $opt_rows_after ) );

$all_instock = true;
foreach ( $stock_before as $pid => $status ) {
	$p = wc_get_product( $pid );
	$now = $p ? $p->get_stock_status() : '(gone)';
	if ( $now !== $status ) {
		$all_instock = false;
		echo "  !! product $pid stock moved: $status -> $now\n";
	}
}
bhp_t( 'all six core products have an UNCHANGED _stock_status', $all_instock );
bhp_t(
	'this gate is not WooCommerce inventory: every core product is still instock',
	! in_array( false, array_map( function ( $s ) {
		return 'instock' === $s; }, $stock_before ), true ),
	wp_json_encode( $stock_before )
);

/* =========================================================================
 * §9 — CLEANUP
 * ====================================================================== */
bhp_h( '§9 — cleanup' );

if ( function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();
	bhp_t( 'cart emptied', 0 === WC()->cart->get_cart_contents_count() );
}
if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
	WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
	bhp_t( 'visit session flag cleared', ! bhp_school_visit_paperback_only() );
}
if ( function_exists( 'wc_clear_notices' ) ) {
	wc_clear_notices();
}

/*
 * ⭐ 1.8.76 — THE SUITE MUST NOT LEAVE ITS OWN SWITCH BEHIND. §7c removes it;
 *    this asserts that it did, so a suite that exits early through a skip
 *    cannot silently leave the founder's ruling switched off in a later test
 *    run sharing the same request.
 */
remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );
if ( function_exists( 'bhp_visit_shelf_backorder_allowed' ) ) {
	bhp_t( 'the suite left the backorder allowance at its shipped default (ON)', true === bhp_visit_shelf_backorder_allowed() );
}

echo "\n" . str_repeat( '=', 78 ) . "\n";
printf( "RESULT: %d passed, %d failed, %d skipped\n", $GLOBALS['bhp_pass'], $GLOBALS['bhp_fail'], $GLOBALS['bhp_skip'] );
echo str_repeat( '=', 78 ) . "\n";

if ( $GLOBALS['bhp_fail'] > 0 ) {
	echo "SUITE FAILED\n";
	exit( 1 );
}
