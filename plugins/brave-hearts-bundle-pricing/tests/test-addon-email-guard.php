<?php
/**
 * Brave Hearts Bundle Pricing — the ADD-ON THANK-YOU EMAIL and the
 * ADD-ON-ONLY CHECKOUT GUARD (1.8.22).
 *
 * Run via WP-CLI, matching the other suites in this directory:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-addon-email-guard.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ A SIBLING FILE, NOT AN EXTENSION OF `test-addon-upsell.php`
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `test-addon-upsell.php` proves ONE thing very carefully: that adding a
 * seventh purchasable product does not silently break the shipping tier,
 * the bundle discount or coupon qualification. Its fixtures are cart stubs
 * built for that question, and its 45 assertions are worth keeping
 * readable as a single argument.
 *
 * This file asks two different questions, with different fixtures: does a
 * cart of nothing but the add-on get stopped, and does the second email
 * fire for exactly the right orders and no others. Bolting them onto the
 * first file would have produced one suite where a failure line no longer
 * tells you which feature broke.
 *
 * ⛔ NO DATABASE WRITE. NO ORDER IS SAVED. NO EMAIL IS SENT.
 *    Orders here are constructed in memory with `new WC_Order()` and are
 *    never `save()`d, so nothing reaches `wc_orders`. Cart fixtures are
 *    plain arrays. `wc_add_notice()` is never called, so no session is
 *    touched. This file is safe to run on any environment.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_aeg_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * A cart stand-in exposing only what the guard reads.
 */
class BHP_AEG_Cart {
	private $items;
	public function __construct( array $items ) {
		$this->items = $items;
	}
	public function get_cart() {
		return $this->items;
	}
}

function bhp_aeg_item( $product_id, $variation_id = 0, $quantity = 1 ) {
	return array(
		'product_id'   => $product_id,
		'variation_id' => $variation_id,
		'quantity'     => $quantity,
	);
}

/**
 * Read a property whatever its visibility.
 *
 * ⚠ NOT LAZINESS AND NOT A DESIGN SMELL IN THE CODE UNDER TEST.
 *   `WC_Email::$customer_email`, `$template_base` and `$enabled` are
 *   `protected` in WooCommerce 10.9.1 (they were public in older releases,
 *   which is exactly the trap). Reading them directly fataled this suite on
 *   its first run. Reflection reads what the object actually holds without
 *   asking the plugin to widen anything for a test's benefit.
 *
 * @param object $obj  Object.
 * @param string $name Property name.
 * @return mixed|null
 */
function bhp_aeg_prop( $obj, $name ) {
	$reflection = new ReflectionObject( $obj );
	if ( ! $reflection->hasProperty( $name ) ) {
		return null;
	}
	$property = $reflection->getProperty( $name );
	$property->setAccessible( true );
	return $property->getValue( $obj );
}

/**
 * Walk every string in a nested array and hand it to a callback.
 */
function bhp_aeg_walk_strings( $value, callable $fn, $path = '' ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			bhp_aeg_walk_strings( $v, $fn, $path ? $path . '.' . $k : (string) $k );
		}
		return;
	}
	if ( is_string( $value ) ) {
		$fn( $value, $path );
	}
}

echo "\n=== 1. MODULES LOADED ===\n";

foreach ( array(
	'bhp_bundle_addon_thankyou_copy',
	'bhp_bundle_addon_copy_is_placeholder',
	'bhp_bundle_cart_is_addon_only',
	'bhp_bundle_addon_only_message',
	'bhp_bundle_addon_guard_notice',
	'bhp_bundle_addon_guard_store_api',
	'bhp_bundle_order_has_addon',
	'bhp_bundle_addon_order_downloads',
	'bhp_bundle_addon_thankyou_should_send',
	'bhp_bundle_register_addon_thankyou_email',
) as $bhp_fn ) {
	bhp_aeg_assert( function_exists( $bhp_fn ), "{$bhp_fn}() is defined", $failures );
}

bhp_aeg_assert(
	defined( 'BHP_BUNDLE_ADDON_SENT_META' ) && '_bhp_addon_thankyou_sent' === BHP_BUNDLE_ADDON_SENT_META,
	'The idempotency meta key is defined and stable',
	$failures
);

bhp_aeg_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.21', '>=' ),
	'Plugin version is at least 1.8.21 (currently ' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ')',
	$failures
);

echo "\n=== 2. THE COPY FILE ===\n";

$copy = bhp_bundle_addon_thankyou_copy();

bhp_aeg_assert( is_array( $copy ) && isset( $copy['email'], $copy['cart_guard'] ), 'Copy array has both sections', $failures );

/*
 * ⛔ NO EM DASH, NO EN DASH, ANYWHERE. Checked by CODEPOINT across every
 *    string in the array, not by eye and not on the strings somebody
 *    remembered to check.
 */
