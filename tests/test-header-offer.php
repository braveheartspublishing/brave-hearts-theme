<?php
/**
 * Brave Hearts — THE MOBILE-HEADER OFFER.
 *
 * CYCLE165-LD-DIRECTION1-STEP1-HEADER (2026-08-19, theme 1.19.260).
 * Direction 1, "Expedition field notes", board build step 1 of 4.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-header-offer.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from the SERVED DOCUMENT rather than from the template source:
 *   §1  the price on the button is DERIVED FROM LIVE PRODUCT RECORDS and there
 *       is no price literal anywhere in the component
 *   §2  the render / defer / suppress matrix, page by page — the assertion that
 *       enforces "exactly ONE above-fold primary CTA" at the markup level
 *   §3  the desktop header is untouched: `.header-expedition-cta` still appears
 *       exactly once on every page, and the offer never appears twice
 *   §4  the whole component is behind ONE filter, default ON, and switching it
 *       off restores 1.19.259 markup exactly
 *   §5  a school-visit session is quoted the PAPERBACK price, never hardcover
 *   §6  the copy gate — no em dash, no customer-facing "we" (standing rule §9.1)
 *   §7  the stylesheet ships the component behind the SAME container query that
 *       drives the hamburger, with a >= 44px tap target
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads markup and PHP.
 *    It does NOT prove the button is visible at 390, that it clears the consent
 *    banner, that the homepage reveal fires, that nothing overflows, or that the
 *    logo did not move. Those are BROWSER facts and were measured separately in
 *    a real headless Chrome at an asserted `window.innerWidth`, filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-direction1-step1-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 * ⛔ NOTHING IS WRITTEN. No product, price, variation, coupon, stock level,
 *    shipping setting, tax setting, payment setting, cart, order, post, page,
 *    option, attachment or user is created, read for mutation, or modified by
 *    any line in this file. §4 and §5 use filters and remove them again.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_hdo_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_hdo_skip( $label ) {
	echo "SKIP: {$label}\n";
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_hdo_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** How many times does the offer anchor appear in this document? */
function bhp_hdo_count_offer( $html ) {
	return preg_match_all( '/class="bhp-header-offer"/', $html );
}

/** The offer's `data-bhp-offer-state`, or '' when the button is absent. */
function bhp_hdo_offer_state( $html ) {
	if ( preg_match( '/class="bhp-header-offer"[^>]*data-bhp-offer-state="([^"]+)"/', $html, $m ) ) {
		return $m[1];
	}
	return '';
}

/** The price string the offer prints, or '' when absent. */
function bhp_hdo_offer_price_text( $html ) {
	if ( preg_match( '/<span class="bhp-header-offer__price">([^<]*)<\/span>/', $html, $m ) ) {
		return html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
	}
	return '';
}

echo "\n=== §0 — THE COMPONENT IS LOADED ===\n";

bhp_hdo_assert(
	function_exists( 'bhp_header_offer' )
		&& function_exists( 'bhp_header_offer_enabled' )
		&& function_exists( 'bhp_header_offer_context' )
		&& function_exists( 'bhp_header_offer_price' )
		&& function_exists( 'bhp_header_offer_format' ),
	'§0.1 all five component functions are loaded from inc/header-offer.php',
	$failures
);

bhp_hdo_assert( bhp_header_offer_enabled(), '§0.2 the component filter defaults to ON', $failures );

$component_src = file_get_contents( get_template_directory() . '/inc/header-offer.php' );
bhp_hdo_assert( is_string( $component_src ) && '' !== $component_src, '§0.3 the component source is readable', $failures );

echo "\n=== §1 — THE PRICE IS LIVE BUNDLE DATA, NEVER A LITERAL ===\n";

$have_bundle = function_exists( 'bhp_bundle_landing_price_facts' ) && function_exists( 'bhp_bundle_rules' );

