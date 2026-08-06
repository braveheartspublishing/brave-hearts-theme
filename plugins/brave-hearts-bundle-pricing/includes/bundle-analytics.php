<?php
/**
 * Brave Hearts Bundle Pricing — analytics events (Phase 10 UX addendum,
 * reshaped to GA4 Enhanced Ecommerce in Analytics Phase 1B, 2026-07-06).
 *
 * All events push through window.dataLayer, matching the pattern already
 * used by assets/js/mariana-popup.js in the theme. This file only handles
 * the events that must be rendered server-side because they need data
 * only PHP has at that point: view_item (product page load), purchase
 * (order confirmation page, exactly once per order), refund, and the
 * business-specific bundle_type_purchased event.
 *
 * GA4-standard event names used here (Phase 1B rename from the original
 * custom names — see docs/event-dictionary.md for the full before/after
 * mapping):
 *   view_item    (was product_viewed)   - single product page load
 *   purchase     (was purchase_completed) - order thank-you page, ONCE per order
 *   refund                                - order refunded
 *   bundle_type_purchased                 - one event per qualifying bundle in the order (business-specific, kept as-is)
 *
 * Client-side events (format_selected, add_to_cart, side_cart_opened,
 * checkout_started -> begin_checkout, etc.) are pushed from
 * assets/bundle-drawer.js and assets/bundle-landing.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__, 1 ) . '/includes/dashboard/class-bhp-order-provenance.php';

/**
 * GTM's GA4 tag "Ecommerce data" source (Data Layer) only auto-reads a
 * nested `ecommerce` object -- it never picks up these same fields sat
 * flat at the top level of the pushed object. Mirrored here (not moved),
 * so the existing {{DLV - item_list_id}}-style GTM variables, which read
 * the flat top-level keys, keep working unchanged.
 */
function bhp_bundle_print_datalayer_push( array $payload ) {
	static $ecommerce_keys = array( 'currency', 'value', 'items', 'item_list_id', 'item_list_name', 'transaction_id', 'tax', 'shipping', 'coupon' );
	$ecommerce = array_intersect_key( $payload, array_flip( $ecommerce_keys ) );
	if ( ! empty( $ecommerce ) ) {
		$payload['ecommerce'] = $ecommerce;
		// GA4/gtag retains the last-pushed ecommerce object across dataLayer
		// pushes on the same page load -- without clearing it immediately
		// before each new ecommerce event, stale items from a prior event
		// (e.g. view_item_list's related-products list) bleed into the next
		// one (e.g. select_item), confirmed live via a raw g/collect request
		// carrying 4 items for a single-item select_item hit.
		printf( '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push({"ecommerce":null});</script>' . "\n" );
	}
	printf(
		'<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(%s);</script>' . "\n",
		wp_json_encode( $payload )
	);
}

/**
 * GA4 Enhanced Ecommerce item schema for one catalog title, built from the
 * same bhp_bundle_catalog() data every other pricing/discount calculation
 * already uses -- never a second, parallel source of item metadata.
 *
 * Recommended values per Phase 1B spec: item_brand is always "Brave
 * Hearts Publishing"; item_category is always "Children's Books";
 * item_category2 is the format (Paperback/Hardcover).
 *
 * @param string $format 'paperback'|'hardcover'
 * @param string $title_key
 * @param int    $quantity
 * @param float|null $discount per-unit discount amount, if any
 * @param string|null $item_list_name e.g. 'Shop', 'Complete Collection Page'
 * @return array
 */
function bhp_bundle_ga4_item( $format, $title_key, $quantity = 1, $discount = null, $item_list_name = null ) {
	$catalog = bhp_bundle_catalog();
	$info    = isset( $catalog[ $format ][ $title_key ] ) ? $catalog[ $format ][ $title_key ] : null;
	if ( ! $info ) {
		return null;
	}
	$item_id = $info['variation_id'] ? $info['variation_id'] : $info['product_id'];

	$item = array(
		'item_id'       => (string) $item_id,
		'item_name'     => $info['label'],
		'item_brand'    => 'Brave Hearts Publishing',
		'item_category' => "Children's Books",
		'item_category2' => 'paperback' === $format ? 'Paperback' : 'Hardcover',
		'item_variant'  => ucfirst( $format ),
		'price'         => (float) bhp_bundle_expected_price( $format ),
		'quantity'      => (int) $quantity,
	);
	if ( null !== $discount ) {
		$item['discount'] = (float) $discount;
	}
	if ( $item_list_name ) {
		$item['item_list_name'] = $item_list_name;
	}
	return $item;
}

