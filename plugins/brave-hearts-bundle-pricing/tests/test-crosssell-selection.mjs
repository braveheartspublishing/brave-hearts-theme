/**
 * Brave Hearts Bundle Pricing — CROSS-SELL SELECTION (1.8.25). CYCLE144-LD-41.
 *
 * Run from the plugin directory with Node 18+:
 *   node tests/test-crosssell-selection.mjs
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS FILE EXISTS AND WHY IT IS NOT PHP
 * ═══════════════════════════════════════════════════════════════════════
 *
 * tests/test-freeship-leads.php says plainly what it cannot do:
 *
 *   "The cross-sell BUTTON is rendered by JavaScript ... PHP cannot execute
 *    it. §4 therefore asserts the JS SOURCE contains the exact branch ...
 *    which is a source assertion and NOT a rendering proof."
 *
 * A source assertion could not have caught the 1.8.24 defect, because the
 * defective code contained every string that suite greps for. The defect
 * was in WHICH TITLE the selection returned, and the only way to test that
 * is to RUN it. This file loads the real, unmodified `assets/bundle-drawer.js`
 * into a stub window and calls the real `window.bhpBundleCrossSell.compute()`
 * over fixture carts.
 *
 * ⛔ IT IS STILL NOT A RENDERING PROOF. It proves the selection function
 *    returns the right offer for a given cart. Whether the button paints
 *    that offer on a real drawer and a real checkout must be observed in a
 *    browser, and this file does not claim to have done that.
 *
 * ⛔ NOTHING IS STUBBED THAT MATTERS. The catalog and rules tables below are
 *    the shapes `bundle-drawer.php` localizes, with the LIVE product ids from
 *    bhp_bundle_catalog() and the LIVE figures from bhp_bundle_rules(). The
 *    PHP suite asserts those same figures against the real functions, so a
 *    table change fails there loudly rather than passing quietly here.
 *
 * Exits non-zero on any failure. Reads files; writes nothing.
 */

import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(resolve(here, '../assets/bundle-drawer.js'), 'utf8');

/* Live ids: bhp_bundle_catalog(). Mariana paperback is a variable product and
 * its VARIATION id (334) is what reaches the cart — the same rule
 * identifyCartItem() applies. */
const CATALOG = {
	paperback: {
		mariana: { label: 'The Mariana Trench (paperback)', product_id: 333, variation_id: 334 },
		everest: { label: 'Mount Everest (paperback)',      product_id: 15, variation_id: 0 },
		amazon:  { label: 'The Amazon (paperback)',         product_id: 18, variation_id: 0 }
	},
	hardcover: {
		mariana: { label: 'The Mariana Trench (hardcover)', product_id: 14, variation_id: 0 },
		everest: { label: 'Mount Everest (hardcover)',      product_id: 17, variation_id: 0 },
		amazon:  { label: 'The Amazon (hardcover)',         product_id: 20, variation_id: 0 }
	}
};

/* Live figures: bhp_bundle_rules(). Asserted against the real PHP function in
 * tests/test-freeship-leads.php §0 and §5. */
const RULES = {
	paperback: { 2: { discount: 1.99, shipping: 2.99 }, 3: { discount: 3.98, shipping: 0.00 } },
	hardcover: { 2: { discount: 2.99, shipping: 3.99 }, 3: { discount: 4.98, shipping: 0.00 } }
};

const listeners = {};
const documentStub = {
	addEventListener(type, fn) { listeners[type] = fn; },
	querySelector() { return null; },
	querySelectorAll() { return []; },
	createElement() { return { setAttribute() {}, appendChild() {}, addEventListener() {}, classList: { add() {} } }; }
};

const sandbox = {
	console,
	setTimeout,
	clearTimeout,
	document: documentStub,
	addEventListener(type, fn) { listeners['window:' + type] = fn; },
	fetch: () => Promise.reject(new Error('no network in this harness')),
	bhpDrawerData: {
		catalog: CATALOG,
		bundleRules: RULES,
		currencySymbol: '$',
		progressCopy: {},
		savedCopy: {},
		freeShipCopy: {
			nudge: 'Add the final adventure and your order ships free.',
			earned: 'Your complete collection ships free.',
			cta_clause: ' - Ships Free'
		},
		addonProductIds: [999]
	}
};
sandbox.window = sandbox;
sandbox.self = sandbox;
vm.createContext(sandbox);
vm.runInContext(source, sandbox, { filename: 'bundle-drawer.js' });

