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

	/**
	 * ⭐⭐ 1.19.393 (`CYCLE179-CX-BUILD-391-1`) — THE STICKY BUY BAR PUBLISHES
	 *     ITS OWN HEIGHT, BECAUSE NO STYLESHEET CAN KNOW IT.
	 *
	 * ⛔ THE PROBLEM IS MEASURED, NOT ANTICIPATED. At widths up to 812px
	 *    `.bhp-formats__buy` is `position: fixed` at the bottom of the screen,
	 *    so it is OUT OF FLOW and the page must reserve its height or the last
	 *    rows of the footer sit permanently underneath it. Its height is the
	 *    CTA plus whatever the Stripe element mounted, and THAT IS
	 *    ENVIRONMENT-DEPENDENT: measured on staging 1.19.393 at an asserted
	 *    375x812, the wallet drew THREE 48px buttons stacked — a 263px bar
	 *    against the 132px static reservation, which would have covered 131px
	 *    of the page. A device with one wallet draws a ~120px bar and the same
	 *    static number would leave 12px of dead parchment instead.
	 *
	 * ⭐ SO THE NUMBER IS READ, ONCE THE THING EXISTS, AND RE-READ WHENEVER IT
	 *    CHANGES. `--bhp-buybar-h` is set on the document element and the
	 *    stylesheet reserves `var(--bhp-buybar-h, 132px)`. The 132px fallback
	 *    is what a no-JavaScript or pre-mount page reserves, which is correct
	 *    for a bar carrying the CTA and an unmounted wallet.
	 *
	 * ⚠ IT NEVER SHRINKS THE RESERVE BELOW THE CTA'S OWN BOX. A transient
	 *   zero height during mount would otherwise flash the footer under the
	 *   bar and back out again.
	 *
	 * ⛔ IT CHANGES NOTHING ABOUT WHAT THE WALLET BUYS, adds no control, and
	 *    writes no state anywhere but one CSS custom property.
	 */
	function trackBuyBarHeight() {
		var bar = document.querySelector('.bhp-formats__buy');
		if (!bar) {
			return;
		}

		function publish() {
			/* Only meaningful while the bar is actually pinned. Above the
			   breakpoint the block is in normal flow and reserves its own
			   space, so the property is cleared rather than left stale. */
			if ('fixed' !== window.getComputedStyle(bar).position) {
				document.documentElement.style.removeProperty('--bhp-buybar-h');
				return;
			}
			var h = Math.ceil(bar.getBoundingClientRect().height);
			if (h < 68) {
				h = 68;
			}
			document.documentElement.style.setProperty('--bhp-buybar-h', h + 'px');
		}

		publish();

		if (typeof ResizeObserver === 'function') {
			new ResizeObserver(publish).observe(bar);
		} else {
			/* ⚠ THE FALLBACK IS DELIBERATELY DUMB AND FINITE. Three delayed
			   reads cover the wallet mounting late without installing a timer
			   that runs for the life of the page. */
			setTimeout(publish, 1200);
			setTimeout(publish, 3500);
			setTimeout(publish, 8000);
		}

		window.addEventListener('resize', publish);
		window.addEventListener('orientationchange', publish);
	}

	function boot() {
		init();
		trackBuyBarHeight();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
