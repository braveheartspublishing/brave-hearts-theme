<?php
/**
 * CYCLE170-LD-SHIP-PREP — the approved copy load and the production ship
 * switch. Theme 1.19.332 (2026-08-30). STAGING ONLY.
 *
 * ⛔ WHAT THIS SUITE IS ACTUALLY FOR. Two of the things this lane does are
 *    genuinely dangerous, and the rest is ordinary cover.
 *
 *    · **The copy.** Four passages in Andrew's first person go onto a public
 *      page. The failure mode is not "a typo" — it is a machine having
 *      SILENTLY ALTERED words he approved, or having invented one. So the
 *      passages are asserted CHARACTER-EXACT against the approved read-back
 *      text, not merely "present and non-empty". Every one of the three
 *      permitted name substitutions is asserted individually, and the clause
 *      that must NOT have been substituted is asserted to be untouched.
 *
 *    · **The redirect switch.** `/author-visits/` is live on production and is
 *      reached from printed QR codes taped to classroom doors. If the option
 *      arming the 301 is wrong in either direction, printed paper points at a
 *      404. The three option states are asserted, and the option is RESTORED
 *      to its prior value afterwards.
 *
 * ⛔ THE COUNTERS ARE IN $GLOBALS, NOT `global $x`. `wp eval-file` includes this
 *    file INSIDE A FUNCTION, so a top-level variable is that function's LOCAL
 *    and `global $x` binds a different, empty slot — which prints
 *    "PASS: 0 FAIL: 0 / ALL PASS" over a visibly failing run. That happened for
 *    real on 2026-08-29 (finding F8 of the 1.19.319 candidate).
 *
 * ⚠ THIS SUITE WRITES ONE OPTION AND PUTS IT BACK. `bhp_school_readalouds_unify`
 *   is set and deleted to prove the switch works, then restored to exactly the
 *   value it had on entry (including "absent" as a distinct state from "empty").
 *   It touches NO post, page, product, coupon, price or WooCommerce setting,
 *   submits no form, sends no mail and calls no external service.
 *
 * Run: wp eval-file tests/test-cycle170-ship-prep.php --user=1
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
function bhp_sp_assert( $cond, $msg ) {
	if ( ! isset( $GLOBALS['bhp_sp_pass'] ) ) {
		$GLOBALS['bhp_sp_pass'] = 0;
		$GLOBALS['bhp_sp_fail'] = 0;
	}
	if ( $cond ) {
		++$GLOBALS['bhp_sp_pass'];
		echo "  PASS  {$msg}\n";
	} else {
		++$GLOBALS['bhp_sp_fail'];
		echo "  FAIL  {$msg}\n";
	}
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * THE APPROVED TEXT, RESTATED INDEPENDENTLY OF THE CODE UNDER TEST.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THESE LITERALS ARE TRANSCRIBED FROM THE READ-BACK SHEET, NOT FROM
 *    `bhp_readaloud_approved_passages()`. If the assertion imported the
 *    function's own output and compared it to itself it would pass on ANY
 *    text, including text a later pass quietly rewrote. That is the entire
 *    value of this section and it is why the duplication is deliberate.
 *
 * Source: Business OS\ANDREW-REVIEW\2026-08-30\readaloud-funnel-copy\
 *         READ-BACK-SHEET.md §3, "What prints" blocks, approved at carrier
 *         item 512, with the three item-514/515 name substitutions applied.
 */
