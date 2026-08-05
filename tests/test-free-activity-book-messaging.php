<?php
/**
 * Brave Hearts — FREE ACTIVITY BOOK MESSAGING + the checkout hero removal
 * (theme 1.19.194).
 *
 * Run via WP-CLI, from the WordPress root:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-free-activity-book-messaging.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE IS FOR
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-05, verbatim (⛔ RELAYED through the Chief of
 * Staff; NOT witnessed first-hand by the agent that wrote this file):
 *
 *   "Remove the whole section on the checkout page "Brave Hearts Field
 *    Journal Checkout" - its clearly understood that its a check out page-
 *    bring everything up. I want to change the upsell- make the activity
 *    book free and I want it clear that you get Free Shipping and a Free
 *    Activity book with Collection purchase- on all collection pages and
 *    boxes"
 *
 * Two rulings, two halves. §1–§2 cover the checkout hero. §3–§6 cover the
 * messaging.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE CAN AND CANNOT PROVE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * PHP/CLI. No layout engine, no browser, no rendered page. It CANNOT show
 * that the checkout form visually moved up, and it does not claim to — that
 * is measured in a real browser and recorded in the release QA. What it
 * proves is that the CONDITION exists, is scoped to the checkout page, and
 * that the two structures a "tidy-up" would break are still in place.
 *
 * ⛔ NO ORDER, NO CART, NO PRODUCT/PRICE/COUPON/STOCK/SHIPPING/TAX/PAYMENT
 *    RECORD is read or written by any part of this file.
 *
 * Exits non-zero on any failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();
$skipped  = array();

function bhp_fab_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_fab_skip( $label, array &$skipped ) {
	echo "SKIP: {$label}\n";
	$skipped[] = $label;
}

function bhp_fab_read( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

/** Source with comments removed, so prose can never satisfy a code assertion. */
function bhp_fab_code( $src ) {
	$out = '';
	foreach ( token_get_all( $src ) as $token ) {
		if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		$out .= is_array( $token ) ? $token[1] : $token;
	}
	return $out;
}

echo "\n=== §1 · THE CHECKOUT HERO IS GONE, AND ONLY ON THE CHECKOUT PAGE ===\n";

$fab_page_src  = bhp_fab_read( 'page.php' );
$fab_page_code = bhp_fab_code( $fab_page_src );

bhp_fab_assert( '' !== $fab_page_src, '§1a page.php is readable', $failures );

bhp_fab_assert(
	1 === preg_match( '/is_checkout\(\)\s*&&\s*!\s*is_order_received_page\(\)/', $fab_page_code ),
	'§1b the suppression condition is is_checkout() AND NOT is_order_received_page()',
	$failures
);

/*
 * ⭐ THE ORDER-RECEIVED EXCLUSION IS THE ASYMMETRIC ONE. `is_checkout()`
 *    returns TRUE on the thank-you page, because order-received is a
 *    checkout endpoint. Without the exclusion, a customer who has just paid
 *    would land on a page with no heading at all. Two other files in this
 *    codebase record the same trap for the same reason
 *    (includes/addon-upsell.php, includes/checkout-upsell.php), which is
 *    why it is asserted rather than trusted.
 */
bhp_fab_assert(
	1 === preg_match( '/function_exists\(\s*\x27is_order_received_page\x27\s*\)/', $fab_page_code ),
	'§1c ⭐ and the order-received exclusion is function_exists-guarded (is_checkout() is TRUE on the thank-you page)',
	$failures
);

bhp_fab_assert(
	1 === preg_match( '/if\s*\(\s*!\s*\$bhp_is_checkout_page\s*\)\s*:/', $fab_page_code ),
	'§1d the interior-hero header is wrapped in that condition',
	$failures
);

/*
 * The eyebrow string must still EXIST in the template — it renders on every
 * other page. An assertion that it is gone entirely would pass against a
 * regression that stripped it from the whole site.
 */
bhp_fab_assert(
	false !== strpos( $fab_page_code, 'Brave Hearts Field Journal' ),
	'§1e ⛔ the eyebrow still exists for every OTHER page - this is a suppression, not a deletion',
	$failures
);
bhp_fab_assert(
	false !== strpos( $fab_page_code, 'interior-hero interior-hero--parchment' ),
	'§1f the interior-hero markup itself is unchanged',
	$failures
);

