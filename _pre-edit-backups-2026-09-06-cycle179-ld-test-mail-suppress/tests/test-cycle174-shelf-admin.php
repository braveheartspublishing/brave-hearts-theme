<?php
/**
 * CYCLE174-LD-345 — THE SIGNED-COPY SHELF ADMIN SCREEN (plugin 1.8.79).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `includes/school-visit-shelf-admin.php` is the first surface in this plugin
 * that WRITES the shelf option from a browser. Everything before it either read
 * the option or was set by a WP-CLI line typed by hand. ⛔ THAT MAKES ITS
 * SAFETY PROPERTIES THE THING WORTH ASSERTING, not its markup:
 *
 *   · it must write EXACTLY ONE option and nothing else;
 *   · it must never go near a WooCommerce stock field;
 *   · it must be capability-checked on the ACTION, not only on the menu;
 *   · it must refuse a bad number rather than coerce it to zero;
 *   · it must not render on the front end at all.
 *
 * ⛔ IT DOES NOT ASSERT THAT THE SCREEN LOOKS RIGHT. A rendered admin table is
 *    verified by a human with a browser; what a suite can prove is that the
 *    dangerous things are absent. Claiming otherwise would be the failure class
 *    `CYCLE173-LD-4` recorded — a test that reads its own comments.
 *
 * RUN:
 *   wp eval-file wp-content/themes/<theme>/tests/test-cycle174-shelf-admin.php --user=1
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, NOT `global $x`, AND THE DIFFERENCE IS NOT COSMETIC. `wp eval-file`
 *    includes this file inside a FUNCTION scope, so a top-level `$bhp_c174_pass`
 *    is a LOCAL, and a `global` declaration inside the helper binds to a
 *    different, empty variable. The first version of this suite did exactly that
 *    and reported "0 passed, 0 FAILED" while printing real FAIL lines — ⛔ A
 *    SCOREBOARD THAT CANNOT GO RED IS WORSE THAN NO SCOREBOARD, because it
 *    reports success. Caught on the first run and recorded rather than quietly
 *    fixed. Matches the pattern `test-cycle169-blog-layout.php` already uses.
 */
$GLOBALS['bhp_c174_pass'] = 0;
$GLOBALS['bhp_c174_fail'] = 0;

function bhp_c174_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_c174_pass']++;
		echo 'PASS  ' . $label . ( '' !== $detail ? "  [{$detail}]" : '' ) . PHP_EOL;
	} else {
		$GLOBALS['bhp_c174_fail']++;
		echo 'FAIL  ' . $label . ( '' !== $detail ? "  [{$detail}]" : '' ) . PHP_EOL;
	}
}

function bhp_c174_head( $t ) {
	echo PHP_EOL . '=== ' . $t . ' ===' . PHP_EOL;
}

