<?php
/**
 * Brave Hearts Bundle Pricing — THE COLLECTION LABEL vs THE COUNT PRICE.
 * Plugin 1.8.90, `CYCLE179-LD-PLUGIN-1.8.90`. Founder seal 1436, CX-3 option B.
 *
 * ⭐ EXTENDED AT 1.8.91 / `CYCLE179-LD-PLUGIN-1.8.91`: §0's version stamp is
 *   advanced (a stamp, not a behavioural assertion — see its own note) and a
 *   new §6 covers the "- Ships Free" gate that closes finding F1. §§1–5 are
 *   BYTE-UNTOUCHED and still assert that seal 1436 and seal 1359 held.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cycle179-collection-copy.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ WHAT THIS FILE IS, AND WHAT IT DELIBERATELY IS NOT
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT DOES NOT TEST THE FIX. It cannot. Every surface seal 1436 moves is
 *    rendered in JavaScript against a Store API cart, and PHP cannot execute
 *    any of it. The fix is proved by EXECUTION in
 *    `tests/test-cycle179-collection-copy.mjs`, which loads the real
 *    `assets/bundle-drawer.js` and runs the four cart shapes through it.
 *
 * ⭐ THIS FILE PROVES THE THREE THINGS THAT MUST NOT HAVE MOVED, plus the
 *    shipped source of the two gates the browser will exercise:
 *
 *      §1 THE PRICE. Seal 1359's count-keyed ladder, at every boundary it
 *         has, run against the LIVE functions. This build's whole risk is
 *         that a copy change quietly re-keyed a discount.
 *      §2 THE COUPON. `paperback_titles_tier` / `hardcover_titles_tier` are
 *         still computed from DISTINCT TITLES and still what the audience
 *         coupon reads. The brief holds these at distinct titles; D1 is
 *         still Andrew's and this suite asserts nobody quietly closed it.
 *      §3 THE STRINGS. Every customer-facing string this release touches the
 *         ROUTING of is asserted BYTE-PRESENT and unedited. ⛔ 1.8.90 authors
 *         no copy; if this section fails, someone coined one.
 *      §4 THE SHIPPED GATES, by source read — LABELLED AS A SOURCE READ AND
 *         NOT REPORTED AS A RENDER.
 *      §5 THE ALLOWLIST. No new quoted literal exists inside the two changed
 *         functions. This is the check that would catch a well-meaning
 *         "small wording tweak" that nobody approved.
 *
 * ⚠️ THE RULING IS RELAYED, NOT WITNESSED BY THIS DESK (Standing Rules §9.2).
 *    Seal 1436 reached this agent through `chief-of-staff`. The DEFECT it
 *    fixes is not relayed: `commerce-cx` measured it live on production
 *    (`CYCLE179-CX-PROD-AUDIT-407`, CX-3, at 390).
 *
 * Exits non-zero on any failure. Reads pure functions and source files only:
 * no cart, no session, no order, no product, no option is written.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_ccc_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_ccc_read( $rel ) {
	$path = BHP_BUNDLE_PRICING_DIR . $rel;
	return file_exists( $path ) ? (string) file_get_contents( $path ) : null;
}

/**
 * Strip JS comments so an assertion about CODE cannot be satisfied by a
 * COMMENT. 1.8.88 hit exactly this trap from the other direction (an absence
 * assertion matched the dated strike that preserved the removed signature),
 * and 1.8.90's comments quote every string in this suite verbatim — so a
 * naive `strpos` over raw source would pass no matter what the code did.
 *
 * Deliberately simple and deliberately conservative: it is only ever used to
 * make an assertion HARDER to satisfy, never easier.
 */
