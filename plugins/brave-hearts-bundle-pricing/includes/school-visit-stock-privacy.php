<?php
/**
 * Brave Hearts Bundle Pricing — PER-VISIT STOCK-QUANTITY PRIVACY.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS EXISTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-28 morning, verbatim (⛔ RELAYED to the agent that
 * wrote this file through the Chief of Staff's `CYCLE168-LD-AMITY-STOCK-
 * SUPPRESSION` dispatch and through carrier item 359 of `FOUNDER-VERBATIM-
 * 2026-08-05-PRODUCTION-DEPLOY-AUTHORIZATION.md`; NOT witnessed first-hand):
 *
 *   "I just want Amity not to see the current stock since we will have 75
 *    more books coming Sept 7-11"
 *
 * ⭐ THE REASON IS A CALENDAR, NOT A PRICE. The Amity Elementary visit is
 *    2026-09-14 with an order cutoff of 2026-09-11. A 75-book restock lands
 *    2026-09-07 to 2026-09-11 — BEFORE both. So the shelf number an Amity
 *    parent would read today describes a shelf that will not be the shelf
 *    their books come off. Showing it is not scarcity, it is a wrong fact.
 *
 * ⛔ DALLAS HARRIS IS THE OPPOSITE CASE AND IS DELIBERATELY UNCHANGED. Its
 *    visit is 2026-09-03 and its cutoff 2026-08-31, both BEFORE the restock,
 *    so its parents are buying off exactly the shelf the counter is counting.
 *    Its scarcity display is real and it stays. The same is true of
 *    `adams-2026-08-28` and `liberty-2026-09-04`.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS FILE MAY DO, AND THE ONE THING IT MAY NEVER DO
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ✅ It REMOVES a number from a page.
 * ⛔ IT NEVER SUBSTITUTES A LARGER ONE, AND IT NEVER INVENTS ONE.
 *
 *    The false-availability rule is a hard gate: no surface may ever display
 *    a quantity higher than the real one. Every branch below either returns
 *    the caller's own value untouched or replaces it with a string carrying
 *    NO quantity at all. There is no arithmetic in this file. There is no
 *    number in this file.
 *
 * ⛔ IT CHANGES NO PURCHASABILITY, ANYWHERE, FOR ANYBODY.
 *
 *    · No `_stock`, `_stock_status`, `_manage_stock` or `_backorders` value is
 *      read-modified or written, on any environment. ⭐ VERIFIED READ-ONLY ON
 *      PRODUCTION 2026-08-28: all nine product/variation records (14, 15, 17,
 *      18, 20, 333, 334, 538, 618) carry `_manage_stock = no`, an EMPTY
 *      `_stock`, `_stock_status = instock` and `_backorders = no`. WooCommerce
 *      therefore prints no quantity to anybody today; the filters below are a
 *      belt for the day that changes, and the shelf gate is where the real
 *      number lives.
 *    · The sold-out state is NOT suppressed. `bhp_visit_shelf_title_is_closed()`
 *      and all five server refusal seams in `school-visit-paperback-only.php`
 *      are untouched and still govern. ⭐ THAT IS ON PURPOSE: hiding a QUANTITY
 *      is honest, hiding a REFUSAL would offer a parent a book the server is
 *      about to decline. A purchasability signal is not a stock number.
 *
 * ⚠️ THE RESIDUAL, NAMED RATHER THAN HIDDEN — AND IT IS ANDREW'S DECISION,
 *    NOT THIS FILE'S. If a chapter title's shelf reaches the buffer before the
 *    restock lands, that title CLOSES for every visit-flagged session, Amity
 *    included, and an Amity parent then cannot buy it at all — regardless of
 *    what this file hides. Suppressing the display does not suppress the gate
 *    and was never asked to. Allowing a visit-flagged backorder would be a
 *    WooCommerce product-configuration change and requires his explicit word.
 *    Registered, not resolved.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ WHERE THE NUMBERS ACTUALLY COME FROM — BOTH SOURCES, IN ORDER
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   1. ⭐⭐ THE SCHOOL-VISIT SHELF COUNTER (`school-visit-shelf-stock.php`,
 *      1.8.72). This is the ONE surface printing a live quantity on this site
 *      today: "Only 7 left for the school visit", on the product loop, the
 *      series rows and the bundle rows, when a flagged session meets a title
 *      whose remaining count is 2..10. It is gated in that file's two
 *      `_for_request` functions, which its own header names as the only two a
 *      surface is allowed to call.
 *
 *   2. WOOCOMMERCE'S OWN AVAILABILITY TEXT ("12 in stock", "Only 2 left in
 *      stock"). Inert today because nothing manages stock, and gated here
 *      anyway — through WooCommerce's OWN display-only setting rather than
 *      through any product record.
 *
 * ⛔ WHAT IS DELIBERATELY *NOT* COVERED, STATED SO A LATER READER DOES NOT
 *    ASSUME IT IS: the Store API's `low_stock_remaining` field, which
 *    WooCommerce Blocks would render in the cart drawer and on checkout. It is
 *    computed inside the Blocks schema classes with no filter this file could
 *    reach, and it is `null` for every product on this store because nothing
 *    manages stock. ⚠️ IF STOCK MANAGEMENT IS EVER TURNED ON, THIS FILE DOES
 *    NOT COVER THE BLOCKS CART and a Store API extension would be needed.
 *    Recorded as a known limitation of 1.8.75, not as a defect fixed here.
 *
 * ⛔ VOICE: standing rule §9.1. This file introduces NO new customer-facing
 *    sentence. The only string it can ever print is WooCommerce's own
 *    translated "In stock" — deliberately, because a suppression that
 *    invented its own wording would be new copy nobody approved.
 *
 * @package brave-hearts-bundle-pricing
 * @since 1.8.75
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * THE PREDICATE
 * ====================================================================== */

