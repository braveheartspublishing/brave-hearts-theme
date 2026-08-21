<?php
/**
 * Brave Hearts — THE PROTECTED ELEMENTS MANIFEST.
 *
 * `CYCLE165-LD-ITERATE-8-FINAL` (2026-08-19, theme 1.19.272).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY THIS SUITE EXISTS — AND WHY IT IS THE ONLY ONE THAT ASSERTS PRESENCE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-19, adopted the PROTECTED ELEMENTS MANIFEST as a
 * founder ruling — carrier item **119**, recorded in the Business OS
 * chief-of-staff founder-verbatim record. Referenced by location, never
 * restated: **this repository is public on GitHub** and standing rules §4/§4.1
 * allow an item number, a status and a date to travel here, but not the words.
 * The `CYCLE165-LD-10` scrub class exists because a private figure once reached
 * a public-bound comment in this repo; that mistake is not repeated here.
 *
 * The ruling, in the terms this file can carry: **trust, lead conversion and
 * sales conversion elements are priorities, they cannot be missed on production
 * pages, and they are protected.** The element list itself is his — assembled
 * from his own rulings, and from the "Keeps" line of
 * `Business OS\ANDREW-REVIEW\2026-08-19\REPORT-2026-08-19-LEARN-REVIEW-ITERATE.md`
 * §4, the subtraction sheet he answered line by line.
 *
 * ⛔ THE ROOT CAUSE IT ANSWERS, stated plainly because it is the whole design.
 *    Every gate this repository had checked an ABSENCE — "the router is gone",
 *    "the band does not render", "no post carries the footer capture". Not one
 *    checked a PRESENCE. So when 1.19.269 switched off the "book this came
 *    from" rail by over-reading a subtraction brief, **4,315 assertions stayed
 *    green and the founder found the regression himself on his own production
 *    site** (carrier item 118, `CYCLE165-COS-009`). A suite of absences cannot
 *    catch a subtraction that went too far. This suite is the other half.
 *
 * ⭐ EVERY ASSERTION CITES ITS RULING IN ITS OWN MESSAGE. When one fails, the
 *    reader learns not only what is missing but whose decision it was and where
 *    that decision is written down — so the fix is never "delete the test".
 *
 * ⛔ A FAILURE HERE IS NOT A TEST BUG BY DEFAULT. If a protected element is
 *    genuinely to be removed, the manifest changes FIRST, on Andrew's explicit
 *    word (standing rules §6), and this suite changes with it. Silently
 *    relaxing an assertion here would defeat the ruling it enforces.
 *
 * Run via WP-CLI (⚠ THE `--url` FLAG IS NOT OPTIONAL — without it the fetched
 * documents come from the wrong environment; `CYCLE165-LD-53`):
 *
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-protected-elements.php \
 *     --url=https://staging2.braveheartspublishing.com --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from SERVED DOCUMENTS — the bytes a customer's browser receives —
 * rather than from template source, because a template that is never included
 * proves nothing:
 *   §1  HOME      — founder chip · hero I-voice line · trust line A ·
 *                   free-sample primary · ★ badge · a REAL attributed Amazon
 *                   review · Kirkus · the first-pages section the hero points at
 *   §2  POSTS     — the book rail EXACTLY ONCE · the end-of-post capture
 *                   EXACTLY ONCE · the popup engine present · the ask count
 *                   still TWO, because the rail is a BOOK, not an ask · and
 *                   ⭐ 1.19.273 THE RAIL CONTRACT (§2.6a/b/c, item 126): the
 *                   rail's IMAGE and PRICE both come from its OWN mode, never
 *                   mixed — the first assertion here to check a PAIRING rather
 *                   than a presence, because presence was true while the
 *                   pairing was false and that is what the founder found
 *   §3  PRODUCTS  — price · format selector · ATC, all three ahead of the
 *                   long-form body · guarantee · a real review · the I-voice
 *                   shipping line
 *   §4  COLLECTION— the price anchor (was / now / save) · the three FREE
 *                   bullets · the primary CTA · the guarantee
 *   §5  SITEWIDE  — the header-offer component renders where it should, its
 *                   suppression rules still resolve, and cart + checkout are
 *                   reachable
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads served markup
 *    and exercises live functions. It does NOT prove that an element is VISIBLE,
 *    that it is inside a fold, that it is not covered by an overlay, that its
 *    contrast passes, or that the console is clean. Those are BROWSER facts,
 *    measured separately at an asserted `window.innerWidth` and filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-iterate8-qa\`.
 *    ⚠ A PRESENT-BUT-INVISIBLE ELEMENT WOULD PASS HERE. That is the known limit
 *      of a markup suite and it is why the browser pass is not optional.
 *
 * ⛔ NOTHING IS WRITTEN. No post, page, option, product, price, variation,
 *    coupon, stock level, shipping/tax/payment/checkout setting, cart, order,
 *    attachment or user is created or modified by any line here. No form is
 *    submitted and no address enters any list. Every fetch is a GET.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_pe_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_pe_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** HTTP status of a URL, following nothing. */
function bhp_pe_status( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false, 'redirection' => 0 ) );
	return is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
}

