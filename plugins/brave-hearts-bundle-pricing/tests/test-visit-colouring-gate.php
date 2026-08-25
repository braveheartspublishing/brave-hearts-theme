<?php
/**
 * CYCLE165-LD-VISIT-COLOURING-GATE (carrier item 217) — A VISIT-FLAGGED
 * SESSION IS **CHAPTER PAPERBACKS ONLY**.
 *
 * Run via WP-CLI, from the WordPress document root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-visit-colouring-gate.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔⛔ THE CONTROL PATH. An ordinary shopper must still be able to buy the
 *      colouring book, the pair offer, and a hardcover. The colouring book is
 *      LIVE AND PURCHASABLE on production (618, `publish`, `instock`, $12.99,
 *      verified read-only over SSH 2026-08-21) and this build must not touch
 *      that for anybody without a school-visit flag. A regression here hits
 *      every customer; the bug being fixed hits the parents of three schools.
 *      §2 and §7.
 *
 *   2. ⭐⭐ THE SERVER-SIDE REFUSAL IS REAL, NOT A HIDDEN BUTTON. A parent can
 *      add a colouring book in a normal session and only THEN click a school
 *      link, or use a stale `?add-to-cart=` URL, or drive the Store API
 *      directly, which renders no UI at all. §4 exercises the real hooks and
 *      §5 puts a REAL colouring book in a REAL cart and asserts the cart-level
 *      seam refuses it.
 *
 *   3. ⭐ THE PANEL DOES NOT OFFER WHAT THE GATE REFUSES. §3. Offering the
 *      colouring book in a flagged cart panel, then refusing the click, is the
 *      incoherence this build exists to remove.
 *
 *   4. ⛔ THE ID IS NEVER HARDCODED. §1 asserts the refused colouring id is
 *      READ FROM `bhp_colouring_product_ids()`. ⭐ THIS IS THE ASSERTION THAT
 *      MATTERS MOST FOR PORTABILITY: the dispatch named "product 618", which
 *      is the PRODUCTION id. The same book is 4065 ON STAGING. A hardcoded 618
 *      would enforce nothing here and this whole suite would pass vacuously.
 *
 *   5. ⛔ THE VOICE RULE. Standing rule §9.1: no "we"/"us"/"our" standing for
 *      the company in customer-facing words. Asserted on the one string this
 *      build moves, and only that. §6.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO stock status, NO coupon, NO
 *    shipping, tax, pickup or payment setting, on any environment. §8 re-reads
 *    the raw `wp_options` rows before and after and asserts they are identical.
 * ⛔ It places NO order, delivers NO webhook and takes NO payment.
 * ⛔ It touches NO visit registry row. It reads the registry and uses a REAL
 *    live visit; if there is not one, §3 to §5 SKIP with the reason printed,
 *    because a vacuous pass on the load-bearing assertion is worse than a skip.
 * ⚠ It DOES set one key in the WooCommerce session and DOES put items in the
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

function bhp_cg_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_cg_skip( $label, $reason, array &$skips ) {
	echo "SKIP: {$label} -- {$reason}\n";
	$skips[] = $label;
}

/**
 * Customer-facing "we/us/our" detector (standing rule §9.1).
 *
 * Word-boundary matched, case-insensitive. Deliberately pointed ONLY at the
 * string this build moves — never swept across arbitrary copy, because "US"
 * the country abbreviation would trip it and because §9.1a forbids rewriting a
 * quoted third party.
 */
function bhp_cg_has_company_we( $text ) {
	return (bool) preg_match( '/\b(we|us|our|ours)\b/i', (string) $text );
}

/**
 * ⛔ READ THE RAW STORED ROW, NOT `get_option()`. `school-visit-pickup.php`
 *    FILTERS the READS of two pickup options for the duration of one flagged
 *    request, so `get_option()` legitimately differs before and after the flag
 *    is set and a comparison built on it would report a change where no byte
 *    moved on disk. The question here is "did anything get WRITTEN".
 */
function bhp_cg_raw_option( $name ) {
	global $wpdb;
	$val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ) ); // phpcs:ignore WordPress.DB
	return null === $val ? 'ABSENT' : (string) $val;
}

