<?php
/**
 * Brave Hearts — THE PATH LINE LEAVES THE HOMEPAGE.
 *
 * `CYCLE165-LD-ITERATE-6-PATH-LINE` (2026-08-19, theme 1.19.270).
 *
 * Andrew Signore, carrier item 114, ⛔ RELAYED through the Chief of Staff and
 * NOT witnessed first-hand by the agent that wrote this suite:
 *
 *   "Remove the 'not sure which brave hearts path fits' on the home page then
 *    we can deploy."
 *
 * Run via WP-CLI (⚠ THE `--url` FLAG IS NOT OPTIONAL — without it
 * `is_staging()` resolves against the production home URL and every fetched
 * document in this file comes from the wrong environment. `CYCLE165-LD-53`):
 *
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cro-iterate6.php \
 *     --url=https://staging2.braveheartspublishing.com --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from SERVED DOCUMENTS rather than from template source:
 *   §1  the band is ABSENT from the homepage — its copy, its button label, its
 *       launcher hook, its modal, its impression event and its anchor id
 *   §2  the band's ASSETS stopped loading on the homepage too, so the removal
 *       is a real subtraction and not a hidden div
 *   §3  the canonical /find-your-adventure/ PAGE still renders a working quiz
 *   §4  a THIRD page still renders the band, proving the change is
 *       homepage-scoped and not a sitewide retirement
 *   §5  the homepage keeps a live route INTO the quiz (the hero ghost CTA) and
 *       that route points at the canonical page, not at a dead fragment
 *   §6  FRAGMENT SCAN — every same-page `href="#..."` the homepage emits
 *       resolves to an id the homepage actually renders. This is the check
 *       that would catch the removal orphaning a link.
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads served markup.
 *    It does NOT prove that a fold did not move, that nothing overflows, that
 *    the layout still reads well, or that the console is clean. Those are
 *    BROWSER facts, measured separately at an asserted `window.innerWidth` and
 *    filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-iterate6-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 * ⛔ NOTHING IS WRITTEN. No post, page, option, product, price, variation,
 *    coupon, stock level, shipping/tax/payment/checkout setting, cart, order,
 *    attachment or user is created or modified by any line here. No form is
 *    submitted and no address enters any list.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_i6_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_i6_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

$home  = bhp_i6_fetch( home_url( '/' ) );
$quiz  = bhp_i6_fetch( home_url( '/find-your-adventure/' ) );
/*
 * §4's control page. `/about/` is chosen because it is an ordinary information
 * page: it is NOT in `bhp_should_show_any_popup()`'s exclusion set, it is not
 * the collection page (whose footer order R-9 defers), and it is not one of the
 * four audience landing templates. If the launcher survives anywhere, it
 * survives here.
 */
$about = bhp_i6_fetch( home_url( '/about/' ) );

bhp_i6_assert( '' !== $home,  '§0 the homepage was fetched (a failure here invalidates every §1/§2/§5/§6 result below)', $failures );
bhp_i6_assert( '' !== $quiz,  '§0 /find-your-adventure/ was fetched', $failures );
bhp_i6_assert( '' !== $about, '§0 /about/ was fetched (the homepage-scope control)', $failures );

/* ───────────────────────────────────────────────────────────────────────────
 * §1 THE BAND IS ABSENT FROM THE HOMEPAGE
 *
 * Six independent markers, deliberately. One marker could disappear because a
 * class name changed; six disappearing together is the band being gone.
 * ─────────────────────────────────────────────────────────────────────────── */
bhp_i6_assert(
	false === strpos( $home, 'Not sure which Brave Hearts path fits' ),
	'§1 the founder-named copy "Not sure which Brave Hearts path fits?" is absent from the homepage',
	$failures
);
bhp_i6_assert(
	false === strpos( $home, 'Find My Best Next Step' ),
	'§1 the band button label "Find My Best Next Step" is absent from the homepage',
	$failures
);
bhp_i6_assert(
	false === strpos( $home, 'bhp-quiz-cta' ),
	'§1 no `bhp-quiz-cta` wrapper is rendered on the homepage',
	$failures
);
bhp_i6_assert(
	0 === substr_count( $home, 'data-bhp-quiz-launcher' ),
	'§1 no quiz launcher hook on the homepage — found ' . substr_count( $home, 'data-bhp-quiz-launcher' ),
	$failures
);
bhp_i6_assert(
	0 === substr_count( $home, 'data-bhp-quiz-modal' ),
	'§1 no hidden quiz modal on the homepage — the band is REMOVED, not display:none — found '
		. substr_count( $home, 'data-bhp-quiz-modal' ),
	$failures
);
bhp_i6_assert(
	0 === substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
	'§1 the `quiz_cta_viewed` impression no longer fires on the homepage — found '
		. substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
	$failures
);
bhp_i6_assert(
	0 === substr_count( $home, 'id="find-your-adventure"' ),
	'§1 the `#find-your-adventure` anchor id leaves the homepage with the band that carried it',
	$failures
);

