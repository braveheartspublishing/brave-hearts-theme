<?php
/**
 * CYCLE164-LD-PAPERBACK-DEFAULT — A VISIT-FLAGGED SESSION IS PAPERBACK ONLY.
 *
 * Run via WP-CLI, from the WordPress document root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-paperback-only.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔⛔ THE CONTROL PATH. An ordinary shopper must still be able to put a
 *      HARDCOVER in the cart and check out with it. Hardcover is in stock and
 *      fully purchasable and that is not changing. A regression there hits
 *      every customer; the bug being fixed hits the parents of three schools.
 *      §2 and §6.
 *
 *   2. ⭐⭐ THE SERVER-SIDE REFUSAL IS REAL. Andrew Signore, 2026-08-18,
 *      verbatim (⛔ RELAYED through the Chief of Staff; NOT witnessed
 *      first-hand by the agent that wrote this): "also for the orders on the
 *      pre-signed books for the read alouds- based on my inventory I can only
 *      do paperbacks". Hiding the hardcover card is NOT the fix — a parent can
 *      add a hardcover in a normal session and only THEN click a school link,
 *      or use a stale `?add-to-cart=` link, or drive the Store API directly.
 *      §4 exercises a REAL SYNTHETIC CART through the real hooks. §5 is the
 *      stale-cart case specifically.
 *
 *   3. THE UI AND THE SERVER AGREE. Every surface asks the SAME predicate the
 *      enforcement asks, so nothing is hidden that would be accepted and
 *      nothing is offered that would be refused. §3.
 *
 *   4. ⛔ THE VOICE RULE. Standing rule §9.1, adopted by Andrew Signore on
 *      2026-08-18: no "we"/"us"/"our" standing for the company in
 *      customer-facing words. Asserted on the two strings this build adds, and
 *      ONLY those. §7.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO stock status, NO coupon, NO
 *    shipping, tax, pickup or payment setting, on any environment. §8 re-reads
 *    the two WooCommerce local-pickup options and the flat-rate settings before
 *    and after every flagged code path and asserts they came back identical.
 * ⛔ It places NO order, delivers NO webhook and takes NO payment.
 * ⛔ It touches NO visit registry row. It reads the registry and uses a REAL
 *    live visit; if there is not one, §3 to §6 SKIP with the reason printed,
 *    because a vacuous pass on the load-bearing assertion is worse than a skip.
 * ⚠ It DOES set one key in the WooCommerce session, and DOES put items in the
 *    CLI request's own cart. Both are session state, not stored settings, and
 *    both are cleared in §9. It is the only way to exercise the real hooks
 *    rather than a mock.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skips    = array();

function bhp_pbo_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_pbo_skip( $label, $reason, array &$skips ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$skips[] = $label;
}

/**
 * Customer-facing "we/us/our" detector (standing rule §9.1).
 *
 * Word-boundary matched, case-insensitive. Deliberately pointed ONLY at the
 * strings this build adds — never swept across arbitrary copy, because "US"
 * the country abbreviation would trip it and because §9.1a forbids rewriting a
 * quoted third party.
 */
function bhp_pbo_has_company_we( $text ) {
	return (bool) preg_match( '/\b(we|us|our|ours)\b/i', (string) $text );
}

echo "\n=== CYCLE164 visit paperback-only gate — bundle plugin "
	. ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '?' )
	. " / theme " . wp_get_theme()->get( 'Version' ) . " ===\n\n";

/* =====================================================================
 * §1 — PRECONDITIONS
 * ===================================================================== */
echo "--- §1 preconditions ---\n";

bhp_pbo_assert( function_exists( 'bhp_school_visit_paperback_only' ), '§1 the plugin exposes bhp_school_visit_paperback_only()', $failures );
bhp_pbo_assert( function_exists( 'bhp_school_visit_use_delivery_framing' ), '§1 the ONE shared visit predicate is present (no second source of truth)', $failures );
bhp_pbo_assert( function_exists( 'bhp_school_visit_hardcover_product_ids' ), '§1 the hardcover id set is exposed', $failures );
bhp_pbo_assert( function_exists( 'bhp_school_visit_is_hardcover' ), '§1 the per-item hardcover test is exposed', $failures );
bhp_pbo_assert( function_exists( 'bhp_school_visit_cart_has_hardcover' ), '§1 the cart-level hardcover test is exposed', $failures );
bhp_pbo_assert( function_exists( 'bhp_bundle_available_format_order' ), '§1 the presentation helper the templates read is exposed', $failures );
bhp_pbo_assert( function_exists( 'bhp_bundle_hardcover_is_offerable' ), '§1 bhp_bundle_hardcover_is_offerable() is exposed', $failures );
bhp_pbo_assert( function_exists( 'bhp_school_visit_paperback_only_message' ), '§1 the refusal message has ONE author', $failures );
bhp_pbo_assert( function_exists( 'bhp_school_visit_paperback_only_note' ), '§1 the short selector note has ONE author', $failures );

