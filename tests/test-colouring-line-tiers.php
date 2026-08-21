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
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.8.62 — §1 IS INVERTED, AND IT IS INVERTED BY THE FOUNDER.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THE COMMENT BLOCK ABOVE IS PRESERVED VERBATIM AND IS NOW HISTORICAL. It
 *    says: "If this assertion ever fails, someone has enabled a customer-facing
 *    shipping change that no canonical document authorises." ⭐ THAT WAS THE
 *    RIGHT GUARD TO WRITE, IT DID ITS JOB, AND THE CONDITION IT GUARDED
 *    AGAINST IS NOW GONE — a canonical document authorises it.
 *
 * ⭐ `FD-583`, `FOUNDER-DECISIONS-2026-08-01.md` PART 66 §66.8 — read AT SOURCE
 *    by the agent that changed this line. Andrew Signore, 2026-08-20
 *    ~17:4x−0600, carrier item 159: *"any 3 books - I think the margins will
 *    hold the same especially since we increased the coloring book price to
 *    12.99"*.
 *
 * ⛔ THE ASSERTION IS NOT DELETED AND THE OLD BEHAVIOUR IS NOT UNTESTED. It is
 *    INVERTED here, and `conservative` remains reachable through the filter and
 *    is still exercised by §3, §5 and §7 below — which is exactly why 1.8.61
 *    built both behaviours instead of one.
 */
