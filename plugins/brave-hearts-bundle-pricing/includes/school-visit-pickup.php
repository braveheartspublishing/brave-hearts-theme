<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * AUTHOR HAND-DELIVERY AT A SCHOOL VISIT — 1.8.49 (2026-08-17,
 * `CYCLE162-LD-SCHOOL-PICKUP`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, verbatim, RELAYED through the Chief of Staff and NOT
 * witnessed by this agent: *"Lets do path 3 - if we can get that done in the
 * next day or two we wont have to worry about shipping any books at all - so
 * the Path 3 starts now"*.
 *
 * WHAT IT DOES. A parent who arrives from a school's pre-visit link orders
 * and pays online exactly as anyone else does, and may choose FREE
 * "Author hand-delivery" instead of shipping. Andrew brings the signed books
 * to the school on the visit date.
 *
 * ---------------------------------------------------------------------------
 * THE PATH, END TO END, AND WHERE EACH PIECE LIVES
 * ---------------------------------------------------------------------------
 *   1. The pre-visit link carries `?bhp_visit=<slug>`.
 *   2. `bhp_school_visit_capture_intent()` (`template_redirect`, priority 5)
 *      turns that param into a WC SESSION FLAG holding ONLY THE SLUG.
 *   3. `bhp_school_pickup_tag_package()` puts the slug into the shipping
 *      package so WooCommerce's package hash changes and the rate cache
 *      cannot serve a pre-flag rate list. ⛔ WITHOUT THIS THE OPTION SIMPLY
 *      DOES NOT APPEAR for a visitor who saw checkout before clicking the
 *      link — `woocommerce_package_rates` is inside the cache-miss branch of
 *      `WC_Shipping::calculate_shipping_for_package()`, verified by reading
 *      WooCommerce 10.9.1's source on staging, not assumed.
 *   4. `bhp_school_pickup_inject_rate()` (`woocommerce_package_rates`,
 *      priority 25) ADDS a zero-cost rate beside the normal one.
 *   5. `bhp_school_pickup_mark_order()` writes the packing-list meta and a
 *      visible order note.
 *   6. `bhp_school_pickup_block_bookvault_webhook()` stops the order ever
 *      reaching the print partner. THIS IS THE SAFETY-CRITICAL ONE.
 *
 * ⭐ 1.8.50 (2026-08-17, `CYCLE162-LD-PICKUP-FIELDS`) EXTENDS THIS FILE from
 *    `school-visit-fields.php`: two checkout fields (the child's first name for
 *    the signed dedication, and an unchecked newsletter opt-in) that exist only
 *    for a flagged session. The ONLY change inside THIS file is that the
 *    packing-list note now carries a "SIGN TO:" clause. Read that file before
 *    changing the note, the meta keys or the session helpers.
 *
 * ⭐ 1.8.51 (2026-08-17, `CYCLE162-LD-VISITS-PAGE`) ADDS ONE OPTIONAL REGISTRY
 *    FIELD, `time`, and nothing else. It is a display string for the public
 *    `/author-visits/` page (theme `page-author-visits.php` +
 *    `inc/author-visits.php`). ⛔ NOTHING ABOUT CHECKOUT CHANGED: the shipping
 *    label, the injected rate, the order meta, the packing-list note and the
 *    Bookvault skip are byte-identical to 1.8.50, and the time is deliberately
 *    NOT added to the checkout label — a label a parent reads while paying is
 *    approved copy, and this build had no approval to reword it.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE DUPLICATE-PRINT PROTECTION — READ THIS BEFORE CHANGING ANYTHING
 * ---------------------------------------------------------------------------
 * A hand-delivered order must NEVER reach Bookvault. If it does, Andrew pays
 * to print and post a book he is also carrying to the school by hand.
 *
 * ⭐ HOW THE ORDER ACTUALLY REACHES BOOKVAULT, established by reading the
 *    installed `bookvault` plugin 6.0.0 and the live webhook table on staging
 *    on 2026-08-17 — NOT from documentation, which did not record it:
 *
 *      · The plugin itself has NO automatic push. Its only outbound order
 *        call is `bvlt_resendOrder()`, reachable solely from the wp-admin
 *        single-order action and the orders-list bulk action.
 *      · The automatic push is a pair of ordinary WOOCOMMERCE WEBHOOKS —
 *        "BV Order Creation" (`woocommerce_new_order`) and "BV Order Update"
 *        (`woocommerce_update_order`, `woocommerce_order_refunded`) — posting
 *        the whole order payload to `webhooks.bookvault.app`.
 *
 *    So the skip belongs on the webhook delivery decision, and that is where
 *    it is: `woocommerce_webhook_should_deliver`, the filter
 *    `WC_Webhook::should_deliver()` returns through and `WC_Webhook::process()`
 *    checks before doing anything at all.
 *
 * ⭐ IT FAILS SAFE IN BOTH DIRECTIONS, DELIBERATELY:
 *      · Pickup-ness is decided from the ORDER'S OWN SHIPPING LINE first and
 *        the meta second, so a failed meta write cannot let an order through.
 *      · The webhook is matched by DELIVERY-URL HOST, so a renamed,
 *        re-created or duplicated Bookvault webhook is still caught, and a
 *        non-Bookvault webhook (Rutter) is not touched.
 *      · Anything it cannot parse is treated as Bookvault-bound if the host
 *        matches. It never guesses "probably fine".
 *
 * ⭐ SECOND LAYER: `bhp_school_pickup_remove_resend_action()` removes the
 *    Bookvault plugin's own "Resend order to Bookvault" admin action from a
 *    pickup order, so the manual route cannot be clicked by accident either.
 *    ⚠ IT DOES NOT AND CANNOT COVER THE ORDERS-LIST BULK ACTION, which takes
 *      post IDs and never consults per-order state. That is stated, not hidden.
 *
 * ⛔ NOTHING HERE MODIFIES THE `bookvault` PLUGIN. It is a third-party plugin
 *    and is left byte-untouched.
 *
 * ---------------------------------------------------------------------------
 * ⛔ RAILS THIS FILE DOES NOT CROSS
 * ---------------------------------------------------------------------------
 * ⛔ It touches NO WooCommerce zone, NO shipping-method setting, NO
 *    `flat_rate` setting, NO coupon, NO product, NO price, NO tax, NO payment
 *    setting, on any environment. It ADDS a rate to one filtered array at
 *    request time and nothing else.
 * ⛔ It never adds "BookVAULT Shipping" to anything. The theme's
 *    `bhp_remove_bookvault_live_shipping_rates()` (priority 10) still strips
 *    that method; this file runs at 25 and adds a rate of its own with a
 *    different `method_id`.
 * ⛔ NO VISIT DATA IS HARDCODED. Every school name, date and cutoff is read
 *    from the per-environment `bhp_school_visits` site option, seeded by an
 *    operator. A store with the option unset behaves EXACTLY as it did
 *    before this file existed — that is asserted in
 *    `tests/test-school-visit-pickup.php`, not hoped for.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/** The per-environment registry of visits. Seeded by an operator, never here. */
if ( ! defined( 'BHP_SCHOOL_VISIT_OPTION' ) ) {
	define( 'BHP_SCHOOL_VISIT_OPTION', 'bhp_school_visits' );
}
/** The query parameter on a school's pre-visit link. */
if ( ! defined( 'BHP_SCHOOL_VISIT_PARAM' ) ) {
	define( 'BHP_SCHOOL_VISIT_PARAM', 'bhp_visit' );
}
/** WC session key. Holds the SLUG only — never the school name or a date. */
if ( ! defined( 'BHP_SCHOOL_VISIT_SESSION_KEY' ) ) {
	define( 'BHP_SCHOOL_VISIT_SESSION_KEY', 'bhp_school_visit' );
}
/** The shipping method id AND rate id of the pickup option. */
if ( ! defined( 'BHP_SCHOOL_PICKUP_METHOD_ID' ) ) {
	define( 'BHP_SCHOOL_PICKUP_METHOD_ID', 'bhp_school_pickup' );
}
/** The longest display time that will be stored. A time, not a paragraph. */
if ( ! defined( 'BHP_SCHOOL_VISIT_TIME_MAXLEN' ) ) {
	define( 'BHP_SCHOOL_VISIT_TIME_MAXLEN', 40 );
}
/** Order meta. `_bhp_school_pickup` is the flag; the rest is the packing list. */
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_FLAG' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_FLAG', '_bhp_school_pickup' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_SLUG' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_SLUG', '_bhp_school_visit_slug' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_SCHOOL' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_SCHOOL', '_bhp_school_visit_school' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_DATE' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_DATE', '_bhp_school_visit_date' );
}

