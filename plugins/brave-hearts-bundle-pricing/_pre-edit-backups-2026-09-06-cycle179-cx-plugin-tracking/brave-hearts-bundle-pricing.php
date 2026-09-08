<?php
/**
 * Plugin Name: Brave Hearts Bundle Pricing
 * Description: Fixed-dollar bundle discounts, shipping, and storefront offers for the six approved Adventures of Charlotte and Henry editions. Every bundle purchase adds the real, individually-mapped WooCommerce products as separate cart line items — Bookvault fulfillment routing and per-book tax are never altered.
 * Version: 1.8.83
 * Author: Brave Hearts Publishing
 * Requires Plugins: woocommerce
 * Text Domain: bhp-bundle-pricing
 *
 * Architecture notes (see also brave-hearts-theme/docs/DECISIONS.md):
 * - This plugin never creates a "bundle" WooCommerce product. It only
 *   changes storefront presentation, adds a visible, itemized cart fee for
 *   the discount, and overrides the cost of the store's existing flat-rate
 *   shipping method.
 * - It is intentionally independent of the Bookvault plugin and of the
 *   theme: it reads product/variation IDs only, never Bookvault meta.
 *   Deactivating this plugin removes all bundle behavior and restores
 *   normal per-product pricing/shipping with no other side effects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⭐⭐ 1.8.62 — THIS CONSTANT HAD DRIFTED TO `1.8.59` WHILE THE HEADER SAID
 *     `1.8.61`, AND FIXING IT IS NOT COSMETIC.
 *
 * ⛔ IT IS THE `$ver` ARGUMENT ON EVERY `wp_enqueue_script()` AND
 *    `wp_enqueue_style()` THIS PLUGIN REGISTERS — the cart drawer, the landing
 *    page, the add-on upsell, the checkout events and the list tracking. A
 *    stale value means a browser that already holds `bundle-drawer.js?ver=1.8.59`
 *    KEEPS SERVING IT and never fetches the new file.
 *
 * ⭐ THIS RELEASE CHANGES `bundle-drawer.js` — the `offer_*` interception fix.
 *    Leaving the constant at `1.8.59` would have shipped that fix to the
 *    server and to nobody's browser: the PHP would be right, the file on disk
 *    would be right, and every returning customer would still hit the alert.
 *    ⛔ That is the same "correct in code review, dead in the browser" failure
 *    class the `finishBundleAdd()` comment records, and it is why this is
 *    corrected here rather than flagged and left.
 *
 * ⛔ KEEP IT IN STEP WITH THE `Version:` HEADER ABOVE, every release.
 */
define( 'BHP_BUNDLE_PRICING_VERSION', '1.8.83' );
define( 'BHP_BUNDLE_PRICING_DIR', plugin_dir_path( __FILE__ ) );
define( 'BHP_BUNDLE_PRICING_URL', plugin_dir_url( __FILE__ ) );

/**
 * Fail safely if WooCommerce is not active: show an admin notice and load
 * nothing else, rather than fatal on a missing WooCommerce class.
 */
