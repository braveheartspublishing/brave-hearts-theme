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
<?php
/*
 * ⚠ CONDITIONAL FROM 1.19.362, AND THE CONDITION IS THE POINT. The approved
 *   visit touch-1 set has NO bolded question and NO "Find the one you read:"
 *   line, because Merry's template has neither. ⛔ Rendering these blocks
 *   unconditionally would print an empty <strong> band above the link, which
 *   reads as a broken email. Inventing a question to fill the slot would be
 *   writing copy into an email whose copy is locked (Standing Rules §9).
 *
 * ⭐ THE SUPERSEDED 21-DAY SET RENDERS BYTE-FOR-BYTE AS IT DID, because its
 *    `question`, `body_middle` and `links_lead` are all non-empty.
 */
?>
<?php if ( '' !== trim( (string) $copy['question'] ) ) : ?>
<p style="margin:22px 0;"><strong style="font-size:18px;"><?php echo esc_html( $copy['question'] ); ?></strong></p>
<?php endif; ?>

<?php foreach ( $copy['body_middle'] as $bhp_paragraph ) : ?>
<p><?php echo esc_html( $bhp_paragraph ); ?></p>
<?php endforeach; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.369 · THE STAR ROW, REBUILT. ANDREW, SEAL 998, VERBATIM: *"The
 *     stars look terrible, they should show up just link an amazon review. 5
 *     stars in a row from left to right."*
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHAT IT IS NOW: ONE centred row of FIVE IDENTICAL gold stars, ascending
 *    left to right. Star 1 links to rating 1, star 5 to rating 5 — the same
 *    five URLs as before, reordered in `bhp_review_ask_star_row()`.
 *
 * ⛔⛔ THE SUPERSEDED BLOCK AND ITS REASONING, PRESERVED RATHER THAN DELETED,
 *     because it argued the opposite case and argued it well:
 *
 *       "⛔⛔ EVERY CELL CARRIES ITS VISIBLE TEXT LABEL, NOT A GRAPHIC ALONE,
 *        and the glyph is a real character rather than a shipped image, so
 *        there is nothing to block, nothing to download and nothing to break.
 *        Merry's V2 §5: 'A row of five broken-image icons with no labels is a
 *        dead end for the reader and a wasted send.' ⚠ If anyone later swaps
 *        the glyph for an <img>, the alt text must equal the label and the
 *        visible label must stay."
 *
 *       <td align="center" valign="top" style="padding:0 4px;width:20%;">
 *         <a href="..." style="display:block;padding:10px 2px;...">
 *           <span aria-hidden="true" style="font-size:22px;">[N glyphs]</span>
 *           <span style="font-size:13px;">4 stars: really good</span>
 *         </a>
 *       </td>
 *
 * ⛔ WHY THE GLYPH LOST, AND SEAL 998 IS NOT THE ONLY REASON. U+2605 is
 *    substituted by Gmail's Android and iOS clients with a COLOUR EMOJI and by
 *    several Outlook builds with a box, so "a row of five stars" was never
 *    reliably a row of five stars in the two clients that matter most. A PNG is
 *    the same picture everywhere it is not blocked.
 *
 * ⭐ AND THE BLOCKED-IMAGE OBJECTION IS ANSWERED, NOT IGNORED. Each <img>
 *    carries alt="1 star" ... "5 stars" INSIDE its link, so a client that
 *    blocks images renders five underlined text links in the same order; the
 *    caption line below the row says what the row is for, in body text that is
 *    never blocked; and `links` still carries a plain "Or open the review page"
 *    link. ⛔ There is no state in which this block is a dead end.
 *
 * ⛔ TABLE-BASED AND INLINE-STYLED, unchanged and for the unchanged reason:
 *    Outlook's Word renderer ignores flex, grid and most block layout, and a
 *    <div> row collapses to five stacked lines with no alignment.
 *
 * ⭐ TAP TARGETS. 32px of image plus 6px of padding on every side is a 44px
 *    square, which is the documented minimum, and the row is a fixed-layout
 *    table centred inside its own full-width cell, so it cannot wrap or spread
 *    at 375px. ⚠ COMPUTED FROM THE DECLARED SIZES, NOT MEASURED IN A REAL
 *    CLIENT — no email client was opened in this build. See the deliverable.
 *
 * ⛔⛔ NOTHING STEERS TOWARD FIVE, AND THIS ROW SATISFIES MERRY'S V2 §5 RULE 1
 *     MORE STRICTLY THAN ITS PREDECESSOR DID, NOT LESS. Every cell holds the
 *     SAME image at the SAME size with the SAME padding and the SAME tap
 *     target. No default selection, no highlight, no hover state.
 *     ⚠ WHAT CHANGED, AND IT IS A REAL TRADE, REPORTED NOT HIDDEN: the rating
 *     each star carries is no longer stated in visible text beside it. It
 *     survives in the alt attribute and in the plain-text part. That is how
 *     Amazon's own row works and it is what seal 998 asked for; it is
 *     nonetheless less explicit for a sighted reader with images enabled.
 */
$bhp_stars        = function_exists( 'bhp_review_ask_star_row' ) ? bhp_review_ask_star_row( $order ) : array();
$bhp_star_caption = isset( $copy['stars_caption'] ) ? trim( (string) $copy['stars_caption'] ) : '';
?>
<?php if ( ! empty( $bhp_stars ) ) : ?>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:24px 0 10px;border-collapse:collapse;">
	<tr>
		<td align="center" style="padding:0;">
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;table-layout:fixed;margin:0 auto;">
				<tr>