function bhp_ccc_strip_js_comments( $src ) {
	$out    = '';
	$len    = strlen( $src );
	$i      = 0;
	$quote  = '';
	while ( $i < $len ) {
		$c  = $src[ $i ];
		$c2 = ( $i + 1 < $len ) ? $src[ $i + 1 ] : '';
		if ( '' !== $quote ) {
			$out .= $c;
			if ( '\\' === $c && $i + 1 < $len ) {
				$out .= $c2;
				$i   += 2;
				continue;
			}
			if ( $c === $quote ) {
				$quote = '';
			}
			$i++;
			continue;
		}
		if ( '"' === $c || "'" === $c || '`' === $c ) {
			$quote = $c;
			$out  .= $c;
			$i++;
			continue;
		}
		if ( '/' === $c && '*' === $c2 ) {
			$end = strpos( $src, '*/', $i + 2 );
			$i   = ( false === $end ) ? $len : $end + 2;
			$out .= ' ';
			continue;
		}
		if ( '/' === $c && '/' === $c2 ) {
			$end = strpos( $src, "\n", $i );
			$i   = ( false === $end ) ? $len : $end;
			$out .= ' ';
			continue;
		}
		$out .= $c;
		$i++;
	}
	return $out;
}

/** Body of a top-level `function name(...) { ... }`, brace-matched. */
function bhp_ccc_function_body( $src, $name ) {
	$needle = 'function ' . $name . '(';
	$pos    = strpos( $src, $needle );
	if ( false === $pos ) {
		return null;
	}
	$open = strpos( $src, '{', $pos );
	if ( false === $open ) {
		return null;
	}
	$depth = 0;
	$len   = strlen( $src );
	for ( $i = $open; $i < $len; $i++ ) {
		if ( '{' === $src[ $i ] ) {
			$depth++;
		} elseif ( '}' === $src[ $i ] ) {
			$depth--;
			if ( 0 === $depth ) {
				return substr( $src, $open, $i - $open + 1 );
			}
		}
	}
	return null;
}

/** Every single- or double-quoted literal in a chunk of comment-stripped JS. */
function bhp_ccc_literals( $code ) {
	$found = array();
	if ( preg_match_all( '/"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\'/', $code, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $hit ) {
			$found[] = ( '' !== $hit[1] || '"' === substr( $hit[0], 0, 1 ) ) ? $hit[1] : $hit[2];
		}
	}
	return $found;
}

$drawer_js_raw   = bhp_ccc_read( 'assets/bundle-drawer.js' );
$checkout_js_raw = bhp_ccc_read( 'assets/checkout-upsell.js' );
$drawer_php_raw  = bhp_ccc_read( 'includes/bundle-drawer.php' );

bhp_ccc_assert( null !== $drawer_js_raw, '0. assets/bundle-drawer.js is readable', $failures );
bhp_ccc_assert( null !== $checkout_js_raw, '0. assets/checkout-upsell.js is readable', $failures );
bhp_ccc_assert( null !== $drawer_php_raw, '0. includes/bundle-drawer.php is readable', $failures );

$drawer_js   = bhp_ccc_strip_js_comments( (string) $drawer_js_raw );
$checkout_js = bhp_ccc_strip_js_comments( (string) $checkout_js_raw );

// =====================================================================
// §0 VERSION
// =====================================================================

/*
 * ⚠️ 1.8.91 — THE PIN IS ADVANCED, AND IT IS SAID OUT LOUD RATHER THAN
 *    QUIETLY EDITED. This assertion is a VERSION STAMP: it fails
 *    mechanically on every bump and carries no behaviour. Advancing it is
 *    the correct maintenance of a stamp; it is NOT the same act as
 *    rewriting a failing BEHAVIOURAL assertion to go green, which is how a
 *    real signal gets erased.
 *
 * ⛔ THE DISTINCTION IS LOAD-BEARING IN THIS EXACT BUILD. `1.19.409`'s
 *    theme file `tests/test-cycle179-407.php` §6.2 carries the same class
 *    of stamp, pinned at 1.8.89, and it is DELIBERATELY LEFT FAILING here
 *    — it is a THEME file and outside this lock's declared write paths.
 *    This one is inside the plugin tree and inside this lane, so it is
 *    maintained rather than routed. Both choices are reported.
 */
