<?php
/**
 * CTA-triggered signup modal suite — theme 1.19.224, 2026-08-13,
 * `CYCLE158-LD-SIGNUP-POPUP`.
 *
 * Run on staging (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-signup-modal.php --user=1
 *
 * WHAT THIS SUITE PROVES:
 *   - Every scroll-to-signup CTA on all five funnel templates carries a
 *     modal-open hook, AND still carries its original `href="#free"` anchor
 *     and its original `data-*-free-cta` hook, so the no-JS fallback and the
 *     existing funnel-structure suite both survive.
 *   - Each page renders exactly one modal, whose id every one of that page's
 *     CTAs points at, gated on the same readiness flag as the inline panel.
 *   - The modal renders the CENTRAL signup-form template part and does not
 *     re-implement submission: no second endpoint, no AJAX, no duplicated
 *     nonce/validation/Mailchimp logic anywhere in the new files.
 *   - The inline `#free` capture panel still renders on all five pages.
 *   - Mailchimp tag sets are byte-identical to the inline panel for all five
 *     lead magnets, asserted by CALLING the real filter, not by reading it.
 *   - Funnel isolation held: the new files touch no funnel storage prefix.
 *   - The modal is not an automatic popup — no timer, scroll or exit trigger
 *     and no `data-bhp-popup` attribute that would make mariana-popup.js
 *     adopt it.
 *   - Collision control: the modal wears `.mariana-popup`, which both
 *     existing overlay engines already treat as an active overlay.
 *   - (1.19.224, section 12) Each modal carries the front cover of its OWN
 *     lead magnet, resolved from files that exist in the theme, with real alt
 *     text; the wholesale guide — which has no PDF on either environment —
 *     has no cover and cannot acquire one by accident; no copy was removed to
 *     meet the above-the-fold requirement; and every compaction selector is
 *     scoped to the variant so the other three popups are untouched.
 *
 * WHAT IT DOES NOT PROVE, stated so no one over-reads a PASS:
 *   It is a PHP + source-level suite, not a browser. It cannot observe a
 *   dialog painting, focus landing in an input, a focus trap holding, an ESC
 *   keypress, a mobile keyboard, or a dataLayer push. Those are browser-QA
 *   claims and are recorded separately, with screenshots, in the release
 *   handoff. It also cannot prove a Mailchimp contact was created — staging
 *   has MC4WP disconnected by design.
 *
 * It touches no post, no option, no product and no WooCommerce record.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_sm_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_sm_read( $relative ) {
	$path = get_template_directory() . '/' . ltrim( $relative, '/' );
	if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
		return '';
	}
	return (string) file_get_contents( $path );
}

/**
 * ⭐ COMMENTS ARE STRIPPED BEFORE EVERY BEHAVIOURAL ASSERTION, AND THIS IS
 *    THE MOST IMPORTANT HELPER IN THE FILE.
 *
 * The first run of this suite reported ten failures. NINE of them were the
 * suite reading its own subject's PROSE: `signup-modal.php`'s docblock says
 * the words `data-bhp-popup`, `bhp_parent_popup_*`, `bhp_mariana_popup_*`
 * and `bhp_process_signup()` in order to explain that the file does NOT use
 * them, and a naive `strpos()` cannot tell an explanation from a use. A
 * negative assertion that a well-documented file can never pass is worse
 * than no assertion: it trains a reader to expect red.
 *
 * (The tenth failure was real — a fourth `href="#free"` anchor on the
 * retailers page with no free-CTA hook, which the hook-based inventory
 * missed entirely. That is why the section-1 assertions below key off the
 * ANCHOR rather than off the hook.)
 *
 * PHP is tokenised rather than regex-stripped, because `href="#free"` starts
 * with a `#` and any regex that treats `#` as a line comment destroys the
 * exact string this suite has to count.
 */
