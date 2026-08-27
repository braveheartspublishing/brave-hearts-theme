<?php
/**
 * Brave Hearts — a kit CTA on the blog opens the kit popup. Theme 1.19.271,
 * 2026-08-19, `CYCLE165-LD-ITERATE-7-KIT-CTA-POPUP`.
 *
 * ⭐ THE RULING THIS SUITE GUARDS. Andrew Signore, verbatim, relayed by the
 *    Chief of Staff as first-hand founder wording (carrier item 117):
 *    "When someone on the blog hits Get the free kit - it should be a pop up
 *    where they can immediately subscribe- not send them to the reluctant
 *    reader page. Less steps."
 *
 * WHAT IS ASSERTED:
 *
 *   1. THE TARGET EXISTS. The id the CTA points at is the id the popup
 *      template actually renders. This is the assertion that catches a
 *      rename producing a button that opens nothing.
 *   2. THE SURFACE RULE. Both limbs — a blog surface AND the popup genuinely
 *      rendered on this request — driven through real WP_Query objects, not
 *      read out of the source.
 *   3. THE ANCHOR IS STILL AN ANCHOR. The rendered CTA carries a real `href`
 *      to the kit page alongside the trigger attributes. ⛔ THIS IS THE
 *      NO-JAVASCRIPT ASSERTION and it is the most important one here: the
 *      failure mode this release could produce is a dead button, and a dead
 *      button is worse than the extra step the founder asked us to remove.
 *   4. ONLY THE KIT CTA. The toolkit, gift-guide and book CTAs are untouched.
 *   5. THE CONTENT FILTER stamps a hand-written kit link, leaves every other
 *      link alone, and never stamps the same anchor twice.
 *   6. THE ENGINE'S EXPLICIT PATH. The three frequency rules suppress the
 *      AUTOMATIC open only; an explicit click still opens; the delegated
 *      listener is not capture-phase and never calls `stopPropagation()`, so
 *      the contextual-CTA analytics click still fires.
 *   7. NOTHING ELSE MOVED — storage keys, event prefixes, the thank-you path,
 *      the delay numbers and the end-of-post inline capture are unchanged.
 *
 * ⚠ THIS IS A SOURCE- AND RENDER-LEVEL SUITE, NOT A BROWSER. It cannot prove
 *   that a click opened anything. That is measured in a real browser at 390
 *   and 1440 and filed in the QA evidence. A pass here is not a click.
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-kit-cta-popup.php --user=1
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$failures = 0;

function bhp_kit_assert( $condition, $label, &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	++$failures;
	echo "FAIL: {$label}\n";
}

$theme_dir  = get_template_directory();
$tpl        = (string) @file_get_contents( $theme_dir . '/template-parts/acquisition/parent-ab-popup.php' );
$js         = (string) @file_get_contents( $theme_dir . '/assets/js/mariana-popup.js' );
$engine_src = (string) @file_get_contents( $theme_dir . '/inc/class-bhp-cta-engine.php' );
$capture    = (string) @file_get_contents( $theme_dir . '/template-parts/acquisition/post-end-capture.php' );

bhp_kit_assert( '' !== $tpl, 'parent-ab-popup.php is readable', $failures );
bhp_kit_assert( '' !== $js, 'mariana-popup.js is readable', $failures );
bhp_kit_assert( '' !== $engine_src, 'class-bhp-cta-engine.php is readable', $failures );

/**
 * Run a callback with a real blog post (or product, or page) as the queried
 * object, as an ANONYMOUS visitor, then restore every global.
 *
 * ⛔ THE CURRENT USER IS DROPPED, AND WITHOUT THAT THIS SUITE IS WORTHLESS.
 *    `bhp_should_show_any_popup()` returns false outright for a logged-in
 *    administrator and this file runs as `--user=1`. Left alone, every
 *    "does not render" assertion below would pass for entirely the wrong
 *    reason. Same reasoning, and the same fix, as `test-popup-ab.php`.
 *
 * Returns null when the environment has no post of the requested shape, so a
 * missing fixture reports as a skip rather than as a false pass.
 */
