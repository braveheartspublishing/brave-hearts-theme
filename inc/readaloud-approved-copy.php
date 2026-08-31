<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE APPROVED FOUNDER-VOICE COPY. Theme 1.19.332 (2026-08-30,
 * `CYCLE170-LD-SHIP-PREP`). THIS FILE ENDS THE PLACEHOLDER ERA.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `inc/gallery-page.php` declared four copy slots that rendered as loud
 * `[PENDING READ-BACK — do not publish]` blocks, and its own docblock named the
 * one permitted way to fill them:
 *
 *     "approved copy lands through add_filter('bhp_readaloud_funnel_copy_slots', …)
 *      with no edit to this file"
 *
 * ⛔ SO `inc/gallery-page.php` IS NOT EDITED BY THIS LANE. This file is that
 *    filter and nothing more. The placeholder mechanism, the renderer and the
 *    slot declarations are all untouched; four entries simply stop being
 *    `pending`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ PROVENANCE — every character below traces to a founder-approved source
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The four passages are copied CHARACTER-EXACT from the "What prints" blocks of
 * `Business OS\ANDREW-REVIEW\2026-08-30\readaloud-funnel-copy\READ-BACK-SHEET.md`
 * §3, which is the read-back Andrew approved at **carrier item 512**, verbatim:
 * *"all good, you can include Ms. Ryans name"*.
 *
 * ⛔ NOT ONE WORD IS WRITTEN BY THIS FILE. No sentence was added, smoothed,
 *    joined, shortened or "improved". If a passage reads oddly, that is the
 *    approved text and it stays. The failure this whole slot mechanism exists to
 *    prevent is a machine writing prose in Andrew's first person.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE ONE AMENDMENT — the F-03 naming gate, and it is OPEN
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The read-back sheet prints "the librarian" everywhere, under hard flag
 * **F-03**, which required the librarian's own consent and not merely Andrew's.
 * That gate is now OPEN, and the chain is recorded here so no future reader has
 * to re-derive it:
 *
 *   item 512  the passages are approved; the naming amendment is HELD pending
 *             F-03, because F-03 needs HER explicit OK, not only the founder's.
 *   item 514  FOUNDER, verbatim: *"She said it was ok to use her name"* → F-03
 *             OPENS. Passages 2 and 3 print "Ms. Ryan".
 *   item 515  the expanded newsletter Adams section is approved WITH the height
 *             reveal carrying Ms. Ryan's "five feet" — which is passage 1's
 *             sentence, named.
 *
 * ⛔ EXACTLY THREE SUBSTITUTIONS ARE MADE, AND THEY ARE LISTED INDIVIDUALLY:
 *
 *   passage 1  "The librarian called out that she is five feet"
 *              →  "Ms. Ryan called out that she is five feet"
 *   passage 2  "the librarian had to shut the library door"
 *              →  "Ms. Ryan had to shut the library door"
 *   passage 3  "The librarian pulled the globe from the back of the classroom"
 *              →  "Ms. Ryan pulled the globe from the back of the classroom"
 *
 * ⛔ "the library has the books" IN PASSAGE 2 IS DELIBERATELY LEFT ALONE. The
 *    read-back sheet removed her name from that clause for a reason that has
 *    nothing to do with consent — the sentence reads better without the
 *    apposition — and item 514 restored her name to the two door/globe spots,
 *    not to this one. Re-inserting it here would be this lane rewriting approved
 *    copy on its own initiative.
 *
 * ⚠ PASSAGE 1's SUBSTITUTION IS THE ONE JUDGMENT IN THIS FILE, AND IT IS
 *   FLAGGED RATHER THAN BURIED. Item 514's ruling text enumerates passages
 *   "2/3". Passage 1 is named on the strength of the gate being open house-wide
 *   plus item 515 approving exactly this sentence, named, for the newsletter.
 *   Reverting it is a two-word edit to the constant below and nothing else.
 *
 * ⛔ PASSAGE 4 IS VERBATIM WITH NO SUBSTITUTION. It names nobody.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠ PASSAGE 4 AND THE STALE FINGERPRINT LIST — read before "fixing" it
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Passage 4 contains "Jiri", "Island Peak", "just over 20,000 feet" and
 * "without supplemental oxygen". `BHP-AGENT-STANDING-RULES.md` §3 still lists
 * those four as UNCONFIRMED founder specifics that "must not appear in any
 * output". **That list is stale, and its correction has been ordered three
 * times by Andrew and never applied:**
 *
 *   item 64   Island Peak summit ATTESTED and TRUE; the never-say rail applies
 *             to EVEREST, which he did not summit. Jiri ATTESTED. The §3 list
 *             is recorded there as "to be corrected".
 *   item 229  The Jiri and above-20,000-feet facts ruled ATTESTED and approved
 *             for use, with the fingerprint check ordered corrected.
 *   item 231  The without-supplemental-oxygen form is ATTESTED and explicitly
 *             SUPERSEDES item 229's contrary detail, which the record marks as
 *             a misstatement. Without-oxygen is the settled form.
 *   item 418  The over-80-days-in-Nepal specific joins the released set.
 *
 * ⛔ THE FOUNDER'S OWN WORDS ON HIS CLIMB ARE **NOT** QUOTED IN THIS FILE, ON
 *    PURPOSE. `C:\BHP\brave-hearts-theme` is a PUBLIC GitHub repository and
 *    Standing Rules §4.1 lists founder interview and life-story records as
 *    PRIVATE in any form. The rulings above are cited by ITEM NUMBER AND
 *    OUTCOME, which §4.1 expressly permits, and the private record is reached
 *    through the carrier file rather than reproduced here. **The passage text
 *    below is a different thing: it is approved PUBLIC copy, written to be
 *    read by a teacher on this page.**
 *
 * ⛔ THE RAILS THAT DO STILL BIND, AND THIS PASSAGE HONOURS ALL OF THEM:
 *    "just over 20,000 feet" in exactly that wording, never rounded up · no
 *    Everest summit claimed, implied or placed adjacent · no outcome claim.
 *
 * ⛔ DO NOT "CORRECT" THIS PASSAGE AGAINST §3's LIST. Doing so would revert
 *    founder-attested biography to a superseded rail, which is the exact
 *    over-application item 64 recorded and reversed once already.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The four approved passages, keyed by slot id.
 *
 * ⛔ ZERO EM DASHES AND ZERO EN DASHES, asserted mechanically by
 *    `tests/test-cycle170-ship-prep.php` rather than trusted to a careful eye.
 *
 * @return array<string,string>
 */
