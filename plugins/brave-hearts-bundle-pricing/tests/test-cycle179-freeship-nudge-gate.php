<?php
/**
 * Brave Hearts Bundle Pricing — THE FREE-SHIPPING NUDGE IS GATED ON THE
 * SHIPPING RULE'S OWN COUNT. Plugin 1.8.88, `CYCLE179-LD-PLUGIN-1.8.88`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cycle179-freeship-nudge-gate.php --user=1 --url=<site>
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * THE DEFECT THIS SUITE PINS — `C-DUP-1`, seal 1410
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Found by `marketing-growth` (Merry), Finding 1 of
 * `CYCLE179-MKT-DUPLICATE-CART-COPY.md`, reading this desk's OWN 1.8.87 QA row
 * `2x Mariana + 1 Everest PB`. ⚠️ THE SEAL IS RELAYED, NOT WITNESSED HERE.
 *
 * ⛔ WHAT WAS RENDERING, READ FIRST-HAND IN A REAL BROWSER ON staging2 AT
 *    PLUGIN 1.8.87, 2026-09-08, in one drawer panel, one line above the other:
 *
 *      "Add the final adventure and your order ships free."
 *      "Your order ships FREE."
 *
 *    An offer, and the same offer reported already fulfilled. The Store API on
 *    that cart returns `total_shipping: 0`.
 *
 * ⛔ THE CAUSE IS A UNIT MISMATCH, NOT A COPY ERROR. The nudge fired on
 *    `2 === adventures.length` — a TITLES question — while the shipping rule it
 *    describes has been keyed on a PHYSICAL BOOK COUNT since 1.8.62 / `FD-583`.
 *    Two sources, one subject, counting different things since 2026-08-20.
 *
 * ⛔ THE STRING IS NOT REWRITTEN BY 1.8.88 AND THIS SUITE ASSERTS THAT TOO. The
 *    sentence is TRUE wherever adding a book actually changes the shipping; the
 *    fix is a gate, not copy.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT IS PROVED HERE, AND WHAT IS DELIBERATELY NOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ SECTION 1 PROVES THE RULE, BY RUNNING IT. For each of the three shapes the
 *    brief names, it runs the REAL `bhp_bundle_shipping_amount()` against a
 *    real `bhp_bundle_evaluate_cart()` and asks the only question the sentence
 *    actually makes: does this cart still pay for shipping, and would adding
 *    the final adventure take it to zero? That is what the nudge claims, and it
 *    is answerable in PHP without guessing.
 *
 * ⛔ SECTION 2 PROVES THE SHIPPED PREDICATE, BY READING IT — and it is a source
 *    read, stated as one. The gate itself lives in `bundle-drawer.js` and this
 *    is a PHP harness: there is no JS engine here, and a PHP re-implementation
 *    of the predicate would test the copy, not the code. So section 2 pins the
 *    shape of the shipped expression and section 1 pins the truth it has to
 *    match. ⭐ THE RENDER ITSELF IS PROVED IN A REAL BROWSER AT ALL THREE
 *    SHAPES, and that evidence lives in
 *    `ANDREW-REVIEW\2026-09-08\PLUGIN-1.8.88\04-BROWSER-QA-STAGING.md`.
 *    ⚠️ NEITHER SECTION IS REPORTED AS THE OTHER.
 *
 * Exits non-zero on any failure. Pure functions plus a stub cart only — no
 * WooCommerce session, no order, no product record is touched, nothing is
 * written anywhere.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_fng_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Same minimal stand-in the sibling suites use. Redeclared under its own name
 * rather than shared, because each test file is eval'd on its own and must run
 * standalone.
 */
class BHP_NudgeGate_Stub_Cart {
	private $items;
	public $fees_added = array();

	public function __construct( array $items ) {
		$this->items = $items;
	}
	public function get_cart() {
		return $this->items;
	}
	public function get_applied_coupons() {
		return array();
	}
	public function add_fee( $label, $amount, $taxable = false ) {
		$this->fees_added[] = array( 'label' => $label, 'amount' => $amount, 'taxable' => $taxable );
	}
}

