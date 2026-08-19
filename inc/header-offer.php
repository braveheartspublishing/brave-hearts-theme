<?php
/**
 * THE MOBILE-HEADER OFFER — one compact primary, outside the hamburger.
 * ============================================================================
 *
 * Andrew Signore, 2026-08-19, ⛔ RELAYED through `chief-of-staff` in the
 * `CYCLE165-LD-DIRECTION1-STEP1-HEADER` brief; NOT witnessed first-hand by the
 * agent that wrote this file. Two rulings govern it:
 *
 *   FD-521 (carrier 99) — Direction 1, "Expedition field notes", is chosen, and
 *   it is built in the board's own order: (1) THIS component, (2) the blog post
 *   template, (3) the product template, (4) the homepage. Step 1 is this file
 *   and nothing else.
 *
 *   Item 96(7) — a FREE-SAMPLE / lead CTA COUNTS AS PRIMARY under FD-479 limb 3
 *   ("only the top CTA above the fold"). That single sentence is what makes the
 *   homepage rule below necessary rather than optional.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE PROBLEM IT SOLVES, MEASURED RATHER THAN ASSERTED
 * ─────────────────────────────────────────────────────────────────────────────
 * `commerce-cx`'s full-site visual inventory (2026-08-19) found 69 of 83 pages
 * with NO buy CTA above the fold at 390, because the only sitewide purchase
 * control — `.header-expedition-cta` — is `display: none` at the mobile
 * breakpoint and its counterpart `.site-nav__cta` lives INSIDE the closed
 * hamburger. A phone visitor therefore has to open a menu before the site
 * offers to sell anything. The blog is 37.7% of human page views and had no
 * buy path at all.
 *
 * Re-measured on staging2 1.19.259 by this build before a line was written,
 * headless Chrome at a `window.innerWidth`-asserted 390x844, scrollY 0
 * (evidence: `CYCLE165-direction1-step1-qa/before/BEFORE-1.19.259-390.json`):
 *
 *     home          1 primary   "Open the book. Read the first pages free"
 *     blog post     0
 *     blog index    0
 *     product       0
 *     shop          0
 *     collection    1 primary   "Get the Complete Collection"
 *     cart          1 primary   (the empty-cart CTA)
 *     checkout      1 primary   (the empty-cart CTA)
 *     static page   0
 *
 * ⭐ THAT TABLE IS THE SPEC. The component renders on the five templates that
 *    measured ZERO, defers on the homepage, and suppresses itself on the three
 *    that already measured ONE. The rule is derived from what the site actually
 *    renders, not from a guess about what it probably renders.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ IT REPLACES A SECOND PRIMARY. IT NEVER ADDS ONE.
 * ─────────────────────────────────────────────────────────────────────────────
 * FD-479 limb 3 allows exactly one CTA above the fold. So:
 *
 *   · /complete-collection/  SUPPRESSED. The page already carries its own
 *     above-fold buy CTA, and this control points at the page the visitor is
 *     already on — which is also why the desktop control carries
 *     `aria-current="page"` there and this one would have to lie to match.
 *   · /cart/ and /checkout/  SUPPRESSED. Both measured a primary above the
 *     fold already, and a sitewide "buy the set" control on a screen where a
 *     customer is mid-payment is the defect class `bhp_collection_cta_context_allows_add()`
 *     was written for. This is the same judgement, applied to a link.
 *   · the HOMEPAGE  DEFERRED, not suppressed — see the next block.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⭐⭐ THE HOMEPAGE RULE, AND WHY IT IS "REVEAL AFTER SCROLL" AND NOT "HIDE"
 * ─────────────────────────────────────────────────────────────────────────────
 * The homepage hero's free-sample CTA (`.home-hero__invite--primary`, measured
 * at top=361 h=70 at 390) IS the homepage's primary under item 96(7). Showing
 * the offer beside it would put two primaries on the first screen.
 *
 * So on `.home` the button is RENDERED and OCCUPIES ITS BOX from the first
 * paint, but is `visibility: hidden; opacity: 0` until the hero primary has
 * left the viewport. Once the visitor has scrolled past the free sample, the
 * fold no longer contains it, and revealing the offer cannot create a second
 * above-fold primary because there is no longer a first one.
 *
 * ⛔ IT OCCUPIES ITS BOX WHILE HIDDEN, AND THAT IS THE WHOLE REASON `visibility`
 *    IS USED INSTEAD OF `display`. Andrew's brief requires NO LOGO LAYOUT SHIFT.
 *    A button that appears mid-scroll and takes width from a `space-between`
 *    flex row would move the wordmark sideways — the exact 53.27px nav shift
 *    recorded in `inc/collection-cta.php` that caused him to reverse the 1.19.183
 *    header build. Reserving the box makes the reveal a pure opacity change with
 *    a measured CLS contribution of zero.
 *
 * ⛔ IT FAILS CLOSED ON THE HOMEPAGE, DELIBERATELY. No JavaScript, no
 *    `IntersectionObserver`, or a hero CTA this script cannot find, all leave
 *    the button hidden. The cost of failing closed is that a phone visitor on
 *    the homepage does not get the offer in the header — and the homepage is
 *    the one page that already has a primary above the fold. The cost of
 *    failing OPEN would be two primaries on the site's most-seen page, which is
 *    the rule this component exists to respect.
 *
 * ⚠ `visibility: hidden` also removes the anchor from the tab order, so a
 *   keyboard visitor is never sent to a control they cannot see.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ THE PRICE IS READ FROM LIVE BUNDLE DATA. THERE IS NO LITERAL IN THIS FILE.
 * ─────────────────────────────────────────────────────────────────────────────
 * `bhp_bundle_landing_price_facts()` sums `get_price()` across the three real
 * product records and subtracts the approved tier-3 discount — the same
 * derivation the Complete Collection page prints and the same figure
 * `bhp_bundle_apply_discount()` charges the cart. If Andrew re-prices a title,
 * this button follows in the same request.
 *
 * ⛔ IF THE PRICE CANNOT BE READ, THE BUTTON DOES NOT RENDER. It never falls
 *    back to a typed number. A stale price on a sitewide purchase control is a
 *    price claim, and a wrong one is an FTC-class problem, not a display bug.
 *    `bhp_bundle_landing_price_facts()` has its own approved-constant fallback
 *    for the case where WooCommerce is present but a product is unresolvable;
 *    this guard covers the case where the plugin is absent entirely.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * SCHOOL-VISIT SESSIONS ARE PAPERBACK ONLY
 * ─────────────────────────────────────────────────────────────────────────────
 * A visit-flagged session cannot be sold a hardcover — Andrew carries
 * paperbacks to a read-aloud and signs them by hand (1.8.57,
 * `school-visit-paperback-only.php`). The site default is already paperback,
 * so today the two agree. They are NOT the same requirement: this file forces
 * paperback for a flagged session explicitly, so that if the sitewide default
 * ever moves back to hardcover, a flagged parent is not quoted a price for a
 * format the server will refuse to sell them.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT THIS FILE DOES NOT DO
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ It changes NO price, discount, shipping tier, coupon, tax, stock status,
 *    product record, variation, SKU, Bookvault mapping or WooCommerce setting.
 *    It reads a derived figure and prints it.
 * ⛔ It does not post, add to cart, or mutate a cart. It is an ANCHOR to
 *    `/complete-collection/`, for exactly the reason Andrew gave on 2026-08-05
 *    when he reversed the header purchase form: that page is cached, a POST
 *    never is, and the two-click purchase already lives there.
 * ⛔ It does not touch `.header-expedition-cta` or `.site-nav__cta`. The
 *    desktop header is byte-identical to 1.19.259.
 *
 * VOICE: standing rule §9.1. No "we". No em dash. The separator is a middle
 * dot, which is punctuation, not a dash.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The whole component behind one filter, default ON.
 *
 * Turning this off returns the header to its 1.19.259 behaviour exactly: the
 * markup is not emitted, the stylesheet rules match nothing, and the reveal
 * script is not enqueued.
 *
 * @return bool
 */
