<?php
/**
 * Brave Hearts — THE PRODUCT TEMPLATE, Direction 1 step 3.
 *
 * CYCLE165-LD-DIRECTION1-STEP3-PRODUCT (2026-08-19, theme 1.19.262).
 * Direction 1, "Expedition field notes", board build step 3 of 4.
 *
 * Run via WP-CLI:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-product-template.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * WHAT THIS SUITE PROVES, AND WHAT IT DELIBERATELY CANNOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * PROVES, from the SERVED DOCUMENT and from the stylesheet, not from a guess:
 *   §1  the component is loaded, behind one filter, default ON
 *   §2  the four product pages each serve exactly ONE above-fold buy control
 *       at the markup level: the header offer is suppressed and the page's own
 *       Add-to-Cart is present
 *   §3  the reorder exists in the shipped CSS, is confined to <=600px, and
 *       names every block the board's order needs
 *   §4  the shipping sentence carries the cost, has no "we"/"us"/"our" and no
 *       em dash, and contains NO price literal in source
 *   §5  the five review-form rating labels are em-dash free, and the real
 *       Amazon review is byte-untouched (§9.1a)
 *   §6  every gallery thumbnail carries alt text, taken from the registry
 *   §7  the slide counter is no longer inside the stage
 *   §8  the visit-variant is still paperback-only
 *   §9  no price literal anywhere in the step-3 source
 *
 * ⛔ CANNOT PROVE, STATED RATHER THAN GLOSSED. This suite reads markup, PHP and
 *    CSS text. It does NOT prove that Add-to-Cart lands above 844 px at 390,
 *    that the counter no longer overlaps the byline, that nothing overflows, or
 *    that the reorder paints in the intended order. Those are BROWSER facts and
 *    were measured separately in headless Chrome at an asserted
 *    `window.innerWidth`, filed at `Business OS\WORKING-DRAFTS\lead-developer\
 *    CYCLE165-direction1-step3-qa\`. A markup test that claimed them would be a
 *    fabricated verification.
 *
 * ⛔ NOTHING IS WRITTEN. No product, price, variation, coupon, stock level,
 *    shipping setting, tax setting, payment setting, cart, order, post, page,
 *    option, attachment, review or user is created, read for mutation, or
 *    modified by any line in this file. §1 and §8 add filters and remove them.
 *
 * @package brave-hearts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = array();

function bhp_pt_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

function bhp_pt_skip( $label ) {
	echo "SKIP: {$label}\n";
}

/** Fetch a rendered document, or '' on any failure. */
function bhp_pt_fetch( $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 45, 'sslverify' => false ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	return (string) wp_remote_retrieve_body( $res );
}

$tpl_dir  = get_template_directory();
$css_path = $tpl_dir . '/assets/css/product-template.css';
$css      = file_exists( $css_path ) ? (string) file_get_contents( $css_path ) : '';
$php_src  = (string) file_get_contents( $tpl_dir . '/inc/product-template.php' );

echo "\n=== §1 — THE COMPONENT IS LOADED, BEHIND ONE FILTER ===\n";

bhp_pt_assert(
	function_exists( 'bhp_product_template_enabled' )
		&& function_exists( 'bhp_product_template_active' )
		&& function_exists( 'bhp_product_ages_mark_html' )
		&& function_exists( 'bhp_product_template_enqueue' ),
	'§1.1 all four component functions are loaded from inc/product-template.php',
	$failures
);
bhp_pt_assert( bhp_product_template_enabled(), '§1.2 the component filter defaults to ON', $failures );

add_filter( 'bhp_product_template_enabled', '__return_false' );
bhp_pt_assert( ! bhp_product_template_enabled(), '§1.3 the filter switches the component off', $failures );
bhp_pt_assert( ! bhp_product_template_active(), '§1.4 with the filter off the template is never active', $failures );
remove_filter( 'bhp_product_template_enabled', '__return_false' );
bhp_pt_assert( bhp_product_template_enabled(), '§1.5 removing the filter restores the default ON', $failures );

