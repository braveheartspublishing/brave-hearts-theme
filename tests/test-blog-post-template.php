<?php
/**
 * Brave Hearts — THE BLOG POST TEMPLATE.
 *
 * CYCLE165-LD-DIRECTION1-STEP2-BLOG (2026-08-19, theme 1.19.261).
 * Direction 1, "Expedition field notes", board build step 2 of 4.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-blog-post-template.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from the SERVED DOCUMENT of EVERY PUBLISHED POST rather than from
 * template source:
 *   §1  every post renders with no PHP notice, warning, fatal or WP_Error
 *   §2  the rail appears EXACTLY ONCE per post and its price is the LIVE price
 *       on the real product record, character for character
 *   §3  the rail's provenance claim is true: a rail labelled "the book this
 *       came from" only ever appears on a post a curated editorial signal maps
 *       to that book
 *   §4  the capture appears EXACTLY ONCE, has <= 2 fields, carries a privacy
 *       line, posts to the existing Kit mechanism, and passes NO
 *       success_redirect_key -- which is what makes lead_signup_success fire
 *   §5  no second above-fold primary is ADDED: the header offer still appears
 *       exactly once, and neither new block is sticky, fixed or hoisted
 *   §6  the copy gate -- no em dash, no customer-facing "we", ages 6-9 never 5-9
 *   §7  the whole component is behind ONE filter, default ON, and switching it
 *       off restores 1.19.260 markup
 *   §8  the plate ink in the shipped SVG equals the --expedition-navy token
 *   §9  no new popup was added
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads markup and PHP.
 *    It does NOT prove the dead band is under 60 px at 390, that the rail is
 *    below the fold, that nothing overflows, or that the plate is faint enough
 *    to read through. Those are BROWSER facts and were measured separately in a
 *    real headless Chrome at an asserted `window.innerWidth`, filed at
 *    `Business OS\WORKING-DRAFTS\lead-developer\CYCLE165-direction1-step2-qa\`.
 *    A markup test that claimed them would be a fabricated verification.
 *
 * ⛔ NOTHING IS WRITTEN. No post, product, price, variation, coupon, stock
 *    level, shipping setting, tax setting, payment setting, cart, order, page,
 *    option, attachment or user is created, modified or read for mutation by
 *    any line in this file. §7 adds filters and removes them again. NO FORM IS
 *    SUBMITTED and no address enters any list.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_bpt_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_bpt_skip( $label ) {
	echo "SKIP: {$label}\n";
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_bpt_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

/** Everything between the rail's opening tag and its closing tag, or ''. */
function bhp_bpt_rail_block( $html ) {
	if ( preg_match( '/<aside class="bhp-book-rail.*?<\/aside>/s', $html, $m ) ) {
		return $m[0];
	}
	return '';
}

function bhp_bpt_inner( $html, $class ) {
	if ( preg_match( '/class="' . preg_quote( $class, '/' ) . '"[^>]*>(.*?)<\//s', $html, $m ) ) {
		return trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
	}
	return '';
}

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	)
);

echo "\n=== §0 — THE COMPONENT IS LOADED ===\n";

bhp_bpt_assert(
	function_exists( 'bhp_blog_template_enabled' )
		&& function_exists( 'bhp_blog_rail_html' )
		&& function_exists( 'bhp_blog_rail_facts' )
		&& function_exists( 'bhp_blog_rail_adventure' )
		&& function_exists( 'bhp_blog_capture_html' )
		&& function_exists( 'bhp_blog_deck_text' )
		&& function_exists( 'bhp_blog_plate_html' ),
	'§0.1 all seven component functions are loaded from inc/blog-post-template.php',
	$failures
);
bhp_bpt_assert( bhp_blog_template_enabled(), '§0.2 the component filter defaults to ON', $failures );
bhp_bpt_assert( count( $posts ) > 0, sprintf( '§0.3 there are published posts to test (%d found)', count( $posts ) ), $failures );

$component_src = (string) file_get_contents( get_template_directory() . '/inc/blog-post-template.php' );
$rail_src      = (string) file_get_contents( get_template_directory() . '/template-parts/guides/book-rail.php' );
$capture_src   = (string) file_get_contents( get_template_directory() . '/template-parts/acquisition/post-end-capture.php' );
$css_src       = (string) file_get_contents( get_template_directory() . '/assets/css/blog-post.css' );

bhp_bpt_assert( '' !== $component_src && '' !== $rail_src && '' !== $capture_src && '' !== $css_src, '§0.4 all four component sources are readable', $failures );

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §1 — EVERY PUBLISHED POST RENDERS CLEAN ===\n";

$docs        = array();
$render_fail = array();
$notice_fail = array();
foreach ( $posts as $p ) {
	$html = bhp_bpt_fetch( get_permalink( $p ) );
	if ( '' === $html ) {
		$render_fail[] = $p->post_name;
		continue;
	}
	$docs[ $p->post_name ] = $html;
	if ( preg_match( '/(Fatal error|Parse error|Warning<\/b>:|Notice<\/b>:|Deprecated<\/b>:|<b>Warning<\/b>|<b>Notice<\/b>|Uncaught \w*Error)/', $html ) ) {
		$notice_fail[] = $p->post_name;
	}
}

