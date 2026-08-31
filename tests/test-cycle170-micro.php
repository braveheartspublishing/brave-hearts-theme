<?php
/**
 * CYCLE170-LD-MICRO — the micro-build. Theme 1.19.337 (2026-08-30).
 * STAGING ONLY. `wp eval-file` from the site root.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS SUITE IS FOR. Five founder-sealed changes, and each one has a
 *    QUIET failure mode — a state in which the page still renders happily and
 *    the thing the founder asked for is silently not there.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   1 · item 547 — THE NAV. Three quiet failures, not one. About can survive
 *       the filter (a menu whose URL has a trailing-slash or host variant);
 *       Read-Alouds can render UNSTACKED (the class applied and the spans
 *       missing, or vice versa); and — the one the founder actually named —
 *       a per-item font rule can creep back in and make the bar ragged again
 *       with nothing failing anywhere. ⭐ §1 IS THE DRIFT ASSERTION HE ASKED
 *       FOR: it reads the stylesheet and fails if ANY `.site-nav` item-level
 *       rule declares a font, a size, a weight, tracking or a casing, and if
 *       any of them re-grows a `content:` pseudo-label.
 *
 *   2 · items 545/546 — /positivity-news/. The quiet failure is the form
 *       drifting back BELOW the body copy in a later edit, and the gradient
 *       overlay being dropped while the photograph stays. §2 asserts DOM order
 *       and asserts the overlay's own rules exist.
 *
 *   3 · items 548/552 — /school-read-alouds/. The quiet failure is the
 *       carousel reappearing in TWO places (moved but not removed) or the
 *       pairing silently collapsing to one column at desktop. §3 asserts the
 *       carousel renders EXACTLY ONCE, and that it renders BEFORE the
 *       scheduler in the document.
 *
 *   4 · items 553/554 — THE OPT-IN. ⛔⛔ THE QUIET FAILURE HERE IS THE SERIOUS
 *       ONE: a consent control that is present in the markup and invisible on
 *       the page, which is exactly the "smuggled consent" the founder's own
 *       ruling rejected. §4 asserts it is checked BY DEFAULT, that its label
 *       is the sealed string character-exact, that it sits ABOVE the submit
 *       button in DOM order, and that NO rule in the stylesheet hides it.
 *
 *   5 · THE COUPON COUNTER. The quiet failures are a counter that stores more
 *       than a code and a date, and a pruner that never prunes. §5 exercises
 *       the pure pruner across its boundaries and asserts the record shape.
 *
 * ⛔ NOTHING HERE SENDS MAIL. §4 does not call the handler and does not POST.
 * ⛔ NOTHING HERE WRITES A REAL OPTION. §5 tests `bhp_coupon_apply_counter_prune()`,
 *    which is PURE — it takes a map and returns a map. The live option is
 *    neither read nor written by this file.
 * ⛔ NOTHING HERE CREATES A MAILCHIMP CONTACT. §4 does not call
 *    `bhp_readaloud_request_enroll_educator()`; the end-to-end opt-in run is a
 *    separate, deliberate QA act against the staging stub and is recorded in
 *    the deploy plan, not automated here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bhp_mic_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_mic_pass'] ) ) {
		$GLOBALS['bhp_mic_pass'] = 0;
		$GLOBALS['bhp_mic_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_mic_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_mic_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * Strip CSS comments before running any needle over the stylesheet.
 *
 * ⛔⛔ THIS HELPER EXISTS BECAUSE OF A THREE-INSTANCE FAILURE CLASS IN THIS
 *     CORPUS, and `CYCLE170-LD-CHAIN` §1a(i) named it as a class: a bare
 *     `strpos()` over a whole file CANNOT TELL A DECLARATION FROM A COMMENT
 *     ABOUT A DECLARATION. `bhp_bun_code_only()` in the bundle suite exists for
 *     the same reason; the weekpicker suite's `toLocaleDateString` assertion
 *     broke because the file DOCUMENTED the rule it was checking; and the chain
 *     lane's own CSS block regex was the third.
 *
 * ⭐ THIS SHEET IS THE WORST POSSIBLE CASE FOR THAT MISTAKE: it quotes its own
 *    superseded declarations verbatim, on purpose, in dozens of places. §1
 *    below asserts that certain declarations DO NOT EXIST, so without this
 *    helper every preserved history block would fail it.
 *
 * @param string $css Raw stylesheet.
 * @return string Stylesheet with every block comment replaced by a newline.
 */
function bhp_mic_css_code_only( $css ) {
	return (string) preg_replace( '#/\*.*?\*/#s', "\n", (string) $css );
}

/**
 * Every rule block whose selector list mentions `.site-nav` AND a
 * `.menu-item--` hook, i.e. every ITEM-LEVEL nav rule in the sheet.
 *
 * @param string $code Comment-stripped stylesheet.
 * @return array<int,array{selector:string,body:string}>
 */
function bhp_mic_nav_item_rules( $code ) {
	$out = array();
	if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $code, $m, PREG_SET_ORDER ) ) {
		return $out;
	}
	foreach ( $m as $rule ) {
		$sel = trim( preg_replace( '/\s+/', ' ', $rule[1] ) );
		if ( false === strpos( $sel, 'menu-item--' ) ) {
			continue;
		}
		/* Only the PRIMARY nav. `.footer-nav` and any other menu are out of
		   scope: item 547 is about the nav bar he was looking at. */
		if ( false === strpos( $sel, '.site-nav' ) && false === strpos( $sel, '.menu-item--' ) ) {
			continue;
		}
		$out[] = array( 'selector' => $sel, 'body' => $rule[2] );
	}
	return $out;
}

echo "\n=== CYCLE170-LD-MICRO · theme 1.19.337 ===\n";

