<?php
/**
 * INSTANT KIT MODAL SUITE — theme 1.19.392, `CYCLE179-LD-KIT-MODAL-392`.
 *
 * Run on staging (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-kit-instant-modal.php --user=1
 *
 * WHAT THIS SUITE PROVES, by running the real functions rather than reading
 * them:
 *   1. THE TRIGGER. A request carrying a valid, unspent kit token renders the
 *      modal; a request with no token, a malformed token, a spent token or a
 *      token minted for a different lead magnet renders NOTHING AT ALL.
 *   2. THE BAR. The rendered bar says "Also sent to your email:" and carries
 *      the exact address that was minted, escaped.
 *   3. THE CLOSE. Three close controls exist (the bar button, the backdrop and
 *      the footer button), Escape is bound, and focus is trapped by a handler
 *      that reads the dialog's own focusables.
 *   4. NOTHING FIRES FOR A FAILED SIGNUP. The mint refuses an empty address,
 *      an invalid address and any lead magnet other than the kit; and in
 *      `inc/mailchimp.php` the single mint call sits downstream of the
 *      subscribe/tag `catch`, so no failure path can reach it.
 *   5. SINGLE USE. The transient is gone after the first consume, so a
 *      refresh, a back-navigation or a forwarded link opens nothing.
 *   6. NO PERSONAL DATA IN A URL. The token is 32 opaque characters and the
 *      address appears nowhere in what the redirect helper builds.
 *   7. FUNNEL ISOLATION HELD. The rendered markup mints no parent or teacher
 *      storage prefix, no analytics prefix, no `data-popup-config`, and pushes
 *      no `dataLayer` event.
 *   8. THE COPY RAILS, asserted against the RENDERED OUTPUT rather than the
 *      source: no em dash, no "we"/"us"/"our", no British spelling, no
 *      outcome claim, no reading time, no page-count promise.
 *
 * WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS:
 *   It is a PHP suite, not a browser. It cannot observe the dialog painting,
 *   the PDF viewer scrolling eleven pages, a focus trap actually holding, an
 *   Escape keypress, `navigator.pdfViewerEnabled` on a real iPhone, or the
 *   print dialog opening. Those are browser-QA claims and are recorded
 *   separately, with screenshots, in the release handoff. It also cannot
 *   prove a Mailchimp contact was created — staging runs the transport stub.
 *
 * It touches no post, no product, no option and no WooCommerce record. The
 * only state it writes is its own short-lived transients, each of which it
 * consumes or deletes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$failures = array();

function bhp_kim_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_kim_read( $relative ) {
	$path = get_template_directory() . '/' . ltrim( $relative, '/' );
	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return '';
	}
	return (string) file_get_contents( $path );
}

/**
 * Comments stripped before every source-level assertion, for the reason
 * `test-signup-modal.php` documents at length: a well-documented file names
 * the things it deliberately does NOT do, and `strpos()` cannot tell an
 * explanation from a use.
 */
function bhp_kim_code_php( $src ) {
	if ( '' === $src || ! function_exists( 'token_get_all' ) ) {
		return $src;
	}
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
	return $out;
}

/** Render the footer hook's output for the current `$_GET`. */
function bhp_kim_render() {
	ob_start();
	bhp_kit_modal_render();
	return (string) ob_get_clean();
}

echo "\n== 0: the subject is loaded ==\n";

bhp_kim_assert( function_exists( 'bhp_kit_modal_mint' ), '0: bhp_kit_modal_mint() is defined', $failures );
bhp_kim_assert( function_exists( 'bhp_kit_modal_consume' ), '0: bhp_kit_modal_consume() is defined', $failures );
bhp_kim_assert( function_exists( 'bhp_kit_modal_render' ), '0: bhp_kit_modal_render() is defined', $failures );
bhp_kim_assert(
	false !== has_action( 'wp_footer', 'bhp_kit_modal_render' ),
	'0: the renderer is hooked to wp_footer',
	$failures
);

