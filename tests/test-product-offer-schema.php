<?php
/**
 * Brave Hearts — PRODUCT OFFER SCHEMA (theme 1.19.222).
 * CYCLE156-LD-01 · covers "Option B" (format-aware primary offer) and F6
 * (the dead shippingDetails / variable-GTIN filter) from the 2026-08-13
 * Google product feed audit.
 *
 * Run via WP-CLI, on staging:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-product-offer-schema.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Two defects, both verified live on 2026-08-13 before a line was changed:
 *
 *   1. A hardcover Google feed item said $17.99 while the page it landed on
 *      emitted `offers[0].price = 11.99`. A $6.00 feed-vs-landing-page
 *      contradiction on all three hardcovers.
 *   2. `functions.php`'s shippingDetails + variable-GTIN filter had been
 *      unreachable since the hardcover offer was added, because its guard
 *      `isset($entity['offers']['@type'])` is false for a LIST of offers.
 *      Rendered pages carried zero `OfferShippingDetails` and no `gtin`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE CAN AND CANNOT PROVE — read before trusting a green run
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It invokes the THEME's REAL registered `rank_math/json_ld` callbacks, in
 * their REAL registration order, against a REAL product query. It proves what
 * those callbacks produce. That is stronger than reading their source, and
 * weaker than reading the page.
 *
 * ⚠ IT DOES NOT RUN RANK MATH'S OWN CALLBACKS, and the reason is recorded
 *   rather than left as a design mystery. A plain
 *   `apply_filters('rank_math/json_ld', $seed, null)` from WP-CLI FATALS:
 *   Rank Math's Local SEO module calls `$jsonld->can_add_global_entities()`
 *   on the second argument, and there is no real `JsonLD` instance to pass
 *   outside a front-end request. Observed, not assumed — it is what the first
 *   version of this suite did, and it died at
 *   `class-local-seo.php:98` on the first assertion.
 *
 *   So the harness resolves the priority-999 callbacks straight off
 *   `$wp_filter` and invokes only the ones defined inside this theme. Rank
 *   Math's contribution is supplied as the SEED instead — the single
 *   associative Offer it hands downstream callbacks — which is the input the
 *   theme's callbacks actually receive in production.
 *
 * ⛔ IT IS NOT A SUBSTITUTE FOR INSPECTING THE RENDERED
 *    `<script class="rank-math-schema">` BLOCK ON A REAL PAGE LOAD, which
 *    `.claude/rules/schema.md` requires for any structured-data change and
 *    which was performed separately. A filter can be correct and still not
 *    reach the page.
 *
 * ⛔ IT SAYS NOTHING ABOUT GOOGLE'S VERDICT. Merchant Center approval is
 *    Google's to give, 24–72h after a re-sync and only after Andrew submits a
 *    review request. Claiming otherwise from a green run would be a fabricated
 *    verification.
 *
 * ⛔ THIS SUITE MUTATES NOTHING. It sets `$_GET` and the global `$wp_query`
 *    for the duration of one assertion and restores both. No post, product,
 *    variation, term, option, order or cart is created or changed on any
 *    environment.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⚠ COUNTERS IN $GLOBALS ON PURPOSE. `wp eval-file` includes this file INSIDE
 *   a function, so a plain top-level `$pass` is a LOCAL and `global $pass`
 *   inside a helper binds to a different, always-empty variable. Recorded in
 *   test-google-feed-attributes.php; repeated so the next suite does not
 *   re-derive it from "0 passed, 0 failed".
 */
$GLOBALS['bhp_pos_pass'] = 0;
$GLOBALS['bhp_pos_fail'] = 0;
$GLOBALS['bhp_pos_skip'] = 0;

function bhp_pos_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['bhp_pos_pass']++;
		WP_CLI::log( "  PASS  {$label}" );
	} else {
		$GLOBALS['bhp_pos_fail']++;
		WP_CLI::log( "  FAIL  {$label}" . ( $detail ? "  --  {$detail}" : '' ) );
	}
}