/* ═══════════════════════════════════════════════════════════════════════════
 * 0 · THE VERSION PIN
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 0 · VERSION ===\n";

$bhp_mic_css_raw = (string) file_get_contents( get_template_directory() . '/style.css' );
$bhp_mic_css     = bhp_mic_css_code_only( $bhp_mic_css_raw );

preg_match( '/^Version:\s*(\S+)/m', $bhp_mic_css_raw, $bhp_mic_vm );
$bhp_mic_ver = isset( $bhp_mic_vm[1] ) ? $bhp_mic_vm[1] : '';
/*
 * ⭐ PIN MOVED TO 1.19.339 BY `CYCLE170-LD-FINAL2` (2026-08-30). This lane
 *    bumped the theme, so this lane owns the pin it broke — the same discipline
 *    `CYCLE170-LD-CHAIN` §6a set. The four stale pins belonging to OTHER lanes
 *    (ship-prep, triple, school-readaloud, cycle169-funnel) are STILL deliberately
 *    left alone; moving them would silently adopt somebody else's stale suite.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM rather than corrected in place, so
 *    the movement is visible and the 1.19.337 attribution to `CYCLE170-LD-MICRO`
 *    is not rewritten:
 *
 *      bhp_mic_assert( '1.19.337' === $bhp_mic_ver, "style.css declares 1.19.337, got '{$bhp_mic_ver}'" );
 */
bhp_mic_assert( '1.19.339' === $bhp_mic_ver, "style.css declares 1.19.339, got '{$bhp_mic_ver}'" );

/* ⭐ THE MINIFIED STYLESHEET MUST HAVE BEEN REBUILT. It embeds the source md5,
   so a stale `.min.css` is detectable rather than invisible — which matters
   because the site ENQUEUES the minified file, not the source. */
