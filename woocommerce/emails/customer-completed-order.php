<?php
/**
 * Customer completed order email - Brave Hearts override (E2).
 *
 * Overrides woocommerce/templates/emails/customer-completed-order.php.
 * Source template version at the time of the override: 10.4.0
 * (WooCommerce 10.9.1 ships that @version). If WooCommerce bumps the
 * source template, re-diff this file against it before assuming parity.
 *
 * ---------------------------------------------------------------------
 * WHY THIS OVERRIDE EXISTS: THE STOCK VERSION WAS MAKING A CLAIM THE
 * SYSTEM COULD NOT KEEP
 * ---------------------------------------------------------------------
 * The stock subject asserted "Your order from {site_title} is on its way!"
 * Nothing in this installation knows whether it is on its way. The
 * predecessor audit established by direct source inspection of the
 * installed Bookvault plugin 6.0.0 that it writes ZERO tracking numbers,
 * ZERO carrier names, ZERO dispatch events and ZERO status changes, and
 * that no auto-complete transition exists in the theme or the bundle
 * plugin. An order becomes `completed` only when a human clicks it in
 * wp-admin.
 *
 * ⭐ SO "Your books have shipped" IS TRUE HERE FOR EXACTLY ONE REASON:
 *    Andrew committed to the operating rule that an order is marked
 *    Completed only AFTER Bookvault has been seen to show it dispatched.
 *    Deck §3.2 VARIANT A, `CYCLE142-CX-038`.
 *
 * ⛔ IF THAT OPERATING RULE STOPS BEING FOLLOWED, THIS EMAIL BECOMES
 *    UNTRUE. The deck's VARIANT A2 ("Your order is complete", claiming
 *    nothing about movement) is the replacement, and it is a copy swap,
 *    not a bug fix. Do not quietly soften the sentence instead.
 *
 * ⛔ NO TRACKING NUMBER, NO CARRIER, NO TRACKING LINK. There is no
 *    dispatch signal reaching WordPress. The email says so out loud
 *    rather than sending a link that goes nowhere.
 * ⛔ NO DELIVERY DATE, NO TRANSIT TIME, NO DURATION CLAIM OF ANY KIND.
 * ⛔ NO COUPON, NO UPSELL, NO REVIEW ASK, NO LEAD MAGNET.
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
do_action( 'woocommerce_email_header', $email_heading, $email );

/*
 * ⭐⭐ THE SCHOOL-VISIT FORK (theme 1.19.315, `CYCLE168-LD-VISIT-COMPLETED-EMAIL`).
 *
 * `$bhp_visit_body` is a NON-EMPTY array only when this order carries
 * `_bhp_school_visit_slug`. For every ordinary order it is `array()` and every
 * branch below takes the 1.19.314 path unchanged.
 *
 * ⛔ WHY THE WHOLE BODY FORKS AND NOT ONLY THE OPENING TWO PARAGRAPHS. Three
 *    separate sentences in the standard body describe a POSTED order, and each
 *    is false for a signed book handed to a child in a school library:
 *      1. "your books are printed and they have left our print partner"
 *      2. "we do not receive a tracking number from our printer"
 *      3. "If anything arrives damaged or wrong" - nothing is going to arrive.
 *    Removing one and keeping the others would leave the email half-true,
 *    which is the failure the founder ruling exists to fix.
 *
 * ⛔ NO SECOND SIGN-OFF. The approved copy's last paragraph IS the sign-off
 *    ("Feel free to email me any time at Andrew@braveheartspublishing.com,
 *    once again thank you!"), so the standard body's "Thanks for taking a
 *    chance on us." is dropped rather than stacked underneath it. The name
 *    block below is kept: it is the brand signature, and it makes no claim.
 *
 * ⚠ THE ORDER SUMMARY TABLE IS UNCHANGED AND STILL RENDERS, in the same
 *   position, from the same three core actions. Carrier item 377: "Order
 *   summary table stays, after the body."
 */
$bhp_visit_body = function_exists( 'bhp_visit_email_body' ) ? bhp_visit_email_body( $email ) : array();
?>

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
<?php if ( ! empty( $bhp_visit_body ) ) : ?>
	<?php foreach ( $bhp_visit_body as $bhp_visit_paragraph ) : ?>
		<p><?php echo esc_html( $bhp_visit_paragraph ); ?></p>
	<?php endforeach; ?>
<?php else : ?>
<p><?php esc_html_e( 'Good news. Your books are printed and they have left our print partner. They are on their way to you.', 'brave-hearts' ); ?></p>
<p><?php esc_html_e( 'One honest thing: we do not receive a tracking number from our printer, so we cannot give you one. We would rather tell you that than send you a link that goes nowhere.', 'brave-hearts' ); ?></p>
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

<?php
/*
 * ⚠ THE PHP TAGS IN THIS BLOCK ARE TIGHT ON PURPOSE, AND THE WHITESPACE IS
 *   LOAD-BEARING. PHP eats exactly ONE newline after each `?>`, so a blank
 *   line placed around `if` / `else` / `endif` here does NOT vanish - it lands
 *   in the assembled HTML of the STANDARD email and breaks the byte-for-byte
 *   equality with 1.19.314 that this build promises. Observed, not reasoned
 *   about: the first staging build of 1.19.315 added exactly three blank lines
 *   to a standard customer's email this way, caught by diffing the rendered
 *   output against a baseline captured before the deploy.
 */
?>
<?php if ( empty( $bhp_visit_body ) ) : ?>
<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<h2 style="margin:0 0 10px;font-size:17px;"><?php esc_html_e( 'When they land', 'brave-hearts' ); ?></h2>

<p><?php esc_html_e( 'Read the first chapter out loud together. Not the whole book. Just the first one. Starting together and handing over later is how these were built to be read.', 'brave-hearts' ); ?></p>

<p><?php esc_html_e( 'If anything arrives damaged or wrong, reply to this email. It comes to a real person and we will sort it out.', 'brave-hearts' ); ?></p>

<p style="margin-top:22px;"><?php esc_html_e( 'Thanks for taking a chance on us.', 'brave-hearts' ); ?></p>

<?php else : ?>
<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">
<?php endif; ?>
<p style="margin:0;">
	<?php esc_html_e( 'Andrew', 'brave-hearts' ); ?><br>
	<?php esc_html_e( 'Brave Hearts Publishing', 'brave-hearts' ); ?><br>
	<em><?php esc_html_e( 'Big Places. Brave Hearts.', 'brave-hearts' ); ?></em>
</p>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 *
 * Suppressed to '' by `bhp_email_additional_content` in
 * inc/transactional-emails.php unless somebody has deliberately typed one
 * into wp-admin. The block is kept so that a deliberate value still renders.
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
