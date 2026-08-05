<?php
/**
 * Customer cancelled order email - Brave Hearts override (E7).
 *
 * Overrides woocommerce/templates/emails/customer-cancelled-order.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * THIS EMAIL DID NOT EXIST FOR THE CUSTOMER UNTIL NOW
 * ---------------------------------------------------------------------
 * `customer_cancelled_order` was disabled on this store, while E1 explicitly
 * invites the customer to ask for a cancellation ("If you need to change
 * your address or cancel, tell us as soon as you can"). We invited the
 * request and then went silent on the outcome. `CYCLE142-CX-022`.
 *
 * ⭐ THE REFUND SENTENCE IS TRUE FOR EXACTLY ONE REASON: Andrew's operating
 *    rule that a paid order cancelled while the books are unprinted is
 *    refunded at the same time. Deck §3.7 VARIANT A, `CYCLE142-CX-041`.
 *
 * ⛔ CANCELLING AN ORDER IN WooCommerce DOES NOT REFUND IT. There is no code
 *    path here that issues a refund. If that operating rule stops being
 *    followed, this sentence becomes a promise the system does not keep and
 *    must be swapped for the deck's A2 wording ("If a payment was taken,
 *    reply to this email and we will get it back to you"). That is a copy
 *    swap, not a bug fix.
 *
 * ⛔ NO REFUND TIMING CLAIM. E3 arrives separately and confirms the refund.
 *
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = class_exists( FeaturesUtil::class ) && FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook: woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 2.5.0
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
<p><?php
printf(
	/* translators: %s: Order number */
	esc_html__( 'Order #%s has been cancelled. Nothing will be printed and nothing will be sent.', 'brave-hearts' ),
	esc_html( $order->get_order_number() )
); ?></p>
<p><?php esc_html_e( 'If a payment was taken, we are refunding it, and you will get a separate email from us confirming that. How quickly it lands is your bank’s decision.', 'brave-hearts' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Hook: woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook: woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook: woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<p><?php esc_html_e( 'If you did not ask for this, reply to this email straight away. It comes to a real person.', 'brave-hearts' ); ?></p>

<p style="margin:0;">
	<?php esc_html_e( 'Andrew', 'brave-hearts' ); ?><br>
	<?php esc_html_e( 'Brave Hearts Publishing', 'brave-hearts' ); ?><br>
	<em><?php esc_html_e( 'Big Places. Brave Hearts.', 'brave-hearts' ); ?></em>
</p>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Hook: woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 2.5.0
 */
do_action( 'woocommerce_email_footer', $email );