function bhp_pos_skip( $label, $why = '' ) {
	$GLOBALS['bhp_pos_skip']++;
	WP_CLI::log( "  SKIP  {$label}" . ( $why ? "  --  {$why}" : '' ) );
}

/**
 * The theme's own `rank_math/json_ld` callbacks at priority 999, in
 * registration order.
 *
 * Resolved from `$wp_filter` rather than named in a list, so a callback added
 * later is exercised automatically instead of being silently untested — and so
 * this suite cannot drift out of date by omission. Rank Math's own callbacks
 * are excluded because they need a real `JsonLD` instance that does not exist
 * under WP-CLI (see the header note).
 *
 * @return callable[]
 */
function bhp_pos_theme_callbacks() {
	global $wp_filter;

	$theme_dir = wp_normalize_path( get_template_directory() );
	$out       = array();

	if ( ! isset( $wp_filter['rank_math/json_ld'][999] ) ) {
		return $out;
	}

	foreach ( $wp_filter['rank_math/json_ld'][999] as $registered ) {
		$fn = $registered['function'];

		if ( is_string( $fn ) && 0 === strpos( $fn, 'bhp_' ) ) {
			$out[] = $fn;
			continue;
		}

		// An anonymous closure — the shippingDetails/GTIN filter is one. Keep
		// it only if it was declared inside this theme, so a plugin's closure
		// registered at the same priority is never dragged in.
		if ( $fn instanceof Closure ) {
			try {
				$file = wp_normalize_path( ( new ReflectionFunction( $fn ) )->getFileName() );
			} catch ( \Throwable $e ) {
				continue;
			}
			if ( $file && 0 === strpos( $file, $theme_dir ) ) {
				$out[] = $fn;
			}
		}
	}

	return $out;
}

/**
 * Run the theme's `rank_math/json_ld` callbacks with the main query pointed at
 * $product_id and $_GET set to $get, then restore both.
 *
 * The seed is the shape Rank Math itself hands downstream callbacks at
 * priority 999: a `@graph`-less flat array of entities, the Product entity
 * carrying a SINGLE associative Offer with `seller` and `priceValidUntil`.
 * That is exactly what was observed in the rendered page before the change,
 * so the input is real even though it is constructed here.
 */
function bhp_pos_graph( $product_id, array $get, array $seed_offer = array() ) {
	global $wp_query, $wp_the_query;

	$prev_get       = $_GET;
	$prev_query     = $wp_query;
	$prev_the_query = $wp_the_query;

	$_GET = $get;

	$q = new WP_Query(
		array(
			'p'         => (int) $product_id,
			'post_type' => 'product',
		)
	);
	$q->is_single   = true;
	$q->is_singular = true;
	$q->is_home     = false;
	$wp_query       = $q;
	$wp_the_query   = $q;

	$product = wc_get_product( $product_id );
	$offer   = array_merge(
		array(
			'@type'           => 'Offer',
			'price'           => $product ? wc_format_decimal( $product->get_price(), wc_get_price_decimals() ) : '',
			'priceCurrency'   => get_woocommerce_currency(),
			'availability'    => 'http://schema.org/InStock',
			'url'             => get_permalink( $product_id ),
			'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'seller'          => array(
				'@type' => 'Organization',
				'name'  => 'Brave Hearts Publishing',
			),
		),
		$seed_offer
	);

	$seed = array(
		'product' => array(
			'@type'  => 'Product',
			'name'   => get_the_title( $product_id ),
			'offers' => $offer,
		),
	);

	try {
		foreach ( bhp_pos_theme_callbacks() as $callback ) {
			// Both accept ($data) or ($data, $jsonld); the second argument is
			// unused by either, and is passed as null exactly as it would be
			// ignored on a real request.
			$seed = call_user_func( $callback, $seed, null );
		}
		return $seed;
	} finally {
		$_GET         = $prev_get;
		$wp_query     = $prev_query;
		$wp_the_query = $prev_the_query;
		wp_reset_postdata();
	}
}

