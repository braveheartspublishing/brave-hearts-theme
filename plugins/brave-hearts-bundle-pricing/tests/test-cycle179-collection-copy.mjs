/**
 * Brave Hearts Bundle Pricing — THE COLLECTION LABEL vs THE COUNT PRICE.
 * 1.8.90 / `CYCLE179-LD-PLUGIN-1.8.90`. Founder seal 1436, CX-3 option B.
 *
 * ⭐ EXTENDED AT 1.8.91 / `CYCLE179-LD-PLUGIN-1.8.91`, ONE SECTION ONLY.
 *   §H was a PIN on finding F1 — the "- Ships Free" clause promising free
 *   shipping to a cart that already had it. Gandalf directed the prepared
 *   fix under G-40, so §H now asserts the FIXED behaviour and reads the
 *   ACTUAL BUTTON LABEL through the new `ctaLabel` seam rather than a
 *   proxy flag. §§A–G are BYTE-UNTOUCHED and still assert seal 1436.
 *
 * Run from the plugin directory with Node 18+:
 *   node tests/test-cycle179-collection-copy.mjs
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS PROVES, AND WHY IT IS NOT A PHP FILE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Every surface seal 1436 touches is rendered by JavaScript against a Store
 * API cart. PHP cannot execute any of it. `test-crosssell-selection.mjs`
 * already says why a grep suite would be worthless here, and it says it about
 * this exact class of change:
 *
 *   "A source assertion could not have caught the 1.8.24 defect, because the
 *    defective code contained every string that suite greps for."
 *
 * That is doubly true of 1.8.90. NOT ONE STRING IS ADDED OR REMOVED BY THIS
 * RELEASE. `"Included in your complete-set savings"` and
 * `"Complete-set savings"` are still in `bundle-drawer.js`, byte-identical,
 * and a grep for either passes at 1.8.89 and at 1.8.90. The entire content of
 * the ruling is WHICH CART REACHES THEM — so this file loads the real,
 * unmodified `assets/bundle-drawer.js` and RUNS it over the four cart shapes.
 *
 * ⭐ SHAPE A IS PIPPIN'S CART, NOT AN INVENTED ONE. `commerce-cx`
 *    (`CYCLE179-CX-PROD-AUDIT-407`, CX-3) measured Mount Everest x1 +
 *    The Amazon x2 on live production at 390 and read three contradictory
 *    statements off one panel. §A reproduces that cart by id and quantity.
 *
 * ⛔ IT IS STILL NOT A RENDERING PROOF, and the same limit `test-crosssell-
 *    selection.mjs` states applies unchanged: this proves the functions
 *    return the right label for a given cart. Whether the panel PAINTS it is
 *    a browser question and is answered separately, in a browser, at two
 *    asserted viewports.
 *
 * ⛔ IT ALSO PROVES A NEGATIVE ON PURPOSE — §E asserts the PRICE did not move.
 *    Seal 1359's count-keyed tiers are the thing this build was most able to
 *    break by accident, so they are asserted rather than assumed.
 *
 * Exits non-zero on any failure. Reads files; writes nothing.
 */

import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(resolve(here, '../assets/bundle-drawer.js'), 'utf8');

/* Live ids: bhp_bundle_catalog(). Same table test-crosssell-selection.mjs
 * uses, for the same reason — the PHP suite asserts it against the real
 * function, so a catalogue change fails there loudly rather than quietly
 * here. */
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

/* Live figures: bhp_bundle_rules(). */
const RULES = {
	paperback: { 2: { discount: 1.99, shipping: 2.99 }, 3: { discount: 3.98, shipping: 0.00 } },
	hardcover: { 2: { discount: 2.99, shipping: 3.99 }, 3: { discount: 4.98, shipping: 0.00 } }
};

/* The four approved strings, exactly as includes/bundle-drawer.php localizes
 * them. Present so the messaging loop is exercised for real; NOT authored
 * here and not edited by this release. */
const PROGRESS_COPY = {
	paperback: {
		1: 'Add another paperback and save $1.99.',
		2: 'Add the final adventure to complete the collection and save $3.98 total.',
		3: 'Best Value - Complete Paperback Collection'
	},
	hardcover: {
		1: 'Add another hardcover and save $2.99.',
		2: 'Complete the hardcover collection and save $4.98 total.',
		3: 'Best Value - Complete Hardcover Collection'
	}
};
const SAVED_COPY = {
	paperback: 'You saved $1.99 with your 2-book paperback set.',
	hardcover: 'You saved $2.99 with your 2-book hardcover set.'
};

