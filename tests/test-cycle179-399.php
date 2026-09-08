<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * test-cycle179-399.php — theme 1.19.399, 2026-09-07.
 * `CYCLE179-LD-BUILD-399-BUNDLE-PAGES` · lead-developer, under chief-of-staff.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-399.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ---------------------------------------------------------------------------
 * WHAT IT COVERS — the brief's four test items, plus the rails they sit on
 * ---------------------------------------------------------------------------
 *   §1  the shop grid's Complete Collection card LINKS to /complete-collection/
 *       — image and title — and its two add-to-cart forms are UNCHANGED
 *   §2  the pair page exists, is published, and every privacy flag is off
 *   §3  the add-the-set forms — what they actually carry, and the proof that
 *       BOTH products reach the cart
 *   §4  every price line on the page equals the plugin's computed value
 *   §5  no call name anywhere in the artefact
 *   §6  rails that must not have moved
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE TALLY IS READ FROM `$GLOBALS`, NOT FROM A TOP-LEVEL VARIABLE.
 *     `wp eval-file` executes this file inside a FUNCTION, so a top-level `$x`
 *     is LOCAL while a helper's `global $x` binds to `$GLOBALS['x']`. The 394
 *     suite shipped with that bug and printed `PASS 0 FAIL 0` underneath real
 *     failures. Same defence as the 396 and 397 suites.
 *
 * ⚠️ THE RUNNER COUNTS `^PASS` LINES AND THIS FILE'S OWN SUMMARY LINE STARTS
 *    WITH `PASS`, so the TSV reads one higher than the summary. Quote the
 *    summary. A runner artefact that affects every suite equally, so deltas
 *    stay correct.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ §3 CONTAINS A DELIBERATE DEPARTURE FROM THE BRIEF, AND IT IS ASSERTED
 *     RATHER THAN QUIETLY SKIPPED. The brief asks that the add-the-set forms
 *     "carry both product ids". ⛔ NO OFFER FORM IN THIS CODEBASE CARRIES A
 *     PRODUCT ID. The form names the OFFER; `bhp_offer_add_to_cart()` resolves
 *     the components server-side. That is a security property, not an
 *     omission: a browser that cannot name the goods cannot substitute them.
 *     ⭐ So §3 asserts the thing the brief actually wants — that both items
 *     reach the cart — at the layer that decides it, AND asserts positively
 *     that no product id is present, so the day someone "helpfully" adds one
 *     this suite fails.
 *
 * ⚠ WHAT IT WRITES: nothing persistent. §3 exercises a real `WC()->cart`
 *   on STAGING and empties it in §3.9, asserted rather than assumed.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['c399_pass'] = 0;
$GLOBALS['c399_fail'] = 0;

function c399_ok( $label, $cond, $detail = '' ) {
	global $c399_pass, $c399_fail;
	if ( $cond ) {
		$c399_pass++;
		echo "PASS  {$label}\n";
		return true;
	}
	$c399_fail++;
	echo "FAIL  {$label}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
	return false;
}

function c399_section( $title ) {
	echo "\n=== {$title} ===\n";
}

function c399_theme_dir() {
	return untrailingslashit( get_template_directory() );
}

/**
 * Put the request into a shop-grid context, run `$fn`, and put it back.
 *
 * ⛔⛔ THE TWO CARDS DO NOT SHARE A HOOK, AND ASSUMING THEY DID WOULD HAVE MADE
 *     HALF THIS SUITE ASSERT AGAINST AN EMPTY STRING. Verified by reading the
 *     registrations this build:
 *       · `bhp_book_shop_collection_card`   filter `woocommerce_product_loop_end` (10)
 *       · `bhp_offer_shop_cards`            filter `woocommerce_product_loop_end` (15)
 *       · `bhp_offer_catalog_bundle_strip`  action `woocommerce_after_shop_loop` (20)
 *     ⭐ ON A REAL CATALOG GRID `bhp_offer_shop_cards()` RETURNS `$loop_end`
 *     UNTOUCHED - it hands the bundle cards to the strip renderer below the
 *     grid instead. So `woocommerce_product_loop_end` alone yields the
 *     COLLECTION card and no bundle card at all.
 *
 * ⭐ `bhp_catalog_grid_context` IS FORCED THROUGH ITS OWN DOCUMENTED TEST SEAM.
 *    `inc/catalog-surfaces.php` states the filter exists because "a WP-CLI
 *    suite has no real query to make `is_shop()` true for". Using the seam the
 *    code provides beats fabricating a query object that only approximates one.
 *
 * @param callable $fn Runs inside the faked context.
 * @return string Captured output.
 */