/* ───────────────────────────────────────────────────────────────────────────
 * §2 THE ASSETS LEFT TOO
 *
 * ⭐ THIS IS THE ASSERTION THAT DISTINGUISHES A REMOVAL FROM A HIDE. Markup can
 *    vanish while the stylesheet and the script keep shipping; that is a
 *    half-done subtraction and it is invisible to every §1 check above.
 * ─────────────────────────────────────────────────────────────────────────── */
bhp_i6_assert(
	false === strpos( $home, 'quiz-entry-cta' ),
	'§2 `quiz-entry-cta.css` no longer loads on the homepage',
	$failures
);
bhp_i6_assert(
	false === strpos( $home, 'quiz-modal' ),
	'§2 neither `quiz-modal.css` nor `quiz-modal.js` loads on the homepage',
	$failures
);
bhp_i6_assert(
	false === strpos( $home, 'audience-quiz' ),
	'§2 `audience-quiz.css`/`.js` no longer loads on the homepage either (the stale `$on_homepage` enqueue limb is gone)',
	$failures
);

/* ───────────────────────────────────────────────────────────────────────────
 * §3 THE QUIZ PAGE ITSELF IS UNTOUCHED
 *
 * The founder removed a homepage band, not the quiz. If this section goes red
 * the release has overshot its own instruction and must not ship.
 * ─────────────────────────────────────────────────────────────────────────── */
bhp_i6_assert(
	false !== strpos( $quiz, 'data-bhp-quiz' ),
	'§3 /find-your-adventure/ still renders a live quiz instance',
	$failures
);
bhp_i6_assert(
	false !== strpos( $quiz, 'audience-quiz' ),
	'§3 ...and still loads the quiz component assets, so it is not silently inert',
	$failures
);
bhp_i6_assert(
	false !== strpos( $quiz, 'find-your-adventure-intro' ),
	'§3 ...and its own intro gate (a DIFFERENT id on a DIFFERENT page) is untouched',
	$failures
);
bhp_i6_assert(
	false === strpos( $quiz, 'data-bhp-quiz-modal' ),
	'§3 ...and it still renders the quiz inline rather than growing a modal of itself',
	$failures
);

/* ───────────────────────────────────────────────────────────────────────────
 * §4 THE CHANGE IS HOMEPAGE-SCOPED
 * ─────────────────────────────────────────────────────────────────────────── */
bhp_i6_assert(
	false !== strpos( $about, 'Not sure which Brave Hearts path fits' ),
	'§4 /about/ STILL shows the band — this release is `is_front_page()`, not a sitewide retirement',
	$failures
);
bhp_i6_assert(
	1 === substr_count( $about, 'data-bhp-quiz-launcher' ),
	'§4 ...with exactly one launcher, exactly as in 1.19.269 — found ' . substr_count( $about, 'data-bhp-quiz-launcher' ),
	$failures
);
bhp_i6_assert(
	false !== strpos( $about, 'quiz-modal' ),
	'§4 ...and its assets still load there',
	$failures
);

/* ───────────────────────────────────────────────────────────────────────────
 * §5 THE HOMEPAGE KEEPS A ROUTE INTO THE QUIZ
 *
 * Removing the band must not strand the quiz. The hero ghost CTA is the
 * surviving route and it is asserted here so a later subtraction cannot take
 * both without going red.
 * ─────────────────────────────────────────────────────────────────────────── */
bhp_i6_assert(
	false !== strpos( $home, 'data-bhp-source="home_hero_quiz"' ),
	'§5 the hero ghost CTA is still on the homepage',
	$failures
);
bhp_i6_assert(
	false !== strpos( $home, 'Take the 30-second quiz.' ),
	'§5 ...with its copy unchanged',
	$failures
);
bhp_i6_assert(
	false !== strpos( $home, esc_url( home_url( '/find-your-adventure/' ) ) ),
	'§5 ...and it points at the canonical /find-your-adventure/ PAGE, not at a homepage fragment',
	$failures
);
/*
 * ⚠ MY OWN ASSERTION WAS WRONG ABOUT THE PAGE, AND IS CORRECTED RATHER THAN
 *   RELAXED. Its first run against staging2 asserted `1 === ...` and found 2.
 *
 * The homepage carries TWO routes into the quiz, not one, and both predate
 * this release:
 *   · the hero ghost CTA          `home_hero_quiz`          front-page.php
 *   · the "Open the book" ghost   `home_open_the_book_quiz` in
 *     template-parts/components/home-open-the-book.php ("Which adventure fits
 *     your reader?")
 * Both are `<a href>` links to the canonical `/find-your-adventure/` PAGE —
 * READ from the templates and CONFIRMED in the served document, not assumed —
 * so neither was orphaned by removing the band, and the homepage is better
 * covered than the first draft of this suite believed.
 *
 * ⭐ THE COUNT IS PINNED AT 2, NOT LOOSENED TO ">= 1". Pinning is the whole
 *    value of the assertion: it fails if a later subtraction takes either
 *    route away, and it fails if a third appears unannounced.
 */
