<?php
/**
 * Brave Hearts Bundle Pricing — optional dashboard module loader test suite.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-dashboard-module-loading.php --user=1
 *
 * Verifies bhp_bundle_pricing_load_dashboard_module() (defined in the main
 * plugin file) draws the correct distinction between "module intentionally
 * absent" (silent, expected) and "module present but incomplete" (loud
 * admin notice + error_log, never silently ignored). Uses scratch fixture
 * directories under sys_get_temp_dir() -- never touches the real
 * includes/dashboard/ directory, so this suite is safe to run regardless
 * of whether the real dashboard module is deployed on this environment.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! function_exists( 'bhp_bundle_pricing_load_dashboard_module' ) ) {
	fwrite( STDERR, "bhp_bundle_pricing_load_dashboard_module() is not defined -- is the plugin active?\n" );
	exit( 1 );
}

$failures = array();

function bhp_dash_test_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
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

function bhp_dash_test_make_fixture_dir( $files_to_write ) {
	$dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/bhp-dashboard-fixture-' . uniqid() . '/';
	mkdir( $dir );
	foreach ( $files_to_write as $filename => $contents ) {
		file_put_contents( $dir . $filename, $contents );
	}
	return $dir;
}

function bhp_dash_test_rrmdir( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	foreach ( scandir( $dir ) as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}
		$path = $dir . $item;
		is_dir( $path ) ? bhp_dash_test_rrmdir( $path . '/' ) : unlink( $path );
	}
	rmdir( $dir );
}

// --- Scenario 1: directory does not exist at all -- expected, silent. ---
$missing_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/bhp-dashboard-does-not-exist-' . uniqid() . '/';
$notice_fired_before = did_action( 'admin_notices' );
bhp_bundle_pricing_load_dashboard_module( $missing_dir );
bhp_dash_test_assert(
	! defined( 'BHP_DASH_TEST_FIXTURE_LOADED' ),
	'Absent module directory: dashboard-bootstrap.php is not required',
	$failures
);

// --- Scenario 2: directory exists with all 7 required files -- loads. ---
$complete_files = array();
foreach ( $required_files as $file ) {
	$complete_files[ $file ] = "<?php // fixture stub for {$file}\n";
}
$complete_files['dashboard-bootstrap.php'] = "<?php define('BHP_DASH_TEST_FIXTURE_LOADED', true);\n";
$complete_dir = bhp_dash_test_make_fixture_dir( $complete_files );
bhp_bundle_pricing_load_dashboard_module( $complete_dir );
bhp_dash_test_assert(
	defined( 'BHP_DASH_TEST_FIXTURE_LOADED' ),
	'Complete module directory (all ' . count( $required_files ) . ' files present): dashboard-bootstrap.php is required and executes',
	$failures
);
bhp_dash_test_rrmdir( $complete_dir );

// --- Scenario 3: directory exists but is missing files -- loud, not loaded. ---
$partial_files = array();
foreach ( array_slice( $required_files, 0, 4 ) as $file ) {
	$partial_files[ $file ] = "<?php // fixture stub for {$file}\n";
}
// Deliberately omit dashboard-bootstrap.php and two class files.
$partial_dir = bhp_dash_test_make_fixture_dir( $partial_files );

function bhp_dash_test_count_admin_notice_callbacks() {
	$hook = $GLOBALS['wp_filter']['admin_notices'] ?? null;
	if ( ! $hook || ! isset( $hook->callbacks ) ) {
		return 0;
	}
	$total = 0;
	foreach ( $hook->callbacks as $priority_group ) {
		$total += count( $priority_group );
	}
	return $total;
}

$callback_count_before = bhp_dash_test_count_admin_notice_callbacks();

bhp_bundle_pricing_load_dashboard_module( $partial_dir );

$callback_count_after = bhp_dash_test_count_admin_notice_callbacks();

bhp_dash_test_assert(
	! defined( 'BHP_DASH_TEST_FIXTURE_LOADED_PARTIAL' ),
	'Partial module directory: dashboard-bootstrap.php is never required',
	$failures
);
bhp_dash_test_assert(
	$callback_count_after > $callback_count_before,
	'Partial module directory: a new admin_notices callback is registered (loud, not silent)',
	$failures
);
bhp_dash_test_rrmdir( $partial_dir );

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL DASHBOARD MODULE LOADING TESTS PASSED\n";
	exit( 0 );
}

echo count( $failures ) . " TEST(S) FAILED:\n";
foreach ( $failures as $label ) {
	echo " - {$label}\n";
}
exit( 1 );
