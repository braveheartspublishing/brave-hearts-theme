<?php
/**
 * Brave Hearts — the Meta Pixel must be silent until the visitor says otherwise.
 *
 * `CYCLE145-LD-META-PIXEL` (2026-08-06, theme 1.19.203).
 *
 * ⛔ WHAT THIS SUITE IS ACTUALLY DEFENDING. A tracking pixel is the one class of
 *    code where "it works" and "it is correct" can point in opposite directions.
 *    A pixel that fires before consent works perfectly and is a compliance
 *    failure. A pixel that reports a made-up order total works perfectly and is
 *    a lie about revenue. Every assertion below is aimed at one of those two,
 *    not at whether the tag renders.
 *
 * ⭐ THE FIVE INVARIANTS, and the assertion group that holds each:
 *
 *    A  `fbq('consent','revoke')` is the FIRST fbq call and precedes `init`.
 *    B  Nothing can reach Meta before consent — and, stronger, the rendered
 *       bytes do not change when a consent cookie is present, so a page cache
 *       cannot serve one visitor's consent state to another.
 *    C  ViewContent, AddToCart and Purchase values are READ FROM the product
 *       and the order. Every expected value in group C is computed
 *       independently from the live record inside the test — there is not one
 *       hardcoded price or total anywhere in this file, deliberately, because
 *       a hardcoded expectation would pass against a hardcoded implementation.
 *    D  Purchase cannot fire twice for one order, cannot fire for a wrong key,
 *       and cannot fire for an unpaid or internal/test order.
 *    E  The two lead funnels stay isolated: the runtime touches NO storage key
 *       belonging to either (`.claude/rules/funnels.md`).
 *
 * ⚠ WHAT THIS SUITE CANNOT PROVE, stated rather than implied: it cannot prove
 *   the browser behaves as the emitted JavaScript says it will. It asserts what
 *   is emitted and what the server computes. The browser half — fbq exists, the
 *   queue is empty of transmissions before consent, events appear after
 *   accepting the WPConsent banner — is a live browser check on staging, and it
 *   is recorded in the release evidence, not here.
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-meta-pixel.php --user=1 --url=<site>
 * Exits non-zero on any failure. Group D creates and force-deletes real
 * WooCommerce orders and therefore SKIPS itself, loudly, anywhere but staging.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$failures = 0;
$skipped  = 0;

function bhp_mp_assert( &$failures, $label, $condition ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
		return;
	}
	$failures++;
	echo "FAIL: {$label}\n";
}

function bhp_mp_skip( &$skipped, $label, $why ) {
	$skipped++;
	echo "SKIP: {$label} — {$why}\n";
}

if ( ! class_exists( 'BHP_Meta_Pixel' ) ) {
	echo "FAIL: BHP_Meta_Pixel is not loaded — the theme's require_once is missing.\n";
	exit( 1 );
}

$pixel_id = BHP_Meta_Pixel::pixel_id();

/* ══════════════════════════════════════════════════════════════════════════
 * GROUP A — the base code, and the order of the first three fbq calls
 * ════════════════════════════════════════════════════════════════════════ */

echo "\n--- A. base code and call order ---\n";

$base = BHP_Meta_Pixel::base_code_html();

bhp_mp_assert( $failures, 'A1 a pixel ID is configured and is all digits', '' !== $pixel_id && ctype_digit( $pixel_id ) );

$pos_revoke = strpos( $base, "fbq('consent','revoke')" );
$pos_init   = strpos( $base, "fbq('init'" );
$pos_view   = strpos( $base, "fbq('track','PageView')" );

bhp_mp_assert( $failures, 'A2 the base code calls fbq(consent, revoke)', false !== $pos_revoke );
bhp_mp_assert( $failures, 'A3 the base code calls fbq(init, ...)', false !== $pos_init );
bhp_mp_assert( $failures, 'A4 the base code tracks PageView', false !== $pos_view );

/* The whole point. If this ever inverts, every visitor is measured before
 * they are asked, and the two-layer design collapses to one layer. */
bhp_mp_assert(
	$failures,
	'A5 ⭐ revoke PRECEDES init in the rendered output',
	false !== $pos_revoke && false !== $pos_init && $pos_revoke < $pos_init
);
bhp_mp_assert(
	$failures,
	'A6 init precedes PageView (an event before init would be dropped by the SDK)',
	false !== $pos_init && false !== $pos_view && $pos_init < $pos_view
);

bhp_mp_assert(
	$failures,
	'A7 init carries the real configured pixel ID, not a placeholder',
	false !== strpos( $base, "fbq('init','" . $pixel_id . "')" )
);

/* Layer 2. Meta's published snippet fetches fbevents.js inside the stub; this
 * build must not, or the "no third-party bytes before consent" claim is false. */