$mark = bhp_product_ages_mark_html();
bhp_pt_assert(
	false !== strpos( $mark, '<svg' ) && false !== strpos( $mark, 'aria-hidden="true"' ),
	'§1.6 the age mark is an inline SVG and is marked decorative',
	$failures
);
bhp_pt_assert(
	false !== strpos( $mark, 'stroke="currentColor"' ) && false === strpos( $mark, '#071522' ),
	'§1.7 the mark inherits currentColor rather than pinning the source literal',
	$failures
);
bhp_pt_assert(
	false === strpos( $mark, '<image' ) && false === strpos( $mark, 'href' ),
	'§1.8 the mark is path data only: nothing generated, nothing embedded',
	$failures
);

echo "\n=== §2 — ONE ABOVE-FOLD BUY CONTROL PER PRODUCT PAGE ===\n";

/*
 * ⭐ THE RULE, AND WHY IT LIVES IN THIS SUITE. FD-479 limb 3 allows exactly one
 *    primary CTA above the fold, and CRO rubric row 1 spells out the failure
 *    mode: "A second buy CTA above the fold = FAIL (replace, do not add)."
 *    Step 3 gives the product page its own above-fold Add-to-Cart, so the
 *    sitewide header offer must leave. This asserts BOTH halves — the one that
 *    arrived and the one that left — because asserting only one of them would
 *    pass on a page with none and on a page with two.
 */
$product_ids = get_posts(
	array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => 20,
		'fields'      => 'ids',
		'orderby'     => 'ID',
		'order'       => 'ASC',
	)
);

$pages = array();
foreach ( $product_ids as $pid ) {
	$url = get_permalink( $pid );
	if ( ! $url ) {
		continue;
	}
	/*
	 * The three HARDCOVER product URLs 301 to their paperback, so fetching them
	 * would test the same document three more times and inflate the pass count
	 * with duplicates. Canonical pages only.
	 */
	if ( false !== strpos( (string) get_post_field( 'post_name', $pid ), 'hardcover' ) ) {
		continue;
	}
	$pages[ $pid ] = $url;
}

bhp_pt_assert( count( $pages ) >= 1, sprintf( '§2.0 %d canonical product page(s) resolved to test', count( $pages ) ), $failures );

foreach ( $pages as $pid => $url ) {
	$html = bhp_pt_fetch( $url );
	$name = get_post_field( 'post_name', $pid );
	if ( '' === $html ) {
		bhp_pt_skip( "§2.{$name} the page could not be fetched from this environment" );
		continue;
	}

	bhp_pt_assert(
		0 === preg_match_all( '/class="bhp-header-offer"/', $html ),
		"§2.{$name} the sitewide header offer is SUPPRESSED on a product page",
		$failures
	);

	/*
	 * The page's own primary. Six canonical editions render the format
	 * selector's CTA; a product with no approved media (the Adventure Activity
	 * Book) renders WooCommerce's own single_add_to_cart_button. Either counts,
	 * and at least one must be there — a page with neither has no way to buy.
	 */
	$has_format_cta = 1 === preg_match_all( '/data-bhp-format-cta/', $html );
	$has_native_cta = 0 < preg_match_all( '/single_add_to_cart_button/', $html );
	bhp_pt_assert(
		$has_format_cta || $has_native_cta,
		"§2.{$name} the page carries its own add-to-cart control",
		$failures
	);

	bhp_pt_assert(
		false !== strpos( $html, 'assets/css/product-template.css' )
			|| false !== strpos( $html, 'assets/css/product-template.min.css' ),
		"§2.{$name} the step-3 stylesheet is enqueued on this page",
		$failures
	);

	bhp_pt_assert(
		false !== strpos( $html, 'bhp-product-value-prop__mark' ),
		"§2.{$name} the one drawn mark renders beside the age line",
		$failures
	);
}

