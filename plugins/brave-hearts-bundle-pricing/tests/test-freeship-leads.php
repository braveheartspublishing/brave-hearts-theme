<?php
/**
 * Brave Hearts Bundle Pricing — FREE SHIPPING LEADS (1.8.24). CYCLE144-LD-14.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-freeship-leads.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (relayed through the Chief of Staff;
 * ⚠ RELAYED, not witnessed by the agent that wrote this file):
 *
 *   "On the checkout when you have two books - It still says 'Add this
 *    adventure save $1.99' supposed to say the Free Shipping info- Same
 *    issue for hardcovers."
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ §0 REPRODUCES THE DEFECT FROM THE LIVE TABLE BEFORE ANYTHING IS FIXED
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The "$1.99" in Andrew's message is not a typo and it is not a hardcoded
 * string anywhere: it is `bhp_bundle_rules()`'s tier-3 discount minus its
 * tier-2 discount, and it comes out at $1.99 for BOTH formats, which is why
 * he saw the same wrong number twice. §0 asserts that arithmetic against the
 * LIVE rules table, so if the table ever moves this suite explains itself
 * instead of failing mysteriously.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED PLAINLY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The cross-sell BUTTON is rendered by JavaScript (bundle-drawer.js and
 * checkout-upsell.js) against a Store API cart. PHP cannot execute it. §4
 * therefore asserts the JS SOURCE contains the exact branch and the exact
 * ordering change, which is a source assertion and NOT a rendering proof.
 * The rendered button and the rendered message order must be checked in a
 * real browser on a real 2-book cart; this file does not claim to have done
 * that, and a passing run here is not that evidence.
 *
 * Exits non-zero on any failure. Reads pure functions and source files only:
 * no cart, no session, no order, no product, no option is written.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_fsl_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_fsl_read( $rel ) {
	$path = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/' . $rel;
	return is_readable( $path ) ? (string) file_get_contents( $path ) : null;
}

// =====================================================================
// 0. THE DEFECT, REPRODUCED FROM THE LIVE RULES TABLE
// =====================================================================

bhp_fsl_assert(
	function_exists( 'bhp_bundle_rules' ) && function_exists( 'bhp_bundle_freeship_copy' ),
	'0. bhp_bundle_rules() and bhp_bundle_freeship_copy() are both loaded',
	$failures
);

$fsl_deltas = array();
foreach ( array( 'paperback', 'hardcover' ) as $fsl_format ) {
	$fsl_rules              = bhp_bundle_rules( $fsl_format );
	$fsl_deltas[ $fsl_format ] = round(
		( (float) $fsl_rules[3]['discount'] ) - ( (float) $fsl_rules[2]['discount'] ),
		2
	);
}
bhp_fsl_assert(
	1.99 === $fsl_deltas['paperback'] && 1.99 === $fsl_deltas['hardcover'],
	sprintf(
		'0. REPRODUCED: the tier-3 minus tier-2 delta is $%.2f paperback and $%.2f hardcover — the same $1.99 Andrew saw on BOTH formats',
		$fsl_deltas['paperback'],
		$fsl_deltas['hardcover']
	),
	$failures
);

/*
 * And the reason leading with shipping is not merely a preference: at that
 * exact moment the customer also stops paying the tier-2 shipping figure,
 * which is LARGER than the $1.99 the button was advertising.
 */
$fsl_ship2 = array(
	'paperback' => (float) bhp_bundle_rules( 'paperback' )[2]['shipping'],
	'hardcover' => (float) bhp_bundle_rules( 'hardcover' )[2]['shipping'],
);
$fsl_ship3 = array(
	'paperback' => (float) bhp_bundle_rules( 'paperback' )[3]['shipping'],
	'hardcover' => (float) bhp_bundle_rules( 'hardcover' )[3]['shipping'],
);
bhp_fsl_assert(
	0.0 === $fsl_ship3['paperback'] && 0.0 === $fsl_ship3['hardcover'],
	'0. the 3-title tier still ships free in both formats (1.8.23 ruling intact)',
	$failures
);
bhp_fsl_assert(
	$fsl_ship2['paperback'] > $fsl_deltas['paperback'] && $fsl_ship2['hardcover'] > $fsl_deltas['hardcover'],
	sprintf(
		'0. the shipping the third book removes ($%.2f pb / $%.2f hc) is LARGER than the extra discount ($1.99) the button was advertising',
		$fsl_ship2['paperback'],
		$fsl_ship2['hardcover']
	),
	$failures
);

