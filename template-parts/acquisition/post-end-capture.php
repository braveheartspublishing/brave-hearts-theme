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
		<p class="bhp-post-capture__eyebrow"><?php esc_html_e( 'Before you go', 'brave-hearts' ); ?></p>
		<h2 class="bhp-post-capture__title" id="<?php echo esc_attr( $heading_id ); ?>">
			<?php esc_html_e( 'Try a chapter tonight', 'brave-hearts' ); ?>
		</h2>
		<p class="bhp-post-capture__reason">
			<?php
			/*
			 * ⛔ THE CONTENTS SENTENCE IS THE WORDING ALREADY LIVE SITEWIDE in
			 *    `footer-capture.php` and `exit-intent-popup.php`. It is quoted
			 *    rather than rewritten so this placement cannot describe the Kit
			 *    differently from the three placements that already describe it.
			 *
			 * ⛔ NO DELIVERY-TIMING PROMISE. An earlier draft of this line read
			 *    "and it is on its way" -- removed, because whether a Mailchimp
			 *    automation delivers the Kit immediately is UNVERIFIED by this
			 *    session (`functions.php`'s own lead-magnet registry still marks
			 *    assets "placeholder", and reading the account is `connected-operator`'s). The
			 *    copy therefore claims only what the Kit CONTAINS, which is
			 *    already stated on three live surfaces.
			 */
			esc_html_e(
				'I send the Reluctant Reader Adventure Kit: a sample chapter, a printable activity, and tips for reading it with a 6 to 9 year old.',
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
			'submit_label'    => __( 'Send me the Kit', 'brave-hearts' ),
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
