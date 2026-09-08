<?php
/**
 * CYCLE179-LD-BUILD-404-KIT-PREVIEW-ONLY — theme 1.19.404.
 *
 * WHAT THIS SUITE IS FOR. 1.19.404 turns the instant kit modal into a
 * PREVIEW-ONLY panel on the founder's decision (seal 1357, RELAYED through the
 * Chief of Staff and NOT witnessed by this desk): the eleven page images on
 * every device, no PDF anywhere in the panel, one Close button, and copy that
 * is Andrew's own or approved by him verbatim.
 *
 * IT ASSERTS SIX THINGS, AND THEY ARE THE SIX THINGS THAT COULD SILENTLY
 * REGRESS:
 *
 *   1. THE FILE IS NOT HANDED OUT. The configured kit PDF URL appears nowhere
 *      in the rendered panel — no attribute, no href, no script literal. This
 *      runs against the URL the environment is REALLY configured with, never
 *      against a hardcoded string, so it cannot pass vacuously.
 *   2. THERE IS ONE READER AND NO BRANCH. No iframe, no feature probe, no QA
 *      view flag, no forced-view function.
 *   3. ELEVEN PAGES RENDER, ALWAYS AND UNCONDITIONALLY, with reserved boxes.
 *   4. THE COPY IS EXACT. Three founder strings, byte for byte, including the
 *      coupon code PARENT10.
 *   5. THERE IS ONE CLOSE BUTTON, plus the round close in the bar.
 *   6. THE BEHAVIOUR THE BRIEF SAID TO KEEP IS STILL THERE: single-use token,
 *      focus trap, Escape, backdrop close, body scroll lock, overscroll
 *      containment, and 1.19.403's vh-then-dvh panel sizing.
 *
 * WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS. This is a PHP
 * suite. It reads declarations and rendered markup; it lays nothing out. It
 * cannot prove an image painted, that a button is on screen, that `dvh`
 * resolved to anything, or that a real iPhone scrolled. Those are browser
 * claims and are recorded separately, with geometry measured at an asserted
 * `window.innerWidth`, in the build report.
 *
 * It touches no post, no product, no option and no WooCommerce record. The
 * only state it writes is its own short-lived transients, which it consumes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_k404_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_k404_src() {
	$path = get_template_directory() . '/inc/kit-instant-modal.php';
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * The file with EVERY comment removed — PHP comments via the tokenizer, then
 * the CSS and JS block comments that the tokenizer hands back as inline HTML.
 *
 * THIS IS NOT FASTIDIOUSNESS, IT IS THE ONLY WAY THE ABSENCE ASSERTIONS MEAN
 * ANYTHING. 1.19.404's docblock explains at length what it deliberately
 * removed, and to do that it NAMES the removed things in prose:
 * `pdfViewerEnabled`, `data-bhp-kit-pdf`, `bhp_kit_view`, `forcedView`.
 * `strpos()` cannot tell an explanation from a use, so an absence assertion
 * run against the raw source would fail on the very sentence that documents
 * the removal. Both sibling suites record the same reasoning.
 */
function bhp_k404_code( $src ) {
	if ( '' === $src ) {
		return $src;
	}

	$out = $src;

	if ( function_exists( 'token_get_all' ) ) {
		$out = '';
		foreach ( token_get_all( $src ) as $token ) {
			if ( is_array( $token ) ) {
				if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
					continue;
				}
				$out .= $token[1];
				continue;
			}
			$out .= $token;
		}
	}

	$stripped = preg_replace( '#/\*.*?\*/#s', '', $out );

	return ( null === $stripped ) ? $out : $stripped;
}

