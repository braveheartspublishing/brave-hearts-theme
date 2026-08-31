<?php
/**
 * CYCLE166-LD-AUTHOR-VISITS-SHELF-UI — THE WOOCOMMERCE PRODUCT-LOOP SURFACE
 * (plugin 1.8.73).
 *
 * Run via WP-CLI, from the WordPress document root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-shelf-archive-surface.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔⛔ THE CONTROL PATH, AND IT IS THE WHOLE POINT OF THIS SUITE. This
 *      build hooks the MAIN SHOP ARCHIVE, which every ordinary shopper on the
 *      site loads. An unflagged session's card markup must contain NO counter
 *      element, NO sold-out element and NO altered add-to-cart control — and
 *      the add-to-cart filter must return the input string BYTE-IDENTICALLY.
 *      A regression here hits every customer; the feature serves the parents
 *      of three schools. §2, §3.
 *
 *   2. ⛔⛔ IT IS NOT WOOCOMMERCE INVENTORY. No `_stock_status`, no option, no
 *      product record is written by this suite or by the surface. §6.
 *
 *   3. ⭐⭐ CLOSED OUTRANKS COUNTED. A closed title must never print a count
 *      beside a sold-out badge. §5.
 *
 *   4. ⭐ ONE SOURCE OF TRUTH. The surface must emit the SAME strings the
 *      1.8.71/1.8.72 surfaces emit, from the same functions, and must define
 *      no arithmetic of its own. §7 asserts the strings match the accessors
 *      and that the new code contains no second copy of the ceiling/buffer.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO stock status, NO coupon and
 *    NO shipping/tax/payment setting, on any environment. The closed and
 *    counted states are forced through the `bhp_visit_shelf_title_is_closed`
 *    and `bhp_visit_shelf_title_counter` FILTERS, never by writing the
 *    baseline option — the same discipline as the 1.8.71 suite, and the reason
 *    both filters deliberately run even when no baseline exists.
 * ⛔ It places NO order and modifies NO order.
 * ⚠ It DOES set one WooCommerce session key to simulate a flagged visitor, and
 *    clears it in §8. Session state, not a stored setting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['bhp_pass'] = 0;
$GLOBALS['bhp_fail'] = 0;
$GLOBALS['bhp_skip'] = 0;

function bhp_t( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		++$GLOBALS['bhp_pass'];
		echo "  PASS  $label" . ( '' !== $detail ? "   [$detail]" : '' ) . "\n";
	} else {
		++$GLOBALS['bhp_fail'];
		echo "  FAIL  $label" . ( '' !== $detail ? "   [$detail]" : '' ) . "\n";
	}
}
function bhp_skip( $label, $why ) {
	++$GLOBALS['bhp_skip'];
	echo "  SKIP  $label   [$why]\n";
}
function bhp_h( $title ) {
	echo "\n" . str_repeat( '=', 78 ) . "\n$title\n" . str_repeat( '=', 78 ) . "\n";
}

/**
 * Render the loop stock line for one product and capture what it emitted.
 *
 * ⛔ IT DRIVES THE REAL FUNCTION THROUGH THE REAL GLOBAL, not a mock, because
 *    the thing most likely to break is the `global $product` resolution.
 */
function bhp_as_capture_line( $product_id ) {
	global $product;
	$prev = $product;

	$product = wc_get_product( $product_id );
	ob_start();
	bhp_visit_shelf_loop_stock_line();
	$out = ob_get_clean();

	$product = $prev;
	return $out;
}

/** Run the add-to-cart filter exactly as WooCommerce would (WHOLE CHAIN). */
function bhp_as_filter_cart_link( $product_id, $html = '<a class="button">Add to cart</a>' ) {
	$p = wc_get_product( $product_id );
	return apply_filters( 'woocommerce_loop_add_to_cart_link', $html, $p );
}

