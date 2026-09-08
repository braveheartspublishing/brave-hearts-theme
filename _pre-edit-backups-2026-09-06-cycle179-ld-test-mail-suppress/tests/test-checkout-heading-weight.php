<?php
/**
 * Brave Hearts — CHECKOUT SECTION HEADING WEIGHT (theme 1.19.194).
 * CYCLE144-LD-231
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-checkout-heading-weight.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the Chief of Staff
 * and NOT witnessed first-hand by the agent that wrote this file):
 *
 *   "also Make the headings on the checkout page bold 'Contact Information' etc"
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * This is a PHP/CLI suite. It has NO layout engine and NO font rasteriser, so
 * it cannot read a computed `font-weight`, cannot resolve CSS specificity, and
 * cannot see how a glyph renders. Claiming otherwise would be a fabricated
 * verification, which the standing rules put in the same class as a fabricated
 * review. The computed weights are measured in a real browser on staging and
 * recorded in the session's QA evidence, not here.
 *
 * What it DOES prove, and each of these regresses silently:
 *
 *   §1  The stylesheet carrying the rule is still enqueued on /cart/ and
 *       /checkout/, and ONLY there.
 *   §2  The rule is present in the SHIPPED stylesheet and names all three
 *       heading families the checkout actually renders.
 *   §3  ⭐ `font-variation-settings: 'wght' 700` is present alongside
 *       `font-weight: 700`. This is the assertion that matters most: the
 *       self-hosted brand fonts declare NO 700 face, so `font-weight: 700`
 *       on its own resolves to the 600 (Cormorant) / 500 (EB Garamond)
 *       face and changes nothing a customer can see. A future "tidy-up"
 *       that deletes the axis line would silently un-bold the headings
 *       while leaving a rule that still LOOKS correct.
 *   §4  ⭐ EVERY SELECTOR IS SCOPED UNDER `.wc-block-checkout`. The
 *       stylesheet loads on /cart/ too, and the instruction named the
 *       checkout page. An unscoped selector would silently restyle the
 *       cart page as well.
 *   §5  ⭐ THE SELECTORS OUT-SPECIFY WOOCOMMERCE'S OWN RULES. Blocks ships
 *       `.wc-block-components-title.wc-block-components-title` — a
 *       deliberately doubled class, specificity (0,2,0). A single-class
 *       override would TIE, and a tie is decided by stylesheet order,
 *       which WooCommerce controls and we do not. Each selector here
 *       therefore carries three classes. Asserted rather than trusted,
 *       because the failure is invisible in the source.
 *   §6  The fonts the rule depends on are still declared as VARIABLE-range
 *       families in assets/fonts/fonts.css with no 700 face — i.e. the
 *       premise of §3 still holds. If a real 700 face is ever added, this
 *       assertion fails loudly and the rule can be simplified.
 *   §7  ⛔ NO PAYMENT PATH WAS TOUCHED. The appended block must contain no
 *       payment-gateway selector, and the theme must not filter payment
 *       gateways.
 *
 * ⛔ NO ORDER IS CREATED. NO CART IS BUILT. No product record, price, coupon,
 *    stock level, shipping, tax or payment setting is read or written by any
 *    part of this file, on any environment.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⚠ THE COUNTERS ARE IN $GLOBALS ON PURPOSE, AND THIS IS NOT STYLE.
 *   `wp eval-file` includes this file INSIDE a function, so a plain
 *   `$pass` at the top level here is a LOCAL, and a `global $pass` inside
 *   the helper below binds to a different, always-empty variable. The
 *   first run of this suite reported "0 passed, 0 failed" underneath 21
 *   PASS lines for exactly that reason. Fixed, and recorded so it is not
 *   re-derived by the next suite written in this repo.
 */
$GLOBALS['bhp_chw_pass'] = 0;
$GLOBALS['bhp_chw_fail'] = 0;

/**
 * @param string $label
 * @param bool   $ok
 * @param string $detail
 */
function bhp_chw_assert( $label, $ok, $detail = '' ) {
	if ( $ok ) {
		$GLOBALS['bhp_chw_pass']++;
		WP_CLI::log( "  PASS  {$label}" );
	} else {
		$GLOBALS['bhp_chw_fail']++;
		WP_CLI::log( "  FAIL  {$label}" . ( $detail ? "  --  {$detail}" : '' ) );
	}
}

WP_CLI::log( '' );
WP_CLI::log( '=== CHECKOUT SECTION HEADING WEIGHT — theme ' . wp_get_theme()->get( 'Version' ) . ' ===' );