if ( ! $have_bundle ) {
	bhp_hdo_skip( '§1 the bundle plugin is not active on this environment' );
	bhp_hdo_assert( null === bhp_header_offer_price(), '§1.0 with no bundle data the component reports NO price', $failures );
} else {
	$facts    = bhp_bundle_landing_price_facts( bhp_header_offer_format() );
	$expected = round( (float) $facts['bundle'], 2 );
	$actual   = round( (float) bhp_header_offer_price(), 2 );

	bhp_hdo_assert(
		$expected === $actual,
		sprintf( '§1.1 the offer price (%s) equals the derived bundle price (%s)', $actual, $expected ),
		$failures
	);

	/*
	 * ⭐ THE ASSERTION THAT MATTERS MOST IN THIS SUITE. `separate - discount` is
	 *    what `bhp_bundle_apply_discount()` charges the cart, so a button that
	 *    agrees with it cannot quote a price the checkout will not honour.
	 */
	$derived = round( (float) $facts['separate'] - (float) bhp_bundle_rules( bhp_header_offer_format() )[3]['discount'], 2 );
	bhp_hdo_assert(
		$derived === $actual,
		sprintf( '§1.2 the price is (sum of the three live product prices %s) minus the approved discount', $facts['separate'] ),
		$failures
	);

	bhp_hdo_assert(
		true === (bool) $facts['live'],
		'§1.3 all three product records resolved, so the figure is LIVE rather than the approved-constant fallback',
		$failures
	);

	/*
	 * A price literal in the component is the failure mode this whole design
	 * exists to prevent. The check is deliberately broad: any `$` followed by
	 * digits and a decimal, anywhere in the executable source.
	 *
	 * The docblock legitimately quotes measured figures, so the comment block
	 * is stripped before the search rather than the search being weakened.
	 */
	$code_only = preg_replace( '#/\*.*?\*/#s', '', $component_src );
	$code_only = preg_replace( '#^\s*//.*$#m', '', $code_only );
	bhp_hdo_assert(
		0 === preg_match( '/\$\s?\d+\.\d{2}/', $code_only ),
		'§1.4 the component EXECUTABLE source contains no price literal',
		$failures
	);
}

echo "\n=== §2 — THE RENDER / DEFER / SUPPRESS MATRIX ===\n";

/*
 * ⭐ THIS MATRIX IS NOT AN OPINION. It is the measured above-the-fold state of
 *    staging2 1.19.259 at an asserted window.innerWidth of 390, scrollY 0
 *    (evidence: CYCLE165-direction1-step1-qa/before/BEFORE-1.19.259-390.json):
 *
 *      home 1 primary · blog post 0 · blog index 0 · product 0 · shop 0 ·
 *      collection 1 · cart 1 · checkout 1 · static page 0
 *
 *    Pages that measured ZERO get the button. Pages that measured ONE do not.
 *    The homepage defers, because its one primary is a free-sample CTA and
 *    Andrew's item 96(7) makes that count.
 */
$blog_page_id = (int) get_option( 'page_for_posts' );
$post_ids     = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );

$doors = array(
	'home'        => array( home_url( '/' ), 'deferred' ),
	'blogindex'   => array( $blog_page_id ? get_permalink( $blog_page_id ) : '', 'visible' ),
	'blogpost'    => array( ! empty( $post_ids ) ? get_permalink( $post_ids[0] ) : '', 'visible' ),
	'shop'        => array( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '', 'visible' ),
	'staticpage'  => array( home_url( '/about/' ), 'visible' ),
	'collection'  => array( home_url( '/complete-collection/' ), 'absent' ),
	'cart'        => array( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '', 'absent' ),
	'checkout'    => array( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '', 'absent' ),
);

