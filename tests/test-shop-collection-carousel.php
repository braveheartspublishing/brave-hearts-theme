<?php
/**
 * Brave Hearts — THE SHOP-ARCHIVE COMPLETE COLLECTION COVER STRIP (theme 1.19.234).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-shop-collection-carousel.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE162-LD-SHOP-CAROUSEL`
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1.19.234 enriched `bhp_woocommerce_shop_complete_collection_banner()` with
 * the three real Complete Collection covers, reusing the A/B popup's
 * `bhp_get_popup_ab_covers()` source rather than uploading, compositing or
 * regenerating any media.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE FOUR THINGS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. THE `is_shop()` GUARD STILL HOLDS. The banner is a shop-archive
 *    interception. A regression that leaked it onto a product page would put
 *    a competing CTA directly above an add-to-cart button, and onto a
 *    category archive would double it up. Section 2 asserts ABSENCE on both,
 *    against rendered documents — presence-only tests never catch a leak.
 *
 * 2. THE COVERS ARE REAL ATTACHMENTS. Section 3 resolves every ID the strip
 *    renders back to an `attachment` post that exists and has a file. ⛔ NO
 *    NEW MEDIA is the rail this enforces: if a future edit ever points the
 *    strip at something that is not an existing attachment, this fails.
 *
 * 3. THE COPY AND THE DESTINATION DID NOT MOVE. The heading, the subline and
 *    the CTA label are locked approved prose and the brief permitted no copy
 *    change. Section 4 compares them byte-for-byte and asserts the CTA still
 *    resolves to /complete-collection/.
 *
 * 4. THE BOX IS RESERVED BEFORE THE IMAGE LANDS. Section 5 asserts every
 *    cover carries real `width` and `height` attributes — the mechanism that
 *    makes the banner cost zero layout shift. An `<img>` without them is the
 *    defect, and it is invisible in a screenshot taken after load.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads
 *    rendered documents. It proves presence, absence, cardinality, document
 *    order and attribute values. It CANNOT prove geometry: that the covers
 *    sit left of the heading, that the banner is under 200px taller, that the
 *    product grid is still above the fold, or that the console is clean.
 *    Those need a real browser with `window.innerWidth` asserted, and they
 *    live in the CYCLE162 QA evidence instead. They are not claimed here.
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

echo "\n=== 0. The functions the banner depends on exist ===\n";

foreach ( array(
	'bhp_woocommerce_shop_complete_collection_banner',
	'bhp_get_popup_ab_covers',
) as $scc_fn ) {
	bhp_scc_assert( function_exists( $scc_fn ), "0: {$scc_fn}() exists", $failures );
}

if ( ! function_exists( 'bhp_get_popup_ab_covers' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
	fwrite( STDERR, "Cannot continue: theme is not the 1.19.234 build, or WooCommerce is inactive.\n" );
	exit( 1 );
}

/*
 * The hook registration itself, not just the function. A callback that exists
 * but is no longer hooked renders nothing and would otherwise only surface as
 * a confusing absence failure two sections later.
 */
bhp_scc_assert(
	false !== has_action( 'woocommerce_before_main_content', 'bhp_woocommerce_shop_complete_collection_banner' ),
	'0: the banner is hooked to woocommerce_before_main_content',
	$failures
);

echo "\n=== 1. The banner renders on the shop archive ===\n";

$scc_shop_url  = wc_get_page_permalink( 'shop' );
$scc_shop_html = bhp_scc_get( $scc_shop_url );

bhp_scc_assert( '' !== $scc_shop_html, "1: the shop archive returns HTTP 200 — {$scc_shop_url}", $failures );

if ( '' === $scc_shop_html ) {
	fwrite( STDERR, "Cannot continue: the shop archive did not render.\n" );
	exit( 1 );
}

bhp_scc_assert(
	1 === substr_count( $scc_shop_html, 'woo-complete-collection-banner__inner' ),
	'1: the banner renders exactly once on the shop archive',
	$failures
);
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'woo-complete-collection-banner__covers' ),
	'1: the cover strip renders inside the shop banner',
	$failures
);

/*
 * Cardinality, deliberately exact. Three books, three covers. A regression that
 * rendered one or six would still contain the wrapper and still pass a mere
 * presence check.
 */
