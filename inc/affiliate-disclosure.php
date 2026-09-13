<?php
/**
 * Brave Hearts Publishing — the per-post Amazon affiliate disclosure.
 *
 * 1.19.418 (2026-09-13, `CYCLE180-LD-BUILD-418`). Founder ruling, seal 1550.
 *
 * ⚠ SOURCE AND EVIDENCE CLASS: the ruling reached the session that wrote this
 *   file RELAYED through the Chief of Staff. It was NOT witnessed first-hand,
 *   and it is not described here as first-hand (Standing Rules §9.2 rule 2).
 *   The verbatim founder record lives in the private carrier, not in this
 *   public repository (§4.1: point at the private source, never copy it in).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE ONE SENTENCE THIS FILE EXISTS FOR — APPROVED VERBATIM, NOT DRAFTED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *     "Some links on this page go to Amazon and earn me a small commission at
 *      no cost to you."
 *
 * ⛔ THE STRING IS APPROVED COPY AND IS LOCKED. Standing Rules §9: approved
 *    copy is never silently rewritten — propose a change, do not make one.
 *    It is emitted through exactly one function, `bhp_affiliate_disclosure_text()`,
 *    so there is one place to read it and one place a future change would have
 *    to be approved into. ⭐ Its three standing rails were checked against the
 *    finished string rather than against the intention (§9.4 clause 3):
 *      · no "we", "us" or "our" — it says "me", which is §9.1's voice
 *      · no em dash anywhere in it
 *      · American spelling throughout
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ WHY THIS IS A THEME FEATURE AND NOT A PARAGRAPH PASTED INTO EACH POST
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The ruling says AUTO-DETECTED, NO PER-POST FLAG. That is the important half:
 * a disclosure that depends on an editor remembering to set a checkbox is a
 * disclosure that is missing on the post where it matters. Detection reads the
 * post's own content, so the line appears the moment an affiliate link is
 * added and disappears the moment the last one is removed, with no second
 * record to keep in sync.
 *
 * ⛔ THE SITEWIDE FOOTER DISCLOSURE STAYS AND IS BYTE-UNTOUCHED.
 *    `bhp_get_amazon_disclosure_text()` in `functions.php` ("As an Amazon
 *    Associate, Brave Hearts Publishing earns from qualifying purchases.") is a
 *    DIFFERENT statement serving a DIFFERENT purpose: it is the program's
 *    required sitewide attribution. This one is the FTC-shaped, page-level
 *    disclosure that sits where a reader meets the links. Neither replaces the
 *    other, and the ruling says the footer stays.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE DETECTION PATTERN IS BORROWED, NOT INVENTED — AND THAT IS DELIBERATE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `BHP_CTA_Collision_Detector::AMAZON_AFFILIATE_URL_PATTERN` already encodes
 * this company's definition of "an Amazon link that earns" —
 * `/amazon\.com\/.*\btag=/i` — and the required-links gate already judges posts
 * by it. ⛔ Writing a second regex here would create two definitions of the
 * same fact, which is the defect class the naming-trap register exists for. So
 * this file reuses the constant and falls back to the identical literal only if
 * the class is absent (a partial deploy), which is recorded rather than hidden.
 *
 * ⛔⛔ BUT THE PATTERN IS APPLIED TO ONE URL AT A TIME, NEVER TO THE WHOLE
 *     DOCUMENT, AND THIS IS THE BUG THAT WAS AVOIDED RATHER THAN FIXED LATER.
 *     `.*` is greedy and matches any character except a newline. Run against a
 *     whole post body it will happily bridge from the word "amazon.com" in one
 *     sentence to a `tag=` in an unrelated URL later on the SAME LINE — and
 *     WordPress content is frequently one very long line. That would report a
 *     post as carrying affiliate links when it carries none, and print a
 *     commission disclosure on a page that earns no commission. ⭐ A FALSE
 *     DISCLOSURE IS NOT A HARMLESS EXTRA LINE: it is an untrue statement about
 *     how this business makes money, which is the never-invent rule's subject
 *     matter (§3), not a display bug. The anchors are therefore extracted
 *     first and each `href` is tested on its own, exactly as the collision
 *     detector does at its own call site.
 *
 * ⚠ SCOPE, STATED RATHER THAN GLOSSED: detection reads `post_content` as
 *   stored. It does NOT render the post through `the_content` filters first.
 *   That is a deliberate trade — rendering every post to decide whether to
 *   print one line would run the whole filter chain twice — and it is correct
 *   for every affiliate anchor on this site today, because every one of them is
 *   authored into the body. ⛔ If a future component ever INJECTS an Amazon
 *   affiliate link into a post at render time, this detector will not see it
 *   and the disclosure will be missing. `bhp_post_has_affiliate_links` is the
 *   filter that would fix that, and this paragraph is here so the next reader
 *   does not have to rediscover the limit.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The approved disclosure sentence. APPROVED COPY — see the file docblock.
 *
 * @return string
 */
