<?php
/**
 * Brave Hearts — EVERY COLLECTION CTA MATCHES THE NAV-BAR BUTTON (theme 1.19.197).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-collection-cta-nav-match.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (RELAYED through the Chief of Staff and
 * NOT witnessed by the agent that wrote this file):
 *
 *   "All the CTA 'Get the Collections' need to match the nav bar button"
 *
 * Four claims, each asserted separately because each can break alone:
 *
 *   §1  THE TOKEN     — the shared class exists and the renderer appends it to
 *                       BOTH the form control and the fail-closed anchor. If
 *                       only one branch carries it, a site with the bundle
 *                       plugin off silently loses the treatment.
 *   §2  THE SURFACES  — every collection CTA on every live surface actually
 *                       carries the token in the RENDERED document.
 *   §3  THE REFERENCE — the sitewide header's own two controls are left exactly
 *                       as 1.19.196 made them: plain anchors, no token. They are
 *                       what everything else is matched TO. Restyling the
 *                       reference would make the comparison meaningless.
 *   §4  THE CSS       — the rule reads the SAME custom properties the nav
 *                       button's palette reads, and sets NO geometry.
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED RATHER THAN GLOSSED: it asserts
 *    markup and stylesheet TEXT. It does NOT prove a pixel. Whether the
 *    cascade actually resolves to the nav button's computed colour on a real
 *    page is a browser question and was answered separately, by reading
 *    getComputedStyle() on staging at two viewports. Do not read a green run
 *    here as visual QA.
 *
 * ⛔ NOTHING IS WRITTEN. No product, price, coupon, stock level, shipping
 *    setting, cart or order is touched by any part of this file.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_ctam_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_ctam_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 30, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** A page's URL from the template it uses, or '' if no page uses it. */
function bhp_ctam_url_for_template( $template ) {
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => $template,
			'number'     => 1,
		)
	);
	return $pages ? get_permalink( $pages[0]->ID ) : '';
}

$token = defined( 'BHP_COLLECTION_CTA_CLASS' ) ? BHP_COLLECTION_CTA_CLASS : '';

echo "\n=== §1 — THE TOKEN: one shared class, applied on BOTH render branches ===\n";

bhp_ctam_assert( defined( 'BHP_COLLECTION_CTA_CLASS' ), '§1 BHP_COLLECTION_CTA_CLASS is defined', $failures );
bhp_ctam_assert( 'bhp-collection-cta__btn' === $token, '§1 the token is exactly `bhp-collection-cta__btn`', $failures );
bhp_ctam_assert( function_exists( 'bhp_collection_add_to_cart_cta' ), '§1 renderer bhp_collection_add_to_cart_cta() is loaded', $failures );

if ( function_exists( 'bhp_collection_add_to_cart_cta' ) && $token ) {

	$live = bhp_collection_add_to_cart_cta(
		array(
			'format' => 'hardcover',
			'label'  => 'Add the Complete Collection',
			'class'  => 'btn btn-primary',
		)
	);

	bhp_ctam_assert(
		false !== strpos( $live, 'class="btn btn-primary ' . $token . '"' ),
		'§1 form branch — the token is APPENDED to the caller class, not substituted for it',
		$failures
	);

	/*
	 * ⚠ THE FAIL-CLOSED BRANCH IS ASSERTED FROM SOURCE, AND THAT IS A REAL
	 *   LIMITATION, NAMED RATHER THAN HIDDEN.
	 *
	 *   `bhp_collection_cta_available()` has no filter, so the anchor branch of
	 *   `bhp_collection_add_to_cart_cta()` cannot be reached in-process without
	 *   deactivating the bundle plugin — which this suite will not do to a live
	 *   install. It matters anyway: if the token were applied to the form branch
	 *   only, a plugin-less site would silently lose the whole treatment on every
	 *   collection CTA at once, and no rendered-HTML test could ever see it.
	 *
	 *   So this reads the FUNCTION'S OWN SOURCE via Reflection — the shipped
	 *   bytes of the shipped function, not a template file and not a comment
	 *   block — and asserts that both branches emit the same `$control_class`
	 *   variable rather than the caller's raw `$args['class']`. Source-scanning
	 *   is the weaker instrument and is used here only because the stronger one
	 *   is unavailable.
	 */
	$fn  = new ReflectionFunction( 'bhp_collection_add_to_cart_cta' );
	$src = implode( '', array_slice( file( $fn->getFileName() ), $fn->getStartLine() - 1, $fn->getEndLine() - $fn->getStartLine() + 1 ) );

	bhp_ctam_assert(
		1 === preg_match( '#\$control_class\s*=\s*trim\(\s*\$args\[.class.\]\s*\.\s*.\s*.\s*\.\s*BHP_COLLECTION_CTA_CLASS#', $src ),
		'§1 the token is built once, from the constant, by appending to the caller class',
		$failures
	);
	bhp_ctam_assert(
		2 === substr_count( $src, '$control_class' ) - 1,
		'§1 BOTH branches emit $control_class — the fail-closed anchor is not left on the old treatment',
		$failures
	);
	bhp_ctam_assert(
		false === strpos( $src, "esc_attr( \$args['class'] )" ),
		'§1 no branch still emits the raw caller class (which would silently skip the token)',
		$failures
	);
}

