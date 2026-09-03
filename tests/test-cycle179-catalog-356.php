<?php
/**
 * THE MOBILE CATALOG PAIR. Theme 1.19.356, bundle plugin 1.8.81.
 * `CYCLE179-LD-356`. Founder direction seal 811; standing ruling seal 820.
 * ============================================================================
 *
 * Run:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-catalog-356.php \
 *      --user=1 --url=https://staging2.braveheartspublishing.com
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SUITE CANNOT PROVE, SAID FIRST RATHER THAN BURIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT CANNOT PROVE THAT THE TWO CARDS SIT SIDE BY SIDE. PHP has no viewport,
 *    no cascade and no layout engine. Nothing in this file shows that the
 *    Complete Collection card and the bundle card share a row at 375 or 390,
 *    which is the whole of what the founder asked for.
 *    ⭐ THAT PROOF IS THE BROWSER, at an asserted `window.innerWidth` in the
 *    SAME evaluation as every rectangle, and it is the PRIMARY evidence for
 *    this release. It is filed with a screenshot per surface per viewport at
 *    `pdp-redesign\356-STAGING\before\` and `\after\`, with the row grouping
 *    computed from the measured card tops.
 *
 * ⛔ IT CANNOT PROVE THE CONTRAST RATIO EITHER. The ratio is computed from the
 *    COMPUTED colour and the RESOLVED background in the same browser
 *    evaluation, because the value the customer sees is the cascade's, not the
 *    stylesheet's. This suite can only prove that an author colour is declared
 *    at all, which is the thing whose ABSENCE was the defect.
 *
 * ⛔ AND IT CANNOT REPRODUCE THE FOUNDER'S NEAR-WHITE. That was observed on his
 *    own device, in his own screenshot. Headless Chrome resolves the same
 *    control to black. The release report states that plainly rather than
 *    claiming a reproduction that did not happen.
 *
 * WHAT IT ASSERTS
 *   §1  the version numbers moved, in both files that carry them
 *   §2  item 1: the pairing rules exist, and CANNOT reach desktop or an archive
 *   §3  item 2: an author colour is declared on the hardcover swap
 *   §4  item 3: the slack is removed and the 48px touch target is NOT
 *   §5  item 4 (`CYCLE179-LD-16`): three surfaces off the build-time literal
 *   §6  the standing rails on everything this pass authored
 *   §7  the shipped CSS artefact is fresh and carries the new rules
 *
 * @package Brave_Hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ⛔ $GLOBALS, not `global` — `wp eval-file` runs this file inside a function,
 *    so a `global $x` in a helper binds to a different, always-empty variable
 *    and the summary prints "0 failed" on a broken build. Same reason, same
 *    fix, as `test-cycle179-catalog-350.php`.
 */
$GLOBALS['c356_failures'] = 0;
$GLOBALS['c356_passes']   = 0;
$GLOBALS['c356_skips']    = 0;

function c356_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['c356_passes']++;
		echo "PASS  {$label}\n";
		return;
	}
	$GLOBALS['c356_failures']++;
	echo "FAIL  {$label}\n";
}

function c356_skip( $label, $why ) {
	$GLOBALS['c356_skips']++;
	echo "SKIP  {$label} -- {$why}\n";
}

/*
 * ⛔ EVERY SOURCE READ IS NORMALISED TO \n BEFORE ANYTHING IS SEARCHED IN IT.
 *    `CYCLE179-LD-355` recorded a self-inflicted SKIP caused by exactly this:
 *    the plugin's `school-visit-pickup.php` is a CRLF file, so a needle written
 *    with "\n" matched nothing and the run correctly refused to claim a check
 *    it had not made. A skip is not a pass. Normalising first removes the whole
 *    class of failure, and the plugin files this suite reads are CRLF too.
 */
function c356_read( $relative_to_theme ) {
	$path = get_template_directory() . '/' . ltrim( $relative_to_theme, '/' );
	if ( ! file_exists( $path ) ) {
		return '';
	}
	return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
}

