<?php
/**
 * Brave Hearts — THE TWO BUNDLES ARE PRODUCT-STYLE CARDS (theme 1.19.284).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-bundle-cards-206-207.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR — `CYCLE165-LD-BUNDLE-CARDS`, CARRIER ITEMS 206 + 207
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ ANDREW SIGNORE, carrier items 206 and 207, 2026-08-21. ⚠️ RELAYED through
 *    `chief-of-staff` (brief item 9) in the build brief — ⛔ NOT witnessed
 *    first-hand by the agent that wrote this file. Recorded as relayed, per
 *    Standing Rules §9.2 rule 2.
 *
 *    ITEM 206: the Mariana pair ($22.99) and the three-paperback Complete
 *    Collection ($31.99) present as PRODUCT-STYLE CARDS in the shop grid —
 *    "image · title · price · direct-buy CTA" — 2-up on mobile, replacing the
 *    "Add both" offer-box rows. ⛔ NO PRODUCT RECORDS: the existing offer
 *    mechanics carry the purchase, so nothing is created in WooCommerce.
 *    ITEM 207: the collection carousel comes OFF /shop/. ⛔ THAT PAGE ONLY.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ THE SIX THINGS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. THE CARD CONTRACT IS FOUR ELEMENTS IN ORDER (§3). "Product-style" is not
 *    a mood — it is image, then title, then price, then CTA, in document
 *    order, because that is the order every other card in the grid uses. A
 *    card carrying all four in the WRONG order reads as a different component
 *    sitting in the same row, which is the defect item 206 was written to fix.
 *
 * 2. ⭐⭐ `FD-549` IS ENFORCED BY CONSTRUCTION AND BY DEGRADATION (§4, §6). The
 *    picture on a bundle card must be a COMPOSITE OF EVERY COMPONENT. A single
 *    component's cover beside a bundle price states that THAT BOOK costs the
 *    bundle price. §4 asserts the composite resolves; §6 forces the resolver
 *    to fail and asserts the card degrades to NO IMAGE rather than falling
 *    back to a cover. ⛔ THE DEGRADE PATH IS TESTED, NOT ASSUMED — it is the
 *    branch that only runs when something is already wrong.
 *
 * 3. ⛔ R2.2 — NO PRICE LITERAL. Every figure on these cards is resolved from
 *    the offer engine / bundle plugin at render. §5 asserts the rendered
 *    figure equals what the engine returns, so a hardcoded "$22.99" that
 *    happened to be correct today would still fail.
 *
 * 4. ⛔ R2.6 — ONE PRICE, ONCE (§5). The card has a labelled price line, so the
 *    CTA must NOT restate the figure. "BOOK + COLORING BOOK / $22.99" plus
 *    "ADD BOTH FOR $22.99" is the same number twice on a 172px tile — the
 *    exact duplicate-price defect already removed from the colouring card.
 *
 * 5. ⛔ NO PRODUCT RECORDS WERE CREATED (§7). His instruction was explicit that
 *    these use the existing offer mechanics. A future "simplification" that
 *    made real WooCommerce products for the bundles would change pricing,
 *    shipping tiers, stock and schema all at once. §7 asserts the store still
 *    holds exactly the approved product set.
 *
 * 6. ITEM 207 IS CROSS-CHECKED HERE TOO (§8), lightly. The carousel's own
 *    suite (`test-shop-collection-carousel.php`) is the authority; this is a
 *    single assertion so that a build which restored the banner cannot pass
 *    THIS suite while failing that one unnoticed.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS. It reads
 *    rendered documents and calls live functions. It proves presence,
 *    absence, order, cardinality and computed values. It CANNOT prove
 *    geometry: that the two cards sit 2-up at 390px, that the composite is not
 *    clipped, that the price line is not cut off by `overflow: hidden`, or
 *    that the console is clean. Those need a real browser with
 *    `window.innerWidth` asserted, and they are NOT claimed here. The browser
 *    evidence for this release is in the QA folder.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

/*
 * ⛔⛔ THE ACCUMULATOR IS A STATIC INSIDE THE FUNCTION, NOT A `global`, AND THAT
 *    IS A CORRECTION RATHER THAN A STYLE CHOICE. The first version of this file
 *    used `global $failures` against a `$failures` declared at file scope.
 *    ⛔ UNDER `wp eval-file` THE FILE'S TOP LEVEL IS NOT THE GLOBAL SCOPE — it
 *    runs inside a WP-CLI method — so the two were DIFFERENT VARIABLES. Every
 *    failure printed "FAIL:" correctly and NONE of them reached the summary:
 *    the first run of this suite reported three real failures and then printed
 *    "ALL CHECKS PASSED".
 *
 * ⭐ CAUGHT BY RUNNING IT AND READING THE OUTPUT, NOT BY REVIEWING IT. A suite
 *    that cannot fail is worse than no suite, because it is trusted. Recorded
 *    here so the pattern is not reintroduced by anyone copying this file as a
 *    template — and note that the other suites in this directory pass
 *    `array &$failures` explicitly for exactly this reason.
 */
