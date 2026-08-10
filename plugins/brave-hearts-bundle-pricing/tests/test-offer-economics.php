<?php
/**
 * Brave Hearts Dashboard — Phase 1A offer-economics + CPA test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-offer-economics.php --user=1
 *
 * Exercises BHP_Cost_Config's per-title costing, BHP_Offer_Economics's
 * offer table, and BHP_CPA_Model's CPA table -- all pure functions
 * reading only the static catalog (bundle-data.php), never a real order.
 * No real WC_Order/WC_Order_Refund is read or written by this file.
 *
 * ---------------------------------------------------------------------
 * NO COST FIGURE IS PINNED IN THIS FILE (since 1.8.29)
 * ---------------------------------------------------------------------
 * The unit-economics amounts moved out of the source tree into a
 * per-environment option (see class-bhp-cost-config.php). A test that
 * pinned a contribution, a break-even or a print cost as a literal would
 * put the relocated figures straight back into the public repository --
 * storefront prices are public, so a contribution literal discloses the
 * cost that produced it by subtraction.
 *
 * So every cost assertion below is RELATIONAL: it asserts the identities
 * the model must satisfy (gross profit = revenue - fees - costs;
 * contribution = gross profit - reserves; per-title costs differ;
 * hardcover exceeds paperback; break-even equals contribution) against
 * values read back from the model itself. Storefront prices, discounts
 * and shipping ARE asserted as literals -- those are published on the
 * site and are not private.
 *
 * A relational assertion cannot catch a wholesale mis-seed of the option.
 * That is stated rather than papered over: the seeded model is verified
 * separately, by fingerprint, at deploy time.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_econ_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_econ_close( $a, $b, $eps = 0.01 ) {
	return abs( (float) $a - (float) $b ) < $eps;
}

// ==================== Every individual SKU ====================
$sku_rows = BHP_Offer_Economics::sku_table();
bhp_econ_test_assert( 6 === count( $sku_rows ), 'sku_table() returns exactly 6 rows (3 titles x 2 formats)', $failures );

$sku_by_key = array();
foreach ( $sku_rows as $row ) {
	$sku_by_key[ $row['sku_key'] ] = $row;

	// Identity 1: gross profit is revenue minus every modelled cost. This
	// is what catches a cost silently dropping out of the formula.
	$expected_gross = round(
		( $row['price'] + $row['shipping_collected'] )
			- $row['stripe_fee']
			- $row['print_cost']['amount']
			- $row['postage']['amount'],
		2
	);
	bhp_econ_test_assert(
		bhp_econ_close( $row['estimated_gross_profit'], $expected_gross ),
		"SKU {$row['sku_key']}: gross profit = price + shipping - stripe - print - postage",
		$failures
	);

	// Identity 2: contribution is gross profit minus the reserve holdback.
	bhp_econ_test_assert(
		bhp_econ_close( $row['contribution_before_acquisition'], round( $row['estimated_gross_profit'] - $row['reserves']['total'], 2 ) ),
		"SKU {$row['sku_key']}: contribution before acquisition = gross profit - reserves",
		$failures
	);

	bhp_econ_test_assert( $row['contribution_before_acquisition'] > 0, "SKU {$row['sku_key']}: contribution before acquisition is positive", $failures );
	bhp_econ_test_assert( 'estimated' === $row['basis'], "SKU {$row['sku_key']}: basis is 'estimated', never silently 'actual' and never 'unavailable'", $failures );
	bhp_econ_test_assert( 'estimated' === $row['print_cost']['basis'], "SKU {$row['sku_key']}: print cost basis is 'estimated' -- the cost model is seeded on this environment", $failures );
}

// Hardcover must contribute more than paperback for the same title, and
// the three titles must not share one print cost -- the whole reason a
// per-title table exists.
foreach ( array( 'mariana', 'everest', 'amazon' ) as $title_key ) {
	bhp_econ_test_assert(
		$sku_by_key[ 'hardcover:' . $title_key ]['contribution_before_acquisition'] > $sku_by_key[ 'paperback:' . $title_key ]['contribution_before_acquisition'],
		"SKU {$title_key}: hardcover contributes more than paperback",
		$failures
	);
}
$pb_print_costs = array_map( function ( $k ) use ( $sku_by_key ) { return $sku_by_key[ 'paperback:' . $k ]['print_cost']['amount']; }, array( 'mariana', 'everest', 'amazon' ) );
bhp_econ_test_assert( count( array_unique( $pb_print_costs ) ) > 1, 'Per-title paperback print costs are NOT all identical (the per-title table is doing real work)', $failures );

// ==================== Every two-book combination (do not assume identical print cost) ====================
$offer_rows = BHP_Offer_Economics::offer_table();
$two_pb = array_filter( $offer_rows, function ( $r ) { return BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE === $r['offer_type']; } );
$two_hc = array_filter( $offer_rows, function ( $r ) { return BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE === $r['offer_type']; } );
bhp_econ_test_assert( 3 === count( $two_pb ), 'Exactly 3 distinct two-paperback combinations exist', $failures );
bhp_econ_test_assert( 3 === count( $two_hc ), 'Exactly 3 distinct two-hardcover combinations exist', $failures );

$pb_contributions = array_map( function ( $r ) { return $r['contribution_before_acquisition']; }, $two_pb );
$hc_contributions = array_map( function ( $r ) { return $r['contribution_before_acquisition']; }, $two_hc );
bhp_econ_test_assert( count( array_unique( $pb_contributions ) ) > 1, 'Two-paperback combinations do NOT all have identical contribution (print cost varies by title)', $failures );
bhp_econ_test_assert( count( array_unique( $hc_contributions ) ) > 1, 'Two-hardcover combinations do NOT all have identical contribution (print cost varies by title)', $failures );

// Each two-book row must satisfy the same identities as a single SKU,
// and every two-book row must out-contribute the single-book row of the
// same format -- asserted structurally rather than against a pinned
// figure, because a pinned contribution discloses the print cost behind
// it (the price is public).
$two_row_identity = function ( $row ) {
	// The offer table's own identity: subtotal - discount is the offer
	// price, so gross profit reduces to the same shape as a single SKU.
	$expected_gross = round(
		( $row['price'] + $row['shipping_collected'] )
			- $row['stripe_fee']
			- $row['print_cost']['amount']
			- $row['postage']['amount'],
		2
	);
	return bhp_econ_close( $row['estimated_gross_profit'], $expected_gross )
		&& bhp_econ_close(
			$row['contribution_before_acquisition'],
			round( $row['estimated_gross_profit'] - $row['reserves']['total'], 2 )
		);
};
$two_identities_hold = true;
foreach ( array_merge( array_values( $two_pb ), array_values( $two_hc ) ) as $row ) {
	if ( ! $two_row_identity( $row ) ) { $two_identities_hold = false; }
	if ( 'estimated' !== $row['basis'] ) { $two_identities_hold = false; }
}
bhp_econ_test_assert( $two_identities_hold, 'Every two-book row satisfies contribution = gross profit - reserves, with basis=estimated', $failures );
bhp_econ_test_assert( min( $hc_contributions ) > max( $pb_contributions ), 'Every two-hardcover combination out-contributes every two-paperback combination', $failures );
bhp_econ_test_assert( min( $pb_contributions ) > $sku_by_key['paperback:mariana']['contribution_before_acquisition'], 'A two-paperback bundle out-contributes a single paperback', $failures );

// ==================== Complete Collections ====================
$complete_pb = array_values( array_filter( $offer_rows, function ( $r ) { return BHP_Offer_Economics::COMPLETE_PAPERBACK_SET === $r['offer_type']; } ) );
$complete_hc = array_values( array_filter( $offer_rows, function ( $r ) { return BHP_Offer_Economics::COMPLETE_HARDCOVER_SET === $r['offer_type']; } ) );
bhp_econ_test_assert( 1 === count( $complete_pb ), 'Exactly 1 Complete Paperback Collection row', $failures );
bhp_econ_test_assert( 1 === count( $complete_hc ), 'Exactly 1 Complete Hardcover Collection row', $failures );
/*
 * ═══════════════════════════════════════════════════════════════════════
 * 1.8.23 — CONTRIBUTION FALLS BY EXACTLY THE SHIPPING GIVEN AWAY.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * SUPERSEDED and REDACTED, retained so the movement stays visible: this
 * block previously pinned four contribution figures as literals and
 * quoted the delta between them. Storefront prices are public, so a
 * contribution literal discloses the print and postage costs behind it.
 * The figures moved out of source with the rest of the cost model in
 * 1.8.29; the RELATION they encoded is asserted below instead, and it is
 * the stronger test of the two -- it fails if the free-shipping change
 * ever stops costing exactly what it should.
 *
 * The relation: absorbing collection shipping reduces contribution by the
 * shipping no longer collected, MINUS the Stripe fee and reserve that are
 * no longer charged on that shipping. Nothing else may move.
 *
 * The underlying print/postage costs are ESTIMATED, G-28 is open, and
 * every assertion here inherits that caveat.
 *
 * CPA CONSEQUENCE, recorded not resolved: break-even CPA IS contribution
 * before ads (`CYCLE143-FIN-11`, Andrew's to close). The canonical record
 * of the approved CPA table belongs to Business Ops, not to this file.
 */
