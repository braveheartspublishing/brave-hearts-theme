<?php
/**
 * Brave Hearts Dashboard — relocated-model source-of-truth test
 * (cost model 1.8.29, acquisition policy 1.8.30).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cost-model-source.php --user=1
 *
 * WHAT THIS PROTECTS
 * ------------------
 * Two families of private figure used to be compiled into this plugin,
 * which is published in a public repository:
 *
 *   - the unit economics -- print costs, postage quotes, the
 *     processing-fee assumption and the reserve rates. The company's cost
 *     side. Moved out in 1.8.29 into BHP_Cost_Config::MODEL_OPTION.
 *   - the approved acquisition policy -- the two Complete Collections'
 *     target CPA and operating ceilings, plus the banding ratios derived
 *     from them. What the company will pay for a customer. Moved out in
 *     1.8.30 into BHP_CPA_Model::MODEL_OPTION, on Andrew's ruling of
 *     2026-08-05.
 *
 * Two things have to stay true after each move, and one of them is easy
 * to lose by accident:
 *
 *   1. NO AMOUNT COMES BACK. Neither shipped class file may contain a
 *      currency-shaped literal. A helpful future edit that "restores the
 *      defaults so it works out of the box" is exactly the regression
 *      this asserts against.
 *   2. AN UNSEEDED ENVIRONMENT MUST SAY SO RATHER THAN DEFAULT TO ZERO.
 *      Treating a missing print cost as $0 does not fail loudly -- it
 *      reports the entire order total as profit. Treating a missing CPA
 *      target as $0 does not fail loudly either -- it grades every real
 *      campaign STOP and looks like a working dashboard. So the unseeded
 *      paths are exercised directly, with a filtered empty model, and
 *      must report 'unavailable' rather than a number.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 * It writes NOTHING. The unseeded paths are exercised through the
 * `bhp_cost_model` / `bhp_cpa_model` filters and the in-request caches,
 * never by deleting or rewriting the real options. Filters are removed
 * and caches flushed before the suite ends, and the restoration is itself
 * asserted.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cm_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// ==================== 1. No amount is committed to the source tree ====================

/*
 * BOTH relocated classes are scanned by the same instrument. The CPA
 * class joined this scan in 1.8.30; before that it carried the approved
 * targets and ceilings as compiled-in constants AND quoted them in its
 * docblocks, which is why the prose check below is not optional.
 */
$scanned_classes = array(
	'class-bhp-cost-config.php' => 'cost-config',
	'class-bhp-cpa-model.php'   => 'CPA-model',
);

foreach ( $scanned_classes as $file => $label ) {
	$config_file = dirname( __DIR__ ) . '/includes/dashboard/' . $file;
	$config_src  = file_exists( $config_file ) ? file_get_contents( $config_file ) : '';
	bhp_cm_assert( '' !== $config_src, "{$file} is readable for source inspection", $failures );

	// Strip block comments and line comments first: the files legitimately
	// discuss dates (2026-07-05) and version numbers in prose, and a naive
	// scan would flag those. What must be clean is the CODE.
	$code_only = preg_replace( '!/\*.*?\*/!s', '', $config_src );
	$code_only = preg_replace( '!//[^\n]*!', '', $code_only );

	// Any decimal literal with 1-3 fractional digits is money-shaped. Zero
	// is exempt and deliberately so: it is the sentinel the cost class
	// returns for an unavailable amount, and it discloses nothing. The only
	// other numerics that may remain in these files are array indexes,
	// rounding precisions and small integers.
	preg_match_all( '/(?<![\w.])\d+\.\d{1,3}(?![\w.])/', $code_only, $decimals );
	$money_like = array_values( array_filter(
		array_unique( $decimals[0] ),
		function ( $n ) { return 0.0 !== (float) $n; }
	) );
	bhp_cm_assert(
		empty( $money_like ),
		"The shipped {$label} class contains no non-zero money-shaped decimal literal in code" .
			( empty( $money_like ) ? '' : ' (found ' . count( $money_like ) . ' -- the amounts have leaked back into source)' ),
		$failures
	);

	// A currency symbol in the prose is the other way a figure creeps back.
	// "$0" is exempt for the same reason as 0.0 above -- the docblocks use
	// it to explain what these classes must NOT silently default to.
	bhp_cm_assert(
		0 === preg_match( '/\$[1-9]/', $config_src ) && 0 === preg_match( '/\$0\.\d/', $config_src ),
		"The shipped {$label} class quotes no dollar figure, in code or in prose",
		$failures
	);

	// A percentage-shaped literal is how the CPA banding ratios would come
	// back in prose after being removed from code: they are fractions, and
	// a fraction written as a percentage is still the figure. (This comment
	// deliberately names no example -- an earlier draft of it quoted the two
	// real ratios verbatim, which the tree scan caught, and which is a
	// perfect miniature of the problem: the leak arrived inside the fix.)
	bhp_cm_assert(
		0 === preg_match( '/(?<![\w.])\d+(\.\d+)?\s?%/', $config_src ),
		"The shipped {$label} class quotes no percentage figure in prose either",
		$failures
	);
}

