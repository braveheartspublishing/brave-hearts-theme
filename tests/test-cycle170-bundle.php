<?php
/**
 * CYCLE170-LD-BUNDLE — the two-page architecture, the wall calendar, the single
 * tail ask, and /positivity-news/. Theme 1.19.333 (2026-08-30). STAGING ONLY.
 *
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Five things in this bundle can fail
 *    silently and expensively; everything else here is ordinary cover.
 *
 *    · **The redirect split.** `/author-visits/` is live on production and is
 *      reached from PRINTED QR CODES TAPED TO CLASSROOM DOORS. Item 524 says it
 *      must never 301. The parent zone has been REMOVED from
 *      `/school-read-alouds/` in the same act, so if the slug list ever regains
 *      `author-visits`, every QR scan lands on a page with no order button and
 *      paper that cannot be recalled stops working. ⭐ THAT IS ASSERTED IN ALL
 *      THREE OPTION STATES, not just the current one.
 *
 *    · **The approved copy.** Founder-voice sentences go onto a public page. The
 *      failure mode is not a typo, it is a machine having SILENTLY ALTERED or
 *      invented words. Asserted CHARACTER-EXACT against literals transcribed
 *      here independently of the code under test.
 *
 *    · **The tags.** `Newsletter Only` and `Friends & Family` were CREATED in
 *      the live audience on 2026-08-30. Mailchimp matches tags by NAME, so a
 *      tidied ampersand or a stray space creates a third tag and the segment
 *      misses the subscriber. Asserted by CALLING the real filter.
 *
 *    · **The lead magnet that must not exist.** `/positivity-news/` promises a
 *      newsletter and nothing else. A magnet key on that form would promise a
 *      download the copy never mentions.
 *
 *    · **The CSS class collision.** `.readaloud-sched__grid` names both the
 *      calendar `<table>` and the "Who and where" `<div>`. Until 1.19.333 the
 *      div's `display: grid` won on source order and the calendar table
 *      computed as a ONE-COLUMN GRID on the live site. Both selectors must stay
 *      element-qualified or the wall calendar silently un-builds itself.
 *
 * ⛔ THE COUNTERS ARE IN $GLOBALS, NOT `global $x`. `wp eval-file` includes this
 *    file INSIDE A FUNCTION, so a top-level variable is that function's LOCAL
 *    and `global $x` binds a different, empty slot — which prints
 *    "PASS: 0 FAIL: 0 / ALL PASS" over a visibly failing run. That happened for
 *    real on 2026-08-29 (finding F8 of the 1.19.319 candidate).
 *
 * ⚠ WHAT THIS SUITE WRITES: `bhp_school_readalouds_unify` is set and deleted to
 *   prove the switch, then restored to exactly the value it had on entry
 *   (including "absent" as a distinct state from "empty"). It touches NO post,
 *   page, product, coupon, price or WooCommerce setting, SUBMITS NO FORM, SENDS
 *   NO MAIL, subscribes nobody and calls no external service.
 *
 * Run: wp eval-file tests/test-cycle170-bundle.php --user=1
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assert.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Message.
 */