echo "\n=== §2 — THE SURFACES: every collection CTA carries the token, in the rendered HTML ===\n";

/*
 * `min` is the number of TOKEN-BEARING controls the page's own body must carry,
 * counted with the sitewide header removed so the header's two never inflate a
 * page's count. The numbers come from the templates:
 *
 *   educators / gift-buyers / parent-kit — 2 price-card panels (one hidden,
 *     both in the DOM) + the closing CTA + the sticky bar = 4.
 *   organizations — a PROGRAM page. Its closing CTA and footer bar are
 *     deliberately "Contact" / "Start a Partnership Conversation", not a
 *     purchase, so only its 2 price-card panels count. See
 *     test-collection-purchase-path.php for why that asymmetry is correct.
 *   homepage / books — the shared collection band, 1 each.
 *   shop — the grid's collection card, a plain link, 1.
 */
$surfaces = array();

$funnels = array(
	'educators'     => array( 'page-audience-educators.php', 4 ),
	'gift-buyers'   => array( 'page-audience-gift-buyers.php', 4 ),
	'organizations' => array( 'page-audience-organizations.php', 2 ),
	'parent-kit'    => array( 'page-reluctant-reader-adventure-kit.php', 4 ),
);
foreach ( $funnels as $key => $conf ) {
	$url = bhp_ctam_url_for_template( $conf[0] );
	if ( $url ) {
		$surfaces[ $key ] = array( 'url' => $url, 'min' => $conf[1] );
	} else {
		bhp_ctam_assert( false, "§2 {$key} — no published page uses {$conf[0]} (cannot assert this surface)", $failures );
	}
}

$surfaces['homepage'] = array( 'url' => home_url( '/' ), 'min' => 1 );

/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐⭐ RETIRED 1.19.285 — CARRIER ITEM 209 MERGED /books/ INTO /shop/.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, carrier item 209, 2026-08-21 (⚠️ RELAYED through
 * `chief-of-staff`, ⛔ NOT witnessed first-hand). /books/ now answers a 301
 * to /shop/ (`bhp_redirect_books_to_shop()`).
 *
 * ⛔ THIS ROW IS RETIRED RATHER THAN LEFT TO "PASS", AND THAT DISTINCTION IS
 *    THE WHOLE POINT. `wp_remote_get()` follows redirects, so leaving the row
 *    in would have fetched /books/, silently received the /shop/ document,
 *    found the item-206 collection card, and reported GREEN — measuring the
 *    SAME surface twice under two names while claiming to cover two. A suite
 *    that passes for the wrong reason is worse than one that fails.
 *
 * ⭐ NO COVERAGE IS LOST: the `shop` surface immediately below is that exact
 *    document, asserted under its real name.
 *
 * ⛔ GATED ON THE MERGE ACTUALLY BEING IN THE BUILD, not on a date or a
 *    version literal. Revert the redirect and this row comes straight back,
 *    which is what makes the retirement reversible instead of a deletion.
 *
 * The superseded row, verbatim:
 *
 *     $books_page = get_page_by_path( 'books' );
 *     if ( $books_page ) {
 *         $surfaces['books'] = array( 'url' => get_permalink( $books_page->ID ), 'min' => 1 );
 *     }
 */