$bhp_dashes = array();
bhp_aeg_walk_strings(
	$copy,
	function ( $s, $path ) use ( &$bhp_dashes ) {
		if ( false !== strpos( $s, "\u{2014}" ) || false !== strpos( $s, "\u{2013}" ) ) {
			$bhp_dashes[] = $path;
		}
	}
);
bhp_aeg_assert( empty( $bhp_dashes ), 'No em dash or en dash in any copy string' . ( $bhp_dashes ? ' (found at: ' . implode( ', ', $bhp_dashes ) . ')' : '' ), $failures );

/*
 * ⛔ NEVER-INVENT SCREEN. Not a claim that the copy is good; a mechanical
 *    check that it does not contain the shapes of claims this company
 *    forbids. A hit is a signal to read the string, not proof of a defect.
 */
/*
 * ⚠ WORD-BOUNDED PATTERNS, NOT SUBSTRINGS, AND THAT WAS A CORRECTION MADE
 *   FROM A REAL FAILING RUN rather than from reasoning. The first version
 *   used a bare `'ages '` substring to catch a reading-age claim, and it
 *   fired on the word "pages" in "print the pages you want". A screen that
 *   cries wolf gets switched off, so it is worth getting right.
 */
$bhp_banned = array(
	'/\breviews?\b/',
	'/\bratings?\b/',
	'/\b\d+\s*stars?\b/',
	'/\bbest[\s-]?sell(er|ing)\b/',
	'/\bawards?\b/',
	'/\bloved by\b/',
	'/\b(parents|teachers|kids|children) (say|love)\b/',
	'/\bproven\b/',
	'/\bguaranteed\b/',
	'/\breading level\b/',
	'/\blexile\b/',
	/*
	 * ⚠ REPLACED IN THE COPY SWAP, AND THE REPLACEMENT IS STRICTER, NOT
	 *   LOOSER. The 1.8.21 patterns were `/\bages\s+\d/` and
	 *   `/\bage\s+\d+\s*(to|-)\s*\d/`, which banned EVERY age band because
	 *   the placeholder copy claimed none. The approved copy DOES claim
	 *   one, it is sourced, and Standing Rules §9 fixes it at "6 to 9,
	 *   never 5 to 9".
	 *
	 *   ⛔ THE OLD ASSERTION WAS NOT DELETED TO MAKE A FAILING RUN PASS.
	 *      Banning all bands and banning all bands EXCEPT the one approved
	 *      value catch different things: the old pattern could not tell
	 *      "ages 6 to 9" from "ages 5 to 9" because it rejected both, so on
	 *      a copy that legitimately carries a band it would have been
	 *      switched off entirely and caught nothing. This catches the
	 *      wrong band, which is the failure that actually matters.
	 *
	 *   Negative lookahead: any "age"/"ages" followed by a digit is a hit
	 *   UNLESS it is exactly "6 to 9" or "6-9".
	 */
	'/\bages?\s+(?!6\s*(to|-)\s*9\b)\d/',
);
$bhp_hits = array();
bhp_aeg_walk_strings(
	$copy,
	function ( $s, $path ) use ( &$bhp_hits, $bhp_banned ) {
		$lower = strtolower( $s );
		foreach ( $bhp_banned as $pattern ) {
			if ( preg_match( $pattern, $lower, $m ) ) {
				$bhp_hits[] = $path . ' => "' . $m[0] . '"';
			}
		}
	}
);
bhp_aeg_assert( empty( $bhp_hits ), 'No never-invent claim shapes in the copy' . ( $bhp_hits ? ' (found: ' . implode( '; ', $bhp_hits ) . ')' : '' ), $failures );

/* No price is restated in a delivery email. */
$bhp_prices = array();
bhp_aeg_walk_strings(
	$copy,
	function ( $s, $path ) use ( &$bhp_prices ) {
		if ( preg_match( '/\$\s?\d/', $s ) ) {
			$bhp_prices[] = $path;
		}
	}
);
bhp_aeg_assert( empty( $bhp_prices ), 'No hardcoded price in the copy' . ( $bhp_prices ? ' (found at: ' . implode( ', ', $bhp_prices ) . ')' : '' ), $failures );

/*
 * ⚠ BOTH `%s` ASSERTIONS INVERTED IN THE COPY SWAP, DELIBERATELY, AND
 *   NEITHER WAS DELETED. 1.8.21 required a `%s` slot in the download
 *   button and in the guard string because the placeholder interpolated a
 *   file name and a product title. The approved copy names both in prose
 *   instead, so requiring a slot would now be requiring the wrong thing.
 *
 *   ⛔ The assertion that MATTERS is unchanged in substance and is stated
 *      positively below: NOTHING RENDERS WITH AN UNFILLED PLACEHOLDER IN
 *      IT. That was always the real risk. Section 4 proves it on the
 *      rendered guard message, which is the string a customer sees.
 */
