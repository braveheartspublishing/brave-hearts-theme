<?php
/**
 * Wave 1 capture / proof / price regression suite (theme 1.19.169, 2026-08-04).
 *
 * Covers the exit-intent capture modal, the sitewide footer capture block and
 * its segment selector, the Mailchimp tag mapping those two add, the three
 * owner-ruled toggles, and the funnel-isolation properties that must survive
 * all of it.
 *
 * ⭐ 1.19.169 (2026-08-04) — THE THREE GATES ARE NOW ON, BY OWNER RULING.
 *    Andrew Signore, in the main session, verbatim: "Turn it on. Turn it on.
 *    Accept." (exit-intent ON — a knowing reversal of his 2026-07-19
 *    one-popup ruling; homepage price cues ON — a knowing reversal of F2;
 *    Kirkus link accepted as built), plus his Message 38 attestation
 *    "Activity book is only at our store - true", which sources the
 *    exclusivity line. Sections 2, 4b and 7 were inverted for that reason and
 *    for no other. Section 2b is new and asserts the LIMIT of the reversal:
 *    BOTH 2026-07-19 lead-magnet popups are still retired.
 *
 * Run on staging (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-wave1-capture.php --user=1
 *
 * WHAT THIS SUITE PROVES:
 *   - The three owner-ruled features are ON in the deployed code, at the
 *     source default and at runtime, and nothing ELSE was loosened with them.
 *   - The two new capture surfaces write only their own namespaces, and
 *     nothing under the teacher funnel's prefix.
 *   - Every pre-existing Mailchimp tag set is byte-identical to 1.19.167.
 *   - No new tag, string or surface promises a resource that does not exist.
 *   - No number, rating, review count, urgency or scarcity string was added.
 *
 * WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS:
 *   It is a PHP + source-level suite, not a browser. It cannot observe the
 *   20-second dwell floor elapsing, a real mouse leaving the viewport, a real
 *   scroll, or a modal painting. Runtime timing evidence for the exit trigger
 *   comes from the Node behavioural harness recorded in the release handoff,
 *   and browser QA is a separate, later step.
 *
 * It touches no post, no option, no product and no WooCommerce record.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_w1_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_w1_read( $relative ) {
	$path = get_template_directory() . '/' . ltrim( $relative, '/' );
	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return null;
	}
	// The deploy artefact carries CRLF (git archive on Windows); the repo copy
	// carries LF. No assertion below may depend on a particular newline.
	return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
}

/**
 * Every CUSTOMER-FACING string in a template, and nothing else.
 *
 * ⭐ This distinction is load-bearing and was added after running the suite
 *    rather than reasoned about: the first version scanned whole files and
 *    failed on the word "rating" and on em dashes that appear only inside
 *    the explanatory PHP comments. A comment is not copy. The rails apply to
 *    what a visitor reads, so the checks are run against exactly that: the
 *    first argument of every translation call in the file.
 */
/**
 * The file with every PHP comment removed.
 *
 * ⭐ Also added after running the suite, not before: the footer block's own
 *    header EXPLAINS that it never touches `bhp_parent_popup_*` or
 *    `bhp_mariana_popup_*`, so a naive substring scan of the whole file
 *    found those very strings and failed. Documenting a constraint must not
 *    look like violating it. Code is checked; prose is not.
 */
function bhp_w1_strip_comments( $php ) {
	$out = '';
	foreach ( token_get_all( (string) $php ) as $token ) {
		if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$out .= is_array( $token ) ? $token[1] : $token;
	}
	return $out;
}

function bhp_w1_customer_strings( $php ) {
	$out = array();
	if ( preg_match_all(
		'/(?:esc_html_e|esc_attr_e|esc_html__|esc_attr__|__|_e)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/',
		(string) $php,
		$matches
	) ) {
		foreach ( $matches[1] as $string ) {
			$out[] = stripslashes( $string );
		}
	}
	return $out;
}

// ==================== 1. THE FILES EXIST ====================

$files = array(
	'template-parts/acquisition/exit-intent-popup.php',
	'template-parts/acquisition/footer-capture.php',
	'assets/js/mariana-popup.js',
	'assets/js/quiz-modal.js',
);
$src = array();
foreach ( $files as $rel ) {
	$src[ $rel ] = bhp_w1_read( $rel );
	bhp_w1_assert( null !== $src[ $rel ] && '' !== trim( (string) $src[ $rel ] ), "{$rel} exists and is non-empty", $failures );
}

// ==================== 2. THE THREE OWNER GATES ARE ON ====================
//
// ⚠️ THESE ASSERTIONS WERE INVERTED ON 2026-08-04 (theme 1.19.169), AND THE
//    REASON IS AN OWNER RULING, NOT A CONVENIENCE.
//
//    1.19.168 shipped all three gates OFF because each contradicted a
//    standing Andrew instruction and an agent does not reverse one. Andrew
//    was then shown all three conflicts and ruled, verbatim, in the main
//    session: "Turn it on. Turn it on. Accept." — exit-intent ON (knowing
//    reversal of his 2026-07-19 one-popup ruling), homepage price cues ON
//    (knowing reversal of F2), Kirkus link accepted as built. Separately, his
//    Message 38 attestation — "Activity book is only at our store - true" —
//    sourced the exclusivity claim, so that gate flips too.
//
//    So the check that protects the founder is no longer "is it off"; it is
//    "is it exactly what he ruled, and did nothing ELSE move with it". That
//    is what section 2 and 2b now assert together.

bhp_w1_assert(
	function_exists( 'bhp_should_show_exit_intent_popup' ),
	'bhp_should_show_exit_intent_popup() is defined',
	$failures
);
/*
 * Two halves, because neither alone is sufficient:
 *   (a) the SOURCE default literal is `true` — i.e. the gate itself was
 *       flipped, not merely masked by some other add_filter() somewhere; and
 *   (b) at RUNTIME nothing filters it back to false.
 * Checking only (b) would pass on a build where the literal is still false
 * and an unrelated filter happened to force it true.
 */
$w1_functions_src = (string) bhp_w1_read( 'functions.php' );
bhp_w1_assert(
	1 === preg_match( "/apply_filters\('bhp_show_exit_intent_popup', true\)/", $w1_functions_src )
		&& true === (bool) apply_filters( 'bhp_show_exit_intent_popup', true ),
	'EXIT-INTENT IS ON by default (Andrew 2026-08-04: "Turn it on" — knowing reversal of the 2026-07-19 one-popup ruling)',
	$failures
);
bhp_w1_assert(
	function_exists( 'bhp_home_price_cues_enabled' ) && true === bhp_home_price_cues_enabled(),
	'HOMEPAGE PRICE CUES ARE ON (Andrew 2026-08-04: "Turn it on" — knowing reversal of F2)',
	$failures
);
bhp_w1_assert(
	function_exists( 'bhp_activity_book_exclusive_enabled' ) && true === bhp_activity_book_exclusive_enabled(),
	'"Only at braveheartspublishing.com" IS ON, sourced to Andrew\'s Message 38 attestation "Activity book is only at our store - true" (closes CYCLE143-MKT-134)',
	$failures
);

// ============ 2b. WHAT THE REVERSAL DID *NOT* TOUCH ============
//
// ⛔ The 2026-07-19 retirement of the two lead-magnet popups is NOT reversed.
//    Andrew opened the door exactly as wide as the exit-intent surface. If
//    either assertion below starts failing, an owner ruling was widened by an
//    agent, which is the failure this section exists to catch.
bhp_w1_assert(
	false === (bool) apply_filters( 'bhp_show_parent_popup', true ),
	'STILL RETIRED: the timed parent lead-magnet popup (Andrew 2026-07-19) — NOT reversed by the exit-intent ruling',
	$failures
);
bhp_w1_assert(
	false === (bool) apply_filters( 'bhp_show_teacher_popup', true ),
	'STILL RETIRED: the teacher lead-magnet popup (Andrew 2026-07-19) — NOT reversed by the exit-intent ruling',
	$failures
);
bhp_w1_assert(
	function_exists( 'bhp_should_show_parent_popup' ) && false === bhp_should_show_parent_popup(),
	'STILL RETIRED, functionally: bhp_should_show_parent_popup() returns false on this request',
	$failures
);
// The anti-stacking guard is Andrew's stated condition on turning exit-intent
// on ("the anti-stacking guard stands"), so it is asserted here beside the
// gate rather than only in section 10.
bhp_w1_assert(
	1 === preg_match( "/SHARED_SESSION_SHOWN_KEY\s*=\s*'bhp_popup_shown_session'/", (string) bhp_w1_read( 'assets/js/mariana-popup.js' ) )
		&& false !== strpos( (string) bhp_w1_read( 'assets/js/quiz-modal.js' ), 'bhp_popup_shown_session' ),
	'THE ANTI-STACKING GUARD STANDS: both the popup engine and the quiz modal read the same session key',
	$failures
);

// ==================== 3. FUNNEL ISOLATION ====================

$exit_src  = (string) $src['template-parts/acquisition/exit-intent-popup.php'];
$exit_code = bhp_w1_strip_comments( $exit_src );

bhp_w1_assert(
	false !== strpos( $exit_src, "'storagePrefix' => 'bhp_parent_popup'" ),
	'exit-intent uses the PARENT funnel storage prefix, and mints no new one',
	$failures
);
bhp_w1_assert(
	false !== strpos( $exit_src, "'eventPrefix'   => 'parent_popup'" ),
	'exit-intent uses the parent funnel analytics event prefix',
	$failures
);
bhp_w1_assert(
	false === strpos( $exit_code, 'bhp_mariana_popup' ) && false === strpos( $exit_code, 'mariana-guide' ),
	'exit-intent CODE touches NOTHING in the teacher funnel namespace',
	$failures
);
bhp_w1_assert(
	false === strpos( $exit_code, 'teacher' ),
	'exit-intent CODE names no teacher funnel key of any kind',
	$failures
);

