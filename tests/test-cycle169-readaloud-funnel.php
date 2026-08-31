<?php
/**
 * CYCLE169-LD-READALOUD-FUNNEL — the /gallery/ rebuild. Theme 1.19.325
 * (2026-08-29). STAGING ONLY.
 *
 * Founder rulings, carrier items 480 and 481, relayed in the build brief:
 * the 1.19.319 build of this page was REJECTED as final — *"a photo wall, not
 * a funnel"* — and read-alouds are currently FREE.
 *
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Three of the things this page does are
 *    genuinely dangerous, and they are sections 4, 5 and 6 below. Everything
 *    else is ordinary regression cover.
 *
 *    · **Section 4 — the placeholders.** The page ships with unwritten copy.
 *      The whole safety of that rests on the placeholders being unmistakable.
 *      If a later pass "tidies" the placeholder styling or, worse, fills a slot
 *      with invented founder prose, this suite must be the thing that screams.
 *    · **Section 5 — funnel isolation.** This page feeds the TEACHER funnel.
 *      One stray parent-funnel key here contaminates the segmentation the whole
 *      company's email rests on.
 *    · **Section 6 — no price.** There is no price for a read-aloud. A figure
 *      appearing on this page would be a fabricated commercial term.
 *
 * ⛔ THE COUNTERS ARE IN $GLOBALS, NOT `global $x`. `wp eval-file` includes this
 *    file INSIDE A FUNCTION, so a top-level variable is that function's LOCAL
 *    and `global $x` binds a different, empty slot — which prints
 *    "PASS: 0 FAIL: 0 / ALL PASS" over a visibly failing run. That happened for
 *    real on 2026-08-29 (finding F8 of the 1.19.319 candidate) and is designed
 *    around here rather than rediscovered.
 *
 * ⛔ THIS SUITE WRITES NOTHING. It reads functions, reads the two stylesheets,
 *    reads the template source and renders the page through the WordPress
 *    front-end loader. It never deletes an option, never touches a post, and
 *    never calls Mailchimp. F8 of the 1.19.319 candidate was a test that ate
 *    live data; that is not repeated.
 *
 * Run: wp eval-file tests/test-cycle169-readaloud-funnel.php --user=1
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assert.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Message.
 */
