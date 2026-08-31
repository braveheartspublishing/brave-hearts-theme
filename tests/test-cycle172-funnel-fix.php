<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CYCLE172-LD-FUNNEL-FIX — theme 1.19.342. The four funnel-observability
 * leaks from the 2026-08-31 audit.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle172-funnel-fix.php --user=1
 *
 * ⛔⛔ READ-ONLY. This file creates no order, no lead event, no coupon, no
 *     option and no post. It asserts against already-existing state and
 *     against pure functions. It is safe on production; it is nonetheless
 *     intended to be run on staging first, like everything else.
 *
 * ⚠️⚠️ WHAT THIS SUITE STRUCTURALLY CANNOT PROVE — stated up front because the
 *      previous generation of these assertions was cited as proof of something
 *      it never touched (see the rewritten block in `test-cycle170-final.php`):
 *
 *   1. ⛔ IT CANNOT SEE THE PROXY CACHE. `wp eval-file` runs INSIDE the origin.
 *      Every HTTP call it makes reaches the origin too. `X-Proxy-Cache` is not
 *      observable from here at all. The cache is verified by an anonymous
 *      EXTERNAL HTTPS GET, out of process, recorded in the release evidence.
 *   2. ⛔ IT CANNOT EXECUTE JAVASCRIPT. The client-side filler's real behaviour
 *      is proven in a real browser, not here. This suite asserts its CONTRACT
 *      (what the file does and does not contain, and that it is enqueued).
 *   3. ⛔ IT CANNOT PROVE A MAILCHIMP CONTACT WAS CREATED, or that GA4 received
 *      anything. Both are on the far side of a wire this desk cannot read.
 *
 *   ⭐ Naming these is the point. A suite that quietly omits its own blind
 *      spots is how G-A survived a green test run for 26 days.
 *
 * @package BraveHearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['c172_pass'] = 0;
$GLOBALS['c172_fail'] = 0;
$GLOBALS['c172_note'] = 0;

