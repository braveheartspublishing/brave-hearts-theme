<?php
/**
 * THE CATALOG SURFACES, SITEWIDE. Theme 1.19.350. `CYCLE179-LD-350-BUILD`.
 * Founder scope, seal 691; concept seal 686; visit additions seal 698.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-catalog-350.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE A FOLD. PHP has no viewport. Nothing here shows that five
 *    cards clear 768px, that the band is 96px, or that a parent can see a buy
 *    button. ⭐ THAT PROOF IS THE BROWSER, at asserted `window.innerWidth` and
 *    `window.innerHeight`, and it is the PRIMARY evidence for this release. It
 *    is filed at `pdp-redesign\350-STAGING\AFTER-350-measurements.json` with a
 *    screenshot per surface per viewport.
 *
 * ⛔ IT CANNOT PROVE THE RECURSION FIX EITHER, AND THAT IS WORTH STATING. The
 *    first 1.19.350 build filtered `woocommerce_get_price_html` from a function
 *    that reads `get_price_html()`, which is infinitely recursive. `php -l`
 *    passed on every file. The page fatalled with "Allowed memory size of
 *    805306368 bytes exhausted". ⭐ A LINT CANNOT SEE A HOOK CYCLE. §7 below
 *    fetches the real document and asserts it rendered, which is the cheapest
 *    gate that would have caught it.
 *
 * WHAT IT ASSERTS
 *   §1  the predicate exists, excludes the PDP, and is filterable
 *   §2  the WooCommerce chrome is REMOVED from a catalog grid, not hidden
 *   §3  the reading order is a QUERY, and no `menu_order` was written
 *   §4  the proof blocks MOVED and were not deleted or reworded
 *   §5  one price once: the selected chip carries a tick, not a repeat
 *   §6  the visit band, the order-by line and the closed state
 *   §7  the served documents: /shop/, an archive, search, and F-01's 301
 *   §8  the ship-to-home confirmation step, and the note parity
 *   §9  the free-PDF v2 asset swap
 *   §10 the standing rails on every new customer-facing string
 *   §11 the shipped CSS artefact carries the catalog scope and is fresh
 *
 * @package Brave_Hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. Same reason, same
 *    fix, as `test-cycle179-pdp-349.php` and `test-shop-grid-2up-204.php`.
 */
$GLOBALS['c350_failures'] = 0;
$GLOBALS['c350_passes']   = 0;
function c350_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['c350_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['c350_failures']++;
		echo "FAIL: $label\n";
	}
}

/** Fetch a URL on this environment. Returns array(code, body). */
function c350_get( $url ) {
	$resp = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false, 'redirection' => 0 ) );
	if ( is_wp_error( $resp ) ) {
		return array( 0, '', '' );
	}
	return array(
		(int) wp_remote_retrieve_response_code( $resp ),
		(string) wp_remote_retrieve_body( $resp ),
		(string) wp_remote_retrieve_header( $resp, 'location' ),
	);
}

/** Read a shipped file, whitespace flattened, or '' when missing. */
function c350_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? preg_replace( '/\s+/', ' ', (string) file_get_contents( $path ) ) : '';
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ READ A FILE'S **CODE**, WITH EVERY COMMENT REMOVED. THIS EXISTS BECAUSE
 *     THE FIRST VERSION OF THIS SUITE FAILED SEVEN TIMES ON A CORRECT BUILD.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE DEFECT, RECORDED RATHER THAN QUIETLY PATCHED. Seven assertions in this
 *    file are ABSENCE assertions: "the catalog file never mentions
 *    `menu_order`", "no filter on `woocommerce_get_price_html`", "the
 *    superseded wording is retired". Every one of them scanned the raw source —
 *    and this codebase's house style is to QUOTE THE SUPERSEDED CODE AND THE
 *    FORBIDDEN PATTERN IN A COMMENT, deliberately, so the movement stays
 *    visible and is not re-derived. So each assertion found the very literal it
 *    was forbidding, inside the comment explaining why it is forbidden, and
 *    reported a correct build as seven failures.
 *
 * ⛔ THAT IS THE EXACT TEST-DEFECT CLASS `test-shop-grid-2up-204.php` §4.3
 *    already records: *"A gate that fires on the right answer is worse than no
 *    gate — the next agent 'fixes' the code."* Left alone, the next reader would
 *    have deleted seven accurate explanatory comments to make a suite go green.
 *
 * ⭐ `token_get_all()` IS THE CORRECT INSTRUMENT AND A REGEX IS NOT. PHP's own
 *    lexer knows the difference between `//` inside a string and a comment;
 *    a regex does not, and a regex that got it wrong would silently strip real
 *    code and start passing assertions that should fail.
 *
 * @param string $rel Theme-relative path.
 * @return string Source with all comments removed, or '' when missing.
 */
function c350_code( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	if ( ! file_exists( $path ) ) {
		return '';
	}
	$out = '';
	foreach ( token_get_all( (string) file_get_contents( $path ) ) as $tok ) {
		if ( is_array( $tok ) ) {
			if ( T_COMMENT === $tok[0] || T_DOC_COMMENT === $tok[0] ) {
				continue;
			}
			$out .= $tok[1];
			continue;
		}
		$out .= $tok;
	}
	return $out;
}

$c350_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';

echo "\n=== §1 · THE PREDICATE ===\n";

c350_assert( function_exists( 'bhp_catalog_grid_context' ), '1.1 bhp_catalog_grid_context() exists' );
c350_assert( function_exists( 'bhp_shop_card_context' ), '1.2 the 1.19.286 name still exists (suites bind to it)' );

/*
 * ⛔⛔ THE ONE ASSERTION IN THIS FILE THAT PROTECTS A SURFACE NOBODY RULED ON.
 *    `.woo-expedition-shell ul.products li.product` is ALSO the product page's
 *    related and upsell rows. 1.19.286 found in a real browser that giving them
 *    the shop control put two live add-to-cart buttons on a page meant to have
 *    one. The predicate must return FALSE there, and it must do so BEFORE it
 *    tests anything else. ⭐ Driven through the filter because a WP-CLI run has
 *    no query to make `is_product()` true for.
 */
