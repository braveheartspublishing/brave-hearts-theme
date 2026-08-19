<?php
/**
 * Brave Hearts — the drawn "field rule".
 *
 * `CYCLE165-LD-DIRECTION1-STEP4-HOME` (2026-08-19, theme 1.19.263). Direction 1,
 * "Expedition field notes", board build step 4 of 4 — the homepage.
 *
 * WHAT THIS IS
 * ------------
 * Board sheet 3, marker ③: *"A drawn field rule. Dive mask, ice axe, river —
 * one per book, from the existing line-art set."* Board sheet 4, homepage panel:
 * *"A drawn field rule sits between headline and CTA."*
 *
 * Source of the three marks:
 * `Business OS\WORKING-DRAFTS\design-creative\playful-trust-direction-2026-08-19\assets\`
 * (`icon-divemask.svg`, `icon-iceaxe.svg`, `icon-river.svg`), which are in turn
 * the hand-authored SVG set built for the activity book at
 * `WORKING-DRAFTS\design-creative\activity-book\line-art\`. Pointer only — the
 * private record is not reproduced here (Standing Rules §4.1).
 *
 * ⛔ NOTHING IS GENERATED, TRACED FROM A MODEL, OR REDRAWN. Every `d`
 *    attribute in `assets/img/field-marks.svg` is copied character-for-character
 *    out of those three files, with whitespace inside a `d` normalised to
 *    single spaces (which SVG treats identically). NOT ONE COORDINATE IS
 *    ALTERED. The three marks are composed into ONE file at x = 0 / 290 / 580
 *    of a 780 × 200 viewBox; the composition is a `transform="translate()"`,
 *    not a re-draw.
 *
 * ⭐ THREE PRESENTATION ATTRIBUTES DIFFER FROM THE SOURCES, ALL THREE LISTED
 *    HERE RATHER THAN LEFT FOR A READER TO FIND BY DIFF:
 *
 *    1. `stroke="#071522"` → `stroke="#D9A45F"`. Navy ink on the NAVY hero is
 *       invisible. `#D9A45F` is not a new hue: it is the exact literal value of
 *       `--expedition-gold` in `style.css`. `bhp_field_rule_ink()` below and
 *       `tests/test-direction1-home.php` §3 exist to stop the two drifting —
 *       the same guard, for the same reason, as `bhp_blog_plate_ink()`.
 *    2. `stroke-width="5.5"` → `"12"`. The sources are authored to render at
 *       their native 200 px. At the 20 px these render at, 5.5 resolves to a
 *       0.55 px hairline that disappears on navy at 1× and shimmers at 2×; 12
 *       resolves to 1.2 px. The GEOMETRY is untouched — only the pen width.
 *    3. The river's leaf drops `fill="#071522" stroke="none"` and its veins
 *       drop `stroke="#ffffff" stroke-width="4.5"`. A solid navy leaf with
 *       white veins is a two-colour object that cannot carry one ink, and at
 *       20 px it reads as a blob rather than a drawing. It is stroked instead,
 *       like its two siblings. This is the only mark whose APPEARANCE differs
 *       from its source, and it differs in the direction of the other two.
 *
 * ⛔ WHY A BACKGROUND-IMAGE FILE RATHER THAN INLINE SVG, AND IT IS A SECURITY
 *    ANSWER, NOT A TASTE ONE. `template-parts/components/hero.php` passes
 *    `after_title` through `wp_kses_post()`, whose 'post' allowlist contains
 *    NO `svg` and NO `path` element — verified against this install, not
 *    assumed. Inline marks would be silently stripped on the way to the
 *    browser, exactly as `srcset` was in CYCLE144-LD-205. The two ways to fix
 *    that are (a) widen the SITEWIDE kses allowlist to admit SVG, or (b) put
 *    the drawing in a file and let the markup be divs and spans. (a) widens a
 *    security filter — SVG can carry `<script>`, event handlers and `<use>` —
 *    for the sake of a DECORATION, and it would apply to every
 *    `wp_kses_post()` call in the theme, not just this one. (b) is what the
 *    blog plate already does at 1.19.261 and what this does. The cost is a
 *    baked ink literal, and that cost is paid by the ink test below.
 *
 * ⛔ DECORATIVE, AND DECLARED SO. `aria-hidden="true"`, no text node, no link,
 *    no `data-bhp-event`. It adds no information a screen reader loses, and —
 *    load-bearing — it therefore introduces NO NEW CUSTOMER-FACING COPY. The
 *    board's 1440 mock sets the words "three real places" beside this rule;
 *    those words are UNAPPROVED COPY and are deliberately NOT shipped.
 *
 * ⛔ IT IS ONE MARK, NOT THREE. The brief's constraint is "one mark per
 *    screen". The three icons are one composed rule — one asset, one element —
 *    which is the same single object the board draws. Nothing else drawn is
 *    added to the hero screen by this step.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ink baked into `assets/img/field-marks.svg`.
 *
 * ⛔ THIS FUNCTION EXISTS TO BE TESTED, NOT TO BE CALLED. Nothing renders from
 *    it. `tests/test-direction1-home.php` asserts that this value, the literal
 *    inside the shipped SVG, and the `--expedition-gold` token in `style.css`
 *    are all the same string. A background-image cannot inherit `currentColor`,
 *    so the only way a baked literal stays honest is a guard that fails loudly
 *    when someone re-tones the palette and forgets the asset. Same mechanism,
 *    same reason, as `bhp_blog_plate_ink()` at 1.19.261.
 *
 * @return string Lower-cased hex, matching how the token is compared.
 */
