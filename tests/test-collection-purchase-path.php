<?php
/**
 * Brave Hearts — ONE-CLICK COLLECTION PURCHASE PATH (theme 1.19.172).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-collection-purchase-path.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (RELAYED through the Chief of Staff and
 * NOT witnessed by the agent that wrote this file):
 *
 *   "to actually buy the collection its all the way at the bottom of the page -
 *    That CTA section needs to be moved up to where the main three images are
 *    on that page ... it should automatically - add the books to your cart and
 *    take you to the checkout page for a 2 click journey to purchase"
 *
 * Two claims, and each is asserted separately because each can break alone:
 *
 *   §1  THE MOVE      — the purchase section now renders ABOVE the free
 *                       lead-magnet section on every funnel page that has one.
 *   §2  THE FORM      — the CTA emits the bundle plugin's real add-and-checkout
 *                       contract, with the right format, and nothing else.
 *   §3  NO STRAGGLERS — no funnel-page collection CTA is still a link to
 *                       /complete-collection/ or an in-page #collection anchor.
 *   §4  THE OFFER     — the bundle discount and FREE collection shipping that
 *                       the customer is promised on the way in are still the
 *                       ones the cart applies. ⛔ THIS IS THE HALF MOST LIKELY
 *                       TO BREAK SILENTLY: a faster path to a cart that has
 *                       lost its discount is a worse outcome than a slow one.
 *   §5  THE REAL CART — three distinct real products actually land in a real
 *                       WooCommerce cart, with the discount fee and $0.00
 *                       shipping, and adding twice does not produce six books.
 *
 * ⛔ NO ORDER IS EVER CREATED. §5 builds a cart in the CLI session, asserts
 *    against it, and empties it again. No product record, price, coupon, stock
 *    level, shipping setting or order is written by any part of this file.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cpp_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

$theme_dir = get_template_directory();

/*
 * ⭐ §1 AND §3 ASSERT AGAINST RENDERED HTML, NOT TEMPLATE SOURCE, AND THE
 *    REASON IS A DEFECT THIS SUITE ALREADY PRODUCED ONCE.
 *
 * The first version of this file read the .php templates and counted strings.
 * It reported seven failures on a correct build, because the CODE COMMENTS
 * explaining each change quote the markup they replaced — `href="#collection"`
 * appears in a comment as readily as in an anchor, and `substr_count()` cannot
 * tell them apart. Reading source to judge output is the exact failure mode
 * `.claude/rules/schema.md` and `docs/` warn about for structured data; it is
 * no better here.
 *
 * So each page is FETCHED and the real HTML is asserted. A comment cannot
 * survive PHP, and a template that renders nothing cannot pass by containing
 * the right words.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN GLOSSED: this is the SERVER-RENDERED
 *   document. It proves the sections' order, the form's presence and its
 *   fields. It does NOT prove that the button paints correctly, that the
 *   format toggle rewrites the hidden field, or that the Store API path
 *   navigates to checkout — those are React/JS behaviours and need a real
 *   browser. Do not read a green run here as browser QA.
 */
/*
 * `min_forms` and `expects_sync` encode a DELIBERATE asymmetry rather than
 * letting one page quietly under-deliver:
 *
 *   educators / gift-buyers / parent-kit — consumer funnels. Four
 *     add-and-checkout forms each: both price-card panels (one is `hidden`,
 *     both are in the DOM), the footer sticky bar and the final CTA. The last
 *     two live outside the panels, so at least one must carry the
 *     format-toggle hook.
 *
 *   organizations — a PROGRAM page. Its footer bar and its closing CTA are
 *     deliberately still "Contact" / "Start a Partnership Conversation",
 *     because an organization ordering for a program is starting a
 *     conversation about quantity, not buying three books. Only its two
 *     price-card panels take the direct path, and it needs no format sync.
 */
$funnel_pages = array(
	'educators'     => array( 'template' => 'page-audience-educators.php', 'min_forms' => 4, 'expects_sync' => true ),
	'gift-buyers'   => array( 'template' => 'page-audience-gift-buyers.php', 'min_forms' => 4, 'expects_sync' => true ),
	'organizations' => array( 'template' => 'page-audience-organizations.php', 'min_forms' => 2, 'expects_sync' => false ),
	'parent-kit'    => array( 'template' => 'page-reluctant-reader-adventure-kit.php', 'min_forms' => 4, 'expects_sync' => true ),
);

/**
 * Resolve a template to its live URL and fetch the rendered document.
 *
 * @return string HTML, or '' if the page is missing or the request failed.
 */
function bhp_cpp_render( $template ) {
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => $template,
			'number'     => 1,
		)
	);
	if ( ! $pages ) {
		return '';
	}
	$res = wp_remote_get(
		get_permalink( $pages[0]->ID ),
		array( 'timeout' => 30, 'sslverify' => false )
	);
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/**
 * The rendered document with the SITE HEADER removed.
 *
 * ⭐ ADDED 1.19.183 (2026-08-05), AND THE REASON IS A REAL FAILURE THIS SUITE
 *    PRODUCED, NOT A TIDY-UP.
 *
 * Andrew's ruling of 2026-08-05 ("Convert to hardcover purchase") made the
 * SITEWIDE header CTA a real add-and-checkout form. It therefore emits
 * `name="bhp_bundle_redirect" value="checkout"` twice — bar + mobile nav — on
 * EVERY page of the site, including pages this suite asserts have no such CTA.
 *
 * ⛔ THIS DOES NOT WEAKEN ANY ASSERTION, AND THAT DISTINCTION IS THE WHOLE
 *    POINT. The numbers below are unchanged. What changed is the region they
 *    are counted in: the page's own BODY, which is what each assertion was
 *    always about. Raising a threshold or deleting a failing check would have
 *    been the dishonest fix; re-scoping it keeps it a real constraint.
 *
 * The header's own two forms are asserted POSITIVELY — and asserted to be
 * exactly two, never three — by `tests/test-header-collection-cta.php`.
 * Coverage moved; none was lost.
 *
 * @param string $html Rendered document.
 * @return string The document minus <header class="site-header">…</header>.
 */
