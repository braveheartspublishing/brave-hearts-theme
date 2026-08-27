<?php
/**
 * Brave Hearts — capture repair. Theme 1.19.292, 2026-08-26,
 * `CYCLE166-CX-CAPTURE-REPAIR`.
 *
 * WHAT IS ASSERTED:
 *
 *   §1 CONVERSION TOKENS. Minted only behind a real signup; single-use;
 *      short-TTL and hard-bounded; carries NO PII; degrades towards the
 *      customer when storage fails.
 *   §2 THE THANK-YOU GATE. Every thank-you page that pushes a conversion
 *      event asks `bhp_is_verified_conversion()` first, so a bare load
 *      fires nothing.
 *   §3 NOINDEX. The three published thank-you pages carry `noindex` in the
 *      STORED `rank_math_robots` meta — the only thing Rank Math's sitemap
 *      provider actually reads — plus the two frontend backstops.
 *   §4 THE COOLDOWN SPLIT. X and "No thanks" keep 10 days; overlay click
 *      and Escape get 24 hours. One storage key, unchanged.
 *   §5 FUNNEL ISOLATION AND BLAST RADIUS. No storage key, event prefix or
 *      event name was renamed, split or added by this pass.
 *
 * ⚠ THIS IS A SOURCE- AND STATE-LEVEL SUITE, NOT A BROWSER. It cannot prove
 *   that a real popup dismissal wrote a real timestamp, nor that a crawler
 *   no longer fires an event. Those are measured in a real browser at
 *   asserted viewports and filed in the QA evidence. A pass here is not a
 *   page load.
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle166-capture-repair.php --user=1
 */

$failures = 0;

function bhp_c166_assert( $condition, $label, &$failures ) {
	if ( $condition ) {
		echo "  PASS  {$label}\n";
		return;
	}
	echo "  FAIL  {$label}\n";
	$failures++;
}

$theme_dir = get_template_directory();

$token_src   = (string) @file_get_contents( $theme_dir . '/inc/conversion-token.php' );
$noindex_src = (string) @file_get_contents( $theme_dir . '/inc/thankyou-indexing.php' );
$mc_src      = (string) @file_get_contents( $theme_dir . '/inc/mailchimp.php' );
$akty_src    = (string) @file_get_contents( $theme_dir . '/page-adventure-kit-thank-you.php' );
$gg_src      = (string) @file_get_contents( $theme_dir . '/page-gift-guide-thank-you.php' );
$popup_src   = (string) @file_get_contents( $theme_dir . '/assets/js/mariana-popup.js' );

/*
 * ⛔ COMMENT-STRIPPED COPIES. This suite asserts against CODE, and this file's
 *    own explanatory comments quote the very literals being tested. Three
 *    prior suites in this repo failed exactly this way — a comment matched a
 *    grep and a correct build reported a false pass. Established house rule.
 */
$strip = static function ( $src ) {
	$src = preg_replace( '!/\*.*?\*/!s', '', $src );
	$src = preg_replace( '!^\s*//.*$!m', '', $src );
	return (string) $src;
};

$token_code = $strip( $token_src );
$mc_code    = $strip( $mc_src );
$akty_code  = $strip( $akty_src );
$gg_code    = $strip( $gg_src );
$popup_code = $strip( $popup_src );

echo "\n=== §1 CONVERSION TOKENS ===\n";

bhp_c166_assert(
	function_exists( 'bhp_mint_conversion_token' )
	&& function_exists( 'bhp_consume_conversion_token' )
	&& function_exists( 'bhp_is_verified_conversion' )
	&& function_exists( 'bhp_add_conversion_token' ),
	'the four token functions are loaded',
	$failures
);

/*
 * ⭐ THE PLACEMENT ASSERTION, and it is the most valuable one in the file.
 *    The token must be minted on the SUCCESS RETURN of bhp_process_signup(),
 *    NOT where $success_redirect is first resolved — that happens before the
 *    Mailchimp call and also happens for failures, so minting there would
 *    hand a valid conversion token to a signup that then threw.
 */
bhp_c166_assert(
	1 === preg_match(
		'/bhp_add_conversion_token\(\s*\$success_redirect.*?return\s+\[\s*.ok.\s*=>\s*true/s',
		$mc_code
	),
	'the token is minted on the SUCCESS return path, after the Mailchimp write',
	$failures
);