bhp_i6_assert(
	2 === substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ),
	'§5 ...and BOTH homepage quiz routes still fire `quiz_cta_clicked` — found '
		. substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ),
	$failures
);
bhp_i6_assert(
	false !== strpos( $home, 'data-bhp-source="home_open_the_book_quiz"' ),
	'§5 ...the second route ("Which adventure fits your reader?") is still present too',
	$failures
);
/*
 * ⚠ AND MY SECOND ATTEMPT AT THIS ROW WAS ALSO WRONG, FOR A DIFFERENT REASON,
 *   AND IS ALSO CORRECTED RATHER THAN RELAXED. It counted page-wide
 *   occurrences of `href="…/find-your-adventure/"` and asserted 2; staging2
 *   returned 3. The THIRD is the "Start Here" NAVIGATION link
 *   (`functions.php`, the primary-menu map) — a real, correct, pre-existing
 *   link to the same page that has nothing to do with either quiz CTA.
 *
 * ⭐ COUNTING THE PAGE WAS THE WRONG INSTRUMENT. The claim is about the two
 *    CTAs' OWN hrefs, so the test now reads each `quiz_cta_clicked` anchor and
 *    checks ITS href. That is immune to a nav link being added or removed, and
 *    it is what the sentence in the label actually says.
 */
$cta_hrefs = array();
if ( preg_match_all( '/<a\b[^>]*>/i', $home, $tags ) ) {
	foreach ( $tags[0] as $tag ) {
		if ( false === strpos( $tag, 'data-bhp-event="quiz_cta_clicked"' ) ) {
			continue;
		}
		$cta_hrefs[] = preg_match( '/href="([^"]*)"/i', $tag, $hm ) ? $hm[1] : '(no href)';
	}
}
$want_href     = esc_url( home_url( '/find-your-adventure/' ) );
$all_to_page   = ( 2 === count( $cta_hrefs ) );
foreach ( $cta_hrefs as $h ) {
	if ( $h !== $want_href ) {
		$all_to_page = false;
	}
}
bhp_i6_assert(
	$all_to_page,
	'§5 ...and BOTH quiz CTAs\' OWN hrefs are the canonical PAGE, neither a homepage fragment — found '
		. ( array() === $cta_hrefs ? '(none)' : implode( ' | ', $cta_hrefs ) ),
	$failures
);

/* ───────────────────────────────────────────────────────────────────────────
 * §6 FRAGMENT SCAN — no homepage link points at an id the homepage no longer
 *     renders.
 *
 * ⭐ THIS IS THE CHECK THE REMOVAL ACTUALLY NEEDS. The band carried
 *    `id="find-your-adventure"`. If anything on the page linked to
 *    `#find-your-adventure`, this release would have created a link that
 *    scrolls nowhere — the exact defect class the hero-cta-fallback pattern
 *    exists to prevent.
 *
 * ⚠ `href="#"` is EXCLUDED and that exclusion is deliberate, not a loophole:
 *   the theme uses bare `#` as a JS-populated placeholder (front-page.php
 *   documents it), and it is not a fragment reference to an element.
 * ─────────────────────────────────────────────────────────────────────────── */
$fragments = array();
if ( preg_match_all( '/href=["]#([^"\s]+)["]/', $home, $m ) ) {
	$fragments = array_values( array_unique( $m[1] ) );
}
$orphans = array();
foreach ( $fragments as $frag ) {
	$frag = html_entity_decode( $frag, ENT_QUOTES );
	if ( '' === $frag || 'top' === strtolower( $frag ) ) {
		continue; // '#top' is a browser built-in and needs no element.
	}
	if ( false === strpos( $home, 'id="' . $frag . '"' ) ) {
		$orphans[] = $frag;
	}
}
bhp_i6_assert(
	array() === $orphans,
	'§6 FRAGMENT SCAN: every same-page fragment link on the homepage resolves to a rendered id — '
		. ( array() === $orphans
			? count( $fragments ) . ' fragment(s) checked, 0 orphaned'
			: 'ORPHANED: ' . implode( ', ', $orphans ) ),
	$failures
);
bhp_i6_assert(
	! in_array( 'find-your-adventure', $fragments, true ),
	'§6 ...and specifically NOTHING on the homepage links to `#find-your-adventure` any more',
	$failures
);

echo "\n";
if ( empty( $failures ) ) {
	echo "ALL PASS — CYCLE165-LD-ITERATE-6-PATH-LINE (theme 1.19.270)\n";
} else {
	echo 'FAILURES (' . count( $failures ) . "):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