/*
 * ⭐ THE HOOKS ARE ASSERTED TO BE REGISTERED, not assumed. A file that loads
 *    but whose add_filter line was lost in an edit would otherwise pass every
 *    functional assertion below by being called directly, while enforcing
 *    nothing on a real request.
 */
bhp_pbo_assert(
	false !== has_filter( 'woocommerce_add_to_cart_validation', 'bhp_school_visit_block_hardcover_add' ),
	'§1 ⭐ SEAM 1 is registered: woocommerce_add_to_cart_validation (classic add)',
	$failures
);
bhp_pbo_assert(
	false !== has_action( 'woocommerce_store_api_validate_add_to_cart', 'bhp_school_visit_block_hardcover_store_api_add' ),
	'§1 ⭐ SEAM 2 is registered: woocommerce_store_api_validate_add_to_cart (Store API add)',
	$failures
);
bhp_pbo_assert(
	false !== has_action( 'woocommerce_store_api_cart_errors', 'bhp_school_visit_hardcover_store_api_cart_error' ),
	'§1 ⭐⭐ SEAM 3 is registered: woocommerce_store_api_cart_errors (the Blocks checkout hard stop)',
	$failures
);
bhp_pbo_assert(
	false !== has_action( 'woocommerce_check_cart_items', 'bhp_school_visit_hardcover_classic_cart_error' ),
	'§1 SEAM 4 is registered: woocommerce_check_cart_items (classic cart/checkout)',
	$failures
);
bhp_pbo_assert(
	false !== has_filter( 'woocommerce_add_cart_item_data', 'bhp_school_visit_block_hardcover_cart_add' ),
	'§1 ⭐⭐ SEAM 5 is registered: woocommerce_add_cart_item_data (WC_Cart::add_to_cart() itself, which does NOT fire woocommerce_add_to_cart_validation)',
	$failures
);

/*
 * The three hardcover ids, READ FROM THE CATALOG. If this ever returns an id
 * set that disagrees with bhp_bundle_catalog(), the restriction is looking at
 * the wrong products and every assertion below would be measuring nothing.
 */
$bhp_pbo_hc_ids = bhp_school_visit_hardcover_product_ids();
$bhp_pbo_catalog = function_exists( 'bhp_bundle_catalog' ) ? bhp_bundle_catalog() : array();
$bhp_pbo_expected_hc = array();
foreach ( ( isset( $bhp_pbo_catalog['hardcover'] ) ? $bhp_pbo_catalog['hardcover'] : array() ) as $bhp_pbo_ed ) {
	if ( ! empty( $bhp_pbo_ed['product_id'] ) ) {
		$bhp_pbo_expected_hc[] = (int) $bhp_pbo_ed['product_id'];
	}
}
sort( $bhp_pbo_expected_hc );
$bhp_pbo_hc_sorted = $bhp_pbo_hc_ids;
sort( $bhp_pbo_hc_sorted );
bhp_pbo_assert(
	! empty( $bhp_pbo_expected_hc ) && $bhp_pbo_expected_hc === array_values( array_intersect( $bhp_pbo_hc_sorted, $bhp_pbo_expected_hc ) ),
	'§1 the hardcover id set is READ FROM bhp_bundle_catalog(), not restated (' . implode( ',', $bhp_pbo_hc_sorted ) . ')',
	$failures
);

$bhp_pbo_pb_ids = array();
foreach ( ( isset( $bhp_pbo_catalog['paperback'] ) ? $bhp_pbo_catalog['paperback'] : array() ) as $bhp_pbo_ed ) {
	$bhp_pbo_pb_ids[] = array(
		'product_id'   => (int) $bhp_pbo_ed['product_id'],
		'variation_id' => (int) $bhp_pbo_ed['variation_id'],
	);
}
bhp_pbo_assert( count( $bhp_pbo_pb_ids ) === 3, '§1 three paperback editions resolve from the catalog', $failures );

/*
 * ⛔ THE SETTINGS SNAPSHOT. Taken before ANY flagged code path runs and
 *    re-read in §8. This suite must not be able to change a WooCommerce
 *    setting even by accident — that is an Andrew-only gate.
 */
/**
 * ⛔⭐ READ THE RAW STORED ROW, NOT `get_option()`. THIS IS NOT PEDANTRY AND IT
 *     COST A FAILING ASSERTION TO LEARN, SO IT IS WRITTEN DOWN HERE.
 *
 * `school-visit-pickup.php` filters the READS of
 * `woocommerce_pickup_location_settings` and `pickup_location_pickup_locations`
 * (`option_*` / `default_option_*`, 1.8.52) so that WooCommerce's own Local
 * Pickup exists for the duration of ONE flagged request. Nothing is ever
 * written. So `get_option()` legitimately returns DIFFERENT values before and
 * after this suite sets the visit flag — and a before/after comparison built on
 * `get_option()` reports a "change" where no byte moved on disk.
 *
 * The question this section actually has to answer is "did anything get
 * WRITTEN", so it reads the `wp_options` row directly through `$wpdb`, which no
 * filter touches. A false alarm here would be as bad as a missed one: it would
 * teach a future session that this suite mutates settings, which it does not.
 *
 * @param string $name Option name.
 * @return string Raw stored value, or the literal 'ABSENT'.
 */