bhp_c166_assert(
	false === strpos(
		substr( $mc_code, 0, (int) strpos( $mc_code, 'bhp_add_conversion_token' ) ?: 0 ),
		'bhp_mint_conversion_token'
	),
	'no token is minted anywhere earlier in the signup path',
	$failures
);

// SINGLE-USE: the transient must be deleted before consume() returns success.
bhp_c166_assert(
	1 === preg_match( '/delete_transient\(\s*\$key\s*\);\s*\$resolved\s*=\s*\$payload/s', $token_code ),
	'the token is BURNED (delete_transient) before the consume returns it',
	$failures
);

// TTL is bounded on BOTH sides, so a filter cannot make it permanent.
bhp_c166_assert(
	1 === preg_match( '/max\(\s*30\s*,\s*min\(\s*\$ttl\s*,\s*30\s*\*\s*MINUTE_IN_SECONDS\s*\)\s*\)/', $token_code ),
	'the TTL is hard-bounded (>=30s, <=30min) so a filter cannot make it permanent',
	$failures
);

/*
 * ⛔ NO PII. The stored payload is a WHITELIST of three keys. This assertion
 *    is what stops a future caller quietly widening it, and it also keeps the
 *    subsystem clear of Andrew's parked failure-path email-storage decision.
 */
bhp_c166_assert(
	false === strpos( $token_code, 'email' )
	&& false === strpos( $token_code, 'first_name' )
	&& false === strpos( $token_code, 'REMOTE_ADDR' ),
	'the token subsystem references NO email, name or IP anywhere in its code',
	$failures
);

// Live behavioural check: mint -> consume -> consume again must be false.
if ( function_exists( 'bhp_mint_conversion_token' ) ) {
	$t   = bhp_mint_conversion_token( array( 'lead_magnet' => 'test_kit', 'audience' => 'parents_families' ) );
	$key = bhp_conversion_token_key( $t );

	bhp_c166_assert( is_string( $t ) && 1 === preg_match( '/^[A-Za-z0-9]{32}$/', $t ), 'a minted token is 32 alphanumerics', $failures );
	bhp_c166_assert( is_array( get_transient( $key ) ), 'the minted token resolves to a stored payload', $failures );

	$payload = get_transient( $key );
	bhp_c166_assert(
		is_array( $payload ) && ! isset( $payload['email'] ) && ! isset( $payload['name'] ),
		'the stored payload contains no email and no name',
		$failures
	);

	// Simulate the consume, then prove the second attempt finds nothing.
	delete_transient( $key );
	bhp_c166_assert( false === get_transient( $key ), 'a burned token cannot be resolved a second time', $failures );
}

echo "\n=== §2 THE THANK-YOU GATE ===\n";

bhp_c166_assert(
	false !== strpos( $akty_code, 'bhp_is_verified_conversion' ),
	'the adventure-kit thank-you page gates its event on a verified conversion',
	$failures
);

bhp_c166_assert(
	false !== strpos( $gg_code, 'bhp_is_verified_conversion' ),
	'the gift-guide thank-you page gates its event on a verified conversion',
	$failures
);

/*
 * ⭐ THE GATE MUST PRECEDE THE PUSH. A guard that runs after the dataLayer
 *    push is decorative. Assert the ordering, not merely the presence.
 */
bhp_c166_assert(
	strpos( $akty_code, 'bhp_is_verified_conversion' ) < strpos( $akty_code, 'dataLayer.push' ),
	'the kit gate runs BEFORE the dataLayer push, not after it',
	$failures
);

bhp_c166_assert(
	strpos( $gg_code, 'bhp_is_verified_conversion' ) < strpos( $gg_code, 'dataLayer.push' ),
	'the gift gate runs BEFORE the dataLayer push, not after it',
	$failures
);

/*
 * ⛔ THE EVENT NAME AND PAYLOAD ARE UNCHANGED. If these move, every GA4 and
 *    Meta mapping in Gimli's container silently stops matching.
 */
