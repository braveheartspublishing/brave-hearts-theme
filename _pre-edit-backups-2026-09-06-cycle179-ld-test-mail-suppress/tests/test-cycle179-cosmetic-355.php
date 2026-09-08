<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 1.19.355 / PLUGIN 1.8.80 — THE COSMETIC RELEASE. `CYCLE179-LD-355`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The standing gates for items 2 to 7 of `BRIEF-CYCLE179-LD-354-COSMETIC.md`.
 * Item 1 shipped as 1.19.354 and has its own suite
 * (`test-cycle179-author-visits-fold-354.php`), which is not duplicated here.
 *
 *   §1  item 7  the bundle saving is COMPUTED from live prices, and fails
 *               closed to silence rather than to a stale literal
 *   §2  item 2  the PDP format block: 10px above the label, and a one-card
 *               rail is not rendered at all
 *   §3  item 3  the visit-band card counter travels with the button
 *   §4  item 4  one <h1> per page, and no coordinate on a utility page
 *   §5  item 5  the account form is on the palette
 *   §6  item 6  no em dash in the thank-you note
 *   §7  LD-12   the specificity note exists where the trap is
 *
 * ⛔ WHAT THIS SUITE CANNOT DO, STATED SO IT IS NOT MISTAKEN FOR WHAT IT DOES.
 *    CSS is not evaluated by PHP, so §2, §3, §5 and §7 assert that the shipped
 *    rules EXIST IN THE ARTEFACT and are shaped correctly. ⭐ THE GEOMETRY
 *    ITSELF IS PROVED IN A REAL BROWSER, at asserted `window.innerWidth` and
 *    `innerHeight`, and those measurements live in the release record, not
 *    here. `CYCLE179-LD-354` is the standing lesson: a CSS rule that reads
 *    correctly can still compute to nothing, and only the deployed page says
 *    which.
 *
 * ⛔ IT WRITES NOTHING: no option, no session, no cookie, no cart, no order, no
 *    product, no price, no stock, no shipping setting, no registry row. Item 7
 *    READS live product prices through WooCommerce; it never writes one.
 *
 * ⭐ INVOCATION, WITH `--url=` (`CYCLE179-LD-9`):
 *
 *      wp eval-file wp-content/themes/<slug>/tests/test-cycle179-cosmetic-355.php \
 *        --url=<site> --user=1
 *
 * @package Brave_Hearts
 * @since   1.19.355
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['c355_pass']    = 0;
$GLOBALS['c355_fail']    = 0;
$GLOBALS['c355_skipped'] = 0;

/**
 * One assertion.
 *
 * @param bool   $cond  The thing that must be true.
 * @param string $label What it means in words.
 * @return void
 */
function c355_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['c355_pass'];
		echo "  PASS  {$label}\n";
		return;
	}
	++$GLOBALS['c355_fail'];
	echo "  FAIL  {$label}\n";
}

/**
 * A check that could not be performed, recorded as not performed.
 *
 * @param string $label  What was not checked.
 * @param string $reason Why not.
 * @return void
 */
function c355_skip( $label, $reason ) {
	++$GLOBALS['c355_skipped'];
	echo "  SKIP  {$label}  --  {$reason}\n";
}

/**
 * Force the computed saving to empty, so §1.7 can prove the empty case.
 *
 * ⚠️ DECLARED UP HERE, NOT BESIDE ITS USE. `wp eval-file` runs this file's body
 *    INSIDE A FUNCTION, so a nested `function` declaration does not exist until
 *    execution reaches it. A copy declared after the `add_filter()` that names
 *    it would be an undefined callback at the moment the filter fires.
 *
 * @return string
 */
function c355_force_empty_saving() {
	return '';
}

/**
 * Read a theme file, or '' when it is not there.
 *
 * @param string $rel Path relative to the theme root.
 * @return string
 */
function c355_theme_src( $rel ) {
	$path = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) file_get_contents( $path ) : '';
}

echo "\n=== CYCLE179-LD-355 · the cosmetic release (theme 1.19.355, plugin 1.8.80) ===\n";

/* =========================================================================
 * §0 · VERSIONS
 * ====================================================================== */