<?php foreach ( $bhp_stars as $bhp_star ) : ?>
					<td align="center" valign="middle" style="padding:0;">
						<a href="<?php echo esc_url( $bhp_star['url'] ); ?>" target="_blank" rel="noopener" style="display:block;padding:6px;color:#173f2f;text-decoration:underline;font-size:13px;line-height:1.2;">
<?php if ( '' !== (string) $bhp_star['image'] ) : ?>
							<img src="<?php echo esc_url( $bhp_star['image'] ); ?>" width="32" height="32" alt="<?php echo esc_attr( $bhp_star['alt'] ); ?>" style="display:block;width:32px;height:32px;border:0;outline:none;text-decoration:none;">
<?php else : ?>
							<?php echo esc_html( $bhp_star['alt'] ); ?>
<?php endif; ?>
						</a>
					</td>
<?php endforeach; ?>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php endif; ?>

<?php
/*
 * ⭐ THE CAPTION, IN BODY TEXT, CENTRED UNDER THE ROW. ⛔ NOT <strong>: the
 *    superseded `links_lead` rendered bold and 1.19.365 flagged that as a
 *    rendering deviation from Merry's own page. The brief asks for "one caption
 *    line in body text", so it is one caption line in body text.
 */
?>
<?php if ( '' !== $bhp_star_caption ) : ?>
<p style="margin:0 0 20px;text-align:center;"><?php echo esc_html( $bhp_star_caption ); ?></p>
<?php endif; ?>

<?php
/*
 * ⚠ `links_lead` IS EMPTY IN ALL THREE LIVE SETS AT 1.19.369, so this block
 *   renders nothing. It is kept because the superseded 21-day set still carries
 *   "Find the one you read:" and must still render byte-for-byte.
 */
?>
<?php if ( '' !== trim( (string) $copy['links_lead'] ) ) : ?>
<p style="margin:22px 0 6px;"><strong><?php echo esc_html( $copy['links_lead'] ); ?></strong></p>
<?php endif; ?>

<?php
/*
 * ⭐ 1.19.369 · THE LINE UNDER THE BLOCK IS NO LONGER THE BARE BOOK TITLE.
 *    Round-8 brief, item 1: *"Remove the bare title link line under the block
 *    (or make it 'Or open the review page' if copy_is_usable needs a link)."*
 *    ⚠ IT DOES NEED ONE — `bhp_review_ask_copy_is_usable()` requires a
 *    non-empty `links` array — so the second option is the one taken, and the
 *    copy sets now carry that label. See `bhp_review_ask_copy_visit_touch1()`.
 *
 * ⛔ AND THE STYLING FOLLOWS THE CONTENT. One quiet secondary link is centred,
 *    small and unbolded under the caption; the SUPERSEDED THREE-TITLE ROW is
 *    still bold, still middot-separated and still `white-space:nowrap`, which
 *    is the measured 2026-08-29 mobile fix and must not be lost. The branch is
 *    on how many links the copy set carries, so the 21-day set renders exactly
 *    as it did and nothing had to be duplicated.
 */
$bhp_link_single = ( 1 === count( (array) $copy['links'] ) );
?>
<p style="margin:0 0 22px;line-height:1.9;<?php echo $bhp_link_single ? 'text-align:center;font-size:14px;' : ''; ?>">
<?php
$bhp_link_parts = array();

foreach ( $copy['links'] as $bhp_link ) {
	/*
	 * ⚠ `white-space:nowrap` IS A MOBILE FIX, MEASURED NOT GUESSED. At an
	 *   observed `window.innerWidth` of 375 on staging 2026-08-29 the line
	 *   wrapped mid-title, leaving "The" on one line and "Amazon" on the next,
	 *   so a reader scanning for the book they bought sees a broken name. A
	 *   title is one token; only the separators may break.
	 *
	 * ⛔ IT IS NOT APPLIED TO THE SINGLE "Or open the review page" LINK. That
	 *    is a sentence, not a title, and five unbreakable words at 375px would
	 *    push the line off the side of the email - the exact defect this rule
	 *    exists to prevent, inverted.
	 */
	$bhp_link_parts[] = '<a href="' . esc_url( $bhp_link['url'] ) . '" target="_blank" rel="noopener" style="color:#173f2f;text-decoration:underline;'
		. ( $bhp_link_single ? '' : 'font-weight:700;white-space:nowrap;' )
		. '">'
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
 * ⭐ 1.19.362 — THE P.S., AND IT SITS BELOW THE SIGN-OFF BECAUSE THAT IS WHAT
 *    A P.S. IS. Merry's approved template ends on one, and a postscript moved
 *    above the name stops being a postscript and becomes another paragraph.
 *
 * ⚠ IT RENDERS ONLY WHEN THE COPY SET CARRIES ONE. The superseded 21-day set
 *   has no `postscript` key at all, so `isset()` guards it rather than
 *   `empty()` alone.
 */
?>
<?php if ( ! empty( $copy['postscript'] ) ) : ?>
<p style="margin:16px 0 0;"><?php echo esc_html( $copy['postscript'] ); ?></p>
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