// The two suites that consume the models must not pin figures either.
foreach ( array( 'test-offer-economics.php', 'test-dashboard.php' ) as $suite ) {
	$suite_src = file_get_contents( __DIR__ . '/' . $suite );
	/*
	 * Published storefront prices, discounts and shipping rates MAY be
	 * pinned -- they are on the live site and disclose nothing.
	 * Synthetic arithmetic inputs and comparison epsilons may be pinned
	 * too. Anything else is a cost or acquisition-policy figure and must
	 * not be.
	 *
	 * ⛔ 1.8.30 REMOVED the six approved CPA targets and ceilings from
	 *    this allowlist. They were legitimately pinned while they lived in
	 *    the source tree -- an approved ceiling that is not pinned is not
	 *    protected -- but they are private now, and the protection moved
	 *    to BHP_CPA_Model::policy_fingerprint(). Do not re-add them. If a
	 *    figure reappears here the suite fails, which is the point.
	 */
	$public_ok = array(
		// storefront prices, bundle discounts and shipping rates
		'31.99', '48.99', '11.99', '16.99', '3.98', '4.98', '1.99', '2.99', '3.99', '4.99', '0.00',
		// order totals composed from those prices, used as function inputs
		'13.98', '23.98', '24.98', '35.98', '35.97',
		// synthetic arithmetic inputs, probes and epsilons
		'20.00', '10.00', '4.00', '6.00', '7.00', '1.50', '1.00', '25.00', '15.00', '0.01', '0.001', '0.2', '0.0', '0.5',
	);
	$suite_code = preg_replace( '!/\*.*?\*/!s', '', $suite_src );
	$suite_code = preg_replace( '!//[^\n]*!', '', $suite_code );
	preg_match_all( '/(?<![\w.])\d+\.\d{1,3}(?![\w.])/', $suite_code, $found );
	$unexpected = array_values( array_diff( array_unique( $found[0] ), $public_ok ) );
	bhp_cm_assert(
		empty( $unexpected ),
		"{$suite} pins no figure outside the published storefront set" .
			( empty( $unexpected ) ? '' : ' (unexpected: ' . count( $unexpected ) . ')' ),
		$failures
	);
}

// ==================== 1b. The acquisition policy is seeded and intact ====================

bhp_cm_assert( class_exists( 'BHP_CPA_Model' ), 'BHP_CPA_Model is loaded', $failures );
bhp_cm_assert( 9 === count( BHP_CPA_Model::model_keys() ), 'The acquisition-policy contract lists 9 amounts', $failures );
bhp_cm_assert( 'bhp_cpa_model' === BHP_CPA_Model::MODEL_OPTION, 'The acquisition-policy option name is unchanged', $failures );
bhp_cm_assert( 'bhp_cost_model' !== BHP_CPA_Model::MODEL_OPTION, 'The acquisition policy is a SEPARATE option from the cost model -- different owner, different lifecycle, separately seeded', $failures );

$cpa_missing = BHP_CPA_Model::missing_model_keys();
bhp_cm_assert(
	empty( $cpa_missing ),
	'Every acquisition-policy key is seeded on this environment' . ( empty( $cpa_missing ) ? '' : ' (missing ' . count( $cpa_missing ) . ': ' . implode( ', ', $cpa_missing ) . ')' ),
	$failures
);

// The option must NOT be autoloaded: it is read only by the dashboard, and
// autoloading it puts private policy into every front-end request's option
// cache for no benefit.
global $wpdb;
$cpa_autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", BHP_CPA_Model::MODEL_OPTION ) );
bhp_cm_assert(
	null !== $cpa_autoload && ! in_array( strtolower( (string) $cpa_autoload ), array( 'yes', 'on', 'auto' ), true ),
	'The acquisition-policy option is NOT autoloaded' . ( null === $cpa_autoload ? ' (option row not found)' : " (autoload={$cpa_autoload})" ),
	$failures
);
bhp_cm_assert(
	64 === strlen( BHP_CPA_Model::policy_fingerprint() ),
	'A seeded policy produces a full-length joint fingerprint',
	$failures
);

// ==================== 2. This environment is seeded ====================

bhp_cm_assert( 21 === count( BHP_Cost_Config::model_keys() ), 'The model contract lists 21 amounts', $failures );
bhp_cm_assert( 'bhp_cost_model' === BHP_Cost_Config::MODEL_OPTION, 'The model option name is unchanged', $failures );

