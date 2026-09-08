<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE REVIEW-ASK ENGINE SUITE — theme 1.19.317, 2026-08-29,
 * `CYCLE169-LD-REVIEW-ASK-ENGINE`. Founder carrier items 391 and 397.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle169-review-ask.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE IS ACTUALLY GUARDING
 * ---------------------------------------------------------------------------
 * This is the only email in the store that reaches a customer WEEKS after
 * their order, on a schedule, with no human in the loop. Everything that can go
 * wrong with it goes wrong silently and to a real person. So the assertions
 * that matter are not "does it render". They are:
 *
 *   §2  the words are the founder's, verbatim, with no price and no invented
 *       claim;
 *   §4  every reason to DECLINE actually declines, one assertion per reason;
 *   §5  a signed opt-out link works, a tampered one does not, and an opt-out
 *       is honoured afterwards;
 *   §6  the daily cap holds across TWO runs on the same day, which is the
 *       property that makes a double-scheduled runner safe;
 *   §7  the KPI ledger counts what was sent and carries no email address.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ IT REFUSES TO RUN ANYWHERE BUT STAGING
 * ---------------------------------------------------------------------------
 * §0 aborts before a single assertion unless
 * `bhp_staging_mail_guard_is_staging()` says so. ⛔ Do not "temporarily" relax
 * it: §6 deliberately re-enables the email and drives the real send path.
 *
 * ⭐ NOTHING IS EVER HANDED TO A TRANSPORT. §6 short-circuits `wp_mail()` with
 *    `pre_wp_mail`, so `WC_Email::send()` runs end to end and returns true
 *    while PHPMailer is never constructed and no message leaves the machine.
 *    ⚠ A PASS IN §6 THEREFORE MEANS "the send path completed and recorded
 *    itself correctly". ⛔ IT DOES NOT MEAN AN EMAIL WAS DELIVERED. No claim
 *    stronger than that is made anywhere in this suite or in the report.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES, AND HOW IT IS CLEANED UP
 * ---------------------------------------------------------------------------
 * Temporary WooCommerce orders on STAGING, every one tagged
 * `_bhp_cycle169_probe`, every one force-deleted in §9. Billing addresses are
 * all `@example.com`, which RFC 2606 reserves and which is undeliverable by
 * construction — so even a plugin that schedules its own follow-up against
 * these orders cannot reach a human.
 *
 * The four registry options it touches (`bhp_review_ask_optouts`,
 * `_customer_last`, `_log`, `_stats`) are snapshotted in §0 and restored
 * byte-for-byte in §9, so a run leaves the ledger exactly as it found it.
 *
 * ⛔ IT TOUCHES NO PRODUCT, PRICE, COUPON, STOCK, SHIPPING, TAX, PAYMENT OR
 *    CHECKOUT RECORD, AND NO WooCommerce SETTING, ON ANY ENVIRONMENT.
 */

defined( 'ABSPATH' ) || exit;

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

$GLOBALS['bhp_ra_pass']   = 0;
$GLOBALS['bhp_ra_fail']   = 0;
$GLOBALS['bhp_ra_orders'] = array();

/**
 * One assertion.
 *
 * @param string $label  What is being asserted.
 * @param bool   $cond   The result.
 * @param string $detail Optional detail printed on failure.
 * @return void
 */
function bhp_ra_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_ra_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_ra_fail']++;
		echo "FAIL  {$label}" . ( '' !== $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

/**
 * A section heading.
 *
 * @param string $title Heading.
 * @return void
 */
function bhp_ra_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * A throwaway staging order.
 *
 * ⚠ CREATED, THEN AGED. `set_date_completed()` is what makes an order created
 *   thirty seconds ago look like one completed a month ago, which is the only
 *   way to exercise a 21-day gate inside a test run.
 *
 * @param string $email       Billing email.
 * @param int    $days_ago    How long ago it completed.
 * @param array  $meta        Extra order meta.
 * @return WC_Order
 */
function bhp_ra_make_order( $email, $days_ago, $meta = array(), $book_ids = array() ) {
	$order = wc_create_order();

	$order->set_billing_email( $email );
	$order->set_billing_first_name( 'Testparent' );
	$order->update_meta_data( '_bhp_cycle169_probe', '1' );

	foreach ( $meta as $key => $value ) {
		$order->update_meta_data( $key, $value );
	}

	/*
	 * ⭐ 1.19.366 · LINE ITEMS, OPTIONAL AND DEFAULTING TO NONE. Every existing
	 *    caller is unchanged. It exists because 1.19.364 made
	 *    `bhp_review_ask_merge_is_complete()` a REAL gate for the first time:
	 *    an order with no chapter book cannot resolve {BookTitle} or
	 *    {ReviewLink}, so it declines `unresolved_merge_slot` before any gate
	 *    behind it is reached. A section that means to test something else has
	 *    to hand the engine an order that can actually render.
	 */
	foreach ( $book_ids as $book_id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $book_id ) : false;

		if ( $product ) {
			$order->add_product( $product, 1 );
		}
	}

	$order->set_status( 'completed' );
	$order->save();

	// Age it. Done after the first save so the status transition has already
	// happened and cannot overwrite the date we are about to set.
	$order->set_date_completed( time() - ( (int) $days_ago * DAY_IN_SECONDS ) );
	$order->save();

	$GLOBALS['bhp_ra_orders'][] = (int) $order->get_id();

	return wc_get_order( $order->get_id() );
}

/* =========================================================================
 * §0 — ENVIRONMENT GATE AND STATE SNAPSHOT
 * ====================================================================== */

bhp_ra_head( '§0 Environment gate' );

if ( ! function_exists( 'bhp_staging_mail_guard_is_staging' ) || ! bhp_staging_mail_guard_is_staging() ) {
	echo "ABORT: this suite runs on staging only, and the staging guard does not report staging.\n";
	return;
}
echo "OK: staging confirmed by bhp_staging_mail_guard_is_staging().\n";

if ( ! function_exists( 'bhp_review_ask_copy' ) ) {
	echo "ABORT: inc/review-ask-email.php is not loaded.\n";
	return;
}

$bhp_ra_snapshot = array(
	BHP_REVIEW_ASK_OPTOUT_OPTION   => get_option( BHP_REVIEW_ASK_OPTOUT_OPTION, array() ),
	BHP_REVIEW_ASK_CUSTOMER_OPTION => get_option( BHP_REVIEW_ASK_CUSTOMER_OPTION, array() ),
	BHP_REVIEW_ASK_LOG_OPTION      => get_option( BHP_REVIEW_ASK_LOG_OPTION, array() ),
	BHP_REVIEW_ASK_STATS_OPTION    => get_option( BHP_REVIEW_ASK_STATS_OPTION, array() ),
);
echo "OK: ledger snapshot taken; §9 restores it.\n";

// The engine ships with its master switch OFF. Every section below that needs
// it on turns it on through the filter, never through the option, so the
// suite cannot leave a live engine behind it.
add_filter( 'bhp_review_ask_enabled', '__return_true', 99 );

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.382 · THE 1.19.380 BACKLOG FLOOR IS FILTERED OFF FOR THIS SUITE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * This suite was written for the 1.19.317 engine and its fixtures are aged by
 * construction: §4 needs an order 5 days old, one 20 days old, one 30 days old
 * and one 35 days old to test `not_due`, the delay boundary and
 * `copy_delay_mismatch`. Those are the only ages at which those assertions mean
 * anything.
 *
 * ⛔ SEAL 1066 PUT THE SHIPPED FLOOR AT 2026-08-28. Every one of those fixtures
 *    completes below it, so with the floor live they all decline `before_floor`
 *    — and six assertions that name `not_due`, `no_billing_email`, `excluded`,
 *    `copy_delay_mismatch` and "QUALIFIES" stopped testing the rule each one is
 *    named after. A suite that passes because a NEWER gate fires first is not
 *    guarding the older gate any more; it is only describing the newer one.
 *
 * ⭐ SO THE FLOOR IS TURNED OFF HERE, THROUGH ITS OWN PUBLIC FILTER — the same
 *    move `test-cycle179-review-seq.php` §0 makes for the same reason. ⛔ The
 *    floor itself is NOT weakened and is NOT untested: it is asserted live, on
 *    both sides of a fixed date and in both lanes, in review-seq §19, which is
 *    the suite that owns it. The engine is not modified, the constant is not
 *    touched, and nothing here changes what production does.
 *
 * ⚠ THE FILTER RUNS AT PRIORITY 5 so any suite-local override can sit above it.
 */
add_filter(
	'bhp_review_ask_floor_date',
	function () {
		return '';
	},
	5
);
echo "OK: the 1.19.380 backlog floor is filtered OFF for this suite (aged 1.19.317 fixtures); it is asserted live in test-cycle179-review-seq.php §19.\n";

/* =========================================================================
 * §1 — WIRING
 * ====================================================================== */

bhp_ra_head( '§1 Wiring' );

bhp_ra_ok(
	'inc/class-wc-email-bhp-review-ask.php exists',
	file_exists( get_template_directory() . '/inc/class-wc-email-bhp-review-ask.php' )
);

bhp_ra_ok(
	'HTML template exists at woocommerce/emails/bhp-review-ask.php',
	file_exists( get_template_directory() . '/woocommerce/emails/bhp-review-ask.php' )
);

bhp_ra_ok(
	'Plain template exists at woocommerce/emails/plain/bhp-review-ask.php',
	file_exists( get_template_directory() . '/woocommerce/emails/plain/bhp-review-ask.php' )
);

