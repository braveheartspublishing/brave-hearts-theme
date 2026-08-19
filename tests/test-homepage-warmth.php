<?php
/**
 * Brave Hearts — THE HOMEPAGE WARMTH BUILD, AND EVERYTHING IT MUST NOT BREAK.
 *
 * CYCLE164-LD-HOMEPAGE-WARMTH (2026-08-18, theme 1.19.241).
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-homepage-warmth.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS FILE IS FOR
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Source: the Homepage Warmth Board (`design-creative` / Legolas, `FD-391`,
 * 2026-08-17). Andrew Signore opened its taste gate 2026-08-18 (RELAYED
 * through the Chief of Staff, NOT witnessed first-hand): "Its really good -
 * needs a few tweaks but I really like the redesign lets mock it up on
 * staging then I can nit-pick the details."
 *
 * FOUR SECTIONS, and §3 is the one that matters most:
 *
 *   §1  THE BUILD          — the three moves are actually in the document.
 *   §2  THE OMISSIONS      — the things deliberately NOT built are NOT there.
 *                            An omission that is not asserted is an omission
 *                            that quietly comes back.
 *   §3  THE CONTROL DIFF   — every commerce, tracking and funnel element the
 *                            homepage had at 1.19.240 is STILL THERE and
 *                            still appears exactly the number of times it did.
 *                            A warmer homepage that drops an event is a
 *                            regression, and this section is what catches it.
 *   §4  THE OTHER CALLERS  — the six other pages that share `hero.php` are
 *                            unaffected by the three new slots.
 *
 * ⛔ WHAT THIS SUITE CANNOT PROVE, STATED RATHER THAN GLOSSED. It asserts the
 *    RENDERED MARKUP of real pages fetched over HTTP. It does not prove a
 *    pixel, a computed style, a font that actually loaded, or that a
 *    dataLayer push actually fired in a browser. Those were checked
 *    separately, in a real browser at asserted `window.innerWidth`, and a
 *    green run here is not visual QA.
 *
 * ⛔ NOTHING IS WRITTEN. No product, price, coupon, stock level, shipping
 *    setting, cart, order, post, page or option is touched by any line here.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_hw_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_hw_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** Byte offset of a needle, or -1. Used for ORDER assertions. */
function bhp_hw_at( $haystack, $needle ) {
	$pos = strpos( $haystack, $needle );
	return ( false === $pos ) ? -1 : (int) $pos;
}

$home = bhp_hw_fetch( home_url( '/' ) );
bhp_hw_assert( '' !== $home, 'the homepage renders (HTTP 200, non-empty body)', $failures );

if ( '' === $home ) {
	WP_CLI::error( 'Could not fetch the homepage; no further assertion is meaningful.' );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §1 — THE BUILD
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §1 — THE THREE MOVES ARE IN THE DOCUMENT ===\n";

/* Move 1: the founder chip, in the hero, above the fold. */
bhp_hw_assert(
	1 === substr_count( $home, 'class="home-founder-chip"' ),
	'§1.1 the founder chip renders EXACTLY ONCE',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'home-founder-chip__photo' )
		&& false !== strpos( $home, 'founder-and-charlotte.webp' ),
	'§1.1 it uses the EXISTING founder photograph, not a new asset',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Andrew Signore with Charlotte and a Brave Hearts book' ),
	'§1.1 it carries the approved alt text, unchanged from #first-reader',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'ICU nurse, uncle, and the author' ),
	'§1.1 the spoken line is present (AWAITING ANDREW - see the build report)',
	$failures
);
/*
 * The board's own sheet 6 marks "The person who packs your book" as
 * "NOT VERIFIED - written by me, and it is an operational claim". It must not
 * be on the page. This assertion is the guard that keeps it off.
 */
bhp_hw_assert(
	false === stripos( $home, 'person who packs your book' ),
	'§1.1 the UNVERIFIED role line "the person who packs your book" is NOT shipped',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Founder, Brave Hearts Publishing' ),
	'§1.1 the role line is the already-live "Founder, Brave Hearts Publishing"',
	$failures
);
/* Above the fold means before the H1 in source order, not merely present. */
bhp_hw_assert(
	bhp_hw_at( $home, 'home-founder-chip' ) > 0
		&& bhp_hw_at( $home, 'home-founder-chip' ) < bhp_hw_at( $home, 'home-hero__title' ),
	'§1.1 the chip precedes the H1 in DOM order (a person speaks first)',
	$failures
);

/* Move 2: the drawn underline, on exactly one word. */
bhp_hw_assert(
	1 === substr_count( $home, 'home-hero__title-mark' ),
	'§1.2 the drawn-underline wrapper appears EXACTLY ONCE (one word per headline, never two)',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, '<em class="home-hero__title-mark">Curiosity</em>' ),
	'§1.2 it wraps the word "Curiosity" and the H1 text is otherwise unchanged',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Adventure Books That Turn' )
		&& false !== strpos( $home, 'Into Courage' ),
	'§1.2 the rest of the approved H1 survives the split intact',
	$failures
);

