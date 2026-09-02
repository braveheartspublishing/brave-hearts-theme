<?php
/**
 * CYCLE169-LD-READALOUD-TRUST-GALLERY — the past-read-alouds trust column, the
 * photo gallery and the October booking CTA. Theme 1.19.319 (2026-08-29).
 *
 * Andrew Signore, verbatim, carrier item 432 (first-hand to the Chief of Staff,
 * commissioning this agent by name; the square brackets below are an editorial
 * substitution of an internal call name, per Standing Rules §14.5 — nothing else
 * in his sentence is altered): *"Also I would like [lead-developer] to work on
 * putting a column for past read alouds on the read-aloud site- I want more
 * trust on that and lets put a picture gallery of the read alouds on that page
 * too."*
 *
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. The risky part of this build is NOT the
 *    layout. It is that a "trust" section is the single most attractive place in
 *    the codebase for a fabricated reaction to appear, and a gallery of real
 *    children is the single most damaging place for a permission or naming
 *    mistake. Sections 5 and 6 below are the point of this file; the rest is
 *    ordinary regression cover.
 *
 * ⛔ IT MUST ALSO PROVE THE UPCOMING LIST DID NOT MOVE. `bhp_author_visits_
 *    build_rows()` was NOT edited by this workstream, and section 2 asserts the
 *    past/upcoming partition is exact rather than assuming it.
 *
 * Run: wp eval-file tests/test-cycle169-visits-trust-gallery.php --user=1
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

$bhp_pass = 0;
$bhp_fail = 0;

/**
 * Assert.
 *
 * ⛔ THE COUNTERS ARE IN $GLOBALS, NOT `global $x`, AND THAT IS NOT A STYLE
 *    CHOICE. `wp eval-file` includes this file INSIDE A FUNCTION, so the
 *    top-level `$bhp_pass` above is a LOCAL variable of that function, not a
 *    global. A `global $bhp_pass` here therefore binds a DIFFERENT, empty
 *    variable, every increment lands on it, and the summary prints
 *    "PASS: 0 FAIL: 0 / ALL PASS" while assertions are visibly failing above
 *    it. That is the worst possible failure mode for a test harness — a green
 *    result over a red run — and it is exactly what this file did on its first
 *    execution. Writing through $GLOBALS makes the counter the same slot the
 *    summary reads, wherever the file is included from.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Message.
 */
