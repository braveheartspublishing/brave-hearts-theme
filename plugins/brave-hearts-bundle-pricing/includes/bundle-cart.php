<?php
/**
 * Brave Hearts Bundle Pricing — WooCommerce cart/checkout hooks.
 *
 * Applies the approved fixed-dollar bundle discount and shipping amounts
 * without ever replacing a product's own cart line item. Every book stays
 * its own line item with its own price, SKU, and tax; this file only adds
 * a clearly-labeled negative fee for the discount and adjusts the cost of
 * the store's existing flat-rate shipping method.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audience-coupon Collection-only stacking policy. Audience coupons are
 * the ONLY coupons in this store ever allowed to coexist with the Bundle
 * Savings fee; every other coupon keeps the original Phase 9 behavior
 * (bundle discount suppressed the instant any other coupon applies).
 * Scope is never a generic "any coupon can stack" mechanism.
 *
 * EMPTY BY DESIGN since 1.8.29. This file is published in a public
 * repository, so a coupon code written here is a coupon code given away:
 * anyone reading the source learns a working discount code, and rotating
 * the code cannot help while the replacement has to be committed to be
 * honoured. Scope now lives entirely on the coupon record itself, via
 * the meta flag defined immediately below.
 *
 * The `! defined()` guard is deliberately kept. It is the supported way
 * for a private, non-published environment (wp-config.php, an mu-plugin)
 * to pin legacy literal codes without those codes entering source
 * control. Nothing in this repository may ever populate it.
 */
if ( ! defined( 'BHP_AUDIENCE_COUPON_CODES' ) ) {
	define( 'BHP_AUDIENCE_COUPON_CODES', array() );
}

/**
 * Per-coupon opt-in flag (added 1.8.8, sole route since 1.8.29). The
 * scope lives with the coupon record, so the code string never has to
 * enter source control and a rotated code needs no release.
 *
 * Live coupon records were confirmed to carry this flag before the
 * literal list was emptied, so emptying it changed no live behaviour --
 * see tests/test-audience-coupon-meta-scope.php, which asserts the
 * equivalence continuously rather than relying on that one check.
 */
if ( ! defined( 'BHP_AUDIENCE_COUPON_META_KEY' ) ) {
	define( 'BHP_AUDIENCE_COUPON_META_KEY', '_bhp_audience_coupon' );
}

/**
 * True if a coupon CODE carries audience-coupon scope: normally by the
 * per-coupon meta flag, and only additionally by a privately-defined
 * literal list (empty in this repository). This is the one
 * place the two routes are combined -- every caller below goes through
 * either this function or bhp_is_audience_coupon(), so validation, the
 * native-discount zeroing, the savings fee and the Bundle Savings
 * stacking guard can never disagree about what counts as an audience
 * coupon.
 */
function bhp_is_audience_coupon_code( $code ) {
	$code = strtolower( trim( (string) $code ) );
	if ( '' === $code ) {
		return false;
	}
	if ( in_array( $code, BHP_AUDIENCE_COUPON_CODES, true ) ) {
		return true;
	}
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return false;
	}
	$coupon = new WC_Coupon( $code );
	if ( ! $coupon->get_id() ) {
		return false;
	}
	return 'yes' === $coupon->get_meta( BHP_AUDIENCE_COUPON_META_KEY, true );
}

/**
 * True only if the cart is a genuine, single-format Complete Collection:
 * all three distinct titles present in exactly one format, no opposite
 * format present, no non-catalog item present, and no on-sale item
 * present. Used identically by coupon validation and by the fee
 * calculation, so the two can never disagree about what counts as
 * "eligible." Sale-item exclusion is enforced here (not left only to
 * WooCommerce's native exclude_sale_items coupon setting) so every
 * ineligible case -- including a sale item -- surfaces the SAME custom
 * message rather than two different error strings.
 */
function bhp_audience_coupon_cart_qualifies( $cart ) {
	if ( ! $cart ) {
		return false;
	}
	$eval = bhp_bundle_evaluate_cart( $cart );

	if ( $eval['has_unrelated'] ) {
		return false;
	}

	foreach ( $cart->get_cart() as $cart_item ) {
		if ( ! empty( $cart_item['data'] ) && is_callable( array( $cart_item['data'], 'is_on_sale' ) ) && $cart_item['data']->is_on_sale() ) {
			return false;
		}
	}

	$pb_complete = ( 3 === $eval['paperback_tier'] ) && ! $eval['has_hardcover'];
	$hc_complete = ( 3 === $eval['hardcover_tier'] ) && ! $eval['has_paperback'];

	return $pb_complete || $hc_complete;
}