function bhp_cpp_body( $html ) {
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

echo "\n=== §1 — THE MOVE: purchase section RENDERS above the lead magnet ===\n";

$rendered = array();

foreach ( $funnel_pages as $key => $meta ) {
	$html            = bhp_cpp_render( $meta['template'] );
	$rendered[ $key ] = $html;

	bhp_cpp_assert( '' !== $html, "§1 {$key} — page renders (HTTP 200, non-empty body)", $failures );
	if ( '' === $html ) {
		continue;
	}

	$collection_at = strpos( $html, 'id="collection"' );
	$free_at       = strpos( $html, 'id="free"' );
	$hero_at       = strpos( $html, 'landing-hero"' );

	bhp_cpp_assert( false !== $collection_at, "§1 {$key} — renders a #collection section", $failures );
	bhp_cpp_assert( false !== $free_at, "§1 {$key} — renders a #free lead-magnet section", $failures );

	if ( false !== $collection_at && false !== $free_at ) {
		bhp_cpp_assert(
			$collection_at < $free_at,
			"§1 {$key} — #collection now precedes #free in the document (the CTA moved UP)",
			$failures
		);
	}

	if ( false !== $collection_at && false !== $hero_at ) {
		bhp_cpp_assert(
			$hero_at < $collection_at,
			"§1 {$key} — #collection still sits BELOW the hero, not above it",
			$failures
		);
	}

	/*
	 * "Directly under the main three book images": between the hero and the
	 * purchase section there must be NOTHING but the one-line quick-scan bar.
	 * This is the assertion that actually encodes Andrew's instruction, and it
	 * is the one that breaks if a future edit slips a section in between.
	 */
	$scan_at = strpos( $html, 'landing-scanbar"' );
	bhp_cpp_assert(
		false !== $scan_at && false !== $collection_at && $hero_at < $scan_at && $scan_at < $collection_at,
		"§1 {$key} — only the one-line scan bar separates the hero from the purchase section",
		$failures
	);

	/*
	 * The three cover cards and the price card must still be in that order,
	 * and close together — this is what stops a future edit from moving the
	 * price card up and leaving the covers behind.
	 */
	$books_at = strpos( $html, 'landing-books"' );
	$card_at  = strpos( $html, 'landing-pricecard"' );
	bhp_cpp_assert(
		false !== $books_at && false !== $card_at && $books_at < $card_at && $card_at < $free_at,
		"§1 {$key} — three book covers, then the price card, both above the lead magnet",
		$failures
	);
}

/*
 * Retailers is the deliberate exception and its exception is ASSERTED, not
 * assumed: a wholesale/trade page with no consumer price card and no
 * collection CTA at all. If someone ever adds one, this fails and the change
 * gets made on purpose rather than passing quietly.
 */
$retailers_html = bhp_cpp_render( 'page-audience-retailers.php' );
bhp_cpp_assert( '' !== $retailers_html, '§1 retailers — page renders', $failures );
bhp_cpp_assert(
	'' !== $retailers_html && false === strpos( $retailers_html, 'id="collection"' ),
	'§1 retailers — still renders NO collection purchase section (trade page, deliberate)',
	$failures
);
bhp_cpp_assert(
	'' !== $retailers_html && false === strpos( bhp_cpp_body( $retailers_html ), 'name="bhp_bundle_redirect"' ),
	'§1 retailers — its own BODY still renders NO add-and-checkout CTA (wholesale route is a conversation)',
	$failures
);
/*
 * And the header on that same page IS a purchase control, because Andrew's
 * ruling applies to every page. Asserted here rather than left implicit, so
 * "the retailers page has no CTA" can never quietly come to mean "the sitewide
 * header stopped rendering on it".
 */
bhp_cpp_assert(
	'' !== $retailers_html && false !== strpos( $retailers_html, 'name="bhp_bundle_redirect"' ),
	'§1 retailers — the SITEWIDE HEADER still carries its own add-and-checkout control (1.19.183)',
	$failures
);

echo "\n=== §2 — THE FORM: the CTA emits the plugin's real add-and-checkout contract ===\n";

bhp_cpp_assert( function_exists( 'bhp_collection_add_to_cart_cta' ), '§2 renderer bhp_collection_add_to_cart_cta() is loaded', $failures );
bhp_cpp_assert( function_exists( 'bhp_collection_cta_available' ), '§2 guard bhp_collection_cta_available() is loaded', $failures );
bhp_cpp_assert( bhp_collection_cta_available(), '§2 the bundle plugin contract is available on this install', $failures );

if ( function_exists( 'bhp_collection_add_to_cart_cta' ) && bhp_collection_cta_available() ) {

	foreach ( array( 'paperback', 'hardcover' ) as $fmt ) {
		$html = bhp_collection_add_to_cart_cta(
			array(
				'format' => $fmt,
				'label'  => 'Add the Complete Collection',
				'class'  => 'btn btn-primary',
				'event'  => 'unit_test_event',
				'source' => 'unit_test_source',
			)
		);

		bhp_cpp_assert( false !== strpos( $html, '<form method="post"' ), "§2 {$fmt} — renders a POST form", $failures );
		bhp_cpp_assert( false !== strpos( $html, 'bhp-bundle-form' ), "§2 {$fmt} — carries the class bundle-drawer.js intercepts", $failures );
		bhp_cpp_assert( false !== strpos( $html, 'name="bhp_bundle_nonce"' ), "§2 {$fmt} — carries the plugin's nonce", $failures );
		bhp_cpp_assert(
			false !== strpos( $html, 'name="bhp_bundle_action" value="complete_' . $fmt . '_smart"' ),
			"§2 {$fmt} — posts complete_{$fmt}_smart (the de-duplicating action)",
			$failures
		);
		bhp_cpp_assert(
			false !== strpos( $html, 'name="bhp_bundle_redirect" value="checkout"' ),
			"§2 {$fmt} — asks to finish on /checkout/, not the cart",
			$failures
		);
		bhp_cpp_assert( false !== strpos( $html, '<button type="submit"' ), "§2 {$fmt} — the control is a real submit button", $failures );
		bhp_cpp_assert( false !== strpos( $html, 'data-bhp-event="unit_test_event"' ), "§2 {$fmt} — analytics event attribute survives", $failures );
		bhp_cpp_assert( false !== strpos( $html, 'data-bhp-source="unit_test_source"' ), "§2 {$fmt} — analytics source attribute survives", $failures );
		bhp_cpp_assert( false === strpos( $html, 'complete-collection' ), "§2 {$fmt} — no longer links to the intermediate page", $failures );
	}

	// The format-sync marker: present only when asked for, because a control
	// inside a per-format panel must NOT be rewritten by the toggle.
	$synced   = bhp_collection_add_to_cart_cta( array( 'format' => 'hardcover', 'sync' => true ) );
	$unsynced = bhp_collection_add_to_cart_cta( array( 'format' => 'hardcover', 'sync' => false ) );
	bhp_cpp_assert( false !== strpos( $synced, 'data-bhp-collection-action' ), '§2 sync=true marks the action field for the format toggle', $failures );
	bhp_cpp_assert( false === strpos( $unsynced, 'data-bhp-collection-action' ), '§2 sync=false leaves an in-panel control unmarked', $failures );

	// A format-agnostic control must post the site's single default, not a
	// literal written into this file.
	$default  = function_exists( 'bhp_book_default_format' ) ? bhp_book_default_format() : '';
	$agnostic = bhp_collection_add_to_cart_cta( array( 'sync' => true ) );
	bhp_cpp_assert(
		'' !== $default && false !== strpos( $agnostic, 'complete_' . $default . '_smart' ),
		"§2 a format-agnostic CTA posts the site default format ({$default})",
		$failures
	);

	// Escaping: a hostile label must not become markup.
	$hostile = bhp_collection_add_to_cart_cta( array( 'format' => 'hardcover', 'label' => '<script>x</script>' ) );
	bhp_cpp_assert( false === strpos( $hostile, '<script>' ), '§2 the label is escaped, not injected', $failures );
}

echo "\n=== §2b — ⭐ 1.8.58 (`CYCLE165-LD-COLLECTION-CONVERSION`, T-3 / R-2) — THE COLLECTION PAGE'S OWN ABOVE-FOLD CTA ===\n";

/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⭐ THIS SECTION EXISTS BECAUSE THE BUILD BRIEF'S DIAGNOSIS WAS WRONG, AND
 *     THAT IS RECORDED HERE RATHER THAN QUIETLY CORRECTED IN A COMMIT.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The CYCLE165 conversion specification's fault F-2 reads: "The above-fold CTA
 * cannot buy anything. `bhp-landing-cta--primary` is a <button> with no
 * `href`. It scrolls to the format selector."
 *
 * ⛔ IT DOES NOT SCROLL. VERIFIED BY READING THE CODE, NOT BY TRUSTING EITHER
 *    DOCUMENT: since 1.8.32 the primary CTA has been a `<button type="submit">`
 *    inside `form.bhp-bundle-form`, carrying `bhp_bundle_action` =
 *    `complete_{format}_smart` and the checkout-redirect input, and
 *    `bundle-drawer.js` intercepts every `form.bhp-bundle-form` submit. The
 *    ONLY control on this page that scrolls to the pricing card is the
 *    gift-section CTA, which is the sole carrier of `data-bhp-scroll-to-card`.
 *    A button with no `href` is not evidence of anything — submit buttons do
 *    not have one.
 *
 * ⭐ SO R-2 WAS ALREADY SATISFIED, and the correct action was to GUARD it, not
 *   to build a second add path — which R-2's own constraint explicitly
 *   forbids ("Reuse it. Do not invent a second add path."). These assertions
 *   are that guard, and they are what makes the claim checkable next time
 *   instead of re-argued.
 */
$cpp_page = null;
foreach ( get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'fields'         => 'ids',
	)
) as $cpp_id ) {
	$cpp_content = get_post_field( 'post_content', $cpp_id );
	if ( is_string( $cpp_content ) && has_shortcode( $cpp_content, 'bhp_complete_series_landing' ) ) {
		$cpp_page = $cpp_id;
		break;
	}
}
bhp_cpp_assert( (bool) $cpp_page, '§2b the collection landing page exists', $failures );
if ( $cpp_page ) {
	$cpp_resp = wp_remote_get( get_permalink( $cpp_page ), array( 'timeout' => 30, 'sslverify' => false ) );
	$cpp_html = is_wp_error( $cpp_resp ) ? '' : (string) wp_remote_retrieve_body( $cpp_resp );
	bhp_cpp_assert( '' !== $cpp_html, '§2b the collection page renders', $failures );

	if ( '' !== $cpp_html ) {
		/*
		 * Containment, not co-occurrence. The needle runs from a
		 * `bhp-bundle-form` open tag to the primary-CTA hook without crossing
		 * a `</form>`, so it proves the button is INSIDE the form that carries
		 * the paperback smart action — which two separate `strpos` calls never
		 * could.
		 */
		bhp_cpp_assert(
			preg_match( '/<form[^>]*class="[^"]*bhp-bundle-form[^"]*"[^>]*>(?:(?!<\/form>).)*?name="bhp_bundle_action" value="complete_paperback_smart"(?:(?!<\/form>).)*?data-bhp-landing-main-cta/s', $cpp_html ) === 1,
			'§2b R-2 — the above-fold primary CTA submits the PAPERBACK smart-add form',
			$failures
		);
		bhp_cpp_assert(
			preg_match( '/<form[^>]*class="[^"]*bhp-bundle-form[^"]*"[^>]*>(?:(?!<\/form>).)*?name="bhp_bundle_nonce"(?:(?!<\/form>).)*?data-bhp-landing-main-cta/s', $cpp_html ) === 1,
			'§2b and that form carries the plugin nonce, so the add is not going to be refused',
			$failures
		);
		bhp_cpp_assert(
			preg_match( '/data-bhp-landing-main-cta[^>]*>/s', $cpp_html ) === 1,
			'§2b the primary CTA hook renders exactly once',
			$failures
		);
		bhp_cpp_assert(
			preg_match( '/<button[^>]*data-bhp-landing-main-cta/s', $cpp_html ) === 1
				&& preg_match( '/<a[^>]*data-bhp-landing-main-cta/s', $cpp_html ) === 0,
			'§2b it is a button, never an anchor',
			$failures
		);
		bhp_cpp_assert(
			preg_match( '/data-bhp-scroll-to-card[^>]*data-bhp-landing-main-cta|data-bhp-landing-main-cta[^>]*data-bhp-scroll-to-card/s', $cpp_html ) === 0,
			'§2b it does NOT carry the scroll-to-card hook (the brief\'s F-2 described the gift-section CTA, not this one)',
			$failures
		);
		/*
		 * ⛔ AND THERE IS STILL ONLY ONE ADD PATH. Every add-to-cart on this
		 *    page must go through `form.bhp-bundle-form`; a bare button posting
		 *    a bundle action outside one would bypass the drawer's smart
		 *    de-duplication and could add a title the visitor already has.
		 */
		$cpp_actions = preg_match_all( '/name="bhp_bundle_action"/', $cpp_html );
		$cpp_forms   = preg_match_all( '/class="[^"]*bhp-bundle-form[^"]*"/', $cpp_html );
		bhp_cpp_assert(
			$cpp_actions > 0 && $cpp_actions === $cpp_forms,
			"§2b every bundle action sits inside a bhp-bundle-form ({$cpp_actions} actions, {$cpp_forms} forms) — no second add path was invented",
			$failures
		);
	}
}

