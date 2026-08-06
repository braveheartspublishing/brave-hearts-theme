<?php
/**
 * Brave Hearts Bundle Pricing — THE ACTIVITY BOOK IS FREE WITH A COLLECTION.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS IS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the Chief of
 * Staff and NOT witnessed first-hand by the agent that wrote this file;
 * the register block carrying it is
 * `OVERNIGHT-EXECUTION-REGISTER-2026-08-04.md`, 2026-08-05 ~13:1x−0600):
 *
 *   "I want to change the upsell- make the activity book free and I want
 *    it clear that you get Free Shipping and a Free Activity book with
 *    Collection purchase- on all collection pages and boxes"
 *
 * So the free-ness ATTACHES TO THE COLLECTION, not to the store. A cart
 * that holds the complete collection auto-includes The Adventure Activity
 * Book at $0.00; every other cart keeps the $5 checkbox exactly as it was.
 *
 * ⚠ THE SPLIT IS A DESIGN READING, NOT ANDREW'S WORDS. His sentence ties
 *   "free" to "with Collection purchase"; the reading that non-collection
 *   carts keep the paid checkbox is the Chief of Staff's, recorded as such
 *   in the same register block and implemented here as specified.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ NO PRODUCT RECORD IS TOUCHED. THIS IS CART-LINE PRICING.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `BHP-ACTIVITY-BOOK-01` stays a $5.00 product in WooCommerce, on every
 * environment. What changes is the price of ONE CART LINE, set on the
 * cloned `$cart_item['data']` object inside
 * `woocommerce_before_calculate_totals` — the standard WooCommerce
 * mechanism for a cart-scoped price, and the same class of intervention
 * `bhp_bundle_override_shipping_cost()` already makes for shipping.
 *
 * This file calls no `update_post_meta()`, no `update_option()`, no
 * `$product->save()`. Nothing here can survive the cart it applies to.
 * Changing the product's own price is an Andrew gate and is NOT what this
 * does.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ NO NAG LOOP. REMOVED MEANS REMOVED.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The one failure mode a free auto-include has is re-adding itself the
 * instant the customer takes it out — a control that cannot be refused.
 * The moment a customer removes a granted copy, a session flag is set and
 * THIS FILE NEVER AUTO-ADDS AGAIN for that session. What remains is the
 * ordinary checkbox, now reading "FREE with your collection", which they
 * may tick if they change their mind. An unobtrusive re-offer, once, in a
 * control they already know.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE PREDICATE IS THE EXISTING ONE. There is no second definition of
 *    "collection" anywhere in this feature.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `bhp_bundle_evaluate_cart()['is_complete_collection']` — three DISTINCT
 * ADVENTURES in any combination of formats — is the same flag that already
 * decides free shipping (1.8.23). Re-deriving it here would be a second
 * copy that can disagree with the shipping the customer is charged on the
 * same screen, which is exactly the defect class `bundle-data.php` records
 * for the tier tables.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - It changes no product, variation, price, coupon, stock, shipping,
 *     tax or payment RECORD on any environment.
 *   - It computes no discount, shipping figure or total. The $0.00 is a
 *     literal zero, not a calculation.
 *   - It never empties a cart, never removes a book, and never removes a
 *     copy the CUSTOMER paid for. The only line it will ever remove is one
 *     this file granted for free, and only once the grant has lapsed.
 *   - It touches no funnel storage key and no funnel analytics prefix.
 *   - It weakens no guard. `bhp_bundle_cart_is_addon_only()` still blocks
 *     an add-on-only checkout, and the SKU allowlist is unchanged.
 *
 * ✅ IT FAILS CLOSED, exactly like the rest of the add-on system. With no
 *    resolvable `BHP-ACTIVITY-BOOK-01` SKU, every function here returns
 *    early and behaviour is byte-identical to 1.8.26.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The cart-item key that marks a line as one THIS FILE granted for free.
 *
 * ⭐ WHY THE FLAG EXISTS AT ALL, and it is the whole removal policy:
 *    a granted line is removed when the grant lapses; a line the customer
 *    CHOSE and paid $5 for is never removed by us. Without the flag the
 *    two are indistinguishable and one of the two behaviours would have to
 *    be wrong for somebody.
 *
 * It is stored in `$cart_item_data`, so it survives the session, appears
 * in the cart-item hash (a granted line and a bought line are therefore
 * separate lines rather than a merged quantity of two), and is readable
 * from the order item afterwards.
 */
