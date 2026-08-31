<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * COUPON LINKS — `?coupon=<code>` APPLIES AN EXISTING COUPON. 1.19.331,
 * `CYCLE170-LD-TRIPLE`, carrier items 504 / 505. STAGING BUILD.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, carrier items 504 and 505, ⛔ RELAYED THROUGH GANDALF, NOT
 * WITNESSED BY THIS FILE'S AUTHOR: a link carrying a discount code should apply
 * the discount by itself, so a newsletter or a card can send someone straight to
 * a page with the discount already on.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THIS FILE CREATES NOTHING. IT ONLY EVER APPLIES A COUPON THAT ALREADY
 *     EXISTS IN WOOCOMMERCE. Read this before changing anything below.
 * ---------------------------------------------------------------------------
 *
 * There is no `wp_insert_post`, no `WC_Coupon::save()`, no `update_post_meta`
 * and no write of any kind to a coupon record anywhere in this file. A code that
 * is not already a published `shop_coupon` does nothing at all. ⭐ THAT IS WHY
 * IT NEEDS NO LIST OF CODES: every campaign code Andrew has today, and every
 * one he creates later, works identically — because the question asked is "does
 * WooCommerce have this one, and is it enabled", never "is this one of the codes
 * I was told about". ⛔ DO NOT ADD AN ALLOW-LIST
 * — it would have to be edited every time he makes a coupon, and the day it is
 * not edited is the day a real campaign link silently stops working.
 *
 * ---------------------------------------------------------------------------
 * ⛔ A TYPO IS SILENT. THAT IS THE PRODUCT DECISION, NOT AN OVERSIGHT.
 * ---------------------------------------------------------------------------
 * An unknown, trashed, draft or disabled code produces NO error, NO warning and
 * NO notice — the page renders exactly as if the parameter were absent. The
 * reader of a `?coupon=` link did not type it and cannot fix it; a red box
 * telling a parent their discount code is invalid, on a link Andrew sent them,
 * is worse in every case than simply showing the normal price.
 *
 * ⛔ AND THE SAME RULE COVERS A REAL CODE THAT THIS CART CANNOT USE. Expiry,
 *    usage limits, per-user limits and product restrictions are asked through
 *    `WC_Discounts::is_coupon_valid()`, which runs WooCommerce's full validation
 *    stack SILENTLY. `WC_Cart::apply_coupon()` would push a customer-facing
 *    error notice instead. ⭐ THAT DISTINCTION IS NOT THIS FILE'S INVENTION —
 *    `bhp_typ_maybe_apply_auto_coupon()` in the bundle plugin documents and uses
 *    exactly the same pair for exactly the same reason, and this file follows it
 *    rather than inventing a second convention.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE INTENT IS KEPT WHEN THE CART IS EMPTY. That is the brief's "on cart
 *    creation if empty at click time".
 * ---------------------------------------------------------------------------
 * A visitor who clicks a coupon link, lands on a product page with an empty
 * cart, reads for a while and then adds a book still gets the discount: the code
 * is stashed in the WooCommerce session and re-tried on every add-to-cart and on
 * every cart/checkout render. WooCommerce refuses to hold a coupon on an empty
 * cart, so stashing is the only correct way to honour the click.
 *
 * ---------------------------------------------------------------------------
 * ⚠️ WHAT THIS FILE DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It does not remove, replace or reorder any coupon already on the cart. If
 *    the visitor has one on, this ADDS to it and lets WooCommerce's own
 *    individual-use rules decide the outcome. Deciding which of two discounts a
 *    customer should get is a pricing decision and belongs to Andrew.
 * ⛔ It does not touch the Complete Collection auto-coupon in
 *    `plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php`. The two use
 *    different session keys and neither reads the other's. ⚠️ THEY CAN BOTH BE
 *    ACTIVE ON ONE CART — that is WooCommerce's normal multi-coupon behaviour,
 *    it is NOT introduced by this file, and whether the two should ever stack is
 *    a pricing question recorded for Andrew rather than answered here.
 * ⛔ It writes no order, no option, no post and no user meta.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The query parameter that carries a code.
 */