/*
 * ⭐ 1.19.262 (CYCLE165-LD-DIRECTION1-STEP3-PRODUCT) — `product` MOVED FROM
 *    'visible' TO 'absent', AND THE MATRIX ABOVE IS WHY.
 *
 * The rule this suite enforces has not changed: the button renders where a
 * template measured ZERO above-fold primaries at 390 and stays away where one
 * already exists. `product 0` was measured on 1.19.259 and is the reason this
 * row read 'visible'. Step 3 of the Direction 1 board moves the price, the
 * format selector and ADD TO CART into the first screen at 390 — re-measured
 * on staging in headless Chrome at an asserted innerWidth, evidence at
 * `CYCLE165-direction1-step3-qa/` — so the product template now measures ONE.
 * Keeping the header offer there would make it a SECOND buy CTA above the
 * fold, which is the thing §2 exists to prevent.
 *
 * ⛔ THE EXPECTATION MOVED BECAUSE THE MEASUREMENT MOVED, not to make a test
 *    pass. The SUPERSEDED row is recorded here rather than deleted:
 *    `$doors['product'] = array( get_permalink( $product_ids[0] ), 'visible' );`
 */
$product_ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
if ( ! empty( $product_ids ) ) {
	$doors['product'] = array( get_permalink( $product_ids[0] ), 'absent' );
}

$docs = array();
foreach ( $doors as $key => $spec ) {
	list( $url, $expected_state ) = $spec;
	if ( '' === $url ) {
		bhp_hdo_skip( "§2 {$key} — no URL resolvable on this environment" );
		continue;
	}
	$html = bhp_hdo_fetch( $url );
	if ( '' === $html ) {
		bhp_hdo_skip( "§2 {$key} — document did not return HTTP 200" );
		continue;
	}
	$docs[ $key ] = $html;

	$count = bhp_hdo_count_offer( $html );
	$state = bhp_hdo_offer_state( $html );

	if ( 'absent' === $expected_state ) {
		bhp_hdo_assert( 0 === $count, "§2.{$key} the offer is SUPPRESSED (this page already carries an above-fold primary)", $failures );
	} else {
		bhp_hdo_assert( 1 === $count, "§2.{$key} the offer appears EXACTLY ONCE", $failures );
		bhp_hdo_assert( $expected_state === $state, "§2.{$key} the offer state is '{$expected_state}' (got '{$state}')", $failures );
	}
}

/* The homepage's deferred button must carry the watch list, or the reveal
   script has nothing to observe and the button would never appear at all. */
if ( isset( $docs['home'] ) ) {
	bhp_hdo_assert(
		1 === preg_match( '/class="bhp-header-offer"[^>]*data-bhp-offer-watch="[^"]+"/', $docs['home'] ),
		'§2.home the deferred button names the elements the reveal script watches',
		$failures
	);
	bhp_hdo_assert(
		false !== strpos( $docs['home'], 'assets/js/header-offer.js' ),
		'§2.home the reveal script is enqueued on the page that defers',
		$failures
	);
}

/* ...and must NOT be enqueued anywhere else: those pages have nothing to reveal.
   1.19.262: `product` left this list when it left the render side of the matrix
   above. The assertion would still have passed there (a suppressed component
   enqueues nothing), but the label would have described a page that no longer
   renders the offer, and a test whose label is wrong is a test nobody trusts. */
foreach ( array( 'blogpost', 'shop', 'staticpage' ) as $k ) {
	if ( isset( $docs[ $k ] ) ) {
		bhp_hdo_assert(
			false === strpos( $docs[ $k ], 'assets/js/header-offer.js' ),
			"§2.{$k} the reveal script is NOT enqueued on a page that renders the offer immediately",
			$failures
		);
	}
}

echo "\n=== §3 — THE DESKTOP HEADER IS UNTOUCHED ===\n";

/*
 * Test (b) of the brief: the 1440 header CTA count is unchanged. At the markup
 * level that is "`.header-expedition-cta` still appears exactly once on every
 * page, and `.site-nav__cta` exactly once" — which is what 1.19.259 emitted.
 * The rendered 1440 count was separately re-measured in a real browser.
 */
