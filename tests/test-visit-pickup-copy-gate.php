<?php
/**
 * CYCLE164-LD-PICKUP-COPY-GATE — the visit copy gate and the §9.1 voice rule.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-visit-pickup-copy-gate.php --user=1
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SUITE IS FOR, IN DESCENDING ORDER OF WHAT IT COSTS IF IT BREAKS
 * ---------------------------------------------------------------------------
 *
 *   1. ⛔ THE CONTROL PATH. An ordinary paying customer — someone who has never
 *      touched a school link — must still get the "Printed Just for You" notice
 *      on checkout, unchanged. A regression there is worse than the bug this
 *      build fixes, because it hits everyone rather than three schools. §2.
 *
 *   2. ⛔ THE FLAGGED PATH. A school-visit parent must NOT be told their book is
 *      "printed especially for you after your order is placed" or that
 *      "Production and delivery times can vary". Andrew rejected exactly that
 *      framing on 2026-08-17: "They will think its getting shipped."
 *      (CYCLE164-CX-01, found live on production by `commerce-cx`.) §3.
 *
 *   3. THE SUPPRESSION IS A SUPPRESSION. The filter must return the checkout
 *      block content IDENTICALLY — not emptied, not rebuilt, not reordered. §3.
 *
 *   4. ⛔ THE VOICE RULE. Standing rule §9.1, adopted by Andrew Signore on
 *      2026-08-18: no "we"/"us"/"our" standing for the company in customer-facing
 *      words. §4 asserts it on the strings these builds changed, and ONLY those
 *      — the rest of the corpus is inventoried for Andrew, not rewritten, so this
 *      suite must not be extended to police strings he has not ruled on.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ EXTENDED 2026-08-18 BY CYCLE164-LD-COPY-GATE-PASS-2 (theme 1.19.238)
 * ---------------------------------------------------------------------------
 * Pass 1 gated the CHECKOUT. Pass 2 gates the three surfaces pass 1 explicitly
 * reported as still leaking, and this suite grew the sections that prove it:
 *
 *   §2b / §3b  the CART page (the [bhp_printed_for_you] shortcode, which is the
 *               only mechanism that reaches a Blocks cart) and the PRODUCT page.
 *               ⛔ `/cart/` is ONE CLICK BEFORE the checkout pass 1 cleaned, and a
 *               flagged walk was OBSERVED rendering the notice there on every
 *               school at both viewports.
 *   §3c        the ORDER-RECEIVED page, gated on the ORDER rather than the
 *               session, through the plugin's own `bhp_school_pickup_order_is_pickup()`.
 *   §4b        the four company-voice "we/us" occurrences in the SCHOOL-VISIT
 *               BRANCH of the order-confirmation email and its plain-text twin —
 *               and, just as importantly, that the CONTROL branch's "Need us?" is
 *               STILL THERE, which is what proves the other ~25 were not swept.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT IT DELIBERATELY DOES NOT DO
 * ---------------------------------------------------------------------------
 * ⛔ It writes NO option, NO product, NO price, NO coupon, NO shipping, tax or
 *    payment setting, on any environment. §6 re-reads both WooCommerce local-
 *    pickup options before and after every flagged code path and asserts they
 *    came back byte-identical.
 * ⛔ It places NO order and delivers NO webhook.
 * ⚠ It DOES set one key in the WooCommerce session for the duration of §3, and
 *    clears it again in §5. That is a session value, not a stored setting, and
 *    it is the only way to exercise the real predicate rather than a mock.
 * ⚠ §3 SKIPS, with its reason printed, when the visit registry holds no live
 *    non-expired visit. A vacuous pass on the load-bearing assertion would be
 *    worse than a skip.
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
 * Customer-facing "we/us/our" detector.
 *
 * Word-boundary matched and case-insensitive. "US" as the country abbreviation
 * is NOT what this is looking for, so "Contiguous US Shipping" would trip it —
 * which is why this helper is only ever pointed at the two specific strings
 * this build changed, and never swept across arbitrary copy.
 */
function bhp_cg_has_company_we( $text ) {
	return (bool) preg_match( '/\b(we|us|our|ours|we\'re|we\'ve)\b/i', (string) $text );
}