/**
 * view_item_list / select_item (Phase 1B addendum, 2026-07-06).
 *
 * Scoped to two contexts where a real, stable product list already
 * exists with no template changes needed: the main Shop/catalog loop
 * (native WooCommerce archive-product.php, unmodified by this theme) and
 * the related-products section on a single product page. Content/
 * marketing grids (the Books landing page, the Complete Collection
 * bundle page) show "adventures" or bundle offers rather than individual
 * addressable products-with-stable-IDs-per-card and are deliberately out
 * of scope here -- see docs/event-dictionary.md.
 *
 * Design: PHP accumulates the exact items actually rendered during the
 * loop (never a separate/parallel query that could drift from what the
 * customer actually sees), prints ONE view_item_list event after the
 * loop completes, and ALSO exposes the same list (plus each item's
 * permalink, not part of the GA4 event itself) to
 * window.bhpTrackedLists so the client-side select_item handler can
 * identify which list+index+item a clicked link belongs to without any
 * DOM data-attribute changes to WooCommerce's own templates.
 */
function bhp_bundle_list_item_registry( $list_id = null, $item = null, $reset = false ) {
	static $lists = array();
	if ( $reset ) {
		$lists[ $list_id ] = array();
		return null;
	}
	if ( null !== $item ) {
		$lists[ $list_id ][] = $item;
		return null;
	}
	return isset( $lists[ $list_id ] ) ? $lists[ $list_id ] : array();
}

function bhp_bundle_ga4_item_from_product( WC_Product $product, $position = null, $item_list_name = null ) {
	$parent_id    = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;
	$match        = bhp_bundle_identify_cart_item( $parent_id, $variation_id );

	if ( $match ) {
		list( $format, $title_key ) = $match;
		$item = bhp_bundle_ga4_item( $format, $title_key, 1, null, $item_list_name );
	} else {
		// Not one of the six catalog editions (shouldn't happen on this
		// store's shop page today, but never silently drop a real
		// displayed product from the list just because it isn't in the
		// bundle catalog map).
		$item = array(
			'item_id'   => (string) $product->get_id(),
			'item_name' => $product->get_name(),
			'price'     => (float) $product->get_price(),
			'quantity'  => 1,
		);
		if ( $item_list_name ) {
			$item['item_list_name'] = $item_list_name;
		}
	}
	if ( null !== $position ) {
		$item['index'] = (int) $position;
	}
	return $item;
}

add_action( 'woocommerce_before_shop_loop', 'bhp_bundle_reset_shop_loop_list' );
function bhp_bundle_reset_shop_loop_list() {
	bhp_bundle_list_item_registry( 'shop', null, true );
}

add_action( 'woocommerce_before_shop_loop_item', 'bhp_bundle_accumulate_shop_loop_item', 5 );
function bhp_bundle_accumulate_shop_loop_item() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$position = count( bhp_bundle_list_item_registry( 'shop' ) ) + 1;
	$item     = bhp_bundle_ga4_item_from_product( $product, $position, bhp_bundle_current_shop_list_name() );
	$item['permalink'] = get_permalink( $product->get_id() ); // registry-only field, stripped before the GA4 event prints below
	bhp_bundle_list_item_registry( 'shop', $item );
}

function bhp_bundle_current_shop_list_name() {
	if ( is_product_category() ) {
		return single_term_title( '', false ) ?: 'Shop';
	}
	return 'Shop';
}

add_action( 'woocommerce_after_shop_loop', 'bhp_bundle_track_view_item_list' );
function bhp_bundle_track_view_item_list() {
	$items = bhp_bundle_list_item_registry( 'shop' );
	if ( empty( $items ) ) {
		return; // an empty/no-results shop page has nothing to report -- never fire with a fabricated empty items[]
	}
	$list_name = bhp_bundle_current_shop_list_name();
	$list_id   = is_product_category() ? 'category_' . sanitize_title( $list_name ) : 'shop';

	$ga4_items = array();
	$registry  = array();
	foreach ( $items as $item ) {
		$permalink = $item['permalink'];
		unset( $item['permalink'] );
		$registry[]  = array( 'permalink' => $permalink, 'item' => $item );
		$ga4_items[] = $item;
	}

	bhp_bundle_print_datalayer_push(
		array(
			'event'         => 'view_item_list',
			'item_list_id'  => $list_id,
			'item_list_name' => $list_name,
			'items'         => $ga4_items,
		)
	);
	bhp_bundle_print_list_registry_script( $list_id, $registry );
}

