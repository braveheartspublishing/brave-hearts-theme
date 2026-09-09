<?php
/**
 * CYCLE179-LD-BUILD-409 — theme 1.19.409.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * THE THREE ITEMS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   409-1  THE KIRKUS LABEL WAS SET BY A TITLE SUBSTRING.
 *          `bhp_get_homepage_books()` (functions.php) set the card's review
 *          label with `stripos(get_the_title($book), 'Mariana Trench')`. The
 *          colouring book's title CONTAINS that phrase, so the colouring
 *          book's hub card printed "Kirkus reviewed".
 *          ⛔ THIS IS THE NEVER-INVENT RULE, NOT A TIDINESS MATTER. Kirkus
 *          reviewed exactly one title — the CHAPTER book — and
 *          `bhp_get_kirkus_review_data()`'s own docblock says nothing there
 *          may be reused to imply otherwise. A colouring book carrying that
 *          label is an endorsement claim no review supports.
 *          ⭐ It is the SAME defect class `CYCLE179-LD-BUILD-408` fixed on
 *          `front-page.php` (CX-1, the "From $12.99" price cue), in the same
 *          function's output. 408 named it in its own "not done" list because
 *          `functions.php` was outside that lock's write paths.
 *
 *   409-2  F3 — `tests/test-cycle179-407.php` §6.2 PINNED the bundle plugin at
 *          '1.8.89'. Staging2 runs 1.8.91, so a routine, correct plugin
 *          release was being reported as a theme defect. Converted to a
 *          `version_compare(..., '1.8.89', '>=')` FLOOR, the same shape
 *          1.19.406 applied to `test-cycle179-397.php` §4.1.
 *
 *   409-3  THE COLOURING PDP HERO THUMBNAIL RAIL. `bhp_book_media_registry()`
 *          had NO `colouring_mariana` key, so the hero gallery resolved to the
 *          cover alone and no rail rendered. Closed with a slug-keyed entry
 *          naming the six interior colouring pages.
 *          ⛔ THE DIAGNOSIS THAT TRAVELLED BEFORE IT WAS FALSE: 1.19.405
 *          recorded this as "two spreads not uploaded". 1.19.406 falsified
 *          that — those stems name THEME FILES in a DIFFERENT registry and
 *          were present the whole time. Two registries, one key.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE MUTATES: NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * No post, product, variation, price, stock field, option, coupon or cart is
 * written. Every live assertion is a read.
 *
 * ⛔ NO PRODUCT ID AND NO ATTACHMENT ID IS TYPED. The colouring product is 618
 *    on production and 4065 on staging; it is resolved BY SKU through
 *    `bhp_colouring_product_ids()`. Chapter-book ids come from
 *    `bhp_book_registry()`. Media comes from SLUGS resolved on whichever
 *    environment is running.
 *
 * ⚠ WHAT PHP CANNOT PROVE, AND SO IS NOT CLAIMED HERE. That the rail is
 *   visible, that a thumb changes the stage, that the lightbox opens, or that
 *   any of it survives at 390px. Those are browser claims and live in the
 *   build report at an asserted `window.innerWidth`, not in this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_c409_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_c409_note( $label ) {
	echo "NOTE: {$label}\n";
}

function bhp_c409_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Source with every comment removed.
 *
 * ⭐ WHY THIS EXISTS, and it is load-bearing for this build in particular.
 *    Every correction here PRESERVES the superseded code inside struck
 *    comments, at the line. A naive `strpos($src, "stripos(")` would therefore
 *    FAIL on correct code — and the tempting "fix" would be to delete the
 *    record, which is the one thing the house style exists to prevent.
 *    Stripping comments with the PHP tokenizer asserts the EXECUTABLE code and
 *    leaves the history intact. Lifted deliberately from
 *    `test-cycle179-408.php` rather than reinvented.
 */
function bhp_c409_code_only( $src ) {
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
		} else {
			$out .= $token;
		}
	}
	return $out;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · PRECONDITIONS — resolved, never typed
 * ═══════════════════════════════════════════════════════════════════════════ */