/*
 * ...and the offer must still render everywhere it did before. A suppression
 * rule that leaked would be the most expensive possible regression here: it
 * would remove the only buy path on the templates step 1 exists for.
 */
$blog_ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
if ( ! empty( $blog_ids ) ) {
	$blog_html = bhp_pt_fetch( get_permalink( $blog_ids[0] ) );
	if ( '' !== $blog_html ) {
		bhp_pt_assert(
			1 === preg_match_all( '/class="bhp-header-offer"/', $blog_html ),
			'§2.blogpost the header offer still renders exactly once off the product pages',
			$failures
		);
	} else {
		bhp_pt_skip( '§2.blogpost the blog post could not be fetched from this environment' );
	}
}

echo "\n=== §3 — THE REORDER IS IN THE SHIPPED CSS AND IS CONFINED TO <=600px ===\n";

bhp_pt_assert( '' !== $css, '§3.0 assets/css/product-template.css exists and is readable', $failures );

$min_path = $tpl_dir . '/assets/css/product-template.min.css';
bhp_pt_assert( file_exists( $min_path ), '§3.1 the minified artefact was built and ships beside the source', $failures );

/*
 * ⛔ THE CONFINEMENT ASSERTION IS THE IMPORTANT ONE IN THIS SECTION. The brief
 *    is explicit that desktop keeps the current layout. Every reorder rule must
 *    therefore live inside the <=600px block. This finds the block, then checks
 *    that no `order:` declaration exists anywhere outside it.
 */
$mq_pos = strpos( $css, '@media (max-width: 600px)' );
bhp_pt_assert( false !== $mq_pos, '§3.2 the stylesheet opens a max-width:600px block', $failures );

if ( false !== $mq_pos ) {
	$before_mq = substr( $css, 0, $mq_pos );
	bhp_pt_assert(
		0 === preg_match( '/^\s*order\s*:/mi', $before_mq ),
		'§3.3 no `order` declaration exists before the 600px block, so desktop cannot be reordered',
		$failures
	);
}

/*
 * The blocks the board's order names. A missing selector here is a silently
 * mis-ordered first screen, which is exactly the defect this step fixes.
 */
$needed = array(
	'.product_title'                      => 'the title',
	'.bhp-product-value-prop__age'        => 'the age line',
	'.bhp-formats'                        => 'the buy box',
	'.amazon-reviews-product-section'     => 'the real Amazon review',
	'.bhp-media-gallery--hero'            => 'the theme gallery',
	'.woocommerce-product-gallery'        => 'the native gallery (Activity Book)',
	'.bhp-product-value-prop__hook'       => 'the hook sentence',
	'.woocommerce-breadcrumb'             => 'the compact breadcrumb',
	'.bhp-gallery__inspect-hint'          => 'the enlarge pill',
);
foreach ( $needed as $sel => $what ) {
	bhp_pt_assert(
		false !== strpos( $css, $sel ),
		"§3.4 the reorder names {$what} (`{$sel}`)",
		$failures
	);
}

bhp_pt_assert(
	false !== strpos( $css, 'display: contents' ),
	'§3.5 `.summary` is promoted with display:contents, which is what lets the review sit between the button and the gallery',
	$failures
);

/*
 * ⛔ NOTHING IS HIDDEN AT THE MOBILE BREAKPOINT except the hover-only enlarge
 *    pill, which cannot be reached on a touch screen. Any other `display:none`
 *    would mean copy was suppressed rather than moved, and that is a content
 *    decision this build was not given.
 */
$display_nones = preg_match_all( '/display:\s*none/i', $css );
bhp_pt_assert(
	1 === $display_nones,
	sprintf( '§3.6 exactly one `display:none` in the whole stylesheet, and it is the hover-only pill (found %d)', $display_nones ),
	$failures
);

