<?php
/**
 * Brave Hearts Theme — THE COLOURING LINE TIER MACHINE.
 * Plugin 1.8.61 / theme 1.19.276. `ACT-OPS-269` · `CYCLE165-OPS-018` /
 * `CYCLE165-OPS-019`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-colouring-line-tiers.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT THIS SUITE IS FOR, AND WHY IT EXISTS BEFORE THE PRODUCT DOES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Two defects were PRE-EMPTED rather than found live, and both are created by
 * the mere EXISTENCE of a colouring product record -- no code change required:
 *
 *   `CYCLE165-OPS-018`  a colouring book in a collection cart killed the
 *                       free-shipping override while LEAVING the -$3.98
 *                       discount and the rendered "FREE shipping" promise in
 *                       place. A false advertised claim.
 *   `CYCLE165-OPS-019`  title-substring matching absorbed the colouring book
 *                       into the Mariana CHAPTER adventure, so a colouring
 *                       cover could render beside a chapter-book price.
 *
 * ⛔ `ACT-OPS-269` requires the fix to ship BEFORE the first product record on
 *    ANY environment, staging included. This suite is the evidence that it did.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, STATED PLAINLY
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It does not load a page, build a real WooCommerce cart, or contact the Store
 * API. `bhp_bundle_shipping_amount()` is a PURE function of the evaluation
 * array, which is exactly why it can be exhausted here -- but a real Blocks
 * cart resolving a real shipping rate is a DIFFERENT claim and this file does
 * not make it. §7 uses a stub cart to reach `bhp_bundle_evaluate_cart()`; that
 * is closer, and still not a checkout.
 *
 * ⭐ NO PRODUCT, PRICE, OPTION, TIER, ZONE, SETTING, CART OR ORDER IS WRITTEN
 *    BY THIS FILE ON ANY ENVIRONMENT. It reads pure functions and installs
 *    request-scoped filters that die with the process.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$passes   = 0;

function bhp_clt_assert( $condition, $label, array &$failures, &$passes ) {
	if ( $condition ) {
		$passes++;
		echo "  PASS  {$label}\n";
		return true;
	}
	$failures[] = $label;
	echo "  FAIL  {$label}\n";
	return false;
}

function bhp_clt_assert_money( $actual, $expected, $label, array &$failures, &$passes ) {
	$ok = ( null !== $actual ) && ( abs( (float) $actual - (float) $expected ) < 0.005 );
	$shown = ( null === $actual ) ? 'NULL' : '$' . number_format( (float) $actual, 2 );
	return bhp_clt_assert( $ok, sprintf( '%s  (expected $%.2f, got %s)', $label, $expected, $shown ), $failures, $passes );
}

/**
 * The evaluation array a given cart shape produces, built by hand.
 *
 * ⛔ HAND-BUILT ON PURPOSE. `bhp_bundle_shipping_amount()` is a pure function
 *    of this array, so driving it directly exhausts the tier table without a
 *    cart, a session or a product. §7 below separately proves that
 *    `bhp_bundle_evaluate_cart()` actually PRODUCES these shapes from a cart,
 *    which is the join that stops this file from testing a fiction.
 */
function bhp_clt_eval( array $overrides = array() ) {
	return array_merge(
		array(
			'paperback_tier'       => 0,
			'hardcover_tier'       => 0,
			'has_paperback'        => false,
			'has_hardcover'        => false,
			'has_unrelated'        => false,
			'total_quantity'       => 0,
			'distinct_adventures'  => 0,
			'is_complete_collection' => false,
			'has_any_book'         => false,
			'is_mixed_format'      => false,
			'colouring_quantity'   => 0,
			'distinct_colouring'   => 0,
			'has_colouring'        => false,
			'physical_book_count'  => 0,
		),
		$overrides
	);
}

/** Force the policy switch for one block of assertions. */
function bhp_clt_set_policy( $policy ) {
	remove_all_filters( 'bhp_bundle_colouring_policy' );
	add_filter(
		'bhp_bundle_colouring_policy',
		function () use ( $policy ) {
			return $policy;
		}
	);
}

