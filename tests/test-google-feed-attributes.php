<?php
/**
 * Brave Hearts — GOOGLE FEED ATTRIBUTES (theme 1.19.221).
 * CYCLE156-CX-01
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-google-feed-attributes.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * All six book products are DISAPPROVED in Google Merchant Center with the
 * code `ebooks_policy_violation`, read live from GLA's own cached issue table
 * on production 2026-08-13. `inc/google-feed.php` supplies the three missing
 * attributes — googleProductCategory, condition, brand — that leave Google
 * with no merchant-supplied signal that these are physical books.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * §1–§4 are pure PHP and prove the filter's logic and its allowlist.
 *
 * §5 constructs GLA's REAL WCProductAdapter and reads the REAL outgoing
 * payload. That is the only assertion in this file that proves what Google
 * actually receives. It SKIPS, loudly, when GLA is not installed.
 *
 * ⛔ NOTHING HERE PROVES GOOGLE ACCEPTS THE PRODUCTS. A green run means the
 * feed now carries the three attributes. Whether that clears the
 * `ebooks_policy_violation` is Google's verdict, delivered 24–72h after a
 * re-sync and only after Andrew submits a review request in the Merchant
 * Center console. Claiming otherwise would be a fabricated verification,
 * which the standing rules put in the same class as a fabricated review.
 *
 * ⛔ THIS SUITE MUTATES NOTHING. It reads products and builds throwaway
 * in-memory objects. No product record, no meta, no option, no cart.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ PASS / FAIL / SKIP / DATA — four result classes, and DATA is the unusual one
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Two of the three fixes in this package are PRODUCTION DATA changes, not code:
 * the six cover images were renamed in the media library, and the Activity
 * Book's GLA visibility was pinned to dont-sync-and-show. Both are WooCommerce
 * record mutations, which are Andrew-gated on EVERY environment including
 * staging — so they were deliberately not applied to staging.
 *
 * A code suite that asserts production data state can therefore never go green
 * on staging. Two bad answers were available and both were rejected: deleting
 * the assertions (which would stop protecting the fix) and softening them into
 * something that always passes (which is the fabricated-verification failure
 * class). The third answer is the DATA class:
 *
 *   - on PRODUCTION it is a hard assertion and fails the suite;
 *   - elsewhere it prints the REAL OBSERVED VALUE under a DATA label and does
 *     not fail, saying plainly that the fix was not applied there.
 *
 * The observed value is printed either way. Only the exit code differs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⚠ COUNTERS IN $GLOBALS ON PURPOSE. `wp eval-file` includes this file INSIDE
 *   a function, so a plain top-level `$pass` is a LOCAL and `global $pass`
 *   inside a helper binds to a different, always-empty variable. Recorded in
 *   test-checkout-heading-weight.php and test-checkout-free-bullets.php;
 *   repeated so the next suite does not re-derive it from "0 passed, 0 failed".
 */
$GLOBALS['bhp_gfa_pass'] = 0;
$GLOBALS['bhp_gfa_fail'] = 0;
$GLOBALS['bhp_gfa_skip'] = 0;

function bhp_gfa_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['bhp_gfa_pass']++;
		WP_CLI::log( "  PASS  {$label}" );
	} else {
		$GLOBALS['bhp_gfa_fail']++;
		WP_CLI::log( "  FAIL  {$label}" . ( $detail ? "  --  {$detail}" : '' ) );
	}
}

function bhp_gfa_skip( $label, $why = '' ) {
	$GLOBALS['bhp_gfa_skip']++;
	WP_CLI::log( "  SKIP  {$label}" . ( $why ? "  --  {$why}" : '' ) );
}

/**
 * Is this the production environment?
 *
 * Two of the fixes in this package are PRODUCTION DATA changes, not code:
 * the six cover images were renamed in the media library, and the Activity
 * Book's GLA visibility was pinned. Both are WooCommerce record mutations and
 * therefore Andrew-gated on EVERY environment — so they were deliberately NOT
 * applied to staging, and staging's data still carries the old state.
 */
function bhp_gfa_is_production() {
	return false === stripos( (string) home_url(), 'staging' );
}

/**
 * A DATA-STATE check, as opposed to a code check.
 *
 * ⛔ THIS IS NOT A SOFTENED ASSERTION AND MUST NOT BECOME ONE. On production
 *   it is a hard assertion and fails the suite. Off production it prints the
 *   real observed value under a DATA label and does not fail, because the fix
 *   it checks was deliberately never applied there — failing would train a
 *   reader to ignore a red run, which is worse than not asserting.
 *
 *   The observed value is ALWAYS printed either way. A reader can therefore
 *   see the true state on any environment; only the exit code differs.
 */