const BHP_BUNDLE_ADDON_GRANT_KEY = 'bhp_free_addon_grant';

/**
 * The session key recording that the customer took the free copy out.
 *
 * Session, not cookie, not user meta: the ruling is "if removed, stay
 * removed for the session", and `WC()->session` is exactly that scope for
 * a guest as well as for a logged-in customer.
 */
const BHP_BUNDLE_ADDON_DECLINED_KEY = 'bhp_free_addon_declined';

/**
 * Is the free-with-collection offer switched on at all?
 *
 * Filterable so the offer can be turned off without a code change if
 * Andrew withdraws it — the same shape as `bhp_bundle_addon_skus()` and
 * `bhp_bundle_freeship_copy()`.
 *
 * @return bool
 */
function bhp_bundle_addon_free_enabled() {
	/**
	 * Switch the free-with-collection offer off.
	 *
	 * @param bool $enabled Whether the activity book is free with a collection.
	 */
	return (bool) apply_filters( 'bhp_bundle_addon_free_enabled', true );
}

/**
 * Is the offer BOTH switched on AND actually deliverable on this
 * environment right now?
 *
 * ⭐ THIS IS THE FUNCTION EVERY MESSAGING SURFACE MUST GATE ON, in this
 *    plugin and in the theme. It is the free-addon counterpart of
 *    `bhp_book_collection_ships_free()`: the site may only say "includes
 *    the Activity Book FREE" while there is a real, purchasable,
 *    in-stock product to include. On production before the product
 *    existed, and on any environment where the SKU stops resolving, this
 *    returns false and every sentence disappears with no copy edit.
 *
 * @return bool
 */
function bhp_bundle_addon_free_with_collection() {
	if ( ! bhp_bundle_addon_free_enabled() ) {
		return false;
	}
	if ( ! function_exists( 'bhp_bundle_addon_product' ) ) {
		return false;
	}
	return null !== bhp_bundle_addon_product();
}

/**
 * Does THIS cart earn the free copy?
 *
 * One line, deliberately: the answer is the existing collection flag and
 * nothing else. See the header note.
 *
 * @param WC_Cart|object|null $cart Cart, or any object exposing get_cart().
 * @return bool
 */
function bhp_bundle_cart_earns_free_addon( $cart ) {
	if ( ! $cart || ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return false;
	}
	if ( ! function_exists( 'bhp_bundle_evaluate_cart' ) ) {
		return false;
	}
	$eval = bhp_bundle_evaluate_cart( $cart );
	return ! empty( $eval['is_complete_collection'] );
}

/**
 * The add-on lines currently in a cart, keyed by cart item key.
 *
 * @param WC_Cart|object|null $cart Cart.
 * @return array<string,array> Cart items.
 */
function bhp_bundle_addon_cart_lines( $cart ) {
	$lines = array();
	if ( ! $cart || ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return $lines;
	}
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return $lines;
	}
	foreach ( $cart->get_cart() as $key => $item ) {
		$product_id   = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		$variation_id = isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;
		if ( bhp_bundle_is_addon_item( $product_id, $variation_id ) ) {
			$lines[ $key ] = $item;
		}
	}
	return $lines;
}

/**
 * Has the customer already removed a granted copy this session?
 *
 * @return bool
 */
function bhp_bundle_addon_free_declined() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return false;
	}
	return 'yes' === WC()->session->get( BHP_BUNDLE_ADDON_DECLINED_KEY );
}

/**
 * Record that they removed it. Never reset by this file.
 *
 * @param bool $declined Whether the customer declined the free copy.
 * @return void
 */
function bhp_bundle_addon_set_free_declined( $declined = true ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	WC()->session->set( BHP_BUNDLE_ADDON_DECLINED_KEY, $declined ? 'yes' : 'no' );
}

