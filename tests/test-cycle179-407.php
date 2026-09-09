<?php
/**
 * CYCLE179-LD-BUILD-407-STOCK-GATE — theme 1.19.407 / plugin 1.8.89.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE DEFECT THIS SUITE EXISTS FOR, IN ONE SENTENCE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Setting the Mariana coloring book out of stock stopped ONE control and left
 * SIX live, because every offer surface asked `is_purchasable()`, and
 * WooCommerce's `is_purchasable()` does not consider stock. Clicking any of
 * the six put ONE $11.99 book in the cart and showed the parent no message.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ HOW BOTH STATES ARE TESTED WITHOUT WRITING TO A SINGLE PRODUCT RECORD
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS SUITE MUTATES NOTHING. No post, no product, no variation, no price,
 *    no stock field, no option, no coupon, no cart. It flips
 *    `woocommerce_product_is_in_stock` — WooCommerce's own filter, read inside
 *    `WC_Product::is_in_stock()` — for the coloring product id only, for the
 *    duration of one block, and removes it again.
 *
 * ⭐ WHY THAT IS THE RIGHT INSTRUMENT AND NOT A SHORTCUT: a suite that set
 *    `stock_status` for real would be a WooCommerce product mutation running
 *    on whatever environment someone points it at, and it would leave the
 *    store wrong if it died mid-run. A filter cannot outlive the process.
 *
 * ⛔ AND THE TEARDOWN IS ASSERTED, NOT ASSUMED. Section 3 re-reads every gate
 *    after the filter is removed and fails if any of them stayed shut. A
 *    suite that left a false out-of-stock behind would be worse than no suite.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * THE SIX SURFACES, EACH ASSERTED IN BOTH STATES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   S1  coloring PDP, its own single ADD control  (format-cards.php, rendered)
 *   S2  the pair block on the coloring PDP and on the Mariana paperback PDP
 *   S3  the shop grid pair strip
 *   S4  the pair landing page and its three ADD THE SET controls
 *   S5  the cart drawer's "Add The Coloring Book" row
 *   S6  /read-aloud/'s combo block
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, said plainly so a PASS is not over-read.
 *    This is PHP. It reads predicates, source and rendered markup. It cannot
 *    prove a button is invisible on a phone, that focus actually moved, or
 *    that a click did nothing. Those are browser claims and they are recorded
 *    separately in the build report, at an asserted `window.innerWidth`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_c407_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_c407_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * ⛔⛔ THE PLUGIN IS NOT UNDER THE THEME ON A REAL INSTALL, AND THIS
 *     FUNCTION EXISTS BECAUSE THE FIRST RUN OF THIS SUITE PROVED IT.
 *
 * ⚠ OWN ERROR, RECORDED RATHER THAN QUIETLY REBUILT. Section 4 originally
 *    read `plugins/brave-hearts-bundle-pricing/includes/offer-engine.php`
 *    through `bhp_c407_read()`, which resolves under `get_template_directory()`.
 *    In the REPOSITORY that path exists; on the SERVER it does not, because the
 *    theme artefact's own gate excludes `plugins/` from the theme ZIP. Four
 *    assertions read an empty string and failed on staging while the behaviour
 *    they describe was demonstrably correct in sections 2 and 3.
 *
 * ⭐ THE LESSON IS THE REASON THIS COMMENT IS LONG: a source assertion that
 *    silently reads '' does not fail safe. 4.2 and 4.8 are `false === strpos()`
 *    and `substr_count() === 2` tests, and an empty haystack would have made
 *    4.2 PASS for the wrong reason had it not been paired with a positive
 *    regex. Every reader below therefore asserts it actually got bytes.
 */
