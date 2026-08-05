<?php
/**
 * Customer note email (plain text) - Brave Hearts override (E6).
 *
 * Overrides woocommerce/templates/emails/plain/customer-note.php.
 * Source template version at the time of the override: 9.9.0
 * (WooCommerce 10.9.1 ships that @version).
 *
 * Same promises as the HTML sibling. The note itself is the content;
 * everything wrapped around it is noise.
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

/* translators: %s: Order number */
echo sprintf( esc_html__( 'We have added a note to order #%s:', 'brave-hearts' ), esc_html( $order->get_order_number() ) ) . "\n\n";

echo esc_html( wp_strip_all_tags( wc_wptexturize_order_note( $customer_note ) ) ) . "\n\n";

echo "----------------------------------------\n\n";

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

echo esc_html__( 'Reply to this email if you want to talk about it. It comes to a real person.', 'brave-hearts' ) . "\n\n";

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