/**
 * Which single format ('paperback'|'hardcover') the cart's qualifying
 * Complete Collection is in. Only meaningful when
 * bhp_audience_coupon_cart_qualifies() is true; returns null otherwise.
 */
function bhp_audience_coupon_qualifying_format( $cart ) {
	if ( ! bhp_audience_coupon_cart_qualifies( $cart ) ) {
		return null;
	}
	$eval = bhp_bundle_evaluate_cart( $cart );
	return ( 3 === $eval['paperback_tier'] ) ? 'paperback' : 'hardcover';
}

/**
 * True if the coupon object is an audience coupon -- a coupon carrying
 * the BHP_AUDIENCE_COUPON_META_KEY opt-in flag, or (only where a private
 * environment has pinned one) a BHP_AUDIENCE_COUPON_CODES literal, which
 * is empty in this repository. Never a generic "is this any
 * coupon" check. Reads the flag off the passed object where possible so
 * an already-loaded coupon costs no extra lookup.
 */
function bhp_is_audience_coupon( $coupon ) {
	if ( ! ( $coupon instanceof WC_Coupon ) ) {
		return false;
	}
	if ( in_array( strtolower( $coupon->get_code() ), BHP_AUDIENCE_COUPON_CODES, true ) ) {
		return true;
	}
	if ( $coupon->get_id() ) {
		return 'yes' === $coupon->get_meta( BHP_AUDIENCE_COUPON_META_KEY, true );
	}
	return bhp_is_audience_coupon_code( $coupon->get_code() );
}

/**
 * Blocks any audience coupon from applying to anything except a genuine,
 * single-format Complete Collection -- one individual book, two
 * individual books, a qualifying two-book bundle, a mixed-format cart, a
 * sale item, or any cart without a complete three-book collection are
 * all rejected with the same exact, clear message. Never a partial or
 * silent discount.
 *
 * Thrown as an Exception because WC_Discounts::is_coupon_valid() catches
 * it and surfaces the message as the customer-facing coupon error -- the
 * standard WooCommerce pattern for a custom coupon-validity message.
 */
add_filter( 'woocommerce_coupon_is_valid', 'bhp_validate_audience_coupon_scope', 10, 2 );
function bhp_validate_audience_coupon_scope( $valid, $coupon ) {
	if ( ! bhp_is_audience_coupon( $coupon ) ) {
		return $valid;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $valid;
	}
	if ( ! bhp_audience_coupon_cart_qualifies( WC()->cart ) ) {
		throw new Exception( sprintf(
			/* translators: %s: the coupon code as the customer typed it. */
			__( '%s is valid on the Paperback or Hardcover Complete Collection, which includes all three Adventures of Charlotte and Henry books.', 'bhp-bundle-pricing' ),
			strtoupper( $coupon->get_code() )
		) );
	}
	return $valid;
}

/**
 * Neutralizes an audience coupon's native per-item percentage discount.
 * Its real monetary effect is applied as a single, exact "[CODE] Savings"
 * fee instead (see bhp_audience_coupon_apply_savings_fee()), computed
 * once against the ALREADY-discounted Complete Collection price rather
 * than distributed per line item -- this sidesteps WooCommerce's internal
 * per-item proportional rounding entirely and guarantees the total always
 * matches exactly what bhp_audience_coupon_savings_amount() computes.
 */
add_filter( 'woocommerce_coupon_get_discount_amount', 'bhp_audience_coupon_zero_native_discount', 10, 5 );
function bhp_audience_coupon_zero_native_discount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
	if ( ! bhp_is_audience_coupon( $coupon ) ) {
		return $discount;
	}
	return 0;
}

/**
 * The exact audience-coupon savings amount for a qualifying cart: 10% of
 * the Complete Collection price AFTER its own $3.98/$4.98 discount is
 * already applied -- i.e. stacked ON TOP OF, not instead of, the existing
 * Collection savings. Returns 0.0 if the cart doesn't qualify.
 */