/** Pull the offers out of a filtered graph, always as a list. */
function bhp_pos_offers( array $graph ) {
	foreach ( $graph as $node ) {
		if ( ! is_array( $node ) || ( $node['@type'] ?? '' ) !== 'Product' ) {
			continue;
		}
		$offers = $node['offers'] ?? array();
		if ( isset( $offers['@type'] ) ) {
			return array( $offers );
		}
		return array_values( (array) $offers );
	}
	return array();
}

/* ───────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '=== PRODUCT OFFER SCHEMA — theme ' . wp_get_theme()->get( 'Version' ) . ' ===' );

if ( ! function_exists( 'bhp_book_registry' ) || ! function_exists( 'wc_get_product' ) ) {
	WP_CLI::error( 'bhp_book_registry() or WooCommerce is unavailable — the suite cannot run meaningfully.' );
}

$registry = bhp_book_registry();

/* ───────────────────────────────────────────────────────────────────────────
 * §1  THE CALLBACKS EXIST AND ARE BOTH REGISTERED AT 999.
 *
 * Priority matters and is not cosmetic: Rank Math builds its Product entity
 * progressively across this same filter, so a default-priority callback sees
 * an empty array (`.claude/rules/schema.md`).
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§1  REGISTRATION' );

bhp_pos_assert(
	'bhp_book_add_hardcover_offer() is registered on rank_math/json_ld at 999',
	999 === has_filter( 'rank_math/json_ld', 'bhp_book_add_hardcover_offer' ),
	var_export( has_filter( 'rank_math/json_ld', 'bhp_book_add_hardcover_offer' ), true )
);

/*
 * ⛔ THE ASSERTION THAT KEEPS THE REST OF THIS FILE HONEST. If the harness
 *    resolved ZERO theme callbacks, every section below would run against an
 *    untouched seed and a handful of them would still pass by coincidence —
 *    a green suite testing nothing at all. Both callbacks must be found:
 *    bhp_book_add_hardcover_offer() and the anonymous shippingDetails/GTIN
 *    closure in functions.php.
 */
$pos_callbacks = bhp_pos_theme_callbacks();
bhp_pos_assert(
	'the harness resolved BOTH theme callbacks at priority 999',
	2 === count( $pos_callbacks ),
	'found ' . count( $pos_callbacks )
);
bhp_pos_assert(
	'the hardcover-offer callback runs FIRST — the order that created the dead-code defect',
	isset( $pos_callbacks[0] ) && 'bhp_book_add_hardcover_offer' === $pos_callbacks[0],
	var_export( $pos_callbacks[0] ?? null, true )
);
bhp_pos_assert(
	'the shippingDetails/GTIN callback runs SECOND, and is a closure declared in this theme',
	isset( $pos_callbacks[1] ) && $pos_callbacks[1] instanceof Closure
);

bhp_pos_assert(
	'bhp_bundle_single_shipping() is available — the schema rate has an authoritative source',
	function_exists( 'bhp_bundle_single_shipping' )
);

/*
 * ⛔ THE TIER FIGURES THE SCHEMA PRINTS. Asserted against the bundle plugin,
 *    which is the single source of truth for every shipping number on this
 *    site, and cross-checked against the published Shipping Policy page's own
 *    words: "$1.99 for a single paperback and $2.99 for a single hardcover".
 *    If Andrew ever approves a different tier, this assertion fails FIRST and
 *    tells the next session the schema figure moved with it — which is the
 *    whole reason the rate is read rather than hardcoded.
 */
if ( function_exists( 'bhp_bundle_single_shipping' ) ) {
	bhp_pos_assert(
		'single-paperback shipping is 1.99 (bundle plugin, not a literal in the theme)',
		abs( 1.99 - (float) bhp_bundle_single_shipping( 'paperback' ) ) < 0.005,
		var_export( bhp_bundle_single_shipping( 'paperback' ), true )
	);
	bhp_pos_assert(
		'single-hardcover shipping is 2.99 (bundle plugin, not a literal in the theme)',
		abs( 2.99 - (float) bhp_bundle_single_shipping( 'hardcover' ) ) < 0.005,
		var_export( bhp_bundle_single_shipping( 'hardcover' ), true )
	);
}