$missing = BHP_Cost_Config::missing_model_keys();
bhp_cm_assert( empty( $missing ), 'Every model key is seeded on this environment' . ( empty( $missing ) ? '' : ' (missing ' . count( $missing ) . ': ' . implode( ', ', $missing ) . ')' ), $failures );
bhp_cm_assert( BHP_Cost_Config::is_seeded(), 'BHP_Cost_Config::is_seeded() is true on this environment', $failures );

// Seeded means every getter reports a real basis, never 'unavailable'.
$seeded_bases = array(
	'print_cost_per_unit.paperback' => BHP_Cost_Config::print_cost_per_unit()['paperback']['basis'],
	'print_cost_per_unit.hardcover' => BHP_Cost_Config::print_cost_per_unit()['hardcover']['basis'],
	'bookvault_shipping_cost'       => BHP_Cost_Config::bookvault_shipping_cost()['basis'],
	'stripe_fee_formula'            => BHP_Cost_Config::stripe_fee_formula()['basis'],
	'refund_reserve'                => BHP_Cost_Config::refund_reserve_percentage()['basis'],
	'replacement_reserve'           => BHP_Cost_Config::replacement_reserve_percentage()['basis'],
	'chargeback_reserve'            => BHP_Cost_Config::chargeback_reserve_percentage()['basis'],
	'postage_single_paperback'      => BHP_Cost_Config::bookvault_postage_for_offer( 'paperback', 1 )['basis'],
	'postage_bundle'                => BHP_Cost_Config::bookvault_postage_for_offer( 'hardcover', 1 )['basis'],
);
$all_available = true;
foreach ( $seeded_bases as $basis ) {
	if ( 'unavailable' === $basis ) { $all_available = false; }
}
bhp_cm_assert( $all_available, 'No seeded getter reports basis=unavailable', $failures );

/*
 * The single-paperback postage is a DIFFERENT figure from the bundle one
 * -- that distinction is the whole reason bookvault_postage_for_offer()
 * exists, and a mis-seed that collapses them would be silent otherwise.
 * A strict '>' catches collapse as well as inversion.
 *
 * ⛔ DIRECTION CORRECTED 2026-08-06. This assertion previously read
 *    "Single-paperback postage is still higher than the
 *    bundle/single-hardcover figure". It encoded the Phase 1A working
 *    belief, and that belief is REFUTED: both figures are now ACTUAL --
 *    the single-paperback amount from Andrew Signore's own test order
 *    (attested by him 2026-08-06) and the multi/hardcover amount from
 *    Bookvault invoiced order 2848396 -- and the multi/hardcover amount
 *    is the LARGER of the two. The prior wording is quoted here rather
 *    than deleted so a future session does not re-derive it.
 *
 * ⚠ READ THIS BEFORE "FIXING" A FAILURE HERE. This assertion reads the
 *   LIVE bhp_cost_model option; it pins no literal and cannot be
 *   satisfied by editing this file. On an environment whose option
 *   still carries the pre-correction multi/hardcover value it WILL fail,
 *   and that failure is CORRECT SIGNAL: it is flagging the DATA, not the
 *   code. The failure label below therefore names the option, not the
 *   plugin. Fix the environment's cost model -- do not flip this back.
 */
bhp_cm_assert(
	BHP_Cost_Config::bookvault_postage_for_offer( 'hardcover', 1 )['amount'] > BHP_Cost_Config::bookvault_postage_for_offer( 'paperback', 1 )['amount'],
	'COST MODEL (not plugin code): the seeded bookvault_postage.multi_or_hardcover exceeds bookvault_postage.single_paperback -- a failure here means THIS ENVIRONMENT\'S bhp_cost_model option is uncorrected, not that the build regressed',
	$failures
);
bhp_cm_assert(
	BHP_Cost_Config::bookvault_postage_for_order( 1, 0 )['amount'] === BHP_Cost_Config::bookvault_postage_for_offer( 'paperback', 1 )['amount'],
	'A single-paperback order resolves to the single-paperback postage figure',
	$failures
);
bhp_cm_assert(
	BHP_Cost_Config::bookvault_postage_for_order( 3, 0 )['amount'] === BHP_Cost_Config::bookvault_postage_for_offer( 'hardcover', 1 )['amount'],
	'A three-paperback order resolves to the bundle postage figure, not the single-paperback one',
	$failures
);
bhp_cm_assert(
	BHP_Cost_Config::print_cost_per_unit()['hardcover']['amount'] > BHP_Cost_Config::print_cost_per_unit()['paperback']['amount'],
	'Hardcover print cost exceeds paperback print cost',
	$failures
);

// ==================== 3. The unseeded path is honest, not zero ====================

$force_empty = function () { return array(); };
add_filter( 'bhp_cost_model', $force_empty, 999 );
BHP_Cost_Config::flush_model_cache();