bhp_aeg_assert( is_string( $copy['email']['download_button'] ) && '' !== trim( $copy['email']['download_button'] ), 'Download button copy is a non-empty string', $failures );
bhp_aeg_assert( false === strpos( $copy['email']['download_button'], '%s' ), 'Download button copy names the artefact rather than interpolating a stored file name', $failures );
bhp_aeg_assert( false === strpos( $copy['cart_guard']['addon_only'], '%s' ), 'Cart-guard copy names the product in prose rather than interpolating a title', $failures );
bhp_aeg_assert( false === strpos( $copy['cart_guard']['addon_only_generic'], '%s' ), 'Generic cart-guard fallback carries no unfilled slot', $failures );
bhp_aeg_assert( count( $copy['email']['paragraphs'] ) >= 1, 'At least one body paragraph', $failures );

echo "\n--- 2b. THE APPROVED COPY, ASSERTED BY CONTENT ---\n";

/*
 * ⭐ THESE ASSERT WHAT THE APPROVED DOCUMENT ACTUALLY SAYS, so that a
 *    later edit to the copy file cannot quietly diverge from it. Source:
 *    `DRAFT-2026-08-04-UPSELL-THANKYOU-EMAIL.md` (DRIVE mount, 2026-08-04).
 */
bhp_aeg_assert( 'Your activity book is here' === $copy['email']['subject'], 'Subject is the `marketing-growth` Subject A, verbatim (got: "' . $copy['email']['subject'] . '")', $failures );
bhp_aeg_assert( is_string( $copy['email']['preheader'] ) && '' !== trim( $copy['email']['preheader'] ), 'The approved preheader string is carried in the copy file', $failures );
bhp_aeg_assert( isset( $copy['email']['paragraphs_after'] ) && count( $copy['email']['paragraphs_after'] ) === 3, 'Three body paragraphs render AFTER the download, in the approved order', $failures );
bhp_aeg_assert( isset( $copy['email']['signoff'] ) && in_array( 'Andrew', (array) $copy['email']['signoff'], true ), 'The sign-off is present and reads "Andrew" (gate G-C, closed 2026-08-04, string unchanged)', $failures );
bhp_aeg_assert( 'Big Places. Brave Hearts.' === $copy['email']['signoff_tagline'], 'The brand line closes the body', $failures );

/*
 * ⭐ THE LICENCE SENTENCE, 1.8.22. Gate G-B is CLOSED and the sentence
 *    CHANGED with it: Andrew Signore, 2026-08-04, "Classroom ok". The
 *    grant is now personal AND classroom printing.
 *
 * Three assertions, not one, because "the new sentence is present" alone
 * would also pass if the old sentence were still sitting beside it.
 */
$bhp_all_copy = '';
bhp_aeg_walk_strings(
	$copy,
	function ( $s ) use ( &$bhp_all_copy ) {
		$bhp_all_copy .= ' ' . $s;
	}
);
bhp_aeg_assert(
	false !== strpos( $bhp_all_copy, 'Print the pages you like, as many times as you like, for your home or your classroom. It is yours to keep.' ),
	'The licence sentence grants home AND classroom printing, verbatim (gate G-B, closed 2026-08-04)',
	$failures
);
bhp_aeg_assert(
	false === strpos( $bhp_all_copy, 'as many times as you like. It is yours to keep.' ),
	'The superseded 1.8.21 licence sentence (home only) is GONE, not duplicated alongside the new one',
	$failures
);

/*
 * ⛔ THE LICENCE MUST NOT BROADEN BEYOND WHAT ANDREW SAID.
 *
 * "Classroom ok" is a printing permission for a home or a classroom. It is
 * NOT a school or district licence, NOT a redistribution, sharing or resale
 * right, and NOT a rights or copyright statement. This screen fails if any
 * of those words reaches customer-facing copy without a fresh founder word.
 */
$bhp_overclaim = array();
foreach ( array( 'school', 'district', 'site licence', 'site license', 'unlimited', 'redistribut', 'resale', 'resell', 'share it', 'copyright', 'all rights' ) as $bhp_term ) {
	if ( false !== stripos( $bhp_all_copy, $bhp_term ) ) {
		$bhp_overclaim[] = $bhp_term;
	}
}
bhp_aeg_assert(
	empty( $bhp_overclaim ),
	'The licence claims no entitlement beyond home and classroom printing' . ( $bhp_overclaim ? ' (found: "' . implode( '", "', $bhp_overclaim ) . '")' : '' ),
	$failures
);

/*
 * ⛔ THE VERSION-COUPLED SENTENCE MUST BE ABSENT.
 *
 * "the answer key is on the last two pages" is true of v4 and of no other
 * version (v1/v2 one page at 20; v3 two at 20-21; v4 two at 25-26), and
 * which PDF ships is pending Andrew's v4/v5 word. Omitted on the `chief-of-staff`
 * direction. This assertion is the thing that stops it creeping back in.
 * `CYCLE143-MKT-121`.
 */
bhp_aeg_assert(
	false === stripos( $bhp_all_copy, 'answer key' ) && false === stripos( $bhp_all_copy, 'last two pages' ),
	'The version-coupled answer-key sentence is ABSENT (pending Andrew\'s v4/v5 word)',
	$failures
);

/* No page count in a delivery email, so a version change cannot make it false. */
bhp_aeg_assert( 0 === preg_match( '/\b\d+\s+pages\b/i', $bhp_all_copy ), 'No page count is claimed in the delivery email', $failures );