const BHP_COUPON_URL_PARAM = 'coupon';

/**
 * The session key holding a captured-but-not-yet-applied code.
 */
const BHP_COUPON_URL_SESSION_KEY = 'bhp_coupon_url_pending';

/**
 * The session key holding the code this visitor's notice should name.
 */
const BHP_COUPON_URL_NOTICE_KEY = 'bhp_coupon_url_notice';

/**
 * Normalise a raw code the way WooCommerce itself does.
 *
 * ⛔ TWO STAGES, AND BOTH ARE LOAD-BEARING. `sanitize_text_field()` strips tags,
 *    nulls and control characters from a value that came off a URL;
 *    `wc_format_coupon_code()` then applies WooCommerce's OWN normalisation
 *    (lowercasing and the `wc_coupon_code` filter) so the string this file looks
 *    up is byte-identical to the string WooCommerce stores. Skipping the second
 *    stage would make an upper-case and a lower-case spelling of the same code
 *    two different codes to this file and one code to WooCommerce.
 *
 * ⛔ THE LENGTH CAP IS A GUARD, NOT A RULE ABOUT COUPONS. It stops a
 *    multi-kilobyte query string being carried into a database lookup and into a
 *    session on every page load. No real coupon code approaches it.
 *
 * @param string $raw Raw parameter value.
 * @return string Normalised code, or '' if unusable.
 */
function bhp_coupon_url_normalise( $raw ) {
	$raw = sanitize_text_field( (string) $raw );
	if ( '' === $raw || strlen( $raw ) > 100 ) {
		return '';
	}
	if ( ! function_exists( 'wc_format_coupon_code' ) ) {
		return '';
	}
	return (string) wc_format_coupon_code( $raw );
}

/**
 * Does this code name a coupon that exists and is enabled?
 *
 * ⛔⛔ READ-ONLY, AND IT IS THE ONLY GATE BETWEEN A URL AND THE CART. Every
 *     branch below returns false rather than creating, publishing or repairing
 *     anything.
 *
 * ⭐ "ENABLED" IS CHECKED AS POST STATUS, because that is what WooCommerce
 *    actually stores. A coupon has no separate on/off switch: a trashed or
 *    drafted coupon is a disabled coupon. `wc_get_coupon_id_by_code()` will
 *    happily return the ID of a trashed coupon, so the status check after it is
 *    not redundant — it is the half that makes "disabled" mean anything.
 *
 * ⚠️ EXPIRY, USAGE LIMITS AND PRODUCT RESTRICTIONS ARE NOT ASKED HERE. They
 *    depend on the cart, which may not exist yet at capture time, so they are
 *    asked at application time by `WC_Discounts::is_coupon_valid()`. This
 *    function answers only "is there such a coupon, and is it switched on".
 *
 * @param string $code Normalised code.
 * @return bool
 */
function bhp_coupon_url_code_is_live( $code ) {
	$code = (string) $code;
	if ( '' === $code || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
		return false;
	}

	$id = (int) wc_get_coupon_id_by_code( $code );
	if ( $id <= 0 ) {
		return false; // No such coupon. Silent.
	}

	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || 'shop_coupon' !== $post->post_type ) {
		return false;
	}
	if ( 'publish' !== $post->post_status ) {
		return false; // Draft, pending, private or trashed = disabled. Silent.
	}

	return true;
}

/**
 * Should this request even look at the parameter?
 *
 * ⛔ FRONT-END PAGE VIEWS ONLY. Admin screens, AJAX, the REST API, cron and
 *    WP-CLI are all excluded — a `?coupon=` on an admin URL or an API call is
 *    not a customer clicking a campaign link, and applying a discount from one
 *    would be a side effect nobody asked for in a context nobody can see.
 *
 * @return bool
 */
function bhp_coupon_url_request_is_eligible() {
	if ( is_admin() ) {
		return false;
	}
	if ( wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}
	return true;
}