$footer_src  = (string) $src['template-parts/acquisition/footer-capture.php'];
$footer_code = bhp_w1_strip_comments( $footer_src );
foreach ( array( 'localStorage', 'sessionStorage', 'bhp_parent_popup', 'bhp_mariana_popup', 'data-bhp-popup' ) as $forbidden ) {
	bhp_w1_assert(
		false === strpos( $footer_code, $forbidden ),
		"footer capture CODE contains no '{$forbidden}' (it writes no popup storage state, and is not a popup)",
		$failures
	);
}
bhp_w1_assert(
	false !== strpos( $footer_src, "'context'              => 'footer_capture'" ),
	'footer capture block uses its OWN analytics/tag context, not a funnel prefix',
	$failures
);

// The functions.php gate must exclude /teachers/ for BOTH new surfaces.
$functions_src = bhp_w1_read( 'functions.php' );
bhp_w1_assert(
	null !== $functions_src
		&& 1 === preg_match( '/function bhp_should_show_footer_capture\(\).*?is_page\(\'teachers\'\)/s', $functions_src ),
	'bhp_should_show_footer_capture() excludes /teachers/ (parent magnet, teacher page)',
	$failures
);
bhp_w1_assert(
	null !== $functions_src
		&& 1 === preg_match( '/function bhp_should_show_exit_intent_popup\(\).*?is_page\(\'teachers\'\).*?bhp_exit_intent_preview_requested\(\)/s', $functions_src ),
	'the /teachers/ exclusion is checked BEFORE the staging preview parameter, so preview can never reach it',
	$failures
);

// ==================== 4. LIVE /teachers/ BEHAVIOUR ====================
//
// Functional, not source-level: put the main query on the real Teachers page
// and ask the two gates directly.
//
// ⚠️ ADDED 2026-08-04 (1.19.169): the suite runs under `--user=1`, and
//    `bhp_should_show_any_popup()` returns false for any logged-in admin. So
//    while the gates were OFF, the exit-intent assertion below passed for the
//    WRONG REASON — admin suppression, not the /teachers/ exclusion. Now that
//    the gate is ON that would hide a real funnel-isolation breach, so the
//    current user is dropped to 0 for this block and restored after. Nothing
//    is written; `wp_set_current_user()` is in-process state only.
$w1_saved_user = get_current_user_id();
wp_set_current_user( 0 );

$teachers_page = get_page_by_path( 'teachers' );
if ( $teachers_page && 'publish' === $teachers_page->post_status ) {
	$saved_query = $GLOBALS['wp_query'];
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $teachers_page->ID ) ); // phpcs:ignore
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore
	if ( $GLOBALS['wp_query']->have_posts() ) {
		$GLOBALS['wp_query']->the_post();
	}

	bhp_w1_assert(
		false === bhp_should_show_footer_capture(),
		'LIVE: the footer capture block does NOT render on /teachers/',
		$failures
	);
	bhp_w1_assert(
		false === bhp_should_show_exit_intent_popup(),
		'LIVE: the exit-intent modal does NOT render on /teachers/',
		$failures
	);

	wp_reset_postdata();
	$GLOBALS['wp_query'] = $saved_query; // phpcs:ignore
	$GLOBALS['wp_the_query'] = $saved_query; // phpcs:ignore
} else {
	bhp_w1_assert( false, 'the Teachers page must exist and be published for the live isolation checks', $failures );
}

// ---- 4b. THE POSITIVE CASE, added 1.19.169 ----
//
// A /teachers/-only test cannot tell "correctly excluded" apart from "renders
// nowhere at all". Andrew turned this surface ON, so the suite now has to
// prove it actually turns on somewhere. Homepage, logged out.
$w1_front_id = (int) get_option( 'page_on_front' );
if ( $w1_front_id > 0 ) {
	$saved_query = $GLOBALS['wp_query'];
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $w1_front_id ) ); // phpcs:ignore
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore
	if ( $GLOBALS['wp_query']->have_posts() ) {
		$GLOBALS['wp_query']->the_post();
	}

	$w1_kit_ready = (bool) bhp_get_reluctant_reader_download()['ready'];
	bhp_w1_assert(
		$w1_kit_ready,
		'the Reluctant Reader Adventure Kit PDF is configured on this environment (without it BOTH capture surfaces are silently inert)',
		$failures
	);
	bhp_w1_assert(
		true === bhp_should_show_exit_intent_popup(),
		'LIVE POSITIVE: the exit-intent modal DOES render on the homepage for a logged-out visitor (the ruling took effect, not just the literal)',
		$failures
	);
	bhp_w1_assert(
		true === bhp_should_show_footer_capture(),
		'LIVE POSITIVE: the footer capture block DOES render on the homepage',
		$failures
	);

	wp_reset_postdata();
	$GLOBALS['wp_query'] = $saved_query; // phpcs:ignore
	$GLOBALS['wp_the_query'] = $saved_query; // phpcs:ignore
} else {
	bhp_w1_assert( false, 'a static front page must be configured for the live positive checks', $failures );
}

wp_set_current_user( $w1_saved_user );

// ==================== 5. MAILCHIMP TAGS ====================

$source = home_url( '/' );