/*
 * ⛔ THE $3.99 REGRESSION GUARD. The revived filter used to hardcode '3.99' as
 *    "the customer-facing rate". It is the ZONE configuration, not the rendered
 *    rate — conflating the two is the documented failure `CYCLE140-DEV-2`.
 *    This pins the literal out of the file so it cannot creep back in.
 */
/*
 * ⚠ COMMENTS ARE STRIPPED BEFORE THIS SEARCH, and that is not a convenience —
 *   the first version of this assertion FAILED against correct code. The
 *   docblock above the filter necessarily quotes the literal it removed
 *   ("The rate was a literal '3.99'"), and a raw substring search cannot tell
 *   an explanation from an instruction. token_get_all() can. Stripping the
 *   comments is the difference between a guard and a false alarm that the
 *   next session learns to ignore.
 */
$functions_src = (string) file_get_contents( get_template_directory() . '/functions.php' );
$code_only     = '';
foreach ( token_get_all( $functions_src ) as $token ) {
	if ( is_array( $token ) ) {
		if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$code_only .= $token[1];
		continue;
	}
	$code_only .= $token;
}

if ( preg_match( "/add_filter\('rank_math\/json_ld', function \(\\\$data, \\\$jsonld\).*?\}, 999, 2\);/s", $code_only, $m ) ) {
	bhp_pos_assert(
		"the schema filter's CODE contains no hardcoded '3.99' shipping literal",
		false === strpos( $m[0], '3.99' ),
		'block length ' . strlen( $m[0] )
	);
	bhp_pos_assert(
		'the schema filter reads its rate from bhp_bundle_single_shipping()',
		false !== strpos( $m[0], 'bhp_bundle_single_shipping' )
	);
	bhp_pos_assert(
		'the schema filter is allowlisted via bhp_book_lookup_product()',
		false !== strpos( $m[0], 'bhp_book_lookup_product' )
	);
} else {
	bhp_pos_skip( 'the $3.99 literal guard', 'could not isolate the filter block in functions.php' );
}