$c350_pdp_guard = false;
add_filter( 'bhp_catalog_grid_context', function ( $v ) {
	return $v;
}, 5 );
c350_assert(
	false === bhp_catalog_grid_context(),
	'1.3 ⛔ the predicate is FALSE outside any catalog request (WP-CLI has no query)'
);
add_filter( 'bhp_catalog_grid_context', '__return_true', 99 );
c350_assert( true === bhp_catalog_grid_context(), '1.4 ⭐ the predicate is filterable, so a suite can reach the true branch' );
c350_assert( true === bhp_shop_card_context(), '1.5 ⭐ bhp_shop_card_context() delegates to it (one predicate, two names)' );
remove_filter( 'bhp_catalog_grid_context', '__return_true', 99 );
unset( $c350_pdp_guard );

/*
 * ⛔ THE SOURCE IS READ FOR ONE THING ONLY: that `is_product()` is tested
 *    FIRST. Order is the whole guarantee here and no runtime call in WP-CLI can
 *    demonstrate it.
 */
/* ⭐ RAW source for the ORDER assertion below (comments do not affect it), and
   COMMENT-STRIPPED code for every ABSENCE assertion in this file. See the long
   note on `c350_code()`. */
$c350_src  = c350_read( 'inc/catalog-surfaces.php' );
$c350_code = c350_code( 'inc/catalog-surfaces.php' );
c350_assert(
	'' !== $c350_src
		&& preg_match( '/function bhp_catalog_grid_context\(\).*?is_product\(\).*?return false;.*?is_shop\(\)/s', $c350_src ),
	'1.6 ⛔ is_product() short-circuits BEFORE is_shop() in the predicate body'
);

echo "\n=== §2 · THE WOOCOMMERCE CHROME IS REMOVED, NOT HIDDEN ===\n";

c350_assert( function_exists( 'bhp_catalog_remove_loop_chrome' ), '2.1 the chrome remover exists' );
c350_assert(
	false !== has_action( 'wp', 'bhp_catalog_remove_loop_chrome' ),
	'2.2 …and it is hooked on `wp`, late enough that every file has registered'
);
c350_assert(
	'' !== $c350_code && false === strpos( $c350_code, 'display:none' ) && false === strpos( $c350_code, 'display: none' ),
	'2.3 ⛔ REMOVED, NOT HIDDEN: the catalog file\'s CODE contains no display:none for the chrome'
);

echo "\n=== §3 · READING ORDER IS A QUERY, NEVER A PRODUCT WRITE ===\n";

c350_assert( function_exists( 'bhp_catalog_reading_order_ids' ), '3.1 the reading-order helper exists' );
$c350_ids = bhp_catalog_reading_order_ids();
c350_assert( count( $c350_ids ) >= 3, sprintf( '3.2 it resolves at least the three chapter books (%d ids)', count( $c350_ids ) ) );
if ( function_exists( 'bhp_book_registry' ) ) {
	$c350_expected = array();
	foreach ( bhp_book_registry() as $b ) {
		$c350_expected[] = (int) $b['pb_product'];
	}
	c350_assert(
		array_slice( $c350_ids, 0, count( $c350_expected ) ) === $c350_expected,
		'3.3 ⭐ the order is the REGISTRY\'s own order, read not typed (Book 1, Book 2, Book 3)'
	);
}
/*
 * ⛔⛔ THE GATE THAT MATTERS MOST IN THIS FILE. Ordering by editing `menu_order`
 *    on the product records is a WOOCOMMERCE PRODUCT MUTATION and is Andrew's
 *    gate on staging as well as production. Nothing in this release may write
 *    one, so the release asserts it rather than promising it.
 */
c350_assert(
	'' !== $c350_code && false === stripos( $c350_code, 'menu_order' ),
	'3.4 ⛔⛔ NO PRODUCT MUTATION: the catalog CODE never touches menu_order'
);
/*
 * ⛔ `update_post_meta_cache` IS EXCLUDED FROM THIS SCAN BY NAMING IT, not by
 *    loosening the pattern. It appears in the F-01 `get_posts()` arguments as
 *    `'update_post_meta_cache' => false`, which is a READ optimisation that
 *    tells WordPress NOT to prime the meta cache. It is the opposite of a write
 *    and it contains the forbidden substring, so the scan removes it first and
 *    then looks for the real thing.
 */
$c350_write_scan = str_replace( 'update_post_meta_cache', '', $c350_code );
c350_assert(
	'' !== $c350_code
		&& false === strpos( $c350_write_scan, 'update_post_meta' )
		&& false === strpos( $c350_write_scan, 'wp_update_post' )
		&& false === strpos( $c350_write_scan, 'wp_insert_post' )
		&& false === strpos( $c350_write_scan, 'update_option' ),
	'3.5 ⛔⛔ NO WRITE AT ALL: no update_post_meta(), wp_update_post(), wp_insert_post() or update_option()'
);

echo "\n=== §4 · THE PROOF BLOCKS MOVED. THEY WERE NOT DELETED OR REWORDED ===\n";

c350_assert( function_exists( 'bhp_catalog_trust_strip' ), '4.1 the trust strip exists' );
c350_assert(
	false !== has_action( 'woocommerce_after_shop_loop', 'bhp_catalog_trust_strip' ),
	'4.2 …and it renders AFTER the loop, never between a price and a button'
);
/*
 * ⛔⛔ RELOCATE, NEVER DELETE. Standing Rules §3 (never invent) and §9.1a (never
 *    rewrite a word inside a quoted third-party statement) both bind. One live
 *    quote reads "We read a few chapters each night" — that "we" is a
 *    CUSTOMER'S word and correcting it to the founder's voice would fabricate a
 *    customer statement. ⭐ Both components must still exist and still render.
 */
