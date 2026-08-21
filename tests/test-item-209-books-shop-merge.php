<?php
/**
 * Brave Hearts — CARRIER ITEM 209: /books/ MERGES INTO /shop/, AND /shop/
 * OPENS SOONER.
 *
 * Theme 1.19.285 / plugin 1.8.66 (plugin unchanged). `CYCLE165-LD-FOLD-AND-MERGE`.
 *
 * Andrew Signore, carrier item 209, 2026-08-21 (⚠️ RELAYED through
 * `chief-of-staff`, ⛔ NOT witnessed first-hand by the agent that wrote this
 * suite — recorded as relayed per Standing Rules §9.2 rule 2).
 *
 * Run via WP-CLI (⚠ THE `--url` FLAG IS NOT OPTIONAL — without it the fetched
 * documents come from the wrong environment; `CYCLE165-LD-53`):
 *
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-item-209-books-shop-merge.php \
 *     --url=https://staging2.braveheartspublishing.com --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   §1  THE REDIRECT   — /books/ answers 301, to /shop/, with the query string
 *                        intact, and the resolver FAILS CLOSED when it cannot
 *                        name a destination.
 *   §2  THE NAV        — the entry's href is /shop/ and its LABEL is byte-for-
 *                        byte what it was: the two stacked spans and the
 *                        explicit aria-label. This is the brief's own
 *                        constraint and it is asserted as a PAIR — href moved,
 *                        label did not — because checking only the href would
 *                        pass a build that silently unstacked the label.
 *   §3  NO ORPHAN      — the item-118 failure shape. The collection purchase
 *                        path that /books/ carried is still reachable from
 *                        /shop/, and /complete-collection/ still renders the
 *                        founder's protected free-shipping string.
 *   §4  THE SITEMAP    — a URL that 301s never enters the sitemap (1.19.272
 *                        rule), and the FLOOR that rule must never gut still
 *                        survives.
 *   §5  THE SUBTRACTION RECORD — what LEAVES the site with /books/, counted in
 *                        the served documents rather than asserted, so the
 *                        cost of the merge is a measured line in a test run
 *                        and not a sentence in a report nobody re-runs.
 *   §6  THE FOLD       — the item-209 limb-1 rules are present in the SERVED
 *                        artefact, scoped to `body.woocommerce-shop`, and
 *                        remove nothing and resize no type.
 *   §7  THE MANIFEST   — the finding that there was no /books/ row to annotate.
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED:
 *    §6 reads CSS TEXT. It does NOT measure a pixel. The before/after fold
 *    numbers at 390 and 1440 were measured separately in a real browser at an
 *    asserted `window.innerWidth`, and a green run HERE IS NOT THAT
 *    MEASUREMENT and must never be reported as one.
 *    §2 reads server-rendered menu markup. It does not prove a tap lands
 *    anywhere — that was done in a browser and is recorded in the release
 *    record.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run through WP-CLI: wp eval-file …\n" );
	exit( 1 );
}

$failures = array();

/**
 * Record one assertion.
 *
 * @param bool   $ok       Result.
 * @param string $label    Message.
 * @param array  $failures By reference.
 */
function bhp_i209_assert( $ok, $label, array &$failures ) {
	if ( $ok ) {
		echo "PASS: {$label}\n";
		return;
	}
	echo "FAIL: {$label}\n";
	$failures[] = $label;
}

/**
 * Fetch a document WITHOUT following redirects, so a 301 is visible as a 301.
 *
 * ⛔ `wp_remote_get()` follows up to five redirects by default. Every suite in
 *    this repository that fetches /books/ therefore sees a 200 and the /shop/
 *    body after this release, which is exactly why those suites are being
 *    retired against a CODE check rather than an HTTP one — an HTTP check
 *    would have quietly reported success.
 *
 * @param string $url URL.
 * @return array{code:int,location:string,body:string}
 */
function bhp_i209_head( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false, 'redirection' => 0 ) );
	if ( is_wp_error( $res ) ) {
		return array( 'code' => 0, 'location' => '', 'body' => '' );
	}
	return array(
		'code'     => (int) wp_remote_retrieve_response_code( $res ),
		'location' => (string) wp_remote_retrieve_header( $res, 'location' ),
		'body'     => (string) wp_remote_retrieve_body( $res ),
	);
}