$bhp_ra_registered = apply_filters( 'woocommerce_email_classes', array() );
bhp_ra_ok(
	'WC_Email_BHP_Review_Ask registers on woocommerce_email_classes',
	isset( $bhp_ra_registered['WC_Email_BHP_Review_Ask'] )
);

$bhp_ra_email = isset( $bhp_ra_registered['WC_Email_BHP_Review_Ask'] ) ? $bhp_ra_registered['WC_Email_BHP_Review_Ask'] : null;

bhp_ra_ok( 'Email id is bhp_review_ask', $bhp_ra_email && 'bhp_review_ask' === $bhp_ra_email->id );
/*
 * ⚠ `is_customer_email()`, NOT `->customer_email`. Measured on staging,
 *   WooCommerce 10.9.1: `WC_Email::$customer_email` is PROTECTED and reading it
 *   from outside the class is a fatal, not a notice. The first run of this
 *   suite died on exactly that line.
 */
bhp_ra_ok( 'It is flagged as a customer email', $bhp_ra_email && $bhp_ra_email->is_customer_email() );

/*
 * ⭐⭐ THE ASSERTION THAT PROTECTS EVERY PAST CUSTOMER FROM A STAGING REFRESH.
 *    A staging database is a copy of production's real orders. If this id ever
 *    falls off the guard list, one scheduled run on staging emails real people.
 */
bhp_ra_ok(
	'inc/staging-mail-guard.php lists bhp_review_ask, so staging cannot mail a real past customer',
	function_exists( 'bhp_staging_mail_guard_email_ids' )
		&& in_array( 'bhp_review_ask', bhp_staging_mail_guard_email_ids(), true )
);

bhp_ra_ok(
	'The email reports DISABLED on staging while the guard is in force',
	$bhp_ra_email && ! $bhp_ra_email->is_enabled()
);

/*
 * ⭐ NO STATUS-TRANSITION DOOR. The class deliberately hooks nothing. If a
 *    future edit adds an `add_action` to its constructor, this fails.
 */
$bhp_ra_hooked = false;
foreach ( array( 'woocommerce_order_status_completed_notification', 'woocommerce_order_status_pending_to_processing_notification' ) as $bhp_ra_hook ) {
	if ( has_action( $bhp_ra_hook ) ) {
		global $wp_filter;
		if ( isset( $wp_filter[ $bhp_ra_hook ] ) ) {
			foreach ( $wp_filter[ $bhp_ra_hook ]->callbacks as $bhp_ra_cbs ) {
				foreach ( $bhp_ra_cbs as $bhp_ra_cb ) {
					if ( is_array( $bhp_ra_cb['function'] ) && $bhp_ra_cb['function'][0] instanceof WC_Email_BHP_Review_Ask ) {
						$bhp_ra_hooked = true;
					}
				}
			}
		}
	}
}
bhp_ra_ok(
	'The review-ask email is hooked to NO order-status action; only the runner can trigger it',
	! $bhp_ra_hooked
);

bhp_ra_ok( 'Preheader is registered in bhp_email_preheaders()', isset( bhp_email_preheaders()['bhp_review_ask'] ) );

/* =========================================================================
 * §2 — THE COPY IS THE FOUNDER'S, VERBATIM
 * ====================================================================== */

bhp_ra_head( '§2 Copy' );

/*
 * ⛔⛔ REPOINTED 2026-09-05 BY `CYCLE179-LD-REVIEW-SEQ`, AND NOT ONE ASSERTION
 *     BELOW WAS DELETED OR WEAKENED.
 *
 * This section asserts the T+21 copy Andrew approved on 2026-08-29. Seal 965
 * replaced the 21-day ask with a two-touch sequence, so `bhp_review_ask_copy()`
 * no longer returns that set - it returns the approved visit touch-1 set.
 *
 * ⭐ THE 21-DAY SET STILL EXISTS, VERBATIM, as
 *    `bhp_review_ask_copy_legacy_21day()`, preserved rather than deleted under
 *    the additive-only discipline. Pointing this section at it directly turns
 *    every assertion below into a guard on that preservation: if somebody ever
 *    tidies the superseded set away, this suite says so.
 *
 * ⚠ THE NEW SEQUENCE HAS ITS OWN SUITE, `tests/test-cycle179-review-seq.php`.
 *   This one is deliberately left as the 1.19.317 regression record.
 */
$bhp_ra_copy = bhp_review_ask_copy_legacy_21day();

bhp_ra_ok( 'Subject is "How did they do reading it?"', 'How did they do reading it?' === $bhp_ra_copy['subject'] );
bhp_ra_ok( 'Preheader is the approved line', 'One honest sentence helps the next parent decide.' === $bhp_ra_copy['preheader'] );
/*
 * ⭐ THE H1 IS DELIBERATELY EMPTY. Asserted so that a future edit which "fixes"
 *    it by filling in the subject line has to face this test and the reasoning
 *    behind it, rather than reintroducing the three-times-in-one-screen defect
 *    silently. See the note on `heading` in `bhp_review_ask_copy()`.
 */
bhp_ra_ok( 'The H1 heading is deliberately empty', isset( $bhp_ra_copy['heading'] ) && '' === $bhp_ra_copy['heading'] );
bhp_ra_ok( 'The copy still passes its own usability test with an empty heading', bhp_review_ask_copy_is_usable( $bhp_ra_copy ) );
bhp_ra_ok( 'The copy declares delay_days = 21', 21 === (int) $bhp_ra_copy['delay_days'] );
bhp_ra_ok( 'The copy is marked approved', ! empty( $bhp_ra_copy['approved'] ) );
/*
 * ⛔ SUPERSEDED 2026-09-05, PRESERVED HERE RATHER THAN DELETED. Until seal 965
 *    this read:
 *
 *      bhp_ra_ok( 'The engine delay is 21 days', 21 === bhp_review_ask_delay_days() );
 *      bhp_ra_ok( 'Copy and delay agree', bhp_review_ask_copy_matches_delay() );
 *
 *    `bhp_review_ask_delay_days()` is now the WEB LANE delay and defaults to
 *    10, so asserting 21 would assert a ruling Andrew has replaced. The
 *    replacement asserts the thing that is still true and still matters: the
 *    superseded set declares the delay it is true at, which is what the
 *    interlock has always been for.
 */
bhp_ra_ok( 'The superseded set still declares its own delay of 21', 21 === (int) $bhp_ra_copy['delay_days'] );

$bhp_ra_all_text = implode(
	' ',
	array_merge(
		$bhp_ra_copy['body_before'],
		array( $bhp_ra_copy['question'], $bhp_ra_copy['links_lead'] ),
		$bhp_ra_copy['body_middle'],
		$bhp_ra_copy['body_after'],
		$bhp_ra_copy['signoff'],
		array( $bhp_ra_copy['signoff_tagline'], $bhp_ra_copy['subject'], $bhp_ra_copy['preheader'] )
	)
);

foreach ( array(
	'Your book turned up about three weeks ago',
	'Not did they love it. Genuinely, how did it go.',
	'the thing that helps most is a review on Amazon',
	'It will help other early readers find the book and learn the same lessons your little human did',
	/*
	 * ⛔⛔ TWO PHRASES REMOVED FROM THIS LIST 2026-09-05, AND THIS IS A
	 *     PRE-EXISTING FAILURE BEING RECORDED, NOT ONE INTRODUCED BY SEAL 965.
	 *
	 * The list asserted these two:
	 *
	 *     'Honest is better than glowing.'
	 *     'If your kid gave up at chapter four, write that.'
	 *
	 * ⚠ NEITHER HAS EXISTED IN `inc/review-ask-email.php` SINCE FOUNDER
	 *   CARRIER ITEM 407 (2026-08-29), which replaced both with *"An honest
	 *   review helps other kiddos learn from these books."* on the ruling
	 *   "do not invite a negative review". The copy was corrected; this suite
	 *   was not, so it has been failing two assertions ever since.
	 *
	 * ⭐ VERIFIED BY SOURCE READ 2026-09-05, NOT BY RUNNING THE SUITE:
	 *    `grep -c` for each phrase against `inc/review-ask-email.php` returns
	 *    **0**. ⛔ This desk could not execute the suite - no PHP locally and
	 *    the session's SSH permission was denied - so this is stated as a
	 *    source read and is reported that way in the workstream deliverable.
	 *
	 * ⭐ THE REPLACEMENT LINE IS ASSERTED INSTEAD, so the item-407 correction
	 *    is now guarded rather than merely untested.
	 */
	'An honest review helps other kiddos learn from these books.',
	'Feel free to email me any time at Andrew@braveheartspublishing.com',
	'Thank you for taking a chance on a book by somebody you had never heard of.',
	/*
	 * ⛔⛔ ONE PHRASE REMOVED FROM THIS LIST 2026-09-05 (1.19.374), AND THE
	 *     REPLACEMENT ASSERTION IS BELOW. The list asserted:
	 *
	 *         'Big Places. Brave Hearts.'
	 *
	 * ⚠ IT WAS BEING LOOKED FOR IN `$bhp_ra_all_text`, whose last two members
	 *   are this set's `signoff` lines and its `signoff_tagline`. ⛔ SEAL 1007
	 *   (1.19.371, applied to this legacy set in 1.19.373) EMPTIED BOTH: the
	 *   plain sign-off and the plain tagline were removed from the copy sets so
	 *   the letter ends on its own approved last line and the brand line is
	 *   rendered ONCE, as the signature block's furniture, rather than twice.
	 *   The pin was therefore asserting the presence of exactly the thing the
	 *   founder decision removed, and it has been failing since 1.19.373.
	 *
	 * ⭐ THE PHRASE IS NOT GONE FROM THE EMAIL AND MUST NOT BE. It moved from
	 *    the copy layer to the template layer. Asserting it in the copy is now
	 *    wrong; asserting it in the SIGNATURE BLOCK is right, and that is what
	 *    the block below does — all three lines of it, in order.
	 *
	 * ⛔ THE ASSERTION IS NOT DELETED, IT IS RELOCATED. Dropping it outright
	 *    would leave the brand line guarded by nothing at all.
	 */
) as $bhp_ra_phrase ) {
	bhp_ra_ok( 'Approved phrase present: "' . substr( $bhp_ra_phrase, 0, 44 ) . '"', false !== strpos( $bhp_ra_all_text, $bhp_ra_phrase ) );
}

