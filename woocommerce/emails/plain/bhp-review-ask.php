<?php
/**
 * The store-sent review ask — plain-text body.
 *
 * Rendered by `WC_Email_BHP_Review_Ask::get_content_plain()`.
 *
 * ⛔ IT WALKS THE SAME `bhp_review_ask_copy()` ARRAYS AS THE HTML TWIN, in the
 *    same order, and it carries the same three links, the same unsubscribe and
 *    the same postal address. That is not tidiness: a promise, an ask or a
 *    compliance element that exists in one version and not the other is a
 *    defect, and sharing one copy array makes the two physically unable to
 *    drift apart.
 *
 * ⛔ NO CUSTOMER-FACING WORD IS DEFINED IN THIS FILE, with the single
 *    exception of the `Hi there,` fallback greeting, which is the spec's own
 *    `*|ELSE:|*` string.
 *
 * @package WooCommerce\Templates\Emails\Plain
 */

defined( 'ABSPATH' ) || exit;

/*
 * ⛔ THE `=-=-=` BANNER IS SKIPPED WHEN THERE IS NO HEADING, AND THIS EMAIL
 *    NORMALLY HAS NONE. See the note on `heading` in `bhp_review_ask_copy()`:
 *    the H1 is deliberately empty so the approved question is not stated three
 *    times. Printing the banner anyway would open the plain-text email with two
 *    rows of `=-=-=` around a blank line, which is worse than either.
 *
 * ⭐ It still renders if a heading is ever supplied in wp-admin, so the plain
 *    twin follows the HTML one either way.
 */
$bhp_ra_heading = trim( (string) wp_strip_all_tags( $email_heading ) );

if ( '' !== $bhp_ra_heading ) {
	echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
	echo esc_html( $bhp_ra_heading );
	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

$bhp_first_name = ( isset( $order ) && $order instanceof WC_Order ) ? trim( (string) $order->get_billing_first_name() ) : '';

if ( '' !== $bhp_first_name ) {
	/* translators: %s: customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'brave-hearts' ), esc_html( $bhp_first_name ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi there,', 'brave-hearts' ) . "\n\n";
}

foreach ( $copy['body_before'] as $bhp_paragraph ) {
	echo esc_html( $bhp_paragraph ) . "\n\n";
}

echo esc_html( $copy['question'] ) . "\n\n";

foreach ( $copy['body_middle'] as $bhp_paragraph ) {
	echo esc_html( $bhp_paragraph ) . "\n\n";
}

echo esc_html( $copy['links_lead'] ) . "\n\n";

/*
 * ⭐ ONE LINE PER TITLE, LABEL THEN URL. A plain-text reader has no anchor
 *    text, so the middot line from the HTML twin would render three names and
 *    no way to reach any of them. Same three destinations, same order.
 */
foreach ( $copy['links'] as $bhp_link ) {
	echo esc_html( $bhp_link['label'] ) . "\n";
	echo esc_url_raw( $bhp_link['url'] ) . "\n\n";
}

foreach ( $copy['body_after'] as $bhp_paragraph ) {
	echo esc_html( $bhp_paragraph ) . "\n\n";
}

foreach ( $copy['signoff'] as $bhp_signoff_line ) {
	echo esc_html( $bhp_signoff_line ) . "\n";
}

if ( ! empty( $copy['signoff_tagline'] ) ) {
	echo esc_html( $copy['signoff_tagline'] ) . "\n";
}

echo "\n----------------------------------------\n\n";

/*
 * ⛔ THE CAN-SPAM BLOCK, UNCONDITIONAL, EXACTLY AS IN THE HTML TWIN. The send
 *    path refuses without an opt-out URL and without a postal address, so both
 *    exist by the time this renders.
 */
echo esc_html( $copy['optout_lead'] ) . ' ' . esc_html( $copy['optout_link'] ) . ":\n";
echo esc_url_raw( $optout_url ) . "\n\n";
echo esc_html( $copy['optout_note'] ) . "\n\n";
echo esc_html( $postal_address ) . "\n\n";

/*
 * ⛔ `do_action( 'woocommerce_email_footer' )` IS NOT CALLED HERE, AND CALLING
 *    IT WOULD HAVE EMITTED HTML INTO A PLAIN-TEXT EMAIL. The theme hooks
 *    `bhp_email_footer_note_html()` onto that action at priority 5, and it
 *    echoes an `<hr>` and a `<p><small>`. WooCommerce's own plain templates do
 *    not fire that action either, for exactly this reason.
 *
 * ⭐ `bhp_email_plain_footer()` is the theme's plain-text equivalent. This id
 *    is deliberately NOT in `bhp_email_order_ids()`, so it prints only the
 *    store's global footer text (site title and store address) with the
 *    `<br />` repaired to a newline — the pre-existing "Brave Hearts
 *    Publishing580 Hyde Ave" defect that helper exists to fix.
 */
if ( function_exists( 'bhp_email_plain_footer' ) ) {
	bhp_email_plain_footer( $email );
}