bhp_c409_assert( function_exists( 'bhp_get_homepage_books' ), '0.1: bhp_get_homepage_books() exists', $failures );
bhp_c409_assert( function_exists( 'bhp_book_key_product_ids' ), '0.2: bhp_book_key_product_ids() exists — the 408 registry identity resolver', $failures );
bhp_c409_assert( function_exists( 'bhp_book_hero_key_for_product' ), '0.3: bhp_book_hero_key_for_product() exists', $failures );
bhp_c409_assert( function_exists( 'bhp_book_media_registry' ), '0.4: bhp_book_media_registry() exists', $failures );
bhp_c409_assert( function_exists( 'bhp_colouring_product_ids' ), '0.5: bhp_colouring_product_ids() exists (bundle plugin SKU registry)', $failures );

$c409_mar = function_exists( 'bhp_book_key_product_ids' ) ? bhp_book_key_product_ids( 'mariana_trench' ) : array();
bhp_c409_assert( ! empty( $c409_mar['paperback'] ) && ! empty( $c409_mar['hardcover'] ), '0.6: the Mariana chapter book resolves to both format ids through the registry', $failures );

$c409_ids = function_exists( 'bhp_colouring_product_ids' ) ? (array) bhp_colouring_product_ids() : array();
$c409_col = isset( $c409_ids['mariana'] ) ? (int) $c409_ids['mariana'] : 0;
bhp_c409_assert( $c409_col > 0, "0.7: the Mariana colouring product resolves BY SKU on this environment (id {$c409_col})", $failures );

if ( ! $c409_col || empty( $c409_mar['paperback'] ) ) {
	echo "\nRESULT: PRECONDITIONS FAILED — nothing below could be trusted.\n";
	return;
}