$scc_cover_count = substr_count( $scc_shop_html, 'woo-complete-collection-banner__cover' ) - 1; // minus the wrapper class.
bhp_scc_assert(
	3 === $scc_cover_count,
	"1: exactly 3 covers render (got {$scc_cover_count})",
	$failures
);

echo "\n=== 2. THE GUARD — the banner is ABSENT everywhere else ===\n";

/*
 * ⛔ THE SECTION THAT MATTERS MOST. `is_shop()` is the whole containment
 *    boundary; everything else in this suite is about what the banner looks
 *    like once it is correctly placed.
 */
$scc_product = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'objects' ) );
$scc_product = $scc_product ? $scc_product[0] : null;

if ( $scc_product ) {
	$scc_product_url  = get_permalink( $scc_product->get_id() );
	$scc_product_html = bhp_scc_get( $scc_product_url );
	bhp_scc_assert( '' !== $scc_product_html, "2: a product page returns HTTP 200 — {$scc_product_url}", $failures );
	if ( '' !== $scc_product_html ) {
		bhp_scc_assert(
			false === strpos( $scc_product_html, 'woo-complete-collection-banner' ),
			'2: the banner is ABSENT from the single-product page',
			$failures
		);
	}
} else {
	echo "SKIP: 2: no published product found to test the product-page guard\n";
}

$scc_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 1 ) );
if ( ! is_wp_error( $scc_terms ) && ! empty( $scc_terms ) ) {
	$scc_cat_url  = get_term_link( $scc_terms[0] );
	$scc_cat_html = is_wp_error( $scc_cat_url ) ? '' : bhp_scc_get( $scc_cat_url );
	if ( '' !== $scc_cat_html ) {
		bhp_scc_assert(
			false === strpos( $scc_cat_html, 'woo-complete-collection-banner' ),
			'2: the banner is ABSENT from a product-category archive',
			$failures
		);
	} else {
		echo "SKIP: 2: the product-category archive did not render\n";
	}
} else {
	echo "SKIP: 2: no non-empty product_cat term found\n";
}

/*
 * The home page is the third surface worth naming: the banner hooks a
 * WooCommerce action, so a leak there would mean the guard failed in a way the
 * two archive checks above cannot see.
 */
$scc_home_html = bhp_scc_get( home_url( '/' ) );
if ( '' !== $scc_home_html ) {
	bhp_scc_assert(
		false === strpos( $scc_home_html, 'woo-complete-collection-banner' ),
		'2: the banner is ABSENT from the home page',
		$failures
	);
} else {
	echo "SKIP: 2: the home page did not render\n";
}

echo "\n=== 3. NO NEW MEDIA — every cover is a real existing attachment ===\n";

$scc_covers = bhp_get_popup_ab_covers();

bhp_scc_assert( is_array( $scc_covers ), '3: bhp_get_popup_ab_covers() returns an array', $failures );
bhp_scc_assert( 3 === count( $scc_covers ), '3: the source resolves 3 covers (got ' . count( $scc_covers ) . ')', $failures );

foreach ( $scc_covers as $scc_i => $scc_cover ) {
	$scc_id  = isset( $scc_cover['id'] ) ? (int) $scc_cover['id'] : 0;
	$scc_pos = $scc_i + 1;

	bhp_scc_assert( $scc_id > 0, "3: cover {$scc_pos} carries a non-zero attachment ID", $failures );
	bhp_scc_assert(
		'attachment' === get_post_type( $scc_id ),
		"3: cover {$scc_pos} (ID {$scc_id}) is a real attachment post",
		$failures
	);
	bhp_scc_assert(
		'' !== (string) get_attached_file( $scc_id ),
		"3: cover {$scc_pos} (ID {$scc_id}) has a file on disk",
		$failures
	);
	/*
	 * The rendered document must actually serve THIS attachment's file, not
	 * some other image — otherwise the source helper and the output could
	 * drift apart and every other check here would still pass.
	 *
	 * ⛔ MATCHED ON THE FILE URL, NOT ON A `wp-image-<id>` CLASS. The first
	 *    version of this assertion looked for `wp-image-13` and failed against
	 *    correct markup. `wp-image-<id>` is written by the BLOCK EDITOR, not
	 *    by `wp_get_attachment_image()`; and passing a `class` attribute to
	 *    that function REPLACES its default `attachment-medium size-medium`
	 *    classes rather than appending to them, so no ID ever appears in the
	 *    tag. The attachment's own resized filename is the durable link
	 *    between the ID and the rendered pixel, so that is what is asserted.
	 */
	$scc_src = wp_get_attachment_image_url( $scc_id, 'medium' );
	bhp_scc_assert(
		is_string( $scc_src ) && '' !== $scc_src && false !== strpos( $scc_shop_html, basename( $scc_src ) ),
		"3: cover {$scc_pos} (ID {$scc_id}) is the file served in the rendered shop archive",
		$failures
	);
}