/**
 * ⭐⭐ THE VISIT SLUGS WHOSE SESSIONS SEE NO STOCK QUANTITY, IN CODE.
 *
 * ⛔ THIS EXISTS BECAUSE THE REGISTRY ALONE COULD NOT CARRY THE RULING IN
 *    TIME, AND THE REASON IS A GOVERNANCE FACT, NOT A TECHNICAL ONE.
 *    `bhp_school_visits` is a production DATA row. Writing it on production is
 *    Andrew's gate. A build whose entire behaviour depended on that write
 *    would deploy INERT, and the ruling would appear to be in force while a
 *    real Amity parent still read a number off the page. So the founder-ruled
 *    slug ships in code, and it is in force the moment the plugin activates.
 *
 * ⭐ THE REGISTRY FIELD IS STILL THE FORWARD PATH, AND IT IS OR-ed WITH THIS,
 *    NOT REPLACED BY IT. The next visit that needs stock hidden takes one
 *    `wp option patch update bhp_school_visits` and no deploy. Nothing new
 *    needs to be added to this list, ever.
 *
 * ⭐ AND IT IS FILTERABLE, so Andrew can drop Amity out of it the day the
 *    restock is on his shelf and counted, without waiting for a release:
 *      add_filter( 'bhp_school_visit_stock_hidden_slugs', '__return_empty_array' );
 *
 * @return string[] Visit slugs.
 */
function bhp_school_visit_stock_hidden_slugs() {
	/**
	 * Visit slugs whose flagged sessions never see a stock quantity.
	 *
	 * @since 1.8.75
	 * @param string[] $slugs Visit slugs.
	 */
	$slugs = apply_filters(
		'bhp_school_visit_stock_hidden_slugs',
		array( 'amity-2026-09-14' )
	);

	if ( ! is_array( $slugs ) ) {
		return array(); // A filter that returns nonsense hides nothing.
	}

	$out = array();
	foreach ( $slugs as $slug ) {
		if ( ! is_scalar( $slug ) ) {
			continue;
		}
		$slug = sanitize_key( (string) $slug );
		if ( '' !== $slug ) {
			$out[] = $slug;
		}
	}
	return $out;
}

/**
 * Does THIS visit record hide stock quantities?
 *
 * ⛔ IT DOES NOT ASK WHETHER THE SESSION IS FLAGGED, for exactly the reason
 *    `bhp_visit_shelf_title_is_closed()` next door does not: it answers a
 *    question about a VISIT, which is true or false regardless of who is
 *    looking, so a report or a test can ask it without faking a session.
 *    ⛔ SURFACES MUST NOT CALL THIS ONE. They call the `_for_request` gate.
 *
 * @param array|null $record A sanitised visit record from `bhp_school_visit_records()`.
 * @return bool
 */
