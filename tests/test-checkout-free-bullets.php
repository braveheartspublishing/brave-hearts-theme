<?php
/**
 * Brave Hearts — THE THREE FREE BULLETS ON THE CHECKOUT PAGE (theme 1.19.220).
 * CYCLE155-LD-01
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-checkout-free-bullets.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-12, verbatim (⛔ RELAYED through the Chief of Staff
 * in the build brief and NOT witnessed first-hand by the agent that wrote
 * this file):
 *
 *   "Oh the FREE vocab stuff isnt on the checkout page either- sorry!"
 *
 * The 2026-08-06 standing format — "FREE-items emphasis on ALL funnel +
 * collection pages: bold, each free item its own bullet line, never combined
 * sentences" — had reached seven surfaces and not the one page every buyer
 * must pass through.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * This is a PHP/CLI suite. It has NO layout engine, NO React and NO Store API
 * session. It cannot prove that the panel is VISIBLE on a rendered checkout,
 * cannot read a computed style, and cannot see where the panel lands relative
 * to the form. Claiming otherwise would be a fabricated verification, which
 * the standing rules put in the same class as a fabricated review. The
 * rendered evidence is a real browser against staging at 1440px and 390px,
 * recorded in the session's QA evidence, not here.
 *
 * What it DOES prove, and every one of these regresses silently:
 *
 *   §1  The filter exists, is registered on `render_block`, and is gated on
 *       the outer `woocommerce/checkout` block, on `is_checkout()`, and
 *       NOT on the order-received endpoint.
 *   §2  ⭐ THE COPY COMES FROM THE SHARED HELPER AND FROM NOWHERE ELSE. This
 *       is the assertion that matters most. The instruction being answered
 *       exists precisely because a surface can be forgotten when a new free
 *       item is added; a literal string here would recreate that failure on
 *       the checkout page and nobody would notice until a customer did.
 *   §3  The three lines the helper yields on THIS environment, each behind
 *       its own live predicate, with "FREE" in typed capitals.
 *   §4  The panel fails closed — empty helper output renders nothing.
 *   §5  The CSS box exists in the shipped stylesheet, does not restate the
 *       sitewide bullet treatment, and uses no `text-transform`.
 *   §6  ⛔ NO WOOCOMMERCE RECORD AND NO PAGE CONTENT WAS EDITED. The checkout
 *       page's own `post_content` must contain none of this markup — the
 *       panel is code-side, which is what keeps it off the Andrew gate.
 *
 * ⛔ NO ORDER IS CREATED. NO CART IS BUILT. No product record, price, coupon,
 *    stock level, shipping, tax, payment or checkout setting is read for
 *    mutation or written by any part of this file, on any environment.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⚠ THE COUNTERS ARE IN $GLOBALS ON PURPOSE, AND THIS IS NOT STYLE.
 *   `wp eval-file` includes this file INSIDE a function, so a plain `$pass`
 *   at the top level here is a LOCAL and a `global $pass` inside the helper
 *   binds to a different, always-empty variable. Recorded once already in
 *   tests/test-checkout-heading-weight.php; repeated here so the next suite
 *   does not re-derive it from a "0 passed, 0 failed" report.
 */
$GLOBALS['bhp_cfb_pass'] = 0;
$GLOBALS['bhp_cfb_fail'] = 0;
$GLOBALS['bhp_cfb_skip'] = 0;

function bhp_cfb_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['bhp_cfb_pass']++;
		WP_CLI::log( "  PASS  {$label}" );
	} else {
		$GLOBALS['bhp_cfb_fail']++;
		WP_CLI::log( "  FAIL  {$label}" . ( $detail ? "  --  {$detail}" : '' ) );
	}
}

function bhp_cfb_skip( $label, $why = '' ) {
	$GLOBALS['bhp_cfb_skip']++;
	WP_CLI::log( "  SKIP  {$label}" . ( $why ? "  --  {$why}" : '' ) );
}