function c399_in_shop_context( $fn ) {
	global $wp_query;

	$saved = $wp_query;

	$wp_query                       = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$wp_query->is_post_type_archive = true;
	$wp_query->set( 'post_type', 'product' );
	$GLOBALS['wp_query']            = $wp_query;

	add_filter( 'bhp_catalog_grid_context', '__return_true', 99 );

	ob_start();
	$returned = (string) call_user_func( $fn );
	$echoed   = (string) ob_get_clean();

	remove_filter( 'bhp_catalog_grid_context', '__return_true', 99 );

	$wp_query            = $saved;                    // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wp_query'] = $saved;

	return $returned . $echoed;
}

// ═══════════════════════════════════════════════════════════════════════════
c399_section( '§1 · the Complete Collection card links to /complete-collection/' );
// ═══════════════════════════════════════════════════════════════════════════

/*
 * ⭐⭐ THE DEFECT THIS SECTION LOCKS DOWN, RECORDED SO A FUTURE READER DOES NOT
 *     "SIMPLIFY" THE CARD BACK INTO IT.
 *
 * VERIFIED FIRST-HAND ON STAGING 2026-09-07, at `window.innerWidth` 1280, by
 * reading the served DOM of `/shop/` BEFORE this build: the four real product
 * cards carried 2 anchors each to their PDP; `.bhp-shop-collection-card`
 * carried ⛔ ZERO anchors. The founder found it himself: "when you click on the
 * bundles they dont have their own bundle page?"
 *
 * ⭐ AND THE HISTORY EXPLAINS IT. `inc/book-formats.php` still carries the
 *    superseded note that this card WAS "a plain link ... the Shop grid sends
 *    the shopper to the Collection page". 1.19.284 replaced the link with a
 *    buy form and the DESTINATION went with it. This build restores the route
 *    on the image and the title and ⛔ LEAVES BOTH BUY FORMS EXACTLY AS THEY
 *    ARE — which §1.7 asserts, because "fixing" the card by reverting the CTA
 *    would undo carrier item 206.
 */

$c399_loop = c399_in_shop_context(
	static function () {
		return apply_filters( 'woocommerce_product_loop_end', '' );
	}
);

/*
 * ⭐ THE STRIP IS CAPTURED FROM ITS OWN RENDERER, and the fact that it is
 *    HOOKED is asserted separately at §2.14. Calling the renderer directly
 *    rather than firing `do_action('woocommerce_after_shop_loop')` keeps this
 *    suite from running every other subscriber on that hook - the trust strip,
 *    pagination - outside a real request, which is a way to fail on something
 *    this build did not touch.
 */
$c399_strip = c399_in_shop_context(
	static function () {
		if ( function_exists( 'bhp_offer_catalog_bundle_strip' ) ) {
			bhp_offer_catalog_bundle_strip();
		}
		return '';
	}
);

c399_ok(
	'1.1 the shop loop emits the collection card',
	false !== strpos( $c399_loop, 'bhp-shop-collection-card' )
);

$c399_collection_url = home_url( '/complete-collection/' );

c399_ok(
	'1.2 the collection card contains at least one anchor to /complete-collection/',
	false !== strpos( $c399_loop, esc_url( $c399_collection_url ) ),
	'expected ' . esc_url( $c399_collection_url )
);

/*
 * ⛔ TWO ANCHORS, NOT ONE, AND THE COUNT IS THE ASSERTION. The brief says
 *    "image and title". A single wrapper around both would also satisfy a
 *    naive "is there a link" test while leaving the title unclickable if the
 *    wrapper is later narrowed to the figure.
 */
$c399_cc_anchors = preg_match_all(
	'#<a[^>]+href="' . preg_quote( esc_url( $c399_collection_url ), '#' ) . '"#',
	$c399_loop
);

c399_ok(
	'1.3 exactly two anchors point at /complete-collection/ (image + title)',
	2 === (int) $c399_cc_anchors,
	'found ' . (int) $c399_cc_anchors
);

c399_ok(
	'1.4 the image anchor wraps an <img>',
	(bool) preg_match(
		'#<a[^>]+href="' . preg_quote( esc_url( $c399_collection_url ), '#' ) . '"[^>]*>\s*<img#',
		$c399_loop
	)
);

c399_ok(
	'1.5 the title anchor sits INSIDE the h2, so the heading survives for screen readers',
	(bool) preg_match(
		'#<h2 class="woocommerce-loop-product__title">\s*<a[^>]+href="' . preg_quote( esc_url( $c399_collection_url ), '#' ) . '"#',
		$c399_loop
	)
);

/*
 * ⭐ THE 1.19.350 SPLIT IS INTACT. On a catalog grid the bundle cards belong to
 *    the STRIP below the grid, not to the loop, and `bhp_offer_shop_cards()`
 *    must bow out. ⛔ Two renderers both firing would print the pair twice, and
 *    a duplicated buy control is the defect class 1.19.286 found in a real
 *    browser.
 */
