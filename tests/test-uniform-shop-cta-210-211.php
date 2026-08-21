<?php
/**
 * Brave Hearts — ONE BUTTON, SIX CARDS (theme 1.19.286 / bundle plugin 1.8.67).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-uniform-shop-cta-210-211.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE165-LD-UNIFORM-CTA`, CARRIER ITEMS 210 + 211
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, carrier items 210 and 211, 2026-08-21. ⚠️ RELAYED through
 *    `chief-of-staff` (Gandalf 9) in the build brief — ⛔ NOT witnessed
 *    first-hand by the agent that wrote this file. Recorded as relayed, per
 *    Standing Rules §9.2 rule 2.
 *
 *    ITEM 211: every shop card carries a UNIFORM "ADD TO CART" — one label,
 *    one size, bottom-aligned in its card, at 390 AND at 1440.
 *    ITEM 210: they all OPEN THE SIDE PANEL (the item-188 flow). Chapter cards
 *    add the PAPERBACK (`FD-439`); the colouring card adds itself; the pair
 *    and the collection add their bundle through the offer engine.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE SEVEN THINGS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 1. ONE LABEL, COUNTED (§2). Every `bhp-shop-atc` control in the served grid
 *    says ADD TO CART, and the four superseded labels are GONE from the grid.
 *    ⛔ Counting is the point: "they all say it" is a claim about a set, and a
 *    seventh card added later either joins the set or fails the count.
 *
 * 2. ONE CLASS, SO GEOMETRY IS A RULE RATHER THAN AN IMITATION (§3). Six
 *    controls, six `bhp-shop-atc` tokens. ⛔ THIS SUITE CANNOT MEASURE PIXELS
 *    — see the limitation note below — but it CAN prove that one selector
 *    governs all six, which is what makes the browser measurement meaningful
 *    instead of coincidental.
 *
 * 3. THE PANEL HOOK IS ON EVERY CARD (§4). Chapter and colouring cards carry
 *    `data-bhp-cart-add` + the ids the drawer adds; the two bundle cards carry
 *    NO `bhp_bundle_redirect` field, which is the mechanism that routes them
 *    to the panel rather than to /checkout/.
 *
 * 4. ⭐⭐ THE CHAPTER CARDS ADD THE PAPERBACK, NOT THE HARDCOVER (§5). `FD-439`.
 *    A card that quietly added a $17.99 hardcover from a grid showing $11.99
 *    beside it is a money defect, and it is the one this section is here for.
 *
 * 5. ⛔⛔ THE FOUNDER-WALKED DIRECT-BUY PATH IS UNTOUCHED (§6). The product
 *    page's offer form and the Collection page's CTA still post
 *    `bhp_bundle_redirect=checkout` and still finish on /checkout/. ⭐ Item 210
 *    changed the SHOP CARDS. A build that also re-routed the surfaces he walked
 *    would pass every other section here and still be wrong.
 *
 * 6. THE PLUGIN CONTRACT IS INTACT (§7). `interceptOfferForms()` exists and is
 *    wired; `offerAdds` is localized; and the 1.8.62 capability test
 *    (`/^(complete_|single_|any2_)/`) is BYTE-PRESENT and unwidened. ⛔ That
 *    test exists because widening it once produced a modal asking a customer to
 *    "choose exactly two different titles" on an offer with no titles.
 *
 * 7. THE CSS RULES ARE SERVED AT BOTH WIDTHS (§8) — in the base block and
 *    inside the ≤640px block. A uniform button defined only for desktop is not
 *    uniform at the viewport the founder checks on.
 *
 * ⛔⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads
 *    rendered documents, served stylesheets, source files and live functions.
 *    It proves presence, absence, cardinality, ids and rule text. It CANNOT
 *    prove GEOMETRY: that the six buttons render at the same width and height,
 *    that their bottoms sit on one baseline per row, that nothing overflows its
 *    card, or that the panel actually opens with the right contents. ⭐ ALL OF
 *    THAT NEEDS A REAL BROWSER WITH `window.innerWidth` ASSERTED, and none of
 *    it is claimed here. The browser evidence for this release is in the QA
 *    folder and is the authority on every one of those questions.
 *
 * ⛔ NO ORDER IS CREATED. NO CART IS BUILT. No product record, price, coupon,
 *    stock level, shipping, tax or payment setting is WRITTEN by any part of
 *    this file. Every call is a read.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⛔ STATIC ACCUMULATOR, NOT `global` — the `test-bundle-cards-206-207.php`
 *    correction, carried deliberately. Under `wp eval-file` the file's top
 *    level is NOT the global scope, so a `global $failures` against a
 *    file-scope `$failures` is two different variables and the suite reports
 *    "ALL CHECKS PASSED" while printing real failures.
 */
