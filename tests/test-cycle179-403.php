<?php
/**
 * INSTANT KIT MODAL — PHONE LAYOUT SUITE. Theme 1.19.403,
 * `CYCLE179-LD-BUILD-402-KIT-MODAL-PHONE (theme 1.19.403)`.
 *
 * Run on staging (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-403.php --user=1
 *
 * WHAT THIS SUITE PROVES:
 *   1. THE QA FLAG IS STAGING-ONLY AND FAILS CLOSED. `bhp_kit_modal_forced_view()`
 *      returns '' for every host that is not the exact staging host, for a
 *      missing flag, and for any value that is not one of the two literal
 *      branch names. The rendered markup carries `data-bhp-kit-view` only when
 *      the flag actually resolved.
 *   2. THE TOP BAR CANNOT LOSE ITS CLOSE CONTROL. The close is declared
 *      un-shrinkable with an explicit floor width, and the address is declared
 *      single-line-with-ellipsis rather than wrapping.
 *   3. THE BUTTON ROW STACKS UNDER 480 at full width, and keeps `flex-wrap`
 *      above it as the second net.
 *   4. THE PANEL IS BOUNDED IN BOTH AXES, with `dvh` declared AFTER `vh` so an
 *      old browser keeps the fallback. The ORDER is asserted, not just the
 *      presence of both.
 *   5. NOTHING ELSE MOVED. The token, single-use, funnel-isolation and
 *      PDF-readiness gates this build did not touch are re-asserted here so a
 *      CSS build cannot quietly cost one of them.
 *
 * WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS:
 *   This is a PHP suite. It reads declarations; it does not lay anything out.
 *   It cannot prove a button is on screen, that an ellipsis rendered, that
 *   `dvh` resolved to anything, or how a real iPhone treats a framed PDF.
 *   Those are browser claims and are recorded separately, with the measured
 *   geometry at asserted `window.innerWidth`, in the build report.
 *
 * It touches no post, no product, no option and no WooCommerce record. The
 * only state it writes is its own short-lived transients, which it consumes.
 */

/*
 * ════════════════════════════════════════════════════════════════════════════
 * ⛔⛔ SUPERSEDED BY 1.19.404 (`CYCLE179-LD-BUILD-404-KIT-PREVIEW-ONLY`),
 *     2026-09-07, founder decision seal 1357. READ THIS BEFORE THE FILE.
 * ════════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THIS SUITE WAS WRITTEN FOR 1.19.403, WHOSE MODAL HAD TWO READER BRANCHES
 *    (a PDF iframe and an image fallback), a staging-only flag to choose
 *    between them, and three buttons. 1.19.404 has ONE branch, no flag and one
 *    button.
 *
 * ⛔ THE ASSERTIONS THAT DEPENDED ON THE REMOVED MACHINERY ARE INVERTED IN
 *    PLACE, NOT DELETED, AND THE FILE IS NOT DELETED EITHER. Two reasons, and
 *    the second is mechanical:
 *      1. an inverted assertion still guards the same property -- it now
 *         proves the thing STAYED gone, which is the only guard against a
 *         copy-paste quietly restoring the PDF path;
 *      2. deleting the file would show as a REMOVED ENTRY in the artefact diff
 *         gate and hard-fail the build, because `wp theme install --force`
 *         removes the theme directory before extracting. The gate is right;
 *         the file stays.
 *
 * ⭐ EVERY SUPERSEDED ASSERTION IS PRESERVED IN THE COMMENT ABOVE ITS
 *    REPLACEMENT, in the same style `test-kit-instant-modal.php` used for the
 *    1.19.392 inversion. Sections 3, 4, 5 (the sizing half) and 7 are the
 *    1.19.403 build's own work and are UNCHANGED where they still hold.
 *
 * ⭐ THE NEW BEHAVIOUR IS COVERED BY `tests/test-cycle179-404.php`. This file
 *    now covers only the REMOVALS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_k403_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_k403_src() {
	$path = get_template_directory() . '/inc/kit-instant-modal.php';
	return ( file_exists( $path ) && is_readable( $path ) ) ? (string) file_get_contents( $path ) : '';
}

/**
 * The file with EVERY comment removed — PHP doc comments via the tokenizer,
 * then the CSS and JS block comments that live in the inline-HTML half, which
 * the tokenizer hands back untouched.
 *
 * THIS IS NOT FASTIDIOUSNESS, IT IS THE ONLY WAY THE ABSENCE ASSERTIONS MEAN
 * ANYTHING. This file explains at length what it deliberately does NOT do — it
 * names `dataLayer`, `data-popup-config` and the wrapping declaration it just
 * deleted, in prose, in order to say they are absent. `strpos()` cannot tell
 * an explanation from a use, so an absence assertion run against the raw
 * source would fail on the sentence that documents the very thing it is
 * checking for. `test-kit-instant-modal.php` records the same reasoning.
 */