echo "\n=== CYCLE165 item 217 visit colouring gate — bundle plugin "
	. ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '?' )
	. " / theme " . wp_get_theme()->get( 'Version' ) . " ===\n\n";

/* =====================================================================
 * §1 — PRECONDITIONS
 * ===================================================================== */
echo "--- §1 preconditions ---\n";

bhp_cg_assert( function_exists( 'bhp_school_visit_paperback_only' ), '§1 the session predicate is present', $failures );
bhp_cg_assert( function_exists( 'bhp_school_visit_is_colouring' ), '§1 ⭐ the per-item COLOURING test is exposed', $failures );
bhp_cg_assert( function_exists( 'bhp_school_visit_is_refused_item' ), '§1 ⭐⭐ the ONE product-level refusal predicate is exposed', $failures );
bhp_cg_assert( function_exists( 'bhp_school_visit_cart_has_refused_item' ), '§1 the cart-level refusal test is exposed', $failures );
bhp_cg_assert( function_exists( 'bhp_offer_is_offerable' ), '§1 ⭐ the offer PRESENTATION predicate is exposed', $failures );
bhp_cg_assert( function_exists( 'bhp_colouring_product_ids' ), '§1 the colouring registry is present', $failures );

/*
 * ⛔ THE HOOKS ARE ASSERTED REGISTERED, not assumed. A file that loads but
 *    whose add_filter line was lost in an edit would otherwise pass every
 *    functional assertion below by being called directly, while enforcing
 *    nothing on a real request.
 */
bhp_cg_assert(
	false !== has_filter( 'woocommerce_add_to_cart_validation', 'bhp_school_visit_block_hardcover_add' ),
	'§1 SEAM 1 registered (classic add)',
	$failures
);
bhp_cg_assert(
	false !== has_action( 'woocommerce_store_api_validate_add_to_cart', 'bhp_school_visit_block_hardcover_store_api_add' ),
	'§1 SEAM 2 registered (Store API add)',
	$failures
);
bhp_cg_assert(
	false !== has_action( 'woocommerce_store_api_cart_errors', 'bhp_school_visit_hardcover_store_api_cart_error' ),
	'§1 SEAM 3 registered (Store API cart/checkout — the stale-cart stop)',
	$failures
);
bhp_cg_assert(
	false !== has_action( 'woocommerce_check_cart_items', 'bhp_school_visit_hardcover_classic_cart_error' ),
	'§1 SEAM 4 registered (classic cart/checkout)',
	$failures
);
bhp_cg_assert(
	false !== has_filter( 'woocommerce_add_cart_item_data', 'bhp_school_visit_block_hardcover_cart_add' ),
	'§1 SEAM 5 registered (WC_Cart::add_to_cart itself)',
	$failures
);

/*
 * ⭐⭐ THE ID SET, READ FROM THE REGISTRY. This is the assertion that makes the
 *     whole suite portable between staging (4065) and production (618).
 */
$bhp_cg_col_map = function_exists( 'bhp_colouring_product_ids' ) ? bhp_colouring_product_ids() : array();
$bhp_cg_col_ids = array_values( array_map( 'intval', $bhp_cg_col_map ) );

bhp_cg_assert(
	! empty( $bhp_cg_col_ids ),
	'§1 ⭐⭐ the colouring registry RESOLVES on this environment (ids: ' . implode( ',', $bhp_cg_col_ids ) . ')',
	$failures
);

/*
 * ⛔ AND IT RESOLVED BY SKU, NOT BY A LITERAL. Asserted by re-resolving the
 *    catalogue's own SKU independently and requiring the same id back. If a
 *    future edit hardcodes an id, these two disagree on one environment.
 */
