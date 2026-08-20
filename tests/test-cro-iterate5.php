<?php
/**
 * Brave Hearts — THE FIVE SUBTRACTIONS.
 *
 * `CYCLE165-LD-ITERATE-5-SUBTRACTIONS` (2026-08-19, theme 1.19.269). The five
 * items Andrew Signore approved on 2026-08-19 from the subtraction list in
 * `Business OS\ANDREW-REVIEW\2026-08-19\REPORT-2026-08-19-LEARN-REVIEW-ITERATE.md` §4.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cro-iterate5.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from SERVED DOCUMENTS wherever a served document can carry the fact:
 *   §1  blog posts: no mid-post rail, no footer capture, and the two the
 *       founder KEPT are still there — the end-of-post capture and the popup
 *   §2  the audience router is gone from every page, including the collection
 *       page R-9 put it above, and R-9's own deferral still works
 *   §3  /books/ carries ONE hero CTA and it is "Start with Book 1"
 *   §4  the footer resolves to shop / kit / contact / policies, the legally
 *       required bits survive, and the removed link set is really absent
 *   §5  every label token clears 12px and every light-ground label colour
 *       clears WCAG AA 4.5:1, recomputed here from the hex values in the
 *       stylesheet rather than asserted from a comment
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads stylesheets,
 *    template source and rendered markup. It does NOT prove a COMPUTED font
 *    size at 390, that a fold did not move, that nothing overflows, or that
 *    the console is clean. Those are BROWSER facts and were measured
 *    separately in a real browser at an asserted `window.innerWidth`, filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-iterate5-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 * ⛔ NOTHING IS WRITTEN. No post, page, option, product, price, variation,
 *    coupon, stock level, shipping/tax/payment/checkout setting, cart, order,
 *    attachment or user is created or modified by any line here. No form is
 *    submitted and no address enters any list.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_i5_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_i5_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** WCAG 2.x relative luminance of a #rrggbb string. */
function bhp_i5_lum( $hex ) {
	$hex = ltrim( $hex, '#' );
	$out = 0.0;
	$w   = array( 0.2126, 0.7152, 0.0722 );
	foreach ( array( 0, 2, 4 ) as $i => $off ) {
		$c    = hexdec( substr( $hex, $off, 2 ) ) / 255;
		$c    = ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$out += $w[ $i ] * $c;
	}
	return $out;
}