bhp_mp_assert(
	$failures,
	'A8 ⭐ the base code does NOT fetch fbevents.js — the SDK URL is absent from the stub',
	false === strpos( $base, 'connect.facebook.net' )
);

/* Meta's queue semantics must survive the relocation, or calls made before the
 * SDK arrives are lost rather than replayed. */
bhp_mp_assert( $failures, 'A9 the stub keeps Meta\'s own queue', false !== strpos( $base, 'n.queue=[]' ) && false !== strpos( $base, 'n.queue.push(arguments)' ) );

/* ── the noscript beacon: off by default, correct when switched on ── */

bhp_mp_assert(
	$failures,
	'A10 ⭐ the no-JS beacon is NOT rendered by default (it cannot be consent-gated by any mechanism that exists)',
	'' === BHP_Meta_Pixel::noscript_tag()
);

add_filter( 'bhp_meta_pixel_noscript', '__return_true' );
$noscript = BHP_Meta_Pixel::noscript_tag();
remove_filter( 'bhp_meta_pixel_noscript', '__return_true' );

bhp_mp_assert( $failures, 'A11 when switched on, the beacon is Meta\'s exact noscript img for this pixel', false !== strpos( $noscript, 'https://www.facebook.com/tr?id=' . $pixel_id . '&ev=PageView&noscript=1' ) && 0 === strpos( $noscript, '<noscript>' ) );

/* ══════════════════════════════════════════════════════════════════════════
 * GROUP B — nothing reaches Meta before consent, and the bytes do not vary
 * ════════════════════════════════════════════════════════════════════════ */

echo "\n--- B. pre-consent silence and cache safety ---\n";

$runtime = BHP_Meta_Pixel::runtime_js();

/* The grant path is the ONLY path that loads the SDK or lifts the revoke. */
bhp_mp_assert( $failures, 'B1 the runtime lifts consent in exactly one place', 1 === substr_count( $runtime, "fbq( 'consent', 'grant' )" ) );
bhp_mp_assert( $failures, 'B2 loadSdk() is the only thing that reads config.sdk into a script src', 1 === substr_count( $runtime, 's.src = config.sdk;' ) );
/*
 * Sliced between the two function headers rather than matched with a brace
 * pattern: grant()'s body contains its own `{ return; }` guard, so a `[^}]*`
 * regex reports a false failure. The slice is exact and the ordering assertion
 * inside it is the thing that matters.
 */
$bhp_mp_grant_start = strpos( $runtime, 'function grant()' );
$bhp_mp_grant_end   = strpos( $runtime, 'function revoke()' );
$bhp_mp_grant_body  = ( false !== $bhp_mp_grant_start && false !== $bhp_mp_grant_end && $bhp_mp_grant_end > $bhp_mp_grant_start )
	? substr( $runtime, $bhp_mp_grant_start, $bhp_mp_grant_end - $bhp_mp_grant_start )
	: '';

bhp_mp_assert(
	$failures,
	'B3 ⭐ the SDK is loaded from inside grant(), before the grant is signalled, and nowhere else',
	'' !== $bhp_mp_grant_body
		&& false !== strpos( $bhp_mp_grant_body, 'loadSdk( function () {' )
		&& strpos( $bhp_mp_grant_body, 'loadSdk(' ) < strpos( $bhp_mp_grant_body, "fbq( 'consent', 'grant' )" )
		&& 2 === substr_count( $runtime, 'loadSdk' . '(' ) // the definition and exactly one call site
);

/*
 * ⭐ B3b IS THE ASSERTION THAT WOULD HAVE CAUGHT THE ONE REAL DEFECT IN THIS
 * BUILD, and it is here because the defect was found by a browser and not by
 * this suite.
 *
 * fbevents.js processes `consent revoke` out of the queue and then STOPS
 * draining it. A `fbq('consent','grant')` issued in the same tick as the
 * script injection is therefore queued BEHIND the revoke that is blocking the
 * drain, and can never run: the pixel never initialises, never fetches its
 * config, and sends nothing, permanently. Reproduced in isolation against
 * Meta's own published snippet. The grant must be a LIVE call made from the
 * script's `onload`, which is what this asserts.
 */