echo "\n=== §0 · VERSIONS ===\n";

$c355_theme_version = function_exists( 'wp_get_theme' ) ? (string) wp_get_theme()->get( 'Version' ) : '';
c355_assert(
	'' !== $c355_theme_version && version_compare( $c355_theme_version, '1.19.355', '>=' ),
	'0.1 the active theme is at least 1.19.355 (' . ( '' === $c355_theme_version ? 'unknown' : $c355_theme_version ) . ')'
);
c355_assert(
	defined( 'BHP_BUNDLE_PRICING_VERSION' ) && version_compare( BHP_BUNDLE_PRICING_VERSION, '1.8.80', '>=' ),
	'0.2 the bundle plugin is at least 1.8.80 (' . ( defined( 'BHP_BUNDLE_PRICING_VERSION' ) ? BHP_BUNDLE_PRICING_VERSION : 'undefined' ) . ')'
);

/* =========================================================================
 * §1 · ITEM 7 — THE SAVING IS COMPUTED FROM LIVE PRICES
 * ====================================================================== */

echo "\n=== §1 · ITEM 7 · THE COMPUTED SAVING ===\n";

c355_assert( function_exists( 'bhp_bundle_saving_label' ), '1.1 ⭐ 1.8.80 the computed saving function exists' );
c355_assert( function_exists( 'bhp_bundle_box_heading' ), '1.2 ⭐ 1.8.80 the heading composer exists, so a suppressed badge cannot leave a dangling separator' );