function bhp_school_visit_record_hides_stock( $record ) {
	if ( ! is_array( $record ) || empty( $record['slug'] ) ) {
		return false;
	}

	// 1. The registry field. Absent or unreadable => false, per 1.8.75.
	if ( ! empty( $record['hide_stock'] ) ) {
		return true;
	}

	// 2. The code-level founder ruling.
	return in_array( (string) $record['slug'], bhp_school_visit_stock_hidden_slugs(), true );
}

/**
 * ⭐⭐ THE ONE GATE EVERY SURFACE AND EVERY FILTER IN THIS BUILD ASKS.
 *
 * ⛔ FALSE FOR EVERY ORDINARY SHOPPER, ON EVERY ENVIRONMENT, ALWAYS — and
 *    false for every visit that is not on the list. An unflagged session, an
 *    Adams session, a Dallas Harris session and a Liberty session all get
 *    `false` and therefore byte-identical behaviour to 1.8.74.
 *
 * ⛔ IT FAILS TO `false`, NOT TO `true`, AND THE DIRECTION IS CHOSEN RATHER
 *    THAN INHERITED. Failing to `true` would suppress the Dallas Harris
 *    counter — a founder-ruled feature that is CORRECT for that visit —
 *    every time a resolver hiccupped. Failing to `false` costs, at worst,
 *    one number shown to one Amity parent on a request that was already
 *    malfunctioning. The cheaper failure is the one that does not silently
 *    switch off a working feature for another school.
 *
 * ⚠️ IT IS SAFE TO CALL FROM INSIDE AN `option_*` READ FILTER, and the
 *    filters below do exactly that. `bhp_school_visit_request_record()` holds
 *    its own reentrancy guard and returns null while it is up, which is the
 *    safe answer: the stored option value passes through unchanged.
 *
 * @return bool
 */
function bhp_school_visit_hide_stock_for_request() {
	if ( ! function_exists( 'bhp_school_visit_request_record' ) ) {
		return false; // FAIL OPEN: no visit machinery -> nothing is suppressed.
	}

	try {
		$record = bhp_school_visit_request_record();
	} catch ( Throwable $e ) {
		return false; // FAIL OPEN.
	}

	if ( ! $record ) {
		return false; // ⭐ ZERO CHANGE for every ordinary shopper.
	}

	return bhp_school_visit_record_hides_stock( $record );
}

/* =========================================================================
 * ⭐ WOOCOMMERCE'S OWN AVAILABILITY TEXT — THE BELT
 *
 * ⛔ EVERY HOOK BELOW IS A READ FILTER ON A DISPLAY VALUE. Not one writes an
 *    option, a product, a meta row or a transient. `update_option()` does not
 *    appear in this file and must not.
 * ====================================================================== */

/**
 * True when the WooCommerce display filters below should engage.
 *
 * ⛔ ADMIN IS EXCLUDED DELIBERATELY. An operator looking at a product screen
 *    must be shown the truth, and there is no version of this feature in which
 *    hiding an inventory number from wp-admin is the helpful thing to do. The
 *    Store API and admin-ajax are NOT excluded: those render to the customer.
 *
 * @return bool
 */
function bhp_school_visit_stock_privacy_active() {
	if ( function_exists( 'is_admin' ) && is_admin()
		&& ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return false;
	}

	return bhp_school_visit_hide_stock_for_request();
}

/**
 * ⭐ FORCE WOOCOMMERCE'S OWN "never show quantity remaining" DISPLAY MODE, for
 *    this request only.
 *
 * ⛔ THIS IS WOOCOMMERCE'S SETTING, USED THE WAY WOOCOMMERCE USES IT, AND THAT
 *    IS THE WHOLE POINT OF DOING IT HERE RATHER THAN BY REGEX ON THE RENDERED
 *    STRING. `wc_format_stock_for_display()` reads this option and returns the
 *    plain, already-translated "In stock" when it is `no_amount`. So the
 *    suppressed page carries WooCommerce's own words, in the shopper's own
 *    language, with no new copy invented by this plugin and no chance of a
 *    string-mangling bug eating a word that was not a number.
 *
 * ⛔ IT IS A READ FILTER. The stored option row is NOT written, NOT patched and
 *    NOT restored-after — because it was never changed. Every other request in
 *    flight, and the wp-admin screen, read the stored value untouched.
 *
 * @param mixed $value The stored (or default) option value.
 * @return mixed
 */
