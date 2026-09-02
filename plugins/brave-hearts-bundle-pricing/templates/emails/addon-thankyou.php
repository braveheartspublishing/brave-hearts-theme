<?php
/**
 * Activity-book thank-you and delivery email — HTML body.
 *
 * Rendered by `WC_Email_BHP_Addon_Thankyou::get_content_html()`.
 * Overridable from the theme at
 * `woocommerce/emails/addon-thankyou.php` like any WooCommerce template.
 *
 * ⛔ THIS FILE CONTAINS NO CUSTOMER-FACING WORDS. Every string comes from
 *    `$copy`, which is `bhp_bundle_addon_thankyou_copy()`. Swapping the
 *    approved copy in must not require editing a template.
 *
 * ⭐ THE DOWNLOAD HREF IS WOOCOMMERCE'S OWN SIGNED PERMISSION URL, taken
 *    from `WC_Order::get_downloadable_items()`. Nothing here builds a link,
 *    computes a key or exposes a file path. `$addon_downloads` is never
 *    empty when this template renders, because the send decision requires
 *    at least one row.
 *
 * BUTTON MARKUP: the same bulletproof table button the E5 template uses.
 * Forest fill #173f2f, ivory text #fffaf0, 1.5px gold #D9A45F border, 8px
 * radius, vertical padding on the <td> rather than the <a>. Outlook desktop
 * will render it square because it ignores `border-radius`; that is
 * accepted rather than fixed with VML, exactly as it is on E5.
 *
 * @package brave-hearts-bundle-pricing
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bhp_improvements = class_exists( FeaturesUtil::class ) && FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $bhp_improvements ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
$bhp_first_name = $order instanceof WC_Order ? $order->get_billing_first_name() : '';
if ( ! empty( $bhp_first_name ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'bhp-bundle-pricing' ), esc_html( $bhp_first_name ) );
} else {
	esc_html_e( 'Hi,', 'bhp-bundle-pricing' );
}
?>
</p>
<?php foreach ( $copy['email']['paragraphs'] as $bhp_paragraph ) : ?>
<p><?php echo esc_html( $bhp_paragraph ); ?></p>
<?php endforeach; ?>
<?php echo $bhp_improvements ? '</div>' : ''; ?>

<?php
/*
 * ⭐ SKIPPED WHEN EMPTY. The approved copy has no heading above the
 *    button, so `download_lead` is an empty string and this element does
 *    not render at all. It is not deleted, because a one-string change in
 *    the copy file must be able to bring it back.
 */
if ( '' !== trim( (string) $copy['email']['download_lead'] ) ) :
	?>
<h2 style="margin:24px 0 8px;font-size:17px;"><?php echo esc_html( $copy['email']['download_lead'] ); ?></h2>
<?php endif; ?>

<?php foreach ( $addon_downloads as $bhp_download ) : ?>
<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin:10px 0 14px;">
	<tr>
		<td align="center" bgcolor="#173f2f" style="background-color:#173f2f;border:1.5px solid #D9A45F;border-radius:8px;padding:14px 26px;mso-padding-alt:14px 26px;">
			<a href="<?php echo esc_url( $bhp_download['download_url'] ); ?>" target="_blank" rel="noopener" style="color:#fffaf0;font-family:Archivo,Arial,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.06em;line-height:20px;text-decoration:none;">
			<?php
			/*
			 * ⭐ 1.8.38 - ONE LABEL PER FILE, not one label for the loop.
			 *    The order can now carry the activity book AND the
			 *    Vocabulary Card Activity, and two buttons both reading
			 *    "Download the Activity Book (PDF)" would be worse than one.
			 *    The resolver keys on the download id (the file hash) and
			 *    falls back to the activity book's own label for anything it
			 *    does not recognise, so this loop can never render an empty
			 *    button.
			 */
			printf(
				esc_html(
					function_exists( 'bhp_bundle_addon_download_button_label' )
						? bhp_bundle_addon_download_button_label( $bhp_download, $copy )
						: $copy['email']['download_button']
				),
				esc_html( $bhp_download['download_name'] )
			);
			?>
			</a>
		</td>
	</tr>
</table>
<?php endforeach; ?>

<?php
/*
 * ⭐ BODY PARAGRAPHS THAT FOLLOW THE DOWNLOAD.
 *
 * ⚠ ADDED IN THE COPY SWAP, and it is the one structural change that swap
 *   required. The approved draft puts the button after the FIRST paragraph
 *   and three more paragraphs after it. Rendering all four before the
 *   button would have reordered approved prose, which is a silent rewrite
 *   of locked copy. Six lines of template is the cheaper correction.
 *
 * Absent or empty in a copy array, this renders nothing.
 */
if ( ! empty( $copy['email']['paragraphs_after'] ) ) :
	foreach ( $copy['email']['paragraphs_after'] as $bhp_paragraph_after ) :
		?>
<p><?php echo esc_html( $bhp_paragraph_after ); ?></p>
		<?php
	endforeach;
endif;
?>

<?php
/* ⭐ SKIPPED WHEN EMPTY, same reasoning as `download_lead` above. */
if ( '' !== trim( (string) $copy['email']['link_note'] ) ) :
	?>
<p style="font-size:14px;color:#6b6b60;margin:0 0 22px;"><?php echo esc_html( $copy['email']['link_note'] ); ?></p>
<?php endif; ?>

<hr style="border:none;border-top:1px solid #e5e0d3;margin:28px 0;">

<p style="margin:0;"><?php echo esc_html( $copy['email']['closing'] ); ?></p>

<?php
/*
 * ⭐ THE SIGN-OFF. Andrew's name over the company name, then the brand
 *    line in italics, exactly as the approved draft sets it out.
 *
 * ⚠ OPEN ANDREW CONFIRM. Signing as a person rather than an unsigned
 *   company voice is the `marketing-growth` gate G-C and is not settled. See
 *   `bhp_bundle_addon_thankyou_copy()`'s `open_confirms`.
 *
 * ⚠ "Brave Hearts Publishing" will appear a SECOND time immediately below
 *   this, in WooCommerce's own footer ({site_title} + store address). That
 *   duplication is observed and disclosed, not fixed: the copy is approved
 *   and the footer is a store-wide option no agent changes.
 */
if ( ! empty( $copy['email']['signoff'] ) ) :
	?>
<p style="margin:18px 0 0;">
	<?php
	$bhp_signoff_lines = array();
	foreach ( $copy['email']['signoff'] as $bhp_signoff_line ) {
		$bhp_signoff_lines[] = esc_html( $bhp_signoff_line );
	}
	echo implode( '<br>', $bhp_signoff_lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each line escaped above.
	?>
</p>
	<?php
endif;

if ( ! empty( $copy['email']['signoff_tagline'] ) ) :
	?>
<p style="margin:4px 0 0;font-style:italic;"><?php echo esc_html( $copy['email']['signoff_tagline'] ); ?></p>
	<?php
endif;
?>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 *
 * ⚠ The theme's order-email footer note ("Printed and fulfilled by our
 *   publishing partner, Bookvault.") is scoped by `bhp_email_order_ids()`
 *   and does NOT include this email's id, so it does not render here.
 *   That is correct and deliberate: a PDF download is not printed or
 *   fulfilled by Bookvault, and saying so would be a false statement about
 *   this product on the email that delivers it.
 */
do_action( 'woocommerce_email_footer', $email );