/*
 * ⚠ THE THIRD LINE OF B3b WAS RELAXED AT 1.19.312, AND THE OLD PATTERN IS
 * QUOTED HERE RATHER THAN DELETED so the change reads as deliberate:
 *
 *   > preg_match( '/loadSdk\(\s*function \(\) \{\s*window\.fbq\( \x27consent\x27, \x27grant\x27 \);/', $runtime ) === 1
 *
 * It required the grant to be the FIRST statement inside the onReady callback.
 * `CYCLE167-LD-CONSENT-PIXEL-EXT` puts a `if ( !granted ) { return; }` re-check
 * ahead of it, because from 1.19.312 a US visitor starts granted on page load
 * and can therefore opt out WHILE fbevents.js is still in flight — a window
 * that could not previously be reached, since the only route to revoke() was a
 * banner click and the banner had to be open before the SDK was ever asked for.
 * ⭐ B3b is not weaker for it: the ordering claim is unchanged and now carries
 * the guard as a REQUIREMENT (B3c below), so removing the guard fails the suite.
 */
bhp_mp_assert(
	$failures,
	'B3b ⭐ the grant is issued from the SDK script\'s onload — never queued behind the revoke that stalls the drain',
	false !== strpos( $runtime, 's.onload = onReady;' )
		&& false !== strpos( $runtime, 's.onerror = onReady;' )
		&& preg_match( '/loadSdk\(\s*function \(\) \{.*?window\.fbq\( \x27consent\x27, \x27grant\x27 \);/s', $runtime ) === 1
);
bhp_mp_assert(
	$failures,
	'B3c ⭐ 1.19.312 — the onload callback RE-CHECKS `granted` before signalling, so an opt-out taken while fbevents.js is in flight is not undone by a stale closure',
	'' !== $bhp_mp_grant_body
		&& false !== strpos( $bhp_mp_grant_body, 'if ( !granted ) { return; }' )
		&& strpos( $bhp_mp_grant_body, 'if ( !granted ) { return; }' ) < strpos( $bhp_mp_grant_body, "window.fbq( 'consent', 'grant' )" )
);
bhp_mp_assert( $failures, 'B4 consent is read from the MARKETING category only, and only on a strict true', false !== strpos( $runtime, "prefs[ config.category ] === true" ) );
bhp_mp_assert( $failures, 'B5 the runtime listens to WPConsent\'s own save/update events', false !== strpos( $runtime, 'wpconsent_consent_saved' ) && false !== strpos( $runtime, 'wpconsent_consent_updated' ) );

/* On staging the SDK URL must be blank by default, so a QA session can inspect
 * fbq.queue without a single byte reaching Meta. */
$is_staging = class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_staging();
$config     = BHP_Meta_Pixel::runtime_config();

if ( $is_staging ) {
	bhp_mp_assert( $failures, 'B6 ⭐ on staging, the SDK URL is empty unless bhp_meta_pixel_staging_mode is set to live', '' === $config['sdk'] && false === BHP_Meta_Pixel::loads_sdk() );
} else {
	bhp_mp_assert( $failures, 'B6 ⭐ off staging, the SDK URL is Meta\'s own', 'https://connect.facebook.net/en_US/fbevents.js' === $config['sdk'] && true === BHP_Meta_Pixel::loads_sdk() );
}

/*
 * ⭐ THE CACHE-SAFETY PROOF, and the reason it is a byte comparison rather than
 * a code review. `CYCLE143-GIM-51` happened because a per-visitor server-side
 * gate sat in front of a page cache that varies only on Accept-Encoding: a
 * consenting visitor's HTML was served to a cookie-less visitor, and vice
 * versa. The only assertion that actually forecloses that is "the output does
 * not change when the consent cookie does".
 */
$cookie_backup = $_COOKIE;

$_COOKIE = array();
$no_cookie = BHP_Meta_Pixel::base_code_html() . BHP_Meta_Pixel::runtime_js() . wp_json_encode( BHP_Meta_Pixel::runtime_config() );

$_COOKIE['wpconsent_preferences'] = '{"essential":true,"statistics":true,"marketing":true}';
$_COOKIE['bhp_consent_state']     = '{"ad_storage":"granted"}';
$accepted = BHP_Meta_Pixel::base_code_html() . BHP_Meta_Pixel::runtime_js() . wp_json_encode( BHP_Meta_Pixel::runtime_config() );

$_COOKIE['wpconsent_preferences'] = '{"essential":true,"statistics":false,"marketing":false}';
$rejected = BHP_Meta_Pixel::base_code_html() . BHP_Meta_Pixel::runtime_js() . wp_json_encode( BHP_Meta_Pixel::runtime_config() );

$_COOKIE = $cookie_backup;

bhp_mp_assert( $failures, 'B7 ⭐ the emitted bytes are IDENTICAL with no consent cookie, with marketing accepted, and with marketing rejected', $no_cookie === $accepted && $accepted === $rejected );

/* Per-visitor payloads must refuse to render where the page cache is not told
 * to stand off. Asserted BEFORE DONOTCACHEPAGE is defined, because a constant
 * cannot be undefined afterwards. */
