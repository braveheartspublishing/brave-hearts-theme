<?php
/**
 * Phase 1D — BHP_Conversion_Scoring test suite.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-conversion-scoring.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * score_page() is a pure function over a caller-supplied HTML string and
 * classification array -- no post/option is created or modified, so no
 * cleanup state is needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cs_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_Conversion_Scoring' ) ) {
	bhp_cs_test_assert( false, 'BHP_Conversion_Scoring class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== Criteria definition shape ====================
$criteria = BHP_Conversion_Scoring::criteria();
bhp_cs_test_assert( count( $criteria ) === 14, 'Exactly 14 criteria are defined, matching the Phase 1D workstream list', $failures );
foreach ( $criteria as $name => $def ) {
	bhp_cs_test_assert( in_array( $def['check_type'], array( 'automated', 'inferred', 'manual' ), true ), "Criterion '{$name}' has a valid check_type (automated/inferred/manual)", $failures );
	bhp_cs_test_assert( ! empty( $def['description'] ), "Criterion '{$name}' has a non-empty description", $failures );
}
$manual_count = count( array_filter( $criteria, function ( $d ) { return 'manual' === $d['check_type']; } ) );
bhp_cs_test_assert( $manual_count >= 3, 'At least mobile_usability, visual_readiness, and search_intent_match are honestly marked manual (never fake-automated)', $failures );

// ==================== Classified vs unclassified content ====================
$classified = array( 'source' => 'explicit', 'audience' => 'parent', 'funnel_stage' => 'consideration', 'featured_book' => 'mount_everest', 'primary_goal' => 'visit_book_page' );
$unclassified = array( 'source' => 'flat_default', 'audience' => 'mixed', 'funnel_stage' => 'awareness', 'featured_book' => '', 'primary_goal' => 'related_content_engagement' );

$html_good = '<html><body><img src="x.jpg" alt="A real description" loading="lazy"><a href="/blog/other-post/">Read more</a><a data-bhp-event="contextual_cta_click" href="/reluctant-reader-adventure-kit/">Get the Kit</a></body></html>';

$scored_classified = BHP_Conversion_Scoring::score_page( 'blog', $html_good, $classified );
bhp_cs_test_assert( 'pass' === $scored_classified['criteria']['audience_clarity']['state'], 'Explicitly classified content passes audience_clarity', $failures );
bhp_cs_test_assert( 'pass' === $scored_classified['criteria']['funnel_stage_clarity']['state'], 'Explicitly classified content passes funnel_stage_clarity', $failures );

$scored_unclassified = BHP_Conversion_Scoring::score_page( 'blog', $html_good, $unclassified );
bhp_cs_test_assert( 'fail' === $scored_unclassified['criteria']['audience_clarity']['state'], 'flat_default classification fails audience_clarity (never silently passes)', $failures );

// ==================== Automated checks: internal links, a11y proxy, analytics coverage ====================
bhp_cs_test_assert( 'pass' === $scored_classified['criteria']['internal_link_quality']['state'], 'A page with a real internal link passes internal_link_quality', $failures );
bhp_cs_test_assert( 'pass' === $scored_classified['criteria']['analytics_coverage']['state'], 'A page with a data-bhp-event attribute passes analytics_coverage', $failures );
bhp_cs_test_assert( 'pass' === $scored_classified['criteria']['accessibility']['state'], 'An image with real alt text passes the accessibility proxy check', $failures );

$html_bad = '<html><body><img src="x.jpg" alt=""><p>No links here at all.</p></body></html>';
$scored_bad = BHP_Conversion_Scoring::score_page( 'blog', $html_bad, $unclassified );
bhp_cs_test_assert( 'fail' === $scored_bad['criteria']['internal_link_quality']['state'], 'A page with zero internal links fails internal_link_quality', $failures );
bhp_cs_test_assert( 'fail' === $scored_bad['criteria']['analytics_coverage']['state'], 'A page with no data-bhp-* attributes fails analytics_coverage', $failures );
bhp_cs_test_assert( 'fail' === $scored_bad['criteria']['accessibility']['state'], 'An image with an empty alt attribute fails the accessibility proxy check', $failures );

// ==================== Claim-accuracy proxy ====================
$html_with_claim = '<html><body>This award-winning series is guaranteed to work!</body></html>';
$scored_claim = BHP_Conversion_Scoring::score_page( 'blog', $html_with_claim, $unclassified );
bhp_cs_test_assert( 'fail' === $scored_claim['criteria']['claim_accuracy']['state'], 'A forbidden claim word (award) fails the claim_accuracy proxy', $failures );
bhp_cs_test_assert( false !== strpos( $scored_claim['criteria']['claim_accuracy']['note'], 'proxy only' ), 'The claim_accuracy note is honest that this is a proxy, not a full accuracy review', $failures );

// ==================== Manual criteria never fake-pass without an override ====================
bhp_cs_test_assert( null === $scored_classified['criteria']['mobile_usability']['state'], 'mobile_usability state is null (not silently passing) when no manual override is supplied', $failures );
bhp_cs_test_assert( null === $scored_classified['criteria']['visual_readiness']['state'], 'visual_readiness state is null when no manual override is supplied', $failures );

$scored_with_manual = BHP_Conversion_Scoring::score_page( 'blog', $html_good, $classified, array( 'mobile_usability' => 8, 'visual_readiness' => 9, 'search_intent_match' => 8 ) );
bhp_cs_test_assert( 'pass' === $scored_with_manual['criteria']['mobile_usability']['state'], 'A supplied manual override for mobile_usability is honored', $failures );
bhp_cs_test_assert( null !== $scored_with_manual['overall_score'], 'overall_score is computable once manual criteria are supplied', $failures );

// ==================== product_relevance is page-type-scoped ====================
$scored_blog_not_applicable = BHP_Conversion_Scoring::score_page( 'blog', $html_good, $classified );
bhp_cs_test_assert( null === $scored_blog_not_applicable['criteria']['product_relevance']['state'], 'product_relevance is marked not-applicable (null state) for a blog page_type', $failures );

$html_with_price = '<html><body><p>$11.99</p></body></html>';
$scored_product = BHP_Conversion_Scoring::score_page( 'product', $html_with_price, null );
bhp_cs_test_assert( 'pass' === $scored_product['criteria']['product_relevance']['state'], 'A product page with a real price passes product_relevance', $failures );

// ==================== Overall score is never fabricated with false precision on an all-manual page ====================
$all_manual_missing = BHP_Conversion_Scoring::score_page( 'landing', '<html><body></body></html>', null );
bhp_cs_test_assert( $all_manual_missing['scored_weight_coverage'] < 1.0, 'scored_weight_coverage reflects that manual criteria remain unresolved, rather than silently treating them as passed', $failures );

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL CONVERSION SCORING TESTS PASSED\n";
