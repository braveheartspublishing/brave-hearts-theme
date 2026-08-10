<?php
/**
 * Brave Hearts - THE VOCABULARY CARD ACTIVITY, THE SECOND FREE GIVEAWAY
 * (1.8.38).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-addon-vocab-cards.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE CAN AND CANNOT PROVE - READ BEFORE TRUSTING A PASS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It CAN prove: the copy constraints, the gating predicates, the fail-closed
 * behaviour, the download-id and label resolution, the save guard, and - the
 * assertion this whole design turns on - that the ADD-ON PRODUCT RECORD IS
 * UNCHANGED while the injected file is visible to every reader.
 *
 * It CANNOT prove: that WooCommerce granted a real permission row for the
 * second file on a real order, that the signed URL serves the PDF, or that
 * the email reached an inbox. Those are live checks against a real staging
 * order and are recorded in the release evidence, never claimed from here.
 * Claiming them from this file would be a fabricated verification, which the
 * standing rules put in the same class as a fabricated review.
 *
 * ⛔ NO ORDER IS CREATED. NO CART IS BUILT OR MUTATED. NO PRODUCT,
 *    VARIATION, PRICE, COUPON, STOCK, SHIPPING, TAX OR PAYMENT RECORD IS
 *    WRITTEN, on any environment. Section 6 constructs an in-memory product
 *    object and never calls `save()`.
 *
 * Exits non-zero on any failure.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_avc_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_avc_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

echo "=== 1. The module is loaded and every public function exists ===\n";

foreach ( array(
	'bhp_bundle_vocab_filename',
	'bhp_bundle_vocab_enabled',
	'bhp_bundle_vocab_raw_downloads',
	'bhp_bundle_vocab_path_for',
	'bhp_bundle_vocab_file',
	'bhp_bundle_vocab_cards_live',
	'bhp_bundle_vocab_download_id',
	'bhp_bundle_vocab_download_name',
	'bhp_bundle_vocab_free_offer_line',
	'bhp_bundle_vocab_inject_download',
	'bhp_bundle_vocab_editing_addon_product',
	'bhp_bundle_vocab_strip_before_save',
	'bhp_bundle_vocab_is_download_row',
) as $avc_fn ) {
	bhp_avc_assert( function_exists( $avc_fn ), "1: {$avc_fn}() is defined", $failures );
}

bhp_avc_assert(
	function_exists( 'bhp_bundle_addon_download_button_label' ),
	'1: the email label resolver bhp_bundle_addon_download_button_label() is defined',
	$failures
);

echo "\n=== 2. The two customer-facing strings, and every constraint on them ===\n";

$avc_line = bhp_bundle_vocab_free_offer_line();
$avc_name = bhp_bundle_vocab_download_name();

bhp_avc_assert( 'FREE Vocabulary Card Activity' === $avc_line, "2: the bullet line reads exactly \"FREE Vocabulary Card Activity\" (got \"{$avc_line}\")", $failures );
bhp_avc_assert( 'Vocabulary Card Activity (printable PDF)' === $avc_name, "2: the download label reads exactly \"Vocabulary Card Activity (printable PDF)\" (got \"{$avc_name}\")", $failures );

/*
 * ⛔ NO DOLLAR ANCHOR ON THE BULLET, DELIBERATELY. The activity book's line
 *    carries "a $5.00 savings" because WooCommerce holds a real $5.00 record
 *    behind it. The cards have no price record, so any figure would be
 *    invented. This asserts the absence so a well-meaning future pass cannot
 *    "make it consistent" by inventing one.
 */
bhp_avc_assert( false === strpos( $avc_line, '$' ), '2: the bullet carries NO dollar figure (the cards have no price record; a figure would be invented)', $failures );
bhp_avc_assert( 1 !== preg_match( '/\bsavings?\b/i', $avc_line ), '2: and no savings claim of any kind', $failures );

/* ⛔ "FREE" is uppercase in the STRING, never via CSS text-transform. */
bhp_avc_assert( 0 === strpos( $avc_line, 'FREE ' ), '2: "FREE" is uppercase in the string itself, not achieved with text-transform', $failures );

/* ⛔ The sitewide dash purge, asserted by codepoint rather than by eye. */
foreach ( array( 'bullet line' => $avc_line, 'download label' => $avc_name ) as $avc_what => $avc_str ) {
	bhp_avc_assert(
		false === strpos( $avc_str, "\xE2\x80\x94" ) && false === strpos( $avc_str, "\xE2\x80\x93" ),
		"2: the {$avc_what} contains no em dash and no en dash",
		$failures
	);
	/* ⛔ Nothing on the never-invent list, and no age band, page/card count or outcome claim. */
	foreach ( array( '/\bages?\b/i', '/\b\d+\s*(pages?|cards?|words?)\b/i', '/\brating|review|award|proven|guarantee|classroom results?\b/i' ) as $avc_banned ) {
		bhp_avc_assert(
			1 !== preg_match( $avc_banned, $avc_str ),
			"2: the {$avc_what} makes no claim matching {$avc_banned}",
			$failures
		);
	}
}

