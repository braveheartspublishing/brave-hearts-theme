<?php
/**
 * CARRIER ITEM 196 — THE MINI-CART FREE-SHIPPING PROGRESS LINE.
 * Theme 1.19.282 / plugin 1.8.66. `CYCLE165-LD-FINAL-FOUR`.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-panel-progress-196.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⭐ WHAT ITEM 196 ASKED FOR (⚠️ RELAYED through `chief-of-staff`, NOT
 *    witnessed first-hand by the agent that wrote this file; the three
 *    STRINGS are founder-approved):
 *
 *      1 book  -> "Add 2 more books and shipping is FREE."
 *      2 books -> "Add 1 more book and shipping is FREE."
 *      3+      -> "Your order ships FREE."
 *
 *    Count-based, on the `FD-583` any-3 count (physical books, DUPLICATES
 *    INCLUDED), recomputed on every panel update, quiet, above the subtotal.
 *
 * ⭐⭐ THE ASSERTION THAT MATTERS MOST IS §3: THE PANEL AND THE CART READ THE
 *    SAME COUNTER. A progress line that counts differently from the shipping
 *    rule is a promise the checkout refuses, and that is the exact defect
 *    class `CYCLE165-OPS-018` exists to prevent. §3 proves the threshold the
 *    copy is keyed to is the same integer the engine actually flips at, by
 *    driving `bhp_bundle_shipping_amount()` across it.
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED RATHER THAN GLOSSED. It is a PHP
 *    suite. It cannot prove the line RENDERS, that it sits above the subtotal,
 *    that it recomputes on a real quantity change, or that it is visually
 *    quiet. Those four are browser facts and are asserted in the browser QA at
 *    an asserted `window.innerWidth`, with screenshots filed with the
 *    workstream. What this proves is that the copy, the counter, the
 *    threshold, the honesty gates and the JS wiring are present, single-
 *    sourced and self-consistent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ⛔ $GLOBALS, not `global` — see test-purchase-flow-186.php. A gate that
 *    cannot report failure is not a gate. */
$GLOBALS['pp_failures'] = 0;
$GLOBALS['pp_passes']   = 0;
function pp_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['pp_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['pp_failures']++;
		echo "FAIL: $label\n";
	}
}

function pp_money( $got, $want, $label ) {
	pp_assert(
		null !== $got && abs( (float) $got - (float) $want ) < 0.005,
		sprintf( '%s  [want %.2f, got %s]', $label, $want, null === $got ? 'null' : number_format( (float) $got, 2 ) )
	);
}

