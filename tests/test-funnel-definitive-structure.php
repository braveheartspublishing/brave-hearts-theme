<?php
/**
 * Brave Hearts — THE DEFINITIVE FUNNEL-PAGE STRUCTURE (theme 1.19.213).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-funnel-definitive-structure.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, relayed verbatim by `chief-of-staff` in the build brief
 * (⛔ RELAYED; NOT witnessed first-hand by the agent that wrote this file).
 * Every funnel page runs, in this order:
 *
 *   1. Heading   2. Paragraph   3. Primary CTA (each funnel's own)
 *   4. THE UNIVERSAL COLLECTION CAROUSEL, DIRECTLY UNDER THE PRIMARY CTA,
 *      "mostly visible" on landing
 *   5. The checkmark selling angles
 *   6. The Best Value buy section, raised to follow directly
 *
 * …with the secondary CTA removed ("it will distract from the main CTA")
 * and the static three-book hero lockup removed, because slide 1 of the
 * carousel IS the three-book image. "This needs to be on every funnel now."
 *
 * This suite guards the parts of that a rendered document can prove:
 * DOCUMENT ORDER, PRESENCE, ABSENCE and CARDINALITY.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   · ⛔ THE FOLD. Nothing here measures a pixel. "Above the fold at
 *     1920x1080 / 1440x900 / 390x844" is a painted-box measurement and is
 *     recorded from a real browser in the release evidence, with
 *     `window.innerWidth` asserted on every run. Claiming a fold result
 *     from a document fetch would be a fabricated verification, which is
 *     the same failure class as a fabricated review.
 *   · ⛔ "MOSTLY VISIBLE". Same reason. Document order proves the carousel
 *     is under the CTA; only a browser proves how much of it a visitor
 *     sees.
 *   · nothing about JS behaviour: slide changes, video playback, thumb
 *     clicks and the lightbox are verified in a real browser;
 *   · nothing about console errors;
 *   · nothing about production, when run on staging.
 *
 * ⛔ READ-ONLY. No post, option, product, price, coupon, stock level,
 *    shipping, tax, payment or checkout setting is read or written. No cart
 *    is built. No order is created. No Mailchimp contact is touched.
 *
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_fds_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/** Document order helper. True when $a occurs before $b, and both exist. */
function bhp_fds_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
}

/**
 * The four funnel pages, resolved by TEMPLATE FILE rather than by slug.
 *
 * Deliberate: `inc/collection-gallery.php`'s placement map is keyed by the
 * template WordPress actually chose, so resolving the same way here means the
 * suite and the feature agree about which page is which even if a slug moves.
 *
 * Each entry names the primary CTA that must survive and the secondary CTA
 * label that must be gone.
 */
$bhp_fds_funnels = array(
	'page-reluctant-reader-adventure-kit.php' => array(
		'label'          => 'kit / parent',
		'prefix'         => 'parent-landing',
		'primary_event'  => 'parent_hero_primary_cta_click',
		'removed_event'  => 'parent_hero_secondary_cta_click',
		'removed_label'  => 'Explore the collection',
	),
	'page-audience-gift-buyers.php'           => array(
		'label'          => 'gift buyers',
		'prefix'         => 'audience-landing',
		'primary_event'  => 'gift_hero_primary_cta_click',
		'removed_event'  => 'gift_hero_secondary_cta_click',
		'removed_label'  => 'Give the Complete Collection',
	),
	'page-audience-organizations.php'         => array(
		'label'          => 'organizations',
		'prefix'         => 'audience-landing',
		'primary_event'  => 'org_hero_primary_cta_click',
		'removed_event'  => 'org_hero_secondary_cta_click',
		'removed_label'  => 'Start a Partnership Conversation',
	),
	'page-audience-educators.php'             => array(
		'label'          => 'educators',
		'prefix'         => 'audience-landing',
		'primary_event'  => 'educator_hero_primary_cta_click',
		'removed_event'  => 'educator_hero_secondary_cta_click',
		'removed_label'  => 'Explore the Complete Collection',
	),
);

echo "\n=== 0. All four funnel pages resolve and render ===\n";

$bhp_fds_pages = array();
foreach ( get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'fields'         => 'ids',
	)
) as $id ) {
	$tpl = get_page_template_slug( $id );
	if ( is_string( $tpl ) && isset( $bhp_fds_funnels[ $tpl ] ) && ! isset( $bhp_fds_pages[ $tpl ] ) ) {
		$bhp_fds_pages[ $tpl ] = $id;
	}
}

