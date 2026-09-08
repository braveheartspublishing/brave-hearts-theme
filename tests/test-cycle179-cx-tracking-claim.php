<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE TRACKING-CLAIM GATE — theme 1.19.387, `CYCLE179-CX-TRACKING-CLAIM`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-cx-tracking-claim.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHY THIS SUITE EXISTS
 * ---------------------------------------------------------------------------
 * The store told buyers, on the product page and on all three audience
 * landing pages, that an order carries tracking. It does not. Owner,
 * verbatim: *"we dont have tracking by the way"*.
 *
 * The claim had FOUR independent carriers in theme code, written at four
 * different times, and two earlier correction passes each fixed one carrier
 * and left the others standing. That is the failure mode this file exists to
 * stop. A gate that scans the shipped source is the only thing that catches
 * carrier number five.
 *
 * ---------------------------------------------------------------------------
 * ⛔ THE RULE THIS ENFORCES
 * ---------------------------------------------------------------------------
 * No customer-facing string in this theme may claim, promise or imply that an
 * order carries tracking. The permitted uses of the word are enumerated below
 * as a FROZEN ALLOWLIST, each with the reason it is permitted. Anything not on
 * that list fails. Editing a permitted string also fails, on purpose: the
 * allowlist is matched on the exact text, so a reworded exception has to be
 * re-approved rather than inherited.
 *
 * ⛔ NOTHING REPLACED THE REMOVED CLAIM. Not "tracking where available", not a
 *    delivery window, not a substitute reassurance. `inc/bookvault-tracker.php`
 *    reads a Bookvault dispatch record that SOMETIMES carries a tracking
 *    number and prints "not supplied" when it does not, so whether any given
 *    order has one is UNAVAILABLE. An unavailable fact is not a sentence on a
 *    product page.
 *
 * ---------------------------------------------------------------------------
 * ⚠️ WHAT THIS SUITE CANNOT SEE, STATED RATHER THAN LEFT IMPLIED
 * ---------------------------------------------------------------------------
 *   1. THE BUNDLE PLUGIN. `brave-hearts-bundle-pricing` renders the Complete
 *      Collection landing page from its own shortcode and prints its own
 *      "Secure checkout &middot; Tracking provided" line. It is a SEPARATE
 *      artefact, it is not in the theme ZIP, and it was READ-ONLY to the pass
 *      that wrote this file. §4 asserts the theme is clean; it makes no claim
 *      about the plugin, and the plugin was still carrying the line when this
 *      was written.
 *   2. PAGE AND POST CONTENT IN THE DATABASE. The `/shipping-policy/` page
 *      carries its own Tracking heading. Content is Andrew's, not a theme
 *      file, and this suite reads no database rows.
 *
 * ⛔ IT WRITES NOTHING. No order, no option, no post, no meta, no network
 *    call, no mail. It reads theme source files and calls two pure string
 *    functions. Safe on any environment.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['bhp_tc_pass'] = 0;
$GLOBALS['bhp_tc_fail'] = 0;