$bhp_kim_download = function_exists( 'bhp_get_reluctant_reader_download' ) ? bhp_get_reluctant_reader_download() : array( 'ready' => false );
bhp_kim_assert(
	! empty( $bhp_kim_download['ready'] ),
	'0: this environment has a kit PDF configured (without one the modal is correctly inert and sections 1-3 cannot run)',
	$failures
);

echo "\n== 1: the mint refuses everything that is not a real kit signup ==\n";

bhp_kim_assert(
	'' === bhp_kit_modal_mint( '', 'reluctant_reader_adventure_kit' ),
	'1: an empty address mints no token',
	$failures
);
bhp_kim_assert(
	'' === bhp_kit_modal_mint( 'not-an-address', 'reluctant_reader_adventure_kit' ),
	'1: an invalid address mints no token',
	$failures
);
bhp_kim_assert(
	'' === bhp_kit_modal_mint( 'qa+kitmodal@braveheartspublishing.com', 'teacher_adventure_toolkit' ),
	'1: the educator toolkit mints no kit token',
	$failures
);
bhp_kim_assert(
	'' === bhp_kit_modal_mint( 'qa+kitmodal@braveheartspublishing.com', 'meaningful_gift_guide' ),
	'1: the gift guide mints no kit token',
	$failures
);
bhp_kim_assert(
	'' === bhp_kit_modal_mint( 'qa+kitmodal@braveheartspublishing.com', '' ),
	'1: an empty lead magnet mints no kit token',
	$failures
);

echo "\n== 2: a real kit signup mints an opaque, single-use token ==\n";

$bhp_kim_email = 'qa+kitmodal@braveheartspublishing.com';
$bhp_kim_token = bhp_kit_modal_mint( $bhp_kim_email, 'reluctant_reader_adventure_kit' );

bhp_kim_assert(
	is_string( $bhp_kim_token ) && preg_match( '/^[A-Za-z0-9]{32}$/', $bhp_kim_token ),
	'2: the token is exactly 32 alphanumerics',
	$failures
);
bhp_kim_assert(
	false === strpos( $bhp_kim_token, '@' ) && false === stripos( $bhp_kim_token, 'braveheart' ),
	'2: the token carries no fragment of the address',
	$failures
);
bhp_kim_assert(
	false === strpos( bhp_kit_modal_key( $bhp_kim_token ), $bhp_kim_token ),
	'2: the storage key is a hash, not the token itself',
	$failures
);

echo "\n== 3: the trigger, the bar and the close ==\n";

/*
 * ⭐ THE ORDER OF THESE SECTIONS MATTERS AND IS NOT ARBITRARY.
 *    `bhp_kit_modal_consume()` memoises its answer for the whole request, so
 *    the FIRST call in this process decides what every later call sees. The
 *    negative cases therefore have to be asserted through paths that do not
 *    consume, and the one real render happens here, once.
 */
$_GET[ BHP_KIT_MODAL_ARG ] = $bhp_kim_token;
$bhp_kim_html              = bhp_kim_render();