echo "\n=== §3 — NO STRAGGLERS: every funnel collection CTA takes the direct path ===\n";

/*
 * ⭐ THE ONE ANCHOR THAT SURVIVES ON PURPOSE.
 *
 * The HERO's secondary button ("Give / Explore the Complete Collection") is
 * deliberately still `href="#collection"` on the three pages that have one.
 * Its job is to REVEAL the offer, not to BE the offer — and after the move the
 * offer is one screen below it, showing price, the format toggle, the savings
 * and free shipping before the customer commits. Turning that one into an
 * instant add would put a customer who clicked to LOOK straight into a
 * checkout holding $53.98 of hardcovers.
 *
 * Every CTA that IS the buy action — the price card, the footer sticky bar and
 * the final CTA — is a real add-and-checkout button and must never be an
 * anchor again. That is what §3 asserts, per REGION, against rendered HTML.
 *
 * ⚠ If Andrew wants the hero button to buy directly too, it is a two-line
 *   change per page and this expectation moves with it. It is called out in
 *   the CTA change/keep list rather than decided silently.
 */
foreach ( $funnel_pages as $key => $meta ) {
	$html = $rendered[ $key ];
	if ( '' === $html ) {
		bhp_cpp_assert( false, "§3 {$key} — page did not render, cannot assert its CTAs", $failures );
		continue;
	}

	// --- The footer sticky bar: everything from its wrapper to end of document.
	$sticky_at = strpos( $html, 'landing-stickybar"' );
	$sticky    = false !== $sticky_at ? substr( $html, $sticky_at ) : '';
	bhp_cpp_assert( '' !== $sticky, "§3 {$key} — renders a footer sticky bar", $failures );
	bhp_cpp_assert(
		'' !== $sticky && false === strpos( $sticky, 'href="#collection"' ),
		"§3 {$key} — the footer bar holds NO #collection anchor",
		$failures
	);

	// --- The final CTA: from its own wrapper up to where the sticky bar starts.
	$final_at = strpos( $html, 'landing-final__ctas' );
	$final    = ( false !== $final_at && false !== $sticky_at && $sticky_at > $final_at )
		? substr( $html, $final_at, $sticky_at - $final_at )
		: '';
	bhp_cpp_assert( '' !== $final, "§3 {$key} — renders a final CTA block", $failures );
	bhp_cpp_assert(
		'' !== $final && false === strpos( $final, 'href="#collection"' ),
		"§3 {$key} — the final CTA holds NO #collection anchor",
		$failures
	);

	// --- The price card: no link out to the intermediate page.
	$card_at = strpos( $html, 'landing-pricecard"' );
	$card    = false !== $card_at ? substr( $html, $card_at, 12000 ) : '';
	bhp_cpp_assert(
		'' !== $card && false === strpos( $card, 'complete-collection/"' ),
		"§3 {$key} — the price card no longer links to /complete-collection/",
		$failures
	);

	/*
	 * BODY-SCOPED since 1.19.183: the sitewide header now contributes two of
	 * these markers to every page. Counting document-wide would let a funnel
	 * page that lost two of its OWN four CTAs keep passing on the header's.
	 */
	$forms = substr_count( bhp_cpp_body( $html ), 'name="bhp_bundle_redirect" value="checkout"' );
	bhp_cpp_assert(
		$forms >= (int) $meta['min_forms'],
		sprintf( '§3 %s — its own body renders at least %d add-and-checkout forms (got %d)', $key, $meta['min_forms'], $forms ),
		$failures
	);
	bhp_cpp_assert(
		substr_count( $html, 'complete_hardcover_smart' ) >= 1 && substr_count( $html, 'complete_paperback_smart' ) >= 1,
		"§3 {$key} — both formats are reachable from this page's CTAs",
		$failures
	);

	$sync_count = substr_count( $html, 'data-bhp-collection-action' );
	if ( $meta['expects_sync'] ) {
		bhp_cpp_assert(
			$sync_count >= 1,
			"§3 {$key} — a CTA outside the price card follows the format toggle (got {$sync_count})",
			$failures
		);
	} else {
		bhp_cpp_assert(
			0 === $sync_count,
			"§3 {$key} — program page: no format-synced CTA expected, and none present",
			$failures
		);
	}
}

echo "\n=== §4 — THE OFFER: discount and FREE collection shipping are intact ===\n";