echo "\n=== §4 — THE SHIPPING SENTENCE ===\n";

if ( ! function_exists( 'bhp_book_ship_note_single' ) ) {
	bhp_pt_skip( '§4 inc/book-formats.php is not loaded on this environment' );
} else {
	$dollar = bhp_book_ship_note_single( 1.99 );
	$free   = bhp_book_ship_note_single( 0.00 );

	/*
	 * ⛔ THE CASE MATTERS HERE AND THE FIRST VERSION OF THIS ASSERTION GOT IT
	 *    WRONG, so the correction is recorded rather than quietly applied. A
	 *    case-INSENSITIVE `/\bus\b/` matches "contiguous US" -- the country, not
	 *    the pronoun -- and reported a §9.1 breach in a sentence that has none.
	 *    "we" and "our" are safe case-insensitively (no English word or code
	 *    collides); "us" is only ever a pronoun in lower case on this site, and
	 *    "US" is always the country. Same rule, correctly scoped.
	 */
	foreach ( array( 'dollar' => $dollar, 'free' => $free ) as $branch => $text ) {
		bhp_pt_assert(
			0 === preg_match( '/\b(we|our)\b/i', $text ) && 0 === preg_match( '/\bus\b/', $text ),
			"§4.1.{$branch} no \"we\", \"us\" or \"our\" (standing rule §9.1; \"US\" the country is not the pronoun)",
			$failures
		);
		bhp_pt_assert(
			false === strpos( $text, "\xE2\x80\x94" ),
			"§4.2.{$branch} no em dash",
			$failures
		);
		bhp_pt_assert(
			0 === preg_match( '/\b(5-9|5 to 9|ages 5)/i', $text ),
			"§4.3.{$branch} no 5-9 reading age",
			$failures
		);
	}

	bhp_pt_assert(
		1 === preg_match( '/\$\d+\.\d{2}/', $dollar ),
		'§4.4 the dollar branch states the cost (CRO rubric row 7)',
		$failures
	);
	bhp_pt_assert(
		false === strpos( $free, '$' ),
		'§4.5 the free branch states no dollar figure',
		$failures
	);

	/*
	 * ⛔ THE FIGURE IS THE PLUGIN'S, NOT THE THEME'S. A literal in the sentence
	 *    would be a price claim that survives a tier change, which is the exact
	 *    failure `bhp_book_ship_note_single()` was extracted to prevent.
	 */
	if ( function_exists( 'bhp_bundle_single_shipping' ) ) {
		$live_pb = (float) bhp_bundle_single_shipping( 'paperback' );
		bhp_pt_assert(
			false !== strpos( bhp_book_ship_note_single( $live_pb ), '$' . number_format( $live_pb, 2 ) )
				|| bhp_book_shipping_is_free( $live_pb ),
			sprintf( '§4.6 the sentence renders the LIVE paperback figure (%s)', number_format( $live_pb, 2 ) ),
			$failures
		);
	} else {
		bhp_pt_skip( '§4.6 the bundle plugin is not active on this environment' );
	}

	/*
	 * The collection-free sentence is a LIVE read and must vanish when it stops
	 * being true. It cannot be asserted to a fixed string: asserting "it says
	 * free" would hard-code today's tier table into a test.
	 */
	if ( function_exists( 'bhp_book_collection_free_ship_note' ) && function_exists( 'bhp_book_collection_ships_free' ) ) {
		$note = bhp_book_collection_free_ship_note();
		bhp_pt_assert(
			bhp_book_collection_ships_free() ? ( '' !== $note ) : ( '' === $note ),
			'§4.7 the collection-ships-free sentence tracks the live tier table in both directions',
			$failures
		);
		bhp_pt_assert(
			'' === $note || ( 0 === preg_match( '/\b(we|our)\b/i', $note ) && 0 === preg_match( '/\bus\b/', $note ) ),
			'§4.8 the collection sentence carries no "we", "us" or "our"',
			$failures
		);
	}
}

