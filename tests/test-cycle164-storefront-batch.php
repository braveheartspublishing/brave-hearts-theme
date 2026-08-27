<?php
/**
 * Brave Hearts — CYCLE164 STOREFRONT BATCH (theme 1.19.241).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle164-storefront-batch.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * THE FOUR ITEMS THIS SUITE GUARDS — `CYCLE164-LD-STOREFRONT-BATCH`
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   1. The flip-through cue on the product page  (`CYCLE164-CX` #1)
 *   2. The A/B capture popup is homepage-only    (`CYCLE164-CX` #3)
 *   3. The 30-day guarantee on the product page  (`CYCLE164-CX` #4)
 *   4. hasMerchantReturnPolicy + the sitemap/redirect contradiction
 *                                                (Gimli's indexing audit)
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE ASSERTIONS THAT MATTER MOST ARE THE NEGATIVE ONES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Three of the checks below exist to catch a mistake nobody would make on
 * purpose, and they are the reason this file is worth its length:
 *
 *   · §4 asserts NO `aggregateRating` and NO `review` in the product
 *     schema. There are ZERO real reviews. Emitting either is the single
 *     most explicit prohibition in `BHP-AGENT-STANDING-RULES.md` §2 and
 *     `.claude/rules/schema.md`, and a future "SEO improvement" reaching
 *     for the obvious missing-field fix is exactly how it would happen.
 *
 *   · §4 asserts NO `returnMethod`. The live policy says "there is nothing
 *     to send back", so `ReturnByMail` would be a structured-data claim
 *     the store's own policy contradicts.
 *
 *   · §1 asserts NO `autoplay` anywhere on the product page.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads
 *    rendered documents and calls functions. It proves presence, absence,
 *    cardinality, document order and JSON shape. It CANNOT prove geometry
 *    (that the cue is above the fold, that the CTA did not move) and it
 *    CANNOT prove that clicking the cue plays the video — that needs a
 *    real browser with `window.innerWidth` asserted, and lives in the
 *    CYCLE164 QA evidence instead. Neither is claimed here.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = 0;

function bhp_c164_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

function bhp_c164_skip( $label, &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped++;
}

/** Document order helper. Returns true when $a occurs before $b. */
function bhp_c164_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
}

/** Fetch a URL and return [ code, body ]. */
function bhp_c164_get( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) ) {
		return array( 0, '' );
	}
	return array( (int) wp_remote_retrieve_response_code( $res ), (string) wp_remote_retrieve_body( $res ) );
}

/** Pull the Product node out of a rendered page's Rank Math schema block. */
function bhp_c164_product_schema( $html ) {
	if ( ! preg_match( '/<script[^>]*class="rank-math-schema"[^>]*>(.*?)<\/script>/su', $html, $m ) ) {
		return null;
	}
	$data = json_decode( $m[1], true );
	if ( ! is_array( $data ) || empty( $data['@graph'] ) ) {
		return null;
	}
	foreach ( $data['@graph'] as $node ) {
		$type = isset( $node['@type'] ) ? $node['@type'] : '';
		if ( 'Product' === $type || ( is_array( $type ) && in_array( 'Product', $type, true ) ) ) {
			return $node;
		}
	}
	return null;
}

/*
 * The canonical titles this suite works against, by slug. Resolved rather
 * than hardcoded as IDs — staging and production have different IDs for the
 * same product and an ID here would silently test the wrong thing.
 */
$c164_slugs = array(
	'mariana' => 'adventures-of-charlotte-and-henry-the-mariana-trench-paperback',
	'everest' => 'adventures-of-charlotte-and-henry-mount-everest-paperback',
	'amazon'  => 'adventures-of-charlotte-and-henry-the-amazon-paperback',
);

$c164_urls = array();
foreach ( $c164_slugs as $c164_key => $c164_slug ) {
	$c164_post = get_page_by_path( $c164_slug, OBJECT, 'product' );
	if ( $c164_post ) {
		$c164_urls[ $c164_key ] = get_permalink( $c164_post );
	}
}