if ( function_exists( 'bhp_colouring_catalog' ) && function_exists( 'wc_get_product_id_by_sku' ) ) {
	$bhp_cg_sku_ok = true;
	foreach ( bhp_colouring_catalog() as $bhp_cg_slug => $bhp_cg_info ) {
		if ( empty( $bhp_cg_info['sku'] ) || empty( $bhp_cg_col_map[ $bhp_cg_slug ] ) ) {
			continue;
		}
		if ( (int) wc_get_product_id_by_sku( $bhp_cg_info['sku'] ) !== (int) $bhp_cg_col_map[ $bhp_cg_slug ] ) {
			$bhp_cg_sku_ok = false;
		}
	}
	bhp_cg_assert( $bhp_cg_sku_ok, '§1 ⛔ every resolved colouring id came from its SKU, not a hardcoded literal', $failures );
}

if ( empty( $bhp_cg_col_ids ) ) {
	bhp_cg_skip(
		'§2 to §5 (every colouring assertion)',
		'no colouring product record resolves on this environment, so every assertion below would be vacuous',
		$skips
	);
}

/* The three chapter paperbacks, read from the catalog. */
$bhp_cg_catalog = function_exists( 'bhp_bundle_catalog' ) ? bhp_bundle_catalog() : array();
$bhp_cg_pb      = array();
foreach ( ( isset( $bhp_cg_catalog['paperback'] ) ? $bhp_cg_catalog['paperback'] : array() ) as $bhp_cg_ed ) {
	$bhp_cg_pb[] = array(
		'product_id'   => (int) $bhp_cg_ed['product_id'],
		'variation_id' => (int) $bhp_cg_ed['variation_id'],
	);
}
bhp_cg_assert( count( $bhp_cg_pb ) === 3, '§1 three CHAPTER paperback editions resolve from the catalog', $failures );

$bhp_cg_opt_before = array(
	'woocommerce_pickup_location_settings' => bhp_cg_raw_option( 'woocommerce_pickup_location_settings' ),
	'pickup_location_pickup_locations'     => bhp_cg_raw_option( 'pickup_location_pickup_locations' ),
	'woocommerce_flat_rate_1_settings'     => bhp_cg_raw_option( 'woocommerce_flat_rate_1_settings' ),
	'bhp_school_visits'                    => bhp_cg_raw_option( 'bhp_school_visits' ),
);
$bhp_cg_stock_before = array();
foreach ( $bhp_cg_col_ids as $bhp_cg_cid ) {
	$bhp_cg_stock_before[ $bhp_cg_cid ] = get_post_meta( $bhp_cg_cid, '_stock_status', true );
}

/* =====================================================================
 * §2 — ⛔⛔ THE CONTROL PATH IS UNCHANGED
 *
 * Every assertion here is about a visitor with NO visit flag, which is
 * every customer the store has. If this section fails, the build is
 * worse than the bug.
 * ===================================================================== */
echo "\n--- §2 control path (no visit session): the colouring book is FULLY PURCHASABLE ---\n";

bhp_cg_assert( false === bhp_school_visit_paperback_only(), '§2 the restriction predicate is FALSE with no visit session', $failures );

foreach ( $bhp_cg_col_ids as $bhp_cg_cid ) {
	bhp_cg_assert(
		true === bhp_school_visit_is_colouring( $bhp_cg_cid, 0 ),
		"§2 the colouring test RECOGNISES {$bhp_cg_cid} (recognition is flag-independent, as it must be)",
		$failures
	);
	bhp_cg_assert(
		true === bhp_school_visit_is_refused_item( $bhp_cg_cid, 0 ),
		"§2 the refusal predicate recognises colouring {$bhp_cg_cid}",
		$failures
	);
	/*
	 * ⭐⭐ THE ONE THAT MATTERS. Recognition is not refusal. SEAM 1 must ALLOW
	 *    this add for an ordinary shopper.
	 */
	bhp_cg_assert(
		true === bhp_school_visit_block_hardcover_add( true, $bhp_cg_cid, 1, 0 ),
		"§2 ⛔⛔ SEAM 1 ALLOWS colouring {$bhp_cg_cid} for an ordinary shopper",
		$failures
	);
	$bhp_cg_ctrl_data = bhp_school_visit_block_hardcover_cart_add( array( 'sentinel' => 1 ), $bhp_cg_cid, 0 );
	bhp_cg_assert(
		is_array( $bhp_cg_ctrl_data ) && isset( $bhp_cg_ctrl_data['sentinel'] ),
		"§2 ⛔⛔ SEAM 5 passes colouring {$bhp_cg_cid} through UNTOUCHED for an ordinary shopper",
		$failures
	);
}