echo "\n=== 3. The kill switch works, and it beats the cache ===\n";

/*
 * ⛔ THE ORDER OF THESE TWO ASSERTIONS IS THE POINT. `bhp_bundle_vocab_file()`
 *    caches its path lookup in a static. If the enabled test sat behind that
 *    static, an offer that had already been read once could not be withdrawn
 *    for the rest of the request. Reading it first is what makes the filter a
 *    real switch rather than a startup option.
 */
$avc_before = bhp_bundle_vocab_file();
add_filter( 'bhp_bundle_vocab_enabled', '__return_false', 99 );
bhp_avc_assert( '' === bhp_bundle_vocab_file(), '3: with the offer switched off, no file resolves (even after it had already resolved once)', $failures );
bhp_avc_assert( false === bhp_bundle_vocab_cards_live(), '3: and no surface may say the words', $failures );
bhp_avc_assert( '' === bhp_bundle_vocab_download_id(), '3: and there is no download id to match', $failures );
remove_filter( 'bhp_bundle_vocab_enabled', '__return_false', 99 );
bhp_avc_assert( $avc_before === bhp_bundle_vocab_file(), '3: switching it back on restores exactly the previous resolution', $failures );

echo "\n=== 4. Is the artefact actually on THIS environment? ===\n";

$avc_file = bhp_bundle_vocab_file();
$avc_live = bhp_bundle_vocab_cards_live();

if ( '' === $avc_file ) {
	/*
	 * ⭐ THIS IS A PASS, NOT A HOLE. An environment without the PDF must
	 *    behave byte-for-byte like one without the code: no injection, no
	 *    bullet, no promise. That is the production ordering guarantee, and
	 *    it is asserted here rather than assumed.
	 */
	bhp_avc_assert( false === $avc_live, '4: no PDF on this environment, so the offer is correctly NOT live (fails closed)', $failures );
	bhp_avc_skip( '4: sections 5 and 6 need the artefact present; this environment does not have it', $skipped );
} else {
	bhp_avc_assert( file_exists( $avc_file ), "4: the resolved PDF exists on disk", $failures );
	bhp_avc_assert( 0 === strpos( $avc_file, '/' ), '4: it is an absolute filesystem path, not a public URL (so it stays protected)', $failures );
	bhp_avc_assert(
		false === strpos( $avc_file, ABSPATH ),
		'4: and it lives OUTSIDE the WordPress root, exactly like the activity book',
		$failures
	);
	bhp_avc_assert(
		basename( $avc_file ) === bhp_bundle_vocab_filename(),
		'4: the file name is the one this module declares',
		$failures
	);
	bhp_avc_assert( md5( $avc_file ) === bhp_bundle_vocab_download_id(), '4: the download id is the path hash, deterministic across requests', $failures );

	echo "\n=== 5. The injection is visible to readers and ABSENT from the record ===\n";

	$avc_ids = function_exists( 'bhp_bundle_addon_product_ids' ) ? bhp_bundle_addon_product_ids() : array();
	if ( empty( $avc_ids ) ) {
		bhp_avc_skip( '5: BHP-ACTIVITY-BOOK-01 does not resolve on this environment', $skipped );
	} else {
		$avc_product = wc_get_product( $avc_ids[0] );

		$avc_view = $avc_product->get_downloads();          // filtered
		$avc_edit = $avc_product->get_downloads( 'edit' );  // raw, stored

		bhp_avc_assert( isset( $avc_view[ bhp_bundle_vocab_download_id() ] ), '5: the vocabulary cards ARE in the filtered (view) downloads', $failures );
		bhp_avc_assert( count( $avc_view ) === count( $avc_edit ) + 1, '5: exactly one file is added, never two', $failures );

		/*
		 * ⭐⭐ THE ASSERTION THIS WHOLE DESIGN TURNS ON. A WooCommerce product
		 *     record is an Andrew gate. The injected file must be real to
		 *     every reader and absent from the stored record.
		 */
		bhp_avc_assert(
			! isset( $avc_edit[ bhp_bundle_vocab_download_id() ] ),
			'5: ⭐ and they are NOT in the stored record - the product row is untouched (Andrew gate not crossed)',
			$failures
		);

		$avc_injected = isset( $avc_view[ bhp_bundle_vocab_download_id() ] ) ? $avc_view[ bhp_bundle_vocab_download_id() ] : null;
		if ( $avc_injected ) {
			bhp_avc_assert( bhp_bundle_vocab_download_name() === $avc_injected->get_name(), '5: the injected file carries the approved label', $failures );
			bhp_avc_assert( $avc_file === $avc_injected->get_file(), '5: and points at the resolved artefact', $failures );
		}

		/* ⛔ The activity book's own file survived, unchanged, in both views. */
		bhp_avc_assert( count( $avc_edit ) >= 1, '5: the activity book file is still on the record', $failures );
		foreach ( $avc_edit as $avc_key => $avc_dl ) {
			bhp_avc_assert( isset( $avc_view[ $avc_key ] ), "5: stored download {$avc_key} still reaches readers unchanged", $failures );
		}

		bhp_avc_assert( $avc_product->is_downloadable(), '5: the product is still downloadable', $failures );
		bhp_avc_assert( 'BHP-ACTIVITY-BOOK-01' === $avc_product->get_sku(), '5: the SKU is unchanged', $failures );

		echo "\n=== 6. The save guard: the injection can never become a record ===\n";

		/*
		 * ⛔ IN-MEMORY ONLY. A fresh product object is loaded, the injected
		 *    file is pushed onto it as wp-admin would post it, the guard runs,
		 *    and the object is DISCARDED. `save()` is never called.
		 */
		$avc_probe     = wc_get_product( $avc_ids[0] );
		$avc_with_ours = $avc_edit;
		$avc_with_ours[ bhp_bundle_vocab_download_id() ] = $avc_injected;
		$avc_probe->set_downloads( $avc_with_ours );
		bhp_bundle_vocab_strip_before_save( $avc_probe );
		$avc_after = $avc_probe->get_downloads( 'edit' );

		bhp_avc_assert( ! isset( $avc_after[ bhp_bundle_vocab_download_id() ] ), '6: an admin save cannot persist the injected file', $failures );
		bhp_avc_assert( count( $avc_after ) === count( $avc_edit ), '6: and it removes nothing else', $failures );
		unset( $avc_probe, $avc_with_ours, $avc_after );
	}
}

