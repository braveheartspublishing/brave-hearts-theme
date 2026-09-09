<?php
/**
 * CYCLE179-CX-BUILD-405 — theme 1.19.405.
 *
 * 1.19.405 is a SIX-SURFACE build plus one deliberate no-op, and this suite
 * asserts the parts of it that could silently regress without anyone noticing
 * on a screenshot.
 *
 *   1. THE PDP CTA CARRIES THE LIVE PRICE, READ FROM THE PRODUCT. The label is
 *      built from `WC_Product::get_price()`, never typed. The assertion below
 *      reads the SAME live product the template reads, so it cannot pass
 *      against a stale literal — and it fails if anyone hardcodes today's
 *      $11.99 back into the template.
 *   2. THE CART'S EMPTY WALLET FRAME COLLAPSES. BH-08 now runs on `is_cart()`
 *      as well as `is_checkout()`, and its observer knows the cart block roots.
 *   3. THE HOME PARENT POPUP FIRES ON ENGAGEMENT, NOT TIME. Asserted in
 *      `tests/test-popup-ab.php`, which owns that surface — NOT duplicated
 *      here. This suite asserts only the thing that file cannot see: that the
 *      engine addition is INERT for every other popup.
 *   4. THE COLOURING PDP GETS THE HERO GALLERY. One resolver decides, and both
 *      the swap gate and the media builder ask it.
 *   5. HOVER ZOOM IS OFF; LIGHTBOX AND FLIP-THROUGH ARE ON.
 *   6. THE PAIR STRIP CARD TAKES THE GRID CARD GEOMETRY, and 1.19.399's two
 *      anchors still work inside it.
 *   7. THE DRAWER NUDGE IS UNCHANGED (seal 1359). ASSERTED, NOT ASSUMED — a
 *      no-op that nobody checks is just an untested claim.
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, stated so a PASS is not over-read. This is
 *    PHP. It reads declarations, source and rendered markup; it lays nothing
 *    out. It cannot prove a cover painted, that the strip card and the grid
 *    card MEASURE the same, that a wallet frame collapsed to 0px, or that a
 *    popup opened at 50% depth. Those are browser claims, and they are recorded
 *    separately in the build report with geometry measured at an asserted
 *    `window.innerWidth`.
 *
 * It touches no post, no product, no option, no coupon and no WooCommerce
 * record. It is read-only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_c405_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_c405_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Source with EVERY comment removed.
 *
 * ⛔ NOT FASTIDIOUSNESS — it is the only way the ABSENCE assertions mean
 *    anything. This release's docblocks explain at length what they removed,
 *    and to do that they NAME the removed things in prose: `wc-product-gallery-
 *    zoom`, `'delay' =>`, the old 110px cover well. `strpos()` cannot tell an
 *    explanation from a use, so an absence assertion run against raw source
 *    would fail on the very sentence documenting the removal. The 404 suite
 *    records the same reasoning; this is the same helper.
 */