/* ⛔ Both live offers must be offerable to an ordinary shopper. */
$bhp_cg_ctrl_offerable = array();
foreach ( array_keys( bhp_offer_catalog() ) as $bhp_cg_key ) {
	if ( bhp_offer_is_purchasable( $bhp_cg_key ) ) {
		$bhp_cg_ctrl_offerable[ $bhp_cg_key ] = bhp_offer_is_offerable( $bhp_cg_key );
	}
}
bhp_cg_assert(
	! empty( $bhp_cg_ctrl_offerable ) && ! in_array( false, $bhp_cg_ctrl_offerable, true ),
	'§2 ⛔⛔ EVERY purchasable offer is OFFERABLE to an ordinary shopper (' . implode( ',', array_keys( $bhp_cg_ctrl_offerable ) ) . ')',
	$failures
);
bhp_cg_assert(
	count( bhp_offer_drawer_payload() ) === count( $bhp_cg_ctrl_offerable ),
	'§2 ⛔ the cart-panel rail carries every offer for an ordinary shopper',
	$failures
);
bhp_cg_assert(
	count( bhp_offer_shop_add_payload() ) === count( $bhp_cg_ctrl_offerable ),
	'§2 ⛔ the shop add payload carries every offer for an ordinary shopper',
	$failures
);

/* =====================================================================
 * §3 — THE FLAGGED PATH: WHAT THE PANEL OFFERS
 * ===================================================================== */
echo "\n--- §3 flagged path: the panel must not offer what the gate refuses ---\n";

$bhp_cg_live_slug = '';
if ( function_exists( 'bhp_school_visit_records' ) && function_exists( 'bhp_school_visit_resolve' ) ) {
	foreach ( array_keys( bhp_school_visit_records() ) as $bhp_cg_slug2 ) {
		if ( bhp_school_visit_resolve( $bhp_cg_slug2 ) ) {
			$bhp_cg_live_slug = $bhp_cg_slug2;
			break;
		}
	}
}

$bhp_cg_flagged = false;

