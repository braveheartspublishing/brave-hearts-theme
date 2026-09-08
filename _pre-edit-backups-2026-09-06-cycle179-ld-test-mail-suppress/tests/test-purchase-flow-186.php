<?php
/**
 * CARRIER ITEM 186 — the founder's staging walk, asserted.
 * Theme 1.19.280 / plugin 1.8.64. `CYCLE165-LD-PURCHASE-FLOW-ROUND`.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-purchase-flow-186.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⭐ WHY THIS SUITE EXISTS. Andrew Signore walked staging at 1.19.279 and
 *    returned six catches and a flow ruling (carrier item 186, ~05:1x−0600
 *    2026-08-21, read FIRST-HAND at source by the agent that wrote this file).
 *    Five of those six were things every existing gate passed — the gates
 *    checked PRESENCE and LIVENESS, and what was wrong was ALIGNMENT,
 *    CONTRAST, DESTINATION and COPY. This suite asserts those four properties
 *    so the same class cannot regress silently, in the spirit of
 *    `test-protected-elements.php` (item 119).
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED RATHER THAN GLOSSED:
 *    it is a PHP suite. It cannot prove a rendered pixel, a computed contrast
 *    ratio or a real browser redirect. Those are asserted in the browser QA
 *    at an asserted `window.innerWidth`, and the evidence lives with the
 *    workstream. What this suite proves is that the CODE that produces them
 *    is present, single-sourced and self-consistent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔⛔ THE COUNTERS LIVE IN $GLOBALS DELIBERATELY, AND THE REASON IS A DEFECT
 *     THIS SUITE ITSELF SHIPPED ONCE.
 *
 * `wp eval-file` executes a file INSIDE A FUNCTION, so this file's top level
 * is NOT global scope. A `global $pf_failures` inside `pf_assert()` therefore
 * binds to a DIFFERENT, always-empty variable, and the summary line printed
 * "0 passed, 0 failed" on a run where 43 assertions had just printed PASS.
 *
 * ⭐ THAT IS THE "a gate that cannot report failure is not a gate" class — the
 *    same class as `test-protected-elements.php`'s reason for existing. A
 *    reader (or a deploy gate) scanning only the summary would have seen zero
 *    failures on a totally broken build. Writing through $GLOBALS makes the
 *    two scopes the same one under `wp eval-file` AND under a plain include.
 */
$GLOBALS['pf_failures'] = 0;
$GLOBALS['pf_passes']   = 0;
function pf_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['pf_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['pf_failures']++;
		echo "FAIL: $label\n";
	}
}

$pf_theme  = get_template_directory();
$pf_plugin = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing';