function bhp_gfa_data_state( $label, $ok, $observed = '' ) {
	if ( bhp_gfa_is_production() ) {
		bhp_gfa_assert( "[prod data] {$label}", $ok, $observed );
		return;
	}
	WP_CLI::log( sprintf(
		'  DATA  %s  --  %s  --  observed: %s',
		$label,
		$ok ? 'already true here' : 'NOT APPLIED on this environment (production-only, Andrew-gated product change)',
		$observed
	) );
}

WP_CLI::log( '' );
WP_CLI::log( '=== GOOGLE FEED ATTRIBUTES — theme ' . wp_get_theme()->get( 'Version' ) . ' ===' );

$theme_dir = get_template_directory();
$php_path  = $theme_dir . '/inc/google-feed.php';
$php_raw   = file_exists( $php_path ) ? file_get_contents( $php_path ) : '';

/* ───────────────────────────────────────────────────────────────────────────
 * §1  THE FILE LOADS AND THE FILTER IS REGISTERED.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§1  THE FILE, THE FUNCTIONS AND THE FILTER REGISTRATION' );

bhp_gfa_assert( 'inc/google-feed.php exists', file_exists( $php_path ), $php_path );
bhp_gfa_assert( 'functions.php requires inc/google-feed.php',
	false !== strpos( (string) file_get_contents( $theme_dir . '/functions.php' ), "/inc/google-feed.php" ) );
bhp_gfa_assert( 'bhp_google_feed_attributes() is defined', function_exists( 'bhp_google_feed_attributes' ) );
bhp_gfa_assert( 'bhp_google_feed_is_book() is defined', function_exists( 'bhp_google_feed_is_book' ) );
bhp_gfa_assert( 'the filter is registered on woocommerce_gla_product_attribute_values',
	false !== has_filter( 'woocommerce_gla_product_attribute_values', 'bhp_google_feed_attributes' ) );

/*
 * The allowlist MUST come from the registry, not from a second definition.
 * A SKU test in particular would silently match nothing on staging, whose
 * SKUs are still the old BHP-* codes while production carries ISBN-13s.
 */
bhp_gfa_assert( 'the allowlist keys on bhp_book_lookup_product(), not a re-derived rule',
	false !== strpos( $php_raw, 'bhp_book_lookup_product' ) );
bhp_gfa_assert( 'inc/book-formats.php is loaded before this file needs it',
	function_exists( 'bhp_book_lookup_product' ) );

/* ───────────────────────────────────────────────────────────────────────────
 * §2  THE THREE VALUES ARE EXACTLY WHAT WAS SPECIFIED.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§2  THE THREE ATTRIBUTE VALUES' );

bhp_gfa_assert( 'googleProductCategory === "Media > Books"',
	defined( 'BHP_GOOGLE_FEED_CATEGORY' ) && 'Media > Books' === BHP_GOOGLE_FEED_CATEGORY,
	defined( 'BHP_GOOGLE_FEED_CATEGORY' ) ? BHP_GOOGLE_FEED_CATEGORY : 'undefined' );
bhp_gfa_assert( 'condition === "new"',
	defined( 'BHP_GOOGLE_FEED_CONDITION' ) && 'new' === BHP_GOOGLE_FEED_CONDITION,
	defined( 'BHP_GOOGLE_FEED_CONDITION' ) ? BHP_GOOGLE_FEED_CONDITION : 'undefined' );
bhp_gfa_assert( 'brand === "Brave Hearts Publishing"',
	defined( 'BHP_GOOGLE_FEED_BRAND' ) && 'Brave Hearts Publishing' === BHP_GOOGLE_FEED_BRAND,
	defined( 'BHP_GOOGLE_FEED_BRAND' ) ? BHP_GOOGLE_FEED_BRAND : 'undefined' );

/* ───────────────────────────────────────────────────────────────────────────
 * §3  ⭐ THE ALLOWLIST. THE ASSERTION THAT MATTERS MOST IN THIS FILE.
 *
 * A digital PDF entering the Google feed is a genuine, merchant-caused ebooks
 * policy violation — the class that escalates from product disapproval to
 * ACCOUNT-level enforcement. If §3 regresses, that is the failure mode.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§3  THE ALLOWLIST — books in, everything else out' );

if ( ! function_exists( 'wc_get_product' ) ) {
	bhp_gfa_skip( '§3 entirely', 'WooCommerce is not active' );
} else {
	$books = [];
	foreach ( bhp_book_registry() as $key => $book ) {
		$books[ (int) $book['pb_product'] ] = $key . ' paperback (parent)';
		$books[ (int) $book['hc_product'] ] = $key . ' hardcover';
		if ( ! empty( $book['pb_variation'] ) ) {
			$books[ (int) $book['pb_variation'] ] = $key . ' paperback VARIATION — this is what the feed sends';
		}
	}

	foreach ( $books as $pid => $label ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			bhp_gfa_skip( "product {$pid} ({$label})", 'product not present on this environment' );
			continue;
		}
		bhp_gfa_assert( "product {$pid} IS a book — {$label}", true === bhp_google_feed_is_book( $p ) );
	}

	/*
	 * The downloadable Activity Book. Its ID differs per environment (538 on
	 * production, 833 on staging), so it is found by its SKU, which does not.
	 */
	$activity_id = (int) wc_get_product_id_by_sku( 'BHP-ACTIVITY-BOOK-01' );
	if ( $activity_id > 0 ) {
		$activity = wc_get_product( $activity_id );
		bhp_gfa_assert( "the downloadable Activity Book ({$activity_id}) is NOT a book for feed purposes",
			false === bhp_google_feed_is_book( $activity ) );
		bhp_gfa_assert( "the Activity Book gets NO feed attributes added",
			[] === bhp_google_feed_attributes( [], $activity ) );
		bhp_gfa_assert( "the Activity Book is still flagged downloadable (nothing here changed it)",
			$activity->is_downloadable() );
	} else {
		bhp_gfa_skip( 'Activity Book allowlist exclusion', 'SKU BHP-ACTIVITY-BOOK-01 not found here' );
	}

	// Defensive inputs must not fatal and must not opt anything in.
	bhp_gfa_assert( 'null product is not a book', false === bhp_google_feed_is_book( null ) );
	bhp_gfa_assert( 'a string is not a book', false === bhp_google_feed_is_book( 'nope' ) );
	bhp_gfa_assert( 'null product gets no attributes', [] === bhp_google_feed_attributes( [], null ) );
	bhp_gfa_assert( 'a non-array $attributes is normalised rather than fatalling',
		is_array( bhp_google_feed_attributes( null, null ) ) );
}

