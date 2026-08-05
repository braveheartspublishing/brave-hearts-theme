<?php
/**
 * Transactional email COPY layer - E1 through E8.
 *
 * Companion to the template overrides in `woocommerce/emails/` and their
 * `plain/` twins. Everything here is theme code: it writes NO WooCommerce
 * setting, NO option and NO order record, so the whole copy layer reverts
 * with a theme rollback and leaves the store's own email configuration
 * exactly as it was found.
 *
 * Spec: Business OS\WORKING-DRAFTS\commerce-cx\
 *       NIGHT-2026-08-03-EMAIL-PROFESSIONALIZATION-DECK.md  §3 (copy),
 *       §6.2 (this file's mechanism), §2.6 (the two-layer footer).
 *
 * ---------------------------------------------------------------------
 * WHY THE STRINGS LIVE HERE AND NOT IN WooCommerce -> Settings -> Emails
 * ---------------------------------------------------------------------
 * A subject typed into wp-admin is invisible to code review, invisible to
 * git blame, does not travel with a deploy, and drifts between staging and
 * production silently. Deck §6.2. The store currently has no
 * `woocommerce_*_settings` option for any email, so this is a clean start
 * with nothing to migrate.
 *
 * Every callback below DEFERS to a real admin setting when one exists, so
 * that typing a subject into WooCommerce -> Settings -> Emails keeps
 * working and is not silently overridden by code. See
 * `bhp_email_admin_value_wins()` for why an "is it empty?" test is not
 * sufficient.
 *
 * ---------------------------------------------------------------------
 * HARD CONSTRAINTS THAT APPLY TO EVERY STRING IN THIS FILE
 * ---------------------------------------------------------------------
 * ⛔ NO EM DASH (U+2014). Standing email rule.
 * ⛔ NO duration, delivery date, production window or transit-time claim.
 * ⛔ NO tracking number, carrier name or tracking link. Nothing in this
 *    system holds one: the installed Bookvault plugin writes zero tracking
 *    numbers, zero carrier names and zero dispatch events.
 * ⛔ NO coupon, discount, upsell, review ask, lead magnet or unsubscribe
 *    link. Receipts are receipts, and keeping the surface empty is also
 *    what keeps the parent and teacher funnels isolated here.
 * ⛔ NO fabricated review, rating, testimonial, statistic or endorsement.
 *
 * ---------------------------------------------------------------------
 * TWO STRINGS ARE TRUE ONLY BECAUSE OF AN OPERATING RULE
 * ---------------------------------------------------------------------
 * E2 says "Your books have shipped" and E7 says "we are refunding it".
 * Neither is derivable from the database. Both are true only under
 * operating commitments Andrew made when he approved this copy:
 *
 *   E2 (deck Q2): an order is marked Completed in wp-admin only AFTER
 *      Bookvault has been seen to show it dispatched.
 *   E7 (deck Q3): a paid order that is cancelled is refunded at the same
 *      time. "Refund on cancellation while books unprinted."
 *
 * ⛔ IF EITHER OPERATING RULE STOPS BEING FOLLOWED, THE CORRESPONDING
 *    EMAIL BECOMES UNTRUE AND MUST BE SWITCHED TO THE DECK'S A2 VARIANT.
 *    That is a copy change, not a code bug, and it is recorded here so a
 *    future reader knows which sentence depends on which promise.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The order-email allowlist.
 *
 * Used to scope the FD-76 D6 fulfilment sentence (§2.6 layer 2) to emails
 * that are actually about an order. A password reset or a new-account email
 * carrying "Printed and fulfilled by our publishing partner, Bookvault."
 * would be nonsense to the reader, which is exactly the class of small
 * wrongness this whole workstream exists to remove. `CYCLE142-CX-037`.
 *
 * ⚠ `customer_partially_refunded_order` is NOT a typo and is NOT a
 *    duplicate. WC_Email_Customer_Refunded_Order::trigger() REWRITES its
 *    own `$this->id` to that value for a partial refund, so an allowlist
 *    that only carried `customer_refunded_order` would silently drop the
 *    footer from every partial-refund email.
 *
 * @return string[]
 */
function bhp_email_order_ids() {
	return array(
		'customer_processing_order',
		'customer_completed_order',
		'customer_refunded_order',
		'customer_partially_refunded_order',
		'customer_on_hold_order',
		'customer_failed_order',
		'customer_note',
		'customer_cancelled_order',
	);
}