function bhp_audience_coupon_savings_amount( $cart ) {
	$format = bhp_audience_coupon_qualifying_format( $cart );
	if ( ! $format ) {
		return 0.0;
	}
	$rules             = bhp_bundle_rules( $format );
	$combined          = 3 * bhp_bundle_expected_price( $format );
	$collection_price  = $combined - $rules[3]['discount'];
	return round( $collection_price * 0.10, 2 );
}

/**
 * Adds the "[CODE] Savings" fee -- the coupon's real monetary effect,
 * shown as its own itemized line alongside "Bundle Savings", mirroring
 * exactly how the Complete Collection discount itself is already
 * displayed. Only ever fires when a single audience coupon is the cart's
 * sole applied coupon on an already-validated, qualifying cart -- the
 * woocommerce_coupon_is_valid check above guarantees a non-qualifying
 * cart never reaches this point with an audience coupon applied.
 */
add_action( 'woocommerce_cart_calculate_fees', 'bhp_audience_coupon_apply_savings_fee', 21 );
function bhp_audience_coupon_apply_savings_fee( $cart ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! is_object( $cart ) ) {
		return;
	}
	$applied = $cart->get_applied_coupons();
	if ( 1 !== count( $applied ) || ! bhp_is_audience_coupon_code( $applied[0] ) ) {
		return;
	}
	$amount = bhp_audience_coupon_savings_amount( $cart );
	if ( $amount <= 0 ) {
		return;
	}
	// taxable = false: matches the Bundle Savings fee's own tax handling
	// immediately below -- the fee reduces the pre-tax subtotal of items
	// that are already taxable, so WooCommerce recalculates tax on the
	// reduced total on its own.
	$cart->add_fee( sprintf( '%s Savings', strtoupper( $applied[0] ) ), -1 * $amount, false );
}

/**
 * Add one negative "Bundle Savings" fee line per qualifying format.
 *
 * Using woocommerce_cart_calculate_fees (a fee, not a price override) is
 * how WooCommerce expects a visible, itemized discount to be represented —
 * it recalculates tax around the fee automatically and it is always
 * recomputed fresh on every cart/checkout load, so there is no way for a
 * stale discount to survive a cart change (Phase 9).
 */
add_action( 'woocommerce_cart_calculate_fees', 'bhp_bundle_apply_discount_fees', 20 );
function bhp_bundle_apply_discount_fees( $cart ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! is_object( $cart ) ) {
		return;
	}

	// Coupon-stacking guard (Phase 9), revised for the audience-coupon
	// Collection-only policy: any coupon OTHER than an audience coupon
	// still fully suppresses the Bundle Savings fee, exactly as before.
	// An audience coupon specifically is allowed to coexist with Bundle
	// Savings -- but ONLY when the cart is a genuine, qualifying Complete
	// Collection (bhp_audience_coupon_cart_qualifies()), which
	// woocommerce_coupon_is_valid already enforces before an audience
	// coupon can be applied at all. This is an exception for coupons
	// explicitly flagged as audience coupons on their own record, never
	// a generic "any coupon can stack" rule.
	$applied_coupons = $cart->get_applied_coupons();
	if ( ! empty( $applied_coupons ) ) {
		$only_audience_coupon = ( 1 === count( $applied_coupons ) ) && bhp_is_audience_coupon_code( $applied_coupons[0] );
		if ( ! $only_audience_coupon || ! bhp_audience_coupon_cart_qualifies( $cart ) ) {
			return;
		}
	}

	$eval = bhp_bundle_evaluate_cart( $cart );

	// Mixed-format discount rule (Overnight Conversion Sprint, Priority 7 —
	// supersedes the Staging Refinement Phase 1 blanket "any mixing kills
	// every discount" rule):
	//   - A partial 2-book discount is suppressed the moment the opposite
	//     format is present at all — a mixed cart with e.g. two paperbacks
	//     + one hardcover never gets the paperback "any 2" discount, since
	//     that offer was never advertised as compatible with a hardcover.
	//   - A COMPLETE 3-book set discount always applies once earned, even
	//     if the opposite format is also present. A customer who has
	//     genuinely bought the complete paperback set has fulfilled that
	//     advertised offer regardless of what else is in the cart, and
	//     the same applies independently to a complete hardcover set — so
	//     a cart with 3 paperbacks + 3 hardcovers gets BOTH complete-set
	//     discounts, never a blanket suppression.
	// Each format's tier is evaluated completely independently below;
	// there is deliberately no early return keyed off is_mixed_format
	// here anymore.
	foreach ( array( 'paperback', 'hardcover' ) as $format ) {
		$tier = $eval[ $format . '_tier' ];
		if ( $tier < 2 ) {
			continue; // No qualifying bundle for this format in this cart.
		}
		if ( 2 === $tier && $eval['is_mixed_format'] ) {
			continue; // Partial 2-book discount suppressed once mixed.
		}

		$rules = bhp_bundle_rules( $format );
		if ( ! isset( $rules[ $tier ] ) ) {
			continue;
		}

		if ( ! bhp_bundle_prices_match_expected( $cart, $format ) ) {
			// Fail safely: if a live product price no longer matches the
			// approved individual price this fixed-dollar discount was
			// calculated against, skip the discount rather than show a
			// wrong savings amount. Logged so a price drift gets noticed.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					sprintf(
						'Bundle discount skipped for %s: a cart item price does not match the expected $%.2f.',
						$format,
						bhp_bundle_expected_price( $format )
					),
					array( 'source' => 'brave-hearts-bundle-pricing' )
				);
			}
			continue;
		}

		$amount = -1 * $rules[ $tier ]['discount'];
		$label  = sprintf( 'Bundle Savings (%s)', ucfirst( $format ) );

		// taxable = false: the fee reduces the pre-tax subtotal of items
		// that are already taxable, so WooCommerce recalculates tax on the
		// reduced total on its own. Marking the fee itself taxable would
		// double-adjust tax instead of correctly reducing it.
		$cart->add_fee( $label, $amount, false );
	}
}

