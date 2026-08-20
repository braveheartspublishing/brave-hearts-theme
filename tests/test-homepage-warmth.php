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

/* Move 2b: the two invitations.

   â AMENDED IN PLACE BY PASS8 (1.19.251). The SUPERSEDED assertion is quoted
      rather than deleted:

        bhp_hw_assert(
            1 === substr_count( $home, 'class="home-hero__invitations"' ),
            '§1.3 the hero invitation cluster renders EXACTLY ONCE',
            $failures
        );

      It matched the class attribute as a WHOLE STRING, closing quote included.
      PASS8 splits the single cluster into two containers so the primary
      invitation can precede the three-book fan IN THE DOM (see §1.10g for why
      that is an accessibility requirement and not a preference), and both
      containers now carry a modifier - so the old needle matches ZERO times,
      not two. The assertion failed by going to 0 while the page was correct.

      The CLAIM it was protecting is unchanged and is now stated more precisely:
      there are exactly TWO containers, exactly one of each kind, and exactly
      two invitation anchors. A duplicated hero would still be caught, and so
      would a container that lost its modifier. */
bhp_hw_assert(
	2 === substr_count( $home, 'home-hero__invitations' ) - substr_count( $home, 'home-hero__invitations--' )
		&& 1 === substr_count( $home, 'home-hero__invitations--primary' )
		&& 1 === substr_count( $home, 'home-hero__invitations--ghost' )
		&& 2 === substr_count( $home, 'class="btn home-hero__invite home-hero__invite--' ),
	'§1.3 the hero renders EXACTLY TWO invitation containers and EXACTLY TWO invitations',
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
/*
 * ⚠️ AMENDED IN PLACE BY 1.19.255 (CYCLE165-LD-HERO-CTA-FALLBACK). THE
 *    SUPERSEDED ASSERTION IS QUOTED RATHER THAN DELETED, because it recorded a
 *    real earlier instruction and because deleting it would hide WHY it was
 *    not enough:
 *
 *      bhp_hw_assert(
 *          false !== strpos( $home, 'data-bhp-source="home_hero_open_book"' )
 *              && preg_match( '/href="#home-open-the-book"[^>]*data-bhp-source="home_hero_open_book"/', $home ) === 1,
 *          '§1.5 the HERO "read the first pages" invitation targets #home-open-the-book',
 *          $failures
 *      );
 *
 * ⛔ IT PASSED ON STAGING AND THE PAGE WAS BROKEN ON PRODUCTION, WHICH IS THE
 *    WHOLE LESSON. It asserted the button's href STRING and never asked
 *    whether anything in the document answered to it. Staging2 has the three
 *    Mariana page attachments and production has none, so the section rendered
 *    here and not there, while the assertion was green on the only environment
 *    the suite was ever run against. Andrew found the dead link on the live
 *    site (item 82, 2026-08-19: "The main CTA on the home page doesnt even
 *    click to to first free pages. Bad link.").
 *
 * ⭐ THE REPLACEMENT ASSERTS THE TARGET, NOT THE STRING, and is therefore true
 *    on every environment: whatever fragment the hero points at must be an id
 *    this same document emits. The full three-candidate chain, the no-media
 *    branch and the whole-document fragment scan live in the dedicated suite
 *    `tests/test-hero-cta-fallback.php`; this is the tripwire in the homepage
 *    suite, so a warmth-pass edit cannot reintroduce a dead anchor without a
 *    red run right here.
 */
bhp_hw_assert(
	false !== strpos( $home, 'data-bhp-source="home_hero_open_book"' ),
	'§1.5a the HERO "read the first pages" invitation is on the page with its unchanged data-bhp-source',
	$failures
);

$hw_hero_frag = '';
if ( preg_match( '/<a\b[^>]*data-bhp-source="home_hero_open_book"[^>]*>/', $home, $hw_m_tag )
	&& preg_match( '/\bhref="#([A-Za-z][A-Za-z0-9_:.\-]*)"/', $hw_m_tag[0], $hw_m_frag ) ) {
	$hw_hero_frag = $hw_m_frag[1];
}

bhp_hw_assert(
	'' !== $hw_hero_frag,
	'§1.5b the HERO invitation targets a same-page fragment (never /complete-collection/ — Andrew rejected that destination in 1.19.242)',
	$failures
);

bhp_hw_assert(
	'' !== $hw_hero_frag
		&& 1 === preg_match( '/\bid=(["\'])' . preg_quote( $hw_hero_frag, '/' ) . '\1/', $home ),
	"§1.5c the HERO invitation's fragment target (#{$hw_hero_frag}) EXISTS in this same rendered document",
	$failures
);

/*
 * The environment-specific expectation, kept SEPARATE from §1.5c on purpose.
 * On staging2 the media resolves, so the button must reach the dedicated
 * first-pages section rather than settle for a fallback. On an environment
 * without the media this correctly does not apply, and the no-media branch is
 * proven in `tests/test-hero-cta-fallback.php` §3 instead.
 */
bhp_hw_assert(
	! function_exists( 'bhp_home_open_the_book_resolved' )
		|| ! bhp_home_open_the_book_resolved()
		|| 'home-open-the-book' === $hw_hero_frag,
	'§1.5d where the first-pages section DOES resolve, the HERO invitation reaches it (not a fallback)',
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

/* 3c. The funnel. */
/*
 * ⛔⛔ AMENDED 1.19.270 — CYCLE165-LD-ITERATE-6-PATH-LINE. THE THREE
 *     ASSERTIONS THIS REPLACES ARE QUOTED VERBATIM RATHER THAN DELETED:
 *
 *       1 === substr_count( $home, 'data-bhp-quiz-launcher' ),
 *       '§3.3 EXACTLY ONE quiz launcher on the page - the hero button is a LINK, not a second launcher',
 *
 *       1 === substr_count( $home, 'data-bhp-quiz-modal-location' ),
 *       '§3.3 exactly one quiz modal, so initLauncher cannot arm its timers twice',
 *
 *       1 === substr_count( $home, 'id="find-your-adventure"' ),
 *       '§3.3 the #find-your-adventure deep-link anchor still resolves to exactly one element',
 *
 *     ALL THREE WERE CORRECT ABOUT THE PAGE UNTIL THIS RELEASE. The founder
 *     ruled the "Not sure which Brave Hearts path fits?" band off the
 *     homepage; the launcher, its hidden modal and the anchor id it carried
 *     all leave with it. The expected counts go 1 -> 0.
 *
 * ⭐ THE ASSERTIONS ARE KEPT AND INVERTED, NOT DROPPED. A removed section is
 *    exactly where a suite gets quietly weakened to "at least zero" and stops
 *    detecting anything. `=== 0` still fails loudly if the band ever returns
 *    to the homepage without a founder ruling behind it.
 *
 * ⭐ AND THE SAME BLOCK NOW ASSERTS THE ROUTE THAT MUST SURVIVE. Removing the
 *    band must not remove the homepage's way INTO the quiz. The hero's ghost
 *    CTA is a link to the canonical PAGE and is asserted here, in the same
 *    place, so a future subtraction cannot take both without going red.
 */
bhp_hw_assert(
	0 === substr_count( $home, 'data-bhp-quiz-launcher' ),
	'§3.3 NO quiz launcher on the homepage (founder ruling, 1.19.270) — found '
		. substr_count( $home, 'data-bhp-quiz-launcher' ),
	$failures
);
bhp_hw_assert(
	0 === substr_count( $home, 'data-bhp-quiz-modal-location' ),
	'§3.3 NO quiz modal on the homepage — found '
		. substr_count( $home, 'data-bhp-quiz-modal-location' ),
	$failures
);
bhp_hw_assert(
	0 === substr_count( $home, 'id="find-your-adventure"' ),
	'§3.3 the #find-your-adventure anchor id leaves the homepage with the band it was stamped on',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'data-bhp-source="home_hero_quiz"' ),
	'§3.3 the homepage KEEPS a live route into the quiz — the hero ghost CTA is still present',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, esc_url( home_url( '/find-your-adventure/' ) ) ),
	'§3.3 ...and that route points at the canonical /find-your-adventure/ PAGE, not a dead fragment',
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
/*
 * ⛔ AMENDED 1.19.268 — CYCLE165-LD-ITERATE-4-HOME-SUBTRACTION. The two
 *    assertions this replaces are quoted verbatim rather than deleted:
 *
 *      2 === substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
 *      '§3.3 both pre-existing quiz impressions still fire (footer launcher + audience-gateway band)',
 *
 *      false !== strpos( $home, 'data-bhp-source="homepage_gateway"' ),
 *      '§3.3 the audience-gateway band still raises its own quiz impression',
 *
 *    They were CORRECT ABOUT THE PAGE until this release. `#home-audience-gateway`
 *    is no longer rendered on a founder ruling, and the impression it raised goes
 *    with it. The count therefore drops 2 -> 1 and `homepage_gateway` disappears.
 *
 * ⭐ THE ASSERTION IS KEPT, NOT DROPPED, AND IT IS ASSERTED BOTH WAYS. A removed
 *    section is exactly the situation where a test is quietly weakened to "at
 *    least one" and stops detecting anything. ONE impression, from the FOOTER
 *    launcher, and NO `homepage_gateway` source anywhere on the document.
 */
/*
 * ⛔ AMENDED AGAIN 1.19.270 — CYCLE165-LD-ITERATE-6-PATH-LINE. The 1.19.268
 *    assertion this replaces is quoted verbatim rather than deleted:
 *
 *      1 === substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
 *      '§3.3 exactly ONE quiz impression now fires (the footer launcher; the audience-gateway band is no longer rendered) — found '
 *
 *    The ONE impression it counted was the footer launcher's. The founder has
 *    now removed the launcher from the homepage, so the count goes 1 -> 0.
 *    The 1.19.268 amendment above remains accurate about what IT changed and
 *    is left standing; this amends only the number.
 *
 * ⚠ `quiz_cta_clicked` IS A DIFFERENT EVENT AND IT STAYS. The hero ghost CTA
 *   raises it. Only the IMPRESSION event leaves the homepage, because only the
 *   band raised it.
 */
bhp_hw_assert(
	0 === substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
	'§3.3 NO quiz impression fires on the homepage now that the band is gone — found '
		. substr_count( $home, 'data-bhp-impression-event="quiz_cta_viewed"' ),
	$failures
);
/*
 * TWO, not one — corrected against the served document rather than left as
 * written. The homepage's two quiz routes are the hero ghost CTA and the
 * "Open the book" ghost ("Which adventure fits your reader?"). Both predate
 * this release and both link to the canonical PAGE. See the fuller note in
 * tests/test-cro-iterate6.php §5.
 */
bhp_hw_assert(
	2 === substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ),
	'§3.3 both surviving homepage quiz routes still raise quiz_cta_clicked — found '
		. substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ),
	$failures
);
bhp_hw_assert(
	false === strpos( $home, 'data-bhp-source="homepage_gateway"' )
		&& false === strpos( $home, 'homepage_gateway' ),
	'§3.3 no `homepage_gateway` analytics source survives anywhere on the homepage',
	$failures
);

