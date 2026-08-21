<?php
/**
 * Brave Hearts — THE COLLECTION CAROUSEL IS OFF /shop/ (theme 1.19.284).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-shop-collection-carousel.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ THIS SUITE WAS INVERTED AT 1.19.284 — CARRIER ITEM 207.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, carrier item 207, 2026-08-21. ⚠️ RELAYED through
 *    `chief-of-staff` (Gandalf 9) in the build brief — ⛔ NOT witnessed
 *    first-hand by the agent that rewrote this file. Recorded as relayed, per
 *    Standing Rules §9.2 rule 2, so the basis of the claim travels with it.
 *    His instruction: the collection carousel comes OFF the shop page.
 *    ⛔ THAT PAGE ONLY.
 *
 * ⛔⛔ WHAT THIS FILE USED TO BE, AND WHY THAT MATTERS. From 1.19.235 until
 *    1.19.283 this suite asserted the OPPOSITE of what it now asserts: that
 *    `bhp_woocommerce_shop_complete_collection_banner()` existed, was hooked,
 *    and rendered a full carousel on /shop/. It was a correct suite for a
 *    correct build, and it PASSED. It began failing the moment item 207
 *    landed, which is the suite doing its job — a removal that no test
 *    noticed is a removal nobody can prove happened.
 *
 * ⭐ THE SUPERSEDED SECTIONS ARE NAMED RATHER THAN SILENTLY DROPPED, because a
 *    future session that finds only the new file will not know a carousel was
 *    ever here and may "restore" it as a missing feature:
 *      · old §0  — asserted the banner function and predicate EXIST.
 *                  ⛔ Now inverted: §1 asserts they are GONE.
 *      · old §1  — asserted the banner rendered exactly once on /shop/.
 *                  ⛔ Now inverted: §2 asserts zero occurrences.
 *      · old §3  — asserted `book-media.css` / `book-media.js` were enqueued
 *                  on /shop/. ⛔ Now inverted: §2 asserts they are NOT, which
 *                  is the whole point of removing the `is_shop()` enqueue
 *                  branch rather than only the render.
 *      · old §4–7 — asserted the carousel's structure, media parity, copy,
 *                  destination and LCP behaviour ON /shop/. ⭐ Those checks are
 *                  NOT deleted, they are RELOCATED: §3 makes the same family of
 *                  assertions against /complete-collection/, which is where the
 *                  component still lives and still has to work.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE FOUR THINGS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. THE REMOVAL IS COMPLETE, NOT COSMETIC (§1, §2). A render that is merely
 *    hidden by CSS still ships the markup, still enqueues the assets and still
 *    costs the request. §1 asserts the FUNCTIONS are gone and the hook is
 *    unregistered; §2 asserts the rendered /shop/ document contains no banner
 *    markup, no carousel markup and no gallery assets.
 *
 * 2. ⭐⭐ THE CONTAINMENT HELD (§3). This is the half of item 207 that a
 *    careless implementation breaks. "OFF the shop page" is not "off the
 *    site". /complete-collection/ must still render the real carousel with its
 *    real media, and §3 asserts that against a rendered document rather than
 *    inferring it from the fact that nobody edited that code path.
 *
 * 3. ⛔ NO CTA WAS ORPHANED (§4). The removed banner's only outbound control
 *    was "View the Complete Collection" → /complete-collection/. §4 asserts
 *    that destination is STILL reachable from /shop/ — via the Complete
 *    Collection product card (item 206) — so the removal shortened the path
 *    rather than severing it. This is the failure shape of item 118 and the
 *    reason §1.11 of the protected-elements suite exists.
 *
 * 4. THE GUARD STILL HOLDS THE OTHER WAY (§5). The shop archive shares
 *    `archive-product.php` with every `product_cat` and `product_tag` archive.
 *    The carousel must be absent from those too — it always was, and a
 *    "restore it on shop" patch keyed on the template filename would leak it
 *    across all of them. Absence is asserted where it was already true, so a
 *    future regression has somewhere to fail.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads
 *    rendered documents. It proves presence, absence, cardinality and
 *    attribute values. It CANNOT prove geometry: that the shop grid now sits
 *    higher on the page, that the two bundle cards look right at 390px, or
 *    that the console is clean. Those need a real browser with
 *    `window.innerWidth` asserted, and they are NOT claimed here.
 *    `tests/test-bundle-cards-206-207.php` covers the item-206 card markup;
 *    the browser evidence is in the QA folder for this release.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_scc_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/** Fetch a rendered document. Returns '' on any transport failure. */
function bhp_scc_get( $url ) {
	$resp = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $resp );
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	fwrite( STDERR, "Cannot continue: WooCommerce is inactive.\n" );
	exit( 1 );
}