function uc_failures( $add = null ) {
	static $failures = array();
	if ( null !== $add ) {
		$failures[] = $add;
	}
	return $failures;
}

function uc_assert( $condition, $label ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	uc_failures( $label );
}

function uc_get( $url ) {
	$resp = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $resp );
}

/** Isolate one `<li>` of the product grid so a needle cannot be satisfied by a neighbour. */
function uc_isolate_li( $html, $class ) {
	if ( preg_match( '#<li[^>]*\bclass="[^"]*' . preg_quote( $class, '#' ) . '[^"]*"[^>]*>(.*?)</li>#s', $html, $m ) ) {
		return $m[0];
	}
	return '';
}

/** The `<ul class="products">` block only — never the header, nav or footer. */
function uc_isolate_grid( $html ) {
	if ( preg_match( '#<ul[^>]*\bclass="[^"]*\bproducts\b[^"]*"[^>]*>.*?</ul>#s', $html, $m ) ) {
		return $m[0];
	}
	return '';
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	fwrite( STDERR, "Cannot continue: WooCommerce is inactive.\n" );
	exit( 1 );
}

$uc_theme_dir  = get_template_directory();
$uc_shop_url   = wc_get_page_permalink( 'shop' );
$uc_shop_html  = uc_get( $uc_shop_url );

echo "\n=== §1 · THE ONE-LABEL API EXISTS, AND IT IS ONE PLACE ===\n";

uc_assert( function_exists( 'bhp_shop_card_atc_label' ), '1.1 bhp_shop_card_atc_label() exists' );
uc_assert( defined( 'BHP_SHOP_ATC_CLASS' ), '1.2 BHP_SHOP_ATC_CLASS is defined' );

$uc_label = function_exists( 'bhp_shop_card_atc_label' ) ? bhp_shop_card_atc_label() : '';
uc_assert( 'ADD TO CART' === $uc_label, "1.3 ⭐ the label is the founder word — got \"{$uc_label}\"" );

/*
 * ⛔ THE COPY RAILS, ASSERTED RATHER THAN ASSUMED (§9.1). The one string this
 *    release puts in front of a customer is this one. No "we/us/our" (he is
 *    the sole operator), no em dash, no outcome claim, no figure, no
 *    superlative — it names the action the control performs and stops.
 */
uc_assert( false === stripos( $uc_label, 'we ' ) && false === stripos( $uc_label, 'our ' ), '1.4 §9.1 — no "we"/"our" in the label' );
uc_assert( false === strpos( $uc_label, "\xe2\x80\x94" ), '1.5 no em dash in the label' );

/*
 * ⭐ THE PAIR CARD'S CTA DEFERS TO THE SAME FUNCTION rather than holding a
 *    sixth literal that happens to match today. This is the assertion that
 *    keeps the grid uniform through the NEXT release, not just this one.
 */
if ( function_exists( 'bhp_colouring_draft_copy' ) ) {
	uc_assert(
		bhp_colouring_draft_copy( 'offer_card_cta' ) === $uc_label,
		'1.6 ⭐⭐ the pair card CTA resolves to the SAME label function (not a matching literal)'
	);
	/*
	 * ⛔ AND THE PRODUCT-PAGE STRING IS UNTOUCHED. A string Andrew has seen keeps
	 *    its wording; only the shop card moved.
	 */
	uc_assert(
		false !== strpos( bhp_colouring_draft_copy( 'offer_cta', array( '$0.00' ) ) , 'ADD BOTH FOR' ),
		'1.7 ⛔ the PRODUCT-PAGE offer label is unchanged ("ADD BOTH FOR %s")'
	);
}