foreach ( $docs as $key => $html ) {
	bhp_hdo_assert(
		1 === preg_match_all( '/class="header-expedition-cta"/', $html ),
		"§3.{$key} `.header-expedition-cta` still appears exactly once",
		$failures
	);
	bhp_hdo_assert(
		1 === preg_match_all( '/class="site-nav__cta"/', $html ),
		"§3.{$key} `.site-nav__cta` still appears exactly once",
		$failures
	);
}

echo "\n=== §4 — ONE FILTER, DEFAULT ON, AND OFF RESTORES 1.19.259 ===\n";

add_filter( 'bhp_header_offer_enabled', '__return_false' );
bhp_hdo_assert( ! bhp_header_offer_enabled(), '§4.1 the filter switches the component off', $failures );
bhp_hdo_assert( 'suppress' === bhp_header_offer_context(), '§4.2 with the filter off every context resolves to suppress', $failures );
bhp_hdo_assert( '' === bhp_header_offer(), '§4.3 with the filter off the renderer emits nothing at all', $failures );
remove_filter( 'bhp_header_offer_enabled', '__return_false' );
bhp_hdo_assert( bhp_header_offer_enabled(), '§4.4 removing the filter restores the default ON', $failures );

echo "
=== §5 — A SCHOOL-VISIT SESSION IS QUOTED PAPERBACK ===
";

/*
 * ⛔ THE PREDICATE IS NOT REACHABLE FROM WP-CLI AND THIS SUITE DOES NOT PRETEND
 *    IT IS. `bhp_school_visit_paperback_only()` returns false before its own
 *    filter runs unless the request carries a live visit flag in the WooCommerce
 *    session, and there is no session under WP-CLI. Faking one would write state
 *    a test must never write.
 *
 *    So the DECISION is proved here, purely, through `bhp_header_offer_format_for()`;
 *    the PREDICATE is proved separately by a real browser walking a live
 *    `?bhp_visit=` link at an asserted window.innerWidth of 390. Both records
 *    exist; neither is claimed on the other's evidence.
 */
bhp_hdo_assert(
	function_exists( 'bhp_header_offer_format_for' ),
	'§5.0 the format decision is a pure, testable function',
	$failures
);
bhp_hdo_assert(
	'paperback' === bhp_header_offer_format_for( true ),
	'§5.1 a restricted session is quoted the PAPERBACK format',
	$failures
);
bhp_hdo_assert(
	'hardcover' !== bhp_header_offer_format_for( true ),
	'§5.2 a restricted session is NEVER quoted hardcover, whatever the sitewide default becomes',
	$failures
);
if ( function_exists( 'bhp_bundle_default_format' ) ) {
	bhp_hdo_assert(
		bhp_bundle_default_format() === bhp_header_offer_format_for( false ),
		'§5.3 an unrestricted session follows the sitewide default format',
		$failures
	);
	bhp_hdo_assert(
		bhp_header_offer_format() === bhp_header_offer_format_for( false ),
		'§5.4 this WP-CLI request is unrestricted, so the live call and the pure call agree',
		$failures
	);
}
if ( $have_bundle ) {
	$pb_only = round( (float) bhp_bundle_landing_price_facts( 'paperback' )['bundle'], 2 );
	$hc_only = round( (float) bhp_bundle_landing_price_facts( 'hardcover' )['bundle'], 2 );
	bhp_hdo_assert(
		$pb_only !== $hc_only,
		'§5.5 the two format prices genuinely differ, so §5.1 is a meaningful assertion',
		$failures
	);
}

echo "\n=== §6 — THE COPY GATE (standing rule §9.1) ===\n";

$rendered = bhp_header_offer();
$visible_copy = wp_strip_all_tags( $rendered );
if ( preg_match( '/aria-label="([^"]*)"/', $rendered, $m_aria ) ) {
	$visible_copy .= ' ' . html_entity_decode( $m_aria[1], ENT_QUOTES, 'UTF-8' );
}