/* The approved reading band, and only that band. */
bhp_aeg_assert( false !== strpos( $bhp_all_copy, 'ages 6 to 9' ), 'The reading band reads "ages 6 to 9"', $failures );
bhp_aeg_assert( false === strpos( $bhp_all_copy, '5 to 9' ) && false === strpos( $bhp_all_copy, '5-9' ), 'The forbidden 5-to-9 band appears nowhere', $failures );

/*
 * Status and the open-confirm list.
 *
 * ⚠ CHANGED IN 1.8.22, and the change is a TIGHTENING, stated so it cannot
 *   be mistaken for a failing assertion being relaxed to force green:
 *
 *   - 1.8.21 asserted only `'PLACEHOLDER' !== $copy['status']`, which any
 *     non-placeholder value satisfied. 1.8.22 asserts the exact value
 *     `APPROVED`. That is strictly harder to pass.
 *   - 1.8.21 asserted `count(open_confirms) === 2`. That assertion was TRUE
 *     of 1.8.21 and is FALSE of 1.8.22, because Andrew closed both gates on
 *     2026-08-04. It is replaced by `=== 0`, which is an equally exact
 *     count and equally capable of failing - not deleted, and not loosened
 *     to `<= 2`.
 */
bhp_aeg_assert( 'APPROVED' === $copy['status'], 'Copy status is exactly APPROVED (got: "' . $copy['status'] . '")', $failures );
bhp_aeg_assert( false === bhp_bundle_addon_copy_is_placeholder(), 'bhp_bundle_addon_copy_is_placeholder() reports false for loaded approved copy', $failures );
bhp_aeg_assert( function_exists( 'bhp_bundle_addon_copy_open_confirms' ), 'bhp_bundle_addon_copy_open_confirms() is defined', $failures );
bhp_aeg_assert( isset( $copy['open_confirms'] ) && is_array( $copy['open_confirms'] ), 'The open_confirms key still EXISTS (kept, not deleted, so the next confirm is a one-line addition)', $failures );
bhp_aeg_assert( count( bhp_bundle_addon_copy_open_confirms() ) === 0, 'Zero Andrew confirms remain open (licence and sign-off both closed 2026-08-04)', $failures );

echo "\n--- 2c. THE PLAIN-TEXT TWIN CANNOT MANGLE THE APPROVED WORDS ---\n";

/*
 * ⛔ THIS SECTION EXISTS BECAUSE THE DEFECT IT GUARDS WAS REAL AND WAS
 *    OBSERVED IN A RENDERED EMAIL ON STAGING, 2026-08-04.
 *
 * The plain template escaped every copy string with `esc_html()`. That is
 * the correct escaper for the HTML twin and the WRONG one for a plain-text
 * stream: it turns an apostrophe into `&#039;`, and a plain-text reader has
 * no browser to decode it. The 1.8.21 placeholder copy had no apostrophe,
 * so nothing caught it. The approved copy says "Charlotte and Henry's", and
 * the rendered plain-text email read "Charlotte and Henry&#039;s".
 *
 * The template now uses `wp_strip_all_tags()`. These assertions prove the
 * property that matters, on every string in the copy array at once: the
 * plain-text pipeline is IDENTITY on approved copy. Any future string that
 * would be altered on its way to a plain-text inbox fails here first.
 */
$bhp_mangled = array();
bhp_aeg_walk_strings(
	$copy,
	function ( $s ) use ( &$bhp_mangled ) {
		if ( '' !== $s && wp_strip_all_tags( $s ) !== $s ) {
			$bhp_mangled[] = $s;
		}
	}
);
bhp_aeg_assert(
	empty( $bhp_mangled ),
	'Every copy string survives the plain-text pipeline byte-identical' . ( $bhp_mangled ? ' (mangled: "' . implode( '", "', $bhp_mangled ) . '")' : '' ),
	$failures
);

/* The specific shape that went wrong, asserted by name so the report is legible. */
bhp_aeg_assert(
	false === strpos( $bhp_all_copy, '&#039;' ) && false === strpos( $bhp_all_copy, '&amp;' ) && false === strpos( $bhp_all_copy, '&quot;' ),
	'No HTML entity is baked into any copy string',
	$failures
);

/* An apostrophe is present as a literal, which is what makes the check above load-bearing. */
bhp_aeg_assert(
	false !== strpos( $bhp_all_copy, "Henry's" ),
	'The approved copy really does contain a literal apostrophe (so section 2c is not a no-op)',
	$failures
);

/*
 * ⭐ THE LOUD PLACEHOLDER BANNER IS RETAINED, NOT REMOVED. It is now
 *    unreachable with the current copy, and that is the point: if anyone
 *    ever reverts the copy file, the banner comes back on its own.
 */