function bhp_tc_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_tc_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_tc_fail']++;
		echo "FAIL  {$label}" . ( '' !== $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

function bhp_tc_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * The frozen allowlist: every translated theme string that may contain the
 * letters "track", with the reason. Matched on exact text.
 *
 * @return array<string,string> string => reason it is permitted.
 */
function bhp_tc_allowed_strings() {
	return array(
		// Explorer Passport. "Track" here is a child following places on a map.
		'Track every real place Charlotte and Henry visit and see the adventure grow around the world.'
			=> 'Explorer Passport map copy. Not a shipment.',
		'The Explorer Passport will help readers track real destinations, celebrate completed books, and carry every adventure into the next one.'
			=> 'Explorer Passport lead magnet copy. Not a shipment.',

		// Admin and audit-trail surfaces. Never rendered to a customer.
		'Every 3 hours (Bookvault tracker)'
			=> 'WP-Cron schedule label. Admin only.',
		'Bookvault dispatch confirmed by the tracker.'
			=> 'Private order note on the status transition. Admin only.',

		// The honest disclaimer. This is the OPPOSITE of the removed claim and
		// removing it would put the store back to saying nothing at all.
		'One honest thing: we do not receive a tracking number from our printer, so we cannot give you one. We would rather tell you that than send you a link that goes nowhere.'
			=> 'Completed-order email. States that tracking is NOT provided.',
	);
}

/* ===================================================================
 * §1 — the product-page shipping link
 * =================================================================== */
bhp_tc_head( '§1 the PDP shipping link text' );

bhp_tc_ok(
	'§1.1 bhp_book_pdp_shipping_link_text() exists',
	function_exists( 'bhp_book_pdp_shipping_link_text' )
);

$pdp = function_exists( 'bhp_book_pdp_shipping_link_text' )
	? bhp_book_pdp_shipping_link_text()
	: '';

bhp_tc_ok(
	'§1.2 it makes NO tracking claim',
	'' !== $pdp && false === stripos( $pdp, 'track' ),
	$pdp
);

bhp_tc_ok(
	'§1.3 "secure checkout" is KEPT (it is true: checkout is served over TLS)',
	false !== stripos( $pdp, 'secure checkout' ),
	$pdp
);

bhp_tc_ok(
	'§1.4 no substitute promise was invented in its place',
	false === stripos( $pdp, 'deliver' )
		&& false === stripos( $pdp, 'guarantee' )
		&& false === stripos( $pdp, 'when available' ),
	$pdp
);

bhp_tc_ok(
	'§1.5 no em dash (§9.1 copy rail)',
	false === strpos( $pdp, "\xe2\x80\x94" ),
	$pdp
);

/* ===================================================================
 * §2 — the source scan: every translated string in the theme
 * =================================================================== */
bhp_tc_head( '§2 translated-string scan across the theme' );

$root = get_template_directory();

$skip_dir = array( 'tests', 'plugins', 'node_modules', 'vendor', '.git', '.claude', 'tmp', 'languages' );

$files = array();
$it    = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
		function ( $current ) use ( $skip_dir ) {
			$name = $current->getFilename();
			if ( $current->isDir() ) {
				if ( in_array( $name, $skip_dir, true ) ) {
					return false;
				}
				return 0 !== strpos( $name, '_pre-edit-backups' );
			}
			return (bool) preg_match( '/\.php$/', $name );
		}
	)
);
foreach ( $it as $f ) {
	$files[] = $f->getPathname();
}
sort( $files );

bhp_tc_ok( '§2.1 the scan found theme PHP files to read', count( $files ) > 100, 'files=' . count( $files ) );

$allowed   = bhp_tc_allowed_strings();
$offenders = array();
$permitted = array();

$call = '/\b(?:esc_html__|esc_html_e|esc_attr__|esc_attr_e|_ex|_nx|__|_e|_x|_n)\s*\(\s*'
	. '(?:\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)")/';

foreach ( $files as $file ) {
	$src = file_get_contents( $file );
	if ( false === $src || false === stripos( $src, 'track' ) ) {
		continue;
	}

	if ( ! preg_match_all( $call, $src, $m, PREG_SET_ORDER ) ) {
		continue;
	}

	foreach ( $m as $set ) {
		$raw = ( isset( $set[2] ) && '' !== $set[2] ) ? $set[2] : $set[1];
		if ( false === stripos( $raw, 'track' ) ) {
			continue;
		}

		$text = str_replace( array( "\\'", '\\"', '\\\\' ), array( "'", '"', '\\' ), $raw );

		if ( isset( $allowed[ $text ] ) ) {
			// Counted as a SET, not as occurrences. The completed-order
			// disclaimer legitimately appears twice, once in the HTML email
			// template and once in the plain-text one.
			$permitted[ $text ] = true;
			continue;
		}

		$offenders[] = str_replace( $root, '', $file ) . '  ::  ' . $text;
	}
}

bhp_tc_ok(
	'§2.2 NO translated theme string contains a tracking claim',
	array() === $offenders,
	array() === $offenders ? '' : "\n        " . implode( "\n        ", $offenders )
);

$missing = array_diff( array_keys( $allowed ), array_keys( $permitted ) );

bhp_tc_ok(
	'§2.3 every allowlisted exception is still present, byte for byte',
	array() === $missing,
	'missing: ' . implode( ' | ', $missing )
		. ' (a reworded exception must be re-approved, not inherited)'
);

/* ===================================================================
 * §3 — the four carriers the defect actually had
 * =================================================================== */
bhp_tc_head( '§3 the four known carriers are all dead' );

$dead = array(
	'tracking on every order'          => 'PDP shipping link, both branches, and the functions.php fallback',
	'Tracking provided'                => 'the trust line on all three audience landing pages',
	'shipped with tracking'            => 'the Adventure Kit shipping FAQ',
	'shipped from the USA with tracking' => 'the gift-buyer shipping FAQ',
);