/* Move 2b: the two invitations. */
bhp_hw_assert(
	1 === substr_count( $home, 'class="home-hero__invitations"' ),
	'§1.3 the hero invitation cluster renders EXACTLY ONCE',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Open the book. Read the first pages free' ),
	'§1.3 the primary invitation is present',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Take the 30-second quiz.' ),
	'§1.3 the secondary invitation reuses the string already live in audience-gateway.php',
	$failures
);
/*
 * Standing rule 9.1's rail: no em dashes in his copy. The board wrote both
 * of these with one. This asserts the restructure actually happened, on the
 * rendered page, in every form an em dash can reach a browser in.
 */
$em_dash_forms = array( "\xE2\x80\x94", '&mdash;', '&#8212;', '&#x2014;' );
$hero_slice    = '';
$hero_open     = bhp_hw_at( $home, 'id="home-hero"' );
$hero_close    = bhp_hw_at( $home, 'id="home-open-the-book"' );
if ( $hero_open >= 0 && $hero_close > $hero_open ) {
	$hero_slice = substr( $home, $hero_open, $hero_close - $hero_open );
}
$em_in_hero = false;
foreach ( $em_dash_forms as $form ) {
	if ( '' !== $hero_slice && false !== strpos( $hero_slice, $form ) ) {
		$em_in_hero = true;
	}
}
bhp_hw_assert( '' !== $hero_slice, '§1.3 the hero slice could be isolated for the em-dash check', $failures );
bhp_hw_assert( ! $em_in_hero, '§1.3 NO em dash anywhere between the hero and the new section (rule 9.1 rail)', $failures );

/* Move 3: "Open the book". */
bhp_hw_assert(
	1 === substr_count( $home, 'id="home-open-the-book"' ),
	'§1.4 the "Open the book" section renders EXACTLY ONCE',
	$failures
);
/*
 * ⭐ 1.19.245 — THE SELECTOR CHANGED BECAUSE THE MARKUP CHANGED, AND THE
 *    ASSERTION IS COUNTED ON THE STABLE HALF OF THE CLASS ATTRIBUTE.
 *
 * PASS3 appends a per-slot aspect modifier, so the attribute is now
 * `class="home-open-book__spread home-open-book__spread--tall"` (slot 1) or
 * `...--square"` (slots 2 and 3). The old exact-string count would have read
 * ZERO and failed for the wrong reason — the photographs would all still be
 * on the page. Counting the opening `class="home-open-book__spread ` prefix
 * keeps the assertion about "three spreads render" instead of about one
 * particular attribute spelling.
 */
bhp_hw_assert(
	3 === substr_count( $home, 'class="home-open-book__spread home-open-book__spread--' ),
	'§1.4 all THREE page photographs resolve',
	$failures
);

/*
 * ⭐ 1.19.243 — THESE THREE ASSERTIONS REPLACE THE 1.19.242 ONES, WHICH WERE
 *    ASSERTING THE DEFECT ANDREW REPORTED.
 *
 * The old test demanded the words "How Deep Is the Mariana Trench diagram",
 * "How Tall Is Mount Everest diagram" and "Connected Amazon ecology diagram"
 * — i.e. it PASSED precisely because the section showed mid-book diagram
 * spreads under a heading that promises the first pages. A green suite is
 * worth nothing if it is green about the wrong thing.
 *
 * ⛔ THE REPLACEMENTS ARE STRICTER, NOT LOOSER. They pin the page ORDER
 *    ("Pages go in 1,2,3 order" — Andrew, 2026-08-18), pin the alt text to
 *    the real first pages, and add a NEGATIVE assertion that the three
 *    diagram spreads have actually left this section. Without that last one
 *    a future edit could re-add them alongside and still pass.
 */
/*
 * ⛔ THESE ASSERTIONS ARE SCOPED TO THE SECTION, NOT TO THE WHOLE PAGE, AND
 *    BOTH SCOPING MISTAKES WERE MADE ONCE AND CAUGHT BY THIS SUITE. Recorded
 *    so they are not made a second time:
 *
 *    1. The attachment SLUG ("mariana-trench-page-1") is the post_name. It
 *       never appears in rendered HTML — the URL carries the FILENAME
 *       ("mt-first-pages-01-...webp") instead. Order is therefore asserted on
 *       the alt text, which is the thing a reader and a screen reader
 *       actually receive.
 *    2. The three diagram spreads legitimately still render ELSEWHERE on this
 *       page, in the Look Inside carousel. A page-wide "the diagrams are
 *       gone" assertion is simply false and would have forced someone to
 *       delete a working gallery to make a test pass.
 */
$bhp_hw_sec_start = bhp_hw_at( $home, 'id="home-open-the-book"' );
$bhp_hw_sec_end   = bhp_hw_at( $home, 'id="where-you-will-find-us"' );
$bhp_hw_section   = ( $bhp_hw_sec_start >= 0 && $bhp_hw_sec_end > $bhp_hw_sec_start )
	? substr( $home, $bhp_hw_sec_start, $bhp_hw_sec_end - $bhp_hw_sec_start )
	: '';
