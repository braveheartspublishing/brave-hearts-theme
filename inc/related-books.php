<?php
/**
 * Brave Hearts Publishing — the store links inside the end-of-post
 * "related books and posts" block.
 *
 * 1.19.418 (2026-09-13, `CYCLE180-LD-BUILD-418`). Founder ruling, seal 1550,
 * ruling 3 — the Sturm tactic (b), approved from
 * KB `MARKETING\SEO\EDWARD-STURM-SEO-NOTES.md` §5 T2 via
 * `CYCLE180-ADS-EDWARD-STURM-KB`.
 *
 * ⚠ EVIDENCE CLASS: RELAYED through the Chief of Staff. NOT witnessed
 *   first-hand by the session that wrote this file (Standing Rules §9.2 rule 2).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT WAS ALREADY THERE — READ THIS BEFORE ADDING A SECOND BLOCK
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The tactic asks for two things from each ranking post: a link to a weaker
 * SIBLING POST, and a link to the STORE. ⭐ MEASURED IN THE LIVE STAGING DOM
 * (real browser, `/blog/reading-level-by-grade-chart/`, 2026-09-13), not read
 * from this template:
 *
 *   · the sibling-post half ALREADY EXISTS and already works. The
 *     `.guide-continuation` block's "Related Field Notes" grid renders four
 *     registry-scored siblings, each anchored by ITS OWN TITLE. Nothing here
 *     adds, duplicates or re-orders them.
 *   · the store half DOES NOT. On that post every store link on the page
 *     resolved to `/complete-collection/` — the in-body rail, its CTA and its
 *     cover, three anchors to one destination. ⛔ NOT ONE LINK TO A PRODUCT
 *     PAGE. The reading cluster carries `book => ''` in the guide registry, so
 *     `related-content.php`'s own book link never renders for it.
 *
 * ⛔⛔ SO THIS IS AN ADDITION TO THE EXISTING BLOCK, NOT A NEW BLOCK, AND THAT
 *     IS A FOUNDER-DRIVEN DECISION RATHER THAN A TIDINESS ONE. Andrew's
 *     2026-08-31 ruling (quoted in full at `template-parts/guides/related-
 *     content.php`) removed a second end-of-post box for being redundant with
 *     the one above it: *"There is Big redundancy on the blog pages!"*. A new
 *     "related books and posts" aside placed under a block that already lists
 *     related posts would re-create precisely that defect, one cycle after it
 *     was fixed. The store links go INSIDE the block that is already there.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ NO HARD-CODED IDS, AND NO SECOND RESOLVER
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Both halves come from functions that already exist and are already tested:
 *
 *   `bhp_blog_rail_adventure()`  — the post → adventure resolver, whose
 *                                  precedence is registry `book`, registry
 *                                  `destination`, assigned category, then the
 *                                  post's own title. Every limb is a curated
 *                                  editorial signal; there is no body-text limb.
 *   `bhp_get_series_adventures()` — the adventures themselves, with each
 *                                  title and `primary_url` resolved from LIVE
 *                                  PRODUCT RECORDS.
 *
 * ⛔ NO PRODUCT ID, SKU, SLUG OR URL IS TYPED IN THIS FILE. If a title changes
 *    in WooCommerce, the anchor text changes with it, because the anchor text
 *    IS the product's own title — which is also the ruling's constraint.
 *
 * ⭐ THE SERIES CASE IS THE POINT, NOT A FALLBACK. When the resolver names one
 *    adventure (the Everest and Mariana science posts), that one book is
 *    linked. When it names none — the reading cluster, which is where the
 *    ranking posts actually are — ALL THREE adventures are linked, because a
 *    post about reading levels genuinely relates to the whole series and not to
 *    one title. That turns the site's strongest pages from three anchors
 *    pointing at one collection page into anchors pointing at the product pages
 *    themselves, which is the whole of tactic (b).
 *
 * ⛔ AN ADVENTURE WITH NO RESOLVED `primary_url` IS SKIPPED, NOT LINKED TO A
 *    GUESS. `bhp_get_series_adventures()` leaves `primary_url` empty when no
 *    published product backs the adventure. Substituting `/books/` there would
 *    print a link whose text is a book title and whose destination is not that
 *    book. An absent link is a non-event; a wrong one is a false promise.
 *
 * ⛔ NO NEW CUSTOMER-FACING SENTENCE IS CREATED HERE. The ruling allows link
 *    text (which is the target's own title) and ONE neutral heading, and only
 *    from strings the theme already has approved. "Books in this series" and
 *    "Related reading" were both checked against the tree and NEITHER EXISTS as
 *    an approved rendered string — the only occurrence of the latter is a line
 *    of outline text inside `class-bhp-content-brief-generator.php`, which is a
 *    brief template, not approved copy. So the heading is taken from
 *    `bhp_blog_rail_eyebrow()`, which is approved, already rendered on this
 *    exact surface, and literally names a list of books. ⚠ RECORDED, NOT
 *    HIDDEN: on a series-resolved post the in-body rail already prints that
 *    same phrase once, so the page now carries it twice, roughly a screen and a
 *    half apart. That is a cosmetic repeat, it is the cost of inventing no new
 *    copy, and `bhp_related_books_heading` changes it in one line the moment
 *    Andrew approves a wording of his own.
 *
 * @package brave-hearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The store destinations for a post's end-of-article block.
 *
 * @param WP_Post|int|null $post Post.
 * @return array<int,array{key:string,title:string,url:string}> Possibly empty.
 */
function bhp_related_books_for_post( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || 'post' !== $post->post_type || ! function_exists( 'bhp_get_series_adventures' ) ) {
		return array();
	}

	$adventures = bhp_get_series_adventures();
	if ( ! $adventures ) {
		return array();
	}

	$key  = function_exists( 'bhp_blog_rail_adventure' ) ? bhp_blog_rail_adventure( $post ) : '';
	$keys = ( $key && isset( $adventures[ $key ] ) ) ? array( $key ) : array_keys( $adventures );

	$books = array();
	foreach ( $keys as $adventure_key ) {
		$adventure = $adventures[ $adventure_key ] ?? array();
		$title     = trim( (string) ( $adventure['title'] ?? '' ) );
		$url       = trim( (string) ( $adventure['primary_url'] ?? '' ) );

		// See the docblock: no title or no live product URL means no link.
		if ( '' === $title || '' === $url ) {
			continue;
		}
		$books[] = array(
			'key'   => (string) $adventure_key,
			'title' => $title,
			'url'   => $url,
		);
	}

	return (array) apply_filters( 'bhp_related_books_for_post', $books, $post );
}

/**
 * The heading above that list. An EXISTING approved string, reused from its one
 * source — see the docblock for why no new heading was written.
 *
 * @param int $count How many books the list carries.
 * @return string
 */
function bhp_related_books_heading( $count ) {
	$kind = ( 1 === (int) $count ) ? 'book' : 'series';
	$text = function_exists( 'bhp_blog_rail_eyebrow' )
		? bhp_blog_rail_eyebrow( array( 'kind' => $kind ) )
		: '';

	return (string) apply_filters( 'bhp_related_books_heading', $text, $count );
}