/**
 * ⭐⭐ THE MANIFEST ITSELF — ONE ARRAY, AND IT IS THE SINGLE SOURCE.
 *
 * Every row is `marker => [count-rule, ruling citation]`. The suite below reads
 * this array, and **the production deploy script greps the SAME marker strings
 * out of the comment-stripped artefact**, so the shipped code and the shipped
 * gate can never drift apart by one being edited and the other forgotten.
 *
 * `min` means "at least this many occurrences in the served document".
 * `exact` means exactly this many — used only where a second copy is itself the
 * defect (a rail injected twice, a capture asked twice).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THERE IS NO `shop` KEY IN THIS ARRAY, AND THAT IS DELIBERATE AND RECORDED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ NOTE ADDED AT 1.19.284 UNDER CARRIER ITEM 207, so the absence stops being
 *    rediscovered as an ambiguity by every successive subtraction brief. The
 *    keys below are `home`, `product`, `collection` (plus the sitewide checks
 *    run outside this array). ⛔ /shop/ HAS NEVER HAD A PROTECTED ROW. The same
 *    finding was reached independently at 1.19.283 (item 204, hiding the Kirkus
 *    badge and the review quote on the MOBILE shop card) and again at 1.19.284.
 *    It is now written down in `21-PROTECTED-ELEMENTS-MANIFEST.md` §3.6 as well
 *    as here, so the two records agree.
 *
 * ⭐⭐ WHAT ITEM 207 REMOVED, AND WHY IT WAS ALLOWED TO PROCEED:
 *      Andrew Signore, carrier item 207, 2026-08-21 (⚠️ RELAYED through
 *      `chief-of-staff`, ⛔ NOT witnessed first-hand by the agent that made the
 *      change — recorded as relayed per Standing Rules §9.2 rule 2) ruled the
 *      Complete Collection carousel OFF /shop/. ⛔ THAT PAGE ONLY.
 *
 *      §5 of the manifest says a protected element is removed only *"on
 *      Andrew's explicit word (Standing Rules §6)"* and that no agent edits a
 *      row out on its own judgement. ⭐ ITEM 207 IS THAT WORD — and separately,
 *      ⛔ THERE WAS NO ROW TO EDIT OUT. So this release removed no listed
 *      element and relaxed no assertion in this file. ⛔ NOT ONE ROW BELOW WAS
 *      TOUCHED, ADDED OR WEAKENED.
 *
 * ⛔ AND NO CTA WAS ORPHANED — the item-118 failure shape this suite's §1.11
 *    exists to catch. The removed banner's only outbound control went to
 *    /complete-collection/; the same destination is still on /shop/ as the
 *    Complete Collection product card (item 206). That is asserted in
 *    `tests/test-shop-collection-carousel.php` §4, not claimed here.
 *
 * @return array
 */
function bhp_pe_manifest() {
	return array(

		/* ── HOME ────────────────────────────────────────────────────────── */
		'home' => array(
			'home-founder-chip' => array( 'min', 1,
				'founder chip — ruling item 110 + report §4 "Keeps" (trust)' ),
			'ICU nurse, uncle, and the author.' => array( 'min', 1,
				'hero I-voice line — report §4 "Keeps" (trust); §9.1 voice rule, I not we' ),
			'home-founder-chip__trust' => array( 'min', 1,
				'trust line A — report §4 "Keeps" (trust)' ),
			'home-hero__invite--primary' => array( 'min', 1,
				'free-sample primary CTA — report §4 "Keeps" (leads); one primary per screen' ),
			'Five-star reader reviews' => array( 'min', 1,
				'★ badge — ruling item 110 limb (1): keep the stars and this exact string' ),
			'amazon-review-card__quote' => array( 'min', 1,
				'REAL Amazon review, verbatim — report §4 "Keeps" (trust); standing rules §2/§3' ),
			'amazon-review-card__attribution' => array( 'min', 1,
				'…and it is ATTRIBUTED. An unattributed review is the fabrication class §2 forbids' ),
			'kirkus-credibility' => array( 'min', 1,
				'Kirkus mention — report §4 "Keeps" (trust)' ),
			'id="home-open-the-book"' => array( 'min', 1,
				'first-pages section — report §4 "Keeps"; also the hero primary\'s own target' ),
		),

		/* ── POSTS ───────────────────────────────────────────────────────── */
		'post' => array(
			'<aside class="bhp-book-rail' => array( 'exact', 1,
				'the book rail — ruling item 110 (he likes it) + item 118 (he found it missing)' ),
			'class="bhp-post-capture"' => array( 'exact', 1,
				'end-of-post capture — report §4 item 1: "keep the end-of-post capture"' ),
			'parent-ab-popup' => array( 'min', 1,
				'popup engine — report §4 item 1 keeps it; ruling item 110 limb (4) approved it' ),
			/*
			 * 1.19.273 — THE RAIL CONTRACT's two declarations (item 126). These
			 * rows exist so the DEPLOY SCRIPT's gate greps them out of the
			 * artefact too: an artefact whose rail cannot state its mode is one
			 * whose pairing §2.6a/b/c could never have checked, and it must not
			 * reach production silently.
			 */
			'data-bhp-rail-image="' => array( 'exact', 1,
				'rail contract — image kind declared (item 126); §2.6a asserts it MATCHES the mode' ),
			'data-bhp-rail-price-source="' => array( 'exact', 1,
				'rail contract — price source declared (item 126); §2.6c compares it to the live figure' ),
		),

		/* ── PRODUCTS ────────────────────────────────────────────────────── */
		'product' => array(
			'bhp-formats__selected-price' => array( 'min', 1,
				'price — item 119 (sales); Direction 1 put price in the phone\'s first screen' ),
			'bhp-formats__grid' => array( 'min', 1,
				'format selector — item 119 (sales)' ),
			'bhp-formats__cta' => array( 'min', 1,
				'ADD TO CART — item 119 (sales)' ),
			'bhp-product-guarantee' => array( 'min', 1,
				'guarantee — report §4 "Keeps" (trust)' ),
			/*
			 * 1.19.275 (CYCLE165-LD-GALLERY-TOP): the label used to read "REAL
			 * review above the gallery". The founder ruled the carousel back to
			 * the top of the mobile page, so the review now sits BELOW it, at
			 * slot 6, directly under ADD TO CART. THE ASSERTION IS UNCHANGED and
			 * always was position-independent — this suite checks PRESENCE, not
			 * order. Only the description is corrected, because a description
			 * that describes the wrong layout is how a stale claim gets quoted
			 * as evidence later.
			 */
			'amazon-review-card__quote' => array( 'min', 1,
				'REAL review, directly under ADD TO CART on mobile — report §4 "Keeps" (trust)' ),
			'Ships from my print partner' => array( 'min', 1,
				'I-voice shipping line — Direction 1 step 3; §9.1 voice rule, I not we' ),
		),

		/* ── COLLECTION ──────────────────────────────────────────────────── */
		'collection' => array(
			'bhp-landing-coldopen__price-was' => array( 'min', 1,
				'price anchor, struck original — item 119 (sales)' ),
			'bhp-landing-coldopen__price-now' => array( 'min', 1,
				'price anchor, the price paid — item 119 (sales)' ),
			'bhp-landing-coldopen__price-save' => array( 'min', 1,
				'price anchor, the saving — item 119 (sales)' ),
			'bhp-landing-coldopen__free' => array( 'min', 3,
				'the three FREE bullets — report §4 "Keeps": one collection offer' ),
			'bhp-landing-cta--primary' => array( 'min', 1,
				'the one primary CTA — report §4 "Keeps" (sales); one primary per screen' ),
			'bhp-landing-guarantee' => array( 'min', 1,
				'guarantee — report §4 "Keeps" (trust)' ),
			/*
			 * ⭐⭐ ADDED 1.19.280 — THE FOUNDER'S FREE-SHIPPING STRING.
			 *
			 * Andrew Signore, carrier item 186, ~05:1x−0600 2026-08-21, read
			 * FIRST-HAND AT SOURCE by the agent that added this row:
			 *
			 *   "Also Free Shipping on the complete collection needs to change
			 *    to Free Shipping on the complete collection or 3 or more books
			 *    purchased."
			 *
			 * ⭐ THIS ROW MODIFIES A LISTED TRUST STRING BY HIS OWN WORD, which
			 *    is the ONLY thing that may move a manifest row (§5, the
			 *    approval gate). Item 186 IS that word.
			 *
			 * ⭐ `min 2` IS MEASURED, NOT GUESSED: the string renders twice on
			 *    /complete-collection/ — the cold-open bullets and the closing
			 *    CTA bullets — observed live on staging 1.19.280 at an asserted
			 *    innerWidth of 390 AND 1440 (newString: 2, oldStringAlone: 0).
			 *    Both come from `bundle-landing-page.php`, and before 1.19.280
			 *    they were two hardcoded copies of one promise.
			 *
			 * ⛔⛔ THE ROW DOES NOT RUN ON A SCHOOL-VISIT SESSION, AND THAT IS
			 *    THE POINT OF THE GUARD, NOT AN EXEMPTION. A flagged parent is
			 *    not being shipped anything and correctly sees "FREE author
			 *    hand-delivery at your school visit" instead — verified live at
			 *    both widths with ?bhp_visit=adams-2026-08-28 (shippingClaim: 0).
			 *    Asserting presence unconditionally would FAIL a correctly
			 *    behaving flagged journey, which is the `FD-505`/`FD-506` path
			 *    that must never be broken by a gate.
			 */
			/*
			 * ⭐⭐ 1.19.282 — CARRIER ITEM 192 CONFIRMED THIS ROW'S CASING.
			 *
			 * Andrew Signore, carrier item 192, 2026-08-21 (⚠️ RELAYED through
			 * `chief-of-staff`, NOT witnessed first-hand by the agent that
			 * wrote this note): uppercase **FREE**, matching the pre-existing
			 * string's own style, everywhere the clause appears.
			 *
			 * ⭐⭐ THE ROW WAS ALREADY THE EXACT FINAL BYTES AND NOT ONE
			 *    CHARACTER OF IT MOVED. 1.19.280 shipped the conservative
			 *    reading — it left the locked casing alone and asked him. He
			 *    confirmed the casing that was already shipping. ⛔ So "sync
			 *    the row to the final bytes" resolved to a VERIFICATION, not
			 *    an edit, and saying so plainly matters more than making the
			 *    diff look like work: a row edited for no reason is a row a
			 *    future reader cannot trust.
			 *
			 * ⭐ THE PARITY GUARD IS THE NEW WORK, and it lives in
			 *    `tests/test-freeship-line-parity.php` §4, which compares THIS
			 *    ROW against the live helper rather than against a second copy
			 *    of the literal. If either side ever moves alone, that suite
			 *    fails. Before 1.19.282 the casing was protected only by a
			 *    comment asking a future agent not to change it.
			 */
			'FREE Shipping on the complete collection or 3 or more books purchased' => array( 'min', 2,
				'the free-shipping promise, his exact string — carrier item 186, casing confirmed at item 192 '
				. '(trust + sales). Skipped on a school-visit session, which shows hand-delivery instead.' ),
		),
	);
}