bhp_ccc_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && '1.8.91' === BHP_BUNDLE_PRICING_VERSION,
	'0. the plugin declares version 1.8.91 (got ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ')',
	$failures
);

// =====================================================================
// §1 THE PRICE DID NOT MOVE — SEAL 1359, RUN AGAINST THE LIVE FUNCTIONS
// =====================================================================

bhp_ccc_assert( function_exists( 'bhp_bundle_qualifying_tier_by_count' ), '1. bhp_bundle_qualifying_tier_by_count() still exists', $failures );

if ( function_exists( 'bhp_bundle_qualifying_tier_by_count' ) ) {
	$ladder = array( 0 => 0, 1 => 0, 2 => 2, 3 => 3, 4 => 3, 9 => 3 );
	foreach ( $ladder as $count => $expected ) {
		$got = (int) bhp_bundle_qualifying_tier_by_count( $count );
		bhp_ccc_assert(
			$got === $expected,
			"1. seal 1359 UNTOUCHED: {$count} book(s) of a format still reads tier {$expected} (got {$got})",
			$failures
		);
	}
}

/*
 * The discount and shipping table itself. If 1.8.90 had touched a figure this
 * is where it would show, and the figures are read from the live function
 * rather than restated as a second table.
 */
/*
 * ⚠️ `bhp_bundle_rules()` TAKES A FORMAT AND RETURNS THAT FORMAT'S TABLE. It
 *    is NOT a two-level array. This suite called it with no argument on its
 *    first staging run and died with an ArgumentCountError; the mistake was
 *    mine, it is recorded rather than quietly corrected, and it is exactly
 *    why the suite is executed on the server instead of reasoned about.
 */
if ( function_exists( 'bhp_bundle_rules' ) ) {
	$want = array(
		array( 'paperback', 2, 1.99, 2.99 ),
		array( 'paperback', 3, 3.98, 0.00 ),
		array( 'hardcover', 2, 2.99, 3.99 ),
		array( 'hardcover', 3, 4.98, 0.00 ),
	);
	foreach ( $want as $row ) {
		list( $fmt, $tier, $disc, $ship ) = $row;
		$table  = bhp_bundle_rules( $fmt );
		$have_d = isset( $table[ $tier ]['discount'] ) ? (float) $table[ $tier ]['discount'] : -1;
		$have_s = isset( $table[ $tier ]['shipping'] ) ? (float) $table[ $tier ]['shipping'] : -1;
		bhp_ccc_assert(
			abs( $have_d - $disc ) < 0.001 && abs( $have_s - $ship ) < 0.001,
			"1. the {$fmt} tier-{$tier} row is unchanged by this build (discount {$have_d}, shipping {$have_s})",
			$failures
		);
	}
}

bhp_ccc_assert(
	function_exists( 'bhp_bundle_freeship_book_threshold' ) && 3 === (int) bhp_bundle_freeship_book_threshold(),
	'1. the free-shipping book threshold is still 3',
	$failures
);

// =====================================================================
// §2 THE COUPON PATH IS STILL DISTINCT-TITLE — D1 IS STILL ANDREW'S
// =====================================================================

bhp_ccc_assert(
	function_exists( 'bhp_bundle_qualifying_tier' ),
	'2. bhp_bundle_qualifying_tier() (the DISTINCT-TITLE tier) still exists',
	$failures
);

if ( function_exists( 'bhp_bundle_qualifying_tier' ) ) {
	bhp_ccc_assert(
		3 === (int) bhp_bundle_qualifying_tier( array( 'mariana', 'everest', 'amazon' ) ),
		'2. three DISTINCT titles is tier 3 on the titles ladder',
		$failures
	);
	bhp_ccc_assert(
		2 === (int) bhp_bundle_qualifying_tier( array( 'mariana', 'everest' ) ),
		'2. two DISTINCT titles is tier 2 on the titles ladder',
		$failures
	);
	bhp_ccc_assert(
		0 === (int) bhp_bundle_qualifying_tier( array( 'mariana' ) ),
		'2. ⭐ one distinct title is tier 0 on the titles ladder — three COPIES can never reach the coupon this way',
		$failures
	);
}

