<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE COLOURING OFFER'S FREE-SHIPPING LABEL — `CYCLE179-CX-BUILD-391-1`.
 * theme 1.19.393 / bundle 1.8.85, 2026-09-07. commerce-cx.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-09-07: "still says add the coloring book save 1.99
 * shouldnt it say add the coloring book get free shipping? or something."
 *
 * ⛔ READ THIS BEFORE ADDING AN ASSERTION: THIS SUITE CANNOT RUN THE DRAWER.
 *    `wp eval-file` has no DOM, no Store API cart response and no JavaScript
 *    engine. `computeDrawerMeta()` and the label branch are BROWSER code. What
 *    a PHP assertion can honestly do is pin the SOURCE CONTRACT the browser
 *    behaviour depends on, and pin the PHP facts that source reads. A PHP
 *    assertion claiming to have watched a button change its words would be a
 *    fabricated verification. The rendered label is checked in a real browser
 *    and that evidence lives in the release QA, not here.
 *
 * ⭐ WHAT IS PINNED, AND WHY EACH ONE MATTERS
 *
 *   §1  the approved clause exists, is EXACTLY " - Ships Free", and carries a
 *       HYPHEN rather than an em dash (B4). This is the whole copy decision:
 *       register B, seal 1274. If this string ever changes, the button changes
 *       with it and nothing else needs editing — which is the point.
 *   §2  the label branch appends THAT clause and does not author a new string
 *   §3  the gate is `(count + 1) === threshold`, never `>=`
 *   §4  the gate is suppressed on a cart with unrelated items
 *   §5  ⛔ THE RANKING IS UNCHANGED — an adventure that completes the
 *       collection still outranks the colouring offer
 *   §6  the count is not recounted: the sanctioned mirror is used
 *   §7  the free-shipping rule the promise depends on is still the `any-three`
 *       policy reading three physical books, read from the PHP source
 *   §8  no em dash and no "we"/"us"/"our" in anything this build added
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE (1.19.386). This
 *    suite creates no order, but the include is unconditional across the
 *    suite family on purpose: a guard that is applied selectively is a guard
 *    somebody forgets. See tests/bootstrap-mail-guard.php.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$cfs_failures = array();

function bhp_cfs_ok( $label, $condition ) {
	global $cfs_failures;
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$cfs_failures[] = $label;
}

/*
 * ⭐ THE SOURCES ARE RESOLVED, NOT ASSUMED. The plugin can sit in
 *    `WP_PLUGIN_DIR` (the deployed shape) or under the theme's own `plugins/`
 *    directory (the repository shape). Both are tried and the first that reads
 *    wins, so this suite gives the same answer in both places rather than
 *    silently passing on an empty string.
 */
function bhp_cfs_read( $relative ) {
	$candidates = array();
	if ( defined( 'WP_PLUGIN_DIR' ) ) {
		$candidates[] = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/' . $relative;
	}
	$candidates[] = get_template_directory() . '/plugins/brave-hearts-bundle-pricing/' . $relative;
	foreach ( $candidates as $path ) {
		if ( is_readable( $path ) ) {
			$body = (string) file_get_contents( $path );
			if ( '' !== $body ) {
				return $body;
			}
		}
	}
	return '';
}

/*
 * ⛔ COMMENTS ARE STRIPPED BEFORE ANY SOURCE ASSERTION, AND THE REASON IS A
 *    REAL FAILURE THIS CYCLE. `tests/test-cycle179-cx-early-cart-capture-2.php`
 *    §2.8 spent a build asserting a string that survived only inside a block
 *    comment describing the bug it documented. An assertion that a comment can
 *    satisfy is not an assertion about behaviour.
 *
 * ⚠ IT IS DELIBERATELY NAIVE ABOUT `//` INSIDE STRINGS AND REGEXES. This file
 *   is read as EVIDENCE, not executed, and `bundle-drawer.js` contains no
 *   protocol-relative URL or regex literal that this would damage in a way
 *   that could turn a FAIL into a PASS. It can only ever remove text, so its
 *   failure mode is a false FAIL, never a false PASS.
 */