echo "\n=== 7. Row matching and the per-file email label ===\n";

$avc_id = bhp_bundle_vocab_download_id();

if ( '' === $avc_id ) {
	bhp_avc_skip( '7: no download id on this environment (no artefact), so row matching cannot be exercised', $skipped );
} else {
	$avc_vocab_row = array( 'download_id' => $avc_id, 'download_name' => bhp_bundle_vocab_download_name(), 'download_url' => 'https://example.test/?download_file=1' );
	$avc_book_row  = array( 'download_id' => 'ffffffffffffffffffffffffffffffff', 'download_name' => 'The Adventure Activity Book (PDF)', 'download_url' => 'https://example.test/?download_file=2' );

	bhp_avc_assert( true === bhp_bundle_vocab_is_download_row( $avc_vocab_row ), '7: the vocabulary-cards row is recognised', $failures );
	bhp_avc_assert( false === bhp_bundle_vocab_is_download_row( $avc_book_row ), '7: the activity-book row is not', $failures );
	bhp_avc_assert( false === bhp_bundle_vocab_is_download_row( array() ), '7: an empty row is not', $failures );

	$avc_copy = bhp_bundle_addon_thankyou_copy();
	bhp_avc_assert(
		$avc_copy['email']['download_button_vocab'] === bhp_bundle_addon_download_button_label( $avc_vocab_row, $avc_copy ),
		'7: the vocabulary-cards row gets the vocabulary-cards button label',
		$failures
	);
	bhp_avc_assert(
		$avc_copy['email']['download_button'] === bhp_bundle_addon_download_button_label( $avc_book_row, $avc_copy ),
		'7: the activity-book row keeps its own, unchanged label',
		$failures
	);
	bhp_avc_assert(
		$avc_copy['email']['download_button'] === bhp_bundle_addon_download_button_label( array( 'download_id' => 'abc' ), $avc_copy ),
		'7: an unrecognised future file falls back to the activity-book label, never to a blank button',
		$failures
	);
	bhp_avc_assert(
		false !== strpos( $avc_copy['email']['download_button_vocab'], 'Vocabulary Card Activity (printable PDF)' ),
		'7: and the button carries the exact label the brief specifies',
		$failures
	);
}

echo "\n=== 8. THE LOCKED COPY DID NOT MOVE ===\n";

/*
 * ⛔ THE BRIEF IS EXPLICIT: "No other email copy changes; locked copy stays
 *    locked." These are the approved 1.8.22 strings, asserted byte-for-byte.
 *    A future pass that reopens any of them fails here rather than in an
 *    inbox.
 */
