<?php
/**
 * Customer refunded order email (plain text) - Brave Hearts override (E3).
 *
 * Overrides woocommerce/templates/emails/plain/customer-refunded-order.php.
 * Source template version at the time of the override: 9.8.0
 * (WooCommerce 10.9.1 ships that @version).
 *
 * Carries the same promises, sentence for sentence, as the HTML sibling,
 * including BOTH the full-refund and partial-refund branches. The reasoning
 * and the deliberate omissions are documented in the HTML template.
 *
 * ⛔ NO REFUND REASON (no customer-safe field exists to hold one).
 * ⛔ NO BANK-TIMING PROMISE, NO DATE, NO DURATION CLAIM.
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
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

if ( $partial_refund ) {
	/* translators: %s: Order number */
	echo sprintf( esc_html__( 'We have refunded part of order #%s. The rest of the order stands.', 'brave-hearts' ), esc_html( $order->get_order_number() ) ) . "\n\n";
	echo esc_html__( 'How quickly the refund shows up is your bank’s decision, not ours, so we will not guess at a date. It will appear on the card you paid with.', 'brave-hearts' ) . "\n\n";
} else {
	/* translators: %s: Order number */
	echo sprintf( esc_html__( 'We have refunded order #%s in full.', 'brave-hearts' ), esc_html( $order->get_order_number() ) ) . "\n\n";
	echo esc_html__( 'How quickly it shows up is your bank’s decision, not ours, so we will not guess at a date. It will appear on the card you paid with.', 'brave-hearts' ) . "\n\n";
}

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

if ( $partial_refund ) {
	echo esc_html__( 'If this is not what you expected, reply to this email. It comes to a real person.', 'brave-hearts' ) . "\n\n";
} else {
	echo esc_html__( 'If this is not what you expected, or if something went wrong that we should know about, reply to this email. It comes to a real person, and we would genuinely rather hear it than not.', 'brave-hearts' ) . "\n\n";
	echo esc_html__( 'Thanks for giving us a try.', 'brave-hearts' ) . "\n\n";
}

echo esc_html__( 'Andrew', 'brave-hearts' ) . "\n";
echo esc_html__( 'Brave Hearts Publishing', 'brave-hearts' ) . "\n";
echo esc_html__( 'Big Places. Brave Hearts.', 'brave-hearts' ) . "\n\n";

echo "----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

bhp_email_plain_footer( $email );