echo "\n=== §5 — RATING LABELS, AND THE CUSTOMER'S WORDS ===\n";

$form_src = (string) file_get_contents( $tpl_dir . '/template-parts/reviews/review-form.php' );

/*
 * The labels are OURS. §9.1a's carve-out protects a customer's words, not site
 * chrome, and conflating the two is what this assertion documents. The source
 * still QUOTES the superseded strings in a comment so the movement is visible,
 * so the executable half is isolated before searching.
 */
$form_code = preg_replace( '#/\*.*?\*/#s', '', $form_src );
bhp_pt_assert(
	1 === preg_match( "/'5 stars: loved it'/", $form_code ),
	'§5.1 the 5-star label uses a colon',
	$failures
);
bhp_pt_assert(
	0 === preg_match( '/\d stars? \xE2\x80\x94/', $form_code ),
	'§5.2 no rating label carries an em dash any more',
	$failures
);
foreach ( array( '4 stars: really good', '3 stars: it was okay', '2 stars: not for us', '1 star: did not work for us' ) as $lbl ) {
	bhp_pt_assert(
		false !== strpos( $form_code, $lbl ),
		"§5.3 the label \"{$lbl}\" renders with a colon",
		$failures
	);
}

/*
 * ⛔⛔ THE REAL AMAZON REVIEW IS NEVER EDITED (§9.1a). Its "we" is a customer's
 *     word and changing it would fabricate a customer statement. This asserts
 *     that no pass has "fixed" it: the showcase must still carry a first-person
 *     plural somewhere in its quoted text, because the live review contains one.
 */
/*
 * ⛔ THE PAGE IS NAMED, NOT GUESSED, AND THE FIRST VERSION OF THIS ASSERTION
 *    GOT IT WRONG. It fetched whichever product happened to sort first and
 *    looked for the Mariana review there, so it failed on a page that never
 *    carried that review -- a red result with nothing behind it, which is worse
 *    than no test. The review with the customer's "we" is on THE MARIANA
 *    TRENCH; The Amazon carries no Amazon review section at all, because it has
 *    no real reviews yet and none is ever invented (Standing Rules §3).
 */
$mariana_url = '';
foreach ( $pages as $pid => $url ) {
	if ( false !== strpos( (string) get_post_field( 'post_name', $pid ), 'mariana' ) ) {
		$mariana_url = $url;
		break;
	}
}
if ( '' !== $mariana_url ) {
	$mariana_html = bhp_pt_fetch( $mariana_url );
	if ( '' !== $mariana_html && false !== strpos( $mariana_html, 'amazon-reviews-product-section' ) ) {
		bhp_pt_assert(
			1 === preg_match( '/We read a few chapters each night/', $mariana_html ),
			'§5.4 the real Amazon review is served verbatim, its "we" untouched (§9.1a)',
			$failures
		);
	} else {
		bhp_pt_skip( '§5.4 the Amazon review section is not present on this environment' );
	}
} else {
	bhp_pt_skip( '§5.4 the Mariana product page did not resolve on this environment' );
}

echo "\n=== §6 — GALLERY THUMBNAIL ALT TEXT ===\n";

$look_src = (string) file_get_contents( $tpl_dir . '/template-parts/commerce/look-inside.php' );

bhp_pt_assert(
	false !== strpos( $look_src, 'alt="<?php echo esc_attr($thumb_alt); ?>"' ),
	'§6.1 the video thumbnail prints the registry label instead of an empty alt',
	$failures
);
bhp_pt_assert(
	false !== strpos( $look_src, "'alt'      => \$thumb_alt," ),
	'§6.2 the image thumbnail passes the registry alt through',
	$failures
);