if ( function_exists( 'bhp_bundle_rules' ) && function_exists( 'bhp_bundle_expected_price' ) ) {
	foreach ( array( 'paperback' => 3.98, 'hardcover' => 4.98 ) as $fmt => $expected_discount ) {
		$rule = bhp_bundle_rules( $fmt )[3];
		bhp_cpp_assert(
			abs( (float) $rule['discount'] - $expected_discount ) < 0.005,
			sprintf( '§4 %s — 3-book discount is still $%0.2f', $fmt, $expected_discount ),
			$failures
		);
		bhp_cpp_assert(
			abs( (float) $rule['shipping'] ) < 0.005,
			"§4 {$fmt} — the complete collection still ships FREE (1.8.23, Option B)",
			$failures
		);
	}

	// Three DISTINCT titles per format — "adds the 3 books" asserted, not assumed.
	if ( function_exists( 'bhp_bundle_catalog' ) ) {
		$catalog = bhp_bundle_catalog();
		foreach ( array( 'paperback', 'hardcover' ) as $fmt ) {
			$ids = array();
			foreach ( (array) ( $catalog[ $fmt ] ?? array() ) as $info ) {
				$ids[] = (int) ( $info['variation_id'] ?: $info['product_id'] );
			}
			bhp_cpp_assert(
				3 === count( $ids ) && 3 === count( array_unique( $ids ) ),
				"§4 {$fmt} — the catalog resolves to exactly 3 DISTINCT purchasable IDs",
				$failures
			);
		}
	}
} else {
	bhp_cpp_assert( false, '§4 bundle plugin pricing functions are unavailable — cannot assert the offer', $failures );
}

echo "\n=== §5 — THE REAL CART: three books, the discount fee, free shipping, no double-add ===\n";

if ( ! function_exists( 'WC' ) || ! function_exists( 'bhp_bundle_add_missing_titles_to_cart' ) ) {
	echo "SKIP: §5 needs WooCommerce and the bundle plugin in this process.\n";
} else {
	if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();
	}

	if ( ! WC()->cart ) {
		echo "SKIP: §5 could not load a cart in this CLI session.\n";
	} else {
		$catalog = bhp_bundle_catalog();

		/*
		 * BOTH FORMATS, not just the default. The hardcover CTA and the
		 * paperback CTA post different actions and hit different rows of the
		 * discount and shipping tables; asserting only the default would leave
		 * half of Andrew's "format-aware" requirement unverified.
		 */
		foreach ( array( 'paperback', 'hardcover' ) as $fmt ) {
		$keys = array_keys( $catalog[ $fmt ] );

		WC()->cart->empty_cart();
		bhp_bundle_add_missing_titles_to_cart( $fmt, $keys );

		/*
		 * ═══════════════════════════════════════════════════════════════
		 * ⭐ RETARGETED 1.19.198 / plugin 1.8.27 (2026-08-05, CYCLE144-LD-228)
		 *    AFTER THIS ASSERTION FAILED ON A CORRECT BUILD. Recorded, not
		 *    silently rewritten.
		 * ═══════════════════════════════════════════════════════════════
		 *
		 * Superseded assertion, verbatim:
		 *     $count = WC()->cart->get_cart_contents_count();
		 *     bhp_cpp_assert( 3 === (int) $count, "§5 {$fmt} — one click puts exactly 3 books in the cart (got {$count})", $failures );
		 *
		 * ⭐ WHAT ACTUALLY CHANGED, AND WHY THE OLD NUMBER WAS RIGHT UNTIL
		 *    IT WASN'T. Andrew's 2026-08-05 ruling makes The Adventure
		 *    Activity Book FREE with a complete collection, so the moment
		 *    this CTA's third book lands the plugin auto-includes a $0.00
		 *    add-on line. `get_cart_contents_count()` counts LINES, so a
		 *    correct cart is now 4 — three books and one free download.
		 *
		 * ⛔ THE ASSERTION IS STRICTER AFTER THIS CHANGE, NOT LOOSER. It no
		 *    longer asks "how many lines are there", which would pass for
		 *    ANY fourth product. It asks the two questions that actually
		 *    matter and asks them separately:
		 *      1. exactly THREE CATALOG BOOKS - the claim this section has
		 *         always been about, now immune to add-on noise;
		 *      2. the only non-book line, if any, is the allowlisted add-on
		 *         AND it is priced $0.00.
		 *    A regression that put a real fourth PRODUCT in the cart failed
		 *    the old assertion and still fails this one.
		 */
		$count = WC()->cart->get_cart_contents_count();
		$bhp_cpp_books = 0;
		$bhp_cpp_extras = array();
		foreach ( WC()->cart->get_cart() as $bhp_cpp_line ) {
			if ( null !== bhp_bundle_identify_cart_item( $bhp_cpp_line['product_id'], $bhp_cpp_line['variation_id'] ) ) {
				$bhp_cpp_books += (int) $bhp_cpp_line['quantity'];
				continue;
			}
			$bhp_cpp_extras[] = $bhp_cpp_line;
		}
		bhp_cpp_assert(
			3 === $bhp_cpp_books,
			"§5 {$fmt} — one click puts exactly 3 CATALOG BOOKS in the cart (got {$bhp_cpp_books}; {$count} lines total)",
			$failures
		);
		foreach ( $bhp_cpp_extras as $bhp_cpp_extra ) {
			bhp_cpp_assert(
				function_exists( 'bhp_bundle_is_addon_item' )
				&& bhp_bundle_is_addon_item( (int) $bhp_cpp_extra['product_id'], (int) $bhp_cpp_extra['variation_id'] ),
				"§5 {$fmt} — ⭐ the only non-book line is the ALLOWLISTED activity-book add-on, never a stray product",
				$failures
			);
			bhp_cpp_assert(
				abs( (float) $bhp_cpp_extra['data']->get_price() ) < 0.005,
				'§5 ' . $fmt . ' — ⭐ and it is priced $0.00, so nobody is charged for it (got $' . $bhp_cpp_extra['data']->get_price() . ')',
				$failures
			);
		}

		/*
		 * THE DOUBLE-ADD GUARD. The server path is redirect-after-POST, so a
		 * browser refresh re-requests /checkout/ rather than the add — but a
		 * customer who taps the CTA twice hits the handler twice for real.
		 * `complete_{fmt}_smart` is what makes that harmless, and this is the
		 * assertion that proves it rather than trusting the function's name.
		 */
		/*
		 * ⭐ RETARGETED 1.19.198 for the same reason as the assertion above,
		 *    and the double-add property is UNCHANGED and still the point.
		 *    Superseded assertion, verbatim:
		 *        3 === (int) $count_after,
		 *        "§5 {$fmt} — a SECOND add is a no-op, not six books (got {$count_after})"
		 *
		 * ⛔ IT ALSO NOW PROVES THE ADD-ON IS NOT DOUBLE-ADDED, which is a
		 *    NEW failure mode this release introduces: the free copy is added
		 *    on the same hook the second click fires, so a missing
		 *    "already have one" check would give a customer two.
		 */
		bhp_bundle_add_missing_titles_to_cart( $fmt, $keys );
		$count_after = WC()->cart->get_cart_contents_count();
		$bhp_cpp_books_after = 0;
		$bhp_cpp_addons_after = 0;
		foreach ( WC()->cart->get_cart() as $bhp_cpp_line ) {
			if ( null !== bhp_bundle_identify_cart_item( $bhp_cpp_line['product_id'], $bhp_cpp_line['variation_id'] ) ) {
				$bhp_cpp_books_after += (int) $bhp_cpp_line['quantity'];
			} elseif ( function_exists( 'bhp_bundle_is_addon_item' )
				&& bhp_bundle_is_addon_item( (int) $bhp_cpp_line['product_id'], (int) $bhp_cpp_line['variation_id'] ) ) {
				$bhp_cpp_addons_after += (int) $bhp_cpp_line['quantity'];
			}
		}
		bhp_cpp_assert(
			3 === $bhp_cpp_books_after,
			"§5 {$fmt} — a SECOND add is a no-op, not six books (got {$bhp_cpp_books_after} books; {$count_after} lines total)",
			$failures
		);
		bhp_cpp_assert(
			$bhp_cpp_addons_after <= 1,
			"§5 {$fmt} — ⭐ and the free add-on is not double-added by the second click (got {$bhp_cpp_addons_after})",
			$failures
		);

		WC()->cart->calculate_totals();

		$fee_total = 0.0;
		foreach ( WC()->cart->get_fees() as $fee ) {
			$fee_total += (float) $fee->amount;
		}
		bhp_cpp_assert(
			$fee_total < -0.005,
			sprintf( '§5 %s — the bundle discount is applied as a negative cart fee (%0.2f)', $fmt, $fee_total ),
			$failures
		);

		$expected_discount = (float) bhp_bundle_rules( $fmt )[3]['discount'];
		bhp_cpp_assert(
			abs( abs( $fee_total ) - $expected_discount ) < 0.005,
			sprintf( '§5 %s — the fee equals the promised discount ($%0.2f)', $fmt, $expected_discount ),
			$failures
		);

		/*
		 * bhp_bundle_shipping_amount() takes the EVALUATION of a cart, not the
		 * cart — it is deliberately a pure function of that array so it stays
		 * unit-testable with a stub. Passing WC()->cart straight in throws a
		 * TypeError; the first run of this suite on staging did exactly that
		 * and the fatal aborted the file before its own exit(1), so wp-cli
		 * reported success. Recorded here because a test that can fail by
		 * fataling is worse than no test.
		 */
		if ( function_exists( 'bhp_bundle_shipping_amount' ) && function_exists( 'bhp_bundle_evaluate_cart' ) ) {
			$eval = bhp_bundle_evaluate_cart( WC()->cart );
			$ship = (float) bhp_bundle_shipping_amount( $eval );
			bhp_cpp_assert(
				abs( $ship ) < 0.005,
				sprintf( '§5 %s — the 3-book cart resolves to FREE shipping (got %0.2f)', $fmt, $ship ),
				$failures
			);
			bhp_cpp_assert(
				! empty( $eval['is_complete_collection'] ),
				"§5 {$fmt} — the cart is recognised as a COMPLETE COLLECTION (the free-shipping branch)",
				$failures
			);
		}

		// Leave nothing behind. The CLI cart is a session like any other.
		WC()->cart->empty_cart();
		bhp_cpp_assert( 0 === (int) WC()->cart->get_cart_contents_count(), "§5 {$fmt} — test cart emptied afterwards", $failures );
		} // end foreach format
	}
}