function bhp_c405_code( $src ) {
	if ( '' === $src || ! function_exists( 'token_get_all' ) ) {
		return $src;
	}
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

/** CSS/JS block comments, which the PHP tokenizer hands back as inline HTML. */
function bhp_c405_strip_block_comments( $src ) {
	return (string) preg_replace( '!/\*.*?\*/!s', '', $src );
}

echo "=== CYCLE179-CX-BUILD-405 — theme 1.19.405 ===\n\n";

/* ══════════════════════════════════════════════════════════════════════════
   1 · THE PDP CTA CARRIES THE LIVE PRICE
   ══════════════════════════════════════════════════════════════════════════ */
echo "-- 1: PDP sticky-bar CTA carries the live paperback price --\n";

$fc_src  = bhp_c405_read( 'template-parts/commerce/format-cards.php' );
$fc_code = bhp_c405_code( $fc_src );

bhp_c405_assert(
	'' !== $fc_src,
	'1.0: format-cards.php is readable (guards every assertion below against a vacuous pass)',
	$failures
);

/* ⛔ THE PRICE MUST COME FROM THE PRODUCT ARRAY, NOT FROM A STRING. */
bhp_c405_assert(
	false !== strpos( $fc_code, "\$data['paperback']['price']" )
		&& false !== strpos( $fc_code, 'wc_price(' ),
	'1.1: the paperback CTA label is built from $data[paperback][price] through wc_price(), i.e. read from the product',
	$failures
);

/* ⛔⛔ THE ASSERTION THAT ACTUALLY PROTECTS THE CUSTOMER. A price literal in
 *    this template is a number that goes stale silently the first time Andrew
 *    reprices, and the customer would be shown one figure and charged another.
 *    ⭐ Comments are stripped first, so the docblock that DISCUSSES $11.99 by
 *      name does not trip this. That is exactly why bhp_c405_code() exists. */
bhp_c405_assert(
	0 === preg_match( '/\$\d+\.\d\d/', $fc_code ),
	'1.2: NO price literal anywhere in the template code — today\'s $11.99 is not hardcoded',
	$failures
);

/* The fallback: a missing price degrades to the working button, never to a
 * broken sentence like "ADD PAPERBACK, ". */
bhp_c405_assert(
	false !== strpos( $fc_code, "ADD PAPERBACK TO CART" ),
	'1.3: the old label survives as the empty-price FALLBACK, so a product with no price still renders a usable button',
	$failures
);

bhp_c405_assert(
	false !== strpos( $fc_code, 'ADD PAPERBACK, %s' ),
	'1.4: the priced label is a sprintf template, so the number is substituted rather than concatenated into a translated string',
	$failures
);

/* ⭐ THE LIVE HALF. Read the SAME product the template reads and confirm the
 * label the customer sees carries the price the store would charge. This is
 * the assertion that cannot pass against a stale literal. */
if ( function_exists( 'bhp_book_purchase_data' ) && function_exists( 'wc_price' ) ) {
	/* ⛔ `mariana_trench`, NOT `mariana`. The registry keys are
	 * mariana_trench / mount_everest / amazon_rainforest; `mariana` is the
	 * COLOURING slug and belongs to a different map entirely. This assertion
	 * silently SKIPPED on its first staging run because of that confusion —
	 * recorded because the two namespaces look interchangeable and are not. */
	$c405_live = bhp_book_purchase_data( 'mariana_trench' );
	if ( is_array( $c405_live ) && isset( $c405_live['paperback']['price'] ) && '' !== $c405_live['paperback']['price'] ) {
		$c405_plain = trim( html_entity_decode( wp_strip_all_tags( wc_price( (float) $c405_live['paperback']['price'] ) ), ENT_QUOTES, 'UTF-8' ) );
		echo "NOTE: live paperback price reads {$c405_plain} from the product object.\n";
		bhp_c405_assert(
			'' !== $c405_plain && false !== strpos( $c405_plain, '.' ),
			"1.5: the live product yields a formatted price ({$c405_plain}) for the CTA label",
			$failures
		);
		/* ⛔⛔ THE ASSERTION THIS BUILD EARNED THE HARD WAY. wc_price() returns
		 * the currency symbol as the HTML entity `&#36;`; wp_strip_all_tags()
		 * removes tags but NOT entities, and esc_html() at the point of use
		 * then escapes the ampersand — so the button would read
		 * "ADD PAPERBACK, &#36;11.99" as literal text. Caught on this build's
		 * first staging run by the NOTE line above, before any browser check. */
		bhp_c405_assert(
			false === strpos( $c405_plain, '&#' ) && false === strpos( $c405_plain, '&amp;' ),
			'1.6: the formatted price carries NO undecoded HTML entity — the button cannot print "&#36;" as literal text',
			$failures
		);
		/* And the template must be doing the same decode, not just the test. */
		bhp_c405_assert(
			false !== strpos( $fc_code, 'html_entity_decode' ),
			'1.7: the template itself decodes the entity before the label is built',
			$failures
		);
	} else {
		echo "NOTE: no live paperback price on this environment — 1.5 skipped, NOT passed.\n";
	}
} else {
	echo "NOTE: WooCommerce/book helpers unavailable — 1.5 skipped, NOT passed.\n";
}

/* ══════════════════════════════════════════════════════════════════════════
   2 · THE CART'S EMPTY WALLET FRAME COLLAPSES
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n-- 2: BH-08 express collapse also runs on /cart/ --\n";

$ar_src  = bhp_c405_read( 'inc/audit-remediation.php' );
$ar_code = bhp_c405_strip_block_comments( bhp_c405_code( $ar_src ) );

bhp_c405_assert(
	'' !== $ar_src,
	'2.0: audit-remediation.php is readable',
	$failures
);

bhp_c405_assert(
	false !== strpos( $ar_code, 'is_cart()' ) && false !== strpos( $ar_code, 'is_checkout()' ),
	'2.1: the BH-08 gate names BOTH is_cart() and is_checkout() — the cart is in scope, the checkout did not lose it',
	$failures
);

/* ⛔ BOTH CONDITIONALS GUARDED. A theme file that runs with WooCommerce
 * deactivated must fatal on neither. */
bhp_c405_assert(
	false !== strpos( $ar_code, "function_exists( 'is_cart' )" ),
	'2.2: is_cart() is called behind its own function_exists() guard, not assumed to exist because is_checkout() does',
	$failures
);

bhp_c405_assert(
	false !== strpos( $ar_code, '.wp-block-woocommerce-cart' )
		&& false !== strpos( $ar_code, '.wp-block-woocommerce-checkout' ),
	'2.3: the MutationObserver root list carries the cart block roots as well as the checkout ones, so the cart is scoped rather than falling through to document.body',
	$failures
);

/* ⛔ THE SAFETY PROPERTIES MUST HAVE SURVIVED THE SCOPE EXTENSION. The failure
 * mode of this function must never be "wallet hidden", and a naive edit here
 * wedged headless Chrome twice. */
bhp_c405_assert(
	false !== strpos( $ar_code, 'everHadWallet' )
		&& false !== strpos( $ar_code, 'GRACE_MS' )
		&& false !== strpos( $ar_code, 'requestAnimationFrame' ),
	'2.4: the one-way everHadWallet latch, the grace window and the rAF coalescing all survived the scope extension',
	$failures
);

/* ══════════════════════════════════════════════════════════════════════════
   3 · THE POPUP ENGINE ADDITION IS INERT FOR EVERY OTHER POPUP
       (the trigger itself is asserted in tests/test-popup-ab.php, which owns
        that surface — this is the half that file cannot see)
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n-- 3: the exit-intent engine addition is inert elsewhere --\n";

$eng = bhp_c405_strip_block_comments( bhp_c405_read( 'assets/js/mariana-popup.js' ) );

bhp_c405_assert(
	'' !== $eng,
	'3.0: the popup engine is readable',
	$failures
);

/* ⛔⛔ THE DEFAULT IS THE WHOLE SAFETY ARGUMENT. `exitFloorElapsed` starts
 *    TRUE, so modes `exit` and `gated` — and any simple-mode popup that did not
 *    opt in — reach exitBlocked() exactly as they did in 1.19.404. If this is
 *    ever flipped, every other popup on the site silently gains a dwell floor
 *    it was never configured for. */
bhp_c405_assert(
	1 === preg_match( '/var exitFloorElapsed = true;/', $eng ),
	'3.1: exitFloorElapsed defaults TRUE — the new floor is a no-op for every popup that did not opt in',
	$failures
);

bhp_c405_assert(
	1 === preg_match( '/if \(deviceConfig\.exitIntent === true\) \{/', $eng ),
	'3.2: exit listeners attach in simple mode only behind the explicit exitIntent opt-in',
	$failures
);

/* The floor timer must be cleared with the others, or a popup that opened on
 * scroll would leave a stray timeout running for the life of the page. */
bhp_c405_assert(
	1 === preg_match( '/if \(exitFloorTimerId\) \{\s*clearTimeout\(exitFloorTimerId\);/', $eng ),
	'3.3: the exit floor timer is cleared in cleanupTriggers() alongside the others — no stray timeout survives a trigger',
	$failures
);

/* ⛔ THE THREE ORIGINAL MODES MUST ALL STILL EXIST. This release added a key,
 * it did not remove a mode, and three other popups depend on them. */
bhp_c405_assert(
	1 === preg_match( "/mode === \x27simple\x27/", $eng )
		&& 1 === preg_match( "/mode === \x27exit\x27/", $eng )
		&& 1 === preg_match( "/mode = \x27gated\x27;/", $eng ),
	'3.4: all three original trigger modes survive — simple, exit and the gated default',
	$failures
);

/* ══════════════════════════════════════════════════════════════════════════
   4 · THE COLOURING PDP GETS THE HERO GALLERY
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n-- 4: the colouring PDP takes the book PDP hero gallery --\n";

$bf_code = bhp_c405_code( bhp_c405_read( 'inc/book-formats.php' ) );

bhp_c405_assert(
	function_exists( 'bhp_book_hero_key_for_product' ),
	'4.1: bhp_book_hero_key_for_product() exists — one resolver decides which key renders the hero',
	$failures
);

/* ⛔⛔ ONE RESOLVER, TWO CALLERS. The gate that REMOVES the native gallery and
 *    the builder that FILLS the replacement must agree about which key is in
 *    play. If they ever disagree, the symptom is a removed native gallery with
 *    nothing rendered in its place — a blank product page. */
/* Three occurrences: the definition, the swap gate's call, the media
 * builder's call. Fewer than three means one of the two callers has been
 * given its own copy of the branch — which is the drift this resolver exists
 * to prevent. */
bhp_c405_assert(
	3 <= substr_count( $bf_code, 'bhp_book_hero_key_for_product(' ),
	'4.2: BOTH the swap gate and the media builder call the same resolver, so they cannot disagree and blank the page',
	$failures
);

/* ⛔ AND THE BUILDER MUST NOT STILL RESOLVE ITS OWN KEY. If the old
 * registry-only lookup survived inside the media builder, the colouring PDP
 * would pass the gate and then render nothing. Asserted on the builder's own
 * body, not on the whole file — bhp_book_lookup_product() is still legitimately
 * used elsewhere and must not be flagged there. */
$c405_builder = (string) strstr( $bf_code, 'function bhp_book_hero_gallery_media' );
$c405_builder = (string) substr( $c405_builder, 0, (int) strpos( $c405_builder . "\n}\n", "\n}\n" ) );
bhp_c405_assert(
	'' !== $c405_builder && false === strpos( $c405_builder, 'bhp_book_lookup_product' ),
	'4.3: the media builder no longer resolves its own key — it asks the shared resolver, so gate and builder cannot diverge',
	$failures
);

/* ⛔ ID-BASED, NEVER A TITLE SUBSTRING. CYCLE165-OPS-019 was exactly that bug,
 * and it put a colouring cover beside a chapter-book price. */
bhp_c405_assert(
	false !== strpos( $bf_code, 'bhp_colouring_slug_for_product(' ),
	'4.4: the colouring branch resolves by product ID via bhp_colouring_slug_for_product(), not by matching a title',
	$failures
);

/* ⭐ THE LIVE HALF: the colouring media the hero will render actually exists.
 * Approved media that was authored and sitting unused is what this item makes
 * visible; it adds no image and approves nothing. */
/* ⚠️⚠️ THIS ASSERTION FAILED ON ITS FIRST STAGING RUN AND THE FAILURE WAS
 *    REAL — it is the reason the cover-only path exists.
 *
 * ⛔⛔ BUT THE REASON RECORDED HERE FOR THAT FAILURE WAS FALSE, and it is
 *    corrected 2026-09-08 by `CYCLE179-LD-BUILD-409` (the one-line fix
 *    `CYCLE179-CX-BUILD-406` raised as its open question 4 and could not make
 *    from its own lane). Superseded sentence preserved STRUCK, at the line:
 *
 *    ~~The two colouring spreads named in the media registry
 *      (`look-inside-mariana-coloring-book-pp95-101` and `-pp99-109`) ARE NOT
 *      UPLOADED, on staging or production.~~
 *
 * ⚠️ WHY IT WAS FALSE. Those two stems name THEME FILES in
 *    `bhp_pdp_look_inside_registry()`, and 406 established first-hand that
 *    they were present in the artefact, present on both servers and serving
 *    HTTP 200 the whole time. They were never in the MEDIA registry at all.
 *    The real cause was a name collision: `bhp_book_media_registry()` had no
 *    `colouring_mariana` key, so `bhp_book_media()` returned zero items.
 *    ⛔ THE FALSE SENTENCE MATTERED because it sent the next build looking
 *    for an upload, which could not have fixed anything.
 *
 * ⭐ AND AS OF 1.19.409 EVEN THE CORRECTED GAP IS CLOSED: that registry now
 *    carries the six media-library interior pages, so
 *    `bhp_book_has_look_inside('colouring_mariana')` returns TRUE like the
 *    three chapter books. The assertion below is unchanged and still passes,
 *    now by the spreads route rather than the cover-only route — which is
 *    exactly what it was re-aimed to allow.
 *
 * ⛔ HISTORICAL, AND TRUE WHEN WRITTEN: `bhp_book_has_look_inside(
 *    'colouring_mariana')` returned FALSE while all three chapter books
 *    returned TRUE.
 * ⭐ SO THE ASSERTION WAS RE-AIMED AT WHAT MUST ACTUALLY BE TRUE: the colouring
 *    PDP gets a hero gallery with SOMETHING REAL IN IT — the spreads if they
 *    are there, the cover alone if they are not. It is NOT weakened to "pass
 *    anyway": if the product has neither, this fails, and it should.
 * ⛔ THE ASSET GAP IS REPORTED, NOT PAPERED OVER. Uploading the two spreads is
 *    an owner-assigned item in the build report; when they land, `4.5b` below
 *    flips from cover-only to the full rail with no code change. */
if ( function_exists( 'bhp_book_hero_key_for_product' ) && function_exists( 'bhp_colouring_product_ids' ) ) {
	$c405_ids = (array) bhp_colouring_product_ids();
	$c405_pid = (int) reset( $c405_ids );
	bhp_c405_assert(
		$c405_pid > 0 && '' !== bhp_book_hero_key_for_product( $c405_pid ),
		'4.5: the colouring product resolves to a hero key, so its PDP takes the book hero frame rather than the native gallery',
		$failures
	);

	$c405_hero = $c405_pid ? bhp_book_hero_gallery_media( $c405_pid ) : null;
	bhp_c405_assert(
		is_array( $c405_hero ) && ! empty( $c405_hero['has_any'] ) && ! empty( $c405_hero['items'] ),
		'4.5b: the colouring hero resolves to a non-empty item list — the gate can never strip the native gallery and then render nothing',
		$failures
	);

	if ( is_array( $c405_hero ) && ! empty( $c405_hero['items'] ) ) {
		$c405_first = $c405_hero['items'][0];
		bhp_c405_assert(
			isset( $c405_first['id'] ) && (int) $c405_first['id'] === (int) get_post_thumbnail_id( $c405_pid ),
			'4.5c: COVER FIRST — slide one of the colouring hero is the product\'s own featured image',
			$failures
		);
		$c405_n = count( $c405_hero['items'] );
		echo "NOTE: the colouring hero holds {$c405_n} item(s). 1 = cover only, the two look-inside spreads are NOT uploaded (asset gap, reported).\n";
	}
} else {
	echo "NOTE: colouring helpers unavailable — 4.5 skipped, NOT passed.\n";
}

/* ⭐ COVER FIRST. The product's own featured image is prepended to the item
 * list, so the gallery opens on the cover rather than on an interior spread. */
bhp_c405_assert(
	false !== strpos( $bf_code, 'array_unshift($media[\'items\']' )
		|| false !== strpos( $bf_code, "array_unshift(\$media['items']" ),
	'4.6: the featured image is still PREPENDED — the gallery opens on the cover, first slide',
	$failures
);

/* ⭐ UNCROPPED. The shared gallery stage contains rather than crops. */
$bm_css = bhp_c405_strip_block_comments( bhp_c405_read( 'assets/css/book-media.css' ) );
bhp_c405_assert(
	1 === preg_match( '/\.bhp-gallery__img[^{]*\{[^}]*object-fit:\s*contain/s', $bm_css ),
	'4.7: the gallery slide is object-fit: contain — the cover is letterboxed, never cropped',
	$failures
);

/* ══════════════════════════════════════════════════════════════════════════
   5 · HOVER ZOOM OFF, LIGHTBOX AND FLIP-THROUGH KEPT
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n-- 5: WooCommerce gallery zoom disabled, lightbox and slider kept --\n";

$fn_code = bhp_c405_code( bhp_c405_read( 'functions.php' ) );

bhp_c405_assert(
	false === strpos( $fn_code, 'wc-product-gallery-zoom' ),
	'5.1: wc-product-gallery-zoom is NOT declared anywhere in functions.php code',
	$failures
);

/* ⛔ THE OTHER TWO ARE SEPARATE FEATURES AND MUST SURVIVE. WooCommerce gates
 * each of the three on its own current_theme_supports() check, so removing one
 * is a removal of one script, not of the gallery. */
bhp_c405_assert(
	false !== strpos( $fn_code, "add_theme_support('wc-product-gallery-lightbox')" ),
	'5.2: the lightbox support is KEPT — tap-to-enlarge still works',
	$failures
);

bhp_c405_assert(
	false !== strpos( $fn_code, "add_theme_support('wc-product-gallery-slider')" ),
	'5.3: the slider support is KEPT — the flip-through still works',
	$failures
);

/* ⭐ AND THE LIVE HALF, because a declaration is not the runtime state. */
if ( function_exists( 'current_theme_supports' ) ) {
	bhp_c405_assert(
		! current_theme_supports( 'wc-product-gallery-zoom' ),
		'5.4: LIVE — the running theme does not support wc-product-gallery-zoom',
		$failures
	);
	bhp_c405_assert(
		current_theme_supports( 'wc-product-gallery-lightbox' )
			&& current_theme_supports( 'wc-product-gallery-slider' ),
		'5.5: LIVE — the running theme still supports the lightbox and the slider',
		$failures
	);
}

/* ══════════════════════════════════════════════════════════════════════════
   6 · THE PAIR STRIP CARD TAKES THE GRID CARD GEOMETRY
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n-- 6: the pair strip card mirrors the grid card, with the 399 links intact --\n";

$css = bhp_c405_strip_block_comments( bhp_c405_read( 'style.css' ) );

/* ⛔⛔ THE DEFECT THAT WAS REMOVED, ASSERTED AS AN ABSENCE. A fixed cover
 *    height in the strip block is exactly what made the pair card a smaller,
 *    second-class card sitting under a grid of larger ones — and it is what
 *    will silently diverge the next time the grid's well is re-measured. */
bhp_c405_assert(
	0 === preg_match( '/bhp-catalog-bundle-strip__list li\.product img[^{]*\{[^}]*height:\s*\d+px/s', $css ),
	'6.1: the strip no longer pins its own fixed cover height — the grid\'s --bhp-cover-well governs',
	$failures
);

bhp_c405_assert(
	0 === preg_match( '/bhp-catalog-bundle-strip__list li\.product[^{]*\{[^}]*font-size/s', $css ),
	'6.2: the strip declares no type sizes of its own — title and descriptor take the grid card\'s',
	$failures
);

/* ⭐ THE MECHANISM, ASSERTED SO THE ABSENCES ABOVE ARE NOT MERELY ABSENCES.
 * The grid rules match these cards only because the strip's <ul> carries
 * `products` and sits inside .woo-expedition-shell. If the markup ever stops
 * doing that, the absences above would leave the card with NO geometry. */
$cl_code = bhp_c405_code( bhp_c405_read( 'inc/colouring-line.php' ) );
bhp_c405_assert(
	false !== strpos( $cl_code, 'class="products bhp-catalog-bundle-strip__list"' ),
	'6.3: the strip list still carries the `products` class — which is WHY the grid card rules reach it',
	$failures
);

/* ⛔ THE 1.19.399 ANCHORS. Item 6 changed the geometry around them, and the
 * brief is explicit that they must keep working. */
bhp_c405_assert(
	false !== strpos( $cl_code, 'bhp-shop-offer-card__image-link' )
		&& false !== strpos( $cl_code, 'bhp-shop-offer-card__title-link' ),
	'6.4: 1.19.399\'s two anchors are still emitted on the pair card',
	$failures
);

/* ⛔⛔ THE ONE THING THE SIMPLIFICATION COULD HAVE BROKEN. The grid's cover
 *    rule targets `li.product img`; on the pair card that <img> is wrapped in
 *    an anchor, which is an INLINE box by default. An inline parent around a
 *    block image is where the height would have been lost. */
bhp_c405_assert(
	1 === preg_match( '/\.bhp-shop-offer-card__image-link\s*\{\s*display:\s*block;\s*\}/s', $css ),
	'6.5: the image link is display:block, so the linked pair cover lays out exactly as every unwrapped grid cover does',
	$failures
);

/* ⚠️ THE SIBLING GUARD THAT 1.19.399 TRIPPED ONCE ALREADY. §6.4b of
 * test-shop-grid-2up-204.php counts the `bhp-shop-offer-item` prefix as though
 * it were a card, so any new `bhp-shop-offer-item__*` class trips it. This
 * release adds none — asserted here so the next desk finds out from a test
 * rather than from a red line in another suite. */
bhp_c405_assert(
	1 === substr_count( $cl_code, 'bhp-shop-offer-item' ),
	'6.6: exactly ONE bhp-shop-offer-item occurrence — the sibling suite\'s prefix-counting guard (§6.4b) is not tripped',
	$failures
);

/* ══════════════════════════════════════════════════════════════════════════
   7 · THE DRAWER NUDGE IS UNCHANGED — ASSERTED, NOT ASSUMED
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n-- 7: the drawer nudge wording is untouched (seal 1359) --\n";

/* ⭐ A NO-OP THAT NOBODY CHECKS IS JUST AN UNTESTED CLAIM. Seal 1359 says the
 * wording "stands", so this release must leave it byte-identical. The string
 * lives in the BUNDLE PLUGIN, not the theme.
 * ⛔ THE PLUGIN IS NOT THIS BUILD'S TO CHANGE and nothing here writes to it —
 *    this reads the shipped copy in the repo and confirms the words are the
 *    approved ones. If the plugin tree is absent, the check is SKIPPED and
 *    reported as skipped, never quietly passed. */
$c405_nudge   = 'Add another paperback and save $1.99.';
$c405_plugins = array(
	'plugins/brave-hearts-bundle-pricing/includes/bundle-cart.php',
	'plugins/brave-hearts-bundle-pricing/includes/bundle-drawer.php',
);
$c405_found = 0;
$c405_seen  = 0;
foreach ( $c405_plugins as $c405_rel ) {
	$c405_src = bhp_c405_read( $c405_rel );
	if ( '' === $c405_src ) {
		continue;
	}
	$c405_seen++;
	if ( false !== strpos( $c405_src, $c405_nudge ) ) {
		$c405_found++;
	}
}
if ( 0 === $c405_seen ) {
	echo "NOTE: the bundle plugin tree is not present here — 7.1 SKIPPED, not passed.\n";
} else {
	bhp_c405_assert(
		$c405_found === $c405_seen,
		"7.1: the approved nudge wording is byte-identical in all {$c405_seen} plugin surface(s) that carry it — seal 1359's no-op is a real no-op",
		$failures
	);
}

/* ⛔ AND THE THEME DID NOT GROW A RIVAL COPY OF IT. Two definitions of one
 * approved string is the drift class this company keeps finding. */
bhp_c405_assert(
	false === strpos( bhp_c405_read( 'style.css' ), $c405_nudge )
		&& false === strpos( $fc_src, $c405_nudge ),
	'7.2: the theme carries no rival copy of the nudge wording — the plugin remains its single owner',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