if ( ! function_exists( 'bhp_bundle_saving_label' ) || ! function_exists( 'bhp_bundle_rules' ) ) {
	c355_skip( '1.x the saving behaviour', 'the plugin functions are not loaded in this process' );
} else {
	$c355_pb2 = bhp_bundle_saving_label( 'paperback', 2 );
	$c355_pb3 = bhp_bundle_saving_label( 'paperback', 3 );
	$c355_hc2 = bhp_bundle_saving_label( 'hardcover', 2 );

	echo "        computed today: paperback tier2 = '{$c355_pb2}'  tier3 = '{$c355_pb3}'  hardcover tier2 = '{$c355_hc2}'\n";

	/*
	 * ⛔ THE SHAPE IS ASSERTED, NOT A LITERAL AMOUNT. Asserting "Save $1.99"
	 *    here would re-introduce exactly the build-time constant this release
	 *    removed, one layer down, and the suite would then have to be edited
	 *    every time Andrew re-prices a title. The amount is checked against the
	 *    approved table below instead, which is where the number lives.
	 */
	c355_assert(
		'' === $c355_pb2 || 1 === preg_match( '/^Save \$\d+\.\d{2}$/', $c355_pb2 ),
		'1.3 the paperback any-2 saving is either a well-formed amount or nothing at all'
	);

	$c355_rules = bhp_bundle_rules( 'paperback' );
	$c355_want  = 'Save $' . number_format( (float) $c355_rules[2]['discount'], 2 );
	c355_assert(
		'' === $c355_pb2 || $c355_pb2 === $c355_want,
		'1.4 when it states a saving it is the approved tier discount (' . $c355_want . '), never an invented figure'
	);

	/*
	 * ⭐ THE ONE PROPERTY THAT MATTERS: the badge and the cart cannot disagree.
	 *    `bhp_bundle_apply_discount_fees()` refuses the discount when a live
	 *    line price no longer matches `bhp_bundle_expected_price()`, so the
	 *    badge must be silent under exactly that condition.
	 */
	if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'bhp_bundle_catalog' ) || ! function_exists( 'bhp_bundle_expected_price' ) ) {
		c355_skip( '1.5 badge/cart agreement', 'WooCommerce or the catalogue is not available in this process' );
	} else {
		$c355_catalog  = bhp_bundle_catalog();
		$c355_expected = (float) bhp_bundle_expected_price( 'paperback' );
		$c355_all_ok   = true;
		$c355_seen     = array();

		foreach ( $c355_catalog['paperback'] as $c355_info ) {
			$c355_product = wc_get_product( (int) $c355_info['product_id'] );
			$c355_price   = $c355_product ? (float) $c355_product->get_price() : -1.0;
			$c355_seen[]  = number_format( $c355_price, 2 );
			if ( $c355_price <= 0 || abs( $c355_price - $c355_expected ) >= 0.005 ) {
				$c355_all_ok = false;
			}
		}

		echo '        live paperback prices: ' . implode( ', ', $c355_seen ) . " (expected {$c355_expected})\n";

		c355_assert(
			$c355_all_ok ? ( '' !== $c355_pb2 ) : ( '' === $c355_pb2 ),
			'1.5 ⭐ the badge states a saving IF AND ONLY IF the live prices are the ones the cart will apply the discount under'
		);
	}

	/*
	 * ⛔ THE HEADING NEVER ENDS IN A DANGLING SEPARATOR, whichever way the
	 *    badge goes. This is the failure the composer exists to prevent.
	 */
	foreach ( array( 'paperback', 'hardcover' ) as $c355_fmt ) {
		foreach ( array( 2, 3 ) as $c355_tier ) {
			$c355_head = bhp_bundle_box_heading( $c355_fmt, $c355_tier );
			c355_assert(
				'' !== $c355_head && ' - ' !== substr( $c355_head, -3 ) && '-' !== substr( $c355_head, -1 ),
				"1.6.{$c355_fmt}.{$c355_tier} the {$c355_fmt} tier-{$c355_tier} heading is non-empty and ends in no dangling separator"
			);
		}
	}

	/*
	 * ⛔ THE FILTER IS A DISPLAY SEAM AND MUST NOT BE ABLE TO CREATE A DISCOUNT.
	 *    Proved by exercising it rather than by asserting the docblock.
	 */
	add_filter( 'bhp_bundle_saving_label', 'c355_force_empty_saving', 99 );
	$c355_forced = bhp_bundle_box_heading( 'paperback', 2 );
	remove_filter( 'bhp_bundle_saving_label', 'c355_force_empty_saving', 99 );
	c355_assert(
		'' !== $c355_forced && false === strpos( $c355_forced, 'Save $' ),
		'1.7 forcing the badge empty leaves a clean heading and removes only the sentence'
	);

	// The shortcode call sites take the computed heading, not the literal.
	$c355_sc = '';
	if ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/bundle-shortcode.php' ) ) {
		$c355_sc = (string) file_get_contents( WP_PLUGIN_DIR . '/brave-hearts-bundle-pricing/includes/bundle-shortcode.php' );
	}
	if ( '' === $c355_sc ) {
		c355_skip( '1.8 the shortcode call sites', 'bundle-shortcode.php could not be read' );
	} else {
		c355_assert(
			2 === substr_count( $c355_sc, 'bhp_bundle_box_heading(' ),
			'1.8 both shortcode box headings go through the computed composer'
		);
		c355_assert(
			false === strpos( $c355_sc, "esc_html( \$rule['save'] )" ),
			'1.9 ⛔ neither shortcode heading prints the build-time literal any more'
		);
	}
}

/* =========================================================================
 * §2 · ITEM 2 — THE PDP FORMAT BLOCK
 * ====================================================================== */

echo "\n=== §2 · ITEM 2 · THE PDP FORMAT BLOCK ===\n";

$c355_bf = c355_theme_src( 'assets/css/book-formats.css' );
$c355_bfm = c355_theme_src( 'assets/css/book-formats.min.css' );

if ( '' === $c355_bf ) {
	c355_skip( '2.x the format CSS', 'assets/css/book-formats.css could not be read' );
} else {
	/*
	 * ⛔ THE RULE IS ASSERTED TOGETHER WITH ITS MEDIA SCOPE. A bare
	 *    `margin-top: 10px` on this selector would also beat the
	 *    screen-reader-only clip rule at <=782px, which is (0,1,0) against this
	 *    selector's (0,2,0). Asserting the scope is what stops that regression
	 *    returning as a "simplification".
	 */
	c355_assert(
		1 === preg_match( '/@media\s*\(min-width:\s*783px\)\s*\{\s*\.bhp-formats\s+\.bhp-formats__heading\s*\{\s*margin-top:\s*10px;/', $c355_bf ),
		'2.1 the 10px above CHOOSE YOUR FORMAT is declared, and only at >=783px where the label is visible'
	);
	c355_assert(
		false !== strpos( $c355_bf, 'position: absolute' ) && false !== strpos( $c355_bf, 'clip-path: inset(50%)' ),
		'2.2 the <=782px screen-reader-only treatment of that label is still present and was not traded away'
	);
}

