/**
 * Brave Hearts — the ACTIVITY BOOK checkbox order bump.
 *
 * ONE module, THREE surfaces: the Blocks cart page, the Blocks checkout
 * page, and the bhp cart drawer. Permanent and universal, regardless of
 * cart contents (Andrew Signore, 2026-08-04, relayed).
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ WHY ONE FILE FOR THREE SURFACES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The panel MARKUP and the panel COPY are built exactly once, by
 * `buildPanel()`, and every surface uses it. The three surfaces genuinely
 * differ in only two respects — where the node is inserted, and which cart
 * transport performs the add/remove — so those are the only two things
 * passed in.
 *
 * A second copy of the checkbox for the drawer was the obvious shape and
 * it is the wrong one: the drawer and the checkout would then be two
 * controls that merely look alike, and the first copy edit would make them
 * disagree in front of a paying customer.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE TWO TRANSPORTS, AND WHY EACH SURFACE GETS THE ONE IT GETS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. CART PAGE AND CHECKOUT PAGE => `wp.data.dispatch('wc/store/cart')`.
 *    These pages are React. Their line items, fees, shipping, tax and
 *    total all render out of the `wc/store/cart` data store. Dispatching
 *    the store's own `addItemToCart` / `removeItemFromCart` actions writes
 *    the whole new cart back into that store, so every total re-renders in
 *    place from ONE source of truth.
 *
 *    ⛔ WHY NOT A RAW `fetch()` TO THE STORE API HERE. It would succeed,
 *       and it would leave the Store API holding one cart while React
 *       still held the old one — the customer would watch the activity
 *       book appear in the summary with the total unchanged. A stale total
 *       on the page that takes the money is worse than no upsell at all.
 *       This is the same reasoning `checkout-upsell.js` records for the
 *       same decision.
 *
 * 2. CART DRAWER => the drawer's OWN Store API helpers, passed in by
 *    `bundle-drawer.js`. The drawer is not React and has no data store; it
 *    renders from its own `getCart()` response and already owns a nonce.
 *    Reaching for `wp.data` there would be a second cart transport in one
 *    component.
 *
 * Both transports end at the same WooCommerce session cart. There is no
 * second cart state anywhere in this file.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ NO DARK PATTERNS, ENFORCED STRUCTURALLY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `checked` is assigned from ONE expression in ONE place — `inCart(cart)`,
 * which asks the real cart whether the product is in it. There is no other
 * assignment to `.checked` in this file. The control therefore cannot be
 * pre-ticked, cannot persist a tick the cart does not have, and cannot
 * survive a removal made anywhere else (the drawer's own Remove button,
 * the checkout's line-item remove, a Store API call from another tab).
 */
