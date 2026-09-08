<?php
/**
 * Brave Hearts — THE SITEWIDE HEADER COLLECTION CTA (theme 1.19.196).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-header-collection-cta.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THIS SUITE WAS INVERTED ON 2026-08-05, AND THE INVERSION IS THE POINT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, current-turn ruling, verbatim:
 *
 *   "So the 'Get Complete Collection' CTA in the nav bar isnt centered -center
 *    it- bring the box up. Also when you add to cart from that CTA button there
 *    is a shifting of the words on the nav bar goes back and forth. Also when
 *    you click the Get the Complete Collection - the UX - says adding to cart -
 *    and its quite slow - It should already be rendered and cached- I want to
 *    make a big change- the nav bar get the complete collection should go to
 *    the collection page not the checkout. Then we have the already rendered
 *    and cached pages on checkout for both the paperback and hardcovers"
 *
 * ⛔ RELAYED through the Chief of Staff. NOT witnessed by the agent that wrote
 *    this file. Recorded with its speaker and channel rather than as an
 *    unattributed requirement.
 *
 * ⭐ THIS REVERSES ANDREW'S OWN SAME-DAY RULING ("Convert to hardcover
 *    purchase", item 8), which this suite previously existed to defend. The
 *    earlier version asserted, in 81 places, that the header POSTS
 *    `complete_hardcover_smart` and lands on /checkout/. Every one of those
 *    assertions was correct for the build it was written against, and every one
 *    of them is now wrong — because the requirement changed, NOT because the
 *    build regressed.
 *
 * ⛔ THE ASSERTIONS WERE THEREFORE INVERTED, NOT DELETED AND NOT LOOSENED.
 *    A suite that is quietly weakened when a requirement reverses stops being
 *    evidence. Each section below states what it used to assert and what it
 *    asserts now, so a future reader can tell a considered reversal from a
 *    silent regression.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * SEVEN CLAIMS, asserted separately because each can break alone
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   §1 LOADED       — the header renderer exists, and so does the SHARED
 *                     purchase renderer the Collection page still depends on.
 *   §2 THE LINK     — both header variants emit a plain anchor to
 *                     /complete-collection/, and emit NO purchase contract.
 *   §3 SITEWIDE     — real rendered pages carry exactly two header anchors and
 *                     zero header purchase forms.
 *   §4 NO CART      — ⛔ THE ONE THAT MATTERS MOST, and it is now STRICTLY
 *      MUTATION       STRONGER than it was. It used to assert "the header does
 *                     not mutate a cart ON /cart/ AND /checkout/", because a
 *                     context guard suppressed the form there. It now asserts
 *                     the header cannot mutate a cart ON ANY PAGE AT ALL,
 *                     including a mid-payment checkout and the order-received
 *                     endpoint, because it no longer posts anything anywhere.
 *   §5 THE PURCHASE — ⭐ THE REGRESSION GUARD ON THE REVERSAL ITSELF. The
 *      PATH SURVIVES  two-click purchase path was NOT walked back: the
 *                     Complete Collection page still carries its format toggle
 *                     and its add-and-checkout CTAs. Andrew's own sentence is
 *                     the requirement — "we have the already rendered and
 *                     cached pages on checkout for both the paperback and
 *                     hardcovers" — and it is only true if that page still
 *                     sells. A revert that also broke the destination would
 *                     satisfy every other section in this file.
 *   §6 ARIA-CURRENT — restored by the revert, and correct in both directions.
 *   §7 SAFETY       — nothing script-shaped, nothing unescaped.
 *
 * ⛔ NO ORDER IS EVER CREATED, AND — UNLIKE THE PREVIOUS VERSION — NO CART IS
 *    EVER BUILT. The old §5 built a real 3-hardcover cart to prove the header's
 *    offer. The header no longer makes an offer, so that cart is no longer this
 *    suite's business; the Collection page's own offer is asserted by
 *    `test-collection-purchase-path.php`, which owns it. No product record,
 *    price, coupon, stock level, shipping setting, cart or order is written by
 *    any part of this file.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN GLOSSED: everything here is the
 *   SERVER-RENDERED document plus in-process renderer calls. It proves the
 *   markup, the absence of any posted contract, and the destination. It does
 *   NOT prove the control paints correctly at either viewport, that the link
 *   navigates instantly from a warm cache, or that no layout shifts on click.
 *   Those are browser behaviours and were measured separately with a real
 *   headless browser. Do not read a green run here as browser QA.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_hcta_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * Fetch a URL and return the rendered document, or '' on any failure.
 *
 * ⭐ ASSERTS AGAINST RENDERED HTML, NOT TEMPLATE SOURCE. The sibling suite
 * `test-collection-purchase-path.php` records why: its first version counted
 * strings in .php files and reported seven failures on a correct build, because
 * the code comments explaining each change quote the markup they replaced. A
 * comment cannot survive PHP.
 *
 * ⚠ That hazard is SHARPER in this file than in any other, because
 *   `inc/collection-cta.php` now contains a large SUPERSEDED comment block that
 *   quotes the old form markup verbatim. Source-scanning this build would find
 *   `complete_hardcover_smart` and report a failed revert on a correct one.
 */
