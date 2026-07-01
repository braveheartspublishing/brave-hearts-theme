<?php
/** Read-only WP-CLI internal-link audit. Writes internal-link-audit.csv beside this file. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run with WP-CLI: wp eval-file internal-link-audit.php\n" );
	return;
}

$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
$sources   = array();
$types     = get_post_types( array( 'public' => true ), 'names' );
$posts     = get_posts( array( 'post_type' => $types, 'post_status' => 'publish', 'posts_per_page' => -1 ) );
foreach ( $posts as $post ) {
	$sources[] = array( $post->post_type, $post->ID, get_permalink( $post ), $post->post_content . ' ' . $post->post_excerpt );
}
foreach ( wp_get_nav_menus() as $menu ) {
	$html = '';
	foreach ( wp_get_nav_menu_items( $menu ) ?: array() as $item ) {
		$html .= '<a href="' . esc_url( $item->url ) . '"></a>';
	}
	$sources[] = array( 'menu', $menu->term_id, home_url( '/' ), $html );
}

$rows = array();
foreach ( $sources as $source ) {
	preg_match_all( '/(?:href|src)\s*=\s*(["\'])(.*?)\1/is', $source[3], $matches );
	foreach ( array_unique( $matches[2] ) as $raw_url ) {
		$url  = html_entity_decode( trim( $raw_url ), ENT_QUOTES, 'UTF-8' );
		$url  = wp_http_validate_url( $url ) ? $url : ( 0 === strpos( $url, '/' ) ? home_url( $url ) : $url );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) || $host !== $home_host ) {
			continue;
		}
		$response = wp_remote_head( $url, array( 'redirection' => 0, 'timeout' => 15 ) );
		$status   = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		if ( ! $status || in_array( $status, array( 403, 405, 501 ), true ) ) {
			$response = wp_remote_get( $url, array( 'redirection' => 0, 'timeout' => 15 ) );
			$status   = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		}
		$final = $url;
		$hops  = 0;
		if ( $status >= 300 && $status < 400 ) {
			$location = wp_remote_retrieve_header( $response, 'location' );
			$final    = $location ? ( wp_http_validate_url( $location ) ? $location : home_url( $location ) ) : $url;
			$hops     = 1;
		}
		$issue = 0 === $status || $status >= 400 ? 'broken' : ( $hops ? 'redirect' : '' );
		if ( 0 === strpos( $url, 'http://' ) ) $issue = 'insecure_http';
		if ( false !== strpos( $url, 'staging' ) ) $issue = 'staging_domain';
		if ( preg_match( '/\.(pdf|docx?|xlsx?|zip)(?:\?|$)/i', $url ) && 200 !== $status ) $issue = 'broken_download';
		$rows[] = array( $source[0], $source[1], $source[2], $url, $status, $final, $hops, $issue, $hops ? 'Update source to final URL' : ( $issue ? 'Review and repair' : '' ) );
	}
}

$file = fopen( __DIR__ . '/internal-link-audit.csv', 'w' );
fputcsv( $file, array( 'source_type','source_id','source_url','linked_url','http_status','final_url','redirect_hops','issue_type','recommended_action' ) );
foreach ( $rows as $row ) fputcsv( $file, $row );
fclose( $file );
WP_CLI::success( count( $rows ) . ' internal link occurrences audited; no content changed.' );
