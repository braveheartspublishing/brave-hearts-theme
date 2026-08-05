<?php
/**
 * Brave Hearts Theme — THE COLLECTION BAND SAYS "SHIPS FREE" (1.19.174).
 * CYCLE144-LD-42.
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
$cbf_format = 'The complete collection ships %s.';

bhp_cbf_assert(
	false !== strpos( $cbf_code, $cbf_format ),
	'3. the approved sentence is present in CODE (not only in a comment)',
	$failures
);
bhp_cbf_assert(
	1 === preg_match( '/bhp_book_collection_ships_free\(\).{0,1200}The complete collection ships %s\./s', $cbf_code ),
	'3. ⭐ and it is INSIDE the bhp_book_collection_ships_free() gate — never printed unconditionally',
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
$cbf_addon_format = 'The complete collection ships %1$s and includes the Activity Book %2$s.';

bhp_cbf_assert(
	false !== strpos( $cbf_code, $cbf_addon_format ),
	'3a. the free-activity-book sentence is present in CODE (not only in a comment)',
	$failures
);
bhp_cbf_assert(
	1 === preg_match( '/bhp_book_collection_includes_free_addon\(\).{0,300}The complete collection ships %1\$s and includes the Activity Book %2\$s\./s', $cbf_code ),
	'3b. ⭐ and it is INSIDE the bhp_book_collection_includes_free_addon() gate — the activity-book claim is never printed unconditionally',
	$failures
);
bhp_cbf_assert(
	1 === preg_match( '/function_exists\(\s*\x27bhp_book_collection_includes_free_addon\x27\s*\)/', $cbf_code ),
	'3c. that call is function_exists-guarded too, so a theme deployed against an older plugin degrades to the shipping-only sentence rather than fatalling',
	$failures
);
/*
 * ⛔ THE ORDERING ASSERTION. The activity-book gate must sit INSIDE the
 *    shipping gate, not beside it: the sentence it prints claims BOTH facts,
 *    so printing it while shipping is not free would be a false statement
 *    about shipping. Asserted by position, because that is what the nesting
 *    actually is.
 */
bhp_cbf_assert(
	1 === preg_match( '/bhp_book_collection_ships_free\(\).{0,900}bhp_book_collection_includes_free_addon\(\)/s', $cbf_code ),
	'3d. ⛔ the activity-book gate is NESTED INSIDE the shipping gate — a sentence claiming both facts can never print when only one holds',
	$failures
);
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
bhp_cbf_assert(
	1 === preg_match( '/function_exists\(\s*\x27bhp_book_collection_ships_free\x27\s*\)/', $cbf_code ),
	'3. the call is function_exists-guarded, so a partial deploy degrades to the old band rather than fatalling',
	$failures
);
/*
 * ⭐ WINDOW WIDENED 800 -> 1600, 1.19.194 (CYCLE144-LD-223), for the same
 *    reason as the one above and with the same compensation: the band now
 *    carries two branches inside the owner gate instead of one. Superseded
 *    assertion, verbatim:
 *      preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,800}The complete collection ships %s\./s', $cbf_code )
 *    The property being asserted — that the sentence lives INSIDE
 *    `$bhp_cc_price_cues_on` and therefore behind the same single owner gate
 *    as the $4.98/$3.98 figures — is unchanged, and is now asserted for BOTH
 *    sentences rather than one.
 */
bhp_cbf_assert(
	1 === preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,1600}The complete collection ships %s\./s', $cbf_code ),
	'3. the sentence extends the SAVINGS line and therefore sits behind the same single owner gate as the figures it follows',
	$failures
);
bhp_cbf_assert(
	1 === preg_match( '/if \(\$bhp_cc_price_cues_on\):.{0,1600}The complete collection ships %1\$s and includes the Activity Book %2\$s\./s', $cbf_code ),
	'3. ⭐ and so does the free-ACTIVITY-BOOK sentence - one owner gate, both claims',
	$failures
);

/*
 * ⭐ ADDED 1.19.179 — the two assertions that encode "put FREE in BOLD"
 *    honestly. Andrew asked for bold; the implementation choice this suite
 *    pins down is WHERE the bold and the capitals live.
 */
bhp_cbf_assert(
	1 === preg_match( '/<strong[^>]*>\s*\.\s*esc_html__\(\s*\x27FREE\x27/', $cbf_code )
	|| 1 === preg_match( '/<strong[^>]*>\x27\s*\.\s*esc_html__\(\s*\x27FREE\x27/', $cbf_code ),
	'3. ⭐ FREE is emphasised in MARKUP (<strong>), not by a CSS font-weight — emphasis that survives email, reader mode and a screen reader',
	$failures
);
bhp_cbf_assert(
	false === stripos( bhp_cbf_read( 'style.css' ), '.home-collection-feature__free' )
	|| 0 === preg_match( '/\.home-collection-feature__free\s*\{[^}]*text-transform/si', (string) bhp_cbf_read( 'style.css' ) ),
	'3. ⭐ the capitals are TYPED, not produced by text-transform — a CSS transform leaves the DOM text lowercase for copy-paste, translation and assistive tech',
	$failures
);