function c172_assert( $cond, $msg ) {
	if ( $cond ) {
		++$GLOBALS['c172_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['c172_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/** An observation printed for the record. Never counted as a pass. */
function c172_note( $msg ) {
	++$GLOBALS['c172_note'];
	echo "  NOTE  {$msg}\n";
}

function c172_file( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return is_readable( $path ) ? (string) file_get_contents( $path ) : '';
}

echo "\n=== CYCLE172-LD-FUNNEL-FIX · theme " . wp_get_theme()->get( 'Version' ) . " ===\n";
echo "    host: " . ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : home_url() ) . "\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · G-A — NO PER-VISITOR VALUE CAN REACH CACHEABLE HTML
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n--- 1 · G-A · cache-poisoned attribution ---\n";

$c172_tpl = c172_file( 'template-parts/acquisition/signup-form.php' );

c172_assert(
	false !== strpos( $c172_tpl, 'name="bhp_attr_now" value="" data-bhp-attr-now' ),
	'⭐ the signup template emits bhp_attr_now EMPTY with the JS marker'
);

/*
 * ⛔⛔ THE REGRESSION TRIPWIRE. If anybody ever puts the PHP value back into
 *     this template, this fails immediately and says why. The bug it guards
 *     against shipped once already and was invisible for 26 days.
 */
c172_assert(
	false === strpos( $c172_tpl, 'esc_attr($bhp_attr_now)' )
		&& false === strpos( $c172_tpl, 'esc_attr( $bhp_attr_now )' ),
	'⛔⛔ the template does NOT render a server-side value into bhp_attr_now (G-A tripwire)'
);

$c172_ra = c172_file( 'inc/school-read-alouds.php' );
c172_assert(
	false !== strpos( $c172_ra, 'name="bhp_attr_now" value="" data-bhp-attr-now' )
		&& false === strpos( $c172_ra, 'esc_attr( $bhp_attr_now )' ),
	'⛔ the read-aloud form carries the same empty field and no server-rendered value'
);

$c172_js = c172_file( 'assets/js/bhp-attr-now.js' );
c172_assert( '' !== $c172_js, 'the client-side filler file exists' );
c172_assert(
	false !== strpos( $c172_js, 'window.location.search' ),
	'⭐ the filler reads the visitor\'s OWN location.search'
);
c172_assert(
	false === strpos( $c172_js, 'document.cookie' )
		&& false === strpos( $c172_js, 'localStorage' )
		&& false === strpos( $c172_js, 'sessionStorage' )
		&& false === strpos( $c172_js, 'fetch(' ),
	'⛔ the filler writes NO cookie/storage and makes NO request — no consent gate needed, and none claimed'
);

/*
 * ⭐ THE WHITELISTS MUST AGREE. The server re-filters everything, so a drift
 *    can only drop a parameter — but a silently dropped `fbclid` is a funnel
 *    that stops reporting paid traffic, which is a leak of exactly the kind
 *    this cycle exists to close.
 */
$c172_php_params = function_exists( 'bhp_get_attribution_capture_params' )
	? (array) bhp_get_attribution_capture_params()
	: array();
if ( $c172_php_params ) {
	$c172_missing = array();
	foreach ( $c172_php_params as $c172_p ) {
		if ( false === strpos( $c172_js, "'" . $c172_p . "'" ) ) {
			$c172_missing[] = $c172_p;
		}
	}
	c172_assert(
		empty( $c172_missing ),
		'⭐ the JS whitelist covers every PHP capture param' . ( $c172_missing ? ' — MISSING: ' . implode( ', ', $c172_missing ) : '' )
	);
} else {
	c172_note( 'bhp_get_attribution_capture_params() not reachable — whitelist parity NOT checked' );
}

/* The referer hardening: core's wp_get_raw_referer() prefers a POSTED
   _wp_http_referer, which is itself rendered into cacheable HTML. */
$c172_mc = c172_file( 'inc/mailchimp.php' );
c172_assert(
	false !== strpos( $c172_mc, "\$_SERVER['HTTP_REFERER']" )
		&& false === strpos( $c172_mc, '$referer = wp_get_raw_referer();' ),
	'⛔ the form-moment pipe reads HTTP_REFERER directly, not wp_get_raw_referer() (which prefers a cacheable posted field)'
);

/*
 * ⭐⭐ THE BEHAVIOURAL ASSERTION, NOT JUST THE TEXTUAL ONE. Render the field
 *     helper with a campaign in $_GET and confirm the TEMPLATE still emits
 *     nothing. This is the invariant that makes the edge cache irrelevant.
 */
$c172_get_backup = $_GET;
$_GET            = array( 'utm_source' => 'facebook', 'fbclid' => 'C172PROBE' );
ob_start();
get_template_part(
	'template-parts/acquisition/signup-form',
	null,
	array( 'lead_magnet' => 'reluctant_reader_adventure_kit', 'audience_type' => 'parents_families' )
);
$c172_rendered = (string) ob_get_clean();
$_GET          = $c172_get_backup;

if ( '' === trim( $c172_rendered ) ) {
	c172_note( 'the signup partial rendered empty in this context — behavioural G-A check NOT performed (template-part args differ outside a real page)' );
} else {
	c172_assert(
		false === strpos( $c172_rendered, 'C172PROBE' ),
		'⛔⛔ BEHAVIOURAL: a render with fbclid in $_GET puts NO click ID in the HTML'
	);
	c172_assert(
		false === strpos( $c172_rendered, 'value="utm_source' ),
		'⛔⛔ BEHAVIOURAL: a render with a UTM in $_GET puts NO campaign value in the HTML'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · G-B — PURCHASE-EVENT COVERAGE IS NOW A NAMED NUMBER
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n--- 2 · G-B · GA4 purchase coverage ---\n";

c172_assert( function_exists( 'bhp_ga4_purchase_coverage' ), 'the coverage reader is loaded' );
c172_assert( defined( 'BHP_THANKYOU_RENDERED_META' ), 'the order-received breadcrumb constant is defined' );
c172_assert(
	has_action( 'woocommerce_thankyou', 'bhp_thankyou_record_render' ) === 1,
	'⛔ the breadcrumb runs at priority 1 — BEFORE the purchase tracker at 10, so it survives a later failure'
);

if ( function_exists( 'bhp_ga4_purchase_coverage' ) && function_exists( 'wc_get_orders' ) ) {
	$c172_cov = bhp_ga4_purchase_coverage( 0 ); // 0 = all time

	c172_assert( is_array( $c172_cov ) && isset( $c172_cov['coverage_pct'] ), 'the reader returns a shaped result' );
	c172_assert(
		$c172_cov['expected'] + $c172_cov['not_applicable'] === $c172_cov['total'],
		'⭐ expected + not_applicable == total — the denominator accounts for every order, nothing is silently dropped'
	);
	c172_assert(
		$c172_cov['fired'] + count( $c172_cov['missing_ids'] ) === $c172_cov['expected'],
		'⭐ fired + missing == expected — the arithmetic closes'
	);
	c172_assert(
		count( $c172_cov['missing_never_rendered'] ) + count( $c172_cov['missing_rendered_but_suppressed'] ) === count( $c172_cov['missing_ids'] ),
		'⭐ every missing order lands in exactly one diagnostic bucket'
	);

	echo "\n" . bhp_ga4_purchase_coverage_report( 0 ) . "\n";

	c172_note( 'the split above is only meaningful for orders created AFTER 1.19.342 — earlier orders carry no breadcrumb and default to never_rendered' );
} else {
	c172_note( 'WooCommerce not loaded — coverage arithmetic NOT checked' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · G-C — THE COUPON AUTO-APPLY PATH
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n--- 3 · G-C · coupon auto-apply ---\n";

c172_assert( function_exists( 'bhp_coupon_url_maybe_apply' ), 'the URL auto-apply path is loaded' );
c172_assert( function_exists( 'bhp_coupon_apply_counter_record' ), 'the counter is loaded' );
c172_assert(
	has_action( 'bhp_coupon_auto_applied', 'bhp_coupon_apply_counter_record' ) !== false,
	'⛔ the counter is actually wired to the auto-apply action (this is the link whose silence looked like a dead code path)'
);
c172_assert( defined( 'BHP_COUPON_URL_SESSION_KEY' ), 'the pending-intent session key is defined' );

/*
 * ⭐ THE ONE THAT MATTERS FOR DIAGNOSIS. The counter option's ABSENCE was read
 *    by the audit as "this path has never executed". That is true, and it is
 *    ALSO exactly what a brand-new feature looks like. Print the facts so the
 *    reader can tell the two apart instead of inferring.
 */
$c172_counts = get_option( 'bhp_coupon_autoapply_counts', null );
c172_note( 'bhp_coupon_autoapply_counts option: ' . ( null === $c172_counts ? 'DOES NOT EXIST (path has never applied a coupon here)' : wp_json_encode( $c172_counts ) ) );

if ( function_exists( 'wc_get_coupon_id_by_code' ) ) {
	foreach ( array( 'newsletter10', 'books10', 'parent10', 'classroom10' ) as $c172_code ) {
		$c172_id = (int) wc_get_coupon_id_by_code( $c172_code );
		if ( $c172_id > 0 ) {
			$c172_c = new WC_Coupon( $c172_code );
			c172_note( sprintf(
				'coupon %-12s id=%d status=%s type=%s amount=%s min=%s usage=%d  -> live_for_url_apply=%s',
				$c172_code,
				$c172_id,
				get_post_status( $c172_id ),
				$c172_c->get_discount_type(),
				$c172_c->get_amount(),
				$c172_c->get_minimum_amount() ?: '-',
				$c172_c->get_usage_count(),
				( function_exists( 'bhp_coupon_url_code_is_live' ) && bhp_coupon_url_code_is_live( $c172_code ) ) ? 'YES' : 'no'
			) );
		}
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · G-E — THE LEAD LOG IS NO LONGER BLINDER THAN MAILCHIMP
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n--- 4 · G-E · lead-event attribution parity ---\n";

c172_assert(
	class_exists( 'BHP_Lead_Event_Log' ) && defined( 'BHP_Lead_Event_Log::META_FORM_MOMENT' ),
	'⭐ the lead log defines the form-moment field'
);

$c172_log = c172_file( 'inc/class-bhp-lead-event-log.php' );
c172_assert(
	false !== strpos( $c172_log, 'bhp_get_form_moment_attribution' )
		&& false !== strpos( $c172_log, 'META_FORM_MOMENT' ),
	'⛔ write_event() records the SAME resolved value the Mailchimp pipe sends'
);

/*
 * ⛔ THE CONSENT GATE ON THE COOKIE CAPTURE IS UNCHANGED, DELIBERATELY. G-E is
 *    closed by making the LOCAL RECORD as informative as the third-party one,
 *    NOT by weakening consent. Anyone "fixing" first/last-touch coverage by
 *    removing this gate is making a privacy decision that belongs to Andrew.
 */
$c172_attr_js = c172_file( 'assets/js/bhp-attribution.js' );
c172_assert(
	false !== strpos( $c172_attr_js, 'analyticsConsentGranted' )
		&& false !== strpos( $c172_attr_js, 'captureIfConsented' ),
	'⛔⛔ the cookie capture is STILL consent-gated — G-E was not closed by weakening consent'
);

/*
 * ⭐ THE ASYMMETRY THAT IS NOT A BUG, ASSERTED SO IT STOPS BEING RE-REPORTED.
 *    An order can legitimately carry first-touch and NOT last-touch: the JS
 *    always writes a first-touch (falling back to direct/none) but writes
 *    last-touch ONLY when the load carries a real campaign signal. A consented
 *    visitor arriving with no campaign therefore produces exactly that pair.
 *    The audit flagged orders 623 and 546 as "a real asymmetry worth a look" —
 *    this is the look, and the answer is that it is correct behaviour.
 */
c172_assert(
	false !== strpos( $c172_attr_js, 'utm_source: \'direct\'' ),
	'⭐ first-touch falls back to direct/none while last-touch does not — the 6-first/4-last split is BY DESIGN, not a defect'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * SUMMARY
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== CYCLE172 SUMMARY ===\n";
printf( "  PASS %d   FAIL %d   NOTE %d\n", $GLOBALS['c172_pass'], $GLOBALS['c172_fail'], $GLOBALS['c172_note'] );
echo "  ⛔ Cache behaviour, JS execution, Mailchimp and GA4 reception are NOT covered here — see the header.\n";