if ( empty( $bhp_cg_col_ids ) ) {
	bhp_cg_skip( '§3 to §5', 'no colouring record on this environment', $skips );
} elseif ( '' === $bhp_cg_live_slug ) {
	bhp_cg_skip( '§3 to §5 (the entire flagged path)', 'no live, non-expired visit exists in the registry, so a pass here would be vacuous', $skips );
} elseif ( ! function_exists( 'WC' ) ) {
	bhp_cg_skip( '§3 to §5 (the entire flagged path)', 'WooCommerce is not loaded', $skips );
} else {
	if ( ! WC()->session && is_callable( array( WC(), 'initialize_session' ) ) ) {
		WC()->initialize_session();
	}
	if ( ! WC()->session ) {
		bhp_cg_skip( '§3 to §5 (the entire flagged path)', 'no WooCommerce session could be initialised in CLI', $skips );
	} else {
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_cg_live_slug );
		$bhp_cg_flagged = true;

		bhp_cg_assert(
			true === bhp_school_visit_use_delivery_framing(),
			"§3 the harness holds a REAL live visit flag ({$bhp_cg_live_slug})",
			$failures
		);
		bhp_cg_assert( true === bhp_school_visit_paperback_only(), '§3 the restriction predicate is TRUE on a flagged session', $failures );

		/*
		 * ⭐⭐ THE PANEL. Every offer carrying a colouring component must be
		 *    withheld — which today is every live offer, because both pairs
		 *    are chapter book + colouring book.
		 */
		$bhp_cg_flag_offerable = array();
		foreach ( array_keys( bhp_offer_catalog() ) as $bhp_cg_key2 ) {
			if ( bhp_offer_is_purchasable( $bhp_cg_key2 ) ) {
				$bhp_cg_flag_offerable[ $bhp_cg_key2 ] = bhp_offer_is_offerable( $bhp_cg_key2 );
			}
		}
		$bhp_cg_still_offered = array_keys( array_filter( $bhp_cg_flag_offerable ) );
		bhp_cg_assert(
			empty( $bhp_cg_still_offered ),
			'§3 ⭐⭐ NO colouring/pair offer is offerable on a flagged session (still offered: ' . ( $bhp_cg_still_offered ? implode( ',', $bhp_cg_still_offered ) : 'none' ) . ')',
			$failures
		);

		/*
		 * ⛔ AND `is_purchasable()` IS UNCHANGED. This is the assertion that
		 *    proves the gate went in the PRESENTATION predicate and not in the
		 *    pricing one. If this flips, `bhp_offer_apply_fees()` has started
		 *    silently removing a discount from a flagged parent's cart.
		 */
		bhp_cg_assert(
			! empty( $bhp_cg_flag_offerable ),
			'§3 ⛔⛔ bhp_offer_is_purchasable() is UNTOUCHED on a flagged session (pricing must not move; the cart is STOPPED, not repriced)',
			$failures
		);

		bhp_cg_assert(
			array() === bhp_offer_drawer_payload(),
			'§3 ⭐⭐ the CART PANEL rail carries NO colouring offer on a flagged session',
			$failures
		);
		bhp_cg_assert(
			array() === bhp_offer_shop_add_payload(),
			'§3 ⭐ the shop add payload carries NO colouring offer on a flagged session',
			$failures
		);

		/* ⭐ The theme module that builds both the product cross-sell and the shop card. */
		if ( function_exists( 'bhp_offer_render_module' ) ) {
			$bhp_cg_mod_dirty = array();
			foreach ( array_keys( bhp_offer_catalog() ) as $bhp_cg_key3 ) {
				if ( '' !== bhp_offer_render_module( $bhp_cg_key3, 'bhp-offer--product' ) ) {
					$bhp_cg_mod_dirty[] = $bhp_cg_key3;
				}
			}
			bhp_cg_assert(
				empty( $bhp_cg_mod_dirty ),
				'§3 ⭐⭐ the PAIR CARD renders EMPTY on a flagged session, product page and shop grid alike (rendered: ' . ( $bhp_cg_mod_dirty ? implode( ',', $bhp_cg_mod_dirty ) : 'none' ) . ')',
				$failures
			);
		} else {
			bhp_cg_skip( '§3 the pair-card render assertion', 'bhp_offer_render_module() is not defined; the theme is older than 1.19.277', $skips );
		}

		/*
		 * ⛔ HAND DELIVERY IS INTACT. This build must not have disturbed the
		 *    thing the school-visit path exists for.
		 */
		if ( function_exists( 'bhp_school_pickup_totals_label' ) ) {
			bhp_cg_assert(
				false !== stripos( bhp_school_pickup_totals_label(), 'hand deliver' ),
				'§3 ⛔ HAND DELIVERY framing is intact on a flagged session ("' . bhp_school_pickup_totals_label() . '")',
				$failures
			);
		}

		/* =====================================================================
		 * §4 — ⭐⭐ THE SERVER REFUSES THE ADD. THE REAL HOOKS.
		 * ===================================================================== */
		echo "\n--- §4 flagged path: the server-side refusal (add) ---\n";

		foreach ( $bhp_cg_col_ids as $bhp_cg_cid2 ) {
			/*
			 * ⭐ SEAM 1 — the seam a stale `?add-to-cart=618` URL runs through.
			 *    This IS the adversarial case in the brief.
			 */
			bhp_cg_assert(
				false === bhp_school_visit_block_hardcover_add( true, $bhp_cg_cid2, 1, 0 ),
				"§4 ⭐⭐ SEAM 1 REFUSES colouring {$bhp_cg_cid2} on a flagged session (the adversarial ?add-to-cart= route)",
				$failures
			);

			/* ⭐ SEAM 5 — WC_Cart::add_to_cart() itself, which seam 1 never sees. */
			$bhp_cg_threw = false;
			$bhp_cg_msg   = '';
			try {
				bhp_school_visit_block_hardcover_cart_add( array(), $bhp_cg_cid2, 0 );
			} catch ( Exception $e ) {
				$bhp_cg_threw = true;
				$bhp_cg_msg   = $e->getMessage();
			}
			bhp_cg_assert(
				$bhp_cg_threw,
				"§4 ⭐ SEAM 5 REFUSES colouring {$bhp_cg_cid2} from inside WC_Cart::add_to_cart()",
				$failures
			);
			bhp_cg_assert(
				$bhp_cg_threw && false !== stripos( $bhp_cg_msg, 'chapter paperback' ),
				'§4 and it carries the ONE refusal sentence, not a generic error',
				$failures
			);

			/*
			 * ⭐ SEAM 2, THROUGH THE REAL HOOK. `do_action()` is fired rather
			 *    than the function called directly, so a lost `add_action`
			 *    line fails this assertion.
			 */
			if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
				$bhp_cg_prod = wc_get_product( $bhp_cg_cid2 );
				if ( $bhp_cg_prod ) {
					$bhp_cg_api_threw = false;
					$bhp_cg_api_msg   = '';
					try {
						do_action( 'woocommerce_store_api_validate_add_to_cart', $bhp_cg_prod, array() );
					} catch ( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e ) {
						$bhp_cg_api_threw = true;
						$bhp_cg_api_msg   = $e->getMessage();
					}
					bhp_cg_assert(
						$bhp_cg_api_threw,
						"§4 ⭐ SEAM 2 REFUSES colouring {$bhp_cg_cid2} over the Store API (the path with no UI at all)",
						$failures
					);
					bhp_cg_assert(
						$bhp_cg_api_threw && $bhp_cg_api_msg === bhp_school_visit_paperback_only_message(),
						'§4 and the Store API 400 carries the ONE sentence, byte for byte',
						$failures
					);
				}
			} else {
				bhp_cg_skip( '§4 SEAM 2 (Store API add)', 'RouteException class is not available in this context', $skips );
			}
		}

		/*
		 * ⛔⛔ AND THE CHAPTER PAPERBACKS STILL ADD. THE WHOLE POINT IS THAT A
		 *     FLAGGED PARENT CAN BUY. If this fails the school journey is dead.
		 */
		foreach ( $bhp_cg_pb as $bhp_cg_pbrow ) {
			bhp_cg_assert(
				true === bhp_school_visit_block_hardcover_add( true, $bhp_cg_pbrow['product_id'], 1, $bhp_cg_pbrow['variation_id'] ),
				"§4 ⛔⛔ SEAM 1 still ALLOWS chapter paperback {$bhp_cg_pbrow['product_id']} on a flagged session",
				$failures
			);
			$bhp_cg_pb_data = bhp_school_visit_block_hardcover_cart_add( array( 'sentinel' => 1 ), $bhp_cg_pbrow['product_id'], $bhp_cg_pbrow['variation_id'] );
			bhp_cg_assert(
				is_array( $bhp_cg_pb_data ) && isset( $bhp_cg_pb_data['sentinel'] ),
				"§4 ⛔⛔ SEAM 5 passes chapter paperback {$bhp_cg_pbrow['product_id']} through UNTOUCHED on a flagged session",
				$failures
			);
		}

		/* ⛔ And the offer add path refuses the pair with the same sentence. */
		if ( function_exists( 'bhp_offer_add_to_cart' ) ) {
			foreach ( array_keys( bhp_offer_catalog() ) as $bhp_cg_key4 ) {
				if ( ! bhp_offer_is_purchasable( $bhp_cg_key4 ) ) {
					continue;
				}
				$bhp_cg_added = bhp_offer_add_to_cart( $bhp_cg_key4 );
				bhp_cg_assert(
					0 === $bhp_cg_added,
					"§4 ⭐ the OFFER add path refuses '{$bhp_cg_key4}' outright on a flagged session (0 components added)",
					$failures
				);
			}
			WC()->cart->empty_cart();
		}

		/* =====================================================================
		 * §5 — ⭐⭐ THE STALE CART. A REAL COLOURING BOOK IN A REAL CART.
		 *
		 * The path seams 1, 2 and 5 cannot reach: added in an ORDINARY
		 * session, THEN the school link clicked. The add already happened,
		 * legally, before the flag existed.
		 * ===================================================================== */
		echo "\n--- §5 flagged path: the stale cart (a REAL cart, REAL hooks) ---\n";

		WC()->cart->empty_cart();

		/*
		 * ⛔ THE ITEM IS PUT IN THE CART WITH THE FLAG TEMPORARILY DOWN, which
		 *    is exactly the real-world sequence. Adding it while flagged would
		 *    be refused by seam 5 and §5 would then be testing an empty cart.
		 */
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
		$bhp_cg_stale_id = $bhp_cg_col_ids[0];
		WC()->cart->add_to_cart( $bhp_cg_stale_id, 1 );
		$bhp_cg_in_cart = ! WC()->cart->is_empty();
		bhp_cg_assert(
			$bhp_cg_in_cart,
			"§5 ⛔ the colouring book ({$bhp_cg_stale_id}) WAS addable in an ORDINARY session, so the control path is real",
			$failures
		);

		/* Now the parent clicks the school link. */
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $bhp_cg_live_slug );

		if ( $bhp_cg_in_cart ) {
			bhp_cg_assert(
				true === bhp_school_visit_cart_has_refused_item( WC()->cart ),
				'§5 ⭐ the cart-level predicate SEES the stale colouring book',
				$failures
			);

			/*
			 * ⭐⭐ SEAM 3 — the hard server-side stop on the REAL Blocks
			 *     checkout. `CartController::validate_cart()` fires this and
			 *     throws a 409 if anything was added to the bag.
			 */
			$bhp_cg_errors = new WP_Error();
			do_action( 'woocommerce_store_api_cart_errors', $bhp_cg_errors, WC()->cart );
			bhp_cg_assert(
				$bhp_cg_errors->has_errors(),
				'§5 ⭐⭐ SEAM 3 BLOCKS the Blocks cart/checkout while the flagged cart holds a colouring book',
				$failures
			);
			bhp_cg_assert(
				$bhp_cg_errors->get_error_message( 'bhp_school_visit_paperback_only' ) === bhp_school_visit_paperback_only_message(),
				'§5 and the 409 carries the ONE sentence, byte for byte',
				$failures
			);
		}

		WC()->cart->empty_cart();
	}
}