/* ───────────────────────────────────────────────────────────────────────────
 * §2  THE DEFECT ITSELF — WITH NO PARAMETER, THE PAPERBACK LEADS.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§2  NO PARAMETER — the $11.99 paperback is the primary offer' );

foreach ( $registry as $key => $book ) {
	$pb      = (int) $book['pb_product'];
	$hc      = wc_get_product( (int) $book['hc_product'] );
	$pb_prod = wc_get_product( (int) ( $book['pb_variation'] ? $book['pb_variation'] : $book['pb_product'] ) );
	if ( ! $hc || ! $pb_prod ) {
		bhp_pos_skip( "{$key}: products resolve", 'a product record is missing here' );
		continue;
	}

	$offers = bhp_pos_offers( bhp_pos_graph( $pb, array() ) );

	bhp_pos_assert( "{$key} (no param): both editions are offered", 2 === count( $offers ), 'count=' . count( $offers ) );
	bhp_pos_assert(
		"{$key} (no param): offers[0] is the PAPERBACK price",
		isset( $offers[0]['price'] ) && (float) $offers[0]['price'] === (float) $pb_prod->get_price(),
		'got ' . var_export( $offers[0]['price'] ?? null, true ) . ', expected ' . $pb_prod->get_price()
	);
	bhp_pos_assert(
		"{$key} (no param): offers[1] is the HARDCOVER price",
		isset( $offers[1]['price'] ) && (float) $offers[1]['price'] === (float) $hc->get_price(),
		'got ' . var_export( $offers[1]['price'] ?? null, true ) . ', expected ' . $hc->get_price()
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §3  OPTION B — `?bhp_format=hardcover` MAKES THE $17.99 OFFER PRIMARY.
 *     This is the assertion the whole change exists to make true.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§3  ?bhp_format=hardcover — the $17.99 hardcover is the primary offer' );

foreach ( $registry as $key => $book ) {
	$pb      = (int) $book['pb_product'];
	$hc      = wc_get_product( (int) $book['hc_product'] );
	$pb_prod = wc_get_product( (int) ( $book['pb_variation'] ? $book['pb_variation'] : $book['pb_product'] ) );
	if ( ! $hc || ! $pb_prod ) {
		continue;
	}

	$offers = bhp_pos_offers( bhp_pos_graph( $pb, array( 'bhp_format' => 'hardcover' ) ) );

	bhp_pos_assert( "{$key} (?bhp_format=hardcover): both editions still offered", 2 === count( $offers ), 'count=' . count( $offers ) );

	// ⭐ THE $6.00 GAP THE AUDIT MEASURED. offers[0] is what Google price-checks.
	bhp_pos_assert(
		"{$key} (?bhp_format=hardcover): offers[0] is the HARDCOVER price — the feed price",
		isset( $offers[0]['price'] ) && (float) $offers[0]['price'] === (float) $hc->get_price(),
		'got ' . var_export( $offers[0]['price'] ?? null, true ) . ', expected ' . $hc->get_price()
	);
	bhp_pos_assert(
		"{$key} (?bhp_format=hardcover): the PAPERBACK offer is still present, second",
		isset( $offers[1]['price'] ) && (float) $offers[1]['price'] === (float) $pb_prod->get_price(),
		'got ' . var_export( $offers[1]['price'] ?? null, true ) . ', expected ' . $pb_prod->get_price()
	);
	bhp_pos_assert(
		"{$key} (?bhp_format=hardcover): the leading offer carries the hardcover SKU",
		( $offers[0]['sku'] ?? '' ) === $hc->get_sku(),
		var_export( $offers[0]['sku'] ?? null, true )
	);

	// A promoted offer must not be structurally thinner than the one it leads.
	// Both values are COPIED from Rank Math's own sibling offer, never invented.
	bhp_pos_assert(
		"{$key} (?bhp_format=hardcover): the leading offer carries a seller",
		! empty( $offers[0]['seller'] )
	);
	bhp_pos_assert(
		"{$key} (?bhp_format=hardcover): the leading offer's priceValidUntil equals the sibling's — copied, not invented",
		( $offers[0]['priceValidUntil'] ?? null ) === ( $offers[1]['priceValidUntil'] ?? null ),
		var_export( $offers[0]['priceValidUntil'] ?? null, true )
	);

	// The URL Google follows from the leading offer must be the page it is on.
	bhp_pos_assert(
		"{$key} (?bhp_format=hardcover): the leading offer's url carries bhp_format=hardcover",
		false !== strpos( (string) ( $offers[0]['url'] ?? '' ), 'bhp_format=hardcover' ),
		var_export( $offers[0]['url'] ?? null, true )
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §4  THE PARAMETER IS STILL NOT TRUSTED BEYOND THE WHITELIST.
 *     ?bhp_format=paperback and a junk value must both leave paperback first.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§4  THE PARAMETER IS STILL WHITELISTED — junk does not reorder the offers' );

foreach ( $registry as $key => $book ) {
	$pb      = (int) $book['pb_product'];
	$pb_prod = wc_get_product( (int) ( $book['pb_variation'] ? $book['pb_variation'] : $book['pb_product'] ) );
	if ( ! $pb_prod ) {
		continue;
	}

	foreach ( array( 'paperback', 'audiobook', '', 'hardcovers', 'hardcove', 'h-a-r-d-c-o-v-e-r', '17.99' ) as $value ) {
		$offers = bhp_pos_offers( bhp_pos_graph( $pb, array( 'bhp_format' => $value ) ) );
		bhp_pos_assert(
			sprintf( "%s (?bhp_format='%s'): the PAPERBACK still leads", $key, $value ),
			isset( $offers[0]['price'] ) && (float) $offers[0]['price'] === (float) $pb_prod->get_price(),
			'got ' . var_export( $offers[0]['price'] ?? null, true )
		);
	}
}

/*
 * ⭐ A PRE-EXISTING BEHAVIOUR, FOUND BY THIS SUITE AND ASSERTED RATHER THAN
 *    "FIXED". `bhp_book_incoming_format()` runs the raw parameter through
 *    `sanitize_key()` BEFORE the whitelist test, and sanitize_key() lower-cases
 *    the value and DELETES — rather than rejects — every character outside
 *    `[a-z0-9_-]`. So all of these select hardcover:
 *
 *        HARDCOVER      (case)
 *        Hardcover      (case)
 *        " hardcover"   (leading space deleted)
 *        "hard cover"   (inner space deleted)
 *        'hardcover"'   (quote deleted)
 *
 *    ⚠ THIS SUITE'S FIRST TWO DRAFTS BOTH ASSERTED THE OPPOSITE AND FAILED —
 *      three times, then six — on CORRECT code. The code was right and the
 *      expectation was wrong, twice, and the second draft under-estimated
 *      sanitize_key() again (assuming case-folding only, when it also strips).
 *      Both corrections are recorded here rather than quietly deleting the
 *      cases that exposed them.
 *
 *    ⛔ NOT INTRODUCED BY 1.19.222, AND NOT A HOLE. It long predates this
 *      change; the visible format selector has behaved identically since
 *      1.19.166 — which is exactly the between-surfaces agreement 1.19.222
 *      exists to protect, so the schema matching it is correct. The whitelist
 *      is still closed: after normalisation the value must equal one of four
 *      literals, and `hardcovers`, `hardcove`, `h-a-r-d-c-o-v-e-r` and `17.99`
 *      are all rejected above. The parameter still carries no commerce
 *      authority — it chooses which card is pre-pressed and which offer leads,
 *      and every price on both surfaces is read live from WooCommerce.
 *
 *    ➡ FLAGGED FOR THE RECORD, NOT CHANGED HERE: tightening the parameter to
 *      an exact-match test would be a behaviour change outside this brief, and
 *      it touches the legacy hardcover 301's landing path. Gandalf's call.
 */