/**
 * Confirm every cart line item for a format is still priced at the
 * approved individual price before trusting the fixed-dollar discount
 * table. Protects against a future price change on one title silently
 * making a bundle discount wrong.
 */
function bhp_bundle_prices_match_expected( $cart, $format ) {
	$expected = bhp_bundle_expected_price( $format );

	foreach ( $cart->get_cart() as $cart_item ) {
		$match = bhp_bundle_identify_cart_item( $cart_item['product_id'], $cart_item['variation_id'] );
		if ( null === $match || $match[0] !== $format ) {
			continue;
		}
		$price = (float) $cart_item['data']->get_price();
		if ( abs( $price - $expected ) > 0.001 ) {
			return false;
		}
	}
	return true;
}

/**
 * Override the cost of the store's existing flat-rate shipping method
 * according to the approved per-tier shipping table. This never adds a
 * new shipping method and never exposes Bookvault's own live carrier
 * rates — it only edits the cost of the flat rate already shown to every
 * customer, the same rate bhp_remove_bookvault_live_shipping_rates() in
 * the theme's functions.php already protects.
 */
add_filter( 'woocommerce_package_rates', 'bhp_bundle_override_shipping_cost', 20, 2 );
function bhp_bundle_override_shipping_cost( $rates, $package ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $rates;
	}

	$eval = bhp_bundle_evaluate_cart( WC()->cart );

	// An item outside the six approved editions is present — the bundle
	// system doesn't understand what's in the cart, so leave shipping
	// completely alone rather than guess.
	if ( $eval['has_unrelated'] ) {
		return $rates;
	}

	$amount = bhp_bundle_shipping_amount( $eval );
	if ( null === $amount ) {
		return $rates; // Empty cart, or nothing recognized — leave as-is.
	}

	foreach ( $rates as $rate_id => $rate ) {
		// This store runs one flat-rate method per zone (see
		// docs/DECISIONS.md, "Subsidized Shipping"). Overriding its cost
		// here follows the same pattern already used to hide Bookvault's
		// live carrier rates — adjusting the number instead of the rate.
		if ( 'flat_rate' === $rate->method_id ) {
			$rates[ $rate_id ]->cost  = $amount;
			$rates[ $rate_id ]->taxes = array();
		}
	}

	return $rates;
}