/**
 * The RENDERED markup with its CSS and JS block comments removed.
 *
 * ⛔⛔ THIS EXISTS BECAUSE A REAL BUG WAS CAUGHT WITH IT, AND THE BUG WAS IN
 *     THIS SUITE. PHP comments never reach the browser, so it is easy to
 *     assume no comment does. The `<style>` and `<script>` blocks in this
 *     modal are INLINE HTML: their `/* ... *\/` comments are printed verbatim
 *     into the page. 1.19.404's script comment NAMES `navigator.pdfViewerEnabled`
 *     and `data-bhp-kit-pdf` in order to say they were removed, so an absence
 *     assertion run against the raw markup FAILS on the very sentence that
 *     documents the removal.
 *
 * ⭐ SO: absence checks for a token that also appears in prose run against
 *    this stripped copy. ⛔ THE PDF URL CHECK DELIBERATELY DOES NOT -- it runs
 *    against the RAW markup, because a URL appearing anywhere at all, comment
 *    included, is a leak.
 */
function bhp_k404_markup_code( $markup ) {
	$stripped = preg_replace( '#/\*.*?\*/#s', '', (string) $markup );

	return ( null === $stripped ) ? (string) $markup : $stripped;
}

/**
 * Mint a token and render the footer hook with it, restoring the request
 * superglobals afterwards.
 *
 * ⚠️ `bhp_kit_modal_consume()` MEMOISES ITS ANSWER IN A STATIC, so the panel
 *    can be rendered exactly ONCE per process. This suite therefore renders
 *    once, into `$markup`, and every markup assertion below reads that one
 *    string. ⛔ Do not add a second render: it will silently produce an empty
 *    string and every absence assertion will pass for the wrong reason.
 */
function bhp_k404_render_with_token( $email ) {
	$get_backup = $_GET;

	$token = bhp_kit_modal_mint( $email, BHP_KIT_MODAL_LEAD_MAGNET );
	$_GET  = array( BHP_KIT_MODAL_ARG => $token );

	ob_start();
	bhp_kit_modal_render();
	$out = (string) ob_get_clean();

	$_GET = $get_backup;

	return $out;
}

$src  = bhp_k404_src();
$code = bhp_k404_code( $src );

echo "\n== 0: the subject is loaded ==\n";

bhp_k404_assert( '' !== $src, '0: inc/kit-instant-modal.php is readable', $failures );
bhp_k404_assert( function_exists( 'bhp_kit_modal_render' ), '0: bhp_kit_modal_render() is defined', $failures );
bhp_k404_assert( function_exists( 'bhp_kit_modal_mint' ), '0: bhp_kit_modal_mint() is defined', $failures );
bhp_k404_assert( function_exists( 'bhp_kit_modal_sample_pages' ), '0: bhp_kit_modal_sample_pages() is defined', $failures );
bhp_k404_assert(
	false !== has_action( 'wp_footer', 'bhp_kit_modal_render' ),
	'0: the renderer is still hooked to wp_footer',
	$failures
);

/*
 * ⛔⛔ SECTION 1 IS THE ONE THAT MATTERS MOST, AND IT IS RUN AGAINST THE LIVE
 *     CONFIGURATION RATHER THAN A LITERAL.
 *
 * ⭐ Andrew, verbatim (seal 1357, RELAYED, NOT witnessed by this desk):
 *    *"maybe do the preview and dont let them download it or print it from
 *    there?"*
 *
 * ⭐ `bhp_get_reluctant_reader_download()` is the same readiness flag the kit
 *    page's own panel is gated on, and its `url` is the real file this site
 *    serves. Asserting the panel does not contain THAT string is a much
 *    stronger statement than asserting it does not contain some example URL,
 *    and it keeps working when the kit is re-issued at a new filename.
 *
 * ⚠️ IF NO KIT IS CONFIGURED the modal renders nothing at all by design, so
 *    the section SKIPS with a loud line rather than passing on an empty
 *    string. ⛔ A skip is not a pass and must not be reported as one.
 */
echo "\n== 1: the panel hands out no file ==\n";

$download = function_exists( 'bhp_get_reluctant_reader_download' ) ? bhp_get_reluctant_reader_download() : array( 'ready' => false );
$pdf_url  = isset( $download['url'] ) ? (string) $download['url'] : '';
$markup   = '';