echo "\n=== §2 · THE 1.19.185 DESKTOP CHECKOUT FIX SURVIVED THE REMOVAL ===\n";

/*
 * ⭐ THIS SECTION IS THE REASON THE HERO WAS SUPPRESSED RATHER THAN GIVEN
 *    ITS OWN TEMPLATE. `assets/css/checkout-experience.css`'s 1240px
 *    desktop rule targets `.page-content.page-checkout`, which page.php
 *    builds. A `page-checkout.php` would have removed that wrapper and
 *    silently returned the duplicate order summary with no other symptom.
 *    tests/test-checkout-desktop-layout.php §5d/§5e own these facts; they
 *    are re-asserted here because THIS release is the one that could have
 *    broken them.
 */
bhp_fab_assert(
	! file_exists( get_template_directory() . '/page-checkout.php' ),
	'§2a ⭐ no page-checkout.php was created - the .page-content.page-checkout wrapper the desktop fix targets is intact',
	$failures
);
bhp_fab_assert(
	false !== strpos( $fab_page_code, 'page-content page-' ),
	'§2b page.php still emits the "page-content page-<slug>" wrapper',
	$failures
);
$fab_checkout_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
if ( $fab_checkout_id > 0 ) {
	$fab_tpl = get_page_template_slug( $fab_checkout_id );
	bhp_fab_assert(
		'' === $fab_tpl || 'default' === $fab_tpl,
		"§2c the checkout page still uses the default template page.php (got '{$fab_tpl}')",
		$failures
	);
} else {
	bhp_fab_skip( '§2c no WooCommerce checkout page configured on this environment', $skipped );
}

/*
 * The visually-hidden <h1>. Flagged in the handoff as a judgement call, so
 * it is asserted here rather than left implicit: if Andrew wants it gone,
 * this assertion is what tells the next session it was deliberate.
 */
bhp_fab_assert(
	1 === preg_match( '/<h1 class="screen-reader-text">/', $fab_page_code ),
	'§2d a visually-hidden <h1> replaces the visible one (document outline preserved; nothing visible added)',
	$failures
);

echo "\n=== §3 · THE THEME NEVER DECIDES WHAT 'FREE' MEANS ===\n";

foreach ( array( 'bhp_book_collection_includes_free_addon', 'bhp_book_free_addon_note', 'bhp_book_free_addon_badge' ) as $fn ) {
	bhp_fab_assert( function_exists( $fn ), "§3 {$fn}() is loaded", $failures );
}

$fab_helper_code = bhp_fab_code( bhp_fab_read( 'inc/book-formats.php' ) );

bhp_fab_assert(
	1 === preg_match( '/function bhp_book_collection_includes_free_addon.{0,300}bhp_bundle_addon_free_with_collection/s', $fab_helper_code ),
	'§3a ⭐ the predicate DELEGATES to the plugin - there is no theme-side opinion about whether a book is free',
	$failures
);
bhp_fab_assert(
	1 === preg_match( '/function bhp_book_collection_includes_free_addon.{0,300}return false;/s', $fab_helper_code ),
	'§3b ⛔ FALSE is the answer when the plugin is absent - a page that says nothing is what shipped before; a page that promises a free book the cart charges for is a lie',
	$failures
);
if ( function_exists( 'bhp_bundle_addon_free_with_collection' ) ) {
	bhp_fab_assert(
		bhp_book_collection_includes_free_addon() === (bool) bhp_bundle_addon_free_with_collection(),
		'§3c the theme and the plugin agree on THIS environment (live check)',
		$failures
	);
} else {
	bhp_fab_assert(
		false === bhp_book_collection_includes_free_addon(),
		'§3c with the plugin function absent the theme returns false',
		$failures
	);
}

/*
 * ⛔ THE EMPTY-STRING CONTRACT. Both copy helpers must return '' when the
 *    offer is not live, because every caller concatenates unconditionally.
 *    A helper that returned a sentence regardless would put an unguarded
 *    claim on four landing pages and three product pages at once.
 */