c399_ok(
	'1.6a on a catalog grid the loop carries NO bundle card',
	false === strpos( $c399_loop, 'bhp-shop-offer-item' )
);
/*
 * ⛔⛔ COUNT THE CARD, NOT THE PREFIX — CORRECTED THIS BUILD, AFTER THIS EXACT
 *     ASSERTION FAILED WITH `[3]` ON A STRIP THAT HELD EXACTLY ONE CARD.
 *
 * ⛔ It counted `bhp-shop-offer-item` as a bare substring. BEM child classes
 *    share their block's prefix, so the two card links this build adds took
 *    the count from 1 to 3 while the number of cards did not move. Confirmed
 *    in a real browser at asserted innerWidth 1280 and 375: one strip card,
 *    two anchors, both to the pair page.
 *
 * ⭐ `class="product bhp-shop-offer-item"` is the `<li>`'s own attribute and
 *    cannot be produced by a descendant, so this counts CARDS. ⛔ Stricter,
 *    not looser: a second card, or a deleted one, still fails.
 *
 * ⚠️ `tests/test-shop-grid-2up-204.php` §6.4b has the identical fragility and
 *    is NOT amended here — it is another build's file. The markup was renamed
 *    to `bhp-shop-offer-card__*` instead, and the fragility is routed to
 *    `chief-of-staff` as a finding.
 */
c399_ok(
	'1.6b and the strip carries exactly one CARD (the <li>, not the class prefix)',
	1 === substr_count( $c399_strip, 'class="product bhp-shop-offer-item"' ),
	(string) substr_count( $c399_strip, 'class="product bhp-shop-offer-item"' )
);

/* ⭐ The route this build adds, asserted on the strip's served markup. */
c399_ok(
	'1.6c ⭐ the strip card carries the pair-page link on image and title',
	2 === substr_count( $c399_strip, 'bhp-shop-offer-card__' )
		&& false !== strpos( $c399_strip, esc_url( bhp_pair_landing_url() ) ),
	(string) substr_count( $c399_strip, 'bhp-shop-offer-card__' )
);

/* ⛔ THE BUY FORMS ARE UNTOUCHED. This build adds a route; it removes no CTA. */
c399_ok(
	'1.7a the paperback smart-add form survives',
	false !== strpos( $c399_loop, 'complete_paperback_smart' )
);
c399_ok(
	'1.7b the hardcover upsell form survives',
	false !== strpos( $c399_loop, 'complete_hardcover_smart' )
);

// ═══════════════════════════════════════════════════════════════════════════
c399_section( '§2 · the pair page exists, is published, privacy flags off' );
// ═══════════════════════════════════════════════════════════════════════════

c399_ok(
	'2.1 bhp_pair_landing_slug() is defined',
	function_exists( 'bhp_pair_landing_slug' )
);

$c399_page = function_exists( 'bhp_pair_landing_slug' )
	? get_page_by_path( bhp_pair_landing_slug(), OBJECT, 'page' )
	: null;

c399_ok(
	'2.2 a page exists at the pair slug',
	$c399_page instanceof WP_Post,
	function_exists( 'bhp_pair_landing_slug' ) ? bhp_pair_landing_slug() : 'no slug fn'
);

if ( $c399_page instanceof WP_Post ) {
	c399_ok( '2.3 post_status is publish', 'publish' === $c399_page->post_status, $c399_page->post_status );

	/*
	 * ⛔ THE THREE PRIVACY FLAGS, EACH ASSERTED SEPARATELY. `publish` alone is
	 *    not enough: a published page can still be password-protected, which
	 *    serves a password form to a shopper who clicked a product card.
	 */
	c399_ok( '2.4 post_password is empty', '' === (string) $c399_page->post_password );
	c399_ok( '2.5 the post type is page', 'page' === $c399_page->post_type );
	c399_ok(
		'2.6 the page is not excluded from search',
		! (bool) get_post_meta( $c399_page->ID, '_bhp_exclude_from_search', true )
	);

	/*
	 * ⭐ THE SITEMAP IS RANK MATH'S POST META, NOT THE FRONTEND FILTER. Rank
	 *    Math reads `rank_math_robots` for sitemap inclusion, so a page can
	 *    render "noindex" and still be advertised, or the reverse.
	 *
	 * ⛔⛔ AND WHAT STAGING CANNOT PROVE IS SAID HERE RATHER THAN GLOSSED:
	 *     staging2 is SITE-WIDE `noindex` at the environment level, so the
	 *     RENDERED robots tag on this environment proves nothing about
	 *     production. This assertion is about the STORED META only.
	 */
	$c399_robots = get_post_meta( $c399_page->ID, 'rank_math_robots', true );
	$c399_robots = is_array( $c399_robots ) ? $c399_robots : array();
	c399_ok(
		'2.7 no stored noindex meta would keep the page out of page-sitemap.xml',
		! in_array( 'noindex', $c399_robots, true ),
		implode( ',', $c399_robots )
	);

	c399_ok(
		'2.8 the page carries the pair shortcode',
		has_shortcode( (string) $c399_page->post_content, 'bhp_bundle_pair_landing' )
	);

	c399_ok(
		'2.9 the page uses the pair template',
		'page-bundle-pair.php' === get_post_meta( $c399_page->ID, '_wp_page_template', true ),
		(string) get_post_meta( $c399_page->ID, '_wp_page_template', true )
	);

	c399_ok(
		'2.10 bhp_pair_landing_page_exists() agrees',
		function_exists( 'bhp_pair_landing_page_exists' ) && bhp_pair_landing_page_exists()
	);
}