c350_assert( function_exists( 'bhp_render_amazon_review_showcase' ), '4.3 ⭐ the review component still exists' );
c350_assert( function_exists( 'bhp_render_kirkus_credibility' ), '4.4 ⭐ the Kirkus component still exists' );
c350_assert(
	function_exists( 'bhp_woocommerce_loop_amazon_review_badge' ) && function_exists( 'bhp_woocommerce_loop_kirkus_badge' ),
	'4.5 ⛔ the card-level renderers were UNHOOKED, not deleted (they still exist)'
);
c350_assert(
	'' !== $c350_code && false !== strpos( $c350_code, "remove_action('woocommerce_after_shop_loop_item_title', 'bhp_woocommerce_loop_amazon_review_badge', 20)" ),
	'4.6 ⭐ the relocation is a remove_action, so the wording cannot be touched by it'
);

echo "\n=== §5 · ONE PRICE, ONCE ===\n";

if ( function_exists( 'bhp_book_shop_format_prices' ) && function_exists( 'bhp_book_registry' ) ) {
	$c350_first = array_key_first( bhp_book_registry() );
	$c350_fmts  = bhp_book_shop_format_prices( $c350_first );
	c350_assert( count( $c350_fmts ) >= 1, sprintf( '5.1 the format list resolves for "%s" (%d formats)', $c350_first, count( $c350_fmts ) ) );
	c350_assert(
		isset( $c350_fmts[0]['selected'] ) && true === $c350_fmts[0]['selected'],
		'5.2 ⭐ the PAPERBACK is the selected format (FD-439), so the big figure is its price'
	);
	if ( isset( $c350_fmts[1] ) ) {
		c350_assert(
			isset( $c350_fmts[1]['selected'] ) && false === $c350_fmts[1]['selected'],
			'5.3 ⭐ the hardcover chip is NOT selected, so it carries the only other figure'
		);
	}
}
c350_assert( function_exists( 'bhp_catalog_price_lead' ), '5.4 the "From" lead exists' );
/*
 * ⛔⛔ THE RECURSION GATE. The superseded implementation filtered
 *    `woocommerce_get_price_html` from a function that calls `get_price_html()`.
 *    It shipped, fatalled the page and exhausted 768MB. It must never come back.
 */
c350_assert(
	'' !== $c350_code && false === strpos( $c350_code, "add_filter('woocommerce_get_price_html'" )
		&& false === strpos( $c350_code, 'add_filter( \'woocommerce_get_price_html\'' ),
	'5.5 ⛔⛔ NO FILTER ON woocommerce_get_price_html in the CODE (it recurses through bhp_book_purchase_data)'
);
c350_assert(
	false !== has_action( 'woocommerce_after_shop_loop_item_title', 'bhp_catalog_price_lead' ),
	'5.6 ⭐ the lead is an element on the loop hook instead, one priority ahead of the price'
);

echo "\n=== §6 · THE SCHOOL-VISIT BAND ===\n";

c350_assert( function_exists( 'bhp_visit_band_state' ), '6.1 the band state resolver exists' );
c350_assert( function_exists( 'bhp_visit_band_order_by_line' ), '6.2 the order-by line builder exists' );
c350_assert( function_exists( 'bhp_visit_band_render' ), '6.3 the band renderer exists' );