/**
 * Capture a code off the URL and stash it.
 *
 * ⭐ ON `wp` RATHER THAN `init`, so WooCommerce's session and cart are both
 *    already built. On `init` the cart does not exist yet and every click would
 *    take the "stash it for later" path even when the cart was ready.
 *
 * ⛔ NO NONCE, AND THAT IS CORRECT RATHER THAN AN OMISSION. A coupon link is
 *    printed in a newsletter weeks before it is clicked and is meant to be
 *    shared; a nonce would expire and break every one of them. The action this
 *    "unauthenticated" request can cause is bounded to exactly one thing —
 *    applying an already-published discount to the clicker's OWN cart — which is
 *    the entire purpose of the link. It writes nothing another visitor can read
 *    and nothing that outlives the session.
 *
 * @return void
 */
function bhp_coupon_url_capture() {
	if ( ! bhp_coupon_url_request_is_eligible() ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see the block comment above.
	if ( ! isset( $_GET[ BHP_COUPON_URL_PARAM ] ) ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$code = bhp_coupon_url_normalise( wp_unslash( $_GET[ BHP_COUPON_URL_PARAM ] ) );
	if ( '' === $code || ! bhp_coupon_url_code_is_live( $code ) ) {
		return; // ⛔ Silent. A typo'd link renders an ordinary page.
	}

	/*
	 * ⭐ ALREADY ON THE CART: NOTHING TO DO, AND NO SECOND NOTICE.
	 *    This is the "no duplicate stacking on revisit" gate. A visitor who
	 *    reloads the link, or opens it twice from two emails, gets one coupon and
	 *    one notice. `has_discount()` is WooCommerce's own answer, so it stays
	 *    correct if the coupon was applied by any other route.
	 */
	if ( WC()->cart && WC()->cart->has_discount( $code ) ) {
		return;
	}

	WC()->session->set( BHP_COUPON_URL_SESSION_KEY, $code );
	if ( is_callable( array( WC()->session, 'set_customer_session_cookie' ) ) ) {
		// Without this the session is not persisted for a visitor who has no
		// cart yet, and the stash would be lost on the very next request.
		WC()->session->set_customer_session_cookie( true );
	}

	bhp_coupon_url_maybe_apply();
}
add_action( 'wp', 'bhp_coupon_url_capture', 20 );

/**
 * Apply the stashed code if the cart can take it.
 *
 * ⛔ THE ORDER OF THE GATES IS LOAD-BEARING and each one is a different kind of
 *    "no":
 *      1. no session/cart yet          -> not now, keep the intent
 *      2. nothing stashed              -> nothing to do
 *      3. the code stopped being live  -> drop the intent, it is dead
 *      4. already on the cart          -> spend the intent, do not stack
 *      5. cart empty                   -> KEEP the intent (the brief's case)
 *      6. WooCommerce refuses it       -> KEEP the intent (see the block at the
 *                                        validation step for why, and for the QA
 *                                        observation that changed it)
 *
 * ⭐ 5 AND 6 ARE THE ONES THAT MATTER AND BOTH LOOK LIKE BUGS. An empty cart must
 *    not burn the intent: that is the visitor who clicked the link before
 *    choosing a book. A refusal must not burn it either: on THIS store a refusal
 *    usually means "not three books yet", not "never".
 *
 * ⛔ SO THE INTENT IS SPENT IN EXACTLY TWO PLACES — when the coupon goes ON, and
 *    when it is found already on. It is DROPPED in exactly one — when the coupon
 *    stops existing or stops being published. Nowhere else.
 *
 * @return bool True only if this call applied a coupon.
 */
function bhp_coupon_url_maybe_apply() {
	static $running = false;
	if ( $running ) {
		return false; // `apply_coupon()` triggers cart hooks that re-enter here.
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
		return false;
	}

	$code = (string) WC()->session->get( BHP_COUPON_URL_SESSION_KEY );
	if ( '' === $code ) {
		return false;
	}

	if ( ! bhp_coupon_url_code_is_live( $code ) ) {
		// Deleted, trashed or unpublished since the click. Drop it.
		WC()->session->set( BHP_COUPON_URL_SESSION_KEY, null );
		return false;
	}

	if ( WC()->cart->has_discount( $code ) ) {
		WC()->session->set( BHP_COUPON_URL_SESSION_KEY, null );
		return false;
	}

	if ( WC()->cart->is_empty() ) {
		return false; // ⭐ Keep the intent. See the block comment above.
	}

	if ( ! class_exists( 'WC_Discounts' ) ) {
		return false;
	}

	/*
	 * ⛔ THE SILENT VALIDATION. `is_coupon_valid()` runs the same stack
	 *    `apply_coupon()` runs — expiry, usage limit, per-user limit, product and
	 *    category restrictions, minimum spend, and every
	 *    `woocommerce_coupon_is_valid` filter this site registers, INCLUDING the
	 *    bundle plugin's own scope filter — and returns a `WP_Error` instead of
	 *    pushing a notice onto the customer's screen.
	 *
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⛔⛔ A REFUSAL KEEPS THE INTENT. IT DOES NOT SPEND IT. QA PROVED WHY.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⚠️ THE FIRST VERSION OF THIS FUNCTION CLEARED THE SESSION HERE, and it was
	 *    wrong in a way that only a real cart could show. Observed on staging
	 *    1.19.331, 2026-08-30: a visitor opening a link carrying one of the
	 *    audience codes with an empty cart, and then adding books ONE AT A TIME,
	 *    lost the discount permanently on the FIRST add — because that code
	 *    carries `_bhp_audience_coupon`,
	 *    and the bundle plugin's `bhp_validate_audience_coupon_scope` filter
	 *    refuses it until all three titles are in the cart in one format. The
	 *    first book is a refusal. The third would have been a yes, and the intent
	 *    was already gone.
	 *
	 * ⭐ THE DISTINCTION THAT MATTERS: "this cart does not qualify YET" and "this
	 *    coupon will never work" are DIFFERENT ANSWERS, and `is_coupon_valid()`
	 *    returns them through the same false. Minimum spend, product
	 *    restrictions, sale-item exclusion and every audience-scope filter are
	 *    all properties of the CART, and the cart is the thing about to change.
	 *
	 * ⭐ SO THE INTENT IS HELD UNTIL IT IS SPENT OR PROVABLY DEAD, which is
	 *    exactly the rule `bhp_typ_maybe_apply_auto_coupon()` states for the
	 *    Complete Collection coupon: *"THE INTENT IS KEPT, NOT BURNED, WHEN THE
	 *    CART MERELY DOES NOT QUALIFY YET."* That function can ask a
	 *    domain-specific `bhp_audience_coupon_cart_qualifies()` first; this one is
	 *    generic and has no such oracle, so it treats EVERY refusal as "not yet".
	 *
	 * ⚠️ THE HONEST COST, STATED RATHER THAN HIDDEN: a code that is genuinely
	 *    dead for this shopper — usage limit reached, already redeemed under a
	 *    per-user limit — is now re-validated on each add-to-cart and cart render
	 *    for the rest of the session instead of once. That is a few microseconds
	 *    of work WooCommerce already does on every cart render anyway, it emits
	 *    no notice and changes no total, and it buys the case the brief actually
	 *    asked for. `bhp_coupon_url_code_is_live()` above still drops a deleted or
	 *    unpublished coupon outright, so a dead INTENT cannot outlive its coupon.
	 */
	$discounts = new WC_Discounts( WC()->cart );
	if ( true !== $discounts->is_coupon_valid( new WC_Coupon( $code ) ) ) {
		return false; // ⭐ Keep the intent. The cart may qualify a book from now.
	}

	$running = true;
	$applied = WC()->cart->apply_coupon( $code );
	$running = false;

	if ( $applied ) {
		WC()->session->set( BHP_COUPON_URL_SESSION_KEY, null );
		// The notice names the code that actually went on, read back from the
		// coupon record at render time. See `bhp_coupon_url_render_notice()`.
		WC()->session->set( BHP_COUPON_URL_NOTICE_KEY, $code );

		/**
		 * ═══════════════════════════════════════════════════════════════════
		 * ⭐⭐ 1.19.337 (2026-08-30, `CYCLE170-LD-MICRO`) — THE DIAGNOSTIC HOOK.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * ⛔ THE GAP IT CLOSES: WooCommerce records REDEMPTIONS and records
		 *    nothing about a discount that went on a cart and never reached an
		 *    order. So "applied but did not buy" — the number that says whether
		 *    a coupon campaign has a traffic problem or a checkout problem — was
		 *    not computable from anything the store holds.
		 *
		 * ⭐ IT FIRES ONLY HERE, INSIDE `if ( $applied )`, so an ATTEMPT is never
		 *    counted as an application. The two refusal returns above this block
		 *    ("cart is empty", "does not qualify yet") deliberately fire nothing:
		 *    they are the common case on this store and counting them would make
		 *    the number meaningless.
		 *
		 * ⛔ THE ACTION CARRIES A CODE AND A SOURCE. NOTHING ELSE. No customer,
		 *    no cart, no order, no session, no address. The listener in
		 *    `inc/coupon-apply-counter.php` stores a code and a DATE and nothing
		 *    else, reads and writes no cookie, and touches no consent posture.
		 *
		 * ⭐ AN ACTION RATHER THAN A DIRECT CALL, so the bundle plugin's own
		 *    auto-apply path (`bhp_typ_maybe_apply_auto_coupon()`, a separate
		 *    deployable at 1.8.76 and NOT part of this release) can join the same
		 *    count with one line and no coupling in either direction. ⚠️ UNTIL IT
		 *    DOES, THE COUNT IS PARTIAL — that limitation is written down at the
		 *    top of the counter file and must travel with any reading of it.
		 *
		 * @param string $code   The coupon code that went on.
		 * @param string $source Which auto-apply path applied it.
		 */
		do_action( 'bhp_coupon_auto_applied', $code, 'url' );
	}

	return (bool) $applied;
}

/**
 * The safety nets, and they are the same two the bundle plugin uses.
 *
 * ⭐ `woocommerce_add_to_cart` is the empty-cart case closing: it fires on the
 *    classic add, the cart drawer and the Store API's own add-item route, so the
 *    discount lands the moment the first book goes in.
 * ⭐ `woocommerce_check_cart_items` covers the cart and checkout renders in both
 *    classic and Blocks, for a visitor who already had items before clicking.
 *
 * Both are no-ops with nothing stashed, and the whole function is a no-op once
 * the coupon is on.
 *
 * @return void
 */
function bhp_coupon_url_net() {
	bhp_coupon_url_maybe_apply();
}
add_action( 'woocommerce_add_to_cart', 'bhp_coupon_url_net', 99 );
add_action( 'woocommerce_check_cart_items', 'bhp_coupon_url_net', 99 );

/**
 * How the discount should be described, in the customer's words.
 *
 * ⛔⛔ THE AMOUNT IS READ OFF THE COUPON RECORD. IT IS NEVER HARDCODED AND NEVER
 *     INFERRED FROM THE CODE'S NAME. A coupon whose name ends in a number
 *     could be set to 15% in WooCommerce tomorrow, and a notice that reads "10%"
 *     because the code says so would be a false statement about a price on a
 *     customer-facing page. The brief's example wording is honoured by DERIVING
 *     the same sentence, not by copying its number.
 *
 * ⚠️ IF THE TYPE IS NEITHER A PERCENTAGE NOR A FIXED CART AMOUNT the wording
 *    falls back to naming no amount at all. Saying "your discount is applied" is
 *    always true; guessing at how a fixed-product discount will total is not.
 *
 * @param WC_Coupon $coupon Coupon object.
 * @return string Human phrase, e.g. "10% discount code <CODE>", already escaped-safe as plain text.
 */
function bhp_coupon_url_describe( $coupon ) {
	if ( ! is_object( $coupon ) || ! method_exists( $coupon, 'get_code' ) ) {
		return '';
	}

	/* ⛔ THE CODE COMES FROM THE COUPON RECORD, NOT FROM THE URL. Even though the
	   value was normalised and matched against the database before it reached
	   here, printing the stored string means the page can only ever echo
	   something WooCommerce itself holds. */
	$code   = strtoupper( (string) $coupon->get_code() );
	$type   = (string) $coupon->get_discount_type();
	$amount = (float) $coupon->get_amount();

	if ( 'percent' === $type && $amount > 0 ) {
		return sprintf(
			/* translators: 1: discount percentage, 2: coupon code. */
			__( '%1$s%% discount code %2$s', 'brave-hearts' ),
			(string) ( 0 === (int) fmod( $amount, 1 ) ? (int) $amount : $amount ),
			$code
		);
	}

	if ( 'fixed_cart' === $type && $amount > 0 && function_exists( 'strip_tags' ) ) {
		return sprintf(
			/* translators: 1: discount amount, 2: coupon code. */
			__( '%1$s discount code %2$s', 'brave-hearts' ),
			wp_strip_all_tags( wc_price( $amount ) ),
			$code
		);
	}

	/* translators: %s: coupon code. */
	return sprintf( __( 'discount code %s', 'brave-hearts' ), $code );
}

/**
 * Render the "it worked" notice.
 *
 * ⛔ IT ONLY RENDERS FOR A COUPON THAT IS ACTUALLY ON THE CART RIGHT NOW. The
 *    session flag alone is not enough: if the visitor removed the coupon on the
 *    cart page between the application and this render, the notice would be
 *    telling them something false about their own order. `has_discount()` is
 *    re-asked on every render for that reason.
 *
 * ⛔ COPY RAILS (Standing Rules §9.1): no "we", "us" or "our" — this is
 *    customer-facing and Andrew is the sole operator. No em dash. No outcome
 *    claim and no urgency. It states one fact and offers a way to close it.
 *
 * ⭐ `wp_body_open` SO IT LANDS ON ANY PAGE, which is the brief's "when any page
 *    loads". A WooCommerce `wc_add_notice()` would only surface on WooCommerce
 *    templates, and the campaign links point at ordinary landing pages.
 *
 * @return void
 */
function bhp_coupon_url_render_notice() {
	if ( ! bhp_coupon_url_request_is_eligible() ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart ) {
		return;
	}

	$code = (string) WC()->session->get( BHP_COUPON_URL_NOTICE_KEY );
	if ( '' === $code ) {
		return;
	}
	if ( ! WC()->cart->has_discount( $code ) ) {
		// Removed by the customer since it was applied. Stop claiming it is on.
		WC()->session->set( BHP_COUPON_URL_NOTICE_KEY, null );
		return;
	}
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return;
	}

	$phrase = bhp_coupon_url_describe( new WC_Coupon( $code ) );
	if ( '' === $phrase ) {
		return;
	}
	?>
	<div class="bhp-coupon-notice" data-bhp-coupon-notice role="status">
	  <p class="bhp-coupon-notice__text">
	    <?php
	    printf(
	        /* translators: %s: a phrase such as "10% discount code SPRINGSALE". */
	        esc_html__( 'Your %s is applied.', 'brave-hearts' ),
	        esc_html( $phrase )
	    );
	    ?>
	  </p>
	  <button type="button" class="bhp-coupon-notice__dismiss" data-bhp-coupon-dismiss>
	    <span aria-hidden="true">&times;</span>
	    <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this message', 'brave-hearts' ); ?></span>
	  </button>
	</div>
	<?php
}
add_action( 'wp_body_open', 'bhp_coupon_url_render_notice', 5 );

/**
 * Enqueue the one-job dismiss script.
 *
 * ⛔ WITHOUT THE SCRIPT THE NOTICE SIMPLY STAYS, and that is an acceptable
 *    degraded state rather than a broken one: it is a short, true, polite line
 *    of text. The dismiss button is printed unconditionally because, unlike an
 *    arrow that would do nothing, a button that does not close a static banner
 *    costs a visitor one tap and misleads nobody about the price.
 *
 * @return void
 */
function bhp_coupon_url_enqueue() {
	if ( ! bhp_coupon_url_request_is_eligible() ) {
		return;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	if ( '' === (string) WC()->session->get( BHP_COUPON_URL_NOTICE_KEY ) ) {
		return; // Nothing to dismiss: no script on the page at all.
	}
	wp_enqueue_script(
		'bhp-coupon-notice',
		get_template_directory_uri() . '/assets/js/coupon-notice.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_coupon_url_enqueue' );
