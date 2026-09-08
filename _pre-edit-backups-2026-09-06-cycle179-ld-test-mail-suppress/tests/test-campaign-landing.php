<?php
/**
 * Phase 1D — BHP_Campaign_Landing test suite.
 *
 * Run on staging (never production) via:
 *   wp eval-file tests/test-campaign-landing.php --user=1 --url=staging2.braveheartspublishing.com
 *
 * render() and validate() are pure functions over a config array plus
 * get_template_part() calls to already-tested, read-only template
 * parts -- no post/option is created or modified, so no cleanup state
 * is needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cl_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

if ( ! class_exists( 'BHP_Campaign_Landing' ) ) {
	bhp_cl_test_assert( false, 'BHP_Campaign_Landing class must be loadable', $failures );
	echo "1 TEST(S) FAILED\n";
	exit( 1 );
}

// ==================== validate() ====================
bhp_cl_test_assert( array() === BHP_Campaign_Landing::validate( array(
	'campaign_id' => 'x', 'audience' => 'parent', 'funnel_stage' => 'awareness', 'lead_offer' => 'x', 'cta_goal' => 'x', 'blocks' => array(),
) ), 'A config with all required keys present validates with zero errors', $failures );

bhp_cl_test_assert( array() !== BHP_Campaign_Landing::validate( array() ), 'An empty config fails validation', $failures );

$missing = BHP_Campaign_Landing::validate( array( 'audience' => 'parent' ) );
bhp_cl_test_assert( in_array( 'Missing required config key: campaign_id', $missing, true ), 'validate() names the specific missing key (campaign_id)', $failures );

$bad_block = BHP_Campaign_Landing::validate( array(
	'campaign_id' => 'x', 'audience' => 'parent', 'funnel_stage' => 'awareness', 'lead_offer' => 'x', 'cta_goal' => 'x',
	'blocks' => array( 'not_a_real_block_type' => array() ),
) );
bhp_cl_test_assert( in_array( 'Unrecognized block type: not_a_real_block_type', $bad_block, true ), 'validate() catches an unrecognized block type (e.g. a typo) rather than silently ignoring it', $failures );

// ==================== render() safety ====================
bhp_cl_test_assert( '' === BHP_Campaign_Landing::render( array() ), 'render() returns an empty string (never a fatal or partial page) for an invalid config', $failures );

// ==================== Example configs (Adventure Kit + Teacher) ====================
$kit_config = BHP_Campaign_Landing::example_adventure_kit_config();
bhp_cl_test_assert( array() === BHP_Campaign_Landing::validate( $kit_config ), 'The Adventure Kit example config is itself valid', $failures );

$kit_html = BHP_Campaign_Landing::render( $kit_config );
bhp_cl_test_assert( false !== strpos( $kit_html, 'data-bhp-campaign-id="adventure_kit_parent_organic"' ), 'Rendered Adventure Kit example carries its campaign_id as a data attribute', $failures );
bhp_cl_test_assert( false !== strpos( $kit_html, 'Get the Free Reluctant Reader Adventure Kit' ), 'Rendered Adventure Kit example includes the configured hero title', $failures );
bhp_cl_test_assert( false !== strpos( $kit_html, 'acquisition-form' ), 'Rendered Adventure Kit example includes the real signup-form component, not a placeholder', $failures );

$teacher_config = BHP_Campaign_Landing::example_teacher_guide_config();
bhp_cl_test_assert( array() === BHP_Campaign_Landing::validate( $teacher_config ), 'The teacher classroom guide example config is itself valid', $failures );

$teacher_html = BHP_Campaign_Landing::render( $teacher_config );
bhp_cl_test_assert( false !== strpos( $teacher_html, 'data-bhp-campaign-audience="teacher"' ), 'Rendered teacher example carries its audience as a data attribute', $failures );
bhp_cl_test_assert( false !== strpos( $teacher_html, 'Free Mariana Trench Classroom Guide' ), 'Rendered teacher example includes the configured hero title', $failures );

// Fabrication guard: neither example may contain invented claims.
foreach ( array( 'kit' => $kit_html, 'teacher' => $teacher_html ) as $label => $html ) {
	bhp_cl_test_assert( false === stripos( $html, 'award' ) && false === stripos( $html, 'guarantee' ) && false === stripos( $html, 'limited time' ), "The {$label} example contains no fabricated award/guarantee/urgency language", $failures );
}

// ==================== Phase 1D analytics event wiring (lead_form_view/start, landing_page_view/cta_click) ====================
bhp_cl_test_assert( false !== strpos( $kit_html, 'data-bhp-impression-event="landing_page_view"' ), 'The rendered landing page wrapper carries the landing_page_view impression-tracking attribute', $failures );
bhp_cl_test_assert( false !== strpos( $kit_html, 'data-bhp-impression-event="lead_form_view"' ), 'The embedded signup form carries the lead_form_view impression-tracking attribute', $failures );
bhp_cl_test_assert( false !== strpos( $kit_html, 'data-bhp-focus-event="lead_form_start"' ), 'The embedded signup form\'s email field carries the lead_form_start focus-tracking attribute', $failures );
bhp_cl_test_assert( false !== strpos( $kit_html, 'data-bhp-lead-offer="adventure_kit_parent"' ), 'The lead-offer context is attached to the tracked form so lead_form_view/start carry it', $failures );

$teacher_product_html = BHP_Campaign_Landing::render( $teacher_config );
bhp_cl_test_assert( false !== strpos( $teacher_product_html, 'data-bhp-event="landing_page_cta_click"' ), 'The product block\'s book link carries the landing_page_cta_click event attribute', $failures );

// PII-safety guard: no rendered block may leak an actual email/name value
// into a data-* attribute (only field values in normal form inputs,
// which are never read by dataLayer.push -- see nav.js bhpBuildEventPayload,
// which only reads data-bhp-* attributes, never .value).
foreach ( array( 'kit' => $kit_html, 'teacher' => $teacher_product_html ) as $label => $html ) {
	bhp_cl_test_assert( 0 === preg_match( '/data-bhp-[a-z-]+="[^"]*@[^"]*"/', $html ), "The {$label} rendered output never places an email-shaped value inside a data-bhp-* attribute", $failures );
}

// ==================== Block order is fixed regardless of config array order ====================
$reordered = $kit_config;
$reordered['blocks'] = array_reverse( $reordered['blocks'], true ); // shuffle key order, values unchanged
$reordered_html = BHP_Campaign_Landing::render( $reordered );
$hero_pos   = strpos( $reordered_html, 'Get the Free Reluctant Reader Adventure Kit' );
$signup_pos = strpos( $reordered_html, 'acquisition-form' );
bhp_cl_test_assert( false !== $hero_pos && false !== $signup_pos && $hero_pos < $signup_pos, 'Block render order (hero before signup_form) is fixed regardless of the order keys appear in the config array', $failures );

// ==================== Result ====================
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	exit( 1 );
}
echo "ALL CAMPAIGN LANDING FRAMEWORK TESTS PASSED\n";