$bhp_sp_expected = array(
	'founder-1' => 'I recently read to a room of thirty kids at Adams Elementary in Boise. I asked them how tall they were. They looked around at each other. Nobody knew. Ms. Ryan called out that she is five feet, so I told the kids they were probably three and a half to four feet tall. Then I asked how tall they thought Mount Everest was. Puzzled faces. Shy silence. I flipped to the next slide and said 29,032 feet. Their jaws dropped.',
	'founder-2' => 'We read all the way through chapter nine, and they were bummed we could not finish the book. They were reassured when I said the library has the books, so they can check them out. Then the definition of resilience, and the tools you need to be resilient. The breathing techniques, four seconds in and four seconds out. The mantras. Manas means mind. Tra means tool. Then we yelled I can do hard things, and Ms. Ryan had to shut the library door.',
	'founder-3' => 'Ms. Ryan pulled the globe from the back of the classroom, and I showed the kids where Idaho was and how far away Nepal is. They had great questions about Everest and the Khumbu Icefall.',
	'founder-4' => 'I spent over 80 days in Nepal, walked in from Jiri toward Base Camp, and summited Island Peak, just over 20,000 feet, without supplemental oxygen. On the climb I used the same tools I write into the books: the breathing, the I can do hard things mantra, one step at a time. At the read aloud, the kids asked if I was tired. I told them yes. And that your mind and good food can propel you anywhere you want to go.',
);

echo "\n=== 1 · THE COPY LOADER EXISTS AND IS WIRED ===\n";

bhp_sp_assert( function_exists( 'bhp_readaloud_approved_passages' ), 'bhp_readaloud_approved_passages() exists' );
bhp_sp_assert( function_exists( 'bhp_readaloud_land_approved_copy' ), 'bhp_readaloud_land_approved_copy() exists' );
bhp_sp_assert(
	has_filter( 'bhp_readaloud_funnel_copy_slots', 'bhp_readaloud_land_approved_copy' ) !== false,
	'⭐ the loader is attached to the bhp_readaloud_funnel_copy_slots filter'
);

/* ⛔ THE DECLARING FILE MUST NOT HAVE BEEN EDITED. gallery-page.php's own
      docblock prescribes the filter route precisely so its 165-assertion suite
      keeps asserting live code. A lane that "just added the copy there" would
      break that contract silently. */
$bhp_sp_gp = (string) file_get_contents( get_template_directory() . '/inc/gallery-page.php' );
bhp_sp_assert(
	false === strpos( $bhp_sp_gp, 'Adams Elementary in Boise' ) && false === strpos( $bhp_sp_gp, 'Ms. Ryan' ),
	'⛔ inc/gallery-page.php carries NO passage text — the copy landed by filter, not by editing the declaring file'
);

echo "\n=== 2 · THE FOUR PASSAGES ARE CHARACTER-EXACT ===\n";

$bhp_sp_actual = bhp_readaloud_approved_passages();
bhp_sp_assert( 4 === count( $bhp_sp_actual ), 'exactly four passages are declared (got ' . count( $bhp_sp_actual ) . ')' );

foreach ( $bhp_sp_expected as $bhp_sp_id => $bhp_sp_text ) {
	$bhp_sp_got = isset( $bhp_sp_actual[ $bhp_sp_id ] ) ? (string) $bhp_sp_actual[ $bhp_sp_id ] : '';
	bhp_sp_assert(
		$bhp_sp_got === $bhp_sp_text,
		"⭐⭐ {$bhp_sp_id} is CHARACTER-EXACT against the approved read-back text (" . strlen( $bhp_sp_got ) . ' bytes)'
	);
}

echo "\n=== 3 · THE THREE NAME SUBSTITUTIONS, ASSERTED ONE BY ONE ===\n";

/* ⭐ F-03 opened at carrier item 514: "She said it was ok to use her name". */
bhp_sp_assert(
	false !== strpos( $bhp_sp_actual['founder-1'], 'Ms. Ryan called out that she is five feet' ),
	'passage 1 · "Ms. Ryan called out that she is five feet"'
);
bhp_sp_assert(
	false !== strpos( $bhp_sp_actual['founder-2'], 'Ms. Ryan had to shut the library door' ),
	'passage 2 · "Ms. Ryan had to shut the library door"'
);
bhp_sp_assert(
	false !== strpos( $bhp_sp_actual['founder-3'], 'Ms. Ryan pulled the globe from the back of the classroom' ),
	'passage 3 · "Ms. Ryan pulled the globe from the back of the classroom"'
);