bhp_mp_assert(
	$failures,
	'B8 per_visitor_ok() reports exactly the DONOTCACHEPAGE flag — no second opinion, no default-open',
	BHP_Meta_Pixel::per_visitor_ok() === ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE )
);

if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
	bhp_mp_skip( $skipped, 'B8b the runtime refusal path with DONOTCACHEPAGE unset', 'DONOTCACHEPAGE was already defined true before this suite ran, and a PHP constant cannot be undefined — asserted structurally by B8c instead' );
} else {
	bhp_mp_assert( $failures, 'B8b ⭐ Purchase refuses to render when DONOTCACHEPAGE is not set', null === BHP_Meta_Pixel::purchase_event( 1, 'anything' ) );
}

/*
 * ⭐ B8c is the assertion that survives the constant already being true. It
 * proves the WIRING rather than the outcome: both per-visitor emitters must
 * open with the gate, so no future edit can add a third per-visitor payload
 * that forgets it. Read from the shipped source, not from intent.
 */
$bhp_mp_src = (string) file_get_contents( get_template_directory() . '/inc/class-bhp-meta-pixel.php' );
bhp_mp_assert(
	$failures,
	'B8c ⭐ BOTH per-visitor emitters gate on per_visitor_ok() as their first statement',
	2 === preg_match_all( '/function (?:initiate_checkout_event|purchase_event)\([^)]*\)\s*\{\s*if \( ! self::per_visitor_ok\(\) \)/', $bhp_mp_src )
);
bhp_mp_assert(
	$failures,
	'B8d the gate is defined once and called exactly twice — no third, ungated per-visitor payload exists',
	1 === substr_count( $bhp_mp_src, 'function per_visitor_ok()' )
		&& 2 === substr_count( $bhp_mp_src, 'self::per_visitor_ok()' )
);

/* ── the pixel is present where it should be, absent where it should not ── */

echo "\n--- B(ii). presence and absence ---\n";

add_filter( 'bhp_meta_pixel_id', '__return_empty_string' );
$empty_id_head = '';
ob_start();
BHP_Meta_Pixel::render_head();
$empty_id_head = ob_get_clean();
remove_filter( 'bhp_meta_pixel_id', '__return_empty_string' );

bhp_mp_assert( $failures, 'B9 with no pixel ID configured, NOTHING is printed — never a placeholder ID', '' === $empty_id_head );
$bhp_mp_bad_id = function () {
	return 'GTM-NOTAPIXEL';
};
add_filter( 'bhp_meta_pixel_id', $bhp_mp_bad_id );
$bhp_mp_refused = BHP_Meta_Pixel::pixel_id();
remove_filter( 'bhp_meta_pixel_id', $bhp_mp_bad_id );
bhp_mp_assert( $failures, 'B9b a malformed pixel ID is refused rather than printed', '' === $bhp_mp_refused );

add_filter( 'bhp_meta_pixel_enabled', '__return_false' );
ob_start();
BHP_Meta_Pixel::render_head();
$disabled_head = ob_get_clean();
remove_filter( 'bhp_meta_pixel_enabled', '__return_false' );
bhp_mp_assert( $failures, 'B10 the kill switch filter suppresses the whole thing', '' === $disabled_head );

/* Under WP-CLI with --user=1 the current user IS an administrator, so this is
 * the internal-traffic exclusion asserting itself. That is the correct answer,
 * and asserting it here is what proves the exclusion is wired at all. */
if ( class_exists( 'BHP_Analytics_Config' ) && BHP_Analytics_Config::is_excluded_internal_request() ) {
	bhp_mp_assert( $failures, 'B11 administrator/internal traffic is excluded from the pixel', false === BHP_Meta_Pixel::should_render() );
} else {
	bhp_mp_assert( $failures, 'B11 ordinary front-end traffic renders the pixel', true === BHP_Meta_Pixel::should_render() );
}

/* ══════════════════════════════════════════════════════════════════════════
 * GROUP C — real values, read from real records
 * ════════════════════════════════════════════════════════════════════════ */

echo "\n--- C. ViewContent / AddToCart read real product data ---\n";

