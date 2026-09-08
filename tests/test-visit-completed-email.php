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

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE (1.19.386).
 *
 * ⭐ Six real emails left staging through Google's SMTP relay during two suite
 *    runs and bounced back to the founder. Staging now relays live, so any
 *    test that creates an order or moves one between statuses is an
 *    outbound-mail event. This include stops every one of them at
 *    `pre_wp_mail`, captures it instead, and PROVES the block at include time
 *    rather than assuming it.
 *
 * ⛔ NO ISO DATE APPEARS IN THIS BLOCK, AND THAT IS DELIBERATE. Two suites
 *    scan their OWN source for one and fail if they find it — which is
 *    exactly what the first version of this comment did to them. The dated
 *    evidence lives in tests/bootstrap-mail-guard.php, which nothing scans.
 *
 * ⛔ Assert on mail with `bhp_test_mail_log()` / `bhp_test_mail_find()`.
 *    Never by sending. See tests/bootstrap-mail-guard.php.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

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

echo "\n=== 4. THE ONE GENERIC DAY-0 SET, STRING BY STRING ===\n";

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.369 · SECTION 4 REWRITTEN. THE PER-SCHOOL ADAMS SET IS RETIRED TO
 *     COMMENTS (seal 994, round-8 brief item 2) AND THERE IS NOW EXACTLY ONE
 *     DAY-0 SET, USED FOR EVERY VISIT ORDER.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SUPERSEDED SECTION ASSERTED THE ADAMS STRINGS VERBATIM — subject,
 *    heading, preheader, "exactly five paragraphs", and paragraphs 1 to 5
 *    including the Amazon/QR paragraph. Those assertions are preserved in the
 *    file history and the strings themselves are preserved verbatim in
 *    `inc/visit-completed-email.php`'s retirement comment.
 *
 * ⚠⚠ AND THREE OF THEM WERE ALREADY FAILING BEFORE THIS BUILD, WHICH IS
 *    REPORTED RATHER THAN QUIETLY FIXED. 1.19.364 removed the Amazon/QR
 *    paragraph from the Adams set under seal 977, taking its body from five
 *    paragraphs to four, but this suite was never updated: "Adams body is
 *    exactly five paragraphs", "Adams paragraph 4 verbatim" and "Adams
 *    paragraph 5 verbatim" have been red since 1.19.364. This desk did not
 *    introduce those failures and did not observe them running — no PHP
 *    runtime was available in this session. See the deliverable.
 */
$day0 = bhp_visit_email_copy( BHP_VCE_ADAMS );

bhp_vce_assert(
	$day0 === bhp_visit_email_copy( 'zzz-nonsense' ),
	'⭐⭐ EVERY slug now resolves to the same generic set (seal 994)',
	$failures
);
bhp_vce_assert(
	array( BHP_VISIT_EMAIL_DEFAULT_KEY ) === array_keys( bhp_visit_email_copy_sets() ),
	'⭐ There is exactly ONE day-0 copy set and it is the generic one',
	$failures
);
bhp_vce_assert(
	'The signed books went home today' === $day0['subject'],
	'Day-0 subject is the approved seal-982 string, verbatim',
	$failures
);
bhp_vce_assert(
	'The signed books went home today' === $day0['heading'],
	'Day-0 heading restates the subject and adds no claim',
	$failures
);
bhp_vce_assert( true === $day0['approved'], 'The generic day-0 set is flagged approved (seal 982)', $failures );
/*
 * ⭐ EIGHT SINCE 1.19.371, NOT NINE — SEAL 1007. Andrew Signore, 2026-09-05,
 *    verbatim (⛔ RELAYED through `chief-of-staff`, not heard first-hand): *"I like the
 *    nice signature and big place brave hearts - drop the plain one"*. The
 *    ninth paragraph was a bare `Andrew` and it is gone; the name is carried by
 *    the signature block. ⚠ If this ever reads 9 again, the plain sign-off has
 *    come back and the founder's edit has been undone.
 */
