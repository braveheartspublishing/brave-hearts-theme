<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CYCLE179-LD-BUILD-389 — the four conversion items, one suite.
 * Theme 1.19.389, 2026-09-06.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * §1  the kit page's instant sample (four gated pages, scrollable)
 * §2  the Chapter 7 -> Chapter 10 copy defect, and the guard that stops it
 *     recurring
 * §3  the product-page comparison table, INCLUDING a live-price drift guard
 * §4  the express checkout bridge (the wallet container on a product page)
 * §5  the exit-intent copy set, and the stale header comment
 *
 * ⛔ WHAT THIS SUITE PROVES AND WHAT IT DOES NOT, stated up front so no one
 *    over-reads a green run:
 *
 *    IT PROVES: the deployed source carries every string, every hook and every
 *    rail; the figures the comparison table would print are equal to the live
 *    WooCommerce prices at the moment of the run; the kit page and the exit
 *    modal name the same chapter; and, where the environment allows a real
 *    HTTP fetch, that the markup actually reaches the browser.
 *
 *    IT DOES NOT PROVE: that a Stripe wallet button PAINTS. That is a browser,
 *    account and payment-method-domain question, not a theme question. §4 can
 *    prove the container is printed and the plugin's callback is on the hook;
 *    it cannot prove Google Pay is available to a given visitor. The render
 *    evidence is a real browser at a stated `window.innerWidth`, in the build
 *    report, and it is a separate artifact.
 *
 *    IT ALSO DOES NOT PROVE the Vocabulary Card grant end to end. That row is
 *    source-verified and gated on the plugin's own live flag; it has not been
 *    observed being delivered at a real single-book checkout. Named, not
 *    smoothed over.
 *
 * Run on STAGING (never production):
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-build-389.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * It touches no post, no option, no product, no order and no WooCommerce
 * record. It sends no mail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$b389_failures = array();
$b389_pass     = 0;

function b389_ok( $label, $cond, $detail = '' ) {
	global $b389_failures, $b389_pass;
	if ( $cond ) {
		++$b389_pass;
		echo "PASS {$label}\n";
		return true;
	}
	echo "FAIL {$label}" . ( '' !== $detail ? "  ({$detail})" : '' ) . "\n";
	$b389_failures[] = $label;
	return false;
}

function b389_head( $t ) {
	echo "\n=== {$t} ===\n";
}

function b389_file( $rel ) {
	$p = get_template_directory() . '/' . ltrim( $rel, '/' );
	return ( file_exists( $p ) && is_readable( $p ) ) ? (string) file_get_contents( $p ) : '';
}

/**
 * Comment-stripped source. Every absence claim below runs through this, because
 * this release deliberately PRESERVES superseded strings in docblocks and a raw
 * scan would report a defect that is in fact the historical record.
 */
function b389_code( $rel ) {
	$src = b389_file( $rel );
	if ( '' === $src || ! function_exists( 'token_get_all' ) ) {
		return $src;
	}
	$out = '';
	foreach ( token_get_all( $src ) as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				continue;
			}
			$out .= $t[1];
		} else {
			$out .= $t;
		}
	}
	return $out;
}

