<?php
/**
 * Brave Hearts Bundle Pricing — 1.8.84, `CYCLE179-CX-PLUGIN-TRACKING`.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-cycle179-cx-plugin-tracking.php --user=1
 *
 * WHAT THIS PROTECTS
 * ------------------
 * Until 1.8.84 the Collection purchase card on `/complete-collection/` — the
 * highest-value page in the store — printed:
 *
 *     Secure checkout · Tracking provided
 *
 * THE STORE DOES NOT HAVE TRACKING. Owner, verbatim, 2026-09-06: "we dont have
 * tracking by the way". Theme `1.19.387` (`CYCLE179-CX-TRACKING-CLAIM`) removed
 * eight such strings from six theme files the same day; the plugin is a
 * separate artefact and this is its release.
 *
 * The store had been contradicting itself in three directions at once:
 *   · this line said tracking is provided;
 *   · this same plugin's admin dashboard said no Bookvault tracking webhook or
 *     API integration exists;
 *   · the theme's completed-order email told the buyer the store does not
 *     receive a tracking number from its printer.
 *
 * THE GATE
 * --------
 * §1 strips every PHP comment with `token_get_all()` — the tokenizer, not a
 * regex — from every PHP file this plugin ships, and asserts that every
 * surviving case-insensitive occurrence of "tracking" matches a FROZEN
 * four-entry allowlist. Comments may discuss the removed claim freely; CODE AND
 * INLINE HTML MAY NOT CONTAIN IT. That is the distinction the suite is built on,
 * and it is why the historic markup can stay preserved verbatim in a comment
 * one line above without defeating the test.
 *
 * §5 proves the detector rather than asserting it: it feeds the detector the
 * exact removed literal and FAILS if the detector reports it clean.
 *
 * WHAT IS DELIBERATELY ALLOWED, AND WHY
 * ------------------------------------
 *   · `bhp-list-tracking` (script handle + asset filename) — GA4 `select_item`
 *     event instrumentation. An analytics identifier is not a shipping promise.
 *   · `initFormatSelectedTracking()` in bundle-drawer.js — same.
 *   · The admin dashboard note in class-bhp-dashboard-page.php. It is ADMIN-ONLY
 *     and it says tracking is NOT available. §4 asserts it SURVIVES: deleting
 *     the honest internal statement while removing the false customer one would
 *     be the wrong repair, and a blunt "no tracking string anywhere" rule would
 *     have demanded exactly that.
 *   · The CSS letter-spacing sense of the word, in stylesheet comments.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 * It writes NO option, product, order, coupon, page or setting on any
 * environment. It reads files off disk and reports. It is safe on production,
 * though the production gate blocks `wp eval-file` there by design.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_pt_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_pt_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

/**
 * The FROZEN allowlist. Every entry is a regex matched against a 160-character
 * window around a surviving "tracking" occurrence in comment-stripped PHP.
 *
 * ⛔ ADDING AN ENTRY HERE IS A DECISION, NOT A FIX. An occurrence that does not
 *    match one of these is either a customer-facing claim (remove it) or a new
 *    analytics identifier (then say so in the release record, and add it here).
 */
function bhp_pt_allowlist() {
	return array(
		'analytics script handle'  => '/bhp-list-tracking/i',
		'analytics asset filename' => '/assets\/bhp-list-tracking\.js/i',
		'admin note, availability' => '/tracking status is not available from any current data source/i',
		'admin note, no webhook'   => '/no Bookvault tracking webhook or API integration exists yet/i',
	);
}

/**
 * Strip every comment from PHP source using the tokenizer, then return what is
 * left. Inline HTML (T_INLINE_HTML) is KEPT — that is exactly the text a
 * customer reads, and the whole point of the gate.
 */
function bhp_pt_strip_php_comments( $source ) {
	if ( ! function_exists( 'token_get_all' ) ) {
		return null;
	}
	$out    = '';
	$tokens = @token_get_all( $source );
	if ( ! is_array( $tokens ) ) {
		return null;
	}
	foreach ( $tokens as $t ) {
		if ( is_array( $t ) ) {
			if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
				$out .= "\n";
				continue;
			}
			$out .= $t[1];
		} else {
			$out .= $t;
		}
	}
	return $out;
}

/**
 * Return every "tracking" occurrence in $code that matches NO allowlist entry.
 * Each returned row is the 160-char window, so a failure report shows the
 * offending text and not just a count.
 */