if ( function_exists( 'bhp_school_visit_last_order_date' ) && function_exists( 'bhp_visit_band_order_by_line' ) ) {
	/*
	 * ⭐ A SYNTHETIC RECORD, NOT A REGISTRY ROW. ⛔ This suite writes NOTHING:
	 *    no option, no session, no registry row, no visit. The record below is a
	 *    local array, and `bhp_school_visit_last_order_date()` is pure
	 *    arithmetic on a date string.
	 */
	$c350_far  = array( 'school' => 'Test School', 'date' => gmdate( 'Y-m-d', strtotime( '+30 days' ) ), 'cutoff' => gmdate( 'Y-m-d', strtotime( '+27 days' ) ) );
	$c350_line = bhp_visit_band_order_by_line( $c350_far );
	c350_assert( '' !== $c350_line && false !== strpos( $c350_line, 'Order by' ), '6.4 a future visit renders an "Order by <date>" line' );
	c350_assert( false === strpos( $c350_line, 'today' ), '6.5 …and it does NOT say "today" when it is not today' );

	/* ⭐ `R3`: on the last order date the sentence says so in the word. Visit
	   date + 2 makes today the last order date, by the plugin's own arithmetic. */
	$c350_today = function_exists( 'bhp_school_visit_today' ) ? bhp_school_visit_today() : gmdate( 'Y-m-d' );
	$c350_near  = array( 'school' => 'Test School', 'date' => gmdate( 'Y-m-d', strtotime( $c350_today . ' +2 days' ) ), 'cutoff' => $c350_today );
	$c350_line2 = bhp_visit_band_order_by_line( $c350_near );
	c350_assert( false !== strpos( $c350_line2, 'today' ), '6.6 ⭐ R3: on the last order date the line says "today", not a bare date' );

	/* ⛔ FAILS CLOSED. An unusable date prints no sentence rather than a wrong one. */
	c350_assert( '' === bhp_visit_band_order_by_line( array( 'school' => 'X', 'date' => 'not-a-date' ) ), '6.7 ⛔ FAILS CLOSED: an unusable date prints no line at all' );
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ §6a · 1.19.350-FIX — ONE DEADLINE, AND THE STANDING GATES THAT KEEP IT
 *       THAT WAY. `CYCLE179-LD-350-FIX` fix 1.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THESE ARE REGRESSION GATES, NOT A DESCRIPTION. Each one fails the build if
 *    a future release reintroduces the defect it names. ⛔ Every record below is
 *    a LOCAL ARRAY: this section writes no option, no registry row, no session
 *    and no visit, and `bhp_visit_deadline_display()` is pure arithmetic on two
 *    date strings.
 */
echo "\n=== §6a · THE ONE DEADLINE (350-FIX fix 1) ===\n";

c350_assert( function_exists( 'bhp_visit_deadline_display' ), '6a.1 the single deadline resolver exists' );

if ( function_exists( 'bhp_visit_deadline_display' ) ) {

	/* ⭐ THE CONVENTIONAL ROW: cutoff = visit - 3, which is BEFORE the online
	   close. The stated deadline wins, so `/author-visits/` prints exactly what
	   it printed before this change and the grace window stays unadvertised. */
	c350_assert(
		'2026-09-11' === bhp_visit_deadline_display( array( 'date' => '2026-09-14', 'cutoff' => '2026-09-11' ) ),
		'6a.2 ⭐ a visit-3 row prints its STATED cutoff, unchanged — the emailed date'
	);

	/* ⛔⛔ THE GATE, RE-ASSERTED IN THIS SUITE RATHER THAN ASSUMED FROM THE
	   BRIEF. The 350-FIX brief stated that the order gate reads the registry
	   `cutoff`. IT DOES NOT: `bhp_school_visit_is_open_on()` routes to
	   `bhp_school_visit_last_order_date()`, which is `visit - 2` and never reads
	   `cutoff`. This assertion fails the build if that ever stops being true, so
	   the misreading cannot be re-derived from the code's behaviour. */
	if ( function_exists( 'bhp_school_visit_is_open_on' ) ) {
		c350_assert(
			true === bhp_school_visit_is_open_on( '2026-09-14', '2026-09-12' )
				&& false === bhp_school_visit_is_open_on( '2026-09-14', '2026-09-13' ),
			'6a.3 ⛔⛔ THE GATE IS visit-2 AND IGNORES cutoff — open on the 12th, shut on the 13th, for a visit on the 14th'
		);
	}

	/* ⭐⭐ THE LIVE PRODUCTION DEFECT, AS A PERMANENT GATE. The production
	   `liberty-2026-09-04` row carries cutoff `2026-09-03` = visit - 1, which is
	   AFTER the online close of `2026-09-02`. Printing it would promise a day
	   the button has already refused. */
	c350_assert(
		'2026-09-02' === bhp_visit_deadline_display( array( 'date' => '2026-09-04', 'cutoff' => '2026-09-03' ) ),
		'6a.4 ⭐⭐ a cutoff LATER than the online close is clamped to the close — no surface promises a day the site refuses'
	);

	/* ⛔⛔ THE FOUNDER RULE, EXPRESSED AS A PROPERTY RATHER THAN AS A CASE. The
	   grace window is never advertised, so the printed deadline can NEVER be
	   later than the stated cutoff — under any row, including ones nobody has
	   entered yet. Swept across a year of visit dates and every cutoff offset
	   from visit-7 to visit+2. */
	$c350_dl_violations = 0;
	for ( $c350_dl_i = 0; $c350_dl_i < 60; $c350_dl_i++ ) {
		$c350_dl_visit = gmdate( 'Y-m-d', strtotime( '2026-03-01 +' . ( $c350_dl_i * 6 ) . ' days' ) );
		for ( $c350_dl_off = -7; $c350_dl_off <= 2; $c350_dl_off++ ) {
			$c350_dl_cut = gmdate( 'Y-m-d', strtotime( $c350_dl_visit . ' ' . $c350_dl_off . ' days' ) );
			$c350_dl_got = bhp_visit_deadline_display( array( 'date' => $c350_dl_visit, 'cutoff' => $c350_dl_cut ) );
			if ( $c350_dl_got > $c350_dl_cut ) {
				$c350_dl_violations++;
			}
			if ( $c350_dl_got > bhp_school_visit_last_order_date( $c350_dl_visit ) ) {
				$c350_dl_violations++;
			}
		}
	}
	c350_assert(
		0 === $c350_dl_violations,
		sprintf( '6a.5 ⛔⛔ ACROSS 600 ROWS: the printed deadline is NEVER later than the stated cutoff (no grace window advertised) and NEVER later than the online close (no refused promise). Violations: %d', $c350_dl_violations )
	);

	/* ⛔ FAILS CLOSED, and a row with no cutoff at all still yields the close
	   rather than an empty sentence. */
	c350_assert( '' === bhp_visit_deadline_display( array( 'date' => 'not-a-date', 'cutoff' => '2026-09-01' ) ), '6a.6 ⛔ FAILS CLOSED: an unusable visit date yields no deadline at all' );
	c350_assert( '2026-09-12' === bhp_visit_deadline_display( array( 'date' => '2026-09-14' ) ), '6a.7 a row carrying no cutoff falls back to the online close' );

	/* ⚠️ THE GRACE DAY GOES SILENT rather than printing a passed date as a
	   future deadline or printing the gate date and advertising the window. */
	if ( function_exists( 'bhp_visit_band_order_by_line' ) && function_exists( 'bhp_school_visit_today' ) ) {
		$c350_dl_today = (string) bhp_school_visit_today();
		$c350_dl_grace = array(
			'school' => 'Grace Day Elementary',
			'date'   => gmdate( 'Y-m-d', strtotime( $c350_dl_today . ' +2 days' ) ),   // close is TODAY
			'cutoff' => gmdate( 'Y-m-d', strtotime( $c350_dl_today . ' -1 day' ) ),    // stated deadline passed
		);
		c350_assert(
			'' === bhp_visit_band_order_by_line( $c350_dl_grace ),
			'6a.8 ⚠️ on the grace day the band prints NO deadline sentence — a passed date is not printed as a future one, and the gate date is not printed at all'
		);
	}
}

/*
 * ⭐⭐ `/author-visits/` READS THE SAME FUNCTION. This is the assertion that
 *    makes "one source of truth" a mechanical property instead of a claim: the
 *    row the page renders from carries the resolver's own output.
 */
if ( function_exists( 'bhp_author_visits_build_rows' ) && function_exists( 'bhp_visit_deadline_display' ) ) {
	$c350_av_fixture = array(
		/* ⛔ `slug` lives INSIDE the record, not in the array key —
		   `bhp_author_visits_build_rows()` re-sanitises every field it is handed
		   and DROPS a row with no slug, exactly as the plugin's own sanitiser
		   does. A fixture keyed by slug alone silently yields zero rows. */
		'c350-conventional' => array( 'slug' => 'c350-conventional', 'school' => 'Conventional Elementary', 'date' => '2026-09-14', 'cutoff' => '2026-09-11', 'time' => '9:00 AM' ),
		'c350-lateoff'      => array( 'slug' => 'c350-lateoff',      'school' => 'Late Cutoff Elementary',  'date' => '2026-09-04', 'cutoff' => '2026-09-03', 'time' => '9:00 AM' ),
	);
	$c350_av_rows = bhp_author_visits_build_rows( $c350_av_fixture, '2026-09-01' );
	$c350_av_by   = array();
	foreach ( $c350_av_rows as $c350_av_r ) {
		$c350_av_by[ $c350_av_r['slug'] ] = $c350_av_r;
	}

	c350_assert( isset( $c350_av_by['c350-conventional']['deadline'] ), '6a.9 the /author-visits/ row carries a `deadline` field' );
	c350_assert(
		isset( $c350_av_by['c350-conventional']['deadline'] ) && '2026-09-11' === $c350_av_by['c350-conventional']['deadline'],
		'6a.10 ⭐ a conventional row prints its stated cutoff on /author-visits/, byte-identical to before the fix'
	);
	c350_assert(
		isset( $c350_av_by['c350-lateoff']['deadline'] ) && '2026-09-02' === $c350_av_by['c350-lateoff']['deadline'],
		'6a.11 ⭐⭐ a late-cutoff row prints the ONLINE CLOSE on /author-visits/ too — the shop band and this page cannot state different deadlines'
	);
	c350_assert(
		isset( $c350_av_by['c350-lateoff']['cutoff'] ) && '2026-09-03' === $c350_av_by['c350-lateoff']['cutoff'],
		'6a.12 ⛔ the registry `cutoff` itself is UNTOUCHED and still carried on the row — this fix changes a display, never the data'
	);

	/* ⛔ THE PAGE TEMPLATE ACTUALLY PRINTS `deadline`, NOT `cutoff`. A source
	   scan, because a row field nothing renders would pass every test above. */
	$c350_av_tpl = @file_get_contents( get_template_directory() . '/page-author-visits.php' );
	if ( is_string( $c350_av_tpl ) && '' !== $c350_av_tpl ) {
		c350_assert(
			2 === substr_count( $c350_av_tpl, "\$bhp_row['deadline']" ),
			'6a.13 ⛔ BOTH the open and the closed "Order by" lines render `deadline`'
		);
		c350_assert(
			false === strpos( $c350_av_tpl, "format_date( \$bhp_row['cutoff'] )" ),
			'6a.14 ⛔ …and NEITHER renders the raw `cutoff` any more'
		);
	}
}

/*
 * ⭐⭐ §6b · THE AGE LINE, BOTH VIEWPORTS. `CYCLE179-LD-350-FIX` fix 2, seal 688.
 *
 * ⛔ A CSS SOURCE SCAN, and it is the right instrument for this one defect: the
 *    element has always been in the DOM, so a markup assertion passes whether
 *    the words are visible or measuring 0×0. What broke was the stylesheet.
 */
echo "\n=== §6b · THE AGE LINE ON THE CATALOG CARD (350-FIX fix 2) ===\n";

c350_assert( function_exists( 'bhp_shop_card_age_range' ), '6b.1 the age-range resolver exists' );
c350_assert(
	function_exists( 'bhp_shop_card_age_range' ) && false !== strpos( bhp_shop_card_age_range( 0 ), '6' ),
	'6b.2 ⛔ it resolves to the standing approved band and coins no new claim'
);
/* ⛔ RAIL §9.1: ages 6-9, NEVER 5-9. */
c350_assert(
	function_exists( 'bhp_shop_card_age_range' ) && false === strpos( bhp_shop_card_age_range( 0 ), '5' ),
	'6b.3 ⛔⛔ the band is never 5-9 (standing rules §9)'
);

$c350_age_css = @file_get_contents( get_template_directory() . '/style.css' );
if ( is_string( $c350_age_css ) && '' !== $c350_age_css ) {
	/*
	 * ⚠️⚠️ 1.19.352 (`CYCLE179-LD-352`) - §6b.4 IS REPLACED, AND THE SUPERSEDED
	 *    ASSERTION IS PRESERVED VERBATIM IMMEDIATELY BELOW RATHER THAN DELETED.
	 *
	 * ⭐ WHY IT MOVED: it asserted that NO rule anywhere hides the age line.
	 *    the chief-of-staff review (seal 759 ruling 3) then ruled the opposite for ONE viewport:
	 *    the line "stays on the MOBILE card and is HIDDEN on the desktop card
	 *    (display:none, no zero-height ghost)". The assertion below was
	 *    therefore a STALE ASSERTION OF AN INTENDED CHANGE, not a caught defect,
	 *    and a build could not satisfy both it and the ruling.
	 *
	 * ⛔ THE PROPERTY UNDER TEST IS NOT WEAKENED, IT IS SPLIT BY VIEWPORT. The
	 *    original guarded "the words must render somewhere". They still must,
	 *    on mobile, and §6b.4a now asserts exactly that - so the 1.19.350
	 *    subtraction still cannot silently return. §6b.4b and §6b.4c add what
	 *    the ruling actually requires and the old assertion never tested: that
	 *    the desktop hide is `display: none` and NOT a zero-height ghost.
	 *
	 * ```
	 * SUPERSEDED 2026-09-02 (seal 759 ruling 3) - asserted no rule may hide it
	 * c350_assert(
	 *     ! preg_match( '/li\.product \.bhp-shop-ages \{\s*display:\s*none/', $c350_age_css ),
	 *     '6b.4 ⛔⛔ NO rule hides the age line on the catalog card — the 1.19.350 mobile subtraction is reversed'
	 * );
	 * ```
	 */
	/* ⛔ MOBILE STILL SHOWS IT. The ≤640px rule comes FIRST in the stylesheet,
	   so the first of the two catalog age rules is the mobile one. */
	preg_match_all(
		'/li\.product \.bhp-shop-ages \{\s*display:\s*(none|block)/',
		$c350_age_css,
		$c350_age_disp
	);
	c350_assert(
		isset( $c350_age_disp[1][0] ) && 'block' === $c350_age_disp[1][0],
		'6b.4a ⛔⛔ the MOBILE catalog age rule still renders the line (display:block) - the 1.19.350 subtraction cannot return'
	);
	/* ⛔ DESKTOP HIDES IT, per the ruling, and hides it the ONE way that leaves
	   nothing in the box tree. */
	c350_assert(
		isset( $c350_age_disp[1][1] ) && 'none' === $c350_age_disp[1][1],
		'6b.4b ⭐ the DESKTOP catalog age rule hides the line with display:none (seal 759 ruling 3)'
	);
	/*
	 * ⛔⛔ AND IT IS NOT A ZERO-HEIGHT GHOST. This is the assertion the ruling
	 *    turns on: `height:0`, `visibility:hidden`, `opacity:0` and
	 *    `font-size:0` all leave a 0×0 box that a measuring rig and assistive
	 *    technology still see, which is the seal 688 defect itself.
	 */
	if ( preg_match( '/@media \(min-width: 641px\) \{\s*body\.bhp-catalog-grid[^}]*\.bhp-shop-ages \{([^}]*)\}/', $c350_age_css, $c350_age_desk ) ) {
		c350_assert(
			! preg_match( '/height:\s*0|visibility:\s*hidden|opacity:\s*0|font-size:\s*0/', $c350_age_desk[1] ),
			'6b.4c ⛔⛔ the desktop hide leaves NO zero-height ghost - no height:0, visibility:hidden, opacity:0 or font-size:0'
		);
	} else {
		c350_assert( false, '6b.4c ⛔ the min-width:641px catalog age rule could not be located for the ghost check' );
	}
	c350_assert(
		2 === preg_match_all( '/li\.product \.bhp-shop-ages \{/', $c350_age_css, $c350_age_m ),
		'6b.5 exactly two catalog age rules remain: one for ≤640px, one for ≥641px'
	);
	c350_assert(
		false !== strpos( $c350_age_css, '@media (min-width: 641px)' ),
		'6b.6 ⭐ the desktop rule is inside min-width:641px, so it cannot overwrite the approved mobile geometry'
	);
}

/*
 * ⭐⭐ `R4`, THE CLOSED STATE, AND IT IS EXERCISED THROUGH THE THEME'S OWN TEST
 *    SEAM. ⛔ The alternative — adding a registry row dated in the past — is an
 *    option write and this desk does not make one. The seam changes what is
 *    PRINTED and nothing else: it opens no gate, grants no entitlement, renders
 *    no counter and changes no shipping method.
 */
add_filter( 'bhp_catalog_grid_context', '__return_true', 99 );
add_filter( 'bhp_visit_band_state', function () {
	return array(
		'state'  => 'closed',
		'record' => array( 'school' => 'Testing Elementary', 'date' => '2026-01-05', 'cutoff' => '2026-01-02' ),
	);
}, 99 );
ob_start();
bhp_visit_band_render();
$c350_closed = (string) ob_get_clean();
remove_all_filters( 'bhp_visit_band_state' );
c350_assert( false !== strpos( $c350_closed, 'bhp-visit-band--closed' ), '6.8 ⭐⭐ R4: a closed visit renders the CLOSED band, not the ordinary storefront' );
c350_assert( false !== strpos( $c350_closed, 'Testing Elementary' ), '6.9 …naming the school' );
c350_assert( false !== strpos( $c350_closed, 'closed on' ), '6.10 …and stating the date ordering closed' );
c350_assert( false !== strpos( $c350_closed, 'still be ordered' ), '6.11 …and the remaining route, without a promise the order cannot keep' );

/* ⛔ `R11`: an ordinary shopper's HTML carries no trace. Not a hidden element,
   not an empty span. */
ob_start();
bhp_visit_band_render();
$c350_none = (string) ob_get_clean();
remove_filter( 'bhp_catalog_grid_context', '__return_true', 99 );
c350_assert( '' === trim( $c350_none ), '6.12 ⛔ R11: with no visit, the band emits ZERO bytes' );

echo "\n=== §7 · THE SERVED DOCUMENTS ===\n";

if ( '' !== $c350_shop_url ) {
	list( $c350_code, $c350_doc ) = c350_get( $c350_shop_url );
	c350_assert( 200 === $c350_code, sprintf( '7.1 ⛔⛔ /shop/ RENDERS (HTTP %d) — the gate the recursion fatal would have failed', $c350_code ) );
	if ( 200 === $c350_code && '' !== $c350_doc ) {
		c350_assert( false === strpos( $c350_doc, 'critical error' ), '7.2 ⛔ …and it is not WordPress\'s fatal-error page' );
		c350_assert( false !== strpos( $c350_doc, 'bhp-catalog-grid' ), '7.3 the catalog body class is on the page' );
		c350_assert( false !== strpos( $c350_doc, 'bhp-catalog-band' ), '7.4 the one-line band renders' );
		c350_assert( false !== strpos( $c350_doc, 'The Expedition Catalog' ), '7.5 ⛔ the H1 WORDS are unchanged (founder default, seal 704)' );
		c350_assert( false === strpos( $c350_doc, 'woocommerce-result-count' ), '7.6 ⭐ the false result count is GONE from the DOM' );
		c350_assert( false === strpos( $c350_doc, 'woocommerce-ordering' ), '7.7 ⭐ the sort select is GONE from the DOM' );
		c350_assert( false !== strpos( $c350_doc, 'bhp-catalog-bundle-strip' ), '7.8 the bundle strip renders below the grid' );
		c350_assert( 1 === substr_count( $c350_doc, 'Book 1 of 3' ), '7.9 ⭐ the eyebrow names the position in the series, once' );
		/* ⛔ NO FABRICATED EVIDENCE. Inspected in the RENDERED schema block. */
		if ( preg_match( '#<script class="rank-math-schema"[^>]*>(.*?)</script>#s', $c350_doc, $c350_sch ) ) {
			c350_assert( false === strpos( $c350_sch[1], 'aggregateRating' ), '7.10 ⛔ no aggregateRating in the RENDERED schema block' );
			c350_assert( false === strpos( $c350_sch[1], '"review"' ), '7.11 ⛔ no review schema in the RENDERED schema block' );
		}
	}
}

/* ⭐⭐ F-01. The archive the hide-hardcovers filter empties must 301 to /shop/. */
$c350_hc = home_url( '/product-category/hardcover-books/' );
list( $c350_hc_code, , $c350_hc_loc ) = c350_get( $c350_hc );
c350_assert( 301 === $c350_hc_code, sprintf( '7.12 ⭐⭐ F-01: /product-category/hardcover-books/ 301s (HTTP %d)', $c350_hc_code ) );
c350_assert(
	'' !== $c350_hc_loc && false !== strpos( $c350_hc_loc, '/shop' ),
	sprintf( '7.13 …to /shop/ (Location: %s)', '' !== $c350_hc_loc ? $c350_hc_loc : 'absent' )
);
/*
 * ⛔⛔ AND THE HARDCOVERS ARE STILL HIDDEN AND STILL PURCHASABLE. The redirect
 *    is a placeholder for a founder decision (`C12`), not a change to what is
 *    for sale. If a later build "fixes" F-01 by un-hiding them, this fails.
 */
c350_assert( function_exists( 'bhp_book_hide_hardcovers_from_shop' ), '7.14 ⛔ the hide-hardcovers filter is UNTOUCHED and still present' );

/* ⭐ An archive now carries the same card as /shop/: no "CHOOSE YOUR FORMAT". */
$c350_cat = home_url( '/product-category/paperback-books/' );
list( $c350_cat_code, $c350_cat_doc ) = c350_get( $c350_cat );
if ( 200 === $c350_cat_code && '' !== $c350_cat_doc ) {
	c350_assert( false !== strpos( $c350_cat_doc, 'bhp-catalog-grid' ), '7.15 ⭐ a category archive is a catalog grid now' );
	c350_assert( false === strpos( $c350_cat_doc, 'CHOOSE YOUR FORMAT' ), '7.16 ⭐⭐ the archive card BUYS: no "CHOOSE YOUR FORMAT" anywhere on it' );
	c350_assert( false !== strpos( $c350_cat_doc, 'bhp-shop-atc' ), '7.17 …it carries the same purchase control /shop/ does' );
	c350_assert( false === strpos( $c350_cat_doc, 'woocommerce-result-count' ), '7.18 …and the same chrome removal' );
}

echo "\n=== §8 · THE SHIP-TO-HOME CONFIRMATION STEP (E2 / R9) ===\n";

c350_assert( defined( 'BHP_COLOURING_SHIPHOME_PARAM' ), '8.1 the ask parameter is defined' );
if ( function_exists( 'bhp_colouring_ship_home_url' ) ) {
	$c350_url = bhp_colouring_ship_home_url( 0 );
	/*
	 * ⛔⛔ THE ASSERTION THIS WHOLE LIMB EXISTS FOR. Until 1.19.350 this link
	 *    carried `?bhp_visit=clear` and cleared hand delivery for the WHOLE
	 *    browser before a pixel rendered, silently. VERIFIED LIVE on production
	 *    2026-09-02 (`VISIT-SHOP-AUDIT.md` §E2).
	 */
	c350_assert(
		'' !== $c350_url && false === strpos( $c350_url, 'bhp_visit=clear' ),
		sprintf( '8.2 ⛔⛔ E2: the ship-home link NO LONGER carries bhp_visit=clear (%s)', $c350_url )
	);
	c350_assert(
		'' !== $c350_url && false !== strpos( $c350_url, BHP_COLOURING_SHIPHOME_PARAM ),
		'8.3 ⭐ it carries the ASK parameter, which clears nothing'
	);
}
c350_assert( function_exists( 'bhp_colouring_shiphome_confirm_notice' ), '8.4 the confirmation panel exists' );
c350_assert( function_exists( 'bhp_colouring_shop_shiphome_note' ), '8.5 ⭐ R9a: the SINGLE coloring card now has a note renderer too' );
c350_assert(
	false !== has_action( 'woocommerce_after_shop_loop_item', 'bhp_colouring_shop_shiphome_note' ),
	'8.6 …hooked inside the same <li> as the control'
);
/* ⭐ R9b: both notes say what is LOST, in the same words, so they cannot drift. */
$c350_col = c350_code( 'inc/colouring-line.php' );
/*
 * ⭐ THREE, NOT TWO, AND THE THIRD IS THE POINT OF THE `E2` FIX. The sentence
 *    is carried by the BUNDLE card's note, the SINGLE coloring card's note
 *    (`R9a`, new in 1.19.350) and the CONFIRMATION PANEL's body (`R9c`, also
 *    new). ⛔ A parent must read the same fact in the same words wherever they
 *    meet it; three paraphrases is how two of them drift. The count is asserted
 *    exactly rather than as "at least", so adding a fourth surface without
 *    thinking about it fails here.
 */
c350_assert(
	'' !== $c350_col && 3 === substr_count( $c350_col, 'ends school visit pickup in this browser' ),
	sprintf( '8.7 ⭐ R9a/R9b/R9c: the bundle note, the single-card note and the confirmation panel use ONE sentence (%d occurrences in code, expected 3)', substr_count( $c350_col, 'ends school visit pickup in this browser' ) )
);
c350_assert(
	'' !== $c350_col && false === strpos( $c350_col, 'Switches this browser out of school-visit pickup' ),
	'8.8 ⛔ the superseded "switches" wording is retired from the CODE, not left live beside its replacement'
);

echo "\n=== §9 · THE FREE-PDF v2 ASSET SWAP ===\n";

$c350_pdf = get_template_directory() . '/assets/downloads/mariana-trench-coloring-pages.pdf';
c350_assert( file_exists( $c350_pdf ), '9.1 the coloring-pages PDF is shipped' );
if ( file_exists( $c350_pdf ) ) {
	$c350_md5 = md5_file( $c350_pdf );
	c350_assert( '49ae06f1402bf7b6dc0f821ddb5c60a9' === $c350_md5, sprintf( '9.2 ⭐ it is v2 (md5 %s)', $c350_md5 ) );
	c350_assert( '408398b053f1ca257923e63201d03cb9' !== $c350_md5, '9.3 ⛔ …and not v1' );
}

echo "\n=== §10 · THE STANDING RAILS ON EVERY NEW CUSTOMER-FACING STRING ===\n";

/*
 * ⛔ §9.1 (no company "we" in customer-facing copy), 608a (no em dash), and the
 *    never-invent list. ⭐ The quoted-words carve-out (§9.1a) is why the REVIEW
 *    COMPONENT is not scanned here: a customer's own "we" is theirs.
 */
$c350_strings = array(
	'Adventures of Charlotte and Henry',
	'Big Places. Brave Hearts.',
	'Paperback and hardcover · Ages 6 to 9',
	'From',
	'Book %1$d of %2$d',
	'Companion coloring book',
	'Order by today, %s',
	'Order by %s',
	'Ordering for %1$s closed on %2$s.',
	'The books can still be ordered here and posted to your home.',
	'Not on the signed shelf. Ordering it posts your whole order and ends school visit pickup in this browser.',
	'Keep school visit pickup',
	'Mail my whole order instead',
	'HARDCOVER + COLORING BOOK %s',
	'%1$d coloring adventures · %2$d pages · ages 6-9',
);
foreach ( $c350_strings as $c350_s ) {
	c350_assert( 0 === preg_match( '/\b(we|us|our)\b/i', $c350_s ), sprintf( '10a §9.1 no company "we" in "%s"', $c350_s ) );
	c350_assert( false === strpos( $c350_s, "\xE2\x80\x94" ), sprintf( '10b 608a no em dash in "%s"', $c350_s ) );
}
/* ⛔ Reading age is 6-9, never 5-9. */
c350_assert( 0 === preg_match( '/\b5\s*(to|-|–)\s*9\b/', implode( ' ', $c350_strings ) ), '10c ⛔ reading age is 6 to 9, never 5 to 9' );
/* ⛔ The trim prints ONCE on the colouring page from 1.19.350. */
c350_assert(
	'' !== $c350_col && false === strpos( $c350_col, "coloring adventures · %2\$d pages · %3\$s · ages" ),
	'10d ⭐ the colouring spec line in the CODE no longer repeats the trim (it prints once, in the format heading)'
);

echo "\n=== §11 · THE SHIPPED CSS ARTEFACT ===\n";

$c350_min = c350_read( 'style.min.css' );
c350_assert( '' !== $c350_min, '11.1 style.min.css is readable' );
c350_assert( false !== strpos( $c350_min, 'body.bhp-catalog-grid' ), '11.2 ⭐ the catalog scope token is SERVED' );
c350_assert( false === strpos( $c350_min, 'body.woocommerce-shop .woo-expedition-shell' ), '11.3 ⛔ the superseded scope token is fully migrated, not half-migrated' );
c350_assert( false !== strpos( $c350_min, 'bhp-catalog-band' ), '11.4 the band rules are served' );
c350_assert( false !== strpos( $c350_min, 'bhp-visit-band' ), '11.5 the visit-band rules are served' );
c350_assert( false !== strpos( $c350_min, 'bhp-shiphome-confirm' ), '11.6 the confirmation-panel rules are served' );
c350_assert( false !== strpos( $c350_min, 'repeat(5, minmax(0, 1fr))' ) || false !== strpos( $c350_min, 'repeat(5,minmax(0,1fr))' ), '11.7 ⭐ five tracks are served' );
/*
 * ⛔ A STALE MINIFY SHIPS VERIFIED CSS TO THE REPOSITORY AND NOTHING TO A
 *    CUSTOMER. This release shipped a stale artefact ONCE, on the second deploy
 *    of the day, because a shell `&&` chain broke on a `grep -c` exit code and
 *    the previous tarball was uploaded. Caught by diffing the DEPLOYED md5
 *    against the local one, not by reading the build log.
 */
$c350_src_css = get_template_directory() . '/style.css';
if ( file_exists( $c350_src_css ) ) {
	$c350_hash = md5( str_replace( "\r\n", "\n", (string) file_get_contents( $c350_src_css ) ) );
	c350_assert( false !== strpos( $c350_min, $c350_hash ), '11.8 ⛔ style.min.css was built from the CURRENT style.css' );
}
c350_assert( false !== strpos( $c350_min, 'Version: ' . wp_get_theme()->get( 'Version' ) ), '11.9 …and rebuilt for THIS release' );

echo "\n============================================================\n";
echo "CYCLE179-LD-350-BUILD — {$GLOBALS['c350_passes']} passed, {$GLOBALS['c350_failures']} failed\n";
echo "============================================================\n";
