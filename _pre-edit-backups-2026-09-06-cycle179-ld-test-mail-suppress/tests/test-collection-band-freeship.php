<?php
/**
 * Brave Hearts Theme — THE COLLECTION BAND SAYS "SHIPS FREE" (1.19.174).
 * CYCLE144-LD-42.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ READ FIRST — 1.19.218 (2026-08-11, CYCLE154-LD-01). THE SENTENCE THIS
 *     SUITE WAS NAMED FOR IS GONE, ON PURPOSE. THE SUITE IS NOT.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-11, verbatim (⛔ RELAYED through the Chief of Staff;
 * NOT witnessed first-hand by the agent that wrote this): "On the home page-
 * in the best value - the complete collection box - the FREE Activity Book,
 * Free Vocab, and FREE Shipping needs to be in bullet points."
 *
 * The band now renders the shared `bhp_book_free_bullets_markup()` list — the
 * same three bullets already shipping on all four funnel offer surfaces and on
 * /complete-collection/ — instead of the combined sentence sections 3 and 4
 * were written around. ⭐ THE FILE NAME AND THE HEADER BELOW ARE PRESERVED
 * VERBATIM RATHER THAN REWRITTEN, so a reader arriving from a search for the
 * sentence finds the history rather than a file that pretends it never existed.
 *
 * ⛔ NOT ONE ASSERTION WAS DELETED. Every superseded needle is quoted verbatim
 *    at the point where it was replaced. Exactly ONE check was deliberately
 *    NOT carried over — "the sentence quotes NO figure" — because the activity
 *    book's approved "$5.00 savings" would now fail it on a correct build; the
 *    property that mattered (no SHIPPING figure) is still enforced, twice.
 *    The reason is recorded at the point of removal, in §3.
 *
 * ⭐ WHAT SECTION 2 ASSERTS IS UNCHANGED AND IS STILL THE POINT OF THE FILE:
 *    the claim cannot become a lie, because the predicate reads the PLUGIN's
 *    live numbers rather than a copy string.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-collection-band-freeship.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, current-turn order relayed through the Chief of
 * Staff (⚠ RELAYED, not witnessed first-hand by this agent): the free-shipping
 * fact was missing from the Complete Collection band, which is the one place
 * on BOTH the homepage and /books/ where the collection is sold.
 *
 * Copy shipped, in full:
 *
 *   "Save $4.98 in hardcover, $3.98 in paperback. The complete collection
 *    ships free."
 *
 * ⭐ AMENDED 1.19.179 (2026-08-05, CYCLE144-LD-70). THE COPY ABOVE IS
 *    SUPERSEDED AND IS PRESERVED VERBATIM RATHER THAN CORRECTED IN PLACE, so
 *    a reader can see that the sentence was changed by an owner instruction
 *    and not by drift. Andrew Signore, 2026-08-05, current-turn order
 *    (⛔ RELAYED through the Chief of Staff and witnessed by the main
 *    session — NOT witnessed first-hand by the agent that wrote this),
 *    verbatim: "In the best value box, make 'The complete collection ships
 *    FREE' and put FREE in BOLD."
 *
 *    Copy shipping from 1.19.179:
 *
 *      "Save $4.98 in hardcover, $3.98 in paperback. The complete collection
 *       ships FREE."   (FREE inside a real <strong>)
 *
 *    ⛔ NOT ONE ASSERTION IS DELETED. The §3 and §4 needles below are
 *       RETARGETED at the new string and the originals are quoted in place.
 *       Two assertions are ADDED, both stronger than what they replace: the
 *       emphasis must be MARKUP (`<strong>`), and the capitals must be TYPED
 *       rather than produced by `text-transform`, because a CSS transform
 *       leaves the DOM text lowercase for copy-paste, translation and
 *       assistive technology. The gate, the "no figure" rule and the "no
 *       shipping dollar amount" rule are untouched and still enforced.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ THE ONE THING THIS SUITE REALLY TESTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Not that the sentence is present — that a grep proves. That the sentence
 * CANNOT BECOME A LIE. Plugin 1.8.23 took the collection tier to $0.00 four
 * days ago; the theme's own 1.19.170 exists because five surfaces were still
 * printing that $0.00 as a dollar figure. A band that hardcodes "ships free"
 * is one tier-table edit away from being a promise checkout contradicts.
 *
 * So §2 asserts the predicate against the PLUGIN's live numbers, and §3
 * asserts that the predicate actually GATES the sentence in the template.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED PLAINLY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * It does not render either page. §3 is a source assertion about the
 * template, and §4 renders the PARTIAL in isolation via output buffering,
 * which is closer but still not a page load. Whether the sentence appears on
 * the real homepage and the real /books/, at both viewports, must be observed
 * in a browser; this file does not claim to have done that.
 *
 * Exits non-zero on any failure. Reads pure functions and source files, and
 * renders one template partial into a buffer: no cart, no session, no order,
 * no product and no option is written.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_cbf_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_cbf_read( $rel ) {
	$path = get_stylesheet_directory() . '/' . $rel;
	return is_readable( $path ) ? (string) file_get_contents( $path ) : null;
}

/** Strip PHP comments so a comment that QUOTES copy cannot pass or fail a scan. */
function bhp_cbf_strip_comments( $src ) {
	$out = '';
	foreach ( token_get_all( $src ) as $token ) {
		if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$out .= is_array( $token ) ? $token[1] : $token;
	}
	return $out;
}