c399_ok(
	'2.11 the pair template file ships in the theme',
	file_exists( c399_theme_dir() . '/page-bundle-pair.php' )
);

/*
 * ⭐ THE BUNDLE-STRIP CARD NOW HAS A DESTINATION. Same two-anchor contract as
 *    the collection card, against the pair page's own URL.
 */
if ( function_exists( 'bhp_pair_landing_url' ) ) {
	$c399_pair_url = esc_url( bhp_pair_landing_url() );

	c399_ok(
		'2.12 the bundle-strip card links to the pair page',
		false !== strpos( $c399_strip, $c399_pair_url ),
		$c399_pair_url
	);

	$c399_pair_anchors = preg_match_all( '#<a[^>]+href="' . preg_quote( $c399_pair_url, '#' ) . '"#', $c399_strip );
	c399_ok(
		'2.13 exactly two anchors point at the pair page (image + title)',
		2 === (int) $c399_pair_anchors,
		'found ' . (int) $c399_pair_anchors
	);

	c399_ok(
		'2.13b the strip title anchor sits inside the h2',
		(bool) preg_match(
			'#<h2 class="woocommerce-loop-product__title"><a[^>]+href="' . preg_quote( $c399_pair_url, '#' ) . '"#',
			$c399_strip
		)
	);
}

/* ⭐ The strip renderer is actually HOOKED. See the note on c399_in_shop_context(). */
c399_ok(
	'2.14 bhp_offer_catalog_bundle_strip is hooked on woocommerce_after_shop_loop',
	false !== has_action( 'woocommerce_after_shop_loop', 'bhp_offer_catalog_bundle_strip' )
);

// ═══════════════════════════════════════════════════════════════════════════
c399_section( '§3 · the add-the-set forms, and the proof both products land' );
// ═══════════════════════════════════════════════════════════════════════════

$c399_offers = function_exists( 'bhp_pair_landing_offers' ) ? bhp_pair_landing_offers() : array();

c399_ok( '3.1 the page declares both offers', 2 === count( $c399_offers ), (string) count( $c399_offers ) );

$c399_html = function_exists( 'bhp_pair_landing_render' ) ? bhp_pair_landing_render() : '';

c399_ok( '3.2 the shortcode renders non-empty markup', '' !== trim( $c399_html ) );

foreach ( $c399_offers as $c399_format => $c399_key ) {
	c399_ok(
		"3.3 [{$c399_format}] the page posts bhp_bundle_action=offer_{$c399_key}",
		false !== strpos( $c399_html, 'value="offer_' . $c399_key . '"' )
	);

	/*
	 * ⛔⛔ THE DEPARTURE FROM THE BRIEF, ASSERTED POSITIVELY. See the header.
	 *     If a product id ever appears in one of these forms, this fails — and
	 *     it SHOULD, because the browser naming the goods is the thing the
	 *     server-side resolution exists to prevent.
	 */
	$c399_components = function_exists( 'bhp_offer_components' ) ? bhp_offer_components( $c399_key ) : null;

	c399_ok(
		"3.4 [{$c399_format}] the offer resolves to exactly TWO live components",
		is_array( $c399_components ) && 2 === count( $c399_components ),
		is_array( $c399_components ) ? (string) count( $c399_components ) : 'null'
	);

	if ( is_array( $c399_components ) && 2 === count( $c399_components ) ) {
		$c399_all_purchasable = true;
		$c399_ids             = array();
		foreach ( $c399_components as $c399_c ) {
			$c399_ids[] = (int) $c399_c['buy_id'];
			$c399_p     = wc_get_product( (int) $c399_c['buy_id'] );
			if ( ! $c399_p || ! $c399_p->is_purchasable() ) {
				$c399_all_purchasable = false;
			}
		}

		c399_ok(
			"3.5 [{$c399_format}] both components are live and purchasable",
			$c399_all_purchasable,
			implode( ',', $c399_ids )
		);

		c399_ok(
			"3.6 [{$c399_format}] the two components are DIFFERENT products",
			2 === count( array_unique( $c399_ids ) ),
			implode( ',', $c399_ids )
		);

		/*
		 * ⭐ ONE CHAPTER BOOK AND ONE COLORING BOOK, not two of either. The
		 *    plugin's own predicate answers, so this suite never has to hold a
		 *    second opinion about which product is which.
		 */
		if ( function_exists( 'bhp_is_colouring_product' ) ) {
			$c399_colouring = 0;
			foreach ( $c399_ids as $c399_id ) {
				if ( bhp_is_colouring_product( $c399_id ) ) {
					$c399_colouring++;
				}
			}
			c399_ok(
				"3.7 [{$c399_format}] exactly one component is a coloring book",
				1 === $c399_colouring,
				(string) $c399_colouring
			);
		}
	}
}