/**
 * Fetch a rendered document, following redirects. '' on any failure.
 *
 * @param string $url URL.
 * @return string
 */
function bhp_i209_get( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** Path-only, untrailingslashed. */
function bhp_i209_path( $url ) {
	return untrailingslashit( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) );
}

echo "Environment: " . home_url( '/' ) . "\n";
echo "Theme:       " . wp_get_theme()->get( 'Version' ) . "\n";

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	fwrite( STDERR, "Cannot continue: WooCommerce is inactive.\n" );
	exit( 1 );
}

/* ═══════════════════════════════════════════════════════════════════════════
   §1 · THE REDIRECT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §1 — /books/ ANSWERS A PERMANENT REDIRECT TO /shop/ ===\n";

bhp_i209_assert(
	function_exists( 'bhp_redirect_books_to_shop' ),
	'1.1 bhp_redirect_books_to_shop() exists (item 209 limb 2)',
	$failures
);
bhp_i209_assert(
	false !== has_action( 'template_redirect', 'bhp_redirect_books_to_shop' ),
	'1.2 …and it is HOOKED on template_redirect — a function nobody calls is not a redirect',
	$failures
);
/*
 * ⛔ PRIORITY 1, NOT 10, AND THE REASON IS ON RECORD ELSEWHERE IN THIS THEME.
 *    WordPress core's `redirect_canonical` / `redirect_guess_404_permalink`
 *    run on the DEFAULT template_redirect priority and exit before a
 *    later-registered hook gets a turn — the race
 *    `bhp_redirect_legacy_bookvault_mariana_slug()` documents losing once.
 */
bhp_i209_assert(
	1 === (int) has_action( 'template_redirect', 'bhp_redirect_books_to_shop' ),
	'1.3 …at priority 1, so it wins the race against core canonical/404-guess handling',
	$failures
);

$i209_dest = function_exists( 'bhp_books_merge_destination' ) ? bhp_books_merge_destination() : '';
bhp_i209_assert( '' !== $i209_dest, '1.4 the merge destination resolves', $failures );
bhp_i209_assert(
	'' !== $i209_dest && bhp_i209_path( $i209_dest ) !== bhp_i209_path( home_url( '/books/' ) ),
	'1.5 …and it is NOT /books/ itself — the fail-closed guard against a redirect loop',
	$failures
);
bhp_i209_assert(
	'' !== $i209_dest && bhp_i209_path( $i209_dest ) === bhp_i209_path( wc_get_page_permalink( 'shop' ) ),
	sprintf( '1.6 …and it IS the live WooCommerce Shop page (%s)', bhp_i209_path( $i209_dest ) ),
	$failures
);

$i209_hit = bhp_i209_head( home_url( '/books/' ) );
bhp_i209_assert(
	301 === $i209_hit['code'],
	sprintf( '1.7 ⭐ /books/ answers HTTP 301 — PERMANENT, not 302 (got %d)', $i209_hit['code'] ),
	$failures
);
bhp_i209_assert(
	'' !== $i209_hit['location'] && bhp_i209_path( $i209_hit['location'] ) === bhp_i209_path( $i209_dest ),
	sprintf( '1.8 …and Location: points at %s (got %s)', bhp_i209_path( $i209_dest ), bhp_i209_path( $i209_hit['location'] ) ?: 'nothing' ),
	$failures
);

/*
 * ⭐ THE QUERY STRING SURVIVES, AND THIS IS NOT A NICETY.
 *    A school-visit session is carried as `?bhp_visit=…`. A redirect that
 *    dropped it would un-flag a parent mid-journey and show them a shipping
 *    promise instead of the hand-delivery sentence — the FD-505/FD-506 path
 *    the protected-elements suite explicitly refuses to break.
 */
$i209_q = bhp_i209_head( home_url( '/books/' ) . '?bhp_visit=suite-209&utm_source=suite' );
bhp_i209_assert(
	301 === $i209_q['code'] && false !== strpos( $i209_q['location'], 'bhp_visit=suite-209' ),
	'1.9 ⭐ the query string travels through the 301 (bhp_visit survives — FD-505/FD-506)',
	$failures
);
bhp_i209_assert(
	false !== strpos( $i209_q['location'], 'utm_source=suite' ),
	'1.10 …and so does the UTM, so attribution is not destroyed by the merge',
	$failures
);