function bhp_rf_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_rf_pass'] ) ) {
		$GLOBALS['bhp_rf_pass'] = 0;
		$GLOBALS['bhp_rf_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_rf_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_rf_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * Section header.
 *
 * @param string $t Title.
 */
function bhp_rf_section( $t ) {
	echo "\n== {$t} ==\n";
}

echo "CYCLE169 — read-aloud funnel (/gallery/ rebuild), theme 1.19.325\n";
echo str_repeat( '=', 72 ) . "\n";

$bhp_theme    = get_template_directory();
$bhp_tpl      = (string) @file_get_contents( $bhp_theme . '/page-gallery.php' );
$bhp_inc      = (string) @file_get_contents( $bhp_theme . '/inc/gallery-page.php' );
$bhp_css      = (string) @file_get_contents( $bhp_theme . '/style.css' );
$bhp_min      = (string) @file_get_contents( $bhp_theme . '/style.min.css' );

/* ------------------------------------------------------------------------ */
bhp_rf_section( '1. The funnel helpers exist and are callable' );

foreach ( array(
	'bhp_readaloud_funnel_cta',
	'bhp_readaloud_funnel_copy_slots',
	'bhp_readaloud_funnel_render_slot',
	'bhp_readaloud_funnel_show_pricing',
	'bhp_gallery_sections',
) as $fn ) {
	bhp_rf_assert( function_exists( $fn ), "{$fn}() is defined" );
}

/* ------------------------------------------------------------------------ */
bhp_rf_section( '2. The booking CTA — founder-ruled label, mailto route' );

$cta = bhp_readaloud_funnel_cta();
bhp_rf_assert( is_array( $cta ), 'the CTA resolves to an array' );
bhp_rf_assert( 'andrew@braveheartspublishing.com' === $cta['email'], 'the address is the one Andrew gave out himself' );
bhp_rf_assert( 0 === strpos( $cta['href'], 'mailto:' ), '⭐ IT IS A mailto:, NOT A HALF-BUILT FORM' );
bhp_rf_assert( false !== strpos( $cta['href'], 'subject=' ), 'the subject line is pre-filled so the mail files itself' );
bhp_rf_assert( false === strpos( $cta['href'], 'body=' ), '⛔ THE BODY IS NOT PREFILLED — no words are put in a stranger\'s mouth' );
bhp_rf_assert( 'Book a FREE read-aloud' === $cta['label'], '⭐ THE LABEL IS ANDREW\'S OWN WORDING, CAPITALS AND ALL (item 481)' );
bhp_rf_assert( 2 === substr_count( $bhp_tpl, "data-readaloud-cta=" ), 'exactly TWO booking CTAs render — hero and educators' );
bhp_rf_assert( false !== strpos( $bhp_tpl, 'data-readaloud-cta="hero"' ), 'the hero CTA is present' );
bhp_rf_assert( false !== strpos( $bhp_tpl, 'data-readaloud-cta="educators"' ), 'the second CTA is present in the teacher/librarian section' );

/* ------------------------------------------------------------------------ */
bhp_rf_section( '3. Section order is the founder-ruled order' );

$order = array(
	'readaloud-funnel__hero',
	'readaloud-funnel__founder',
	'readaloud-funnel__gallery',
	'readaloud-funnel__educators',
	'readaloud-funnel__capture',
	'readaloud-funnel__pricing',
);
$last = -1;
foreach ( $order as $i => $cls ) {
	$pos = strpos( $bhp_tpl, $cls );
	bhp_rf_assert( false !== $pos, "the {$cls} section exists in the template" );
	bhp_rf_assert( false !== $pos && $pos > $last, "⭐ {$cls} comes after everything before it — the order is founder-ruled, not taste" );
	$last = false !== $pos ? $pos : $last;
}

/* ------------------------------------------------------------------------ */
bhp_rf_section( '4. ⛔ THE PLACEHOLDERS — the section that matters most' );

$slots = bhp_readaloud_funnel_copy_slots();
bhp_rf_assert( count( $slots ) >= 4, 'at least four copy slots are declared (3 founder + 1 educator)' );

$founder_slots = 0;
foreach ( $slots as $key => $slot ) {
	bhp_rf_assert( ! empty( $slot['label'] ), "slot {$key} declares a label" );
	bhp_rf_assert( ! empty( $slot['spec'] ), "slot {$key} declares what is coming" );
	bhp_rf_assert( ! empty( $slot['pending'] ), "⭐ slot {$key} is PENDING — no copy has been invented for it" );
	bhp_rf_assert( ! isset( $slot['copy'] ) || '' === $slot['copy'], "⛔ slot {$key} carries NO prose — the marketing lane owns the words" );
	if ( isset( $slot['section'] ) && 'founder' === $slot['section'] ) {
		++$founder_slots;
	}
}
bhp_rf_assert( 3 === $founder_slots, 'exactly three founder intro paragraph slots exist' );

ob_start();
bhp_readaloud_funnel_render_slot( 'founder-1' );
$rendered_slot = (string) ob_get_clean();

bhp_rf_assert( false !== strpos( $rendered_slot, 'PENDING READ-BACK' ), '⭐⭐ A PENDING SLOT RENDERS THE WORDS "PENDING READ-BACK"' );
bhp_rf_assert( false !== strpos( $rendered_slot, 'do not publish' ), '⭐⭐ AND THE WORDS "do not publish"' );
bhp_rf_assert( false !== strpos( $rendered_slot, 'bhp-copy-placeholder' ), 'it wears the placeholder class, so the ugly styling applies' );
bhp_rf_assert( false !== strpos( $rendered_slot, 'data-copy-slot="founder-1"' ), 'the slot is addressable by id for the lane landing real copy' );

/* An approved slot must stop looking like a placeholder. Proven with a filter
   rather than by editing the registry, so nothing is left behind. */
$bhp_rf_filter = function ( $s ) {
	$s['founder-1']['pending'] = false;
	$s['founder-1']['copy']    = 'APPROVED COPY SENTINEL';
	return $s;
};
add_filter( 'bhp_readaloud_funnel_copy_slots', $bhp_rf_filter );
ob_start();
bhp_readaloud_funnel_render_slot( 'founder-1' );
$approved_render = (string) ob_get_clean();
remove_filter( 'bhp_readaloud_funnel_copy_slots', $bhp_rf_filter );

bhp_rf_assert( false !== strpos( $approved_render, 'APPROVED COPY SENTINEL' ), 'an approved slot renders its approved copy' );
bhp_rf_assert( false === strpos( $approved_render, 'PENDING READ-BACK' ), '⭐ and the placeholder chrome disappears on its own — no second edit needed' );
bhp_rf_assert( false === strpos( $approved_render, 'bhp-copy-placeholder' ), 'the placeholder class is gone too' );

/* The registry must be back exactly as it was. A test that mutates live state
   is F8 all over again. */
$slots_after = bhp_readaloud_funnel_copy_slots();
bhp_rf_assert( ! empty( $slots_after['founder-1']['pending'] ), '⛔ THE FILTER WAS REMOVED — the registry is unchanged after this suite' );

/* The placeholder must be VISUALLY unmistakable, not merely semantically so. */
bhp_rf_assert( false !== strpos( $bhp_css, '.bhp-copy-placeholder' ), 'style.css carries the placeholder rules' );
bhp_rf_assert( false !== strpos( $bhp_min, '.bhp-copy-placeholder' ), 'style.min.css carries them too — the built artefact is current' );
bhp_rf_assert( false !== strpos( $bhp_css, 'dashed #C62828' ), '⭐ the heavy dashed red rule is present — ugly on purpose' );
bhp_rf_assert( false !== strpos( $bhp_css, 'repeating-linear-gradient' ), '⭐ the hazard stripes are present' );
bhp_rf_assert( false !== strpos( $bhp_css, "'Courier New'" ), '⭐ monospace — it must not look like the brand, because it is not' );

/* ------------------------------------------------------------------------ */
bhp_rf_section( '5. ⛔⛔ FUNNEL ISOLATION — this page is the TEACHER funnel' );

bhp_rf_assert( false !== strpos( $bhp_tpl, "'teacher_adventure_toolkit'" ), '⭐ the educator lead-magnet key is used' );
bhp_rf_assert( false !== strpos( $bhp_tpl, "'educators'" ), '⭐ the educator audience type is used' );
bhp_rf_assert( false !== strpos( $bhp_tpl, 'template-parts/acquisition/lead-magnet-cta' ), '⭐⭐ IT REUSES THE SHIPPED CAPTURE PIPE — never a fork of it' );
bhp_rf_assert( false !== strpos( $bhp_tpl, "'source_page'" ), 'the page\'s own permalink travels into the SOURCE merge field' );

/*
 * ⚠ THE FIRST VERSION OF THIS CHECK GREPPED THE RAW TEMPLATE SOURCE AND FAILED
 *   ON ITS OWN DOCUMENTATION — the template's header comment NAMES the parent
 *   tokens in order to say they are absent, and a source grep cannot tell a
 *   prohibition from an occurrence. Recorded rather than silently fixed,
 *   because the naive version of this test would have read as a real funnel
 *   contamination and cost somebody an hour.
 *
 * ⭐ THE CORRECTED CHECK RUNS ON CODE WITH COMMENTS STRIPPED, which is what the
 *   assertion always meant. `token_get_all()` is used rather than a regex
 *   because a regex cannot reliably tell a comment from a string containing
 *   `/*`.
 */
$bhp_tpl_code = '';
foreach ( token_get_all( $bhp_tpl ) as $tok ) {
	if ( is_array( $tok ) && in_array( $tok[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
		continue;
	}
	$bhp_tpl_code .= is_array( $tok ) ? $tok[1] : $tok;
}

foreach ( array(
	'reluctant_reader_adventure_kit',
	'adventure_kit_parent',
	'bhp_parent_popup',
	'parent_popup',
	'adventure-kit-thank-you',
	'mariana_trench_parent_guide',
) as $parent_token ) {
	bhp_rf_assert( false === strpos( $bhp_tpl_code, $parent_token ), "⛔ NO PARENT-FUNNEL TOKEN '{$parent_token}' in this template's CODE (comments stripped)" );
}

/* The tags this page will actually produce, resolved through the real filter
   rather than assumed — and asserted IDENTICAL to the educator landing page's,
   because the brief said match it exactly. */
if ( function_exists( 'bhp_get_mailchimp_signup_tags' ) ) {
	$mine     = bhp_get_mailchimp_signup_tags( 'lead_magnet', 'educators', 'teacher_adventure_toolkit', home_url( '/gallery/' ) );
	$educator = bhp_get_mailchimp_signup_tags( 'lead_magnet', 'educators', 'teacher_adventure_toolkit', home_url( '/teachers/' ) );
	bhp_rf_assert( in_array( 'Audience: Educator', $mine, true ), '⭐ the signup is tagged Audience: Educator' );
	bhp_rf_assert( in_array( 'Adventure Learning Toolkit', $mine, true ), 'and with the toolkit tag' );
	bhp_rf_assert( ! in_array( 'Audience: Parent/Grandparent', $mine, true ), '⛔ AND NOT with any parent audience tag' );
	bhp_rf_assert( $mine === $educator, '⭐⭐ THE TAG SET IS IDENTICAL TO THE EDUCATOR LANDING PAGE\'S — matched exactly, as briefed' );
}

/* ------------------------------------------------------------------------ */
bhp_rf_section( '6. ⛔⛔ NO PRICE. NONE EXISTS (item 481)' );

bhp_rf_assert( false === bhp_readaloud_funnel_show_pricing(), '⭐⭐ THE PRICING SLOT IS GATED OFF BY DEFAULT' );
bhp_rf_assert( false !== strpos( $bhp_tpl, 'readaloud-funnel__pricing' ), 'the structural slot exists so a future ruling has somewhere to land' );
bhp_rf_assert( false !== strpos( $bhp_css, '.readaloud-funnel__pricing[hidden]' ), 'and it is hidden by stylesheet as well as by attribute' );

/* The rendered page must carry no figure a visitor could read as a fee. */
$rendered = '';
$page     = get_page_by_path( 'gallery' );
if ( $page instanceof WP_Post ) {
	$resp = wp_remote_get( get_permalink( $page->ID ), array( 'timeout' => 30, 'sslverify' => false ) );
	if ( ! is_wp_error( $resp ) ) {
		$rendered = (string) wp_remote_retrieve_body( $resp );
	}
}
if ( '' !== $rendered ) {
	/* Strip the shared header/footer before looking for currency: the site nav
	   and footer legitimately carry shop links, and this assertion is about the
	   PAGE, not the chrome around it. */
	$body_only = $rendered;
	if ( preg_match( '/<div class="readaloud-funnel">(.*?)<\/div>\s*<footer/s', $rendered, $m ) ) {
		$body_only = $m[1];
	}
	bhp_rf_assert( ! preg_match( '/\$\s*\d/', $body_only ), '⭐⭐ NO DOLLAR FIGURE ANYWHERE IN THE FUNNEL BODY' );
	foreach ( array( 'per hour', 'per session', 'my fee', 'speaking fee', 'booking fee', 'honorarium', 'rate card' ) as $fee_word ) {
		bhp_rf_assert( false === stripos( $body_only, $fee_word ), "⛔ the fee phrase '{$fee_word}' does not appear" );
	}
	/*
	 * ⚠ THE FIRST VERSION ASSERTED THE STRING "Booking details" WAS ABSENT FROM
	 *   THE HTML. That was the wrong assertion and it failed correctly: a
	 *   `hidden` section IS in the document, it is simply not rendered and not
	 *   exposed to assistive technology. The right assertion is that the node
	 *   carries the gate, which is what is checked now — and the "a reader
	 *   cannot see it" half is a BROWSER measurement, taken in the QA pass, not
	 *   something a source check can honestly claim.
	 */
	bhp_rf_assert( (bool) preg_match( '/id="readaloud-funnel-pricing"[^>]*\bhidden\b/s', $rendered ), '⭐⭐ THE PRICING SECTION RENDERS WITH THE `hidden` ATTRIBUTE' );
	bhp_rf_assert( false !== strpos( $rendered, 'data-readaloud-pricing="off"' ), '⭐ and it declares itself OFF, so the state is readable from the DOM' );

	bhp_rf_section( '7. The rendered page — funnel shape, gallery intact, voice' );

	bhp_rf_assert( false !== strpos( $rendered, 'Book a FREE read-aloud' ), '⭐ the founder-ruled CTA label is on the rendered page' );
	bhp_rf_assert( 2 === substr_count( $rendered, 'Book a FREE read-aloud' ), 'and it appears exactly twice' );
	bhp_rf_assert( false !== strpos( $rendered, 'mailto:andrew@braveheartspublishing.com' ), 'the mailto route renders' );
	bhp_rf_assert( false !== strpos( $rendered, 'PENDING READ-BACK' ), '⭐⭐ THE PLACEHOLDERS ARE VISIBLE AS PLACEHOLDERS ON THE LIVE PAGE' );

	/* The gallery is approved and must be reused byte-for-byte. */
	bhp_rf_assert( 3 === substr_count( $rendered, 'author-visits-gallery__item' ), '⭐ all three approved photographs still render' );
	bhp_rf_assert( 3 === substr_count( $rendered, 'Adams Elementary, August 28, 2026' ), '⭐ all three approved captions are unchanged' );
	bhp_rf_assert( false !== strpos( $rendered, 'assets/img/read-alouds/adams-elementary-read-aloud-group.jpg' ), 'the group photograph is the same asset' );
	bhp_rf_assert( false !== strpos( $rendered, 'assets/img/read-alouds/adams-elementary-signed-books.jpg' ), 'the signed-books photograph is the same asset' );

	/* Nothing on this page may name a child or a librarian. */
	bhp_rf_assert( false === stripos( $body_only, 'librarian, ' ), 'no librarian is named' );
} else {
	bhp_rf_assert( false, '⚠ the rendered page could not be fetched — sections 6b/7 did NOT run, and that is a FAILURE, not a skip' );
}

/* ------------------------------------------------------------------------ */
bhp_rf_section( '7b. §9.1 — no company "we" in THIS TEMPLATE\'S OWN WORDS' );

/*
 * ⚠ THE FIRST VERSION OF THIS CHECK RAN OVER THE WHOLE RENDERED FUNNEL BODY AND
 *   FAILED ON A STRING THIS LANE DOES NOT OWN: the sitewide Find Your Adventure
 *   quiz modal renders inside the page wrapper and one of its ANSWER OPTIONS
 *   reads *"Readers at our organization"*. That "our" is the VISITOR's, not the
 *   company's, so §9.1 is not actually breached — but the failure is recorded in
 *   the deploy plan rather than deleted, because a future reader deserves to
 *   know the string is there and why it was left alone.
 *
 * ⭐ The corrected check is scoped to the words THIS TEMPLATE emits, which is
 *   the boundary of this lane's responsibility. It reads the literals out of the
 *   translation calls rather than the rendered page.
 */
$bhp_own_words = array();
if ( preg_match_all( "/(?:esc_html_e|esc_html__|esc_attr_e|esc_attr__|_e|__)\(\s*'((?:[^'\\\\]|\\\\.)*)'/s", $bhp_tpl, $lit ) ) {
	$bhp_own_words = $lit[1];
}
bhp_rf_assert( count( $bhp_own_words ) >= 10, 'the template\'s own visible strings were extracted for checking' );
foreach ( $bhp_own_words as $s ) {
	$clean = stripslashes( $s );
	bhp_rf_assert( ! preg_match( '/\b(we|our|ours|us)\b/i', $clean ), '⭐ §9.1 — no company "we"/"our"/"us" in: ' . $clean );
	bhp_rf_assert( false === strpos( $clean, '5–9' ) && false === strpos( $clean, '5-9' ), 'reading age is never 5–9 in: ' . $clean );
	bhp_rf_assert( false === stripos( $clean, 'colouring' ), '⭐ §24 American spelling in: ' . $clean );
}

/* ------------------------------------------------------------------------ */
bhp_rf_section( '8. Stylesheet hygiene' );

bhp_rf_assert( false !== strpos( $bhp_css, 'Version: 1.19.325' ), 'style.css declares 1.19.325' );
foreach ( array(
	'.readaloud-funnel__hero',
	'.readaloud-funnel__hero-title',
	'.readaloud-funnel__hero-cta',
	'.readaloud-funnel__btn',
	'.readaloud-funnel__pricing',
	'.bhp-copy-placeholder__flag',
) as $c ) {
	bhp_rf_assert( false !== strpos( $bhp_css, $c ), "style.css carries {$c}" );
	bhp_rf_assert( false !== strpos( $bhp_min, $c ), "style.min.css carries {$c} (the built artefact is current)" );
}

/* Every custom property the new block uses must actually be declared, or the
   rule silently resolves to nothing. */
if ( preg_match( '/READ-ALOUD FUNNEL — \/gallery\/(.*)$/s', $bhp_css, $blk ) ) {
	preg_match_all( '/var\((--[a-z0-9-]+)\)/i', $blk[1], $vars );
	foreach ( array_unique( $vars[1] ) as $v ) {
		bhp_rf_assert( false !== strpos( $bhp_css, $v . ':' ), "the custom property {$v} is actually declared somewhere in style.css" );
	}
	bhp_rf_assert( ! preg_match( '/^\s*\.(?!readaloud-funnel|bhp-copy-placeholder)[a-z]/mi', $blk[1] ), '⭐⭐ EVERY SELECTOR IN THE NEW BLOCK IS SCOPED — nothing leaks onto another page' );
}

/* ------------------------------------------------------------------------ */
$bhp_pass = isset( $GLOBALS['bhp_rf_pass'] ) ? (int) $GLOBALS['bhp_rf_pass'] : 0;
$bhp_fail = isset( $GLOBALS['bhp_rf_fail'] ) ? (int) $GLOBALS['bhp_rf_fail'] : 0;

echo "\n" . str_repeat( '=', 72 ) . "\n";
echo "PASS: {$bhp_pass}   FAIL: {$bhp_fail}\n";
/* A run that asserted nothing at all is a FAILURE, not a pass. */
echo ( 0 === $bhp_fail && $bhp_pass > 0 ) ? "ALL PASS\n" : "FAILURES PRESENT\n";