function bhp_kit_in_context( array $query_args, callable $callback ) {
	$ids = get_posts(
		array_merge(
			array(
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'publish',
			),
			$query_args
		)
	);
	if ( empty( $ids ) ) {
		return null;
	}

	$type = isset( $query_args['post_type'] ) ? $query_args['post_type'] : 'post';
	$args = ( 'page' === $type )
		? array( 'page_id' => $ids[0] )
		: array(
			'p'         => $ids[0],
			'post_type' => $type,
		);

	global $wp_query, $wp_the_query, $post;
	$saved_query = $wp_query;
	$saved_the    = $wp_the_query;
	$saved_post  = $post;
	$saved_user  = get_current_user_id();

	wp_set_current_user( 0 );
	$wp_query = new WP_Query( $args );

	/* ⛔ `wp_the_query` IS SWAPPED TOO, AND IT HAS TO BE. `is_main_query()`
	 *    compares the two globals by identity, and the content filter under
	 *    test guards on it. Swapping only `wp_query` would make that guard
	 *    false for a reason that never occurs in production, and the filter
	 *    would look broken while being correct. Both are restored below. */
	$wp_the_query = $wp_query;

	$result = null;
	if ( $wp_query->have_posts() ) {
		$wp_query->the_post();
		$result = $callback( $ids[0] );
		wp_reset_postdata();
	}

	$wp_query     = $saved_query;
	$wp_the_query = $saved_the;
	$post         = $saved_post;
	wp_set_current_user( $saved_user );

	return $result;
}

/* =====================================================================
 * 1. THE TARGET EXISTS
 * ================================================================== */

bhp_kit_assert( function_exists( 'bhp_kit_popup_dom_id' ), 'bhp_kit_popup_dom_id() is defined', $failures );
bhp_kit_assert( function_exists( 'bhp_kit_cta_opens_popup' ), 'bhp_kit_cta_opens_popup() is defined', $failures );
bhp_kit_assert( function_exists( 'bhp_kit_popup_trigger_attrs' ), 'bhp_kit_popup_trigger_attrs() is defined', $failures );

$dom_id = function_exists( 'bhp_kit_popup_dom_id' ) ? bhp_kit_popup_dom_id() : '';

/* ⛔ THE ASSERTION THAT CATCHES A DEAD BUTTON. The CTA points at an element id;
 *    if the template ever renders a different one, every kit CTA on the blog
 *    silently falls back to navigation and nobody notices for a release. */
bhp_kit_assert(
	'' !== $dom_id && false !== strpos( $tpl, 'id="' . $dom_id . '"' ),
	"the popup template renders id=\"{$dom_id}\" — the id the CTA is told to open",
	$failures
);

/* =====================================================================
 * 2. THE SURFACE RULE — BOTH LIMBS, DRIVEN THROUGH REAL QUERIES
 * ================================================================== */

$attrs_on_post = bhp_kit_in_context(
	array( 'post_type' => 'post' ),
	function () {
		return bhp_kit_popup_trigger_attrs( 'contextual_cta' );
	}
);
bhp_kit_assert(
	is_array( $attrs_on_post ) && isset( $attrs_on_post['data-bhp-popup-open'] ) && $attrs_on_post['data-bhp-popup-open'] === $dom_id,
	'ON A BLOG POST a kit CTA is marked as a popup trigger'
	. ( null === $attrs_on_post ? ' [SKIPPED: no published post on this environment]' : '' ),
	$failures
);

$attrs_on_product = bhp_kit_in_context(
	array( 'post_type' => 'product' ),
	function () {
		return bhp_kit_popup_trigger_attrs( 'contextual_cta' );
	}
);
bhp_kit_assert(
	array() === $attrs_on_product,
	'ON A PRODUCT PAGE it is NOT — no popup renders there, so a trigger would be a dead button'
	. ( null === $attrs_on_product ? ' [SKIPPED: no published product on this environment]' : '' ),
	$failures
);

$attrs_on_page = bhp_kit_in_context(
	array(
		'post_type'    => 'page',
		'post__not_in' => array( (int) get_option( 'page_on_front' ) ),
	),
	function () {
		return bhp_kit_popup_trigger_attrs( 'contextual_cta' );
	}
);
bhp_kit_assert(
	array() === $attrs_on_page,
	'ON AN ORDINARY PAGE it is NOT — the kit landing page and every other page keep plain links'
	. ( null === $attrs_on_page ? ' [SKIPPED: no non-front page on this environment]' : '' ),
	$failures
);

/* The second limb, stated as code rather than inferred: the trigger can only
 * ever be emitted where the popup itself is rendered. */