function bhp_pbo_raw_option( $name ) {
	global $wpdb;
	$val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ) ); // phpcs:ignore WordPress.DB
	return null === $val ? 'ABSENT' : (string) $val;
}

$bhp_pbo_opt_before = array(
	'woocommerce_pickup_location_settings' => bhp_pbo_raw_option( 'woocommerce_pickup_location_settings' ),
	'pickup_location_pickup_locations'     => bhp_pbo_raw_option( 'pickup_location_pickup_locations' ),
	'woocommerce_flat_rate_1_settings'     => bhp_pbo_raw_option( 'woocommerce_flat_rate_1_settings' ),
	'bhp_school_visits'                    => bhp_pbo_raw_option( 'bhp_school_visits' ),
);
$bhp_pbo_stock_before = array();
foreach ( $bhp_pbo_expected_hc as $bhp_pbo_hc_id ) {
	$bhp_pbo_stock_before[ $bhp_pbo_hc_id ] = get_post_meta( $bhp_pbo_hc_id, '_stock_status', true );
}

/* =====================================================================
 * §2 — ⛔ THE CONTROL PATH IS UNCHANGED
 * ===================================================================== */
echo "\n--- §2 control path (no visit session) ---\n";

bhp_pbo_assert( false === bhp_school_visit_paperback_only(), '§2 with no visit session the restriction predicate is FALSE', $failures );
bhp_pbo_assert( true === bhp_bundle_hardcover_is_offerable(), '§2 ⛔ hardcover IS offerable to an ordinary shopper', $failures );
$bhp_pbo_ctrl_formats = bhp_bundle_available_format_order();
bhp_pbo_assert(
	2 === count( $bhp_pbo_ctrl_formats )
	&& in_array( 'paperback', $bhp_pbo_ctrl_formats, true )
	&& in_array( 'hardcover', $bhp_pbo_ctrl_formats, true ),
	'§2 ⛔ BOTH physical formats are offered to an ordinary shopper (' . implode( ',', $bhp_pbo_ctrl_formats ) . ')',
	$failures
);
/*
 * ⭐ AND THE FIRST-SEEN DEFAULT IS PAPERBACK (Andrew, 2026-08-18). Pinned here
 *    as well as in the two collection suites, because THIS suite is the one a
 *    future school-visit change will be run against, and the two rulings
 *    shipped together.
 */
bhp_pbo_assert(
	function_exists( 'bhp_bundle_default_format' ) && 'paperback' === bhp_bundle_default_format(),
	'§2 ⭐ the SITE-WIDE first-seen format is PAPERBACK (Andrew, 2026-08-18)',
	$failures
);
bhp_pbo_assert(
	'paperback' === $bhp_pbo_ctrl_formats[0],
	'§2 ⭐ and PAPERBACK leads the presentation order for an ordinary shopper',
	$failures
);

bhp_pbo_assert( '' === bhp_school_visit_paperback_only_note(), '§2 the paperback-only note is EMPTY for an ordinary shopper, so no surface prints it', $failures );

foreach ( $bhp_pbo_expected_hc as $bhp_pbo_hc_id ) {
	bhp_pbo_assert(
		true === bhp_school_visit_block_hardcover_add( true, $bhp_pbo_hc_id, 1, 0 ),
		"§2 ⛔ SEAM 1 ALLOWS hardcover {$bhp_pbo_hc_id} for an ordinary shopper",
		$failures
	);
}
/*
 * ⛔ SEAM 1 NEVER OVERWRITES SOMEBODY ELSE'S REFUSAL. A sentinel is used rather
 *    than `false`, because `false` in / `false` out would also be produced by
 *    this filter refusing on its own account, which would be a vacuous
 *    assertion.
 */
bhp_pbo_assert(
	'SOMEONE-ELSE-ALREADY-REFUSED' === bhp_school_visit_block_hardcover_add( 'SOMEONE-ELSE-ALREADY-REFUSED', $bhp_pbo_expected_hc[0], 1, 0 ),
	'§2 SEAM 1 returns the refusal of another filter UNCHANGED rather than replacing its reason',
	$failures
);


/* =====================================================================
 * §3 — THE FLAGGED PATH: PRESENTATION
 * ===================================================================== */
echo "\n--- §3 flagged path: what the surfaces offer ---\n";

$bhp_pbo_live_slug = '';
if ( function_exists( 'bhp_school_visit_records' ) && function_exists( 'bhp_school_visit_resolve' ) ) {
	foreach ( array_keys( bhp_school_visit_records() ) as $bhp_pbo_slug ) {
		if ( bhp_school_visit_resolve( $bhp_pbo_slug ) ) {
			$bhp_pbo_live_slug = $bhp_pbo_slug;
			break;
		}
	}
}

$bhp_pbo_flagged = false;

