<?php
/**
 * Brave Hearts Publishing — thank-you pages are noindex and out of the sitemap.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.292 (2026-08-26, `CYCLE166-CX-CAPTURE-REPAIR`) — THE THREE
 *    THANK-YOU PAGES LEAVE THE INDEX AND THE SITEMAP.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE THREE PAGES, VERIFIED LIVE ON STAGING 2026-08-26 by `wp post list`
 *    over SSH — enumerated, not assumed:
 *      349  adventure-kit-thank-you
 *      341  mariana-guide-thank-you
 *      614  gift-guide-thank-you
 *    ⚠️ A FOURTH TEMPLATE EXISTS — `page-explorer-passport-thank-you.php` —
 *    but NO PUBLISHED PAGE USES IT on either environment. It is therefore
 *    deliberately absent from the slug list below rather than added
 *    "for completeness"; the list describes pages that exist, and a slug
 *    for a page nobody can reach would be a claim this pass cannot support.
 *    If that page is ever published, add its slug here.
 *
 * ⛔ WHY THEY MUST NOT BE INDEXED. A thank-you page is the inside of a
 *    funnel. Indexed, it (a) competes with the landing page that is
 *    supposed to rank, (b) hands the deliverable to anyone arriving from
 *    a search result without ever converting, and (c) — the reason this is
 *    urgent rather than tidy — invites the crawler traffic that was
 *    MANUFACTURING CONVERSION EVENTS. See `inc/conversion-token.php` for
 *    the measured production evidence. These two fixes are halves of one
 *    repair: the token stops the event firing, and this stops the crawlers
 *    being invited in the first place.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ⛔ WHY THIS WRITES POST META AND NOT ONLY A FILTER — read before "simplifying"
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The obvious implementation is a `rank_math/frontend/robots` filter alone.
 * ⭐ IT WOULD NOT REMOVE THE PAGES FROM THE SITEMAP, and the sitemap half is
 *    most of the point.
 *
 * VERIFIED BY READING THE INSTALLED PLUGIN, not by assumption — Rank Math
 * 1.0.272, `includes/modules/sitemap/providers/class-post-type.php` lines
 * ~300-306 and ~417-423. The sitemap provider builds its own RAW SQL:
 *
 *     LEFT JOIN {$wpdb->postmeta} AS pm
 *          ON ( p.ID = pm.post_id AND pm.meta_key = 'rank_math_robots' )
 *     ... ( pm.meta_key = 'rank_math_robots'
 *           AND pm.meta_value NOT LIKE '%noindex%' ) OR ...
 *
 * That query never loads a post object and never runs a PHP filter. It
 * reads the STORED `rank_math_robots` postmeta directly. So the stored meta
 * is the ONLY thing that takes a page out of the XML sitemap, and the
 * filter below is a backstop for the rendered `<meta name="robots">` tag,
 * not the mechanism.
 *
 * ⚠️ CONSEQUENCE FOR DEPLOYMENT, AND IT IS AN ANDREW-VISIBLE ONE: this is
 *    DATA, not code. It does not travel inside the theme ZIP. The sync
 *    below therefore runs once per theme version on `admin_init` so that
 *    installing the theme brings whichever environment it lands on into
 *    line, with no separate manual data step to forget. On production that
 *    means the approved deploy itself writes three postmeta rows.
 *    ⛔ THAT IS A PRODUCTION DATA WRITE. It is called out here, and in the
 *       handoff, so it is approved knowingly rather than discovered later.
 *       It touches NO WooCommerce record, NO product, NO price and NO
 *       commerce configuration — three SEO postmeta rows on three
 *       non-commercial pages, and nothing else.
 *
 * ⭐ IT IS IDEMPOTENT AND NON-DESTRUCTIVE. It writes only when the stored
 *    value does not already contain `noindex`, so it cannot thrash, and it
 *    never removes a directive somebody set by hand — it merges `noindex`
 *    into whatever is already there.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The thank-you page slugs that must never be indexed.
 *
 * Slugs rather than IDs, deliberately: the same three pages carry DIFFERENT
 * post IDs on staging and production, and hardcoding an ID is precisely the
 * class of bug this codebase has been bitten by before (the composite
 * attachment was 4570 on staging and 616 on production).
 */
function bhp_noindex_thankyou_slugs() {
	return apply_filters(
		'bhp_noindex_thankyou_slugs',
		array(
			'adventure-kit-thank-you',
			'mariana-guide-thank-you',
			'gift-guide-thank-you',
		)
	);
}