/*
 * ⛔ THE TEMPLATE IS STILL ON DISK. A merge that deleted page-books.php would
 *    make the reversal a rebuild instead of removing one add_action.
 */
bhp_i209_assert(
	is_readable( get_stylesheet_directory() . '/page-books.php' ),
	'1.11 page-books.php is still on disk — the merge is reversible in one hunk',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §2 · THE NAV — HREF MOVED, LABEL DID NOT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §2 — THE \"ADVENTURE BOOKS\" ENTRY POINTS AT /shop/, LABEL UNTOUCHED ===\n";

bhp_i209_assert(
	false !== has_filter( 'wp_nav_menu_objects', 'bhp_adventure_books_nav_target_shop' ),
	'2.1 the retarget filter is registered on wp_nav_menu_objects',
	$failures
);
/*
 * ⚠ PRIORITY 25 IS THE CONTRACT, NOT A DETAIL. The label-stacking filter runs
 *   at 20 and the retarget must run AFTER it. (The label filter also matches
 *   the /shop/ path as of 1.19.285, so the ordering is belt-and-braces rather
 *   than the only thing holding the label on — but it is still the contract,
 *   and a silent reorder should name itself here.)
 */
bhp_i209_assert(
	25 === (int) has_filter( 'wp_nav_menu_objects', 'bhp_adventure_books_nav_target_shop' ),
	'2.2 …at priority 25, AFTER the label-stacking filter at 20',
	$failures
);

$i209_nav = wp_nav_menu(
	array(
		'theme_location' => 'primary',
		'container'      => false,
		'echo'           => false,
		'fallback_cb'    => 'bhp_fallback_menu',
	)
);
$i209_nav = is_string( $i209_nav ) ? $i209_nav : '';
bhp_i209_assert( '' !== $i209_nav, '2.3 the primary menu rendered', $failures );

if ( '' !== $i209_nav ) {
	$i209_item = '';
	if ( preg_match( '#<li[^>]*menu-item--adventure-books[^>]*>.*?</li>#s', $i209_nav, $m ) ) {
		$i209_item = $m[0];
	}
	bhp_i209_assert(
		'' !== $i209_item,
		'2.4 the entry is present and still carries `menu-item--adventure-books`',
		$failures
	);

	if ( '' !== $i209_item ) {
		$i209_href = '';
		if ( preg_match( '#href="([^"]+)"#', $i209_item, $hm ) ) {
			$i209_href = html_entity_decode( $hm[1] );
		}
		bhp_i209_assert(
			'' !== $i209_href && bhp_i209_path( $i209_href ) === bhp_i209_path( $i209_dest ),
			sprintf( '2.5 ⭐ its href is %s (got %s)', bhp_i209_path( $i209_dest ), bhp_i209_path( $i209_href ) ?: 'nothing' ),
			$failures
		);
		bhp_i209_assert(
			'' !== $i209_href && bhp_i209_path( $i209_href ) !== bhp_i209_path( home_url( '/books/' ) ),
			'2.6 …and it is no longer /books/, so the nav does not spend a hop on a 301',
			$failures
		);

		/* ⭐ THE LABEL HALF OF THE PAIR. Both stacked spans, in order, plus the
		     explicit accessible name that two block spans would otherwise lose. */
		bhp_i209_assert(
			1 === preg_match(
				'#<span class="site-nav__label-line">Adventure</span><span class="site-nav__label-line">Books</span>#',
				$i209_item
			),
			'2.7 ⭐ LABEL UNCHANGED: the two stacked spans render exactly as before',
			$failures
		);
		bhp_i209_assert(
			false !== strpos( $i209_item, 'aria-label="Adventure Books"' ),
			'2.8 …and the explicit accessible name is still "Adventure Books"',
			$failures
		);
		bhp_i209_assert(
			false === stripos( $i209_item, 'shop</span>' ) && false === stripos( $i209_item, '>Shop<' ),
			'2.9 …and the word "Shop" did NOT leak into the label with the href',
			$failures
		);
	}

	/*
	 * ⛔ EXACTLY ONE. A retarget that matched both the old and the new path
	 *    without de-duplicating would be invisible here unless it is counted.
	 */
	bhp_i209_assert(
		1 === preg_match_all( '#menu-item--adventure-books#', $i209_nav ),
		sprintf( '2.10 exactly ONE Adventure Books entry (found %d)', preg_match_all( '#menu-item--adventure-books#', $i209_nav ) ),
		$failures
	);
	bhp_i209_assert(
		0 === preg_match_all( '#href="[^"]*' . preg_quote( bhp_i209_path( home_url( '/books/' ) ), '#' ) . '/?"#', $i209_nav ),
		'2.11 no primary-nav entry still points at /books/',
		$failures
	);
}

/*
 * The dormant fallback carries the same destination. It does not fire while a
 * stored menu is assigned — asserted, not assumed, so "dormant" stays a fact.
 */
bhp_i209_assert(
	'' !== ( $i209_loc = get_nav_menu_locations()['primary'] ?? '' ) && 0 < (int) $i209_loc,
	'2.12 a stored menu IS assigned to `primary`, so bhp_fallback_menu() is dormant',
	$failures
);
ob_start();
bhp_fallback_menu();
$i209_fb = (string) ob_get_clean();
bhp_i209_assert(
	false === strpos( $i209_fb, bhp_i209_path( home_url( '/books/' ) ) . '/' ),
	'2.13 …and the dormant fallback no longer names /books/ either',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
   §3 · NO ORPHANED CTA — THE ITEM-118 FAILURE SHAPE
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §3 — THE COLLECTION PURCHASE PATH SURVIVES THE MERGE ===\n";

$i209_shop = bhp_i209_get( $i209_dest );
bhp_i209_assert( '' !== $i209_shop, '3.1 /shop/ renders (HTTP 200, non-empty)', $failures );

if ( '' !== $i209_shop ) {
	/*
	 * ⛔ THE ONE THING THE MERGE MAY NOT COST. /books/ carried the Complete
	 *    Collection band, whose outbound control went to /complete-collection/.
	 *    The item-206 Complete Collection card on /shop/ carries the same
	 *    destination. If that ever stops being true, the merge has orphaned a
	 *    CTA and this assertion — not a founder on his own phone — is what says
	 *    so. (Item 118 is the incident that makes this the priority it is.)
	 */
	bhp_i209_assert(
		false !== strpos( $i209_shop, '/complete-collection/' ),
		'3.2 ⭐ /shop/ still routes to /complete-collection/ — the band\'s destination is not orphaned',
		$failures
	);
	bhp_i209_assert(
		false !== strpos( $i209_shop, 'bhp-shop-collection-card' ),
		'3.3 …and it is the item-206 Complete Collection CARD carrying it',
		$failures
	);
	bhp_i209_assert(
		false !== strpos( $i209_shop, 'woocommerce-loop-product__title' ),
		'3.4 the product grid renders, so the merged door shows the books it promises',
		$failures
	);
}

/*
 * ⭐ THE PROTECTED ROW IS UNAFFECTED, AND THAT IS CHECKED RATHER THAN ASSUMED.
 *    The founder's free-shipping string (carrier item 186, casing confirmed at
 *    item 192) is a PROTECTED ELEMENTS MANIFEST row scoped to
 *    /complete-collection/. It also rendered once on /books/, which the merge
 *    retires. This proves the MANIFEST row itself did not move.
 *    ⛔ Skipped, announced, on a school-visit session — a flagged parent
 *      correctly sees hand-delivery instead, and asserting presence there would
 *      fail a correctly behaving journey.
 */
$i209_visit = function_exists( 'bhp_school_visit_use_delivery_framing' ) && bhp_school_visit_use_delivery_framing();
if ( $i209_visit ) {
	echo "SKIP: 3.5 — school-visit session active; the free-shipping row is correctly replaced by hand-delivery\n";
} else {
	$i209_coll = bhp_i209_get( home_url( '/complete-collection/' ) );
	bhp_i209_assert( '' !== $i209_coll, '3.5a /complete-collection/ renders', $failures );
	if ( '' !== $i209_coll ) {
		$i209_fs = substr_count( $i209_coll, 'FREE Shipping on the complete collection or 3 or more books purchased' );
		bhp_i209_assert(
			$i209_fs >= 2,
			sprintf( '3.5 ⭐ PROTECTED: the founder\'s free-shipping string still renders %d times on /complete-collection/ (manifest floor 2)', $i209_fs ),
			$failures
		);
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   §4 · THE SITEMAP — A URL THAT 301s NEVER ENTERS IT
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §4 — /books/ LEAVES THE SITEMAP, THE FLOOR SURVIVES ===\n";

if ( ! function_exists( 'bhp_seo_exclude_redirected_from_sitemap' ) || ! function_exists( 'bhp_seo_theme_redirected_paths' ) ) {
	echo "SKIP: §4 — inc/seo-hygiene.php is not loaded\n";
} else {
	$i209_entry = static function ( $path ) {
		return array( 'loc' => home_url( $path ), 'mod' => '2026-08-21T00:00:00+00:00' );
	};

	bhp_i209_assert(
		in_array( bhp_i209_path( home_url( '/books/' ) ), bhp_seo_theme_redirected_paths(), true ),
		'4.1 /books/ is registered in bhp_seo_theme_redirected_paths()',
		$failures
	);
	bhp_i209_assert(
		array() === bhp_seo_exclude_redirected_from_sitemap( $i209_entry( '/books/' ), 'post', null ),
		'4.2 ⭐ …and the sitemap filter therefore EXCLUDES it (1.19.272 rule)',
		$failures
	);
	/*
	 * ⛔ THE FLOOR. The whole risk of adding a path to that registry is gutting
	 *    the sitemap by over-matching. /shop/ in particular is the merge's own
	 *    destination and must be advertised harder than before, not less.
	 */
	foreach ( array( '/', '/shop/', '/complete-collection/', '/blog/', '/teachers/', '/about/' ) as $i209_keep ) {
		$i209_kept = bhp_seo_exclude_redirected_from_sitemap( $i209_entry( $i209_keep ), 'post', null );
		bhp_i209_assert(
			is_array( $i209_kept ) && ! empty( $i209_kept['loc'] ),
			"4.3 sitemap FLOOR: {$i209_keep} is NOT excluded",
			$failures
		);
	}
	bhp_i209_assert(
		in_array( bhp_i209_path( home_url( '/teachers-guide/' ) ), bhp_seo_theme_redirected_paths(), true ),
		'4.4 the pre-existing /teachers-guide/ registration survives the addition',
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
   §5 · THE SUBTRACTION RECORD — WHAT THE MERGE COSTS, COUNTED
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §5 — WHAT LEAVES THE SITE WITH /books/ (RECORDED, NOT ASSERTED) ===\n";

/*
 * ⛔⛔ THESE ARE DELIBERATELY NOT ASSERTIONS, AND THE REASON MATTERS.
 *
 *    Asserting "the collection band is ABSENT from /shop/" would LOCK the
 *    subtraction in: the day Andrew asks for the band back on the merged door,
 *    a correct build would fail a gate and somebody would delete the gate.
 *    Asserting "the band is PRESENT" would fail today's correct build.
 *
 *    So this section COUNTS and PRINTS. It cannot fail. Its job is to make the
 *    cost of item 209 a line in every future run of this suite instead of a
 *    sentence in one report — because the item-118 regression was found by the
 *    founder on his own production site, months of green assertions later.
 */
$i209_markers = array(
	'bhp-collection-band'                                                   => 'the Complete Collection band (format toggle + two-click checkout)',
	'Start with Book 1'                                                     => 'the single hero primary',
	'look-inside'                                                           => 'the Look Inside gallery',
	'FREE Shipping on the complete collection or 3 or more books purchased' => 'the founder\'s free-shipping sentence (item 186)',
);
foreach ( $i209_markers as $i209_needle => $i209_what ) {
	printf(
		"RECORD: /shop/ carries %d × %s\n",
		substr_count( $i209_shop, $i209_needle ),
		$i209_what
	);
}
echo "RECORD: the four above rendered on /books/ before item 209. Their retirement is\n";
echo "RECORD: the measured cost of the merge and is reported to the Chief of Staff\n";
echo "RECORD: as a subtraction, not absorbed into a success summary.\n";

/* ═══════════════════════════════════════════════════════════════════════════
   §6 · THE FOLD — LIMB 1
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §6 — THE ABOVE-FOLD COMPRESSION IS IN THE SERVED ARTEFACT ===\n";

$i209_css_url = get_stylesheet_directory_uri() . '/style.min.css';
$i209_css     = bhp_i209_get( $i209_css_url );
if ( '' === $i209_css ) {
	$i209_css = bhp_i209_get( get_stylesheet_directory_uri() . '/style.css' );
}
bhp_i209_assert( '' !== $i209_css, '6.1 the stylesheet artefact was fetched from the server', $failures );

if ( '' !== $i209_css ) {
	bhp_i209_assert(
		false !== strpos( $i209_css, 'Version: ' . wp_get_theme()->get( 'Version' ) ),
		'6.2 the artefact was rebuilt for THIS release',
		$failures
	);
	/*
	 * ⛔ SCOPE FIRST. The one real risk in this limb is collateral: the empty
	 *    products-header padding also exists on product-category and search
	 *    archives, where the header is NOT empty. Every rule below is scoped to
	 *    `body.woocommerce-shop`, and that is asserted before anything else.
	 */
	/*
	 * ⛔ MATCHED ON WHITESPACE-STRIPPED BYTES, AND THE FIRST RUN IS WHY.
	 *    `tools/build-css.mjs` strips COMMENTS, not whitespace — the artefact
	 *    still reads `selector { prop: value; }` with every space intact. Five
	 *    needles written in minifier shorthand reported a correct, freshly
	 *    built stylesheet as five missing rules. Normalising both sides means
	 *    this cannot break again the day the builder does start collapsing
	 *    whitespace, which is the more useful fix than correcting the spacing.
	 */
	$i209_css_flat = preg_replace( '/\s+/', '', $i209_css );
	foreach ( array(
		'body.woocommerce-shop.woocommerce-products-header{padding-block:0'   => 'the empty products header stops padding nothing (64px at 390)',
		'body.woocommerce-shop.woocommerce-products-header>*{margin-block'    => '…with the rhythm moved onto its children, so a description still breathes',
		'body.woocommerce-shop.woo-expedition-shellul.products{margin-top:0'  => 'the grid stops double-counting the ordering form\'s gap',
		'body.woocommerce-shop.woo-expedition-shell{padding-top'              => 'the space the item-207 carousel vacated is closed',
		'body.woocommerce-shop.woocommerce-breadcrumb{margin-bottom'          => 'the breadcrumb gap steps down the space scale',
	) as $i209_rule => $i209_why ) {
		bhp_i209_assert(
			false !== strpos( $i209_css_flat, $i209_rule ),
			"6.3 {$i209_why}",
			$failures
		);
	}

	/*
	 * ⛔⛔ THE BRIEF'S TWO PROHIBITIONS, ENFORCED ON THE BLOCK ITSELF RATHER
	 *     THAN TRUSTED. "No element removed, no type-size change."
	 */
	$i209_block_at = strpos( $i209_css, 'body.woocommerce-shop .woocommerce-products-header' );
	$i209_block    = false !== $i209_block_at ? substr( $i209_css, $i209_block_at ) : '';
	bhp_i209_assert(
		'' !== $i209_block && false === strpos( $i209_block, 'display:none' ),
		'6.4 ⛔ NO ELEMENT REMOVED: the item-209 block contains no display:none',
		$failures
	);
	bhp_i209_assert(
		'' !== $i209_block && 0 === preg_match( '/body\.woocommerce-shop[^{}]*\{[^{}]*font-size/', $i209_block ),
		'6.5 ⛔ NO TYPE-SIZE CHANGE: no shop-scoped rule in the block sets a font-size',
		$failures
	);
}

/* ⭐ …and the elements themselves are still SERVED. §6.4 proves the CSS does
     not hide them; this proves the markup still emits them. Both are needed —
     a rule that hides nothing is no comfort if the node stopped rendering. */
if ( '' !== $i209_shop ) {
	foreach ( array(
		'woo-archive-hero'            => 'the archive hero',
		'woocommerce-breadcrumb'      => 'the breadcrumb',
		'woocommerce-products-header' => 'the products header element itself',
		'woocommerce-result-count'    => 'the result count',
		'woocommerce-ordering'        => 'the ordering select',
	) as $i209_cls => $i209_label ) {
		bhp_i209_assert(
			false !== strpos( $i209_shop, $i209_cls ),
			"6.6 still rendered on /shop/: {$i209_label}",
			$failures
		);
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   §7 · THE PROTECTED ELEMENTS MANIFEST
   ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §7 — THE MANIFEST NEEDED NO ROW EDIT, AND THAT IS A FINDING ===\n";

/*
 * ⭐ THE BRIEF ASKED FOR "/books/-scoped rows annotated as merged-under-item-209".
 *    READ, NOT ASSUMED: `bhp_pe_manifest()` has exactly four keys — home, post,
 *    product, collection. There is no `books` key and there never was, and the
 *    manifest suite never fetches /books/. So the annotation resolves to a
 *    RECORDED FINDING plus a comment in that file, exactly as item 207 resolved
 *    when it found no `shop` key. ⛔ NOT ONE MANIFEST ROW WAS TOUCHED, ADDED OR
 *    WEAKENED BY ITEM 209.
 *
 *    This section asserts that finding so it cannot silently stop being true:
 *    if somebody later adds a /books/ row, this fails and the merge gets
 *    re-examined instead of quietly breaking a protected element.
 */
$i209_pe = get_stylesheet_directory() . '/tests/test-protected-elements.php';
if ( ! is_readable( $i209_pe ) ) {
	echo "SKIP: §7 — the protected-elements suite is not on disk\n";
} else {
	/*
	 * ⛔ READ AS TEXT, NEVER `require`d. `test-protected-elements.php` is a
	 *    SCRIPT: including it would run the entire manifest suite inside this
	 *    one and then `exit()` on its own result, swallowing everything below.
	 *    The first draft of this section did exactly that.
	 */
	$i209_pe_src = (string) file_get_contents( $i209_pe );

	$i209_body = '';
	$i209_fn_at = strpos( $i209_pe_src, 'function bhp_pe_manifest()' );
	if ( false !== $i209_fn_at ) {
		$i209_body = substr( $i209_pe_src, $i209_fn_at, 40000 );
		$i209_end  = strpos( $i209_body, "\n}" );
		if ( false !== $i209_end ) {
			$i209_body = substr( $i209_body, 0, $i209_end );
		}
	}
	bhp_i209_assert( '' !== $i209_body, '7.0 bhp_pe_manifest() was located in the suite source', $failures );

	/* Section keys are the only `'<key>' => array(` at four-space indent. */
	preg_match_all( "/^\t\t'([a-z_]+)'\s*=>\s*array\(\s*$/m", $i209_body, $i209_km );
	$i209_keys = $i209_km[1] ?? array();

	bhp_i209_assert(
		! in_array( 'books', $i209_keys, true ),
		sprintf( '7.1 ⭐ the manifest has NO `books` section — nothing to annotate (sections: %s)', implode( ', ', $i209_keys ) ),
		$failures
	);
	bhp_i209_assert(
		array( 'home', 'post', 'product', 'collection' ) === $i209_keys,
		'7.2 …and its four sections are unchanged by item 209',
		$failures
	);
	bhp_i209_assert(
		false === strpos( $i209_body, '/books/' ),
		'7.3 no manifest ROW is /books/-scoped',
		$failures
	);
	bhp_i209_assert(
		false === strpos( $i209_pe_src, "bhp_pe_fetch( home_url( '/books/' )" ),
		'7.4 …and the manifest suite fetches no /books/ document, so no assertion follows the redirect',
		$failures
	);
}

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL CHECKS PASSED\n";
	exit( 0 );
}
echo count( $failures ) . " FAILURE(S):\n";
foreach ( $failures as $i209_f ) {
	echo " - {$i209_f}\n";
}
exit( 1 );
