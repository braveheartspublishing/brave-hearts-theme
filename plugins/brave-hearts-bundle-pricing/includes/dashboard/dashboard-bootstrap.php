<?php
/**
 * Wires the dashboard module together. Loaded from the main plugin file
 * alongside the existing bundle-pricing includes -- kept in its own
 * subdirectory so the dashboard stays a clearly separable module inside
 * the existing plugin rather than a second plugin to install/activate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-bhp-cost-config.php';
require_once __DIR__ . '/class-bhp-offer-classifier.php';
require_once __DIR__ . '/class-bhp-bookvault-status.php';
require_once __DIR__ . '/class-bhp-refund-metrics.php';
require_once __DIR__ . '/class-bhp-order-provenance.php';
require_once __DIR__ . '/class-bhp-order-metrics.php';
require_once __DIR__ . '/class-bhp-kpi-cache.php';
require_once __DIR__ . '/class-bhp-offer-economics.php';
require_once __DIR__ . '/class-bhp-cpa-model.php';
require_once __DIR__ . '/class-bhp-dashboard-page.php';

// Called from bhp_bundle_pricing_init() in the main plugin file, which
// already gates this whole require on class_exists('WooCommerce').
BHP_Dashboard_Page::init();

// The unit-economics amounts live in a per-environment option, not in
// this (public) source tree -- see class-bhp-cost-config.php. If the
// option is missing or partial the dashboard still renders, but every
// cost figure reports 'unavailable' rather than a number computed
// against zero costs. Say so where an operator will actually see it,
// on the dashboard screen itself, rather than leaving it to be inferred
// from a column of blanks.
add_action( 'admin_notices', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'woocommerce_page_bhp-dashboard' !== $screen->id ) {
		return;
	}
	BHP_Cost_Config::render_unseeded_notice();
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'woocommerce_page_bhp-dashboard' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'bhp-dashboard', BHP_BUNDLE_PRICING_URL . 'assets/dashboard.css', array(), BHP_BUNDLE_PRICING_VERSION );
} );