if ( function_exists( 'bhp_redirect_books_to_shop' ) ) {
	echo "RETIRED: §2 `books` surface — merged into `shop` by carrier item 209 (1.19.285)\n";
} else {
	$books_page = get_page_by_path( 'books' );
	if ( $books_page ) {
		$surfaces['books'] = array( 'url' => get_permalink( $books_page->ID ), 'min' => 1 );
	}
}
if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) {
	$surfaces['shop'] = array( 'url' => get_permalink( wc_get_page_id( 'shop' ) ), 'min' => 1 );
}

/** The document minus <header class="site-header">…</header>. */
function bhp_ctam_body( $html ) {
	$start = strpos( $html, '<header class="site-header"' );
	if ( false === $start ) {
		return $html;
	}
	$end = strpos( $html, '</header>', $start );
	if ( false === $end ) {
		return $html;
	}
	return substr( $html, 0, $start ) . substr( $html, $end + 9 );
}

$documents = array();

foreach ( $surfaces as $key => $conf ) {
	$html = bhp_ctam_fetch( $conf['url'] );
	bhp_ctam_assert( '' !== $html, "§2 {$key} — page renders (HTTP 200, non-empty body)", $failures );
	if ( '' === $html ) {
		continue;
	}
	$documents[ $key ] = $html;
	$body  = bhp_ctam_body( $html );
	$count = substr_count( $body, $token );

	bhp_ctam_assert(
		$count >= $conf['min'],
		sprintf( '§2 %s — at least %d token-bearing collection CTAs in the page body (got %d)', $key, $conf['min'], $count ),
		$failures
	);
}

/*
 * THE STRAGGLER CHECK, and it is the assertion that would actually catch a
 * missed surface. A collection CTA that is still on the old treatment is one
 * whose class list contains a known CTA class but NOT the token. Rather than
 * maintain an inventory of every such class — which is the inventory this
 * release exists to stop maintaining — assert the one invariant that holds
 * everywhere: inside a `form.bhp-collection-cta`, every submit button carries
 * the token. That form wrapper is emitted by, and only by, the shared renderer.
 */
foreach ( $documents as $key => $html ) {
	$forms   = substr_count( $html, 'class="bhp-bundle-form bhp-collection-cta' );
	$matches = preg_match_all( '#<form[^>]*class="bhp-bundle-form bhp-collection-cta.*?</form>#s', $html, $m );
	$missing = 0;
	if ( $matches ) {
		foreach ( $m[0] as $frag ) {
			if ( false === strpos( $frag, $token ) ) {
				$missing++;
			}
		}
	}
	/*
	 * ⚠ `$forms` IS DELIBERATELY NOT REQUIRED TO BE > 0, AND THE FIRST VERSION OF
	 *   THIS FILE GOT THAT WRONG — recorded rather than quietly corrected.
	 *
	 *   It asserted `$forms > 0 && 0 === $missing` and duly reported FAIL on
	 *   /shop/ against a CORRECT build, because the Shop grid's collection card
	 *   is a plain link, not a rendered form. The build was right; the assertion
	 *   was. What this check is actually for is "no collection FORM is on the old
	 *   treatment", and a page with no forms satisfies that vacuously and
	 *   honestly. The "there is at least one token-bearing CTA here" claim is a
	 *   different one, and the per-surface `min` loop above already makes it.
	 */
	bhp_ctam_assert(
		0 === $missing,
		sprintf( '%s — no rendered collection form is missing the token (%d forms, %d missing)', "§2 {$key}", $forms, $missing ),
		$failures
	);
}

/*
 * The Shop grid's card is the one collection CTA that is NOT a rendered form —
 * it is a plain link into /complete-collection/, and it takes the token by hand
 * in inc/book-formats.php. Asserted explicitly so a lost `class` attribute there
 * cannot hide inside a vacuous pass above.
 */
if ( isset( $documents['shop'] ) ) {
	bhp_ctam_assert(
		false !== strpos( $documents['shop'], 'class="button ' . $token . '"' ),
		'§2 shop — the grid\'s collection card is a plain link and carries the token by hand',
		$failures
	);
}