// =====================================================================
// 1. THE NEW COPY KEY
// =====================================================================

$fsl_copy = bhp_bundle_freeship_copy();

bhp_fsl_assert(
	isset( $fsl_copy['cta_clause'] ) && '' !== trim( (string) $fsl_copy['cta_clause'] ),
	'1. bhp_bundle_freeship_copy() exposes a non-empty cta_clause',
	$failures
);
bhp_fsl_assert(
	isset( $fsl_copy['nudge'], $fsl_copy['earned'] )
	&& 'Add the final adventure and your order ships free.' === $fsl_copy['nudge']
	&& 'Your complete collection ships free.' === $fsl_copy['earned'],
	'1. REGRESSION: the two 1.8.23 strings are byte-unchanged — this release ADDED a key, it rewrote nothing',
	$failures
);
bhp_fsl_assert(
	0 === preg_match( '/\d/', (string) $fsl_copy['cta_clause'] ),
	'1. the clause quotes NO figure (shipping saved differs by format, so any single number would be wrong somewhere)',
	$failures
);
foreach ( array( '—', '–' ) as $fsl_dash ) {
	bhp_fsl_assert(
		false === strpos( (string) $fsl_copy['cta_clause'], $fsl_dash ),
		'1. the clause uses a hyphen, never an em/en dash (B4 + the sitewide purge)',
		$failures
	);
}
$fsl_has_urgency = false;
foreach ( array( 'hurry', 'today only', 'last chance', 'expires', 'ends soon', 'act now', 'limited time' ) as $fsl_word ) {
	if ( false !== stripos( (string) $fsl_copy['cta_clause'], $fsl_word ) ) {
		$fsl_has_urgency = true;
	}
}
bhp_fsl_assert(
	false === $fsl_has_urgency,
	'1. the clause uses no false-urgency, scarcity or countdown language',
	$failures
);
add_filter(
	'bhp_bundle_freeship_copy',
	function ( $c ) {
		$c['cta_clause'] = ' - REVIEWED CLAUSE';
		return $c;
	}
);
bhp_fsl_assert(
	' - REVIEWED CLAUSE' === bhp_bundle_freeship_copy()['cta_clause'],
	'1. the clause is swappable through the existing bhp_bundle_freeship_copy filter (one filter, all three surfaces)',
	$failures
);

// =====================================================================
// 2. THE PHP CART/CHECKOUT SURFACE: SHIPPING LEADS
// =====================================================================

$fsl_cart_src = bhp_fsl_read( 'includes/bundle-cart.php' );
bhp_fsl_assert( null !== $fsl_cart_src, '2. includes/bundle-cart.php is readable', $failures );

$fsl_fn_start = strpos( (string) $fsl_cart_src, 'function bhp_bundle_print_progress_messages()' );
$fsl_fn_end   = strpos( (string) $fsl_cart_src, 'Admin UI for the audience-coupon flag', (int) $fsl_fn_start );
$fsl_fn       = substr( (string) $fsl_cart_src, (int) $fsl_fn_start, (int) $fsl_fn_end - (int) $fsl_fn_start );

$fsl_pos_freeship = strpos( $fsl_fn, "esc_html( \$freeship['nudge'] )" );
$fsl_pos_loop     = strpos( $fsl_fn, "foreach ( array( 'paperback', 'hardcover' ) as \$format )" );
bhp_fsl_assert(
	false !== $fsl_pos_freeship && false !== $fsl_pos_loop && $fsl_pos_freeship < $fsl_pos_loop,
	'2. the free-shipping nudge is printed BEFORE the per-format discount loop (Andrew: the shipping info leads)',
	$failures
);
bhp_fsl_assert(
	1 === substr_count( $fsl_fn, "\$freeship['nudge']" )
	&& 1 === substr_count( $fsl_fn, "\$freeship['earned']" ),
	'2. each shipping string is printed exactly once — the block MOVED, it was not duplicated',
	$failures
);
bhp_fsl_assert(
	false !== strpos( $fsl_fn, "empty( \$eval['has_unrelated'] )" ),
	'2. REGRESSION: the load-bearing has_unrelated suppression survived the move',
	$failures
);
bhp_fsl_assert(
	false !== strpos( $fsl_fn, 'You saved $1.99 with your 2-book paperback set.' )
	&& false !== strpos( $fsl_fn, 'You saved $2.99 with your 2-book hardcover set.' ),
	'2. REGRESSION: the approved per-format discount copy is still present and still printed (nothing was deleted to make room)',
	$failures
);