$cbf_src  = bhp_cbf_read( 'template-parts/components/complete-collection-feature.php' );
bhp_cbf_assert( null !== $cbf_src, '0. the shared band partial is readable', $failures );
$cbf_code = bhp_cbf_strip_comments( (string) $cbf_src );

// =====================================================================
// 1. THE THEME VERSION AND THE HELPER EXIST
// =====================================================================

$cbf_theme = wp_get_theme();
/*
 * ⚠️ CORRECTED 2026-08-05, within the hour, AFTER THIS ASSERTION FAILED ON
 *    STAGING FOR THE WRONG REASON.
 *
 * It was written as `'1.19.174' === ...`, an exact pin. Forty minutes later a
 * concurrent session's integration build took the theme to 1.19.175 WITHOUT
 * touching one line of this feature, and this suite reported a failure on a
 * build where the feature was entirely correct.
 *
 * ⛔ A FEATURE SUITE MUST NOT PIN A SHARED VERSION NUMBER. The theme version
 *    belongs to every session at once; an exact pin makes this file fail on
 *    the next release by whoever ships it, and a suite that cries wolf on a
 *    correct build is worse than no version check. `>=` asserts the only
 *    thing this suite actually needs — that the build carrying the fix is the
 *    one being tested — and stays true through every later release.
 */
bhp_cbf_assert(
	version_compare( (string) $cbf_theme->get( 'Version' ), '1.19.174', '>=' ),
	sprintf( '1. the active theme is at or past 1.19.174, the release carrying this fix (got %s)', $cbf_theme->get( 'Version' ) ),
	$failures
);
bhp_cbf_assert(
	function_exists( 'bhp_book_collection_ships_free' ),
	'1. bhp_book_collection_ships_free() is loaded',
	$failures
);
bhp_cbf_assert(
	function_exists( 'bhp_book_shipping_is_free' )
	&& function_exists( 'bhp_book_ship_note_single' )
	&& function_exists( 'bhp_book_ship_note_collection' )
	&& function_exists( 'bhp_book_landing_ship_note' ),
	'1. REGRESSION: all four 1.19.170 shipping helpers are still loaded and were not disturbed',
	$failures
);

// =====================================================================
// 2. ⭐ THE PREDICATE READS THE PLUGIN, NOT AN OPINION
// =====================================================================

bhp_cbf_assert(
	true === bhp_book_collection_ships_free(),
	'2. ⭐ the collection DOES currently ship free on this environment, so the sentence is entitled to render',
	$failures
);

/*
 * Proved against the plugin's own numbers rather than against the helper's
 * word for it. If these two disagree the helper is wrong, and this is the
 * assertion that says so.
 */
if ( function_exists( 'bhp_bundle_rules' ) ) {
	bhp_cbf_assert(
		0.0 === (float) bhp_bundle_rules( 'paperback' )[3]['shipping']
		&& 0.0 === (float) bhp_bundle_rules( 'hardcover' )[3]['shipping'],
		'2. corroborated: bhp_bundle_rules() puts BOTH three-title tiers at $0.00',
		$failures
	);
} else {
	bhp_cbf_assert( false, '2. bhp_bundle_rules() is unavailable — the plugin is not active', $failures );
}

if ( function_exists( 'bhp_bundle_shipping_amount' ) ) {
	$cbf_mixed = bhp_bundle_shipping_amount(
		array(
			'is_complete_collection' => true,
			'is_mixed_format'        => true,
			'total_quantity'         => 3,
			'has_paperback'          => true,
			'has_hardcover'          => true,
			'paperback_tier'         => 2,
			'hardcover_tier'         => 0,
			'distinct_adventures'    => 3,
			'has_unrelated'          => false,
		)
	);
	bhp_cbf_assert(
		0.0 === (float) $cbf_mixed,
		sprintf( '2. corroborated: a MIXED-format complete collection also ships free ($%.2f) — the route the tier table does not cover', (float) $cbf_mixed ),
		$failures
	);
}

/*
 * ⭐ THE NEGATIVE CASE, and the honest limit on how far it can be exercised.
 *
 * ⛔ `bhp_bundle_rules()` IS A CONSTANT TABLE WITH NO FILTER. It cannot be
 *    perturbed at runtime, so there is no way to make
 *    bhp_book_collection_ships_free() return false inside this process
 *    without editing plugin source. **That is not a gap in the test, it is
 *    the shape of the mechanism:** the only thing that can make the rule
 *    non-free is a code change, and the sentence then stops rendering in the
 *    same deploy that makes it stop being true. A runtime toggle would be a
 *    weaker design, not a more testable one.
 *
 * What CAN be exercised, and is: the threshold the predicate delegates to,
 * and the fact that the predicate is data-driven rather than a `return true`.
 */