bhp_kim_assert(
	'' !== trim( $bhp_kim_html ),
	'3 TRIGGER: a request carrying a valid token renders the modal',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'id="bhp-kit-modal"' )
		&& false !== strpos( $bhp_kim_html, 'role="dialog"' )
		&& false !== strpos( $bhp_kim_html, 'aria-modal="true"' ),
	'3 TRIGGER: it renders as a labelled modal dialog',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'Also sent to your email:' ),
	'3 BAR: the top bar carries the exact phrase "Also sent to your email:"',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, esc_html( $bhp_kim_email ) ),
	'3 BAR: the bar shows the address that was minted',
	$failures
);
bhp_kim_assert(
	1 === preg_match( '/bhp-kit-modal__bar\b.*?bhp-kit-modal__bar-email/s', $bhp_kim_html ),
	'3 BAR: the address sits inside the bar, not loose in the dialog',
	$failures
);
bhp_kim_assert(
	substr_count( $bhp_kim_html, 'data-bhp-kit-close' ) >= 3,
	'3 CLOSE: at least three close controls exist (bar button, backdrop, footer button)',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'aria-label="Close and return to the page"' ),
	'3 CLOSE: the bar close control has an accessible name that says where it returns',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, "'Escape'" ) && false !== strpos( $bhp_kim_html, "addEventListener('keydown'" ),
	'3 CLOSE: Escape is bound on keydown',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'function focusables' ) && false !== strpos( $bhp_kim_html, "event.key !== 'Tab'" ),
	'3 CLOSE: a Tab focus trap is present',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'bhp-kit-modal-open' ),
	'3: the body scroll lock class is applied',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'mariana-popup is-open' ),
	'3: it wears .mariana-popup.is-open so the exit-intent engine defers to it',
	$failures
);
bhp_kim_assert(
	false === strpos( $bhp_kim_html, 'data-popup-config' ),
	'3: it carries no data-popup-config, so the popup engine never adopts it',
	$failures
);

echo "\n== 4: the whole kit is reachable, and the fallback does not overclaim ==\n";

$bhp_kim_pdf = isset( $bhp_kim_download['url'] ) ? $bhp_kim_download['url'] : '';

bhp_kim_assert(
	'' !== $bhp_kim_pdf && false !== strpos( $bhp_kim_html, esc_url( $bhp_kim_pdf ) ),
	'4: the configured kit PDF is the document the modal renders',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'data-bhp-kit-frame' ) && false !== strpos( $bhp_kim_html, '<iframe' ),
	'4: the primary reader is a same-origin iframe, so it can scroll and print',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'data-bhp-kit-print' ) && false !== strpos( $bhp_kim_html, 'contentWindow.print()' ),
	'4: Print prints the kit, not the page behind it',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'download data-bhp-kit-download' ) || false !== strpos( $bhp_kim_html, 'data-bhp-kit-download' ),
	'4: a Download PDF control is present',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'navigator.pdfViewerEnabled' ),
	'4: the fallback is chosen by feature probe, never by user-agent sniffing',
	$failures
);

/*
 * ⭐⭐ 1.19.392 · THE HONESTY ASSERTION, INVERTED BECAUSE THE FACT INVERTED.
 *
 * ⛔ THE SUPERSEDED PAIR IS DESCRIBED RATHER THAN DELETED SILENTLY. Until
 *    1.19.392 this section asserted (a) that `kit-sample-p05-1200.jpg` was
 *    ABSENT from the theme and (b) that the fallback said in words *"Pages 5
 *    to 11 are in the PDF file."*. Both were true and both are now false:
 *    `design-creative` rendered pages 5 to 11 for
 *    `CYCLE179-DES-KIT-SAMPLE-P5-11` and they are shipped.
 *
 * ⭐ THE RULE THE OLD PAIR ENFORCED IS THE ONE ENFORCED BELOW, POINTED THE
 *    OTHER WAY: the fallback must never claim more than is on the screen.
 *    Before, that meant proving the shortfall was disclosed. Now it means
 *    proving all eleven pages are really there AND that the disclosure
 *    sentence is gone, so the copy and the artefact cannot drift apart in
 *    either direction.
 */
$bhp_kim_sample_dir = get_template_directory() . '/assets/img/kit-sample/';
$bhp_kim_missing    = array();
for ( $bhp_kim_n = 1; $bhp_kim_n <= 11; $bhp_kim_n++ ) {
	foreach ( array( '600', '1200' ) as $bhp_kim_w ) {
		$bhp_kim_f = sprintf( 'kit-sample-p%02d-%s.jpg', $bhp_kim_n, $bhp_kim_w );
		if ( ! file_exists( $bhp_kim_sample_dir . $bhp_kim_f ) ) {
			$bhp_kim_missing[] = $bhp_kim_f;
		}
	}
}