const documentStub = {
	addEventListener() {},
	querySelector() { return null; },
	querySelectorAll() { return []; },
	createElement() { return { setAttribute() {}, appendChild() {}, addEventListener() {}, classList: { add() {} } }; }
};

const sandbox = {
	console,
	setTimeout,
	clearTimeout,
	document: documentStub,
	addEventListener() {},
	fetch: () => Promise.reject(new Error('no network in this harness')),
	bhpDrawerData: {
		catalog: CATALOG,
		bundleRules: RULES,
		currencySymbol: '$',
		progressCopy: PROGRESS_COPY,
		savedCopy: SAVED_COPY,
		freeShipCopy: {
			nudge: 'Add the final adventure and your order ships free.',
			earned: 'Your complete collection ships free.',
			cta_clause: ' - Ships Free'
		},
		/* bhp_bundle_freeship_book_threshold() and bhp_bundle_colouring_policy()
		 * as read FIRST-HAND off staging2 for this build: 3 / any-three. */
		freeShipAtCount: 3,
		anyThreeActive: true,
		colouringIds: [],
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
for (const fn of ['qualifyingNote', 'savingsRowLabel', 'ctaLabel']) {
	if (typeof api[fn] !== 'function') {
		console.error(`FATAL: the plugin did not export bhpBundleCrossSell.${fn}`);
		process.exit(1);
	}
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
/* Quantities matter in every shape below, so this builder takes them. */
const cart = (...triples) => ({
	items: triples.map(([format, title, qty]) => ({ id: id(format, title), quantity: qty || 1 }))
});
const FEE_PB = 'Bundle Savings (Paperback)';
const FEE_HC = 'Bundle Savings (Hardcover)';

/* Every item's note, as the drawer would draw them, in cart order. */
const notes = (c, meta) => c.items.map((item) => api.qualifyingNote(item, meta));

// =====================================================================
// A. SHAPE A — PIPPIN'S CART. 3 PAPERBACKS, 2 DISTINCT TITLES.
//    Mount Everest x1 + The Amazon x2. THE CART SEAL 1436 IS ABOUT.
// =====================================================================

{
	const c = cart(['paperback', 'everest', 1], ['paperback', 'amazon', 2]);
	const meta = api.compute(c);

	// --- the price, which MUST NOT have moved (seal 1359) ---
	assert(meta.tiers.paperback === 3, 'A. PRICE UNCHANGED: 3 paperbacks of 2 titles still read TIER 3 (seal 1359 count-keyed discount stays)');

	// --- the new display facts ---
	assert(meta.format_set.paperback === false, 'A. the cart does NOT hold the paperback set (2 distinct titles)');
	assert(meta.tier_without_set === true, 'A. tier_without_set is TRUE — tier 3 earned by count, set not held');

	// --- the three contradictions Pippin measured, one at a time ---
	assert(
		notes(c, meta).every((n) => n === ''),
		`A. ⭐ CX-3 FIXED: no line item claims "Included in your complete-set savings" (got ${JSON.stringify(notes(c, meta))})`
	);
	assert(
		api.savingsRowLabel('paperback', meta, FEE_PB) === FEE_PB,
		`A. ⭐ CX-3 FIXED: the fee row quotes the invoice's own name, not "Complete-set savings" (got "${api.savingsRowLabel('paperback', meta, FEE_PB)}")`
	);
	assert(
		api.savingsRowLabel('paperback', meta, FEE_PB).indexOf('Complete-set') === -1
		&& api.savingsRowLabel('paperback', meta, FEE_PB).indexOf('2-book') === -1,
		'A. and it does NOT fall back to the tier-2 label, which would be a quieter second false statement'
	);
	assert(
		!meta.messages.some((m) => m.indexOf('Best Value') === 0),
		`A. no "Best Value - Complete Paperback Collection" message (got ${JSON.stringify(meta.messages)})`
	);
	assert(
		!meta.messages.some((m) => m.indexOf('complete collection ships free') !== -1),
		'A. no "Your complete collection ships free." message — that string is a titles claim and stays one'
	);
	assert(
		!!meta.cross_sell && meta.cross_sell.title_key === 'mariana',
		'A. the OFFER survives — the missing adventure is still offered, only the collection claim above it goes'
	);
}

// =====================================================================
// B. SHAPE B — 3 PAPERBACKS, 3 DISTINCT TITLES. UNCHANGED, BY INSTRUCTION.
// =====================================================================

{
	const c = cart(['paperback', 'mariana', 1], ['paperback', 'everest', 1], ['paperback', 'amazon', 1]);
	const meta = api.compute(c);

	assert(meta.tiers.paperback === 3, 'B. tier 3');
	assert(meta.format_set.paperback === true, 'B. the cart HOLDS the paperback set');
	assert(meta.tier_without_set === false, 'B. tier_without_set is FALSE');
	assert(
		notes(c, meta).every((n) => n === 'Included in your complete-set savings'),
		`B. ⭐ UNCHANGED: every line still reads "Included in your complete-set savings" (got ${JSON.stringify(notes(c, meta))})`
	);
	assert(
		api.savingsRowLabel('paperback', meta, FEE_PB) === 'Complete-set savings (Paperback)',
		`B. ⭐ UNCHANGED: the fee row still reads "Complete-set savings (Paperback)" (got "${api.savingsRowLabel('paperback', meta, FEE_PB)}")`
	);
	assert(
		meta.messages.indexOf('Best Value - Complete Paperback Collection') !== -1,
		`B. ⭐ UNCHANGED: the Best Value message still renders (got ${JSON.stringify(meta.messages)})`
	);
	assert(
		meta.messages.indexOf('Your complete collection ships free.') !== -1,
		'B. ⭐ UNCHANGED: and so does the earned free-shipping line'
	);
	assert(meta.cross_sell === null, 'B. a complete paperback collection is offered nothing');
}

// =====================================================================
// C. SHAPE C — 2 PAPERBACKS, 2 DISTINCT TITLES. UNCHANGED, BY INSTRUCTION.
//    The brief is explicit: "Two distinct: unchanged."
// =====================================================================

{
	const c = cart(['paperback', 'mariana', 1], ['paperback', 'everest', 1]);
	const meta = api.compute(c);

	assert(meta.tiers.paperback === 2, 'C. tier 2');
	assert(meta.format_set.paperback === false, 'C. the set is not held (2 of 3 titles)');
	assert(
		meta.tier_without_set === false,
		'C. ⭐ tier_without_set is FALSE — the cart was never TOLD it had a set, so there is nothing to suppress'
	);
	assert(
		notes(c, meta).every((n) => n === 'Included in your 2-book savings'),
		`C. ⭐ UNCHANGED: both lines still read "Included in your 2-book savings" (got ${JSON.stringify(notes(c, meta))})`
	);
	assert(
		api.savingsRowLabel('paperback', meta, FEE_PB) === '2-book savings (Paperback)',
		`C. ⭐ UNCHANGED: the fee row still reads "2-book savings (Paperback)" (got "${api.savingsRowLabel('paperback', meta, FEE_PB)}")`
	);
	assert(
		!!meta.cross_sell && meta.cross_sell.completes_collection === true,
		'C. ⭐ UNCHANGED: the third adventure is still offered as the one that completes the collection'
	);
	assert(
		meta.messages.indexOf('Add the final adventure and your order ships free.') === 0,
		`C. ⭐ UNCHANGED: the free-shipping nudge still leads (got ${JSON.stringify(meta.messages)})`
	);
}

// =====================================================================
// D. SHAPE D — 3 COPIES OF ONE TITLE. THE EXTREME OF THE SAME DEFECT.
//    Not named in the ruling; it is the same cart class as A with one
//    title instead of two, and it was carrying the identical false claim.
// =====================================================================

{
	const c = cart(['paperback', 'mariana', 3]);
	const meta = api.compute(c);

	assert(meta.tiers.paperback === 3, 'D. PRICE UNCHANGED: 3 copies of one title still read TIER 3 (seal 1359)');
	assert(meta.format_set.paperback === false, 'D. one distinct title is not a set');
	assert(meta.tier_without_set === true, 'D. tier_without_set is TRUE');
	assert(
		notes(c, meta).every((n) => n === ''),
		`D. ⭐ FIXED: the line carries no complete-set claim (got ${JSON.stringify(notes(c, meta))})`
	);
	assert(
		api.savingsRowLabel('paperback', meta, FEE_PB) === FEE_PB,
		`D. ⭐ FIXED: the fee row quotes the invoice (got "${api.savingsRowLabel('paperback', meta, FEE_PB)}")`
	);
	assert(
		!meta.messages.some((m) => m.indexOf('Best Value') === 0),
		'D. no Best Value message on three copies of one book'
	);
}

// =====================================================================
// E. THE PRICE DID NOT MOVE. SEAL 1359 IS ASSERTED, NOT ASSUMED.
//    This build could most easily have broken 1.8.87 by accident, so the
//    count-keyed tier is checked at every boundary it has.
// =====================================================================

{
	const rows = [
		[1, 0], [2, 2], [3, 3], [4, 3]
	];
	rows.forEach(([qty, expected]) => {
		const meta = api.compute(cart(['paperback', 'mariana', qty]));
		assert(
			meta.tiers.paperback === expected,
			`E. ${qty}x one paperback title still reads tier ${expected} — the count-keyed ladder is untouched (got ${meta.tiers.paperback})`
		);
	});
}

{
	// The cross-sell savings delta on shape A must still be zero: the cart is
	// already at the top tier, so the third adventure adds no discount. This
	// is 1.8.87 behaviour and 1.8.90 must not have disturbed it.
	const meta = api.compute(cart(['paperback', 'everest', 1], ['paperback', 'amazon', 2]));
	assert(
		meta.cross_sell.savings === 0,
		`E. shape A's offer still carries NO savings clause — already at tier 3, nothing further to earn (got ${meta.cross_sell.savings})`
	);
}

// =====================================================================
// F. HARDCOVER MIRRORS PAPERBACK. Andrew's standing "Same issue for
//    hardcovers" is why every rule in this plugin gets checked twice.
// =====================================================================

{
	const c = cart(['hardcover', 'everest', 1], ['hardcover', 'amazon', 2]);
	const meta = api.compute(c);
	assert(meta.tiers.hardcover === 3, 'F. hardcover: 3 books / 2 titles is tier 3');
	assert(meta.tier_without_set === true, 'F. hardcover: tier_without_set is TRUE');
	assert(notes(c, meta).every((n) => n === ''), 'F. hardcover: no complete-set claim on the lines');
	assert(
		api.savingsRowLabel('hardcover', meta, FEE_HC) === FEE_HC,
		`F. hardcover: the fee row quotes the invoice (got "${api.savingsRowLabel('hardcover', meta, FEE_HC)}")`
	);
}

{
	const c = cart(['hardcover', 'mariana', 1], ['hardcover', 'everest', 1], ['hardcover', 'amazon', 1]);
	const meta = api.compute(c);
	assert(meta.tier_without_set === false, 'F. hardcover: a real hardcover set is NOT suppressed');
	assert(
		api.savingsRowLabel('hardcover', meta, FEE_HC) === 'Complete-set savings (Hardcover)',
		'F. hardcover: and its fee row still reads "Complete-set savings (Hardcover)"'
	);
}

// =====================================================================
// G. MIXED-FORMAT AND FAIL-SAFE EDGES.
// =====================================================================

{
	/* Mariana PB + Everest PB + Mariana HC: three books, TWO adventures, but
	 * NEITHER format reaches tier 3. Nothing was ever claimed, so nothing is
	 * suppressed — the flag must not over-fire on "not a collection". */
	const c = cart(['paperback', 'mariana', 1], ['paperback', 'everest', 1], ['hardcover', 'mariana', 1]);
	const meta = api.compute(c);
	assert(meta.tiers.paperback === 2 && meta.tiers.hardcover === 0, 'G. mixed 2+1: paperback tier 2, hardcover tier 0');
	assert(
		meta.tier_without_set === false,
		'G. ⭐ tier_without_set does NOT fire on a mixed cart that never reached tier 3 — it is not a "is this a collection" flag'
	);
}

{
	/* effectiveTierFor() suppresses a tier-2 format in a mixed cart. A tier-3
	 * format is never suppressed, so the label path must still be reached. */
	const c = cart(['paperback', 'everest', 1], ['paperback', 'amazon', 2], ['hardcover', 'mariana', 1]);
	const meta = api.compute(c);
	assert(meta.is_mixed_format === true, 'G. mixed cart with a tier-3 paperback leg is detected as mixed');
	assert(meta.tier_without_set === true, 'G. and its paperback leg still reads tier_without_set');
	assert(
		api.savingsRowLabel('paperback', meta, FEE_PB) === FEE_PB,
		'G. and the paperback fee row still quotes the invoice rather than claiming a set'
	);
}

{
	/* FAIL-SAFE: a caller with no meta at all, and one with no fee name.
	 * Neither may throw, and neither may render a blank money row. */
	const meta = api.compute(cart(['paperback', 'everest', 1], ['paperback', 'amazon', 2]));
	assert(
		api.savingsRowLabel('paperback', meta, '') === 'Complete-set savings (Paperback)',
		'G. FAIL-SAFE: with no fee name the row keeps the 1.8.89 label rather than rendering nameless'
	);
	assert(
		api.savingsRowLabel('paperback', {}, FEE_PB) === '2-book savings (Paperback)',
		'G. FAIL-SAFE: an empty meta does not throw'
	);
	assert(api.qualifyingNote({ id: 4242, quantity: 1 }, meta) === '', 'G. an unrecognised item still gets no note');
}

// =====================================================================
// H. FIXED IN 1.8.91 — the "- Ships Free" clause never promises free
//    shipping to a cart that ALREADY ships free.
//
//    ⭐ THIS SECTION WAS A PIN AND IS NOW A PROOF. At 1.8.90 it asserted
//    the DEFECT so it could not rot unnoticed, and routed it to Gandalf as
//    finding F1. Gandalf directed the prepared one-line fix under G-40, so
//    the section is rewritten to assert the FIXED behaviour. The pinned
//    text is preserved immediately below, struck, rather than deleted —
//    a reader arriving from the 1.8.90 report needs to see what moved.
//
//    ⛔ SUPERSEDED, 2026-09-08, `CYCLE179-LD-PLUGIN-1.8.91`:
//    ~~"H. PINNED, NOT FIXED — the '- Ships Free' clause on shape A.
//       On shape A the cart ALREADY ships free (3 physical books,
//       threshold 3), and `completes_collection` is still TRUE, so the
//       cross-sell button still appends ' - Ships Free'. That is the
//       1.8.88 defect class one surface over: a promise of something
//       already earned. IT IS OUT OF SEAL 1436's SCOPE AND OUT OF THIS
//       BRIEF'S ... Routed to Gandalf as finding F1."~~
//
//    ⛔ WHAT IS ASSERTED, AND WHY IT IS THE LABEL AND NOT A FLAG.
//    `completes_collection` is STILL TRUE on shape A and is asserted to be
//    — the flag was never wrong. Adding Mariana genuinely does take two
//    adventures to three. The defect was the SUFFIX, not the flag, so the
//    test reads the actual assembled BUTTON LABEL through the 1.8.91
//    `ctaLabel` seam. A flag assertion would have passed at 1.8.90 too.
// =====================================================================

const CLAUSE = sandbox.bhpDrawerData.freeShipCopy.cta_clause;
const BASE_CTA = 'Add This Adventure';
const label = (c) => api.ctaLabel(api.compute(c).cross_sell, CLAUSE);

{
	/* Shape A — Pippin's cart. THE ONE F1 WAS RAISED ON. */
	const c = cart(['paperback', 'everest', 1], ['paperback', 'amazon', 2]);
	const meta = api.compute(c);

	assert(
		meta.cross_sell.completes_collection === true,
		'H. shape A still flags completes_collection — TRUE and untouched; adding Mariana really does take 2 adventures to 3'
	);
	assert(
		meta.cross_sell.already_ships_free === true,
		'H. and 1.8.91 also knows the cart ALREADY ships free (3 physical books against the localized threshold of 3)'
	);
	assert(
		label(c) === BASE_CTA,
		`H. ⭐ F1 FIXED: shape A's button reads the BASE label and promises nothing it has already given (got "${label(c)}")`
	);
	assert(
		label(c).indexOf(CLAUSE) === -1,
		'H. and the " - Ships Free" clause is specifically absent, which is the false claim F1 named'
	);
	assert(
		label(c).indexOf('Save') === -1,
		'H. and it did NOT fall through to a savings claim — the base label was the direction, not a substituted claim'
	);
}

{
	/* ⭐⭐ THE CART THAT PROVES THE BRANCH HAD TO BE KEPT RATHER THAN LET
	 *     FALL THROUGH, and it is not hypothetical.
	 *
	 *     2 paperbacks of 2 titles + 1 hardcover: THREE physical books, so
	 *     the order already ships free, while the paperback leg is still at
	 *     tier 2 — so the Mariana paperback offer carries a REAL non-zero
	 *     saving. Had 1.8.91 simply dropped this cart out of the
	 *     completes_collection arm, the button would have swapped one
	 *     customer-facing claim ("- Ships Free") for a DIFFERENT one
	 *     ("- Save $X") on this desk's own judgement. It does not. */
	const c = cart(['paperback', 'everest', 1], ['paperback', 'amazon', 1], ['hardcover', 'everest', 1]);
	const meta = api.compute(c);

	assert(
		meta.cross_sell.savings > 0,
		`H. the mixed 3-book cart's offer carries a REAL saving (got ${meta.cross_sell.savings}), so the fall-through arm was reachable`
	);
	assert(
		meta.cross_sell.already_ships_free === true,
		'H. and that cart already ships free on PHYSICAL count, across formats — the count is books, not titles'
	);
	assert(
		label(c) === BASE_CTA,
		`H. ⭐ and the button still reads the BASE label — no shipping promise AND no substituted savings claim (got "${label(c)}")`
	);
}

{
	/* Shape C — the cart that MUST still get the clause. Two books, the
	 * threshold not reached: the promise is TRUE there and 1.8.91 leaves
	 * 1.8.24's founder-approved behaviour exactly as it was. This is the
	 * assertion that stops the fix being over-applied. */
	const c = cart(['paperback', 'everest', 1], ['paperback', 'amazon', 1]);
	const meta = api.compute(c);

	assert(
		meta.cross_sell.already_ships_free === false,
		'H. shape C does NOT already ship free (2 physical books, threshold 3)'
	);
	assert(
		label(c) === BASE_CTA + CLAUSE,
		`H. ⭐ 1.8.24 UNCHANGED: shape C's button still reads "${BASE_CTA}${CLAUSE}" — the promise is true there (got "${label(c)}")`
	);
}

{
	/* Shape D — three copies of one title. `completes_collection` is FALSE
	 * (two adventures still missing), so this cart never reached the clause
	 * at 1.8.90 either. Asserted so the 1.8.91 gate is proved not to have
	 * changed a cart it was never about. */
	const c = cart(['paperback', 'mariana', 3]);
	const meta = api.compute(c);

	assert(
		meta.cross_sell.completes_collection === false && meta.cross_sell.already_ships_free === true,
		'H. shape D already ships free but does NOT complete the collection — one adventure is not two'
	);
	assert(
		label(c) === BASE_CTA,
		`H. shape D's button is unchanged by 1.8.91: the base label, as at 1.8.90 (got "${label(c)}")`
	);
}

{
	/* Hardcover mirrors paperback. Andrew's standing "Same issue for
	 * hardcovers" (1.8.24, CYCLE144-LD-14) applies to the fix as well as to
	 * the feature: 3 hardcovers of 2 titles is shape A one format over. */
	const c = cart(['hardcover', 'everest', 1], ['hardcover', 'amazon', 2]);
	const meta = api.compute(c);

	assert(
		meta.cross_sell.format === 'hardcover' && meta.cross_sell.already_ships_free === true,
		'H. the hardcover mirror of shape A also reads already_ships_free'
	);
	assert(
		label(c) === BASE_CTA,
		`H. ⭐ F1 FIXED FOR HARDCOVER TOO — "Same issue for hardcovers" honoured (got "${label(c)}")`
	);
}

{
	/* FAIL-SAFE. An older drawer payload sends no flag, and a caller may
	 * pass no offer at all. Neither may throw, and neither may lose the
	 * 1.8.24 behaviour: an absent flag can only ever restore what 1.8.90
	 * did, never invent a claim. */
	assert(api.ctaLabel(null, CLAUSE) === BASE_CTA, 'H. FAIL-SAFE: a null offer returns the base label and does not throw');
	assert(
		api.ctaLabel({ completes_collection: true, savings: 0 }, CLAUSE) === BASE_CTA + CLAUSE,
		'H. FAIL-SAFE: an offer with NO already_ships_free flag renders exactly what 1.8.90 rendered'
	);
	assert(
		api.ctaLabel({ completes_collection: true, already_ships_free: true, savings: 0 }, '') === BASE_CTA,
		'H. FAIL-SAFE: a missing cta_clause cannot produce a bare or broken label'
	);
	assert(
		api.ctaLabel({ cta: 'Add The Coloring Book', earns_freeship: true, savings: 0 }, CLAUSE) === 'Add The Coloring Book' + CLAUSE,
		'H. 1.8.85 UNCHANGED: the colouring arm still appends the clause and still brings its own button word'
	);
}

// ---------------------------------------------------------------------
console.log('');
if (failures.length === 0) {
	console.log('ALL COLLECTION-COPY TESTS PASSED');
	process.exit(0);
}
console.log(`${failures.length} TEST(S) FAILED:`);
failures.forEach((label) => console.log(` - ${label}`));
process.exit(1);
