<?php
/**
 * Brave Hearts — THE SHOP-ARCHIVE COMPLETE COLLECTION CAROUSEL (theme 1.19.235).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-shop-collection-carousel.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE162-LD-SHOP-CAROUSEL-V2`
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1.19.235 replaced 1.19.234's static three-cover fan with the REAL
 * /complete-collection/ carousel — `template-parts/commerce/look-inside.php`
 * driven by `bhp_book_media('complete_collection')`, the identical media call
 * the collection page's own hero makes.
 *
 * ⛔ THE 1.19.234 SUITE IS SUPERSEDED, NOT EXTENDED. Its sections 3 and 5
 *    asserted `bhp_get_popup_ab_covers()`, a cover count of exactly 3, and
 *    `alt=""` on every image. All three are now WRONG BY DESIGN: the source is
 *    the Collection registry, the count is whatever that registry resolves to
 *    on this environment, and the slides carry real alt text because they are
 *    content, not decoration. Keeping them would have failed a correct build.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE SIX THINGS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. THE `is_shop()` GUARD STILL HOLDS, and it matters MORE than it did for
 *    the fan. The shop archive resolves to WooCommerce's own
 *    `archive-product.php`, which is ALSO every `product_cat` and
 *    `product_tag` archive's template — so a placement keyed on the template
 *    filename would have leaked the carousel onto every category archive.
 *    Section 2 asserts ABSENCE against rendered documents, because
 *    presence-only tests never catch a leak.
 *
 * 2. ⭐ THE ASSETS ACTUALLY LOAD. This is the failure the brief named: without
 *    `book-media.css` and `book-media.js` the component degrades to a broken
 *    vertical list of every slide, which still contains all the markup this
 *    suite would otherwise check. Section 3 asserts BOTH files appear in the
 *    rendered shop archive AND that each carries a `ver=` string.
 *
 * 3. THE CAROUSEL IS THE REAL COMPONENT, RENDERED ONCE. Section 4 asserts the
 *    stage, the thumbnail rail, the arrows, the "N / total" counter, the
 *    "Click to enlarge" affordance and the lightbox are all present, and that
 *    the component's DOM id appears EXACTLY ONCE — two instances would share
 *    an id, share an `aria-labelledby` target and share a lightbox while
 *    looking perfectly correct on screen.
 *
 * 4. THE MEDIA IS THE COLLECTION PAGE'S OWN. Section 5 resolves
 *    `bhp_book_media('complete_collection')` and asserts the shop archive
 *    serves the same item count and the same first slide as
 *    /complete-collection/ does. ⛔ NO NEW MEDIA is the rail this enforces.
 *
 * 5. THE COPY AND THE DESTINATION DID NOT MOVE. The heading, the subline and
 *    the CTA label are locked approved prose and the brief permitted no copy
 *    change. Section 6 compares them byte-for-byte and asserts the CTA still
 *    resolves to /complete-collection/.
 *
 * 6. THE BOX IS RESERVED AND THE LCP SLIDE IS EAGER. Section 7 asserts every
 *    stage image carries real `width`/`height` (zero layout shift) and that
 *    slide 0 is NOT lazy — the archive hero above the banner is text-only, so
 *    slide 0 is the shop archive's largest contentful paint and lazy-loading
 *    it would cost exactly the metric the placement is meant to win.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads
 *    rendered documents. It proves presence, absence, cardinality, document
 *    order and attribute values. It CANNOT prove geometry: that the stage is
 *    ~340px tall, that the first row of product cards is still above the fold,
 *    that the arrows and thumbnails actually switch slides, that the lightbox
 *    opens, or that the console is clean. Those need a real browser with
 *    `window.innerWidth` asserted, and they are NOT claimed here.
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
	'bhp_cx_shop_banner_gallery_media',
	'bhp_book_media',
	'bhp_book_enqueue_media_assets',
) as $scc_fn ) {
	bhp_scc_assert( function_exists( $scc_fn ), "0: {$scc_fn}() exists", $failures );
}

if ( ! function_exists( 'bhp_cx_shop_banner_gallery_media' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
	fwrite( STDERR, "Cannot continue: theme is not the 1.19.235 build, or WooCommerce is inactive.\n" );
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
bhp_scc_assert(
	false !== has_action( 'wp_enqueue_scripts', 'bhp_book_enqueue_media_assets' ),
	'0: the media-asset enqueue is hooked to wp_enqueue_scripts',
	$failures
);

/*
 * The component template has to be locatable, because the banner's render
 * branch is `locate_template()`-gated: a missing template degrades silently to
 * copy-only markup, which every other check here would read as "no carousel"
 * without saying why.
 */
