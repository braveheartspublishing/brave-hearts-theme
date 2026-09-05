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
 *
 * ⭐⭐ 1.19.373 · THE HEADER GOES THROUGH A WRAPPER, NOT STRAIGHT TO OUTPUT.
 *     On the school-visit fork `$email_heading` is '' (seal 1010,
 *     `bhp_visit_email_suppress_heading()`), and WooCommerce's own
 *     `emails/email-header.php` still emits `<h1></h1>` inside a cell with
 *     20px of top padding — a cream band above a hero photograph.
 *
 * ⛔ 1.19.372 TRIED TO REMOVE THAT ON `woocommerce_mail_content` AND FAILED,
 *    because that filter runs inside `WC_Email::send()`, AFTER
 *    `get_content_html()` has already produced the document the suite and the
 *    staging renders read. The wrapper buffers this action instead, so the
 *    element is gone from the render itself. Full diagnosis in
 *    `inc/transactional-emails.php` on `bhp_email_strip_empty_heading()`.
 *
 * ⛔ THE ACTION STILL FIRES, unchanged, with the same two arguments. Nothing
 *    hooked to it is skipped or reordered.
 *
 * ⚠ THE FALLBACK IS THE ORIGINAL LINE. If `inc/transactional-emails.php` is
 *   not loaded, this template renders exactly as it did before 1.19.373
 *   rather than fataling.
 */
if ( function_exists( 'bhp_email_header_without_empty_band' ) ) {
	bhp_email_header_without_empty_band( $email_heading, $email );
} else {
	do_action( 'woocommerce_email_header', $email_heading, $email );
}

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

/*
 * ⭐ 1.19.370 · THE DAY-0 HERO, AND IT IS GATED ON THE VISIT FORK, NOT ON THE
 *    EMAIL ID. `$bhp_visit_body` is non-empty ONLY for an order carrying
 *    `_bhp_school_visit_slug`. ⛔ AN ORDINARY WEB RECEIPT GETS NO PHOTOGRAPH:
 *    a picture of a school read-aloud at the top of a shipping confirmation
 *    for a family that was never at a school visit says something untrue.
 *
 * ⭐ THE FRAME IS THE WIDE-ROOM ONE (`...-01.jpg` for Dallas Harris), per
 *    `CYCLE179-DES-REVIEW-EMAIL.md` §7: the room frame suits the thank-you,
 *    the reading frame suits the later review ask.
 *
 * ⚠ BELOW THE H1 RATHER THAN ABOVE IT, for the reason given in
 *   `woocommerce/emails/bhp-review-ask.php`: overriding
 *   `emails/email-header.php` is prohibited in this theme.
 */
$bhp_visit_hero = ( ! empty( $bhp_visit_body ) && function_exists( 'bhp_review_ask_hero' ) )
	? bhp_review_ask_hero( $order, 'day0' )
	: array();
?>

<?php if ( ! empty( $bhp_visit_hero ) ) : ?>
<p style="margin:0 0 22px;">
	<img src="<?php echo esc_url( $bhp_visit_hero['url'] ); ?>" width="<?php echo esc_attr( (string) $bhp_visit_hero['width'] ); ?>" alt="<?php echo esc_attr( $bhp_visit_hero['alt'] ); ?>" style="display:block;width:100%;max-width:<?php echo esc_attr( (string) $bhp_visit_hero['width'] ); ?>px;height:auto;border:0;outline:none;text-decoration:none;border-radius:6px;">
</p>
<?php endif; ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ SEAL 1010 · EXACTLY ONE GREETING. Andrew Signore, 2026-09-05, verbatim
 *     (⛔ RELAYED through Gandalf, not heard first-hand): *"There is a double
 *     'Hi Aragorn, Hi Aragorn' -- needs to be fixed"*.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHAT WAS ACTUALLY WRONG, OBSERVED NOT GUESSED. The rendered 1.19.370
 *    day-0 message (`REVIEW-SEQ-STAGING\rs370-day0.html`, lines 53-55, read at
 *    this desk 2026-09-05) opened `div.email-introduction` with THIS
 *    template's own `Hi %s,` paragraph and then immediately printed the
 *    approved copy's own first line, which is also `Hi {ParentFirstName},`.
 *    Two greetings, one after the other, because two authors each supplied
 *    one.
 *
 * ⭐ WHICH ONE SURVIVES, AND WHY. The COPY's line stands: it is Andrew's
 *    approved wording in `CYCLE179-MKT-REVIEW-SEQ-V2.md` §1 and it is the one
 *    that merges `{ParentFirstName}` through the sequence's own resolver. This
 *    template's greeting is engineering furniture and yields.
 *
 * ⛔ THE STANDARD (NON-VISIT) COMPLETED-ORDER EMAIL IS UNTOUCHED. It has no
 *    copy set, so `$bhp_visit_body` is empty, so the `else` branch below still
 *    prints exactly the greeting it printed in 1.19.314 and every build since.
 *    ⚠ Whitespace inside this block is load-bearing for that byte equality —
 *    see the note further down about PHP eating exactly one newline.
 */
