/**
 * Brave Hearts — N1, the checkout-page "Add This Adventure" cross-sell.
 *
 * Loaded on /checkout/ ONLY (never on order-received — see checkout-upsell.php).
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ SCOPE, AND IT IS NARROW ON PURPOSE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * This file renders ONE panel above the checkout's order summary and adds ONE
 * product when its button is pressed. It computes no price, writes no total,
 * reads no consent state, touches no payment method and never intercepts the
 * submit. If every line of it failed, the checkout would still take money.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE TWO MECHANISMS, AND WHY EACH IS THE RIGHT ONE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. THE MATHS IS THE DRAWER'S, IMPORTED, NOT REIMPLEMENTED.
 *    `window.bhpBundleCrossSell.compute()` IS `computeDrawerMeta()` from
 *    bundle-drawer.js, which reads `window.bhpDrawerData.bundleRules`, which
 *    is `bhp_bundle_rules()` verbatim, which is the same table
 *    `bhp_bundle_apply_discount_fees()` builds the real cart fee from. The
 *    number on this button and the number on the invoice cannot drift,
 *    because there is one function and one table.
 *
 *    It returns the DELTA, not a tier total — a customer holding two
 *    hardcovers already has the $2.99 tier-2 discount, so adding the third
 *    gains 4.98 - 2.99 = $1.99, not $4.98. Quoting the tier total there would
 *    be a claim the totals rows immediately contradict, two inches below.
 *
 * 2. THE ADD IS BLOCKS' OWN DATA-STORE ACTION, NOT A RAW `fetch()`.
 *    `wp.data.dispatch('wc/store/cart').addItemToCart(id, qty)` is the same
 *    action WooCommerce's own cart-page and cross-sell blocks dispatch. It
 *    calls the Store API, receives the whole new cart back, and writes it into
 *    the `wc/store/cart` store — so the line items, the Bundle Savings fee,
 *    shipping, tax and the total all re-render from ONE source of truth,
 *    in place, with no reload. That is precisely what "updates totals in
 *    place" has to mean here.
 *
 *    ⛔ WHY NOT A DIRECT `fetch()` TO cart/add-item. It works, and it is
 *       WRONG here, for the identical reason 2C-4's remove control does not
 *       use one: the Store API would then hold one cart while React still
 *       held the old one, so the customer would see their new book appear in
 *       the summary with the total unchanged — or worse, the reward moment
 *       (the Bundle Savings row) simply would not appear at the instant the
 *       third book landed, which is the entire point of the feature. A stale
 *       total on the page that takes the money is a worse defect than the one
 *       being fixed.
 *
 *       ⭐ VERIFIED ON STAGING 1.19.157 BEFORE THIS WAS WRITTEN, not assumed:
 *          `addItemToCart` is present in
 *          `Object.keys(wp.data.dispatch('wc/store/cart'))` on this build.
 *          If it ever is not, `render()` bails and NO BUTTON IS DRAWN — a
 *          missing upsell is a harmless failure; a button that does nothing
 *          when pressed is not.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ WHY A MUTATION OBSERVER, AND WHY THE PANEL SITS WHERE IT SITS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The Blocks checkout is React and re-renders the summary on every cart and
 * customer update — including the one this module's own button causes. A
 * one-shot pass is discarded by the first add. The observer is debounced with
 * `requestAnimationFrame` so a burst of React commits costs one pass.
 *
 * The panel is inserted as a SIBLING immediately BEFORE
 * `.wc-block-components-order-summary` (the line-item list), never between
 * nodes React owns inside it. Above the items, not below, on Andrew's own
 * F4 ruling about the drawer — verbatim: "put it on top of the inventory so
 * they see it first" — so the two surfaces behave the same way as well as
 * looking the same.
 *
 * ⭐ MEASURED, NOT REASONED: this checkout renders TWO
 *    `.wp-block-woocommerce-checkout-order-summary-block` nodes (one in the
 *    sticky sidebar, one in a `...-fill-wrapper`), and on staging 1.19.157 the
 *    sidebar one measured 626x71 with a 0x0 inner summary while the fill
 *    wrapper measured 626x533 with the real 142px item list inside it. Which
 *    of the two is live is a WooCommerce layout decision that changes with
 *    viewport and version, so this file does not hard-code either: it hosts
 *    off every `.wc-block-components-order-summary` with a non-zero box, and
 *    a zero-box one is skipped. That is why F3's "exactly one order summary
 *    on mobile" cannot produce a duplicate panel here.
 */