function bhp_pt_unallowed_hits( $code ) {
	$hits      = array();
	$allowlist = bhp_pt_allowlist();
	if ( ! preg_match_all( '/tracking/i', $code, $m, PREG_OFFSET_CAPTURE ) ) {
		return $hits;
	}
	foreach ( $m[0] as $match ) {
		$pos    = $match[1];
		$start  = max( 0, $pos - 80 );
		$window = substr( $code, $start, 160 );
		$ok     = false;
		foreach ( $allowlist as $pattern ) {
			if ( preg_match( $pattern, $window ) ) {
				$ok = true;
				break;
			}
		}
		if ( ! $ok ) {
			$hits[] = preg_replace( '/\s+/', ' ', $window );
		}
	}
	return $hits;
}

$plugin_dir = dirname( __DIR__ );

echo "===== 0. Fixture =====\n";
echo "  plugin dir: {$plugin_dir}\n";
bhp_pt_assert( is_dir( $plugin_dir ), 'Plugin directory resolves from the test file', $failures );
bhp_pt_assert( function_exists( 'token_get_all' ), 'Tokenizer extension available (the gate needs it; a regex is not good enough)', $failures );

$version_stamped = defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : '(undefined)';
echo "  BHP_BUNDLE_PRICING_VERSION = {$version_stamped}\n";

echo "\n===== 1. No customer-facing 'tracking' survives in any PHP this plugin ships =====\n";

$php_files = array();
$it        = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin_dir, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$path = str_replace( '\\', '/', $file->getPathname() );
	// The suite itself and any pre-edit backup directory are not shipped code.
	if ( false !== strpos( $path, '/tests/' ) || false !== strpos( $path, '/_pre-edit-backups' ) ) {
		continue;
	}
	$php_files[] = $path;
}
sort( $php_files );
echo '  scanned ' . count( $php_files ) . " shipped PHP files\n";
bhp_pt_assert( count( $php_files ) >= 20, 'Scanner found the plugin PHP tree (>= 20 files) — a near-empty scan is a false green', $failures );

$offenders     = array();
$untokenizable = array();
foreach ( $php_files as $path ) {
	$source = file_get_contents( $path );
	if ( false === $source ) {
		$untokenizable[] = $path;
		continue;
	}
	$code = bhp_pt_strip_php_comments( $source );
	if ( null === $code ) {
		$untokenizable[] = $path;
		continue;
	}
	$hits = bhp_pt_unallowed_hits( $code );
	foreach ( $hits as $h ) {
		$offenders[] = str_replace( $plugin_dir, '', $path ) . ' :: ' . $h;
	}
}
foreach ( $offenders as $o ) {
	echo "  OFFENDER: {$o}\n";
}
bhp_pt_assert( empty( $untokenizable ), 'Every shipped PHP file was readable and tokenizable', $failures );
bhp_pt_assert( empty( $offenders ), 'ZERO unallowed "tracking" occurrences in comment-stripped shipped PHP', $failures );

echo "\n===== 2. The Collection purchase card fine print =====\n";

$landing_path = $plugin_dir . '/includes/bundle-landing-page.php';
bhp_pt_assert( file_exists( $landing_path ), 'includes/bundle-landing-page.php exists', $failures );

if ( file_exists( $landing_path ) ) {
	$landing_raw  = (string) file_get_contents( $landing_path );
	$landing_code = (string) bhp_pt_strip_php_comments( $landing_raw );

	bhp_pt_assert(
		false !== strpos( $landing_code, '<p class="bhp-landing-panel__fine-print">Secure checkout</p>' ),
		'Fine print renders exactly "Secure checkout" — TLS is mechanically verifiable, so it stays',
		$failures
	);
	bhp_pt_assert(
		false === stripos( $landing_code, 'Tracking provided' ),
		'"Tracking provided" is GONE from the emitted markup',
		$failures
	);
	bhp_pt_assert(
		false !== stripos( $landing_raw, 'Secure checkout &middot; Tracking provided' ),
		'The superseded markup IS still preserved verbatim in a comment — history is not deleted, only the claim',
		$failures
	);
	bhp_pt_assert(
		false === stripos( $landing_code, 'shipped in the USA' ),
		'The unsourced country-of-origin claim removed on 2026-08-02 has not returned either',
		$failures
	);
}

echo "\n===== 3. The historic carrier phrasings are dead in shipped code =====\n";

$dead_phrasings = array(
	'Tracking provided',
	'tracking number',
	'with tracking',
	'tracked shipping',
	'tracking included',
	'tracking information',
	'track your order',
	'track your package',
);
foreach ( $dead_phrasings as $phrase ) {
	$found = array();
	foreach ( $php_files as $path ) {
		$code = bhp_pt_strip_php_comments( (string) file_get_contents( $path ) );
		if ( null !== $code && false !== stripos( $code, $phrase ) ) {
			$found[] = str_replace( $plugin_dir, '', $path );
		}
	}
	bhp_pt_assert( empty( $found ), "Phrase absent from shipped code: \"{$phrase}\"" . ( $found ? ' (found in ' . implode( ', ', $found ) . ')' : '' ), $failures );
}

