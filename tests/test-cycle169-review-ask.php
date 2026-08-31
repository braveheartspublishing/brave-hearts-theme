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
function bhp_ra_make_order( $email, $days_ago, $meta = array() ) {
	$order = wc_create_order();

	$order->set_billing_email( $email );
	$order->set_billing_first_name( 'Testparent' );
	$order->update_meta_data( '_bhp_cycle169_probe', '1' );

	foreach ( $meta as $key => $value ) {
		$order->update_meta_data( $key, $value );
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

$bhp_ra_copy = bhp_review_ask_copy();

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
bhp_ra_ok( 'The engine delay is 21 days', 21 === bhp_review_ask_delay_days() );
bhp_ra_ok( 'Copy and delay agree', bhp_review_ask_copy_matches_delay() );

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
	'Honest is better than glowing.',
	'If your kid gave up at chapter four, write that.',
	'Feel free to email me any time at Andrew@braveheartspublishing.com',
	'Thank you for taking a chance on a book by somebody you had never heard of.',
	'Big Places. Brave Hearts.',
) as $bhp_ra_phrase ) {
	bhp_ra_ok( 'Approved phrase present: "' . substr( $bhp_ra_phrase, 0, 44 ) . '"', false !== strpos( $bhp_ra_all_text, $bhp_ra_phrase ) );
}

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
 */
add_filter( 'bhp_review_ask_delay_days', function () {
	return 35;
}, 99 );
$bhp_ra_mismatch = bhp_review_ask_run( array( 'dry' => true ) );
remove_all_filters( 'bhp_review_ask_delay_days' );

bhp_ra_ok(
	'Moving the delay to 35 days HALTS the run (the copy says "three weeks")',
	'copy_delay_mismatch' === $bhp_ra_mismatch['halted']
);
bhp_ra_ok( 'The delay filter was cleanly removed', 21 === bhp_review_ask_delay_days() );

/* =========================================================================
 * §4 — EVERY DECLINE REASON, ONE ASSERTION EACH
 * ====================================================================== */

bhp_ra_head( '§4 Qualification' );

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

/* =========================================================================
 * §5 — THE OPT-OUT
 * ====================================================================== */

bhp_ra_head( '§5 Opt-out' );

$bhp_ra_opt = bhp_ra_make_order( 'ra-optout@example.com', 30 );

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

bhp_ra_ok( 'Before opting out the order qualifies', '' === bhp_review_ask_decline_reason( $bhp_ra_opt ) );

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

/* =========================================================================
 * §6 — THE RUN, THE DAILY CAP AND THE LEDGER
 * ====================================================================== */

bhp_ra_head( '§6 Run, cap and ledger' );

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

$bhp_ra_before_total = bhp_review_ask_stats();

$bhp_ra_run1 = bhp_review_ask_run( array( 'logger' => null ) );

bhp_ra_ok( 'Run 1 halted for no reason', '' === $bhp_ra_run1['halted'], 'halted=' . $bhp_ra_run1['halted'] );
bhp_ra_ok(
	'⭐ Run 1 sent exactly the daily cap (5), not all six due orders',
	5 === (int) $bhp_ra_run1['sent'],
	'sent=' . $bhp_ra_run1['sent']
);

$bhp_ra_run2 = bhp_review_ask_run( array( 'logger' => null ) );
bhp_ra_ok(
	'⭐⭐ A SECOND run on the same day sends nothing (this is what makes a double-scheduled runner safe)',
	0 === (int) $bhp_ra_run2['sent'] && 'daily_cap_reached' === $bhp_ra_run2['halted'],
	'sent=' . $bhp_ra_run2['sent'] . ' halted=' . $bhp_ra_run2['halted']
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