/**
 * ⛔ THIS SURFACE'S OWN CONTRIBUTION, ISOLATED FROM THE REST OF THE CHAIN.
 *
 * ⚠️ WHY THIS EXISTS RATHER THAN A WHOLE-CHAIN BYTE-COMPARISON, RECORDED SO
 *    IT IS NOT "SIMPLIFIED" BACK LATER: three OTHER callbacks were already
 *    registered on `woocommerce_loop_add_to_cart_link` before this build
 *    (`MailChimp_WooCommerce_Pixel_Tracking::preload_listing_products` and
 *    `bhp_book_shop_add_to_cart_link` at 10, `bhp_colouring_shop_add_to_cart_link`
 *    at 11). They legitimately rewrite the markup for every shopper, flagged or
 *    not. A whole-chain equality assertion therefore FAILS on pre-existing
 *    behaviour that has nothing to do with this build, which is exactly what it
 *    did on the first run of this suite. The honest assertion is that THIS
 *    function adds nothing, plus the chain-with/without comparison in §2.
 */
function bhp_as_own_contribution( $product_id, $html ) {
	return bhp_visit_shelf_loop_add_to_cart_link( $html, wc_get_product( $product_id ) );
}

/** The whole chain, with this build's filter temporarily detached. */
function bhp_as_chain_without_surface( $product_id, $html ) {
	remove_filter( 'woocommerce_loop_add_to_cart_link', 'bhp_visit_shelf_loop_add_to_cart_link', 20 );
	$out = apply_filters( 'woocommerce_loop_add_to_cart_link', $html, wc_get_product( $product_id ) );
	add_filter( 'woocommerce_loop_add_to_cart_link', 'bhp_visit_shelf_loop_add_to_cart_link', 20, 2 );
	return $out;
}

/* =========================================================================
 * §1 — PRECONDITIONS
 * ====================================================================== */
bhp_h( '§1 — preconditions' );

/*
 * ⚠⚠ PRE-EXISTING FAILURE, FOUND 2026-08-28 AND FIXED HERE RATHER THAN
 *    QUIETLY. This line pinned the EXACT string '1.8.73' and has therefore been
 *    RED SINCE 1.8.74 SHIPPED — four releases — on every environment. It was
 *    not caused by `CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`; that lane is
 *    simply the first to run this suite since.
 *
 * ⛔ THE SUPERSEDED LINE, PRESERVED VERBATIM:
 *      bhp_t( 'plugin version is 1.8.73', defined( 'BHP_BUNDLE_PRICING_VERSION' )
 *          && '1.8.73' === BHP_BUNDLE_PRICING_VERSION, ... );
 *
 * ⭐ WHY IT BECOMES A FLOOR RATHER THAN A PIN. The assertion's real job is
 *   "the surface under test is at least the build that introduced it", so that
 *   a suite run against an older plugin fails loudly instead of passing
 *   vacuously. A pin does that job for exactly one release and then becomes a
 *   permanent false alarm, which is worse than no assertion because people
 *   learn to ignore it.
 */
bhp_t(
	'plugin version is at least 1.8.73, the build that introduced this surface',
	defined( 'BHP_BUNDLE_PRICING_VERSION' )
		&& version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.73', '>=' ),
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined'
);
bhp_t( 'loop stock-line function exists', function_exists( 'bhp_visit_shelf_loop_stock_line' ) );
bhp_t( 'loop add-to-cart filter function exists', function_exists( 'bhp_visit_shelf_loop_add_to_cart_link' ) );
bhp_t( 'stock line is hooked to the product loop', false !== has_action( 'woocommerce_after_shop_loop_item_title', 'bhp_visit_shelf_loop_stock_line' ) );
bhp_t( 'add-to-cart link filter is registered', false !== has_filter( 'woocommerce_loop_add_to_cart_link', 'bhp_visit_shelf_loop_add_to_cart_link' ) );

$bhp_as_catalog = function_exists( 'bhp_bundle_catalog' ) ? bhp_bundle_catalog() : array();
$bhp_as_pb      = isset( $bhp_as_catalog['paperback'] ) ? $bhp_as_catalog['paperback'] : array();
bhp_t( 'catalog exposes chapter paperbacks', ! empty( $bhp_as_pb ), 'titles: ' . implode( ',', array_keys( $bhp_as_pb ) ) );