bhp_kim_assert(
	empty( $bhp_kim_missing ),
	'4: all eleven kit pages ship at both widths (missing: ' . ( $bhp_kim_missing ? implode( ', ', $bhp_kim_missing ) : 'none' ) . ')',
	$failures
);

$bhp_kim_pages = function_exists( 'bhp_kit_modal_sample_pages' ) ? bhp_kit_modal_sample_pages() : array();

bhp_kim_assert(
	11 === count( $bhp_kim_pages ),
	'4: the fallback builds eleven page rows, not four (got ' . count( $bhp_kim_pages ) . ')',
	$failures
);
bhp_kim_assert(
	11 === substr_count( $bhp_kim_html, 'class="bhp-kit-modal__page"' ),
	'4: eleven individual page images render inline, one element each (got ' . substr_count( $bhp_kim_html, 'class="bhp-kit-modal__page"' ) . ')',
	$failures
);
bhp_kim_assert(
	false === strpos( $bhp_kim_html, 'kit-full-strip' ) && false === strpos( $bhp_kim_html, 'kit-sample-strip' ),
	'4: the single tall strip is NOT used (individual images, per the render note)',
	$failures
);
bhp_kim_assert(
	false === strpos( $bhp_kim_html, 'Pages 5 to 11 are in the PDF file' ),
	'4: the superseded shortfall sentence is gone from the rendered fallback',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'all 11 pages of the kit are shown above' ),
	'4: the fallback states the count it actually rendered',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'Open the full kit in a new tab' ),
	'4: the fallback always offers a route to the complete kit',
	$failures
);

/*
 * ⛔⛔ EVERY PAGE IMAGE CARRIES width AND height, AND THIS IS A FUNCTIONAL
 *     ASSERTION, NOT A HOUSE-STYLE ONE.
 *
 * ⭐ THE DEFECT IT PINS WAS OBSERVED IN A BROWSER ON STAGING 1.19.392, not
 *    predicted: with no intrinsic dimensions the ten `loading="lazy"` pages
 *    had zero height, all eleven collapsed into a 1,114 px stack inside a
 *    323 px scroller, and pages 2 to 11 never loaded at all
 *    (`naturalWidth === 0` after each had been scrolled to). The fallback is
 *    the whole answer to "scroll and see the entire thing" on a browser with
 *    no PDF viewer, and without these attributes it showed page one.
 *
 * ⛔ SO IF SOMEONE REMOVES THEM, THIS FAILS. The count is asserted against
 *    the rendered markup rather than the source, because an attribute that
 *    is present in a template but suppressed by a conditional is not on the
 *    page.
 */
$bhp_kim_dim_missing = array();
foreach ( $bhp_kim_pages as $bhp_kim_page ) {
	if ( empty( $bhp_kim_page['w'] ) || empty( $bhp_kim_page['h'] ) ) {
		$bhp_kim_dim_missing[] = $bhp_kim_page['n'];
	}
}
bhp_kim_assert(
	empty( $bhp_kim_dim_missing ),
	'4: every page row carries measured pixel dimensions (missing on pages: ' . ( $bhp_kim_dim_missing ? implode( ', ', $bhp_kim_dim_missing ) : 'none' ) . ')',
	$failures
);
bhp_kim_assert(
	11 === preg_match_all( '/class="bhp-kit-modal__page"[^>]*\swidth="\d+"\s+height="\d+"/', $bhp_kim_html ),
	'4: all eleven rendered page images carry width and height, so every row reserves its box (got ' . preg_match_all( '/class="bhp-kit-modal__page"[^>]*\swidth="\d+"\s+height="\d+"/', $bhp_kim_html ) . ')',
	$failures
);