if ( '' === $bhp_pbo_live_slug ) {
	bhp_pbo_skip( '§3 to §6 (the entire flagged path)', 'no live, non-expired visit exists in the registry on this environment, so a pass here would be vacuous', $skips );
} elseif ( ! function_exists( 'WC' ) ) {
	bhp_pbo_skip( '§3 to §6 (the entire flagged path)', 'WooCommerce is not loaded', $skips );
} else {
	if ( ! WC()->session && is_callable( array( WC(), 'initialize_session' ) ) ) {
		WC()->initialize_session();
	}
	if ( ! WC()->session ) {
		bhp_pbo_skip( '§3 to §6 (the entire flagged path)', 'no WooCommerce session could be initialised in CLI', $skips );
	} else {
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_pbo_live_slug );
		$bhp_pbo_flagged = true;

		bhp_pbo_assert(
			true === bhp_school_visit_use_delivery_framing(),
			"§3 the harness holds a REAL live visit flag ({$bhp_pbo_live_slug}) — the shared predicate is TRUE",
			$failures
		);
		bhp_pbo_assert( true === bhp_school_visit_paperback_only(), '§3 ⭐ the restriction predicate is TRUE on a flagged session', $failures );
		bhp_pbo_assert( false === bhp_bundle_hardcover_is_offerable(), '§3 ⭐ hardcover is NOT offerable on a flagged session', $failures );
		bhp_pbo_assert(
			array( 'paperback' ) === bhp_bundle_available_format_order(),
			'§3 ⭐⭐ the format helper every surface reads returns PAPERBACK ALONE (' . implode( ',', bhp_bundle_available_format_order() ) . ')',
			$failures
		);
		$bhp_pbo_note = bhp_school_visit_paperback_only_note();
		bhp_pbo_assert(
			'' !== $bhp_pbo_note,
			'§3 the short selector note is present on a flagged session: "' . $bhp_pbo_note . '"',
			$failures
		);


		/*
		 * ⭐ THE THEME'S OWN HELPERS DELEGATE HERE RATHER THAN DECIDING. If the
		 *    theme ever grows a second answer, the product page would hide a
		 *    card the server accepts, or show one it refuses.
		 */
		if ( function_exists( 'bhp_book_available_formats' ) ) {
			bhp_pbo_assert(
				array( 'paperback' ) === bhp_book_available_formats(),
				'§3 ⭐ the THEME helper bhp_book_available_formats() agrees with the plugin (one source of truth)',
				$failures
			);
		} else {
			bhp_pbo_skip( '§3 the theme-helper agreement assertion', 'bhp_book_available_formats() is not defined; the theme is older than 1.19.240', $skips );
		}
		if ( function_exists( 'bhp_book_hardcover_is_offerable' ) ) {
			bhp_pbo_assert( false === bhp_book_hardcover_is_offerable(), '§3 the THEME predicate agrees: hardcover not offerable', $failures );
		}

		/*
		 * ⭐ THE COLLECTION CROSS-SELL A FLAGGED PARENT SEES IS THE PAPERBACK
		 *    SET. This is the surface `commerce-cx` reported as showing $48.99.
		 */
		if ( function_exists( 'bhp_book_collection_data' ) ) {
			$bhp_pbo_coll = bhp_book_collection_data();
			bhp_pbo_assert(
				isset( $bhp_pbo_coll['format'] ) && 'paperback' === $bhp_pbo_coll['format'],
				'§3 ⭐ the product-page/shop COLLECTION card resolves to the PAPERBACK collection on a flagged session',
				$failures
			);
		}

		/*
		 * ⭐⭐ THE ONE RESOLVER. `bhp_book_incoming_format()` must NOT answer
		 *     "hardcover" on a flagged session even when the URL says so.
		 *
		 * ⛔ THIS ASSERTION EXISTS BECAUSE THE CASE IT COVERS WAS FOUND LEAKING
		 *    IN BROWSER QA on 2026-08-18, AFTER the format cards were already
		 *    restricted: /product/…-hardcover/ 301s to the canonical page with
		 *    `?bhp_format=hardcover`, and the product-page COLLECTION UPSELL
		 *    (inc/audit-remediation.php), which reads this resolver rather than
		 *    the cards, still rendered a submittable "Add the Complete Hardcover
		 *    Collection" button posting `complete_hardcover_smart`. Restricting
		 *    the resolver closes that consumer and every other one at once.
		 */
		if ( function_exists( 'bhp_book_incoming_format' ) ) {
			$bhp_pbo_get_before = isset( $_GET['bhp_format'] ) ? $_GET['bhp_format'] : null; // phpcs:ignore
			$_GET['bhp_format'] = 'hardcover';
			$bhp_pbo_incoming = bhp_book_incoming_format();
			$bhp_pbo_incoming_raw = function_exists( 'bhp_book_incoming_format_unrestricted' ) ? bhp_book_incoming_format_unrestricted() : 'n/a';
			if ( null === $bhp_pbo_get_before ) {
				unset( $_GET['bhp_format'] );
			} else {
				$_GET['bhp_format'] = $bhp_pbo_get_before; // phpcs:ignore
			}
			bhp_pbo_assert(
				'paperback' === $bhp_pbo_incoming,
				'§3 ⭐⭐ bhp_book_incoming_format() returns PAPERBACK on a flagged session even with ?bhp_format=hardcover (got "' . $bhp_pbo_incoming . '")',
				$failures
			);
			bhp_pbo_assert(
				'hardcover' === $bhp_pbo_incoming_raw,
				'§3 the UNRESTRICTED resolver still reports the raw URL intent ("' . $bhp_pbo_incoming_raw . '") so nothing lost the ability to see it',
				$failures
			);
		}

		/*
		 * ⭐ THE COLLECTION PAGE'S OWN PILL ORDER.
		 */
		if ( function_exists( 'bhp_bundle_landing_format_order' ) ) {
			bhp_pbo_assert(
				array( 'paperback' ) === bhp_bundle_landing_format_order(),
				'§3 ⭐ the COLLECTION PAGE renders paperback pills only (' . implode( ',', bhp_bundle_landing_format_order() ) . ')',
				$failures
			);
		}

		/* =====================================================================
		 * §4 — ⭐⭐ THE SERVER REFUSES. THE ADD SEAMS, WITH THE REAL HOOKS.
		 * ===================================================================== */
		echo "\n--- §4 flagged path: the server-side refusal (add) ---\n";

		foreach ( $bhp_pbo_expected_hc as $bhp_pbo_hc_id ) {
			bhp_pbo_assert(
				false === bhp_school_visit_block_hardcover_add( true, $bhp_pbo_hc_id, 1, 0 ),
				"§4 ⭐ SEAM 1 REFUSES hardcover {$bhp_pbo_hc_id} on a flagged session",
				$failures
			);
		}
		foreach ( $bhp_pbo_pb_ids as $bhp_pbo_pb ) {
			bhp_pbo_assert(
				true === bhp_school_visit_block_hardcover_add( true, $bhp_pbo_pb['product_id'], 1, $bhp_pbo_pb['variation_id'] ),
				"§4 ⛔ SEAM 1 still ALLOWS paperback {$bhp_pbo_pb['product_id']} on a flagged session (the whole point is that they CAN buy)",
				$failures
			);
		}

		/*
		 * ⭐ SEAM 2, THROUGH THE REAL HOOK. `do_action()` is fired rather than
		 *    the function being called directly, so a lost `add_action` line
		 *    fails this assertion.
		 */
		if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
			$bhp_pbo_hc_product = wc_get_product( $bhp_pbo_expected_hc[0] );
			$bhp_pbo_threw      = false;
			$bhp_pbo_thrown_msg = '';
			try {
				do_action( 'woocommerce_store_api_validate_add_to_cart', $bhp_pbo_hc_product, null );
			} catch ( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e ) {
				$bhp_pbo_threw      = true;
				$bhp_pbo_thrown_msg = $e->getMessage();
			}
			bhp_pbo_assert( $bhp_pbo_threw, '§4 ⭐⭐ SEAM 2 THROWS a RouteException on a Store API hardcover add (fired through the real hook)', $failures );
			bhp_pbo_assert(
				$bhp_pbo_thrown_msg === bhp_school_visit_paperback_only_message(),
				'§4 SEAM 2 carries the ONE shared message, not a second wording',
				$failures
			);

			$bhp_pbo_pb_product = wc_get_product( $bhp_pbo_pb_ids[0]['variation_id'] ? $bhp_pbo_pb_ids[0]['variation_id'] : $bhp_pbo_pb_ids[0]['product_id'] );
			$bhp_pbo_pb_threw   = false;
			try {
				do_action( 'woocommerce_store_api_validate_add_to_cart', $bhp_pbo_pb_product, null );
			} catch ( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e ) {
				$bhp_pbo_pb_threw = true;
			}
			bhp_pbo_assert( ! $bhp_pbo_pb_threw, '§4 ⛔ SEAM 2 does NOT throw for a PAPERBACK on a flagged session', $failures );
		} else {
			bhp_pbo_skip( '§4 the SEAM 2 assertions', 'the Store API RouteException class is not available on this environment', $skips );
		}

		/* =====================================================================
		 * §5 — ⭐⭐ THE STALE CART. A REAL SYNTHETIC CART, CLEANED UP.
		 *
		 * This is the case UI hiding can never reach: the hardcover was added
		 * LEGALLY, in an ordinary session, BEFORE the school link was clicked.
		 * The add hooks have already run and passed. Only a cart-level gate
		 * stops it, and only the STORE API cart-level gate stops it on the
		 * checkout this store actually renders.
		 * ===================================================================== */
		echo "\n--- §5 flagged path: the STALE CART (synthetic cart, real hooks) ---\n";

		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( ! WC()->cart ) {
			bhp_pbo_skip( '§5 the stale-cart section', 'no WooCommerce cart could be loaded in CLI', $skips );
		} else {
			$bhp_pbo_cart_was = WC()->cart->get_cart_contents_count();
			WC()->cart->empty_cart();

			/*
			 * ⭐ THE HARDCOVER IS ADDED WITH THE RESTRICTION LIFTED, which is
			 *    exactly what an ordinary session is. Lifting it through the
			 *    plugin's own filter rather than by clearing the session flag
			 *    keeps the visit flag (and therefore the pickup method and the
			 *    delivery framing) exactly as a real stale-cart parent has it.
			 */
			add_filter( 'bhp_school_visit_paperback_only', '__return_false', 99 );
			$bhp_pbo_added = WC()->cart->add_to_cart( $bhp_pbo_expected_hc[0], 1 );
			remove_filter( 'bhp_school_visit_paperback_only', '__return_false', 99 );

			bhp_pbo_assert( (bool) $bhp_pbo_added, "§5 a hardcover ({$bhp_pbo_expected_hc[0]}) was added to a synthetic cart while unrestricted, as an ordinary session would", $failures );
			bhp_pbo_assert( true === bhp_school_visit_paperback_only(), '§5 the restriction is back ON after the filter was removed (the parent has now clicked the school link)', $failures );
			bhp_pbo_assert( true === bhp_school_visit_cart_has_hardcover( WC()->cart ), '§5 the cart-level detector sees the hardcover', $failures );

			/*
			 * ⭐⭐ SEAM 3, THROUGH THE REAL HOOK, WITH A REAL WP_Error BAG.
			 *     `CartController::validate_cart()` throws a 409
			 *     InvalidCartException when this bag is non-empty, and the
			 *     Checkout route calls validate_cart() BEFORE processing an
			 *     order. So this assertion is the checkout refusal.
			 */
			$bhp_pbo_errors = new WP_Error();
			do_action( 'woocommerce_store_api_cart_errors', $bhp_pbo_errors, WC()->cart );
			bhp_pbo_assert(
				in_array( 'bhp_school_visit_paperback_only', $bhp_pbo_errors->get_error_codes(), true ),
				'§5 ⭐⭐ SEAM 3 ADDS A CART ERROR — the Blocks checkout REFUSES a flagged cart holding a hardcover',
				$failures
			);
			bhp_pbo_assert(
				$bhp_pbo_errors->get_error_message( 'bhp_school_visit_paperback_only' ) === bhp_school_visit_paperback_only_message(),
				'§5 SEAM 3 carries the ONE shared message',
				$failures
			);

			/* SEAM 4, the classic surface, same cart. */
			if ( function_exists( 'wc_clear_notices' ) ) {
				wc_clear_notices();
			}
			do_action( 'woocommerce_check_cart_items' );
			$bhp_pbo_classic_errors = function_exists( 'wc_get_notices' ) ? wc_get_notices( 'error' ) : array();
			$bhp_pbo_classic_text   = '';
			foreach ( $bhp_pbo_classic_errors as $bhp_pbo_n ) {
				$bhp_pbo_classic_text .= is_array( $bhp_pbo_n ) && isset( $bhp_pbo_n['notice'] ) ? $bhp_pbo_n['notice'] : (string) $bhp_pbo_n;
			}
			bhp_pbo_assert(
				false !== strpos( $bhp_pbo_classic_text, 'paperback' ),
				'§5 SEAM 4 adds the classic cart/checkout error too',
				$failures
			);
			if ( function_exists( 'wc_clear_notices' ) ) {
				wc_clear_notices();
			}

			/*
			 * ⛔ THE CART WAS NOT SILENTLY REPAIRED. The rule is "refuse and
			 *    explain", never "change the customer's cart for them". If a
			 *    future edit starts stripping items, this fails.
			 */
			bhp_pbo_assert(
				true === bhp_school_visit_cart_has_hardcover( WC()->cart ),
				'§5 ⛔ the hardcover is STILL IN THE CART — nothing was silently removed on the customer\'s behalf',
				$failures
			);

			/*
			 * ⭐⭐ §5b — THE BUNDLE-FORM ROUTE. `/book-bundles/`,
			 *     `/shop-the-series/`, the homepage band and the four funnel
			 *     pages ALL add through `bhp_bundle_add_titles_to_cart()`, which
			 *     calls `WC()->cart->add_to_cart()` and therefore passes through
			 *     SEAM 1. That is a code-path argument, and a code-path argument
			 *     is an INFERENCE until it is run. This runs it: the complete
			 *     HARDCOVER set is pushed at a flagged cart through the real
			 *     function, and the cart must stay empty.
			 */
			WC()->cart->empty_cart();
			if ( function_exists( 'wc_clear_notices' ) ) {
				wc_clear_notices();
			}
			if ( function_exists( 'bhp_bundle_add_titles_to_cart' ) && function_exists( 'bhp_bundle_catalog' ) ) {
				$bhp_pbo_cat = bhp_bundle_catalog();
				bhp_bundle_add_titles_to_cart( 'hardcover', array_keys( $bhp_pbo_cat['hardcover'] ) );
				bhp_pbo_assert(
					0 === (int) WC()->cart->get_cart_contents_count(),
					'§5b ⭐⭐ the BUNDLE-FORM route (bhp_bundle_add_titles_to_cart hardcover x3) adds NOTHING on a flagged session',
					$failures
				);
				if ( function_exists( 'wc_clear_notices' ) ) {
					wc_clear_notices();
				}
				bhp_bundle_add_titles_to_cart( 'paperback', array_keys( $bhp_pbo_cat['paperback'] ) );
				bhp_pbo_assert(
					3 <= (int) WC()->cart->get_cart_contents_count(),
					'§5b ⛔ and the PAPERBACK collection STILL adds on a flagged session (' . WC()->cart->get_cart_contents_count() . ' items) - this is the order the parent is meant to place',
					$failures
				);
				WC()->cart->empty_cart();
				if ( function_exists( 'wc_clear_notices' ) ) {
					wc_clear_notices();
				}
			} else {
				bhp_pbo_skip( '§5b the bundle-form route assertions', 'bhp_bundle_add_titles_to_cart() is not available', $skips );
			}

			/*
			 * Rebuild the stale hardcover cart for §6, which needs it.
			 */
			add_filter( 'bhp_school_visit_paperback_only', '__return_false', 99 );
			WC()->cart->add_to_cart( $bhp_pbo_expected_hc[0], 1 );
			remove_filter( 'bhp_school_visit_paperback_only', '__return_false', 99 );
			bhp_pbo_assert(
				true === bhp_school_visit_cart_has_hardcover( WC()->cart ),
				'§5b the stale hardcover cart is rebuilt for the §6 control check',
				$failures
			);

			/* =====================================================================
			 * §6 — ⛔ THE SAME STALE CART, UNFLAGGED, IS FINE.
			 * ===================================================================== */
			echo "\n--- §6 control path: the SAME hardcover cart with no visit flag ---\n";

			$bhp_pbo_saved_slug = WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY );
			WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );

			bhp_pbo_assert( false === bhp_school_visit_paperback_only(), '§6 with the flag cleared the restriction is OFF again', $failures );

			$bhp_pbo_ctrl_errors = new WP_Error();
			do_action( 'woocommerce_store_api_cart_errors', $bhp_pbo_ctrl_errors, WC()->cart );
			bhp_pbo_assert(
				! in_array( 'bhp_school_visit_paperback_only', $bhp_pbo_ctrl_errors->get_error_codes(), true ),
				'§6 ⛔⛔ AN ORDINARY SHOPPER WITH THE IDENTICAL HARDCOVER CART IS NOT BLOCKED',
				$failures
			);
			bhp_pbo_assert(
				true === bhp_school_visit_block_hardcover_add( true, $bhp_pbo_expected_hc[0], 1, 0 ),
				'§6 ⛔ and can still ADD a hardcover',
				$failures
			);

			WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_pbo_saved_slug );

			/* ⛔ CLEAN UP THE SYNTHETIC CART. */
			WC()->cart->empty_cart();
			bhp_pbo_assert( 0 === (int) WC()->cart->get_cart_contents_count(), '§6 ⛔ the synthetic test cart is emptied afterwards', $failures );
			if ( $bhp_pbo_cart_was > 0 ) {
				echo "NOTE: the CLI request's cart held {$bhp_pbo_cart_was} item(s) before this suite ran; it is a per-request CLI cart, not a customer's.\n";
			}
		}
	}
}