if ( empty( $download['ready'] ) || '' === $pdf_url ) {
	echo "SKIP: no kit PDF is configured in this environment, so the panel does not render and sections 1 to 5 cannot run.\n";
} else {
	$markup = bhp_k404_render_with_token( 'qa.suite404@example.com' );
	$mcode  = bhp_k404_markup_code( $markup );

	bhp_k404_assert(
		false !== strpos( $markup, 'id="bhp-kit-modal"' ),
		'1: the panel rendered for a valid single-use token',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, $pdf_url ) && false === strpos( $markup, esc_url( $pdf_url ) ),
		'1: the CONFIGURED kit PDF URL appears nowhere in the rendered panel, raw or escaped',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $mcode, 'data-bhp-kit-pdf' ),
		'1: there is no data-bhp-kit-pdf attribute to read the file out of',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $mcode, '.pdf' ),
		'1: no .pdf reference of any kind is printed in the panel',
		$failures
	);
	/*
	 * ⚠⚠ THIS ASSERTION WAS WRITTEN WRONG TWICE AND THE SECOND VERSION IS
	 *    RECORDED HERE RATHER THAN QUIETLY REPLACED, because the mistake is
	 *    instructive and cheap to repeat.
	 *
	 * ⛔ A bare `strpos( $markup, 'download' )` fails: the founder's own bar
	 *    line contains the word -- *"see your email to download it now"*.
	 * ⛔ So did `/\sdownload(?=[\s>=])/`, which was supposed to match only the
	 *    ATTRIBUTE form. It matches *" download it"* too: a space before, a
	 *    space after. The regex was written to dodge exactly the string it hit.
	 *
	 * ⭐ THE ENGLISH WORD IS SUPPOSED TO BE THERE. What must be absent is the
	 *    download AFFORDANCE, and an HTML `download` attribute can only live on
	 *    an anchor. Section 5 proves there is no `<a` in the panel at all, which
	 *    settles it more completely than any pattern over prose could. What is
	 *    left to assert here is the theme's own hook.
	 */
	bhp_k404_assert(
		false === strpos( $mcode, 'data-bhp-kit-download' ),
		'1: the download hook is gone (the download AFFORDANCE is settled by the no-anchor check in section 5)',
		$failures
	);

	echo "\n== 2: there is one reader and no branch to choose ==\n";

	bhp_k404_assert(
		false === strpos( $mcode, '<iframe' ) && false === strpos( $mcode, 'data-bhp-kit-frame' ),
		'2: no PDF iframe is rendered, on any device',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $mcode, 'data-bhp-kit-view' ),
		'2: no forced-branch attribute is rendered',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $mcode, 'pdfViewerEnabled' ),
		'2: the feature probe is not in the rendered script',
		$failures
	);
	bhp_k404_assert(
		false !== strpos( $markup, 'data-bhp-kit-reader' ),
		'2: the images container is the reader and is present',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $mcode, 'data-bhp-kit-fallback' ) && false === strpos( $mcode, 'hidden>' ),
		'2: the reader is NOT a hidden fallback any more',
		$failures
	);

	echo "\n== 3: eleven pages render, unconditionally ==\n";

	$pages = bhp_kit_modal_sample_pages();

	bhp_k404_assert(
		11 === count( $pages ),
		'3: eleven page rows are built from files proved to exist on disk (got ' . count( $pages ) . ')',
		$failures
	);
	bhp_k404_assert(
		11 === substr_count( $markup, 'class="bhp-kit-modal__page"' ),
		'3: eleven individual page images render inline (got ' . substr_count( $markup, 'class="bhp-kit-modal__page"' ) . ')',
		$failures
	);
	/*
	 * ⛔⛔ THE RESERVED BOX IS FUNCTIONAL, NOT COSMETIC, AND 1.19.392 MEASURED
	 *     IT: without intrinsic dimensions the stack collapsed and pages 2 to
	 *     11 never loaded at all. It carries into 1.19.404 unchanged and is
	 *     re-asserted here because this build is the first in which the images
	 *     are the ONLY thing the panel has.
	 */
	bhp_k404_assert(
		11 === substr_count( $markup, 'width="' ) && 11 === substr_count( $markup, 'height="' ),
		'3: every page image reserves its box with width and height attributes',
		$failures
	);
	bhp_k404_assert(
		11 === substr_count( $markup, 'loading="eager"' ),
		'3: every page row is eager, per the 1.19.392 in-browser measurement',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, 'loading="lazy"' ),
		'3: no row is lazy - lazy rows were measured NOT to load inside this scroller',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, 'alt=""' ),
		'3: no page image ships an empty alt attribute',
		$failures
	);

	/*
	 * ⭐⭐ SECTION 4 IS COPY, AND COPY IS ASSERTED BYTE FOR BYTE BECAUSE IT IS
	 *     LOCKED PROSE (Standing Rules §9). Two of these three strings are
	 *     ANDREW'S OWN WORDS (seals 1358, 1362, 1366); the third is his
	 *     approved wording. ⛔ None of them is this desk's to improve.
	 *
	 * ⛔⛔ PARENT10 IS CHECKED ON ITS OWN. It is a real coupon code a parent will
	 *     type. A lower-casing, a smart-quote pass, a "10 %" spacing fix or a
	 *     hyphenation would ship a code that does not work, and none of those
	 *     would fail a looser check on the sentence around it.
	 */
	echo "\n== 4: the founder copy ships verbatim ==\n";

	bhp_k404_assert(
		false !== strpos( $markup, 'This Free Chapter Activity was sent to your email, see your email to download it now, with your PARENT10 code for 10% off the Collection' ),
		'4: the bar carries the founder-authored line VERBATIM (seals 1358, 1362)',
		$failures
	);
	bhp_k404_assert(
		false !== strpos( $markup, 'PARENT10' ),
		'4: the coupon code PARENT10 survives intact',
		$failures
	);
	bhp_k404_assert(
		false !== strpos( $markup, 'Add andrew@braveheartspublishing.com to your contacts so the next two emails reach your inbox. I read the replies myself.' ),
		'4: the approved first-person contacts line renders under the pages VERBATIM (seal 1366)',
		$failures
	);
	bhp_k404_assert(
		false !== strpos( $markup, 'qa.suite404@example.com' ),
		'4: the bar still shows the address the visitor typed',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, 'next three emails' ),
		'4: the count is TWO - journey 89 has three steps and E1 has already been sent',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, 'Also sent to your email:' ),
		'4: the superseded 1.19.392 bar label is gone',
		$failures
	);
	/*
	 * ⭐ THE THREE STANDING COPY RAILS, SWEPT ON THE FINISHED STRING RATHER
	 *    THAN TRUSTED TO INTENTION (§9.1 voice, §9.4 American spelling, and
	 *    the no-em-dash rule).
	 *
	 * ⚠️ THE "we" SWEEP IS DELIBERATELY WORD-BOUNDARY MATCHED. A substring
	 *    search for "we" hits "answer", "between" and "viewer"; the failure
	 *    that matters is the PRONOUN reaching a parent, and a rail that cries
	 *    wolf on "viewer" gets switched off. ⛔ Quoted third-party words would
	 *    be exempt under §9.1a, and there are none in this panel.
	 */
	/*
	 * ⚠️ SWEPT OVER THE VISIBLE COPY ONLY. `wp_strip_all_tags()` removes the
	 *    `<style>` and `<script>` blocks AND their contents, which is what is
	 *    wanted: a CSS property or a JS identifier is not customer-facing copy,
	 *    and a rail that fires on `cursor` or `viewer` is a rail somebody
	 *    switches off.
	 */
	$visible = wp_strip_all_tags( $markup );

	bhp_k404_assert(
		0 === preg_match( '/\b(we|us|our)\b/i', $visible ),
		'4: no "we", "us" or "our" in any customer-facing text (§9.1)',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $visible, "\xe2\x80\x94" ) && false === strpos( $markup, '&mdash;' ),
		'4: no em dash in any rendered string',
		$failures
	);
	bhp_k404_assert(
		0 === preg_match( '/\b(colour|favourite|realise|recognise|organise|centre|catalogue|grey|whilst|towards|learnt)\b/i', $visible ),
		'4: American spelling throughout (§9.4)',
		$failures
	);

	echo "\n== 5: one Close button, plus the round close ==\n";

	bhp_k404_assert(
		1 === substr_count( $markup, 'class="bhp-kit-modal__btn bhp-kit-modal__btn--quiet"' ),
		'5: exactly one button in the action row',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, 'Download PDF' ) && false === strpos( $markup, '>Print<' ),
		'5: neither Download PDF nor Print is rendered',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, 'Open the full kit in a new tab' ),
		'5: the link-out is gone',
		$failures
	);
	bhp_k404_assert(
		false !== strpos( $markup, 'class="bhp-kit-modal__close"' ),
		'5: the round close control in the bar is still present',
		$failures
	);
	/*
	 * ⚠️ COUNTED AS AN ATTRIBUTE, NOT AS A SUBSTRING, AND THE DIFFERENCE IS
	 *    REAL: `substr_count()` would return FOUR, because the inline script
	 *    also contains the selector `'[data-bhp-kit-close]'`. The lookahead
	 *    matches only the attribute form (followed by whitespace or `>`), so
	 *    the selector is not counted and the number means what its label says.
	 */
	$close_hooks = preg_match_all( '/data-bhp-kit-close(?=[\s>])/', $markup );
	bhp_k404_assert(
		3 === $close_hooks,
		'5: exactly three close hooks - the backdrop, the round close, and the Close button (got ' . (int) $close_hooks . ')',
		$failures
	);
	bhp_k404_assert(
		false === strpos( $markup, '<a ' ),
		'5: there is no anchor of any kind left in the panel',
		$failures
	);
}