bhp_hw_assert(
	'' !== $bhp_hw_section,
	'§1.4 the "Open the book" section could be isolated for the page-order checks',
	$failures
);

$bhp_hw_p1 = bhp_hw_at( $bhp_hw_section, 'Page 1 of Adventures of Charlotte and Henry: The Mariana Trench' );
$bhp_hw_p2 = bhp_hw_at( $bhp_hw_section, 'Page 2 of Adventures of Charlotte and Henry: The Mariana Trench' );
$bhp_hw_p3 = bhp_hw_at( $bhp_hw_section, 'Page 3 of Adventures of Charlotte and Henry: The Mariana Trench' );
bhp_hw_assert(
	$bhp_hw_p1 >= 0 && $bhp_hw_p2 > $bhp_hw_p1 && $bhp_hw_p3 > $bhp_hw_p2,
	'§1.4 the three slots are the REAL first pages, in 1-2-3 order (Andrew, 2026-08-18)',
	$failures
);
bhp_hw_assert(
	false === strpos( $bhp_hw_section, 'How Deep Is the Mariana Trench diagram' )
		&& false === strpos( $bhp_hw_section, 'How Tall Is Mount Everest diagram' )
		&& false === strpos( $bhp_hw_section, 'Connected Amazon ecology diagram' ),
	'§1.4 no mid-book DIAGRAM spread stands in for a first page IN THIS SECTION',
	$failures
);
bhp_hw_assert(
	3 === substr_count( $bhp_hw_section, 'held open by hand.' ),
	'§1.4 all three page photographs carry their honest media-record alt text',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Read the first pages before you decide.' )
		&& false !== strpos( $home, 'At the market, people pick a book up' ),
	'§1.4 the section copy is present (NEW COPY, awaiting Andrew - see the build report)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1.5 — 1.19.243. NEITHER "READ THE FIRST PAGES" CONTROL LEAVES THE HOMEPAGE.
 *
 * Andrew, 2026-08-18, on 1.19.242: "When you hit read the first pages it goes
 * direct to the collection page... Also the 'read the first pages' CTA goes to
 * the collection page again - this is all incorrect."
 *
 * Both controls now target the on-page section. The negative assertion is the
 * one that matters: it is what stops a future edit from restoring the
 * convenient /complete-collection/ href and shipping this bug a third time.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_hw_assert(
	false !== strpos( $home, 'data-bhp-source="home_hero_open_book"' )
		&& preg_match( '/href="#home-open-the-book"[^>]*data-bhp-source="home_hero_open_book"/', $home ) === 1,
	'§1.5 the HERO "read the first pages" invitation targets #home-open-the-book',
	$failures
);
/*
 * ⚠️ SUPERSEDED 2026-08-19 BY 1.19.245 (PASS3), AND QUOTED RATHER THAN
 *    SILENTLY DELETED, because it records a real earlier instruction:
 *
 *      bhp_hw_assert(
 *          preg_match( '/href="#home-open-the-book"[^>]*data-bhp-source="home_open_the_book"/', $home ) === 1,
 *          '§1.5 the SECTION "read the first pages" CTA targets #home-open-the-book',
 *          $failures );
 *      bhp_hw_assert(
 *          2 === substr_count( $home, 'href="#home-open-the-book"' ),
 *          '§1.5 EXACTLY the two read-the-first-pages controls carry the anchor, no more',
 *          $failures );
 *
 *    Both asserted a SECTION CTA that Andrew has since removed outright
 *    ("There is no need to have the same 'Read the first pages free' CTA
 *    right below the actual pages 1-3"). Their replacements are in §1.6.
 *    The HERO invitation's anchor assertion immediately above is UNCHANGED
 *    and still runs — that control is untouched by PASS3.
 */
bhp_hw_assert(
	1 === substr_count( $home, 'href="#home-open-the-book"' ),
	'§1.5 EXACTLY ONE control now carries the anchor: the hero invitation (the section CTA became a shop link in 1.19.245)',
	$failures
);

/* Move 3b: the booth, promoted. */
bhp_hw_assert(
	1 === substr_count( $home, 'id="where-you-will-find-us"' ),
	'§1.5 the booth section still renders EXACTLY ONCE (it was MOVED, not copied)',
	$failures
);
bhp_hw_assert(
	bhp_hw_at( $home, 'id="where-you-will-find-us"' ) < bhp_hw_at( $home, 'id="explore-world"' )
		&& bhp_hw_at( $home, 'id="where-you-will-find-us"' ) < bhp_hw_at( $home, 'id="first-reader"' ),
	'§1.5 the booth now precedes #explore-world and #first-reader (promoted from position 9 to 6)',
	$failures
);
bhp_hw_assert(
	bhp_hw_at( $home, 'id="home-open-the-book"' ) > bhp_hw_at( $home, 'id="kirkus-credibility-home"' )
		&& bhp_hw_at( $home, 'id="home-open-the-book"' ) < bhp_hw_at( $home, 'id="where-you-will-find-us"' ),
	'§1.5 the new section sits between Kirkus and the booth, exactly as documented',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §1.6 — 1.19.245 (PASS3). THE FOUR THINGS ANDREW ASKED FOR ON HIS PHONE.
 *
 * Verbatim (RELAYED through the Chief of Staff, NOT witnessed first-hand):
 *   "The 'Real World...6-9' needs to be removed - it looks to out of place."
 *   "The first page of MT is zoomed in on the chapter icon- this screen shot
 *    to have the icon and all the words on the page. I like the page 2 and 3
 *    cropped images."
 *   "There is no need to have the same 'Read the first pages free' CTA right
 *    below the actual pages 1-3 thats just not a good CTA - Put Shop the
 *    books and send to shop page."
 *
 * ⛔ EACH POSITIVE ASSERTION IS PAIRED WITH A NEGATIVE ONE. A test that only
 *    checks the new thing arrived cannot catch the old thing failing to
 *    leave, and every defect in this cycle has been an old thing that stayed.
 * ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §1.6 — PASS3: THE EYEBROW, THE FULL PAGE 1, THE SHOP CTA ===\n";

/* --- 1.6a THE EYEBROW LEAVES THE HOMEPAGE, AND ONLY THE HOMEPAGE. --- */
bhp_hw_assert(
	false === strpos( $home, 'home-hero__eyebrow' ),
	'§1.6a the hero eyebrow element is NOT rendered on the homepage at all',
	$failures
);
bhp_hw_assert(
	false === strpos( $home, 'REAL-WORLD ADVENTURE BOOKS FOR AGES' ),
	'§1.6a the "Real World...6-9" string is gone from the homepage',
	$failures
);
/*
 * The claim is not lost from the page — it moved home in 2026-08-05 on
 * Andrew's own instruction. This assertion is what stops a future "tidy-up"
 * from concluding the age range is now unstated and re-adding a strip.
 */
/*
 * ⛔ THE EN DASH IS ASSERTED AS A LITERAL UTF-8 CHARACTER, NOT AS `&#8211;`.
 *    `esc_html_e()` escapes `& < > " '` and leaves every other character
 *    alone, so "Ages 6–9" reaches the document as the real en dash. A first
 *    draft of this assertion looked for the numeric entity and would have
 *    failed against a page that was perfectly correct.
 */
bhp_hw_assert(
	false !== strpos( $home, 'home-trust-proof__badge' )
		&& false !== strpos( $home, "Ages 6\xE2\x80\x939" ),
	'§1.6a the Ages 6-9 pill STILL renders in #home-trust-proof (the claim moved in 2026-08-05, it did not vanish)',
	$failures
);
bhp_hw_assert(
	false === stripos( $home, 'ages 5-9' ) && false === stripos( $home, "ages 5\xE2\x80\x939" ),
	'§1.6a reading age is still 6-9 and never 5-9 (standing rule §9)',
	$failures
);

/* --- 1.6b PAGE 1 IS THE FULL PAGE; PAGES 2 AND 3 STAY SQUARE. --- */
bhp_hw_assert(
	1 === substr_count( $home, 'home-open-book__spread--tall' )
		&& 2 === substr_count( $home, 'home-open-book__spread--square' ),
	'§1.6b exactly ONE slot is tall (page 1) and TWO stay square (pages 2 and 3, which Andrew said he likes)',
	$failures
);
/*
 * Asserted on the FILENAME, because that is what reaches the HTML — the
 * attachment slug is the post_name and never appears in rendered markup.
 * This is the same trap §1.4 records having fallen into once already.
 *
 * ⚠️ AND ON THE `src` ATTRIBUTE SPECIFICALLY, NOT ON A BARE OCCURRENCE COUNT.
 *    The first version of this assertion demanded the filename appear
 *    EXACTLY ONCE and failed on staging — not because the page was wrong but
 *    because the page was right: requesting the `full` size emits a nine-rung
 *    `srcset`, so the base filename legitimately appears TEN times. The page
 *    was inspected before the test was changed, and it was the test that was
 *    wrong about the page. Pinning `src="…-1600.webp"` asserts the one thing
 *    that actually matters — which file a browser with no srcset support, and
 *    every crawler, receives — and is immune to the derivative ladder
 *    changing underneath it.
 */
bhp_hw_assert(
	preg_match( '#src="[^"]*mt-first-pages-01-chapter1-opening-1600\.webp"#', $bhp_hw_section ) === 1,
	'§1.6b slot 1 serves the FULL-PAGE master (icon and all the words), not the square crop',
	$failures
);
bhp_hw_assert(
	preg_match( '#<img width="1600" height="2133"#', $bhp_hw_section ) === 1,
	'§1.6b slot 1 declares the page\'s real 3:4 intrinsic size, so no crop or letterbox can occur',
	$failures
);
bhp_hw_assert(
	false === strpos( $bhp_hw_section, 'mt-first-pages-01-chapter1-opening-square' ),
	'§1.6b the SQUARE page-1 crop Andrew rejected is no longer in this section',
	$failures
);
bhp_hw_assert(
	false !== strpos( $bhp_hw_section, 'mt-first-pages-02-page2-under-the-bed-square' )
		&& false !== strpos( $bhp_hw_section, 'mt-first-pages-03-page3-text-spread-square' ),
	'§1.6b pages 2 and 3 are still the SQUARE crops, untouched',
	$failures
);
/* The order assertion in §1.4 runs on alt text, which is identical between
   3382 and 3385 by design — so it survives the swap and still pins 1-2-3. */

/* --- 1.6c THE SECTION CTA SELLS, AND POINTS AT THE REAL SHOP PAGE. --- */
bhp_hw_assert(
	false !== strpos( $bhp_hw_section, 'Shop the books' ),
	'§1.6c the section CTA carries Andrew\'s own label, "Shop the books"',
	$failures
);
bhp_hw_assert(
	false === strpos( $bhp_hw_section, 'Read the first pages free' ),
	'§1.6c the duplicated "Read the first pages free" CTA is GONE from the section',
	$failures
);
/*
 * The hero invitation still legitimately contains that phrase. Scoping this
 * to the section rather than the page is deliberate: a page-wide assertion
 * would be false and would force the hero button to be deleted to go green.
 */
bhp_hw_assert(
	false !== strpos( $home, 'Open the book. Read the first pages free' ),
	'§1.6c the HERO invitation keeps its wording (the removal was section-scoped)',
	$failures
);
bhp_hw_assert(
	preg_match( '#href="[^"]*/shop/"[^>]*data-bhp-source="home_open_the_book_shop"#', $bhp_hw_section ) === 1,
	'§1.6c the section CTA links to /shop/ and emits the renamed source',
	$failures
);
bhp_hw_assert(
	false === strpos( $home, 'data-bhp-source="home_open_the_book"' ),
	'§1.6c the OLD source name is fully retired (renamed, not left emitting alongside)',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'data-bhp-source="home_open_the_book_quiz"' ),
	'§1.6c the section ghost button\'s quiz event is untouched',
	$failures
);
/*
 * The section must not send anyone to /complete-collection/ again. This is
 * the third cycle in which a control in this section pointed somewhere its
 * label did not promise; the negative assertion is the memory.
 */
bhp_hw_assert(
	false === strpos( $bhp_hw_section, '/complete-collection/' ),
	'§1.6c no control in this section points at /complete-collection/ (the 1.19.242 defect stays fixed)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §2 — THE OMISSIONS. Each of these is a decision, and each is guarded.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §2 — WHAT WAS DELIBERATELY NOT BUILT IS NOT ON THE PAGE ===\n";

bhp_hw_assert(
	false === stripos( $home, 'must not survive a build' )
		&& false === stripos( $home, 'Founder Reel' ),
	'§2.1 the board\'s red-dashed Instagram Reel placeholder did NOT survive the build',
	$failures
);
bhp_hw_assert(
	false === stripos( $home, "I'd hand you one" )
		&& false === stripos( $home, 'hand you one and let your kid read' ),
	'§2.2 the UNVERIFIED booth quote is NOT attributed to Andrew anywhere',
	$failures
);
bhp_hw_assert(
	false === stripos( $home, 'Boise farmers market' ),
	'§2.3 the market is still NOT named by city (the file carries no GPS; a named location is a claim)',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Brave Hearts at a farmers market, May 2026.' ),
	'§2.3 the EXISTING approved caption is the one that renders',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'roll-up banner for Adventures of Charlotte and Henry' ),
	'§2.4 the booth photograph is UNCROPPED and its approved alt text is untouched',
	$failures
);
bhp_hw_assert(
	false === stripos( $home, '30-Day Guarantee' ) && false === stripos( $home, '30 Day Guarantee' ),
	'§2.5 no guarantee claim was added to this page (it is not live homepage copy, and "guarantee" is on the theme\'s own forbidden-claim list)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §3 — THE CONTROL DIFF. THE SECTION THAT MATTERS.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §3 — CONTROL DIFF: EVERYTHING 1.19.240 DID, THE PAGE STILL DOES ===\n";

/* 3a. Commerce. The paperback-default band shipped the same night; it must not regress. */
bhp_hw_assert(
	false !== strpos( $home, 'home-collection-feature' ) || false !== strpos( $home, 'Complete Collection' ),
	'§3.1 the Complete Collection band still renders',
	$failures
);
bhp_hw_assert(
	bhp_hw_at( $home, 'Complete Collection' ) > 0
		&& bhp_hw_at( $home, 'id="home-open-the-book"' ) > bhp_hw_at( $home, 'id="home-trust-proof"' ),
	'§3.1 the band and #home-trust-proof still precede the new section (Andrew\'s 2026-08-05 order holds)',
	$failures
);
bhp_hw_assert(
	1 === substr_count( $home, 'id="home-trust-proof"' )
		&& 1 === substr_count( $home, 'id="kirkus-credibility-home"' ),
	'§3.1 the trust-proof band and the Kirkus section each still render exactly once',
	$failures
);
bhp_hw_assert(
	1 === substr_count( $home, 'href="#kirkus-credibility-home"' ),
	'§3.1 the claim-to-evidence Kirkus link still renders exactly once (F19)',
	$failures
);

/* 3b. The look-inside gallery. Still ONE per page: the new section is not a second one. */
/*
 * The needle carries its closing quote ON PURPOSE. `data-bhp-gallery-count`
 * is ALSO a prefix of `data-bhp-gallery-counter`, the slide counter inside
 * the same gallery, so the bare string matches twice for ONE gallery. The
 * first run of this suite failed here and the page was innocent -- recorded
 * rather than quietly corrected, because a test that cries wolf gets
 * disabled and then the real regression ships.
 */
bhp_hw_assert(
	1 === substr_count( $home, 'data-bhp-gallery-count="' ),
	'§3.2 EXACTLY ONE interactive Look Inside gallery on the page (the new spreads are static links, not a second gallery)',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'data-bhp-source="look_inside_section"' )
		|| false !== strpos( $home, 'data-bhp-source="look_inside_hero"' ),
	'§3.2 the gallery still carries its analytics context attributes',
	$failures
);

/* 3c. The funnel. One quiz launcher, one modal, unchanged auto-open contract. */
bhp_hw_assert(
	1 === substr_count( $home, 'data-bhp-quiz-launcher' ),
	'§3.3 EXACTLY ONE quiz launcher on the page - the hero button is a LINK, not a second launcher',
	$failures
);
bhp_hw_assert(
	1 === substr_count( $home, 'data-bhp-quiz-modal-location' ),
	'§3.3 exactly one quiz modal, so initLauncher cannot arm its timers twice',
	$failures
);
bhp_hw_assert(
	1 === substr_count( $home, 'id="find-your-adventure"' ),
	'§3.3 the #find-your-adventure deep-link anchor still resolves to exactly one element',
	$failures
);
/*
 * TWO, not one, and both are pre-existing. The footer launcher
 * (`data-bhp-source="information_page"`, id `find-your-adventure`) and the
 * mid-page audience-gateway band (`data-bhp-source="homepage_gateway"`,
 * reinstated by B6 on 2026-08-03) each raise this impression. This build
 * adds neither and removes neither. The first run of this suite asserted 1
 * and was wrong about the page, not the other way round.
 */
bhp_hw_assert(
	2 === substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
	'§3.3 both pre-existing quiz impressions still fire (footer launcher + audience-gateway band)',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'data-bhp-source="homepage_gateway"' ),
	'§3.3 the audience-gateway band still raises its own quiz impression',
	$failures
);