$cart_src = bhp_ccc_read( 'includes/bundle-cart.php' );
bhp_ccc_assert(
	is_string( $cart_src ) && false !== strpos( $cart_src, 'paperback_titles_tier' ),
	'2. the audience-coupon path still reads paperback_titles_tier (D1 not silently widened by this build)',
	$failures
);

$data_src = bhp_ccc_read( 'includes/bundle-data.php' );
bhp_ccc_assert(
	is_string( $data_src ) && false !== strpos( $data_src, "'paperback_titles_tier' => bhp_bundle_qualifying_tier(" ),
	'2. and evaluate_cart still builds it from the DISTINCT-title function',
	$failures
);

// =====================================================================
// §3 NO CUSTOMER-FACING STRING WAS AUTHORED, EDITED OR REMOVED
// =====================================================================

/*
 * ⛔ Asserted against COMMENT-STRIPPED source. 1.8.90's own comments quote
 *    every one of these verbatim, so a raw-source check would pass even if the
 *    code no longer contained them.
 */
$approved_in_js = array(
	'Included in your 2-book savings',
	'Included in your complete-set savings',
	'Complete-set savings',
	'2-book savings',
);
foreach ( $approved_in_js as $needle ) {
	bhp_ccc_assert(
		false !== strpos( $drawer_js, $needle ),
		'3. approved string still present in drawer CODE (not just a comment): "' . $needle . '"',
		$failures
	);
}

if ( function_exists( 'bhp_bundle_ship_progress_copy' ) ) {
	$sp = bhp_bundle_ship_progress_copy();
	bhp_ccc_assert(
		isset( $sp['earned'] ) && 'Your order ships FREE.' === $sp['earned'],
		'3. ⭐ the founder-approved line the fixed cart still reads is unchanged: "Your order ships FREE." (item 196)',
		$failures
	);
}

if ( function_exists( 'bhp_bundle_freeship_copy' ) ) {
	$fs = bhp_bundle_freeship_copy();
	bhp_ccc_assert(
		isset( $fs['earned'] ) && 'Your complete collection ships free.' === $fs['earned'],
		'3. the collection free-shipping line is unchanged (it was already a titles claim and stays one)',
		$failures
	);
	bhp_ccc_assert(
		isset( $fs['cta_clause'] ) && ' - Ships Free' === $fs['cta_clause'],
		'3. the CTA clause is unchanged — a HYPHEN, never an em dash (B4)',
		$failures
	);
}

bhp_ccc_assert(
	false !== strpos( (string) $drawer_php_raw, "3 => 'Best Value - Complete Paperback Collection'," ),
	'3. progressCopy[paperback][3] is byte-unchanged',
	$failures
);
bhp_ccc_assert(
	false !== strpos( (string) $drawer_php_raw, "3 => 'Best Value - Complete Hardcover Collection'," ),
	'3. progressCopy[hardcover][3] is byte-unchanged',
	$failures
);

$upsell_copy = function_exists( 'bhp_bundle_checkout_upsell_copy' ) ? bhp_bundle_checkout_upsell_copy() : array();
bhp_ccc_assert(
	isset( $upsell_copy['heading'] ) && 'Complete the collection' === $upsell_copy['heading'],
	'3. ⭐ the eyebrow string itself is UNCHANGED — 1.8.90 changes WHICH CART SEES IT, never the words',
	$failures
);

// =====================================================================
// §4 THE SHIPPED GATES — ⚠️ SOURCE READ, NOT A RENDER PROOF
// =====================================================================