echo "\n=== 0. The release's functions exist ===\n";

foreach ( array(
	'bhp_book_hero_gallery_media',
	'bhp_book_flip_through_cue_html',
	'bhp_book_media_duration',
	'bhp_should_show_parent_ab_popup',
	'bhp_seo_theme_redirected_paths',
	'bhp_seo_exclude_redirected_from_sitemap',
) as $c164_fn ) {
	bhp_c164_assert( function_exists( $c164_fn ), "0: {$c164_fn}() exists", $failures );
}

if ( ! function_exists( 'bhp_book_flip_through_cue_html' ) ) {
	fwrite( STDERR, "Cannot continue: this is not the 1.19.241 build.\n" );
	exit( 1 );
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n=== 1. The flip-through cue ===\n";
// ═══════════════════════════════════════════════════════════════════════

/*
 * Unit level first, because the page-level checks below cannot distinguish
 * "correctly absent" from "broken and therefore absent".
 */
bhp_c164_assert(
	'' === bhp_book_flip_through_cue_html( array( 'items' => array() ) ),
	'1: an empty media set yields no cue',
	$failures
);
bhp_c164_assert(
	'' === bhp_book_flip_through_cue_html( array(
		'items' => array(
			array( 'type' => 'image', 'id' => 1, 'alt' => '' ),
			array( 'type' => 'image', 'id' => 2, 'alt' => '' ),
		),
	) ),
	'1: a stills-only media set yields no cue (it must never promise a video)',
	$failures
);

/*
 * ⛔ THE INDEX IS THE WHOLE POINT. A cue that opens the wrong slide is worse
 *    than no cue, so this asserts the emitted index is the position of the
 *    FIRST video in the list it was handed — including the offset created by
 *    the featured image the hero list prepends.
 */
$c164_synth = bhp_book_flip_through_cue_html( array(
	'items' => array(
		array( 'type' => 'image', 'id' => 1, 'alt' => '' ),   // the prepended cover
		array( 'type' => 'video', 'duration' => 12, 'label' => 'x' ),
		array( 'type' => 'image', 'id' => 2, 'alt' => '' ),
	),
) );
bhp_c164_assert(
	false !== strpos( $c164_synth, 'data-bhp-gallery-cue="1"' ),
	'1: the cue names the video\'s index in the RENDERED list, not the registry list',
	$failures
);
bhp_c164_assert(
	false !== strpos( $c164_synth, '(12 sec)' ),
	'1: a known duration is printed',
	$failures
);

/*
 * ⛔ 0 SECONDS MUST PRINT NO DURATION AT ALL. An asset whose metadata is
 *    missing has no honest length, and "(0 sec)" would be a fabricated
 *    figure in customer-facing copy.
 */
$c164_nodur = bhp_book_flip_through_cue_html( array(
	'items' => array( array( 'type' => 'video', 'duration' => 0, 'label' => 'x' ) ),
) );
bhp_c164_assert(
	false !== strpos( $c164_nodur, 'data-bhp-gallery-cue="0"' )
		&& false === strpos( $c164_nodur, 'sec)' ),
	'1: an unknown duration prints the cue WITHOUT inventing a number',
	$failures
);

/* §9.1 voice rule — customer-facing copy carries no company "we". */
bhp_c164_assert(
	! preg_match( '/\b(we|us|our)\b/i', wp_strip_all_tags( $c164_synth ) ),
	'1: the cue copy contains no "we"/"us"/"our" (Standing Rules §9.1)',
	$failures
);

/* Page level. */
if ( isset( $c164_urls['mariana'] ) ) {
	list( $c164_code, $c164_mariana ) = bhp_c164_get( $c164_urls['mariana'] );
	bhp_c164_assert( 200 === $c164_code, "1: the Mariana product page returns HTTP 200 (got {$c164_code})", $failures );

	if ( 200 === $c164_code ) {
		bhp_c164_assert(
			substr_count( $c164_mariana, 'data-bhp-gallery-cue' ) === 1,
			'1: exactly one cue renders on the Mariana product page',
			$failures
		);

		/*
		 * Cross-check the emitted index against the DOM it points into: find
		 * the slide carrying data-bhp-slide-type="video" and confirm the cue
		 * names the same number. This is the assertion that would have caught
		 * a drift between the cue and the gallery.
		 */
		if ( preg_match( '/data-bhp-gallery-cue="(\d+)"/', $c164_mariana, $c164_cm )
			&& preg_match_all( '/data-bhp-slide="(\d+)"\s+data-bhp-slide-type="video"/', $c164_mariana, $c164_vm ) ) {
			bhp_c164_assert(
				in_array( $c164_cm[1], $c164_vm[1], true ),
				"1: the cue's index ({$c164_cm[1]}) is a slide that really is a video (video slides: " . implode( ',', $c164_vm[1] ) . ')',
				$failures
			);
		} else {
			bhp_c164_assert( false, '1: could not locate both the cue index and a video slide to compare', $failures );
		}

		/* ⛔ NO AUTOPLAY. Not on any element, not on any viewport. */
		bhp_c164_assert(
			false === stripos( $c164_mariana, 'autoplay' ),
			'1: the product page emits no autoplay attribute anywhere',
			$failures
		);
		bhp_c164_assert(
			false !== strpos( $c164_mariana, 'preload="metadata"' ),
			'1: the video still ships preload="metadata" (no video bytes before a click)',
			$failures
		);
	}
} else {
	bhp_c164_skip( '1: the Mariana product page is not present on this environment', $skipped );
}

/*
 * ⛔ THE AMAZON MUST NOT SHOW A CUE. Andrew withdrew that clip on
 *    2026-08-02 and the registry entry is commented out. If this ever
 *    starts failing, either the clip was restored (fine — delete this
 *    assertion knowingly) or the cue has stopped checking for an asset.
 */
if ( isset( $c164_urls['amazon'] ) ) {
	list( $c164_acode, $c164_amazon ) = bhp_c164_get( $c164_urls['amazon'] );
	if ( 200 === $c164_acode ) {
		bhp_c164_assert(
			false === strpos( $c164_amazon, 'data-bhp-gallery-cue' ),
			'1: The Amazon renders NO cue — its flip-through is withdrawn, and the control follows the asset',
			$failures
		);
	} else {
		bhp_c164_skip( "1: The Amazon product page returned {$c164_acode}", $skipped );
	}
} else {
	bhp_c164_skip( '1: The Amazon product page is not present on this environment', $skipped );
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n=== 2. The capture popup is off the selling pages ===\n";
// ═══════════════════════════════════════════════════════════════════════

/*
 * ⚠ SECTION HEADING CORRECTED AT 1.19.267 (2026-08-19,
 *   `CYCLE165-LD-ITERATE-3-POPUP-SIMPLE`), AND NOT ONE ASSERTION BELOW WAS
 *   TOUCHED. This section was written at 1.19.241, when the popup had just
 *   been narrowed to the homepage, and it said so in its heading. 1.19.241's
 *   own comment in `bhp_should_show_parent_ab_popup()` flagged that blog posts
 *   had lost the offer with nobody deciding they should; that flag has since
 *   been discharged and the surface is now the homepage AND single blog posts.
 *
 * ⭐ EVERY ASSERTION IN THIS SECTION IS STILL TRUE AND STILL WANTED — the
 *   popup on the homepage, and suppressed on the product page, the collection
 *   page, the cart, the checkout and /teachers/. Only the HEADING claimed
 *   something the code no longer does. The blog-post surface is asserted in
 *   `test-popup-ab.php` §6 rather than duplicated here.
 */

list( $c164_hcode, $c164_home ) = bhp_c164_get( home_url( '/' ) );
bhp_c164_assert( 200 === $c164_hcode, "2: the homepage returns HTTP 200 (got {$c164_hcode})", $failures );

/*
 * ⛔ THE POPUP IS NARROWED, NOT KILLED. This assertion is the one that stops
 *    a future "tidy-up" from removing the surface that produced the funnel's
 *    only paid subscriber.
 */
if ( 200 === $c164_hcode ) {
	bhp_c164_assert(
		false !== strpos( $c164_home, 'mariana-popup--ab' ),
		'2: the popup STILL renders on the homepage (it was narrowed, not disabled)',
		$failures
	);
}

$c164_suppress = array();
if ( isset( $c164_urls['mariana'] ) ) {
	$c164_suppress['the Mariana product page'] = $c164_urls['mariana'];
}
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ UPDATED 1.19.296 (2026-08-27, `CYCLE167-LD-CAPTURE-FIX-BUILD`) —
 *    `/complete-collection/` MOVES FROM "SUPPRESSED" TO "RENDERS".
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHY THE SUPPRESSION WAS RIGHT AT 1.19.241, and it is not being called
 *    wrong: `commerce-cx` / Pippin's `CYCLE164-CX` #3 found that interrupting
 *    somebody who is already reading a price costs more than it earns. That
 *    reasoning is intact and still applies to the product pages, the cart and
 *    the checkout, which all stay in the suppressed set below.
 *
 * ⛔⛔ WHAT CHANGED IS WHAT THE PAGE TURNED OUT TO BE. Production access logs
 *     over 30 days show `/complete-collection/` is the **#1 human entry page
 *     on the site** — 134 entries, rank 1 — with an entry:pageview ratio of
 *     1.32, meaning most people who land there see that one page and leave. It
 *     is not only a price page; it is the front door. And because it matched
 *     neither the front page nor a single post, it fell through to the
 *     exit-intent modal, whose MOBILE trigger is 20s dwell AND 45% scroll AND
 *     a 400px upward flick inside 600ms. The site's busiest entrance had its
 *     hardest gate.
 *
 * ⭐ AUTHORISED by Andrew Signore, carrier item 280 (2026-08-27), naming the
 *    placement flip on the top entry page. ⚠ RELAYED, not witnessed here.
 * ⚠ A NARROWER OPTION IS ON THE RECORD for him: `marketing-growth`'s R4
 *   recommends flipping MOBILE ONLY and leaving desktop exit-intent alone.
 *   Reported at the deploy gate; not decided by this suite.
 */
$c164_coll = get_page_by_path( 'complete-collection' );
if ( $c164_coll ) {
	list( $c164_ccode, $c164_cbody ) = bhp_c164_get( get_permalink( $c164_coll ) );
	if ( 200 !== $c164_ccode ) {
		bhp_c164_skip( "2: /complete-collection/ returned {$c164_ccode}", $skipped );
	} else {
		bhp_c164_assert(
			false !== strpos( $c164_cbody, 'mariana-popup--ab' ),
			'2: ⭐ the popup NOW RENDERS on /complete-collection/ — the #1 entry page joins the gated surface',
			$failures
		);
		/*
		 * ⛔ AND IT IS THE PARENT A/B SURFACE, NOT THE OLD EXIT GATE. If the
		 *    exit-intent modal were still the one rendering here, the flip
		 *    would have achieved nothing while appearing to.
		 *
		 * ⭐⭐ UPDATED 1.19.300 (`CYCLE167-LD-POPUP-TIME-ONLY`) ON FOUNDER
		 *     CARRIER ITEM 306, 2026-08-27: *"We also dont have the awareness
		 *     or market share - I think we keep our pop ups time only."*
		 *     ⚠ RELAYED via the Chief of Staff, not witnessed by this author.
		 *
		 * ⛔ THIS ASSERTION READ `"mode":"gated"` UNTIL 1.19.299, and the
		 *    change of literal is recorded rather than quietly swapped. What
		 *    this line has always been FOR is unchanged — proving that the
		 *    surface rendering on /complete-collection/ is the parent A/B
		 *    popup and not the exit-intent modal. Item 306 moved that popup
		 *    from mode `gated` to mode `simple`; the assertion follows it.
		 *
		 * ⭐ WHY THIS IS THE STRONGEST EVIDENCE IN THE SUITE FOR ITEM 306:
		 *    unlike a source-level check, this reads the popup's config out of
		 *    the REAL RENDERED HTML of the page that takes more first arrivals
		 *    than any other. If the new trigger did not actually reach a
		 *    visitor, this line would fail.
		 */
		bhp_c164_assert(
			false === strpos( $c164_cbody, 'exit-intent-popup' )
				&& false !== strpos( $c164_cbody, '&quot;mode&quot;:&quot;simple&quot;' )
				&& false === strpos( $c164_cbody, '&quot;mode&quot;:&quot;gated&quot;' ),
			'2: ⭐ item 306: the TIME-ONLY parent popup renders there, not the exit-intent modal and not a gated trigger',
			$failures
		);

		/* ⛔ AND THE RENDERED CONFIG CARRIES NO SCROLL THRESHOLD. Asserted on
		 *    the HTML rather than the template, because this is the page where
		 *    a leftover threshold would cost the most. */
		bhp_c164_assert(
			false === strpos( $c164_cbody, 'scrollPct' )
				&& false === strpos( $c164_cbody, '&quot;scrollPct&quot;' ),
			'2: ⛔ item 306: the rendered popup config on /complete-collection/ carries NO scroll threshold',
			$failures
		);
	}
}
if ( function_exists( 'wc_get_page_permalink' ) ) {
	$c164_suppress['the cart'] = wc_get_page_permalink( 'cart' );
	$c164_suppress['the checkout'] = wc_get_page_permalink( 'checkout' );
}

foreach ( $c164_suppress as $c164_label => $c164_url ) {
	if ( ! $c164_url ) {
		bhp_c164_skip( "2: {$c164_label} has no resolvable URL", $skipped );
		continue;
	}
	list( $c164_scode, $c164_sbody ) = bhp_c164_get( $c164_url );
	if ( 200 !== $c164_scode ) {
		bhp_c164_skip( "2: {$c164_label} returned {$c164_scode}", $skipped );
		continue;
	}
	bhp_c164_assert(
		false === strpos( $c164_sbody, 'mariana-popup--ab' ),
		"2: the popup is suppressed on {$c164_label}",
		$failures
	);
}

/*
 * Funnel isolation is unchanged by this release and is asserted so, because
 * `.claude/rules/funnels.md` treats it as load-bearing: the teacher popup
 * must still be the ONLY popup on /teachers/.
 */
$c164_teach = get_page_by_path( 'teachers' );
if ( $c164_teach ) {
	list( $c164_tcode, $c164_tbody ) = bhp_c164_get( get_permalink( $c164_teach ) );
	if ( 200 === $c164_tcode ) {
		bhp_c164_assert(
			false === strpos( $c164_tbody, 'mariana-popup--ab' ),
			'2: /teachers/ still shows no parent-funnel popup (isolation intact)',
			$failures
		);
	} else {
		bhp_c164_skip( "2: /teachers/ returned {$c164_tcode}", $skipped );
	}
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n=== 3. The 30-day guarantee on the product page ===\n";
// ═══════════════════════════════════════════════════════════════════════

if ( ! function_exists( 'bhp_bundle_render_landing_guarantee' ) ) {
	bhp_c164_skip( '3: the bundle plugin is not active, so there is no guarantee component to reuse', $skipped );
} elseif ( ! isset( $c164_mariana ) || '' === $c164_mariana ) {
	bhp_c164_skip( '3: no rendered product page to inspect', $skipped );
} else {
	bhp_c164_assert(
		substr_count( $c164_mariana, 'class="bhp-landing-guarantee"' ) === 1,
		'3: exactly one guarantee block renders on the product page',
		$failures
	);
	bhp_c164_assert(
		false !== strpos( $c164_mariana, 'class="bhp-product-guarantee"' ),
		'3: it is wrapped in the product-scoped container that carries its styles',
		$failures
	);

	/* It must sit BELOW the buy button, never above it. */
	bhp_c164_assert(
		bhp_c164_before( $c164_mariana, 'bhp-formats__cta', 'bhp-landing-guarantee' ),
		'3: the guarantee renders AFTER the primary CTA in document order',
		$failures
	);

	/*
	 * ⛔⛔ THE COPY IS THE COLLECTION PAGE'S, BYTE FOR BYTE. This is the
	 *     assertion that enforces "reuse the component, do not retype the
	 *     copy". If somebody ever forks the wording for the product page,
	 *     this fails — which is the intent, because approved copy is locked.
	 */
	if ( $c164_coll ) {
		list( $c164_ccode, $c164_cbody ) = bhp_c164_get( get_permalink( $c164_coll ) );
		if ( 200 === $c164_ccode
			&& preg_match( '/<p class="bhp-landing-guarantee">(.*?)<\/p>/su', $c164_cbody, $c164_cg )
			&& preg_match( '/<p class="bhp-landing-guarantee">(.*?)<\/p>/su', $c164_mariana, $c164_pg ) ) {
			bhp_c164_assert(
				trim( $c164_cg[1] ) === trim( $c164_pg[1] ),
				'3: the product page\'s guarantee is byte-identical to the collection page\'s',
				$failures
			);
		} else {
			bhp_c164_skip( '3: could not fetch both guarantee blocks to compare', $skipped );
		}
	}

	/* A refund promise must link somewhere real. */
	if ( function_exists( 'bhp_bundle_guarantee_policy_url' ) ) {
		$c164_purl = bhp_bundle_guarantee_policy_url();
		list( $c164_pcode, ) = bhp_c164_get( $c164_purl );
		bhp_c164_assert(
			200 === $c164_pcode,
			"3: the policy link resolves (HTTP {$c164_pcode}) — {$c164_purl}",
			$failures
		);
	}

	/*
	 * ⛔ THE ACTIVITY BOOK MUST NOT CARRY A PRINTED-BOOK RETURN PROMISE. It is
	 *    a $5 downloadable and the policy describes books.
	 */
	$c164_ab = get_page_by_path( 'the-adventure-activity-book', OBJECT, 'product' );
	if ( $c164_ab ) {
		list( $c164_abcode, $c164_abbody ) = bhp_c164_get( get_permalink( $c164_ab ) );
		if ( 200 === $c164_abcode ) {
			bhp_c164_assert(
				false === strpos( $c164_abbody, 'bhp-landing-guarantee' ),
				'3: the downloadable Activity Book carries NO printed-book guarantee',
				$failures
			);
		} else {
			bhp_c164_skip( "3: the Activity Book page returned {$c164_abcode}", $skipped );
		}
	}
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n=== 4. Product schema: return policy in, ratings still out ===\n";
// ═══════════════════════════════════════════════════════════════════════

if ( ! isset( $c164_mariana ) || '' === $c164_mariana ) {
	bhp_c164_skip( '4: no rendered product page to inspect', $skipped );
} else {
	$c164_prod = bhp_c164_product_schema( $c164_mariana );
	bhp_c164_assert( is_array( $c164_prod ), '4: a Product node is present in the rank-math-schema block', $failures );

	if ( is_array( $c164_prod ) ) {
		/*
		 * ⛔⛔ THE PROHIBITION. Zero reviews exist. Neither of these may ever
		 *     appear, and no "missing field" warning justifies inventing one.
		 */
		bhp_c164_assert(
			! isset( $c164_prod['aggregateRating'] ),
			'4: NO aggregateRating is emitted (there are zero real reviews)',
			$failures
		);
		bhp_c164_assert(
			! isset( $c164_prod['review'] ),
			'4: NO review schema is emitted (there are zero real reviews)',
			$failures
		);

		$c164_offers = isset( $c164_prod['offers'] ) ? $c164_prod['offers'] : array();
		if ( isset( $c164_offers['@type'] ) ) {
			$c164_offers = array( $c164_offers );
		}
		bhp_c164_assert(
			count( $c164_offers ) >= 1,
			'4: at least one Offer is present (got ' . count( $c164_offers ) . ')',
			$failures
		);

		foreach ( $c164_offers as $c164_i => $c164_offer ) {
			$c164_rp = isset( $c164_offer['hasMerchantReturnPolicy'] ) ? $c164_offer['hasMerchantReturnPolicy'] : null;

			bhp_c164_assert(
				is_array( $c164_rp ) && 'MerchantReturnPolicy' === ( $c164_rp['@type'] ?? '' ),
				"4: offer[{$c164_i}] carries a MerchantReturnPolicy",
				$failures
			);
			if ( ! is_array( $c164_rp ) ) {
				continue;
			}
			bhp_c164_assert(
				'US' === ( $c164_rp['applicableCountry'] ?? '' ),
				"4: offer[{$c164_i}] applicableCountry is US",
				$failures
			);
			bhp_c164_assert(
				'https://schema.org/MerchantReturnFiniteReturnWindow' === ( $c164_rp['returnPolicyCategory'] ?? '' ),
				"4: offer[{$c164_i}] returnPolicyCategory is MerchantReturnFiniteReturnWindow",
				$failures
			);
			bhp_c164_assert(
				30 === (int) ( $c164_rp['merchantReturnDays'] ?? 0 ),
				"4: offer[{$c164_i}] merchantReturnDays is 30 — the number the live policy states",
				$failures
			);
			bhp_c164_assert(
				'https://schema.org/FreeReturn' === ( $c164_rp['returnFees'] ?? '' ),
				"4: offer[{$c164_i}] returnFees is FreeReturn",
				$failures
			);

			/*
			 * ⛔ NO returnMethod. VERIFIED on production 2026-08-18, the policy
			 *    page reads "there is nothing to send back - keep the books or
			 *    pass them along." ReturnByMail would contradict it.
			 */
			bhp_c164_assert(
				! isset( $c164_rp['returnMethod'] ),
				"4: offer[{$c164_i}] declares NO returnMethod — nothing is sent back, so none may be claimed",
				$failures
			);

			/* Regression guard: the shipping block this filter already emitted. */
			bhp_c164_assert(
				'OfferShippingDetails' === ( $c164_offer['shippingDetails']['@type'] ?? '' ),
				"4: offer[{$c164_i}] still emits its OfferShippingDetails (unchanged)",
				$failures
			);
		}
	}

	/*
	 * ⛔ THE DOWNLOADABLE ACTIVITY BOOK MUST RECEIVE NEITHER. It is outside
	 *    the six-edition allowlist, so it gets no shipping claim and no
	 *    printed-book return policy.
	 */
	if ( isset( $c164_abbody ) && '' !== $c164_abbody ) {
		$c164_abprod = bhp_c164_product_schema( $c164_abbody );
		if ( is_array( $c164_abprod ) ) {
			$c164_aboffers = isset( $c164_abprod['offers'] ) ? $c164_abprod['offers'] : array();
			if ( isset( $c164_aboffers['@type'] ) ) {
				$c164_aboffers = array( $c164_aboffers );
			}
			$c164_leak = false;
			foreach ( $c164_aboffers as $c164_aboffer ) {
				if ( isset( $c164_aboffer['hasMerchantReturnPolicy'] ) || isset( $c164_aboffer['shippingDetails'] ) ) {
					$c164_leak = true;
				}
			}
			bhp_c164_assert(
				! $c164_leak,
				'4: the downloadable Activity Book receives NO return policy and NO shipping details',
				$failures
			);
		}
	}
}

// ═══════════════════════════════════════════════════════════════════════
echo "\n=== 5. A 301'd URL is not also advertised in the sitemap ===\n";
// ═══════════════════════════════════════════════════════════════════════

$c164_paths = bhp_seo_theme_redirected_paths();
$c164_tg    = untrailingslashit( (string) wp_parse_url( home_url( '/teachers-guide/' ), PHP_URL_PATH ) );

bhp_c164_assert(
	in_array( $c164_tg, $c164_paths, true ),
	'5: /teachers-guide/ is on the redirected-paths list',
	$failures
);

/*
 * ⛔ THE DRIFT ALARM. The list above is a second copy of a fact that lives in
 *    functions.php. If somebody deletes the redirect and leaves the list
 *    behind, the sitemap would keep hiding a page that had become legitimate
 *    again — silently. This fails instead.
 */
bhp_c164_assert(
	function_exists( 'bhp_redirect_legacy_teacher_resources' ),
	'5: the redirect this exclusion depends on still exists',
	$failures
);
bhp_c164_assert(
	has_action( 'template_redirect', 'bhp_redirect_legacy_teacher_resources' ) !== false,
	'5: that redirect is still hooked on template_redirect',
	$failures
);

/* The filter itself: excludes the redirected URL, passes everything else. */
bhp_c164_assert(
	array() === bhp_seo_exclude_redirected_from_sitemap(
		array( 'loc' => home_url( '/teachers-guide/' ) ), 'post', null
	),
	'5: the filter drops the /teachers-guide/ entry',
	$failures
);
$c164_keep = array( 'loc' => home_url( '/teachers/' ) );
bhp_c164_assert(
	$c164_keep === bhp_seo_exclude_redirected_from_sitemap( $c164_keep, 'post', null ),
	'5: the filter leaves /teachers/ — the destination — untouched',
	$failures
);
$c164_keep2 = array( 'loc' => home_url( '/blog/some-post/' ) );
bhp_c164_assert(
	$c164_keep2 === bhp_seo_exclude_redirected_from_sitemap( $c164_keep2, 'post', null ),
	'5: the filter leaves an unrelated URL untouched',
	$failures
);

/* And the rendered artefact, which is the only thing Google actually reads. */
list( $c164_smcode, $c164_sm ) = bhp_c164_get( home_url( '/page-sitemap.xml' ) );
if ( 200 === $c164_smcode ) {
	bhp_c164_assert(
		false === strpos( $c164_sm, '/teachers-guide/' ),
		'5: the rendered page-sitemap.xml no longer contains /teachers-guide/',
		$failures
	);
	bhp_c164_assert(
		false !== strpos( $c164_sm, '/teachers/' ),
		'5: the rendered page-sitemap.xml STILL contains /teachers/ (the destination is not lost)',
		$failures
	);
} else {
	bhp_c164_skip( "5: page-sitemap.xml returned {$c164_smcode}", $skipped );
}

/*
 * ⛔ REPORTED, NOT ASSERTED — /product/the-adventure-activity-book/ is
 *    `noindex` (per-post `rank_math_robots`) AND present in
 *    product-sitemap.xml. That contradiction is REAL and is Andrew's to
 *    decide (index it, or exclude it), so this suite prints the current
 *    state and deliberately does not fail on it. Changing it here would be
 *    resolving a contradiction that belongs to the owner.
 */
echo "\n--- REPORT ONLY (Andrew's decision, not a test) ---\n";
$c164_abp = get_page_by_path( 'the-adventure-activity-book', OBJECT, 'product' );
if ( $c164_abp ) {
	$c164_robots = get_post_meta( $c164_abp->ID, 'rank_math_robots', true );
	$c164_robots = is_array( $c164_robots ) ? implode( ',', $c164_robots ) : (string) $c164_robots;
	list( $c164_pscode, $c164_ps ) = bhp_c164_get( home_url( '/product-sitemap.xml' ) );
	$c164_insm = ( 200 === $c164_pscode && false !== strpos( $c164_ps, 'the-adventure-activity-book' ) ) ? 'YES' : 'no';
	echo "  Activity Book (id {$c164_abp->ID}): rank_math_robots = [{$c164_robots}]; in product-sitemap.xml = {$c164_insm}\n";
	echo "  -> UNRESOLVED BY DESIGN. Options and recommendation are in the CYCLE164 report.\n";
} else {
	echo "  Activity Book not present on this environment.\n";
}

echo "\n";
echo "SKIPPED: {$skipped}\n";
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