foreach ( $bhp_fds_funnels as $tpl => $meta ) {
	bhp_fds_assert(
		isset( $bhp_fds_pages[ $tpl ] ),
		"0: a published page uses {$tpl} ({$meta['label']})",
		$failures
	);
}

if ( count( $bhp_fds_pages ) !== count( $bhp_fds_funnels ) ) {
	fwrite( STDERR, "Cannot continue without all four funnel pages.\n" );
	exit( 1 );
}

$bhp_fds_html = array();
foreach ( $bhp_fds_pages as $tpl => $id ) {
	$response = wp_remote_get(
		get_permalink( $id ),
		array(
			'timeout'   => 45,
			'sslverify' => false,
		)
	);
	$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	$html = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
	$bhp_fds_html[ $tpl ] = $html;

	bhp_fds_assert( 200 === $code, "0: {$bhp_fds_funnels[$tpl]['label']} returns HTTP 200 (got {$code})", $failures );
	bhp_fds_assert( strlen( $html ) > 20000, "0: {$bhp_fds_funnels[$tpl]['label']} renders a non-trivial document", $failures );
}

echo "\n=== 1. Exactly ONE collection gallery per page, and it is the universal set ===\n";

/*
 * Cardinality is asserted first because a duplicate instance is the one
 * defect that looks correct on screen while being wrong in the accessibility
 * tree and in analytics attribution — two elements sharing a DOM id, two
 * `aria-labelledby` targets, two lightboxes. `bhp_cx_render_collection_
 * gallery()`'s render-once guard is what prevents it; this asserts the guard
 * actually held after the call site moved.
 */
foreach ( $bhp_fds_funnels as $tpl => $meta ) {
	$html = $bhp_fds_html[ $tpl ];
	bhp_fds_assert(
		substr_count( $html, 'data-bhp-gallery-stage' ) === 1,
		"1: {$meta['label']} renders exactly one gallery stage",
		$failures
	);
	bhp_fds_assert(
		substr_count( $html, 'data-bhp-gallery-lightbox>' ) <= 1
			&& substr_count( $html, 'bhp-gallery__lightbox"' ) === 1,
		"1: {$meta['label']} renders exactly one lightbox",
		$failures
	);
}

/*
 * The 2026-08-09 parity ruling: every funnel page shows the same set as
 * /complete-collection/. Asserted against the registry rather than against a
 * hardcoded number, so adding an approved slide to the registry does not turn
 * this suite red for the wrong reason.
 */
if ( function_exists( 'bhp_collection_carousel_slugs' ) && function_exists( 'bhp_cx_collection_media_subset' ) ) {
	$expected = bhp_cx_collection_media_subset( bhp_collection_carousel_slugs() );
	$count    = isset( $expected['count'] ) ? (int) $expected['count'] : 0;
	bhp_fds_assert( $count > 1, "1: the universal carousel set resolves to more than one slide (got {$count})", $failures );
	foreach ( $bhp_fds_funnels as $tpl => $meta ) {
		bhp_fds_assert(
			substr_count( $bhp_fds_html[ $tpl ], 'bhp-gallery__slide' ) >= $count,
			"1: {$meta['label']} renders all {$count} universal slides",
			$failures
		);
	}
} else {
	bhp_fds_assert( false, '1: bhp_collection_carousel_slugs()/bhp_cx_collection_media_subset() are available', $failures );
}

echo "\n=== 2. Document order: heading -> paragraph -> primary CTA -> carousel -> checkmarks -> Best Value ===\n";