/* ⛔ THE CLAUSE THAT MUST **NOT** HAVE BEEN SUBSTITUTED. The read-back sheet
      removed her name here for a reading reason, not a consent reason, and
      item 514 restored it only to the door and the globe. A lane that
      "helpfully" made all four consistent would be rewriting approved copy. */
bhp_sp_assert(
	false !== strpos( $bhp_sp_actual['founder-2'], 'I said the library has the books, so they can check them out' ),
	'⛔ passage 2 · "the library has the books" is UNCHANGED — not over-substituted'
);

/* ⛔ NO PASSAGE STILL SAYS "the librarian". */
$bhp_sp_joined = implode( ' ', $bhp_sp_actual );
bhp_sp_assert( false === stripos( $bhp_sp_joined, 'the librarian' ), '⛔ the phrase "the librarian" appears in NO passage' );

/* ⛔ PASSAGE 4 NAMES NOBODY. */
bhp_sp_assert( false === strpos( $bhp_sp_actual['founder-4'], 'Ms. Ryan' ), 'passage 4 is verbatim and names nobody' );

echo "\n=== 4 · FORMAT RAILS ===\n";

/* ⛔ ZERO EM DASHES AND ZERO EN DASHES. Counted, not eyeballed. */
bhp_sp_assert( false === strpos( $bhp_sp_joined, "\xE2\x80\x94" ), '⛔ ZERO em dashes across all four passages' );
bhp_sp_assert( false === strpos( $bhp_sp_joined, "\xE2\x80\x93" ), '⛔ ZERO en dashes across all four passages' );

/* ⛔ NO PRICE. Item 481: read-alouds are free and no figure appears. */
bhp_sp_assert( false === strpos( $bhp_sp_joined, '$' ), '⛔ no dollar figure in any passage' );

/* ⛔ THE ISLAND PEAK RAILS. "just over 20,000 feet" in exactly that wording,
      never rounded up, and NO Everest summit claimed or implied. */
bhp_sp_assert(
	false !== strpos( $bhp_sp_actual['founder-4'], 'just over 20,000 feet' ),
	'⭐ the Island Peak elevation uses the mandated "just over 20,000 feet" wording'
);
bhp_sp_assert(
	false === strpos( $bhp_sp_actual['founder-4'], '21,000' ) && false === strpos( $bhp_sp_actual['founder-4'], 'over 20,000 feet high' ),
	'⛔ the elevation is not rounded up or restated'
);
bhp_sp_assert(
	! preg_match( '/summit\w*\s+(of\s+)?(Mount\s+)?Everest|Everest\s+summit/i', $bhp_sp_joined ),
	'⛔⛔ NO Everest summit is claimed or implied anywhere'
);

echo "\n=== 5 · THE SLOT MAP AFTER THE FILTER ===\n";

$bhp_sp_slots = bhp_readaloud_funnel_copy_slots();

foreach ( array( 'founder-1', 'founder-2', 'founder-3', 'founder-4' ) as $bhp_sp_id ) {
	bhp_sp_assert( isset( $bhp_sp_slots[ $bhp_sp_id ] ), "slot {$bhp_sp_id} exists in the filtered map" );
	bhp_sp_assert( empty( $bhp_sp_slots[ $bhp_sp_id ]['pending'] ), "slot {$bhp_sp_id} is NOT pending" );
	bhp_sp_assert( ! empty( $bhp_sp_slots[ $bhp_sp_id ]['copy'] ), "slot {$bhp_sp_id} carries copy" );
}

/* ⛔ `educators-1` STAYS PENDING AND THAT IS CORRECT, NOT AN OVERSIGHT. It is
      the teacher/librarian lead paragraph — machine-written copy from §2.4 of
      the read-back sheet, NOT one of the four passages item 512 approved. It
      still awaits the §4 strike pass. Asserting it stays honest is what stops a
      future lane from "finishing the set". */