bhp_cbf_assert(
	false === bhp_book_shipping_is_free( 4.99 ) && false === bhp_book_shipping_is_free( 0.01 ),
	'2. the shared threshold rejects a real rate: $4.99 and $0.01 are both NOT free',
	$failures
);
bhp_cbf_assert(
	true === bhp_book_shipping_is_free( 0.00 ),
	'2. REGRESSION: and $0.00 still is (the 1.19.170 predicate, delegating to the plugin)',
	$failures
);

$cbf_helper_src = bhp_cbf_read( 'inc/book-formats.php' );
bhp_cbf_assert(
	null !== $cbf_helper_src
	&& 1 === preg_match(
		'/function bhp_book_collection_ships_free.{0,1400}bhp_bundle_rules\(\s*\$format\s*\).{0,300}bhp_book_shipping_is_free\(\s*\$rules\[3\]\[\x27shipping\x27\]\s*\)/s',
		bhp_cbf_strip_comments( (string) $cbf_helper_src )
	),
	'2. ⭐ the predicate READS bhp_bundle_rules()[3][\'shipping\'] and passes it through the shared threshold — it is not a `return true`',
	$failures
);
bhp_cbf_assert(
	null !== $cbf_helper_src
	&& false !== strpos( bhp_cbf_strip_comments( (string) $cbf_helper_src ), 'bhp_bundle_shipping_amount(' ),
	'2. and it also probes the MIXED-format route through bhp_bundle_shipping_amount(), which the tier table does not cover',
	$failures
);
bhp_cbf_assert(
	null !== $cbf_helper_src
	&& 1 === preg_match( '/function bhp_book_collection_ships_free.{0,400}if \(!function_exists\(\x27bhp_bundle_rules\x27\)\) \{\s*return false;/s', bhp_cbf_strip_comments( (string) $cbf_helper_src ) ),
	'2. FALSE is the answer when the plugin is absent — a band that says nothing is the pre-existing behaviour; a band that says "free" without the plugin is a lie',
	$failures
);

// =====================================================================
// 3. THE TEMPLATE GATES THE SENTENCE ON THE PREDICATE
// =====================================================================

/*
 * ⭐ RETARGETED 1.19.179 (2026-08-05, CYCLE144-LD-70), NOT WEAKENED AND NOT
 *    DELETED. The needle changed because the COPY changed on Andrew's
 *    current-turn order, and the originals are quoted here so a future reader
 *    can see the guard moved rather than lapsed:
 *
 *      false !== strpos( $cbf_code, 'The complete collection ships free.' )
 *      preg_match( '/bhp_book_collection_ships_free\(\).{0,200}The complete collection ships free\./s', $cbf_code )
 *      preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,600}The complete collection ships free\./s', $cbf_code )
 *      $cbf_sentence = 'The complete collection ships free.';
 *
 *    The template now prints the sentence through printf() with the bolded
 *    word as the argument, so the CODE needle is the format string.
 */
/*
 * ═════════════════════════════════════════════════════════════════════
 * ⭐⭐ RETARGETED 1.19.218 (2026-08-11, CYCLE154-LD-01). THE SENTENCE IS
 *     GONE FROM THIS BAND ON PURPOSE, AND THESE ASSERTIONS NOW GUARD ITS
 *     REPLACEMENT. NOT ONE ASSERTION IS DELETED — every superseded needle
 *     is quoted verbatim below so a future reader sees the guard MOVE
 *     rather than lapse.
 * ═════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-11, verbatim (⛔ RELAYED through the Chief of
 * Staff; NOT witnessed first-hand by the agent that wrote this): "On the
 * home page- in the best value - the complete collection box - the FREE
 * Activity Book, Free Vocab, and FREE Shipping needs to be in bullet
 * points."
 *
 * ⭐ THE BAND NOW CALLS THE SHARED HELPER `bhp_book_free_bullets_markup()`,
 *    which is the same list already shipping on all four funnel offer
 *    surfaces and both ends of /complete-collection/. So the properties this
 *    section used to assert about a hand-written sentence are now asserted in
 *    two halves: that the BAND delegates to the helper (here), and that the
 *    HELPER gates each line on its own live predicate (§3h below, and
 *    tests/test-free-activity-book-messaging.php).
 *
 * ⛔ THE SUITE IS STRICTER AFTER THIS RETARGET, NOT LOOSER. Three assertions
 *    are ADDED that had no equivalent before: that the combined sentence is
 *    ABSENT from the code (the failure mode this change exists to remove),
 *    that a THIRD free item — the Vocabulary Card Activity — reaches the
 *    band, and that the band composes no free-item copy of its own.
 *
 * SUPERSEDED needles, verbatim:
 *
 *     $cbf_format = 'The complete collection ships %s.';
 *     false !== strpos( $cbf_code, $cbf_format )
 *     preg_match( '/bhp_book_collection_ships_free\(\).{0,1200}The complete collection ships %s\./s', $cbf_code )
 *     $cbf_addon_format = 'The complete collection ships %1$s and includes the Activity Book %2$s.';
 *     false !== strpos( $cbf_code, $cbf_addon_format )
 *     preg_match( '/bhp_book_collection_includes_free_addon\(\).{0,300}The complete collection ships %1\$s and includes the Activity Book %2\$s\./s', $cbf_code )
 *     preg_match( '/function_exists\(\s*\x27bhp_book_collection_includes_free_addon\x27\s*\)/', $cbf_code )
 *     preg_match( '/bhp_book_collection_ships_free\(\).{0,900}bhp_book_collection_includes_free_addon\(\)/s', $cbf_code )
 *     preg_match( '/function_exists\(\s*\x27bhp_book_collection_ships_free\x27\s*\)/', $cbf_code )
 *     preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,1600}The complete collection ships %s\./s', $cbf_code )
 *     preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,1600}The complete collection ships %1\$s and includes the Activity Book %2\$s\./s', $cbf_code )
 *     preg_match( '/<strong[^>]*>\s*\.\s*esc_html__\(\s*\x27FREE\x27/', $cbf_code )
 */