function bhp_header_offer_enabled() {
	return (bool) apply_filters( 'bhp_header_offer_enabled', true );
}

/**
 * Which format the offer quotes.
 *
 * @return string 'paperback'|'hardcover'
 */
function bhp_header_offer_format() {
	$restricted = function_exists( 'bhp_school_visit_paperback_only' ) && bhp_school_visit_paperback_only();
	return bhp_header_offer_format_for( $restricted );
}

/**
 * The pure half of the format decision, split out SO IT CAN BE TESTED HONESTLY.
 *
 * ⭐ WHY THE SPLIT EXISTS, recorded so it is not "tidied" back together.
 *    `bhp_school_visit_paperback_only()` returns false and never reaches its own
 *    filter unless the request already carries a live visit flag in the
 *    WooCommerce session. Under WP-CLI there is no session, so the restricted
 *    branch is UNREACHABLE from a test suite. The two options were to fake a
 *    session (which writes state a test must never write) or to make the
 *    decision itself a pure function of one boolean. This is the second.
 *
 *    The suite therefore proves the DECISION; a real browser walking a live
 *    `?bhp_visit=` link proves the PREDICATE. Both are recorded, neither is
 *    claimed on the other's evidence.
 *
 * @param bool $paperback_only True when this session cannot be sold a hardcover.
 * @return string 'paperback'|'hardcover'
 */