function bhp_bun_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_bun_pass'] ) ) {
		$GLOBALS['bhp_bun_pass'] = 0;
		$GLOBALS['bhp_bun_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_bun_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_bun_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * A PHP file's CODE, with every comment and docblock removed.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHY THIS EXISTS, RECORDED BECAUSE IT WAS A REAL DEFECT IN THIS SUITE'S
 *     FIRST RUN AND NOT A PRECAUTION.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The first run produced FIVE reds of the form *"the teacher template no longer
 * contains: Upcoming Visits"*. Every one of them was matching the templates' own
 * BLOCK COMMENTS — the paragraphs that explain that the parent zone was removed
 * and must not come back, and the ones on `page-positivity-news.php` explaining
 * why `require_name` and `success_redirect_key` are deliberately absent.
 *
 * ⛔ SO THE ASSERTIONS WERE WRONG, NOT THE BUILD. A file cannot both document why
 *    a thing was removed and pass a naive `strpos()` for that thing's name — and
 *    the documentation is the more valuable of the two. The alternative "fix"
 *    would have been to delete the explanatory comments to make a test pass,
 *    which is exactly backwards.
 *
 * ⭐ `token_get_all()` IS PHP'S OWN LEXER, so this is an exact separation of code
 *    from commentary rather than a regex that approximates one. What remains is
 *    what the interpreter actually executes.
 *
 * ⚠ HTML OUTSIDE `<?php … ?>` SURVIVES, and that is correct: markup a template
 *   prints IS code for this purpose.
 *
 * @param string $path Absolute path to a PHP file.
 * @return string
 */
function bhp_bun_code_only( $path ) {
	$src  = (string) file_get_contents( $path );
	$out  = '';
	$skip = array( T_COMMENT, T_DOC_COMMENT );
	foreach ( token_get_all( $src ) as $tok ) {
		if ( is_array( $tok ) ) {
			if ( in_array( $tok[0], $skip, true ) ) {
				continue;
			}
			$out .= $tok[1];
		} else {
			$out .= $tok;
		}
	}
	return $out;
}

/**
 * Fetch a page's rendered HTML over HTTP, the way a visitor would get it.
 *
 * ⛔ `wp_remote_get`, NOT an internal render. A template's output depends on
 *    the main query, on `wp_footer`, on enqueued assets and on every gate that
 *    reads `is_page_template()` — none of which is right inside a CLI process.
 *
 * @param string $path Site-relative path.
 * @return array{code:int,body:string}
 */
function bhp_bun_fetch( $path ) {
	$r = wp_remote_get(
		home_url( $path ),
		array(
			'timeout'   => 45,
			'sslverify' => false,
		)
	);
	return array(
		'code' => (int) wp_remote_retrieve_response_code( $r ),
		'body' => (string) wp_remote_retrieve_body( $r ),
	);
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * THE APPROVED TEXT, RESTATED INDEPENDENTLY OF THE CODE UNDER TEST.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THESE LITERALS ARE TRANSCRIBED FROM THEIR SOURCES, NOT IMPORTED FROM THE
 *    FUNCTIONS THEY CHECK. An assertion that compares a function's output to
 *    itself passes on ANY text, including text a later pass quietly rewrote.
 *    The duplication is the entire value of this section.
 *
 * Item 522 / 523 source: the `CYCLE170-LD-BUNDLE` build brief.
 *   ⚠ SINGLE-SOURCE. A corpus-wide search on 2026-08-30 found no file under
 *   `C:\BHP` carrying this text, so unlike the item-512 passages there is no
 *   second copy on disk to diff against. Recorded, not glossed.
 *
 * Item 489 source: `WORKING-DRAFTS\connected-operator\CYCLE170-GIM-TRIPLE\
 *   LANDING-PAGE-COPY-DECK.md`, corroborated independently by the Mailchimp
 *   read-back in `CYCLE170-GIM-LANDING-FILL\RESULT-42351-2026-08-30.md` §1.
 */
$bhp_bun_points = array(
	'A short presentation on the character values behind the books: bravery, resilience, kindness, and curiosity.',
	'Mantras: repeated phrases like "I can do hard things!" that empower little humans to be confident, strong, and brave.',
	'Kid friendly breathing techniques for regulating emotions.',
	'I read one book, and I answer every question they have.',
);

$bhp_bun_closing = 'These books are made for early readers, to help them gain reading confidence and confidence in life. My goal is to empower kids to be better little humans, in the classroom and outside of it.';

/*
 * ⛔⛔ AMENDED AT 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562, Gandalf's
 *     implementation ruling). CHIP 1 DROPPED THE WORD "Free".
 *
 * ⛔ SUPERSEDED FIXTURE, QUOTED VERBATIM RATHER THAN DELETED:
 *
 *        $bhp_bun_chips = array(
 *            'Free for Boise-area schools',
 *            'October onward',
 *            'I confirm every request personally',
 *        );
 *
 * ⭐ THE PROPERTY THIS FIXTURE GUARDS IS UNCHANGED and is the reason it stays an
 *    independently-typed literal rather than becoming a call to the function
 *    under test: the chips must be exactly what he approved, character for
 *    character, so a fourth chip or a reworded one still fails.
 *
 * ⚠️ THE WORD IS NOT LOST FROM THE PAGE, and `tests/test-cycle170-final2.php` §2
 *    is where that is proven — it counts "free" in the rendered hero and requires
 *    EXACTLY TWO (the `<h1>` and the CTA button, both item 481's own words).
 */
$bhp_bun_chips = array(
	'Boise-area schools',
	'October onward',
	'I confirm every request personally',
);

$bhp_bun_pn = array(
	'headline' => 'Positivity News by Brave Hearts Publishing',
	'subhead'  => 'An ounce of positivity in a dark place.',
	'body'     => array(
		'Everyone knows the news is negative. This is the opposite.',
		'Once a month I will send you the highlights from the company. Only positive things to brighten your day, I promise.',
	),
	'submit'   => 'Subscribe',
	'thanks'   => 'Thank you for subscribing to Brave Hearts Publishing. You will get this email once a month. Only positive things, I promise.',
	'sign_off' => 'Big Places. Brave Hearts.',
);

$bhp_bun_tpl_dir = get_template_directory();

echo "\n=== 1 · THE APPROVED VISIT SECTION (item 523) ===\n";

bhp_bun_assert( function_exists( 'bhp_readaloud_visit_shape_points' ), 'bhp_readaloud_visit_shape_points() exists' );
bhp_bun_assert( function_exists( 'bhp_readaloud_visit_shape_closing' ), 'bhp_readaloud_visit_shape_closing() exists' );

$bhp_bun_got_points = function_exists( 'bhp_readaloud_visit_shape_points' ) ? bhp_readaloud_visit_shape_points() : array();
/*
 * ⛔ AMENDED AT 1.19.336 (`CYCLE170-LD-CHAIN`, founder-sealed item 541).
 *
 * ⛔ SUPERSEDED LINE, quoted rather than deleted:
 *
 *      bhp_bun_assert( 4 === count( $bhp_bun_got_points ), 'exactly FOUR numbered points, got ' . count( $bhp_bun_got_points ) );
 *
 * ⭐ A FIFTH POINT WAS APPROVED: "I leave a signed copy for your classroom
 *    library, free." The property this assertion guards is unchanged, and it is
 *    the reason it stays an EXACT count rather than becoming a `>=`: the list
 *    must be exactly what he approved, so a sixth point arriving from anywhere
 *    still fails.
 *
 * ⚠️ THE `$bhp_bun_points` FIXTURE ABOVE IS DELIBERATELY LEFT AT FOUR. It is the
 *    independently-transcribed copy of the ORIGINAL four, and the loop below
 *    still diffs each of them character by character. The fifth is asserted
 *    separately, against its own independently-transcribed literal, so no
 *    assertion here compares the code under test to itself.
 */
bhp_bun_assert( 5 === count( $bhp_bun_got_points ), 'exactly FIVE numbered points, got ' . count( $bhp_bun_got_points ) );
bhp_bun_assert(
	isset( $bhp_bun_got_points[4] ) && 'I leave a signed copy for your classroom library, free.' === $bhp_bun_got_points[4],
	'⭐⭐ the FIFTH point is item 541 VERBATIM, character-exact, and it is LAST'
);

foreach ( $bhp_bun_points as $bhp_bun_i => $bhp_bun_want ) {
	$bhp_bun_have = isset( $bhp_bun_got_points[ $bhp_bun_i ] ) ? $bhp_bun_got_points[ $bhp_bun_i ] : '';
	bhp_bun_assert(
		$bhp_bun_want === $bhp_bun_have,
		'point ' . ( $bhp_bun_i + 1 ) . ' is CHARACTER-EXACT'
	);
}

/* ⛔ THE MANTRA'S QUOTES ARE STRAIGHT (U+0022), NOT CURLY. The brief marks this
      copy character-exact, so a smart-quote pass is a character change. */
bhp_bun_assert(
	isset( $bhp_bun_got_points[1] ) && false !== strpos( $bhp_bun_got_points[1], '"I can do hard things!"' ),
	'⛔ the mantra keeps STRAIGHT double quotes, not typographic ones'
);

bhp_bun_assert(
	$bhp_bun_closing === ( function_exists( 'bhp_readaloud_visit_shape_closing' ) ? bhp_readaloud_visit_shape_closing() : '' ),
	'the closing couplet is CHARACTER-EXACT and is ONE contiguous string'
);

/* ⛔ ZERO EM DASHES AND ZERO EN DASHES in anything a visitor reads. House rule.
      Checked on the CONSTANTS, so it cannot be defeated by an HTML entity. */
$bhp_bun_all_copy = implode( ' ', $bhp_bun_got_points ) . ' '
	. ( function_exists( 'bhp_readaloud_visit_shape_closing' ) ? bhp_readaloud_visit_shape_closing() : '' ) . ' '
	. ( function_exists( 'bhp_readaloud_hero_chips' ) ? implode( ' ', bhp_readaloud_hero_chips() ) : '' );
bhp_bun_assert( false === strpos( $bhp_bun_all_copy, "\xe2\x80\x94" ), '⛔ ZERO em dashes in the item 522/523 copy' );
bhp_bun_assert( false === strpos( $bhp_bun_all_copy, "\xe2\x80\x93" ), '⛔ ZERO en dashes in the item 522/523 copy' );

echo "\n=== 2 · THE HERO CHIPS (item 522) ===\n";

$bhp_bun_got_chips = function_exists( 'bhp_readaloud_hero_chips' ) ? bhp_readaloud_hero_chips() : array();
bhp_bun_assert( $bhp_bun_chips === array_values( $bhp_bun_got_chips ), 'the three chips are CHARACTER-EXACT and in order' );

/* ⛔ NO PUNCTUATION IS BAKED INTO THE APPROVED STRINGS. The middot separator is
      CSS generated content; a chip carrying its own separator would mean the
      stored copy differs from what was approved, and would leave a trailing
      mark when the list wraps. */
foreach ( $bhp_bun_got_chips as $bhp_bun_chip ) {
	bhp_bun_assert(
		false === strpos( $bhp_bun_chip, '·' )
			&& false === strpos( $bhp_bun_chip, '|' )
			&& $bhp_bun_chip === trim( $bhp_bun_chip ),
		'chip carries no separator character and no padding: ' . $bhp_bun_chip
	);
}

echo "\n=== 3 · THE REDIRECT SPLIT (item 524) — THE PRINTED-PAPER GUARD ===\n";

$bhp_bun_merged = bhp_school_readalouds_merged_slugs();
bhp_bun_assert(
	array( 'gallery' ) === array_values( $bhp_bun_merged ),
	'⛔⛔ EXACTLY ONE merged slug and it is `gallery`: ' . implode( ', ', $bhp_bun_merged )
);
bhp_bun_assert(
	! in_array( 'author-visits', $bhp_bun_merged, true ),
	'⛔⛔ `author-visits` is NOT in the merged slug list'
);

/*
 * ⛔ AND IT MUST HOLD IN ALL THREE OPTION STATES, not merely in today's. The
 *    option is what the production ship step sets; if the slug list were still
 *    consulted for `author-visits` in any state, printed QR codes would break
 *    at the moment somebody armed the redirect.
 */
$bhp_bun_had_opt = get_option( 'bhp_school_readalouds_unify', '__ABSENT__' );

foreach ( array( '1', '0' ) as $bhp_bun_state ) {
	update_option( 'bhp_school_readalouds_unify', $bhp_bun_state );
	bhp_bun_assert(
		! in_array( 'author-visits', bhp_school_readalouds_merged_slugs(), true ),
		"⛔⛔ with the unify option = \"{$bhp_bun_state}\", `author-visits` STILL never redirects"
	);
}

delete_option( 'bhp_school_readalouds_unify' );
bhp_bun_assert(
	! in_array( 'author-visits', bhp_school_readalouds_merged_slugs(), true ),
	'⛔⛔ with the unify option ABSENT, `author-visits` STILL never redirects'
);

/* Restore the option to exactly what it was, treating "absent" as its own state. */
if ( '__ABSENT__' === $bhp_bun_had_opt ) {
	delete_option( 'bhp_school_readalouds_unify' );
} else {
	update_option( 'bhp_school_readalouds_unify', $bhp_bun_had_opt );
}
bhp_bun_assert(
	get_option( 'bhp_school_readalouds_unify', '__ABSENT__' ) === $bhp_bun_had_opt,
	'⚠ the unify option is RESTORED to its entry value'
);

/* ⛔ THE DESTINATION GUARD IS STILL THERE. The option is belt and braces; the
      real protection is that no redirect fires into a page that does not
      exist. */
$bhp_bun_sra_src = (string) file_get_contents( $bhp_bun_tpl_dir . '/inc/school-read-alouds.php' );
bhp_bun_assert(
	(bool) preg_match( '/get_page_by_path\(\s*bhp_school_readalouds_slug\(\)\s*\)/', $bhp_bun_sra_src )
		&& false !== strpos( $bhp_bun_sra_src, "'publish' !== \$target->post_status" ),
	'⛔ the redirect STILL refuses to fire unless the destination exists AND is published'
);

echo "\n=== 4 · THE PARENT ZONE IS OFF THE TEACHER PAGE (item 524) ===\n";

/* ⛔ CODE ONLY. The template's own comments explain at length that the parent
      zone was removed and must not return; a naive read of the file would match
      that explanation and red. See `bhp_bun_code_only()`. */
$bhp_bun_page_src = bhp_bun_code_only( $bhp_bun_tpl_dir . '/page-school-read-alouds.php' );

/*
 * ⛔ THESE ARE SOURCE ASSERTIONS ON THE TEMPLATE, AND THEY ARE THE WEAKER HALF.
 *    The rendered-page assertions in section 9 are the ones that prove it to a
 *    visitor. Both are kept: source catches a re-add during review, render
 *    catches a re-add that arrives through a filter or an include.
 */
foreach ( array(
	'Upcoming Visits',
	'How It Works',
	'Order signed books for this visit',
	'Ordering closed',
	'bhp_author_visits_rows',
) as $bhp_bun_gone ) {
	bhp_bun_assert(
		false === strpos( $bhp_bun_page_src, $bhp_bun_gone ),
		"⛔ the teacher template no longer contains: {$bhp_bun_gone}"
	);
}

/* ⭐ AND THE PAST LIST STAYS. It carries no order button, no `?bhp_visit=` link
      and no transaction, so it is trust evidence rather than a parent funnel. */
bhp_bun_assert(
	false !== strpos( $bhp_bun_page_src, 'bhp_author_visits_past_rows' ),
	'⭐ the PAST read-aloud list is still rendered on the teacher page'
);

/* ⛔⛔ `/author-visits/` ITSELF IS UNEDITED. The parent zone did not move, it
       stayed where it already was. If this fails, the parent page lost the
       thing the QR codes point at. */
$bhp_bun_av_src = bhp_bun_code_only( $bhp_bun_tpl_dir . '/page-author-visits.php' );
foreach ( array( 'Upcoming Visits', 'How It Works', 'Order signed books for this visit' ) as $bhp_bun_keep ) {
	bhp_bun_assert(
		false !== strpos( $bhp_bun_av_src, $bhp_bun_keep ),
		"⛔⛔ /author-visits/ STILL carries the parent zone: {$bhp_bun_keep}"
	);
}

echo "\n=== 5 · THE SINGLE TAIL ASK — funnel separation ===\n";

bhp_bun_assert( function_exists( 'bhp_school_readalouds_is_page' ), 'bhp_school_readalouds_is_page() exists' );

foreach ( array(
	'bhp_show_parent_popup',
	'bhp_show_parent_ab_popup',
	'bhp_show_exit_intent_popup',
	'bhp_show_quiz_cta',
) as $bhp_bun_hook ) {
	bhp_bun_assert(
		false !== has_filter( $bhp_bun_hook, 'bhp_school_readalouds_suppress_parent_surfaces' ),
		"⛔ the teacher page suppresses `{$bhp_bun_hook}`"
	);
}
bhp_bun_assert(
	false !== has_filter( 'bhp_show_footer_capture', 'bhp_school_readalouds_suppress_footer_capture' ),
	'⛔ the teacher page suppresses `bhp_show_footer_capture`'
);

/* ⛔ AND EVERY CALLBACK IS A NO-OP OFF-TEMPLATE. This is the assertion that
      proves no other page on the site lost its surface. Under CLI
      `bhp_school_readalouds_is_page()` is false, so the callbacks must return
      their input untouched in both directions. */
bhp_bun_assert(
	true === bhp_school_readalouds_suppress_parent_surfaces( true )
		&& false === bhp_school_readalouds_suppress_parent_surfaces( false ),
	'⛔ the parent-surface callback returns $show UNCHANGED off-template'
);
bhp_bun_assert(
	true === bhp_school_readalouds_suppress_footer_capture( true ),
	'⛔ the footer-capture callback returns $show UNCHANGED off-template'
);

echo "\n=== 6 · noindex ON BOTH NEW SURFACES ===\n";

foreach ( array(
	'wp_robots'                => 'bhp_school_readalouds_robots',
	'rank_math/frontend/robots' => 'bhp_school_readalouds_rankmath_robots',
) as $bhp_bun_hook => $bhp_bun_cb ) {
	bhp_bun_assert( false !== has_filter( $bhp_bun_hook, $bhp_bun_cb ), "⛔ /school-read-alouds/ registers {$bhp_bun_cb} on {$bhp_bun_hook}" );
}
foreach ( array(
	'wp_robots'                => 'bhp_positivity_news_robots',
	'rank_math/frontend/robots' => 'bhp_positivity_news_rankmath_robots',
) as $bhp_bun_hook => $bhp_bun_cb ) {
	bhp_bun_assert( false !== has_filter( $bhp_bun_hook, $bhp_bun_cb ), "⛔ /positivity-news/ registers {$bhp_bun_cb} on {$bhp_bun_hook}" );
}

/*
 * ⛔ AND EVERY ROBOTS CALLBACK IS A NO-OP OFF-TEMPLATE. Under CLI both
 *    `_is_page()` helpers are false, so an unrelated page's robots directives
 *    must come back byte-identical. If this fails, the whole site went noindex.
 */
$bhp_bun_robots_in = array( 'index' => true, 'follow' => true, 'max-image-preview' => 'large' );
bhp_bun_assert(
	$bhp_bun_robots_in === bhp_school_readalouds_robots( $bhp_bun_robots_in )
		&& $bhp_bun_robots_in === bhp_positivity_news_robots( $bhp_bun_robots_in ),
	'⛔⛔ the wp_robots callbacks return their input UNCHANGED off-template'
);
$bhp_bun_rm_in = array( 'index' => 'index', 'follow' => 'follow' );
bhp_bun_assert(
	$bhp_bun_rm_in === bhp_school_readalouds_rankmath_robots( $bhp_bun_rm_in )
		&& $bhp_bun_rm_in === bhp_positivity_news_rankmath_robots( $bhp_bun_rm_in ),
	'⛔⛔ the Rank Math callbacks return their input UNCHANGED off-template'
);

echo "\n=== 7 · /positivity-news/ — COPY, TAGS, AND THE MISSING LEAD MAGNET ===\n";

bhp_bun_assert( function_exists( 'bhp_positivity_news_copy' ), 'bhp_positivity_news_copy() exists' );
$bhp_bun_got_pn = function_exists( 'bhp_positivity_news_copy' ) ? bhp_positivity_news_copy() : array();

foreach ( array( 'headline', 'subhead', 'submit', 'thanks', 'sign_off' ) as $bhp_bun_k ) {
	bhp_bun_assert(
		isset( $bhp_bun_got_pn[ $bhp_bun_k ] ) && $bhp_bun_pn[ $bhp_bun_k ] === $bhp_bun_got_pn[ $bhp_bun_k ],
		"item 489 `{$bhp_bun_k}` is CHARACTER-EXACT"
	);
}
bhp_bun_assert(
	isset( $bhp_bun_got_pn['body'] ) && $bhp_bun_pn['body'] === array_values( $bhp_bun_got_pn['body'] ),
	'item 489 body copy is CHARACTER-EXACT, both sentences, in order'
);

$bhp_bun_pn_all = implode( ' ', array( $bhp_bun_got_pn['headline'], $bhp_bun_got_pn['subhead'], $bhp_bun_got_pn['submit'], $bhp_bun_got_pn['thanks'], $bhp_bun_got_pn['sign_off'] ) )
	. ' ' . implode( ' ', (array) $bhp_bun_got_pn['body'] );
bhp_bun_assert( false === strpos( $bhp_bun_pn_all, "\xe2\x80\x94" ), '⛔ ZERO em dashes in the item 489 copy' );
bhp_bun_assert( false === strpos( $bhp_bun_pn_all, "\xe2\x80\x93" ), '⛔ ZERO en dashes in the item 489 copy' );

/*
 * ⛔⛔ THE TAGS, ASSERTED BY CALLING THE REAL FILTER RATHER THAN BY READING THE
 *     CALLBACK. That is what proves the priority actually wins against every
 *     other callback registered on this hook, which is the only thing that
 *     matters at run time.
 */
$bhp_bun_tags = bhp_get_mailchimp_signup_tags( bhp_positivity_news_context(), 'general_readers', '', bhp_positivity_news_url() );
bhp_bun_assert(
	array( 'Newsletter Only', 'Friends & Family' ) === array_values( $bhp_bun_tags ),
	'⛔⛔ the live pipe returns EXACTLY the two tags, in order: ' . implode( ' | ', $bhp_bun_tags )
);

/* ⛔ THE AMPERSAND SURVIVES `sanitize_text_field()` UNENCODED. Mailchimp matches
      tags by name; `Friends &amp; Family` would create a THIRD tag and the
      segment would silently miss these people. */
bhp_bun_assert(
	in_array( 'Friends & Family', $bhp_bun_tags, true ),
	'⛔ `Friends & Family` survives the pipe with a literal ampersand'
);

/* ⛔ NO FUNNEL MEMBERSHIP TAG LEAKS IN. `Adventure Club` is the pipe's default
      and every `Audience:` tag is funnel membership. A newsletter-only
      subscriber must carry neither. */
foreach ( $bhp_bun_tags as $bhp_bun_t ) {
	bhp_bun_assert(
		'Adventure Club' !== $bhp_bun_t && 0 !== strpos( $bhp_bun_t, 'Audience:' ) && 0 !== strpos( $bhp_bun_t, 'Source:' ),
		"⛔ no funnel/audience/source tag leaked in: {$bhp_bun_t}"
	);
}

/* ⛔ AND THE CALLBACK IS INERT OFF-CONTEXT. If this fails, another form on the
      site has had its tags rewritten. */
$bhp_bun_other = bhp_get_mailchimp_signup_tags( 'adventure_club', 'general_readers', '', home_url( '/' ) );
bhp_bun_assert(
	! in_array( 'Newsletter Only', $bhp_bun_other, true ) && ! in_array( 'Friends & Family', $bhp_bun_other, true ),
	'⛔ the newsletter tags do NOT leak onto another context: ' . implode( ' | ', $bhp_bun_other )
);

/* ⛔ THE PRIORITY IS 30, one step later than inc/read-aloud-landing.php's 20, so
      the outcome is a stated rule rather than a consequence of require order. */
bhp_bun_assert(
	30 === has_filter( 'bhp_mailchimp_signup_tags', 'bhp_positivity_news_mailchimp_tags' ),
	'⛔ the newsletter tag callback is registered at priority 30'
);

/* ⛔⛔ NO LEAD MAGNET ANYWHERE ON THE PAGE. */
$bhp_bun_pn_src = bhp_bun_code_only( $bhp_bun_tpl_dir . '/page-positivity-news.php' );
bhp_bun_assert(
	(bool) preg_match( "/'lead_magnet'\s*=>\s*''/", $bhp_bun_pn_src ),
	'⛔⛔ the signup form is passed an EMPTY lead_magnet'
);
foreach ( array( 'reluctant_reader_adventure_kit', 'teacher_adventure_toolkit', 'mariana_trench', 'success_redirect_key', 'require_name' ) as $bhp_bun_forbidden ) {
	bhp_bun_assert(
		false === strpos( $bhp_bun_pn_src, $bhp_bun_forbidden ),
		"⛔ the newsletter page carries no `{$bhp_bun_forbidden}`"
	);
}

echo "\n=== 8 · THE CSS CLASS COLLISION — the wall calendar's prerequisite ===\n";

$bhp_bun_css = (string) file_get_contents( $bhp_bun_tpl_dir . '/style.css' );

/*
 * ⛔⛔ BOTH RULES MUST STAY ELEMENT-QUALIFIED. `.readaloud-sched__grid` names the
 *     calendar `<table>` AND the "Who and where" `<div>`. Unqualified, the div's
 *     `display: grid` wins on source order and applies to the table as well —
 *     VERIFIED LIVE on staging 1.19.332, where the calendar computed
 *     `display: grid` with `grid-template-columns: 350px` and its `<th>` was
 *     14.3px wide. If either qualifier is dropped, the calendar silently stops
 *     being a calendar again.
 */
bhp_bun_assert(
	(bool) preg_match( '/(^|[\s,])table\.readaloud-sched__grid\s*\{/m', $bhp_bun_css ),
	'⛔⛔ the calendar rule is qualified `table.readaloud-sched__grid`'
);
bhp_bun_assert(
	(bool) preg_match( '/(^|[\s,])div\.readaloud-sched__grid\s*\{/m', $bhp_bun_css ),
	'⛔⛔ the fields rule is qualified `div.readaloud-sched__grid`'
);
bhp_bun_assert(
	! preg_match( '/^\.readaloud-sched__grid\s*\{/m', $bhp_bun_css ),
	'⛔⛔ NO unqualified `.readaloud-sched__grid { … }` rule remains'
);

/* ⭐ The wall-calendar restyle is present and is scoped to this page. */
foreach ( array(
	'.school-readalouds .readaloud-sched__cal {',
	'.school-readalouds .readaloud-sched__cal-nav {',
	'.school-readalouds .readaloud-sched__dow {',
	'.school-readalouds table.readaloud-sched__grid {',
	'.school-readalouds .readaloud-sched__day-input:checked + .readaloud-sched__day {',
	'.school-readalouds .readaloud-sched__slot {',
) as $bhp_bun_sel ) {
	bhp_bun_assert( false !== strpos( $bhp_bun_css, $bhp_bun_sel ), "⭐ wall-calendar rule present and page-scoped: {$bhp_bun_sel}" );
}

/*
 * ⚠ THE SPACING SCALE HAS NO `--space-5`. It runs 1,2,3,4,6,8,10,12,16,20,24,32.
 *   A rule referencing it resolves to nothing and the declaration silently
 *   collapses. That exact defect shipped once at 1.19.325 and only a suite
 *   caught it, which is why the check exists at all.
 *
 * ⛔⛔ THE ASSERTION IS SCOPED TO THIS LANE'S OWN BLOCK, AND THE SITEWIDE COUNT
 *     IS PRINTED AS A NOTE RATHER THAN AS A RED. THE REASON MATTERS:
 *
 *     The first run of this suite asserted it sitewide and failed. That was NOT
 *     this lane's doing — it found SEVEN PRE-EXISTING `var(--space-5)`
 *     references already in the shipped stylesheet, the oldest of them on
 *     `.free-resource-card__preview`. Every one is a margin or padding that has
 *     been silently collapsing to nothing on the live site.
 *
 *     ⛔ A RED HERE WOULD BE A RED THIS LANE CANNOT CLEAR WITHOUT EDITING SEVEN
 *        UNRELATED COMPONENTS' SPACING — a visual change to the homepage, the
 *        Amazon review showcase and the free-resource card, none of which is in
 *        this brief. So it is REPORTED to Gandalf as an inherited finding and
 *        left for a decision, not absorbed and not silently "fixed".
 *
 *     ⚠ AND IT IS NOT SUPPRESSED EITHER: the count is printed on every run, so
 *       it cannot quietly grow.
 */
$bhp_bun_block_at = strpos( $bhp_bun_css, 'THE WALL CALENDAR · THE HERO CHIPS' );
$bhp_bun_block    = false === $bhp_bun_block_at ? '' : substr( $bhp_bun_css, $bhp_bun_block_at );
bhp_bun_assert( '' !== $bhp_bun_block, "this lane's own CSS block was located in style.css" );
bhp_bun_assert(
	'' !== $bhp_bun_block && false === strpos( $bhp_bun_block, 'var(--space-5)' ),
	"⚠ NO rule in THIS LANE'S block references the nonexistent `--space-5`"
);
echo '  NOTE  INHERITED, NOT THIS LANE: sitewide `var(--space-5)` references in style.css = '
	. substr_count( $bhp_bun_css, 'var(--space-5)' ) . " (each one silently collapses; routed to Gandalf)\n";

echo "\n=== 9 · THE RENDERED TEACHER PAGE ===\n";

$bhp_bun_sra = bhp_bun_fetch( '/school-read-alouds/' );
bhp_bun_assert( 200 === $bhp_bun_sra['code'], '/school-read-alouds/ returns 200, got ' . $bhp_bun_sra['code'] );
$bhp_bun_b = $bhp_bun_sra['body'];

/* ⭐ The approved copy actually reaches a visitor. */
foreach ( $bhp_bun_points as $bhp_bun_i => $bhp_bun_p ) {
	/* `esc_html()` turns the mantra's straight quotes into `&quot;`, so the
	   rendered comparison is made against the escaped form — the same characters
	   a reader sees. */
	bhp_bun_assert(
		false !== strpos( $bhp_bun_b, esc_html( $bhp_bun_p ) ),
		'rendered: visit point ' . ( $bhp_bun_i + 1 ) . ' is on the page'
	);
}
bhp_bun_assert( false !== strpos( $bhp_bun_b, esc_html( $bhp_bun_closing ) ), 'rendered: the closing couplet is on the page' );
foreach ( $bhp_bun_chips as $bhp_bun_c ) {
	bhp_bun_assert( false !== strpos( $bhp_bun_b, esc_html( $bhp_bun_c ) ), "rendered: chip is on the page: {$bhp_bun_c}" );
}
/*
 * ⭐ 1.19.334 (`CYCLE170-LD-MVP`): the apostrophe is the TYPOGRAPHIC one
 *    (U+2019), matching house typography and every other visible string on this
 *    page. 1.19.333 asserted the straight U+0027 the brief typed. One character
 *    moved in `inc/school-read-alouds.php` and one moved here, together.
 */
/*
 * ⛔⛔ AMENDED AT 1.19.335 (`CYCLE170-LD-WEEKPICKER`, carrier item 534) — ONE
 *     WORD, "day" → "week", AND THE ORIGINAL NEEDLES ARE PRESERVED HERE:
 *
 *       'Pick a day. I’ll come read to your class. Free.'   (was asserted present)
 *       "Pick a day. I'll come read to your class. Free."   (was asserted absent)
 *
 * ⭐ THIS SECTION'S SUBJECT IS THE APOSTROPHE AND IT IS UNCHANGED: the
 *    typographic form present, the straight form absent, both directions. A
 *    third assertion is added so the OLD headline is proven GONE rather than
 *    merely joined by the new one - the same both-directions discipline.
 */
bhp_bun_assert(
	false !== strpos( $bhp_bun_b, esc_html( 'Pick a week. I’ll come read to your class. Free.' ) ),
	'rendered: the scheduler header is the promoted wording, typographic apostrophe'
);
bhp_bun_assert(
	false === strpos( $bhp_bun_b, esc_html( "Pick a week. I'll come read to your class. Free." ) ),
	'⛔ rendered: the STRAIGHT-apostrophe form is gone, not merely joined by the curly one'
);
bhp_bun_assert(
	false === strpos( $bhp_bun_b, esc_html( 'Pick a day. I’ll come read to your class. Free.' ) ),
	'⛔ 1.19.335: the OLD "Pick a day" headline is gone from the rendered page'
);

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ ZERO PARENT-FUNNEL ELEMENTS. This is what the whole "single tail ask"
 *     ruling reduces to, and it is asserted against what a browser receives.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ `data-popup-config` IS THE DECISIVE ONE. It is the attribute the shared
 *    popup engine reads, it is the exact attribute `CYCLE170-LD-SHIP-PREP`'s
 *    finding **b** measured on this page at 1.19.332, and no popup of any funnel
 *    can render without it. Zero here means finding b is closed.
 *
 * ⛔⛔ TWO NEEDLES WERE REMOVED FROM THIS LIST AFTER THE FIRST RUN, AND THE
 *     REASON IS RECORDED RATHER THAN THE LIST QUIETLY SHRINKING:
 *
 *     `parent_popup` (3 hits) and `adventure-kit-thank-you` (2 hits) both still
 *     appear in the rendered HTML, and NEITHER IS A POPUP. Located and read, not
 *     assumed: two `parent_popup` hits are keys in the Meta Pixel's STATIC
 *     EVENT-NAME MAP (`window.bhpMetaPixel.config.map`), which is a lookup table
 *     shipped on every page of the site whether or not a popup exists; the
 *     remaining three hits are inside JavaScript COMMENTS in that same inline
 *     script.
 *
 *     ⛔ ASSERTING ON THEM WOULD HAVE MADE THIS TEST FAIL FOR SITEWIDE ANALYTICS
 *        CHROME, and the only way to "pass" it would have been to strip the
 *        pixel's event map — a real regression to satisfy a bad assertion. The
 *        storage prefix `bhp_parent_popup` IS still asserted below, because
 *        unlike the event-name map it appears only where a popup actually renders.
 *
 * ⚠ `mariana-popup` (the shared engine's SCRIPT URL) is likewise present on
 *   every page by design — `functions.php` enqueues it sitewide so a thank-you
 *   page can complete a pending-submit handoff. A script that is loaded is not a
 *   popup that is shown. Not asserted, and said out loud rather than left for
 *   the next reader to rediscover.
 */
foreach ( array(
	'data-popup-config'              => 'the popup engine config attribute (finding b)',
	'bhp_parent_popup'               => 'the parent popup storage prefix',
	'reluctant_reader_adventure_kit' => 'the parent lead magnet',
	'data-bhp-quiz-launcher'         => 'the quiz launcher',
	'quiz_cta_viewed'                => 'the quiz impression event',
	'footer-capture'                 => 'the sitewide footer capture block',
	'find my best next step'         => 'the quiz band copy',
) as $bhp_bun_needle => $bhp_bun_what ) {
	bhp_bun_assert(
		false === stripos( $bhp_bun_b, $bhp_bun_needle ),
		"⛔⛔ rendered: ZERO parent-funnel elements — {$bhp_bun_what} is absent"
	);
}

/* ⛔ AND THE UPCOMING/ORDER ZONE IS GONE FROM WHAT A VISITOR SEES. */
foreach ( array( 'Upcoming Visits', 'How It Works', 'Order signed books for this visit' ) as $bhp_bun_needle ) {
	bhp_bun_assert( false === strpos( $bhp_bun_b, $bhp_bun_needle ), "⛔ rendered: `{$bhp_bun_needle}` is absent from the teacher page" );
}

/* ⭐ THE TEACHER ASK IS STILL THERE. Removing the competitors must not have
      removed the offer. */
bhp_bun_assert(
	false !== strpos( $bhp_bun_b, 'teacher_adventure_toolkit' ),
	'⭐ rendered: the EDUCATOR toolkit capture is still on the page'
);

/*
 * ⭐ SECTION ORDER, MEASURED BY BYTE OFFSET RATHER THAN ASSERTED IN PROSE.
 *    Item 519: the form moves above the proof.
 */
/*
 * ⛔⛔ AMENDED AT 1.19.337 (`CYCLE170-LD-MICRO`) FOR CARRIER ITEM 552. THE
 *     CAROUSEL AND THE SCHEDULER SWAP PLACES, AND THIS ASSERTION PINNED THE
 *     ORDER THE FOUNDER JUST SUPERSEDED.
 *
 * ⭐⭐ FOUNDER, verbatim, 2026-08-30, item 552 (RELAYED, read first-hand at the
 *    carrier): *"I think we should bring the carousel up and put it with the
 *    'About' make the carousel a little smaller so it fits. Then bring up the
 *    pick a week as close as possible to it"*.
 *
 * ⛔ THIS IS A STALE TEST, NOT A REGRESSION, AND THAT WAS MEASURED RATHER THAN
 *    ASSUMED. The suite was run at BOTH versions on staging and the failure
 *    lists diffed (the method `CYCLE170-LD-CHAIN` §1a(ii) established). Exactly
 *    one line moved, and the offsets the failure itself printed show the page
 *    is in the NEW founder-ruled order, not in a broken one:
 *
 *      hero 112659 < visit 114411 < about 115739 < carousel 116660
 *                  < scheduler 127060 < past 161376 < capture 162514
 *
 * ⭐ SUPERSEDED KEY ORDER, PRESERVED VERBATIM so the movement is visible and
 *    item 519's intent is not re-derived as a bug:
 *
 *      'about' => …, 'scheduler' => …, 'carousel' => …,
 *      '⭐ rendered order is hero → visit → about → SCHEDULER → carousel → past → capture'
 */
$bhp_bun_pos = array(
	'hero'      => strpos( $bhp_bun_b, 'school-readalouds-hero-title' ),
	'visit'     => strpos( $bhp_bun_b, 'school-readalouds-visit-title' ),
	'about'     => strpos( $bhp_bun_b, 'school-readalouds-founder-title' ),
	'carousel'  => strpos( $bhp_bun_b, 'school-readalouds-gallery-read-alouds' ),
	'scheduler' => strpos( $bhp_bun_b, 'readaloud-sched-title' ),
	'past'      => strpos( $bhp_bun_b, 'school-readalouds-past-title' ),
	'capture'   => strpos( $bhp_bun_b, 'school-readalouds-capture-title' ),
);
$bhp_bun_order_ok = true;
$bhp_bun_prev     = -1;
foreach ( $bhp_bun_pos as $bhp_bun_name => $bhp_bun_off ) {
	if ( false === $bhp_bun_off || $bhp_bun_off < $bhp_bun_prev ) {
		$bhp_bun_order_ok = false;
	}
	$bhp_bun_prev = (int) $bhp_bun_off;
}
bhp_bun_assert(
	$bhp_bun_order_ok,
	'⭐ item 552 rendered order is hero → visit → about → CAROUSEL → scheduler → past → capture (' . wp_json_encode( $bhp_bun_pos ) . ')'
);

/* ⛔ noindex is actually served. */
bhp_bun_assert(
	(bool) preg_match( '/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $bhp_bun_b ),
	'⛔ rendered: /school-read-alouds/ serves a `noindex` robots meta'
);

/*
 * ⛔ NO PRICE, FEE OR RATE ON THE PAGE ITSELF. None exists (item 481).
 *
 * ⛔ SCOPED TO THE PAGE'S OWN WRAPPER, NOT THE WHOLE DOCUMENT, AND THAT IS
 *    HONESTY RATHER THAN LENIENCE. The site header and footer are shared chrome
 *    that can legitimately carry a cart total or an offer figure; asserting over
 *    them would make this test fail for a reason that has nothing to do with
 *    this page and would then get "fixed" by weakening it. The rail is about
 *    what THIS TEMPLATE writes.
 */
$bhp_bun_body_start = strpos( $bhp_bun_b, 'readaloud-funnel school-readalouds' );
$bhp_bun_body_end   = strpos( $bhp_bun_b, 'school-readalouds-pricing' );
$bhp_bun_own = ( false !== $bhp_bun_body_start && false !== $bhp_bun_body_end && $bhp_bun_body_end > $bhp_bun_body_start )
	? substr( $bhp_bun_b, $bhp_bun_body_start, $bhp_bun_body_end - $bhp_bun_body_start )
	: '';
bhp_bun_assert( '' !== $bhp_bun_own, 'the page wrapper was located, so the scoped checks below mean something' );
bhp_bun_assert(
	'' !== $bhp_bun_own && ! preg_match( '/\$\s?\d/', wp_strip_all_tags( $bhp_bun_own ) ),
	'⛔ rendered: no currency figure in the page\'s own visible text'
);

/* ⛔ AND NO EM DASH IN THE PAGE'S OWN VISIBLE TEXT. House rule, checked where a
      reader would meet one rather than in the source. */
bhp_bun_assert(
	'' !== $bhp_bun_own && false === strpos( wp_strip_all_tags( $bhp_bun_own ), "\xe2\x80\x94" ),
	'⛔ rendered: ZERO em dashes in the page\'s own visible text'
);

echo "\n=== 10 · THE RENDERED NEWSLETTER PAGE ===\n";

$bhp_bun_pnp = bhp_bun_fetch( '/positivity-news/' );
bhp_bun_assert( 200 === $bhp_bun_pnp['code'], '/positivity-news/ returns 200, got ' . $bhp_bun_pnp['code'] );
$bhp_bun_pb = $bhp_bun_pnp['body'];

foreach ( array( $bhp_bun_pn['headline'], $bhp_bun_pn['subhead'], $bhp_bun_pn['body'][0], $bhp_bun_pn['body'][1], $bhp_bun_pn['sign_off'] ) as $bhp_bun_s ) {
	bhp_bun_assert( false !== strpos( $bhp_bun_pb, esc_html( $bhp_bun_s ) ), 'rendered: item 489 copy on the page: ' . substr( $bhp_bun_s, 0, 44 ) );
}

/* ⛔ THE FORM ACTUALLY POSTS SOMEWHERE. An `action=""` form looks perfect and
      posts nowhere — the exact defect `inc/mailchimp-staging-stub.php` exists
      to make detectable on staging. */
bhp_bun_assert(
	(bool) preg_match( '/<form[^>]+id=["\']bhp-positivity-signup["\'][^>]*action=["\']([^"\']+)["\']/', $bhp_bun_pb, $bhp_bun_fa )
		&& '' !== trim( $bhp_bun_fa[1] ),
	'⛔ the signup form carries a NON-EMPTY action'
);

bhp_bun_assert(
	false !== strpos( $bhp_bun_pb, 'name="bhp_context" value="positivity_news"' ),
	'⛔ the form reports context `positivity_news` to the pipe'
);
bhp_bun_assert(
	(bool) preg_match( '/name="lead_magnet"\s+value=""/', $bhp_bun_pb ),
	'⛔⛔ the form posts an EMPTY lead_magnet'
);

/* ⛔ ONE FIELD. The deck specifies email only and the Mailchimp page carries one
      field; both surfaces ask for the same one thing. */
bhp_bun_assert(
	1 === preg_match_all( '/<input[^>]+type="email"/', $bhp_bun_pb ),
	'⛔ exactly ONE email input on the page'
);
bhp_bun_assert(
	! preg_match( '/name="first_name"/', $bhp_bun_pb ),
	'⛔ NO first-name field renders'
);
/* ⛔ WHITESPACE-TOLERANT, BECAUSE THE TEMPLATE INDENTS ITS BUTTON LABEL. The
      first run failed on `>Subscribe<` against a real
      `>\r\n    Subscribe  </button>`. The assertion is about the WORD a reader
      sees, so it trims rather than demanding the label hug its tags. */
bhp_bun_assert(
	(bool) preg_match( '/<button[^>]*acquisition-form__submit[^>]*>\s*([^<]*?)\s*<\/button>/s', $bhp_bun_pb, $bhp_bun_btn )
		&& esc_html( $bhp_bun_pn['submit'] ) === trim( $bhp_bun_btn[1] ),
	'⛔ the submit button reads exactly "Subscribe"'
);

/* ⛔ NO LEAD-MAGNET PROMISE REACHES A READER. */
foreach ( array( 'PDF', 'download', 'Download', 'Toolkit', 'Adventure Kit', 'printable' ) as $bhp_bun_promise ) {
	bhp_bun_assert(
		false === strpos( wp_strip_all_tags( $bhp_bun_pb ), $bhp_bun_promise ),
		"⛔⛔ rendered: the newsletter page promises no resource — `{$bhp_bun_promise}` is absent from the visible text"
	);
}

/* ⛔ AND NO COMPETING ASK. This page is a dedicated signup destination. */
foreach ( array( 'data-bhp-quiz-launcher', 'footer-capture', 'bhp_parent_popup' ) as $bhp_bun_needle ) {
	bhp_bun_assert( false === strpos( $bhp_bun_pb, $bhp_bun_needle ), "⛔ rendered: no competing ask on the newsletter page — {$bhp_bun_needle}" );
}

bhp_bun_assert(
	(bool) preg_match( '/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $bhp_bun_pb ),
	'⛔ rendered: /positivity-news/ serves a `noindex` robots meta'
);

echo "\n=== 11 · /author-visits/ IS UNCHANGED AND NEVER REDIRECTS ===\n";

/*
 * ⛔⛔ THE PRINTED-PAPER ASSERTION, MADE OVER HTTP RATHER THAN IN PHP. `redirection
 *     => 0` means the response is read as the server sent it, so a 301 shows up
 *     as a 301 instead of being silently followed to a 200.
 */
$bhp_bun_av = wp_remote_get(
	home_url( '/author-visits/' ),
	array(
		'timeout'     => 30,
		'sslverify'   => false,
		'redirection' => 0,
	)
);
$bhp_bun_av_code = (int) wp_remote_retrieve_response_code( $bhp_bun_av );
bhp_bun_assert(
	200 === $bhp_bun_av_code,
	'⛔⛔ /author-visits/ returns 200 and does NOT redirect, got ' . $bhp_bun_av_code . ' ' . wp_remote_retrieve_header( $bhp_bun_av, 'location' )
);
bhp_bun_assert(
	false !== strpos( (string) wp_remote_retrieve_body( $bhp_bun_av ), 'Upcoming Visits' ),
	'⛔⛔ /author-visits/ still renders the parent zone the QR codes point at'
);

/* ⭐ AND `/gallery/` STILL DOES fold in, on staging, where the detector is on. */
$bhp_bun_g = wp_remote_get(
	home_url( '/gallery/' ),
	array(
		'timeout'     => 30,
		'sslverify'   => false,
		'redirection' => 0,
	)
);
echo '  NOTE  /gallery/ => ' . (int) wp_remote_retrieve_response_code( $bhp_bun_g ) . ' ' . wp_remote_retrieve_header( $bhp_bun_g, 'location' ) . "\n";

echo "\n=== 12 · VERSION ===\n";

/*
 * ⭐ 1.19.334 (`CYCLE170-LD-MVP`): the pin moves with the release, because this
 *    suite now covers 1.19.334 behaviour — its scheduler-headline assertion was
 *    re-pointed to the typographic apostrophe in the same pass.
 *
 * ⭐ 1.19.335 (`CYCLE170-LD-WEEKPICKER`): the pin moves again, for the same
 *    reason and by the same rule — the scheduler-headline assertion was
 *    re-pointed from "Pick a day" to "Pick a week" for carrier item 534 in this
 *    pass. The 1.19.334 note above is kept so the movement stays legible.
 */
/*
 * ⭐ 1.19.336 (`CYCLE170-LD-CHAIN`): the pin moves a third time, same reason.
 *    ⛔ SUPERSEDED, quoted rather than deleted, and the 1.19.334 / 1.19.335
 *    notes above are kept so the whole movement stays legible:
 *
 *      bhp_bun_assert( (bool) preg_match( '/^Version:\s*1\.19\.335\s*$/m', $bhp_bun_css ), 'style.css declares 1.19.335' );
 */
/*
 * ⭐ MOVED AGAIN TO 1.19.337 BY `CYCLE170-LD-MICRO` (2026-08-30). SUPERSEDED
 *    ASSERTION, PRESERVED VERBATIM — the 1.19.336 pin was the CHAIN lane's:
 *
 *      bhp_bun_assert( (bool) preg_match( '/^Version:\s*1\.19\.336\s*$/m', $bhp_bun_css ), 'style.css declares 1.19.336' );
 */
/*
 * ⭐ PIN MOVED TO 1.19.339 BY `CYCLE170-LD-FINAL2` (2026-08-30). This lane
 *    bumped the theme, so this lane owns the pin it broke — the same discipline
 *    `CYCLE170-LD-CHAIN` §6a set. The four stale pins belonging to OTHER lanes
 *    (ship-prep, triple, school-readaloud, cycle169-funnel) are STILL deliberately
 *    left alone; moving them would silently adopt somebody else's stale suite.
 *
 *    SUPERSEDED ASSERTION, PRESERVED VERBATIM rather than corrected in place, so
 *    the movement is visible and the 1.19.337 attribution to `CYCLE170-LD-MICRO`
 *    is not rewritten:
 *
 *      bhp_bun_assert( (bool) preg_match( '/^Version:\s*1\.19\.337\s*$/m', $bhp_bun_css ), 'style.css declares 1.19.337' );
 */
bhp_bun_assert( (bool) preg_match( '/^Version:\s*1\.19\.339\s*$/m', $bhp_bun_css ), 'style.css declares 1.19.339' );

/* ⭐ THE MINIFIED STYLESHEET MUST HAVE BEEN REBUILT. It embeds the source md5,
      so a stale .min.css is detectable rather than silently shipped. */
$bhp_bun_min = (string) file_get_contents( $bhp_bun_tpl_dir . '/style.min.css' );
if ( preg_match( '/source-md5:\s*([0-9a-f]{32})/', $bhp_bun_min, $bhp_bun_mm ) ) {
	bhp_bun_assert(
		$bhp_bun_mm[1] === md5( $bhp_bun_css ),
		'⭐ style.min.css was REBUILT from the current style.css (embedded md5 matches)'
	);
} else {
	bhp_bun_assert( false, 'style.min.css carries a source-md5 stamp' );
}

$bhp_bun_p = isset( $GLOBALS['bhp_bun_pass'] ) ? $GLOBALS['bhp_bun_pass'] : 0;
$bhp_bun_f = isset( $GLOBALS['bhp_bun_fail'] ) ? $GLOBALS['bhp_bun_fail'] : 0;
echo "\n=== CYCLE170-LD-BUNDLE: PASS: {$bhp_bun_p}  FAIL: {$bhp_bun_f} ===\n";
echo( 0 === $bhp_bun_f ? "ALL PASS\n" : "SOME FAILED\n" );