function bhp_school_visit_stock_format_no_amount( $value ) {
	static $busy = false;

	if ( $busy ) {
		return $value; // Reentrancy: hand back exactly what was stored.
	}

	$busy = true;
	try {
		return bhp_school_visit_stock_privacy_active() ? 'no_amount' : $value;
	} catch ( Throwable $e ) {
		return $value; // FAIL OPEN.
	} finally {
		$busy = false;
	}
}
add_filter( 'option_woocommerce_stock_format', 'bhp_school_visit_stock_format_no_amount', 20 );
add_filter( 'default_option_woocommerce_stock_format', 'bhp_school_visit_stock_format_no_amount', 20 );

/**
 * ⭐ THE LAST LINE: an availability string that still carries a digit.
 *
 * The option filter above covers WooCommerce's own three formats. This covers
 * anything else that got there first — a third-party plugin, a future core
 * change, a themed override — WITHOUT trying to edit the string it was given.
 *
 * ⛔ IT REPLACES, IT NEVER REWRITES. There is no `preg_replace` on a
 *    customer-facing sentence here: partial surgery on a translated string is
 *    how "Only 2 left in stock" becomes "Only left in stock" in one locale and
 *    something worse in another. If the text carries a digit and the product is
 *    purchasable, the whole string becomes WooCommerce's own plain "In stock".
 *
 * ⛔ IT NEVER TOUCHES A NOT-IN-STOCK OR BACKORDER STRING. "Out of stock" and
 *    "Available on backorder" are PURCHASABILITY signals, not quantities, and
 *    replacing either would tell a parent something untrue in the one direction
 *    this build is forbidden to move.
 *
 * @param string          $text    The availability text WooCommerce built.
 * @param WC_Product|null $product The product.
 * @return string
 */
function bhp_school_visit_strip_availability_quantity( $text, $product = null ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}
	if ( ! preg_match( '/\d/', $text ) ) {
		return $text; // No digit -> nothing to hide. The common case, first.
	}
	if ( ! is_object( $product ) || ! method_exists( $product, 'is_in_stock' ) ) {
		return $text;
	}

	try {
		if ( ! $product->is_in_stock() ) {
			return $text; // ⛔ NEVER touch an out-of-stock signal.
		}
		if ( method_exists( $product, 'is_on_backorder' ) && $product->is_on_backorder( 1 ) ) {
			return $text; // ⛔ NEVER touch a backorder signal.
		}
		if ( ! bhp_school_visit_stock_privacy_active() ) {
			return $text; // ⭐ BYTE-IDENTICAL for every ordinary shopper.
		}
	} catch ( Throwable $e ) {
		return $text; // FAIL OPEN.
	}

	return __( 'In stock', 'woocommerce' );
}
add_filter( 'woocommerce_get_availability_text', 'bhp_school_visit_strip_availability_quantity', 20, 2 );

/**
 * ⭐ The formatted quantity string itself, for any caller that reaches
 *    `wc_format_stock_quantity_for_display()` directly rather than through the
 *    availability text.
 *
 * ⛔ UNREACHABLE ON THIS STORE TODAY — the option filter above short-circuits
 *    the only core path into it. It is here because "unreachable today" is a
 *    fact about a configuration, not about the code, and this is one line.
 *
 * @param string          $display  The formatted quantity, e.g. "12 in stock".
 * @param WC_Product|null $product  The product.
 * @return string
 */
function bhp_school_visit_hide_formatted_stock_quantity( $display, $product = null ) {
	unset( $product );

	if ( ! is_string( $display ) ) {
		return $display;
	}

	try {
		if ( ! bhp_school_visit_stock_privacy_active() ) {
			return $display; // ⭐ BYTE-IDENTICAL for every ordinary shopper.
		}
	} catch ( Throwable $e ) {
		return $display; // FAIL OPEN.
	}

	return __( 'In stock', 'woocommerce' );
}
add_filter( 'woocommerce_format_stock_quantity', 'bhp_school_visit_hide_formatted_stock_quantity', 20, 2 );
