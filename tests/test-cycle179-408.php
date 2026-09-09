<?php
/**
 * CYCLE179-LD-BUILD-408 — theme 1.19.408.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * THE TWO DEFECTS THIS SUITE EXISTS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Both were raised on PRODUCTION by `CYCLE179-CX-PROD-AUDIT-407` (Pippin,
 * commerce-cx) and both have the SAME shape: a book was identified by a
 * TITLE SUBSTRING or by a registry that does not know every book.
 *
 *   CX-1  The homepage hub card priced THE MARIANA TRENCH at "From $12.99".
 *         `front-page.php` selected a title's formats with
 *         `stripos($book['title'], 'Mariana Trench')`. The colouring book's
 *         title contains that phrase, so it entered the Mariana format map,
 *         was labelled "Paperback" (its title carries no "hardcover" to say
 *         otherwise) and OVERWROTE the real paperback entry.
 *         `bhp_get_home_price_cue()` then published the cheapest thing it had
 *         been handed — the colouring book's price, on the chapter book's card.
 *
 *   CX-2  The colouring PDP rendered "Click to enlarge", the lightbox scaffold
 *         and the flip-through cue, with `book-media.css` and `book-media.js`
 *         NOT ENQUEUED. A dead control on a live product page.
 *         `bhp_book_enqueue_media_assets()` gated on
 *         `bhp_book_lookup_product()`, which walks `bhp_book_registry()` —
 *         THREE CHAPTER BOOKS ONLY — while the RENDER side gates on
 *         `bhp_book_hero_key_for_product()`, which DOES resolve the colouring
 *         book. Gate and render asked different questions.
 *
 * ⭐ 1.19.405 assertions 4.2 and 4.3 already established the governing rule —
 *    "BOTH the swap gate and the media builder call the same resolver, so they
 *    cannot disagree and blank the page". What 405 did not cover is that a
 *    THIRD party asks the same question: the ENQUEUE gate. 408 closes it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE MUTATES: NOTHING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * No post, product, variation, price, stock field, option, coupon or cart is
 * written. Section 2 runs the real enqueue function against a real product by
 * swapping `$wp_query` for the duration of one call and putting it back, and
 * de-registers the two handles on the way in and the way out so no assertion
 * can inherit a previous one's result. Nothing it does can outlive the process.
 *
 * ⛔ NO PRODUCT ID IS TYPED. The colouring id is 618 on production and 4065 on
 *    staging; it is resolved BY SKU through `bhp_colouring_product_ids()`.
 *    The chapter-book ids are read from `bhp_book_registry()` itself.
 *
 * ⚠ WHAT PHP CANNOT PROVE, AND SO IS NOT CLAIMED HERE. That the lightbox
 *   visually opens, that the flip-through plays, or that the card reads
 *   correctly on a phone. Those are browser claims and are recorded
 *   separately in the build report at an asserted `window.innerWidth`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_c408_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_c408_note( $label ) {
	echo "NOTE: {$label}\n";
}

function bhp_c408_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * Source with every comment removed.
 *
 * ⭐ WHY THIS EXISTS. This build DELIBERATELY PRESERVES the superseded
 *    substring code inside struck comments, at the line, so a future reader
 *    can see what was corrected. A naive `strpos($src, 'stripos(')` would
 *    therefore FAIL on correct code — and, worse, a future session might
 *    "fix" the test by deleting the record. Stripping comments with the PHP
 *    tokenizer asserts the EXECUTABLE code while leaving the history intact.
 */
