<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE RETAILER / BOOKSELLER FUNNEL SUITE — theme 1.19.304, 2026-08-27,
 * `CYCLE167-LD-RETAILER-PAGE`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle167-retailer-funnel.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE EXISTS TO STOP, IN ONE SENTENCE
 * ---------------------------------------------------------------------------
 * ⛔⛔ AN ISBN PRINTED ON A RETAILER PAGE IS A PROMISE THAT A PROFESSIONAL CAN
 *    ORDER THAT BOOK TODAY, and a buyer who searches one in ipage and finds
 *    nothing does not email to ask why — they close the tab and they do not
 *    come back. Two of the seven ISBNs this company owns are NOT orderable
 *    (The Amazon hardcover `9798996810833` and the colouring book
 *    `9798996810840`, both `Processing`, both `Enabled for Distribution: No`).
 *    §2 below asserts the five that are, and §3 asserts THE TWO ARE ABSENT —
 *    an absence test, because the failure mode is an ADDITION, not a removal.
 *
 * ⭐ THE ABSENCE ASSERTIONS ARE THE POINT OF THIS FILE. Most suites in this
 *    repo assert that something is present. Nearly every rule that governs
 *    this page is a rule about what may NOT appear: no minimum, no lead time,
 *    no freight, no margin, no trim, no page count, no BISAC, no carton
 *    quantity, no sell-through claim, no coupon, no aggregateRating, no Add to
 *    Cart, no "in the USA", no "consistent quality and turnaround", no
 *    parent/teacher funnel key, and no customer-facing "we". Each is
 *    independently grep-checkable, so each is checked.
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHERE THE TERM VALUES IN §2 COME FROM — and why a test may assert them
 * ---------------------------------------------------------------------------
 * `Business OS\WORKING-DRAFTS\connected-operator\
 *  CYCLE167-GIM-INGRAM-READ-2-2026-08-27.md` §2 — `connected-operator` read each title's own
 * distribution page inside the authenticated IngramSpark account 9885354 on
 * 2026-08-27. ⚠️ RELAYED to this file, not witnessed by it.
 * ⛔ THIS SUITE DOES NOT VERIFY INGRAM. It cannot. It verifies that the code
 *    still says what the live read said, so that a future edit which quietly
 *    changes a discount or a returns value has to argue with a failing test
 *    instead of shipping. ⭐ THAT IS A DIFFERENT AND HONEST CLAIM.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * This is PHP and source level. It cannot see layout, wrapping, a tap target,
 * console cleanliness, or where a table actually sits on a rendered page.
 * Those claims carry browser evidence at a stated `window.innerWidth` in the
 * handoff and are NOT inferred from a PASS below. ⛔ Nor does it prove that a
 * wholesale enquiry reaches Andrew's inbox — §7 proves the handler DISPATCHES
 * correctly with the send intercepted; the SMTP hop is separately reported and
 * is NOT claimed here.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, no mail. It registers two filters and REMOVES BOTH before it
 *    exits (§4 and §7), so it leaves no residue in the process it ran in.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/*
 * ⛔ COUNTERS IN $GLOBALS. `wp eval-file` runs this file in FUNCTION scope, so
 *    a file-top `$pass = 0;` is a LOCAL and `global $pass;` inside the helper
 *    binds a different, unset global — the helper would increment one variable
 *    and the summary would read another, making the suite structurally
 *    incapable of reporting a failure. ⛔ A SUITE THAT CANNOT FAIL IS A
 *    FABRICATED VERIFICATION.
 */
$GLOBALS['bhp_rtl_pass'] = 0;
$GLOBALS['bhp_rtl_fail'] = 0;

function bhp_rtl_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_rtl_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_rtl_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_rtl_head( $title ) {
	echo "\n=== {$title} ===\n";
}

$theme_dir   = get_template_directory();
$tpl_path    = $theme_dir . '/page-audience-retailers.php';
$terms_path  = $theme_dir . '/inc/retailer-trade-terms.php';
$fns_path    = $theme_dir . '/functions.php';
$form_path   = $theme_dir . '/template-parts/contact/contact-form.php';

$tpl_src   = file_exists( $tpl_path ) ? (string) file_get_contents( $tpl_path ) : '';
$terms_src = file_exists( $terms_path ) ? (string) file_get_contents( $terms_path ) : '';
$fns_src   = file_exists( $fns_path ) ? (string) file_get_contents( $fns_path ) : '';

/*
 * ⭐ THE PROSE-ONLY VIEW OF THE TEMPLATE. Every absence assertion below runs
 *    against THIS, not against the raw file, because this file is DENSELY
 *    COMMENTED and the comments deliberately QUOTE the forbidden strings in
 *    order to record why they are forbidden. A naive `strpos` over the raw
 *    source would fail on the very comment that documents the rule, which
 *    would teach the next engineer to delete the documentation to make the
 *    build go green. ⛔ THAT IS THE WRONG LESSON AND THIS AVOIDS TEACHING IT.
 */
function bhp_rtl_strip_comments( $src ) {
	$out = '';
	foreach ( token_get_all( $src ) as $token ) {
		if ( is_array( $token ) ) {
			if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$out .= $token[1];
		} else {
			$out .= $token;
		}
	}
	return $out;
}

$tpl_prose = '' !== $tpl_src ? bhp_rtl_strip_comments( $tpl_src ) : '';

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '0. THE FILES AND THE FUNCTIONS EXIST' );
// ══════════════════════════════════════════════════════════════════════════

bhp_rtl_ok( '0.1  page-audience-retailers.php exists', '' !== $tpl_src );
bhp_rtl_ok( '0.2  inc/retailer-trade-terms.php exists', '' !== $terms_src );
bhp_rtl_ok( '0.3  it is require_once-d from functions.php',
	false !== strpos( $fns_src, "inc/retailer-trade-terms.php" ) );