if ( '' === $c355_bfm ) {
	c355_skip( '2.3 the built artefact', 'assets/css/book-formats.min.css could not be read' );
} else {
	c355_assert(
		false !== strpos( $c355_bfm, 'min-width:783px' ) || false !== strpos( $c355_bfm, 'min-width: 783px' ),
		'2.3 ⭐ the rule survived minification into the artefact that actually ships'
	);
}

$c355_cards = c355_theme_src( 'template-parts/commerce/format-cards.php' );
if ( '' === $c355_cards ) {
	c355_skip( '2.4 the one-card rail', 'template-parts/commerce/format-cards.php could not be read' );
} else {
	c355_assert(
		false !== strpos( $c355_cards, '$bhp_card_total' ) && false !== strpos( $c355_cards, 'if ($bhp_card_total > 1):' ),
		'2.4 ⭐ the grid is emitted only when there is more than one card'
	);
	/*
	 * ⛔ THE COUNT MUST BE BUILT FROM THE SAME THREE CONDITIONS THAT PRINT THE
	 *    CARDS, and BEFORE the element opens. A count computed after the div is
	 *    printed is the defect this change exists to remove.
	 */
	$c355_total_at = strpos( $c355_cards, '$bhp_card_total = count(' );
	$c355_div_at   = strpos( $c355_cards, '<div class="bhp-formats__grid"' );
	c355_assert(
		false !== $c355_total_at && false !== $c355_div_at && $c355_total_at < $c355_div_at,
		'2.5 the card count is computed BEFORE the grid element is opened'
	);
	c355_assert(
		1 === substr_count( $c355_cards, '<div class="bhp-formats__grid"' ),
		'2.6 there is still exactly one place that opens the format grid'
	);
	/*
	 * ⛔ A HIDDEN CONTROL IS NOT AN ACCEPTABLE SUBSTITUTE. 1.19.240's rule:
	 *    a control in the DOM is reachable by keyboard and by assistive tech
	 *    whatever CSS says.
	 */
	c355_assert(
		false === strpos( $c355_cards, 'bhp-formats__grid--hidden' )
			&& false === strpos( $c355_cards, 'style="display:none"' ),
		'2.7 ⛔ the one-card case removes the row rather than hiding it'
	);
}

/* =========================================================================
 * §3 · ITEM 3 — THE COUNTER TRAVELS WITH THE BUTTON
 * ====================================================================== */

echo "\n=== §3 · ITEM 3 · THE VISIT-BAND CARD COUNTER ===\n";

$c355_style = c355_theme_src( 'style.css' );
if ( '' === $c355_style ) {
	c355_skip( '3.x style.css', 'style.css could not be read' );
} else {
	c355_assert(
		1 === preg_match( '/li\.product\s*>\s*a\.woocommerce-loop-product__link\s*>\s*\.bhp-bundle-stock-counter\s*\{\s*margin-top:\s*auto;\s*\}/', $c355_style ),
		'3.1 the counter is pinned to the foot of the growing card link'
	);
	/*
	 * ⛔ SPECIFICITY IS THE WHOLE RISK HERE, and `CYCLE179-LD-354` lost a rule
	 *    to exactly this. The selector must carry the catalog-grid scope, which
	 *    is what puts it far above the plugin's own (0,1,0) counter rule.
	 */
	$c355_ctr_at = strpos( $c355_style, 'a.woocommerce-loop-product__link > .bhp-bundle-stock-counter' );
	$c355_line   = ( false === $c355_ctr_at ) ? '' : substr( $c355_style, max( 0, $c355_ctr_at - 200 ), 260 );
	c355_assert(
		false !== strpos( $c355_line, 'body.bhp-catalog-grid' ) && false !== strpos( $c355_line, '.woo-expedition-shell' ),
		'3.2 ⭐ it carries the catalog-grid scope, so it cannot lose to a later single-class rule'
	);
	c355_assert(
		false === strpos( $c355_line, '!important' ),
		'3.3 it wins on specificity rather than on !important'
	);
}

