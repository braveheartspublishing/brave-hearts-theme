<?php
/**
 * MID-POST LEAD CAPTURE — theme 1.19.296, 2026-08-27,
 * `CYCLE167-LD-CAPTURE-FIX-BUILD`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHY A SECOND INLINE CAPTURE EXISTS ON A POST THAT ALREADY HAS ONE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE MEASUREMENT (`CYCLE167-MKT-CAPTURE-ENTICEMENT-R3`, live, both
 *    viewports): EVERY inline capture form on this site sits between **59% and
 *    94% of the way down** a page between 4 and 36 screens long. On
 *    `/blog/reading-level-by-grade-chart/` at 390px the only capture is at
 *    **74% — screen 15.3 of 20.6.** The two posts this build targets are the
 *    same shape. The ask exists; almost nobody scrolls far enough to meet it.
 *
 * ⛔ THE END-OF-POST CAPTURE IS NOT MOVED AND NOT REMOVED. It converts the
 *    reader who finished, which is a genuinely different reader. This is an
 *    additional, quieter ask placed after the introduction.
 *
 * ⚠ HONEST COST, STATED RATHER THAN HIDDEN: this makes the post's third ask
 *   (mid capture + end capture + popup), where `ads-knowledge`'s note records
 *   two. ⛔ That is a real trade-off and it is REPORTED to the Chief of Staff,
 *   not absorbed silently. It is placed inline, in the document flow, so it
 *   obstructs nothing: Google's intrusive-interstitial rule does not engage
 *   and NN/g's objection to modal mailing-list asks does not apply.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ EVERY WORD BELOW IS COPY THAT IS ALREADY LIVE. NOTHING IS INVENTED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The teardown lane's R6 is explicit: *"Do not write new copy for this; move the copy that
 * already works."* So, AT 1.19.296:
 *   · "Start with one free chapter" was the live homepage capture heading.
 *   · The contents sentence was the wording already live in
 *     `footer-capture.php`, `exit-intent-popup.php` and `post-end-capture.php`.
 *   · "Send me the Kit" and the privacy line were reused from the same set.
 *
 * ⚠ THE FIRST THREE BULLETS ARE WRITTEN IN THE PAST TENSE ON PURPOSE — THEY
 *   WERE TRUE OF 1.19.296 AND ARE NOT TRUE NOW. 1.19.297 replaced the heading
 *   and the button (see the block above the markup). ⭐ They are KEPT rather
 *   than deleted because the R6 reasoning is still the reason this panel exists
 *   and because the teardown's later finding — that the "copy that already
 *   works" was itself twelve different offers — only makes sense next to the
 *   instruction it superseded. ⛔ The privacy line IS still reused verbatim.
 *
 * ⭐ AND THE CONTENTS SENTENCE WAS INDEPENDENTLY CHECKED AGAINST THE ARTEFACT
 *    THIS BUILD, not merely inherited: page 1 of the live
 *    `Reluctant-Reader-Adventure-Kit-1.pdf` was fetched from the production
 *    document root and all seven pages read. It genuinely contains one real
 *    chapter (Chapter 7, "The Swordfish", from *The Mariana Trench*), a
 *    printable explorer activity ("Create Your Own Deep-Sea Creature"), and a
 *    note to the parent. ⭐ "a sample chapter, a printable activity, and tips
 *    for reading it with a 6 to 9 year old" is TRUE of the real file.
 *
 * VOICE — standing rule §9.1: I/me/my, never "we". No em dash. Ages 6 to 9,
 * never 5 to 9. ⛔ NO OUTCOME CLAIM: the copy says what the Kit CONTAINS and
 * never what it will do to a child.
 *
 * ⛔ NO `success_redirect_key`, matching `post-end-capture.php` exactly, so
 *    this posts and returns to the same post with `?bhp_signup=success` and
 *    fires `lead_signup_success` inline. `CYCLE165-BOR-101`'s proposal to
 *    redirect inline captures to a thank-you page is ANDREW'S DECISION and is
 *    deliberately not pre-empted here.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$panel_id   = wp_unique_id( 'bhp-post-midcapture-' );
$heading_id = $panel_id . '-title';
?>
<aside id="<?php echo esc_attr( $panel_id ); ?>" class="bhp-post-capture bhp-post-capture--mid" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
	<div class="bhp-post-capture__intro">
		<?php
		/*
		 * ⭐⭐ 1.19.297 (`CYCLE167-LD-CAPTURE-COPY-APPLY`) — THE OFFER NAME.
		 *
		 * ⭐ This panel shipped ONE RELEASE AGO with copy deliberately borrowed
		 *    from the surfaces that already existed ("move the copy that already
		 *    works", `CYCLE167-MKT-CAPTURE-ENTICEMENT-R3` R6). That was right then and is superseded now: the
		 *    teardown's 12-of-12 finding is that the borrowed copy was itself
		 *    twelve different offers, and the founder picked one (carrier item
		 *    290). ⛔ SO THE STRINGS BELOW ARE BYTE-IDENTICAL TO THE POPUP'S AND
		 *    TO THE END-OF-POST PANEL'S — the mid and end captures on the SAME
		 *    POST must not read as two offers, which is the sharpest case the
		 *    consistency rule has.
		 *
		 * ⚠ CONSEQUENCE, STATED RATHER THAN HIDDEN: mid and end now carry the
		 *   SAME headline on one page. That is intentional (one offer, two entry
		 *   points), and it is a real editorial trade-off against variety.
		 *   REPORTED to the Chief of Staff, not absorbed. The eyebrow that used
		 *   to differentiate them ("Free printable") is dropped rather than
		 *   rewritten: it named the format, not the offer.
		 *
		 * ⛔ The contents half of the support sentence is the artefact-checked
		 *    wording, not a paraphrase. NO OUTCOME CLAIM. VOICE §9.1.
		 */
		?>
		<h2 class="bhp-post-capture__title" id="<?php echo esc_attr( $heading_id ); ?>">
			<?php esc_html_e( 'FREE Chapter for Reluctant Readers', 'brave-hearts' ); ?>
		</h2>
		<p class="bhp-post-capture__reason">
			<?php
			esc_html_e(
				"I'll send you the chapter now, just add your email. It arrives inside my free Reluctant Reader Adventure Kit, along with a printable activity and tips for reading it with a 6 to 9 year old.",
				'brave-hearts'
			);
			?>
		</p>
	</div>

	<?php
	get_template_part(
		'template-parts/acquisition/signup-form',
		null,
		array(
			'id'              => $panel_id . '-form',
			// ⛔ THE SAME CONTEXT AS THE END-OF-POST CAPTURE, DELIBERATELY.
			//    `bhp_blog_capture_context()` is the join key for the Mailchimp
			//    tag "Source: Blog Post". A new context string here would have
			//    minted a NEW tag in the live audience and split the blog's
			//    segment in two — a Mailchimp decision, and Andrew's, not an
			//    engineering one.
			'context'         => bhp_blog_capture_context(),
			'audience_type'   => 'parents_families',
			'lead_magnet'     => 'reluctant_reader_adventure_kit',
			// ⭐ 1.19.297 — was "Send me the Kit". One button string sitewide.
			'submit_label'    => __( 'Send me the chapter', 'brave-hearts' ),
			'email_label'     => __( 'Email address', 'brave-hearts' ),
			'submit_class'    => 'btn-cta-primary',
			'privacy_text'    => __( 'Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts' ),
			'aria_labelledby' => $heading_id,
			'class'           => 'bhp-post-capture__form',
		)
	);
	?>
</aside>
