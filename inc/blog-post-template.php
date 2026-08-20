<?php
/**
 * THE BLOG POST TEMPLATE — Direction 1, "Expedition field notes", step 2 of 4.
 * ============================================================================
 *
 * Andrew Signore, 2026-08-19, ⛔ RELAYED through `chief-of-staff` in the
 * `CYCLE165-LD-DIRECTION1-STEP2-BLOG` brief; NOT witnessed first-hand by the
 * agent that wrote this file. FD-521 (carrier 99) chooses Direction 1 and fixes
 * the build order: (1) the mobile-header offer — shipped at 1.19.260,
 * `inc/header-offer.php` — (2) THIS FILE, (3) the product template, (4) the
 * homepage. Steps 3 and 4 are out of scope here and nothing below reaches them.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE FOUR DEFECTS IT ANSWERS, EACH MEASURED BEFORE A LINE WAS WRITTEN
 * ─────────────────────────────────────────────────────────────────────────────
 * The CRO rubric of 2026-08-19 scores the blog post template FAIL on rows 1, 4,
 * 8 and 13. The blog carries the largest share of human page views on this site
 * and the product pages carry a small fraction of it, so a template defect here
 * is the highest-traffic defect on the site.
 *
 * ⚠ THE ACTUAL TRAFFIC SPLIT IS DELIBERATELY NOT QUOTED IN THIS FILE. This
 *   repository is PUBLIC on GitHub, and the site's internal traffic
 *   distribution is a business figure, not an engineering fact this component
 *   needs. The numbers, their source and their date live in the private record
 *   named at the foot of this block. Standing rule §4.1: a public file may
 *   POINT AT a private source; it may not reproduce its contents.
 *
 * Re-measured on staging2 1.19.260 by this build, headless Chrome at an
 * asserted `window.innerWidth`, scrollY 0 (evidence:
 * `CYCLE165-direction1-step2-qa/before/BEFORE-1.19.260-390.json`):
 *
 *     dead band, header bottom to H1 top   289 px  @390     (rubric row 4)
 *     book rails in the body                 0              (rubric row 13)
 *     lead captures in the post              0              (rubric row 8)
 *     above-fold primaries                   1              (rubric row 1 PASS)
 *
 * ⭐ ROW 1 ALREADY PASSES AND MUST KEEP PASSING. The single above-fold primary
 *    at 390 is step 1's `.bhp-header-offer`. Everything this file adds is
 *    therefore placed BELOW THE FOLD BY CONSTRUCTION, and the suite asserts it.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ WHERE THIS BUILD DEPARTS FROM THE DIRECTION BOARD, AND WHY
 * ─────────────────────────────────────────────────────────────────────────────
 * The board's own `d1-blog-390` mock draws the book rail ABOVE the fold, right
 * under the meta line, and labels its CTA "READ FREE". This build does neither.
 * Both departures are deliberate and both are recorded rather than smoothed:
 *
 *   1. THE RAIL SITS BELOW THE FOLD. The mock's rail plus step 1's header offer
 *      would put TWO primaries on the first screen — a buy control and a
 *      free-sample control — and item 96(7) makes a free-sample CTA count as
 *      primary under `FD-479` limb 3. The mock was drawn before step 1 shipped.
 *      Placing the rail after the first useful answer (rubric row 13's own
 *      wording, and `ads-knowledge` §5.2) satisfies the bridge requirement and
 *      keeps row 1 green at the same time.
 *
 *   2. THE CTA IS NOT "READ FREE". See `bhp_blog_rail_cta()` below for the full
 *      reasoning. In one line: no ungated reading sample exists on this site, so
 *      "Read free" beside a cover, an age band and a price would promise what
 *      the destination cannot deliver.
 *
 * ⚠ The board's mock copy is marked PROPOSAL / UNAPPROVED in its own README §5.
 *   Nothing here treats it as approved copy, and the two departures above are
 *   routed to Andrew as copy decisions rather than settled by this build.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ THE RAIL MAKES A PROVENANCE CLAIM, SO IT RENDERS ONLY WHERE ONE IS TRUE
 * ─────────────────────────────────────────────────────────────────────────────
 * "The book this came from" is a statement of fact about where a post came
 * from. `bhp_blog_rail_adventure()` resolves it from CURATED EDITORIAL SIGNALS
 * ONLY — the guide registry, the destination hub, the post's categories, and
 * the place named in its own title.
 *
 * ⛔ A BODY-MENTION COUNT WAS BUILT, MEASURED, AND THEN DELETED. Counting the
 *    word "Mariana" in a post body resolved 26 of 36 posts instead of 9 — and
 *    every one of the extra 17 was a MENTION, not a provenance. A bridge-books
 *    listicle that names the Mariana book three times did not come from it.
 *    Inferring provenance from a frequency threshold is the derived-claim trap
 *    in `evidence-verification` step 5, and the number it produced is recorded
 *    here so a future pass does not "improve" coverage by reintroducing it.
 *
 * Posts with no resolvable book fall back to the SERIES rail, which claims
 * nothing about provenance and points at `/complete-collection/`. Its label is
 * a different string for exactly that reason.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ EVERY FIGURE IS READ FROM LIVE DATA. THERE IS NO PRICE LITERAL IN THIS FILE
 * ─────────────────────────────────────────────────────────────────────────────
 * The rail's price comes from `bhp_get_homepage_books()`'s `price` field, which
 * is `WC_Product::get_price_html()` on the real product record; the series
 * rail's price comes from `bhp_bundle_landing_price_facts()`, the same
 * derivation the Complete Collection page prints and step 1's header button
 * quotes. IF A PRICE CANNOT BE READ, THE RAIL DOES NOT RENDER. It never falls
 * back to a typed number — a stale price on a customer-facing control is a
 * price claim, and a wrong one is an FTC-class problem, not a display bug.
 *
 * The age band is `bhp_get_homepage_books()`'s `age_range`, which defaults to
 * "Ages 6-9" — standing rule §9, never 5-9. The cover is the real product
 * image. No image is generated, composited or altered (`FD-376`).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT THIS FILE DOES NOT DO
 * ─────────────────────────────────────────────────────────────────────────────
 * ⛔ It does not modify one character of any post's stored content. The rail is
 *    injected into the RENDERED output through `the_content`; `post_content` in
 *    the database is never read for mutation and never written. H2s, body copy
 *    and headings belong to `marketing-growth` and Andrew.
 * ⛔ It adds NO popup. Item 61(4) keeps the popup homepage-only plus blog,
 *    exactly as it is. An inline block in the document flow is not a popup and
 *    does not engage Google's intrusive-interstitial rule.
 * ⛔ It does not change the funnel's redirect behaviour. The end-of-post capture
 *    passes NO `success_redirect_key`, so it posts and returns to the hosting
 *    post with `?bhp_signup=success` and `signup-form.php` fires
 *    `lead_signup_success` inline, consent-gated by
 *    `BHP_Analytics_Config::should_render_analytics()`. `CYCLE165-BOR-101`
 *    proposes redirecting inline captures to the thank-you page instead. THAT
 *    IS ANDREW'S DECISION AND IS NOT TAKEN HERE.
 * ⛔ It changes no price, discount, shipping tier, coupon, tax, stock status,
 *    product record, variation, SKU or WooCommerce setting.
 * ⛔ It touches neither funnel's storage keys or analytics prefixes.
 *
 * VOICE: standing rule §9.1. No "we" in anything a customer reads. No em dash —
 * the separators below are middle dots, which are punctuation, not dashes.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The whole component behind one filter, default ON.
 *
 * Turning this off returns the blog post template to its 1.19.260 behaviour:
 * no rail, no capture, no plate, and `single.php`'s header falls back to the
 * ordering it had before this build.
 *
 * @return bool
 */