foreach ( $bhp_fds_funnels as $tpl => $meta ) {
	$html   = $bhp_fds_html[ $tpl ];
	$prefix = $meta['prefix'];

	bhp_fds_assert(
		bhp_fds_before( $html, '<h1', 'class="' . $prefix . '__lead"' ),
		"2: {$meta['label']} — heading precedes the paragraph",
		$failures
	);
	bhp_fds_assert(
		bhp_fds_before( $html, 'class="' . $prefix . '__lead"', $meta['primary_event'] ),
		"2: {$meta['label']} — paragraph precedes the primary CTA",
		$failures
	);
	bhp_fds_assert(
		bhp_fds_before( $html, $meta['primary_event'], 'class="' . $prefix . '-hero__gallery"' ),
		"2: {$meta['label']} — the primary CTA precedes the carousel",
		$failures
	);
	/*
	 * Slot 4 is "DIRECTLY under the primary CTA". The strongest thing a
	 * document can assert about "directly" is that nothing else from the
	 * ordered list intervenes — specifically that the carousel comes before
	 * the checkmark scanbar rather than after it.
	 */
	bhp_fds_assert(
		bhp_fds_before( $html, 'class="' . $prefix . '-hero__gallery"', 'class="' . $prefix . '-scanbar"' ),
		"2: {$meta['label']} — the carousel precedes the checkmark scanbar",
		$failures
	);
	bhp_fds_assert(
		bhp_fds_before( $html, 'class="' . $prefix . '-scanbar"', 'class="' . $prefix . '-pricecard"' ),
		"2: {$meta['label']} — the checkmark scanbar precedes the Best Value card",
		$failures
	);
	/*
	 * Slot 6, "raised… so they can buy easier on the page": the three-cover
	 * grid used to sit between the section heading and the price card and is
	 * now below it. This is the assertion that catches a revert.
	 */
	bhp_fds_assert(
		bhp_fds_before( $html, 'class="' . $prefix . '-pricecard"', 'class="' . $prefix . '-books"' ),
		"2: {$meta['label']} — the Best Value card precedes the three-book grid",
		$failures
	);
	/* The carousel is in the hero, not in the #collection section. */
	bhp_fds_assert(
		bhp_fds_before( $html, 'class="' . $prefix . '-hero__gallery"', 'id="collection"' )
			|| false === strpos( $html, 'id="collection"' ),
		"2: {$meta['label']} — the carousel renders above the #collection section",
		$failures
	);
}

echo "\n=== 3. The secondary hero CTA is GONE, and the primary is untouched ===\n";

foreach ( $bhp_fds_funnels as $tpl => $meta ) {
	$html = $bhp_fds_html[ $tpl ];

	bhp_fds_assert(
		false === strpos( $html, $meta['removed_event'] ),
		"3: {$meta['label']} — the secondary hero CTA event {$meta['removed_event']} is absent",
		$failures
	);
	bhp_fds_assert(
		strpos( $html, $meta['primary_event'] ) !== false,
		"3: {$meta['label']} — the primary hero CTA event {$meta['primary_event']} survives",
		$failures
	);
	/*
	 * The primary CTA still scrolls to the capture panel. `#free` plus the
	 * page's own `data-*-free-cta` hook are what the landing JS binds to; a
	 * release that removed a button must not have removed the hook.
	 */
	$hook = 'page-reluctant-reader-adventure-kit.php' === $tpl ? 'data-parent-free-cta' : 'data-audience-free-cta';
	bhp_fds_assert(
		strpos( $html, $hook ) !== false,
		"3: {$meta['label']} — the primary CTA keeps its {$hook} scroll hook",
		$failures
	);
	/*
	 * The hero must now carry exactly ONE button. Counted inside the hero CTA
	 * row only, so the page's other buttons are irrelevant to the assertion.
	 */
	$start = strpos( $html, 'class="' . $meta['prefix'] . '-hero__ctas"' );
	$row   = false === $start ? '' : substr( $html, $start, 900 );
	bhp_fds_assert(
		false !== $start && substr_count( $row, 'class="btn ' ) === 1,
		"3: {$meta['label']} — the hero CTA row contains exactly one button",
		$failures
	);
}

echo "\n=== 4. The static three-cover hero lockup is gone; the covers are NOT lost ===\n";

foreach ( $bhp_fds_funnels as $tpl => $meta ) {
	$html   = $bhp_fds_html[ $tpl ];
	$prefix = $meta['prefix'];

	bhp_fds_assert(
		false === strpos( $html, 'class="' . $prefix . '-hero__art"' ),
		"4: {$meta['label']} — the hero art column is removed",
		$failures
	);
	bhp_fds_assert(
		false === strpos( $html, 'class="' . $prefix . '-hero__covers"' ),
		"4: {$meta['label']} — the static three-cover lockup is removed",
		$failures
	);
	/*
	 * ⭐ THE COMPLEMENT, AND IT MATTERS AS MUCH AS THE REMOVAL. "Remove the
	 * duplicate" must not become "lose the covers". They are still the
	 * three-book grid inside #collection, and still every slide of the
	 * carousel.
	 */
	bhp_fds_assert(
		strpos( $html, 'class="' . $prefix . '-books"' ) !== false,
		"4: {$meta['label']} — the three-book grid still renders further down the page",
		$failures
	);
}

echo "\n=== 5. Funnel isolation, capture forms and Lead events are untouched ===\n";