/**
 * PHP source with every comment removed.
 *
 * ⚠ LOAD-BEARING, AND THE REASON IS THE SAME ONE test-checkout-heading-weight
 *   records for CSS: this codebase writes essay-length comments, and the
 *   comments in `inc/checkout-experience.php` QUOTE the three approved
 *   strings verbatim as part of the record of why the panel exists. A
 *   naive `strpos()` over the raw file would therefore find "FREE
 *   Vocabulary Card Activity" and report a hardcoded literal that is not
 *   there. `token_get_all()` is used rather than a regex because it is the
 *   PHP parser's own answer to "what is a comment".
 */
function bhp_cfb_code( $src ) {
	$out = '';
	foreach ( token_get_all( $src ) as $token ) {
		if ( is_array( $token ) ) {
			if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
				continue;
			}
			$out .= $token[1];
			continue;
		}
		$out .= $token;
	}
	return $out;
}

WP_CLI::log( '' );
WP_CLI::log( '=== CHECKOUT FREE BULLETS — theme ' . wp_get_theme()->get( 'Version' ) . ' ===' );

$theme_dir = get_template_directory();
$php_path  = $theme_dir . '/inc/checkout-experience.php';
$css_path  = $theme_dir . '/assets/css/checkout-experience.css';
$min_path  = $theme_dir . '/assets/css/checkout-experience.min.css';
$root_css  = $theme_dir . '/style.css';

$php_raw = file_exists( $php_path ) ? file_get_contents( $php_path ) : '';
$php     = '' === $php_raw ? '' : bhp_cfb_code( $php_raw );
$css     = file_exists( $css_path ) ? file_get_contents( $css_path ) : '';
$min     = file_exists( $min_path ) ? file_get_contents( $min_path ) : '';
$style   = file_exists( $root_css ) ? file_get_contents( $root_css ) : '';

/* ───────────────────────────────────────────────────────────────────────────
 * §1  THE FILTER, AND ITS GATES.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§1  THE FILTER AND ITS GATES' );

bhp_cfb_assert( 'inc/checkout-experience.php is readable', '' !== $php_raw, $php_path );
bhp_cfb_assert(
	'bhp_checkout_free_bullets_panel() is defined at runtime',
	function_exists( 'bhp_checkout_free_bullets_panel' )
);
bhp_cfb_assert(
	'it is hooked to render_block',
	function_exists( 'has_filter' ) && false !== has_filter( 'render_block', 'bhp_checkout_free_bullets_panel' )
);
bhp_cfb_assert(
	'the hook accepts two arguments (the filter needs $block, not just $block_content)',
	1 === preg_match( "/add_filter\(\s*'render_block'\s*,\s*'bhp_checkout_free_bullets_panel'\s*,\s*10\s*,\s*2\s*\)/", $php )
);
bhp_cfb_assert(
	'it only acts on the OUTER woocommerce/checkout block',
	1 === preg_match( "/'woocommerce\/checkout'\s*!==\s*\\\$block\['blockName'\]/", $php )
);
bhp_cfb_assert(
	'is_checkout() is required, and function_exists-guarded',
	1 === preg_match( "/!function_exists\('is_checkout'\)\s*\|\|\s*!is_checkout\(\)/", $php )
);
/*
 * ⭐ THE ASYMMETRIC GATE. `is_checkout()` returns TRUE on the thank-you page,
 *    because order-received is a checkout endpoint. Without this exclusion a
 *    customer who has already paid would be shown an offer list for a cart
 *    that no longer has anything to do with their completed order. The same
 *    guard is asserted in three other suites for three other modules; this is
 *    the fourth module that needs it.
 */
bhp_cfb_assert(
	'⭐ the order-received endpoint is excluded, and the check is function_exists-guarded',
	1 === preg_match( "/function_exists\('is_order_received_page'\)\s*&&\s*is_order_received_page\(\)/", $php )
);
bhp_cfb_assert(
	'the panel is PREPENDED (it must sit outside the React root, before the block wrapper)',
	1 === preg_match( "/return\s*'<div class=\"bhp-checkout-free\">'\s*\.\s*\\\$bullets\s*\.\s*'<\/div>'\s*\.\s*\\\$block_content;/", $php )
);

