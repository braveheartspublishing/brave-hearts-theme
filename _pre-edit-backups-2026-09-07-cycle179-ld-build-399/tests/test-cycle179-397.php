<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * test-cycle179-397.php — theme 1.19.397 / bundle plugin 1.8.86, 2026-09-07.
 * `CYCLE179-LD-BUILD-397` · lead-developer, under chief-of-staff.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-397.php \
 *     --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ---------------------------------------------------------------------------
 * WHAT IT COVERS
 * ---------------------------------------------------------------------------
 *   §1  the staging mail guard — EVERY registered email id is guarded, and the
 *       assertion asks WooCommerce and the filter registry, never the list
 *   §2  cache-busting — one `ver` rule for every enqueued theme/plugin asset
 *   §3  the reward-order origin — stamping, exclusion, and V-9 keyed to it
 *   §4  rails that must not have moved
 *   §5  cleanup, asserted rather than assumed
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE TALLY IS READ FROM `$GLOBALS`, NOT FROM A TOP-LEVEL VARIABLE.
 *     `wp eval-file` executes this file inside a FUNCTION, so a top-level `$x`
 *     is LOCAL while a helper's `global $x` binds to `$GLOBALS['x']`. The 394
 *     suite shipped with that bug and printed `PASS 0 FAIL 0` underneath real
 *     failures. Same defence as the 396 suite.
 *
 * ⚠️ THE RUNNER COUNTS `^PASS` LINES AND THIS FILE'S OWN SUMMARY LINE STARTS
 *    WITH `PASS`, so the TSV will read one higher than the summary. Quote the
 *    summary. Recorded by `commerce-cx` at 396; it is a runner artefact that
 *    affects every suite equally and therefore leaves deltas correct.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES: throwaway `shop_order` records on STAGING, each tagged
 *   `_bhp_cycle397_probe`, all force-deleted in §5.
 *
 * ⛔⛔ IT CREATES NO COUPON. The 397 brief forbids it, and the reward rails are
 *     exercised with a COUPON LINE ITEM instead — a `WC_Order_Item_Coupon`
 *     carrying a code, added to a fixture order, with no coupon post anywhere.
 *     ⭐ That is not a weaker fixture; it is the MORE FAITHFUL one. These
 *     coupons are single-use and are deleted after redemption, so "a coupon
 *     code on an order with no coupon post behind it" is the state a reward
 *     order spends almost all of its life in.
 *
 * ⛔ WHAT IS THEREFORE NOT PROVED HERE, said plainly rather than implied:
 *    RAIL 3 of `BHP_Order_Provenance::is_incentive_fulfillment()` — the coupon
 *    META rail — is NOT exercised live, because exercising it requires
 *    creating a coupon. §3.12 asserts its code is present and reachable; that
 *    is a source assertion and is labelled as one.
 *
 * ⛔ It creates, edits or deletes NO coupon, product, variation, price, stock,
 *    shipping, tax, payment or checkout record, and reads no option for
 *    modification.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['c397_pass']   = 0;
$GLOBALS['c397_fail']   = 0;
$GLOBALS['c397_orders'] = array();

function c397_ok( $label, $cond, $detail = '' ) {
	global $c397_pass, $c397_fail;
	if ( $cond ) {
		$c397_pass++;
		echo "PASS  {$label}\n";
		return true;
	}
	$c397_fail++;
	echo "FAIL  {$label}" . ( '' !== $detail ? "  [{$detail}]" : '' ) . "\n";
	return false;
}