function bhp_sm_code_php( $src ) {
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

/**
 * JS: block comments removed, then any line that is entirely a `//` or `*`
 * comment. Deliberately conservative — it never touches a trailing comment
 * on a code line, so a token inside real code can still be found.
 */
function bhp_sm_code_js( $src ) {
	if ( '' === $src ) {
		return $src;
	}
	$src   = preg_replace( '#/\*.*?\*/#s', '', $src );
	$lines = preg_split( '/\R/', $src );
	$keep  = array();
	foreach ( $lines as $line ) {
		$trimmed = ltrim( $line );
		if ( 0 === strpos( $trimmed, '//' ) || 0 === strpos( $trimmed, '*' ) ) {
			continue;
		}
		$keep[] = $line;
	}
	return implode( "\n", $keep );
}

/*
 * The five funnel templates, their modal id, their lead magnet, and the CTA
 * hook their own landing script binds. Retailers has two CTAs; the other four
 * have three. The counts are asserted rather than assumed, because a future
 * layout release that adds or removes a CTA must not silently leave one
 * scrolling while its siblings open the dialog — that inconsistency is the
 * exact defect this suite exists to catch.
 */
$bhp_sm_pages = array(
	'page-reluctant-reader-adventure-kit.php' => array(
		'label'    => 'Parent / Reluctant Reader Adventure Kit',
		'hook'     => 'data-parent-free-cta',
		'modal_id' => 'adventure-kit-modal',
		'magnet'   => 'reluctant_reader_adventure_kit',
		'audience' => 'parents_families',
		'ctas'     => 3,
		'anchors'  => 3,
		'ready_fn' => 'bhp_get_reluctant_reader_download',
	),
	'page-audience-educators.php'             => array(
		'label'    => 'Educators / Adventure Learning Toolkit',
		'hook'     => 'data-audience-free-cta',
		'modal_id' => 'educator-toolkit-modal',
		'magnet'   => 'teacher_adventure_toolkit',
		'audience' => 'educators',
		'ctas'     => 3,
		'anchors'  => 3,
		'ready_fn' => 'bhp_get_teacher_toolkit_download',
	),
	'page-audience-gift-buyers.php'           => array(
		'label'    => 'Gift Buyers / Meaningful Gift Guide',
		'hook'     => 'data-audience-free-cta',
		'modal_id' => 'gift-guide-modal',
		'magnet'   => 'meaningful_gift_guide',
		'audience' => 'gift_buyers',
		'ctas'     => 3,
		'anchors'  => 3,
		'ready_fn' => 'bhp_get_gift_guide_download',
	),
	'page-audience-organizations.php'         => array(
		'label'    => 'Organizations / Community Reading Kit',
		'hook'     => 'data-audience-free-cta',
		'modal_id' => 'org-reading-kit-modal',
		'magnet'   => 'community_reading_kit',
		'audience' => 'organizations',
		'ctas'     => 3,
		'anchors'  => 3,
		'ready_fn' => 'bhp_get_community_kit_download',
	),
	'page-audience-retailers.php'             => array(
		'label'    => 'Retailers / Wholesale Guide',
		'hook'     => 'data-audience-free-cta',
		'modal_id' => 'retailer-wholesale-guide-modal',
		'magnet'   => 'bookstore_wholesale_guide',
		'audience' => 'retailers',
		'ctas'     => 2,
		'anchors'  => 3,
		'ready_fn' => 'bhp_get_bookstore_guide_download',
	),
);

$bhp_sm_src = array();
foreach ( $bhp_sm_pages as $tpl => $meta ) {
	// Comments stripped: every assertion below is about code and markup.
	$bhp_sm_src[ $tpl ] = bhp_sm_code_php( bhp_sm_read( $tpl ) );
}
$bhp_sm_modal_php = bhp_sm_code_php( bhp_sm_read( 'template-parts/acquisition/signup-modal.php' ) );
$bhp_sm_modal_js  = bhp_sm_code_js( bhp_sm_read( 'assets/js/signup-modal.js' ) );
$bhp_sm_style     = bhp_sm_read( 'style.css' );
$bhp_sm_functions = bhp_sm_code_php( bhp_sm_read( 'functions.php' ) );

echo "=== 0. The new files exist and are non-trivial ===\n";

bhp_sm_assert( strlen( $bhp_sm_modal_php ) > 2000, '0: template-parts/acquisition/signup-modal.php exists', $failures );
bhp_sm_assert( strlen( $bhp_sm_modal_js ) > 4000, '0: assets/js/signup-modal.js exists', $failures );
foreach ( $bhp_sm_pages as $tpl => $meta ) {
	bhp_sm_assert( '' !== $bhp_sm_src[ $tpl ], "0: {$meta['label']} — template readable", $failures );
}

echo "\n=== 1. EVERY scroll CTA opens the modal, and NONE lost its fallback ===\n";

foreach ( $bhp_sm_pages as $tpl => $meta ) {
	$html = $bhp_sm_src[ $tpl ];

	/*
	 * ⭐ THE ASSERTION KEYS OFF THE ANCHOR, NOT OFF THE HOOK, AND THAT CHOICE
	 *    IS THE POINT OF THIS SECTION.
	 *
	 *    "Convert every CTA that scrolls to the signup panel" is a statement
	 *    about `href="#free"`. It is NOT a statement about
	 *    `data-*-free-cta`, which is an ANALYTICS hook that most — but
	 *    provably not all — of those anchors happen to carry. Inventorying by
	 *    the hook missed `page-audience-retailers.php`'s final-CTA "Get the
	 *    Wholesale Guide", which scrolled to #free and carried no hook at all.
	 *
	 *    So: find every `<a ... href="#free" ...>` in the template and require
	 *    that EACH ONE carries this page's modal-open hook. A future release
	 *    that adds a sixth CTA is caught by construction, whether or not
	 *    whoever adds it remembers the analytics hook.
	 */
	$anchors = array();
	preg_match_all( '/<a\b[^>]*href="#free"[^>]*>/i', $html, $anchors );
	$anchors     = $anchors[0];
	$anchor_n    = count( $anchors );
	$wired       = 0;
	$attributed  = 0;
	foreach ( $anchors as $anchor ) {
		if ( strpos( $anchor, 'data-bhp-signup-modal-open="' . $meta['modal_id'] . '"' ) !== false ) {
			$wired++;
		}
		if ( strpos( $anchor, 'data-bhp-signup-modal-source="' ) !== false ) {
			$attributed++;
		}
	}

	bhp_sm_assert(
		$anchor_n === $meta['anchors'],
		"1: {$meta['label']} — {$meta['anchors']} scroll-to-signup anchors found (found {$anchor_n})",
		$failures
	);
	bhp_sm_assert(
		$anchor_n > 0 && $wired === $anchor_n,
		"1: {$meta['label']} — EVERY #free anchor opens this page's modal ({$wired}/{$anchor_n})",
		$failures
	);
	bhp_sm_assert(
		$anchor_n > 0 && $attributed === $anchor_n,
		"1: {$meta['label']} — every #free anchor declares its own source label ({$attributed}/{$anchor_n})",
		$failures
	);

	/*
	 * NO-JS FALLBACK. Every one of those anchors must still point at #free —
	 * proven by the regex above, which matched on exactly that. Asserted
	 * separately here so a future edit that swaps `href` for a `button`
	 * cannot pass by making the regex match nothing.
	 */
	bhp_sm_assert(
		substr_count( $html, 'href="#free"' ) === $meta['anchors'],
		"1: {$meta['label']} — every CTA keeps href=\"#free\" for the no-JS path",
		$failures
	);

	/*
	 * The page's own analytics hook count is UNCHANGED by this release. A
	 * changed count would mean an existing event's volume moved.
	 */
	bhp_sm_assert(
		substr_count( $html, $meta['hook'] ) === $meta['ctas'],
		"1: {$meta['label']} — the {$meta['hook']} analytics-hook count is unchanged at {$meta['ctas']}",
		$failures
	);
}

echo "\n=== 2. The inline #free capture panel SURVIVES on every page ===\n";

foreach ( $bhp_sm_pages as $tpl => $meta ) {
	$html = $bhp_sm_src[ $tpl ];
	bhp_sm_assert(
		strpos( $html, 'id="free"' ) !== false,
		"2: {$meta['label']} — the #free section still renders",
		$failures
	);
	bhp_sm_assert(
		strpos( $html, "get_template_part('template-parts/acquisition/lead-magnet-cta'" ) !== false,
		"2: {$meta['label']} — the inline lead-magnet panel is still rendered",
		$failures
	);
}

echo "\n=== 3. Exactly ONE modal per page, correctly wired and correctly gated ===\n";

foreach ( $bhp_sm_pages as $tpl => $meta ) {
	$html = $bhp_sm_src[ $tpl ];

	bhp_sm_assert(
		substr_count( $html, "get_template_part('template-parts/acquisition/signup-modal'" ) === 1,
		"3: {$meta['label']} — exactly one signup modal is rendered",
		$failures
	);
	bhp_sm_assert(
		strpos( $html, "'id'                   => '" . $meta['modal_id'] . "'" ) !== false,
		"3: {$meta['label']} — the modal id matches what its CTAs point at",
		$failures
	);
	bhp_sm_assert(
		strpos( $html, "'lead_magnet'          => '" . $meta['magnet'] . "'" ) !== false,
		"3: {$meta['label']} — the modal carries the page's own lead magnet",
		$failures
	);
	bhp_sm_assert(
		strpos( $html, "'audience_type'        => '" . $meta['audience'] . "'" ) !== false,
		"3: {$meta['label']} — the modal carries the page's own audience type",
		$failures
	);
	/*
	 * THE READINESS GATE. The modal must be inside the same `$download['ready']`
	 * condition the inline panel uses. Without it, a page whose PDF is unset
	 * would show a "coming soon" panel and still open a working capture modal
	 * for a resource that does not exist.
	 */
	bhp_sm_assert(
		strpos( $html, "if (\$download['ready']) {" ) !== false,
		"3: {$meta['label']} — the modal is gated on \$download['ready']",
		$failures
	);
}

echo "\n=== 4. THE SUBMISSION HANDLER IS RENDERED, NEVER RE-IMPLEMENTED ===\n";

bhp_sm_assert(
	strpos( $bhp_sm_modal_php, "get_template_part('template-parts/acquisition/signup-form'" ) !== false,
	'4: the modal renders the central signup-form template part',
	$failures
);

/*
 * The negative half, and it is the one that protects the pixel-credited Lead
 * path. None of these may appear in either new file: a second endpoint, an
 * AJAX submission, a hand-rolled nonce, or a direct call into the Mailchimp
 * layer. The modal is a container; the form inside it is the shipped one.
 */
$bhp_sm_forbidden = array(
	'admin-ajax'          => 'a second AJAX endpoint',
	'XMLHttpRequest'      => 'a hand-rolled XHR submission',
	'fetch('              => 'a fetch() submission',
	'bhp_mailchimp_signup(' => 'a direct call into the Mailchimp handler',
	'wp_nonce_field'      => 'a duplicated nonce field',
	'bhp_process_signup'  => 'a duplicated submission processor',
);
foreach ( $bhp_sm_forbidden as $needle => $why ) {
	bhp_sm_assert(
		false === strpos( $bhp_sm_modal_php, $needle ) && false === strpos( $bhp_sm_modal_js, $needle ),
		"4: neither new file contains {$why}",
		$failures
	);
}

/*
 * The JS observes `submit`; it must never prevent it. A preventDefault() on
 * the form would break the POST/redirect/GET flow that every downstream lead
 * event and the Meta pixel's Lead attribution depend on.
 */
bhp_sm_assert(
	false === strpos( $bhp_sm_modal_js, "form.addEventListener('submit', function (event)" ),
	'4: the submit listener takes no event object, so it cannot preventDefault',
	$failures
);

echo "\n=== 5. Mailchimp tags are IDENTICAL for the modal and the inline panel ===\n";

/*
 * Asserted by CALLING the real filter with both contexts, not by reading the
 * callback. `lead_magnet_modal` is the modal's context; `lead_magnet` is the
 * inline panel's (set in lead-magnet-cta.php). A tag divergence here would
 * silently split a funnel's audience in Mailchimp.
 */
foreach ( $bhp_sm_pages as $tpl => $meta ) {
	$inline = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'lead_magnet', $meta['audience'], $meta['magnet'], home_url( '/' ) );
	$modal  = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'lead_magnet_modal', $meta['audience'], $meta['magnet'], home_url( '/' ) );
	bhp_sm_assert(
		$inline === $modal && ! empty( $modal ),
		"5: {$meta['magnet']} — modal tags identical to inline panel tags (" . implode( ' | ', $modal ) . ')',
		$failures
	);
}