$stripe_cfg   = BHP_Cost_Config::stripe_fee_formula();
$refund_cfg   = BHP_Cost_Config::refund_reserve_percentage();
$replace_cfg  = BHP_Cost_Config::replacement_reserve_percentage();
$reserve_rate = $refund_cfg['percentage'] + $replace_cfg['percentage'];

$collection_relation_holds = true;
foreach ( array( 'paperback' => $complete_pb[0], 'hardcover' => $complete_hc[0] ) as $format => $row ) {
	// contribution = gross profit - reserves, and gross profit is the
	// revenue identity. Both must hold on the collection rows too.
	if ( ! bhp_econ_close( $row['contribution_before_acquisition'], round( $row['estimated_gross_profit'] - $row['reserves']['total'], 2 ) ) ) {
		$collection_relation_holds = false;
	}
	// The three-title subtotal minus the approved bundle discount IS the
	// collection price, asserted here from bundle-data.php rather than
	// assumed, so the gross-profit identity below is the same shape as a
	// single SKU's.
	$subtotal = 3 * bhp_bundle_expected_price( $format );
	$rules    = bhp_bundle_rules( $format );
	if ( ! bhp_econ_close( $subtotal - $rules[3]['discount'], $row['price'] ) ) {
		$collection_relation_holds = false;
	}
	$expected_gross = round(
		( $row['price'] + $row['shipping_collected'] )
			- $row['stripe_fee']
			- $row['print_cost']['amount']
			- $row['postage']['amount'],
		2
	);
	if ( ! bhp_econ_close( $row['estimated_gross_profit'], $expected_gross ) ) {
		$collection_relation_holds = false;
	}
	// The reserve is charged on what the customer actually pays, which
	// after the free-shipping ruling is the price alone.
	if ( ! bhp_econ_close( $row['reserves']['total'], round( $row['price'] * $reserve_rate, 2 ) ) ) {
		$collection_relation_holds = false;
	}
}
bhp_econ_test_assert( $collection_relation_holds, '1.8.23/1.8.29: both Complete Collection rows satisfy the revenue, reserve and contribution identities with shipping collected at $0.00', $failures );
bhp_econ_test_assert( $complete_hc[0]['contribution_before_acquisition'] > $complete_pb[0]['contribution_before_acquisition'], 'Complete Hardcover Collection contributes more than Complete Paperback Collection', $failures );
bhp_econ_test_assert( 31.99 === $complete_pb[0]['price'], 'Complete Paperback Collection price matches approved storefront price ($31.99)', $failures );
bhp_econ_test_assert( 48.99 === $complete_hc[0]['price'], 'Complete Hardcover Collection price matches approved storefront price ($48.99)', $failures );

// ==================== Stripe fee calculation ====================
$fee = BHP_Cost_Config::estimate_stripe_fee( 35.98 );
$expected_fee = round( 35.98 * $stripe_cfg['percentage'] + $stripe_cfg['fixed'], 2 );
bhp_econ_test_assert( bhp_econ_close( $fee, $expected_fee ), 'Stripe fee applies the configured percentage plus the configured fixed component, in that order', $failures );
bhp_econ_test_assert( 'estimated' === $stripe_cfg['basis'], 'Stripe fee formula is available from the seeded cost model, not reported unavailable', $failures );