add_action( 'plugins_loaded', 'bhp_bundle_pricing_init' );
function bhp_bundle_pricing_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'bhp_bundle_pricing_missing_wc_notice' );
		return;
	}

	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-data.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-cart.php';
	/*
	 * ⭐ 1.8.62 — THE OFFER ENGINE (`FD-579`). Loaded AFTER `bundle-data.php`
	 *    (it reads both catalogues) and AFTER `bundle-cart.php` (its fee hook
	 *    runs at priority 21, behind the chapter-tier fees at 20), and BEFORE
	 *    `bundle-shortcode.php`, whose add-to-cart handler dispatches the
	 *    `offer_*` actions this file resolves.
	 */
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/offer-engine.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-shortcode.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-shop-series.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-landing-page.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-drawer.php';
	// N1 (2026-08-03): the checkout-page cross-sell. Loaded after the drawer
	// because it depends on the drawer's enqueued script handle and on the
	// cross-sell maths that file exports.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/checkout-upsell.php';
	// 2026-08-04: the activity-book checkbox order bump. Loaded after the
	// drawer because its script declares `bhp-cart-drawer` as a dependency
	// and its drawer surface is populated by that file's render pass.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-upsell.php';
	// 1.8.21 (2026-08-04, Andrew's Message 32): the two rules that follow
	// from the add-on existing at all.
	//   - addon-cart-guard.php: a cart of nothing but the add-on cannot
	//     check out. "People cannot just buy the $5 activity book."
	//   - addon-thankyou-email.php: a SECOND completed-order email, only
	//     for orders that contain the add-on, carrying core's own signed
	//     download link. The existing completed-order email is untouched.
	// Both are loaded after addon-upsell.php because they reuse its product
	// resolution and the SKU allowlist in bundle-data.php.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-cart-guard.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-thankyou-email.php';
	// 1.8.27 (2026-08-05, Andrew's 13:1x ruling): the activity book is FREE
	// with a complete collection. Loaded after addon-upsell.php because it
	// reuses that file's product resolution, and after bundle-cart.php
	// because it gates on bhp_bundle_evaluate_cart()'s existing
	// is_complete_collection flag rather than defining a second predicate.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-free-with-collection.php';
	// 1.8.38 (2026-08-09/10, Andrew's vocabulary-cards ruling, relayed): the
	// Vocabulary Card Activity is the SECOND free giveaway. It rides on the
	// activity book's existing product and grant as a second downloadable
	// FILE, injected at read time - no second product, no product record
	// touched. Loaded after addon-free-with-collection.php because its
	// messaging gate defers to that file's live-offer predicate, and after
	// addon-upsell.php because it resolves the same SKU allowlist.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/addon-vocab-cards.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/bundle-analytics.php';
	// 1.8.49 (2026-08-17, CYCLE162-LD-SCHOOL-PICKUP): author hand-delivery at a
	// school visit. Loaded LAST of the storefront includes because its shipping
	// filter deliberately runs at priority 25 -- after the theme's Bookvault
	// live-rate strip (10) and after bhp_bundle_override_shipping_cost() (20) --
	// and because its order-marking reads the shipping line those two produce.
	// It is self-contained: with the `bhp_school_visits` option unset it
	// registers its hooks and every one of them returns its input untouched.
	//
	// 1.8.52 (2026-08-17, CYCLE163-LD-PICKUP-NATIVE): the $0.00 shipping RATE is
	// gone; hand delivery is now WooCommerce Blocks' own LOCAL PICKUP, made
	// visible for one request by filtering the READS of
	// `woocommerce_pickup_location_settings` and `pickup_location_pickup_locations`.
	// ⛔ NEITHER OPTION IS EVER WRITTEN, on any environment -- that is an
	// Andrew-only gate. The load position still matters for the same reason, and
	// the file's priority-25 rate filter now DECORATES WooCommerce's pickup rate
	// instead of adding one of its own.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-pickup.php';
	// 1.8.50 (2026-08-17, CYCLE162-LD-PICKUP-FIELDS): the two school-visit
	// checkout fields (child's first name + newsletter opt-in). Loaded AFTER
	// school-visit-pickup.php because every gate it applies -- the session flag,
	// the visit registry, the pickup predicate and the packing-list meta keys --
	// is defined there. With the `bhp_school_visits` option unset it registers
	// its hooks and every one of them returns its input untouched.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-fields.php';
	// 1.8.57 (2026-08-18, CYCLE164-LD-PAPERBACK-DEFAULT): a visit-flagged
	// session is PAPERBACK ONLY, because Andrew's inventory cannot supply a
	// hardcover for a signed pre-order. Loaded AFTER school-visit-pickup.php
	// for the same reason school-visit-fields.php is: the session flag, the
	// visit registry and `bhp_school_visit_use_delivery_framing()` are all
	// defined there, and this file is a thin restriction on top of that ONE
	// predicate rather than a second resolver. With no visit flag on the
	// request, every hook it registers returns its input untouched.
	// 1.8.71 (2026-08-24, CYCLE166-LD-VISIT-STOCK-GATE): SHELF STOCK. Andrew
	// hand-delivers visit orders off a finite personal shelf, so a chapter
	// title closes for VISITS ONLY once its remaining count reaches 1. Loaded
	// BEFORE school-visit-paperback-only.php because that file's refused-item
	// predicate now asks this one, and AFTER school-visit-pickup.php because
	// it reads `BHP_SCHOOL_PICKUP_META_FLAG` and `bhp_school_visit_is_ymd()`.
	// ⛔ It is NOT WooCommerce inventory: no `_stock_status`, product record,
	// price or setting is touched on any environment. With the
	// `bhp_visit_shelf_stock` option unset it is behaviourally inert -- every
	// predicate returns "nothing is closed" and every surface renders exactly
	// as it did in 1.8.70. Ordinary shipped orders route to Bookvault
	// print-on-demand and consume no shelf stock, so they are untouched.
	// 1.8.72 (2026-08-24, CYCLE166-LD-VISIT-STOCK-COUNTER): the same file now
	// also owns the LIVE COUNTER shown when a title's remaining count is in
	// 2..10 on a visit-flagged session. Same arithmetic, same option, same
	// visit gate, zero new state: the number is `baseline - committed` recomputed
	// on every render and is never stored. ⛔ Still completely invisible off a
	// visit flag, and still inert with `bhp_visit_shelf_stock` unset.
	// 1.8.76 (2026-08-28, CYCLE168-LD-RETAILER-BATCH-AND-BACKORDERS): THE
	// BACKORDER ALLOWANCE. Andrew Signore, RELAYED (carrier item 363): "I think
	// we allow backorders and we will get the new books in latest by Sept 10th.
	// If not we will figure something out, Like dropping off the books a few
	// days later." Loaded BEFORE school-visit-shelf-stock.php because that
	// file's purchase gate now asks this one's predicate, and AFTER
	// school-visit-pickup.php because its `_for_request` gate reads the visit
	// flag. ⛔ It is NOT a WooCommerce backorder setting: `_backorders`,
	// `_stock`, `_stock_status` and `_manage_stock` are never read-modified or
	// written on any environment. It relaxes a gate THIS PLUGIN invented.
	// ⛔ Its default is ON, per the ruling, and it is behaviourally inert while
	// `bhp_visit_shelf_stock` is unset because nothing is exhausted to relax.
	// One WP-CLI line switches it off with no deploy; see the file header.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-backorder.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-shelf-stock.php';
	// 1.8.75 (2026-08-28, CYCLE168-LD-AMITY-STOCK-SUPPRESSION): PER-VISIT STOCK
	// PRIVACY. A visit whose parents are buying off a shelf that has not arrived
	// yet must not be shown today's count. Andrew Signore, 2026-08-28, RELAYED
	// (carrier item 359): "I just want Amity not to see the current stock since
	// we will have 75 more books coming Sept 7-11." Loaded AFTER
	// school-visit-pickup.php (it reads the visit record and the new optional
	// `hide_stock` registry field) and AFTER school-visit-shelf-stock.php (whose
	// two `_for_request` counter gates now ask this file's predicate).
	// ⛔ DISPLAY ONLY. It removes a number and never substitutes a larger one; it
	// changes no product record, no stock status, no backorder setting, no
	// purchasability and no server refusal seam, on any environment. Off a
	// hide-stock visit flag every hook it registers returns its input untouched,
	// so an ordinary shopper, an Adams session, a Dallas Harris session and a
	// Liberty session are byte-identical to 1.8.74.
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-stock-privacy.php';
	require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-paperback-only.php';

	/*
	 * ⭐⭐ 1.8.79 (`CYCLE174-LD-345`, founder items 588/589) — THE SHELF ADMIN
	 *    SCREEN. WooCommerce -> Signed Copies.
	 *
	 * ⛔ LOADED LAST OF THE VISIT MODULES, AND ONLY IN wp-admin. It READS the
	 *    shelf functions defined above (`bhp_visit_shelf_baseline()`,
	 *    `_committed()`, `_title_counter()`, the label functions), so loading it
	 *    earlier would fatal on a partial deploy. It defines no storefront
	 *    behaviour of its own and has no front-end output at all, so gating it on
	 *    `is_admin()` keeps it entirely out of a customer request.
	 *
	 * ⛔ IT WRITES EXACTLY ONE OPTION, `bhp_visit_shelf_stock`. No product
	 *    record, no WooCommerce stock field, no price, no coupon.
	 */
	if ( is_admin() ) {
		require_once BHP_BUNDLE_PRICING_DIR . 'includes/school-visit-shelf-admin.php';
		if ( class_exists( 'BHP_Visit_Shelf_Admin' ) ) {
			BHP_Visit_Shelf_Admin::init();
		}
	}

	bhp_bundle_pricing_load_dashboard_module();

	add_action( 'wp_enqueue_scripts', 'bhp_bundle_pricing_assets' );
}