const api = sandbox.window.bhpBundleCrossSell;
if (!api || typeof api.compute !== 'function') {
	console.error('FATAL: bundle-drawer.js did not export bhpBundleCrossSell.compute');
	process.exit(1);
}

const failures = [];
function assert(condition, label) {
	if (condition) {
		console.log(`PASS: ${label}`);
	} else {
		console.log(`FAIL: ${label}`);
		failures.push(label);
	}
}

const id = (format, title) => {
	const info = CATALOG[format][title];
	return info.variation_id ? info.variation_id : info.product_id;
};
const cart = (...pairs) => ({
	items: pairs.map(([format, title]) => ({ id: id(format, title), quantity: 1 }))
});

/*
 * ⭐ THE SERVER'S OWN RULE, MIRRORED HERE AS THE ORACLE.
 *
 * bhp_bundle_shipping_amount() returns 0.00 when `is_complete_collection`,
 * which bhp_bundle_evaluate_cart() sets from the count of DISTINCT ADVENTURES
 * across both formats with nothing unrelated in the cart. Every "ships free"
 * assertion below is checked against THAT rule applied to the cart AFTER the
 * offered item is added — never against the button's own flag, which is the
 * thing under test.
 */
function shipsFreeAfterAdding(items, offer) {
	const after = items.concat([{ id: id(offer.format, offer.title_key), quantity: 1 }]);
	const known = after.map((item) => api.identify(item));
	if (known.some((m) => m === null)) {
		return false; // has_unrelated — the server leaves those carts alone.
	}
	const adventures = new Set(known.map((m) => m.titleKey));
	return adventures.size >= 3;
}

// =====================================================================
// 1. THE DEFECT ANDREW'S FLAG NAMED: A MIXED 2-ADVENTURE CART
// =====================================================================

{
	const c = cart(['paperback', 'mariana'], ['hardcover', 'everest']);
	const cs = api.compute(c).cross_sell;

	assert(!!cs, '1. a 1-1 mixed cart still gets an offer');
	assert(
		cs.title_key === 'amazon',
		`1. THE FIX: the offer is the MISSING adventure (got "${cs && cs.title_key}") — 1.8.24 offered "everest", a format twin of a title already in the cart`
	);
	assert(
		cs.format === 'paperback',
		`1. a 1-1 mixed cart ties on format and defaults to PAPERBACK, the lower-commitment ask (got "${cs && cs.format}")`
	);
	assert(
		cs.completes_collection === true,
		'1. the offer is flagged as completing the collection'
	);
	assert(
		shipsFreeAfterAdding(c.items, cs) === true,
		'1. ⭐ THE CLAIM IS TRUE: mixed cart + the offered item = 3 distinct adventures, which is exactly the condition bhp_bundle_shipping_amount() returns $0.00 for'
	);
}

// =====================================================================
// 2. THE SAME CART UNDER 1.8.24'S OFFER — THE CLAIM WOULD HAVE BEEN FALSE
// =====================================================================

{
	const c = cart(['paperback', 'mariana'], ['hardcover', 'everest']);
	const old = { format: 'paperback', title_key: 'everest' };
	assert(
		shipsFreeAfterAdding(c.items, old) === false,
		'2. REPRODUCED: 1.8.24\'s offer (Everest paperback) takes the cart to 3 BOOKS but only 2 adventures — it does NOT ship free'
	);
}

// =====================================================================
// 3. MAJORITY FORMAT
// =====================================================================

{
	// 2 paperbacks + 1 hardcover of a title already held: majority paperback.
	const c = cart(['paperback', 'mariana'], ['paperback', 'everest'], ['hardcover', 'mariana']);
	const cs = api.compute(c).cross_sell;
	assert(cs.title_key === 'amazon', '3. 3 books / 2 adventures: the missing adventure is offered, not a fourth format twin');
	assert(cs.format === 'paperback', '3. majority format paperback (2 pb titles vs 1 hc) is matched');
	assert(shipsFreeAfterAdding(c.items, cs) === true, '3. and it ships free');
}

