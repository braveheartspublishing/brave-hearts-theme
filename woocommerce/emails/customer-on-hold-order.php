<?php
/**
 * Customer on-hold order email - Brave Hearts override (E4).
 *
 * Overrides woocommerce/templates/emails/customer-on-hold-order.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * WHY THE NEUTRAL WORDING, AND WHY IT IS NOT A HEDGE
 * ---------------------------------------------------------------------
 * The stock subject was "Your {site_title} order has been received!", which
 * competes directly with E1's subject and gives the buyer a second,
 * contradictory "order received" email with none of E1's expectation
 * setting.
 *
 * ⛔ WHICH PAYMENT PATHS ON THIS STORE CAN PRODUCE AN `on-hold` ORDER HAS
 *    NEVER BEEN ENUMERATED. `CYCLE142-CX-039`. WooCommerce's stock copy
 *    assumes an awaiting-payment hold. We do not know that is the only path
 *    here, so the shipped version says only what is true under EVERY cause:
 *    we have it, nothing is printing, we will write again.
 *
 * ⛔ DO NOT ADD "while we wait for the payment to clear" UNTIL somebody has
 *    enumerated this store's on-hold causes live and found payment to be
 *    the only one. The deck's conditional wording is ready and blocked on
 *    exactly that check.
 *
 * ⛔ NO DURATION CLAIM. "As soon as it is moving" is a sequence, not a time.
 *
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

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
<p><?php esc_html_e( 'We have your order, and it is on hold for the moment. Nothing has gone into production yet.', 'brave-hearts' ); ?></p>
<p><?php esc_html_e( 'As soon as it is moving, we will email you again. There is nothing you need to do.', 'brave-hearts' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

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
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<p><?php esc_html_e( 'If you think this is a mistake, reply to this email. It comes to a real person.', 'brave-hearts' ); ?></p>

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

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