echo "\n=== §2 · THE SERVED GRID — ONE LABEL, COUNTED ===\n";

uc_assert( '' !== $uc_shop_html, "2.1 the shop archive returns HTTP 200 — {$uc_shop_url}" );
if ( '' === $uc_shop_html ) {
	fwrite( STDERR, "Cannot continue: the shop archive did not render.\n" );
	exit( 1 );
}

$uc_grid = uc_isolate_grid( $uc_shop_html );
uc_assert( '' !== $uc_grid, '2.2 the product grid (<ul class="products">) is isolated' );

$uc_cards = preg_match_all( '#<li[^>]*\bclass="[^"]*\bproduct\b#', $uc_grid );
$uc_atcs  = substr_count( $uc_grid, 'bhp-shop-atc' );

/*
 * ⛔⛔ THE CARD COUNT IS ENVIRONMENT-DEPENDENT AND THE SUITE SAYS SO RATHER THAN
 *     HARDCODING SIX. `FD-598` keeps the colouring product off PRODUCTION, and
 *     `R1.4` then removes the pair offer card with it — so production is FOUR
 *     cards and staging is SIX, and BOTH are correct. ⭐ A suite that demanded
 *     six would report correct production behaviour as a failure, and the third
 *     time that happens someone "fixes" it by weakening the assertion.
 *     ⭐ SO THE RULE IS ASSERTED, NOT THE OUTCOME: every card in the grid has
 *     exactly one uniform control, whatever the grid contains.
 */
echo "NOTE: 2.3 this environment's grid holds {$uc_cards} cards and {$uc_atcs} uniform controls\n";
uc_assert( $uc_cards > 0, '2.3a the grid renders at least one card' );
uc_assert(
	$uc_atcs === $uc_cards,
	"2.4 ⭐⭐ EVERY card carries exactly ONE uniform control — {$uc_atcs} controls / {$uc_cards} cards"
);

$uc_label_hits = substr_count( $uc_grid, 'ADD TO CART' );
uc_assert(
	$uc_label_hits === $uc_cards,
	"2.5 ⭐ the label appears once per card — {$uc_label_hits} occurrences / {$uc_cards} cards"
);

/*
 * ⛔ THE FOUR SUPERSEDED LABELS ARE GONE FROM THE GRID — and only from the grid.
 *    "GET THE COMPLETE COLLECTION" still lives on /complete-collection/, in the
 *    header offer and on the sticky bar, and `test-collection-cold-traffic.php`
 *    still asserts it there. Scoping this to `$uc_grid` is what keeps this
 *    section from quietly demanding a sitewide string purge nobody ruled on.
 */
foreach ( array( 'CHOOSE YOUR FORMAT', 'GET BOTH', 'GET THE COMPLETE COLLECTION' ) as $uc_dead ) {
	uc_assert(
		false === strpos( $uc_grid, $uc_dead ),
		"2.6 ⛔ the superseded label \"{$uc_dead}\" is GONE from the grid"
	);
}
/*
 * ⭐ WooCommerce's own sentence-case control, which the colouring card used to
 *    carry. Matched case-sensitively on purpose: "Add to cart" is core's
 *    string and "ADD TO CART" is the founder's; a case-insensitive check here
 *    would fail on the correct build.
 */
uc_assert(
	false === strpos( $uc_grid, '>Add to cart<' ),
	'2.7 ⛔ WooCommerce\'s own sentence-case "Add to cart" no longer renders on any card'
);

echo "\n=== §3 · THE CHAPTER AND COLOURING CARDS CARRY THE PANEL HOOK ===\n";