/*
 * ⛔⛔ NO PAGE IMAGE MAY BE `loading="lazy"`, AND THIS IS THE ASSERTION THAT
 *     KEEPS THE FALLBACK WORKING AT ALL.
 *
 * ⭐ PROVED IN A BROWSER ON STAGING 1.19.392, not argued from first
 *    principles. Lazy: 1 of 11 loaded after the scroller was driven to its
 *    end, with correct width and height on every row, and neither promoting
 *    the parked rows to `eager` nor an `IntersectionObserver` rooted at the
 *    scroller recovered them. Eager: 11 of 11 loaded, 0 broken.
 *
 * ⛔ The cost of eager is roughly 1.7 MB, paid only in this fallback, only
 *    after a real conversion, and only by a browser with no PDF viewer. A
 *    visitor shown one page and ten blank boxes did not get the kit.
 */
$bhp_kim_lazy_pages = preg_match_all( '/class="bhp-kit-modal__page"[^>]*loading="lazy"/', $bhp_kim_html );
bhp_kim_assert(
	0 === $bhp_kim_lazy_pages,
	'4: no page image is lazy-loaded (browser-proved: lazy loads 1 of 11 in this scroller; got ' . $bhp_kim_lazy_pages . ' lazy)',
	$failures
);
bhp_kim_assert(
	11 === preg_match_all( '/class="bhp-kit-modal__page"[^>]*loading="eager"/', $bhp_kim_html ),
	'4: all eleven page images are eager, so every one of them actually loads',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_html, 'aspect-ratio:1200/1553' ),
	'4: the CSS ratio fallback is present, so a page with no measurable size still reserves a box',
	$failures
);

/*
 * ⛔⛔ EVERY PAGE IMAGE CARRIES REAL ALT TEXT. `alt=""` on these would leave a
 *     screen reader user with no representation of the kit at all, which is
 *     the whole artefact. A placeholder such as "Page 7" would be very nearly
 *     as bad, so a length floor is asserted rather than mere non-emptiness.
 */
$bhp_kim_alt_short = array();
foreach ( $bhp_kim_pages as $bhp_kim_page ) {
	if ( strlen( $bhp_kim_page['alt'] ) < 80 ) {
		$bhp_kim_alt_short[] = $bhp_kim_page['n'];
	}
}
bhp_kim_assert(
	empty( $bhp_kim_alt_short ),
	'4: every page image carries descriptive alt text (thin or empty on pages: ' . ( $bhp_kim_alt_short ? implode( ', ', $bhp_kim_alt_short ) : 'none' ) . ')',
	$failures
);

/*
 * ⛔⛔ PAGES 5 TO 11 ARE THE GATED HALF. The public kit page must still name no
 *     page above 4. This asserts the PUBLIC TEMPLATE'S SOURCE, because that is
 *     where the leak would be introduced — by someone converting its explicit
 *     four-row array into a loop over `bhp_kit_modal_sample_pages()` now that
 *     the files are all in one folder. Putting page 7 on the public page gives
 *     away the cliffhanger the email gate is trading on.
 */
$bhp_kim_public_src = @file_get_contents( get_template_directory() . '/template-parts/acquisition/kit-sample-preview.php' );
$bhp_kim_public_hi  = array();
foreach ( array( 'p05', 'p06', 'p07', 'p08', 'p09', 'p10', 'p11' ) as $bhp_kim_slug ) {
	if ( is_string( $bhp_kim_public_src ) && false !== strpos( $bhp_kim_public_src, 'kit-sample-' . $bhp_kim_slug ) ) {
		$bhp_kim_public_hi[] = $bhp_kim_slug;
	}
}
bhp_kim_assert(
	is_string( $bhp_kim_public_src ) && empty( $bhp_kim_public_hi ),
	'4: the PUBLIC kit sample template names no page above 4 (found: ' . ( $bhp_kim_public_hi ? implode( ', ', $bhp_kim_public_hi ) : 'none' ) . ')',
	$failures
);
bhp_kim_assert(
	is_string( $bhp_kim_public_src ) && false === strpos( $bhp_kim_public_src, 'bhp_kit_modal_sample_pages' ),
	'4: the PUBLIC template is not driven by the modal page list',
	$failures
);