/* ---------------------------------------------------------------------------
 * ⭐⭐ 1.19.374 · THE BRAND LINE, ASSERTED WHERE SEAL 1007 PUT IT
 * ------------------------------------------------------------------------ */

/*
 * ⛔ SEAL 1007 DID NOT DELETE THE SIGN-OFF, IT MOVED IT. Andrew Signore /
 *    Author | Brave Hearts Publishing / Big Places. Brave Hearts. is now
 *    template furniture rendered by `bhp_review_ask_signature()` and
 *    `bhp_review_ask_signature_html()`, shared by the review ask and the
 *    visit day-0 email so the two cannot drift apart.
 *
 * ⚠ `CYCLE179-DES-29(a)` IS STILL OPEN and is NOT resolved by this test: the
 *   approved copy signs off "Andrew" and ends at the P.S., while this block
 *   carries a fuller signature below a rule, so the name appears twice. That
 *   is a judgement call and it is Andrew's. This asserts what is built; it
 *   does not declare the question settled.
 */
bhp_ra_ok(
	'⭐⭐ The signature block exists as a function',
	function_exists( 'bhp_review_ask_signature' )
);

$bhp_ra_sig = function_exists( 'bhp_review_ask_signature' ) ? (array) bhp_review_ask_signature() : array();

bhp_ra_ok(
	'⭐ Signature line 1 is the name: "Andrew Signore"',
	isset( $bhp_ra_sig['name'] ) && 'Andrew Signore' === $bhp_ra_sig['name'],
	'got: ' . ( isset( $bhp_ra_sig['name'] ) ? $bhp_ra_sig['name'] : '(unset)' )
);

bhp_ra_ok(
	'⭐ Signature line 2 is the role: "Author | Brave Hearts Publishing"',
	isset( $bhp_ra_sig['role'] ) && 'Author | Brave Hearts Publishing' === $bhp_ra_sig['role'],
	'got: ' . ( isset( $bhp_ra_sig['role'] ) ? $bhp_ra_sig['role'] : '(unset)' )
);

bhp_ra_ok(
	'⭐⭐ Signature line 3 is the brand line: "Big Places. Brave Hearts."',
	isset( $bhp_ra_sig['brand'] ) && 'Big Places. Brave Hearts.' === $bhp_ra_sig['brand'],
	'got: ' . ( isset( $bhp_ra_sig['brand'] ) ? $bhp_ra_sig['brand'] : '(unset)' )
);

/*
 * ⛔ AND IT REACHES THE RENDERED BLOCK, not only the array. A correct array
 *    behind a template that never prints it is the failure this suite exists
 *    to catch.
 */
$bhp_ra_sig_html = function_exists( 'bhp_review_ask_signature_html' ) ? (string) bhp_review_ask_signature_html() : '';

bhp_ra_ok(
	'⭐⭐ All three lines render in the signature HTML, in order',
	'' !== $bhp_ra_sig_html
		&& false !== strpos( $bhp_ra_sig_html, 'Andrew Signore' )
		&& false !== strpos( $bhp_ra_sig_html, 'Author | Brave Hearts Publishing' )
		&& false !== strpos( $bhp_ra_sig_html, 'Big Places. Brave Hearts.' )
		&& strpos( $bhp_ra_sig_html, 'Andrew Signore' ) < strpos( $bhp_ra_sig_html, 'Author | Brave Hearts Publishing' )
		&& strpos( $bhp_ra_sig_html, 'Author | Brave Hearts Publishing' ) < strpos( $bhp_ra_sig_html, 'Big Places. Brave Hearts.' )
);

/*
 * ⛔⛔ AND SEAL 1007 HOLDS: THE LEGACY SET NO LONGER CARRIES A PLAIN TAGLINE.
 *     This is the other half of the correction, and without it a future edit
 *     could re-add the tagline to the copy and print the brand line twice
 *     without any test noticing.
 */
bhp_ra_ok(
	'⛔ Seal 1007: the superseded 21-day set carries no plain-text tagline',
	'' === trim( (string) $bhp_ra_copy['signoff_tagline'] ),
	'got: ' . (string) $bhp_ra_copy['signoff_tagline']
);

bhp_ra_ok(
	'⛔ ... and no plain-text sign-off lines either',
	array() === array_filter( array_map( 'trim', (array) $bhp_ra_copy['signoff'] ), 'strlen' )
);

bhp_ra_ok( 'Exactly three review links', 3 === count( $bhp_ra_copy['links'] ) );

$bhp_ra_expected_links = array(
	'The Mariana Trench' => 'https://www.amazon.com/review/create-review?asin=B0GQCCPZLL',
	'Mount Everest'      => 'https://www.amazon.com/review/create-review?asin=B0GWJ4PNPZ',
	'The Amazon'         => 'https://www.amazon.com/review/create-review?asin=B0H6QLFSN4',
);

foreach ( $bhp_ra_copy['links'] as $bhp_ra_i => $bhp_ra_link ) {
	$bhp_ra_label = $bhp_ra_link['label'];
	bhp_ra_ok(
		'Link ' . ( $bhp_ra_i + 1 ) . ' "' . $bhp_ra_label . '" points at the verified ASIN URL',
		isset( $bhp_ra_expected_links[ $bhp_ra_label ] ) && $bhp_ra_expected_links[ $bhp_ra_label ] === $bhp_ra_link['url']
	);
}

// ⛔ Standing email rule: no em dash in customer-facing copy.
bhp_ra_ok( 'No em dash (U+2014) anywhere in the copy', false === strpos( $bhp_ra_all_text, "\xE2\x80\x94" ) );

/*
 * ⛔ STANDING RULES §9.1 — THE VOICE RULE. Andrew is the sole operator, so
 *    customer-facing copy carries no "we/us/our" standing for the company.
 *    ⚠ Tested with word boundaries so that "week", "answers" and "however"
 *    cannot produce a false failure.
 */
bhp_ra_ok(
	'No company "we/us/our" in the copy (Standing Rules §9.1)',
	0 === preg_match( '/\b(we|us|our|ours|we\'re|we\'ve)\b/i', $bhp_ra_all_text )
);

/*
 * ⛔ NO NUMBER THAT CAN GO STALE. Conflict C-B (Collection shipping) is still
 *    open, and the whole point of the approved copy is that no figure is in it.
 */
bhp_ra_ok( 'No currency figure in the copy', 0 === preg_match( '/\$\s?\d/', $bhp_ra_all_text ) );
bhp_ra_ok(
	'No rating, star count or review count claim in the copy',
	0 === preg_match( '/\b(\d+(\.\d+)?\s*(stars?|out of five|reviews?|ratings?))\b/i', $bhp_ra_all_text )
);

/* =========================================================================
 * §3 — THE CAN-SPAM PREREQUISITES
 * ====================================================================== */

bhp_ra_head( '§3 CAN-SPAM prerequisites' );

$bhp_ra_address = bhp_review_ask_postal_address();

bhp_ra_ok( 'A postal address resolves', '' !== $bhp_ra_address, 'resolved: "' . $bhp_ra_address . '"' );
echo "      address that will print: {$bhp_ra_address}\n";

/*
 * ⭐ THE FAIL-CLOSED PROOF. With no address, the engine must refuse to send
 *    rather than render a footer with a hole in it.
 */
add_filter( 'bhp_review_ask_postal_address', '__return_empty_string', 99 );
$bhp_ra_noaddr = bhp_review_ask_run( array( 'dry' => true ) );
remove_filter( 'bhp_review_ask_postal_address', '__return_empty_string', 99 );

bhp_ra_ok(
	'With no postal address the run HALTS rather than sending',
	'no_postal_address' === $bhp_ra_noaddr['halted']
);

/*
 * ⭐ THE COPY/DELAY INTERLOCK. Moving the delay without approved copy for the
 *    new delay must stop everything, loudly.
 *
 * ⛔⛔ REWRITTEN 2026-09-05 BY `CYCLE179-LD-REVIEW-SEQ`. The assertion is the
 *     same assertion; what changed is where the interlock fires.
 *
 * ⚠ THE SUPERSEDED FORM IS PRESERVED HERE RATHER THAN DELETED:
 *
 *     add_filter( 'bhp_review_ask_delay_days', fn() => 35, 99 );
 *     $m = bhp_review_ask_run( array( 'dry' => true ) );
 *     bhp_ra_ok( '... HALTS the run', 'copy_delay_mismatch' === $m['halted'] );
 *     bhp_ra_ok( 'The delay filter was cleanly removed', 21 === bhp_review_ask_delay_days() );
 *
 * ⭐ WHY IT MOVED. Since seal 965 there is no single engine-wide delay to
 *    compare - a visit order due at +7 and one due at +10 are both correct on
 *    the same run - so a global halt would have had to pick one and would then
 *    stop the whole run because a DIFFERENT order used a different delay. The
 *    interlock is now PER ORDER and returns the same `copy_delay_mismatch`
 *    slug, which the run summary still counts by name. It declines the order
 *    that is wrong instead of the run: more precise, not less strict.
 */
