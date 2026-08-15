<?php
/**
 * Brave Hearts — THE ADVENTURE KIT THANK-YOU UPSELL (theme 1.19.228).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-kit-thankyou-upsell.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE161-LD-TYP-AND-GUARANTEE`, build 1
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The parent Adventure Kit thank-you page's Complete Collection module used
 * to link out with no price, no anchor and no reason to click. It now
 * carries the collection page's own price treatment — a TRUE strikethrough
 * of the sum-of-singles, the collection price, the saving — and a CTA that
 * lands on the purchase card with the paperback preselected.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE ONE THING THIS SUITE EXISTS FOR ABOVE ALL OTHERS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * A struck-through former price is an FTC-class claim. This suite asserts
 * that the figure struck on THIS page equals the real sum of the three live
 * WooCommerce paperback prices, that the collection figure equals that sum
 * minus the live tier-3 discount, and — the assertion that makes this page
 * safe rather than merely correct today — that NO price literal exists in
 * the template's source at all. A number that cannot be typed cannot go
 * stale.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE. It reads a rendered document and the live
 *    pricing functions. It cannot prove geometry, cannot prove what the
 *    cart charges, and cannot prove the coupon line's commercial policy is
 *    settled. The cart figure is verified in a real Blocks cart and
 *    recorded in the CYCLE161 QA evidence; the coupon policy conflict is
 *    escalated, not tested.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_ktu_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

function bhp_ktu_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
}

echo "\n=== 0. Prerequisites ===\n";

foreach ( array(
	'bhp_bundle_landing_price_facts',
	'bhp_bundle_landing_default_format',
	'bhp_audience_coupon_public_notice',
) as $ktu_fn ) {
	bhp_ktu_assert( function_exists( $ktu_fn ), "0: {$ktu_fn}() exists", $failures );
}
if ( ! function_exists( 'bhp_bundle_landing_price_facts' ) ) {
	fwrite( STDERR, "Cannot continue: the bundle plugin is not the 1.8.46 build.\n" );
	exit( 1 );
}

$ktu_page = get_page_by_path( 'adventure-kit-thank-you' );
if ( ! $ktu_page ) {
	fwrite( STDERR, "Cannot continue: /adventure-kit-thank-you/ does not exist on this environment.\n" );
	exit( 1 );
}
bhp_ktu_assert(
	'page-adventure-kit-thank-you.php' === get_page_template_slug( $ktu_page->ID ),
	'0: the page uses the Adventure Kit thank-you template',
	$failures
);

$ktu_resp = wp_remote_get( get_permalink( $ktu_page ), array( 'timeout' => 30, 'sslverify' => false ) );
$ktu_code = is_wp_error( $ktu_resp ) ? 0 : (int) wp_remote_retrieve_response_code( $ktu_resp );
$html     = is_wp_error( $ktu_resp ) ? '' : (string) wp_remote_retrieve_body( $ktu_resp );
bhp_ktu_assert( 200 === $ktu_code, "0: the page returns HTTP 200 (got {$ktu_code})", $failures );
if ( 200 !== $ktu_code ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

echo "\n=== 1. The numbers are LIVE, and the strikethrough is TRUE ===\n";

$ktu_fmt = bhp_bundle_landing_default_format();
bhp_ktu_assert( 'paperback' === $ktu_fmt, "1: the page leads with PAPERBACK (got '{$ktu_fmt}')", $failures );

$facts = bhp_bundle_landing_price_facts( $ktu_fmt );

/*
 * ⛔ THE SUM IS RE-DERIVED HERE FROM WooCOMMERCE INDEPENDENTLY of the
 *    function under test. Asserting the function against itself would
 *    prove nothing.
 */