if ( ! function_exists( 'wc_get_product' ) ) {
	bhp_mp_skip( $skipped, 'C ViewContent and AddToCart against real products', 'WooCommerce is not active in this context' );
} else {
	$currency = get_woocommerce_currency();

	/* Pick real published products from the live catalogue rather than naming
	 * IDs, so this suite does not rot the next time the catalogue changes. */
	$candidates = wc_get_products(
		array(
			'status' => 'publish',
			'limit'  => 10,
			'return' => 'objects',
		)
	);

	$simple   = null;
	$variable = null;
	foreach ( $candidates as $candidate ) {
		if ( ! $simple && $candidate->is_type( 'simple' ) && '' !== $candidate->get_price() ) {
			$simple = $candidate;
		}
		if ( ! $variable && $candidate->is_type( 'variable' ) && 1 === count( $candidate->get_children() ) ) {
			$variable = $candidate;
		}
	}

	if ( ! $simple ) {
		bhp_mp_skip( $skipped, 'C1–C4 ViewContent on a simple product', 'no published, priced simple product found in the catalogue' );
	} else {
		$event = BHP_Meta_Pixel::view_content_event( $simple->get_id() );
		bhp_mp_assert( $failures, 'C1 ViewContent is produced for a real product', is_array( $event ) && 'ViewContent' === $event['name'] );
		bhp_mp_assert( $failures, 'C2 content_ids is the real product ID, as a string', array( (string) $simple->get_id() ) === $event['params']['content_ids'] );
		bhp_mp_assert( $failures, 'C3 content_type is Meta\'s required literal "product"', 'product' === $event['params']['content_type'] );
		/* Expectation recomputed from the record — never a number typed here. */
		bhp_mp_assert( $failures, 'C4 ⭐ value equals the product\'s OWN price, read live', round( (float) $simple->get_price(), 2 ) === $event['params']['value'] );
		bhp_mp_assert( $failures, 'C5 currency is the store\'s configured currency', $currency === $event['params']['currency'] );
	}

	/* Two different products must produce two different values. A single
	 * assertion cannot distinguish "reads the price" from "returns a constant
	 * that happens to match"; this one can. */
	$priced = array();
	foreach ( $candidates as $candidate ) {
		if ( $candidate->is_type( 'simple' ) && '' !== $candidate->get_price() ) {
			$ev = BHP_Meta_Pixel::view_content_event( $candidate->get_id() );
			if ( $ev ) {
				$priced[ (string) $candidate->get_id() ] = $ev['params']['value'];
			}
		}
	}
	bhp_mp_assert( $failures, 'C6 ⭐ different products yield different values — the price is read, not hardcoded', count( $priced ) >= 2 && count( array_unique( $priced ) ) >= 2 );

	if ( ! $variable ) {
		bhp_mp_skip( $skipped, 'C7–C8 ViewContent on a single-variation variable product', 'no variable product with exactly one variation in the catalogue' );
	} else {
		$children  = $variable->get_children();
		$variation = wc_get_product( reset( $children ) );
		$event     = BHP_Meta_Pixel::view_content_event( $variable->get_id() );
		bhp_mp_assert( $failures, 'C7 a single-variation variable product reports the VARIATION id, not the container', array( (string) $variation->get_id() ) === $event['params']['content_ids'] );
		bhp_mp_assert( $failures, 'C8 …and the variation\'s own price', round( (float) $variation->get_price(), 2 ) === $event['params']['value'] );
	}

	bhp_mp_assert( $failures, 'C9 a non-product ID produces NO event rather than an empty one', null === BHP_Meta_Pixel::view_content_event( 999999999 ) );

	/* AddToCart, built from the same real records. */
	if ( $simple ) {
		$atc = BHP_Meta_Pixel::add_to_cart_event( 'abc123def456', $simple->get_id(), 3, 0 );
		bhp_mp_assert( $failures, 'C10 AddToCart is produced from the server-side add hook payload', is_array( $atc ) && 'AddToCart' === $atc['name'] );
		bhp_mp_assert( $failures, 'C11 ⭐ AddToCart value is the real price × the real quantity', round( (float) $simple->get_price() * 3, 2 ) === $atc['params']['value'] && 3 === $atc['params']['num_items'] );
		bhp_mp_assert( $failures, 'C12 AddToCart carries a stable eventID derived from the cart item key, for Meta-side dedup', 'atc_abc123def456' === $atc['eventID'] );
		bhp_mp_assert( $failures, 'C13 a variation ID wins over the parent product ID when both are supplied', $variable ? array( (string) reset( $children ) ) === BHP_Meta_Pixel::add_to_cart_event( 'k', $variable->get_id(), 1, reset( $children ) )['params']['content_ids'] : true );
		bhp_mp_assert( $failures, 'C14 an unresolvable product produces NO event rather than a zero-value one', null === BHP_Meta_Pixel::add_to_cart_event( 'k', 999999999, 1, 0 ) );
	}
}

/* ══════════════════════════════════════════════════════════════════════════
 * GROUP D — Purchase: real totals, and it fires exactly once
 * ════════════════════════════════════════════════════════════════════════ */

echo "\n--- D. Purchase ---\n";

