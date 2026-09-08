<?php
/**
 * The end-of-post lead capture — the honest home for the word "free".
 *
 * ⭐ IT REUSES `signup-form.php` UNCHANGED, AND THAT IS THE WHOLE DESIGN.
 *    The Kit funnel's POST handler, nonce, honeypot, MC4WP audience check,
 *    consent gate and `lead_signup_success` dataLayer push all live in that one
 *    template. A bespoke form here would have had to reimplement five of those
 *    and would have drifted from them by the next release.
 *
 * ⛔ NO `success_redirect_key` IS PASSED, DELIBERATELY. That is what makes the
 *    form post and return to THIS post with `?bhp_signup=success`, at which
 *    point `signup-form.php` lines 246-291 fire `lead_signup_success` inline,
 *    gated on `BHP_Analytics_Config::should_render_analytics()` so a visitor who
 *    has not consented fires nothing.
 *
 *    `CYCLE165-BOR-101` observes that inline captures therefore never reach
 *    `/adventure-kit-thank-you/` and never see its offer, and proposes changing
 *    that. ⛔ THAT IS ANDREW'S DECISION. This file deliberately preserves the
 *    1.19.260 behaviour and only ensures the event fires.
 *
 * ⛔ IT IS NOT A POPUP. Item 61(4) leaves the popup homepage-only plus blog,
 *    untouched. This is a block in the document flow: it obstructs nothing, so
 *    Google's intrusive-interstitial rule does not engage, and NN/g's objection
 *    to modal mailing-list asks does not apply to it.
 *
 * ONE FIELD. web.dev's sign-up guidance is "ask for as little as possible"; the
 * brief's ceiling is two. Email alone is one, and the Kit needs nothing else to
 * arrive.
 *
 * VOICE: standing rule §9.1 — Andrew is the sole operator, so the reason-why is
 * "I", never "we". No em dash. Ages 6-9, never 5-9. No outcome claim: the copy
 * says what the Kit CONTAINS, never what it will do to a child.
 *
 * ⚠ EVERY LINE BELOW IS COPY AND IS ROUTED TO ANDREW FOR RATIFICATION. It is
 *   written to the rails rather than invented from a persona, and it describes
 *   only things that exist: the Kit's own contents are quoted from the wording
 *   already live sitewide in `footer-capture.php` and `parent-popup.php`.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$panel_id   = wp_unique_id( 'bhp-post-capture-' );
$heading_id = $panel_id . '-title';
?>
<aside id="<?php echo esc_attr( $panel_id ); ?>" class="bhp-post-capture" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
	<div class="bhp-post-capture__intro">
		<?php
		/*
		 * ═══════════════════════════════════════════════════════════════════
		 * ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — ONE
		 *     OFFER NAME, AND THIS SURFACE NOW USES IT.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * ⭐ THE FINDING (`CYCLE167-MKT-MAGNET-TEARDOWN`): TWELVE OF
		 *    TWELVE capture surfaces described the same offer differently. This
		 *    one said "Try a chapter tonight" and offered "the Kit"; the footer
		 *    said "Start with one free chapter."; exit-intent said "take the
		 *    free kit"; the quiz said "Send My Free Adventure Kit". A visitor
		 *    who met two of them met two offers. The founder agreed in terms:
		 *    *"we need to be consistent on the email capture across the entire
		 *    website"*.
		 *
		 * ⭐ SO THE HEADLINE AND THE BUTTON ARE NOW HIS STRINGS (carrier item
		 *    290), BYTE-IDENTICAL TO THE POPUP'S. ⛔ THE EYEBROW IS DROPPED
		 *    RATHER THAN REWRITTEN: "Before you go" described the PLACEMENT, and
		 *    a second line above a headline that already names the offer is the
		 *    padding the teardown objected to.
		 *
		 * ⭐ THE SUPPORT SENTENCE BRIDGES chapter -> Kit, AND THE BRIDGE IS THE
		 *    HONESTY CONDITION, NOT A FLOURISH. The offer is now the chapter;
		 *    the artefact that arrives is still the Kit. Item 290 condition (b)
		 *    requires the two to visibly match, so this sentence names both.
		 * ⛔ THE CONTENTS HALF IS NOT INVENTED AND NOT REWORDED FROM MEMORY: the
		 *    296 lane read all seven pages of the live
		 *    `Reluctant-Reader-Adventure-Kit-1.pdf` from the production document
		 *    root. It contains one real chapter (Chapter 7, "The Swordfish"), a
		 *    printable explorer activity, and tips to the parent.
		 * ⛔ NO OUTCOME CLAIM. The copy says what the Kit CONTAINS, never what it
		 *    will do to a child. VOICE §9.1: I/me/my, no em dash, ages 6 to 9.
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
			'context'         => bhp_blog_capture_context(),
			// `parents_families` is the registry key. `bhp_normalize_audience_type()`
			// silently falls back to `general_readers` for anything it does not
			// know, so a near-miss like "parents" would have mis-tagged every
			// blog lead without erroring. Verified against `bhp_get_audience_types()`.
			'audience_type'   => 'parents_families',
			'lead_magnet'     => 'reluctant_reader_adventure_kit',
			// ⭐ 1.19.297 — was "Send me the Kit". Send-imperative, matching the
			//    headline above it and every other parent capture button.
			//    ⛔ FREE never appears on a button (teardown pattern).
			'submit_label'    => __( 'Send me the chapter', 'brave-hearts' ),
			'email_label'     => __( 'Email address', 'brave-hearts' ),
			'submit_class'    => 'btn-cta-primary',
			// The wording already live on the footer capture and the parent
			// popup. Reused, not rewritten, so three placements cannot promise
			// three different things.
			'privacy_text'    => __( 'Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts' ),
			'aria_labelledby' => $heading_id,
			'class'           => 'bhp-post-capture__form',
			// ⛔ No 'success_redirect_key'. See the docblock.
		)
	);
	?>
</aside>