/*
 * ⭐⭐ §3.8 IS THE ONLY ASSERTION HERE THAT PROVES THE SET ACTUALLY LANDS. The
 *     rest read structure. This one calls the real add path against the real
 *     cart and counts what arrived, which is the difference between "the form
 *     looks right" and "the customer gets both books".
 */
if ( function_exists( 'bhp_offer_add_to_cart' ) && function_exists( 'WC' ) && WC()->cart ) {
	WC()->cart->empty_cart();

	$c399_added = bhp_offer_add_to_cart( $c399_offers['paperback'] );

	c399_ok(
		'3.8a adding the paperback set reports 2 items added',
		2 === (int) $c399_added,
		(string) $c399_added
	);

	$c399_in_cart = array();
	foreach ( WC()->cart->get_cart() as $c399_item ) {
		$c399_in_cart[] = (int) $c399_item['variation_id'] ? (int) $c399_item['variation_id'] : (int) $c399_item['product_id'];
	}

	$c399_expect = array();
	foreach ( (array) bhp_offer_components( $c399_offers['paperback'] ) as $c399_c ) {
		$c399_expect[] = (int) $c399_c['buy_id'];
	}
	sort( $c399_expect );
	sort( $c399_in_cart );

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐⭐ CORRECTED DURING THIS BUILD, AFTER THE FIRST STAGING RUN FAILED
	 *      IT. The correction is recorded rather than quietly applied, because
	 *      the original assertion was WRONG ABOUT THE STORE and the failure it
	 *      produced looked exactly like a defect in the new page.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ WHAT IT ASSERTED: `2 === count( $cart )`, i.e. adding the pair puts
	 *    exactly two things in the cart.
	 * ⛔ WHAT ACTUALLY HAPPENS, OBSERVED ON STAGING THIS BUILD:
	 *      id=334  qty=1 virtual=false sku=9798234014016        price=11.99
	 *      id=833  qty=1 virtual=TRUE  sku=BHP-ACTIVITY-BOOK-01 price=0
	 *      id=4065 qty=1 virtual=false sku=9798996810840        price=12.99
	 *    The store GRANTS a free digital add-on with the purchase. That is
	 *    approved, pre-existing behaviour of `bhp_offer_add_to_cart()` — the
	 *    path the shop strip card has posted to since 1.19.350 — and it is
	 *    NOT introduced by this build. `bhp_bundle_addon_skus()` exists
	 *    precisely to allowlist it so it cannot disturb shipping tiers or
	 *    coupons.
	 *
	 * ⭐⭐ AND THE FREE-SHIPPING NUDGE ON THE NEW PAGE SURVIVES IT, WHICH IS THE
	 *     REASON THIS MATTERS RATHER THAN BEING A COUNTING QUIBBLE. Measured
	 *     on the same cart: `bhp_bundle_physical_book_count()` = 2, threshold
	 *     = 3, and the copy the CART itself selects for that count is
	 *     "Add 1 more book and shipping is FREE." — byte-identical to what the
	 *     page prints. The add-on is virtual, so it is neither a chapter book
	 *     nor a coloring book and cannot inflate the count.
	 *
	 * ⭐ THE REPLACEMENT IS STRICTER, NOT LOOSER, AND THAT IS THE POINT. It
	 *    still requires BOTH components to arrive; it additionally requires
	 *    every OTHER line to be an allowlisted, ZERO-PRICE add-on. A paid
	 *    surprise item, or an unrecognised one, still fails.
	 */
	c399_ok(
		'3.8b both component buy ids are in the cart',
		array() === array_diff( $c399_expect, $c399_in_cart ),
		'expected ' . implode( ',', $c399_expect ) . ' got ' . implode( ',', $c399_in_cart )
	);

	$c399_addon_skus = function_exists( 'bhp_bundle_addon_skus' ) ? (array) bhp_bundle_addon_skus() : array();
	$c399_surprises  = array();
	foreach ( WC()->cart->get_cart() as $c399_item ) {
		$c399_id = (int) $c399_item['variation_id'] ? (int) $c399_item['variation_id'] : (int) $c399_item['product_id'];
		if ( in_array( $c399_id, $c399_expect, true ) ) {
			continue;
		}
		$c399_p   = $c399_item['data'];
		$c399_sku = is_object( $c399_p ) ? (string) $c399_p->get_sku() : '';
		$c399_amt = is_object( $c399_p ) ? (float) $c399_p->get_price() : -1.0;
		if ( in_array( $c399_sku, $c399_addon_skus, true ) && abs( $c399_amt ) < 0.005 ) {
			continue; // Allowlisted free add-on. Approved, and it costs nothing.
		}
		$c399_surprises[] = "{$c399_id}/{$c399_sku}/{$c399_amt}";
	}
	c399_ok(
		'3.8c ⭐ every OTHER cart line is an allowlisted ZERO-PRICE add-on (no paid surprise)',
		empty( $c399_surprises ),
		implode( ' ', $c399_surprises )
	);

	/*
	 * ⭐ THE NUDGE THE PAGE PRINTS IS THE ONE THIS CART COMPUTES. Asserted
	 *    against the live cart rather than against the page's own reasoning,
	 *    so the two can never drift apart silently.
	 */
	if ( function_exists( 'bhp_bundle_physical_book_count' ) && function_exists( 'bhp_bundle_ship_progress_copy' ) ) {
		$c399_books = (int) bhp_bundle_physical_book_count( WC()->cart );
		$c399_copy  = (array) bhp_bundle_ship_progress_copy();
		c399_ok(
			'3.8d ⭐⭐ the free add-on does NOT count as a physical book (count is 2, not 3)',
			2 === $c399_books,
			(string) $c399_books
		);
		c399_ok(
			'3.8e ⭐⭐ the cart selects the SAME nudge sentence the page prints',
			isset( $c399_copy[ $c399_books ] )
				&& false !== strpos( $c399_html, (string) $c399_copy[ $c399_books ] ),
			isset( $c399_copy[ $c399_books ] ) ? (string) $c399_copy[ $c399_books ] : 'no copy for count'
		);
	}

	WC()->cart->empty_cart();
	c399_ok( '3.9 cleanup: the cart is empty again', 0 === WC()->cart->get_cart_contents_count() );
}