/*
 * ⭐ SECTION 6 READS THE SOURCE, NOT THE MARKUP, SO IT RUNS EVEN WHERE NO KIT
 *    IS CONFIGURED AND SECTIONS 1 TO 5 SKIPPED. These are the properties the
 *    brief said to KEEP, and a build that quietly lost one of them while
 *    removing the PDF would look like a success.
 */
echo "\n== 6: everything the brief said to keep is still here ==\n";

bhp_k404_assert(
	false === strpos( $code, 'bhp_kit_view' ) && ! function_exists( 'bhp_kit_modal_forced_view' ),
	'6: the staging QA view flag and its function are gone from code and runtime',
	$failures
);
bhp_k404_assert(
	false === strpos( $code, 'pdfViewerEnabled' ) && false === strpos( $code, 'navigator.userAgent' ),
	'6: no feature probe and no user-agent sniff replaced it',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, 'delete_transient( $key );' ),
	'6: the token is still burned on first read (single use)',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, "empty( \$download['ready'] )" ),
	'6: the readiness gate is kept, so a site with no kit shows no preview',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, 'BHP_KIT_MODAL_LEAD_MAGNET !== $lead_magnet' ),
	'6: a token minted for another lead magnet still cannot open this panel',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, 'function focusables()' ) && false !== strpos( $src, "event.key === 'Escape'" ),
	'6: the focus trap and the Escape handler survive',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, 'bhp-kit-modal__backdrop" data-bhp-kit-close' ),
	'6: the backdrop still closes the panel',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, "body.classList.add('bhp-kit-modal-open')" ) && false !== strpos( $src, 'body.bhp-kit-modal-open{overflow:hidden}' ),
	'6: the body scroll lock survives',
	$failures
);
/*
 * ⭐ OVERSCROLL CONTAINMENT IS ASSERTED IN TWO PLACES, AND THAT IS THE 404
 *    ADDITION. On the READER it stops the scroll chain at the end of the
 *    eleven pages; on the DIALOG it stops a touch that begins on the bar or
 *    the button row - neither of which scrolls - from being handed to the
 *    document behind. On iOS the body lock alone has never been enough.
 */