/*
 * And the one that would actually bite: the parent funnel's tag callback
 * branches on `$context === 'parent_popup'`. Confirm the modal's context does
 * NOT take that branch, so a landing-page capture is never mislabelled as a
 * popup capture.
 */
$bhp_sm_parent_modal = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'lead_magnet_modal', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_sm_assert(
	in_array( 'Source: Parent Landing Page', $bhp_sm_parent_modal, true ) && ! in_array( 'Source: Parent Popup', $bhp_sm_parent_modal, true ),
	'5: the parent modal tags as "Source: Parent Landing Page", never "Source: Parent Popup"',
	$failures
);

echo "\n=== 6. Funnel isolation held — no funnel storage prefix is touched ===\n";

foreach ( array( 'bhp_parent_popup', 'bhp_mariana_popup' ) as $prefix ) {
	bhp_sm_assert(
		false === strpos( $bhp_sm_modal_php, $prefix . '_' ) && false === strpos( $bhp_sm_modal_js, $prefix . '_' ),
		"6: neither new file reads or writes any {$prefix}_* key",
		$failures
	);
}
/*
 * The ONE shared key it does write is the frequency flag mariana-popup.js
 * already defines. Asserted positively so a future edit that renames it is
 * caught here rather than by an exit-intent popup that silently stacks.
 */