bhp_cbf_assert(
	false !== strpos( $cbf_code, 'bhp_book_free_bullets_markup(' ),
	'3. the band calls the SHARED free-bullets helper in CODE (not only in a comment)',
	$failures
);
bhp_cbf_assert(
	1 === preg_match( '/function_exists\(\s*\x27bhp_book_free_bullets_markup\x27\s*\)/', $cbf_code ),
	'3. that call is function_exists-guarded, so a partial deploy degrades to a band with no free-item list rather than fatalling',
	$failures
);
bhp_cbf_assert(
	1 === preg_match( '/bhp_book_free_bullets_markup\(\s*\x27collection\x27/', $cbf_code ),
	'3. ⭐ and it asks for the COLLECTION scope — the scope in which the free-shipping line is entitled to appear',
	$failures
);
/*
 * ⛔ THE ANTI-REGRESSION ASSERTION, and it is the one this whole change is
 *    for. Andrew's 2026-08-06 standing ruling forbids the combined sentence
 *    ("never combined sentences"), and this band was the last surface still
 *    printing one. Checked against $cbf_code, NOT $cbf_src: the superseded
 *    sentence is quoted verbatim in the partial's comments on purpose, so a
 *    raw-source scan would fail on a correct build.
 */
bhp_cbf_assert(
	false === strpos( $cbf_code, 'The complete collection ships' )
	&& false === strpos( $cbf_code, 'includes the Activity Book' ),
	'3. ⭐⭐ the COMBINED SENTENCE is gone from the band CODE (Andrew 2026-08-11); its removal is not hidden by the comments that still quote it verbatim',
	$failures
);
/*
 * ⛔ AND THE BAND MUST NOT HAVE GROWN ITS OWN REPLACEMENT COPY. The whole
 *    point of delegating is that there is exactly one place these strings
 *    live. A band that hand-wrote "FREE Shipping" would drift the moment a
 *    fourth free item is added, which is the defect this file now guards.
 */
bhp_cbf_assert(
	false === stripos( $cbf_code, 'FREE Shipping' )
	&& false === stripos( $cbf_code, 'FREE Activity Book' )
	&& false === stripos( $cbf_code, 'Vocabulary Card' ),
	'3. ⭐ the band composes NO free-item copy of its own — every line comes from the shared helper or the plugin',
	$failures
);

/*
 * ═════════════════════════════════════════════════════════════════════
 * ⭐ RETARGETED AGAIN 1.19.194 (2026-08-05, CYCLE144-LD-223). WINDOW
 *    WIDENED 400 -> 1200, AND THE REASON IS STATED SO IT IS NOT READ AS
 *    A WEAKENING.
 * ═════════════════════════════════════════════════════════════════════
 *
 * The band now prints TWO sentences from inside the SAME shipping gate:
 * the 1.19.179 sentence when only shipping is free, and a longer one that
 * also names the free activity book when the plugin confirms the add-on is
 * live. The second branch sits between the gate and the original format
 * string, so the 400-character window no longer reaches it. Superseded
 * assertion, verbatim, so the move is visible rather than silent:
 *
 *     preg_match( '/bhp_book_collection_ships_free\(\).{0,400}The complete collection ships %s\./s', $cbf_code )
 *
 * ⭐ THE GUARD IS STRICTER AFTER THIS CHANGE, NOT LOOSER: the widened
 *    window is paired with THREE new assertions below that did not exist
 *    before, one per new failure mode. A wider window on its own would be
 *    a weakening; a wider window plus the branch, the gate and the
 *    both-gates ordering being asserted individually is not.
 */
/*
 * ⭐ 3a–3d RETARGETED 1.19.218 (CYCLE154-LD-01). The nesting they asserted was
 *    a property of ONE SENTENCE claiming TWO facts: printing "ships free AND
 *    includes the activity book" while only one held would have been a lie
 *    about the other. ⭐ BULLETS REMOVE THAT FAILURE MODE BY CONSTRUCTION —
 *    each line is its own claim behind its own predicate, so there is nothing
 *    left to nest. The property that replaces it, and is asserted below and at
 *    §4, is INDEPENDENCE: each line appears exactly when its own predicate is
 *    true, and never because another one is.
 *
 * ⛔ THE GATES THEMSELVES ARE NOT DROPPED — they moved into the helper, and
 *    §3h asserts them there rather than taking the helper's word for it.
 */