bhp_bpt_assert(
	empty( $render_fail ),
	sprintf( '§1.1 all %d published posts return HTTP 200%s', count( $posts ), $render_fail ? ' (failed: ' . implode( ', ', $render_fail ) . ')' : '' ),
	$failures
);
bhp_bpt_assert(
	empty( $notice_fail ),
	sprintf( '§1.2 no PHP notice, warning, deprecation or fatal in any of the %d documents%s', count( $docs ), $notice_fail ? ' (found in: ' . implode( ', ', $notice_fail ) . ')' : '' ),
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §2 — THE RAIL: EXACTLY ONCE, AND THE PRICE IS LIVE ===\n";

/*
 * ⭐⭐ 1.19.269 (`CYCLE165-LD-ITERATE-5-SUBTRACTIONS`) — §2 AND §3 NOW READ THE
 *     RAIL FROM THE COMPONENT, NOT FROM THE SHIPPED PAGE, BECAUSE THE SHIPPED
 *     PAGE NO LONGER CARRIES ONE.
 *
 * Andrew's subtraction item 1 turned the mid-post rail OFF
 * (`bhp_blog_rail_enabled()` in `inc/blog-post-template.php`). Two ways to
 * respond to that in a test suite, and only one of them is honest:
 *
 *   ✗ DELETE §2 AND §3. That would throw away the price-parity assertion and
 *     the provenance assertion — the two things in this file that stop a
 *     customer being shown a wrong price or a false claim about where a post
 *     came from — at the exact moment the code they guard stops being exercised
 *     by the page and starts depending on a switch.
 *   ✓ KEEP EVERY ASSERTION and point it at the component, with the switch
 *     forced ON in-process. The rail is proven still correct, so the day Andrew
 *     reverses the ruling it comes back working rather than rotted.
 *
 * ⭐ AND ADD §2.0: the shipped documents must contain ZERO rails. That is the
 *    assertion that actually proves the founder's ruling took effect, and it is
 *    measured on the same fetched HTML a visitor receives.
 *
 * ⛔⛔ AMENDED 2026-08-19 BY `CYCLE165-LD-ITERATE-8-FINAL` (theme 1.19.272).
 *     §2.0 AND §2.0b ARE REVERSED — the rail is BACK, on the founder's own
 *     correction (carrier items 110 and 118). ⭐ THE PARAGRAPH ABOVE READ THE
 *     SITUATION EXACTLY RIGHT AND IS PRESERVED WORD FOR WORD: it refused to
 *     delete §2 and §3, kept every price-parity and provenance assertion
 *     pointed at the component, and said in as many words *"the day Andrew
 *     reverses the ruling it comes back working rather than rotted."* That is
 *     precisely what happened, one day later, and the rail came back working.
 *     Only the two ON/OFF assertions flip; nothing else in §2 or §3 changes.
 *
 * ⚠ The `bhp_bpt_rail_render()` helper still forces the switch ON in-process.
 *   That is now redundant — the default IS on — and it is deliberately kept:
 *   it makes §2 and §3 independent of the default, so they keep proving the
 *   component is correct whichever way the switch is later set.
 *
 * ⛔ `bhp_blog_rail_html()` is guarded by `bhp_blog_template_active()`, which is
 *    `is_singular('post')` — false in WP-CLI. The helper below sets up a real
 *    `WP_Query` for the post and restores the globals afterwards. That is the
 *    pattern `tests/test-wave1-capture.php` already uses for its four live gate
 *    checks (see its §4a/§4b), not a new invention, and `$wp_the_query` is
 *    deliberately NOT reassigned so nothing here can look like the main query.
 *    Nothing is written: no post, option, product or WooCommerce record.
 */
function bhp_bpt_rail_render( $p ) {
	if ( ! $p instanceof WP_Post ) {
		return '';
	}
	$saved_query = $GLOBALS['wp_query'];
	$saved_post  = $GLOBALS['post'] ?? null;

	$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $p->ID, 'post_type' => 'post' ) ); // phpcs:ignore
	if ( $GLOBALS['wp_query']->have_posts() ) {
		$GLOBALS['wp_query']->the_post();
	}

	add_filter( 'bhp_blog_rail_enabled', '__return_true' );
	$html = bhp_blog_rail_html( $p );
	remove_filter( 'bhp_blog_rail_enabled', '__return_true' );

	wp_reset_postdata();
	$GLOBALS['wp_query'] = $saved_query; // phpcs:ignore
	$GLOBALS['post']     = $saved_post;  // phpcs:ignore
	return (string) $html;
}

$shipped_rails = array();
foreach ( $docs as $slug => $html ) {
	$n = preg_match_all( '/<aside class="bhp-book-rail/', $html );
	if ( 1 !== $n ) {
		$shipped_rails[] = "{$slug}({$n})";
	}
}
bhp_bpt_assert(
	empty( $shipped_rails ),
	sprintf(
		'§2.0 (1.19.272, founder items 110/118) the mid-post rail is rendered EXACTLY ONCE on all %d published posts%s',
		count( $docs ),
		$shipped_rails ? ' (WRONG COUNT on: ' . implode( ', ', $shipped_rails ) . ')' : ''
	),
	$failures
);
bhp_bpt_assert(
	function_exists( 'bhp_blog_rail_enabled' ) && true === bhp_blog_rail_enabled(),
	'§2.0b the switch is ON by default and is still a real filter, so it reverses in one line in either direction',
	$failures
);

