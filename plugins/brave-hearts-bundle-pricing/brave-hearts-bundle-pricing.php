<?php
/**
 * Plugin Name: Brave Hearts Bundle Pricing
 * Description: Fixed-dollar bundle discounts, shipping, and storefront offers for the six approved Adventures of Charlotte and Henry editions. Every bundle purchase adds the real, individually-mapped WooCommerce products as separate cart line items — Bookvault fulfillment routing and per-book tax are never altered.
 * Version: 1.8.46
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

define( 'BHP_BUNDLE_PRICING_VERSION', '1.8.46' );
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