function bhp_readaloud_approved_passages() {
	return array(
		/*
		 * PASSAGE 1 · THE OPENING · carrier 413 · approved at item 420 and again
		 * at item 512. "Ms. Ryan" per items 514/515.
		 */
		'founder-1' => 'I recently read to a room of thirty kids at Adams Elementary in Boise. I asked them how tall they were. They looked around at each other. Nobody knew. Ms. Ryan called out that she is five feet, so I told the kids they were probably three and a half to four feet tall. Then I asked how tall they thought Mount Everest was. Puzzled faces. Shy silence. I flipped to the next slide and said 29,032 feet. Their jaws dropped.',

		/*
		 * PASSAGE 2 · WHAT HAPPENED IN THE SESSION · carrier 414.
		 * "the library has the books" keeps its approved form. The door clause
		 * takes her name per item 514.
		 */
		'founder-2' => 'We read all the way through chapter nine, and they were bummed we could not finish the book. They were reassured when I said the library has the books, so they can check them out. Then the definition of resilience, and the tools you need to be resilient. The breathing techniques, four seconds in and four seconds out. The mantras. Manas means mind. Tra means tool. Then we yelled I can do hard things, and Ms. Ryan had to shut the library door.',

		/*
		 * PASSAGE 3 · THE GLOBE · carriers 368 and 411. "Ms. Ryan" per item 514.
		 */
		'founder-3' => 'Ms. Ryan pulled the globe from the back of the classroom, and I showed the kids where Idaho was and how far away Nepal is. They had great questions about Everest and the Khumbu Icefall.',

		/*
		 * PASSAGE 4 · WHY I CAN TAKE THEM THERE · carriers 416 and 418, approved
		 * at item 420 with its first sentence dropped, and again at item 512.
		 * VERBATIM. No substitution. Names nobody.
		 */
		'founder-4' => 'I spent over 80 days in Nepal, walked in from Jiri toward Base Camp, and summited Island Peak, just over 20,000 feet, without supplemental oxygen. On the climb I used the same tools I write into the books: the breathing, the I can do hard things mantra, one step at a time. At the read aloud, the kids asked if I was tired. I told them yes. And that your mind and good food can propel you anywhere you want to go.',
	);
}

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.334 (2026-08-30, `CYCLE170-LD-MVP`) — THE CONVERSION TRIM.
 *     CARRIER ITEM **530**, FOUNDER-SEALED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The `/school-read-alouds/` About section drops **passages 1, 2 and 3** — the
 * Adams Elementary narrative — and keeps **ONLY passage 4**, the Nepal /
 * Island Peak authority paragraph, in that slot area.
 *
 * ⛔⛔ THE PASSAGES ARE NOT DELETED, AND THAT IS ITEM 530's OWN INSTRUCTION.
 *     `bhp_readaloud_approved_passages()` above still returns all four, still
 *     character-exact, still with their full provenance blocks. They are
 *     DISABLED FOR THIS ONE SURFACE, not struck from the record. ⭐ Re-enabling
 *     them is deleting three strings from `bhp_readaloud_trimmed_slots()` and
 *     nothing else — no copy has to be found, re-approved or retyped.
 *
 * ⭐ WHY THE STORY IS NOT LOST FROM THE PAGE. The brief's reasoning, recorded
 *    so a later reader does not "restore" it as a regression: the **Past
 *    Read-Alouds** card further down the page already carries a one-line taste
 *    of the Adams session plus the blog link that tells it in full. Three
 *    paragraphs of narrative sat between a teacher and the scheduler; the card
 *    keeps the story and stops charging her three paragraphs to reach the form.
 *
 * ⛔⛔ THE REMOVAL IS SCOPED TO THIS PAGE, AND THE SCOPING IS THE LOAD-BEARING
 *     PART OF THIS BLOCK. `page-gallery.php` ALSO RENDERS `founder-1`,
 *     `founder-2` AND `founder-3` (lines 162-164). An UNCONDITIONAL unset here
 *     would have emptied `/gallery/`'s About section silently — no placeholder,
 *     no error, just three paragraphs gone from a surface item 530 says nothing
 *     about. Item 530 governs `/school-read-alouds/`. So does this filter.
 *
 * ⛔ UNSET, NOT `pending => true`. `bhp_readaloud_funnel_render_slot()` returns
 *    EARLY AND SILENTLY on a slot id it does not hold (`inc/gallery-page.php`
 *    line 175), but renders the loud `[PENDING READ-BACK — do not publish]`
 *    block for a slot that exists and is pending. Marking them pending would
 *    have put three placeholders on the founder's return gate. ⭐ THAT IS WHY
 *    THIS SHIPS WITH ZERO TEMPLATE EDITS: the three `render_slot()` calls in
 *    `page-school-read-alouds.php` stay exactly where they are and print
 *    nothing, which is also what makes the trim reversible from this file alone.
 * ═══════════════════════════════════════════════════════════════════════════ */

