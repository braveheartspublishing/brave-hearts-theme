<?php
/**
 * Brave Hearts — THE HOMEPAGE, Direction 1.
 *
 * CYCLE165-LD-DIRECTION1-STEP4-HOME (2026-08-19, theme 1.19.263).
 * Direction 1, "Expedition field notes", board build step 4 of 4.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-direction1-home.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from the SERVED HOMEPAGE DOCUMENT rather than from template source:
 *   §1  the hero's five frozen strings are byte-identical to 1.19.262
 *   §2  the field rule renders exactly once, is decorative, and carries no
 *       text, no link and no analytics event
 *   §3  no new hue: both shipped SVG assets are stroked in the literal value
 *       of --expedition-gold, and the step-4 CSS block declares no hex at all
 *   §4  exactly ONE plate on the whole page, on the ONE light-ground section
 *       that carries no other drawn mark
 *   §5  the board's unapproved copy is ABSENT — this is the assertion that
 *       stops a proposal becoming a ship
 *   §6  no second primary was added: the free-sample CTA and the quiz CTA are
 *       still exactly one each, with their 1.19.262 hrefs and event names
 *   §7  the shared hero component is byte-unchanged, so the six other callers
 *       cannot have moved
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads markup, CSS
 *    and PHP. It does NOT prove where anything sits relative to a fold, that
 *    the quiz CTA clears 844 at 390, that nothing overflows, that the plate is
 *    faint enough to read through, or that contrast survived it. Those are
 *    BROWSER facts and were measured separately in a real headless Chrome at
 *    an asserted `window.innerWidth`, filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-direction1-step4-qa\`.
 *    A markup test that claimed them would be a fabricated verification, which
 *    is the same failure class as a fabricated review.
 *
 * ⛔ NOTHING IS WRITTEN. No post, product, price, variation, coupon, stock
 *    level, shipping setting, tax setting, payment setting, cart, order, page,
 *    option, attachment or user is created or modified by any line in this
 *    file. No form is submitted and no address enters any list.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_d1h_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_d1h_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

$theme = get_template_directory();
$home  = bhp_d1h_fetch( home_url( '/' ) );

echo "=== §0 — THE HOMEPAGE SERVES ===\n";
bhp_d1h_assert( '' !== $home, '§0.1 the homepage returns 200 with a body', $failures );
bhp_d1h_assert(
	'' !== $home
		&& false === stripos( $home, 'Fatal error' )
		&& false === stripos( $home, 'Warning: ' )
		&& false === stripos( $home, 'Notice: ' )
		&& false === stripos( $home, 'Deprecated: ' ),
	'§0.2 no PHP fatal, warning, notice or deprecation in the served document',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §1 — THE FROZEN HERO STRINGS

   The brief's first constraint: "keep the hero exactly as the founder approved
   on 1.19.252 … no copy change". Two of these are LOCKED FOUNDER COPY that no
   agent rewrites under any circumstances — the hero line (FD-460) and trust
   line A (FD-469). They are asserted as literals rather than by pattern, so a
   single changed character fails.

   ⛔ THE APOSTROPHE IS A CURLY ONE AND THAT IS DELIBERATE. WordPress's
      `wptexturize` converts the straight apostrophe in "I'm" on the way out.
      Asserting the straight form would fail on a correct page, so both forms
      are accepted for the apostrophe ONLY — every other character is exact.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §1 — THE HERO'S FROZEN STRINGS ARE BYTE-IDENTICAL ===\n";

$flat = '' === $home ? '' : preg_replace( '/\s+/', ' ', $home );

bhp_d1h_assert(
	'' !== $flat && 1 === preg_match( '/I(&#8217;|&rsquo;|\x{2019}|\')m Andrew\. ICU nurse, uncle, and the author\./u', $flat ),
	'§1.1 the hero line is present and unaltered (FD-460, LOCKED)',
	$failures
);
bhp_d1h_assert(
	'' !== $flat && false !== strpos( $flat, 'I write them, I sign the school copies myself, and I hand them over at the read-aloud.' ),
	'§1.2 trust line A is present and unaltered (FD-469 / item 81, LOCKED)',
	$failures
);
bhp_d1h_assert(
	'' !== $flat && 1 === preg_match( '/<h1[^>]*class="[^"]*home-hero__title[^"]*"[^>]*>.*?Adventure Books That Turn.*?Curiosity.*?Into Courage.*?<\/h1>/s', $home ),
	'§1.3 the H1 still reads "Adventure Books That Turn Curiosity Into Courage", emphasis intact',
	$failures
);
bhp_d1h_assert(
	'' !== $flat && false !== strpos( $flat, 'Open the book. Read the first pages free' ),
	'§1.4 the free-sample primary label is unchanged',
	$failures
);
bhp_d1h_assert(
	'' !== $flat && false !== strpos( $flat, 'Take the 30-second quiz.' ),
	'§1.5 the quiz CTA label is unchanged (it MOVED nowhere and was NOT reworded)',
	$failures
);
bhp_d1h_assert(
	'' !== $flat && false !== strpos( $flat, 'Follow Charlotte and Henry from the Mariana Trench to Mount Everest and the Amazon.' ),
	'§1.6 the hero subcopy is unchanged',
	$failures
);
bhp_d1h_assert(
	'' !== $flat && false !== strpos( $flat, 'Real places. Doors into wonder.' ),
	'§1.7 the cover-fan label is unchanged',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §2 — THE FIELD RULE

   Board sheet 3 marker ③ / sheet 4 homepage panel. What matters is not that it
   exists but that it is INERT: one of them, decorative, textless, and
   incapable of counting as a CTA.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §2 — THE FIELD RULE IS PRESENT, SINGULAR AND INERT ===\n";

bhp_d1h_assert(
	1 === substr_count( $home, 'data-bhp-field-rule="home-hero"' ),
	'§2.1 exactly ONE field rule on the whole page (found ' . substr_count( $home, 'data-bhp-field-rule' ) . ')',
	$failures
);

$rule_block = '';
if ( preg_match( '/<div class="bhp-field-rule".*?<\/div>/s', $home, $rm ) ) {
	$rule_block = $rm[0];
}
bhp_d1h_assert( '' !== $rule_block, '§2.2 the rule block is extractable from the served document', $failures );
bhp_d1h_assert(
	'' !== $rule_block && false !== strpos( $rule_block, 'aria-hidden="true"' ),
	'§2.3 it is hidden from assistive technology (it carries no information)',
	$failures
);
bhp_d1h_assert(
	'' !== $rule_block && '' === trim( wp_strip_all_tags( $rule_block ) ),
	'§2.4 it contains NO TEXT NODE — which is what makes it "no new copy"',
	$failures
);
bhp_d1h_assert(
	'' !== $rule_block
		&& false === strpos( $rule_block, '<a ' ) && false === strpos( $rule_block, '<button' )
		&& false === strpos( $rule_block, 'href' ) && false === strpos( $rule_block, 'data-bhp-event' ),
	'§2.5 it is not a control: no anchor, button, href or analytics event',
	$failures
);
bhp_d1h_assert(
	'' !== $rule_block
		&& false !== strpos( $rule_block, 'bhp-field-rule__marks' )
		&& false === stripos( $rule_block, '<svg' ),
	'§2.6 the marks are a background image, not inline SVG (wp_kses_post would strip inline SVG here)',
	$failures
);

// It renders BETWEEN the H1 and the primary invitation. Document order, which
// is the only ordering claim a markup test is entitled to make.
$pos_h1    = strpos( $home, 'home-hero__title' );
$pos_rule  = strpos( $home, 'data-bhp-field-rule' );
$pos_prim  = strpos( $home, 'home-hero__invitations--primary' );
bhp_d1h_assert(
	false !== $pos_h1 && false !== $pos_rule && false !== $pos_prim
		&& $pos_h1 < $pos_rule && $pos_rule < $pos_prim,
	'§2.7 document order is H1 -> field rule -> primary CTA (board sheet 4: "between headline and CTA")',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §3 — NO NEW HUE

   A background image CANNOT inherit `currentColor`, so both assets carry a
   baked literal. A baked literal only stays honest if something checks it —
   this is that check, and it is the same mechanism, for the same reason, as
   `bhp_blog_plate_ink()` at 1.19.261.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §3 — NO NEW HUE: BOTH ASSETS EQUAL THE GOLD TOKEN ===\n";

$theme_css = (string) file_get_contents( $theme . '/style.css' );
preg_match( '/--expedition-gold:\s*(#[0-9a-fA-F]{3,8})/', $theme_css, $gm );
$token_gold = strtolower( $gm[1] ?? '' );

bhp_d1h_assert( '' !== $token_gold, '§3.1 the --expedition-gold token is readable from style.css', $failures );
bhp_d1h_assert(
	function_exists( 'bhp_field_rule_ink' ) && strtolower( bhp_field_rule_ink() ) === $token_gold,
	sprintf( '§3.2 bhp_field_rule_ink() [%s] equals the token [%s]', function_exists( 'bhp_field_rule_ink' ) ? bhp_field_rule_ink() : 'MISSING', $token_gold ?: 'not found' ),
	$failures
);

foreach ( array( 'field-marks.svg', 'plate-compass-rose.svg' ) as $asset ) {
	$path = $theme . '/assets/img/' . $asset;
	$svg  = file_exists( $path ) ? (string) file_get_contents( $path ) : '';
	bhp_d1h_assert( '' !== $svg, "§3.3 {$asset} ships with the theme", $failures );
	preg_match_all( '/#[0-9a-fA-F]{3,8}\b/', $svg, $hx );
	$uniq = array_values( array_unique( array_map( 'strtolower', $hx[0] ) ) );
	bhp_d1h_assert(
		'' !== $svg && array( $token_gold ) === $uniq,
		sprintf( '§3.4 %s declares exactly ONE colour and it is the gold token (found: %s)', $asset, $uniq ? implode( ', ', $uniq ) : 'none' ),
		$failures
	);
}

/*
 * The step-4 CSS block itself declares no colour at all.
 *
 * ⚠️ THE SLICE MUST START AT THE BANNER'S OPENING DELIMITER, NOT AT THE MARKER
 *    TEXT INSIDE IT. The marker sits on the banner's second line, so a slice
 *    starting there begins in the MIDDLE of a comment with no opener — and the
 *    comment-stripping regex below would then leave the rest of that banner
 *    standing while stripping the NEXT comment instead. The first run of this
 *    suite failed exactly that way: §3.6 reported `#D9A45F` "declared" when
 *    the only occurrence was the banner sentence explaining that the assets
 *    carry it. Rewinding to the banner opener is the fix; loosening the
 *    assertion would have been the wrong one. (This is the same defect class
 *    as `test-homepage-warmth`'s unbounded `$p9_tail`, found in this session.)
 */