/* =====================================================================
 * §6 — ⛔ THE VOICE RULE AND THE COPY RAILS, ON THE ONE MOVED STRING
 * ===================================================================== */
echo "\n--- §6 the refusal sentence ---\n";

$bhp_cg_message = bhp_school_visit_paperback_only_message();
echo "    THE STRING: {$bhp_cg_message}\n";

bhp_cg_assert( ! bhp_cg_has_company_we( $bhp_cg_message ), '§6 ⛔ §9.1 no "we"/"us"/"our" in the refusal sentence', $failures );
bhp_cg_assert( false === strpos( $bhp_cg_message, '—' ), '§6 ⛔ no em dash', $failures );
bhp_cg_assert( false === strpos( $bhp_cg_message, '–' ), '§6 ⛔ no en dash', $failures );
bhp_cg_assert(
	0 === strpos( $bhp_cg_message, 'I ' ),
	'§6 ⛔ it is I-voice and opens in his own voice',
	$failures
);
bhp_cg_assert(
	false !== stripos( $bhp_cg_message, 'chapter paperbacks only' ),
	'§6 ⭐ it states the RULE THIS BUILD ENFORCES ("chapter paperbacks only"), not the superseded one',
	$failures
);
bhp_cg_assert(
	false !== stripos( $bhp_cg_message, 'coloring book' ),
	'§6 ⭐ it names the coloring book, so a refused parent is told what to do about the thing in their hand',
	$failures
);
/*
 * ⛔ AMERICAN SPELLING IN CUSTOMER-FACING COPY. The code says "colouring", the
 *    storefront says "coloring", and the book's own cover says "Coloring".
 *    A British spelling here would be the one place that disagreed with it.
 */
