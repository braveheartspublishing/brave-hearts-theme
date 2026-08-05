<?php
/**
 * Customer note email - Brave Hearts override (E6).
 *
 * Overrides woocommerce/templates/emails/customer-note.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * KEEP IT ALMOST EMPTY. THE NOTE IS THE CONTENT.
 * ---------------------------------------------------------------------
 * Everything wrapped around a customer note is noise. The stock version
 * used three framing sentences around one real one. This uses one.
 *
 * ⚠ THIS IS ALSO THE SURFACE FOR ANYTHING THE OTHER EMAILS DELIBERATELY
 *   CANNOT SAY. E3 does not state a refund reason because WooCommerce holds
 *   no customer-safe field for one; a real reason belongs here, typed by a
 *   human, where it is a fact somebody actually asserted.
 *
 * ⛔ `make_clickable()` IS RETAINED FROM CORE. A note routinely contains a
 *    URL and turning it into a link is the behaviour the sender expects.
 *    `wc_wptexturize_order_note()` is core's own escaping path for this
 *    field and is not replaced.
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
<p><?php
printf(
	/* translators: %s: Order number */
	esc_html__( 'We have added a note to order #%s:', 'brave-hearts' ),
	esc_html( $order->get_order_number() )
); ?></p>

<blockquote style="margin:0 0 16px;padding:12px 16px;border-left:3px solid #e5e0d3;">
<?php
$safe_note = wc_wptexturize_order_note( $customer_note );
echo wpautop( make_clickable( $safe_note ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</blockquote>
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

<p><?php esc_html_e( 'Reply to this email if you want to talk about it. It comes to a real person.', 'brave-hearts' ); ?></p>

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