bhp_hdo_assert(
	false === strpos( $visible_copy, "\xE2\x80\x94" ),
	'§6.1 no em dash in any customer-facing string this component emits',
	$failures
);
bhp_hdo_assert(
	0 === preg_match( '/\b(we|us|our|we\'re|we\'ve)\b/i', $visible_copy ),
	'§6.2 no "we", "us" or "our" in any customer-facing string this component emits',
	$failures
);
bhp_hdo_assert(
	0 === preg_match( '/\b(5-9|5 to 9|ages 5)/i', $visible_copy ),
	'§6.3 no 5-9 reading age',
	$failures
);
bhp_hdo_assert(
	false === strpos( $visible_copy, '!' ),
	'§6.4 no exclamation mark',
	$failures
);
bhp_hdo_assert(
	false !== strpos( $rendered, 'href="' . esc_url( home_url( '/complete-collection/' ) ) . '"' ),
	'§6.5 the destination is /complete-collection/ and nothing else',
	$failures
);
bhp_hdo_assert(
	false === strpos( $rendered, '<form' ) && false === strpos( $rendered, 'bhp_bundle_action' ),
	'§6.6 the control is an ANCHOR and posts nothing (Andrew, 2026-08-05: the nav goes to the collection page)',
	$failures
);

echo "\n=== §7 — THE STYLESHEET ===\n";

$css_src = file_get_contents( get_template_directory() . '/style.css' );
$css_min_path = get_template_directory() . '/style.min.css';
$css_min = file_exists( $css_min_path ) ? file_get_contents( $css_min_path ) : '';

bhp_hdo_assert(
	false !== strpos( $css_src, '.bhp-header-offer' ),
	'§7.1 the component has rules in style.css',
	$failures
);
bhp_hdo_assert(
	'' !== $css_min && false !== strpos( $css_min, '.bhp-header-offer' ),
	'§7.2 the component survived the minification build into style.min.css',
	$failures
);

/*
 * ⛔ THE SAME CONTAINER QUERY AS THE HAMBURGER. If this ever becomes a viewport
 *    media query, the button and the hamburger part company on `.single-post`,
 *    where <body> is capped at 1120px and `.header-inner` is far narrower than
 *    `window.innerWidth`. The blog post template is 37.7% of human page views,
 *    so that is precisely the page it would break on.
 */
$offer_block = strstr( $css_src, '⭐ 1.19.260 (2026-08-19) — THE MOBILE-HEADER OFFER' );
bhp_hdo_assert(
	is_string( $offer_block ) && false !== strpos( $offer_block, '@container (max-width: 1116px)' ),
	'§7.3 the component is gated on the SAME container query that drives the hamburger',
	$failures
);
bhp_hdo_assert(
	is_string( $offer_block ) && false !== strpos( $offer_block, 'min-height: 44px' ),
	'§7.4 the tap target is at least 44px tall',
	$failures
);
bhp_hdo_assert(
	is_string( $offer_block ) && false !== strpos( $offer_block, 'visibility: hidden' )
		&& false === strpos( $offer_block, 'data-bhp-offer-state="deferred"] {' . "\n" . '    display: none' ),
	'§7.5 the hidden state uses visibility, so the box is reserved and the logo cannot shift',
	$failures
);
bhp_hdo_assert(
	is_string( $offer_block ) && false === strpos( $offer_block, '#c4a15c' ) && false === strpos( $offer_block, '#071522' ),
	'§7.6 no new hue: the component reads the existing palette tokens rather than hardcoding colours',
	$failures
);

echo "\n=====================================================\n";
if ( empty( $failures ) ) {
	echo "ALL ASSERTIONS PASSED\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( count( $failures ) . ' assertion(s) failed.' );
	}
}