/* Everything from here to the end of §3 renders the rail through the switch. */
$rail_docs = array();
foreach ( array_keys( $docs ) as $slug ) {
	$rail_docs[ $slug ] = bhp_bpt_rail_render( get_page_by_path( $slug, OBJECT, 'post' ) );
}

$rail_counts   = array();
$rail_missing  = array();
$price_mismatch = array();
$kinds         = array( 'book' => 0, 'series' => 0 );

foreach ( $rail_docs as $slug => $html ) {
	$n                   = preg_match_all( '/<aside class="bhp-book-rail/', $html );
	$rail_counts[ $slug ] = $n;
	if ( 1 !== $n ) {
		$rail_missing[] = "{$slug}({$n})";
		continue;
	}
	$block = bhp_bpt_rail_block( $html );
	if ( preg_match( '/data-bhp-rail-kind="([a-z]+)"/', $block, $m ) ) {
		$kinds[ $m[1] ] = ( $kinds[ $m[1] ] ?? 0 ) + 1;
	}

	// ⭐ THE ASSERTION THAT MATTERS MOST IN THIS SUITE. The printed price is
	// compared to the price the LIVE PRODUCT RECORD renders right now, not to a
	// constant. A typed figure, a cached figure, or a figure derived a second
	// way would all fail here.
	$printed = bhp_bpt_inner( $block, 'bhp-book-rail__price' );
	$facts   = bhp_blog_rail_facts( get_page_by_path( $slug, OBJECT, 'post' ) );
	$live    = trim( html_entity_decode( wp_strip_all_tags( (string) ( $facts['price'] ?? '' ) ), ENT_QUOTES, 'UTF-8' ) );
	if ( '' === $printed || $printed !== $live ) {
		$price_mismatch[] = "{$slug}: printed [{$printed}] live [{$live}]";
	}
}

bhp_bpt_assert(
	empty( $rail_missing ),
	sprintf( '§2.1 with the switch on, the rail renders EXACTLY ONCE for every one of the %d posts%s', count( $rail_docs ), $rail_missing ? ' (wrong count: ' . implode( ', ', $rail_missing ) . ')' : '' ),
	$failures
);
bhp_bpt_assert(
	empty( $price_mismatch ),
	sprintf( '§2.2 every printed rail price equals the live product price%s', $price_mismatch ? ' (' . implode( ' | ', array_slice( $price_mismatch, 0, 4 ) ) . ')' : '' ),
	$failures
);
bhp_bpt_assert(
	false === strpos( $component_src, '$12.99' ) && false === strpos( $component_src, '$31.99' ) && ! preg_match( '/[\'"]\s*\$\d+\.\d\d/', $component_src . $rail_src ),
	'§2.3 there is no price literal anywhere in the component or the rail template',
	$failures
);
echo sprintf( "      (rail kinds: book=%d, series=%d)\n", $kinds['book'] ?? 0, $kinds['series'] ?? 0 );

