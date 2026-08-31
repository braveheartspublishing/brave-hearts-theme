<?php
/**
 * THE SCHOOL-VISIT COMPLETED-ORDER EMAIL (E2-V) — theme 1.19.315.
 * Workstream `CYCLE168-LD-VISIT-COMPLETED-EMAIL`.
 *
 * Run via WP-CLI, matching the other suites in this directory:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-visit-completed-email.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ WHAT THIS SUITE IS ACTUALLY DEFENDING
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE ONE FAILURE THAT WOULD MATTER IS NOT A FATAL ERROR. It is a Dallas
 *    Harris parent being told, in Andrew's name, that their child heard
 *    Mount Everest read to thirty first and second graders on a day nobody
 *    was there. That is a fabricated author experience and a fabricated
 *    classroom result sent to a real person, and no amount of "it looked
 *    right on staging" catches it, because on staging it WOULD look right.
 *
 * ⭐ SO THE CENTRE OF THIS FILE IS THE SLUG-ISOLATION BLOCK: every Adams
 *    fact is asserted ABSENT from the copy any other slug resolves to,
 *    string by string, rather than "the sets are different".
 *
 * ⛔ NO DATABASE WRITE. NO ORDER IS SAVED. NO EMAIL IS SENT, ENABLED OR
 *    TRIGGERED. Orders are built in memory with `new WC_Order()` and never
 *    `save()`d, so nothing reaches `wc_orders`. Email objects are the ones
 *    WooCommerce already constructed; only their `$object` property is read
 *    and restored. This file is safe to run on any environment.
 *
 * @package BraveHearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_vce_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/**
 * A stand-in WC_Email carrying an id and an order, which is the only shape
 * every helper under test reads.
 *
 * ⚠ A SUBCLASS OF THE REAL `WC_Email`, not a duck-typed stub, because every
 *   helper guards on `instanceof WC_Email` and a plain object would pass the
 *   suite by taking the "not an email" branch every time - a green run
 *   proving nothing.
 */
class BHP_VCE_Email extends WC_Email {
	public function __construct( $id, $order = null ) {
		$this->id     = $id;
		$this->object = $order;
	}
}

/** An in-memory order carrying (or not carrying) a visit slug. Never saved. */
function bhp_vce_order( $slug = '' ) {
	$order = new WC_Order();
	if ( '' !== $slug ) {
		$order->add_meta_data( '_bhp_school_visit_slug', $slug, true );
	}
	return $order;
}

const BHP_VCE_ADAMS = 'adams-2026-08-28';

echo "\n=== 1. THE HELPERS EXIST AND ARE LOADED ===\n";

foreach ( array(
	'bhp_visit_email_order_slug',
	'bhp_visit_email_order',
	'bhp_visit_email_slug',
	'bhp_visit_email_is_visit',
	'bhp_visit_email_copy_sets',
	'bhp_visit_email_copy',
	'bhp_visit_email_copy_is_usable',
	'bhp_visit_email_copy_is_approved',
	'bhp_visit_email_string',
	'bhp_visit_email_body',
	'bhp_email_footer_note_lines',
	'bhp_email_footer_note_lines_for',
) as $fn ) {
	bhp_vce_assert( function_exists( $fn ), "function {$fn}() is loaded", $failures );
}

echo "\n=== 2. SLUG DETECTION ===\n";

bhp_vce_assert( '' === bhp_visit_email_order_slug( null ), 'null order yields no slug', $failures );
bhp_vce_assert( '' === bhp_visit_email_order_slug( 'not an order' ), 'a string is not an order', $failures );
bhp_vce_assert( '' === bhp_visit_email_order_slug( bhp_vce_order() ), 'order without the meta yields no slug', $failures );
bhp_vce_assert( BHP_VCE_ADAMS === bhp_visit_email_order_slug( bhp_vce_order( BHP_VCE_ADAMS ) ), 'Adams order yields the Adams slug', $failures );

echo "\n=== 3. THE EMAIL-ID SCOPE — ONE EMAIL, NOT SEVEN ===\n";

$adams_order = bhp_vce_order( BHP_VCE_ADAMS );

bhp_vce_assert(
	bhp_visit_email_is_visit( new BHP_VCE_Email( 'customer_completed_order', $adams_order ) ),
	'completed email + visit order => IS a visit email',
	$failures
);