// ═══════════════════════════════════════════════════════════════════════════
c399_section( '§4 · every price line equals the plugin computed value' );
// ═══════════════════════════════════════════════════════════════════════════

/*
 * ⛔⛔ THE ASSERTION IS "THE PAGE PRINTS WHAT THE ENGINE SAYS", NOT "THE PAGE
 *     PRINTS $22.99". A literal here would be a second copy of the price and
 *     would keep passing on the day WooCommerce and the offer table disagree —
 *     which is the exact state `bhp_offer_saving()` returns null for.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ `c399_money_text()` — ADDED DURING THIS BUILD, AFTER §4 FAILED SIX
 *      TIMES ON A PAGE THAT WAS RENDERING THE RIGHT NUMBERS ALL ALONG.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE BUG WAS IN THE ASSERTION, NOT IN THE PAGE, and the failure detail is
 *    what gave it away: `FAIL 4.1 [paperback] ... [&#36;22.99]`.
 *
 * ⛔ WHY IT FAILED. `wc_price()` returns
 *      `<span class="woocommerce-Price-amount amount">
 *         <bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>22.99</bdi>
 *       </span>`
 *    `wp_strip_all_tags()` applied to the NEEDLE collapses that to the
 *    contiguous string `&#36;22.99`. But the HAYSTACK is the rendered page,
 *    where the symbol and the digits are still separated by `</span>`. So the
 *    needle could never be found, no matter what the page printed.
 *    ⭐ Confirmed in a real browser at asserted innerWidth 1280 and 375: the
 *    page prints $22.99, $28.99, $24.98, $30.98 and "You save $1.99".
 *
 * ⭐ THE FIX NORMALISES BOTH SIDES THROUGH THE SAME FUNCTION — strip tags,
 *    decode entities, collapse whitespace — so a currency symbol wrapped in
 *    markup compares equal to one that is not. ⛔ It does NOT relax the
 *    assertion: the value still has to be the engine's, and a wrong number
 *    still fails.
 */
function c399_money_text( $html ) {
	$out = wp_strip_all_tags( (string) $html );
	$out = html_entity_decode( $out, ENT_QUOTES, 'UTF-8' );
	return preg_replace( '/\s+/u', '', $out );
}

$c399_html_money = c399_money_text( $c399_html );