bhp_sm_assert(
	strpos( $bhp_sm_modal_js, "'bhp_popup_shown_session'" ) !== false,
	'6: the modal claims the shared session-frequency slot, suppressing exit-intent',
	$failures
);
bhp_sm_assert(
	strpos( bhp_sm_read( 'template-parts/acquisition/exit-intent-popup.php' ), "'bhp_popup_shown_session'" ) !== false,
	'6: the exit-intent popup still consults that same shared slot',
	$failures
);

echo "\n=== 7. It is NOT an automatic popup ===\n";

/*
 * `data-bhp-popup` is what mariana-popup.js binds. Its presence would put
 * this modal under a trigger engine, a 10-day dismissal cooldown and a
 * session cap — and would make it a lead-magnet popup, reversing the
 * 2026-07-19 one-popup ruling. It must be absent.
 */
bhp_sm_assert(
	false === strpos( $bhp_sm_modal_php, 'data-bhp-popup' ),
	'7: the modal does not carry data-bhp-popup, so the trigger engine ignores it',
	$failures
);
bhp_sm_assert(
	false === strpos( $bhp_sm_modal_php, 'data-popup-config' ),
	'7: the modal supplies no trigger config',
	$failures
);
foreach ( array( 'minDelay', 'scrollPct', 'mouseout', 'setTimeout' ) as $trigger_token ) {
	bhp_sm_assert(
		false === strpos( $bhp_sm_modal_js, $trigger_token ),
		"7: the controller contains no {$trigger_token} — nothing opens automatically",
		$failures
	);
}

