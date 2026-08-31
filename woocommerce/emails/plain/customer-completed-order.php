<?php
/**
 * Customer completed order email (plain text) - Brave Hearts override (E2).
 *
 * Overrides woocommerce/templates/emails/plain/customer-completed-order.php.
 * Source template version at the time of the override: 9.9.0
 * (WooCommerce 10.9.1 ships that @version).
 *
 * This exists so a plain-text recipient reads the SAME honest copy, and the
 * SAME promises, as the HTML recipient. A promise that exists in one version
 * and not the other is a defect. The full reasoning, and the operating rule
 * that makes "your books have shipped" true, are documented in the HTML
 * template alongside this file and apply here identically.
 *
 * ⛔ NO TRACKING PROMISE. ⛔ NO DELIVERY DATE OR DURATION CLAIM.
 * ⛔ NO COUPON, NO UPSELL, NO REVIEW ASK.
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

/*
 * ⭐⭐ THE SCHOOL-VISIT FORK, IDENTICAL IN SHAPE TO THE HTML TWIN.
 *
 * ⛔ BOTH VERSIONS WALK THE SAME `body` ARRAY out of
 *    `bhp_visit_email_copy_sets()`. That is deliberate and it is the whole
 *    reason the copy lives in an array rather than in two templates: a promise
 *    that exists in the HTML version and not in the plain one is a defect, and
 *    this shape makes the two physically unable to drift.
 */
$bhp_visit_body = function_exists( 'bhp_visit_email_body' ) ? bhp_visit_email_body( $email ) : array();

if ( ! empty( $bhp_visit_body ) ) {
	foreach ( $bhp_visit_body as $bhp_visit_paragraph ) {
		echo esc_html( $bhp_visit_paragraph ) . "\n\n";
	}
} else {
	echo esc_html__( 'Good news. Your books are printed and they have left our print partner. They are on their way to you.', 'brave-hearts' ) . "\n\n";
	echo esc_html__( 'One honest thing: we do not receive a tracking number from our printer, so we cannot give you one. We would rather tell you that than send you a link that goes nowhere.', 'brave-hearts' ) . "\n\n";
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

// Same fork as the HTML twin: the "when they land" block describes an arrival
// that does not happen for a book already in a child's backpack, and the
// approved visit copy carries its own sign-off.
if ( empty( $bhp_visit_body ) ) {
	echo esc_html__( 'WHEN THEY LAND', 'brave-hearts' ) . "\n\n";
	echo esc_html__( 'Read the first chapter out loud together. Not the whole book. Just the first one. Starting together and handing over later is how these were built to be read.', 'brave-hearts' ) . "\n\n";
	echo esc_html__( 'If anything arrives damaged or wrong, reply to this email. It comes to a real person and we will sort it out.', 'brave-hearts' ) . "\n\n";

	echo esc_html__( 'Thanks for taking a chance on us.', 'brave-hearts' ) . "\n\n";
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