// -- 5a. Every pre-existing tag set is byte-identical to 1.19.167. --
$unchanged = array(
	'parent popup' => array(
		array( 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit' ),
		array( 'Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Parent Popup' ),
	),
	'parent landing page' => array(
		array( 'adventure_club', 'parents_families', 'reluctant_reader_adventure_kit' ),
		array( 'Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Parent Landing Page' ),
	),
	'teacher popup' => array(
		array( 'teacher_popup', 'teachers', 'mariana_trench_classroom_guide' ),
		array( 'Mariana Trench Classroom Guide', 'Audience: Teacher/Librarian', 'Source: Mariana Popup' ),
	),
	'educator landing page' => array(
		array( 'adventure_club', 'educators', 'teacher_adventure_toolkit' ),
		array( 'Adventure Learning Toolkit', 'Audience: Educator', 'Source: Educator Landing Page' ),
	),
	'gift landing page' => array(
		array( 'adventure_club', 'gift_buyers', 'meaningful_gift_guide' ),
		array( 'Meaningful Gift Guide', 'Audience: Gift Buyer', 'Source: Gift Buyer Landing Page' ),
	),
	'organization landing page' => array(
		array( 'adventure_club', 'organizations', 'community_reading_kit' ),
		array( 'Community Reading Kit', 'Audience: Organization', 'Source: Organization Landing Page' ),
	),
);
foreach ( $unchanged as $label => $case ) {
	list( $in, $expected ) = $case;
	$actual = bhp_get_mailchimp_signup_tags( $in[0], $in[1], $in[2], $source );
	bhp_w1_assert(
		$actual === $expected,
		"REGRESSION: {$label} tag set unchanged (" . implode( ' | ', $expected ) . ')',
		$failures
	);
}

// -- 5b. The exit-intent surface gets its own source tag. --
$exit_tags = bhp_get_mailchimp_signup_tags( 'parent_popup_exit', 'parents_families', 'reluctant_reader_adventure_kit', $source );
bhp_w1_assert(
	in_array( 'Source: Exit Intent', $exit_tags, true ) && in_array( 'Audience: Parent/Grandparent', $exit_tags, true ),
	'exit-intent signups are tagged Source: Exit Intent, Audience: Parent/Grandparent',
	$failures
);
bhp_w1_assert(
	! in_array( 'Source: Parent Popup', $exit_tags, true ),
	'exit-intent does NOT reuse the timed popup source tag (the two surfaces stay distinguishable)',
	$failures
);

// -- 5c. Footer capture, per segment. The browser sends a KEY; the tag is
//        resolved here. Every segment receives the SAME resource tag,
//        because every segment receives the same kit. --
$segment_expectations = array(
	''             => 'Audience: Parent/Grandparent',
	'parent'       => 'Audience: Parent/Grandparent',
	'educator'     => 'Audience: Teacher/Librarian',
	'gift'         => 'Audience: Gift Buyer',
	'organization' => 'Audience: Organization',
	'not_a_real_segment' => 'Audience: Parent/Grandparent',
);
$saved_post = $_POST;
foreach ( $segment_expectations as $segment => $expected_audience ) {
	$_POST['bhp_segment'] = $segment;
	$tags = bhp_get_mailchimp_signup_tags( 'footer_capture', 'parents_families', 'reluctant_reader_adventure_kit', $source );

	$seg_label = '' === $segment ? '(none)' : $segment;
	bhp_w1_assert(
		in_array( $expected_audience, $tags, true ),
		"footer segment '{$seg_label}' -> {$expected_audience}",
		$failures
	);
	bhp_w1_assert(
		in_array( 'Source: Footer Capture', $tags, true ),
		"footer segment '{$seg_label}' carries Source: Footer Capture",
		$failures
	);
	bhp_w1_assert(
		in_array( 'Reluctant Reader Adventure Kit', $tags, true ),
		"footer segment '{$seg_label}' promises the kit it actually delivers, and only that",
		$failures
	);
	// ⛔ THE ISOLATION ASSERTION. A footer segment must never route a
	//    signup into another funnel's lead magnet.
	foreach ( array( 'Mariana Trench Classroom Guide', 'Adventure Learning Toolkit', 'Meaningful Gift Guide', 'Community Reading Kit' ) as $other_magnet ) {
		bhp_w1_assert(
			! in_array( $other_magnet, $tags, true ),
			"footer segment '{$seg_label}' does NOT apply the '{$other_magnet}' resource tag",
			$failures
		);
	}
}
$_POST = $saved_post;

// -- 5d. The segment map itself. --
$routes = bhp_get_capture_segment_routes();
bhp_w1_assert(
	array_keys( $routes ) === array( 'parent', 'educator', 'gift', 'organization' ),
	'segment routes are exactly the four LIVE segments (Merry SET A), in order',
	$failures
);
$expected_labels = array(
	'parent'       => 'My own reader (ages 6 to 9)',
	'educator'     => 'My class, library or homeschool',
	'gift'         => 'A gift for a young reader',
	'organization' => 'Readers at our organization',
);
foreach ( $expected_labels as $key => $label ) {
	bhp_w1_assert(
		isset( $routes[ $key ]['label'] ) && $routes[ $key ]['label'] === $label,
		"segment label '{$key}' is the live string: \"{$label}\"",
		$failures
	);
}
foreach ( $routes as $key => $route ) {
	bhp_w1_assert(
		! isset( $route['lead_magnet'] ),
		"segment '{$key}' carries NO lead_magnet — the selector changes the audience tag and nothing else",
		$failures
	);
}
bhp_w1_assert(
	! in_array( 'Other', array_values( wp_list_pluck( $routes, 'label' ) ), true ),
	'no unrouted "Other" bucket exists (CYCLE143-MKT-135)',
	$failures
);

// ============ 5e. TRAFFIC-SOURCE MERGE FIELD (CYCLE148-FIN-002) ============
/*
 * ⭐ WHY THIS SECTION EXISTS. Before 1.19.211 a signup driven by a UTM-tagged
 *    probe and a signup from the organic sitewide popup reached Mailchimp
 *    looking identical — every merge field described the FORM or the PAGE, none
 *    described where the visitor came from. Monday's measurement depends on
 *    telling those two apart, so the mapping is asserted here rather than
 *    eyeballed in the Mailchimp UI after the fact.
 *
 * ⛔ WHAT THIS CANNOT PROVE, stated so a PASS is not over-read: it never calls
 *    Mailchimp. It proves the map, the merge-tag shape and the cookie->value
 *    derivation. Whether the `TRAFFIC` field EXISTS in the audience is a
 *    console fact, not a code fact, and it is verified by Andrew/Gimli in
 *    Mailchimp — see the deploy packet.
 */
$w1_map = bhp_get_mailchimp_merge_field_map();

// The three pre-existing rows are byte-identical. This is an ADDITIVE change,
// and a renamed merge tag would orphan every existing contact's data.
foreach ( array(
	'audience_type' => 'AUDIENCE',
	'lead_magnet'   => 'LEADMAG',
	'source_page'   => 'SOURCE',
) as $w1_field => $w1_tag ) {
	bhp_w1_assert(
		isset( $w1_map[ $w1_field ] ) && $w1_map[ $w1_field ] === $w1_tag,
		"REGRESSION: merge field '{$w1_field}' still maps to {$w1_tag} (additive change, nothing renamed)",
		$failures
	);
}
bhp_w1_assert(
	isset( $w1_map['traffic_source'] ) && $w1_map['traffic_source'] === 'TRAFFIC',
	'traffic_source maps to the TRAFFIC merge tag',
	$failures
);
// Mailchimp caps a merge tag at 10 characters and bhp_process_signup()
// truncates to 10 as well. A longer name would be silently cut and would then
// never match the field in the audience — which fails SILENTLY, the worst kind.
foreach ( $w1_map as $w1_field => $w1_tag ) {
	bhp_w1_assert(
		strlen( $w1_tag ) <= 10 && preg_match( '/\A[A-Z0-9_]+\z/', $w1_tag ) === 1,
		"merge tag '{$w1_tag}' survives the 10-char uppercase sanitiser intact",
		$failures
	);
}

$w1_saved_cookie = $_COOKIE;

// -- No attribution cookie at all: UNKNOWN, and unknown must not be reported
//    as "direct". No cookie is written until analytics consent is granted, so
//    calling a consent-decliner "direct" would invent a fact about them. An
//    empty value is dropped by the merge-field loop, so nothing is sent.
unset( $_COOKIE['bhp_attr_last'], $_COOKIE['bhp_attr_first'] );
bhp_w1_assert(
	'' === bhp_get_signup_traffic_source(),
	'no attribution cookie -> empty value (unknown is NOT reported as "direct")',
	$failures
);

// -- A cookie with no campaign signal. This one genuinely IS direct/organic:
//    the visitor consented, the first-touch cookie was written, and no UTM or
//    click ID ever arrived. This is the ORGANIC POPUP SIGNUP case.
$_COOKIE['bhp_attr_first'] = wp_json_encode( array( 'landing_page' => '/', 'timestamp' => '2026-08-09' ) );
bhp_w1_assert(
	'direct' === bhp_get_signup_traffic_source(),
	'cookie present, no campaign signal -> "direct" (the organic popup signup)',
	$failures
);

// -- The probe case. A UTM-tagged last touch must be distinguishable from the
//    line above at a glance in Mailchimp — that is the entire point.
$_COOKIE['bhp_attr_last'] = wp_json_encode( array(
	'utm_source'   => 'reddit',
	'utm_medium'   => 'paid_social',
	'utm_campaign' => 'probe-monday',
	'landing_page' => '/complete-collection/',
) );
$w1_probe = bhp_get_signup_traffic_source();
bhp_w1_assert(
	'reddit / paid_social / probe-monday' === $w1_probe,
	'UTM-tagged last touch -> "reddit / paid_social / probe-monday"',
	$failures
);
bhp_w1_assert(
	'direct' !== $w1_probe,
	'THE POINT: a probe-driven signup is distinguishable from an organic one',
	$failures
);
// ⛔ No PII and no URL reaches the third-party platform. The cookie carries
//    landing_page; the merge value deliberately does not.
bhp_w1_assert(
	false === strpos( $w1_probe, '/complete-collection/' ),
	'the landing page URL is NOT sent to Mailchimp (campaign identifiers only)',
	$failures
);

// -- Last touch outranks first touch: it is the campaign that produced THIS
//    visit, and therefore this signup.
bhp_w1_assert(
	'reddit / paid_social / probe-monday' === bhp_get_signup_traffic_source(),
	'last touch outranks first touch when both are present',
	$failures
);

// -- Auto-tagged paid click with no utm_source. Naming the platform keeps the
//    value groupable; printing the per-click ID would make every contact unique.
unset( $_COOKIE['bhp_attr_last'] );
$_COOKIE['bhp_attr_first'] = wp_json_encode( array( 'gclid' => 'EAIaIQobCh_TEST', 'landing_page' => '/' ) );
$w1_click = bhp_get_signup_traffic_source();
bhp_w1_assert(
	'google / cpc' === $w1_click,
	'a bare gclid -> "google / cpc" (the platform, never the opaque click ID)',
	$failures
);
bhp_w1_assert(
	false === strpos( $w1_click, 'EAIaIQobCh_TEST' ),
	'the raw click ID is never written into the merge field',
	$failures
);

// -- The value is capped. A merge field is not a place for unbounded input.
$_COOKIE['bhp_attr_first'] = wp_json_encode( array( 'utm_source' => str_repeat( 'x', 400 ), 'utm_medium' => 'cpc' ) );
bhp_w1_assert(
	strlen( bhp_get_signup_traffic_source() ) <= 100,
	'the traffic-source value is capped at 100 characters',
	$failures
);

$_COOKIE = $w1_saved_cookie;

// -- The graceful no-op. The TRAFFIC field must be created by hand in the
//    Mailchimp console; until it is, a signup must never fail BECAUSE of it.
//    That safety is structural (drop the optional tag, retry once), so it is
//    asserted against the source rather than against a belief about the API.
$w1_mc_src = (string) bhp_w1_read( 'inc/mailchimp.php' );
bhp_w1_assert(
	'' !== $w1_mc_src,
	'inc/mailchimp.php is readable in the deployed artefact (guards the three source assertions below)',
	$failures
);
bhp_w1_assert(
	false !== strpos( $w1_mc_src, '$optional_merge_tags' ) && false !== strpos( $w1_mc_src, "'TRAFFIC'" ),
	'TRAFFIC is registered as an OPTIONAL merge tag in the signup path',
	$failures
);
bhp_w1_assert(
	false !== strpos( $w1_mc_src, 'bhp_mailchimp_optional_merge_field_dropped' ),
	'dropping the optional field fires an observable action rather than failing silently',
	$failures
);
bhp_w1_assert(
	false !== strpos( $w1_mc_src, 'throw $merge_exception' ),
	'a genuine outage still fails (the retry does not swallow real errors)',
	$failures
);

// ==================== 6. THE GIFT LANE PROMISES NOTHING ====================

$footer_copy = implode( ' | ', bhp_w1_customer_strings( $footer_src ) );
bhp_w1_assert(
	'' !== $footer_copy,
	'the footer block yields extractable customer-facing copy (guards against a silent regex miss below)',
	$failures
);
bhp_w1_assert(
	false === stripos( $footer_copy, 'gift guide' ) && false === stripos( $footer_copy, 'gift kit' ),
	'the footer block PROMISES no gift guide and no gift kit (capture + tag only, Gandalf ruling; CYCLE143-MKT-131)',
	$failures
);
bhp_w1_assert(
	false !== stripos( $footer_copy, 'Reluctant Reader Adventure Kit' ),
	'the footer block names the ONE resource it actually delivers',
	$failures
);

// ==================== 7. ACTIVITY-BOOK FRAMING ====================

$framing = bhp_get_activity_book_framing();

/*
 * ⚠️ INVERTED 2026-08-04 (1.19.169). This previously asserted the eyebrow was
 *    EMPTY, because the exclusivity claim had no source. It now has exactly
 *    one, and it is the strongest available for a distribution fact: Andrew
 *    Signore's own attestation, 2026-08-04, verbatim — "Activity book is only
 *    at our store - true" (Message 38, overnight execution register), accepted
 *    in the same session. Closes CYCLE143-MKT-134 / gate G-W1-5.
 *
 * ⛔ The claim is FOUNDER-ATTESTED, not retailer-swept. If distribution ever
 *    changes, the line becomes false and the filter comes back off.
 */
bhp_w1_assert(
	'Only at braveheartspublishing.com' === $framing['eyebrow'],
	'activity-book eyebrow carries the exclusivity line, sourced to Andrew\'s 2026-08-04 attestation ("Activity book is only at our store - true")',
	$failures
);
bhp_w1_assert( 'The Adventure Activity Book' === $framing['title'], 'the title is "The Adventure Activity Book", never "Ocean Activity Book"', $failures );
bhp_w1_assert( false !== strpos( $framing['note'], 'book order' ), 'the framing states it goes with a book order (the checkout guard refuses an add-on-only cart)', $failures );

/*
 * The rails that did NOT move. "Only at" leaves this list because it is now
 * sourced; every other forbidden string stays, and the exclusivity line is
 * the ONLY claim the reversal admits.
 */
$framing_blob = implode( ' ', $framing );
foreach ( array( 'crossword', 'puzzle', '$', 'Ocean Activity', 'rating', 'review', 'award', 'best-selling', 'Limited time', 'Hurry' ) as $forbidden ) {
	bhp_w1_assert(
		false === stripos( $framing_blob, $forbidden ),
		"activity-book framing contains no '{$forbidden}'",
		$failures
	);
}
// And the exclusivity string appears ONCE, in the eyebrow, nowhere else.
bhp_w1_assert(
	1 === substr_count( strtolower( $framing_blob ), 'only at' ),
	'the exclusivity line appears exactly once in the framing, in the eyebrow only',
	$failures
);

// ==================== 8. NOTHING FABRICATED WAS ADDED ====================

$new_copy_files = array(
	'template-parts/acquisition/exit-intent-popup.php',
	'template-parts/acquisition/footer-capture.php',
);
foreach ( $new_copy_files as $rel ) {
	$copy = bhp_w1_customer_strings( (string) $src[ $rel ] );
	bhp_w1_assert( count( $copy ) > 0, "{$rel} yields extractable customer-facing copy", $failures );
	$blob = implode( ' | ', $copy );

	/*
	 * ⚠️ WORD BOUNDARIES, and this was a real false positive rather than a
	 *    precaution: a plain substring scan for "star" failed on the
	 *    approved footer heading "Start with one free chapter." The rail
	 *    forbids a fabricated STAR RATING, not the letters s-t-a-r.
	 */
	foreach ( array( 'aggregateRating', 'star', 'stars', 'rating', 'ratings', 'reviewer', 'reviewers', 'review', 'reviews', 'Limited time', 'Hurry', 'Only at', 'left in stock', 'parents bought', 'best-selling', 'bestselling', 'award', 'awards' ) as $forbidden ) {
		bhp_w1_assert(
			0 === preg_match( '/\b' . preg_quote( $forbidden, '/' ) . '\b/i', $blob ),
			"{$rel} copy contains no '{$forbidden}' (no fabricated proof, no urgency, no scarcity)",
			$failures
		);
	}
	// No digit-bearing claim other than the approved age range.
	$without_age = str_replace( array( '6 to 9', '6–9', '6-9' ), '', $blob );
	bhp_w1_assert(
		0 === preg_match( '/\d/', $without_age ),
		"{$rel} copy carries NO number except the approved 6 to 9 age range",
		$failures
	);
	bhp_w1_assert(
		false === strpos( $blob, '5-9' ) && false === strpos( $blob, '5 to 9' ),
		"{$rel} states the reading age as 6 to 9, never 5 to 9",
		$failures
	);
	bhp_w1_assert(
		false === strpos( $blob, '—' ) && false === strpos( $blob, '--' ),
		"{$rel} copy carries no em dash",
		$failures
	);
}

// ==================== 9. F2 IS INTACT ON THE HOMEPAGE ====================

$front_src = bhp_w1_read( 'front-page.php' );
bhp_w1_assert(
	3 === substr_count( (string) $front_src, "'formats_info' => []" ),
	'REGRESSION: all three homepage cards still pass an EMPTY format price list (F2 not reinstated)',
	$failures
);
/*
 * ⚠️ The card comments are counted by their FULL wording, not by the quoted
 *    phrase alone. This release's own explanatory comment quotes Andrew's
 *    "remove the cost numbers" too, so a bare count of that phrase is 4 and
 *    would stay >= 3 even if one card's comment were deleted. The longer
 *    string below occurs only at the three cards.
 */
bhp_w1_assert(
	3 === substr_count( (string) $front_src, '"remove the cost numbers" -- the price' ),
	'REGRESSION: all three F2 owner-instruction comments survive verbatim at the three cards',
	$failures
);

/*
 * ⚠️ CHECKED AGAINST CODE, NOT PROSE, and this one found something real:
 *    front-page.php's farmers-market comment legitimately contains "$11.99",
 *    "$8.99", "$16" and "$1.98" while EXPLAINING why a price sign was cropped
 *    out of a photograph. Scanning the raw file therefore fails on a comment
 *    that is doing exactly the right thing. Pre-existing, untouched by this
 *    release, and correct where it is.
 */
$front_code = bhp_w1_strip_comments( (string) $front_src );
foreach ( array( '$11.99', '$17.99', '$48.99', '$31.99' ) as $price ) {
	bhp_w1_assert(
		false === strpos( $front_code, $price ),
		"no book or collection price {$price} is hardcoded in front-page.php code",
		$failures
	);
}
/*
 * ⭐ RETARGETED IN 1.19.171 (2026-08-05), AND THE OLD ASSERTIONS ARE QUOTED
 *    BELOW RATHER THAN DELETED, so a future reader can see that the guard
 *    was moved and not weakened.
 *
 *    Until 1.19.170 the Complete Collection band was written inline in
 *    front-page.php, so these three assertions read front-page.php. On
 *    Andrew's 2026-08-05 instruction ("use the same homepage one") that band
 *    became `template-parts/components/complete-collection-feature.php`,
 *    which `/books/` renders too. The band's source file changed; not one
 *    character of the band's copy, its owner gate or its two approved
 *    literals did.
 *
 *    The three checks are therefore retargeted at the partial, plus a FOURTH
 *    that did not exist before and is the stronger one: front-page.php must
 *    now contain NO currency figure at all in code.
 *
 *    Superseded assertions, verbatim:
 *      array_values( array_unique( $currency[0] ) ) === array( '$4.98', '$3.98' )
 *        'the ONLY currency figures in front-page.php code are the two
 *         approved bundle-savings literals'
 *      1 === preg_match( '/if \(\$home_price_cues_on\):.{0,400}Save \$4\.98 in hardcover, \$3\.98 in paperback\./s', $front_code )
 *      false !== strpos( $front_src, 'Best value: all three adventures in one Complete Collection.' )
 */
$cc_src  = bhp_w1_read( 'template-parts/components/complete-collection-feature.php' );
$cc_code = bhp_w1_strip_comments( (string) $cc_src );

preg_match_all( '/\$\d+\.\d\d/', $front_code, $currency );
bhp_w1_assert(
	array() === array_values( array_unique( $currency[0] ) ),
	'front-page.php code now carries NO currency figure at all — the two approved literals moved with the band into the shared partial',
	$failures
);

preg_match_all( '/\$\d+\.\d\d/', $cc_code, $cc_currency );
bhp_w1_assert(
	array_values( array_unique( $cc_currency[0] ) ) === array( '$4.98', '$3.98' ),
	'the ONLY currency figures in the shared Complete Collection partial are the two approved bundle-savings literals',
	$failures
);
bhp_w1_assert(
	1 === preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,400}Save \$4\.98 in hardcover, \$3\.98 in paperback\./s', $cc_code ),
	'the savings line is still INSIDE the same owner gate as the card cues (one switch, one owner question, now on two pages)',
	$failures
);
bhp_w1_assert(
	1 === preg_match( '/bhp_cc_price_cues_on\s*=\s*function_exists\(\s*\x27bhp_home_price_cues_enabled\x27\s*\).{0,80}bhp_home_price_cues_enabled\(\)/s', $cc_code ),
	'the partial reads the owner gate ITSELF rather than accepting it as an argument (a gate passed in is a gate that can be passed wrong)',
	$failures
);
/*
 * ⭐ INVERTED 2026-08-05, theme 1.19.182, CYCLE144-LD-110 — AND THE OLD
 *    ASSERTION IS QUOTED RATHER THAN DELETED.
 *
 *    Superseded assertion, verbatim:
 *      false !== strpos( (string) $cc_src, 'Best value: all three adventures in one Complete Collection.' )
 *      'the claim-free collection primacy line ships (Merry C3)'
 *
 * ⛔ THE LINE NO LONGER SHIPS, BY OWNER ORDER. Andrew Signore, 2026-08-05
 *    (⛔ RELAYED through the Chief of Staff — not witnessed by this suite's
 *    author), adopting the reviewed recommendation to cut it as a
 *    redundancy: every one of its three claims is already on screen inside
 *    the same box, above it — "Best value" is the badge, "one Complete
 *    Collection" is the <h2>, and "all three adventures" is the description
 *    directly above, which also names the three titles. Merry's C3 was a
 *    correct call in 1.19.169 and is superseded by a later owner call, not
 *    by this suite's opinion.
 *
 * ⭐ THE CHECK IS AGAINST `$cc_code`, NOT `$cc_src`, AND THAT IS THE WHOLE
 *    POINT OF THE RETARGET. The partial's WAVE 1 header comment still quotes
 *    the sentence verbatim — deliberately, so the history stays legible — so
 *    a raw-source scan for its ABSENCE would fail on a correct comment, and
 *    a raw-source scan for its PRESENCE would have PASSED on that comment
 *    with the rendered line already gone. That second failure mode is the
 *    dangerous one: a green suite over a missing line. Sections 9 and 9a of
 *    this file already strip comments for exactly this reason.
 */