bhp_cbf_assert(
	function_exists( 'bhp_book_free_bullet_lines' ) && function_exists( 'bhp_book_free_bullets_markup' ),
	'3a. the shared free-bullets helpers are loaded',
	$failures
);
bhp_cbf_assert(
	1 === preg_match(
		'/function bhp_book_free_bullet_lines.{0,900}bhp_book_collection_ships_free\(\).{0,900}bhp_book_collection_includes_free_addon\(\).{0,900}bhp_bundle_vocab_cards_live\(\)/s',
		bhp_cbf_strip_comments( (string) $cbf_helper_src )
	),
	'3h. ⭐ the helper gates all THREE lines on their own live predicates, in the brief\'s fixed order (Shipping, Activity Book, Vocabulary Cards)',
	$failures
);
/*
 * ⭐ INDEPENDENCE, EXERCISED RATHER THAN ASSUMED. `bhp_book_free_bullet_lines()`
 *    is called with the non-collection scope, where the free-SHIPPING line is
 *    not entitled to appear. If the activity-book or vocabulary line vanished
 *    too, the lines would be coupled and a single predicate would be deciding
 *    for all three — the exact property the combined sentence had and bullets
 *    are supposed to remove.
 */
if ( function_exists( 'bhp_book_free_bullet_lines' ) ) {
	$cbf_any  = bhp_book_free_bullet_lines( 'any_book' );
	$cbf_coll = bhp_book_free_bullet_lines( 'collection' );
	$cbf_ship_in_any = false;
	foreach ( $cbf_any as $cbf_line ) {
		if ( false !== stripos( $cbf_line, 'shipping' ) ) {
			$cbf_ship_in_any = true;
		}
	}
	bhp_cbf_assert(
		false === $cbf_ship_in_any,
		'3i. ⭐ the free-SHIPPING line is scoped: it does NOT appear in the any_book scope',
		$failures
	);
	bhp_cbf_assert(
		count( $cbf_coll ) === count( $cbf_any ) + 1,
		sprintf( '3j. ⭐ and dropping it drops EXACTLY one line — the three claims are independent, not one gate deciding for all (collection=%d, any_book=%d)', count( $cbf_coll ), count( $cbf_any ) ),
		$failures
	);
}
bhp_cbf_assert(
	function_exists( 'bhp_book_collection_includes_free_addon' ),
	'3e. bhp_book_collection_includes_free_addon() is loaded',
	$failures
);
/*
 * The predicate must delegate to the PLUGIN, never answer for itself — the
 * same property section 2 asserts for the shipping predicate.
 */
bhp_cbf_assert(
	1 === preg_match(
		'/function bhp_book_collection_includes_free_addon.{0,400}function_exists\(\x27bhp_bundle_addon_free_with_collection\x27\).{0,200}return false;/s',
		bhp_cbf_strip_comments( (string) $cbf_helper_src )
	),
	'3f. ⭐ and it returns FALSE when the plugin is absent — the theme never decides on its own that a book is free',
	$failures
);
if ( function_exists( 'bhp_book_collection_includes_free_addon' ) && function_exists( 'bhp_bundle_addon_free_with_collection' ) ) {
	bhp_cbf_assert(
		bhp_book_collection_includes_free_addon() === (bool) bhp_bundle_addon_free_with_collection(),
		'3g. the theme predicate and the plugin predicate agree on THIS environment (live check, not a source read)',
		$failures
	);
}
/*
 * ⭐ THE OWNER GATE, RETARGETED 1.19.218 AND NOT DROPPED. The property is
 *    unchanged and is the one that matters: the free-item copy sits INSIDE
 *    `$bhp_cc_price_cues_on`, behind the SAME single owner gate as the
 *    $4.98/$3.98 figures it follows. Only the thing being gated changed —
 *    a sentence became a call to the shared helper.
 *
 * ⛔ MOVING FREE-ITEM COPY OUT OF AN OWNER GATE WOULD BE A SCOPE EXPANSION
 *    WEARING A FORMATTING CHANGE'S CLOTHES. This assertion is what stops a
 *    later pass from doing it by accident.
 */
bhp_cbf_assert(
	1 === preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,4000}\$bhp_cc_free_bullets/s', $cbf_code ),
	'3. the free-item bullets extend the SAVINGS line and therefore sit behind the same single owner gate as the figures they follow',
	$failures
);

/*
 * ⭐ 1.19.179's TWO BOLD/CAPITALS ASSERTIONS, RETARGETED 1.19.218 — NOT
 *    DROPPED. Andrew asked for bold on 2026-08-05 and has not withdrawn it;
 *    what changed is WHERE the markup is composed. `<strong>` now comes from
 *    `bhp_book_free_bullets_markup()`, and the capitals still come from the
 *    translated string, never from `text-transform`. Both properties are
 *    asserted at their new home, and the rendered check at §4 proves they
 *    survive the round trip.
 */
