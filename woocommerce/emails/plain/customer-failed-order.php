<?php
/**
 * Customer failed order email (plain text) - Brave Hearts override (E5).
 *
 * Overrides woocommerce/templates/emails/plain/customer-failed-order.php.
 * Source template version at the time of the override: 9.8.0
 * (WooCommerce 10.9.1 ships that @version).
 *
 * Same promises as the HTML sibling, including the retry link. A plain-text
 * reader gets the URL as text because there is no button to press. The
 * reasoning and the deliberate omission of any pending-authorisation
 * explanation are documented in the HTML template.
 *
 * ⛔ NO CLAIM ABOUT WHAT THE CUSTOMER'S BANK WILL SHOW OR DO.
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

$bhp_pay_url = $order->get_checkout_payment_url();

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'brave-hearts' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi,', 'brave-hearts' ) . "\n\n";
}

echo esc_html__( 'Your order did not complete, because the payment was not approved. We have not taken a payment, and nothing has been printed.', 'brave-hearts' ) . "\n\n";

if ( $bhp_pay_url ) {
	echo esc_html__( 'If you still want the books, you can pick up where you left off:', 'brave-hearts' ) . "\n";
	echo esc_url_raw( $bhp_pay_url ) . "\n\n";
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

echo esc_html__( 'You can use the same card or a different one. If it keeps failing, reply to this email and we will work it out with you. It comes to a real person.', 'brave-hearts' ) . "\n\n";

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