echo "\n=== 8. Dialog semantics and collision control ===\n";

foreach ( array( 'role="dialog"', 'aria-modal="true"', 'aria-labelledby=', 'aria-describedby=', 'tabindex="-1"' ) as $needle ) {
	bhp_sm_assert(
		strpos( $bhp_sm_modal_php, $needle ) !== false,
		"8: the dialog declares {$needle}",
		$failures
	);
}
bhp_sm_assert(
	strpos( $bhp_sm_modal_php, 'class="mariana-popup mariana-popup--signup"' ) !== false,
	'8: the modal wears .mariana-popup, which both existing overlay engines already detect',
	$failures
);
bhp_sm_assert(
	strpos( bhp_sm_read( 'assets/js/mariana-popup.js' ), ".mariana-popup.is-open" ) !== false,
	'8: mariana-popup.js still defers to any open .mariana-popup',
	$failures
);
bhp_sm_assert(
	strpos( bhp_sm_read( 'assets/js/quiz-modal.js' ), ".mariana-popup.is-open" ) !== false,
	'8: quiz-modal.js still defers to any open .mariana-popup',
	$failures
);
bhp_sm_assert(
	strpos( $bhp_sm_modal_js, "anotherOverlayIsOpen" ) !== false,
	'8: the controller refuses to open over another overlay',
	$failures
);
// ESC, overlay click and focus return.
foreach ( array( "event.key === 'Escape'", 'data-bhp-signup-modal-overlay', 'lastFocused.focus' ) as $needle ) {
	bhp_sm_assert(
		strpos( $bhp_sm_modal_js, $needle ) !== false,
		"8: the controller implements {$needle}",
		$failures
	);
}

echo "\n=== 9. Analytics — new events added, existing events untouched ===\n";

foreach ( array( 'signup_modal_opened', 'signup_modal_closed', 'signup_modal_submit' ) as $evt ) {
	bhp_sm_assert(
		strpos( $bhp_sm_modal_js, "'" . $evt . "'" ) !== false,
		"9: {$evt} is pushed to the dataLayer",
		$failures
	);
}
bhp_sm_assert(
	strpos( $bhp_sm_modal_js, 'source_cta' ) !== false && strpos( $bhp_sm_modal_js, 'page_path' ) !== false,
	'9: the open event carries source_cta and page_path',
	$failures
);
/*
 * Everything must go through window.dataLayer and nothing else. Consent mode
 * is live; a direct gtag/fbq call from a new file would sit outside it.
 */
foreach ( array( 'gtag(', 'fbq(', 'ga(' ) as $needle ) {
	bhp_sm_assert(
		false === strpos( $bhp_sm_modal_js, $needle ),
		"9: no direct {$needle} call — everything goes through the dataLayer",
		$failures
	);
}
/*
 * The existing lead events are emitted by signup-form.php and nav.js. Assert
 * they are still there and that the modal's form carries the attributes that
 * drive them — the modal must not become an analytics blind spot.
 */
$bhp_sm_form = bhp_sm_read( 'template-parts/acquisition/signup-form.php' );
foreach ( array( 'data-bhp-impression-event="lead_form_view"', 'data-bhp-focus-event="lead_form_start"', 'lead_signup_success', 'signup_error' ) as $needle ) {
	bhp_sm_assert(
		strpos( $bhp_sm_form, $needle ) !== false,
		"9: signup-form.php still emits/declares {$needle}",
		$failures
	);
}

echo "\n=== 10. Assets, styling and the deploy artefact ===\n";

bhp_sm_assert(
	strpos( $bhp_sm_functions, "bhp_enqueue_signup_modal_assets" ) !== false,
	'10: the controller is enqueued by its own scoped function',
	$failures
);
foreach ( $bhp_sm_pages as $tpl => $meta ) {
	bhp_sm_assert(
		strpos( $bhp_sm_functions, "'" . $tpl . "'" ) !== false,
		"10: {$meta['label']} — its template is in an enqueue allowlist",
		$failures
	);
}
/*
 * No new stylesheet file. The RUNBOOK's deploy assertion counts exactly 10
 * `*.min.css` entries in the artefact; a new component stylesheet would make
 * that 12 and turn a correct build into a "failed" one.
 */