/* =====================================================================
 * §7 — ⛔ THE VOICE RULE (standing rule §9.1)
 * ===================================================================== */
echo "\n--- §7 the §9.1 voice rule on the strings THIS build adds ---\n";

/*
 * ⭐ THE NOTE IS THE ONE CAPTURED FROM THE REAL FLAGGED SESSION IN §3, not a
 *    literal restated here. A copy of a customer-facing string inside its own
 *    test is a copy that can agree with itself while disagreeing with the site.
 *    If §3 skipped, the note is not asserted and that is said out loud.
 */
$bhp_pbo_strings = array( 'the refusal message' => bhp_school_visit_paperback_only_message() );
if ( isset( $bhp_pbo_note ) && '' !== $bhp_pbo_note ) {
	$bhp_pbo_strings['the selector note'] = $bhp_pbo_note;
} else {
	bhp_pbo_skip( '§7 the selector-note voice assertions', 'no flagged session was available in §3, so no real note string was captured', $skips );
}

foreach ( $bhp_pbo_strings as $bhp_pbo_what => $bhp_pbo_str ) {
	bhp_pbo_assert( '' !== $bhp_pbo_str, "§7 {$bhp_pbo_what} is non-empty", $failures );
	bhp_pbo_assert( ! bhp_pbo_has_company_we( $bhp_pbo_str ), "§7 ⛔ {$bhp_pbo_what} contains NO company \"we/us/our\" (§9.1)", $failures );
	bhp_pbo_assert( false === strpos( $bhp_pbo_str, "\xE2\x80\x94" ), "§7 ⛔ {$bhp_pbo_what} contains NO em dash", $failures );
	bhp_pbo_assert( false !== stripos( $bhp_pbo_str, 'paperback' ), "§7 {$bhp_pbo_what} actually says \"paperback\"", $failures );
}
bhp_pbo_assert(
	false !== stripos( bhp_school_visit_paperback_only_message(), ' I ' ) || 0 === stripos( bhp_school_visit_paperback_only_message(), 'I ' ),
	'§7 the refusal message speaks in the founder\'s first person',
	$failures
);

