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
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ 1.8.77 (2026-08-31, `CYCLE172-LD-COUPON-DEFECT`) — THE APPLIED-COUPON
 *     LIST IS **NOT** A ZERO-INDEXED LIST, AND THREE PLACES IN THIS FILE
 *     ASSUMED IT WAS. THAT ASSUMPTION CHARGED CUSTOMERS MORE THAN NO COUPON.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT, AS A CUSTOMER SAW IT. Escalated as `CYCLE172-CX-FUNNEL-E2E`
 *    **E-1**, reproduced on PRODUCTION in a real browser on 2026-08-31: on a
 *    three-paperback cart, two of the three audience coupons rendered
 *    *"Your 10% discount code … is applied"* and then charged **$35.97** —
 *    **$3.98 MORE than applying no coupon at all** ($31.99), and **$7.18 more**
 *    than the third audience coupon on the byte-identical cart ($28.79). Both
 *    the *"[CODE] Savings"* line AND the *"Bundle Savings"* line vanished while
 *    the banner still said the discount was on.
 *
 * ⛔ WHAT IT WAS **NOT**, and this is the part that cost the diagnosis time:
 *    it was **not** a difference between the coupon RECORDS. All three
 *    audience coupons' postmeta were confirmed byte-identical
 *    (`percent` · `10` · `individual_use yes` · `exclude_sale_items yes` ·
 *    `_bhp_audience_coupon yes`). Nothing in this plugin branches on a code
 *    STRING. The discriminator was never the coupon. It was the **array key**.
 *
 * ⭐⭐ THE MECHANISM, READ OUT OF WOOCOMMERCE'S OWN SOURCE ON THE SERVER
 *     (`includes/class-wc-cart.php`, WooCommerce 10.9.1) RATHER THAN ASSUMED:
 *
 *       · `WC_Cart::remove_coupon()`  → `unset( $this->applied_coupons[ $key ] )`
 *         — it **unsets**, it does not reindex.
 *       · `WC_Cart::set_applied_coupons()` → `$this->applied_coupons = (array) $value`
 *         — no reindex on the way in either.
 *       · `WC_Cart::apply_coupon()` → `$this->applied_coupons[] = $coupon_code`
 *         — `[]` appends at *max integer key + 1*, which `unset()` does not reset.
 *
 *     So the moment an `individual_use` coupon replaces another **inside one
 *     request** — which is exactly what `apply_coupon()` does, it calls
 *     `remove_coupon()` on the incumbent and then appends — the cart holds
 *     `array( 1 => 'the-new-code' )`. There is no element `0`. And because
 *     `WC_Cart_Session` persists and restores that array verbatim, **the gap
 *     survives every subsequent page load for the rest of the session.**
 *
 * ⛔ WHY THAT PRODUCED THE WORST POSSIBLE SHAPE OF FAILURE. Three call sites
 *    read `$applied[0]`, and the two that decide MONEY both went quiet, while
 *    the one that decides APPEARANCE kept working:
 *      · `bhp_audience_coupon_apply_savings_fee()` — no *"[CODE] Savings"* fee.
 *      · `bhp_bundle_apply_discount_fees()`        — no *"Bundle Savings"* fee,
 *        because its `$only_audience_coupon` test also read index 0. **The
 *        customer therefore lost a discount they would have had with NO coupon
 *        at all.**
 *      · `bhp_audience_coupon_zero_native_discount()` reads the coupon OBJECT,
 *        never an index, so it kept zeroing WooCommerce's own 10% — which is
 *        why the Store API reported `total_discount: "0"` and nothing anywhere
 *        said "error".
 *    Silent, self-consistent, and wrong in the customer's disfavour.
 *
 * ⭐ REPRODUCED DETERMINISTICALLY ON STAGING BEFORE ANY FIX WAS WRITTEN
 *    (`wp eval-file`, theme 1.19.342 / plugin 1.8.76 / WC 10.9.1, cart
 *    334 + 15 + 18, subtotal $35.97). Same coupon, same cart, key `0` vs key `1`:
 *      · key 0 → `[CODE] Savings -3.20` + `Bundle Savings (Paperback) -3.98`
 *        (⛔ the code itself is redacted here on purpose — this repository is
 *        public and BHP-AGENT-STANDING-RULES §4.1 makes coupon codes private.
 *        The unredacted evidence is in the private release folder.)
 *      · key 1 → **no fees at all**, and PHP emitted
 *        `Warning: Undefined array key 0 … bundle-cart.php on line 715`.
 *    The warning had been there the whole time, invisible on a production
 *    install with display_errors off.
 *
 * ⛔ THE FIX IS THIS ONE FUNCTION, AND EVERY READER GOES THROUGH IT. Returning
 *    `array_values()` makes "the first applied coupon" mean the first applied
 *    coupon rather than "whatever happens to sit at offset zero". It is
 *    deliberately NOT a per-site `isset()` patch: three sites drifted apart
 *    once already, and a fourth reader added later would reintroduce the bug
 *    exactly. ⭐ It also cannot mask a real problem — a cart with no coupons
 *    still returns `array()`, and callers still assert `1 === count()`.
 *
 * ⚠ NO COUPON RECORD WAS READ FOR THIS DIAGNOSIS AND NONE WAS CHANGED, on any
 *   environment. The defect was always in this file.
 *
 * @param object|WC_Cart $cart Any cart-like object.
 * @return string[] Applied coupon codes, densely reindexed from 0.
 */