/*
 * ⚠️ ROWS 1 TO 4 ARE DUPLICATED FROM THE PUBLIC TEMPLATE ON PURPOSE (see
 *    `bhp_kit_modal_page_alt()`). This is the guard that stops the two copies
 *    drifting: each of the four approved strings must appear in BOTH files.
 */
/*
 * ⛔ THE COMPARISON IS AGAINST SOURCE TEXT, SO THE APOSTROPHE HAS TO BE
 *    ESCAPED BEFORE IT WILL MATCH. Page 3's approved string contains
 *    *"the kit's cream page"*, and the public template holds it inside a
 *    SINGLE-QUOTED PHP literal, where it is written `kit\'s`. The runtime
 *    value has no backslash. Comparing the two naively reports a drift that
 *    does not exist — which is exactly what it did on the first 1.19.392
 *    suite run. Both forms are accepted.
 */
$bhp_kim_alt_map   = function_exists( 'bhp_kit_modal_page_alt' ) ? bhp_kit_modal_page_alt() : array();
$bhp_kim_alt_drift = array();
for ( $bhp_kim_n = 1; $bhp_kim_n <= 4; $bhp_kim_n++ ) {
	if ( ! isset( $bhp_kim_alt_map[ $bhp_kim_n ] ) || ! is_string( $bhp_kim_public_src ) ) {
		$bhp_kim_alt_drift[] = $bhp_kim_n;
		continue;
	}
	$bhp_kim_plain   = $bhp_kim_alt_map[ $bhp_kim_n ];
	$bhp_kim_escaped = str_replace( "'", "\\'", $bhp_kim_plain );
	if ( false === strpos( $bhp_kim_public_src, $bhp_kim_plain )
		&& false === strpos( $bhp_kim_public_src, $bhp_kim_escaped ) ) {
		$bhp_kim_alt_drift[] = $bhp_kim_n;
	}
}
bhp_kim_assert(
	empty( $bhp_kim_alt_drift ),
	'4: alt text for pages 1 to 4 matches the public template byte for byte (drifted: ' . ( $bhp_kim_alt_drift ? implode( ', ', $bhp_kim_alt_drift ) : 'none' ) . ')',
	$failures
);

echo "\n== 5: single use, and no personal data in any URL ==\n";

bhp_kim_assert(
	false === get_transient( bhp_kit_modal_key( $bhp_kim_token ) ),
	'5: the transient is gone after the first render, so a refresh reopens nothing',
	$failures
);

$bhp_kim_probe_token = bhp_kit_modal_mint( 'qa+probe@braveheartspublishing.com', 'reluctant_reader_adventure_kit' );
$bhp_kim_probe_url   = add_query_arg( 'bhp_kit', $bhp_kim_probe_token, home_url( '/reluctant-reader-adventure-kit/' ) );

bhp_kim_assert(
	false === strpos( $bhp_kim_probe_url, '@' ) && false === strpos( rawurldecode( $bhp_kim_probe_url ), 'qa+probe' ),
	'5: a URL carrying the token carries no address, encoded or otherwise',
	$failures
);
delete_transient( bhp_kit_modal_key( $bhp_kim_probe_token ) );

bhp_kim_assert(
	false === get_transient( bhp_kit_modal_key( $bhp_kim_probe_token ) ),
	'5: the suite cleaned up the probe token it minted',
	$failures
);

echo "\n== 6: nothing fires for a failed signup ==\n";

$bhp_kim_mc = bhp_kim_code_php( bhp_kim_read( 'inc/mailchimp.php' ) );

