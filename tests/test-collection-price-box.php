<?php
/**
 * Brave Hearts — THE COLLECTION PRICE BOX (theme 1.19.226 / bundle 1.8.41).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-collection-price-box.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE160-LD-COLLECTION-PRICE-BOX`
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-14, as relayed in the build brief (⛔ RELAYED
 * through `chief-of-staff` (Gandalf); NOT witnessed first-hand by the agent
 * that wrote this file):
 *
 *   "the collection price display goes INSIDE the existing FREE box (the
 *    free-activity-book-with-collection offer box), bottom-right region,
 *    ABOVE THE FOLD on desktop AND mobile; it shows the paperback
 *    collection price with a true strikethrough of the sum-of-singles; and
 *    clicking the box's CTA must land the visitor on the PAPERBACK
 *    purchase path (paperback preselected), not hardcover."
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It reads a RENDERED DOCUMENT and the live pricing functions. It therefore
 * proves presence, absence, cardinality, document order and ARITHMETIC.
 *
 * It CANNOT prove:
 *   - that anything is above the fold. That is geometry, it needs a real
 *     browser with `window.innerWidth` asserted, and it is recorded in the
 *     QA evidence for this cycle instead — NOT here, and NOT claimed here;
 *   - what the cart actually charges. That is the Store API and a real
 *     Blocks cart;
 *   - that the WPConsent banner does not cover the block on a first visit.
 *     The banner lives in an OPEN SHADOW ROOT on a 0x0 host and a light-DOM
 *     query misses it entirely.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE ONE THING THIS SUITE EXISTS FOR ABOVE ALL OTHERS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * A struck-through former price is an FTC-class claim. This suite asserts
 * that the struck figure equals the REAL sum of the three live WooCommerce
 * product prices for that format, and that the collection figure equals
 * that sum minus the live bundle discount — so the comparison is TRUE and
 * not merely present, and so a future re-price of one single title cannot
 * silently turn a true saving into an invented one.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cpb_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/** Document order helper. Returns true when $a occurs before $b. */
function bhp_cpb_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
}

echo "\n=== 0. The plugin exposes the page-scoped functions ===\n";

foreach ( array(
	'bhp_bundle_landing_default_format',
	'bhp_bundle_landing_format_order',
	'bhp_bundle_landing_price_facts',
	'bhp_bundle_render_landing_coldopen_price',
) as $cpb_fn ) {
	bhp_cpb_assert( function_exists( $cpb_fn ), "0: {$cpb_fn}() exists", $failures );
}
if ( ! function_exists( 'bhp_bundle_landing_price_facts' ) ) {
	fwrite( STDERR, "Cannot continue: the bundle plugin is not the 1.8.41 build.\n" );
	exit( 1 );
}

echo "\n=== 1. The numbers are LIVE, and the strike-through is TRUE ===\n";

foreach ( array( 'paperback', 'hardcover' ) as $cpb_fmt ) {
	$facts = bhp_bundle_landing_price_facts( $cpb_fmt );

	/*
	 * ⛔ THE SUM IS RE-DERIVED HERE FROM WooCommerce INDEPENDENTLY of the
	 *    function under test. Asserting `$facts['separate']` against itself
	 *    would prove nothing.
	 */
	$catalog = bhp_bundle_catalog();
	$sum     = 0.0;
	$n       = 0;
	foreach ( $catalog[ $cpb_fmt ] as $info ) {
		$p = wc_get_product( (int) $info['product_id'] );
		if ( $p && (float) $p->get_price() > 0 ) {
			$sum += (float) $p->get_price();
			$n++;
		}
	}
	bhp_cpb_assert( 3 === $n, "1: all three {$cpb_fmt} products resolve to a live price", $failures );
	bhp_cpb_assert(
		true === $facts['live'],
		"1: {$cpb_fmt} price facts are read LIVE, not from the fallback constant",
		$failures
	);
	bhp_cpb_assert(
		abs( $facts['separate'] - $sum ) < 0.005,
		sprintf( '1: %s strike-through anchor is the real sum of singles (%.2f)', $cpb_fmt, $sum ),
		$failures
	);
	$discount = (float) bhp_bundle_rules( $cpb_fmt )[3]['discount'];
	bhp_cpb_assert(
		abs( $facts['bundle'] - ( $sum - $discount ) ) < 0.005,
		sprintf( '1: %s collection price is sum-minus-discount (%.2f - %.2f = %.2f)', $cpb_fmt, $sum, $discount, $sum - $discount ),
		$failures
	);
	bhp_cpb_assert(
		abs( $facts['save'] - $discount ) < 0.005,
		sprintf( '1: %s saving equals the live bundle discount (%.2f)', $cpb_fmt, $discount ),
		$failures
	);
	/*
	 * ⛔ THE ANCHOR MUST BE HIGHER THAN THE PRICE, OR THE STRIKE IS A LIE.
	 *    A zero or negative saving would render "$31.99  $31.99  Save $0.00",
	 *    which is a former-price claim with nothing behind it.
	 */
	bhp_cpb_assert(
		$facts['separate'] > $facts['bundle'] && $facts['save'] > 0,
		"1: {$cpb_fmt} the struck anchor is genuinely higher than the collection price",
		$failures
	);
}