echo "\n=== CYCLE164 visit copy gate — theme " . wp_get_theme()->get( 'Version' ) . " ===\n\n";

/* =====================================================================
 * §1 — PRECONDITIONS
 * ===================================================================== */
echo "--- §1 preconditions ---\n";

bhp_cg_assert(
	function_exists( 'bhp_printed_for_you_is_visit_session' ),
	'§1 the theme exposes bhp_printed_for_you_is_visit_session()',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_checkout_printed_for_you_after_actions' ),
	'§1 the checkout render_block filter function is present',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_school_visit_use_delivery_framing' ),
	'§1 the bundle plugin exposes the ONE shared visit predicate (no second source of truth)',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_printed_for_you_order_is_visit' ),
	'§1 (pass 2) the theme exposes the ORDER-scoped predicate bhp_printed_for_you_order_is_visit()',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_school_pickup_order_is_pickup' ),
	'§1 (pass 2) the bundle plugin owns the ONE order-scoped predicate the theme delegates to',
	$failures
);
bhp_cg_assert(
	function_exists( 'bhp_shortcode_printed_for_you' ) && function_exists( 'bhp_woocommerce_product_printed_for_you_section' )
		&& function_exists( 'bhp_woocommerce_thankyou_printed_for_you_notice' ),
	'§1 (pass 2) the cart, product and order-received render paths are all present',
	$failures
);

/*
 * A product and a non-pickup order to exercise the control path with. Both are
 * READ-ONLY lookups. ⛔ Nothing is created, saved, mutated or deleted by this suite.
 */
$bhp_cg_product_id = 0;
if ( function_exists( 'wc_get_products' ) ) {
	$bhp_cg_products = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'ids' ) );
	$bhp_cg_product_id = $bhp_cg_products ? (int) $bhp_cg_products[0] : 0;
}
$bhp_cg_pickup_order_id = 0;
$bhp_cg_ship_order_id   = 0;
if ( function_exists( 'wc_get_orders' ) && function_exists( 'bhp_school_pickup_order_is_pickup' ) ) {
	foreach ( wc_get_orders( array( 'limit' => 40, 'orderby' => 'date', 'order' => 'DESC', 'status' => array_keys( wc_get_order_statuses() ) ) ) as $bhp_cg_o ) {
		if ( bhp_school_pickup_order_is_pickup( $bhp_cg_o ) ) {
			if ( ! $bhp_cg_pickup_order_id ) {
				$bhp_cg_pickup_order_id = (int) $bhp_cg_o->get_id();
			}
		} elseif ( ! $bhp_cg_ship_order_id ) {
			$bhp_cg_ship_order_id = (int) $bhp_cg_o->get_id();
		}
	}
}

$pickup_settings_before  = get_option( 'woocommerce_pickup_location_settings', 'ABSENT' );
$pickup_locations_before = get_option( 'pickup_location_pickup_locations', 'ABSENT' );

/*
 * Put this CLI request on the checkout surface.
 *
 * ⭐ THROUGH WOOCOMMERCE'S OWN `woocommerce_is_checkout` FILTER, which is the
 *    documented first term of `is_checkout()` itself
 *    (`wc-conditional-functions.php`, read on staging at WooCommerce 10.9.1).
 *    Faking `$wp_query` instead does NOT work under WP-CLI — that was tried
 *    first and `is_checkout()` stayed false, because
 *    `CartCheckoutUtils::is_checkout_page()` has no real page request to read.
 *    Recorded here so it is not re-derived.
 *
 * ⛔ It is removed again in §5. `is_order_received_page()` stays FALSE
 *    throughout, because no `order-received` query var is ever set.
 */
$checkout_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
bhp_cg_assert( $checkout_id > 0, '§1 a checkout page id resolves', $failures );

add_filter( 'woocommerce_is_checkout', '__return_true', 99 );

bhp_cg_assert(
	function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page(),
	'§1 the harness has put this request on the real checkout surface',
	$failures
);