/*
 * ⭐ AND THE CHECKOUT PICKUP NOTICE NOW SAYS "paperbacks". A parent finds out
 *    on the last screen before payment, not at the read aloud.
 */
if ( '' !== $bhp_pbo_live_slug && function_exists( 'bhp_school_pickup_location_details' ) && function_exists( 'bhp_school_visit_resolve' ) ) {
	$bhp_pbo_details = bhp_school_pickup_location_details( bhp_school_visit_resolve( $bhp_pbo_live_slug ) );
	bhp_pbo_assert(
		false !== strpos( $bhp_pbo_details, 'signed paperbacks' ),
		'§7 ⭐ the CHECKOUT pickup notice says "signed paperbacks", not "signed books"',
		$failures
	);
	bhp_pbo_assert(
		false !== strpos( $bhp_pbo_details, 'Nothing is posted' ),
		'§7 ⛔ and the rest of the approved 2026-08-17 sentence is intact',
		$failures
	);
	bhp_pbo_assert(
		false === strpos( $bhp_pbo_details, "\xE2\x80\x94" ),
		'§7 ⛔ the pickup notice still contains no em dash',
		$failures
	);
} else {
	bhp_pbo_skip( '§7 the checkout pickup notice assertions', 'no live visit to resolve a location record from', $skips );
}