function c397_section( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * A throwaway order, tracked for §5's cleanup.
 *
 * @param string[] $coupon_codes Codes to attach as COUPON LINE ITEMS. No
 *                               coupon post is created or looked up.
 * @return WC_Order
 */
function c397_make_order( $coupon_codes = array() ) {
	global $c397_orders;

	$order = wc_create_order();
	$order->update_meta_data( '_bhp_cycle397_probe', 'yes' );

	foreach ( (array) $coupon_codes as $code ) {
		$item = new WC_Order_Item_Coupon();
		$item->set_code( $code );
		$item->set_discount( 12.99 );
		$order->add_item( $item );
	}

	$order->save();
	$c397_orders[] = $order->get_id();

	// Re-read, so every assertion runs against a hydrated object rather than
	// the one still holding the writer's in-memory state.
	return wc_get_order( $order->get_id() );
}

function c397_theme_dir() {
	return untrailingslashit( get_template_directory() );
}

function c397_theme_uri() {
	return untrailingslashit( get_template_directory_uri() );
}

// ═══════════════════════════════════════════════════════════════════════════
c397_section( '§1 · staging mail guard — every registered email id is guarded' );
// ═══════════════════════════════════════════════════════════════════════════

/*
 * ⭐⭐⭐ THE SHAPE OF §1 IS THE POINT, AND IT IS WHY THIS SUITE EXISTS.
 *
 * ⛔ The obvious test is "assert the ids in `bhp_staging_mail_guard_email_ids()`
 *    are registered". THAT TEST PASSES ON A GUARD THAT MISSES HALF THE STORE.
 *    It did pass, for months, while `customer_failed_order` was missing and six
 *    real emails left staging and bounced back to Andrew — because the list was
 *    both the thing under test and the definition of correctness.
 *
 * ⭐ SO NEITHER SIDE OF §1.2 COMES FROM THAT LIST. The ids come from
 *    `WC()->mailer()->get_emails()`, which is WooCommerce's own answer to
 *    "what emails exist"; the verdict comes from `has_filter()`, which is
 *    WordPress's own answer to "what is actually hooked". The list at the top
 *    of the guard file is not consulted by this assertion at all, and the
 *    assertion fails the day a plugin adds an email nobody guarded.
 */

$mailer_ready = function_exists( 'WC' ) && is_callable( array( WC(), 'mailer' ) );
c397_ok( '1.1 WooCommerce mailer is reachable from the CLI', $mailer_ready );

$registered_ids = array();
if ( $mailer_ready ) {
	foreach ( (array) WC()->mailer()->get_emails() as $email ) {
		if ( is_object( $email ) && isset( $email->id ) && '' !== (string) $email->id ) {
			$registered_ids[] = (string) $email->id;
		}
	}
}
$registered_ids = array_values( array_unique( $registered_ids ) );

c397_ok(
	'1.1b the store registers a plausible number of emails (>= 18)',
	count( $registered_ids ) >= 18,
	'found ' . count( $registered_ids )
);

$unguarded = array();
foreach ( $registered_ids as $id ) {
	if ( false === has_filter( 'woocommerce_email_enabled_' . $id, 'bhp_staging_mail_guard_disable' ) ) {
		$unguarded[] = $id;
	}
}
c397_ok(
	'1.2 ⭐ EVERY registered WooCommerce email id is guarded (ids from get_emails(), verdict from has_filter())',
	empty( $unguarded ),
	'unguarded: ' . implode( ', ', $unguarded )
);

c397_ok(
	'1.3 the guard reports itself as running on staging',
	function_exists( 'bhp_staging_mail_guard_is_staging' ) && bhp_staging_mail_guard_is_staging()
);

$still_enabled = array();
if ( $mailer_ready ) {
	foreach ( (array) WC()->mailer()->get_emails() as $email ) {
		if ( is_object( $email ) && method_exists( $email, 'is_enabled' ) && $email->is_enabled() ) {
			$still_enabled[] = isset( $email->id ) ? $email->id : get_class( $email );
		}
	}
}
c397_ok(
	'1.4 ⭐⭐ NO registered email answers is_enabled() === true on staging',
	empty( $still_enabled ),
	'still enabled: ' . implode( ', ', $still_enabled )
);

/*
 * ⭐ 1.5 is the regression test for the 2026-09-06 incident and for this
 *    build's own finding. Each id is named individually so a failure says
 *    WHICH one came back, not merely that the count changed.
 */
$must_cover = array(
	// The 2026-09-06 incident.
	'customer_failed_order',
	// The five Stripe gateway emails found by `commerce-cx` at 396.
	'failed_renewal_authentication',
	'failed_preorder_sca_authentication',
	'failed_authentication_requested',
	'wc_stripe_failed_refund_admin',
	'wc_stripe_failed_refund_customer',
	// The three core emails this build stopped exempting.
	'customer_reset_password',
	'customer_new_account',
	'admin_payment_gateway_enabled',
);
foreach ( $must_cover as $id ) {
	c397_ok(
		"1.5 named id is guarded: {$id}",
		false !== has_filter( 'woocommerce_email_enabled_' . $id, 'bhp_staging_mail_guard_disable' )
	);
}

c397_ok(
	'1.6 the dynamic sweep is registered on woocommerce_email_classes at PHP_INT_MAX',
	PHP_INT_MAX === has_filter( 'woocommerce_email_classes', 'bhp_staging_mail_guard_sweep' )
);

c397_ok(
	'1.7 guarded_ids() answers from the filter registry, not from any list (an invented id is not guarded)',
	function_exists( 'bhp_staging_mail_guard_guarded_ids' )
		&& array() === bhp_staging_mail_guard_guarded_ids( array( 'bhp_no_such_email_397' ) )
);

c397_ok(
	'1.8 guarded_ids() with no argument covers every registered id',
	function_exists( 'bhp_staging_mail_guard_guarded_ids' )
		&& count( bhp_staging_mail_guard_guarded_ids() ) === count( $registered_ids ),
	'guarded ' . ( function_exists( 'bhp_staging_mail_guard_guarded_ids' ) ? count( bhp_staging_mail_guard_guarded_ids() ) : -1 )
		. ' of ' . count( $registered_ids )
);

c397_ok(
	'1.9 registering the same id twice does not stack a second callback',
	function_exists( 'bhp_staging_mail_guard_register_id' )
		&& false === bhp_staging_mail_guard_register_id( 'new_order' )
);

/*
 * ⭐ SOURCE ASSERTION, LABELLED AS ONE. The fail-towards-production clause
 *    cannot be exercised from staging — there is no way to make this process
 *    believe it is production without lying to it, and a test that lies to the
 *    host detector is testing the lie. So its PRESENCE is asserted instead,
 *    which is a weaker claim, stated as a weaker claim.
 */
$guard_src = (string) @file_get_contents( c397_theme_dir() . '/inc/staging-mail-guard.php' );
c397_ok(
	'1.10 SOURCE: the detector still fails towards production when the config class is absent',
	false !== strpos( $guard_src, "if ( ! class_exists( 'BHP_Analytics_Config' ) ) {" )
		&& false !== strpos( $guard_src, 'return false;' )
);
c397_ok(
	'1.11 SOURCE: the guard writes no option and mutates no store setting',
	false === strpos( $guard_src, 'update_option' )
		&& false === strpos( $guard_src, 'update_post_meta' )
);

// ═══════════════════════════════════════════════════════════════════════════
c397_section( '§2 · cache-busting — one ver rule for every enqueued asset' );
// ═══════════════════════════════════════════════════════════════════════════

c397_ok( '2.1 the module loaded', function_exists( 'bhp_asset_version_filter_src' ) );

c397_ok(
	'2.2 style_loader_src is filtered at priority 20',
	20 === has_filter( 'style_loader_src', 'bhp_asset_version_filter_src' )
);
c397_ok(
	'2.3 script_loader_src is filtered at priority 20',
	20 === has_filter( 'script_loader_src', 'bhp_asset_version_filter_src' )
);

/*
 * ⛔⛔ 2.4 IS THE ORDERING ASSERTION AND IT IS THE ONE WORTH READING.
 *
 * `bhp_minified_style_src()` sits on the SAME hook at priority 10 and swaps
 * `foo.css` for `foo.min.css`, carrying the query string across untouched. If
 * the stamper ever ran first, it would stamp the SOURCE file's mtime onto a URL
 * that then becomes the ARTEFACT's — so rebuilding `style.min.css` without
 * touching `style.css` would ship under an unchanged `ver`. That is precisely
 * the 396 defect, one hop upstream, and it would look fixed.
 */
$min_prio   = has_filter( 'style_loader_src', 'bhp_minified_style_src' );
$stamp_prio = has_filter( 'style_loader_src', 'bhp_asset_version_filter_src' );
c397_ok(
	'2.4 ⭐⭐ the stamper runs AFTER the .min.css swap on the same hook',
	false !== $min_prio && false !== $stamp_prio && $stamp_prio > $min_prio,
	"min={$min_prio} stamp={$stamp_prio}"
);

$style_mtime     = filemtime( c397_theme_dir() . '/style.css' );
$style_min_mtime = file_exists( c397_theme_dir() . '/style.min.css' ) ? filemtime( c397_theme_dir() . '/style.min.css' ) : 0;
$theme_version   = (string) wp_get_theme()->get( 'Version' );

c397_ok( '2.5 the theme reports 1.19.397', '1.19.397' === $theme_version, $theme_version );

$raw_style_url = c397_theme_uri() . '/style.css?ver=' . $theme_version;
$stamped       = bhp_asset_version_filter_src( $raw_style_url );
c397_ok(
	'2.6 a theme stylesheet URL gains <version>.<mtime>',
	false !== strpos( $stamped, 'ver=' . $theme_version . '.' . $style_mtime ),
	$stamped
);

/*
 * ⭐ 2.7 runs the WHOLE hook chain rather than this file's callback alone, so
 *    it proves the two filters compose the way 2.4 says they are ordered to.
 *    The URL must end up pointing at the ARTEFACT and carrying the ARTEFACT's
 *    mtime — not the source's.
 */
$chained = apply_filters( 'style_loader_src', $raw_style_url, 'bhp-style', home_url() );
c397_ok(
	'2.7 ⭐⭐ the full style_loader_src chain yields style.min.css stamped with the ARTEFACT mtime',
	false !== strpos( $chained, '/style.min.css?' )
		&& $style_min_mtime > 0
		&& false !== strpos( $chained, '.' . $style_min_mtime ),
	$chained
);
c397_ok(
	'2.7b …and that is a DIFFERENT stamp from the source file\'s (the 396 defect, restated as an assertion)',
	$style_min_mtime !== $style_mtime || false === strpos( $chained, '.' . $style_mtime . '&' ),
	"src_mtime={$style_mtime} min_mtime={$style_min_mtime}"
);

$js_rel = '/assets/js/nav.js';
if ( file_exists( c397_theme_dir() . $js_rel ) ) {
	$js_stamped = bhp_asset_version_filter_src( c397_theme_uri() . $js_rel . '?ver=' . $theme_version );
	c397_ok(
		'2.8 a theme script URL gains <version>.<mtime>',
		false !== strpos( $js_stamped, 'ver=' . $theme_version . '.' . filemtime( c397_theme_dir() . $js_rel ) ),
		$js_stamped
	);
} else {
	c397_ok( '2.8 a theme script URL gains <version>.<mtime>', false, 'assets/js/nav.js missing' );
}

c397_ok(
	'2.9 a URL with no ver at all still gets one',
	false !== strpos( bhp_asset_version_filter_src( c397_theme_uri() . '/style.css' ), 'ver=' )
);

c397_ok(
	'2.10 the stamp is idempotent — filtering twice changes nothing',
	bhp_asset_version_filter_src( $stamped ) === $stamped,
	$stamped
);

c397_ok(
	'2.11 an external URL is returned byte-identical',
	'https://cdn.example.com/x.css?ver=1' === bhp_asset_version_filter_src( 'https://cdn.example.com/x.css?ver=1' )
);
c397_ok(
	'2.12 a WordPress core URL is returned byte-identical',
	includes_url( 'js/jquery/jquery.min.js' ) . '?ver=3.7.1' === bhp_asset_version_filter_src( includes_url( 'js/jquery/jquery.min.js' ) . '?ver=3.7.1' )
);
c397_ok(
	'2.13 a theme URL pointing at no file is returned byte-identical (a broken enqueue stays debuggable)',
	c397_theme_uri() . '/assets/css/no-such-397.css?ver=9' === bhp_asset_version_filter_src( c397_theme_uri() . '/assets/css/no-such-397.css?ver=9' )
);
c397_ok(
	'2.14 a path containing .. is refused rather than resolved',
	c397_theme_uri() . '/../wp-config.php?ver=1' === bhp_asset_version_filter_src( c397_theme_uri() . '/../wp-config.php?ver=1' )
);

/*
 * ⭐ 2.15 IS THE ASSERTION THE BRIEF ASKED FOR — "consistently for EVERY
 *    enqueued stylesheet and script". It sweeps every stylesheet and script
 *    the theme actually ships and confirms each one, put through the filter,
 *    comes back carrying its own file's mtime. A single missed call site
 *    cannot hide, because the sweep does not read call sites at all.
 */
$assets    = array_merge(
	(array) glob( c397_theme_dir() . '/assets/css/*.css' ),
	(array) glob( c397_theme_dir() . '/assets/js/*.js' )
);
$unstamped = array();
foreach ( $assets as $abs ) {
	$rel = str_replace( c397_theme_dir(), '', str_replace( '\\', '/', $abs ) );
	$out = bhp_asset_version_filter_src( c397_theme_uri() . $rel . '?ver=' . $theme_version );
	if ( false === strpos( $out, '.' . filemtime( $abs ) ) ) {
		$unstamped[] = $rel;
	}
}
c397_ok(
	'2.15 ⭐⭐ EVERY shipped theme stylesheet and script stamps with its own mtime (' . count( $assets ) . ' files)',
	empty( $unstamped ) && count( $assets ) >= 20,
	'unstamped: ' . implode( ', ', array_slice( $unstamped, 0, 8 ) ) . ' of ' . count( $assets )
);

if ( defined( 'BHP_BUNDLE_PRICING_URL' ) && defined( 'BHP_BUNDLE_PRICING_DIR' ) ) {
	$plugin_css = BHP_BUNDLE_PRICING_DIR . 'assets/bundle-drawer.css';
	c397_ok(
		'2.16 the bundle plugin\'s own assets are stamped too',
		file_exists( $plugin_css )
			&& false !== strpos(
				bhp_asset_version_filter_src( BHP_BUNDLE_PRICING_URL . 'assets/bundle-drawer.css?ver=' . BHP_BUNDLE_PRICING_VERSION ),
				'.' . filemtime( $plugin_css )
			)
	);
} else {
	c397_ok( '2.16 the bundle plugin\'s own assets are stamped too', false, 'plugin constants undefined' );
}

// ═══════════════════════════════════════════════════════════════════════════
c397_section( '§3 · ORIGIN_INCENTIVE_FULFILLMENT — stamping and exclusion' );
// ═══════════════════════════════════════════════════════════════════════════

$have_prov = class_exists( 'BHP_Order_Provenance' );
c397_ok( '3.1 BHP_Order_Provenance is loaded', $have_prov );

if ( $have_prov ) {
	c397_ok(
		'3.2 the constant exists and reads as a fulfillment, not a test',
		defined( 'BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT' )
			&& 'incentive_fulfillment_order' === BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT,
		defined( 'BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT' ) ? BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT : '(undefined)'
	);

	/*
	 * ⭐⭐ 3.3 IS THE ASSERTION THAT PROTECTS ANDREW'S NUMBERS FROM A LIE, and
	 *    it is worth more than the exclusion itself. The exclusion could have
	 *    been had for free by reusing `staging_origin_order`. This asserts that
	 *    the origin's own string does NOT contain "test" — so the shortcut
	 *    cannot be taken later by someone who reads only the arithmetic.
	 */
	c397_ok(
		'3.3 ⭐⭐ the origin string does not call a real family\'s order a test',
		false === strpos( BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT, 'test' )
	);

	$labels = BHP_Order_Provenance::origin_labels();
	c397_ok(
		'3.4 it carries a human label',
		isset( $labels[ BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT ] )
			&& false !== stripos( $labels[ BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT ], 'reward' )
	);

	// --- prefix matching -------------------------------------------------
	c397_ok( '3.5 READER- (as the brief writes it) matches', BHP_Order_Provenance::code_is_incentive( 'READER-ABC123' ) );
	c397_ok( '3.5b reader- (as WooCommerce stores it) matches', BHP_Order_Provenance::code_is_incentive( 'reader-abc123' ) );
	c397_ok( '3.5c bhp-thanks- (V-9\'s shipped prefix) matches', BHP_Order_Provenance::code_is_incentive( 'bhp-thanks-x' ) );
	c397_ok( '3.5d an ordinary coupon does NOT match', ! BHP_Order_Provenance::code_is_incentive( 'parent10' ) );
	c397_ok( '3.5e a code merely containing the word does NOT match', ! BHP_Order_Provenance::code_is_incentive( 'welcome-reader-10' ) );
	c397_ok( '3.5f an empty code does NOT match', ! BHP_Order_Provenance::code_is_incentive( '' ) );

	// --- a reward order --------------------------------------------------
	$reward = c397_make_order( array( 'reader-c397fixture' ) );

	c397_ok( '3.6 the fixture order is recognised as a reward fulfillment', BHP_Order_Provenance::is_incentive_fulfillment( $reward ) );

	$cls = BHP_Order_Provenance::classify( $reward );
	c397_ok(
		'3.7 classify() returns ORIGIN_INCENTIVE_FULFILLMENT',
		BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT === $cls['origin'],
		$cls['origin']
	);
	c397_ok(
		'3.8 …with reporting status audit_only',
		BHP_Order_Provenance::STATUS_AUDIT_ONLY === $cls['reporting_status'],
		$cls['reporting_status']
	);
	c397_ok(
		'3.9 ⭐⭐ is_executive_eligible() is FALSE — the single gate on revenue, AOV, orders, units and the purchase event',
		! BHP_Order_Provenance::is_executive_eligible( $reward )
	);
	c397_ok(
		'3.10 the reason says it is not a test order',
		false !== stripos( $cls['reason'], 'NOT because it is a test' ),
		$cls['reason']
	);

	// --- the stamp -------------------------------------------------------
	c397_ok( '3.11 stamp_order() writes the durable stamp', true === BHP_Order_Provenance::stamp_order( $reward ) );
	$reward = wc_get_order( $reward->get_id() );
	c397_ok(
		'3.11b the stamp is on the order',
		'yes' === $reward->get_meta( BHP_Order_Provenance::INCENTIVE_ORDER_META_KEY )
	);
	c397_ok( '3.11c stamping again is a no-op', false === BHP_Order_Provenance::stamp_order( $reward ) );

	/*
	 * ⭐⭐ 3.12 IS THE DURABILITY ASSERTION AND IT IS THE ONE THAT MATTERS IN A
	 *    YEAR. Reward coupons are single-use and are DELETED after redemption.
	 *    Here the coupon LINE ITEM is removed from the order entirely — a
	 *    harsher state than a deleted coupon post, since even the code is gone —
	 *    and the classification must survive on the stamp alone.
	 */
	foreach ( $reward->get_items( 'coupon' ) as $item_id => $item ) {
		$reward->remove_item( $item_id );
	}
	$reward->save();
	$reward = wc_get_order( $reward->get_id() );

	c397_ok(
		'3.12 ⭐⭐ with every coupon trace removed, the stamp alone still classifies it',
		BHP_Order_Provenance::is_incentive_fulfillment( $reward )
			&& BHP_Order_Provenance::ORIGIN_INCENTIVE_FULFILLMENT === BHP_Order_Provenance::classify( $reward )['origin']
	);

	// --- the false-positive guard ---------------------------------------
	$ordinary = c397_make_order( array( 'parent10' ) );
	c397_ok( '3.13 an ordinary coupon order is NOT a reward', ! BHP_Order_Provenance::is_incentive_fulfillment( $ordinary ) );
	$ocls = BHP_Order_Provenance::classify( $ordinary );
	c397_ok(
		'3.13b …and still classifies as a live customer order',
		BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER === $ocls['origin'],
		$ocls['origin']
	);
	c397_ok( '3.13c …and remains executive-eligible', BHP_Order_Provenance::is_executive_eligible( $ordinary ) );

	$bare = c397_make_order();
	c397_ok( '3.14 an order with no coupon at all is NOT a reward', ! BHP_Order_Provenance::is_incentive_fulfillment( $bare ) );
	c397_ok( '3.14b stamp_order() declines to stamp it', false === BHP_Order_Provenance::stamp_order( $bare ) );

	/*
	 * ⭐ 3.15 — the manual override must still outrank the reward stamp. This is
	 *    the property that cost a second meta key rather than reusing
	 *    OVERRIDE_META_KEY, so it is asserted rather than trusted.
	 */
	$override = c397_make_order( array( 'reader-c397override' ) );
	$override->update_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER );
	$override->save();
	$override = wc_get_order( $override->get_id() );
	c397_ok(
		'3.15 ⭐ a human override still beats the reward classification',
		BHP_Order_Provenance::ORIGIN_LIVE_CUSTOMER === BHP_Order_Provenance::classify( $override )['origin'],
		BHP_Order_Provenance::classify( $override )['origin']
	);

	// --- source assertions, labelled -------------------------------------
	$prov_src = (string) @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/dashboard/class-bhp-order-provenance.php' );
	c397_ok(
		'3.16 SOURCE: the coupon-META rail exists (NOT exercised live — this suite creates no coupon)',
		false !== strpos( $prov_src, 'INCENTIVE_COUPON_META_KEY' )
			&& false !== strpos( $prov_src, "new WC_Coupon( \$code )" )
	);
	$analytics_src = (string) @file_get_contents( BHP_BUNDLE_PRICING_DIR . 'includes/bundle-analytics.php' );
	c397_ok(
		'3.17 SOURCE: the purchase and bundle_type_purchased events both gate on is_executive_eligible()',
		2 <= substr_count( $analytics_src, 'BHP_Order_Provenance::is_executive_eligible' )
	);
} else {
	foreach ( range( 2, 17 ) as $n ) {
		c397_ok( "3.{$n} skipped — BHP_Order_Provenance not loaded", false, 'dashboard module absent' );
	}
}