bhp_cg_assert(
	false === stripos( $bhp_cg_message, 'colouring' ),
	'§6 ⛔ the CUSTOMER-FACING spelling is "coloring", matching the product record and every other storefront string',
	$failures
);
/*
 * ⛔ NO OUTCOME CLAIM, NO URGENCY, NO INVENTED FACT. A blunt scan for the
 *    shapes the standing rules forbid, not a judgement of tone.
 */
foreach ( array( 'hurry', 'act now', 'limited time', 'guarantee', 'proven', 'sorry', 'unfortunately', '!' ) as $bhp_cg_bad ) {
	bhp_cg_assert(
		false === stripos( $bhp_cg_message, $bhp_cg_bad ),
		"§6 ⛔ no \"{$bhp_cg_bad}\" in the refusal sentence",
		$failures
	);
}

/* =====================================================================
 * §7 — ⛔⛔ THE CONTROL PATH, RE-ASSERTED AFTER EVERY FLAGGED PATH RAN
 *
 * The flag is cleared and the ordinary-shopper answers are demanded
 * again. This catches a gate that leaks into a static or a cached map.
 * ===================================================================== */
echo "\n--- §7 the control path, AFTER the flagged path (leak detection) ---\n";

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}

bhp_cg_assert( false === bhp_school_visit_paperback_only(), '§7 the restriction predicate is FALSE again once the flag is cleared', $failures );

