<?php
/**
 * Brave Hearts Bundle Pricing — the SECOND transactional email.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS IS, AND WHAT IT IS NOT
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-04, Message 32, verbatim (RELAYED through the
 * Chief of Staff; ⛔ NOT witnessed first-hand by the agent that wrote this
 * file):
 *
 *   "Your books have shipped is fine. They should receive two emails then-
 *    1 for the post purchase expectation path and then 1 email with the $5
 *    upsell PDF thanking them and tell them we hope you enjoy this
 *    activity book etc."
 *
 * ⛔ THE EXISTING COMPLETED-ORDER EMAIL STAYS AND IS NOT TOUCHED BY THIS
 *    FILE. "Your books have shipped" is WooCommerce's own
 *    `customer_completed_order`, worded by the theme's
 *    `inc/transactional-emails.php`. Nothing here filters its subject, its
 *    heading, its content, its recipient or its enabled state. This is a
 *    SECOND, ADDITIONAL email that goes out alongside it.
 *
 * ⭐ IT FIRES ONLY WHEN THE ORDER CONTAINS THE ADD-ON. Detection is the
 *    same SKU allowlist the rest of the add-on system uses
 *    (`bhp_bundle_is_addon_item()` / `BHP-ACTIVITY-BOOK-01`), so this email
 *    and the shipping-tier exemption can never disagree about what the
 *    add-on is. A books-only order fires exactly the ordinary emails and
 *    nothing else.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE DOWNLOAD LINK IS CORE'S OWN. NOTHING HERE MINTS A LINK.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * WooCommerce already grants download permissions on order completion:
 *
 *   wp-content/plugins/woocommerce/includes/wc-order-functions.php:494
 *     add_action( 'woocommerce_order_status_completed',
 *                 'wc_downloadable_product_permissions' );
 *
 * (read from the running store, WooCommerce 10.9.1, not from memory), and
 * that permission is what makes `WC_Order::get_downloadable_items()`
 * return a `download_url` — the signed, order-scoped, key-bearing URL that
 * core's own downloads table and My Account page use.
 *
 * ⛔ THIS FILE READS THAT URL. It does not build one, does not compute a
 *    key, does not call `add_query_arg` on a file path, and does not
 *    expose a file path anywhere. A hand-rolled link would be a second
 *    implementation of an access-control mechanism, which is the exact
 *    class of thing not to reimplement.
 *
 * ⚠ ORDERING, CHECKED RATHER THAN ASSUMED. `wc_downloadable_product_
 *   permissions` is hooked at file-load of `wc-order-functions.php`, which
 *   WooCommerce includes long before `WC_Emails::init_transactional_emails()`
 *   runs on `init`. Both sit at priority 10 on
 *   `woocommerce_order_status_completed`, so registration order decides,
 *   and core's grant is registered first. This email additionally hooks the
 *   `_notification` action at priority 20 rather than 10, so it also runs
 *   after WooCommerce's own completed-order email. Verified by observation
 *   on staging, not left to inference: see the QA record for this release.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   - It writes no WooCommerce setting and no option. Registering an email
 *     CLASS is not writing a setting: `WC_Email::init_settings()` only
 *     reads `get_option()`. Nothing here calls `update_option()`.
 *   - It changes no product, variation, price, coupon, stock, shipping,
 *     tax or payment record on any environment.
 *   - It changes no other email's subject, heading, content or state.
 *   - It touches no funnel storage key and no funnel analytics prefix.
 *   - It carries no coupon, no second upsell, no review ask and no lead
 *     magnet. See `addon-thankyou-copy.php`.
 *   - It contains no customer-facing string. Every word is in
 *     `addon-thankyou-copy.php`, so approved copy lands as a one-file swap.
 *
 * ✅ IT FAILS CLOSED, exactly like the rest of the add-on system. With no
 *    resolvable `BHP-ACTIVITY-BOOK-01` SKU — which is the state of
 *    production until Andrew approves the live product — no order can ever
 *    contain the add-on, so the trigger returns before doing anything and
 *    behaviour is byte-identical to before this file existed.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-thankyou-copy.php';

/**
 * The order meta key recording that this email has been sent once.
 *
 * ⭐ WHY AN IDEMPOTENCY GUARD EXISTS HERE AND NOT ON CORE'S EMAILS.
 *    `woocommerce_order_status_completed` fires every time an order
 *    ENTERS the completed status, and an order can legitimately go
 *    completed -> processing -> completed while a human sorts something
 *    out in wp-admin. For a status email a second copy is noise. For a
 *    "here is the file you bought" email a second copy reads as either a
 *    duplicate charge or a system fault, which is a support ticket.
 *
 *    So: sent once per order, recorded on the order itself.
 */