/*
 * ⛔ `data-bhp-cart-add` IS THE ITEM-188 HOOK, and it is the ONLY thing
 *    `interceptCartAddLinks()` in `bundle-drawer.js` looks for. No attribute,
 *    no panel — the anchor would navigate instead. ⭐ So this is the assertion
 *    that item 210 actually reached the four single-product cards.
 */
$uc_hooks = substr_count( $uc_grid, 'data-bhp-cart-add' );
$uc_bundle_cards = ( '' !== uc_isolate_li( $uc_grid, 'bhp-shop-offer-item' ) ? 1 : 0 )
	+ ( '' !== uc_isolate_li( $uc_grid, 'bhp-shop-collection-item' ) ? 1 : 0 );
$uc_single_cards = $uc_cards - $uc_bundle_cards;

echo "NOTE: 3.0 {$uc_single_cards} single-product cards, {$uc_bundle_cards} bundle cards\n";
uc_assert(
	$uc_hooks === $uc_single_cards,
	"3.1 ⭐ every single-product card carries the panel hook — {$uc_hooks} hooks / {$uc_single_cards} cards"
);

echo "\n=== §4 · ⭐⭐ FD-439 — A CHAPTER CARD ADDS THE PAPERBACK ===\n";

if ( ! function_exists( 'bhp_book_registry' ) || ! function_exists( 'bhp_book_shop_add_to_cart_link' ) ) {
	uc_assert( false, '4.0 the chapter-card CTA filter is available' );
} else {
	foreach ( bhp_book_registry() as $uc_key => $uc_book ) {
		$uc_li = uc_isolate_li( $uc_grid, 'post-' . (int) $uc_book['pb_product'] );
		if ( '' === $uc_li ) {
			echo "NOTE: 4.x [{$uc_key}] no card for product {$uc_book['pb_product']} in this grid — skipped\n";
			continue;
		}
		uc_assert(
			false !== strpos( $uc_li, 'data-product-id="' . (int) $uc_book['pb_product'] . '"' ),
			"4.1 [{$uc_key}] ⭐ the card's control adds the PAPERBACK product id"
		);
		uc_assert(
			false !== strpos( $uc_li, 'data-variation-id="' . (int) $uc_book['pb_variation'] . '"' ),
			"4.2 [{$uc_key}] the paperback variation id travels with it"
		);
		uc_assert(
			false === strpos( $uc_li, 'data-product-id="' . (int) $uc_book['hc_product'] . '"' ),
			"4.3 [{$uc_key}] ⛔⛔ it does NOT add the hardcover (a $17.99 add from a $11.99 card is a money defect)"
		);
		/*
		 * ⛔ THE HARDCOVER IS NOT REMOVED FROM THE CARD, and this asserts the
		 *    difference. Item 210 changed which edition the BUTTON adds; it did
		 *    not take the hardcover price line off the tile, and a build that
		 *    also dropped the line would be hiding a format rather than
		 *    defaulting to one.
		 */
		uc_assert(
			false !== strpos( $uc_li, 'bhp-shop-format-price' ),
			"4.4 [{$uc_key}] the card still names its formats and prices (nothing was hidden to make room)"
		);
		/*
		 * ⭐ THE HREF IS THE NO-JAVASCRIPT FLOOR AND IT MUST CARRY `bhp_buy=panel`,
		 *    which `inc/purchase-flow.php` turns into "stay on the product page".
		 *    ⛔ Without it that shopper lands on the CART PAGE FOOTER — the exact
		 *    thing carrier item 186 objected to.
		 */
		uc_assert(
			false !== strpos( $uc_li, 'bhp_buy=panel' ) || false !== strpos( $uc_li, 'bhp_buy&#61;panel' ),
			"4.5 [{$uc_key}] the fallback href is marked bhp_buy=panel (item 186: never the cart page)"
		);
	}
}

echo "\n=== §5 · THE BUNDLE CARDS ROUTE TO THE PANEL, NOT TO CHECKOUT ===\n";