/**
 * The slots item 530 takes off the teacher page.
 *
 * ⛔ A LITERAL LIST, never a pattern like "every founder slot except the last".
 *    A pattern would have swallowed `founder-4` the moment a `founder-5` was
 *    approved, which is the exact class of accident the merged-slugs list in
 *    `inc/school-read-alouds.php` is written as a literal to avoid.
 *
 * @return string[]
 */
function bhp_readaloud_trimmed_slots() {
	return apply_filters(
		'bhp_readaloud_trimmed_slots',
		array( 'founder-1', 'founder-2', 'founder-3' )
	);
}

/**
 * Land the approved copy into the funnel's slot map.
 *
 * ⭐ SLOT `founder-4` IS CREATED HERE, AND THAT IS A REAL FINDING, NOT A
 *    CONVENIENCE. `bhp_readaloud_funnel_copy_slots()` declares three founder
 *    slots plus `educators-1`. The merged page renders the three founder slots
 *    and NEVER renders `educators-1` — the merge dropped that call, so the slot
 *    is orphaned on the page that actually ships.
 *
 *    There are FOUR approved founder passages. Putting the fourth into the
 *    orphaned `educators-1` would have satisfied the slot map and silently
 *    failed to print a passage Andrew approved. So the fourth passage gets its
 *    own founder slot and `page-school-read-alouds.php` gains the one render
 *    call that prints it.
 *
 * ⛔ `educators-1` IS LEFT PENDING ON PURPOSE. Its spec is the teacher and
 *    librarian lead paragraph, which is machine-written copy from §2.4 of the
 *    read-back sheet, NOT one of the four passages item 512 approved. It still
 *    awaits the §4 strike pass. Filling it from this lane would ship unapproved
 *    prose in Andrew's voice, which is the precise failure the slot mechanism
 *    exists to catch. It renders nowhere on the shipping page, so it costs
 *    nothing to leave honest.
 *
 * @param array<string,array<string,mixed>> $slots Declared slot map.
 * @return array<string,array<string,mixed>>
 */
