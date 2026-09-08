<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * HOUSE STYLE ON THE COLLECTION PAGE — `CYCLE165-LD-COLLECTION-CONVERSION`,
 * T-5 and T-9. theme 1.19.254 / bundle 1.8.58, 2026-08-19.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * This is the REGRESSION GUARD for R-12 and R-13. Both were one-character-class
 * fixes to strings that had been live for weeks, and the reason they survived
 * that long is that nothing failed when they appeared. Now something does.
 *
 *   §1  M-11a — ZERO em dashes rendered inside `.bhp-landing`
 *   §2  M-11b — ZERO first-person-plural pronouns rendered inside `.bhp-landing`
 *   §3  M-12  — no `aggregateRating` and no `review` key in any JSON-LD block
 *   §4  B-4 / T-9 — no hardcoded price literal was introduced
 *
 * ⛔ §2 CARRIES A CARVE-OUT THAT IS NOT OPTIONAL AND MUST NOT BE "TIDIED".
 *
 *    The voice rule governs the words the founder puts in front of a customer.
 *    It does NOT govern words somebody else said. A real approved Amazon review
 *    quoted on this site reads "We read a few chapters each night" — rewriting
 *    that "we" would FABRICATE A CUSTOMER STATEMENT, and the never-invent rule
 *    outranks the voice rule every time. So the scan EXCLUDES quoted
 *    third-party material and asserts the exclusion is doing something, rather
 *    than silently passing because the quote happened to be absent.
 *
 * ⛔ §4 IS THE ANTI-DRIFT GUARD FOR EVERY PRICE ON THE PAGE. Every dollar
 *    figure here is read from `bundle-data.php` — the same source the cart and
 *    the shipping logic use — so a re-price follows through in one request. A
 *    literal typed into markup or into a fixture is how a page starts
 *    advertising a price the cart does not charge, which is an FTC-class
 *    defect, not a formatting one. It asserts against SOURCE TEXT, because a
 *    literal that renders identically to the derived figure is invisible in
 *    the HTML and visible only in the code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_hs_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

function bhp_hs_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

echo "\n=== 0. The page renders ===\n";

$page = null;
foreach ( get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'fields'         => 'ids',
	)
) as $id ) {
	$content = get_post_field( 'post_content', $id );
	if ( is_string( $content ) && has_shortcode( $content, 'bhp_complete_series_landing' ) ) {
		$page = $id;
		break;
	}
}
bhp_hs_assert( (bool) $page, '0: a published page carrying [bhp_complete_series_landing] exists', $failures );
if ( ! $page ) {
	fwrite( STDERR, "Cannot continue without the landing page.\n" );
	exit( 1 );
}

$response = wp_remote_get( get_permalink( $page ), array( 'timeout' => 30, 'sslverify' => false ) );
$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
bhp_hs_assert( 200 === $code, "0: the page returns HTTP 200 (got {$code})", $failures );
if ( 200 !== $code ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

/*
 * THE SCOPE. `.bhp-landing` is the shortcode's own wrapper — this page's copy,
 * and the only copy this release is answerable for. The site header, the site
 * footer and the consent banner are governed by their own suites; sweeping
 * them here would either fail on approved copy this release did not write, or
 * have to be loosened until it proved nothing.
 */
$hs_open = strpos( $html, '<div class="bhp-landing" data-bhp-landing>' );
bhp_hs_assert( false !== $hs_open, '0: the .bhp-landing wrapper is present', $failures );
$hs_close  = false !== $hs_open ? strrpos( $html, 'bhp-landing-stickybar' ) : false;
$hs_region = ( false !== $hs_open && false !== $hs_close && $hs_close > $hs_open )
	? substr( $html, $hs_open, $hs_close - $hs_open )
	: '';
bhp_hs_assert( strlen( $hs_region ) > 10000, '0: the landing region is non-trivial and extractable (' . strlen( $hs_region ) . ' bytes)', $failures );

/* Tags out, entities decoded — the scans are about what a READER sees. */
$hs_text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( $hs_region, ENT_QUOTES, 'UTF-8' ) ) ) );

echo "\n=== 1. M-11a — zero em dashes inside .bhp-landing (R-13's guard) ===\n";

$hs_emdash = substr_count( $hs_text, "\u{2014}" );
bhp_hs_assert(
	0 === $hs_emdash,
	"1: no em dash renders anywhere inside .bhp-landing ({$hs_emdash} found)",
	$failures
);
/*
 * The specific one R-13 removed, asserted by name. The count above would catch
 * its return, but only this names what came back.
 */
bhp_hs_assert(
	strpos( $hs_text, 'Guarantee — if these books' ) === false,
	'1: the guarantee\'s em dash construction specifically stays gone',
	$failures
);

echo "\n=== 2. M-11b — zero first-person-plural inside .bhp-landing (R-12's guard) ===\n";