echo "\n=== §1 · THE REMOVAL IS REAL IN CODE, NOT JUST IN CSS ===\n";

/*
 * ⛔ THE FUNCTIONS THEMSELVES. A build that deleted the `add_action` but left
 *    the function behind would pass every document assertion below while
 *    leaving a live, callable renderer one line away from returning. Item 207
 *    removed the renderer, its predicate and the hook together, and all three
 *    are asserted separately so a partial revert names itself.
 */
bhp_scc_assert(
	! function_exists( 'bhp_woocommerce_shop_complete_collection_banner' ),
	'1.1 ⛔ bhp_woocommerce_shop_complete_collection_banner() is GONE (item 207)',
	$failures
);
bhp_scc_assert(
	! function_exists( 'bhp_cx_shop_banner_gallery_media' ),
	'1.2 ⛔ bhp_cx_shop_banner_gallery_media() is GONE — its only two callers were this feature',
	$failures
);
bhp_scc_assert(
	false === has_action( 'woocommerce_before_main_content', 'bhp_woocommerce_shop_complete_collection_banner' ),
	'1.3 ⛔ the banner is NOT hooked to woocommerce_before_main_content',
	$failures
);

/*
 * ⭐⭐ AND THE CONTAINMENT, ASSERTED AT THE CODE LEVEL BEFORE ANY DOCUMENT IS
 *    FETCHED. Item 207 removed ONE placement. The component, its media
 *    resolver and the collection page's own renderer must all still exist —
 *    if any of these is missing, §3 below would fail for the WRONG reason and
 *    the report would blame the wrong thing.
 */
foreach ( array(
	'bhp_book_media'                => 'the media registry',
	'bhp_book_enqueue_media_assets' => 'the gallery asset enqueue',
	'bhp_cx_collection_gallery_map' => 'the collection placement map',
) as $scc_fn => $scc_what ) {
	bhp_scc_assert(
		function_exists( $scc_fn ),
		"1.4 ⭐ {$scc_what} SURVIVES — {$scc_fn}() still exists",
		$failures
	);
}
bhp_scc_assert(
	'' !== locate_template( 'template-parts/commerce/look-inside.php' ),
	'1.5 ⭐ the carousel component template still exists (removal was a placement, not a deletion)',
	$failures
);
bhp_scc_assert(
	false !== has_action( 'wp_enqueue_scripts', 'bhp_book_enqueue_media_assets' ),
	'1.6 ⭐ the media-asset enqueue is still hooked (other surfaces need it)',
	$failures
);

echo "\n=== §2 · /shop/ CARRIES NO CAROUSEL AND NO GALLERY ASSETS ===\n";

$scc_shop_url  = wc_get_page_permalink( 'shop' );
$scc_shop_html = bhp_scc_get( $scc_shop_url );

bhp_scc_assert( '' !== $scc_shop_html, "2.1 the shop archive returns HTTP 200 — {$scc_shop_url}", $failures );

if ( '' === $scc_shop_html ) {
	fwrite( STDERR, "Cannot continue: the shop archive did not render.\n" );
	exit( 1 );
}

/*
 * ⛔ THE BANNER MARKUP, AT ZERO. Matched on the bare block name so that a
 *    partial restore of ANY element of it (inner, media, --gallery modifier)
 *    trips this rather than slipping through a more specific needle.
 */
bhp_scc_assert(
	0 === substr_count( $scc_shop_html, 'woo-complete-collection-banner' ),
	'2.2 ⛔ ZERO occurrences of the banner block on /shop/ (found ' . substr_count( $scc_shop_html, 'woo-complete-collection-banner' ) . ')',
	$failures
);
/*
 * ⛔ AND THE COMPONENT ITSELF, INDEPENDENTLY. The banner was the only thing
 *    that put the collection gallery on /shop/, but asserting the wrapper
 *    alone would miss a future placement that dropped the component straight
 *    into the archive without the banner chrome around it.
 */
bhp_scc_assert(
	0 === substr_count( $scc_shop_html, 'id="bhp-look-inside-complete_collection"' ),
	'2.3 ⛔ the collection gallery component does not render on /shop/ by ANY route',
	$failures
);
foreach ( array(
	'data-bhp-gallery-stage'        => 'the carousel stage',
	'data-bhp-gallery-thumbs'       => 'the thumbnail rail',
	'data-bhp-gallery-lightbox'     => 'the lightbox',
	'bhp-media-gallery--collection' => 'the collection gallery chrome',
) as $scc_needle => $scc_what ) {
	bhp_scc_assert(
		false === strpos( $scc_shop_html, $scc_needle ),
		"2.4 ⛔ {$scc_what} is ABSENT from /shop/",
		$failures
	);
}