/**
 * Is this run happening inside a school-visit session?
 *
 * ⛔ Used to skip the free-shipping row above. A flagged session REPLACES the
 *    shipping promise with the hand-delivery sentence, by design — so on that
 *    path the string's ABSENCE is correct behaviour, not a regression.
 *
 * @return bool
 */
function bhp_pe_visit_session() {
	return function_exists( 'bhp_school_visit_use_delivery_framing' )
		&& bhp_school_visit_use_delivery_framing();
}

/**
 * Run one template's manifest rows against one served document.
 *
 * @param string $tpl      Manifest key.
 * @param string $html     Served document.
 * @param string $where    Human label for the message.
 * @param string $section  Section number.
 * @param array  $failures By reference.
 */
function bhp_pe_run_manifest( $tpl, $html, $where, $section, array &$failures ) {
	$rows = bhp_pe_manifest();
	$i    = 0;
	foreach ( $rows[ $tpl ] as $marker => $rule ) {
		++$i;
		list( $mode, $want, $why ) = $rule;
		/*
		 * ⛔ 1.19.280 — THE ONE CONDITIONAL ROW, AND IT IS CONDITIONAL FOR A
		 *    REASON THAT PROTECTS A JOURNEY RATHER THAN EXCUSING A FAILURE.
		 *    On a school-visit session the shipping promise is REPLACED by the
		 *    hand-delivery sentence by design, so asserting its presence would
		 *    fail a correctly behaving flagged path (`FD-505`/`FD-506`).
		 *    ⭐ Everything else in the manifest still runs on that path.
		 */
		if ( false !== strpos( $marker, 'FREE Shipping on the complete collection' )
			&& function_exists( 'bhp_pe_visit_session' ) && bhp_pe_visit_session() ) {
			echo "SKIP: §{$section}.{$i} {$marker} — school-visit session shows hand-delivery instead (by design)\n";
			continue;
		}
		$got = substr_count( $html, $marker );
		$ok  = ( 'exact' === $mode ) ? ( $got === $want ) : ( $got >= $want );
		bhp_pe_assert(
			$ok,
			sprintf(
				'§%s.%d PRESENT on %s: %s  [%s %d, got %d]  ⭐ PROTECTED: %s',
				$section, $i, $where, $marker, $mode, $want, $got, $why
			),
			$failures
		);
	}
}