/**
 * Does a real admin-entered value exist for this email field?
 *
 * An "is it empty?" test is NOT sufficient here, and getting this wrong
 * silently disables the whole filter. WC_Email::get_subject() calls
 * get_option_or_transient( 'subject', $this->get_default_subject() ) BEFORE
 * applying the subject filter, and that call populates
 * $this->settings['subject'] with the class default as a side effect. So by
 * the time a callback runs, get_option( 'subject' ) returns WooCommerce's
 * own stock string even when the store has never configured a subject at
 * all. Measured on staging, WooCommerce 10.9.1.
 *
 * Comparing against the class default is what actually distinguishes
 * "somebody typed a subject in wp-admin" from "WooCommerce filled in its
 * own stock string".
 *
 * @param WC_Email $email    Email object.
 * @param string   $key      Settings key, e.g. 'subject' or 'heading'.
 * @param string   $fallback Explicit default to compare against, for the
 *                           refunded email's partial variants whose
 *                           defaults take an argument.
 * @return bool True when an admin value exists and must win.
 */
function bhp_email_admin_value_wins( $email, $key, $fallback = null ) {
	if ( ! $email instanceof WC_Email ) {
		return false;
	}

	$configured = $email->get_option( $key );

	if ( null !== $fallback ) {
		$default = $fallback;
	} else {
		$getter  = 'get_default_' . $key;
		$default = method_exists( $email, $getter ) ? $email->$getter() : '';
	}

	return ( '' !== $configured && $configured !== $default );
}

/**
 * Resolve one string, deferring to a real admin value.
 *
 * @param string   $current  Value as resolved so far.
 * @param WC_Email $email    Email object.
 * @param string   $key      Settings key.
 * @param string   $new      The Brave Hearts string.
 * @param string   $fallback Explicit class default, where needed.
 * @return string
 */
function bhp_email_resolve( $current, $email, $key, $new, $fallback = null ) {
	if ( bhp_email_admin_value_wins( $email, $key, $fallback ) ) {
		return $current;
	}

	return $email instanceof WC_Email ? $email->format_string( $new ) : $new;
}

/* -------------------------------------------------------------------------
 * SUBJECTS
 * ---------------------------------------------------------------------- */

/**
 * E1, processing order.
 *
 * ⛔ NOT REGISTERED HERE. E1's subject is approved, shipped, and resolved by
 *    `bhp_e1_processing_order_subject()` in `inc/post-purchase-email.php`,
 *    which is left byte-untouched. Registering a second callback for the
 *    same filter would make the winner depend on include order.
 */