/**
 * Work out the single shipping amount for a cart, or null if the cart is
 * empty of approved editions. A mixed paperback + hardcover cart is never
 * eligible for a bundle-tier shipping rate (see bhp_bundle_apply_discount_
 * fees's mixed-format guard above) — instead it uses the flat approved
 * mixed-cart shipping table, priced by total book count rather than by
 * distinct-title tier: $3.99 for exactly two books total, $4.99 for three
 * or more (Staging Refinement Phase 1, item 5). Stays a pure function of
 * $eval (which already carries total_quantity from bhp_bundle_evaluate_
 * cart()) rather than reaching for WC()->cart itself, so it is still
 * directly unit-testable with a stub cart.
 *
 * ⚠ 1.8.23 AMENDS THE PARAGRAPH ABOVE RATHER THAN REPLACING IT. The
 *   mixed-format count table still governs mixed carts that are NOT a
 *   complete collection ($3.99 for two books, $4.99 for three or more,
 *   both unchanged). What changed is that a mixed cart holding all three
 *   distinct adventures is no longer priced by that table at all: it ships
 *   free, before the table is consulted. See the branch below.
 */
function bhp_bundle_shipping_amount( array $eval ) {
	/*
	 * ═══════════════════════════════════════════════════════════════════
	 * ⭐ 1.8.23 — THE COMPLETE COLLECTION SHIPS FREE, FIRST AND ABOVE ALL.
	 * ═══════════════════════════════════════════════════════════════════
	 *
	 * ⭐ THIS BRANCH IS FIRST ON PURPOSE. It has to outrank the mixed-format
	 *    table immediately below it, because that table is a BOOK COUNT and
	 *    it is exactly what would otherwise charge $4.99 to a customer who
	 *    has just bought all three adventures across two formats. Placing it
	 *    lower would leave the ruling half-applied.
	 *
	 * ⭐ IT IS DISTINCT ADVENTURES, NOT BOOKS, and the pair of cases that
	 *    proves it is asserted in tests/test-freeship-collections.php:
	 *      · Everest PB + Amazon PB + Mariana HC  = 3 adventures → $0.00
	 *      · 2x Everest PB      + Mariana HC      = 3 BOOKS, 2 adventures
	 *                                             → $4.99, unchanged
	 *    Same book count, different answer. The rule is "you have the
	 *    collection", never "you spent enough", and `bundle-data.php` has
	 *    said since Phase 4 that two copies of one title never qualify.
	 *
	 * ✅ IT AGREES WITH THE TIER TABLE RATHER THAN OVERRIDING IT. A pure
	 *    3-paperback cart reaches `$rules[3]['shipping']` below, which is
	 *    now 0.00 as well, so both routes return the same figure. There is
	 *    no reading of this function under which one surface can say "free"
	 *    while another charges.
	 *
	 * ⛔ SCOPE, so it is not over-read: this returns a COST for the existing
	 *    flat rate. It adds no shipping method, removes none, and touches no
	 *    WooCommerce zone or setting on any environment. `has_unrelated`
	 *    carts never reach this function at all (the caller returns the
	 *    rates untouched), so an unknown product still cannot be given free
	 *    shipping by accident.
	 */
	if ( ! empty( $eval['is_complete_collection'] ) ) {
		return 0.00;
	}

	if ( $eval['is_mixed_format'] ) {
		return $eval['total_quantity'] <= 2 ? 3.99 : 4.99;
	}

	foreach ( array( 'paperback', 'hardcover' ) as $format ) {
		if ( ! $eval[ 'has_' . $format ] ) {
			continue;
		}
		$tier = $eval[ $format . '_tier' ];
		if ( $tier >= 2 ) {
			$rules = bhp_bundle_rules( $format );
			return $rules[ $tier ]['shipping'];
		}
		return bhp_bundle_single_shipping( $format );
	}
	return null;
}

/**
 * Bundle progress messaging on the cart and checkout pages. Plain
 * informational text only — no percentages, no popups, never blocks
 * checkout (Phase 8).
 */