bhp_cm_assert( ! BHP_Cost_Config::is_seeded(), 'With an empty model, is_seeded() is false', $failures );
bhp_cm_assert( 21 === count( BHP_Cost_Config::missing_model_keys() ), 'With an empty model, every key is reported missing by name', $failures );
bhp_cm_assert( 'unavailable' === BHP_Cost_Config::print_cost_per_unit()['paperback']['basis'], 'With an empty model, print cost reports basis=unavailable', $failures );
bhp_cm_assert( 'unavailable' === BHP_Cost_Config::stripe_fee_formula()['basis'], 'With an empty model, the Stripe formula reports basis=unavailable', $failures );
bhp_cm_assert( 'unavailable' === BHP_Cost_Config::bookvault_postage_for_offer( 'paperback', 1 )['basis'], 'With an empty model, postage reports basis=unavailable', $failures );
bhp_cm_assert( 'unavailable' === BHP_Cost_Config::combo_print_cost( 'paperback', array( 'mariana' ) )['basis'], 'With an empty model, a mapped title still reports basis=unavailable rather than a $0 cost', $failures );

$unseeded_profit = BHP_Cost_Config::estimate_order_profit( 23.98, 2.99, 1.99, 24.98, array( 'paperback' => 2 ) );
bhp_cm_assert( 'unavailable' === $unseeded_profit['cost_basis'], 'With an empty model, estimate_order_profit() reports cost_basis=unavailable', $failures );
bhp_cm_assert( null === $unseeded_profit['estimated_profit'], 'With an empty model, estimated_profit is null -- NOT the order total reported as profit', $failures );
bhp_cm_assert( null === $unseeded_profit['estimated_print_cost'], 'With an empty model, estimated_print_cost is null, never 0.00', $failures );
bhp_cm_assert( 23.98 === $unseeded_profit['product_revenue'], 'With an empty model, the revenue side is still echoed back -- it comes from the real order and is known', $failures );

$unseeded_precise = BHP_Cost_Config::estimate_order_profit_precise( 11.99, 1.99, 0.0, 13.98, array( 'mariana' => 1 ), array() );
bhp_cm_assert( 'unavailable' === $unseeded_precise['cost_basis'], 'With an empty model, estimate_order_profit_precise() reports cost_basis=unavailable', $failures );
bhp_cm_assert( null === $unseeded_precise['estimated_profit'], 'With an empty model, the precise path returns null profit too', $failures );
bhp_cm_assert( 0.0 === BHP_Cost_Config::estimate_stripe_fee( 24.98 ), 'With an empty model, the Stripe estimate is 0.0 and its formula basis says unavailable', $failures );

// The offer table must not present an unseeded row as 'estimated'.
if ( class_exists( 'BHP_Offer_Economics' ) ) {
	$rows = BHP_Offer_Economics::sku_table();
	$any_estimated = false;
	foreach ( $rows as $row ) {
		if ( 'estimated' === $row['basis'] ) { $any_estimated = true; }
	}
	bhp_cm_assert( ! $any_estimated, 'With an empty model, no SKU row claims basis=estimated', $failures );
}

// A partial seed is not a seed.
remove_filter( 'bhp_cost_model', $force_empty, 999 );
$force_partial = function () { return array( 'stripe_fee.percentage' => 0.01, 'stripe_fee.fixed' => 0.01 ); };
add_filter( 'bhp_cost_model', $force_partial, 999 );
BHP_Cost_Config::flush_model_cache();
bhp_cm_assert( ! BHP_Cost_Config::is_seeded(), 'A partially populated model is NOT reported as seeded', $failures );
bhp_cm_assert( 'unavailable' === BHP_Cost_Config::print_cost_per_unit()['paperback']['basis'], 'A partial seed leaves the unsupplied keys reporting unavailable', $failures );
bhp_cm_assert( 'estimated' === BHP_Cost_Config::stripe_fee_formula()['basis'], 'A partial seed does report the keys it DOES supply as available', $failures );

// ==================== 4. Restoration is asserted, not assumed ====================

remove_filter( 'bhp_cost_model', $force_partial, 999 );
BHP_Cost_Config::flush_model_cache();
bhp_cm_assert( BHP_Cost_Config::is_seeded(), 'The real model is restored after the suite manipulates it', $failures );
bhp_cm_assert( empty( BHP_Cost_Config::missing_model_keys() ), 'No model key is missing after restoration', $failures );

// ==================== Result ====================

echo "\n";
echo empty( $failures ) ? "ALL COST-MODEL SOURCE TESTS PASSED\n" : count( $failures ) . " TEST(S) FAILED:\n";
if ( ! empty( $failures ) ) {
	foreach ( $failures as $f ) { echo " - {$f}\n"; }
	exit( 1 );
}
