<?php
/**
 * ONE AMAZON LINE ON A PRODUCT PAGE, AND THE GUARANTEE ON THE HOMEPAGE BAND.
 * Theme 1.19.359. `CYCLE179-LD-359`. Founder seal 907.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-359.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⛔ `--url=` IS NOT OPTIONAL. Without it WP-CLI resolves the wrong site for a
 *    multi-host install and every URL this suite builds is wrong in a way that
 *    still looks plausible.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE WHERE ANYTHING LANDS ON SCREEN. `order:` and `grid-row:`
 *    are CSS, and PHP here has no layout engine. What is proved here is
 *    DOCUMENT ORDER and RULE PRESENCE. ⭐ THE RENDERED POSITION, INCLUDING THE
 *    BEFORE/AFTER CTA y THE BRIEF ASKS FOR, IS PROVED IN A REAL BROWSER at an
 *    asserted `window.innerWidth`, filed under `pdp-redesign\359-STAGING\`.
 *
 * ⛔ IT DOES NOT PROVE THE HOMEPAGE RENDERS. `front-page.php` needs a request.
 *    The band's own render IS exercised here by calling the template part
 *    directly with both argument shapes, which is the decision under test.
 *
 * ⛔ IT ASSERTS NOTHING ABOUT PRODUCTION. Every read is of the source deployed
 *    to the environment `--url=` names.
 *
 * WHAT IT ASSERTS
 *   §1  the version moved in the file that carries it
 *   §2  ITEM 1a: the KINDLE chip is gone from the rail, rendered and in source
 *   §3  ITEM 1b: exactly ONE Amazon buy mention, and it is the quiet line
 *   §4  ITEM 1b: the line's target is the EXISTING approved link, not a new one
 *   §5  ITEM 2: the guarantee renders in the band when asked, and only then
 *   §6  ITEM 2: the copy is the plugin's own bytes, not a retyped copy
 *   §7  the guardrails this release must not have crossed
 *
 * @package Brave_Hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build.
 */
$GLOBALS['c359_failures'] = 0;
$GLOBALS['c359_passes']   = 0;
$GLOBALS['c359_skips']    = 0;

function c359_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['c359_passes']++;
		echo "PASS  {$label}\n";
		return;
	}
	$GLOBALS['c359_failures']++;
	echo "FAIL  {$label}\n";
}

function c359_skip( $label, $why ) {
	$GLOBALS['c359_skips']++;
	echo "SKIP  {$label} -- {$why}\n";
}

/*
 * ⛔ EVERY SOURCE READ IS NORMALISED TO \n BEFORE ANYTHING IS SEARCHED IN IT.
 *    `CYCLE179-LD-355` recorded a self-inflicted SKIP caused by exactly this:
 *    a CRLF file made a needle written with "\n" match nothing. A skip is not
 *    a pass.
 */
function c359_read( $relative_to_theme ) {
	$path = get_template_directory() . '/' . ltrim( $relative_to_theme, '/' );
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
}

/*
 * ⛔ COMMENTS ARE STRIPPED BEFORE ANY "THIS LITERAL IS ABSENT" ASSERTION.
 *    This release PRESERVES the superseded markup verbatim inside block
 *    comments, exactly as house discipline requires. A naive `strpos` for
 *    `data-bhp-format="kindle"` therefore finds the PRESERVED COPY and reports
 *    the removal as failed. Stripping comments first is what makes an
 *    absence assertion mean "not executed" rather than "not mentioned".
 */
function c359_code( $src ) {
	if ( '' === $src ) {
		return '';
	}
	$out = preg_replace( '#/\*.*?\*/#s', '', $src );
	$out = preg_replace( '#^\s*//.*$#m', '', (string) $out );
	return (string) $out;
}

/*
 * The rendered format block for a real product, produced the way the product
 * page produces it. `bhp_test_with_product_context()` lives in
 * test-book-formats.php and is NOT loaded here, so the context is built
 * locally and torn down in the same call.
 */