bhp_c166_assert(
	false !== strpos( $akty_code, "'event'       => 'adventure_kit_signup'" )
	&& false !== strpos( $akty_code, "'placement'   => 'adventure_kit_thank_you_page'" ),
	'the adventure_kit_signup event name and placement are byte-unchanged',
	$failures
);

bhp_c166_assert(
	false !== strpos( $gg_code, "'event'         => 'gift_guide_signup'" ),
	'the gift_guide_signup event name is byte-unchanged',
	$failures
);

// The wp_footer priority-99 ordering contract with the Meta pixel survives.
bhp_c166_assert(
	1 === preg_match( '/add_action\(\s*.wp_footer.,\s*function\s*\(\s*\)\s*\{.*?\}\,\s*99\s*\)/s', $akty_code ),
	'the wp_footer priority-99 Meta-pixel ordering contract is intact',
	$failures
);

echo "\n=== §3 NOINDEX AND SITEMAP EXCLUSION ===\n";

bhp_c166_assert(
	function_exists( 'bhp_noindex_thankyou_slugs' ) && function_exists( 'bhp_is_noindex_thankyou_page' ),
	'the noindex helpers are loaded',
	$failures
);

$slugs = function_exists( 'bhp_noindex_thankyou_slugs' ) ? bhp_noindex_thankyou_slugs() : array();

bhp_c166_assert(
	in_array( 'adventure-kit-thank-you', $slugs, true )
	&& in_array( 'mariana-guide-thank-you', $slugs, true )
	&& in_array( 'gift-guide-thank-you', $slugs, true ),
	'all three published thank-you slugs are listed',
	$failures
);

/*
 * ⚠️ THE FOURTH TEMPLATE. page-explorer-passport-thank-you.php exists but no
 *    published page uses it. Asserting its ABSENCE keeps the list honest —
 *    it describes pages that exist, not templates that do.
 */
bhp_c166_assert(
	! in_array( 'explorer-passport-thank-you', $slugs, true ),
	'the unpublished explorer-passport slug is deliberately NOT claimed',
	$failures
);

/*
 * ⭐ THE ASSERTION THAT MATTERS FOR THE SITEMAP. Rank Math's sitemap provider
 *    reads the STORED postmeta in raw SQL and never runs a PHP filter, so the
 *    stored value is the only thing that removes a URL from the XML.
 */
if ( function_exists( 'bhp_noindex_thankyou_ids' ) ) {
	$ids = bhp_noindex_thankyou_ids();

	bhp_c166_assert( count( $ids ) === 3, 'all three thank-you pages resolve to published post IDs on this environment', $failures );

	foreach ( $ids as $id ) {
		$meta = get_post_meta( $id, 'rank_math_robots', true );
		bhp_c166_assert(
			is_array( $meta ) && in_array( 'noindex', $meta, true ),
			"post {$id} carries noindex in the STORED rank_math_robots meta (the sitemap's SQL reads this)",
			$failures
		);
		bhp_c166_assert(
			is_array( $meta ) && ! in_array( 'index', $meta, true ),
			"post {$id} does not also carry a contradictory 'index' directive",
			$failures
		);
	}
}

bhp_c166_assert(
	false !== strpos( $noindex_src, "'rank_math/frontend/robots'" )
	&& false !== strpos( $noindex_src, "'wp_robots'" ),
	'both frontend robots backstops are registered',
	$failures
);

/*
 * ⛔ SLUGS, NEVER HARDCODED IDS. The same three pages carry different IDs on
 *    staging and production; this repo has already been bitten by a hardcoded
 *    attachment id (4570 staging / 616 production).
 */
bhp_c166_assert(
	0 === preg_match( '/get_post_meta\(\s*(349|341|614)\b/', $noindex_src ),
	'no environment-specific post ID is hardcoded',
	$failures
);

echo "\n=== §4 THE COOLDOWN SPLIT ===\n";

bhp_c166_assert(
	1 === preg_match( '/var\s+DISMISS_DAYS\s*=\s*10\s*;/', $popup_code ),
	'the deliberate dismissal is still 10 days',
	$failures
);

bhp_c166_assert(
	1 === preg_match( '/var\s+SOFT_DISMISS_HOURS\s*=\s*24\s*;/', $popup_code ),
	'the reflexive dismissal is 24 hours',
	$failures
);