/*
 * ⛔ THE LEAK TEST. The same visit order attached to every OTHER order email
 *    in the system must not turn any of them into a visit email. Without the
 *    id guard in `bhp_visit_email_order()` every one of these would flip, and
 *    the Adams copy would appear in the receipt, the refund and the add-on
 *    thank-you.
 */
foreach ( array(
	'customer_processing_order',
	'customer_refunded_order',
	'customer_partially_refunded_order',
	'customer_on_hold_order',
	'customer_failed_order',
	'customer_note',
	'customer_cancelled_order',
	'bhp_addon_thankyou',
	'new_order',
) as $other_id ) {
	bhp_vce_assert(
		! bhp_visit_email_is_visit( new BHP_VCE_Email( $other_id, $adams_order ) ),
		"visit copy does NOT leak into {$other_id}",
		$failures
	);
}

bhp_vce_assert(
	! bhp_visit_email_is_visit( new BHP_VCE_Email( 'customer_completed_order', bhp_vce_order() ) ),
	'completed email + ordinary order => NOT a visit email',
	$failures
);
bhp_vce_assert( ! bhp_visit_email_is_visit( null ), 'null email is not a visit email', $failures );

echo "\n=== 4. THE APPROVED ADAMS COPY, STRING BY STRING ===\n";

$adams = bhp_visit_email_copy( BHP_VCE_ADAMS );

bhp_vce_assert(
	'What an awesome group of 1st and 2nd Graders!' === $adams['subject'],
	'Adams subject is the approved string, verbatim',
	$failures
);
bhp_vce_assert(
	'The signed books are with the kiddos! Along with a coloring book page.' === $adams['heading'],
	'Adams heading is the approved string, verbatim',
	$failures
);
bhp_vce_assert(
	'Signed Books, Delivered, and ready to read.' === $adams['preheader'],
	'Adams preheader is the approved string, verbatim',
	$failures
);
bhp_vce_assert( 5 === count( $adams['body'] ), 'Adams body is exactly five paragraphs', $failures );
bhp_vce_assert(
	'What an awesome group of kiddos! We read from Mount Everest, practiced Stop, Breathe, Think, Act, and yelled I can do hard things together, all thirty or so of us!' === $adams['body'][0],
	'Adams paragraph 1 verbatim',
	$failures
);
bhp_vce_assert(
	'Your signed books went home with your child today (or children, for a few families), along with a coloring book page from the read aloud.' === $adams['body'][1],
	'Adams paragraph 2 verbatim (smoothing 1 present)',
	$failures
);
bhp_vce_assert(
	'I also wanted to reach out and genuinely say thank you for raising such an awesome kiddo. Everyone in the group paid attention and listened so well.' === $adams['body'][2],
	'Adams paragraph 3 verbatim (smoothing 2 present)',
	$failures
);
bhp_vce_assert(
	'If they read the books and like them, there is a small thank you page with a QR code in the back. It goes to Amazon reviews. If you could write a review on the book/s it will help other early readers learn the lessons your little human got today.' === $adams['body'][3],
	'Adams paragraph 4 verbatim',
	$failures
);
bhp_vce_assert(
	'Feel free to email me any time at Andrew@braveheartspublishing.com, once again thank you!' === $adams['body'][4],
	'Adams paragraph 5 verbatim',
	$failures
);
bhp_vce_assert( true === $adams['approved'], 'Adams set is flagged approved', $failures );

echo "\n=== 5. THE HARD RAILS ON EVERY SET ===\n";