/* 3d. Tracking. Events ADDED, never removed or renamed. */
$expected_events = array(
	'quiz_cta_clicked'      => 3, /* footer launcher + hero link + open-the-book link */
	'contextual_cta_click'  => 2, /* 1.19.243: 1 section CTA + >=1 pre-existing */
);
bhp_hw_assert(
	substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ) >= 3,
	'§3.4 quiz_cta_clicked is emitted by the footer launcher AND both new links (>= 3 emitters)',
	$failures
);
/*
 * ⚠️ 1.19.243 — THIS FLOOR DROPS FROM 4 TO 2, AND THE DROP IS THE POINT, SO
 *    IT IS WRITTEN DOWN RATHER THAN QUIETLY RE-NUMBERED.
 *
 * The three spread <a> wrappers were removed this build: they pointed at
 * /complete-collection/, which is half of the defect Andrew reported ("the
 * 'read the first pages' CTA goes to the collection page again - this is all
 * incorrect"). The photographs ARE the first pages now, so there is nowhere
 * honest to send a click, and they are plain <figure>s.
 *
 * ⭐ THIS IS THE ONLY EVENT EMITTER REMOVED IN THE WHOLE BUILD. No event is
 *    renamed, re-sourced or added. The section CTA and the section quiz
 *    button both still emit, and the assertion immediately below still
 *    forbids any event NAME that did not already exist at 1.19.240.
 */