// --- V-9 keyed to the same origin ---------------------------------------
c397_section( '§3b · V-9 post-purchase suppression keys on the SAME origin' );

c397_ok( '3b.1 the plugin exposes the shared predicate', function_exists( 'bhp_is_incentive_fulfillment_order' ) );
c397_ok( '3b.2 V-9 is loaded', function_exists( 'bhp_postpurchase_is_suppressed' ) );

/*
 * ⭐⭐⭐ 3b.3 IS THE DECISIVE ASSERTION OF ITEM 3, AND THE CHOICE OF PREFIX IS
 *     THE WHOLE TEST.
 *
 * `reader-` is NOT one of V-9's own prefixes — V-9 ships keyed on
 * `bhp-thanks-` and would answer FALSE for this order on its own rails. So if
 * suppression comes back TRUE, it can only have come through rail 0, which is
 * the plugin's origin. ⛔ A test written with a `bhp-thanks-` code would pass
 * identically whether or not rail 0 existed, and would therefore prove nothing
 * about the single-source-of-truth property it appears to be testing.
 */
if ( function_exists( 'bhp_postpurchase_is_suppressed' ) ) {
	$v9_order = c397_make_order( array( 'reader-c397v9' ) );
	c397_ok(
		'3b.3 ⭐⭐⭐ a READER- order is suppressed, which only rail 0 can explain',
		bhp_postpurchase_is_suppressed( $v9_order )
	);

	$v9_order = wc_get_order( $v9_order->get_id() );
	c397_ok(
		'3b.4 …and V-9 stamped its own durable mark while doing so',
		'yes' === $v9_order->get_meta( BHP_POSTPURCHASE_SUPPRESS_ORDER_META )
	);

	$v9_legacy = c397_make_order( array( 'bhp-thanks-c397' ) );
	c397_ok(
		'3b.5 V-9\'s own shipped prefix still works independently (rails 1-3 not regressed)',
		bhp_postpurchase_is_suppressed( $v9_legacy )
	);

	$v9_plain = c397_make_order( array( 'parent10' ) );
	c397_ok(
		'3b.6 an ordinary order is NOT suppressed (the false-positive guard)',
		! bhp_postpurchase_is_suppressed( $v9_plain )
	);

	/*
	 * ⛔⛔ THE FIRST VERSION OF 3b.7 FAILED AGAINST CORRECT CODE, AND THE
	 *     CORRECTION IS KEPT VISIBLE RATHER THAN QUIETLY APPLIED.
	 *
	 * It read `bhp_review_ask_decline_reason()` on a freshly created order and
	 * expected `suppressed_coupon`. It got `status_not_completed` — because
	 * `wc_create_order()` produces a PENDING order and that function's status
	 * gate sits ABOVE the coupon gate (inc/review-ask-email.php lines 4591 and
	 * 4625, read after the failure rather than assumed).
	 *
	 * ⭐ THE GATE ORDER IS RIGHT AND THE ASSERTION WAS WRONG. A pending order
	 *    is not eligible for a review ask for a reason that has nothing to do
	 *    with coupons, so answering `status_not_completed` is the more precise
	 *    answer, and reordering the gates to make this test pass would have
	 *    made the code worse.
	 *
	 * ⭐⭐ COMPLETING THE ORDER IS NOT MERELY THE FIX — IT IS A STRONGER TEST.
	 *     `woocommerce_order_status_completed` is one of the three hooks the
	 *     PLUGIN's stamper is registered on, so 3b.7b below now proves the
	 *     stamping path end to end on a real status transition rather than
	 *     only through a direct `stamp_order()` call.
	 *
	 * ⚠️ COMPLETING AN ORDER FIRES WooCommerce'S COMPLETED-ORDER EMAILS. That
	 *    is deliberate exposure to §1's guard, not an oversight: every
	 *    registered email id is guarded (§1.2) and none reads enabled (§1.4),
	 *    which is exactly the claim worth exercising with a real trigger. The
	 *    FluentSMTP row count is read in the build report to confirm nothing
	 *    left the box.
	 */
	$v9_order->set_status( 'completed' );
	$v9_order->save();
	$v9_order = wc_get_order( $v9_order->get_id() );

	if ( function_exists( 'bhp_review_ask_decline_reason' ) ) {
		$reason = (string) bhp_review_ask_decline_reason( $v9_order );
		c397_ok(
			'3b.7 a COMPLETED READER- order is declined by the review-ask sequence with suppressed_coupon',
			'suppressed_coupon' === $reason,
			$reason
		);
	} else {
		c397_ok( '3b.7 a COMPLETED READER- order is declined with suppressed_coupon', false, 'bhp_review_ask_decline_reason absent' );
	}

	if ( class_exists( 'BHP_Order_Provenance' ) ) {
		c397_ok(
			'3b.7b ⭐⭐ the completion hook wrote the PLUGIN\'s reward stamp — the stamper proved end to end',
			'yes' === $v9_order->get_meta( BHP_Order_Provenance::INCENTIVE_ORDER_META_KEY )
		);
		c397_ok(
			'3b.7c …and the completed reward order is still excluded from executive KPIs',
			! BHP_Order_Provenance::is_executive_eligible( $v9_order )
		);
	}

	if ( function_exists( 'bhp_review_ask_should_send' ) ) {
		c397_ok(
			'3b.7d …and the sequence will not send it',
			! bhp_review_ask_should_send( $v9_order )
		);
	}

	$v9_src = (string) @file_get_contents( c397_theme_dir() . '/inc/postpurchase-suppression.php' );
	c397_ok(
		'3b.8 SOURCE: rail 0 is guarded by function_exists, so the theme does not hard-depend on the plugin',
		false !== strpos( $v9_src, "function_exists( 'bhp_is_incentive_fulfillment_order' )" )
	);
}