foreach ( $dead as $needle => $where ) {
	$hits = array();
	foreach ( $files as $file ) {
		$src = file_get_contents( $file );
		if ( false === $src ) {
			continue;
		}
		// The supersession records in the docblocks quote the dead strings on
		// purpose, so the assertion is on CODE, not on comments. Strip block
		// and line comments before looking.
		$code = preg_replace( '#/\*.*?\*/#s', '', $src );
		$code = preg_replace( '#^\s*//.*$#m', '', (string) $code );
		if ( false !== stripos( (string) $code, $needle ) ) {
			$hits[] = str_replace( $root, '', $file );
		}
	}
	bhp_tc_ok(
		'§3.' . ( array_search( $needle, array_keys( $dead ), true ) + 1 ) . ' "' . $needle . '" is gone from theme code',
		array() === $hits,
		implode( ', ', $hits ) . ' (' . $where . ')'
	);
}

/* ===================================================================
 * §4 — raw markup, not just translated strings
 * =================================================================== */
bhp_tc_head( '§4 raw markup scan' );

/*
 * ⚠️ THIS SCAN IS DELIBERATELY NARROWED, AND THE NARROWING IS ITSELF A
 *    FINDING WORTH WRITING DOWN.
 *
 * The first version of it failed on two files that are NOT defects and would
 * have had to be silenced by hand on every future build:
 *   · inc/class-bhp-analytics-debug.php prints "Tracking enabled this load"
 *     in an admin debug panel. That is ANALYTICS.
 *   · inc/class-bhp-meta-pixel.php contains the literal
 *     `fbq('track','PageView')` inside a script string.
 *
 * "Tracking" is three different words in this codebase: a shipment, an
 * analytics pixel, and CSS letter-spacing. Only the first is a claim. So the
 * gate fires when the word shares a text node with a SHIPPING word, plus a
 * short list of phrasings that are a promise with no neighbour needed.
 */
$ship = '/\b(order|orders|ship|ships|shipped|shipping|shipment|delivery|deliver|delivered|package|parcel|carrier|dispatch)\b/i';

$naked = array(
	'tracking provided',
	'with tracking',
	'tracking included',
	'track your order',
	'track your package',
	'tracking on every',
);

$raw_hits = array();
foreach ( $files as $file ) {
	$src = file_get_contents( $file );
	if ( false === $src || false === stripos( $src, 'track' ) ) {
		continue;
	}
	$code = preg_replace( '#/\*.*?\*/#s', '', $src );
	$code = preg_replace( '#^\s*//.*$#m', '', (string) $code );

	if ( ! preg_match_all( '/>([^<>]*)</', (string) $code, $nodes ) ) {
		continue;
	}

	foreach ( $nodes[1] as $node ) {
		if ( ! preg_match( '/\btrack(ing|ed|s)?\b/i', $node ) ) {
			continue;
		}

		$is_claim = (bool) preg_match( $ship, $node );

		if ( ! $is_claim ) {
			foreach ( $naked as $phrase ) {
				if ( false !== stripos( $node, $phrase ) ) {
					$is_claim = true;
					break;
				}
			}
		}

		if ( $is_claim ) {
			$raw_hits[] = str_replace( $root, '', $file )
				. '  ::  ' . trim( (string) preg_replace( '/\s+/', ' ', $node ) );
		}
	}
}

bhp_tc_ok(
	'§4.1 no untranslated markup text carries a shipment-tracking claim',
	array() === $raw_hits,
	array() === $raw_hits ? '' : "\n        " . implode( "\n        ", $raw_hits )
);

/* ===================================================================
 * §5 — the honest disclaimer must survive
 * =================================================================== */
bhp_tc_head( '§5 the completed-order email still says the true thing' );

foreach ( array(
	'/woocommerce/emails/customer-completed-order.php',
	'/woocommerce/emails/plain/customer-completed-order.php',
) as $rel ) {
	$src = file_exists( $root . $rel ) ? file_get_contents( $root . $rel ) : '';
	bhp_tc_ok(
		'§5' . $rel . ' still states that no tracking number is received',
		false !== stripos( (string) $src, 'we do not receive a tracking number from our printer' )
	);
}

/* =================================================================== */
echo "\n";
echo "PASS {$GLOBALS['bhp_tc_pass']}  FAIL {$GLOBALS['bhp_tc_fail']}\n";
if ( $GLOBALS['bhp_tc_fail'] > 0 ) {
	exit( 1 );
}