?>
<?php if ( empty( $bhp_visit_body ) ) : ?>
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
<?php endif; ?>
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
 * ⭐⭐ 1.19.374 · SEAL 1025 · THE HAND-DELIVERY ROW, SHORTENED FOR DAY 0 ONLY.
 *
 * ⛔ THE SCOPING IS THE POINT AND IT IS DELIBERATELY MECHANICAL: the callback
 *    is attached on the line before the action and detached on the line after
 *    it, and only when `$bhp_visit_body` is non-empty. An ordinary web receipt
 *    never has it attached at any moment, so its order summary is
 *    byte-identical to 1.19.373. Nothing is left hooked after this template
 *    returns, so a second email rendered in the same request is unaffected.
 *
 * ⛔ `$plain_text` IS NOT EXCLUDED AND THAT IS ON PURPOSE. The plain-text
 *    email builds its totals from the same rows; a forty-word right-aligned
 *    paragraph is a forty-word paragraph there too, and the shortened value's
 *    wrapper div is stripped by WooCommerce's own plain-text conversion.
 *
 * ⚠ FALLBACK: if `inc/visit-completed-email.php` is not loaded, this block is
 *   skipped entirely and the action fires exactly as it did before 1.19.374.
 *
 * Full rationale, including why no new copy was minted, is on
 * `bhp_visit_email_shorten_pickup_row()`.
 */
$bhp_visit_shorten_row = ( ! empty( $bhp_visit_body ) && function_exists( 'bhp_visit_email_shorten_pickup_row' ) );

if ( $bhp_visit_shorten_row ) {
	add_filter( 'woocommerce_get_order_item_totals', 'bhp_visit_email_shorten_pickup_row', 99, 2 );
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

if ( $bhp_visit_shorten_row ) {
	remove_filter( 'woocommerce_get_order_item_totals', 'bhp_visit_email_shorten_pickup_row', 99 );
}

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

<?php endif; ?>
<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.372 · THE VISIT EMAIL ENDS WITH TOUCH 1'S SIGNATURE BLOCK.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER INSTRUCTION, ROUND 11 (⛔ RELAYED through Gandalf, not heard
 *    first-hand): day 0 must end with the SAME block as touch 1 — Andrew
 *    Signore / Author | Brave Hearts Publishing / Big Places. Brave Hearts. /
 *    the social line from `bhp_social_links` — and not the WooCommerce
 *    `<em>Big Places. Brave Hearts.</em>` sign-off it had been ending on.
 *
 * ⛔ THE MARKUP IS NOT COPIED HERE. `bhp_review_ask_signature_html()` renders
 *    it, including its own `<hr>`, and `woocommerce/emails/bhp-review-ask.php`
 *    calls the same function. One block, one place to change it, no drift.
 *
 * ⛔⛔ THE STANDARD COMPLETED-ORDER EMAIL KEEPS ITS OLD SIGN-OFF EXACTLY.
 *     `$bhp_visit_body` is empty for every ordinary order, so the first branch
 *     below emits the same three lines, in the same order, with the same
 *     leading tabs and the same `<em>`, that 1.19.314 emitted. ⛔ THE `<hr>`
 *     THAT USED TO SIT IN THE `else` OF THE BLOCK ABOVE IS GONE FROM THE VISIT
 *     PATH ON PURPOSE: the signature block brings its own rule, and two rules
 *     stacked 28px apart is what "the same as touch 1" is not.
 *
 * ⚠ WHITESPACE IS LOAD-BEARING HERE. PHP eats exactly one newline after each
 *   `?>`, which is why these tags are tight against each other.
 */
?>
<?php if ( empty( $bhp_visit_body ) ) : ?>
<p style="margin:0;">
	<?php esc_html_e( 'Andrew', 'brave-hearts' ); ?><br>
	<?php esc_html_e( 'Brave Hearts Publishing', 'brave-hearts' ); ?><br>
	<em><?php esc_html_e( 'Big Places. Brave Hearts.', 'brave-hearts' ); ?></em>
</p>
<?php else : ?>
<?php
/*
 * ⚠ A `//` COMMENT WOULD SWALLOW THE `?>` ON THE SAME LINE — PHP ends a
 *   one-line comment at a closing tag — so the phpcs annotation is a block
 *   comment and the tag sits on its own line.
 */
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every dynamic part is escaped inside bhp_review_ask_signature_html().
echo function_exists( 'bhp_review_ask_signature_html' ) ? bhp_review_ask_signature_html() : '';
?>
<?php endif; ?>

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
