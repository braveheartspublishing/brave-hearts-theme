<?php
/**
 * Template Name: Video Testimonial Terms and Release
 * Description: The full terms and release behind the one-line disclaimer on the
 * video testimonial submission page. Founder seal 1295 asks for the Apple
 * pattern: a short disclaimer at the point of signing, a link to the whole
 * thing, one checkbox and a typed name.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE RELEASE TEXT IS A SLOT, NOT A DRAFT. IT IS EMPTY ON PURPOSE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ The `ads-knowledge` role is writing the release language in
 *    `CYCLE179-ADS-TESTIMONIAL-DOCTRINE` against the FTC Endorsement Guides
 *    and 16 CFR 465, the rules on children in testimonials, and the guardian
 *    release. ⛔ NOBODY ELSE DRAFTS IT, AND CERTAINLY NOT THIS DESK. A release
 *    is a legal instrument; a plausible-sounding paragraph written by an
 *    engineer to fill a gap is the worst possible outcome here, because it
 *    LOOKS finished and would be signed by real people.
 *
 * ⭐ THE SLOT IS `bhp_testimonial_release_text()`, which returns '' today. When
 *    the approved text exists it is pasted into that one function and
 *    `BHP_TESTIMONIAL_TERMS_VERSION` in `inc/video-testimonials.php` is moved
 *    off `PLACEHOLDER-` in the same edit. ⛔ THOSE TWO CHANGES GO TOGETHER: a
 *    consent record that names a version but points at different text is
 *    evidence of nothing.
 *
 * ⭐ WHILE THE SLOT IS EMPTY THIS PAGE SAYS SO IN PLAIN WORDS RATHER THAN
 *    SHOWING A BLANK. A submitter who is asked to sign for text that is not
 *    there has to be told that, and the suite asserts the notice is present
 *    while the slot is empty.
 *
 * ⛔ THE STRUCTURAL HEADINGS BELOW ARE NOT THE RELEASE. They are the sections
 *    the finished text is expected to cover, kept here so the shape of the
 *    page can be reviewed before the words exist. Each one carries the seal it
 *    comes from. None of them makes a promise.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The approved release text.
 *
 * ⛔ RETURNS AN EMPTY STRING UNTIL THE RELEASE TEXT IS APPROVED AND PASTED HERE.
 *    See this file's header for why it is not filled in with something
 *    plausible in the meantime.
 *
 * @return string Raw HTML of the approved release, or '' when there is none.
 */
function bhp_testimonial_release_text() {
	return (string) apply_filters( 'bhp_testimonial_release_text', '' );
}

get_header();

$release = bhp_testimonial_release_text();
?>

<section class="section" aria-labelledby="bhp-testimonial-terms-title">
	<div class="container container--content">

		<h1 id="bhp-testimonial-terms-title"><?php esc_html_e( 'Video testimonial terms and release', 'brave-hearts' ); ?></h1>

		<?php if ( '' === $release ) : ?>

			<p class="bhp-testimonial__placeholder" role="status">
				<?php esc_html_e( 'PLACEHOLDER PAGE - FOR ANDREW\'S APPROVAL. The terms and release text has not been written or approved yet, so nothing on this page is binding on anybody. The headings below show only the sections the finished text is expected to cover.', 'brave-hearts' ); ?>
			</p>

			<h2><?php esc_html_e( 'What sending a video allows', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. Scope of the permission granted, where it applies and how long it lasts.', 'brave-hearts' ); ?></em></p>

			<h2><?php esc_html_e( 'Children on camera', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. The parent or legal guardian affirmation, and the standing rule that no child is ever named.', 'brave-hearts' ); ?></em></p>

			<h2><?php esc_html_e( 'The reward, and what it is not', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. The reward is earned by meeting the checklist. It does not depend on a favorable opinion, and it is not a payment for one.', 'brave-hearts' ); ?></em></p>

			<h2><?php esc_html_e( 'Disclosure', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. How the reward is disclosed wherever a video is shown.', 'brave-hearts' ); ?></em></p>

			<h2><?php esc_html_e( 'Publication is not promised', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. Meeting the checklist earns the reward. It does not mean the video is used anywhere.', 'brave-hearts' ); ?></em></p>

			<h2><?php esc_html_e( 'What is kept, and for how long', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. The video, the name, the email address, the submitting address and the record of this agreement.', 'brave-hearts' ); ?></em></p>

			<h2><?php esc_html_e( 'Withdrawing', 'brave-hearts' ); ?></h2>
			<p><em><?php esc_html_e( 'Section reserved. How to ask for a video to be removed, and what that does and does not undo.', 'brave-hearts' ); ?></em></p>

		<?php else : ?>

			<div class="bhp-testimonial__release">
				<?php echo wp_kses_post( $release ); ?>
			</div>

		<?php endif; ?>

		<p class="bhp-testimonial__terms-version">
			<?php
			printf(
				/* translators: %s: the terms version string stamped into each consent record. */
				esc_html__( 'Version %s', 'brave-hearts' ),
				esc_html( BHP_TESTIMONIAL_TERMS_VERSION )
			);
			?>
		</p>

		<p>
			<a href="<?php echo esc_url( bhp_testimonial_page_url() ); ?>">
				<?php esc_html_e( 'Back to the submission page', 'brave-hearts' ); ?>
			</a>
		</p>

	</div>
</section>

<?php get_footer(); ?>
