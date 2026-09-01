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

/**
 * ⭐⭐ 1.19.323 (`CYCLE169-LD-R3-IMGCAP-ATTRIBUTION`) — THE FEATURED-IMAGE
 *    HIDE TOGGLE. PREPARED AND SHIPPED **OFF**.
 *
 * ⛔⛔ THE DEFAULT IS `true`, WHICH IS BYTE-EQUIVALENT TO 1.19.322's BEHAVIOUR.
 *     Nothing subscribes to this filter on any environment. `single.php` asks
 *     this question where it previously asked only `has_post_thumbnail()`, and
 *     with no subscriber the two ask the same thing. ⭐ THIS RELEASE DOES NOT
 *     CHANGE WHAT ANY VISITOR SEES.
 *
 * ⭐ WHY IT EXISTS: open finding **R1** from 1.19.322 is Andrew's, not this
 *    desk's — this blog's featured images are designed PORTRAIT POSTERS WITH
 *    TEXT BAKED IN (23 of 36 published posts carry a portrait thumbnail;
 *    counted on staging 2026-08-29, not estimated), and the briefed
 *    `object-fit: cover` crop cuts their headlines. One of the outcomes he may
 *    choose is simply not to show them on the single post at all. This makes
 *    that a ONE-LINE FLIP in either direction instead of a release.
 *
 *      add_filter( 'bhp_blog_featured_image_on_single', '__return_false' );
 *
 * ⛔ SCOPE IS THE SINGLE-POST MASTHEAD, AND ONLY THAT. It does not touch the
 *    post thumbnail itself, `index.php`'s blog cards, the related-article grid
 *    (`template-parts/guides/article-card.php`), Open Graph / Twitter images,
 *    Rank Math's schema, or any feed. Those read the thumbnail through their
 *    own calls and never pass through here — verified by grep, and asserted in
 *    `tests/test-cycle169-blog-layout.php` §6.
 *
 * ⛔ IT IS DELIBERATELY **NOT** GATED ON `bhp_blog_template_active()`. That
 *    helper means "the round-1 blog component is on"; this is a separate
 *    question about one image, and coupling them would mean turning the
 *    component off silently changed featured-image behaviour too.
 *
 * @param int|WP_Post|null $post Optional post context handed to subscribers.
 * @return bool True to render the masthead featured image (the default).
 */