/**
 * Related-products view_item_list -- captured via the filter WooCommerce
 * itself uses to compute the related list (never a second/duplicate
 * query), printed once per single-product page load. Passes
 * $related_posts through unchanged; this is a read-only observer, not a
 * modifier of what actually renders.
 */
add_filter( 'woocommerce_related_products', 'bhp_bundle_track_related_view_item_list', 10, 3 );
function bhp_bundle_track_related_view_item_list( $related_posts, $product_id, $args ) {
	if ( empty( $related_posts ) || is_admin() ) {
		return $related_posts;
	}
	$ga4_items = array();
	$registry  = array();
	foreach ( array_values( $related_posts ) as $index => $related_id ) {
		$related_product = wc_get_product( $related_id );
		if ( ! $related_product ) {
			continue;
		}
		$item = bhp_bundle_ga4_item_from_product( $related_product, $index + 1, 'Related Products' );
		$registry[] = array( 'permalink' => get_permalink( $related_id ), 'item' => $item );
		$ga4_items[] = $item;
	}
	if ( empty( $ga4_items ) ) {
		return $related_posts;
	}
	bhp_bundle_print_datalayer_push(
		array(
			'event'          => 'view_item_list',
			'item_list_id'   => 'related_products',
			'item_list_name' => 'Related Products',
			'items'          => $ga4_items,
		)
	);
	bhp_bundle_print_list_registry_script( 'related_products', $registry );
	return $related_posts;
}

/**
 * Exposes the permalink->item registry to the client so select_item can
 * identify which tracked list+index+item a clicked link belongs to,
 * purely by matching the clicked anchor's resolved href -- no DOM
 * data-attribute changes to WooCommerce's own loop templates needed.
 * Never part of the GA4 event payload itself.
 */
function bhp_bundle_print_list_registry_script( $list_id, array $registry ) {
	printf(
		'<script>window.bhpTrackedLists=window.bhpTrackedLists||{};window.bhpTrackedLists[%s]=%s;</script>' . "\n",
		wp_json_encode( $list_id ),
		wp_json_encode( $registry )
	);
}

add_action( 'woocommerce_after_single_product', 'bhp_bundle_track_product_viewed' );
function bhp_bundle_track_product_viewed() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$product_id   = $product->get_id();
	$variation_id = $product->is_type( 'variation' ) ? $product_id : 0;
	$parent_id    = $variation_id ? $product->get_parent_id() : $product_id;
	$match        = bhp_bundle_identify_cart_item( $parent_id, $variation_id );

	$item = null;
	if ( $match ) {
		list( $format, $title_key ) = $match;
		$item = bhp_bundle_ga4_item( $format, $title_key, 1 );
	}

	bhp_bundle_print_datalayer_push(
		array(
			'event'    => 'view_item',
			'currency' => get_woocommerce_currency(),
			'value'    => (float) $product->get_price(),
			'items'    => $item ? array( $item ) : array(
				array(
					'item_id'   => (string) $product_id,
					'item_name' => $product->get_name(),
					'price'     => (float) $product->get_price(),
					'quantity'  => 1,
				),
			),
		)
	);
}

/**
 * Purchase deduplication (Phase 7, the highest-risk event in this phase).
 * `woocommerce_thankyou` fires on every load/refresh of the order-received
 * page -- an order-meta flag, checked and set atomically per order, is
 * the only reliable dedup mechanism (survives refreshes, different
 * devices/sessions, and page caching, unlike a sessionStorage flag).
 *
 * Internal/test orders (Phase 8) are excluded via the same
 * BHP_Order_Provenance classifier the executive dashboard already uses --
 * never a separately maintained ID list here.
 */
const BHP_PURCHASE_EVENT_FIRED_META = '_bhp_purchase_event_fired';