function bhp_fng_item( $product_id, $variation_id, $price, $quantity = 1 ) {
	$product = new class( $price ) {
		private $price;
		public function __construct( $price ) {
			$this->price = $price;
		}
		public function get_price() {
			return $this->price;
		}
		public function is_on_sale() {
			return false;
		}
	};
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $quantity,
		'data'         => $product,
	);
}

function bhp_fng_eval( array $items ) {
	return bhp_bundle_evaluate_cart( new BHP_NudgeGate_Stub_Cart( $items ) );
}
function bhp_fng_ship( array $items ) {
	return (float) bhp_bundle_shipping_amount( bhp_fng_eval( $items ) );
}
function bhp_fng_read( $rel ) {
	$path = BHP_BUNDLE_PRICING_DIR . $rel;
	return file_exists( $path ) ? (string) file_get_contents( $path ) : null;
}

/*
 * The six approved editions, read from the catalogue rather than written down
 * again, so this suite cannot drift from the product records the way a
 * hardcoded id table would.
 */
$fng_catalog = bhp_bundle_catalog();
$fng_pb      = $fng_catalog['paperback'];

function bhp_fng_pb( $title_key, $quantity = 1 ) {
	$info = bhp_bundle_catalog()['paperback'][ $title_key ];
	return bhp_fng_item( (int) $info['product_id'], (int) $info['variation_id'], 11.99, $quantity );
}

$fng_threshold = (int) bhp_bundle_freeship_book_threshold();

echo "\n=== 0. THE ENVIRONMENT THE GATE READS ===\n";
bhp_fng_assert( 3 === $fng_threshold, '0. bhp_bundle_freeship_book_threshold() is 3 (the count the sentence talks about)', $failures );
bhp_fng_assert( function_exists( 'bhp_bundle_physical_book_count' ), '0. bhp_bundle_physical_book_count() exists — the PHP side of the gate', $failures );

/*
 * ⛔ THE THREE SHAPES THE BRIEF NAMES, IN ITS OWN WORDS:
 *      2 distinct        -> the nudge SHOWS
 *      2 copies + 1 other-> the nudge is HIDDEN   (this is the live defect)
 *      3 distinct        -> the nudge is HIDDEN   ("earned" leads instead)
 *
 * ⭐ NAMED AFTER MERRY'S SHAPE TAXONOMY (A, E, C) so the two documents can be
 *    read against each other without a translation step.
 */
$fng_shape_a = array( bhp_fng_pb( 'mariana', 1 ), bhp_fng_pb( 'everest', 1 ) );                 // 2 books, 2 titles
$fng_shape_e = array( bhp_fng_pb( 'mariana', 2 ), bhp_fng_pb( 'everest', 1 ) );                 // 3 books, 2 titles
$fng_shape_c = array( bhp_fng_pb( 'mariana', 1 ), bhp_fng_pb( 'everest', 1 ), bhp_fng_pb( 'amazon', 1 ) ); // 3 books, 3 titles

echo "\n=== 1. THE RULE, RUN — DOES THIS CART STILL PAY FOR SHIPPING? ===\n";
/*
 * ⭐ THIS IS THE WHOLE TEST, STATED AS A QUESTION ABOUT MONEY. The sentence
 *    "Add the final adventure and your order ships free" is TRUE on a cart if
 *    and only if the cart is NOT already free. Everything below runs the real
 *    shipping function to answer that, on carts built from the real catalogue.
 */