foreach ( array( 'the pair card' => 'bhp-shop-offer-item', 'the collection card' => 'bhp-shop-collection-item' ) as $uc_what => $uc_class ) {
	$uc_li = uc_isolate_li( $uc_grid, $uc_class );
	if ( '' === $uc_li ) {
		/*
		 * ⛔ ABSENCE IS A CORRECT OUTCOME ON PRODUCTION (`FD-598` + `R1.4`), not
		 *    a failure — the pair offer has no colouring component there. Said
		 *    out loud rather than skipped silently.
		 */
		echo "NOTE: 5.x {$uc_what} is not in this grid (FD-598 / R1.4) — its assertions are skipped, not passed\n";
		continue;
	}
	uc_assert(
		false === strpos( $uc_li, 'name="bhp_bundle_redirect"' ),
		"5.1 ⭐⭐ {$uc_what} carries NO checkout-redirect field — so ADD TO CART opens the PANEL (item 210)"
	);
	uc_assert(
		false !== strpos( $uc_li, 'name="bhp_bundle_action"' ),
		"5.2 {$uc_what} still posts a real bundle action (the purchase mechanism is unchanged)"
	);
	uc_assert(
		false !== strpos( $uc_li, 'bhp_bundle_nonce' ),
		"5.3 {$uc_what} still carries the plugin nonce"
	);
	/*
	 * ⛔ THE CTA IS THE LAST CONTROL IN THE CARD. That is what lets
	 *    `margin-top:auto` pin it to the card floor so all six bottoms share one
	 *    baseline. ⭐ DOM ORDER, NOT a CSS `order` property — asserted here
	 *    because a later "tidy-up" that restored the old order would silently
	 *    break the alignment the founder asked for, and the CSS would still look
	 *    correct.
	 */
	$uc_atc_pos = strpos( $uc_li, 'bhp-shop-atc' );
	$uc_swap_pos = strpos( $uc_li, 'bhp-offer__upsell' );
	if ( false !== $uc_swap_pos ) {
		uc_assert(
			false !== $uc_atc_pos && $uc_swap_pos < $uc_atc_pos,
			"5.4 ⭐ {$uc_what} — the hardcover swap precedes the CTA in DOM order (the CTA is the card's last control)"
		);
	}
}

/* ⭐ The pair card's own opt-in, which is what `interceptOfferForms()` claims. */
$uc_pair_li = uc_isolate_li( $uc_grid, 'bhp-shop-offer-item' );
if ( '' !== $uc_pair_li ) {
	uc_assert(
		false !== strpos( $uc_pair_li, 'data-bhp-offer-panel' ),
		'5.5 ⭐⭐ the pair card opts in to the offer-form interceptor (`data-bhp-offer-panel`)'
	);
	uc_assert(
		false !== strpos( $uc_pair_li, 'name="bhp_offer_panel"' ),
		'5.6 it carries the no-JavaScript floor field, so a scriptless click lands on a product page, never the cart page'
	);
}

echo "\n=== §5b · ⛔⛔ THE CHANGE STOPS AT /shop/ ===\n";

/*
 * ⭐ THE BRIEF'S OWN BOUNDARY: *"only the SHOP CARDS change"*. ⛔ AND IT IS NOT
 *    FREE — `woocommerce_loop_add_to_cart_link` fires on EVERY product loop on
 *    the site, and the PRODUCT PAGE's related/upsell rows are
 *    `ul.products li.product` too. A real browser found TWO `bhp-shop-atc`
 *    controls on the Mariana product page before `bhp_shop_card_context()`
 *    existed — controls that ADD where the superseded ones NAVIGATED, on a
 *    surface nobody ruled on, and carrying none of the geometry, because every
 *    CSS rule in this release is scoped to `body.woocommerce-shop`.
 * ⭐ SO THE OFF-ARCHIVE BRANCH RENDERS 1.19.285's CONTROL, BYTE FOR BYTE.
 */
