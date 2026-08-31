/**
 * Brave Hearts Bundle Pricing — begin_checkout / add_shipping_info /
 * add_payment_info (Phase 1B correction pass, 2026-07-06).
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.78 (2026-08-31, `CYCLE173-LD-CONSENT-CHECKOUT`) — begin_checkout
 *     MOVES HERE, AND THE REASON IT WAS MISSING IS A RACE, NOT AN OMISSION.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⚠ THE FINDING. Frodo's funnel-observability audit (2026-08-31) records
 *   that GA4 carries view_item, add_to_cart and purchase but NO
 *   begin_checkout at all, so cart→checkout drop-off is unmeasurable.
 *   The reflex reading is "it was never built". IT WAS BUILT, and reading
 *   the code is what shows why it does not arrive:
 *
 *     bundle-drawer.js, side-cart checkout button (pre-1.8.78):
 *         checkoutBtn.addEventListener('click', function () {
 *             getCart().then(function (cart) {      <-- async round trip
 *                 pushEvent('begin_checkout', {...});
 *             });
 *         });
 *
 *   The button is a real link to /checkout/. The browser begins unloading
 *   the document immediately; the Store API `getCart()` round trip and the
 *   dataLayer push that depends on it are racing that navigation, and
 *   GTM's own tag then has to fire after them. On a normal connection the
 *   navigation wins. ⛔ It is not that the event is wrong — it is that it
 *   is emitted in the one moment of the page lifecycle where an async
 *   emission is least likely to survive.
 *
 * ⭐ THE FIX, and why THIS file. begin_checkout is now emitted on
 *   /checkout/ PAGE LOAD, where nothing is unloading, from the script that
 *   already owns every other checkout-page event and already has the cart
 *   helpers, the catalog map and a Store API client. One event source per
 *   surface. It fires EXACTLY ONCE per checkout page load, and only when
 *   the cart actually has items.
 *
 * ⛔ THE SIDE-CART EVENT IS RENAMED, NOT DUPLICATED — `side_cart_checkout_click`.
 *   Leaving both as `begin_checkout` would double-count every side-cart
 *   customer the moment the reliable one started arriving, which is a worse
 *   defect than the one being fixed. The click is still a real, useful
 *   intent signal, so it keeps a name of its own rather than being deleted.
 *   ⚠ FLAGGED: if a GTM tag is bound to `begin_checkout`, it now fires from
 *   the checkout page load instead of the drawer click — that is the intent.
 *   Nothing in the GTM container was inspected or changed by this release;
 *   the container is not reachable from this desk.
 *
 * ⛔ CONSENT IS NOT GATED HERE, AND THAT IS THE HOUSE PATTERN, NOT AN
 *   OVERSIGHT. Every dataLayer push on this site is unconditional so the
 *   rendered page stays byte-identical for every visitor — `CYCLE143-GIM-51`
 *   proved that a per-visitor gate in front of SiteGround's page cache is
 *   defeated in both directions. Collection is gated downstream by Google
 *   Consent Mode (BHP_Consent's region-scoped defaults plus
 *   BHP_WPConsent_Bridge's update), exactly as it is for view_item,
 *   add_to_cart, view_cart, add_shipping_info, add_payment_info and
 *   purchase. This event is gated identically to all six.
 *
 * ⚠ KNOWN LIMITATION, RECORDED RATHER THAN FIXED (brief: note, do not build):
 *   `add_shipping_info` and `add_payment_info` below fire from PAGE-LOAD
 *   state, not from a customer interaction. Shipping fires whenever a Store
 *   API cart response already carries a selected rate — and this store has
 *   exactly one flat-rate method per zone, which WooCommerce auto-selects,
 *   so a returning customer with a saved address produces the event without
 *   ever touching the shipping step. Payment fires from
 *   `checkPreselectedPayment()` on whichever gateway is pre-checked. Both
 *   are deliberate (the alternative was losing the event entirely for
 *   customers who never interact), but they measure ARRIVAL AT the step,
 *   not COMPLETION OF it. ⛔ Do not read a funnel drop-off between
 *   add_shipping_info and add_payment_info as customer behaviour.
 *
 * ───────────────────────────────────────────────────────────────────────
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
	// ⛔ THE LATCH. Module-scoped, so it lives exactly as long as this
	// checkout page load does. A same-page cart recalculation, a Blocks
	// re-render, or the fetch observer below seeing a second /cart response
	// can never produce a second begin_checkout; a genuine second visit to
	// /checkout/ is a new document and correctly emits a new one.
	var beginCheckoutFired = false;

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
	 * ⭐ begin_checkout — exactly once per /checkout/ page load, and only
	 * with a non-empty cart.
	 *
	 * ⛔ AN EMPTY CART DOES NOT LATCH. If the first cart response this page
	 *    sees has no items, nothing is emitted AND nothing is consumed, so a
	 *    later response that does carry items still produces the event. The
	 *    ordering matters: WooCommerce redirects an empty cart away from
	 *    /checkout/, but Blocks can render this page from a cached/empty
	 *    Store API response before the real one resolves, and latching on
	 *    that would silently lose the event for a real customer. Absence of
	 *    items is "not yet", never "no".
	 *
	 * The payload keys match what bhp_bundle_print_datalayer_push() nests
	 * server-side and what add_shipping_info/add_payment_info already send
	 * client-side: currency, value, items. `value` is items-only (excludes
	 * shipping and tax), the same basis every other GA4 event on this site
	 * uses — do not "improve" it to the order total, or begin_checkout and
	 * purchase stop being comparable.
	 */
	function maybeFireBeginCheckout(cart) {
		if (beginCheckoutFired) {
			return;
		}
		if (!cart || !cart.items || !cart.items.length) {
			return;
		}
		beginCheckoutFired = true;
		pushEvent('begin_checkout', {
			source: 'checkout_page',
			currency: cartCurrency(cart),
			value: cartItemsValue(cart),
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
						maybeFireBeginCheckout(cart);
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

	/**
	 * ⭐ THE PRIMARY begin_checkout TRIGGER — an explicit cart read on load.
	 *
	 * ⛔ WHY NOT RELY ON THE FETCH OBSERVER ALONE. The observer above only
	 *    sees requests Blocks itself makes. Blocks hydrates from the
	 *    server-rendered `wcSettings` payload and, for a straightforward
	 *    guest checkout with no saved address, may make no /cart request at
	 *    all before the customer starts typing. Depending on it would
	 *    reproduce the class of bug the fixed-1500ms-setTimeout had in
	 *    watchForPreselectedPayment(): an event that arrives for some
	 *    customers and silently not for others. This read is unconditional.
	 *
	 * ⛔ originalFetch, NOT window.fetch: this must not re-enter the observer
	 *    (which would work, but through a path that only exists by accident).
	 *    The latch makes the two routes idempotent whichever wins the race.
	 *
	 * A failed read is swallowed. An observability event never breaks a
	 * customer's checkout — the same rule the observer above follows.
	 */
	function fireBeginCheckoutFromCart() {
		currentCart().then(maybeFireBeginCheckout).catch(function () {
			/* network or shape failure -- never break checkout over an observability failure */
		});
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', function () {
			fireBeginCheckoutFromCart();
			watchForPreselectedPayment();
		});
	} else {
		fireBeginCheckoutFromCart();
		watchForPreselectedPayment();
	}
})();