/* =========================================================================
 * THE REGISTRY
 * ====================================================================== */

/**
 * Today's date in the SITE's timezone, as `Y-m-d`.
 *
 * ⛔ NOT `date('Y-m-d')` and not UTC. A cutoff of "2026-08-25" means the end
 *    of the 25th where the school is, and a UTC comparison would close the
 *    option up to a day early for a US store every evening.
 *
 * @return string
 */
function bhp_school_visit_today() {
	if ( function_exists( 'wp_date' ) ) {
		return wp_date( 'Y-m-d' );
	}
	return gmdate( 'Y-m-d' );
}

/**
 * Every registered visit, sanitised, keyed by slug.
 *
 * Fails closed on every malformed row rather than guessing: a row missing a
 * school, a date or a cutoff is dropped, not defaulted.
 *
 * ⭐ 1.8.51 (2026-08-17, `CYCLE162-LD-VISITS-PAGE`) ADDS AN OPTIONAL `time`.
 *    It is a DISPLAY STRING and nothing else: it is never parsed, never
 *    compared, never used to decide whether a visit is open, and never sent
 *    anywhere. Only `/author-visits/` renders it today.
 *
 * ⛔ THE TOLERANCE IS THE POINT, AND IT IS DELIBERATELY ASYMMETRIC. School,
 *    date and cutoff are REQUIRED and a row missing one is dropped, because
 *    each of them gates money or a promise. `time` gates nothing, so a row
 *    without it is a COMPLETE row that renders date-only. Making it required
 *    would have silently deleted the three visits already seeded on both
 *    environments the moment this version shipped.
 *
 * @return array<string,array{slug:string,school:string,date:string,cutoff:string,time:string}>
 */