/* ───────────────────────────────────────────────────────────────────────────
 * §2  THE COPY COMES FROM THE SHARED HELPER — the anti-drift assertion.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§2  ⭐ ONE AUTHOR FOR THE FREE LIST' );

bhp_cfb_assert(
	'the panel calls bhp_book_free_bullets_markup()',
	1 === preg_match( "/bhp_book_free_bullets_markup\(\s*'collection'\s*,\s*'bhp-free-bullets--checkout'\s*\)/", $php )
);
bhp_cfb_assert(
	'the call is function_exists-guarded (the helper lives in inc/book-formats.php)',
	1 === preg_match( "/!function_exists\('bhp_book_free_bullets_markup'\)/", $php )
);

/*
 * The literals, checked against COMMENT-STRIPPED source. Each of these three
 * strings has exactly one author elsewhere in the codebase:
 *   "FREE Shipping on the complete collection or 3 or more books purchased"  -> inc/book-formats.php
 *   "FREE Activity Book ..."                    -> the plugin's
 *                                                  bhp_bundle_addon_free_offer_line()
 *   "FREE Vocabulary Card Activity"             -> the plugin's
 *                                                  bhp_bundle_vocab_free_offer_line()
 * A copy here would be a fourth author and would drift on the next change.
 */
foreach ( array(
	'FREE Shipping on the complete collection or 3 or more books purchased',
	'FREE Activity Book',
	'FREE Vocabulary Card Activity',
) as $literal ) {
	bhp_cfb_assert(
		"no hardcoded '{$literal}' literal in the checkout code",
		false === stripos( $php, $literal ),
		'the string must come from the shared helper, never from this file'
	);
}

