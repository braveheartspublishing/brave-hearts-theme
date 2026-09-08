<?php
/**
 * Template Name: Video Testimonial Submission
 * Description: The page a parent or grandparent uses to send a short video of
 * themselves with their reader, in exchange for the printed $12.99 Mariana
 * Trench Ocean Coloring Book when the submission meets the checklist.
 * Founder seals 1294 to 1301.
 *
 * ⛔ READ `inc/video-testimonials.php` FIRST. Every decision behind this page
 *    lives there, including the measurement that decided the upload route.
 *    The form, the handler and the emails are in
 *    `inc/video-testimonial-form.php`.
 *
 * ⛔⛔ EVERY VISIBLE STRING REACHED FROM HERE IS PLACEHOLDER COPY AND IS
 *     WAITING ON ANDREW. `BHP_TESTIMONIAL_COPY_APPROVED` is `false`, so the
 *     page prints a band saying so, and the suite asserts the band. The marketing lane is
 *     drafting the real wording in `CYCLE179-MKT-VIDEO-TESTIMONIAL`.
 *
 * ⛔ THE COPY RAILS, RESTATED HERE BECAUSE THIS IS A FILE SOMEONE WILL EDIT:
 *      · Andrew's I-voice. NO "we", "us" or "our" in any visible string.
 *      · NO em dashes in customer copy.
 *      · AMERICAN SPELLING. "coloring", never "colouring".
 *      · NO child's name is asked for, printed or stored. There is no field.
 *      · NEVER promise publication.
 *      · NO mention of Amazon, reviews or ratings.
 *
 * ⚠ THE READER IS A PARENT ON A PHONE holding a video they just filmed. The
 *   375px layout is the primary one, not the fallback.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section" aria-labelledby="bhp-testimonial-title" id="bhp-testimonial">
	<div class="container container--content">
		<?php
		/*
		 * ⛔ NOT ESCAPED HERE ON PURPOSE, AND THAT IS SAFE BECAUSE THE FORM
		 *    ESCAPES EVERY VALUE AT THE POINT IT PRINTS IT. Escaping a block
		 *    of assembled markup a second time renders the tags as text.
		 */
		echo bhp_testimonial_render_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>

<?php get_footer(); ?>