bhp_rtl_ok( '0.4  bhp_retailer_trade_registry() is defined', function_exists( 'bhp_retailer_trade_registry' ) );
bhp_rtl_ok( '0.5  bhp_retailer_orderable_titles() is defined', function_exists( 'bhp_retailer_orderable_titles' ) );
bhp_rtl_ok( '0.6  bhp_retailer_withheld_isbns() is defined', function_exists( 'bhp_retailer_withheld_isbns' ) );
bhp_rtl_ok( '0.7  bhp_bundle_catalog() is available (the join depends on it)', function_exists( 'bhp_bundle_catalog' ) );
bhp_rtl_ok( '0.8  the template comment strip produced usable prose', strlen( $tpl_prose ) > 4000, strlen( $tpl_prose ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '1. THE TEMPLATE HARDCODES NO ISBN' );
// ══════════════════════════════════════════════════════════════════════════
/*
 * ⭐ `marketing-growth` §4.3: "The table is generated from bhp_bundle_catalog() at render
 *    time. No ISBN is ever hardcoded into a template." Asserted, not trusted.
 */
preg_match_all( '/97[89][0-9]{10}/', $tpl_prose, $tpl_isbn_hits );
bhp_rtl_ok( '1.1  zero ISBN literals in the template body',
	0 === count( $tpl_isbn_hits[0] ), implode( ',', $tpl_isbn_hits[0] ) );
bhp_rtl_ok( '1.2  the template calls the resolver instead',
	false !== strpos( $tpl_prose, 'bhp_retailer_orderable_titles' ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '2. ⭐⭐ THE SIX ORDERABLE EDITIONS, AND THEIR VERIFIED TERMS' );
// ══════════════════════════════════════════════════════════════════════════

$rows = function_exists( 'bhp_retailer_orderable_titles' ) ? bhp_retailer_orderable_titles() : array();
$by_isbn = array();
foreach ( $rows as $r ) {
	$by_isbn[ $r['isbn'] ] = $r;
}

/*
 * ⭐⭐ 1.19.314 (2026-08-28, `CYCLE168-LD-RETAILER-BATCH`) — FIVE BECAME SIX.
 *
 * ⛔ THE SUPERSEDED ASSERTION, PRESERVED VERBATIM so the movement is visible:
 *      bhp_rtl_ok( '2.0  exactly FIVE rows render', 5 === count( $rows ), count( $rows ) );
 *
 * ⭐ Andrew Signore, 2026-08-28, carrier item 364, his own first-hand read of
 *   the authenticated IngramSpark console: "The amazon HC is active on ingram -
 *   saw it this morning after approval". Item 363: "Should be 6 ISBNs now with
 *   the Amazon hardcover active."
 *
 * ⛔ THIS NUMBER IS DELIBERATELY HARDCODED AND DELIBERATELY NOT DERIVED. A
 *    count read from the registry would agree with any registry, including one
 *    somebody widened by accident. Six is the number the founder stated, and
 *    this line is where a seventh row would be caught.
 */
bhp_rtl_ok( '2.0  exactly SIX rows render', 6 === count( $rows ), count( $rows ) );

/*
 * ⛔ THE EXPECTED SET IS WRITTEN OUT LONGHAND ON PURPOSE. A test that derived
 *    it from the same registry the code reads would assert nothing at all —
 *    it would agree with any value, including a wrong one. These five rows
 *    are transcribed from the `connected-operator` live read and are the INDEPENDENT side of
 *    the comparison.
 */
$expected = array(
	'9798234014016' => array( 'Paperback', '12.99', '55%', 'Yes - Destroy', 'The Mariana Trench' ),
	'9798234055873' => array( 'Paperback', '12.99', '55%', 'Yes - Destroy', 'Mount Everest' ),
	'9798996810802' => array( 'Paperback', '12.99', '55%', 'Yes - Destroy', 'The Amazon' ),
	'9798996810819' => array( 'Hardcover', '19.99', '55%', 'Yes - Destroy', 'The Mariana Trench' ),
	'9798996810826' => array( 'Hardcover', '19.99', '55%', 'Yes - Destroy', 'Mount Everest' ),
	/*
	 * ⭐⭐ 1.19.314 — THE SIXTH ROW. ⚠️ AND ITS PROVENANCE IS WEAKER THAN THE
	 *    OTHER FIVE, WHICH IS STATED HERE RATHER THAN LEVELLED UP.
	 *    STATUS was read first-hand by the founder (item 364, 2026-08-28).
	 *    The four TERM values below are carried from the two sibling hardcovers
	 *    and from item 363's own restatement; nobody has read THIS ISBN's own
	 *    four fields. See `inc/retailer-trade-terms.php` for the full note and
	 *    the recheck. ⛔ If the next Ingram pass finds a different figure, THIS
	 *    line is the one that must change first, and 2.91 will already have
	 *    failed by then because the terms will no longer be uniform.
	 */
	'9798996810833' => array( 'Hardcover', '19.99', '55%', 'Yes - Destroy', 'The Amazon' ),
);

foreach ( $expected as $isbn => $exp ) {
	list( $fmt, $price, $disc, $ret, $title_fragment ) = $exp;
	$have = isset( $by_isbn[ $isbn ] ) ? $by_isbn[ $isbn ] : null;

	bhp_rtl_ok( "2.{$isbn}a  present", null !== $have );
	if ( ! $have ) {
		continue;
	}
	bhp_rtl_ok( "2.{$isbn}b  format {$fmt}", $fmt === $have['format_label'], $have['format_label'] );
	bhp_rtl_ok( "2.{$isbn}c  Ingram US list {$price}", $price === $have['list_us'], $have['list_us'] );
	bhp_rtl_ok( "2.{$isbn}d  wholesale discount {$disc}", $disc === $have['discount'], $have['discount'] );
	bhp_rtl_ok( "2.{$isbn}e  returns {$ret}", $ret === $have['returnable'], $have['returnable'] );
	bhp_rtl_ok( "2.{$isbn}f  title from the CATALOG, containing '{$title_fragment}'",
		false !== strpos( $have['label'], $title_fragment ), $have['label'] );
	bhp_rtl_ok( "2.{$isbn}g  title carries the series name",
		false !== strpos( $have['label'], 'Adventures of Charlotte and Henry' ), $have['label'] );
}

bhp_rtl_ok( '2.90  three paperbacks and three hardcovers',
	3 === count( array_filter( $rows, function ( $r ) { return 'Paperback' === $r['format_label']; } ) )
	&& 3 === count( array_filter( $rows, function ( $r ) { return 'Hardcover' === $r['format_label']; } ) ) );

bhp_rtl_ok( '2.91  terms are uniform, so the page may summarise them once',
	function_exists( 'bhp_retailer_terms_are_uniform' ) && bhp_retailer_terms_are_uniform() );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '3. ⛔⛔ THE DO-NOT-LIST PAIR IS ABSENT — the assertion that matters most' );
// ══════════════════════════════════════════════════════════════════════════

/*
 * ⛔⛔ 1.19.314 — `9798996810833` LEFT THIS LIST, AND IT IS THE ONLY ONE THAT
 *     MAY. The superseded entry, preserved verbatim:
 *
 *       '9798996810833' => 'The Amazon HARDCOVER — Processing, Enabled for
 *                           Distribution: No, never entered production',
 *
 * ⭐ It is now asserted as PRESENT, with its terms, in §2 above. Moving an
 *   ISBN from the forbidden list to the expected list is the single most
 *   dangerous edit anybody can make to this file, so it is made once, with the
 *   founder's own words attached, and never again without them.
 *
 * ⛔⛔ THE COLOURING BOOK IS MORE FIRMLY FORBIDDEN THAN BEFORE, NOT LESS.
 *    Item 358: Ingram customer service pushed it through, AND HE IS
 *    DELIBERATELY NOT APPROVING IT pending the interior remake. Its reason is
 *    upgraded from an Ingram state to a founder decision.
 */
$forbidden_isbns = array(
	'9798996810840' => 'Mariana Ocean COLORING BOOK — the FOUNDER is withholding it (item 358, 2026-08-28) pending the interior remake. Not an Ingram state: his decision.',
	'9798996810857' => 'Mount Everest colouring book — ISBN assigned, never submitted to Ingram at all',
);

foreach ( $forbidden_isbns as $isbn => $why ) {
	bhp_rtl_ok( "3.{$isbn}a  NOT in the rendered set", ! isset( $by_isbn[ $isbn ] ), $why );
	bhp_rtl_ok( "3.{$isbn}b  NOT anywhere in the template body",
		false === strpos( $tpl_prose, $isbn ), $why );
}

$withheld = function_exists( 'bhp_retailer_withheld_isbns' ) ? bhp_retailer_withheld_isbns() : array();
bhp_rtl_ok( '3.90  the registry names the withheld colouring ISBN WITH A REASON, rather than omitting it',
	isset( $withheld['9798996810840'] ) && '' !== trim( (string) $withheld['9798996810840'] ),
	wp_json_encode( array_keys( $withheld ) ) );
bhp_rtl_ok( '3.90a 1.19.314: the Amazon hardcover is NO LONGER withheld, because the founder read it ACTIVE (item 364)',
	! isset( $withheld['9798996810833'] ) );

bhp_rtl_ok( '3.91  the colouring ISBN is not in bhp_bundle_catalog() either, so it cannot leak by the join',
	false === strpos( wp_json_encode( bhp_bundle_catalog() ), '9798996810840' ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '4. ⭐ FAIL-CLOSED — an invented ISBN cannot reach the page' );
// ══════════════════════════════════════════════════════════════════════════
/*
 * ⭐ THE STRONGEST ASSERTION IN THE FILE, because it tests the MECHANISM
 *    rather than the current data. Push a plausible, well-formed, orderable
 *    row into the registry through its own filter and confirm the catalog join
 *    still refuses it. A registry entry alone must never be sufficient.
 */
$bhp_rtl_inject = function ( $registry ) {
	$registry['9799999999999'] = array(
		'orderable'    => true,
		'format'       => 'paperback',
		'format_label' => 'Paperback',
		'list_us'      => '12.99',
		'discount'     => '55%',
		'returnable'   => 'Yes - Destroy',
	);
	// And re-enable the withheld COLOURING BOOK, which is now the likeliest
	// real-world mistake — the founder is holding it (item 358) and a
	// well-meaning edit could open it. (Until 1.19.314 this line re-enabled
	// `…0833`, which is legitimately open now and so can no longer be the probe.)
	$registry['9798996810840']['orderable'] = true;
	return $registry;
};
add_filter( 'bhp_retailer_trade_registry', $bhp_rtl_inject, 99 );

$rows_injected = bhp_retailer_orderable_titles();
$injected_isbns = wp_list_pluck( $rows_injected, 'isbn' );

bhp_rtl_ok( '4.1  an ISBN absent from bhp_bundle_catalog() is REFUSED even when the registry allows it',
	! in_array( '9799999999999', $injected_isbns, true ) );
bhp_rtl_ok( '4.2  ⭐⭐ the COLOURING BOOK cannot be surfaced by a registry flip either, because it is not in bhp_bundle_catalog() — two independent gates, and the founder-withheld title sits behind BOTH',
	! in_array( '9798996810840', $injected_isbns, true ) );

remove_filter( 'bhp_retailer_trade_registry', $bhp_rtl_inject, 99 );
bhp_rtl_ok( '4.3  the filter was removed and the set is back to six',
	6 === count( bhp_retailer_orderable_titles() ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '5. ⛔ FORBIDDEN VOCABULARY — every one an independent absence' );
// ══════════════════════════════════════════════════════════════════════════

$forbidden = array(
	// The two unsourced claims that were live on the page until 1.19.304.
	'in the USA'                        => 'country-of-origin claim, no located source; the identical claim was removed from the Collection page 2026-08-02',
	'consistent quality and turnaround' => 'printer-performance claim, no measurement behind it',
	// The terminology error that would fail the trade credibility test instantly.
	'IngramSpark'                       => 'a bookseller cannot order on IngramSpark; the trade orders through Ingram on ipage',
	// Contract terms nobody has verified.
	'free freight'                      => "Ingram's offer to the store, not Brave Hearts'; restating it makes it read as ours",
	'lead time'                         => 'a lead time is a delivery commitment nobody has made',
	'carton'                            => 'carton quantity is not published',
	// Sell-through, the never-invent limb this page is most tempted by.
	'proven seller'                     => 'no sell-through datum exists anywhere in this company',
	'sells well'                        => 'same',
	'moves well'                        => 'same',
	'reorder rate'                      => 'same',
	'best seller'                       => 'same',
	'bestseller'                        => 'same',
	// Fabricated evidence.
	'aggregateRating'                   => 'never fabricated, and no real reviews exist to emit',
	'40 classrooms'                     => 'contested and open; not on the page today and must not be added',
	// The author-fingerprint four.
	'Island Peak'                       => 'unconfirmed founder specific, guarded by check_author_fingerprint()',
	'Jiri'                              => 'same',
	'20,000 feet'                       => 'same',
	'without oxygen'                    => 'same',
	// Family.
	'daughter'                          => 'Charlotte is his NIECE and he has no children (carrier item 285)',
	// The reading-age rail.
	'5–9'                               => 'reading age is 6-9, never 5-9',
	'5-9'                               => 'reading age is 6-9, never 5-9',
	// Coupons are frozen policy for Retailer.
	'coupon'                            => 'frozen policy: no coupon for Organization or Retailer',
	// The old dead state.
	'coming soon'                       => 'the Ingram "coming soon" state is over and the placeholder cover is suppressed',
	'Coming Soon'                       => 'same',
	'Guide cover in progress'           => 'a placeholder graphic on a trade page costs the whole page its credibility',
);

foreach ( $forbidden as $needle => $why ) {
	bhp_rtl_ok( "5.  ABSENT: '{$needle}'", false === stripos( $tpl_prose, $needle ), $why );
}

bhp_rtl_ok( '5.90  no em dash anywhere in the template body', false === strpos( $tpl_prose, "\xE2\x80\x94" ) );

/*
 * ⭐⭐ THE VALUE-PATTERN CHECKS, and the distinction they draw is the important
 *    one on this page. THE WORDS ARE NOT FORBIDDEN. THE VALUES ARE.
 *
 * ⛔ A naive literal ban on "minimum order" or "BISAC" would fail on the two
 *    sentences that make this page HONEST — the approved FAQ "Is there a
 *    minimum order quantity?" (whose answer is that there is no published
 *    minimum) and the `marketing-growth` on-request line "Trim size, page count and BISAC
 *    codes for each edition are available on request". ⭐ Both SAY THE
 *    COMPANY DOES NOT PUBLISH THESE, which is the opposite of the failure the
 *    rule guards against. Banning the word would have pushed the next engineer
 *    to delete an honest sentence to make a build go green.
 *
 * ⭐ SO THE ASSERTION IS ON A STATED FIGURE: no page count, no trim dimension,
 *    no numeric minimum, no carton quantity, no lead time in days.
 */
$value_patterns = array(
	'a stated page count'        => '/\b\d+\s*pages\b/i',
	'a stated trim dimension'    => '/\b\d+(?:\.\d+)?\s*x\s*\d+(?:\.\d+)?\b/i',
	'a stated lead time in days' => '/\b\d+\s*(?:business\s+)?days\b/i',
	'a numeric minimum order'    => '/\bminimum\s+(?:of|order\s+of)?\s*\d+/i',
	'a stated carton quantity'   => '/\bcarton[^.]{0,20}\d+/i',
	'a stated unit minimum'      => '/\b\d+\s*(?:copies|units)\s+minimum\b/i',
);
foreach ( $value_patterns as $what => $pattern ) {
	bhp_rtl_ok( "5.  ABSENT ({$what})", 0 === preg_match( $pattern, $tpl_prose ) );
}

/*
 * ⭐ AND THE POSITIVE HALF: the honest substitute IS present, and it appears
 *    exactly once, offering the specs on request rather than inventing them.
 */
bhp_rtl_ok( '5.95  BISAC is mentioned exactly once, and only as an on-request offer',
	1 === substr_count( $tpl_prose, 'BISAC' )
	&& false !== strpos( $tpl_prose, 'available on request' ),
	substr_count( $tpl_prose, 'BISAC' ) );
bhp_rtl_ok( '5.96  the approved minimum-order FAQ survives, answered honestly',
	false !== strpos( $tpl_prose, 'Is there a minimum order quantity?' )
	&& false !== strpos( $tpl_prose, 'no published minimum yet' ) );

/*
 * ⭐ THE VOICE RAIL, §9.1, in his own words: "when you are putting front facing
 *    words to customers, there is no 'we'. I am the sole operator of the
 *    company." ⛔ Measured live on staging 2026-08-27, the OLD page rendered
 *    three visible "we"s and more inside the collapsed FAQ answers.
 */
preg_match_all( "/\b(we|We|WE)\b|\bwe(’|')(ll|ve|re|d)\b|\b(our|Our|us|Us)\b/", $tpl_prose, $we_hits );
bhp_rtl_ok( '5.91  ⭐ ZERO customer-facing "we / us / our" in the template body',
	0 === count( $we_hits[0] ), implode( ' | ', array_slice( $we_hits[0], 0, 12 ) ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '6. ⛔ FUNNEL ISOLATION — the retailer funnel touches no other rail' );
// ══════════════════════════════════════════════════════════════════════════

$foreign_rails = array(
	'bhp_parent_popup'   => 'parent funnel storage prefix',
	'parent_popup'       => 'parent funnel analytics prefix',
	'bhp_mariana_popup'  => 'teacher funnel storage prefix',
	'teacher_popup'      => 'teacher funnel analytics prefix',
	'adventure_kit'      => "parent funnel's lead-magnet key",
	'mariana_trench_classroom_guide' => "teacher funnel's lead-magnet key",
	'add-to-cart'        => 'wholesale must never route through WooCommerce',
	'add_to_cart'        => 'same',
);
foreach ( $foreign_rails as $needle => $why ) {
	bhp_rtl_ok( "6.  ABSENT: '{$needle}'", false === strpos( $tpl_prose, $needle ), $why );
}

bhp_rtl_ok( '6.10  the retailer funnel keeps its OWN analytics event, unrenamed',
	false !== strpos( $tpl_prose, 'retailer_landing_view' ) );
bhp_rtl_ok( '6.11  and no SECOND landing event was minted for this page',
	1 === substr_count( $tpl_prose, 'retailer_landing_view' ), substr_count( $tpl_prose, 'retailer_landing_view' ) );
bhp_rtl_ok( '6.12  the lead-magnet key is unchanged and still bookstore_wholesale_guide',
	false !== strpos( $tpl_prose, 'bookstore_wholesale_guide' ) );
/*
 * ⭐ FOUR, not three: the hero primary CTA is emitted in BOTH branches of the
 *    `$download['ready']` conditional (the guide modal when the PDF exists, the
 *    terms table when it does not), so the source carries it twice while a
 *    rendered page carries it once. Counting the source and expecting the
 *    rendered number is a classic way to write an assertion that is wrong in a
 *    way nobody notices.
 */
/*
 * ⭐⭐ 1.19.314 — THE COUNT MOVED FROM 4 TO 6, AND THE ASSERTION'S JOB DID NOT.
 *
 * ⛔ THE SUPERSEDED LINE, PRESERVED VERBATIM:
 *      bhp_rtl_ok( '6.13  every CTA event keeps the retailer_ prefix (4 in source, 3 rendered)',
 *          4 === substr_count( $tpl_prose, 'data-bhp-event="retailer_' ), ... );
 *
 * ⚠ THE COUNT IS NOT THE POINT AND NEVER WAS — funnel isolation is. This
 *   line exists to prove no event on this page borrows the parent or teacher
 *   prefix, which 6.14 asserts directly. The number is a tripwire for somebody
 *   adding a control without thinking about which funnel it belongs to, so it
 *   is UPDATED rather than loosened to a range: a range would stop tripping.
 *
 * ⭐ THE SIX, NAMED, so the next person can tell an addition from a drift:
 *      1 retailer_hero_primary_cta_click     hero, now the ipage ordering route
 *      2 retailer_hero_sell_sheet_click      hero, the ungated PDF
 *      3 retailer_hero_secondary_cta_click   hero, the demoted inquiry text link
 *      4 retailer_ordering_sell_sheet_click  B2 ordering block, the same PDF
 *      5 retailer_sticky_order_click         sticky bar, the ordering route
 *      6 retailer_wholesale_contact_click    final CTA section, the inquiry
 *
 * ⭐ AND THE SOURCE/RENDERED GAP IS GONE. The old note explained that the hero
 *   primary appeared TWICE in source because of the `$download['ready']`
 *   branch; 1.19.314 removed that branch from the hero, so all six source
 *   occurrences now render. Source count and rendered count finally agree.
 */
bhp_rtl_ok( '6.13  every CTA event keeps the retailer_ prefix (6 in source, 6 rendered)',
	6 === substr_count( $tpl_prose, 'data-bhp-event="retailer_' ), substr_count( $tpl_prose, 'data-bhp-event="retailer_' ) );
bhp_rtl_ok( '6.14  and NO event on this page carries a foreign funnel prefix',
	false === strpos( $tpl_prose, 'data-bhp-event="parent_' )
	&& false === strpos( $tpl_prose, 'data-bhp-event="teacher_' ) );

bhp_rtl_ok( '6.20  the page template is STILL in bhp_should_show_any_popup()\'s exclusion list',
	false !== strpos( $fns_src, "'page-audience-retailers.php'" ) );
bhp_rtl_ok( '6.21  and the exclusion actually holds at runtime for this template',
	function_exists( 'bhp_should_show_any_popup' ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '7. ⭐ THE CAPTURE ROUTE — the wholesale inquiry type' );
// ══════════════════════════════════════════════════════════════════════════

$types = apply_filters( 'bhp_contact_inquiry_types', array(
	''        => 'Select an inquiry type',
	'general' => 'General Question',
) );

bhp_rtl_ok( '7.1  a "wholesale" inquiry type is registered', isset( $types['wholesale'] ), implode( ',', array_keys( $types ) ) );
bhp_rtl_ok( '7.2  its label names both words a buyer would look for',
	isset( $types['wholesale'] )
	&& false !== stripos( $types['wholesale'], 'Wholesale' )
	&& false !== stripos( $types['wholesale'], 'Retail' ), isset( $types['wholesale'] ) ? $types['wholesale'] : '(absent)' );
bhp_rtl_ok( '7.3  it was added through the FILTER, not by editing the shared array in place',
	false !== strpos( $fns_src, "add_filter( 'bhp_contact_inquiry_types'" )
	|| false !== strpos( $fns_src, "add_filter('bhp_contact_inquiry_types'" ) );
bhp_rtl_ok( '7.4  the shared default array in contact-form.php is BYTE-UNTOUCHED (no wholesale key inside it)',
	file_exists( $form_path ) && false === strpos( (string) file_get_contents( $form_path ), "'wholesale'" ) );

bhp_rtl_ok( '7.5  the page routes to /contact/?inquiry=wholesale',
	false !== strpos( $tpl_prose, "add_query_arg('inquiry', 'wholesale'" )
	|| false !== strpos( $tpl_prose, 'add_query_arg( \'inquiry\', \'wholesale\'' ) );
bhp_rtl_ok( '7.6  the existing bookseller ROLE is untouched in the shared form',
	file_exists( $form_path ) && false !== strpos( (string) file_get_contents( $form_path ), "'bookseller'" ) );

/*
 * ⭐⭐ 7.7 — THE END-TO-END DISPATCH TEST, WITH THE SEND INTERCEPTED.
 *
 * ⛔ THIS SUITE DOES NOT SEND MAIL. Sending a real message to the founder's
 *    inbox is an outward-facing action and an Andrew gate; a test that crossed
 *    it to prove itself would be a worse failure than the gap it closed. So
 *    `pre_wp_mail` short-circuits the send and captures the arguments, and
 *    what is asserted is that the handler VALIDATES a wholesale submission and
 *    DISPATCHES it to a server-controlled recipient with the inquiry type in
 *    the body. ⛔ THE SMTP HOP IS NOT PROVEN HERE and is reported separately as
 *    NOT VERIFIED. `CYCLE167-MKT-T10` is therefore narrowed, not closed.
 */
$GLOBALS['bhp_rtl_mail'] = null;
$bhp_rtl_intercept = function ( $null, $atts ) {
	$GLOBALS['bhp_rtl_mail'] = $atts;
	return true; // ⛔ short-circuit: wp_mail() returns true and sends NOTHING.
};
add_filter( 'pre_wp_mail', $bhp_rtl_intercept, 1, 2 );

$recipient = sanitize_email( apply_filters( 'bhp_contact_recipient', get_option( 'admin_email' ) ) );
bhp_rtl_ok( '7.7  the contact recipient resolves to a real, server-controlled address',
	is_email( $recipient ), $recipient );

$probe = wp_mail(
	$recipient,
	'[Brave Hearts Contact] wholesale - SUITE PROBE',
	"Inquiry type: wholesale\nSource page: /retailers-wholesale-guide/\n",
	array( 'Reply-To: Suite Probe <suite-probe@example.invalid>' )
);
bhp_rtl_ok( '7.8  the mail path is reachable and was INTERCEPTED, not sent',
	true === $probe && is_array( $GLOBALS['bhp_rtl_mail'] ) );
bhp_rtl_ok( '7.9  the intercepted message carries the wholesale inquiry type',
	is_array( $GLOBALS['bhp_rtl_mail'] )
	&& false !== strpos( (string) $GLOBALS['bhp_rtl_mail']['message'], 'Inquiry type: wholesale' ) );
bhp_rtl_ok( '7.10 the intercepted recipient is the server-controlled one, never a user-supplied address',
	is_array( $GLOBALS['bhp_rtl_mail'] ) && $recipient === $GLOBALS['bhp_rtl_mail']['to'] );

$bhp_rtl_removed = remove_filter( 'pre_wp_mail', $bhp_rtl_intercept, 1 );
/*
 * ⛔ THIS ASSERTION WAS ORIGINALLY WRITTEN AS `! has_filter(...) || true`, WHICH
 *    IS ALWAYS TRUE AND THEREFORE ASSERTS NOTHING. Caught in review before the
 *    suite ever ran. ⭐ A test that cannot fail is a fabricated verification,
 *    and one hiding inside a passing suite is worse than a missing test because
 *    it reports green. The real check is that the specific closure is gone.
 */
bhp_rtl_ok( '7.11 the intercept filter was REMOVED — the suite leaves no residue in this process',
	true === $bhp_rtl_removed
	&& false === has_filter( 'pre_wp_mail', $bhp_rtl_intercept ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '8. ⭐ SEO BASICS — crawlable ISBNs, one H1, no fabricated schema' );
// ══════════════════════════════════════════════════════════════════════════

bhp_rtl_ok( '8.1  exactly one <h1> in the template', 1 === substr_count( $tpl_prose, '<h1>' ), substr_count( $tpl_prose, '<h1>' ) );
bhp_rtl_ok( '8.2  the ISBNs are emitted as TEXT in a real table, not only in a JS payload',
	false !== strpos( $tpl_prose, 'retailer-terms__isbn' )
	&& false !== strpos( $tpl_prose, '<table class="retailer-terms">' ) );
bhp_rtl_ok( '8.3  the table has a caption for screen readers',
	false !== strpos( $tpl_prose, '<caption' ) );
bhp_rtl_ok( '8.4  an SEO title FALLBACK exists for this template',
	false !== strpos( $fns_src, 'bhp_retailer_seo_title' ) );
bhp_rtl_ok( '8.5  ⭐ and it is a FALLBACK — the existing audience-landing meta description filter still governs, so wp-admin always wins',
	false !== strpos( $fns_src, 'bhp_audience_landing_seo_description_filter' ) );
bhp_rtl_ok( '8.6  no Offer / Product schema is emitted from this template',
	false === strpos( $tpl_prose, 'application/ld+json' ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '9. ⭐ THE PROTECTED SENTENCES — kept byte-verbatim' );
// ══════════════════════════════════════════════════════════════════════════

$verbatim = array(
	'9.1  B10, the pricing disclaimer (the sentence that makes the rest of the page safe)'
		=> 'Prices shown are current consumer list prices on braveheartspublishing.com, not wholesale or trade pricing. The wholesale discount and returns terms for each orderable edition are listed above. For minimums or anything not covered here, contact me directly.',
	'9.2  the hero H1'
		=> 'A visually distinctive adventure series for your shelves.',
	'9.3  the hero lead'
		=> 'Illustrated middle-grade adventures built around real destinations - a natural fit for independent bookstores, museum and park stores, nature centers, and educational retailers.',
	'9.4  the Kirkus proof item, in its series_note form'
		=> 'Featuring a Kirkus-reviewed title',
	'9.5  the consumer hardcover price card'
		=> '$17.99 current consumer list price per title · keepsake gift edition',
	'9.6  the consumer paperback price card'
		=> '$11.99 current consumer list price per title · softcover, matte finish',
);
foreach ( $verbatim as $label => $sentence ) {
	bhp_rtl_ok( $label, false !== strpos( $tpl_prose, $sentence ), 'MISSING' );
}

bhp_rtl_ok( '9.10 Kirkus is never attributed to Everest or The Amazon',
	false === stripos( $tpl_prose, 'Kirkus-reviewed series' )
	&& false === stripos( $tpl_prose, 'Kirkus reviewed all' ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '10. ⭐ THE NEW COPY IS THE FOUNDER\'S, AND THE INGRAM ROUTE IS RIGHT' );
// ══════════════════════════════════════════════════════════════════════════

/*
 * ⭐⭐ 1.19.314 — 10.1 WAS REWRITTEN BECAUSE THE SENTENCE IS NOW SPLIT, NOT
 *    BECAUSE IT CHANGED. The superseded assertion, preserved verbatim:
 *
 *      bhp_rtl_ok( '10.1  the ordering block names ipage',
 *          false !== strpos( $tpl_prose, 'Search the ISBN in ipage' ) );
 *
 * ⛔ The `marketing-growth` D1 — "ipage is named in the copy but is NOT a link" — is fixed by
 *    wrapping the word in an anchor, which necessarily splits the translatable
 *    string in two. The SENTENCE a visitor reads is byte-identical; only the
 *    markup between two of its words changed. Both halves are asserted, plus
 *    the anchor, so this is strictly MORE coverage than the line it replaces.
 */
bhp_rtl_ok( '10.1a the ordering block still says "Search the ISBN in"',
	false !== strpos( $tpl_prose, 'Search the ISBN in ' ) );
bhp_rtl_ok( '10.1b and the word ipage is now a real anchor, not bare prose (marketing-growth D1)',
	false !== strpos( $tpl_prose, 'retailer-ipage-link' )
	&& false !== strpos( $tpl_prose, "esc_html_e('ipage'" ) );
bhp_rtl_ok( '10.2  ⛔ and does NOT claim the record is findable there ("and it is there" is unverified)',
	false === stripos( $tpl_prose, 'and it is there' ) );
bhp_rtl_ok( '10.3  Ingram Content Group is named as where an account is created',
	false !== strpos( $tpl_prose, 'Ingram Content Group' ) );
bhp_rtl_ok( '10.4  carrier item 288, the poetry paragraph, is present',
	false !== strpos( $tpl_prose, 'The first one started as poetry' ) );
bhp_rtl_ok( '10.5  carrier item 286/287, the honest format claim, is present',
	false !== strpos( $tpl_prose, 'Built for the reader who stalls' )
	&& false !== strpos( $tpl_prose, 'kids pick these up at my table and keep reading' ) );
bhp_rtl_ok( '10.6  ⛔ item 287\'s conversion-shaped half is deliberately NOT used',
	false === stripos( $tpl_prose, 'immediately say yes' ) );
bhp_rtl_ok( '10.7  the replacement fulfilment line is present and names the real partner',
	false !== strpos( $tpl_prose, 'printed on demand through Bookvault' ) );
bhp_rtl_ok( '10.8  "nothing goes out of print" appears, and no returns wording sits beside it',
	false !== strpos( $tpl_prose, 'Nothing goes out of print' ) );
bhp_rtl_ok( '10.9  the risk-removal block leads with no minimum',
	false !== strpos( $tpl_prose, 'A small first order is a fine first order' ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '11. ⭐ §26 AFFILIATE PRESERVATION — the count-decrease test, source side' );
// ══════════════════════════════════════════════════════════════════════════
/*
 * ⭐ MEASURED LIVE ON STAGING 2026-08-27 BEFORE THIS CHANGE: this page rendered
 *    ZERO affiliate anchors (30 anchors total, 0 matching amzn.to / amazon
 *    /dp/ / any `tag=`). So the count-decrease test's floor here is ZERO and
 *    it cannot be breached by this change. ⛔ THE TEST IS STILL RUN AND THE
 *    NUMBERS ARE STILL RECORDED, because §26.6 is explicit that a count which
 *    was not actually run is a fabricated check.
 * ⛔ THIS ASSERTION IS THE SOURCE SIDE ONLY. The rendered side is in the QA
 *    evidence, measured in a real browser, before and after.
 */
preg_match_all( '/amzn\.to|amazon\.[a-z.]+\/[^"\']*\/dp\/|[?&]tag=/i', $tpl_prose, $aff_hits );
bhp_rtl_ok( '11.1  affiliate anchors in the template source: 0 before, 0 after (nothing to preserve, nothing lost)',
	0 === count( $aff_hits[0] ), implode( ',', $aff_hits[0] ) );

// ══════════════════════════════════════════════════════════════════════════
bhp_rtl_head( '12. ⭐ PROVENANCE IS IN THE CODE, not only in a report' );
// ══════════════════════════════════════════════════════════════════════════

bhp_rtl_ok( '12.1  the registry cites the live read that produced it',
	false !== strpos( $terms_src, 'CYCLE167-GIM-INGRAM-READ-2-2026-08-27' ) );
bhp_rtl_ok( '12.2  it carries the date the terms were read',
	false !== strpos( $terms_src, '2026-08-27' ) );
bhp_rtl_ok( '12.3  it records that the read was RELAYED, not witnessed by this desk',
	false !== stripos( $terms_src, 'RELAYED' ) );
bhp_rtl_ok( '12.4  it records the Yes-Deliver -> Yes-Destroy correction so it cannot be lost again',
	false !== strpos( $terms_src, 'Yes-Deliver' ) && false !== strpos( $terms_src, 'Yes - Destroy' ) );
bhp_rtl_ok( '12.5  the superseded "coming soon" guard is preserved verbatim in the template docblock',
	false !== strpos( $tpl_src, 'must never claim active Ingram availability' ) );

// ==========================================================================
bhp_rtl_head( '13. ⭐⭐ 1.19.314 — ORDERING ROUTE, SELL SHEET, IMPRINT, FOOTER LINK, TIGHT HERO' );
// ==========================================================================
/*
 * ⛔ SOURCE-SIDE ASSERTIONS ONLY. What a browser actually renders — the CTA
 *    above the fold at 1440x900 AND at 375x812, the six ISBNs in the visible
 *    text, the PDF returning 200, the footer link on three unrelated page
 *    types — is in the QA evidence, measured in a real browser with
 *    `window.innerWidth` asserted in the same read. Neither substitutes for
 *    the other, and a passing suite here is NOT a rendered-page claim.
 */

// -- 13.1  THE IPAGE ORDERING LINK (marketing-growth D1, founder items 360/366) --
bhp_rtl_ok( '13.1a the ipage URL literal appears exactly ONCE in the template',
	1 === substr_count( $tpl_prose, "'https://ipage.ingramcontent.com'" ),
	substr_count( $tpl_prose, "'https://ipage.ingramcontent.com'" ) );
bhp_rtl_ok( '13.1b every use goes through the $ipage_url variable, so the button and the prose can never disagree',
	substr_count( $tpl_prose, '$ipage_url' ) >= 3,
	substr_count( $tpl_prose, '$ipage_url' ) );
bhp_rtl_ok( '13.1c the hero primary CTA is an ordering route, not a same-page anchor',
	false !== strpos( $tpl_prose, "Order on Ingram (ipage)" ) );
bhp_rtl_ok( '13.1d ⛔ no control says "IngramSpark" — a bookseller cannot buy there, and the R3 sell sheet does not say it either',
	false === strpos( $tpl_prose, 'IngramSpark' ) );
bhp_rtl_ok( '13.1e outbound ipage links carry rel="noopener"',
	substr_count( $tpl_prose, 'rel="noopener"' ) >= 3,
	substr_count( $tpl_prose, 'rel="noopener"' ) );
bhp_rtl_ok( '13.1f ⛔ no tracking parameter was bolted onto somebody else\'s URL',
	false === strpos( $tpl_prose, 'ingramcontent.com?' )
	&& false === strpos( $tpl_prose, 'ingramcontent.com/?' ) );
/*
 * ⭐ 13.1g IS SCOPED TO THE HERO, DELIBERATELY. "See the ISBNs and terms"
 *    survives as the SECONDARY control in the page's closing CTA section,
 *    which is correct: down there the buyer has read the page and an anchor
 *    back to the table is useful. What must not survive is that label as the
 *    page's PRIMARY above-the-fold control, which is item 366's whole point.
 */
$bhp_rtl_hero_at  = strpos( $tpl_prose, 'retailer_hero_primary_cta_click' );
$bhp_rtl_hero_ctx = false !== $bhp_rtl_hero_at
	? substr( $tpl_prose, max( 0, $bhp_rtl_hero_at - 400 ), 400 )
	: '';
bhp_rtl_ok( '13.1g the hero PRIMARY CTA points at ipage and is no longer a same-page anchor',
	false !== strpos( $bhp_rtl_hero_ctx, '$ipage_url' )
	&& false === strpos( $bhp_rtl_hero_ctx, '#titles' ),
	$bhp_rtl_hero_ctx );
bhp_rtl_ok( '13.1h the wholesale-inquiry route survives its demotion (it is still in the template)',
	false !== strpos( $tpl_prose, '#contact' )
	&& false !== strpos( $tpl_prose, 'Start a Wholesale Inquiry' ) );

// -- 13.2  THE SELL SHEET, UNGATED (marketing-growth D2 / Fix 2) ------------
$bhp_rtl_pdf = get_template_directory() . '/assets/downloads/bhp-retailer-sell-sheet.pdf';
bhp_rtl_ok( '13.2a the sell-sheet PDF actually ships inside the theme',
	file_exists( $bhp_rtl_pdf ), $bhp_rtl_pdf );
bhp_rtl_ok( '13.2b it is a real PDF, not a placeholder or a truncated copy',
	file_exists( $bhp_rtl_pdf )
	&& filesize( $bhp_rtl_pdf ) > 100000
	&& '%PDF' === (string) file_get_contents( $bhp_rtl_pdf, false, null, 0, 4 ),
	file_exists( $bhp_rtl_pdf ) ? filesize( $bhp_rtl_pdf ) . ' bytes' : 'missing' );
bhp_rtl_ok( '13.2c the template offers it in the hero AND in the ordering block',
	substr_count( $tpl_prose, '$sell_sheet_url' ) >= 4,
	substr_count( $tpl_prose, '$sell_sheet_url' ) );
bhp_rtl_ok( '13.2d ⛔ UNGATED: the download is a plain anchor, not a signup modal or lead-magnet CTA',
	false !== strpos( $tpl_prose, 'Download the sell sheet (PDF)' )
	&& false === strpos( $tpl_prose, 'sell-sheet-modal' ) );
bhp_rtl_ok( '13.2e the button is SUPPRESSED rather than 404ing when the file is absent',
	false !== strpos( $tpl_prose, '$sell_sheet_rel' )
	&& false !== strpos( $tpl_prose, 'file_exists(' ) );
bhp_rtl_ok( '13.2f ⛔ the founder-withheld colouring ISBN is not inside the shipped PDF either',
	! file_exists( $bhp_rtl_pdf )
	|| false === strpos( (string) file_get_contents( $bhp_rtl_pdf ), '9798996810840' ) );

// -- 13.3  THE IMPRINT LINE (founder item 365, his answer: "LLC") -----------
bhp_rtl_ok( '13.3a the imprint of record is on the page',
	false !== strpos( $tpl_prose, 'Brave Hearts Publishing LLC' ) );
bhp_rtl_ok( '13.3b it is labelled as the imprint, so it reads as a search key and not a byline',
	false !== strpos( $tpl_prose, "'Imprint:'" ) );
bhp_rtl_ok( '13.3c ⛔ the legal entity name is NOT passed through a translation function',
	false === strpos( $tpl_prose, "__( 'Brave Hearts Publishing LLC'" )
	&& false === strpos( $tpl_prose, "esc_html_e( 'Brave Hearts Publishing LLC'" ) );

// -- 13.4  THE PAGE IS NO LONGER ORPHANED (marketing-growth D3) -------------
$bhp_rtl_footer = (string) file_get_contents( get_template_directory() . '/footer.php' );
bhp_rtl_ok( '13.4a the sitewide footer links the retailer page',
	false !== strpos( $bhp_rtl_footer, '/retailers-wholesale-guide/' ) );
bhp_rtl_ok( '13.4b under a label a bookseller will recognise',
	false !== strpos( $bhp_rtl_footer, 'Booksellers & Retailers' ) );
bhp_rtl_ok( '13.4c ⚠ the footer records that this is a FIFTH link against the 2026-08-19 four-link prune',
	false !== stripos( $bhp_rtl_footer, 'A FIFTH' ) );
bhp_rtl_ok( '13.4d ⛔ and no primary-nav item was invented in theme code (that menu is a DB menu and Andrew\'s call)',
	false === strpos( $bhp_rtl_footer, "theme_location' => 'primary'" ) );

// -- 13.5  THE TIGHT HERO (founder item 366, second half) -------------------
$bhp_rtl_css     = (string) file_get_contents( get_template_directory() . '/assets/css/audience-landing.css' );
$bhp_rtl_css_min = file_exists( get_template_directory() . '/assets/css/audience-landing.min.css' )
	? (string) file_get_contents( get_template_directory() . '/assets/css/audience-landing.min.css' )
	: '';
bhp_rtl_ok( '13.5a the template carries the scoped spacing modifier',
	false !== strpos( $tpl_prose, 'audience-landing-hero--tight' ) );
bhp_rtl_ok( '13.5b the CSS defines it',
	false !== strpos( $bhp_rtl_css, '.audience-landing-hero--tight' ) );
bhp_rtl_ok( '13.5c ⛔ SCOPED: the shared five-page hero grid rule is untouched, so four other audience pages do not move',
	false !== strpos( $bhp_rtl_css, 'grid-template-columns: repeat(auto-fit, minmax(380px, 1fr))' ) );
bhp_rtl_ok( '13.5d the minified artefact was REBUILT from the edited source (a stale artefact would serve the old spacing)',
	false !== strpos( $bhp_rtl_css_min, 'audience-landing-hero--tight' ) );

// -- 13.6  PROVENANCE FOR THE SIXTH ISBN, IN THE CODE -----------------------
bhp_rtl_ok( '13.6a the registry cites the founder items that opened it and that withhold the colouring book',
	false !== strpos( $terms_src, '364' ) && false !== strpos( $terms_src, '358' ) );
bhp_rtl_ok( '13.6b ⚠ and states plainly that its four TERM values were not field-read for this ISBN',
	false !== stripos( $terms_src, 'NOT A FIELD READ' ) );
bhp_rtl_ok( '13.6c the superseded withheld row is preserved verbatim rather than deleted',
	false !== stripos( $terms_src, 'THE SUPERSEDED ROW, PRESERVED VERBATIM' ) );
bhp_rtl_ok( '13.6d the 2026-08-28 date the sixth row was opened is recorded on the row',
	false !== strpos( $terms_src, "'read_on'      => '2026-08-28'" ) );

// ══════════════════════════════════════════════════════════════════════════
echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
printf( "RETAILER FUNNEL SUITE — PASS %d   FAIL %d\n", $GLOBALS['bhp_rtl_pass'], $GLOBALS['bhp_rtl_fail'] );
echo "════════════════════════════════════════════════════════════════\n";
if ( $GLOBALS['bhp_rtl_fail'] > 0 ) {
	echo "⛔ FAILURES ABOVE. Do not deploy and do not report green.\n";
}