$theme_dir = get_template_directory();
$css_path  = $theme_dir . '/assets/css/checkout-experience.css';
$php_path  = $theme_dir . '/inc/checkout-experience.php';
$fonts_css = $theme_dir . '/assets/fonts/fonts.css';

$css = file_exists( $css_path ) ? file_get_contents( $css_path ) : '';
$php = file_exists( $php_path ) ? file_get_contents( $php_path ) : '';

/* ───────────────────────────────────────────────────────────────────────────
 * §1  The stylesheet is enqueued on cart and checkout, and only there.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§1  ENQUEUE SCOPE' );

bhp_chw_assert( 'checkout-experience.css exists', '' !== $css, $css_path );
bhp_chw_assert(
	'inc/checkout-experience.php enqueues it',
	false !== strpos( $php, 'assets/css/checkout-experience.css' )
);
bhp_chw_assert(
	'the enqueue is gated on is_cart() || is_checkout(), and returns otherwise',
	(bool) preg_match( '/if\s*\(\s*!function_exists\(\s*[\'"]is_cart[\'"]\s*\)\s*\|\|\s*\(\s*!is_cart\(\)\s*&&\s*!is_checkout\(\)\s*\)\s*\)\s*\{\s*return;/', $php )
);

/* ───────────────────────────────────────────────────────────────────────────
 * §2–§5  The rule itself.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§2  THE RULE IS IN THE SHIPPED STYLESHEET' );

/* Isolate the appended block so the assertions cannot be satisfied by some
 * unrelated part of the file. */
$marker = 'THE CHECKOUT SECTION HEADINGS ARE BOLD';
$at     = strpos( $css, $marker );
bhp_chw_assert( 'the 1.19.194 block is present', false !== $at, 'marker: ' . $marker );

/*
 * ⚠ THE BLOCK STARTS AT THE COMMENT'S `/*`, NOT AT THE MARKER, AND THIS IS
 *   THE SECOND HALF OF THE SAME BUG. The marker lives INSIDE the comment, so
 *   slicing from it produces a string with a comment TAIL and no opening
 *   delimiter — and the comment-stripper below then finds nothing to strip
 *   and hands prose to the selector parser. Rewind to the delimiter first.
 */
/*
 * ⭐ CORRECTED 2026-08-12 (theme 1.19.220, CYCLE155-LD-02) — THE SLICE IS
 *    NOW BOUNDED AT THE END OF THIS BLOCK'S OWN RULE. It used to run to the
 *    END OF THE FILE.
 *
 *    THE SUPERSEDED LINE, preserved verbatim so this reads as a repair and
 *    not as a silent rewrite:
 *
 *        $block = substr( $css, false === $open ? $at : $open );
 *
 *    That was harmless for exactly as long as the heading-weight rule was
 *    the LAST thing in checkout-experience.css — which it was, from 1.19.194
 *    until 1.19.220 appended the `.bhp-checkout-free` panel after it.
 *    The moment anything followed, this suite began parsing the NEXT
 *    author's selectors and reporting them as its own §4 and §5 failures:
 *
 *        FAIL  every selector is scoped under .wc-block-checkout
 *              -- unscoped: .bhp-checkout-free | .bhp-checkout-free .bhp-free-bullets
 *        FAIL  EVERY selector carries 3+ classes
 *              -- under-specified: .bhp-checkout-free | .bhp-checkout-free .bhp-free-bullets
 *
 *    ⭐ THE TWO FAILURES WERE REAL OUTPUT AND ARE QUOTED RATHER THAN
 *       PARAPHRASED. Both were FALSE POSITIVES — measured, not assumed:
 *       this same suite passes 22/0 against PRODUCTION 1.19.219, whose
 *       stylesheet ends at the heading rule. Nothing about the heading
 *       weight changed; the suite's reach did.
 *
 *    ⛔ NOT ONE ASSERTION IS WEAKENED OR DELETED BY THE BOUND. §4 still
 *       demands every selector be scoped under `.wc-block-checkout`, §5
 *       still demands three classes each, and both still run over the same
 *       three selectors they always did. What changes is only which text is
 *       handed to them. The `.bhp-checkout-free` panel is deliberately NOT
 *       scoped under `.wc-block-checkout` — it renders OUTSIDE that
 *       element, above it — and it is owned by
 *       tests/test-checkout-free-bullets.php, which asserts its scope,
 *       its classes and its anti-drift properties in §5 there.
 *
 *    THE BOUND: the block is one comment plus exactly one rule, so it ends
 *    at the first `}` after the comment closes. A bound on "the next `/*`"
 *    would break the moment somebody wrote a comment inside the rule.
 */