echo "\n=== §6 — THE PRODUCT-PAGE UPSELL OFFERS THE FORMAT ON SCREEN ===\n";

/*
 * ⛔ THE REGRESSION THIS EXISTS TO PREVENT — CYCLE144-LD-23, found live.
 *
 * The hardcover permalink 301s to the canonical PAPERBACK product page with
 * `?bhp_format=hardcover`. `global $product` is the paperback there, so a
 * module that derives its format from the product ID offers the PAPERBACK
 * collection on a page the customer is reading as hardcover. Harmless while
 * the CTA was a link; a wrong three-book order once the CTA adds to the cart.
 *
 * Asserted against rendered HTML on both URLs, because this is precisely the
 * kind of thing that reads correctly in the source and is wrong on screen.
 */
if ( function_exists( 'bhp_bundle_catalog' ) ) {
	$cat = bhp_bundle_catalog();
	$cases = array();
	foreach ( array( 'hardcover', 'paperback' ) as $fmt ) {
		$first = $cat[ $fmt ] ? reset( $cat[ $fmt ] ) : null;
		if ( $first ) {
			$cases[ $fmt ] = get_permalink( (int) $first['product_id'] );
		}
	}

	foreach ( $cases as $fmt => $url ) {
		bhp_cpp_assert( ! empty( $url ), "§6 {$fmt} — product permalink resolves", $failures );
		if ( empty( $url ) ) {
			continue;
		}
		$res  = wp_remote_get( $url, array( 'timeout' => 30, 'sslverify' => false ) );
		$body = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res );

		bhp_cpp_assert( '' !== $body, "§6 {$fmt} — product page renders", $failures );
		if ( '' === $body ) {
			continue;
		}

		$start = strpos( $body, '<section class="bhp-cc-upsell"' );
		$block = false !== $start ? substr( $body, $start, 4000 ) : '';
		bhp_cpp_assert( '' !== $block, "§6 {$fmt} — the collection upsell card renders on this product page", $failures );
		if ( '' === $block ) {
			continue;
		}

		bhp_cpp_assert(
			false !== strpos( $block, 'complete_' . $fmt . '_smart' ),
			"§6 {$fmt} — the upsell CTA adds the {$fmt} collection, matching the format on screen",
			$failures
		);
		$wrong = ( 'hardcover' === $fmt ) ? 'paperback' : 'hardcover';
		bhp_cpp_assert(
			false === strpos( $block, 'complete_' . $wrong . '_smart' ),
			"§6 {$fmt} — and does NOT offer the {$wrong} collection",
			$failures
		);
		bhp_cpp_assert(
			false !== strpos( $block, 'name="bhp_bundle_redirect" value="checkout"' ),
			"§6 {$fmt} — the upsell CTA finishes on /checkout/",
			$failures
		);
		bhp_cpp_assert(
			false !== stripos( $block, 'Add the Complete ' . $fmt . ' Collection' ),
			"§6 {$fmt} — the label says Add (it charges) rather than See (it did not)",
			$failures
		);
	}
}

echo "\n=== §7 — THE HOMEPAGE AND /books/ BANDS BUY, AND BUY THE CHOSEN FORMAT ===\n";

/*
 * ⭐ ADDED 1.19.177, 2026-08-05, CYCLE144-LD-51. §1–§6 above are UNTOUCHED.
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the Chief
 * of Staff and witnessed by the main session — NOT witnessed first-hand by the
 * agent that wrote this section): the homepage "Get the Complete Collection"
 * CTA, and its /books/ twin via the shared band, must add the collection to the
 * cart and land on the checkout page like the funnel-page CTAs, rather than
 * link to /complete-collection/.
 *
 * The two pages share ONE file
 * (`template-parts/components/complete-collection-feature.php`), so both are
 * asserted against RENDERED HTML rather than once against the partial: a shared
 * partial can still be called with the wrong argument, and that is exactly the
 * defect that left the homepage on 'link' while /books/ was on 'checkout'.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN GLOSSED. This is the SERVER-RENDERED
 *   document. It proves the form's presence and fields, the toggle's presence
 *   and pre-selection, the sync marker, and that the toggle's own script is
 *   enqueued. It does NOT prove that clicking a toggle button rewrites the
 *   hidden field in a browser, or that the Store API path navigates to
 *   /checkout/ — those are JS behaviours and need a real browser. A green run
 *   here is not browser QA.
 */
$bhp_cpp_bands = array(
	'homepage' => array( 'url' => home_url( '/' ), 'section' => 'home-sales-paths' ),
	'books'    => array( 'url' => '', 'section' => 'books-complete-collection-banner' ),
);

/*
 * ⚠ /books/ IS NOT RESOLVABLE BY `_wp_page_template`, AND THAT IS A REAL FACT
 *   ABOUT THIS SITE RATHER THAN A TEST CONVENIENCE. The page (ID 102 on
 *   staging) carries NO template meta: WordPress picks `page-books.php` through
 *   the `page-{slug}.php` hierarchy, by filename. The template-meta lookup that
 *   works for every funnel page in §1 returns NOTHING here, and the first run of
 *   this section failed on exactly that. Slug lookup first, meta second.
 */
$bhp_cpp_books_page = get_page_by_path( 'books' );
if ( ! $bhp_cpp_books_page ) {
	$bhp_cpp_books_pages = get_pages(
		array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-books.php', 'number' => 1 )
	);
	$bhp_cpp_books_page = $bhp_cpp_books_pages ? $bhp_cpp_books_pages[0] : null;
}
$bhp_cpp_bands['books']['url'] = $bhp_cpp_books_page ? get_permalink( $bhp_cpp_books_page->ID ) : '';

$bhp_cpp_default_fmt = function_exists( 'bhp_book_default_format' ) ? bhp_book_default_format() : '';
bhp_cpp_assert( '' !== $bhp_cpp_default_fmt, '§7 the theme exposes a single default format', $failures );

$bhp_cpp_toggle_formats = array();