/*
 * This release is a layout change. It must not have touched a capture surface
 * or crossed a funnel boundary. These assert the boundary held — they do NOT
 * claim the funnel works end-to-end, which is a browser-and-Mailchimp
 * question, not a document one.
 */
foreach ( $bhp_fds_funnels as $tpl => $meta ) {
	$html = $bhp_fds_html[ $tpl ];

	bhp_fds_assert(
		strpos( $html, 'id="free"' ) !== false,
		"5: {$meta['label']} — the #free capture section still renders",
		$failures
	);
	/*
	 * The teacher funnel's storage prefix belongs to /teachers/ only. Its
	 * appearance on an audience landing page would be a real isolation break.
	 * (`page-audience-educators.php` is the `educators` funnel, deliberately
	 * distinct from the `teachers` Mariana classroom-guide funnel.)
	 */
	bhp_fds_assert(
		false === strpos( $html, 'bhp_mariana_popup' ),
		"5: {$meta['label']} — the teacher-funnel storage prefix does not leak onto this page",
		$failures
	);
	/*
	 * The page's own landing-view analytics payload.
	 *
	 * ⚠ ASSERTED AGAINST THE GATE, NOT AGAINST PRESENCE, and the first draft
	 *   of this suite got that wrong. Every funnel template wraps its
	 *   `*_landing_view` push in
	 *   `BHP_Analytics_Config::should_render_analytics()`, which is FALSE on
	 *   both environments today by deliberate business decision — GTM is built
	 *   and unpublished, and `bhp_consent_decision_approved` is `false` by
	 *   design (see `docs/START_HERE.md` "Major blockers"). Asserting the
	 *   payload is present would fail on a correctly-configured site and would
	 *   have been a test asserting a defect.
	 *
	 *   So: present when the gate is open, absent when it is shut. Either way
	 *   this release must not have changed which.
	 */
	$analytics_on = class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::should_render_analytics();
	bhp_fds_assert(
		$analytics_on === ( strpos( $html, '_landing_view' ) !== false ),
		"5: {$meta['label']} — the landing-view payload matches the analytics gate ("
			. ( $analytics_on ? 'open' : 'shut' ) . ')',
		$failures
	);
}

echo "\n=== 6. The two below-the-fold placements are NOT converted ===\n";

/*
 * `front-page.php` and `page-books.php` keep `eager_first === false` and their
 * visible/hidden heading behaviour, because their galleries genuinely are
 * below the fold and eager-loading them would steal priority from their real
 * LCP. Asserted from the map, not from a page fetch, because that is where the
 * decision lives.
 */
if ( function_exists( 'bhp_cx_collection_gallery_map' ) ) {
	$map = bhp_cx_collection_gallery_map();

	foreach ( array( 'front-page.php', 'page-books.php' ) as $below ) {
		bhp_fds_assert(
			isset( $map[ $below ] ) && empty( $map[ $below ]['eager_first'] ),
			"6: {$below} keeps eager_first === false",
			$failures
		);
	}
	foreach ( array_keys( $bhp_fds_funnels ) as $funnel ) {
		bhp_fds_assert(
			isset( $map[ $funnel ] ) && ! empty( $map[ $funnel ]['eager_first'] ),
			"6: {$funnel} declares eager_first === true (its gallery is now the hero LCP)",
			$failures
		);
		bhp_fds_assert(
			isset( $map[ $funnel ]['heading_level'] ) && 'h2' === $map[ $funnel ]['heading_level'],
			"6: {$funnel} declares heading_level h2 (the nearest preceding heading is the <h1>)",
			$failures
		);
		/*
		 * The heading is HIDDEN, never deleted — the gallery region is
		 * `aria-labelledby` it, so removing the string would strip the
		 * region's accessible name.
		 */
		bhp_fds_assert(
			! empty( $map[ $funnel ]['heading_hidden'] )
				&& isset( $map[ $funnel ]['heading'] )
				&& '' !== trim( (string) $map[ $funnel ]['heading'] ),
			"6: {$funnel} hides its gallery heading but keeps the string for the accessible name",
			$failures
		);
	}

	/* `/teachers/` stays deliberately absent — a founder journey decision. */
	bhp_fds_assert(
		! isset( $map['page-teachers.php'] ),
		'6: /teachers/ is still deliberately absent from the placement map',
		$failures
	);
} else {
	bhp_fds_assert( false, '6: bhp_cx_collection_gallery_map() is available', $failures );
}

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
exit( 0 );