bhp_scc_assert(
	'' !== locate_template( 'template-parts/commerce/look-inside.php' ),
	'0: template-parts/commerce/look-inside.php is locatable',
	$failures
);

echo "\n=== 1. The banner and the carousel render on the shop archive ===\n";

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
	1 === substr_count( $scc_shop_html, 'woo-complete-collection-banner__media' ),
	'1: the carousel host renders exactly once inside the banner',
	$failures
);
/*
 * The modifier class is what the CSS layout hangs off, and it is only emitted
 * when the media AND the template both resolved. Its absence is the signature
 * of a silent fail-closed fallback to copy-only markup.
 */
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'woo-complete-collection-banner--gallery' ),
	'1: the banner carries the --gallery modifier (media resolved, not failed closed)',
	$failures
);
/*
 * ⛔ THE FAN IS GONE. Asserted explicitly rather than left implied: a partial
 *    deploy that shipped 1.19.235's PHP against 1.19.234's CSS, or a revert of
 *    one file, would render both and look merely "busy" rather than broken.
 */
bhp_scc_assert(
	false === strpos( $scc_shop_html, 'woo-complete-collection-banner__covers' ),
	'1: the superseded 1.19.234 cover fan is ABSENT',
	$failures
);

echo "\n=== 2. THE GUARD — the banner is ABSENT everywhere else ===\n";

/*
 * ⛔ THE SECTION THAT MATTERS MOST, AND MORE SO AT 1.19.235 THAN AT 1.19.234.
 *    /shop/ and every product-category archive resolve to the SAME WooCommerce
 *    template file. `is_shop()` is the only thing separating them.
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
		/*
		 * And the ASSETS must be absent too. The enqueue gate and the render
		 * gate are the same predicate by construction; this is the assertion
		 * that proves the construction actually holds on a rendered document.
		 */
		bhp_scc_assert(
			false === strpos( $scc_cat_html, 'assets/css/book-media' ),
			'2: the gallery CSS is NOT enqueued on a product-category archive',
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

echo "\n=== 3. ⭐ THE ASSETS LOAD — otherwise the carousel is a broken list ===\n";

/*
 * ⛔ THE CHECK THE BRIEF ASKED FOR BY NAME. Every markup assertion in section 4
 *    would still pass on a page whose CSS and JS never loaded — the DOM would
 *    be identical and the component would render as an unstyled vertical stack
 *    of all ten slides with dead buttons. Only the enqueue proves otherwise.
 *
 * Matched on the PATH, not on a handle: WordPress prints the handle only in
 * the `id="bhp-book-media-css"` attribute for styles, and not at all for a
 * footer script's tag in some configurations. The file path is what is always
 * emitted and is what the browser actually fetches.
 */
foreach ( array(
	'assets/css/book-media' => 'the gallery CSS (book-media.css)',
	'assets/js/book-media'  => 'the gallery JS (book-media.js)',
) as $scc_path => $scc_what ) {
	$scc_found = ( false !== strpos( $scc_shop_html, $scc_path ) );
	bhp_scc_assert( $scc_found, "3: {$scc_what} is enqueued on the shop archive", $failures );

	/*
	 * The `ver=` string is not cosmetic here: it is the theme version, and it
	 * is what makes a deploy actually reach a returning visitor's browser
	 * rather than being served from cache. An asset without one is a
	 * cache-invalidation defect that only shows up days later.
	 *
	 * ⛔ `(\.min)?` IS LOAD-BEARING AND WAS NOT DEFENSIVE PADDING — the first
	 *    version of this assertion omitted it and FAILED against a correct
	 *    1.19.235 build. Observed on staging: the CSS is served from the
	 *    comment-stripped build artefact as `book-media.min.css?ver=1.19.235`
	 *    (from theme 1.19.201, `bhp_minified_style_src()`), while the JS has no
	 *    minified counterpart and is served as `book-media.js?ver=1.19.235`.
	 *    The two assets legitimately differ, so the pattern has to allow both.
	 */
	if ( $scc_found && preg_match(
		'#' . preg_quote( $scc_path, '#' ) . '(\.min)?\.(css|js)\?ver=([^"\'&]+)#',
		$scc_shop_html,
		$scc_m
	) ) {
		bhp_scc_assert(
			'' !== $scc_m[3],
			"3: {$scc_what} carries a ver= string ({$scc_m[3]}" . ( '' !== $scc_m[1] ? ', minified' : '' ) . ')',
			$failures
		);
	} else {
		bhp_scc_assert( false, "3: {$scc_what} carries a ver= string", $failures );
	}
}

/*
 * The version the assets are stamped with must be the theme's own, or a stale
 * build is live and every other assertion here is describing the wrong code.
 */
$scc_theme_ver = wp_get_theme()->get( 'Version' );
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'assets/css/book-media' ) &&
	false !== strpos( $scc_shop_html, 'ver=' . $scc_theme_ver ),
	"3: assets are stamped with the active theme version ({$scc_theme_ver})",
	$failures
);