foreach ( $bhp_cpp_bands as $bhp_cpp_key => $bhp_cpp_band ) {

	bhp_cpp_assert( '' !== $bhp_cpp_band['url'], "§7 {$bhp_cpp_key} — page URL resolves", $failures );
	if ( '' === $bhp_cpp_band['url'] ) {
		continue;
	}

	$bhp_cpp_res  = wp_remote_get( $bhp_cpp_band['url'], array( 'timeout' => 30, 'sslverify' => false ) );
	$bhp_cpp_html = ( is_wp_error( $bhp_cpp_res ) || 200 !== (int) wp_remote_retrieve_response_code( $bhp_cpp_res ) )
		? ''
		: (string) wp_remote_retrieve_body( $bhp_cpp_res );

	bhp_cpp_assert( '' !== $bhp_cpp_html, "§7 {$bhp_cpp_key} — page renders (HTTP 200, non-empty body)", $failures );
	if ( '' === $bhp_cpp_html ) {
		continue;
	}

	/*
	 * Slice the BAND out of the document before asserting anything. Both pages
	 * carry other purchase controls (the product cards, the footer), and a
	 * document-wide substr_count() would happily pass on someone else's form —
	 * which is the same "assert the region, not the page" discipline §3 uses.
	 *
	 * ⛔ THE OBVIOUS BOUND IS WRONG, AND THE FIRST RUN OF THIS SECTION PROVED
	 *    IT. "Cut at the first `</section>`" fails here because the band
	 *    CONTAINS a nested `<section id="bhp-look-inside-complete_collection">`
	 *    — the collection gallery, ~20KB of it — whose closing tag arrives
	 *    ~900 bytes in, long before the CTA at ~22KB. That bound reported the
	 *    CTA missing on a build where it renders correctly, which is worse
	 *    than no assertion at all.
	 *
	 *    So the bound is the next SIBLING section: the next `<section` tag that
	 *    is not the look-inside gallery. Scanned with a regex over the tags
	 *    rather than guessed with a byte count, so a bigger gallery cannot
	 *    silently push the CTA outside the window again.
	 */
	$bhp_cpp_at = strpos( $bhp_cpp_html, 'id="' . $bhp_cpp_band['section'] . '"' );
	$bhp_cpp_band_html = false !== $bhp_cpp_at ? substr( $bhp_cpp_html, $bhp_cpp_at, 60000 ) : '';

	if ( '' !== $bhp_cpp_band_html && preg_match_all( '/<section[^>]*>/', $bhp_cpp_band_html, $bhp_cpp_tags, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $bhp_cpp_tags[0] as $bhp_cpp_tag ) {
			// Offset 0-ish is the band's own opening tag; skip it and the gallery.
			if ( $bhp_cpp_tag[1] < 200 || false !== strpos( $bhp_cpp_tag[0], 'bhp-look-inside' ) ) {
				continue;
			}
			$bhp_cpp_band_html = substr( $bhp_cpp_band_html, 0, $bhp_cpp_tag[1] );
			break;
		}
	}

	bhp_cpp_assert( '' !== $bhp_cpp_band_html, "§7 {$bhp_cpp_key} — renders the Complete Collection band (#{$bhp_cpp_band['section']})", $failures );
	if ( '' === $bhp_cpp_band_html ) {
		continue;
	}

	// --- The contract. Identical to what the funnel CTAs post.
	bhp_cpp_assert( false !== strpos( $bhp_cpp_band_html, '<form method="post"' ), "§7 {$bhp_cpp_key} — the band's CTA is a POST form, not a link", $failures );
	bhp_cpp_assert( false !== strpos( $bhp_cpp_band_html, 'bhp-bundle-form' ), "§7 {$bhp_cpp_key} — carries the class bundle-drawer.js intercepts (and its double-submit guard)", $failures );
	bhp_cpp_assert( false !== strpos( $bhp_cpp_band_html, 'name="bhp_bundle_nonce"' ), "§7 {$bhp_cpp_key} — carries the plugin's nonce", $failures );
	bhp_cpp_assert( false !== strpos( $bhp_cpp_band_html, 'name="bhp_bundle_redirect" value="checkout"' ), "§7 {$bhp_cpp_key} — asks to finish on /checkout/, not the cart", $failures );
	bhp_cpp_assert(
		false !== strpos( $bhp_cpp_band_html, 'name="bhp_bundle_action" value="complete_' . $bhp_cpp_default_fmt . '_smart"' ),
		"§7 {$bhp_cpp_key} — posts the DEFAULT format's de-duplicating action (complete_{$bhp_cpp_default_fmt}_smart)",
		$failures
	);

	/*
	 * ⭐ THE ASSERTION THAT ENCODES "no double-add". EXACTLY ONE
	 *    add-and-checkout form in the band. Two would mean the old inline form
	 *    survived alongside the shared renderer, and a customer could add the
	 *    set twice from one section.
	 */
	$bhp_cpp_forms = substr_count( $bhp_cpp_band_html, 'name="bhp_bundle_redirect" value="checkout"' );
	bhp_cpp_assert( 1 === $bhp_cpp_forms, "§7 {$bhp_cpp_key} — exactly ONE add-and-checkout form in the band (got {$bhp_cpp_forms})", $failures );

	// --- The toggle.
	bhp_cpp_assert( false !== strpos( $bhp_cpp_band_html, 'data-bhp-collection-band' ), "§7 {$bhp_cpp_key} — renders the format radiogroup", $failures );
	bhp_cpp_assert(
		false !== strpos( $bhp_cpp_band_html, 'data-bhp-band-format-btn="hardcover"' )
		&& false !== strpos( $bhp_cpp_band_html, 'data-bhp-band-format-btn="paperback"' ),
		"§7 {$bhp_cpp_key} — BOTH formats are offered by the toggle",
		$failures
	);
	$bhp_cpp_btns = substr_count( $bhp_cpp_band_html, 'data-bhp-band-format-btn=' );
	bhp_cpp_assert( 2 === $bhp_cpp_btns, "§7 {$bhp_cpp_key} — exactly two format buttons (got {$bhp_cpp_btns})", $failures );

	/*
	 * The pre-selected button must be the SITE default, and it must be the one
	 * the hidden action already names. If those two ever disagree, the first
	 * click buys a format the customer never chose — the exact CYCLE144-LD-23
	 * defect class, one surface further out.
	 */
	if ( preg_match( '/aria-checked="true"[^>]*data-bhp-band-format-btn="([a-z]+)"/', $bhp_cpp_band_html, $bhp_cpp_m ) ) {
		bhp_cpp_assert(
			$bhp_cpp_m[1] === $bhp_cpp_default_fmt,
			"§7 {$bhp_cpp_key} — the PRE-SELECTED toggle button is the site default ({$bhp_cpp_default_fmt}), got {$bhp_cpp_m[1]}",
			$failures
		);
	} else {
		bhp_cpp_assert( false, "§7 {$bhp_cpp_key} — exactly one toggle button is pre-selected via aria-checked=\"true\"", $failures );
	}

	/*
	 * ⭐ THE SYNC MARKER IS THE WHOLE MECHANISM. Without it the toggle paints a
	 *    selection the form ignores, and a customer who picked paperback checks
	 *    out holding three hardcovers. Asserted in the rendered band, not in the
	 *    renderer's unit output (§2 already covers that).
	 */
	$bhp_cpp_sync = substr_count( $bhp_cpp_band_html, 'data-bhp-collection-action' );
	bhp_cpp_assert( 1 === $bhp_cpp_sync, "§7 {$bhp_cpp_key} — the hidden action field follows the toggle (got {$bhp_cpp_sync} sync markers)", $failures );

	// --- The script that does the following. Enqueued document-wide, so this
	//     one looks at the whole page rather than the band slice.
	bhp_cpp_assert(
		false !== strpos( $bhp_cpp_html, 'assets/js/collection-band.js' ),
		"§7 {$bhp_cpp_key} — collection-band.js is enqueued on this page",
		$failures
	);

	// --- The escape hatch survives (B7), and the CTA itself is no longer a link.
	bhp_cpp_assert(
		false !== strpos( $bhp_cpp_band_html, 'home-collection-feature__browse' ),
		"§7 {$bhp_cpp_key} — the \"read about the collection first\" link is still offered",
		$failures
	);
	bhp_cpp_assert(
		false === strpos( $bhp_cpp_band_html, '<a class="btn btn-primary home-collection-feature__cta"' ),
		"§7 {$bhp_cpp_key} — the CTA is no longer an anchor to /complete-collection/",
		$failures
	);

	// Collect what the toggle actually offers, for the cart assertions below.
	if ( preg_match_all( '/data-bhp-band-format-btn="([a-z]+)"/', $bhp_cpp_band_html, $bhp_cpp_all ) ) {
		foreach ( $bhp_cpp_all[1] as $bhp_cpp_f ) {
			$bhp_cpp_toggle_formats[ $bhp_cpp_f ] = true;
		}
	}
}