/* ───────────────────────────────────────────────────────────────────────────
 * §4  THE FILTER'S RETURN SHAPE, AND ITS DEFERENCE TO OTHER CALLBACKS.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§4  RETURN SHAPE AND PRECEDENCE' );

if ( ! function_exists( 'wc_get_product' ) ) {
	bhp_gfa_skip( '§4 entirely', 'WooCommerce is not active' );
} else {
	$reg      = bhp_book_registry();
	$first    = reset( $reg );
	$sample   = wc_get_product( (int) $first['hc_product'] );

	if ( ! $sample ) {
		bhp_gfa_skip( '§4 entirely', 'no sample book product on this environment' );
	} else {
		$out = bhp_google_feed_attributes( [], $sample );

		bhp_gfa_assert( 'a book receives googleProductCategory',
			isset( $out['googleProductCategory'] ) && 'Media > Books' === $out['googleProductCategory'] );
		bhp_gfa_assert( 'a book receives condition=new',
			isset( $out['condition'] ) && 'new' === $out['condition'] );
		bhp_gfa_assert( 'a book receives brand',
			isset( $out['brand'] ) && 'Brave Hearts Publishing' === $out['brand'] );
		bhp_gfa_assert( 'exactly three keys are added and nothing else',
			3 === count( $out ), 'keys: ' . implode( ',', array_keys( $out ) ) );

		/*
		 * ⛔ THE NEVER-INVENT GUARD. Zero real reviews exist on any product.
		 * Nothing in this file may ever put a rating into the feed.
		 */
		foreach ( [ 'aggregateRating', 'ratingValue', 'reviews', 'productReviews', 'sellerRating' ] as $forbidden ) {
			bhp_gfa_assert( "no {$forbidden} is ever emitted", ! isset( $out[ $forbidden ] ) );
		}

		/*
		 * ⛔ THE ANDREW-GATE GUARD. This filter must never touch commerce
		 * configuration. If someone later adds price/shipping/availability
		 * handling here, that is a gated change and this assertion fails first.
		 */
		foreach ( [ 'price', 'salePrice', 'shipping', 'availability', 'tax', 'link', 'title' ] as $gated ) {
			bhp_gfa_assert( "no {$gated} is overridden by this filter", ! isset( $out[ $gated ] ) );
		}

		// An existing value from another callback wins; this filter fills gaps.
		$pre = bhp_google_feed_attributes( [ 'brand' => 'Someone Else' ], $sample );
		bhp_gfa_assert( 'an already-set brand is NOT overwritten',
			'Someone Else' === $pre['brand'] );
		bhp_gfa_assert( 'the other two are still filled in alongside it',
			'Media > Books' === $pre['googleProductCategory'] && 'new' === $pre['condition'] );
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §5  ⭐ THE REAL OUTGOING PAYLOAD. The only section that proves what Google
 *     actually receives.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§5  THE REAL GLA PAYLOAD' );