function bhp_blog_featured_image_on_single( $post = null ) {
	return (bool) apply_filters( 'bhp_blog_featured_image_on_single', true, $post );
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.341 (`CYCLE171-LD-341` item 2) — THE MASTHEAD IMAGE IS BACK, SMALL.
 *     THE `__return_false` LINE THAT STOOD HERE IS REMOVED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ FOUNDER REVERSAL, 2026-08-31, of carrier item 474. Relayed in the build
 *    brief, verbatim: *"all the blogs should have a small heading picture - it
 *    looks so bland now. Use the picture it has on the blog page but just make
 *    it a tailored to the page."* Recorded with its provenance (§9.2 rule 2).
 *
 * ⛔ THE SUPERSEDED 1.19.324 COMMENT IS PRESERVED VERBATIM BELOW rather than
 *    deleted, so item 474 does not vanish from the file's history and a future
 *    reader can see that the hide was a RULING, correctly applied, and then
 *    reversed — not a bug that was fixed:
 *
 *      "⭐ FOUNDER RULING 2026-08-29 — carrier item 474, verbatim 'Hide them':
 *       featured images are HIDDEN on single blog posts sitewide. Articles open
 *       with the writing. Cards, related-article grid, Open Graph / Twitter,
 *       Rank Math schema, feeds and Pinterest duty are unaffected (see the scope
 *       note above — they never pass through this gate; asserted in
 *       tests/test-cycle169-blog-layout.php §6). Applied by chief-of-staff under
 *       G-40, staging 1.19.324. Remove this ONE line to restore the masthead."
 *
 * ⭐ THE HELPER AND ITS FILTER SURVIVE UNTOUCHED, and that is why this reversal
 *    is a one-line DELETION rather than a release. `bhp_blog_featured_image_on_
 *    single()` returns to its shipped default of `true`. The toggle is still
 *    there, still flippable in one line in either direction, still asserted by
 *    `tests/test-cycle169-blog-layout.php` §8. 1.19.323 built it for exactly
 *    this: "one of the outcomes he may choose". He chose, then chose again, and
 *    both cost one line.
 *
 * ⚠⚠ "SMALL" IS THE FOUNDER'S WORD AND IT IS LOAD-BEARING, BECAUSE THE ORIGINAL
 *    COMPLAINT THAT LED TO THE HIDE IS STILL TRUE OF THESE IMAGES. Open finding
 *    R1 (1.19.322/1.19.323) records that this blog's featured images are
 *    designed as PORTRAIT POSTERS WITH TEXT BAKED IN, and that an
 *    `object-fit: cover` crop cuts their headlines. That is not a stale note:
 *    ⭐ MEASURED ON STAGING 2026-08-31 by direct query, not estimated —
 *    37 published posts, **23 portrait** (683x1024, ratio 0.67), 10 landscape,
 *    3 square, 1 with no thumbnail. A 683x1024 poster filling a ~690px column
 *    and cropped to a 400px band shows roughly the middle 39% of itself, with
 *    the baked-in headline off the top.
 *
 * ⭐ SO THE CROP IS GONE ALONG WITH THE HIDE. `assets/css/blog-post.css` § 2 now
 *    constrains the image by HEIGHT and lets the width follow the picture's own
 *    aspect ratio, so nothing is ever cut: a portrait poster renders as a small
 *    centred card, a landscape photo as a wide short banner, both capped at the
 *    same height. Un-hiding without that change would have re-shipped the exact
 *    defect that produced item 474 in the first place. The full reasoning,
 *    including why the element must hug the picture rather than letterbox it,
 *    is in that stylesheet next to the rule.
 *
 * ⛔ SCOPE IS UNCHANGED AND IS STILL THE SINGLE-POST MASTHEAD ONLY. Cards, the
 *    related-article grid, Open Graph / Twitter images, Rank Math schema and
 *    feeds never pass through this gate and are untouched by this reversal in
 *    either direction — asserted by §8.7, which is unchanged.
 */

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

		/*
		 * ═══════════════════════════════════════════════════════════════════
		 * ⭐ 1.19.276 — THE COLOURING LINE IS NOT ABSORBED BY A CHAPTER
		 *    ADVENTURE. ⛔ `CYCLE165-OPS-019`, the rail limb.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * ⛔ The founder-ruled colouring title (`FD-557`) CONTAINS 'mariana
		 *    trench', so the substring fallback below would resolve any post
		 *    carrying that title to the `mariana_trench` adventure and rail a
		 *    CHAPTER book beside COLOURING-book words. That is the `FD-549`
		 *    shape: an image and a price that describe different objects.
		 *
		 * ⭐⭐ THIS IS A NEGATIVE GUARD, AND THE DIRECTION MATTERS. It only
		 *    ever REFUSES a match; it never MAKES one. The authoritative,
		 *    ID-based test lives in `bhp_get_series_adventures()`
		 *    (`bhp_is_colouring_product()`), where the subject is a PRODUCT and
		 *    an ID exists. ⛔ Here the subject is a POST, which has no
		 *    colouring product ID to compare against, so the only available
		 *    signal is the title -- and the safe way to use an unreliable
		 *    signal is to let it VETO a guess, never to let it make one.
		 *
		 * ⭐ FAILING SAFE MEANS RAILING NOTHING. If this vetoes, `$key` stays
		 *    empty and the rail renders no adventure at all. ⛔ That is
		 *    "degrade, never mix" (spec R2.3) applied to a rail: an absent
		 *    rail is a non-event, a wrong rail is a false claim.
		 *
		 * ⚠ IT IS DELIBERATELY BROAD. Any post whose title says "coloring
		 *   book" or "colouring book" is refused a chapter adventure, whether
		 *   or not a colouring PRODUCT exists yet -- so the veto is already in
		 *   force before the first product record, which is the entire point
		 *   of `ACT-OPS-269`. Both spellings are matched because the corpus
		 *   uses "colouring" internally and `FD-557` uses "Coloring" on the
		 *   cover.
		 */
		foreach ( array( 'coloring book', 'colouring book' ) as $colouring_needle ) {
			if ( false !== strpos( $title, $colouring_needle ) ) {
				return (string) apply_filters( 'bhp_blog_rail_adventure', '', $post );
			}
		}

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
 * ┌───────────────────────────────────────────────────────────────────────────
 * │ ⭐⭐ SUPERSEDED IN PART, 2026-08-31, theme 1.19.344 (`CYCLE173-LD-344`).
 * │ ⛔ THE PARAGRAPH IMMEDIATELY ABOVE IS PRESERVED VERBATIM AND DELIBERATELY
 * │    NOT CORRECTED IN PLACE (additive-only discipline). Read this before
 * │    relying on its ask count.
 * │
 * │ ⭐ FOUNDER ORDER, 2026-08-31, ⚠ RELAYED by the Chief of Staff and NOT
 * │    witnessed first-hand by the session that wrote this note:
 * │
 * │      "There is Big redundancy on the blog pages! - we have FREE chapter
 * │       for reluctant readers then another box saying get the free reluctant
 * │       reader kit - Remove the Get the Free kit and keep the email capture
 * │       one-"
 * │
 * │ ⛔ WHAT THE COUNT ABOVE MISSED. It says a post carries "exactly two asks",
 * │    and `tests/test-protected-elements.php` asserted exactly that — but its
 * │    counter only ever looked at `.bhp-post-capture`, the popup and the
 * │    footer capture. It NEVER counted the end-of-article contextual-CTA
 * │    block. Measured in the live production DOM on 2026-08-31, a
 * │    non-registry post also rendered "Get the Free Reluctant Reader
 * │    Adventure Kit" -> "Get the Free Kit", asking for the SAME lead magnet
 * │    as the capture directly above it. So the doctrine held on paper while a
 * │    third ask shipped, and the founder found it by looking at the page.
 * │
 * │ ⭐ WHAT CHANGED: that block is now suppressed on single posts at its call
 * │    site — `template-parts/guides/related-content.php`, which carries the
 * │    full record and the founder's words. `BHP_CTA_Engine` itself is
 * │    untouched, so every other surface is unaffected.
 * │
 * │ ⭐ WHAT DID NOT CHANGE, so this is not misread as reopening item 110/118:
 * │    the BOOK RAIL still renders and is still a book bridge, not an ask. The
 * │    end-of-post capture still renders. The popup still renders. The footer
 * │    capture is still suppressed. The ask count in the sentence above is
 * │    therefore still **two** — it is now two in the rendered page as well as
 * │    in the counter, which is the part that was not true before.
 * │
 * │ ┌─────────────────────────────────────────────────────────────────────────
 * │ │ ⛔⛔ CORRECTED 2026-08-31 by `CYCLE173-LD-344B` — THE "STILL TWO"
 * │ │     SENTENCE DIRECTLY ABOVE IS WRONG. `CYCLE173-LD-5`.
 * │ │
 * │ │ ⛔ THE PARAGRAPH ABOVE IS PRESERVED VERBATIM AND DELIBERATELY NOT
 * │ │    EDITED IN PLACE (additive-only discipline). Do not rely on its count.
 * │ │
 * │ │ ⚠ IT WAS WRITTEN BY `CYCLE173-LD-344`, WHICH WAS KILLED BEFORE IT COULD
 * │ │   RUN A BROWSER AGAINST THE RESULT. It is an inference from the diff,
 * │ │   not an observation of the page — which is precisely the failure class
 * │ │   Standing Rules §9.2 exists to stop.
 * │ │
 * │ │ ⭐ MEASURED IN THE LIVE STAGING DOM at theme 1.19.344 — who: Aragorn
 * │ │    (`CYCLE173-LD-344B`) · when: 2026-08-31 · with: a real browser at a
 * │ │    VERIFIED `window.innerWidth` of 375 and again at 1440, on
 * │ │    `/blog/amazon-rainforest-facts-for-kids/` (post 546, a non-registry
 * │ │    post, i.e. the fallback path this change actually governs).
 * │ │
 * │ │    Document order inside <article>, with measured offsets:
 * │ │
 * │ │      .bhp-capture-band   1582px  "FREE Chapter for Reluctant Readers"
 * │ │                                  + <input type=email>          ← ASK 1
 * │ │      .bhp-book-rail      2963px  "The book this came from"     (bridge)
 * │ │      .bhp-post-capture   4388px  "FREE Chapter for Reluctant Readers"
 * │ │                                  + <input type=email>          ← ASK 2
 * │ │      .guide-continuation 4868px  related guides                (nav)
 * │ │      #parent-ab-popup    (in DOM, storagePrefix bhp_parent_popup,
 * │ │                           thankYouPath adventure-kit-thank-you) ← ASK 3
 * │ │
 * │ │ ⛔ THAT IS **THREE** ASKS FOR THE SAME LEAD MAGNET, NOT TWO, and TWO OF
 * │ │    THEM CARRY THE BYTE-IDENTICAL HEADLINE "FREE Chapter for Reluctant
 * │ │    Readers". `article input[type=email]` returns **2**.
 * │ │
 * │ │ ⭐ THE 1.19.344 CHANGE IS STILL CORRECT AND IS NOT WEAKENED BY THIS.
 * │ │    The founder's order was to remove the box that said "Get the Free
 * │ │    Kit", and it is GONE — `Get the Free Kit` now matches ZERO times in
 * │ │    the rendered page, verified at both viewports. What is corrected here
 * │ │    is only the CLAIM ABOUT THE RESULTING COUNT.
 * │ │
 * │ │ ⛔ THE REMAINING DUPLICATION IS NOT RESOLVED HERE, AND DELIBERATELY SO.
 * │ │    Whether a second identical email capture should also go is a founder
 * │ │    decision of exactly the kind Standing Rules §7 forbids an agent from
 * │ │    taking, and Gandalf's brief was explicit that the mid-post capture
 * │ │    band STAYS. It is recorded as `CYCLE173-LD-5` and routed to Andrew.
 * │ │    ⚠ Do not "finish the job" by deleting one of them without his word.
 * │ │
 * │ │ ⚠ AND THE ASK COUNTER IN `tests/test-protected-elements.php` STILL DOES
 * │ │   NOT COUNT `.bhp-capture-band`. Extending it was left alone on purpose:
 * │ │   it would encode an answer to the open question above. Same finding.
 * │ └─────────────────────────────────────────────────────────────────────────
 * └───────────────────────────────────────────────────────────────────────────
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
 * ⭐⭐ 1.19.321 — HOW FAR DOWN THE ARTICLE THE RAIL SITS, AS A FRACTION.
 *
 * `CYCLE169-LD-BLOG-LAYOUT-TEMPLATE` moves the book-sales module off the top of
 * the article and down to roughly the three-quarter mark. This is the tunable.
 *
 * Clamped rather than trusted: a filter returning 0 or 2 would put a commerce
 * control above the fold or off the end of the document, and rubric row 1
 * (exactly one above-fold primary) is a standing constraint, not a preference.
 *
 * @return float
 */