echo "\n=== COLOURING LINE TIER MACHINE — plugin 1.8.61 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · THE FUNCTIONS EXIST AT ALL
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§0] Wiring\n";
foreach ( array(
	'bhp_colouring_catalog',
	'bhp_colouring_product_ids',
	'bhp_is_colouring_product',
	'bhp_bundle_identify_colouring_item',
	'bhp_bundle_colouring_quantity_in_cart',
	'bhp_bundle_distinct_colouring_in_cart',
	'bhp_bundle_physical_book_count',
	'bhp_bundle_colouring_policy',
	'bhp_bundle_shipping_amount',
) as $fn ) {
	bhp_clt_assert( function_exists( $fn ), "{$fn}() is defined", $failures, $passes );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · ⭐ THE DEFAULT IS THE STRICTER READING
 *
 * ⛔ The colouring-in-free-shipping policy is an OPEN founder decision
 *    (`00A-WHAT-GOVERNS-TODAY.md`, read 2026-08-20). If this assertion ever
 *    fails, someone has enabled a customer-facing shipping change that no
 *    canonical document authorises. That is the whole point of the assertion.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§1] The policy default is conservative (OPEN founder decision)\n";
remove_all_filters( 'bhp_bundle_colouring_policy' );
bhp_clt_assert(
	'conservative' === bhp_bundle_colouring_policy(),
	'default policy is `conservative` — the any-three rule is NOT live',
	$failures,
	$passes
);
add_filter( 'bhp_bundle_colouring_policy', function () { return 'nonsense-value'; } );
bhp_clt_assert(
	'conservative' === bhp_bundle_colouring_policy(),
	'an unrecognised policy value falls back to `conservative`, never to any-three',
	$failures,
	$passes
);
remove_all_filters( 'bhp_bundle_colouring_policy' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · ⭐⭐ IT FAILS CLOSED TODAY — the registry resolves to NOTHING
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§2] Fails closed on the current store\n";
$live_ids = bhp_colouring_product_ids();
bhp_clt_assert(
	is_array( $live_ids ) && empty( $live_ids ),
	'no colouring SKU resolves on this environment — the line is INERT (' . count( (array) $live_ids ) . ' resolved)',
	$failures,
	$passes
);
bhp_clt_assert(
	! bhp_is_colouring_product( 333 ) && ! bhp_is_colouring_product( 15 ) && ! bhp_is_colouring_product( 18 ),
	'no chapter-book product is ever mistaken for a colouring product',
	$failures,
	$passes
);
$catalog = bhp_colouring_catalog();
bhp_clt_assert(
	1 === count( $catalog ) && isset( $catalog['mariana'] ),
	'the catalogue holds exactly ONE title — Everest and Amazon are NOT invented',
	$failures,
	$passes
);
bhp_clt_assert(
	'Coloring Adventures with Charlotte and Henry: The Mariana Trench Ocean Coloring Book' === $catalog['mariana']['label'],
	'the MT label is FD-557 verbatim — not shortened, re-cased or subtitle-dropped',
	$failures,
	$passes
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ⭐ THE PRE-EXISTING TIERS DO NOT MOVE. Every row here predates 1.8.61
 *      and must return exactly what it returned before.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§3] Baseline — no colouring book present, nothing may move\n";
bhp_clt_set_policy( 'conservative' );

bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'total_quantity' => 1, 'physical_book_count' => 1, 'distinct_adventures' => 1, 'has_any_book' => true ) ) ),
	1.99, '1 chapter paperback', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'paperback_tier' => 2, 'total_quantity' => 2, 'physical_book_count' => 2, 'distinct_adventures' => 2 ) ) ),
	2.99, '2 distinct chapter paperbacks', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'paperback_tier' => 3, 'total_quantity' => 3, 'physical_book_count' => 3, 'distinct_adventures' => 3, 'is_complete_collection' => true ) ) ),
	0.00, '3 distinct chapter paperbacks — the complete collection', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_hardcover' => true, 'total_quantity' => 1, 'physical_book_count' => 1, 'distinct_adventures' => 1 ) ) ),
	2.99, '1 chapter hardcover', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_hardcover' => true, 'hardcover_tier' => 2, 'total_quantity' => 2, 'physical_book_count' => 2, 'distinct_adventures' => 2 ) ) ),
	3.99, '2 distinct chapter hardcovers', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'has_hardcover' => true, 'is_mixed_format' => true, 'total_quantity' => 2, 'physical_book_count' => 2, 'distinct_adventures' => 2 ) ) ),
	3.99, 'mixed formats, 2 books', $failures, $passes
);
// ⭐ The row the any-three policy would kill. Under `conservative` it LIVES.
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'has_hardcover' => true, 'is_mixed_format' => true, 'total_quantity' => 3, 'physical_book_count' => 3, 'distinct_adventures' => 2 ) ) ),
	4.99, 'mixed formats, 3 books but only 2 adventures — the $4.99 row, ALIVE under conservative', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'has_hardcover' => true, 'is_mixed_format' => true, 'total_quantity' => 3, 'physical_book_count' => 3, 'distinct_adventures' => 3, 'is_complete_collection' => true ) ) ),
	0.00, '3 distinct adventures across mixed formats', $failures, $passes
);
bhp_clt_assert(
	null === bhp_bundle_shipping_amount( bhp_clt_eval() ),
	'an empty cart returns NULL, so the override leaves the zone rate alone',
	$failures,
	$passes
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ⭐⭐ THE ACCEPTANCE TEST — spec cart-matrix row 18.
 *
 *     3 chapter paperbacks + 1 colouring book. The promise on the collection
 *     page says FREE. The cart must agree. ⛔ This is the row the whole build
 *     exists for.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§4] ⭐ ROW 18 — the acceptance test\n";
$row18 = bhp_clt_eval( array(
	'has_paperback'          => true,
	'paperback_tier'         => 3,
	'total_quantity'         => 3,
	'colouring_quantity'     => 1,
	'distinct_colouring'     => 1,
	'has_colouring'          => true,
	'physical_book_count'    => 4,
	'distinct_adventures'    => 3,
	'is_complete_collection' => true,
	'has_any_book'           => true,
	'has_unrelated'          => false, // ⭐ 1.8.61: a colouring book is RELATED.
) );
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( $row18 ),
	0.00,
	'⭐ 3 chapter PB + 1 colouring book still ships FREE — the promise stays true',
	$failures,
	$passes
);
bhp_clt_assert(
	false === $row18['has_unrelated'],
	'a colouring book does NOT set has_unrelated — so the discount survives too',
	$failures,
	$passes
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · THE COLOURING LADDER — every figure read from an existing table.
 *     ⚠️ Three readings are FLAGGED in the report, not settled here.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§5] The colouring ladder (conservative policy)\n";
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_colouring' => true, 'colouring_quantity' => 1, 'distinct_colouring' => 1, 'physical_book_count' => 1 ) ) ),
	1.99, '⚠️ FLAGGED READING 1 — 1 colouring book alone = $1.99, like a single paperback', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'has_colouring' => true, 'total_quantity' => 1, 'colouring_quantity' => 1, 'distinct_colouring' => 1, 'physical_book_count' => 2, 'distinct_adventures' => 1 ) ) ),
	2.99, '⚠️ FLAGGED READING 2 — 1 chapter PB + 1 colouring (the bundle) = $2.99, not the mixed $3.99', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_colouring' => true, 'colouring_quantity' => 2, 'distinct_colouring' => 1, 'physical_book_count' => 2 ) ) ),
	2.99, '⚠️ FLAGGED READING 3 — 2 copies of ONE colouring book = $2.99 by COUNT, not $1.99', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_hardcover' => true, 'has_colouring' => true, 'total_quantity' => 1, 'colouring_quantity' => 1, 'distinct_colouring' => 1, 'physical_book_count' => 2, 'distinct_adventures' => 1 ) ) ),
	3.99, 'a HARDCOVER in the cart still wins — colouring does not cheapen a hardcover shipment', $failures, $passes
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · ⭐⭐ THE `any-three` POLICY — built, tested, NOT DEFAULT.
 *     ⛔ Including the DUPLICATES case the brief names explicitly.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§6] ⭐ The any-three policy (built and tested, NOT enabled)\n";
bhp_clt_set_policy( 'any-three' );

bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_colouring' => true, 'colouring_quantity' => 3, 'distinct_colouring' => 1, 'physical_book_count' => 3 ) ) ),
	0.00, '⭐⭐ THE DUPLICATES CASE — 3 copies of ONE colouring title = $0.00', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'total_quantity' => 3, 'physical_book_count' => 3, 'distinct_adventures' => 1 ) ) ),
	0.00, '⭐⭐ 3 copies of ONE chapter paperback = $0.00 — "any 3 books", duplicates included', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'has_hardcover' => true, 'is_mixed_format' => true, 'total_quantity' => 3, 'physical_book_count' => 3, 'distinct_adventures' => 2 ) ) ),
	0.00, '⭐ THE $4.99 ROW IS DEAD — mixed, 3 books, 2 adventures now ships free', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'has_colouring' => true, 'total_quantity' => 2, 'colouring_quantity' => 1, 'distinct_colouring' => 1, 'physical_book_count' => 3, 'distinct_adventures' => 2 ) ) ),
	0.00, '2 chapter PB + 1 colouring = 3 physical books = $0.00', $failures, $passes
);
// ⛔ Under two books, any-three must change NOTHING.
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'total_quantity' => 1, 'physical_book_count' => 1, 'distinct_adventures' => 1 ) ) ),
	1.99, 'under 3 books the any-three policy changes nothing — 1 paperback still $1.99', $failures, $passes
);
bhp_clt_assert_money(
	bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'paperback_tier' => 2, 'total_quantity' => 2, 'physical_book_count' => 2, 'distinct_adventures' => 2 ) ) ),
	2.99, 'under 3 books the any-three policy changes nothing — 2 paperbacks still $2.99', $failures, $passes
);
bhp_clt_set_policy( 'conservative' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · THE JOIN — `bhp_bundle_evaluate_cart()` really produces these shapes
 *      from a cart, using an injected colouring product ID.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§7] evaluate_cart() produces the shapes §3–§6 assume\n";