/**
 * Resolve those slugs to the post IDs on THIS environment.
 */
function bhp_noindex_thankyou_ids() {
	$ids = array();

	foreach ( bhp_noindex_thankyou_slugs() as $slug ) {
		$page = get_page_by_path( sanitize_title( $slug ) );
		if ( $page && $page->post_status === 'publish' ) {
			$ids[] = (int) $page->ID;
		}
	}

	return $ids;
}

/**
 * Is the post currently being rendered one of them?
 */
function bhp_is_noindex_thankyou_page( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( ! $post_id ) {
		return false;
	}

	$slug = get_post_field( 'post_name', $post_id );
	if ( ! $slug ) {
		return false;
	}

	return in_array( $slug, bhp_noindex_thankyou_slugs(), true );
}

/**
 * BACKSTOP 1 — Rank Math's own rendered robots tag.
 *
 * Runs late (99) so it is the last word. Rank Math passes an associative
 * array keyed by directive; setting `noindex` and dropping `index` is the
 * shape it expects.
 */
add_filter(
	'rank_math/frontend/robots',
	function ( $robots ) {
		if ( ! is_page() || ! bhp_is_noindex_thankyou_page() ) {
			return $robots;
		}

		if ( ! is_array( $robots ) ) {
			$robots = array();
		}

		unset( $robots['index'] );
		$robots['noindex'] = 'noindex';

		// `follow` is left intact on purpose. These pages link onward to the
		// Complete Collection, and there is no reason to throw that link
		// equity away — the requirement is "do not INDEX this page", not
		// "pretend its outbound links do not exist".
		return $robots;
	},
	99
);

/**
 * BACKSTOP 2 — WordPress core's own `wp_robots` (5.7+).
 *
 * Belt and braces for any request path where Rank Math's frontend does not
 * run (a REST render, a theme preview, Rank Math deactivated during
 * maintenance). Cheap, and it removes an entire failure mode.
 */
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( ! is_page() || ! bhp_is_noindex_thankyou_page() ) {
			return $robots;
		}

		unset( $robots['index'] );
		$robots['noindex'] = true;

		return $robots;
	},
	99
);

/**
 * THE MECHANISM — sync the stored `rank_math_robots` postmeta.
 *
 * ⭐ THIS is what actually removes the pages from the XML sitemap, for the
 *    raw-SQL reason documented at the top of this file.
 *
 * Runs on `admin_init` rather than on every front-end request: it is a
 * migration, not a runtime concern, and the front end must not pay three
 * `get_page_by_path()` lookups plus a possible write on every page view.
 * A version stamp keeps it to once per theme version.
 */
function bhp_sync_thankyou_robots_meta() {
	$version = defined( 'BHP_THEME_VERSION' ) ? BHP_THEME_VERSION : wp_get_theme()->get( 'Version' );
	$stamp   = get_option( 'bhp_thankyou_robots_synced' );

	if ( $stamp === $version ) {
		return;
	}

	foreach ( bhp_noindex_thankyou_ids() as $post_id ) {
		$existing = get_post_meta( $post_id, 'rank_math_robots', true );

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		// ⭐ MERGE, NEVER REPLACE. If somebody has already set `noarchive` or
		//    `nosnippet` by hand in the Rank Math panel, it survives.
		if ( in_array( 'noindex', $existing, true ) ) {
			continue;
		}

		$existing[] = 'noindex';

		// `index` and `noindex` together is contradictory; drop the former.
		$existing = array_values(
			array_filter(
				$existing,
				static function ( $directive ) {
					return $directive !== 'index';
				}
			)
		);

		update_post_meta( $post_id, 'rank_math_robots', $existing );
	}

	update_option( 'bhp_thankyou_robots_synced', $version, false );

	// The sitemap is cached; a stale cache would keep serving the URLs we
	// just excluded. Rank Math exposes its own invalidation for exactly this.
	if ( class_exists( '\\RankMath\\Sitemap\\Cache' ) && method_exists( '\\RankMath\\Sitemap\\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
}
add_action( 'admin_init', 'bhp_sync_thankyou_robots_meta' );

/**
 * WP-CLI entry point, so a deploy can force the sync without waiting for
 * somebody to open wp-admin.
 *
 * `wp eval 'bhp_force_thankyou_robots_sync();' --user=1`
 */
function bhp_force_thankyou_robots_sync() {
	delete_option( 'bhp_thankyou_robots_synced' );
	bhp_sync_thankyou_robots_meta();

	return bhp_noindex_thankyou_ids();
}