// ═══════════════════════════════════════════════════════════════════════════
c397_section( '§4 · rails that must not have moved' );
// ═══════════════════════════════════════════════════════════════════════════

c397_ok(
	'4.1 the bundle plugin reports 1.8.86',
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && '1.8.86' === BHP_BUNDLE_PRICING_VERSION,
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '(undefined)'
);

if ( class_exists( 'BHP_Order_Provenance' ) ) {
	/*
	 * ⭐ 4.2 — every ORIGIN_* constant must map to a reporting status. The map
	 *    is private, so it is probed the only honest way: through the override
	 *    path, which is the public door that reads it. An origin added later
	 *    without a map entry falls to STATUS_UNKNOWN, which silently drops the
	 *    order out of every executive number AND out of the failure numbers.
	 */
	$probe   = c397_make_order();
	$missing = array();
	$ref     = new ReflectionClass( 'BHP_Order_Provenance' );
	foreach ( $ref->getConstants() as $name => $value ) {
		if ( 0 !== strpos( $name, 'ORIGIN_' ) ) {
			continue;
		}
		$probe->update_meta_data( BHP_Order_Provenance::OVERRIDE_META_KEY, $value );
		$probe->save();
		$got = BHP_Order_Provenance::classify( wc_get_order( $probe->get_id() ) );
		if ( BHP_Order_Provenance::ORIGIN_UNKNOWN !== $value && BHP_Order_Provenance::STATUS_UNKNOWN === $got['reporting_status'] ) {
			$missing[] = $name;
		}
		if ( $value !== $got['origin'] ) {
			$missing[] = $name . '(not accepted as an override)';
		}
	}
	c397_ok(
		'4.2 ⭐ every ORIGIN_* constant is accepted as an override AND has a reporting status',
		empty( $missing ),
		implode( ', ', $missing )
	);
}