bhp_hw_assert(
	substr_count( $home, 'data-bhp-event="contextual_cta_click"' ) >= 2,
	'§3.4 contextual_cta_click still emitted by the section CTA plus pre-existing (>= 2 emitters)',
	$failures
);
bhp_hw_assert(
	false === strpos( $home, 'home-open-book__spread-link' ),
	'§3.4 the three page photographs carry NO link to /complete-collection/',
	$failures
);
/*
 * NO NEW EVENT NAME. This is what keeps GTM from needing a new variable: every
 * data-bhp-event value on the page must already be one of the names the site
 * used before this build. The list is the one read out of the 1.19.240 tree.
 */
$known_events = array(
	'amazon_outbound_click', 'bhp_direct_purchase_click', 'collection_upsell_click',
	'contextual_cta_click', 'customer_review_product_click', 'customer_review_source_click',
	'educator_final_cta_click', 'educator_hero_primary_cta_click', 'educator_readaloud_invite_click',
	'gift_final_cta_click', 'gift_hero_primary_cta_click', 'kirkus_review_link_click',
	'landing_page_cta_click', 'org_final_cta_click', 'org_hero_primary_cta_click',
	'parent_final_cta_click', 'parent_hero_primary_cta_click', 'quiz_cta_clicked',
	'quiz_destination_click', 'related_content_click', 'retailer_hero_primary_cta_click',
	'retailer_hero_secondary_cta_click', 'retailer_wholesale_contact_click',
	/*
	 * `collection_band_add_to_cart` is emitted by the Complete Collection
	 * band's own control. It was MISSING from the first version of this list
	 * because that list was built by grepping literal
	 * `data-bhp-event="..."` attributes out of the template tree, and this
	 * one is assembled in PHP rather than written as a literal. The list is
	 * therefore a HAND-MAINTAINED allowlist, not a derived one -- if this
	 * assertion fails on a name you recognise, add it here; if it fails on a
	 * name you do not, a new GTM variable is needed before it ships.
	 */
	'collection_band_add_to_cart',
);
$unknown = array();
if ( preg_match_all( '/data-bhp-event="([a-z_]+)"/', $home, $em ) ) {
	foreach ( array_unique( $em[1] ) as $name ) {
		if ( ! in_array( $name, $known_events, true ) ) {
			$unknown[] = $name;
		}
	}
}
bhp_hw_assert(
	empty( $unknown ),
	'§3.4 NO NEW dataLayer event name was introduced (found: ' . ( $unknown ? implode( ', ', $unknown ) : 'none' ) . ')',
	$failures
);