bhp_w1_assert(
	false === strpos( $cc_code, 'Best value: all three adventures in one Complete Collection.' )
	&& false === strpos( $cc_code, 'home-collection-feature__primacy' ),
	'the redundant collection primacy line is GONE from the partial CODE (Andrew 2026-08-05); its removal is not hidden by the comment that still quotes it',
	$failures
);
/*
 * ⛔ THE COUNTERWEIGHT. Cutting one line must not have taken the line that
 *    carries the actual numbers with it — that is the whole risk of a
 *    redundancy pass, and it is asserted rather than trusted.
 */
/*
 * ⭐ RETARGETED 1.19.218 (2026-08-11, CYCLE154-LD-01), NOT WEAKENED.
 *
 * Superseded needle, verbatim, so the guard is seen to move rather than lapse:
 *
 *     && false !== strpos( $cc_code, 'The complete collection ships %s.' )
 *
 * Andrew Signore, 2026-08-11 (⛔ RELAYED through the Chief of Staff): the FREE
 * items in the Best Value box become bullet points. The combined sentence was
 * replaced by the shared `bhp_book_free_bullets_markup()` list, so the needle
 * is now the helper call. ⭐ THE PROPERTY THIS ASSERTION EXISTS FOR IS
 * UNCHANGED and is the counterweight to a redundancy pass: cutting copy must
 * never take the line that carries the actual NUMBERS with it, and the
 * free-item claim must still be present and still gated. Both are checked.
 */