add_action( 'woocommerce_before_cart_table', 'bhp_bundle_print_progress_messages' );
add_action( 'woocommerce_checkout_before_order_review', 'bhp_bundle_print_progress_messages' );
function bhp_bundle_print_progress_messages() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	// Mixed-format messaging rule (Priority 7): a partial 2-book progress
	// message is suppressed once the opposite format is present (matches
	// the discount-fee suppression above) -- a mixed cart is never called
	// a "bundle" for a partial 2-book offer. A complete 3-book set message
	// always shows regardless of mixing, since that discount always
	// applies once earned.
	$eval     = bhp_bundle_evaluate_cart( WC()->cart );
	$distinct = bhp_bundle_distinct_titles_in_cart( WC()->cart );

	/*
	 * ═══════════════════════════════════════════════════════════════════
	 * ⭐ 1.8.24 (2026-08-05) — THE FREE-SHIPPING LINE NOW LEADS. CYCLE144-LD-14.
	 * ═══════════════════════════════════════════════════════════════════
	 *
	 * Andrew Signore, 2026-08-05: the two-book state was "supposed to say
	 * the Free Shipping info". In 1.8.23 this block ran at the END of the
	 * function, so the shipping fact arrived under the discount sentences.
	 * It is now printed FIRST, and the count===2 per-format progress line is
	 * suppressed while it shows, because the two make the identical ask.
	 *
	 * ⛔ EVERY GUARD FROM 1.8.23 IS CARRIED OVER UNCHANGED, and the
	 *    `has_unrelated` one is the load-bearing one: with an unknown product
	 *    in the cart the shipping override does not run at all, so the
	 *    customer must not be shown a promise the checkout would break. The
	 *    allowlisted activity-book add-on is NOT an unrelated item.
	 *
	 * ⛔ MOVING IT CHANGED THE ORDER, NOT THE CONDITIONS. Same
	 *    `distinct_adventures` thresholds, same copy function, same
	 *    suppression. This is the PHP counterpart of the `unshift` in
	 *    bundle-drawer.js's computeDrawerMeta(); the two surfaces are kept
	 *    identical deliberately.
	 */
	$freeship        = bhp_bundle_freeship_copy();
	$freeship_leads  = empty( $eval['has_unrelated'] ) && 2 === (int) $eval['distinct_adventures'];

	if ( empty( $eval['has_unrelated'] ) ) {
		if ( $freeship_leads ) {
			printf( '<p class="bhp-bundle-message bhp-bundle-message--freeship">%s</p>', esc_html( $freeship['nudge'] ) );
		} elseif ( $eval['distinct_adventures'] >= 3 ) {
			printf( '<p class="bhp-bundle-message bhp-bundle-message--freeship">%s</p>', esc_html( $freeship['earned'] ) );
		}
	}

	$copy     = array(
		'paperback' => array(
			1 => 'Add another paperback and save $1.99.',
			2 => 'You saved $1.99 with your 2-book paperback set. Add the final adventure to complete the series and save $3.98 total.',
		),
		'hardcover' => array(
			1 => 'Add another hardcover and save $2.99.',
			2 => 'You saved $2.99 with your 2-book hardcover set. Complete the hardcover collection and save $4.98 total.',
		),
	);

	foreach ( array( 'paperback', 'hardcover' ) as $format ) {
		$count = count( $distinct[ $format ] );
		if ( 2 === $count && $eval['is_mixed_format'] ) {
			continue; // Partial 2-book progress message suppressed once mixed.
		}
		/*
		 * ⛔ 1.8.24 DELIBERATELY DOES **NOT** SUPPRESS THE count===2 LINE ON
		 *    THIS SURFACE, even though bundle-drawer.js does. The asymmetry
		 *    is intentional and this note exists so it is not "tidied up".
		 *
		 *    In the drawer the two facts are TWO strings — "You saved $1.99
		 *    with your 2-book paperback set." and "Add the final adventure to
		 *    complete the collection and save $3.98 total." — so the ask can
		 *    be dropped while the report of money already saved survives.
		 *    Here they are ONE approved string carrying both. Suppressing it
		 *    would silently delete a true statement about the customer's
		 *    money in order to remove a duplicate ask, and rewriting it would
		 *    be editing approved copy. Leading with the shipping line, which
		 *    is what Andrew asked for, is achieved above without either.
		 */
		if ( isset( $copy[ $format ][ $count ] ) ) {
			printf(
				'<p class="bhp-bundle-message">%s</p>',
				esc_html( $copy[ $format ][ $count ] )
			);
		}
		// 0 or 3 distinct titles: nothing to prompt — either no books of
		// that format are in the cart yet, or the full-set discount is
		// already applied and shown as its own Bundle Savings fee line.
	}

	/*
	 * ⭐ 1.8.24 — THE 1.8.23 FREE-SHIPPING BLOCK USED TO END THIS FUNCTION.
	 *
	 * It has MOVED to the top, above the per-format loop, so the shipping
	 * fact is the first line the customer reads (Andrew Signore, 2026-08-05).
	 * Its guards, thresholds and strings are carried over character for
	 * character — read them there, including the load-bearing
	 * `has_unrelated` suppression and the reason the trigger is distinct
	 * ADVENTURES rather than format or book count.
	 *
	 * ⛔ THE OLD BLOCK'S EARLY `return` ON `has_unrelated` WENT WITH IT, AND
	 *    THAT MATTERED. It sat AFTER the per-format loop, so it never
	 *    suppressed a discount message — it only skipped the shipping lines.
	 *    The replacement wraps the shipping lines in the same condition
	 *    instead of returning, which is the same behaviour without leaving a
	 *    `return` in the middle of a function that now has code after it.
	 */
}