bhp_ccc_assert(
	false !== strpos( $drawer_js, 'tier_without_set' ),
	'4. (source) the drawer computes tier_without_set',
	$failures
);
bhp_ccc_assert(
	false !== strpos( $drawer_js, 'format_set' ),
	'4. (source) the drawer computes format_set',
	$failures
);
bhp_ccc_assert(
	false !== strpos( $drawer_js, "'colouring' === cs.format || (meta && meta.tier_without_set)" ),
	'4. (source) the drawer eyebrow suppression carries the new term alongside the 1.8.65 / 1.8.68 ones',
	$failures
);
bhp_ccc_assert(
	false !== strpos( $checkout_js, 'collectionCopyAllowed' )
	&& false !== strpos( $checkout_js, 'COPY.heading && collectionCopyAllowed' ),
	'4. (source) the CHECKOUT heading is gated too — the drawer and the checkout read one shared string and must not diverge',
	$failures
);
bhp_ccc_assert(
	false !== strpos( $checkout_js, 'buildPanel(cs, addItem, meta)' ),
	'4. (source) the checkout panel is built WITH meta',
	$failures
);
bhp_ccc_assert(
	false !== strpos( $checkout_js, 'meta.tier_without_set' )
	&& false !== strpos( $checkout_js, 'var offerKey' ),
	'4. ⭐ (source) tier_without_set is part of the panel identity key, so a 2->3 book transition actually redraws',
	$failures
);
bhp_ccc_assert(
	false !== strpos( $drawer_js, 'qualifyingNote: itemQualifyingNote' )
	&& false !== strpos( $drawer_js, 'savingsRowLabel: savingsRowLabel' ),
	'4. (source) both label functions are exported as the .mjs test seam',
	$failures
);
bhp_ccc_assert(
	file_exists( BHP_BUNDLE_PRICING_DIR . 'tests/test-cycle179-collection-copy.mjs' ),
	'4. ⭐ the EXECUTABLE suite that actually proves the fix ships alongside this one',
	$failures
);

// =====================================================================
// §5 THE ALLOWLIST — NO NEW LITERAL INSIDE THE TWO CHANGED FUNCTIONS
// =====================================================================

$note_body  = bhp_ccc_function_body( $drawer_js, 'itemQualifyingNote' );
$label_body = bhp_ccc_function_body( $drawer_js, 'savingsRowLabel' );

bhp_ccc_assert( null !== $note_body, '5. itemQualifyingNote() body located', $failures );
bhp_ccc_assert( null !== $label_body, '5. savingsRowLabel() body located', $failures );

$allowed = array(
	'', 'item_note', 'FREE with your collection',
	'Included in your 2-book savings', 'Included in your complete-set savings',
	'Complete-set savings', '2-book savings', ' (', ')', 'paperback', 'Paperback', 'Hardcover',
	'format_set',
);

foreach ( array( 'itemQualifyingNote' => $note_body, 'savingsRowLabel' => $label_body ) as $fn => $body ) {
	if ( null === $body ) {
		continue;
	}
	$unexpected = array();
	foreach ( bhp_ccc_literals( $body ) as $lit ) {
		if ( ! in_array( $lit, $allowed, true ) ) {
			$unexpected[] = $lit;
		}
	}
	bhp_ccc_assert(
		empty( $unexpected ),
		"5. ⛔ {$fn}() contains NO literal outside the approved allowlist"
		. ( empty( $unexpected ) ? '' : ' — FOUND: ' . implode( ' | ', array_map( 'strval', $unexpected ) ) ),
		$failures
	);
}

/*
 * ⭐ AND THE FALLBACK IS THE SERVER'S OWN FEE NAME, NOT A STRING THIS FILE
 *    OWNS. Asserted structurally: `savingsRowLabel` returns its `feeName`
 *    argument on the tier-3-without-the-set branch. If someone ever replaces
 *    that with a literal, §5 above catches the literal and this catches the
 *    lost provenance.
 */
bhp_ccc_assert(
	null !== $label_body && false !== strpos( (string) $label_body, 'return feeName;' ),
	'5. ⭐ the tier-3-without-a-set row returns the INVOICE\'S OWN fee name, so the drawer quotes WooCommerce rather than paraphrasing it',
	$failures
);