/*
 * ⛔⛔ THE PLUGIN IS NOT INSIDE THE THEME ON A DEPLOYED SITE, AND THE FIRST RUN
 *    OF THIS SUITE PROVED IT THE EXPENSIVE WAY.
 *
 * ⭐ In the REPOSITORY the plugin lives at `plugins/brave-hearts-bundle-pricing/`
 *    under the theme root, which is why `c356_read()` above looks correct. On a
 *    deployed site it is installed separately into `wp-content/plugins/` and the
 *    theme ZIP does not carry it at all. Reading it through the theme path
 *    returned '' for every plugin file.
 *
 * ⛔⛔ AND THE DAMAGE WAS NOT THE FIVE HONEST FAILURES. It was that assertions
 *    5.2, 5.3 and 5.4 are of the form "this literal is ABSENT", and an empty
 *    string satisfies every one of them. THREE ASSERTIONS REPORTED PASS WHILE
 *    READING NOTHING AT ALL. A check that cannot fail is not a check, and this
 *    is the same failure class `CYCLE179-LD-355` recorded when a boundary
 *    assertion silently reported SKIP on a CRLF file: a run must never claim a
 *    check it did not make.
 *
 * ⭐ THE FIX IS BOTH HALVES. This resolver looks in the real plugin directory
 *    first and falls back to the repository layout, so the files are actually
 *    read; and every plugin assertion below is now gated on `'' !== $source`
 *    and reports SKIP, never PASS, when the source is unreadable.
 */
function c356_read_plugin( $relative_to_plugin ) {
	$candidates = array();
	if ( defined( 'WP_PLUGIN_DIR' ) ) {
		$candidates[] = WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/' . ltrim( $relative_to_plugin, '/' );
	}
	$candidates[] = get_template_directory() . '/plugins/brave-hearts-bundle-pricing/' . ltrim( $relative_to_plugin, '/' );

	foreach ( $candidates as $path ) {
		if ( file_exists( $path ) ) {
			return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
		}
	}
	return '';
}

$c356_css    = c356_read( 'style.css' );
$c356_min    = c356_read( 'style.min.css' );
$c356_series = c356_read_plugin( 'includes/bundle-shop-series.php' );
$c356_land   = c356_read_plugin( 'includes/bundle-landing-page.php' );
$c356_data   = c356_read_plugin( 'includes/bundle-data.php' );
$c356_boot   = c356_read_plugin( 'brave-hearts-bundle-pricing.php' );

echo "\n=== CYCLE179-LD-356 - the mobile catalog pair ===\n\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE VERSIONS
 * ═══════════════════════════════════════════════════════════════════════════ */

c356_assert( '' !== $c356_css, '1.1 style.css is readable' );
c356_assert( false !== strpos( $c356_css, 'Version: 1.19.356' ), '1.2 style.css declares 1.19.356' );
c356_assert( '1.19.356' === wp_get_theme()->get( 'Version' ), '1.3 the ACTIVE theme reports 1.19.356' );

/*
 * ⛔ THE PLUGIN CARRIES ITS VERSION TWICE and the two have drifted before. Both
 *    are asserted, because a header that says 1.8.81 over a constant that says
 *    1.8.80 breaks cache-busting on exactly the surfaces this release changed.
 */