$block = '';
if ( false !== $at ) {
	$open  = strrpos( substr( $css, 0, $at ), '/*' );
	$start = false === $open ? $at : $open;
	$block = substr( $css, $start );

	$comment_end = strpos( $block, '*/' );
	$rule_end    = false === $comment_end ? false : strpos( $block, '}', $comment_end );
	if ( false !== $rule_end ) {
		$block = substr( $block, 0, $rule_end + 1 );
	}
}

/*
 * ⭐ THE BOUND IS ITSELF ASSERTED, so a future edit that removes it fails
 *    here — one clear failure — instead of at §4 and §5, where it looks
 *    like somebody else's selector broke this rule's scoping.
 */
bhp_chw_assert(
	'⭐ the slice stops at this block\'s own rule and does not reach later authors',
	'' !== $block && false === strpos( $block, '.bhp-checkout-free' ) && substr_count( $block, '}' ) === 1,
	'braces in slice: ' . substr_count( $block, '}' )
);

$selectors = array(
	'the checkout step titles (Contact information, Shipping address, Shipping options, Payment options, Additional order information, and Order summary when stacked)'
		=> '.wc-block-checkout .wc-block-components-title.wc-block-components-checkout-step__title',
	'the Express Checkout heading'
		=> '.wc-block-checkout .wc-block-components-express-payment--checkout .wc-block-components-express-payment__title',
	'the desktop sidebar Order summary heading'
		=> '.wc-block-checkout .wp-block-woocommerce-checkout-order-summary-block .wc-block-components-checkout-order-summary__title-text',
);
foreach ( $selectors as $label => $selector ) {
	bhp_chw_assert( "selector present — {$label}", false !== strpos( $block, $selector ), $selector );
}

WP_CLI::log( '' );
WP_CLI::log( '§3  THE VARIABLE-AXIS DECLARATION — the one that actually bolds the glyphs' );

bhp_chw_assert( 'font-weight: 700 is declared', (bool) preg_match( '/font-weight:\s*700\s*;/', $block ) );
bhp_chw_assert(
	"font-variation-settings: 'wght' 700 is declared alongside it",
	(bool) preg_match( '/font-variation-settings:\s*[\'"]wght[\'"]\s+700\s*;/', $block ),
	'without this the request resolves to the heaviest DECLARED face (600/500) and nothing changes visibly'
);
bhp_chw_assert(
	'the two live in the SAME declaration block',
	(bool) preg_match( '/font-weight:\s*700;\s*font-variation-settings:\s*[\'"]wght[\'"]\s+700;/', $block )
);

WP_CLI::log( '' );
WP_CLI::log( '§4  SCOPE — checkout only, never the cart page' );

/*
 * ⚠ COMMENTS ARE STRIPPED FIRST, AND THAT IS LOAD-BEARING. The block above
 *   is mostly prose, and its lines are indented plain text rather than
 *   `*`-prefixed, so a naive line scan reads sentences that happen to end
 *   in a comma as CSS selectors. The first run of this suite did exactly
 *   that and failed §4 and §5 on the sentence "…enqueued on BOTH /cart/
 *   and /checkout/". Strip the comment, then parse.
 */
$css_only      = preg_replace( '#/\*.*?\*/#s', '', $block );
$selector_lines = array();
foreach ( preg_split( '/\R/', $css_only ) as $line ) {
	$trim = trim( $line );
	if ( '' === $trim || 0 === strpos( $trim, '}' ) ) {
		continue;
	}
	if ( false !== strpos( $trim, '{' ) || substr( $trim, -1 ) === ',' ) {
		$selector_lines[] = rtrim( $trim, ' {,' );
	}
}
bhp_chw_assert( 'the block declares at least three selectors', count( $selector_lines ) >= 3, 'found ' . count( $selector_lines ) );
$unscoped = array();
foreach ( $selector_lines as $sel ) {
	if ( 0 !== strpos( $sel, '.wc-block-checkout ' ) ) {
		$unscoped[] = $sel;
	}
}
bhp_chw_assert(
	'every selector is scoped under .wc-block-checkout (the cart page is untouched)',
	empty( $unscoped ),
	'unscoped: ' . implode( ' | ', $unscoped )
);
bhp_chw_assert(
	'no cart-only heading selector was swept in',
	false === strpos( $block, 'wc-block-cart__totals-title' ) && false === strpos( $block, 'wc-block-cart-items__header' ),
	'the instruction named the checkout page'
);