/* ───────────────────────────────────────────────────────────────────────────
 * §3  WHAT THE HELPER ACTUALLY YIELDS ON THIS ENVIRONMENT.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§3  THE LIVE LINES, EACH BEHIND ITS OWN PREDICATE' );

if ( ! function_exists( 'bhp_book_free_bullet_lines' ) || ! function_exists( 'bhp_book_free_bullets_markup' ) ) {
	bhp_cfb_skip( '§3 the shared helper is not loaded on this environment' );
} else {
	$lines  = bhp_book_free_bullet_lines( 'collection' );
	$markup = bhp_book_free_bullets_markup( 'collection', 'bhp-free-bullets--checkout' );

	bhp_cfb_assert(
		'the markup carries the checkout modifier class',
		'' === $markup || false !== strpos( $markup, 'bhp-free-bullets bhp-free-bullets--checkout' ),
		$markup
	);
	bhp_cfb_assert(
		'one <li> per live line, and no more',
		count( $lines ) === substr_count( $markup, '<li class="bhp-free-bullets__item">' ),
		count( $lines ) . ' lines vs ' . substr_count( $markup, '<li class="bhp-free-bullets__item">' ) . ' items'
	);
	bhp_cfb_assert(
		'every line is wrapped in <strong> (the ruling asks for bold, and bold is markup)',
		count( $lines ) === substr_count( $markup, '<strong>' )
	);
	foreach ( $lines as $line ) {
		bhp_cfb_assert(
			"the line says FREE in typed capitals, not via CSS — '" . $line . "'",
			false !== strpos( $line, 'FREE' ),
			'a text-transform leaves the accessible name reading "Free"'
		);
	}

	/*
	 * ⭐ EACH PREDICATE IS ASKED SEPARATELY, and the assertion is an
	 *    EQUIVALENCE rather than a presence check. "The vocab line is there"
	 *    would pass on an environment that has no vocabulary-cards PDF and is
	 *    promising it anyway — which is the exact failure this shape prevents.
	 */
	$ship_live  = function_exists( 'bhp_book_collection_ships_free' ) && bhp_book_collection_ships_free();
	$addon_live = function_exists( 'bhp_book_collection_includes_free_addon' ) && bhp_book_collection_includes_free_addon();
	$vocab_live = function_exists( 'bhp_bundle_vocab_cards_live' ) && bhp_bundle_vocab_cards_live();

	$has_ship  = false !== strpos( $markup, 'FREE Shipping on the complete collection or 3 or more books purchased' );
	$has_addon = false !== strpos( $markup, 'FREE Activity Book' );
	$has_vocab = false !== strpos( $markup, 'FREE Vocabulary Card Activity' );

	bhp_cfb_assert(
		'free-shipping line present IF AND ONLY IF bhp_book_collection_ships_free()',
		$ship_live === $has_ship,
		'predicate=' . var_export( $ship_live, true ) . ' rendered=' . var_export( $has_ship, true )
	);
	bhp_cfb_assert(
		'activity-book line present IF AND ONLY IF the add-on offer is live',
		$addon_live === $has_addon,
		'predicate=' . var_export( $addon_live, true ) . ' rendered=' . var_export( $has_addon, true )
	);
	bhp_cfb_assert(
		'⭐ vocabulary-cards line present IF AND ONLY IF bhp_bundle_vocab_cards_live()',
		$vocab_live === $has_vocab,
		'predicate=' . var_export( $vocab_live, true ) . ' rendered=' . var_export( $has_vocab, true )
	);

	/*
	 * ORDER IS FIXED AND IS THE BRIEF'S: Shipping, then Activity Book, then
	 * Vocabulary Cards. Only asserted where all three are live, because with
	 * a line missing there is nothing to order.
	 */
	if ( $has_ship && $has_addon && $has_vocab ) {
		bhp_cfb_assert(
			'the three lines render in the fixed order: Shipping, Activity Book, Vocabulary Cards',
			strpos( $markup, 'FREE Shipping' ) < strpos( $markup, 'FREE Activity Book' )
				&& strpos( $markup, 'FREE Activity Book' ) < strpos( $markup, 'FREE Vocabulary Card Activity' )
		);
	} else {
		bhp_cfb_skip( '§3 order assertion needs all three lines live on this environment' );
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §4  FAIL-CLOSED.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§4  FAIL-CLOSED' );

bhp_cfb_assert(
	'nothing renders when the helper returns an empty string',
	1 === preg_match( "/if\s*\('' === trim\(\\\$bullets\)\)\s*\{\s*return \\\$block_content;/", $php ),
	'an empty <div> above the checkout form would be a visible empty box'
);
/*
 * ⚠ THE FUNCTION BODY IS ISOLATED FIRST. `inc/checkout-experience.php` also
 *   holds `bhp_empty_cart_invitation()`, which is another `render_block`
 *   filter and which returns `$block_content` twice of its own. Counting
 *   across the whole file would assert nothing about this function.
 */
$fn_start = strpos( $php, 'function bhp_checkout_free_bullets_panel(' );
$fn_end   = false === $fn_start ? false : strpos( $php, "add_filter('render_block', 'bhp_checkout_free_bullets_panel'", $fn_start );
$fn_body  = ( false === $fn_start || false === $fn_end ) ? '' : substr( $php, $fn_start, $fn_end - $fn_start );

bhp_cfb_assert( 'the function body is locatable', '' !== $fn_body );
bhp_cfb_assert(
	'every rejected path returns $block_content untouched — five guards, five returns',
	5 === substr_count( $fn_body, 'return $block_content;' ),
	'found ' . substr_count( $fn_body, 'return $block_content;' ) . ' (blockName, is_checkout, order-received, helper-missing, empty-markup)'
);
bhp_cfb_assert(
	'the function has exactly one path that changes the output',
	1 === substr_count( $fn_body, 'bhp-checkout-free' )
);

/* ───────────────────────────────────────────────────────────────────────────
 * §5  THE CSS.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§5  THE PANEL BOX, AND WHAT IT MUST NOT RESTATE' );

$marker = 'THE THREE FREE BULLETS ON /checkout/';
$at     = strpos( $css, $marker );
bhp_cfb_assert( 'the 1.19.220 block is present in checkout-experience.css', false !== $at, 'marker: ' . $marker );

$block = '';
if ( false !== $at ) {
	$open  = strrpos( substr( $css, 0, $at ), '/*' );
	$block = substr( $css, false === $open ? $at : $open );
}
$block_css_only = preg_replace( '#/\*.*?\*/#s', '', $block );

bhp_cfb_assert( '.bhp-checkout-free is declared', false !== strpos( $block_css_only, '.bhp-checkout-free {' ) );
bhp_cfb_assert(
	'the list inside it is targeted through the sitewide class, not a new one',
	false !== strpos( $block_css_only, '.bhp-checkout-free .bhp-free-bullets' )
);
/*
 * ⭐ THE ANTI-DRIFT CSS ASSERTION. `.bhp-free-bullets__item` — the bold, the
 *    size, the colour and the green checkmark — is owned by style.css. If this
 *    block ever restates it, the checkout quietly becomes a fourth FREE-bullet
 *    treatment and the next sitewide change stops reaching it.
 */
bhp_cfb_assert(
	'⭐ the block does NOT restate .bhp-free-bullets__item (style.css owns the treatment)',
	false === strpos( $block_css_only, '.bhp-free-bullets__item' )
);
bhp_cfb_assert(
	'style.css still owns .bhp-free-bullets__item',
	false !== strpos( $style, '.bhp-free-bullets__item {' )
);
bhp_cfb_assert(
	'no text-transform in the block (the capitals are in the PHP string)',
	false === stripos( $block_css_only, 'text-transform' )
);
bhp_cfb_assert(
	'no payment-gateway selector in the block',
	false === stripos( $block_css_only, 'payment' ) && false === stripos( $block_css_only, 'stripe' )
);
bhp_cfb_assert(
	'the rule survived minification into the shipped artefact',
	false !== strpos( $min, '.bhp-checkout-free' ),
	'checkout-experience.min.css is what the browser loads'
);

/* ───────────────────────────────────────────────────────────────────────────
 * §6  NO WOOCOMMERCE RECORD, NO PAGE CONTENT.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§6  ⛔ THE ANDREW GATE WAS NOT CROSSED' );

bhp_cfb_assert(
	'the file writes no option',
	false === strpos( $php, 'update_option(' ) && false === strpos( $php, 'add_option(' )
);
bhp_cfb_assert(
	'the file writes no post or postmeta',
	false === strpos( $php, 'update_post_meta(' ) && false === strpos( $php, 'wp_update_post(' )
);
bhp_cfb_assert(
	'the file saves no WooCommerce object',
	false === strpos( $php, '->save()' )
);

$checkout_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
if ( $checkout_id > 0 ) {
	$content = (string) get_post_field( 'post_content', $checkout_id );
	bhp_cfb_assert(
		'⭐ the checkout PAGE content carries none of this markup — the panel is code-side',
		false === strpos( $content, 'bhp-checkout-free' )
			&& false === strpos( $content, 'bhp_book_free_bullets' )
			&& false === strpos( $content, 'bhp-free-bullets' ),
		'editing the checkout page would be a checkout-configuration change, which is an Andrew gate'
	);
	bhp_cfb_assert(
		'the checkout page still uses the WooCommerce checkout block',
		false !== strpos( $content, 'wp:woocommerce/checkout' )
	);
} else {
	bhp_cfb_skip( '§6 no WooCommerce checkout page configured on this environment' );
}

/* ───────────────────────────────────────────────────────────────────────────
 * RESULT
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( sprintf(
	'=== %d passed, %d failed, %d skipped ===',
	$GLOBALS['bhp_cfb_pass'],
	$GLOBALS['bhp_cfb_fail'],
	$GLOBALS['bhp_cfb_skip']
) );
WP_CLI::log( '' );

if ( $GLOBALS['bhp_cfb_fail'] > 0 ) {
	WP_CLI::error( $GLOBALS['bhp_cfb_fail'] . ' assertion(s) failed.' );
}
