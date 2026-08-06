/**
 * Brave Hearts Bundle Pricing — premium complete-series landing page.
 *
 * Owns only this page's format-selector toggle (Paperback/Hardcover) and
 * this page's own analytics events. Cart writes, the side-cart drawer, and
 * shared events (add_to_cart, side_cart_opened, complete_set_reached,
 * checkout_started) are all handled by bundle-drawer.js, which already
 * intercepts this page's form.bhp-bundle-form submissions -- this file
 * never talks to the Store API directly, so there is exactly one place
 * that adds items to the cart.
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
		var payload = withEcommerceNesting(Object.assign({ event: eventName, page_path: window.location.pathname || '' }, extra || {}));
		if (payload.ecommerce) {
			// GA4/gtag retains the last-pushed ecommerce object across
			// dataLayer pushes on the same page load -- clear it immediately
			// before each new ecommerce event so stale items from a prior
			// event never bleed into this one.
			window.dataLayer.push({ ecommerce: null });
		}
		window.dataLayer.push(payload);
	}

	function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
	function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	/**
	 * F5 (2026-08-03): the sticky buy bar has to agree with the panel above it
	 * at all times. If the customer switches to Paperback and the bar still
	 * submits `complete_hardcover_smart`, the bar becomes a way to buy the
	 * wrong thing — which is worse than having no bar. The action, the label
	 * and the price are all re-derived from the format the page is showing,
	 * read out of the rendered DOM rather than hardcoded, so the server-side
	 * default and the bar can never disagree.
	 */
	function syncStickyBar(format) {
		var action = qs('[data-bhp-stickybar-action]');
		if (action) {
			action.value = 'complete_' + format + '_smart';
		}
		var text = qs('[data-bhp-stickybar-text]');
		var btn = qs('[data-bhp-format-btn="' + format + '"] .bhp-landing-format-btn__price');
		if (text && btn) {
			// The format button already renders "$48.99 collection" from the
			// same bundle-data source as everything else on the page. Reusing
			// it means the bar cannot show a price the page does not.
			var price = (btn.textContent || '').replace(/collection/i, '').trim();
			if (price) {
				text.textContent = 'Complete Collection · ' + price;
			}
		}
	}

	function setFormat(format) {
		qsa('[data-bhp-format-btn]').forEach(function (btn) {
			var isMatch = btn.getAttribute('data-bhp-format-btn') === format;
			btn.classList.toggle('is-selected', isMatch);
			btn.setAttribute('aria-checked', isMatch ? 'true' : 'false');
		});
		qsa('[data-bhp-format-panel]').forEach(function (panel) {
			var isMatch = panel.getAttribute('data-bhp-format-panel') === format;
			if (isMatch) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', '');
			}
		});
		syncStickyBar(format);
	}

	/**
	 * F5 — sticky-bar visibility.
	 *
	 * Deliberately the SAME behaviour as the shipped `audience-landing-stickybar`
	 * on the five audience pages, because that one is proven: appear past a
	 * scroll threshold, and SUPPRESS while a real CTA is already on screen so
	 * the bar never stacks a duplicate button on top of an identical one. The
	 * suppression targets are this page's own two buy buttons and the footer.
	 *
	 * The floating cart button is lifted clear by writing an inline transform,
	 * for the same reason the audience version does it: the plugin
	 * over-constrains that button's `bottom` and drives its own inline scale,
	 * so an inline transform is the only layer that reliably wins.
	 */
	function initStickyBar() {
		var bar = qs('[data-bhp-landing-stickybar]');
		if (!bar) { return; }
		var suppressTargets = [
			qs('[data-bhp-pricing-card]'),
			qs('.bhp-landing-final'),
			qs('.site-footer') || qs('footer')
		].filter(Boolean);

		var scrolled = false;
		var suppressed = false;

		function render() {
			var visible = scrolled && !suppressed;
			bar.classList.toggle('is-visible', visible);
			document.body.classList.toggle('bhp-landing-stickybar-visible', visible);
			var fab = document.getElementById('bhp-floating-cart');
			if (fab) {
				if (visible) {
					fab.style.setProperty('transform', 'translateY(-' + (bar.offsetHeight + 12) + 'px) scale(1)', 'important');
				} else {
					fab.style.removeProperty('transform');
				}
			}
		}

		window.addEventListener('scroll', function () {
			var y = window.scrollY || document.documentElement.scrollTop || 0;
			var next = y > 520;
			if (next !== scrolled) { scrolled = next; render(); }
		}, { passive: true });

		if (suppressTargets.length && typeof IntersectionObserver === 'function') {
			var active = 0;
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) { active += entry.isIntersecting ? 1 : -1; });
				active = Math.max(0, active);
				var next = active > 0;
				if (next !== suppressed) { suppressed = next; render(); }
			}, { rootMargin: '0px 0px -10% 0px' });
			suppressTargets.forEach(function (t) { observer.observe(t); });
		}
		render();
	}

	function initFormatSelector() {
		var buttons = qsa('[data-bhp-format-btn]');
		if (!buttons.length) {
			return;
		}
		buttons.forEach(function (btn, index) {
			btn.addEventListener('click', function () {
				var format = btn.getAttribute('data-bhp-format-btn');
				setFormat(format);
				pushEvent('bundle_format_selected', { format: format });
			});
			// Keyboard: left/right/up/down move between the two radio-style
			// buttons, matching native radiogroup behavior.
			btn.addEventListener('keydown', function (e) {
				if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].indexOf(e.key) === -1) {
					return;
				}
				e.preventDefault();
				var dir = (e.key === 'ArrowRight' || e.key === 'ArrowDown') ? 1 : -1;
				var next = buttons[(index + dir + buttons.length) % buttons.length];
				next.focus();
				next.click();
			});
		});
	}

	/**
	 * The gift-section CTA doesn't add to cart directly (the visitor hasn't
	 * necessarily confirmed a format yet) -- it scrolls to the format
	 * selector/pricing card and gives it a brief visible highlight plus
	 * keyboard focus, so landing there reads as "here's your next step,"
	 * not an unexplained jump partway down the page.
	 */
	function initScrollToCard() {
		qsa('[data-bhp-scroll-to-card]').forEach(function (link) {
			link.addEventListener('click', function (e) {
				var card = qs('[data-bhp-pricing-card]');
				if (!card) {
					return;
				}
				e.preventDefault();
				card.scrollIntoView({ behavior: 'smooth', block: 'center' });
				card.classList.add('bhp-landing-card--highlighted');
				card.setAttribute('tabindex', '-1');
				window.setTimeout(function () {
					card.focus({ preventScroll: true });
				}, 400);
				window.setTimeout(function () {
					card.classList.remove('bhp-landing-card--highlighted');
				}, 2200);
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		if (!qs('[data-bhp-landing]')) {
			return;
		}
		initFormatSelector();
		initScrollToCard();
		initStickyBar();
		var current = qs('[data-bhp-format-btn][aria-checked="true"]');
		if (current) { syncStickyBar(current.getAttribute('data-bhp-format-btn')); }
		// 2026-07-30: report which format the page actually opened on, so
		// bundle_page_view identifies the default rather than being silent
		// about it. Read from the rendered selector rather than hardcoded, so
		// it stays truthful if the server-side default ever changes.
		// Additive only -- the event name and every existing field are
		// unchanged, and bundle_format_selected still fires on every switch.
		var initial = qs('[data-bhp-format-btn][aria-checked="true"]');
		pushEvent('bundle_page_view', initial
			? { format: initial.getAttribute('data-bhp-format-btn') }
			: {});
	});
})();