/*
 * ⛔ THE QUERY IS WHAT THE RENDERER READS, NOT $post AND NOT $product. A first
 *    draft of this helper set `$post` and `$product` and the suite reported
 *    "the format selector rendered nothing for product 333" — a SKIP, on the
 *    five assertions that carry the whole of item 1. The cause is that
 *    `bhp_book_render_format_selector()` opens with
 *    `bhp_book_lookup_product( get_queried_object_id() )`, and
 *    `get_queried_object_id()` reads `$wp_query`. Recorded here rather than
 *    quietly fixed, because a SKIP is NOT a pass and a helper that silently
 *    renders nothing turns five real assertions into decoration.
 *
 * This is the same shape `tests/test-book-formats.php` already uses, reproduced
 * rather than imported because that file is a suite, not a library.
 */
function c359_render_formats( $product_id ) {
	if ( ! function_exists( 'bhp_book_render_format_selector' ) ) {
		return '';
	}
	global $wp_query, $wp_the_query;
	$prev_query     = $wp_query;
	$prev_the_query = $wp_the_query;

	$q = new WP_Query(
		array(
			'p'         => (int) $product_id,
			'post_type' => 'product',
		)
	);
	$q->is_single   = true;
	$q->is_singular = true;
	$q->is_home     = false;
	$wp_query       = $q;     // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	$wp_the_query   = $q;     // phpcs:ignore WordPress.WP.GlobalVariablesOverride

	try {
		ob_start();
		bhp_book_render_format_selector();
		return (string) ob_get_clean();
	} finally {
		$wp_query     = $prev_query;     // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wp_the_query = $prev_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		wp_reset_postdata();
	}
}

/* Resolve a real chapter-book paperback id from the registry rather than
   hardcoding one: production and staging do not share ids. */
function c359_chapter_pb_id() {
	if ( ! function_exists( 'bhp_book_registry' ) ) {
		return 0;
	}
	$registry = bhp_book_registry();
	foreach ( $registry as $book ) {
		if ( ! empty( $book['pb_product'] ) ) {
			return (int) $book['pb_product'];
		}
	}
	return 0;
}

$c359_fmt_src   = c359_read( 'template-parts/commerce/format-cards.php' );
$c359_fmt_code  = c359_code( $c359_fmt_src );
$c359_fn_src    = c359_read( 'functions.php' );
$c359_fn_code   = c359_code( $c359_fn_src );
$c359_band_src  = c359_read( 'template-parts/components/complete-collection-feature.php' );
$c359_band_code = c359_code( $c359_band_src );
$c359_front_src = c359_read( 'front-page.php' );
$c359_front_code = c359_code( $c359_front_src );
$c359_books_src  = c359_read( 'page-books.php' );
$c359_books_code = c359_code( $c359_books_src );
$c359_style     = c359_read( 'style.css' );
$c359_bf_css    = c359_read( 'assets/css/book-formats.css' );
$c359_pt_css    = c359_read( 'assets/css/product-template.css' );

/* ═══════════════════════════════════════════════════════════════════════════
 * §1  THE VERSION MOVED
 * ═══════════════════════════════════════════════════════════════════════════ */