$avc_copy = bhp_bundle_addon_thankyou_copy();
$avc_locked = array(
	'subject'         => 'Your activity book is here',
	'heading'         => 'Your activity book is here',
	'download_button' => 'Download the Activity Book (PDF)',
	'closing'         => 'If the download does not open, reply to this email. It comes to a real person and we will sort it out.',
	'signoff_tagline' => 'Big Places. Brave Hearts.',
);
foreach ( $avc_locked as $avc_key => $avc_expected ) {
	bhp_avc_assert( $avc_expected === $avc_copy['email'][ $avc_key ], "8: email.{$avc_key} is byte-identical to the approved 1.8.22 string", $failures );
}
bhp_avc_assert( 1 === count( $avc_copy['email']['paragraphs'] ), '8: the single pre-download paragraph is still single', $failures );
bhp_avc_assert( 3 === count( $avc_copy['email']['paragraphs_after'] ), '8: the three post-download paragraphs are still three', $failures );
bhp_avc_assert( 'APPROVED' === $avc_copy['status'], '8: the copy status is still APPROVED', $failures );
bhp_avc_assert( array() === $avc_copy['open_confirms'], '8: with no open Andrew confirms', $failures );
bhp_avc_assert(
	false === strpos( wp_json_encode( $avc_copy ), 'answer key' ),
	'8: the deliberately-omitted answer-key clause has not crept back in',
	$failures
);

echo "\n=== 9. Both landing-page ends route through the helper, and in order ===\n";

/*
 * Source-read assertions, in the style the sibling suites already use: the
 * rendered page is checked in a browser, but this catches a future edit that
 * hardcodes the sentence or drops the gate.
 */
$avc_lp = BHP_BUNDLE_PRICING_DIR . 'includes/bundle-landing-page.php';
if ( ! is_readable( $avc_lp ) ) {
	bhp_avc_skip( '9: bundle-landing-page.php is not readable in this deployment', $skipped );
} else {
	$avc_src = file_get_contents( $avc_lp );
	bhp_avc_assert( 2 === substr_count( $avc_src, 'bhp_bundle_vocab_free_offer_line()' ), '9: the vocabulary bullet is emitted at exactly two places (cold open, lower CTA)', $failures );
	bhp_avc_assert( 2 === substr_count( $avc_src, 'bhp_bundle_vocab_cards_live()' ), '9: each is gated on the live predicate', $failures );
	bhp_avc_assert(
		1 !== preg_match( '/FREE Vocabulary Card Activity/', $avc_src ),
		'9: and the sentence is never hardcoded into the page',
		$failures
	);

	/* ⛔ ORDER: Shipping, then Activity Book, then Vocabulary Cards, at both ends. */
	foreach ( array( 'coldopen', 'final' ) as $avc_which ) {
		$avc_ship  = strpos( $avc_src, "\$bhp_{$avc_which}_free[] = 'FREE Shipping" );
		$avc_addon = strpos( $avc_src, "\$bhp_{$avc_which}_free[] = bhp_bundle_addon_free_offer_line()" );
		$avc_vocab = strpos( $avc_src, "\$bhp_{$avc_which}_free[] = bhp_bundle_vocab_free_offer_line()" );
		bhp_avc_assert(
			false !== $avc_ship && false !== $avc_addon && false !== $avc_vocab && $avc_ship < $avc_addon && $avc_addon < $avc_vocab,
			"9: {$avc_which} bullet order is Shipping then Activity Book then Vocabulary Cards",
			$failures
		);
	}
}

echo "\n=== 10. Nothing next to this feature moved ===\n";

/* The guards this feature sits beside. A regression in any of them is this
 * suite's business, because this feature is the newest thing near them. */
bhp_avc_assert( function_exists( 'bhp_bundle_cart_is_addon_only' ), '10: the never-sold-alone cart guard is still loaded', $failures );
bhp_avc_assert( function_exists( 'bhp_bundle_addon_free_with_collection' ), '10: the activity-book live-offer predicate is still loaded', $failures );
bhp_avc_assert( function_exists( 'bhp_bundle_addon_thankyou_should_send' ), '10: the email send decision is still loaded', $failures );
bhp_avc_assert( array( 'BHP-ACTIVITY-BOOK-01' ) === bhp_bundle_addon_skus(), '10: the SKU allowlist is unchanged (no second product was created)', $failures );
bhp_avc_assert( 'FREE Activity Book' === bhp_bundle_addon_free_offer_label(), '10: the activity book\'s own short label is unchanged', $failures );

echo "\n";
if ( $skipped ) {
	echo count( $skipped ) . " SKIPPED (stated, not hidden):\n";
	foreach ( $skipped as $s ) {
		echo "  - {$s}\n";
	}
	echo "\n";
}
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED (10 sections)\n";
	exit( 0 );
}

echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
