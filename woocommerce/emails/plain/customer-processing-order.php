<?php
/**
 * Customer processing order email (plain text) - Brave Hearts override (E1).
 *
 * Overrides woocommerce/templates/emails/plain/customer-processing-order.php.
 * Source template version at the time of the override: 9.9.0
 * (WooCommerce 10.9.1 ships that @version).
 *
 * This exists so a plain-text recipient reads the SAME honest copy as the
 * HTML recipient. Without it, the store's HTML email would carry the E1
 * expectation-setting copy and its plain-text twin would silently fall back
 * to WooCommerce's stock "we've received your order" wording - two different
 * promises from one order. The full reasoning, the empty timing slot and the
 * never-add-a-number rule are documented in the HTML template alongside this
 * file; they apply here identically.
 *
 * ⛔ NO ELAPSED-TIME CLAIM. ⛔ NO TRACKING PROMISE. ⛔ NO COUPON, NO UPSELL.
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'brave-hearts' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi,', 'brave-hearts' ) . "\n\n";
}

echo esc_html__( 'Thank you - your order is confirmed. Here is exactly what happens now, and when you’ll hear from us again.', 'brave-hearts' ) . "\n\n";

echo esc_html__( 'WHAT YOU ORDERED', 'brave-hearts' ) . "\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.231 (2026-08-17, `CYCLE162-LD-SCHOOL-PICKUP`) — THE HAND-DELIVERY
 *    BRANCH, MIRRORING THE HTML SIBLING EXACTLY.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS FILE'S OWN HEADER RECORDS THAT THESE TWO TEMPLATES HAVE DRIFTED
 *    APART BEFORE (C10, 2026-08-03 — the plain twin was one sentence
 *    short, so plain-text and HTML recipients of the same order read two
 *    different promises). That is exactly the failure a shipping-vs-
 *    hand-delivery branch would repeat at ten times the cost, so the
 *    branch is added here in the same sitting, with the same predicate,
 *    the same guard and the same strings.
 *
 * ⛔ THE `else` BRANCH IS THE APPROVED, LOCKED COPY, BYTE-FOR-BYTE
 *    UNCHANGED. Every ordinary order still reads exactly what it read
 *    before this release.
 */
$bhp_is_pickup = function_exists( 'bhp_school_pickup_order_is_pickup' ) && bhp_school_pickup_order_is_pickup( $order );