if ( ! $is_staging || ! function_exists( 'wc_create_order' ) ) {
	bhp_mp_skip( $skipped, 'D1–D9 Purchase against a real order', 'creates and force-deletes real WooCommerce orders — staging only, and this is not staging' );
} else {
	wc_maybe_define_constant( 'DONOTCACHEPAGE', true );

	/*
	 * ⛔ NOTHING LEAVES THIS PROCESS. Creating an order and moving it to
	 * `processing` is exactly the moment WooCommerce would email the store
	 * admin. Sending mail to a real person is an owner-gated action, and a
	 * test suite is never the thing that crosses that line — so both the
	 * per-email switches AND wp_mail() itself are short-circuited for the
	 * duration. The existing purchase-validation harness relies on the test
	 * orders having no billing address; that is true but it is not a guarantee,
	 * because the admin "new order" notification has a recipient regardless.
	 */
	foreach ( array( 'new_order', 'customer_processing_order', 'customer_on_hold_order', 'customer_completed_order', 'cancelled_order', 'failed_order' ) as $bhp_mp_email ) {
		add_filter( 'woocommerce_email_enabled_' . $bhp_mp_email, '__return_false', 999 );
	}
	add_filter( 'pre_wp_mail', '__return_false', 999 );

	/*
	 * ⛔ CLEANUP IS INLINE AND ASSERTED, not left to a shutdown hook.
	 *
	 * The first run of this suite registered a `register_shutdown_function`
	 * closure exactly as the existing purchase-validation harness does — and
	 * SIX synthetic orders survived the run and had to be removed by hand.
	 * The reason was not diagnosed and is deliberately not guessed at here;
	 * what matters is that a cleanup mechanism which silently does nothing is
	 * worse than none, because it is trusted. Deletion now happens inline at
	 * the end of the group, its result is ASSERTED, and the shutdown handler
	 * is kept only as a net for an abort partway through.
	 */
	$GLOBALS['bhp_mp_created'] = array();
	$created                   = &$GLOBALS['bhp_mp_created'];
	register_shutdown_function(
		function () {
			foreach ( (array) ( $GLOBALS['bhp_mp_created'] ?? array() ) as $oid ) {
				$o = wc_get_order( $oid );
				if ( $o ) {
					$o->delete( true );
				}
			}
		}
	);

	/*
	 * ⛔ `managing_stock()` is excluded deliberately. Moving an order to
	 * `processing` triggers wc_maybe_reduce_stock_levels(), and a test suite
	 * must not alter a product record's stock on any environment — that is an
	 * owner-gated field. A stock-managed product is skipped rather than used.
	 */
	$product = null;
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 10, 'return' => 'objects' ) ) as $candidate ) {
		if ( $candidate->is_type( 'simple' ) && '' !== $candidate->get_price() && ! $candidate->managing_stock() ) {
			$product = $candidate;
			break;
		}
	}

	if ( ! $product ) {
		bhp_mp_skip( $skipped, 'D1–D13 Purchase against a real order', 'no published, priced, non-stock-managed simple product to build a test order from' );
	} else {
		$make_order = function ( $status ) use ( $product, &$created ) {
			$order = wc_create_order();
			$order->add_product( $product, 2 );
			$order->set_currency( get_woocommerce_currency() );
			$order->calculate_totals();
			$order->set_status( $status );
			$order->save();
			$created[] = $order->get_id();
			return wc_get_order( $order->get_id() );
		};

		$order = $make_order( 'processing' );

		bhp_mp_assert( $failures, 'D1 a wrong order key produces NO Purchase', null === BHP_Meta_Pixel::purchase_event( $order->get_id(), 'wc_order_not_this_one' ) );
		bhp_mp_assert( $failures, 'D2 an empty order key produces NO Purchase', null === BHP_Meta_Pixel::purchase_event( $order->get_id(), '' ) );

		$event = BHP_Meta_Pixel::purchase_event( $order->get_id(), $order->get_order_key() );

		bhp_mp_assert( $failures, 'D3 a real, paid, correctly-keyed order produces a Purchase', is_array( $event ) && 'Purchase' === $event['name'] );
		/* Recomputed from the order object, never typed. */
		bhp_mp_assert( $failures, 'D4 ⭐ value is the order\'s OWN total — the real amount paid, tax and shipping included', round( (float) $order->get_total(), 2 ) === $event['params']['value'] );
		bhp_mp_assert( $failures, 'D5 ⭐ currency comes from the order, not from a constant', $order->get_currency() === $event['params']['currency'] );
		bhp_mp_assert( $failures, 'D6 ⭐ eventID is the order NUMBER, for dedup against any future server-side source', (string) $order->get_order_number() === $event['eventID'] );
		bhp_mp_assert( $failures, 'D7 content_ids are the ordered products and num_items the real quantity', array( (string) $product->get_id() ) === $event['params']['content_ids'] && 2 === $event['params']['num_items'] );

		/* The refresh. This is the assertion that protects reported revenue. */
		$reloaded = wc_get_order( $order->get_id() );
		bhp_mp_assert( $failures, 'D8 ⭐ the dedup latch is written to order meta', 'yes' === $reloaded->get_meta( BHP_Meta_Pixel::PURCHASE_META, true ) );
		bhp_mp_assert( $failures, 'D9 ⭐ a SECOND render of the same order — a refresh — produces NOTHING', null === BHP_Meta_Pixel::purchase_event( $order->get_id(), $order->get_order_key() ) );

		/* An unpaid order is not a purchase, however the page was reached. */
		$pending = $make_order( 'pending' );
		bhp_mp_assert( $failures, 'D10 a pending/unpaid order produces NO Purchase', null === BHP_Meta_Pixel::purchase_event( $pending->get_id(), $pending->get_order_key() ) );

		/* An internal/test order must never become a reported conversion, and
		 * must be latched so it cannot be re-evaluated into one later. */
		if ( class_exists( 'BHP_Order_Provenance' ) ) {
			$internal = $make_order( 'processing' );
			$internal->update_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, BHP_Order_Provenance::ORIGIN_PRELAUNCH_TEST );
			$internal->save_meta_data();
			$internal = wc_get_order( $internal->get_id() );

			bhp_mp_assert( $failures, 'D11 an internal/test order produces NO Purchase', null === BHP_Meta_Pixel::purchase_event( $internal->get_id(), $internal->get_order_key() ) );
			bhp_mp_assert( $failures, 'D12 …and is latched, so a provenance change can never turn it into one', 'yes' === wc_get_order( $internal->get_id() )->get_meta( BHP_Meta_Pixel::PURCHASE_META, true ) );
		} else {
			bhp_mp_skip( $skipped, 'D11–D12 internal/test order exclusion', 'BHP_Order_Provenance is not loaded (bundle plugin inactive)' );
		}

		bhp_mp_assert( $failures, 'D13 a nonexistent order produces NO Purchase', null === BHP_Meta_Pixel::purchase_event( 999999999, 'wc_order_x' ) );

		/* ⭐ The suite leaves nothing behind, and proves it rather than trusting it. */
		$to_delete = $created;
		foreach ( $to_delete as $oid ) {
			$o = wc_get_order( $oid );
			if ( $o ) {
				$o->delete( true );
			}
		}
		$survivors = array();
		foreach ( $to_delete as $oid ) {
			if ( wc_get_order( $oid ) ) {
				$survivors[] = $oid;
			}
		}
		$created = array();
		bhp_mp_assert( $failures, 'D14 ⭐ every order this suite created has been force-deleted — ' . count( $to_delete ) . ' created, ' . count( $survivors ) . ' surviving', array() === $survivors );
	}
}