function bhp_cart_applied_coupons( $cart ) {
	if ( ! is_object( $cart ) || ! is_callable( array( $cart, 'get_applied_coupons' ) ) ) {
		return array();
	}
	return array_values( (array) $cart->get_applied_coupons() );
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
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ 1.8.46 (2026-08-14, `CYCLE161-LD-TYP-AND-GUARANTEE`) — RENDERING AN
 *     AUDIENCE COUPON CODE ON A PAGE. READ ALL OF THIS BEFORE CALLING IT.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS FUNCTION EXISTS AGAINST A FROZEN POLICY, AND THAT IS WHY IT IS
 *    OFF EVERYWHERE BY DEFAULT AND WHY THIS COMMENT IS THIS LONG.
 *
 *    `brave-hearts-theme/docs/ENGINEERING/FUNNEL_CONSTITUTION.md`, Frozen
 *    2026-07-14, VERBATIM: "Do not hardcode or publicly render
 *    audience-specific coupon codes in themes, plugins, landing pages,
 *    posts, or navigation. … No product page, collection page, homepage,
 *    navigation, footer, blog article, landing page, banner, or static
 *    promotional copy may advertise a coupon code."
 *
 *    The same document records the WORKED PRECEDENT: on 2026-07-14 the
 *    Complete Collection page rendered exactly such a line and it was
 *    REMOVED as a defect (plugin 1.8.2 -> 1.8.3). The 2026-08-04 owner
 *    amendment moved the code to Emails 1 and 2 — still EMAIL ONLY — and
 *    restated that "mandatory purchase suppression stands in full and must
 *    gate the coupon wherever it appears."
 *
 *    The 2026-08-14 build brief asks for a coupon line on the Adventure Kit
 *    thank-you page, which is a page template. ⛔ THAT CONFLICT IS NOT
 *    RESOLVED HERE AND MUST NOT BE. Per the standing refusal duty, both
 *    sources are recorded and the decision is routed to Andrew. What is
 *    built is the mechanism, wired, tested, and STOPPED WITH A FINGER OVER
 *    THE BUTTON: it renders nothing at all until an environment operator
 *    sets the option below, and no environment ships with it set.
 *
 * ⛔ NO COUPON CODE LITERAL ENTERS THIS PUBLIC REPOSITORY. The code is read
 *    from a per-environment site option — the same shape 1.8.29 used to get
 *    the literal code list and the unit-economics amounts out of the
 *    published source tree, and required by BHP-AGENT-STANDING-RULES §4.1
 *    ("coupon codes and values" are PRIVATE) and by conflict C6.
 *
 * ⛔ IT FAILS CLOSED ON EVERY DISAGREEMENT WITH LIVE STATE, so a page can
 *    never advertise a coupon the cart will refuse. Every one of these is
 *    read from the live coupon record at render time, never remembered:
 *      - the option is set and non-empty;
 *      - a coupon record with that code exists;
 *      - it is `publish` (not draft, not trashed);
 *      - it is not expired;
 *      - its type is `percent` and its amount is > 0  (the percentage
 *        PRINTED is this number — it is never typed into copy);
 *      - it carries the audience-coupon opt-in meta flag, i.e. it is one of
 *        the coupons this plugin actually lets stack on the collection;
 *      - its minimum spend, if any, does not exceed the price of the
 *        Complete Collection in the format being advertised — otherwise the
 *        offer is unreachable on the very cart it is being shown beside.
 *
 * VERIFIED LIVE ON STAGING 2026-08-14 by WP-CLI (`wp post list
 * --post_type=shop_coupon`, `wp post meta list <id>`) for the parent
 * audience coupon record: `post_status` publish · `discount_type` percent ·
 * `coupon_amount` 10 · `individual_use` yes · `usage_limit` 0 ·
 * `usage_limit_per_user` 1 · `exclude_sale_items` yes ·
 * `_bhp_audience_coupon` yes · NO `minimum_amount` meta · NO `date_expires`
 * meta. So: 10%, no minimum spend, no expiry.
 *
 * ⚠ ITS REAL FLOOR IS NOT A DOLLAR AMOUNT, IT IS A CART SHAPE, and that is
 *   worth more than the missing `minimum_amount`: `bhp_audience_coupon_
 *   cart_qualifies()` above requires a complete, single-format,
 *   three-title collection with no unrelated and no sale items. Any copy
 *   this function feeds must therefore say what the discount applies TO.
 *
 * @param string $format 'paperback'|'hardcover' — the collection the copy
 *                       sits beside, used for the minimum-spend check.
 * @return array{code:string,percent:float,minimum:float}|null
 */
function bhp_audience_coupon_public_notice( $format = 'paperback' ) {
	return bhp_audience_coupon_resolve( get_option( 'bhp_audience_coupon_public_code', '' ), $format );
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * THE ONE VALIDATOR. 1.8.47 (2026-08-17, `CYCLE162-LD-TYP-V2`) extracted it
 * from `bhp_audience_coupon_public_notice()` above, unchanged line for line.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHY IT IS ONE FUNCTION AND NOT TWO. There are now TWO independent
 *    per-environment gates that name an audience coupon — the public NOTICE
 *    (`bhp_audience_coupon_public_code`, which renders a code on a page) and
 *    the AUTO-APPLY (`bhp_typ_auto_coupon`, which applies one to a cart).
 *    They are deliberately separate options with separate consequences, but
 *    they must never disagree about whether a given coupon record is fit to
 *    use. One validator is how that is guaranteed rather than hoped for.
 *
 * ⛔ IT FAILS CLOSED ON EVERY DISAGREEMENT WITH LIVE STATE. Every condition
 *    is read from the live coupon record at call time, never remembered.
 *
 * @param string $code   The candidate code. Never a literal in this file.
 * @param string $format 'paperback'|'hardcover' — the collection the caller
 *                       sits beside, used for the minimum-spend check.
 * @return array{code:string,percent:float,minimum:float}|null
 */
function bhp_audience_coupon_resolve( $code, $format = 'paperback' ) {
	$code = trim( (string) $code );
	if ( '' === $code || ! class_exists( 'WC_Coupon' ) ) {
		return null;
	}

	$coupon = new WC_Coupon( $code );
	if ( ! $coupon->get_id() || 'publish' !== get_post_status( $coupon->get_id() ) ) {
		return null;
	}
	if ( ! bhp_is_audience_coupon( $coupon ) ) {
		return null;
	}
	if ( 'percent' !== $coupon->get_discount_type() ) {
		return null;
	}
	$percent = (float) $coupon->get_amount();
	if ( $percent <= 0 ) {
		return null;
	}
	$expiry = $coupon->get_date_expires();
	if ( $expiry && $expiry->getTimestamp() < time() ) {
		return null;
	}

	$minimum = (float) $coupon->get_minimum_amount();
	if ( $minimum > 0 && function_exists( 'bhp_bundle_landing_price_facts' ) ) {
		$facts = bhp_bundle_landing_price_facts( 'hardcover' === $format ? 'hardcover' : 'paperback' );
		if ( $minimum > (float) $facts['bundle'] ) {
			return null; // unreachable on the cart this copy sits beside.
		}
	}

	return array(
		'code'    => strtoupper( $coupon->get_code() ),
		'percent' => $percent,
		'minimum' => $minimum,
	);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.8.47 (2026-08-17, `CYCLE162-LD-TYP-V2`) — THE AUTO-APPLIED WELCOME
 *     DISCOUNT. Andrew, verbatim, relayed through the Chief of Staff and NOT
 *     witnessed by this agent: *"if they click get collection it auto applies
 *     the discount so they have a 2 click path to purchase no need to add the
 *     coupon code in"*.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * THE PATH, END TO END, AND WHERE EACH PIECE LIVES:
 *
 *   1. The Adventure Kit thank-you page renders its CTA with `?bhp_offer=
 *      welcome` — a FIXED, NEUTRAL LITERAL. It is not the code, it is not
 *      derived from the code, and it discloses nothing. The param is only
 *      added when the offer actually resolves live, so a dead option
 *      produces the byte-identical pre-1.19.229 link.
 *   2. `bhp_typ_capture_auto_coupon_intent()` (below, on `template_redirect`)
 *      turns that param into a WC SESSION FLAG. ⛔ The flag is a BOOLEAN, not
 *      the code — the code is re-resolved from the option every single time
 *      it is needed, so a rotated, expired or unflagged coupon can never be
 *      carried around inside a stale session.
 *   3. `bhp_typ_maybe_apply_auto_coupon()` applies it the moment the cart is
 *      a genuine qualifying Complete Collection, and never before.
 *
 * ⛔ WHY IT CANNOT BE APPLIED AT STEP 2. `bhp_validate_audience_coupon_scope()`
 *    (below) throws on any cart that is not a complete single-format
 *    collection — including an EMPTY one. Applying on page view would fail
 *    validation and push a red error notice at a visitor who has done nothing
 *    wrong. Intent is stored; application waits for the cart.
 *
 * ⛔ NO COUPON CODE LITERAL ENTERS THIS PUBLIC REPOSITORY. The code is read
 *    from the per-environment `bhp_typ_auto_coupon` site option — the shape
 *    1.8.29 established and BHP-AGENT-STANDING-RULES §4.1 requires ("coupon
 *    codes and values" are PRIVATE). Conflict C6 is the live instance of what
 *    happens when they are not.
 *
 * ⛔ IT IS OFF ON EVERY ENVIRONMENT UNTIL AN OPERATOR SETS THE OPTION, and no
 *    release seeds it. Production seeding is an explicit, separate act.
 *
 * ⛔⛔ THE POLICY CONFLICT IS RECORDED, NOT RESOLVED. `docs/ENGINEERING/
 *     FUNNEL_CONSTITUTION.md`'s Audience Coupon Policy (Frozen 2026-07-14,
 *     amended 2026-08-04) delivers audience coupons through EMAIL, gated by
 *     mandatory purchase suppression. Auto-applying one from a page template
 *     is a different delivery route and is Andrew's call, not this file's.
 *     Nothing here advertises a CODE — the code is never rendered by the
 *     auto-apply path — but the conflict is real and is escalated.
 */
if ( ! defined( 'BHP_TYP_AUTO_COUPON_OPTION' ) ) {
	define( 'BHP_TYP_AUTO_COUPON_OPTION', 'bhp_typ_auto_coupon' );
}
if ( ! defined( 'BHP_TYP_AUTO_COUPON_SESSION_KEY' ) ) {
	define( 'BHP_TYP_AUTO_COUPON_SESSION_KEY', 'bhp_typ_auto_coupon_intent' );
}
if ( ! defined( 'BHP_TYP_AUTO_COUPON_PARAM' ) ) {
	define( 'BHP_TYP_AUTO_COUPON_PARAM', 'bhp_offer' );
}
if ( ! defined( 'BHP_TYP_AUTO_COUPON_PARAM_VALUE' ) ) {
	define( 'BHP_TYP_AUTO_COUPON_PARAM_VALUE', 'welcome' );
}

/**
 * The live, validated auto-apply coupon record, or null.
 *
 * Shares `bhp_audience_coupon_resolve()` with the public-notice gate, so the
 * two can never disagree about whether a coupon record is fit to use.
 *
 * @param string $format 'paperback'|'hardcover'.
 * @return array{code:string,percent:float,minimum:float}|null
 */
function bhp_typ_auto_coupon_record( $format = 'paperback' ) {
	return bhp_audience_coupon_resolve( get_option( BHP_TYP_AUTO_COUPON_OPTION, '' ), $format );
}

/**
 * Everything a page needs to state the OUTCOME honestly, with no code in it.
 *
 * ⛔ THE DISPLAYED NUMBER AND THE CHARGED NUMBER COME OUT OF THE SAME
 *    FUNCTION. `savings` here is `bhp_audience_coupon_savings_for_format()`,
 *    which is literally what `bhp_audience_coupon_apply_savings_fee()` adds to
 *    the cart. A page cannot quote a total the cart will not honour, because
 *    there is only one place the total is computed.
 *
 * @param string $format 'paperback'|'hardcover'.
 * @return array{code:string,percent:float,bundle:float,savings:float,effective:float}|null
 */
function bhp_typ_auto_coupon_offer( $format = 'paperback' ) {
	$format = ( 'hardcover' === $format ) ? 'hardcover' : 'paperback';
	$record = bhp_typ_auto_coupon_record( $format );
	if ( ! $record || ! function_exists( 'bhp_bundle_landing_price_facts' ) ) {
		return null;
	}
	$facts   = bhp_bundle_landing_price_facts( $format );
	$savings = bhp_audience_coupon_savings_for_format( $format, $record['percent'] );
	if ( $savings <= 0 ) {
		return null;
	}
	return array(
		'code'      => $record['code'],
		'percent'   => $record['percent'],
		'bundle'    => (float) $facts['bundle'],
		'savings'   => $savings,
		'effective' => round( (float) $facts['bundle'] - $savings, 2 ),
	);
}

/**
 * Turn the CTA's neutral param into a session intent.
 *
 * ⛔ IT STORES A BOOLEAN, NEVER THE CODE. See the block comment above.
 * ⛔ IT SETS NOTHING unless a live coupon actually resolves, so a stale
 *    bookmark carrying the param on an environment with the option unset is
 *    an ordinary page view with an ignored query string.
 * ⭐ IT IS NOT SCOPED TO ONE PAGE ON PURPOSE — the CTA points at
 *    /complete-collection/ today, and the moment it points anywhere else this
 *    keeps working. The param is the contract, not the destination.
 */
add_action( 'template_redirect', 'bhp_typ_capture_auto_coupon_intent', 5 );
function bhp_typ_capture_auto_coupon_intent() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only intent flag from a public link; it grants nothing a customer could not get by typing the code.
	if ( empty( $_GET[ BHP_TYP_AUTO_COUPON_PARAM ] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( BHP_TYP_AUTO_COUPON_PARAM_VALUE !== sanitize_key( wp_unslash( $_GET[ BHP_TYP_AUTO_COUPON_PARAM ] ) ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	if ( ! bhp_typ_auto_coupon_record() ) {
		return;
	}
	WC()->session->set( BHP_TYP_AUTO_COUPON_SESSION_KEY, 1 );
	if ( is_callable( array( WC()->session, 'set_customer_session_cookie' ) ) ) {
		WC()->session->set_customer_session_cookie( true );
	}
	// The intent may fire on a cart the customer already completed elsewhere.
	bhp_typ_maybe_apply_auto_coupon();
}

/**
 * Apply the welcome discount if — and only if — every gate is open.
 *
 * ⛔ IT PRE-VALIDATES BEFORE APPLYING, and that is the difference between a
 *    silent no-op and a red error box on someone's checkout.
 *    `WC_Cart::apply_coupon()` pushes a customer-facing error notice when it
 *    refuses (usage limit reached, expired, restricted). Nobody asked for
 *    this coupon out loud, so nobody should be told off for it:
 *    `WC_Discounts::is_coupon_valid()` answers the same question silently and
 *    runs the identical validation stack, including this plugin's own
 *    `woocommerce_coupon_is_valid` scope filter.
 *
 * ⛔ THE INTENT IS KEPT, NOT BURNED, WHEN THE CART MERELY DOES NOT QUALIFY
 *    YET. A visitor who arrives with intent, adds one book, then completes
 *    the collection still gets the discount. It is cleared only when it is
 *    spent or provably dead.
 *
 * @return bool True only if a coupon was applied by this call.
 */
function bhp_typ_maybe_apply_auto_coupon() {
	static $running = false;
	if ( $running ) {
		return false;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
		return false;
	}
	if ( ! WC()->session->get( BHP_TYP_AUTO_COUPON_SESSION_KEY ) ) {
		return false;
	}

	$record = bhp_typ_auto_coupon_record();
	if ( ! $record ) {
		// Option cleared, coupon deleted/unpublished/expired/re-typed, or the
		// audience flag removed. Drop the intent rather than carry a dead flag.
		WC()->session->set( BHP_TYP_AUTO_COUPON_SESSION_KEY, null );
		return false;
	}
	$code = $record['code'];

	if ( WC()->cart->has_discount( $code ) ) {
		WC()->session->set( BHP_TYP_AUTO_COUPON_SESSION_KEY, null );
		return false;
	}
	if ( ! bhp_audience_coupon_cart_qualifies( WC()->cart ) ) {
		return false; // Not yet a collection. Keep the intent.
	}
	if ( ! class_exists( 'WC_Discounts' ) ) {
		return false;
	}

	$discounts = new WC_Discounts( WC()->cart );
	$valid     = $discounts->is_coupon_valid( new WC_Coupon( $code ) );
	if ( true !== $valid ) {
		// Genuinely refused (usage limit, per-user limit, restriction). Silent,
		// and the intent is spent so it is not retried on every page load.
		WC()->session->set( BHP_TYP_AUTO_COUPON_SESSION_KEY, null );
		return false;
	}

	$running = true;
	$applied = WC()->cart->apply_coupon( $code );
	$running = false;

	if ( $applied ) {
		WC()->session->set( BHP_TYP_AUTO_COUPON_SESSION_KEY, null );
	}
	return (bool) $applied;
}

/**
 * The two safety nets around the primary path.
 *
 * The primary path is `bhp_bundle_handle_add_to_cart()`, which calls the
 * function directly after the three books are in and before the redirect —
 * fully server-side, one request, deterministic.
 *
 * These cover the rest of the store honestly rather than assuming one route:
 *   · `woocommerce_add_to_cart` — the cart drawer, the Store API's own
 *     add-item route, and any future add path. Fires per item, so it lands on
 *     the third book, which is the first moment the cart qualifies.
 *   · `woocommerce_check_cart_items` — the cart and checkout renders, classic
 *     and Blocks. Covers the visitor who ALREADY had the collection in the
 *     cart before clicking the thank-you CTA.
 * Both are no-ops without a session intent, and the whole function is a no-op
 * once the coupon is on.
 */
add_action( 'woocommerce_add_to_cart', 'bhp_typ_auto_coupon_net', 99 );
add_action( 'woocommerce_check_cart_items', 'bhp_typ_auto_coupon_net', 99 );
function bhp_typ_auto_coupon_net() {
	bhp_typ_maybe_apply_auto_coupon();
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
 * The audience-coupon savings for one format at one percentage: that
 * percentage of the Complete Collection price AFTER its own $3.98/$4.98
 * discount is already applied — i.e. stacked ON TOP OF, not instead of, the
 * existing Collection savings.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.8.47 (2026-08-17, `CYCLE162-LD-TYP-V2`) — EXTRACTED, AND THE `0.10`
 *    LITERAL IT USED TO CONTAIN IS GONE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHY THE LITERAL HAD TO GO. Until 1.8.46 this arithmetic hardcoded a 10%
 *    rate while the coupon record carried its own `coupon_amount`. They agree
 *    today (PARENT-audience coupon, `coupon_amount` 10, verified live on
 *    staging 2026-08-17 by WP-CLI) — but a page that quotes an effective
 *    price has to compute it from the same expression the cart charges from,
 *    and a hardcoded rate makes those two things silently divergeable by a
 *    coupon edit nobody re-deploys for. The percentage now comes off the live
 *    record in both directions.
 *
 * ⛔ BEHAVIOUR IS UNCHANGED AT 10%. `round($p * 0.10, 2)` and
 *    `round($p * (10/100), 2)` are the same computation; no cart total moves
 *    unless Andrew changes the coupon's own amount, which is his gate and
 *    would be the correct outcome if he did.
 *
 * @param string $format  'paperback'|'hardcover'.
 * @param float  $percent The coupon's own live amount, e.g. 10.0.
 * @return float
 */
function bhp_audience_coupon_savings_for_format( $format, $percent ) {
	$percent = (float) $percent;
	if ( $percent <= 0 ) {
		return 0.0;
	}
	$format           = ( 'hardcover' === $format ) ? 'hardcover' : 'paperback';
	$rules            = bhp_bundle_rules( $format );
	$combined         = 3 * bhp_bundle_expected_price( $format );
	$collection_price = $combined - $rules[3]['discount'];
	return round( $collection_price * ( $percent / 100 ), 2 );
}

/**
 * The exact audience-coupon savings amount for a qualifying cart. Returns
 * 0.0 if the cart doesn't qualify, or if the applied coupon is not a
 * positive percentage coupon.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ 1.8.48 (2026-08-17, `CYCLE162-LD-TYP-V2-QA`) — THIS FUNCTION READS THE
 *    CART OBJECT NOW, AND 1.8.47 FORGOT TO SAY SO OUT LOUD.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Until 1.8.46 it was a PURE function of `bhp_bundle_rules()` and
 * `bhp_bundle_expected_price()` — it read cart LINES only through
 * `bhp_audience_coupon_qualifying_format()` and never called a cart method.
 * `tests/test-addon-free-collection.php` §3 relied on exactly that, passing a
 * lightweight cart double that implements `get_cart()` and nothing else.
 *
 * 1.8.47 added the `get_applied_coupons()` call below to take the percentage
 * off the live coupon instead of a `0.10` literal — correct in substance, but
 * it turned every caller holding a non-`WC_Cart` cart object into an
 * UNCAUGHT `Error`. OBSERVED, not inferred: `wp eval-file` of
 * `test-addon-free-collection.php` on staging 2026-08-17 died with
 * *"Call to undefined method BHP_AFC_Cart::get_applied_coupons()"* at this
 * line, aborting 40-odd later assertions in that suite.
 *
 * ⛔ NO LIVE CART EVER TOOK THIS PATH. The only production caller is
 *    `bhp_audience_coupon_apply_savings_fee()` on
 *    `woocommerce_cart_calculate_fees`, which WooCommerce always hands a real
 *    `WC_Cart`. This was a test-visible fatal, not a customer-visible one —
 *    but a suite that cannot finish cannot protect anything, so it is fixed
 *    rather than explained away.
 *
 * ⭐ THE GUARD IS `is_callable`, NOT A `WC_Cart` TYPE CHECK, deliberately: a
 *    cart object that cannot report its coupons has, by definition, no
 *    coupon to take a percentage from, and 0.0 is the honest answer for it.
 */
function bhp_audience_coupon_savings_amount( $cart ) {
	$format = bhp_audience_coupon_qualifying_format( $cart );
	if ( ! $format ) {
		return 0.0;
	}
	if ( ! is_callable( array( $cart, 'get_applied_coupons' ) ) ) {
		return 0.0;
	}
	// 1.8.77: densely reindexed. See bhp_cart_applied_coupons() for why
	// `$applied[0]` was not a safe expression and what it cost.
	$applied = bhp_cart_applied_coupons( $cart );
	if ( 1 !== count( $applied ) || ! class_exists( 'WC_Coupon' ) ) {
		return 0.0;
	}
	$coupon = new WC_Coupon( $applied[0] );
	if ( ! $coupon->get_id() || 'percent' !== $coupon->get_discount_type() ) {
		return 0.0;
	}
	return bhp_audience_coupon_savings_for_format( $format, (float) $coupon->get_amount() );
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
	// 1.8.77: densely reindexed. See bhp_cart_applied_coupons(). Before this,
	// an individual_use swap inside one request left the code at key 1 and this
	// guard read a non-existent key 0 -- so the savings fee silently vanished.
	$applied = bhp_cart_applied_coupons( $cart );
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
	// 1.8.77: densely reindexed. See bhp_cart_applied_coupons(). This line is
	// where the customer-visible loss actually happened: reading a missing key 0
	// made $only_audience_coupon false, which suppressed Bundle Savings entirely
	// -- so applying an audience coupon cost MORE than applying none.
	$applied_coupons = bhp_cart_applied_coupons( $cart );
	if ( ! empty( $applied_coupons ) ) {
		$only_audience_coupon = ( 1 === count( $applied_coupons ) ) && bhp_is_audience_coupon_code( $applied_coupons[0] );
		if ( ! $only_audience_coupon || ! bhp_audience_coupon_cart_qualifies( $cart ) ) {
			return;
		}
	}

	$eval = bhp_bundle_evaluate_cart( $cart );

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.8.61 — THE `has_unrelated` GUARD. ⛔ THE DEFECT THIS CLOSES IS
	 *     `CYCLE165-OPS-018`, AND IT WAS PRE-EMPTED, NOT FOUND LIVE.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔⛔ THIS FUNCTION HAD NO SUCH GUARD, AND ITS ABSENCE WAS ASYMMETRIC IN
	 *    THE ONE DIRECTION THAT PRODUCES A FALSE CLAIM.
	 *    `bhp_bundle_override_shipping_cost()` has bailed on `has_unrelated`
	 *    since the tiered table shipped, deliberately, rather than guess a
	 *    shipping amount for a cart it does not understand. This function did
	 *    not -- so an unrelated item took the FREE SHIPPING away while leaving
	 *    the -$3.98 DISCOUNT in place, and the collection page's own "FREE
	 *    shipping" promise kept rendering because it is produced by
	 *    `bhp_bundle_rules()` BEFORE any cart exists.
	 *
	 * ⭐ THE CUSTOMER-FACING RESULT WAS: the site promises free shipping on
	 *    the complete collection, the shopper adds the collection plus one
	 *    unrecognised item, and is charged for shipping -- while the cart's
	 *    own progress copy goes QUIET, because
	 *    `bhp_bundle_print_progress_messages()` DOES check `has_unrelated`.
	 *    Less discoverable, not more.
	 *
	 * ⭐⭐ WHY IT COULD NOT WAIT: no code change was required to trigger it.
	 *    CREATING A PRODUCT RECORD WAS SUFFICIENT. The colouring line made
	 *    that imminent, which is why `ACT-OPS-269` requires this fix to ship
	 *    BEFORE the first colouring product record on ANY environment,
	 *    staging included.
	 *
	 * ⛔ NOTE WHAT THE GUARD DOES *NOT* DO TO THE COLOURING LINE. From 1.8.61
	 *    a colouring book is RELATED (`bhp_bundle_cart_has_unrelated_items()`),
	 *    so a collection cart holding one still earns its discount AND still
	 *    earns its free shipping. The promise stays TRUE. This guard bites
	 *    only on a genuinely unrecognised product -- and there it fails SAFE
	 *    in both directions at once: no discount AND no free-shipping promise,
	 *    which is the only combination that cannot lie to a customer.
	 *
	 * ⚠ IT IS A REAL COMMERCIAL MOVEMENT AND IS REPORTED AS ONE, NOT AS A
	 *   TIDY-UP: a cart that today receives a bundle discount alongside an
	 *   unrecognised product will, from 1.8.61, receive none. ⭐ NO SUCH
	 *   PRODUCT EXISTS ON EITHER ENVIRONMENT TODAY -- verified over SSH this
	 *   session: staging holds 9 product records, all six catalogue editions
	 *   plus the allowlisted Activity Book plus two drafts. So the guard is
	 *   INERT on the current store and changes no live order.
	 */
	if ( ! empty( $eval['has_unrelated'] ) ) {
		return;
	}

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
	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.8.61 · BRANCH A — ANY THREE PHYSICAL BOOKS SHIP FREE.
	 *     ⛔⛔ OFF BY DEFAULT. THE POLICY IS AN OPEN FOUNDER DECISION.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ Read `bhp_bundle_colouring_policy()` in bundle-data.php before
	 *    touching this branch. Two sources disagree about whether the
	 *    any-three rule is ruled: `00A-WHAT-GOVERNS-TODAY.md` records the
	 *    colouring-in-free-shipping policy ⛔ OPEN as of 2026-08-20T09:0x-0600,
	 *    while the build brief relays founder carrier items 158/159 ruling it.
	 *    ⭐ RECORDED, NOT RESOLVED. The stricter reading is the default.
	 *
	 * ⭐ WHEN ENABLED, THIS BRANCH IS FIRST FOR THE SAME REASON THE COLLECTION
	 *    BRANCH BELOW IT IS: it has to outrank the mixed-format COUNT table,
	 *    which is exactly what would otherwise charge $4.99 to a customer who
	 *    has just bought three books. ⭐ It makes the $4.99 row UNREACHABLE --
	 *    "the $4.99 row dies" -- rather than deleting the row, so a reader can
	 *    still see what the table used to say and why it stopped applying.
	 *
	 * ⭐ DUPLICATES COUNT, AND THAT IS THE WHOLE DIFFERENCE FROM BRANCH B.
	 *    `physical_book_count` is a QUANTITY; `distinct_adventures` is a SET.
	 *    Three copies of one title is three books in one box and ships free
	 *    under this policy, while remaining TWO-books-short of a collection
	 *    for every DISCOUNT purpose -- the discount tables are untouched by
	 *    this branch and still require distinct titles.
	 */
	if ( 'any-three' === bhp_bundle_colouring_policy() && ! empty( $eval['physical_book_count'] ) && (int) $eval['physical_book_count'] >= 3 ) {
		return 0.00;
	}

	/* ⭐ BRANCH B — the complete collection ships free. 1.8.23, UNCHANGED. */
	if ( ! empty( $eval['is_complete_collection'] ) ) {
		return 0.00;
	}

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐ 1.8.61 · BRANCH C — A COLOURING BOOK IS IN THE CART.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⭐⭐ NOT ONE NEW TIER NUMBER IS INVENTED HERE. The colouring line is a
	 *    single-format physical paperback-class book, so a colouring-bearing
	 *    cart is priced on the EXISTING PAPERBACK LADDER by PHYSICAL BOOK
	 *    COUNT, and every figure below is read from the approved tables rather
	 *    than written down again:
	 *
	 *      1 book  -> `bhp_colouring_single_shipping()` = $2.99   ⭐ 1.8.66
	 *                 (was `bhp_bundle_single_shipping('paperback')` = $1.99)
	 *      2 books -> `bhp_bundle_rules('paperback')[2]['shipping']` = $2.99
	 *      3+      -> $4.99, the pre-existing mixed-cart 3-or-more row,
	 *                 reachable ONLY under the `conservative` policy (under
	 *                 `any-three`, branch A has already returned $0.00)
	 *
	 * ⚠️⚠️ THREE READINGS ARE FLAGGED RATHER THAN PRESENTED AS SETTLED. The
	 *    existing table is genuinely ambiguous for a colouring item and this
	 *    build chose the PARALLEL reading in each case, per the brief's
	 *    instruction to choose the parallel and flag it:
	 *      1. A single colouring book ships at $1.99, like a single paperback,
	 *         NOT at the hardcover $2.99. Basis: it is a paperback binding.
	 *         ⭐⭐ SUPERSEDED 1.8.66 BY FOUNDER RULING, CARRIER ITEM 195: a
	 *         single colouring book ships at $2.99. ⛔ The sentence above is
	 *         preserved verbatim rather than corrected in place, so a reader
	 *         can see that $1.99 was a REASONED READING flagged for him and
	 *         not an oversight, and that he then ruled against it. The figure
	 *         now comes from `bhp_colouring_single_shipping()`.
	 *      2. One chapter paperback + one colouring book ships at $2.99, like
	 *         two distinct paperbacks -- NOT at the mixed-format $3.99, which
	 *         exists to price a paperback-plus-HARDCOVER cart.
	 *      3. Two copies of ONE colouring book ships at $2.99 (count), not
	 *         $1.99. ⭐ This deliberately DIFFERS from the chapter line, where
	 *         two copies of one title fall through to the single rate -- a
	 *         pre-existing quirk of a table keyed on DISTINCT titles. Two
	 *         printed books cost two books of postage, and the colouring
	 *         ladder is a COUNT, so it does not inherit the quirk.
	 *    ⛔ NONE of these is a founder ruling. All three are reported.
	 *
	 * ⛔ A HARDCOVER IN THE CART STILL WINS. If the cart also holds a
	 *    hardcover, the mixed-format table below is the honest answer and this
	 *    branch stands aside -- a colouring book does not make a hardcover
	 *    cart cheaper to ship.
	 */
	if ( ! empty( $eval['has_colouring'] ) && empty( $eval['has_hardcover'] ) ) {
		$books = (int) $eval['physical_book_count'];
		if ( $books <= 1 ) {
			/*
			 * ⭐ 1.8.66 — founder carrier item 195. The single colouring row is
			 *    the ONLY row this ruling moves; every branch above and below
			 *    is byte-unchanged, and a single chapter paperback still reads
			 *    $1.99 from `bhp_bundle_single_shipping()` further down.
			 */
			return bhp_colouring_single_shipping();
		}
		if ( 2 === $books ) {
			$paperback_rules = bhp_bundle_rules( 'paperback' );
			return $paperback_rules[2]['shipping'];
		}
		return 4.99;
	}

	if ( $eval['is_mixed_format'] || ! empty( $eval['has_colouring'] ) ) {
		/*
		 * ⭐ 1.8.61 widens the COUNT this table reads from `total_quantity`
		 *    (catalogue editions only) to `physical_book_count` (catalogue +
		 *    colouring). ⛔ On a cart with no colouring book the two are equal
		 *    by construction, so every pre-existing mixed-cart answer is
		 *    unchanged. Reached now only when a hardcover is present, since
		 *    branch C handles the paperback-class colouring carts above.
		 */
		return $eval['physical_book_count'] <= 2 ? 3.99 : 4.99;
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
