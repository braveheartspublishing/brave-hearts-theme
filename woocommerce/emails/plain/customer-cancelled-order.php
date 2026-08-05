<?php
/**
 * Customer cancelled order email (plain text) - Brave Hearts override (E7).
 *
 * Overrides woocommerce/templates/emails/plain/customer-cancelled-order.php.
 * Source template version at the time of the override: 10.0.0
 * (WooCommerce 10.9.1 ships that @version).
 *
 * Same promises as the HTML sibling, including the refund sentence, whose
 * truth depends on the cancel-and-refund-together operating rule documented
 * in the HTML template.
 *
 * ⚠ ONE DELIBERATE DEVIATION FROM CORE'S PLAIN TEMPLATE: core's version
 *   ends by firing the `woocommerce_email_footer` ACTION, which renders the
 *   HTML footer template into a plain-text message. This one echoes a plain
 *   footer instead, matching every other plain twin in this theme.
 *
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
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
echo sprintf( esc_html__( 'Order #%s has been cancelled. Nothing will be printed and nothing will be sent.', 'brave-hearts' ), esc_html( $order->get_order_number() ) ) . "\n\n";
echo esc_html__( 'If a payment was taken, we are refunding it, and you will get a separate email from us confirming that. How quickly it lands is your bank’s decision.', 'brave-hearts' ) . "\n\n";

/**
 * Hook: woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

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

echo "\n----------------------------------------\n\n";

echo esc_html__( 'If you did not ask for this, reply to this email straight away. It comes to a real person.', 'brave-hearts' ) . "\n\n";

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