// ---- SHAPE A — 1x Mariana + 1x Everest paperback. THE NUDGE SHOWS. ----
$fng_eval_a = bhp_fng_eval( $fng_shape_a );
bhp_fng_assert( 2 === (int) $fng_eval_a['physical_book_count'], '1A. 2 distinct: physical_book_count is 2', $failures );
bhp_fng_assert( 2 === (int) $fng_eval_a['distinct_adventures'], '1A. 2 distinct: distinct_adventures is 2 (the OLD trigger fires here too)', $failures );
bhp_fng_assert( (int) $fng_eval_a['physical_book_count'] < $fng_threshold, '1A. ⭐ 2 distinct: the cart is BELOW the free-ship threshold, so the gate OPENS', $failures );
bhp_fng_assert( 2.99 === bhp_fng_ship( $fng_shape_a ), '1A. 2 distinct: the order still PAYS $2.99 shipping — the nudge is TRUE and must show', $failures );
bhp_fng_assert( 0.00 === bhp_fng_ship( $fng_shape_c ), '1A. 2 distinct: ...and adding the final adventure really does take it to $0.00', $failures );

// ---- SHAPE E — 2x Mariana + 1x Everest paperback. THE NUDGE IS HIDDEN. ----
/*
 * ⛔⛔ THIS IS THE LIVE DEFECT ROW. Three books, two titles. The old trigger
 *    (`2 === distinct_adventures`) fires and the claim is FALSE, which is
 *    exactly why the assertion below is written as a contradiction and not as a
 *    count: the two quantities disagree, and the money is the one that decides.
 */
$fng_eval_e = bhp_fng_eval( $fng_shape_e );
bhp_fng_assert( 3 === (int) $fng_eval_e['physical_book_count'], '1E. 2 copies + 1 other: physical_book_count is 3', $failures );
bhp_fng_assert( 2 === (int) $fng_eval_e['distinct_adventures'], '1E. 2 copies + 1 other: distinct_adventures is 2 — THE OLD TRIGGER STILL FIRES, which is the defect', $failures );
bhp_fng_assert( ! ( (int) $fng_eval_e['physical_book_count'] < $fng_threshold ), '1E. ⛔ 2 copies + 1 other: the cart is AT the threshold, so the gate CLOSES', $failures );
bhp_fng_assert( 0.00 === bhp_fng_ship( $fng_shape_e ), '1E. ⛔ 2 copies + 1 other: the order ALREADY ships free — "add the final adventure and your order ships free" is FALSE here', $failures );
/*
 * ⭐ AND THE ASK GAINS THE CUSTOMER NOTHING, which is the second half of why the
 *    line must go: adding the third adventure moves the shipping by $0.00.
 */
$fng_shape_e_plus = array( bhp_fng_pb( 'mariana', 2 ), bhp_fng_pb( 'everest', 1 ), bhp_fng_pb( 'amazon', 1 ) );
bhp_fng_assert( 0.00 === bhp_fng_ship( $fng_shape_e_plus ), '1E. ⛔ ...and adding the final adventure changes the shipping by $0.00 — the ask buys nothing', $failures );

// ---- SHAPE C — three distinct adventures. THE NUDGE IS HIDDEN. ----
$fng_eval_c = bhp_fng_eval( $fng_shape_c );
bhp_fng_assert( 3 === (int) $fng_eval_c['physical_book_count'], '1C. 3 distinct: physical_book_count is 3', $failures );
bhp_fng_assert( 3 === (int) $fng_eval_c['distinct_adventures'], '1C. 3 distinct: distinct_adventures is 3 — the nudge never fired here, the "earned" line leads', $failures );
bhp_fng_assert( ! ( (int) $fng_eval_c['physical_book_count'] < $fng_threshold ), '1C. 3 distinct: the gate CLOSES here too', $failures );
bhp_fng_assert( 0.00 === bhp_fng_ship( $fng_shape_c ), '1C. 3 distinct: the order ships free', $failures );
bhp_fng_assert( ! empty( $fng_eval_c['is_complete_collection'] ), '1C. 3 distinct: this cart IS a complete collection — the only one of the three that may say so', $failures );
bhp_fng_assert( empty( $fng_eval_e['is_complete_collection'] ), '1E. REGRESSION: 3 books / 2 titles is NOT a complete collection (the "earned" line must not leak onto it)', $failures );