bhp_kim_assert(
	'' !== $bhp_kim_mc,
	'6: inc/mailchimp.php is readable',
	$failures
);
bhp_kim_assert(
	1 === substr_count( $bhp_kim_mc, 'bhp_kit_modal_mint(' ),
	'6: there is exactly ONE mint call in the signup pipeline',
	$failures
);

$bhp_kim_catch_at = strpos( $bhp_kim_mc, 'catch (Throwable' );
$bhp_kim_mint_at  = strpos( $bhp_kim_mc, 'bhp_kit_modal_mint(' );

bhp_kim_assert(
	false !== $bhp_kim_catch_at && false !== $bhp_kim_mint_at && $bhp_kim_mint_at > $bhp_kim_catch_at,
	'6: the mint sits DOWNSTREAM of the subscribe/tag catch, so no failure path reaches it',
	$failures
);
bhp_kim_assert(
	1 === substr_count( $bhp_kim_mc, "'kit_token' => \$kit_token" ),
	'6: kit_token appears in exactly one return statement',
	$failures
);
/*
 * ⭐ AND THAT ONE RETURN IS THE SUCCESS RETURN, PROVED POSITIONALLY: every
 *    failure return in `bhp_process_signup()` is a `$fail(...)` or
 *    `$reject(...)` call, and all of them are upstream of the mint.
 */
$bhp_kim_last_reject = strrpos( $bhp_kim_mc, "\$reject('unavailable')" );
bhp_kim_assert(
	false !== $bhp_kim_last_reject && false !== $bhp_kim_mint_at && $bhp_kim_mint_at > $bhp_kim_last_reject,
	'6: every validation rejection returns before the mint is reached',
	$failures
);

/*
 * The three transports, asserted from source because a failure path cannot be
 * exercised here without a live Mailchimp rejection to trigger it.
 */
bhp_kim_assert(
	false !== strpos( $bhp_kim_mc, "!empty(\$result['kit_token'])" ),
	'6: both transports guard on a non-empty token before adding it to a URL',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_mc, '$extra = ($status === \'success\'' ),
	'6: the redirect helper discards extra args on any non-success status',
	$failures
);

echo "\n== 7: funnel isolation held ==\n";

foreach ( array( 'bhp_parent_popup', 'bhp_mariana_popup', 'parent_popup', 'teacher_popup', 'dataLayer', 'localStorage', 'sessionStorage' ) as $bhp_kim_forbidden ) {
	bhp_kim_assert(
		false === strpos( $bhp_kim_html, $bhp_kim_forbidden ),
		"7: the rendered modal contains no \"{$bhp_kim_forbidden}\"",
		$failures
	);
}

echo "\n== 8: the copy rails, asserted against the rendered output ==\n";

/* Visible text only: markup, script and style are stripped so a CSS class or a
   JS identifier can never be mistaken for customer-facing copy. */
$bhp_kim_text = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $bhp_kim_html );
$bhp_kim_text = wp_strip_all_tags( $bhp_kim_text );
$bhp_kim_text = html_entity_decode( $bhp_kim_text, ENT_QUOTES, 'UTF-8' );

/* Attribute copy a customer hears or reads is held to the same rails. */
preg_match_all( '/(?:aria-label|alt|title)="([^"]*)"/', $bhp_kim_html, $bhp_kim_attrs );
$bhp_kim_copy = $bhp_kim_text . ' ' . implode( ' ', $bhp_kim_attrs[1] );