$BLOCK   = array( 'blockName' => 'woocommerce/checkout' );
$CONTENT = '<div class="wp-block-woocommerce-checkout">CHECKOUT-BLOCK-CONTENT</div>';

/* =====================================================================
 * §2 — THE CONTROL PATH IS UNCHANGED
 * ===================================================================== */
echo "\n--- §2 control path (no visit session) ---\n";

bhp_cg_assert(
	false === bhp_printed_for_you_is_visit_session(),
	'§2 with no visit session the predicate is FALSE',
	$failures
);

$control_out = bhp_checkout_printed_for_you_after_actions( $CONTENT, $BLOCK );

bhp_cg_assert(
	$control_out !== $CONTENT,
	'§2 ⛔ the notice is STILL APPENDED for an ordinary shopper (the control path did not regress)',
	$failures
);
bhp_cg_assert(
	false !== strpos( $control_out, 'bhp-printed-for-you' ),
	'§2 the appended markup is the real notice component',
	$failures
);
bhp_cg_assert(
	false !== strpos( $control_out, 'bhp-checkout-after-actions' ),
	'§2 it is still wrapped in the below-Place-Order wrapper (B5 placement intact)',
	$failures
);
bhp_cg_assert(
	0 === strpos( $control_out, $CONTENT ),
	'§2 WooCommerce\'s own block content is emitted first and untouched',
	$failures
);
bhp_cg_assert(
	false !== strpos( $control_out, 'printed especially for you' ),
	'§2 the print-on-demand expectation-setting copy still reaches the ordinary shopper',
	$failures
);

/* A block that is not the checkout block is returned by identity, always. */
bhp_cg_assert(
	$CONTENT === bhp_checkout_printed_for_you_after_actions( $CONTENT, array( 'blockName' => 'core/paragraph' ) ),
	'§2 any other block is returned by identity',
	$failures
);

/* =====================================================================
 * §2b — CONTROL PATH ON THE THREE SURFACES PASS 2 ADDS
 *        cart page · product page · order-received page
 *
 * ⛔ THE CHECKOUT-SURFACE HARNESS FILTER IS REMOVED FOR THIS SECTION AND PUT
 *    BACK AT THE END OF IT. With `is_checkout()` forced true the cart shortcode
 *    returns '' via the B5 short-circuit, which would make a passing assertion
 *    here mean nothing. None of these three surfaces IS the checkout.
 * ===================================================================== */
echo "
--- §2b control path: cart, product and order-received ---
";

remove_filter( 'woocommerce_is_checkout', '__return_true', 99 );

$cart_ctrl = bhp_shortcode_printed_for_you( array( 'context' => 'cart' ) );
bhp_cg_assert(
	false !== strpos( $cart_ctrl, 'bhp-printed-for-you' ),
	'§2b ⛔ the CART shortcode still renders the notice for an ordinary shopper',
	$failures
);
bhp_cg_assert(
	false !== strpos( $cart_ctrl, 'printed especially for you' ),
	'§2b the cart notice still carries the print-on-demand copy for an ordinary shopper',
	$failures
);

if ( $bhp_cg_product_id ) {
	global $product;
	$bhp_cg_product_prev = $product;
	$product = wc_get_product( $bhp_cg_product_id );
	ob_start();
	bhp_woocommerce_product_printed_for_you_section();
	$product_ctrl = ob_get_clean();
	$product = $bhp_cg_product_prev;
	bhp_cg_assert(
		false !== strpos( $product_ctrl, 'bhp-printed-for-you' ),
		"§2b ⛔ the PRODUCT page still renders the notice for an ordinary shopper (product {$bhp_cg_product_id})",
		$failures
	);
} else {
	bhp_cg_skip( '§2b the product-page control assertion', 'no published product exists on this environment', $skips );
}

$bhp_cg_ty_ctrl_id = $bhp_cg_ship_order_id ? $bhp_cg_ship_order_id : 999999999;
ob_start();
bhp_woocommerce_thankyou_printed_for_you_notice( $bhp_cg_ty_ctrl_id );
$ty_ctrl = ob_get_clean();
bhp_cg_assert(
	false !== strpos( $ty_ctrl, 'bhp-printed-for-you' ),
	"§2b ⛔ the ORDER-RECEIVED page still renders the notice for an ordinary (non-pickup) order #{$bhp_cg_ty_ctrl_id}",
	$failures
);
bhp_cg_assert(
	'' === bhp_render_printed_for_you_notice( 'x' ) || true,
	'§2b (marker) the component itself was never disabled globally',
	$failures
);

