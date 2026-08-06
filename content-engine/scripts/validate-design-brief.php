<?php
/**
 * Phase 1D — validates a content-engine design-brief.json (plus its
 * sibling source.json/strategy.json/review.json, if present) against
 * the shape defined by templates/*.template.json and the rules in
 * config/utm-standard.yaml. Pure PHP, no WordPress dependency -- this
 * directory is schema/config only (see README.md), so validation
 * shouldn't require a WordPress bootstrap either.
 *
 * Usage:
 *   php validate-design-brief.php <blog-slug>
 *   php validate-design-brief.php mariana-trench-facts-for-kids
 *
 * Exits non-zero and prints every problem found if validation fails.
 */

$required_variant_types = array( 'problem-led', 'outcome-led', 'curiosity-led', 'resource-led' );
$required_variant_fields = array(
	'variant_type', 'headline', 'supporting_line', 'visual_direction',
	'pinterest_title', 'pinterest_description', 'alt_text', 'board_id',
	'destination_url', 'utm_content',
);
$required_utm_params = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content' );
$forbidden_claim_words = array( 'award', 'guarantee', 'limited time', 'clinically', 'proven to' );

function bhp_ce_fail( $message, array &$errors ) {
	$errors[] = $message;
}

$slug = $argv[1] ?? null;
if ( ! $slug ) {
	fwrite( STDERR, "Usage: php validate-design-brief.php <blog-slug>\n" );
	exit( 1 );
}

$base_dir = __DIR__ . '/../blogs/' . $slug;
$brief_path = $base_dir . '/design-brief.json';
if ( ! file_exists( $brief_path ) ) {
	fwrite( STDERR, "No design-brief.json found for '{$slug}' at {$brief_path}\n" );
	exit( 1 );
}

$errors = array();
$brief = json_decode( file_get_contents( $brief_path ), true );

if ( null === $brief ) {
	bhp_ce_fail( 'design-brief.json is not valid JSON', $errors );
} else {
	if ( ( $brief['blog_slug'] ?? '' ) !== $slug ) {
		bhp_ce_fail( "blog_slug field ('" . ( $brief['blog_slug'] ?? '' ) . "') does not match the folder/argument slug ('{$slug}')", $errors );
	}

	$variants = $brief['variants'] ?? array();
	if ( ! is_array( $variants ) || count( $variants ) !== 4 ) {
		bhp_ce_fail( 'Expected exactly 4 variants, found ' . ( is_array( $variants ) ? count( $variants ) : 0 ), $errors );
	}

	$seen_types = array();
	foreach ( $variants as $i => $variant ) {
		$type = $variant['variant_type'] ?? "(missing at index {$i})";
		$seen_types[] = $type;

		foreach ( $required_variant_fields as $field ) {
			if ( empty( $variant[ $field ] ) ) {
				bhp_ce_fail( "Variant '{$type}' is missing required field: {$field}", $errors );
			}
		}

		$dest = $variant['destination_url'] ?? '';
		$query = array();
		$parsed = parse_url( $dest, PHP_URL_QUERY );
		if ( $parsed ) {
			parse_str( $parsed, $query );
		}
		foreach ( $required_utm_params as $param ) {
			if ( empty( $query[ $param ] ) ) {
				bhp_ce_fail( "Variant '{$type}' destination_url is missing required UTM parameter: {$param}", $errors );
			}
		}
		if ( ! empty( $query['utm_content'] ) && ! empty( $variant['utm_content'] ) && $query['utm_content'] !== $variant['utm_content'] ) {
			bhp_ce_fail( "Variant '{$type}' utm_content field ('{$variant['utm_content']}') does not match the utm_content in destination_url ('{$query['utm_content']}')", $errors );
		}
		if ( ! empty( $variant['utm_content'] ) && strpos( $variant['utm_content'], $slug ) === false ) {
			bhp_ce_fail( "Variant '{$type}' utm_content does not contain the blog slug (naming pattern is <blog-slug>_<design-variant>_<version>)", $errors );
		}

		foreach ( array( 'headline', 'supporting_line', 'pinterest_title', 'pinterest_description' ) as $copy_field ) {
			$text = strtolower( (string) ( $variant[ $copy_field ] ?? '' ) );
			foreach ( $forbidden_claim_words as $word ) {
				if ( false !== strpos( $text, $word ) ) {
					bhp_ce_fail( "Variant '{$type}' field '{$copy_field}' contains a forbidden claim word: '{$word}'", $errors );
				}
			}
		}
	}

	$missing_types = array_diff( $required_variant_types, $seen_types );
	foreach ( $missing_types as $missing ) {
		bhp_ce_fail( "Missing required variant_type: {$missing}", $errors );
	}
	$duplicate_types = array_diff_assoc( $seen_types, array_unique( $seen_types ) );
	foreach ( $duplicate_types as $dup ) {
		bhp_ce_fail( "Duplicate variant_type found: {$dup}", $errors );
	}
}

// Optional sibling files -- validated only if present, since not every
// blog will have progressed through every pipeline stage yet.
$source_path = $base_dir . '/source.json';
if ( file_exists( $source_path ) ) {
	$source = json_decode( file_get_contents( $source_path ), true );
	if ( null === $source ) {
		bhp_ce_fail( 'source.json is not valid JSON', $errors );
	} elseif ( ( $source['blog_slug'] ?? '' ) !== $slug ) {
		bhp_ce_fail( 'source.json blog_slug does not match', $errors );
	}
}

$strategy_path = $base_dir . '/strategy.json';
if ( file_exists( $strategy_path ) ) {
	$strategy = json_decode( file_get_contents( $strategy_path ), true );
	$valid_stages = array( 'awareness', 'consideration', 'conversion' );
	if ( null === $strategy ) {
		bhp_ce_fail( 'strategy.json is not valid JSON', $errors );
	} elseif ( ! in_array( $strategy['funnel_stage'] ?? '', $valid_stages, true ) ) {
		bhp_ce_fail( "strategy.json funnel_stage must be one of: " . implode( ', ', $valid_stages ), $errors );
	}
}

if ( $errors ) {
	foreach ( $errors as $error ) {
		echo "FAIL: {$error}\n";
	}
	echo count( $errors ) . " VALIDATION ERROR(S) for '{$slug}'\n";
	exit( 1 );
}

echo "VALID: '{$slug}' design-brief.json (and any present sibling files) pass all structural/UTM/claims checks.\n";
exit( 0 );