foreach ( $pages as $pid => $url ) {
	$html = bhp_pt_fetch( $url );
	$name = get_post_field( 'post_name', $pid );
	if ( '' === $html ) {
		continue;
	}
	$empty_alt_thumbs = preg_match_all( '/<img[^>]*class="[^"]*bhp-gallery__thumb-img[^"]*"[^>]*alt=""/', $html )
		+ preg_match_all( '/<img[^>]*alt=""[^>]*class="[^"]*bhp-gallery__thumb-img[^"]*"/', $html );
	bhp_pt_assert(
		0 === $empty_alt_thumbs,
		"§6.3.{$name} no gallery thumbnail is served with an empty alt (found {$empty_alt_thumbs})",
		$failures
	);
}

echo "\n=== §7 — THE SLIDE COUNTER IS OUT OF THE STAGE ===\n";

/*
 * A geometric collision is a browser fact and is measured in the QA evidence,
 * not here. What CAN be proved from source is the structural cause: the counter
 * is no longer a child of `.bhp-gallery__stage`, so it cannot be painted over
 * the artwork at any viewport, on any cover.
 */
$stage_open  = strpos( $look_src, 'class="bhp-gallery__stage"' );
$counter_pos = strpos( $look_src, 'class="bhp-gallery__counter"' );

/*
 * The stage is closed by a `</div>` at four-space indent. Asserting that one
 * appears BETWEEN the stage's opening tag and the counter is what proves the
 * counter is a sibling of the stage rather than a child of it — which is the
 * structural reason it can no longer be painted over the artwork.
 */
$between = ( false !== $stage_open && false !== $counter_pos && $counter_pos > $stage_open )
	? substr( $look_src, $stage_open, $counter_pos - $stage_open )
	: '';

bhp_pt_assert(
	'' !== $between && false !== strpos( $between, "\n    </div>" ),
	'§7.1 the counter markup sits after the stage closes, not inside it',
	$failures
);
bhp_pt_assert(
	false !== strpos( $look_src, 'data-bhp-gallery-current' ),
	'§7.2 the live-number hook the gallery script reads is carried across unchanged',
	$failures
);

$media_css = (string) file_get_contents( $tpl_dir . '/assets/css/book-media.css' );
if ( preg_match( '/\n\.bhp-gallery__counter\s*\{([^}]*)\}/', $media_css, $m_counter ) ) {
	bhp_pt_assert(
		false === strpos( $m_counter[1], 'position: absolute' ),
		'§7.3 the counter is no longer absolutely positioned',
		$failures
	);
} else {
	bhp_pt_assert( false, '§7.3 the .bhp-gallery__counter rule could not be located in book-media.css', $failures );
}

if ( preg_match( '/\.bhp-gallery__inspect-hint\s*\{([^}]*)\}/', $media_css, $m_hint ) ) {
	bhp_pt_assert(
		false === strpos( $m_hint[1], 'bottom:' ),
		'§7.4 the enlarge pill no longer anchors to the bottom of the slide, where the byline is',
		$failures
	);
} else {
	bhp_pt_assert( false, '§7.4 the .bhp-gallery__inspect-hint rule could not be located', $failures );
}

echo "\n=== §8 — THE VISIT VARIANT IS STILL PAPERBACK ONLY ===\n";

/*
 * ⛔ THE PREDICATE IS NOT REACHABLE FROM WP-CLI and this suite does not pretend
 *    it is: `bhp_school_visit_paperback_only()` needs a live WooCommerce session
 *    flag and there is none here. Faking one would write state a test must never
 *    write. The DECISION is provable, through the filter the restriction reads,
 *    and the browser walk of a real `?bhp_visit=` link is filed in the QA
 *    evidence. Neither is claimed on the other's evidence.
 */