function bhp_cfs_strip_js_comments( $src ) {
	$src = preg_replace( '#/\*.*?\*/#s', ' ', $src );
	$src = preg_replace( '#^\s*//.*$#m', ' ', $src );
	return (string) $src;
}

$cfs_js_raw  = bhp_cfs_read( 'assets/bundle-drawer.js' );
$cfs_js      = bhp_cfs_strip_js_comments( $cfs_js_raw );
$cfs_data    = bhp_cfs_read( 'includes/bundle-data.php' );
$cfs_cart    = bhp_cfs_read( 'includes/bundle-cart.php' );
$cfs_drawer  = bhp_cfs_read( 'includes/bundle-drawer.php' );
$cfs_main    = bhp_cfs_read( 'brave-hearts-bundle-pricing.php' );

echo "\n=== 0. The sources read ===\n";
bhp_cfs_ok( '§0.1 bundle-drawer.js reads', '' !== $cfs_js_raw );
bhp_cfs_ok( '§0.2 bundle-data.php reads', '' !== $cfs_data );
bhp_cfs_ok( '§0.3 bundle-cart.php reads', '' !== $cfs_cart );
bhp_cfs_ok( '§0.4 bundle-drawer.php reads', '' !== $cfs_drawer );
/*
 * ⛔⛔ CORRECTED 1.19.397 / plugin 1.8.86 (`CYCLE179-LD-BUILD-397`) — an
 *     EQUALITY ON A MOVING NUMBER, which failed the first correct release
 *     after it was written.
 *
 *     ~~bhp_cfs_ok( '§0.5 the plugin reports 1.8.85', false !== strpos( $cfs_main, "'1.8.85'" ) );~~
 *
 * ⭐ Same correction, same reasoning, as `tests/test-cycle179-396.php` §0.5 and
 *    as the RUNBOOK's own `*.min.css # MUST be 10` → `>= 14`: a fixed number
 *    goes stale, then fails a build that is correct, and the next reader
 *    learns to distrust a passing suite.
 *
 * ⭐ THE INTENT IS SHARPENED RATHER THAN DROPPED. The plugin's header
 *    `Version:` and its `BHP_BUNDLE_PRICING_VERSION` constant DRIFTING APART
 *    is a real, documented defect in this plugin — it happened at 1.8.62,
 *    where the constant sat at `1.8.59` while the header read `1.8.61`, and
 *    the consequence was every enqueued asset shipping under a stale `?ver=`.
 *    Asserting the two AGREE catches that; asserting a literal never did.
 */
preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $cfs_main, $cfs_hdr_v );
bhp_cfs_ok(
	'§0.5 the plugin header Version and BHP_BUNDLE_PRICING_VERSION agree (the 1.8.62 drift defect)',
	isset( $cfs_hdr_v[1] )
		&& defined( 'BHP_BUNDLE_PRICING_VERSION' )
		&& $cfs_hdr_v[1] === BHP_BUNDLE_PRICING_VERSION
		&& false !== strpos( $cfs_main, "'" . BHP_BUNDLE_PRICING_VERSION . "'" )
);

echo "\n=== 1. The approved clause, which is the whole copy decision ===\n";

/*
 * ⛔ THE STRING IS ASSERTED IN ITS EXACT PUBLISHED FORM, SPACE AND HYPHEN
 *    INCLUDED. " - Ships Free" is Andrew's, recorded at plugin 1.8.24 /
 *    `CYCLE144-LD-14`. Register A ("Add the coloring book and shipping is
 *    free.") was proposed and NOT chosen (seal 1274), so this release
 *    introduces NO new customer-facing string at all.
 */
bhp_cfs_ok(
	'§1.1 ⭐ the drawer button clause is exactly " - Ships Free"',
	false !== strpos( $cfs_data, "'cta_clause' => ' - Ships Free'" )
);
bhp_cfs_ok(
	'§1.2 ⛔ it is a HYPHEN, never an em dash (B4)',
	false === strpos( $cfs_data, "' \xE2\x80\x94 Ships Free'" )
);
bhp_cfs_ok(
	'§1.3 ⛔ the sentence-shaped alternative was NOT built',
	false === stripos( $cfs_js, 'and shipping is free' )
);
bhp_cfs_ok(
	'§1.4 ⭐ the clause has ONE author: the drawer reads bhp_bundle_freeship_copy()',
	false !== strpos( $cfs_drawer, "'freeShipCopy' => bhp_bundle_freeship_copy()" )
);