// =====================================================================
// 3. THE DRAWER: SHIPPING LEADS, AND THE DUPLICATE ASK IS SUPPRESSED
// =====================================================================

$fsl_js = bhp_fsl_read( 'assets/bundle-drawer.js' );
bhp_fsl_assert( null !== $fsl_js, '3. assets/bundle-drawer.js is readable', $failures );

bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, 'messages.unshift(freeShipCopy.nudge)' )
	&& false !== strpos( (string) $fsl_js, 'messages.unshift(freeShipCopy.earned)' ),
	'3. the drawer UNSHIFTS both shipping lines to the front (1.8.23 pushed them to the back)',
	$failures
);
bhp_fsl_assert(
	false === strpos( (string) $fsl_js, 'messages.push(freeShipCopy.' ),
	'3. no push() of a shipping line survives anywhere in the drawer',
	$failures
);
bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, 'if (2 === count && freeShipLeads)' ),
	'3. the count===2 progress line is suppressed while the shipping nudge leads (they make the identical ask)',
	$failures
);
bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, "messages.push(savedCopy[format])" ),
	'3. REGRESSION: the "You saved $X with your 2-book set" report SURVIVES — only the duplicate ask is suppressed',
	$failures
);
/*
 * `freeShipLeads` must be computed before the loop that reads it. A `var`
 * hoists but its VALUE does not, so a declaration after the loop would make
 * the suppression silently never fire.
 */
$fsl_pos_leads_decl = strpos( (string) $fsl_js, 'var freeShipLeads =' );
$fsl_pos_leads_use  = strpos( (string) $fsl_js, 'if (2 === count && freeShipLeads)' );
bhp_fsl_assert(
	false !== $fsl_pos_leads_decl && false !== $fsl_pos_leads_use && $fsl_pos_leads_decl < $fsl_pos_leads_use,
	'3. freeShipLeads is ASSIGNED before the loop that reads it (a var hoists; its value does not)',
	$failures
);
bhp_fsl_assert(
	1 === substr_count( (string) $fsl_js, 'var adventures = []' )
	&& 1 === substr_count( (string) $fsl_js, 'var hasUnrelated =' ),
	'3. adventures/hasUnrelated exist exactly ONCE — the move up left no duplicate declaration behind',
	$failures
);

// =====================================================================
// 4. THE CROSS-SELL BUTTON, BOTH SURFACES
// =====================================================================

$fsl_upsell = bhp_fsl_read( 'assets/checkout-upsell.js' );
bhp_fsl_assert( null !== $fsl_upsell, '4. assets/checkout-upsell.js is readable', $failures );

bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, 'completes_collection: !hasUnrelated' ),
	'4. the cross-sell offer carries a completes_collection flag',
	$failures
);
/*
 * ⭐ AMENDED 1.8.25: the needle was "adventures.indexOf(titleKeys[i]) === -1",
 *    naming the loop variable of the in-loop selection this release removed.
 *    The TEST is unchanged in substance -- membership of the cross-format
 *    `adventures` list -- and it now reads the identifier the extracted
 *    chooseCrossSell() uses. The assertion was tightened rather than
 *    loosened: it must appear inside completes_collection's own expression,
 *    so a stray mention elsewhere in the file cannot satisfy it.
 */
