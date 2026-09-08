/**
 * Express checkout bridge — keep the hidden scaffold in step with the format rail.
 * Theme 1.19.389 (2026-09-06, `CYCLE179-LD-BUILD-389`).
 *
 * WHY THIS EXISTS. `inc/express-checkout-bridge.php` prints a hidden
 * `form.cart` so the Stripe plugin's own JS can read a product id off
 * `.single_add_to_cart_button`. The visible purchase control on this theme is
 * an ANCHOR whose `data-product-id` / `data-variation-id` are rewritten by
 * `assets/js/book-formats.js` every time the shopper picks a format. Without
 * this file the scaffold would keep first paint's id forever, and a shopper who
 * switched to hardcover could hand a wallet the paperback.
 *
 * ⛔ IT MIRRORS. IT NEVER DECIDES. The anchor is the single source of truth for
 *    which edition is selected — the same rule `book-formats.js` states in its
 *    own comment: one switch statement, not two.
 *
 * ⛔ IT ADDS NOTHING TO THE CART, submits nothing, and binds no click handler.
 *    It copies one attribute value.
 *
 * ⛔ NO FUNNEL STATE. No localStorage, no sessionStorage, no analytics event,
 *    no popup binding.
 *
 * ⚠ MutationObserver rather than a custom event, because `book-formats.js`
 *   emits no event and adding one would mean editing a shipped purchase-path
 *   file for a wallet feature. Observing the attribute it already writes is the
 *   smaller change and cannot alter the rail's behaviour.
 */
(function () {
	'use strict';

	function init() {
		var scaffold = document.querySelector('[data-bhp-express-buy]');
		if (!scaffold) {
			return;
		}

		var cta = document.querySelector('[data-bhp-format-cta]');
		if (!cta) {
			// No format rail on this page (a non-canonical product). The
			// server-rendered value is already correct and nothing moves it.
			return;
		}

		function sync() {
			var variation = parseInt(cta.getAttribute('data-variation-id'), 10) || 0;
			var parent = parseInt(cta.getAttribute('data-product-id'), 10) || 0;
			var id = variation > 0 ? variation : parent;

			/*
			 * ⛔ A ZERO IS NOT WRITTEN. Kindle and the Collection deliberately
			 *    carry NO `data-product-id` (book-formats.js removes it rather
			 *    than zeroing it), and those two formats are not things a wallet
			 *    on this page can buy. Leaving the previous physical id in place
			 *    is wrong too, so the scaffold is emptied and the plugin's JS
			 *    then has nothing to add, which is the correct outcome.
			 */
			if (id > 0) {
				scaffold.value = String(id);
			} else {
				scaffold.value = '';
			}
		}

		sync();

		if (typeof MutationObserver === 'function') {
			new MutationObserver(sync).observe(cta, {
				attributes: true,
				attributeFilter: ['data-product-id', 'data-variation-id']
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