function bhp_blog_template_enabled() {
	return (bool) apply_filters( 'bhp_blog_template_enabled', true );
}

/**
 * True only on a single post that this template governs.
 *
 * @return bool
 */
function bhp_blog_template_active() {
	return bhp_blog_template_enabled() && is_singular( 'post' );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 1 · THE HEADER — reclaiming the dead band
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The eyebrow.
 *
 * "Explorer's Journal" is the string `single.php` already shipped at 1.19.260;
 * "field note" is the board's proposed second limb. Both are filterable so
 * Andrew's copy ruling lands as a one-line change rather than a rebuild.
 *
 * @return string
 */
function bhp_blog_eyebrow_text() {
	return (string) apply_filters(
		'bhp_blog_eyebrow_text',
		sprintf(
			/* translators: 1: the journal name, 2: the entry kind. */
			_x( '%1$s · %2$s', 'blog post eyebrow', 'brave-hearts' ),
			__( 'Explorer’s Journal', 'brave-hearts' ),
			__( 'field note', 'brave-hearts' )
		)
	);
}

/**
 * The deck line — the post's OWN manual excerpt, or nothing.
 *
 * ⛔ `get_the_excerpt()` IS NOT USED, AND THAT IS THE POINT. It falls back to an
 *    auto-generated trim of the first 55 words of the body, which on this
 *    template would print the opening sentence of the article immediately above
 *    the opening sentence of the article. `post_excerpt` is the hand-written
 *    field, and all 36 published posts carry one (verified on staging2
 *    2026-08-19 by direct query). Where a future post does not, the deck is
 *    simply absent — no sentence is generated for it.
 *
 * Editors write excerpts in the block editor, so the stored value can carry
 * wrapper markup; tags are stripped and entities decoded before printing.
 *
 * @param WP_Post|int|null $post Post.
 * @return string Plain text, or ''.
 */
function bhp_blog_deck_text( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || '' === trim( (string) $post->post_excerpt ) ) {
		return '';
	}
	$deck = wp_strip_all_tags( $post->post_excerpt, true );
	$deck = html_entity_decode( $deck, ENT_QUOTES, 'UTF-8' );
	$deck = trim( preg_replace( '/\s+/u', ' ', $deck ) );

	return (string) apply_filters( 'bhp_blog_deck_text', $deck, $post );
}

/**
 * Words per minute used for the reading estimate.
 *
 * ⚠ THIS IS AN ASSUMED CONSTANT, LABELLED AS ONE. 200 wpm is the conventional
 *   figure for adult silent reading of general prose. No BHP measurement of
 *   reader speed exists, and none is implied. The WORD COUNT is a real count of
 *   the post's own body; only the divisor is assumed, which is why the label
 *   reads "min read" rather than a promise.
 *
 * @return int
 */
function bhp_blog_reading_wpm() {
	return max( 1, (int) apply_filters( 'bhp_blog_reading_wpm', 200 ) );
}

/**
 * Whole-minute reading estimate for a post, or 0 when it cannot be computed.
 *
 * @param WP_Post|int|null $post Post.
 * @return int
 */