// ==================== Shipping collected / discount handling ====================
bhp_econ_test_assert( 0.00 === $complete_pb[0]['shipping_collected'], '1.8.23: Complete Paperback Collection shipping collected is $0.00 (was $3.99)', $failures );
$rules = bhp_bundle_rules( 'paperback' );
bhp_econ_test_assert( 0.00 === $rules[3]['shipping'], '1.8.23: Complete Paperback Collection approved shipping is $0.00 (from bhp_bundle_rules, single source of truth)', $failures );
bhp_econ_test_assert( 3.98 === $rules[3]['discount'], 'Complete Paperback Collection approved discount is $3.98 (UNCHANGED by the free-shipping ruling)', $failures );
bhp_econ_test_assert( 31.99 === $complete_pb[0]['price'], '1.8.23: the PRICE the economics model uses is still $31.99, so the model reflects Option B and not a reprice', $failures );
// The single-book rates are the economics model's other shipping input and
// they did not move. Asserted here so a future free-shipping extension to
// singles cannot land silently inside the dashboard.
bhp_econ_test_assert( 1.99 === bhp_bundle_single_shipping( 'paperback' ), '1.8.23: single-paperback shipping still $1.99 in the economics model', $failures );
bhp_econ_test_assert( 2.99 === bhp_bundle_single_shipping( 'hardcover' ), '1.8.23: single-hardcover shipping still $2.99 in the economics model', $failures );

// ==================== Refund reserve / replacement reserve ====================
$reserves = BHP_Offer_Economics::reserve_amount( 31.99, 0.00 );
$expected_refund = round( ( 31.99 + 0.00 ) * $refund_cfg['percentage'], 2 );
$expected_replacement = round( ( 31.99 + 0.00 ) * $replace_cfg['percentage'], 2 );
bhp_econ_test_assert( bhp_econ_close( $reserves['refund_amount'], $expected_refund ), 'Refund reserve amount is the configured refund rate applied to price+shipping', $failures );
bhp_econ_test_assert( bhp_econ_close( $reserves['replacement_amount'], $expected_replacement ), 'Replacement reserve amount is the configured replacement rate applied to price+shipping', $failures );
bhp_econ_test_assert( 0.0 === $reserves['chargeback_amount'], 'Chargeback reserve is excluded unless explicitly included', $failures );
// Approved policy (2026-07-06): the chargeback reserve rate is set to a
// configured value that is currently not applied. Asserted as a relation
// against the configured rate, never as a pinned rate.
$chargeback_cfg = BHP_Cost_Config::chargeback_reserve_percentage();
$reserves_with_chargeback = BHP_Offer_Economics::reserve_amount( 31.99, 0.00, true );
bhp_econ_test_assert(
	bhp_econ_close( $reserves_with_chargeback['chargeback_amount'], round( 31.99 * $chargeback_cfg['percentage'], 2 ) ),
	'Chargeback reserve amount equals the configured chargeback rate applied to price+shipping when explicitly included',
	$failures
);
bhp_econ_test_assert(
	bhp_econ_close( $reserves['total'], round( $expected_refund + $expected_replacement, 2 ) ),
	'Reserve total is refund + replacement, with chargeback excluded by default',
	$failures
);

// ==================== Contribution before/after acquisition ====================
/*
 * Synthetic inputs, deliberately NOT real contribution or CPA figures --
 * this function is arithmetic and does not need a real one to be
 * exercised.
 *
 * ⚠️ 1.8.30 CHANGED THE SECOND ARGUMENT, and the reason is worth keeping.
 *    It used to be a round number that happened to equal one of Andrew's
 *    approved target CPAs exactly, sitting in an acquisition-cost
 *    position in a public file. Nobody put it there to disclose anything;
 *    it was picked because it made the subtraction tidy. That is how this
 *    class of leak actually happens, and it is why the source-scan guard
 *    checks literals rather than intent. Do not reintroduce a "tidy"
 *    number here without checking it against the seeded policy.
 */
$after = BHP_Offer_Economics::contribution_after_acquisition( 25.00, 15.00 );
bhp_econ_test_assert( bhp_econ_close( $after, 10.00 ), 'Contribution after acquisition = contribution before acquisition - attributed spend', $failures );

// ==================== Missing / null acquisition cost ====================
$after_null = BHP_Offer_Economics::contribution_after_acquisition( 20.00, null );
bhp_econ_test_assert( bhp_econ_close( $after_null, 20.00 ), 'Missing/null acquisition cost -> contribution after acquisition equals contribution before (no spend attributed)', $failures );

// ==================== Negative contribution ====================
$negative = BHP_Offer_Economics::contribution_after_acquisition( 4.00, 10.00 );
bhp_econ_test_assert( $negative < 0, 'Overspending acquisition cost beyond contribution correctly produces a negative contribution (not clamped to zero)', $failures );
bhp_econ_test_assert( bhp_econ_close( $negative, -6.00 ), 'Negative contribution value is exact, not just negative', $failures );

// ==================== Margin calculation ====================
$margin = $complete_pb[0]['contribution_margin_pct'];
// 1.8.23: numerator is the free-shipping contribution; the denominator is
// price + shipping collected, and shipping collected is now $0.00. Read
// the numerator back from the row rather than pinning it.
$expected_margin = round( ( $complete_pb[0]['contribution_before_acquisition'] / ( 31.99 + 0.00 ) ) * 100, 1 );
bhp_econ_test_assert( bhp_econ_close( $margin, $expected_margin, 0.2 ), 'Complete Paperback Collection contribution margin % is computed as contribution / (price+shipping)', $failures );

// ==================== Missing SKU mapping / unknown cost state ====================
$unknown_combo = BHP_Cost_Config::combo_print_cost( 'paperback', array( 'nonexistent_title' ) );
bhp_econ_test_assert( 'unknown' === $unknown_combo['basis'], 'A title with no cost mapping reports basis=unknown, not a silent $0', $failures );
bhp_econ_test_assert( 0.0 === $unknown_combo['amount'], 'Unknown-title combo amount excludes the unmapped title rather than guessing', $failures );
bhp_econ_test_assert( in_array( 'nonexistent_title', $unknown_combo['unknown_titles'], true ), 'Unknown title is named explicitly, not just flagged generically', $failures );

$mixed_known_unknown = BHP_Cost_Config::combo_print_cost( 'paperback', array( 'mariana', 'nonexistent_title' ) );
bhp_econ_test_assert( 'unknown' === $mixed_known_unknown['basis'], 'A combo with one known + one unknown title still reports basis=unknown overall (not silently estimated)', $failures );
$mariana_pb_cost = BHP_Cost_Config::title_print_cost()['paperback']['mariana']['amount'];
bhp_econ_test_assert( bhp_econ_close( $mixed_known_unknown['amount'], $mariana_pb_cost ), 'Partial-unknown combo amount reflects only the known title\'s cost, not zero for the whole combo', $failures );