bhp_w1_assert(
	false !== strpos( $cc_code, 'Save $4.98 in hardcover, $3.98 in paperback.' )
	&& false !== strpos( $cc_code, 'bhp_book_free_bullets_markup(' )
	&& false !== strpos( $cc_code, 'home-collection-feature__savings' ),
	'the savings line SURVIVES the cut, with both approved literals intact and the gated FREE-items list still rendered (1.19.218: bullets, not a sentence)',
	$failures
);

/*
 * ---- 9c. CYCLE144-LD-111 — THE HOMEPAGE GALLERY LABEL IS HIDDEN, NOT
 *      DELETED, AND ONLY ON THE HOMEPAGE ----
 *
 * Andrew Signore, 2026-08-05 (⛔ RELAYED): remove the small "All three books"
 * label above the homepage gallery — it repeats the band description four
 * lines above it, inside the same box, in the same eyeful.
 *
 * ⛔ THE RISK THIS SECTION EXISTS TO CATCH is that "remove the label" gets
 *    implemented as "delete the element". The gallery <section> is
 *    `aria-labelledby` that heading's id, so deleting it would leave a
 *    dangling reference and strip the region's accessible name — trading an
 *    accessibility regression for ~32px. The heading must still be in the
 *    DOM, still the aria target, and merely visually hidden.
 */
$w1_li_src   = bhp_w1_read( 'template-parts/commerce/look-inside.php' );
$w1_gal_src  = bhp_w1_read( 'inc/collection-gallery.php' );
$w1_li_code  = bhp_w1_strip_comments( (string) $w1_li_src );
$w1_gal_code = bhp_w1_strip_comments( (string) $w1_gal_src );

bhp_w1_assert(
	1 === preg_match( '/\$heading_hidden\s*=\s*!empty\(\s*\$heading_hidden\s*\)/', $w1_li_code ),
	'look-inside.php defaults heading_hidden to FALSE, so every other caller (3 product pages, /complete-collection/, /books/, 4 funnel pages) is unchanged',
	$failures
);
bhp_w1_assert(
	1 === preg_match( '/bhp-look-inside__title.{0,120}\$heading_hidden\s*\?\s*\x27 screen-reader-text\x27/s', $w1_li_code ),
	'the hidden heading is hidden with the theme\'s own .screen-reader-text utility — it stays in the DOM and in the a11y tree',
	$failures
);
bhp_w1_assert(
	1 === preg_match( '/aria-labelledby="[^"]*\$uid[^"]*-title/', $w1_li_code )
	&& 1 === preg_match( '/id="[^"]*\$uid[^"]*-title"/', $w1_li_code ),
	'the aria-labelledby reference and its target id both still exist — the accessible name was not traded away',
	$failures
);
/*
 * ⚠️ Checked against CODE, not raw source: the map entry's own explanatory
 *    comment names `page-books.php` while explaining why /books/ is left
 *    alone, which would make a raw scan read as if /books/ carried the flag.
 */
bhp_w1_assert(
	1 === preg_match( '/\x27front-page\.php\x27\s*=>\s*\[.*?\x27heading_hidden\x27\s*=>\s*true.*?\],/s', $w1_gal_code ),
	'heading_hidden is set on the front-page.php placement entry',
	$failures
);
bhp_w1_assert(
	1 === substr_count( $w1_gal_code, "'heading_hidden' => true" ),
	'EXACTLY ONE placement hides its heading — /books/ and the four funnel pages keep their visible headings (flagged divergence, Andrew\'s call)',
	$failures
);
bhp_w1_assert(
	1 === preg_match( '/\$heading_hidden\s*=\s*!empty\(\s*\$config\[\x27heading_hidden\x27\]\s*\)/', $w1_gal_code ),
	'the renderer passes heading_hidden through as placement data, exactly like heading and collection',
	$failures
);

/*
 * ---- 9a. CYCLE144-LD-12 — ONE BAND, TWO PAGES, NO FORK ----
 *
 * The whole point of the 2026-08-05 change. If either page ever stops
 * calling the shared partial, or if a second copy of the band's copy
 * reappears in either template, these fail.
 */
$books_src  = bhp_w1_read( 'page-books.php' );
/*
 * ⚠️ Checked against CODE, not prose, for the same reason section 9 above
 *    does it: page-books.php's own explanatory comment QUOTES the heading it
 *    removed ("Get All Three Adventures"), which is exactly right where it
 *    is and would make a raw-file scan fail on a correct comment.
 */
$books_code = bhp_w1_strip_comments( (string) $books_src );
foreach ( array(
	'front-page.php' => $front_src,
	'page-books.php' => $books_src,
) as $w1_rel => $w1_src ) {
	bhp_w1_assert(
		false !== strpos( (string) $w1_src, "get_template_part('template-parts/components/complete-collection-feature'" ),
		"{$w1_rel} renders the SHARED Complete Collection partial",
		$failures
	);
	bhp_w1_assert(
		false === strpos( (string) $w1_src, 'home-collection-feature__primacy' )
		&& false === strpos( (string) $w1_src, 'Save $4.98 in hardcover' ),
		"{$w1_rel} holds NO forked copy of the band's markup or savings line",
		$failures
	);
}
bhp_w1_assert(
	false === strpos( $books_code, 'Get All Three Adventures' ),
	'the old /books/ "Get All Three Adventures" heading is gone from CODE, replaced by the homepage band (Andrew 2026-08-05)',
	$failures
);
/*
 * ⛔ B7 IS NOT REPEALED BY THE CONSOLIDATION. Andrew, walk-3 2026-08-03: "I
 *    want less steps to purchase." /books/ must still POST the smart-add and
 *    land on /checkout/, and the partial must still read the format from
 *    bhp_bundle_default_format() rather than hardcoding one.
 */
bhp_w1_assert(
	false !== strpos( (string) $books_src, "'cta'           => 'checkout'" ),
	'B7 survives: /books/ still asks for the add-to-checkout CTA',
	$failures
);
/*
 * ⭐ REPOINTED 2026-08-05, theme 1.19.177, CYCLE144-LD-52 — SOURCE → RENDERED.
 *
 * ⛔ THE ORIGINAL LOOP IS PRESERVED VERBATIM IMMEDIATELY BELOW, COMMENTED OUT
 *    RATHER THAN DELETED, so a reader can see what changed and why, and can
 *    restore it in one hunk if the Chief of Staff prefers:
 *
 *      foreach ( array( 'bhp_bundle_default_format()', 'name="bhp_bundle_action"',
 *                       'value="checkout"', 'bhp_bundle_add' ) as $w1_needle ) {
 *          bhp_w1_assert(
 *              false !== strpos( (string) $cc_src, $w1_needle ),
 *              "the shared partial's checkout CTA keeps B7's `{$w1_needle}`",
 *              $failures
 *          );
 *      }
 *
 * WHAT BROKE IT, AND WHY THIS IS NOT A TEST BENT TO FIT A BUILD. 1.19.177
 * moved the band's CTA onto `bhp_collection_add_to_cart_cta()`
 * (inc/collection-cta.php) — the SAME shared renderer the four funnel pages
 * and the product-page upsell already post through — so the nonce, the action
 * field and the checkout-redirect field are now emitted in ONE place instead
 * of being retyped into the partial. The literals therefore left
 * `complete-collection-feature.php`, and this loop reported B7 broken on a
 * build where B7 demonstrably works.
 *
 * ⚠ AND THE OLD LOOP'S OWN PASS WAS UNRELIABLE, WHICH IS DISCLOSED RATHER
 *   THAN QUIETLY FIXED: after 1.19.177 its first needle,
 *   `bhp_bundle_default_format()`, still PASSED — but only because the
 *   partial's explanatory COMMENT quotes it. A comment cannot buy anything.
 *   That is the exact comment-versus-markup trap this file's own section 9
 *   documents, and `test-collection-purchase-path.php` §1 was rewritten once
 *   already for the same reason.
 *
 * SO B7 IS NOW ASSERTED WHERE IT ACTUALLY LIVES: the rendered /books/
 * document. A comment cannot survive PHP, and a renderer that emits nothing
 * cannot pass by containing the right words. If the shared renderer is ever
 * swapped for another mechanism, this still passes — as it should, because
 * Andrew's instruction was "less steps to purchase", not "these four strings
 * must appear in this file".
 */