/*
 * ⭐⭐ THE ASSERTION THAT PROVES THE ENQUEUE BRANCH CAME OUT TOO, and not just
 *    the render. Leaving the `is_shop()` branch of `bhp_book_enqueue_media_
 *    assets()` in place would ship two files to every shop visitor for a
 *    component that no longer exists on the page — invisible in a screenshot,
 *    invisible in a DOM diff, and a real cost on a phone.
 */
foreach ( array(
	'assets/css/book-media' => 'the gallery CSS (book-media.css)',
	'assets/js/book-media'  => 'the gallery JS (book-media.js)',
) as $scc_path => $scc_what ) {
	bhp_scc_assert(
		false === strpos( $scc_shop_html, $scc_path ),
		"2.5 ⛔ {$scc_what} is NOT enqueued on /shop/ (the is_shop() branch was removed)",
		$failures
	);
}

/*
 * ⛔ THE 1.19.234 COVER FAN STAYS GONE TOO. It was superseded at 1.19.235 and
 *    removed; a revert that reached for "the old shop banner" could bring back
 *    either one. Both are asserted absent so neither can return quietly.
 */
bhp_scc_assert(
	false === strpos( $scc_shop_html, 'woo-complete-collection-banner__covers' ),
	'2.6 ⛔ the superseded 1.19.234 cover fan is ABSENT too',
	$failures
);

echo "\n=== §3 · ⭐⭐ CONTAINMENT — /complete-collection/ STILL HAS ITS CAROUSEL ===\n";

/*
 * ⭐⭐ THE HALF OF ITEM 207 THAT A CARELESS IMPLEMENTATION BREAKS. "OFF the
 *    shop page" is not "off the site". The removal touched one placement, one
 *    predicate and one enqueue branch; `bhp_cx_collection_gallery_map()`,
 *    `bhp_cx_render_collection_gallery()` and the collection page's own hero
 *    were not touched at all. ⛔ THAT IS ASSERTED AGAINST A RENDERED DOCUMENT
 *    HERE, not inferred from the fact that nobody edited the file.
 */
$scc_cc_url  = home_url( '/complete-collection/' );
$scc_cc_html = bhp_scc_get( $scc_cc_url );

bhp_scc_assert( '' !== $scc_cc_html, "3.1 /complete-collection/ returns HTTP 200 — {$scc_cc_url}", $failures );

if ( '' !== $scc_cc_html ) {
	bhp_scc_assert(
		1 === substr_count( $scc_cc_html, 'id="bhp-look-inside-complete_collection"' ),
		'3.2 ⭐ the collection gallery renders EXACTLY ONCE on /complete-collection/ (found ' . substr_count( $scc_cc_html, 'id="bhp-look-inside-complete_collection"' ) . ')',
		$failures
	);
	foreach ( array(
		'data-bhp-gallery-stage'    => 'the carousel stage',
		'data-bhp-gallery-thumbs'   => 'the thumbnail rail',
		'data-bhp-gallery-prev'     => 'the previous arrow',
		'data-bhp-gallery-next'     => 'the next arrow',
		'data-bhp-gallery-counter'  => 'the "N / total" counter',
		'data-bhp-gallery-lightbox' => 'the lightbox',
	) as $scc_needle => $scc_what ) {
		bhp_scc_assert(
			false !== strpos( $scc_cc_html, $scc_needle ),
			"3.3 ⭐ {$scc_what} STILL renders on /complete-collection/",
			$failures
		);
	}

	/*
	 * ⭐ AND ITS ASSETS STILL LOAD THERE. Without `book-media.css` / `.js` the
	 *    component degrades to an unstyled vertical stack of every slide with
	 *    dead buttons — which still contains every needle asserted above. This
	 *    is the check that separates "renders" from "works", and it is exactly
	 *    the failure the enqueue removal could plausibly have caused if the
	 *    branch had been over-removed.
	 */
	foreach ( array(
		'assets/css/book-media' => 'the gallery CSS',
		'assets/js/book-media'  => 'the gallery JS',
	) as $scc_path => $scc_what ) {
		bhp_scc_assert(
			false !== strpos( $scc_cc_html, $scc_path ),
			"3.4 ⭐ {$scc_what} IS still enqueued on /complete-collection/",
			$failures
		);
	}

	/*
	 * ⛔ NO NEW MEDIA, AND THE FULL SET STILL ARRIVES. The counter's
	 *    denominator is the one number that proves the whole registry reached
	 *    the page; a subset would still render every structural element above.
	 */
	if ( function_exists( 'bhp_book_media' ) ) {
		$scc_media = bhp_book_media( 'complete_collection' );
		bhp_scc_assert(
			! empty( $scc_media['has_any'] ) && ! empty( $scc_media['items'] ),
			'3.5 the complete_collection media set still resolves on this environment',
			$failures
		);
		if ( ! empty( $scc_media['items'] ) ) {
			$scc_total = count( $scc_media['items'] );
			bhp_scc_assert(
				$scc_total === substr_count( $scc_cc_html, 'data-bhp-slide="' ),
				"3.6 ⭐ every registry item still renders as a slide (expected {$scc_total}, got " . substr_count( $scc_cc_html, 'data-bhp-slide="' ) . ')',
				$failures
			);
		}
	}
}