echo "\n=== §3 — THE REFERENCE: the nav-bar button is left exactly as 1.19.196 made it ===\n";

/*
 * ⭐ THE REFERENCE DOES NOT CARRY THE TOKEN, AND THIS SECTION EXISTS TO ASSERT
 *    THAT ON PURPOSE.
 *
 * 1.19.196 reverted both header controls to plain anchors on Andrew's own
 * same-day reversal. They are what everything else is being matched TO, so this
 * release must leave them untouched — a styling pass that "helpfully" restyled
 * the reference would make the comparison meaningless and would collide with
 * the label-centring fix that shipped in the same release.
 *
 * Parity is instead structural: §4 asserts the token rule reads the same custom
 * properties the header's palette reads, so the two cannot diverge.
 */
$home = isset( $documents['homepage'] ) ? $documents['homepage'] : '';

bhp_ctam_assert(
	'' !== $home && false !== strpos( $home, '<a class="header-expedition-cta"' ),
	'§3 the desktop nav-bar CTA is still the 1.19.196 plain anchor — this release did not touch the reference',
	$failures
);
bhp_ctam_assert(
	'' !== $home && false !== strpos( $home, '<a class="site-nav__cta"' ),
	'§3 the mobile-dropdown nav CTA is still the 1.19.196 plain anchor',
	$failures
);
bhp_ctam_assert(
	'' !== $home && false === strpos( $home, 'class="header-expedition-cta ' . $token . '"' ),
	'§3 the reference control was NOT given the token (it is the thing being matched, not a follower)',
	$failures
);

echo "\n=== §4 — THE CSS: same variables as the nav palette, and NO geometry ===\n";

$css = @file_get_contents( get_template_directory() . '/style.css' );
bhp_ctam_assert( ! empty( $css ), '§4 style.css is readable', $failures );

if ( ! empty( $css ) ) {

	/* Isolate the token's own declaration block, so a stray match elsewhere in a
	   7,000-line stylesheet cannot make this pass. */
	$block = '';
	if ( preg_match( '#\.bhp-collection-cta__btn,\s*\.bhp-landing-cta\s*\{(.*?)\}#s', $css, $bm ) ) {
		$block = $bm[1];
	}
	bhp_ctam_assert( '' !== $block, '§4 the token rule exists and is a single shared block with .bhp-landing-cta', $failures );

	$must_read = array(
		'--cta-primary-bg'     => 'background',
		'--cta-primary-text'   => 'colour',
		'--cta-primary-border' => 'border colour',
		'--btn-radius'         => 'radius',
		'--btn-font'           => 'typeface',
		'--btn-tracking'       => 'tracking',
	);
	foreach ( $must_read as $var => $what ) {
		bhp_ctam_assert(
			false !== strpos( $block, $var ),
			"§4 the {$what} is read from var({$var}) — the same property the nav palette reads, so the two cannot drift",
			$failures
		);
	}
	bhp_ctam_assert(
		false !== strpos( $block, 'text-transform: uppercase' ),
		'§4 the case matches the nav button (uppercase)',
		$failures
	);

	/*
	 * ⛔ THE GEOMETRY GUARANTEE, AND IT IS THE ASSERTION MOST WORTH KEEPING.
	 *
	 * Andrew's instruction is that the CTAs MATCH the nav button, not that they
	 * BECOME it. A 340px homepage band CTA and a 13px sticky-bar chip are each
	 * correct at their own size. If a future pass adds font-size or padding
	 * here, every collection CTA on the site silently collapses to one size and
	 * the sticky bars stop fitting their bars. Failing loudly is cheaper.
	 */
	foreach ( array( 'font-size', 'padding', 'min-height', 'width', 'display' ) as $forbidden ) {
		bhp_ctam_assert(
			false === strpos( $block, $forbidden . ':' ),
			"§4 the token rule sets NO {$forbidden} — each surface keeps its own size",
			$failures
		);
	}

	bhp_ctam_assert(
		false !== strpos( $css, '.bhp-collection-cta__btn:focus-visible' ),
		'§4 a visible focus ring is defined for the token, matching the nav button',
		$failures
	);
}

echo "\n=== RESULT ===\n";
if ( $failures ) {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "ALL PASS\n";
exit( 0 );