echo "\n=== 2. Paperback is preselected on THIS page, and only this page ===\n";

bhp_cpb_assert(
	'paperback' === bhp_bundle_landing_default_format(),
	'2: the collection landing page opens on PAPERBACK (Andrew, 2026-08-14)',
	$failures
);
bhp_cpb_assert(
	array( 'paperback', 'hardcover' ) === bhp_bundle_landing_format_order(),
	'2: the landing format order leads with paperback',
	$failures
);
/*
 * ⛔ THE GUARD THAT MATTERS MORE THAN THE OVERRIDE ITSELF. Six surfaces
 *    outside this page read `bhp_bundle_default_format()`. If a future edit
 *    "simplifies" the override by changing the global, every product page
 *    and collection card on the site silently re-defaults, which is a
 *    commercial change nobody approved.
 */
bhp_cpb_assert(
	'hardcover' === bhp_bundle_default_format(),
	'2: the SITEWIDE default format is STILL hardcover — the override is page-scoped',
	$failures
);

echo "\n=== 3. The rendered document ===\n";

$page = null;
foreach ( get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'fields'         => 'ids',
	)
) as $id ) {
	$content = get_post_field( 'post_content', $id );
	if ( is_string( $content ) && has_shortcode( $content, 'bhp_complete_series_landing' ) ) {
		$page = $id;
		break;
	}
}
bhp_cpb_assert( (bool) $page, '3: a published page carrying [bhp_complete_series_landing] exists', $failures );
if ( ! $page ) {
	fwrite( STDERR, "Cannot continue without the landing page.\n" );
	exit( 1 );
}