function bhp_c407_read_plugin( $rel ) {
	$path = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/' . ltrim( $rel, '/' );
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · ENVIRONMENT. Everything below depends on these, so they are asserted
 *     rather than assumed, and the ids are RESOLVED BY SKU, never typed.
 *     ⛔ The brief's "product 618" is a PRODUCTION id. On staging 618 is a
 *        private `bhp_lead_event`. Hardcoding it would test a lead record.
 * ═══════════════════════════════════════════════════════════════════════════ */

bhp_c407_assert( function_exists( 'bhp_offer_is_purchasable' ), '0.1: plugin loaded — bhp_offer_is_purchasable() exists', $failures );
bhp_c407_assert( function_exists( 'bhp_offer_is_offerable' ), '0.2: plugin loaded — bhp_offer_is_offerable() exists', $failures );
bhp_c407_assert( function_exists( 'bhp_offer_is_in_stock' ), '0.3: ⭐ 1.8.89 — bhp_offer_is_in_stock() exists (the new display-side predicate)', $failures );

$c407_ids = function_exists( 'bhp_colouring_product_ids' ) ? bhp_colouring_product_ids() : array();
$c407_col = isset( $c407_ids['mariana'] ) ? (int) $c407_ids['mariana'] : 0;
bhp_c407_assert( $c407_col > 0, "0.4: the Mariana coloring product resolves BY SKU on this environment (id {$c407_col})", $failures );

$c407_key    = 'mariana_pb_colouring';
$c407_hc_key = 'mariana_hc_colouring';
$c407_cat    = function_exists( 'bhp_offer_catalog' ) ? bhp_offer_catalog() : array();
bhp_c407_assert( isset( $c407_cat[ $c407_key ] ), '0.5: the paperback pair offer is in the catalogue', $failures );

if ( ! $c407_col || ! function_exists( 'bhp_offer_is_in_stock' ) ) {
	echo "\nRESULT: " . ( count( $failures ) + 1 ) . " FAILURE(S) — environment not fit to continue\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	echo "  - 0.x: aborted before the state tests\n";
	return;
}

/* The filter is scoped to ONE product id, so everything else in the store
 * keeps whatever stock status it really has and the hardcover / C12-C13
 * question is untouched by this suite. */
$GLOBALS['bhp_c407_oos_id'] = $c407_col;
function bhp_c407_force_oos( $in_stock, $product ) {
	if ( $product && (int) $product->get_id() === (int) $GLOBALS['bhp_c407_oos_id'] ) {
		return false;
	}
	return $in_stock;
}

/**
 * Read every gate at once, so IN-STOCK and OUT-OF-STOCK are compared over an
 * identical set of questions rather than over two hand-written lists.
 */
function bhp_c407_probe( $key, $hc_key, $col_id ) {
	$out = array();

	$out['is_purchasable'] = bhp_offer_is_purchasable( $key );
	$out['is_in_stock']    = bhp_offer_is_in_stock( $key );
	$out['is_offerable']   = bhp_offer_is_offerable( $key );
	$out['hc_offerable']   = bhp_offer_is_offerable( $hc_key );

	// S2 — the pair block (coloring PDP + Mariana paperback PDP, one builder).
	$out['s2_module'] = function_exists( 'bhp_offer_render_module' )
		? trim( (string) bhp_offer_render_module( $key, 'bhp-offer--product' ) )
		: 'ABSENT-FUNCTION';

	// S3 — the shop grid strip.
	$out['s3_cards'] = function_exists( 'bhp_offer_shop_card_items' )
		? trim( (string) bhp_offer_shop_card_items() )
		: 'ABSENT-FUNCTION';

	// The ship-home degrade, which must NOT stand in for a stock refusal.
	$out['s3_shiphome'] = function_exists( 'bhp_offer_shop_shiphome_module' )
		? trim( (string) bhp_offer_shop_shiphome_module( $key ) )
		: 'ABSENT-FUNCTION';

	// S4 — the pair landing page.
	$out['s4_available'] = function_exists( 'bhp_pair_landing_available' )
		? (bool) bhp_pair_landing_available()
		: null;
	if ( function_exists( 'bhp_pair_landing_render_final_cta' ) ) {
		ob_start();
		bhp_pair_landing_render_final_cta();
		$out['s4_final'] = trim( (string) ob_get_clean() );
	} else {
		$out['s4_final'] = 'ABSENT-FUNCTION';
	}

	// S5 — the cart drawer rail.
	$rows           = function_exists( 'bhp_offer_drawer_payload' ) ? bhp_offer_drawer_payload() : array();
	$out['s5_rows'] = count( (array) $rows );
	$out['s5_json'] = wp_json_encode( $rows );

	// S6 — /read-aloud/.
	$out['s6_combo']   = function_exists( 'bhp_read_aloud_combo' ) ? count( (array) bhp_read_aloud_combo() ) : -1;
	$out['s6_blocked'] = function_exists( 'bhp_read_aloud_offer_blocked_by_visit' )
		? (bool) bhp_read_aloud_offer_blocked_by_visit( $key )
		: null;

	// S1 — the coloring PDP's own control, RENDERED, not read from source.
	$out['s1_html'] = 'ABSENT-FUNCTION';
	if ( function_exists( 'bhp_colouring_purchase_data' ) ) {
		$data = bhp_colouring_purchase_data( $col_id );
		if ( $data ) {
			$initial = 'paperback';
			$tpl     = get_template_directory() . '/template-parts/commerce/format-cards.php';
			if ( file_exists( $tpl ) ) {
				ob_start();
				include $tpl;
				$out['s1_html'] = (string) ob_get_clean();
			}
		}
	}

	return $out;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · CONTROL STATE — the store as it really is right now.
 *
 * ⛔ THIS SECTION IS THE REASON THE SUITE IS TRUSTWORTHY. If the coloring book
 *    happens to be out of stock when someone runs this, section 1 says so and
 *    fails LOUDLY, rather than the whole file silently "passing" because every
 *    control was already absent.
 * ═══════════════════════════════════════════════════════════════════════════ */

$live = bhp_c407_probe( $c407_key, $c407_hc_key, $c407_col );

bhp_c407_assert( true === $live['is_purchasable'], '1.1: CONTROL — the pair offer is purchasable on this environment', $failures );
bhp_c407_assert( true === $live['is_in_stock'], '1.2: CONTROL — every component reads IN STOCK right now (if this fails, the store is currently out of stock and section 2 cannot be read)', $failures );
bhp_c407_assert( true === $live['is_offerable'], '1.3: CONTROL — the pair offer is offerable', $failures );
bhp_c407_assert( '' !== $live['s2_module'] && 'ABSENT-FUNCTION' !== $live['s2_module'], '1.4: CONTROL S2 — the PDP pair block renders', $failures );
bhp_c407_assert( false !== strpos( (string) $live['s3_cards'], 'bhp-offer' ), '1.5: CONTROL S3 — the shop grid emits a pair card', $failures );
bhp_c407_assert( true === $live['s4_available'], '1.6: CONTROL S4 — the pair landing page is available', $failures );
bhp_c407_assert( '' !== $live['s4_final'] && 'ABSENT-FUNCTION' !== $live['s4_final'], '1.7: CONTROL S4 — the landing final CTA renders', $failures );
bhp_c407_assert( $live['s5_rows'] >= 1, "1.8: CONTROL S5 — the cart drawer rail carries {$live['s5_rows']} coloring row(s)", $failures );
bhp_c407_assert( $live['s6_combo'] > 0, '1.9: CONTROL S6 — /read-aloud/ builds its combo block', $failures );
bhp_c407_assert( false !== strpos( (string) $live['s1_html'], 'ADD PAPERBACK' ), '1.10: CONTROL S1 — the coloring PDP control reads ADD PAPERBACK while the book is in stock', $failures );
bhp_c407_assert( false === strpos( (string) $live['s1_html'], 'Temporarily unavailable' ), '1.11: CONTROL S1 — the unavailable label is ABSENT while the book is in stock', $failures );
bhp_c407_assert( false === strpos( (string) $live['s1_html'], 'tabindex="-1"' ), '1.12: CONTROL S1 — the in-stock control is NOT removed from the tab order', $failures );
bhp_c407_assert( '' === (string) $live['s3_shiphome'], '1.13: CONTROL — the ship-home degrade is absent for an ordinary in-stock session', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · OUT OF STOCK — the same probe, one filter added.
 * ═══════════════════════════════════════════════════════════════════════════ */

add_filter( 'woocommerce_product_is_in_stock', 'bhp_c407_force_oos', 99, 2 );
$oos = bhp_c407_probe( $c407_key, $c407_hc_key, $c407_col );

/* 2 · THE PREDICATES ------------------------------------------------------ */

bhp_c407_assert( false === $oos['is_in_stock'], '2.1: bhp_offer_is_in_stock() goes FALSE when a component is out of stock', $failures );
bhp_c407_assert( false === $oos['is_offerable'], '2.2: bhp_offer_is_offerable() goes FALSE with it — the one gate that feeds every display surface', $failures );
bhp_c407_assert( false === $oos['hc_offerable'], '2.3: the HARDCOVER pair offer closes too — its coloring half is the same book', $failures );

/* ⛔⛔ 2.4 IS THE SAFETY ASSERTION AND IT IS THE MOST IMPORTANT LINE IN THIS
 *     FILE. `offer-engine.php` carries an explicit warning that
 *     `bhp_offer_apply_fees()` reads `bhp_offer_is_purchasable()` to decide
 *     whether a cart's DISCOUNT applies. Had the stock test gone in there, a
 *     parent whose cart ALREADY held a legally-added pair would lose $1.99 and
 *     watch their total GO UP. This asserts the warning was honored: the
 *     pricing predicate is UNMOVED and still TRUE. If a future edit "tidies"
 *     the gate into is_purchasable(), THIS LINE IS WHAT CATCHES IT. */
bhp_c407_assert( true === $oos['is_purchasable'], '2.4: ⛔ SAFETY — bhp_offer_is_purchasable() is UNCHANGED and still TRUE, so an already-assembled pair cart keeps its discount and no total goes up', $failures );

/* 2 · THE SIX SURFACES ---------------------------------------------------- */

bhp_c407_assert( '' === (string) $oos['s2_module'], '2.5: S2 — the PDP pair block (ADD BOTH FOR $22.99 plus the hardcover swap) renders NOTHING on the coloring PDP and the Mariana paperback PDP', $failures );
bhp_c407_assert( false === strpos( (string) $oos['s3_cards'], 'bhp-offer' ), '2.6: S3 — the shop grid pair strip and its ADD TO CART are gone', $failures );
bhp_c407_assert( false === $oos['s4_available'], '2.7: S4 — the pair landing page reports unavailable', $failures );
bhp_c407_assert( '' === (string) $oos['s4_final'], '2.8: S4 — none of the landing page ADD THE SET controls render', $failures );
bhp_c407_assert( 0 === (int) $oos['s5_rows'], '2.9: S5 — the cart drawer rail carries ZERO coloring rows, so the Add The Coloring Book row cannot be built', $failures );
bhp_c407_assert( false === strpos( (string) $oos['s5_json'], 'colouring' ), '2.10: S5 — no coloring component survives anywhere in the drawer payload JSON', $failures );
bhp_c407_assert( 0 === (int) $oos['s6_combo'], '2.11: S6 — /read-aloud/ builds no combo block, so its heading, framing line and pair strip are all absent', $failures );

/* 2 · S1, THE ONE SURFACE THAT STAYS AND MUST DEGRADE ---------------------- */

bhp_c407_assert( false === strpos( (string) $oos['s1_html'], 'ADD PAPERBACK' ), '2.12: S1 — the coloring PDP control STOPS reading ADD PAPERBACK and its price', $failures );
bhp_c407_assert( false !== strpos( (string) $oos['s1_html'], 'Temporarily unavailable' ), '2.13: S1 — it states a reason instead (PLACEHOLDER COPY, needs Andrew approval)', $failures );
bhp_c407_assert( false !== strpos( (string) $oos['s1_html'], 'aria-disabled="true"' ), '2.14: S1 — the control is announced disabled', $failures );
bhp_c407_assert( false !== strpos( (string) $oos['s1_html'], 'tabindex="-1"' ), '2.15: S1 — and it is OUT OF THE TAB ORDER, so Enter no longer reaches the add URL the mouse was blocked from', $failures );
bhp_c407_assert( false === strpos( (string) $oos['s1_html'], 'add-to-cart=' . $c407_col ), '2.16: S1 — the live add-to-cart href is REMOVED from the control, not merely dimmed', $failures );
bhp_c407_assert( false === strpos( (string) $oos['s1_html'], 'data-bhp-cart-add' ), '2.17: S1 — the cart-drawer hook is removed too, so a click is not intercepted into a silent failure', $failures );

/* ⛔ 2.18 — THE DEGRADE THAT MUST **NOT** HAPPEN. `purchasable && !offerable`
 *    used to mean exactly one thing: this SESSION is refused, and ship-to-home
 *    is the remedy. From 1.8.89 it also means out of stock, which has no
 *    remedy. Without the third clause added in 1.19.407 the shop would have
 *    swapped a dead ADD control for a WORSE card: an invitation to pay postage
 *    for a book that cannot be printed. */
bhp_c407_assert( '' === (string) $oos['s3_shiphome'], '2.18: ⛔ the SHIP-HOME remedy card does NOT appear for a stock refusal — it is a school-visit remedy and out of stock has no remedy to offer', $failures );
bhp_c407_assert( false === $oos['s6_blocked'], '2.19: ⛔ /read-aloud/ link mode does NOT fire for a stock refusal, for the same reason', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · TEARDOWN, AND IT IS ASSERTED
 * ═══════════════════════════════════════════════════════════════════════════ */

remove_filter( 'woocommerce_product_is_in_stock', 'bhp_c407_force_oos', 99 );
$back = bhp_c407_probe( $c407_key, $c407_hc_key, $c407_col );

bhp_c407_assert( true === $back['is_in_stock'], '3.1: TEARDOWN — the filter is gone and stock reads true again', $failures );
bhp_c407_assert( true === $back['is_offerable'], '3.2: TEARDOWN — the pair offer is offerable again', $failures );
bhp_c407_assert( $back['s5_rows'] === $live['s5_rows'], '3.3: TEARDOWN — the drawer rail is back to exactly the row count it had before this suite ran', $failures );
bhp_c407_assert( $back['s2_module'] === $live['s2_module'], '3.4: TEARDOWN — the PDP pair block is byte-identical to the control render', $failures );
bhp_c407_assert( $back['s1_html'] === $live['s1_html'], '3.5: TEARDOWN — the coloring PDP control is byte-identical to the control render', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · THE SOURCE PROPERTIES A RENDER CANNOT SHOW
 * ═══════════════════════════════════════════════════════════════════════════ */

$c407_engine = bhp_c407_read_plugin( 'includes/offer-engine.php' );
$c407_fc     = bhp_c407_read( 'template-parts/commerce/format-cards.php' );
$c407_js     = bhp_c407_read( 'assets/js/book-formats.js' );
$c407_cl     = bhp_c407_read( 'inc/colouring-line.php' );

/* ⛔ 4.0 GUARDS EVERY ASSERTION BELOW IT. Without it an unreadable file
   turns section 4's absence tests into free passes. */
bhp_c407_assert( strlen( $c407_engine ) > 10000, '4.0: the INSTALLED offer-engine.php was actually read from WP_PLUGIN_DIR (' . strlen( $c407_engine ) . ' bytes) — not an empty string standing in for source', $failures );
bhp_c407_assert( false !== strpos( $c407_engine, 'function bhp_offer_is_in_stock' ), '4.1: the predicate is declared in the offer engine', $failures );

/* ⛔ 4.2 IS 2.4's STATIC TWIN. 2.4 proves the behaviour today; this proves the
 *    SHAPE, so a refactor that keeps the behaviour by accident still fails. */
bhp_c407_assert(
	1 === preg_match( '/function bhp_offer_is_purchasable\(\s*\$key\s*\)\s*\{\s*return null !== bhp_offer_components\(\s*\$key\s*\);\s*\}/', $c407_engine ),
	'4.2: ⛔ bhp_offer_is_purchasable() is still a one-line components test — the stock gate was NOT moved into the pricing path',
	$failures
);

bhp_c407_assert( false === strpos( $c407_engine, '! $product->is_purchasable() || ! $product->is_in_stock()' ), '4.3: ⛔ bhp_offer_components() was NOT gated on stock either — it is what bhp_offer_apply_fees() resolves through', $failures );

bhp_c407_assert( false !== strpos( $c407_cl, "define('BHP_COLOURING_UNAVAILABLE_CTA', 'Temporarily unavailable')" ), '4.4: the unavailable label lives in ONE constant, so the approved wording replaces it in one place', $failures );

/* ⭐ 4.5 — THE SCOPE LINE. The chapter-book hardcover is out of stock ON
 *    PURPOSE (open C12/C13) and this build must not restate that standing
 *    product decision as a temporary printing problem on six product pages. */
bhp_c407_assert( false !== strpos( $c407_fc, "0 === strpos((string) \$data['key'], 'colouring_')" ), '4.5: the relabel is scoped to the coloring rail by key — the chapter-book hardcover CTA is untouched', $failures );

bhp_c407_assert( false !== strpos( $c407_js, "ctaEl.setAttribute('tabindex', '-1')" ) && false !== strpos( $c407_js, "ctaEl.removeAttribute('tabindex')" ), '4.6: the script sets AND clears tabindex, so switching back to an in-stock format restores focusability', $failures );

/* ⛔ 4.7 — NO PARTIAL ADD. The server entry point refuses the whole offer. */
bhp_c407_assert( false !== strpos( $c407_engine, "if ( function_exists( 'bhp_offer_is_in_stock' ) && ! bhp_offer_is_in_stock( \$key ) ) {" ), '4.7: bhp_offer_add_to_cart() refuses an out-of-stock offer up front, so a bookmarked POST cannot produce a half-added pair', $failures );

/* ⛔ 4.8 — AND IT COINS NO SECOND SENTENCE FOR THE SAME EVENT. */
bhp_c407_assert( 2 === substr_count( $c407_engine, "wc_add_notice( 'That offer is not available right now.', 'error' );" ), '4.8: the refusal reuses the existing string — no new customer copy was written in the engine', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · COPY RAILS ON THE ONE NEW STRING
 * ═══════════════════════════════════════════════════════════════════════════ */

$c407_new_copy = defined( 'BHP_COLOURING_UNAVAILABLE_CTA' ) ? (string) BHP_COLOURING_UNAVAILABLE_CTA : '';
bhp_c407_assert( '' !== $c407_new_copy, '5.0: the constant is defined at runtime', $failures );
bhp_c407_assert( false === strpos( $c407_new_copy, "\xe2\x80\x94" ) && false === strpos( $c407_new_copy, "\xe2\x80\x93" ), '5.1: the new string carries no em dash or en dash', $failures );
bhp_c407_assert( 0 === preg_match( '/\b(we|us|our|ours)\b/i', $c407_new_copy ), '5.2: no we/us/our', $failures );
bhp_c407_assert( 0 === preg_match( '/\b(colour|favourite|realise|organis)/i', $c407_new_copy ), '5.3: American spelling', $failures );
bhp_c407_assert( 0 === preg_match( '/\b(back (in|on)|soon|shortly|next week|restock)\b/i', $c407_new_copy ), '5.4: it promises no return date — nobody can honestly make that promise while the printing problem is open', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · VERSIONS
 * ═══════════════════════════════════════════════════════════════════════════ */

$c407_style = bhp_c407_read( 'style.css' );
bhp_c407_assert( 1 === preg_match( '/^Version:\s*1\.19\.407\s*$/m', $c407_style ), '6.1: theme style.css declares 1.19.407', $failures );
/*
 * ⭐⭐ 1.19.409 (2026-09-08, `CYCLE179-LD-BUILD-409`, F3) — A FLOOR, NOT A PIN.
 *     Same move, same reasoning and the same shape as the 1.19.406 fix to
 *     `test-cycle179-397.php` §4.1; see that block for the long argument.
 *
 * ⛔⛔ THE SUPERSEDED ASSERTION, PRESERVED STRUCK AT THE LINE:
 *
 *      ~~bhp_c407_assert( defined( 'BHP_BUNDLE_PRICING_VERSION' )
 *          && '1.8.89' === BHP_BUNDLE_PRICING_VERSION,
 *          '6.2: the ACTIVE plugin reports 1.8.89 — read from the constant,
 *          not from the file on disk', $failures );~~
 *
 * ⚠️ WHY IT MOVED, AND IT IS AN OBSERVED FAILURE, NOT A PRECAUTION. The bundle
 *    plugin on staging2 reads **1.8.91** (VERIFIED first-hand over SSH,
 *    `wp plugin get brave-hearts-bundle-pricing --field=version`, 2026-09-08).
 *    A literal pin reports that ROUTINE, CORRECT plugin release as a THEME
 *    defect, which is exactly the false-failure this suite is supposed to
 *    make impossible to ignore.
 *
 * ⭐ WHAT §6 IS ACTUALLY FOR. It is "the versions this suite's assumptions
 *    rest on". The assumption here is that the theme runs against a plugin
 *    NEW ENOUGH to carry the 1.8.89 out-of-stock display behaviour §5 and
 *    §0.3 exercise — not that it runs against one exact build.
 *
 * ⛔ IT IS STILL A REAL GATE. `version_compare` with '>=' FAILS on a
 *    DOWNGRADE below 1.8.89 — the regression this row exists to catch — and
 *    the undefined-constant case still fails. Raise the floor deliberately
 *    when a later version becomes a hard requirement.
 *
 * ⚠️ 6.1 IS DELIBERATELY LEFT AS A LITERAL PIN and is EXPECTED to fail on
 *    every later theme build. That row is this file's own version stamp: it
 *    records which release this suite was written against, and turning it
 *    into a floor would erase that. The one FAIL line it produces per bump is
 *    mechanical and is reported as such in every build diff.
 */
bhp_c407_assert( defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.89', '>=' ), '6.2: the ACTIVE plugin reports at least 1.8.89 — read from the constant, not from the file on disk (got ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '(undefined)' ) . ')', $failures );

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