bhp_sm_assert(
	false === strpos( $bhp_sm_functions, 'signup-modal.css' ),
	'10: no new stylesheet is enqueued — the artefact min.css count is unchanged',
	$failures
);
foreach ( array( '.mariana-popup--signup', 'body.bhp-signup-modal-open', '--bhp-modal-vv-height' ) as $needle ) {
	bhp_sm_assert(
		strpos( $bhp_sm_style, $needle ) !== false,
		"10: style.css carries {$needle}",
		$failures
	);
}
/*
 * The variant must outrank WPConsent's shadow-root gear (z-index 9999), the
 * same problem the quiz modal already solved at 10000.
 */
bhp_sm_assert(
	strpos( $bhp_sm_style, '.mariana-popup--signup { z-index: 10000; }' ) !== false,
	'10: the signup variant sits above the consent gear at z-index 10000',
	$failures
);
/*
 * No new font, no new colour literal. Every value must come from an existing
 * token. A raw hex in the new block would mean a new colour was invented.
 */
$bhp_sm_block_start = strpos( $bhp_sm_style, 'CTA-TRIGGERED SIGNUP MODAL' );
/*
 * Bounded at the NEXT section banner, not by a character budget. A fixed
 * length silently spills into the block below as soon as this one is edited,
 * and then the assertion is measuring somebody else's CSS.
 */
$bhp_sm_block_end = false === $bhp_sm_block_start ? false : strpos( $bhp_sm_style, 'OVERNIGHT CONVERSION SPRINT', $bhp_sm_block_start );
$bhp_sm_block     = ( false === $bhp_sm_block_start || false === $bhp_sm_block_end )
	? ''
	: substr( $bhp_sm_style, $bhp_sm_block_start, $bhp_sm_block_end - $bhp_sm_block_start );
bhp_sm_assert(
	'' !== $bhp_sm_block && ! preg_match( '/#[0-9a-fA-F]{3,8}\b/', $bhp_sm_block ),
	'10: the new CSS block introduces no colour literal',
	$failures
);
bhp_sm_assert(
	'' !== $bhp_sm_block && false === strpos( $bhp_sm_block, 'font-family' ),
	'10: the new CSS block introduces no font',
	$failures
);

echo "\n=== 11. No fabricated claim was introduced ===\n";

/*
 * The never-invent rule, applied mechanically to the one file that carries
 * new customer-facing copy. Every string in signup-modal.php is either a
 * label or is passed in from a page template, and every page template's
 * strings are reused verbatim from the panel above it — but assert anyway.
 */
$bhp_sm_copy_forbidden = array( 'review', 'rating', 'stars', 'award', 'bestseller', 'best-seller', 'trusted by', 'parents love', 'teachers love', 'proven' );
foreach ( $bhp_sm_copy_forbidden as $needle ) {
	bhp_sm_assert(
		false === stripos( $bhp_sm_modal_php, $needle ),
		"11: the modal template contains no '{$needle}' claim",
		$failures
	);
}
// No number, count or urgency string in any modal copy argument.
foreach ( $bhp_sm_pages as $tpl => $meta ) {
	$start = strpos( $bhp_sm_src[ $tpl ], "get_template_part('template-parts/acquisition/signup-modal'" );
	$call  = false === $start ? '' : substr( $bhp_sm_src[ $tpl ], $start, 1400 );
	bhp_sm_assert(
		'' !== $call && ! preg_match( '/\b\d{2,}\b/', $call ),
		"11: {$meta['label']} — the modal copy carries no numeric claim",
		$failures
	);
}

echo "\n=== 12. ITERATION 2 — the cover, the fold and the box (1.19.224) ===\n";

/*
 * ⭐ WHAT THIS SECTION CAN AND CANNOT PROVE, STATED FIRST.
 *
 * It proves the cover files EXIST, are wired to the right magnets, carry real
 * alt text, and that the markup and the compaction CSS are present. It CANNOT
 * prove the submit button is above the fold, that the box has a visible margin
 * on four sides, or that the cover is in cache before the dialog opens —
 * those are measurements of a painted layout at a named viewport, they belong
 * to the browser harness, and they are recorded there with screenshots. A PASS
 * here is not evidence for requirement 1 or requirement 3.
 */

/*
 * The four magnets whose PDF is registered on both environments get a cover;
 * the wholesale guide has no PDF anywhere and must have NO cover entry, so a
 * future session cannot quietly invent one for it.
 */
$bhp_sm_covered = array(
	'reluctant_reader_adventure_kit' => 'reluctant-reader-adventure-kit-cover',
	'teacher_adventure_toolkit'      => 'adventure-learning-toolkit-cover',
	'meaningful_gift_guide'          => 'ultimate-gift-guide-cover',
	'community_reading_kit'          => 'community-reading-kit-cover',
);

bhp_sm_assert(
	function_exists( 'bhp_get_lead_magnet_cover' ),
	'12: bhp_get_lead_magnet_cover() exists',
	$failures
);