/*
 * ⛔ THE PLUGIN'S OWN COUNTER IS UNTOUCHED. `VISIT-SHOP-AUDIT.md` R6: the
 *    renderer, its label and its arithmetic are the plugin's and no template
 *    grows a copy.
 */
c355_assert( function_exists( 'bhp_visit_shelf_render_counter' ), '3.4 the plugin still owns the counter renderer' );

/* =========================================================================
 * §4 · ITEM 4 — ONE <h1>, AND NO COORDINATE ON A UTILITY PAGE
 * ====================================================================== */

echo "\n=== §4 · ITEM 4 · THE PLAIN PAGE HERO ===\n";

c355_assert( function_exists( 'bhp_page_drop_duplicate_h1' ), '4.1 ⭐ 1.19.355 the duplicate-heading remover exists' );
c355_assert( function_exists( 'bhp_page_hero_shows_coordinate' ), '4.2 ⭐ 1.19.355 the coordinate predicate exists' );
c355_assert( function_exists( 'bhp_page_normalise_heading' ), '4.3 the heading normaliser exists and is separately testable' );

if ( ! function_exists( 'bhp_page_drop_duplicate_h1' ) ) {
	c355_skip( '4.x the heading behaviour', 'inc/page-hero.php is not loaded in this process' );
} else {
	$c355_title = 'Read-Aloud Books & Classroom Resources';
	$c355_dupe  = '<h1>Read-Aloud Books &amp; Classroom Resources</h1>' . "\n" . '<p>Body copy.</p>';
	$c355_other = '<h1>Something Else Entirely</h1>' . "\n" . '<p>Body copy.</p>';

	c355_assert(
		false === strpos( bhp_page_drop_duplicate_h1( $c355_dupe, $c355_title ), '<h1' ),
		'4.4 ⭐ a content heading that repeats the title is removed, entity differences and all'
	);
	c355_assert(
		false !== strpos( bhp_page_drop_duplicate_h1( $c355_dupe, $c355_title ), '<p>Body copy.</p>' ),
		'4.5 the rest of the content is returned untouched'
	);
	c355_assert(
		bhp_page_drop_duplicate_h1( $c355_other, $c355_title ) === $c355_other,
		'4.6 ⛔ a heading that says something else is kept, byte for byte'
	);
	c355_assert(
		bhp_page_drop_duplicate_h1( '<p>No heading here.</p>', $c355_title ) === '<p>No heading here.</p>',
		'4.7 content with no h1 is returned unchanged'
	);
	c355_assert(
		bhp_page_drop_duplicate_h1( $c355_dupe, '' ) === $c355_dupe,
		'4.8 ⛔ an empty title removes nothing: it fails open'
	);
	c355_assert(
		1 === substr_count( bhp_page_drop_duplicate_h1( $c355_dupe . $c355_dupe, $c355_title ), '<h1' ),
		'4.9 only the FIRST matching heading is ever removed'
	);
	// Attributes, mixed case and inner markup must not defeat the match.
	c355_assert(
		false === strpos( bhp_page_drop_duplicate_h1( '<H1 class="x" id="y">Read-Aloud Books &amp; <em>Classroom</em> Resources</H1><p>x</p>', $c355_title ), '<H1' ),
		'4.10 attributes, mixed case and inner markup do not defeat the match'
	);
	c355_assert(
		bhp_page_drop_duplicate_h1( '<h10>Read-Aloud Books</h10>', 'Read-Aloud Books' ) === '<h10>Read-Aloud Books</h10>',
		'4.11 ⛔ it matches <h1> and not a tag that merely starts with it'
	);
}

