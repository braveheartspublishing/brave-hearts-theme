/**
 * Brave Hearts Bundle Pricing — add_shipping_info / add_payment_info
 * (Phase 1B correction pass, 2026-07-06).
 *
 * This store's checkout is the WooCommerce Blocks Checkout block (a
 * single scrollable page with distinct Contact/Shipping/Payment
 * sections, not a classic multi-page step wizard) confirmed by live DOM
 * inspection on 2026-07-06 (`.wp-block-woocommerce-checkout`,
 * `.wp-block-woocommerce-checkout-shipping-methods-block`,
 * `.wp-block-woocommerce-checkout-payment-block`). Two different
 * detection strategies are used deliberately:
 *
 * - Shipping: the shipping-method radio only APPEARS after the customer
 *   enters an address (confirmed live: "Enter a shipping address to view
 *   shipping options" shows with none entered) and this store has only
 *   ONE flat-rate method per zone, which WooCommerce auto-selects the
 *   instant it becomes available -- there may be no user click to hang
 *   an event on at all. Detected instead via the underlying Store API
 *   network responses (`/cart/update-customer`, `/cart/select-shipping-rate`,
 *   and the initial `/cart` fetch) that the Blocks checkout itself
 *   already makes -- a stable, version-independent contract, unlike
 *   Blocks' internal DOM structure.
 * - Payment: multiple real gateways are configured (Stripe, PayPal, ...),
 *   so a real radio selection exists and is the right signal. Confirmed
 *   live: `input[name="radio-control-wc-payment-method-options"]`,
 *   `value` = the gateway's internal ID (e.g. "stripe") -- already a
 *   non-sensitive label, never the label prose or any card data.
 *
 * Enqueued only on the checkout page (is_checkout(), excluding the
 * order-received endpoint) -- see bundle-drawer.php.
 */
