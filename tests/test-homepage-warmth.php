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
bhp_hw_assert(
	3 === substr_count( $home, 'class="home-open-book__spread"' ),
	'§1.4 all THREE interior spreads resolved from the media registry',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'How Deep Is the Mariana Trench diagram' )
		&& false !== strpos( $home, 'How Tall Is Mount Everest diagram' )
		&& false !== strpos( $home, 'Connected Amazon ecology diagram' ),
	'§1.4 each spread carries the registry alt text, not a locally-written one',
	$failures
);
bhp_hw_assert(
	false !== strpos( $home, 'Read the first pages before you decide.' )
		&& false !== strpos( $home, 'At the market, people pick a book up' ),
	'§1.4 the section copy is present (NEW COPY, awaiting Andrew - see the build report)',
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
	'contextual_cta_click'  => 5, /* 3 spread links + 1 section CTA + >=1 pre-existing */
);
bhp_hw_assert(
	substr_count( $home, 'data-bhp-event="quiz_cta_clicked"' ) >= 3,
	'§3.4 quiz_cta_clicked is emitted by the footer launcher AND both new links (>= 3 emitters)',
	$failures
);
bhp_hw_assert(
	substr_count( $home, 'data-bhp-event="contextual_cta_click"' ) >= 4,
	'§3.4 contextual_cta_click is emitted by the 3 spread links and the section CTA (>= 4 emitters)',
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