$fn_src = (string) @file_get_contents( $theme_dir . '/functions.php' );
bhp_kit_assert(
	1 === preg_match( '/function bhp_kit_cta_opens_popup\(\)\s*\{\s*return bhp_is_blog_kit_cta_surface\(\) && bhp_should_show_parent_ab_popup\(\);/', $fn_src ),
	'the gate is the blog surface AND the popup\'s own render rule, not a copy of it',
	$failures
);

/* =====================================================================
 * 3 & 4. THE RENDERED CTA — REAL href, TRIGGER ATTRIBUTES, KIT ONLY
 * ================================================================== */

if ( class_exists( 'BHP_CTA_Engine' ) ) {

	$kit_html = bhp_kit_in_context(
		array( 'post_type' => 'post' ),
		function () {
			$selected = BHP_CTA_Engine::select_specific( 'adventure_kit_signup', array() );
			if ( null === $selected ) {
				return '';
			}
			ob_start();
			BHP_CTA_Engine::render( $selected, 'blog_inline' );
			return (string) ob_get_clean();
		}
	);

	bhp_kit_assert(
		is_string( $kit_html ) && false !== strpos( $kit_html, 'data-bhp-popup-open="' . $dom_id . '"' ),
		'the rendered kit CTA carries the popup trigger'
		. ( null === $kit_html ? ' [SKIPPED: no published post on this environment]' : '' ),
		$failures
	);

	/* ⛔ THE NO-JAVASCRIPT FALLBACK. The href must survive intact. */
	bhp_kit_assert(
		is_string( $kit_html ) && false !== strpos( $kit_html, 'href="' . esc_url( home_url( '/reluctant-reader-adventure-kit/' ) ) . '"' ),
		'the kit CTA still carries its real href — with no JS it navigates exactly as before',
		$failures
	);

	bhp_kit_assert(
		is_string( $kit_html ) && false === strpos( $kit_html, 'href="#"' ) && false === strpos( $kit_html, '<button' ),
		'the kit CTA was NOT turned into a button or a dead anchor',
		$failures
	);

	/* The analytics contract the marketing side already reads. */
	bhp_kit_assert(
		is_string( $kit_html ) && false !== strpos( $kit_html, 'data-bhp-event="contextual_cta_click"' ),
		'the contextual-CTA click event attribute is unchanged',
		$failures
	);

	$other_html = bhp_kit_in_context(
		array( 'post_type' => 'post' ),
		function () {
			$selected = BHP_CTA_Engine::select_specific( 'educator_toolkit_signup', array() );
			if ( null === $selected ) {
				return '';
			}
			ob_start();
			BHP_CTA_Engine::render( $selected, 'blog_inline' );
			return (string) ob_get_clean();
		}
	);

	bhp_kit_assert(
		is_string( $other_html ) && '' !== $other_html && false === strpos( $other_html, 'data-bhp-popup-open' ),
		'a DIFFERENT lead-magnet CTA (the educator toolkit) is untouched — its own funnel, its own destination'
		. ( null === $other_html ? ' [SKIPPED: no published post on this environment]' : '' ),
		$failures
	);
} else {
	bhp_kit_assert( false, 'BHP_CTA_Engine is loaded', $failures );
}

/* =====================================================================
 * 5. THE CONTENT FILTER
 * ================================================================== */