uc_assert( function_exists( 'bhp_shop_card_context' ), '5b.1 the scope gate exists' );
if ( function_exists( 'bhp_shop_card_context' ) && function_exists( 'bhp_book_shop_add_to_cart_link' ) && function_exists( 'bhp_book_registry' ) ) {
	uc_assert(
		false === bhp_shop_card_context(),
		'5b.2 under WP-CLI the gate is CLOSED (is_shop() cannot be true here) — so the branch below is the real off-archive one'
	);
	$uc_off_key  = array_key_first( bhp_book_registry() );
	$uc_off_book = bhp_book_registry()[ $uc_off_key ];
	$uc_off_prod = function_exists( 'wc_get_product' ) ? wc_get_product( $uc_off_book['pb_product'] ) : null;
	if ( $uc_off_prod ) {
		$uc_off_html = bhp_book_shop_add_to_cart_link( '<a>fallback</a>', $uc_off_prod );
		uc_assert(
			false !== strpos( $uc_off_html, 'CHOOSE YOUR FORMAT' ),
			'5b.3 ⭐ OFF the archive the related/upsell control is the 1.19.285 navigation anchor, unchanged'
		);
		uc_assert(
			false === strpos( $uc_off_html, 'data-bhp-cart-add' ) && false === strpos( $uc_off_html, 'bhp-shop-atc' ),
			'5b.4 ⛔ …and it carries NO panel hook and NO uniform token — no surface outside /shop/ gained a control'
		);
	}
}

echo "\n=== §6 · ⛔⛔ THE FOUNDER-WALKED DIRECT-BUY PATH IS UNTOUCHED ===\n";

/*
 * ⭐ HE WALKED THIS HIMSELF. The product page's offer cross-sell and the
 *    Collection page's CTA go straight to /checkout/, and item 210 said "only
 *    the SHOP CARDS change". A build that also re-routed these would pass every
 *    other section in this file and still be wrong, which is precisely why this
 *    section exists.
 */
if ( function_exists( 'bhp_offer_render_module' ) && function_exists( 'bhp_offer_is_purchasable' ) && bhp_offer_is_purchasable( 'mariana_pb_colouring' ) ) {
	$uc_pdp_module  = bhp_offer_render_module( 'mariana_pb_colouring', 'bhp-offer--product' );
	$uc_card_module = bhp_offer_render_module( 'mariana_pb_colouring', 'bhp-offer--card', false, true );

	uc_assert(
		false !== strpos( $uc_pdp_module, 'name="bhp_bundle_redirect"' ),
		'6.1 ⭐⭐ PRODUCT-PAGE mode still posts bhp_bundle_redirect (straight to /checkout/, as he walked it)'
	);
	uc_assert(
		false === strpos( $uc_pdp_module, 'data-bhp-offer-panel' ),
		'6.2 ⛔ PRODUCT-PAGE mode carries NO panel opt-in, so the interceptor can never claim it'
	);
	uc_assert(
		false !== strpos( $uc_pdp_module, 'ADD BOTH FOR' ),
		'6.3 PRODUCT-PAGE mode keeps its own label, byte for byte'
	);
	uc_assert(
		false === strpos( $uc_card_module, 'name="bhp_bundle_redirect"' ),
		'6.4 CARD mode does NOT post the checkout redirect'
	);
	uc_assert(
		false !== strpos( $uc_card_module, 'data-bhp-offer-panel' ),
		'6.5 CARD mode carries the panel opt-in'
	);
} else {
	echo "NOTE: 6.x the MT pair offer is not purchasable on this environment (FD-598) — §6 skipped, not passed\n";
}

/* ⭐ The Collection page's own CTA, which is a different function and a different surface. */
if ( function_exists( 'bhp_collection_add_to_cart_cta' ) ) {
	$uc_coll_cta = bhp_collection_add_to_cart_cta();
	uc_assert(
		false !== strpos( (string) $uc_coll_cta, 'name="bhp_bundle_redirect"' ),
		'6.6 ⭐ the COLLECTION PAGE CTA still posts the checkout redirect (untouched by this release)'
	);
}

echo "\n=== §7 · THE PLUGIN CONTRACT ===\n";