/*
 * ⭐ §7b — WHAT THE TOGGLE OFFERS ACTUALLY BUYS THE RIGHT THING.
 *
 * §5 proves a correct cart for two formats named by a LITERAL in this file.
 * This proves it for the formats the BAND ACTUALLY RENDERED, driving the exact
 * `complete_<fmt>_smart` action string the toggle writes into the hidden field.
 * If a future edit ships a toggle offering a format the plugin cannot fulfil,
 * §5 stays green and this fails — which is the point of asserting the rendered
 * value rather than the expected one.
 *
 * ⛔ NO ORDER IS CREATED. A cart is built in this CLI session, asserted, and
 *    emptied. No product, price, coupon, stock, shipping or checkout setting is
 *    written by any line below.
 */
if ( ! $bhp_cpp_toggle_formats ) {
	bhp_cpp_assert( false, '§7b no toggle formats were rendered — cannot assert what the band buys', $failures );
} elseif ( ! function_exists( 'WC' ) || ! function_exists( 'bhp_bundle_add_missing_titles_to_cart' ) ) {
	echo "SKIP: §7b needs WooCommerce and the bundle plugin in this process.\n";
} else {
	if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();
	}
	if ( ! WC()->cart ) {
		echo "SKIP: §7b could not load a cart in this CLI session.\n";
	} else {
		$bhp_cpp_catalog = bhp_bundle_catalog();

		foreach ( array_keys( $bhp_cpp_toggle_formats ) as $bhp_cpp_fmt ) {
			$bhp_cpp_action = 'complete_' . $bhp_cpp_fmt . '_smart';

			/*
			 * The plugin's `template_redirect` handler dispatches on exactly
			 * these four strings. Asserting the band's string is one of them is
			 * what proves the POST is not silently ignored — the handler's
			 * `else { return; }` branch would drop it with no notice at all.
			 */
			bhp_cpp_assert(
				in_array( $bhp_cpp_action, array( 'complete_paperback_smart', 'complete_hardcover_smart' ), true ),
				"§7b the band's posted action \"{$bhp_cpp_action}\" is one the plugin handler dispatches",
				$failures
			);
			bhp_cpp_assert(
				! empty( $bhp_cpp_catalog[ $bhp_cpp_fmt ] ),
				"§7b {$bhp_cpp_fmt} — the catalog resolves this toggle format",
				$failures
			);
			if ( empty( $bhp_cpp_catalog[ $bhp_cpp_fmt ] ) ) {
				continue;
			}

			$bhp_cpp_keys = array_keys( $bhp_cpp_catalog[ $bhp_cpp_fmt ] );

			WC()->cart->empty_cart();
			bhp_bundle_add_missing_titles_to_cart( $bhp_cpp_fmt, $bhp_cpp_keys );
			/*
			 * ⭐ RETARGETED 1.19.198 / plugin 1.8.27 (CYCLE144-LD-228) — the
			 *    same correction as §5, for the same reason, and the two are
			 *    kept identical deliberately so the band and the funnel
			 *    cards cannot be asserted to two different standards.
			 *    Superseded assertions, verbatim:
			 *        3 === $bhp_cpp_n,  "§7b {$bhp_cpp_fmt} — the band's CTA puts exactly 3 books in the cart (got {$bhp_cpp_n})"
			 *        3 === $bhp_cpp_n2, "§7b {$bhp_cpp_fmt} — a SECOND band click is a no-op, not six books (got {$bhp_cpp_n2})"
			 */
			$bhp_cpp_n     = (int) WC()->cart->get_cart_contents_count();
			$bhp_cpp_books = 0;
			foreach ( WC()->cart->get_cart() as $bhp_cpp_line ) {
				if ( null !== bhp_bundle_identify_cart_item( $bhp_cpp_line['product_id'], $bhp_cpp_line['variation_id'] ) ) {
					$bhp_cpp_books += (int) $bhp_cpp_line['quantity'];
				}
			}
			bhp_cpp_assert( 3 === $bhp_cpp_books, "§7b {$bhp_cpp_fmt} — the band's CTA puts exactly 3 CATALOG BOOKS in the cart (got {$bhp_cpp_books}; {$bhp_cpp_n} lines total)", $failures );

			// Double-add guard, at the band's own action.
			bhp_bundle_add_missing_titles_to_cart( $bhp_cpp_fmt, $bhp_cpp_keys );
			$bhp_cpp_n2     = (int) WC()->cart->get_cart_contents_count();
			$bhp_cpp_books2 = 0;
			$bhp_cpp_addons2 = 0;
			foreach ( WC()->cart->get_cart() as $bhp_cpp_line ) {
				if ( null !== bhp_bundle_identify_cart_item( $bhp_cpp_line['product_id'], $bhp_cpp_line['variation_id'] ) ) {
					$bhp_cpp_books2 += (int) $bhp_cpp_line['quantity'];
				} elseif ( function_exists( 'bhp_bundle_is_addon_item' )
					&& bhp_bundle_is_addon_item( (int) $bhp_cpp_line['product_id'], (int) $bhp_cpp_line['variation_id'] ) ) {
					$bhp_cpp_addons2 += (int) $bhp_cpp_line['quantity'];
				}
			}
			bhp_cpp_assert( 3 === $bhp_cpp_books2, "§7b {$bhp_cpp_fmt} — a SECOND band click is a no-op, not six books (got {$bhp_cpp_books2} books; {$bhp_cpp_n2} lines total)", $failures );
			bhp_cpp_assert( $bhp_cpp_addons2 <= 1, "§7b {$bhp_cpp_fmt} — ⭐ and the free add-on is not double-added (got {$bhp_cpp_addons2})", $failures );

			WC()->cart->calculate_totals();

			$bhp_cpp_fee = 0.0;
			foreach ( WC()->cart->get_fees() as $bhp_cpp_f ) {
				$bhp_cpp_fee += (float) $bhp_cpp_f->amount;
			}
			$bhp_cpp_expected = (float) bhp_bundle_rules( $bhp_cpp_fmt )[3]['discount'];
			bhp_cpp_assert(
				abs( abs( $bhp_cpp_fee ) - $bhp_cpp_expected ) < 0.005,
				sprintf( '§7b %s — the promised bundle discount ($%0.2f) is applied (%0.2f)', $bhp_cpp_fmt, $bhp_cpp_expected, $bhp_cpp_fee ),
				$failures
			);

			if ( function_exists( 'bhp_bundle_shipping_amount' ) && function_exists( 'bhp_bundle_evaluate_cart' ) ) {
				$bhp_cpp_eval = bhp_bundle_evaluate_cart( WC()->cart );
				$bhp_cpp_ship = (float) bhp_bundle_shipping_amount( $bhp_cpp_eval );
				bhp_cpp_assert(
					abs( $bhp_cpp_ship ) < 0.005,
					sprintf( '§7b %s — the band cart resolves to FREE shipping (got %0.2f)', $bhp_cpp_fmt, $bhp_cpp_ship ),
					$failures
				);
				bhp_cpp_assert(
					! empty( $bhp_cpp_eval['is_complete_collection'] ),
					"§7b {$bhp_cpp_fmt} — the band cart is recognised as a COMPLETE COLLECTION",
					$failures
				);
			}

			WC()->cart->empty_cart();
			bhp_cpp_assert( 0 === (int) WC()->cart->get_cart_contents_count(), "§7b {$bhp_cpp_fmt} — test cart emptied afterwards", $failures );
		}
	}
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ §7c — THE BEST VALUE BAND SITS DIRECTLY UNDER "STORIES THAT TRAVEL"
 *         ADDED 1.19.179, 2026-08-05, CYCLE144-LD-81. §1–§7b ABOVE ARE
 *         BYTE-UNTOUCHED — this section re-fetches /books/ for itself rather
 *         than reaching into §7's loop variables, precisely so it cannot
 *         perturb anything above it.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the Chief
 * of Staff and witnessed by the main session — NOT witnessed first-hand by the
 * agent that wrote this): "Also on the adventure books page the best value box
 * has quite alot of room under the stories that travel section- close that gap
 * and bring up the best value box".
 *
 * The fix is one CSS rule pair in style.css, and it is load-bearing on TWO
 * facts that a future edit can break without touching the CSS at all:
 *
 *   1. THE ADJACENCY. `body:not(.home) .books-series-overview +
 *      .books-complete-collection-banner` only matches while the band is the
 *      IMMEDIATE next sibling of the overview section. Drop anything between
 *      them — a promo strip, a divider, an empty wrapper — and the band
 *      silently reverts to a full 128px top pad and the gap comes back.
 *   2. THE CASCADE ORDER. The rule ties on specificity with nothing, but the
 *      band's OWN `section--sm` request is already being beaten by
 *      `body:not(.home) .section` (0-2-1 vs 0-1-0). The new overview rule is
 *      also 0-2-1, so it wins ONLY because it is declared LATER in the file.
 *      Move it above line ~4174 and it stops applying, with no error anywhere.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN GLOSSED. This asserts DOM STRUCTURE and
 *   STYLESHEET CONTENT. It does NOT measure a pixel. The before/after computed
 *   padding and the rendered gap were measured separately, in headless Chrome
 *   against staging, at 1440px and 390px — a green run here is not that
 *   measurement and must not be reported as one.
 */