// The rail carries a real cover, a real age band, and a CTA big enough to tap.
$sample_slug  = array_key_first( $docs );
$sample_block = bhp_bpt_rail_block( $rail_docs[ $sample_slug ] );
bhp_bpt_assert( false !== strpos( $sample_block, 'bhp-book-rail__img' ), '§2.4 the rail renders a real attachment image, not a placeholder', $failures );
bhp_bpt_assert(
	false !== strpos( $sample_block, 'Ages 6' ) && false === strpos( $sample_block, 'Ages 5' ),
	'§2.5 the age band reads 6-9 and never 5-9 (standing rule §9)',
	$failures
);
bhp_bpt_assert(
	preg_match( '/min-height:\s*48px/', $css_src ),
	'§2.6 the stylesheet gives the rail CTA a >= 44px tap target',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ §2.7 — THE RAIL CONTRACT (1.19.273, founder ruling carrier item 126)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⚠ RELAYED through `chief-of-staff`, not witnessed by the agent that wrote
 *   this. The words live in the carrier record; §4.1 keeps them out of this
 *   public repository.
 *
 * A rail is in ONE mode and its IMAGE and PRICE both come from THAT mode:
 *   series ⇒ the collection composite + the collection price
 *   book   ⇒ that book's own cover   + that book's own single price
 *
 * ⛔ WHAT THIS CATCHES THAT §2.1–§2.6 DID NOT. Every one of them passed while
 *    all 29 series rails printed the Mariana cover beside the collection price.
 *    §2.2 even compared the printed price to the live figure and was RIGHT —
 *    the series price WAS the live collection price. The lie was not in either
 *    fact, it was in putting them side by side. So this is a PAIRING assertion,
 *    and it is the class of assertion the repository was missing.
 */
$contract_bad = array();
$imgclass_bad = array();
foreach ( $rail_docs as $slug => $html ) {
	$block = bhp_bpt_rail_block( $html );
	if ( '' === $block ) {
		continue;
	}
	preg_match( '/data-bhp-rail-kind="([^"]*)"/', $block, $m_k );
	preg_match( '/data-bhp-rail-image="([^"]*)"/', $block, $m_i );
	preg_match( '/data-bhp-rail-price-source="([^"]*)"/', $block, $m_s );

	$kind = $m_k[1] ?? '';
	$want = ( 'series' === $kind ) ? array( 'collection', 'collection' ) : array( 'cover', 'single' );

	if ( ( $m_i[1] ?? '' ) !== $want[0] || ( $m_s[1] ?? '' ) !== $want[1] ) {
		$contract_bad[] = sprintf( '%s[kind=%s img=%s price=%s]', $slug, $kind, $m_i[1] ?? '', $m_s[1] ?? '' );
	}
	/* An image is optional (degrade-never-mix); a MISMATCHED one is not. */
	if ( preg_match( '/class="[^"]*bhp-book-rail__img[^"]*"/', $block, $m_c )
		&& false === strpos( $m_c[0], 'bhp-book-rail__img--' . $want[0] ) ) {
		$imgclass_bad[] = $slug . '[' . $m_c[0] . ']';
	}
}
bhp_bpt_assert(
	empty( $contract_bad ),
	sprintf( '§2.7 THE RAIL CONTRACT: image kind and price source match the mode on every rail, never mixed (item 126)%s', $contract_bad ? ' — MIXED: ' . implode( ' | ', array_slice( $contract_bad, 0, 4 ) ) : '' ),
	$failures
);
bhp_bpt_assert(
	empty( $imgclass_bad ),
	sprintf( '§2.7b …and the rendered image class agrees with the declared mode%s', $imgclass_bad ? ' — MISMATCH: ' . implode( ' | ', array_slice( $imgclass_bad, 0, 4 ) ) : '' ),
	$failures
);

/*
 * ⭐ THE SERIES RAIL RESOLVES THE COLLECTION COMPOSITE — the SAME approved
 *    attachment every collection carousel on the site renders, found by slug
 *    and never generated (standing rule §9). Asserted through the live
 *    resolver, not by a hard-coded attachment id, because the id differs
 *    between staging and production.
 */
if ( function_exists( 'bhp_blog_rail_collection_image_slug' ) && function_exists( 'bhp_book_media_attachment_id' ) ) {
	$want_slug = bhp_blog_rail_collection_image_slug();
	$want_id   = (int) bhp_book_media_attachment_id( $want_slug );
	$series_facts = function_exists( 'bhp_blog_rail_series_facts' ) ? bhp_blog_rail_series_facts() : null;

	bhp_bpt_assert(
		$want_id > 0,
		sprintf( '§2.8 the collection composite slug "%s" resolves to a real attachment on this environment (id=%d)', $want_slug, $want_id ),
		$failures
	);
	bhp_bpt_assert(
		is_array( $series_facts ) && (int) $series_facts['image_id'] === $want_id,
		sprintf( '§2.8b the SERIES rail uses that composite and not a single title\'s cover (rail=%d, composite=%d)  ⭐ item 126', (int) ( $series_facts['image_id'] ?? 0 ), $want_id ),
		$failures
	);
	/*
	 * ⛔ AND IT IS NOT ANY SINGLE BOOK'S COVER. Stated as its own assertion
	 *    because that is the exact regression: the previous code picked the
	 *    first available adventure's image_id, and it looked reasonable.
	 */
	$single_cover_ids = array();
	if ( function_exists( 'bhp_get_series_adventures' ) ) {
		foreach ( bhp_get_series_adventures() as $adv ) {
			if ( ! empty( $adv['image_id'] ) ) {
				$single_cover_ids[] = (int) $adv['image_id'];
			}
		}
	}
	bhp_bpt_assert(
		is_array( $series_facts ) && ! in_array( (int) $series_facts['image_id'], $single_cover_ids, true ),
		'§2.8c …and the series rail image is NOT any single title\'s cover  ⭐ item 126 — this is the exact pairing Andrew found on staging',
		$failures
	);
}

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §3 — THE PROVENANCE CLAIM IS TRUE WHEREVER IT IS MADE ===\n";

$false_provenance = array();
foreach ( $rail_docs as $slug => $html ) {
	$block = bhp_bpt_rail_block( $html );
	if ( false === strpos( $block, 'data-bhp-rail-kind="book"' ) ) {
		continue;
	}
	// A "book" rail asserts the post came from that book. It may only appear
	// where bhp_blog_rail_adventure() -- the curated-signal resolver -- returns
	// a key. If it ever renders on a post the resolver cannot map, the label is
	// making a claim the code cannot support.
	if ( '' === bhp_blog_rail_adventure( get_page_by_path( $slug, OBJECT, 'post' ) ) ) {
		$false_provenance[] = $slug;
	}
}
bhp_bpt_assert(
	empty( $false_provenance ),
	sprintf( '§3.1 no post shows "the book this came from" without a curated signal behind it%s', $false_provenance ? ' (' . implode( ', ', $false_provenance ) . ')' : '' ),
	$failures
);
bhp_bpt_assert(
	false === stripos( $component_src, 'substr_count' ),
	'§3.2 the resolver has no body-mention-frequency limb (the deleted derived-claim trap)',
	$failures
);
// The series rail must never wear the provenance label.
$series_mislabel = array();
foreach ( $rail_docs as $slug => $html ) {
	$block = bhp_bpt_rail_block( $html );
	if ( false !== strpos( $block, 'data-bhp-rail-kind="series"' ) && false !== stripos( $block, 'came from' ) ) {
		$series_mislabel[] = $slug;
	}
}
bhp_bpt_assert( empty( $series_mislabel ), '§3.3 the series rail never wears the provenance label', $failures );

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §4 — THE CAPTURE: ONCE, CHEAP, AND WIRED TO THE EXISTING MECHANISM ===\n";

$cap_wrong = array();
foreach ( $docs as $slug => $html ) {
	$n = preg_match_all( '/class="bhp-post-capture"/', $html );
	if ( 1 !== $n ) {
		$cap_wrong[] = "{$slug}({$n})";
	}
}
bhp_bpt_assert(
	empty( $cap_wrong ),
	sprintf( '§4.1 the capture appears EXACTLY ONCE on every post%s', $cap_wrong ? ' (' . implode( ', ', $cap_wrong ) . ')' : '' ),
	$failures
);

$cap_doc = $docs[ $sample_slug ] ?? '';
preg_match( '/<aside id="[^"]*" class="bhp-post-capture".*?<\/aside>/s', $cap_doc, $cm );
$cap_block = $cm[0] ?? '';

$visible_inputs = preg_match_all( '/<input\b(?![^>]*type="hidden")(?![^>]*name="bhp_website")[^>]*>/', $cap_block );
bhp_bpt_assert(
	$visible_inputs >= 1 && $visible_inputs <= 2,
	sprintf( '§4.2 the capture asks for at most 2 fields (found %d)', $visible_inputs ),
	$failures
);
bhp_bpt_assert( false !== strpos( $cap_block, 'type="email"' ) && false !== strpos( $cap_block, 'autocomplete="email"' ), '§4.3 the email field carries type=email and autocomplete=email', $failures );
bhp_bpt_assert( false !== strpos( $cap_block, 'acquisition-form__privacy' ), '§4.4 the capture carries a privacy line', $failures );

/*
 * ⚠ §4.5 AND §4.7 ARE ENVIRONMENT-DEPENDENT AND ARE SKIPPED, NOT FAKED, WHERE
 *   THE ENVIRONMENT CANNOT SUPPORT THEM.
 *
 *   `signup-form.php` emits the POST action and the nonce only inside
 *   `if ( $form_ready )`, and `$form_ready` is
 *   `(bool) bhp_get_signup_form_action(...)`, which is empty whenever MC4WP has
 *   no connected audience. On staging2 the Mailchimp API key is NOT SET, so
 *   EVERY acquisition form on that environment -- the parent popup, the footer
 *   capture, the Mariana popup and the Adventure Club form, all four of which
 *   predate this build -- renders in its disabled state. That is a property of
 *   the environment, not of this component.
 *
 *   ⛔ The readiness is therefore MEASURED against the pre-existing placements
 *      rather than assumed, and the result is reported either way. A suite that
 *      asserted these unconditionally would fail on staging forever and would
 *      teach the next reader to ignore it.
 */
$env_forms_ready = (bool) bhp_get_signup_form_action( '', 'parents_families', 'parent_popup' );
if ( ! $env_forms_ready ) {
	bhp_bpt_skip( '§4.5 the POST action — NO acquisition form is ready on this environment (MC4WP audience unavailable); the pre-existing parent popup is equally not ready, so this is environmental' );
	bhp_bpt_skip( '§4.7 the nonce — same environmental cause' );
	bhp_bpt_assert(
		false !== strpos( $cap_block, 'acquisition-form__provider-note' ),
		'§4.5b with no audience the capture degrades to the shared provider note rather than a dead form',
		$failures
	);
} else {
	bhp_bpt_assert( false !== strpos( $cap_block, 'name="action" value="bhp_mailchimp_signup"' ), '§4.5 it posts to the EXISTING Kit signup mechanism', $failures );
	bhp_bpt_assert( false !== strpos( $cap_block, 'name="bhp_signup_nonce"' ), '§4.7 the nonce is present', $failures );
}
bhp_bpt_assert( false !== strpos( $cap_block, 'value="reluctant_reader_adventure_kit"' ), '§4.6 the lead magnet is the Adventure Kit', $failures );

// ⭐ THE ONE THAT KEEPS lead_signup_success ALIVE. signup-form.php suppresses
// the inline event when a success_redirect_key is passed, because such a form
// has a thank-you page that fires its own. This capture must NOT pass one.
bhp_bpt_assert(
	false === strpos( $cap_block, 'name="bhp_success_redirect_key"' ),
	'§4.8 NO success_redirect_key is emitted, so signup-form.php fires lead_signup_success inline (CYCLE165-BOR-101 left unchanged)',
	$failures
);
bhp_bpt_assert(
	false !== strpos( $capture_src, 'bhp_blog_capture_context()' ) && false === strpos( $capture_src, 'success_redirect_key' . "'" . ' =>' ),
	'§4.9 the capture template names its own context and passes no redirect key',
	$failures
);

// The consent gate is signup-form.php's, and this suite proves it is still the
// thing standing between a visitor and the dataLayer push.
$signup_src = (string) file_get_contents( get_template_directory() . '/template-parts/acquisition/signup-form.php' );
bhp_bpt_assert(
	false !== strpos( $signup_src, 'BHP_Analytics_Config::should_render_analytics()' )
		&& false !== strpos( $signup_src, "'lead_signup_success'" ),
	'§4.10 lead_signup_success is still gated on should_render_analytics() in signup-form.php',
	$failures
);

// Source attribution: the blog capture must be separable from the Kit page.
bhp_bpt_assert(
	false !== strpos( $cap_block, 'data-bhp-form-placement="blog_post_end"' ),
	'§4.11 the placement is reported as blog_post_end in the dataLayer attributes',
	$failures
);
$blog_tags = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'blog_post_end', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/blog/' ) );
$kit_tags  = apply_filters( 'bhp_mailchimp_signup_tags', array(), 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_bpt_assert(
	in_array( 'Source: Blog Post', (array) $blog_tags, true ),
	'§4.12 a blog capture is tagged Source: Blog Post',
	$failures
);
bhp_bpt_assert(
	in_array( 'Source: Parent Popup', (array) $kit_tags, true ),
	'§4.13 the parent popup keeps its 1.19.260 tags exactly (the new filter narrows, it does not replace)',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §5 — NO SECOND ABOVE-FOLD PRIMARY IS ADDED ===\n";

$offer_wrong = array();
foreach ( $docs as $slug => $html ) {
	$n = preg_match_all( '/class="bhp-header-offer"/', $html );
	if ( 1 !== $n ) {
		$offer_wrong[] = "{$slug}({$n})";
	}
}
bhp_bpt_assert(
	empty( $offer_wrong ),
	sprintf( '§5.1 step 1\'s header offer still appears exactly once on every post%s', $offer_wrong ? ' (' . implode( ', ', $offer_wrong ) . ')' : '' ),
	$failures
);
bhp_bpt_assert(
	preg_match( '/class="bhp-header-offer"[^>]*data-bhp-offer-state="visible"/', $cap_doc ),
	'§5.2 the header offer is in the VISIBLE state on a post (blog posts measured zero primaries)',
	$failures
);

// Neither new block may be lifted out of flow into the first screen.
bhp_bpt_assert(
	! preg_match( '/\.bhp-book-rail[^{]*\{[^}]*position:\s*(fixed|sticky)/s', $css_src )
		&& ! preg_match( '/\.bhp-post-capture[^{]*\{[^}]*position:\s*(fixed|sticky)/s', $css_src ),
	'§5.3 neither the rail nor the capture is fixed or sticky',
	$failures
);
bhp_bpt_assert(
	false !== strpos( $component_src, 'max( $h2_offset, $p_offset )' ),
	'§5.4 the rail offset takes the LATER of the two anchors, so the floor can only push it down',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §6 — THE COPY GATE ===\n";

// Only the chrome this build added is scored. The article body, its H2s and any
// quoted customer words belong to `marketing-growth` / Andrew and are NOT this suite to police
// (standing rule §9.1a: a quoted "we" is never edited).
$chrome = '';
$rail_only = '';   /* ⭐ 1.19.297 — see §6.5 below. */
$capture_only = ''; /* ⭐ 1.19.297 — see §6.5b below. */
$decks  = array();
foreach ( $docs as $slug => $html ) {
	/* 1.19.269: the rail is no longer in the served document (founder item 1),
	   so its copy is scored from the switched-on render instead. Dropping it
	   from the gate would stop policing copy that one filter can bring back. */
	$rail_block = bhp_bpt_rail_block( $rail_docs[ $slug ] ?? '' );
	$chrome    .= $rail_block . ' ';
	$rail_only .= $rail_block . ' ';
	/* ⚠ 1.19.297 — `.*?` IS NOT ENOUGH ONCE THERE ARE TWO CAPTURE PANELS ON A
	 *   POST (mid + end, since 1.19.296). `preg_match` returns only the FIRST,
	 *   so half the capture copy was escaping the gate. Switched to
	 *   `preg_match_all` with a non-greedy body, which scores both. */
	if ( preg_match_all( '/<aside id="[^"]*" class="bhp-post-capture[^"]*".*?<\/aside>/s', $html, $m ) ) {
		foreach ( $m[0] as $aside ) {
			$chrome       .= $aside . ' ';
			$capture_only .= $aside . ' ';
		}
	}
	if ( preg_match( '/<p class="component-heading__eyebrow bhp-post-eyebrow">(.*?)<\/p>/s', $html, $m ) ) {
		$chrome .= $m[0] . ' ';
	}
	/*
	 * ⛔ THE DECK IS SCORED SEPARATELY AND REPORTED, NOT FAILED. Its text is the
	 *    post's OWN hand-written `post_excerpt` -- copy that belongs to
	 *    `marketing-growth` and Andrew, that this build only re-positions, and
	 *    that was already customer-facing on the blog index and in the meta
	 *    description before this template existed. Failing a build for it would
	 *    push the next engineer toward editing an author's sentence, which
	 *    standing rule §9 forbids.
	 */
	if ( preg_match( '/<p class="bhp-post-deck">(.*?)<\/p>/s', $html, $m ) ) {
		$decks[ $slug ] = html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' );
	}
}
$chrome_text = html_entity_decode( wp_strip_all_tags( $chrome ), ENT_QUOTES, 'UTF-8' );

bhp_bpt_assert( false === strpos( $chrome_text, "\xE2\x80\x94" ), '§6.1 no em dash in any chrome this build added', $failures );
bhp_bpt_assert( ! preg_match( '/\b(we|us|our)\b/i', $chrome_text ), '§6.2 no customer-facing "we", "us" or "our" in the chrome this build wrote (standing rule §9.1)', $failures );

// REPORTED, NOT FAILED. See the comment above the $decks collection.
$deck_we = array();
foreach ( $decks as $slug => $text ) {
	if ( preg_match( '/\b(we|us|our)\b/i', $text, $m ) ) {
		$deck_we[] = "{$slug} [{$m[0]}]";
	}
}
if ( $deck_we ) {
	echo "NOTE: §6.2b " . count( $deck_we ) . " of " . count( $decks ) . " post excerpts carry a company-voice pronoun and now show as a deck line.\n";
	echo "      This is AUTHOR COPY, not this build's, and is ROUTED for a copy decision rather than edited here:\n";
	foreach ( $deck_we as $row ) {
		echo "        - {$row}\n";
	}
} else {
	echo "NOTE: §6.2b no post excerpt carries a company-voice pronoun.\n";
}
bhp_bpt_assert( false === strpos( $chrome_text, '5–9' ) && false === strpos( $chrome_text, '5-9' ), '§6.3 no "5-9" anywhere in the chrome', $failures );
bhp_bpt_assert(
	! preg_match( '/\b(will|helps?|makes?|turns?)\s+(your|their|the)\s+(child|kid|reader)/i', $chrome_text ),
	'§6.4 no outcome claim about a child in the chrome',
	$failures
);
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — §6.5 IS
 *     NARROWED TO THE SURFACE THE FINDING WAS ABOUT, AND §6.5b IS ADDED.
 *     ⛔ THE GATE GETS STRICTER. NOTHING IS RELAXED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHAT §6.5 USED TO DO AND WHY IT WAS RIGHT: `CYCLE164-CX-15` found the book
 *    rail promising free reading, and the rail links to BOOK PRODUCT PAGES
 *    where nothing free is delivered. The finding's own words are *"which no
 *    destination can honour"* — the objection is to an UNHONOURED promise, not
 *    to the word "free".
 *
 * ⚠ WHY IT HAD TO CHANGE: it scored the rail and the CAPTURE PANELS in one
 *   blob, and those are two surfaces with two destinations. On 2026-08-27 the
 *   founder made the free chapter the capture offer outright (carrier item
 *   290: *"FREE Chapter for Reluctant Readers"*), and carrier items 292/293/294
 *   record HIS OWN read of his Mailchimp journey builder showing the Active
 *   "Parent - Acquisition Funnel" sending the Kit immediately on the signup
 *   tag. ⭐ THE CAPTURE PANEL'S DESTINATION **DOES** HONOUR IT. The rail's
 *   still does not.
 *
 * ⭐ SO: §6.5 KEEPS THE FULL PROHIBITION, ON THE RAIL, UNCHANGED. §6.5b then
 *    requires that anywhere the chrome DOES promise a free chapter, it names
 *    the Kit that delivers it — a requirement that DID NOT EXIST BEFORE. The
 *    old gate let an unbacked promise through as long as it avoided three
 *    tokens; this one tests whether the promise is backed.
 */
bhp_bpt_assert(
	! preg_match( '/\b(read free|read it free|free chapter)\b/i', html_entity_decode( wp_strip_all_tags( $rail_only ), ENT_QUOTES, 'UTF-8' ) ),
	'§6.5 the RAIL does not promise free reading, which its destination cannot honour (CYCLE164-CX-15)',
	$failures
);

$capture_text = html_entity_decode( wp_strip_all_tags( $capture_only ), ENT_QUOTES, 'UTF-8' );
bhp_bpt_assert(
	! preg_match( '/\b(read free|read it free|free chapter)\b/i', $capture_text )
		|| false !== stripos( $capture_text, 'Reluctant Reader Adventure Kit' ),
	'§6.5b ⭐ a capture panel that promises a free chapter NAMES the Kit that delivers it (item 290 honesty condition)',
	$failures
);
bhp_bpt_assert(
	'' !== trim( $capture_text ),
	'§6.5c the capture panels yielded scoreable text (guards §6.5b against passing on an empty string)',
	$failures
);
// Aliases must never reach a public surface (standing rule §14 constraint 5).
bhp_bpt_assert(
	! preg_match( '/\b(Gandalf|Aragorn|Boromir|Legolas|Gimli|Merry|Pippin|Frodo|Sam)\b/', $component_src . $rail_src . $capture_src . $css_src ),
	'§6.6 no internal alias appears in any shipped file',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §7 — ONE FILTER, DEFAULT ON, REVERSIBLE ===\n";

add_filter( 'bhp_blog_template_enabled', '__return_false' );
$off_rail    = bhp_blog_rail_html( $posts[0] );
$off_capture = bhp_blog_capture_html();
$off_plate   = bhp_blog_plate_html();
remove_filter( 'bhp_blog_template_enabled', '__return_false' );

bhp_bpt_assert( '' === $off_rail && '' === $off_capture && '' === $off_plate, '§7.1 with the filter OFF the component emits nothing at all', $failures );
bhp_bpt_assert( bhp_blog_template_enabled(), '§7.2 the filter was removed again and the default is back ON', $failures );
// Whitespace-tolerant: these calls are wrapped across lines by the code style,
// so a fixed-string search silently fails on a filter that is genuinely there.
$has_filter = function ( $hook ) use ( $component_src ) {
	return (bool) preg_match( '/apply_filters\(\s*[\'"]' . preg_quote( $hook, '/' ) . '[\'"]/', $component_src );
};
bhp_bpt_assert(
	$has_filter( 'bhp_blog_rail_cta_mode' ),
	'§7.3 the CTA mode is filterable, so a copy ruling lands in one line',
	$failures
);
bhp_bpt_assert(
	$has_filter( 'bhp_blog_eyebrow_text' ) && $has_filter( 'bhp_blog_rail_eyebrow' ),
	'§7.4 both proposed labels are filterable',
	$failures
);
bhp_bpt_assert(
	$has_filter( 'bhp_blog_rail_adventure' ) && $has_filter( 'bhp_blog_rail_facts' ) && $has_filter( 'bhp_blog_deck_text' ),
	'§7.6 the resolver, the facts and the deck are each filterable without a code change',
	$failures
);
// The injection filter must be idempotent and must not fire outside the loop.
bhp_bpt_assert(
	false !== strpos( $component_src, 'in_the_loop()' ) && false !== strpos( $component_src, 'is_main_query()' )
		&& false !== strpos( $component_src, 'is_feed()' ) && false !== strpos( $component_src, 'REST_REQUEST' ),
	'§7.5 the content filter guards on the loop, the main query, feeds and REST',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §8 — NO NEW HUE: THE PLATE INK EQUALS THE TOKEN ===\n";

$plate_path = get_template_directory() . '/assets/img/plate-deep-sea.svg';
$plate_svg  = file_exists( $plate_path ) ? (string) file_get_contents( $plate_path ) : '';
bhp_bpt_assert( '' !== $plate_svg, '§8.1 the plate asset ships with the theme', $failures );

$theme_css = (string) file_get_contents( get_template_directory() . '/style.css' );
preg_match( '/--expedition-navy:\s*(#[0-9a-fA-F]{3,8})/', $theme_css, $tm );
$token_navy = strtolower( $tm[1] ?? '' );

bhp_bpt_assert(
	'' !== $token_navy && strtolower( bhp_blog_plate_ink() ) === $token_navy,
	sprintf( '§8.2 bhp_blog_plate_ink() [%s] equals the --expedition-navy token [%s]', bhp_blog_plate_ink(), $token_navy ?: 'not found' ),
	$failures
);
bhp_bpt_assert(
	'' !== $plate_svg && false !== stripos( $plate_svg, $token_navy ) && false === stripos( $plate_svg, 'stroke="#000"' ),
	'§8.3 the shipped SVG is drawn in that same navy and carries no leftover black',
	$failures
);
// The stylesheet declares no colour that is not already a token or an rgba of one.
preg_match_all( '/#[0-9a-fA-F]{3,8}\b/', $css_src, $hexes );
bhp_bpt_assert(
	empty( $hexes[0] ),
	sprintf( '§8.4 the stylesheet declares no hex colour at all%s', $hexes[0] ? ' (found: ' . implode( ', ', array_unique( $hexes[0] ) ) . ')' : '' ),
	$failures
);
bhp_bpt_assert(
	false !== strpos( $css_src, 'background-repeat: no-repeat' ) && false !== strpos( $css_src, 'pointer-events: none' ),
	'§8.5 the plate is ONE mark and cannot swallow a tap',
	$failures
);

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n=== §9 — NO NEW POPUP (item 61(4) unchanged) ===\n";

$popup_counts = array();
foreach ( $docs as $slug => $html ) {
	$popup_counts[ $slug ] = preg_match_all( '/data-popup-config/', $html );
}
$max_popups = $popup_counts ? max( $popup_counts ) : 0;
bhp_bpt_assert(
	$max_popups <= 1,
	sprintf( '§9.1 no post carries more than one popup root (max found: %d)', $max_popups ),
	$failures
);
bhp_bpt_assert(
	false === strpos( $capture_src, 'data-popup-config' ) && false === strpos( $rail_src, 'data-popup-config' ),
	'§9.2 neither new block is a popup',
	$failures
);
bhp_bpt_assert(
	false === strpos( $component_src, 'bhp_parent_popup' ) && false === strpos( $component_src, 'bhp_mariana_popup' ),
	'§9.3 neither funnel\'s storage keys are touched (funnel isolation, standing rule §9)',
	$failures
);
// The post's stored content is never modified.
bhp_bpt_assert(
	false === strpos( $component_src, 'wp_update_post' ) && false === strpos( $component_src, 'wp_insert_post' )
		&& false === strpos( $component_src, 'update_post_meta' ) && false === strpos( $component_src, '$wpdb' ),
	'§9.4 the component writes nothing: no post, meta or direct query anywhere in it',
	$failures
);

echo "\n=====================================================\n";
if ( empty( $failures ) ) {
	echo "ALL ASSERTIONS PASSED\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( count( $failures ) . ' assertion(s) failed.' );
	}
}