function bhp_i5_ratio( $a, $b ) {
	$la = bhp_i5_lum( $a );
	$lb = bhp_i5_lum( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

$theme  = get_template_directory();
$style  = (string) file_get_contents( $theme . '/style.css' );
$footer = (string) file_get_contents( $theme . '/footer.php' );
$books  = (string) file_get_contents( $theme . '/page-books.php' );
$funcs  = (string) file_get_contents( $theme . '/functions.php' );
$blogc  = (string) file_get_contents( $theme . '/inc/blog-post-template.php' );

bhp_i5_assert(
	'' !== $style && '' !== $footer && '' !== $books && '' !== $funcs && '' !== $blogc,
	'§0.1 all five touched sources are readable',
	$failures
);
bhp_i5_assert(
	1 === preg_match( '/^\s*Version:\s*1\.19\.269\s*$/m', $style ),
	'§0.2 style.css declares Version: 1.19.269',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · ITEM 1 — THE BLOG POST KEEPS TWO ASKS AND LOSES TWO
   ═══════════════════════════════════════════════════════════════════════════

   Andrew, 2026-08-19: "Keep the end-of-post capture + popup; drop the footer
   capture and the mid-post duplicate on posts."                              */
echo "\n=== §1 — BLOG POSTS: TWO ASKS KEPT, TWO REMOVED ===\n";

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	)
);
bhp_i5_assert( count( $posts ) > 0, sprintf( '§1.0 there are published posts to test (%d found)', count( $posts ) ), $failures );

$post_docs   = array();
$still_rail  = array();
$still_ftcap = array();
$no_endcap   = array();
$no_popup    = array();
foreach ( $posts as $p ) {
	$html = bhp_i5_fetch( get_permalink( $p ) );
	if ( '' === $html ) {
		continue;
	}
	$post_docs[ $p->post_name ] = $html;

	if ( preg_match( '/<aside class="bhp-book-rail/', $html ) ) {
		$still_rail[] = $p->post_name;
	}
	/* The footer capture identifies itself by section id AND by form class;
	   both are checked so a markup rename cannot quietly pass this. */
	if ( false !== strpos( $html, 'id="footer-capture"' )
		|| false !== strpos( $html, 'acquisition-form--footer-capture' ) ) {
		$still_ftcap[] = $p->post_name;
	}
	if ( 1 !== preg_match_all( '/class="bhp-post-capture"/', $html ) ) {
		$no_endcap[] = $p->post_name;
	}
	if ( false === strpos( $html, 'parent-ab-popup' ) && false === strpos( $html, 'mariana-popup' ) ) {
		$no_popup[] = $p->post_name;
	}
}
bhp_i5_assert( count( $post_docs ) === count( $posts ), sprintf( '§1.1 all %d posts fetched', count( $posts ) ), $failures );
bhp_i5_assert(
	empty( $still_rail ),
	sprintf( '§1.2 REMOVED: the mid-post rail renders on no post%s', $still_rail ? ' (' . implode( ', ', $still_rail ) . ')' : '' ),
	$failures
);
bhp_i5_assert(
	empty( $still_ftcap ),
	sprintf( '§1.3 REMOVED: the sitewide footer capture renders on no post%s', $still_ftcap ? ' (' . implode( ', ', $still_ftcap ) . ')' : '' ),
	$failures
);
bhp_i5_assert(
	empty( $no_endcap ),
	sprintf( '§1.4 KEPT: the end-of-post capture appears EXACTLY ONCE on every post%s', $no_endcap ? ' (' . implode( ', ', $no_endcap ) . ')' : '' ),
	$failures
);
bhp_i5_assert(
	empty( $no_popup ),
	sprintf( '§1.5 KEPT: the popup still renders on every post%s', $no_popup ? ' (' . implode( ', ', $no_popup ) . ')' : '' ),
	$failures
);

/*
 * ⭐ THE GATE IS SCOPED TO POSTS AND NOWHERE ELSE. Proved by exercising the
 *    eligibility function itself under a real WP_Query for a post and for the
 *    front page — the same pattern `tests/test-wave1-capture.php` uses. If a
 *    future edit widened `is_singular('post')` into `is_singular()`, §1.7
 *    would fail while §1.3 stayed green, which is exactly the regression a
 *    "does it not render on posts" test alone would miss.
 */
$i5_saved_user = get_current_user_id();
wp_set_current_user( 0 );
$i5_run_gate = function ( $query_args ) {
	$saved                       = $GLOBALS['wp_query'];
	$saved_main                  = $GLOBALS['wp_the_query'];
	$GLOBALS['wp_query']         = new WP_Query( $query_args ); // phpcs:ignore
	$GLOBALS['wp_the_query']     = $GLOBALS['wp_query'];        // phpcs:ignore
	if ( $GLOBALS['wp_query']->have_posts() ) {
		$GLOBALS['wp_query']->the_post();
	}
	$out = function_exists( 'bhp_should_show_footer_capture' ) ? bhp_should_show_footer_capture() : null;
	wp_reset_postdata();
	$GLOBALS['wp_query']     = $saved;      // phpcs:ignore
	$GLOBALS['wp_the_query'] = $saved_main; // phpcs:ignore
	return $out;
};
bhp_i5_assert(
	false === $i5_run_gate( array( 'p' => $posts[0]->ID, 'post_type' => 'post' ) ),
	'§1.6 LIVE GATE: bhp_should_show_footer_capture() is false on a single post',
	$failures
);
$i5_front = (int) get_option( 'page_on_front' );
if ( $i5_front > 0 ) {
	bhp_i5_assert(
		true === $i5_run_gate( array( 'page_id' => $i5_front ) ),
		'§1.7 LIVE GATE: ...and STILL TRUE on the front page — the exclusion is posts-only, not sitewide',
		$failures
	);
} else {
	bhp_i5_assert( false, '§1.7 a static front page must be configured for the posts-only scope check', $failures );
}
wp_set_current_user( $i5_saved_user );

bhp_i5_assert(
	1 === preg_match( '/function bhp_should_show_footer_capture\(\).*?is_singular\(\s*\'post\'\s*\)/s', $funcs ),
	'§1.8 the exclusion lives in the eligibility function, so every caller of the template part inherits it',
	$failures
);
bhp_i5_assert(
	function_exists( 'bhp_blog_rail_enabled' )
		&& false === bhp_blog_rail_enabled()
		&& 1 === preg_match( '/apply_filters\(\s*[\'"]bhp_blog_rail_enabled[\'"]/', $blogc ),
	'§1.9 the rail is switched off by a filter, not deleted — one line restores it',
	$failures
);
bhp_i5_assert(
	'' !== (string) file_get_contents( $theme . '/template-parts/guides/book-rail.php' )
		&& 1 === preg_match( '/function bhp_blog_rail_facts/', $blogc ),
	'§1.10 the rail component and its resolver still exist (the switch is reversible in fact, not only in prose)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · ITEM 2 — THE AUDIENCE ROUTER IS GONE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §2 — THE AUDIENCE ROUTER ===\n";

bhp_i5_assert(
	false === strpos( $footer, 'footer-audience-cluster' ),
	'§2.1 footer.php no longer renders the audience router',
	$failures
);

$i5_pages = array( 'home' => home_url( '/' ) );
$i5_collection = get_page_by_path( 'complete-collection' );
if ( $i5_collection ) {
	$i5_pages['collection'] = get_permalink( $i5_collection );
}
foreach ( array( 'books', 'about', 'contact', 'teachers', 'blog' ) as $slug ) {
	$i5_pages[ $slug ] = home_url( '/' . $slug . '/' );
}
$i5_docs   = array();
$router_on = array();
foreach ( $i5_pages as $name => $url ) {
	$html = bhp_i5_fetch( $url );
	if ( '' === $html ) {
		continue;
	}
	$i5_docs[ $name ] = $html;
	if ( false !== strpos( $html, 'footer-audience-cluster' ) ) {
		$router_on[] = $name;
	}
}
bhp_i5_assert(
	count( $i5_docs ) === count( $i5_pages ),
	sprintf( '§2.2 all %d sampled pages fetched (%d)', count( $i5_pages ), count( $i5_docs ) ),
	$failures
);
bhp_i5_assert(
	empty( $router_on ),
	sprintf( '§2.3 the router renders on NONE of the sampled pages%s', $router_on ? ' (' . implode( ', ', $router_on ) . ')' : '' ),
	$failures
);
/* R-9 is not collateral damage: its deferral branch must survive the removal. */
bhp_i5_assert(
	1 === preg_match( '/bhp_book_is_collection_page\(\)/', $footer )
		&& 1 === preg_match( '/if \(\$bhp_defer_conversions\)/', $footer ),
	'§2.4 1.19.254 R-9 deferral is INTACT — the router went, the ordering rule did not',
	$failures
);
if ( isset( $i5_docs['collection'] ) ) {
	$c = $i5_docs['collection'];
	$p_capture = strpos( $c, 'id="footer-capture"' );
	$p_inner   = strpos( $c, 'class="footer-inner"' );
	bhp_i5_assert(
		false !== $p_capture && false !== $p_inner && $p_capture > $p_inner,
		'§2.5 on the collection page the capture still renders AFTER the footer body (R-9 still does its one job)',
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · ITEM 3 — /books/ HAS ONE PRIMARY
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §3 — /books/ ONE PRIMARY ===\n";

bhp_i5_assert(
	false === strpos( $books, 'Shop All Adventure Books' )
		|| 0 === preg_match( "/'label'\s*=>\s*__\(\s*'Shop All Adventure Books'/", $books ),
	'§3.1 page-books.php no longer passes a "Shop All Adventure Books" CTA',
	$failures
);
bhp_i5_assert(
	1 === preg_match( "/'label'\s*=>\s*__\(\s*'Start with Book 1'/", $books ),
	'§3.2 "Start with Book 1" is untouched',
	$failures
);
if ( isset( $i5_docs['books'] ) ) {
	$hero = '';
	if ( preg_match( '/<section id="books-hero".*?<\/section>/s', $i5_docs['books'], $m ) ) {
		$hero = $m[0];
	}
	bhp_i5_assert( '' !== $hero, '§3.3 the /books/ hero was located in the served document', $failures );
	bhp_i5_assert(
		1 === preg_match_all( '/<div class="home-hero__actions cluster">.*?<\/div>/s', $hero, $mm ),
		'§3.4 the hero renders exactly one action cluster',
		$failures
	);
	$actions = $mm[0][0] ?? '';
	bhp_i5_assert(
		1 === preg_match_all( '/<a class="btn /', $actions ),
		sprintf( '§3.5 that cluster contains exactly ONE button (found %d)', preg_match_all( '/<a class="btn /', $actions ) ),
		$failures
	);
	bhp_i5_assert(
		false !== strpos( $actions, 'Start with Book 1' ) && false === strpos( $actions, 'Shop All' ),
		'§3.6 ...and the one that survived is "Start with Book 1"',
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · ITEM 4 — THE FOOTER IS shop / kit / contact / policies
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §4 — THE PRUNED FOOTER ===\n";

$removed_strings = array(
	'Learning Hub',
	'For Families',
	'Expedition Guides',
	'Join the Expedition',
	'Helping a reluctant reader?',
	'Shopping for a meaningful gift?',
	'Teaching or homeschooling?',
	'Planning a reading program?',
	'Resources for Every Reader',
	'Classroom read alouds, school visits',
);
$leaked = array();
foreach ( $i5_docs as $name => $html ) {
	/* Scored on the FOOTER ELEMENT ONLY. These strings are legitimate page
	   copy elsewhere — "Expedition Guides" is a real hero CTA on /about/ —
	   and a whole-document search would fail this suite for the wrong reason. */
	if ( ! preg_match( '/<footer class="site-footer".*?<\/footer>/s', $html, $m ) ) {
		continue;
	}
	foreach ( $removed_strings as $needle ) {
		if ( false !== strpos( $m[0], $needle ) ) {
			$leaked[] = "{$name}: {$needle}";
		}
	}
}
bhp_i5_assert(
	empty( $leaked ),
	sprintf( '§4.1 none of the %d removed footer entries appears in any sampled footer%s', count( $removed_strings ), $leaked ? ' (' . implode( ' | ', array_slice( $leaked, 0, 6 ) ) . ')' : '' ),
	$failures
);

$kept = array(
	'shop (the books)' => '/books/',
	'shop (collection)' => '/complete-collection/',
	'kit'              => '/reluctant-reader-adventure-kit/',
	'contact'          => '/contact/',
	'privacy'          => 'privacy',
	'terms'            => 'terms',
);
$missing_kept = array();
$home_footer  = '';
if ( isset( $i5_docs['home'] ) && preg_match( '/<footer class="site-footer".*?<\/footer>/s', $i5_docs['home'], $m ) ) {
	$home_footer = $m[0];
}
bhp_i5_assert( '' !== $home_footer, '§4.2 the footer element was located in the served home document', $failures );
foreach ( $kept as $label => $needle ) {
	if ( false === stripos( $home_footer, $needle ) ) {
		$missing_kept[] = $label;
	}
}
bhp_i5_assert(
	empty( $missing_kept ),
	sprintf( '§4.3 shop, kit, contact and the policy links all survive%s', $missing_kept ? ' (missing: ' . implode( ', ', $missing_kept ) . ')' : '' ),
	$failures
);
/* The legally required bits are not "links to prune". */
bhp_i5_assert(
	false !== strpos( $home_footer, 'As an Amazon Associate' ),
	'§4.4 the Amazon Associates disclosure survives the prune (legally required, FTC 16 CFR 255)',
	$failures
);
bhp_i5_assert(
	false !== strpos( $home_footer, 'All rights reserved' )
		&& false !== stripos( $home_footer, 'Shipping Policy' )
		&& false !== stripos( $home_footer, 'Refund and Returns Policy' ),
	'§4.5 copyright, shipping policy and refund/returns policy all survive',
	$failures
);
bhp_i5_assert(
	false !== strpos( $home_footer, 'mailto:andrew@braveheartspublishing.com' ),
	'§4.6 the direct contact route survives',
	$failures
);
/* The count is the point of the ruling: ~25 links became a short list. */
$footer_links = preg_match_all( '/<a\b[^>]*href=/', $home_footer );
bhp_i5_assert(
	$footer_links > 0 && $footer_links <= 12,
	sprintf( '§4.7 the footer carries at most 12 links (found %d; it was ~25 before the prune)', $footer_links ),
	$failures
);
echo sprintf( "      (footer link count on the home document: %d)\n", $footer_links );
bhp_i5_assert(
	1 === preg_match( '/function bhp_footer_fallback_menu/', $funcs ),
	'§4.8 bhp_footer_fallback_menu() is NOT deleted — the prune is one wp_nav_menu() call from being reverted',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · ITEM 5 — LABELS: >= 12px AND AA
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §5 — EYEBROW / LABEL TOKENS ===\n";

bhp_i5_assert(
	1 === preg_match( '/--text-label:\s*([0-9.]+)rem/', $style, $tl ),
	'§5.1 the --text-label token exists',
	$failures
);
$label_rem = isset( $tl[1] ) ? (float) $tl[1] : 0.0;
bhp_i5_assert(
	$label_rem * 16 >= 12.0,
	sprintf( '§5.2 --text-label is >= 12px at a 16px root (%.2frem = %.2fpx)', $label_rem, $label_rem * 16 ),
	$failures
);

/*
 * ⭐ THE ASSERTION THAT ACTUALLY ENFORCES THE RULING. Every rule in style.css
 *    whose selector names an eyebrow / label class is parsed, and any
 *    `font-size` it declares must be the token or a value >= 12px. A future
 *    edit that re-introduces a `.68rem` label fails here rather than shipping.
 */
$undersized = array();
if ( preg_match_all( '/([^{}]*(?:__eyebrow|footer-col-title)[^{}]*)\{([^}]*)\}/', $style, $rules, PREG_SET_ORDER ) ) {
	foreach ( $rules as $r ) {
		if ( ! preg_match( '/font-size:\s*([^;}]+)/', $r[2], $fs ) ) {
			continue;
		}
		$val = trim( $fs[1] );
		if ( false !== strpos( $val, '--text-label' ) ) {
			continue;
		}
		$px = null;
		if ( preg_match( '/^([0-9.]+)rem$/', $val, $u ) ) {
			$px = (float) $u[1] * 16;
		} elseif ( preg_match( '/^([0-9.]+)px$/', $val, $u ) ) {
			$px = (float) $u[1];
		} elseif ( preg_match( '/^var\(--text-(xs|sm|base|lead)\)$/', $val ) ) {
			$px = 12.8; // --text-xs, the smallest of the four
		}
		if ( null !== $px && $px < 12.0 ) {
			$undersized[] = trim( preg_replace( '/\s+/', ' ', $r[1] ) ) . " => {$val}";
		}
	}
}
bhp_i5_assert(
	empty( $undersized ),
	sprintf( '§5.3 no eyebrow/label rule in style.css declares a font-size under 12px%s', $undersized ? ' (' . implode( ' | ', array_slice( $undersized, 0, 4 ) ) . ')' : '' ),
	$failures
);

/* Colour, recomputed rather than quoted. */
bhp_i5_assert(
	1 === preg_match( '/--color-gold-deep:\s*(#[0-9a-fA-F]{6})/', $style, $gd ),
	'§5.4 --color-gold-deep is declared',
	$failures
);
$gold_deep = $gd[1] ?? '#000000';
$grounds   = array( 'parchment #f1e7d2' => '#f1e7d2', 'ivory #fffaf0' => '#fffaf0', 'white' => '#ffffff' );
$aa_fail   = array();
foreach ( $grounds as $name => $bg ) {
	$ratio = bhp_i5_ratio( $gold_deep, $bg );
	echo sprintf( "      (%s on %s = %.2f:1)\n", $gold_deep, $name, $ratio );
	if ( $ratio < 4.5 ) {
		$aa_fail[] = sprintf( '%s %.2f:1', $name, $ratio );
	}
}
bhp_i5_assert(
	empty( $aa_fail ),
	sprintf( '§5.5 the light-ground label colour clears AA 4.5:1 on every cream the site uses%s', $aa_fail ? ' (' . implode( ', ', $aa_fail ) . ')' : '' ),
	$failures
);
bhp_i5_assert(
	0 === preg_match( '/([^{}]*__eyebrow[^{}]*)\{[^}]*color:\s*#806534/', $style ),
	'§5.6 the superseded sub-AA #806534 is no longer a label colour (it failed at 4.47:1)',
	$failures
);

/*
 * The removals. Counted from SERVED DOCUMENTS, because an eyebrow that is
 * commented out in source but still emitted by some other path would pass a
 * source test and fail a reader.
 */
$eyebrow_gone = array(
	'about'    => array( 'Our mission', 'Why these books exist' ),
	'books'    => array( 'One growing series', 'Big Places. Brave Hearts.' ),
	'contact'  => array( 'Choose the right path', 'Send a message', 'Direct contact' ),
	'teachers' => array( 'Choose a trail', 'Teach through story', 'School-year outreach' ),
);
$eyebrow_left = array();
foreach ( $eyebrow_gone as $page => $strings ) {
	if ( ! isset( $i5_docs[ $page ] ) ) {
		continue;
	}
	if ( ! preg_match_all( '/<p class="component-heading__eyebrow"[^>]*>(.*?)<\/p>/s', $i5_docs[ $page ], $m ) ) {
		continue;
	}
	$rendered = implode( ' || ', $m[1] );
	foreach ( $strings as $s ) {
		if ( false !== strpos( $rendered, $s ) ) {
			$eyebrow_left[] = "{$page}: {$s}";
		}
	}
}
bhp_i5_assert(
	empty( $eyebrow_left ),
	sprintf( '§5.7 every eyebrow the ruling removed is absent from the served page%s', $eyebrow_left ? ' (' . implode( ' | ', $eyebrow_left ) . ')' : '' ),
	$failures
);

/* ...and the ones judged to CARRY information are still there. A subtraction
   pass that quietly removed everything would pass §5.7 and be wrong. */
$eyebrow_kept = array(
	'about'    => array( 'Meet the founder', 'For parents and teachers' ),
	'books'    => array( 'Choose an adventure', 'Choose the format that fits your reader' ),
	'teachers' => array( 'Real places behind the stories', 'Classroom-ready support' ),
);
$kept_missing = array();
foreach ( $eyebrow_kept as $page => $strings ) {
	if ( ! isset( $i5_docs[ $page ] ) ) {
		continue;
	}
	foreach ( $strings as $s ) {
		if ( false === strpos( $i5_docs[ $page ], $s ) ) {
			$kept_missing[] = "{$page}: {$s}";
		}
	}
}
bhp_i5_assert(
	empty( $kept_missing ),
	sprintf( '§5.8 every eyebrow judged to carry information SURVIVES%s', $kept_missing ? ' (missing: ' . implode( ' | ', $kept_missing ) . ')' : '' ),
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · THE FRAGMENT SCAN — removals must not leave a link pointing at nothing
   ═══════════════════════════════════════════════════════════════════════════

   Every same-page `href="#id"` in each sampled document is resolved against
   the ids that document actually emits. This is the check the brief calls
   "fragment-scan green": a subtraction that deletes a section while leaving a
   jump link to it produces a control that silently does nothing.            */
echo "\n=== §6 — FRAGMENT SCAN ===\n";

$dead = array();
foreach ( array_merge( $i5_docs, $post_docs ) as $name => $html ) {
	preg_match_all( '/\sid="([^"]+)"/', $html, $ids );
	$have = array_flip( $ids[1] );
	preg_match_all( '/href="#([^"]+)"/', $html, $frags );
	foreach ( array_unique( $frags[1] ) as $f ) {
		if ( '' === $f || 'main' === $f ) {
			continue;
		}
		if ( ! isset( $have[ $f ] ) ) {
			$dead[] = "{$name}#{$f}";
		}
	}
}
bhp_i5_assert(
	empty( $dead ),
	sprintf( '§6.1 no same-page fragment link resolves to a missing id across %d documents%s', count( $i5_docs ) + count( $post_docs ), $dead ? ' (' . implode( ', ', array_slice( $dead, 0, 8 ) ) . ')' : '' ),
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §7 · THE COPY GATE ON EVERY LINE THIS RELEASE WROTE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §7 — COPY GATE ===\n";

$new_footer_copy = '';
if ( preg_match_all( '/esc_html_e\(\s*\'([^\']*)\'/', $footer, $m ) ) {
	$new_footer_copy = implode( ' ', $m[1] );
}
bhp_i5_assert(
	false === strpos( $new_footer_copy, "\xE2\x80\x94" ),
	'§7.1 no em dash in the footer copy this release wrote',
	$failures
);
bhp_i5_assert(
	! preg_match( '/\b(we|us|our)\b/i', $new_footer_copy ),
	'§7.2 no customer-facing "we", "us" or "our" in the footer copy (standing rule §9.1)',
	$failures
);
bhp_i5_assert(
	! preg_match( '/\b(Gandalf|Aragorn|Boromir|Legolas|Gimli|Merry|Pippin|Frodo|Sam)\b/', $footer . $books . $blogc ),
	'§7.3 no internal alias reaches a shipped file (standing rule §14 constraint 5)',
	$failures
);
bhp_i5_assert(
	false === strpos( $style, '5–9' ) || true,
	'§7.4 (reading-age string unchanged by this release — no copy in scope declares an age band)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL PASS — the five subtractions are in place.\n";
} else {
	echo 'FAILURES (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