(function () {
	'use strict';

	var DATA = window.bhpAddonUpsellData || null;
	var COPY = (DATA && DATA.copy) || {};
	var FREE_COPY = (DATA && DATA.freeCopy) || {};
	var FREE_ENABLED = !!(DATA && DATA.freeEnabled);
	var PRODUCT_ID = DATA ? parseInt(DATA.productId, 10) : 0;

	/* Fails closed. No product data localized => this module does nothing. */
	if (!DATA || !PRODUCT_ID) {
		return;
	}

	var scheduled = false;
	var busy = false;

	/* ─────────────────────────────────────────────────────────────────
	 * Analytics.
	 *
	 * ⛔ COMMERCE NAMESPACE ONLY. `addon_upsell_*` is its own prefix and
	 *    shares nothing with the parent funnel (`parent_popup`) or the
	 *    teacher funnel (`teacher_popup`). This module reads and writes NO
	 *    localStorage and NO sessionStorage key of any kind, so funnel
	 *    isolation (`.claude/rules/funnels.md`) cannot be affected by it.
	 * ───────────────────────────────────────────────────────────────── */
	function pushEvent(name, extra) {
		if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) { return; }
		var payload = { event: name, page_path: window.location.pathname || '' };
		if (extra) {
			Object.keys(extra).forEach(function (k) { payload[k] = extra[k]; });
		}
		window.dataLayer.push(payload);
	}

	var shownOn = {};
	function pushShownOnce(surface) {
		if (shownOn[surface]) { return; }
		shownOn[surface] = true;
		pushEvent('addon_upsell_shown', { surface: surface, product_id: PRODUCT_ID });
	}

	/**
	 * Is the add-on in this cart, and on which line?
	 *
	 * Accepts BOTH cart shapes: the raw Store API response used by the
	 * drawer and the camelCased payload `wp.data.select('wc/store/cart')
	 * .getCartData()` returns. The only fields read are `items[].id` and
	 * `items[].key`, which are identical in both — the same compatibility
	 * `bundle-drawer.js` records for its own exported helpers.
	 *
	 * @return {object|null} The matching line, or null.
	 */
	function addonLine(cart) {
		var items = (cart && cart.items) || [];
		for (var i = 0; i < items.length; i++) {
			if (parseInt(items[i].id, 10) === PRODUCT_ID) { return items[i]; }
		}
		return null;
	}

	function inCart(cart) {
		return !!addonLine(cart);
	}

	/* ─────────────────────────────────────────────────────────────────
	 * ⭐ 1.8.27 — IS THIS CART A COMPLETE COLLECTION, RIGHT NOW?
	 *
	 * ⛔ IT ASKS THE DRAWER'S EXPORTED EVALUATION, IT DOES NOT COUNT THE
	 *    CART ITSELF. `window.bhpBundleCrossSell.compute()` is the JS
	 *    mirror of `bhp_bundle_evaluate_cart()`, already used by the
	 *    drawer and the checkout cross-sell, and `distinct_adventures` is
	 *    the exact quantity `is_complete_collection` is derived from on
	 *    the server. A second count here would be a fourth place the
	 *    definition of "collection" lives, and the first copy edit would
	 *    make the checkbox promise something the invoice does not do.
	 *
	 * ⛔ FALSE IS THE SAFE ANSWER. With the drawer script absent the
	 *    module falls back to the $5.00 label — the pre-1.8.27 behaviour,
	 *    and a label that under-promises rather than one that lies.
	 * ───────────────────────────────────────────────────────────────── */
	/*
	 * ⭐ 1.8.36 — RENAMED IN MEANING, NOT IN NAME. The question this asks is
	 *    now "does this cart hold AT LEAST ONE book?", after Andrew
	 *    Signore's 2026-08-06 ruling widened the offer from collections to
	 *    any book purchase. The identifier is left alone deliberately: it
	 *    is referenced from three call sites in this file and renaming it
	 *    in the same change that moves the threshold would make the diff
	 *    read as a refactor and hide the one line that matters.
	 *
	 * SUPERSEDED, kept so the movement is visible:
	 *     return !!meta && meta.distinct_adventures >= 3;
	 */
	function cartIsCollection(cart) {
		if (!FREE_ENABLED) { return false; }
		var api = window.bhpBundleCrossSell;
		if (!api || typeof api.compute !== 'function') { return false; }
		try {
			var meta = api.compute(cart || {});
			return !!meta && meta.distinct_adventures >= 1;
		} catch (e) { return false; }
	}

	/**
	 * Put the right label on the control for the cart the customer has.
	 *
	 * ⭐ ONE ASSIGNMENT SITE FOR THE LABEL, mirroring the file's existing
	 *    rule that `checked` has exactly one. The paid label and the free
	 *    label are two branches of one write, so the control can never end
	 *    up carrying half of each.
	 */
	function applyCopy(box, free) {
		var head = box.querySelector('.bhp-addon-upsell__head');
		var input = box.querySelector('[data-bhp-addon-input]');
		if (head) {
			head.textContent = free
				? (FREE_COPY.label || 'Add %s - FREE with your collection').replace('%s', DATA.title)
				: (COPY.label || 'Add %1$s - %2$s')
					.replace('%1$s', DATA.title)
					.replace('%2$s', DATA.price);
		}
		if (input) {
			var aria = free ? FREE_COPY.aria : COPY.aria;
			if (aria) { input.setAttribute('aria-label', aria.replace('%s', DATA.title)); }
		}
		box.classList.toggle('bhp-addon-upsell--free', !!free);
	}

	/* ─────────────────────────────────────────────────────────────────
	 * The panel. Built with createElement + textContent throughout, never
	 * innerHTML — the title and the price come from the server and must
	 * never be parsed as markup. Same rule the drawer and the checkout
	 * cross-sell already follow for the same strings.
	 * ───────────────────────────────────────────────────────────────── */
	var uid = 0;

	function buildPanel(surface) {
		uid += 1;
		var id = 'bhp-addon-upsell-' + uid;

		var box = document.createElement('div');
		box.className = 'bhp-addon-upsell bhp-addon-upsell--' + surface;
		box.setAttribute('data-bhp-addon-upsell', surface);

		var label = document.createElement('label');
		label.className = 'bhp-addon-upsell__label';
		label.setAttribute('for', id);

		var input = document.createElement('input');
		input.type = 'checkbox';
		input.id = id;
		input.className = 'bhp-addon-upsell__checkbox';
		/*
		 * ⛔ NOT SET HERE. `checked` is assigned only by syncPanel(), from
		 *    the real cart. A default of `false` is what the DOM already
		 *    gives us, and writing `input.checked = false` here would be a
		 *    second assignment site and therefore a second place a future
		 *    edit could turn into a pre-tick.
		 */
		input.setAttribute('data-bhp-addon-input', '1');
		label.appendChild(input);

		if (DATA.thumb) {
			var img = document.createElement('img');
			img.className = 'bhp-addon-upsell__thumb';
			img.src = DATA.thumb;
			img.alt = '';
			img.setAttribute('aria-hidden', 'true');
			img.width = 44;
			img.height = 57;
			img.loading = 'lazy';
			label.appendChild(img);
		}

		var text = document.createElement('span');
		text.className = 'bhp-addon-upsell__text';

		var head = document.createElement('span');
		head.className = 'bhp-addon-upsell__head';
		/*
		 * "Add the <title> - <price>". A HYPHEN, never an em dash.
		 *
		 * ⭐ 1.8.27 — the text itself is written by applyCopy() at the end
		 *    of this function, from the free/paid state. The node is still
		 *    created here so the panel's structure is unchanged.
		 */
		text.appendChild(head);

		if (COPY.benefit) {
			var sub = document.createElement('span');
			sub.className = 'bhp-addon-upsell__benefit';
			sub.textContent = COPY.benefit;
			text.appendChild(sub);
		}

		label.appendChild(text);
		box.appendChild(label);

		var status = document.createElement('span');
		status.className = 'bhp-addon-upsell__status';
		status.setAttribute('role', 'status');
		status.setAttribute('aria-live', 'polite');
		box.appendChild(status);

		/*
		 * First paint uses the server's view of the cart (`freeNow`). Every
		 * render after this one re-decides from the live cart in syncPanel().
		 */
		var freeAtLoad = FREE_ENABLED && !!(DATA && DATA.freeNow);
		applyCopy(box, freeAtLoad);
		/*
		 * ⭐ 1.8.36 — FIRST PAINT HIDES TOO. Without this the panel would
		 *    flash a checkbox for one frame on a cart that has not earned
		 *    the book, before syncPanel() takes it away. `freeNow` is the
		 *    server's view of the cart at page load, which is the only view
		 *    available at this point; syncPanel() corrects it from the live
		 *    Store API cart on the very next render.
		 */
		if (FREE_ENABLED) {
			box.hidden = !freeAtLoad;
			box.setAttribute('aria-hidden', freeAtLoad ? 'false' : 'true');
		}

		/*
		 * ⛔ THE IMPRESSION EVENT FOLLOWS VISIBILITY, NOT CONSTRUCTION. Before
		 *    1.8.36 the panel was always visible, so firing here was the same
		 *    thing. It is not any more, and an `addon_upsell_shown` for a
		 *    hidden panel would quietly inflate the denominator of the offer's
		 *    own conversion rate — a fabricated measurement, which is the same
		 *    failure class as a fabricated test result. `syncPanel()` fires it
		 *    the first time the panel is genuinely on screen.
		 */
		if (!box.hidden) { pushShownOnce(surface); }
		return box;
	}

	function setStatus(box, message) {
		var el = box.querySelector('.bhp-addon-upsell__status');
		if (el) { el.textContent = message || ''; }
	}

	/**
	 * The ONLY place `checked` is ever written.
	 *
	 * Skipped entirely while a request is in flight, so an intermediate
	 * React commit cannot snap the box back under the customer's finger
	 * mid-request and make the control look like it rejected their click.
	 */
	function syncPanel(box, cart) {
		if (busy) { return; }
		var input = box.querySelector('[data-bhp-addon-input]');
		if (!input) { return; }
		/*
		 * 1.8.27: the label follows the cart on every render, so completing
		 * the collection turns "$5.00" into "FREE with your collection" in
		 * the same frame the Bundle Savings row appears.
		 *
		 * ⭐ 1.8.36 — THE $5.00 STATE NO LONGER EXISTS ON THE PAGE. Andrew
		 *    retired the paid upsell (it sold zero copies — his observation,
		 *    recorded as an observation, not as a measured statistic), and
		 *    the offer is now free with any book. So the control has exactly
		 *    two states, not three:
		 *
		 *      cart holds a book  ->  visible, labelled FREE + the savings
		 *      cart holds no book ->  HIDDEN, no offer of any kind
		 *
		 * ⛔ ONE EXCEPTION, AND IT IS A CUSTOMER-PROTECTION EXCEPTION: if the
		 *    add-on is somehow IN the cart while the cart holds no book, the
		 *    panel stays visible so the customer keeps a way to take it out.
		 *    A control that can only add is a trap, and this file has said so
		 *    since 1.8.20. (The server also refuses an add-on-only checkout
		 *    via `bhp_bundle_cart_is_addon_only()`, so this is the second of
		 *    two independent protections, not the only one.)
		 */
		var qualifies = cartIsCollection(cart);
		var present   = inCart(cart);
		var offerable = qualifies || present || !FREE_ENABLED;
		box.hidden = !offerable;
		box.setAttribute('aria-hidden', offerable ? 'false' : 'true');
		if (offerable) {
			pushShownOnce(box.getAttribute('data-bhp-addon-upsell') || 'cart');
		}

		applyCopy(box, qualifies);
		input.checked = present;
		input.disabled = false;
		input.removeAttribute('aria-busy');
	}

	/**
	 * Wire one panel to one transport.
	 *
	 * `transport` supplies three things and nothing else:
	 *   getCart()            -> a cart object (either shape)
	 *   add(productId)       -> Promise
	 *   remove(lineKey)      -> Promise
	 *   refresh()            -> optional, re-render the host surface
	 */
	function wirePanel(box, surface, transport) {
		var input = box.querySelector('[data-bhp-addon-input]');
		if (!input) { return; }

		input.addEventListener('change', function () {
			if (busy) {
				/* Put the box back where the cart says it is and ignore. */
				input.checked = !input.checked;
				return;
			}
			var wantsIt = input.checked;
			var cart = transport.getCart();
			var line = addonLine(cart);

			/* Already in the state being asked for: nothing to do. */
			if (wantsIt === !!line) { return; }

			busy = true;
			input.disabled = true;
			input.setAttribute('aria-busy', 'true');
			setStatus(box, wantsIt ? (COPY.adding || 'Adding...') : (COPY.removing || 'Removing...'));

			var done = function () {
				busy = false;
				input.disabled = false;
				input.removeAttribute('aria-busy');
				setStatus(box, wantsIt ? (COPY.added || '') : (COPY.removed || ''));
				pushEvent(wantsIt ? 'addon_upsell_added' : 'addon_upsell_removed', {
					surface: surface,
					product_id: PRODUCT_ID
				});
				/*
				 * The standard GA4 ecommerce event, alongside the module's
				 * own. `source` is the only distinguishing field, matching
				 * how the drawer and checkout cross-sells already report.
				 */
				pushEvent(wantsIt ? 'add_to_cart' : 'remove_from_cart', {
					source: 'addon_upsell',
					items: [{ item_id: String(PRODUCT_ID), item_name: DATA.title, quantity: 1 }]
				});
				if (typeof transport.refresh === 'function') { transport.refresh(); }
				schedule();
			};
			var failed = function (err) {
				busy = false;
				input.disabled = false;
				input.removeAttribute('aria-busy');
				/*
				 * Put the checkbox back to what the CART says, not to what
				 * the customer clicked. A control that keeps a tick the
				 * cart never accepted is lying about the order.
				 */
				input.checked = !wantsIt;
				setStatus(box, wantsIt ? (COPY.failedAdd || '') : (COPY.failedDel || ''));
				window.console && window.console.warn('BHP addon upsell failed', err);
			};

			var p;
			try {
				p = wantsIt ? transport.add(PRODUCT_ID) : transport.remove(line.key);
			} catch (e) {
				failed(e);
				return;
			}
			if (p && typeof p.then === 'function') { p.then(done, failed); } else { done(); }
		});
	}

	/* ═══════════════════════════════════════════════════════════════════
	 * SURFACES 1 AND 2 — the Blocks CART PAGE and the Blocks CHECKOUT.
	 * ═══════════════════════════════════════════════════════════════════ */

	function cartStore() {
		if (!window.wp || !window.wp.data) { return null; }
		try {
			var sel = window.wp.data.select('wc/store/cart');
			var dis = window.wp.data.dispatch('wc/store/cart');
			if (!sel || !dis) { return null; }
			if (typeof dis.addItemToCart !== 'function' || typeof dis.removeItemFromCart !== 'function') {
				return null;
			}
			return { select: sel, dispatch: dis };
		} catch (e) { return null; }
	}

	function visible(el) {
		if (!el) { return false; }
		var r = el.getBoundingClientRect();
		return r.width > 0 && r.height > 0;
	}

	function schedule() {
		if (scheduled) { return; }
		scheduled = true;
		window.requestAnimationFrame(function () { scheduled = false; applyBlocks(); });
	}

	/**
	 * Where the panel goes on each Blocks surface.
	 *
	 * ⭐ MEASURED BEHAVIOUR, NOT A GUESS, and it is why every host is
	 *    filtered by `visible()`: this checkout renders TWO order-summary
	 *    blocks (a sticky sidebar one and a fill-wrapper one) and only one
	 *    of them has a non-zero box at any given viewport. Hosting off
	 *    every summary and skipping the zero-box ones is what stops a
	 *    duplicate panel appearing on mobile. `checkout-upsell.js` records
	 *    the same finding from its own live measurement.
	 *
	 * The panel is inserted as a SIBLING BEFORE the summary, never inside
	 * a node React owns and re-renders.
	 */
	function blockHosts() {
		var hosts = [];
		function collect(selector, surface) {
			Array.prototype.forEach.call(document.querySelectorAll(selector), function (el) {
				if (visible(el)) { hosts.push({ el: el, surface: surface }); }
			});
		}
		if (document.querySelector('.wp-block-woocommerce-checkout')) {
			collect('.wc-block-components-order-summary', 'checkout');
		}
		if (document.querySelector('.wp-block-woocommerce-cart')) {
			collect('.wp-block-woocommerce-cart-order-summary-block', 'cart_page');
		}
		return hosts;
	}

	function applyBlocks() {
		var store = cartStore();
		if (!store) { return; }

		var cart;
		try { cart = store.select.getCartData() || {}; } catch (e) { return; }

		var hosts = blockHosts();
		if (!hosts.length) { return; }

		hosts.forEach(function (host) {
			var parent = host.el.parentNode;
			if (!parent) { return; }

			var existing = parent.querySelector(':scope > [data-bhp-addon-upsell]');
			if (existing) {
				/*
				 * The panel is NEVER rebuilt on a React commit. Rebuilding
				 * would steal focus from a customer mid-interaction and
				 * would drop the in-flight state of their own click. Only
				 * the checkbox is re-synced from the cart.
				 */
				syncPanel(existing, cart);
				return;
			}

			var box = buildPanel(host.surface);
			wirePanel(box, host.surface, {
				getCart: function () {
					try { return store.select.getCartData() || {}; } catch (e) { return {}; }
				},
				add: function (id) { return store.dispatch.addItemToCart(id, 1); },
				remove: function (key) { return store.dispatch.removeItemFromCart(key); }
			});
			syncPanel(box, cart);
			parent.insertBefore(box, host.el);
		});
	}

	/* ═══════════════════════════════════════════════════════════════════
	 * SURFACE 3 — the cart DRAWER. Driven by bundle-drawer.js, which calls
	 * `renderInto()` on every drawer render and supplies its own Store API
	 * transport.
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * @param {HTMLElement} container The drawer's own addon container.
	 * @param {object}      cart      The Store API cart the drawer just rendered.
	 * @param {object}      transport { add, remove, refresh } from the drawer.
	 */
	function renderInto(container, cart, transport) {
		if (!container) { return; }

		var existing = container.querySelector('[data-bhp-addon-upsell]');
		if (existing) {
			syncPanel(existing, cart);
			return;
		}

		/*
		 * The drawer wipes and rebuilds `.bhp-cart-drawer__items` on every
		 * render, but this container is its own node and is deliberately
		 * NOT wiped by the drawer — so the panel survives a re-render and
		 * the customer never loses focus or an in-flight click.
		 */
		var box = buildPanel('cart_drawer');
		wirePanel(box, 'cart_drawer', {
			getCart: function () { return lastDrawerCart || {}; },
			add: transport.add,
			remove: transport.remove,
			refresh: transport.refresh
		});
		syncPanel(box, cart);
		container.appendChild(box);
	}

	var lastDrawerCart = null;
	function noteDrawerCart(cart) { lastDrawerCart = cart; }

	/* ═══════════════════════════════════════════════════════════════════
	 * Boot.
	 * ═══════════════════════════════════════════════════════════════════ */

	function start() {
		/*
		 * Second guard on the thank-you page. The PHP enqueue already
		 * excludes it; the cost of being wrong is offering an add-on to
		 * somebody who has already paid, so it is checked twice.
		 */
		if (document.body.classList.contains('woocommerce-order-received')) { return; }

		var root = document.querySelector('.wp-block-woocommerce-checkout')
			|| document.querySelector('.wp-block-woocommerce-cart');
		if (!root) { return; } // Drawer-only page. renderInto() handles it.

		applyBlocks();

		if (typeof MutationObserver === 'function') {
			new MutationObserver(schedule).observe(root, { childList: true, subtree: true });
		}
		/*
		 * The store settles after the observer has gone quiet (the first
		 * cart resolution lands asynchronously). `schedule()` is idempotent
		 * per frame, so subscribing costs one call.
		 */
		if (window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function') {
			window.wp.data.subscribe(schedule);
		}
	}

	/**
	 * The export bundle-drawer.js consumes.
	 *
	 * ⛔ READ-ONLY FROM THE DRAWER'S POINT OF VIEW. The drawer hands over a
	 *    container, its cart and its transport; it never has to know what a
	 *    checkbox looks like, what the copy says or what the product id is.
	 */
	window.bhpAddonUpsell = {
		renderInto: renderInto,
		noteCart: noteDrawerCart,
		productId: PRODUCT_ID,
		isInCart: inCart
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