echo "\n=== 4. The copy and the destination are UNCHANGED ===\n";

/*
 * Byte-for-byte against the 1.19.233 strings. The brief permitted no copy
 * change and these three are the entire customer-visible message.
 */
foreach ( array(
	'Looking for the complete series?'          => 'the heading',
	'Get all three adventures in paperback or hardcover.' => 'the subline',
	'View the Complete Collection'              => 'the CTA label',
) as $scc_text => $scc_what ) {
	bhp_scc_assert(
		false !== strpos( $scc_shop_html, esc_html( $scc_text ) ),
		"4: {$scc_what} is verbatim — \"{$scc_text}\"",
		$failures
	);
}

$scc_expected_href = esc_url( home_url( '/complete-collection/' ) );
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'href="' . $scc_expected_href . '"' ),
	"4: the CTA href is unchanged — {$scc_expected_href}",
	$failures
);

/* Document order: the covers precede the heading, which is the requested
   "covers visual left/top" arrangement expressed the one way a rendered
   document can prove it. */
$scc_p_covers  = strpos( $scc_shop_html, 'woo-complete-collection-banner__covers' );
$scc_p_heading = strpos( $scc_shop_html, 'Looking for the complete series?' );
bhp_scc_assert(
	false !== $scc_p_covers && false !== $scc_p_heading && $scc_p_covers < $scc_p_heading,
	'4: the covers precede the heading in document order',
	$failures
);

echo "\n=== 5. ZERO LAYOUT SHIFT — every cover reserves its box ===\n";

/*
 * ⛔ THE CHECK A SCREENSHOT CANNOT MAKE. By the time anything is screenshotted
 *    the images have loaded and the shift has already happened and finished.
 *    The only durable evidence is the attributes in the markup.
 */
if ( preg_match_all( '/<img[^>]*woo-complete-collection-banner__cover[^>]*>/i', $scc_shop_html, $scc_imgs ) ) {
	bhp_scc_assert(
		3 === count( $scc_imgs[0] ),
		'5: 3 cover <img> elements found (got ' . count( $scc_imgs[0] ) . ')',
		$failures
	);
	foreach ( $scc_imgs[0] as $scc_j => $scc_tag ) {
		$scc_pos = $scc_j + 1;
		bhp_scc_assert(
			(bool) preg_match( '/\swidth="\d+"/', $scc_tag ) && (bool) preg_match( '/\sheight="\d+"/', $scc_tag ),
			"5: cover {$scc_pos} carries real width and height attributes",
			$failures
		);
		/* Decorative, matching the A/B popup precedent — the heading and CTA
		   already carry the message and titles here would be noise. */
		bhp_scc_assert(
			(bool) preg_match( '/\salt=""/', $scc_tag ),
			"5: cover {$scc_pos} is decorative (alt=\"\")",
			$failures
		);
	}
	bhp_scc_assert(
		false !== strpos( $scc_shop_html, 'woo-complete-collection-banner__covers" aria-hidden="true"' ),
		'5: the cover wrapper is hidden from assistive technology',
		$failures
	);
} else {
	bhp_scc_assert( false, '5: no cover <img> elements found in the rendered shop archive', $failures );
}

/*
 * Eager, not lazy — the deliberate call recorded in functions.php. Asserted so
 * that a well-meaning future "add lazy loading everywhere" pass has to read the
 * reasoning before it changes this.
 */
bhp_scc_assert(
	false === strpos( $scc_shop_html, 'woo-complete-collection-banner__cover" loading="lazy"' )
	&& false === strpos( $scc_shop_html, 'loading="lazy" class="woo-complete-collection-banner__cover"' ),
	'5: the covers are not lazy-loaded (they are above the fold, by design)',
	$failures
);

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