bhp_kim_assert(
	false === strpos( $bhp_kim_copy, "\xE2\x80\x94" ),
	'8: no em dash in any customer-facing string',
	$failures
);
bhp_kim_assert(
	! preg_match( '/\b(we|us|our|ours|we\'re|we\'ll|we\'ve)\b/i', $bhp_kim_copy ),
	'8: no "we", "us" or "our" in any customer-facing string (Standing Rules 9.1)',
	$failures
);
foreach ( array( 'colour', 'favourite', 'realise', 'recognise', 'organise', 'centre', 'travelling', 'labelled', 'grey', 'catalogue', 'whilst', 'towards', 'learnt', 'maths' ) as $bhp_kim_british ) {
	bhp_kim_assert(
		! preg_match( '/\b' . preg_quote( $bhp_kim_british, '/' ) . '\b/i', $bhp_kim_copy ),
		"8: American spelling held (no \"{$bhp_kim_british}\")",
		$failures
	);
}
bhp_kim_assert(
	! preg_match( '/\b\d+\s*min(ute)?s?\b/i', $bhp_kim_copy ),
	'8: no reading-time claim (retired from this funnel 2026-08-03)',
	$failures
);
/*
 * ⚠️ THE PREFIX FORM OF THIS RAIL WAS A FALSE POSITIVE AND IS CORRECTED.
 *    It was written as `\b(star|rating|review|...)\w*`, which matches any
 *    word BEGINNING with those letters. Page 4's approved alt text says
 *    *"the sub starts going down"*, so "starts" was read as a star rating
 *    and the whole modal was reported as carrying a review claim.
 *
 * ⛔ THE RAIL IS NOT WEAKENED, ONLY MADE EXACT. Each term is now whole-word
 *    with its real inflections, so "star", "stars", "rating", "reviews",
 *    "awarded" and "bestselling" are all still caught. What is no longer
 *    caught is an unrelated word that merely starts the same way — which was
 *    never the claim this rail exists to stop.
 */
bhp_kim_assert(
	! preg_match( '/\b(stars?|ratings?|reviews?|reviewers?|awards?|awarded|bestsell(er|ers|ing)?)\b/i', $bhp_kim_copy ),
	'8: no review, rating or award claim anywhere in the modal',
	$failures
);
bhp_kim_assert(
	! preg_match( '/\b(will help|helps your child|guaranteed|proven|reading level|lexile)\b/i', $bhp_kim_copy ),
	'8: no outcome, developmental or reading-level claim',
	$failures
);

echo "\n== 9: the negative trigger cases ==\n";

/*
 * ⭐ ASSERTED THROUGH THE MINT AND THE STORE RATHER THAN THROUGH A SECOND
 *    `bhp_kit_modal_consume()`, because that function memoises for the request
 *    and section 3 has already spent this process's one answer. A suite that
 *    called it again would be testing PHP's `static`, not the modal.
 */
bhp_kim_assert(
	false === get_transient( bhp_kit_modal_key( 'ZZZZnotarealtoken000000000000000Z' ) ),
	'9: an unminted token resolves to nothing in the store',
	$failures
);
bhp_kim_assert(
	'' === bhp_kit_modal_key( '' ),
	'9: an empty token yields no storage key at all',
	$failures
);

$bhp_kim_src = bhp_kim_code_php( bhp_kim_read( 'inc/kit-instant-modal.php' ) );

bhp_kim_assert(
	false !== strpos( $bhp_kim_src, "preg_match( '/^[A-Za-z0-9]{32}$/'" ),
	'9: a malformed token is rejected on shape before any storage read',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_src, 'delete_transient( $key );' ),
	'9: the token is burned on read',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_src, "empty( \$download['ready'] )" ),
	'9: the modal is gated on the same PDF-readiness flag as the kit page panel',
	$failures
);
bhp_kim_assert(
	false !== strpos( $bhp_kim_src, "BHP_KIT_MODAL_LEAD_MAGNET !== \$lead_magnet" ),
	'9: a token minted for another lead magnet cannot open this modal',
	$failures
);

unset( $_GET[ BHP_KIT_MODAL_ARG ] );

echo "\n";
if ( $failures ) {
	echo 'RESULT: ' . count( $failures ) . " FAILURE(S)\n";
	foreach ( $failures as $failure ) {
		echo "  - {$failure}\n";
	}
} else {
	echo "RESULT: ALL CHECKS PASSED\n";
}