function bhp_k403_code( $src ) {
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

	// The CSS and JS block comments, which are inline HTML to the tokenizer.
	$stripped = preg_replace( '#/\*.*?\*/#s', '', $out );

	return ( null === $stripped ) ? $out : $stripped;
}

/** Render the footer hook's output for the current `$_GET` / `$_SERVER`. */
function bhp_k403_render() {
	ob_start();
	bhp_kit_modal_render();
	return (string) ob_get_clean();
}

/**
 * Mint a token and render with it, restoring the request superglobals after.
 * Returns the rendered markup.
 */
function bhp_k403_render_with_token( $email, $extra_get = array(), $host = null ) {
	$get_backup  = $_GET;
	$host_backup = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;

	if ( null !== $host ) {
		$_SERVER['HTTP_HOST'] = $host;
	}

	$token = bhp_kit_modal_mint( $email, BHP_KIT_MODAL_LEAD_MAGNET );
	$_GET  = array_merge( array( BHP_KIT_MODAL_ARG => $token ), $extra_get );

	/*
	 * `bhp_kit_modal_consume()` memoises its answer in a static, so a second
	 * render in the same process would otherwise reuse the first token's
	 * payload. The suite therefore renders each case in a fresh sub-request
	 * where it can, and where it cannot it asserts only on the FIRST render.
	 */
	$out = bhp_k403_render();

	$_GET = $get_backup;
	if ( null === $host_backup ) {
		unset( $_SERVER['HTTP_HOST'] );
	} else {
		$_SERVER['HTTP_HOST'] = $host_backup;
	}

	return $out;
}

$src  = bhp_k403_src();
$code = bhp_k403_code( $src );

echo "\n== 0: the subject is loaded ==\n";

bhp_k403_assert( '' !== $src, '0: inc/kit-instant-modal.php is readable', $failures );
/*
 * ⛔ SUPERSEDED 1.19.404. Was: `'0: bhp_kit_modal_forced_view() is defined'`.
 *    The function is DELETED. Its absence is now the assertion, so a future
 *    revert cannot pass this suite.
 */
bhp_k403_assert( ! function_exists( 'bhp_kit_modal_forced_view' ), '0: bhp_kit_modal_forced_view() is GONE (1.19.404 has one reader branch)', $failures );
bhp_k403_assert( function_exists( 'bhp_kit_modal_render' ), '0: bhp_kit_modal_render() is defined', $failures );
bhp_k403_assert( class_exists( 'BHP_Analytics_Config' ), '0: the staging gate class is loaded', $failures );
bhp_k403_assert(
	false !== has_action( 'wp_footer', 'bhp_kit_modal_render' ),
	'0: the renderer is still hooked to wp_footer',
	$failures
);

echo "\n== 1: the QA view flag is GONE, not merely unused ==\n";