$w1_books_page = get_page_by_path( 'books' );
$w1_books_html = '';
if ( $w1_books_page ) {
	$w1_books_res = wp_remote_get( get_permalink( $w1_books_page->ID ), array( 'timeout' => 30, 'sslverify' => false ) );
	if ( ! is_wp_error( $w1_books_res ) && 200 === (int) wp_remote_retrieve_response_code( $w1_books_res ) ) {
		$w1_books_html = (string) wp_remote_retrieve_body( $w1_books_res );
	}
}
bhp_w1_assert( '' !== $w1_books_html, 'B7: /books/ renders (HTTP 200) so its CTA can be asserted', $failures );
if ( '' !== $w1_books_html ) {
	foreach ( array(
		'name="bhp_bundle_nonce"'                                   => 'the plugin nonce',
		'name="bhp_bundle_action" value="complete_'                 => 'a complete_<format>_smart add action',
		'name="bhp_bundle_redirect" value="checkout"'               => 'the land-on-/checkout/ flag',
	) as $w1_needle => $w1_label ) {
		bhp_w1_assert(
			false !== strpos( $w1_books_html, $w1_needle ),
			"B7 survives in RENDERED /books/: the CTA emits {$w1_label}",
			$failures
		);
	}
	/*
	 * The format must still come from the site's single source of truth
	 * rather than a literal typed into a template. Asserted by comparing the
	 * rendered action against `bhp_book_default_format()` at run time, which
	 * is stronger than grepping for the function's NAME: a template could
	 * name the function and still print the wrong format.
	 */
	if ( function_exists( 'bhp_book_default_format' ) ) {
		$w1_default_fmt = bhp_book_default_format();
		bhp_w1_assert(
			false !== strpos( $w1_books_html, 'value="complete_' . $w1_default_fmt . '_smart"' ),
			"B7: the /books/ CTA pre-selects the SITE default format ({$w1_default_fmt}), not a hardcoded one",
			$failures
		);
	}
}
/*
 * ⛔ FREE-SHIPPING SAFETY (plugin 1.8.23 took the collection tier to $0.00).
 *    The band is safe because it quotes NO shipping figure at all — assert
 *    that, so nobody adds one without meeting this test.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ AMENDED 2026-08-05, theme 1.19.174, CYCLE144-LD-42. THE PARAGRAPH
 *    ABOVE IS PRESERVED VERBATIM AND IS NO LONGER THE WHOLE RULE.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The original assertion was `false === stripos( $cc_code, 'shipping' )` —
 * the band must not contain the word at all. Andrew Signore's current-turn
 * order of 2026-08-05 (relayed through the Chief of Staff; ⚠ RELAYED, not
 * witnessed by this agent) adds a free-shipping sentence to this band, so
 * that assertion is now false BY DESIGN.
 *
 * ⭐ THE GATE WAS MET, NOT REMOVED. Its own comment says it exists "so
 *    nobody adds one without meeting this test". The condition it was
 *    really protecting is not the absence of a word — it is the absence of
 *    a STALE PROMISE. That condition is asserted below, and it is stricter
 *    than a word ban because a word ban would also have permitted a
 *    hardcoded "ships free" written as "no delivery charge":
 *
 *      1. still NO shipping FIGURE — the dollar-literal assertion at the
 *         top of this section already pins the file to exactly $4.98 and
 *         $3.98 and nothing else, so a shipping amount cannot get in;
 *      2. the claim is CONDITIONAL on bhp_book_collection_ships_free(),
 *         which reads the plugin's own rules rather than the theme's
 *         opinion, so it stops rendering the moment the rule stops being
 *         free.
 *
 * The dedicated suite for the sentence itself is
 * tests/test-collection-band-freeship.php.
 */
bhp_w1_assert(
	0 === preg_match( '/shipping[^<]{0,40}\$\d/i', $cc_code ),
	'the shared Complete Collection band still quotes NO shipping figure (the 1.8.23 hazard: a dollar amount that goes stale)',
	$failures
);
/*
 * ⭐ RETARGETED 1.19.179 (2026-08-05, CYCLE144-LD-70). The needle changed
 *    because the COPY changed on Andrew's current-turn order — "ships free."
 *    became "ships FREE." with FREE in a real <strong>, printed through
 *    printf() — so the lowercase needle no longer matches the source. The
 *    GATE it protects is unchanged and is still what is asserted. Superseded
 *    assertion, verbatim:
 *        preg_match( '/bhp_book_collection_ships_free\(\).{0,200}ships free/s', $cc_code )
 */
/*
 * ⭐ RETARGETED AGAIN 1.19.194 (2026-08-05, CYCLE144-LD-223). The band now
 *    prints a SECOND sentence from inside the same gate — the one that also
 *    names the free activity book — so the 400-character window no longer
 *    reaches the original format string. Window widened to 1200 and a second
 *    assertion added for the new sentence, rather than the first being
 *    loosened on its own. Superseded assertion, verbatim:
 *        preg_match( '/bhp_book_collection_ships_free\(\).{0,400}The complete collection ships %s\./s', $cc_code )
 *    The dedicated suite is tests/test-collection-band-freeship.php §3–3g.
 */
/*
 * ⭐ RETARGETED AGAIN 1.19.218 (2026-08-11, CYCLE154-LD-01). Andrew Signore,
 *    2026-08-11 (⛔ RELAYED through the Chief of Staff): the FREE items in the
 *    Best Value box become bullet points. The two sentences these assertions
 *    were written around are gone, replaced by the shared
 *    `bhp_book_free_bullets_markup()` list. Superseded assertions, verbatim:
 *
 *      preg_match( '/bhp_book_collection_ships_free\(\).{0,1200}The complete collection ships %s\./s', $cc_code )
 *      preg_match( '/bhp_book_collection_includes_free_addon\(\).{0,300}The complete collection ships %1\$s and includes the Activity Book %2\$s\./s', $cc_code )
 *
 * ⛔ THE GATES DID NOT GO AWAY — THEY MOVED INTO THE HELPER, one per line, and
 *    are asserted there. The property this file needs is that the band gets
 *    its free-item copy from the shared helper rather than printing it
 *    unconditionally, which is what is asserted now. The dedicated suite is
 *    tests/test-collection-band-freeship.php §3–§3j and §4.
 */
bhp_w1_assert(
	false !== strpos( $cc_code, 'bhp_book_free_bullets_markup(' )
	&& 1 === preg_match( '/function_exists\(\x27bhp_book_free_bullets_markup\x27\)/', $cc_code ),
	'the free items are rendered through the SHARED gated helper, never printed unconditionally by the band',
	$failures
);
bhp_w1_assert(
	function_exists( 'bhp_book_free_bullet_lines' )
	&& 1 === preg_match(
		'/function bhp_book_free_bullet_lines.{0,900}bhp_book_collection_ships_free\(\).{0,900}bhp_book_collection_includes_free_addon\(\).{0,900}bhp_bundle_vocab_cards_live\(\)/s',
		bhp_w1_strip_comments( (string) bhp_w1_read( 'inc/book-formats.php' ) )
	),
	'and the helper gates each free item on its own live predicate (shipping, activity book, vocabulary cards)',
	$failures
);

// ---- 9b. THE PRICE CUE PRODUCES A REAL PRICE, added 1.19.169 ----
//
// ⚠️ THIS SECTION EXISTS BECAUSE OF A LIVE DEFECT, not as a precaution.
//    Turning the gate on rendered "From $3,611.99" on all three homepage
//    cards on staging. `get_price_html()` emits the USD symbol as the entity
//    `&#036;`; `wp_strip_all_tags()` does not decode entities; the old
//    "strip everything but [0-9.]" left the entity's OWN digits behind as
//    "03611.99". The whole suite passed while that was true, because nothing
//    asserted the cue's VALUE. Now something does.
$w1_cue_cases = array(
	// input price html                          => expected cue
	'&#036;11.99'                                 => 'From $11.99',
	'<span class="amount">&#036;11.99</span>'     => 'From $11.99',
	'&#036;11.99 &ndash; &#036;16.99'             => 'From $11.99',
	'<del>&#036;16.99</del> <ins>&#036;11.99</ins>' => 'From $11.99',
	'&#036;1,234.56'                              => 'From $1,234.56',
);
foreach ( $w1_cue_cases as $input => $expected ) {
	$actual = bhp_get_home_price_cue( array( 'Paperback' => $input ) );
	bhp_w1_assert(
		$expected === $actual,
		"price cue for '{$input}' is '{$expected}' (got '{$actual}')",
		$failures
	);
}
bhp_w1_assert(
	'' === bhp_get_home_price_cue( array() ) && '' === bhp_get_home_price_cue( array( 'Paperback' => '' ) ),
	'price cue is EMPTY when no price resolves, so a card renders with no cue rather than a wrong one',
	$failures
);
bhp_w1_assert(
	'From $11.99' === bhp_get_home_price_cue( array( 'Hardcover' => '&#036;16.99', 'Paperback' => '&#036;11.99' ) ),
	'"From" means the LOWEST live format price across both formats',
	$failures
);
// And the LIVE homepage cards, not a fixture: every rendered cue must be a
// plausible book price. The defect produced 3611.99, which this catches.
$w1_home_id = (int) get_option( 'page_on_front' );
if ( $w1_home_id > 0 && function_exists( 'bhp_get_homepage_books' ) ) {
	$w1_live_books = bhp_get_homepage_books( -1 );
	$w1_live_prices = array();
	foreach ( (array) $w1_live_books as $w1_book ) {
		if ( ! empty( $w1_book['price'] ) ) {
			$w1_live_prices = array_merge( $w1_live_prices, bhp_extract_price_amounts( $w1_book['price'] ) );
		}
	}
	bhp_w1_assert(
		count( $w1_live_prices ) > 0,
		'LIVE: at least one homepage book price resolves to a number',
		$failures
	);
	$w1_bad = array_filter(
		$w1_live_prices,
		static function ( $p ) {
			return $p < 1 || $p > 200;
		}
	);
	bhp_w1_assert(
		array() === $w1_bad,
		'LIVE: every homepage book price parses into a plausible range ($1-$200) — catches entity-digit contamination (' . implode( ', ', $w1_live_prices ) . ')',
		$failures
	);
}

// ==================== 10. THE SHARED SESSION GUARD ====================

$engine = (string) $src['assets/js/mariana-popup.js'];
$quiz   = (string) $src['assets/js/quiz-modal.js'];