bhp_fsl_assert(
	1 === preg_match( '/completes_collection:.{0,160}adventures\.indexOf\(\s*titleKey\s*\) === -1/s', (string) $fsl_js ),
	'4. completes_collection is a distinct-TITLE test, not a book-count test (2 copies of one title never qualify)',
	$failures
);
foreach ( array(
	'bundle-drawer.js'    => $fsl_js,
	'checkout-upsell.js'  => $fsl_upsell,
) as $fsl_rel => $fsl_src ) {
	bhp_fsl_assert(
		false !== strpos( (string) $fsl_src, 'cs.completes_collection && freeShipClause' ),
		"4. {$fsl_rel} prefers the free-shipping clause over the savings clause on the completing title",
		$failures
	);
	bhp_fsl_assert(
		false !== strpos( (string) $fsl_src, 'freeShipCopy.cta_clause' ),
		"4. {$fsl_rel} reads the clause from the SERVER's bhp_bundle_freeship_copy(), never a JS literal",
		$failures
	);
	bhp_fsl_assert(
		false === strpos( (string) $fsl_src, "'Ships Free'" ) && false === strpos( (string) $fsl_src, '"Ships Free"' ),
		"4. {$fsl_rel} hardcodes no copy of the clause",
		$failures
	);
	bhp_fsl_assert(
		false !== strpos( (string) $fsl_src, 'cs.savings > 0' ),
		"4. REGRESSION: {$fsl_rel} still shows the B4 savings clause in every state that is NOT the completing title",
		$failures
	);
}
/*
 * The checkout panel is only redrawn when its `data-offer` key changes. If
 * the key did not include the flag, a cart that reaches the completing state
 * without changing title/format/savings would keep the stale "$1.99" label —
 * which is the exact defect being fixed.
 */
bhp_fsl_assert(
	false !== strpos( (string) $fsl_upsell, "(cs.completes_collection ? '1' : '0')" ),
	'4. the checkout panel\'s data-offer key includes completes_collection, so the label cannot go stale',
	$failures
);

// =====================================================================
// 5. NOTHING COMMERCIAL MOVED
// =====================================================================

bhp_fsl_assert(
	3.98 === round( (float) bhp_bundle_rules( 'paperback' )[3]['discount'], 2 )
	&& 4.98 === round( (float) bhp_bundle_rules( 'hardcover' )[3]['discount'], 2 ),
	'5. REGRESSION: collection discounts unchanged at $3.98 paperback / $4.98 hardcover',
	$failures
);
bhp_fsl_assert(
	1.99 === round( (float) bhp_bundle_rules( 'paperback' )[2]['discount'], 2 )
	&& 2.99 === round( (float) bhp_bundle_rules( 'hardcover' )[2]['discount'], 2 ),
	'5. REGRESSION: 2-book discounts unchanged at $1.99 paperback / $2.99 hardcover',
	$failures
);
bhp_fsl_assert(
	false === strpos( (string) $fsl_cart_src, 'update_option' )
	&& false === strpos( (string) $fsl_cart_src . (string) $fsl_js . (string) $fsl_upsell, 'BookVAULT Shipping' ),
	'5. this release writes no option and names no BookVAULT shipping method',
	$failures
);

// =====================================================================
// 6. ⭐ 1.8.25 — CYCLE144-LD-41. WHICH TITLE THE CROSS-SELL OFFERS.
// =====================================================================
/*
 * Andrew Signore, 2026-08-05, flagged after the 1.8.24 release (relayed
 * through the Chief of Staff; ⚠ RELAYED, not witnessed by this agent): on a
 * mixed two-adventure cart the cross-sell was offering a FORMAT TWIN of a
 * title already in the cart, which does not complete the collection.
 *
 *     cart:      Mariana paperback + Everest hardcover
 *     1.8.24:    offers Everest PAPERBACK  -> 3 books, 2 adventures, NOT free
 *     1.8.25:    offers The Amazon          -> 3 adventures, ships free
 *
 * ⛔ THE BUTTON'S CLAIM WAS NEVER FALSE. `completes_collection` correctly
 *    reported `false` for the old offer, so 1.8.24 printed the plain savings
 *    clause and promised no free shipping. The SELECTION was the defect, and
 *    §6.3 below is what makes the free-shipping claim on the new offer TRUE
 *    rather than merely intended: it asks the SERVER.
 */

// --- 6.1 The selection is a whole-cart decision, not a per-format one. ---

bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, 'function chooseCrossSell(' ),
	'6.1 the offer is chosen by a named whole-cart function, not inside the per-format messaging loop',
	$failures
);
bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, 'crossSell = chooseCrossSell(distinct, adventures, isMixedFormat, hasUnrelated)' ),
	'6.1 computeDrawerMeta() delegates the choice and passes it the CROSS-FORMAT adventure list',
	$failures
);
bhp_fsl_assert(
	1 === substr_count( (string) $fsl_js, 'completes_collection: !hasUnrelated' ),
	'6.1 completes_collection is still computed in exactly ONE place — the fix added no second copy of the test',
	$failures
);
bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, "if (adventures.indexOf(keys[i]) === -1)" ),
	'6.1 PASS 1 selects on the CROSS-FORMAT adventure list (a title the cart holds in no format)',
	$failures
);
bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, "var majorityIsHardcover = distinct.hardcover.length > distinct.paperback.length" ),
	'6.1 format follows the cart MAJORITY, and a tie falls to paperback because the test is strictly greater-than',
	$failures
);
bhp_fsl_assert(
	false !== strpos( (string) $fsl_js, 'if (!adventures.length)' ),
	'6.1 an empty cart returns no offer — the guard the old in-loop selection got for free from its count===0 skip',
	$failures
);

// --- 6.2 The executable harness exists and its fixture has not drifted. ---
/*
 * ⛔ THIS FILE CANNOT RUN THE SELECTION. PHP cannot execute bundle-drawer.js
 *    (see the header). tests/test-crosssell-selection.mjs DOES run it, in a
 *    stub window, over fixture carts — `node tests/test-crosssell-selection.mjs`.
 *    A source assertion could not have caught the 1.8.24 defect, because the
 *    defective code contained every string §4 greps for.
 *
 * What THIS section can do, and the reason it is here rather than there: the
 * harness carries a copy of the catalog, and a copy is a thing that drifts.
 * These assertions bind that copy to the LIVE bhp_bundle_catalog(), so moving
 * a product id fails loudly in PHP instead of leaving the harness passing
 * against a catalog the store no longer has.
 */

$fsl_mjs = bhp_fsl_read( 'tests/test-crosssell-selection.mjs' );
bhp_fsl_assert(
	null !== $fsl_mjs,
	'6.2 tests/test-crosssell-selection.mjs exists (the executable proof of the selection)',
	$failures
);

$fsl_catalog     = bhp_bundle_catalog();
$fsl_id_drift    = array();
$fsl_key_missing = array();
foreach ( $fsl_catalog as $fsl_format => $fsl_titles ) {
	foreach ( $fsl_titles as $fsl_key => $fsl_info ) {
		if ( false === strpos( (string) $fsl_mjs, $fsl_key . ':' ) ) {
			$fsl_key_missing[] = "{$fsl_format}/{$fsl_key}";
		}
		$fsl_expect = sprintf(
			'product_id: %d, variation_id: %d',
			(int) $fsl_info['product_id'],
			(int) $fsl_info['variation_id']
		);
		if ( false === strpos( (string) $fsl_mjs, $fsl_expect ) ) {
			$fsl_id_drift[] = "{$fsl_format}/{$fsl_key} ({$fsl_expect})";
		}
	}
}
bhp_fsl_assert(
	empty( $fsl_key_missing ),
	'6.2 the harness fixture names all six live catalog title keys' . ( empty( $fsl_key_missing ) ? '' : ' — MISSING: ' . implode( ', ', $fsl_key_missing ) ),
	$failures
);
bhp_fsl_assert(
	empty( $fsl_id_drift ),
	'6.2 the harness fixture carries the LIVE product/variation ids from bhp_bundle_catalog()' . ( empty( $fsl_id_drift ) ? '' : ' — DRIFTED: ' . implode( ', ', $fsl_id_drift ) ),
	$failures
);

// --- 6.3 ⭐ THE CLAIM, CHECKED AGAINST THE SERVER'S OWN SHIPPING FUNCTION. ---
/*
 * The offered title must be one that actually makes the order ship free. This
 * asserts that against `bhp_bundle_shipping_amount()` itself — the same
 * function `bhp_bundle_override_shipping_cost()` charges the customer from —
 * for the exact composition the harness proves the button now offers, and for
 * the exact composition 1.8.24 offered instead.
 *
 * ⛔ NO CART, SESSION, ORDER, PRODUCT OR OPTION IS TOUCHED. The stub cart is
 *    a local object with a get_cart() method, as in the sibling suites.
 */

if ( ! class_exists( 'BHP_FSL_Stub_Cart' ) ) {
	class BHP_FSL_Stub_Cart {
		private $items;
		public function __construct( array $items ) {
			$this->items = $items;
		}
		public function get_cart() {
			return $this->items;
		}
		public function get_applied_coupons() {
			return array();
		}
	}
}