if ( function_exists( 'bhp_kit_content_links_open_popup' ) ) {

	$kit_url  = home_url( '/reluctant-reader-adventure-kit/' );
	$fixture  = '<p><a href="' . esc_url( $kit_url ) . '">Get the free kit</a>'
		. '<a href="' . esc_url( home_url( '/books/' ) ) . '">The books</a>'
		. '<a href="https://example.com/reluctant-reader-adventure-kit/">Somebody else\'s</a>'
		. '<a href="' . esc_url( $kit_url ) . '" data-bhp-popup-open="' . esc_attr( $dom_id ) . '">Already stamped</a></p>';

	$filtered = bhp_kit_in_context(
		array( 'post_type' => 'post' ),
		function () use ( $fixture ) {
			/* `in_the_loop()` is true only inside the loop, which is exactly
			 * where `the_content` runs, so the filter is called the way
			 * WordPress calls it rather than in isolation. */
			return bhp_kit_content_links_open_popup( $fixture );
		}
	);

	/* Two anchors carry `data-bhp-popup-open="` — the one this filter stamped
	 * and the one that arrived already stamped — and exactly ONE carries the
	 * reason attribute, which only this filter writes. That pair is what
	 * proves it stamped once and did not double up. */
	bhp_kit_assert(
		is_string( $filtered ) && 2 === substr_count( $filtered, 'data-bhp-popup-open="' ),
		'exactly one NEW anchor is stamped: the on-site kit link, and the already-stamped one is not doubled'
		. ( null === $filtered ? ' [SKIPPED: no published post on this environment]' : '' ),
		$failures
	);

	bhp_kit_assert(
		is_string( $filtered ) && 1 === substr_count( $filtered, 'data-bhp-popup-open-reason="content_link"' ),
		'the filter stamped exactly one anchor with its own reason attribute',
		$failures
	);

	bhp_kit_assert(
		is_string( $filtered ) && false !== strpos( $filtered, '<a href="' . esc_url( home_url( '/books/' ) ) . '">' ),
		'a link to another page on this site is returned byte-identical',
		$failures
	);

	bhp_kit_assert(
		is_string( $filtered ) && false !== strpos( $filtered, '<a href="https://example.com/reluctant-reader-adventure-kit/">' ),
		'an OFF-SITE url with the same path is left alone — the host is checked, not just the path',
		$failures
	);

	bhp_kit_assert(
		is_string( $filtered ) && false !== strpos( $filtered, 'href="' . esc_url( $kit_url ) . '" data-bhp-popup-open' ),
		'the stamped anchor keeps its href — the filter adds attributes and rewrites nothing',
		$failures
	);

	/* Idempotence: running it twice must not change the result. */
	$twice = bhp_kit_in_context(
		array( 'post_type' => 'post' ),
		function () use ( $fixture ) {
			return bhp_kit_content_links_open_popup( bhp_kit_content_links_open_popup( $fixture ) );
		}
	);
	bhp_kit_assert(
		is_string( $twice ) && $twice === $filtered,
		'the filter is idempotent — a second pass is a no-op',
		$failures
	);

	bhp_kit_assert(
		1 === preg_match( "/add_filter\(\s*'the_content',\s*'bhp_kit_content_links_open_popup',\s*20\s*\)/", $fn_src ),
		'the filter runs at priority 20, after do_shortcode(11), so the CTA block is recognised rather than raced',
		$failures
	);
} else {
	bhp_kit_assert( false, 'bhp_kit_content_links_open_popup() is defined', $failures );
}

/* =====================================================================
 * 6. THE ENGINE'S EXPLICIT PATH
 * ================================================================== */

bhp_kit_assert(
	false !== strpos( $js, 'popup.bhpOpenExplicitly = function' ),
	'the engine registers an explicit opener on the popup element',
	$failures
);

/* ⛔ THE FOUNDER'S "ONCE PER SESSION MUST NOT BLOCK A CLICK" CONDITION.
 *    Before 1.19.271 the cooldown, the session flag and the shared guard each
 *    ABANDONED initPopup(), so nothing — not even a click — could open the
 *    popup afterwards. They must now set the flag instead. */
bhp_kit_assert(
	3 === substr_count( $js, 'autoSuppressed = true;' ),
	'all three frequency rules set the auto-suppression flag instead of abandoning setup',
	$failures
);

bhp_kit_assert(
	1 === preg_match( '/if \(autoSuppressed\) \{/', $js ),
	'the flag is consulted before any trigger is armed',
	$failures
);

/* A suppressed popup must arm NOTHING. The guard sits above every arming
 * branch and returns, so no timer and no scroll listener can be attached. */
bhp_kit_assert(
	strpos( $js, 'if (autoSuppressed) {' ) < strpos( $js, "if (mode === 'simple') {" ),
	'the suppression guard precedes the trigger-arming block — a suppressed popup arms no timer and no scroll listener',
	$failures
);

/* ⛔ THE PERMANENT SIGNED-UP SUPPRESSION IS NOT IN THAT SET. Somebody who
 *    already gave their email for this kit is never asked again; their click
 *    follows the link instead. */
bhp_kit_assert(
	1 === preg_match( "/readLocal\(STORAGE_SIGNED_UP\) === '1'\)\s*\{\s*return;/", $js ),
	'a visitor who already signed up still gets a plain return — no opener, no re-ask',
	$failures
);