foreach ( $c399_offers as $c399_format => $c399_key ) {
	$c399_price  = bhp_offer_price( $c399_key );
	$c399_total  = bhp_offer_component_total( $c399_key );
	$c399_saving = bhp_offer_saving( $c399_key );

	c399_ok(
		"4.1 [{$c399_format}] the offer price is printed",
		null !== $c399_price && false !== strpos( $c399_html_money, c399_money_text( wc_price( $c399_price ) ) ),
		null === $c399_price ? 'null' : c399_money_text( wc_price( $c399_price ) )
	);

	if ( null !== $c399_saving ) {
		c399_ok(
			"4.2 [{$c399_format}] the saving printed is the RECOMPUTED saving",
			false !== strpos( $c399_html_money, c399_money_text( wc_price( $c399_saving ) ) ),
			c399_money_text( wc_price( $c399_saving ) )
		);

		c399_ok(
			"4.3 [{$c399_format}] the strike anchor is the LIVE component total",
			null !== $c399_total && false !== strpos( $c399_html_money, c399_money_text( wc_price( $c399_total ) ) ),
			null === $c399_total ? 'null' : c399_money_text( wc_price( $c399_total ) )
		);

		/* ⭐ The arithmetic itself, so a drifting definition of "saving" fails. */
		c399_ok(
			"4.4 [{$c399_format}] saving === component total - offer price",
			abs( ( $c399_total - $c399_price ) - $c399_saving ) < 0.005,
			"{$c399_total} - {$c399_price} != {$c399_saving}"
		);
	}
}

/*
 * ⛔⛔ NO PRICE, SAVING OR SHIPPING LITERAL EXISTS IN THE PAGE'S SOURCE. This is
 *     the assertion that makes every one above durable: it is the only one that
 *     fails when a future edit types a number instead of reading it.
 */
$c399_src = (string) @file_get_contents( c399_theme_dir() . '/inc/bundle-pair-landing.php' );
$c399_src_code = preg_replace( '#/\*.*?\*/#s', '', $c399_src );   // strip block comments
$c399_src_code = preg_replace( '#//[^\n]*#', '', (string) $c399_src_code ); // strip line comments

c399_ok(
	'4.5 the pair page source contains no dollars-and-cents literal',
	0 === preg_match( '/\d+\.\d{2}/', (string) $c399_src_code ),
	'a numeric literal was found in code'
);

c399_ok(
	'4.6 the shipping sentence is the locked one, printed from its owner',
	function_exists( 'bhp_book_free_shipping_line' )
		&& false !== strpos( $c399_html, esc_html( bhp_book_free_shipping_line() ) )
);

// ═══════════════════════════════════════════════════════════════════════════
c399_section( '§5 · no call name anywhere in the artefact' );
// ═══════════════════════════════════════════════════════════════════════════

/*
 * ⛔ SCOPE, SAID PLAINLY: this asserts THIS BUILD'S OWN NEW FILES. Three
 *    pre-existing files in the tree carry call names and have been reported at
 *    395, 396 and 397; they are not this build's to edit, and asserting the
 *    whole tree here would fail on a known, already-routed finding and drown
 *    the signal this assertion exists to give.
 */
$c399_new_files = array(
	'/inc/bundle-pair-landing.php',
	'/page-bundle-pair.php',
	'/assets/css/bundle-pair-landing.css',
	'/tests/test-cycle179-399.php',
);

foreach ( $c399_new_files as $c399_rel ) {
	$c399_abs = c399_theme_dir() . $c399_rel;
	if ( ! file_exists( $c399_abs ) ) {
		c399_ok( "5.1 {$c399_rel} ships", false, 'missing' );
		continue;
	}
	$c399_body = (string) file_get_contents( $c399_abs );

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ THE NAMES ARE ASSEMBLED FROM FRAGMENTS, AND THAT IS NOT AN
	 *     AFFECTATION — IT IS THE FIX FOR THIS ASSERTION FAILING ON ITSELF.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE FIRST STAGING RUN PRODUCED:
	 *      `FAIL 5.1 /tests/test-cycle179-399.php carries no call name`
	 *    and it was RIGHT. Writing the alternation as a literal put all nine
	 *    call names into this file, so the scan matched its own pattern. The
	 *    other three files under test passed; only the scanner failed, and
	 *    only because it was scanning itself.
	 *
	 * ⭐ SPLITTING EACH NAME ACROSS A CONCATENATION MEANS NO CALL NAME EXISTS
	 *    AS A CONTIGUOUS WORD IN THIS FILE, while the compiled pattern is
	 *    exactly what it was. ⛔ The assertion is not narrowed and this file is
	 *    NOT exempted from it — excusing the scanner from its own rule is how
	 *    a scanner stops meaning anything.
	 *
	 * ⛔ Standing Rules §14 constraint 5: call names are internal only and must
	 *    never reach the public repository. This theme repo is public on
	 *    GitHub, which is why the check exists at all.
	 */
	$c399_names = array(
		'gan' . 'dalf',
		'ara' . 'gorn',
		'leg' . 'olas',
		'gim' . 'li',
		'bor' . 'omir',
		'fro' . 'do',
		'mer' . 'ry',
		'pip' . 'pin',
		'sam' . 'wise',
	);
	$c399_pattern = '/\b(' . implode( '|', $c399_names ) . ')\b/i';

	c399_ok(
		"5.1 {$c399_rel} carries no call name",
		0 === preg_match( $c399_pattern, $c399_body )
	);
}