if ( ! function_exists( 'bhp_book_available_formats' ) ) {
	bhp_pt_skip( '§8 inc/book-formats.php is not loaded on this environment' );
} else {
	$available = bhp_book_available_formats();

	bhp_pt_assert(
		in_array( 'paperback', $available, true ) && in_array( 'hardcover', $available, true ),
		'§8.1 an ordinary shopper is still offered both physical formats',
		$failures
	);

	/*
	 * ⭐ THE ASSERTION THAT ACTUALLY PROTECTS THE VISIT VARIANT. The restriction
	 *    is the PLUGIN's; the theme must never hold a second copy of the answer.
	 *    Asserting that the theme's list is byte-identical to the plugin's is what
	 *    keeps a paperback-only session safe from a theme-side hardcode, including
	 *    one this step could have introduced while reordering the buy box.
	 */
	if ( function_exists( 'bhp_bundle_available_format_order' ) ) {
		bhp_pt_assert(
			array_values( (array) bhp_bundle_available_format_order() ) === array_values( $available ),
			'§8.2 the theme DELEGATES the format list to the plugin rather than holding its own',
			$failures
		);
	} else {
		bhp_pt_skip( '§8.2 the bundle plugin is not active on this environment' );
	}

	/*
	 * And the rendered selector must emit exactly those formats. A control that
	 * exists in the DOM is reachable by keyboard, by a screen reader and by
	 * anything that ignores CSS, so "hidden" is not "absent" -- 1.19.240's own
	 * reasoning, re-asserted here because a reorder is exactly the kind of pass
	 * that could reintroduce a hidden-but-present card.
	 */
	if ( ! empty( $pages ) ) {
		$sel_html = bhp_pt_fetch( reset( $pages ) );
		if ( '' !== $sel_html && false !== strpos( $sel_html, 'data-bhp-formats' ) ) {
			foreach ( array( 'paperback', 'hardcover' ) as $fmt ) {
				$present = 0 < preg_match_all( '/data-bhp-format="' . $fmt . '"/', $sel_html );
				bhp_pt_assert(
					$present === in_array( $fmt, $available, true ),
					"§8.3 the rendered selector emits the {$fmt} card if and only if this request may buy it",
					$failures
				);
			}
		} else {
			bhp_pt_skip( '§8.3 the format selector is not present on this environment' );
		}
	}

	/*
	 * ⛔ THE SESSION PREDICATE IS NOT REACHABLE FROM WP-CLI AND THIS SUITE DOES
	 *    NOT PRETEND IT IS. `bhp_school_visit_paperback_only()` returns false and
	 *    never reaches its own filter unless the request carries a live visit flag
	 *    in the WooCommerce session; there is no session here, so the restricted
	 *    branch cannot be entered without faking session state, which a test must
	 *    never write. The restricted path was walked in a REAL BROWSER on a live
	 *    `?bhp_visit=` link at an asserted innerWidth of 390 and is filed in the
	 *    step-3 QA evidence. Recorded as SKIP rather than asserted, because a
	 *    vacuous pass here would be a fabricated check.
	 */
	bhp_pt_skip( '§8.4 the restricted-session branch (no WooCommerce session under WP-CLI; walked in a real browser instead, see CYCLE165-direction1-step3-qa)' );
}

echo "\n=== §9 — NO PRICE LITERAL IN THE STEP-3 SOURCE ===\n";

/*
 * The docblocks legitimately quote measured pixel figures and superseded copy,
 * so comments are stripped before the search rather than the search being
 * weakened. `$` followed by digits and two decimals, anywhere in executable
 * code, is the failure this looks for.
 */
foreach (
	array(
		'inc/product-template.php'                => $php_src,
		'assets/css/product-template.css'         => $css,
		'template-parts/reviews/review-form.php'  => $form_src,
	) as $file => $src
) {
	$code_only = preg_replace( '#/\*.*?\*/#s', '', $src );
	$code_only = preg_replace( '#^\s*//.*$#m', '', $code_only );
	bhp_pt_assert(
		0 === preg_match( '/\$\s?\d+\.\d{2}/', $code_only ),
		"§9.1 {$file} contains no price literal",
		$failures
	);
}

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL PASS\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