/* 3e. The rest of the page. Every section that existed still exists, exactly once. */
$sections = array(
	'home-hero', 'home-trust-proof', 'kirkus-credibility-home', 'explore-world',
	'first-reader', 'home-philosophy', 'where-you-will-find-us', 'learning-hub',
	'teacher-resources', 'trust', 'amazon-customer-reviews',
);
foreach ( $sections as $sid ) {
	bhp_hw_assert(
		1 === substr_count( $home, 'id="' . $sid . '"' ),
		"§3.5 #{$sid} still renders exactly once",
		$failures
	);
}

/* 3f. The hero's own pre-existing furniture. */
/* Again the quoted form: `home-hero__book-preview` is a prefix of
   `home-hero__book-preview-label`. */
bhp_hw_assert(
	1 === substr_count( $home, 'class="home-hero__book-preview"' )
		&& 3 === substr_count( $home, 'home-hero__book-cover' ),
	'§3.6 the three-cover preview still renders once, with all three covers',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Real places. Doors into wonder.' ),
	'§3.6 the cover-preview label is unchanged',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Follow Charlotte and Henry from the Mariana Trench' ),
	'§3.6 the approved hero subcopy is unchanged',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Big Places. Brave Hearts.' ),
	'§3.6 the brand signature still renders',
	$failures
);
/*
 * THE HERO BACKGROUND IS A CSS `::after`, NOT AN `<img>`, AND THAT WAS
 * ESTABLISHED BY LOOKING RATHER THAN BY ASSUMING. The first version of this
 * assertion looked for `home-hero__media`, the markup `hero.php` emits when
 * `image_id` is non-zero. It is absent -- and it is absent on PRODUCTION
 * too: `bhp_home_hero_image_id` is an empty string on the front page of
 * BOTH environments (read read-only on production, 2026-08-18). The ocean
 * comes from `.home .home-hero--with-books::after` in style.css, so what
 * there is to assert is that the RULE survived the CSS append.
 */