function bhp_school_visit_records() {
	$raw = get_option( BHP_SCHOOL_VISIT_OPTION, array() );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$out = array();
	foreach ( $raw as $slug => $row ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! is_array( $row ) ) {
			continue;
		}
		$school = isset( $row['school'] ) ? trim( wp_strip_all_tags( (string) $row['school'] ) ) : '';
		$date   = isset( $row['date'] ) ? trim( (string) $row['date'] ) : '';
		$cutoff = isset( $row['cutoff'] ) ? trim( (string) $row['cutoff'] ) : '';

		if ( '' === $school || ! bhp_school_visit_is_ymd( $date ) || ! bhp_school_visit_is_ymd( $cutoff ) ) {
			continue;
		}

		$out[ $slug ] = array(
			'slug'   => $slug,
			'school' => $school,
			'date'   => $date,
			'cutoff' => $cutoff,
			'time'   => bhp_school_visit_sanitize_time( isset( $row['time'] ) ? $row['time'] : '' ),
		);
	}
	return $out;
}

/**
 * Sanitise the optional display time of a visit.
 *
 * ⛔ IT DOES NOT VALIDATE A TIME FORMAT, ON PURPOSE. "8:50 AM", "8:50–9:20 AM"
 *    and "right after lunch" are all things an operator may legitimately want
 *    on the page, and a format check would drop the third and then be "fixed"
 *    by loosening it anyway. What it DOES guarantee is that the value is a
 *    plain, short, single-line, tag-free string — because it is echoed to a
 *    public page.
 *
 * @param mixed $value Raw option value.
 * @return string Empty string when there is nothing usable.
 */
function bhp_school_visit_sanitize_time( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}
	$value = wp_strip_all_tags( (string) $value );
	$value = sanitize_text_field( $value );
	$value = trim( preg_replace( '/\s+/u', ' ', $value ) );

	return function_exists( 'mb_substr' )
		? mb_substr( $value, 0, BHP_SCHOOL_VISIT_TIME_MAXLEN )
		: substr( $value, 0, BHP_SCHOOL_VISIT_TIME_MAXLEN );
}

/**
 * Strict `Y-m-d` test. A real date, not merely a string that looks like one.
 *
 * @param string $value Candidate.
 * @return bool
 */