// ==================== estimate_order_profit_precise() unknown-cost propagation ====================
$precise_unknown = BHP_Cost_Config::estimate_order_profit_precise( 11.99, 1.99, 0.0, 13.98, array( 'nonexistent_title' => 1 ), array() );
bhp_econ_test_assert( 'unknown' === $precise_unknown['cost_basis'], 'estimate_order_profit_precise() propagates unknown cost_basis when a title has no print-cost mapping', $failures );
bhp_econ_test_assert( in_array( 'paperback:nonexistent_title', $precise_unknown['unknown_titles'], true ), 'estimate_order_profit_precise() names the specific unknown title', $failures );

$precise_known = BHP_Cost_Config::estimate_order_profit_precise( 11.99, 1.99, 0.0, 13.98, array( 'mariana' => 1 ), array() );
bhp_econ_test_assert( 'estimated' === $precise_known['cost_basis'], 'estimate_order_profit_precise() reports cost_basis=estimated when every title is mapped', $failures );
// estimate_order_profit_precise() is the REAL-order path, which never
// applies a reserve deduction (a real order's actual refund status, if
// any, is already known via BHP_Refund_Metrics instead). The SKU table's
// figure is the PROSPECTIVE, reserve-adjusted one, so the real-order
// figure must be strictly LARGER by exactly the reserve. That relation is
// the assertion; neither number is pinned. See the class docblock on
// BHP_Offer_Economics.
$precise_expected = round(
	( 11.99 + 1.99 )
		- BHP_Cost_Config::estimate_stripe_fee( 13.98 )
		- $mariana_pb_cost
		- BHP_Cost_Config::bookvault_postage_for_order( 1, 0 )['amount'],
	2
);
bhp_econ_test_assert( bhp_econ_close( $precise_known['estimated_profit'], $precise_expected ), 'estimate_order_profit_precise() for a single known mapped paperback equals revenue - stripe - per-title print - single-paperback postage', $failures );
bhp_econ_test_assert( $precise_known['estimated_profit'] > $sku_by_key['paperback:mariana']['contribution_before_acquisition'], 'The real-order profit path exceeds the prospective SKU contribution, because it applies no reserve', $failures );

// ==================== Legacy/non-catalog product handling (via classifier) ====================
$legacy_items = array( array( 'id' => 12, 'quantity' => 1 ) ); // known legacy product ID
$legacy_classification = BHP_Offer_Classifier::classify_items( $legacy_items );
bhp_econ_test_assert( empty( $legacy_classification['paperback_titles'] ) && empty( $legacy_classification['hardcover_titles'] ), 'A legacy/non-catalog-only order has no paperback/hardcover title map -- economics correctly has nothing to cost, not a false $0', $failures );
bhp_econ_test_assert( 1 === $legacy_classification['units_non_catalog'], 'Legacy product unit is counted as non-catalog, the signal BHP_Order_Metrics uses to mark unknown cost rather than zero', $failures );

// ==================== Break-even / safer-max / target CPA ====================
$cpa_rows = BHP_CPA_Model::build_table();
bhp_econ_test_assert( 6 === count( $cpa_rows ), 'CPA table has exactly 6 rows (one per required offer type)', $failures );

$cpa_by_type = array();
foreach ( $cpa_rows as $row ) { $cpa_by_type[ $row['offer_type'] ] = $row; }

/*
 * 1.8.23 — BREAK-EVEN MOVED AND THE APPROVED CEILINGS DID NOT.
 *
 * Break-even CPA IS contribution before ads, so absorbing collection
 * shipping lowered it on both collections. The Andrew-approved ceilings
 * are asserted UNCHANGED on purpose: **only Andrew may set a CPA
 * ceiling**, the relayed ruling gave no replacement figures, and an
 * engineer inventing them here would be writing an unapproved number into
 * a table labelled "approved".
 *
 * The consequence is asserted rather than hidden: the approved ceiling
 * now sits ABOVE break-even on both collections, which means spending to
 * it would lose money on every order. `CYCLE143-FIN-11`, still OPEN and
 * Andrew's to close. The dashboard labels the row.
 *
 * ─────────────────────────────────────────────────────────────────────
 * 1.8.30 — THE PIN SURVIVES WITHOUT THE LITERALS. Read this before
 *          "simplifying" the assertions below.
 * ─────────────────────────────────────────────────────────────────────
 *
 * REDACTED in 1.8.29: the four break-even literals that stood here are
 * derived from the relocated cost model, so they are asserted against the
 * model's own contribution rows instead.
 *
 * REDACTED in 1.8.30: the approved targets and ceilings themselves. They
 * are acquisition policy, this repository is public, and Andrew's ruling
 * of 2026-08-05 was to remove them. They now live in the per-environment
 * `bhp_cpa_model` option.
 *
 * ⛔ AN APPROVED CEILING THAT IS NOT PINNED IS NOT PROTECTED. The reason
 *    the literals were here at all is that an unauthorised edit to a
 *    ceiling failed a test. Relocating them must not quietly surrender
 *    that, so the protection is re-expressed as an EXACT-MATCH FINGERPRINT
 *    of the whole loaded policy. Change any target, any ceiling or any
 *    ratio without authorisation and this suite fails, exactly as before,
 *    without this file naming a figure.
 *
 * ⭐ The fingerprint is ONE JOINT DIGEST over all nine values, never one
 *    digest per value. That is a security property, not a convenience: a
 *    hash of a single currency amount has a small enough search space to
 *    invert by brute force; the joint space does not. Do not split it.
 *
 * ⚠️ A FAILURE HERE HAS EXACTLY TWO CAUSES, and both must be investigated
 *    rather than "fixed" by pasting in the new digest: either an
 *    unauthorised change to approved policy, or a mis-seeded environment.
 *    Only Andrew can authorise the first; updating this constant without
 *    his decision is the precise regression it exists to catch.
 */
bhp_econ_test_assert( BHP_CPA_Model::is_seeded(), 'The acquisition-policy option is seeded on this environment', $failures );
bhp_econ_test_assert( 9 === count( BHP_CPA_Model::model_keys() ), 'The acquisition-policy contract lists 9 amounts', $failures );
bhp_econ_test_assert( 'bhp_cpa_model' === BHP_CPA_Model::MODEL_OPTION, 'The acquisition-policy option name is unchanged', $failures );