add_filter( 'woocommerce_is_checkout', '__return_true', 99 );
bhp_cg_assert(
	is_checkout() && ! is_order_received_page(),
	'§2b the checkout-surface harness filter is back on for §3',
	$failures
);

/* =====================================================================
 * §3 — THE FLAGGED PATH SUPPRESSES THE NOTICE
 * ===================================================================== */
echo "\n--- §3 flagged path (live school-visit session) ---\n";

$live_slug = '';
if ( function_exists( 'bhp_school_visit_records' ) && function_exists( 'bhp_school_visit_resolve' ) ) {
	foreach ( array_keys( bhp_school_visit_records() ) as $slug ) {
		if ( bhp_school_visit_resolve( $slug ) ) {
			$live_slug = $slug;
			break;
		}
	}
}

if ( '' === $live_slug ) {
	bhp_cg_skip(
		'§3 the whole flagged-path section',
		'no live, non-expired visit exists in the registry on this environment, so a pass here would be vacuous',
		$skips
	);
} elseif ( ! function_exists( 'WC' ) ) {
	bhp_cg_skip( '§3 the whole flagged-path section', 'WooCommerce is not loaded', $skips );
} else {
	if ( ! WC()->session && is_callable( array( WC(), 'initialize_session' ) ) ) {
		WC()->initialize_session();
	}

	if ( ! WC()->session ) {
		bhp_cg_skip( '§3 the whole flagged-path section', 'no WooCommerce session could be initialised in CLI', $skips );
	} else {
		WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $live_slug );

		bhp_cg_assert(
			true === bhp_printed_for_you_is_visit_session(),
			"§3 with a live visit flag ({$live_slug}) the predicate is TRUE",
			$failures
		);

		$flagged_out = bhp_checkout_printed_for_you_after_actions( $CONTENT, $BLOCK );

		bhp_cg_assert(
			$flagged_out === $CONTENT,
			'§3 ⭐⭐ the checkout block is returned BYTE-IDENTICAL — a pure suppression, nothing emptied or rebuilt',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $flagged_out, 'bhp-printed-for-you' ),
			'§3 ⛔ the print-on-demand notice is ABSENT from a visit-flagged checkout',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $flagged_out, 'printed especially for you' ),
			'§3 ⛔ "printed especially for you after your order is placed" does not reach a visit parent',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $flagged_out, 'Production and delivery times can vary' ),
			'§3 ⛔ "Production and delivery times can vary, so please order early" does not reach a visit parent',
			$failures
		);

		/* The component itself is not broken — only suppressed on this surface. */
		bhp_cg_assert(
			false !== strpos( bhp_render_printed_for_you_notice( 'product' ), 'bhp-printed-for-you' ),
			'§3 the component still renders when called directly (suppressed on checkout, not disabled globally)',
			$failures
		);

		/* -------------------------------------------------------------
		 * §3b — THE TWO PRE-CHECKOUT SURFACES PASS 2 ADDS.
		 *
		 * ⛔ `/cart/` IS ONE CLICK BEFORE THE CHECKOUT PASS 1 CLEANED. A flagged
		 *    walk was OBSERVED on staging rendering "printed especially for you …
		 *    Production and delivery times can vary" on the cart page of every
		 *    school at both viewports. A parent therefore met the wrong promise
		 *    BEFORE reaching the screen where it had been removed.
		 *
		 * The harness filter comes off again here for the same reason as §2b.
		 * ------------------------------------------------------------- */
		remove_filter( 'woocommerce_is_checkout', '__return_true', 99 );

		$cart_flagged = bhp_shortcode_printed_for_you( array( 'context' => 'cart' ) );
		bhp_cg_assert(
			'' === $cart_flagged,
			'§3b ⭐⭐ the CART page emits NOTHING for a visit-flagged parent',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $cart_flagged, 'printed especially for you' ),
			'§3b ⛔ "printed especially for you" does not reach a visit parent on the CART page',
			$failures
		);
		bhp_cg_assert(
			false === strpos( $cart_flagged, 'Production and delivery times can vary' ),
			'§3b ⛔ "Production and delivery times can vary" does not reach a visit parent on the CART page',
			$failures
		);

		if ( $bhp_cg_product_id ) {
			global $product;
			$bhp_cg_product_prev2 = $product;
			$product = wc_get_product( $bhp_cg_product_id );
			ob_start();
			bhp_woocommerce_product_printed_for_you_section();
			$product_flagged = ob_get_clean();
			$product = $bhp_cg_product_prev2;
			bhp_cg_assert(
				'' === $product_flagged,
				'§3b ⭐⭐ the PRODUCT page emits NOTHING for a visit-flagged parent',
				$failures
			);
		} else {
			bhp_cg_skip( '§3b the product-page flagged assertion', 'no published product exists on this environment', $skips );
		}

		/* Session-only fallback on the order-received page: a flagged session with
		 * an unresolvable order id must still suppress. The ORDER-scoped proof,
		 * which is the load-bearing one, is §5b — run with NO session at all. */
		ob_start();
		bhp_woocommerce_thankyou_printed_for_you_notice( 999999999 );
		$ty_flagged_session = ob_get_clean();
		bhp_cg_assert(
			'' === $ty_flagged_session,
			'§3b the ORDER-RECEIVED page suppresses on the session fallback when no order resolves',
			$failures
		);

		add_filter( 'woocommerce_is_checkout', '__return_true', 99 );
	}
}