if ( ! function_exists( 'bhp_page_hero_shows_coordinate' ) ) {
	c355_skip( '4.12 the coordinate predicate', 'inc/page-hero.php is not loaded in this process' );
} else {
	$c355_account = (int) get_option( 'woocommerce_myaccount_page_id', 0 );
	$c355_privacy = (int) get_option( 'wp_page_for_privacy_policy', 0 );

	if ( $c355_account > 0 ) {
		c355_assert( false === bhp_page_hero_shows_coordinate( $c355_account ), '4.12 the my-account page prints no coordinate' );
	} else {
		c355_skip( '4.12 the my-account page', 'woocommerce_myaccount_page_id is unset on this environment' );
	}
	if ( $c355_privacy > 0 ) {
		c355_assert( false === bhp_page_hero_shows_coordinate( $c355_privacy ), '4.13 the privacy policy prints no coordinate' );
	} else {
		c355_skip( '4.13 the privacy policy', 'wp_page_for_privacy_policy is unset on this environment' );
	}
	/*
	 * ⛔ AND AN ORDINARY PAGE STILL PRINTS IT. Without this the change could
	 *    have silently removed the ornament sitewide and still "passed".
	 */
	$c355_about = get_page_by_path( 'about' );
	if ( $c355_about instanceof WP_Post ) {
		c355_assert( true === bhp_page_hero_shows_coordinate( (int) $c355_about->ID ), '4.14 ⭐ an ordinary page still prints the coordinate' );
	} else {
		c355_assert( true === bhp_page_hero_shows_coordinate( 0 ), '4.14 ⭐ an unrecognised page id still prints the coordinate (fails open)' );
	}

	$c355_page = c355_theme_src( 'page.php' );
	c355_assert(
		'' !== $c355_page && false !== strpos( $c355_page, 'bhp_page_hero_shows_coordinate' ),
		'4.15 page.php actually calls the predicate'
	);
	c355_assert(
		'' !== $c355_page && false !== strpos( $c355_page, 'bhp_page_drop_duplicate_h1' ),
		'4.16 page.php actually calls the heading remover'
	);
	/*
	 * ⛔ NOT A `the_content` FILTER. `CLAUDE.md` and repo `docs/DECISIONS.md`
	 *    both record that the removed Teachers-page filter must not return, and
	 *    a global filter would also reach feeds, REST and excerpts.
	 */
	$c355_hero = c355_theme_src( 'inc/page-hero.php' );
	c355_assert(
		'' !== $c355_hero && false === strpos( $c355_hero, "add_filter( 'the_content'" ) && false === strpos( $c355_hero, "add_filter('the_content'" ),
		'4.17 ⛔ the fix registers no the_content filter'
	);
	c355_assert(
		'' !== $c355_hero && false === strpos( $c355_hero, 'wp_update_post' ) && false === strpos( $c355_hero, 'wp_insert_post' ),
		'4.18 ⛔ it never writes post content: the stored article is untouched'
	);
}

/* =========================================================================
 * §5 · ITEM 5 — THE ACCOUNT FORM IS ON THE PALETTE
 * ====================================================================== */

echo "\n=== §5 · ITEM 5 · THE ACCOUNT FORM ===\n";

if ( '' === $c355_style ) {
	c355_skip( '5.x the account CSS', 'style.css could not be read' );
} else {
	c355_assert(
		false !== strpos( $c355_style, '.woocommerce-account .woocommerce-form-login button.woocommerce-form-login__submit' ),
		'5.1 the login submit is styled'
	);
	c355_assert(
		false !== strpos( $c355_style, 'accent-color: var(--expedition-forest)' ),
		'5.2 the checkbox is branded through accent-color rather than rebuilt'
	);
	$c355_acct_at = strpos( $c355_style, '.woocommerce-account .woocommerce-form-login button.woocommerce-form-login__submit' );
	$c355_block   = ( false === $c355_acct_at ) ? '' : substr( $c355_style, $c355_acct_at, 1400 );
	c355_assert(
		false !== strpos( $c355_block, 'var(--expedition-forest)' ) && false === strpos( $c355_block, '#e9e6ed' ),
		'5.3 it uses a palette token, and the WooCommerce grey appears nowhere in the block'
	);
	c355_assert(
		false === strpos( $c355_block, '!important' ),
		'5.4 no !important: the base rule sets no background, so there is nothing to out-weigh'
	);
	/*
	 * ⛔ SCOPED. It must not be able to reach the cart, the checkout or a
	 *    product page.
	 */
	c355_assert(
		false === strpos( $c355_style, ".woocommerce button.button { background: var(--expedition-forest)" ),
		'5.5 ⛔ no unscoped button rule was introduced'
	);
}