echo "\n[§1] The policy default is any-three (FD-583, founder-ruled)\n";
remove_all_filters( 'bhp_bundle_colouring_policy' );
bhp_clt_assert(
	'any-three' === bhp_bundle_colouring_policy(),
	'default policy is `any-three` — FD-583 is live',
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
 * §2 · ⭐⭐ THE REGISTRY RESOLVES ONLY WHAT ACTUALLY EXISTS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THIS SECTION'S ORIGINAL ASSERTION WAS `empty( $live_ids )` — "the line is
 *    INERT". ⭐ THAT WAS TRUE OF BOTH ENVIRONMENTS WHEN 1.8.61 SHIPPED, AND IT
 *    WAS THE RIGHT THING TO ASSERT: the fix had to land BEFORE the first
 *    product record (`ACT-OPS-269`), and the assertion proved it had.
 *
 * ⭐ THE PRODUCT NOW EXISTS ON STAGING (`BHP-COLOR-MT-01`, created
 *    2026-08-21 under `FD-570` / `FD-580`), so "inert" is no longer the
 *    correct claim about every environment — and a test that hardcodes one
 *    environment's state fails on the other for no defect.
 *
 * ⛔ WHAT REPLACES IT IS STRICTLY STRONGER, NOT WEAKER. The old assertion said
 *    "nothing resolves". This says "ONLY a real, catalogued SKU resolves, and
 *    every resolved id is a live product" — which is the property that
 *    actually protects the cart on BOTH environments, before and after the
 *    record exists. It would still catch an invented Everest entry, a
 *    hardcoded id, or a SKU resolving to a deleted post.
 */
echo "\n[§2] The registry resolves only what actually exists\n";
$live_ids = bhp_colouring_product_ids();
$catalog_slugs = array_keys( bhp_colouring_catalog() );
$ids_are_sane  = is_array( $live_ids );
foreach ( (array) $live_ids as $slug => $id ) {
	// ⛔ Never a slug outside the catalogue, never a non-product id.
	if ( ! in_array( $slug, $catalog_slugs, true ) || (int) $id < 1 || ! wc_get_product( (int) $id ) ) {
		$ids_are_sane = false;
	}
}
bhp_clt_assert(
	$ids_are_sane,
	'every resolved colouring SKU maps to a catalogued slug AND a live product (' . count( (array) $live_ids ) . ' resolved)',
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
 * §9b · ⭐⭐ FULFILMENT ROUTING — A COLOURING ORDER LINE RESOLVES TO AN ISBN THE
 *       SAME WAY A CHAPTER BOOK'S DOES. `CYCLE165-LD-COLOURING-ISBN-WIRING`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ THE DEFECT THIS SECTION EXISTS TO CATCH IS SILENT, AND THAT IS THE WHOLE
 *     POINT. The installed third-party plugin builds its order lines like this
 *     (`wp-content/plugins/bookvault/Bookvault.php:136-139`, read on staging
 *     this build, NOT from documentation):
 *
 *         $sku = $product->get_sku();
 *         if (strlen($sku) == 13) {
 *             $transaction_lines[] = [ "ISBN" => $sku, "Quantity" => ... ];
 *         }
 *
 *     ⭐ There is no `else`. A product whose SKU is not exactly 13 characters
 *        contributes NO LINE — no warning, no error, no failed request. The
 *        order simply arrives at the printer missing a book, or empty.
 *
 * ⭐ SO THE ASSERTIONS BELOW TEST THE PRODUCT RECORD, NOT THE CATALOGUE. Code
 *    stating an intention proves nothing about what goes on the wire.
 *
 * ⚠ WHAT THIS SECTION CANNOT PROVE, STATED PLAINLY RATHER THAN GLOSSED: it does
 *   NOT place an order, does not fire a webhook, and does not contact
 *   Bookvault. Bookvault's RECEIVER is server-side and unreadable from here, so
 *   "the payload carries the right ISBN in the fields the six live books use"
 *   is the strongest honest claim available, and it is the claim made. Whether
 *   the receiver keys on `sku` or on `global_unique_id` is NOT asserted — which
 *   is exactly why both are required to be present and to agree.
 */
echo "\n[§9b] ⭐ Fulfilment routing — the colouring line resolves to an ISBN\n";

$clt_expected_isbn = '9798996810840';

bhp_clt_assert(
	$clt_expected_isbn === bhp_colouring_isbn( 'mariana' ),
	'the catalogue routes the MT colouring book to ISBN ' . $clt_expected_isbn,
	$failures,
	$passes
);
bhp_clt_assert(
	'' === bhp_colouring_isbn( 'everest' ) && '' === bhp_colouring_isbn( 'amazon' ),
	'⛔ no ISBN is invented for a title that does not exist',
	$failures,
	$passes
);
// ⛔ The shape gate must be the SAME gate fulfilment applies — 13 digits.
bhp_clt_assert(
	13 === strlen( $clt_expected_isbn ) && ctype_digit( $clt_expected_isbn ),
	'the ISBN is 13 digits — the exact shape Bookvault.php:137 accepts',
	$failures,
	$passes
);

/*
 * ⭐⭐ THE COMPARATIVE ASSERTION THE BRIEF ASKS FOR, AND IT IS COMPARATIVE ON
 *     PURPOSE: the colouring product is required to satisfy the SAME predicate
 *     the chapter books satisfy, rather than a predicate written for it.
 *
 * ⛔ IT IS ENVIRONMENT-AWARE INSTEAD OF ENVIRONMENT-BLIND, AND THAT IS A REAL
 *    FINDING RATHER THAN A CONVENIENCE. Read this build:
 *      · PRODUCTION 14/15/17/18/20 + variation 334 → ISBN in `_sku` AND
 *        `_global_unique_id`. Both. That is the pattern.
 *      · STAGING → the same products carry `BHP-MT-HC`-style internal SKUs with
 *        the ISBN only in `_global_unique_id`.
 *    A test hardcoding either environment's shape fails on the other for no
 *    defect. So the reference books are MEASURED, and product seven is required
 *    to be no worse than they are.
 */
$clt_reference_ids = array( 14, 15, 17, 18, 20 );
$clt_ref_routing   = array();
foreach ( $clt_reference_ids as $clt_ref_id ) {
	if ( wc_get_product( $clt_ref_id ) ) {
		$clt_ref_routing[ $clt_ref_id ] = bhp_colouring_product_isbn_state( $clt_ref_id );
	}
}
$clt_ref_all_route = ! empty( $clt_ref_routing );
foreach ( $clt_ref_routing as $clt_state ) {
	if ( ! $clt_state['routes'] ) {
		$clt_ref_all_route = false;
	}
}

$clt_colouring_ids = bhp_colouring_product_ids();

if ( empty( $clt_colouring_ids ) ) {
	// ⛔ NOT A PASS. An environment with no colouring record cannot make this
	//    claim, and saying so is the honest result — never a silent skip-as-ok.
	echo "  SKIP  no colouring product record on this environment — routing NOT CHECKED here.\n";
} else {
	$clt_col_id    = (int) reset( $clt_colouring_ids );
	$clt_col_state = bhp_colouring_product_isbn_state( $clt_col_id );

	bhp_clt_assert(
		$clt_col_state['sku'] === $clt_expected_isbn,
		'the colouring PRODUCT RECORD carries the ISBN in `_sku` (got "' . $clt_col_state['sku'] . '")',
		$failures,
		$passes
	);
	bhp_clt_assert(
		$clt_col_state['guid'] === $clt_expected_isbn,
		'the colouring PRODUCT RECORD carries the ISBN in `_global_unique_id` (got "' . $clt_col_state['guid'] . '")',
		$failures,
		$passes
	);
	bhp_clt_assert(
		$clt_col_state['agree'],
		'⛔ the two ISBN-bearing fields AGREE — a disagreement is never resolved by guessing',
		$failures,
		$passes
	);
	bhp_clt_assert(
		$clt_col_state['routes'],
		'the colouring line PASSES the 13-character gate, so it produces an order line at all',
		$failures,
		$passes
	);
	bhp_clt_assert(
		$clt_expected_isbn === bhp_colouring_isbn_for_product( $clt_col_id ),
		'product id ' . $clt_col_id . ' maps back to ' . $clt_expected_isbn . ' from the order-line side',
		$failures,
		$passes
	);

	// ⭐ THE SAMENESS CLAIM ITSELF: no worse than a chapter book, on this box.
	if ( $clt_ref_all_route ) {
		bhp_clt_assert(
			$clt_col_state['routes'],
			'⭐ the colouring line routes exactly as the chapter books do on this environment',
			$failures,
			$passes
		);
	} else {
		echo "  NOTE  chapter books do NOT all pass the 13-char SKU gate on this environment"
			. " (staging data divergence, pre-existing, out of scope) — comparative claim NOT made.\n";
	}

	// ⛔ The identity registry must survive the SKU change. If this fails, the
	//    cart maths silently reverts and CYCLE165-OPS-018 comes back.
	bhp_clt_assert(
		bhp_is_colouring_product( $clt_col_id ),
		'⛔ the ISBN-keyed record still resolves as a colouring product — cart maths intact',
		$failures,
		$passes
	);
}

// ⭐ The legacy SKU remains a resolving alias, so the deploy order is safe.
$clt_catalog_mt = bhp_colouring_catalog();
bhp_clt_assert(
	isset( $clt_catalog_mt['mariana']['sku_aliases'] )
		&& in_array( 'BHP-COLOR-MT-01', (array) $clt_catalog_mt['mariana']['sku_aliases'], true ),
	'the legacy SKU survives as an alias — code may ship before the record is edited',
	$failures,
	$passes
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9c · ⛔⛔ THE HAND-DELIVERY SKIP MUST TREAT PRODUCT SEVEN LIKE EVERY OTHER.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FINDING, AND IT IS THE REASSURING ONE: the skip is decided ENTIRELY at
 *    ORDER level and never inspects a line item. `bhp_school_pickup_block_
 *    bookvault_webhook()` tests, in order — WooCommerce's own prior decision,
 *    the webhook's delivery-URL HOST, the webhook's RESOURCE being `order`, the
 *    argument being numeric, and `bhp_school_pickup_order_is_pickup()`. A
 *    seventh product cannot reach any of those tests.
 *
 * ⛔ SO THIS SECTION ASSERTS THAT PROPERTY RATHER THAN ASSUMING IT, by proving
 *    the source contains no product-level test. A future edit that started
 *    filtering by product would break the free hand-delivery promise for a
 *    colouring book, and would break it silently.
 *
 * ⚠ IT IS A SOURCE ASSERTION, NOT A LIVE ORDER. Verified live on order 612 by a
 *   previous cycle; this suite does not re-verify that and does not claim to.
 */
echo "\n[§9c] ⛔ Hand-delivery Bookvault skip is product-agnostic\n";

$clt_pickup_src = file_get_contents(
	__DIR__ . '/../plugins/brave-hearts-bundle-pricing/includes/school-visit-pickup.php'
);
$clt_skip_fn = strstr( $clt_pickup_src, 'function bhp_school_pickup_block_bookvault_webhook' );
$clt_skip_fn = $clt_skip_fn ? substr( $clt_skip_fn, 0, 2200 ) : '';

bhp_clt_assert(
	'' !== $clt_skip_fn,
	'the skip function is present in the shipped source',
	$failures,
	$passes
);
bhp_clt_assert(
	'' !== $clt_skip_fn
		&& false === strpos( $clt_skip_fn, 'get_items' )
		&& false === strpos( $clt_skip_fn, 'get_product' )
		&& false === strpos( $clt_skip_fn, 'colouring' )
		&& false === strpos( $clt_skip_fn, 'get_sku' ),
	'⛔ the skip inspects NO line item, product, SKU or colouring state — product seven cannot alter it',
	$failures,
	$passes
);
bhp_clt_assert(
	'' !== $clt_skip_fn && false !== strpos( $clt_skip_fn, 'return false' ),
	'the skip can only ever SUPPRESS a delivery, never cause one',
	$failures,
	$passes
);
bhp_clt_assert(
	function_exists( 'bhp_school_pickup_order_is_pickup' )
		&& function_exists( 'bhp_school_pickup_is_fulfilment_webhook' ),
	'both order-level predicates the skip depends on are loaded',
	$failures,
	$passes
);
// ⭐ The host match is what makes a re-created webhook still caught.
bhp_clt_assert(
	in_array( 'bookvault.app', bhp_school_pickup_fulfilment_hosts(), true ),
	'the fulfilment host list still covers bookvault.app',
	$failures,
	$passes
);

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