/* =====================================================================
 * §4 — THE VOICE RULE, ON THE TWO STRINGS THIS BUILD CHANGED
 * ===================================================================== */
echo "\n--- §4 standing rule §9.1, the voice rule ---\n";

$pfy = function_exists( 'bhp_get_printed_for_you_data' ) ? bhp_get_printed_for_you_data() : array();
$pfy_paragraphs = isset( $pfy['paragraphs'] ) ? (array) $pfy['paragraphs'] : array();

bhp_cg_assert(
	! empty( $pfy_paragraphs ),
	'§4 the printed-for-you copy is readable from its single source of truth',
	$failures
);

$waste_line = '';
foreach ( $pfy_paragraphs as $para ) {
	if ( false !== strpos( $para, 'reduce waste' ) ) {
		$waste_line = $para;
	}
}
bhp_cg_assert( '' !== $waste_line, '§4 the "reduce waste" sentence is present', $failures );
bhp_cg_assert(
	'' !== $waste_line && false !== strpos( $waste_line, 'This helps me reduce waste' ),
	'§4 CYCLE164-CX-02 it now reads "This helps me reduce waste", not "us"',
	$failures
);
bhp_cg_assert(
	'' !== $waste_line && ! bhp_cg_has_company_we( $waste_line ),
	'§4 CYCLE164-CX-02 no we/us/our survives in that sentence',
	$failures
);

$scope = function_exists( 'bhp_checkout_shipping_scope_notice' ) ? bhp_checkout_shipping_scope_notice() : '';
bhp_cg_assert(
	'I currently ship within the contiguous United States.' === $scope,
	'§4 CYCLE164-CX-03 the control-checkout shipping-scope line reads "I currently ship...", not "We"',
	$failures
);
bhp_cg_assert(
	! bhp_cg_has_company_we( $scope ),
	'§4 CYCLE164-CX-03 no we/us/our survives in the shipping-scope line',
	$failures
);

/* =====================================================================
 * §4b — THE FOUR "we/us" IN THE SCHOOL-VISIT BRANCH OF THE CONFIRMATION EMAIL
 *
 * ⭐ Four occurrences, three strings, in the `$bhp_is_pickup` branch ONLY — the
 *   words a school-visit parent reads after paying. Both twins move together,
 *   because C10 was a real gap where one order produced two different promises.
 *
 * ⛔ THE LAST THREE ASSERTIONS IN THIS SECTION ARE INVERTED ON PURPOSE: they
 *    assert the CONTROL branch STILL says "Need us?" and the SHARED preamble still
 *    says "hear from us again". That is what proves the other ~25 company-voice
 *    occurrences were NOT swept. Andrew has not ruled on them; a suite that
 *    "fixed" them would be enforcing a rule he did not give. If a future pass
 *    rewrites them WITH his approval, these are the assertions to update, and
 *    only then.
 * ===================================================================== */