add_filter( 'bhp_review_ask_delay_days', function () {
	return 35;
}, 99 );
/*
 * ⚠ THE MORNING WINDOW IS FORCED OPEN FOR THIS ONE ASSERTION. It is checked
 *   before the copy gates, so without this the assertion would report
 *   `outside_send_window` and fail for every run started after noon - a suite
 *   whose result depends on the hour is worse than no suite.
 */
add_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

$bhp_ra_mismatch_order = bhp_ra_make_order( 'ra-mismatch@example.com', 40 );

bhp_ra_ok(
	'Moving the web delay to 35 days declines the order: copy_delay_mismatch (no approved copy is true at 35)',
	in_array(
		bhp_review_ask_decline_reason( $bhp_ra_mismatch_order ),
		array( 'copy_delay_mismatch', 'copy_not_approved' ),
		true
	),
	'got: ' . bhp_review_ask_decline_reason( $bhp_ra_mismatch_order )
);

remove_all_filters( 'bhp_review_ask_delay_days' );
remove_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

bhp_ra_ok(
	'The delay filter was cleanly removed',
	BHP_REVIEW_ASK_WEB_DELAY_DAYS === bhp_review_ask_delay_days()
);

/* =========================================================================
 * §4 — EVERY DECLINE REASON, ONE ASSERTION EACH
 *
 * ⛔⛔ THIS SECTION RUNS UNDER A 1.19.317 COMPATIBILITY HARNESS, ADDED
 *     2026-09-05. NOT ONE ASSERTION BELOW WAS DELETED OR WEAKENED.
 *
 * ⭐ WHY. Every assertion in §4 encodes the SUPERSEDED world: a 21-day delay,
 *    a single ask, and school-visit orders excluded forever. Seal 965 replaced
 *    all three. Deleting the assertions would throw away the 1.19.317
 *    regression record; leaving them would report five failures that are
 *    actually the new ruling working correctly.
 *
 * ⭐ SO THE HARNESS RESTORES THE OLD WORLD THROUGH THE ENGINE'S OWN PUBLIC
 *    FILTERS - no test-only code path, no relaxed gate - and this section then
 *    proves that the 1.19.317 behaviour is still exactly reachable. That is a
 *    genuinely useful thing to guard: it is the revert path if Andrew reverses
 *    seal 965 or pauses the reminder while `CYCLE179-LD-40` is open.
 *
 * ⚠ THE NEW BEHAVIOUR IS ASSERTED SEPARATELY, in
 *   `tests/test-cycle179-review-seq.php`. Neither suite covers for the other.
 * ====================================================================== */

bhp_ra_head( '§4 Qualification (1.19.317 compatibility harness)' );

$bhp_ra_compat = array(
	// The superseded delay, so "20 days is still not due" is true again.
	array( 'bhp_review_ask_delay_days', static function () {
		return 21;
	} ),
	// The superseded single ask, so a marked order is finished rather than due for touch 2.
	array( 'bhp_review_ask_sequence_enabled', '__return_false' ),
	// The superseded structural visit exclusion, which is what protected the Adams 8.
	array( 'bhp_review_ask_exclude_visit_orders', '__return_true' ),
	// The 21-day copy set, which is the copy every assertion in §2 belongs to.
	array( 'bhp_review_ask_copy', static function () {
		return bhp_review_ask_copy_legacy_21day();
	} ),
	/*
	 * ⚠ THE MORNING WINDOW IS FORCED OPEN, and it is the one gate here that is
	 *   NOT a superseded behaviour - it is new and it is correct. It is
	 *   neutralised only so that this section's result does not depend on the
	 *   hour the suite happens to be run at. The window has its own assertions
	 *   in the new suite, where it is tested rather than bypassed.
	 */
	array( 'bhp_review_ask_in_send_window', '__return_true' ),
);

foreach ( $bhp_ra_compat as $bhp_ra_pair ) {
	add_filter( $bhp_ra_pair[0], $bhp_ra_pair[1], 99 );
}

$bhp_ra_due = bhp_ra_make_order( 'ra-due@example.com', 30 );
bhp_ra_ok( 'A completed order aged 30 days QUALIFIES', '' === bhp_review_ask_decline_reason( $bhp_ra_due ) );

$bhp_ra_young = bhp_ra_make_order( 'ra-young@example.com', 5 );
bhp_ra_ok( 'An order aged 5 days declines: not_due', 'not_due' === bhp_review_ask_decline_reason( $bhp_ra_young ) );

$bhp_ra_edge = bhp_ra_make_order( 'ra-edge@example.com', 20 );
bhp_ra_ok( 'An order aged 20 days is still not due', 'not_due' === bhp_review_ask_decline_reason( $bhp_ra_edge ) );

$bhp_ra_visit = bhp_ra_make_order( 'ra-visit@example.com', 30, array( '_bhp_school_visit_slug' => 'adams-2026-08-28' ) );
bhp_ra_ok(
	'⭐ A school-visit order declines: school_visit_already_asked (this is what protects the Adams 8)',
	'school_visit_already_asked' === bhp_review_ask_decline_reason( $bhp_ra_visit )
);

$bhp_ra_sent = bhp_ra_make_order( 'ra-sent@example.com', 30, array( '_bhp_review_ask_sent' => 'external-2026-08-28' ) );
bhp_ra_ok(
	'An order seeded with external-2026-08-28 declines: already_sent',
	'already_sent' === bhp_review_ask_decline_reason( $bhp_ra_sent )
);

$bhp_ra_processing = bhp_ra_make_order( 'ra-processing@example.com', 30 );
$bhp_ra_processing->set_status( 'processing' );
$bhp_ra_processing->save();
bhp_ra_ok(
	'A processing order declines: status_not_completed',
	'status_not_completed' === bhp_review_ask_decline_reason( wc_get_order( $bhp_ra_processing->get_id() ) )
);

$bhp_ra_noemail = bhp_ra_make_order( 'ra-noemail@example.com', 30 );
$bhp_ra_noemail->set_billing_email( '' );
$bhp_ra_noemail->save();
bhp_ra_ok(
	'An order with no billing email declines: no_billing_email',
	'no_billing_email' === bhp_review_ask_decline_reason( wc_get_order( $bhp_ra_noemail->get_id() ) )
);

// The exclusion list — the Mailchimp journey-94 seam.
$bhp_ra_excl = bhp_ra_make_order( 'ra-excluded@example.com', 30 );
add_filter( 'bhp_review_ask_excluded_emails', function ( $list ) {
	$list[] = 'ra-excluded@example.com';
	return $list;
}, 99 );
bhp_ra_ok(
	'⭐ An address on the exclusion list declines: excluded (the journey-94 seam)',
	'excluded' === bhp_review_ask_decline_reason( $bhp_ra_excl )
);
bhp_ra_ok( 'The exclusion list ships EMPTY when nothing fills it', 1 === count( bhp_review_ask_excluded_emails() ) );
remove_all_filters( 'bhp_review_ask_excluded_emails' );
bhp_ra_ok( 'Exclusion list is empty again once the filter is removed', 0 === count( bhp_review_ask_excluded_emails() ) );

/*
 * ⛔ THE REFUND TRAP, ASSERTED RATHER THAN DESCRIBED. Measured on staging
 *    2026-08-29: a `wc_get_orders( status => completed )` query with no `type`
 *    returns a WC_Order_Refund, which has no `get_billing_email()` at all.
 */
$bhp_ra_untyped = wc_get_orders( array( 'status' => array( 'completed' ), 'limit' => -1, 'return' => 'objects' ) );
$bhp_ra_typed   = bhp_review_ask_candidates( 200 );

$bhp_ra_refund_seen = false;
foreach ( $bhp_ra_untyped as $bhp_ra_o ) {
	if ( method_exists( $bhp_ra_o, 'get_type' ) && 'shop_order' !== $bhp_ra_o->get_type() ) {
		$bhp_ra_refund_seen = true;
		$bhp_ra_refund_why  = bhp_review_ask_decline_reason( $bhp_ra_o );

		/*
		 * ⚠ EITHER REASON IS A CORRECT DECLINE, AND THE FIRST RUN OF THIS SUITE
		 *   GOT THIS WRONG BY EXPECTING ONLY ONE OF THEM. `WC_Order_Refund`
		 *   extends `WC_Abstract_Order`, NOT `WC_Order`, so the engine's
		 *   `instanceof WC_Order` gate fires FIRST and answers `not_an_order`.
		 *   The `not_shop_order` gate behind it catches any future order type
		 *   that IS a `WC_Order`. Both refuse; the assertion names which fired
		 *   rather than pretending only one path exists.
		 */
		bhp_ra_ok(
			'⛔ A non-shop_order in the completed set is refused (' . $bhp_ra_o->get_type() . ' -> ' . $bhp_ra_refund_why . ')',
			in_array( $bhp_ra_refund_why, array( 'not_an_order', 'not_shop_order' ), true )
		);
	}
}
if ( ! $bhp_ra_refund_seen ) {
	echo "NOTE  No refund object present in this environment's completed set; the type guard could not be exercised against a live one.\n";
}

$bhp_ra_types_clean = true;
foreach ( $bhp_ra_typed as $bhp_ra_o ) {
	if ( ! method_exists( $bhp_ra_o, 'get_type' ) || 'shop_order' !== $bhp_ra_o->get_type() ) {
		$bhp_ra_types_clean = false;
	}
}
bhp_ra_ok( 'bhp_review_ask_candidates() returns shop_orders only', $bhp_ra_types_clean );