$bhp_c174_file = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/school-visit-shelf-admin.php';
$bhp_c174_src  = file_exists( $bhp_c174_file ) ? (string) file_get_contents( $bhp_c174_file ) : '';
$bhp_c174_boot = (string) @file_get_contents( WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/brave-hearts-bundle-pricing.php' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 — IT EXISTS, AND IT IS ADMIN-ONLY
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§1 presence and admin-only loading' );

bhp_c174_ok( '§1.1 the admin module exists', '' !== $bhp_c174_src );
bhp_c174_ok(
	'§1.2 ⛔ it is required INSIDE an is_admin() guard, so a customer request never loads it',
	(bool) preg_match( '/if\s*\(\s*is_admin\(\)\s*\)\s*\{[^}]*school-visit-shelf-admin\.php/s', $bhp_c174_boot )
);
bhp_c174_ok(
	'§1.3 ⛔ and it is required AFTER the shelf module whose functions it reads',
	strpos( $bhp_c174_boot, 'school-visit-shelf-stock.php' ) < strpos( $bhp_c174_boot, 'school-visit-shelf-admin.php' )
);
bhp_c174_ok(
	'§1.4 the plugin version header and the VERSION constant agree',
	(bool) preg_match( '/Version:\s*([0-9.]+)/', $bhp_c174_boot, $vh )
		&& (bool) preg_match( "/BHP_BUNDLE_PRICING_VERSION',\s*'([0-9.]+)'/", $bhp_c174_boot, $vc )
		&& $vh[1] === $vc[1],
	isset( $vh[1], $vc[1] ) ? $vh[1] . ' / ' . $vc[1] : 'unreadable'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ §2 — THE BLAST RADIUS. THIS IS THE SECTION THAT MATTERS.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§2 blast radius — what this screen is allowed to touch' );

preg_match_all( '/update_option\(\s*([^,]+),/', $bhp_c174_src, $bhp_c174_opts );
$bhp_c174_opt_args = isset( $bhp_c174_opts[1] ) ? array_map( 'trim', $bhp_c174_opts[1] ) : array();

bhp_c174_ok(
	'§2.1 ⭐⭐ EXACTLY ONE update_option() call in the whole file',
	1 === count( $bhp_c174_opt_args ),
	count( $bhp_c174_opt_args ) . ' call(s)'
);
bhp_c174_ok(
	'§2.2 ⭐ and it writes the shelf option and nothing else',
	1 === count( $bhp_c174_opt_args )
		&& false !== strpos( $bhp_c174_opt_args[0], 'BHP_VISIT_SHELF_OPTION' ),
	$bhp_c174_opt_args ? $bhp_c174_opt_args[0] : 'none'
);

/*
 * ⛔ THE WOOCOMMERCE STOCK FIELDS. `.claude/rules/woocommerce.md` makes changing
 *    any of these on a core product an Andrew gate.
 *
 * ⚠⚠ CORRECTED IN THE SAME SITTING IT WAS WRITTEN, AND THE ERROR WAS MINE. The
 *    first version searched the WHOLE FILE and asserted the names appear nowhere
 *    at all, "not even in a comment". ⛔ IT FAILED, AND IT WAS WRONG RATHER THAN
 *    the code being wrong: the module's docblock names all four DELIBERATELY, in
 *    a paragraph headed "what this is NOT", to record that they are never
 *    touched. That documentation is worth having, and an assertion that forbids
 *    a file from DESCRIBING a hazard punishes the safest version of the file.
 *
 * ⭐ SO THE COMMENTS ARE STRIPPED FIRST AND ONLY THE EXECUTABLE CODE IS
 *   SEARCHED. That is the property that was always meant: the screen must not
 *   READ OR WRITE a stock field. `token_get_all()` does the stripping, so the
 *   test cannot be fooled by a `#`, `//` or `/* *\/` style a regex would miss.
 */
$bhp_c174_code = '';
foreach ( token_get_all( $bhp_c174_src ) as $bhp_c174_tok ) {
	if ( is_array( $bhp_c174_tok ) ) {
		if ( in_array( $bhp_c174_tok[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue; // Comments are documentation, not behaviour.
		}
		$bhp_c174_code .= $bhp_c174_tok[1];
	} else {
		$bhp_c174_code .= $bhp_c174_tok;
	}
}
bhp_c174_ok(
	'§2.3-0 the comment-stripped code body was extracted, and it is genuinely smaller than the file',
	'' !== $bhp_c174_code && strlen( $bhp_c174_code ) < strlen( $bhp_c174_src ),
	strlen( $bhp_c174_code ) . ' of ' . strlen( $bhp_c174_src ) . ' bytes are code'
);

$bhp_c174_forbidden = array( '_stock_status', '_manage_stock', '_backorders', 'set_stock_quantity', 'set_stock_status', 'wc_update_product_stock' );
foreach ( $bhp_c174_forbidden as $needle ) {
	bhp_c174_ok(
		'§2.3 ⛔ no EXECUTABLE reference to "' . $needle . '"',
		false === strpos( $bhp_c174_code, $needle )
	);
}
/*
 * `_stock` is checked separately and deliberately: `_stock_status` contains it
 * as a substring, so a naive search would pass vacuously once the rows above are
 * green. This looks for the bare quoted field name.
 */
bhp_c174_ok(
	'§2.3b ⛔ and no bare "_stock" field reference either (checked separately so _stock_status cannot mask it)',
	0 === preg_match( '/[\'"]_stock[\'"]/', $bhp_c174_code )
);
/*
 * ⭐ AND THE DOCUMENTATION IS ASSERTED TO EXIST, which is the inverse of the
 *   mistake above: the file SHOULD say what it does not touch.
 */
bhp_c174_ok(
	'§2.3c ⭐ the docblock still documents that those fields are never touched',
	false !== strpos( $bhp_c174_src, '_stock_status' ) && false !== strpos( $bhp_c174_src, 'NOT A WOOCOMMERCE INVENTORY SCREEN' )
);

foreach ( array( 'wp_insert_post', 'wp_update_post', 'update_post_meta', 'delete_post_meta', '$wpdb' ) as $needle ) {
	bhp_c174_ok(
		'§2.4 ⛔ no direct post/meta/db write: "' . $needle . '" absent',
		false === strpos( $bhp_c174_src, $needle )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 — AUTHORISATION
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§3 capability and nonce' );

bhp_c174_ok(
	'§3.1 the capability is manage_woocommerce, matching BHP_Dashboard_Page',
	false !== strpos( $bhp_c174_src, "CAPABILITY = 'manage_woocommerce'" )
);
bhp_c174_ok(
	'§3.2 ⭐⭐ the SAVE HANDLER checks the capability itself — a menu check protects the link, not the action',
	(bool) preg_match( '/function\s+handle_save\(\)\s*\{\s*if\s*\(\s*!\s*current_user_can\(\s*self::CAPABILITY/s', $bhp_c174_src )
);
bhp_c174_ok(
	'§3.3 the save handler verifies a nonce',
	false !== strpos( $bhp_c174_src, 'check_admin_referer( self::NONCE )' )
);
bhp_c174_ok(
	'§3.4 the form emits that same nonce',
	false !== strpos( $bhp_c174_src, 'wp_nonce_field( self::NONCE )' )
);
bhp_c174_ok(
	'§3.5 ⛔ the render method is capability-guarded too, not only the menu registration',
	(bool) preg_match( '/function\s+render\(\)\s*\{\s*if\s*\(\s*!\s*current_user_can\(\s*self::CAPABILITY/s', $bhp_c174_src )
);
bhp_c174_ok(
	'§3.6 it redirects after saving, so a refresh cannot re-submit',
	false !== strpos( $bhp_c174_src, 'wp_safe_redirect' ) && false !== strpos( $bhp_c174_src, 'exit;' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ §4 — THE GROSS/NET TRAP AND THE REFUSAL RULE
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§4 the gross-count contract and bad input' );

if ( class_exists( 'BHP_Visit_Shelf_Admin' ) || file_exists( $bhp_c174_file ) ) {
	if ( ! class_exists( 'BHP_Visit_Shelf_Admin' ) ) {
		require_once $bhp_c174_file;
	}
}
bhp_c174_ok( '§4.0 the class loads', class_exists( 'BHP_Visit_Shelf_Admin' ) );

$bhp_c174_html = '';
if ( class_exists( 'BHP_Visit_Shelf_Admin' ) ) {
	wp_set_current_user( 1 );
	ob_start();
	BHP_Visit_Shelf_Admin::render();
	$bhp_c174_html = (string) ob_get_clean();
}

bhp_c174_ok( '§4.1 the screen renders something', strlen( $bhp_c174_html ) > 500, strlen( $bhp_c174_html ) . ' bytes' );
bhp_c174_ok(
	'§4.2 ⭐⭐ THE GROSS WARNING IS ON THE SCREEN, beside the fields, not buried in a docblock',
	false !== stripos( $bhp_c174_html, 'GROSS count' )
);
bhp_c174_ok(
	'§4.3 ⭐ and it says in plain words not to subtract open orders by hand',
	false !== stripos( $bhp_c174_html, 'Do not subtract open orders' )
);
bhp_c174_ok(
	'§4.4 ⛔ a bad number is REFUSED, never coerced — the refusal branch exists and reports the slug',
	false !== strpos( $bhp_c174_src, '$refused[] = $slug;' )
		&& false !== stripos( $bhp_c174_src, 'REFUSED, and not saved' )
);
bhp_c174_ok(
	'§4.5 ⛔ a BLANK field means "not counted" and is distinct from zero',
	(bool) preg_match( "/if\s*\(\s*''\s*===\s*\\\$raw\s*\)\s*\{\s*continue;/s", $bhp_c174_src )
);
bhp_c174_ok(
	'§4.6 ⛔ COMMITTED IS READ-ONLY — there is no input field for it anywhere in the markup',
	0 === preg_match( '/name="committed/i', $bhp_c174_html )
		&& 0 === preg_match( "/name='committed/i", $bhp_c174_html )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 — IT CANNOT DISAGREE WITH THE STOREFRONT
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§5 one arithmetic, one set of words' );

/*
 * ⭐ THE POINT: an admin screen that recomputed "remaining" itself, or retyped
 *   the counter sentence, could show Andrew a number or a phrase that differs
 *   from what a parent sees. Both must come from the shipped functions.
 */
foreach ( array( 'bhp_visit_shelf_baseline', 'bhp_visit_shelf_committed', 'bhp_visit_shelf_title_counter', 'bhp_visit_shelf_title_is_closed', 'bhp_visit_shelf_title_is_exhausted' ) as $fn ) {
	bhp_c174_ok(
		'§5.1 it asks the shipped function ' . $fn . '() rather than recomputing',
		false !== strpos( $bhp_c174_src, $fn . '(' )
	);
}
foreach ( array( 'bhp_visit_shelf_counter_label', 'bhp_visit_shelf_sold_out_label', 'bhp_visit_shelf_backorder_label' ) as $fn ) {
	bhp_c174_ok(
		'§5.2 the customer-facing wording comes from ' . $fn . '(), not retyped here',
		false !== strpos( $bhp_c174_src, $fn . '(' )
	);
}
bhp_c174_ok(
	'§5.3 ⛔ the buffer and the ceiling are read from the shelf constants, never re-literalled',
	false !== strpos( $bhp_c174_src, 'BHP_VISIT_SHELF_BUFFER' )
		&& false !== strpos( $bhp_c174_src, 'BHP_VISIT_SHELF_COUNTER_MAX' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ §6 — THE PDF RULE. THE ACTIVITY BOOK NEVER GETS A COUNT.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§6 the activity book is a PDF and has no shelf' );

/*
 * Founder, 2026-08-31 (⛔ RELAYED): "The Adventure Activity Book is a PDF —
 * NEVER gets a counter or scarcity number."
 *
 * ⭐ THE GUARANTEE IS STRUCTURAL RATHER THAN A SPECIAL CASE, and that is the
 *   stronger form: the shelf keys on `bhp_bundle_catalog()['paperback']`, so a
 *   PDF is not in the list at all and cannot acquire a count by anybody typing
 *   one. This asserts the structure rather than trusting the intent.
 */
$bhp_c174_slugs = function_exists( 'bhp_visit_shelf_title_slugs' ) ? bhp_visit_shelf_title_slugs() : array();
bhp_c174_ok(
	'§6.1 the shelf knows only chapter-paperback title slugs',
	! empty( $bhp_c174_slugs ),
	implode( ',', $bhp_c174_slugs )
);
foreach ( array( 'activity', 'adventure_activity_book', 'activity_book', 'coloring', 'colouring' ) as $bhp_c174_bad ) {
	bhp_c174_ok(
		'§6.2 ⛔ "' . $bhp_c174_bad . '" is not a counted title',
		! in_array( $bhp_c174_bad, $bhp_c174_slugs, true )
	);
}
bhp_c174_ok(
	'§6.3 ⭐ the screen states the PDF rule to whoever is editing counts',
	false !== stripos( $bhp_c174_html, 'PDF' ) && false !== stripos( $bhp_c174_html, 'never carries a count' )
);
bhp_c174_ok(
	'§6.4 ⛔ every rendered count field belongs to a known catalog title — the screen cannot invent a row',
	(bool) ( function () use ( $bhp_c174_html, $bhp_c174_slugs ) {
		preg_match_all( '/name="count_([a-z0-9_\-]+)"/i', $bhp_c174_html, $m );
		foreach ( $m[1] as $found ) {
			if ( ! in_array( $found, $bhp_c174_slugs, true ) ) {
				return false;
			}
		}
		return true;
	} )()
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 — CACHE HONESTY
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§7 a saved count must not sit behind a stale cached page' );

bhp_c174_ok(
	'§7.1 ⭐ saving purges the SiteGround page cache',
	false !== strpos( $bhp_c174_src, 'sg_cachepress_purge_cache' )
);
bhp_c174_ok(
	'§7.2 ⛔ and the purge is guarded, so a host without the function does not fatal',
	(bool) preg_match( '/function_exists\(\s*[\'"]sg_cachepress_purge_cache[\'"]\s*\)/', $bhp_c174_src )
);
bhp_c174_ok(
	'§7.3 ⭐ an action fires on save so other caches can be hooked without editing this file',
	false !== strpos( $bhp_c174_src, "do_action( 'bhp_visit_shelf_counts_updated'" )
);
/*
 * ⛔ THE COMMITTED FIGURE IS NOT CACHED ACROSS REQUESTS. The shelf file is
 *    explicit that a transient here would let a parent add the copy the previous
 *    parent just bought. Asserted against the shelf source, because this screen
 *    inherits that property rather than restating it.
 */
$bhp_c174_shelf_src = (string) @file_get_contents( WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/school-visit-shelf-stock.php' );
bhp_c174_ok(
	'§7.4 ⛔⛔ the committed count uses NO transient — it is recomputed from real orders every request',
	'' !== $bhp_c174_shelf_src
		&& false === strpos( $bhp_c174_shelf_src, 'set_transient' )
		&& false === strpos( $bhp_c174_shelf_src, 'get_transient' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 — OUTPUT SAFETY
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_c174_head( '§8 escaping' );

bhp_c174_ok(
	'§8.1 the slug and label are escaped where they are printed',
	false !== strpos( $bhp_c174_src, 'esc_html( $row[\'label\'] )' )
		&& false !== strpos( $bhp_c174_src, 'esc_html( $row[\'slug\'] )' )
);
bhp_c174_ok(
	'§8.2 the refused-slug notice is escaped and sanitised on the way in and out',
	false !== strpos( $bhp_c174_src, "array_map( 'sanitize_key', explode" )
		&& false !== strpos( $bhp_c174_src, 'esc_html( implode' )
);
bhp_c174_ok(
	'§8.3 the form action is escaped',
	false !== strpos( $bhp_c174_src, "esc_url( admin_url( 'admin-post.php' ) )" )
);
bhp_c174_ok(
	'§8.4 ⛔ the rendered screen emits no unescaped <script>',
	false === stripos( $bhp_c174_html, '<script' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * TOTALS
 * ═══════════════════════════════════════════════════════════════════════════ */
echo PHP_EOL . '=== CYCLE174 SHELF ADMIN: ' . (int) $GLOBALS['bhp_c174_pass'] . ' passed, ' . (int) $GLOBALS['bhp_c174_fail'] . ' FAILED ===' . PHP_EOL;