/* ═══════════════════════════════════════════════════════════════════════
 * 1 · THE PRICE. $0.00 on a qualifying cart, for EVERY add-on line.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ "EVERY add-on line" IS THE CORRECT SCOPE AND IT WAS THE SECOND
 *    ANSWER, NOT THE FIRST. The obvious rule — "only the line this file
 *    granted is free" — charges $5.00 to the one customer who deserves it
 *    least: somebody who ticked the checkbox with two books in the cart,
 *    THEN added the third. They would be paying for the thing the same
 *    page is advertising as free. The rule is therefore about the CART,
 *    not about how the line got there.
 *
 * ⛔ IT IS A CART-LINE PRICE, SET ON THE CLONE. `$cart_item['data']` is a
 *    per-cart clone of the product object; `set_price()` on it is scoped
 *    to this cart and is never persisted. The product record stays $5.00.
 */
add_action( 'woocommerce_before_calculate_totals', 'bhp_bundle_addon_apply_free_price', 20 );

/**
 * Zero the add-on's cart-line price on a qualifying cart.
 *
 * @param WC_Cart $cart Cart being calculated.
 * @return void
 */
function bhp_bundle_addon_apply_free_price( $cart ) {
	if ( ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return;
	}
	if ( ! bhp_bundle_addon_free_enabled() ) {
		return;
	}
	if ( ! bhp_bundle_cart_earns_free_addon( $cart ) ) {
		return;
	}

	foreach ( bhp_bundle_addon_cart_lines( $cart ) as $key => $item ) {
		if ( empty( $item['data'] ) || ! is_callable( array( $item['data'], 'set_price' ) ) ) {
			continue;
		}
		$item['data']->set_price( 0 );
	}
}

/* ═══════════════════════════════════════════════════════════════════════
 * 2 · THE AUTO-INCLUDE, and the auto-withdrawal that has to come with it.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUR HOOKS, AND EACH ONE EARNS ITS PLACE. This store runs Blocks, so
 *    the cart the customer uses is React over the Store API. Every one of
 *    these fires on BOTH the classic and the Store API paths, because the
 *    Store API's `CartController` drives `WC()->cart` itself:
 *
 *      woocommerce_cart_loaded_from_session — every request. Catches a
 *          cart assembled anywhere else (a bundle form post, a restored
 *          session, a second tab) before anything renders.
 *      woocommerce_add_to_cart — the third adventure landing is the moment
 *          the grant is earned, and the customer must see it in the SAME
 *          response, not on the next page load.
 *      woocommerce_cart_item_removed — the moment it can lapse.
 *      woocommerce_after_cart_item_quantity_update — a quantity taken to
 *          zero removes the line through this path on some surfaces.
 *
 * ⛔ RE-ENTRANCY IS GUARDED, and it is not theoretical: WC_Cart hooks its
 *    own `calculate_totals()` onto `woocommerce_add_to_cart` at priority
 *    20 (`class-wc-cart.php:130`, read on the running store, WooCommerce
 *    10.9.1), and `add_to_cart()` fires that action. Without the static,
 *    adding the add-on from inside the add-to-cart hook would re-enter
 *    this function.
 */
add_action( 'woocommerce_cart_loaded_from_session', 'bhp_bundle_addon_sync_free_grant', 20 );
add_action( 'woocommerce_add_to_cart', 'bhp_bundle_addon_sync_free_grant', 25 );
add_action( 'woocommerce_cart_item_removed', 'bhp_bundle_addon_sync_free_grant', 25 );
add_action( 'woocommerce_after_cart_item_quantity_update', 'bhp_bundle_addon_sync_free_grant', 25 );

/**
 * Bring the cart's free copy into line with what the cart has earned.
 *
 * Adds a granted copy when the collection is complete and the customer has
 * not declined one; withdraws a granted copy when the collection is no
 * longer complete. Never touches a copy the customer paid for.
 *
 * @return bool True if the cart was changed by this call.
 */