// --- Claim safety on the string itself. ---

/*
 * The rendered sentence, with the bold word substituted in — this is what a
 * reader actually sees, and it is what the claim-safety checks below must
 * examine. Superseded value quoted in the retargeting note above.
 */
$cbf_sentence = sprintf( $cbf_format, 'FREE' );
foreach ( array( '—', '–' ) as $cbf_dash ) {
	bhp_cbf_assert(
		false === strpos( $cbf_sentence, $cbf_dash ),
		'3. the sentence contains no em dash or en dash (the sitewide purge)',
		$failures
	);
}
bhp_cbf_assert(
	0 === preg_match( '/\d/', $cbf_sentence ),
	'3. the sentence quotes NO figure — it adds a fact, not a number',
	$failures
);
$cbf_urgency = false;
foreach ( array( 'hurry', 'today only', 'last chance', 'expires', 'ends soon', 'act now', 'limited time' ) as $cbf_word ) {
	if ( false !== stripos( $cbf_sentence, $cbf_word ) ) {
		$cbf_urgency = true;
	}
}
bhp_cbf_assert( false === $cbf_urgency, '3. no false-urgency, scarcity or countdown language', $failures );

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
	 * ⭐ RETARGETED 1.19.179 (CYCLE144-LD-70). Superseded needle, verbatim:
	 *      false !== strpos( $cbf_html, 'The complete collection ships free.' )
	 * ADDED alongside it, and stronger: the word must arrive INSIDE a
	 * <strong> in the rendered output, which a source scan cannot prove.
	 */
	bhp_cbf_assert(
		false !== strpos( $cbf_html, 'The complete collection ships <strong' ),
		"4. RENDERED: the sentence appears in the {$cbf_surface} band output",
		$failures
	);
	/*
	 * ═════════════════════════════════════════════════════════════════
	 * ⭐ RETARGETED 1.19.194 (2026-08-05, CYCLE144-LD-223), AFTER IT
	 *    FAILED ON A CORRECT BUILD ON STAGING. Recorded, not silently
	 *    rewritten.
	 * ═════════════════════════════════════════════════════════════════
	 *
	 * Superseded needle, verbatim:
	 *     preg_match( '#The complete collection ships <strong[^>]*>FREE</strong>\.#', $cbf_html )
	 *
	 * With the activity book live, the rendered sentence continues past
	 * the first </strong>: "... ships <strong>FREE</strong> and includes
	 * the Activity Book <strong>FREE</strong>." The old needle required a
	 * full stop immediately after the first bold word, so it reported a
	 * defect on a build that was doing exactly what Andrew asked for.
	 *
	 * ⛔ THE ALTERNATION IS NOT A LOOSENING. Both branches still require
	 *    the bold, the typed capitals AND the terminal full stop; and the
	 *    assertion immediately below pins WHICH branch must render on THIS
	 *    environment against the live predicate, so a build that dropped
	 *    the activity-book clause while the offer is live still fails.
	 */
	bhp_cbf_assert(
		1 === preg_match(
			'#The complete collection ships <strong[^>]*>FREE</strong>(\.| and includes the Activity Book <strong[^>]*>FREE</strong>\.)#',
			$cbf_html
		),
		"4. ⭐ RENDERED: FREE is bold, capitalised in the DOM text, and the sentence still closes with a full stop on {$cbf_surface}",
		$failures
	);
	/*
	 * ⭐ ADDED 1.19.194 — THE ASSERTION THAT MAKES THE ALTERNATION ABOVE
	 *    SAFE. It pins the rendered output to the LIVE predicate rather
	 *    than accepting either sentence unconditionally:
	 *      · offer live   -> the activity-book clause MUST be present;
	 *      · offer not live -> it MUST NOT be, because a page that promises
	 *        a free book the cart will charge $5.00 for is a lie on the
	 *        surface that sells the collection.
	 */
	$cbf_addon_live = function_exists( 'bhp_book_collection_includes_free_addon' )
		&& bhp_book_collection_includes_free_addon();
	$cbf_has_addon_clause = false !== strpos( $cbf_html, 'includes the Activity Book <strong' );
	bhp_cbf_assert(
		$cbf_addon_live === $cbf_has_addon_clause,
		"4. ⭐ RENDERED: the activity-book clause is present on {$cbf_surface} EXACTLY when the plugin says the offer is live (live=" . var_export( $cbf_addon_live, true ) . ", rendered=" . var_export( $cbf_has_addon_clause, true ) . ')',
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