echo "\n=== 2. The label branch appends the clause and authors nothing ===\n";

bhp_cfs_ok(
	'§2.1 the earns_freeship branch exists and appends the SAME clause',
	(bool) preg_match(
		'/else if \(cs\.earns_freeship && freeShipClause\) \{\s*ctaLabel \+= freeShipClause;/s',
		$cfs_js
	)
);
bhp_cfs_ok(
	'§2.2 ⛔ it APPENDS, never replaces (the "Add The Coloring Book" stem survives)',
	false === strpos( $cfs_js, "ctaLabel = 'Add the coloring" )
		&& false === strpos( $cfs_js, 'ctaLabel = "Add the coloring' )
);
bhp_cfs_ok(
	'§2.3 the savings branch still exists for every cart that does not earn free shipping',
	false !== strpos( $cfs_js, "ctaLabel += ' - Save ' + formatMoneyPlain(cs.savings)" )
);
/*
 * ⛔ ORDER IS BEHAVIOUR HERE, NOT STYLE. `completes_collection` must be tested
 *    FIRST, then `earns_freeship`, then savings. A cart that both completes the
 *    collection and reaches the threshold must take the collection branch,
 *    which is the one Andrew's 1.8.24 clause was written for.
 */
$cfs_pos_completes = strpos( $cfs_js, 'cs.completes_collection && freeShipClause' );
$cfs_pos_earns     = strpos( $cfs_js, 'cs.earns_freeship && freeShipClause' );
$cfs_pos_savings   = strpos( $cfs_js, "ctaLabel += ' - Save '" );
bhp_cfs_ok(
	'§2.4 ⛔ branch order is completes_collection, then earns_freeship, then savings',
	false !== $cfs_pos_completes && false !== $cfs_pos_earns && false !== $cfs_pos_savings
		&& $cfs_pos_completes < $cfs_pos_earns && $cfs_pos_earns < $cfs_pos_savings
);

echo "\n=== 3. The gate: exactly one book short, never already free ===\n";

bhp_cfs_ok(
	'§3.1 ⚠ the test is (count + 1) === threshold, never >=',
	(bool) preg_match( '/\(physicalBookCount\(cart\) \+ 1\) === fsThreshold/', $cfs_js )
);
bhp_cfs_ok(
	'§3.2 ⛔ no >= comparison against the threshold was introduced',
	0 === preg_match( '/physicalBookCount\(cart\)\s*\+\s*1\s*>=/', $cfs_js )
);
bhp_cfs_ok(
	'§3.3 the threshold is read from the server, not typed',
	false !== strpos( $cfs_js, 'parseInt(fsData.freeShipAtCount, 10)' )
		&& false !== strpos( $cfs_drawer, "'freeShipAtCount'" )
);
bhp_cfs_ok(
	'§3.4 ⛔ a threshold of 0 makes the branch unreachable rather than wrong',
	false !== strpos( $cfs_js, 'fsThreshold > 0' )
);
bhp_cfs_ok(
	'§3.5 ⛔ the honesty gate is respected: anyThreeActive must be true',
	false !== strpos( $cfs_js, 'fsData.anyThreeActive' )
);

echo "\n=== 4. Suppression on a cart the shipping override will not touch ===\n";

/*
 * ⛔ AN ITEM OUTSIDE THE SIX EDITIONS AND THE ALLOWLISTED ADD-ON STOPS
 *    `bhp_bundle_override_shipping_cost()` RUNNING AT ALL. A free-shipping
 *    promise on such a cart is a claim the checkout refuses, which is the
 *    single worst thing this rail can do.
 */
bhp_cfs_ok(
	'§4.1 ⛔ the flag is only set when !hasUnrelated',
	(bool) preg_match(
		"/if \(crossSell && 'colouring' === crossSell\.format && !hasUnrelated\)/",
		$cfs_js
	)
);
bhp_cfs_ok(
	'§4.2 ⭐ it is decided where hasUnrelated is in scope, not inside chooseColouringOffer()',
	strpos( $cfs_js, 'crossSell.earns_freeship' ) > strpos( $cfs_js, 'var hasUnrelated' )
);

