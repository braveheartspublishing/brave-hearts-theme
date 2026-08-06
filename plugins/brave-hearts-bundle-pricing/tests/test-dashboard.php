<?php
/**
 * Brave Hearts Dashboard — offer classification and cost estimation tests.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-dashboard.php --user=1
 *
 * Exits non-zero on any failure. Only exercises the pure classify_items()
 * function and BHP_Cost_Config's math -- no real orders are read or
 * written by this file.
 *
 * Since 1.8.29 no cost figure is pinned here. The unit-economics amounts
 * live in a per-environment option, not in this (public) source tree --
 * see class-bhp-cost-config.php -- so the cost assertions below are
 * relational, computed against values read back from the model itself.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_dash_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// Canonical catalog IDs (bundle-data.php) used directly rather than
// re-deriving them, so a classification test failure can't be masked by
// an unrelated catalog lookup bug.
$PB_MARIANA = 334;
$PB_EVEREST = 15;
$PB_AMAZON  = 18;
$HC_MARIANA = 14;
$HC_EVEREST = 17;
$HC_AMAZON  = 20;
$NON_CATALOG = 99999;

function items( array $pairs ) {
	// $pairs: [ [id, qty], ... ]
	return array_map( function ( $p ) {
		return array( 'id' => $p[0], 'quantity' => $p[1] );
	}, $pairs );
}

// --- Single-title orders ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::SINGLE_PAPERBACK === $r['offer_type'], 'One paperback -> single_paperback', $failures );

$r = BHP_Offer_Classifier::classify_items( items( array( array( $HC_EVEREST, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::SINGLE_HARDCOVER === $r['offer_type'], 'One hardcover -> single_hardcover', $failures );

// --- Two-distinct-title bundles ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 1 ), array( $PB_EVEREST, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::TWO_PAPERBACK_BUNDLE === $r['offer_type'], 'Two distinct paperbacks -> two_paperback_bundle', $failures );

$r = BHP_Offer_Classifier::classify_items( items( array( array( $HC_MARIANA, 1 ), array( $HC_AMAZON, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::TWO_HARDCOVER_BUNDLE === $r['offer_type'], 'Two distinct hardcovers -> two_hardcover_bundle', $failures );

// --- Complete sets ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 1 ), array( $PB_EVEREST, 1 ), array( $PB_AMAZON, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::COMPLETE_PAPERBACK_SET === $r['offer_type'], 'Three distinct paperbacks -> complete_paperback_set', $failures );

$r = BHP_Offer_Classifier::classify_items( items( array( array( $HC_MARIANA, 1 ), array( $HC_EVEREST, 1 ), array( $HC_AMAZON, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::COMPLETE_HARDCOVER_SET === $r['offer_type'], 'Three distinct hardcovers -> complete_hardcover_collection', $failures );

// --- Mixed format ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 1 ), array( $HC_EVEREST, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::MIXED_FORMAT === $r['offer_type'], 'One paperback + one hardcover -> mixed_format_order', $failures );

$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 1 ), array( $PB_EVEREST, 1 ), array( $PB_AMAZON, 1 ), array( $HC_MARIANA, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::MIXED_FORMAT === $r['offer_type'], 'Complete paperback set + one hardcover -> mixed_format_order (not both-complete)', $failures );

// --- Both complete collections ---
$r = BHP_Offer_Classifier::classify_items( items( array(
	array( $PB_MARIANA, 1 ), array( $PB_EVEREST, 1 ), array( $PB_AMAZON, 1 ),
	array( $HC_MARIANA, 1 ), array( $HC_EVEREST, 1 ), array( $HC_AMAZON, 1 ),
) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::BOTH_COMPLETE === $r['offer_type'], 'All six distinct editions -> both_complete_collections', $failures );

// --- Duplicate-title edge cases (no bundle earned) ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 2 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::OTHER === $r['offer_type'], 'Same paperback title x2 (1 distinct, 2 units) -> other_needs_review', $failures );
bhp_dash_test_assert( true === $r['has_duplicate_units'], 'Duplicate-title order flags has_duplicate_units', $failures );

// --- Non-catalog items ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $NON_CATALOG, 1 ) ) ) );
bhp_dash_test_assert( BHP_Offer_Classifier::OTHER === $r['offer_type'], 'Only non-catalog items -> other_needs_review', $failures );
bhp_dash_test_assert( 1 === $r['units_non_catalog'], 'Non-catalog units counted separately from book units', $failures );

// --- Empty cart ---
$r = BHP_Offer_Classifier::classify_items( array() );
bhp_dash_test_assert( BHP_Offer_Classifier::OTHER === $r['offer_type'], 'Empty item list -> other_needs_review', $failures );

// --- Distinct/unit counts are correct alongside classification ---
$r = BHP_Offer_Classifier::classify_items( items( array( array( $PB_MARIANA, 1 ), array( $PB_EVEREST, 1 ), array( $HC_AMAZON, 1 ) ) ) );
bhp_dash_test_assert( 2 === $r['distinct_paperback'] && 1 === $r['distinct_hardcover'], 'Mixed cart reports correct distinct counts per format', $failures );
bhp_dash_test_assert( 2 === $r['units_paperback'] && 1 === $r['units_hardcover'], 'Mixed cart reports correct unit counts per format', $failures );

// ==================== Cost estimation ====================

$per_unit = BHP_Cost_Config::print_cost_per_unit();
$profit   = BHP_Cost_Config::estimate_order_profit( 23.98, 2.99, 1.99, 24.98, array( 'paperback' => 2 ) );
bhp_dash_test_assert( abs( $profit['estimated_print_cost'] - ( $per_unit['paperback']['amount'] * 2 ) ) < 0.01, 'Print cost scales by paperback unit count', $failures );
$profit_one = BHP_Cost_Config::estimate_order_profit( 11.99, 1.99, 0.00, 13.98, array( 'paperback' => 1 ) );
bhp_dash_test_assert( abs( ( $profit['estimated_print_cost'] - $profit_one['estimated_print_cost'] ) - $per_unit['paperback']['amount'] ) < 0.01, 'A second paperback unit adds exactly one unit of print cost, never a flat or doubled figure', $failures );
bhp_dash_test_assert( $profit['estimated_stripe_fee'] > 0, 'Stripe fee estimate is a positive number', $failures );
bhp_dash_test_assert( 'estimated' === $profit['cost_basis'], 'Profit is always labeled estimated, never exact, until real cost data exists', $failures );

// The seeded state is itself an assertion: an unseeded environment must
// report 'unavailable', and this environment must not be in that state.
bhp_dash_test_assert( BHP_Cost_Config::is_seeded(), 'The unit-economics model is seeded on this environment (option present and complete)', $failures );
bhp_dash_test_assert( array() === BHP_Cost_Config::missing_model_keys(), 'No cost model key is missing', $failures );

$stripe_cfg   = BHP_Cost_Config::stripe_fee_formula();
$stripe_fee   = BHP_Cost_Config::estimate_stripe_fee( 24.98 );
$expected_fee = round( ( 24.98 * $stripe_cfg['percentage'] ) + $stripe_cfg['fixed'], 2 );
bhp_dash_test_assert( abs( $stripe_fee - $expected_fee ) < 0.001, 'Stripe fee applies the configured percentage plus the configured fixed component', $failures );

// ==================== Bookvault status pattern matching ====================

bhp_dash_test_assert(
	1 === preg_match( BHP_Bookvault_Status::SUCCESS_PATTERN, 'Order saved with status Active as BV2796848', $m ) && 'BV2796848' === $m[2],
	'Success pattern extracts the Bookvault reference from a real observed note',
	$failures
);
bhp_dash_test_assert(
	1 === preg_match( BHP_Bookvault_Status::SUCCESS_PATTERN, 'Order saved with status Draft as BV2796764', $m ) && 'Draft' === $m[1],
	'Success pattern extracts the Bookvault state word (Draft) from a real observed note',
	$failures
);
bhp_dash_test_assert(
	1 === preg_match( BHP_Bookvault_Status::FAILURE_PATTERN, 'Failed to read line_items: Notice - The Bookvault plugin scans all incoming orders...' ),
	'Failure pattern matches the real observed routing-failure note',
	$failures
);
bhp_dash_test_assert(
	0 === preg_match( BHP_Bookvault_Status::SUCCESS_PATTERN, 'Stripe charge complete (Charge ID: py_abc123)' ),
	'Success pattern does not false-match an unrelated Stripe note',
	$failures
);

echo empty( $failures ) ? "\nALL DASHBOARD TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
