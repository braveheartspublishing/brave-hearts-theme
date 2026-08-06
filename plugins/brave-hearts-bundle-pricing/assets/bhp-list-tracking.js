/**
 * Brave Hearts Bundle Pricing — select_item tracking (Phase 1B addendum,
 * 2026-07-06).
 *
 * Matches a clicked product-card link against the server-populated
 * window.bhpTrackedLists registry (see bundle-analytics.php) purely by
 * comparing the clicked anchor's resolved href against each tracked
 * item's permalink -- no DOM data-attribute changes to WooCommerce's own
 * loop/related-products templates were needed to make this work.
 *
 * Only fires for a click that actually matches a tracked list entry --
 * an unrelated click (e.g. the "Add to cart" button, a review link, page
 * navigation) never fires select_item.
 */
(function () {
	'use strict';

	var GA4_ECOMMERCE_KEYS = ['currency', 'value', 'items', 'item_list_id', 'item_list_name', 'transaction_id', 'tax', 'shipping', 'coupon'];

	// GTM's GA4 tag "Ecommerce data" source (Data Layer) only auto-reads a
	// nested `ecommerce` object -- it never picks up these same fields sat
	// flat at the top level of the pushed object. Mirrored here (not
	// moved), so any existing {{DLV - *}}-style GTM variable reading a
	// flat top-level key keeps working unchanged.
	function withEcommerceNesting(payload) {
		var ecommerce = {};
		var hasEcommerce = false;
		GA4_ECOMMERCE_KEYS.forEach(function (key) {
			if (Object.prototype.hasOwnProperty.call(payload, key)) {
				ecommerce[key] = payload[key];
				hasEcommerce = true;
			}
		});
		if (hasEcommerce) {
			payload.ecommerce = ecommerce;
		}
		return payload;
	}

	function pushEvent(eventName, extra) {
		if (typeof window.dataLayer === 'undefined' || !Array.isArray(window.dataLayer)) {
			return;
		}
		var payload = withEcommerceNesting(Object.assign({ event: eventName }, extra || {}));
		if (payload.ecommerce) {
			// GA4/gtag retains the last-pushed ecommerce object across
			// dataLayer pushes on the same page load -- clear it immediately
			// before each new ecommerce event so stale items from a prior
			// event (e.g. view_item_list) never bleed into this one (e.g.
			// select_item). Confirmed live via a raw g/collect request
			// carrying 4 items for a single-item select_item hit.
			window.dataLayer.push({ ecommerce: null });
		}
		window.dataLayer.push(payload);
	}

	function findTrackedMatch(href) {
		var lists = window.bhpTrackedLists || {};
		var listIds = Object.keys(lists);
		for (var i = 0; i < listIds.length; i++) {
			var listId = listIds[i];
			var entries = lists[listId] || [];
			for (var j = 0; j < entries.length; j++) {
				if (entries[j].permalink === href) {
					return { listId: listId, entry: entries[j] };
				}
			}
		}
		return null;
	}

	document.addEventListener('click', function (event) {
		var link = event.target.closest('a[href]');
		if (!link) {
			return;
		}
		// Only anchors inside a product-loop or related-products container
		// are ever considered -- avoids matching an unrelated same-URL
		// link elsewhere on the page (e.g. a nav menu item that happens to
		// point at the same product for some other reason).
		if (!link.closest('ul.products, .related.products')) {
			return;
		}
		var match = findTrackedMatch(link.href);
		if (!match) {
			return;
		}
		pushEvent('select_item', {
			item_list_id: match.listId,
			item_list_name: match.entry.item.item_list_name || '',
			items: [match.entry.item]
		});
	});
})();
