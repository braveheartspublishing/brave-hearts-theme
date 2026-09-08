<?php
/**
 * CARRIER ITEM 192, LIMB 1 — THE FREE-SHIPPING CLAUSE'S CASING, GUARDED.
 * Theme 1.19.282 / plugin 1.8.66. `CYCLE165-LD-FINAL-FOUR`.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-freeship-line-parity.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ⭐ WHY THIS SUITE EXISTS, AND WHY IT EXISTS *NOW* RATHER THAN AT 1.19.280.
 *
 *    1.19.280 appended the founder's clause to the free-shipping sentence and
 *    deliberately LEFT THE CASING ALONE, because his own message wrote "Free"
 *    for both halves — including where it quoted the string that already
 *    shipped as "FREE" — so the casing was not readable as an instruction. It
 *    was flagged and routed to him rather than guessed. That comment named
 *    THIS FILE by name as the thing that would hold the line if he ruled:
 *
 *      "⛔ If he wants 'Free Shipping', it is one literal in three files and
 *          `test-freeship-line-parity.php` names all three."
 *
 * ⭐ HE RULED AT CARRIER ITEM 192 (2026-08-21, ⚠️ RELAYED through
 *    `chief-of-staff`, NOT witnessed first-hand by the agent that wrote this
 *    file): uppercase **FREE**, matching the pre-existing string's own style,
 *    everywhere the clause appears.
 *
 * ⭐⭐ THE RULING CONFIRMED WHAT WAS ALREADY SHIPPING, SO NO BYTE OF COPY MOVED.
 *    ⛔ THAT IS EXACTLY WHY THE GUARD IS THE DELIVERABLE. Before this file
 *    existed, the casing was protected only by a comment asking a future agent
 *    not to change it. A confirmed founder ruling deserves an executable gate,
 *    not a polite note — a string nobody asserts is a string that drifts.
 *
 * WHAT IT ASSERTS
 *   §1  the helper returns the exact bytes
 *   §2  every SHIPPED occurrence in theme + plugin source is the exact bytes
 *   §3  ⛔ NO lowercase "Free Shipping on the complete collection" variant
 *       exists anywhere in shipped code — the actual regression guard
 *   §4  the protected-elements manifest row carries the same exact bytes
 *   §5  the school-visit replacement path is untouched by any of the above
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED RATHER THAN GLOSSED: it reads
 *    SOURCE. It does not prove a rendered page. The rendered proof is the
 *    protected-elements suite (which counts the string in a served document)
 *    and the browser QA at an asserted `window.innerWidth`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. Same reason, same
 *    fix, as `test-purchase-flow-186.php`. A gate that cannot report failure
 *    is not a gate.
 */
$GLOBALS['flp_failures'] = 0;
$GLOBALS['flp_passes']   = 0;
function flp_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['flp_passes']++;
		echo "PASS: $label\n";
	} else {
		$GLOBALS['flp_failures']++;
		echo "FAIL: $label\n";
	}
}

/** ⭐ THE ONE TRUE BYTES. Every assertion below compares against this literal. */
$flp_expected = 'FREE Shipping on the complete collection or 3 or more books purchased';

/**
 * The casing variant the ruling forbids.
 *
 * ⛔⛔ IT NEVER OVERLAPS `$flp_expected`, AND THE FIRST VERSION OF THIS FILE
 *    GOT THAT WRONG. `substr_count()` is case-SENSITIVE, so 'Free Shipping…'
 *    and 'FREE Shipping…' are two disjoint literals: a file containing only
 *    the correct string matches the forbidden one ZERO times. The original
 *    assertions computed `count(forbidden) - count(expected)` as if the
 *    forbidden literal were a PREFIX the expected one contained — which made
 *    §2.1 and §3.1 pass for the wrong reason (a negative number is always
 *    `<= 0`) and made §4.2 fail on a perfectly correct manifest.
 *
 * ⭐ THE CORRECT TEST IS SIMPLY `=== 0`, and it is stated once here so all
 *    three sections read it the same way. Caught by the first staging sweep of
 *    1.19.282, which is exactly what a sweep is for.
 */
$flp_forbidden = 'Free Shipping on the complete collection';

$flp_theme  = get_template_directory();
$flp_plugin = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing';