function bhp_blog_rail_position_ratio() {
	$ratio = (float) apply_filters( 'bhp_blog_rail_position_ratio', 0.75 );

	return min( 0.95, max( 0.05, $ratio ) );
}

/**
 * Visible-text length of `$html` up to `$offset`, in bytes of stripped text.
 *
 * ⭐ WHY TEXT AND NOT RAW BYTES. "Three quarters of the way down the article" is
 *    a claim about what the READER has read, and raw byte offsets do not measure
 *    that: post 28 carries eleven affiliate anchors whose markup is several
 *    hundred bytes of `href`, `rel`, `aria-label` and `data-bhp-*` attributes
 *    that a reader never sees. Measuring raw bytes would pull the rail upward on
 *    exactly the posts that carry the most commerce markup, which is the wrong
 *    direction for the wrong reason.
 *
 * @param string $html   Rendered content.
 * @param int    $offset Byte offset.
 * @return int
 */
function bhp_blog_visible_text_length( $html, $offset ) {
	return strlen( wp_strip_all_tags( substr( $html, 0, (int) $offset ) ) );
}

/**
 * Choose the byte offset in rendered post HTML at which the rail is inserted.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.321 — THE RAIL MOVED FROM THE TOP OF THE ARTICLE TO THE 3/4 MARK
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ SUPERSEDED BEHAVIOUR, PRESERVED IN WORDS SO IT IS NOT RE-DERIVED. Until
 *    1.19.320 this function returned `max( second <h2>, Nth </p> )` — "after the
 *    first useful answer". On a five-heading post that is roughly a fifth of the
 *    way down, which is what the brief means by "the books-on-top placement".
 *    The reasoning for it was sound at the time (bridge early, stay below the
 *    fold) and it is not being called a bug; it is being MOVED, on instruction.
 *
 * ⭐ THE SHAPE OF THE ANSWER IS DELIBERATELY UNCHANGED, AND THAT MATTERS. It is
 *    still the LATER of two anchors:
 *
 *      · `$h2_offset` — the last `<h2>` section boundary at or before the target
 *        mark, never the first heading. Inserting immediately before a heading
 *        is the cleanest possible break: it never lands between a heading and
 *        the paragraph it introduces.
 *      · `$p_offset`  — the last `</p>` boundary at or before the target mark,
 *        floored at `bhp_blog_rail_min_paragraphs()`. On a post with no usable
 *        heading this is the whole answer.
 *
 *    Taking the LATER of the two means the floor can only ever push the rail
 *    DOWN the page, never up. `max()` is correct here and `min()` would be a bug
 *    — the same invariant this function has always carried, and the same one
 *    `test-blog-post-template.php` §5.4 asserts.
 *
 * ⛔ IT IS STILL AN OFFSET INTO UNMODIFIED CONTENT AND STILL A TAG BOUNDARY.
 *    Every candidate is the start of an `<h2` or the end of a `</p>`, so the
 *    split can never fall inside an anchor, an attribute or the FTC disclosure
 *    paragraph. The §26 insert-only argument in `bhp_blog_inject_midcapture()`
 *    applies here word for word.
 *
 * ⚠ ONE MEASURED IMPRECISION, DISCLOSED RATHER THAN HIDDEN. The capture band
 *   (priority 11) is already in `$html` when this runs at priority 12, so its
 *   dozen or so words count toward the visible-text total and nudge the target
 *   fractionally later. It is well under one percent of a real article and the
 *   rendered position is verified in a browser rather than trusted from this
 *   arithmetic.
 *
 * Returns null when neither anchor is present, in which case the caller appends.
 *
 * @param string $html Rendered content.
 * @return int|null
 */
/**
 * ⭐⭐ 1.19.321 — THE MINIMUM GAP BETWEEN THE CAPTURE BAND AND THE RAIL.
 *
 * ⛔ THIS EXISTS BECAUSE THE FIRST BUILD PUT THEM 1.7% APART ON A REAL POST, AND
 *    A BROWSER FOUND IT, NOT A UNIT TEST. On staging post 28 the band anchored at
 *    70.6% of visible text and the rail at 72.3% — an email ask and a buy control
 *    stacked with about 150 characters between them. The cause is structural
 *    rather than arithmetical: that post's second section is an eight-book list
 *    that runs to two thirds of the article, so "after section two" is genuinely
 *    late on that post. Both anchors were individually behaving as specified.
 *
 * ⭐ THE RAIL IS THE ONE THAT YIELDS, AND THE DIRECTION IS DELIBERATE. It only
 *    ever moves DOWN, which is the same invariant every other limb of this
 *    function carries: a control can be pushed further from the fold, never
 *    closer to it. Moving the BAND up instead would have silently overridden the
 *    brief's explicit "after the second H2", which is not this build's call.
 *
 * @return float
 */
function bhp_blog_rail_min_gap_ratio() {
	$ratio = (float) apply_filters( 'bhp_blog_rail_min_gap_ratio', 0.08 );

	return min( 0.5, max( 0.0, $ratio ) );
}

/**
 * The latest boundary at or before `$target`, or — when none clears `$floor` —
 * the earliest boundary that does.
 *
 * @param string $html   Rendered content.
 * @param array  $hits   preg_match_all PREG_OFFSET_CAPTURE hits.
 * @param int    $target Visible-text target.
 * @param int    $floor  Visible-text floor the candidate must clear.
 * @param bool   $at_end True to take the offset AFTER the matched token.
 * @param int    $skip   Number of leading hits to ignore.
 * @return int|null
 */