/* 3d. Tracking. Events ADDED, never removed or renamed. */
/*
 * ⛔ AMENDED 1.19.270 — CYCLE165-LD-ITERATE-6-PATH-LINE. The floor drops from
 *    3 to 2, and it is written down rather than quietly re-numbered. The
 *    assertion this replaces is quoted verbatim:
 *
 *      substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ) >= 3,
 *      '§3.4 quiz_cta_clicked is emitted by the footer launcher AND both new links (>= 3 emitters)',
 *
 *    The THIRD emitter was the footer launcher, which the founder has removed
 *    from the homepage. The other two — the hero ghost CTA and the
 *    "Open the book" ghost — are untouched, and no event is renamed or
 *    re-sourced.
 *
 * ⭐ AND THE FLOOR IS REPLACED BY AN EXACT COUNT. ">= 2" would keep passing if
 *    a later change took one of the two surviving routes away, which is
 *    precisely the regression this row exists to catch after a subtraction.
 */
$expected_events = array(
	'quiz_cta_clicked'      => 2, /* 1.19.270: hero link + open-the-book link (footer launcher removed) */
	'contextual_cta_click'  => 2, /* 1.19.243: 1 section CTA + >=1 pre-existing */
);
bhp_hw_assert(
	2 === substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ),
	'§3.4 quiz_cta_clicked is emitted by EXACTLY the two surviving homepage links — found '
		. substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ),
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
	/*
	 * ⭐ ADDED 2026-08-19 (`CYCLE165-LD-16`, theme 1.19.263). `header_offer_click`
	 *    is emitted by `.bhp-header-offer`, the mobile-header offer button that
	 *    shipped at 1.19.260 as step 1 of the Direction 1 board build. It has
	 *    been turning this assertion RED on staging2 since that deploy.
	 *
	 * ⭐ THIS IS WHAT THE COMMENT ABOVE INSTRUCTS, NOT A WORKAROUND OF IT:
	 *    "if this assertion fails on a name you recognise, add it here; if it
	 *    fails on a name you do not, a new GTM variable is needed before it
	 *    ships." The name is recognised — `inc/header-offer.php` is in this
	 *    tree, the event is documented in its header, and step 1's own suite
	 *    `tests/test-header-offer.php` asserts it.
	 *
	 * ⚠️ WHAT THIS ADDITION DOES NOT DO: it does not confirm a GTM variable
	 *    exists for `header_offer_click`. GTM remains deliberately unpublished
	 *    (`bhp_gtm_container_id` unset), so there is nothing to check against
	 *    today. This allowlist records that the name is KNOWN TO THE THEME, not
	 *    that it is wired downstream. Whoever publishes GTM owns that step.
	 */
	'header_offer_click',
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

/* 3e. The rest of the page. Every section that survives still exists, exactly once.
 *
 * ⛔ AMENDED 1.19.268 — CYCLE165-LD-ITERATE-4-HOME-SUBTRACTION. The list this
 *    replaces is quoted verbatim rather than deleted, because "which sections
 *    the homepage had" is the single fact this suite exists to pin down:
 *
 *      'home-hero', 'home-trust-proof', 'kirkus-credibility-home', 'explore-world',
 *      'first-reader', 'home-philosophy', 'where-you-will-find-us', 'learning-hub',
 *      'teacher-resources', 'trust', 'amazon-customer-reviews',
 *
 *    `home-philosophy` and `learning-hub` move OUT of this list and INTO the
 *    must-be-absent list below. Every other id is unchanged and still asserted.
 *
 * ⭐ `explore-world` STAYS IN THE PRESENT LIST ON PURPOSE. Its id reads like the
 *    removed "Follow Curiosity Into the Real World" band, and it is not that
 *    band — it renders "Choose Your Adventure", the three destination cards and
 *    the only homepage link to /books/. Verified against the live staging2
 *    1.19.267 document before this release was written. If a future pass deletes
 *    it by name-matching, THIS assertion is what fails.
 */