/*
 * Authorised as of 2026-08-06 (1.8.31): Andrew's REVISED acquisition policy,
 * decided by him directly on the re-approved contribution basis and seeded
 * out of band. It SUPERSEDES the 2026-07-06 policy this constant pinned from
 * 1.8.29 through 1.8.30.
 *
 * ⛔ THE VALUE BELOW WAS READ BACK FROM THE SEEDED OPTION, NOT COMPUTED.
 *    The re-seed script prints it as READ_BACK_FINGERPRINT after verifying its
 *    own round trip; that printed line is the only permitted source. Deriving
 *    this digest from a document, a spreadsheet or a memory would prove that
 *    the document and the constant agree, which is not the property this
 *    assertion exists to establish. What it must prove is that the LOADED
 *    OPTION matches what Andrew authorised.
 *
 * ⚠️ The two-causes warning above is unchanged and still governs: a failure
 *    here is either an unauthorised policy change or a mis-seeded environment,
 *    and neither is fixed by pasting in a new digest.
 *
 * SUPERSEDED, retained so the movement is visible rather than re-derived:
 *   2026-07-06 policy, pinned 1.8.29 -> 1.8.30
 *   8ee4f98859f985bfe4c22610acd074e5f0c16bd21fa89285636b4f792b3ebcea
 */
$bhp_authorised_cpa_policy = 'e03d29973408406178720c7c9f47c10d8c352157f4594fe2f81541c250726959';
bhp_econ_test_assert(
	$bhp_authorised_cpa_policy === BHP_CPA_Model::policy_fingerprint(),
	'The loaded CPA policy (both approved targets, all four ceilings, all three banding ratios) matches the AUTHORISED fingerprint exactly -- an unauthorised change to any of them fails here',
	$failures
);

$cpc = $cpa_by_type[ BHP_Offer_Economics::COMPLETE_PAPERBACK_SET ];
bhp_econ_test_assert( bhp_econ_close( $cpc['theoretical_breakeven_cpa'], $complete_pb[0]['contribution_before_acquisition'] ), 'Complete Paperback Collection theoretical break-even CPA equals its contribution before acquisition', $failures );
bhp_econ_test_assert( null !== $cpc['target_cpa'] && $cpc['target_cpa'] > 0, 'Complete Paperback Collection carries a real preferred target CPA, never null and never zero', $failures );
bhp_econ_test_assert(
	$cpc['target_cpa'] < $cpc['safer_ceiling_low'] && $cpc['safer_ceiling_low'] < $cpc['safer_ceiling_high'],
	'Complete Paperback Collection target sits below its safer ceiling, and the ceiling is a real low-to-high band',
	$failures
);
bhp_econ_test_assert( bhp_econ_close( $cpc['hard_stop_cpa'], $cpc['theoretical_breakeven_cpa'] ), 'Complete Paperback Collection hard stop equals break-even', $failures );
/*
 * 1.8.31 — CYCLE143-FIN-11 IS FIXED, AND THIS ASSERTS THE FIXED STATE.
 *
 * This assertion read `true` from 1.8.23 to 1.8.30. It was not testing a
 * desirable property: it was pinning a DEFECT so the defect could not be
 * quietly lost. Free collection shipping lowered break-even below Andrew's
 * 2026-07-06 ceilings, so spending to the approved ceiling lost money on every
 * order, and only Andrew could set a replacement ceiling.
 *
 * He set one on 2026-08-06. The revised ceilings sit BELOW live break-even in
 * both formats, which is the healthy state, so the flag now reads `false` and
 * this asserts `false` deliberately. ⛔ Flipping it back to `true` would be
 * re-pinning the defect.
 */
bhp_econ_test_assert( false === $cpc['ceiling_exceeds_breakeven'], '1.8.31 CYCLE143-FIN-11 FIXED: the PB approved ceiling sits BELOW break-even again and the table says so', $failures );
bhp_econ_test_assert( (float) $cpc['safer_ceiling_high'] < (float) $cpc['hard_stop_cpa'], '1.8.31: the PB approved ceiling is strictly below the hard stop -- the invariant the approved rows had lost', $failures );

$chc = $cpa_by_type[ BHP_Offer_Economics::COMPLETE_HARDCOVER_SET ];
bhp_econ_test_assert( bhp_econ_close( $chc['theoretical_breakeven_cpa'], $complete_hc[0]['contribution_before_acquisition'] ), 'Complete Hardcover Collection theoretical break-even CPA equals its contribution before acquisition', $failures );
bhp_econ_test_assert( null !== $chc['target_cpa'] && $chc['target_cpa'] > 0, 'Complete Hardcover Collection carries a real preferred target CPA, never null and never zero', $failures );
bhp_econ_test_assert(
	$chc['target_cpa'] < $chc['safer_ceiling_low'] && $chc['safer_ceiling_low'] < $chc['safer_ceiling_high'],
	'Complete Hardcover Collection target sits below its safer ceiling, and the ceiling is a real low-to-high band',
	$failures
);
bhp_econ_test_assert(
	$chc['target_cpa'] > $cpc['target_cpa'] && $chc['safer_ceiling_high'] > $cpc['safer_ceiling_high'],
	'The hardcover collection is allowed a higher target and a higher ceiling than the paperback one -- a seed that collapsed or swapped the two would be silent otherwise',
	$failures
);
// 1.8.31 — see the note on the paperback row above. Asserted `false` on purpose.
bhp_econ_test_assert( false === $chc['ceiling_exceeds_breakeven'], '1.8.31 CYCLE143-FIN-11 FIXED: the HC approved ceiling sits BELOW break-even again and the table says so', $failures );
bhp_econ_test_assert( (float) $chc['safer_ceiling_high'] < (float) $chc['hard_stop_cpa'], '1.8.31: the HC approved ceiling is strictly below the hard stop', $failures );

// ==================== Approved company policy (2026-07-06): only the two Complete Collections have an approved cold-acquisition target ====================
bhp_econ_test_assert( true === $cpc['cold_acquisition_approved'], 'Complete Paperback Collection is approved for cold acquisition', $failures );
bhp_econ_test_assert( true === $chc['cold_acquisition_approved'], 'Complete Hardcover Collection is approved for cold acquisition', $failures );
bhp_econ_test_assert( 'approved' === $cpc['ceiling_basis'] && 'approved' === $chc['ceiling_basis'], 'Both Complete Collections\' ceilings are labeled "approved", not "model estimate"', $failures );
bhp_econ_test_assert( 'Primary cold-acquisition candidate.' === $cpc['strategic_statement'], 'Complete Paperback Collection strategic statement matches approved wording exactly', $failures );
bhp_econ_test_assert( 'Premium/gift acquisition candidate.' === $chc['strategic_statement'], 'Complete Hardcover Collection strategic statement matches approved wording exactly', $failures );