$css_live = @file_get_contents( get_template_directory() . '/style.css' );
bhp_hw_assert(
	is_string( $css_live ) && false !== strpos( $css_live, 'assets/images/wild-places/ocean-surface.webp' ),
	'§3.6 the hero background treatment is KEPT (the board mock-up drew a flat gradient; current behaviour wins where the board is silent)',
	$failures
);
bhp_hw_assert(
	is_string( $css_live ) && false !== strpos( $css_live, '.home .home-hero--with-books .home-hero__overlay' ),
	'§3.6 the hero overlay gradient rule is untouched by the warmth append',
	$failures
);

/* 3g. Reading age. Never 5-9. */
bhp_hw_assert(
	false === strpos( $home, '5-9' ) && false === strpos( $home, '5–9' ),
	'§3.7 reading age is never stated as 5-9',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §4 — THE SIX OTHER CALLERS OF hero.php
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §4 — THE SHARED HERO COMPONENT DID NOT LEAK ONTO OTHER PAGES ===\n";

$other_pages = array(
	'/about/'    => 'About',
	'/books/'    => 'Books',
	'/contact/'  => 'Contact',
	'/teachers/' => 'Teachers',
);
foreach ( $other_pages as $path => $label ) {
	$doc = bhp_hw_fetch( home_url( $path ) );
	if ( '' === $doc ) {
		bhp_hw_assert( false, "§4 {$label} — page could not be fetched (cannot assert this surface)", $failures );
		continue;
	}
	bhp_hw_assert(
		false === strpos( $doc, 'home-founder-chip' )
			&& false === strpos( $doc, 'home-hero__title-mark' )
			&& false === strpos( $doc, 'home-hero__invitations' ),
		"§4 {$label} — none of the three new hero slots rendered here",
		$failures
	);
	bhp_hw_assert(
		false !== strpos( $doc, 'home-hero__title' ),
		"§4 {$label} — its hero still renders normally",
		$failures
	);
}
/* ═══════════════════════════════════════════════════════════════════════════
   §1.7 — 1.19.246 (PASS4). THE DESKTOP FOLD.

   Andrew, on 1.19.244/245, RELAYED through the Chief of Staff and NOT
   witnessed first-hand by this agent:
     "The CTA and Books arent above the fold on desktop either btw."

   These assertions guard the STRUCTURAL fix, because that is the part a
   future edit is most likely to undo by accident. The pixel result itself
   is measured in a real browser at an asserted window.innerWidth and is
   recorded in the release notes -- PHP cannot lay out a page, so no pixel
   claim is made here. What PHP CAN prove is that the rules which produce
   the result are present, correctly scoped, and shipped in the artefact
   the browser actually downloads.
   ═══════════════════════════════════════════════════════════════════════════ */
echo "
=== §1.7 — PASS4: THE DESKTOP HERO STACKS FROM THE TOP ===
";

$css_p4     = @file_get_contents( get_template_directory() . '/style.css' );
$min_p4     = @file_get_contents( get_template_directory() . '/style.min.css' );
$has_css_p4 = is_string( $css_p4 ) && '' !== $css_p4;

bhp_hw_assert(
	$has_css_p4 && false !== strpos( $css_p4, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS4' ),
	'§1.7a the PASS4 block is present in style.css',
	$failures
);

/* The structural fix. `align-content: center` on a viewport-height box is
   what pinned the CTA to a PERCENTAGE of the viewport; `start` is what makes
   the pixel answer stop moving between 1366, 1440 and 1920. */
bhp_hw_assert(
	$has_css_p4 && 1 === preg_match(
		'/\.home\s+\.home-hero--with-books\s+\.home-hero__content\s*\{[^}]*align-content:\s*start/s',
		$css_p4
	),
	'§1.7b the hero content stacks from the TOP (align-content: start), not centred',
	$failures
);

bhp_hw_assert(
	$has_css_p4 && 1 === preg_match(
		'/\.home\s+\.home-hero--with-books\s+\.home-hero__content\s*\{[^}]*min-height:\s*0/s',
		$css_p4
	),
	'§1.7b the content box no longer reserves calc(100vh - 200px) on desktop',
	$failures
);

bhp_hw_assert(
	$has_css_p4 && 1 === preg_match(
		'/\.home\s+\.home-hero--with-books\s*\{[^}]*min-height:\s*clamp\(\s*640px\s*,\s*82vh\s*,\s*800px\s*\)/s',
		$css_p4
	),
	'§1.7c the desktop hero is BOUNDED (clamp), not pinned to exactly 100vh',
	$failures
);

/* Scope. The most dangerous regression available here is a desktop rule
   leaking onto the phone and undoing PASS3, so scope is asserted, not
   assumed. */
$p4_pos  = $has_css_p4 ? strpos( $css_p4, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS4' ) : false;
$p4_tail = ( false !== $p4_pos ) ? substr( $css_p4, $p4_pos ) : '';
bhp_hw_assert(
	'' !== $p4_tail && false !== strpos( $p4_tail, '@media (min-width: 1051px)' ),
	'§1.7d every PASS4 rule sits behind min-width: 1051px (it cannot reach a phone)',
	$failures
);
bhp_hw_assert(
	'' !== $p4_tail && false === strpos( $p4_tail, 'max-width: 600px' )
		&& false === strpos( $p4_tail, 'max-width: 768px' ),
	'§1.7d the PASS4 block opens no phone-scoped media query of its own',
	$failures
);

/* PASS3's phone result must survive this pass untouched. */
bhp_hw_assert(
	$has_css_p4 && false !== strpos( $css_p4, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS3' ),
	'§1.7e the PASS3 phone block is still present and was not replaced',
	$failures
);

/* The built artefact is what the browser loads. A correct source file that
   was never rebuilt is precisely the failure this catches. */
bhp_hw_assert(
	/* The builder strips comments but preserves the single space after a
	   colon, so this tolerates both forms rather than hard-coding one and
	   failing the day the builder changes. */
	is_string( $min_p4 ) && 1 === preg_match( '/align-content:\s*start/', $min_p4 ),
	'§1.7f style.min.css was REBUILT and carries the structural rule',
	$failures
);

/* No copy changed. PASS4 is CSS only; if a word moved, this pass did the one
   thing it declared it would not do. */
$home_p4 = bhp_hw_fetch( home_url( '/' ) );
bhp_hw_assert(
	'' !== $home_p4 && false !== strpos( $home_p4, 'home-hero__invitations' )
		&& false !== strpos( $home_p4, 'home-founder-chip' ),
	'§1.7g the hero markup is unchanged: chip and both invitations still render',
	$failures
);



/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n";
if ( ! empty( $failures ) ) {
	echo "FAILED ASSERTIONS:\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	WP_CLI::error( count( $failures ) . ' homepage-warmth assertion(s) failed.' );
}
WP_CLI::success( 'Homepage warmth: build, omissions, control diff and shared-component isolation all pass.' );