(function () {
	'use strict';

	var STORE_API_CART = '/wp-json/wc/store/v1/cart';
	var lastShippingRateId = null;
	var lastPaymentMethod = null;

	function pushEvent(eventName, extra) {
		if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
			return;
		}
		window.dataLayer.push(Object.assign({ event: eventName }, extra || {}));
	}

	function cartCurrency(cart) {
		return (cart && cart.totals && cart.totals.currency_code) || '';
	}

	function cartItemsValue(cart) {
		if (!cart || !cart.totals) {
			return null;
		}
		var minorUnit = cart.totals.currency_minor_unit || 2;
		return Number(cart.totals.total_items) / Math.pow(10, minorUnit);
	}

	function ga4ItemsFromCart(cart) {
		var catalog = (window.bhpDrawerData && window.bhpDrawerData.catalog) || {};
		return (cart.items || []).map(function (item) {
			var minorUnit = (item.totals && item.totals.currency_minor_unit) || 2;
			var price = Number(item.prices && item.prices.price) / Math.pow(10, minorUnit);
			var out = { item_id: String(item.id), item_name: item.name, price: price, quantity: item.quantity };
			var formats = Object.keys(catalog);
			for (var f = 0; f < formats.length; f++) {
				var titles = catalog[formats[f]];
				var keys = Object.keys(titles);
				for (var i = 0; i < keys.length; i++) {
					var info = titles[keys[i]];
					var catalogId = info.variation_id ? info.variation_id : info.product_id;
					if (parseInt(catalogId, 10) === parseInt(item.id, 10)) {
						out.item_brand = 'Brave Hearts Publishing';
						out.item_category = "Children's Books";
						out.item_category2 = 'paperback' === formats[f] ? 'Paperback' : 'Hardcover';
						out.item_variant = formats[f].charAt(0).toUpperCase() + formats[f].slice(1);
					}
				}
			}
			return out;
		});
	}

	/**
	 * Finds the currently-selected shipping rate (if any) in a Store API
	 * cart response. Returns null if no address has been entered yet /
	 * no rate is selected.
	 */
	function selectedShippingRate(cart) {
		var pkg = cart && cart.shipping_rates && cart.shipping_rates[0];
		if (!pkg || !pkg.shipping_rates) {
			return null;
		}
		return pkg.shipping_rates.filter(function (r) { return r.selected; })[0] || null;
	}

	function maybeFireShippingInfo(cart) {
		var rate = selectedShippingRate(cart);
		if (!rate) {
			return;
		}
		// Deterministic, content-based event_id -- the same selected rate
		// firing again (e.g. a recalculation triggered by an unrelated
		// field edit) produces the SAME event_id rather than a new one
		// each time, so this is never a duplicate customer-facing event
		// even if the underlying code path runs more than once for the
		// same state.
		if (rate.rate_id === lastShippingRateId) {
			return;
		}
		lastShippingRateId = rate.rate_id;
		pushEvent('add_shipping_info', {
			event_id: 'add_shipping_info_' + rate.rate_id,
			currency: cartCurrency(cart),
			value: cartItemsValue(cart),
			shipping_tier: rate.name || rate.rate_id,
			items: ga4ItemsFromCart(cart)
		});
	}

	/**
	 * Wraps fetch non-invasively: always calls through to the original and
	 * returns its exact result unchanged (a clone is read separately for
	 * observation, so Blocks' own body-read is never affected). Only
	 * observes Store API cart endpoints -- never intercepts, modifies, or
	 * blocks any request.
	 */
	var originalFetch = window.fetch;
	window.fetch = function (input, init) {
		var url = typeof input === 'string' ? input : (input && input.url) || '';
		var promise = originalFetch.apply(window, arguments);
		if (url.indexOf(STORE_API_CART) !== -1) {
			promise.then(function (response) {
				if (!response.ok) {
					return;
				}
				response
					.clone()
					.json()
					.then(function (cart) {
						maybeFireShippingInfo(cart);
					})
					.catch(function () {
						/* non-JSON or unexpected shape -- never break checkout over an observability failure */
					});
			});
		}
		return promise;
	};

	function firePaymentInfo(radio, cart) {
		if (radio.value === lastPaymentMethod) {
			return;
		}
		lastPaymentMethod = radio.value;
		pushEvent('add_payment_info', {
			event_id: 'add_payment_info_' + radio.value,
			currency: cartCurrency(cart),
			value: cartItemsValue(cart),
			payment_type: radio.value, // the gateway's internal ID (e.g. "stripe") -- never card data or any sensitive field
			items: ga4ItemsFromCart(cart)
		});
	}

	function currentCart() {
		return originalFetch(STORE_API_CART, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
	}

	document.addEventListener('change', function (event) {
		var radio = event.target;
		if (!radio.matches || !radio.matches('input[type="radio"][name="radio-control-wc-payment-method-options"]')) {
			return;
		}
		currentCart().then(function (cart) {
			firePaymentInfo(radio, cart);
		});
	});

	/**
	 * Covers the common case where a payment method is already selected
	 * by default (only one gateway, or the first one pre-checked) and the
	 * customer never actively changes it. The WooCommerce Blocks payment
	 * section mounts asynchronously (after cart/address data resolves),
	 * and the actual delay is NOT fixed -- it depends on network latency
	 * and how many Store API round trips a persisted address triggers on
	 * load. A prior version of this file used a single fixed 1500ms
	 * setTimeout after DOMContentLoaded, which was confirmed live
	 * (staging, 2026-07-06) to sometimes fire before the payment radio
	 * existed at all -- silently losing add_payment_info for a customer
	 * who never touches the payment step because their gateway was
	 * already selected. A MutationObserver removes the fixed-delay
	 * assumption entirely: it reacts the instant the radio actually
	 * appears in the DOM, however long that takes.
	 */
	function checkPreselectedPayment() {
		var checked = document.querySelector('input[type="radio"][name="radio-control-wc-payment-method-options"]:checked');
		if (!checked) {
			return false;
		}
		currentCart().then(function (cart) {
			firePaymentInfo(checked, cart);
		});
		return true;
	}

	function watchForPreselectedPayment() {
		if (checkPreselectedPayment()) {
			return;
		}
		var observer = new MutationObserver(function () {
			if (checkPreselectedPayment()) {
				observer.disconnect();
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
		// Safety net only -- stops observing if checkout never finishes
		// mounting a payment method (e.g. cart emptied), so this never
		// keeps watching the DOM indefinitely on an abandoned checkout.
		window.setTimeout(function () {
			observer.disconnect();
		}, 30000);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', watchForPreselectedPayment);
	} else {
		watchForPreselectedPayment();
	}
})();