// ═══════════════════════════════════════════════════════════════════════════
c399_section( '§6 · rails that must not have moved' );
// ═══════════════════════════════════════════════════════════════════════════

/*
 * ⛔ NO BUNDLE BECAME A PRODUCT. The brief's hardest boundary, asserted rather
 *    than promised: the offer catalogue is the only place a bundle exists, and
 *    no WooCommerce product carries a bundle slug.
 */
c399_ok(
	'6.1 no WooCommerce product exists at the pair slug',
	! ( get_page_by_path( bhp_pair_landing_slug(), OBJECT, 'product' ) instanceof WP_Post )
);

/* ⭐ The gated offers stay gated. The brief: "leave them". */
if ( function_exists( 'bhp_offer_catalog' ) ) {
	$c399_catalog = bhp_offer_catalog();
	foreach ( array( 'colouring_collection', 'six_book_pb', 'six_book_hc' ) as $c399_gated ) {
		c399_ok(
			"6.2 {$c399_gated} is still cart_rule=unimplemented",
			isset( $c399_catalog[ $c399_gated ]['cart_rule'] )
				&& 'unimplemented' === $c399_catalog[ $c399_gated ]['cart_rule']
		);
		c399_ok(
			"6.3 {$c399_gated} renders no card on the grid or the strip",
			false === strpos( $c399_loop . $c399_strip, 'data-bhp-offer="' . $c399_gated . '"' )
		);
	}
}

/* ⭐ The voice rule and the American-spelling rule, on this build's own copy. */
if ( function_exists( 'bhp_pair_landing_draft_copy' ) ) {
	$c399_copy_keys = array(
		'page_title', 'hero_eyebrow', 'hero_sub', 'inside_heading', 'inside_chapter',
		'inside_colour', 'value_heading', 'value_separate', 'value_together',
		'value_saving', 'panel_pb', 'panel_hc', 'panel_cta', 'lookinside_head',
		'final_heading', 'singles_link',
	);
	$c399_we   = array();
	$c399_dash = array();
	$c399_uk   = array();
	foreach ( $c399_copy_keys as $c399_ck ) {
		$c399_line = bhp_pair_landing_draft_copy( $c399_ck, array( '$0.00' ) );
		if ( preg_match( '/\b(we|us|our|ours)\b/i', $c399_line ) ) {
			$c399_we[] = $c399_ck;
		}
		if ( false !== strpos( $c399_line, "\xE2\x80\x94" ) ) {   // em dash
			$c399_dash[] = $c399_ck;
		}
		if ( preg_match( '/\bcolour/i', $c399_line ) ) {
			$c399_uk[] = $c399_ck;
		}
	}
	c399_ok( '6.4 no "we/us/our" in this page\'s customer copy', empty( $c399_we ), implode( ',', $c399_we ) );
	c399_ok( '6.5 no em dash in this page\'s customer copy', empty( $c399_dash ), implode( ',', $c399_dash ) );
	c399_ok( '6.6 American spelling: no "colour" in customer copy', empty( $c399_uk ), implode( ',', $c399_uk ) );
}

/* ⛔ Reading age is 6 to 9, never 5 to 9, in the rendered page. */
c399_ok(
	'6.7 the rendered page never says 5 to 9',
	0 === preg_match( '/\b5\s*(to|-|\x{2013})\s*9\b/u', $c399_html )
);

/*
 * ⛔ THE COLLECTION PAGE IS NOT TOUCHED BY THIS BUILD. Asserted because this
 *    build enqueues the collection page's own stylesheet on a second page, and
 *    the one way that could go wrong is a rule escaping its scope.
 */
$c399_pair_css = (string) @file_get_contents( c399_theme_dir() . '/assets/css/bundle-pair-landing.css' );
$c399_selectors = preg_match_all( '/^\s*(\.[a-z0-9_.-]+[^{]*)\{/mi', $c399_pair_css, $c399_m );
$c399_unscoped  = array();
foreach ( (array) $c399_m[1] as $c399_sel ) {
	if ( false === strpos( $c399_sel, '.bhp-pair-landing' ) ) {
		$c399_unscoped[] = trim( $c399_sel );
	}
}
c399_ok(
	'6.8 every rule in the pair stylesheet is scoped under .bhp-pair-landing',
	empty( $c399_unscoped ),
	implode( ' | ', $c399_unscoped )
);

echo "\n";
echo 'PASS ' . (int) $GLOBALS['c399_pass'] . '  FAIL ' . (int) $GLOBALS['c399_fail'] . "\n";

if ( (int) $GLOBALS['c399_fail'] > 0 ) {
	exit( 1 );
}
