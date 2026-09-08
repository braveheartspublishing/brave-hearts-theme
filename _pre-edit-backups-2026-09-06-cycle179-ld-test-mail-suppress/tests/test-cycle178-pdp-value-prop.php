<?php
/**
 * Brave Hearts Theme — THE PRODUCT-PAGE VALUE-PROP LINE IS PRODUCT-AWARE.
 * Theme 1.19.346. `CYCLE178-LD-345-PDP-LINE`, from the `commerce-cx`
 * colouring-line launch review (see internal release notes) F-list.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle178-pdp-value-prop.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE DEFECT THIS PINS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `bhp_woocommerce_product_value_prop()` rendered ONE hardcoded sentence on
 * EVERY product page: "Adventure chapter books for ages 6-9 that combine real
 * places, science, history, courage, and kindness." On the COLOURING PDP that
 * describes an object that does not exist — no chapters, no history, no
 * science. Same class as `CYCLE165-OPS-019` (a colouring cover beside a
 * chapter-book price), read on the copy side instead of the price side.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHY THIS SUITE ENUMERATES PRODUCTS INSTEAD OF HARDCODING IDS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE COLOURING PRODUCT HAS A DIFFERENT POST ID ON EACH ENVIRONMENT —
 *    verified first-hand by SKU lookup on 2026-09-02: **618 on production,
 *    4065 on staging**, both SKU `9798996810840`. A suite asserting "618" would
 *    pass on production and be meaningless on staging, and vice versa. So every
 *    check below classifies by `bhp_colouring_slug_for_product()` — the same
 *    SKU-keyed resolver the shipped gate uses — and asserts over whatever
 *    catalogue this environment actually has.
 *
 * ⛔ NO PRODUCT, PRICE, SKU, OPTION, TIER, CART OR ORDER IS WRITTEN BY THIS
 *    FILE ON ANY ENVIRONMENT. It reads published products, renders a template
 *    fragment into an output buffer, and installs one request-scoped filter in
 *    §5 that dies with the process.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$passes   = 0;

function bhp_vp_assert( $condition, $label, array &$failures, &$passes ) {
	if ( $condition ) {
		$passes++;
		echo "  PASS  {$label}\n";
		return true;
	}
	$failures[] = $label;
	echo "  FAIL  {$label}\n";
	return false;
}

/** Render the value-prop partial for one product id and return its markup. */
function bhp_vp_render( $product_id ) {
	global $product;
	$previous = $product;
	$product  = wc_get_product( $product_id );
	ob_start();
	bhp_woocommerce_product_value_prop();
	$html    = ob_get_clean();
	$product = $previous;
	return $html;
}

/** The <p class="...__hook"> text of a rendered partial, or '' if absent. */
function bhp_vp_hook_text( $html ) {
	if ( ! preg_match( '~<p class="bhp-product-value-prop__hook">(.*?)</p>~s', $html, $m ) ) {
		return '';
	}
	return html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' );
}

$CHAPTER_HOOK   = 'Adventure chapter books for ages 6–9 that combine real places, science, history, courage, and kindness.';
$COLOURING_HOOK = 'A coloring adventure for ages 6 to 9';

echo "\n=== CYCLE178-LD-345-PDP-LINE — product-page value-prop line ===\n";

/* ─────────────────────────────────────────────────────────────────────────
 * §0 · The environment can answer the question at all.
 * ───────────────────────────────────────────────────────────────────────── */
echo "\n§0 Preconditions\n";
bhp_vp_assert( function_exists( 'bhp_woocommerce_product_value_prop' ), '§0.1 bhp_woocommerce_product_value_prop() exists', $failures, $passes );
bhp_vp_assert( function_exists( 'bhp_colouring_slug_for_product' ), '§0.2 bhp_colouring_slug_for_product() exists (theme resolver)', $failures, $passes );
bhp_vp_assert( function_exists( 'bhp_colouring_product_ids' ), '§0.3 bhp_colouring_product_ids() exists (plugin SKU resolver)', $failures, $passes );
bhp_vp_assert( function_exists( 'bhp_colouring_draft_copy' ), '§0.4 bhp_colouring_draft_copy() exists', $failures, $passes );

/* ─────────────────────────────────────────────────────────────────────────
 * §1 · The new string itself, and the standing copy rail.
 * ───────────────────────────────────────────────────────────────────────── */
echo "\n§1 The colouring string obeys the standing copy rail\n";
$copy = function_exists( 'bhp_colouring_draft_copy' ) ? bhp_colouring_draft_copy( 'value_prop_hook' ) : '';
bhp_vp_assert( $copy === $COLOURING_HOOK, sprintf( '§1.1 value_prop_hook is exactly the approved brief text (got "%s")', $copy ), $failures, $passes );
bhp_vp_assert( false === strpos( $copy, '—' ), '§1.2 no em dash (standing rail)', $failures, $passes );
bhp_vp_assert( ! preg_match( '~\b(we|us|our)\b~i', $copy ), '§1.3 no company "we"/"us"/"our" (Standing Rules §9.1)', $failures, $passes );
bhp_vp_assert( false !== strpos( $copy, '6 to 9' ), '§1.4 states ages 6 to 9', $failures, $passes );
bhp_vp_assert( false === strpos( $copy, '5' ), '§1.5 never 5-9 (Standing Rules §9)', $failures, $passes );
bhp_vp_assert( ! preg_match( '~\bchapter|science|history|Lexile~i', $copy ), '§1.6 makes no chapter-book claim', $failures, $passes );