function bhp_bundle_pricing_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>Brave Hearts Bundle Pricing requires WooCommerce to be active. Bundle discounts, shipping rules, and the bundle offers shortcode are not being applied.</p></div>';
}

/**
 * The KPI dashboard is an optional module: a self-contained subdirectory
 * that some deployments (e.g. staging) include and others (e.g. a
 * storefront-only production release) intentionally omit. It must never
 * be wired in with a bare, unconditional require_once -- that fatals the
 * entire plugin (storefront included, since bhp_bundle_pricing_init()
 * runs on every request once WooCommerce is active) the moment the
 * directory is missing from a given deployment package.
 *
 * The distinction this function draws is deliberate:
 * - Directory entirely absent -> normal, expected state for a release
 *   that doesn't include the dashboard. Skip silently.
 * - Directory present but missing one or more of its required files ->
 *   a broken/partial deploy, not an intentional omission. This must be
 *   surfaced loudly (admin notice + error_log), never silently ignored,
 *   so a corrupted deployment is never mistaken for "dashboard simply
 *   not included this release."
 *
 * This makes the plugin directory itself packaging-agnostic: a
 * storefront-only release is built by excluding includes/dashboard/ (and
 * its assets/tests) wholesale from the deployment package -- no manual
 * editing of this file is ever required for either release shape.
 */