bhp_w1_assert(
	1 === preg_match( "/var\s+SHARED_SESSION_SHOWN_KEY\s*=\s*'bhp_popup_shown_session'/", $engine ),
	'the popup engine declares the shared session-frequency key',
	$failures
);
bhp_w1_assert(
	1 === preg_match( "/var\s+SHARED_POPUP_SHOWN_KEY\s*=\s*'bhp_popup_shown_session'/", $quiz ),
	'the quiz modal reads the SAME shared session-frequency key',
	$failures
);
bhp_w1_assert(
	false !== strpos( $engine, "document.querySelectorAll('[data-bhp-popup]')" ),
	'the engine initialises EVERY popup element, not just the first',
	$failures
);
bhp_w1_assert(
	1 === preg_match( "/var\s+AUTO_OPEN_DELAY_MS\s*=\s*20000\s*;/", $quiz ),
	'REGRESSION: the quiz dwell floor is still exactly 20000ms (Andrew, 2026-08-04)',
	$failures
);
bhp_w1_assert(
	false !== strpos( $exit_src, "'minDelay' => 20000" ) && 2 === substr_count( $exit_src, "'minDelay' => 20000" ),
	'the exit-intent modal carries the same 20000ms floor on BOTH devices',
	$failures
);
bhp_w1_assert(
	false === strpos( $exit_src, 'fallbackDelay' ),
	'the exit-intent modal has NO fallback timer (an exit popup that opens on a timer is just a timed popup)',
	$failures
);

/*
 * ==================== 11. THE HOMEPAGE ORDER — ADDED 1.19.179 ============
 *
 * CYCLE144-LD-70. §1–§10 above are UNTOUCHED except for the one retargeted
 * needle in §9, which is annotated where it sits.
 *
 * Andrew Signore, 2026-08-05, current-turn order (⛔ RELAYED through the Chief
 * of Staff and witnessed by the main session — NOT witnessed first-hand by the
 * agent that wrote this), verbatim:
 *
 *   "Remove the hero section CTA 'Get the complete collection' and 'Find their
 *    first adventure' - Because right below is the best value box with the
 *    collection.- Right under 'Its an invitation to look up. Put the Best Value
 *    box. Too much redundancy. Put the big places. brave hearts under that box
 *    along with the Ages 6-9.... Featuring a kirkus reviewed title"
 *
 * ⭐ ASSERTED AGAINST THE RENDERED DOCUMENT, not the template. A source scan
 *    would pass on a template that renders the right markup in the wrong
 *    order, and ORDER is the entire instruction.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN GLOSSED. This is DOM order in the
 *   server-rendered HTML. It does NOT prove what a browser paints: CSS could
 *   still reorder or hide any of it, and "FREE renders bold at 390px" is a
 *   browser observation this file cannot make. A green run here is not visual
 *   QA and must not be reported as one.
 */
echo "\n=== 11 — THE HOMEPAGE ORDER (rendered) ===\n";

$w1_home_res  = wp_remote_get( home_url( '/' ), array( 'timeout' => 30, 'sslverify' => false ) );
$w1_home_html = ( is_wp_error( $w1_home_res ) || 200 !== (int) wp_remote_retrieve_response_code( $w1_home_res ) )
	? ''
	: (string) wp_remote_retrieve_body( $w1_home_res );

bhp_w1_assert( '' !== $w1_home_html, '11. the homepage renders (HTTP 200, non-empty body)', $failures );