foreach ( $bhp_sm_covered as $magnet => $slug ) {
	$cover = function_exists( 'bhp_get_lead_magnet_cover' ) ? bhp_get_lead_magnet_cover( $magnet ) : array();

	bhp_sm_assert(
		! empty( $cover['url'] ) && ! empty( $cover['fallback'] ),
		"12: {$magnet} — resolves a cover (webp + fallback)",
		$failures
	);
	bhp_sm_assert(
		! empty( $cover['url'] ) && false !== strpos( $cover['url'], $slug . '.webp' )
			&& ! empty( $cover['fallback'] ) && false !== strpos( $cover['fallback'], $slug . '.png' ),
		"12: {$magnet} — points at its OWN magnet's cover files ({$slug})",
		$failures
	);
	/*
	 * The files themselves, on disk, in the theme. A URL that resolves in PHP
	 * and a file that ships in the deploy artefact are different claims, and
	 * this is the one that catches a ZIP built before the assets were added.
	 */
	foreach ( array( 'webp', 'png' ) as $ext ) {
		$path = get_template_directory() . '/assets/images/lead-magnets/' . $slug . '.' . $ext;
		bhp_sm_assert(
			file_exists( $path ) && filesize( $path ) > 1000,
			"12: {$slug}.{$ext} is present in the theme and non-trivial",
			$failures
		);
	}
	// Real alt text, naming a real document. Never empty, never "image".
	bhp_sm_assert(
		! empty( $cover['alt'] ) && strlen( $cover['alt'] ) > 20 && false === stripos( $cover['alt'], 'placeholder' ),
		"12: {$magnet} — alt text names the document (\"" . ( isset( $cover['alt'] ) ? $cover['alt'] : '' ) . '")',
		$failures
	);
	bhp_sm_assert(
		isset( $cover['width'], $cover['height'] ) && (int) $cover['width'] > 0 && (int) $cover['height'] > 0,
		"12: {$magnet} — intrinsic width/height are declared, so the head row cannot reflow",
		$failures
	);
}

/*
 * ⛔ THE RETAILERS GATE, ASSERTED FROM BOTH ENDS. Its modal does not render
 *    (no PDF), and even if a future release rendered it, there is no cover to
 *    put in it. Inventing one would be a §3 violation, not a nicety.
 */
bhp_sm_assert(
	array() === ( function_exists( 'bhp_get_lead_magnet_cover' ) ? bhp_get_lead_magnet_cover( 'bookstore_wholesale_guide' ) : array( 'x' ) ),
	'12: bookstore_wholesale_guide has NO cover — the gate holds from the asset side too',
	$failures
);
bhp_sm_assert(
	array() === ( function_exists( 'bhp_get_lead_magnet_cover' ) ? bhp_get_lead_magnet_cover( 'not_a_real_magnet' ) : array( 'x' ) ),
	'12: an unknown magnet key resolves to no cover rather than to someone else\'s',
	$failures
);

// The markup: a <picture> with a webp source and an <img> that has real alt.
foreach ( array( '<picture class="signup-modal__cover">', 'type="image/webp"', 'signup-modal__head', 'signup-modal__intro', 'esc_attr($cover[\'alt\'])' ) as $needle ) {
	bhp_sm_assert(
		strpos( $bhp_sm_modal_php, $needle ) !== false,
		"12: the modal template renders {$needle}",
		$failures
	);
}
bhp_sm_assert(
	strpos( $bhp_sm_modal_php, 'data-bhp-cover-preload' ) !== false
		&& strpos( $bhp_sm_modal_js, 'data-bhp-cover-preload' ) !== false,
	'12: the cover is warmed from a data attribute the controller reads',
	$failures
);
bhp_sm_assert(
	strpos( $bhp_sm_modal_js, 'warmCover' ) !== false && strpos( $bhp_sm_modal_js, 'new Image()' ) !== false,
	'12: the controller warms the cover on first intent, not on page load',
	$failures
);
bhp_sm_assert(
	strpos( $bhp_sm_modal_js, 'ensureSubmitVisible' ) !== false
		&& strpos( $bhp_sm_modal_js, 'dialog.scrollTop' ) !== false,
	'12: the controller keeps the submit button in view when the visible box is short',
	$failures
);
/*
 * And it must scroll the DIALOG, never the page. `window.scrollTo` /
 * `scrollIntoView` in this file would move the visitor's position behind the
 * modal, which is the "the page moved on me" defect the release already
 * avoided once.
 */
foreach ( array( 'window.scrollTo', 'scrollIntoView' ) as $needle ) {
	bhp_sm_assert(
		false === strpos( $bhp_sm_modal_js, $needle ),
		"12: the controller never calls {$needle} — only the dialog's own scroll region moves",
		$failures
	);
}