/*
 * ⛔⛔ SUPERSEDED 1.19.404 IN FULL. This section held ELEVEN assertions proving
 *     `bhp_kit_modal_forced_view()` was staging-gated and failed closed: that
 *     `?bhp_kit_view=` was ignored on `braveheartspublishing.com` and on
 *     `localhost`, that `images` and `pdf` resolved on the staging host, that
 *     `iframe` and `<script>alert(1)</script>` fell through to the probe, that
 *     the gate used `BHP_Analytics_Config::is_staging()` and never
 *     `wp_get_environment_type()`, and that the whitelist was strict.
 *
 * ⭐ ALL ELEVEN WERE PASSING. They are replaced rather than kept because the
 *    flag they guarded no longer exists: 1.19.404 shows the images on every
 *    device, so there is no second branch for a flag to select. The strongest
 *    remaining statement about a deleted request parameter is that it is
 *    deleted, and that is what is asserted now.
 *
 * ⚠️ THE FINDING THAT SECTION CARRIED IS NOT LOST: `wp_get_environment_type()`
 *    returns `local` on PRODUCTION AND on staging2 (measured over WP-CLI
 *    against both installs, 2026-09-07). The assertion that this file does not
 *    use it is KEPT below, because that trap is still live for anyone editing
 *    this file tomorrow.
 */
bhp_k403_assert(
	false === strpos( $code, 'bhp_kit_view' ),
	'1: the bhp_kit_view request parameter is GONE from the code (comments aside)',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'bhp_kit_modal_forced_view' ),
	'1: no call site for the deleted forced-view function survives',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, "in_array( \$view, array( 'images', 'pdf' ), true )" ),
	'1: the two-branch whitelist is gone with the branch it selected',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'wp_get_environment_type' ),
	'1: the file still does NOT use wp_get_environment_type(), which reports local on BOTH installs',
	$failures
);

echo "\n== 2: the rendered markup exposes no branch flag and no PDF URL ==\n";

/*
 * ⛔ SUPERSEDED 1.19.404. This section asserted that `data-bhp-kit-view="images"`
 *    WAS printed when the staging flag resolved, and that the attribute was
 *    printed ONLY then. Both attributes are gone.
 *
 * ⭐⭐ THE REPLACEMENT IS STRONGER THAN THE ORIGINAL AND IS THE POINT OF THE
 *     WHOLE BUILD: the panel must not hand out the PDF. Seal 1357 -- Andrew:
 *     *"maybe do the preview and dont let them download it or print it from
 *     there?"* So this now proves the rendered markup contains NEITHER the
 *     branch flag NOR the kit URL, against the URL the site is really
 *     configured with rather than against a literal.
 */
$download = function_exists( 'bhp_get_reluctant_reader_download' ) ? bhp_get_reluctant_reader_download() : array( 'ready' => false );
if ( empty( $download['ready'] ) ) {
	echo "SKIP: no kit PDF configured in this environment, so section 2 cannot render.\n";
} else {
	$markup = bhp_k403_render_with_token( 'qa.suite403@example.com' );
	/*
	 * ⚠️ THE INLINE `<style>` AND `<script>` COMMENTS ARE PART OF THE RENDERED
	 *    MARKUP -- they are inline HTML, not PHP -- and 1.19.404's script
	 *    comment NAMES `data-bhp-kit-pdf` in order to say it was removed. An
	 *    absence check on the raw markup therefore fails on the sentence that
	 *    documents the removal. Observed as a real false failure on the first
	 *    staging run of this suite, 2026-09-07.
	 * ⛔ THE PDF URL CHECK BELOW DELIBERATELY STAYS ON THE RAW MARKUP: a URL
	 *    appearing anywhere at all, comment included, would be a leak.
	 */
	$mcode = bhp_k403_code( $markup );

	bhp_k403_assert( false !== strpos( $markup, 'id="bhp-kit-modal"' ), '2: the modal rendered for a valid token', $failures );
	bhp_k403_assert(
		false === strpos( $mcode, 'data-bhp-kit-view' ),
		'2: no forced-branch attribute is printed, on any host',
		$failures
	);
	bhp_k403_assert(
		false === strpos( $mcode, 'data-bhp-kit-pdf' ),
		'2: no data-bhp-kit-pdf attribute is printed',
		$failures
	);
	bhp_k403_assert(
		'' !== (string) $download['url'] && false === strpos( $markup, (string) $download['url'] ),
		'2: the configured kit PDF URL does NOT appear anywhere in the rendered panel',
		$failures
	);
	bhp_k403_assert(
		false === strpos( $mcode, '<iframe' ),
		'2: there is no iframe in the rendered panel',
		$failures
	);
	bhp_k403_assert(
		false !== strpos( $markup, 'qa.suite403@example.com' ),
		'2: the bar still carries the address that was minted',
		$failures
	);
}

