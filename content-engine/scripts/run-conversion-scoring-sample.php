<?php
/**
 * Phase 1D — one-time sample run of BHP_Conversion_Scoring against the
 * 6 required real page types. Read-only: fetches each page's live
 * rendered HTML via wp_remote_get() and scores it. Writes nothing to
 * the database and edits no content -- report only.
 *
 * Run via WP-CLI (needs WordPress + the theme's classes loaded):
 *   wp eval-file content-engine/scripts/run-conversion-scoring-sample.php --user=1 --url=staging2.braveheartspublishing.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

if ( ! class_exists( 'BHP_Conversion_Scoring' ) ) {
	fwrite( STDERR, "BHP_Conversion_Scoring is not loaded.\n" );
	exit( 1 );
}

$pages = array(
	array( 'label' => 'Awareness blog', 'page_type' => 'blog', 'url' => home_url( '/blog/mount-everest-facts-for-kids/' ), 'post_slug' => 'mount-everest-facts-for-kids' ),
	array( 'label' => 'Consideration blog', 'page_type' => 'blog', 'url' => home_url( '/blog/what-are-bridge-books-guide-for-parents-and-teachers/' ), 'post_slug' => 'what-are-bridge-books-guide-for-parents-and-teachers' ),
	array( 'label' => 'Resource hub page', 'page_type' => 'resource', 'url' => home_url( '/teachers/' ), 'post_slug' => null ),
	array( 'label' => 'Individual book page', 'page_type' => 'product', 'url' => home_url( '/product/adventures-of-charlotte-and-henry-mount-everest-paperback/' ), 'post_slug' => null ),
	array( 'label' => 'Collection page', 'page_type' => 'collection', 'url' => home_url( '/complete-collection/' ), 'post_slug' => null ),
	array( 'label' => 'Landing page', 'page_type' => 'landing', 'url' => home_url( '/reluctant-reader-adventure-kit/' ), 'post_slug' => null ),
);

$results = array();

foreach ( $pages as $page ) {
	$response = wp_remote_get( $page['url'], array( 'timeout' => 15, 'sslverify' => false ) );
	if ( is_wp_error( $response ) ) {
		$results[] = array( 'label' => $page['label'], 'url' => $page['url'], 'error' => $response->get_error_message() );
		continue;
	}
	$html = wp_remote_retrieve_body( $response );
	$status = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $status || ! $html ) {
		$results[] = array( 'label' => $page['label'], 'url' => $page['url'], 'error' => "HTTP {$status} or empty body" );
		continue;
	}

	$classification = null;
	if ( $page['post_slug'] && class_exists( 'BHP_Content_Classification' ) ) {
		$post = get_page_by_path( $page['post_slug'], OBJECT, 'post' );
		if ( $post ) {
			$classification = BHP_Content_Classification::get_classification( $post->ID );
		}
	}

	$score = BHP_Conversion_Scoring::score_page( $page['page_type'], $html, $classification );
	$results[] = array(
		'label'      => $page['label'],
		'url'        => $page['url'],
		'page_type'  => $page['page_type'],
		'has_classification' => null !== $classification,
		'score'      => $score,
	);
}

echo wp_json_encode( array( 'generated_at' => gmdate( 'c' ), 'pages' => $results ), JSON_PRETTY_PRINT );
echo "\n";