echo "\n=== 4. It is the REAL carousel component, rendered ONCE ===\n";

/*
 * The DOM id is derived from the media key, so two instances would collide on
 * it, on their `aria-labelledby` target and on their lightbox — all of which
 * look perfectly correct in a screenshot.
 */
bhp_scc_assert(
	1 === substr_count( $scc_shop_html, 'id="bhp-look-inside-complete_collection"' ),
	'4: exactly one gallery instance (the component DOM id appears once)',
	$failures
);

foreach ( array(
	'data-bhp-gallery-stage'      => 'the shared stage',
	'data-bhp-gallery-thumbs'     => 'the thumbnail rail',
	'data-bhp-gallery-prev'       => 'the previous arrow',
	'data-bhp-gallery-next'       => 'the next arrow',
	'data-bhp-gallery-counter'    => 'the "N / total" counter',
	'data-bhp-gallery-inspect'    => 'the click-to-enlarge affordance',
	'data-bhp-gallery-lightbox'   => 'the lightbox',
	'bhp-media-gallery--collection' => 'the collection chrome',
) as $scc_needle => $scc_what ) {
	bhp_scc_assert(
		false !== strpos( $scc_shop_html, $scc_needle ),
		"4: {$scc_what} renders on the shop archive",
		$failures
	);
}

bhp_scc_assert(
	false !== strpos( $scc_shop_html, esc_html__( 'Click to enlarge', 'brave-hearts' ) ),
	'4: the "Click to enlarge" hint renders',
	$failures
);

/*
 * NOT hero mode. Hero mode deletes the heading element and labels the region
 * with `aria-label` instead; here the region must keep its own heading so the
 * banner's <h2> and the gallery's title are two distinct, correctly-nested
 * things. The heading is present AND visually hidden.
 */
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'id="bhp-look-inside-complete_collection-title"' ),
	'4: the gallery keeps its own heading element (not hero mode)',
	$failures
);
bhp_scc_assert(
	(bool) preg_match(
		'#<h3[^>]*class="[^"]*bhp-look-inside__title[^"]*screen-reader-text[^"]*"#',
		$scc_shop_html
	),
	'4: the gallery heading is an <h3> and is visually hidden',
	$failures
);
bhp_scc_assert(
	false === strpos( $scc_shop_html, 'bhp-media-gallery--hero' ),
	'4: hero mode is NOT used on the shop archive',
	$failures
);

echo "\n=== 5. NO NEW MEDIA — the set is the Collection page's own ===\n";

$scc_media = bhp_cx_shop_banner_gallery_media();

/*
 * Called from WP-CLI, `is_shop()` is false, so the predicate correctly returns
 * null here. That is not a failure — it is the guard working. The registry is
 * read directly instead, which is the same call the predicate makes.
 */
if ( null === $scc_media ) {
	echo "NOTE: 5: bhp_cx_shop_banner_gallery_media() returns null outside a shop request (correct); reading the registry directly.\n";
	$scc_media = bhp_book_media( 'complete_collection' );
}

bhp_scc_assert(
	! empty( $scc_media['has_any'] ) && ! empty( $scc_media['items'] ),
	'5: the complete_collection media set resolves on this environment',
	$failures
);