echo "\n== 3: the top bar cannot lose its close control ==\n";

bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__close{flex:0 0 40px;width:40px;min-width:40px;height:40px' ),
	'3: the close control is un-shrinkable and has an explicit floor width',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__bar-email{display:block;min-width:0;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' ),
	'3: the address is one line with an ellipsis',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'word-break:break-all' ),
	'3: the wrapping declaration that grew the bar is GONE, not merely overridden',
	$failures
);
/*
 * ⛔ SUPERSEDED 1.19.404. Two assertions stood here, both about a bar that
 *    carried a short LABEL beside the address:
 *      · `.bhp-kit-modal__bar-text{... font-size:.9rem ... flex-direction:column}`
 *        stacked the label above the address under 480;
 *      · `@media (min-width:480px){.bhp-kit-modal__bar-text{flex-direction:row`
 *        returned it to one row above 480.
 *
 * ⭐ THE BAR NOW CARRIES A SENTENCE, NOT A LABEL -- the founder's own line plus
 *    his approved PARENT10 clause (seals 1358, 1362) -- so it is a permanent
 *    column at every width and the 480 row rule is deleted. What still MUST
 *    hold is that the address keeps the one-line-with-ellipsis treatment while
 *    the sentence does NOT, because a clipped coupon code is a broken coupon.
 *    That pair is asserted immediately above and below.
 */
bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__bar-text{margin:0;font-size:.8rem;line-height:1.35;flex:1 1 auto;min-width:0;display:flex;flex-direction:column' ),
	'3: the bar text is a column at every width now that it carries a sentence',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__bar-msg{min-width:0}' ),
	'3: the message line carries NO ellipsis or nowrap, so PARENT10 cannot be clipped',
	$failures
);

echo "\n== 4: the button row stacks under 480 ==\n";