$fab_live = bhp_book_collection_includes_free_addon();
if ( $fab_live ) {
	bhp_fab_assert( '' !== bhp_book_free_addon_note(), '§3d the note is non-empty while the offer is live', $failures );
	bhp_fab_assert( '' !== bhp_book_free_addon_badge(), '§3e the badge is non-empty while the offer is live', $failures );
	foreach ( array( 'note' => bhp_book_free_addon_note(), 'badge' => bhp_book_free_addon_badge() ) as $where => $text ) {
		bhp_fab_assert( false === strpos( $text, '—' ), "§3 the {$where} carries no em dash", $failures );
		bhp_fab_assert( false === strpos( $text, '$' ), "§3 the {$where} quotes no dollar figure", $failures );
		bhp_fab_assert( false === stripos( $text, 'normally' ) && false === stripos( $text, 'value' ), "§3 the {$where} makes no struck-out-price claim", $failures );
	}
	bhp_fab_assert(
		false !== strpos( bhp_book_free_addon_note(), 'FREE' ),
		'§3f the note says FREE in typed capitals (not a CSS transform - a screen reader reads the DOM text)',
		$failures
	);
} else {
	bhp_fab_assert( '' === bhp_book_free_addon_note(), '§3d ⭐ the note is EMPTY when the offer is not live', $failures );
	bhp_fab_assert( '' === bhp_book_free_addon_badge(), '§3e ⭐ the badge is EMPTY when the offer is not live', $failures );
	bhp_fab_skip( '§3f the live-copy assertions need a resolvable BHP-ACTIVITY-BOOK-01 on this environment', $skipped );
}

echo "\n=== §4 · ALL FOUR FUNNEL PRICE CARDS CARRY IT, GATED ===\n";

foreach ( array(
	'page-audience-educators.php'             => 'audience-landing-pricecard',
	'page-audience-gift-buyers.php'           => 'audience-landing-pricecard',
	'page-audience-organizations.php'         => 'audience-landing-pricecard',
	'page-reluctant-reader-adventure-kit.php' => 'parent-landing-pricecard',
) as $tpl => $prefix ) {
	$src  = bhp_fab_read( $tpl );
	$code = '' === $src ? '' : bhp_fab_code( $src );

	bhp_fab_assert( '' !== $src, "§4 {$tpl}: readable", $failures );
	bhp_fab_assert(
		false !== strpos( $code, 'bhp_book_free_addon_badge()' ),
		"§4 {$tpl}: renders the badge through the shared helper (never its own copy of the string)",
		$failures
	);
	bhp_fab_assert(
		1 === preg_match( '/function_exists\(\x27bhp_book_free_addon_badge\x27\)/', $code ),
		"§4 {$tpl}: the call is function_exists-guarded",
		$failures
	);
	bhp_fab_assert(
		1 === preg_match( '/if\s*\(\x27\x27\s*!==\s*\$bhp_free_addon_badge\)/', $code ),
		"§4 {$tpl}: ⭐ the pill only renders when the helper returned something - the gate is in the TEMPLATE as well as in the helper",
		$failures
	);
	bhp_fab_assert(
		false === stripos( $code, 'Free Activity Book</span>' ),
		"§4 {$tpl}: ⛔ no hardcoded 'Free Activity Book' literal in the markup",
		$failures
	);
	// The existing ship-note routing must not have been disturbed.
	bhp_fab_assert(
		false !== strpos( $code, "bhp_book_landing_ship_note(\$f['shipping'])" ),
		"§4 {$tpl}: the shipping note still routes through bhp_book_landing_ship_note()",
		$failures
	);
}

echo "\n=== §5 · THE PDP COLLECTION CARD ===\n";

$fab_fc_code = bhp_fab_code( bhp_fab_read( 'template-parts/commerce/format-cards.php' ) );

bhp_fab_assert(
	false !== strpos( $fab_fc_code, 'bhp_book_free_addon_note()' ),
	'§5a the collection card appends the shared note',
	$failures
);
bhp_fab_assert(
	1 === preg_match( '/\$bhp_shipping_note_collection\s*\.=/', $fab_fc_code ),
	'§5b ⭐ it APPENDS rather than replacing - bhp_book_ship_note_collection() stays a pure function of a shipping figure, and its approved wording stays asserted in tests/test-book-formats.php',
	$failures
);
bhp_fab_assert(
	1 === preg_match( '/if\s*\(\x27\x27\s*!==\s*\$bhp_free_addon_note\)/', $fab_fc_code ),
	'§5c and only when the helper returned something',
	$failures
);