/*
 * ⛔ THE QUOTED-WORDS CARVE-OUT. Approved third-party quotes are removed from
 *    the haystack BEFORE the scan, because a pronoun inside somebody else's
 *    sentence is their word, not the founder's, and "fixing" it would invent a
 *    customer statement.
 */
$hs_quotes = array();
if ( function_exists( 'bhp_bundle_landing_testimonial_quote' ) ) {
	$hs_quotes[] = html_entity_decode( bhp_bundle_landing_testimonial_quote(), ENT_QUOTES, 'UTF-8' );
}
if ( function_exists( 'bhp_get_kirkus_review_data' ) ) {
	$hs_k = bhp_get_kirkus_review_data();
	if ( ! empty( $hs_k['quote'] ) ) {
		$hs_quotes[] = html_entity_decode( (string) $hs_k['quote'], ENT_QUOTES, 'UTF-8' );
	}
}
if ( function_exists( 'bhp_get_approved_amazon_reviews_for_book' ) ) {
	foreach ( array( 'mariana_trench', 'mount_everest', 'amazon_rainforest' ) as $hs_slug ) {
		foreach ( (array) bhp_get_approved_amazon_reviews_for_book( $hs_slug ) as $hs_review ) {
			foreach ( array( 'body', 'quote', 'text', 'title' ) as $hs_field ) {
				if ( ! empty( $hs_review[ $hs_field ] ) ) {
					$hs_quotes[] = html_entity_decode( (string) $hs_review[ $hs_field ], ENT_QUOTES, 'UTF-8' );
				}
			}
		}
	}
}
bhp_hs_assert(
	count( $hs_quotes ) > 0,
	'2: the third-party quote registry resolved (' . count( $hs_quotes ) . ' entries) — the carve-out is real, not a no-op',
	$failures
);

$hs_own = $hs_text;
foreach ( $hs_quotes as $hs_q ) {
	$hs_q = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $hs_q ) ) );
	if ( '' !== $hs_q ) {
		$hs_own = str_replace( $hs_q, ' ', $hs_own );
	}
}

/*
 * Word-boundary, case-insensitive, and possessive/contracted forms included.
 * `\b` alone would miss "we've" only if the apostrophe were curly, so both
 * apostrophes are handled by having decoded entities above.
 */
$hs_plural_hits = array();
foreach ( array( 'we', 'our', 'ours', 'us', "we're", "we've", "we'll", "we'd" ) as $hs_word ) {
	$hs_n = preg_match_all( '/\b' . preg_quote( $hs_word, '/' ) . '\b/iu', $hs_own );
	if ( $hs_n > 0 ) {
		$hs_plural_hits[] = "{$hs_word} x{$hs_n}";
	}
}
bhp_hs_assert(
	empty( $hs_plural_hits ),
	'2: no first-person-plural pronoun renders in the page\'s own copy' . ( $hs_plural_hits ? ' (found: ' . implode( ', ', $hs_plural_hits ) . ')' : '' ),
	$failures
);
/* The specific string R-12 corrected, asserted by name in both directions. */
bhp_hs_assert(
	strpos( $html, 'reader reviews on our first two titles' ) === false,
	'2: the superseded "on our first two titles" wording stays gone',
	$failures
);
bhp_hs_assert(
	strpos( $html, 'reader reviews on my first two titles' ) !== false,
	'2: and its replacement renders, with the scope qualifier intact (the claim did not widen)',
	$failures
);

echo "\n=== 3. M-12 — no fabricated rating or review schema ===\n";

$hs_ld = array();
if ( preg_match_all( '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/su', $html, $hs_ldm ) ) {
	$hs_ld = $hs_ldm[1];
}
bhp_hs_assert( count( $hs_ld ) > 0, '3: at least one JSON-LD block renders (' . count( $hs_ld ) . ' found)', $failures );
$hs_agg = 0;
$hs_rev = 0;
foreach ( $hs_ld as $hs_block ) {
	$hs_agg += substr_count( $hs_block, 'aggregateRating' );
	$hs_rev += preg_match_all( '/"review"\s*:/', $hs_block );
}
bhp_hs_assert( 0 === $hs_agg, "3: no aggregateRating is emitted anywhere ({$hs_agg} found)", $failures );
bhp_hs_assert( 0 === $hs_rev, "3: no \"review\" key is emitted anywhere ({$hs_rev} found)", $failures );

echo "\n=== 4. B-4 / T-9 — no hardcoded price literal was introduced ===\n";

/*
 * The six figures the page currently displays, all of them DERIVED at render
 * time. Any of them appearing as a literal in the renderer or in a fixture
 * means the page has stopped tracking `bundle-data.php`.
 */