echo "\n=== 2. THE SHIPPED PREDICATE (SOURCE READ, LABELLED AS ONE) ===\n";
$fng_js = bhp_fng_read( 'assets/bundle-drawer.js' );
bhp_fng_assert( null !== $fng_js, '2. assets/bundle-drawer.js is readable', $failures );

bhp_fng_assert(
	false !== strpos( (string) $fng_js, 'var physicalBooksInCart = physicalBookCount(cart);' ),
	'2. the drawer counts PHYSICAL BOOKS through the existing 1.8.66 mirror, not a new counter',
	$failures
);
bhp_fng_assert(
	false !== strpos( (string) $fng_js, 'window.bhpDrawerData.freeShipAtCount' ),
	'2. the threshold is read from the localized engine value, never a JS literal 3',
	$failures
);
bhp_fng_assert(
	1 === preg_match( '/var freeShipLeads = !hasUnrelated\s*&&\s*2 === adventures\.length\s*&&\s*physicalBooksInCart < freeShipThreshold;/s', (string) $fng_js ),
	'2. ⭐ freeShipLeads is the THREE-term gate: nothing unrelated, two adventures, AND below the free-ship threshold',
	$failures
);
/*
 * ⛔ ONE PREDICATE, TWO READERS. `freeShipLeads` both triggers the nudge and
 *    suppresses the count-2 progress line. If the render site restated the
 *    condition instead of reading the variable, the two could drift and the
 *    panel could suppress a line for a nudge that never rendered. This row is
 *    the anti-drift row and it is the reason the fix is one variable.
 */
bhp_fng_assert(
	false !== strpos( (string) $fng_js, 'if (freeShipLeads && freeShipCopy.nudge) {' ),
	'2. ⛔ the render site READS freeShipLeads rather than restating its terms (one predicate, two readers, cannot drift)',
	$failures
);
/*
 * ⛔ ASSERTED WITH A LINE ANCHOR, NOT A PLAIN `strpos`, AND THE REASON IS THE
 *    SAME TRAP THAT CAUGHT THE PHP ROWS: the render site now carries a STRIKE
 *    that preserves the superseded condition verbatim, so a bare substring
 *    search finds the old trigger in its own obituary and reports the fix as
 *    missing. ⭐ THE DISCRIMINATOR IS STRUCTURAL, NOT COSMETIC: executable JS
 *    reaches `if (` after nothing but whitespace, while the preserved line
 *    sits behind a comment's ` * ` and `~~` markers, which `^\s*` cannot
 *    cross. ⛔ IT IS NOT AN INDENTATION GUESS — reflowing the comment cannot
 *    make this pass, because no reflow removes the `*`.
 */
bhp_fng_assert(
	0 === preg_match( '/^\s*if \(2 === adventures\.length && freeShipCopy\.nudge\)/m', (string) $fng_js ),
	'2. the old two-term trigger is GONE from the render site (executable code only; the strike that preserves it does not count)',
	$failures
);
/*
 * ⛔ THE `earned` LINE IS STILL A TITLES TEST AND MUST STAY ONE. "Your complete
 *    collection ships free." is a claim about owning the collection; three
 *    copies of one book is not one. Gating it on a book count would have been
 *    the same class of defect in the opposite direction.
 */
bhp_fng_assert(
	false !== strpos( (string) $fng_js, 'adventures.length >= 3 && freeShipCopy.earned' ),
	'2. ⛔ REGRESSION: the "complete collection ships free" line is still a TITLES test, untouched',
	$failures
);

echo "\n=== 3. NOT ONE STRING WAS REWRITTEN ===\n";
/*
 * ⭐ THE COPY FIX IS NO COPY FIX (Merry, Finding 1). The nudge is true where it
 *    now fires, so the sentence survives byte-for-byte. These rows exist so a
 *    later pass cannot "fix" the defect a second time by editing the words.
 */