bhp_sp_assert(
	isset( $bhp_sp_slots['educators-1'] ) && ! empty( $bhp_sp_slots['educators-1']['pending'] ),
	'⛔ educators-1 REMAINS pending — unapproved copy was not invented to fill it'
);

echo "\n=== 6 · THE MERGED TEMPLATE RENDERS ALL FOUR ===\n";

$bhp_sp_tpl = (string) file_get_contents( get_template_directory() . '/page-school-read-alouds.php' );
foreach ( array( 'founder-1', 'founder-2', 'founder-3', 'founder-4' ) as $bhp_sp_id ) {
	bhp_sp_assert(
		false !== strpos( $bhp_sp_tpl, "render_slot( '{$bhp_sp_id}' )" ),
		"the template calls render_slot('{$bhp_sp_id}')"
	);
}

echo "\n=== 7 · THE RENDERED PAGE ===\n";

$bhp_sp_page = get_page_by_path( bhp_school_readalouds_slug() );
if ( ! $bhp_sp_page instanceof WP_Post ) {
	bhp_sp_assert( false, 'the /school-read-alouds/ page exists (NOT FOUND — rendered checks skipped)' );
} else {
	bhp_sp_assert( true, 'the /school-read-alouds/ page exists (id ' . $bhp_sp_page->ID . ')' );

	$bhp_sp_res  = wp_remote_get( get_permalink( $bhp_sp_page ), array( 'timeout' => 30, 'sslverify' => false ) );
	$bhp_sp_html = is_wp_error( $bhp_sp_res ) ? '' : (string) wp_remote_retrieve_body( $bhp_sp_res );

	bhp_sp_assert( '' !== $bhp_sp_html, 'the page fetched (' . strlen( $bhp_sp_html ) . ' bytes)' );

	if ( '' !== $bhp_sp_html ) {
		/* ⭐⭐ THE HEADLINE ASSERTION OF THIS ENTIRE LANE. */
		bhp_sp_assert(
			false === strpos( $bhp_sp_html, '[PENDING READ-BACK' ),
			'⭐⭐ ZERO "[PENDING READ-BACK" placeholders on the rendered page'
		);
		bhp_sp_assert(
			false === strpos( $bhp_sp_html, 'bhp-copy-placeholder' ),
			'⭐⭐ ZERO placeholder chrome blocks on the rendered page'
		);

		/* ═══════════════════════════════════════════════════════════════════
		 * ⛔⛔ UPDATED AT 1.19.334 (`CYCLE170-LD-MVP`) FOR CARRIER ITEM 530.
		 *     THE SECTION IS AMENDED, NOT DELETED, AND THE REASON IS RECORDED.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * Until 1.19.333 this block asserted that ALL FOUR passages print on the
		 * teacher page. Item 530 — founder-sealed — drops passages 1, 2 and 3
		 * from `/school-read-alouds/` and keeps ONLY passage 4.
		 *
		 * ⛔ SO THE OLD ASSERTION WOULD NOW BE ASSERTING A REVERTED RULING. It is
		 *    re-pointed rather than removed, because the QUESTION it asks is
		 *    still the right one: does the page print exactly the approved copy
		 *    it is supposed to print, character-exact, and nothing else?
		 *
		 * ⭐ THE PROVENANCE CHECK IS NOT WEAKENED BY THE TRIM. Passages 1-3 are
		 *    still asserted character-exact against the independently
		 *    transcribed literals in §2 of this suite — that is what protects
		 *    them from silent rewriting while they are disabled. What changed is
		 *    only WHERE they may appear.
		 */
		foreach ( $bhp_sp_expected as $bhp_sp_id => $bhp_sp_text ) {
			if ( 'founder-4' === $bhp_sp_id ) {
				bhp_sp_assert(
					false !== strpos( $bhp_sp_html, esc_html( $bhp_sp_text ) ),
					'⭐⭐ passage founder-4 renders IN FULL on the live page (item 530 keeps this one)'
				);
				continue;
			}
			bhp_sp_assert(
				false === strpos( $bhp_sp_html, esc_html( $bhp_sp_text ) ),
				"⛔ item 530: passage {$bhp_sp_id} is ABSENT from the teacher page (trimmed, and still character-exact in source)"
			);
		}

		/* ⛔ THE NAMING RULE, RE-POINTED. "Ms. Ryan" appears only in passages 2
		      and 3, both of which item 530 trims from THIS page — so the correct
		      assertion here is now ZERO, not three. ⭐ Her name is NOT removed
		      from the corpus: `/gallery/` still renders those passages and §2
		      still asserts the naming character-exact. */
		bhp_sp_assert(
			0 === substr_count( $bhp_sp_html, 'Ms. Ryan' ),
			'⛔ item 530: "Ms. Ryan" does not appear on the teacher page (passages 2 and 3 are trimmed), got '
				. substr_count( $bhp_sp_html, 'Ms. Ryan' )
		);

		/* ⛔ THE FOUNDER SECTION CARRIES NO EM DASH. Scoped to the founder
		      block so the sitewide chrome cannot mask or cause a failure. */
		if ( preg_match( '/readaloud-funnel__founder.*?<\/section>/s', $bhp_sp_html, $bhp_sp_fm ) ) {
			bhp_sp_assert( false === strpos( $bhp_sp_fm[0], "\xE2\x80\x94" ), '⛔ the rendered founder section contains ZERO em dashes' );
		} else {
			bhp_sp_assert( false, 'the founder section was locatable in the rendered HTML' );
		}
	}
}