function bhp_hcta_fetch( $url ) {
	if ( ! $url ) {
		return '';
	}
	$res = wp_remote_get( $url, array( 'timeout' => 30, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/**
 * Everything from <header class="site-header" up to the closing </header>.
 *
 * ⛔ THE WHOLE SUITE DEPENDS ON THIS ISOLATION, AND MORE SO AFTER THE REVERSAL.
 *    Every page on this site also carries collection CTAs in its BODY — the
 *    homepage band, the /books/ banner, the funnel sticky bars — and they all
 *    still emit `bhp_bundle_redirect=checkout`, correctly, because they were
 *    NOT reverted. Counting that marker across a whole document would now
 *    report a failed revert on a perfectly correct build.
 */
function bhp_hcta_header( $html ) {
	$start = strpos( $html, '<header class="site-header"' );
	if ( false === $start ) {
		return '';
	}
	$end = strpos( $html, '</header>', $start );
	if ( false === $end ) {
		return '';
	}
	return substr( $html, $start, $end - $start );
}

/**
 * The document with its site header removed — i.e. the page's own body.
 *
 * Used by §5, which must prove the Collection page still sells WITHOUT being
 * satisfied by the header markup this suite spends the rest of its length
 * proving is now a link.
 */
function bhp_hcta_body_without_header( $html ) {
	$header = bhp_hcta_header( $html );
	if ( '' === $header ) {
		return $html;
	}
	return str_replace( $header, '', $html );
}

echo "\n=== §1 — LOADED: the header renderer, and the shared purchase renderer it no longer uses ===\n";

bhp_hcta_assert( function_exists( 'bhp_collection_header_cta' ), '§1 bhp_collection_header_cta() is loaded', $failures );

/*
 * ⭐ ASSERTED DELIBERATELY, THOUGH THE HEADER NO LONGER CALLS IT. The shared
 *    add-and-checkout renderer is what the Collection page, the four funnel
 *    pages, the homepage band and the /books/ banner all still use. If the
 *    revert had over-reached and removed it, §5 below would fail — but it would
 *    fail late and confusingly. This says so immediately.
 */
bhp_hcta_assert( function_exists( 'bhp_collection_add_to_cart_cta' ), '§1 the SHARED purchase renderer still exists (the Collection page needs it)', $failures );
bhp_hcta_assert( function_exists( 'bhp_collection_cta_available' ), '§1 the plugin-contract guard is loaded', $failures );
bhp_hcta_assert( bhp_collection_cta_available(), '§1 the bundle plugin contract is available on this install (the Collection page can still sell)', $failures );

/*
 * The context guard is KEPT as a documented filter surface even though the
 * header no longer consults it — see the reversal block in
 * inc/collection-cta.php. Asserted so that "kept" is a fact rather than a
 * comment, and so its removal is a deliberate act with a failing test.
 */
bhp_hcta_assert( function_exists( 'bhp_collection_cta_context_allows_add' ), '§1 bhp_collection_cta_context_allows_add() is retained as a filter surface', $failures );

echo "\n=== §2 — THE LINK: both header variants are anchors to /complete-collection/ ===\n";

if ( function_exists( 'bhp_collection_header_cta' ) ) {

	$variants = array(
		'bar' => array( 'class' => 'header-expedition-cta' ),
		'nav' => array( 'class' => 'site-nav__cta' ),
	);

	foreach ( $variants as $variant => $meta ) {
		$html = bhp_collection_header_cta( array( 'variant' => $variant ) );

		bhp_hcta_assert(
			0 === strpos( $html, '<a ' ),
			"§2 {$variant} — the control IS an anchor, and the anchor is the whole output",
			$failures
		);
		bhp_hcta_assert(
			false !== strpos( $html, 'class="' . $meta['class'] . '"' ),
			"§2 {$variant} — keeps its existing class ({$meta['class']}), so the header's CSS still governs it",
			$failures
		);
		bhp_hcta_assert(
			false !== strpos( $html, '/complete-collection/' ),
			"§2 {$variant} — points at /complete-collection/, exactly as Andrew's ruling names",
			$failures
		);
		bhp_hcta_assert(
			false !== strpos( $html, 'Get the Complete Collection' ),
			"§2 {$variant} — the label is unchanged by the reversal",
			$failures
		);

		/* ── The inverted half: every purchase marker must now be ABSENT. ── */
		bhp_hcta_assert( false === strpos( $html, '<form' ), "§2 {$variant} — renders NO form", $failures );
		bhp_hcta_assert( false === strpos( $html, '<button' ), "§2 {$variant} — renders NO submit button", $failures );
		bhp_hcta_assert( false === strpos( $html, 'bhp-bundle-form' ), "§2 {$variant} — carries NO class for bundle-drawer.js to intercept", $failures );
		bhp_hcta_assert( false === strpos( $html, 'bhp_bundle_nonce' ), "§2 {$variant} — carries NO add-to-cart nonce", $failures );
		bhp_hcta_assert( false === strpos( $html, 'bhp_bundle_action' ), "§2 {$variant} — posts NO bundle action", $failures );
		bhp_hcta_assert( false === strpos( $html, 'complete_hardcover_smart' ), "§2 {$variant} — no hardcover add action", $failures );
		bhp_hcta_assert( false === strpos( $html, 'complete_paperback_smart' ), "§2 {$variant} — no paperback add action", $failures );
		bhp_hcta_assert( false === strpos( $html, 'bhp_bundle_redirect' ), "§2 {$variant} — asks for NO checkout redirect", $failures );
		bhp_hcta_assert( false === strpos( $html, 'data-bhp-collection-action' ), "§2 {$variant} — is not format-synced (there is no format to sync)", $failures );
		bhp_hcta_assert( false === strpos( $html, 'bhp-collection-cta--header' ), "§2 {$variant} — carries no purchase-form layout hook", $failures );
	}

	/*
	 * ⭐ UNCONDITIONAL, AND THIS IS THE ASSERTION THAT ENCODES THE REVERSAL.
	 *
	 * 1.19.183 rendered a form and fell back to this anchor in two cases: the
	 * plugin being absent, and a suppressed context. Both fallbacks produced an
	 * anchor — so a test that merely saw an anchor could not tell "Andrew's
	 * reversal shipped" from "the bundle plugin is switched off and the old
	 * build fell back". Flipping the context filter, which USED to change the
	 * output completely, must now change nothing at all.
	 */
	if ( function_exists( 'bhp_collection_cta_context_allows_add' ) ) {
		$normal = bhp_collection_header_cta( array( 'variant' => 'bar' ) );

		add_filter( 'bhp_collection_cta_context_allows_add', '__return_false' );
		$suppressed_ctx = bhp_collection_header_cta( array( 'variant' => 'bar' ) );
		remove_filter( 'bhp_collection_cta_context_allows_add', '__return_false' );

		bhp_hcta_assert(
			$normal === $suppressed_ctx,
			'§2 the header output is UNCONDITIONAL — forcing the old context guard to false changes nothing',
			$failures
		);
		bhp_hcta_assert(
			bhp_collection_cta_available(),
			'§2 …and the plugin contract IS available, so the anchor is the ruling, not a fail-closed fallback',
			$failures
		);
	}
}

echo "\n=== §3 — SITEWIDE: ordinary rendered pages carry two header anchors and zero header forms ===\n";

/*
 * Three DIFFERENT template families, because the header is shared but the
 * templates around it are not — .single-post wraps the page in a narrower
 * container, and a product page runs WooCommerce's own template stack.
 */
$sitewide = array( 'home' => home_url( '/' ) );

$posts = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish' ) );
if ( $posts ) {
	$sitewide['blog post'] = get_permalink( $posts[0]->ID );
}
$products = get_posts( array( 'post_type' => 'product', 'numberposts' => 1, 'post_status' => 'publish' ) );
if ( $products ) {
	$sitewide['product'] = get_permalink( $products[0]->ID );
}

foreach ( $sitewide as $key => $url ) {
	$html = bhp_hcta_fetch( $url );
	bhp_hcta_assert( '' !== $html, "§3 {$key} — page renders (HTTP 200, non-empty body)", $failures );
	if ( '' === $html ) {
		continue;
	}

	$header = bhp_hcta_header( $html );
	bhp_hcta_assert( '' !== $header, "§3 {$key} — the site header is present in the document", $failures );
	if ( '' === $header ) {
		continue;
	}

	bhp_hcta_assert(
		1 === substr_count( $header, '<a class="header-expedition-cta"' ),
		sprintf( '§3 %s — EXACTLY ONE bar anchor in the header (got %d)', $key, substr_count( $header, '<a class="header-expedition-cta"' ) ),
		$failures
	);
	bhp_hcta_assert(
		1 === substr_count( $header, '<a class="site-nav__cta"' ),
		sprintf( '§3 %s — EXACTLY ONE dropdown anchor in the header (got %d)', $key, substr_count( $header, '<a class="site-nav__cta"' ) ),
		$failures
	);
	/*
	 * ⭐ UPDATED 1.19.260 (2026-08-19, `CYCLE165-LD-DIRECTION1-STEP1-HEADER`).
	 *
	 * ⛔ THE SUPERSEDED ASSERTION IS PRESERVED IMMEDIATELY BELOW rather than
	 *    deleted, because a hardcoded `2` that silently became a hardcoded `3`
	 *    would hide that a third header control now exists:
	 *
	 *        2 === substr_count( $header, '/complete-collection/' )
	 *
	 * WHAT MOVED: the header carries a THIRD control at the mobile nav
	 * breakpoint — `.bhp-header-offer`, the compact offer button that lives
	 * OUTSIDE the hamburger (`inc/header-offer.php`). It is an anchor to the same
	 * destination, so the count went 2 -> 3 on every page that renders it.
	 *
	 * ⭐ THE INTENT OF THIS ASSERTION IS UNCHANGED AND IS NOW STATED DIRECTLY
	 *    rather than encoded as a magic number: EVERY collection link in the
	 *    header is one of the known controls, and NO header link routes anywhere
	 *    else while claiming to be a collection CTA. Counting against the number
	 *    of offer buttons actually present means this assertion keeps working on
	 *    the pages where the offer is deliberately SUPPRESSED (the collection page
	 *    itself, /cart/ and /checkout/), where the expected count is 2 again.
	 */
	$offers_in_header = substr_count( $header, '<a class="bhp-header-offer"' );
	bhp_hcta_assert(
		( 2 + $offers_in_header ) === substr_count( $header, '/complete-collection/' ),
		sprintf( '§3 %s — both header CTAs plus %d offer button(s) route to the collection page, and nothing else does (got %d)', $key, $offers_in_header, substr_count( $header, '/complete-collection/' ) ),
		$failures
	);

	/*
	 * THE STRAGGLER CHECK, INVERTED. `<button class="header-expedition-cta"` and
	 * the redirect input are the exact markup this reversal removed. If either
	 * survives anywhere in the header, some path is still handing the customer
	 * the slow POST journey Andrew reversed.
	 */
	bhp_hcta_assert(
		false === strpos( $header, '<button class="header-expedition-cta"' ) && false === strpos( $header, '<button class="site-nav__cta"' ),
		"§3 {$key} — NO header CTA is still a submit button",
		$failures
	);
	bhp_hcta_assert(
		false === strpos( $header, 'bhp-collection-cta--header' ),
		"§3 {$key} — no purchase-form wrapper survives in the header",
		$failures
	);
}

echo "\n=== §4 — NO CART MUTATION FROM THE HEADER, ON ANY PAGE (stronger than the old suppression) ===\n";

/*
 * ⛔ WHAT CHANGED, AND WHY THIS IS A STRENGTHENING RATHER THAN A RELAXATION.
 *
 * The old suite asserted that the header rendered no purchase form on /cart/
 * and /checkout/ — two pages — because a runtime context guard suppressed it
 * there. The guarantee depended on that guard being correct, on is_checkout()
 * also covering the order-received endpoint, and on nobody filtering it.
 *
 * The header now posts nothing on ANY page, so the guarantee no longer has a
 * mechanism that could be wrong. This section therefore asserts it across the
 * commerce pages AND the ordinary pages §3 already fetched: a sitewide button
 * cannot mutate a cart the customer is mid-payment on, because it cannot mutate
 * a cart at all.
 */
$no_mutation = $sitewide;

if ( function_exists( 'wc_get_cart_url' ) && function_exists( 'wc_get_checkout_url' ) ) {
	/*
	 * ⚠ /checkout/ WITH AN EMPTY CART MAY REDIRECT TO /cart/, and wp_remote_get
	 *   follows that. It does not weaken the assertion: the header must post
	 *   nothing on whichever document comes back.
	 */
	$no_mutation['cart']     = wc_get_cart_url();
	$no_mutation['checkout'] = wc_get_checkout_url();
} else {
	bhp_hcta_assert( false, '§4 WooCommerce URL helpers unavailable — cannot reach /cart/ and /checkout/', $failures );
}

foreach ( $no_mutation as $key => $url ) {
	$html = bhp_hcta_fetch( $url );
	bhp_hcta_assert( '' !== $html, "§4 {$key} — page renders (HTTP 200, non-empty body)", $failures );
	if ( '' === $html ) {
		continue;
	}

	$header = bhp_hcta_header( $html );
	bhp_hcta_assert( '' !== $header, "§4 {$key} — the site header is present", $failures );
	if ( '' === $header ) {
		continue;
	}

	bhp_hcta_assert(
		false === strpos( $header, 'bhp_bundle_action' ),
		"§4 {$key} — the header posts NO bundle action here",
		$failures
	);
	bhp_hcta_assert(
		false === strpos( $header, 'name="bhp_bundle_redirect" value="checkout"' ),
		"§4 {$key} — the header carries NO add-and-checkout form here",
		$failures
	);
	bhp_hcta_assert(
		false === strpos( $header, 'bhp_bundle_nonce' ),
		"§4 {$key} — the header carries NO add-to-cart nonce here",
		$failures
	);
	bhp_hcta_assert(
		false === strpos( $header, '<form' ),
		"§4 {$key} — there is NO form of any kind inside the site header",
		$failures
	);
	bhp_hcta_assert(
		false !== strpos( $header, 'Get the Complete Collection' ),
		"§4 {$key} — the CTA is still THERE (reverted, never removed)",
		$failures
	);
}

echo "\n=== §5 — THE TWO-CLICK PURCHASE PATH SURVIVES ON THE COLLECTION PAGE ===\n";

/*
 * ⭐ THE REGRESSION GUARD ON THE REVERSAL ITSELF.
 *
 * Andrew reverted the HEADER on the explicit premise that the destination
 * already sells: "Then we have the already rendered and cached pages on
 * checkout for both the paperback and hardcovers." If the Collection page ever
 * stopped carrying its own add-and-checkout CTAs, the header reversal would
 * silently become a dead end — and every other section in this file would still
 * pass. This is the assertion that makes that impossible.
 *
 * ⛔ Measured against the page BODY with the site header removed, so the header
 *    anchors this suite just proved cannot accidentally satisfy it.
 */
$collection_url  = home_url( '/complete-collection/' );
$collection_html = bhp_hcta_fetch( $collection_url );

bhp_hcta_assert( '' !== $collection_html, '§5 /complete-collection/ renders (HTTP 200, non-empty body)', $failures );

if ( '' !== $collection_html ) {
	$body = bhp_hcta_body_without_header( $collection_html );

	bhp_hcta_assert(
		substr_count( $body, 'bhp_bundle_action' ) > 0,
		sprintf( '§5 the Collection page still carries add-to-cart controls in its body (got %d)', substr_count( $body, 'bhp_bundle_action' ) ),
		$failures
	);
	bhp_hcta_assert(
		substr_count( $body, 'name="bhp_bundle_redirect" value="checkout"' ) > 0,
		sprintf( '§5 …and they still land the customer on /checkout/ — the two-click path (got %d)', substr_count( $body, 'name="bhp_bundle_redirect" value="checkout"' ) ),
		$failures
	);
	bhp_hcta_assert(
		false !== strpos( $body, 'bhp_bundle_nonce' ),
		'§5 …carrying the plugin-owned nonce, so the POST is a real, valid add',
		$failures
	);

	/*
	 * BOTH FORMATS. Andrew's sentence names both explicitly — "for both the
	 * paperback and hardcovers" — and the format toggle is what makes the
	 * single header link a sufficient replacement for a hardcover-specific
	 * button. Asserting only one format would pass a build where the toggle
	 * had collapsed.
	 */
	bhp_hcta_assert(
		false !== strpos( $body, 'complete_hardcover_smart' ),
		'§5 the HARDCOVER set is still purchasable from the Collection page',
		$failures
	);
	bhp_hcta_assert(
		false !== strpos( $body, 'complete_paperback_smart' ) || false !== strpos( $body, 'data-bhp-collection-action' ),
		'§5 the PAPERBACK set is still reachable — either rendered directly or via the format toggle',
		$failures
	);
	bhp_hcta_assert(
		false !== strpos( $body, 'data-bhp-collection-action' ),
		'§5 the format toggle still rewires the format-agnostic CTAs (this is what makes one header link enough)',
		$failures
	);
}

/*
 * The other reverted-adjacent surfaces were NOT in scope and must be untouched.
 * The homepage band is the one Andrew named separately and guaranteed earlier
 * the same day, so it gets its own assertion rather than being assumed.
 */
$home_html = bhp_hcta_fetch( home_url( '/' ) );
if ( '' !== $home_html ) {
	$home_body = bhp_hcta_body_without_header( $home_html );
	bhp_hcta_assert(
		substr_count( $home_body, 'bhp_bundle_action' ) > 0,
		'§5 the HOMEPAGE band still carries its own two-click purchase CTA (not reverted, and not collateral damage)',
		$failures
	);
}

echo "\n=== §6 — ARIA-CURRENT: restored by the revert, and correct in both directions ===\n";

/*
 * ⭐ A REAL ACCESSIBILITY REPAIR, NOT A COSMETIC ONE. 1.19.183 recorded
 *    dropping `aria-current="page"` as a deliberate loss: a button that posts
 *    and lands on /checkout/ does not "point at the page you are on", so
 *    asserting it would have been shipping a false statement to assistive
 *    technology. The anchor points at that page again, so the assertion is
 *    true again — and the gold ring `.header-expedition-cta[aria-current]`
 *    draws once more.
 */
if ( '' !== $collection_html ) {
	$collection_header = bhp_hcta_header( $collection_html );
	bhp_hcta_assert(
		false !== strpos( $collection_header, 'aria-current="page"' ),
		'§6 on /complete-collection/ the header CTA marks itself as the current page',
		$failures
	);
}

$home_header = '' !== $home_html ? bhp_hcta_header( $home_html ) : '';
if ( '' !== $home_header ) {
	bhp_hcta_assert(
		false === strpos( $home_header, 'aria-current="page"' ),
		'§6 …and does NOT claim to be current on a page it does not point at',
		$failures
	);
}

echo "\n=== §7 — SAFETY: nothing script-shaped, nothing unescaped ===\n";

if ( function_exists( 'bhp_collection_header_cta' ) ) {
	foreach ( array( 'bar', 'nav' ) as $variant ) {
		$html = bhp_collection_header_cta( array( 'variant' => $variant ) );
		bhp_hcta_assert( false === strpos( $html, '<script' ), "§7 {$variant} — nothing script-shaped is emitted", $failures );
		bhp_hcta_assert(
			1 === substr_count( $html, '<a ' ) && 1 === substr_count( $html, '</a>' ),
			"§7 {$variant} — exactly one well-formed anchor, opened and closed once",
			$failures
		);
		bhp_hcta_assert(
			false !== strpos( $html, 'href="http' ),
			"§7 {$variant} — the href is a real absolute URL from home_url(), not a raw path",
			$failures
		);
	}

	/*
	 * An unknown variant must still render a usable control rather than an empty
	 * string — the header is sitewide, and a blank there is a silent hole.
	 */
	$unknown = bhp_collection_header_cta( array( 'variant' => 'not-a-real-variant' ) );
	bhp_hcta_assert(
		false !== strpos( $unknown, '<a ' ) && false !== strpos( $unknown, '/complete-collection/' ),
		'§7 an unrecognised variant still renders a working link (defaults to the bar control)',
		$failures
	);
}

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL HEADER COLLECTION-CTA ASSERTIONS PASSED\n";
exit( 0 );