/**
 * Admin UI for the audience-coupon flag (added 1.8.8).
 *
 * Without a visible control, the only way to see whether a coupon carries
 * Collection-only audience scope would be to read postmeta directly, and
 * a coupon created through the normal WooCommerce screens would silently
 * behave as an ordinary store-wide percentage coupon -- which, on a
 * Complete Collection cart, also suppresses the Bundle Savings fee. That
 * failure is invisible until a customer hits it, so the flag gets a real
 * checkbox rather than living only in the database.
 *
 * BHP_AUDIENCE_COUPON_CODES is empty in this repository, so in practice
 * every coupon's scope is editable here. The locked, already-checked
 * rendering is retained for the one case that can still occur: a private
 * environment that has pinned literal codes outside source control. In
 * that case the screen must not claim such a coupon is unscoped, and the
 * checkbox must not be able to strip scope the code itself grants.
 */
add_action( 'woocommerce_coupon_options', 'bhp_audience_coupon_admin_field', 10, 2 );
function bhp_audience_coupon_admin_field( $coupon_id, $coupon ) {
	$is_legacy = ( $coupon instanceof WC_Coupon )
		&& in_array( strtolower( $coupon->get_code() ), BHP_AUDIENCE_COUPON_CODES, true );

	woocommerce_wp_checkbox( array(
		'id'          => 'bhp_audience_coupon',
		'label'       => __( 'Audience coupon (Collection-only)', 'bhp-bundle-pricing' ),
		'description' => $is_legacy
			? __( 'This is a legacy audience coupon, recognised by its code. Always on.', 'bhp-bundle-pricing' )
			: __( 'Restrict this coupon to a complete single-format Complete Collection, and let it stack on top of the Bundle Savings discount. Leave unchecked for an ordinary coupon.', 'bhp-bundle-pricing' ),
		'value'       => ( $is_legacy || 'yes' === get_post_meta( $coupon_id, BHP_AUDIENCE_COUPON_META_KEY, true ) ) ? 'yes' : 'no',
		'custom_attributes' => $is_legacy ? array( 'disabled' => 'disabled' ) : array(),
	) );
}

/**
 * Persist the flag. Privately-pinned literal-code coupons are skipped so
 * a disabled checkbox (which posts nothing) can never strip scope from
 * them. With the list empty, this guard never fires.
 */
add_action( 'woocommerce_coupon_options_save', 'bhp_audience_coupon_admin_save', 10, 2 );
function bhp_audience_coupon_admin_save( $coupon_id, $coupon ) {
	if ( ( $coupon instanceof WC_Coupon )
		&& in_array( strtolower( $coupon->get_code() ), BHP_AUDIENCE_COUPON_CODES, true ) ) {
		return;
	}
	$value = ( isset( $_POST['bhp_audience_coupon'] ) && 'yes' === $_POST['bhp_audience_coupon'] ) ? 'yes' : 'no';
	update_post_meta( $coupon_id, BHP_AUDIENCE_COUPON_META_KEY, $value );
}