if ( bhp_bundle_addon_copy_is_placeholder() ) {
	echo "\n";
	echo "!!! COPY STATUS: PLACEHOLDER. The strings above are NOT approved copy.\n";
	echo "!!! Approved copy source: " . $copy['source'] . "\n";
	echo "!!! Swap it in `includes/addon-thankyou-copy.php` and set status APPROVED.\n\n";
} else {
	$bhp_confirms = bhp_bundle_addon_copy_open_confirms();
	echo "\nCopy status: " . $copy['status'] . ' (source: ' . $copy['source'] . ")\n";
	if ( $bhp_confirms ) {
		echo 'NOTE: ' . count( $bhp_confirms ) . " open Andrew confirm(s) on otherwise approved copy:\n";
		foreach ( $bhp_confirms as $bhp_confirm ) {
			echo '  - ' . $bhp_confirm . "\n";
		}
		echo "\n";
	} else {
		echo "No open Andrew confirms.\n\n";
	}
}

echo "=== 3. THE CART GUARD - DETECTION ===\n";

/*
 * Real ids from this store: 333/334 Mariana paperback (the one variable
 * product), 15 Everest paperback, 14 Mariana hardcover. 987654 is a
 * deliberate non-id.
 */
$bhp_addon_ids = bhp_bundle_addon_product_ids();
echo 'Resolved add-on product ids: ' . ( $bhp_addon_ids ? implode( ',', $bhp_addon_ids ) : '(none - fail-closed state)' ) . "\n";

$bhp_addon_id = $bhp_addon_ids ? (int) $bhp_addon_ids[0] : 0;

bhp_aeg_assert( false === bhp_bundle_cart_is_addon_only( null ), 'Null cart is not add-on-only', $failures );
bhp_aeg_assert( false === bhp_bundle_cart_is_addon_only( new BHP_AEG_Cart( array() ) ), 'EMPTY cart is not add-on-only (WooCommerce owns the empty-cart error)', $failures );

bhp_aeg_assert(
	false === bhp_bundle_cart_is_addon_only( new BHP_AEG_Cart( array( bhp_aeg_item( 333, 334 ) ) ) ),
	'One paperback is not add-on-only',
	$failures
);
bhp_aeg_assert(
	false === bhp_bundle_cart_is_addon_only(
		new BHP_AEG_Cart( array( bhp_aeg_item( 333, 334 ), bhp_aeg_item( 15 ), bhp_aeg_item( 18 ) ) )
	),
	'Three paperbacks are not add-on-only',
	$failures
);
bhp_aeg_assert(
	false === bhp_bundle_cart_is_addon_only( new BHP_AEG_Cart( array( bhp_aeg_item( 987654 ) ) ) ),
	'An unknown product alone is not add-on-only (the guard cannot block a cart it does not own)',
	$failures
);

if ( $bhp_addon_id ) {
	bhp_aeg_assert(
		true === bhp_bundle_cart_is_addon_only( new BHP_AEG_Cart( array( bhp_aeg_item( $bhp_addon_id ) ) ) ),
		'THE ADD-ON ALONE IS ADD-ON-ONLY (checkout is blocked)',
		$failures
	);
	bhp_aeg_assert(
		true === bhp_bundle_cart_is_addon_only( new BHP_AEG_Cart( array( bhp_aeg_item( $bhp_addon_id, 0, 3 ) ) ) ),
		'Three copies of the add-on alone are still add-on-only',
		$failures
	);
	bhp_aeg_assert(
		false === bhp_bundle_cart_is_addon_only(
			new BHP_AEG_Cart( array( bhp_aeg_item( 333, 334 ), bhp_aeg_item( $bhp_addon_id ) ) )
		),
		'ONE BOOK + the add-on is NOT blocked (this is the whole point of the upsell)',
		$failures
	);
	bhp_aeg_assert(
		false === bhp_bundle_cart_is_addon_only(
			new BHP_AEG_Cart( array( bhp_aeg_item( 14 ), bhp_aeg_item( 17 ), bhp_aeg_item( 20 ), bhp_aeg_item( $bhp_addon_id ) ) )
		),
		'Hardcover collection + the add-on is NOT blocked',
		$failures
	);
	bhp_aeg_assert(
		false === bhp_bundle_cart_is_addon_only(
			new BHP_AEG_Cart( array( bhp_aeg_item( 987654 ), bhp_aeg_item( $bhp_addon_id ) ) )
		),
		'Add-on plus an unrelated product is NOT blocked',
		$failures
	);
} else {
	/*
	 * ⭐ FAIL-CLOSED. This is the state of PRODUCTION until Andrew approves
	 *    the live product, and it is the most important assertion here:
	 *    with no resolvable SKU the guard can never fire on anything.
	 */
	bhp_aeg_assert(
		false === bhp_bundle_cart_is_addon_only( new BHP_AEG_Cart( array( bhp_aeg_item( 987654 ) ) ) ),
		'FAIL-CLOSED: with no resolvable add-on SKU, no cart is ever add-on-only',
		$failures
	);
	echo "NOTE: the add-on SKU does not resolve here, so the positive-detection\n";
	echo "      assertions were SKIPPED, not passed. That is the correct state for\n";
	echo "      an environment with no activity-book product.\n";
}