/* =========================================================================
 * §6 · ITEM 6 — NO EM DASH IN THE THANK-YOU NOTE
 * ====================================================================== */

echo "\n=== §6 · ITEM 6 · THE THANK-YOU NOTE ===\n";

$c355_kit = c355_theme_src( 'page-adventure-kit-thank-you.php' );
if ( '' === $c355_kit ) {
	c355_skip( '6.x the thank-you template', 'page-adventure-kit-thank-you.php could not be read' );
} else {
	c355_assert(
		false !== strpos( $c355_kit, 'Applied automatically at checkout. No code to enter.' ),
		'6.1 the corrected sentence is present'
	);
	/*
	 * ⛔ THE OLD STRING IS GONE FROM THE OUTPUT, and the needle is assembled
	 *    from fragments so this file cannot match its own source. That is the
	 *    `CYCLE179-LD-354` lesson, where a plain literal made an assertion pass
	 *    against itself.
	 */
	$c355_old = 'Applied automatically at checkout ' . "\xE2\x80\x94" . ' no code to enter.';
	c355_assert(
		false === strpos( $c355_kit, $c355_old ),
		'6.2 ⛔ the em-dash form is gone'
	);
	/*
	 * ⚠️ THE WHOLE FILE IS NOT ASSERTED EM-DASH-FREE. `D5` lists eleven
	 *    surfaces and the brief scopes THIS release to one string; the sweep of
	 *    the rest is out of scope and Andrew's. Counting them here would turn
	 *    an out-of-scope backlog into a failing gate.
	 */
	$c355_em = substr_count( $c355_kit, "\xE2\x80\x94" );
	echo "        em dashes still in this template (out of scope, reported not gated): {$c355_em}\n";
}

/* =========================================================================
 * §7 · CYCLE179-LD-12 — THE SPECIFICITY NOTE
 * ====================================================================== */

echo "\n=== §7 · LD-12 · THE SPECIFICITY NOTE ===\n";

if ( '' === $c355_style ) {
	c355_skip( '7.x the note', 'style.css could not be read' );
} else {
	$c355_note_at  = strpos( $c355_style, 'CYCLE179-LD-12' );
	$c355_block_at = strpos( $c355_style, 'body:not(.home) .section { padding-block: var(--section-space); }' );
	c355_assert( false !== $c355_note_at, '7.1 the note exists and carries its finding id' );
	c355_assert(
		false !== $c355_note_at && false !== $c355_block_at && $c355_note_at < $c355_block_at && ( $c355_block_at - $c355_note_at ) < 1400,
		'7.2 it sits immediately above the body:not(.home) block it is about'
	);
	c355_assert(
		false !== $c355_note_at && false !== strpos( substr( $c355_style, $c355_note_at, 1200 ), '(0,2,1)' ),
		'7.3 it states the actual specificity rather than describing it vaguely'
	);
	c355_assert(
		false !== $c355_note_at && false !== strpos( substr( $c355_style, $c355_note_at, 1200 ), 'MEASURING' ),
		'7.4 ⭐ it tells the next reader to measure the deployed page, which is how 1.19.354 caught the no-op'
	);
}

/* =========================================================================
 * §8 · STANDING RAILS
 * ====================================================================== */

echo "\n=== §8 · STANDING RAILS ===\n";

$c355_new_files = array(
	'inc/page-hero.php',
	'tests/test-cycle179-cosmetic-355.php',
	'tests/test-cycle179-visit-capture-355.php',
);
/*
 * ⛔⛔ THE NEEDLES ARE ASSEMBLED FROM FRAGMENTS, AND THAT IS NOT A STYLE CHOICE.
 *     Standing Rules §14.5 forbids an internal call name anywhere in this
 *     repository, which is PUBLIC on GitHub. Writing the eight names as plain
 *     literals here would (a) put them in the public repo, which is the very
 *     thing being asserted against, and (b) make this assertion match its own
 *     source and fail on a clean tree. `CYCLE179-LD-354` hit the second half of
 *     that trap with a different literal and fixed it the same way.
 */