function bhp_bundle_addon_sync_free_grant() {
	static $running = false;

	if ( $running ) {
		return false;
	}
	if ( ! bhp_bundle_addon_free_enabled() ) {
		return false;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	if ( ! function_exists( 'bhp_bundle_addon_product' ) ) {
		return false;
	}

	$product = bhp_bundle_addon_product();
	if ( ! $product ) {
		return false; // Fails closed: no product, no grant, no change.
	}

	$cart      = WC()->cart;
	$earns     = bhp_bundle_cart_earns_free_addon( $cart );
	$lines     = bhp_bundle_addon_cart_lines( $cart );
	$granted   = array();
	$purchased = array();

	foreach ( $lines as $key => $item ) {
		if ( ! empty( $item[ BHP_BUNDLE_ADDON_GRANT_KEY ] ) ) {
			$granted[ $key ] = $item;
		} else {
			$purchased[ $key ] = $item;
		}
	}

	$running = true;
	$changed = false;

	try {
		if ( $earns ) {
			/*
			 * ⛔ THE THREE REASONS NOT TO ADD, IN ORDER. Each is a separate
			 *    sentence rather than one boolean so a future reader can see
			 *    which one fired:
			 *      1. they already have a granted copy;
			 *      2. they already have a copy at all (a bought one is now
			 *         free by rule 1 above, so adding a second would be a
			 *         duplicate of a thing they already hold);
			 *      3. they removed one this session — REMOVED MEANS REMOVED.
			 */
			if ( empty( $granted ) && empty( $purchased ) && ! bhp_bundle_addon_free_declined() ) {
				$added = $cart->add_to_cart(
					(int) $product->get_id(),
					1,
					0,
					array(),
					array( BHP_BUNDLE_ADDON_GRANT_KEY => 'yes' )
				);
				$changed = (bool) $added;
			}
		} elseif ( ! empty( $granted ) ) {
			/*
			 * The grant has lapsed — a book came out of the cart. Withdraw
			 * the copy WE gave, so nobody is silently charged $5.00 for a
			 * line they never chose. A copy they ticked themselves is in
			 * `$purchased` and is deliberately left alone.
			 *
			 * ⛔ `bhp_bundle_addon_withdrawing()` is what stops this
			 *    removal from being mistaken for the CUSTOMER declining the
			 *    offer. Without it the decline flag would latch on every
			 *    ordinary book removal and the offer would never be made
			 *    again for the rest of the session.
			 */
			bhp_bundle_addon_withdrawing( true );
			foreach ( array_keys( $granted ) as $key ) {
				$cart->remove_cart_item( $key );
				$changed = true;
			}
			bhp_bundle_addon_withdrawing( false );
		}
	} finally {
		$running = false;
	}

	return $changed;
}

/**
 * Read/write the "this removal is ours, not the customer's" flag.
 *
 * @param bool|null $set Set the flag, or null to read it.
 * @return bool
 */
function bhp_bundle_addon_withdrawing( $set = null ) {
	static $withdrawing = false;
	if ( null !== $set ) {
		$withdrawing = (bool) $set;
	}
	return $withdrawing;
}

/* ═══════════════════════════════════════════════════════════════════════
 * 3 · REMOVED MEANS REMOVED.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `woocommerce_remove_cart_item` fires BEFORE the line is gone
 * (`class-wc-cart.php:1445`), which is the only point at which the item's
 * own data — and therefore the grant flag — can still be read.
 */
add_action( 'woocommerce_remove_cart_item', 'bhp_bundle_addon_note_customer_removal', 10, 2 );

/**
 * Latch the decline flag when the CUSTOMER removes a granted copy.
 *
 * @param string  $cart_item_key Cart item key being removed.
 * @param WC_Cart $cart          Cart.
 * @return void
 */
function bhp_bundle_addon_note_customer_removal( $cart_item_key, $cart ) {
	if ( bhp_bundle_addon_withdrawing() ) {
		return; // Our own withdrawal. Not a refusal.
	}
	if ( ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
		return;
	}
	$items = $cart->get_cart();
	if ( empty( $items[ $cart_item_key ] ) ) {
		return;
	}
	$item = $items[ $cart_item_key ];
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return;
	}
	if ( ! bhp_bundle_is_addon_item(
		isset( $item['product_id'] ) ? (int) $item['product_id'] : 0,
		isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0
	) ) {
		return;
	}

	/*
	 * ⭐ ANY add-on removal latches the flag, not only a granted one, and
	 *    that asymmetry is deliberate. A customer who unticks a PAID copy
	 *    on a cart that is one book away from qualifying has said no to
	 *    this product; auto-adding it back the moment they complete the
	 *    collection would be the nag loop, arrived at by a side door.
	 */
	bhp_bundle_addon_set_free_declined( true );
}