if ( '' !== $w1_home_html ) {

	/*
	 * --- The removal. Both hero buttons are gone, and so is the wrapper.
	 *
	 * ⛔ THE REGION IS SLICED FROM `id="home-hero"`, NOT FROM BYTE 0. Slicing
	 *    from the top of the document would put <head> inside the window, and
	 *    any inline CSS, og:title or JSON-LD string mentioning a class name or
	 *    a CTA label would fail an assertion about the hero's BODY markup.
	 */
	$w1_hero_start = strpos( $w1_home_html, 'id="home-hero"' );
	$w1_hero_end   = strpos( $w1_home_html, 'id="home-sales-paths"' );
	$w1_hero_doc   = ( false !== $w1_hero_start && false !== $w1_hero_end && $w1_hero_end > $w1_hero_start )
		? substr( $w1_home_html, $w1_hero_start, $w1_hero_end - $w1_hero_start )
		: '';
	bhp_w1_assert( '' !== $w1_hero_doc, '11. the hero region is sliceable (#home-hero before #home-sales-paths)', $failures );

	bhp_w1_assert(
		false === strpos( $w1_hero_doc, 'home-hero__actions' ),
		'11. ⭐ the hero renders NO actions wrapper at all — both CTAs removed, not merely relabelled',
		$failures
	);
	bhp_w1_assert(
		false === stripos( $w1_hero_doc, 'Find Their First Adventure' ),
		'11. the "Find Their First Adventure" CTA is gone from the hero',
		$failures
	);
	/*
	 * ⚠ SCOPED TO THE HERO, DELIBERATELY. "Get the Complete Collection" is
	 *   still the BAND's CTA label lower down the page, and must be — Andrew
	 *   removed the hero duplicate, not the offer. A document-wide ban here
	 *   would fail on the button he asked to keep.
	 */
	bhp_w1_assert(
		false === stripos( $w1_hero_doc, 'Get the Complete Collection' ),
		'11. the "Get the Complete Collection" CTA is gone from the HERO (the band below still carries it — that one stays)',
		$failures
	);

	// --- The order. hero -> band -> brand+proof -> Kirkus.
	$w1_at = array(
		'hero'     => strpos( $w1_home_html, 'id="home-hero"' ),
		'band'     => strpos( $w1_home_html, 'id="home-sales-paths"' ),
		'proof'    => strpos( $w1_home_html, 'id="home-trust-proof"' ),
		'kirkus'   => strpos( $w1_home_html, 'id="kirkus-credibility-home"' ),
	);
	foreach ( $w1_at as $w1_key => $w1_pos ) {
		bhp_w1_assert( false !== $w1_pos, "11. the homepage still renders #{$w1_key}", $failures );
	}
	if ( ! in_array( false, $w1_at, true ) ) {
		bhp_w1_assert(
			$w1_at['hero'] < $w1_at['band'],
			'11. ⭐ the Best Value box now comes DIRECTLY after the hero ("Right under \'Its an invitation to look up\'")',
			$failures
		);
		bhp_w1_assert(
			$w1_at['band'] < $w1_at['proof'],
			'11. ⭐ the Ages 6-9 / Kirkus proof row now sits UNDER the box, not above it',
			$failures
		);
		bhp_w1_assert(
			$w1_at['proof'] < $w1_at['kirkus'],
			'11. REGRESSION (F19): the Kirkus section still follows immediately, so the pill and the quote stay together',
			$failures
		);
		/*
		 * The assertion that actually encodes "immediately followed by". If a
		 * future edit slips a section between the hero and the box, this
		 * fails — which is the whole point of writing it down.
		 */
		/*
		 * ⚠ THE WINDOW RUNS FROM `id="home-hero"` TO `id="home-sales-paths"`,
		 *   and both ids are the FIRST attribute of their own <section> tag —
		 *   so the window necessarily contains the band's own opening tag and
		 *   nothing else that opens a section. EXACTLY ONE is the pass
		 *   condition; two would mean a third section slipped in between.
		 *   (`</section>` does not match `<section\b`.)
		 */
		$w1_between = substr( $w1_home_html, $w1_at['hero'], $w1_at['band'] - $w1_at['hero'] );
		bhp_w1_assert(
			1 === substr_count( $w1_between, '<section' ),
			'11. ⭐ NO OTHER SECTION renders between the hero and the Best Value box (got '
				. substr_count( $w1_between, '<section' ) . ' opening tags where 1 is the band\'s own)',
			$failures
		);
	}

	// --- The move. The brand line is below the box, exactly once.
	bhp_w1_assert(
		1 === substr_count( $w1_home_html, 'home-hero__signature' ),
		'11. ⭐ "Big Places. Brave Hearts." renders EXACTLY ONCE — moved, not duplicated',
		$failures
	);
	$w1_sig_at = strpos( $w1_home_html, 'home-hero__signature' );
	if ( false !== $w1_sig_at && false !== $w1_at['band'] ) {
		bhp_w1_assert(
			$w1_sig_at > $w1_at['band'],
			'11. ⭐ and it renders BELOW the Best Value box, as instructed',
			$failures
		);
	}
	bhp_w1_assert(
		false !== strpos( $w1_home_html, 'Big Places. Brave Hearts.' ),
		'11. the brand line copy itself is unchanged',
		$failures
	);
	bhp_w1_assert(
		1 === substr_count( $w1_home_html, 'id="home-trust-proof"' )
		&& 1 === substr_count( $w1_home_html, 'Featuring a Kirkus-reviewed title' ),
		'11. the proof row and its Kirkus pill render exactly once each',
		$failures
	);
	bhp_w1_assert(
		false !== strpos( $w1_home_html, 'href="#kirkus-credibility-home"' ),
		'11. REGRESSION: the Kirkus pill is still an anchor link to the quote section',
		$failures
	);

	// --- The copy change, on the real page.
	/*
	 * ⭐ RETARGETED 1.19.198 (2026-08-05, CYCLE144-LD-223), after it failed on
	 *    a correct build on staging. Superseded needle, verbatim:
	 *        preg_match( '#The complete collection ships <strong[^>]*>FREE</strong>\.#', $w1_home_html )
	 *
	 *    With the activity book free (Andrew, 2026-08-05), the rendered
	 *    sentence continues past the first </strong>: "... ships
	 *    <strong>FREE</strong> and includes the Activity Book
	 *    <strong>FREE</strong>." The old needle demanded a full stop
	 *    immediately after the first bold word.
	 *
	 * ⛔ NOT A LOOSENING: both branches still require the bold, the typed
	 *    capitals and the terminal full stop, and the assertion added
	 *    immediately below pins WHICH branch must render against the live
	 *    predicate. The dedicated suite is
	 *    tests/test-collection-band-freeship.php §4.
	 */
	/*
	 * ═════════════════════════════════════════════════════════════════
	 * ⭐⭐ RETARGETED 1.19.218 (2026-08-11, CYCLE154-LD-01) — THE HOMEPAGE
	 *     NOW RENDERS BULLETS, on Andrew Signore's 2026-08-11 instruction
	 *     (⛔ RELAYED through the Chief of Staff).
	 * ═════════════════════════════════════════════════════════════════
	 *
	 * Superseded needles, verbatim:
	 *
	 *   preg_match( '#The complete collection ships <strong[^>]*>FREE</strong>(\.| and includes the Activity Book <strong[^>]*>FREE</strong>\.)#', $w1_home_html )
	 *   false === strpos( $w1_home_html, 'The complete collection ships free.' )
	 *   $w1_addon_live === ( false !== strpos( $w1_home_html, 'includes the Activity Book <strong' ) )
	 *
	 * ⛔ THIS IS A REAL PAGE FETCH, which is what makes it worth keeping: the
	 *    dedicated suite renders the PARTIAL, this renders the HOMEPAGE. The
	 *    three properties are carried over one-for-one and a fourth is added
	 *    for the vocabulary line the sentence could never carry.
	 */
	preg_match_all( '#<li class="bhp-free-bullets__item"><strong>(.*?)</strong></li>#s', $w1_home_html, $w1_free_items );
	$w1_free_joined = implode( ' | ', $w1_free_items[1] );
	bhp_w1_assert(
		false !== strpos( $w1_home_html, '<ul class="bhp-free-bullets' ) && count( $w1_free_items[1] ) >= 1,
		sprintf( '11. ⭐ RENDERED ON THE HOMEPAGE: the FREE items are BULLET LINES, one <li><strong> each (found %d)', count( $w1_free_items[1] ) ),
		$failures
	);
	bhp_w1_assert(
		false === strpos( $w1_home_html, 'The complete collection ships' ),
		'11. and the superseded COMBINED SENTENCE is gone from the page (no stale duplicate alongside the bullets)',
		$failures
	);
	$w1_ship_live = function_exists( 'bhp_book_collection_ships_free' ) && bhp_book_collection_ships_free();
	bhp_w1_assert(
		$w1_ship_live === ( false !== stripos( $w1_free_joined, 'shipping' ) ),
		'11. ⭐ RENDERED ON THE HOMEPAGE: the FREE-shipping bullet is present EXACTLY when the plugin says the collection ships free (live=' . var_export( $w1_ship_live, true ) . ')',
		$failures
	);
	$w1_addon_live = function_exists( 'bhp_book_collection_includes_free_addon' )
		&& bhp_book_collection_includes_free_addon();
	bhp_w1_assert(
		$w1_addon_live === ( false !== stripos( $w1_free_joined, 'Activity Book' ) ),
		'11. ⭐ RENDERED ON THE HOMEPAGE: the free-activity-book bullet is present EXACTLY when the plugin says the offer is live (live=' . var_export( $w1_addon_live, true ) . ')',
		$failures
	);
	$w1_vocab_live = function_exists( 'bhp_bundle_vocab_cards_live' ) && bhp_bundle_vocab_cards_live();
	bhp_w1_assert(
		$w1_vocab_live === ( false !== stripos( $w1_free_joined, 'Vocabulary Card' ) ),
		'11. ⭐ RENDERED ON THE HOMEPAGE: the free-vocabulary-cards bullet — the third item Andrew named — is present EXACTLY when the plugin says so (live=' . var_export( $w1_vocab_live, true ) . ')',
		$failures
	);

	// --- The surfaces that must have survived the reorder untouched.
	bhp_w1_assert(
		false !== strpos( $w1_home_html, 'data-bhp-popup' ),
		'11. REGRESSION: the capture popup still renders on the homepage',
		$failures
	);
	bhp_w1_assert(
		false !== strpos( $w1_home_html, 'data-bhp-collection-band' )
		&& false !== strpos( $w1_home_html, 'name="bhp_bundle_redirect" value="checkout"' ),
		'11. REGRESSION: the band still carries its format toggle and its add-and-checkout form',
		$failures
	);
	/*
	 * --- RE-SCOPED 1.19.183 (2026-08-05). READ THIS BEFORE CHANGING THE NUMBER.
	 *
	 * This assertion exists to stop a SECOND collection purchase form appearing
	 * in the homepage's own content — the "one band, one CTA" guarantee. It
	 * counted document-wide, which was correct until Andrew's ruling of
	 * 2026-08-05 ("Convert to hardcover purchase") made the SITEWIDE HEADER a
	 * real add-and-checkout form. The header now contributes two markers to
	 * every page on the site, so the count broke on a homepage that had not
	 * changed at all.
	 *
	 * ⛔ THE NUMBER IS STILL ONE. It was not raised to three, and the check was
	 *    not deleted. The header region is excluded and the same "exactly one"
	 *    constraint is applied to the homepage's own body, which is what this
	 *    assertion was always about. Raising the threshold would have made a
	 *    genuine second band-CTA regression invisible.
	 *
	 * The header's two forms are asserted positively, and asserted to be
	 * exactly two, in tests/test-header-collection-cta.php §3.
	 */
	$w1_header_at  = strpos( $w1_home_html, '<header class="site-header"' );
	$w1_header_end = false !== $w1_header_at ? strpos( $w1_home_html, '</header>', $w1_header_at ) : false;
	$w1_home_body  = ( false !== $w1_header_at && false !== $w1_header_end )
		? substr( $w1_home_html, 0, $w1_header_at ) . substr( $w1_home_html, $w1_header_end + 9 )
		: $w1_home_html;

	bhp_w1_assert(
		1 === substr_count( $w1_home_body, 'name="bhp_bundle_redirect" value="checkout"' ),
		'11. REGRESSION: still exactly ONE add-and-checkout form in the homepage BODY (header excluded)',
		$failures
	);
	bhp_w1_assert(
		2 === substr_count( $w1_home_html, 'name="bhp_bundle_redirect" value="checkout"' ) - substr_count( $w1_home_body, 'name="bhp_bundle_redirect" value="checkout"' ),
		'11. REGRESSION: the sitewide header contributes exactly TWO add-and-checkout forms (bar + mobile nav, 1.19.183)',
		$failures
	);

	/*
	 * --- 11b. THE .home-brand-proof BLOCK READS AS ONE CENTRED UNIT.
	 *          ADDED 1.19.181 (2026-08-05, CYCLE144-LD-90).
	 *
	 * Andrew Signore, 2026-08-05 (RELAYED through the Chief of Staff, NOT
	 * witnessed first-hand here), verbatim: "The row that starts with 'Nearly
	 * 7 miles deep..' needs to be centered."
	 *
	 * ⛔ WHY A STYLESHEET ASSERTION AND NOT A DOM ONE. Centring is not
	 *    expressible in server-rendered markup — the three destination <li>s
	 *    are in the same order either way. The regression this guards against
	 *    is somebody re-introducing `margin: 0` on a `width: max-content` grid
	 *    and assuming `justify-content: center` centres it. It does not, and
	 *    that is exactly the 1.19.179 defect this release fixes.
	 *
	 * ⚠ HONEST LIMIT: a PASS here proves the rule SHIPPED, not that the row
	 *   PAINTS centred. Geometry evidence is the headless-Chrome measurement
	 *   recorded in the release handoff, not this assertion.
	 */
	$w1_css_path = get_template_directory() . '/style.css';
	$w1_css      = is_readable( $w1_css_path ) ? (string) file_get_contents( $w1_css_path ) : '';
	bhp_w1_assert( '' !== $w1_css, '11b. the shipped style.css is readable', $failures );

	if ( '' !== $w1_css ) {
		$w1_bp_at = strpos( $w1_css, '.home .home-brand-proof .home-hero__destinations {' );
		bhp_w1_assert(
			false !== $w1_bp_at,
			'11b. the .home-brand-proof destinations rule is present',
			$failures
		);

		if ( false !== $w1_bp_at ) {
			$w1_bp_close = strpos( $w1_css, '}', $w1_bp_at );
			$w1_bp_body  = substr( $w1_css, $w1_bp_at, ( false === $w1_bp_close ? 400 : ( $w1_bp_close - $w1_bp_at ) ) );

			bhp_w1_assert(
				(bool) preg_match( '/margin(-inline)?\s*:\s*(0\s+auto|auto)\s*;/', $w1_bp_body ),
				'11b. ⭐ the stats row is centred by an AUTO inline margin — the only thing that '
					. 'centres a block box of intrinsic width (found: ' . trim( preg_replace( '/\s+/', ' ', $w1_bp_body ) ) . ')',
				$failures
			);
			bhp_w1_assert(
				! preg_match( '/margin\s*:\s*0\s*;/', $w1_bp_body ),
				'11b. the defective `margin: 0` has NOT been reinstated (it is what left the row 375px off centre in 1.19.180)',
				$failures
			);
		}

		// The wrapper still centres its inline content (the brand signature).
		bhp_w1_assert(
			(bool) preg_match( '/\.home\s+\.home-brand-proof\s*\{[^}]*text-align\s*:\s*center/s', $w1_css ),
			'11b. the .home-brand-proof wrapper still sets text-align: center for the brand signature',
			$failures
		);

		// The proof row itself is a centred flex row, and stays one.
		bhp_w1_assert(
			(bool) preg_match( '/\.home-trust-proof__inner\s*\{[^}]*justify-content\s*:\s*center/s', $w1_css ),
			'11b. the Ages 6-9 / Kirkus proof row is still a centred flex row',
			$failures
		);

		// The phone behaviour is unchanged: stats hidden, signature never hidden.
		bhp_w1_assert(
			false !== strpos( $w1_css, '.home .home-brand-proof .home-hero__destinations { display: none; }' ),
			'11b. REGRESSION: the <=768px stats hide is intact (pre-1.19.179 phone behaviour)',
			$failures
		);
		bhp_w1_assert(
			! preg_match( '/\.home\s+\.home-brand-proof\s+\.home-hero__signature\s*\{[^}]*display\s*:\s*none/s', $w1_css ),
			'11b. REGRESSION: the brand signature is NEVER hidden — it must survive on every phone',
			$failures
		);
	}
}

// ==================== RESULT ====================

echo "\n";
if ( $failures ) {
	echo count( $failures ) . " TEST(S) FAILED\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
	exit( 1 );
}
echo "ALL TESTS PASSED\n";