function bc_failures( $add = null ) {
	static $failures = array();
	if ( null !== $add ) {
		$failures[] = $add;
	}
	return $failures;
}

function bc_assert( $condition, $label ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	bc_failures( $label );
}

/*
 * Normalise rendered money for comparison. `wc_price()` called from WP-CLI and
 * `wc_price()` rendered through the page's filters do NOT produce the same
 * bytes: the page emits `&#036;` and the CLI call emits `&#36;`, and WooCommerce
 * separates symbol from amount with a NON-BREAKING SPACE. Comparing raw strings
 * therefore fails on a completely correct build — which is exactly what the
 * first run of this suite did. Decode, de-tag, de-nbsp, then compare.
 */
function bc_money( $html ) {
	$txt = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );
	$txt = str_replace( array( "\xc2\xa0", "\xe2\x80\xaf" ), ' ', $txt );
	return trim( preg_replace( '/\s+/', ' ', $txt ) );
}

function bc_get( $url ) {
	$resp = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $resp );
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	fwrite( STDERR, "Cannot continue: WooCommerce is inactive.\n" );
	exit( 1 );
}

echo "\n=== §1 · THE ITEM-206 API EXISTS ===\n";

foreach ( array(
	'bhp_offer_composite_slugs'         => 'the composite slug registry',
	'bhp_offer_composite_attachment_id' => 'the composite resolver',
	'bhp_offer_composite_card_image'    => 'the card-image renderer',
	'bhp_offer_render_module'           => 'the offer module',
	'bhp_offer_shop_cards'              => 'the shop-grid card injector',
	'bhp_offer_price'                   => 'the price engine',
) as $bc_fn => $bc_what ) {
	bc_assert( function_exists( $bc_fn ), "1.1 {$bc_what} exists — {$bc_fn}()" );
}

/*
 * ⭐ THE CARD MODE IS A FOURTH PARAMETER ON THE EXISTING TEMPLATE, NOT A SECOND
 *    TEMPLATE. Asserted by reflection because it is the design decision that
 *    keeps the product page and the shop card from drifting apart: a second
 *    template would be a second place for a protected element to go missing.
 */
if ( function_exists( 'bhp_offer_render_module' ) ) {
	$bc_ref = new ReflectionFunction( 'bhp_offer_render_module' );
	bc_assert(
		4 === $bc_ref->getNumberOfParameters(),
		sprintf( '1.2 ⭐ bhp_offer_render_module() takes the $card mode as a 4th parameter (got %d)', $bc_ref->getNumberOfParameters() )
	);
	$bc_params = $bc_ref->getParameters();
	bc_assert(
		isset( $bc_params[3] ) && 'card' === $bc_params[3]->getName(),
		'1.3 …and it is named $card'
	);
}

echo "\n=== §2 · THE COMPOSITE SLUGS RESOLVE TO REAL ATTACHMENTS ===\n";

/*
 * ⛔ SLUGS, NOT IDS, AND THAT IS THE WHOLE POINT. An attachment id is
 *    environment-local: staging's 4570 is not production's 4570, and a
 *    hardcoded id renders the right picture on staging and a random one — or
 *    nothing — on the live storefront. This suite therefore asserts that the
 *    SLUGS resolve on whatever environment it is run against, which is the
 *    thing that actually has to be true at both ends of a deploy.
 */