$hs_literals = array( '31.99', '48.99', '35.97', '53.97', '3.98', '4.98' );
$hs_files    = array();
if ( defined( 'BHP_BUNDLE_PRICING_DIR' ) ) {
	$hs_files['bundle-landing-page.php'] = BHP_BUNDLE_PRICING_DIR . 'includes/bundle-landing-page.php';
}
$hs_files['tests/test-collection-fold-390.php']   = get_template_directory() . '/tests/test-collection-fold-390.php';
$hs_files['tests/test-collection-house-style.php'] = get_template_directory() . '/tests/test-collection-house-style.php';

/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⭐ CORRECTED AFTER THIS ASSERTION'S FIRST RUN ON STAGING, AND RECORDED
 *     RATHER THAN QUIETLY PATCHED. THE TEST WAS WRONG; THE CODE WAS RIGHT.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The superseded needle, preserved verbatim so the movement is visible:
 *
 *   $hs_c = substr_count( $hs_src, $hs_lit );
 *
 * It counted the six figures ANYWHERE in the file's raw text and read
 * "31.99 x4, 48.99 x5, 35.97 x2, 53.97 x2, 3.98 x2, 4.98 x1" — a FAILURE on
 * a correct build. ⛔ EVERY ONE OF THOSE OCCURRENCES IS INSIDE A COMMENT, and
 * they are load-bearing comments: the docblock recording the live WP-CLI
 * verification of the price arithmetic, the docblock stating that no literal
 * exists in the price function, the founder's own quoted instruction not to
 * lead with the hardcover price, and preserved superseded markup. ⛔ THE
 * COUNTS ARE IDENTICAL TO THE 1.19.253 BASELINE — this release introduced
 * exactly zero of them. A guard that fails on its own documentation trains a
 * reader to ignore a red suite, which is worse than no guard.
 *
 * ⭐ THE NEEDLE NOW ASSERTS THE PROPERTY THAT WAS ALWAYS MEANT: no literal in
 *   CODE. `token_get_all()` is PHP's own lexer, so comments are removed
 *   exactly, not approximately — a regex would trip over `$` in a docblock and
 *   over apostrophes in prose, which is how the first form got this wrong.
 *   Inline HTML is KEPT in the haystack deliberately: a price typed straight
 *   into markup is the exact defect this is here to catch.
 */
foreach ( $hs_files as $hs_label => $hs_path ) {
	if ( ! is_readable( $hs_path ) ) {
		bhp_hs_skip( "4: {$hs_label} is not readable in this deployment", $skipped );
		continue;
	}
	if ( ! function_exists( 'token_get_all' ) ) {
		bhp_hs_skip( '4: the tokenizer extension is unavailable, so the code-only scan cannot be made honestly', $skipped );
		break;
	}
	$hs_code = '';
	foreach ( token_get_all( (string) file_get_contents( $hs_path ) ) as $hs_tok ) {
		if ( is_array( $hs_tok ) ) {
			if ( T_COMMENT === $hs_tok[0] || T_DOC_COMMENT === $hs_tok[0] ) {
				continue; // documentation, not output
			}
			$hs_code .= $hs_tok[1];
			continue;
		}
		$hs_code .= $hs_tok;
	}
	/*
	 * ⚠ THIS FILE IS ITS OWN HAYSTACK — the needle array is real code here, not
	 *   a comment, so the tokenizer keeps it. It is removed by name.
	 */
	$hs_code = str_replace( "'31.99', '48.99', '35.97', '53.97', '3.98', '4.98'", '', $hs_code );
	$hs_found = array();
	foreach ( $hs_literals as $hs_lit ) {
		$hs_c = substr_count( $hs_code, $hs_lit );
		if ( $hs_c > 0 ) {
			$hs_found[] = "{$hs_lit} x{$hs_c}";
		}
	}
	bhp_hs_assert(
		empty( $hs_found ),
		"4: {$hs_label} contains no displayed-price literal in CODE" . ( $hs_found ? ' (found: ' . implode( ', ', $hs_found ) . ')' : '' ),
		$failures
	);
}

/* And the derived path still resolves, so "no literal" cannot mean "no price". */
if ( function_exists( 'bhp_bundle_landing_price_facts' ) ) {
	$hs_facts = bhp_bundle_landing_price_facts( 'paperback' );
	bhp_hs_assert(
		! empty( $hs_facts['live'] ) && $hs_facts['bundle'] > 0 && $hs_facts['separate'] > $hs_facts['bundle'],
		'4: the paperback price still resolves LIVE from WooCommerce and the strike is a true one',
		$failures
	);
} else {
	bhp_hs_skip( '4: bhp_bundle_landing_price_facts is unavailable in this deployment', $skipped );
}

echo "\n";
if ( $skipped ) {
	echo 'SKIPPED: ' . count( $skipped ) . "\n";
}
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
exit( 0 );