bhp_k404_assert(
	false !== strpos( $src, '.bhp-kit-modal__reader{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain;' ),
	'6: overscroll-behavior:contain is on the reader',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, 'overflow:hidden;overscroll-behavior:contain}' ),
	'6: overscroll-behavior:contain is ALSO on the dialog (1.19.404 addition)',
	$failures
);
/*
 * ⭐ 1.19.403'S SIZING IS RE-ASSERTED HERE RATHER THAN LEFT TO THAT SUITE,
 *    because this build changed what fills the panel and the ORDER of the pair
 *    is load bearing: `vh` is declared FIRST so a browser too old to parse
 *    `dvh` drops the second declaration and keeps a working value.
 */
bhp_k404_assert(
	false !== strpos( $src, 'height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;' ),
	'6: the panel keeps 1.19.403 vh-then-dvh sizing in both properties, in that order',
	$failures
);
bhp_k404_assert(
	false !== strpos( $src, '@media (min-width:768px){.bhp-kit-modal{padding:24px}.bhp-kit-modal__dialog{height:min(92vh,100%);height:min(92dvh,100%);' ),
	'6: the desktop rule keeps the same vh-then-dvh pair',
	$failures
);
bhp_k404_assert(
	false === strpos( $code, 'data-popup-config' ) && false === strpos( $code, 'dataLayer' )
		&& false === strpos( $code, 'bhp_parent_popup' ) && false === strpos( $code, 'bhp_mariana_popup' ),
	'6: funnel isolation held - no popup config, no dataLayer, no parent or teacher storage key',
	$failures
);