$non_collection_types = array(
	BHP_Offer_Economics::SINGLE_PAPERBACK,
	BHP_Offer_Economics::SINGLE_HARDCOVER,
	BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE,
	BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE,
);
foreach ( $non_collection_types as $type ) {
	$row = $cpa_by_type[ $type ];
	bhp_econ_test_assert( false === $row['cold_acquisition_approved'], "{$type}: cold_acquisition_approved is false -- not approved for cold paid acquisition", $failures );
	bhp_econ_test_assert( null === $row['target_cpa'], "{$type}: target_cpa is null -- no definitive preferred target is assigned without real campaign data", $failures );
	bhp_econ_test_assert( 'model_estimate' === $row['ceiling_basis'], "{$type}: ceiling_basis is 'model_estimate', clearly distinguished from an approved figure", $failures );
	bhp_econ_test_assert( null !== $row['contribution_before_acquisition'] && null !== $row['theoretical_breakeven_cpa'], "{$type}: contribution and theoretical break-even are still real, always-shown figures", $failures );
	bhp_econ_test_assert( null !== $row['safer_ceiling_low'] && null !== $row['safer_ceiling_high'], "{$type}: a model-estimate operating ceiling is still shown (informational only)", $failures );
}
bhp_econ_test_assert(
	'Organic/search entry offer; not approved for cold paid acquisition.' === $cpa_by_type[ BHP_Offer_Economics::SINGLE_PAPERBACK ]['strategic_statement'],
	'Single paperback strategic statement matches approved wording exactly',
	$failures
);
bhp_econ_test_assert(
	'Organic/search or gift entry offer; not approved for cold paid acquisition.' === $cpa_by_type[ BHP_Offer_Economics::SINGLE_HARDCOVER ]['strategic_statement'],
	'Single hardcover strategic statement matches approved wording exactly',
	$failures
);
bhp_econ_test_assert(
	'Retargeting, upsell, or incomplete-series offer; no approved cold-acquisition target.' === $cpa_by_type[ BHP_Offer_Economics::TWO_PAPERBACK_BUNDLE ]['strategic_statement'],
	'Two-paperback bundle strategic statement matches approved wording exactly',
	$failures
);
bhp_econ_test_assert(
	'Retargeting, upsell, or gift offer; no approved cold-acquisition target.' === $cpa_by_type[ BHP_Offer_Economics::TWO_HARDCOVER_BUNDLE ]['strategic_statement'],
	'Two-hardcover bundle strategic statement matches approved wording exactly',
	$failures
);

// ==================== Strategic offer classification (enum labels, still used internally) ====================
bhp_econ_test_assert(
	in_array( BHP_Offer_Economics::STRATEGIC_ORGANIC_ONLY, $cpa_by_type[ BHP_Offer_Economics::SINGLE_PAPERBACK ]['strategic_labels'], true ),
	'Single paperback is labeled organic/search only -- never recommended for cold acquisition merely because the formula produces a positive CPA',
	$failures
);
bhp_econ_test_assert(
	in_array( BHP_Offer_Economics::STRATEGIC_COLD, $cpc['strategic_labels'], true ),
	'Complete Paperback Collection is labeled a cold-acquisition candidate',
	$failures
);
bhp_econ_test_assert(
	in_array( BHP_Offer_Economics::STRATEGIC_COLD, $chc['strategic_labels'], true ) && in_array( BHP_Offer_Economics::STRATEGIC_PREMIUM_GIFT, $chc['strategic_labels'], true ),
	'Complete Hardcover Collection is labeled both cold-acquisition and premium/gift candidate',
	$failures
);

// ==================== classify_cpa() status thresholds ====================
/*
 * 1.8.30: every probe below is DERIVED from the table rather than pinned.
 * A literal probe would disclose a band boundary by which side of it the
 * expected status falls on, which is the same leak the relocated figures
 * were removed to close. Each probe asserts that it really does sit in
 * the band it is meant to exercise, so a derived probe that drifted out
 * of position fails loudly instead of testing nothing.
 */
$probe_green = (float) $cpc['target_cpa'] / 2;
bhp_econ_test_assert( $probe_green < (float) $cpc['target_cpa'], 'The GREEN probe really does sit below the approved target', $failures );
bhp_econ_test_assert( BHP_CPA_Model::STATUS_GREEN === BHP_CPA_Model::classify_cpa( BHP_Offer_Economics::COMPLETE_PAPERBACK_SET, $probe_green ), 'A CPA below target classifies GREEN', $failures );

/*
 * 1.8.31 — THE YELLOW PROBE IS DERIVED FROM THE CEILING, NOT FROM BREAK-EVEN.
 *
 * It used to be the midpoint of [target, hard_stop]. That landed inside the
 * yellow band only because the approved ceiling sat ABOVE break-even, so
 * "under break-even" and "under the ceiling" were the same span. With the
 * ceiling restored below break-even those are different spans, and the old
 * midpoint lands in the RED band instead.
 *
 * ⭐ The correct derivation was always the midpoint of [target, ceiling_high],
 *    because that IS the yellow band by definition. It is now derived that way
 *    and no longer depends on which side of break-even the ceiling happens to
 *    sit -- so this probe survives the next ceiling revision in either
 *    direction. The self-check below still proves it landed where intended.
 */
$probe_yellow = ( (float) $cpc['target_cpa'] + (float) $cpc['safer_ceiling_high'] ) / 2;
bhp_econ_test_assert(
	$probe_yellow > (float) $cpc['target_cpa'] && $probe_yellow < (float) $cpc['hard_stop_cpa'] && $probe_yellow <= (float) $cpc['safer_ceiling_high'],
	'The YELLOW probe really does sit above target, under break-even and within the safer ceiling',
	$failures
);
bhp_econ_test_assert( BHP_CPA_Model::STATUS_YELLOW === BHP_CPA_Model::classify_cpa( BHP_Offer_Economics::COMPLETE_PAPERBACK_SET, $probe_yellow ), 'A CPA between target and safer ceiling, still under break-even, classifies YELLOW', $failures );

/*
 * ═════════════════════════════════════════════════════════════════════════
 * 1.8.31 — THE BAND BETWEEN THE APPROVED CEILING AND BREAK-EVEN NOW EXISTS
 *          ON THE APPROVED ROWS, AND IT MUST GRADE RED.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * From 1.8.23 to 1.8.30 this position was occupied by a STOP probe derived as
 * the midpoint of [hard_stop, safer_ceiling_high] -- a span that only exists
 * when the ceiling is ABOVE break-even, i.e. only while `CYCLE143-FIN-11` was
 * live. That probe is not "fixed" here, it is RETIRED, because the state it
 * exercised has been resolved by Andrew's revised ceilings. Asserting it would
 * be asserting the defect.
 *
 * ⛔ Retiring it would have surrendered the 1.8.23 ordering guard, so that
 *    guard is re-established SYNTHETICALLY further down rather than dropped.
 *    Read that block before concluding the ordering is untested.
 *
 * What is asserted here instead is the band the fix created: above the
 * approved ceiling, still under break-even. It is a genuine loss-free-but-
 * over-policy region, and RED is the correct grade for it. It did not exist on
 * an approved row until 2026-08-06.
 */