$ktu_catalog = bhp_bundle_catalog();
$ktu_sum     = 0.0;
$ktu_n       = 0;
foreach ( $ktu_catalog[ $ktu_fmt ] as $ktu_info ) {
	$ktu_p = wc_get_product( (int) $ktu_info['product_id'] );
	if ( $ktu_p && (float) $ktu_p->get_price() > 0 ) {
		$ktu_sum += (float) $ktu_p->get_price();
		$ktu_n++;
	}
}
bhp_ktu_assert( 3 === $ktu_n, "1: all three {$ktu_fmt} products resolve to a live price", $failures );
bhp_ktu_assert( true === $facts['live'], '1: the price facts are read LIVE, not from the fallback constant', $failures );
bhp_ktu_assert(
	abs( $facts['separate'] - $ktu_sum ) < 0.005,
	sprintf( '1: the strikethrough anchor is the real sum of singles (%.2f)', $ktu_sum ),
	$failures
);
$ktu_discount = (float) bhp_bundle_rules( $ktu_fmt )[3]['discount'];
bhp_ktu_assert(
	abs( $facts['bundle'] - ( $ktu_sum - $ktu_discount ) ) < 0.005,
	sprintf( '1: the collection price is sum-minus-discount (%.2f - %.2f = %.2f)', $ktu_sum, $ktu_discount, $ktu_sum - $ktu_discount ),
	$failures
);

echo "\n=== 2. Those numbers are what the page actually prints ===\n";

$ktu_sep = '$' . number_format( $facts['separate'], 2 );
$ktu_bun = '$' . number_format( $facts['bundle'], 2 );
$ktu_sav = '$' . number_format( $facts['save'], 2 );

bhp_ktu_assert(
	substr_count( $html, 'class="bhp-kit-upsell__price"' ) === 1,
	'2: exactly one price block renders',
	$failures
);
bhp_ktu_assert(
	strpos( $html, '<s class="bhp-kit-upsell__price-was" aria-hidden="true">' . $ktu_sep . '</s>' ) !== false,
	"2: the strike is a real <s> carrying {$ktu_sep}",
	$failures
);
/* Dollars and cents are separate nodes by design; re-assemble and compare. */
$ktu_now_ok = false;
if ( preg_match( '/<span class="bhp-kit-upsell__price-now" aria-hidden="true">(.*?)<\/span>\s*<\/span>/su', $html, $ktu_nm ) ) {
	$ktu_flat   = preg_replace( '/<span class="bhp-kit-upsell__price-cents">(.*?)$/su', '$1', $ktu_nm[1] );
	$ktu_now_ok = trim( (string) $ktu_flat ) === $ktu_bun;
} else {
	$ktu_now_ok = strpos( $html, '<span class="bhp-kit-upsell__price-now" aria-hidden="true">' . $ktu_bun . '</span>' ) !== false;
}
bhp_ktu_assert( $ktu_now_ok, "2: the collection price {$ktu_bun} is printed", $failures );
bhp_ktu_assert(
	strpos( $html, '<span class="bhp-kit-upsell__price-save" aria-hidden="true">Save ' . $ktu_sav . '</span>' ) !== false,
	"2: the saving \"Save {$ktu_sav}\" is printed",
	$failures
);
/* The visually-hidden sentence is what a screen reader actually gets. */
bhp_ktu_assert(
	strpos( $html, 'bought separately cost ' . $ktu_sep ) !== false
	&& strpos( $html, 'The Complete Collection is ' . $ktu_bun ) !== false
	&& strpos( $html, 'you save ' . $ktu_sav . ' buying them together' ) !== false,
	'2: the accessible sentence states the full comparison',
	$failures
);

echo "\n=== 3. NO PRICE LITERAL EXISTS IN THE TEMPLATE SOURCE ===\n";

/*
 * ⛔ THIS IS THE ASSERTION THAT KEEPS THE PAGE HONEST NEXT YEAR. Everything
 *    above proves the numbers are right TODAY. This proves they cannot
 *    become wrong by being typed: if a future editor pastes "$31.99" into
 *    the template, the suite fails even though the page still looks right.
 */