function bhp_bundle_pricing_load_dashboard_module( $dir = null ) {
	if ( null === $dir ) {
		$dir = BHP_BUNDLE_PRICING_DIR . 'includes/dashboard/';
	}

	if ( ! is_dir( $dir ) ) {
		return;
	}

	$required_files = array(
		'class-bhp-cost-config.php',
		'class-bhp-offer-classifier.php',
		'class-bhp-bookvault-status.php',
		'class-bhp-refund-metrics.php',
		'class-bhp-order-provenance.php',
		'class-bhp-order-metrics.php',
		'class-bhp-kpi-cache.php',
		'class-bhp-offer-economics.php',
		'class-bhp-cpa-model.php',
		'class-bhp-dashboard-page.php',
		'dashboard-bootstrap.php',
	);

	$missing = array();
	foreach ( $required_files as $file ) {
		if ( ! file_exists( $dir . $file ) ) {
			$missing[] = $file;
		}
	}

	if ( $missing ) {
		error_log( 'Brave Hearts Bundle Pricing: dashboard module directory is present but incomplete (missing: ' . implode( ', ', $missing ) . '). Dashboard not loaded.' );
		add_action(
			'admin_notices',
			function () use ( $missing ) {
				echo '<div class="notice notice-error"><p>Brave Hearts Bundle Pricing: the dashboard module is incomplete (missing: ' . esc_html( implode( ', ', $missing ) ) . '). The dashboard is disabled until the deployment is corrected.</p></div>';
			}
		);
		return;
	}

	require_once $dir . 'dashboard-bootstrap.php';
}

function bhp_bundle_pricing_assets() {
	wp_enqueue_style(
		'bhp-bundle-pricing',
		BHP_BUNDLE_PRICING_URL . 'assets/bundle.css',
		array(),
		BHP_BUNDLE_PRICING_VERSION
	);
}