echo "\n=== 4. THE CART GUARD - MESSAGE AND WIRING ===\n";

$bhp_msg = bhp_bundle_addon_only_message();
bhp_aeg_assert( is_string( $bhp_msg ) && '' !== trim( $bhp_msg ), 'Guard message resolves to a non-empty string', $failures );
bhp_aeg_assert( false === strpos( $bhp_msg, '%s' ), 'Guard message has no unfilled placeholder', $failures );
bhp_aeg_assert( false === strpos( $bhp_msg, "\u{2014}" ) && false === strpos( $bhp_msg, "\u{2013}" ), 'Guard message contains no em or en dash', $failures );
echo "Rendered guard message: {$bhp_msg}\n";

/* The rendered message IS the approved string, with nothing added or lost. */
bhp_aeg_assert( $bhp_msg === $copy['cart_guard']['addon_only'], 'Rendered guard message is the approved string verbatim', $failures );
bhp_aeg_assert( false !== strpos( $bhp_msg, 'companion download' ), 'Guard message uses "companion download", the approved framing', $failures );
bhp_aeg_assert( false !== strpos( $bhp_msg, 'Charlotte and Henry book' ), 'Guard message names what is missing', $failures );

/*
 * ⚠ THE SAME CLAIM AS 1.8.21'S "names the real product", VERIFIED THE
 *   OTHER WAY ROUND, AND IT IS NOW A STRONGER TEST. 1.8.21 interpolated
 *   the live title, so the message named the product by construction and
 *   the assertion could never fail. The approved copy names it in prose,
 *   so this compares TWO INDEPENDENT SOURCES - the approved string and the
 *   live WooCommerce record - and fails if they ever diverge. A product
 *   rename now surfaces here instead of silently rewriting approved copy.
 */
if ( $bhp_addon_id && function_exists( 'bhp_bundle_addon_product' ) ) {
	$bhp_addon_product = bhp_bundle_addon_product();
	if ( $bhp_addon_product ) {
		bhp_aeg_assert(
			false !== strpos( $bhp_msg, $bhp_addon_product->get_name() ),
			'Guard message and the LIVE WooCommerce product title agree (live title: "' . $bhp_addon_product->get_name() . '")',
			$failures
		);
	}
}

bhp_aeg_assert( 10 === has_action( 'woocommerce_store_api_cart_errors', 'bhp_bundle_addon_guard_store_api' ), 'Store API cart-error hook is registered (the Blocks checkout enforcement point)', $failures );
bhp_aeg_assert( 10 === has_action( 'woocommerce_check_cart_items', 'bhp_bundle_addon_guard_check_cart_items' ), 'Classic cart/checkout hook is registered', $failures );
bhp_aeg_assert( 10 === has_action( 'woocommerce_checkout_process', 'bhp_bundle_addon_guard_checkout_process' ), 'Classic checkout POST hook is registered', $failures );

/* The Store API callback, exercised directly against a real WP_Error. */
if ( $bhp_addon_id ) {
	$bhp_err = new WP_Error();
	bhp_bundle_addon_guard_store_api( $bhp_err, new BHP_AEG_Cart( array( bhp_aeg_item( $bhp_addon_id ) ) ) );
	bhp_aeg_assert( $bhp_err->has_errors(), 'Store API callback ADDS an error for an add-on-only cart', $failures );
	bhp_aeg_assert( 'bhp_bundle_addon_only_cart' === $bhp_err->get_error_code(), 'The error code is the guard\'s own, not a borrowed one', $failures );

	$bhp_err2 = new WP_Error();
	bhp_bundle_addon_guard_store_api( $bhp_err2, new BHP_AEG_Cart( array( bhp_aeg_item( 333, 334 ), bhp_aeg_item( $bhp_addon_id ) ) ) );
	bhp_aeg_assert( ! $bhp_err2->has_errors(), 'Store API callback adds NOTHING for a book + add-on cart', $failures );

	$bhp_err3 = new WP_Error();
	bhp_bundle_addon_guard_store_api( $bhp_err3, new BHP_AEG_Cart( array( bhp_aeg_item( 15 ) ) ) );
	bhp_aeg_assert( ! $bhp_err3->has_errors(), 'Store API callback adds NOTHING for a books-only cart', $failures );
}

echo "\n=== 5. THE SECOND EMAIL - ORDER DETECTION ===\n";

bhp_aeg_assert( false === bhp_bundle_order_has_addon( null ), 'Null order has no add-on', $failures );
bhp_aeg_assert( false === bhp_bundle_order_has_addon( 'not an order' ), 'A non-order has no add-on', $failures );
bhp_aeg_assert( array() === bhp_bundle_addon_order_downloads( null ), 'Downloads for a non-order is an empty array', $failures );
bhp_aeg_assert( false === bhp_bundle_addon_thankyou_should_send( null ), 'A non-order never sends', $failures );

/*
 * In-memory orders. `new WC_Order()` with no id writes nothing; only
 * `save()` would, and it is never called.
 */