function bhp_field_rule_ink() {
	return '#d9a45f';
}

/**
 * The three adventures the marks stand for, in series order.
 *
 * Series order, not alphabetical, and matching the hero's own cover fan:
 * Mariana, Everest, The Amazon. It is not a bug and should not be "fixed".
 *
 * ⛔ NOT RENDERED. There is no visible text on the rule and none is wanted —
 *    see the no-new-copy note in the file header. This list is the record of
 *    what the three marks ARE, so that a future pass reordering the covers
 *    knows the asset has an order too.
 *
 * @return array<int, string>
 */
function bhp_field_rule_marks() {
	return array( 'mariana', 'everest', 'amazon' );
}

/**
 * The field rule: hairline, the drawn marks, hairline.
 *
 * ⛔ FAIL CLOSED. If the asset is missing from the installed theme the whole
 *    rule renders NOTHING, rather than two hairlines around an empty gap. A
 *    decorative element has no business degrading into a visible defect, and
 *    this build ships to an environment where a partial theme install is a
 *    documented failure mode.
 *
 * ⛔ IT IS A `<div>`, NOT AN `<hr>`. An `<hr>` is a semantic thematic break and
 *    would be announced. This is furniture between a headline and its button.
 *
 * ⛔ EVERY ELEMENT AND ATTRIBUTE BELOW SURVIVES `wp_kses_post()` — `div`,
 *    `span`, `class`, `aria-hidden` and `data-*` are all in the 'post'
 *    allowlist. That is a requirement of the slot this renders into, not a
 *    coincidence; see the file header.
 *
 * @param string $context Written to `data-bhp-field-rule`, so the QA probe and
 *                        the test can find it without matching a class name.
 * @return string
 */
function bhp_field_rule( $context = 'home-hero' ) {
	if ( ! file_exists( get_template_directory() . '/assets/img/field-marks.svg' ) ) {
		return '';
	}

	return '<div class="bhp-field-rule" data-bhp-field-rule="' . esc_attr( $context ) . '" aria-hidden="true">'
		. '<span class="bhp-field-rule__line"></span>'
		. '<span class="bhp-field-rule__marks"></span>'
		. '<span class="bhp-field-rule__line"></span>'
		. '</div>';
}