function bhp_tg_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_tg_pass'] ) ) {
		$GLOBALS['bhp_tg_pass'] = 0;
		$GLOBALS['bhp_tg_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_tg_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_tg_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/**
 * Section header.
 *
 * @param string $t Title.
 */
function bhp_tg_section( $t ) {
	echo "\n== {$t} ==\n";
}

echo "CYCLE169 — visits trust column, gallery, October CTA\n";
echo str_repeat( '=', 72 ) . "\n";

/* ------------------------------------------------------------------------ */
bhp_tg_section( '1. The functions exist and are callable' );

foreach ( array(
	'bhp_author_visits_notes',
	'bhp_author_visits_photo_url',
	'bhp_author_visits_build_past_rows',
	'bhp_author_visits_format_past_date',
	'bhp_author_visits_past_rows',
	'bhp_author_visits_gallery_photos',
) as $fn ) {
	bhp_tg_assert( function_exists( $fn ), "{$fn}() is defined" );
}

/* ------------------------------------------------------------------------ */
bhp_tg_section( '2. PAST and UPCOMING are an exact partition — no visit lost, none duplicated' );

$fixture = array(
	array( 'slug' => 'alpha-2026-01-01', 'school' => 'Alpha School', 'date' => '2026-01-01', 'cutoff' => '2025-12-29', 'time' => '9:00 AM' ),
	array( 'slug' => 'bravo-2026-06-15', 'school' => 'Bravo School', 'date' => '2026-06-15', 'cutoff' => '2026-06-12', 'time' => '' ),
	array( 'slug' => 'delta-2026-12-31', 'school' => 'Delta School', 'date' => '2026-12-31', 'cutoff' => '2026-12-28', 'time' => '1:15 PM' ),
);

/**
 * Partition check for one "today".
 *
 * @param array  $records Records.
 * @param string $today   Date.
 * @return array{past:array,up:array}
 */
function bhp_tg_split( $records, $today ) {
	return array(
		'past' => bhp_author_visits_build_past_rows( $records, $today, array() ),
		'up'   => bhp_author_visits_build_rows( $records, $today ),
	);
}

foreach ( array( '2025-12-31', '2026-01-01', '2026-01-02', '2026-06-15', '2026-06-16', '2027-01-01' ) as $today ) {
	$s     = bhp_tg_split( $fixture, $today );
	$total = count( $s['past'] ) + count( $s['up'] );
	bhp_tg_assert( 3 === $total, "on {$today} every visit lands in exactly one list (past " . count( $s['past'] ) . ' + upcoming ' . count( $s['up'] ) . ' = 3)' );

	$past_slugs = wp_list_pluck( $s['past'], 'slug' );
	$up_slugs   = wp_list_pluck( $s['up'], 'slug' );
	bhp_tg_assert( array() === array_intersect( $past_slugs, $up_slugs ), "on {$today} no visit appears in BOTH lists" );
}

/* THE VISIT-DAY BOUNDARY. A visit is upcoming on the morning it happens — the
   school is still on the page while Andrew is standing in the classroom — and
   becomes past the next day. This is the exact boundary item 432 depends on:
   Adams (2026-08-28) had to become the first PAST row on 2026-08-29. */
$s = bhp_tg_split( $fixture, '2026-06-15' );
bhp_tg_assert( in_array( 'bravo-2026-06-15', wp_list_pluck( $s['up'], 'slug' ), true ), '⭐ ON THE DAY OF THE VISIT the row is UPCOMING, not history' );
bhp_tg_assert( ! in_array( 'bravo-2026-06-15', wp_list_pluck( $s['past'], 'slug' ), true ), '   ...and it is not also in the past list' );

$s = bhp_tg_split( $fixture, '2026-06-16' );
bhp_tg_assert( in_array( 'bravo-2026-06-15', wp_list_pluck( $s['past'], 'slug' ), true ), '⭐ THE DAY AFTER THE VISIT the row is PAST' );
bhp_tg_assert( ! in_array( 'bravo-2026-06-15', wp_list_pluck( $s['up'], 'slug' ), true ), '   ...and it has left the upcoming list' );

/* ------------------------------------------------------------------------ */
bhp_tg_section( '3. Ordering, formatting and degradation' );

$past = bhp_author_visits_build_past_rows( $fixture, '2027-01-01', array() );
bhp_tg_assert( 3 === count( $past ), 'all three are past once every date has gone by' );
bhp_tg_assert( 'delta-2026-12-31' === $past[0]['slug'], 'MOST RECENT FIRST: the newest past visit leads' );
bhp_tg_assert( 'alpha-2026-01-01' === $past[2]['slug'], '   ...and the oldest is last' );

bhp_tg_assert( '' === implode( '', wp_list_pluck( bhp_author_visits_build_past_rows( $fixture, '', array() ), 'slug' ) ), '⛔ WITH NO "today" THERE IS NO PAST — an empty date does not turn every visit into history' );
bhp_tg_assert( array() === bhp_author_visits_build_past_rows( 'not-an-array', '2027-01-01' ), 'a non-array records value degrades to an empty list, not a fatal' );
bhp_tg_assert( array() === bhp_author_visits_build_past_rows( array( 'nope', 42, null ), '2027-01-01' ), 'junk rows are skipped' );

/* A past row must survive a MISSING cutoff — the ordering window is meaningless
   for a visit that already happened, and requiring it would silently lose history. */
$no_cutoff = bhp_author_visits_build_past_rows( array( array( 'slug' => 'echo-2026-02-02', 'school' => 'Echo School', 'date' => '2026-02-02' ) ), '2027-01-01', array() );
bhp_tg_assert( 1 === count( $no_cutoff ), '⭐ A PAST ROW WITH NO cutoff STILL RENDERS — history is not gated on an ordering deadline' );

bhp_tg_assert( false !== strpos( bhp_author_visits_format_past_date( '2026-08-28' ), '2026' ), 'THE YEAR IS SHOWN on a past date (a trust record outlives its year)' );
bhp_tg_assert( false === strpos( bhp_author_visits_format_past_date( '2026-08-28' ), 'Friday' ), '   ...and the weekday is dropped, unlike an upcoming date' );

/* ------------------------------------------------------------------------ */
bhp_tg_section( '4. The notes option — sanitisation and path safety' );

/*
 * ⛔⛔ THIS SECTION WRITES TO A REAL OPTION, SO IT SNAPSHOTS IT FIRST AND PUTS
 *     IT BACK AT THE END. THAT IS NOT A NICETY — IT IS A REPAIR.
 *
 *     The first version of this file ended the section with a bare
 *     `delete_option( 'bhp_school_visit_notes' )` to prove the no-notes
 *     degradation path. It did prove it. It also DELETED THE LIVE NOTES OPTION
 *     ON STAGING, and the next page load rendered the past column with no note
 *     and the gallery with no photographs at all. The build looked broken; the
 *     build was fine; the TEST had eaten the data. It was caught only because
 *     the browser check immediately afterwards counted zero gallery items.
 *
 *     A test that destroys the state it is run against is worse than no test:
 *     run on production it would have silently blanked the trust column. The
 *     `$restore` value below is captured before the first write and restored
 *     unconditionally at the end of the section, including the case where the
 *     option did not exist to begin with.
 */
$bhp_tg_had_notes = ( false !== get_option( 'bhp_school_visit_notes', false ) );
$bhp_tg_restore   = get_option( 'bhp_school_visit_notes', array() );

$dirty = array(
	'good-2026-01-01' => array(
		'note'      => '  A factual sentence.  ',
		'recap_url' => '/blog/example/',
		'photos'    => array(
			array( 'file' => 'ok.jpg', 'alt' => 'A description', 'w' => 100, 'h' => 50 ),
			array( 'file' => '../../../wp-config.php', 'alt' => 'traversal attempt' ),
			array( 'file' => 'evil.php', 'alt' => 'wrong extension' ),
			array( 'file' => 'noalt.jpg', 'alt' => '' ),
			array( 'file' => '/etc/passwd.jpg', 'alt' => 'absolute path' ),
		),
	),
	''                => array( 'note' => 'empty slug must vanish' ),
	'junk-2026-01-01' => 'not an array',
);

update_option( 'bhp_school_visit_notes', $dirty );
$clean = bhp_author_visits_notes();

bhp_tg_assert( isset( $clean['good-2026-01-01'] ), 'a well-formed entry survives' );
bhp_tg_assert( ! isset( $clean[''] ), 'an empty slug is dropped' );
bhp_tg_assert( ! isset( $clean['junk-2026-01-01'] ), 'a non-array entry is dropped' );
bhp_tg_assert( 'A factual sentence.' === $clean['good-2026-01-01']['note'], 'the note is trimmed' );

$files = wp_list_pluck( $clean['good-2026-01-01']['photos'], 'file' );
bhp_tg_assert( in_array( 'ok.jpg', $files, true ), 'a valid photo survives' );
bhp_tg_assert( ! in_array( 'evil.php', $files, true ), '⛔ A NON-IMAGE EXTENSION IS REJECTED' );
bhp_tg_assert( ! in_array( 'noalt.jpg', $files, true ), '⛔ A PHOTO WITH NO ALT TEXT IS DROPPED, not rendered with alt=""' );
bhp_tg_assert( ! in_array( '../../../wp-config.php', $files, true ), '⛔⛔ PATH TRAVERSAL IS DEFEATED: the raw value never survives' );
bhp_tg_assert( ! in_array( '/etc/passwd.jpg', $files, true ), '⛔⛔ AN ABSOLUTE PATH IS REDUCED TO ITS BASENAME, never used as a path' );
foreach ( $files as $f ) {
	bhp_tg_assert( false === strpos( $f, '/' ) && false === strpos( $f, '\\' ) && false === strpos( $f, '..' ), "every surviving filename is a bare basename ({$f})" );
}

$url = bhp_author_visits_photo_url( '../../../wp-config.php' );
bhp_tg_assert( false === strpos( $url, '..' ), '⛔⛔ bhp_author_visits_photo_url() CANNOT BE MADE TO ESCAPE its directory' );
bhp_tg_assert( false !== strpos( bhp_author_visits_photo_url( 'ok.jpg' ), 'assets/img/read-alouds/ok.jpg' ), 'a valid photo resolves under assets/img/read-alouds/' );
bhp_tg_assert( '' === bhp_author_visits_photo_url( '' ), 'an empty filename yields no URL' );

/* The no-notes degradation path, proved WITHOUT destroying anything: the
   builder is pure, so an empty notes map is passed as an argument rather than
   by deleting the option out from under the live site. */
$bare = bhp_author_visits_build_past_rows( $fixture, '2027-01-01', array() );
bhp_tg_assert( 3 === count( $bare ) && '' === $bare[0]['note'] && array() === $bare[0]['photos'], '⭐ WITH NO NOTES AT ALL past rows still render with school and date, just without notes or photos' );

/* And the reader's own empty-input path, via a value that is not an array. */
update_option( 'bhp_school_visit_notes', 'not-an-array' );
bhp_tg_assert( array() === bhp_author_visits_notes(), '   ...and a corrupt notes option yields an empty map rather than a fatal' );

/* ⛔ RESTORE. Unconditional, and it handles "the option did not exist" too. */
if ( $bhp_tg_had_notes ) {
	update_option( 'bhp_school_visit_notes', $bhp_tg_restore );
} else {
	delete_option( 'bhp_school_visit_notes' );
}
$bhp_tg_after = get_option( 'bhp_school_visit_notes', false );
bhp_tg_assert(
	( $bhp_tg_had_notes && $bhp_tg_after === $bhp_tg_restore ) || ( ! $bhp_tg_had_notes && false === $bhp_tg_after ),
	'⛔⛔ THE LIVE NOTES OPTION IS RESTORED EXACTLY AS IT WAS FOUND — this suite leaves no trace on the site it ran against'
);

/* ------------------------------------------------------------------------ */
bhp_tg_section( '5. ⛔ THE NEVER-INVENT GATE — the reason this suite exists' );

$tpl_path = get_theme_file_path( 'page-author-visits.php' );
$inc_path = get_theme_file_path( 'inc/author-visits.php' );
$tpl_src  = file_exists( $tpl_path ) ? file_get_contents( $tpl_path ) : '';
$inc_src  = file_exists( $inc_path ) ? file_get_contents( $inc_path ) : '';
$both_src = $tpl_src . "\n" . $inc_src;

bhp_tg_assert( '' !== $tpl_src && '' !== $inc_src, 'both source files are readable' );

/* The copy blob: only the strings this template actually shows a human. */
$copy = '';
if ( preg_match_all( '/(?:esc_html_e|esc_html__|__)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $tpl_src, $m ) ) {
	$copy = strtolower( implode( ' | ', $m[1] ) );
}
bhp_tg_assert( '' !== $copy, 'translatable copy was extracted from the template' );

bhp_tg_assert( ! preg_match( '/\b(we|us|our|ours)\b/', $copy ), '⭐ FOUNDER VOICE (Standing Rules §9.1): no company "we", "us" or "our" in any visible string' );
bhp_tg_assert( ! preg_match( '/\b(loved|adored|thrilled|delighted|amazing|incredible|best|favou?rite|proven|award|bestsell|#1)\b/', $copy ), '⛔ NO SUPERLATIVE OR REACTION WORD in the trust or gallery copy' );
bhp_tg_assert( ! preg_match( '/\b(parents say|teachers say|kids say|students say|reviews?|testimonial|rating)\b/', $copy ), '⛔⛔ NO REVIEW, TESTIMONIAL OR REACTION CLAIM — Standing Rules §3, absolute' );
bhp_tg_assert( ! preg_match( '/\b\d+\s*(kids|children|students|classrooms|schools)\b/', $copy ), '⛔ NO HARDCODED COUNT of kids, classrooms or schools in template copy — every number is founder-attested and lives in the notes option' );
bhp_tg_assert( ! preg_match( '/\$\s?\d/', $copy ), 'NO price appears in the new copy' );
bhp_tg_assert( ! preg_match( '/\b5\s*[-–—]\s*9\b/', $copy ), 'the reading age is never stated as 5-9' );
bhp_tg_assert( ! preg_match( '/\b(lexile|reading level|grade level)\b/', $copy ), 'NO reading-level claim' );

/* The grace window, still never advertised — the new sections must not leak it. */
bhp_tg_assert( ! preg_match( '/\b(grace|extra day|one more day|sneak|late order|really closes|actually closes|secretly)\b/', $copy ), '⛔⛔ THE GRACE WINDOW IS STILL NEVER ADVERTISED by any new string' );

/* The no-hardcoded-visit-data rule, extended to the new code. */
bhp_tg_assert( ! preg_match( '/adams-20\d\d|dallas-harris|liberty-20\d\d|amity-20\d\d/i', $both_src ), '⛔ NO real visit slug appears in either source file' );
bhp_tg_assert( ! preg_match( '/\bElementary\b/', $both_src ), '⛔ NO school name is hardcoded — the trust column reads the registry' );
bhp_tg_assert( ! preg_match( '/[\'"]school[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/', $both_src ), '⛔ NO literal visit row exists in either source file' );

/* The librarian. Item 368 names her; this page never may. */
bhp_tg_assert( ! preg_match( '/\bkristi\b/i', $both_src ), '⛔⛔ THE LIBRARIAN IS NEVER NAMED anywhere in the source' );
/* ⛔ NARROWED DELIBERATELY. The first version of this assertion was
   `/\blibrarian\'?s? (?:name|is)\b/i`, which fired on the ordinary English
   sentence "a librarian is a private individual" in this build's own comments.
   A test that fails on prose ABOUT the rule, rather than on a breach of it,
   trains the next reader to ignore it. What actually matters is that no
   librarian is introduced BY NAME, so that is what is now matched: the word
   followed by a capitalised given name. */
bhp_tg_assert( ! preg_match( '/\blibrarian\b[^.\n]{0,20}\b(?:named|called)\b/i', $both_src ), '   ...and no string introduces a librarian by name' );
bhp_tg_assert( ! preg_match( '/\b(?:librarian|teacher)\b,?\s+[A-Z][a-z]{2,}\b/', $both_src ), '   ...and no capitalised personal name follows "librarian" or "teacher"' );

/* ------------------------------------------------------------------------ */
bhp_tg_section( '6. ⛔ THE GALLERY SAFETY GATE — real children are in these frames' );

bhp_tg_assert( 1 === preg_match( '/loading="lazy"/', $tpl_src ), 'every gallery image is lazy-loaded' );
bhp_tg_assert( 1 === preg_match( '/decoding="async"/', $tpl_src ), 'and decoded asynchronously' );
bhp_tg_assert( 1 === preg_match( '/width="<\?php echo esc_attr/', $tpl_src ), '⭐ EXPLICIT width IS EMITTED so the CTA below does not jump as photos load' );
bhp_tg_assert( 1 === preg_match( '/height="<\?php echo esc_attr/', $tpl_src ), '   ...and explicit height' );
bhp_tg_assert( 1 === preg_match( '/alt="<\?php echo esc_attr\(\s*\$bhp_photo\[.alt.\]/', $tpl_src ), '⭐ ALT TEXT COMES FROM THE DATA, never from a literal in the template' );
bhp_tg_assert( ! preg_match( '/alt=""/', $tpl_src ), '⛔ NO EMPTY alt ATTRIBUTE: a photograph of real children is not decorative' );

/* ⛔ THE <img>-HAS-alt CHECK, DONE HONESTLY.
   The obvious regex `/<img(?![^>]*\balt=)/` DOES NOT WORK on this template and
   silently reports a false failure. The <img> tag here spans several lines and
   contains an interpolated `<?php if ( ... ) : ?>` block for the optional
   width/height pair — and `?>` contains a `>`, so `[^>]*` stops inside the PHP
   long before it reaches the alt attribute. The tag is well-formed; the regex
   simply cannot see it.
   So: count the tags, and require every one of them to carry an alt. */
$img_count = preg_match_all( '/<img\b/', $tpl_src );
$alt_count = preg_match_all( '/\balt="/', $tpl_src );
bhp_tg_assert( $img_count > 0, 'the template renders at least one <img>' );
bhp_tg_assert( $img_count === $alt_count, "EVERY <img> CARRIES AN alt ATTRIBUTE ({$img_count} tags, {$alt_count} alt attributes)" );

/* Every shipped photograph must actually exist, or the page renders broken
   images of children, which is worse than rendering none. */
$dir = get_theme_file_path( 'assets/img/read-alouds' );
bhp_tg_assert( is_dir( $dir ), 'assets/img/read-alouds/ exists in the deployed theme' );
foreach ( array( 'adams-elementary-read-aloud-group.jpg', 'adams-elementary-read-aloud-questions.jpg', 'adams-elementary-signed-books.jpg' ) as $f ) {
	bhp_tg_assert( file_exists( $dir . '/' . $f ) && filesize( $dir . '/' . $f ) > 1024, "the shipped photograph {$f} is present and non-empty" );
}

/* ------------------------------------------------------------------------ */
bhp_tg_section( '7. The October booking CTA (items 412 / 429)' );

bhp_tg_assert( 1 === preg_match( '/mailto:/', $tpl_src ), 'the CTA offers a mailto route' );
bhp_tg_assert( 1 === preg_match( '/october/i', $copy ), '⭐ THE COPY NAMES OCTOBER — item 412, "I can take read alouds in boise starting in october"' );
bhp_tg_assert( ! preg_match( '/\b(this season|cannot take|can\'t take|calendar is full|fully booked|not taking)\b/', $copy ), '⛔ THE SUPERSEDED CALENDAR-FULL FRAMING (item 309) APPEARS NOWHERE — item 412 amended it' );
bhp_tg_assert( 1 === preg_match( '/andrew@braveheartspublishing\.com/i', $tpl_src ), 'the contact address is the founder address already public on this site' );

/* ⛔ THE COMMERCIAL-PROMISE CHECK IS SCOPED TO THE BOOKING BLOCK, and narrowing
   it was a correction, not a weakening. Run against the WHOLE template it fired
   on "choose the free author hand-delivery option" in the How It Works steps —
   copy that predates this workstream, that is approved, and where "free"
   describes a real delivery option rather than promising a free school visit.
   The risk this assertion exists for is the BOOKING CTA implying a price or a
   free visit, so the booking block is what it now reads. */
$booking_src = '';
if ( preg_match( '/author-visits-booking(.*?)<\?php get_footer/s', $tpl_src, $bm ) ) {
	$booking_src = $bm[1];
}
$booking_copy = '';
if ( '' !== $booking_src && preg_match_all( '/(?:esc_html_e|esc_html__|__)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $booking_src, $bc ) ) {
	$booking_copy = strtolower( implode( ' | ', $bc[1] ) );
}
bhp_tg_assert( '' !== $booking_copy, 'the booking block copy was isolated for its own checks' );
bhp_tg_assert( ! preg_match( '/\b(free|no charge|no cost|fee|rate|price|\$)\b/', $booking_copy ), '⛔ THE BOOKING CTA MAKES NO COMMERCIAL PROMISE — it names no fee, no rate, and does not claim the visit is free' );
bhp_tg_assert( ! preg_match( '/\b(guarantee|will come|promise|any school|every school)\b/', $booking_copy ), '⛔ AND IT PROMISES NO ACCEPTANCE — asking is not booking' );
bhp_tg_assert( ! preg_match( '/\b(we|us|our)\b/', $booking_copy ), 'the booking block is in the founder I-voice' );

/* ------------------------------------------------------------------------ */
bhp_tg_section( '8. The upcoming list is untouched — regression cover' );

bhp_tg_assert( 1 === preg_match( '/<span[^>]*author-visits-card__btn--closed[^>]*>/', $tpl_src ), 'the closed button is STILL a <span>' );
bhp_tg_assert( ! preg_match( '/<a[^>]*author-visits-card__btn--closed/', $tpl_src ), 'the closed button is STILL not an <a>' );
bhp_tg_assert( 1 === preg_match( '/author-visits-card__btn--closed[^>]*aria-disabled="true"/', $tpl_src ), 'the closed button is STILL aria-disabled' );
/* ⛔ TAG-SCOPED, matching `tests/test-author-visits-page.php` exactly. A bare
   `/\bonclick=/i` fires on this template's own 1.19.239 comment explaining why
   the closed control is NOT `<a onclick="return false">`. What must never exist
   is the ATTRIBUTE ON AN ELEMENT, which is what the original suite asserts and
   what is asserted here. */
bhp_tg_assert( ! preg_match( '/<[a-z][^>]*\sonclick=/i', $tpl_src ), 'STILL no onclick attribute on any element' );
bhp_tg_assert( 1 === preg_match( '/Template Name:\s*Author Visits/', $tpl_src ), 'the Template Name is unchanged so the page keeps its template' );

/* The past column must never offer an ordering affordance. */
bhp_tg_assert( ! preg_match( '/author-visits-past[\s\S]{0,900}?bhp_visit=/', $tpl_src ), '⛔ NO ?bhp_visit= ENTITLEMENT LINK inside the past column' );
bhp_tg_assert( ! preg_match( '/author-visits-past__(?:item|school|when|note)[\s\S]{0,400}?btn-primary/', $tpl_src ), '⛔ NO ordering BUTTON inside a past row — a past visit cannot be ordered for' );

/* ------------------------------------------------------------------------ */
bhp_tg_section( '9. CSS is present in both the source and the built stylesheet' );

$css     = file_get_contents( get_theme_file_path( 'style.css' ) );
$min     = file_exists( get_theme_file_path( 'style.min.css' ) ) ? file_get_contents( get_theme_file_path( 'style.min.css' ) ) : '';
$classes = array(
	'.author-visits-columns',
	'.author-visits-past',
	'.author-visits-past__item',
	'.author-visits-gallery',
	'.author-visits-gallery__img',
	'.author-visits-booking__title',
);
foreach ( $classes as $c ) {
	bhp_tg_assert( false !== strpos( $css, $c ), "style.css carries {$c}" );
	bhp_tg_assert( false !== strpos( $min, $c ), "style.min.css carries {$c} (the built artefact is current)" );
}
bhp_tg_assert( false !== strpos( $css, 'grid-template-columns: minmax(0, 3fr) minmax(0, 2fr)' ), 'the two-column split exists' );
bhp_tg_assert( false !== strpos( $css, '@media (min-width: 64rem)' ), '⭐ THE SPLIT IS BEHIND A 64rem QUERY — the phone stays single-column' );
bhp_tg_assert( false !== strpos( $css, 'height: auto' ), 'gallery images keep their intrinsic aspect ratio' );

/* Every custom property the new block uses must actually be declared, or the
   rule silently resolves to nothing. This is how `--space-5` was caught. */
if ( preg_match( '/AUTHOR VISITS — THE TRUST COLUMN(.*?)\n\/\* =/s', $css, $blk ) ) {
	preg_match_all( '/var\((--[a-z0-9-]+)\)/i', $blk[1], $vars );
	foreach ( array_unique( $vars[1] ) as $v ) {
		bhp_tg_assert( false !== strpos( $css, $v . ':' ), "the custom property {$v} is actually declared somewhere in style.css" );
	}
}

/* ------------------------------------------------------------------------ */
$bhp_pass = isset( $GLOBALS['bhp_tg_pass'] ) ? (int) $GLOBALS['bhp_tg_pass'] : 0;
$bhp_fail = isset( $GLOBALS['bhp_tg_fail'] ) ? (int) $GLOBALS['bhp_tg_fail'] : 0;

echo "\n" . str_repeat( '=', 72 ) . "\n";
echo "PASS: {$bhp_pass}   FAIL: {$bhp_fail}\n";
/* A run that asserted nothing at all is a FAILURE, not a pass. */
echo ( 0 === $bhp_fail && $bhp_pass > 0 ) ? "ALL PASS\n" : "FAILURES PRESENT\n";