bhp_cbf_assert(
	1 === preg_match( '/function bhp_book_free_bullets_markup.{0,600}<li class="bhp-free-bullets__item"><strong>/s', bhp_cbf_strip_comments( (string) $cbf_helper_src ) ),
	'3. ⭐ FREE is emphasised in MARKUP (<strong>), not by a CSS font-weight — emphasis that survives email, reader mode and a screen reader',
	$failures
);
$cbf_css = (string) bhp_cbf_read( 'style.css' );
bhp_cbf_assert(
	false === stripos( $cbf_css, '.home-collection-feature__free' )
	|| 0 === preg_match( '/\.home-collection-feature__free\s*\{[^}]*text-transform/si', $cbf_css ),
	'3. ⭐ the capitals are TYPED, not produced by text-transform — a CSS transform leaves the DOM text lowercase for copy-paste, translation and assistive tech',
	$failures
);
/*
 * ⛔ ADDED 1.19.218 — the same rule, applied to the class that now actually
 *    carries the capitals. Without this the guard would be watching an inert
 *    selector while the live one went unchecked.
 */
bhp_cbf_assert(
	0 === preg_match( '/\.bhp-free-bullets(__item)?[^{]*\{[^}]*text-transform/si', $cbf_css ),
	'3. ⭐ and no text-transform is applied to the bullet items either — the class that now carries the capitals',
	$failures
);
/*
 * ⛔ ADDED 1.19.218 — the band centres the LIST, never the TEXT. Centring
 *    each line is what makes a bullet list stop reading as a list, and the
 *    box it sits in is `text-align: center`, so the scoped rule has to exist
 *    and has to leave the base class's `text-align: left` alone.
 */
bhp_cbf_assert(
	1 === preg_match( '/\.home-collection-feature \.bhp-free-bullets\s*\{[^}]*margin-inline:\s*auto/si', $cbf_css )
	&& 0 === preg_match( '/\.home-collection-feature \.bhp-free-bullets\s*\{[^}]*text-align:\s*center/si', $cbf_css ),
	'3. ⭐ the scoped rule centres the LIST BLOCK inside the centred box and does NOT centre the lines',
	$failures
);

// --- Claim safety on the strings themselves. ---

/*
 * ⭐ RETARGETED 1.19.218. Superseded fixture, verbatim:
 *      $cbf_sentence = sprintf( $cbf_format, 'FREE' );
 *
 * ⛔ THE CHECKS NOW RUN OVER EVERY LINE THE HELPER ACTUALLY RETURNS ON THIS
 *    ENVIRONMENT, which is strictly more than the one sentence they used to
 *    examine — three claims instead of one.
 *
 * ⚠ ONE CHECK IS DELIBERATELY NOT CARRIED OVER AND THE REASON IS RECORDED
 *   RATHER THAN THE CHECK QUIETLY DROPPED. The superseded assertion
 *
 *       0 === preg_match( '/\d/', $cbf_sentence )
 *       '3. the sentence quotes NO figure — it adds a fact, not a number'
 *
 *   would now FAIL ON A CORRECT BUILD: the activity-book line legitimately
 *   carries "a $5.00 savings", which Andrew asked for and which the PLUGIN
 *   composes from WooCommerce's own price record. ⛔ The property that
 *   actually mattered — that no SHIPPING figure is quoted — is unchanged,
 *   still enforced, and is asserted directly below and again at §4.
 */
$cbf_lines = function_exists( 'bhp_book_free_bullet_lines' ) ? bhp_book_free_bullet_lines( 'collection' ) : array();
bhp_cbf_assert(
	! empty( $cbf_lines ),
	sprintf( '3. the helper returns at least one free-item line on this environment (got %d)', count( $cbf_lines ) ),
	$failures
);
$cbf_urgency  = false;
$cbf_dashfail = false;
$cbf_shipfig  = false;
foreach ( $cbf_lines as $cbf_line ) {
	foreach ( array( '—', '–' ) as $cbf_dash ) {
		if ( false !== strpos( $cbf_line, $cbf_dash ) ) {
			$cbf_dashfail = true;
		}
	}
	foreach ( array( 'hurry', 'today only', 'last chance', 'expires', 'ends soon', 'act now', 'limited time' ) as $cbf_word ) {
		if ( false !== stripos( $cbf_line, $cbf_word ) ) {
			$cbf_urgency = true;
		}
	}
	if ( 1 === preg_match( '/shipping[^$]{0,40}\$\d/i', $cbf_line ) ) {
		$cbf_shipfig = true;
	}
}
bhp_cbf_assert( false === $cbf_dashfail, '3. no bullet line contains an em dash or en dash (the sitewide purge)', $failures );
bhp_cbf_assert( false === $cbf_urgency, '3. no false-urgency, scarcity or countdown language', $failures );
bhp_cbf_assert( false === $cbf_shipfig, '3. ⭐ NO SHIPPING DOLLAR FIGURE in any bullet line — the rule that keeps this band correct under the $0.00 collection tier', $failures );

