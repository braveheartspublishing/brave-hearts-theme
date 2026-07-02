<?php
/** Focused Rank Math redirect-map preflight. Defaults to dry-run and never assumes database schema. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run with WP-CLI: wp eval-file import-focused-redirects.php -- --dry-run\n" );
	return;
}
$apply = in_array( '--apply', $GLOBALS['argv'], true );
$path  = __DIR__ . '/bhp-redirect-map-focused.csv';
if ( ! is_readable( $path ) ) WP_CLI::error( 'Focused redirect map is missing.' );
$fh = fopen( $path, 'r' );
$header = fgetcsv( $fh );
$expected = array( 'old_url','new_url','redirect_type','content_type','reason','approval_status' );
if ( $header !== $expected ) WP_CLI::error( 'Unexpected CSV header.' );
$seen=array(); $valid=array(); $skipped=0; $failed=0;
while ( false !== ( $row=fgetcsv( $fh ) ) ) {
	if ( ! array_filter( $row, 'strlen' ) ) continue;
	if ( count( $row ) !== count( $expected ) ) { ++$failed; continue; }
	$r=array_combine( $expected, array_map( 'trim', $row ) );
	if ( 'approved' !== strtolower( $r['approval_status'] ) || '301' !== $r['redirect_type'] ) { ++$skipped; continue; }
	if ( ! $r['old_url'] || ! $r['new_url'] || $r['old_url'] === $r['new_url'] || isset( $seen[$r['old_url']] ) ) { ++$skipped; continue; }
	$seen[$r['old_url']]=true;
	$response=wp_remote_get( home_url( $r['new_url'] ), array( 'redirection'=>0, 'timeout'=>15 ) );
	$status=is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
	if ( 200 !== $status ) { ++$failed; WP_CLI::warning("Target HTTP {$status}: {$r['new_url']}"); continue; }
	$valid[]=$r; WP_CLI::log("VALID {$r['old_url']} -> {$r['new_url']}");
}
fclose($fh);
if ( $apply ) {
	WP_CLI::error( 'Apply remains locked until the installed Rank Math version/native import method and existing-rule detection are authenticated, one temporary redirect is tested and removed, and database/redirect backups are verified. Rollback: restore the redirect export/database backup and purge caches.' );
}
WP_CLI::success(sprintf('Dry run complete: %d valid, %d skipped, %d failed; no writes.',count($valid),$skipped,$failed));