$bc_slugs = function_exists( 'bhp_offer_composite_slugs' ) ? bhp_offer_composite_slugs() : array();
bc_assert( ! empty( $bc_slugs ), '2.1 the slug registry is non-empty' );
bc_assert(
	isset( $bc_slugs['mariana_pb_colouring'] ) && isset( $bc_slugs['collection'] ),
	'2.2 both item-206 composites are registered (the pair and the collection)'
);

$bc_ids = array();
foreach ( $bc_slugs as $bc_key => $bc_slug ) {
	$bc_id             = function_exists( 'bhp_offer_composite_attachment_id' ) ? (int) bhp_offer_composite_attachment_id( $bc_key ) : 0;
	$bc_ids[ $bc_key ] = $bc_id;
	bc_assert( $bc_id > 0, "2.3 \"{$bc_key}\" resolves (slug {$bc_slug} -> ID {$bc_id})" );
	if ( $bc_id > 0 ) {
		bc_assert( 'attachment' === get_post_type( $bc_id ), "2.4 \"{$bc_key}\" (ID {$bc_id}) is a real attachment post" );
		bc_assert( '' !== (string) get_attached_file( $bc_id ), "2.5 \"{$bc_key}\" (ID {$bc_id}) has a file on disk" );
		/*
		 * ⭐ THE MASTERS ARE SQUARE ON PURPOSE. This store's
		 *    `woocommerce_thumbnail` is a HARD-CROPPED SQUARE, so a landscape
		 *    master would have its outer books sliced off — which on the
		 *    three-paperback composite would silently turn a collection
		 *    picture into a two-book picture beside a three-book price.
		 *    ⛔ THAT IS AN FD-549 FAILURE WITH NO VISIBLE ERROR, so the aspect
		 *    ratio is asserted rather than trusted to the build script.
		 */
		$bc_meta = wp_get_attachment_metadata( $bc_id );
		if ( ! empty( $bc_meta['width'] ) && ! empty( $bc_meta['height'] ) ) {
			bc_assert(
				(int) $bc_meta['width'] === (int) $bc_meta['height'],
				sprintf( '2.6 ⭐ "%s" master is SQUARE (%dx%d) — a hard-cropped thumbnail cannot slice a component off', $bc_key, $bc_meta['width'], $bc_meta['height'] )
			);
		}
	}
}

echo "\n=== §3 · THE CARD CONTRACT — image · title · price · CTA, IN ORDER ===\n";

$bc_shop_url  = wc_get_page_permalink( 'shop' );
$bc_shop_html = bc_get( $bc_shop_url );
bc_assert( '' !== $bc_shop_html, "3.1 the shop archive returns HTTP 200 — {$bc_shop_url}" );

if ( '' === $bc_shop_html ) {
	fwrite( STDERR, "Cannot continue: the shop archive did not render.\n" );
	exit( 1 );
}

/*
 * Each card is isolated to its own `<li>` before anything is asserted about it.
 * Asserting needles against the WHOLE document would let the pair card's price
 * satisfy a check meant for the collection card — the two sit in the same grid
 * and share most of their class names.
 */