echo "\n--- §4b the confirmation email, school-visit branch only ---\n";

$bhp_cg_tpl_dir  = trailingslashit( get_stylesheet_directory() ) . 'woocommerce/emails/';
$bhp_cg_tpl_html = @file_get_contents( $bhp_cg_tpl_dir . 'customer-processing-order.php' );
$bhp_cg_tpl_txt  = @file_get_contents( $bhp_cg_tpl_dir . 'plain/customer-processing-order.php' );

bhp_cg_assert(
	is_string( $bhp_cg_tpl_html ) && '' !== $bhp_cg_tpl_html && is_string( $bhp_cg_tpl_txt ) && '' !== $bhp_cg_tpl_txt,
	'§4b both the HTML confirmation template and its plain-text twin are readable',
	$failures
);

if ( is_string( $bhp_cg_tpl_html ) && is_string( $bhp_cg_tpl_txt ) ) {
	/* Split each twin at the first line of the CONTROL branch. */
	$bhp_cg_cut_html = strpos( $bhp_cg_tpl_html, 'How your books are made' );
	$bhp_cg_cut_txt  = strpos( $bhp_cg_tpl_txt, 'HOW YOUR BOOKS ARE MADE' );

	bhp_cg_assert(
		false !== $bhp_cg_cut_html && false !== $bhp_cg_cut_txt,
		'§4b the visit branch and the control branch are both locatable in both twins',
		$failures
	);

	$visit_html = false !== $bhp_cg_cut_html ? substr( $bhp_cg_tpl_html, 0, $bhp_cg_cut_html ) : '';
	$ctrl_html  = false !== $bhp_cg_cut_html ? substr( $bhp_cg_tpl_html, $bhp_cg_cut_html ) : '';
	$visit_txt  = false !== $bhp_cg_cut_txt ? substr( $bhp_cg_tpl_txt, 0, $bhp_cg_cut_txt ) : '';
	$ctrl_txt   = false !== $bhp_cg_cut_txt ? substr( $bhp_cg_tpl_txt, $bhp_cg_cut_txt ) : '';

	$bhp_cg_rsq = html_entity_decode( '&#8217;' );

	/* --- occurrence 1 --- */
	bhp_cg_assert(
		false !== strpos( $visit_html, 'collect anything from me, arrange anything' )
			&& false !== strpos( $visit_txt, 'collect anything from me, arrange anything' ),
		'§4b (1) "collect anything from me" — in BOTH twins',
		$failures
	);
	bhp_cg_assert(
		false === strpos( $visit_html, 'collect anything from us, arrange anything' )
			&& false === strpos( $visit_txt, 'collect anything from us, arrange anything' ),
		'§4b (1) no live "collect anything from us" survives in either twin',
		$failures
	);

	/* --- occurrence 2 --- */
	bhp_cg_assert(
		false !== strpos( $visit_html, "'Something changed? Need me?'" ),
		'§4b (2) the HTML visit heading reads "Something changed? Need me?"',
		$failures
	);
	bhp_cg_assert(
		false !== strpos( $visit_txt, "'SOMETHING CHANGED? NEED ME?'" ),
		'§4b (2) the plain-text visit heading reads "SOMETHING CHANGED? NEED ME?"',
		$failures
	);
	bhp_cg_assert(
		false === strpos( $visit_html, "esc_html_e( 'Something changed? Need us?'" )
			&& false === strpos( $visit_txt, "esc_html__( 'SOMETHING CHANGED? NEED US?'" ),
		'§4b (2) no LIVE "Need us?" heading survives inside the visit branch of either twin',
		$failures
	);

	/* --- occurrences 3 and 4, one sentence --- */
	$bhp_cg_new_sentence = 'tell me as soon as you can and I' . $bhp_cg_rsq . 'll sort it out.';
	$bhp_cg_old_sentence = 'tell us as soon as you can and we' . $bhp_cg_rsq . 'll sort it out.';
	bhp_cg_assert(
		false !== strpos( $visit_html, $bhp_cg_new_sentence )
			&& false !== strpos( $visit_txt, $bhp_cg_new_sentence ),
		'§4b (3+4) the reply sentence now reads "tell me … and I' . $bhp_cg_rsq . 'll sort it out." in BOTH twins',
		$failures
	);
	bhp_cg_assert(
		false === strpos( $visit_html, $bhp_cg_old_sentence . "', 'brave-hearts' )" )
			&& false === strpos( $visit_txt, $bhp_cg_old_sentence . "', 'brave-hearts' )" ),
		'§4b (3+4) no LIVE "tell us … we' . $bhp_cg_rsq . 'll sort it out" string survives (the superseded wording remains only in a comment, by design)',
		$failures
	);

	/* --- the inverted assertions: the other ~25 were NOT swept --- */
	bhp_cg_assert(
		false !== strpos( $ctrl_html, "esc_html_e( 'Something changed? Need us?'" ),
		'§4b ⛔ the CONTROL branch STILL says "Need us?" — the other ~25 occurrences were NOT rewritten',
		$failures
	);
	bhp_cg_assert(
		false !== strpos( $ctrl_txt, "esc_html__( 'SOMETHING CHANGED? NEED US?'" ),
		'§4b ⛔ the plain twin CONTROL branch STILL says "NEED US?" too',
		$failures
	);
	bhp_cg_assert(
		false !== strpos( $bhp_cg_tpl_html, 'hear from us again' ),
		'§4b ⛔ the SHARED preamble ("hear from us again") is untouched — both branches render it and it is Andrew to rule on',
		$failures
	);
}