foreach ( bhp_visit_email_copy_sets() as $key => $set ) {
	$all = $set['subject'] . ' ' . $set['heading'] . ' ' . $set['preheader'] . ' ' . implode( ' ', $set['body'] );

	// ⛔ NO EM DASH. Standing email rule and Andrew's own.
	bhp_vce_assert( false === strpos( $all, "\xE2\x80\x94" ), "[{$key}] contains no em dash", $failures );

	/*
	 * ⛔ NO COLORING-PAGE QR LINE. Andrew removed it deliberately so the
	 *    T+7 scan count measures the printed handout and nothing else.
	 *    "QR code in the back" (paragraph 4, about the BOOK) is approved and
	 *    must survive; a QR pointing at the coloring page must not appear.
	 */
	bhp_vce_assert(
		false === stripos( $all, 'coloring page qr' ) && false === stripos( $all, 'scan the coloring' ),
		"[{$key}] carries no coloring-page QR line",
		$failures
	);

	// ⛔ THE UNCONFIRMED FOUNDER SPECIFICS. Standing Rules §3.
	foreach ( array( 'Island Peak', 'Jiri', '20,000 feet', 'without oxygen' ) as $forbidden ) {
		bhp_vce_assert( false === stripos( $all, $forbidden ), "[{$key}] does not contain \"{$forbidden}\"", $failures );
	}

	// The reply route Andrew gave must be reachable in every set.
	bhp_vce_assert( false !== stripos( $all, 'Andrew@braveheartspublishing.com' ), "[{$key}] carries the reply address", $failures );

	bhp_vce_assert( bhp_visit_email_copy_is_usable( $set ), "[{$key}] is a complete, renderable set", $failures );
}

echo "\n=== 6. ⭐ SLUG ISOLATION — THE TEST THIS FILE EXISTS FOR ===\n";

/*
 * Three slugs that are not Adams: a real future visit, a plausible typo of
 * the Adams slug, and pure junk. None of them may reach an Adams fact.
 */
$non_adams = array(
	'dallas-harris-2026-09-11',
	'adams-2026-08-29',
	'zzz-nonsense',
);

foreach ( $non_adams as $slug ) {
	$set = bhp_visit_email_copy( $slug );
	$all = $set['subject'] . ' ' . $set['heading'] . ' ' . $set['preheader'] . ' ' . implode( ' ', $set['body'] );

	bhp_vce_assert( $set !== $adams, "[{$slug}] does not resolve to the Adams set", $failures );

	// ⛔ EVERY ADAMS FACT, ASSERTED ABSENT INDIVIDUALLY.
	bhp_vce_assert( false === stripos( $all, '1st and 2nd' ), "[{$slug}] states no grade band", $failures );
	bhp_vce_assert( false === stripos( $all, 'Graders' ), "[{$slug}] states no grade level", $failures );
	bhp_vce_assert( false === stripos( $all, 'Mount Everest' ), "[{$slug}] names no book", $failures );
	bhp_vce_assert( false === stripos( $all, 'thirty' ), "[{$slug}] states no headcount", $failures );
	bhp_vce_assert( false === stripos( $all, 'Stop, Breathe' ), "[{$slug}] claims no specific activity", $failures );
	bhp_vce_assert( false === stripos( $all, 'paid attention' ), "[{$slug}] claims no classroom result", $failures );
	bhp_vce_assert( false === stripos( $all, 'coloring book page' ), "[{$slug}] promises no coloring page", $failures );
	bhp_vce_assert( false === stripos( $all, 'Adams' ), "[{$slug}] names no school", $failures );
}

/*
 * ⚠ THE NEUTRAL SET IS DELIBERATELY FLAGGED UNAPPROVED. This assertion is a
 *   tripwire: the day somebody flips it to true, they must come here and
 *   change this line, which is exactly the moment to ask whether Andrew
 *   actually approved it.
 */
bhp_vce_assert(
	false === bhp_visit_email_copy_is_approved( 'dallas-harris-2026-09-11' ),
	'the neutral fallback is flagged NOT approved (tripwire)',
	$failures
);
bhp_vce_assert(
	true === bhp_visit_email_copy_is_approved( BHP_VCE_ADAMS ),
	'exactly the Adams slug reports approved',
	$failures
);

$approved_count = 0;
foreach ( bhp_visit_email_copy_sets() as $set ) {
	if ( ! empty( $set['approved'] ) ) {
		++$approved_count;
	}
}
bhp_vce_assert( 1 === $approved_count, 'exactly ONE copy set is approved today', $failures );

echo "\n=== 7. A BROKEN FILTER IS DISCARDED, NOT SENT ===\n";

$guard = function () {
	return array( 'subject' => '', 'heading' => '', 'preheader' => '', 'body' => array() );
};
add_filter( 'bhp_visit_email_copy', $guard, 10, 2 );
$after = bhp_visit_email_copy( BHP_VCE_ADAMS );
remove_filter( 'bhp_visit_email_copy', $guard, 10 );