echo "=== PROTECTED ELEMENTS — founder ruling item 119 ===\n";
echo "Environment: " . home_url( '/' ) . "\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · HOME
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §1 — HOME ===\n";

$pe_home = bhp_pe_fetch( home_url( '/' ) );
bhp_pe_assert( '' !== $pe_home, '§1.0 the homepage was fetched and returned 200', $failures );
if ( '' !== $pe_home ) {
	bhp_pe_run_manifest( 'home', $pe_home, 'the homepage', '1', $failures );

	/*
	 * ⭐ THE HERO PRIMARY MUST POINT AT SOMETHING THE HOMEPAGE ACTUALLY
	 *    RENDERS. This is the check that would have caught a subtraction
	 *    orphaning the free-sample CTA — the exact failure mode of item 118,
	 *    one element removed and its partner left pointing at nothing.
	 */
	$pe_href = array();
	preg_match( '/class="[^"]*home-hero__invite--primary[^"]*"\s+href="([^"]+)"/', $pe_home, $pe_href );
	$pe_target = isset( $pe_href[1] ) ? (string) $pe_href[1] : '';
	bhp_pe_assert(
		'' !== $pe_target,
		'§1.10 the free-sample primary carries an href at all',
		$failures
	);
	if ( '' !== $pe_target && '#' === substr( $pe_target, 0, 1 ) ) {
		bhp_pe_assert(
			false !== strpos( $pe_home, 'id="' . substr( $pe_target, 1 ) . '"' ),
			sprintf( '§1.11 …and its fragment %s resolves to an id the homepage renders  ⭐ PROTECTED: the free-sample path must not dead-end (item 119, leads)', $pe_target ),
			$failures
		);
	}

	/*
	 * ⛔ THE REVIEW IS REAL OR IT IS NOT THERE. Standing rules §2 and §3 make
	 *    invented reviews the one absolute. So the manifest's "a review is
	 *    present" row is not enough on its own: the quote must carry a source
	 *    that names where it came from, and the star glyph count must be a
	 *    rendered attribute rather than a decoration this suite invented.
	 */
	bhp_pe_assert(
		false !== strpos( $pe_home, 'amazon-review-card__source' )
			|| false !== strpos( $pe_home, 'amazon-review-card__verified' ),
		'§1.12 the review names its SOURCE (Amazon / verified purchase)  ⭐ PROTECTED: §2 — reviews are never invented, and an unsourced one is indistinguishable from one that was',
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · POSTS — the item-118 regression's own home
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §2 — POSTS ===\n";

$pe_posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	)
);
bhp_pe_assert( count( $pe_posts ) > 0, sprintf( '§2.0 there are published posts to test (%d found)', count( $pe_posts ) ), $failures );

/*
 * ⭐ EVERY POST, NOT A SAMPLE. Item 118 was found by the founder on a post
 *    nobody had opened. A three-post sample would have missed it just as the
 *    absence-suite did.
 */
$pe_bad_rail   = array();
$pe_bad_cap    = array();
$pe_bad_popup  = array();
$pe_bad_asks   = array();
$pe_bad_price  = array();
/* 1.19.273 — the rail contract's four collectors (item 126). */
$pe_bad_contract = array();
$pe_bad_imgclass = array();
$pe_bad_pricesrc = array();
$pe_fetched    = 0;