add_filter(
	'woocommerce_email_subject_customer_completed_order',
	function ( $subject, $order = null, $email = null ) {
		// Deck §3.2 VARIANT A. True only under the mark-complete-after-dispatch rule.
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your books have shipped', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_refunded_order',
	function ( $subject, $order = null, $email = null ) {
		$partial = ( $email instanceof WC_Email && ! empty( $email->partial_refund ) );

		if ( $partial ) {
			$default = method_exists( $email, 'get_default_subject' ) ? $email->get_default_subject( true ) : null;
			return bhp_email_resolve(
				$subject,
				$email,
				'subject_partial',
				__( 'A partial refund for order #{order_number}', 'brave-hearts' ),
				$default
			);
		}

		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your refund for order #{order_number}', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_on_hold_order',
	function ( $subject, $order = null, $email = null ) {
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your order is on hold', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_failed_order',
	function ( $subject, $order = null, $email = null ) {
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your payment did not go through', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_note',
	function ( $subject, $order = null, $email = null ) {
		return bhp_email_resolve( $subject, $email, 'subject', __( 'A note about your order #{order_number}', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_subject_customer_cancelled_order',
	function ( $subject, $order = null, $email = null ) {
		// Deck §3.7 VARIANT A. True only under the cancel-and-refund-together rule.
		return bhp_email_resolve( $subject, $email, 'subject', __( 'Your order has been cancelled', 'brave-hearts' ) );
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * HEADINGS
 * ---------------------------------------------------------------------- */

/**
 * E1's H1. The ONLY change this file makes to E1's presentation.
 *
 * The stock heading "Thank you for your order" renders at 32px directly
 * above E1's own opening line "Thank you - your order is confirmed." Two
 * thank-yous in the first 40 pixels. `CYCLE142-CX-029`.
 *
 * ⛔ E1's BODY COPY IS APPROVED AND LOCKED AND IS NOT TOUCHED, HERE OR
 *    ANYWHERE IN THIS BUILD. Standing Rules §9.
 */
add_filter(
	'woocommerce_email_heading_customer_processing_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your order is confirmed', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_completed_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your books have shipped', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_refunded_order',
	function ( $heading, $order = null, $email = null ) {
		$partial = ( $email instanceof WC_Email && ! empty( $email->partial_refund ) );

		if ( $partial ) {
			$default = method_exists( $email, 'get_default_heading' ) ? $email->get_default_heading( true ) : null;
			return bhp_email_resolve(
				$heading,
				$email,
				'heading_partial',
				__( 'We have refunded part of your order', 'brave-hearts' ),
				$default
			);
		}

		return bhp_email_resolve( $heading, $email, 'heading', __( 'We have refunded your order', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_on_hold_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your order is on hold', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_failed_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your payment did not go through', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_note',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'A note about your order', 'brave-hearts' ) );
	},
	10,
	3
);

add_filter(
	'woocommerce_email_heading_customer_cancelled_order',
	function ( $heading, $order = null, $email = null ) {
		return bhp_email_resolve( $heading, $email, 'heading', __( 'Your order has been cancelled', 'brave-hearts' ) );
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * ADDITIONAL CONTENT - suppressed on every order email
 * ---------------------------------------------------------------------- */

/**
 * Kill WooCommerce's stock `additional_content` filler on every order email.
 *
 * `CYCLE142-CX-028`. On E1 it currently renders as the very last thing the
 * reader sees, BELOW Andrew's signature and BELOW the order-number fine
 * print:
 *
 *   "Thanks again! If you need any help with your order, please contact us
 *    at Andrew@braveheartspublishing.com."
 *
 * It is a WooCommerce class default. Nobody wrote it. It duplicates E1's own
 * better line ("Reply to this email - it comes to a real person") and it puts
 * a stock-sounding sentence in the last position. The same string, or a near
 * twin, is the class default on all six of the other order emails.
 *
 * ⚠ An admin-entered value still wins, so this suppresses the DEFAULT and
 *   not a deliberate choice somebody made in wp-admin.
 */
foreach ( bhp_email_order_ids() as $bhp_email_id ) {
	add_filter(
		'woocommerce_email_additional_content_' . $bhp_email_id,
		function ( $content, $order = null, $email = null ) {
			if ( bhp_email_admin_value_wins( $email, 'additional_content' ) ) {
				return $content;
			}
			return '';
		},
		10,
		3
	);
}
unset( $bhp_email_id );

/* -------------------------------------------------------------------------
 * PREHEADERS - the inbox preview line
 * ---------------------------------------------------------------------- */

/**
 * Preheader text, per email id.
 *
 * @return array<string,string>
 */
function bhp_email_preheaders() {
	return array(
		// E1's preheader is already approved and shipped. Carried verbatim.
		'customer_processing_order'         => __( 'A quick note on how your books are made, and when to expect us again.', 'brave-hearts' ),
		'customer_completed_order'          => __( 'They are on their way to you now.', 'brave-hearts' ),
		'customer_refunded_order'           => __( 'The full amount is on its way back to you.', 'brave-hearts' ),
		'customer_partially_refunded_order' => __( 'Part of your order has been refunded.', 'brave-hearts' ),
		'customer_on_hold_order'            => __( 'Nothing is printing yet. We will email you when it moves.', 'brave-hearts' ),
		'customer_failed_order'             => __( 'Nothing was charged. You can try again here.', 'brave-hearts' ),
		'customer_note'                     => __( 'A quick update from us.', 'brave-hearts' ),
		'customer_cancelled_order'          => __( 'Nothing will be printed or sent.', 'brave-hearts' ),
	);
}

/**
 * Capture the identity of the email currently rendering.
 *
 * `woocommerce_mail_content` carries no email identity, so the identity is
 * captured here - `woocommerce_email_header` does receive the email object,
 * and it always fires during the same render, before the content filter.
 *
 * @param string   $email_heading Heading text.
 * @param WC_Email $email         Email object.
 * @return void
 */
function bhp_email_mark_rendering( $email_heading, $email = null ) {
	if ( $email instanceof WC_Email ) {
		$GLOBALS['bhp_rendering_email_id'] = $email->id;
	}
}
add_action( 'woocommerce_email_header', 'bhp_email_mark_rendering', 1, 2 );

/**
 * Inject the preheader (inbox preview text) into the assembled HTML.
 *
 * WooCommerce's `email-header.php` has no preheader slot, and anything
 * echoed on `woocommerce_email_header` lands either before the doctype
 * (priority < 10) or after the masthead (priority > 10), neither of which a
 * mail client reads as preview text. The reliable place is immediately
 * inside `<body>`, so the assembled HTML is patched there, once.
 *
 * The trailing zero-width joiners are the standard preview-text padding:
 * they stop the client spilling the first paragraph of body copy into the
 * inbox preview after the preheader ends.
 *
 * @param string $content Fully assembled, style-inlined email HTML.
 * @return string
 */
function bhp_email_inject_preheader( $content ) {
	if ( empty( $GLOBALS['bhp_rendering_email_id'] ) ) {
		return $content;
	}

	$id = $GLOBALS['bhp_rendering_email_id'];
	unset( $GLOBALS['bhp_rendering_email_id'] );

	$preheaders = bhp_email_preheaders();

	if ( ! isset( $preheaders[ $id ] ) ) {
		return $content;
	}

	if ( false === strpos( $content, '<body' ) ) {
		return $content; // Plain-text or an unexpected wrapper - leave untouched.
	}

	$preheader = '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;color:transparent;">'
		. esc_html( $preheaders[ $id ] )
		. str_repeat( '&#8204;&nbsp;', 60 )
		. '</div>';

	return preg_replace( '/(<body\b[^>]*>)/i', '$1' . $preheader, $content, 1 );
}
add_filter( 'woocommerce_mail_content', 'bhp_email_inject_preheader', 20 );

/* -------------------------------------------------------------------------
 * FOOTER - layer 2, order emails only
 * ---------------------------------------------------------------------- */

/**
 * The FD-76 D6 fulfilment sentence and the reply route.
 *
 * Provenance, checked at source rather than accepted from a brief: FD-76
 * limb D6, `Business OS\FOUNDER-DECISIONS-2026-08-01.md` line 2761. Andrew,
 * verbatim: "One fulfilment sentence sitewide: 'Printed and fulfilled by our
 * publishing partner, Bookvault', matching the privacy-policy naming.
 * Retires 'Printed and shipped by Bookvault'." The identical string with a
 * full stop is already enforced sitewide by `inc/audit-remediation.php`.
 *
 * @return string[] Two sentences, in render order.
 */
function bhp_email_footer_note_lines() {
	return array(
		__( 'Printed and fulfilled by our publishing partner, Bookvault.', 'brave-hearts' ),
		__( 'Reply to this email and it comes to a real person.', 'brave-hearts' ),
	);
}

/**
 * Render the order-email footer note (HTML path).
 *
 * ⛔ WHY THIS IS SCOPED AND NOT PUT IN THE GLOBAL FOOTER OPTION.
 *    `woocommerce_email_footer_text`'s stored value is global. Putting a
 *    print-fulfilment sentence in the OPTION would mean a password-reset
 *    email and a new-account email both carry "Printed and fulfilled by our
 *    publishing partner, Bookvault." `CYCLE142-CX-037`.
 *
 * ⛔ WHY THIS IS AN ACTION AND NOT THE `woocommerce_email_footer_text`
 *    FILTER, MEASURED RATHER THAN ASSUMED. That filter LOOKS scopable:
 *    `emails/email-footer.php` calls it as
 *    `apply_filters( 'woocommerce_email_footer_text', $text, $email )`. But
 *    `WC_Emails::email_footer()` is declared `function email_footer()` with
 *    NO parameters and calls `wc_get_template( 'emails/email-footer.php' )`
 *    with NO arguments, so the template's own `$email = $email ?? null;`
 *    resolves to null on EVERY render. Read from
 *    `includes/class-wc-emails.php:388` on staging, WooCommerce 10.9.1, and
 *    confirmed by a first build in which the sentence appeared in all seven
 *    plain twins and in none of the HTML siblings.
 *    ➡ The `woocommerce_email_footer` ACTION does receive `$email`, because
 *      every template fires it as `do_action( 'woocommerce_email_footer',
 *      $email )`. That is what this hooks.
 *
 * Priority 5 puts this above WooCommerce's own footer template (priority
 * 10), so it renders at the end of the body card, immediately below the
 * order-number fine print and immediately above the store name and address.
 * That is the same position, in the same order, as the plain-text twins.
 *
 * ⛔ DO NOT "SIMPLIFY" THIS BY OVERRIDING emails/email-header.php OR
 *    emails/email-footer.php. The `email_improvements` feature flag is
 *    enabled on this store and rewrites both; an override pins the theme to
 *    one branch of core and diverges silently on the next update. Deck §6.3.
 *
 * @param WC_Email|null $email Email object.
 * @return void
 */
function bhp_email_footer_note_html( $email = null ) {
	if ( ! $email instanceof WC_Email ) {
		return;
	}

	if ( ! in_array( $email->id, bhp_email_order_ids(), true ) ) {
		return;
	}

	$lines = bhp_email_footer_note_lines();

	echo '<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0 14px;">';
	echo '<p style="font-size:12px;color:#6b6b60;margin:0;"><small>'
		. esc_html( $lines[0] ) . '<br>' . esc_html( $lines[1] )
		. '</small></p>';
}
add_action( 'woocommerce_email_footer', 'bhp_email_footer_note_html', 5 );

/**
 * Plain-text footer for every Brave Hearts order-email plain twin.
 *
 * Echoes the two footer-note lines, then the store's global footer text,
 * so the plain reader gets exactly the promises the HTML reader gets.
 *
 * Called directly from the `plain/` templates. It does NOT run the
 * `woocommerce_email_footer_text` filter with an `$email` argument, because
 * `bhp_email_footer_note_html()` would then add the same two lines a second
 * time.
 *
 * @param WC_Email|null $email Email object.
 * @return void
 */
function bhp_email_plain_footer( $email = null ) {
	$is_order_email = ( $email instanceof WC_Email && in_array( $email->id, bhp_email_order_ids(), true ) );

	if ( $is_order_email ) {
		foreach ( bhp_email_footer_note_lines() as $line ) {
			echo esc_html( $line ) . "\n";
		}
		echo "\n";
	}

	/*
	 * ⚠ `<br />` REPAIRED, NOT INHERITED. The stored footer option is
	 *   `{site_title}<br />{store_address}`. WC_Email::get_content() runs
	 *   `wp_strip_all_tags()` over the plain-text body, which DELETES the
	 *   `<br />` outright rather than turning it into a line break, so
	 *   WooCommerce's own plain templates render
	 *   "Brave Hearts Publishing580 Hyde Ave, Pocatello..." with the store
	 *   name welded to the street number.
	 *
	 *   OBSERVED, not assumed: it is present in the pre-change baseline
	 *   render of E1's plain twin taken on staging 1.19.155 before this
	 *   build, so it is a pre-existing defect this build repairs rather
	 *   than one it introduced. Converting `<br>` to a newline BEFORE the
	 *   strip is the whole fix.
	 *
	 * One filter argument only, matching WooCommerce's own plain templates.
	 */
	$footer_text = apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
	$footer_text = preg_replace( '#<br\s*/?>#i', "\n", $footer_text );

	echo wp_kses_post( $footer_text );
}

/* -------------------------------------------------------------------------
 * BRAND CSS LAYER
 * ---------------------------------------------------------------------- */

/**
 * Brand typography and a mobile guard, appended to WooCommerce's email CSS.
 *
 * ⚠ `CYCLE142-CX-036`. Webfonts do not render in Gmail, Outlook desktop or
 *   several other major clients. EB Garamond WILL resolve to Georgia for a
 *   large share of recipients and no `@font-face` changes that. This is a
 *   graceful-degradation stack, not a guarantee, and saying otherwise would
 *   be claiming a result nobody observed. Georgia is legitimate AS THE
 *   FALLBACK in a stack, never as the intended face.
 *
 * ⛔ Do not attempt to load the theme's local variable font files into
 *    email. The bracket-filename trap (`Archivo[wdth,wght].ttf`) fails
 *    silently in a browser and would fail silently here too.
 *
 * `@media` blocks cannot be inlined; WooCommerce preserves them in a
 * `<style>` element instead. The assembled output is checked by counting
 * `@media` occurrences, not by trusting that this filter fired.
 *
 * ⛔ Gold `#D9A45F` appears here NOT AT ALL. On the ivory and parchment
 *    grounds of an email body gold measures 1.81:1 to 2.14:1 and fails
 *    everything. It appears in exactly two places in this build: inside the
 *    masthead image, and as the 1.5px BORDER on E5's button. Never as a
 *    colour value. Kit §1.4.
 *
 * @param string $css Existing email CSS.
 * @return string
 */
function bhp_email_brand_styles( $css ) {
	$css .= '
#template_header h1,
#body_content_inner h1,
#body_content_inner h2,
#body_content_inner h3 {
	font-family: "EB Garamond", Georgia, "Times New Roman", serif;
	letter-spacing: normal;
}

#body_content_inner {
	font-size: 16px;
	line-height: 1.6;
}

@media only screen and (max-width: 400px) {
	#body_content_inner {
		font-size: 16px !important;
	}
	#template_header h1 {
		font-size: 26px !important;
	}
}
';

	return $css;
}
add_filter( 'woocommerce_email_styles', 'bhp_email_brand_styles', 20 );