/* ══════════════════════════════════════════════════════════════════════════
 * GROUP E — funnel isolation and the Lead mapping
 * ════════════════════════════════════════════════════════════════════════ */

echo "\n--- E. lead funnels ---\n";

/* `.claude/rules/funnels.md`: dismissing or signing up on one funnel must never
 * touch the other's storage state. The strongest possible guarantee is that the
 * pixel runtime holds NO storage at all, so there is no key to collide on. */
bhp_mp_assert( $failures, 'E1 ⭐ the runtime uses NO localStorage', false === strpos( $runtime, 'localStorage' ) );
bhp_mp_assert( $failures, 'E2 ⭐ the runtime uses NO sessionStorage', false === strpos( $runtime, 'sessionStorage' ) );
bhp_mp_assert( $failures, 'E3 ⭐ the runtime names NEITHER funnel\'s storage prefix', false === strpos( $runtime, 'bhp_parent_popup' ) && false === strpos( $runtime, 'bhp_mariana_popup' ) );

$map = $config['map'];
bhp_mp_assert( $failures, 'E4 the parent popup\'s success event maps to Lead', isset( $map['parent_popup_success'] ) && 'Lead' === $map['parent_popup_success'][0] );
bhp_mp_assert( $failures, 'E5 the teacher popup\'s success event maps to Lead', isset( $map['teacher_popup_success'] ) && 'Lead' === $map['teacher_popup_success'][0] );
bhp_mp_assert( $failures, 'E6 the landing-page signup event maps to Lead', isset( $map['lead_signup_success'] ) && 'Lead' === $map['lead_signup_success'][0] );
bhp_mp_assert(
	$failures,
	'E7 ⭐ the two popup funnels carry DIFFERENT content_names — they are distinguishable in Events Manager',
	'parent_popup' === $map['parent_popup_success'][1]
		&& 'teacher_popup' === $map['teacher_popup_success'][1]
		&& $map['parent_popup_success'][1] !== $map['teacher_popup_success'][1]
);
bhp_mp_assert( $failures, 'E8 landing-page leads derive their content_name from the payload\'s lead_offer', '' === $map['lead_signup_success'][1] && false !== strpos( $runtime, "'landing_' + String( payload.lead_offer )" ) );
bhp_mp_assert( $failures, 'E9 the Blocks checkout\'s proven add_payment_info event maps to AddPaymentInfo', isset( $map['add_payment_info'] ) && 'AddPaymentInfo' === $map['add_payment_info'][0] );
/*
 * ⛔ E10 REWRITTEN 2026-08-19 (theme 1.19.253, `CYCLE165-LD-META-LEAD-EVENT`).
 *    THE ASSERTION IT REPLACES WAS TRUE OF THE FORMATTING, NOT OF THE BEHAVIOUR:
 *
 *      false !== strpos( $runtime, "if ( 'Lead' === mapped[ 0 ] ) { params.content_name" )
 *
 *    It matched a ONE-LINE `if`. 1.19.253 grew that branch to several lines (the
 *    Lead latch and the eventID live there now) and the assertion failed while
 *    the invariant it names was never once violated. A test that fails on
 *    reformatting trains a reader to edit the test, which is the failure mode
 *    that lets a real regression through next time.
 *
 *    ⭐ THE REPLACEMENT IS STRICTLY STRONGER, not looser. The old line proved
 *    only that a Lead branch mentioned content_name somewhere. This proves
 *    content_name is assigned EXACTLY ONCE in the whole runtime, and that the
 *    single assignment sits INSIDE the Lead-only branch — after the Lead guard
 *    and before the currency/value lines that every mapped event reaches. An
 *    AddPaymentInfo carrying a content_name now fails here; under the old line
 *    it could have passed.
 */