c397_ok(
	'4.3 the theme still serves a minified root stylesheet',
	file_exists( c397_theme_dir() . '/style.min.css' ) && function_exists( 'bhp_minified_style_src' )
);

// ═══════════════════════════════════════════════════════════════════════════
c397_section( '§5 · cleanup, asserted rather than assumed' );
// ═══════════════════════════════════════════════════════════════════════════

$created = $GLOBALS['c397_orders'];
foreach ( $created as $oid ) {
	$o = wc_get_order( $oid );
	if ( $o ) {
		$o->delete( true );
	}
}
$left = array();
foreach ( $created as $oid ) {
	if ( wc_get_order( $oid ) ) {
		$left[] = $oid;
	}
}
c397_ok(
	'5.1 every fixture order created by this suite is gone (' . count( $created ) . ' created)',
	empty( $left ),
	'still present: ' . implode( ', ', $left )
);

/*
 * ⛔ 5.2 IS NOT A FORMALITY. The 397 brief says "Do not create any coupon; use
 *    a fixture." This asserts the suite obeyed it, by counting coupons rather
 *    than by the author's say-so.
 */
$coupons = get_posts(
	array(
		'post_type'      => 'shop_coupon',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$reward_coupons = array();
foreach ( $coupons as $cid ) {
	$code = strtolower( (string) get_post_field( 'post_title', $cid ) );
	if ( false !== strpos( $code, 'c397' ) ) {
		$reward_coupons[] = $code;
	}
}
c397_ok(
	'5.2 ⛔ this suite created NO coupon (store holds ' . count( $coupons ) . ', none of them ours)',
	empty( $reward_coupons ),
	implode( ', ', $reward_coupons )
);

echo "\nPASS {$GLOBALS['c397_pass']}  FAIL {$GLOBALS['c397_fail']}\n";