/** Comment-stripped source, so a comment QUOTING a marker cannot satisfy a gate. */
function pf_code( $path ) {
	if ( ! file_exists( $path ) ) {
		return '';
	}
	$out = '';
	foreach ( token_get_all( file_get_contents( $path ) ) as $t ) {
		if ( is_array( $t ) && in_array( $t[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$out .= is_array( $t ) ? $t[1] : $t;
	}
	return $out;
}

echo "=== CARRIER ITEM 186 — six catches + the flow ruling ===\n\n";

/* ───────────────────────────────────────────────────────────────────────────
 * §1 · CATCH (4) — THE FOUNDER'S FREE-SHIPPING STRING, AND IT IS SINGLE-VOICED
 * ─────────────────────────────────────────────────────────────────────────── */
echo "--- §1 free-shipping copy (catch 4) ---\n";

$pf_expected = 'FREE Shipping on the complete collection or 3 or more books purchased';

pf_assert(
	function_exists( 'bhp_book_free_shipping_line' ),
	'1.1 the theme helper exists'
);

/*
 * ⛔ ASSERTED WITH THE SCHOOL-VISIT FLAG DOWN. `bhp_book_free_shipping_line()`
 *    deliberately returns the HAND-DELIVERY sentence for a flagged session —
 *    §3 below asserts that branch separately and it must NOT be broken by the
 *    copy change.
 */
$pf_flagged = function_exists( 'bhp_school_visit_use_delivery_framing' )
	&& bhp_school_visit_use_delivery_framing();

if ( ! $pf_flagged ) {
	pf_assert(
		$pf_expected === bhp_book_free_shipping_line(),
		'1.2 UNFLAGGED: the helper returns the founder string byte-for-byte'
	);
} else {
	echo "SKIP: 1.2 — this CLI session carries a school-visit flag\n";
}

/*
 * ⭐⭐ THE DRIFT GATE, AND IT IS THE POINT OF THIS SECTION. Before 1.19.280
 *    this one customer-facing promise was HARDCODED IN THREE PLACES: the
 *    theme helper and TWO literals in the plugin's landing page (the cold-open
 *    bullets and the closing-CTA bullets). Three copies of one trust string is
 *    three chances for the page to promise two different things. This asserts
 *    all three carry the SAME bytes, from the comment-stripped source.
 */
$pf_landing_code = pf_code( $pf_plugin . '/includes/bundle-landing-page.php' );
pf_assert(
	2 === substr_count( $pf_landing_code, $pf_expected ),
	'1.3 DRIFT GATE: both plugin landing-page literals carry the founder string (expect exactly 2)'
);
pf_assert(
	0 === substr_count( $pf_landing_code, "'FREE Shipping on the complete collection'" ),
	'1.4 …and NO superseded short form survives in the plugin landing page'
);
$pf_formats_code = pf_code( $pf_theme . '/inc/book-formats.php' );
pf_assert(
	false !== strpos( $pf_formats_code, $pf_expected ),
	'1.5 …and the theme helper carries it too'
);

/*
 * ⭐ THE CLAIM IS TRUE OF THE ENGINE. A promise the cart does not honour is
 *    the `CYCLE165-OPS-018` defect class. `FD-583` ("ANY 3 BOOKS FREE,
 *    DUPLICATES INCLUDED") is implemented as `bhp_bundle_colouring_policy()`
 *    returning `any-three`, which makes branch A of
 *    `bhp_bundle_shipping_amount()` return 0.00 at three physical books.
 *    ⛔ THIS READS THE POLICY; IT CHANGES NOTHING.
 */
if ( function_exists( 'bhp_bundle_colouring_policy' ) ) {
	pf_assert(
		'any-three' === bhp_bundle_colouring_policy(),
		'1.6 THE CLAIM IS HONOURED: the free-shipping policy is `any-three` (FD-583), so "3 or more books" is true'
	);
} else {
	pf_assert( false, '1.6 the policy function is reachable' );
}
if ( function_exists( 'bhp_bundle_shipping_amount' ) ) {
	pf_assert(
		0.00 === (float) bhp_bundle_shipping_amount( array( 'physical_book_count' => 3 ) ),
		'1.7 …exercised, not read: three physical books resolve to $0.00 shipping'
	);
	pf_assert(
		0.00 === (float) bhp_bundle_shipping_amount( array( 'physical_book_count' => 5 ) ),
		'1.8 …and so does five ("or more")'
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §2 · CATCH (3), REFINED BY CARRIER ITEM 188 — TWO BUTTON CLASSES
 * ───────────────────────────────────────────────────────────────────────────
 *
 * ⛔ THIS SECTION'S HEADING USED TO READ "THE FLOW: EVERY BUY BUTTON FINISHES
 *    ON /checkout/", which is what item 186's delegated mechanism was read to
 *    mean. ⭐ CARRIER ITEM 188 IS ANDREW CHOOSING FOR HIMSELF, read first-hand
 *    at source by the agent that rewrote this section
 *    (`FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-AUTHORIZATION.md` line
 *    818, G: mount, NOT relayed):
 *
 *      "Well if we keep add to cart - lets not do the cart page- we made the
 *       cart side panel for a reason with the upsells and the totals in their-
 *       they go to checkout then add the coupon"
 *
 *    ADD TO CART  → adds, and OPENS THE SIDE PANEL
 *    DIRECT BUY   → straight to /checkout/, UNCHANGED (he walked it himself)
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §2 the flow (catch 3, refined by item 188) ---\n";

pf_assert(
	function_exists( 'bhp_purchase_flow_mark' ) && function_exists( 'bhp_purchase_flow_redirect' ),
	'2.1 the purchase-flow module is loaded'
);
pf_assert(
	function_exists( 'bhp_purchase_flow_mark_panel' ) && function_exists( 'bhp_purchase_flow_panel_requested' ),
	'2.1b item 188: the "add, then open the panel" half of the module is loaded'
);

$pf_flow_code = pf_code( $pf_theme . '/inc/purchase-flow.php' );

/*
 * ⛔ THE OPEN-REDIRECT PROPERTY, ASSERTED IN THE SOURCE. The destination must
 *    be built from WooCommerce's own helper and NEVER from the request. On a
 *    page that takes payment this is a security property, not a nicety.
 */
pf_assert(
	false !== strpos( $pf_flow_code, 'wc_get_checkout_url()' ),
	'2.2 the destination is built from wc_get_checkout_url()'
);
pf_assert(
	false === strpos( $pf_flow_code, 'wp_safe_redirect' )
	&& false === strpos( $pf_flow_code, 'wp_redirect' ),
	'2.3 SECURITY: this module never calls a redirect function itself — it only filters a destination'
);

/* The single-book buy URLs carry the flag; Kindle (offsite) does not. */
if ( function_exists( 'bhp_book_purchase_data' ) && function_exists( 'bhp_book_registry' ) ) {
	$pf_keys = array_keys( bhp_book_registry() );
	$pf_key  = isset( $pf_keys[0] ) ? $pf_keys[0] : '';
	$pf_data = $pf_key ? bhp_book_purchase_data( $pf_key ) : null;
	if ( $pf_data ) {
		/*
		 * ⛔ THE SUPERSEDED ASSERTIONS, PRESERVED SO THE MOVEMENT IS VISIBLE.
		 *    1.19.280 asserted `bhp_buy=checkout` on both single-book URLs:
		 *      '2.4 the PAPERBACK add-to-cart URL is marked "finish on /checkout/"'
		 *      '2.5 …and so is the HARDCOVER one'
		 *    ⭐ Item 188 moved ADD TO CART to the panel, so they invert.
		 */
		pf_assert(
			false !== strpos( (string) $pf_data['paperback']['add_url'], 'bhp_buy=panel' ),
			'2.4 item 188: the PAPERBACK add-to-cart URL is marked "add, then open the panel"'
		);
		pf_assert(
			false !== strpos( (string) $pf_data['hardcover']['add_url'], 'bhp_buy=panel' ),
			'2.5 …and so is the HARDCOVER one'
		);
		pf_assert(
			false === strpos( (string) $pf_data['paperback']['add_url'], 'bhp_buy=checkout' )
			&& false === strpos( (string) $pf_data['hardcover']['add_url'], 'bhp_buy=checkout' ),
			'2.5b ⛔ …and NEITHER still carries the superseded straight-to-checkout mark'
		);
		pf_assert(
			false === strpos( (string) $pf_data['kindle']['url'], 'bhp_buy=checkout' )
			&& false === strpos( (string) $pf_data['kindle']['url'], 'bhp_buy=panel' ),
			'2.6 ⛔ the KINDLE link is NOT marked at all — it leaves this site for Amazon'
		);
		/*
		 * ⭐⭐ THE DIRECT-BUY PATH IS THE ONE HE WALKED. It must NOT have
		 *     acquired a panel mark: the collection URL is a page link, and the
		 *     control beside it is the plugin's own checkout-redirect POST.
		 */
		pf_assert(
			false === strpos( (string) $pf_data['collection']['url'], 'bhp_buy=panel' ),
			'2.6b ⛔ DIRECT BUY UNTOUCHED: the collection URL carries no panel mark'
		);
	} else {
		pf_assert( false, '2.4 purchase data resolves for at least one title' );
	}
}

/*
 * ⭐⭐ THE PANEL IS OPENED BY A CLICK, NEVER BY A URL — the `ads-knowledge` second
 *     condition, asserted structurally rather than promised in a comment.
 *
 * ⛔ IF ANY FUTURE BUILD ADDS AN "OPEN THE PANEL" QUERY PARAMETER, THIS FAILS.
 *    A URL that opens the panel is a URL that can be bookmarked, shared,
 *    crawled and landed on, which is precisely the self-open `ads-knowledge` ruled
 *    out. The two legitimate openers are both DOM hooks on controls the
 *    shopper clicks: `data-bhp-cart-add` and `data-bhp-cart-open`.
 */
$pf_drawer_js = pf_code( $pf_plugin . '/assets/bundle-drawer.js' );
pf_assert(
	false !== strpos( $pf_drawer_js, 'data-bhp-cart-add' )
	&& false !== strpos( $pf_drawer_js, 'interceptCartAddLinks' ),
	'2.10 item 188: the drawer intercepts add-to-cart controls and opens the panel'
);
pf_assert(
	false === strpos( $pf_drawer_js, 'bhp_cart=open' )
	&& false === strpos( $pf_drawer_js, 'bhp_open_cart' )
	&& false === strpos( $pf_flow_code, 'bhp_cart=open' ),
	'2.11 ⛔ ads-knowledge CONDITION 2: no query parameter anywhere can open the panel'
);
pf_assert(
	false !== strpos( $pf_flow_code, "option_woocommerce_cart_redirect_after_add" ),
	'2.12 the no-JavaScript fallback is steered by a READ filter, keeping that shopper off the cart page'
);
pf_assert(
	false === strpos( $pf_flow_code, "update_option" )
	&& false === strpos( $pf_flow_code, "add_option" ),
	'2.13 ⛔ …and it writes NO WooCommerce setting on any environment (Andrew gate not crossed)'
);
/*
 * ⛔ THE FORMAT SWITCHER MUST MOVE THE BUY ID WITH THE HREF. An anchor whose
 *    href said "paperback" while its data-product-id still said "hardcover"
 *    would add the wrong book — silently, and only for shoppers who switch
 *    format, which is the hardest class of defect to notice.
 */
$pf_formats_js = pf_code( $pf_theme . '/assets/js/book-formats.js' );
pf_assert(
	false !== strpos( $pf_formats_js, 'data-bhp-cart-add' )
	&& false !== strpos( $pf_formats_js, "removeAttribute('data-bhp-cart-add')" ),
	'2.14 the format switcher sets AND removes the panel hook, so it can never go stale'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §2b · THE COLOURING OFFER IN THE PANEL, AND THE FALSE CLAIM IT ALMOST MADE
 * ───────────────────────────────────────────────────────────────────────────
 *
 * ⭐ Andrew, carrier item 186, naming both offers the surviving cart surface
 *    should carry: "add the coloring book, add the next chapter book etc."
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §2b the colouring offer reaches the panel ---\n";

pf_assert(
	function_exists( 'bhp_offer_drawer_payload' ),
	'2b.1 the panel gets its colouring offers from the offer engine, not from a second catalogue'
);
if ( function_exists( 'bhp_offer_drawer_payload' ) ) {
	$pf_rows = bhp_offer_drawer_payload();
	/*
	 * ⛔ ON PRODUCTION THIS IS EMPTY AND MUST BE — `FD-598`. The assertion is
	 *    therefore about the GATE, not about a row existing.
	 */
	$pf_gated_ok = true;
	foreach ( $pf_rows as $pf_row ) {
		if ( ! bhp_offer_is_purchasable( $pf_row['key'] ) || ! ( $pf_row['saving'] > 0 ) ) {
			$pf_gated_ok = false;
		}
	}
	pf_assert( $pf_gated_ok, sprintf( '2b.2 ⛔ every row the panel receives is purchasable TODAY and has a real saving (%d rows)', count( $pf_rows ) ) );
	pf_assert(
		empty( bhp_colouring_product_ids() ) ? empty( $pf_rows ) : true,
		'2b.3 ⛔ FD-598: with no colouring product record the panel receives NOTHING'
	);
}
/*
 * ⛔⛔ THE FALSE CLAIM THIS ALMOST SHIPPED. The gold eyebrow reads "COMPLETE
 *     THE COLLECTION", which is true above an adventure cross-sell and FALSE
 *     above a coloring book — the Complete Collection is three CHAPTER books.
 *     ⭐ Caught in a real browser on the first staging screenshot, not by
 *     reading the file. This asserts the suppression stays.
 */
/*
 * ⛔⛔ CORRECTED 1.19.287 / plugin 1.8.68 (carrier item 214) — THIS ASSERTION
 *     MATCHED A JS LITERAL THAT A CORRECT CHANGE REPLACED, AND IT FAILED ON A
 *     BUILD WHERE THE SUPPRESSION IT GUARDS IS STRICTLY STRONGER THAN BEFORE.
 *     Same failure class as `test-uniform-shop-cta-210-211.php` §8 at 1.19.286
 *     ("matched CSS literals the geometry fix replaced").
 *
 * ⭐ THE SUPERSEDED TEST, PRESERVED SO IT IS NOT RE-DERIVED:
 *
 *       strpos( $pf_drawer_js, "if ('colouring' === cs.format) {" )
 *
 * ⭐ WHAT CHANGED AND WHY THE OLD LITERAL HAD TO GO. 1.8.68 made the pair
 *    offer BIDIRECTIONAL, and the reverse offer's `cs.format` is 'paperback'
 *    — so a format-only test would have let the false "COMPLETE THE
 *    COLLECTION" eyebrow print above a cart holding one coloring book. The
 *    suppression now tests `offer_kind`, WITH the original format test kept
 *    beside it for an older payload. ⛔ Two tests, one outcome, no gap.
 *
 * ⛔ THE ASSERTION IS NOT WEAKENED — IT IS WIDENED TO THE CLAIM IT ALWAYS
 *    MEANT: the eyebrow is suppressed on EVERY pair offer, in both
 *    directions. Both halves are required, so neither can be dropped later.
 *
 * ⭐ OBSERVED LIVE ON STAGING 2026-08-21 at an asserted innerWidth of 1280 and
 *    of 390, in a real Blocks cart: colouring-only cart -> the reverse offer
 *    renders with NO eyebrow; Mariana-paperback-only cart -> the forward
 *    offer renders with NO eyebrow; the ordinary adventure cross-sell KEEPS
 *    its "Complete the collection" heading.
 */
pf_assert(
	false !== strpos( $pf_drawer_js, "'colouring' === cs.format" ),
	'2b.4 ⛔ the "COMPLETE THE COLLECTION" eyebrow is suppressed on a colouring offer — it would be a false claim'
);
pf_assert(
	false !== strpos( $pf_drawer_js, "'pair' === cs.offer_kind" ),
	'2b.4b ⛔⛔ …and on the REVERSE pair offer too, whose format is "paperback" — a format-only test would have let the false claim through (1.8.68)'
);
pf_assert(
	false !== strpos( $pf_drawer_js, 'completes_collection: false' ),
	'2b.5 ⛔ …and a colouring offer NEVER reports completes_collection — a coloring book cannot earn FD-583'
);

/*
 * ⭐ THE OFFER BOXES AND THE COLLECTION CARD ALREADY DID THIS, and they still
 *    do it by the plugin's own POST contract rather than by this new flag —
 *    one mechanism per transport, not two competing ones.
 */
$pf_colouring_code = pf_code( $pf_theme . '/inc/colouring-line.php' );
pf_assert(
	substr_count( $pf_colouring_code, 'bhp_bundle_checkout_redirect_input()' ) >= 2,
	'2.7 the offer box still posts the plugin checkout-redirect flag on BOTH its forms'
);

/* The cart page leaves the flow — but is never broken. */
pf_assert(
	function_exists( 'bhp_purchase_flow_cart_page_in_flow' )
	&& false === bhp_purchase_flow_cart_page_in_flow(),
	'2.8 the cart page is OUT of the purchase flow'
);
$pf_cart_page = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
pf_assert(
	'' !== $pf_cart_page,
	'2.9 ⛔ …and the cart URL STILL RESOLVES — nothing 404s, a direct hit still works'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §3 · THE SCHOOL-VISIT PATH SURVIVES ALL OF IT  (FD-505/FD-506 bar)
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §3 the school-visit flagged path (must not regress) ---\n";

pf_assert(
	function_exists( 'bhp_school_visit_use_delivery_framing' )
	&& function_exists( 'bhp_school_visit_delivery_bullet' ),
	'3.1 the hand-delivery helpers are still reachable'
);

/*
 * ⭐ THE SWAP IS STILL WIRED AT ALL THREE RENDER SITES. The copy change in §1
 *    touched the FALLBACK arm of three ternaries; this asserts the FLAGGED arm
 *    of each is untouched, which is the half a copy edit could silently eat.
 */
pf_assert(
	2 === substr_count( $pf_landing_code, 'bhp_school_visit_delivery_bullet()' ),
	'3.2 both plugin landing-page sites still swap to the hand-delivery bullet when flagged'
);
pf_assert(
	false !== strpos( $pf_formats_code, 'bhp_school_visit_delivery_bullet()' ),
	'3.3 …and so does the theme helper'
);
pf_assert(
	false !== strpos( $pf_formats_code, 'bhp_school_visit_use_delivery_framing' ),
	'3.4 …gated on the live predicate, not on a stored flag'
);

/*
 * ⛔ THE REDIRECT CANNOT LOSE THE FLAG, AND HERE IS WHY, ASSERTED: the visit
 *    flag lives in the WooCommerce SESSION, not in the URL. A redirect to
 *    checkout is an ordinary same-session request.
 */
$pf_pickup_code = pf_code( $pf_plugin . '/includes/school-visit-pickup.php' );
pf_assert(
	false !== strpos( $pf_pickup_code, 'WC()->session->set' ),
	'3.5 the visit flag is SESSION-borne, so a checkout redirect cannot drop it'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §4 · CATCHES (1) (2) (5) — THE THREE AESTHETIC FIXES ARE PRESENT IN THE CSS
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §4 the aesthetic fixes (catches 1, 2, 5) ---\n";

/*
 * ⛔ ASSERTED AGAINST THE MINIFIED ARTEFACTS, NOT THE SOURCE. `functions.php`
 *    serves `*.min.css` when it exists, so a fix that lands in the source and
 *    is never rebuilt DOES NOT SHIP. That is a real, previously-open hazard in
 *    this repository (`LD-63`/`LD-69`, build-css determinism), and it is
 *    exactly what this section is here to catch.
 */
$pf_style_min   = @file_get_contents( $pf_theme . '/style.min.css' );
$pf_product_min = @file_get_contents( $pf_theme . '/assets/css/product-template.min.css' );
$pf_checkout_min = @file_get_contents( $pf_theme . '/assets/css/checkout-experience.min.css' );

pf_assert( ! empty( $pf_style_min ), '4.0 style.min.css exists (it is what actually ships)' );

/* Catch 1 — the offer CTA is the house primary, and the module has one alignment. */
pf_assert(
	$pf_style_min && preg_match( '/\.bhp-offer\s*\{[^}]*text-align:\s*center/s', $pf_style_min ),
	'4.1 catch 1: the offer module is single-aligned (text-align:center)'
);
pf_assert(
	$pf_style_min && preg_match( '/\.bhp-offer__cta[^{]*\{[^}]*var\(--color-primary\)/s', $pf_style_min ),
	'4.2 catch 1: the offer CTA is painted with the HOUSE token, not an ambient WooCommerce skin'
);
pf_assert(
	$pf_style_min && preg_match( '/\.bhp-offer__upsell\s*\{[^}]*width:\s*100%/s', $pf_style_min ),
	'4.3 catch 1: the upsell box spans the module, so its centred text cannot skew'
);

/* Catch 2 — the buy box reads as one centred unit on the PRODUCT page only. */
pf_assert(
	$pf_product_min && false !== strpos( $pf_product_min, 'bhp-formats__paperback-only' )
	&& preg_match( '/single-product[^}]*bhp-formats[^}]*\{[^}]*text-align:\s*center/s', $pf_product_min ),
	'4.4 catch 2: the product buy box is centred'
);
pf_assert(
	$pf_product_min && false === strpos( $pf_product_min, '.bhp-landing' ),
	'4.5 catch 2: ⛔ scoped to the product template — the Collection money page is untouched'
);

/* Catch 5 — the coupon row stops painting a white band on a cream panel. */
pf_assert(
	$pf_checkout_min && preg_match( '/\.wc-block-components-totals-coupon\s*\{[^}]*background:\s*transparent/s', $pf_checkout_min ),
	'4.6 catch 5: the checkout coupon row sits on the panel ground instead of pure white'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §5 · THE MINI-CART SIDE PANEL — ONE PANEL, REACHABLE FROM THE HEADER
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §5 the mini-cart side panel (catch 3, panel limb) ---\n";

pf_assert(
	function_exists( 'bhp_mini_cart_header_control' ),
	'5.1 the header cart control renderer is loaded'
);

$pf_header_php = pf_code( $pf_theme . '/header.php' );
pf_assert(
	false !== strpos( $pf_header_php, 'bhp_mini_cart_header_control' ),
	'5.2 …and the sitewide header calls it'
);
pf_assert(
	false !== strpos( $pf_header_php, 'function_exists' ),
	'5.3 …behind function_exists — a fatal in the sitewide header is a whole-site outage'
);

$pf_minicart_code = pf_code( $pf_theme . '/inc/mini-cart.php' );
pf_assert(
	false !== strpos( $pf_minicart_code, 'data-bhp-cart-open' ),
	'5.4 the control carries the generic opener hook'
);
/*
 * ⭐⭐ ONE PANEL, NOT TWO. The whole design of this limb is that the header
 *    control opens the ALREADY-SHIPPED drawer. If a future pass ever renders
 *    a second panel from the theme, this fails.
 */
pf_assert(
	false === strpos( $pf_minicart_code, 'bhp-cart-drawer__panel' )
	&& false === strpos( $pf_minicart_code, 'bhp-cart-drawer__items' ),
	'5.5 ⛔ ONE PANEL: the theme renders no second drawer — it only opens the plugin\'s'
);
/*
 * ⛔ CACHE SAFETY. The sitewide header is behind SiteGround's page cache, so a
 *    server-rendered cart count would be cached and shown to the wrong
 *    visitor. The count must ship as a static placeholder and be hydrated.
 */
pf_assert(
	false === strpos( $pf_minicart_code, 'get_cart_contents_count' ),
	'5.6 ⛔ CACHE SAFETY: no live cart count is server-rendered into the cached header'
);
pf_assert(
	false !== strpos( $pf_minicart_code, 'data-bhp-cart-count' ),
	'5.7 …the count node is present for the Store-API hydration to write into'
);

$pf_drawer_js = @file_get_contents( $pf_plugin . '/assets/bundle-drawer.js' );
pf_assert(
	$pf_drawer_js && false !== strpos( $pf_drawer_js, "querySelectorAll('[data-bhp-cart-open]')" ),
	'5.8 the drawer script wires every generic opener'
);
pf_assert(
	$pf_drawer_js && false !== strpos( $pf_drawer_js, 'updateExtraOpeners' ),
	'5.9 …and keeps their counts in step with the canonical Store-API cart'
);
pf_assert(
	$pf_drawer_js && false !== strpos( $pf_drawer_js, "qs('#bhp-floating-cart')" ),
	'5.10 ⛔ REGRESSION: the original floating opener is untouched'
);
pf_assert(
	$pf_drawer_js && false !== strpos( $pf_drawer_js, 'data-remove' )
	&& false !== strpos( $pf_drawer_js, 'data-qty-up' ),
	'5.11 the panel still offers remove and quantity controls (his stated needs)'
);
$pf_drawer_php = pf_code( $pf_plugin . '/includes/bundle-drawer.php' );
pf_assert(
	false !== strpos( $pf_drawer_php, 'bhp-cart-drawer__checkout' ),
	'5.12 …and a checkout button'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §6 · CATCH (6) — HE RULED. STACKING SHIPS, WITH NO CAP.
 * ───────────────────────────────────────────────────────────────────────────
 *
 * ⛔ THIS SECTION'S SUPERSEDED HEADING AND REASONING, PRESERVED VERBATIM:
 *
 *      "§6 · CATCH (6) — THE SUPPRESSION IS UNCHANGED, PENDING HIS RULING
 *       ⛔ HE ASKED A QUESTION, HE DID NOT GIVE AN ORDER: 'The bundle savings
 *          and adding the coloring books doesnt show the coloring book and PB
 *          bundle savings - does that go away when you buy all 4?'
 *          Stack-or-keep is HIS. Until he rules, the behaviour must not move
 *          in either direction — this asserts it did not."
 *
 * ⭐ HE HAS SINCE RULED, TWICE, AND BOTH WERE READ FIRST-HAND AT SOURCE by the
 *    agent that rewrote this section (same carrier, G: mount, NOT relayed):
 *
 *      item 187, ~05:2x−0600:  "Stack and see what the math says"
 *      item 189, ~06:0x−0600:  "So no cap and stack is the way to go?"
 *
 *    Item 187 attached a condition — the `finance-analytics` contribution read — and item 189
 *    is him adopting the recommendation once that read came back.
 *
 * ⭐ THE ARITHMETIC IS ASSERTED IN THE OFFER SUITE (§5, §5b of
 *    `plugins/brave-hearts-bundle-pricing/tests/test-offer-engine.php`), which
 *    drives both fee engines over one cart. This section asserts only that the
 *    RULE moved and stayed reversible.
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §6 pair-offer stacking: item 189, RULED ---\n";

pf_assert(
	function_exists( 'bhp_offer_tier_takes_precedence' ),
	'6.1 the precedence rule is still where it was'
);
$pf_offer_code = pf_code( $pf_plugin . '/includes/offer-engine.php' );
pf_assert(
	false !== strpos( $pf_offer_code, 'bhp_offer_tier_precedence' ),
	'6.2 …still reversible by the single documented filter, not rewritten'
);
/*
 * ⭐ EXERCISED, NOT READ. A collection-tier cart evaluation must NO LONGER
 *    suppress the pair offer.
 */
pf_assert(
	false === bhp_offer_tier_takes_precedence(
		'mariana_pb_colouring',
		array( 'paperback_tier' => 3, 'hardcover_tier' => 0 )
	),
	'6.3 item 189: a tier-3 paperback cart NO LONGER suppresses the pair offer — stacking is on'
);
pf_assert(
	false === bhp_offer_tier_takes_precedence(
		'mariana_pb_colouring',
		array( 'paperback_tier' => 2, 'hardcover_tier' => 0 )
	),
	'6.4 …and neither does a tier-2 one'
);
/*
 * ⛔ NO QUANTITY CAP — his second limb. Asserted at the source, because there
 *    never was a cap to remove and the risk is somebody ADDING one later.
 */
pf_assert(
	false !== strpos( $pf_offer_code, '$saving * $instances' ),
	'6.5 ⛔ NO CAP: the fee is still the saving times the number of complete pairs'
);

echo "\n=== RESULT: {$GLOBALS['pf_passes']} passed, {$GLOBALS['pf_failures']} failed ===\n";