/*
 * ⛔ THE APPROVED SHIPPING SENTENCES ARE UNTOUCHED. Asserted by calling the
 *    helpers, not by reading them: the exact strings tests/test-book-formats
 *    .php pins are still what comes out.
 */
if ( function_exists( 'bhp_book_ship_note_collection' ) ) {
	bhp_fab_assert(
		bhp_book_ship_note_collection( 0.00 ) === 'All three adventures, bundled at a lower price than buying separately. FREE shipping in the contiguous US.',
		'§5d ⛔ the approved FREE collection shipping sentence is byte-identical to 1.19.193',
		$failures
	);
	bhp_fab_assert(
		bhp_book_ship_note_collection( 4.99 ) === 'All three adventures, bundled at a lower price than buying separately. Shipping from $4.99 in the contiguous US.',
		'§5e ⛔ and so is the dollar branch',
		$failures
	);
}
if ( function_exists( 'bhp_book_landing_ship_note' ) ) {
	bhp_fab_assert(
		bhp_book_landing_ship_note( 0.00 ) === '+ FREE shipping · ages 6–9 · printed & shipped in the USA',
		'§5f ⛔ the approved landing-card shipping sentence is byte-identical to 1.19.193',
		$failures
	);
}

echo "\n=== §6 · FUNNEL ISOLATION AND THE STANDING CONTENT RULES ===\n";

/*
 * The two funnels must stay independent. This release touches one of the
 * two funnel landing pages (the parent Adventure Kit page), so the storage
 * and event prefixes are re-asserted rather than assumed unmoved.
 */
$fab_parent = bhp_fab_read( 'page-reluctant-reader-adventure-kit.php' );
bhp_fab_assert(
	false === stripos( $fab_parent, 'bhp_mariana_popup' ) && false === stripos( $fab_parent, 'teacher_popup' ),
	'§6a ⛔ the parent landing page carries no TEACHER funnel storage key or event prefix',
	$failures
);

/*
 * Reading age is 6–9, never 5–9, everywhere. The badge row this release
 * edits sits directly beside the ages line on all four cards.
 */
foreach ( array( 'page-audience-educators.php', 'page-audience-gift-buyers.php', 'page-audience-organizations.php', 'page-reluctant-reader-adventure-kit.php' ) as $tpl ) {
	$src = bhp_fab_read( $tpl );
	bhp_fab_assert(
		false === strpos( $src, '5–9' ) && false === strpos( $src, '5-9 ' ),
		"§6b {$tpl}: reading age is not stated as 5-9",
		$failures
	);
}

/*
 * ⛔ NO FABRICATED RATING SCHEMA can have entered through this release.
 *    Cheap to assert, and it is on the never-invent list.
 */
foreach ( array( 'template-parts/components/complete-collection-feature.php', 'template-parts/commerce/format-cards.php' ) as $tpl ) {
	/*
	 * ⚠ CORRECTED AFTER THIS ASSERTION PRODUCED A FALSE FAILURE ON A
	 *   CORRECT BUILD, staging 1.19.194, 2026-08-05. The first version
	 *   scanned the RAW file, and complete-collection-feature.php's header
	 *   states, in prose, that it "emits no Review or AggregateRating
	 *   schema" — so `stripos()` matched an explanation and reported a
	 *   defect that did not exist. It is the same defect class this suite's
	 *   own bhp_fab_code() exists to prevent, and the same one
	 *   test-checkout-desktop-layout.php §4 already records once.
	 *   `CYCLE144-LD-226`.
	 */
	$src = bhp_fab_code( bhp_fab_read( $tpl ) );
	bhp_fab_assert(
		false === stripos( $src, 'aggregateRating' ) && false === stripos( $src, 'ratingValue' ),
		"§6c {$tpl}: no aggregateRating/ratingValue in any CODE (comments excluded)",
		$failures
	);
}

echo "\n";
if ( $skipped ) {
	echo count( $skipped ) . " SKIPPED (stated, not hidden):\n";
	foreach ( $skipped as $s ) {
		echo "  - {$s}\n";
	}
	echo "\n";
}
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED (6 sections)\n";
	exit( 0 );
}

echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