$bhp_mic_min = (string) file_get_contents( get_template_directory() . '/style.min.css' );
preg_match( '/source-md5:\s*([0-9a-f]{32})/', $bhp_mic_min, $bhp_mic_mm );
bhp_mic_assert(
	isset( $bhp_mic_mm[1] ) && $bhp_mic_mm[1] === md5( $bhp_mic_css_raw ),
	'style.min.css was rebuilt from THIS style.css (embedded source-md5 matches)'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE NAV — item 547. ⭐⭐ INCLUDING THE UNIFORMITY DRIFT ASSERTION.
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 1 · NAV (item 547) ===\n";

/* --- 1a · the PHP side --- */

bhp_mic_assert(
	function_exists( 'bhp_primary_nav_about_out_readalouds_in' ),
	'the nav filter exists'
);
bhp_mic_assert(
	has_filter( 'wp_nav_menu_objects', 'bhp_primary_nav_about_out_readalouds_in' ),
	'the nav filter is registered on wp_nav_menu_objects'
);
bhp_mic_assert(
	function_exists( 'bhp_readalouds_nav_aria_label' )
		&& has_filter( 'nav_menu_link_attributes', 'bhp_readalouds_nav_aria_label' ),
	'⭐ the stacked item has an aria-label filter - two block spans otherwise read as one run of text'
);

/*
 * ⛔ THE SCOPE CHECK, AND IT IS THE MOST IMPORTANT ASSERTION IN 1a.
 *    `wp_nav_menu_objects` fires for EVERY menu on the site. An unscoped
 *    REMOVAL filter would strip About out of any future menu anywhere. The
 *    three older filters beside it are unscoped, which is survivable for a
 *    label rewrite and is not survivable for a removal.
 */
$bhp_mic_fn_src = (string) file_get_contents( get_template_directory() . '/functions.php' );
$bhp_mic_fn_body = '';
if ( preg_match( '/function bhp_primary_nav_about_out_readalouds_in\s*\(.*?\n\}/s', $bhp_mic_fn_src, $bhp_mic_fm ) ) {
	$bhp_mic_fn_body = preg_replace( '#/\*.*?\*/#s', "\n", $bhp_mic_fm[0] );
}
bhp_mic_assert(
	'' !== $bhp_mic_fn_body && false !== strpos( $bhp_mic_fn_body, "'primary' !== \$location" ),
	'⛔ the nav filter is SCOPED to the primary location - an unscoped removal would strip About from every menu'
);

/* --- 1b · the rendered nav, on a real page --- */

$bhp_mic_nav_r = wp_remote_get( home_url( '/positivity-news/' ), array( 'timeout' => 45, 'sslverify' => false ) );
$bhp_mic_nav_b = wp_remote_retrieve_body( $bhp_mic_nav_r );

bhp_mic_assert( 200 === (int) wp_remote_retrieve_response_code( $bhp_mic_nav_r ), '/positivity-news/ is 200' );

$bhp_mic_navhtml = '';
if ( preg_match( '#<nav class="site-nav".*?</nav>#s', $bhp_mic_nav_b, $bhp_mic_nm ) ) {
	$bhp_mic_navhtml = $bhp_mic_nm[0];
}
bhp_mic_assert( '' !== $bhp_mic_navhtml, 'the primary nav element was found in the rendered page' );

bhp_mic_assert(
	0 === substr_count( $bhp_mic_navhtml, 'href="' . home_url( '/about/' ) . '"' ),
	'⭐⭐ item 547: ABOUT IS GONE from the primary nav'
);
bhp_mic_assert(
	1 === substr_count( $bhp_mic_navhtml, 'menu-item--read-alouds' ),
	'⭐ the Read-Alouds item carries its hook class exactly once'
);
bhp_mic_assert(
	false !== strpos( $bhp_mic_navhtml, '<span class="site-nav__label-line">Read</span><span class="site-nav__label-line">Alouds</span>' ),
	'⭐⭐ item 547: READ-ALOUDS IS STACKED - the two label-line spans, in order'
);
bhp_mic_assert(
	false !== strpos( $bhp_mic_navhtml, 'aria-label="Read-Alouds"' ),
	'⛔ the accessible name is the CANONICAL HYPHENATED form, not the split visible text'
);

/*
 * ⭐ ABOUT'S SLOT, MEASURED RATHER THAN ASSUMED. At 1.19.336 the stored menu
 *    order was Home · Blog · About · Books · Contact · Teacher's Guide ·
 *    Read-Alouds (verified first-hand with `wp menu item list`), with the
 *    theme's own Start Here injected after Home. So Read-Alouds must now render
 *    BEFORE the Books entry, which is where About was.
 */
$bhp_mic_ra_pos    = strpos( $bhp_mic_navhtml, 'menu-item--read-alouds' );
$bhp_mic_books_pos = strpos( $bhp_mic_navhtml, 'menu-item--adventure-books' );
bhp_mic_assert(
	false !== $bhp_mic_ra_pos && false !== $bhp_mic_books_pos && $bhp_mic_ra_pos < $bhp_mic_books_pos,
	'⭐ Read-Alouds took About\'s SLOT - it renders before the Adventure Books item'
);

/* --- 1c · ⭐⭐ THE UNIFORMITY DRIFT ASSERTION. Item 547: "this needs to always
       be uniform the font the style etc". THIS is the mechanism that makes that
       a rule rather than a wish. --- */

$bhp_mic_drift_props = array( 'font-family', 'font-size', 'font-weight', 'letter-spacing', 'text-transform', 'font-style' );
$bhp_mic_nav_rules   = bhp_mic_nav_item_rules( $bhp_mic_css );
$bhp_mic_drifted     = array();

foreach ( $bhp_mic_nav_rules as $bhp_mic_rule ) {
	foreach ( $bhp_mic_drift_props as $bhp_mic_prop ) {
		if ( preg_match( '/(^|[;{\s])' . preg_quote( $bhp_mic_prop, '/' ) . '\s*:/i', $bhp_mic_rule['body'] ) ) {
			$bhp_mic_drifted[] = $bhp_mic_prop . ' in {' . $bhp_mic_rule['selector'] . '}';
		}
	}
}

bhp_mic_assert(
	empty( $bhp_mic_drifted ),
	'⭐⭐ ITEM 547 UNIFORMITY: no per-item nav rule declares a font, size, weight, tracking, casing or style'
		. ( $bhp_mic_drifted ? ' - FOUND: ' . implode( ' | ', array_slice( $bhp_mic_drifted, 0, 4 ) ) : '' )
);

/*
 * ⛔ AND THE FOURTH-LABEL-SITE ASSERTION, kept from 1.19.303's finding. A
 *    `content:` string on a nav pseudo-element is a SECOND place a label can
 *    live, and it is the one where renaming the PHP leaves every desktop
 *    visitor reading the old word with nothing failing. `content: none` is
 *    fine — that is the neutralised rule, deliberately kept — a QUOTED string
 *    is not.
 */
$bhp_mic_pseudo_label = false;
foreach ( $bhp_mic_nav_rules as $bhp_mic_rule ) {
	if ( preg_match( '/content\s*:\s*[\'"][^\'"]+[\'"]/', $bhp_mic_rule['body'] ) ) {
		$bhp_mic_pseudo_label = true;
	}
}
bhp_mic_assert( ! $bhp_mic_pseudo_label, '⛔ no nav item rule carries a quoted `content:` pseudo-label - one label site only, in PHP' );

/* ⭐ THE THREE STACKED ITEMS SHARE ONE RULE, which is what makes them unable to
   drift apart. Asserted by counting the selector, not by reading the comment. */
bhp_mic_assert(
	1 === preg_match_all( '/\.site-nav \.menu-item--adventure-books > a,\s*\.site-nav \.menu-item--free-resources > a,\s*\.site-nav \.menu-item--read-alouds > a\s*\{\s*display: flex;/', $bhp_mic_css, $bhp_mic_junk ),
	'⭐ all three stacked nav items share ONE desktop rule, exactly once'
);

/* --- 1d · About landed in the footer --- */

bhp_mic_assert(
	false !== strpos( $bhp_mic_nav_b, 'href="' . home_url( '/about/' ) . '"' ),
	'⭐⭐ item 547: /about/ is STILL LINKED on the page - it moved, it was not orphaned'
);
$bhp_mic_foot = '';
if ( preg_match( '#<nav class="footer-nav".*?</nav>#s', $bhp_mic_nav_b, $bhp_mic_fm2 ) ) {
	$bhp_mic_foot = $bhp_mic_fm2[0];
}
bhp_mic_assert(
	'' !== $bhp_mic_foot && 1 === substr_count( $bhp_mic_foot, 'href="' . home_url( '/about/' ) . '"' ),
	'⭐ About is in the FOOTER link column exactly once'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · /positivity-news/ — items 545 / 546
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 2 · POSITIVITY NEWS (items 545 / 546) ===\n";

bhp_mic_assert( function_exists( 'bhp_positivity_news_photo' ), 'the photo helper exists' );

$bhp_mic_photo = function_exists( 'bhp_positivity_news_photo' ) ? bhp_positivity_news_photo() : array();
bhp_mic_assert(
	isset( $bhp_mic_photo['file'] ) && 'adams-elementary-read-aloud-group.jpg' === $bhp_mic_photo['file'],
	'⛔ it is the ALREADY-PUBLISHED Adams group photograph, not a new asset'
);
bhp_mic_assert(
	isset( $bhp_mic_photo['alt'] ) && '' !== trim( (string) $bhp_mic_photo['alt'] ),
	'⛔⛔ the photograph HAS an alt on this environment - an undescribed photograph of children must never render'
);
bhp_mic_assert(
	isset( $bhp_mic_photo['alt'] ) && false !== strpos( (string) $bhp_mic_photo['alt'], 'Adams Elementary in Boise' ),
	'⭐ the alt is the founder-published string (registry on staging, verbatim copy as the production fallback)'
);
/* ⛔ §3 never-invent: the description says what is in the frame and claims
   nothing about what the visit did to anyone. */
foreach ( array( 'loved', 'excited', 'engaged', 'inspired', 'transformed', 'thrilled' ) as $bhp_mic_bad ) {
	bhp_mic_assert(
		false === stripos( (string) ( isset( $bhp_mic_photo['alt'] ) ? $bhp_mic_photo['alt'] : '' ), $bhp_mic_bad ),
		"⛔ the alt claims no reaction or outcome: no \"{$bhp_mic_bad}\""
	);
}
/* ⛔ THE ASSET MUST ACTUALLY BE IN THE BUILD. A helper returning a URL to a file
   the ZIP did not carry renders a broken image and fails nothing else. */
bhp_mic_assert(
	file_exists( get_template_directory() . '/assets/img/read-alouds/adams-elementary-read-aloud-group.jpg' ),
	'⛔ the photograph file is present in the deployed theme'
);

$bhp_mic_pn = wp_remote_retrieve_body( wp_remote_get( home_url( '/positivity-news/' ), array( 'timeout' => 45, 'sslverify' => false ) ) );

bhp_mic_assert( 1 === substr_count( $bhp_mic_pn, 'bhp-positivity__photo-img' ), '⭐ the photograph renders exactly once' );

/*
 * ⭐⭐ THE ORDER ASSERTION — item 546. The form must precede the body copy in
 *    the DOCUMENT, because DOM order is the entire above-the-fold mechanism and
 *    a later edit could reverse it with nothing else failing.
 */
$bhp_mic_form_pos  = strpos( $bhp_mic_pn, 'bhp-positivity__form' );
$bhp_mic_body_pos  = strpos( $bhp_mic_pn, 'bhp-positivity__body' );
$bhp_mic_photo_pos = strpos( $bhp_mic_pn, 'bhp-positivity__photo' );

bhp_mic_assert(
	false !== $bhp_mic_form_pos && false !== $bhp_mic_body_pos && $bhp_mic_form_pos < $bhp_mic_body_pos,
	'⭐⭐ item 546: the FORM precedes the body copy in DOM order'
);
bhp_mic_assert(
	false !== $bhp_mic_photo_pos && $bhp_mic_form_pos < $bhp_mic_photo_pos,
	'⛔ item 545 sits BELOW item 546 - the photograph can never push the email field down'
);

/* ⛔ THE APPROVED COPY IS UNTOUCHED BY THE REORDER. Both paragraphs, verbatim. */
foreach ( bhp_positivity_news_copy()['body'] as $bhp_mic_para ) {
	bhp_mic_assert(
		false !== strpos( $bhp_mic_pn, esc_html( $bhp_mic_para ) ),
		'⛔ approved body copy still renders character-exact: "' . substr( $bhp_mic_para, 0, 38 ) . '..."'
	);
}
bhp_mic_assert(
	false !== strpos( $bhp_mic_pn, esc_html( bhp_positivity_news_copy()['headline'] ) )
		&& false !== strpos( $bhp_mic_pn, esc_html( bhp_positivity_news_copy()['subhead'] ) ),
	'⛔ headline and subhead unchanged'
);
/* ⛔ AND STILL NO LEAD MAGNET. The one rule this page exists to keep. */
bhp_mic_assert( false === strpos( $bhp_mic_pn, 'teacher_adventure_toolkit' ), '⛔ no lead magnet on this page - unchanged' );
bhp_mic_assert( false === strpos( $bhp_mic_pn, 'adventure_kit_parent' ), '⛔ no parent magnet on this page - unchanged' );

/* --- the gradient. Asserted in the SHEET, because a gradient is not visible to
   a DOM read and claiming it renders without measuring it would be a fabricated
   check. The computed-style measurement is in the deploy plan. --- */
bhp_mic_assert( false !== strpos( $bhp_mic_css, '--color-ivory-rgb: 255, 250, 240;' ), '⭐ the ivory RGB token exists' );
bhp_mic_assert(
	false !== strpos( $bhp_mic_css, '.bhp-positivity__photo::after' )
		&& false !== strpos( $bhp_mic_css, '.bhp-positivity__photo::before' ),
	'⭐ item 545: BOTH gradient overlays are declared (vertical fade + side fade)'
);
bhp_mic_assert(
	2 <= substr_count( $bhp_mic_css, 'rgba(var(--color-ivory-rgb)' ),
	'⛔ the gradients fade to the IVORY TOKEN, not to a re-typed hex'
);
bhp_mic_assert(
	false !== strpos( $bhp_mic_css, 'aspect-ratio: 1200 / 675;' ),
	'⛔ the box is reserved at the real file ratio - a lazy image cannot shift the sign-off'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · /school-read-alouds/ — items 548 / 552
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 3 · SCHOOL READ-ALOUDS (items 548 / 552) ===\n";

$bhp_mic_sr = wp_remote_retrieve_body( wp_remote_get( home_url( '/school-read-alouds/' ), array( 'timeout' => 45, 'sslverify' => false ) ) );

bhp_mic_assert( '' !== $bhp_mic_sr && false !== strpos( $bhp_mic_sr, 'readaloud-sched__form' ), '/school-read-alouds/ renders with its form' );

/*
 * ⛔⛔ THE MOVE-NOT-COPY ASSERTION. The failure this catches is the carousel
 *    being ADDED to the pair and left in place below the form — two carousels,
 *    duplicate DOM ids, and a script that binds to the first one.
 */
bhp_mic_assert( 1 === substr_count( $bhp_mic_sr, 'data-bhp-photo-carousel' ), '⛔⛔ the carousel renders EXACTLY ONCE - moved, not copied' );
bhp_mic_assert( 1 === substr_count( $bhp_mic_sr, 'id="school-readalouds-gallery-read-alouds"' ), '⛔ its heading id is unique - no duplicate ids' );
bhp_mic_assert( 1 === substr_count( $bhp_mic_sr, 'school-readalouds__proof-grid' ), '⭐ the proof pair renders once' );

$bhp_mic_pair_pos  = strpos( $bhp_mic_sr, 'school-readalouds__proof-grid' );
$bhp_mic_car_pos   = strpos( $bhp_mic_sr, 'data-bhp-photo-carousel' );
$bhp_mic_sched_pos = strpos( $bhp_mic_sr, 'id="readaloud-scheduler"' );
$bhp_mic_past_pos  = strpos( $bhp_mic_sr, 'id="school-readalouds-past-title"' );
$bhp_mic_cap_pos   = strpos( $bhp_mic_sr, 'id="school-readalouds-capture-title"' );
$bhp_mic_visit_pos = strpos( $bhp_mic_sr, 'id="school-readalouds-visit-title"' );
$bhp_mic_hero_pos  = strpos( $bhp_mic_sr, 'id="school-readalouds-hero-title"' );

/* ⭐⭐ ITEM 552'S ORDER, END TO END, IN ONE CHAIN. */
bhp_mic_assert(
	false !== $bhp_mic_hero_pos && false !== $bhp_mic_visit_pos && $bhp_mic_hero_pos < $bhp_mic_visit_pos,
	'⭐ order: hero -> visit points'
);
bhp_mic_assert(
	false !== $bhp_mic_pair_pos && $bhp_mic_visit_pos < $bhp_mic_pair_pos,
	'⭐ order: visit points -> the About + carousel pair'
);
bhp_mic_assert(
	false !== $bhp_mic_car_pos && false !== $bhp_mic_sched_pos && $bhp_mic_car_pos < $bhp_mic_sched_pos,
	'⭐⭐ item 552: the CAROUSEL now renders BEFORE the week picker, not after it'
);
bhp_mic_assert(
	false !== $bhp_mic_past_pos && $bhp_mic_sched_pos < $bhp_mic_past_pos,
	'⭐ order: week picker -> past read-alouds'
);
bhp_mic_assert(
	false !== $bhp_mic_cap_pos && $bhp_mic_past_pos < $bhp_mic_cap_pos,
	'⭐ order: past read-alouds -> educator capture (still the only tail ask)'
);

/* ⛔ THE APPROVED COPY SURVIVED THE MOVE. Passage 4 verbatim, and the trim of
   passages 1-3 is still in force on THIS page (items 530 / 512). */
$bhp_mic_passages = function_exists( 'bhp_readaloud_approved_passages' ) ? bhp_readaloud_approved_passages() : array();
if ( isset( $bhp_mic_passages['founder-4'] ) ) {
	bhp_mic_assert(
		false !== strpos( $bhp_mic_sr, esc_html( $bhp_mic_passages['founder-4'] ) ),
		'⛔ passage 4 still renders character-exact after the reorder'
	);
}
if ( isset( $bhp_mic_passages['founder-1'] ) ) {
	bhp_mic_assert(
		false === strpos( $bhp_mic_sr, esc_html( $bhp_mic_passages['founder-1'] ) ),
		'⛔ the item-530 trim still holds - passage 1 is still off this page'
	);
}
/* ⛔ AND NOTHING PENDING CREPT ONTO THE PAGE while sections were being moved. */
bhp_mic_assert( false === strpos( $bhp_mic_sr, 'PENDING READ-BACK' ), '⛔ no placeholder block on the page' );
bhp_mic_assert( false === strpos( $bhp_mic_sr, 'data-popup-config' ), '⛔ no popup on this teacher page - funnel isolation unchanged' );

/* --- 3b · whitespace compression round 2, asserted in the sheet --- */

bhp_mic_assert(
	false !== strpos( $bhp_mic_css, '--section-space: clamp(2.25rem, 4vw, 3.5rem);' ),
	'⭐ item 548 round 2: the page-scoped --section-space is retuned'
);
bhp_mic_assert(
	false === strpos( $bhp_mic_css, '--section-space: clamp(3.25rem, 6vw, 5.25rem);' ),
	'⛔ the 1.19.327 value is SUPERSEDED, not merely joined by a second declaration'
);
/*
 * ⛔ THE `body:not(.home)` PREFIX. 1.19.327 shipped this rule once WITHOUT it
 *    and measured 72px unchanged at 1440, because the sitewide rule is (0,2,1)
 *    and a bare `.school-readalouds .component-heading` is (0,2,0). This
 *    assertion is that measured defect turned into a test.
 */
bhp_mic_assert(
	false !== strpos( $bhp_mic_css, 'body:not(.home) .school-readalouds .component-heading' ),
	'⛔ the heading rule keeps its load-bearing body:not(.home) prefix - without it the sitewide rule wins'
);
bhp_mic_assert(
	false !== strpos( $bhp_mic_css, '.school-readalouds .readaloud-sched.section' ),
	'⭐ item 552: the week picker\'s top padding is closed from its own side too'
);

/* ⛔ THE HERO CTA REQUIREMENT IS NOT RELAXED BY COMPRESSION. The hero padding
   only ever moves the button UP; asserted here as "the rule still exists and is
   still smaller than the section default". The pixel measurement at both
   viewports is in the deploy plan. */
bhp_mic_assert(
	false !== strpos( $bhp_mic_css, 'padding-block: clamp(1.25rem, 2.5vw, 2rem);' ),
	'⭐ the hero keeps its own smaller padding - the CTA moved up, never down'
);

/* --- 3c · ⛔⛔ THE MOBILE GRID MINIMUM. A REAL DEFECT THIS BUILD SHIPPED,
       MEASURED, AND FIXED — and this assertion is what stops it returning. --- */

/*
 * ⛔ WHAT IT CATCHES. Without an explicit `grid-template-columns`, the single
 *    implicit column is `auto`, whose MINIMUM is `min-content` — and the photo
 *    carousel's min-content is its rail's width. MEASURED on staging at
 *    `innerWidth` 375: a 343px grid box with 518px children, ending 159px past
 *    the right edge of the phone.
 *
 * ⛔⛔ AND THE ORDINARY CHECK DID NOT CATCH IT: `documentElement.scrollWidth`
 *     still read 375, because an ancestor clipped the overflow. So the About
 *     passage was being CUT OFF at the screen edge with no scrollbar to reveal
 *     it. ⭐ A horizontal-overflow assertion is NOT sufficient for this class of
 *     defect, which is exactly why this one asserts the declaration itself.
 */
bhp_mic_assert(
	(bool) preg_match( '/\.school-readalouds__proof-grid\s*\{[^}]*grid-template-columns:\s*minmax\(\s*0\s*,\s*1fr\s*\)/s', $bhp_mic_css ),
	'⛔⛔ the mobile proof grid declares minmax(0, 1fr) - an implicit auto column sizes to the carousel and clips the prose'
);
bhp_mic_assert(
	(bool) preg_match( '/\.school-readalouds__proof-grid--solo\s*\{[^}]*minmax\(\s*0\s*,\s*1fr\s*\)/s', $bhp_mic_css ),
	'⛔ the solo (no-photographs) desktop case has the same minimum - production has no visit-notes option and takes this path'
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · ⭐⭐ THE TOOLKIT OPT-IN — items 553 / 554. THE CONSENT ASSERTIONS.
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 4 · TOOLKIT OPT-IN (items 553 / 554) ===\n";

/* ⛔ THE SEALED LABEL, CHARACTER-EXACT. A near-miss paraphrase of a consent
   string is a different promise, and this is the one place a paraphrase would
   be invisible. */
$bhp_mic_optin_label = 'Also send me the free Adventure Learning Toolkit and my classroom resources emails.';
bhp_mic_assert(
	false !== strpos( $bhp_mic_sr, esc_html( $bhp_mic_optin_label ) ),
	'⭐⭐ the founder-sealed opt-in label renders character-exact'
);
bhp_mic_assert( 1 === substr_count( $bhp_mic_sr, 'name="toolkit_optin"' ), '⭐ exactly one opt-in control' );

/* ⭐⭐ PRE-CHECKED. Item 554: "im ok with the preselected free kit". */
if ( preg_match( '/<input[^>]*name="toolkit_optin"[^>]*>/', $bhp_mic_sr, $bhp_mic_om ) ) {
	bhp_mic_assert( false !== strpos( $bhp_mic_om[0], 'checked' ), '⭐⭐ item 554: the box ships PRE-CHECKED' );
	bhp_mic_assert( false !== strpos( $bhp_mic_om[0], 'value="yes"' ), '⛔ its value is the literal the handler checks for' );
	bhp_mic_assert( false !== strpos( $bhp_mic_om[0], 'type="checkbox"' ), '⛔ it is a real checkbox, not a hidden field' );
} else {
	bhp_mic_assert( false, '⛔ the opt-in input was not found in the rendered form' );
}

/* ⭐ A REAL `<label for>`, which is what makes "easy to untick" true on glass:
   the whole sentence is the tap target, not a 20px square. */
bhp_mic_assert(
	false !== strpos( $bhp_mic_sr, 'for="bhp-sched-optin"' ) && false !== strpos( $bhp_mic_sr, 'id="bhp-sched-optin"' ),
	'⛔ the label is bound to the input - the sentence itself toggles it'
);

/* ⛔⛔ ABOVE THE SUBMIT BUTTON. The ruling's own words. A consent control below
   the button is a control most people never see. */
$bhp_mic_optin_pos = strpos( $bhp_mic_sr, 'name="toolkit_optin"' );
$bhp_mic_submit_pos = strpos( $bhp_mic_sr, 'readaloud-sched__btn' );
bhp_mic_assert(
	false !== $bhp_mic_optin_pos && false !== $bhp_mic_submit_pos && $bhp_mic_optin_pos < $bhp_mic_submit_pos,
	'⭐⭐ the opt-in sits ABOVE the submit button in DOM order'
);

/*
 * ⛔⛔ AND NOTHING HIDES IT. THIS IS THE ASSERTION THAT KEEPS THE CONSENT
 *    HONEST. "Smuggled consent" was the founder's original instruction and it
 *    was rejected; the shipped control is visible, and a later refactor that
 *    makes it invisible would reinstate the rejected version with no other
 *    failure anywhere.
 */
$bhp_mic_hidden = array();
if ( preg_match_all( '/([^{}]*readaloud-sched__optin[^{}]*)\{([^{}]*)\}/', $bhp_mic_css, $bhp_mic_hm, PREG_SET_ORDER ) ) {
	foreach ( $bhp_mic_hm as $bhp_mic_hr ) {
		if ( preg_match( '/(display\s*:\s*none|visibility\s*:\s*hidden|opacity\s*:\s*0(?![.\d])|clip-path\s*:|position\s*:\s*absolute)/i', $bhp_mic_hr[2] ) ) {
			$bhp_mic_hidden[] = trim( preg_replace( '/\s+/', ' ', $bhp_mic_hr[1] ) );
		}
	}
}
bhp_mic_assert(
	empty( $bhp_mic_hidden ),
	'⛔⛔ NO stylesheet rule hides the opt-in control'
		. ( $bhp_mic_hidden ? ' - FOUND: ' . implode( ' | ', $bhp_mic_hidden ) : '' )
);
bhp_mic_assert(
	false !== strpos( $bhp_mic_css, '.readaloud-sched__optin-label' )
		&& false === strpos( $bhp_mic_css, "screen-reader-text readaloud-sched__optin" ),
	'⛔ the label is styled as body copy, not as fine print or as screen-reader-only text'
);

/* --- 4b · the wire --- */

bhp_mic_assert( function_exists( 'bhp_readaloud_request_enroll_educator' ), 'the educator enrolment function exists' );

/*
 * ⭐⭐ THE "EXACTLY AS AN EDUCATORS-PAGE SIGNUP" ASSERTION, AND IT IS NOT A
 *    COMMENT — the three arguments are read out of the function's own source
 *    and matched against the three the educator landing page passes.
 */
$bhp_mic_sched_src = bhp_mic_css_code_only( (string) file_get_contents( get_template_directory() . '/inc/readaloud-scheduler.php' ) );
$bhp_mic_enroll = '';
if ( preg_match( '/function bhp_readaloud_request_enroll_educator\s*\(.*?\n\}/s', $bhp_mic_sched_src, $bhp_mic_em ) ) {
	$bhp_mic_enroll = $bhp_mic_em[0];
}
bhp_mic_assert( false !== strpos( $bhp_mic_enroll, "'lead_magnet'   => 'teacher_adventure_toolkit'" ), '⭐ it passes the EDUCATOR lead magnet' );
bhp_mic_assert( false !== strpos( $bhp_mic_enroll, "'audience_type' => 'educators'" ), '⭐ it passes audience_type educators' );
bhp_mic_assert( false !== strpos( $bhp_mic_enroll, "'context'       => 'lead_magnet'" ), '⭐ it passes the same context lead-magnet-cta.php passes' );

/*
 * ⭐⭐ AND THE TAGS THOSE THREE ACTUALLY PRODUCE, computed by CALLING the real
 *    filter chain rather than by reading a comment about it. This is the
 *    assertion that would catch a future `bhp_mailchimp_signup_tags` callback
 *    accidentally capturing this path.
 */
if ( function_exists( 'bhp_get_mailchimp_signup_tags' ) ) {
	$bhp_mic_tags_edu = bhp_get_mailchimp_signup_tags( 'lead_magnet', 'educators', 'teacher_adventure_toolkit', home_url( '/audience/educators/' ) );
	$bhp_mic_tags_ra  = bhp_get_mailchimp_signup_tags( 'lead_magnet', 'educators', 'teacher_adventure_toolkit', home_url( '/school-read-alouds/' ) );
	echo '  NOTE  educator-page tags: ' . implode( ' | ', $bhp_mic_tags_edu ) . "\n";
	echo '  NOTE  read-aloud   tags: ' . implode( ' | ', $bhp_mic_tags_ra ) . "\n";
	bhp_mic_assert(
		$bhp_mic_tags_edu === $bhp_mic_tags_ra,
		'⭐⭐ THE TAG SETS ARE IDENTICAL - a booking opt-in is tagged exactly as an educators-page signup'
	);
	bhp_mic_assert( ! empty( $bhp_mic_tags_ra ), '⛔ the tag set is non-empty - a contact with no tags enters no journey' );
}

/* ⛔ THE HANDLER READS ABSENCE AS "NO". An unchecked box posts nothing, and
   defaulting absence to true would turn a dropped field into a consent claim. */
bhp_mic_assert(
	false !== strpos( $bhp_mic_sched_src, "\$optin = isset( \$_POST['toolkit_optin'] ) && 'yes' === sanitize_key( wp_unslash( \$_POST['toolkit_optin'] ) );" ),
	'⛔⛔ absence of the field is read as NO, never as consent'
);
/* ⛔ AND THE BOOKING IS NEVER CONDITIONAL ON THE OPT-IN. */
bhp_mic_assert(
	false !== strpos( $bhp_mic_enroll, "if ( ! \$opted ) {" ),
	'⛔ unticked returns immediately - nothing reaches Mailchimp and the booking is unaffected'
);

/* ⭐ THE COMPOSED EMAIL SAYS WHICH WAY SHE ANSWERED, IN BOTH DIRECTIONS. */
if ( function_exists( 'bhp_readaloud_request_compose' ) ) {
	$bhp_mic_base = array(
		'school' => 'Assert Elementary', 'city' => 'boise', 'contact' => 'A Tester',
		'email' => 'micro@example.invalid', 'grades' => 'First grade',
		'week' => '2026-10-05', 'week_label' => 'Week of October 5', 'week_range' => 'Monday, October 5 to Friday, October 9',
		'slots' => array(), 'weekdays' => array(), 'notes' => '', 'source_page' => home_url( '/school-read-alouds/' ),
	);
	$bhp_mic_yes = bhp_readaloud_request_compose( array_merge( $bhp_mic_base, array( 'optin' => true ) ) );
	$bhp_mic_no  = bhp_readaloud_request_compose( array_merge( $bhp_mic_base, array( 'optin' => false ) ) );
	bhp_mic_assert( false !== strpos( $bhp_mic_yes['body'], 'Toolkit opt-in: YES' ), '⭐ the email states a YES opt-in' );
	bhp_mic_assert( false !== strpos( $bhp_mic_no['body'], 'Toolkit opt-in: no' ), '⭐ the email states a NO opt-in - printed both ways, never omitted' );
	/* ⛔ COMPOSE IS STILL PURE AND STILL SENDS NOTHING. */
	bhp_mic_assert( false !== strpos( $bhp_mic_yes['body'], 'Nothing is booked until you reply' ), '⛔ the TENTATIVE line is unchanged' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE COUPON AUTO-APPLY COUNTER
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 5 · COUPON AUTO-APPLY COUNTER ===\n";

bhp_mic_assert( function_exists( 'bhp_coupon_apply_counter_record' ), 'the counter exists' );
bhp_mic_assert(
	has_action( 'bhp_coupon_auto_applied', 'bhp_coupon_apply_counter_record' ),
	'⭐ it listens on an action - so the bundle plugin path can join with one line'
);

/* ⭐ THE HOOK FIRES ONLY ON A SUCCESSFUL APPLY. Asserted on the source, inside
   the `if ( $applied )` branch, because an attempt counted as an application
   would make the whole number meaningless. */
$bhp_mic_coupon_src = bhp_mic_css_code_only( (string) file_get_contents( get_template_directory() . '/inc/coupon-url-apply.php' ) );
bhp_mic_assert(
	1 === substr_count( $bhp_mic_coupon_src, "do_action( 'bhp_coupon_auto_applied'" ),
	'⛔ the action fires from exactly one place'
);
if ( preg_match( '/if \( \$applied \) \{(.*?)\n\t\}/s', $bhp_mic_coupon_src, $bhp_mic_am ) ) {
	bhp_mic_assert(
		false !== strpos( $bhp_mic_am[1], "do_action( 'bhp_coupon_auto_applied'" ),
		'⛔⛔ it fires INSIDE if ( $applied ) - an attempt is never counted as an application'
	);
} else {
	bhp_mic_assert( false, '⛔ could not locate the applied branch to check the hook position' );
}

/* ⛔⛔ NO PII. Asserted by reading the writer's own source for the field names
   that must never appear in it. */
$bhp_mic_counter_src = bhp_mic_css_code_only( (string) file_get_contents( get_template_directory() . '/inc/coupon-apply-counter.php' ) );
foreach ( array( 'REMOTE_ADDR', 'HTTP_USER_AGENT', 'get_current_user_id', 'billing_email', 'setcookie', '$_COOKIE', 'get_customer_id' ) as $bhp_mic_pii ) {
	bhp_mic_assert(
		false === strpos( $bhp_mic_counter_src, $bhp_mic_pii ),
		"⛔ the counter never touches {$bhp_mic_pii}"
	);
}
/* ⛔ AND IT NEVER WRITES A COUPON, PRICE, PRODUCT OR SETTING. */
foreach ( array( 'wp_insert_post', 'update_post_meta', 'wp_update_post', 'wc_create_coupon', 'update_option( \'woocommerce' ) as $bhp_mic_mut ) {
	bhp_mic_assert(
		false === strpos( $bhp_mic_counter_src, $bhp_mic_mut ),
		"⛔ the counter never calls {$bhp_mic_mut}"
	);
}
/* ⭐ ITS ONE OPTION IS NON-AUTOLOADED. A growing option read on every request
   is a performance defect wearing a diagnostic's clothes. */
bhp_mic_assert(
	false !== strpos( $bhp_mic_counter_src, 'update_option( $option, $map, false )' ),
	'⛔ the option is written with autoload FALSE'
);

/* --- 5b · the pruner, exercised across its boundaries. PURE, so every
       boundary is an assertion rather than something you wait six months for. --- */

$bhp_mic_map = array(
	'keepme'  => array( '2026-08-30' => 3, '2026-08-01' => 1, '2026-01-01' => 9 ),
	'oldonly' => array( '2025-01-01' => 4 ),
	'junk'    => array( 'not-a-date' => 2, '2026-08-29' => 1 ),
	'zeroes'  => array( '2026-08-29' => 0 ),
);
$bhp_mic_pruned = bhp_coupon_apply_counter_prune( $bhp_mic_map, '2026-06-01', 50 );

bhp_mic_assert( isset( $bhp_mic_pruned['keepme'] ), '⭐ a code with in-window days survives' );
bhp_mic_assert( ! isset( $bhp_mic_pruned['keepme']['2026-01-01'] ), '⛔ an out-of-window day is dropped' );
bhp_mic_assert( isset( $bhp_mic_pruned['keepme']['2026-08-30'] ) && 3 === $bhp_mic_pruned['keepme']['2026-08-30'], '⭐ in-window counts are preserved exactly' );
bhp_mic_assert( ! isset( $bhp_mic_pruned['oldonly'] ), '⛔ a code whose only day fell out of window is dropped entirely' );
bhp_mic_assert( ! isset( $bhp_mic_pruned['junk']['not-a-date'] ), '⛔ a malformed date key is dropped, not compared' );
bhp_mic_assert( isset( $bhp_mic_pruned['junk']['2026-08-29'] ), '⭐ its valid sibling day survives' );
bhp_mic_assert( ! isset( $bhp_mic_pruned['zeroes'] ), '⛔ a zero count is not kept' );
/* ⛔ THE CUTOFF IS INCLUSIVE at its own boundary. */
$bhp_mic_edge = bhp_coupon_apply_counter_prune( array( 'c' => array( '2026-06-01' => 1, '2026-05-31' => 1 ) ), '2026-06-01', 50 );
bhp_mic_assert(
	isset( $bhp_mic_edge['c']['2026-06-01'] ) && ! isset( $bhp_mic_edge['c']['2026-05-31'] ),
	'⛔ the cutoff day itself is KEPT and the day before it is dropped'
);
/* ⭐ THE CODE CEILING EVICTS THE OLDEST-TOUCHED, NEVER THE NEWEST. */
$bhp_mic_many = array(
	'stale'  => array( '2026-06-02' => 1 ),
	'recent' => array( '2026-08-30' => 1 ),
	'mid'    => array( '2026-07-15' => 1 ),
);
$bhp_mic_capped = bhp_coupon_apply_counter_prune( $bhp_mic_many, '2026-06-01', 2 );
bhp_mic_assert( 2 === count( $bhp_mic_capped ), '⭐ the code ceiling is enforced' );
bhp_mic_assert( isset( $bhp_mic_capped['recent'] ), '⛔ the most recently used code survives the cap' );
bhp_mic_assert( ! isset( $bhp_mic_capped['stale'] ), '⛔ the oldest-touched code is the one evicted' );

/* ⭐ THE READER AND THE WRITER AGREE ON THE KEY. If they disagree the reader
   silently returns zero, which is the quiet failure for a diagnostic.

   ⛔⛔ THE FIXTURE IS A PLACEHOLDER CODE, NOT A REAL ONE, AND THAT IS A RULE
      RATHER THAN A STYLE CHOICE. This repository is PUBLIC on GitHub; a real
      campaign code in a test file is a live discount handed to anyone who reads
      it. Standing Rules §4.1 lists coupon codes as never-public. ⭐ The
      assertion is about CASE AND WHITESPACE HANDLING, so any string proves it
      exactly as well. */
if ( function_exists( 'bhp_coupon_apply_counter_key' ) ) {
	bhp_mic_assert(
		bhp_coupon_apply_counter_key( 'EXAMPLE10' ) === bhp_coupon_apply_counter_key( ' example10 ' ),
		'⭐ the storage key is case and whitespace insensitive, both sides'
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · §26 AFFILIATE SWEEP — the count-decrease test's AFTER half
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE COMPARISON IS THE DEPLOY PLAN'S, NOT THIS FILE'S. A suite cannot hold
 *    the BEFORE count across a deploy, so it prints the AFTER counts and the
 *    plan records both. §26.6: a count that was not actually run is a
 *    fabricated check, so this prints what it measured and claims nothing.
 * ═══════════════════════════════════════════════════════════════════════════ */

echo "\n=== 6 · AFFILIATE SWEEP (prints only) ===\n";

foreach ( array( '/blog/why-i-wrote-this-book/', '/shop/', '/school-read-alouds/', '/author-visits/', '/read-aloud/', '/complete-collection/', '/positivity-news/', '/about/' ) as $bhp_mic_p ) {
	$bhp_mic_r  = wp_remote_get( home_url( $bhp_mic_p ), array( 'timeout' => 45, 'sslverify' => false ) );
	$bhp_mic_pb = wp_remote_retrieve_body( $bhp_mic_r );
	printf(
		"  NOTE  %-32s http=%s amzn.to=%d tag=%d\n",
		$bhp_mic_p,
		wp_remote_retrieve_response_code( $bhp_mic_r ),
		substr_count( $bhp_mic_pb, 'amzn.to' ),
		substr_count( $bhp_mic_pb, 'tag=' )
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * TOTALS
 * ═══════════════════════════════════════════════════════════════════════════ */

$bhp_mic_p_n = isset( $GLOBALS['bhp_mic_pass'] ) ? (int) $GLOBALS['bhp_mic_pass'] : 0;
$bhp_mic_f_n = isset( $GLOBALS['bhp_mic_fail'] ) ? (int) $GLOBALS['bhp_mic_fail'] : 0;

echo "\n=== TOTALS ===\n";
echo "  PASS: {$bhp_mic_p_n}\n";
echo "  FAIL: {$bhp_mic_f_n}\n";
echo ( 0 === $bhp_mic_f_n ? "  RESULT: ALL PASS\n" : "  RESULT: FAILURES PRESENT\n" );
