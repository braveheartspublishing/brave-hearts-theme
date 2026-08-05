<?php
/**
 * Customer processing order email — Brave Hearts override (E1).
 *
 * Overrides woocommerce/templates/emails/customer-processing-order.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * WHAT THIS IS, AND WHAT IT DELIBERATELY DOES NOT SAY
 * ---------------------------------------------------------------------
 * This is the E1 "order confirmation + expectations" email specified by
 * `marketing-growth` in
 *   Business OS\WORKING-DRAFTS\marketing-growth\
 *   OVERNIGHT-2026-08-03-POST-PURCHASE-SEQUENCE.md  §2
 * Its job is to make the next few days feel like a plan rather than a
 * silence, by explaining honestly that these books are printed after
 * they are ordered.
 *
 * ⛔ IT CONTAINS NO ELAPSED-TIME CLAIM OF ANY KIND, AND THAT IS
 *    DELIBERATE, NOT AN OVERSIGHT. The spec's §2.3 timing slot is left
 *    EMPTY on the spec author's own ruling. There is no measured
 *    production window for the current print partner. The site's
 *    Terms page carries a "24 hours" figure that was written for the
 *    FORMER print vendor and has been flagged unverified since
 *    2026-07-09 (`CYCLE141-MKT-3`). Repeating it into a customer's
 *    inbox would propagate an unverified claim, not inherit one.
 *    ⛔ DO NOT ADD A NUMBER HERE until a real measured range exists.
 *       When it does, the slot is marked below.
 *
 * ⛔ IT DOES NOT SELL, UPSELL, CARRY A COUPON, OR PROMISE A DATE.
 * ⛔ IT PROMISES NO TRACKING NUMBER AND NO DISPATCH NOTIFICATION.
 *    There is no dispatch event and no tracking field on the WordPress
 *    side — confirmed absent (`CYCLE141-MKT-2`). The line therefore
 *    reads "We'll be in touch again in a few days", which the follow-up
 *    email can actually keep, and NOT "we'll email you when it moves",
 *    which nothing in the system can trigger.
 *
 * Every factual claim in the copy below is sourced in the spec's §2.5
 * claim audit. The refund/cancellation sentence is hedged exactly as
 * the live Terms page hedges it.
 *
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = class_exists( FeaturesUtil::class ) && FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'brave-hearts' ), esc_html( $order->get_billing_first_name() ) );
} else {
	esc_html_e( 'Hi,', 'brave-hearts' );
}
?>
</p>
<p><?php esc_html_e( 'Thank you - your order is confirmed. Here is exactly what happens now, and when you’ll hear from us again.', 'brave-hearts' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<h2 style="margin:24px 0 8px;font-size:17px;"><?php esc_html_e( 'What you ordered', 'brave-hearts' ); ?></h2>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 *
 * NOTE: the spec's copy deck marks a "Shipping to" heading here. WooCommerce
 * renders its own "Billing address" / "Shipping address" headings inside this
 * hook, so adding a third heading above them would duplicate rather than label.
 * Deliberate deviation, recorded rather than silently applied.
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<h2 style="margin:0 0 10px;font-size:17px;"><?php esc_html_e( 'How your books are made', 'brave-hearts' ); ?></h2>

<p><?php
printf(
	/* translators: 1: opening strong tag, 2: closing strong tag */
	esc_html__( 'Your books aren’t sitting in a warehouse waiting. They’re %1$sprinted for you after you order%2$s, by our print partner Bookvault, and then packed and sent to you.', 'brave-hearts' ),
	'<strong>',
	'</strong>'
); ?></p>