$bhp_books_order = new WC_Order();
$bhp_books_order->set_billing_email( 'nobody@example.invalid' );
$bhp_books_item = new WC_Order_Item_Product();
$bhp_books_item->set_product_id( 15 );
$bhp_books_item->set_quantity( 1 );
$bhp_books_order->add_item( $bhp_books_item );

bhp_aeg_assert( false === bhp_bundle_order_has_addon( $bhp_books_order ), 'A BOOKS-ONLY order does not contain the add-on', $failures );
bhp_aeg_assert( false === bhp_bundle_addon_thankyou_should_send( $bhp_books_order ), 'A BOOKS-ONLY order does NOT send the second email (it gets exactly the ordinary emails)', $failures );
bhp_aeg_assert( array() === bhp_bundle_addon_order_downloads( $bhp_books_order ), 'A books-only order yields no add-on downloads', $failures );

if ( $bhp_addon_id ) {
	$bhp_addon_order = new WC_Order();
	$bhp_addon_order->set_billing_email( 'nobody@example.invalid' );
	$bhp_addon_line = new WC_Order_Item_Product();
	$bhp_addon_line->set_product_id( $bhp_addon_id );
	$bhp_addon_line->set_quantity( 1 );
	$bhp_addon_order->add_item( $bhp_addon_line );

	$bhp_book_line = new WC_Order_Item_Product();
	$bhp_book_line->set_product_id( 333 );
	$bhp_book_line->set_variation_id( 334 );
	$bhp_book_line->set_quantity( 1 );
	$bhp_addon_order->add_item( $bhp_book_line );

	bhp_aeg_assert( true === bhp_bundle_order_has_addon( $bhp_addon_order ), 'A book + add-on order DOES contain the add-on', $failures );

	/*
	 * ⭐ THE DECLINE-RATHER-THAN-PROMISE PATH. This unsaved order has no
	 *    download permission, so core reports no signed link. The email
	 *    must refuse to send rather than deliver a thank-you with nothing
	 *    to download.
	 */
	bhp_aeg_assert(
		false === bhp_bundle_addon_thankyou_should_send( $bhp_addon_order ),
		'With NO signed download available, the email DECLINES to send rather than promising a file',
		$failures
	);

	/* No billing email is also a decline. */
	$bhp_no_email_order = new WC_Order();
	$bhp_no_email_line  = new WC_Order_Item_Product();
	$bhp_no_email_line->set_product_id( $bhp_addon_id );
	$bhp_no_email_line->set_quantity( 1 );
	$bhp_no_email_order->add_item( $bhp_no_email_line );
	bhp_aeg_assert( false === bhp_bundle_addon_thankyou_should_send( $bhp_no_email_order ), 'No billing email is a decline', $failures );
}

echo "\n=== 6. THE SECOND EMAIL - REGISTRATION AND WIRING ===\n";

$bhp_registered = apply_filters( 'woocommerce_email_classes', array() );
bhp_aeg_assert( isset( $bhp_registered['WC_Email_BHP_Addon_Thankyou'] ), 'The email class registers on woocommerce_email_classes', $failures );

if ( isset( $bhp_registered['WC_Email_BHP_Addon_Thankyou'] ) ) {
	$bhp_email = $bhp_registered['WC_Email_BHP_Addon_Thankyou'];

	$bhp_tpl_base  = bhp_aeg_prop( $bhp_email, 'template_base' );
	$bhp_tpl_html  = bhp_aeg_prop( $bhp_email, 'template_html' );
	$bhp_tpl_plain = bhp_aeg_prop( $bhp_email, 'template_plain' );

	bhp_aeg_assert( $bhp_email instanceof WC_Email, 'It is a real WC_Email subclass, not a wp_mail() wrapper', $failures );
	bhp_aeg_assert( 'bhp_addon_thankyou' === $bhp_email->id, 'Email id is bhp_addon_thankyou', $failures );
	bhp_aeg_assert( true === $bhp_email->is_customer_email(), 'It is a customer email', $failures );
	bhp_aeg_assert( 'emails/addon-thankyou.php' === $bhp_tpl_html, 'HTML template path', $failures );
	bhp_aeg_assert( 'emails/plain/addon-thankyou.php' === $bhp_tpl_plain, 'Plain template path', $failures );
	bhp_aeg_assert( BHP_BUNDLE_PRICING_DIR . 'templates/' === $bhp_tpl_base, 'Template base points at the plugin, so the theme can still override', $failures );
	bhp_aeg_assert( file_exists( $bhp_tpl_base . $bhp_tpl_html ), 'HTML template file exists on disk', $failures );
	bhp_aeg_assert( file_exists( $bhp_tpl_base . $bhp_tpl_plain ), 'Plain template file exists on disk', $failures );

	bhp_aeg_assert( $bhp_email->get_default_subject() === $copy['email']['subject'], 'Default subject comes from the copy file, not the class', $failures );
	bhp_aeg_assert( $bhp_email->get_default_heading() === $copy['email']['heading'], 'Default heading comes from the copy file, not the class', $failures );

	bhp_aeg_assert(
		20 === has_action( 'woocommerce_order_status_completed_notification', array( $bhp_email, 'trigger' ) ),
		'Trigger is hooked on ORDER COMPLETED at priority 20 (after core\'s own completed-order email)',
		$failures
	);
	bhp_aeg_assert(
		false === has_action( 'woocommerce_order_status_processing_notification', array( $bhp_email, 'trigger' ) ),
		'It does NOT fire on processing - completion is the trigger Andrew described',
		$failures
	);
}