preg_match_all( '/\$\d+\.\d\d/', $cbf_code, $cbf_currency );
bhp_cbf_assert(
	array_values( array_unique( $cbf_currency[0] ) ) === array( '$4.98', '$3.98' ),
	'3. REGRESSION: the ONLY currency figures in the band are still the two approved literals — no shipping amount got in',
	$failures
);

// =====================================================================
// 4. IT RENDERS, AND IT RENDERS ON BOTH SURFACES
// =====================================================================
/*
 * The partial is rendered into a buffer with each page's own arguments. This
 * is stronger than a source scan (it proves the gates actually open) and
 * weaker than a page load (no theme wrapper, no CSS, no browser). Both
 * statements are made so neither is over-read.
 */

/*
 * ⚠ FIXTURE CORRECTED 1.19.179 (CYCLE144-LD-70), and disclosed rather than
 *   quietly changed: this loop rendered the homepage surface with
 *   `'cta' => 'link'`, which stopped matching the real homepage at 1.19.177
 *   when front-page.php began passing `'checkout'`. It is now the argument
 *   front-page.php actually passes, so "RENDERED: … on homepage" means the
 *   homepage. Superseded value, verbatim:
 *       'homepage (front-page.php)' => array( 'cta' => 'link' ),
 *   Nothing about the free-shipping sentence depends on the branch — it sits
 *   above the CTA either way — so this changes what the label claims, not
 *   what passes.
 */