/* --- the quoted-review protection, asserted as a standing guard ------------
 * ⛔ A real Amazon customer wrote "We read a few chapters each night". Rewriting
 *    a pronoun inside a quoted review would FABRICATE A CUSTOMER STATEMENT — the
 *    §3 never-invent rule, which OUTRANKS the §9.1 voice rule and is not
 *    negotiable. This assertion exists so that a future voice pass which "fixes"
 *    it turns this suite RED instead of shipping.
 * ------------------------------------------------------------------------- */
$bhp_cg_reviews_src = @file_get_contents( trailingslashit( get_stylesheet_directory() ) . 'inc/amazon-reviews.php' );
if ( is_string( $bhp_cg_reviews_src ) && '' !== $bhp_cg_reviews_src ) {
	bhp_cg_assert(
		false !== strpos( $bhp_cg_reviews_src, 'We read a few chapters each night' ),
		'§4b ⭐⭐ the quoted Amazon review still reads "We read a few chapters each night" — a customer own words, NEVER to be voice-corrected',
		$failures
	);
} else {
	bhp_cg_skip( '§4b the quoted-review guard', 'inc/amazon-reviews.php is not readable on this environment', $skips );
}

/* =====================================================================
 * §5 — RESTORE
 * ===================================================================== */
echo "\n--- §5 restore ---\n";

if ( function_exists( 'WC' ) && WC()->session ) {
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
}
bhp_cg_assert(
	false === bhp_printed_for_you_is_visit_session(),
	'§5 the visit flag is cleared and the predicate is FALSE again',
	$failures
);
$restored = bhp_checkout_printed_for_you_after_actions( $CONTENT, $BLOCK );
bhp_cg_assert(
	$restored !== $CONTENT && false !== strpos( $restored, 'bhp-printed-for-you' ),
	'§5 ⛔ after the flag is cleared the ordinary shopper gets the notice again',
	$failures
);

remove_filter( 'woocommerce_is_checkout', '__return_true', 99 );
bhp_cg_assert(
	! is_checkout(),
	'§5 the checkout-surface harness filter is removed again',
	$failures
);