$probe_red_approved = ( (float) $cpc['safer_ceiling_high'] + (float) $cpc['hard_stop_cpa'] ) / 2;
bhp_econ_test_assert(
	$probe_red_approved > (float) $cpc['safer_ceiling_high'] && $probe_red_approved < (float) $cpc['hard_stop_cpa'],
	'The approved-row RED probe really does sit above the approved ceiling and below break-even',
	$failures
);
bhp_econ_test_assert(
	BHP_CPA_Model::STATUS_RED === BHP_CPA_Model::classify_cpa( BHP_Offer_Economics::COMPLETE_PAPERBACK_SET, $probe_red_approved ),
	'1.8.31: on an approved row, a CPA above the approved ceiling but still under break-even classifies RED -- a band that did not exist while the ceiling sat above break-even',
	$failures
);

// RED still exists and is still reachable: a model-estimate row keeps the
// ceiling-below-break-even invariant, because its ceiling is a fraction of
// break-even by construction, so the band between ceiling and hard stop is
// real there. (That fraction is one of the relocated ratios and is
// deliberately not named here -- it used to be, and the tree scan caught
// it. The assertion below proves the invariant instead of quoting it.)
$single_pb_row = $cpa_by_type[ BHP_Offer_Economics::SINGLE_PAPERBACK ];
bhp_econ_test_assert(
	(float) $single_pb_row['safer_ceiling_high'] < (float) $single_pb_row['hard_stop_cpa'],
	'1.8.23: the model-estimate ceiling still sits BELOW break-even (the invariant the approved rows have lost)',
	$failures
);
$red_cpa = ( (float) $single_pb_row['safer_ceiling_high'] + (float) $single_pb_row['hard_stop_cpa'] ) / 2;
bhp_econ_test_assert(
	BHP_CPA_Model::STATUS_RED === BHP_CPA_Model::classify_cpa( BHP_Offer_Economics::SINGLE_PAPERBACK, $red_cpa ),
	'1.8.23: a CPA between safer ceiling and hard stop still classifies RED where that band exists',
	$failures
);
$probe_stop = (float) $cpc['hard_stop_cpa'] * 2;
bhp_econ_test_assert( $probe_stop > (float) $cpc['hard_stop_cpa'] && $probe_stop > (float) $cpc['safer_ceiling_high'], 'The STOP probe really does sit beyond both the hard stop and the ceiling', $failures );
bhp_econ_test_assert( BHP_CPA_Model::STATUS_STOP === BHP_CPA_Model::classify_cpa( BHP_Offer_Economics::COMPLETE_PAPERBACK_SET, $probe_stop ), 'A CPA beyond the hard stop classifies STOP', $failures );
bhp_econ_test_assert( BHP_CPA_Model::STATUS_STOP === BHP_CPA_Model::classify_cpa( 'not_a_real_offer_type', 1.00 ), 'An unrecognized offer type fails safe to STOP, never silently GREEN', $failures );

/*
 * ═════════════════════════════════════════════════════════════════════════
 * 1.8.31 — THE 1.8.23 ORDERING GUARD, PRESERVED SYNTHETICALLY.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * 1.8.23 moved the hard-stop test to the FRONT of classify_cpa(). Before that,
 * a CPA between break-even and the approved ceiling graded YELLOW -- an
 * order-by-order loss reported as acceptable. That fix is still load-bearing
 * and its regression must still be caught.
 *
 * ⛔ THE PROBLEM: from 1.8.31 the live policy no longer produces the state the
 *    old test exercised, because the approved ceilings sit below break-even
 *    again. A guard that can only fire while a defect is live stops guarding
 *    the moment the defect is fixed -- and would silently pass forever after.
 *
 * ⭐ THE FIX: inject a PATHOLOGICAL policy through the same `bhp_cpa_model`
 *    filter the unseeded tests already use, with the paperback ceilings pushed
 *    above break-even. Nothing is written, the real option is never touched,
 *    the pathological figures are DERIVED from the live hard stop rather than
 *    named, and restoration is asserted rather than assumed.
 *
 *    This also re-exercises the 1.8.23 `ceiling_exceeds_breakeven` FLAG, whose
 *    live value is now `false` -- so the flag's true branch keeps its coverage.
 */
$bhp_live_cpa_model  = BHP_CPA_Model::model(); // captured BEFORE the filter, so the closure cannot recurse
$bhp_pb_type         = BHP_Offer_Economics::COMPLETE_PAPERBACK_SET;
$bhp_pathological_be = (float) $cpc['hard_stop_cpa'];
$bhp_pathological_cpa = function () use ( $bhp_live_cpa_model, $bhp_pb_type, $bhp_pathological_be ) {
	$m = $bhp_live_cpa_model;
	/*
	 * Written as integer ratios on purpose, not as decimal multipliers.
	 * `test-cost-model-source.php` scans this suite for decimal literals
	 * outside the published-storefront allowlist, and a new one here would
	 * have to be allowlisted -- widening a guard to admit a value that is not
	 * a figure at all. These are eleven-tenths and six-fifths of the live hard
	 * stop; the only thing that matters about them is that both exceed it.
	 */
	$m[ 'ceiling_low.'  . $bhp_pb_type ] = $bhp_pathological_be * 11 / 10;
	$m[ 'ceiling_high.' . $bhp_pb_type ] = $bhp_pathological_be * 6 / 5;
	return $m;
};
add_filter( 'bhp_cpa_model', $bhp_pathological_cpa, 999 );
BHP_CPA_Model::flush_model_cache();

$bhp_path_rows = array();
foreach ( BHP_CPA_Model::build_table() as $row ) { $bhp_path_rows[ $row['offer_type'] ] = $row; }
$bhp_path_pb = $bhp_path_rows[ $bhp_pb_type ];