// =====================================================================
// §6 1.8.91 — THE SHIPS-FREE GATE. ⚠️ SOURCE READ, NOT A RENDER PROOF.
//
// ⛔ WHAT PROVES THE BEHAVIOUR IS `test-cycle179-collection-copy.mjs` §H,
//    which EXECUTES the real function over six carts and reads the actual
//    button label. This section proves only the three things PHP can:
//    that the gate exists in both surfaces, that the checkout panel's
//    identity key carries it, and — the one that matters most — that the
//    new function coined NO customer-facing string.
// =====================================================================

$cta_body = bhp_ccc_function_body( $drawer_js, 'crossSellCtaLabel' );
bhp_ccc_assert( null !== $cta_body, '6. crossSellCtaLabel() body located (1.8.91 test seam)', $failures );

/*
 * ⭐ THE ALLOWLIST IS THE POINT OF THIS SECTION. 1.8.91 is a TRUTHFULNESS
 *    fix: it removes a false claim and adds none. Every literal the new
 *    function may contain already existed in this file at 1.8.90, in the
 *    inline block this function was extracted from. Anything else is a
 *    coined string and is Andrew's, not this desk's.
 */
$cta_allowed = array( '', 'Add This Adventure', ' - Save ' );

if ( null !== $cta_body ) {
	$cta_unexpected = array();
	foreach ( bhp_ccc_literals( $cta_body ) as $lit ) {
		if ( ! in_array( $lit, $cta_allowed, true ) ) {
			$cta_unexpected[] = $lit;
		}
	}
	bhp_ccc_assert(
		empty( $cta_unexpected ),
		'6. ⛔ crossSellCtaLabel() contains NO literal outside the approved allowlist — 1.8.91 coins no customer-facing string'
		. ( empty( $cta_unexpected ) ? '' : ' — FOUND: ' . implode( ' | ', array_map( 'strval', $cta_unexpected ) ) ),
		$failures
	);

	bhp_ccc_assert(
		false !== strpos( (string) $cta_body, 'already_ships_free' ),
		'6. ⭐ the 1.8.91 gate is present in the drawer\'s label assembly',
		$failures
	);
	bhp_ccc_assert(
		false === strpos( (string) $cta_body, 'Ships Free' ),
		'6. ⛔ and the clause itself is STILL not authored here — it comes from bhp_bundle_freeship_copy(), as it has since 1.8.24',
		$failures
	);
}

bhp_ccc_assert(
	false !== strpos( $drawer_js, 'already_ships_free' )
		&& false !== strpos( $drawer_js, 'physicalBooksInCart >= freeShipThreshold' ),
	'6. ⭐ the flag is computed ONCE in the drawer, from the physical-book count against the localized threshold — the same stock of truth 1.8.88 moved the nudge onto',
	$failures
);

bhp_ccc_assert(
	false !== strpos( $checkout_js, 'already_ships_free' ),
	'6. the checkout panel reads the SAME flag off the SAME offer object — one predicate, two readers',
	$failures
);

/*
 * ⛔ THE IDENTITY KEY. Without this term the checkout panel would keep a
 *    stale button across the one transition that changes the flag and
 *    nothing else (2 paperbacks, then a third physical book of any kind).
 *    The fix would be real in the code and invisible on the screen — the
 *    exact trap 1.8.90 named when it added `tier_without_set` to this key.
 */
bhp_ccc_assert(
	(bool) preg_match( '/offerKey\s*=(?:[^;]|\n)*already_ships_free/', $checkout_js ),
	'6. ⛔ and already_ships_free JOINS the checkout panel\'s data-offer identity key, so the label cannot go stale',
	$failures
);

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED\n";
	return;
}
echo count( $failures ) . " CHECK(S) FAILED:\n";
foreach ( $failures as $f ) {
	echo " - {$f}\n";
}