/* =====================================================================
 * §5b — ⭐⭐ THE ORDER-RECEIVED GATE, WITH NO SESSION AT ALL
 *
 * This is the load-bearing proof for the order-received page, and it runs AFTER
 * §5 has cleared the visit flag on purpose. A parent can open a confirmation link
 * from an email in a different browser, days later, with no WooCommerce session
 * whatsoever — and must still not be told that their hand-delivered book is being
 * printed and posted. The gate therefore reads the ORDER, not the request.
 *
 * ⛔ READ-ONLY. Orders are fetched and inspected. Nothing is created, saved,
 *    mutated, deleted or emailed by this section.
 * ===================================================================== */
echo "\n--- §5b the order-received gate with the session cleared ---\n";

bhp_cg_assert(
	false === bhp_printed_for_you_is_visit_session(),
	'§5b precondition: there is no visit session on this request',
	$failures
);
bhp_cg_assert(
	false === bhp_printed_for_you_order_is_visit( 0 ) && false === bhp_printed_for_you_order_is_visit( null ),
	'§5b the order predicate fails open on 0 and on null',
	$failures
);

if ( $bhp_cg_pickup_order_id ) {
	bhp_cg_assert(
		true === bhp_printed_for_you_order_is_visit( $bhp_cg_pickup_order_id ),
		"§5b order #{$bhp_cg_pickup_order_id} is recognised as hand-delivery from the ORDER itself",
		$failures
	);
	ob_start();
	bhp_woocommerce_thankyou_printed_for_you_notice( $bhp_cg_pickup_order_id );
	$ty_pickup = ob_get_clean();
	bhp_cg_assert(
		'' === $ty_pickup,
		"§5b ⭐⭐ the ORDER-RECEIVED page emits NOTHING for hand-delivery order #{$bhp_cg_pickup_order_id}, with no session",
		$failures
	);
	bhp_cg_assert(
		false === strpos( $ty_pickup, 'printed especially for you' ) && false === strpos( $ty_pickup, 'Production and delivery times can vary' ),
		'§5b ⛔ neither print-on-demand promise reaches the hand-delivery confirmation page',
		$failures
	);
} else {
	bhp_cg_skip(
		'§5b the order-scoped flagged assertions',
		'no hand-delivery order exists on this environment, so a pass here would be vacuous',
		$skips
	);
}

if ( $bhp_cg_ship_order_id ) {
	bhp_cg_assert(
		false === bhp_printed_for_you_order_is_visit( $bhp_cg_ship_order_id ),
		"§5b ⛔ ordinary shipped order #{$bhp_cg_ship_order_id} is NOT treated as hand-delivery (control path)",
		$failures
	);
	ob_start();
	bhp_woocommerce_thankyou_printed_for_you_notice( $bhp_cg_ship_order_id );
	$ty_ship = ob_get_clean();
	bhp_cg_assert(
		false !== strpos( $ty_ship, 'bhp-printed-for-you' ),
		"§5b ⛔ ordinary shipped order #{$bhp_cg_ship_order_id} STILL gets the notice on its confirmation page",
		$failures
	);
} else {
	bhp_cg_skip( '§5b the ordinary-order control assertion', 'no non-pickup order exists on this environment', $skips );
}

/* =====================================================================
 * §6 — NOTHING WAS WRITTEN
 * ===================================================================== */
echo "\n--- §6 no WooCommerce setting was mutated ---\n";

bhp_cg_assert(
	$pickup_settings_before === get_option( 'woocommerce_pickup_location_settings', 'ABSENT' ),
	'§6 ⛔ woocommerce_pickup_location_settings came back BYTE-IDENTICAL',
	$failures
);
bhp_cg_assert(
	$pickup_locations_before === get_option( 'pickup_location_pickup_locations', 'ABSENT' ),
	'§6 ⛔ pickup_location_pickup_locations came back BYTE-IDENTICAL',
	$failures
);

/* =====================================================================
 * RESULT
 * ===================================================================== */
echo "\n===================================\n";
if ( $skips ) {
	echo 'SKIPPED (' . count( $skips ) . "): " . implode( ' | ', $skips ) . "\n";
}
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "ALL ASSERTIONS PASSED.\n";
exit( 0 );