/*
 * ⛔ COMPACTION MUST NOT HAVE REMOVED COPY. Every element the 1.19.223 modal
 *    rendered is still rendered. This is the assertion that stops a future
 *    "make it fit" pass from deleting the privacy line or the description
 *    instead of tightening them.
 */
foreach ( array(
	'component-heading__eyebrow' => 'the eyebrow',
	'mariana-popup__text'        => 'the description',
	'mariana-popup__trust'       => 'the trust line',
	"'privacy_text'"             => 'the privacy line',
	"'show_name'"                => 'the first-name field argument',
) as $needle => $what ) {
	bhp_sm_assert(
		strpos( $bhp_sm_modal_php, $needle ) !== false,
		"12: {$what} still renders — no copy was removed to meet the fold",
		$failures
	);
}
// The first-name field stays PRESENT and stays OPTIONAL.
bhp_sm_assert(
	strpos( $bhp_sm_modal_php, "'show_name'            => true," ) !== false
		&& strpos( $bhp_sm_modal_php, "'require_name'         => false," ) !== false,
	'12: the first-name field is still present and still optional',
	$failures
);

/*
 * The compaction CSS, and the two floors it must never cross:
 *   - inputs stay at 1rem (16px). Below it, iOS Safari zooms the page on
 *     focus and throws the visitor off the dialog as they start typing.
 *   - the box is bounded on BOTH axes against the real viewport, which is
 *     what gives requirement 3 its margin on all four sides.
 */
$bhp_sm_block_start_12 = strpos( $bhp_sm_style, 'CTA-TRIGGERED SIGNUP MODAL' );
$bhp_sm_block_end_12   = false === $bhp_sm_block_start_12 ? false : strpos( $bhp_sm_style, 'OVERNIGHT CONVERSION SPRINT', $bhp_sm_block_start_12 );
$bhp_sm_block_12       = ( false === $bhp_sm_block_start_12 || false === $bhp_sm_block_end_12 )
	? ''
	: substr( $bhp_sm_style, $bhp_sm_block_start_12, $bhp_sm_block_end_12 - $bhp_sm_block_start_12 );

/*
 * ⭐ COMMENTS ARE STRIPPED BEFORE THE SELECTOR SCAN BELOW, for the same reason
 *    bhp_sm_code_php() exists at the top of this file: a prose line that
 *    happens to end in a comma reads exactly like a selector list to a
 *    line-based scanner. The first draft of this section failed on the
 *    sentence "1. the subscribe button fully visible on open, with NO
 *    scrolling," — which is documentation, not CSS.
 */
$bhp_sm_block_12 = (string) preg_replace( '#/\*.*?\*/#s', '', $bhp_sm_block_12 );

foreach ( array( '.signup-modal__head', '.signup-modal__intro', '.signup-modal__cover', 'max-height: calc(100dvh' ) as $needle ) {
	bhp_sm_assert(
		'' !== $bhp_sm_block_12 && strpos( $bhp_sm_block_12, $needle ) !== false,
		"12: the signup block carries {$needle}",
		$failures
	);
}
bhp_sm_assert(
	'' !== $bhp_sm_block_12 && ! preg_match( '/acquisition-form__field input\s*\{[^}]*font-size:\s*0?\.\d+rem/s', $bhp_sm_block_12 ),
	'12: no rule in the signup block drops an input below 1rem (the iOS zoom floor)',
	$failures
);
/*
 * ⛔ EVERY SELECTOR IN THE BLOCK IS SCOPED. An unscoped `.mariana-popup__*`
 *    rule here would restyle the teacher popup, the parent popup and the
 *    exit-intent modal — three surfaces this release does not touch and
 *    `.claude/rules/funnels.md` keeps isolated.
 */
$bhp_sm_unscoped = array();
foreach ( preg_split( '/\R/', (string) $bhp_sm_block_12 ) as $line ) {
	$trimmed = trim( $line );
	if ( '' === $trimmed || 0 === strpos( $trimmed, '/*' ) || 0 === strpos( $trimmed, '*' ) ) {
		continue;
	}
	if ( ! preg_match( '/^[.\w][^{}@]*[,{]\s*$/', $trimmed ) ) {
		continue;
	}
	$selector = rtrim( $trimmed, " ,{\t" );
	if ( false === strpos( $selector, '.mariana-popup--signup' )
		&& false === strpos( $selector, 'body.bhp-signup-modal-open' ) ) {
		$bhp_sm_unscoped[] = $selector;
	}
}
bhp_sm_assert(
	empty( $bhp_sm_unscoped ),
	'12: every selector in the signup block is scoped to the variant (' . ( $bhp_sm_unscoped ? implode( ' | ', $bhp_sm_unscoped ) : 'all scoped' ) . ')',
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