function bhp_readaloud_land_approved_copy( $slots ) {
	if ( ! is_array( $slots ) ) {
		return $slots;
	}

	$passages = bhp_readaloud_approved_passages();

	// The fourth passage needs a slot to live in. Declared with the same shape
	// as its three siblings so the renderer cannot tell them apart.
	if ( ! isset( $slots['founder-4'] ) ) {
		$slots['founder-4'] = array(
			'section' => 'founder',
			'label'   => __( 'FOUNDER INTRO — PARAGRAPH 4', 'brave-hearts' ),
			'spec'    => __( 'Why he can take them there. Approved at carrier item 512.', 'brave-hearts' ),
			'pending' => true,
		);
	}

	foreach ( $passages as $slot_id => $copy ) {
		if ( ! isset( $slots[ $slot_id ] ) ) {
			continue;
		}
		$slots[ $slot_id ]['copy']    = $copy;
		$slots[ $slot_id ]['pending'] = false;
	}

	/*
	 * ⭐ 1.19.334, ITEM 530 — THE TRIM, APPLIED LAST AND ONLY ON THE TEACHER
	 *    PAGE. See the block comment above this function's siblings for why it
	 *    is an unset rather than a `pending` flag, and why it is scoped.
	 *
	 * ⛔ THE GUARD IS `function_exists()`-WRAPPED. This file is loaded by
	 *    `functions.php` and `inc/school-read-alouds.php` is a separate require;
	 *    a load-order change must not fatal the copy filter. When the helper is
	 *    absent NOTHING is trimmed, which is the safe direction: the page shows
	 *    approved copy rather than an empty section.
	 */
	if ( function_exists( 'bhp_school_readalouds_is_page' ) && bhp_school_readalouds_is_page() ) {
		foreach ( bhp_readaloud_trimmed_slots() as $trimmed ) {
			unset( $slots[ $trimmed ] );
		}
	}

	return $slots;
}
add_filter( 'bhp_readaloud_funnel_copy_slots', 'bhp_readaloud_land_approved_copy' );

/* ═══════════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.333 (2026-08-30, `CYCLE170-LD-BUNDLE`) — THE APPROVED VISIT SECTION
 *    AND THE THREE HERO CHIPS. FOUNDER-APPROVED, CARRIER ITEMS 522 / 523.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ PROVENANCE, STATED HONESTLY AND WITHOUT INFLATION. Unlike the four
 *     passages above — which are copied out of a read-back sheet that exists on
 *     disk at
 *     `Business OS\ANDREW-REVIEW\2026-08-30\readaloud-funnel-copy\READ-BACK-SHEET.md`
 *     and can be diffed — the strings below were TRANSCRIBED FROM THE BUILD
 *     BRIEF, which cites carrier items 522 and 523 as founder-approved.
 *     ⚠ A corpus-wide search on 2026-08-30 found NO file under `C:\BHP`
 *     containing them, so there is no second copy to diff against. They are
 *     recorded here character-exact as briefed, and that single-source status
 *     is flagged rather than presented as a two-source verification.
 *
 * ⛔ ZERO EM DASHES AND ZERO EN DASHES, asserted mechanically by
 *    `tests/test-cycle170-bundle.php` rather than trusted to a careful eye.
 *
 * ⛔ THE STRAIGHT DOUBLE QUOTES AROUND THE MANTRA ARE DELIBERATE AND ARE NOT
 *    "TYPOGRAPHICALLY IMPROVED" TO CURLY ONES. The brief marks this copy
 *    CHARACTER-EXACT; a smart-quote pass is a character change. `esc_html()`
 *    renders them as `&quot;`, which is the same character to a reader.
 *
 * ⛔ NOT ONE WORD IS WRITTEN HERE. No sentence is added, joined, shortened,
 *    reordered or smoothed. The numbering is presentational (an `<ol>`); the
 *    sentences themselves are the founder's.
 */