$c355_aliases   = array(
	'Gan' . 'dalf',
	'Ara' . 'gorn',
	'Leg' . 'olas',
	'Mer' . 'ry',
	'Pip' . 'pin',
	'Fro' . 'do',
	'Bor' . 'omir',
	'Gim' . 'li',
);
$c355_alias_hit = array();

foreach ( $c355_new_files as $c355_rel ) {
	$c355_body = c355_theme_src( $c355_rel );
	if ( '' === $c355_body ) {
		continue;
	}
	foreach ( $c355_aliases as $c355_alias ) {
		if ( false !== strpos( $c355_body, $c355_alias ) ) {
			$c355_alias_hit[] = $c355_rel . ':' . $c355_alias;
		}
	}
}
c355_assert(
	empty( $c355_alias_hit ),
	'8.1 ⛔ no internal call name appears in any file this release added to the public repository'
		. ( empty( $c355_alias_hit ) ? '' : ' -- found: ' . implode( ', ', $c355_alias_hit ) )
);

/*
 * ⛔ RULE 608a IS ABOUT STRINGS A CUSTOMER READS, NOT ABOUT PROSE IN A CODE
 *    COMMENT, AND THE DISTINCTION IS LOAD-BEARING RATHER THAN CONVENIENT. This
 *    repository's own file headers are written with em dashes throughout
 *    (`inc/visit-band.php`, `style.css` and this release's new files included),
 *    so a whole-file check would fail on every clean tree and would then be
 *    "fixed" by deleting the assertion. ⭐ SO THE COMMENTS ARE STRIPPED FIRST
 *    and only what survives, which is code and its string literals, is checked.
 *    The first draft of this assertion did NOT strip them and failed on its own
 *    subject's header; caught by running the suite, not by reading it.
 */
$c355_authored = array( 'inc/page-hero.php', 'tests/test-cycle179-cosmetic-355.php', 'tests/test-cycle179-visit-capture-355.php' );
$c355_em_hit   = array();

foreach ( $c355_authored as $c355_rel ) {
	$c355_body = c355_theme_src( $c355_rel );
	if ( '' === $c355_body ) {
		continue;
	}
	$c355_code = preg_replace( '#/\*.*?\*/#s', '', $c355_body );
	$c355_code = preg_replace( '#//[^\n]*#', '', (string) $c355_code );
	if ( false !== strpos( (string) $c355_code, "\xE2\x80\x94" ) ) {
		$c355_em_hit[] = $c355_rel;
	}
}
c355_assert(
	empty( $c355_em_hit ),
	'8.2 no em dash in any STRING this release authored, comments excluded (rule 608a)'
		. ( empty( $c355_em_hit ) ? '' : ' -- found in: ' . implode( ', ', $c355_em_hit ) )
);

/*
 * ⛔ THE VOICE RULE ON THE ONE CUSTOMER-FACING STRING THIS RELEASE TOUCHES.
 *    Standing Rules §9.1: no company "we/us/our" in words a customer reads.
 */
c355_assert(
	'' !== $c355_kit && false !== strpos( $c355_kit, 'Applied automatically at checkout. No code to enter.' )
		&& false === stripos( 'Applied automatically at checkout. No code to enter.', ' we ' ),
	'8.3 the one customer-facing string this release rewrote carries no company "we"'
);

/* =========================================================================
 * RESULT
 * ====================================================================== */

echo "\n=== CYCLE179-LD-355 COSMETIC RESULT ===\n";
echo "  passed:  {$GLOBALS['c355_pass']}\n";
echo "  failed:  {$GLOBALS['c355_fail']}\n";
echo "  skipped: {$GLOBALS['c355_skipped']}\n";

if ( $GLOBALS['c355_fail'] > 0 ) {
	echo "\nFAILED\n";
	exit( 1 );
}
echo "\nOK\n";