/* ─────────────────────────────────────────────────────────────────────────
 * §2 · Classify this environment's real catalogue by the shipped resolver.
 * ───────────────────────────────────────────────────────────────────────── */
echo "\n§2 Catalogue classification (by SKU resolver, not by hardcoded id)\n";
$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$colouring = array();
$chapter   = array();
foreach ( $product_ids as $pid ) {
	if ( null !== bhp_colouring_slug_for_product( $pid ) ) {
		$colouring[] = $pid;
	} else {
		$chapter[] = $pid;
	}
}
bhp_vp_assert( count( $colouring ) >= 1, sprintf( '§2.1 at least one colouring product resolves here (found %d: %s)', count( $colouring ), implode( ',', $colouring ) ), $failures, $passes );
bhp_vp_assert( count( $chapter ) >= 1, sprintf( '§2.2 at least one non-colouring product to regression-check (found %d)', count( $chapter ) ), $failures, $passes );

foreach ( $colouring as $pid ) {
	$sku = wc_get_product( $pid ) ? wc_get_product( $pid )->get_sku() : '';
	bhp_vp_assert( '9798996810840' === $sku, sprintf( '§2.3 colouring id %d carries the canonical SKU (got "%s")', $pid, $sku ), $failures, $passes );
}

/* ─────────────────────────────────────────────────────────────────────────
 * §3 · THE FIX. The colouring PDP renders the colouring line.
 * ───────────────────────────────────────────────────────────────────────── */
echo "\n§3 The colouring PDP\n";
foreach ( $colouring as $pid ) {
	$html = bhp_vp_render( $pid );
	$hook = bhp_vp_hook_text( $html );
	bhp_vp_assert( $hook === $COLOURING_HOOK, sprintf( '§3.1 id %d renders the colouring line (got "%s")', $pid, $hook ), $failures, $passes );
	bhp_vp_assert( false === stripos( $hook, 'chapter' ), sprintf( '§3.2 id %d says nothing about chapters', $pid ), $failures, $passes );
	bhp_vp_assert( false === stripos( $hook, 'science' ) && false === stripos( $hook, 'history' ), sprintf( '§3.3 id %d claims no science/history', $pid ), $failures, $passes );
	// ⭐ The age line is deliberately UNCHANGED and must still render.
	bhp_vp_assert( false !== strpos( $html, 'bhp-product-value-prop__age' ), sprintf( '§3.4 id %d still renders the age line', $pid ), $failures, $passes );
	bhp_vp_assert( false === stripos( $html, 'Lexile' ), sprintf( '§3.5 id %d emits no Lexile claim', $pid ), $failures, $passes );
}

/* ─────────────────────────────────────────────────────────────────────────
 * §4 · THE REGRESSION HALF. Every chapter PDP is byte-for-byte unchanged.
 *      ⛔ This is the assertion that proves the fix is a gate and not a
 *         rewrite. A "fix" that changed all six chapter pages would pass §3.
 * ───────────────────────────────────────────────────────────────────────── */
echo "\n§4 Every non-colouring PDP is unchanged\n";
$chapter_ok = 0;
foreach ( $chapter as $pid ) {
	$hook = bhp_vp_hook_text( bhp_vp_render( $pid ) );
	if ( $hook === $CHAPTER_HOOK ) {
		$chapter_ok++;
		continue;
	}
	bhp_vp_assert( false, sprintf( '§4.1 id %d kept the chapter-book line (got "%s")', $pid, $hook ), $failures, $passes );
}
bhp_vp_assert( $chapter_ok === count( $chapter ), sprintf( '§4.1 all %d non-colouring PDPs kept the chapter-book line verbatim', count( $chapter ) ), $failures, $passes );

/* ─────────────────────────────────────────────────────────────────────────
 * §5 · THE DEGRADE PATH. With the resolver returning nothing, the colouring
 *      page falls back to exactly 1.19.345's output rather than to an empty
 *      line. ⛔ A gate that fails OPEN into a blank <p> would be a worse
 *      defect than the one being fixed, and §3 cannot see that.
 * ───────────────────────────────────────────────────────────────────────── */
echo "\n§5 Degrade path (resolver unavailable)\n";
if ( $colouring ) {
	$probe = $colouring[0];
	add_filter( 'bhp_colouring_product_ids', 'bhp_vp_empty_ids', 999 );
	$degraded = bhp_vp_hook_text( bhp_vp_render( $probe ) );
	remove_filter( 'bhp_colouring_product_ids', 'bhp_vp_empty_ids', 999 );
	bhp_vp_assert( $degraded === $CHAPTER_HOOK, sprintf( '§5.1 id %d degrades to the 1.19.345 line, never to a blank <p> (got "%s")', $probe, $degraded ), $failures, $passes );
	// ⭐ And the filter really was lifted — otherwise §5.1 proves nothing.
	bhp_vp_assert( bhp_vp_hook_text( bhp_vp_render( $probe ) ) === $COLOURING_HOOK, '§5.2 filter lifted cleanly; the fix is live again', $failures, $passes );
} else {
	bhp_vp_assert( false, '§5.1 SKIPPED — no colouring product on this environment', $failures, $passes );
}

function bhp_vp_empty_ids( $ids ) {
	return array();
}

/* ───────────────────────────────────────────────────────────────────────── */
echo "\n";
if ( $failures ) {
	printf( "RESULT: %d passed, %d FAILED\n", $passes, count( $failures ) );
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
printf( "RESULT: %d passed, 0 failed\n", $passes );
exit( 0 );