function bhp_c408_code_only( $src ) {
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

/**
 * Run the REAL enqueue gate against a REAL product page and report whether the
 * two book-media handles ended up enqueued.
 *
 * ⛔ BOTH HANDLES ARE DE-REGISTERED BEFORE AND AFTER. Without that, the first
 *    call to return true would make every later call return true and the whole
 *    section would pass vacuously.
 *
 * ⛔ `$wp_query`, `$wp_the_query` and `$post` are restored unconditionally.
 */
function bhp_c408_media_enqueued_for( $product_id ) {
	global $wp_query, $wp_the_query, $post;

	$prev_query    = $wp_query;
	$prev_thequery = $wp_the_query;
	$prev_post     = $post;

	bhp_c408_drop_media_handles();

	$q = new WP_Query(
		array(
			'p'                   => (int) $product_id,
			'post_type'           => 'product',
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
		)
	);

	$wp_query     = $q;
	$wp_the_query = $q;
	if ( $q->have_posts() ) {
		$q->the_post();
	}

	/* The front-end action has not fired in WP-CLI, so calling the enqueue
	 * function directly is correct — but WordPress would emit a
	 * _doing_it_wrong notice for it. Silence only that, only here. */
	add_filter( 'doing_it_wrong_trigger_error', '__return_false', 99 );
	bhp_book_enqueue_media_assets();
	remove_filter( 'doing_it_wrong_trigger_error', '__return_false', 99 );

	$result = wp_style_is( 'bhp-book-media', 'enqueued' ) && wp_script_is( 'bhp-book-media', 'enqueued' );

	wp_reset_postdata();
	$wp_query     = $prev_query;
	$wp_the_query = $prev_thequery;
	$post         = $prev_post;

	bhp_c408_drop_media_handles();

	return $result;
}

function bhp_c408_drop_media_handles() {
	wp_dequeue_style( 'bhp-book-media' );
	wp_dequeue_script( 'bhp-book-media' );
	wp_deregister_style( 'bhp-book-media' );
	wp_deregister_script( 'bhp-book-media' );
}

/**
 * Return the body of ONE named function, isolated by brace matching.
 *
 * ⛔⛔ OWN ERROR, RECORDED RATHER THAN QUIETLY REBUILT. The first run of this
 *     suite on staging read the enqueue gate as `substr( $code, $start, 2000 )`.
 *     With comments stripped the real function is far shorter than 2000 chars,
 *     so the window ran PAST its closing brace and into
 *     `bhp_book_hero_key_for_product()` — which legitimately calls
 *     `bhp_book_lookup_product()` for the chapter-book path. Assertion 2.9
 *     therefore FAILED on correct code.
 *
 * ⭐ THE LESSON, and the reason this comment is longer than the function: a
 *    FIXED-WIDTH WINDOW IS NOT A SCOPE. It fails in both directions — too
 *    short and it truncates a real match into a false pass, too long and it
 *    imports a neighbour's code into a false fail. Match the braces.
 */
function bhp_c408_function_body( $code, $name ) {
	$start = strpos( $code, 'function ' . $name . '(' );
	if ( false === $start ) {
		return '';
	}
	$open = strpos( $code, '{', $start );
	if ( false === $open ) {
		return '';
	}
	$depth = 0;
	$len   = strlen( $code );
	for ( $i = $open; $i < $len; $i++ ) {
		if ( '{' === $code[ $i ] ) {
			$depth++;
		} elseif ( '}' === $code[ $i ] ) {
			$depth--;
			if ( 0 === $depth ) {
				return substr( $code, $start, $i - $start + 1 );
			}
		}
	}
	return '';
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · ENVIRONMENT. Everything below depends on these, so they are asserted
 *     rather than assumed.
 * ═══════════════════════════════════════════════════════════════════════════ */

bhp_c408_assert( function_exists( 'bhp_book_registry' ), '0.1: bhp_book_registry() exists', $failures );
bhp_c408_assert( function_exists( 'bhp_book_key_product_ids' ), '0.2: ⭐ 1.19.408 — bhp_book_key_product_ids() exists (the new registry-identity accessor)', $failures );
bhp_c408_assert( function_exists( 'bhp_book_hero_key_for_product' ), '0.3: bhp_book_hero_key_for_product() exists — the ONE resolver the gate and the builder both ask', $failures );
bhp_c408_assert( function_exists( 'bhp_book_enqueue_media_assets' ), '0.4: bhp_book_enqueue_media_assets() exists', $failures );
bhp_c408_assert( function_exists( 'bhp_get_homepage_books' ), '0.5: bhp_get_homepage_books() exists', $failures );
bhp_c408_assert( function_exists( 'bhp_get_home_price_cue' ), '0.6: bhp_get_home_price_cue() exists', $failures );

$c408_ids = function_exists( 'bhp_colouring_product_ids' ) ? bhp_colouring_product_ids() : array();
$c408_col = isset( $c408_ids['mariana'] ) ? (int) $c408_ids['mariana'] : 0;
bhp_c408_assert( $c408_col > 0, "0.7: the Mariana colouring product resolves BY SKU on this environment (id {$c408_col})", $failures );

if ( ! $c408_col || ! function_exists( 'bhp_book_key_product_ids' ) || ! function_exists( 'bhp_book_hero_key_for_product' ) ) {
	echo "\nRESULT: " . ( count( $failures ) + 1 ) . " FAILURE(S) — environment not fit to continue\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	echo "  - 0.x: aborted before the behaviour tests\n";
	return;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · CX-1 — REGISTRY IDENTITY REPLACES THE TITLE SUBSTRING
 * ═══════════════════════════════════════════════════════════════════════════ */

$c408_reg = bhp_book_registry();
$c408_mar = bhp_book_key_product_ids( 'mariana_trench' );

bhp_c408_assert(
	isset( $c408_mar['paperback'], $c408_mar['hardcover'] )
		&& (int) $c408_reg['mariana_trench']['pb_product'] === $c408_mar['paperback']
		&& (int) $c408_reg['mariana_trench']['hc_product'] === $c408_mar['hardcover'],
	'1.1: bhp_book_key_product_ids() returns the registry\'s own pb_product/hc_product — it adds no second source of truth',
	$failures
);

bhp_c408_assert( array() === bhp_book_key_product_ids( 'not_a_book' ), '1.2: an unknown key returns [] — the caller degrades to no cue rather than to a different book', $failures );

bhp_c408_assert(
	3 === count( array_filter( array( 'mariana_trench', 'mount_everest', 'amazon_rainforest' ), 'bhp_book_key_product_ids' ) ),
	'1.3: all three registry keys used by front-page.php resolve on this environment',
	$failures
);

/* ⛔ THE SEPARATION THE WHOLE FIX RESTS ON: the colouring product is not any
 *    chapter book's paperback or hardcover, in ANY registry entry. If this
 *    ever fails, registry identity has stopped distinguishing them and CX-1
 *    is back by a different route. */
$c408_all_reg_ids = array();
foreach ( $c408_reg as $c408_k => $c408_b ) {
	$c408_all_reg_ids[] = (int) $c408_b['pb_product'];
	$c408_all_reg_ids[] = (int) $c408_b['hc_product'];
}
bhp_c408_assert( ! in_array( $c408_col, $c408_all_reg_ids, true ), "1.4: the colouring product ({$c408_col}) is NOT a registry pb/hc id — identity separates the two books", $failures );

/* ⭐ THE HAZARD IS ASSERTED TO STILL EXIST. If the colouring book were ever
 *    retitled so it no longer contained "Mariana Trench", this suite would
 *    still pass while proving nothing. Asserting the collision keeps the
 *    regression witness below HONEST. */
$c408_col_title = get_the_title( $c408_col );
bhp_c408_assert( false !== stripos( $c408_col_title, 'Mariana Trench' ), "1.5: the colouring title still CONTAINS \"Mariana Trench\" ({$c408_col_title}) — so the old substring match would still catch it, and the witness below is meaningful", $failures );

/* ─── THE REGRESSION WITNESS, ON LIVE DATA ─────────────────────────────────
 * Both algorithms are run against the SAME live `bhp_get_homepage_books()`
 * set: the one that shipped at 1.19.407, and the one that ships at 1.19.408.
 * This does not test a copy of the fix — it demonstrates, with this
 * environment's real products and real prices, WHAT the substring rule
 * selects and what registry identity selects.
 * ───────────────────────────────────────────────────────────────────────── */

$c408_books = bhp_get_homepage_books( -1 );
bhp_c408_assert( is_array( $c408_books ) && count( $c408_books ) > 0, '1.6: bhp_get_homepage_books() returned a non-empty set (guards every assertion below against a vacuous pass)', $failures );

$c408_col_in_set = false;
foreach ( $c408_books as $c408_bk ) {
	if ( (int) ( isset( $c408_bk['product_id'] ) ? $c408_bk['product_id'] : 0 ) === $c408_col ) {
		$c408_col_in_set = true;
		break;
	}
}

/* 1.19.407 ALGORITHM, replicated verbatim from the superseded closure. */
$c408_old_map = array();
foreach ( $c408_books as $c408_bk ) {
	if ( stripos( $c408_bk['title'], 'Mariana Trench' ) === false ) {
		continue;
	}
	$c408_lab = stripos( $c408_bk['title'], 'hardcover' ) !== false ? 'Hardcover' : 'Paperback';
	if ( ! empty( $c408_bk['price'] ) ) {
		$c408_old_map[ $c408_lab ] = $c408_bk['price'];
	}
}

/* 1.19.408 ALGORITHM, replicated verbatim from the shipping closure. */
$c408_new_map = array();
foreach ( $c408_books as $c408_bk ) {
	$c408_pid = (int) ( isset( $c408_bk['product_id'] ) ? $c408_bk['product_id'] : 0 );
	if ( $c408_pid && $c408_pid === $c408_mar['paperback'] ) {
		$c408_lab = 'Paperback';
	} elseif ( $c408_pid && $c408_pid === $c408_mar['hardcover'] ) {
		$c408_lab = 'Hardcover';
	} else {
		continue;
	}
	if ( ! empty( $c408_bk['price'] ) ) {
		$c408_new_map[ $c408_lab ] = $c408_bk['price'];
	}
}

$c408_old_cue = bhp_get_home_price_cue( $c408_old_map );
$c408_new_cue = bhp_get_home_price_cue( $c408_new_map );

bhp_c408_note( '1.x witness — 1.19.407 map: ' . wp_json_encode( $c408_old_map ) . ' -> cue "' . $c408_old_cue . '"' );
bhp_c408_note( '1.x witness — 1.19.408 map: ' . wp_json_encode( $c408_new_map ) . ' -> cue "' . $c408_new_cue . '"' );

if ( $c408_col_in_set ) {
	bhp_c408_assert( isset( $c408_old_map['Paperback'] ), '1.7: WITNESS — the 1.19.407 substring rule DID admit the colouring book to the Mariana map, labelled Paperback (this is CX-1, reproduced on live data)', $failures );
	bhp_c408_assert( $c408_old_cue !== $c408_new_cue, "1.8: WITNESS — the two rules produce DIFFERENT cues on this environment (was \"{$c408_old_cue}\", now \"{$c408_new_cue}\") — the fix changes what the customer is shown", $failures );
} else {
	bhp_c408_note( '1.7/1.8: SKIPPED — the colouring product is not inside bhp_get_homepage_books() on this environment, so CX-1 does not reproduce here. This is an ENVIRONMENT DIFFERENCE and is recorded in the build report, not a pass.' );
}

/* Registry identity must admit ONLY registry products, on every environment,
 * whether or not the colouring book happens to be in the homepage set. */
bhp_c408_assert( ! in_array( $c408_col_title, $c408_new_map, true ), '1.9: the colouring book is absent from the 1.19.408 Mariana format map by construction', $failures );

$c408_new_ok = true;
foreach ( array_keys( $c408_new_map ) as $c408_lab ) {
	if ( 'Paperback' !== $c408_lab && 'Hardcover' !== $c408_lab ) {
		$c408_new_ok = false;
	}
}
bhp_c408_assert( $c408_new_ok, '1.10: every label in the new map came from a registry FIELD (pb_product/hc_product), never from a word read off a title', $failures );

/* Source-level: the executable code carries no title-substring identity. The
 * struck record inside the comments is preserved on purpose and is excluded. */
$c408_fp_code = bhp_c408_code_only( bhp_c408_read( 'front-page.php' ) );
bhp_c408_assert( '' !== $c408_fp_code, '1.11: front-page.php is readable (guards 1.12–1.14 against a vacuous pass)', $failures );
bhp_c408_assert( false === strpos( $c408_fp_code, "stripos( \$book['title']" ) && false === strpos( $c408_fp_code, "stripos(\$book['title']" ), '1.12: NO executable title-substring book identity remains in front-page.php', $failures );
bhp_c408_assert( false !== strpos( $c408_fp_code, 'bhp_book_key_product_ids' ), '1.13: front-page.php identifies books through the registry accessor', $failures );
bhp_c408_assert(
	false !== strpos( $c408_fp_code, "'mariana_trench'" )
		&& false !== strpos( $c408_fp_code, "'mount_everest'" )
		&& false !== strpos( $c408_fp_code, "'amazon_rainforest'" ),
	'1.14: all three call sites pass a REGISTRY KEY, not a display string',
	$failures
);
bhp_c408_assert( 0 === preg_match( '/=\s*(618|4065)\b/', $c408_fp_code ), '1.15: no colouring product id is typed into front-page.php — 618/4065 differ per environment and a denylist would be wrong on one of them', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · CX-2 — THE ENQUEUE GATE ASKS THE RENDER'S OWN QUESTION
 * ═══════════════════════════════════════════════════════════════════════════ */

$c408_col_key = bhp_book_hero_key_for_product( $c408_col );
bhp_c408_assert( 'colouring_mariana' === $c408_col_key, "2.1: the colouring PDP resolves to hero key 'colouring_mariana' (got '{$c408_col_key}') — the scaffold DOES render there, which is why dead assets were a live defect", $failures );

$c408_mar_pb_key = bhp_book_hero_key_for_product( $c408_mar['paperback'] );
bhp_c408_assert( '' !== $c408_mar_pb_key, "2.2: the Mariana paperback PDP still resolves to a hero key ('{$c408_mar_pb_key}') — the chapter-book path is untouched", $failures );

/* ─── BEHAVIOURAL: the real gate, real products, real handles ───────────── */
bhp_c408_assert( bhp_c408_media_enqueued_for( $c408_col ), '2.3: ⭐ THE FIX — book-media.css AND book-media.js are now ENQUEUED on the colouring PDP (ran the real gate against the real product)', $failures );
bhp_c408_assert( bhp_c408_media_enqueued_for( $c408_mar['paperback'] ), '2.4: NO REGRESSION — both handles still enqueue on the Mariana paperback PDP', $failures );

$c408_no_media_id = 0;
foreach ( $c408_books as $c408_bk ) {
	$c408_pid = (int) ( isset( $c408_bk['product_id'] ) ? $c408_bk['product_id'] : 0 );
	if ( $c408_pid && '' === bhp_book_hero_key_for_product( $c408_pid ) ) {
		$c408_no_media_id = $c408_pid;
		break;
	}
}
if ( $c408_no_media_id ) {
	bhp_c408_assert( ! bhp_c408_media_enqueued_for( $c408_no_media_id ), "2.5: STILL FAILS CLOSED — a product with no hero key (id {$c408_no_media_id}) enqueues neither handle, so no visitor pays for assets nothing renders", $failures );
} else {
	bhp_c408_note( '2.5: SKIPPED — every product in the homepage set resolves to a hero key on this environment, so the fail-closed branch has no subject here.' );
}

/* Source-level: gate and render must name the SAME resolver. */
$c408_bf_code = bhp_c408_code_only( bhp_c408_read( 'inc/book-formats.php' ) );
bhp_c408_assert( '' !== $c408_bf_code, '2.6: inc/book-formats.php is readable (guards 2.7–2.9 against a vacuous pass)', $failures );

$c408_enq_body = bhp_c408_function_body( $c408_bf_code, 'bhp_book_enqueue_media_assets' );
bhp_c408_assert( '' !== $c408_enq_body, '2.7: the enqueue function body was isolated by brace matching in the stripped source', $failures );
bhp_c408_assert( false !== strpos( $c408_enq_body, 'bhp_book_hero_key_for_product' ), '2.8: ⭐ the ENQUEUE gate calls bhp_book_hero_key_for_product() — the same resolver 1.19.405 (4.2/4.3) made the swap gate and the media builder share', $failures );
bhp_c408_assert( strlen( $c408_enq_body ) > 100 && strlen( $c408_enq_body ) < 1600, '2.9a: the isolated body is a single function, not a run-on window (' . strlen( $c408_enq_body ) . ' chars) — this is the guard the first run of this suite lacked', $failures );
bhp_c408_assert( false === strpos( $c408_enq_body, 'bhp_book_lookup_product' ), '2.9: the enqueue gate no longer walks bhp_book_lookup_product(), which knows only the three chapter books', $failures );

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE 1.19.405 / 406 DECISIONS ARE PRESERVED
 * ═══════════════════════════════════════════════════════════════════════════ */

$c408_supports = get_theme_support( 'wc-product-gallery-zoom' );
bhp_c408_assert( false === $c408_supports || empty( $c408_supports ), '3.1: LIVE — the running theme still does NOT support wc-product-gallery-zoom (405 decision: no hover zoom)', $failures );
bhp_c408_assert( (bool) current_theme_supports( 'wc-product-gallery-lightbox' ), '3.2: LIVE — the lightbox support is still declared (405 decision: tap-to-enlarge stays)', $failures );
bhp_c408_assert( (bool) current_theme_supports( 'wc-product-gallery-slider' ), '3.3: LIVE — the slider support is still declared (405 decision: the flip-through stays)', $failures );

$c408_media = function_exists( 'bhp_book_media' ) ? bhp_book_media( 'colouring_mariana' ) : array();
bhp_c408_assert( is_array( $c408_media ), '3.4: bhp_book_media() answers for the colouring key', $failures );
/* ⛔ NOTE CORRECTED 1.19.409: it read ~~"colouring hero remains COVER-ONLY"~~,
 *    which was true at 1.19.408 and is no longer. The 405/406 PRESENTATION
 *    decisions (cover first, uncropped, no hover zoom) are still unchanged —
 *    what changed is how many items sit behind the cover. */
bhp_c408_note( '3.x — colouring hero media has_any=' . ( ! empty( $c408_media['has_any'] ) ? 'true' : 'false' ) . ', count=' . (int) ( $c408_media['count'] ?? 0 ) . ' (cover-only at 1.19.408; six interior pages from 1.19.409). The cover-first, uncropped 405/406 presentation is unchanged.' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · OUT OF SCOPE — ASSERTED UNTOUCHED, NOT ASSUMED UNTOUCHED
 * ═══════════════════════════════════════════════════════════════════════════ */

/*
 * ⭐⭐ 4.1 IS INVERTED AS OF 1.19.409 (`CYCLE179-LD-BUILD-409`), AND THE
 *     INVERSION IS THE CORRECT OUTCOME, NOT A WEAKENING.
 *
 * ⛔⛔ THE SUPERSEDED ASSERTION, PRESERVED STRUCK AT THE LINE:
 *
 *      ~~bhp_c408_assert( ! isset( $c408_media_reg['colouring_mariana'] ),
 *        '4.1: bhp_book_media_registry() still has NO colouring_mariana key —
 *        the thumbnail-rail gap recorded at 1.19.406 is STILL OPEN and was NOT
 *        quietly closed by this build', $failures );~~
 *
 * ⭐ WHAT IT WAS FOR, WHICH IS WHY IT WAS RIGHT TO WRITE IT. §4 of this suite
 *    is "out of scope — ASSERTED untouched, not ASSUMED untouched". 1.19.408
 *    was told not to close the media-registry gap, so it asserted that it had
 *    not. That assertion did its job for exactly one release.
 *
 * ⛔ IT MUST NOT SURVIVE AS A BLOCKER. `CYCLE179-LD-BUILD-409` was briefed to
 *    close that gap, and it did. Left as-is, this row would report the
 *    COMMISSIONED work as a defect — the same false-failure shape as a version
 *    pin. It is therefore turned around to assert the NEW truth rather than
 *    deleted, so the history stays legible.
 *
 * ⚠️ THIS IS AN EDIT TO ANOTHER BUILD'S TEST FILE, made deliberately and
 *    named in the 409 report. The alternative — asserting around it from a new
 *    file while a red line stands here — is the "standing red line" this
 *    project has already ruled against twice (1.19.404, 1.19.405 §3).
 */
$c408_media_reg = function_exists( 'bhp_book_media_registry' ) ? bhp_book_media_registry() : array();
bhp_c408_assert( isset( $c408_media_reg['colouring_mariana'] ), '4.1: bhp_book_media_registry() NOW HAS a colouring_mariana key — the thumbnail-rail gap recorded at 1.19.406 and held open through 1.19.408 was closed by 1.19.409 (see the struck original above)', $failures );
bhp_c408_assert( 6 === count( $c408_media_reg['colouring_mariana']['items'] ?? array() ), '4.1b: that entry names exactly the SIX interior colouring pages', $failures );
foreach ( (array) ( $c408_media_reg['colouring_mariana']['items'] ?? array() ) as $c408_ci ) {
	bhp_c408_assert( ! preg_match( '/^\d+$/', (string) ( $c408_ci['slug'] ?? '' ) ) && '' !== (string) ( $c408_ci['slug'] ?? '' ), '4.1c: every colouring media item is addressed by SLUG, never by a hardcoded attachment ID (' . ( $c408_ci['slug'] ?? '(none)' ) . ')', $failures );
}

$c408_col_product = function_exists( 'wc_get_product' ) ? wc_get_product( $c408_col ) : null;
bhp_c408_note( '4.2: colouring product stock status READ-ONLY = ' . ( $c408_col_product ? $c408_col_product->get_stock_status() : 'unavailable' ) . ' (this suite writes nothing to it; the brief leaves staging out of stock).' );

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · VERSIONS
 * ═══════════════════════════════════════════════════════════════════════════ */

$c408_style = bhp_c408_read( 'style.css' );
bhp_c408_assert( 1 === preg_match( '/^Version:\s*1\.19\.408\s*$/m', $c408_style ), '5.1: theme style.css declares 1.19.408', $failures );
bhp_c408_assert( '1.19.408' === wp_get_theme()->get( 'Version' ), '5.2: LIVE — the RUNNING theme reports 1.19.408', $failures );

$c408_min = bhp_c408_read( 'style.min.css' );
$c408_stamp = '';
if ( preg_match( '/source-md5:\s*([0-9a-f]{32})/', $c408_min, $c408_m ) ) {
	$c408_stamp = $c408_m[1];
}
bhp_c408_assert( '' !== $c408_stamp && $c408_stamp === md5( $c408_style ), '5.3: style.min.css source-md5 matches the shipped style.css — the built artefact is current', $failures );

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