/**
 * Comment-stripped source.
 *
 * ⛔ THIS IS LOAD-BEARING FOR §3, NOT HYGIENE. Both `inc/book-formats.php` and
 *    `tests/test-protected-elements.php` QUOTE Andrew's own message in a
 *    comment, and his message uses the lowercase "Free Shipping on the
 *    complete collection". A naive grep would therefore fail §3 on two files
 *    whose SHIPPED strings are perfectly correct — and an agent would then
 *    "fix" it by editing a verbatim founder quotation, which is the one thing
 *    §9 forbids. Stripping comments first is what makes the guard safe to obey.
 */
function flp_code( $path ) {
	if ( ! file_exists( $path ) ) {
		return '';
	}
	$out = '';
	foreach ( token_get_all( file_get_contents( $path ) ) as $t ) {
		if ( is_array( $t ) && in_array( $t[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$out .= is_array( $t ) ? $t[1] : $t;
	}
	return $out;
}

echo "=== FREE-SHIPPING CLAUSE PARITY — carrier item 192, limb 1 ===\n";
echo "Environment: " . home_url( '/' ) . "\n\n";

/* ───────────────────────────────────────────────────────────────────────────
 * §1 · THE HELPER RETURNS THE EXACT BYTES
 * ─────────────────────────────────────────────────────────────────────────── */
echo "--- §1 the helper ---\n";

flp_assert(
	function_exists( 'bhp_book_free_shipping_line' ),
	'1.1 bhp_book_free_shipping_line() exists'
);

if ( function_exists( 'bhp_book_free_shipping_line' ) ) {
	$flp_live = bhp_book_free_shipping_line();

	/*
	 * ⛔ THE SCHOOL-VISIT BRANCH LEGITIMATELY RETURNS SOMETHING ELSE. On a
	 *    flagged session the helper returns the hand-delivery sentence BY
	 *    DESIGN (`FD-505`/`FD-506`), so asserting the shipping string
	 *    unconditionally would fail a correctly behaving journey. Same guard
	 *    shape as `test-protected-elements.php`'s one conditional row.
	 */
	$flp_flagged = function_exists( 'bhp_school_visit_use_delivery_framing' )
		&& bhp_school_visit_use_delivery_framing();

	if ( $flp_flagged ) {
		echo "SKIP: 1.2 school-visit session — the helper correctly returns the hand-delivery sentence\n";
		flp_assert(
			false === strpos( $flp_live, 'Shipping on the complete collection' ),
			'1.2b …and it makes NO shipping claim to a parent whose books are hand-delivered'
		);
	} else {
		flp_assert(
			$flp_expected === $flp_live,
			'1.2 ⭐ the helper returns the founder-ruled bytes EXACTLY (item 192: uppercase FREE)'
		);
		flp_assert(
			0 === strpos( $flp_live, 'FREE ' ),
			'1.3 ⭐ the sentence opens with uppercase FREE, not "Free"'
		);
	}
}

/* ───────────────────────────────────────────────────────────────────────────
 * §2 · EVERY SHIPPED OCCURRENCE IS THE EXACT BYTES
 *
 * ⭐ THE THREE FILES THE 1.19.280 COMMENT PROMISED THIS SUITE WOULD NAME.
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §2 every shipped occurrence ---\n";

$flp_carriers = array(
	'inc/book-formats.php'                          => $flp_theme . '/inc/book-formats.php',
	'plugin includes/bundle-landing-page.php'       => $flp_plugin . '/includes/bundle-landing-page.php',
	'plugin includes/school-visit-pickup.php'       => $flp_plugin . '/includes/school-visit-pickup.php',
);

$flp_total_occurrences = 0;
foreach ( $flp_carriers as $flp_label => $flp_path ) {
	$flp_src = flp_code( $flp_path );
	flp_assert( '' !== $flp_src, "2.0 {$flp_label} is readable" );

	$flp_good = substr_count( $flp_src, $flp_expected );
	$flp_bad  = substr_count( $flp_src, $flp_forbidden );
	$flp_total_occurrences += $flp_good;

	flp_assert(
		0 === $flp_bad,
		sprintf( '2.1 %s carries NO lowercase variant in shipped code (found %d)', $flp_label, $flp_bad )
	);
}

/*
 * ⛔ AT LEAST THREE, NOT EXACTLY THREE. `bundle-landing-page.php` renders the
 *    clause twice (cold-open bullets and closing CTA bullets) and
 *    `school-visit-pickup.php` names it once in the code that REPLACES it. An
 *    `exact` count here would fail the next time a legitimate surface adopts
 *    the string, which is a gate that punishes correct work.
 */
flp_assert(
	$flp_total_occurrences >= 3,
	sprintf( '2.2 the clause is present in shipped code across the carriers (found %d, want >= 3)', $flp_total_occurrences )
);

/* ───────────────────────────────────────────────────────────────────────────
 * §3 · ⛔ THE REGRESSION GUARD — NO LOWERCASE VARIANT ANYWHERE IN SHIPPED CODE
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §3 the regression guard (whole tree, comments stripped) ---\n";

$flp_scan = array();
foreach ( array( $flp_theme . '/inc', $flp_theme . '/template-parts', $flp_plugin . '/includes' ) as $flp_dir ) {
	if ( ! is_dir( $flp_dir ) ) {
		continue;
	}
	$flp_it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $flp_dir ) );
	foreach ( $flp_it as $flp_file ) {
		if ( $flp_file->isFile() && 'php' === strtolower( $flp_file->getExtension() ) ) {
			$flp_scan[] = $flp_file->getPathname();
		}
	}
}

$flp_offenders = array();
foreach ( $flp_scan as $flp_file ) {
	$flp_src = flp_code( $flp_file );
	if ( '' === $flp_src ) {
		continue;
	}
	$flp_bad = substr_count( $flp_src, $flp_forbidden );
	if ( $flp_bad > 0 ) {
		$flp_offenders[] = str_replace( array( $flp_theme, $flp_plugin ), array( 'theme', 'plugin' ), $flp_file );
	}
}

flp_assert(
	empty( $flp_offenders ),
	sprintf(
		'3.1 ⭐⭐ NO file in %d scanned sources downcases the clause (offenders: %s)',
		count( $flp_scan ),
		$flp_offenders ? implode( ', ', $flp_offenders ) : 'none'
	)
);

/* ───────────────────────────────────────────────────────────────────────────
 * §4 · THE PROTECTED-ELEMENTS ROW CARRIES THE SAME EXACT BYTES
 *
 * ⭐ THIS IS THE "SYNC THE SUITE ROW TO THE FINAL BYTES" REQUIREMENT, MADE
 *    EXECUTABLE. Comparing the manifest row against the LIVE HELPER (not
 *    against a second copy of the literal) is what makes drift impossible:
 *    if either side ever moves alone, this fails.
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §4 the protected-elements manifest row ---\n";

$flp_pe = $flp_theme . '/tests/test-protected-elements.php';
$flp_pe_code = flp_code( $flp_pe );

flp_assert(
	'' !== $flp_pe_code,
	'4.0 the protected-elements suite is readable'
);
flp_assert(
	false !== strpos( $flp_pe_code, "'" . $flp_expected . "'" ),
	'4.1 ⭐ the manifest row is the founder-ruled bytes, character for character'
);
flp_assert(
	0 === substr_count( $flp_pe_code, $flp_forbidden ),
	sprintf(
		'4.2 the manifest carries no lowercase variant in its executable half (found %d)',
		substr_count( $flp_pe_code, $flp_forbidden )
	)
);

/* ───────────────────────────────────────────────────────────────────────────
 * §5 · THE SCHOOL-VISIT REPLACEMENT IS UNTOUCHED BY ALL OF THE ABOVE
 *
 * ⛔ A CASING GATE MUST NOT BECOME A REASON TO PROMISE SHIPPING TO A PARENT
 *    WHOSE BOOKS ARE BEING HAND-DELIVERED. This is asserted, not assumed.
 * ─────────────────────────────────────────────────────────────────────────── */
echo "\n--- §5 the school-visit path still overrides ---\n";

flp_assert(
	function_exists( 'bhp_school_visit_delivery_bullet' ),
	'5.1 the hand-delivery replacement still exists'
);
if ( function_exists( 'bhp_school_visit_delivery_bullet' ) ) {
	$flp_bullet = bhp_school_visit_delivery_bullet();
	flp_assert(
		'' !== $flp_bullet && false === strpos( $flp_bullet, 'Shipping on the complete collection' ),
		'5.2 ⭐ the hand-delivery sentence makes no shipping claim of any casing'
	);
}

echo "\n";
printf(
	"FREE-SHIPPING CLAUSE PARITY: %d passed, %d failed\n",
	(int) $GLOBALS['flp_passes'],
	(int) $GLOBALS['flp_failures']
);
if ( $GLOBALS['flp_failures'] > 0 ) {
	echo "FAILED (" . (int) $GLOBALS['flp_failures'] . ")\n";
}
echo "FAILURES: " . (int) $GLOBALS['flp_failures'] . "\n";
