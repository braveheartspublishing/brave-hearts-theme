<?php
/**
 * Brave Hearts redirect-map validator/import guard.
 *
 * Usage:
 * wp eval-file import-redirects.php -- --dry-run
 * wp eval-file import-redirects.php -- --apply
 *
 * Apply mode intentionally refuses to write until the installed Rank Math
 * version and supported import API have been verified on the launch server.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run this file with WP-CLI.\n" );
	return;
}

$mode = in_array( '--apply', $GLOBALS['argv'], true ) ? 'apply' : 'dry-run';
$csv  = __DIR__ . '/bhp-redirect-map-final.csv';

if ( ! file_exists( $csv ) || ! is_readable( $csv ) ) {
	WP_CLI::error( 'bhp-redirect-map.csv is missing or unreadable.' );
}

$handle = fopen( $csv, 'r' );
if ( false === $handle ) {
	WP_CLI::error( 'Unable to open redirect map.' );
}

$header  = fgetcsv( $handle );
$wanted  = array( 'old_url', 'new_url', 'redirect_type', 'reason', 'review_status' );
$counts  = array( 'valid' => 0, 'skipped' => 0, 'failed' => 0 );
$seen    = array();
$records = array();

if ( $header !== $wanted ) {
	WP_CLI::error( 'Unexpected CSV header.' );
}

while ( false !== ( $row = fgetcsv( $handle ) ) ) {
	if ( empty( array_filter( $row, 'strlen' ) ) ) {
		continue;
	}
	if ( count( $row ) !== count( $wanted ) ) {
		++$counts['failed'];
		WP_CLI::warning( 'Malformed row skipped.' );
		continue;
	}
	$item = array_combine( $wanted, array_map( 'trim', $row ) );
	if ( 'approved' !== strtolower( $item['review_status'] ) ) {
		++$counts['skipped'];
		WP_CLI::log( "SKIP unapproved: {$item['old_url']}" );
		continue;
	}
	if ( '301' !== $item['redirect_type'] || empty( $item['old_url'] ) || empty( $item['new_url'] ) || $item['old_url'] === $item['new_url'] ) {
		++$counts['failed'];
		WP_CLI::warning( "Invalid/unchanged row: {$item['old_url']}" );
		continue;
	}
	if ( isset( $seen[ $item['old_url'] ] ) ) {
		++$counts['skipped'];
		WP_CLI::warning( "Duplicate source skipped: {$item['old_url']}" );
		continue;
	}
	$seen[ $item['old_url'] ] = true;
	$response                 = wp_remote_get( home_url( $item['new_url'] ), array( 'redirection' => 0, 'timeout' => 15 ) );
	$status                   = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
	if ( 200 !== $status ) {
		++$counts['failed'];
		WP_CLI::warning( "Target is not HTTP 200 ({$status}): {$item['new_url']}" );
		continue;
	}
	$records[] = $item;
	++$counts['valid'];
	WP_CLI::log( "VALID: {$item['old_url']} -> {$item['new_url']}" );
}
fclose( $handle );

if ( 'apply' === $mode ) {
	WP_CLI::error( 'Apply mode is locked: verify the installed Rank Math version, schema, and supported native import/API first. Export a database backup and existing redirects, test one redirect through Rank Math, then replace this guard with that verified method. Rollback must restore the redirect export/database backup and purge caches.' );
}

WP_CLI::success( sprintf( 'Dry run complete: %d valid, %d skipped, %d failed. No redirects written.', $counts['valid'], $counts['skipped'], $counts['failed'] ) );
