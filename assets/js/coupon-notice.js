/**
 * Brave Hearts — dismiss the "your discount is applied" notice.
 *
 * `CYCLE170-LD-TRIPLE`, carrier items 504 / 505.
 *
 * ⛔ THIS FILE TOUCHES NO PRICE, NO CART AND NO COUPON. It removes one element
 *    from the page and remembers, for this tab only, that it was removed. The
 *    discount itself is server-side and is entirely unaffected by anything here
 *    — dismissing the notice does not remove the coupon, and the visitor sees
 *    the discount on the cart and checkout totals either way.
 *
 * ⭐ `sessionStorage`, NOT `localStorage`, AND NOT A COOKIE. The notice should
 *    come back for a new visit (a coupon on a cart is worth stating once per
 *    visit) but not on every page of the visit in which it was dismissed.
 *    `sessionStorage` is exactly that lifetime, it is per-tab, and it is never
 *    sent to the server.
 *
 * ⛔ EVERY STORAGE CALL IS WRAPPED. Safari's private mode and a browser set to
 *    block site data both THROW on access rather than returning null, and an
 *    uncaught throw here would stop the dismiss button working at all. The
 *    fallback is "the notice does not persist its dismissal", which is the
 *    correct thing to degrade to.
 */
(function () {
	'use strict';

	var KEY = 'bhp_coupon_notice_dismissed';

	function readDismissed() {
		try {
			return '1' === window.sessionStorage.getItem(KEY);
		} catch (e) {
			return false;
		}
	}

	function writeDismissed() {
		try {
			window.sessionStorage.setItem(KEY, '1');
		} catch (e) {
			/* Storage unavailable. The notice still closes for this page view;
			   it simply reappears on the next one. Nothing else depends on it. */
		}
	}

	function boot() {
		var notice = document.querySelector('[data-bhp-coupon-notice]');
		if (!notice) {
			return;
		}

		if (readDismissed()) {
			notice.remove();
			return;
		}

		var btn = notice.querySelector('[data-bhp-coupon-dismiss]');
		if (!btn) {
			return;
		}
		btn.addEventListener('click', function () {
			writeDismissed();
			notice.remove();
		});
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());