$uc_drawer_js = '';
$uc_js_path   = defined( 'BHP_BUNDLE_PRICING_DIR' ) ? BHP_BUNDLE_PRICING_DIR . 'assets/bundle-drawer.js' : '';
if ( $uc_js_path && file_exists( $uc_js_path ) ) {
	$uc_drawer_js = (string) file_get_contents( $uc_js_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local read in a test.
}
uc_assert( '' !== $uc_drawer_js, '7.1 bundle-drawer.js is readable on this environment' );

if ( '' !== $uc_drawer_js ) {
	uc_assert( false !== strpos( $uc_drawer_js, 'function interceptOfferForms' ), '7.2 interceptOfferForms() exists' );
	uc_assert( false !== strpos( $uc_drawer_js, 'interceptOfferForms();' ), '7.3 it is wired on DOMContentLoaded' );
	uc_assert(
		false !== strpos( $uc_drawer_js, 'form[data-bhp-offer-panel]' ),
		'7.4 ⛔ it claims ONLY opted-in forms — never every offer_* form on the site'
	);
	/*
	 * ⛔⛔ THE 1.8.62 CAPABILITY TEST MUST STILL BE THERE AND MUST STILL BE
	 *     NARROW. Widening it to include `offer_` would have been the obvious
	 *     way to build item 210 — and it is the exact change that once made
	 *     `interceptBundleForms()` claim an offer form, call preventDefault(),
	 *     fall through every branch and alert "Please choose exactly two
	 *     different titles for this bundle" while adding nothing to the cart.
	 */
	uc_assert(
		false !== strpos( $uc_drawer_js, '/^(complete_|single_|any2_)/' ),
		'7.5 ⭐⭐ the 1.8.62 capability test is byte-present and UNWIDENED (no `offer_` in it)'
	);
}

uc_assert( function_exists( 'bhp_offer_shop_add_payload' ), '7.6 bhp_offer_shop_add_payload() exists' );
if ( function_exists( 'bhp_offer_shop_add_payload' ) && function_exists( 'bhp_offer_components' ) ) {
	$uc_payload = bhp_offer_shop_add_payload();
	foreach ( $uc_payload as $uc_okey => $uc_row ) {
		$uc_components = bhp_offer_components( $uc_okey );
		$uc_expected   = array();
		foreach ( (array) $uc_components as $uc_c ) {
			$uc_expected[] = (int) $uc_c['buy_id'];
		}
		uc_assert(
			$uc_row['buy_ids'] === $uc_expected,
			"7.7 [{$uc_okey}] ⭐ the published ids are exactly the engine's own components (no second resolution)"
		);
		/*
		 * ⛔ IDS ONLY. A price, a saving or a discount crossing into the page
		 *    would be a figure the browser could drift from the invoice on. The
		 *    offer's money is a server-side cart fee and must stay there.
		 */
		uc_assert(
			array( 'buy_ids', 'format' ) === array_keys( $uc_row ),
			"7.8 ⛔ [{$uc_okey}] the payload publishes ids + format and NOTHING ELSE — no price, no saving, no copy"
		);
		/*
		 * ⛔ SAID EXPLICITLY BECAUSE IT IS THE POINT OF 7.8: the offer's money is
		 *    a server-side cart FEE (`bhp_offer_apply_fees()`), computed from what
		 *    is actually in the cart on every recalculation. A price or a saving
		 *    reaching the page would be a figure the browser could drift from the
		 *    invoice on, and this suite would be the last thing that could catch it.
		 */
		uc_assert(
			! isset( $uc_row['price'] ) && ! isset( $uc_row['saving'] ) && ! isset( $uc_row['label'] ),
			"7.9 ⛔⛔ [{$uc_okey}] no price, no saving and no customer copy crosses into the page"
		);
	}
}

echo "\n=== §8 · THE CSS RULES ARE SERVED, AT BOTH WIDTHS ===\n";

/*
 * ⭐ READ FROM THE FILE THE SITE ACTUALLY SERVES. `bhp_minified_style_src()`
 *    serves `style.min.css` when it exists, so checking `style.css` alone would
 *    pass a build whose minified artefact was never rebuilt — a real and
 *    already-observed failure mode in this repo.
 */
$uc_css_files = array( $uc_theme_dir . '/style.min.css', $uc_theme_dir . '/style.css' );
$uc_css       = '';
$uc_css_used  = '';
foreach ( $uc_css_files as $uc_f ) {
	if ( file_exists( $uc_f ) ) {
		$uc_css      = (string) file_get_contents( $uc_f ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local read in a test.
		$uc_css_used = basename( $uc_f );
		break;
	}
}
uc_assert( '' !== $uc_css, "8.1 a served stylesheet is readable ({$uc_css_used})" );

if ( '' !== $uc_css ) {
	uc_assert(
		substr_count( $uc_css, '.bhp-shop-atc' ) >= 4,
		'8.2 the uniform-button rules are present in the served stylesheet'
	);
	/*
	 * ⛔⛔ THESE TWO ASSERTIONS WERE REWRITTEN AFTER A REAL BROWSER FORCED A
	 *     REWRITE OF THE CSS, AND THE SUPERSEDED VERSIONS ARE QUOTED SO THE
	 *     MOVEMENT IS VISIBLE:
	 *
	 *       8.3  strpos( $css, 'margin: auto 1.5rem 1.5rem' )
	 *       8.4  strpos( $css, 'margin: auto 10px 10px' )
	 *
	 *     ⭐ They matched LITERAL VALUES. The first CSS pass hard-coded the
	 *     gutter and produced THREE different button widths and an overflow at
	 *     both viewports; the fix made the gutter a TOKEN each wrapper subtracts
	 *     its own box from, at which point the literals stopped existing and
	 *     these assertions failed against a build the browser had just measured
	 *     as correct.
	 * ⭐ THEY NOW ASSERT THE MECHANISM: the token is declared at BOTH widths,
	 *    and the bottom-pin (`margin-top: auto`) is served. ⛔ The WIDTHS and
	 *    the BASELINE themselves are not assertable from a stylesheet at all —
	 *    they are measured in a real browser at an asserted `innerWidth`, and
	 *    the QA folder is the authority on them.
	 */
	uc_assert(
		substr_count( $uc_css, '--bhp-card-gutter' ) >= 4,
		'8.3 ⭐ the shared gutter token is declared and consumed in the served stylesheet'
	);
	uc_assert(
		(bool) preg_match( '/--bhp-card-gutter:\s*10px/', $uc_css ),
		'8.4 ⭐ the token takes its MOBILE value too — a rule that only exists at 1440 is not uniform at 390'
	);
	uc_assert(
		(bool) preg_match( '/margin:\s*auto\s+/', $uc_css ),
		'8.4a the bottom-pin (`margin-top: auto` via the margin shorthand) is served'
	);
	uc_assert(
		(bool) preg_match( '/--bhp-collection-frame-pad/', $uc_css ),
		'8.4b ⭐ the collection frame subtracts its own box rather than being flattened'
	);
	uc_assert(
		false !== strpos( $uc_css, 'min-height:48px' ) || false !== strpos( $uc_css, 'min-height: 48px' ),
		'8.5 the 48px control floor is served (clear of the 44px touch minimum)'
	);
	/*
	 * ⛔ THE SUPERSEDED 1.19.284 RULE MUST NOT STILL BE ACTIVE. It set
	 *    `width:100%; margin:8px 0 0` on the two bundle CTAs specifically, and
	 *    leaving it live beside the new uniform rule is how two cards end up
	 *    8px off the other four at exactly the viewport he checks.
	 */
	uc_assert(
		false === strpos( $uc_css, 'margin:8px 0 0' ) || false === strpos( $uc_css, '.bhp-offer__cta{width:100%;margin:8px 0 0' ),
		'8.6 ⛔ the superseded per-bundle CTA rule is retired, not left live beside its replacement'
	);
}

echo "\n=== SUMMARY ===\n";
$uc_all = uc_failures();
if ( empty( $uc_all ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $uc_all ) . " FAILURE(S):\n";
foreach ( $uc_all as $uc_f2 ) {
	echo "  - {$uc_f2}\n";
}
exit( 1 );