$sections = array(
	'home-hero', 'home-trust-proof', 'kirkus-credibility-home', 'explore-world',
	'first-reader', 'where-you-will-find-us',
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
   §1.7 — 1.19.247 (PASS4). THE DESKTOP FOLD.

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
$p4_tail = false === $p4_pos ? '' : substr( $css_p4, $p4_pos );
/* ⭐ BOUNDED AT PASS5, AND THIS IS A REAL FIX, NOT A TEST WEAKENED TO GO
   GREEN. §1.7d asks whether the PASS4 BLOCK opens a phone-scoped media
   query. It computed its tail as "everything after the PASS4 marker",
   which silently assumed PASS4 would be the last block in the file
   forever. PASS5 appended a `@media (max-width: 600px)` after it, so the
   assertion started reading PASS5's rules and reporting them as PASS4's.
   The tail now ends where PASS5 begins, which is what the assertion
   always meant. PASS5 has its own equivalent guard at §1.8e. */
$p5_marker = strpos( $p4_tail, 'PASS5 (2026-08-19, theme 1.19.248)' );
if ( false !== $p5_marker ) {
	$p4_tail = substr( $p4_tail, 0, $p5_marker );
}
bhp_hw_assert(
	'' !== $p4_tail && false !== strpos( $p4_tail, '@media (min-width: 1051px)' ),
	'§1.7d every PASS4 rule sits behind min-width: 1051px (it cannot reach a phone)',
	$failures
);
bhp_hw_assert(
	/* Assert on an actual @media DECLARATION, not on the bare string. The
	   first version of this assertion searched for 'max-width: 600px'
	   anywhere in the tail and duly failed on this block's own PROSE, which
	   explains the scoping by naming those very breakpoints. A test that
	   cannot tell a rule from a sentence about a rule is not testing the
	   rule. */
	'' !== $p4_tail && 0 === preg_match( '/@media[^{]*max-width/', $p4_tail ),
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

/* PASS5 — §1.8: THE MOBILE FAN IS ONE CLUSTER, AND THE TRUST LINE IS THERE.

   The founder: "The books like separate now they should all be together
   splayed out like on the desktop."

   WHAT THIS SECTION CANNOT PROVE, SAID PLAINLY. It reads CSS text and
   rendered markup. It CANNOT prove a pixel. The overlap that answers the
   complaint was measured in a real headless browser at an asserted
   window.innerWidth of 390 — BEFORE: -32.9 px, a GAP between the painted
   covers. AFTER: a positive overlap. Those numbers live in the build report,
   not here, because a test claiming to have measured them would be claiming
   a check it never ran. */

/* ⚠️ These read the SAME two files §1.7 already read. A first version of
   this section invented `$css` / `$min` / `$has_css`, which exist nowhere
   in this file, so every CSS assertion below compared against an empty
   string and FAILED on a correct build. Reuse the real variables. */
$css_p5 = $has_css_p4 ? $css_p4 : '';
$min_p5 = $min_p4;
$bhp_trust_line = 'I write them, I sign the school copies myself, and I hand them over at the read-aloud.';

bhp_hw_assert(
	'' !== $css_p5 && false !== strpos( $css_p5, 'PASS5 (2026-08-19, theme 1.19.248)' ),
	'§1.8a the PASS5 block is present in style.css',
	$failures
);

/* The two rules that actually close the 33 px gap. Either alone still leaves
   three separated covers, so both are asserted.
 *
 * ⚠️ AMENDED 2026-08-19 BY 1.19.250 (PASS7), AND QUOTED RATHER THAN REWRITTEN.
 *    The original assertion was:
 *
 *      1 === preg_match( '/margin-right:\s*-9px\s*!important/', $css_p5 )
 *        && 1 === preg_match( '/margin-left:\s*-9px\s*!important/', $css_p5 )
 *
 *    It still MATCHES on a correct 1.19.250 build, and that is exactly the
 *    problem: PASS5's `-9px` text is still in the file, but PASS7 overrides it
 *    with `-12px` further down, so the old assertion would have gone on
 *    passing while asserting a value the browser no longer computes. A test
 *    that cannot fail is not a test.
 *
 *    ⭐ WHAT IS BEING PROTECTED HAS NOT CHANGED — "the fan overlaps rather
 *       than sitting apart, and it beats F8a's `margin-inline: 0 !important`".
 *       Only the number moved, and it moved because the covers grew 34%; the
 *       overlap-to-cover-width PROPORTION is 20.9% in both builds. The
 *       assertion is therefore written against the EFFECTIVE pair, with the
 *       superseded pair still required to be present so a future pass cannot
 *       delete PASS5's block and think nothing depended on it. */
bhp_hw_assert(
	'' !== $css_p5 && 1 === preg_match( '/margin-right:\s*-12px\s*!important/', $css_p5 )
		&& 1 === preg_match( '/margin-left:\s*-12px\s*!important/', $css_p5 )
		&& 1 === preg_match( '/margin-right:\s*-9px\s*!important/', $css_p5 )
		&& 1 === preg_match( '/margin-left:\s*-9px\s*!important/', $css_p5 ),
	"§1.8b the fan overlaps again: both negative margins beat F8a's margin-inline: 0 !important (PASS7's -12px effective, PASS5's -9px still present)",
	$failures
);

/* F8a holds the ITEM at 104 px while the IMAGE inside it is ~66 px. That
   difference IS the defect the founder saw; `width: auto` closes it. */
bhp_hw_assert(
	'' !== $css_p5 && 1 === preg_match( '/#home-hero\s+\.home-hero__book-stack li[^{]*\{[^}]*width:\s*auto/', $css_p5 ),
	'§1.8c the cover item shrink-wraps its picture instead of floating inside a wider box',
	$failures
);

/* The reclaimed margins only win at (1,4,0)/(1,3,0). If a later tidy-up
   simplifies them back to `.home #home-hero`, they lose silently and the CTA
   drops off the first screen again. This is the guard for that. */
bhp_hw_assert(
	'' !== $css_p5
		&& false !== strpos( $css_p5, '.home #home-hero.home-hero--aside-after-title .home-hero__content > .home-hero__book-preview' )
		&& false !== strpos( $css_p5, '.home #home-hero.home-hero--aside-after-title .home-hero__book-preview-label' ),
	'§1.8d the reclaimed margins keep the compound specificity they need to win',
	$failures
);

/* Asserted on an actual @media declaration, never on prose about one — that
   false positive cost PASS4 a red run on a correct build. */
$p5_pos  = strpos( $css_p5, 'PASS5 (2026-08-19, theme 1.19.248)' );

/* â AMENDED IN PLACE BY PASS8 (1.19.251). SUPERSEDED line, quoted:

       $p5_tail = false === $p5_pos ? '' : substr( $css_p5, $p5_pos );

   It ran to the END OF THE FILE, so "$p5_tail" stopped meaning "PASS5's rules"
   the moment any later block was appended. That was harmless while everything
   after PASS5 was also <=600px, and it went red the instant PASS8 added its
   deliberate `@media (min-width: 1051px)` desktop block - a false positive on
   a correct build, which is the exact failure this assertion's own comment
   warns about two lines above. `$p5_tail` is now bounded at the start of the
   PASS7 block, so it is genuinely PASS5's own text. PASS7's and PASS8's
   equivalents are covered separately by §1.9l and by §1.10d. */
$p5_end  = strpos( $css_p5, 'PASS7 (2026-08-19, theme 1.19.250)' );
$p5_tail = ( false === $p5_pos )
	? ''
	: ( false === $p5_end ? substr( $css_p5, $p5_pos ) : substr( $css_p5, $p5_pos, $p5_end - $p5_pos ) );
$p5_tail_code = preg_replace( '#/\*.*?\*/#s', '', $p5_tail );
bhp_hw_assert(
	'' !== $p5_tail && 1 === preg_match( '/@media\s*\(\s*max-width:\s*600px\s*\)/', $p5_tail_code )
		&& 0 === preg_match( '/@media\s*\(\s*min-width:/', $p5_tail_code ),
	'§1.8e no PASS5 layout rule can reach the desktop hero',
	$failures
);

bhp_hw_assert(
	is_string( $min_p5 ) && false !== strpos( $min_p5, 'home-founder-chip__trust' ),
	'§1.8f style.min.css was REBUILT and carries the trust-line rule',
	$failures
);

/* ---- The trust line itself --------------------------------------------- */
$home_p5 = bhp_hw_fetch( home_url( '/' ) );

bhp_hw_assert(
	'' !== $home_p5 && false !== strpos( $home_p5, $bhp_trust_line ),
	'§1.8g the founder trust line renders on the homepage',
	$failures
);

/* It REPLACES the role line. If both ever render the chip grows by a line and
   the CTA leaves the first screen, so this asserts the replacement rather
   than merely the addition. */
bhp_hw_assert(
	'' !== $home_p5 && false === strpos( $home_p5, 'home-founder-chip__role' ),
	'§1.8h the role line it replaced is no longer rendered',
	$failures
);

/* §9.1, enforced rather than trusted. */
bhp_hw_assert(
	0 === preg_match( '/\b(we|us|our)\b/i', $bhp_trust_line )
		&& false === strpos( $bhp_trust_line, "\xe2\x80\x94" ),
	"§1.8i the trust line is in Andrew's voice: no we/us/our, and no em dash",
	$failures
);

/* PASS3's phone result and the F8a block PASS5 interacts with must both
   survive. PASS5 OVERRIDES F8a; it does not delete it. */
bhp_hw_assert(
	'' !== $css_p5 && false !== strpos( $css_p5, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS3' )
		&& false !== strpos( $css_p5, 'F8a. Mobile hero fan' ),
	'§1.8j PASS3 and the F8a block it overrides are both still present',
	$failures
);



/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n";

/* PASS7 — §1.9: THE TYPE IS BACK AT FULL SIZE, THE COVERS ARE 34% BIGGER, AND
   ONLY THE PRIMARY CTA HAS TO BE ON THE FIRST SCREEN.

   The founder, on 1.19.249, on his phone:
     "Mobile- The book images are way too small, make the image bigger by at
      least 30-40%. Also all the text now is very very small even the logo on
      the top left and all the fonts- was that just to fit the CTA above the
      fold, we only need the top CTA above the fold honestly?"

   ⛔ WHAT THIS SECTION CANNOT PROVE, SAID PLAINLY AND FOR THE SAME REASON
      §1.8 said it. It reads CSS text and rendered markup. IT CANNOT PROVE A
      PIXEL. The three numbers that actually answer him — primary CTA bottom
      616.7 at 390x664, cover height 82 -> 110px, cover width 66.4 -> 88.9px —
      were measured in a real headless browser at an asserted
      window.innerWidth/innerHeight, and they live in the build report and in
      the screenshots, NOT here. An assertion in this file claiming to have
      measured them would be claiming a check it never ran. */

$css_p7 = $has_css_p4 ? $css_p4 : '';
$min_p7 = $min_p4;

$p7_pos  = strpos( $css_p7, 'PASS7 (2026-08-19, theme 1.19.250)' );
$p7_tail = false === $p7_pos ? '' : substr( $css_p7, $p7_pos );

/* AMENDED BY PASS8 (1.19.251). `$p7_tail` runs to the END OF THE FILE, so the
   moment a PASS8 block was appended it stopped meaning "PASS7's rules" and
   started meaning "PASS7's rules AND everything after them". That is fine for
   the presence checks below, which only ask whether a declaration EXISTS, but
   it is fatal to the NEGATIVE check at §1.9l, which asks whether a `min-width`
   rule exists anywhere in the slice - PASS8 deliberately adds one. `$p7_only`
   is the PASS7 block alone and is what §1.9l now reads. */
$p8_pos  = strpos( $css_p7, 'PASS8 (2026-08-19, theme 1.19.251)' );
$p7_only = ( false === $p7_pos )
	? ''
	: ( false === $p8_pos ? $p7_tail : substr( $css_p7, $p7_pos, $p8_pos - $p7_pos ) );

bhp_hw_assert(
	'' !== $p7_tail,
	'§1.9a the PASS7 block is present in style.css',
	$failures
);

/* ---- The type restoration, selector by selector -------------------------
   Asserted individually rather than as one "the block contains font-size"
   check, because the failure mode that matters is ONE of these being lost in
   a later tidy-up while the others survive — which is precisely how the page
   drifted small over three passes in the first place. Each value is the
   1.19.242 computed size, read out of a real render. */
$p7_type = array(
	'§1.9b the H1 is back to 2rem/1.1 (32px, was 28px)'
		=> '/\.home-hero__title\s*\{[^}]*font-size:\s*2rem;[^}]*line-height:\s*1\.1;/',
	'§1.9c the subcopy is back to 1.02rem/1.55 (16.32px, was 14px)'
		=> '/\.home-hero__text\s*\{[^}]*font-size:\s*1\.02rem;[^}]*line-height:\s*1\.55;/',
	'§1.9d the spoken line is back to 1.25rem (20px, was 16.32px)'
		=> '/\.home-founder-chip__said\s*\{[^}]*font-size:\s*1\.25rem;/',
	'§1.9e the founder photograph is back to 52px square (was 46px)'
		=> '/\.home-founder-chip__photo\s*\{[^}]*flex:\s*0\s+0\s+52px;[^}]*width:\s*52px;[^}]*height:\s*52px;/',
	'§1.9f the fan label is back to .656rem (10.5px, was 9.28px)'
		=> '/\.home-hero__book-preview-label\s*\{[^}]*font-size:\s*\.656rem;/',
	'§1.9g the invitation padding is back to .95rem (15.2px, was 12.8px)'
		=> '/\.home-hero__invite\s*\{[^}]*padding-block:\s*\.95rem;/',
);
foreach ( $p7_type as $p7_label => $p7_re ) {
	bhp_hw_assert( '' !== $p7_tail && 1 === preg_match( $p7_re, $p7_tail ), $p7_label, $failures );
}

/* ---- The covers ---------------------------------------------------------
   110/82 = +34.1%, inside the "at least 30-40%" he asked for. The height is
   what is asserted because width follows it; asserting a width would be
   asserting a consequence and would go stale if the artwork ever changes. */
bhp_hw_assert(
	'' !== $p7_tail && 1 === preg_match( '/\.home-hero__book-cover\s*\{\s*height:\s*110px;/', $p7_tail ),
	'§1.9h the covers are 110px (+34.1% on PASS5\'s 82px)',
	$failures
);

/* The splay must scale WITH the covers or the fan loosens back into three
   separate books. Both the rotation and the scaled translateY are asserted,
   because a pass that kept the angle and dropped the lift would flatten it. */
bhp_hw_assert(
	'' !== $p7_tail
		&& 1 === preg_match( '/transform:\s*rotate\(-6deg\)\s*translateY\(12px\)/', $p7_tail )
		&& 1 === preg_match( '/transform:\s*rotate\(6deg\)\s*translateY\(12px\)/', $p7_tail )
		&& 1 === preg_match( '/transform:\s*translateY\(-9px\)/', $p7_tail ),
	'§1.9i the splay geometry scaled with the covers rather than flattening',
	$failures
);

/* ---- The one structural move --------------------------------------------

   â AMENDED IN PLACE BY PASS8 (1.19.251). The SUPERSEDED assertion is quoted
      rather than deleted, because it PASSED for a correct reason and now fails
      for an equally correct one:

        bhp_hw_assert(
            '' !== $p7_tail && 1 === preg_match( '/\.home-hero__text\s*\{\s*order:\s*1;/', $p7_tail ),
            '§1.9j the subcopy moves below the primary invitation at <=600px',
            $failures
        );

      PASS7 lifted the buttons above the subcopy with `order: 1` while BOTH
      invitations still sat in one container at the end of the hero content.
      PASS8 splits them and puts the primary invitation in the hero's new
      `after_title` slot, so at <=600px the DOM order is already
      chip > H1 > primary > covers > subcopy > ghost and NO `order` is needed.
      The declaration is gone from that breakpoint on purpose; asserting it
      would now pin a rule whose removal is the point of the change.

      What replaces it is the same claim stated where it is still true: the
      subcopy is ordered LAST of the flow items on DESKTOP, which is how the
      primary invitation gets above the paragraph. */
/* â  AND IT MUST READ CODE, NOT COMMENTS. The first version of this assertion
      matched `$p7_only` raw and FAILED on a correct build, because the
      amendment note directly above QUOTES the superseded declaration verbatim
      - so the regex found `.home-hero__text { order: 1; ... }` inside a
      comment and concluded the rule was still live. Quoting superseded text is
      this file's house style and is not going away, so every negative CSS
      assertion has to strip comments first. */
$p7_only_code = preg_replace( '#/\*.*?\*/#s', '', $p7_only );
bhp_hw_assert(
	'' !== $p7_only && 0 === preg_match( '/\.home-hero__text\s*\{\s*order:\s*1;/', $p7_only_code ),
	'§1.9j PASS7\'s <=600px `order: 1` on the subcopy is GONE (PASS8 does it in the DOM)',
	$failures
);

/* ⭐ THE GUARD THAT MAKES THE ORDER MOVE SAFE, AND IT IS AN A11Y GUARD.
   `order` is only defensible here because the element it moves contains no
   focusable content — one <p>, no links. If a link is ever added to the hero
   subcopy, the visual order and the TAB order diverge and this stops being a
   free move. This asserts the shape of the rendered subcopy, so that day is
   caught by a red suite rather than by a keyboard user. */
$home_p7 = bhp_hw_fetch( home_url( '/' ) );
$p7_text_pos = strpos( $home_p7, 'home-hero__text' );
$p7_text_slice = false === $p7_text_pos ? '' : substr( $home_p7, $p7_text_pos, 900 );
$p7_text_slice = false === strpos( $p7_text_slice, 'home-hero__invitations' )
	? $p7_text_slice
	: substr( $p7_text_slice, 0, strpos( $p7_text_slice, 'home-hero__invitations' ) );
bhp_hw_assert(
	'' !== $p7_text_slice
		&& 0 === preg_match( '/<a\b|<button\b|tabindex=/i', $p7_text_slice ),
	'§1.9k the reordered subcopy still contains NO focusable element, so no tab stop moved',
	$failures
);

/* ---- The things PASS7 promised NOT to do -------------------------------- */

/* Asserted on an actual @media declaration, never on prose about one — that
   false positive cost PASS4 a red run on a correct build, and §1.8e records
   the lesson. */
/* â AMENDED IN PLACE BY PASS8: reads `$p7_only`, not `$p7_tail`. The
      SUPERSEDED assertion is quoted rather than deleted:

        bhp_hw_assert(
            '' !== $p7_tail && 1 === preg_match( '/@media\s*\(\s*max-width:\s*600px\s*\)/', $p7_tail )
                && 0 === preg_match( '/@media\s*\(\s*min-width:/', $p7_tail ),
            '§1.9l no PASS7 rule can reach the tablet or desktop hero',
            $failures
        );

      It read to the end of the file, so PASS8's deliberate
      `@media (min-width: 1051px)` desktop block would have made it fail while
      describing PASS7 - a red run on a correct build, which is the exact false
      positive §1.8e was written about. The CLAIM is unchanged and still
      enforced; only the slice it reads is corrected. */
bhp_hw_assert(
	'' !== $p7_only && 1 === preg_match( '/@media\s*\(\s*max-width:\s*600px\s*\)/', $p7_only_code )
		&& 0 === preg_match( '/@media\s*\(\s*min-width:/', $p7_only_code ),
	'§1.9l no PASS7 rule can reach the tablet or desktop hero',
	$failures
);

/* THE LOGO GUARD. Andrew named the logo, and the honest answer was that no
   pass in this workstream ever shrank it — `height: 34.5px` has been live
   since 1.19.190 and is on PRODUCTION at the same value. Asserting it here
   turns that claim into something a future session can check in one second
   instead of re-deriving from six commits. */
bhp_hw_assert(
	'' !== $css_p7
		&& 1 === preg_match( '/\.site-logo\s+img\.site-logo__mark\s*\{[^}]*height:\s*34\.5px/', $css_p7 ),
	'§1.9m the header logo is untouched at 34.5px (PASS7 reverted nothing, because nothing shrank it)',
	$failures
);

/* PASS3 removed the eyebrow on Andrew's own instruction. "Restore the type"
   is not licence to restore an ELEMENT, and this is the guard that keeps the
   two apart. */
bhp_hw_assert(
	'' !== $home_p7 && false === strpos( $home_p7, 'home-hero__eyebrow' ),
	'§1.9n the eyebrow Andrew removed in PASS3 is still gone',
	$failures
);

/* No copy changed, at any width. PASS7 is CSS only. */
bhp_hw_assert(
	'' !== $home_p7 && false !== strpos( $home_p7, 'home-hero__invitations' )
		&& false !== strpos( $home_p7, 'home-founder-chip' )
		&& false !== strpos( $home_p7, 'home-hero__book-preview' ),
	'§1.9o the hero markup is unchanged: chip, fan and both invitations still render',
	$failures
);

/* Every earlier block PASS7 overrides must still BE there. PASS7 wins on
   source order, not by deletion — if PASS3's or PASS5's block is removed, the
   base values underneath them are not what PASS7 was measured against. */
bhp_hw_assert(
	'' !== $css_p7 && false !== strpos( $css_p7, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS3' )
		&& false !== strpos( $css_p7, 'PASS5 (2026-08-19, theme 1.19.248)' )
		&& false !== strpos( $css_p7, 'F8a. Mobile hero fan' ),
	'§1.9p PASS3, PASS5 and F8a are all still present underneath PASS7',
	$failures
);

/* The artefact, not the source. Editing style.css without rebuilding ships a
   stale stylesheet to a phone, which is the exact defect 1.19.244 existed to
   fix. */
bhp_hw_assert(
	is_string( $min_p7 ) && 1 === preg_match( '/height:\s*110px/', $min_p7 )
		&& 1 === preg_match( '/margin-right:\s*-12px\s*!important/', $min_p7 ),
	'§1.9q style.min.css was REBUILT and carries the PASS7 cover rules',
	$failures
);


/* ═══════════════════════════════════════════════════════════════════════════
   PASS8 — §1.10: THE COVERS ARE 150px, THE PRIMARY INVITATION LEADS THE FAN
   ON A PHONE, AND ON DESKTOP IT SITS ABOVE THE PARAGRAPH.

   The founder, on 1.19.250, on his own devices:
     "The books on mobile are still too small and the CTA on desktop is still
      below the fold. Put the CTA above the paragraph then on desktop. I agree
      to make the change from we to I"

   ⛔ WHAT THIS SECTION CANNOT PROVE, for the third pass running and for the
      same reason. It reads CSS text and rendered markup. IT CANNOT PROVE A
      PIXEL. The numbers that actually answer him — primary CTA bottom 431.0 at
      390x664 and 464.0 at 1440x900, cover height 110 -> 150px, painted cover
      width 73.2 -> 99.5px, fan 194.7 -> 265.4px wide — were measured by DOM
      read in a real browser at an asserted window.innerWidth/innerHeight, and
      they live in the build report and the screenshots, NOT here. An assertion
      here claiming to have measured them would be claiming a check it never
      ran.
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_hw_assert(
	'' !== $css_p7 && false !== $p8_pos,
	'§1.10a the PASS8 block is present in style.css',
	$failures
);
/* ⚠ BOUNDED BY PASS9 (1.19.252), AND THIS IS THE THIRD TIME THIS FILE HAS
   LEARNED IT. The SUPERSEDED line, quoted rather than deleted:

       $p8_tail = ( false === $p8_pos ) ? '' : substr( $css_p7, $p8_pos );

   It sliced to the END OF THE FILE, so the instant PASS9 appended a block,
   "PASS8's rules" silently began to include PASS9's. §1.10q and §1.10r are
   NEGATIVE assertions over this slice — they would have started reporting a
   LATER pass's declarations as PASS8's own, which is precisely how 1.8e and
   1.9l went red on a correct build at PASS8. Bounded to its own block now. */
$p9_pos  = strpos( $css_p7, 'PASS9 — 1.19.252' );
$p8_tail = ( false === $p8_pos )
	? ''
	: ( false === $p9_pos
		? substr( $css_p7, $p8_pos )
		: substr( $css_p7, $p8_pos, $p9_pos - $p8_pos ) );

/* ---- The covers -------------------------------------------------------
   150/110 = +36.4%, past the "at least 30-40%" he asked for at PASS7 and
   answering "still too small" at PASS8. The HEIGHT is asserted because width
   follows it — asserting a width would assert a consequence. */
bhp_hw_assert(
	'' !== $p8_tail && 1 === preg_match( '/\.home-hero__book-cover\s*\{\s*height:\s*150px;/', $p8_tail ),
	'§1.10b the covers are 150px (+36.4% on PASS7\'s 110px)',
	$failures
);

/* THE SCALED OVERLAP. -12 x (150/110) = -16.36px, and the fraction is the
   assertion: rounding to -16px measures a 20.6% join instead of the 21.0% the
   fan is designed around, and a later "tidy" to a whole number is exactly the
   silent loosening this guards. Both margins and all three transforms are
   asserted, because a pass that scaled the margin and left the lift behind
   would flatten the arc. */
bhp_hw_assert(
	'' !== $p8_tail
		&& 1 === preg_match( '/margin-right:\s*-16\.36px\s*!important/', $p8_tail )
		&& 1 === preg_match( '/margin-left:\s*-16\.36px\s*!important/', $p8_tail )
		&& 1 === preg_match( '/transform:\s*rotate\(-6deg\)\s*translateY\(16px\)/', $p8_tail )
		&& 1 === preg_match( '/transform:\s*rotate\(6deg\)\s*translateY\(16px\)/', $p8_tail )
		&& 1 === preg_match( '/transform:\s*translateY\(-12px\)/', $p8_tail ),
	'§1.10c the overlap and the lift both scaled with the covers (21.0% join preserved)',
	$failures
);

/* ---- The desktop swap, which is his second sentence -------------------- */
bhp_hw_assert(
	'' !== $p8_tail
		&& 1 === preg_match( '/@media\s*\(\s*min-width:\s*1051px\s*\)/', $p8_tail )
		&& 1 === preg_match( '/\.home-hero__invitations--primary\s*\{\s*order:\s*1;\s*\}/', $p8_tail )
		&& 1 === preg_match( '/\.home-hero__invitations--ghost\s*\{\s*order:\s*2;\s*\}/', $p8_tail )
		&& 1 === preg_match( '/\.home-hero__text\s*\{\s*order:\s*3;\s*\}/', $p8_tail ),
	'§1.10d on desktop both invitations are ordered ABOVE the paragraph',
	$failures
);

/* The ghost container's 13px. PASS4 gives every `.home-hero__invitations` an
   18px margin-top; with two containers that 18px would also land BETWEEN the
   buttons and open the pair by 5px against 1.19.250. Asserted because "the
   desktop hero is otherwise byte-identical" is a claim this build makes. */
bhp_hw_assert(
	'' !== $p8_tail && 1 === preg_match( '/\.home-hero__invitations--ghost\s*\{\s*margin-top:\s*13px;\s*\}/', $p8_tail ),
	'§1.10e the split does not open the desktop button pair (13px reproduces the old flex gap)',
	$failures
);

/* The grid items do not stretch. Split apart, the ghost container shrink-wrapped
   its shorter label and measured 263.1px against the primary's 302px. */
bhp_hw_assert(
	'' !== $p8_tail
		&& 1 === preg_match( '/@media\s*\(\s*max-width:\s*1050px\s*\)/', $p8_tail )
		&& 1 === preg_match( '/\.home-hero__invitations\s*\{\s*width:\s*100%;\s*\}/', $p8_tail ),
	'§1.10f the two invitation containers are forced to equal width below 1051px',
	$failures
);

/* ---- THE MARKUP SPLIT, AND THE A11Y GUARD THAT IS THE POINT OF IT -------
   ⭐ THIS IS THE ASSERTION THAT MATTERS MOST IN THIS SECTION.

   The three covers are THREE REAL LINKS, one per product page. The whole
   reason PASS8 moved the DOM instead of using `order` is that a CSS-only
   reorder would have left the primary invitation visually above three links
   that still preceded it in the DOM — a keyboard user tabbing the covers and
   then jumping back up to the button.

   So the guard is a POSITIONAL one on the rendered homepage: the primary
   invitation must appear BEFORE the book preview in the HTML, and the ghost
   invitation AFTER it. If a later pass ever moves the primary invitation back
   into `after_text` "to simplify", the visual order will still look right and
   the tab order will silently regress — and this is what catches that. */
$home_p8      = bhp_hw_fetch( home_url( '/' ) );
$pos_primary  = strpos( $home_p8, 'home-hero__invitations--primary' );
$pos_preview  = strpos( $home_p8, 'home-hero__book-preview' );
$pos_ghost    = strpos( $home_p8, 'home-hero__invitations--ghost' );

bhp_hw_assert(
	false !== $pos_primary && false !== $pos_preview && false !== $pos_ghost
		&& $pos_primary < $pos_preview && $pos_preview < $pos_ghost,
	'§1.10g DOM ORDER: primary invitation BEFORE the fan, ghost invitation AFTER it (tab order == visual order)',
	$failures
);

/* And the premise that makes §1.10g necessary: the covers really are links.
   If they ever stop being links the reasoning above changes, and a future
   session should be told that by a red suite rather than rediscover it. */
$p8_stack_pos   = strpos( $home_p8, 'home-hero__book-stack' );
$p8_stack_end   = false === $p8_stack_pos ? false : strpos( $home_p8, '</ul>', $p8_stack_pos );
$p8_stack_slice = ( false === $p8_stack_pos || false === $p8_stack_end )
	? ''
	: substr( $home_p8, $p8_stack_pos, $p8_stack_end - $p8_stack_pos );
bhp_hw_assert(
	'' !== $p8_stack_slice && 3 === preg_match_all( '/<a\b[^>]*href=/i', $p8_stack_slice ),
	'§1.10h the three covers are still three real links (the premise of §1.10g)',
	$failures
);

/* Both invitations still render exactly once, with their events and sources
   intact. The split moved a wrapper; it must not have moved a tag. */
bhp_hw_assert(
	1 === substr_count( $home_p8, 'data-bhp-source="home_hero_open_book"' )
		&& 1 === substr_count( $home_p8, 'data-bhp-source="home_hero_quiz"' )
		&& false !== strpos( $home_p8, 'contextual_cta_click' )
		&& false !== strpos( $home_p8, 'quiz_cta_clicked' )
		&& false !== strpos( $home_p8, 'Open the book. Read the first pages free' )
		&& false !== strpos( $home_p8, 'Take the 30-second quiz.' ),
	'§1.10i both invitations still render once each, same labels, same events, same sources',
	$failures
);

/* ---- The shared component ---------------------------------------------- */
$hero_php_p8 = (string) @file_get_contents( get_template_directory() . '/template-parts/components/hero.php' );
bhp_hw_assert(
	'' !== $hero_php_p8
		&& 1 === preg_match( "/'after_title'\s*=>\s*''/", $hero_php_p8 )
		&& false !== strpos( $hero_php_p8, "\$args['after_title']" ),
	'§1.10j hero.php gained an `after_title` slot that defaults to empty',
	$failures
);

/* ---- THE THREE COPY EDITS, on Andrew's explicit approval ----------------
   "I agree to make the change from we to I" (2026-08-19). Each is asserted
   BOTH ways — the new string present AND the old string absent — because a
   half-applied rename is the failure that leaves one "we" on the page. */
/*
 * ⛔ AMENDED 1.19.268 — CYCLE165-LD-ITERATE-4-HOME-SUBTRACTION. Superseded
 *    assertion, quoted rather than deleted:
 *
 *      false !== strpos( $home_p8, 'Five-star reader reviews on my first two titles' )
 *        && false === strpos( $home_p8, 'Five-star reader reviews on our first two titles' ),
 *      '§1.10k trust strip says "my first two titles"',
 *
 *    The founder removed the scope qualifier. The homepage badge is now exactly
 *    "Five-star reader reviews". BOTH prior wordings are now asserted ABSENT —
 *    the "our" one because standing rule 9.1 still forbids it, and the "my" one
 *    because a half-applied edit that leaves the qualifier on one of the two
 *    render paths is the failure this suite is for.
 *
 * ⛔ HOMEPAGE ONLY. `$home_p8` is the rendered HOMEPAGE document. The Complete
 *    Collection page's own badge lives in the bundle plugin and still reads
 *    "...on my first two titles" under R-12; `tests/test-collection-house-style.php`
 *    lines 189-195 assert THAT one and are deliberately NOT touched. The two
 *    strings are no longer the same and neither suite should be "harmonised".
 */
bhp_hw_assert(
	false !== strpos( $home_p8, 'Five-star reader reviews' )
		&& false === strpos( $home_p8, 'Five-star reader reviews on my first two titles' )
		&& false === strpos( $home_p8, 'Five-star reader reviews on our first two titles' ),
	'§1.10k trust strip reads exactly "Five-star reader reviews" — the scope qualifier is gone in both its wordings',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home_p8, 'Some of what I do happens at a table outdoors' )
		&& false === strpos( $home_p8, 'Some of what we do happens at a table outdoors' ),
	'§1.10l booth section says "Some of what I do"',
	$failures
);
/*
 * ⛔ AMENDED 1.19.268 — CYCLE165-LD-ITERATE-4-HOME-SUBTRACTION. Superseded
 *    assertion, quoted rather than deleted:
 *
 *      false !== stripos( $home_p8, 'My philosophy' )
 *        && false === stripos( $home_p8, 'Our philosophy' ),
 *      '§1.10m the philosophy eyebrow says "My philosophy"',
 *
 *    `#home-philosophy` is no longer rendered on a founder ruling, so there is
 *    no eyebrow left to say either word. ⭐ THE "our" HALF IS KEPT AND STILL
 *    BINDING: standing rule 9.1 forbids that word in customer-facing copy
 *    whether or not this particular section exists, and if the section is ever
 *    restored it must come back in the first-person voice PASS8 gave it.
 */
bhp_hw_assert(
	false === stripos( $home_p8, 'Our philosophy' )
		&& false === stripos( $home_p8, 'My philosophy' ),
	'§1.10m the philosophy eyebrow is gone with its section — and "Our philosophy" is still forbidden if it ever returns',
	$failures
);

/* ⛔⛔ THE CARVE-OUT GUARD — STANDING RULE 9.1a, AND IT OUTRANKS THE VOICE RULE.
   A REAL Amazon customer wrote "We read a few chapters each night." That "we"
   is a CUSTOMER'S WORD, not Andrew's, and rewriting it would FABRICATE A
   CUSTOMER STATEMENT — the never-invent failure class, which wins over the
   voice rule every time and without escalation.

   This assertion exists so that the next agent told "remove the we's" cannot
   quietly reach into the review registry. It must stay green forever. */
$reviews_php_p8 = (string) @file_get_contents( get_template_directory() . '/inc/amazon-reviews.php' );
bhp_hw_assert(
	'' !== $reviews_php_p8
		&& false !== strpos( $reviews_php_p8, 'We read a few chapters each night' ),
	'§1.10n the quoted customer review still says "We read a few chapters each night" (§9.1a — never edit a quote)',
	$failures
);

/* ---- The artefact ------------------------------------------------------ */
bhp_hw_assert(
	is_string( $min_p7 ) && 1 === preg_match( '/height:\s*150px/', $min_p7 )
		&& 1 === preg_match( '/margin-right:\s*-16\.36px\s*!important/', $min_p7 )
		&& 1 === preg_match( '/\.home-hero__invitations--primary\s*\{\s*order:\s*1;\s*\}/', $min_p7 ),
	'§1.10o style.min.css was REBUILT and carries the PASS8 cover and desktop rules',
	$failures
);

/* Everything PASS8 overrides must still BE there — PASS8 wins on source order,
   not by deletion, and the values it was measured against live underneath. */
bhp_hw_assert(
	'' !== $css_p7 && false !== strpos( $css_p7, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS3' )
		&& false !== strpos( $css_p7, 'PASS5 (2026-08-19, theme 1.19.248)' )
		&& false !== strpos( $css_p7, 'PASS7 (2026-08-19, theme 1.19.250)' )
		&& false !== strpos( $css_p7, 'F8a. Mobile hero fan' ),
	'§1.10p PASS3, PASS5, PASS7 and F8a are all still present underneath PASS8',
	$failures
);

/* PASS8 changes NO type size. Every font-size PASS7 restored must survive it —
   asserted as an absence, because the failure mode is a "while we are in here"
   tweak, not a deliberate edit. */
$p8_tail_code = preg_replace( '#/\*.*?\*/#s', '', $p8_tail );
bhp_hw_assert(
	'' !== $p8_tail && 0 === preg_match( '/font-size:/', $p8_tail_code ),
	'§1.10q PASS8 declares no font-size at any width (no type was traded for the fold)',
	$failures
);

/* Nor does it trim the chip padding or the H1 leading. BOTH WERE MEASURED
   (chip padding-block 8px -> 2px and H1 line-height 1.1 -> 1.0 together reached
   635.1, still 11.1px over the 624 line) AND BOTH WERE REJECTED. Neither may
   arrive later by accident and be mistaken for part of this build. */
bhp_hw_assert(
	'' !== $p8_tail
		&& 0 === preg_match( '/padding-top:\s*2px/', $p8_tail_code )
		&& 0 === preg_match( '/line-height:\s*1(\.0)?;/', $p8_tail_code ),
	'§1.10r the two rejected trims (chip padding, H1 leading) are NOT in the shipped block',
	$failures
);


/* ═══════════════════════════════════════════════════════════════════════════
   PASS9 — §1.11: THE COVERS STOP TOUCHING THE CTA BUTTON ON A PHONE.

   The founder, on 1.19.251, on his own phone:
     "The books sites directly on the CTA button. I think it should be a little
      space in between the button and the image"

   ⛔ WHAT THIS SECTION CANNOT PROVE, for the fourth pass running and for the
      same reason. It reads CSS text. IT CANNOT PROVE A PIXEL — and on THIS
      change that limitation is the entire story, because the defect was
      INVISIBLE to every layout-box measurement:

        primary CTA bottom                 431.0
        .home-hero__book-preview top       449.0   -> 18.0px of "clearance"
        painted top, centre cover          429.0   -> 2.0px OVERLAP

      PASS8 gives the centre cover `translateY(-12px)`, so the fan paints
      higher than it lays out. The numbers that actually answer him — painted
      clearance -2.0 -> +18.0 at an asserted window.innerWidth 390 /
      innerHeight 664 — were measured by DOM read with getBoundingClientRect()
      in a real browser and live in the build report, NOT here. An assertion
      here claiming to have measured them would be claiming a check it never
      ran.
   ═══════════════════════════════════════════════════════════════════════════ */

bhp_hw_assert(
	'' !== $css_p7 && false !== $p9_pos,
	'§1.11a the PASS9 block is present in style.css',
	$failures
);
/*
 * ⭐ REPAIRED 2026-08-19 (`CYCLE165-LD-16`, theme 1.19.263) — THIS SLICE WAS
 *    UNBOUNDED, AND THE THREE ASSERTIONS BELOW HAD BEEN FAILING SINCE
 *    1.19.260 BECAUSE OF IT. `test-homepage-warmth` was RED on staging2 at
 *    1.19.262 with four failures; this repairs three of them and the
 *    allowlist repair at §3.4 repairs the fourth.
 *
 * THE DEFECT. `substr( $css_p7, $p9_pos )` runs from the PASS9 marker to the
 * END OF THE FILE. `$p8_tail` twenty lines above is bounded — it stops at
 * `$p9_pos` — but PASS9 was the last block in `style.css` when this was
 * written, so its slice had no terminator and none was noticed. The comment
 * on §1.11c states the intent in its own words: *"Asserted as an absence over
 * the PASS9 block only."* THE IMPLEMENTATION CONTRADICTED THAT SENTENCE.
 *
 * WHAT TRIPPED IT. `style.css` appends new components at the end — that is
 * this file's stated convention, written into both the 1.19.260 and 1.19.263
 * banners. 1.19.260 appended `.bhp-header-offer`, which legitimately declares
 * `min-height`, `line-height`, `font-size` and `text-transform` and carries
 * its own container query. None of that is inside the PASS9 block; all of it
 * was inside the unbounded slice.
 *
 * ⛔ THIS DOES NOT WEAKEN THE ASSERTION — IT RESTORES THE ONE THAT WAS
 *    INTENDED. The slice now ends at the next top-level banner, exactly the
 *    way `$p8_tail` ends at `$p9_pos`. Everything PASS9 actually declares is
 *    still checked, and a real regression inside PASS9 still fails. What can
 *    no longer happen is an unrelated component appended below it turning
 *    this suite red and training a future reader to ignore it.
 *
 * ⛔ NO ASSERTION IS DELETED, RELAXED OR RENUMBERED. §1.11c, §1.11d and
 *    §1.11e are byte-identical below. Only the string they read changed.
 */
$p9_tail = ( false === $p9_pos ) ? '' : substr( $css_p7, $p9_pos );
if ( '' !== $p9_tail ) {
	/*
	 * The next top-level banner, which is where the PASS9 block ends.
	 *
	 * `$p9_pos` is the position of the MARKER TEXT ("PASS9 — 1.19.252"), which
	 * sits on the banner's second line — so the slice starts INSIDE PASS9's own
	 * banner and the first `\n/* =====` it contains is unambiguously the start
	 * of the next block. Matching on the banner opener alone, rather than on
	 * the star or the version that follows it, means a future block that
	 * formats its heading differently still terminates this slice.
	 */
	$p9_end = strpos( $p9_tail, "\n/* =========" );
	if ( false !== $p9_end ) {
		$p9_tail = substr( $p9_tail, 0, $p9_end );
	}
}

/* ---- THE ASSERTION THAT MATTERS MOST IN THIS SECTION -------------------
   The COMPOUND SELECTOR is load-bearing and is not decoration. PASS5's own
   note at style.css line ~9797 records that a simplified `.home #home-hero …`
   at (1,2,0) MEASURED NO CHANGE AT ALL, because two rules further up the file
   carry `#home-hero.home-hero--aside-after-title` at (1,4,0) and win
   silently. If a later pass "tidies" this selector to a shorter one, the rule
   stops applying, the covers land back on the button, and the page LOOKS
   plausible — the exact silent regression this guards. */
bhp_hw_assert(
	'' !== $p9_tail
		&& 1 === preg_match( '/@media\s*\(\s*max-width:\s*600px\s*\)/', $p9_tail )
		&& 1 === preg_match(
			'/\.home\s+#home-hero\.home-hero--aside-after-title\s+\.home-hero__content\s*>\s*\.home-hero__book-preview\s*\{\s*margin-top:\s*30px;\s*\}/',
			$p9_tail
		),
	'§1.11b the fan margin is 30px at <=600px, ON THE (1,4,0) COMPOUND SELECTOR that actually wins',
	$failures
);

/* The spacing is bought with MARGIN and nothing else. The one thing this build
   was forbidden to do is shrink the covers to make room — that is the
   complaint 1.19.251 was built to fix, and re-introducing it here would look
   like a tidy. Asserted as an absence over the PASS9 block only. */
$p9_tail_code = preg_replace( '#/\*.*?\*/#s', '', $p9_tail );
bhp_hw_assert(
	'' !== $p9_tail
		&& 0 === preg_match( '/height:/', $p9_tail_code )
		&& 0 === preg_match( '/transform:/', $p9_tail_code )
		&& 0 === preg_match( '/font-size:/', $p9_tail_code )
		&& 0 === preg_match( '/margin-(left|right):/', $p9_tail_code ),
	'§1.11c PASS9 declares no height, transform, font-size or splay margin (no cover was shrunk to buy the space)',
	$failures
);

/* PASS8's cover geometry must survive PASS9 untouched. §1.10b/c already assert
   it inside PASS8's now-bounded slice; this asserts the pair did not get
   RESTATED at a different value further down, which a bounded slice cannot
   see and which would let the later value win on source order. */
bhp_hw_assert(
	'' !== $p9_tail
		&& 0 === preg_match( '/height:\s*\d+px/', $p9_tail_code )
		&& 0 === preg_match( '/-16\.36px/', $p9_tail_code ),
	'§1.11d PASS9 does not restate PASS8\'s cover height or splay at a new value',
	$failures
);

/* Nothing above 600px may be touched. The founder reported a PHONE defect and
   the desktop hero was signed off at 1.19.251 (primary CTA bottom 464.0, hero
   bottom 743.8, both re-measured identical after this build). A stray
   min-width or a wider max-width in this block is how a phone fix reaches a
   desktop nobody asked about. */
bhp_hw_assert(
	'' !== $p9_tail
		&& 0 === preg_match( '/min-width:/', $p9_tail_code )
		&& 1 === preg_match_all( '/@media/', $p9_tail_code ),
	'§1.11e PASS9 contains exactly ONE media query and it is phone-only (no min-width, desktop untouched)',
	$failures
);

/* Every earlier pass still underneath PASS9. */
bhp_hw_assert(
	'' !== $css_p7
		&& false !== strpos( $css_p7, 'CYCLE164-LD-HOMEPAGE-WARMTH-PASS3' )
		&& false !== strpos( $css_p7, 'PASS5 (2026-08-19, theme 1.19.248)' )
		&& false !== strpos( $css_p7, 'PASS8 (2026-08-19, theme 1.19.251)' )
		&& false !== strpos( $css_p7, 'F8a. Mobile hero fan' ),
	'§1.11f PASS3, PASS5, PASS8 and F8a are all still present underneath PASS9',
	$failures
);

/* The artefact, not the source. Editing style.css without rebuilding ships a
   stale stylesheet to a phone — the exact defect 1.19.244 existed to fix, and
   on this build it would mean the founder sees no change at all. */
bhp_hw_assert(
	is_string( $min_p7 )
		&& 1 === preg_match(
			'/\.home\s+#home-hero\.home-hero--aside-after-title\s+\.home-hero__content\s*>\s*\.home-hero__book-preview\s*\{\s*margin-top:\s*30px;\s*\}/',
			$min_p7
		),
	'§1.11g style.min.css was REBUILT and carries the PASS9 fan margin',
	$failures
);

if ( ! empty( $failures ) ) {
	echo "FAILED ASSERTIONS:\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	WP_CLI::error( count( $failures ) . ' homepage-warmth assertion(s) failed.' );
}
WP_CLI::success( 'Homepage warmth: build, omissions, control diff and shared-component isolation all pass.' );