echo "\n===== 4. The honest internal statement SURVIVES =====\n";

$dashboard_path = $plugin_dir . '/includes/dashboard/class-bhp-dashboard-page.php';
if ( file_exists( $dashboard_path ) ) {
	$dash_code = (string) bhp_pt_strip_php_comments( (string) file_get_contents( $dashboard_path ) );
	bhp_pt_assert(
		false !== stripos( $dash_code, 'no Bookvault tracking webhook or API integration exists yet' ),
		'Admin dashboard still tells the operator there is NO Bookvault tracking integration',
		$failures
	);
	bhp_pt_assert(
		false !== stripos( $dash_code, 'check the Bookvault portal directly' ),
		'Admin dashboard still names the real place to look for shipment status',
		$failures
	);
} else {
	bhp_pt_skip( 'Dashboard page file not present — cannot assert the honest internal statement', $skipped );
}

echo "\n===== 5. The detector is PROVED against a reintroduced defect =====\n";

$reintroduced = '<?php ?><p class="bhp-landing-panel__fine-print">Secure checkout &middot; Tracking provided</p>';
$probe_hits   = bhp_pt_unallowed_hits( (string) bhp_pt_strip_php_comments( $reintroduced ) );
bhp_pt_assert(
	! empty( $probe_hits ),
	'Detector FLAGS the exact removed markup when it is fed back in (a green suite that cannot fail is not a suite)',
	$failures
);

$commented_out = "<?php\n/* <p>Secure checkout &middot; Tracking provided</p> */\n";
$probe2        = bhp_pt_unallowed_hits( (string) bhp_pt_strip_php_comments( $commented_out ) );
bhp_pt_assert(
	empty( $probe2 ),
	'Detector does NOT flag the same text inside a PHP comment — comments are allowed to record history',
	$failures
);

$analytics_probe = "<?php wp_enqueue_script( 'bhp-list-tracking', 'assets/bhp-list-tracking.js' );\n";
$probe3          = bhp_pt_unallowed_hits( (string) bhp_pt_strip_php_comments( $analytics_probe ) );
bhp_pt_assert(
	empty( $probe3 ),
	'Detector does NOT flag the allowlisted analytics identifier',
	$failures
);

echo "\n===== 6. Stylesheets cannot print the word either =====\n";

$css_offenders = array();
foreach ( glob( $plugin_dir . '/assets/*.css' ) as $css ) {
	$body = (string) file_get_contents( $css );
	// Only `content:` can put text on screen from a stylesheet.
	if ( preg_match_all( '/content\s*:\s*[^;}]*/i', $body, $cm ) ) {
		foreach ( $cm[0] as $decl ) {
			if ( false !== stripos( $decl, 'tracking' ) ) {
				$css_offenders[] = basename( $css ) . ' :: ' . preg_replace( '/\s+/', ' ', $decl );
			}
		}
	}
}
foreach ( $css_offenders as $o ) {
	echo "  OFFENDER: {$o}\n";
}
bhp_pt_assert( empty( $css_offenders ), 'No CSS `content:` declaration prints "tracking"', $failures );

echo "\n===== 7. Version stamps are in step =====\n";

$main_path = $plugin_dir . '/brave-hearts-bundle-pricing.php';
if ( file_exists( $main_path ) ) {
	$main     = (string) file_get_contents( $main_path );
	$header_v = preg_match( '/^\s*\*\s*Version:\s*([0-9.]+)\s*$/m', $main, $hm ) ? $hm[1] : '(none)';
	$const_v  = preg_match( "/define\(\s*'BHP_BUNDLE_PRICING_VERSION',\s*'([0-9.]+)'\s*\)/", $main, $cm2 ) ? $cm2[1] : '(none)';
	echo "  header: {$header_v}   constant: {$const_v}\n";
	bhp_pt_assert(
		$header_v === $const_v && '(none)' !== $header_v,
		"Version: header and BHP_BUNDLE_PRICING_VERSION agree ({$header_v} / {$const_v}) — a stale constant ships the fix to the server and to nobody's browser",
		$failures
	);
} else {
	bhp_pt_skip( 'Main plugin file not found — cannot check version stamps', $skipped );
}

echo "\n==================================================\n";
printf( "RESULT: %d failed, %d skipped\n", count( $failures ), count( $skipped ) );
foreach ( $failures as $f ) {
	echo "  FAILED: {$f}\n";
}
foreach ( $skipped as $s ) {
	echo "  SKIPPED: {$s}\n";
}
if ( $failures ) {
	echo "SUITE: FAIL\n";
} else {
	echo "SUITE: PASS\n";
}