/* =====================================================================
 * §8 — ⛔ NOTHING WAS WRITTEN
 * ===================================================================== */
echo "\n--- §8 ⛔ no setting, price, stock status or registry row was written ---\n";

$bhp_pbo_opt_after = array(
	'woocommerce_pickup_location_settings' => bhp_pbo_raw_option( 'woocommerce_pickup_location_settings' ),
	'pickup_location_pickup_locations'     => bhp_pbo_raw_option( 'pickup_location_pickup_locations' ),
	'woocommerce_flat_rate_1_settings'     => bhp_pbo_raw_option( 'woocommerce_flat_rate_1_settings' ),
	'bhp_school_visits'                    => bhp_pbo_raw_option( 'bhp_school_visits' ),
);
/*
 * ⭐ AND THE READ FILTER IS ASSERTED TO STILL BE DOING ITS JOB, because the
 *    switch to a raw read must not quietly stop this suite noticing that the
 *    flagged request DOES see a pickup location. That divergence is the
 *    designed behaviour, and it is evidence, not noise.
 */
bhp_pbo_assert(
	bhp_pbo_raw_option( 'woocommerce_pickup_location_settings' ) !== wp_json_encode( get_option( 'woocommerce_pickup_location_settings' ) )
	|| ! bhp_school_visit_paperback_only(),
	'§8 ⭐ the pickup options are FILTERED ON READ, never written (raw row differs from the filtered read on a flagged request)',
	$failures
);
foreach ( $bhp_pbo_opt_before as $bhp_pbo_key => $bhp_pbo_val ) {
	bhp_pbo_assert(
		$bhp_pbo_val === $bhp_pbo_opt_after[ $bhp_pbo_key ],
		"§8 ⛔ option `{$bhp_pbo_key}` is byte-identical before and after",
		$failures
	);
}
foreach ( $bhp_pbo_stock_before as $bhp_pbo_hc_id => $bhp_pbo_stock ) {
	bhp_pbo_assert(
		$bhp_pbo_stock === get_post_meta( $bhp_pbo_hc_id, '_stock_status', true ),
		"§8 ⛔ hardcover {$bhp_pbo_hc_id} `_stock_status` unchanged ({$bhp_pbo_stock}) — this build restricts a SESSION, never a product",
		$failures
	);
	bhp_pbo_assert(
		'instock' === $bhp_pbo_stock,
		"§8 ⛔⛔ hardcover {$bhp_pbo_hc_id} is STILL IN STOCK and purchasable by ordinary shoppers",
		$failures
	);
}

/* =====================================================================
 * §9 — TEARDOWN
 * ===================================================================== */
echo "\n--- §9 teardown ---\n";

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
	bhp_pbo_assert( null === WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY ), '§9 the visit session flag is cleared', $failures );
}
if ( function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();
	bhp_pbo_assert( 0 === (int) WC()->cart->get_cart_contents_count(), '§9 the cart is empty', $failures );
}
if ( function_exists( 'wc_clear_notices' ) ) {
	wc_clear_notices();
}
bhp_pbo_assert( false === bhp_school_visit_paperback_only(), '§9 the restriction predicate is FALSE again at teardown', $failures );

echo "\n=== RESULT ===\n";
if ( $skips ) {
	echo count( $skips ) . " skipped:\n";
	foreach ( $skips as $s ) {
		echo "  SKIP {$s}\n";
	}
}
if ( $failures ) {
	echo count( $failures ) . " FAILED:\n";
	foreach ( $failures as $f ) {
		echo "  {$f}\n";
	}
	exit( 1 );
}
echo "ALL ASSERTIONS PASSED\n";
exit( 0 );