function bhp_blog_reading_minutes( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 0;
	}
	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words = preg_match_all( '/[\p{L}\p{N}\'’-]+/u', $text );
	if ( ! $words ) {
		return 0;
	}
	return max( 1, (int) ceil( $words / bhp_blog_reading_wpm() ) );
}

/**
 * The meta line's parts, in order, already escaped-safe as plain strings.
 *
 * ⛔ THE CATEGORY IS DELIBERATELY ABSENT. At 1.19.260 this line rendered
 *    "APRIL 15, 2026 · BY ANDREW SIGNORE · UNCATEGORIZED" at 390 — every one of
 *    the 36 published posts sits in the default category (verified on staging2
 *    2026-08-19), so the third limb was printing the word "Uncategorized" to
 *    every reader of the site's highest-traffic section. The topic already
 *    reaches the reader through the breadcrumb hub one line above.
 *
 * @param WP_Post|int|null $post Post.
 * @return array
 */
function bhp_blog_meta_parts( $post = null ) {
	$post  = get_post( $post );
	$parts = array();
	if ( ! $post ) {
		return $parts;
	}
	$parts['date'] = get_the_date( '', $post );

	$author = get_the_author_meta( 'display_name', (int) $post->post_author );
	if ( $author ) {
		/* translators: %s: author name. */
		$parts['author'] = sprintf( __( 'By %s', 'brave-hearts' ), $author );
	}

	$minutes = bhp_blog_reading_minutes( $post );
	if ( $minutes ) {
		$parts['reading'] = sprintf(
			/* translators: %d: estimated reading time in whole minutes. */
			_n( '%d min read', '%d min read', $minutes, 'brave-hearts' ),
			$minutes
		);
	}

	return (array) apply_filters( 'bhp_blog_meta_parts', $parts, $post );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 2 · THE PLATE — one faint line-art watermark, no new hue
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The navy the plate is drawn in, as a hex string.
 *
 * ⚠ THIS IS A COPY OF THE `--expedition-navy` CUSTOM PROPERTY, AND IT IS A COPY
 *   ON PURPOSE. The plate ships as an external SVG file so it is fetched once
 *   and cached, rather than inlining 23 KB of path data into every blog post;
 *   an external file referenced from `background-image` cannot read a CSS custom
 *   property, so the value has to exist in the file itself.
 *
 *   That duplication is a drift risk, so the suite asserts the two are equal
 *   (`test-blog-post-template.php` §4). If Andrew re-tones the navy, the test
 *   fails loudly instead of leaving a stale hue on 36 pages.
 *
 * @return string
 */
function bhp_blog_plate_ink() {
	return '#071522';
}

/**
 * The plate mark. One per screen, and exactly one per document.
 *
 * ⛔ ONE ELEMENT, NOT A REPEATING TILE. The brief's constraint is "one mark per
 *    screen"; a `background-repeat` would put several in a viewport on a tall
 *    phone. It is `aria-hidden` and carries no alt text because it carries no
 *    information — it is texture, and a screen reader announcing "deep sea line
 *    art" before every article would be noise, not access.
 *
 * @return string HTML.
 */
function bhp_blog_plate_html() {
	if ( ! bhp_blog_template_active() ) {
		return '';
	}
	return '<div class="bhp-post-plate" aria-hidden="true"></div>';
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 3 · THE BOOK RAIL — the bridge from a field note to the book behind it
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Normalise the several spellings of an adventure key that exist in this
 * codebase into the one `bhp_get_series_adventures()` uses.
 *
 * ⚠ THE GUIDE REGISTRY IS INTERNALLY INCONSISTENT AND THIS FUNCTION IS THE
 *   EVIDENCE. `bhp_get_guide_registry()` stores `mariana-trench` and
 *   `mount-everest` with hyphens but `amazon_rainforest` with an underscore,
 *   while `bhp_get_series_adventures()` keys all three with underscores. The
 *   pre-existing consequence is visible in
 *   `template-parts/guides/related-content.php`, whose `$book_urls` map has
 *   hyphenated keys only — so an Amazon-mapped post gets no book link there at
 *   all. Recorded as `CYCLE165-LD-02` and routed to `chief-of-staff`; NOT fixed here,
 *   because related-content is another template's behaviour and changing it
 *   would move a customer-facing link outside this brief's scope.
 *
 * @param string $key Raw key from any source.
 * @return string Normalised key, or '' when it names no adventure.
 */
function bhp_blog_normalise_adventure_key( $key ) {
	$key = strtolower( str_replace( '-', '_', trim( (string) $key ) ) );
	if ( ! function_exists( 'bhp_get_series_adventures' ) ) {
		return '';
	}
	return array_key_exists( $key, bhp_get_series_adventures() ) ? $key : '';
}

/**
 * Resolve the adventure a post genuinely came from, or '' when none does.
 *
 * PRECEDENCE, every limb a CURATED EDITORIAL SIGNAL:
 *   1. the guide registry's own `book` field — hand-assigned per slug
 *   2. the registry's `destination` hub — hand-assigned per slug
 *   3. the post's assigned categories — WordPress taxonomy, editor-set
 *   4. the place named in the post's OWN TITLE — self-evident from the headline
 *
 * `series-wide` is deliberately NOT a resolution. It is the registry's way of
 * saying "this is about the series, not one book", which is the series rail's
 * case, not this one's.
 *
 * ⛔ THERE IS NO BODY-TEXT LIMB. See the file docblock for the measurement that
 *    removed it.
 *
 * @param WP_Post|int|null $post Post.
 * @return string Adventure key, or ''.
 */
function bhp_blog_rail_adventure( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$data = function_exists( 'bhp_get_guide_post_data' ) ? bhp_get_guide_post_data( $post ) : array();

	$key = bhp_blog_normalise_adventure_key( $data['book'] ?? '' );
	if ( ! $key ) {
		$key = bhp_blog_normalise_adventure_key( $data['destination'] ?? '' );
	}
	if ( ! $key ) {
		foreach ( (array) get_the_category( $post->ID ) as $term ) {
			$key = bhp_blog_normalise_adventure_key( $term->slug );
			if ( $key ) {
				break;
			}
		}
	}
	if ( ! $key && function_exists( 'bhp_get_series_adventures' ) ) {
		$title = strtolower( wp_strip_all_tags( get_the_title( $post ) ) );
		foreach ( bhp_get_series_adventures() as $adventure_key => $adventure ) {
			foreach ( (array) ( $adventure['matches'] ?? array() ) as $needle ) {
				// "amazon" alone is excluded: on this site it names the retailer
				// at least as often as the rainforest (standing rule §10).
				if ( 'amazon' === $needle || strlen( $needle ) < 6 ) {
					continue;
				}
				if ( false !== strpos( $title, $needle ) ) {
					$key = $adventure_key;
					break 2;
				}
			}
		}
	}

	return (string) apply_filters( 'bhp_blog_rail_adventure', $key, $post );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 3a · THE RAIL CONTRACT — 1.19.273, founder-ruled (carrier item 126)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⚠ SOURCE: Andrew Signore, RELAYED through `chief-of-staff`. This agent did
 *   NOT witness it first-hand, and it is not described here as first-hand. The
 *   verbatim words live in the carrier record, not in this public repository
 *   (standing rule §4.1: point at the private source, never copy it in).
 *   ⛔ The technical agent ID is used deliberately and the internal call name is
 *      not: standing rule §14 constraint 5 keeps aliases off every public
 *      surface, and this repository is public on GitHub.
 *
 * THE RULE, IN ONE SENTENCE: A RAIL IS IN EXACTLY ONE MODE, AND ITS IMAGE AND
 * ITS PRICE COME FROM THE SAME MODE. NEVER MIXED.
 *
 *   ┌─────────────────┬──────────────────────────┬────────────────────────────┐
 *   │ mode            │ series / collection      │ single book                │
 *   ├─────────────────┼──────────────────────────┼────────────────────────────┤
 *   │ image           │ the COLLECTION composite │ THAT BOOK's cover          │
 *   │ `image_kind`    │ `collection`             │ `cover`                    │
 *   │ price           │ live COLLECTION price    │ that book's live SINGLE    │
 *   │ `price_source`  │ `collection`             │ `single`                   │
 *   │ eyebrow         │ the series wording       │ "The book this came from"  │
 *   │ CTA             │ "See the books"          │ "See the book"/"Look inside"│
 *   │ link            │ /complete-collection/    │ that book's product page   │
 *   └─────────────────┴──────────────────────────┴────────────────────────────┘
 *
 * ⛔ WHY IT IS A RULE AND NOT A PREFERENCE. Until 1.19.273 the series branch
 *    showed the first available title's cover — The Mariana Trench — beside the
 *    COLLECTION price, on all 29 series rails. Andrew found it on staging. A
 *    cover and a price sitting in one card are read as one object: that pairing
 *    states, to a parent, that The Mariana Trench costs the bundle price. It is
 *    a false price claim assembled out of two true facts — the derived-claim
 *    trap, and a standing rule §3 failure rather than a design nitpick.
 *
 * ⛔ NO FIGURE IN EITHER COLUMN IS A LITERAL, AND NONE IS WRITTEN IN THIS
 *    COMMENT EITHER. Single prices come from `bhp_get_homepage_books()`, the
 *    collection price from `bhp_bundle_landing_price_facts()`. A price change
 *    in WooCommerce moves the rail with no code edit — which is the property
 *    that makes the contract survive.
 *
 *    ⚠ THE OBSERVED FIGURES ARE DELIBERATELY NOT QUOTED HERE. `test-blog-post-
 *      template.php` §2.3 greps this file for any `$n.nn` literal with a blunt
 *      `strpos`, and that bluntness is worth more than the convenience of
 *      naming the numbers in a comment: a gate that cannot be argued with
 *      cannot be argued down. The values as observed on staging 2026-08-19 are
 *      in the QA evidence, which is where a dated figure belongs (§8).
 *
 * ⭐ THE CONTRACT IS MACHINE-CHECKED, not merely documented. Both branches
 *    declare `image_kind` and `price_source`; `book-rail.php` prints them onto
 *    the element; and `tests/test-protected-elements.php` asserts the
 *    implication in both directions, as does the deploy script's gate. A future
 *    edit that reintroduces a lead-title cover on the series rail fails the
 *    suite and refuses the deploy.
 */

/**
 * The live product facts behind one adventure, or null.
 *
 * Both halves come from functions that already read the real product records:
 * `bhp_get_series_adventures()` for the canonical URL, title and age band, and
 * `bhp_get_homepage_books()` for the product id, cover and price. They are
 * joined on the permalink rather than by re-implementing the title matching, so
 * there is one matching vocabulary in this codebase and not two.
 *
 * @param string $key Adventure key.
 * @return array|null
 */
function bhp_blog_rail_book_facts( $key ) {
	if ( ! $key || ! function_exists( 'bhp_get_series_adventures' ) || ! function_exists( 'bhp_get_homepage_books' ) ) {
		return null;
	}
	$adventures = bhp_get_series_adventures();
	$adventure  = $adventures[ $key ] ?? null;
	if ( ! $adventure || empty( $adventure['available'] ) || empty( $adventure['primary_url'] ) ) {
		return null;
	}

	$card = null;
	foreach ( bhp_get_homepage_books( -1 ) as $candidate ) {
		if ( ( $candidate['url'] ?? '' ) === $adventure['primary_url'] ) {
			$card = $candidate;
			break;
		}
	}
	if ( ! $card || '' === trim( (string) ( $card['price'] ?? '' ) ) ) {
		return null; // No live price -> no rail. See the file docblock.
	}

	return array(
		'kind'       => 'book',
		'key'        => $key,
		'title'      => $adventure['title'],
		'url'        => $adventure['primary_url'],
		'price'      => trim( (string) $card['price'] ),
		'age_range'  => $card['age_range'] ?: __( 'Ages 6–9', 'brave-hearts' ),
		'image_id'   => (int) ( $card['image_id'] ?: $adventure['image_id'] ),
		'image_alt'  => (string) ( $card['image_alt'] ?: $adventure['image_alt'] ),
		'product_id' => (int) ( $card['product_id'] ?? 0 ),
		// THE RAIL CONTRACT (see §3a below). Single-book mode: this book's own
		// cover, this book's own live single price. Both declared, so the
		// pairing is assertable in the DOM and cannot silently drift.
		'image_kind'   => 'cover',
		'price_source' => 'single',
	);
}

/**
 * The series fallback — for a post that no single book stands behind.
 *
 * ⛔ ITS LABEL IS A DIFFERENT STRING FROM THE BOOK RAIL'S, AND THAT IS THE WHOLE
 *    REASON IT IS A SEPARATE BRANCH. Printing "The book this came from" over a
 *    cover on a post that did not come from that book would be a fabricated
 *    provenance claim, which is a standing rule §3 failure and not a wording
 *    preference.
 *
 * The price is the collection price from `bhp_bundle_landing_price_facts()` —
 * the same derivation step 1's header button quotes and the Complete Collection
 * page prints.
 *
 * @return array|null
 */
function bhp_blog_rail_series_facts() {
	if ( ! function_exists( 'bhp_bundle_landing_price_facts' ) || ! function_exists( 'bhp_get_series_adventures' ) ) {
		return null;
	}
	$format = function_exists( 'bhp_bundle_default_format' ) ? bhp_bundle_default_format() : 'paperback';
	$facts  = bhp_bundle_landing_price_facts( $format );
	if ( ! is_array( $facts ) || empty( $facts['bundle'] ) || (float) $facts['bundle'] <= 0 ) {
		return null;
	}

	/*
	 * ⛔ THE COLLECTION IMAGE, NOT A LEAD TITLE'S COVER. Founder ruling,
	 *    carrier item 126 (⚠ RELAYED through `chief-of-staff`, not witnessed
	 *    by this agent). See §3a below for the contract in full.
	 *
	 * SUPERSEDED, preserved so the movement is visible and is not re-derived.
	 * This branch previously walked `bhp_get_series_adventures()` and took the
	 * FIRST available title's `image_id`, under the reasoning:
	 *
	 *     "The cover shown is the series' lead title, which is a real Charlotte
	 *      and Henry cover under a label that names the series, not that one
	 *      book."
	 *
	 * That reasoning was about PROVENANCE — it is careful that the label never
	 * claims the post came from that one book, and on that narrow point it was
	 * right. But it left a different defect, which is the one Andrew found on
	 * staging himself: the first available title is The Mariana Trench, so all
	 * 29 series rails printed THE MARIANA COVER beside THE COLLECTION PRICE.
	 * A parent reads one cover and one price as one object, so the bundle price
	 * under a single cover reads as that book costing the bundle price.
	 * (The figures are not quoted here — see §3a on why §2.3 stays blunt.)
	 *
	 * ⭐ THE ASSET IS FOUND, NEVER GENERATED. `collection-look-01-three-books-v2`
	 *    is the approved three-cover composite already registered in
	 *    `inc/book-media.php` under `complete_collection`, and already rendered
	 *    by every collection carousel on the site through
	 *    `bhp_collection_carousel_slugs()`. Standing rule §9: covers are
	 *    composited from approved artwork, never regenerated. This resolves the
	 *    SAME slug those surfaces resolve, so the rail cannot drift from them.
	 *
	 * ⚠ WHEN THE COMPOSITE DOES NOT RESOLVE on an environment, `image_id` is 0
	 *   and `book-rail.php` renders the rail WITH NO IMAGE. That is deliberate:
	 *   a text-only series rail still bridges to the books and makes no false
	 *   claim, whereas falling back to a single cover would reinstate exactly
	 *   the mixed rail this change exists to remove. Degrade, never mix.
	 */
	$collection_image_id = function_exists( 'bhp_book_media_attachment_id' )
		? (int) bhp_book_media_attachment_id( bhp_blog_rail_collection_image_slug() )
		: 0;

	$price = (float) $facts['bundle'];

	return array(
		'kind'       => 'series',
		'key'        => 'series',
		'title'      => __( 'Adventures of Charlotte and Henry', 'brave-hearts' ),
		'url'        => home_url( '/complete-collection/' ),
		'price'      => function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $price, array( 'decimals' => 2 ) ) ) : '$' . number_format( $price, 2 ),
		'age_range'  => __( 'Ages 6–9', 'brave-hearts' ),
		'image_id'   => $collection_image_id,
		'image_alt'  => '',
		'product_id' => 0,
		// THE RAIL CONTRACT. Collection mode: the collection composite, the
		// live collection price. Never one book's cover beside a bundle price.
		'image_kind'   => 'collection',
		'price_source' => 'collection',
	);
}

/**
 * The attachment slug of the approved three-cover collection composite.
 *
 * One place, so the rail, the carousels and the deploy-script gate name the
 * same asset. Filterable so a rebuilt composite is a one-line change — the
 * same rollback shape `inc/book-media.php` already documents for v1 -> v2.
 *
 * @return string
 */
function bhp_blog_rail_collection_image_slug() {
	return (string) apply_filters(
		'bhp_blog_rail_collection_image_slug',
		'collection-look-01-three-books-v2'
	);
}

/**
 * The facts the rail will print on this request, or null when it must not render.
 *
 * @param WP_Post|int|null $post Post.
 * @return array|null
 */
function bhp_blog_rail_facts( $post = null ) {
	$facts = bhp_blog_rail_book_facts( bhp_blog_rail_adventure( $post ) );
	if ( ! $facts ) {
		$facts = bhp_blog_rail_series_facts();
	}
	return apply_filters( 'bhp_blog_rail_facts', $facts, get_post( $post ) );
}

/**
 * The rail's eyebrow — a factual label, chosen by which branch resolved.
 *
 * @param array $facts Rail facts.
 * @return string
 */
function bhp_blog_rail_eyebrow( $facts ) {
	$text = ( 'book' === ( $facts['kind'] ?? '' ) )
		? __( 'The book this came from', 'brave-hearts' )
		: __( 'The books behind this journal', 'brave-hearts' );

	return (string) apply_filters( 'bhp_blog_rail_eyebrow', $text, $facts );
}

/**
 * ⭐⭐ THE CTA RULE — stated here because the brief asked for the rule, not just
 * the label, and because "Read free" was the option this build REFUSED.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * THE RULE, in one sentence: THE RAIL'S CTA NAMES WHAT THE DESTINATION ACTUALLY
 * DELIVERS, AND THE WORD "FREE" IS RESERVED FOR THE ONE PLACEMENT ON THIS PAGE
 * THAT CAN HONOUR IT.
 *
 * Applied:
 *
 *   · "Look inside"  — when the mapped book has real look-inside media
 *     (`bhp_book_has_look_inside()`: the approved photo set and the flip-through
 *     video). Links to that anchor on the product page. This is a genuinely
 *     free, ungated look at the physical book, and it already exists.
 *   · "See the book" — when it does not. Links to the product page.
 *   · "See the books" — the series rail, to `/complete-collection/`.
 *
 * ⛔ WHY NOT "READ FREE", WHICH IS WHAT THE BOARD'S MOCK DRAWS.
 *    `ads-knowledge` §2.1 and §5.2 are right that content traffic should be
 *    offered a lead-first path, and item 96(7) makes a free-sample CTA count as
 *    primary. But NO UNGATED READING SAMPLE EXISTS ON THIS SITE
 *    (`CYCLE164-CX-15`). The only complete sample chapter lives INSIDE the
 *    Reluctant Reader Adventure Kit, which costs an email address. A control
 *    reading "Read free", sitting beside a cover, an age band and a price,
 *    promises free reading of THAT BOOK and the destination cannot honour it.
 *    That is an unsubstantiated claim in customer-facing copy — standing rule
 *    §3, and the class `ads-knowledge` §3.3 shows is also an FTC floor, not only
 *    a house rule.
 *
 *    ⭐ THE LEAD-FIRST PATH IS NOT ABANDONED, IT IS MOVED TO WHERE IT IS TRUE.
 *       The end-of-post capture below offers the Kit, which really does contain
 *       a complete sample chapter, and says so in Andrew's own voice. The page
 *       therefore carries exactly one honest free offer and one honest buy
 *       bridge, instead of two controls competing to be the free one.
 *
 * ⚠ The mode is filterable. If Andrew rules for the board's wording, or commissions
 *   an ungated first chapter, `bhp_blog_rail_cta_mode` flips it in one line.
 *
 * @param array $facts Rail facts.
 * @return array {label, url}
 */
function bhp_blog_rail_cta( $facts ) {
	$mode = 'product';
	if ( 'book' === ( $facts['kind'] ?? '' )
		&& function_exists( 'bhp_book_has_look_inside' )
		&& bhp_book_has_look_inside( $facts['key'] ) ) {
		$mode = 'look_inside';
	}
	$mode = (string) apply_filters( 'bhp_blog_rail_cta_mode', $mode, $facts );

	$url = $facts['url'];
	if ( 'look_inside' === $mode ) {
		$label = __( 'Look inside', 'brave-hearts' );
		$url   = $facts['url'] . '#bhp-look-inside-' . sanitize_html_class( $facts['key'] );
	} elseif ( 'series' === ( $facts['kind'] ?? '' ) ) {
		$label = __( 'See the books', 'brave-hearts' );
	} else {
		$label = __( 'See the book', 'brave-hearts' );
	}

	return (array) apply_filters(
		'bhp_blog_rail_cta',
		array( 'label' => $label, 'url' => $url, 'mode' => $mode ),
		$facts
	);
}

/**
 * Render the rail.
 *
 * @param WP_Post|int|null $post Post.
 * @return string HTML, or ''.
 */
function bhp_blog_rail_html( $post = null ) {
	if ( ! bhp_blog_template_active() ) {
		return '';
	}
	$facts = bhp_blog_rail_facts( $post );
	if ( ! $facts ) {
		return '';
	}
	ob_start();
	get_template_part(
		'template-parts/guides/book-rail',
		null,
		array(
			'facts'   => $facts,
			'eyebrow' => bhp_blog_rail_eyebrow( $facts ),
			'cta'     => bhp_blog_rail_cta( $facts ),
		)
	);
	return (string) ob_get_clean();
}

/* ═══════════════════════════════════════════════════════════════════════════
 * 4 · INJECTION — after the first useful answer, and never above the fold
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * ⭐⭐ 1.19.272 (`CYCLE165-LD-ITERATE-8-FINAL`) — THE RAIL RENDERS. RESTORED,
 *     ON THE FOUNDER'S OWN CORRECTION, AFTER 1.19.269 TURNED IT OFF.
 *
 * ⛔ THIS REVERSES 1.19.269, AND THE REVERSAL IS THE POINT. The previous
 *    version of this docblock is preserved in git history at 3762909 rather
 *    than restated here; the summary below is what a future reader needs.
 *
 * WHAT HAPPENED, in order, each limb traceable:
 *
 *   1. Founder ruling, 2026-08-19 (~16:2x−0600) — carrier item **110** — kept
 *      the "book this came from" rail on blog posts explicitly. He likes it.
 *   2. Founder ruling, same evening — carrier item **119**'s source line, the
 *      subtraction list at
 *      `Business OS\ANDREW-REVIEW\2026-08-19\REPORT-2026-08-19-LEARN-REVIEW-ITERATE.md`
 *      §4 item 1 — asked for two of the FOUR asks on a blog post to go.
 *   3. 1.19.269 read (2) as covering this rail and switched it off. That was
 *      an over-read: **the rail is a BOOK BRIDGE, not an email capture**, and
 *      (1) had already ruled on it. The 1.19.269 docblock said so itself and
 *      still shipped the switch — the finding was recorded and then not acted
 *      on, which is the actual defect.
 *   4. Founder-found regression, 2026-08-19 (~21:4x−0600) — carrier item
 *      **118**. He looked at production and asked where the embedded books had
 *      gone. Registered as `CYCLE165-COS-009`.
 *   5. This release restores it. Item 118 nominated 1.19.271 for the fix;
 *      1.19.271 shipped the kit-CTA popup only, so the restore lands here in
 *      1.19.272. Stated rather than smoothed over.
 *
 * ⛔ SOURCE RECORDS ARE REFERENCED BY LOCATION, NOT RESTATED. The founder
 *    verbatim for items 110, 118 and 119 lives in the Business OS chief-of-staff
 *    founder-verbatim record; **this repository is public** (standing rules §4,
 *    §4.1) and the `CYCLE165-LD-10` scrub class exists because a private figure
 *    once reached a public-bound comment. Item numbers and dates may travel
 *    here. The words may not.
 *
 * ⭐ WHAT DID *NOT* COME BACK WITH IT. Subtraction item 1's other limb — the
 *    sitewide FOOTER CAPTURE being suppressed on posts — is untouched and stays
 *    suppressed (`bhp_should_show_footer_capture()`). A blog post therefore
 *    carries exactly **two asks** (end-of-post capture + popup) **and one book
 *    bridge**, which is what both rulings say together.
 *
 * ⚠ STILL A FILTER, DELIBERATELY. `add_filter( 'bhp_blog_rail_enabled',
 *   '__return_false' )` turns it off again in one line, with no code deleted,
 *   exactly as the reverse direction did. A switch that only travels one way is
 *   not a switch.
 *
 * @return bool
 */
function bhp_blog_rail_enabled() {
	return (bool) apply_filters( 'bhp_blog_rail_enabled', true );
}

/**
 * How many closing paragraphs must precede the rail at minimum.
 *
 * @return int
 */
function bhp_blog_rail_min_paragraphs() {
	return max( 1, (int) apply_filters( 'bhp_blog_rail_min_paragraphs', 3 ) );
}

/**
 * Choose the byte offset in rendered post HTML at which the rail is inserted.
 *
 * ⭐ "AFTER THE FIRST USEFUL ANSWER" IS IMPLEMENTED AS THE LATER OF TWO
 *    POSITIONS, and both limbs are load-bearing:
 *
 *      · immediately BEFORE THE SECOND `<h2>` — i.e. after the first H2 section
 *        has actually been answered. On a post that opens with an intro and then
 *        answers the headline question, this is exactly the reader's first
 *        payoff moment.
 *      · after the Nth closing `</p>` (N = 3) — the floor. A post whose second
 *        H2 arrives after two sentences would otherwise put a commerce control
 *        within a screen of the H1, which at 390 risks the fold.
 *
 *    Taking the LATER of the two means the floor can only ever push the rail
 *    DOWN the page, never up. That is the direction that keeps rubric row 1
 *    safe, and it is why `max()` is correct here and `min()` would be a bug.
 *
 * Returns null when neither anchor is present, in which case the caller appends
 * rather than injecting — a post with no H2 and under three paragraphs is short
 * enough that the end IS after the first useful answer.
 *
 * @param string $html Rendered content.
 * @return int|null
 */
function bhp_blog_rail_offset( $html ) {
	$h2_offset = null;
	if ( preg_match_all( '/<h2[\s>]/i', $html, $m, PREG_OFFSET_CAPTURE ) && count( $m[0] ) >= 2 ) {
		$h2_offset = (int) $m[0][1][1];
	}

	$p_offset = null;
	if ( preg_match_all( '/<\/p>/i', $html, $mp, PREG_OFFSET_CAPTURE ) ) {
		$need = bhp_blog_rail_min_paragraphs();
		if ( count( $mp[0] ) >= $need ) {
			$p_offset = (int) $mp[0][ $need - 1 ][1] + strlen( $mp[0][ $need - 1 ][0] );
		}
	}

	if ( null === $h2_offset && null === $p_offset ) {
		return null;
	}
	if ( null === $h2_offset ) {
		return $p_offset;
	}
	if ( null === $p_offset ) {
		return $h2_offset;
	}
	return max( $h2_offset, $p_offset );
}

/**
 * Inject the rail into the rendered post body.
 *
 * ⛔ THE FOUR GUARDS BELOW EXIST SO THIS FILTER CAN NEVER FIRE TWICE OR IN THE
 *    WRONG PLACE. `the_content` is called by feeds, by REST, by SEO plugins
 *    building meta descriptions, and by any block that renders a post excerpt —
 *    every one of those would otherwise get a commerce rail inside it.
 *
 * @param string $content Rendered content.
 * @return string
 */
function bhp_blog_inject_rail( $content ) {
	static $done = false;

	if ( ! bhp_blog_rail_enabled() ) {
		return $content;                                  // filterable off-switch; default ON since 1.19.272 (item 118)
	}
	if ( $done ) {
		return $content;                                  // once per request
	}
	if ( ! bhp_blog_template_active() || ! in_the_loop() || ! is_main_query() ) {
		return $content;                                  // the real article only
	}
	if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'bhp-book-rail' ) ) {
		return $content;                                  // an editor placed one
	}

	$rail = bhp_blog_rail_html();
	if ( '' === $rail ) {
		return $content;
	}

	$done   = true;
	$offset = bhp_blog_rail_offset( $content );

	return ( null === $offset )
		? $content . $rail
		: substr( $content, 0, $offset ) . $rail . substr( $content, $offset );
}
add_filter( 'the_content', 'bhp_blog_inject_rail', 12 );