function bhp_affiliate_disclosure_text() {
	return __( 'Some links on this page go to Amazon and earn me a small commission at no cost to you.', 'brave-hearts' );
}

/**
 * Every `href` value in a block of HTML, single- and double-quoted alike.
 *
 * ⭐ Anchors are extracted rather than pattern-matching the raw document. See
 *    the file docblock for the greedy-`.*` trap this avoids.
 *
 * @param string $html Content HTML.
 * @return string[] Raw href values, unescaped.
 */
function bhp_affiliate_extract_hrefs( $html ) {
	$html = (string) $html;
	if ( '' === $html || false === stripos( $html, 'href' ) ) {
		return array();
	}
	if ( ! preg_match_all( '/<a\b[^>]*\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$urls = array();
	foreach ( $m as $hit ) {
		$url = ( isset( $hit[1] ) && '' !== $hit[1] ) ? $hit[1] : ( $hit[2] ?? '' );
		if ( '' !== $url ) {
			$urls[] = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
		}
	}
	return $urls;
}

/**
 * The company's definition of "an Amazon link that earns", in one place.
 *
 * @return string A PCRE pattern.
 */
function bhp_affiliate_url_pattern() {
	if ( class_exists( 'BHP_CTA_Collision_Detector' )
		&& defined( 'BHP_CTA_Collision_Detector::AMAZON_AFFILIATE_URL_PATTERN' ) ) {
		return BHP_CTA_Collision_Detector::AMAZON_AFFILIATE_URL_PATTERN;
	}
	/*
	 * Partial-deploy fallback only. It is the SAME literal the class carries;
	 * if the two ever diverge, the class is right and this line is the bug.
	 */
	return '/amazon\.com\/.*\btag=/i';
}

/**
 * Does this post carry at least one earning Amazon link in its own body?
 *
 * Memoized per post id: `single.php` asks once, the test suite asks repeatedly,
 * and the answer cannot change inside one request.
 *
 * @param WP_Post|int|null $post Post.
 * @return bool
 */
function bhp_post_has_affiliate_links( $post = null ) {
	static $cache = array();

	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$id = (int) $post->ID;
	if ( ! isset( $cache[ $id ] ) ) {
		$pattern = bhp_affiliate_url_pattern();
		$found   = false;
		foreach ( bhp_affiliate_extract_hrefs( $post->post_content ) as $url ) {
			if ( preg_match( $pattern, $url ) ) {
				$found = true;
				break;
			}
		}
		$cache[ $id ] = $found;
	}

	/*
	 * ⚠ The filter is NOT memoized with the detection, deliberately: a
	 *   subscriber that depends on request state (a preview, a test fixture)
	 *   must be able to answer differently on a later call within the request.
	 */
	return (bool) apply_filters( 'bhp_post_has_affiliate_links', $cache[ $id ], $post );
}

/**
 * Should the per-post disclosure render on this post?
 *
 * ⛔ SINGULAR POSTS ONLY. An archive or a search result shows excerpts of many
 *    posts; "links on this page" would then be a claim about a page whose links
 *    this function did not examine. A statement about how the business earns is
 *    printed where it is true, or not at all.
 *
 * @param WP_Post|int|null $post Post.
 * @return bool
 */
function bhp_affiliate_disclosure_enabled( $post = null ) {
	$post    = get_post( $post );
	$enabled = (bool) $post
		&& 'post' === $post->post_type
		&& bhp_post_has_affiliate_links( $post );

	return (bool) apply_filters( 'bhp_affiliate_disclosure_enabled', $enabled, $post );
}

/**
 * The disclosure line's markup, or '' when the post carries no affiliate link.
 *
 * ⭐ `role="note"` rather than a bare paragraph: it is an aside about the page,
 *    not part of the article's argument, and a screen reader should be able to
 *    tell the difference. ⛔ It is NOT `aria-hidden` and NOT visually hidden —
 *    a disclosure that assistive technology skips is not a disclosure.
 *
 * @param WP_Post|int|null $post Post.
 * @return string HTML, or ''.
 */
function bhp_affiliate_disclosure_html( $post = null ) {
	$post = get_post( $post );
	if ( ! bhp_affiliate_disclosure_enabled( $post ) ) {
		return '';
	}

	/*
	 * ⛔ `.text-caption` IS DELIBERATELY NOT USED, AND THE REASON IS MEASURED,
	 *    not stylistic. That utility carries `text-transform: uppercase`
	 *    (style.css §typography), which is correct for a four-word date-and-
	 *    author meta line and wrong for a 15-word sentence: a disclosure set in
	 *    all caps is materially harder to read, and an FTC-shaped disclosure
	 *    that is harder to read is a worse disclosure. The class here is its
	 *    own hook with its own rule, sentence case, muted but not faint.
	 */
	return sprintf(
		'<p class="bhp-affiliate-disclosure" role="note">%s</p>',
		esc_html( bhp_affiliate_disclosure_text() )
	);
}