add_action( 'woocommerce_thankyou', 'bhp_bundle_track_purchase_completed' );
function bhp_bundle_track_purchase_completed( $order_id ) {
	if ( ! $order_id ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Dedup: never print the purchase event twice for the same order,
	// regardless of how many times this page is loaded.
	if ( 'yes' === $order->get_meta( BHP_PURCHASE_EVENT_FIRED_META, true ) ) {
		return;
	}

	// Only a genuinely paid/processing order should ever reach this point
	// (WooCommerce only renders the thank-you page in that state), but
	// fail safe rather than trust that alone -- never fire for cancelled
	// or failed orders even if this hook is somehow reached for one.
	if ( ! $order->has_status( array( 'processing', 'completed', 'on-hold' ) ) ) {
		return;
	}

	// Internal/test order exclusion -- reuses the exact same provenance
	// classifier the executive KPI dashboard already relies on, so a
	// future internal test is excluded from analytics the same moment
	// it's excluded from executive revenue, with zero separate ID list to
	// maintain here.
	if ( class_exists( 'BHP_Order_Provenance' ) && ! BHP_Order_Provenance::is_executive_eligible( $order ) ) {
		// Still mark as "fired" so a refresh of this page doesn't keep
		// re-evaluating provenance on every load, and so this internal
		// order can never later slip through if provenance data changes.
		$order->update_meta_data( BHP_PURCHASE_EVENT_FIRED_META, 'yes' );
		$order->save_meta_data();

		// Staging-only debug visibility (Phase 8: "internal test activity
		// can optionally emit a clearly separated debug event on staging
		// only") -- never printed on production, never named `purchase`,
		// so it can never be mistaken for a real conversion downstream.
		if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_staging() ) {
			bhp_bundle_print_datalayer_push(
				array(
					'event'    => 'bhp_debug_internal_order_purchase_suppressed',
					'order_id' => $order_id,
				)
			);
		}
		return;
	}

	$items    = array();
	$distinct = array(
		'paperback' => array(),
		'hardcover' => array(),
	);
	foreach ( $order->get_items() as $item ) {
		$match = bhp_bundle_identify_cart_item( $item->get_product_id(), $item->get_variation_id() );
		if ( null === $match ) {
			continue;
		}
		list( $format, $title_key ) = $match;
		if ( ! in_array( $title_key, $distinct[ $format ], true ) ) {
			$distinct[ $format ][] = $title_key;
		}
		$line_discount = $item->get_subtotal() - $item->get_total(); // per-line discount actually applied
		$ga4_item      = bhp_bundle_ga4_item( $format, $title_key, $item->get_quantity(), $line_discount > 0 ? $line_discount : null );
		if ( $ga4_item ) {
			$items[] = $ga4_item;
		}
	}

	$bundle_types = array();
	foreach ( array( 'paperback', 'hardcover' ) as $format ) {
		$tier = bhp_bundle_qualifying_tier( $distinct[ $format ] );
		if ( $tier >= 2 ) {
			$bundle_types[] = $format . '_' . $tier;
		}
	}

	// Revenue excludes tax (Phase 7 default policy) -- get_total() minus
	// get_total_tax() -- shipping IS included, matching GA4's own
	// recommended `value` definition (items + shipping, tax reported
	// separately via the `tax` field below).
	$value = (float) $order->get_total() - (float) $order->get_total_tax();

	bhp_bundle_print_datalayer_push(
		array(
			'event'          => 'purchase',
			'event_id'       => 'purchase_' . $order_id, // deterministic, stable across any re-render
			'transaction_id' => (string) $order_id,
			'currency'       => $order->get_currency(),
			'value'          => round( $value, 2 ),
			'tax'            => round( (float) $order->get_total_tax(), 2 ),
			'shipping'       => round( (float) $order->get_shipping_total(), 2 ),
			'coupon'         => implode( ',', $order->get_coupon_codes() ) ?: null,
			'items'          => $items,
		)
	);

	foreach ( $bundle_types as $bundle_type ) {
		bhp_bundle_print_datalayer_push(
			array(
				'event'       => 'bundle_type_purchased',
				'bundle_type' => $bundle_type,
				'order_id'    => $order_id,
			)
		);
	}

	$order->update_meta_data( BHP_PURCHASE_EVENT_FIRED_META, 'yes' );
	$order->save_meta_data();
}

/**
 * `refund` event (Phase 6/8): fires once per refund object, using
 * WooCommerce's own refund-created hook so it is driven by the same
 * financial event the dashboard's refund accounting already reads from --
 * never a duplicate refund-detection mechanism. Internal/test orders are
 * excluded the same way as purchase, above.
 */
add_action( 'woocommerce_order_refunded', 'bhp_bundle_track_refund', 10, 2 );
function bhp_bundle_track_refund( $order_id, $refund_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	if ( class_exists( 'BHP_Order_Provenance' ) && ! BHP_Order_Provenance::is_executive_eligible( $order ) ) {
		return; // internal/test order refunds never reach production-style analytics (Phase 8)
	}
	$refund = wc_get_order( $refund_id );
	if ( ! $refund ) {
		return;
	}
	bhp_bundle_print_datalayer_push(
		array(
			'event'          => 'refund',
			'event_id'       => 'refund_' . $refund_id,
			'transaction_id' => (string) $order_id,
			'currency'       => $order->get_currency(),
			'value'          => round( abs( (float) $refund->get_total() ), 2 ),
		)
	);
}