function bc_isolate_li( $html, $class ) {
	if ( preg_match( '#<li[^>]*\bclass="[^"]*' . preg_quote( $class, '#' ) . '[^"]*"[^>]*>(.*?)</li>#s', $html, $m ) ) {
		return $m[0];
	}
	return '';
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THIS SUITE IS FD-598-AWARE, AND IT HAS TO BE TO RUN ON PRODUCTION.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE PAIR OFFER IS "MARIANA PAPERBACK + MARIANA COLOURING BOOK", so it is
 *    purchasable only where BOTH components exist. ⭐ PRODUCTION CARRIES NO
 *    COLOURING PRODUCT RECORD — that is FD-598, Andrew's standing ruling that
 *    no colouring product reaches production until Bookvault has the title and
 *    the order routing recognises the SKU.
 *
 * ⛔⛔ SO ON PRODUCTION THE PAIR CARD MUST BE **ABSENT**, AND THAT IS THE
 *    CONTRACT WORKING, NOT A DEFECT. `R1.4` — nothing is advertised that cannot
 *    be bought. A suite that demanded the card unconditionally would report
 *    correct production behaviour as six failures, and the third time that
 *    happens someone "fixes" it by weakening the assertion.
 *
 * ⭐ SO THE SUITE ASSERTS THE RULE RATHER THAN THE OUTCOME: purchasable means
 *    the card must render AND be correct; not purchasable means the card must
 *    NOT render at all. Both directions are gated. Neither is skipped silently.
 */
$bc_pair_live = function_exists( 'bhp_offer_is_purchasable' ) && bhp_offer_is_purchasable( 'mariana_pb_colouring' );
echo 'NOTE: 3.0 the MT pair offer is ' . ( $bc_pair_live ? 'PURCHASABLE' : 'NOT purchasable (FD-598: no colouring product on this environment)' ) . " on this environment\n";

$bc_pair_li_probe = bc_isolate_li( $bc_shop_html, 'bhp-shop-offer-item' );
if ( ! $bc_pair_live ) {
	bc_assert(
		'' === $bc_pair_li_probe,
		'3.1a ⛔ R1.4 — the pair is not purchasable here, so its card is correctly ABSENT from the grid'
	);
}

$bc_cards = array( 'the collection card' => bc_isolate_li( $bc_shop_html, 'bhp-shop-collection-item' ) );
if ( $bc_pair_live ) {
	$bc_cards = array( 'the pair card' => $bc_pair_li_probe ) + $bc_cards;
}

foreach ( $bc_cards as $bc_what => $bc_li ) {
	bc_assert( '' !== $bc_li, "3.2 {$bc_what} renders as an <li> in the grid" );
	if ( '' === $bc_li ) {
		continue;
	}

	/* ⭐ It is a real grid cell, carrying WooCommerce's own `product` class, so
	 *    the grid's `li.product` rules size it exactly like its neighbours. */
	bc_assert(
		(bool) preg_match( '/<li[^>]*\bclass="[^"]*\bproduct\b/', $bc_li ),
		"3.3 {$bc_what} carries the `product` class (the grid sizes it like every other cell)"
	);

	$bc_p_img   = stripos( $bc_li, '<img' );
	$bc_p_title = stripos( $bc_li, '<h2' );
	$bc_p_price = strpos( $bc_li, 'bhp-shop-format-price__amount' );
	if ( false === $bc_p_price ) {
		$bc_p_price = strpos( $bc_li, 'bhp-shop-collection-card__price' );
	}
	/*
	 * ⭐⭐ 1.19.286 (items 210+211) — THE CTA IS NOW LOCATED BY ITS OWN CLASS,
	 *     NOT BY "the first <button> in the card".
	 *
	 * ⛔ THE SUPERSEDED PROBE, QUOTED SO THE MOVEMENT IS VISIBLE:
	 *        $bc_p_cta = stripos( $bc_li, '<button' );
	 *    It was correct while the primary CTA was the first button in the card.
	 *    ⭐ It stopped being correct when the hardcover swap moved ABOVE the CTA
	 *    so the CTA could be pinned to the card floor — at which point
	 *    "first <button>" silently began measuring the SWAP, and 3.8's ordering
	 *    assertion would have passed while proving nothing about the control the
	 *    founder actually presses.
	 * ⛔ THIS IS THE CLASS OF DEFECT THAT PASSES A SUITE. It is fixed by naming
	 *    the element, not by loosening the check.
	 */
	$bc_p_cta = strpos( $bc_li, 'bhp-shop-atc' );

	bc_assert( false !== $bc_p_img, "3.4 {$bc_what} — ⭐ IMAGE present" );
	bc_assert( false !== $bc_p_title, "3.5 {$bc_what} — TITLE present (<h2>)" );
	bc_assert( false !== $bc_p_price, "3.6 {$bc_what} — PRICE present as its own element" );
	bc_assert( false !== $bc_p_cta, "3.7 {$bc_what} — the uniform ADD TO CART control is present (`bhp-shop-atc`)" );
	bc_assert(
		false !== strpos( $bc_li, 'ADD TO CART' ),
		"3.7a ⭐ {$bc_what} — it carries the one founder label (item 211)"
	);
	/*
	 * ⛔⛔ THE SHOP CARD MUST NOT CARRY THE CHECKOUT-REDIRECT FIELD. Its absence
	 *     IS the mechanism that sends this card to the SIDE PANEL instead of to
	 *     /checkout/ (item 210) — `finishBundleAdd()` and
	 *     `bhp_bundle_handle_add_to_cart()` both branch on it. Asserting the
	 *     absence is asserting the behaviour.
	 * ⛔ IT SAYS NOTHING ABOUT THE PRODUCT PAGE OR THE HEADER, whose forms still
	 *    post the field and still finish on /checkout/ — the founder-walked
	 *    path, unchanged, and covered by their own suites.
	 */
	bc_assert(
		false === strpos( $bc_li, 'name="bhp_bundle_redirect"' ),
		"3.7b ⭐⭐ {$bc_what} — no checkout-redirect field, so ADD TO CART opens the PANEL (item 210)"
	);

	/*
	 * ⛔ THE ORDER IS THE CONTRACT, NOT A NICETY. All four present in the wrong
	 *    sequence reads as a different component wedged into the row, which is
	 *    exactly what item 206 replaced.
	 */
	bc_assert(
		false !== $bc_p_img && false !== $bc_p_title && false !== $bc_p_price && false !== $bc_p_cta
			&& $bc_p_img < $bc_p_title && $bc_p_title < $bc_p_price && $bc_p_price < $bc_p_cta,
		"3.8 ⭐⭐ {$bc_what} — document order is IMAGE -> TITLE -> PRICE -> CTA"
	);

	/*
	 * ⛔ THE CTA IS A REAL POST, NOT A LINK TO A PAGE THAT SELLS. "Direct-buy"
	 *    is the instruction; a card whose button navigated to the product page
	 *    would satisfy every check above and none of the intent.
	 */
	bc_assert(
		false !== strpos( $bc_li, 'name="bhp_bundle_action"' ),
		"3.9 ⭐ {$bc_what} — the CTA posts a bundle action (direct buy, not a link to a page that sells)"
	);
	bc_assert(
		false !== strpos( $bc_li, 'bhp_bundle_nonce' ) || (bool) preg_match( '/name="[^"]*nonce[^"]*"/i', $bc_li ),
		"3.10 {$bc_what} — the purchase form carries a nonce"
	);
}

echo "\n=== §4 · FD-549 — THE PICTURE IS A COMPOSITE, NOT A COMPONENT'S COVER ===\n";

/*
 * ⛔⛔ THE ASSERTION THE WHOLE RAIL EXISTS FOR. The image on a bundle card must
 *    be the registered composite. If a component's cover ever appears there,
 *    the card states that THAT BOOK costs the bundle price — the exact false
 *    claim `FD-549` was written after.
 */
$bc_fd549_targets = array( 'the collection card' => array( 'bhp-shop-collection-item', 'collection' ) );
if ( $bc_pair_live ) {
	$bc_fd549_targets = array( 'the pair card' => array( 'bhp-shop-offer-item', 'mariana_pb_colouring' ) ) + $bc_fd549_targets;
}
foreach ( $bc_fd549_targets as $bc_what => $bc_pair ) {
	list( $bc_class, $bc_key ) = $bc_pair;
	$bc_li = bc_isolate_li( $bc_shop_html, $bc_class );
	if ( '' === $bc_li || empty( $bc_ids[ $bc_key ] ) ) {
		bc_assert( false, "4.1 {$bc_what} — card or composite id unavailable, cannot verify the image identity" );
		continue;
	}
	$bc_src = wp_get_attachment_image_url( $bc_ids[ $bc_key ], 'woocommerce_thumbnail' );
	bc_assert(
		is_string( $bc_src ) && '' !== $bc_src && false !== strpos( $bc_li, basename( $bc_src ) ),
		sprintf( '4.1 ⭐⭐ %s serves the REGISTERED COMPOSITE (ID %d, %s)', $bc_what, $bc_ids[ $bc_key ], is_string( $bc_src ) ? basename( $bc_src ) : '?' )
	);
	bc_assert(
		false !== strpos( $bc_li, 'bhp-offer__composite' ),
		"4.2 {$bc_what} — the image carries the composite class"
	);
}

echo "\n=== §5 · R2.2 (NO LITERAL) AND R2.6 (ONE PRICE, ONCE) ===\n";

/*
 * ⛔ THE FIGURE IS THE ENGINE'S. Asserted by COMPUTING it here and matching the
 *    rendered text, so a hardcoded "$22.99" that happens to be correct today
 *    still fails. That is the difference between a price that is right and a
 *    price that stays right.
 */
/*
 * ⛔ SKIPPED-AND-SAID-SO where the pair is not purchasable (FD-598 environments).
 *    A skip that is announced is a fact; a skip that is silent is a lie.
 */
$bc_pair_li = $bc_pair_live ? bc_isolate_li( $bc_shop_html, 'bhp-shop-offer-item' ) : '';
if ( ! $bc_pair_live ) {
	echo "SKIP: §5 the pair card is not on this environment (FD-598) — R2.2/R2.6 are asserted where it renders\n";
}
if ( '' !== $bc_pair_li && function_exists( 'bhp_offer_price' ) ) {
	$bc_expected     = (float) bhp_offer_price( 'mariana_pb_colouring' );
	$bc_expected_txt = bc_money( wc_price( $bc_expected ) );
	$bc_pair_txt     = bc_money( $bc_pair_li );
	bc_assert(
		false !== strpos( $bc_pair_txt, $bc_expected_txt ),
		"5.1 ⭐ the pair card's figure IS the engine's ({$bc_expected_txt})"
	);

	/*
	 * ⛔ R2.6 — ONE PRICE, ONCE. The labelled price line carries the figure, so
	 *    the CTA must not restate it. Counted on the STRIPPED card text: two
	 *    occurrences of the same amount on a 172px tile is the duplicate-price
	 *    defect already removed from the colouring card.
	 */
	$bc_count = substr_count( $bc_pair_txt, $bc_expected_txt );
	bc_assert(
		1 === $bc_count,
		sprintf( '5.2 ⛔ R2.6 — %s appears EXACTLY ONCE on the pair card (found %d)', $bc_expected_txt, $bc_count )
	);

	/*
	 * ⭐ AND THE CARD CTA IS THE FIGURE-FREE STRING, not the product page's
	 *    "ADD BOTH FOR %s". The product-page label is unchanged and must stay
	 *    unchanged — a string he has already seen keeps its wording — so this
	 *    asserts the card took the new one rather than that the old one moved.
	 */
	if ( preg_match( '#<button[^>]*>(.*?)</button>#s', $bc_pair_li, $bc_btn ) ) {
		$bc_btn_txt = bc_money( $bc_btn[1] );
		bc_assert(
			false === strpos( $bc_btn_txt, $bc_expected_txt ),
			"5.3 ⭐ the card CTA does NOT restate the figure (\"{$bc_btn_txt}\")"
		);
		bc_assert(
			'' !== $bc_btn_txt,
			'5.4 …and it is not empty'
		);
	}

	/*
	 * ⛔ THE PRICE CARRIES A LABEL. An unlabelled figure on a bundle tile is the
	 *    `FD-549` ambiguity the rail exists to close — the neighbouring cards
	 *    say "PAPERBACK $11.99", so this one has to say what its $22.99 buys.
	 */
	bc_assert(
		false !== strpos( $bc_pair_li, 'bhp-shop-format-price__label' ),
		'5.5 ⭐ the pair card price carries a LABEL saying what the figure buys'
	);
}

/* ⛔ AND NO PRICE LITERAL IS TYPED INTO THE SOURCE. Read from the shipped file
 *    rather than trusted: a template that hardcodes a figure passes every
 *    rendered assertion above on the day it is written. */
$bc_src_file = get_template_directory() . '/inc/colouring-line.php';
if ( is_readable( $bc_src_file ) ) {
	$bc_src = (string) file_get_contents( $bc_src_file );
	/* strip block and line comments so a price QUOTED in a note is not a hit */
	$bc_src_code = preg_replace( '#/\*.*?\*/#s', '', $bc_src );
	$bc_src_code = preg_replace( '#//[^\n]*#', '', (string) $bc_src_code );
	bc_assert(
		! preg_match( '/[\'"]\s*\$\s*\d+\.\d{2}/', (string) $bc_src_code )
			&& ! preg_match( '/=>\s*\d+\.\d{2}\s*[,;]/', (string) $bc_src_code ),
		'5.6 ⛔ R2.2 — no price literal in inc/colouring-line.php (comments excluded)'
	);
}

echo "\n=== §6 · ⛔⛔ THE DEGRADE PATH — NO IMAGE, NEVER THE WRONG IMAGE ===\n";

/*
 * ⛔⛔ THE BRANCH THAT ONLY RUNS WHEN SOMETHING IS ALREADY WRONG, WHICH IS
 *    EXACTLY WHY IT IS TESTED RATHER THAN ASSUMED. If a composite fails to
 *    resolve on an environment — a deploy that shipped the code but not the
 *    media, most plausibly production on release day — the card MUST render
 *    with no picture. It must never fall back to a component's cover.
 *
 * ⭐ FORCED HERE BY FILTER, then removed again, so the assertion exercises the
 *    real code path instead of describing it.
 */
if ( function_exists( 'bhp_offer_composite_card_image' ) ) {
	$bc_force_zero = function () {
		return 0;
	};
	add_filter( 'bhp_offer_composite_attachment_id', $bc_force_zero, 99 );

	$bc_degraded = bhp_offer_composite_card_image( 'mariana_pb_colouring' );
	bc_assert(
		'' === $bc_degraded,
		'6.1 ⭐⭐ an unresolved composite renders NOTHING (not a placeholder, not a cover)'
	);

	remove_filter( 'bhp_offer_composite_attachment_id', $bc_force_zero, 99 );

	/* ⭐ AND IT COMES BACK. A degrade test that left the filter attached would
	 *    poison every assertion after it. ⛔ Gated on the composite existing at
	 *    all, so that on an environment where the media has not been registered
	 *    this reports the missing ATTACHMENT (§2) rather than a phantom
	 *    filter-leak here. */
	if ( ! empty( $bc_ids['mariana_pb_colouring'] ) ) {
		bc_assert(
			'' !== bhp_offer_composite_card_image( 'mariana_pb_colouring' ),
			'6.2 …and the composite resolves again once the forced failure is removed'
		);
	} else {
		echo "SKIP: 6.2 the pair composite is not registered on this environment — see §2\n";
	}
}

/*
 * ⛔ THE HARDCOVER UPSELL HAS NO COMPOSITE AND MUST NOT GET ONE. It is a format
 *    swap inside the paperback offer, not its own card, so it never asks for an
 *    image — and the paperback composite would be the WRONG picture for it.
 */
if ( function_exists( 'bhp_offer_composite_attachment_id' ) ) {
	bc_assert(
		0 === (int) bhp_offer_composite_attachment_id( 'mariana_hc_colouring' ),
		'6.3 ⛔ the hardcover upsell has NO composite (it is a format swap, not a card)'
	);
}

echo "\n=== §7 · ⛔ NO PRODUCT RECORDS WERE CREATED ===\n";

/*
 * ⛔ HIS INSTRUCTION WAS EXPLICIT: existing offer mechanics, no product records.
 *    A future "simplification" that created real WooCommerce products for the
 *    two bundles would move pricing, shipping tiers, stock status and schema
 *    all at once, and every one of those is an Andrew gate. The count is
 *    asserted so that change cannot land quietly.
 */
/*
 * ⛔⛔ CORRECTED AFTER THE FIRST RUN. This section originally asserted
 *    `6 === count(published products)` on the strength of the phrase "the six
 *    approved editions" that appears throughout this project's documentation.
 *    ⛔ THAT NUMBER WAS WRONG AND THE ASSERTION FAILED ON A CORRECT BUILD.
 *
 * ⭐ WHAT IS ACTUALLY THERE, READ FROM STAGING RATHER THAN FROM A DOC
 *    (2026-08-21): EIGHT published products. The "six editions" are the three
 *    titles × paperback/hardcover; the phrase never counted the colouring line.
 *    The other two are the Mariana colouring book (12.99) and The Adventure
 *    Activity Book (5.00). ⛔ Both predate this release and neither was created
 *    by it.
 *
 * ⭐⭐ AND THE COUNT IS NO LONGER THE GATE, because a count is the wrong
 *    instrument: it is environment-local, it breaks the day a legitimate new
 *    title publishes, and it would then be "fixed" by bumping the number —
 *    which is precisely how a real bundle product would slip past it. The gate
 *    below is environment-independent and tests the thing that actually
 *    matters.
 */
$bc_products = wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'return' => 'objects' ) );
$bc_n        = is_array( $bc_products ) ? count( $bc_products ) : -1;
echo "NOTE: 7.0 published products on this environment: {$bc_n} (state, not a gate)\n";