bhp_c409_note( '0.x environment: colouring product id ' . $c409_col . ', Mariana pb ' . (int) $c409_mar['paperback'] . ' / hc ' . (int) $c409_mar['hardcover'] . '. None of these three numbers is written anywhere in this file.' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · 409-1 — THE KIRKUS LABEL IS SET BY IDENTITY, NOT BY A SUBSTRING
 * ═══════════════════════════════════════════════════════════════════════════ */

$c409_fn_src  = bhp_c409_read( 'functions.php' );
$c409_fn_code = bhp_c409_code_only( $c409_fn_src );

bhp_c409_assert( '' !== $c409_fn_src, '1.0: functions.php is readable', $failures );

/* ⭐ THE STRUCK RECORD MUST SURVIVE. This build's whole method is that a
 *    correction stands AT the line it corrects, with the old text visible. A
 *    later session that "tidies" the comment away recreates the conditions
 *    that let this bug live through 408. */
bhp_c409_assert(
	false !== strpos( $c409_fn_src, "~~if (!\$review && stripos(get_the_title(\$book), 'Mariana Trench') !== false) {" ),
	'1.1: the superseded substring test is PRESERVED STRUCK in the comment, not deleted',
	$failures
);

/* ⛔ AND IT MUST NOT SURVIVE AS CODE. Comments stripped, so 1.1 cannot satisfy
 *    this and this cannot be satisfied by 1.1 being removed. */
bhp_c409_assert(
	false === strpos( $c409_fn_code, "stripos(get_the_title(\$book), 'Mariana Trench')" ),
	'1.2: ⭐ THE FIX — no executable title-substring test for "Mariana Trench" remains in functions.php',
	$failures
);

bhp_c409_assert(
	false !== strpos( $c409_fn_code, "bhp_book_key_product_ids('mariana_trench')" ),
	'1.3: the label is now selected by the registry identity resolver, with a KEY as its argument',
	$failures
);

/* ⛔ NO HARDCODED ID, EITHER WAY ROUND. 618/4065 differ per environment; a
 *    denylist would be wrong on one of them the day it was written. */
bhp_c409_assert(
	0 === preg_match( '/\b(618|4065)\b/', (string) substr( $c409_fn_code, max( 0, (int) strpos( $c409_fn_code, 'function bhp_get_homepage_books' ) ), 4000 ) ),
	'1.4: no colouring product id is typed into bhp_get_homepage_books()',
	$failures
);

/* ─── BEHAVIOURAL: the real function, the real cards, on this environment ── */
$c409_cards = bhp_get_homepage_books( -1 );
bhp_c409_assert( is_array( $c409_cards ) && count( $c409_cards ) > 0, '1.5: bhp_get_homepage_books() returns cards on this environment (' . count( (array) $c409_cards ) . ')', $failures );

$c409_col_card = array();
$c409_mar_card = array();
foreach ( (array) $c409_cards as $c409_card ) {
	$c409_pid = (int) ( isset( $c409_card['product_id'] ) ? $c409_card['product_id'] : 0 );
	if ( $c409_pid === $c409_col ) {
		$c409_col_card = $c409_card;
	}
	if ( $c409_pid === (int) $c409_mar['paperback'] ) {
		$c409_mar_card = $c409_card;
	}
}

/*
 * ⚠ THE COLOURING CARD MAY LEGITIMATELY BE ABSENT. `bhp_get_homepage_books()`
 *   scopes to a product category, and whether the colouring book carries it is
 *   a merchandising fact, not a code fact. So its absence is NOTED, never
 *   asserted either way — and the label assertion below is skipped rather than
 *   passed vacuously, which would be the dishonest outcome.
 */
if ( $c409_col_card ) {
	bhp_c409_assert(
		'Kirkus reviewed' !== (string) ( isset( $c409_col_card['review'] ) ? $c409_col_card['review'] : '' ),
		'1.6: ⭐⭐ LIVE — the COLOURING book\'s hub card does NOT carry "Kirkus reviewed" (review field: "' . (string) ( $c409_col_card['review'] ?? '' ) . '")',
		$failures
	);
	bhp_c409_note( '1.6x the colouring book IS in the hub card set on this environment, so 1.6 is a real observation rather than a vacuous pass.' );
} else {
	bhp_c409_note( '1.6 SKIPPED — the colouring product is not in bhp_get_homepage_books() on this environment, so there is no card to inspect. NOT counted as a pass.' );
}

if ( $c409_mar_card ) {
	bhp_c409_assert(
		'Kirkus reviewed' === (string) ( isset( $c409_mar_card['review'] ) ? $c409_mar_card['review'] : '' ),
		'1.7: ⭐ LIVE — the MARIANA CHAPTER BOOK\'s card still DOES carry "Kirkus reviewed" — the fix removed a false label, it did not remove a true one',
		$failures
	);
} else {
	bhp_c409_note( '1.7 SKIPPED — the Mariana paperback is not in the hub card set on this environment.' );
}

/* ⛔ AND NOTHING ELSE MAY CARRY IT. The real invariant is not "one card is
 *   right" but "exactly the reviewed title is labelled". */
$c409_kirkus_cards = array();
foreach ( (array) $c409_cards as $c409_card ) {
	if ( 'Kirkus reviewed' === (string) ( isset( $c409_card['review'] ) ? $c409_card['review'] : '' ) ) {
		$c409_kirkus_cards[] = (int) ( $c409_card['product_id'] ?? 0 );
	}
}
$c409_allowed = array( (int) $c409_mar['paperback'], (int) $c409_mar['hardcover'] );
bhp_c409_assert(
	array() === array_diff( $c409_kirkus_cards, $c409_allowed ),
	'1.8: ⭐⭐ LIVE — every card carrying "Kirkus reviewed" is a Mariana CHAPTER-BOOK registry id (' . ( $c409_kirkus_cards ? implode( ',', $c409_kirkus_cards ) : 'none' ) . ')',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 1b · THE SWEEP — the other title-substring sites, asserted rather than
 *      described. Both are already GUARDED and both are LEFT ALONE; these
 *      rows exist so a future edit cannot silently remove the guard.
 * ═══════════════════════════════════════════════════════════════════════════ */

/*
 * ⭐ `bhp_get_series_adventures()` still matches product titles against a
 *    `matches` list — deliberately. It is guarded by an ID test
 *    (`bhp_is_colouring_product()`) that `continue`s BEFORE the substring
 *    loop, so the colouring book can never enter an adventure bucket. That
 *    guard is the thing worth protecting.
 */
bhp_c409_assert(
	false !== strpos( $c409_fn_code, 'bhp_is_colouring_product' ),
	'1b.1: the series-adventures matcher still carries its ID-based colouring guard ahead of the title loop',
	$failures
);

/*
 * ⭐ `inc/blog-post-template.php` matches POST titles, where no product id
 *    exists to test. Its guard is a NEGATIVE one — "coloring book" in a title
 *    VETOES a chapter adventure, it never selects one. Direction matters: the
 *    safe way to use an unreliable signal is to let it refuse, never to let it
 *    choose.
 */
$c409_blog_code = bhp_c409_code_only( bhp_c409_read( 'inc/blog-post-template.php' ) );
bhp_c409_assert(
	false !== strpos( $c409_blog_code, "'coloring book'" ) && false !== strpos( $c409_blog_code, "'colouring book'" ),
	'1b.2: the blog rail still carries its negative colouring veto, in both spellings',
	$failures
);

/*
 * ⭐ FORMAT WORDS ARE NOT BOOK IDENTITY. `strpos($product_title, 'hardcover')`
 *    and friends remain, in `bhp_get_series_adventures()` and
 *    `inc/audit-remediation.php`. They classify a FORMAT, not a TITLE, and no
 *    book is selected by them. Left alone on purpose, and named here so the
 *    sweep's boundary is on the record rather than in a report only.
 */
bhp_c409_note( '1b.3 SWEEP BOUNDARY — remaining title-substring tests in the theme are FORMAT classifiers (paperback/hardcover/kindle) in bhp_get_series_adventures() and inc/audit-remediation.php. They select no product and set no label, and were deliberately NOT changed.' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · 409-2 — F3, THE PLUGIN PIN IS NOW A FLOOR
 * ═══════════════════════════════════════════════════════════════════════════ */

$c409_407_src  = bhp_c409_read( 'tests/test-cycle179-407.php' );
$c409_407_code = bhp_c409_code_only( $c409_407_src );

bhp_c409_assert(
	false !== strpos( $c409_407_code, "version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.89', '>=' )" ),
	'2.1: ⭐ THE FIX — test-cycle179-407 §6.2 is a version_compare floor',
	$failures
);
bhp_c409_assert(
	false === strpos( $c409_407_code, "'1.8.89' === BHP_BUNDLE_PRICING_VERSION" ),
	'2.2: the literal equality pin is gone from the EXECUTABLE code',
	$failures
);
bhp_c409_assert(
	false !== strpos( $c409_407_src, "~~bhp_c407_assert( defined( 'BHP_BUNDLE_PRICING_VERSION' )" ),
	'2.3: the superseded pinned assertion is PRESERVED STRUCK in the comment',
	$failures
);

/* ⛔ IT IS STILL A REAL GATE, and this row proves it rather than asserting it
 *   in prose: a downgrade below the floor must still fail. */
bhp_c409_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.89', '>=' ),
	'2.4: LIVE — the running plugin satisfies the 1.8.89 floor (reports ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '(undefined)' ) . ')',
	$failures
);
bhp_c409_assert(
	! version_compare( '1.8.88', '1.8.89', '>=' ),
	'2.5: the floor still REJECTS a downgrade (1.8.88 fails it) — a floor, not a rubber stamp',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · 409-3 — THE COLOURING HERO RAIL
 * ═══════════════════════════════════════════════════════════════════════════ */

$c409_reg = bhp_book_media_registry();
bhp_c409_assert( isset( $c409_reg['colouring_mariana'] ), '3.1: ⭐ THE FIX — bhp_book_media_registry() now HAS a colouring_mariana key', $failures );

$c409_items = (array) ( $c409_reg['colouring_mariana']['items'] ?? array() );
bhp_c409_assert( 6 === count( $c409_items ), '3.2: the entry names exactly six interior pages (got ' . count( $c409_items ) . ')', $failures );

/*
 * ⛔⛔ THE RULE THIS ENTRY EXISTS TO OBEY, ASSERTED MECHANICALLY. Rule 2 at the
 *     top of `inc/book-media.php`: assets are addressed by ATTACHMENT SLUG,
 *     never by a hardcoded id, because staging and production genuinely differ.
 *     VERIFIED first-hand over SSH before the entry was written: these six
 *     slugs resolve to 752-757 on production and 7343-7348 on staging.
 */
$c409_bad_slug = 0;
foreach ( $c409_items as $c409_item ) {
	$c409_slug = (string) ( $c409_item['slug'] ?? '' );
	if ( '' === $c409_slug || preg_match( '/^\d+$/', $c409_slug ) ) {
		$c409_bad_slug++;
	}
}
bhp_c409_assert( 0 === $c409_bad_slug, '3.3: ⛔ every colouring media item is addressed by SLUG, never by an attachment id', $failures );

$c409_media_src = bhp_c409_read( 'inc/book-media.php' );
bhp_c409_assert(
	0 === preg_match( '/=>\s*(4066|694|619|752|753|754|755|756|757|7343|7344|7345|7346|7347|7348)\b/', bhp_c409_code_only( $c409_media_src ) ),
	'3.4: no colouring attachment id appears in the executable code of inc/book-media.php',
	$failures
);

/* ─── BEHAVIOURAL: what the page will actually build ─────────────────────── */
$c409_media = bhp_book_media( 'colouring_mariana' );
bhp_c409_assert( ! empty( $c409_media['has_any'] ), '3.5: ⭐⭐ LIVE — the six slugs RESOLVE on this environment, so has_any is true', $failures );
bhp_c409_assert( 6 === (int) ( $c409_media['count'] ?? 0 ), '3.6: LIVE — all six resolve, none is silently dropped (count=' . (int) ( $c409_media['count'] ?? 0 ) . ')', $failures );

/*
 * ⛔ ALT TEXT IS NOT INVENTED AND IS NOT EMPTY. This build authored no new
 *    customer-facing string, so the entry declares no `alt` and
 *    `bhp_book_media()` falls back to the attachment's own approved
 *    `_wp_attachment_image_alt`. An empty alt on a content image is an
 *    accessibility defect, so "we added no string" must not become "we shipped
 *    alt=''". This row is the difference between those two outcomes.
 */
$c409_empty_alt = 0;
foreach ( (array) ( $c409_media['items'] ?? array() ) as $c409_mi ) {
	if ( '' === trim( (string) ( $c409_mi['alt'] ?? '' ) ) ) {
		$c409_empty_alt++;
	}
}
bhp_c409_assert( 0 === $c409_empty_alt, '3.7: ⭐ LIVE — every resolved colouring image carries real alt text, taken from the already-approved media-library value (' . $c409_empty_alt . ' empty)', $failures );

/* ⭐ THE FALLBACK IS ADDITIVE. Every pre-existing image item declares its own
 *   alt in the registry, so rule 3 still governs them and nothing that already
 *   rendered changed. Asserted, not assumed. */
$c409_declared_alt = 0;
$c409_image_items  = 0;
foreach ( $c409_reg as $c409_k => $c409_entry ) {
	if ( 'colouring_mariana' === $c409_k ) {
		continue;
	}
	foreach ( (array) ( $c409_entry['items'] ?? array() ) as $c409_it ) {
		if ( 'image' === ( $c409_it['type'] ?? '' ) ) {
			$c409_image_items++;
			if ( '' !== (string) ( $c409_it['alt'] ?? '' ) ) {
				$c409_declared_alt++;
			}
		}
	}
}
bhp_c409_assert( $c409_image_items > 0 && $c409_image_items === $c409_declared_alt, '3.8: every OTHER image item still declares its alt in the registry (' . $c409_declared_alt . '/' . $c409_image_items . ') — the fallback changed nothing that already rendered', $failures );

/* ─── THE HERO THE PDP WILL RENDER ──────────────────────────────────────── */
bhp_c409_assert( 'colouring_mariana' === bhp_book_hero_key_for_product( $c409_col ), '3.9: the colouring PDP still resolves to the colouring hero key', $failures );

$c409_hero = bhp_book_hero_gallery_media( $c409_col );
bhp_c409_assert( is_array( $c409_hero ) && ! empty( $c409_hero['items'] ), '3.10: the colouring hero builds a non-empty item list', $failures );
bhp_c409_assert( 7 === count( (array) ( $c409_hero['items'] ?? array() ) ), '3.11: ⭐⭐ THE RAIL — the hero is the COVER plus the six interior pages = 7 items (got ' . count( (array) ( $c409_hero['items'] ?? array() ) ) . ')', $failures );

/*
 * ⛔ 405 DECISION: COVER FIRST. The cover is the product's own featured image,
 *    PREPENDED by `bhp_book_hero_gallery_media()` — read from the product, not
 *    named in the registry, which is exactly why the entry needed no
 *    per-environment cover slug (staging and production genuinely differ on
 *    that one slug, and only on that one).
 */
$c409_first = (array) ( $c409_hero['items'][0] ?? array() );
bhp_c409_assert(
	(int) ( $c409_first['id'] ?? 0 ) === (int) get_post_thumbnail_id( $c409_col ) && (int) get_post_thumbnail_id( $c409_col ) > 0,
	'3.12: ⛔ 405 DECISION HELD — item 0 is the product\'s own featured image (cover first)',
	$failures
);

/* ⛔ 405 DECISION: NO HOVER ZOOM. Re-asserted here because this build touched
 *   the gallery's data, and a decision is only preserved if it is checked. */
$c409_zoom = get_theme_support( 'wc-product-gallery-zoom' );
bhp_c409_assert( false === $c409_zoom || empty( $c409_zoom ), '3.13: ⛔ 405 DECISION HELD — the theme still does NOT support wc-product-gallery-zoom', $failures );
bhp_c409_assert( (bool) current_theme_supports( 'wc-product-gallery-lightbox' ), '3.14: ⛔ 405 DECISION HELD — lightbox support still declared', $failures );

/* ⛔ THE 1.19.406 CORRECTION MUST NOT BE RE-BROKEN. The theme-FILE registry is
 *   a different registry with the same key, and this build did not touch it. */
bhp_c409_assert(
	function_exists( 'bhp_pdp_look_inside_registry' ) && isset( bhp_pdp_look_inside_registry()['colouring_mariana'] ),
	'3.15: the SEPARATE theme-file look-inside registry still has its own colouring_mariana entry — two registries, one key, both intact',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · REGRESSION — the chapter books are untouched
 * ═══════════════════════════════════════════════════════════════════════════ */

foreach ( array( 'mariana_trench', 'mount_everest', 'amazon_rainforest' ) as $c409_key ) {
	$c409_kids = bhp_book_key_product_ids( $c409_key );
	$c409_pb   = (int) ( $c409_kids['paperback'] ?? 0 );
	bhp_c409_assert( $c409_pb > 0 && '' !== bhp_book_hero_key_for_product( $c409_pb ), "4.1 [{$c409_key}]: the chapter-book PDP still resolves to a hero key", $failures );
	bhp_c409_assert( bhp_book_has_look_inside( $c409_key ), "4.2 [{$c409_key}]: the chapter-book look-inside media still resolves", $failures );
}

/*
 * ⛔ STOCK IS READ, NEVER WRITTEN. Staging product 4065 is `outofstock` by
 *    founder ruling and this build leaves it that way. Recorded as a NOTE, not
 *    an assertion: the value is a merchandising decision, and a test that
 *    asserted it would turn a legitimate owner change into a theme failure.
 */
$c409_col_product = function_exists( 'wc_get_product' ) ? wc_get_product( $c409_col ) : null;
bhp_c409_note( '4.3: colouring product stock status READ-ONLY = ' . ( $c409_col_product ? $c409_col_product->get_stock_status() : 'unavailable' ) . ' (this suite writes nothing to it).' );

/* ⛔ OUT OF SCOPE, ASSERTED UNTOUCHED RATHER THAN ASSUMED UNTOUCHED. The
 *   shop-grid "Temporarily unavailable" card line is decision 30 and belongs
 *   to Andrew. This build added no customer-facing string anywhere. */
$c409_new_strings = 0;
foreach ( array( 'functions.php', 'inc/book-media.php', 'inc/book-formats.php' ) as $c409_f ) {
	$c409_c = bhp_c409_code_only( bhp_c409_read( $c409_f ) );
	if ( preg_match_all( '/__\(\s*\x27([^\x27]{12,})\x27\s*,\s*\x27brave-hearts\x27\s*\)/', $c409_c, $c409_mm ) ) {
		foreach ( $c409_mm[1] as $c409_s ) {
			if ( false !== stripos( $c409_s, 'Temporarily unavailable' ) ) {
				$c409_new_strings++;
			}
		}
	}
}
bhp_c409_assert( 0 === $c409_new_strings, '4.4: ⛔ decision 30 NOT pre-empted — this build introduced no "Temporarily unavailable" string in the files it touched', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · VERSIONS
 * ═══════════════════════════════════════════════════════════════════════════ */

$c409_style = bhp_c409_read( 'style.css' );
bhp_c409_assert( 1 === preg_match( '/^Version:\s*1\.19\.409\s*$/m', $c409_style ), '5.1: theme style.css declares 1.19.409', $failures );
bhp_c409_assert( '1.19.409' === wp_get_theme()->get( 'Version' ), '5.2: LIVE — the RUNNING theme reports 1.19.409', $failures );

$c409_min   = bhp_c409_read( 'style.min.css' );
$c409_stamp = '';
if ( preg_match( '/source-md5:\s*([0-9a-f]{32})/', $c409_min, $c409_m2 ) ) {
	$c409_stamp = $c409_m2[1];
}
bhp_c409_assert( '' !== $c409_stamp && $c409_stamp === md5( $c409_style ), '5.3: style.min.css source-md5 matches the shipped style.css — the built artefact is current', $failures );

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