foreach ( $bhp_cg_col_ids as $bhp_cg_cid3 ) {
	bhp_cg_assert(
		true === bhp_school_visit_block_hardcover_add( true, $bhp_cg_cid3, 1, 0 ),
		"§7 ⛔⛔ SEAM 1 ALLOWS colouring {$bhp_cg_cid3} again — no leak into an ordinary session",
		$failures
	);
}
$bhp_cg_after_offerable = array();
foreach ( array_keys( bhp_offer_catalog() ) as $bhp_cg_key5 ) {
	if ( bhp_offer_is_purchasable( $bhp_cg_key5 ) ) {
		$bhp_cg_after_offerable[ $bhp_cg_key5 ] = bhp_offer_is_offerable( $bhp_cg_key5 );
	}
}
bhp_cg_assert(
	$bhp_cg_after_offerable === $bhp_cg_ctrl_offerable,
	'§7 ⛔⛔ offer offerability is IDENTICAL to §2 for an ordinary shopper — the gate did not leak',
	$failures
);

/* =====================================================================
 * §8 — ⛔ NOTHING WAS WRITTEN
 * ===================================================================== */
echo "\n--- §8 no setting, price, stock or registry row was written ---\n";

foreach ( $bhp_cg_opt_before as $bhp_cg_name => $bhp_cg_val ) {
	bhp_cg_assert(
		bhp_cg_raw_option( $bhp_cg_name ) === $bhp_cg_val,
		"§8 ⛔ the raw wp_options row '{$bhp_cg_name}' is byte-identical to before this suite ran",
		$failures
	);
}
foreach ( $bhp_cg_stock_before as $bhp_cg_sid => $bhp_cg_sval ) {
	bhp_cg_assert(
		get_post_meta( $bhp_cg_sid, '_stock_status', true ) === $bhp_cg_sval,
		"§8 ⛔ colouring product {$bhp_cg_sid} stock status is unchanged ({$bhp_cg_sval})",
		$failures
	);
}

/* =====================================================================
 * §9 — CLEANUP
 * ===================================================================== */
echo "\n--- §9 cleanup ---\n";

if ( function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();
	echo "    cart emptied\n";
}
if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
	echo "    visit flag cleared from the CLI session\n";
}

/* =====================================================================
 * RESULT
 * ===================================================================== */
echo "\n=== RESULT ===\n";
if ( $skips ) {
	echo count( $skips ) . " SKIPPED:\n";
	foreach ( $skips as $bhp_cg_s ) {
		echo "  - {$bhp_cg_s}\n";
	}
}
if ( $failures ) {
	echo count( $failures ) . " FAILED:\n";
	foreach ( $failures as $bhp_cg_f ) {
		echo "  - {$bhp_cg_f}\n";
	}
	exit( 1 );
}
echo "ALL PASS\n";
