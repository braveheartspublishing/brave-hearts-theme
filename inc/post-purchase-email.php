<?php
/**
 * Post-purchase transactional email support - E1.
 *
 * Companion to the template override at
 * `woocommerce/emails/customer-processing-order.php`. Everything here is
 * theme code: it writes NO WooCommerce setting, NO option and NO order
 * record, so it reverts entirely with a theme rollback and leaves the
 * store's own email configuration exactly as it was found.
 *
 * Spec: Business OS\WORKING-DRAFTS\marketing-growth\
 *       OVERNIGHT-2026-08-03-POST-PURCHASE-SEQUENCE.md §2.2
 *
 * ⛔ Both strings below are CUSTOMER-FACING WORDING and are pending
 *    Andrew's approval. They are staged for review, not approved.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * E1 subject line.
 *
 * Deliberately DEFERS to the WooCommerce admin setting when one exists, so
 * that changing the subject in WooCommerce → Settings → Emails keeps working
 * and is not silently overridden by code. It only supplies a value where the
 * store is currently falling back to WooCommerce's stock "Thank you for your
 * order" - which is the state observed on staging (the
 * `woocommerce_customer_processing_order_settings` option does not exist).
 *
 * @param string   $subject Subject as resolved so far.
 * @param WC_Order $order   Order object.
 * @param WC_Email $email   Email object.
 * @return string
 */
function bhp_e1_processing_order_subject( $subject, $order = null, $email = null ) {
	if ( $email instanceof WC_Email ) {
		$configured = $email->get_option( 'subject' );

		/*
		 * An "is it empty?" test is NOT sufficient here, and getting this
		 * wrong silently disables the whole filter. WC_Email::get_subject()
		 * calls get_option_or_transient( 'subject', $this->get_default_subject() )
		 * BEFORE applying this filter, and that call populates
		 * $this->settings['subject'] with the default as a side effect. So by
		 * the time this callback runs, get_option( 'subject' ) returns
		 * "Your {site_title} order has been received!" even when the store has
		 * never configured a subject at all - measured on staging, WooCommerce
		 * 10.9.1, where reading the same option one line earlier returns ''.
		 *
		 * Comparing against the default is what actually distinguishes "Andrew
		 * typed a subject in WooCommerce → Settings → Emails" from "WooCommerce
		 * filled in its own stock string".
		 */
		$default = method_exists( $email, 'get_default_subject' ) ? $email->get_default_subject() : '';

		if ( '' !== $configured && $configured !== $default ) {
			return $subject; // A real admin setting always wins.
		}
	}

	return __( 'Your order is in - here’s what happens next', 'brave-hearts' );
}
add_filter( 'woocommerce_email_subject_customer_processing_order', 'bhp_e1_processing_order_subject', 10, 3 );

/*
 * ---------------------------------------------------------------------
 * E1's PREHEADER MOVED - 2026-08-03, the email build.
 * ---------------------------------------------------------------------
 * `bhp_e1_mark_rendering_email()` and `bhp_e1_inject_preheader()` used to
 * live here. They are now `bhp_email_mark_rendering()` and
 * `bhp_email_inject_preheader()` in `inc/transactional-emails.php`, which
 * runs the identical mechanism for all seven order emails off a single
 * id-keyed map.
 *
 * ⭐ E1's PREHEADER STRING IS CARRIED VERBATIM into that map:
 *    "A quick note on how your books are made, and when to expect us again."
 *    The injection point (immediately inside `<body>`), the hidden-div
 *    styles, the 60 zero-width-joiner pad and the filter priority (20) are
 *    all unchanged. E1's assembled output was diffed before and after this
 *    move and the only differences are the ones this build intended.
 *
 * ⚠ THEY WERE MOVED, NOT DUPLICATED, AND THAT IS THE POINT. Two callbacks
 *   on `woocommerce_mail_content` both matching `<body` would have injected
 *   the preheader TWICE into every E1.
 *
 * E1's SUBJECT filter above is deliberately NOT moved. It is approved,
 * shipped and correct, and registering a second callback on the same filter
 * from a second file would make the winner depend on include order.
 */