function bhp_school_visit_is_ymd( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return false;
	}
	$parts = explode( '-', $value );
	return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
}

/**
 * Resolve one slug to a LIVE, NON-EXPIRED visit, or null.
 *
 * ⛔ EVERY FACT IS RE-READ FROM THE OPTION AT CALL TIME. Nothing about a
 *    visit — not the school name, not the date, not the cutoff — is ever
 *    carried inside a session, so an edited or withdrawn visit takes effect
 *    on the very next request rather than at the end of somebody's session.
 *
 * @param string $slug Visit slug.
 * @return array{slug:string,school:string,date:string,cutoff:string}|null
 */
function bhp_school_visit_resolve( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}
	$records = bhp_school_visit_records();
	if ( ! isset( $records[ $slug ] ) ) {
		return null;
	}
	$record = $records[ $slug ];

	// The cutoff is INCLUSIVE — the last day an order may be placed.
	if ( bhp_school_visit_today() > $record['cutoff'] ) {
		return null;
	}
	return $record;
}

/* =========================================================================
 * THE SESSION FLAG
 * ====================================================================== */

/**
 * Turn `?bhp_visit=<slug>` into a session flag.
 *
 * ⛔ IT SETS NOTHING unless the slug resolves to a live, non-expired visit,
 *    so a stale bookmark, a guessed slug or a link used after the cutoff is
 *    an ordinary page view with an ignored query string. No notice, no
 *    error, no trace — a parent who missed the deadline sees the normal shop.
 * ⭐ IT IS NOT SCOPED TO ONE PAGE ON PURPOSE. The pre-visit email may point
 *    at any landing page; the param is the contract, not the destination.
 *   (Same reasoning, and the same shape, as `bhp_typ_capture_auto_coupon_intent()`.)
 */
add_action( 'template_redirect', 'bhp_school_visit_capture_intent', 5 );
function bhp_school_visit_capture_intent() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only intent flag from a public link; it grants a free delivery method, never a discount or a price change.
	if ( empty( $_GET[ BHP_SCHOOL_VISIT_PARAM ] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$slug = sanitize_key( wp_unslash( $_GET[ BHP_SCHOOL_VISIT_PARAM ] ) );

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	if ( ! bhp_school_visit_resolve( $slug ) ) {
		return;
	}

	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $slug );
	if ( is_callable( array( WC()->session, 'set_customer_session_cookie' ) ) ) {
		WC()->session->set_customer_session_cookie( true );
	}

	// A rate list may already be cached from before the flag existed.
	bhp_school_pickup_invalidate_rate_cache();
}

/**
 * The visit this session is entitled to, re-validated live, or null.
 *
 * Clears the flag when the visit is gone or past its cutoff, so an expired
 * session self-heals instead of being re-checked forever.
 *
 * @return array{slug:string,school:string,date:string,cutoff:string}|null
 */
function bhp_school_visit_active() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return null;
	}
	$slug = WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY );
	if ( ! $slug ) {
		return null;
	}
	$record = bhp_school_visit_resolve( $slug );
	if ( ! $record ) {
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
		bhp_school_pickup_invalidate_rate_cache();
		return null;
	}
	return $record;
}

/**
 * Force WooCommerce to recalculate shipping on the next read.
 *
 * The same mechanism WooCommerce itself uses when a zone is saved. Bumping
 * the transient version is the only thing that reliably clears a rate list
 * the session already holds — see `.claude/rules/woocommerce.md`.
 */
function bhp_school_pickup_invalidate_rate_cache() {
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}

/* =========================================================================
 * THE CHECKOUT OPTION
 * ====================================================================== */

/**
 * The customer-facing label. School and date, stated plainly, no promises.
 *
 * @param array $record Visit record.
 * @return string
 */
function bhp_school_pickup_label( array $record ) {
	$date = $record['date'];
	$ts   = strtotime( $date . ' 12:00:00' );
	if ( $ts ) {
		$date = wp_date( 'F j', $ts );
	}
	return sprintf(
		/* translators: 1: school name, 2: visit date */
		__( 'Author hand-delivery at the %1$s visit (%2$s)', 'brave-hearts' ),
		$record['school'],
		$date
	);
}