$response = wp_remote_get( get_permalink( $page ), array( 'timeout' => 30, 'sslverify' => false ) );
$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
bhp_cpb_assert( 200 === $code, "3: the page returns HTTP 200 (got {$code})", $failures );
if ( 200 !== $code ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

bhp_cpb_assert(
	substr_count( $html, 'class="bhp-landing-coldopen__price"' ) === 2,
	'3: exactly two price blocks render — one per format',
	$failures
);

/* The block is INSIDE the FREE box, and it is the LAST thing in it. */
$cpb_cold = '';
if ( preg_match( '/<div class="bhp-landing-coldopen">(.*?)<\/div>/su', $html, $m ) ) {
	$cpb_cold = $m[1];
}
bhp_cpb_assert(
	'' !== $cpb_cold && substr_count( $cpb_cold, 'class="bhp-landing-coldopen__price"' ) === 2,
	'3: both price blocks are INSIDE the cold-open FREE box',
	$failures
);
bhp_cpb_assert(
	bhp_cpb_before( $cpb_cold, 'bhp-landing-coldopen__free', 'bhp-landing-coldopen__price' ),
	'3: the price block sits at the BOTTOM of the box, after the FREE bullets',
	$failures
);
bhp_cpb_assert(
	bhp_cpb_before( $html, 'bhp-landing-coldopen__price', 'data-bhp-landing-main-cta' ),
	'3: the price block renders ABOVE the primary CTA in document order',
	$failures
);

/* Exactly one is visible on load, and it is the paperback one. */
bhp_cpb_assert(
	preg_match( '/<p class="bhp-landing-coldopen__price" data-bhp-format-panel="paperback" >/', $html ) === 1,
	'3: the PAPERBACK price block is the one rendered without `hidden`',
	$failures
);
bhp_cpb_assert(
	strpos( $html, '<p class="bhp-landing-coldopen__price" data-bhp-format-panel="hardcover" hidden>' ) !== false,
	'3: the HARDCOVER price block is rendered but hidden',
	$failures
);

/* The struck figure and the collection figure, per format, from live data. */
foreach ( array( 'paperback', 'hardcover' ) as $cpb_fmt ) {
	$facts = bhp_bundle_landing_price_facts( $cpb_fmt );
	$sep   = '$' . number_format( $facts['separate'], 2 );
	$bun   = '$' . number_format( $facts['bundle'], 2 );
	$sav   = '$' . number_format( $facts['save'], 2 );

	bhp_cpb_assert(
		strpos( $html, '<s class="bhp-landing-coldopen__price-was" aria-hidden="true">' . $sep . '</s>' ) !== false,
		"3: {$cpb_fmt} — the strike is a real <s> carrying {$sep}",
		$failures
	);
	bhp_cpb_assert(
		strpos( $html, '<span class="bhp-landing-coldopen__price-now" aria-hidden="true">' . $bun . '</span>' ) !== false,
		"3: {$cpb_fmt} — the collection price {$bun} is printed",
		$failures
	);
	bhp_cpb_assert(
		strpos( $html, '<span class="bhp-landing-coldopen__price-save" aria-hidden="true">Save ' . $sav . '</span>' ) !== false,
		"3: {$cpb_fmt} — the saving \"Save {$sav}\" is printed",
		$failures
	);
	/* The visually-hidden sentence is what a screen reader actually gets. */
	bhp_cpb_assert(
		strpos( $html, 'bought separately cost ' . $sep ) !== false
		&& strpos( $html, 'The Complete Collection is ' . $bun ) !== false
		&& strpos( $html, 'you save ' . $sav . ' buying them together' ) !== false,
		"3: {$cpb_fmt} — the accessible sentence states the full comparison",
		$failures
	);
}

echo "\n=== 4. The quiet alternate-format control ===\n";

bhp_cpb_assert(
	substr_count( $html, 'data-bhp-format-link="hardcover"' ) === 1
	&& substr_count( $html, 'data-bhp-format-link="paperback"' ) === 1,
	'4: exactly one alternate-format control per price block',
	$failures
);
/*
 * ⚠ CORRECTED after this suite's first run on staging, and recorded rather
 *   than quietly patched. The first version asserted the literal
 *   `>Hardcover available`, which FAILED on a correct build: the label sits
 *   on its own indented line inside the button, so the character before it
 *   is a tab, not `>`. An assertion that encodes template whitespace tests
 *   the indenter, not the copy.
 */
bhp_cpb_assert(
	preg_match( '/data-bhp-format-link="hardcover"[^>]*>\s*Hardcover available\s*<\/button>/su', $html ) === 1
	&& preg_match( '/data-bhp-format-link="paperback"[^>]*>\s*Paperback available\s*<\/button>/su', $html ) === 1,
	'4: the alternate-format line states availability',
	$failures
);
/*
 * ⛔ IT MUST NOT LEAD WITH A PRICE. Andrew, 2026-08-14: "a quieter
 *    'hardcover available' line (do NOT lead with $48.99)". The assertion is
 *    built from the live hardcover figure, not from the literal 48.99, so it
 *    still holds if the collection is ever re-priced.
 */
$cpb_hc = '$' . number_format( bhp_bundle_landing_price_facts( 'hardcover' )['bundle'], 2 );
if ( preg_match( '/<span class="bhp-landing-coldopen__price-alt">(.*?)<\/span>\s*<\/p>/su', $html, $m ) ) {
	bhp_cpb_assert(
		strpos( $m[1], $cpb_hc ) === false && strpos( $m[1], '$' ) === false,
		"4: the alternate-format line carries no price (no {$cpb_hc}, no dollar sign)",
		$failures
	);
} else {
	bhp_cpb_assert( false, '4: the alternate-format line could not be isolated', $failures );
}
/*
 * ⛔ IT MUST NOT BE A `data-bhp-format-btn`. That attribute enrols an element
 *    in the two-radio arrow-key loop AND is what `syncStickyBar()` queries for
 *    the sticky bar's price; a third one, rendered earlier in the document,
 *    would break the sticky bar silently.
 */
bhp_cpb_assert(
	substr_count( $html, 'data-bhp-format-btn=' ) === 2,
	'4: still exactly two `data-bhp-format-btn` elements (the pills), no more',
	$failures
);

echo "\n=== 5. Nothing is hardcoded, and nothing is duplicated ===\n";

/*
 * ⛔ NO PRICE LITERAL IN THE RENDER PATH. This reads the SOURCE of the two
 *    functions that print the money and asserts none of the four figures
 *    appears as a literal. It is the guard that keeps the strike honest when
 *    somebody re-prices a book.
 */
$cpb_src = '';
foreach ( array( 'bhp_bundle_render_landing_coldopen_price', 'bhp_bundle_landing_price_facts' ) as $cpb_fn ) {
	$r = new ReflectionFunction( $cpb_fn );
	$cpb_lines = file( $r->getFileName() );
	$cpb_src  .= implode( '', array_slice( $cpb_lines, $r->getStartLine() - 1, $r->getEndLine() - $r->getStartLine() + 1 ) );
}
foreach ( array( '31.99', '35.97', '48.99', '53.97' ) as $cpb_lit ) {
	bhp_cpb_assert(
		strpos( $cpb_src, $cpb_lit ) === false,
		"5: no {$cpb_lit} literal in the price-display render path",
		$failures
	);
}

/* The panel below the button no longer restates any of it. */
bhp_cpb_assert(
	strpos( $html, 'bhp-landing-panel__price-row--main' ) === false,
	'5: the purchase panel no longer carries the duplicated price row',
	$failures
);
bhp_cpb_assert(
	strpos( $html, 'bhp-landing-panel__savings-badge' ) === false,
	'5: the purchase panel no longer carries the duplicated savings badge',
	$failures
);
bhp_cpb_assert(
	strpos( $html, 'bhp-landing-panel__ages' ) !== false,
	'5: the ages line stayed exactly where it was',
	$failures
);

echo "\n=== 6. The purchase path really is paperback ===\n";

/*
 * The visible pricing panel and the visible final-CTA panel must both post
 * `complete_paperback_smart`. The hidden hardcover panels still post their
 * own action — the toggle has to keep working, and a page that could only
 * ever buy paperback would be a different defect.
 */
bhp_cpb_assert(
	preg_match_all( '/name="bhp_bundle_action" value="complete_paperback_smart"/', $html ) >= 2,
	'6: the paperback purchase forms are on the page',
	$failures
);
bhp_cpb_assert(
	preg_match_all( '/name="bhp_bundle_action" value="complete_hardcover_smart"/', $html ) >= 2,
	'6: the hardcover purchase forms are still on the page (the toggle still works)',
	$failures
);
/* The paperback pricing panel is the one rendered WITHOUT `hidden`. */
bhp_cpb_assert(
	preg_match( '/data-bhp-format-panel="paperback" >\s*<h2 class="bhp-landing-panel__title/su', $html ) === 1,
	'6: the PAPERBACK pricing panel is the visible one on load',
	$failures
);
bhp_cpb_assert(
	strpos( $html, 'data-bhp-format-btn="paperback"' ) !== false
	&& preg_match( '/aria-checked="true"\s*class="bhp-landing-format-btn is-selected"\s*data-bhp-format-btn="paperback"/su', $html ) === 1,
	'6: the PAPERBACK pill is the checked, selected one',
	$failures
);
bhp_cpb_assert(
	bhp_cpb_before( $html, 'data-bhp-format-btn="paperback"', 'data-bhp-format-btn="hardcover"' ),
	'6: the paperback pill leads in reading and tab order',
	$failures
);

echo "\n=== 7. Nothing on the never-invent list, and no schema change ===\n";

/*
 * ⛔ THE PRICE DISPLAY IS DISPLAY-LAYER ONLY. It must not have introduced any
 *    Product/Offer JSON-LD onto this page — the collection page has never
 *    emitted one, and the offers[] rebuild of 1.19.222 lives on the product
 *    pages. A price appearing in this page's schema would be a new offer
 *    claim nobody reviewed.
 */
$cpb_schema = '';
if ( preg_match_all( '/<script[^>]*application\/ld\+json[^>]*>(.*?)<\/script>/su', $html, $mm ) ) {
	$cpb_schema = implode( "\n", $mm[1] );
}
bhp_cpb_assert(
	'' !== $cpb_schema,
	'7: the page still emits its JSON-LD block',
	$failures
);
bhp_cpb_assert(
	strpos( $cpb_schema, '"@type":"Offer"' ) === false
	&& strpos( $cpb_schema, '"@type": "Offer"' ) === false
	&& strpos( $cpb_schema, '"price"' ) === false,
	'7: the collection page emits NO Offer and NO price in JSON-LD (unchanged)',
	$failures
);
bhp_cpb_assert(
	strpos( $cpb_schema, 'aggregateRating' ) === false
	&& strpos( $cpb_schema, '"review"' ) === false,
	'7: no aggregateRating and no review schema (never-invent rule)',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