/** Comment-stripped source, so a comment quoting a marker cannot satisfy a gate. */
function pp_code( $path ) {
	if ( ! file_exists( $path ) ) {
		return '';
	}
	$src = (string) file_get_contents( $path );
	if ( 'php' !== strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
		// JS: strip /* */ and // comments well enough for a presence gate.
		$src = preg_replace( '#/\*.*?\*/#s', '', $src );
		$src = preg_replace( '#^\s*//.*$#m', '', $src );
		return (string) $src;
	}
	$out = '';
	foreach ( token_get_all( $src ) as $t ) {
		if ( is_array( $t ) && in_array( $t[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$out .= is_array( $t ) ? $t[1] : $t;
	}
	return $out;
}

$pp_plugin = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing';

echo "=== PANEL FREE-SHIPPING PROGRESS LINE — carrier item 196 ===\n";
echo "Environment: " . home_url( '/' ) . "\n\n";

/* ───────────────────────────────────────────────────────────────────────────
 * §1 · THE THREE STRINGS ARE HIS, EXACTLY, AND HAVE ONE AUTHOR
 * ─────────────────────────────────────────────────────────────────────────── */
echo "--- §1 the founder-approved copy ---\n";

pp_assert( function_exists( 'bhp_bundle_ship_progress_copy' ), '1.0 bhp_bundle_ship_progress_copy() exists' );

if ( function_exists( 'bhp_bundle_ship_progress_copy' ) ) {
	$pp_copy = bhp_bundle_ship_progress_copy();

	pp_assert(
		isset( $pp_copy[1] ) && 'Add 2 more books and shipping is FREE.' === $pp_copy[1],
		'1.1 ⭐ 1 book -> "Add 2 more books and shipping is FREE." (exact bytes)'
	);
	pp_assert(
		isset( $pp_copy[2] ) && 'Add 1 more book and shipping is FREE.' === $pp_copy[2],
		'1.2 ⭐ 2 books -> "Add 1 more book and shipping is FREE." (exact bytes)'
	);
	pp_assert(
		isset( $pp_copy['earned'] ) && 'Your order ships FREE.' === $pp_copy['earned'],
		'1.3 ⭐ 3+ books -> "Your order ships FREE." (exact bytes)'
	);

	/*
	 * ⛔ THE STANDING COPY CONSTRAINTS, MACHINE-CHECKED RATHER THAN PROMISED.
	 *    §9.1 (no "we"/"us"/"our"), no em dash, no dollar figure, and the
	 *    no-urgency rule `bhp_bundle_freeship_copy()` already carries.
	 */
	$pp_all = implode( ' ', array_map( 'strval', $pp_copy ) );

	pp_assert(
		0 === preg_match( '/\b(we|our)\b/i', $pp_all ) && 0 === preg_match( '/\bus\b/i', $pp_all ),
		'1.4 §9.1 VOICE — no "we", "us" or "our" in any of the three strings'
	);
	pp_assert(
		false === strpos( $pp_all, "\xe2\x80\x94" ) && false === strpos( $pp_all, '--' ),
		'1.5 no em dash and no double hyphen'
	);
	pp_assert(
		false === strpos( $pp_all, '$' ),
		'1.6 ⛔ NO DOLLAR FIGURE — a hardcoded amount would drift out of step with the tier table'
	);
	pp_assert(
		0 === preg_match( '/(hurry|today only|limited|act now|expires|ends soon|last chance|!)/i', $pp_all ),
		'1.7 ⛔ NO URGENCY THEATRICS — no countdown, no scarcity, not even an exclamation mark'
	);
	pp_assert(
		0 === preg_match( '/\b(5|five|6|six|7|seven|8|eight|9|nine)\b/i', $pp_all ),
		'1.8 the strings name only the real remainders (1 and 2), so none can contradict the threshold'
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §2 · THE THRESHOLD HAS ONE AUTHOR
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §2 the threshold ---\n";

pp_assert( function_exists( 'bhp_bundle_freeship_book_threshold' ), '2.0 bhp_bundle_freeship_book_threshold() exists' );
if ( function_exists( 'bhp_bundle_freeship_book_threshold' ) ) {
	pp_assert(
		3 === bhp_bundle_freeship_book_threshold(),
		'2.1 the threshold is 3 books — FD-583, "any 3 books"'
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §3 · ⭐⭐ THE COPY'S THRESHOLD IS THE ENGINE'S THRESHOLD
 *
 * ⛔ THE ASSERTION THIS WHOLE SUITE EXISTS FOR. The fixtures below are NOT
 *    collections — three copies of ONE paperback title, `is_complete_
 *    collection` false, one distinct adventure — so a $0.00 at the top can
 *    only have come through branch A, the any-three branch. That is what makes
 *    it proof of the ANY-THREE rule rather than of the collection rule.
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §3 the panel promise vs the cart's own arithmetic ---\n";

function pp_eval( $count ) {
	return array(
		'is_complete_collection' => false,
		'is_mixed_format'        => false,
		'total_quantity'         => $count,
		'physical_book_count'    => $count,
		'colouring_quantity'     => 0,
		'distinct_colouring'     => 0,
		'has_colouring'          => false,
		'has_paperback'          => true,
		'has_hardcover'          => false,
		'paperback_tier'         => 1,
		'hardcover_tier'         => 0,
		'distinct_adventures'    => 1,
		'has_any_book'           => true,
		'has_unrelated'          => false,
	);
}

if ( function_exists( 'bhp_bundle_shipping_amount' ) && function_exists( 'bhp_bundle_colouring_policy' ) ) {
	$pp_policy = bhp_bundle_colouring_policy();
	pp_assert( 'any-three' === $pp_policy, "3.0 the any-three policy is live (got '{$pp_policy}') — the 3+ string is only true while it is" );

	$pp_threshold = function_exists( 'bhp_bundle_freeship_book_threshold' ) ? bhp_bundle_freeship_book_threshold() : 3;

	pp_assert(
		(float) bhp_bundle_shipping_amount( pp_eval( $pp_threshold - 1 ) ) > 0.0,
		sprintf( '3.1 ⭐ ONE BOOK BELOW the threshold (%d) the cart still CHARGES — so "add 1 more" is an honest ask', $pp_threshold - 1 )
	);
	pp_money(
		bhp_bundle_shipping_amount( pp_eval( $pp_threshold ) ),
		0.00,
		sprintf( '3.2 ⭐⭐ AT the threshold (%d, duplicates, NOT a collection) the cart charges $0.00 — "Your order ships FREE." is true', $pp_threshold )
	);
	pp_money(
		bhp_bundle_shipping_amount( pp_eval( $pp_threshold + 2 ) ),
		0.00,
		sprintf( '3.3 ABOVE the threshold (%d) it stays $0.00 — the 3+ string does not expire', $pp_threshold + 2 )
	);
	pp_assert(
		(float) bhp_bundle_shipping_amount( pp_eval( 1 ) ) > 0.0,
		'3.4 at 1 book the cart charges — so "Add 2 more books" is an honest ask, not a restatement'
	);

	/*
	 * ⛔ THE UNRELATED-ITEM SUPPRESSION, PROVED WHERE IT ACTUALLY LIVES.
	 *
	 * ⚠️ NOT in `bhp_bundle_shipping_amount()` — that function is never
	 *    reached on an unrelated cart, because `bhp_bundle_override_shipping_
	 *    cost()` returns the rates untouched BEFORE calling it. Asserting
	 *    against `bhp_bundle_shipping_amount()` here would have been a test
	 *    that passes for the wrong reason, so the assertion is made against
	 *    the caller's own early return instead. That early return is the whole
	 *    reason the JS suppresses the line on `has_unrelated`.
	 */
	$pp_guard_src = pp_code( $pp_plugin . '/includes/bundle-cart.php' );
	$pp_override  = strpos( $pp_guard_src, 'function bhp_bundle_override_shipping_cost' );
	pp_assert(
		false !== $pp_override
			&& false !== strpos( substr( $pp_guard_src, $pp_override, 1200 ), "\$eval['has_unrelated']" ),
		'3.5 ⭐ bhp_bundle_override_shipping_cost() bails on has_unrelated BEFORE pricing — which is why the panel must go quiet too'
	);
} else {
	echo "SKIP: §3 the bundle engine is not loaded on this environment\n";
}

/* ───────────────────────────────────────────────────────────────────────────
 * §4 · ONE COUNTER, TWO READERS — THE PANEL COUNTS WHAT SHIPPING COUNTS
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §4 the FD-583 counter ---\n";

pp_assert(
	function_exists( 'bhp_bundle_physical_book_count' ),
	'4.0 bhp_bundle_physical_book_count() exists — the counter the shipping rule reads'
);

$pp_data_code = pp_code( $pp_plugin . '/includes/bundle-data.php' );
$pp_cart_code = pp_code( $pp_plugin . '/includes/bundle-cart.php' );

pp_assert(
	false !== strpos( $pp_data_code, 'bhp_bundle_total_quantity_in_cart( $cart ) + bhp_bundle_colouring_quantity_in_cart( $cart )' ),
	'4.1 ⭐ the counter is catalogue quantity + colouring quantity — a QUANTITY, duplicates included'
);
pp_assert(
	false !== strpos( $pp_cart_code, "\$eval['physical_book_count']" ),
	'4.2 ⭐⭐ bhp_bundle_shipping_amount() reads that SAME field — one counter, two readers'
);

$pp_js = pp_code( $pp_plugin . '/assets/bundle-drawer.js' );

pp_assert(
	false !== strpos( $pp_js, 'function physicalBookCount' ),
	'4.3 the drawer has a JS counter of its own name'
);
pp_assert(
	false !== strpos( $pp_js, 'item.quantity' ) && false !== strpos( $pp_js, 'colouringIds' ),
	'4.4 ⭐ the JS counter sums QUANTITIES and includes colouring ids — the same two terms as PHP'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §5 · THE PAYLOAD REACHES THE PANEL, AND NOTHING IS RESTATED IN JS
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §5 the localized payload ---\n";

$pp_drawer_code = pp_code( $pp_plugin . '/includes/bundle-drawer.php' );

foreach ( array(
	'shipProgressCopy' => 'bhp_bundle_ship_progress_copy',
	'freeShipAtCount'  => 'bhp_bundle_freeship_book_threshold',
	'colouringIds'     => 'bhp_colouring_product_ids',
) as $pp_key => $pp_source ) {
	pp_assert(
		false !== strpos( $pp_drawer_code, "'" . $pp_key . "'" ),
		"5.1 the drawer localizes `{$pp_key}`"
	);
	pp_assert(
		false !== strpos( $pp_drawer_code, $pp_source ),
		"5.2 …read from {$pp_source}(), not restated in the drawer"
	);
}

pp_assert(
	false !== strpos( $pp_drawer_code, 'anyThreeActive' )
		&& false !== strpos( $pp_drawer_code, 'bhp_bundle_colouring_policy' ),
	'5.3 ⛔ the honesty gate `anyThreeActive` is derived from the live policy, not hardcoded true'
);

/*
 * ⛔ NOT ONE OF THE THREE STRINGS MAY BE WRITTEN IN JAVASCRIPT. This is the
 *    single-author rule made executable: if a future edit inlines the copy,
 *    a filter or a translation would silently stop reaching the panel.
 */
foreach ( array( 'Add 2 more books', 'Add 1 more book', 'Your order ships FREE' ) as $pp_str ) {
	pp_assert(
		false === strpos( $pp_js, $pp_str ),
		"5.4 ⛔ \"{$pp_str}\" is NOT written in bundle-drawer.js — the copy has one author, and it is PHP"
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §6 · THE FOUR SUPPRESSIONS ARE WIRED
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §6 the suppressions ---\n";

pp_assert(
	false !== strpos( $pp_js, 'function shipProgressLine' ),
	'6.0 shipProgressLine() exists'
);
pp_assert(
	false !== strpos( $pp_js, 'data.anyThreeActive' ),
	'6.1 suppressed when the any-three rule is not running'
);
pp_assert(
	false !== strpos( $pp_js, 'meta.has_unrelated' ),
	'6.2 suppressed when an unrelated item stops the shipping override running'
);
pp_assert(
	false !== strpos( $pp_js, 'data.shipRowPickupMethod' ),
	'6.3 ⭐⭐ suppressed on a SELECTED HAND-DELIVERY RATE — a school-visit parent is not being shipped anything'
);
pp_assert(
	false !== strpos( $pp_js, 'shipProgressLine(cart, meta, selectedRate)' ),
	'6.4 …and the selected rate is actually passed in, so 6.3 can fire'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §7 · PLACEMENT AND RECOMPUTATION
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §7 placement and recomputation ---\n";

$pp_summary_pos  = strpos( $pp_js, 'function renderSummary' );
$pp_progress_pos = strpos( $pp_js, 'bhp-cart-drawer__ship-progress' );
$pp_subtotal_pos = strpos( $pp_js, "addRow('Books subtotal'" );

pp_assert(
	false !== $pp_progress_pos && false !== $pp_subtotal_pos && $pp_progress_pos < $pp_subtotal_pos,
	'7.1 ⭐ the line is appended BEFORE the "Books subtotal" row — above the subtotal, as ruled'
);
pp_assert(
	false !== $pp_summary_pos && $pp_summary_pos < $pp_progress_pos,
	'7.2 …and it is inside renderSummary(), which is where the wipe-and-redraw happens'
);
pp_assert(
	false !== strpos( $pp_js, "summaryEl.innerHTML = ''" ),
	'7.3 ⭐ the region is wiped on every render, so a stale line cannot survive an add, remove or qty change'
);
pp_assert(
	false !== strpos( $pp_js, 'renderSummary(cart, meta, summaryEl)' ),
	'7.4 renderDrawer() calls it on every panel update — one route, no second path to forget'
);

$pp_css = (string) file_get_contents( $pp_plugin . '/assets/bundle-drawer.css' );
pp_assert(
	false !== strpos( $pp_css, '.bhp-cart-drawer__ship-progress' ),
	'7.5 the class is styled'
);
/*
 * ⛔ "QUIET" IS A CSS CLAIM, SO IT IS TESTED IN CSS. #5a5045 is the drawer's
 *    existing 8.0:1 quiet-text token, already used by __ship-note and
 *    __tax-note. A new colour here would mean a new visual weight nobody
 *    approved.
 */
if ( preg_match( '/\.bhp-cart-drawer__ship-progress\s*\{([^}]*)\}/', $pp_css, $pp_m ) ) {
	$pp_rule = $pp_m[1];
	pp_assert(
		false !== stripos( $pp_rule, '#5a5045' ),
		'7.6 ⭐ it uses the house quiet-text token #5a5045 (8.0:1), not a new colour'
	);
	pp_assert(
		false === stripos( $pp_rule, 'background' )
			&& false === stripos( $pp_rule, 'border' )
			&& false === stripos( $pp_rule, 'animation' )
			&& false === stripos( $pp_rule, 'uppercase' ),
		'7.7 ⛔ QUIET — no background, no border, no animation, no uppercase'
	);
}

echo "\n";
printf(
	"PANEL PROGRESS LINE (item 196): %d passed, %d failed\n",
	(int) $GLOBALS['pp_passes'],
	(int) $GLOBALS['pp_failures']
);
if ( $GLOBALS['pp_failures'] > 0 ) {
	echo "FAILED (" . (int) $GLOBALS['pp_failures'] . ")\n";
}
echo "FAILURES: " . (int) $GLOBALS['pp_failures'] . "\n";