/*
 * ⛔⛔ THE REAL GATE. If someone ever creates a WooCommerce product record for
 *    one of these bundles, it will carry the OFFER'S OWN PRICE — that is what
 *    makes it a bundle product rather than a book. So: no published product may
 *    be priced at any figure in the offer catalogue.
 *
 * ⭐ VERIFIED SAFE FROM FALSE POSITIVES BEFORE BEING USED AS A GATE (staging,
 *    2026-08-21): the offer prices are 22.99 / 28.99 / 34.99 / 63.99 / 79.99 and
 *    every published product is 5.00, 11.99, 12.99 or 17.99. ⛔ No collision, so
 *    a hit here means a bundle product was really created, not that a book
 *    happens to cost the same.
 */
$bc_offer_prices = array();
if ( function_exists( 'bhp_offer_catalog' ) ) {
	foreach ( bhp_offer_catalog() as $bc_ok => $bc_od ) {
		if ( isset( $bc_od['price'] ) && is_numeric( $bc_od['price'] ) ) {
			$bc_offer_prices[ (string) number_format( (float) $bc_od['price'], 2, '.', '' ) ] = $bc_ok;
		}
	}
}
$bc_collisions = array();
if ( is_array( $bc_products ) && ! empty( $bc_offer_prices ) ) {
	foreach ( $bc_products as $bc_p ) {
		$bc_pp = (string) number_format( (float) $bc_p->get_price(), 2, '.', '' );
		if ( isset( $bc_offer_prices[ $bc_pp ] ) ) {
			$bc_collisions[] = $bc_p->get_id() . ' (' . $bc_p->get_name() . ') @ ' . $bc_pp;
		}
	}
}
bc_assert(
	! empty( $bc_offer_prices ) && empty( $bc_collisions ),
	'7.1 ⛔⛔ NO published product is priced at an OFFER price — the bundles are still offers, not products'
		. ( $bc_collisions ? ' [' . implode( '; ', $bc_collisions ) . ']' : '' )
);