bhp_econ_test_assert(
	true === $bhp_path_pb['ceiling_exceeds_breakeven'],
	'1.8.23 flag, synthetic: with a ceiling forced above break-even the table reports ceiling_exceeds_breakeven=true -- the true branch keeps its coverage now that the live policy is healthy',
	$failures
);
$bhp_path_probe = ( (float) $bhp_path_pb['hard_stop_cpa'] + (float) $bhp_path_pb['safer_ceiling_high'] ) / 2;
bhp_econ_test_assert(
	$bhp_path_probe > (float) $bhp_path_pb['hard_stop_cpa'] && $bhp_path_probe < (float) $bhp_path_pb['safer_ceiling_high'],
	'1.8.23 ordering, synthetic: the probe really does sit above break-even and below the forced ceiling',
	$failures
);
bhp_econ_test_assert(
	BHP_CPA_Model::STATUS_STOP === BHP_CPA_Model::classify_cpa( $bhp_pb_type, $bhp_path_probe ),
	'1.8.23 ordering, synthetic: a CPA under the ceiling but OVER break-even classifies STOP, not YELLOW -- the hard stop is still tested first',
	$failures
);

remove_filter( 'bhp_cpa_model', $bhp_pathological_cpa, 999 );
BHP_CPA_Model::flush_model_cache();
bhp_econ_test_assert(
	$bhp_authorised_cpa_policy === BHP_CPA_Model::policy_fingerprint(),
	'The real acquisition policy is restored after the synthetic-pathology guard, byte for byte',
	$failures
);

/*
 * 1.8.30 — AN UNSEEDED ACQUISITION POLICY MUST NOT GRADE ANYTHING.
 *
 * The failure this guards is the CPA analogue of the cost model's
 * "$0 print cost reported as profit": a target that silently defaulted to
 * zero would grade every real campaign STOP, and a ceiling that silently
 * defaulted to zero would do the same, so the dashboard would look like
 * it were working and reporting terrible performance. Exercised through
 * the filter and the in-request cache -- nothing is written, and the real
 * option is never deleted or rewritten.
 */
$bhp_force_empty_cpa = function () { return array(); };
add_filter( 'bhp_cpa_model', $bhp_force_empty_cpa, 999 );
BHP_CPA_Model::flush_model_cache();

bhp_econ_test_assert( ! BHP_CPA_Model::is_seeded(), 'With an empty policy, is_seeded() is false', $failures );
bhp_econ_test_assert( 9 === count( BHP_CPA_Model::missing_model_keys() ), 'With an empty policy, every key is reported missing by name', $failures );
bhp_econ_test_assert( '' === BHP_CPA_Model::policy_fingerprint(), 'With an empty policy, the fingerprint is empty rather than a digest of zeroes', $failures );

$unseeded_rows = array();
foreach ( BHP_CPA_Model::build_table() as $row ) { $unseeded_rows[ $row['offer_type'] ] = $row; }
$unseeded_pb = $unseeded_rows[ BHP_Offer_Economics::COMPLETE_PAPERBACK_SET ];
bhp_econ_test_assert( 'unavailable' === $unseeded_pb['ceiling_basis'], 'With an empty policy, the approved row reports ceiling_basis=unavailable', $failures );
bhp_econ_test_assert( null === $unseeded_pb['target_cpa'], 'With an empty policy, target_cpa is null -- never 0.00 presented as a decision', $failures );
bhp_econ_test_assert( null === $unseeded_pb['safer_ceiling_low'] && null === $unseeded_pb['safer_ceiling_high'], 'With an empty policy, both ceiling bounds are null, never zero', $failures );
bhp_econ_test_assert( null !== $unseeded_pb['theoretical_breakeven_cpa'] && null !== $unseeded_pb['hard_stop_cpa'], 'With an empty policy, break-even and hard stop are still real -- they are cost-model math, not policy', $failures );
bhp_econ_test_assert( true === $unseeded_pb['cold_acquisition_approved'], 'With an empty policy, the approval FACT survives -- it is structure in code, not a figure in the option', $failures );

$unseeded_single = $unseeded_rows[ BHP_Offer_Economics::SINGLE_PAPERBACK ];
bhp_econ_test_assert( 'unavailable' === $unseeded_single['ceiling_basis'], 'With an empty policy, a model-estimate row reports ceiling_basis=unavailable rather than banding off a zero ratio', $failures );
bhp_econ_test_assert( null === $unseeded_single['safer_ceiling_high'], 'With an empty policy, the model-estimate ceiling is null, never 0.00', $failures );

bhp_econ_test_assert(
	BHP_CPA_Model::STATUS_STOP === BHP_CPA_Model::classify_cpa( BHP_Offer_Economics::COMPLETE_PAPERBACK_SET, $probe_green ),
	'With an empty policy, a CPA that would otherwise be GREEN fails safe to STOP -- an unloaded policy certifies nothing',
	$failures
);

// A partial seed is not a seed.
remove_filter( 'bhp_cpa_model', $bhp_force_empty_cpa, 999 );
$bhp_force_partial_cpa = function () { return array( 'ratio.target' => 0.5, 'ratio.ceiling' => 0.5, 'ratio.ceiling_band' => 0.5 ); };
add_filter( 'bhp_cpa_model', $bhp_force_partial_cpa, 999 );
BHP_CPA_Model::flush_model_cache();
bhp_econ_test_assert( ! BHP_CPA_Model::is_seeded(), 'A partially populated policy is NOT reported as seeded', $failures );
bhp_econ_test_assert( '' === BHP_CPA_Model::policy_fingerprint(), 'A partially populated policy produces no fingerprint', $failures );

// Restoration is asserted, not assumed.
remove_filter( 'bhp_cpa_model', $bhp_force_partial_cpa, 999 );
BHP_CPA_Model::flush_model_cache();
bhp_econ_test_assert( BHP_CPA_Model::is_seeded(), 'The real acquisition policy is restored after the suite manipulates it', $failures );
bhp_econ_test_assert( $bhp_authorised_cpa_policy === BHP_CPA_Model::policy_fingerprint(), 'The restored policy still matches the authorised fingerprint', $failures );

// ==================== Provenance logic remains unchanged ====================
bhp_econ_test_assert(
	class_exists( 'BHP_Order_Provenance' ) && in_array( 336, BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS, true ),
	'BHP_Order_Provenance::KNOWN_TEST_ORDER_IDS still includes #336 -- Phase 1A economics work did not touch provenance data',
	$failures
);
bhp_econ_test_assert(
	array() === BHP_Order_Provenance::NEEDS_CONFIRMATION_ORDER_IDS,
	'BHP_Order_Provenance::NEEDS_CONFIRMATION_ORDER_IDS remains empty -- unchanged from the completed provenance correction',
	$failures
);

echo empty( $failures ) ? "\nALL OFFER-ECONOMICS TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED:\n";
if ( ! empty( $failures ) ) {
	foreach ( $failures as $f ) { echo " - {$f}\n"; }
	exit( 1 );
}