echo "\n=== 8 · THE PRODUCTION SHIP SWITCH ===\n";

/* ⚠ CAPTURE THE PRIOR STATE FIRST, and treat "absent" as distinct from "".
      Restoring an absent option to "" would leave the switch in a state it was
      never in, which is exactly the kind of quiet drift this lane exists to
      avoid. */
$bhp_sp_had     = ( false !== get_option( 'bhp_school_readalouds_unify', false ) );
$bhp_sp_prior   = get_option( 'bhp_school_readalouds_unify', '' );
$bhp_sp_staging = function_exists( 'bhp_staging_mail_guard_is_staging' ) ? bhp_staging_mail_guard_is_staging() : false;

bhp_sp_assert( function_exists( 'bhp_school_readalouds_unify_redirects' ), 'bhp_school_readalouds_unify_redirects() exists' );

/* ⛔ NOT HARDCODED ON. The brief is explicit: ship-time config, never a
      hardcoded true, so that deploying the artefact for an unrelated reason
      cannot repoint printed QR codes. */
$bhp_sp_src = (string) file_get_contents( get_template_directory() . '/inc/school-read-alouds.php' );
bhp_sp_assert(
	! preg_match( '/function\s+bhp_school_readalouds_unify_redirects\s*\([^)]*\)\s*\{\s*return\s+true\s*;/s', $bhp_sp_src ),
	'⛔⛔ the switch is NOT hardcoded to true'
);
bhp_sp_assert(
	false !== strpos( $bhp_sp_src, "get_option( 'bhp_school_readalouds_unify'" ),
	'⭐ the switch reads a ship-time OPTION'
);

/* State 1 — forced ON. */
update_option( 'bhp_school_readalouds_unify', '1' );
bhp_sp_assert( true === bhp_school_readalouds_unify_redirects(), '⭐⭐ option "1" turns the redirects ON (the production ship step)' );

/* State 2 — forced OFF, the kill switch, and it must beat the staging
   detector or it is not a kill switch at all. */