if ( $bhp_is_pickup ) {
	$bhp_pickup_school = (string) $order->get_meta( '_bhp_school_visit_school' );
	$bhp_pickup_date   = (string) $order->get_meta( '_bhp_school_visit_date' );
	$bhp_pickup_ts     = $bhp_pickup_date ? strtotime( $bhp_pickup_date . ' 12:00:00' ) : false;
	$bhp_pickup_pretty = $bhp_pickup_ts ? wp_date( 'l, F j', $bhp_pickup_ts ) : $bhp_pickup_date;

	/*
	 * ⭐ 1.19.232 (2026-08-17, `CYCLE162-LD-PICKUP-FIELDS`) — THE CHILD'S NAME.
	 *
	 * ⛔ TWO WHOLE SENTENCES, NOT ONE SENTENCE WITH A NAME GLUED ON. The
	 *    no-name variant is byte-identical to the 1.19.231 string, so an order
	 *    with no child name reads exactly what it read before rather than
	 *    "…signed for ." — the same rule the school/date block below follows,
	 *    and the same rule the HTML twin follows. The two templates are kept in
	 *    lockstep deliberately: this file's own header records that they drifted
	 *    apart once (C10) and left two recipients of one order reading two
	 *    different promises.
	 * ⛔ IT ADDS NO NEW PROMISE. "signed" was already there; the only new
	 *    information is who it is signed to, which is what the parent typed.
	 */
	$bhp_pickup_child = function_exists( 'bhp_school_visit_child_name' ) ? bhp_school_visit_child_name( $order ) : '';

	echo esc_html__( 'HOW YOU’LL GET YOUR BOOKS', 'brave-hearts' ) . "\n\n";
	if ( '' !== $bhp_pickup_child ) {
		echo esc_html(
			sprintf(
				/* translators: %s: the child's first name */
				__( 'Nothing is being posted, and you have not been charged for shipping. Andrew is bringing your books to the school visit by hand, signed for %s.', 'brave-hearts' ),
				$bhp_pickup_child
			)
		) . "\n\n";
	} else {
		echo esc_html__( 'Nothing is being posted, and you have not been charged for shipping. Andrew is bringing your books to the school visit by hand, signed.', 'brave-hearts' ) . "\n\n";
	}

	if ( '' !== $bhp_pickup_school && '' !== $bhp_pickup_pretty ) {
		echo esc_html( $bhp_pickup_school ) . ' - ' . esc_html( $bhp_pickup_pretty ) . "\n\n";
	}

	echo esc_html__( 'You don’t need to collect anything from us, arrange anything, or do anything before the visit.', 'brave-hearts' ) . "\n\n";

	echo "----------------------------------------\n\n";

	echo esc_html__( 'WHILE YOU WAIT', 'brave-hearts' ) . "\n\n";
	echo esc_html__( 'If this is a first chapter book in your house, one small thing helps more than anything else: read the first chapter together. Not the whole book - just the first one. Starting together and handing over later is how these were built to be read.', 'brave-hearts' ) . "\n\n";

	echo "----------------------------------------\n\n";

	echo esc_html__( 'SOMETHING CHANGED? NEED US?', 'brave-hearts' ) . "\n\n";
	echo esc_html__( 'Reply to this email - it comes to a real person. If your plans have changed and you won’t be at the visit, tell us as soon as you can and we’ll sort it out.', 'brave-hearts' ) . "\n\n";
} else {

echo esc_html__( 'HOW YOUR BOOKS ARE MADE', 'brave-hearts' ) . "\n\n";
echo esc_html__( 'Your books aren’t sitting in a warehouse waiting. They’re printed for you after you order, by our print partner Bookvault, and then packed and sent to you.', 'brave-hearts' ) . "\n\n";

/*
 * C10 (2026-08-03) — THE ECHO LINE THE PLAIN TWIN WAS MISSING.
 *
 * WAVE F added this sentence to the HTML sibling on Andrew's "add it" and did
 * not add it here, so a plain-text recipient read one sentence fewer than an
 * HTML recipient of the same order. That is precisely the two-different-
 * promises-from-one-order failure this file's own header says it exists to
 * prevent, and it was a real gap, not a stylistic one.
 *
 * ⭐ POSITION MATCHES THE HTML SIBLING EXACTLY: immediately after the "printed
 *    for you after you order" paragraph and immediately before "That means two
 *    things worth knowing:". The HTML template states the reason and it holds
 *    identically here — this line is the emotional consequence of the fact
 *    just stated, and the numbered list below is the practical consequence.
 *    Placing it after the list would separate it from what it comments on.
 *
 * ⭐ THE STRING IS BYTE-FOR-BYTE THE HTML SIBLING'S, INCLUDING THE FULL STOP
 *    INSTEAD OF AN EM DASH. The approved wording arrived as "Every copy is
 *    printed just for you — nothing mass-produced, nothing wasted." and was
 *    amended by Andrew to two sentences under the standing email em-dash rule.
 *    Recorded here too so a future reader does not "restore" the dash in one
 *    template and not the other, which is how these two drifted apart in the
 *    first place.
 *
 * ⛔ IT ADDS NO NEW CLAIM. No number, no timing, no environmental metric, and
 *    no elapsed-time claim — the empty timing slot below is still empty.
 */
echo esc_html__( 'Every copy is printed just for you. Nothing mass-produced, nothing wasted.', 'brave-hearts' ) . "\n\n";

echo esc_html__( 'That means two things worth knowing:', 'brave-hearts' ) . "\n\n";
echo '1. ' . esc_html__( 'There’s a short production step before anything ships. Your order goes into production first, then into the post.', 'brave-hearts' ) . "\n\n";
echo '2. ' . esc_html__( 'We’ll be in touch again in a few days. You don’t need to check anything or do anything.', 'brave-hearts' ) . "\n\n";

/*
 * ⛔⛔ TIMING SLOT - INTENTIONALLY EMPTY. See the HTML template for the full
 * reasoning. Fill it only from a MEASURED production range, never from the
 * Terms page's unverified former-vendor figure and never from an estimate.
 */

echo "----------------------------------------\n\n";

echo esc_html__( 'WHILE YOU WAIT', 'brave-hearts' ) . "\n\n";
echo esc_html__( 'If this is a first chapter book in your house, one small thing helps more than anything else: read the first chapter together. Not the whole book - just the first one. Starting together and handing over later is how these were built to be read.', 'brave-hearts' ) . "\n\n";

echo "----------------------------------------\n\n";

echo esc_html__( 'SOMETHING CHANGED? NEED US?', 'brave-hearts' ) . "\n\n";
echo esc_html__( 'Reply to this email - it comes to a real person. If you need to change your address or cancel, tell us as soon as you can, because once a book is in production we may not be able to stop it.', 'brave-hearts' ) . "\n\n";

} // End of the 1.19.231 hand-delivery branch. Everything between `else {` and here is the untouched locked copy.

echo esc_html__( 'Thanks for taking a chance on us.', 'brave-hearts' ) . "\n\n";
echo esc_html__( 'Andrew', 'brave-hearts' ) . "\n";
echo esc_html__( 'Brave Hearts Publishing', 'brave-hearts' ) . "\n";
echo esc_html__( 'Big Places. Brave Hearts.', 'brave-hearts' ) . "\n\n";

echo "----------------------------------------\n\n";

/* translators: %s: Order number */
echo sprintf( esc_html__( 'This is an order confirmation for order #%s. You’re receiving it because you placed an order with us.', 'brave-hearts' ), esc_html( $order->get_order_number() ) ) . "\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/*
 * FOOTER - 2026-08-03, the email build.
 *
 * Was: echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text',
 *      get_option( 'woocommerce_email_footer_text' ) ) );
 *
 * Now routed through the shared helper so this plain twin carries the SAME
 * two footer lines the HTML sibling carries (the FD-76 D6 fulfilment
 * sentence and the reply route), followed by the identical global footer
 * text. The plain templates call that filter with one argument and so
 * cannot be scoped by email id, which is why the lines are added here by
 * hand. Deck §2.6 layer 2, `CYCLE142-CX-037`.
 *
 * ⛔ NOT ONE WORD OF E1's BODY COPY ABOVE THIS BLOCK WAS CHANGED.
 */
bhp_email_plain_footer( $email );