foreach ( $pe_posts as $pe_p ) {
	$pe_doc = bhp_pe_fetch( get_permalink( $pe_p ) );
	if ( '' === $pe_doc ) {
		continue;
	}
	++$pe_fetched;

	if ( 1 !== substr_count( $pe_doc, '<aside class="bhp-book-rail' ) ) {
		$pe_bad_rail[] = $pe_p->post_name;
	}
	if ( 1 !== substr_count( $pe_doc, 'class="bhp-post-capture"' ) ) {
		$pe_bad_cap[] = $pe_p->post_name;
	}
	if ( false === strpos( $pe_doc, 'parent-ab-popup' ) ) {
		$pe_bad_popup[] = $pe_p->post_name;
	}

	/*
	 * ⭐ THE RAIL CARRIES A LIVE PRICE OR IT DOES NOT RENDER. The resolver
	 *    returns null when no live product price is available, so a rail with
	 *    an empty price element would mean the guard was bypassed — and a
	 *    typed price is the standing-rule §3 failure this component was built
	 *    to make impossible.
	 *
	 * ⚠ THE CURRENCY SYMBOL ARRIVES AS THE ENTITY `&#036;`, NOT AS `$`, AND
	 *   THIS ASSERTION WAS WRONG ABOUT THAT BEFORE THE CODE WAS. WooCommerce's
	 *   `get_woocommerce_currency_symbol()` returns the entity; `esc_html()`
	 *   passes it through un-double-encoded, so the browser renders "$11.99"
	 *   correctly and only a byte-level test could mistake it for a defect.
	 *   Checked live on staging2 1.19.272 before this pattern was widened.
	 */
	if ( ! preg_match( '/class="bhp-book-rail__price">\s*(?:\$|&#0*36;|&#x0*24;)\s*\d/i', $pe_doc ) ) {
		$pe_bad_price[] = $pe_p->post_name;
	}

	/*
	 * ⭐⭐ THE ASK COUNT IS STILL TWO. This is the assertion that reconciles the
	 *     two rulings instead of choosing between them: the founder kept the
	 *     rail (item 110/118) AND cut the post down to two asks (report §4
	 *     item 1). Both hold, because the rail is a BOOK BRIDGE and not an ask.
	 *     An "ask" here is an email capture: the end-of-post form and the
	 *     popup. The footer capture must still be gone.
	 */
	$pe_asks = substr_count( $pe_doc, 'class="bhp-post-capture"' )
		+ ( false !== strpos( $pe_doc, 'parent-ab-popup' ) ? 1 : 0 )
		+ ( ( false !== strpos( $pe_doc, 'id="footer-capture"' )
			|| false !== strpos( $pe_doc, 'acquisition-form--footer-capture' ) ) ? 1 : 0 );
	if ( 2 !== $pe_asks ) {
		$pe_bad_asks[] = $pe_p->post_name . '=' . $pe_asks;
	}

	/*
	 * ⭐⭐ THE RAIL CONTRACT — 1.19.273, founder ruling carrier item 126.
	 *     ⚠ RELAYED through `chief-of-staff`, not witnessed by the agent that
	 *       wrote this. Words live in the carrier record; §4.1 keeps them out
	 *       of this public repository.
	 *
	 *     THE ASSERTION: a rail is in ONE mode, and its IMAGE and its PRICE
	 *     both come from THAT mode. Never mixed.
	 *
	 *         series  ⇒ collection composite  +  collection price
	 *         book    ⇒ that book's cover     +  that book's single price
	 *
	 * ⛔ THIS IS THE ASSERTION THAT WOULD HAVE CAUGHT WHAT ANDREW CAUGHT. Until
	 *    1.19.273 all 29 series rails printed THE MARIANA COVER beside the
	 *    COLLECTION price. Every existing assertion passed: the rail was
	 *    present (§2.2), rendered once, carried a live price (§2.3), and the ask
	 *    count was two (§2.6). Presence and liveness were both true. THE
	 *    PAIRING was the defect, and nothing checked the pairing — so the
	 *    founder found it on staging himself, one release after item 118.
	 *    A cover and a price in one card are read as one object; a bundle price
	 *    under a single cover states that that book costs the bundle price,
	 *    which is a false claim assembled from two true facts.
	 *
	 * ⚠ THE IMAGE LIMB IS CONDITIONAL BY DESIGN, and this is not a softened
	 *   assertion. `book-rail.php` renders no image at all when the attachment
	 *   does not resolve, which is the deliberate degrade-never-mix path. So
	 *   the rule is: IF an image is present it MUST match the mode. An absent
	 *   image is not a contract breach; a MISMATCHED one is. The declarations
	 *   themselves are checked unconditionally below — they are never optional.
	 */
	if ( preg_match( '/<aside class="bhp-book-rail[^>]*>/', $pe_doc, $pe_rail_tag ) ) {
		$pe_tag = $pe_rail_tag[0];

		preg_match( '/data-bhp-rail-kind="([^"]*)"/', $pe_tag, $m_kind );
		preg_match( '/data-bhp-rail-image="([^"]*)"/', $pe_tag, $m_img );
		preg_match( '/data-bhp-rail-price-source="([^"]*)"/', $pe_tag, $m_src );

		$pe_kind = $m_kind[1] ?? '';
		$pe_ikind = $m_img[1] ?? '';
		$pe_psrc  = $m_src[1] ?? '';

		$pe_want = ( 'series' === $pe_kind )
			? array( 'collection', 'collection' )
			: array( 'cover', 'single' );

		if ( $pe_ikind !== $pe_want[0] || $pe_psrc !== $pe_want[1] ) {
			$pe_bad_contract[] = sprintf(
				'%s[kind=%s image=%s price=%s, want image=%s price=%s]',
				$pe_p->post_name, $pe_kind, $pe_ikind, $pe_psrc, $pe_want[0], $pe_want[1]
			);
		}

		/* The rendered image's own class must agree with the declaration. */
		if ( preg_match( '/class="[^"]*bhp-book-rail__img[^"]*"/', $pe_doc, $m_cls ) ) {
			if ( false === strpos( $m_cls[0], 'bhp-book-rail__img--' . $pe_want[0] ) ) {
				$pe_bad_imgclass[] = $pe_p->post_name . '[' . $m_cls[0] . ' want --' . $pe_want[0] . ']';
			}
		}

		/*
		 * ⭐ AND THE PRINTED PRICE IS THE MODE'S OWN LIVE FIGURE — compared by
		 *    VALUE against the same resolver the page used, not against a
		 *    literal. `$11.99` and `$31.99` appear nowhere in this suite.
		 */
		if ( preg_match( '/class="bhp-book-rail__price">\s*(?:\$|&#0*36;|&#x0*24;)\s*([\d,]+\.\d\d)/i', $pe_doc, $m_price ) ) {
			$pe_shown = (float) str_replace( ',', '', $m_price[1] );
			$pe_want_price = null;

			if ( 'series' === $pe_kind && function_exists( 'bhp_bundle_landing_price_facts' ) ) {
				$pe_fmt = function_exists( 'bhp_bundle_default_format' ) ? bhp_bundle_default_format() : 'paperback';
				$pe_cf  = bhp_bundle_landing_price_facts( $pe_fmt );
				$pe_want_price = is_array( $pe_cf ) ? (float) ( $pe_cf['bundle'] ?? 0 ) : null;
			} elseif ( function_exists( 'bhp_blog_rail_facts' ) ) {
				$pe_bf = bhp_blog_rail_facts( $pe_p );
				if ( $pe_bf ) {
					$pe_want_price = (float) preg_replace( '/[^\d.]/', '', html_entity_decode( (string) $pe_bf['price'] ) );
				}
			}

			if ( null !== $pe_want_price && $pe_want_price > 0
				&& abs( $pe_shown - $pe_want_price ) > 0.005 ) {
				$pe_bad_pricesrc[] = sprintf( '%s[shown=%.2f live=%.2f]', $pe_p->post_name, $pe_shown, $pe_want_price );
			}
		}
	}
}