function bhp_header_offer_format_for( $paperback_only ) {
	if ( $paperback_only ) {
		return 'paperback';
	}
	if ( function_exists( 'bhp_bundle_default_format' ) ) {
		return bhp_bundle_default_format();
	}
	return 'paperback';
}

/**
 * The collection price, DERIVED FROM LIVE PRODUCT RECORDS, or null.
 *
 * @return float|null
 */
function bhp_header_offer_price() {
	if ( ! function_exists( 'bhp_bundle_landing_price_facts' ) ) {
		return null; // No bundle data -> no price -> no button. Never a literal.
	}
	$facts = bhp_bundle_landing_price_facts( bhp_header_offer_format() );
	if ( ! is_array( $facts ) || ! isset( $facts['bundle'] ) ) {
		return null;
	}
	$price = (float) $facts['bundle'];
	return $price > 0 ? $price : null;
}

/**
 * How many titles the offer names. Read from the catalog, not typed.
 *
 * @return int
 */
function bhp_header_offer_title_count() {
	if ( ! function_exists( 'bhp_bundle_catalog' ) ) {
		return 3;
	}
	$catalog = bhp_bundle_catalog();
	$format  = bhp_header_offer_format();
	return isset( $catalog[ $format ] ) ? count( $catalog[ $format ] ) : 3;
}

/**
 * What this request should do with the offer.
 *
 * 'render'   — show it immediately. The template measured ZERO above-fold
 *              primaries at 390, so this button becomes the one.
 * 'defer'    — render it, reserve its box, keep it hidden until the page's own
 *              above-fold primary has been scrolled past. Homepage only.
 * 'suppress' — do not emit it at all.
 *
 * @return string
 */
function bhp_header_offer_context() {
	if ( ! bhp_header_offer_enabled() ) {
		return 'suppress';
	}

	// The purchase path itself. Measured: both already carry a primary above
	// the fold at 390, and a customer here is inside the funnel, not looking
	// for its entrance.
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return 'suppress';
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return 'suppress';
	}

	// The destination itself. Measured: 1 primary above the fold at 390, and
	// this control would point at the current page.
	if ( is_page( 'complete-collection' ) ) {
		return 'suppress';
	}

	/*
	 * The homepage hero's free-sample CTA is THE primary (item 96(7)).
	 *
	 * ⛔ `is_front_page()` ONLY. `is_home()` IS NOT A SYNONYM AND USING IT HERE
	 *    IS A BUG — caught by this build's own §2 matrix before deploy. This site
	 *    runs `show_on_front = page` with `page_for_posts` set, so `is_home()` is
	 *    true on the BLOG INDEX, which measured ZERO above-fold primaries at 390
	 *    and must therefore show the offer immediately. Deferring there would have
	 *    hidden the button on the section that carries 37.7% of human page views,
	 *    behind a reveal script watching for a hero CTA that page does not have.
	 */
	if ( is_front_page() ) {
		return 'defer';
	}

	return 'render';
}