echo "\n=== §7c — /books/ · THE BEST VALUE BAND OPENS FLUSH UNDER THE OVERVIEW ===\n";

$bhp_cpp_gap_page = get_page_by_path( 'books' );
$bhp_cpp_gap_url  = $bhp_cpp_gap_page ? get_permalink( $bhp_cpp_gap_page->ID ) : '';
bhp_cpp_assert( '' !== $bhp_cpp_gap_url, '§7c /books/ resolves', $failures );

if ( '' !== $bhp_cpp_gap_url ) {
	$bhp_cpp_gap_res  = wp_remote_get( $bhp_cpp_gap_url, array( 'timeout' => 30, 'sslverify' => false ) );
	$bhp_cpp_gap_html = ( is_wp_error( $bhp_cpp_gap_res ) || 200 !== (int) wp_remote_retrieve_response_code( $bhp_cpp_gap_res ) )
		? ''
		: (string) wp_remote_retrieve_body( $bhp_cpp_gap_res );
	bhp_cpp_assert( '' !== $bhp_cpp_gap_html, '§7c /books/ renders (HTTP 200, non-empty body)', $failures );

	if ( '' !== $bhp_cpp_gap_html ) {
		$bhp_cpp_ov_at   = strpos( $bhp_cpp_gap_html, 'id="series-overview"' );
		$bhp_cpp_band_at = strpos( $bhp_cpp_gap_html, 'id="books-complete-collection-banner"' );

		bhp_cpp_assert( false !== $bhp_cpp_ov_at, '§7c the "Stories That Travel" section renders (#series-overview)', $failures );
		bhp_cpp_assert( false !== $bhp_cpp_band_at, '§7c the Best Value band renders (#books-complete-collection-banner)', $failures );

		if ( false !== $bhp_cpp_ov_at && false !== $bhp_cpp_band_at ) {
			bhp_cpp_assert(
				$bhp_cpp_ov_at < $bhp_cpp_band_at,
				'§7c the band comes AFTER the overview in document order',
				$failures
			);

			// The band's own opening tag, then the overview's outermost close.
			$bhp_cpp_head     = substr( $bhp_cpp_gap_html, 0, $bhp_cpp_band_at );
			$bhp_cpp_tag_at   = strrpos( $bhp_cpp_head, '<section' );
			$bhp_cpp_close_at = false !== $bhp_cpp_tag_at
				? strrpos( substr( $bhp_cpp_head, 0, $bhp_cpp_tag_at ), '</section>' )
				: false;

			bhp_cpp_assert( false !== $bhp_cpp_tag_at, '§7c the band is a <section> element', $failures );
			bhp_cpp_assert( false !== $bhp_cpp_close_at, '§7c the overview section closes before the band opens', $failures );

			if ( false !== $bhp_cpp_tag_at && false !== $bhp_cpp_close_at && $bhp_cpp_close_at > $bhp_cpp_ov_at ) {
				$bhp_cpp_between = substr(
					$bhp_cpp_gap_html,
					$bhp_cpp_close_at + strlen( '</section>' ),
					$bhp_cpp_tag_at - $bhp_cpp_close_at - strlen( '</section>' )
				);
				// Comments and whitespace are NOT elements: the CSS "+" combinator
				// steps over both, so both are legitimately allowed here.
				$bhp_cpp_between_clean = trim( preg_replace( '/<!--.*?-->/s', '', $bhp_cpp_between ) );
				bhp_cpp_assert(
					'' === $bhp_cpp_between_clean,
					'§7c the band is the IMMEDIATE next sibling of the overview — nothing between them '
						. '(the "+" selector that closes the gap depends on it); found: '
						. ( '' === $bhp_cpp_between_clean ? 'nothing' : substr( $bhp_cpp_between_clean, 0, 120 ) ),
					$failures
				);
			}

			// The class the rule targets must actually be on the band.
			$bhp_cpp_band_tag = substr( $bhp_cpp_gap_html, (int) $bhp_cpp_tag_at, ( $bhp_cpp_band_at - (int) $bhp_cpp_tag_at ) + 400 );
			bhp_cpp_assert(
				false !== strpos( $bhp_cpp_band_tag, 'books-complete-collection-banner' ),
				'§7c the band carries the class the spacing rule targets',
				$failures
			);
			bhp_cpp_assert(
				false !== strpos( $bhp_cpp_gap_html, 'books-series-overview' ),
				'§7c the overview carries the class the spacing rule targets',
				$failures
			);
		}
	}
}

/*
 * The stylesheet half. Read from the SHIPPED artefact on disk, which is what
 * `wp theme install --force` actually deployed.
 */
$bhp_cpp_css_path = get_template_directory() . '/style.css';
$bhp_cpp_css      = is_readable( $bhp_cpp_css_path ) ? (string) file_get_contents( $bhp_cpp_css_path ) : '';
bhp_cpp_assert( '' !== $bhp_cpp_css, '§7c the shipped style.css is readable', $failures );

if ( '' !== $bhp_cpp_css ) {
	$bhp_cpp_rule_ov   = strpos( $bhp_cpp_css, 'body:not(.home) .books-series-overview { padding-block-end:' );
	$bhp_cpp_rule_band = strpos( $bhp_cpp_css, 'body:not(.home) .books-series-overview + .books-complete-collection-banner { padding-block-start: 0; }' );
	$bhp_cpp_rule_base = strpos( $bhp_cpp_css, 'body:not(.home) .section { padding-block: var(--section-space); }' );

	bhp_cpp_assert( false !== $bhp_cpp_rule_ov, '§7c the overview closing-gap rule is present', $failures );
	bhp_cpp_assert( false !== $bhp_cpp_rule_band, '§7c the band flush-top rule is present, and uses the "+" combinator', $failures );
	bhp_cpp_assert( false !== $bhp_cpp_rule_base, '§7c the generic body:not(.home) .section rule is still present (it was not deleted)', $failures );

	if ( false !== $bhp_cpp_rule_ov && false !== $bhp_cpp_rule_band && false !== $bhp_cpp_rule_base ) {
		bhp_cpp_assert(
			$bhp_cpp_rule_ov > $bhp_cpp_rule_base && $bhp_cpp_rule_band > $bhp_cpp_rule_base,
			'§7c BOTH new rules are declared AFTER body:not(.home) .section — they tie on specificity and win only on order',
			$failures
		);
	}

	// No negative margin was used to fake the fix. The brief forbade it and the
	// grep is cheap, so the ban is asserted rather than trusted.
	bhp_cpp_assert(
		! preg_match( '/\.books-(series-overview|complete-collection-banner)[^{}]*\{[^{}]*margin[^:]*:\s*-/', $bhp_cpp_css ),
		'§7c the gap is closed by padding, NOT by a negative margin pulling the band up',
		$failures
	);
}

echo "\n";
if ( $failures ) {
	echo 'FAILED (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "ALL COLLECTION PURCHASE PATH ASSERTIONS PASSED\n";