/*
 * ⭐ AND THE NAME CHECK, kept as a second net. A bundle product created with a
 *    placeholder price would evade the price gate on the day it was made.
 */
foreach ( array( 'bundle', 'complete-collection-bundle', 'mariana-pair', 'book-coloring-book' ) as $bc_bad ) {
	bc_assert( null === get_page_by_path( $bc_bad, OBJECT, 'product' ), "7.2 ⛔ no product record named \"{$bc_bad}\" was created" );
}

/*
 * ⛔ AND THE POSITIVE HALF: the cards must still be driven by the OFFER ENGINE.
 *    If the grid ever stopped resolving them through `bhp_offer_is_purchasable()`
 *    the cards could keep rendering from some other source and every assertion
 *    above would still pass.
 */
if ( function_exists( 'bhp_offer_is_purchasable' ) ) {
	bc_assert(
		true === (bool) bhp_offer_is_purchasable( 'mariana_pb_colouring' ),
		'7.3 ⭐ the pair is purchasable THROUGH THE OFFER ENGINE (no product record involved)'
	);
}

echo "\n=== §8 · ITEM 207 CROSS-CHECK (the carousel suite is the authority) ===\n";

bc_assert(
	0 === substr_count( $bc_shop_html, 'woo-complete-collection-banner' ),
	'8.1 ⛔ the collection carousel banner is absent from /shop/ (item 207)'
);

echo "\n=== RESULT ===\n";
$bc_all = bc_failures();
if ( empty( $bc_all ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $bc_all ) . " FAILURE(S):\n";
foreach ( $bc_all as $bc_f ) {
	echo " - {$bc_f}\n";
}
exit( 1 );