function bhp_blog_rail_pick( $html, $hits, $target, $floor, $at_end, $skip ) {
	$best     = null;
	$fallback = null;
	foreach ( $hits as $index => $hit ) {
		if ( $index < $skip ) {
			continue;
		}
		$candidate = $at_end ? (int) $hit[1] + strlen( $hit[0] ) : (int) $hit[1];
		$text      = bhp_blog_visible_text_length( $html, $candidate );
		if ( $text < $floor ) {
			continue;
		}
		if ( null === $fallback ) {
			$fallback = $candidate; // Earliest boundary clearing the gap floor.
		}
		if ( $text <= $target ) {
			$best = $candidate;     // Latest boundary at or before the target.
		}
	}

	return ( null !== $best ) ? $best : $fallback;
}

function bhp_blog_rail_offset( $html ) {
	$total = strlen( wp_strip_all_tags( $html ) );
	if ( $total < 1 ) {
		return null;
	}
	$target = (int) round( $total * bhp_blog_rail_position_ratio() );

	/*
	 * The band is already in `$html` at this point (priority 11 against this
	 * filter's 12), so the gap is measured against where it actually landed
	 * rather than against where it was predicted to land.
	 */
	$floor   = 0;
	$band_at = strpos( $html, 'bhp-capture-band' );
	if ( false !== $band_at ) {
		$floor = bhp_blog_visible_text_length( $html, $band_at )
			+ (int) round( $total * bhp_blog_rail_min_gap_ratio() );
	}

	$h2_offset = null;
	if ( preg_match_all( '/<h2[\s>]/i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		// Skip 1: never the opening section's own heading.
		$h2_offset = bhp_blog_rail_pick( $html, $m[0], $target, $floor, false, 1 );
	}

	$p_offset = null;
	if ( preg_match_all( '/<\/p>/i', $html, $mp, PREG_OFFSET_CAPTURE ) ) {
		$need     = bhp_blog_rail_min_paragraphs();
		$p_offset = bhp_blog_rail_pick( $html, $mp[0], $target, $floor, true, $need - 1 );
		if ( null === $p_offset && count( $mp[0] ) >= $need ) {
			/*
			 * A post short enough that its Nth paragraph is already past the
			 * target. The paragraph floor wins, exactly as it did before
			 * 1.19.321 — BUT ONLY IF IT ALSO CLEARS THE BAND GAP.
			 *
			 * ⛔ THIS CONDITION WAS MISSING IN THE FIRST 1.19.321 BUILD AND THE
			 *    REGRESSION TEST IN `test-cycle169-blog-layout.php` §3.5 CAUGHT
			 *    IT: on a lopsided fixture the gap guard correctly rejected every
			 *    candidate, and then this branch handed back the THIRD PARAGRAPH
			 *    — putting the sales module at 7.7% of the article, which is the
			 *    exact defect this release exists to remove. Failing to null here
			 *    is better than failing upward: null makes the caller APPEND the
			 *    rail at the end of the article, which is below everything and
			 *    cannot collide with anything.
			 */
			$nth = (int) $mp[0][ $need - 1 ][1] + strlen( $mp[0][ $need - 1 ][0] );
			if ( bhp_blog_visible_text_length( $html, $nth ) >= $floor ) {
				$p_offset = $nth;
			}
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
 * 4b · THE MID-POST CAPTURE — 1.19.296, `CYCLE167-LD-CAPTURE-FIX-BUILD`
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * Which posts carry a mid-post capture.
 *
 * ⭐ SCOPED TO THE TWO TOP ENTRY POSTS RATHER THAN THE WHOLE BLOG, and the
 *    narrowness is deliberate. These two take 97 and 74 human entries per 30
 *    days (ranks 3 and 4 sitewide, production access logs). Every other post
 *    keeps exactly the asks it had at 1.19.295, so this change cannot alter
 *    the shape of a post nobody investigated.
 *
 * ⭐ IDS, NOT SLUGS. A slug can be edited in the WordPress admin without
 *    anybody touching code, which would silently switch this off. IDs cannot.
 *
 * @return int[]
 */
function bhp_blog_midcapture_post_ids() {
	return (array) apply_filters( 'bhp_blog_midcapture_post_ids', array( 28, 88 ) );
}

/**
 * Where the mid-post capture goes: immediately BEFORE the first `<h2>`, i.e.
 * at the end of the introduction and before the article's first real section.
 *
 * ⛔ IT MUST LAND ABOVE THE BOOK RAIL, NOT BESIDE IT. `bhp_blog_rail_offset()`
 *    targets `max(second <h2>, Nth </p>)`. Anchoring here to the FIRST `<h2>`
 *    keeps the two apart by construction rather than by luck.
 *
 * @param string $html Rendered content.
 * @return int|null Byte offset, or null when the shape does not fit.
 */
function bhp_blog_midcapture_offset( $html ) {
	if ( ! preg_match( '/<h2[\s>]/i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		return null;
	}
	$offset = (int) $m[0][1];

	/*
	 * ⛔ REFUSE A PLACEMENT THAT IS NOT ACTUALLY "AFTER THE INTRODUCTION". If
	 *    the first heading arrives before two closing paragraphs, the post
	 *    opens straight into a section and there is no introduction to sit
	 *    after — insert nothing rather than wedge a form under the title.
	 */
	if ( ! preg_match_all( '/<\/p>/i', substr( $html, 0, $offset ), $mp ) || count( $mp[0] ) < 2 ) {
		return null;
	}

	return $offset;
}

/**
 * Inject the mid-post capture into the rendered post body.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ STANDING RULES §26 — THIS FUNCTION IS INSERT-ONLY, AND THAT IS THE
 *     WHOLE SAFETY ARGUMENT. READ THIS BEFORE CHANGING A CHARACTER OF IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ These two posts carry **12 and 9 live Amazon affiliate anchors** — Andrew
 *    Signore's personal Associates codes, earning into his account. §26: *"they
 *    are revenue producing links"*, and the correct mental model is the
 *    checkout button, not the paragraph around it. §26.5 names *"a build step
 *    — minification, comment-stripping, find-and-replace, migration"* and *"a
 *    theme or plugin deploy that changes rendered output"* as exactly the ways
 *    they get lost. A `the_content` filter is precisely that kind of machinery.
 *
 * ⭐ SO THE CONTENT IS NEVER REWRITTEN, ONLY SPLIT AND REJOINED:
 *        substr( $content, 0, $offset ) . $panel . substr( $content, $offset )
 *    ⛔ There is no `preg_replace`, no `str_replace`, no DOM round-trip and no
 *    re-serialisation anywhere in this path. Every byte of the original
 *    `$content` appears in the output, in its original order. An anchor cannot
 *    be dropped, and its `tag=` parameter cannot be altered, because nothing
 *    ever reads or edits one. ⭐ The panel itself contains no Amazon URL, so
 *    the affiliate count after this filter is arithmetically identical to the
 *    count before it.
 *
 * ⭐ THE OFFSET IS A TAG BOUNDARY (`<h2`), so the split can never fall inside
 *    an anchor, an attribute or the FTC disclosure paragraph.
 *
 * ⛔ THE COUNT-DECREASE TEST IS STILL RUN. This reasoning is why the test
 *    should pass; it is not a substitute for running it. §26.6: a before/after
 *    count that was not actually run is a FABRICATED CHECK.
 *
 * ⛔ THE FOUR GUARDS mirror `bhp_blog_inject_rail()` for the same reasons —
 *    `the_content` is called by feeds, REST, SEO plugins and excerpt blocks.
 *
 * @param string $content Rendered content.
 * @return string
 */
function bhp_blog_inject_midcapture( $content ) {
	static $done = false;

	if ( ! (bool) apply_filters( 'bhp_blog_midcapture_enabled', true ) ) {
		return $content;
	}
	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⚠⚠ 1.19.321 — THIS PANEL STANDS DOWN WHERE THE STANDARD BAND RENDERS.
	 *     ⛔ THIS IS THE ONE JUDGEMENT CALL IN `CYCLE169-LD-BLOG-LAYOUT-
	 *        TEMPLATE` AND IT IS FLAGGED TO THE CHIEF OF STAFF, NOT ABSORBED.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * THE ARITHMETIC THAT FORCED IT. This panel is scoped to posts 28 and 88.
	 * Post 28 is also one of the two pilot posts for the standard band. Without
	 * this guard post 28 would carry, in one article: this panel after the
	 * introduction, the standard band after section two, the end-of-post
	 * capture, and the popup — FOUR asks, three of them the same offer, on the
	 * page the brief describes as the "standard blog-post layout pattern".
	 * "Automatically and consistently" cannot mean two posts get a different
	 * and heavier shape than the other thirty-four.
	 *
	 * ⛔ NOTHING IS DELETED. The panel, its template, its post-ID list, its
	 *    offset function and its own filter are all untouched and all still
	 *    tested. `add_filter( 'bhp_blog_capture_band_enabled', '__return_false' )`
	 *    brings this panel straight back, in one line, with no code restored —
	 *    and `bhp_blog_midcapture_enabled` still turns it off independently. A
	 *    switch that only travels one way is not a switch.
	 *
	 * ⚠ WHAT THIS COSTS, STATED PLAINLY: posts 28 and 88 lose their after-the-
	 *   introduction ask, which `CYCLE167` added on a real measurement (ranks 3
	 *   and 4 by human entries). They gain the section-two band instead, which
	 *   is LOWER on the page. If Andrew wants the earlier ask kept on those two,
	 *   the one-line reversal above is the answer and no rebuild is needed.
	 */
	if ( bhp_blog_capture_band_enabled() ) {
		return $content;
	}
	if ( $done ) {
		return $content;
	}
	if ( ! bhp_blog_template_active() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	if ( ! in_array( (int) get_the_ID(), array_map( 'intval', bhp_blog_midcapture_post_ids() ), true ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'bhp-post-capture--mid' ) ) {
		return $content;
	}

	$offset = bhp_blog_midcapture_offset( $content );
	if ( null === $offset ) {
		return $content;
	}

	ob_start();
	get_template_part( 'template-parts/acquisition/post-mid-capture' );
	$panel = (string) ob_get_clean();
	if ( '' === $panel ) {
		return $content;
	}

	$done = true;

	return substr( $content, 0, $offset ) . $panel . substr( $content, $offset );
}
/*
 * ⭐ PRIORITY 13 — DELIBERATELY **AFTER** THE BOOK RAIL'S 12, AND THE FIRST
 *    VERSION OF THIS LINE HAD IT THE OTHER WAY ROUND. The wrong reasoning is
 *    written out so it is not re-derived:
 *
 *    ⛔ THE WRONG ANSWER: "run first, so the two are injected in page order."
 *       That silently breaks the rail. `bhp_blog_rail_offset()` anchors on the
 *       **second** `<h2>` — and the panel this filter inserts CONTAINS an
 *       `<h2>` of its own, high in the document. Injecting first would have
 *       made the capture panel's heading count as one of the article's
 *       headings, dragged the rail's anchor upward, and moved a commerce rail
 *       that nobody asked to move. No test would have failed; the rail would
 *       just have been in the wrong place.
 *
 *    ⭐ RUNNING LAST MEANS THE RAIL COMPUTES ITS OFFSETS ON THE ORIGINAL
 *       ARTICLE, exactly as it did at 1.19.295. Its behaviour is unchanged by
 *       this release, which is what "additive" has to mean.
 *
 *    ⭐ AND THIS FILTER IS STILL CORRECT AFTERWARDS: it anchors on the FIRST
 *       `<h2>`, while the rail inserts at `max(second <h2>, Nth </p>)`, which
 *       is necessarily below it on any post with two or more headings. Both
 *       target posts have five. ⚠ VERIFIED ON STAGING BY READING THE RENDERED
 *       DOM ORDER, not assumed from this arithmetic.
 */
add_filter( 'the_content', 'bhp_blog_inject_midcapture', 13 );

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 4c · THE SLIM CAPTURE BAND — 1.19.321, `CYCLE169-LD-BLOG-LAYOUT-TEMPLATE`
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * THE STANDARD PATTERN, applied to every post automatically rather than to a
 * hand-kept list: one line, one field, one button, on a single row, after the
 * article's second main content section.
 *
 * ⛔ IT IS NOT A NEW SIGNUP PATH. `post-capture-band.php` renders
 *    `signup-form.php` with the same `lead_magnet` and the same `context` as the
 *    end-of-post capture, so it reaches the same handler, nonce, MC4WP audience
 *    and Mailchimp tags the footer capture already uses. The brief was explicit
 *    about this and nothing here invents a second pipe.
 * ⛔ IT ADDS NO POPUP and changes no funnel storage key or analytics prefix.
 * ⛔ It changes no price, coupon, shipping tier, stock status, product record or
 *    WooCommerce setting, and it does not modify one character of any post's
 *    stored `post_content`.
 */

/**
 * Whether the standard band renders at all. Default ON.
 *
 * ⭐ TURNING THIS OFF IS A COMPLETE RETURN TO 1.19.320 BEHAVIOUR, not a partial
 *    one: it also un-stands-down the posts 28/88 mid capture (see
 *    `bhp_blog_inject_midcapture()`), so a single line restores the previous
 *    layout on every post including those two.
 *
 * @return bool
 */
function bhp_blog_capture_band_enabled() {
	return (bool) apply_filters( 'bhp_blog_capture_band_enabled', true );
}

/**
 * ⭐⭐ 1.19.322 — AFTER WHICH PARAGRAPH THE BAND SITS. DEFAULT 2.
 *
 * ⛔ SUPERSEDED TUNABLE, NAMED IN WORDS SO A LIVE FILTER IS NOT SILENTLY
 *    ORPHANED: 1.19.321 shipped `bhp_blog_capture_band_after_section` (default
 *    2), which counted `<h2>` tokens and anchored on the heading that OPENED
 *    section 3. THAT FILTER NAME NO LONGER EXISTS. It was removed rather than
 *    left in place returning a value nothing reads — a tunable that tunes
 *    nothing is worse than an absent one, because it reports success. Nothing
 *    in this theme or its suites registered against it (checked by grep across
 *    every `.php` in the tree before removal); an external caller would now get
 *    a PHP notice rather than a silent no-op, which is the honest failure.
 *
 * ⭐ WHY IT MOVED, AND IT IS A MEASURED PROBLEM RATHER THAN A PREFERENCE. The
 *    section-2 anchor was round 1's own finding B3: it landed at 29.6% of post
 *    32 and 70.6% of post 28, because post 28's second section is an eight-book
 *    list running to two thirds of the article. Both were "after section two"
 *    exactly as briefed, and the spread is inherent to counting headings —
 *    a heading anchor measures the AUTHOR's structure, not the READER's depth.
 *
 * ⚠ THE FOUNDER'S INSTRUCTION IS RELAYED, NOT WITNESSED BY THIS AGENT. Quoted
 *   in the round-2 brief as: *"Not many people finish the blogs - I think the
 *   email ask needs to be much high on the page. Right after the first
 *   paragraph or second paragraph."* Recorded with its provenance because a
 *   relayed quote and a first-hand one are different evidence (§9.2 rule 2).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.341 (`CYCLE171-LD-341` item 4) — THE DEFAULT MOVES 2 → 5.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FOUNDER SAW THE RESULT AND RULED AGAINST IT. 2026-08-31, on the Adams
 *    post: the band at paragraph 2 splits the article's opening argument, and
 *    the gap it leaves reads as a break in the writing. `CYCLE171-MKT-2`
 *    documented the same thing independently. The instruction is that the
 *    article's opening must read UNINTERRUPTED.
 *
 * ⛔ THIS MOVES AN ASK. IT DOES NOT ADD ONE. The two-asks-one-bridge doctrine
 *    (carrier 110/119) is intact: still exactly one mid-post band and one
 *    end-of-post capture, still the same `lead_magnet`, `context`, handler,
 *    nonce, audience and tags. Nothing about the count or the pipe changed.
 *
 * ⛔ IT IS A PARAGRAPH COUNT AGAIN, NOT A RESTORED HEADING ANCHOR, even though
 *    the brief offered "after the first H2 section ends" as an option. The
 *    heading anchor is the thing 1.19.321 shipped and 1.19.322 REMOVED for a
 *    measured reason recorded immediately above: it landed at 29.6% of one
 *    pilot post and 70.6% of another, because it measures the AUTHOR's
 *    structure rather than the READER's depth. Re-introducing it to solve a
 *    depth complaint would re-introduce that spread. The brief's own words were
 *    "whichever the existing mechanism supports most cleanly" — the mechanism
 *    supports paragraph counting cleanly and heading counting badly, and it was
 *    deliberately stripped of the latter.
 *
 * ⚠ 5, NOT 6, AND THE CHOICE IS DISCLOSED RATHER THAN PRESENTED AS OBVIOUS. The
 *   brief said "~paragraph 5-6". 5 is the lower end because the max-ratio guard
 *   below stands the band down entirely on a post that is not long enough to
 *   host it — so erring later costs a real ask on a short post, while erring
 *   earlier costs nothing but a slightly earlier one on a long post. The value
 *   is one filtered integer; a ruling either way is a one-value change.
 *
 * @return int
 */
function bhp_blog_capture_band_after_paragraph() {
	return max( 1, (int) apply_filters( 'bhp_blog_capture_band_after_paragraph', 5 ) );
}

/**
 * The block-level elements a paragraph can be BURIED IN, for the purpose of
 * deciding whether it is a "clean top-level paragraph".
 *
 * ⛔ `div` IS DELIBERATELY ABSENT, AND THE OMISSION IS THE DESIGN. Gutenberg
 *    wraps ordinary groups of ordinary paragraphs in `<div class="wp-block-
 *    group">`, so counting every `div` as a container would classify perfectly
 *    clean prose as buried and push the band down the page — the exact opposite
 *    of what this release is for. The brief's words are "list/blockquote/embed
 *    structure", and this list is that, spelled out. Embeds are covered by
 *    `figure`, which is what `wp-block-embed` renders as.
 *
 * @return string[]
 */
function bhp_blog_capture_band_containers() {
	return (array) apply_filters(
		'bhp_blog_capture_band_containers',
		array(
			'blockquote',
			'figure',
			'picture',
			'details',
			'table',
			'thead',
			'tbody',
			'tfoot',
			'video',
			'audio',
			'aside',
			'form',
			'pre',
			'ul',
			'ol',
			'li',
			'dl',
			'dt',
			'dd',
			'tr',
			'td',
			'th',
		)
	);
}

/**
 * Every paragraph in `$html`, in document order, with the offset just AFTER its
 * `</p>` and whether it was top-level.
 *
 * ⭐ ONE LEFT-TO-RIGHT PASS OVER TAG TOKENS, WITH A DEPTH COUNTER. It is not a
 *    DOM parse and it deliberately is not one: `DOMDocument` would re-serialise
 *    the article, and re-serialisation is precisely the class of change §26
 *    forbids on a page carrying live affiliate anchors. This function only ever
 *    READS offsets; the caller splices at one of them and never rewrites a byte.
 *
 * ⚠ THE ALTERNATION ORDER IS LOAD-BEARING: the container names are emitted
 *   BEFORE the bare `p`, so `<picture>` and `<pre>` match as themselves rather
 *   than as a `<p>` followed by stray characters. PHP's alternation is
 *   first-match-wins, so reordering this list would be a real bug.
 *
 * @param string $html Rendered content.
 * @return array<int,array{end:int,top:bool}>
 */
function bhp_blog_capture_band_paragraphs( $html ) {
	$containers = array_map( 'preg_quote', bhp_blog_capture_band_containers() );
	$pattern    = '/<(\/?)(' . implode( '|', $containers ) . '|p)(?=[\s>\/])/i';

	if ( ! preg_match_all( $pattern, $html, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER ) ) {
		return array();
	}

	$depth    = 0;
	$open_top = false;
	$out      = array();

	foreach ( $m as $set ) {
		$closing = ( '/' === $set[1][0] );
		$tag     = strtolower( $set[2][0] );
		$offset  = (int) $set[0][1];

		if ( 'p' === $tag ) {
			if ( $closing ) {
				$out[] = array(
					'end' => $offset + 4,            // just past `</p>`
					'top' => ( $open_top && 0 === $depth ),
				);
				$open_top = false;
			} else {
				$open_top = ( 0 === $depth );
			}
			continue;
		}

		// `max(0, …)` so malformed markup can never drive the depth negative and
		// make a buried paragraph look top-level.
		$depth = $closing ? max( 0, $depth - 1 ) : $depth + 1;
	}

	return $out;
}

/**
 * ⭐⭐ 1.19.322 — THE BAND'S ONE LINE OF COPY. THE STANDARDIZED ITEM-290 OFFER
 * HEADLINE, AND ROUND 1'S FINDING B1 IS CLOSED BY THIS.
 *
 * ⛔ SUPERSEDED WORDING, PRESERVED IN WORDS BECAUSE THE MOVEMENT IS THE POINT:
 *    1.19.321 shipped *"Want a free chapter to test on your kiddo?"* — the
 *    round-1 brief's own draft line, shipped as briefed and FLAGGED in the same
 *    sitting as a THIRTEENTH wording of one offer. `CYCLE167-MKT-MAGNET-
 *    TEARDOWN` had found twelve of twelve capture surfaces naming that offer
 *    twelve different ways; the founder then picked ONE name (carrier item
 *    290), and since 1.19.297 the popup, the footer capture, the mid capture,
 *    the end-of-post capture and the two landing pages have carried
 *    byte-identical strings for that reason.
 *
 * ⭐ THE STRING BELOW IS BYTE-IDENTICAL TO ALL OF THEM. It is not a paraphrase
 *    and not a near-match: the same literal, the same `brave-hearts`
 *    textdomain, so `test-cycle167-capture-copy.php`'s `$offer_headline`
 *    constant matches it character for character.
 *
 * ⛔ THE WORD "test" IS NOW ABSENT FROM THE BAND ENTIRELY — an explicit
 *    constraint in the round-2 brief, and it is what the superseded line
 *    carried ("to test on your kiddo"). Asserted on the RENDERED band, at
 *    `tests/test-cycle169-blog-layout.php` §2.24, not on this source line.
 *
 * ⚠ THIS FUNCTION SURVIVES RATHER THAN THE LITERAL BEING INLINED, AND THAT IS
 *   ON INSTRUCTION: the round-2 brief says to keep the copy "in the single
 *   filterable spot — the founder may still swap the phrase in-thread". So the
 *   indirection stays and a future ruling remains a one-line change.
 *
 * VOICE §9.1: no "we". No em dash. No outcome claim. Reading age 6 to 9.
 *
 * @return string
 */
function bhp_blog_capture_band_line() {
	return (string) apply_filters(
		'bhp_blog_capture_band_line',
		__( 'FREE Chapter for Reluctant Readers', 'brave-hearts' )
	);
}

/**
 * The band's button label.
 *
 * ⭐ THE SITEWIDE STRING, BYTE-IDENTICAL to the popup, the footer capture, the
 *    mid capture and the end-of-post capture (founder's pick, carrier item 290).
 *    The control is where the one-offer-name rule bites hardest, and the brief
 *    specified a line and a button but not a NEW button string, so this one is
 *    inherited rather than invented. Filterable alongside the line above.
 *
 * @return string
 */
function bhp_blog_capture_band_button() {
	return (string) apply_filters(
		'bhp_blog_capture_band_button',
		__( 'Send me the chapter', 'brave-hearts' )
	);
}

/**
 * ⭐⭐ 1.19.322 — WHERE THE BAND GOES: IMMEDIATELY AFTER THE SECOND CLEAN
 * TOP-LEVEL PARAGRAPH OF THE ARTICLE.
 *
 * ⛔ SUPERSEDED BEHAVIOUR, PRESERVED IN WORDS SO IT IS NOT RE-DERIVED. 1.19.321
 *    returned the offset of the `<h2>` that opened section 3 — "after section
 *    two". It is not being called a bug; it did exactly what round 1 briefed.
 *    It is being MOVED, on instruction, because a heading anchor measures the
 *    AUTHOR's structure and the founder's complaint is about the READER's
 *    depth: the same rule put the ask at 29.6% of one pilot post and 70.6% of
 *    another (round 1, finding B3).
 *
 * ⭐ THE TWO-STEP RULE, AS AMENDED AT 1.19.341, AND IN THIS ORDER:
 *
 *      1. If the article's Nth paragraph (N = the tunable, now 5) is clean and
 *         top-level, the band goes immediately after it.
 *      2. Otherwise — the Nth paragraph is inside a list, a blockquote, a table
 *         or an embed — the band FALLS FORWARD to immediately after the NEXT
 *         clean top-level paragraph after it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ 1.19.341 — STEP 2 REVERSED DIRECTION, AND IT HAD TO. THIS IS THE HALF OF
 *     ITEM 4 A VERSION-BUMP-ONLY CHANGE WOULD HAVE GOT SILENTLY WRONG.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SUPERSEDED STEP 2, PRESERVED IN WORDS SO IT IS NOT RE-DERIVED: it fell
 *    back to "the FIRST clean top-level paragraph" — i.e. it searched BACKWARD,
 *    to the top of the article. The 1.19.322 note justifying that is preserved
 *    verbatim here because it was CORRECT FOR ITS OWN TARGET and the reasoning
 *    is worth keeping:
 *
 *      "STEP 2 IS 'THE FIRST CLEAN ONE', NOT 'THE SECOND CLEAN ONE', AND THE
 *       DIFFERENCE IS DELIBERATE. On an article that opens with one paragraph
 *       and then a pull-quote, counting only clean paragraphs would push the ask
 *       PAST the quote to whatever came after it. The brief's wording is
 *       'fall back to after the first clean top-level paragraph', which places
 *       it EARLIER, and earlier is the entire point of this release."
 *
 * ⭐ WHY IT INVERTS NOW: with a target of 2, "earlier" meant paragraph 1 — a
 *    negligible move, and the release wanted early. With a target of 5, the old
 *    backward fallback would fire on any article whose fifth paragraph sits in
 *    a list — extremely common in this blog's listicles — and dump the ask
 *    after paragraph ONE. That is not a near miss of the founder's instruction,
 *    it is the exact defect he ruled against, reached by a code path nobody
 *    would look at. Raising the default without inverting this would have
 *    shipped item 4 as a regression on precisely the posts it was meant to fix.
 *
 * ⛔ FALLING FORWARD CANNOT RUN AWAY WITH THE ASK. The max-ratio guard below is
 *    unchanged and still binding: if the forward search lands past 85% of the
 *    visible text, the band stands down entirely and the reader gets the
 *    end-of-post capture alone. An honest refusal, not a badly-placed ask.
 *
 * ⛔ THE REFUSAL SURVIVES, AND IT IS STILL A REFUSAL RATHER THAN A GUESS. An
 *    article with no clean top-level paragraph at all gets NO band, and so does
 *    one whose anchor lands past `bhp_blog_capture_band_max_ratio` of the
 *    visible text — on a two-paragraph post the end-of-post capture is already
 *    a screen below, and two asks a screen apart is worse than one.
 *
 *    ⭐ The escape hatch is unchanged: `[bhp_capture_band]` placed in the body
 *       by an editor renders the band exactly there. It is a real WordPress
 *       shortcode, so this theme still performs no string surgery on post
 *       content.
 *
 * ⚠ MEASURED CONSEQUENCE, DISCLOSED RATHER THAN HIDDEN: the band now lands in
 *   the first tenth of a normal article, so the rail's minimum-gap floor
 *   (`bhp_blog_rail_min_gap_ratio()`, computed in `bhp_blog_rail_offset()` from
 *   where the band ACTUALLY landed) resolves to roughly 15% and can no longer
 *   bind against a 75% target. The collision the guard was written for is
 *   structurally gone; the guard STAYS, because it costs nothing and the next
 *   ratio ruling could reintroduce the condition.
 *
 * @param string $html Rendered content.
 * @return int|null Byte offset, or null when the shape does not fit.
 */
function bhp_blog_capture_band_offset( $html ) {
	$paragraphs = bhp_blog_capture_band_paragraphs( $html );
	if ( ! $paragraphs ) {
		return null;
	}

	$want   = bhp_blog_capture_band_after_paragraph();
	$offset = null;

	if ( isset( $paragraphs[ $want - 1 ] ) && $paragraphs[ $want - 1 ]['top'] ) {
		$offset = (int) $paragraphs[ $want - 1 ]['end'];      // step 1
	} else {
		/*
		 * Step 2, 1.19.341: FORWARD from the target, never backward to the top
		 * of the article. See the block comment above for why the direction
		 * inverted when the default moved 2 -> 5.
		 */
		for ( $i = $want - 1, $n = count( $paragraphs ); $i < $n; $i++ ) {
			if ( $i >= 0 && ! empty( $paragraphs[ $i ]['top'] ) ) {
				$offset = (int) $paragraphs[ $i ]['end'];
				break;
			}
		}

		/*
		 * ⛔ AND ONLY IF THERE IS NOTHING CLEAN AT OR AFTER THE TARGET AT ALL
		 *    does it look backward, to the LAST clean paragraph before it. This
		 *    is the short-article case: a three-paragraph post can never reach
		 *    paragraph five, and refusing outright there would silently drop the
		 *    mid-post ask from every short post on the blog. It is the LAST one
		 *    before the target rather than the first, so it still lands as deep
		 *    as the article allows. The max-ratio guard below then decides
		 *    whether even that is too deep to be worth placing.
		 */
		if ( null === $offset ) {
			foreach ( $paragraphs as $index => $paragraph ) {
				if ( $index < $want - 1 && ! empty( $paragraph['top'] ) ) {
					$offset = (int) $paragraph['end'];
				}
			}
		}
	}

	if ( null === $offset ) {
		return null;
	}

	/*
	 * ⛔ THE STAND-DOWN GUARD, CARRIED OVER FROM 1.19.321 UNCHANGED IN VALUE
	 *    (0.85) THOUGH ITS FAILURE MODE HAS CHANGED. It used to catch a long
	 *    section 2; it now catches a SHORT ARTICLE, where paragraph two is
	 *    already most of the body and the end-of-post capture sits just under
	 *    it. Same guard, same tunable, different shape of post — worth stating,
	 *    because the reason a line of code exists is not always the reason it
	 *    still earns its place.
	 */
	$total = strlen( wp_strip_all_tags( $html ) );
	if ( $total > 0 ) {
		$max = min( 0.95, max( 0.1, (float) apply_filters( 'bhp_blog_capture_band_max_ratio', 0.85 ) ) );
		if ( bhp_blog_visible_text_length( $html, $offset ) > $total * $max ) {
			return null;
		}
	}

	return $offset;
}

/**
 * Render the band.
 *
 * @return string HTML, or ''.
 */
function bhp_blog_capture_band_html() {
	if ( ! bhp_blog_capture_band_enabled() ) {
		return '';
	}
	ob_start();
	get_template_part( 'template-parts/acquisition/post-capture-band' );

	return (string) ob_get_clean();
}

/**
 * The editor-placed marker: `[bhp_capture_band]`.
 *
 * ⭐ A REAL SHORTCODE, EXPANDED BY WORDPRESS ITSELF at `do_shortcode`'s priority
 *    11 on `the_content`. That is the whole reason it is a shortcode and not a
 *    string this theme searches for and replaces: core removes the marker and
 *    substitutes the markup, so THIS FILE never performs a replacement on post
 *    content and the §26 insert-only property of the whole path is preserved.
 *
 * @return string
 */
function bhp_blog_capture_band_shortcode() {
	if ( ! bhp_blog_template_active() ) {
		return '';
	}

	return bhp_blog_capture_band_html();
}
add_shortcode( 'bhp_capture_band', 'bhp_blog_capture_band_shortcode' );

/**
 * Inject the band into the rendered post body.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ STANDING RULES §26 — INSERT-ONLY, exactly as `bhp_blog_inject_midcapture()`
 * ═══════════════════════════════════════════════════════════════════════════
 * The two pilot posts carry eleven and one live Amazon affiliate anchors on
 * Andrew's own Associates code. This function never rewrites content: it splits
 * it at a tag boundary and rejoins it around the band. There is no
 * `preg_replace`, no `str_replace`, no DOM round-trip and no re-serialisation in
 * this path, so every original byte appears in the output in its original order,
 * and the band itself contains no Amazon URL. ⛔ The rendered before/after
 * anchor diff is still RUN on staging — §26.6 is explicit that a count which was
 * not actually run is a fabricated check.
 *
 * ⭐ PRIORITY 11 — BEFORE THE RAIL'S 12, AND THE ORDER IS LOAD-BEARING. The band
 *    emits NO `<h2>`, so the rail's heading arithmetic at priority 12 sees the
 *    same heading tokens it would have seen on the untouched article. Running
 *    the band LAST instead would have meant measuring an article that already
 *    contained the mid capture's `<h2>`, which would have shifted the band's own
 *    anchor by one section on exactly the two posts that carry that panel.
 *
 * ⛔ THE FOUR GUARDS mirror the other two injectors for the same reasons:
 *    `the_content` is also called by feeds, REST, SEO plugins building meta
 *    descriptions, and any block rendering a post excerpt.
 *
 * @param string $content Rendered content.
 * @return string
 */
function bhp_blog_inject_capture_band( $content ) {
	static $done = false;

	if ( ! bhp_blog_capture_band_enabled() ) {
		return $content;
	}
	if ( $done ) {
		return $content;
	}
	if ( ! bhp_blog_template_active() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'bhp-capture-band' ) ) {
		return $content; // An editor placed the marker; that placement wins.
	}

	$offset = bhp_blog_capture_band_offset( $content );
	if ( null === $offset ) {
		return $content;
	}

	$band = bhp_blog_capture_band_html();
	if ( '' === $band ) {
		return $content;
	}

	$done = true;

	return substr( $content, 0, $offset ) . $band . substr( $content, $offset );
}
add_filter( 'the_content', 'bhp_blog_inject_capture_band', 11 );

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