$adapter_class = '\Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter';

if ( ! class_exists( $adapter_class ) ) {
	bhp_gfa_skip( '§5 entirely', 'Google Listings & Ads is not installed/active here' );
} elseif ( ! function_exists( 'wc_get_product' ) ) {
	bhp_gfa_skip( '§5 entirely', 'WooCommerce is not active' );
} else {
	// What the feed actually sends: the variation where one exists, else the product.
	$feed_ids = [];
	foreach ( bhp_book_registry() as $book ) {
		$feed_ids[] = ! empty( $book['pb_variation'] ) ? (int) $book['pb_variation'] : (int) $book['pb_product'];
		$feed_ids[] = (int) $book['hc_product'];
	}

	foreach ( $feed_ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			bhp_gfa_skip( "payload for {$pid}", 'product not present on this environment' );
			continue;
		}

		$args = [
			'wc_product'    => $p,
			'targetCountry' => 'US',
			'mapping_rules' => [],
			'gla_attributes' => [],
		];
		if ( $p instanceof WC_Product_Variation ) {
			$args['parent_wc_product'] = wc_get_product( $p->get_parent_id() );
		}

		try {
			$adapter = new $adapter_class( $args );
		} catch ( \Throwable $e ) {
			bhp_gfa_assert( "payload for {$pid} builds", false, $e->getMessage() );
			continue;
		}

		bhp_gfa_assert( "payload {$pid}: googleProductCategory = Media > Books",
			'Media > Books' === $adapter->getGoogleProductCategory(),
			var_export( $adapter->getGoogleProductCategory(), true ) );
		bhp_gfa_assert( "payload {$pid}: condition = new",
			'new' === $adapter->getCondition(),
			var_export( $adapter->getCondition(), true ) );
		bhp_gfa_assert( "payload {$pid}: brand = Brave Hearts Publishing",
			'Brave Hearts Publishing' === $adapter->getBrand(),
			var_export( $adapter->getBrand(), true ) );

		/*
		 * ⛔ THE REGRESSION GUARD ON THE OTHER FIX IN THIS PACKAGE. The image
		 * filenames were renamed on production because "ebook" in the image URL
		 * is the leading candidate cause of the disapproval. If a filename ever
		 * carries it again, this catches it in the payload rather than in a
		 * Merchant Center rejection three days later.
		 */
		$img = (string) $adapter->getImageLink();
		bhp_gfa_data_state( "payload {$pid}: imageLink carries no form of 'ebook'",
			'' !== $img && false === stripos( $img, 'ebook' ), $img );

		// The identifier must survive untouched — it is the healthy part of this feed.
		bhp_gfa_assert( "payload {$pid}: gtin is still a 13-digit ISBN",
			1 === preg_match( '/^\d{13}$/', (string) $adapter->getGtin() ),
			var_export( $adapter->getGtin(), true ) );
	}

	/*
	 * The downloadable Activity Book must be pinned OUT of the feed explicitly,
	 * not merely hidden by catalog visibility. GLA's get_channel_visibility()
	 * returns null when the meta is unset, and is_sync_ready() tests
	 * DONT_SYNC_AND_SHOW !== $visibility — null passes that test, so an unset
	 * product is sync-ELIGIBLE by default. This asserts the explicit pin.
	 */
	$activity_id = (int) wc_get_product_id_by_sku( 'BHP-ACTIVITY-BOOK-01' );
	if ( $activity_id > 0 ) {
		$vis = get_post_meta( $activity_id, '_wc_gla_visibility', true );
		bhp_gfa_data_state( "the Activity Book ({$activity_id}) is pinned to dont-sync-and-show, not merely hidden",
			'dont-sync-and-show' === $vis,
			var_export( $vis, true ) . ' — an empty value means it is sync-eligible BY DEFAULT and is kept out of the feed only by catalog-visibility exclusion, which is a side effect and not a control' );
	} else {
		bhp_gfa_skip( 'Activity Book GLA pin', 'SKU BHP-ACTIVITY-BOOK-01 not found here' );
	}
}

/* ───────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( sprintf(
	'=== %d passed, %d failed, %d skipped  (environment: %s) ===',
	$GLOBALS['bhp_gfa_pass'],
	$GLOBALS['bhp_gfa_fail'],
	$GLOBALS['bhp_gfa_skip'],
	bhp_gfa_is_production() ? 'PRODUCTION — DATA lines are hard assertions' : 'NON-PRODUCTION — DATA lines report only, read them'
) );
WP_CLI::log( '' );

if ( $GLOBALS['bhp_gfa_fail'] > 0 ) {
	WP_CLI::error( 'Google feed attribute suite FAILED.' );
}