update_option( 'bhp_school_readalouds_unify', '0' );
bhp_sp_assert( false === bhp_school_readalouds_unify_redirects(), '⭐⭐ option "0" forces the redirects OFF even on staging (kill switch)' );

/* State 3 — absent, the shipped default. Staging keeps working; production
   stays off by construction. */
delete_option( 'bhp_school_readalouds_unify' );
bhp_sp_assert(
	bhp_school_readalouds_unify_redirects() === (bool) $bhp_sp_staging,
	'⭐ with the option ABSENT the staging detector still decides (staging=' . ( $bhp_sp_staging ? 'true' : 'false' ) . ') — today\'s behaviour is unchanged'
);

/* ⛔ RESTORE. */
if ( $bhp_sp_had ) {
	update_option( 'bhp_school_readalouds_unify', $bhp_sp_prior );
} else {
	delete_option( 'bhp_school_readalouds_unify' );
}
bhp_sp_assert(
	( false !== get_option( 'bhp_school_readalouds_unify', false ) ) === $bhp_sp_had,
	'⛔ the option was RESTORED to its exact prior state (' . ( $bhp_sp_had ? 'present' : 'absent' ) . ')'
);

/* ⛔ THE DESTINATION GUARD IS STILL THERE. The option is belt and braces; the
      real protection is that no redirect fires into a page that does not
      exist. If a future edit removed this, arming the option on production
      before creating the page would 301 live pages into a 404. */
bhp_sp_assert(
	(bool) preg_match( '/get_page_by_path\(\s*bhp_school_readalouds_slug\(\)\s*\)/', $bhp_sp_src )
		&& false !== strpos( $bhp_sp_src, "'publish' !== \$target->post_status" ),
	'⛔⛔ the redirect STILL refuses to fire unless the destination exists AND is published'
);

/* ⛔ THE MERGED SLUG LIST IS STILL EXACTLY TWO LITERALS. A pattern here would
      swallow /read-aloud/ and /read-alouds/, which are different live pages. */
$bhp_sp_merged = bhp_school_readalouds_merged_slugs();
bhp_sp_assert(
	array( 'gallery', 'author-visits' ) === array_values( $bhp_sp_merged ),
	'⛔ exactly two merged slugs, still literals: ' . implode( ', ', $bhp_sp_merged )
);

echo "\n=== 9 · VERSION ===\n";

$bhp_sp_css = (string) file_get_contents( get_template_directory() . '/style.css' );
bhp_sp_assert( (bool) preg_match( '/^Version:\s*1\.19\.332\s*$/m', $bhp_sp_css ), 'style.css declares 1.19.332' );

/* ⭐ THE MINIFIED STYLESHEET MUST HAVE BEEN REBUILT. It embeds the source md5,
      so a stale .min.css is detectable rather than silently shipped. */
$bhp_sp_min = (string) file_get_contents( get_template_directory() . '/style.min.css' );
if ( preg_match( '/source-md5:\s*([0-9a-f]{32})/', $bhp_sp_min, $bhp_sp_mm ) ) {
	bhp_sp_assert(
		$bhp_sp_mm[1] === md5( $bhp_sp_css ),
		'⭐ style.min.css was REBUILT from the current style.css (embedded md5 matches)'
	);
} else {
	bhp_sp_assert( false, 'style.min.css carries a source-md5 stamp' );
}

$bhp_sp_p = isset( $GLOBALS['bhp_sp_pass'] ) ? $GLOBALS['bhp_sp_pass'] : 0;
$bhp_sp_f = isset( $GLOBALS['bhp_sp_fail'] ) ? $GLOBALS['bhp_sp_fail'] : 0;
echo "\n=== CYCLE170-LD-SHIP-PREP: PASS: {$bhp_sp_p}  FAIL: {$bhp_sp_f} ===\n";
echo( 0 === $bhp_sp_f ? "ALL PASS\n" : "SOME FAILED\n" );