$e10_guard    = strpos( $runtime, "if ( 'Lead' === mapped[ 0 ] ) {" );
$e10_assign   = strpos( $runtime, 'params.content_name = contentNameFor( mapped, payload );' );
$e10_shared   = strpos( $runtime, 'if ( payload.currency ) { params.currency = payload.currency; }' );
bhp_mp_assert(
	$failures,
	'E10 content_name is assigned exactly once, inside the Lead-only branch — never on AddPaymentInfo',
	// Assignments only. `NS.lead` READS params.content_name for the QA hook,
	// and a read is not a second place the field can be set.
	1 === substr_count( $runtime, 'params.content_name =' )
		&& false !== $e10_guard && false !== $e10_assign && false !== $e10_shared
		&& $e10_assign > $e10_guard && $e10_assign < $e10_shared
		&& '' === $map['add_payment_info'][1]
);

/* GTM replaces dataLayer.push when its container executes. A wrapper alone
 * would be silently discarded; the cursor-based drain is what makes the bridge
 * survive that. */
bhp_mp_assert( $failures, 'E11 the dataLayer bridge survives GTM replacing dataLayer.push', false !== strpos( $runtime, 'window.setInterval( drain, 1000 )' ) && false !== strpos( $runtime, 'while ( seen < dl.length )' ) );

/* ══════════════════════════════════════════════════════════════════════════
 * GROUP F — the assembled head payload
 * ════════════════════════════════════════════════════════════════════════ */

echo "\n--- F. assembled head payload ---\n";

$head = BHP_Meta_Pixel::head_html();

bhp_mp_assert( $failures, 'F1 the head payload contains the base code, the config and the runtime, in that order', strpos( $head, "fbq('consent','revoke')" ) < strpos( $head, 'window.bhpMetaPixel.config' ) && strpos( $head, 'window.bhpMetaPixel.config' ) < strpos( $head, 'NS.started = true' ) );
$bhp_mp_events_marker = 'window.bhpMetaPixel.events=';
bhp_mp_assert( $failures, 'F2 the page-events array is present', false !== strpos( $head, $bhp_mp_events_marker ) );
bhp_mp_assert( $failures, 'F3 no page event is emitted on a non-commerce request', '[]' === substr( $head, strpos( $head, $bhp_mp_events_marker ) + strlen( $bhp_mp_events_marker ), 2 ) );
bhp_mp_assert( $failures, 'F4 ⭐ the head payload never carries an order key — nothing identifying reaches a cacheable page', false === stripos( $head, 'wc_order_' ) );

/* ------------------------------------------------------------------ */

echo "\n";
if ( $skipped ) {
	echo "{$skipped} assertion group(s) skipped — reported, not hidden.\n";
}
if ( $failures ) {
	echo "FAILURES: {$failures}\n";
	exit( 1 );
}
echo "ALL PASS\n";
exit( 0 );