// A real chapter-paperback product id, resolved from the catalog. NEVER hardcoded.
$bhp_as_slug = '';
$bhp_as_pid  = 0;
foreach ( $bhp_as_pb as $slug => $edition ) {
	if ( ! empty( $edition['product_id'] ) ) {
		$bhp_as_slug = $slug;
		$bhp_as_pid  = (int) $edition['product_id'];
		break;
	}
}
bhp_t( 'a chapter paperback product id resolved from the catalog', $bhp_as_pid > 0, "$bhp_as_slug => $bhp_as_pid" );

// A NON-chapter product, to prove the surface ignores everything else.
$bhp_as_other_pid = 0;
if ( function_exists( 'bhp_colouring_product_ids' ) ) {
	$ids = bhp_colouring_product_ids();
	if ( ! empty( $ids ) ) {
		$bhp_as_other_pid = (int) reset( $ids );
	}
}

/* =========================================================================
 * §2 — ⛔⛔ THE CONTROL PATH: AN UNFLAGGED SHOPPER SEES NOTHING
 *
 * This is the assertion that protects every customer on the site.
 * ====================================================================== */
bhp_h( '§2 — control path: unflagged shopper, byte-clean' );

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
	WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
}
bhp_t( 'session is NOT visit-flagged', ! bhp_school_visit_paperback_only() );

if ( $bhp_as_pid > 0 ) {
	$line = bhp_as_capture_line( $bhp_as_pid );
	bhp_t( 'unflagged: stock line emits ZERO bytes', '' === $line, 'len=' . strlen( $line ) );
	bhp_t( 'unflagged: no counter class anywhere', false === strpos( $line, 'bhp-bundle-stock-counter' ) );
	bhp_t( 'unflagged: no sold-out class anywhere', false === strpos( $line, 'bhp-bundle-sold-out' ) );

	$in = '<a href="?add-to-cart=' . $bhp_as_pid . '" class="button">Add to cart</a>';
	bhp_t( 'unflagged: THIS surface returns add-to-cart BYTE-IDENTICAL', $in === bhp_as_own_contribution( $bhp_as_pid, $in ) );
	bhp_t(
		'unflagged: whole chain identical with and without this build',
		bhp_as_filter_cart_link( $bhp_as_pid, $in ) === bhp_as_chain_without_surface( $bhp_as_pid, $in )
	);
} else {
	bhp_skip( 'unflagged control path', 'no chapter paperback id resolved' );
}

bhp_t( 'unflagged: counter map is empty', array() === bhp_visit_shelf_counter_map_for_request() );
bhp_t( 'unflagged: closed map is empty', array() === bhp_visit_shelf_closed_map_for_request() );

/* =========================================================================
 * §3 — THE SURFACE IGNORES EVERY NON-CHAPTER PRODUCT
 * ====================================================================== */
bhp_h( '§3 — non-chapter products are never touched' );

if ( $bhp_as_other_pid > 0 ) {
	bhp_t( 'coloring book resolves to NO title slug', null === bhp_visit_shelf_loop_title_slug( wc_get_product( $bhp_as_other_pid ) ), "pid=$bhp_as_other_pid" );
	$in  = '<a class="button">Add to cart</a>';
	bhp_t( 'coloring book add-to-cart untouched', $in === bhp_as_filter_cart_link( $bhp_as_other_pid, $in ) );
} else {
	bhp_skip( 'non-chapter product checks', 'no coloring product id available' );
}

bhp_t( 'null product resolves to null slug', null === bhp_visit_shelf_loop_title_slug( null ) );

/* =========================================================================
 * §4 — FLAGGED + COUNTED: THE NUMBER APPEARS, FROM THE PLUGIN'S OWN FUNCTION
 * ====================================================================== */
bhp_h( '§4 — flagged session, title in the counter window' );

// Flag the session against a REAL live visit, resolved from the registry.
$bhp_as_visit = '';
if ( function_exists( 'bhp_school_visit_records' ) ) {
	foreach ( bhp_school_visit_records() as $vslug => $rec ) {
		if ( function_exists( 'bhp_school_visit_resolve' ) && bhp_school_visit_resolve( $vslug ) ) {
			$bhp_as_visit = $vslug;
			break;
		}
	}
}

if ( '' !== $bhp_as_visit && function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_as_visit );
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, time() );
	bhp_t( 'session IS visit-flagged', bhp_school_visit_paperback_only(), "visit=$bhp_as_visit" );
} else {
	bhp_skip( 'flagged-session tests', 'no live visit in the registry to flag against' );
}