$ktu_tpl_path = get_template_directory() . '/page-adventure-kit-thank-you.php';
$ktu_tpl      = is_readable( $ktu_tpl_path ) ? (string) file_get_contents( $ktu_tpl_path ) : '';
bhp_ktu_assert( '' !== $ktu_tpl, '3: the template source is readable', $failures );
if ( '' !== $ktu_tpl ) {
	/* Strip block comments first: the docblock legitimately quotes the
	   verified figures as evidence, and evidence is not a hardcoded price. */
	$ktu_code_only = preg_replace( '#/\*.*?\*/#s', '', $ktu_tpl );
	$ktu_code_only = preg_replace( '#//[^\n]*#', '', (string) $ktu_code_only );
	bhp_ktu_assert(
		preg_match( '/\$\s*\d+\s*\.\s*\d{2}/', (string) $ktu_code_only ) !== 1,
		'3: no "$NN.NN" price literal appears in the template code',
		$failures
	);
	foreach ( array( '35.97', '31.99', '3.98', '53.97', '48.99', '4.98' ) as $ktu_lit ) {
		bhp_ktu_assert(
			strpos( (string) $ktu_code_only, $ktu_lit ) === false,
			"3: the literal {$ktu_lit} does not appear in the template code",
			$failures
		);
	}
	/*
	 * ⛔ AND NO COUPON CODE LITERAL, EVER. This repository is PUBLIC on
	 *    GitHub. BHP-AGENT-STANDING-RULES §4.1 classes coupon codes as
	 *    PRIVATE, conflict C6 is the live instance of them leaking, and
	 *    plugin 1.8.29 emptied the last literal list for exactly this
	 *    reason. The code the page prints is read from a site option.
	 */
	bhp_ktu_assert(
		preg_match( '/[\'"][A-Z]{4,}[0-9]{1,3}[\'"]/', $ktu_tpl ) !== 1,
		'3: no coupon-code-shaped literal appears anywhere in the template',
		$failures
	);
}

echo "\n=== 4. The CTA lands on the purchase card, paperback preselected ===\n";

bhp_ktu_assert(
	strpos( $html, 'href="' . esc_url( home_url( '/complete-collection/' ) . '#bhp-landing-pricing-card' ) . '"' ) !== false,
	'4: the CTA points at /complete-collection/#bhp-landing-pricing-card',
	$failures
);
/*
 * ⛔ THE ANCHOR MUST RESOLVE. It only worked from off-page after plugin
 *    1.8.28 added the id; before that the link relied on a same-page JS
 *    interceptor that a visitor arriving from THIS page never runs.
 */
$ktu_cres = wp_remote_get( home_url( '/complete-collection/' ), array( 'timeout' => 30, 'sslverify' => false ) );
$ktu_chtml = is_wp_error( $ktu_cres ) ? '' : (string) wp_remote_retrieve_body( $ktu_cres );
bhp_ktu_assert(
	substr_count( $ktu_chtml, 'id="bhp-landing-pricing-card"' ) === 1,
	'4: the target id exists exactly once on the collection page',
	$failures
);
bhp_ktu_assert(
	preg_match( '/<button[^>]*aria-checked="true"[^>]*data-bhp-format-btn="paperback"/s', $ktu_chtml ) === 1
	|| preg_match( '/data-bhp-format-btn="paperback"[^>]*aria-checked="true"/s', $ktu_chtml ) === 1
	|| preg_match( '/aria-checked="true"[\s\S]{0,200}?data-bhp-format-btn="paperback"/s', $ktu_chtml ) === 1,
	'4: the collection page opens with PAPERBACK preselected',
	$failures
);

echo "\n=== 5. The analytics contract is BYTE-UNCHANGED ===\n";

bhp_ktu_assert(
	strpos( $html, 'data-bhp-event="collection_upsell_click"' ) !== false,
	'5: the existing collection_upsell_click event attribute survives',
	$failures
);
bhp_ktu_assert(
	strpos( $html, 'data-bhp-format="collection"' ) !== false
	&& strpos( $html, 'data-bhp-source="parent_thank_you"' ) !== false,
	'5: its payload attributes are unchanged (collection / parent_thank_you)',
	$failures
);
bhp_ktu_assert(
	substr_count( $html, 'data-bhp-event="collection_upsell_click"' ) === 1,
	'5: it still fires from exactly one element',
	$failures
);
/* The signup conversion event on this page is not this build's business. */
bhp_ktu_assert(
	strpos( $html, "'event': 'adventure_kit_signup'" ) !== false
	|| strpos( $html, '"event":"adventure_kit_signup"' ) !== false,
	'5: the adventure_kit_signup conversion event still renders (unchanged)',
	$failures
);

echo "\n=== 6. The section that had to survive, survived ===\n";

