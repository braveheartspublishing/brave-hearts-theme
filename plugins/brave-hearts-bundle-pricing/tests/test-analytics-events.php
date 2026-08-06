<?php
/**
 * Brave Hearts Bundle Pricing — Analytics Phase 1B event test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-analytics-events.php --user=1
 *
 * Covers bhp_bundle_ga4_item() (pure catalog->GA4-item mapping) and the
 * provenance-based purchase/refund exclusion rule reused from
 * BHP_Order_Provenance. Uses in-memory (unsaved) WC_Order objects with a
 * sentinel ID via set_id() -- same safe pattern already used in
 * test-bookvault-fulfillment-eligibility.php -- so no real order is read
 * or written by this file. Full end-to-end purchase-page dedup (the
 * order-meta round trip through a real saved order) is verified live on
 * staging per docs/analytics-validation.md, not here, since it requires
 * an actual WooCommerce order to persist meta against.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_ae_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

// ==================== bhp_bundle_ga4_item(): schema shape ====================
$item = bhp_bundle_ga4_item( 'paperback', 'mariana', 2 );
bhp_ae_test_assert( null !== $item, 'bhp_bundle_ga4_item() returns a value for a real catalog title', $failures );
bhp_ae_test_assert( 'Brave Hearts Publishing' === $item['item_brand'], 'GA4 item_brand is always "Brave Hearts Publishing" per Phase 1B recommended values', $failures );
bhp_ae_test_assert( "Children's Books" === $item['item_category'], 'GA4 item_category is always "Children\'s Books" per Phase 1B recommended values', $failures );
bhp_ae_test_assert( 'Paperback' === $item['item_category2'], 'GA4 item_category2 reflects the format (Paperback) for a paperback title', $failures );
bhp_ae_test_assert( 2 === $item['quantity'], 'Quantity passed through correctly', $failures );
bhp_ae_test_assert( is_string( $item['item_id'] ) && '' !== $item['item_id'], 'item_id is a non-empty string (GA4 expects string IDs)', $failures );
bhp_ae_test_assert( 11.99 === $item['price'], 'Paperback price matches the approved catalog price ($11.99)', $failures );

$hc_item = bhp_bundle_ga4_item( 'hardcover', 'everest', 1 );
bhp_ae_test_assert( 'Hardcover' === $hc_item['item_category2'], 'GA4 item_category2 reflects the format (Hardcover) for a hardcover title', $failures );
bhp_ae_test_assert( 17.99 === $hc_item['price'], 'Hardcover price matches the approved catalog price ($17.99)', $failures );

$unknown_item = bhp_bundle_ga4_item( 'paperback', 'not_a_real_title', 1 );
bhp_ae_test_assert( null === $unknown_item, 'bhp_bundle_ga4_item() returns null for an unrecognized title, never a fabricated item', $failures );

$item_with_discount = bhp_bundle_ga4_item( 'paperback', 'mariana', 1, 1.99 );
bhp_ae_test_assert( 1.99 === $item_with_discount['discount'], 'A supplied per-unit discount is included on the item when present', $failures );
$item_without_discount = bhp_bundle_ga4_item( 'paperback', 'mariana', 1, null );
bhp_ae_test_assert( ! array_key_exists( 'discount', $item_without_discount ), 'No discount key is added when no discount applies (never a fabricated $0.00 discount)', $failures );

$item_with_list = bhp_bundle_ga4_item( 'paperback', 'mariana', 1, null, 'Shop' );
bhp_ae_test_assert( 'Shop' === $item_with_list['item_list_name'], 'item_list_name is included when supplied', $failures );

// ==================== Purchase/refund provenance exclusion ====================
// Reuses the exact classifier the executive dashboard already relies on
// -- a synthetic, unsaved order with a KNOWN internal test ID must be
// excluded; one with a fresh, never-used ID must NOT be excluded.
if ( class_exists( 'BHP_Order_Provenance' ) ) {
	$known_test_order = new WC_Order();
	$known_test_order->set_id( 351 ); // documented internal Bookvault verification test order
	bhp_ae_test_assert(
		false === BHP_Order_Provenance::is_executive_eligible( $known_test_order ),
		'A known internal/test order ID (#351) is excluded from executive eligibility -- purchase/refund events must not fire for it',
		$failures
	);

	$genuine_order = new WC_Order();
	$genuine_order->set_id( 999999902 ); // sentinel: guaranteed never a real or known-test order
	bhp_ae_test_assert(
		true === BHP_Order_Provenance::is_executive_eligible( $genuine_order ),
		'An order with no known-test classification is treated as executive-eligible by default -- purchase events fire for genuine customer orders',
		$failures
	);
} else {
	bhp_ae_test_assert( false, 'BHP_Order_Provenance class must be loadable for purchase/refund exclusion to work at all', $failures );
}

// ==================== Purchase-fired meta constant is stable ====================
bhp_ae_test_assert(
	'_bhp_purchase_event_fired' === BHP_PURCHASE_EVENT_FIRED_META,
	'The purchase-dedup order-meta key is the documented, stable value (a change here would silently un-dedupe every previously-fired order)',
	$failures
);

// ==================== view_item_list / select_item (Phase 1B correction pass) ====================

// bhp_bundle_ga4_item_from_product(): a real catalog product builds a
// full GA4 item with the correct list name and 1-based index.
$product_15 = wc_get_product( 15 ); // Mount Everest paperback
bhp_ae_test_assert( $product_15 instanceof WC_Product, 'Test fixture: product 15 (Mount Everest paperback) exists on this store', $failures );

if ( $product_15 ) {
	$list_item = bhp_bundle_ga4_item_from_product( $product_15, 3, 'Shop' );
	bhp_ae_test_assert( 3 === $list_item['index'], 'bhp_bundle_ga4_item_from_product() includes the supplied 1-based position as `index`', $failures );
	bhp_ae_test_assert( 'Shop' === $list_item['item_list_name'], 'bhp_bundle_ga4_item_from_product() includes the supplied item_list_name', $failures );
	bhp_ae_test_assert( 'Brave Hearts Publishing' === $list_item['item_brand'], 'A recognized catalog product still gets the standard item_brand via the catalog match path', $failures );

	$list_item_no_index = bhp_bundle_ga4_item_from_product( $product_15 );
	bhp_ae_test_assert( ! array_key_exists( 'index', $list_item_no_index ), 'No `index` key is added when no position is supplied (never a fabricated 0)', $failures );
}

// bhp_bundle_list_item_registry(): the accumulator used to build
// view_item_list's items[] resets cleanly per list_id and never leaks
// entries from a previous list into a new one -- proven directly here
// since the real hooks (woocommerce_before_shop_loop /
// woocommerce_before_shop_loop_item) require a live shop-loop query this
// unit test does not depend on.
bhp_bundle_list_item_registry( 'test_list_a', null, true ); // reset
bhp_bundle_list_item_registry( 'test_list_a', array( 'item_id' => '1' ) );
bhp_bundle_list_item_registry( 'test_list_a', array( 'item_id' => '2' ) );
$list_a = bhp_bundle_list_item_registry( 'test_list_a' );
bhp_ae_test_assert( 2 === count( $list_a ), 'The list-item registry correctly accumulates multiple items for the same list_id', $failures );

bhp_bundle_list_item_registry( 'test_list_b', null, true ); // a second, independent list_id
bhp_bundle_list_item_registry( 'test_list_b', array( 'item_id' => '99' ) );
$list_b = bhp_bundle_list_item_registry( 'test_list_b' );
bhp_ae_test_assert( 1 === count( $list_b ) && '99' === $list_b[0]['item_id'], 'A second list_id is tracked independently -- no cross-contamination between two tracked lists', $failures );
bhp_ae_test_assert( 2 === count( bhp_bundle_list_item_registry( 'test_list_a' ) ), 'Populating a second list_id does not affect the first list_id\'s already-accumulated items', $failures );

bhp_bundle_list_item_registry( 'test_list_a', null, true ); // reset again
bhp_ae_test_assert( 0 === count( bhp_bundle_list_item_registry( 'test_list_a' ) ), 'Resetting a list_id clears it back to empty (proves woocommerce_before_shop_loop\'s reset call works correctly across multiple archive-page loads in the same request)', $failures );

// ==================== add_shipping_info / add_payment_info: enqueue is checkout-only ====================
// The JS itself (bhp-checkout-events.js) is validated live in the browser
// (see docs/analytics-validation.md) -- no PHP-testable logic exists in
// that file itself. What IS testable here is the enqueue condition: the
// script must never load on a non-checkout page (Phase 6 "work with the
// actual checkout implementation" + general script-bloat hygiene).
bhp_ae_test_assert(
	function_exists( 'bhp_bundle_drawer_assets' ),
	'bhp_bundle_drawer_assets() (which conditionally enqueues bhp-checkout-events.js) is defined and loadable',
	$failures
);

// ==================== bhp_bundle_print_datalayer_push(): GA4 ecommerce nesting ====================
// GTM's GA4 Event tags use "Send Ecommerce data" / "Data source: Data
// Layer", which only auto-reads a nested `ecommerce` object -- it never
// picks up currency/value/items/etc. sat flat at the top level. Found via
// live GA4 DebugView on 2026-07-10: `view_item_list` was reaching GA4
// with item_list_id/item_list_name (explicit Data Layer Variables) but
// with NO items array at all, because it was never nested. Fixed by
// mirroring the known ecommerce-scoped keys into `ecommerce` at push
// time -- additive, not a move, so existing {{DLV - item_list_id}}-style
// variables reading the flat top-level key are unaffected.
// Returns EVERY dataLayer.push(...) call the helper printed, in order --
// needed because an ecommerce event now prints two: a {"ecommerce":null}
// clear immediately followed by the actual event payload. Confirmed live
// via a raw g/collect request: without the clear, select_item's pr1 (the
// one clicked item) arrived correctly, but pr2/pr3/pr4 -- stale items
// from the page's own prior view_item_list (related products) push --
// bled into the same hit.
function bhp_ae_captured_pushes( array $payload ) {
	ob_start();
	bhp_bundle_print_datalayer_push( $payload );
	$html = ob_get_clean();
	preg_match_all( '/window\.dataLayer\.push\((.*?)\);<\/script>/', $html, $m );
	return array_map( function ( $json ) {
		return json_decode( $json, true );
	}, $m[1] );
}

function bhp_ae_captured_push( array $payload ) {
	$pushes = bhp_ae_captured_pushes( $payload );
	return end( $pushes ); // the actual event payload is always the last push
}

$vil_pushes = bhp_ae_captured_pushes( array(
	'event'          => 'view_item_list',
	'item_list_id'   => 'shop',
	'item_list_name' => 'Shop',
	'items'          => array( array( 'item_id' => '15', 'item_name' => 'Mount Everest' ) ),
) );
bhp_ae_test_assert( 2 === count( $vil_pushes ), 'view_item_list (has ecommerce data): exactly two dataLayer pushes occur -- a clear, then the event', $failures );
bhp_ae_test_assert( array( 'ecommerce' => null ) === ( $vil_pushes[0] ?? null ), 'view_item_list: the FIRST push is the ecommerce clear ({"ecommerce":null}), before the event itself', $failures );
$vil_push = $vil_pushes[1] ?? null;
bhp_ae_test_assert( is_array( $vil_push ), 'bhp_bundle_print_datalayer_push() emits parseable JSON inside the dataLayer.push() call', $failures );
bhp_ae_test_assert( isset( $vil_push['ecommerce']['items'] ) && 1 === count( $vil_push['ecommerce']['items'] ), 'view_item_list: items[] is nested under ecommerce (the field GTM\'s GA4 tag actually reads)', $failures );
bhp_ae_test_assert( 'shop' === ( $vil_push['ecommerce']['item_list_id'] ?? null ), 'view_item_list: item_list_id is also present inside the nested ecommerce object', $failures );
bhp_ae_test_assert( 'shop' === ( $vil_push['item_list_id'] ?? null ), 'view_item_list: item_list_id remains at the flat top level too (existing {{DLV - item_list_id}} variable must keep working unchanged)', $failures );
bhp_ae_test_assert( 'view_item_list' === ( $vil_push['event'] ?? null ), 'view_item_list: the `event` key itself is never nested', $failures );

// view_item_list may legitimately carry multiple items (a full catalog list) --
$vil_multi = bhp_ae_captured_push( array(
	'event'          => 'view_item_list',
	'item_list_id'   => 'shop',
	'item_list_name' => 'Shop',
	'items'          => array(
		array( 'item_id' => '14' ),
		array( 'item_id' => '15' ),
		array( 'item_id' => '17' ),
		array( 'item_id' => '18' ),
	),
) );
bhp_ae_test_assert( 4 === count( $vil_multi['ecommerce']['items'] ), 'view_item_list may contain multiple items (a full catalog/related-products list)', $failures );

// select_item must carry EXACTLY the one selected item -- this is the
// literal bug found live: without the clear push, a select_item fired
// from a page that had already fired a 4-item view_item_list arrived at
// GA4 with 4 items instead of 1.
$select_item_pushes = bhp_ae_captured_pushes( array(
	'event'          => 'select_item',
	'item_list_id'   => 'related_products',
	'item_list_name' => 'Related Products',
	'items'          => array( array( 'item_id' => '14', 'item_name' => 'Mariana Trench Hardcover' ) ),
) );
bhp_ae_test_assert( array( 'ecommerce' => null ) === ( $select_item_pushes[0] ?? null ), 'select_item: a clear push precedes it too, exactly like every other ecommerce event', $failures );
$select_item_push = end( $select_item_pushes );
bhp_ae_test_assert( 1 === count( $select_item_push['ecommerce']['items'] ?? array() ), 'select_item: contains exactly one selected item, never more', $failures );
bhp_ae_test_assert( '14' === ( $select_item_push['ecommerce']['items'][0]['item_id'] ?? null ), 'select_item: the single item is the one actually selected', $failures );

// view_item (single product page) must contain only the viewed item.
$view_item_push = bhp_ae_captured_push( array(
	'event'    => 'view_item',
	'currency' => 'USD',
	'value'    => 17.99,
	'items'    => array( array( 'item_id' => '17', 'item_name' => 'Mount Everest Hardcover' ) ),
) );
bhp_ae_test_assert( 1 === count( $view_item_push['ecommerce']['items'] ?? array() ), 'view_item: contains only the single viewed item', $failures );

// add_to_cart must contain only the newly added item(s), not the whole cart.
$add_to_cart_push = bhp_ae_captured_push( array(
	'event'    => 'add_to_cart',
	'currency' => 'USD',
	'value'    => 11.99,
	'items'    => array( array( 'item_id' => '15', 'item_name' => 'Mount Everest Paperback' ) ),
) );
bhp_ae_test_assert( 1 === count( $add_to_cart_push['ecommerce']['items'] ?? array() ), 'add_to_cart: contains only the newly added item, not the whole cart', $failures );

// Sequential ecommerce events on the "same page" (simulated by two calls
// in a row, exactly as PHP would print two events on one page load) must
// never let the first event's items bleed into the second's.
$sequence_first  = bhp_ae_captured_push( array( 'event' => 'view_item_list', 'item_list_id' => 'related_products', 'item_list_name' => 'Related Products', 'items' => array( array( 'item_id' => 'A' ), array( 'item_id' => 'B' ), array( 'item_id' => 'C' ) ) ) );
$sequence_second = bhp_ae_captured_push( array( 'event' => 'select_item', 'item_list_id' => 'related_products', 'item_list_name' => 'Related Products', 'items' => array( array( 'item_id' => 'B' ) ) ) );
bhp_ae_test_assert( 3 === count( $sequence_first['ecommerce']['items'] ), 'Sequence check: first event (view_item_list) has its own 3 items', $failures );
bhp_ae_test_assert( 1 === count( $sequence_second['ecommerce']['items'] ) && 'B' === $sequence_second['ecommerce']['items'][0]['item_id'], 'Sequence check: second event (select_item) has only its own 1 item -- no bleed-over from the first event\'s 3 items', $failures );

$purchase_pushes = bhp_ae_captured_pushes( array(
	'event'          => 'purchase',
	'event_id'       => 'purchase_123',
	'transaction_id' => '123',
	'currency'       => 'USD',
	'value'          => 24.99,
	'tax'            => 2.10,
	'shipping'       => 3.99,
	'coupon'         => null,
	'items'          => array( array( 'item_id' => '15' ) ),
) );
bhp_ae_test_assert( 2 === count( $purchase_pushes ) && array( 'ecommerce' => null ) === $purchase_pushes[0], 'purchase: also gets a clear push immediately before it, same as every ecommerce event', $failures );
$purchase_push = end( $purchase_pushes );
bhp_ae_test_assert( isset( $purchase_push['ecommerce']['items'], $purchase_push['ecommerce']['transaction_id'], $purchase_push['ecommerce']['value'] ), 'purchase: items/transaction_id/value are all nested under ecommerce', $failures );
bhp_ae_test_assert( 'purchase_123' === ( $purchase_push['event_id'] ?? null ), 'purchase: event_id (GTM dedup key, not a GA4 ecommerce field) stays at the flat top level, never nested', $failures );
bhp_ae_test_assert( ! isset( $purchase_push['ecommerce']['event_id'] ), 'purchase: event_id is never duplicated inside the ecommerce object', $failures );
bhp_ae_test_assert( '123' === ( $purchase_push['ecommerce']['transaction_id'] ?? null ), 'purchase: transaction_id survives the clear-then-push sequence intact', $failures );
bhp_ae_test_assert( 24.99 === ( $purchase_push['ecommerce']['value'] ?? null ), 'purchase: value survives the clear-then-push sequence intact', $failures );

$refund_pushes = bhp_ae_captured_pushes( array(
	'event'          => 'refund',
	'event_id'       => 'refund_456',
	'transaction_id' => '123',
	'currency'       => 'USD',
	'value'          => 11.99,
) );
$refund_push = end( $refund_pushes );
bhp_ae_test_assert( array( 'ecommerce' => null ) === $refund_pushes[0], 'refund: also gets a clear push immediately before it', $failures );
bhp_ae_test_assert( '123' === ( $refund_push['ecommerce']['transaction_id'] ?? null ), 'refund: transaction_id remains intact after the clear-then-push sequence', $failures );
bhp_ae_test_assert( 'refund_456' === ( $refund_push['event_id'] ?? null ), 'refund: event_id stays flat, never nested, never dropped', $failures );

$lead_pushes = bhp_ae_captured_pushes( array(
	'event'       => 'lead_form_view',
	'lead_offer'  => 'adventure_kit_parent',
	'funnel_stage' => 'view',
) );
bhp_ae_test_assert( 1 === count( $lead_pushes ), 'A non-ecommerce event (lead_form_view) gets exactly ONE dataLayer push -- no unnecessary ecommerce clear is ever emitted for it', $failures );
$lead_push = $lead_pushes[0];
bhp_ae_test_assert( ! isset( $lead_push['ecommerce'] ), 'A non-ecommerce event (lead_form_view) never gets an empty/fabricated ecommerce object', $failures );

$no_pii_keys = array( 'email', 'first_name', 'last_name', 'phone', 'address' );
foreach ( array( $vil_push, $purchase_push, $lead_push ) as $push ) {
	foreach ( $no_pii_keys as $pii_key ) {
		bhp_ae_test_assert( ! array_key_exists( $pii_key, (array) $push ), "No PII key '{$pii_key}' present in the pushed payload", $failures );
	}
}

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL ANALYTICS EVENT TESTS PASSED\n";
