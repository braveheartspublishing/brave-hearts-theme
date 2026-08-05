<?php
/**
 * Brave Hearts Publishing — taxonomy-assignment safety mechanism, added
 * after a `wp post term set <id> <taxonomy> 'A,B,C'` command (a single
 * comma-joined string, not separate shell arguments) silently created
 * one malformed term literally named "A,B,C" instead of assigning three
 * real existing terms. WP-CLI's `<term>...` argument is variadic and
 * requires separate arguments -- a mistake that reached production
 * (post 90) before being caught. See
 * docs/weekly-cycle-1-qa-failure-audit.md and the taxonomy-repair
 * session for the full root-cause writeup.
 *
 * This class is the code-level guardrail for that exact failure mode:
 * it never creates a term, always resolves names/IDs to real existing
 * terms first, rejects any single requested value that looks like an
 * accidental comma-joined list, assigns strictly by ID, and verifies
 * the post's actual assigned term set matches what was requested before
 * declaring success.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Taxonomy_Safety {

	/**
	 * Resolves a list of requested category/tag values (names or
	 * numeric IDs, never mix intentionally but both are accepted per
	 * item) to existing term IDs. Never creates a term. Fails the whole
	 * resolution if any single requested item does not match an
	 * existing term, or if any single item contains a comma (the exact
	 * shape of the original defect -- a caller passing one
	 * comma-joined string instead of separate values).
	 *
	 * @param string $taxonomy 'category' or 'post_tag'.
	 * @param array  $requested Array of term names and/or numeric term IDs.
	 * @return array {
	 *     @type bool  $success      True only if every requested value resolved cleanly.
	 *     @type array $resolved_ids Term IDs resolved, in the same order as $requested (only meaningful if success is true).
	 *     @type array $errors       Human-readable failure reasons.
	 * }
	 */
	public static function resolve_terms( $taxonomy, array $requested ) {
		$errors = array();
		$resolved_ids = array();

		foreach ( $requested as $value ) {
			$value = is_string( $value ) ? trim( $value ) : $value;

			if ( is_string( $value ) && false !== strpos( $value, ',' ) ) {
				$errors[] = "Rejected requested term value '{$value}' — contains a comma, which is the exact shape of the known comma-joined-string defect (multiple terms must be passed as separate values, never one comma-joined string).";
				continue;
			}

			if ( is_numeric( $value ) ) {
				$term = get_term( (int) $value, $taxonomy );
				if ( ! $term || is_wp_error( $term ) ) {
					$errors[] = "Requested term ID {$value} does not exist in taxonomy '{$taxonomy}' — term creation is prohibited, this is a hard failure, not an auto-create.";
					continue;
				}
				$resolved_ids[] = (int) $term->term_id;
				continue;
			}

			$term = get_term_by( 'name', $value, $taxonomy );
			if ( ! $term ) {
				$errors[] = "Requested term name '{$value}' does not exist in taxonomy '{$taxonomy}' — term creation is prohibited, this is a hard failure, not an auto-create.";
				continue;
			}
			$resolved_ids[] = (int) $term->term_id;
		}

		return array(
			'success'      => empty( $errors ),
			'resolved_ids' => empty( $errors ) ? $resolved_ids : array(),
			'errors'       => $errors,
		);
	}

	/**
	 * Resolves $requested to existing term IDs (never creating any),
	 * assigns them to $post_id strictly by ID (never by name, avoiding
	 * WP-CLI's slug/name matching ambiguity entirely), then reads the
	 * post back and compares the actual assigned term ID set against
	 * the expected set. Fails loudly on any mismatch rather than
	 * silently trusting that the write did what was asked -- this is
	 * the check that would have caught the original defect immediately
	 * (a "success" WP-CLI response does not guarantee the assigned
	 * terms are the ones actually intended).
	 *
	 * @param int    $post_id
	 * @param string $taxonomy 'category' or 'post_tag'.
	 * @param array  $requested Array of term names and/or numeric term IDs.
	 * @return array {
	 *     @type bool  $success      True only if resolution AND the post-write verification both succeeded.
	 *     @type array $expected_ids The resolved term IDs that were requested.
	 *     @type array $actual_ids   The term IDs actually found on the post after the write.
	 *     @type array $errors       Human-readable failure reasons.
	 * }
	 */
	public static function assign_terms( $post_id, $taxonomy, array $requested ) {
		$resolution = self::resolve_terms( $taxonomy, $requested );
		if ( ! $resolution['success'] ) {
			return array(
				'success'      => false,
				'expected_ids' => array(),
				'actual_ids'   => array(),
				'errors'       => $resolution['errors'],
			);
		}

		$expected_ids = $resolution['resolved_ids'];
		sort( $expected_ids );
		$expected_ids = array_values( array_unique( $expected_ids ) );

		$result = wp_set_object_terms( $post_id, $expected_ids, $taxonomy, false );
		if ( is_wp_error( $result ) ) {
			return array(
				'success'      => false,
				'expected_ids' => $expected_ids,
				'actual_ids'   => array(),
				'errors'       => array( 'wp_set_object_terms failed: ' . $result->get_error_message() ),
			);
		}

		// Read back and verify -- never trust the write call's own
		// return value as proof of the resulting state.
		$actual_terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $actual_terms ) ) {
			return array(
				'success'      => false,
				'expected_ids' => $expected_ids,
				'actual_ids'   => array(),
				'errors'       => array( 'Post-write readback failed: ' . $actual_terms->get_error_message() ),
			);
		}
		$actual_ids = array_values( array_unique( array_map( 'intval', $actual_terms ) ) );
		sort( $actual_ids );

		$matches = ( $expected_ids === $actual_ids );

		return array(
			'success'      => $matches,
			'expected_ids' => $expected_ids,
			'actual_ids'   => $actual_ids,
			'errors'       => $matches ? array() : array( 'Post-write verification failed: actual assigned term ID set does not exactly match the expected set. Expected [' . implode( ',', $expected_ids ) . '], got [' . implode( ',', $actual_ids ) . '].' ),
		);
	}
}