$p4_mark = strpos( $theme_css, '1.19.263 (2026-08-19) — THE FIELD RULE AND THE PLATE' );
$p4_pos  = ( false === $p4_mark ) ? false : strrpos( substr( $theme_css, 0, $p4_mark ), '/*' );
$p4_tail = ( false === $p4_pos ) ? '' : substr( $theme_css, $p4_pos );
bhp_d1h_assert( '' !== $p4_tail, '§3.5 the step-4 CSS block is present in style.css', $failures );

/*
 * Everything below reads the COMMENT-STRIPPED block. This codebase writes
 * essay-length CSS comments on purpose, and those essays quote the very
 * things these assertions forbid — the hex the assets carry, the property
 * names that make the decorations inert. Asserting over prose would make an
 * accurate comment fail the build.
 */
$p4_code = (string) preg_replace( '#/\*.*?\*/#s', '', $p4_tail );

preg_match_all( '/#[0-9a-fA-F]{3,8}\b/', $p4_code, $p4hex );
bhp_d1h_assert(
	'' !== $p4_tail && empty( $p4hex[0] ),
	sprintf( '§3.6 the step-4 CSS declares no hex colour at all%s', ! empty( $p4hex[0] ) ? ' (found: ' . implode( ', ', array_unique( $p4hex[0] ) ) . ')' : '' ),
	$failures
);
bhp_d1h_assert(
	'' !== $p4_tail
		&& 2 === substr_count( $p4_code, 'background-repeat: no-repeat' )
		&& 1 === substr_count( $p4_code, 'pointer-events: none' ),
	sprintf(
		'§3.7 both decorations are ONE mark each (2 no-repeat, found %d) and the plate cannot swallow a tap (1 pointer-events, found %d)',
		substr_count( $p4_code, 'background-repeat: no-repeat' ),
		substr_count( $p4_code, 'pointer-events: none' )
	),
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §4 — ONE PLATE, ON THE ONE UNMARKED LIGHT GROUND

   The brief's constraint is "one mark per screen". The screen part is a
   browser fact and is measured elsewhere; what this file can prove is the
   part that makes that measurement STAY true: there is exactly one plate on
   the page, and it is on the section that carries no other drawn mark.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §4 — EXACTLY ONE PLATE, ON THE UNMARKED LIGHT GROUND ===\n";

bhp_d1h_assert(
	1 === substr_count( $home, 'data-bhp-plate=' ),
	'§4.1 exactly ONE plate on the whole homepage (found ' . substr_count( $home, 'data-bhp-plate=' ) . ')',
	$failures
);
bhp_d1h_assert(
	1 === substr_count( $home, 'data-bhp-plate="home-audience-gateway"' ),
	'§4.2 it is on #home-audience-gateway, the one light-ground section with no other drawn mark',
	$failures
);

$gw_src = (string) file_get_contents( $theme . '/template-parts/components/audience-gateway.php' );
bhp_d1h_assert(
	false === stripos( $gw_src, '<svg' ),
	'§4.3 that section still carries no other SVG (the measurement that chose it stays true)',
	$failures
);
$obk_src = (string) file_get_contents( $theme . '/template-parts/components/home-open-the-book.php' );
bhp_d1h_assert(
	false === strpos( $obk_src, 'data-bhp-plate' ) && false !== strpos( $obk_src, 'home-open-book__divider' ),
	'§4.4 #home-open-the-book keeps its divider and did NOT also get a plate (that is why it was rejected)',
	$failures
);
$plate_block = '';
if ( preg_match( '/<div class="audience-gateway__plate".*?<\/div>/s', $home, $pm ) ) {
	$plate_block = $pm[0];
}
bhp_d1h_assert(
	'' !== $plate_block && false !== strpos( $plate_block, 'aria-hidden="true"' )
		&& '' === trim( wp_strip_all_tags( $plate_block ) ),
	'§4.5 the plate is decorative and textless',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §5 — THE BOARD'S UNAPPROVED COPY IS ABSENT

   ⭐ THIS IS THE MOST IMPORTANT ASSERTION IN THE FILE. The direction board's
      own README classes these lines as PROPOSALS: "Unapproved copy. Written
      this session to demonstrate the tone move." Standing rule §9: approved
      copy is locked, propose changes rather than make them. A build that
      quietly shipped a mock's placeholder line would have turned a proposal
      into live customer-facing copy without anyone deciding to.

   ⛔ THE QUESTION EYEBROW IS REPORTED TO GANDALF AS PROPOSED, NOT SHIPPED.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §5 — NO UNAPPROVED BOARD COPY SHIPPED ===\n";

$proposed = array(
	'What would you find down there?',
	'three real places',
	'What if the deepest place on Earth had a door?',
	'How deep is the deepest place on Earth?',
	'the book this came from',
);
foreach ( $proposed as $line ) {
	bhp_d1h_assert(
		'' !== $flat && false === stripos( $flat, $line ),
		'§5.x the PROPOSED line "' . $line . '" is NOT on the homepage',
		$failures
	);
}

// And no new customer-facing "we" arrived with any of it (standing rule §9.1).
$hero_region = '';
if ( preg_match( '/<section[^>]*id="home-hero".*?<\/section>/s', $home, $hm ) ) {
	$hero_region = preg_replace( '/\s+/', ' ', wp_strip_all_tags( $hm[0] ) );
}
bhp_d1h_assert(
	'' !== $hero_region && 0 === preg_match( '/\b(we|us|our)\b/i', $hero_region ),
	'§5.6 the hero contains no customer-facing "we"/"us"/"our" (standing rule §9.1)',
	$failures
);
bhp_d1h_assert(
	'' !== $hero_region && false === strpos( $hero_region, '—' ),
	'§5.7 no em dash entered the hero',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §6 — NO SECOND PRIMARY WAS ADDED

   FD-479 limb 3. The count itself is a browser fact and is measured in a real
   headless Chrome; what this proves is that the DOCUMENT still contains one of
   each control, with the hrefs and event names 1.19.262 shipped.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §6 — THE TWO HERO CONTROLS ARE STILL EXACTLY ONE EACH ===\n";

bhp_d1h_assert(
	1 === substr_count( $home, 'data-bhp-source="home_hero_open_book"' ),
	'§6.1 exactly one free-sample primary, same data-bhp-source as 1.19.262',
	$failures
);
bhp_d1h_assert(
	1 === substr_count( $home, 'data-bhp-source="home_hero_quiz"' ),
	'§6.2 exactly one hero quiz CTA, same data-bhp-source as 1.19.262',
	$failures
);
/*
 * ⚠️ COUNT THE CLASS ON AN ELEMENT, NOT THE STRING IN THE DOCUMENT. The bare
 *    string `home-hero__invite--primary` appears TWICE on this page and both
 *    are correct: once on the anchor, and once inside step 1's
 *    `data-bhp-offer-watch=".home-hero__invite--primary,…"` — the selector
 *    list that makes the mobile header offer hide itself while the hero
 *    primary is on screen. The first run of this suite failed here and the
 *    finding was step 1's mechanism working, not a duplicate control.
 *    Matching the full class attribute pair is what distinguishes them.
 */
bhp_d1h_assert(
	1 === substr_count( $home, 'home-hero__invite home-hero__invite--primary' )
		&& 1 === substr_count( $home, 'home-hero__invite home-hero__invite--ghost' ),
	sprintf(
		'§6.3 exactly one primary element and one ghost element (found %d / %d)',
		substr_count( $home, 'home-hero__invite home-hero__invite--primary' ),
		substr_count( $home, 'home-hero__invite home-hero__invite--ghost' )
	),
	$failures
);
// And step 1's watch selector still names the hero primary, which is the
// mechanism behind "the header offer stays hidden while the hero CTA is in view".
bhp_d1h_assert(
	false !== strpos( $home, 'data-bhp-offer-watch' )
		&& false !== strpos( $home, '.home-hero__invite--primary' ),
	'§6.3b the header offer still watches the hero primary (step 1\'s suppression is intact)',
	$failures
);
bhp_d1h_assert(
	false !== strpos( $home, 'data-bhp-event="contextual_cta_click"' ) && false !== strpos( $home, 'data-bhp-event="quiz_cta_clicked"' ),
	'§6.4 both event names are the ones already in the dataLayer vocabulary — no new event was minted',
	$failures
);
// Step 1's header offer still suppresses itself while the hero primary is in view.
bhp_d1h_assert(
	1 === substr_count( $home, 'class="bhp-header-offer"' ) || 1 === substr_count( $home, 'bhp-header-offer' ),
	'§6.5 the header offer renders exactly once and was not duplicated by this step',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §7 — THE SHARED HERO COMPONENT IS UNCHANGED

   Six other pages call `template-parts/components/hero.php`. The whole reason
   the field rule was prepended to `after_title` rather than given its own
   argument is that the component must not move. This asserts the absence.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §7 — THE SHARED HERO COMPONENT DID NOT MOVE ===\n";

$hero_src = (string) file_get_contents( $theme . '/template-parts/components/hero.php' );
bhp_d1h_assert(
	false === strpos( $hero_src, 'field-rule' ) && false === strpos( $hero_src, 'bhp_field_rule' )
		&& false === strpos( $hero_src, 'plate' ),
	'§7.1 hero.php names neither decoration — this step did not touch the shared component',
	$failures
);
bhp_d1h_assert(
	1 === preg_match( "/'after_title'\s*=>\s*''\s*,/", $hero_src ),
	'§7.2 after_title still defaults to empty, so the six other callers render nothing new',
	$failures
);
bhp_d1h_assert(
	function_exists( 'bhp_field_rule' ) && '' !== bhp_field_rule( 'probe' )
		&& false !== strpos( bhp_field_rule( 'probe' ), 'data-bhp-field-rule="probe"' ),
	'§7.3 bhp_field_rule() is callable and honours its context argument',
	$failures
);
// The renderer writes nothing, anywhere.
$fm_src = (string) file_get_contents( $theme . '/inc/field-marks.php' );
bhp_d1h_assert(
	false === strpos( $fm_src, 'wp_update_post' ) && false === strpos( $fm_src, 'update_option' )
		&& false === strpos( $fm_src, 'update_post_meta' ) && false === strpos( $fm_src, '$wpdb' )
		&& false === strpos( $fm_src, 'add_action' ) && false === strpos( $fm_src, 'add_filter' ),
	'§7.4 inc/field-marks.php writes nothing and registers no hook — it is pure markup',
	$failures
);

echo "\n=====================================================\n";
if ( empty( $failures ) ) {
	echo "ALL ASSERTIONS PASSED\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( count( $failures ) . ' assertion(s) failed.' );
	}
}