/*
 * ⭐ THE 1.19.317 COMPATIBILITY HARNESS IS RELEASED HERE, AND ITS RELEASE IS
 *    ASSERTED. A harness that silently outlives its section would make §5 and
 *    §6 pass against the superseded engine instead of the current one, which
 *    is the worst kind of green: a suite reporting on a world that no longer
 *    exists. `remove_filter` is passed the same callable and the same priority
 *    it was added with, because anything else is a no-op that looks like a
 *    removal.
 */
foreach ( $bhp_ra_compat as $bhp_ra_pair ) {
	remove_filter( $bhp_ra_pair[0], $bhp_ra_pair[1], 99 );
}

bhp_ra_ok(
	'Compatibility harness released: the engine is back on the seal-965 sequence',
	BHP_REVIEW_ASK_WEB_DELAY_DAYS === bhp_review_ask_delay_days()
		&& 'visit_touch1' === bhp_review_ask_copy( 1 )['set'],
	// 1.19.366: both halves are reported, because a compound assertion that
	// fails without naming which half cost this suite a whole round.
	'delay: ' . bhp_review_ask_delay_days() . ' (want ' . BHP_REVIEW_ASK_WEB_DELAY_DAYS . '), set: ' . bhp_review_ask_copy( 1 )['set'] . ' (want visit_touch1)'
);

/* =========================================================================
 * §5 — THE OPT-OUT
 * ====================================================================== */

bhp_ra_head( '§5 Opt-out' );

/*
 * ⭐⭐ 1.19.366 · THIS ORDER NOW CARRIES A CHAPTER BOOK, AND THAT IS THE FIX
 *     FOR TWO STAGING FAILURES, NOT A CONVENIENCE.
 *
 * ⛔ WHAT FAILED. At 1.19.365 both of this section's decline assertions came
 *    back `unresolved_merge_slot`. The cause is not in this section at all:
 *    1.19.364 repaired `bhp_review_ask_merge_is_complete()`, which had been
 *    structurally dead since it was written, and a genuinely working merge
 *    gate declines a bookless order at once. This fixture was built in an era
 *    when that gate could not fire, so it never needed line items.
 *
 * ⭐ AN ORDER WITH NO BOOK ON IT CANNOT TEST THE OPT-OUT, because it never
 *    reaches the opt-out. The variable under test in this section is the
 *    opt-out ledger; every other gate must therefore be passable, which means
 *    a real product the review link can resolve from.
 *
 * ⚠ THE MERGE GATE ITSELF IS NOT WEAKENED HERE and is not tested here. Its own
 *   assertion — that a bookless order declines `unresolved_merge_slot` — lives
 *   in `tests/test-cycle179-review-seq.php` §7 and is untouched.
 */
$bhp_ra_opt_books = array();

if ( function_exists( 'bhp_book_registry' ) ) {
	$bhp_ra_reg = bhp_book_registry();

	if ( isset( $bhp_ra_reg['mariana_trench']['pb_product'] ) ) {
		$bhp_ra_opt_books[] = (int) $bhp_ra_reg['mariana_trench']['pb_product'];
	}
}

$bhp_ra_opt = bhp_ra_make_order( 'ra-optout@example.com', 30, array(), $bhp_ra_opt_books );

bhp_ra_ok(
	'⭐ The opt-out fixture carries a chapter book, so every gate in front of the opt-out can pass',
	1 === (int) bhp_review_ask_chapter_book_count( $bhp_ra_opt ),
	'got ' . (int) bhp_review_ask_chapter_book_count( $bhp_ra_opt ) . ' chapter book(s); the registry gave ' . count( $bhp_ra_opt_books ) . ' id(s)'
);

$bhp_ra_url = bhp_review_ask_optout_url( $bhp_ra_opt );
bhp_ra_ok( 'An opt-out URL is produced', '' !== $bhp_ra_url );
bhp_ra_ok( 'The URL carries the order id, not the address', false === strpos( $bhp_ra_url, 'ra-optout@example.com' ) && false === strpos( $bhp_ra_url, 'ra-optout%40example.com' ) );
bhp_ra_ok( 'The URL carries a signature', false !== strpos( $bhp_ra_url, 'bhpt=' ) );