function b389_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** The priority a named callback sits at on a named hook, or null. */
function b389_hook_priority( $hook, $callback ) {
	global $wp_filter;
	if ( ! isset( $wp_filter[ $hook ] ) ) {
		return null;
	}
	foreach ( $wp_filter[ $hook ]->callbacks as $prio => $cbs ) {
		foreach ( $cbs as $id => $cb ) {
			if ( is_string( $cb['function'] ) && $cb['function'] === $callback ) {
				return (int) $prio;
			}
		}
	}
	return null;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · PRECONDITIONS — refuse to run rather than produce a false PASS.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§0 PRECONDITIONS' );

$b389_version = (string) wp_get_theme()->get( 'Version' );
b389_ok( '§0.1 theme version is 1.19.389 or later', version_compare( $b389_version, '1.19.389', '>=' ), "got {$b389_version}" );
b389_ok( '§0.2 the comment stripper actually strips (it is what keeps every absence claim honest)', false === strpos( b389_code( 'template-parts/acquisition/exit-intent-popup.php' ), 'SHIPPED DISABLED' ) );
b389_ok( '§0.3 WooCommerce is loaded', function_exists( 'wc_get_product' ) );
b389_ok( '§0.4 the bundle plugin is loaded', function_exists( 'bhp_bundle_rules' ) && function_exists( 'bhp_bundle_catalog' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE INSTANT SAMPLE — four real Kit pages, gated, above the form.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§1 THE INSTANT SAMPLE' );

$b389_sample_rel  = 'template-parts/acquisition/kit-sample-preview.php';
$b389_sample_src  = b389_file( $b389_sample_rel );
$b389_sample_code = b389_code( $b389_sample_rel );

b389_ok( '§1.1 the template part exists', '' !== $b389_sample_src );

/* ⛔ THE IMAGES SHIP INSIDE THE THEME. This is the assertion that would fail if
 *   someone deployed the code without the assets, which is the failure that
 *   would put four broken images above the one email field this page exists to
 *   fill. Eight files: four pages at two widths. */
$b389_missing_img = array();
foreach ( array( 'p01', 'p02', 'p03', 'p04' ) as $b389_p ) {
	foreach ( array( '600', '1200' ) as $b389_w ) {
		$b389_img = "assets/img/kit-sample/kit-sample-{$b389_p}-{$b389_w}.jpg";
		if ( '' === b389_file( $b389_img ) ) {
			$b389_missing_img[] = $b389_img;
		}
	}
}
b389_ok( '§1.2 all eight sample images ship inside the theme', empty( $b389_missing_img ), implode( ', ', $b389_missing_img ) );

b389_ok(
	'§1.3 the kit page includes the sample, above the signup form',
	false !== strpos( b389_code( 'page-reluctant-reader-adventure-kit.php' ), "get_template_part('template-parts/acquisition/kit-sample-preview')" )
);

/* ⛔ ORDER MATTERS AND IS ASSERTED, not assumed: the sample must come BEFORE
 *   the lead-magnet form in the source, or the "gated preview above the form"
 *   the brief asked for is a preview below the form. */
$b389_kit_code   = b389_code( 'page-reluctant-reader-adventure-kit.php' );
$b389_pos_sample = strpos( $b389_kit_code, 'kit-sample-preview' );
$b389_pos_form   = strpos( $b389_kit_code, "'adventure-kit-signup'" );
b389_ok(
	'§1.4 the sample renders ABOVE the signup form',
	false !== $b389_pos_sample && false !== $b389_pos_form && $b389_pos_sample < $b389_pos_form
);

b389_ok( '§1.5 the heading is present', false !== strpos( $b389_sample_code, 'See the first four pages' ) );
b389_ok(
	'§1.6 the one line in his voice is present, character for character',
	false !== strpos( $b389_sample_code, 'Here are the first four pages. The rest of the chapter arrives by email, with the activity pages.' )
);
b389_ok(
	'§1.7 the line after page four is present',
	false !== strpos( $b389_sample_code, 'The chapter continues in the kit' )
);

/* Four pages, four alt texts, none of them empty. ⛔ `alt=""` is forbidden on
 * these four: for a screen-reader user they are the ONLY representation of the
 * sample, which is `design-creative`'s own instruction in ALT-TEXT.md. */
b389_ok( '§1.8 exactly four pages are configured', 4 === substr_count( $b389_sample_code, "'slug'  => 'kit-sample-p" ) );
b389_ok( '§1.9 every page carries an alt text', 4 === substr_count( $b389_sample_code, "'alt'   => __(" ) );
b389_ok( '§1.10 ⛔ no empty alt on a sample page', false === strpos( $b389_sample_code, 'alt=""' ) );

/* Responsive images, not a single 1200px file on a phone. */
b389_ok( '§1.11 each page ships a srcset', false !== strpos( $b389_sample_code, 'srcset=' ) && false !== strpos( $b389_sample_code, '600w' ) && false !== strpos( $b389_sample_code, '1200w' ) );
b389_ok( '§1.12 images are lazy and carry intrinsic dimensions (no CLS)', false !== strpos( $b389_sample_code, 'loading="lazy"' ) && false !== strpos( $b389_sample_code, 'width="600" height="776"' ) );

/* ⛔ FUNNEL ISOLATION (`.claude/rules/funnels.md`). A block added beside a
 *   capture form is exactly where a storage prefix walks in by momentum. */
b389_ok(
	'§1.13 ⛔ the sample mints no funnel storage prefix and no popup analytics prefix',
	false === strpos( $b389_sample_code, 'bhp_parent_popup' )
		&& false === strpos( $b389_sample_code, 'bhp_mariana_popup' )
		&& false === strpos( $b389_sample_code, 'data-bhp-popup' )
		&& false === strpos( $b389_sample_code, 'localStorage' )
);
b389_ok( '§1.14 ⛔ the sample renders no form of its own', false === stripos( $b389_sample_code, '<form' ) );

/* ⭐ THE BUILD NOTE FROM `design-creative`, ASSERTED RATHER THAN TRUSTED: the
 *   page images have no border of their own, so the CONTAINER must supply an
 *   edge. Both a border and a shadow, because a shadow alone is imperceptible
 *   on some displays and a border alone does not lift the strip off the page. */
$b389_pl_css = b389_file( 'assets/css/parent-landing.css' );
$b389_frame  = '';
if ( preg_match( '/\.parent-landing-sample__frame\s*\{([^}]*)\}/s', $b389_pl_css, $b389_m ) ) {
	$b389_frame = $b389_m[1];
}
b389_ok( '§1.15 the frame rule exists', '' !== $b389_frame );
b389_ok( '§1.16 ⭐ the frame carries a border (design-creative: the pages have none)', false !== strpos( $b389_frame, 'border:' ) );
b389_ok( '§1.17 ⭐ the frame carries a shadow as well as the border', false !== strpos( $b389_frame, 'box-shadow:' ) );

$b389_strip = '';
if ( preg_match( '/\.parent-landing-sample__strip\s*\{([^}]*)\}/s', $b389_pl_css, $b389_m2 ) ) {
	$b389_strip = $b389_m2[1];
}
b389_ok( '§1.18 the strip scrolls horizontally', false !== strpos( $b389_strip, 'overflow-x: auto' ) );
b389_ok( '§1.19 the strip snaps, so a thumb lands on a page rather than between two', false !== strpos( $b389_strip, 'scroll-snap-type' ) );
/* ⛔ A scrollable region that cannot be focused is unreachable by keyboard, and
 *   this is the only proof element on the page. */
b389_ok( '§1.20 the strip is keyboard reachable', false !== strpos( $b389_sample_code, 'tabindex="0"' ) && false !== strpos( $b389_pl_css, '.parent-landing-sample__strip:focus-visible' ) );

/* ⛔ THE ALT TEXT IS HELD TO THE PAGE'S OWN COPY RAILS. §4c of
 *   tests/test-cycle167-kit-page.php forbids a duration claim anywhere in this
 *   page's code, and an alt attribute is customer-facing copy. This is why the
 *   "About 10 minutes" line in design-creative's ALT-TEXT.md is deliberately
 *   NOT reproduced. */
b389_ok( '§1.21 ⛔ no duration claim in the sample copy or its alt text', 0 === preg_match( '/\b\d+\s*(?:-|\s)?\s*(?:minute|min|hour)s?\b/i', $b389_sample_code ) );
b389_ok( '§1.22 ⛔ no "we"/"us"/"our" in the sample copy (VOICE §9.1)', 0 === preg_match( '/\b(we|us|our)\b/i', $b389_sample_code ) );
b389_ok( '§1.23 ⛔ no em dash in the sample copy', false === strpos( $b389_sample_code, "\xE2\x80\x94" ) );
b389_ok(
	'§1.24 ⛔ nothing is promised that the Kit does not contain',
	0 === preg_match( '/\b(workbook|worksheets?|audiobook|poster|stickers?|lesson plans?|flashcards?|curriculum)\b/i', $b389_sample_code )
);
b389_ok(
	'§1.25 ⛔ no rating, review, award, urgency or scarcity claim',
	0 === preg_match( '/\b(rating|reviews?|stars?|award-winning|best-?sell\w*|hurry|only \d+ left|limited time)\b/i', $b389_sample_code )
);

/*
 * ⛔⛔ THE SAMPLE CSS MUST SIT ABOVE THE 1.19.311 DESKTOP FOLD SECTION, AND
 *    THIS ASSERTION EXISTS BECAUSE THIS BUILD GOT IT WRONG TWICE AND WAS
 *    CAUGHT ON STAGING BOTH TIMES, NOT IN REVIEW.
 *
 * ⭐ WHY IT MATTERS: `tests/test-cycle167-kit-page.php` §5e finds that
 *    section's workstream marker with `strpos()` and then takes
 *    `substr($css, $pos)` — everything from the marker to the END OF THE FILE.
 *    Anything below it is read as part of the desktop fold block, and §5e-l
 *    ("exactly ONE media query") and §5e-q ("no font-size declared") fail
 *    against CSS that is correct.
 *
 * ⭐ THE SECOND MISTAKE IS WORTH MORE THAN THE FIRST: the fix comment written
 *    into the CSS QUOTED THE MARKER, which put an earlier copy of the search
 *    string in the file and moved §5e's window onto the warning itself.
 *
 * ⛔ THE MARKER IS THEREFORE ASSEMBLED HERE RATHER THAN WRITTEN AS A LITERAL,
 *    so this suite cannot become the earlier copy either. And the fix was to
 *    move and reword CSS, NEVER to edit §5e.
 */
$b389_fold_marker = 'CYCLE167-LD-KIT' . '-FOLD-FIX';
$b389_sample_pos  = strpos( $b389_pl_css, 'THE INSTANT SAMPLE' );
$b389_fold_pos    = strpos( $b389_pl_css, $b389_fold_marker );
b389_ok(
	'§1.26 ⛔⛔ the sample CSS sits ABOVE the fold-budget section (§5e reads to EOF from there)',
	false !== $b389_sample_pos && false !== $b389_fold_pos && $b389_sample_pos < $b389_fold_pos
);
b389_ok(
	'§1.27 ⛔⛔ the fold marker appears exactly ONCE in the stylesheet (an earlier copy moves §5e\'s window)',
	1 === substr_count( $b389_pl_css, $b389_fold_marker )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · CHAPTER 10, AND THE GUARD THAT STOPS THIS RECURRING.
 *
 * ⭐ VERIFIED LIVE 2026-09-06 against the PRODUCTION document root: the served
 *    kit is md5 `e227eea53ec762df4abdb6a09615a730`, 8,944,368 bytes, mtime
 *    2026-09-03 19:26, `/Count 11`, byte-identical to the Drive kit of record
 *    "Reluctant Reader Adventure Kit v2.2 (Chapter 10, live 2026-09-03).pdf".
 *
 * ⚠ THESE ASSERTIONS CANNOT OPEN THAT PDF. They are consistency guards across
 *   the surfaces that name the chapter, which is the drift that actually put
 *   "Chapter 7" in front of parents for three days.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§2 THE CHAPTER NUMBER' );

b389_ok( '§2.1 ⛔ no "Chapter 7" survives in the kit page CODE', false === stripos( $b389_kit_code, 'Chapter 7' ) );
b389_ok( '§2.2 the kit page names Chapter 10 in the checklist', false !== strpos( $b389_kit_code, 'Chapter 10 from The Mariana Trench, in full' ) );
b389_ok( '§2.3 the kit page names Chapter 10 in the FAQ answer', false !== strpos( $b389_kit_code, 'Chapter 10 from The Mariana Trench in full' ) );
b389_ok( '§2.4 the kit page names Chapter 10 on the cover tag', false !== strpos( $b389_kit_code, 'Free · Chapter 10' ) );
/* ⛔ THE PAGE-COUNT CLAIM. The kit is 11 pages, not 7. The page must not claim
 *   a count that contradicts the artefact; the cleanest way to satisfy that is
 *   to claim no count at all, which is what it does. */
b389_ok(
	'§2.5 ⛔ the kit page CODE makes no seven-page claim',
	0 === preg_match( '/\b(seven|7)\s+pages?\b/i', $b389_kit_code )
);
b389_ok( '§2.6 ⛔ the retired chapter title is gone from the kit page CODE', false === stripos( $b389_kit_code, 'The Swordfish' ) );
/* The two surfaces that name a chapter must name the same one. */
$b389_exit_code = b389_code( 'template-parts/acquisition/exit-intent-popup.php' );
b389_ok(
	'§2.7 ⭐ the exit modal and the kit page name the SAME chapter',
	false !== strpos( $b389_exit_code, 'Chapter 10' ) && false !== strpos( $b389_kit_code, 'Chapter 10' )
		&& false === stripos( $b389_exit_code, 'Chapter 7' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · THE COMPARISON TABLE — and the drift guard the brief asked for.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§3 THE COMPARISON TABLE' );

b389_ok( '§3.1 the functions are loaded', function_exists( 'bhp_compare_table_data' ) && function_exists( 'bhp_compare_collection_figures' ) && function_exists( 'bhp_compare_table_render' ) );

$b389_cmp_prio  = b389_hook_priority( 'woocommerce_single_product_summary', 'bhp_compare_table_render' );
$b389_card_prio = b389_hook_priority( 'woocommerce_single_product_summary', 'bhp_book_render_format_selector' );
/*
 * ⭐⭐ §3.2 SUPERSEDED IN PLACE, 1.19.391 (`CYCLE179-CX-BUILD-391`), on
 *     ANDREW SIGNORE'S OWN INSTRUCTION, 2026-09-07: the format cards, the
 *     price, the add-to-cart button and the Stripe express element stay ABOVE
 *     THE FOLD and ABOVE the comparison table; the table moves below the
 *     purchase block.
 *
 * ⛔ THE SUPERSEDED ASSERTION, PRESERVED AT THE LINE rather than replaced
 *    silently, because "14" was a considered choice and a later reader who
 *    finds only "17" cannot tell whether 14 was ever weighed:
 *
 *      ~~b389_ok( '§3.2 it renders at priority 14', 14 === $b389_cmp_prio );~~
 *
 * ⭐ 17 IS NOT ARBITRARY: 15 is the format selector, 16 is the express
 *    bridge, and 17 is the first free slot after them, so nothing is inserted
 *    between the purchase block and the table.
 */
b389_ok( '§3.2 ⭐ SUPERSEDED 1.19.391: it renders at priority 17, below the purchase block', 17 === $b389_cmp_prio, 'got ' . var_export( $b389_cmp_prio, true ) );
b389_ok(
	'§3.2a ⛔ and it is BELOW the format selector (15) and the express bridge (16) in the hook table',
	17 > b389_hook_priority( 'woocommerce_single_product_summary', 'bhp_book_render_format_selector' )
		&& 17 > b389_hook_priority( 'woocommerce_single_product_summary', 'bhp_express_bridge_render' )
);
/*
 * ⛔ §3.3 REVERSED WITH §3.2 AND §3.38, same ruling, same date. Preserved:
 *      ~~§3.3 and 14 is genuinely ABOVE the format cards
 *        $b389_cmp_prio < $b389_card_prio~~
 *    The point of the original was that the ORDER is asserted from the live
 *    hook table rather than assumed from a number. That is still the point.
 */
b389_ok( '§3.3 ⭐ SUPERSEDED 1.19.391: 17 is genuinely BELOW the format cards, read from the live hook table', null !== $b389_card_prio && $b389_cmp_prio > $b389_card_prio, "cards at " . var_export( $b389_card_prio, true ) );

/*
 * ⭐⭐ THE DRIFT GUARD. The build brief: pull prices from the live products, or
 *     state the hardcode and add a test that fails if the live prices drift.
 *     Nothing is hardcoded, so this asserts the stronger thing: every figure
 *     the table would print is EQUAL to what WooCommerce and the bundle plugin
 *     say right now. If a price moves and the table does not move with it,
 *     this goes red.
 *
 * ⛔ THE EXPECTATIONS ARE RECOMPUTED FROM THE LIVE SOURCES INSIDE THIS TEST,
 *    not typed. A test that typed 11.99 would be the very hardcode the brief
 *    was guarding against, relocated into the suite.
 */
$b389_key  = 'mariana_trench';
$b389_data = function_exists( 'bhp_compare_table_data' ) ? bhp_compare_table_data( $b389_key ) : null;
b389_ok( '§3.4 the table can be priced from live data', is_array( $b389_data ) );

if ( is_array( $b389_data ) ) {
	$b389_live = bhp_book_purchase_data( $b389_key );
	b389_ok(
		'§3.5 the single paperback figure EQUALS the live product price',
		abs( (float) $b389_data['single_paperback'] - (float) $b389_live['paperback']['price'] ) < 0.005,
		$b389_data['single_paperback'] . ' vs ' . $b389_live['paperback']['price']
	);
	b389_ok(
		'§3.6 the single hardcover figure EQUALS the live product price',
		abs( (float) $b389_data['single_hardcover'] - (float) $b389_live['hardcover']['price'] ) < 0.005,
		$b389_data['single_hardcover'] . ' vs ' . $b389_live['hardcover']['price']
	);

	/* The collection arithmetic, recomputed here from the same two sources the
	 * shipped `bhp_book_collection_data()` reads. */
	$b389_rules = bhp_bundle_rules( 'paperback' );
	$b389_final = end( $b389_rules );
	$b389_cat   = bhp_bundle_catalog();
	$b389_sub   = 0.0;
	foreach ( $b389_cat['paperback'] as $b389_e ) {
		$b389_pid = ! empty( $b389_e['variation_id'] ) ? (int) $b389_e['variation_id'] : (int) $b389_e['product_id'];
		$b389_p   = wc_get_product( $b389_pid );
		$b389_sub += $b389_p ? (float) $b389_p->get_price() : 0.0;
	}
	b389_ok(
		'§3.7 the "bought separately" figure EQUALS the live sum of the three books',
		abs( (float) $b389_data['collection_separately'] - $b389_sub ) < 0.005,
		$b389_data['collection_separately'] . ' vs ' . $b389_sub
	);
	b389_ok(
		'§3.8 the saving EQUALS the plugin\'s approved discount (a derived claim, recomputed)',
		abs( (float) $b389_data['collection_saving'] - (float) $b389_final['discount'] ) < 0.005
	);
	b389_ok(
		'§3.9 the collection price EQUALS separately minus the saving, to the cent',
		abs( (float) $b389_data['collection_price'] - ( $b389_sub - (float) $b389_final['discount'] ) ) < 0.005
	);
	b389_ok(
		'§3.10 the shipping figure EQUALS the plugin\'s approved single-item amount',
		null !== $b389_data['ship_single'] && abs( (float) $b389_data['ship_single'] - (float) bhp_bundle_single_shipping( 'paperback' ) ) < 0.005
	);
	b389_ok(
		'§3.11 "Free" on the collection row is a LIVE read, not a copy decision',
		(bool) $b389_data['collection_ships_free'] === (bool) bhp_book_collection_ships_free()
	);
	/* ⚠ THE VOCABULARY ROW IS GATED ON THE PLUGIN'S OWN FLAG, so the row cannot
	 *   outlive the offer. It is still NOT observed at a real single-book
	 *   checkout, and that gap is named in the build report. */
	b389_ok(
		'§3.12 the Vocabulary Card row is gated on the plugin\'s live grant flag',
		function_exists( 'bhp_bundle_vocab_cards_live' )
			&& (bool) $b389_data['vocab_cards_live'] === (bool) bhp_bundle_vocab_cards_live()
	);
	b389_ok(
		'§3.13 the Activity Book row is gated on the plugin\'s live grant flag',
		function_exists( 'bhp_bundle_addon_free_with_collection' )
			&& (bool) $b389_data['activity_book_live'] === (bool) bhp_bundle_addon_free_with_collection()
	);

	/* ⛔ AND THE RENDERED CELLS CARRY THOSE FIGURES. Reading the data array is
	 *   not the same claim as printing it, and this is the seam where a format
	 *   helper could drop a number. */
	$bhp_compare = $b389_data;
	ob_start();
	include get_template_directory() . '/template-parts/commerce/compare-table.php';
	$b389_html = (string) ob_get_clean();

	/*
	 * ⚠ THE ENTITY DECODE MIRRORS THE TEMPLATE'S, AND IT IS THE SECOND HALF OF
	 *   A DEFECT THIS SUITE ACTUALLY CAUGHT. `wc_price()` emits the currency
	 *   symbol as `&#36;`; the template decodes it so `esc_html()` does not
	 *   double-escape it into `&amp;#36;`. A test formatter that skipped the
	 *   decode compared `&#36;11.99` against a rendered `$11.99` and failed on
	 *   correct output. ⛔ Both sides now format identically, which is what
	 *   makes §3.14 to §3.18 an equality check rather than a coincidence.
	 */
	$b389_fmt = function ( $n ) {
		if ( ! function_exists( 'wc_price' ) ) {
			return '$' . number_format( (float) $n, 2 );
		}
		return html_entity_decode( wp_strip_all_tags( wc_price( (float) $n ) ), ENT_QUOTES, 'UTF-8' );
	};

	b389_ok( '§3.14 the rendered table prints the live single price', false !== strpos( $b389_html, $b389_fmt( $b389_data['single_paperback'] ) ) );
	b389_ok( '§3.15 the rendered table prints the live collection price', false !== strpos( $b389_html, $b389_fmt( $b389_data['collection_price'] ) ) );
	b389_ok( '§3.16 the rendered table prints the live bought-separately total', false !== strpos( $b389_html, $b389_fmt( $b389_data['collection_separately'] ) ) );
	b389_ok( '§3.17 the rendered table prints the live saving', false !== strpos( $b389_html, $b389_fmt( $b389_data['collection_saving'] ) ) );
	b389_ok( '§3.18 the footnote prints the live hardcover prices', false !== strpos( $b389_html, $b389_fmt( $b389_data['single_hardcover'] ) ) && false !== strpos( $b389_html, $b389_fmt( $b389_data['collection_hardcover'] ) ) );

	/* The approved copy specification's structure, row for row. */
	b389_ok( '§3.19 the headline is the approved one', false !== strpos( $b389_html, 'One book, or all three' ) );
	b389_ok( '§3.20 the lead is the approved one, character for character', false !== strpos( $b389_html, 'Here is exactly what changes if you take all three together, and what comes with your order either way.' ) );
	b389_ok( '§3.21 both group heads render', false !== strpos( $b389_html, 'What changes' ) && false !== strpos( $b389_html, 'The same either way' ) );
	foreach ( array( 'Price, paperback', 'Shipping', 'Your shipment', '30 day guarantee, keep the books', 'Printed for your order' ) as $b389_row ) {
		b389_ok( "§3.22 row renders: {$b389_row}", false !== strpos( $b389_html, $b389_row ) );
	}
	/*
	 * ⭐⭐ §3.23, §3.24 AND §3.26 NOW READ THE STRIPPED TEXT, AND THE CLAIM IS
	 *     UNCHANGED. In 1.19.391 the word FREE became a gold badge inside the
	 *     row label (Andrew, seal 1208: "emphasize FREE etc"), so the rendered
	 *     HTML is `<span class="bhp-compare__free">FREE</span> Adventure
	 *     Activity Book, printable`. A raw `strpos()` over markup cannot see
	 *     past a tag it did not expect.
	 *
	 * ⛔ THE ASSERTION WAS RIGHT AND ITS INSTRUMENT WAS TAG-BLIND — the same
	 *    failure shape `tests/test-cycle168-early-cart-capture.php` §1.0
	 *    records for comment-blind `strpos()`. The tempting "fix" is to delete
	 *    the badge, which would undo the founder's instruction to satisfy a
	 *    test. ⭐ Stripping tags first tests the READER'S experience, which is
	 *    what the assertion was always about.
	 *
	 * ⛔ WHAT IS STILL FORBIDDEN, unchanged: sentence-case "Free" in these row
	 *    labels. FREE must be uppercase IN THE STRING, never by CSS.
	 *
	 * ⛔ SUPERSEDED, PRESERVED:
	 *      ~~§3.23 strpos( $b389_html, 'FREE Vocabulary Card Activity, printable' )~~
	 *      ~~§3.24 strpos( $b389_html, 'FREE Adventure Activity Book, printable' )~~
	 */
	$b389_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $b389_html ) ) );
	b389_ok( '§3.23 ⭐ the Vocabulary Card row renders (the brief asked for it)', false !== strpos( $b389_text, 'FREE Vocabulary Card Activity, printable' ) );
	b389_ok( '§3.24 the Activity Book row renders', false !== strpos( $b389_text, 'FREE Adventure Activity Book, printable' ) );
	b389_ok( '§3.25 the footnote renders', false !== strpos( $b389_html, 'Prices are paperback.' ) );

	/* ⭐ FREE IS UPPERCASE IN THE STRING ITSELF, never by text-transform, so it
	 *   survives the accessible name and any plain-text fallback. */
	b389_ok( '§3.26 ⭐ FREE is uppercase in the string, not in CSS', false !== strpos( $b389_text, 'FREE Adventure Activity Book' ) && false === strpos( $b389_text, 'Free Adventure Activity Book' ) );
	/*
	 * ⭐ 1.19.391 — THE RESTYLE MUST NOT HAVE CHANGED WHAT A ROW SAYS. Every
	 *    visual substitution carries the original string in `.screen-reader-text`
	 *    and marks the visual `aria-hidden`, so the accessible reading is what
	 *    1.19.390 printed. These assert the two substitutions that carry the
	 *    most meaning: the saving sentence and the "Yes" answers.
	 */
	b389_ok(
		'§3.26a ⛔ the collection price cell still SPEAKS the 1.19.390 sentence in full',
		false !== strpos(
			$b389_text,
			sprintf(
				'%1$s for all three. Bought separately they are %2$s, so that is %3$s off.',
				$b389_fmt( $b389_data['collection_price'] ),
				$b389_fmt( $b389_data['collection_separately'] ),
				$b389_fmt( $b389_data['collection_saving'] )
			)
		)
	);
	b389_ok(
		'§3.26b ⛔ the "Yes" answers still SPEAK the word Yes (the check disc is aria-hidden)',
		substr_count( $b389_text, 'Yes' ) >= 4 && false !== strpos( $b389_html, 'aria-hidden="true"' )
	);
	b389_ok(
		'§3.26c ⭐ the gold lane is carried through the group headings by a filler cell, not a colspan=3',
		false !== strpos( $b389_html, 'bhp-compare__groupfill' ) && false === strpos( $b389_html, 'colspan="3"' )
	);

	/* Mobile shape: the two answers must carry their own labels. */
	b389_ok( '§3.27 every answer cell carries a data-label for the mobile shape', substr_count( $b389_html, 'data-label=' ) >= 12 );
	b389_ok( '§3.28 the table is scoped for screen readers (row and column scopes)', false !== strpos( $b389_html, 'scope="row"' ) && false !== strpos( $b389_html, 'scope="col"' ) );

	/* Rails on the rendered copy. */
	b389_ok( '§3.29 ⛔ no em dash in the rendered table', false === strpos( $b389_html, "\xE2\x80\x94" ) );
	/*
	 * ⚠ "us" IS MATCHED CASE-SENSITIVELY HERE, AND THE REASON IS A REAL FALSE
	 *   POSITIVE FOUND ON STAGING IN THIS BUILD: the shipping cell reads "in
	 *   the contiguous US", and a case-insensitive `\bus\b` matched the
	 *   STATE-GROUP ABBREVIATION. The voice rail forbids the company "us"; it
	 *   was never about the letters u-s. "we" and "our" stay case-insensitive
	 *   because neither has an innocent uppercase form on a product page.
	 *   ⛔ Recorded rather than quietly widened: an assertion that fires on
	 *      correct copy teaches the next reader to ignore a red suite.
	 */
	b389_ok(
		'§3.30 ⛔ no "we"/"us"/"our" in the rendered table (VOICE §9.1)',
		0 === preg_match( '/\b(we|our)\b/i', wp_strip_all_tags( $b389_html ) )
			&& 0 === preg_match( '/\bus\b/', wp_strip_all_tags( $b389_html ) )
	);
	/* ⛔⛔ THE TRACKING CLAIM. Andrew: there is no tracking. This table adds no
	 *   new instance of it. */
	b389_ok( '§3.31 ⛔⛔ NO tracking claim anywhere in the rendered table', 0 === preg_match( '/\btracking\b/i', $b389_html ) );
	b389_ok(
		'§3.32 ⛔ no rating, review, award, urgency or scarcity claim',
		0 === preg_match( '/\b(rating|reviews?|stars?|award-winning|best-?sell\w*|hurry|only \d+ left|limited time)\b/i', wp_strip_all_tags( $b389_html ) )
	);
	b389_ok(
		'§3.33 ⛔ no outcome claim about the child',
		0 === preg_match( '/\b(will (?:love|read|improve)|turns? your|makes? your child|guaranteed|proven)\b/i', wp_strip_all_tags( $b389_html ) )
	);
	unset( $bhp_compare );
}

/* ⛔ NO PRICE LITERAL IN THE TEMPLATE OR ITS CONTROLLER. This is the assertion
 *   that keeps the live-price discipline true a year from now: the moment
 *   somebody types a dollar figure into either file to "fix" a render, this
 *   goes red. */
$b389_cmp_tpl_code  = b389_code( 'template-parts/commerce/compare-table.php' );
$b389_cmp_ctrl_code = b389_code( 'inc/compare-table.php' );
b389_ok( '§3.34 ⛔⛔ the template contains no hardcoded dollar figure', 0 === preg_match( '/\$\s?\d+\.\d\d/', $b389_cmp_tpl_code ) );
b389_ok( '§3.35 ⛔⛔ the controller contains no hardcoded dollar figure', 0 === preg_match( '/\$\s?\d+\.\d\d/', $b389_cmp_ctrl_code ) );
b389_ok( '§3.36 ⛔ the controller never falls back to a guessed price', false !== strpos( $b389_cmp_ctrl_code, 'return null' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · THE EXPRESS CHECKOUT BRIDGE.
 *
 * ⛔ WHAT §4 CAN AND CANNOT SAY. It can prove the container is printed and that
 *    the plugin's own callback is the thing that prints it. It CANNOT prove a
 *    wallet paints: that depends on the visitor's browser, the Stripe account
 *    and whether the host is a registered payment method domain. Real-browser
 *    evidence is in the build report.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§4 THE EXPRESS CHECKOUT BRIDGE' );

b389_ok( '§4.1 the bridge is loaded', function_exists( 'bhp_express_bridge_render' ) && function_exists( 'bhp_express_bridge_should_render' ) && function_exists( 'bhp_express_bridge_initial_id' ) );

$b389_ece_prio = b389_hook_priority( 'woocommerce_single_product_summary', 'bhp_express_bridge_render' );
b389_ok( '§4.2 it renders at priority 16, just under the format rail', 16 === $b389_ece_prio, 'got ' . var_export( $b389_ece_prio, true ) );

$b389_ece_code = b389_code( 'inc/express-checkout-bridge.php' );

/* ⭐ THE ONE LINE THAT FIXES THE DEFECT, asserted to exist exactly once. Twice
 *   would print two containers. */
b389_ok(
	'§4.3 ⭐ it fires woocommerce_after_add_to_cart_form exactly once',
	1 === substr_count( $b389_ece_code, "do_action('woocommerce_after_add_to_cart_form')" )
);

/* ⭐ AND THE PLUGIN IS ACTUALLY LISTENING. This is the live half: without it,
 *   §4.3 proves only that the theme shouts into an empty room. */
global $wp_filter;
$b389_after_form = isset( $wp_filter['woocommerce_after_add_to_cart_form'] ) ? $wp_filter['woocommerce_after_add_to_cart_form'] : null;
$b389_stripe_on_hook = false;
$b389_hook_count     = 0;
if ( $b389_after_form ) {
	foreach ( $b389_after_form->callbacks as $b389_prio => $b389_cbs ) {
		foreach ( $b389_cbs as $b389_cb ) {
			++$b389_hook_count;
			if ( is_array( $b389_cb['function'] ) && is_object( $b389_cb['function'][0] )
				&& false !== stripos( get_class( $b389_cb['function'][0] ), 'Stripe_Express_Checkout' ) ) {
				$b389_stripe_on_hook = true;
			}
		}
	}
}
b389_ok( '§4.4 ⭐ the Stripe express element IS subscribed to that hook (live read of $wp_filter)', $b389_stripe_on_hook );
/* ⚠ Recorded as a NOTE, not a hard assertion: another plugin adding a callback
 *   here later is not a defect, but it changes what firing the hook does, and
 *   whoever reads this suite next should see the number. */
echo "NOTE: woocommerce_after_add_to_cart_form has {$b389_hook_count} subscriber(s) on this environment.\n";

/* The scaffold the plugin's own JS reads. Selectors verified against
 * `build/express-checkout.js` on the server this build. */
b389_ok( '§4.5 the scaffold is a form.cart', false !== strpos( $b389_ece_code, 'class="cart bhp-express__form"' ) );
b389_ok( '§4.6 it carries .single_add_to_cart_button with the buy id as its value', false !== strpos( $b389_ece_code, 'single_add_to_cart_button' ) && false !== strpos( $b389_ece_code, 'value="<?php echo esc_attr($buy_id); ?>"' ) );
b389_ok( '§4.7 it carries an input.qty', false !== strpos( $b389_ece_code, 'class="qty"' ) );
/* ⛔ IT IS NOT A SECOND BUY BUTTON. Unreachable by pointer, keyboard and
 *   screen reader, while still readable by jQuery .val() and serializeArray(). */
b389_ok( '§4.8 ⛔ the scaffold is hidden from every input modality', false !== strpos( $b389_ece_code, 'aria-hidden="true"' ) && false !== strpos( $b389_ece_code, 'tabindex="-1"' ) );
$b389_bf_css = b389_file( 'assets/css/book-formats.css' );
b389_ok( '§4.9 ⛔ the scaffold is display:none in CSS', 1 === preg_match( '/\.bhp-express__scaffold\s*\{[^}]*display:\s*none/s', $b389_bf_css ) );
/* ⛔ AND THE WRAPPER IS NOT. The Stripe container un-hides itself only when a
 *   wallet mounts; hiding its parent is the one way to build this and still
 *   render nothing. */
b389_ok( '§4.10 ⛔⛔ the VISIBLE wrapper is not hidden (this is the whole feature)', 0 === preg_match( '/\.bhp-express\s*\{[^}]*display:\s*none/s', $b389_bf_css ) );

/* ⛔ THE EXISTING PURCHASE PATH IS UNTOUCHED. */
/* ⚠ THE TRAILING COMMA IS DELIBERATE: the shipped call passes a priority
 *   (`..., 30);`), and the first version of this assertion omitted it and
 *   failed on correct code. Matching up to the comma keeps the assertion true
 *   of the real call without pinning the priority number. */
b389_ok( '§4.11 ⛔ the theme still removes WooCommerce\'s native add-to-cart form', false !== strpos( b389_code( 'inc/book-formats.php' ), "remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart'," ) );
b389_ok( '§4.12 ⛔ the existing add-to-cart ANCHOR is still the visible control', false !== strpos( b389_code( 'template-parts/commerce/format-cards.php' ), 'data-bhp-format-cta' ) );
b389_ok( '§4.13 ⛔ the bridge does not restore the native form hook', false === strpos( $b389_ece_code, "add_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart'" ) );
b389_ok( '§4.14 ⛔ the bridge changes no payment, wallet or Stripe setting', 0 === preg_match( '/update_option|WC_Stripe.*set_|payment_gateways\(\)->/i', $b389_ece_code ) );

/* The mirror script: one source of truth for which edition is selected. */
$b389_ece_js = b389_file( 'assets/js/express-checkout-bridge.js' );
b389_ok( '§4.15 the mirror script ships', '' !== $b389_ece_js );
b389_ok( '§4.16 it mirrors the CTA rather than deciding for itself', false !== strpos( $b389_ece_js, 'data-bhp-format-cta' ) && false !== strpos( $b389_ece_js, 'data-variation-id' ) );
b389_ok( '§4.17 ⛔ it adds nothing to the cart and binds no click handler', false === strpos( $b389_ece_js, 'add-to-cart' ) && false === stripos( $b389_ece_js, 'fetch(' ) );
/*
 * ⚠ ASSERTED ON USAGE, NOT ON THE WORD, AND THIS IS A COMMENT-BLINDNESS FIX
 *   RATHER THAN A RELAXATION. `token_get_all()` cannot strip JavaScript
 *   comments, and that file's own header says in words that it uses no
 *   localStorage and pushes no dataLayer event. A bare substring scan matched
 *   its own disclaimer and reported a defect that does not exist — exactly the
 *   failure `bhp_ei_strip_php_comments()` was written for in the exit-intent
 *   suite. ⛔ Absence claims are asserted against CODE, so this looks for a
 *   property access or an index, which a sentence cannot produce.
 */
b389_ok(
	'§4.18 ⛔ it mints no funnel or analytics state',
	0 === preg_match( '/(?:local|session)Storage\s*[.\[]/', $b389_ece_js )
		&& 0 === preg_match( '/dataLayer\s*[.\[]/', $b389_ece_js )
);

/* ⭐ AND THE CONTAINER ACTUALLY REACHES THE BROWSER. A real HTTP fetch of the
 *   Mariana paperback page. ⚠ SKIPPED, not failed, if the environment cannot
 *   fetch its own page — a false FAIL there would teach the next reader to
 *   ignore a red suite. */
$b389_product = null;
if ( function_exists( 'bhp_book_registry' ) ) {
	$b389_reg = bhp_book_registry();
	if ( isset( $b389_reg['mariana_trench']['pb_product'] ) ) {
		$b389_product = get_permalink( (int) $b389_reg['mariana_trench']['pb_product'] );
	}
}
if ( $b389_product ) {
	$b389_pdp = b389_fetch( $b389_product );
	if ( '' === $b389_pdp ) {
		echo "SKIP: §4.19 the product page could not be fetched from this environment\n";
		echo "SKIP: §3.37 the product page could not be fetched from this environment\n";
	} else {
		b389_ok( '§4.19 ⭐ the Stripe express container is IN THE RENDERED PRODUCT PAGE', false !== strpos( $b389_pdp, 'wc-stripe-express-checkout-element' ) );
		b389_ok( '§4.20 ⭐ exactly one express container is printed', 1 === substr_count( $b389_pdp, 'id="wc-stripe-express-checkout-element"' ) );
		b389_ok( '§4.21 the scaffold reaches the browser with a real buy id', 1 === preg_match( '/single_add_to_cart_button[^>]*value="(\d+)"/', $b389_pdp, $b389_vm ) && (int) $b389_vm[1] > 0 );
		b389_ok( '§4.22 ⛔ the format rail still renders beside it', false !== strpos( $b389_pdp, 'data-bhp-format-cta' ) );
		b389_ok( '§3.37 ⭐ the comparison table is IN THE RENDERED PRODUCT PAGE', false !== strpos( $b389_pdp, 'bhp-compare__table' ) );
		/* ⛔ ORDER ON THE PAGE, not just in the hook table. */
		$b389_p_cmp  = strpos( $b389_pdp, 'bhp-compare__table' );
		$b389_p_card = strpos( $b389_pdp, 'data-bhp-format-initial' );
		/*
		 * ⭐⭐ §3.38 REVERSED IN PLACE, 1.19.391, ON ANDREW SIGNORE'S OWN
		 *     INSTRUCTION, 2026-09-07. His words as carried in the build brief:
		 *     the format cards, price, add-to-cart and the Stripe express
		 *     element stay ABOVE THE FOLD and ABOVE the table; the table moves
		 *     BELOW the purchase block.
		 *
		 * ⛔ THE SUPERSEDED ASSERTION, PRESERVED, because it is the one a
		 *    future reader is most likely to "restore" as an obvious typo:
		 *
		 *      ~~§3.38 and it renders ABOVE the format cards in the actual HTML
		 *        $b389_p_cmp < $b389_p_card~~
		 *
		 * ⛔ THIS IS A FOUNDER RULING, NOT A PREFERENCE. Do not flip it back
		 *    without a fresh one.
		 */
		b389_ok( '§3.38 ⭐ SUPERSEDED 1.19.391: it renders BELOW the format cards in the actual HTML', false !== $b389_p_cmp && false !== $b389_p_card && $b389_p_cmp > $b389_p_card );
		/* ⛔ AND BELOW THE EXPRESS ELEMENT, which is the rest of the purchase block. */
		$b389_p_ece = strpos( $b389_pdp, 'wc-stripe-express-checkout-element' );
		b389_ok( '§3.38a ⛔ and BELOW the Stripe express element', false !== $b389_p_cmp && false !== $b389_p_ece && $b389_p_cmp > $b389_p_ece );
	}
} else {
	echo "SKIP: §4.19 the Mariana paperback permalink could not be resolved\n";
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · THE EXIT-INTENT COPY SET, AND THE STALE HEADER.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§5 THE EXIT-INTENT MODAL' );

b389_ok( '§5.1 the approved eyebrow', false !== strpos( $b389_exit_code, 'A free chapter tonight' ) );
b389_ok( '§5.2 the approved headline, character for character', false !== strpos( $b389_exit_code, 'Before you go, test Chapter 10 with your child tonight for free' ) );
b389_ok( '§5.3 the approved supporting line, character for character', false !== strpos( $b389_exit_code, 'It is a real chapter from The Mariana Trench, about ten minutes of reading, and it arrives with a printable activity and three ways to make it feel like an adventure.' ) );
b389_ok( '§5.4 ⛔ the button is UNCHANGED', false !== strpos( $b389_exit_code, "'Send me the chapter'" ) );
b389_ok( '§5.5 ⛔ the retired 1.19.297 headline is gone from the CODE', false === strpos( $b389_exit_code, 'FREE Chapter for Reluctant Readers' ) );
b389_ok( '§5.6 ⛔ the retired 1.19.296 headline is still gone', false === strpos( $b389_exit_code, 'Before you go, take the free kit.' ) );
/* ⛔ AND THE OLD EYEBROW IS GONE, or the card prints "Before you go" twice. */
b389_ok( '§5.7 ⛔ the old eyebrow does not survive beside the new headline', false === strpos( $b389_exit_code, "esc_html_e('Before you go', 'brave-hearts')" ) );

/* ⛔ WHAT MUST NOT HAVE MOVED. `parent_popup_exit` is a live Mailchimp tag join
 *   key: renaming it orphans the before/after count on this surface. */
b389_ok( '§5.8 ⛔ the Mailchimp context is unchanged', false !== strpos( $b389_exit_code, "'context'              => 'parent_popup_exit'" ) );
b389_ok( '§5.9 ⛔ the funnel storage prefix is unchanged', false !== strpos( $b389_exit_code, "'storagePrefix' => 'bhp_parent_popup'" ) );
b389_ok( '§5.10 ⛔ the funnel event prefix is unchanged', false !== strpos( $b389_exit_code, "'eventPrefix'   => 'parent_popup'" ) );
b389_ok( '§5.11 ⛔ the trust caption is unchanged', false !== strpos( $b389_exit_code, 'Free printable PDF. No purchase required.' ) );
b389_ok( '§5.12 ⛔ the dismiss control is unchanged', false !== strpos( $b389_exit_code, 'No thanks, not tonight' ) );
b389_ok( '§5.13 ⛔ the privacy line is unchanged', false !== strpos( $b389_exit_code, 'Adventure Club updates and resource news. Unsubscribe anytime.' ) );
b389_ok( '§5.14 ⛔ the 20 second dwell floor stands on both devices', 2 === substr_count( $b389_exit_code, "'minDelay' => 20000" ) );
b389_ok( '§5.15 ⛔ nothing in the teacher funnel namespace is touched', false === strpos( $b389_exit_code, 'bhp_mariana_popup' ) && false === strpos( $b389_exit_code, 'teacher' ) );

/* ⭐ THE STALE HEADER. `marketing-growth` found it; this release corrects it.
 *   The correction is ADDITIVE — the wrong words are preserved struck, per the
 *   house discipline — so the assertion is that the correction is PRESENT, not
 *   that the old words are absent. */
$b389_exit_raw = b389_file( 'template-parts/acquisition/exit-intent-popup.php' );
b389_ok( '§5.16 ⭐ the stale "SHIPPED DISABLED" header carries a dated correction', false !== strpos( $b389_exit_raw, 'THE FIVE LINES DIRECTLY ABOVE ARE STALE' ) );
b389_ok( '§5.17 ⭐ and the correction states the popup is live', false !== strpos( $b389_exit_raw, 'THIS POPUP IS LIVE' ) );

/* ⭐⭐ AND THE CODE IS ASKED DIRECTLY, because a comment about a gate is worth
 *     nothing next to the gate itself. This is the live half of §5.16. */
if ( function_exists( 'bhp_should_show_exit_intent_popup' ) ) {
	$b389_fn = new ReflectionFunction( 'bhp_should_show_exit_intent_popup' );
	$b389_fn_src = implode( '', array_slice( file( $b389_fn->getFileName() ), $b389_fn->getStartLine() - 1, $b389_fn->getEndLine() - $b389_fn->getStartLine() + 1 ) );
	b389_ok(
		'§5.18 ⭐ the gate really does default TRUE (read out of the function, not the comment)',
		1 === preg_match( "/apply_filters\(\s*'bhp_show_exit_intent_popup'\s*,\s*true\s*\)/", $b389_fn_src )
	);
} else {
	echo "SKIP: §5.18 bhp_should_show_exit_intent_popup() is not loaded\n";
}

/* Rails on the new strings. */
$b389_exit_new = 'A free chapter tonight Before you go, test Chapter 10 with your child tonight for free It is a real chapter from The Mariana Trench, about ten minutes of reading, and it arrives with a printable activity and three ways to make it feel like an adventure.';
b389_ok( '§5.19 ⛔ no em dash in the new copy', false === strpos( $b389_exit_new, "\xE2\x80\x94" ) );
b389_ok( '§5.20 ⛔ no "we"/"us"/"our" (VOICE §9.1)', 0 === preg_match( '/\b(we|us|our)\b/i', $b389_exit_new ) );
b389_ok( '§5.21 ⛔ no rating, review, award, urgency or scarcity claim', 0 === preg_match( '/\b(rating|reviews?|stars?|award-winning|best-?sell\w*|hurry|only \d+ left|limited time)\b/i', $b389_exit_new ) );
b389_ok( '§5.22 ⛔ no outcome claim about the child', 0 === preg_match( '/\b(will (?:love|read|improve)|turns? your|makes? your child|guaranteed|proven)\b/i', $b389_exit_new ) );
b389_ok( '§5.23 ⛔ no invented contents', 0 === preg_match( '/\b(workbook|worksheets?|audiobook|poster|sticker|lesson plans?|flashcards?)\b/i', $b389_exit_new ) );
b389_ok( '§5.24 ⛔ no tracking claim', 0 === preg_match( '/\btracking\b/i', $b389_exit_new ) );
/* ⭐ THE ONLY DIGITS IN THE NEW COPY ARE THE CHAPTER NUMBER. "about ten
 *   minutes" is written in words, so it carries no numeral, and the chapter
 *   number is verifiable in the served PDF. */
b389_ok( '§5.25 ⭐ the only numeral in the new copy is the live kit\'s chapter number', 0 === preg_match( '/\d/', str_replace( 'Chapter 10', '', $b389_exit_new ) ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · NO SIDE EFFECTS.
 * ═══════════════════════════════════════════════════════════════════════════ */
b389_head( '§6 NO SIDE EFFECTS' );

b389_ok( '§6.1 ⛔ no mail left this suite', 0 === count( bhp_test_mail_log() ) );
b389_ok(
	'§6.2 ⛔ neither new inc file writes an option, a post, a product or a term',
	0 === preg_match( '/\b(update_option|add_option|delete_option|wp_insert_post|wp_update_post|wp_delete_post|update_post_meta|wp_set_object_terms|wc_create_order)\b/', $b389_ece_code . $b389_cmp_ctrl_code )
);
b389_ok(
	'§6.3 ⛔ the new template parts write nothing either',
	0 === preg_match( '/\b(update_option|add_option|wp_insert_post|update_post_meta)\b/', $b389_cmp_tpl_code . $b389_sample_code )
);

echo "\n";
echo 'PASS ' . $b389_pass . '  FAIL ' . count( $b389_failures ) . "\n";
if ( ! empty( $b389_failures ) ) {
	echo "FAILED:\n - " . implode( "\n - ", $b389_failures ) . "\n";
	exit( 1 );
}
echo "RESULT: all assertions passed\n";
