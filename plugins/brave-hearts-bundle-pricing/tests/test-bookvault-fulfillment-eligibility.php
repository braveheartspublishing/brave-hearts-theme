<?php
/**
 * Brave Hearts Dashboard — Bookvault fulfillment-eligibility test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-bookvault-fulfillment-eligibility.php --user=1
 *
 * Covers the 2026-07-06 correction to the Bookvault routing denominator:
 * being paid and catalog-eligible no longer alone means an order counts
 * toward "expected to fulfill via Bookvault" -- refunded, cancelled,
 * Bookvault-excluded, and legacy/pre-integration orders must not inflate
 * "orders needing attention" or deflate the routing success rate. See
 * docs/bookvault-chronology.md for the full real-order evidence
 * (direct reconciliation against the Bookvault portal found only 3 real
 * orders existed there, not the 6 the dashboard previously counted).
 *
 * Uses in-memory (unsaved) WC_Order objects where a real order object is
 * needed -- get_id() returns 0 and wc_get_order_notes() safely returns an
 * empty array for that, so no database row is read or written by this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_fe_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// ==================== Pure decision function: determine_bookvault_fulfillment_expectation() ====================
// Priority ordering matters -- test it explicitly, not just the individual branches.

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'routed', 'full', 'processing', true );
bhp_fe_assert( true === $d['expected'] && 'ok' === $d['reason'], 'A real Bookvault record (routed) is expected/ok even if the order was LATER fully refunded, cancelled, or is legacy -- the historical routing record is preserved (Phase 5)', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'excluded', 'none', 'processing', false );
bhp_fe_assert( false === $d['expected'] && 'excluded_by_bookvault' === $d['reason'], 'Bookvault itself declining the order (excluded) is never expected/actionable -- order #351\'s real scenario', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'unknown', 'full', 'processing', false );
bhp_fe_assert( false === $d['expected'] && 'refunded' === $d['reason'], 'A fully refunded order with no Bookvault record is not expected to fulfill -- order #317\'s real scenario', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'unknown', 'partial', 'processing', false );
bhp_fe_assert( true === $d['expected'] && 'ok' === $d['reason'], 'A PARTIALLY refunded order remains fulfillable/expected -- only a FULL refund removes fulfillment expectation', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'unknown', 'none', 'cancelled', false );
bhp_fe_assert( false === $d['expected'] && 'cancelled' === $d['reason'], 'A cancelled order is not expected to fulfill', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'unknown', 'none', 'processing', true );
bhp_fe_assert( false === $d['expected'] && 'legacy_pre_integration' === $d['reason'], 'An order paid before Bookvault integration went live is legacy/excluded, not an overdue failure -- orders #336/#322\'s real scenario', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'unknown', 'none', 'processing', false );
bhp_fe_assert( true === $d['expected'] && 'ok' === $d['reason'], 'A normal paid, non-refunded, non-cancelled, non-legacy order with no Bookvault activity yet is still expected (pending or actionable, decided elsewhere by is_routing_overdue())', $failures );

$d = BHP_Order_Metrics::determine_bookvault_fulfillment_expectation( 'failed', 'none', 'processing', false );
bhp_fe_assert( true === $d['expected'] && 'ok' === $d['reason'], 'A genuine technical failure (not an exclusion) stays in the expected/actionable denominator -- this is what should drive "needs review", unlike an exclusion', $failures );

// ==================== VALID_PAID_STATUSES upstream filter ====================
// Cancelled and failed orders never reach compute_kpis()'s per-order loop
// at all -- they're excluded by the wc_get_orders() status filter in
// get_valid_paid_orders(), not by any per-order logic. Documented here so
// the "cancelled"/"failed" exclusion reasons above are understood as a
// defensive/complete decision table, not proof those states are reachable
// through the live order-loop today.
bhp_fe_assert(
	! in_array( 'cancelled', BHP_Order_Metrics::VALID_PAID_STATUSES, true ),
	'VALID_PAID_STATUSES excludes cancelled orders upstream (get_valid_paid_orders never returns them)',
	$failures
);
bhp_fe_assert(
	! in_array( 'failed', BHP_Order_Metrics::VALID_PAID_STATUSES, true ),
	'VALID_PAID_STATUSES excludes failed-payment orders upstream (counted separately via get_failed_orders_count(), never in the Bookvault denominator)',
	$failures
);

// ==================== classify_note_content(): excluded vs. genuine failure ====================
// Real, full note text observed on order #351 -- NOT a technical failure,
// Bookvault intentionally declining to process the order.
$real_excluded_note = 'Failed to read line_items: Notice - The Bookvault plugin scans all incoming orders to identify those specifically intended for Bookvault to fulfill. Based on your current configuration, this order does not indicate Bookvault as the selected fulfillment service. As a result, it will not be processed by Bookvault.';
$event = BHP_Bookvault_Status::classify_note_content( $real_excluded_note );
bhp_fe_assert( null !== $event && 'excluded' === $event['type'], 'The real order #351 note text classifies as "excluded", not "failed", despite starting with "Failed to read line_items:"', $failures );

// A hypothetical genuine technical failure note (same prefix, different
// body) must still classify as a real failure, not swallowed by the
// excluded-pattern check.
$hypothetical_real_failure = 'Failed to read line_items: Fatal - malformed cart data, unable to parse product IDs.';
$event = BHP_Bookvault_Status::classify_note_content( $hypothetical_real_failure );
bhp_fe_assert( null !== $event && 'failure' === $event['type'], 'A genuinely different "Failed to read line_items:" message (no "not selected as fulfillment service" text) still classifies as a real failure', $failures );

$r = BHP_Bookvault_Status::get_status_from_events( array(
	array( 'type' => 'excluded', 'time' => null, 'timestamp' => null ),
), null );
bhp_fe_assert( 'excluded' === $r['status'], 'get_status_from_events() with only an excluded event reports status=excluded, never failed', $failures );
bhp_fe_assert( 0 === $r['failure_count'], 'An excluded event never increments failure_count', $failures );

// Chronology still applies: a later real success after an earlier
// exclusion note means the order WAS eventually created (e.g. Andrew
// fixed the configuration and it routed on a retry).
$r = BHP_Bookvault_Status::get_status_from_events( array(
	array( 'type' => 'excluded', 'time' => null, 'timestamp' => 100 ),
	array( 'type' => 'success', 'state' => 'Active', 'ref' => 'BV999', 'time' => null, 'timestamp' => 200 ),
), 50 );
bhp_fe_assert( 'routed' === $r['status'], 'Excluded followed by a later real success -> routed (latest event wins, same chronology rule as failure/success)', $failures );

// ==================== BVRef postmeta: has_bookvault_record() / get_bvref_meta() ====================
$order_with_bvref = new WC_Order();
$order_with_bvref->add_meta_data( 'BVRef', '2796848' );
bhp_fe_assert( '2796848' === BHP_Bookvault_Status::get_bvref_meta( $order_with_bvref ), 'get_bvref_meta() reads the BVRef postmeta value directly', $failures );
bhp_fe_assert( true === BHP_Bookvault_Status::has_bookvault_record( $order_with_bvref ), 'has_bookvault_record() is true when BVRef meta is present', $failures );

$order_without_bvref = new WC_Order();
bhp_fe_assert( '' === BHP_Bookvault_Status::get_bvref_meta( $order_without_bvref ), 'get_bvref_meta() returns empty string when no BVRef meta exists', $failures );
bhp_fe_assert( false === BHP_Bookvault_Status::has_bookvault_record( $order_without_bvref ), 'has_bookvault_record() is false when no BVRef meta exists', $failures );

// ==================== get_status(): BVRef outranks a stale/missing note ====================
// An order with BVRef set but (for whatever reason) no matching success
// note in its history must still report 'routed' -- the direct Bookvault
// signal overrides note-text inference, never the other way around
// (Phase 4 source-of-truth hierarchy).
//
// 2026-07-06 fix: this fixture previously left the order UNSAVED, so
// get_id() returned 0 and get_status() queried
// wc_get_order_notes(['order_id' => 0]). On staging that happened to
// return an empty array, but on production the same query returned
// notes from EVERY order on the site -- confirmed by direct diagnosis
// (order_id => 0 is apparently treated as "no post filter" by the
// underlying comment query on this WooCommerce/HPOS configuration,
// rather than "match nothing", which is not guaranteed/documented
// behavior and differed between the two environments). That leak fed a
// real, unrelated success note (order #355's) into the chronology
// engine, which set status='routed' from a genuine note BEFORE the
// BVRef override could ever run -- masking whether the override itself
// works and making the "bookvault_ref falls back" assertion fail with
// the wrong (leaked) reference on production while accidentally still
// reporting 'routed' for the unrelated reason on the first assertion.
// A test must never depend on an environment-specific interpretation
// of a boundary value like order_id=0 to mean "no rows exist".
//
// Fix: force a definite, non-zero, realistically-impossible order ID
// with set_id() (still never persisted -- no save() call, so nothing
// is written to the database) so wc_get_order_notes() runs a REAL
// filtered query in every environment and deterministically finds zero
// notes, because no real order will ever have this ID.
$order_bvref_no_notes = new WC_Order();
$order_bvref_no_notes->set_id( 999999901 ); // sentinel ID, never saved, guaranteed not to collide with a real order
$order_bvref_no_notes->add_meta_data( 'BVRef', '2796764' );
$status = BHP_Bookvault_Status::get_status( $order_bvref_no_notes );
bhp_fe_assert( 'routed' === $status['status'], 'get_status() reports "routed" from BVRef alone even when zero order notes are found -- direct Bookvault evidence overrides missing/stale note inference', $failures );
bhp_fe_assert( '2796764' === $status['bookvault_ref'], 'get_status() bookvault_ref falls back to the BVRef meta value when no success note supplied one', $failures );

// ==================== is_legacy_pre_integration() ====================
$legacy_order = new WC_Order();
$legacy_order->set_date_paid( ( new WC_DateTime( '2026-07-02 21:24:41' ) ) );
bhp_fe_assert( true === BHP_Bookvault_Status::is_legacy_pre_integration( $legacy_order ), 'An order paid 2026-07-02 (before the confirmed integration go-live window) is legacy/pre-integration', $failures );

$post_integration_order = new WC_Order();
$post_integration_order->set_date_paid( ( new WC_DateTime( '2026-07-05 06:30:07' ) ) );
bhp_fe_assert( false === BHP_Bookvault_Status::is_legacy_pre_integration( $post_integration_order ), 'An order paid 2026-07-05 (after the confirmed integration go-live window) is NOT legacy/pre-integration', $failures );

$unpaid_order = new WC_Order();
bhp_fe_assert( false === BHP_Bookvault_Status::is_legacy_pre_integration( $unpaid_order ), 'An order with no date_paid is not flagged legacy (nothing to compare -- not a routing question yet)', $failures );

// ==================== Offer classifier: Legacy / pre-catalog ====================
$LEGACY_ID = 12; // real, confirmed legacy Mariana Trench Paperback product ID (no SKU, pre-variation)
$UNKNOWN_ID = 424242;
$PB_EVEREST = 15; // real current-catalog product ID

$r = BHP_Offer_Classifier::classify_items( array( array( 'id' => $LEGACY_ID, 'quantity' => 1 ) ) );
bhp_fe_assert( BHP_Offer_Classifier::LEGACY_PRECATALOG === $r['offer_type'], 'An order using ONLY the known legacy product ID classifies as Legacy / pre-catalog, not "Other / needs review"', $failures );

$r = BHP_Offer_Classifier::classify_items( array( array( 'id' => $LEGACY_ID, 'quantity' => 1 ), array( 'id' => $UNKNOWN_ID, 'quantity' => 1 ) ) );
bhp_fe_assert( BHP_Offer_Classifier::OTHER === $r['offer_type'], 'An order mixing the known legacy ID with a genuinely unrecognized product ID stays "Other / needs review" -- legacy status is not assumed for unknown IDs', $failures );

$r = BHP_Offer_Classifier::classify_items( array( array( 'id' => $PB_EVEREST, 'quantity' => 1 ), array( 'id' => $LEGACY_ID, 'quantity' => 1 ) ) );
bhp_fe_assert( BHP_Offer_Classifier::SINGLE_PAPERBACK === $r['offer_type'], 'An order with one real catalog item plus a legacy non-catalog item still classifies normally by its catalog content (legacy units are extra context, not a blocker)', $failures );

echo empty( $failures ) ? "\nALL BOOKVAULT FULFILLMENT-ELIGIBILITY TESTS PASSED\n" : "\n" . count( $failures ) . " TEST(S) FAILED\n";
if ( ! empty( $failures ) ) {
	exit( 1 );
}
