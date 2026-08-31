<?php
/**
 * THE SLIM INLINE CAPTURE BAND — theme 1.19.322, 2026-08-29,
 * `CYCLE169-LD-BLOG-LAYOUT-R2` (built 1.19.321, `…-TEMPLATE`).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT IT IS, IN ONE SENTENCE
 * ═══════════════════════════════════════════════════════════════════════════
 * One line, one email field, one button, on a single row, injected into every
 * blog post immediately after its SECOND TOP-LEVEL PARAGRAPH. It is the
 * standard pattern, not a per-post placement: `inc/blog-post-template.php`
 * decides WHERE, this file decides WHAT.
 *
 * ⭐ 1.19.322 MOVED IT MUCH HIGHER — it was "after the second main content
 *    section" (an `<h2>` count) and is now "after the second paragraph". The
 *    reasoning, the superseded rule and the fallback all live at
 *    `bhp_blog_capture_band_offset()`; they are not restated here.
 *
 * ⛔ IT IS NOT A SECOND SIGNUP PATH, AND THAT WAS THE BRIEF'S EXPLICIT
 *    CONSTRAINT. It renders `template-parts/acquisition/signup-form.php`
 *    unchanged, with the SAME `lead_magnet` and the SAME `context` as the
 *    end-of-post capture — so it posts to the same handler, the same nonce, the
 *    same MC4WP audience and the same Mailchimp tag set that
 *    `footer-capture.php` and `post-end-capture.php` already use. Nothing new
 *    is invented anywhere in the pipe.
 *
 * ⭐ THE CONTEXT IS `bhp_blog_capture_context()` DELIBERATELY, and this is the
 *    same reasoning `post-mid-capture.php` records: a NEW context string would
 *    mint a NEW tag in the live Mailchimp audience and split the blog's segment
 *    in two. That is a Mailchimp decision and Andrew's, not an engineering one.
 *    The placement is still separable in the dataLayer, because the injector
 *    stamps `data-bhp-band-placement` on the wrapper.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.322 — THE COPY IS NO LONGER DRAFT. ROUND 1'S FINDING B1 IS CLOSED.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE CONFLICT THIS FILE RECORDED IS NOW SETTLED, AND IT WAS SETTLED BY THE
 *    FOUNDER, NOT BY THIS DESK. 1.19.321 shipped the round-1 brief's draft
 *    wording, *"Want a free chapter to test on your kiddo?"*, and flagged it in
 *    the same sitting as a THIRTEENTH wording of one offer — `CYCLE167` having
 *    found twelve of twelve capture surfaces naming it twelve ways before the
 *    founder picked ONE name at carrier item 290. The round-2 brief carries his
 *    ruling: use the standardized item-290 strings.
 *
 * ⭐ BOTH STRINGS ARE NOW THE SITEWIDE ONES, BYTE-IDENTICAL to the popup, the
 *    footer capture, the mid capture, the end-of-post capture and the two
 *    landing pages:
 *
 *      · LINE   — "FREE Chapter for Reluctant Readers"
 *      · BUTTON — "Send me the chapter"   (unchanged; it was already sitewide)
 *
 *    Both still resolve through `bhp_blog_capture_band_line()` and
 *    `bhp_blog_capture_band_button()` rather than being typed here, because the
 *    round-2 brief keeps the copy in "the single filterable spot — the founder
 *    may still swap the phrase in-thread".
 *
 * ⚠ ONE FINDING RAISED RATHER THAN QUIETLY FIXED, AND IT IS THE HONEST HALF OF
 *   THIS CHANGE. `tests/test-cycle167-capture-copy.php` is the suite that
 *   enforces the one-offer rule, and its `$parent_surfaces` list does NOT
 *   include this file — its own docblock names that as its blind spot ("a
 *   thirteenth parent capture surface added later is not covered until somebody
 *   adds it here"). Adding this row would be editing ANOTHER suite's
 *   assertions, which the round-2 brief explicitly forbids. So the strings are
 *   asserted in THIS build's own suite instead, and the missing row is reported
 *   to the Chief of Staff as a follow-up rather than absorbed.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE MARKUP CONSTRAINT THAT IS LOAD-BEARING: NO `<h2>` IN THIS FILE
 * ═══════════════════════════════════════════════════════════════════════════
 * The book rail's anchor arithmetic counts `<h2>` tokens in the rendered
 * article. This band is injected BEFORE the rail runs (priority 11 against the
 * rail's 12), so any heading it emitted would be counted as one of the
 * article's own headings and would move a commerce rail that nobody asked to
 * move. The band's accessible name therefore comes from a `<span>` tied to the
 * wrapper by `aria-labelledby` — a real accessible name, and zero heading
 * tokens. `tests/test-cycle169-blog-layout.php` §2 asserts it.
 *
 * VOICE: standing rule §9.1 — I/me/my, never "we" in customer-facing words. No
 * em dash. Ages 6 to 9, never 5 to 9. ⛔ NO OUTCOME CLAIM: the copy offers the
 * chapter and never says what it will do to a child.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

$band_id = wp_unique_id( 'bhp-capture-band-' );
$line_id = $band_id . '-line';
?>
<aside id="<?php echo esc_attr( $band_id ); ?>" class="bhp-capture-band" aria-labelledby="<?php echo esc_attr( $line_id ); ?>" data-bhp-band-placement="blog_post_paragraph2">
	<span class="bhp-capture-band__line" id="<?php echo esc_attr( $line_id ); ?>"><?php echo esc_html( bhp_blog_capture_band_line() ); ?></span>

	<?php
	get_template_part(
		'template-parts/acquisition/signup-form',
		null,
		array(
			'id'              => $band_id . '-form',
			// ⛔ THE SAME CONTEXT AND THE SAME MAGNET AS EVERY OTHER BLOG
			//    CAPTURE. See the docblock: a new context mints a new live tag.
			'context'         => bhp_blog_capture_context(),
			'audience_type'   => 'parents_families',
			'lead_magnet'     => 'reluctant_reader_adventure_kit',
			// ⭐ The sitewide control string, byte-identical to the popup, the
			//    footer capture, the mid capture and the end-of-post capture.
			//    Unchanged at 1.19.322 — it was already the item-290 string; it
			//    is the LINE above it that moved onto the standard this release.
			'submit_label'    => bhp_blog_capture_band_button(),
			'email_label'     => __( 'Email address', 'brave-hearts' ),
			'submit_class'    => 'btn-cta-primary',
			'privacy_text'    => __( 'Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts' ),
			'aria_labelledby' => $line_id,
			'class'           => 'acquisition-form--band',
			// ⛔ No 'success_redirect_key', matching `post-end-capture.php` and
			//    `post-mid-capture.php` exactly, so this posts and returns to
			//    the same post and `signup-form.php` fires `lead_signup_success`
			//    inline, consent-gated. `CYCLE165-BOR-101`'s proposal to
			//    redirect inline captures is ANDREW'S DECISION and is not
			//    pre-empted here.
		)
	);
	?>
</aside>