/**
 * The numbered points of "What a visit looks like".
 *
 * ⛔⛔ AMENDED AT 1.19.336 (`CYCLE170-LD-CHAIN`, founder-sealed item 541).
 *     WAS: "The four numbered points of "What a visit looks like"." and a
 *     FOUR-element array. A FIFTH point was approved and is added at the end.
 *     ⭐ The superseded docblock line is quoted here rather than deleted, for
 *     the same reason the passages above keep theirs: a future reader needs to
 *     see that the count moved deliberately and by whose authority.
 *
 * ⛔⛔ THE FIFTH POINT IS VERBATIM, CHARACTER-EXACT, AND THIS BUILD WROTE NONE
 *     OF IT. Item 541: "I leave a signed copy for your classroom library,
 *     free." ⛔ RELAYED THROUGH GANDALF IN THE BRIEF, NOT WITNESSED FIRST-HAND
 *     BY THIS BUILD (§9.2 rule 3), and recorded that way for the same reason
 *     item 534's honest line is.
 *
 * ⛔ NO "we" (§9.1 — it is his I-voice). ⛔ No em dash. ⛔ NO OUTCOME CLAIM: it
 *    says what he DOES, never what it does to a child, and "free" restates the
 *    page's existing "There is no charge." rather than adding a new offer.
 *
 * ⭐ THE STRAIGHT APOSTROPHE QUESTION DOES NOT ARISE — the sentence contains no
 *    apostrophe at all. The only punctuation is one comma and one period, both
 *    the founder's.
 *
 * @return string[]
 */
function bhp_readaloud_visit_shape_points() {
	return array(
		'A short presentation on the character values behind the books: bravery, resilience, kindness, and curiosity.',
		'Mantras: repeated phrases like "I can do hard things!" that empower little humans to be confident, strong, and brave.',
		'Kid friendly breathing techniques for regulating emotions.',
		'I read one book, and I answer every question they have.',
		'I leave a signed copy for your classroom library, free.',
	);
}

/**
 * The closing couplet that follows the four points.
 *
 * ⭐ RETURNED AS ONE STRING, NOT TWO. The two sentences are quoted in the brief
 *    as a single couplet, so they are stored joined by a single space and
 *    printed in ONE paragraph. That makes the briefed text appear CONTIGUOUSLY
 *    in the rendered DOM, which is the form a verification can actually assert.
 *
 * @return string
 */
function bhp_readaloud_visit_shape_closing() {
	return 'These books are made for early readers, to help them gain reading confidence and confidence in life. My goal is to empower kids to be better little humans, in the classroom and outside of it.';
}

/**
 * The three hero chips under the CTA.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ AMENDED AT 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562, GANDALF'S
 *     IMPLEMENTATION RULING). CHIP 1 DROPS THE WORD "Free".
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ SUPERSEDED ARRAY, QUOTED VERBATIM RATHER THAN DELETED, so a future reader
 *    can see that the word left deliberately and by whose authority — and does
 *    not "restore" it as a lost fact:
 *
 *        return array(
 *            'Free for Boise-area schools',
 *            'October onward',
 *            'I confirm every request personally',
 *        );
 *
 * ⭐ THE FACT IS NOT LOST AND WAS NEVER THIS CHIP'S TO CARRY. "Free" still
 *    appears TWICE in the hero — in the `<h1>` ("Book a free read-aloud") and on
 *    the CTA button ("Book a FREE read-aloud"), which are item 481's own words
 *    and are untouched. The founder's instruction is that the hero says it twice
 *    and no more; this chip was the third saying, and the "There is no charge."
 *    note was a fourth statement of the same fact. ⛔ THE OFFER DID NOT CHANGE.
 *    Nothing on this page now charges for a read-aloud that did not before, and
 *    the fifth visit point still reads "I leave a signed copy for your classroom
 *    library, free." further down the page (item 541, untouched — it is outside
 *    the hero region the ruling scopes).
 *
 * ⛔ THE REMAINING WORDS ARE THE FOUNDER'S OWN, UNCHANGED. "Boise-area" is the
 *    hero lead's own wording ("Boise-area classroom read-alouds", approved at
 *    1.19.319) and "schools" is the surface's own subject. No word was written
 *    here; one was removed.
 *
 * ⛔ THE OTHER TWO CHIPS ARE BYTE-IDENTICAL TO 1.19.333:
 *      · "October onward"                     the hero lead's own wording,
 *        approved at 1.19.319.
 *      · "I confirm every request personally" the scheduler's own tentative
 *        line ("Nothing is confirmed until I email you back.") in the hero's
 *        register.
 *
 * ⛔ NO CHIP MAKES A CLAIM THAT IS NOT ALREADY TRUE ON THIS PAGE. No count, no
 *    rating, no reaction, no result, no price, no school invented. §3's
 *    never-invent list is the binding constraint on this array.
 *
 * @return string[]
 */
function bhp_readaloud_hero_chips() {
	return array(
		'Boise-area schools',
		'October onward',
		'I confirm every request personally',
	);
}