/* ═══════════════════════════════════════════════════════════════════════════
 * 5 · THE END-OF-POST CAPTURE
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The form context this placement reports as.
 *
 * It is distinct from every existing context so the placement is separable in
 * both the dataLayer (`placement`) and the Mailchimp tag map below.
 *
 * @return string
 */
function bhp_blog_capture_context() {
	return 'blog_post_end';
}

/**
 * Render the end-of-post capture.
 *
 * @return string HTML, or ''.
 */
function bhp_blog_capture_html() {
	if ( ! bhp_blog_template_active() ) {
		return '';
	}
	if ( ! (bool) apply_filters( 'bhp_blog_capture_enabled', true ) ) {
		return '';
	}
	ob_start();
	get_template_part( 'template-parts/acquisition/post-end-capture' );
	return (string) ob_get_clean();
}

/**
 * Source attribution for blog-post captures.
 *
 * ⭐ WHY THIS FILTER EXISTS. `functions.php`'s existing map sends every
 *    `reluctant_reader_adventure_kit` signup that is not the parent popup to
 *    "Source: Parent Landing Page". A capture at the end of a blog post is
 *    neither, and without this the blog's leads would be indistinguishable from
 *    the Kit landing page's — which is precisely the source-attribution gap
 *    `ads-knowledge` §4.4 flags (`CYCLE148-FIN-002`, O-71) and which blinds the
 *    only cross-check available at this traffic volume.
 *
 * ⛔ ADDED AS A NEW `add_filter`, NOT AN EDIT TO THE PROVEN ONE. It runs at a
 *    later priority and narrows on this context alone, so the parent popup, the
 *    Kit landing page, the teacher funnel and every other placement keep the
 *    exact tags they had at 1.19.260.
 *
 * ⚠ IT CHANGES NO MAILCHIMP ACCOUNT SETTING. It changes the tag string this
 *   theme sends with a FUTURE signup. Nothing is written to any external system
 *   by this file.
 *
 * @param array  $tags          Tags.
 * @param string $context       Form context.
 * @param string $audience_type Audience.
 * @param string $lead_magnet   Lead magnet key.
 * @param string $source_page   Source URL.
 * @return array
 */
add_filter(
	'bhp_mailchimp_signup_tags',
	function ( $tags, $context, $audience_type, $lead_magnet, $source_page ) {
		unset( $audience_type, $source_page );
		if ( bhp_blog_capture_context() !== $context ) {
			return $tags;
		}
		if ( 'reluctant_reader_adventure_kit' !== $lead_magnet ) {
			return $tags;
		}
		return array( 'Reluctant Reader Adventure Kit', 'Audience: Parent/Grandparent', 'Source: Blog Post' );
	},
	20,
	5
);

/* ═══════════════════════════════════════════════════════════════════════════
 * 6 · ASSETS
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The template's stylesheet, on single posts only.
 *
 * Every other page gets no extra bytes.
 */
function bhp_blog_template_enqueue() {
	if ( ! bhp_blog_template_active() ) {
		return;
	}
	wp_enqueue_style(
		'bhp-blog-post',
		get_template_directory_uri() . '/assets/css/blog-post.css',
		array( 'bhp-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_blog_template_enqueue' );