bhp_vce_assert( 8 === count( $day0['body'] ), 'Day-0 body is exactly eight paragraphs (seal 1007 dropped the plain sign-off)', $failures );
bhp_vce_assert( '{VisitLine}' === $day0['body'][2], '⛔ {VisitLine} is its own paragraph, so an empty one disappears', $failures );
bhp_vce_assert(
	'Email me any time at Andrew@braveheartspublishing.com.' === $day0['body'][7],
	'⭐ The day-0 body now ENDS on the reply route, not on a bare name',
	$failures
);
bhp_vce_assert(
	! in_array( 'Andrew', $day0['body'], true ),
	'⛔ SEAL 1007: no paragraph in the day-0 body is the standalone word "Andrew"',
	$failures
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ SEAL 1064 — THE REPLACED SENTENCE, PINNED BOTH WAYS. 1.19.381.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ TWO ASSERTIONS, NOT ONE, AND THE SECOND IS THE IMPORTANT ONE. Pinning the
 *    new sentence proves it shipped; pinning the ABSENCE of the old one proves
 *    a later merge, revert or copy-paste from an archived set has not quietly
 *    put back a claim the founder withdrew.
 *
 * ⚠ WHY HE WITHDREW IT, so nobody re-adds it as an improvement: the old
 *   sentence said **all of it is true** of books containing a talking dog and
 *   historical figures who did not really take Charlotte and Henry anywhere
 *   (seal 1063). It is on the wrong side of this company's never-invent rule.
 *
 * ⛔ SOURCE: `Business OS\WORKING-DRAFTS\marketing-growth\CYCLE179-MKT-REVIEW-SEQ-V2.md`
 *    line 55, as amended 2026-09-05 by `chief-of-staff` under seal 1064. ⚠ THAT
 *    AMENDMENT CHANGED THE FILE'S md5: the seal-982 pin recorded elsewhere in
 *    this suite as `1ecd9c75acfc755df0e121b47ca73842` is the PRE-AMENDMENT
 *    digest; the file now reads `0ecc659fbea46fb25ef9b01014c2ed13` (measured
 *    2026-09-05 by this desk, not carried from a report).
 */
$bhp_vce_day0_new = 'The places are real. So are the animals, the weather and the science. The adventures are made up; the world they happen in is not.';
$bhp_vce_day0_old = 'The places are real. So are the animals, the weather and the science. None of it is homework and all of it is true.';

bhp_vce_assert(
	in_array( $bhp_vce_day0_new, $day0['body'], true ),
	'⭐⭐ SEAL 1064: the day-0 body carries the replacement sentence verbatim',
	$failures
);
bhp_vce_assert(
	! in_array( $bhp_vce_day0_old, $day0['body'], true ),
	'⛔ SEAL 1064: the superseded "all of it is true" sentence is NOT in the day-0 body',
	$failures
);
bhp_vce_assert(
	false === strpos( implode( ' ', $day0['body'] ), 'all of it is true' ),
	'⛔ SEAL 1064: no reworded survival of the withdrawn truth claim anywhere in the body',
	$failures
);

/*
 * ⛔⛔ THE ADAMS FACTS ARE UNREACHABLE FROM THIS FILE, FOR EVERY SLUG. This is
 *     the assertion the retirement exists to make possible: it used to be true
 *     only for slugs that were not Adams.
 */
$day0_all = $day0['subject'] . ' ' . $day0['heading'] . ' ' . $day0['preheader'] . ' ' . implode( ' ', $day0['body'] );

foreach ( array( '1st and 2nd', 'Graders', 'Mount Everest', 'thirty', 'Stop, Breathe', 'paid attention', 'coloring book page', 'awesome group' ) as $adams_fact ) {
	bhp_vce_assert(
		false === stripos( $day0_all, $adams_fact ),
		"⛔ The generic day-0 set states no Adams fact: \"{$adams_fact}\"",
		$failures
	);
}

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

echo "\n=== 6. ⭐ SLUG ISOLATION — NOW A ONE-SET PROOF ===\n";

/*
 * ⭐⭐ 1.19.369 · THE PROOF INVERTED, AND IT IS STRONGER THAN IT WAS. This
 *     section used to walk three non-Adams slugs and assert that none of them
 *     reached an Adams fact. Under seal 994 there is no Adams set to reach, so
 *     the same guarantee is now proved for EVERY slug including the Adams slug
 *     itself, which is the case the old loop could not cover.
 *
 * ⛔ SUPERSEDED LOOP, PRESERVED IN DESCRIPTION: three slugs — a real future
 *    visit, a plausible typo of the Adams slug, and pure junk — each asserted
 *    not to resolve to the Adams set and not to contain any Adams fact.
 */
$every_slug = array(
	BHP_VCE_ADAMS,
	'dallas-harris-2026-09-11',
	'adams-2026-08-29',
	'zzz-nonsense',
	'',
);

foreach ( $every_slug as $slug ) {
	$set = bhp_visit_email_copy( $slug );
	$all = $set['subject'] . ' ' . $set['heading'] . ' ' . $set['preheader'] . ' ' . implode( ' ', $set['body'] );

	bhp_vce_assert( $set === $day0, "[{$slug}] resolves to the one generic set", $failures );

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
 * ⛔⛔ THE GATE. `approved` became a HARD SEND GATE in 1.19.364, so what has to
 *     be proved now is that it still refuses two things: NO SLUG AT ALL, and a
 *     FILTERED set carrying `approved => false`.
 *
 * ⚠ SUPERSEDED TRIPWIRE, PRESERVED RATHER THAN DELETED: the neutral fallback
 *   used to be asserted NOT approved, so that flipping it forced a visit to
 *   this line. Andrew flipped it himself under seal 982, so the tripwire has
 *   done its job and is replaced by the two assertions below.
 */
bhp_vce_assert(
	true === bhp_visit_email_copy_is_approved( BHP_VCE_ADAMS ),
	'a real visit slug reports approved (seal 982 set)',
	$failures
);
bhp_vce_assert(
	false === bhp_visit_email_copy_is_approved( '' ),
	'⛔ NO SLUG AT ALL is not a visit order and is not approved',
	$failures
);

$unapproved = function () {
	return array(
		'approved'  => false,
		'subject'   => 'UNAPPROVED PROBE',
		'heading'   => 'UNAPPROVED PROBE',
		'preheader' => 'UNAPPROVED PROBE',
		'body'      => array( 'UNAPPROVED PROBE BODY' ),
	);
};
add_filter( 'bhp_visit_email_copy', $unapproved, 99, 2 );
$gate_blocked = ! bhp_visit_email_copy_is_approved( BHP_VCE_ADAMS );
remove_filter( 'bhp_visit_email_copy', $unapproved, 99 );

bhp_vce_assert( $gate_blocked, '⛔⛔ An unapproved set supplied through the filter seam is NOT approved', $failures );

$approved_count = 0;
foreach ( bhp_visit_email_copy_sets() as $set ) {
	if ( ! empty( $set['approved'] ) ) {
		++$approved_count;
	}
}
bhp_vce_assert( 1 === $approved_count, 'exactly ONE copy set exists and it is approved', $failures );

echo "\n=== 7. A BROKEN FILTER IS DISCARDED, NOT SENT ===\n";

$guard = function () {
	return array( 'subject' => '', 'heading' => '', 'preheader' => '', 'body' => array() );
};
add_filter( 'bhp_visit_email_copy', $guard, 10, 2 );
$after = bhp_visit_email_copy( BHP_VCE_ADAMS );
remove_filter( 'bhp_visit_email_copy', $guard, 10 );

bhp_vce_assert(
	// ⛔ 1.19.369 · SUPERSEDED: 'What an awesome group of 1st and 2nd Graders!'
	'The signed books went home today' === $after['subject'],
	'an empty filter return falls back to the real set rather than sending blank',
	$failures
);

$junk = function () {
	return 'not an array';
};
add_filter( 'bhp_visit_email_copy', $junk, 10, 2 );
$after2 = bhp_visit_email_copy( BHP_VCE_ADAMS );
remove_filter( 'bhp_visit_email_copy', $junk, 10 );

bhp_vce_assert( is_array( $after2 ) && $after2['subject'] === $day0['subject'], 'a non-array filter return is discarded', $failures );

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
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.371 · THIS ASSERTION WAS STALE AND WRONG IN TWO SEPARATE WAYS, AND
 *     BOTH ARE FIXED HERE RATHER THAN THE NUMBER BEING NUDGED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ SUPERSEDED, PRESERVED VERBATIM:
 *        bhp_vce_assert( 4 === count( bhp_visit_email_body( $visit_email ) ),
 *            'visit order => four paragraphs (Amazon ask removed, seal 977)', $failures );
 *    with the docblock *"FOUR, NOT FIVE, SINCE 1.19.364"*.
 *
 * ⚠ WRONG #1 — IT NAMED THE RETIRED SET. `4` was the paragraph count of the
 *   per-school `adams-2026-08-28` copy. Seal 994 retired that set at 1.19.369
 *   and every slug now resolves to the ONE generic day-0 set, whose body is a
 *   different length entirely. The number stopped describing anything real.
 *
 * ⚠ WRONG #2 — `$visit_email` CANNOT RENDER A BODY AT ALL, so the old
 *   assertion could not have been green whatever the number was. Its order is
 *   `bhp_vce_order( BHP_VCE_ADAMS )`: a slug and nothing else. No line items
 *   means `{BookTitle(s)}` does not resolve, and
 *   `bhp_visit_email_merge_is_complete()` is a HARD STOP on that slot — *"If no
 *   title resolves, do not send"*. The correct answer for that order is an
 *   EMPTY body, and that is now asserted as the gate it is.
 *
 * ⭐ THE REAL BODY IS THEN ASSERTED AGAINST A RESOLVABLE ORDER, still entirely
 *    in memory: the school comes off `_bhp_school_visit_school` (order meta,
 *    which `bhp_visit_email_merge_values()` reads FIRST) and the title off one
 *    line item whose product id is taken from `bhp_book_registry()` at runtime
 *    rather than hard-coded. ⛔ Nothing is saved, no product is created, no
 *    WooCommerce record is touched.
 */
bhp_vce_assert(
	array() === bhp_visit_email_body( $visit_email ),
	'⛔ a visit order with no resolvable {BookTitle(s)} renders NO body (hard stop, not a blank)',
	$failures
);

$bhp_vce_registry = function_exists( 'bhp_book_registry' ) ? bhp_book_registry() : array();
$bhp_vce_first    = is_array( $bhp_vce_registry ) && $bhp_vce_registry ? reset( $bhp_vce_registry ) : array();
$bhp_vce_pid      = isset( $bhp_vce_first['pb_product'] ) ? (int) $bhp_vce_first['pb_product'] : 0;

if ( $bhp_vce_pid && class_exists( 'WC_Order_Item_Product' ) ) {
	$bhp_vce_full = bhp_vce_order( BHP_VCE_ADAMS );
	$bhp_vce_full->add_meta_data( '_bhp_school_visit_school', 'Dallas Harris Elementary', true );

	$bhp_vce_item = new WC_Order_Item_Product();
	$bhp_vce_item->set_product_id( $bhp_vce_pid );
	$bhp_vce_full->add_item( $bhp_vce_item );

	$bhp_vce_rendered = bhp_visit_email_body( new BHP_VCE_Email( 'customer_completed_order', $bhp_vce_full ) );

	/*
	 * ⚠ SEVEN, NOT EIGHT. The set is eight paragraphs (see §4); `{VisitLine}`
	 *   is its own paragraph and this order has no registry visit line, so the
	 *   body reader drops it and the email closes up around it. ⛔ If this ever
	 *   reads 8 with no visit line written, an empty paragraph is reaching a
	 *   parent.
	 */
	bhp_vce_assert(
		7 === count( $bhp_vce_rendered ),
		'⭐ resolvable visit order => seven rendered paragraphs (eight in the set, {VisitLine} dropped)',
		$failures
	);
	bhp_vce_assert(
		'Hi there,' === $bhp_vce_rendered[0],
		'the body opens on the greeting, with {ParentFirstName} falling back to "there"',
		$failures
	);
	bhp_vce_assert(
		'Email me any time at Andrew@braveheartspublishing.com.' === $bhp_vce_rendered[6],
		'⭐ SEAL 1007: the rendered body ENDS on the reply route, not on a bare name',
		$failures
	);
	bhp_vce_assert(
		! in_array( 'Andrew', $bhp_vce_rendered, true ),
		'⛔ SEAL 1007: no rendered paragraph is the standalone word "Andrew"',
		$failures
	);
	bhp_vce_assert(
		false === strpos( implode( ' ', $bhp_vce_rendered ), '{' ),
		'⛔ every merge slot resolved; no raw {Slot} reaches a parent',
		$failures
	);
} else {
	/*
	 * ⛔ REPORTED, NOT SILENTLY SKIPPED. A skipped assertion that prints
	 *    nothing is indistinguishable from a passing one.
	 */
	echo "SKIP: no product id in bhp_book_registry(), so the rendered-body assertions did not run.
";
	$failures[] = 'rendered-body assertions could not run (no registry product id)';
}
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

/* ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.374 · SEAL 1025 · THE HAND-DELIVERY ROW IN THE DAY-0 SUMMARY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE ROW IS BUILT AS A PLAIN ARRAY HERE RATHER THAN BY RENDERING AN
 *    EMAIL. `WC_Order::get_order_item_totals()` needs a SAVED order with
 *    saved shipping line items, and this suite writes nothing to the
 *    database, by design (see the header). The function under test takes the
 *    rows array and returns a rows array, so it is tested at exactly that
 *    boundary, with the shipping value copied byte-for-byte out of the real
 *    1.19.373 staging render `REVIEW-SEQ-STAGING\rs373-day0.html` line 152.
 *
 * ⚠ STATED PLAINLY: this proves the transform. It does NOT prove the
 *   rendered email, because nothing here renders one. The rendered proof is
 *   The `chief-of-staff` desk's re-render on staging.
 */

$bhp_vce_pickup_value = 'Collection from <strong>Author hand-delivery at the Dallas Harris Elementary visit (September 3)</strong>:<br>Andrew brings the signed books to Dallas Harris Elementary on Thursday, September 3. Nothing is posted to your home, and there is no shipping charge. Visit time: 10:10 AM.';

bhp_vce_assert(
	function_exists( 'bhp_visit_email_shorten_pickup_row' ) && function_exists( 'bhp_visit_email_pickup_short_label' ),
	'⭐⭐ the day-0 shipping-row shortener and its label helper are loaded',
	$failures
);

$bhp_vce_short = function_exists( 'bhp_visit_email_pickup_short_label' )
	? bhp_visit_email_pickup_short_label( $bhp_vce_pickup_value )
	: '';

/*
 * ⛔ THE STRING IS THE APPROVED PICKUP LABEL AND NOTHING ELSE. It is
 *    `bhp_school_pickup_label()`'s own output, which the bundle plugin marks
 *    APPROVED COPY, CARRIED FORWARD BYTE-IDENTICAL. ⛔ No new customer-facing
 *    sentence is minted anywhere in this change, and this assertion is what
 *    keeps it that way.
 */
bhp_vce_assert(
	'Author hand-delivery at the Dallas Harris Elementary visit (September 3)' === $bhp_vce_short,
	'⭐⭐ the shortened label is the APPROVED pickup label, verbatim (got: ' . $bhp_vce_short . ')',
	$failures
);

bhp_vce_assert(
	false === strpos( $bhp_vce_short, 'Andrew brings the signed' )
		&& false === strpos( $bhp_vce_short, 'Visit time' )
		&& false === strpos( $bhp_vce_short, 'Collection from' ),
	'⭐ the forty-word checkout explanation and the "Collection from" prefix are gone',
	$failures
);

$bhp_vce_rows_pickup = array(
	'cart_subtotal' => array(
		'label' => 'Subtotal:',
		'value' => '$11.99',
	),
	'shipping'      => array(
		'label' => 'Hand delivery: Author hand-delivery at the Dallas Harris Elementary visit (September 3)',
		'value' => $bhp_vce_pickup_value,
	),
	'order_total'   => array(
		'label' => 'Total:',
		'value' => '$12.71',
	),
);

$bhp_vce_out = function_exists( 'bhp_visit_email_shorten_pickup_row' )
	? bhp_visit_email_shorten_pickup_row( $bhp_vce_rows_pickup, null )
	: $bhp_vce_rows_pickup;

bhp_vce_assert(
	isset( $bhp_vce_out['shipping']['value'] )
		&& false !== strpos( $bhp_vce_out['shipping']['value'], 'text-align:left' ),
	'⭐⭐ the value cell is left-aligned by a wrapper the totals filter can actually reach',
	$failures
);

bhp_vce_assert(
	isset( $bhp_vce_out['shipping']['value'] )
		&& false !== strpos( $bhp_vce_out['shipping']['value'], 'Author hand-delivery at the Dallas Harris Elementary visit (September 3)' )
		&& false === strpos( $bhp_vce_out['shipping']['value'], 'Andrew brings the signed' ),
	'⭐ the rendered value is the short label and carries no second paragraph',
	$failures
);

/*
 * ⛔ THE `<th>` STOPS DUPLICATING THE PICKUP NAME. It arrives as
 *    "Hand delivery: Author hand-delivery at the ... (September 3)" and is cut
 *    back to the plugin's own shared phrase.
 */
bhp_vce_assert(
	isset( $bhp_vce_out['shipping']['label'] ) && 'Hand delivery:' === $bhp_vce_out['shipping']['label'],
	'⭐ the row label is trimmed to "Hand delivery:" (got: ' . ( isset( $bhp_vce_out['shipping']['label'] ) ? $bhp_vce_out['shipping']['label'] : '(unset)' ) . ')',
	$failures
);

/*
 * ⛔⛔ NOTHING ELSE IN THE TABLE MOVES. No price, no tax, no total, no row
 *     order. If this ever fails, the change has stopped being cosmetic.
 */
bhp_vce_assert(
	$bhp_vce_out['cart_subtotal'] === $bhp_vce_rows_pickup['cart_subtotal']
		&& $bhp_vce_out['order_total'] === $bhp_vce_rows_pickup['order_total']
		&& array_keys( $bhp_vce_out ) === array_keys( $bhp_vce_rows_pickup ),
	'⛔ no other row, value or ordering is touched',
	$failures
);

/*
 * ⛔⛔ THE ORDINARY ORDER EMAIL IS THE ONE THAT MUST NOT CHANGE. Even if this
 *     callback were somehow attached to a posted order, the "Shipping:" label
 *     makes it a no-op. The template scoping is the first guard; this is the
 *     second, and two guards is deliberate.
 */
$bhp_vce_rows_shipped = array(
	'cart_subtotal' => array(
		'label' => 'Subtotal:',
		'value' => '$27.98',
	),
	'shipping'      => array(
		'label' => 'Shipping:',
		'value' => 'Flat rate',
	),
	'order_total'   => array(
		'label' => 'Total:',
		'value' => '$31.97',
	),
);

bhp_vce_assert(
	function_exists( 'bhp_visit_email_shorten_pickup_row' )
		&& bhp_visit_email_shorten_pickup_row( $bhp_vce_rows_shipped, null ) === $bhp_vce_rows_shipped,
	'⛔⛔ a posted, charged order ("Shipping:") passes through byte-identical',
	$failures
);

/*
 * ⛔ AND SO DOES ANYTHING THAT IS NOT A TOTALS ARRAY AT ALL. A filter that
 *    fatals on unexpected input takes the whole email with it.
 */
bhp_vce_assert(
	function_exists( 'bhp_visit_email_shorten_pickup_row' )
		&& array() === bhp_visit_email_shorten_pickup_row( array(), null )
		&& 'nope' === bhp_visit_email_shorten_pickup_row( 'nope', null ),
	'⛔ empty and non-array input are returned unchanged rather than fataling',
	$failures
);

/*
 * ⛔ A PICKUP ROW WITH NOTHING USABLE IN IT IS LEFT ALONE. Nothing is ever
 *    invented to fill an empty short label.
 */
$bhp_vce_rows_blank = array(
	'shipping' => array(
		'label' => 'Hand delivery:',
		'value' => '',
	),
);

bhp_vce_assert(
	function_exists( 'bhp_visit_email_shorten_pickup_row' )
		&& bhp_visit_email_shorten_pickup_row( $bhp_vce_rows_blank, null ) === $bhp_vce_rows_blank,
	'⛔ an empty pickup value is left exactly as WooCommerce built it',
	$failures
);

/*
 * ⭐ THE ONE-LINE SEAM IS REAL. If Andrew prefers the brief's first-choice
 *    literal, "Hand delivered at the school visit", it is this filter and no
 *    build at all. Asserted so the seam cannot rot unnoticed.
 */
$bhp_vce_seam = static function () {
	return 'Hand delivered at the school visit';
};

add_filter( 'bhp_visit_email_pickup_short_label', $bhp_vce_seam, 99 );
$bhp_vce_seamed = function_exists( 'bhp_visit_email_shorten_pickup_row' )
	? bhp_visit_email_shorten_pickup_row( $bhp_vce_rows_pickup, null )
	: array();
remove_filter( 'bhp_visit_email_pickup_short_label', $bhp_vce_seam, 99 );

bhp_vce_assert(
	isset( $bhp_vce_seamed['shipping']['value'] )
		&& false !== strpos( $bhp_vce_seamed['shipping']['value'], 'Hand delivered at the school visit' ),
	'⭐ bhp_visit_email_pickup_short_label swaps the label without a build',
	$failures
);

/*
 * ⛔⛔ THE PLUGIN IS BYTE-UNTOUCHED BY THIS ROUND. The approved label still
 *     lives there and is still produced there. If this assertion ever fails,
 *     the copy has been moved into the theme and the approval trail is broken.
 */
bhp_vce_assert(
	function_exists( 'bhp_school_pickup_label' ),
	'⛔ the approved pickup label is still produced by the bundle plugin, not by the theme',
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
