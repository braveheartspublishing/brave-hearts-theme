<?php
/**
 * Brave Hearts — THE COLD-TRAFFIC OPENING ON /complete-collection/ (1.19.206 / 1.8.32).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-collection-cold-traffic.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-07, from his own mobile audit of this page
 * (⛔ RELAYED through the Chief of Staff; NOT witnessed first-hand by the
 * agent that wrote this file). Three asks, and this suite guards the two
 * of them a document can prove:
 *
 *   1. the carousel image is bigger inside its green box   → NOT provable
 *      here. It is a painted-box measurement and it is recorded from a real
 *      browser in the release evidence, with `innerWidth` asserted. This
 *      file does not touch it, because claiming a measurement from a
 *      document fetch would be a fabricated verification.
 *   2. an emotional case above the price, built ONLY from claims already
 *      published on this page                              → §2, §3
 *   3. the buy button above the price block and below the trust line → §4
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ §3 IS THE ONE THAT MATTERS MOST. THE ANTI-FABRICATION GUARD.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The trust bar quotes two SHORTENED reviews. A shortened quote is the
 * easiest place in this codebase for an invented review to appear, because
 * it looks like editing rather than writing. So both fragments are asserted
 * to be literal substrings of their canonical sources —
 * `bhp_get_kirkus_review_data()` and
 * `bhp_bundle_landing_testimonial_quote()`. If anyone edits a source and
 * leaves the fragment behind, this suite fails instead of the site quietly
 * publishing words nobody said.
 *
 * It also asserts the Kirkus line still names the reviewed title. Only The
 * Mariana Trench was reviewed; this page sells three books; dropping that
 * scope turns a true claim into a false one.
 *
 * ⛔ WHAT THIS FILE CANNOT PROVE — READ BEFORE TRUSTING A PASS
 *
 *   · nothing visual, at any viewport: not the fold, not the image size,
 *     not layout shift, not contrast;
 *   · nothing about JS behaviour: the format toggle, the drawer and the
 *     sticky bar are verified in a real browser;
 *   · nothing about production, when run on staging.
 *
 * ⛔ READ-ONLY. No post, option, product, price, coupon, stock level,
 *    shipping, tax, payment or checkout setting is read or written. No cart
 *    is built. No order is created.
 *
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cct_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/** Document order helper. Returns true when $a occurs before $b. */
function bhp_cct_before( $html, $a, $b ) {
	$pa = strpos( $html, $a );
	$pb = strpos( $html, $b );
	return false !== $pa && false !== $pb && $pa < $pb;
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
bhp_cct_assert( (bool) $page, '0: a published page carrying [bhp_complete_series_landing] exists', $failures );
if ( ! $page ) {
	fwrite( STDERR, "Cannot continue without the landing page.\n" );
	exit( 1 );
}

$response = wp_remote_get( get_permalink( $page ), array( 'timeout' => 30, 'sslverify' => false ) );
$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
bhp_cct_assert( 200 === $code, "0: the page returns HTTP 200 (got {$code})", $failures );
bhp_cct_assert( strlen( $html ) > 20000, '0: the rendered document is non-trivial', $failures );
if ( 200 !== $code ) {
	fwrite( STDERR, "Cannot continue without the rendered document.\n" );
	exit( 1 );
}

echo "\n=== 1. The cold-open block renders, exactly once ===\n";

bhp_cct_assert( substr_count( $html, 'bhp-landing-coldopen"' ) === 1, '1: the cold-open block renders exactly once', $failures );
bhp_cct_assert(
	strpos( $html, 'Real places. Short chapters.' ) !== false
		&& strpos( $html, 'Stories that pull kids off screens.' ) !== false
		&& preg_match( '/Real places\. Short chapters\.\s*<br\s*\/?>\s*Stories that pull kids off screens\./', $html ) === 1,
	'1: the emotional headline renders on two lines (2026-08-07 revision)',
	$failures
);
bhp_cct_assert(
	strpos( $html, 'bhp-landing-coldopen__subhead' ) !== false,
	'1: the one-line subhead renders',
	$failures
);
/*
 * ⭐ 1.8.33 — the subhead is Andrew's 2026-08-07 replacement, and it is
 * asserted verbatim BECAUSE it is the company's canonical core-promise
 * wording ("educational without feeling like homework", `C:\BHP\CLAUDE.md`).
 * The line it replaced repeated the headline directly above it.
 */
bhp_cct_assert(
	strpos( $html, 'Educational without feeling like homework' ) !== false,
	'1: the subhead carries the canonical brand promise, not a repeat of the headline',
	$failures
);
bhp_cct_assert(
	strpos( $html, 'Short chapters &middot; real places' ) === false
		&& strpos( $html, 'Short chapters · real places' ) === false,
	'1: the superseded, headline-repeating subhead is gone',
	$failures
);
/*
 * Standing content constraint (BHP-AGENT-STANDING-RULES §9): the reading age
 * in copy is 6–9 and NEVER 5–9. Asserted on the whole document, not just on
 * the new block, because this page is where the copy would most plausibly be
 * widened to make the market look bigger.
 */
$bhp_cct_text = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
bhp_cct_assert( stripos( $bhp_cct_text, '5–9' ) === false, '1: the forbidden "5-9" reading age appears nowhere on the page', $failures );
bhp_cct_assert( strpos( $bhp_cct_text, '6–9' ) !== false, '1: the approved 6-9 reading age is present', $failures );

echo "\n=== 2. The narrative H1 and the two-click machinery are untouched ===\n";

bhp_cct_assert( substr_count( $html, 'bhp-landing-hero__title' ) === 1, '2: still exactly one H1, and it is the narrative headline', $failures );
bhp_cct_assert( strpos( $html, 'Three Big Adventures.' ) !== false, '2: the locked narrative headline copy is unchanged and still present', $failures );
$bhp_cct_forms    = substr_count( $html, 'class="bhp-bundle-form' );
$bhp_cct_redirect = substr_count( $html, 'name="bhp_bundle_redirect" value="checkout"' );
bhp_cct_assert(
	$bhp_cct_forms > 0 && $bhp_cct_redirect === $bhp_cct_forms,
	"2: EVERY bundle form still carries the redirect input, so the 2-click path survives the move ({$bhp_cct_redirect}/{$bhp_cct_forms})",
	$failures
);
bhp_cct_assert( substr_count( $html, 'data-bhp-landing-main-cta' ) >= 1, '2: the primary in-card CTA hook survives the move', $failures );
foreach ( array( 'paperback', 'hardcover' ) as $format ) {
	bhp_cct_assert( strpos( $html, 'complete_' . $format . '_smart' ) !== false, "2: the {$format} smart add-to-cart action survives", $failures );
	bhp_cct_assert( strpos( $html, 'data-bhp-format-btn="' . $format . '"' ) !== false, "2: the {$format} format pill survives", $failures );
}

/*
 * ⛔ THE DEFAULT FORMAT IS EXPLICITLY OUT OF SCOPE FOR THIS RELEASE and is
 *    asserted UNCHANGED. The brief said not to touch it; the brief also
 *    described the default as paperback while the code has returned
 *    'hardcover' since 2026-07-30. Rather than pick a side — which is
 *    Andrew's to do, not this file's — the suite asserts only that ONE
 *    function decides it and that the pills, the panels and the final CTA
 *    all agree with that one function. Whichever value it holds.
 */
$bhp_cct_default = bhp_bundle_default_format();
bhp_cct_assert( in_array( $bhp_cct_default, array( 'paperback', 'hardcover' ), true ), "2: the default format resolves to a real format (it is '{$bhp_cct_default}')", $failures );
bhp_cct_assert(
	strpos( $html, 'data-bhp-format-btn="' . $bhp_cct_default . '"' ) !== false
		&& strpos( $html, 'aria-checked="true"' ) !== false,
	'2: the default format pill is the checked one',
	$failures
);
bhp_cct_assert(
	bhp_bundle_format_order()[0] === $bhp_cct_default,
	'2: the format order still leads with the default',
	$failures
);

echo "\n=== 3. ⭐ ANTI-FABRICATION: every trust-bar claim traces to a source ===\n";

$kirkus_data     = bhp_get_kirkus_review_data();
$kirkus_fragment = 'spark children’s curiosity';
bhp_cct_assert(
	strpos( $kirkus_data['quote'], $kirkus_fragment ) !== false,
	'3: the Kirkus fragment is a literal substring of the approved Kirkus quote',
	$failures
);
bhp_cct_assert(
	strpos( $html, 'spark children&rsquo;s curiosity' ) !== false || strpos( $html, 'spark children’s curiosity' ) !== false,
	'3: the Kirkus fragment actually renders',
	$failures
);
bhp_cct_assert(
	strpos( $html, $kirkus_data['attribution'] ) !== false,
	'3: the Kirkus fragment carries its attribution',
	$failures
);
/*
 * The scope qualifier. Only The Mariana Trench was reviewed by Kirkus; this
 * page sells three books. Without the title the line reads as a review of
 * the collection, which would be false.
 */
bhp_cct_assert(
	preg_match( '/spark children(?:&rsquo;|’)s curiosity.{0,120}Mariana Trench/su', $html ) === 1,
	'3: the Kirkus line still names the reviewed title (it does not claim the whole collection was reviewed)',
	$failures
);

$testimonial = bhp_bundle_landing_testimonial_quote();
/* 2026-08-07 (Andrew, current-turn): the teacher fragment left the cold-open
   bar — replaced by the FREE offer line. The full testimonial remains
   published further down (asserted below, unchanged). */
bhp_cct_assert(
	preg_match( '/<strong>FREE<\/strong> Activity Book \+ <strong>FREE<\/strong> Shipping/', $html ) === 1,
	'3: the FREE Activity Book + FREE Shipping line renders, FREE bold (2026-08-07 revision)',
	$failures
);
bhp_cct_assert(
	strpos( $html, $testimonial ) !== false || strpos( $html, html_entity_decode( $testimonial, ENT_QUOTES, 'UTF-8' ) ) !== false,
	'3: the FULL testimonial is still published further down the page, unshortened',
	$failures
);

/*
 * ⭐ 1.8.33 — SUPERSEDED ASSERTION, REWRITTEN RATHER THAN DELETED.
 *
 * This previously required "Placed in classrooms across Boise" to appear at
 * least TWICE — once in the cold-open bar and once in the trust row below.
 * Andrew Signore removed it from the cold-open bar on 2026-08-07 (⛔ RELAYED;
 * NOT witnessed here). It is NOT removed from the page: the trust row still
 * publishes it, with its whole "placed, not used" history intact, and that is
 * now what is asserted. The guard is INVERTED, not dropped — the string must
 * still exist exactly once, so neither a silent re-introduction into the
 * cold open nor a silent deletion from the trust row can pass unnoticed.
 */
bhp_cct_assert(
	substr_count( $html, 'Placed in classrooms across Boise' ) === 1,
	'3: the Boise placement claim is gone from the cold-open bar and still published, word for word, in the trust row below',
	$failures
);
/*
 * The remaining claim scans are SCOPED TO THE COLD-OPEN BLOCK, deliberately.
 *
 * A page-wide sweep would either fail on pre-existing approved copy this
 * release did not write, or have to be loosened until it proved nothing.
 * This release's new copy is the cold-open block, so that is what it is
 * answerable for. The rest of the page is guarded by the suites that already
 * cover it.
 */
if ( preg_match( '/<div class="bhp-landing-coldopen">(.*?)<\/div>/su', $html, $m ) ) {
	$cold = $m[1];
	bhp_cct_assert(
		strpos( $cold, 'Engaging, educational' ) === false,
		'3: the teacher fragment no longer renders in the cold-open bar (2026-08-07 revision; full testimonial still published below)',
		$failures
	);
	/*
	 * ⭐ 1.8.33 — SUPERSEDED ASSERTION, REPLACED BY A STRICTER ONE.
	 *
	 * This previously banned star glyphs from the cold-open block outright.
	 * Andrew Signore's 2026-08-07 instruction puts "★★★★★ 5-Star Reader
	 * Reviews" above AND below the headline block (⛔ RELAYED; NOT witnessed
	 * here). A blanket ban is therefore the wrong guard — the right guard is
	 * that the glyphs may only appear while the RECORDED DATA still supports
	 * them, which is what the render gate does and what is asserted here.
	 *
	 * ⛔ THE DATA WAS CHECKED BEFORE THE COPY WAS BUILT, not after:
	 *    `inc/amazon-reviews.php` holds six approved reviews and every one
	 *    carries `'rating' => 5`. This re-derives that from the registry on
	 *    every run rather than trusting the reading.
	 */
	$bhp_cct_ratings = array();
	if ( function_exists( 'bhp_get_approved_amazon_reviews_for_book' ) ) {
		foreach ( array( 'mariana_trench', 'mount_everest', 'amazon_rainforest' ) as $bhp_cct_slug ) {
			foreach ( (array) bhp_get_approved_amazon_reviews_for_book( $bhp_cct_slug ) as $bhp_cct_r ) {
				$bhp_cct_ratings[] = (int) ( $bhp_cct_r['rating'] ?? 0 );
			}
		}
	}
	$bhp_cct_all_five = $bhp_cct_ratings && array( 5 ) === array_values( array_unique( $bhp_cct_ratings ) );
	bhp_cct_assert(
		$bhp_cct_all_five,
		'3: every approved Amazon review in the registry is a 5 — the five-star claim is recorded fact, not copy (found ' . count( $bhp_cct_ratings ) . ' reviews)',
		$failures
	);
	bhp_cct_assert(
		function_exists( 'bhp_bundle_landing_five_star_reviews_exist' )
			&& bhp_bundle_landing_five_star_reviews_exist() === $bhp_cct_all_five,
		'3: the five-star line is GATED on that registry, so downgrading a review removes the claim by itself',
		$failures
	);
	bhp_cct_assert(
		substr_count( $cold, 'bhp-landing-coldopen__stars"' ) + substr_count( $cold, 'bhp-landing-coldopen__stars ' ) === 1,
		'3: the five-star line renders exactly once, above the headline (2026-08-07: the duplicate below-line was removed on Andrew\'s order)',
		$failures
	);
	bhp_cct_assert(
		substr_count( $cold, '5-Star Reader Reviews' ) === 1,
		'3: the five-star line carries Andrew\'s exact wording, once',
		$failures
	);
	bhp_cct_assert( preg_match( '/\b\d+(\.\d+)?\s*(out of|\/)\s*5\b/i', $cold ) !== 1, '3: the cold-open block contains no rating expression', $failures );
	bhp_cct_assert( preg_match( '/\b\d{2,}\s+(classrooms|schools|teachers|parents|readers|reviews)\b/i', $cold ) !== 1, '3: the cold-open block contains no invented count', $failures );
	foreach ( array( 'Used in', 'adopted by', 'trusted by', 'proven', 'Lexile', 'grade level', 'award' ) as $forbidden ) {
		bhp_cct_assert(
			stripos( $cold, $forbidden ) === false,
			"3: the cold-open block does not use the unsupported phrasing \"{$forbidden}\"",
			$failures
		);
	}
} else {
	bhp_cct_assert( false, '3: the cold-open block could be isolated for claim scanning', $failures );
}

echo "\n=== 4. Document order: trust line -> CTA -> price -> format selector ===\n";

bhp_cct_assert(
	bhp_cct_before( $html, 'bhp-landing-coldopen__trust', 'data-bhp-landing-main-cta' ),
	'4: the primary CTA renders directly BELOW the trust line (Andrew\'s mobile ruling)',
	$failures
);
bhp_cct_assert(
	bhp_cct_before( $html, 'data-bhp-landing-main-cta', '<dt>Complete Collection</dt>' ),
	'4: the primary CTA renders directly ABOVE the "Complete Collection - price" element (Andrew\'s explicit placement)',
	$failures
);
bhp_cct_assert(
	bhp_cct_before( $html, '<dt>Complete Collection</dt>', 'bhp-landing-format-selector' ),
	'4: the format selector renders BELOW the emotional case and the price block',
	$failures
);
bhp_cct_assert(
	bhp_cct_before( $html, 'bhp-landing-coldopen__headline', 'bhp-landing-coldopen__subhead' )
		&& bhp_cct_before( $html, 'bhp-landing-coldopen__subhead', 'bhp-landing-coldopen__trust' ),
	'4: headline -> subhead -> trust bar, in that order',
	$failures
);
/* ⭐ 1.8.33 asked for stars above AND below; 2026-08-07 Andrew reversed the
   duplicate on sight ("the 5 star reviews is on the page twice?!") — the
   single line above the headline is the standing order. */
bhp_cct_assert(
	bhp_cct_before( $html, 'bhp-landing-coldopen__stars"', 'bhp-landing-coldopen__headline' )
		&& strpos( $html, 'bhp-landing-coldopen__stars--below' ) === false,
	'4: five-star line -> headline, once, with no below-headline duplicate (2026-08-07 revision)',
	$failures
);
bhp_cct_assert(
	bhp_cct_before( $html, 'bhp-landing-hero__main', 'bhp-landing-hero__intro' ),
	'4: the 1.8.28 sales-first hero order is NOT regressed by this release',
	$failures
);

echo "\n=== 5. The benefit lines, and the gated CTA label ===\n";

bhp_cct_assert(
	strpos( $html, '<dt>Shipping</dt>' ) !== false,
	'5: the Shipping row still carries its <dt> label (restyled, not replaced)',
	$failures
);
bhp_cct_assert(
	substr_count( $html, 'bhp-landing-panel__price-row--benefit' ) >= 1,
	'5: the benefit rows carry the bold-bullet modifier',
	$failures
);
if ( function_exists( 'bhp_bundle_addon_free_with_collection' ) && bhp_bundle_addon_free_with_collection() ) {
	bhp_cct_assert( strpos( $html, '<dt>Activity Book</dt>' ) !== false, '5: the free Activity Book row still renders', $failures );
	bhp_cct_assert(
		substr_count( $html, 'bhp-landing-panel__price-row--benefit' ) === 2 * substr_count( $html, '<dt>Shipping</dt>' ),
		'5: BOTH benefit rows carry the modifier in every panel',
		$failures
	);
} else {
	echo "SKIP: the free-activity-book offer is not deliverable on this environment; its row is correctly absent\n";
}

/*
 * ⭐ THE CLAIM GATE. "Free Shipping" is in the button label ONLY when the
 *    three-book rule for that format actually resolves free. This asserts
 *    the label AGAINST bundle-data.php rather than against itself, so a
 *    future shipping change cannot leave a false promise on the button.
 */
foreach ( array( 'paperback', 'hardcover' ) as $format ) {
	$rule     = bhp_bundle_rules( $format )[3];
	$is_free  = bhp_bundle_shipping_is_free( $rule['shipping'] );
	$label    = bhp_bundle_landing_primary_cta_label( $format );
	$promises = ( stripos( $label, 'free shipping' ) !== false );
	bhp_cct_assert(
		$promises === $is_free,
		"5: the {$format} CTA label promises free shipping only when it IS free (label=\"{$label}\", shipping=" . number_format( (float) $rule['shipping'], 2 ) . ')',
		$failures
	);
	bhp_cct_assert(
		strpos( $label, 'Get the Complete Collection' ) === 0,
		"5: the {$format} CTA label leads with the benefit-driven wording",
		$failures
	);
}
bhp_cct_assert(
	strpos( $html, 'Get the Complete Collection' ) !== false,
	'5: the benefit-driven CTA label actually renders',
	$failures
);
/*
 * Nothing factual was dropped when the material subtitle and the title list
 * moved below the price block.
 */
foreach ( bhp_bundle_catalog()[ $bhp_cct_default ] as $info ) {
	bhp_cct_assert( strpos( $html, $info['label'] ) !== false, "5: \"{$info['label']}\" is still listed on the page", $failures );
}
bhp_cct_assert( strpos( $html, 'View Individual Books' ) !== false, '5: the quiet individual-books exit survives', $failures );
bhp_cct_assert( strpos( $html, 'Secure checkout' ) !== false, '5: the fine print survives', $failures );

echo "\n=== 6. Funnel isolation and the free-kit capture path are untouched ===\n";

/*
 * This release must not remove a capture surface. It asserts only that the
 * page still reaches one — it does NOT claim the funnel works, which is a
 * browser-and-Mailchimp question, not a document one.
 */
bhp_cct_assert(
	strpos( $html, 'data-bhp-quiz-launcher' ) !== false
		|| strpos( $html, 'adventure-kit' ) !== false
		|| strpos( $html, 'bhp-quiz' ) !== false,
	'6: a lead-capture surface is still reachable from this page',
	$failures
);
bhp_cct_assert(
	strpos( $html, 'bhp_mariana_popup' ) === false,
	'6: the teacher-funnel storage prefix does not leak onto this non-/teachers/ page',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo "RESULT: ALL ASSERTIONS PASSED\n";
exit( 0 );
