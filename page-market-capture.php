<?php
/**
 * Template Name: Market Capture Page
 * Description: The destination of a printed QR code Andrew hands to people who
 * buy a book from him in person at a market, fair or event. One page serves
 * EVERY event, forever — it is event-agnostic by design and never goes stale.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHY THIS PAGE EXISTS — THE LARGEST CAPTURE HOLE IN THE COMPANY'S RECORD
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER-ATTESTED, and it is the whole brief in one line: **73 books sold
 *    at one market weekend, zero emails captured.** `ads-knowledge` C-4 puts
 *    it plainly — *"where we have the most traffic includes traffic that never
 *    touches the website"*. Those 73 people are the highest-intent audience
 *    this business has ever met: they had the book in their hands and paid for
 *    it. The website popup could be perfect and would still never see them.
 *
 * ⭐ SO THE ASK IS DELIBERATELY SMALL. Somebody is standing at a stall holding
 *    a book and a phone. One field, one button, no scrolling, no second step.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS PAGE DELIBERATELY IS NOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ NO VISIT-MODE FLAG. It never reads or writes `bhp_visit`, and it is not
 *    the read-aloud school-visit surface. The two are separate audiences with
 *    separate offers, and conflating them would put a school-visit cart state
 *    in front of a market customer. FD-642's hand-delivery rules are not
 *    engaged here because no cart is engaged here.
 * ⛔ NO WOOCOMMERCE ANYTHING. No cart, no product query, no price literal, no
 *    add-to-cart, no coupon. The transaction already happened, in cash, at a
 *    table. This page's only job is the email address.
 * ⛔ NOT IN THE NAV, AND IT MUST STAY OUT. It is reached by QR only. Adding it
 *    to the menu would put an event-specific page in front of web visitors who
 *    have no idea what it refers to.
 * ⛔ NO REVIEW, RATING, TESTIMONIAL, REACTION, RESULT, STATISTIC OR AWARD.
 * ⛔ NO OUTCOME CLAIM. The copy says what the Kit CONTAINS, never what it will
 *    do to a child.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE FUNNEL — EXISTING PARENT FUNNEL, ONE NEW CONTEXT. NOTHING NEW BUILT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Same `signup-form.php`, same `bhp_mailchimp_signup` endpoint, same nonce,
 *    same honeypot, same consent gate, same Reluctant Reader Adventure Kit.
 *    ONLY the `context` is new (`market_capture`), which is exactly the shape
 *    `inc/read-aloud-landing.php` established for the read-aloud QR page.
 *
 * ⚠ IT DOES MINT ONE NEW TAG STRING — "Source: Market Event" — in a live
 *   Mailchimp audience the first time somebody signs up. ⭐ That is the POINT:
 *   without it, an in-person signup is indistinguishable from a website
 *   signup and the 73-books question stays permanently unanswerable. The
 *   precedent is `Source: Read-Aloud Visit` and `Source: Blog Post`, both
 *   minted the same way for the same reason. ⛔ FLAGGED to Andrew in this
 *   build's report rather than assumed, because what lands in his audience is
 *   his call. The filter lives in `functions.php` (it must be registered
 *   globally — the tag is applied during the POST to `admin-post.php`, when
 *   this template is not rendering).
 *
 * VOICE — standing rule §9.1: I/me/my, never "we". No em dash. Ages 6 to 9.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

get_header();

$bhp_market_cover = function_exists( 'bhp_get_lead_magnet_cover' )
	? bhp_get_lead_magnet_cover( 'reluctant_reader_adventure_kit' )
	: array();
?>

<main id="primary" class="site-main bhp-market">
	<section class="bhp-market__panel section" aria-labelledby="bhp-market-title">
		<div class="container container--content bhp-market__inner">

			<?php
			/*
			 * ⭐⭐ 1.19.297 (`CYCLE167-LD-CAPTURE-COPY-APPLY`) — THE OFFER NAME
			 *     LEADS, AND THE GREETING BECOMES THE EYEBROW.
			 *
			 * ⭐ THE SWAP IS THE WHOLE CHANGE, and it is deliberate rather than
			 *    a deletion: "Thanks for stopping by" is the RIGHT first thing
			 *    to say to somebody who just handed over cash at a stall, so it
			 *    is KEPT — moved up into the eyebrow, where it greets without
			 *    occupying the one line that has to carry the offer. The H1 now
			 *    carries the founder's picked headline (carrier item 290),
			 *    byte-identical to the popup's and to every other parent capture
			 *    surface. The eyebrow it replaces ("Free printable") named the
			 *    format, which the headline and support line now both cover.
			 *
			 * ⛔ `bhp-market-title` IS STILL THE H1's id AND IS STILL WHAT THE
			 *    FORM'S `aria_labelledby` POINTS AT. Moving the greeting into a
			 *    <p> that carried the id would have left the form labelled by a
			 *    pleasantry instead of by the offer.
			 * ⛔ `context` (`market_capture`) UNCHANGED — it is the join key for
			 *    the "Source: Market Event" tag.
			 */
			?>
			<p class="component-heading__eyebrow bhp-market__eyebrow">
				<?php esc_html_e( 'Thanks for stopping by', 'brave-hearts' ); ?>
			</p>

			<h1 id="bhp-market-title" class="bhp-market__title">
				<?php esc_html_e( 'FREE Chapter for Reluctant Readers', 'brave-hearts' ); ?>
			</h1>

			<?php
			/*
			 * ⛔ THE CONTENTS SENTENCE IS THE WORDING ALREADY LIVE SITEWIDE, in
			 *    `footer-capture.php`, `exit-intent-popup.php` and both blog
			 *    captures. Quoted rather than rewritten so this placement cannot
			 *    describe the Kit differently from the ones that already do.
			 * ⭐ AND IT WAS CHECKED AGAINST THE ARTEFACT THIS BUILD: all seven
			 *    pages of the live `Reluctant-Reader-Adventure-Kit-1.pdf` were
			 *    read from the production document root. It genuinely contains a
			 *    real chapter, a printable explorer activity and a note to the
			 *    parent. The sentence is true of the real file.
			 */
			?>
			<p class="bhp-market__reason">
				<?php
				/*
				 * ⭐ 1.19.297 — the support line + chapter -> Kit bridge, the
				 *    same sentence every other parent capture surface carries.
				 *    ⭐ THE "now" PROMISE IS PARTICULARLY LOAD-BEARING HERE: this
				 *    is the one surface where the visitor is standing in front of
				 *    Andrew and can say the email did not arrive. Items 292/293/
				 *    294 record HIS OWN read of the Active "Parent - Acquisition
				 *    Funnel" journey sending on the tag immediately, which is
				 *    what makes the word shippable at a market stall.
				 */
				esc_html_e(
					"I'll send you the chapter now, just add your email. It arrives inside my free Reluctant Reader Adventure Kit, along with a printable activity and tips for reading it with a 6 to 9 year old.",
					'brave-hearts'
				);
				?>
			</p>

			<?php if ( ! empty( $bhp_market_cover['url'] ) ) : ?>
				<picture class="bhp-market__cover">
					<source srcset="<?php echo esc_url( $bhp_market_cover['url'] ); ?>" type="image/webp">
					<img
						src="<?php echo esc_url( $bhp_market_cover['fallback'] ); ?>"
						width="<?php echo (int) $bhp_market_cover['width']; ?>"
						height="<?php echo (int) $bhp_market_cover['height']; ?>"
						alt="<?php echo esc_attr( $bhp_market_cover['alt'] ); ?>"
						decoding="async"
					>
				</picture>
			<?php endif; ?>

			<?php
			/*
			 * ⭐ ONE FIELD. web.dev's sign-up guidance is "ask for as little as
			 *    possible" and the corpus default is email-only. `require_name`
			 *    and `show_name` are both omitted, so no name field renders.
			 *    ⛔ This is the one surface where the second field is least
			 *    defensible: a person standing at a stall, one-handed, on
			 *    someone else's wifi.
			 * ⛔ NO `success_redirect_key`, matching the blog captures: the form
			 *    posts and returns HERE with `?bhp_signup=success`, so the
			 *    confirmation is on the page they are already looking at rather
			 *    than a navigation they have to wait for on event wifi.
			 */
			get_template_part(
				'template-parts/acquisition/signup-form',
				null,
				array(
					'id'              => 'bhp-market-capture-form',
					'context'         => 'market_capture',
					'audience_type'   => 'parents_families',
					'lead_magnet'     => 'reluctant_reader_adventure_kit',
					// ⭐ 1.19.297 — was "Send me the Kit". One button string
					//    sitewide; FREE never on the button.
					'submit_label'    => __( 'Send me the chapter', 'brave-hearts' ),
					'email_label'     => __( 'Email address', 'brave-hearts' ),
					'submit_class'    => 'btn-cta-primary',
					'privacy_text'    => __( 'Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts' ),
					'aria_labelledby' => 'bhp-market-title',
					'class'           => 'bhp-market__form',
				)
			);
			?>

			<p class="bhp-market__brand"><?php esc_html_e( 'Big Places. Brave Hearts.', 'brave-hearts' ); ?></p>

		</div>
	</section>
</main>

<?php get_footer(); ?>