bhp_k403_assert(
	false !== strpos( $src, '@media (max-width:479px){.bhp-kit-modal__actions{flex-direction:column;flex-wrap:nowrap}.bhp-kit-modal__btn{flex:0 0 auto;width:100%}}' ),
	'4: under 480 the button row still stacks at full width (one button since 1.19.404)',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__actions{display:flex;flex-wrap:wrap;' ),
	'4: the row form keeps flex-wrap as the second net above 480',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__btn{flex:1 1 auto;min-width:0;min-height:44px' ),
	'4: every button keeps the 44px minimum height',
	$failures
);

echo "\n== 5: the panel is bounded in both axes, vh before dvh ==\n";

$dialog_rule = '.bhp-kit-modal__dialog{position:relative;display:flex;flex-direction:column;width:min(980px,100%);max-width:100%;height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;';
bhp_k403_assert(
	false !== strpos( $src, $dialog_rule ),
	'5: the panel declares height and max-height in vh THEN dvh, and is width-capped',
	$failures
);

$vh_pos  = strpos( $src, 'height:100vh;height:100dvh' );
bhp_k403_assert(
	false !== $vh_pos,
	'5: the vh declaration precedes the dvh one, so a browser without dvh keeps the fallback',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, '@media (min-width:768px){.bhp-kit-modal{padding:24px}.bhp-kit-modal__dialog{height:min(92vh,100%);height:min(92dvh,100%);max-height:100%;border-radius:14px}}' ),
	'5: the desktop rule carries the same vh-then-dvh pair and stays bounded by the padded box',
	$failures
);
/*
 * ⛔ SUPERSEDED 1.19.404. Was: `.bhp-kit-modal__viewer{flex:1 1 auto;...}` is
 *    the only growing child, and `.bhp-kit-modal__frame{display:block;width:
 *    100%;height:100%;border:0}` fills it. Both rules are DELETED with the
 *    iframe they styled.
 *
 * ⭐ THE PROPERTY IS UNCHANGED AND STILL ASSERTED, on the element that
 *    inherited the job: the reader is the one `flex:1 1 auto` child and may
 *    shrink below its content, which is what keeps the column inside the
 *    panel. The frame rule's ABSENCE is asserted so a copy-paste cannot bring
 *    the PDF path back through the stylesheet.
 */
bhp_k403_assert(
	false !== strpos( $src, '.bhp-kit-modal__reader{flex:1 1 auto;min-height:0;overflow-y:auto;' ),
	'5: the reader is the only growing child and may shrink below its content',
	$failures
);
bhp_k403_assert(
	false === strpos( $src, '.bhp-kit-modal__frame{display:block' ),
	'5: the PDF frame CSS rule is gone',
	$failures
);
bhp_k403_assert(
	false === strpos( $src, '.bhp-kit-modal__viewer{' ),
	'5: the viewer CSS rule is gone',
	$failures
);

echo "\n== 6: the JS has no branch to honour ==\n";

/*
 * ⛔⛔ SUPERSEDED 1.19.404. Four assertions stood here: that the script defined
 *     `forcedView()`, that `if (forced) { return forced === 'pdf'; }` let a
 *     forced branch beat the probe, that `navigator.pdfViewerEnabled` was the
 *     default path, and that no user-agent sniff was used.
 *
 * ⚠️ THE THIRD ONE IS THE REASON THIS BUILD EXISTS. `navigator.pdfViewerEnabled`
 *    reports TRUE on iOS Safari, which then paints page one of a framed PDF,
 *    oversized and unscrollable -- observed by Andrew on his own iPhone,
 *    2026-09-07 21:19, and relayed to this desk as a screenshot. A probe whose
 *    one authority lies to it is not a probe, so it is gone rather than
 *    patched.
 *
 * ⭐ THE ANTI-SNIFF ASSERTION IS KEPT AND STRENGTHENED. There is no branch to
 *    choose now, so a user-agent test would be even less defensible than
 *    before, and the guard costs nothing.
 */
bhp_k403_assert(
	false === strpos( $code, 'forcedView' ),
	'6: forcedView() is gone from the script',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'usePdfFrame' ),
	'6: usePdfFrame() is gone from the script',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'pdfViewerEnabled' ),
	'6: the feature probe that iOS Safari answers wrongly is gone from the code',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'bhpKitModalForceView' ),
	'6: the harness global is gone with the flag it fed',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'navigator.userAgent' ),
	'6: and it was NOT replaced by a user-agent sniff',
	$failures
);

echo "\n== 7: nothing this build did not touch has moved ==\n";

bhp_k403_assert( false !== strpos( $src, 'delete_transient( $key );' ), '7: the token is still burned on read', $failures );
bhp_k403_assert(
	false !== strpos( $src, "empty( \$download['ready'] )" ),
	'7: the modal is still gated on the same PDF-readiness flag as the kit page panel',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, "BHP_KIT_MODAL_LEAD_MAGNET !== \$lead_magnet" ),
	'7: a token minted for another lead magnet still cannot open this modal',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'data-popup-config' ),
	'7: funnel isolation held: the modal still carries no popup config',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'dataLayer' ),
	'7: funnel isolation held: the modal still pushes no dataLayer event',
	$failures
);
bhp_k403_assert(
	false === strpos( $code, 'bhp_parent_popup' ) && false === strpos( $code, 'bhp_mariana_popup' ),
	'7: funnel isolation held: the modal still mints no parent or teacher storage key',
	$failures
);
bhp_k403_assert(
	false !== strpos( $src, "loading=\"eager\"" ),
	'7: the page images are still eager, per the 1.19.392 measurement',
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