if ( ! empty( $scc_media['items'] ) ) {
	$scc_total = count( $scc_media['items'] );

	/*
	 * The counter's denominator is the one number that proves the whole set
	 * reached the page. A subset would still render every structural element
	 * checked in section 4.
	 */
	bhp_scc_assert(
		false !== strpos( $scc_shop_html, '</span> / ' . $scc_total ),
		"5: the counter's denominator is the full registry count ({$scc_total})",
		$failures
	);
	bhp_scc_assert(
		$scc_total === substr_count( $scc_shop_html, 'data-bhp-slide="' ),
		"5: every registry item renders as a slide (expected {$scc_total}, got " . substr_count( $scc_shop_html, 'data-bhp-slide="' ) . ')',
		$failures
	);

	/*
	 * ⛔ NO NEW MEDIA. Every image slide must resolve back to an attachment
	 *    that exists and has a file, and its rendered file must actually be
	 *    served in the document. Matched on the resized filename, not on a
	 *    `wp-image-<id>` class — that class is written by the block editor, not
	 *    by `wp_get_attachment_image()` (the 1.19.234 suite learned this the
	 *    hard way and the note is kept so it is not re-derived).
	 */
	$scc_checked = 0;
	foreach ( $scc_media['items'] as $scc_i => $scc_item ) {
		$scc_id = 0;
		if ( isset( $scc_item['type'] ) && 'video' === $scc_item['type'] ) {
			$scc_id = isset( $scc_item['poster_id'] ) ? (int) $scc_item['poster_id'] : 0;
		} elseif ( ! empty( $scc_item['id'] ) ) {
			$scc_id = (int) $scc_item['id'];
		}
		if ( $scc_id <= 0 ) {
			continue;
		}
		$scc_checked++;
		bhp_scc_assert(
			'attachment' === get_post_type( $scc_id ),
			'5: slide ' . ( $scc_i + 1 ) . " (ID {$scc_id}) is a real attachment post",
			$failures
		);
		bhp_scc_assert(
			'' !== (string) get_attached_file( $scc_id ),
			'5: slide ' . ( $scc_i + 1 ) . " (ID {$scc_id}) has a file on disk",
			$failures
		);
	}
	bhp_scc_assert( $scc_checked > 0, '5: at least one slide resolved to an attachment ID', $failures );

	/*
	 * PARITY WITH /complete-collection/. The founder's instruction was "just
	 * use the collection page carousel image gallery that we already created",
	 * and this is the assertion that means it: the first slide's file on the
	 * shop archive is the first slide's file on the collection page.
	 */
	$scc_first    = $scc_media['items'][0];
	$scc_first_id = ( isset( $scc_first['type'] ) && 'video' === $scc_first['type'] )
		? ( isset( $scc_first['poster_id'] ) ? (int) $scc_first['poster_id'] : 0 )
		: ( ! empty( $scc_first['id'] ) ? (int) $scc_first['id'] : 0 );

	if ( $scc_first_id > 0 ) {
		$scc_first_src = wp_get_attachment_image_url( $scc_first_id, 'large' );
		bhp_scc_assert(
			is_string( $scc_first_src ) && '' !== $scc_first_src && false !== strpos( $scc_shop_html, basename( $scc_first_src ) ),
			"5: slide 1 (ID {$scc_first_id}) is the file served in the rendered shop archive",
			$failures
		);
	} else {
		echo "SKIP: 5: slide 1 carries no resolvable attachment ID\n";
	}
}

echo "\n=== 6. The copy and the destination are UNCHANGED ===\n";

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
		"6: {$scc_what} is verbatim — \"{$scc_text}\"",
		$failures
	);
}

$scc_expected_href = esc_url( home_url( '/complete-collection/' ) );
bhp_scc_assert(
	false !== strpos( $scc_shop_html, 'href="' . $scc_expected_href . '"' ),
	"6: the CTA href is unchanged — {$scc_expected_href}",
	$failures
);

/*
 * Document order: carousel → heading → CTA, which is the requested
 * "gallery + heading + CTA read as one banner unit" expressed the one way a
 * rendered document can prove it. (Whether they LOOK like one unit is a
 * geometry question and is explicitly not claimed here — see the header.)
 */
$scc_p_media   = strpos( $scc_shop_html, 'woo-complete-collection-banner__media' );
$scc_p_heading = strpos( $scc_shop_html, 'Looking for the complete series?' );
$scc_p_cta     = strpos( $scc_shop_html, 'View the Complete Collection' );
bhp_scc_assert(
	false !== $scc_p_media && false !== $scc_p_heading && false !== $scc_p_cta
		&& $scc_p_media < $scc_p_heading && $scc_p_heading < $scc_p_cta,
	'6: document order is carousel → heading → CTA',
	$failures
);