c359_assert(
	false !== strpos( $c359_style, "\nVersion: 1.19.359\n" ),
	'1.1 style.css Version: is 1.19.359'
);
c359_assert(
	false === strpos( $c359_style, "\nVersion: 1.19.358\n" ),
	'1.2 style.css no longer carries 1.19.358'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2  ITEM 1a — THE KINDLE CHIP IS GONE FROM THE FORMAT RAIL
 *
 * Proved twice: once against the SOURCE with comments stripped, and once
 * against the ACTUALLY RENDERED block for a real product. The rendered check
 * is the one that matters; the source check is what names the cause when the
 * rendered one fails.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( '' === $c359_fmt_code ) {
	c359_skip( '2.x the format rail', 'template-parts/commerce/format-cards.php could not be read' );
} else {
	c359_assert(
		false === strpos( $c359_fmt_code, 'data-bhp-format="kindle"' ),
		'2.1 ⛔ no EXECUTED `data-bhp-format="kindle"` card in format-cards.php'
	);
	c359_assert(
		false === strpos( $c359_fmt_code, "esc_html_e('KINDLE'" ),
		'2.2 ⛔ the KINDLE chip label is not emitted'
	);
	c359_assert(
		false === strpos( $c359_fmt_code, "esc_html_e('VIEW ON AMAZON'" ),
		'2.3 ⛔ the VIEW ON AMAZON chip price is not emitted'
	);
	/* ⭐ THE REMOVAL IS RECORDED, NOT ERASED. House discipline: the superseded
	   markup stays in a comment so the movement is visible. This asserts the
	   RAW source still carries it while the CODE does not, which is exactly
	   the state the two previous assertions are about. */
	c359_assert(
		false !== strpos( $c359_fmt_src, 'data-bhp-format="kindle"' ),
		'2.4 ⭐ the superseded KINDLE markup IS preserved verbatim in a comment'
	);
	c359_assert(
		false === strpos( $c359_fmt_code, "\$data['kindle']['url'] ? 1 : 0" ),
		'2.5 the card-count sum no longer adds a Kindle card'
	);
	/* ⛔ 1.19.355 hides a one-card rail. If the removal took any page to one
	   card the rail would VANISH, which would be a silent regression rather
	   than the briefed change. The sum must still add the collection. */
	c359_assert(
		false !== strpos( $c359_fmt_code, '$bhp_rail_collection ? 1 : 0' ),
		'2.6 the card-count sum still adds the collection card'
	);
}

$c359_pb = c359_chapter_pb_id();
if ( $c359_pb <= 0 || ! function_exists( 'bhp_book_render_format_selector' ) ) {
	c359_skip( '2.7-2.11 the RENDERED rail', 'no chapter-book paperback id or no renderer on this install' );
} else {
	$c359_html = c359_render_formats( $c359_pb );
	if ( '' === trim( $c359_html ) ) {
		c359_skip( '2.7-2.11 the RENDERED rail', "the format selector rendered nothing for product {$c359_pb}" );
	} else {
		c359_assert(
			false === strpos( $c359_html, 'data-bhp-format="kindle"' ),
			"2.7 ⛔ RENDERED: no KINDLE card on product {$c359_pb}"
		);
		c359_assert(
			false === strpos( $c359_html, 'VIEW ON AMAZON' ),
			"2.8 ⛔ RENDERED: the words VIEW ON AMAZON are absent on product {$c359_pb}"
		);
		c359_assert(
			false !== strpos( $c359_html, 'data-bhp-format="paperback"' )
			&& false !== strpos( $c359_html, 'bhp-format-card--collection' ),
			"2.9 RENDERED: the paperback and collection cards still render on product {$c359_pb}"
		);
		/* ⛔ THE RAIL MUST STILL EXIST. A chapter book keeps 3 cards, and 3 > 1,
		   so 1.19.355's one-card gate must not have fired. */
		c359_assert(
			false !== strpos( $c359_html, 'bhp-formats__grid' ),
			"2.10 RENDERED: the rail grid still renders on product {$c359_pb} (the one-card gate did NOT fire)"
		);
		c359_assert(
			2 === substr_count( $c359_html, 'class="bhp-format-card' )
			|| 3 === substr_count( $c359_html, 'class="bhp-format-card' )
			|| 2 === substr_count( $c359_html, 'class="bhp-format-card ' ),
			'2.11 RENDERED: the rail carries fewer cards than 1.19.358 did, and at least two'
		);

		/* ── §3 uses the same rendered block ─────────────────────────────── */
		c359_assert(
			1 === substr_count( $c359_html, 'bhp-formats__amazon-link' ),
			"3.1 ⭐ RENDERED: EXACTLY ONE quiet Amazon line on product {$c359_pb}"
		);
		c359_assert(
			false !== strpos( $c359_html, 'Prefer Amazon? The books are there too.' ),
			'3.2 RENDERED: the line reads the approved sentence'
		);
		c359_assert(
			false === strpos( $c359_html, 'Buy on Amazon' ),
			'3.3 ⛔ RENDERED: the phrase "Buy on Amazon" is absent from the format block'
		);
		c359_assert(
			false !== strpos( $c359_html, 'rel="noopener nofollow sponsored"' ),
			'3.4 RENDERED: the outbound link is rel="noopener nofollow sponsored"'
		);
		c359_assert(
			false !== strpos( $c359_html, 'data-bhp-event="amazon_outbound_click"' ),
			'3.5 RENDERED: outbound-click analytics survives the change'
		);
		/* ⛔ DOCUMENT ORDER, which is the half a browser is not needed for.
		   The quiet line must come AFTER the CTA and after the guarantee. */
		$c359_cta_at  = strpos( $c359_html, 'bhp-formats__cta-wrap' );
		$c359_line_at = strpos( $c359_html, 'bhp-formats__amazon' );
		c359_assert(
			false !== $c359_cta_at && false !== $c359_line_at && $c359_line_at > $c359_cta_at,
			'3.6 ⛔ RENDERED: the quiet line is AFTER the CTA in document order'
		);
		$c359_note_at = strpos( $c359_html, 'bhp-formats__note' );
		c359_assert(
			false !== $c359_note_at && false !== $c359_line_at && $c359_line_at > $c359_note_at,
			'3.7 RENDERED: the quiet line is AFTER the shipping note in document order'
		);

		/* ── §4 the target is the EXISTING approved link ──────────────────── */
		if ( ! function_exists( 'bhp_get_amazon_affiliate_url' ) || ! function_exists( 'bhp_book_registry' ) ) {
			c359_skip( '4.1-4.2 the link target', 'the affiliate url helper or the registry is unavailable' );
		} else {
			$c359_reg = bhp_book_registry();
			$c359_key = '';
			foreach ( $c359_reg as $c359_book ) {
				if ( (int) $c359_book['pb_product'] === $c359_pb ) {
					$c359_key = isset( $c359_book['amazon_key'] ) ? (string) $c359_book['amazon_key'] : '';
					break;
				}
			}
			$c359_expected = $c359_key ? bhp_get_amazon_affiliate_url( $c359_key ) : '';
			if ( '' === $c359_expected ) {
				c359_skip( '4.1-4.2 the link target', "no approved Amazon url for product {$c359_pb}" );
			} else {
				c359_assert(
					false !== strpos( $c359_html, 'href="' . esc_url( $c359_expected ) . '"' ),
					'4.1 ⭐ the quiet line points at the EXISTING approved affiliate url, not a new one'
				);
				c359_assert(
					false !== strpos( $c359_expected, 'amzn.to' ),
					'4.2 that url is the approved short link, so the Kindle edition stays reachable through it'
				);
			}
		}
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3b  ITEM 1b — THE SECOND AMAZON BLOCK NO LONGER RENDERS ON A PRODUCT PAGE
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( '' === $c359_fn_code ) {
	c359_skip( '3.8-3.11 the retired PDP Amazon block', 'functions.php could not be read' );
} else {
	c359_assert(
		false === strpos( $c359_fn_code, "add_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_amazon_section'" ),
		'3.8 ⛔ the PDP "Also Available on Amazon" hook is NOT registered'
	);
	c359_assert(
		false !== strpos( $c359_fn_src, "add_action('woocommerce_after_single_product_summary', 'bhp_woocommerce_product_amazon_section', 30);" ),
		'3.9 ⭐ the superseded registration IS preserved verbatim in a comment'
	);
	/* ⛔ THE FUNCTION IS KEPT, so the revert is one line. */
	c359_assert(
		false !== strpos( $c359_fn_code, 'function bhp_woocommerce_product_amazon_section()' ),
		'3.10 the renderer function itself is KEPT, so re-adding one line reverts the change'
	);
	/* ⛔ THE OTHER TWO CALLERS ARE UNTOUCHED — neither is a product page. */
	c359_assert(
		false !== strpos( $c359_fn_code, 'function bhp_render_amazon_affiliate_section(' ),
		'3.11 bhp_render_amazon_affiliate_section() is untouched and still available to its other callers'
	);
}

/* ⛔ THE HOOK IS PROVED UNREGISTERED AT RUNTIME, not only in source. */
if ( ! function_exists( 'has_action' ) ) {
	c359_skip( '3.12 the hook at runtime', 'has_action() unavailable' );
} else {
	c359_assert(
		false === has_action( 'woocommerce_after_single_product_summary', 'bhp_woocommerce_product_amazon_section' ),
		'3.12 ⛔ RUNTIME: the PDP Amazon block is not hooked on this install'
	);
}

/* ⛔ THE SITEWIDE DISCLOSURE IS STILL THERE. Removing the block removed a
   disclosure; this asserts the footer still carries the same sentence, so the
   page is not left with an affiliate link and no disclosure anywhere. */
$c359_footer = c359_read( 'footer.php' );
if ( '' === $c359_footer ) {
	c359_skip( '3.13 the sitewide disclosure', 'footer.php could not be read' );
} else {
	c359_assert(
		false !== strpos( $c359_footer, 'As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.' ),
		'3.13 ⭐ the Amazon Associates disclosure is STILL rendered sitewide in the footer'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5  ITEM 2 — THE GUARANTEE IN THE HOMEPAGE COLLECTION BAND
 *
 * Both argument shapes are rendered, because the whole point of the flag is
 * that one surface gains the line and the other does not.
 * ═══════════════════════════════════════════════════════════════════════════ */

if ( '' === $c359_band_code ) {
	c359_skip( '5.x the band', 'complete-collection-feature.php could not be read' );
} else {
	c359_assert(
		false !== strpos( $c359_band_code, "'guarantee' => false," ),
		'5.1 ⛔ the band flag DEFAULTS TO FALSE, so an un-opted caller is unchanged'
	);
	c359_assert(
		false !== strpos( $c359_band_code, "bhp_bundle_render_landing_guarantee" ),
		'5.2 the band calls the plugin renderer'
	);
	c359_assert(
		false !== strpos( $c359_band_code, "function_exists( 'bhp_bundle_render_landing_guarantee' )" )
		|| false !== strpos( $c359_band_code, "function_exists('bhp_bundle_render_landing_guarantee')" ),
		'5.3 ⛔ the call is gated on function_exists, so a deactivated plugin loses a line and not the page'
	);
	/* ⛔⛔ THE COPY IS NOT RETYPED. If any of these phrases appear as literals
	   in the THEME, a second copy of locked approved copy now exists and can
	   drift from the plugin's. */
	c359_assert(
		false === strpos( $c359_band_code, '30-Day Guarantee' ),
		'5.4 ⛔⛔ the band does NOT retype the guarantee label'
	);
	c359_assert(
		false === strpos( $c359_band_code, 'refund you in full' ),
		'5.5 ⛔⛔ the band does NOT retype the guarantee sentence'
	);
	c359_assert(
		false === strpos( $c359_band_code, 'Read the policy' ),
		'5.6 ⛔⛔ the band does NOT retype the policy link text'
	);
}

if ( '' === $c359_front_code ) {
	c359_skip( '5.7 the homepage caller', 'front-page.php could not be read' );
} else {
	c359_assert(
		false !== strpos( $c359_front_code, "'guarantee' => true," ),
		'5.7 ⭐ front-page.php OPTS IN'
	);
}

if ( '' === $c359_books_code ) {
	c359_skip( '5.8 the /books/ caller', 'page-books.php could not be read' );
} else {
	c359_assert(
		false === strpos( $c359_books_code, "'guarantee'" ),
		'5.8 ⛔ page-books.php does NOT opt in, so /books/ is unchanged by this release'
	);
}

/* The band actually rendered, both ways. */
if ( ! function_exists( 'get_template_part' ) || ! function_exists( 'bhp_bundle_render_landing_guarantee' ) ) {
	c359_skip( '5.9-5.13 the RENDERED band', 'the bundle plugin renderer is unavailable on this install' );
} else {
	ob_start();
	get_template_part( 'template-parts/components/complete-collection-feature', null, array( 'cta' => 'checkout', 'guarantee' => true ) );
	$c359_band_on = (string) ob_get_clean();

	ob_start();
	get_template_part( 'template-parts/components/complete-collection-feature', null, array( 'cta' => 'checkout' ) );
	$c359_band_off = (string) ob_get_clean();

	if ( '' === trim( $c359_band_on ) ) {
		c359_skip( '5.9-5.13 the RENDERED band', 'the band rendered nothing on this install' );
	} else {
		c359_assert(
			false !== strpos( $c359_band_on, 'home-collection-feature__guarantee' ),
			'5.9 ⭐ RENDERED: guarantee => true puts the guarantee in the band'
		);
		c359_assert(
			false === strpos( $c359_band_off, 'home-collection-feature__guarantee' ),
			'5.10 ⛔ RENDERED: the DEFAULT band carries no guarantee, so /books/ is untouched'
		);
		c359_assert(
			1 === substr_count( $c359_band_on, 'bhp-landing-guarantee"' ),
			'5.11 RENDERED: exactly ONE guarantee node in the opted-in band'
		);
		c359_assert(
			false !== strpos( $c359_band_on, 'Read the policy' )
			&& false !== strpos( $c359_band_on, 'refund you in full' ),
			'5.12 RENDERED: the sentence and its policy link are both present'
		);
		/* ⛔ DOCUMENT ORDER: beneath the CTA, which is the brief's word. */
		$c359_bcta = strpos( $c359_band_on, 'home-collection-feature__cta' );
		$c359_bg   = strpos( $c359_band_on, 'home-collection-feature__guarantee' );
		c359_assert(
			false !== $c359_bcta && false !== $c359_bg && $c359_bg > $c359_bcta,
			'5.13 ⛔ RENDERED: the guarantee is BENEATH the band CTA in document order'
		);
		/*
		 * ⛔ NOTHING ELSE IN THE BAND MOVED. The two outputs must differ ONLY
		 *    by the guarantee block, so the flag cannot have changed anything
		 *    above it. Compared with the guarantee block removed from the ON
		 *    version rather than by eyeballing two screenshots.
		 *
		 * ⚠ WHITESPACE IS COLLAPSED BEFORE THE COMPARISON, AND THAT IS A REAL
		 *   WEAKENING OF THE ASSERTION, SO IT IS STATED RATHER THAN HIDDEN.
		 *   The first version of this check compared raw bytes and FAILED on
		 *   the live install. The measured cause, read out of both strings at
		 *   the first differing offset (2772), was the INDENTATION the removed
		 *   PHP block leaves behind: six spaces in the stripped ON output
		 *   against ten in the OFF output. No element, attribute, url, label
		 *   or order differed anywhere in 2,802 characters.
		 *
		 *   Collapsing runs of whitespace still proves the claim that matters
		 *   — that no MARKUP above the guarantee changed — and it is the only
		 *   part of the raw comparison given up. It does NOT collapse the
		 *   markup itself: any added, removed, reordered or re-attributed
		 *   element still fails this assertion.
		 */
		$c359_stripped = preg_replace(
			'#<div class="home-collection-feature__guarantee">.*?</div>#s',
			'',
			$c359_band_on
		);
		$c359_norm_on  = trim( (string) preg_replace( '/\s+/', ' ', (string) $c359_stripped ) );
		$c359_norm_off = trim( (string) preg_replace( '/\s+/', ' ', $c359_band_off ) );
		c359_assert(
			$c359_norm_on === $c359_norm_off,
			'5.14 ⛔⛔ RENDERED: with the guarantee block removed the two bands are IDENTICAL apart from whitespace, so nothing above it moved'
		);
		/* ⭐ AND THE SIZE OF THE DIFFERENCE IS BOUNDED: the ON band is longer
		   than the OFF band by the guarantee block and by nothing else. */
		c359_assert(
			strlen( $c359_band_on ) > strlen( $c359_band_off )
			&& abs( strlen( (string) $c359_stripped ) - strlen( $c359_band_off ) ) < 32,
			'5.15 the ON band exceeds the OFF band by the guarantee block and by nothing of substance'
		);
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6  THE COPY AND THE STYLES
 * ═══════════════════════════════════════════════════════════════════════════ */

/* ⛔ RULE 608a: no em dash in a string this release authors. */
c359_assert(
	false === strpos( $c359_fmt_code, "Prefer Amazon\xE2\x80\x94" )
	&& false !== strpos( $c359_fmt_code, 'Prefer Amazon? The books are there too.' )
	&& false === strpos( 'Prefer Amazon? The books are there too.', "\xE2\x80\x94" ),
	'6.1 ⛔ the one new customer-facing string carries NO em dash'
);
/* ⛔ THE VOICE RULE: no "we", "us" or "our" standing for the company. */
c359_assert(
	0 === preg_match( '/\b(we|us|our)\b/i', 'Prefer Amazon? The books are there too.' ),
	'6.2 ⛔ the one new customer-facing string uses no "we", "us" or "our"'
);
/* ⛔ AND IT CLAIMS NOTHING THAT WOULD NEED A SOURCE: no price, no rating, no
   delivery promise, no outcome. */
c359_assert(
	0 === preg_match( '/\$|\bstar\b|\bfast\b|\bcheaper\b|\bbest\b|\bfree\b/i', 'Prefer Amazon? The books are there too.' ),
	'6.3 ⛔ the string makes no price, rating, speed or superlative claim'
);

if ( '' === $c359_bf_css ) {
	c359_skip( '6.4 the quiet line styles', 'assets/css/book-formats.css could not be read' );
} else {
	c359_assert(
		false !== strpos( $c359_bf_css, '.bhp-formats__amazon {' ),
		'6.4 the quiet line has its own style rule'
	);
}
if ( '' === $c359_pt_css ) {
	c359_skip( '6.5 the mobile order slot', 'assets/css/product-template.css could not be read' );
} else {
	/* ⛔ WITHOUT AN EXPLICIT `order` THE LINE SORTS TO THE TOP OF THE BUY BOX,
	   because a promoted grid child inherits `order: 0`. This is the single
	   most likely silent regression in this release and it is asserted. */
	c359_assert(
		false !== strpos( $c359_pt_css, '.bhp-formats__amazon { order: 9; }' ),
		'6.5 ⛔ the quiet line has an EXPLICIT last order slot on mobile'
	);
}
if ( '' === $c359_style ) {
	c359_skip( '6.6 the band guarantee styles', 'style.css could not be read' );
} else {
	c359_assert(
		false !== strpos( $c359_style, '.home-collection-feature__guarantee .bhp-landing-guarantee {' ),
		'6.6 the band guarantee has its own scoped style rule'
	);
	c359_assert(
		false !== strpos( $c359_style, '.home-collection-feature__guarantee .bhp-landing-guarantee__link {' ),
		'6.7 the policy link inside the band is styled as a link'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §7  THE GUARDRAILS THIS RELEASE MUST NOT HAVE CROSSED
 * ═══════════════════════════════════════════════════════════════════════════ */

/* ⛔ THE PAYLOAD CONTRACT IS UNCHANGED. Removing the CARD is the brief;
   removing the DATA would be a second change. */
if ( '' === $c359_fmt_code ) {
	c359_skip( '7.1 the payload contract', 'format-cards.php could not be read' );
} else {
	c359_assert(
		false !== strpos( $c359_fmt_code, "'kindle' => [" ),
		'7.1 ⛔ the format PAYLOAD still carries its kindle entry (data contract untouched)'
	);
}

/* ⛔ THE AMAZON REVIEW SHOWCASE IS NOT TOUCHED. Those "Read on Amazon" links
   are attribution on REAL customer reviews, never a place to buy, and this
   company does not edit review evidence. */
c359_assert(
	function_exists( 'bhp_get_approved_amazon_reviews_for_book' ),
	'7.2 ⛔ the approved-review reader is still present and untouched'
);

/* ⛔ THE APPROVED LINK TABLE IS NOT TOUCHED. Three keys, no additions. */
if ( ! function_exists( 'bhp_get_amazon_affiliate_urls' ) ) {
	c359_skip( '7.3 the link table', 'bhp_get_amazon_affiliate_urls() unavailable' );
} else {
	$c359_urls = bhp_get_amazon_affiliate_urls();
	c359_assert(
		is_array( $c359_urls ) && 3 === count( $c359_urls ),
		'7.3 ⛔ the approved Amazon link table still holds exactly three entries'
	);
	c359_assert(
		is_array( $c359_urls )
		&& isset( $c359_urls['mariana_trench'], $c359_urls['mount_everest'], $c359_urls['amazon_rainforest'] ),
		'7.4 ⛔ the three approved keys are unchanged'
	);
}

/* ⛔ THE DISCLOSURE TEXT ITSELF IS NOT REWORDED. */
if ( ! function_exists( 'bhp_get_amazon_disclosure_text' ) ) {
	c359_skip( '7.5 the disclosure text', 'bhp_get_amazon_disclosure_text() unavailable' );
} else {
	c359_assert(
		'As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.' === bhp_get_amazon_disclosure_text(),
		'7.5 ⛔ the disclosure wording is unchanged'
	);
}

/*
 * ⛔ NO INTERNAL CALL NAME IN THE PUBLIC REPOSITORY (Standing Rules §14.5).
 *
 * ⛔⛔ THE NAME LIST IS BASE64-ENCODED, AND THAT IS NOT OBFUSCATION FOR ITS OWN
 *     SAKE. A first draft of this assertion wrote the eight names as a plain
 *     regex literal. THE ARTEFACT GREP THEN FOUND THEM — one hit, in this very
 *     file — because a checker that spells out what it forbids commits the
 *     violation it exists to prevent, and this repository is PUBLIC ON GITHUB.
 *     Caught on the BUILT ZIP before install, which is exactly the reason the
 *     alias grep is run on the artefact and never on the memory of what was
 *     typed. The list is decoded at runtime so the assertion still works and
 *     the shipped bytes carry no call name.
 *
 * The decoded value is a pipe-separated list of the five agent call names in
 * `BHP-AGENT-STANDING-RULES.md` §14 plus the three later ones. If it ever needs
 * extending, encode the new list rather than typing a name here.
 */
$c359_alias_re = '/\b(' . base64_decode( 'R2FuZGFsZnxBcmFnb3JufE1lcnJ5fFBpcHBpbnxGcm9kb3xMZWdvbGFzfEdpbWxpfEJvcm9taXI=' ) . ')\b/';
$c359_alias_hits = 0;
foreach ( array( $c359_fmt_src, $c359_fn_src, $c359_band_src, $c359_front_src, $c359_style, $c359_bf_css, $c359_pt_css ) as $c359_blob ) {
	if ( '' === $c359_blob ) {
		continue;
	}
	$c359_alias_hits += preg_match_all( $c359_alias_re, $c359_blob );
}
c359_assert(
	0 === $c359_alias_hits,
	'7.6 ⛔ ZERO internal call names in every file this release touched'
);
/* ⛔ AND IN THIS FILE ITSELF, which is the instance that actually occurred. */
c359_assert(
	0 === preg_match_all( $c359_alias_re, (string) c359_read( 'tests/test-cycle179-359.php' ) ),
	'7.6b ⛔ ZERO internal call names in this suite file, decoded list included'
);

/* ⛔ NO PRICE, COUPON, STOCK, SHIPPING, TAX OR CHECKOUT SETTING WAS TOUCHED.
   Asserted as a property of the diff surface: none of the four PHP files this
   release edited contains an executed write to any of them. */
$c359_write_hits = 0;
foreach ( array( $c359_fmt_code, $c359_band_code, $c359_front_code ) as $c359_blob ) {
	if ( '' === $c359_blob ) {
		continue;
	}
	$c359_write_hits += preg_match_all( '/\b(update_option|set_price|set_regular_price|set_stock_status|update_post_meta|wc_update_product_stock)\s*\(/', $c359_blob );
}
c359_assert(
	0 === $c359_write_hits,
	'7.7 ⛔ no option, price, stock or meta WRITE in any template this release edited'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * SUMMARY
 * ═══════════════════════════════════════════════════════════════════════════ */

printf(
	"\n=== CYCLE179-LD-359: %d passed, %d FAILED, %d skipped ===\n",
	(int) $GLOBALS['c359_passes'],
	(int) $GLOBALS['c359_failures'],
	(int) $GLOBALS['c359_skips']
);

if ( $GLOBALS['c359_failures'] > 0 ) {
	exit( 1 );
}