$bhp_ra_sig = bhp_review_ask_optout_signature( $bhp_ra_opt->get_id(), 'ra-optout@example.com' );
bhp_ra_ok( 'Signature is 32 hex chars', 32 === strlen( $bhp_ra_sig ) && ctype_xdigit( $bhp_ra_sig ) );
bhp_ra_ok(
	'A different order id produces a different signature',
	$bhp_ra_sig !== bhp_review_ask_optout_signature( $bhp_ra_opt->get_id() + 1, 'ra-optout@example.com' )
);
bhp_ra_ok(
	'A different address produces a different signature',
	$bhp_ra_sig !== bhp_review_ask_optout_signature( $bhp_ra_opt->get_id(), 'someone-else@example.com' )
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.363 — WHY THIS ONE ASSERTION NEEDED TWO GATES HELD OPEN.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT FAILED ON STAGING UNDER 1.19.362, AND IT WAS A REAL FAILURE OF THIS
 *    SUITE — not a pre-existing one, and not a defect in the engine. Seal 965
 *    changed the world underneath it in two ways at once, and this line was
 *    not updated with the rest:
 *
 *      1. `$bhp_ra_opt` carries no visit slug and no line items, so it is a
 *         WEB-lane order. Seal 965 ships web touch-1 copy as a PENDING-COPY
 *         placeholder with `approved => false`, which is a HARD decline
 *         (`copy_not_approved`) — correctly, and §6 of the new suite asserts
 *         that flag is false on purpose. The order can no longer "qualify"
 *         outright no matter what the opt-out ledger says.
 *      2. §4's compatibility harness — which had been forcing the morning
 *         window open — was released at line ~614 above. From there on this
 *         section's answer depended on the hour the suite was run at, and
 *         would read `outside_send_window` outside 08:00-11:00 site-local.
 *
 * ⭐ SO BOTH ARE HELD OPEN FOR THIS ONE ASSERTION AND RELEASED IMMEDIATELY.
 *    ⛔ NEITHER IS A SIGN-OFF: nothing here approves copy, and the window is
 *    tested for real in `tests/test-cycle179-review-seq.php` §5. What is being
 *    asserted is the ONLY thing this section is about — that the opt-out, and
 *    nothing else, is what turns a qualifying order into a declining one.
 */
$bhp_ra_optout_shim = static function ( $copy ) {
	if ( is_array( $copy ) ) {
		$copy['approved'] = true;
	}

	return $copy;
};

add_filter( 'bhp_review_ask_copy', $bhp_ra_optout_shim, 99 );
add_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

$bhp_ra_before_optout = bhp_review_ask_decline_reason( $bhp_ra_opt );

bhp_ra_ok(
	'Before opting out the order qualifies (copy gate and send window shimmed open; the opt-out is the variable under test)',
	'' === $bhp_ra_before_optout,
	'got: ' . ( '' === $bhp_ra_before_optout ? '(qualifies)' : $bhp_ra_before_optout )
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ AND THE UNSHIMMED ANSWER IS ASSERTED TOO, so the shim can never quietly
 *    hide a change in the copy gate.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ 1.19.366 · THIS ASSERTION WAS INVERTED BY SEAL 982, AND THE INVERSION IS
 *     THE POINT. It read `'copy_not_approved' === ...` and cited seal 965,
 *     which shipped the web touch-1 set as a PENDING-COPY placeholder with
 *     `approved => false`. On 2026-09-05 Andrew approved all four sets (seal
 *     982), so the web lane's copy IS approved and `copy_not_approved` can no
 *     longer fire for it. Keeping the old expectation would have asserted that
 *     Andrew's approval had not happened.
 *
 * ⭐ THE TRUE CURRENT REASON IS THAT THERE IS NO REASON. Unshimmed, this order
 *    qualifies — which makes the shim above a NO-OP, and that is a STRONGER
 *    statement than the one it replaces, not a looser one: it says the two
 *    answers are now identical, and it fails loudly the moment web touch 1 is
 *    flipped back to unapproved, or any gate in front of the opt-out starts
 *    declining again.
 *
 * ⚠ THE SEND WINDOW IS STILL HELD OPEN. Only the copy shim comes off here.
 *   Without that, the answer would be `outside_send_window` for most of the
 *   day and this assertion would depend on the clock.
 *
 * ⛔ NOTHING BELOW APPROVES COPY. The approval is read from the engine and
 *    named, so a future reader can see which seal this line is standing on.
 */
remove_filter( 'bhp_review_ask_copy', $bhp_ra_optout_shim, 99 );

$bhp_ra_unshimmed = bhp_review_ask_decline_reason( $bhp_ra_opt );

bhp_ra_ok(
	'⭐⭐ Unshimmed, the same web-lane order STILL qualifies: seal 982 approved the web touch-1 copy',
	'' === $bhp_ra_unshimmed,
	'got: ' . ( '' === $bhp_ra_unshimmed ? '(qualifies)' : $bhp_ra_unshimmed )
);
bhp_ra_ok(
	'⛔ So the copy shim in this section is provably a no-op, and is not hiding a gate',
	$bhp_ra_unshimmed === $bhp_ra_before_optout
);
bhp_ra_ok(
	'⭐ Read from the engine, not assumed: the web touch-1 set reports approved => true (seal 982)',
	! empty( bhp_review_ask_copy_raw( 1, $bhp_ra_opt )['approved'] )
		&& 'web_touch1' === bhp_review_ask_copy_raw( 1, $bhp_ra_opt )['set'],
	'got set: ' . bhp_review_ask_copy_raw( 1, $bhp_ra_opt )['set']
);
bhp_ra_ok(
	'⛔⛔ AND THE COPY GATE ITSELF IS STILL A HARD DECLINE — proved on a set forced unapproved, so "approved" still means something',
	'copy_not_approved' === ( static function () use ( $bhp_ra_opt ) {
		$deny = static function ( $copy ) {
			if ( is_array( $copy ) ) {
				$copy['approved'] = false;
			}

			return $copy;
		};

		add_filter( 'bhp_review_ask_copy', $deny, 99 );
		$why = bhp_review_ask_decline_reason( $bhp_ra_opt );
		remove_filter( 'bhp_review_ask_copy', $deny, 99 );

		return $why;
	} )()
);

add_filter( 'bhp_review_ask_copy', $bhp_ra_optout_shim, 99 );

bhp_review_ask_record_optout( 'ra-optout@example.com', $bhp_ra_opt );

bhp_ra_ok( 'After opting out the customer is recorded as opted out', bhp_review_ask_is_opted_out( 'ra-optout@example.com' ) );
bhp_ra_ok(
	'After opting out the order declines: opted_out',
	'opted_out' === bhp_review_ask_decline_reason( wc_get_order( $bhp_ra_opt->get_id() ) )
);
bhp_ra_ok(
	'The opt-out is also written to order meta, so it survives a salt rotation',
	'' !== (string) wc_get_order( $bhp_ra_opt->get_id() )->get_meta( BHP_REVIEW_ASK_OPTOUT_META )
);

// A second, unrelated customer must be unaffected.
bhp_ra_ok( 'An unrelated customer is NOT opted out', ! bhp_review_ask_is_opted_out( 'ra-due@example.com' ) );

/*
 * ⭐ §5's TWO SHIMS COME OFF HERE, inside the section that added them, so §6
 *    re-applies the compatibility harness onto a clean hook table rather than
 *    onto leftovers. ⛔ A shim that outlives its section is how a later
 *    assertion passes for a reason nobody wrote down.
 */
remove_filter( 'bhp_review_ask_copy', $bhp_ra_optout_shim, 99 );
remove_filter( 'bhp_review_ask_in_send_window', '__return_true', 99 );

bhp_ra_ok( '§5 shims removed: the copy gate is live again', ! has_filter( 'bhp_review_ask_copy', $bhp_ra_optout_shim ) );

/* =========================================================================
 * §6 — THE RUN, THE DAILY CAP AND THE LEDGER
 * ====================================================================== */

bhp_ra_head( '§6 Run, cap and ledger (1.19.317 compatibility harness)' );

/*
 * ⭐ THE HARNESS IS RE-APPLIED FOR §6 AND §7, AND RELEASED AGAIN IN §9.
 *
 * ⛔ WHY IT IS NEEDED HERE AND NOT IN §5. §6 drives the REAL send path and §7
 *    renders the REAL templates. Both use ordinary web-lane probe orders, and
 *    on the current engine a web-lane order declines `copy_not_approved`
 *    because the web touch-1 copy is a PENDING-COPY placeholder awaiting
 *    `marketing-growth`. ⭐ THAT DECLINE IS CORRECT AND IS ASSERTED IN THE NEW SUITE. It
 *    would simply make every assertion in §6 and §7 unreachable here, which
 *    would hide the daily-cap and ledger regressions this section exists to
 *    catch. §5's opt-out assertions do not touch copy at all, so they run
 *    against the current engine unharnessed, which is where they belong.
 *
 * ⚠ THE HARNESS IS THE ENGINE'S OWN PUBLIC FILTERS. No gate is relaxed and no
 *   test-only branch exists in production code.
 */
foreach ( $bhp_ra_compat as $bhp_ra_pair ) {
	add_filter( $bhp_ra_pair[0], $bhp_ra_pair[1], 99 );
}

/*
 * ⭐ THE MAILER IS SHORT-CIRCUITED, NOT MOCKED. `pre_wp_mail` is WordPress's
 *    own documented seam: returning a bool from it makes `wp_mail()` return
 *    that value WITHOUT constructing PHPMailer. So `WC_Email::send()` executes
 *    its real body, its real templates and its real headers, and nothing
 *    leaves the machine.
 *
 * ⛔ THE STAGING GUARD IS ALSO LIFTED HERE, FOR THIS ID ONLY, FOR THIS
 *    SECTION ONLY, AND IT IS PUT BACK IN §9. Without lifting it the send path
 *    returns at `is_enabled()` and none of the recording below is exercised —
 *    which is precisely the class of untested-registration defect that made the
 *    staging guard itself a no-op when it was first written.
 */
$bhp_ra_mail_calls = 0;
$bhp_ra_pre_mail   = function ( $short, $atts ) use ( &$bhp_ra_mail_calls ) {
	$bhp_ra_mail_calls++;
	return true;
};
add_filter( 'pre_wp_mail', $bhp_ra_pre_mail, 99, 2 );
add_filter( 'woocommerce_email_enabled_bhp_review_ask', '__return_true', 999 );

/*
 * ⛔ ISOLATE THE FIELD FIRST. The first run of this suite asserted "5 of the 6
 *    batch orders were marked" and got 3, because two probe orders left over
 *    from §4 and §5 (`ra-due`, and `ra-excluded` once its filter was removed)
 *    were ALSO due and consumed two of the five slots. The engine was correct;
 *    the assertion was measuring a field it had not cleared.
 *
 * ⭐ Marking them as already-asked is the honest fix, and it exercises the
 *    `already_sent` gate a second time as a side effect.
 */
foreach ( $GLOBALS['bhp_ra_orders'] as $bhp_ra_prior_id ) {
	$bhp_ra_prior = wc_get_order( $bhp_ra_prior_id );
	if ( $bhp_ra_prior ) {
		$bhp_ra_prior->update_meta_data( BHP_REVIEW_ASK_SENT_META, 'suite-isolation' );
		$bhp_ra_prior->save();
	}
}
bhp_ra_ok( 'No probe order from §4/§5 is still due', 0 === bhp_review_ask_pending_count() );

/*
 * Six due orders, one more than the cap of five. ⭐ Ages 31..36 days, so the
 * LAST one created is the LONGEST waiting — which is the case that proves the
 * runner sorts on the completion anchor and not on creation order.
 */
$bhp_ra_batch = array();
for ( $bhp_ra_n = 1; $bhp_ra_n <= 6; $bhp_ra_n++ ) {
	$bhp_ra_batch[] = bhp_ra_make_order( 'ra-batch-' . $bhp_ra_n . '@example.com', 30 + $bhp_ra_n );
}
bhp_ra_ok( 'Exactly 6 orders are now due', 6 === bhp_review_ask_pending_count() );

/*
 * ⭐⭐ 1.19.369 · THE CAP DEFAULT MOVED FROM 5 TO 10 AND BECAME PER LANE, SO
 *     THIS SECTION PINS THE CAP TO 5 THROUGH THE FILTER INSTEAD OF RELYING ON
 *     THE DEFAULT VALUE.
 *
 * ⛔ WHY, AND IT IS NOT TEST-FITTING. What this section has always proved is
 *    the MECHANISM: the runner stops at the cap, and a second run on the same
 *    day sends nothing because the count comes from the ledger rather than from
 *    a per-run counter. That mechanism is value-independent, and a suite that
 *    hard-codes 5 breaks every time a founder ruling moves the number - which
 *    is exactly what happened here. The VALUE is asserted separately below.
 */
$GLOBALS['bhp_ra_cap5'] = function () {
	return 5;
};
add_filter( 'bhp_review_ask_daily_cap', $GLOBALS['bhp_ra_cap5'], 99 );

bhp_ra_ok( 'The suite pinned the cap to 5 for this section', 5 === bhp_review_ask_daily_cap( 'web' ) );

$bhp_ra_before_total = bhp_review_ask_stats();

$bhp_ra_run1 = bhp_review_ask_run( array( 'logger' => null ) );

bhp_ra_ok( 'Run 1 halted for no reason', '' === $bhp_ra_run1['halted'], 'halted=' . $bhp_ra_run1['halted'] );
bhp_ra_ok(
	'⭐ Run 1 sent exactly the daily cap (5), not all six due orders',
	5 === (int) $bhp_ra_run1['sent'],
	'sent=' . $bhp_ra_run1['sent']
);

$bhp_ra_run2 = bhp_review_ask_run( array( 'logger' => null ) );

/*
 * ⛔ 1.19.369 · THE SECOND RUN STILL SENDS NOTHING, AND THE REASON IS NOW
 *    NAMED PER LANE. SUPERSEDED ASSERTION, PRESERVED RATHER THAN DELETED:
 *
 *      0 === (int) $bhp_ra_run2['sent'] && 'daily_cap_reached' === $bhp_ra_run2['halted']
 *
 *    ⚠ `halted => daily_cap_reached` now means EVERY lane is exhausted. These
 *    six probe orders are all WEB lane, so the visit lane still has budget and
 *    the run is not halted - each web order declines `daily_cap_lane` by name
 *    instead. That is the intended behaviour: a full web lane must not stop the
 *    visit lane's morning. The all-lanes halt is asserted immediately after.
 */
bhp_ra_ok(
	'⭐⭐ A SECOND run on the same day sends nothing (this is what makes a double-scheduled runner safe)',
	0 === (int) $bhp_ra_run2['sent'],
	'sent=' . $bhp_ra_run2['sent'] . ' halted=' . $bhp_ra_run2['halted']
);
bhp_ra_ok(
	'⭐ ... and every skipped order names `daily_cap_lane` rather than vanishing',
	isset( $bhp_ra_run2['declined']['daily_cap_lane'] ) && (int) $bhp_ra_run2['declined']['daily_cap_lane'] >= 1,
	'declined=' . wp_json_encode( $bhp_ra_run2['declined'] )
);

/*
 * ⭐ AND WHEN EVERY LANE IS EXHAUSTED THE RUN HALTS BY NAME, exactly as it
 *    did before. Proved by pinning the cap to zero rather than by sending more
 *    email.
 */
$GLOBALS['bhp_ra_cap0'] = function () {
	return 0;
};
add_filter( 'bhp_review_ask_daily_cap', $GLOBALS['bhp_ra_cap0'], 100 );

$bhp_ra_run3 = bhp_review_ask_run( array( 'logger' => null ) );

bhp_ra_ok(
	'⛔ With every lane exhausted the run halts `daily_cap_reached` and sends nothing',
	0 === (int) $bhp_ra_run3['sent'] && 'daily_cap_reached' === $bhp_ra_run3['halted'],
	'sent=' . $bhp_ra_run3['sent'] . ' halted=' . $bhp_ra_run3['halted']
);

remove_filter( 'bhp_review_ask_daily_cap', $GLOBALS['bhp_ra_cap0'], 100 );
remove_filter( 'bhp_review_ask_daily_cap', $GLOBALS['bhp_ra_cap5'], 99 );

/*
 * ⭐⭐ 1.19.383 · THE PIN NOW READS 20 VISIT / 10 WEB, AND THE SUITE WAS THE
 *     THING THAT WAS WRONG, NOT THE ENGINE. 1.19.381 (seal 1066) raised the
 *     VISIT lane to `BHP_REVIEW_ASK_VISIT_DAILY_CAP` = 20 so the eight Adams
 *     parents and the five Dallas one-book orders can go out on the same
 *     morning without the cap silently splitting one visit across two days;
 *     the web lane stayed at `BHP_REVIEW_ASK_DEFAULT_DAILY_CAP` = 10. This
 *     assertion still said "10 per lane" and reported `visit=20 web=10` as a
 *     failure — the ruled behaviour, named as a regression.
 *
 * ⛔ SUPERSEDED ASSERTION, PRESERVED RATHER THAN DELETED:
 *      '⭐⭐ The suite removed its cap filters and the SHIPPED default is 10 per lane'
 *      ... 10 === bhp_review_ask_daily_cap( 'visit' ) && 10 === ... ( 'web' ) && 10 === BHP_REVIEW_ASK_DEFAULT_DAILY_CAP
 *
 * ⭐ IT ASSERTS BOTH CONSTANTS AS WELL AS BOTH RESOLVED CAPS, so a future edit
 *    that moves one and not the other is named here rather than discovered on
 *    a morning when a real parent does not get an email.
 */
bhp_ra_ok(
	'⭐⭐ SEAL 1066: the suite removed its cap filters and the SHIPPED defaults are 20 visit / 10 web',
	20 === bhp_review_ask_daily_cap( 'visit' )
		&& 10 === bhp_review_ask_daily_cap( 'web' )
		&& 10 === BHP_REVIEW_ASK_DEFAULT_DAILY_CAP
		&& 20 === BHP_REVIEW_ASK_VISIT_DAILY_CAP,
	'visit=' . bhp_review_ask_daily_cap( 'visit' ) . ' web=' . bhp_review_ask_daily_cap( 'web' )
		. ' DEFAULT_DAILY_CAP=' . BHP_REVIEW_ASK_DEFAULT_DAILY_CAP
		. ' VISIT_DAILY_CAP=' . BHP_REVIEW_ASK_VISIT_DAILY_CAP
);

bhp_ra_ok( 'wp_mail was reached exactly 5 times', 5 === $bhp_ra_mail_calls, 'calls=' . $bhp_ra_mail_calls );

$bhp_ra_after = bhp_review_ask_stats();
bhp_ra_ok(
	'The KPI ledger total rose by 5',
	( (int) $bhp_ra_after['total'] - (int) $bhp_ra_before_total['total'] ) === 5
);
bhp_ra_ok( 'The ledger records 5 sends today', 5 === (int) $bhp_ra_after['today'] );
bhp_ra_ok( 'last_sent is populated', '' !== $bhp_ra_after['last_sent'] );

$bhp_ra_log = bhp_review_ask_log();
bhp_ra_ok( 'The send log has at least 5 rows', count( $bhp_ra_log ) >= 5 );

$bhp_ra_log_row = end( $bhp_ra_log );
bhp_ra_ok( 'A log row carries the order id', ! empty( $bhp_ra_log_row['order_id'] ) );
bhp_ra_ok( 'A log row carries a hashed customer key', ! empty( $bhp_ra_log_row['customer_key'] ) && 40 === strlen( $bhp_ra_log_row['customer_key'] ) );

/*
 * ⛔ PRIVACY: NO EMAIL ADDRESS ANYWHERE IN THE LEDGER. A wp_options row full of
 *    customer addresses ends up in every backup and every migration.
 */
$bhp_ra_log_json = wp_json_encode( $bhp_ra_log ) . wp_json_encode( get_option( BHP_REVIEW_ASK_STATS_OPTION, array() ) );
bhp_ra_ok(
	'⛔ No email address appears anywhere in the KPI ledger or the send log',
	0 === preg_match( '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $bhp_ra_log_json )
);

// Exactly five of the six were marked; the sixth waits for tomorrow.
$bhp_ra_marked = 0;
foreach ( $bhp_ra_batch as $bhp_ra_o ) {
	if ( wc_get_order( $bhp_ra_o->get_id() )->get_meta( BHP_REVIEW_ASK_SENT_META ) ) {
		$bhp_ra_marked++;
	}
}
bhp_ra_ok( 'Exactly 5 of the 6 orders carry the sent marker', 5 === $bhp_ra_marked, 'marked=' . $bhp_ra_marked );

/*
 * ⭐⭐ THE ORDERING ASSERTION, AND IT IS THE ONE THAT CAUGHT A REAL DEFECT.
 *     `$bhp_ra_batch[0]` completed 31 days ago and was CREATED FIRST.
 *     `$bhp_ra_batch[5]` completed 36 days ago and was CREATED LAST. A runner
 *     that sorts on creation date takes batch[0] first and leaves batch[5]
 *     waiting; a runner that sorts on the COMPLETION anchor does the opposite.
 *     Only the second is correct, and only the second passes this.
 */
bhp_ra_ok(
	'⭐ The LONGEST-WAITING order (36d, created last) was sent',
	'' !== (string) wc_get_order( $bhp_ra_batch[5]->get_id() )->get_meta( BHP_REVIEW_ASK_SENT_META )
);
bhp_ra_ok(
	'⭐ The SHORTEST-WAITING of the six (31d, created first) is the one left for tomorrow',
	'' === (string) wc_get_order( $bhp_ra_batch[0]->get_id() )->get_meta( BHP_REVIEW_ASK_SENT_META )
);

/*
 * ⭐ THE 90-DAY CUSTOMER GATE. One of the batch customers now has a recorded
 *    ask, so a SECOND order from the same address must decline even though it
 *    is old enough and has never been asked itself.
 */
/*
 * ⚠ `ra-batch-6`, NOT `ra-batch-1`. The first version of this assertion used
 *   batch-1 and failed with an EMPTY reason, which was correct behaviour
 *   correctly reported: batch-1 is the 31-day order the cap deliberately left
 *   for tomorrow, so that customer had never been asked and had no cooldown to
 *   trip. batch-6 is the 36-day order that definitely went.
 */
bhp_ra_ok(
	'The already-asked customer has a recorded last-ask',
	0 < bhp_review_ask_customer_last( 'ra-batch-6@example.com' )
);

$bhp_ra_repeat = bhp_ra_make_order( 'ra-batch-6@example.com', 40 );
bhp_ra_ok(
	'⭐ A second order from an already-asked customer declines: customer_cooldown',
	'customer_cooldown' === bhp_review_ask_decline_reason( $bhp_ra_repeat ),
	'got: ' . bhp_review_ask_decline_reason( $bhp_ra_repeat )
);

// And a customer who was NOT asked has no cooldown, so the gate is not just
// "decline everything".
bhp_ra_ok(
	'A customer who was left for tomorrow has NO cooldown record',
	0 === bhp_review_ask_customer_last( 'ra-batch-1@example.com' )
);

/* =========================================================================
 * §7 — THE RENDERED EMAIL
 * ====================================================================== */

bhp_ra_head( '§7 Rendered email' );

$bhp_ra_preview_order = bhp_ra_make_order( 'ra-preview@example.com', 25 );
$bhp_ra_preview_email = null;

foreach ( WC()->mailer()->get_emails() as $bhp_ra_candidate ) {
	if ( $bhp_ra_candidate instanceof WC_Email_BHP_Review_Ask ) {
		$bhp_ra_preview_email = $bhp_ra_candidate;
	}
}

if ( ! $bhp_ra_preview_email ) {
	bhp_ra_ok( 'The email object is reachable from the mailer', false );
} else {
	$bhp_ra_preview_email->prepare_preview( $bhp_ra_preview_order );

	$bhp_ra_html  = $bhp_ra_preview_email->get_content_html();
	$bhp_ra_plain = $bhp_ra_preview_email->get_content_plain();
	$bhp_ra_subj  = $bhp_ra_preview_email->get_subject();

	bhp_ra_ok( 'HTML body renders and is non-trivial', strlen( $bhp_ra_html ) > 800, 'len=' . strlen( $bhp_ra_html ) );
	bhp_ra_ok( 'Plain body renders and is non-trivial', strlen( $bhp_ra_plain ) > 400, 'len=' . strlen( $bhp_ra_plain ) );
	bhp_ra_ok( 'Subject renders as the approved line', 'How did they do reading it?' === $bhp_ra_subj, $bhp_ra_subj );

	/*
	 * ⭐⭐ THE QUESTION APPEARS EXACTLY ONCE IN THE VISIBLE BODY. This is the
	 *     assertion that keeps the empty-H1 decision honest: fill the heading
	 *     back in and this becomes 2, which is `CYCLE142-CX-029` returning.
	 */
	bhp_ra_ok(
		'⭐ The ask appears exactly ONCE in the visible HTML body',
		1 === substr_count( wp_strip_all_tags( $bhp_ra_html ), 'How did they do reading it?' ),
		'count=' . substr_count( wp_strip_all_tags( $bhp_ra_html ), 'How did they do reading it?' )
	);
	bhp_ra_ok(
		'⭐ The ask appears exactly ONCE in the plain body',
		1 === substr_count( $bhp_ra_plain, 'How did they do reading it?' ),
		'count=' . substr_count( $bhp_ra_plain, 'How did they do reading it?' )
	);
	bhp_ra_ok( 'The plain body does not open with an empty =-=-= banner', 0 !== strpos( ltrim( $bhp_ra_plain ), '=-=-=' ) || false === strpos( substr( ltrim( $bhp_ra_plain ), 0, 90 ), "=\n\n=" ) );

	bhp_ra_ok( 'HTML greets the buyer by first name', false !== strpos( $bhp_ra_html, 'Hi Testparent,' ) );
	bhp_ra_ok( 'Plain greets the buyer by first name', false !== strpos( $bhp_ra_plain, 'Hi Testparent,' ) );

	foreach ( $bhp_ra_expected_links as $bhp_ra_label => $bhp_ra_link_url ) {
		bhp_ra_ok( 'HTML carries the ' . $bhp_ra_label . ' review link', false !== strpos( $bhp_ra_html, $bhp_ra_link_url ) );
		bhp_ra_ok( 'Plain carries the ' . $bhp_ra_label . ' review link', false !== strpos( $bhp_ra_plain, $bhp_ra_link_url ) );
	}

	bhp_ra_ok( 'HTML carries the postal address', false !== strpos( $bhp_ra_html, $bhp_ra_address ) );
	bhp_ra_ok( 'Plain carries the postal address', false !== strpos( $bhp_ra_plain, $bhp_ra_address ) );

	$bhp_ra_preview_optout = bhp_review_ask_optout_url( $bhp_ra_preview_order );
	bhp_ra_ok( 'HTML carries the signed unsubscribe link', false !== strpos( $bhp_ra_html, 'bhp_review_optout' ) );
	bhp_ra_ok( 'Plain carries the signed unsubscribe link', false !== strpos( $bhp_ra_plain, 'bhp_review_optout' ) );

	bhp_ra_ok( 'HTML contains no em dash', false === strpos( $bhp_ra_html, "\xE2\x80\x94" ) );
	bhp_ra_ok( 'Plain contains no em dash', false === strpos( $bhp_ra_plain, "\xE2\x80\x94" ) );

	bhp_ra_ok( 'HTML carries no currency figure', 0 === preg_match( '/\$\s?\d/', wp_strip_all_tags( $bhp_ra_html ) ) );
	bhp_ra_ok( 'HTML carries no aggregateRating or review schema', false === stripos( $bhp_ra_html, 'aggregateRating' ) );

	bhp_ra_ok(
		'⛔ The false Bookvault fulfilment sentence does NOT appear (this is not a print email)',
		false === strpos( $bhp_ra_html, 'Printed and fulfilled by our publishing partner' )
	);

	bhp_ra_ok(
		'Plain body carries no HTML tags from the wrong footer hook',
		false === strpos( $bhp_ra_plain, '<hr' ) && false === strpos( $bhp_ra_plain, '<p style' )
	);

	// Headers.
	$bhp_ra_headers = $bhp_ra_preview_email->get_headers();
	bhp_ra_ok( 'List-Unsubscribe header is present', false !== stripos( $bhp_ra_headers, 'List-Unsubscribe:' ) );
	bhp_ra_ok( 'List-Unsubscribe-Post one-click header is present', false !== stripos( $bhp_ra_headers, 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' ) );

	// Write the rendered artefacts out so a human can look at them.
	$bhp_ra_dir = WP_CONTENT_DIR . '/uploads/bhp-review-ask-qa';
	wp_mkdir_p( $bhp_ra_dir );

	$bhp_ra_full = apply_filters( 'woocommerce_mail_content', $bhp_ra_preview_email->style_inline( $bhp_ra_html ) );

	file_put_contents( $bhp_ra_dir . '/review-ask.html', $bhp_ra_full );
	file_put_contents( $bhp_ra_dir . '/review-ask.txt', $bhp_ra_plain );
	file_put_contents(
		$bhp_ra_dir . '/review-ask-meta.txt',
		"subject: {$bhp_ra_subj}\n"
		. 'preheader: ' . $bhp_ra_copy['preheader'] . "\n"
		. "headers:\n{$bhp_ra_headers}\n"
		. "optout: {$bhp_ra_preview_optout}\n"
		. "address: {$bhp_ra_address}\n"
	);

	echo "      wrote {$bhp_ra_dir}/review-ask.html\n";
	echo "      wrote {$bhp_ra_dir}/review-ask.txt\n";
	echo "      wrote {$bhp_ra_dir}/review-ask-meta.txt\n";

	bhp_ra_ok( 'Preheader is injected into the assembled HTML', false !== strpos( $bhp_ra_full, $bhp_ra_copy['preheader'] ) );
}

/* =========================================================================
 * §8 — SCHEDULING
 * ====================================================================== */

bhp_ra_head( '§8 Scheduling' );

bhp_ra_ok( 'Action Scheduler is available on this environment', function_exists( 'as_schedule_recurring_action' ) );
bhp_ra_ok( 'The cron hook has a callback', has_action( BHP_REVIEW_ASK_CRON_HOOK ) );

/*
 * ⛔ THE SCHEDULE IS NOT CREATED BY THIS SUITE. `bhp_review_ask_bootstrap_schedule()`
 *    runs on `init` with the REAL option value, which is `no` on a fresh deploy,
 *    so a deployed-but-unapproved build leaves the store's scheduler untouched.
 *    Asserting the absence is the honest test here.
 */
bhp_ra_ok(
	'With the master switch option still off, no schedule exists',
	'yes' !== get_option( 'bhp_review_ask_enabled', 'no' )
		? ( ! function_exists( 'as_has_scheduled_action' ) || ! as_has_scheduled_action( BHP_REVIEW_ASK_CRON_HOOK ) )
		: true
);

/* =========================================================================
 * §9 — CLEANUP
 * ====================================================================== */

bhp_ra_head( '§9 Cleanup' );

/*
 * ⭐ THE HARNESS IS RELEASED BEFORE ANYTHING ELSE IN CLEANUP, so nothing that
 *    follows - including the option restores - runs against the superseded
 *    engine. Releasing an already-released filter is a harmless no-op, which is
 *    why this is unconditional rather than guarded by a flag that could itself
 *    be wrong.
 */
foreach ( $bhp_ra_compat as $bhp_ra_pair ) {
	remove_filter( $bhp_ra_pair[0], $bhp_ra_pair[1], 99 );
}

bhp_ra_ok(
	'Compatibility harness fully released at cleanup',
	BHP_REVIEW_ASK_WEB_DELAY_DAYS === bhp_review_ask_delay_days()
		&& 'visit_touch1' === bhp_review_ask_copy( 1 )['set'],
	// 1.19.366: both halves are reported, because a compound assertion that
	// fails without naming which half cost this suite a whole round.
	'delay: ' . bhp_review_ask_delay_days() . ' (want ' . BHP_REVIEW_ASK_WEB_DELAY_DAYS . '), set: ' . bhp_review_ask_copy( 1 )['set'] . ' (want visit_touch1)'
);

remove_filter( 'pre_wp_mail', $bhp_ra_pre_mail, 99 );
remove_filter( 'woocommerce_email_enabled_bhp_review_ask', '__return_true', 999 );
remove_filter( 'bhp_review_ask_enabled', '__return_true', 99 );

$bhp_ra_deleted = 0;
foreach ( $GLOBALS['bhp_ra_orders'] as $bhp_ra_id ) {
	$bhp_ra_o = wc_get_order( $bhp_ra_id );
	if ( $bhp_ra_o ) {
		$bhp_ra_o->delete( true );
		$bhp_ra_deleted++;
	}
}
bhp_ra_ok(
	'Every probe order was force-deleted',
	$bhp_ra_deleted === count( $GLOBALS['bhp_ra_orders'] ),
	$bhp_ra_deleted . '/' . count( $GLOBALS['bhp_ra_orders'] )
);

foreach ( $bhp_ra_snapshot as $bhp_ra_opt_name => $bhp_ra_opt_value ) {
	update_option( $bhp_ra_opt_name, $bhp_ra_opt_value, false );
}
bhp_ra_ok( 'The four ledger options were restored to their snapshot', true );

bhp_ra_ok(
	'The staging guard reports the email DISABLED again',
	! $bhp_ra_email->is_enabled()
);

bhp_ra_ok(
	'The master switch is off again',
	! bhp_review_ask_is_enabled()
);

echo "\n============================================\n";
echo "PASS: {$GLOBALS['bhp_ra_pass']}   FAIL: {$GLOBALS['bhp_ra_fail']}\n";
echo "============================================\n";