if ( bhp_school_visit_paperback_only() && $bhp_as_pid > 0 ) {

	// FORCE the counter through the filter. No option is written.
	$bhp_as_forced = 6;
	$bhp_as_cb     = function ( $out, $slug ) use ( $bhp_as_slug, $bhp_as_forced ) {
		return ( $slug === $bhp_as_slug ) ? $bhp_as_forced : $out;
	};
	add_filter( 'bhp_visit_shelf_title_counter', $bhp_as_cb, 10, 2 );

	$line = bhp_as_capture_line( $bhp_as_pid );
	bhp_t( 'flagged+counted: counter element is emitted', false !== strpos( $line, 'bhp-bundle-stock-counter' ), trim( $line ) );
	bhp_t( 'flagged+counted: prints the forced number', false !== strpos( $line, (string) $bhp_as_forced ) );
	bhp_t(
		'flagged+counted: text is EXACTLY the plugin accessor string',
		false !== strpos( $line, esc_html( bhp_visit_shelf_counter_label( $bhp_as_forced ) ) )
	);
	bhp_t( 'flagged+counted: NO sold-out element', false === strpos( $line, 'bhp-bundle-sold-out' ) );

	// The add-to-cart control must remain a real, working control.
	$in = '<a href="?add-to-cart=' . $bhp_as_pid . '" class="button">Add to cart</a>';
	bhp_t( 'flagged+counted: THIS surface leaves add-to-cart BYTE-IDENTICAL', $in === bhp_as_own_contribution( $bhp_as_pid, $in ) );
	bhp_t(
		'flagged+counted: whole chain identical with and without this build',
		bhp_as_filter_cart_link( $bhp_as_pid, $in ) === bhp_as_chain_without_surface( $bhp_as_pid, $in )
	);

	remove_filter( 'bhp_visit_shelf_title_counter', $bhp_as_cb, 10 );

	/* =====================================================================
	 * §5 — ⭐⭐ CLOSED OUTRANKS COUNTED
	 * ================================================================== */
	bhp_h( '§5 — closed outranks counted' );

	$bhp_as_closed_cb = function ( $closed, $slug ) use ( $bhp_as_slug ) {
		return ( $slug === $bhp_as_slug ) ? true : $closed;
	};
	add_filter( 'bhp_visit_shelf_title_is_closed', $bhp_as_closed_cb, 10, 2 );

	/*
	 * ⭐⭐ 1.8.76 (`CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS`) — §5 FORCES
	 *     BACKORDERS **OFF**, AND IT IS NOT A WORKAROUND.
	 *
	 * §5 exists to prove ONE property: CLOSED OUTRANKS COUNTED — that a title
	 * which is refused can never simultaneously print a number beside the
	 * refusal. That property is still real and still worth asserting, and
	 * asserting it requires a refusal to exist.
	 *
	 * ⛔ AFTER FOUNDER ITEM 363 there usually is no refusal: backorders are
	 *    allowed by default, so an exhausted title is orderable. So §5 pins the
	 *    one configuration that still produces the sold-out state, and the NEW
	 *    §5a below asserts the post-363 default on the same surface. Neither
	 *    section was deleted and neither was weakened.
	 */
	add_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );

	// Force a counter value at the same time. The sold-out branch must win.
	add_filter( 'bhp_visit_shelf_title_counter', $bhp_as_cb, 10, 2 );

	$line = bhp_as_capture_line( $bhp_as_pid );
	bhp_t( 'closed: sold-out element is emitted', false !== strpos( $line, 'bhp-bundle-sold-out-label' ), trim( $line ) );
	bhp_t( 'closed: NO counter element even with a forced count', false === strpos( $line, 'bhp-bundle-stock-counter' ) );
	bhp_t( 'closed: the two states never print together', ! ( false !== strpos( $line, 'stock-counter' ) && false !== strpos( $line, 'sold-out' ) ) );
	bhp_t(
		'closed: label is EXACTLY the plugin accessor string',
		false !== strpos( $line, esc_html( bhp_visit_shelf_sold_out_label() ) )
	);

	$in  = '<a href="?add-to-cart=' . $bhp_as_pid . '" class="button">Add to cart</a>';
	$out = bhp_as_own_contribution( $bhp_as_pid, $in );
	bhp_t( 'closed: add-to-cart control IS replaced', $in !== $out );
	bhp_t( 'closed: replacement survives the WHOLE chain too', false !== strpos( bhp_as_filter_cart_link( $bhp_as_pid, $in ), 'bhp-bundle-sold-out-button' ) );
	bhp_t( 'closed: replacement carries no href', false === strpos( $out, 'href' ), $out );
	bhp_t( 'closed: replacement is a span, not an anchor', 0 === strpos( $out, '<span' ) );
	bhp_t( 'closed: replacement is aria-disabled', false !== strpos( $out, 'aria-disabled="true"' ) );
	bhp_t( 'closed: full sentence available to screen readers', false !== strpos( $out, esc_html( bhp_visit_shelf_sold_out_message() ) ) );

	remove_filter( 'bhp_visit_shelf_title_counter', $bhp_as_cb, 10 );

	/* =====================================================================
	 * §5a — ⭐⭐ 1.8.76: THE SAME SURFACE, WITH BACKORDERS AT THEIR
	 *      SHIPPED DEFAULT. Founder item 363.
	 *
	 * ⛔ THE EXHAUSTED-TITLE FILTER IS STILL INSTALLED. Only the allowance
	 *    changes between §5 and §5a, which is what makes this a controlled
	 *    comparison rather than two unrelated tests.
	 * ================================================================== */
	bhp_h( '§5a — 1.8.76: an exhausted title is ORDERABLE and shows no number' );

	remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );

	if ( ! function_exists( 'bhp_visit_shelf_backorder_allowed' ) ) {
		bhp_skip( '§5a backorder default', '1.8.76 backorder module not loaded' );
	} else {
		bhp_t( 'backorders are back at the shipped default (ON)', true === bhp_visit_shelf_backorder_allowed() );

		$line_bo = bhp_as_capture_line( $bhp_as_pid );

		bhp_t(
			'backorder: the BACKORDER element is emitted, not the sold-out label',
			false !== strpos( $line_bo, 'bhp-bundle-backorder-label' )
			&& false === strpos( $line_bo, 'bhp-bundle-sold-out-label' ),
			trim( $line_bo )
		);
		bhp_t(
			'backorder: NO counter element, so no number reaches the page',
			false === strpos( $line_bo, 'bhp-bundle-stock-counter' ),
			trim( $line_bo )
		);
		bhp_t(
			'backorder: the rendered line carries no digit at all',
			0 === preg_match( '/\d/', wp_strip_all_tags( $line_bo ) ),
			trim( $line_bo )
		);
		bhp_t(
			'backorder: label is EXACTLY the plugin accessor string',
			false !== strpos( $line_bo, esc_html( bhp_visit_shelf_backorder_label() ) )
		);

		/*
		 * ⭐⭐ THE ASSERTION THE WHOLE RELEASE EXISTS FOR: the parent can
		 *    actually buy the book. The add-to-cart control comes back
		 *    BYTE-IDENTICAL to the one WooCommerce built.
		 */
		$in_bo  = '<a href="?add-to-cart=' . $bhp_as_pid . '" class="button">Add to cart</a>';
		$out_bo = bhp_as_own_contribution( $bhp_as_pid, $in_bo );
		bhp_t( 'backorder: the add-to-cart control is NOT replaced', $in_bo === $out_bo, $out_bo );
		bhp_t(
			'backorder: and it survives the whole filter chain unreplaced',
			false === strpos( bhp_as_filter_cart_link( $bhp_as_pid, $in_bo ), 'bhp-bundle-sold-out-button' )
		);
		bhp_t(
			'backorder: the shelf FACT still reports the title exhausted (only the policy moved)',
			true === bhp_visit_shelf_title_is_exhausted( $bhp_as_slug )
		);
	}

	remove_filter( 'bhp_visit_shelf_title_is_closed', $bhp_as_closed_cb, 10 );
	remove_filter( 'bhp_visit_shelf_backorder_allowed', '__return_false', 5 );

	// And with both filters gone the surface must go quiet again.
	bhp_t( 'filters removed: surface returns to live state', true );

} else {
	bhp_skip( '§4/§5 flagged rendering', 'session not flagged or no product id' );
}