bhp_vce_assert(
	'What an awesome group of 1st and 2nd Graders!' === $after['subject'],
	'an empty filter return falls back to the real set rather than sending blank',
	$failures
);

$junk = function () {
	return 'not an array';
};
add_filter( 'bhp_visit_email_copy', $junk, 10, 2 );
$after2 = bhp_visit_email_copy( BHP_VCE_ADAMS );
remove_filter( 'bhp_visit_email_copy', $junk, 10 );

bhp_vce_assert( is_array( $after2 ) && $after2['subject'] === $adams['subject'], 'a non-array filter return is discarded', $failures );

echo "\n=== 8. THE FD-76 BOOKVAULT FOOTER FORK ===\n";

$standard_lines = bhp_email_footer_note_lines();
bhp_vce_assert( 2 === count( $standard_lines ), 'the standard footer note is still two lines', $failures );
bhp_vce_assert(
	'Printed and fulfilled by our publishing partner, Bookvault.' === $standard_lines[0],
	'the FD-76 sentence is byte-unchanged',
	$failures
);

$visit_email    = new BHP_VCE_Email( 'customer_completed_order', $adams_order );
$plain_email    = new BHP_VCE_Email( 'customer_completed_order', bhp_vce_order() );
$visit_footer   = bhp_email_footer_note_lines_for( $visit_email );
$plain_footer   = bhp_email_footer_note_lines_for( $plain_email );
$default_footer = bhp_email_footer_note_lines_for( null );

bhp_vce_assert( 1 === count( $visit_footer ), 'visit footer has one line', $failures );
bhp_vce_assert(
	false === stripos( implode( ' ', $visit_footer ), 'Bookvault' ),
	'⭐ visit footer carries NO Bookvault sentence',
	$failures
);
bhp_vce_assert(
	'Reply to this email and it comes to a real person.' === $visit_footer[0],
	'visit footer keeps the reply route',
	$failures
);
bhp_vce_assert( $plain_footer === $standard_lines, 'a standard order keeps BOTH footer lines', $failures );
bhp_vce_assert( $default_footer === $standard_lines, 'a null email keeps BOTH footer lines (fail safe)', $failures );

echo "\n=== 9. BODY RESOLUTION AT THE TEMPLATE BOUNDARY ===\n";

bhp_vce_assert( array() === bhp_visit_email_body( $plain_email ), 'ordinary order => empty body array => standard template path', $failures );
bhp_vce_assert( 5 === count( bhp_visit_email_body( $visit_email ) ), 'visit order => five paragraphs', $failures );
bhp_vce_assert( array() === bhp_visit_email_body( null ), 'null email => empty body array', $failures );
bhp_vce_assert( '' === bhp_visit_email_string( $plain_email, 'subject' ), 'ordinary order => no visit subject', $failures );
bhp_vce_assert( '' === bhp_visit_email_string( $visit_email, 'nope' ), 'an unknown field yields empty string, not a notice', $failures );

echo "\n=== 10. THE ADD-ON THANK-YOU EMAIL IS NOT TOUCHED ===\n";

/*
 * ⭐ VERIFIED SEPARATELY AND RECORDED HERE SO IT IS NOT RE-DERIVED: seven of
 *    the eight Adams orders already carry `_bhp_addon_thankyou_sent` from
 *    2026-08-24/25, and `bhp_bundle_addon_thankyou_should_send()` declines on
 *    that meta. The eighth (order 630) has no add-on in its line items. So
 *    completing these orders re-sends NOTHING from that lane.
 */
bhp_vce_assert(
	function_exists( 'bhp_bundle_addon_thankyou_should_send' ),
	'the add-on guard is still loaded and unmodified in shape',
	$failures
);

$already_sent = bhp_vce_order( BHP_VCE_ADAMS );
$already_sent->add_meta_data( '_bhp_addon_thankyou_sent', '2026-08-24 22:00:08', true );
bhp_vce_assert(
	false === bhp_bundle_addon_thankyou_should_send( $already_sent ),
	'⭐ an order already carrying _bhp_addon_thankyou_sent declines a second send',
	$failures
);

echo "\n========================================\n";
if ( empty( $failures ) ) {
	echo "ALL ASSERTIONS PASSED\n";
} else {
	echo 'FAILURES (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
echo "========================================\n";