bhp_ktu_assert(
	strpos( $html, 'Let Your Child Choose Their Adventure' ) !== false,
	'6: the "Let Your Child Choose Their Adventure" section is intact',
	$failures
);
bhp_ktu_assert(
	bhp_ktu_before( $html, 'bhp-kit-upsell__price', 'Let Your Child Choose Their Adventure' ),
	'6: the collection module still precedes it',
	$failures
);
bhp_ktu_assert(
	strpos( $html, 'Your Reluctant Reader Adventure Kit Is on Its Way' ) !== false,
	'6: the confirmation headline is unchanged',
	$failures
);

echo "\n=== 7. The coupon line is GATED, and the gate is asserted ===\n";

/*
 * ⛔ THIS SECTION DOES NOT ASSERT THAT THE COUPON LINE RENDERS. It asserts
 *    that the line and the live coupon record cannot disagree, in either
 *    direction. Whether the line SHOULD render at all is an open owner
 *    decision — it runs against the FROZEN Audience Coupon Policy in
 *    docs/ENGINEERING/FUNNEL_CONSTITUTION.md — and a test that demanded it
 *    would be this agent resolving a contradiction that is not its to
 *    resolve.
 */
$ktu_notice   = bhp_audience_coupon_public_notice( $ktu_fmt );
$ktu_rendered = strpos( $html, 'class="bhp-kit-upsell__coupon"' ) !== false;

bhp_ktu_assert(
	( null === $ktu_notice ) === ( ! $ktu_rendered ),
	'7: the line renders if and only if the gated helper returns a live coupon',
	$failures
);
if ( null !== $ktu_notice ) {
	echo "NOTE: the coupon line IS enabled on this environment (option bhp_audience_coupon_public_code is set).\n";
	bhp_ktu_assert(
		strpos( $html, '<strong>' . $ktu_notice['code'] . '</strong>' ) !== false,
		'7: the rendered code matches the live coupon record',
		$failures
	);
	$ktu_pct = rtrim( rtrim( number_format( $ktu_notice['percent'], 2, '.', '' ), '0' ), '.' ) . '%';
	bhp_ktu_assert(
		strpos( $html, 'another ' . $ktu_pct . ' off' ) !== false,
		"7: the rendered percentage ({$ktu_pct}) is read off the coupon record, not typed",
		$failures
	);
	bhp_ktu_assert(
		$ktu_notice['minimum'] <= 0 || $ktu_notice['minimum'] <= (float) $facts['bundle'],
		sprintf( '7: the coupon minimum spend (%.2f) is reachable on the advertised collection (%.2f)', $ktu_notice['minimum'], $facts['bundle'] ),
		$failures
	);
	bhp_ktu_assert(
		strpos( $html, 'off the collection at checkout' ) !== false,
		'7: the copy states WHAT the discount applies to (the collection), not a bare "10% off"',
		$failures
	);
} else {
	echo "NOTE: the coupon line is OFF on this environment (option bhp_audience_coupon_public_code unset or the coupon failed a live check). This is the shipped default.\n";
}

echo "\n=== 8. Nothing that must not move, moved ===\n";

$ktu_schema = '';
if ( preg_match( '/<script[^>]*class="rank-math-schema"[^>]*>(.*?)<\/script>/su', $html, $ktu_sm ) ) {
	$ktu_schema = $ktu_sm[1];
}
bhp_ktu_assert(
	strpos( $ktu_schema, 'aggregateRating' ) === false && strpos( $ktu_schema, '"review"' ) === false,
	'8: no aggregateRating and no review schema (never-invent rule)',
	$failures
);
bhp_ktu_assert(
	strpos( $ktu_schema, '"@type":"Offer"' ) === false && strpos( $ktu_schema, '"@type": "Offer"' ) === false,
	'8: the thank-you page emits NO Offer schema despite now showing a price',
	$failures
);
/* Funnel isolation: this is the PARENT funnel's page and must stay so. */
bhp_ktu_assert(
	strpos( $html, 'bhp_mariana_popup' ) === false && strpos( $html, 'teacher_popup' ) === false,
	'8: no teacher-funnel storage key or event prefix appears (funnel isolation)',
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