/* ⛔ NOT CAPTURE PHASE, AND NO stopPropagation. The contextual-CTA analytics
 *    binder in nav.js listens for the same click; cancelling the navigation
 *    must not cancel the measurement. */
/* ⚠ THE HALTING METHOD IS NAMED HERE AND NOWHERE IN THE ENGINE. The engine's
 *   own comment describes the rule without quoting the token precisely so
 *   this substring test measures the code and not the prose. */
bhp_kit_assert(
	false === strpos( $js, 'stopPropagation' ),
	'the click handler never halts propagation — the contextual_cta_click measurement still fires',
	$failures
);

bhp_kit_assert(
	1 === preg_match( "/document\.addEventListener\(\s*'click',\s*function \(event\) \{/", $js ),
	'the kit trigger is a delegated bubble-phase click listener',
	$failures
);

/* Capture-phase registrations exist in this file for the exit-intent guards
 * and are legitimate. What must not exist is a capture-phase registration of
 * THIS handler, which would run ahead of the analytics binder. */
bhp_kit_assert(
	0 === preg_match( "/findPopupTrigger[\s\S]{0,200}?addEventListener\([^)]*,\s*true\s*\)/", $js ),
	'that listener is NOT registered in the capture phase',
	$failures
);

bhp_kit_assert(
	false !== strpos( $js, 'event.metaKey || event.ctrlKey || event.shiftKey || event.altKey' ),
	'modifier clicks keep their browser meaning — open in a new tab still works',
	$failures
);

/* The fallback is the ABSENCE of an opener. If no popup is on the page, or the
 * visitor is permanently suppressed, the handler must return BEFORE
 * preventDefault() so the anchor navigates. */
bhp_kit_assert(
	1 === preg_match( "/typeof popup\.bhpOpenExplicitly !== 'function'[\s\S]{0,400}?return;[\s\S]{0,400}?event\.preventDefault\(\);/", $js ),
	'with no popup on the page the handler returns BEFORE preventDefault() — the link still navigates',
	$failures
);

/* =====================================================================
 * 7. NOTHING ELSE MOVED
 * ================================================================== */

foreach (
	array(
		"'storagePrefix' => 'bhp_parent_popup'",
		"'eventPrefix'   => 'parent_popup'",
		"'thankYouPath'  => 'adventure-kit-thank-you'",
	) as $unchanged
) {
	bhp_kit_assert(
		false !== strpos( $tpl, $unchanged ),
		"funnel key unchanged: {$unchanged}",
		$failures
	);
}

/* ⭐ UPDATED 1.19.300 (`CYCLE167-LD-POPUP-TIME-ONLY`) ON FOUNDER CARRIER ITEM
 * 306 ("I think we keep our pop ups time only"). The FLOOR ITSELF is what this
 * assertion has always guarded and it is untouched at 15000 ms; only the array
 * key moved, because `simple` mode reads `delay` where `gated` mode read
 * `minDelay`. ⚠ Item 306 relayed via the Chief of Staff, not witnessed here. */
bhp_kit_assert(
	2 === substr_count( $tpl, "'delay' => 15000" ),
	'item 306: the founder\'s 15-second timer for the AUTOMATIC open is untouched',
	$failures
);

/* ⛔ THE END-OF-POST CAPTURE IS AN INLINE FORM AND STAYS ONE. The ruling bites
 *    on CTAs that NAVIGATE; this one already subscribes in place, so there is
 *    no step to remove and nothing here may turn it into a popup trigger. */
bhp_kit_assert(
	'' !== $capture
	&& false !== strpos( $capture, "template-parts/acquisition/signup-form" )
	&& false === strpos( $capture, 'data-bhp-popup-open' ),
	'the end-of-post capture is still an inline form and was not converted into a popup trigger',
	$failures
);

bhp_kit_assert(
	false === strpos( $engine_src, "home_url( '/reluctant-reader-adventure-kit/' ) . '#" )
	&& 1 === preg_match( "/'adventure_kit_signup'.*?return home_url\( '\/reluctant-reader-adventure-kit\/' \);/s", $engine_src ),
	'the kit CTA\'s destination URL is unchanged in the registry',
	$failures
);

echo "\n";
if ( $failures > 0 ) {
	echo "RESULT: {$failures} failure(s)\n";
	exit( 1 );
}
echo "RESULT: all assertions passed\n";