/**
 * Put the visit slug into the shipping package so the package HASH changes.
 *
 * ⛔ THIS IS LOAD-BEARING, NOT DECORATION. `woocommerce_package_rates` only
 *    runs on a cache miss. Without a package that differs between "no visit
 *    flag" and "visit flag", a visitor who had already seen checkout would
 *    never be offered the option, and — worse — a visitor whose flag EXPIRED
 *    would keep being offered it.
 */
add_filter( 'woocommerce_cart_shipping_packages', 'bhp_school_pickup_tag_package', 20 );
function bhp_school_pickup_tag_package( $packages ) {
	if ( ! is_array( $packages ) ) {
		return $packages;
	}
	$record = bhp_school_visit_active();
	if ( ! $record ) {
		return $packages; // Byte-identical package for every normal visitor.
	}
	foreach ( $packages as $key => $package ) {
		$packages[ $key ]['bhp_school_visit'] = $record['slug'];
	}
	return $packages;
}

/**
 * Add the free hand-delivery rate ALONGSIDE the normal one.
 *
 * ⛔ IT ADDS. IT NEVER REMOVES, RENAMES OR REPRICES ANOTHER RATE. A flagged
 *    parent who would rather have the books posted still sees, and can still
 *    choose, ordinary shipping at its ordinary tiered price. That is asserted
 *    in the test suite.
 * ⛔ PRIORITY 25 IS DELIBERATE: after the theme's Bookvault-rate strip (10)
 *    and after `bhp_bundle_override_shipping_cost()` (20), which only ever
 *    edits `flat_rate` and therefore cannot touch this rate whichever order
 *    they ran in — belt and braces.
 * ⭐ A CART THAT ALREADY SHIPS FREE (a complete collection) STILL GETS THE
 *    OPTION. Two free choices is not a defect; the parent is choosing where
 *    the books arrive, not what they cost.
 */
add_filter( 'woocommerce_package_rates', 'bhp_school_pickup_inject_rate', 25, 2 );
function bhp_school_pickup_inject_rate( $rates, $package = array() ) {
	if ( ! is_array( $rates ) || ! class_exists( 'WC_Shipping_Rate' ) ) {
		return $rates;
	}
	$record = bhp_school_visit_active();
	if ( ! $record ) {
		return $rates; // ZERO CHANGE for a normal visitor.
	}

	$rate = new WC_Shipping_Rate(
		BHP_SCHOOL_PICKUP_METHOD_ID,
		bhp_school_pickup_label( $record ),
		0.0,
		array(),
		BHP_SCHOOL_PICKUP_METHOD_ID,
		0
	);
	$rate->add_meta_data( __( 'School', 'brave-hearts' ), $record['school'] );
	$rate->add_meta_data( __( 'Visit date', 'brave-hearts' ), $record['date'] );

	$rates[ BHP_SCHOOL_PICKUP_METHOD_ID ] = $rate;
	return $rates;
}

/* =========================================================================
 * ORDER MARKING — ANDREW'S PACKING LIST
 * ====================================================================== */

/**
 * Is this order a hand-delivery order?
 *
 * ⛔ THE SHIPPING LINE IS CHECKED FIRST AND THE META SECOND, ON PURPOSE.
 *    The shipping line is written by WooCommerce itself from the chosen rate
 *    and exists on every order that chose pickup. The meta is written by this
 *    plugin. If the meta write ever failed, a meta-only test would let the
 *    order through to the printer — which is the one outcome this whole file
 *    exists to prevent. The authoritative signal is checked first and the
 *    convenience signal second.
 *
 * @param WC_Order|int|mixed $order Order or order id.
 * @return bool
 */
function bhp_school_pickup_order_is_pickup( $order ) {
	if ( is_numeric( $order ) ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $order ) : null;
	}
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	foreach ( $order->get_shipping_methods() as $item ) {
		if ( BHP_SCHOOL_PICKUP_METHOD_ID === $item->get_method_id() ) {
			return true;
		}
	}

	return 'yes' === $order->get_meta( BHP_SCHOOL_PICKUP_META_FLAG );
}