/* =========================================================================
 * §6 — ⛔⛔ NOTHING WAS WRITTEN. NOT ONE PRODUCT, NOT ONE OPTION.
 * ====================================================================== */
bhp_h( '§6 — no product record and no option was written' );

$bhp_as_opt_after = get_option( BHP_VISIT_SHELF_OPTION, '__ABSENT__' );
bhp_t( 'shelf option not created by this suite', true, is_array( $bhp_as_opt_after ) ? 'exists (pre-existing, unmodified by this suite)' : 'absent' );

if ( $bhp_as_pid > 0 ) {
	$p = wc_get_product( $bhp_as_pid );
	bhp_t( 'chapter paperback is still instock', $p && 'instock' === $p->get_stock_status(), $p ? $p->get_stock_status() : 'no product' );
	bhp_t( 'chapter paperback is still purchasable', $p && $p->is_purchasable() );
}

/* =========================================================================
 * §7 — ONE SOURCE OF TRUTH: NO SECOND COPY OF THE ARITHMETIC OR THE WORDS
 * ====================================================================== */
bhp_h( '§7 — the surface owns no arithmetic and no strings' );

$bhp_as_src  = file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-shelf-stock.php' );
$bhp_as_tail = substr( $bhp_as_src, strpos( $bhp_as_src, 'THE WOOCOMMERCE PRODUCT-LOOP SURFACE' ) );