/*
 * ⭐ SECTION 7 IS THE THANK-YOU PAGE, WHICH IS A SECOND FILE AND A SECOND
 *    SURFACE. Its two copy changes are approved verbatim (seals 1366) and are
 *    asserted as strings on the SOURCE, because rendering a page template
 *    inside a WP-CLI eval-file is not something this suite can do honestly.
 *    ⚠️ That is a weaker instrument than section 4's rendered-markup check and
 *    is labelled so rather than presented as equivalent. The rendered proof is
 *    the browser walk in the build report.
 */
echo "\n== 7: the thank-you page copy ==\n";

$typ_path = get_template_directory() . '/page-adventure-kit-thank-you.php';
$typ      = ( file_exists( $typ_path ) && is_readable( $typ_path ) ) ? (string) file_get_contents( $typ_path ) : '';
/*
 * ⚠️ THE ABSENCE CHECKS RUN ON THE COMMENT-STRIPPED COPY, AND THIS CAUGHT A
 *    REAL FALSE FAILURE. The template PRESERVES its superseded drafts in a
 *    comment -- *"a second note arrives from Andrew"*, the third-person wording
 *    that did NOT ship -- exactly so the movement stays visible. `strpos()`
 *    cannot tell a preserved draft from a shipped one, so the raw source made
 *    this assertion fail on the record of the very thing it was checking had
 *    not shipped. ⭐ The PRESENCE checks stay on the raw source: a string that
 *    is there is there.
 */
$typ_code = bhp_k404_code( $typ );

bhp_k404_assert( '' !== $typ, '7: page-adventure-kit-thank-you.php is readable', $failures );
bhp_k404_assert(
	false !== strpos( $typ, 'In two days, a second note arrives from me, subject "How did story time go?" It carries the PARENT10 code again in case you have not used it yet.' ),
	'7: the approved first-person second-email line is present VERBATIM (seal 1366)',
	$failures
);
bhp_k404_assert(
	false !== strpos( $typ, 'Please allow up to 15 minutes for the email to arrive, and check your promotions or spam folder if you do not see it.' ),
	'7: the wait guidance now names the EMAIL, and the spam-folder note is kept word for word',
	$failures
);
bhp_k404_assert(
	false === strpos( $typ_code, 'Please allow up to 15 minutes for it to arrive' ),
	'7: the ambiguous "for it to arrive" wording is gone',
	$failures
);
bhp_k404_assert(
	false === strpos( $typ_code, 'next three emails' ) && false === strpos( $typ_code, 'arrives from Andrew' ),
	'7: neither the wrong count nor the superseded third-person draft shipped',
	$failures
);

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
