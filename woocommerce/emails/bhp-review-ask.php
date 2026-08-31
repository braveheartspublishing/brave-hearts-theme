<?php
/**
 * The store-sent review ask — HTML body.
 *
 * Rendered by `WC_Email_BHP_Review_Ask::get_content_html()`.
 *
 * ⛔ THIS FILE CONTAINS NO CUSTOMER-FACING WORDS. Every string comes from
 *    `$copy`, which is `bhp_review_ask_copy()`, so the founder-approved copy
 *    lands as a one-file swap and a template edit can never reword it.
 *
 * ⛔ NO PRICE, NO COUPON, NO SHIPPING FIGURE, NO PRODUCT BLOCK, NO SECOND CALL
 *    TO ACTION. One ask. See the header of `inc/review-ask-email.php` for why
 *    every number was removed and must stay removed.
 *
 * LINK STYLING: the three title links are plain inline links, not buttons.
 * Three buttons in a row reads as a merchandising strip, which is exactly the
 * thing the approved copy replaced. Forest `#173f2f` underlined is the same
 * link treatment the rest of the store's email uses.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
<?php
/*
 * ⭐ THE GREETING IS THE SPEC'S OWN CONDITIONAL, TRANSLATED OUT OF MAILCHIMP.
 *    The approved copy reads `*|IF:FNAME|*Hi *|FNAME|*,*|ELSE:|*Hi there,*|END:IF|*`
 *    because it was drafted for a journey. ⛔ Store-synced buyers frequently
 *    have no first name, and a bare merge renders "Hi ," to a real customer,
 *    which is the defect spec item G-12 exists to prevent.
 */
$bhp_first_name = ( isset( $order ) && $order instanceof WC_Order ) ? trim( (string) $order->get_billing_first_name() ) : '';

if ( '' !== $bhp_first_name ) {
	/* translators: %s: customer first name */
	printf( esc_html__( 'Hi %s,', 'brave-hearts' ), esc_html( $bhp_first_name ) );
} else {
	esc_html_e( 'Hi there,', 'brave-hearts' );
}
?>
</p>

<?php foreach ( $copy['body_before'] as $bhp_paragraph ) : ?>
<p><?php echo esc_html( $bhp_paragraph ); ?></p>
<?php endforeach; ?>

<?php
/*
 * ⭐ THE ONE ASK, SET APART. The approved copy bolds this line and nothing
 *    else in the email. It is a <p><strong>, not an <h2>: an H2 would inherit
 *    the serif display face from the theme's email CSS layer and read as a
 *    section break, when the whole point is that it is a spoken question in
 *    the middle of a paragraph run.
 */
?>
<p style="margin:22px 0;"><strong style="font-size:18px;"><?php echo esc_html( $copy['question'] ); ?></strong></p>

<?php foreach ( $copy['body_middle'] as $bhp_paragraph ) : ?>
<p><?php echo esc_html( $bhp_paragraph ); ?></p>
<?php endforeach; ?>

<p style="margin:22px 0 6px;"><strong><?php echo esc_html( $copy['links_lead'] ); ?></strong></p>

<p style="margin:0 0 22px;line-height:1.9;">
<?php
$bhp_link_parts = array();

foreach ( $copy['links'] as $bhp_link ) {
	/*
	 * ⚠ `white-space:nowrap` IS A MOBILE FIX, MEASURED NOT GUESSED. At an
	 *   observed `window.innerWidth` of 375 on staging 2026-08-29 the line
	 *   wrapped mid-title, leaving "The" on one line and "Amazon" on the next,
	 *   so a reader scanning for the book they bought sees a broken name. A
	 *   title is one token; only the separators may break.
	 */
	$bhp_link_parts[] = '<a href="' . esc_url( $bhp_link['url'] ) . '" target="_blank" rel="noopener" style="color:#173f2f;font-weight:700;text-decoration:underline;white-space:nowrap;">'
		. esc_html( $bhp_link['label'] )
		. '</a>';
}

/*
 * ⭐ SEPARATED BY A MIDDOT ON ONE LINE, exactly as the approved copy sets it
 *    out ("The Mariana Trench · Mount Everest · The Amazon"). ⛔ NOT an em
 *    dash, which is forbidden in this store's email copy, and not a bulleted
 *    list, which turns one line into a menu.
 */
echo implode( ' <span style="color:#6b6b60;">&middot;</span> ', $bhp_link_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above.
?>
</p>

<?php foreach ( $copy['body_after'] as $bhp_paragraph ) : ?>
<p><?php echo esc_html( $bhp_paragraph ); ?></p>
<?php endforeach; ?>

<p style="margin:18px 0 0;">
<?php
$bhp_signoff_lines = array();

foreach ( $copy['signoff'] as $bhp_signoff_line ) {
	$bhp_signoff_lines[] = esc_html( $bhp_signoff_line );
}

echo implode( '<br>', $bhp_signoff_lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each line escaped above.
?>
</p>

<?php if ( ! empty( $copy['signoff_tagline'] ) ) : ?>
<p style="margin:4px 0 0;font-style:italic;"><?php echo esc_html( $copy['signoff_tagline'] ); ?></p>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE CAN-SPAM BLOCK. IT IS NOT OPTIONAL AND IT IS NOT CONDITIONAL.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ There is no `if` around the address or the unsubscribe link, because
 *    `WC_Email_BHP_Review_Ask::trigger()` refuses to send without an opt-out
 *    URL and `bhp_review_ask_decline_reason()` refuses without an address. By
 *    the time this template renders, both exist. A template-level fallback
 *    would hide a missing one instead of preventing the send.
 *
 * ⚠ The address printed here is WooCommerce's own configured store address,
 *   the same one already at the bottom of every receipt this store sends. No
 *   address is invented anywhere in this feature.
 */
?>
<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0 14px;">

<p style="font-size:12px;color:#6b6b60;margin:0 0 6px;line-height:1.6;">
	<?php echo esc_html( $copy['optout_lead'] ); ?>
	<a href="<?php echo esc_url( $optout_url ); ?>" style="color:#6b6b60;text-decoration:underline;"><?php echo esc_html( $copy['optout_link'] ); ?></a>.
	<?php echo esc_html( $copy['optout_note'] ); ?>
</p>

<p style="font-size:12px;color:#6b6b60;margin:0;">
	<?php echo esc_html( $postal_address ); ?>
</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 *
 * ⚠ The theme's order-email footer note ("Printed and fulfilled by our
 *   publishing partner, Bookvault.") is scoped by `bhp_email_order_ids()`,
 *   which does NOT include this email's id, so it does not render here. That
 *   is correct: this email is not about a print job, and three weeks after
 *   delivery a fulfilment sentence is noise.
 */
do_action( 'woocommerce_email_footer', $email );