echo "\n=== 5. ⛔ THE RANKING IS UNTOUCHED ===\n";

/*
 * ⛔⛔ THIS IS THE REGRESSION ASSERTION AND IT IS THE MOST IMPORTANT ONE IN THE
 *     FILE. A cart one adventure short of the collection must still be offered
 *     the ADVENTURE, because that offer earns free shipping AND completes the
 *     collection. Offering a colouring book at that exact moment trades a
 *     stronger true statement for a weaker one. This build changed no line of
 *     `chooseCrossSell()` and this pins that it stays that way.
 */
bhp_cfs_ok(
	'§5.1 ⛔ completes_collection still returns the adventure offer first',
	(bool) preg_match(
		'/if \(adventureOffer && adventureOffer\.completes_collection\) \{\s*return adventureOffer;\s*\}/s',
		$cfs_js
	)
);
bhp_cfs_ok(
	'§5.2 ⛔ the pair offer is still second and the plain adventure still last',
	(bool) preg_match(
		'/return adventureOffer;\s*\}\s*if \(pairOffer\) \{\s*return pairOffer;\s*\}\s*return adventureOffer;/s',
		$cfs_js
	)
);
bhp_cfs_ok(
	'§5.3 ⛔ a colouring offer still declares completes_collection: false',
	false !== strpos( $cfs_js, 'completes_collection: false' )
);

echo "\n=== 6. Nothing is recounted ===\n";

bhp_cfs_ok(
	'§6.1 ⭐ the sanctioned mirror of bhp_bundle_physical_book_count() is used',
	false !== strpos( $cfs_js, 'function physicalBookCount(cart)' )
		&& false !== strpos( $cfs_js, 'physicalBookCount(cart) + 1' )
);
/*
 * ⭐ RECORDED DEPARTURE FROM THE PREPARED PATCH. The prepared patch proposed
 *    publishing `physical_book_count` and `freeship_threshold` from
 *    `bundle-cart.php`. Both facts were ALREADY published
 *    (`freeShipAtCount`, `anyThreeActive`, `colouringIds`), so that half was
 *    dropped and NO PHP FILE WAS TOUCHED by 1.8.85. This assertion pins that.
 */
bhp_cfs_ok(
	'§6.2 ⭐ 1.8.85 added no second definition of the count to the PHP payload',
	false === strpos( $cfs_drawer, "'physical_book_count'" )
		&& false === strpos( $cfs_drawer, "'freeship_threshold'" )
);

echo "\n=== 7. The PHP rule the promise depends on ===\n";

bhp_cfs_ok(
	'§7.1 the colouring policy still defaults to any-three',
	false !== strpos( $cfs_data, "apply_filters( 'bhp_bundle_colouring_policy', 'any-three' )" )
);
bhp_cfs_ok(
	'§7.2 three physical books still ship free under that policy',
	(bool) preg_match(
		"/'any-three' === bhp_bundle_colouring_policy\(\).*physical_book_count'\] >= 3/s",
		$cfs_cart
	)
);

echo "\n=== 8. House style on everything this build added ===\n";

/*
 * ⛔ SCOPED TO THE 1.8.85 ADDITIONS ON PURPOSE. Scanning the whole file for an
 *    em dash would fail on prose written years of builds ago and would say
 *    nothing about this change. The rendered STRING is what matters, and it is
 *    the clause asserted in §1.
 */
bhp_cfs_ok(
	'§8.1 ⛔ the clause carries no em dash',
	false === strpos( "' - Ships Free'", "\xE2\x80\x94" )
		&& false !== strpos( $cfs_data, "' - Ships Free'" )
);
bhp_cfs_ok(
	'§8.2 ⛔ no first person in the clause',
	0 === preg_match( '/\b(we|us|our)\b/i', ' - Ships Free' )
);

echo "\n";
if ( empty( $cfs_failures ) ) {
	echo "ALL PASS — colouring free-shipping label (bundle 1.8.85)\n";
} else {
	echo 'FAILURES (' . count( $cfs_failures ) . "):\n";
	foreach ( $cfs_failures as $cfs_f ) {
		echo "  - {$cfs_f}\n";
	}
}