bhp_pe_assert(
	$pe_fetched === count( $pe_posts ),
	sprintf( '§2.1 all %d posts fetched (%d returned 200)', count( $pe_posts ), $pe_fetched ),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_rail ),
	sprintf(
		'§2.2 the BOOK RAIL renders EXACTLY ONCE on every post%s  ⭐ PROTECTED: ruling item 110 (kept) + item 118 (founder-found regression, CYCLE165-COS-009)',
		$pe_bad_rail ? ' — WRONG COUNT on: ' . implode( ', ', $pe_bad_rail ) : ''
	),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_price ),
	sprintf(
		'§2.3 …and every rail prints a LIVE price%s  ⭐ PROTECTED: standing rules §3 — no figure on the rail is typed',
		$pe_bad_price ? ' — MISSING on: ' . implode( ', ', $pe_bad_price ) : ''
	),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_cap ),
	sprintf(
		'§2.4 the END-OF-POST CAPTURE renders EXACTLY ONCE on every post%s  ⭐ PROTECTED: report §4 item 1 — "keep the end-of-post capture"',
		$pe_bad_cap ? ' — WRONG COUNT on: ' . implode( ', ', $pe_bad_cap ) : ''
	),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_popup ),
	sprintf(
		'§2.5 the POPUP ENGINE is present on every post%s  ⭐ PROTECTED: report §4 item 1 keeps it; ruling item 110 limb (4) approved it as built',
		$pe_bad_popup ? ' — MISSING on: ' . implode( ', ', $pe_bad_popup ) : ''
	),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_asks ),
	sprintf(
		'§2.6 the ASK COUNT on every post is exactly TWO — capture + popup%s  ⭐ PROTECTED: report §4 item 1. The rail is a BOOK, not an ask, which is why §2.2 and this assertion are both true at once',
		$pe_bad_asks ? ' — WRONG on: ' . implode( ', ', $pe_bad_asks ) : ''
	),
	$failures
);

/* ── 1.19.273 · THE RAIL CONTRACT (founder ruling item 126) ──────────────── */
bhp_pe_assert(
	empty( $pe_bad_contract ),
	sprintf(
		'§2.6a THE RAIL CONTRACT: every rail declares an image kind and a price source that MATCH ITS MODE — series⇒collection/collection, book⇒cover/single%s  ⭐ PROTECTED: ruling item 126. A collection price under one book\'s cover is a false price claim (standing rules §3, derived-claim trap)',
		$pe_bad_contract ? ' — MIXED on: ' . implode( ', ', $pe_bad_contract ) : ''
	),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_imgclass ),
	sprintf(
		'§2.6b …and the RENDERED IMAGE\'s own class agrees with that declaration%s  ⭐ PROTECTED: ruling item 126 — a declaration the picture contradicts is worse than none',
		$pe_bad_imgclass ? ' — MISMATCH on: ' . implode( ', ', $pe_bad_imgclass ) : ''
	),
	$failures
);
bhp_pe_assert(
	empty( $pe_bad_pricesrc ),
	sprintf(
		'§2.6c …and the PRINTED PRICE equals the mode\'s own LIVE figure, compared by value against the live resolver%s  ⭐ PROTECTED: ruling item 126 + standing rules §3 — no figure on the rail is typed, and none is borrowed from the other mode',
		$pe_bad_pricesrc ? ' — WRONG SOURCE on: ' . implode( ', ', $pe_bad_pricesrc ) : ''
	),
	$failures
);

/*
 * ⭐ THE POPUP'S SCOPE IS HOMEPAGE + BLOG, AND THE PROTECTION IS THAT IT STILL
 *    IS. Exercised through the live eligibility function rather than inferred
 *    from markup, because markup can only ever show where it DID render.
 */
