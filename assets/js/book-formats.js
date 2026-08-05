/**
 * Brave Hearts — canonical book page format selector (2026-07-30).
 *
 * Selection only. It contains no commerce logic whatsoever: every price is
 * server-rendered HTML handed over verbatim, and every purchase action is a
 * plain URL built in PHP from WooCommerce/bundle-plugin data. Switching
 * format never touches the cart and never recalculates anything.
 */
(function () {
	'use strict';

	/*
	 * 2D (2026-08-03): the fixed ORDER array that stood here is gone. Card
	 * order is now decided server-side by bhp_book_format_order() so that the
	 * site-wide default format is rendered first, and the keyboard order is
	 * read from the DOM below rather than restated here where the two could
	 * drift apart.
	 */

	function init(root) {
		var dataEl = root.querySelector('[data-bhp-format-data]');
		var priceEl = root.querySelector('[data-bhp-format-price]');
		var ctaEl = root.querySelector('[data-bhp-format-cta]');
		var noteEl = root.querySelector('[data-bhp-format-note]');
		// A1 (2026-08-03): the format-reactive spec line. Server-rendered for
		// the initial format, so this only ever REPLACES existing text and can
		// never introduce a layout shift on load.
		var specEl = root.querySelector('[data-bhp-format-spec]');
		if (!dataEl || !priceEl || !ctaEl) {
			return;
		}

		var formats;
		try {
			formats = JSON.parse(dataEl.textContent);
		} catch (e) {
			return; // Server-rendered prices remain visible in the cards.
		}

		var cards = Array.prototype.slice.call(root.querySelectorAll('[data-bhp-format]'));
		if (!cards.length) {
			return;
		}

		function select(key, focusCard) {
			var conf = formats[key];
			if (!conf) {
				return;
			}

			cards.forEach(function (card) {
				var on = card.getAttribute('data-bhp-format') === key;
				card.setAttribute('aria-pressed', on ? 'true' : 'false');
				card.classList.toggle('is-selected', on);
			});

			// Price HTML comes straight from WooCommerce via the server.
			priceEl.innerHTML = conf.priceHtml || '';
			ctaEl.textContent = conf.ctaLabel || '';
			ctaEl.setAttribute('href', conf.addUrl || '#');

			if (conf.external) {
				ctaEl.setAttribute('target', '_blank');
				ctaEl.setAttribute('rel', 'noopener nofollow sponsored');
			} else {
				ctaEl.removeAttribute('target');
				ctaEl.removeAttribute('rel');
			}

			ctaEl.classList.toggle('is-disabled', conf.inStock === false);
			if (conf.inStock === false) {
				ctaEl.setAttribute('aria-disabled', 'true');
			} else {
				ctaEl.removeAttribute('aria-disabled');
			}

			if (specEl && conf.formatSpec) {
				specEl.textContent = conf.formatSpec;
			}

			if (noteEl) {
				noteEl.textContent = conf.note || '';
			}

			// Analytics: selection only, no personal data.
			if (window.dataLayer && Array.isArray(window.dataLayer)) {
				window.dataLayer.push({
					event: 'book_format_selected',
					format: key,
					product_id: conf.productId || 0,
					variation_id: conf.variationId || 0
				});
			}

			if (focusCard) {
				var target = root.querySelector('[data-bhp-format="' + key + '"]');
				if (target) {
					target.focus();
				}
			}
		}

		cards.forEach(function (card) {
			card.addEventListener('click', function () {
				select(card.getAttribute('data-bhp-format'), false);
			});

			// Arrow keys move between cards, matching the single-select
			// group semantics the aria-pressed buttons already advertise.
			card.addEventListener('keydown', function (e) {
				if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].indexOf(e.key) === -1) {
					return;
				}
				e.preventDefault();
				/*
				 * 2D: arrow order follows the DOM, not a fixed list. The server
				 * now emits the default format first, so a hardcoded ORDER would
				 * have moved focus in a different sequence from the one the
				 * customer can see.
				 */
				var present = cards.map(function (c) {
					return c.getAttribute('data-bhp-format');
				});
				var idx = present.indexOf(card.getAttribute('data-bhp-format'));
				var dir = (e.key === 'ArrowRight' || e.key === 'ArrowDown') ? 1 : -1;
				select(present[(idx + dir + present.length) % present.length], true);
			});
		});

		/*
		 * 2D (2026-08-03): the server always sets data-bhp-format-initial from
		 * bhp_book_incoming_format(). The literal 'paperback' fallbacks that
		 * stood here would have silently contradicted it on any page where the
		 * attribute was missing or named a format this title does not sell, so
		 * the fallback is now the FIRST CARD ACTUALLY RENDERED - which the
		 * server already ordered initial-first.
		 *
		 * CYCLE143-CX-2 (2026-08-04): the comment above used to say
		 * bhp_book_incoming_format() "is hardcover-first". It no longer is on a
		 * product page - the URL's own format now wins there, and the server
		 * orders the cards to match - so that clause is corrected rather than
		 * left to mislead the next reader. NO BEHAVIOUR IN THIS FILE CHANGED:
		 * select() still re-applies exactly what the server already rendered,
		 * and cards[0] is still the pressed card because the server still emits
		 * the initial format first.
		 */
		var initial = root.getAttribute('data-bhp-format-initial') || '';
		if (!formats[initial]) {
			initial = cards[0].getAttribute('data-bhp-format');
		}
		select(initial, false);
	}

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.slice.call(document.querySelectorAll('[data-bhp-formats]')).forEach(init);
	});
})();
