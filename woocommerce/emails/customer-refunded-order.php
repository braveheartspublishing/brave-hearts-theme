<?php
/**
 * Customer refunded order email - Brave Hearts override (E3).
 *
 * Overrides woocommerce/templates/emails/customer-refunded-order.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * THE HIGHEST-TRUST MOMENT IN THE WHOLE RELATIONSHIP
 * ---------------------------------------------------------------------
 * A parent being refunded was receiving a bare transaction notice with no
 * explanation and no human in it. This is the email where a refund either
 * costs the relationship or saves it, so it is short, it explains what it
 * can, and it invites a reply.
 *
 * ⚠ `$partial_refund` IS PASSED IN BY THE EMAIL CLASS and both branches
 *   have real copy. WooCommerce sends two forms of this email off one
 *   template, and a template that only handled the full-refund case would
 *   tell a partially-refunded customer their whole order came back.
 *
 * ⛔ DELIBERATELY ABSENT: THE REASON FOR THE REFUND. WooCommerce holds no
 *    customer-safe reason field, and inventing one is a fabrication. If a
 *    reason should be stated, it goes in a CUSTOMER NOTE (E6), which is the
 *    surface built for exactly that.
 *
 * ⛔ NO DATE, NO "WITHIN X DAYS", NO BANK-TIMING PROMISE. How quickly a
 *    refund lands is the customer's bank's decision. The copy disclaims it
 *    rather than guessing at it.
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

<?php if ( $partial_refund ) : ?>
	<p><?php
	printf(
		/* translators: %s: Order number */
		esc_html__( 'We have refunded part of order #%s. The rest of the order stands.', 'brave-hearts' ),
		esc_html( $order->get_order_number() )
	); ?></p>
	<p><?php esc_html_e( 'How quickly the refund shows up is your bank’s decision, not ours, so we will not guess at a date. It will appear on the card you paid with.', 'brave-hearts' ); ?></p>
<?php else : ?>
	<p><?php
	printf(
		/* translators: %s: Order number */
		esc_html__( 'We have refunded order #%s in full.', 'brave-hearts' ),
		esc_html( $order->get_order_number() )
	); ?></p>
	<p><?php esc_html_e( 'How quickly it shows up is your bank’s decision, not ours, so we will not guess at a date. It will appear on the card you paid with.', 'brave-hearts' ); ?></p>
<?php endif; ?>
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

<?php if ( $partial_refund ) : ?>
	<p><?php esc_html_e( 'If this is not what you expected, reply to this email. It comes to a real person.', 'brave-hearts' ); ?></p>
<?php else : ?>
	<p><?php esc_html_e( 'If this is not what you expected, or if something went wrong that we should know about, reply to this email. It comes to a real person, and we would genuinely rather hear it than not.', 'brave-hearts' ); ?></p>
	<p style="margin-top:22px;"><?php esc_html_e( 'Thanks for giving us a try.', 'brave-hearts' ); ?></p>
<?php endif; ?>

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