const BHP_BUNDLE_ADDON_SENT_META = '_bhp_addon_thankyou_sent';

/**
 * Does this order contain an allowlisted digital add-on?
 *
 * Uses the SAME allowlist as the cart-side exemption and the same
 * `bhp_bundle_is_addon_item()` test, so there is exactly one definition of
 * "is the add-on" across the whole feature.
 *
 * ⚠ Variations are matched on BOTH ids, matching the cart-side helper: a
 *   future variable add-on cannot slip past by being ordered as a
 *   variation.
 *
 * @param WC_Order|null $order Order.
 * @return bool
 */
function bhp_bundle_order_has_addon( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return false;
	}

	foreach ( $order->get_items() as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}
		if ( bhp_bundle_is_addon_item( (int) $item->get_product_id(), (int) $item->get_variation_id() ) ) {
			return true;
		}
	}

	return false;
}

/**
 * The add-on's downloads on this order, as core describes them.
 *
 * Returns core's own rows from `WC_Order::get_downloadable_items()`,
 * filtered to the allowlisted add-on and nothing else. The books are not
 * downloadable, but filtering rather than taking everything means this
 * email stays about the add-on even if that ever changes.
 *
 * ⚠ `get_downloadable_items()` reports one `product_id` per row and it is
 *   `$product->get_id()` — the VARIATION id for a variation. It carries no
 *   separate `variation_id` key. Read from
 *   `includes/class-wc-order.php:1799` on the running store. Passing the
 *   single id into both slots of `bhp_bundle_is_addon_item()` is therefore
 *   correct for both simple and variable add-ons, and is not laziness.
 *
 * ⛔ NO FALLBACK LINK IS EVER SYNTHESISED. If core reports no download,
 *    this returns an empty array and the caller declines to send. An email
 *    that promises a file and carries no working link is worse than no
 *    email, and the customer still has core's own downloads section in the
 *    completed-order email and in My Account.
 *
 * @param WC_Order|null $order Order.
 * @return array[] Core's download rows for the add-on, possibly empty.
 */
function bhp_bundle_addon_order_downloads( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return array();
	}
	if ( ! function_exists( 'bhp_bundle_is_addon_item' ) ) {
		return array();
	}

	$found = array();

	foreach ( $order->get_downloadable_items() as $row ) {
		if ( empty( $row['download_url'] ) ) {
			continue;
		}
		$product_id = isset( $row['product_id'] ) ? (int) $row['product_id'] : 0;
		if ( ! $product_id ) {
			continue;
		}
		if ( ! bhp_bundle_is_addon_item( $product_id, $product_id ) ) {
			continue;
		}
		$found[] = $row;
	}

	return $found;
}

/**
 * Should the add-on thank-you email be sent for this order?
 *
 * Every reason to decline, in one readable place, so the QA record can
 * name which one fired.
 *
 * @param WC_Order|null $order Order.
 * @return bool
 */
function bhp_bundle_addon_thankyou_should_send( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	if ( ! bhp_bundle_order_has_addon( $order ) ) {
		return false;
	}
	if ( $order->get_meta( BHP_BUNDLE_ADDON_SENT_META ) ) {
		return false; // Already sent once for this order.
	}
	if ( ! $order->get_billing_email() ) {
		return false;
	}
	if ( ! bhp_bundle_addon_order_downloads( $order ) ) {
		return false; // No working link: decline rather than promise one.
	}

	/**
	 * Filter whether the add-on thank-you email may send for this order.
	 *
	 * @param bool     $send  Whether to send.
	 * @param WC_Order $order Order.
	 */
	return (bool) apply_filters( 'bhp_bundle_addon_thankyou_should_send', true, $order );
}

/**
 * Register the email class with WooCommerce.
 *
 * The class file is required INSIDE the callback because `WC_Email` does
 * not exist until WooCommerce has loaded its own email classes. Requiring
 * it at file scope would fatal on a request where the mailer never loads.
 *
 * @param array $emails Registered email classes.
 * @return array
 */
function bhp_bundle_register_addon_thankyou_email( $emails ) {
	if ( ! class_exists( 'WC_Email' ) ) {
		return $emails;
	}

	require_once BHP_BUNDLE_PRICING_DIR . 'includes/class-wc-email-bhp-addon-thankyou.php';

	if ( class_exists( 'WC_Email_BHP_Addon_Thankyou' ) ) {
		$emails['WC_Email_BHP_Addon_Thankyou'] = new WC_Email_BHP_Addon_Thankyou();
	}

	return $emails;
}
add_filter( 'woocommerce_email_classes', 'bhp_bundle_register_addon_thankyou_email' );