foreach ( array(
	'homepage (front-page.php)' => array( 'cta' => 'checkout' ),
	'/books/ (page-books.php)'  => array(
		'cta'           => 'checkout',
		'section_id'    => 'books-complete-collection',
		'title_id'      => 'books-collection-feature-title',
	),
) as $cbf_surface => $cbf_args ) {
	ob_start();
	get_template_part( 'template-parts/components/complete-collection-feature', null, $cbf_args );
	$cbf_html = (string) ob_get_clean();

	/*
	 * ═════════════════════════════════════════════════════════════════
	 * ⭐⭐ RETARGETED 1.19.218 (2026-08-11, CYCLE154-LD-01) — THE RENDERED
	 *     OUTPUT IS NOW A BULLET LIST, ON ANDREW'S 2026-08-11 INSTRUCTION.
	 * ═════════════════════════════════════════════════════════════════
	 *
	 * Superseded needles, verbatim, so the guard is seen to MOVE:
	 *
	 *     false !== strpos( $cbf_html, 'The complete collection ships <strong' )
	 *     preg_match( '#The complete collection ships <strong[^>]*>FREE</strong>(\.| and includes the Activity Book <strong[^>]*>FREE</strong>\.)#', $cbf_html )
	 *     $cbf_has_addon_clause = false !== strpos( $cbf_html, 'includes the Activity Book <strong' );
	 *
	 * ⛔ NOT A LOOSENING. The three properties those needles protected —
	 *    the claim renders, it is BOLD with TYPED capitals, and it appears
	 *    EXACTLY when the plugin says the offer is live — are all asserted
	 *    below against the new markup. What is ADDED is per-item structure
	 *    ("never combined sentences") and a THIRD item the sentence could
	 *    never carry.
	 */
	bhp_cbf_assert(
		false !== strpos( $cbf_html, '<ul class="bhp-free-bullets' ),
		"4. RENDERED: the shared FREE-items bullet list appears in the {$cbf_surface} band output",
		$failures
	);
	preg_match_all( '#<li class="bhp-free-bullets__item"><strong>(.*?)</strong></li>#s', $cbf_html, $cbf_items );
	$cbf_rendered_items = $cbf_items[1];
	bhp_cbf_assert(
		count( $cbf_rendered_items ) === count( $cbf_lines ),
		sprintf( '4. ⭐ RENDERED: one <li><strong> per free item on %s, matching the helper exactly (rendered %d, helper %d)', $cbf_surface, count( $cbf_rendered_items ), count( $cbf_lines ) ),
		$failures
	);
	/*
	 * ⛔ "NEVER COMBINED SENTENCES" (Andrew, 2026-08-06) ASSERTED STRUCTURALLY.
	 *    A single <li> carrying two FREE claims would satisfy a naive "the
	 *    words are on the page" check and would be exactly the shape the
	 *    ruling forbids.
	 */
	$cbf_combined = false;
	foreach ( $cbf_rendered_items as $cbf_item ) {
		if ( substr_count( strtoupper( $cbf_item ), 'FREE' ) > 1 ) {
			$cbf_combined = true;
		}
	}
	bhp_cbf_assert(
		false === $cbf_combined,
		"4. ⛔ RENDERED: no bullet combines two FREE claims into one line on {$cbf_surface} — the shape Andrew's 2026-08-06 ruling forbids",
		$failures
	);
	/*
	 * ⭐ THE BOLD AND THE TYPED CAPITALS, PROVEN IN THE OUTPUT rather than in
	 *    the source. Every item is inside a <strong>, and every item's DOM
	 *    text carries the capital FREE — a `text-transform` would leave it
	 *    lowercase here, which is the whole reason this is checked on the
	 *    rendered string.
	 */
	$cbf_all_caps_free = ! empty( $cbf_rendered_items );
	foreach ( $cbf_rendered_items as $cbf_item ) {
		if ( false === strpos( $cbf_item, 'FREE' ) ) {
			$cbf_all_caps_free = false;
		}
	}
	bhp_cbf_assert(
		$cbf_all_caps_free,
		"4. ⭐ RENDERED: every bullet is <strong> and carries a TYPED capital FREE in the DOM text on {$cbf_surface}",
		$failures
	);
	/*
	 * ⭐ THE LIVE-PREDICATE PINS, one per item, carried over from 1.19.194 and
	 *    extended to the third item. A page that promises a free book the cart
	 *    will charge $5.00 for is a lie on the surface that sells the
	 *    collection — and the same is now true of the vocabulary cards.
	 */
	$cbf_joined = implode( ' | ', $cbf_rendered_items );
	$cbf_ship_live = function_exists( 'bhp_book_collection_ships_free' ) && bhp_book_collection_ships_free();
	bhp_cbf_assert(
		$cbf_ship_live === ( false !== stripos( $cbf_joined, 'shipping' ) ),
		"4. ⭐ RENDERED: the FREE-shipping bullet is present on {$cbf_surface} EXACTLY when the plugin says the collection ships free (live=" . var_export( $cbf_ship_live, true ) . ')',
		$failures
	);
	$cbf_addon_live = function_exists( 'bhp_book_collection_includes_free_addon' )
		&& bhp_book_collection_includes_free_addon();
	bhp_cbf_assert(
		$cbf_addon_live === ( false !== stripos( $cbf_joined, 'Activity Book' ) ),
		"4. ⭐ RENDERED: the FREE-activity-book bullet is present on {$cbf_surface} EXACTLY when the plugin says the offer is live (live=" . var_export( $cbf_addon_live, true ) . ')',
		$failures
	);
	$cbf_vocab_live = function_exists( 'bhp_bundle_vocab_cards_live' ) && bhp_bundle_vocab_cards_live();
	bhp_cbf_assert(
		$cbf_vocab_live === ( false !== stripos( $cbf_joined, 'Vocabulary Card' ) ),
		"4. ⭐ RENDERED: the FREE-vocabulary-cards bullet — the third item, and the one the superseded sentence could never carry — is present on {$cbf_surface} EXACTLY when the plugin says so (live=" . var_export( $cbf_vocab_live, true ) . ')',
		$failures
	);
	/*
	 * ⛔ AND THE SENTENCE IT REPLACED MUST NOT COME BACK ALONGSIDE IT. Two
	 *    statements of the same fact in one box is the redundancy Andrew is
	 *    reporting elsewhere on the site; this is the assertion that stops
	 *    this band from acquiring it.
	 */
	bhp_cbf_assert(
		false === strpos( $cbf_html, 'The complete collection ships' ),
		"4. ⛔ RENDERED: the superseded combined sentence does NOT also appear on {$cbf_surface}",
		$failures
	);

	bhp_cbf_assert(
		false !== strpos( $cbf_html, 'Save $4.98 in hardcover, $3.98 in paperback.' ),
		"4. RENDERED: and it follows the approved savings line on {$cbf_surface}",
		$failures
	);
	bhp_cbf_assert(
		0 === preg_match( '/shipping[^<]{0,40}\$\d/i', $cbf_html ),
		"4. RENDERED: no shipping DOLLAR FIGURE appears on {$cbf_surface}",
		$failures
	);
	bhp_cbf_assert(
		false === stripos( $cbf_html, 'aggregateRating' ) && false === stripos( $cbf_html, 'reviewCount' ),
		"4. RENDERED: no rating or review schema on {$cbf_surface}",
		$failures
	);
}

// =====================================================================
// 5. NOTHING COMMERCIAL MOVED
// =====================================================================

if ( function_exists( 'bhp_bundle_rules' ) ) {
	bhp_cbf_assert(
		3.98 === round( (float) bhp_bundle_rules( 'paperback' )[3]['discount'], 2 )
		&& 4.98 === round( (float) bhp_bundle_rules( 'hardcover' )[3]['discount'], 2 ),
		'5. REGRESSION: collection discounts unchanged at $3.98 paperback / $4.98 hardcover — the two literals in the band still match the table',
		$failures
	);
}
bhp_cbf_assert(
	false === strpos( (string) $cbf_src, 'update_option' )
	&& false === strpos( (string) $cbf_src, 'BookVAULT' ),
	'5. the band writes no option and names no BookVAULT shipping method',
	$failures
);

// ---------------------------------------------------------------------
echo "\n";
if ( empty( $failures ) ) {
	echo "ALL COLLECTION-BAND FREE-SHIPPING TESTS PASSED\n";
	exit( 0 );
}

echo count( $failures ) . " TEST(S) FAILED:\n";
foreach ( $failures as $label ) {
	echo " - {$label}\n";
}
exit( 1 );
