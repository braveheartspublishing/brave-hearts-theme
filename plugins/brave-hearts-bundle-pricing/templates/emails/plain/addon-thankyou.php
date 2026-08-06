<?php
/**
 * Activity-book thank-you and delivery email — PLAIN TEXT twin.
 *
 * Same promises as the HTML sibling, and the same strings, from the same
 * `bhp_bundle_addon_thankyou_copy()` array. A plain-text reader gets the
 * signed download URL as text, because there is no button to press.
 *
 * ⛔ NO CUSTOMER-FACING WORDS ARE DEFINED HERE.
 *
 * ⛔⛔ NEVER `esc_html()` A COPY STRING IN THIS FILE. THIS IS PLAIN TEXT,
 *     NOT HTML, AND `esc_html()` IS AN HTML-CONTEXT ESCAPER.
 *
 *     ⭐ A REAL DEFECT, OBSERVED ON STAGING 2026-08-04, NOT A THEORETICAL
 *        ONE. The 1.8.21 placeholder copy contained no apostrophe, so this
 *        never surfaced. The approved copy says "Charlotte and Henry's",
 *        and the rendered plain-text email read:
 *
 *          "...Charlotte and Henry&#039;s adventures..."
 *
 *        A plain-text reader has no browser to decode that entity, so the
 *        customer would have seen the raw `&#039;`. The HTML twin was and
 *        is correct, because there `esc_html()` is the right escaper and
 *        the entity decodes on render.
 *
 *     ⭐ THE REPLACEMENT IS `wp_strip_all_tags()`: it removes markup, which
 *        is the only escaping a plain-text stream can meaningfully need,
 *        and it does NOT entity-encode. The approved words therefore reach
 *        the reader byte-identical to the copy file.
 *
 *     ⚠ `tests/test-addon-email-guard.php` section 2c now asserts that
 *       every copy string survives `wp_strip_all_tags()` unchanged, so a
 *       future copy that would be mangled here fails the suite instead of
 *       reaching an inbox.
 *
 * ⚠ `bhp_email_plain_footer()` is a THEME function
 *   (`inc/transactional-emails.php`). This is plugin code and must not
 *   fatal if the theme is ever switched, so it is called only when it
 *   exists and falls back to WooCommerce's own footer text otherwise.
 *   The theme function's Bookvault fulfilment sentence is scoped to
 *   `bhp_email_order_ids()`, which does not contain this email's id, so it
 *   correctly does not appear on a PDF download email either way.
 *
 * @package brave-hearts-bundle-pricing
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo wp_strip_all_tags( $email_heading ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

$bhp_first_name = $order instanceof WC_Order ? $order->get_billing_first_name() : '';
if ( ! empty( $bhp_first_name ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( __( 'Hi %s,', 'bhp-bundle-pricing' ), wp_strip_all_tags( $bhp_first_name ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
} else {
	echo __( 'Hi,', 'bhp-bundle-pricing' ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
}

foreach ( $copy['email']['paragraphs'] as $bhp_paragraph ) {
	echo wp_strip_all_tags( $bhp_paragraph ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
}

echo "----------------------------------------\n\n";

/* ⭐ SKIPPED WHEN EMPTY. The approved copy has no heading above the link. */
if ( '' !== trim( (string) $copy['email']['download_lead'] ) ) {
	echo wp_strip_all_tags( $copy['email']['download_lead'] ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
}

foreach ( $addon_downloads as $bhp_download ) {
	echo wp_strip_all_tags( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
		sprintf(
			$copy['email']['download_button'],
			$bhp_download['download_name']
		)
	) . "\n";
	echo esc_url_raw( $bhp_download['download_url'] ) . "\n\n";
}

/*
 * ⭐ THE PARAGRAPHS THAT FOLLOW THE DOWNLOAD, in the approved order. The
 *    HTML twin's comment explains why this loop exists.
 */
if ( ! empty( $copy['email']['paragraphs_after'] ) ) {
	foreach ( $copy['email']['paragraphs_after'] as $bhp_paragraph_after ) {
		echo wp_strip_all_tags( $bhp_paragraph_after ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
	}
}

/* ⭐ SKIPPED WHEN EMPTY, same reasoning as `download_lead` above. */
if ( '' !== trim( (string) $copy['email']['link_note'] ) ) {
	echo wp_strip_all_tags( $copy['email']['link_note'] ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
}

echo "----------------------------------------\n\n";

echo wp_strip_all_tags( $copy['email']['closing'] ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.

/*
 * ⭐ THE SIGN-OFF. ⚠ Open Andrew confirm (gate G-C), and "Brave Hearts
 *    Publishing" repeats in the footer below. Both disclosed in the copy
 *    file rather than silently corrected.
 */
if ( ! empty( $copy['email']['signoff'] ) ) {
	foreach ( $copy['email']['signoff'] as $bhp_signoff_line ) {
		echo wp_strip_all_tags( $bhp_signoff_line ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
	}
}
if ( ! empty( $copy['email']['signoff_tagline'] ) ) {
	echo wp_strip_all_tags( $copy['email']['signoff_tagline'] ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text stream; esc_html() would emit HTML entities a plain reader cannot decode.
}
echo "\n";

echo "----------------------------------------\n\n";

if ( function_exists( 'bhp_email_plain_footer' ) ) {
	bhp_email_plain_footer( $email );
} else {
	$bhp_footer = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
	$bhp_footer = preg_replace( '#<br\s*/?>#i', "\n", $bhp_footer );
	echo wp_kses_post( $bhp_footer );
}