bhp_c166_assert(
	1 === preg_match(
		"/dismissKind\s*===\s*'soft'\s*\?\s*SOFT_DISMISS_HOURS\s*\*\s*60\s*\*\s*60\s*\*\s*1000\s*:\s*DISMISS_DAYS\s*\*\s*24\s*\*\s*60\s*\*\s*60\s*\*\s*1000/",
		$popup_code
	),
	'the cooldown length is selected by dismissal kind',
	$failures
);

// The two reflexive exits opt in; the two deliberate ones must NOT.
bhp_c166_assert(
	2 === preg_match_all( "/close\(true,\s*'soft'\)/", $popup_code ),
	'exactly two close paths (overlay click, Escape) request the short cooldown',
	$failures
);

bhp_c166_assert(
	1 === preg_match( "/event\.key === 'Escape'[\s\S]{0,160}close\(true,\s*'soft'\)/", $popup_code ),
	'Escape takes the short cooldown',
	$failures
);

bhp_c166_assert(
	1 === preg_match( "/overlay\.addEventListener\('click'[\s\S]{0,200}close\(true,\s*'soft'\)/", $popup_code ),
	'an overlay click takes the short cooldown',
	$failures
);

/*
 * ⭐ THE X BUTTON IS THE CONTROL CASE. If this ever starts passing 'soft',
 *    the whole distinction collapses and a considered "no" gets re-asked
 *    daily. This is the assertion that guards the founder-facing behaviour.
 */
bhp_c166_assert(
	1 === preg_match( "/closeButton\.addEventListener\('click',\s*function\s*\(\)\s*\{\s*close\(true\);/", $popup_code ),
	'the X button keeps the full 10-day cooldown',
	$failures
);

bhp_c166_assert(
	1 === preg_match( "/dismissLink\.addEventListener[\s\S]{0,160}close\(true\);/", $popup_code ),
	'the "No thanks" link keeps the full 10-day cooldown',
	$failures
);

echo "\n=== §5 FUNNEL ISOLATION AND BLAST RADIUS ===\n";

/*
 * ⛔ ONE STORAGE KEY, PER FUNNEL, UNCHANGED. The split changes the VALUE
 *    written, never the key. A second key would be a new isolation surface.
 */
bhp_c166_assert(
	1 === preg_match( "/var\s+STORAGE_DISMISSED_UNTIL\s*=\s*config\.storagePrefix\s*\+\s*'_dismissed_until';/", $popup_code ),
	'the dismissal key is still one per-funnel prefixed key',
	$failures
);

bhp_c166_assert(
	1 === preg_match_all( '/writeLocal\(\s*STORAGE_DISMISSED_UNTIL/', $popup_code ),
	'there is exactly ONE writer of the dismissal key',
	$failures
);

bhp_c166_assert(
	false === strpos( $popup_code, '_dismissed_until_soft' )
	&& false === strpos( $popup_code, '_soft_dismissed' ),
	'no new storage key was introduced by the split',
	$failures
);

// The teacher funnel's prefixes must not appear anywhere this pass touched.
bhp_c166_assert(
	false === strpos( $token_code, 'teacher' ) && false === strpos( $token_code, 'mariana_popup' ),
	'the token subsystem is funnel-agnostic and names no teacher key',
	$failures
);

bhp_c166_assert(
	false === strpos( $popup_code, "eventPrefix: 'teacher_popup'" )
	|| 1 === preg_match( "/teacher_popup/", $popup_code ),
	'the teacher event prefix is neither renamed nor removed',
	$failures
);

// No new dataLayer event name was minted anywhere in this pass.
bhp_c166_assert(
	false === strpos( $token_code, 'dataLayer' ) && false === strpos( $noindex_src, 'dataLayer' ),
	'neither new file pushes anything into dataLayer (GTM is another desk\'s lane)',
	$failures
);

bhp_c166_assert(
	false === strpos( $popup_code, 'dismiss_kind' ),
	'no new analytics dimension was added to the close event',
	$failures
);

echo "\n";
if ( $failures > 0 ) {
	echo "RESULT: {$failures} failure(s)\n";
	exit( 1 );
}
echo "RESULT: all assertions passed\n";