$fng_copy = bhp_bundle_freeship_copy();
bhp_fng_assert(
	'Add the final adventure and your order ships free.' === $fng_copy['nudge'],
	'3. the nudge string is byte-unchanged',
	$failures
);
bhp_fng_assert(
	'Your complete collection ships free.' === $fng_copy['earned'],
	'3. the earned string is byte-unchanged',
	$failures
);
$fng_drawer_php = bhp_fng_read( 'includes/bundle-drawer.php' );
bhp_fng_assert(
	false !== strpos( (string) $fng_drawer_php, "'freeShipCopy' => bhp_bundle_freeship_copy()" ),
	'3. both strings still come from the one server-side source, so a filter still moves every surface at once',
	$failures
);

echo "\n=== 4. THE DEAD CLASSIC SURFACE IS GONE (seal 1411) ===\n";
/*
 * ⛔ `bhp_bundle_print_progress_messages()` CARRIED ITS OWN COPY OF THIS DEFECT
 *    (`2 === (int) $eval['distinct_adventures']`, no book-count gate). It was
 *    removed at 1.8.88 rather than fixed, because it hooked the CLASSIC cart and
 *    this store has none — VERIFIED LIVE at 1.8.87, while it was still hooked:
 *    `.bhp-bundle-message` count was 0 on both /cart/ and /checkout/.
 *    ⭐ ASSERTED HERE AS WELL AS IN test-freeship-leads.php ON PURPOSE: this is
 *    the suite that owns the defect, and a reader who finds only one copy of the
 *    fix should not have to guess whether the other copy was missed.
 */
/*
 * PLUGIN 1.8.88 - STRIP COMMENTS BEFORE ASSERTING THAT SOMETHING IS ABSENT.
 *
 * ⛔ THIS HELPER EXISTS BECAUSE THE NAIVE ASSERTION WAS WRONG AND THE ZIP
 *    CHECK CAUGHT IT BEFORE IT SHIPPED. `bundle-cart.php` now carries a dated
 *    STRIKE that quotes the removed function signature, its two `add_action`
 *    lines and its copy table VERBATIM - that is the house additive-only
 *    discipline working as intended. A plain `strpos()` for those tokens
 *    therefore matches the STRIKE and reports the dead code as still present.
 *
 * ⭐ SO ABSENCE IS ASSERTED AGAINST CODE ONLY, VIA `token_get_all()`, WHILE
 *    PRESENCE OF THE STRIKE IS ASSERTED AGAINST THE FULL SOURCE. The two
 *    questions are different and are asked of different inputs:
 *      "is the function still declared or hooked?"  -> code only
 *      "is the removal recorded at the line?"       -> full source
 *
 * ⛔ NEVER REPLACE THIS WITH A REGEX ON `^function`. A `^`-anchored needle
 *    passes today only because the strike happens to indent its quotation; a
 *    later reflow of that comment would silently turn this suite green on a
 *    file that still declares the function.
 */
function bhp_fng_code_only( $src ) {
	$out = '';
	foreach ( token_get_all( (string) $src ) as $tok ) {
		if ( is_array( $tok ) ) {
			if ( T_COMMENT === $tok[0] || T_DOC_COMMENT === $tok[0] ) {
				continue;
			}
			$out .= $tok[1];
		} else {
			$out .= $tok;
		}
	}
	return $out;
}

$fng_cart_php = bhp_fng_read( 'includes/bundle-cart.php' );
$fng_cart_code = bhp_fng_code_only( $fng_cart_php );
bhp_fng_assert(
	false === strpos( $fng_cart_code, 'bhp_bundle_print_progress_messages' ),
	'4. the second, dead copy of this defect was REMOVED, not fixed twice',
	$failures
);
bhp_fng_assert(
	false !== strpos( (string) $fng_cart_php, 'bhp_bundle_freeship_book_threshold()' ),
	'4. the strike records the gate any revival of that surface must carry',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'FAILURES: ' . count( $failures ) . "\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "ALL PASS\n";