/**
 * Filterable list of selectors the reveal script watches on a deferred page.
 *
 * The first one that resolves wins. `.home-hero__invite--primary` is the
 * homepage hero's free-sample CTA as it renders at 1.19.259, measured; the
 * others are fallbacks so a homepage rebuild (step 4 of this board) does not
 * silently turn the reveal into an always-on button.
 *
 * @return array
 */
function bhp_header_offer_defer_watch_selectors() {
	return (array) apply_filters(
		'bhp_header_offer_defer_watch_selectors',
		array(
			'.home-hero__invite--primary',
			'.home-hero__invite',
			'.home-hero .btn',
		)
	);
}

/**
 * Render the control. Returns '' when this request must not carry one.
 *
 * @return string HTML.
 */
function bhp_header_offer() {
	$context = bhp_header_offer_context();
	if ( 'suppress' === $context ) {
		return '';
	}

	$price = bhp_header_offer_price();
	if ( null === $price ) {
		return ''; // No live price -> no button. See the docblock.
	}

	$count = bhp_header_offer_title_count();

	/*
	 * The visible label is two short lines, as the direction board draws it:
	 * "ALL 3" over the price. The accessible name is a whole sentence, because
	 * "ALL 3 $31.99" read aloud is not a destination.
	 */
	$count_text = sprintf(
		/* translators: %d: number of books in the collection. */
		_n( 'All %d', 'All %d', $count, 'brave-hearts' ),
		$count
	);
	$price_text = function_exists( 'wc_price' )
		? wp_strip_all_tags( wc_price( $price, array( 'decimals' => 2 ) ) )
		: '$' . number_format( $price, 2 );

	$aria = sprintf(
		/* translators: 1: number of books, 2: collection price. */
		__( 'All %1$d books for %2$s. Open the Complete Collection page.', 'brave-hearts' ),
		$count,
		$price_text
	);

	$state = ( 'defer' === $context ) ? 'deferred' : 'visible';

	$attrs = '';
	if ( 'deferred' === $state ) {
		$attrs .= ' data-bhp-offer-watch="' . esc_attr( implode( ',', bhp_header_offer_defer_watch_selectors() ) ) . '"';
	}

	return sprintf(
		'<a class="bhp-header-offer" href="%1$s" data-bhp-offer-state="%2$s" data-bhp-event="header_offer_click" data-bhp-source="mobile-header" aria-label="%3$s"%4$s><span class="bhp-header-offer__count">%5$s</span><span class="bhp-header-offer__price">%6$s</span></a>',
		esc_url( home_url( '/complete-collection/' ) ),
		esc_attr( $state ),
		esc_attr( $aria ),
		$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
		esc_html( $count_text ),
		esc_html( $price_text )
	);
}

/**
 * The reveal script, enqueued ONLY on a page that actually defers.
 *
 * Every other page gets no extra bytes: the button is either already visible or
 * not emitted at all, and in both cases there is nothing for a script to do.
 */
function bhp_header_offer_enqueue() {
	if ( 'defer' !== bhp_header_offer_context() ) {
		return;
	}
	if ( '' === bhp_header_offer() ) {
		return; // No price -> no button -> no script.
	}
	$theme_version = wp_get_theme()->get( 'Version' );
	wp_enqueue_script(
		'bhp-header-offer',
		get_template_directory_uri() . '/assets/js/header-offer.js',
		array(),
		$theme_version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_header_offer_enqueue' );