<?php
/*
 * WAVE F (2026-08-03) -- Andrew Signore, "add it", relayed via
 * chief-of-staff. The print-on-demand waste sentence.
 *
 * ⚠ RENDERED AS TWO SENTENCES, NOT ONE WITH AN EM DASH, ON ANDREW'S
 *   OWN INSTRUCTION. The approved wording reached this brief as
 *   "Every copy is printed just for you — nothing mass-produced,
 *   nothing wasted." and was explicitly amended to drop the em dash
 *   per the standing email rule. It is recorded here so a future
 *   reader does not "restore" the dash thinking a source was lost.
 *
 * It is placed immediately after the "printed for you after you order"
 * paragraph and before "That means two things worth knowing:", because
 * it is the emotional consequence of the fact just stated, and the
 * numbered list that follows is the practical consequence. Putting it
 * after the list would separate it from what it comments on.
 *
 * ⛔ IT ADDS NO NEW CLAIM. "Printed just for you" restates the
 *    preceding sentence, which is sourced; "nothing mass-produced,
 *    nothing wasted" is a direct property of print-on-demand and is
 *    the same claim the sitewide "Printed Just for You" component
 *    already makes. No number, no timing, no environmental metric.
 */
?>
<p><?php esc_html_e( 'Every copy is printed just for you. Nothing mass-produced, nothing wasted.', 'brave-hearts' ); ?></p>

<p><?php esc_html_e( 'That means two things worth knowing:', 'brave-hearts' ); ?></p>

<ol style="margin:0 0 16px;padding-left:20px;">
	<li style="margin-bottom:8px;"><?php
	printf(
		/* translators: 1: opening strong tag, 2: closing strong tag */
		esc_html__( '%1$sThere’s a short production step before anything ships.%2$s Your order goes into production first, then into the post.', 'brave-hearts' ),
		'<strong>',
		'</strong>'
	); ?></li>
	<li><?php
	printf(
		/* translators: 1: opening strong tag, 2: closing strong tag */
		esc_html__( '%1$sWe’ll be in touch again in a few days.%2$s You don’t need to check anything or do anything.', 'brave-hearts' ),
		'<strong>',
		'</strong>'
	); ?></li>
</ol>

<?php
/*
 * ⛔⛔ TIMING SLOT — INTENTIONALLY EMPTY. DO NOT FILL THIS IN FROM MEMORY,
 * FROM THE TERMS PAGE, OR FROM A PLAUSIBLE ESTIMATE.
 *
 * It is filled only by a MEASURED range: record the payment timestamp and
 * the print partner's "Packed"/dispatch timestamp for ~10 real orders, then
 * state the observed range. That is a portal login, i.e. Andrew or an
 * authorised human — no agent can obtain it.
 *
 * When the measurement exists, the line goes here and reads, e.g.:
 *   "Most orders finish production and ship within X–Y business days."
 * Nothing else belongs in this slot.
 */
?>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<h2 style="margin:0 0 10px;font-size:17px;"><?php esc_html_e( 'While you wait', 'brave-hearts' ); ?></h2>

<p><?php
printf(
	/* translators: 1: opening strong tag, 2: closing strong tag */
	esc_html__( 'If this is a first chapter book in your house, one small thing helps more than anything else: %1$sread the first chapter together.%2$s Not the whole book - just the first one. Starting together and handing over later is how these were built to be read.', 'brave-hearts' ),
	'<strong>',
	'</strong>'
); ?></p>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<h2 style="margin:0 0 10px;font-size:17px;"><?php esc_html_e( 'Something changed? Need us?', 'brave-hearts' ); ?></h2>

<p><?php esc_html_e( 'Reply to this email - it comes to a real person. If you need to change your address or cancel, tell us as soon as you can, because once a book is in production we may not be able to stop it.', 'brave-hearts' ); ?></p>

<p style="margin-top:22px;"><?php esc_html_e( 'Thanks for taking a chance on us.', 'brave-hearts' ); ?></p>

<p style="margin:0;">
	<?php esc_html_e( 'Andrew', 'brave-hearts' ); ?><br>
	<?php esc_html_e( 'Brave Hearts Publishing', 'brave-hearts' ); ?><br>
	<em><?php esc_html_e( 'Big Places. Brave Hearts.', 'brave-hearts' ); ?></em>
</p>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0 14px;">

<p style="font-size:12px;color:#6b6b60;margin:0;"><small><?php
printf(
	/* translators: %s: Order number */
	esc_html__( 'This is an order confirmation for order #%s. You’re receiving it because you placed an order with us.', 'brave-hearts' ),
	esc_html( $order->get_order_number() )
); ?></small></p>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