echo "\n=== §4 · ⛔ NO CTA WAS ORPHANED — /complete-collection/ IS STILL REACHED FROM /shop/ ===\n";

/*
 * ⛔⛔ THE FAILURE SHAPE OF ITEM 118, AND THE REASON THIS SECTION IS NOT
 *    OPTIONAL. Removing a component that carried the ONLY link to a
 *    destination silently deletes a purchase path. The banner's outbound
 *    control was "View the Complete Collection" → /complete-collection/.
 *
 * ⭐ ITEM 206 PUT THE SAME DESTINATION ON THE SAME PAGE, as a product-style
 *    card carrying the three-paperback composite, the $31.99 price and "GET
 *    THE COMPLETE COLLECTION". So the path is SHORTER, not missing — and that
 *    is asserted here rather than assumed, because the two items shipped in
 *    one release and either could regress without the other noticing.
 */
$scc_cc_path = wp_parse_url( $scc_cc_url, PHP_URL_PATH );
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'href="' . esc_url( $scc_cc_url ) . '"' )
		|| ( is_string( $scc_cc_path ) && false !== strpos( $scc_shop_html, $scc_cc_path ) ),
	"4.1 ⭐ /shop/ still reaches {$scc_cc_url}",
	$failures
);
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'bhp-shop-collection-item' ),
	'4.2 ⭐ …and it reaches it via the Complete Collection CARD in the grid (item 206)',
	$failures
);

echo "\n=== §5 · THE OTHER ARCHIVES WERE ALWAYS CLEAN AND STAY CLEAN ===\n";

/*
 * The shop archive shares `archive-product.php` with every product_cat and
 * product_tag archive. The carousel was never on those — `is_shop()` was the
 * only thing separating them. Absence is asserted where it was already true so
 * that a future "put it back on shop" patch keyed on the template filename has
 * somewhere to fail loudly instead of leaking across every category page.
 */
$scc_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 1 ) );
if ( ! is_wp_error( $scc_terms ) && ! empty( $scc_terms ) ) {
	$scc_cat_url  = get_term_link( $scc_terms[0] );
	$scc_cat_html = is_wp_error( $scc_cat_url ) ? '' : bhp_scc_get( $scc_cat_url );
	if ( '' !== $scc_cat_html ) {
		bhp_scc_assert(
			false === strpos( $scc_cat_html, 'woo-complete-collection-banner' ),
			'5.1 the banner is ABSENT from a product-category archive',
			$failures
		);
		bhp_scc_assert(
			false === strpos( $scc_cat_html, 'assets/css/book-media' ),
			'5.2 the gallery CSS is NOT enqueued on a product-category archive',
			$failures
		);
	} else {
		echo "SKIP: 5: the product-category archive did not render\n";
	}
} else {
	echo "SKIP: 5: no non-empty product_cat term found\n";
}

$scc_product = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'objects' ) );
$scc_product = $scc_product ? $scc_product[0] : null;
if ( $scc_product ) {
	$scc_product_html = bhp_scc_get( get_permalink( $scc_product->get_id() ) );
	if ( '' !== $scc_product_html ) {
		bhp_scc_assert(
			false === strpos( $scc_product_html, 'woo-complete-collection-banner' ),
			'5.3 the banner is ABSENT from a single-product page',
			$failures
		);
	} else {
		echo "SKIP: 5: the product page did not render\n";
	}
} else {
	echo "SKIP: 5: no published product found\n";
}

$scc_home_html = bhp_scc_get( home_url( '/' ) );
if ( '' !== $scc_home_html ) {
	bhp_scc_assert(
		false === strpos( $scc_home_html, 'woo-complete-collection-banner' ),
		'5.4 the banner is ABSENT from the home page',
		$failures
	);
} else {
	echo "SKIP: 5: the home page did not render\n";
}

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $scc_f ) {
	echo " - {$scc_f}\n";
}
exit( 1 );