if ( ! class_exists( 'BHP_CLT_Stub_Cart' ) ) {
	class BHP_CLT_Stub_Cart {
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

function bhp_clt_line( $product_id, $variation_id = 0, $qty = 1 ) {
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $qty,
	);
}

// ⭐ Inject a colouring product ID. 999901 is not a real post on any
//    environment, which is deliberate: the test must not depend on, or touch,
//    a real product record.
$injected = 999901;
remove_all_filters( 'bhp_colouring_product_ids' );
add_filter( 'bhp_colouring_product_ids', function () use ( $injected ) {
	return array( 'mariana' => $injected );
} );

bhp_clt_assert(
	bhp_is_colouring_product( $injected ),
	'the injected colouring ID is recognised by bhp_is_colouring_product()',
	$failures,
	$passes
);

// 3 chapter paperbacks (334 is the Mariana PB variation) + 1 colouring book.
$cart_row18 = new BHP_CLT_Stub_Cart( array(
	bhp_clt_line( 333, 334, 1 ),
	bhp_clt_line( 15, 0, 1 ),
	bhp_clt_line( 18, 0, 1 ),
	bhp_clt_line( $injected, 0, 1 ),
) );
$eval18 = bhp_bundle_evaluate_cart( $cart_row18 );

bhp_clt_assert( false === $eval18['has_unrelated'], 'ROW 18 from a real cart shape: has_unrelated is FALSE', $failures, $passes );
bhp_clt_assert( true === $eval18['has_colouring'], 'ROW 18: has_colouring is TRUE', $failures, $passes );
bhp_clt_assert( 4 === (int) $eval18['physical_book_count'], 'ROW 18: physical_book_count is 4 (3 chapter + 1 colouring), got ' . $eval18['physical_book_count'], $failures, $passes );
bhp_clt_assert( 3 === (int) $eval18['total_quantity'], 'ROW 18: total_quantity still counts only the 3 catalogue editions', $failures, $passes );
bhp_clt_assert( true === $eval18['is_complete_collection'], 'ROW 18: still a complete collection', $failures, $passes );
bhp_clt_assert_money( bhp_bundle_shipping_amount( $eval18 ), 0.00, '⭐ ROW 18 end to end from a cart: $0.00', $failures, $passes );

// ⭐ Duplicates counted as books, from a real cart shape.
$cart_dupes = new BHP_CLT_Stub_Cart( array( bhp_clt_line( $injected, 0, 3 ) ) );
$eval_dupes = bhp_bundle_evaluate_cart( $cart_dupes );
bhp_clt_assert( 3 === (int) $eval_dupes['physical_book_count'], 'a quantity of 3 on ONE colouring line counts as 3 physical books', $failures, $passes );
bhp_clt_assert( 1 === (int) $eval_dupes['distinct_colouring'], 'the same cart holds only ONE distinct colouring title', $failures, $passes );
bhp_clt_set_policy( 'any-three' );
bhp_clt_assert_money( bhp_bundle_shipping_amount( $eval_dupes ), 0.00, '⭐⭐ 3x one colouring title from a cart = $0.00 under any-three', $failures, $passes );
bhp_clt_set_policy( 'conservative' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · ⛔⛔ UNRELATED STILL FAILS SAFE — IN BOTH DIRECTIONS.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§8] A genuinely unrelated product fails SAFE\n";
$cart_unrelated = new BHP_CLT_Stub_Cart( array(
	bhp_clt_line( 333, 334, 1 ),
	bhp_clt_line( 15, 0, 1 ),
	bhp_clt_line( 18, 0, 1 ),
	bhp_clt_line( 999902, 0, 1 ), // not a book, not an add-on, not colouring
) );
$eval_unrelated = bhp_bundle_evaluate_cart( $cart_unrelated );
bhp_clt_assert(
	true === $eval_unrelated['has_unrelated'],
	'an unrecognised product still sets has_unrelated — the fail-safe is intact',
	$failures,
	$passes
);
/*
 * ⭐ The guard added to `bhp_bundle_apply_discount_fees()` is asserted at
 *    SOURCE, because the behavioural route is not reliably reachable here:
 *    that function returns early unless `WC()->cart` is a live cart, and under
 *    WP-CLI there is usually no cart session. ⛔ A behavioural test that
 *    "passes" because the function returned at its FIRST line would be worse
 *    than no test at all -- it would report the guard working when the guard
 *    was never reached. So the source assertion is the honest instrument, and
 *    the behavioural one is attempted only when a real cart exists.
 *
 * ⚠ THE PLUGIN IS NOT INSIDE THE THEME. On disk here it sits in
 *   `wp-content/plugins/`, while in the repository it sits in the theme's own
 *   `plugins/` folder. Both layouts are resolved below -- the first version of
 *   this assertion used only the repo-relative path and FAILED on the server
 *   for that reason, which is exactly the kind of false red a path assumption
 *   produces.
 */
$cart_src_candidates = array();
if ( defined( 'WP_PLUGIN_DIR' ) ) {
	$cart_src_candidates[] = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/bundle-cart.php';
}
$cart_src_candidates[] = __DIR__ . '/../plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php';

$cart_src  = '';
$cart_path = '';
foreach ( $cart_src_candidates as $candidate ) {
	if ( is_readable( $candidate ) ) {
		$cart_src  = (string) file_get_contents( $candidate );
		$cart_path = $candidate;
		break;
	}
}
bhp_clt_assert(
	'' !== $cart_src,
	'bundle-cart.php is readable for source assertion (' . ( $cart_path ? basename( dirname( dirname( $cart_path ) ) ) : 'NOT FOUND' ) . ')',
	$failures,
	$passes
);

$fee_fn   = $cart_src ? strstr( $cart_src, 'function bhp_bundle_apply_discount_fees' ) : '';
$next_fn  = $fee_fn ? strpos( $fee_fn, 'function bhp_bundle_prices_match_expected' ) : false;
$fee_body = ( $fee_fn && false !== $next_fn ) ? substr( $fee_fn, 0, $next_fn ) : '';
$guard_at = $fee_body ? strpos( $fee_body, "\$eval['has_unrelated']" ) : false;
$fee_at   = $fee_body ? strpos( $fee_body, 'add_fee' ) : false;
bhp_clt_assert(
	false !== $guard_at && false !== $fee_at && $guard_at < $fee_at,
	'⭐ bhp_bundle_apply_discount_fees() checks has_unrelated BEFORE it can add_fee()',
	$failures,
	$passes
);

/*
 * ⭐ THE BEHAVIOURAL ROUTE, attempted honestly. If there is no live cart this
 *    reports SKIPPED rather than PASSED. ⛔ "Not checked" is an acceptable
 *    result; a fabricated check is not.
 */
if ( function_exists( 'WC' ) && WC() && ! empty( WC()->cart ) && is_object( WC()->cart ) ) {
	$fee_probe = new class( array(
		array( 'product_id' => 333, 'variation_id' => 334, 'quantity' => 1 ),
		array( 'product_id' => 15,  'variation_id' => 0,   'quantity' => 1 ),
		array( 'product_id' => 18,  'variation_id' => 0,   'quantity' => 1 ),
		array( 'product_id' => 999902, 'variation_id' => 0, 'quantity' => 1 ),
	) ) {
		public $fees = array();
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
		public function add_fee( $label, $amount, $taxable = false ) {
			$this->fees[] = array( $label, $amount );
		}
	};
	bhp_bundle_apply_discount_fees( $fee_probe );
	bhp_clt_assert(
		empty( $fee_probe->fees ),
		'⭐ BEHAVIOURAL — no Bundle Savings fee is added to a cart holding an unrelated product (' . count( $fee_probe->fees ) . ' fees added)',
		$failures,
		$passes
	);
} else {
	echo "  SKIP  behavioural add_fee() probe — no live WC()->cart under WP-CLI. NOT CHECKED, not assumed.\n";
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · ⛔ THE RAIL VETO — CYCLE165-OPS-019
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§9] The colouring title is not absorbed by a chapter adventure\n";
$adv_src = file_get_contents( __DIR__ . '/../functions.php' );
bhp_clt_assert(
	false !== strpos( $adv_src, "bhp_is_colouring_product(\$product['product_id']" ),
	'bhp_get_series_adventures() excludes the colouring line by PRODUCT ID, not substring',
	$failures,
	$passes
);
$rail_src = file_get_contents( __DIR__ . '/../inc/blog-post-template.php' );
$rail_fn  = strstr( $rail_src, 'function bhp_blog_rail_adventure' );
bhp_clt_assert(
	$rail_fn && false !== strpos( $rail_fn, 'coloring book' ) && false !== strpos( $rail_fn, 'colouring book' ),
	'bhp_blog_rail_adventure() vetoes both spellings before the substring fallback',
	$failures,
	$passes
);
if ( function_exists( 'bhp_get_series_adventures' ) ) {
	$adventures = bhp_get_series_adventures();
	bhp_clt_assert(
		isset( $adventures['mariana_trench'] ) && 3 === count( $adventures ),
		'the three chapter adventures still resolve — the guard broke nothing',
		$failures,
		$passes
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §10 · ⭐⭐ THE NEGATIVE CONTROL — "a check that has never failed is not
 *      known to work" (spec §11.2 step 9, the method that proved the item-126
 *      fix). Every assertion above is worthless if the harness cannot fail.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n[§10] ⭐ Negative control — proving the assertions can actually fail\n";
$control_failures = array();
$control_passes   = 0;
bhp_clt_assert_money( bhp_bundle_shipping_amount( bhp_clt_eval( array( 'has_paperback' => true, 'total_quantity' => 1, 'physical_book_count' => 1 ) ) ), 99.99, 'DELIBERATE MISMATCH — this row is SUPPOSED to fail', $control_failures, $control_passes );
bhp_clt_assert( false, 'DELIBERATE FALSE — this row is SUPPOSED to fail', $control_failures, $control_passes );
bhp_clt_assert(
	2 === count( $control_failures ) && 0 === $control_passes,
	'⭐ both deliberate failures were CAUGHT — the harness genuinely fails when it should',
	$failures,
	$passes
);
echo "  (the two FAIL lines immediately above are the negative control and are EXPECTED)\n";

remove_all_filters( 'bhp_colouring_product_ids' );
remove_all_filters( 'bhp_bundle_colouring_policy' );

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== RESULT ===\n";
printf( "  %d passed, %d failed\n", $passes, count( $failures ) );
if ( ! empty( $failures ) ) {
	echo "\nFAILURES:\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "  ALL PASS\n";
exit( 0 );