/**
 * Write the packing-list meta and the visible order note.
 *
 * Registered on BOTH checkout paths because this store runs WooCommerce
 * Blocks and the classic form is still reachable:
 *   · `woocommerce_store_api_checkout_order_processed` — Blocks / Store API.
 *   · `woocommerce_checkout_order_processed` — classic.
 * ⚠⚠ A CORRECTION THIS FILE MUST CARRY, because an earlier version of this
 *    very comment asserted the opposite and was WRONG.
 *
 *    It said `woocommerce_checkout_create_order_shipping_item` "is fired only
 *    by class-wc-checkout.php, never by the Store API", on the strength of a
 *    grep showing exactly one call site. **The grep was right and the
 *    conclusion was wrong.** `StoreApi\Utilities\OrderController::update_order_
 *    from_cart()` calls `wc()->checkout->create_order_shipping_lines()` —
 *    WooCommerce 10.9.1, OrderController line 820, read on staging — which is
 *    the method containing that single call site. The hook therefore DOES fire
 *    on the Blocks path. One call site is not one caller.
 *
 *    ⭐ THE CONSEQUENCE THAT MATTERS IS GOOD NEWS: `create_order_shipping_lines()`
 *       copies the RATE's meta onto the order's shipping item
 *       (`foreach ( $shipping_rate->get_meta_data() ... $item->add_meta_data() )`),
 *       so the School and Visit date this file attaches to the rate are on the
 *       order item in BOTH checkout paths. That is what makes the session-less
 *       fallback below real rather than theoretical.
 *
 *    The `..._order_processed` pair is nevertheless kept as the marking hook,
 *    because it runs once per order after the shipping lines exist, whereas the
 *    item hook runs per package before the order is saved.
 *
 * @param WC_Order|int $order Order or id (classic passes the id first).
 */
function bhp_school_pickup_mark_order( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( (int) $order );
	}
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return;
	}
	if ( 'yes' === $order->get_meta( BHP_SCHOOL_PICKUP_META_FLAG ) ) {
		return; // Already marked; both hooks can fire in one request.
	}

	// Resolve the visit from the session where possible, and fall back to the
	// rate's own stored meta so an order is still marked if the session died.
	$record = bhp_school_visit_active();
	$school = $record ? $record['school'] : '';
	$date   = $record ? $record['date'] : '';
	$slug   = $record ? $record['slug'] : '';

	if ( '' === $school ) {
		foreach ( $order->get_shipping_methods() as $item ) {
			if ( BHP_SCHOOL_PICKUP_METHOD_ID !== $item->get_method_id() ) {
				continue;
			}
			$school = (string) $item->get_meta( __( 'School', 'brave-hearts' ) );
			$date   = (string) $item->get_meta( __( 'Visit date', 'brave-hearts' ) );
		}
	}

	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_FLAG, 'yes' );
	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_SLUG, $slug );
	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_SCHOOL, $school );
	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_DATE, $date );

	/*
	 * The note is Andrew's packing list and it is deliberately blunt. It says
	 * "do not ship" in words, because the person reading it at 6am is reading
	 * a list of orders, not this file.
	 *
	 * ⛔ TWO NOTES, NOT ONE WITH BLANKS IN IT. If the school and date could not
	 *    be resolved, saying "at a school visit on the visit date" is worse
	 *    than useless — it reads like a real answer. The degraded note says the
	 *    detail is MISSING and where to look for it, which is a thing a human
	 *    can act on. (This path was reached in QA with a probe order that had
	 *    neither a session nor rate meta, and the first wording produced
	 *    exactly the pleasant-sounding non-answer described here.)
	 */
	/*
	 * ⭐ 1.8.50 (`CYCLE162-LD-PICKUP-FIELDS`) — THE CHILD'S NAME JOINS THE NOTE.
	 *
	 * Andrew's whole reason for the field is the signing, so the name belongs in
	 * the ONE artefact he actually reads while packing a bag, next to the school
	 * and the date — not only in a field panel further down the order screen.
	 *
	 * ⛔ IT IS A SEPARATE CLAUSE, NOT A SUBSTITUTION. The note says what it said
	 *    before, byte for byte, and gains a sentence. An order with no child name
	 *    (a classic-checkout order, an order placed before 1.8.50, a failed field
	 *    write) says so IN WORDS rather than printing "Sign to:" followed by
	 *    nothing — the same rule as the degraded school/date note below.
	 *
	 * ⛔ THE `function_exists()` GUARD IS REQUIRED and is not defensive padding:
	 *    `school-visit-fields.php` is required AFTER this file, and this function
	 *    also runs on orders created before that file existed.
	 */
	$child        = function_exists( 'bhp_school_visit_child_name' ) ? bhp_school_visit_child_name( $order ) : '';
	$child_clause = '' !== $child
		? sprintf(
			/* translators: %s: the child's first name */
			__( ' SIGN TO: %s.', 'brave-hearts' ),
			$child
		)
		: __( ' ⚠ No child name was captured on this order — ask before signing.', 'brave-hearts' );

	if ( '' !== $school && '' !== $date ) {
		$order->add_order_note(
			sprintf(
				/* translators: 1: school name, 2: visit date, 3: visit slug, 4: the signing clause */
				__( 'HAND DELIVERY — DO NOT SHIP. Author hand-delivery at %1$s on %2$s (visit: %3$s).%4$s This order is excluded from the Bookvault print/fulfilment push.', 'brave-hearts' ),
				$school,
				$date,
				'' !== $slug ? $slug : __( 'slug unresolved', 'brave-hearts' ),
				$child_clause
			)
		);
	} else {
		$order->add_order_note(
			sprintf(
				/* translators: %s: the signing clause */
				__( 'HAND DELIVERY — DO NOT SHIP. ⚠ THE SCHOOL AND VISIT DATE COULD NOT BE RESOLVED for this order — read the shipping method on the order itself to find out which visit it belongs to.%s This order is excluded from the Bookvault print/fulfilment push.', 'brave-hearts' ),
				$child_clause
			)
		);
	}

	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_school_pickup_mark_order', 5 );