{
	// Majority hardcover.
	const c = cart(['hardcover', 'mariana'], ['hardcover', 'everest'], ['paperback', 'mariana']);
	const cs = api.compute(c).cross_sell;
	assert(cs.title_key === 'amazon', '3. majority-hardcover cart: the missing adventure is offered');
	assert(cs.format === 'hardcover', '3. majority format hardcover (2 hc titles vs 1 pb) is matched');
	assert(shipsFreeAfterAdding(c.items, cs) === true, '3. and it ships free');
}

// =====================================================================
// 4. REGRESSION — SINGLE-FORMAT CARTS BEHAVE EXACTLY AS THEY DID
// =====================================================================

{
	const c = cart(['paperback', 'mariana'], ['paperback', 'everest']);
	const cs = api.compute(c).cross_sell;
	assert(cs.title_key === 'amazon' && cs.format === 'paperback', '4. REGRESSION: 2 paperbacks are offered the third paperback');
	assert(cs.completes_collection === true, '4. REGRESSION: and it completes the collection');
	assert(Math.abs(cs.savings - 1.99) < 0.001, `4. REGRESSION: the B4 delta is still $1.99 (3.98 - 1.99), got ${cs.savings}`);
}

{
	const c = cart(['hardcover', 'mariana'], ['hardcover', 'everest']);
	const cs = api.compute(c).cross_sell;
	assert(cs.title_key === 'amazon' && cs.format === 'hardcover', '4. REGRESSION: 2 hardcovers are offered the third hardcover');
	assert(Math.abs(cs.savings - 1.99) < 0.001, `4. REGRESSION: the hardcover delta is still $1.99 (4.98 - 2.99), got ${cs.savings}`);
}

{
	const c = cart(['paperback', 'mariana']);
	const cs = api.compute(c).cross_sell;
	assert(cs.format === 'paperback' && cs.title_key !== 'mariana', '4. REGRESSION: a 1-book cart is offered a different title in the same format');
	assert(cs.completes_collection === false, '4. REGRESSION: a 1-book cart\'s offer does NOT claim free shipping (it would still be 2 adventures)');
}

// =====================================================================
// 5. NO OFFER WHERE THERE IS NOTHING HONEST TO OFFER
// =====================================================================

{
	assert(api.compute({ items: [] }).cross_sell === null, '5. an EMPTY cart gets no offer (the guard the old in-loop code got for free)');
	assert(api.compute({ items: [{ id: 999, quantity: 1 }] }).cross_sell === null, '5. an add-on-only cart holds no adventure and gets no offer');
}

{
	const c = cart(['paperback', 'mariana'], ['paperback', 'everest'], ['paperback', 'amazon']);
	assert(api.compute(c).cross_sell === null, '5. a complete paperback collection gets no offer at all');
}

// =====================================================================
// 6. PASS 2 — EVERY ADVENTURE OWNED, SO COMPLETE A FORMAT SET INSTEAD
// =====================================================================

{
	const c = cart(['paperback', 'mariana'], ['paperback', 'everest'], ['hardcover', 'amazon']);
	const cs = api.compute(c).cross_sell;
	assert(!!cs && cs.title_key === 'amazon' && cs.format === 'paperback', '6. all three adventures owned: the offer completes the PAPERBACK set');
	assert(
		cs.completes_collection === false,
		'6. and it does NOT claim to earn free shipping — this cart already ships free, so the claim would be meaningless rather than false'
	);
	assert(cs.savings > 0, `6. it still carries a real discount delta (got ${cs.savings})`);
}

// =====================================================================
// 7. AN UNRELATED PRODUCT SUPPRESSES THE FREE-SHIPPING CLAIM
// =====================================================================

{
	const c = cart(['paperback', 'mariana'], ['hardcover', 'everest']);
	c.items.push({ id: 4242, quantity: 1 }); // not in the catalog, not an add-on
	const cs = api.compute(c).cross_sell;
	assert(cs.title_key === 'amazon', '7. the missing adventure is still the right offer with an unrelated item present');
	assert(
		cs.completes_collection === false,
		'7. REGRESSION: has_unrelated still suppresses the free-shipping claim — the server leaves those carts alone, so the claim would be false'
	);
}

// ---------------------------------------------------------------------
console.log('');
if (failures.length === 0) {
	console.log('ALL CROSS-SELL SELECTION TESTS PASSED');
	process.exit(0);
}
console.log(`${failures.length} TEST(S) FAILED:`);
failures.forEach((label) => console.log(` - ${label}`));
process.exit(1);
