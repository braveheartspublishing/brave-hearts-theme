<?php
/**
 * Customer failed order email - Brave Hearts override (E5).
 *
 * Overrides woocommerce/templates/emails/customer-failed-order.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * THE ONLY EMAIL IN THIS SUITE WITH A BUTTON, AND IT HAS EXACTLY ONE
 * ---------------------------------------------------------------------
 * The stock subject was "Your order at {site_title} was unsuccessful".
 * "Unsuccessful" is a word a bank uses. This one says what happened, says
 * nothing was charged, and gives the customer the single link that lets
 * them finish.
 *
 * BUTTON MARKUP: a table-based bulletproof button with inline styles, not a
 * bare <a>. Forest fill #173f2f, ivory text #fffaf0 (11.29:1), a 1.5px gold
 * #D9A45F BORDER, 8px radius. The vertical padding lives on the <td>, not
 * the <a>.
 *
 * ⛔ `display` IS DELIBERATELY NEVER SET ON THIS BUTTON. The last time
 *    `display:inline-flex` was added to the shared site button rule it
 *    produced 32px of horizontal scroll on every page. The email context is
 *    different, but there is no reason to set it, and padding on the cell
 *    reaches the 48px minimum tap target without it (14 + 20 + 14 + 3px of
 *    border = 51px).
 *
 * ⛔ OUTLOOK DESKTOP WILL RENDER THIS BUTTON SQUARE because it ignores
 *    `border-radius`. THAT IS ACCEPTED, NOT FIXED WITH VML. A square forest
 *    button with a gold border is still on brand.
 *
 * ⛔ DELIBERATELY OMITTED, AND THIS IS A CLAIM-AUDIT DECISION NOT A STYLE
 *    ONE: any line explaining that a pending amount on the customer's
 *    statement is an authorisation hold rather than a charge. It is very
 *    probably true and it would genuinely reduce support contacts, BUT it
 *    is a statement about what a third party's bank will do and no source
 *    in this corpus supports it. `CYCLE142-CX-040`. It ships only with a
 *    citation from Stripe's own published documentation.
 *
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = class_exists( FeaturesUtil::class ) && FeaturesUtil::feature_is_enabled( 'email_improvements' );
$bhp_pay_url                = $order->get_checkout_payment_url();

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
<p><?php esc_html_e( 'Your order did not complete, because the payment was not approved. We have not taken a payment, and nothing has been printed.', 'brave-hearts' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php if ( $bhp_pay_url ) : ?>
<p><?php esc_html_e( 'If you still want the books, you can pick up where you left off:', 'brave-hearts' ); ?></p>

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin:10px 0 24px;">
	<tr>
		<td align="center" bgcolor="#173f2f" style="background-color:#173f2f;border:1.5px solid #D9A45F;border-radius:8px;padding:14px 26px;mso-padding-alt:14px 26px;">
			<a href="<?php echo esc_url( $bhp_pay_url ); ?>" target="_blank" rel="noopener" style="color:#fffaf0;font-family:Archivo,Arial,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.06em;line-height:20px;text-decoration:none;"><?php esc_html_e( 'FINISH YOUR ORDER', 'brave-hearts' ); ?></a>
		</td>
	</tr>
</table>
<?php endif; ?>

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

<p><?php esc_html_e( 'You can use the same card or a different one. If it keeps failing, reply to this email and we will work it out with you. It comes to a real person.', 'brave-hearts' ); ?></p>

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
