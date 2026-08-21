/**
 * Brave Hearts Bundle Pricing — slide-out cart drawer.
 *
 * Talks directly to the WooCommerce Store API (/wc/store/v1) — the same
 * API this site's own Blocks-based cart and checkout pages use — so the
 * drawer's line items, prices, and Bundle Savings fee always match what
 * checkout will actually charge. No cart totals are computed twice.
 */
(function () {
	'use strict';

	var STORE_API = '/wp-json/wc/store/v1';
	var nonce = '';
	var drawer = null;
	var lastFocusedElement = null;
	var floatingBtn = null;
	var floatingBadge = null;
	/* 1.8.64 - every non-FAB control carrying data-bhp-cart-open. */
	var extraOpeners = [];

	function focusableElements(container) {
		return qsa(
			'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])',
			container
		).filter(function (el) {
			return el.offsetParent !== null;
		});
	}

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}
	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

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
		var payload = withEcommerceNesting(
			Object.assign(
				{
					event: eventName,
					page_path: window.location.pathname || ''
				},
				extra || {}
			)
		);
		if (payload.ecommerce) {
			// GA4/gtag retains the last-pushed ecommerce object across
			// dataLayer pushes on the same page load -- clear it immediately
			// before each new ecommerce event so stale items from a prior
			// event never bleed into this one.
			window.dataLayer.push({ ecommerce: null });
		}
		window.dataLayer.push(payload);
	}

	/**
	 * GA4 Enhanced Ecommerce item schema built directly from a Store API
	 * cart line item -- the same object the drawer already renders from,
	 * so price/quantity here can never drift from what checkout will
	 * actually charge. Falls back to identifyCartItem()'s catalog format
	 * detection for item_category2/item_brand/item_category; an item that
	 * isn't one of the six approved catalog editions still gets a minimal
	 * valid item rather than being silently dropped.
	 */
	function ga4ItemFromCartLine(item) {
		var match = identifyCartItem(item);
		var minorUnit = (item.totals && item.totals.currency_minor_unit) || 2;
		var price = Number(item.prices && item.prices.price) / Math.pow(10, minorUnit);
		var out = {
			item_id: String(item.id),
			item_name: item.name,
			price: price,
			quantity: item.quantity
		};
		if (match) {
			out.item_brand = 'Brave Hearts Publishing';
			out.item_category = "Children's Books";
			out.item_category2 = 'paperback' === match.format ? 'Paperback' : 'Hardcover';
			out.item_variant = match.format.charAt(0).toUpperCase() + match.format.slice(1);
		}
		return out;
	}

	function ga4ItemsFromCart(cart) {
		return (cart.items || []).map(ga4ItemFromCartLine);
	}

	function cartItemsValue(cart) {
		if (!cart || !cart.totals) {
			return null;
		}
		var minorUnit = cart.totals.currency_minor_unit || 2;
		return Number(cart.totals.total_items) / Math.pow(10, minorUnit);
	}

	function cartCurrency(cart) {
		return (cart && cart.totals && cart.totals.currency_code) || '';
	}

	function doFetch(path, options) {
		options = options || {};
		var headers = Object.assign({ 'Content-Type': 'application/json' }, options.headers || {});
		if (nonce) {
			headers.Nonce = nonce;
		}
		return fetch(
			STORE_API + path,
			Object.assign({ credentials: 'same-origin' }, options, { headers: headers })
		).then(function (response) {
			var newNonce = response.headers.get('Nonce');
			if (newNonce) {
				nonce = newNonce;
			}
			return response.json().then(function (data) {
				if (!response.ok) {
					throw data;
				}
				return data;
			});
		});
	}

	/**
	 * Every Store API write (add/update/remove) requires a valid Nonce
	 * header. If the page-load priming GET never completed successfully
	 * for any reason, `nonce` is still empty here — retrying a plain GET
	 * first guarantees a nonce is in hand before the write is attempted,
	 * instead of sending the write with no nonce and getting a confusing
	 * non-JSON error response back.
	 */
	function ensureNonce() {
		if (nonce) {
			return Promise.resolve();
		}
		return doFetch('/cart');
	}

	function storeApiFetch(path, options) {
		return doFetch(path, options);
	}

	function storeApiWrite(path, options) {
		return ensureNonce().then(function () {
			return doFetch(path, options);
		});
	}

	function getCart() {
		return storeApiFetch('/cart');
	}

	function addItem(productId, variationId, quantity) {
		var body = { id: variationId ? variationId : productId, quantity: quantity || 1 };
		return storeApiWrite('/cart/add-item', { method: 'POST', body: JSON.stringify(body) });
	}

	function updateItemQuantity(key, quantity) {
		return storeApiWrite('/cart/update-item', {
			method: 'POST',
			body: JSON.stringify({ key: key, quantity: quantity })
		});
	}

	function removeItem(key) {
		return storeApiWrite('/cart/remove-item', { method: 'POST', body: JSON.stringify({ key: key }) });
	}

	/**
	 * Identify which catalog title (if any) a Store API cart item
	 * represents, mirroring bhp_bundle_identify_cart_item() in
	 * includes/bundle-data.php. Store API cart items expose a single `id`
	 * field that is already the variation ID when the line item is a
	 * variation (there is no separate parent product ID on the item), so
	 * matching is a direct comparison against each catalog entry's
	 * variation_id-or-product_id — the same rule the PHP side uses.
	 */
	function identifyCartItem(item) {
		var catalog = (window.bhpDrawerData && window.bhpDrawerData.catalog) || {};
		var cartId = parseInt(item.id, 10);

		var formats = Object.keys(catalog);
		for (var f = 0; f < formats.length; f++) {
			var format = formats[f];
			var titles = catalog[format];
			var titleKeys = Object.keys(titles);
			for (var i = 0; i < titleKeys.length; i++) {
				var info = titles[titleKeys[i]];
				var catalogId = info.variation_id ? info.variation_id : info.product_id;
				if (parseInt(catalogId, 10) === cartId) {
					return { format: format, titleKey: titleKeys[i], info: info };
				}
			}
		}
		return null;
	}

	/**
	 * B4 (2026-08-03) — the REAL incremental saving from adding one more
	 * distinct title of `format`, given the cart already holds `count` of
	 * them.
	 *
	 * ⛔ THE RULE THIS FUNCTION EXISTS TO OBEY: never hardcode a dollar
	 *    amount on a button. Every number below is read from
	 *    window.bhpDrawerData.bundleRules, which is bhp_bundle_rules()
	 *    verbatim -- the same table bhp_bundle_apply_discount_fees() uses to
	 *    create the actual cart fee. If the table changes, this changes with
	 *    it; there is no second copy to forget.
	 *
	 * It returns the DELTA, not a tier total. A customer holding two
	 * paperbacks already has the $1.99 tier-2 discount, so adding the third
	 * gains 3.98 - 1.99 = $1.99, not $3.98. Quoting the tier total there
	 * would be a claim the invoice contradicts.
	 *
	 * Mixed-format carts follow the SAME suppression rule the messaging and
	 * the PHP fee logic already apply: tier 2 is genuinely not granted in a
	 * mixed cart, so it counts as 0 on both sides of the subtraction. Tier 3
	 * is always granted, mixed or not.
	 *
	 * Returns 0 when there is nothing honest to claim, and the caller then
	 * renders the plain label with no savings clause at all.
	 */
	function crossSellSavings(format, count, isMixedFormat) {
		var rules = (window.bhpDrawerData && window.bhpDrawerData.bundleRules && window.bhpDrawerData.bundleRules[format]) || null;
		if (!rules) {
			return 0;
		}
		function discountAt(tier) {
			if (tier < 2) {
				return 0;
			}
			if (2 === tier && isMixedFormat) {
				return 0; // not actually applied in a mixed cart
			}
			var rule = rules[tier] || rules[String(tier)];
			return rule ? parseFloat(rule.discount) || 0 : 0;
		}
		var next = count + 1;
		if (next > 3) {
			return 0;
		}
		var delta = discountAt(next) - discountAt(count);
		return delta > 0 ? delta : 0;
	}

	/**
	 * ═══════════════════════════════════════════════════════════════════
	 * ⭐ 1.8.25 (2026-08-05) — CYCLE144-LD-41. WHICH TITLE IS OFFERED.
	 * ═══════════════════════════════════════════════════════════════════
	 *
	 * THE DEFECT, stated as arithmetic rather than as an opinion. Until
	 * 1.8.24 the offer was chosen INSIDE the per-format messaging loop: for
	 * the first format in the cart, the first catalog title missing from
	 * THAT FORMAT's distinct list. On a single-format cart that is correct
	 * and nothing below changes it. On a MIXED cart it is wrong:
	 *
	 *     cart:      Mariana paperback + Everest hardcover
	 *     old offer: Everest PAPERBACK  (missing from distinct.paperback)
	 *     result:    3 books, still 2 distinct adventures, still NOT a
	 *                complete collection, still NOT free shipping
	 *
	 * The customer was being asked to buy a second copy of a story they
	 * already own, one step away from the collection. `completes_collection`
	 * correctly reported `false` for that offer, so the button was honest --
	 * it was the SELECTION that was wrong, not the claim about it.
	 *
	 * THE RULE, in two passes:
	 *
	 *   PASS 1 -- offer a title the cart does not hold in ANY format.
	 *             `adventures` is the distinct-title list across both
	 *             formats, which is the same list `bhp_bundle_shipping_
	 *             amount()` reads server-side through `is_complete_
	 *             collection`. Offering from it is what makes the free-
	 *             shipping claim on the button TRUE rather than merely
	 *             well-intentioned.
	 *
	 *   PASS 2 -- only when every adventure is already owned somewhere:
	 *             fall back to the pre-1.8.25 behaviour and complete a
	 *             FORMAT set (e.g. Mariana PB + Everest PB + Amazon HC is
	 *             already a free-shipping collection; offering Amazon PB
	 *             still earns a real tier-3 discount and is still true).
	 *
	 * FORMAT CHOICE (chief-of-staff direction, 2026-08-05): match the
	 * cart's MAJORITY format, counting distinct titles -- the same unit
	 * every other rule in this module counts. **A 1-1 mixed cart is a tie
	 * and defaults to PAPERBACK**, the lower-commitment ask. Hardcover only
	 * wins when the cart is strictly more hardcover than paperback.
	 *
	 * ⛔ THIS FUNCTION CHOOSES. IT CLAIMS NOTHING. Every figure on the
	 *    resulting button still comes from `crossSellSavings()` and the
	 *    server's `bhp_bundle_freeship_copy()`; `completes_collection` is
	 *    still the same distinct-TITLE test it was in 1.8.24, evaluated
	 *    against whichever title this function picked. No price, discount,
	 *    tier or shipping figure is read, written or invented here.
	 *
	 * ⛔ AN EMPTY CART RETURNS null, explicitly. The old code got that for
	 *    free from a `count === 0` guard in the messaging loop it lived in;
	 *    lifting the selection out of that loop means the guard has to be
	 *    written down. Without it a cart with no adventures in it would be
	 *    offered "the first title in the catalog", which is a recommendation
	 *    engine, not a cross-sell.
	 *
	 * @param {Object} distinct     {paperback: [titleKey], hardcover: [...]}
	 * @param {Array}  adventures   distinct titles across BOTH formats
	 * @param {boolean} isMixedFormat
	 * @param {boolean} hasUnrelated
	 * @return {Object|null}
	 */
	/**
	 * ⭐⭐⭐ 1.8.65 — PASS 0: THE COLOURING OFFER.
	 *
	 * Andrew, carrier item 186, naming the two offers the surviving cart
	 * surface should carry: "add the coloring book, add the next chapter book
	 * etc." ⛔ Only the second existed until now.
	 *
	 * ⭐ ONE OFFER AT A TIME — Boromir's rail. This returns a single offer or
	 *    null, exactly as the adventure passes do, and the rail renders
	 *    exactly one box. ⛔ It is NOT a second box and NOT a stack of offers.
	 *
	 * ⭐ THE ORDER, AND WHY IT IS THIS ORDER, so nobody re-derives it:
	 *
	 *      1. COMPLETING THE COLLECTION WINS. If an adventure cross-sell would
	 *         take the cart from two adventures to three, that offer earns
	 *         `FD-583` FREE SHIPPING, and free shipping is the strongest, most
	 *         concrete thing this rail can truthfully say. It also carries
	 *         Andrew's own approved free-shipping clause.
	 *      2. THEN THE COLOURING BOOK, when the cart holds the chapter book it
	 *         pairs with and does not already hold it.
	 *      3. THEN the ordinary adventure cross-sell, unchanged.
	 *
	 * ⛔ IT NEVER OFFERS SOMETHING ALREADY IN THE CART — every component of
	 *    the offer's chapter side must be present AND the colouring book must
	 *    be absent. That is a real id test against the Store API cart, not a
	 *    title test.
	 *
	 * ⛔ AND IT CLAIMS NOTHING. `saving` arrives from the server, where it is
	 *    `bhp_offer_saving()` — the same function that creates the cart fee.
	 *    A row with no positive saving is not sent and cannot be offered.
	 *
	 * @param {Object} cart Store API cart response.
	 * @return {Object|null}
	 */
	function chooseColouringOffer(cart) {
		var offers = (window.bhpDrawerData && window.bhpDrawerData.colouringOffers) || [];
		if (!offers.length) {
			return null; // ⛔ No colouring product record on this environment.
		}

		var inCart = {};
		(cart.items || []).forEach(function (item) {
			inCart[parseInt(item.id, 10)] = true;
		});

		for (var i = 0; i < offers.length; i++) {
			var offer = offers[i];
			if (!offer || !offer.colouring_id || !(offer.saving > 0)) {
				continue;
			}
			if (inCart[parseInt(offer.colouring_id, 10)]) {
				continue; // Already has it.
			}
			var chapterIds = offer.chapter_ids || [];
			if (!chapterIds.length) {
				continue;
			}
			var hasEveryChapter = chapterIds.every(function (id) {
				return !!inCart[parseInt(id, 10)];
			});
			if (!hasEveryChapter) {
				continue;
			}
			return {
				format: 'colouring',
				title_key: offer.title_key,
				label: offer.label,
				cta: offer.cta,
				product_id: offer.product_id,
				variation_id: offer.variation_id || 0,
				savings: offer.saving,
				// ⛔ A colouring book is not an adventure, so it can never be
				//    the title that completes the collection. Stated, not
				//    left to a falsy default.
				completes_collection: false
			};
		}
		return null;
	}

	function chooseCrossSell(distinct, adventures, isMixedFormat, hasUnrelated, cart) {
		var catalog = (window.bhpDrawerData && window.bhpDrawerData.catalog) || {};

		if (!adventures.length) {
			return null;
		}

		/*
		 * ⭐ 1.8.65 — the colouring offer is chosen BEFORE the ordinary
		 *    adventure passes but AFTER the completes-the-collection case,
		 *    which is why the adventure choice is computed first and only
		 *    yielded to when it earns free shipping. See chooseColouringOffer().
		 */
		var colouringOffer = cart ? chooseColouringOffer(cart) : null;

		var majorityIsHardcover = distinct.hardcover.length > distinct.paperback.length;
		var formatOrder = majorityIsHardcover
			? ['hardcover', 'paperback']
			: ['paperback', 'hardcover'];

		/*
		 * ⭐ 1.8.57 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT) — a school-visit
		 *    session is PAPERBACK ONLY, so the cross-sell never offers a
		 *    hardcover on one. The flag is server-set in bundle-drawer.php from
		 *    the SAME predicate that refuses the add server-side, so the button
		 *    the drawer shows and the add the Store API accepts cannot disagree.
		 *
		 * ⛔ Falsy for every ordinary shopper: `formatOrder` is untouched and the
		 *    cross-sell behaves exactly as it did in 1.8.56.
		 */
		if (window.bhpDrawerData && window.bhpDrawerData.paperbackOnly) {
			formatOrder = ['paperback'];
		}

		function offer(format, titleKey) {
			var info = (catalog[format] || {})[titleKey];
			if (!info) {
				return null;
			}
			return {
				format: format,
				title_key: titleKey,
				label: info.label,
				product_id: info.product_id,
				variation_id: info.variation_id,
				// B4: the real delta, computed from the same rules table the
				// cart fee is built from.
				savings: crossSellSavings(format, distinct[format].length, isMixedFormat),
				/*
				 * ⭐ 1.8.24, UNCHANGED IN SUBSTANCE BY 1.8.25 — TRUE exactly
				 *    when adding THIS title takes the cart from two distinct
				 *    adventures to three, i.e. when it is the title that
				 *    earns free shipping.
				 *
				 * ⛔ IT IS A TITLE TEST, NOT A COUNT TEST, and that is
				 *    load-bearing. A cart holding Mariana PB + Mariana HC +
				 *    Everest PB has THREE books but TWO adventures; offering
				 *    the Amazon completes it, offering a format twin of
				 *    something already there does not. 1.8.25 is the fix for
				 *    the second half of that sentence: the flag was already
				 *    right, and now the OFFER it describes is too.
				 */
				completes_collection: !hasUnrelated
					&& 2 === adventures.length
					&& adventures.indexOf(titleKey) === -1
			};
		}

		/*
		 * ⭐ 1.8.65 — the two adventure passes are BYTE-UNCHANGED in substance.
		 *    What changed is that they now `break` into a local variable
		 *    instead of returning straight out, so the colouring offer can be
		 *    weighed against the result. Every title they can choose, the
		 *    order they choose it in, and the `offer()` payload are identical
		 *    to 1.8.64.
		 */
		var adventureOffer = null;

		// PASS 1 — a title missing from the cart entirely.
		for (var f = 0; f < formatOrder.length && !adventureOffer; f++) {
			var format = formatOrder[f];
			if (distinct[format].length >= 3) {
				continue; // cannot happen while a title is missing; cheap guard.
			}
			var keys = Object.keys(catalog[format] || {});
			for (var i = 0; i < keys.length; i++) {
				if (adventures.indexOf(keys[i]) === -1) {
					var missing = offer(format, keys[i]);
					if (missing) {
						adventureOffer = missing;
						break;
					}
				}
			}
		}

		// PASS 2 — every adventure is owned; complete a format set instead.
		for (var g = 0; g < formatOrder.length && !adventureOffer; g++) {
			var fmt = formatOrder[g];
			var count = distinct[fmt].length;
			if (0 === count || count >= 3) {
				continue;
			}
			var fmtKeys = Object.keys(catalog[fmt] || {});
			for (var j = 0; j < fmtKeys.length; j++) {
				if (distinct[fmt].indexOf(fmtKeys[j]) === -1) {
					var twin = offer(fmt, fmtKeys[j]);
					if (twin) {
						adventureOffer = twin;
						break;
					}
				}
			}
		}

		/*
		 * ⭐⭐ THE PRIORITY, IN THREE LINES. See chooseColouringOffer() for the
		 *     reasoning. ⛔ EXACTLY ONE OFFER IS RETURNED, ALWAYS.
		 *
		 * ⛔ FREE SHIPPING OUTRANKS THE COLOURING BOOK. A cart one adventure
		 *    short of the collection is offered the adventure, because that
		 *    offer earns `FD-583` free shipping and says so in Andrew's own
		 *    approved clause. Offering a coloring book at that exact moment
		 *    would trade a "your order ships free" for a "save $1.99".
		 */
		if (adventureOffer && adventureOffer.completes_collection) {
			return adventureOffer;
		}
		if (colouringOffer) {
			return colouringOffer;
		}
		return adventureOffer;
	}

	function formatMoneyPlain(amount) {
		var symbol = (window.bhpDrawerData && window.bhpDrawerData.currencySymbol) || '$';
		return symbol + amount.toFixed(2);
	}

	/**
	 * Bundle-progress message(s) + single cross-sell suggestion, computed
	 * directly from the Store API cart response already in hand — see the
	 * note at the top of includes/bundle-drawer.php for why this is
	 * computed here instead of a second server round-trip.
	 */
	function computeDrawerMeta(cart) {
		/*
		 * ⭐ 1.8.25 — the `catalog` lookup that used to be declared here moved
		 *    into chooseCrossSell(), which is now its only reader. Leaving an
		 *    unread declaration behind would suggest this function still
		 *    decides what to offer, and it no longer does.
		 */
		var progressCopy = (window.bhpDrawerData && window.bhpDrawerData.progressCopy) || {};
		var savedCopy = (window.bhpDrawerData && window.bhpDrawerData.savedCopy) || {};
		var distinct = { paperback: [], hardcover: [] };

		(cart.items || []).forEach(function (item) {
			var match = identifyCartItem(item);
			if (!match) {
				return;
			}
			if (distinct[match.format].indexOf(match.titleKey) === -1) {
				distinct[match.format].push(match.titleKey);
			}
		});

		/*
		 * ⭐ 1.8.24 — MOVED UP FROM BELOW, unchanged in substance.
		 *
		 * `adventures` (distinct titles across BOTH formats) and
		 * `hasUnrelated` used to be computed after the per-format messaging
		 * loop, because nothing before the loop needed them. `freeShipLeads`
		 * now does: the loop has to know whether the free-shipping nudge is
		 * about to lead, so it can suppress the duplicate "add the final
		 * adventure" ask. Same code, same inputs, same results, read earlier.
		 */
		var adventures = [];
		['paperback', 'hardcover'].forEach(function (format) {
			distinct[format].forEach(function (titleKey) {
				if (adventures.indexOf(titleKey) === -1) {
					adventures.push(titleKey);
				}
			});
		});

		var addonIds = (window.bhpDrawerData && window.bhpDrawerData.addonProductIds) || [];
		var hasUnrelated = (cart.items || []).some(function (item) {
			if (identifyCartItem(item)) {
				return false;
			}
			return addonIds.indexOf(parseInt(item.id, 10)) === -1;
		});

		// True exactly when the two-adventure free-shipping nudge will render.
		var freeShipLeads = !hasUnrelated && 2 === adventures.length;

		var messages = [];
		var crossSell = null;

		// Mixed-format messaging rule (Overnight Conversion Sprint, Priority
		// 7 -- supersedes the old blanket "mixed = no messaging at all"
		// guard). Matches the PHP-side rule in bhp_bundle_apply_discount_
		// fees() / bhp_bundle_print_progress_messages():
		// - A "you saved $X with your 2-book set" claim is suppressed
		//     once mixed, because that discount is genuinely NOT applied
		//     in a mixed cart (it would be a lie to show it).
		// - The "add another X and save $Y" (count=1) message is
		//     suppressed once mixed, because that 2-book discount is not
		//     reachable while the opposite format is present.
		// - The "add the final adventure to complete the series" (count=2)
		//     message and the "Best Value -- Complete Set" (count=3)
		//     message both stay, mixed or not: reaching the complete 3-book
		//     set for a format ALWAYS earns that discount regardless of
		//     what else is in the cart, so both remain fully accurate.
		// - Cross-sell toward completing a format to 3 also stays valid
		//     when mixed, for the same reason.
		var isMixedFormat = distinct.paperback.length > 0 && distinct.hardcover.length > 0;

		['paperback', 'hardcover'].forEach(function (format) {
			var count = distinct[format].length;
			if (0 === count) {
				return;
			}
			if (1 === count && isMixedFormat) {
				// "Add another and save" is not reachable while mixed -- skip.
			} else if (2 === count && isMixedFormat) {
				// Skip the "you saved" claim (not actually applied), but the
				// "complete the series" progress message below still holds.
				if (progressCopy[format] && progressCopy[format][count]) {
					messages.push(progressCopy[format][count]);
				}
			} else {
				if (2 === count && savedCopy[format]) {
					messages.push(savedCopy[format]);
				}
				/*
				 * ⭐ 1.8.24 — the ONE suppression. At count===2 with the
				 *    free-shipping nudge about to lead, this line and the
				 *    nudge make the identical ask ("add the final
				 *    adventure") back to back; Andrew asked for the
				 *    shipping one. Every other count is untouched, and this
				 *    line returns unchanged the moment the cart is not one
				 *    step from free shipping.
				 */
				if (2 === count && freeShipLeads) {
					// suppressed in favour of the free-shipping nudge
				} else if (progressCopy[format] && progressCopy[format][count]) {
					messages.push(progressCopy[format][count]);
				}
			}
		});

		/*
		 * ⭐ 1.8.25 — THE SELECTION MOVED OUT OF THE MESSAGING LOOP.
		 *
		 * It used to live inside the `['paperback','hardcover']` loop above,
		 * which is what made it format-first: the loop's job is to build one
		 * message per format, and choosing the cross-sell inside it silently
		 * inherited that ordering. Deciding which ADVENTURE the customer is
		 * missing is a whole-cart question, so it is now asked once, about
		 * the whole cart. The loop above is otherwise untouched — every
		 * message, every suppression and every `savedCopy` line is
		 * byte-identical to 1.8.24.
		 */
		crossSell = chooseCrossSell(distinct, adventures, isMixedFormat, hasUnrelated, cart);

		// Tiers exposed for renderDrawer()'s per-line-item "included in your
		// savings" notes and summary math -- same 0/2/3 values the PHP side
		// uses, with the same mixed-format suppression already applied
		// above (a tier-2 format inside a mixed cart reports its RAW
		// distinct-title count here, e.g. 2, not a suppressed 0 -- callers
		// must apply the same isMixedFormat check themselves for tier 2,
		// exactly as this function just did for messaging).
		var tiers = {
			paperback: distinct.paperback.length >= 3 ? 3 : (distinct.paperback.length >= 2 ? 2 : 0),
			hardcover: distinct.hardcover.length >= 3 ? 3 : (distinct.hardcover.length >= 2 ? 2 : 0)
		};

		/*
		 * ═══════════════════════════════════════════════════════════════
		 * ⭐ 1.8.23 — THE FREE-SHIPPING LINE, mirroring the PHP side exactly.
		 * ═══════════════════════════════════════════════════════════════
		 *
		 * Counterpart of the block at the end of
		 * bhp_bundle_print_progress_messages() in includes/bundle-cart.php.
		 * Same trigger (distinct ADVENTURES across both formats, not books,
		 * not per format), same suppression (nothing unrelated in the cart),
		 * same two strings — which are localized from
		 * bhp_bundle_freeship_copy() rather than written here, so the drawer
		 * and the cart page cannot drift apart.
		 *
		 * ⛔ THE ADD-ON IS NOT AN UNRELATED ITEM. Its product IDs are
		 *    localized in precisely so a $5 digital activity book cannot
		 *    silence a message the server is still honouring.
		 */
		/*
		 * ⭐ 1.8.24 — `adventures`, `addonIds` and `hasUnrelated` USED to be
		 *    declared here. They are now computed above the per-format
		 *    messaging loop, unchanged, because `freeShipLeads` needs them
		 *    before the loop runs. Nothing was added to or removed from the
		 *    calculation itself; only its position moved.
		 */

		/*
		 * ═══════════════════════════════════════════════════════════════
		 * ⭐ 1.8.24 (2026-08-05) — THE FREE-SHIPPING LINE NOW *LEADS*.
		 * ═══════════════════════════════════════════════════════════════
		 *
		 * Andrew Signore, 2026-08-05: the two-book state was "supposed to
		 * say the Free Shipping info". In 1.8.23 the nudge was PUSHED, so
		 * on a 2-paperback cart it arrived third, under two discount
		 * sentences:
		 *
		 *   1. "You saved $1.99 with your 2-book paperback set."
		 *   2. "Add the final adventure to complete the collection and
		 *       save $3.98 total."
		 *   3. "Add the final adventure and your order ships free."
		 *
		 * Two changes, both minimal:
		 *
		 *   · UNSHIFT, not push. The shipping fact is now the first line a
		 *     customer reads in both the 2-book and the 3-book state.
		 *
		 *   · The count===2 progress line is SUPPRESSED while the nudge is
		 *     showing, because lines 2 and 3 above make the IDENTICAL ask
		 *     ("add the final adventure") one after the other, and Andrew
		 *     asked for the shipping one. `freeShipLeads` is computed
		 *     before the per-format loop for exactly that reason.
		 *
		 * ⛔ NOT ONE STRING IS REWRITTEN, and the "You saved $X with your
		 *    2-book set" line SURVIVES: it reports a discount already
		 *    applied, which is different information from an ask, and
		 *    deleting it would remove a true statement about the customer's
		 *    money. The suppressed line's own $3.98/$4.98 total is still
		 *    reachable — it renders again the moment the cart is no longer
		 *    one step from free shipping (e.g. an unrelated item present).
		 */
		var freeShipCopy = (window.bhpDrawerData && window.bhpDrawerData.freeShipCopy) || {};
		if (!hasUnrelated) {
			if (2 === adventures.length && freeShipCopy.nudge) {
				messages.unshift(freeShipCopy.nudge);
			} else if (adventures.length >= 3 && freeShipCopy.earned) {
				messages.unshift(freeShipCopy.earned);
			}
		}

		return {
			messages: messages,
			cross_sell: crossSell,
			tiers: tiers,
			is_mixed_format: isMixedFormat,
			distinct_adventures: adventures.length,
			has_unrelated: hasUnrelated
		};
	}

	function money(minorUnitAmount, minorUnit) {
		var divisor = Math.pow(10, minorUnit || 2);
		return '$' + (Number(minorUnitAmount) / divisor).toFixed(2);
	}

	/**
	 * Which savings tier a cart item's format actually qualifies for,
	 * honoring the same mixed-format suppression the fee/messaging logic
	 * already applies: a raw tier-2 becomes "not qualifying" once the cart
	 * is mixed, but tier-3 (complete set) always stands (Priority 7).
	 */
	function effectiveTierFor(format, meta) {
		var tier = (meta.tiers && meta.tiers[format]) || 0;
		if (2 === tier && meta.is_mixed_format) {
			return 0;
		}
		return tier;
	}

	/* ─────────────────────────────────────────────────────────────────
	 * ⭐ 1.8.27 — IS THIS DRAWER LINE THE FREE ACTIVITY BOOK?
	 *
	 * ⛔ IT ASKS THE ADD-ON MODULE FOR THE PRODUCT ID rather than carrying
	 *    a second copy of it, and it asks `meta` for the collection state
	 *    rather than re-counting the cart. Both are already computed once
	 *    per render by code the drawer owns.
	 *
	 * ⛔ NON-FATAL WHEN THE MODULE IS ABSENT, which is the normal state
	 *    whenever the add-on product does not resolve: no module, no id,
	 *    no free rendering, drawer unchanged.
	 * ───────────────────────────────────────────────────────────────── */
	function isFreeAddonLine(item, meta) {
		var mod = window.bhpAddonUpsell;
		var data = window.bhpAddonUpsellData;
		if (!mod || !mod.productId || !data || !data.freeEnabled) {
			return false;
		}
		if (parseInt(item.id, 10) !== parseInt(mod.productId, 10)) {
			return false;
		}
		/*
		 * ⭐ 1.8.36 — >= 1, NOT >= 3. The offer widened from collections to
		 *    ANY book purchase on Andrew Signore's 2026-08-06 ruling. This
		 *    is the JS mirror of `evaluate_cart()['has_any_book']`; the
		 *    server-side threshold moved in the same release and the two
		 *    must stay equal or the drawer will print "FREE" beside a line
		 *    the invoice charges for, or the reverse.
		 *
		 * SUPERSEDED, kept so the movement is visible:
		 *     return !!meta && meta.distinct_adventures >= 3;
		 */
		return !!meta && meta.distinct_adventures >= 1;
	}

	function freeAddonCopy(key, fallback) {
		var data = window.bhpAddonUpsellData;
		var copy = (data && data.freeCopy) || {};
		return copy[key] || fallback;
	}

	function itemQualifyingNote(item, meta) {
		if (isFreeAddonLine(item, meta)) {
			return freeAddonCopy('item_note', 'FREE with your collection');
		}
		var match = identifyCartItem(item);
		if (!match) {
			return '';
		}
		var tier = effectiveTierFor(match.format, meta);
		if (2 === tier) {
			return 'Included in your 2-book savings';
		}
		if (3 === tier) {
			return 'Included in your complete-set savings';
		}
		return '';
	}

	function renderDrawer(cart, meta) {
		var itemsEl = qs('.bhp-cart-drawer__items', drawer);
		var messageEl = qs('.bhp-cart-drawer__message', drawer);
		var crossSellEl = qs('.bhp-cart-drawer__crosssell', drawer);
		var summaryEl = qs('.bhp-cart-drawer__summary', drawer);

		itemsEl.innerHTML = '';
		(cart.items || []).forEach(function (item) {
			var row = document.createElement('div');
			row.className = 'bhp-cart-drawer__item';
			var img = item.images && item.images[0] ? item.images[0].thumbnail : '';
			var formatLabel = item.variation && item.variation[0] ? item.variation[0].value : '';
			var note = itemQualifyingNote(item, meta);
			row.innerHTML =
				'<img class="bhp-cart-drawer__item-img" src="' +
				img +
				'" alt="" />' +
				'<div class="bhp-cart-drawer__item-info">' +
				'<div class="bhp-cart-drawer__item-title">' +
				item.name +
				'</div>' +
				(formatLabel ? '<div class="bhp-cart-drawer__item-format">' + formatLabel + '</div>' : '') +
				(note ? '<span class="bhp-cart-drawer__item-note">' + note + '</span>' : '') +
				'<div class="bhp-cart-drawer__item-qty">' +
				'<button type="button" data-qty-down data-key="' +
				item.key +
				'" data-qty="' +
				(item.quantity - 1) +
				'" aria-label="Decrease quantity of ' + item.name + '">&minus;</button>' +
				'<span>' +
				item.quantity +
				'</span>' +
				'<button type="button" data-qty-up data-key="' +
				item.key +
				'" data-qty="' +
				(item.quantity + 1) +
				'" aria-label="Increase quantity of ' + item.name + '">+</button>' +
				'<button type="button" class="bhp-cart-drawer__item-remove" data-remove data-key="' +
				item.key +
				'" aria-label="Remove ' + item.name + ' from cart">Remove</button>' +
				'</div>' +
				'</div>' +
				/*
				 * 1.8.27: a granted activity book reads FREE, not "$0.00".
				 * The branch is keyed off the SAME predicate as the note
				 * above, so the price cell and the note cannot disagree.
				 */
				'<div class="bhp-cart-drawer__item-price">' +
				(isFreeAddonLine(item, meta)
					? freeAddonCopy('price', 'FREE')
					: money(item.totals.line_total, item.totals.currency_minor_unit)) +
				'</div>';
			itemsEl.appendChild(row);
		});

		messageEl.innerHTML = '';
		(meta.messages || []).forEach(function (msg) {
			var p = document.createElement('p');
			p.className = 'bhp-cart-drawer__message-line';
			p.textContent = msg;
			messageEl.appendChild(p);
		});

		crossSellEl.innerHTML = '';
		if (meta.cross_sell) {
			var cs = meta.cross_sell;
			var box = document.createElement('div');
			box.className = 'bhp-cart-drawer__crosssell-box';
			/*
			 * B4 (2026-08-03). Andrew, walk-3: "Add this adventure - save X
			 * amount of money". A HYPHEN, not an em dash -- the sitewide
			 * em-dash purge (commit 3ef65be) applies to new copy too.
			 *
			 * The clause is APPENDED to the existing label and appears only
			 * when a real, non-zero saving exists. A cross-sell that earns
			 * nothing (e.g. the second title in a mixed-format cart, where
			 * the 2-book tier is genuinely not granted) renders the original
			 * "Add This Adventure" untouched, because a "save $0.00" button
			 * would be worse than no claim at all.
			 */
			/*
			 * ⭐ 1.8.24 (2026-08-05) — CYCLE144-LD-14. Andrew: on the
			 *    two-book state this button "supposed to say the Free
			 *    Shipping info", not "save $1.99".
			 *
			 *    When this title is the one that completes the collection,
			 *    the savings clause is REPLACED by the free-shipping clause
			 *    from `bhp_bundle_freeship_copy()` — the same function the
			 *    cart, the drawer message and the checkout panel all read,
			 *    so one filter still changes every surface at once. In every
			 *    other state the B4 savings clause is byte-unchanged.
			 *
			 *    Falls back to the savings clause if the server did not send
			 *    `cta_clause` (an older plugin build), so the button can
			 *    never render bare on a real saving.
			 */
			/*
			 * ⭐ 1.8.65 — the colouring offer brings its OWN button word, from
			 *    `bhp_colouring_draft_copy('panel_cta')`, because "Add This
			 *    Adventure" is false of a coloring book. Every other offer is
			 *    byte-unchanged: absent `cs.cta`, this is the 1.8.64 literal.
			 *
			 * ⛔ THE SAVINGS CLAUSE IS STILL APPENDED BY THE SAME CODE BELOW,
			 *    from the same live figure. No offer carries a number in its
			 *    own copy.
			 */
			var ctaLabel = cs.cta || 'Add This Adventure';
			var freeShipClause = (window.bhpDrawerData
				&& window.bhpDrawerData.freeShipCopy
				&& window.bhpDrawerData.freeShipCopy.cta_clause) || '';
			if (cs.completes_collection && freeShipClause) {
				ctaLabel += freeShipClause;
			} else if (cs.savings > 0) {
				ctaLabel += ' - Save ' + formatMoneyPlain(cs.savings);
			}
			box.innerHTML =
				'<span class="bhp-cart-drawer__crosssell-label">' +
				cs.label +
				'</span>' +
				'<button type="button" class="button bhp-cart-drawer__crosssell-btn" data-crosssell-add ' +
				'data-product-id="' +
				cs.product_id +
				'" data-variation-id="' +
				(cs.variation_id || 0) +
				'" data-title-key="' +
				cs.title_key +
				'" data-crosssell-savings="' +
				(cs.savings > 0 ? cs.savings.toFixed(2) : '0.00') +
				'"></button>';
			// textContent, so the label can never be parsed as markup.
			box.querySelector('[data-crosssell-add]').textContent = ctaLabel;
			/*
			 * R4 (2026-08-03). The gold eyebrow, so this box matches the
			 * approved checkout treatment element for element.
			 *
			 * ⛔ NO NEW COPY WAS WRITTEN. The string is the checkout module's
			 *    OWN heading, served from `bhp_bundle_checkout_upsell_copy()`
			 *    — one function, one string, both surfaces. If the eyebrow is
			 *    ever missing from `bhpDrawerData` the node is simply not
			 *    rendered, and the box degrades to exactly what it was.
			 *
			 * Built with createElement + textContent and INSERTED FIRST,
			 * rather than added to the innerHTML string above, so a server
			 * string can never be parsed as markup on the way in.
			 */
			var csHeading = (window.bhpDrawerData && window.bhpDrawerData.crossSellHeading) || '';
			if (csHeading) {
				var headingEl = document.createElement('p');
				headingEl.className = 'bhp-cart-drawer__crosssell-heading';
				headingEl.textContent = csHeading;
				box.insertBefore(headingEl, box.firstChild);
			}
			crossSellEl.appendChild(box);
		}

		renderAddonUpsell(cart);

		renderSummary(cart, meta, summaryEl);

		checkAnalyticsMilestones(meta);
	}

	/**
	 * Surface 3 of 3 of the ACTIVITY BOOK checkbox order bump.
	 *
	 * ⭐ THE DRAWER OWNS NO PART OF THE CONTROL. It hands `addon-upsell.js`
	 *    a container, the cart it has just rendered, and its OWN Store API
	 *    transport — then gets out of the way. The checkbox markup, the
	 *    copy, the product id, the price and the analytics all live in that
	 *    one module, so the drawer, the cart page and the checkout are
	 *    literally the same control and cannot drift into three that merely
	 *    look alike.
	 *
	 * ⛔ THE TRANSPORT IS THE DRAWER'S, NOT `wp.data`. The drawer is not
	 *    React and holds no Blocks data store; it already owns a Store API
	 *    nonce and a refresh path. Introducing a second cart transport into
	 *    one component is how two views of one cart start disagreeing.
	 *
	 * ⛔ NON-FATAL BY CONSTRUCTION. If `addon-upsell.js` is not present —
	 *    which is the normal state whenever the add-on product does not
	 *    exist, including production today — this function does nothing and
	 *    the drawer renders exactly as it did before.
	 */
	function renderAddonUpsell(cart) {
		var addonEl = qs('.bhp-cart-drawer__addon', drawer);
		if (!addonEl) {
			return;
		}
		var mod = window.bhpAddonUpsell;
		if (!mod || typeof mod.renderInto !== 'function') {
			return;
		}
		/*
		 * The module keeps the last cart the drawer rendered, so its own
		 * change handler can ask "is it already in the cart?" without a
		 * second network round-trip.
		 */
		if (typeof mod.noteCart === 'function') {
			mod.noteCart(cart);
		}
		mod.renderInto(addonEl, cart, {
			add: function (productId) {
				return addItem(productId, 0, 1);
			},
			remove: function (key) {
				return removeItem(key);
			},
			refresh: function () {
				return refreshDrawer();
			}
		});
	}

	/**
	 * Transparent price breakdown (Overnight Conversion Sprint, Priority
	 * 5): every row is computed fresh from the live Store API cart
	 * response on every render, so there is no way for a stale discount,
	 * shipping amount, or total to survive a cart change. Replaces the
	 * old single mislabeled "Subtotal" line (which was actually
	 * cart.totals.total_price -- items + shipping + tax combined) and the
	 * static "Shipping calculated at checkout" text (which was never
	 * true here -- the flat rate is always known immediately).
	 */
	function renderSummary(cart, meta, summaryEl) {
		summaryEl.innerHTML = '';
		if (!cart.items || !cart.items.length) {
			return;
		}

		var minorUnit = cart.totals.currency_minor_unit;

		function addRow(label, value, modifier) {
			var row = document.createElement('div');
			row.className = 'bhp-cart-drawer__summary-row' + (modifier ? ' bhp-cart-drawer__summary-row--' + modifier : '');
			var labelEl = document.createElement('span');
			labelEl.textContent = label;
			var valueEl = document.createElement('span');
			valueEl.textContent = value;
			row.appendChild(labelEl);
			row.appendChild(valueEl);
			summaryEl.appendChild(row);
		}

		addRow('Books subtotal', money(cart.totals.total_items, minorUnit));

		var bundleFees = (cart.fees || []).filter(function (f) {
			return f.name.indexOf('Bundle Savings') === 0;
		});
		var totalSavingsMinor = 0;
		bundleFees.forEach(function (fee) {
			var format = fee.name.indexOf('Paperback') !== -1 ? 'paperback' : 'hardcover';
			var tier = effectiveTierFor(format, meta);
			var label = (3 === tier ? 'Complete-set savings' : '2-book savings') + ' (' + (format === 'paperback' ? 'Paperback' : 'Hardcover') + ')';
			addRow(label, money(fee.totals.total, minorUnit), 'savings');
			totalSavingsMinor += Number(fee.totals.total);
		});

		if (bundleFees.length) {
			addRow('Set price', money(Number(cart.totals.total_items) + totalSavingsMinor, minorUnit));
		}

		var shippingPackage = cart.shipping_rates && cart.shipping_rates[0];
		var selectedRate = shippingPackage && (shippingPackage.shipping_rates || []).filter(function (r) { return r.selected; })[0];
		var shippingMinor = selectedRate ? Number(selectedRate.price) : 0;

		/*
		 * ⭐ 1.8.55 — the row is not always called "Shipping".
		 *
		 * A parent who arrived from a school's pre-visit link and has the
		 * hand-delivery rate SELECTED is not being shipped anything, and this
		 * drawer said so anyway: `<span>Shipping</span><span>FREE</span>`.
		 * Founder-caught on production, on a phone, 2026-08-18 -- the same
		 * defect the WooCommerce Blocks totals row on the cart page was
		 * carrying, and one screen before the checkout that was already fixed.
		 *
		 * ⛔ THE TEST IS THE LIVE SELECTED RATE, NOT A SESSION FLAG BAKED INTO
		 *    THE PAGE. A flagged parent who deliberately picks ordinary
		 *    shipping still reads "Shipping" here, because that is what is
		 *    about to happen to their books. It also means this row is correct
		 *    on a cached page: the two labels are shipped to everyone and only
		 *    the Store API response decides between them.
		 *
		 * ⛔ NEITHER WORD IS AUTHORED HERE. Both come from PHP
		 *    (`bhp_school_pickup_totals_label()` via the
		 *    `bhp_bundle_drawer_ship_row_*` filters), so the drawer, the cart
		 *    totals row, the order-received page and every order email cannot
		 *    drift apart.
		 */
		var drawerData = window.bhpDrawerData || {};
		var shipRowLabel = drawerData.shipRowLabel || 'Shipping';
		if (
			selectedRate &&
			drawerData.shipRowPickupLabel &&
			drawerData.shipRowPickupMethod &&
			selectedRate.method_id === drawerData.shipRowPickupMethod
		) {
			shipRowLabel = drawerData.shipRowPickupLabel;
		}

		if (selectedRate) {
			/*
			 * ⭐ 1.8.23 — a zero rate is written FREE, never "$0.00 flat".
			 *
			 * ⛔ THE NUMBER STILL COMES FROM THE STORE API, NOT FROM A GUESS.
			 *    This reads the rate WooCommerce actually selected and only
			 *    changes how zero is spelled, so the drawer cannot claim free
			 *    shipping on a cart the checkout would charge for. If the
			 *    override ever fails to run, `shippingMinor` is non-zero here
			 *    and the row renders the real amount.
			 *
			 * The word itself is localized from bhp_bundle_shipping_display()
			 * (PHP) so the drawer, the cart page and the Collection page all
			 * spell it identically.
			 */
			var freeShipLabel = (window.bhpDrawerData && window.bhpDrawerData.freeShipLabel) || 'FREE';
			addRow(
				shipRowLabel,
				0 === shippingMinor ? freeShipLabel : money(shippingMinor, minorUnit) + ' flat',
				0 === shippingMinor ? 'free' : ''
			);
		} else {
			// No rate selected yet, so there is nothing to be hand-delivered
			// yet either. This branch stays the generic one, deliberately.
			addRow(shipRowLabel, 'Flat rate - enter address at checkout');
		}

		/**
		 * The side cart intentionally shows a pre-tax "Estimated total"
		 * (Books subtotal - bundle savings + shipping), not
		 * cart.totals.total_price -- that Store API field already includes
		 * tax, which is why the drawer used to silently show a tax-inclusive
		 * figure under a plain "Total" label. Tax varies by the customer's
		 * address (WooCommerce estimates it from a default/store-base
		 * address before checkout, e.g. shows Idaho Sales Tax with no
		 * address entered), so showing it here would be a number that
		 * changes again at checkout for reasons the drawer never explains.
		 * Tax is surfaced once, clearly, only at checkout.
		 */
		var estimatedTotalMinor = Number(cart.totals.total_items) + totalSavingsMinor + shippingMinor;
		addRow('Estimated total', money(estimatedTotalMinor, minorUnit), 'total');

		var taxNote = document.createElement('p');
		taxNote.className = 'bhp-cart-drawer__tax-note';
		/* F16 / CYCLE142-CX-011 (2026-08-03): the drawer says $53.98 and the
		   cart then says $56.92. The figure is honestly labelled "Estimated",
		   but the note under it did not say WHY the number would grow. It now
		   names the missing component instead of merely mentioning tax. */
		taxNote.textContent = 'Plus tax, calculated at checkout';
		summaryEl.appendChild(taxNote);
	}

	function checkAnalyticsMilestones(meta) {
		(meta.messages || []).forEach(function (msg) {
			if (msg.indexOf('Best Value') === 0) {
				pushEvent('complete_set_reached', {});
			} else if (msg.indexOf('You saved') === 0) {
				pushEvent('second_book_added', {});
			}
		});
	}

	function refreshDrawer() {
		return getCart().then(function (cart) {
			renderDrawer(cart, computeDrawerMeta(cart));
			updateFloatingCartButton(cart);
			updateExtraOpeners(cart);
			return cart;
		});
	}

	/**
	 * Total physical units in the cart -- sums each line's quantity rather
	 * than counting lines, so "Complete Collection" (3 line items, qty 1
	 * each) reads as 3, and one book at quantity 2 also reads as 2. This is
	 * the same canonical Store API cart response the drawer itself renders
	 * from, so the floating button can never drift out of sync with what
	 * the drawer shows -- there is no second, parallel cart count anywhere.
	 */
	function totalUnitsInCart(cart) {
		var total = 0;
		(cart.items || []).forEach(function (item) {
			total += item.quantity || 0;
		});
		return total;
	}

	/**
	 * Keeps the persistent floating "reopen cart" button in sync with the
	 * canonical cart on every refreshDrawer() call -- i.e. after every add,
	 * remove, and quantity change, and on the initial page-load priming
	 * fetch and the bfcache pageshow refetch below. No separate polling or
	 * duplicated cart state.
	 */
	function updateFloatingCartButton(cart) {
		if (!floatingBtn) {
			return;
		}
		var total = totalUnitsInCart(cart);
		var previousCount = floatingBadge ? floatingBadge.textContent : null;

		if (total > 0) {
			floatingBtn.classList.add('is-visible');
			floatingBtn.setAttribute(
				'aria-label',
				'Open cart, ' + total + (1 === total ? ' item' : ' items')
			);
		} else {
			floatingBtn.classList.remove('is-visible');
			floatingBtn.setAttribute('aria-label', 'Open cart');
		}

		if (floatingBadge) {
			floatingBadge.textContent = String(total);
			if (previousCount !== null && previousCount !== String(total)) {
				floatingBadge.classList.remove('bhp-floating-cart__badge--pulse');
				// Force reflow so removing/re-adding the class restarts the
				// CSS animation (a no-op if prefers-reduced-motion applies,
				// since the animation itself is gated off in that case).
				void floatingBadge.offsetWidth;
				floatingBadge.classList.add('bhp-floating-cart__badge--pulse');
			}
		}
	}

	/**
	 * Focus trap: while the drawer is open, Tab/Shift+Tab must cycle only
	 * through the drawer's own focusable elements. Without this, a keyboard
	 * user can tab past the last drawer control into page content that is
	 * still visually present behind the overlay (only hidden by z-index and
	 * a semi-transparent background, never actually removed from the DOM).
	 */
	function trapFocusKeydown(e) {
		if ('Escape' === e.key || 'Esc' === e.key) {
			closeDrawer();
			return;
		}
		if ('Tab' !== e.key) {
			return;
		}
		var panel = qs('.bhp-cart-drawer__panel', drawer);
		var focusable = focusableElements(panel);
		if (!focusable.length) {
			return;
		}
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}

	/**
	 * openDrawer() is only ever called right after a fresh add-to-cart or
	 * cross-sell add (interceptSingleProductForms, interceptBundleForms,
	 * the cross-sell click handler) -- there is no "just reopen to look"
	 * call path today. That means it is always safe to reset the body's
	 * scroll position to the top here: a customer who scrolled down while
	 * the drawer was already open (to review lower items, or the
	 * cross-sell box) never re-triggers openDrawer() by doing so -- only
	 * refreshDrawer() runs for qty/remove changes on an already-open
	 * drawer, which does not touch scroll position. Fixes a real
	 * production defect: after scrolling down once, a later add-to-cart
	 * could leave the newly-added first item scrolled out of view under
	 * the sticky "Your Cart" header.
	 */
	function openDrawer(source) {
		if (!drawer) {
			return;
		}
		lastFocusedElement = document.activeElement;
		drawer.classList.add('is-open');
		drawer.setAttribute('aria-hidden', 'false');
		document.body.classList.add('bhp-drawer-open');
		document.addEventListener('keydown', trapFocusKeydown);
		var body = qs('.bhp-cart-drawer__body', drawer);
		if (body) {
			body.scrollTop = 0;
		}
		var closeBtn = qs('.bhp-cart-drawer__close', drawer);
		if (closeBtn) {
			// Deferred so it runs after the current click's own focus
			// handling (e.g. a just-clicked add-to-cart button) settles.
			window.setTimeout(function () {
				closeBtn.focus();
			}, 0);
		}
		// `source` is a plain string passed only by callers that want it
		// recorded (e.g. the floating button passes 'floating_cart'); every
		// existing add-to-cart call path omits it, so the event payload is
		// unchanged for them -- this key only ever appears, never replaces
		// an existing one.
		pushEvent('side_cart_opened', 'string' === typeof source ? { source: source } : {});
		getCart().then(function (cart) {
			pushEvent('view_cart', {
				currency: cartCurrency(cart),
				value: cartItemsValue(cart),
				items: ga4ItemsFromCart(cart)
			});
		});
	}

	function closeDrawer() {
		if (!drawer) {
			return;
		}
		drawer.classList.remove('is-open');
		drawer.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('bhp-drawer-open');
		document.removeEventListener('keydown', trapFocusKeydown);
		if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
			lastFocusedElement.focus();
		}
		lastFocusedElement = null;
	}

	/**
	 * remove_from_cart (Phase 6) needs the line item's data BEFORE it's
	 * removed -- fetches the current cart fresh (not the last-rendered
	 * one, which could be stale after a rapid double-click) to find the
	 * matching line, pushes the event, then runs the actual removal.
	 */
	function pushRemoveFromCartThen(key, removeFn) {
		getCart().then(function (cart) {
			var line = (cart.items || []).filter(function (i) { return i.key === key; })[0];
			if (line) {
				pushEvent('remove_from_cart', {
					currency: cartCurrency(cart),
					value: ga4ItemFromCartLine(line).price * line.quantity,
					items: [ga4ItemFromCartLine(line)]
				});
			}
			return removeFn();
		}).catch(function () {
			return removeFn();
		});
	}

	function initDrawerEvents() {
		qsa('[data-bhp-drawer-close]', drawer).forEach(function (el) {
			el.addEventListener('click', closeDrawer);
		});

		drawer.addEventListener('click', function (e) {
			var t = e.target;

			if (t.matches('[data-qty-up], [data-qty-down]')) {
				var key = t.getAttribute('data-key');
				var qty = parseInt(t.getAttribute('data-qty'), 10);
				if (qty <= 0) {
					pushRemoveFromCartThen(key, function () {
						return removeItem(key).then(refreshDrawer);
					});
				} else {
					updateItemQuantity(key, qty).then(refreshDrawer);
				}
			}

			if (t.matches('[data-remove]')) {
				var removeKey = t.getAttribute('data-key');
				pushRemoveFromCartThen(removeKey, function () {
					return removeItem(removeKey).then(refreshDrawer);
				});
			}

			if (t.matches('[data-crosssell-add]')) {
				var productId = parseInt(t.getAttribute('data-product-id'), 10);
				var variationId = parseInt(t.getAttribute('data-variation-id'), 10) || 0;
				pushEvent('side_cart_cross_sell_clicked', { title_key: t.getAttribute('data-title-key') });
				addItem(productId, variationId, 1).then(function () {
					return refreshDrawer();
				}).then(function (cart) {
					var addedId = variationId || productId;
					var addedLine = (cart.items || []).filter(function (i) { return parseInt(i.id, 10) === addedId; })[0];
					pushEvent('add_to_cart', {
						source: 'cross_sell',
						currency: cartCurrency(cart),
						value: addedLine ? ga4ItemFromCartLine(addedLine).price : null,
						items: addedLine ? [ga4ItemFromCartLine(addedLine)] : []
					});
				});
			}
		});

		var checkoutBtn = qs('.bhp-cart-drawer__checkout', drawer);
		if (checkoutBtn) {
			checkoutBtn.addEventListener('click', function () {
				getCart().then(function (cart) {
					pushEvent('begin_checkout', {
						source: 'side_cart',
						currency: cartCurrency(cart),
						value: cartItemsValue(cart),
						items: ga4ItemsFromCart(cart)
					});
				});
			});
		}
	}

	/**
	 * Single-product "Add to Cart" forms: intercept the normal full-page
	 * submit so the customer stays on the product page and the drawer
	 * opens instead. Falls back to a real form submit if the Store API
	 * call fails for any reason.
	 */
	function interceptSingleProductForms() {
		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!form.matches || !form.matches('form.cart')) {
				return;
			}
			var addToCartField = form.querySelector('input[name="add-to-cart"], button[name="add-to-cart"]');
			if (!addToCartField) {
				return;
			}

			e.preventDefault();
			var productId = parseInt(addToCartField.value || addToCartField.getAttribute('value'), 10);
			var variationInput = form.querySelector('input[name="variation_id"]');
			var variationId = variationInput ? parseInt(variationInput.value, 10) || 0 : 0;
			var qtyInput = form.querySelector('input[name="quantity"]');
			var quantity = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

			addItem(productId, variationId, quantity)
				.then(function () {
					return refreshDrawer();
				})
				.then(function (cart) {
					var addedId = variationId || productId;
					var addedLine = (cart.items || []).filter(function (i) { return parseInt(i.id, 10) === addedId; })[0];
					pushEvent('add_to_cart', {
						source: 'product_page',
						currency: cartCurrency(cart),
						value: addedLine ? ga4ItemFromCartLine(addedLine).price * quantity : null,
						items: addedLine ? [ga4ItemFromCartLine(addedLine)] : []
					});
					openDrawer();
				})
				.catch(function (err) {
					window.console && console.error('BHP drawer add-to-cart failed, falling back to normal submit', err);
					HTMLFormElement.prototype.submit.call(form);
				});
		});
	}

	/**
	 * ⭐⭐⭐ 1.8.65 — CARRIER ITEM 188. ADD TO CART OPENS THE PANEL.
	 * ========================================================================
	 *
	 * Andrew Signore, ~05:4x−0600 2026-08-21, read first-hand at source by the
	 * agent that wrote this function (`FOUNDER-VERBATIM-2026-08-05-PRODUCTION-
	 * DEPLOY-AUTHORIZATION.md` line 818, G: mount, NOT relayed):
	 *
	 *   "Well if we keep add to cart - lets not do the cart page- we made the
	 *    cart side panel for a reason with the upsells and the totals in
	 *    their- they go to checkout then add the coupon"
	 *
	 * ⭐ THE PRODUCT PAGE'S ADD TO CART IS AN ANCHOR, NOT A `form.cart`, which
	 *    is why `interceptSingleProductForms()` above never saw it. It is an
	 *    anchor because the format rail swaps its href when the shopper picks
	 *    a format, and 1.19.281 keeps it that way — this adds a click handler,
	 *    it does not convert the control into a form.
	 *
	 * ⛔ IT IS THE SAME MACHINERY, DELIBERATELY. `addItem()` → `refreshDrawer()`
	 *    → `openDrawer()`, the identical sequence the form path has used since
	 *    the Overnight Conversion Sprint, and the identical `add_to_cart`
	 *    analytics event with the same `product_page` source. ⛔ NO SECOND
	 *    ADD PATH AND NO SECOND PANEL.
	 *
	 * ⛔ DIRECT-BUY IS UNREACHABLE FROM HERE. The collection / bundle "GET
	 *    THE…" controls are `form.bhp-bundle-form` POSTs handled by
	 *    `interceptBundleForms()`, and the theme never puts
	 *    `data-bhp-cart-add` on them. ⭐ He walked that path himself and it
	 *    still lands on /checkout/.
	 *
	 * ⛔ IT FALLS BACK BY NAVIGATING, NEVER BY SWALLOWING THE CLICK. If the
	 *    Store API refuses for any reason, the anchor's own href is followed —
	 *    a real add-to-cart request, which `inc/purchase-flow.php` keeps off
	 *    the cart page. A shopper never ends a click with nothing having
	 *    happened.
	 *
	 * ⛔ A DISABLED / OUT-OF-STOCK CTA IS LEFT ALONE (`is-disabled`,
	 *    `aria-disabled`), and so is any modified click (new tab, middle
	 *    click), which belongs to the browser and not to us.
	 */
	function interceptCartAddLinks() {
		document.addEventListener('click', function (e) {
			var link = e.target && e.target.closest ? e.target.closest('[data-bhp-cart-add]') : null;
			if (!link) {
				return;
			}
			// The browser's own gestures stay the browser's.
			if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
				return;
			}
			if (link.classList.contains('is-disabled') || 'true' === link.getAttribute('aria-disabled')) {
				return;
			}

			var productId = parseInt(link.getAttribute('data-product-id'), 10) || 0;
			var variationId = parseInt(link.getAttribute('data-variation-id'), 10) || 0;
			if (!productId) {
				return; // Not ours to handle; let the href do its job.
			}

			e.preventDefault();

			var href = link.getAttribute('href') || '';
			var fallback = function () {
				if (href && '#' !== href) {
					window.location.assign(href);
				}
			};

			addItem(productId, variationId, 1)
				.then(function () {
					return refreshDrawer();
				})
				.then(function (cart) {
					var addedId = variationId || productId;
					var addedLine = (cart.items || []).filter(function (i) { return parseInt(i.id, 10) === addedId; })[0];
					pushEvent('add_to_cart', {
						source: 'product_page',
						currency: cartCurrency(cart),
						value: addedLine ? ga4ItemFromCartLine(addedLine).price : null,
						items: addedLine ? [ga4ItemFromCartLine(addedLine)] : []
					});
					openDrawer('add_to_cart');
				})
				.catch(function (err) {
					window.console && console.error('BHP drawer add-to-cart link failed, following the href instead', err);
					fallback();
				});
		});
	}

	/**
	 * Bundle offer forms (Any-2 and Complete-Set) from the Shop the Series
	 * page: intercept and add each selected title via the Store API
	 * instead of the original full-page POST + redirect-to-cart, so the
	 * customer stays on the bundle page and sees the drawer instead.
	 */
	/**
	 * Adds each title's product to the cart ONE AT A TIME, in sequence --
	 * never via Promise.all. Firing multiple Store API add-item calls
	 * concurrently against the same cart session is a real "lost update"
	 * race: each request's server-side handler works from its own read of
	 * the session cart, so whichever request finishes last silently
	 * overwrites the others' additions instead of merging with them.
	 * Discovered live on the premium landing page's "Add Complete Set"
	 * button (Staging Refinement Phase 2 QA) -- clicking it with an empty
	 * cart added only 1 of 3 titles despite all three POSTs returning 201.
	 * This affects every complete-set/any-2 add path, not just the new
	 * "smart" one, so it's fixed here once for all callers.
	 */
	function addTitlesSequentially(keys, catalog, format) {
		var chain = Promise.resolve();
		keys.forEach(function (key) {
			var info = (catalog[format] || {})[key];
			if (!info) {
				return;
			}
			chain = chain.then(function () {
				return addItem(info.product_id, info.variation_id, 1);
			});
		});
		return chain;
	}

	/**
	 * Add several titles in a SINGLE Store API request via the /batch endpoint
	 * (Audit remediation 2026-07-18, performance). The batch endpoint runs each
	 * add-item server-side, in order, inside one cart-session context -- so it
	 * carries the same "no lost update" guarantee that motivated
	 * addTitlesSequentially() while collapsing three network round-trips into
	 * one. Every sub-response must be 2xx or the whole thing is treated as a
	 * failure and the caller falls back to the proven sequential path. Never
	 * fires for zero titles.
	 */
	function addItemsBatch(keys, catalog, format) {
		// Which of these keys map to a real product this format? (Independent
		// of the nonce, so we can bail out early for a zero-title batch.)
		var addable = keys.filter(function (key) {
			return !!(catalog[format] || {})[key];
		});
		if (!addable.length) {
			return Promise.resolve();
		}
		// The Store API /batch endpoint validates the Nonce on EACH inner
		// sub-request, not just the outer request -- an outer-only nonce yields
		// 401 "woocommerce_rest_missing_nonce" on every sub-response (verified
		// on staging 2026-07-18). So ensure the nonce is in hand first, then
		// stamp it into every sub-request's headers.
		return ensureNonce().then(function () {
			var requests = addable.map(function (key) {
				var info = catalog[format][key];
				return {
					method: 'POST',
					path: '/wc/store/v1/cart/add-item',
					body: { id: info.variation_id ? info.variation_id : info.product_id, quantity: 1 },
					headers: { 'Content-Type': 'application/json', 'Nonce': nonce }
				};
			});
			return doFetch('/batch', { method: 'POST', body: JSON.stringify({ requests: requests }) }).then(function (res) {
				var responses = res && res.responses;
				var allOk = Array.isArray(responses) && responses.length === requests.length && responses.every(function (r) {
					return r && r.status >= 200 && r.status < 300;
				});
				if (!allOk) {
					throw new Error('Store API /batch add-item returned a non-2xx sub-response');
				}
				return res;
			});
		});
	}

	/**
	 * Add titles the fast way (one /batch request), falling back automatically
	 * to the sequential one-at-a-time path if /batch is unavailable or reports
	 * any partial failure. This is the single entry point every add-to-cart
	 * path below now uses, so the optimization and its safety net apply
	 * uniformly (complete-set, smart complete-set, any-2, single).
	 */
	function addTitles(keys, catalog, format) {
		return addItemsBatch(keys, catalog, format).catch(function (err) {
			window.console && console.warn('BHP batch add-to-cart failed, falling back to sequential', err);
			return addTitlesSequentially(keys, catalog, format);
		});
	}

	/**
	 * Immediate button feedback + duplicate-click prevention for the bundle
	 * add-to-cart forms (Audit remediation 2026-07-18). Registered in the
	 * CAPTURE phase so it runs before interceptBundleForms' own (bubble-phase)
	 * handler: a second submit while an add is already in flight is blocked
	 * outright rather than firing a duplicate add. The button is disabled and
	 * shows an "Adding to cart…" label the instant the first submit fires, and
	 * is restored when the drawer opens (the end of every success path) or by a
	 * safety timeout if a path ever fails silently.
	 */
	function initBundleFormFeedback() {
		var drawerEl = qs('#bhp-cart-drawer');

		function restore(form) {
			if (!form || !form.__bhpBusy) {
				return;
			}
			form.__bhpBusy = false;
			var btn = form.__bhpBtn;
			if (btn) {
				btn.disabled = false;
				btn.removeAttribute('aria-busy');
				if (typeof btn.__bhpLabel === 'string') {
					btn.innerHTML = btn.__bhpLabel;
				}
			}
			if (form.__bhpTimer) {
				window.clearTimeout(form.__bhpTimer);
				form.__bhpTimer = null;
			}
		}

		function restoreAll() {
			qsa('form.bhp-bundle-form').forEach(restore);
		}

		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!form.matches || !form.matches('form.bhp-bundle-form')) {
				return;
			}
			if (form.__bhpBusy) {
				e.preventDefault();
				e.stopImmediatePropagation();
				return;
			}
			form.__bhpBusy = true;
			var btn = form.querySelector('button[type="submit"]');
			form.__bhpBtn = btn;
			if (btn) {
				btn.__bhpLabel = btn.innerHTML;
				btn.disabled = true;
				btn.setAttribute('aria-busy', 'true');
				btn.innerHTML = 'Adding to cart…';
			}
			form.__bhpTimer = window.setTimeout(function () { restore(form); }, 12000);
		}, true);

		if (drawerEl) {
			var mo = new MutationObserver(function () {
				if (drawerEl.classList.contains('is-open')) {
					restoreAll();
				}
			});
			mo.observe(drawerEl, { attributes: true, attributeFilter: ['class'] });
		}
	}

	/**
	 * P2-5 (2026-08-03) — where a bundle add FINISHES.
	 *
	 * Andrew, verbatim (relayed): "on the collection page- it says get the
	 * collection - it should go straight to check out or cart and auto check out
	 * with one click". The Collection page's three buy CTAs now carry
	 * `bhp_bundle_redirect=checkout`.
	 *
	 * The no-JS path was already handled server-side in bundle-shortcode.php.
	 * THIS is the path a real customer actually takes, because
	 * interceptBundleForms() preventDefault()s the POST and adds over the Store
	 * API — so without this the hidden field would have been silently inert on
	 * every JS-enabled browser, i.e. on essentially every real visit. Shipping
	 * the PHP half alone would have looked correct in code review and done
	 * nothing at all in the browser.
	 *
	 * Every other bundle form on the site omits the field and still opens the
	 * drawer, unchanged.
	 */
	function wantsCheckout(form) {
		var f = form && form.querySelector('input[name="bhp_bundle_redirect"]');
		return !!f && f.value === 'checkout';
	}

	function finishBundleAdd(form) {
		if (!wantsCheckout(form)) {
			openDrawer();
			return;
		}
		var url = (window.bhpDrawerData && window.bhpDrawerData.checkoutUrl) || '/checkout/';
		// The analytics pushes above are synchronous dataLayer writes and have
		// already happened by the time this runs, so navigating here cannot
		// drop an add_to_cart or bundle_add_to_cart event.
		window.location.assign(url);
	}

	function interceptBundleForms() {
		document.addEventListener('submit', function (e) {
			var form = e.target;
			if (!form.matches || !form.matches('form.bhp-bundle-form')) {
				return;
			}

			var actionField = form.querySelector('input[name="bhp_bundle_action"]');
			if (!actionField) {
				return;
			}

			var action = actionField.value;

			/*
			 * ═══════════════════════════════════════════════════════════════
			 * ⭐⭐ 1.8.62 — AN ACTION THIS SCRIPT DOES NOT IMPLEMENT IS LEFT
			 *     TO THE SERVER. ⛔ THE TEST IS DELIBERATELY ABOVE
			 *     `preventDefault()`.
			 * ═══════════════════════════════════════════════════════════════
			 *
			 * ⛔⛔ THE DEFECT THIS CLOSES WAS OBSERVED IN A REAL BROWSER ON
			 *    STAGING, and it could not have been found any other way: the
			 *    PHP was correct, the served HTML was correct, and the button
			 *    still did not work.
			 *
			 * The offer engine's `offer_*` forms carry `bhp-bundle-form`, so
			 * this listener claimed them, called `preventDefault()`, fell
			 * through every branch below to the final `else` — which looks for
			 * `input[name="bhp_titles[]"]:checked` — found none, and fired
			 * `window.alert('Please choose exactly two different titles for
			 * this bundle.')`.
			 *
			 * ⛔ SO THE CUSTOMER GOT A MODAL ASKING THEM TO CHOOSE TWO TITLES,
			 *    ON AN OFFER THAT HAS NO TITLES TO CHOOSE, AND NOTHING WAS
			 *    ADDED TO THE CART. The `catch` fallback below could not save
			 *    it either: that branch `return`s before any promise exists.
			 *
			 * ⭐ THE FIX IS A CAPABILITY TEST, NOT A LIST OF NEW BRANCHES. This
			 *    script implements exactly three action families. Anything
			 *    else is handled by `bhp_bundle_handle_add_to_cart()` on
			 *    `template_redirect` — a real, tested, redirect-after-POST
			 *    path that has shipped since Phase 4. Returning BEFORE
			 *    `preventDefault()` lets the native POST proceed untouched, so
			 *    an unrecognised action degrades to the server rather than to
			 *    a wrong modal.
			 *
			 * ⛔ CONTROL PATH: all three implemented families still match, so
			 *    every collection, single and any-2 form behaves exactly as it
			 *    did in 1.8.61 — same interception, same drawer, same events.
			 */
			if (!/^(complete_|single_|any2_)/.test(action)) {
				return;
			}

			e.preventDefault();
			var catalog = (window.bhpDrawerData && window.bhpDrawerData.catalog) || {};
			var format = action.indexOf('hardcover') !== -1 ? 'hardcover' : 'paperback';
			var titleKeys;

			// "Smart" complete-set actions (premium landing page): only add
			// titles not already in the cart for this format, so a partial
			// set already present never gets a duplicate line or an
			// unintended quantity bump. Resolved async against a fresh cart
			// fetch, then funneled into the same add/refresh/open flow below.
			if (/^complete_(paperback|hardcover)_smart$/.test(action)) {
				var allKeys = Object.keys(catalog[format] || {});
				getCart().then(function (cart) {
					var present = [];
					(cart.items || []).forEach(function (item) {
						var match = identifyCartItem(item);
						if (match && match.format === format && present.indexOf(match.titleKey) === -1) {
							present.push(match.titleKey);
						}
					});
					var missing = allKeys.filter(function (k) { return present.indexOf(k) === -1; });
					if (!missing.length) {
						pushEvent('bundle_add_to_cart', { format: format, bundle_type: action, product_count: 0, already_complete: true });
						return refreshDrawer().then(function () {
							finishBundleAdd(form);
						});
					}
					return addTitles(missing, catalog, format).then(function () {
						return refreshDrawer();
					}).then(function (cart) {
						var addedIds = missing.map(function (k) {
							var info = catalog[format][k];
							return info.variation_id || info.product_id;
						});
						var addedLines = (cart.items || []).filter(function (i) { return addedIds.indexOf(parseInt(i.id, 10)) !== -1; });
						var addedItems = addedLines.map(ga4ItemFromCartLine);
						var addedValue = addedItems.reduce(function (sum, it) { return sum + it.price * it.quantity; }, 0);
						pushEvent('add_to_cart', { source: 'bundle_page', bundle_type: action, currency: cartCurrency(cart), value: addedValue, items: addedItems });
						var totalCartValue = cartItemsValue(cart);
						pushEvent('bundle_add_to_cart', { format: format, bundle_type: action, product_count: missing.length, cart_value: totalCartValue });
						if (present.length + missing.length === allKeys.length) {
							var savings = (window.bhpLandingData && window.bhpLandingData.savings && window.bhpLandingData.savings[format]) || null;
							pushEvent('bundle_savings_applied', { format: format, savings_amount: savings, cart_value: totalCartValue });
						}
						finishBundleAdd(form);
					});
				}).catch(function (err) {
					window.console && console.error('BHP drawer smart bundle add failed, falling back to normal submit', err);
					HTMLFormElement.prototype.submit.call(form);
				});
				return;
			}

			if (action.indexOf('complete_') === 0) {
				titleKeys = Object.keys(catalog[format] || {});
			} else if (action.indexOf('single_') === 0) {
				var singleInput = form.querySelector('input[name="bhp_single_title"]:checked');
				if (!singleInput) {
					window.alert('Please choose one title.');
					return;
				}
				titleKeys = [singleInput.value];
			} else {
				titleKeys = qsa('input[name="bhp_titles[]"]:checked', form).map(function (cb) {
					return cb.value;
				});
				if (titleKeys.length !== 2) {
					window.alert('Please choose exactly two different titles for this bundle.');
					return;
				}
			}

			addTitles(titleKeys, catalog, format)
				.then(function () {
					return refreshDrawer();
				})
				.then(function (cart) {
					var addedIds = titleKeys.map(function (k) {
						var info = catalog[format][k];
						return info.variation_id || info.product_id;
					});
					var addedLines = (cart.items || []).filter(function (i) { return addedIds.indexOf(parseInt(i.id, 10)) !== -1; });
					var addedItems = addedLines.map(ga4ItemFromCartLine);
					var addedValue = addedItems.reduce(function (sum, it) { return sum + it.price * it.quantity; }, 0);
					pushEvent('add_to_cart', { source: 'bundle_page', bundle_type: action, currency: cartCurrency(cart), value: addedValue, items: addedItems });
					finishBundleAdd(form);
				})
				.catch(function (err) {
					window.console && console.error('BHP drawer bundle add failed, falling back to normal submit', err);
					HTMLFormElement.prototype.submit.call(form);
				});
		});
	}

	function initFormatSelectedTracking() {
		if (!window.jQuery) {
			return;
		}
		qsa('form.variations_form').forEach(function (form) {
			window.jQuery(form).on('found_variation', function (evt, variation) {
				pushEvent('format_selected', { variation_id: variation.variation_id });
			});
		});
	}

	/**
	 * ═══════════════════════════════════════════════════════════════════
	 * N1 (2026-08-03) — THE CROSS-SELL MATH, EXPORTED ONCE.
	 * ═══════════════════════════════════════════════════════════════════
	 *
	 * Andrew, production walk, verbatim: "On the checkout page- we should
	 * have the same style 'Add This Adventure- Save $x' as the cart drawer
	 * but on the checkout page - we need to be able to upsell on the
	 * checkout page - and once they get to 3 books they get the full
	 * collection discount." (Relayed through the Chief of Staff; NOT
	 * witnessed first-hand by this agent.)
	 *
	 * ⭐ THE WHOLE POINT OF THIS EXPORT IS THAT THERE IS NO SECOND COPY.
	 *    `assets/checkout-upsell.js` renders the checkout surface, and it
	 *    calls THESE functions — the same `computeDrawerMeta()` and
	 *    `crossSellSavings()` the drawer itself renders from, which read
	 *    `window.bhpDrawerData.bundleRules`, which is `bhp_bundle_rules()`
	 *    verbatim, which is the same table `bhp_bundle_apply_discount_fees()`
	 *    builds the real cart fee from.
	 *
	 *    ⛔ A REIMPLEMENTATION WOULD HAVE BEEN THE DEFECT, NOT THE FEATURE.
	 *       Two independent copies of a savings calculation are two things to
	 *       forget to update, and the failure mode is a button that promises
	 *       a discount the invoice does not give. Changing the rules table
	 *       now moves the drawer, the checkout module and the actual fee
	 *       together, because there is one function.
	 *
	 * ⛔ THIS EXPORT IS READ-ONLY AND SIDE-EFFECT FREE. All three functions
	 *    are pure: they take a Store API cart object and return a
	 *    description of it. None of them writes to the cart, touches the
	 *    DOM, fires an analytics event or mutates module state. Nothing about
	 *    the drawer's own behaviour changes by making them reachable.
	 *
	 * ⭐ SHAPE COMPATIBILITY IS DELIBERATE, AND IT WAS CHECKED LIVE, NOT
	 *    ASSUMED. The checkout module feeds these the object returned by
	 *    `wp.data.select('wc/store/cart').getCartData()` rather than a raw
	 *    Store API response. Blocks camelCases that payload, but the only
	 *    fields these functions read are `cart.items[].id` and
	 *    `cart.items[].quantity`, which are identical in both shapes —
	 *    verified on staging 1.19.157 by reading the store directly.
	 */
	window.bhpBundleCrossSell = {
		compute: computeDrawerMeta,
		savings: crossSellSavings,
		identify: identifyCartItem,
		money: formatMoneyPlain
	};

	/**
	 * ⭐⭐ 1.8.64 — EVERY "OPEN THE CART" CONTROL, NOT JUST THE FLOATING ONE.
	 *
	 * Andrew Signore, carrier item 186 (~05:1x−0600 2026-08-21), read
	 * first-hand at source by the agent that wrote this:
	 *
	 *   "or at least have the cart pop up not the cart page show. … It should
	 *    just go to check out or to the cart side panel then to check out"
	 *
	 * The side panel he is describing ALREADY EXISTS — this is `#bhp-cart-drawer`,
	 * shipped since the Overnight Conversion Sprint, with line items, quantity
	 * steppers, remove, a cross-sell, the order bump, a live Store-API summary
	 * and a Secure Checkout button. What it did NOT have was a way to open it
	 * from the site header; only the floating button could.
	 *
	 * ⭐ SO THIS GENERALISES THE OPENER AND ADDS NO SECOND PANEL. Any element
	 *    carrying `data-bhp-cart-open` now opens the SAME drawer through the
	 *    SAME `openDrawer()` — one panel, one state machine, one analytics
	 *    event. A second drawer implementation is exactly the drift class this
	 *    codebase has paid for before.
	 *
	 * ⛔ THE FLOATING BUTTON IS UNCHANGED. It keeps its id, its handler, its
	 *    `floating_cart` event source and its badge. `floatingBtn` /
	 *    `floatingBadge` still point at it, so `updateFloatingCartButton()` is
	 *    untouched and cannot regress.
	 *
	 * ⭐ EACH EXTRA OPENER REPORTS ITS OWN SOURCE via `data-bhp-cart-source`,
	 *    so `side_cart_opened` can still tell the header from the FAB.
	 */
	function initFloatingCartButton() {
		floatingBtn = qs('#bhp-floating-cart');
		if (floatingBtn) {
			floatingBadge = qs('.bhp-floating-cart__badge', floatingBtn);
			floatingBtn.addEventListener('click', function () {
				openDrawer('floating_cart');
			});
		}

		extraOpeners = [].slice.call(document.querySelectorAll('[data-bhp-cart-open]'));
		extraOpeners.forEach(function (el) {
			el.addEventListener('click', function (e) {
				// A <button> needs no default suppressed, but an <a> fallback
				// would navigate; suppress defensively either way.
				if (e && typeof e.preventDefault === 'function') {
					e.preventDefault();
				}
				openDrawer(el.getAttribute('data-bhp-cart-source') || 'cart_opener');
			});
		});
	}

	/**
	 * Keep every extra opener's count/label/visibility in step with the same
	 * canonical cart the floating button reads. Called from the same place.
	 *
	 * ⛔ AN OPENER WITH NO COUNT NODE IS LEFT ALONE rather than guessed at, and
	 *    an opener that opts out of hiding (`data-bhp-cart-persist`) stays
	 *    visible at zero — the header control uses that, because a control that
	 *    appears and disappears as the cart changes moves the nav around.
	 */
	function updateExtraOpeners(cart) {
		if (!extraOpeners || !extraOpeners.length) {
			return;
		}
		var total = totalUnitsInCart(cart);
		extraOpeners.forEach(function (el) {
			var badge = qs('[data-bhp-cart-count]', el);
			if (badge) {
				badge.textContent = String(total);
			}
			el.setAttribute(
				'aria-label',
				total > 0
					? 'Open cart, ' + total + (1 === total ? ' item' : ' items')
					: 'Open cart'
			);
			el.setAttribute('data-bhp-cart-empty', total > 0 ? 'false' : 'true');
			if (!el.hasAttribute('data-bhp-cart-persist')) {
				el.classList.toggle('is-visible', total > 0);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		drawer = qs('#bhp-cart-drawer');
		if (!drawer) {
			return;
		}

		initDrawerEvents();
		interceptSingleProductForms();
		/*
		 * ⭐ 1.8.65 — carrier item 188. Delegated on `document`, so a CTA whose
		 *    attributes `book-formats.js` rewrites on a format switch is still
		 *    intercepted; there is no per-node listener to go stale.
		 */
		interceptCartAddLinks();
		initBundleFormFeedback();
		interceptBundleForms();
		initFormatSelectedTracking();
		initFloatingCartButton();

		// Prime the Store API nonce and initial content without opening
		// the drawer, so it is instantly ready the first time it's needed.
		// A failure here is not fatal: ensureNonce() re-fetches a nonce
		// before any actual write if this priming call didn't get one, so
		// swallow the error here rather than leave an uncaught rejection.
		refreshDrawer().catch(function (err) {
			window.console && console.warn('BHP drawer priming fetch failed (non-fatal, will retry before first write)', err);
		});
	});

	/**
	 * bfcache restores (browser back/forward) do not re-fire
	 * DOMContentLoaded, so without this the floating button and drawer
	 * could show stale cart contents from before the customer navigated
	 * away and back. Refetches the same canonical Store API cart used
	 * everywhere else -- no new state, no polling (this only runs on the
	 * bfcache restore itself, not on a timer).
	 */
	window.addEventListener('pageshow', function (e) {
		if (e.persisted && drawer) {
			refreshDrawer().catch(function (err) {
				window.console && console.warn('BHP drawer refresh on bfcache restore failed', err);
			});
		}
	});
})();