/* ═══════════════════════════════════════════════════════════════════════
 * 4 · SAYING SO, ON THE LINE ITSELF.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * A line that reads "$0.00" is accurate and says nothing. `woocommerce_
 * get_item_data` is the one filter both worlds honour: the classic cart
 * template prints it under the product name, and the Store API exposes it
 * as `items[].item_data`, which the Blocks cart and checkout render in the
 * same place. One string, three surfaces, no template override.
 */
add_filter( 'woocommerce_get_item_data', 'bhp_bundle_addon_free_item_data', 10, 2 );

/**
 * Add the "FREE with your collection" line under the add-on's name.
 *
 * @param array $item_data Existing item data rows.
 * @param array $cart_item Cart item.
 * @return array
 */
function bhp_bundle_addon_free_item_data( $item_data, $cart_item ) {
	if ( ! is_array( $item_data ) || ! is_array( $cart_item ) ) {
		return $item_data;
	}
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return $item_data;
	}
	if ( ! bhp_bundle_is_addon_item(
		isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0,
		isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0
	) ) {
		return $item_data;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $item_data;
	}
	if ( ! bhp_bundle_addon_free_enabled() || ! bhp_bundle_cart_earns_free_addon( WC()->cart ) ) {
		return $item_data;
	}

	$copy = bhp_bundle_addon_free_copy();

	$item_data[] = array(
		'key'     => $copy['line_key'],
		'value'   => $copy['line_value'],
		'display' => '',
	);

	return $item_data;
}

/**
 * The free-offer copy, in ONE place, next to the paid copy it replaces.
 *
 * ⛔ EVERY CONSTRAINT THE $5 COPY WAS WRITTEN UNDER STILL APPLIES: no em
 *    dash, no urgency, no scarcity, no countdown, no rating, no statistic,
 *    no reading age, no outcome claim. Nothing on the never-invent list
 *    appears in any string here.
 *
 * ⛔ NO PRICE STRING IS HARDCODED. "FREE" is not a price, it is the
 *    absence of one, and it only ever renders while
 *    `bhp_bundle_cart_earns_free_addon()` is true — i.e. while the line is
 *    genuinely $0.00. The $5.00 figure continues to come from WooCommerce
 *    itself in `bhp_bundle_addon_data()`.
 *
 * @return array
 */
function bhp_bundle_addon_free_copy() {
	$copy = array(
		// The cart/checkout line-item note.
		'line_key'    => __( 'Included', 'bhp-bundle-pricing' ),
		'line_value'  => __( 'FREE with your collection', 'bhp-bundle-pricing' ),
		// The checkbox label, replacing "Add %1$s - $5.00" while the cart qualifies.
		/* translators: %s: product title */
		'label'       => __( 'Add %s - FREE with your collection', 'bhp-bundle-pricing' ),
		// What the drawer prints in the price cell for a granted line.
		'price'       => __( 'FREE', 'bhp-bundle-pricing' ),
		// The line the drawer prints under the add-on's title.
		'item_note'   => __( 'FREE with your collection', 'bhp-bundle-pricing' ),
		/* translators: %s: product title */
		'aria'        => __( 'Add %s to your order, free with your collection', 'bhp-bundle-pricing' ),
		// The collection-page and messaging clause.
		'offer_short' => __( 'FREE Activity Book', 'bhp-bundle-pricing' ),
	);

	/**
	 * Swap the free-with-collection copy.
	 *
	 * @param array $copy Free-offer strings.
	 */
	return apply_filters( 'bhp_bundle_addon_free_copy', $copy );
}

/**
 * The single word a price box prints in the Activity Book row.
 *
 * Deliberately routed through `bhp_bundle_shipping_display()`'s sibling
 * copy array rather than written inline at the call site, so the offer can
 * be reworded in one filter — the same discipline
 * `bhp_bundle_freeship_copy()` is built on.
 *
 * @return string
 */
function bhp_bundle_addon_free_display() {
	$copy = bhp_bundle_addon_free_copy();
	return isset( $copy['price'] ) ? $copy['price'] : 'FREE';
}

/**
 * The short offer label for a fine-print or badge context.
 *
 * @return string
 */
function bhp_bundle_addon_free_offer_label() {
	$copy = bhp_bundle_addon_free_copy();
	return isset( $copy['offer_short'] ) ? $copy['offer_short'] : 'FREE Activity Book';
}