function bhp_fsl_item( $product_id, $variation_id ) {
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => 1,
		'data'         => null,
	);
}
function bhp_fsl_ship( array $items ) {
	return bhp_bundle_shipping_amount( bhp_bundle_evaluate_cart( new BHP_FSL_Stub_Cart( $items ) ) );
}

$fsl_pb_mariana = bhp_fsl_item( 333, 334 );
$fsl_pb_everest = bhp_fsl_item( 15, 0 );
$fsl_pb_amazon  = bhp_fsl_item( 18, 0 );
$fsl_hc_everest = bhp_fsl_item( 17, 0 );

// The cart Andrew's flag describes: one paperback, one hardcover, two adventures.
$fsl_mixed = array( $fsl_pb_mariana, $fsl_hc_everest );

bhp_fsl_assert(
	3.99 === round( (float) bhp_fsl_ship( $fsl_mixed ), 2 ),
	sprintf( '6.3 baseline: the mixed 2-adventure cart pays $%.2f shipping today', (float) bhp_fsl_ship( $fsl_mixed ) ),
	$failures
);

// ⭐ THE ASSERTION THE WHOLE FIX EXISTS FOR.
$fsl_after_new = array_merge( $fsl_mixed, array( $fsl_pb_amazon ) );
bhp_fsl_assert(
	0.00 === round( (float) bhp_fsl_ship( $fsl_after_new ), 2 ),
	'6.3 ⭐ mixed cart + the OFFERED item = 3 distinct adventures and bhp_bundle_shipping_amount() returns $0.00 — the free-shipping claim on the button is TRUE',
	$failures
);

// And the offer 1.8.24 would have made, checked the same way.
$fsl_after_old = array_merge( $fsl_mixed, array( $fsl_pb_everest ) );
bhp_fsl_assert(
	0.00 !== round( (float) bhp_fsl_ship( $fsl_after_old ), 2 ),
	sprintf(
		'6.3 REPRODUCED: 1.8.24\'s offer (a format twin of Everest) leaves the cart at $%.2f shipping — 3 books, 2 adventures, no collection',
		(float) bhp_fsl_ship( $fsl_after_old )
	),
	$failures
);
bhp_fsl_assert(
	2 === count( bhp_bundle_distinct_adventures_in_cart( new BHP_FSL_Stub_Cart( $fsl_after_old ) ) )
	&& 3 === count( bhp_bundle_distinct_adventures_in_cart( new BHP_FSL_Stub_Cart( $fsl_after_new ) ) ),
	'6.3 stated as the rule itself: the old offer leaves 2 distinct adventures, the new one reaches 3',
	$failures
);

// --- 6.4 Nothing commercial moved in 1.8.25 either. ---

bhp_fsl_assert(
	false === strpos( (string) $fsl_js, 'update_option' )
	&& 0 === preg_match( '/(discount|shipping)\s*[:=]\s*\d/', (string) $fsl_js ),
	'6.4 the drawer still hardcodes no discount or shipping figure anywhere',
	$failures
);
/*
 * ⭐ AMENDED 1.8.26 (CYCLE144-LD-160). This was an EXACT-EQUALITY pin:
 *
 *     '1.8.25' === BHP_BUNDLE_PRICING_VERSION
 *
 * which passed on exactly one build and failed on every build after it. It
 * failed the moment 1.8.26 landed — not because anything regressed, but
 * because the assertion could only ever be true once. That is the same
 * defect class the checkout suite carried at 1.19.185 (an assertion that can
 * only ever fail trains a reader to ignore a red line), and it is fixed the
 * same way: assert the FLOOR, which is what the check was actually for —
 * that the version constant exists and has been bumped at least as far as
 * the release that introduced this cross-sell behaviour, so the enqueued
 * asset is cache-busted.
 */
bhp_fsl_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' )
		&& version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.25', '>=' ),
	'6.4 the running plugin reports version 1.8.25 or later (found '
		. ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ')',
	$failures
);

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL FREE-SHIPPING-LEADS TESTS PASSED\n";
	exit( 0 );
}

echo count( $failures ) . " TEST(S) FAILED:\n";
foreach ( $failures as $label ) {
	echo " - {$label}\n";
}
exit( 1 );