foreach ( $registry as $key => $book ) {
	$pb = (int) $book['pb_product'];
	$hc = wc_get_product( (int) $book['hc_product'] );
	if ( ! $hc ) {
		continue;
	}
	foreach ( array( 'HARDCOVER ', 'Hardcover', ' hardcover', 'hard cover', 'hardcover"' ) as $value ) {
		$offers = bhp_pos_offers( bhp_pos_graph( $pb, array( 'bhp_format' => $value ) ) );
		bhp_pos_assert(
			sprintf( "%s (?bhp_format='%s'): sanitize_key() normalises it, so the HARDCOVER leads — documented, pre-existing", $key, $value ),
			isset( $offers[0]['price'] ) && (float) $offers[0]['price'] === (float) $hc->get_price(),
			'got ' . var_export( $offers[0]['price'] ?? null, true )
		);
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §5  F6 — shippingDetails IS REACHABLE AGAIN, ON BOTH OFFER SHAPES,
 *     AND CARRIES THE OWNER-RULED TIER RATHER THAN THE OLD $3.99 LITERAL.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§5  F6 — shippingDetails, per offer, at the approved single-item tier' );

foreach ( $registry as $key => $book ) {
	$pb = (int) $book['pb_product'];

	foreach ( array( '' => 'no param', 'hardcover' => '?bhp_format=hardcover' ) as $param => $label ) {
		$get    = '' === $param ? array() : array( 'bhp_format' => $param );
		$offers = bhp_pos_offers( bhp_pos_graph( $pb, $get ) );

		foreach ( $offers as $i => $offer ) {
			$is_hc    = false !== strpos( (string) ( $offer['url'] ?? '' ), 'bhp_format=hardcover' );
			$expected = number_format( (float) bhp_bundle_single_shipping( $is_hc ? 'hardcover' : 'paperback' ), 2, '.', '' );
			$got      = $offer['shippingDetails']['shippingRate']['value'] ?? null;

			bhp_pos_assert(
				sprintf( '%s (%s): offer[%d] emits OfferShippingDetails', $key, $label, $i ),
				( $offer['shippingDetails']['@type'] ?? '' ) === 'OfferShippingDetails'
			);
			bhp_pos_assert(
				sprintf( '%s (%s): offer[%d] shippingRate is %s (%s)', $key, $label, $i, $expected, $is_hc ? 'hardcover' : 'paperback' ),
				$got === $expected,
				'got ' . var_export( $got, true )
			);
			bhp_pos_assert(
				sprintf( '%s (%s): offer[%d] shipping rate is NOT the old 3.99 zone literal', $key, $label, $i ),
				'3.99' !== $got,
				'got ' . var_export( $got, true )
			);

			// Contiguous US only, honestly — no AK/HI/territories/international.
			$regions = $offer['shippingDetails']['shippingDestination']['addressRegion'] ?? array();
			bhp_pos_assert(
				sprintf( '%s (%s): offer[%d] names 49 contiguous-US regions (48 states + DC)', $key, $label, $i ),
				is_array( $regions ) && 49 === count( $regions ),
				'count=' . ( is_array( $regions ) ? count( $regions ) : 'n/a' )
			);
			bhp_pos_assert(
				sprintf( '%s (%s): offer[%d] does NOT claim AK or HI', $key, $label, $i ),
				is_array( $regions ) && ! in_array( 'AK', $regions, true ) && ! in_array( 'HI', $regions, true )
			);
		}
	}
}

/*
 * ⭐ THE SHAPE REGRESSION. The guard used to be
 *   `!isset($entity['offers']['@type'])` and skipped a LIST. It must now handle
 *   BOTH shapes — and a SINGLE Offer must come back out as a single Offer, not
 *   silently promoted into a one-element list, which would change what every
 *   other consumer of the graph sees.
 */
WP_CLI::log( '' );
WP_CLI::log( '§5b  BOTH OFFER SHAPES SURVIVE — a single Offer stays a single Offer' );

$single_shape_ok = null;
foreach ( $registry as $key => $book ) {
	$hc_id = (int) $book['hc_product'];

	// A hardcover product page never gets a second offer appended (the
	// hardcover callback returns early on a non-canonical product), so the
	// graph reaching the shipping filter still holds a SINGLE Offer there.
	$graph = bhp_pos_graph( $hc_id, array() );
	foreach ( $graph as $node ) {
		if ( is_array( $node ) && ( $node['@type'] ?? '' ) === 'Product' ) {
			$single_shape_ok = isset( $node['offers']['@type'] );
			bhp_pos_assert(
				"{$key}: a single Offer is written back as a single Offer, not a list",
				true === $single_shape_ok,
				'offers is ' . ( isset( $node['offers']['@type'] ) ? 'associative' : 'a list' )
			);
			bhp_pos_assert(
				"{$key}: that single Offer still received its shippingDetails",
				( $node['offers']['shippingDetails']['@type'] ?? '' ) === 'OfferShippingDetails'
			);
		}
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §6  F6 — THE VARIABLE-PRODUCT GTIN PATCH IS REACHABLE AGAIN.
 *     Only Mariana is a variable product; the other two are simple and get
 *     gtin natively from Rank Math, so they are correctly untouched here.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§6  F6 — the variable-product GTIN reaches the paperback offer' );

$variable_seen = 0;
foreach ( $registry as $key => $book ) {
	$pb      = (int) $book['pb_product'];
	$product = wc_get_product( $pb );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		continue;
	}
	$children = $product->get_children();
	if ( 1 !== count( $children ) ) {
		bhp_pos_skip( "{$key}: single-variation GTIN patch", 'this product has ' . count( $children ) . ' variations, which is outside the patch scope' );
		continue;
	}
	$variable_seen++;

	$variation = wc_get_product( $children[0] );
	$gtin      = $variation ? (string) $variation->get_global_unique_id() : '';
	if ( '' === $gtin ) {
		bhp_pos_skip( "{$key}: single-variation GTIN patch", 'the variation carries no Global Unique ID on this environment' );
		continue;
	}

	foreach ( array( '' => 'no param', 'hardcover' => '?bhp_format=hardcover' ) as $param => $label ) {
		$get    = '' === $param ? array() : array( 'bhp_format' => $param );
		$offers = bhp_pos_offers( bhp_pos_graph( $pb, $get ) );

		$pb_offer = null;
		$hc_offer = null;
		foreach ( $offers as $offer ) {
			if ( false !== strpos( (string) ( $offer['url'] ?? '' ), 'bhp_format=hardcover' ) ) {
				$hc_offer = $offer;
			} else {
				$pb_offer = $offer;
			}
		}

		bhp_pos_assert(
			"{$key} ({$label}): the PAPERBACK offer carries the variation's gtin",
			( $pb_offer['gtin'] ?? '' ) === $gtin,
			'got ' . var_export( $pb_offer['gtin'] ?? null, true ) . ', expected ' . $gtin
		);
		bhp_pos_assert(
			"{$key} ({$label}): the gtin is a 13-digit ISBN",
			1 === preg_match( '/^\d{13}$/', (string) ( $pb_offer['gtin'] ?? '' ) )
		);
		// The hardcover is a separate product with its own ISBN — the
		// paperback variation's identifier must never be copied onto it.
		bhp_pos_assert(
			"{$key} ({$label}): the paperback gtin is NOT copied onto the hardcover offer",
			( $hc_offer['gtin'] ?? null ) !== $gtin,
			var_export( $hc_offer['gtin'] ?? null, true )
		);
	}
}
if ( 0 === $variable_seen ) {
	bhp_pos_skip( 'variable-product GTIN patch', 'no registry title is a variable product on this environment' );
}

/* ───────────────────────────────────────────────────────────────────────────
 * §7  THE ALLOWLIST — a non-book product gets NO shipping claim at all.
 *
 * ⛔ THIS IS A REAL RISK, NOT A HYPOTHETICAL. The pre-fix filter stamped its
 *    shipping block onto EVERY single product page. Reviving it unguarded
 *    would advertise a physical contiguous-US rate and a 3–8 day transit time
 *    for the $5.00 downloadable Activity Book. Silence is the honest output
 *    for a product whose shipping is not defined.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§7  THE ALLOWLIST — no shipping claim on a non-book product' );

$activity_id = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( 'BHP-ACTIVITY-BOOK-01' ) : 0;
if ( $activity_id > 0 ) {
	$offers = bhp_pos_offers( bhp_pos_graph( $activity_id, array() ) );
	$any    = false;
	foreach ( $offers as $offer ) {
		if ( isset( $offer['shippingDetails'] ) ) {
			$any = true;
		}
	}
	bhp_pos_assert(
		"the downloadable Activity Book ({$activity_id}) receives NO shippingDetails",
		false === $any
	);
	bhp_pos_assert(
		"the Activity Book ({$activity_id}) receives no second (hardcover) offer",
		count( $offers ) <= 1,
		'count=' . count( $offers )
	);
} else {
	bhp_pos_skip( 'non-book allowlist check', 'SKU BHP-ACTIVITY-BOOK-01 not found on this environment' );
}

/* ───────────────────────────────────────────────────────────────────────────
 * §8  THE STANDING CONSTRAINT — NO FABRICATED RATING OR REVIEW SCHEMA.
 *     Zero real reviews exist. This must stay true through any schema change.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§8  NO FABRICATED aggregateRating / review SCHEMA' );

foreach ( $registry as $key => $book ) {
	$pb   = (int) $book['pb_product'];
	$json = (string) wp_json_encode( bhp_pos_graph( $pb, array( 'bhp_format' => 'hardcover' ) ) );

	bhp_pos_assert( "{$key}: the filtered graph emits no aggregateRating", false === stripos( $json, 'aggregateRating' ) );
	bhp_pos_assert( "{$key}: the filtered graph emits no ratingValue", false === stripos( $json, 'ratingValue' ) );
	bhp_pos_assert( "{$key}: the filtered graph emits no reviewCount", false === stripos( $json, 'reviewCount' ) );
}

/* ───────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log(
	sprintf(
		'=== %d passed, %d failed, %d skipped ===',
		$GLOBALS['bhp_pos_pass'],
		$GLOBALS['bhp_pos_fail'],
		$GLOBALS['bhp_pos_skip']
	)
);
WP_CLI::log( '' );

if ( $GLOBALS['bhp_pos_fail'] > 0 ) {
	WP_CLI::error( 'Product offer schema suite FAILED.' );
}