WP_CLI::log( '' );
WP_CLI::log( '§5  SPECIFICITY — the override must beat WooCommerce\'s doubled class' );

foreach ( $selector_lines as $sel ) {
	$classes = preg_match_all( '/\.[A-Za-z0-9_-]+/', $sel );
	bhp_chw_assert(
		"3+ classes (beats Blocks\' (0,2,0)) — " . substr( $sel, 0, 62 ) . '…',
		$classes >= 3,
		"counted {$classes}"
	);
	break; // one representative; the exhaustive pass is immediately below
}
bhp_chw_assert(
	'no !important is used — specificity carries this rule',
	false === strpos( $css_only, '!important' ),
	'!important would be unearned here'
);
$under_specified = array();
foreach ( $selector_lines as $sel ) {
	if ( preg_match_all( '/\.[A-Za-z0-9_-]+/', $sel ) < 3 ) {
		$under_specified[] = $sel;
	}
}
bhp_chw_assert(
	'EVERY selector carries 3+ classes',
	empty( $under_specified ),
	'under-specified: ' . implode( ' | ', $under_specified )
);

/* ───────────────────────────────────────────────────────────────────────────
 * §6  The premise: the self-hosted families still have no 700 face.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§6  THE FONT PREMISE BEHIND §3' );

if ( ! file_exists( $fonts_css ) ) {
	bhp_chw_assert( 'assets/fonts/fonts.css exists', false, $fonts_css );
} else {
	$fonts = file_get_contents( $fonts_css );
	bhp_chw_assert( 'Cormorant Garamond is self-hosted here', false !== strpos( $fonts, "font-family: 'Cormorant Garamond'" ) );
	bhp_chw_assert( 'EB Garamond is self-hosted here', false !== strpos( $fonts, "font-family: 'EB Garamond'" ) );

	/* Collect the declared weights per family from the @font-face blocks. */
	$weights = array( 'Cormorant Garamond' => array(), 'EB Garamond' => array() );
	if ( preg_match_all( '/@font-face\s*\{[^}]*\}/s', $fonts, $faces ) ) {
		foreach ( $faces[0] as $face ) {
			foreach ( array_keys( $weights ) as $fam ) {
				if ( false !== strpos( $face, "font-family: '{$fam}'" ) && preg_match( '/font-weight:\s*(\d+)/', $face, $w ) ) {
					$weights[ $fam ][ (int) $w[1] ] = true;
				}
			}
		}
	}
	foreach ( $weights as $fam => $set ) {
		$declared = array_keys( $set );
		sort( $declared );
		bhp_chw_assert(
			"{$fam} declares NO 700 face — so the axis line in §3 is load-bearing",
			! isset( $set[700] ) && ! empty( $declared ),
			'declared: ' . implode( '/', $declared )
		);
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §7  No payment path touched.
 * ────────────────────────────────────────────────────────────────────────── */
WP_CLI::log( '' );
WP_CLI::log( '§7  NO PAYMENT PATH TOUCHED' );

$payment_needles = array(
	'wc-block-components-radio-control-accordion',
	'wc-block-checkout__payment-method',
	'payment_gateway',
	'stripe',
	'paypal',
);
$hits = array();
foreach ( $payment_needles as $needle ) {
	if ( false !== stripos( $block, $needle ) ) {
		$hits[] = $needle;
	}
}
bhp_chw_assert( 'the appended block names no payment selector or gateway', empty( $hits ), implode( ', ', $hits ) );
bhp_chw_assert(
	'the theme does not filter available payment gateways',
	false === strpos( file_get_contents( $theme_dir . '/functions.php' ), 'woocommerce_available_payment_gateways' )
);

/* ───────────────────────────────────────────────────────────────────────────
 * Result
 * ────────────────────────────────────────────────────────────────────────── */
$pass = (int) $GLOBALS['bhp_chw_pass'];
$fail = (int) $GLOBALS['bhp_chw_fail'];

WP_CLI::log( '' );
WP_CLI::log( "=== {$pass} passed, {$fail} failed ===" );
WP_CLI::log( '' );
WP_CLI::log( '⚠ REMINDER: this suite proves the RULE SHIPPED. It does not and cannot' );
WP_CLI::log( '  prove the rendered weight. That is measured in a real browser on' );
WP_CLI::log( '  staging at 1440px and 390px, and lives in the session QA evidence.' );

if ( $fail > 0 ) {
	WP_CLI::halt( 1 );
}