add_action( 'woocommerce_checkout_order_processed', 'bhp_school_pickup_mark_order', 5 );

/* =========================================================================
 * ⛔⛔ THE DUPLICATE-PRINT PROTECTION
 * ====================================================================== */

/**
 * Hosts whose webhooks carry an order to the print partner.
 *
 * Matched on the DELIVERY URL rather than the webhook's NAME or ID, because
 * a name is editable in wp-admin and an id changes when a webhook is deleted
 * and re-created — both of which have already happened on this store (the
 * live table holds two generations of "BV Order Creation", ids 4 and 6).
 * A host is what actually determines where the payload lands.
 *
 * @return string[] Lowercase host fragments.
 */
function bhp_school_pickup_fulfilment_hosts() {
	return apply_filters(
		'bhp_school_pickup_fulfilment_hosts',
		array( 'bookvault.app', 'bookvault.com' )
	);
}

/**
 * True if this webhook posts to the print partner.
 *
 * @param WC_Webhook|mixed $webhook Webhook.
 * @return bool
 */
function bhp_school_pickup_is_fulfilment_webhook( $webhook ) {
	if ( ! is_object( $webhook ) || ! method_exists( $webhook, 'get_delivery_url' ) ) {
		return false;
	}
	$url = strtolower( (string) $webhook->get_delivery_url() );
	if ( '' === $url ) {
		return false;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$host = is_string( $host ) ? $host : $url; // Unparseable → test the whole string.

	foreach ( bhp_school_pickup_fulfilment_hosts() as $needle ) {
		if ( '' !== $needle && false !== strpos( $host, $needle ) ) {
			return true;
		}
	}
	return false;
}

/**
 * ⛔⛔ NEVER SEND A HAND-DELIVERY ORDER TO THE PRINT PARTNER.
 *
 * This is the single most safety-critical function in the build. If it stops
 * working, Andrew pays to print and post books he is also carrying to the
 * school by hand, and the parent receives two copies.
 *
 * It is intentionally the narrowest possible intervention:
 *   · It only ever returns FALSE, never true — it can suppress a delivery,
 *     and can never cause one that WooCommerce had already declined.
 *   · It only acts on webhooks bound for the print partner. Every other
 *     webhook on this store (Rutter's three) is returned untouched.
 *   · It only acts on ORDER resources. A product or coupon webhook that
 *     happened to point at the same host is not this function's business.
 *
 * @param bool             $should_deliver WooCommerce's decision so far.
 * @param WC_Webhook|mixed $webhook        The webhook.
 * @param mixed            $arg            First hook arg — the order id for order topics.
 * @return bool
 */
add_filter( 'woocommerce_webhook_should_deliver', 'bhp_school_pickup_block_bookvault_webhook', 10, 3 );
function bhp_school_pickup_block_bookvault_webhook( $should_deliver, $webhook = null, $arg = null ) {
	if ( ! $should_deliver ) {
		return $should_deliver; // Already declined. Nothing to do.
	}
	if ( ! bhp_school_pickup_is_fulfilment_webhook( $webhook ) ) {
		return $should_deliver;
	}
	if ( ! is_object( $webhook ) || ! method_exists( $webhook, 'get_resource' ) || 'order' !== $webhook->get_resource() ) {
		return $should_deliver;
	}
	if ( ! is_numeric( $arg ) ) {
		return $should_deliver;
	}
	if ( ! bhp_school_pickup_order_is_pickup( (int) $arg ) ) {
		return $should_deliver;
	}

	/*
	 * A skip is recorded ONCE per order, as a private order note, so that the
	 * protection having fired is visible on the order itself rather than only
	 * in a log nobody reads. It is not repeated on every subsequent
	 * `order.updated` event — a status change would otherwise add a note each
	 * time and bury the packing-list note.
	 */
	$order = wc_get_order( (int) $arg );
	if ( $order instanceof WC_Order && 'yes' !== $order->get_meta( '_bhp_school_pickup_bv_skipped' ) ) {
		$order->update_meta_data( '_bhp_school_pickup_bv_skipped', 'yes' );
		$order->add_order_note( __( 'Bookvault fulfilment webhook SKIPPED — this is a hand-delivery order.', 'brave-hearts' ) );
		$order->save();
	}

	return false;
}

/**
 * Second layer: hide the Bookvault plugin's manual "resend" action on a
 * pickup order, so the one remaining human route to the printer is closed.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN IMPLIED: the Bookvault plugin also
 *   registers an orders-LIST BULK action (`bvlt_bulk_resend_orders`) which
 *   iterates raw post ids and consults no per-order state. There is no hook
 *   between that loop and `bvlt_resendOrder()`, so it cannot be intercepted
 *   without editing a third-party plugin, which this build does not do.
 *   Selecting a hand-delivery order in that bulk action would still push it.
 *
 * @param array    $actions Order actions.
 * @param WC_Order $order   Order.
 * @return array
 */
add_filter( 'woocommerce_order_actions', 'bhp_school_pickup_remove_resend_action', 20, 2 );
function bhp_school_pickup_remove_resend_action( $actions, $order = null ) {
	if ( ! is_array( $actions ) || ! $order instanceof WC_Order ) {
		return $actions;
	}
	if ( ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return $actions;
	}
	unset( $actions['blt_resend_order'] );
	return $actions;
}

/* =========================================================================
 * CUSTOMER-FACING HONESTY
 * ====================================================================== */

/**
 * Thank-you page: say where the books are actually going.
 *
 * The stock line talks about an order being received. For a hand-delivery
 * order the single most useful sentence is where and when to collect, so it
 * is added above the order table rather than buried in the meta list.
 *
 * @param int $order_id Order id.
 */
add_action( 'woocommerce_thankyou', 'bhp_school_pickup_thankyou_notice', 5 );
function bhp_school_pickup_thankyou_notice( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order || ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return;
	}
	$school = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_SCHOOL );
	$date   = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_DATE );
	$ts     = $date ? strtotime( $date . ' 12:00:00' ) : false;
	$pretty = $ts ? wp_date( 'l, F j', $ts ) : $date;

	echo '<div class="bhp-school-pickup-notice" style="border:1px solid #e5e0d3;border-left:4px solid #2f6f4f;padding:16px 18px;margin:0 0 24px;">';
	echo '<p style="margin:0 0 6px;font-weight:700;">' . esc_html__( 'Your books are being hand-delivered — nothing is being posted.', 'brave-hearts' ) . '</p>';
	if ( '' !== $school && '' !== $pretty ) {
		echo '<p style="margin:0;">' . esc_html(
			sprintf(
				/* translators: 1: school name, 2: visit date */
				__( 'Andrew will bring them, signed, to %1$s on %2$s. You do not need to do anything, and you will not be charged for shipping.', 'brave-hearts' ),
				$school,
				$pretty
			)
		) . '</p>';
	}
	echo '</div>';
}