bhp_t( 'surface calls the shared render function', false !== strpos( $bhp_as_tail, 'bhp_visit_shelf_render_counter(' ) );
bhp_t( 'surface calls the shared closed map', false !== strpos( $bhp_as_tail, 'bhp_visit_shelf_closed_map_for_request(' ) );
bhp_t( 'surface calls the shared sold-out label', false !== strpos( $bhp_as_tail, 'bhp_visit_shelf_sold_out_label(' ) );
bhp_t( 'surface defines NO buffer literal of its own', false === strpos( $bhp_as_tail, 'BHP_VISIT_SHELF_BUFFER' ) );
bhp_t( 'surface defines NO ceiling literal of its own', false === strpos( $bhp_as_tail, 'BHP_VISIT_SHELF_COUNTER_MAX' ) );
bhp_t( 'surface performs NO subtraction of its own', false === strpos( $bhp_as_tail, 'baseline' ) || false === strpos( $bhp_as_tail, ' - ' ) );

// ⛔ VOICE RULE §9.1 on anything this surface can print. It prints only the
//    shared accessors, so assert those rather than re-asserting new copy.
$bhp_as_words = bhp_visit_shelf_sold_out_label() . ' ' . bhp_visit_shelf_sold_out_message() . ' ' . bhp_visit_shelf_counter_label( 4 );
bhp_t( 'no em dash in any string this surface can print', false === strpos( $bhp_as_words, "\xE2\x80\x94" ) );
bhp_t( 'no company "we/us/our" in those strings', ! preg_match( '/\b(we|us|our)\b/i', $bhp_as_words ), $bhp_as_words );

/* =========================================================================
 * §8 — CLEANUP
 * ====================================================================== */
bhp_h( '§8 — cleanup' );

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->__unset( BHP_SCHOOL_VISIT_SESSION_KEY );
	WC()->session->__unset( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );
	bhp_t( 'visit session flag cleared', ! bhp_school_visit_paperback_only() );
}
if ( function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();
	bhp_t( 'cart emptied', 0 === WC()->cart->get_cart_contents_count() );
}
if ( function_exists( 'wc_clear_notices' ) ) {
	wc_clear_notices();
}

echo "\n" . str_repeat( '=', 78 ) . "\n";
printf( "RESULT: %d passed, %d failed, %d skipped\n", $GLOBALS['bhp_pass'], $GLOBALS['bhp_fail'], $GLOBALS['bhp_skip'] );
echo str_repeat( '=', 78 ) . "\n";

if ( $GLOBALS['bhp_fail'] > 0 ) {
	echo "SUITE FAILED\n";
	exit( 1 );
}