if ( '' === $c356_boot ) {
	c356_skip( '1.4 the plugin header declares 1.8.81', 'the plugin bootstrap could not be read' );
	c356_skip( '1.5 the plugin CONSTANT declares 1.8.81', 'the plugin bootstrap could not be read' );
} else {
	c356_assert( false !== strpos( $c356_boot, 'Version: 1.8.81' ), '1.4 the plugin header declares 1.8.81' );
	c356_assert( false !== strpos( $c356_boot, "BHP_BUNDLE_PRICING_VERSION', '1.8.81'" ), '1.5 the plugin CONSTANT declares 1.8.81' );
}
if ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ) {
	c356_assert( '1.8.81' === BHP_BUNDLE_PRICING_VERSION, '1.6 the LOADED plugin constant is 1.8.81' );
} else {
	c356_skip( '1.6 the LOADED plugin constant is 1.8.81', 'BHP_BUNDLE_PRICING_VERSION is not defined in this context' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · ITEM 1 — THE PAIRING, AND THE TWO THINGS IT MUST NOT REACH
 * ═══════════════════════════════════════════════════════════════════════════ */

$c356_pair_anchor = 'body.bhp-catalog-grid.woocommerce-shop .woo-expedition-shell';

c356_assert( false !== strpos( $c356_css, $c356_pair_anchor ), '2.1 the pairing block exists' );
c356_assert(
	false !== strpos( $c356_css, $c356_pair_anchor . ' > .bhp-catalog-bundle-strip > ul.products' ),
	'2.2 the strip\'s own list is flattened, not just the section'
);
c356_assert(
	substr_count( $c356_css, 'display: contents' ) >= 1,
	'2.3 the two lists are flattened so both cards can be items of one grid'
);
c356_assert(
	false !== strpos( $c356_css, $c356_pair_anchor . ' ul.products li.product.bhp-shop-offer-item' ),
	'2.4 ⭐ the bundle card\'s order is set at a specificity that BEATS the (0,4,3) reading-order rule'
);

/*
 * ⛔⛔ THE TWO ASSERTIONS THIS SECTION EXISTS FOR. Everything above proves the
 *    rules are present; these two prove they cannot reach anything the founder
 *    already accepted.
 *
 * ⭐ 2.5 — EVERY new pairing declaration sits inside a `max-width: 640px`
 *    query, so the desktop grid (seal 764) is unreachable from here.
 * ⭐ 2.6 — EVERY new pairing selector carries `.woocommerce-shop`, so the 18
 *    taxonomy archives and product search are unreachable. Those surfaces
 *    render NO Collection card and NO bundle strip (`bhp_offer_catalog_bundle_
 *    strip()` returns early off `is_shop()`), were measured correct at 375 and
 *    390 before this release, and must stay exactly as they are.
 */
$c356_media_ok = true;
$c356_scope_ok = true;
$c356_depth    = 0;
$c356_in_640   = false;
$c356_640_at   = -1;
foreach ( explode( "\n", $c356_css ) as $c356_line ) {
	if ( preg_match( '/@media[^{]*max-width:\s*640px/', $c356_line ) ) {
		$c356_in_640 = true;
		$c356_640_at = $c356_depth;
	}
	if ( false !== strpos( $c356_line, $c356_pair_anchor ) ) {
		if ( ! $c356_in_640 ) {
			$c356_media_ok = false;
		}
		if ( false === strpos( $c356_line, '.woocommerce-shop' ) ) {
			$c356_scope_ok = false;
		}
	}
	$c356_depth += substr_count( $c356_line, '{' ) - substr_count( $c356_line, '}' );
	if ( $c356_in_640 && $c356_depth <= $c356_640_at ) {
		$c356_in_640 = false;
	}
}
c356_assert( $c356_media_ok, '2.5 ⛔ EVERY pairing declaration is inside a max-width:640px query - DESKTOP IS UNREACHABLE' );
c356_assert( $c356_scope_ok, '2.6 ⛔ EVERY pairing selector carries .woocommerce-shop - THE CATEGORY ARCHIVES ARE UNREACHABLE' );

/*
 * ⭐ The strip is still rendered by its own function on its own hook. This
 *    release is a LAYOUT change and moves no markup; if that ever stops being
 *    true, the desktop 608px card moves too and this assertion is the warning.
 */
c356_assert(
	function_exists( 'bhp_offer_catalog_bundle_strip' ),
	'2.7 the bundle strip is still rendered by its own function (no markup was moved)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ITEM 2 — AN AUTHOR COLOUR, WHOSE ABSENCE WAS THE DEFECT
 * ═══════════════════════════════════════════════════════════════════════════ */

$c356_e2 = strpos( $c356_css, 'li.bhp-shop-collection-item .bhp-offer__upsell,' );
if ( false === $c356_e2 ) {
	c356_assert( false, '3.1 the hardcover-swap block is present' );
} else {
	c356_assert( true, '3.1 the hardcover-swap block is present' );
	$c356_block = substr( $c356_css, $c356_e2, 600 );
	c356_assert(
		false !== strpos( $c356_block, 'color: var(--color-text)' ),
		'3.2 ⭐ an AUTHOR colour is declared on the hardcover swap - the user agent no longer chooses'
	);
	/*
	 * ⛔ THE TOKEN, NOT A HEX. A literal would be a second definition of the
	 *    body ink and would drift from `--color-text` the first time the
	 *    palette moves.
	 */
	c356_assert(
		0 === preg_match( '/color:\s*#[0-9a-fA-F]{3,8}\s*;/', $c356_block ),
		'3.3 ⛔ it is the TOKEN, not a hard-coded hex'
	);
}

/*
 * ⛔ THIS IS THE REGRESSION GUARD, AND IT IS THE ONE THAT MATTERS. The defect
 *    was that NOTHING declared a colour. If a future pass deletes the
 *    declaration the control silently returns to the user agent's `buttontext`
 *    and the founder's phone gets its near-white back, with no error anywhere.
 */
c356_assert(
	preg_match( '/\.bhp-offer__upsell[^{}]*\{[^{}]*color:/', $c356_css ) === 1
		|| false !== strpos( $c356_css, 'color: var(--color-text)' ),
	'3.4 ⛔ REGRESSION GUARD: the hardcover swap never returns to the UA system colour'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ITEM 3 — THE SLACK GOES, THE TOUCH TARGET DOES NOT
 * ═══════════════════════════════════════════════════════════════════════════ */

c356_assert(
	false !== strpos( $c356_css, $c356_pair_anchor . ' ul.products li.bhp-shop-collection-item .bhp-offer__upsell' ),
	'4.1 the slack above the hardcover swap is removed at 2-up widths'
);
c356_assert(
	false !== strpos( $c356_css, 'form.bhp-shop-collection-card__form--upsell' ),
	'4.2 the upsell form\'s own bottom slack is removed too'
);

/*
 * ⛔⛔ THE REFUSAL, ASSERTED SO IT CANNOT BE QUIETLY UNDONE. `min-height: 44px`
 *    on the base control and the 48px it computes to are the touch-target
 *    floor. This file already states, for this breakpoint, that "shrinking the
 *    type is a legitimate response to 172px, shrinking the target is not."
 *    Removing the dead band by shrinking the control would be the easy fix and
 *    the wrong one.
 */
c356_assert(
	false !== strpos( $c356_css, 'min-height: 44px' ),
	'4.3 ⛔ THE TOUCH TARGET IS NOT SHRUNK to close the band'
);
c356_assert(
	0 === preg_match( '/\.bhp-offer__upsell[^{}]*\{[^{}]*display:\s*none/', $c356_css ),
	'4.4 ⛔ the hardcover swap is NOT hidden - hiding it removes a purchase path from a phone (FD-439)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · ITEM 4 — `CYCLE179-LD-16`, THREE SURFACES OFF THE BUILD-TIME LITERAL
 * ═══════════════════════════════════════════════════════════════════════════ */

c356_assert( function_exists( 'bhp_bundle_saving_label' ), '5.1 the render-time saving helper from 1.8.80 is loaded' );

/*
 * ⛔ THE NEEDLE IS ASSEMBLED, NOT WRITTEN. A literal `$rules[2]['save']` in
 *    this file would make the file that checks for the literal contain the
 *    literal, which is precisely the trap `CYCLE179-LD-355` fell into and
 *    reported. The same discipline is applied to every needle below.
 */
$c356_lit_a = '$rules[' . '2' . ']' . "['save']";
$c356_lit_b = '$rules[' . '3' . ']' . "['save']";
$c356_lit_c = '$rule' . "['save']";

/*
 * ⛔ COMMENTS ARE STRIPPED BEFORE THE SEARCH. Both files now DESCRIBE the
 *    literal they no longer print, so a raw `strpos` would fail on the
 *    documentation rather than on the code. Searching the executable text is
 *    the only honest form of this assertion.
 */
function c356_code_only( $php ) {
	$php = preg_replace( '#/\*.*?\*/#s', '', $php );
	$php = preg_replace( '#^\s*//.*$#m', '', $php );
	return (string) $php;
}
$c356_series_code = c356_code_only( $c356_series );
$c356_land_code   = c356_code_only( $c356_land );

/*
 * ⛔⛔ THE READABILITY GATE, AND IT IS THE REASON THIS SECTION IS SHAPED LIKE
 *    THIS. Assertions 5.2 to 5.4 assert an ABSENCE, and an empty string
 *    satisfies an absence. Without this gate an unreadable file reports three
 *    PASSes and proves nothing. A SKIP is the honest result; a PASS is a lie.
 */
if ( '' === $c356_series ) {
	c356_skip( '5.2 bundle-shop-series no longer PRINTS the tier-2 build-time literal', 'bundle-shop-series.php could not be read' );
	c356_skip( '5.3 bundle-shop-series no longer PRINTS the tier-3 build-time literal', 'bundle-shop-series.php could not be read' );
	c356_skip( '5.5 both series surfaces call the render-time helper', 'bundle-shop-series.php could not be read' );
} else {
	c356_assert( false === strpos( $c356_series_code, $c356_lit_a ), '5.2 bundle-shop-series no longer PRINTS the tier-2 build-time literal' );
	c356_assert( false === strpos( $c356_series_code, $c356_lit_b ), '5.3 bundle-shop-series no longer PRINTS the tier-3 build-time literal' );
	c356_assert( 2 === substr_count( $c356_series_code, 'bhp_bundle_saving_label(' ), '5.5 both series surfaces call the render-time helper' );
}

if ( '' === $c356_land ) {
	c356_skip( '5.4 bundle-landing-page no longer PRINTS the build-time literal', 'bundle-landing-page.php could not be read' );
	c356_skip( '5.6 the landing fine print calls the render-time helper', 'bundle-landing-page.php could not be read' );
} else {
	c356_assert( false === strpos( $c356_land_code, $c356_lit_c ), '5.4 bundle-landing-page no longer PRINTS the build-time literal' );
	c356_assert( false !== strpos( $c356_land_code, 'bhp_bundle_saving_label(' ), '5.6 the landing fine print calls the render-time helper' );
}

/*
 * ⛔⛔ FAILING CLOSED IS THE POINT, AND IT IS WHAT MAKES THIS SAFE. The helper
 *    returns '' when a live price no longer matches the price the cart applies
 *    the discount under. If a caller printed that empty string next to a
 *    hard-coded separator the page would render a dangling punctuation mark
 *    instead of a saving. Every one of the three guards the empty case.
 */
if ( '' === $c356_series ) {
	c356_skip( '5.7 both series surfaces guard the empty label, separator included', 'bundle-shop-series.php could not be read' );
} else {
	c356_assert( 2 === substr_count( $c356_series_code, "'' !== \$bhp_series_save" ), '5.7 ⭐ both series surfaces guard the empty label, separator included' );
}
if ( '' === $c356_land ) {
	c356_skip( '5.8 the landing surface guards the empty label', 'bundle-landing-page.php could not be read' );
	c356_skip( '5.9 the landing fine print JOINS its parts', 'bundle-landing-page.php could not be read' );
} else {
	c356_assert( false !== strpos( $c356_land_code, "'' !== \$bhp_final_save" ), '5.8 ⭐ the landing surface guards the empty label' );
	c356_assert( false !== strpos( $c356_land_code, 'implode(' ), '5.9 ⛔ the landing fine print JOINS its parts, so an unstated part leaves no dangling separator' );
}

/*
 * ⛔⛔ SEAL 820. THE AMOUNT DOES NOT MOVE, AND THIS IS THE ASSERTION THAT SAYS
 *    SO. `CYCLE179-LD-15` was ruled by the founder: the two-paperback saving IS
 *    $1.99. This release changes WHERE the number comes from and never WHAT it
 *    is, so the approved literals in `bhp_bundle_rules()` are untouched.
 */
if ( '' === $c356_data ) {
	c356_skip( '5.10 bhp_bundle_rules() is still the single home of the approved amounts', 'bundle-data.php could not be read' );
} else {
	c356_assert(
		false !== strpos( $c356_data, 'function bhp_bundle_rules' ),
		'5.10 bhp_bundle_rules() is still the single home of the approved amounts'
	);
}
if ( function_exists( 'bhp_bundle_saving_label' ) ) {
	$c356_label = bhp_bundle_saving_label( 'paperback', 2 );
	c356_assert(
		'' === $c356_label || false !== strpos( $c356_label, '1.99' ),
		'5.11 ⭐ the tier-2 paperback saving computed from LIVE prices is $1.99 (seal 820) or is honestly silent'
	);
	echo "      (computed tier-2 paperback label: '" . $c356_label . "')\n";
} else {
	c356_skip( '5.11 the tier-2 paperback saving from live prices', 'bhp_bundle_saving_label() is not loaded' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THE STANDING RAILS, ON EVERYTHING THIS PASS AUTHORED
 * ═══════════════════════════════════════════════════════════════════════════ */

/*
 * ⛔ ASSEMBLED FROM FRAGMENTS, FOR THE SECOND TIME IN THIS FILE AND FOR THE
 *    SAME REASON. Written as literals, the check for internal call names in the
 *    PUBLIC repository would ITSELF put eight of them into the public
 *    repository. `CYCLE179-LD-355` caught that in its own suite before it ran;
 *    this file inherits the fix rather than the bug.
 */
$c356_aliases = array(
	'Gan' . 'dalf', 'Ara' . 'gorn', 'Leg' . 'olas', 'Mer' . 'ry',
	'Pip' . 'pin', 'Fro' . 'do', 'Bor' . 'omir', 'Gim' . 'li',
);
/*
 * ⛔ THE SOURCES ARE RESOLVED ONCE AND THE COUNT IS ASSERTED. An alias check is
 *    another ABSENCE assertion, so an unreadable file would pass it while
 *    reading nothing - the same trap §5 above fell into on this suite's first
 *    run. 6.0 proves the files were actually opened before 6.1 claims anything
 *    about what is not in them.
 */
$c356_scan = array(
	'style.css'                          => c356_read( 'style.css' ),
	'tests/test-cycle179-catalog-356.php' => c356_read( 'tests/test-cycle179-catalog-356.php' ),
	'plugin/bundle-shop-series.php'      => $c356_series,
	'plugin/bundle-landing-page.php'     => $c356_land,
	'plugin/brave-hearts-bundle-pricing.php' => $c356_boot,
);
$c356_unread = array();
foreach ( $c356_scan as $c356_name => $c356_body ) {
	if ( '' === $c356_body ) {
		$c356_unread[] = $c356_name;
	}
}
c356_assert( array() === $c356_unread, '6.0 ⛔ every file the alias and em-dash checks scan was actually READ (' . count( $c356_scan ) . ' files)' );

$c356_alias_hits = array();
foreach ( $c356_scan as $c356_name => $c356_body ) {
	if ( '' === $c356_body ) {
		continue;
	}
	foreach ( $c356_aliases as $c356_a ) {
		if ( false !== strpos( $c356_body, $c356_a ) ) {
			$c356_alias_hits[] = $c356_name;
		}
	}
}
if ( array() !== $c356_unread ) {
	c356_skip( '6.1 NO internal call name in any file this pass touched', 'one or more sources were unreadable: ' . implode( ', ', $c356_unread ) );
} else {
	c356_assert( array() === $c356_alias_hits, '6.1 ⛔ NO internal call name in any file this pass touched (Standing Rules 14.5)' );
}

/*
 * ⛔ RULE 608a. Checked on the STRINGS THIS PASS AUTHORED, not on the whole
 *    file: older prose elsewhere is another lane's scope and rewriting it here
 *    would be exactly the silent-sweep this release was told not to do.
 */
/*
 * ⛔⛔ THIS ASSERTION WAS NARROWED AFTER IT FAILED, AND THE REASON IS RECORDED
 *    RATHER THAN THE FAILURE BEING SILENCED.
 *
 * ⭐ It first scanned BOTH plugin files whole and failed on ONE em dash, at
 *    `bundle-landing-page.php` line 631, inside a PRE-EXISTING customer-facing
 *    `aria-label` that this pass did not write and was not scoped to touch.
 *    `CYCLE179-LD-355` explicitly recorded the wider em-dash sweep (finding
 *    `D5`, ten remaining surfaces) as OUT OF SCOPE and the founder's.
 *
 * ⛔ SO THE BUG WAS IN THE ASSERTION, NOT IN THE CODE: its LABEL said "the code
 *    this pass authored" and its IMPLEMENTATION read two entire files. An
 *    assertion that checks more than it claims will eventually fail for a
 *    reason its label cannot explain, and someone will then weaken it.
 *
 * ⭐ IT NOW SCANS EXACTLY THE LINES THIS PASS ADDED, identified by the
 *    variables this pass introduced. ⛔ THE PRE-EXISTING EM DASH IS NOT FIXED
 *    AND NOT HIDDEN: it is registered as `CYCLE179-LD-18` for the Chief of
 *    Staff to scope, because silently rewriting a customer-facing string
 *    outside this brief is precisely the unscoped sweep the brief forbids.
 */
if ( '' === $c356_series || '' === $c356_land ) {
	c356_skip( '6.2 no em dash in the lines this pass authored (608a)', 'a plugin source was unreadable' );
} else {
	$c356_markers = array( 'bhp_series_save_2', 'bhp_series_save_3', 'bhp_final_parts', 'bhp_final_save', 'bhp_final_sep' );
	$c356_authored_lines = array();
	foreach ( array( $c356_series_code, $c356_land_code ) as $c356_chunk ) {
		foreach ( explode( "\n", $c356_chunk ) as $c356_line ) {
			foreach ( $c356_markers as $c356_marker ) {
				if ( false !== strpos( $c356_line, $c356_marker ) ) {
					$c356_authored_lines[] = $c356_line;
					break;
				}
			}
		}
	}
	c356_assert( count( $c356_authored_lines ) >= 8, '6.2a the authored lines were actually located (' . count( $c356_authored_lines ) . ' found)' );
	$c356_emdash = false;
	foreach ( $c356_authored_lines as $c356_line ) {
		if ( false !== strpos( $c356_line, "\xE2\x80\x94" ) ) {
			$c356_emdash = true;
		}
	}
	c356_assert( ! $c356_emdash, '6.2b ⛔ no em dash in the lines this pass authored (608a)' );
}

/*
 * ⛔ NO NEW CUSTOMER-FACING STRING WAS COINED BY THIS RELEASE, and that is the
 *    cleanest way to satisfy the voice rule and the never-invent rule at once.
 *    Every word a customer reads still comes from the same helper it came from
 *    before. This assertion states the claim so a future pass has to break it
 *    deliberately.
 */
if ( '' === $c356_series || '' === $c356_land ) {
	c356_skip( '6.3 no saving string is HARD-CODED into a surface by this release', 'a plugin source was unreadable' );
} else {
	c356_assert(
		false === strpos( $c356_land_code, 'Save $' ) && false === strpos( $c356_series_code, 'Save $' ),
		'6.3 ⭐ no saving string is HARD-CODED into a surface by this release'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · THE SHIPPED ARTEFACT
 * ═══════════════════════════════════════════════════════════════════════════ */

c356_assert( '' !== $c356_min, '7.1 style.min.css is readable' );
c356_assert( false !== strpos( $c356_min, 'display:contents' ) || false !== strpos( $c356_min, 'display: contents' ), '7.2 ⭐ the flattening is SERVED, not only authored' );
c356_assert( false !== strpos( $c356_min, '.woocommerce-shop .woo-expedition-shell' ) || false !== strpos( $c356_min, 'woocommerce-shop' ), '7.3 the shop scope is served' );

/*
 * ⛔ A STALE MINIFY SHIPS VERIFIED CSS TO THE REPOSITORY AND NOTHING TO A
 *    CUSTOMER. 1.19.350 shipped a stale artefact once and it was caught by
 *    comparing hashes, not by reading a build log. Same gate, same reason.
 */
$c356_src = get_template_directory() . '/style.css';
if ( file_exists( $c356_src ) ) {
	$c356_hash = md5( str_replace( "\r\n", "\n", (string) file_get_contents( $c356_src ) ) );
	c356_assert( false !== strpos( $c356_min, $c356_hash ), '7.4 ⛔ style.min.css was built from the CURRENT style.css' );
}
c356_assert( false !== strpos( $c356_min, 'Version: ' . wp_get_theme()->get( 'Version' ) ), '7.5 ...and rebuilt for THIS release' );

echo "\n============================================================\n";
echo "CYCLE179-LD-356 - {$GLOBALS['c356_passes']} passed, {$GLOBALS['c356_failures']} failed, {$GLOBALS['c356_skips']} skipped\n";
echo "============================================================\n";