/*
 * ⛔ THE CAROUSEL'S LINKS, CHECKED RATHER THAN ASSUMED. The component emits no
 *    anchor except the <video> element's "Download the flip-through instead."
 *    no-codec fallback, which points at the media file. Anything else — a link
 *    back into /complete-collection/ from inside the gallery, say — would be a
 *    second competing destination sitting inside the banner and would need to
 *    be reported, not shipped.
 */
if ( preg_match( '#<section[^>]*id="bhp-look-inside-complete_collection".*?</section>#s', $scc_shop_html, $scc_sec ) ) {
	preg_match_all( '#<a\s[^>]*href="([^"]*)"#i', $scc_sec[0], $scc_links );
	$scc_bad = array();
	foreach ( $scc_links[1] as $scc_href ) {
		if ( ! preg_match( '#\.(mp4|webm)(\?|$)#i', $scc_href ) ) {
			$scc_bad[] = $scc_href;
		}
	}
	bhp_scc_assert(
		empty( $scc_bad ),
		'6: the carousel contains no navigating links (found: ' . ( $scc_bad ? implode( ', ', $scc_bad ) : 'none' ) . ')',
		$failures
	);
} else {
	bhp_scc_assert( false, '6: the gallery <section> could not be isolated for link inspection', $failures );
}

echo "\n=== 7. ZERO LAYOUT SHIFT, and the LCP slide is EAGER ===\n";

/*
 * ⛔ THE CHECK A SCREENSHOT CANNOT MAKE. By the time anything is screenshotted
 *    the images have loaded and the shift has already happened and finished.
 *    The only durable evidence is the attributes in the markup.
 */
if ( preg_match_all( '/<img[^>]*bhp-gallery__img[^>]*>/i', $scc_shop_html, $scc_imgs ) ) {
	$scc_img_n = count( $scc_imgs[0] );
	bhp_scc_assert( $scc_img_n > 0, "7: {$scc_img_n} stage <img> elements found", $failures );

	foreach ( $scc_imgs[0] as $scc_j => $scc_tag ) {
		$scc_pos = $scc_j + 1;
		bhp_scc_assert(
			(bool) preg_match( '/\swidth="\d+"/', $scc_tag ) && (bool) preg_match( '/\sheight="\d+"/', $scc_tag ),
			"7: stage image {$scc_pos} carries real width and height attributes",
			$failures
		);
	}

	/*
	 * ⛔ SLIDE 0 IS THE SHOP ARCHIVE'S LCP. `bhp_woocommerce_archive_hero()`
	 *    directly above this banner is TEXT ONLY — eyebrow, <h1>, lead
	 *    paragraph, no image — so the first stage image is the largest thing
	 *    painted. `$eager_first = true` is the deliberate call recorded in
	 *    functions.php, asserted here so a future "lazy-load everything" pass
	 *    has to read the reasoning before changing it.
	 */
	bhp_scc_assert(
		(bool) preg_match( '/\sloading="eager"/', $scc_imgs[0][0] )
		&& (bool) preg_match( '/\sfetchpriority="high"/', $scc_imgs[0][0] ),
		'7: slide 1 is eager + fetchpriority=high (it is the shop archive LCP)',
		$failures
	);

	/*
	 * And every OTHER slide must stay lazy, or the page fetches the whole
	 * gallery up front — which is the mirror-image mistake.
	 */
	if ( $scc_img_n > 1 ) {
		$scc_lazy_ok = true;
		foreach ( array_slice( $scc_imgs[0], 1 ) as $scc_tag ) {
			if ( ! preg_match( '/\sloading="lazy"/', $scc_tag ) ) {
				$scc_lazy_ok = false;
			}
		}
		bhp_scc_assert( $scc_lazy_ok, '7: every slide after the first is lazy-loaded', $failures );
	}

	/*
	 * F7 / CYCLE142-LD-01: a hidden slide has no layout box, so an `auto`
	 * sizes clause cannot resolve and the browser fetches the LARGEST srcset
	 * candidate for a picture nobody is looking at. The fixed `sizes` is what
	 * prevents it, and the `auto,` prefix must not be present.
	 */
	$scc_auto = 0;
	foreach ( $scc_imgs[0] as $scc_tag ) {
		if ( preg_match( '/\ssizes="auto[,\s]/i', $scc_tag ) ) {
			$scc_auto++;
		}
	}
	bhp_scc_assert(
		0 === $scc_auto,
		"7: no stage image carries an unresolvable `auto` sizes clause (got {$scc_auto})",
		$failures
	);
} else {
	bhp_scc_assert( false, '7: no stage <img> elements found in the rendered shop archive', $failures );
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