if ( function_exists( 'bhp_should_show_parent_ab_popup' ) ) {
	$pe_saved_user = get_current_user_id();
	wp_set_current_user( 0 );
	$pe_gate = function ( $args ) {
		$saved                   = $GLOBALS['wp_query'];
		$saved_main              = $GLOBALS['wp_the_query'];
		$GLOBALS['wp_query']     = new WP_Query( $args ); // phpcs:ignore
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];  // phpcs:ignore
		if ( $GLOBALS['wp_query']->have_posts() ) {
			$GLOBALS['wp_query']->the_post();
		}
		$out = bhp_should_show_parent_ab_popup();
		wp_reset_postdata();
		$GLOBALS['wp_query']     = $saved;      // phpcs:ignore
		$GLOBALS['wp_the_query'] = $saved_main; // phpcs:ignore
		return $out;
	};
	bhp_pe_assert(
		true === (bool) $pe_gate( array( 'p' => $pe_posts[0]->ID, 'post_type' => 'post' ) ),
		'§2.7 LIVE GATE: the popup is eligible on a single post  ⭐ PROTECTED: ruling item 110 limb (4) — homepage + blog scope',
		$failures
	);
	wp_set_current_user( $pe_saved_user );
} else {
	bhp_pe_assert( false, '§2.7 bhp_should_show_parent_ab_popup() must exist for the popup scope check', $failures );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · PRODUCTS
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §3 — PRODUCTS ===\n";

/*
 * ⭐ THE CANONICAL PAPERBACK PAGES, RESOLVED LIVE. Slugs are not typed here:
 *    the registry that owns the products is asked which pages are canonical, so
 *    a fourth title is covered the day it is published.
 */
$pe_products = array();
if ( function_exists( 'bhp_get_series_adventures' ) ) {
	foreach ( bhp_get_series_adventures() as $pe_adv ) {
		if ( ! empty( $pe_adv['available'] ) && ! empty( $pe_adv['primary_url'] ) ) {
			$pe_products[] = (string) $pe_adv['primary_url'];
		}
	}
}
bhp_pe_assert(
	count( $pe_products ) >= 3,
	sprintf( '§3.0 the product registry resolved %d canonical product URLs (>=3 expected)', count( $pe_products ) ),
	$failures
);

$pe_prod_doc = '' !== ( $pe_products[0] ?? '' ) ? bhp_pe_fetch( $pe_products[0] ) : '';
bhp_pe_assert( '' !== $pe_prod_doc, '§3.1 the first canonical product page was fetched and returned 200', $failures );
if ( '' !== $pe_prod_doc ) {
	bhp_pe_run_manifest( 'product', $pe_prod_doc, 'the product page', '3', $failures );

	/*
	 * ⭐⭐ "INSIDE THE FIRST SCREEN" IS ASSERTED AS AN ORDERING FACT, NOT AS A
	 *     PIXEL FACT, AND THE DIFFERENCE IS STATED. A markup suite cannot know
	 *     where a fold is. What it CAN know — and what the Direction-1 defect
	 *     actually was — is whether price, format and ATC come BEFORE the
	 *     long-form body. If a future edit pushes the buy box below the
	 *     description again, this fails. The pixel measurement stays in the
	 *     browser pass at 390 and 1440, and neither substitutes for the other.
	 */
	$pe_atc  = strpos( $pe_prod_doc, 'bhp-formats__cta' );
	$pe_body = strpos( $pe_prod_doc, 'woocommerce-Tabs-panel' );
	if ( false === $pe_body ) {
		$pe_body = strpos( $pe_prod_doc, 'woocommerce-tabs' );
	}
	bhp_pe_assert(
		false !== $pe_atc && false !== $pe_body && $pe_atc < $pe_body,
		sprintf(
			'§3.7 ADD TO CART precedes the long-form product body (atc@%s, body@%s)  ⭐ PROTECTED: item 119 (sales) — Iterate-1 moved the buy box back inside the fold and it must stay there',
			var_export( $pe_atc, true ), var_export( $pe_body, true )
		),
		$failures
	);

	/*
	 * ⛔ THE SHIPPING LINE IS IN ANDREW'S VOICE. Standing rules §9.1: no "we"
	 *    in customer-facing words. Checked on the note element itself rather
	 *    than the whole document, because a third-party review quoting "we" is
	 *    protected by §9.1a and must never be rewritten.
	 */
	$pe_note = array();
	preg_match( '/<p class="bhp-formats__note"[^>]*>(.*?)<\/p>/s', $pe_prod_doc, $pe_note );
	$pe_note_txt = isset( $pe_note[1] ) ? wp_strip_all_tags( $pe_note[1] ) : '';
	/*
	 * ⚠ CASE-SENSITIVE ON PURPOSE, AND THIS ASSERTION WAS WRONG BEFORE THE CODE
	 *   WAS. A case-insensitive `\bus\b` matches "contiguous **US**" — the
	 *   country, not the pronoun — and reported a voice violation in a line
	 *   that is already correctly in Andrew's voice. Lowercase `us` only;
	 *   `we`/`our` in either case, because both can open a sentence.
	 */
	bhp_pe_assert(
		'' !== $pe_note_txt
			&& ! preg_match( '/\b(?:[Ww]e|[Oo]ur|us)\b/', $pe_note_txt ),
		sprintf( '§3.8 the shipping note carries no company "we" — %s  ⭐ PROTECTED: standing rules §9.1, the founder is the sole operator', var_export( $pe_note_txt, true ) ),
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · COLLECTION — the money page
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §4 — COLLECTION ===\n";

$pe_coll = bhp_pe_fetch( home_url( '/complete-collection/' ) );
bhp_pe_assert( '' !== $pe_coll, '§4.0 /complete-collection/ was fetched and returned 200', $failures );
if ( '' !== $pe_coll ) {
	bhp_pe_run_manifest( 'collection', $pe_coll, 'the collection page', '4', $failures );

	/*
	 * ⭐ THE ANCHOR IS AN ANCHOR — was > now, and the saving is the difference.
	 *    A price anchor whose arithmetic does not hold is a claim the page
	 *    cannot support (standing rules §3, the derived-claim trap). Recomputed
	 *    here from the rendered numbers, not inherited from any document.
	 */
	$pe_was  = array();
	$pe_now  = array();
	$pe_save = array();
	preg_match( '/price-was[^>]*>\$([0-9.,]+)/', $pe_coll, $pe_was );
	preg_match( '/price-now[^>]*>\$([0-9]+)<span[^>]*>\.([0-9]{2})/', $pe_coll, $pe_now );
	preg_match( '/price-save[^>]*>Save \$([0-9.,]+)/', $pe_coll, $pe_save );

	$pe_was_f  = isset( $pe_was[1] ) ? (float) str_replace( ',', '', $pe_was[1] ) : 0.0;
	$pe_now_f  = ( isset( $pe_now[1] ) && isset( $pe_now[2] ) ) ? (float) ( $pe_now[1] . '.' . $pe_now[2] ) : 0.0;
	$pe_save_f = isset( $pe_save[1] ) ? (float) str_replace( ',', '', $pe_save[1] ) : 0.0;

	bhp_pe_assert(
		$pe_was_f > 0 && $pe_now_f > 0 && $pe_was_f > $pe_now_f,
		sprintf( '§4.7 the price anchor reads was $%.2f > now $%.2f  ⭐ PROTECTED: item 119 (sales)', $pe_was_f, $pe_now_f ),
		$failures
	);
	bhp_pe_assert(
		$pe_save_f > 0 && abs( ( $pe_was_f - $pe_now_f ) - $pe_save_f ) < 0.005,
		sprintf( '§4.8 …and the stated saving $%.2f IS the difference $%.2f  ⭐ PROTECTED: standing rules §3 — a derived claim is recomputed, never inherited', $pe_save_f, $pe_was_f - $pe_now_f ),
		$failures
	);

	/*
	 * ⭐ ONE PRIMARY. The founder's subtraction sheet and the CRO rubric agree
	 *    on this and it is the rule the audience router was removed to protect.
	 *
	 * ⚠ "ONE" IS ONE *CONTROL*, NOT ONE OCCURRENCE IN THE MARKUP, AND THIS
	 *   ASSERTION WAS WRONG BEFORE THE CODE WAS. The page renders the SAME
	 *   primary once per format panel — paperback visible, hardcover `hidden` —
	 *   so a naive count of 1 fails on a page that is behaving correctly.
	 *   Verified live on staging2 1.19.272: two occurrences, both carrying
	 *   `data-bhp-landing-main-cta`, one inside a `hidden` panel.
	 *
	 *   ⭐ THE PROTECTION IS THEREFORE STATED PRECISELY: every primary CTA on
	 *      this page is THE SAME CONTROL. A genuinely second, competing primary
	 *      would be a different element and would not carry that attribute —
	 *      which is exactly the regression this assertion has to catch.
	 */
	$pe_prim = substr_count( $pe_coll, 'bhp-landing-cta--primary' );
	$pe_main = substr_count( $pe_coll, 'data-bhp-landing-main-cta' );
	bhp_pe_assert(
		$pe_prim >= 1 && $pe_prim === $pe_main,
		sprintf( '§4.9 every primary CTA is the one main control (%d primary, %d main-cta)  ⭐ PROTECTED: report §4 — one primary per screen; format panels repeat it, they do not compete with it', $pe_prim, $pe_main ),
		$failures
	);
	/*
	 * ⭐ AND ONLY ONE FORMAT IS ON SHOW AT A TIME. Every format panel's opening
	 *    tag is read; the ones without `hidden` must all name the SAME format.
	 *    If a future edit un-hid the hardcover panels, the visible set would
	 *    hold two format names and this fails — which is the real "two
	 *    primaries on the money page" regression, expressed as a fact about the
	 *    page rather than as a count of a class name.
	 */
	$pe_panels  = array();
	preg_match_all( '/data-bhp-format-panel="([a-z]+)"([^>]*)>/', $pe_coll, $pe_panels, PREG_SET_ORDER );
	$pe_visible = array();
	foreach ( $pe_panels as $pe_pan ) {
		if ( ! preg_match( '/\bhidden\b/', $pe_pan[2] ) ) {
			$pe_visible[ $pe_pan[1] ] = true;
		}
	}
	bhp_pe_assert(
		count( $pe_panels ) > 0 && 1 === count( $pe_visible ),
		sprintf(
			'§4.10 …and exactly ONE format is visible at a time (%d panels, visible formats: %s)  ⭐ PROTECTED: the customer sees one primary, not two',
			count( $pe_panels ),
			$pe_visible ? implode( '+', array_keys( $pe_visible ) ) : 'none'
		),
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · SITEWIDE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §5 — SITEWIDE ===\n";

/*
 * ⭐ THE HEADER OFFER IS A PROTECTED SALES ELEMENT *AND* A SUPPRESSION RULE.
 *    Both halves are protected: it must render where nothing else is primary,
 *    and it must yield where something is. Asserting only the first would let
 *    a regression put two primaries on the money page.
 */
bhp_pe_assert(
	'' !== $pe_home && false !== strpos( $pe_home, 'bhp-header-offer' ),
	'§5.1 the header-offer component renders on the homepage  ⭐ PROTECTED: item 119 (sales) — Direction 1 took zero-primary pages at 390 from 69 to 0',
	$failures
);
bhp_pe_assert(
	'' !== $pe_home && false !== strpos( $pe_home, 'data-bhp-offer-watch' ),
	'§5.2 …carrying the watch list its reveal script needs  ⭐ PROTECTED: without it the deferred button never appears, which is a silent loss',
	$failures
);
bhp_pe_assert(
	'' !== $pe_coll && false === strpos( $pe_coll, 'class="bhp-header-offer"' ),
	'§5.3 …and it SUPPRESSES itself on /complete-collection/, which has its own primary  ⭐ PROTECTED: the suppression rules are half the ruling',
	$failures
);

if ( function_exists( 'bhp_header_offer_context' ) ) {
	$pe_ctx_saved = $GLOBALS['wp_query'];
	$pe_ctx_main  = $GLOBALS['wp_the_query'];
	$pe_coll_page = get_page_by_path( 'complete-collection' );
	if ( $pe_coll_page ) {
		$GLOBALS['wp_query']     = new WP_Query( array( 'page_id' => $pe_coll_page->ID ) ); // phpcs:ignore
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore
		if ( $GLOBALS['wp_query']->have_posts() ) {
			$GLOBALS['wp_query']->the_post();
		}
		bhp_pe_assert(
			'suppress' === bhp_header_offer_context(),
			'§5.4 LIVE GATE: bhp_header_offer_context() resolves to "suppress" on the collection page  ⭐ PROTECTED: the rule is exercised, not read from markup',
			$failures
		);
		wp_reset_postdata();
	}
	$GLOBALS['wp_query']     = $pe_ctx_saved; // phpcs:ignore
	$GLOBALS['wp_the_query'] = $pe_ctx_main;  // phpcs:ignore
} else {
	bhp_pe_assert( false, '§5.4 bhp_header_offer_context() must exist for the suppression check', $failures );
}

/*
 * ⭐ CART AND CHECKOUT ARE REACHABLE. The cheapest possible check on the one
 *    path where a failure costs an order outright.
 *
 * ⚠ 302 IS A PASS ON /checkout/ AND SAYING SO IS THE POINT. An EMPTY cart
 *   sends checkout to the cart page — that is WooCommerce working, not a
 *   defect, and it is the same 302 that `inc/seo-hygiene.php` keeps out of the
 *   sitemap. What would be a real failure is a 404 or a 5xx.
 */
$pe_cart_status     = bhp_pe_status( wc_get_cart_url() );
$pe_checkout_status = bhp_pe_status( wc_get_checkout_url() );
bhp_pe_assert(
	200 === $pe_cart_status,
	sprintf( '§5.5 the CART is reachable (HTTP %d)  ⭐ PROTECTED: item 119 (sales)', $pe_cart_status ),
	$failures
);
bhp_pe_assert(
	in_array( $pe_checkout_status, array( 200, 301, 302 ), true ),
	sprintf( '§5.6 CHECKOUT is reachable (HTTP %d; 302-to-cart on an empty cart is correct)  ⭐ PROTECTED: item 119 (sales)', $pe_checkout_status ),
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   RESULT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== RESULT ===\n";
printf( "FAILURES: %d\n", count( $failures ) );
foreach ( $failures as $pe_f ) {
	echo "  - {$pe_f}\n";
}
if ( empty( $failures ) ) {
	echo "ALL PROTECTED ELEMENTS PRESENT.\n";
} else {
	echo "\n⛔ A PROTECTED ELEMENT IS MISSING. Under founder ruling item 119 this is\n";
	echo "   NOT a test to relax: the manifest changes first, on Andrew's explicit\n";
	echo "   word, and this suite changes with it.\n";
}