(function () {
	'use strict';

	var COPY = window.bhpCheckoutUpsell || {};
	var scheduled = false;
	var busy = false;

	function schedule() {
		if (scheduled) { return; }
		scheduled = true;
		window.requestAnimationFrame(function () { scheduled = false; apply(); });
	}

	/** The Blocks cart data store, or null if it is not usable on this build. */
	function cartStore() {
		if (!window.wp || !window.wp.data) { return null; }
		try {
			var sel = window.wp.data.select('wc/store/cart');
			var dis = window.wp.data.dispatch('wc/store/cart');
			if (!sel || !dis || typeof dis.addItemToCart !== 'function') { return null; }
			return { select: sel, dispatch: dis };
		} catch (e) { return null; }
	}

	/** The shared cross-sell maths from bundle-drawer.js, or null. */
	function maths() {
		var m = window.bhpBundleCrossSell;
		return (m && typeof m.compute === 'function') ? m : null;
	}

	function visible(el) {
		if (!el) { return false; }
		var r = el.getBoundingClientRect();
		return r.width > 0 && r.height > 0;
	}

	/**
	 * Build the panel. Deliberately DOM nodes, never `innerHTML` — the title
	 * comes from the catalog and the label from the server, and neither is
	 * ever parsed as markup. Same rule the drawer follows for the same string.
	 */
	function buildPanel(cs, onAdd) {
		var box = document.createElement('div');
		box.className = 'bhp-checkout-upsell';
		box.setAttribute('data-bhp-checkout-upsell', '1');

		if (COPY.heading) {
			var h = document.createElement('p');
			h.className = 'bhp-checkout-upsell__heading';
			h.textContent = COPY.heading;
			box.appendChild(h);
		}

		var title = document.createElement('span');
		title.className = 'bhp-checkout-upsell__label';
		title.textContent = cs.label;
		box.appendChild(title);

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'button bhp-checkout-upsell__btn';
		/*
		 * The label is assembled EXACTLY as bundle-drawer.js assembles it:
		 * the base CTA, plus the savings clause only when a real non-zero
		 * saving exists. A cross-sell that earns nothing (the second title in
		 * a mixed-format cart, where the 2-book tier is genuinely not granted)
		 * renders the plain "Add This Adventure" — a "Save $0.00" button would
		 * be worse than no claim at all.
		 */
		/*
		 * ⭐ 1.8.24 (2026-08-05) — CYCLE144-LD-14, AND THIS IS THE SURFACE
		 *    ANDREW NAMED. Verbatim: "On the checkout when you have two
		 *    books - It still says 'Add this adventure save $1.99' supposed
		 *    to say the Free Shipping info- Same issue for hardcovers."
		 *
		 *    Same rule as the drawer, in the same order, for the same
		 *    reason: when the offered title is the one that takes the cart
		 *    to three distinct adventures, plugin 1.8.23 makes the order
		 *    ship free, and that is the larger and the more useful of the
		 *    two true facts ($2.99 paperback / $3.99 hardcover of shipping,
		 *    against $1.99 of extra discount).
		 *
		 *    `completes_collection` is computed ONCE, in
		 *    `computeDrawerMeta()`, and arrives here on the same object as
		 *    `savings`. There is deliberately no second copy of the test.
		 */
		var label = COPY.cta || 'Add This Adventure';
		var freeShipClause = (window.bhpDrawerData
			&& window.bhpDrawerData.freeShipCopy
			&& window.bhpDrawerData.freeShipCopy.cta_clause) || '';
		if (cs.completes_collection && freeShipClause) {
			label += freeShipClause;
		} else if (cs.savings > 0) {
			var money = maths() ? maths().money(cs.savings) : '$' + cs.savings.toFixed(2);
			label += (COPY.ctaSavings || ' - Save %s').replace('%s', money);
		}
		btn.textContent = label;
		btn.setAttribute('data-product-id', String(cs.product_id));
		btn.setAttribute('data-variation-id', String(cs.variation_id || 0));
		btn.setAttribute('data-title-key', cs.title_key);
		btn.setAttribute('data-savings', cs.savings > 0 ? cs.savings.toFixed(2) : '0.00');
		if (COPY.addAria) { btn.setAttribute('aria-label', COPY.addAria.replace('%s', cs.label)); }
		btn.addEventListener('click', function (ev) {
			ev.preventDefault();
			ev.stopPropagation();
			onAdd(btn, cs);
		});
		box.appendChild(btn);

		var live = document.createElement('span');
		live.className = 'bhp-checkout-upsell__status';
		live.setAttribute('role', 'status');
		live.setAttribute('aria-live', 'polite');
		box.appendChild(live);

		return box;
	}

	/**
	 * GA4, mirroring the drawer's own cross-sell events exactly, so the two
	 * surfaces are comparable in reporting rather than being two differently
	 * named things. `source: 'checkout_upsell'` is the only distinguishing
	 * field; the event NAMES are the drawer's.
	 */
	function pushEvent(name, extra) {
		if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) { return; }
		var payload = Object.assign({ event: name, page_path: window.location.pathname || '' }, extra || {});
		window.dataLayer.push(payload);
	}

	function addItem(btn, cs) {
		if (busy || btn.disabled) { return; }
		var store = cartStore();
		if (!store) { return; }

		busy = true;
		btn.disabled = true;
		btn.setAttribute('aria-busy', 'true');
		var original = btn.textContent;
		btn.textContent = COPY.adding || 'Adding…';

		pushEvent('checkout_cross_sell_clicked', {
			title_key: cs.title_key,
			format: cs.format,
			savings: cs.savings > 0 ? cs.savings : 0
		});

		var id = parseInt(btn.getAttribute('data-variation-id'), 10) || parseInt(btn.getAttribute('data-product-id'), 10);

		var done = function () {
			busy = false;
			/*
			 * No manual re-render and no re-enable of this button: the store
			 * update triggers React, React re-renders the summary, the
			 * observer fires, and apply() draws the NEXT cross-sell (or
			 * removes the panel once the format is complete). Re-enabling a
			 * button that is about to be replaced would only create a window
			 * in which it could be double-clicked.
			 */
			schedule();
		};
		var failed = function (err) {
			busy = false;
			btn.disabled = false;
			btn.removeAttribute('aria-busy');
			btn.textContent = original;
			var status = btn.parentNode && btn.parentNode.querySelector('.bhp-checkout-upsell__status');
			if (status && COPY.failed) { status.textContent = COPY.failed; }
			window.console && console.warn('BHP checkout upsell add failed', err);
		};

		try {
			var p = store.dispatch.addItemToCart(id, 1);
			if (p && typeof p.then === 'function') { p.then(done, failed); } else { done(); }
		} catch (e) { failed(e); }
	}

	function apply() {
		var store = cartStore();
		var m = maths();
		/*
		 * No store, or no shared maths => NO PANEL AT ALL. Deliberately not a
		 * degraded fallback: the only honest version of this module is one
		 * whose number comes from the rules table and whose add updates the
		 * real totals. Without either, drawing nothing is correct.
		 */
		if (!store || !m) { return; }

		var cart;
		try { cart = store.select.getCartData() || {}; } catch (e) { return; }
		if (!cart.items || !cart.items.length) {
			removeAll();
			return;
		}

		var meta;
		try { meta = m.compute(cart); } catch (e) { return; }
		var cs = meta && meta.cross_sell;

		if (!cs) {
			// Every format in the cart is complete. Nothing honest to offer.
			removeAll();
			return;
		}

		var hosts = Array.prototype.filter.call(
			document.querySelectorAll('.wc-block-components-order-summary'),
			visible
		);
		if (!hosts.length) { return; }

		hosts.forEach(function (host) {
			var parent = host.parentNode;
			if (!parent) { return; }
			var existing = parent.querySelector(':scope > [data-bhp-checkout-upsell]');

			if (existing) {
				// Same offer already drawn? Leave it alone — replacing it on
				// every React commit would steal focus and restart the CSS
				// transition on a panel the customer may be reading.
				if (existing.getAttribute('data-offer') === cs.title_key + '|' + cs.format + '|' + cs.savings + '|' + (cs.completes_collection ? '1' : '0')) {
					return;
				}
				existing.remove();
			}

			var panel = buildPanel(cs, addItem);
			panel.setAttribute('data-offer', cs.title_key + '|' + cs.format + '|' + cs.savings + '|' + (cs.completes_collection ? '1' : '0'));
			parent.insertBefore(panel, host);
		});
	}

	function removeAll() {
		Array.prototype.forEach.call(
			document.querySelectorAll('[data-bhp-checkout-upsell]'),
			function (el) { el.remove(); }
		);
	}

	function start() {
		// Never on the thank-you page. The PHP enqueue already excludes it;
		// this is the second, cheap guard, because the cost of being wrong
		// here is offering an upsell to somebody who has already paid.
		if (document.body.classList.contains('woocommerce-order-received')) { return; }
		if (!document.querySelector('.wp-block-woocommerce-checkout')) { return; }

		apply();

		var root = document.querySelector('.wp-block-woocommerce-checkout') || document.body;
		if (typeof MutationObserver === 'function') {
			new MutationObserver(schedule).observe(root, { childList: true, subtree: true });
		}
		/*
		 * The store can settle after the observer has gone quiet (the first
		 * cart resolution lands asynchronously), so subscribe to it as well.
		 * `schedule()` is idempotent per frame, so this costs nothing.
		 */
		if (window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function') {
			window.wp.data.subscribe(schedule);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