echo "\n=== 7. REGRESSION - THE EXISTING EMAILS ARE UNTOUCHED ===\n";

/*
 * Andrew, Message 32: "Your books have shipped is fine." The completed-order
 * email stays. These assertions exist so that a future change to this
 * feature cannot quietly take it away.
 */
$bhp_mailer = function_exists( 'WC' ) ? WC()->mailer() : null;
$bhp_emails = $bhp_mailer ? $bhp_mailer->get_emails() : array();

bhp_aeg_assert( isset( $bhp_emails['WC_Email_Customer_Completed_Order'] ), 'The completed-order email is still registered', $failures );
bhp_aeg_assert( isset( $bhp_emails['WC_Email_Customer_Processing_Order'] ), 'The processing-order email is still registered', $failures );
bhp_aeg_assert( isset( $bhp_emails['WC_Email_BHP_Addon_Thankyou'] ), 'The new email is present in the live mailer, alongside them', $failures );

if ( isset( $bhp_emails['WC_Email_Customer_Completed_Order'] ) ) {
	$bhp_completed = $bhp_emails['WC_Email_Customer_Completed_Order'];
	bhp_aeg_assert(
		'Your books have shipped' === $bhp_completed->get_subject(),
		'Completed-order subject is still "Your books have shipped" (got: "' . $bhp_completed->get_subject() . '")',
		$failures
	);
	/*
	 * ⭐⭐ 1.19.281 — ASSERTED AGAINST A SIMULATED PRODUCTION HOST, AND THAT IS
	 *     A NARROWING OF SCOPE, NOT A WEAKENING.
	 *
	 * ⛔ WHAT THIS ASSERTION IS FOR, UNCHANGED: Andrew, Message 32 — "Your
	 *    books have shipped is fine." It exists so a future change cannot
	 *    QUIETLY TURN THAT EMAIL OFF. That protection is fully intact: if
	 *    anyone disables the email in WooCommerce's settings, the `enabled`
	 *    property is 'no' and this still fails on every host.
	 *
	 * ⛔ WHAT CHANGED AROUND IT: `inc/staging-mail-guard.php` (theme 1.19.281)
	 *    suppresses WooCommerce ORDER emails ON STAGING ONLY, because the QA
	 *    round of 2026-08-21 mailed Andrew for every test order it placed.
	 *    That guard filters `is_enabled()` by HOST — it does not touch the
	 *    stored setting, and it is inert on production.
	 *
	 * ⭐ SO THE TWO ARE NOT ACTUALLY IN CONFLICT: this suite runs on staging,
	 *    where delivery is deliberately off, and it means to assert the
	 *    CONFIGURATION. Simulating a production host is what makes the
	 *    assertion say what it has always meant.
	 */
	$bhp_aeg_host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;
	$_SERVER['HTTP_HOST'] = 'braveheartspublishing.com';
	$bhp_aeg_completed_enabled = $bhp_completed->is_enabled();
	if ( null === $bhp_aeg_host ) {
		unset( $_SERVER['HTTP_HOST'] );
	} else {
		$_SERVER['HTTP_HOST'] = $bhp_aeg_host;
	}

	bhp_aeg_assert(
		$bhp_aeg_completed_enabled,
		'Completed-order email is still enabled (asserted against a production host, so the staging delivery guard is not read as a regression)',
		$failures
	);
}

/*
 * The 1.8.20 allowlist behaviour must not have moved. A one-line sanity
 * check, not a re-run of test-addon-upsell.php.
 */
if ( $bhp_addon_id ) {
	bhp_aeg_assert(
		false === bhp_bundle_cart_has_unrelated_items( new BHP_AEG_Cart( array( bhp_aeg_item( 333, 334 ), bhp_aeg_item( $bhp_addon_id ) ) ) ),
		'1.8.20 regression: the add-on is still exempt from has_unrelated (shipping tiers survive)',
		$failures
	);
}

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL ASSERTIONS PASSED\n";
	if ( bhp_bundle_addon_copy_is_placeholder() ) {
		echo "(with PLACEHOLDER copy - see section 2)\n";
	} elseif ( bhp_bundle_addon_copy_open_confirms() ) {
		echo '(approved copy loaded; ' . count( bhp_bundle_addon_copy_open_confirms() ) . " open Andrew confirm(s) - see section 2b)\n";
	} else {
		echo "(APPROVED copy, zero open Andrew confirms - see section 2b)\n";
	}
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::halt( 1 );
	}
	exit( 1 );
}
