<?php
/**
 * THE SCHOOL-VISIT VARIANT OF THE COMPLETED-ORDER EMAIL (E2-V).
 * Theme 1.19.315. Workstream `CYCLE168-LD-VISIT-COMPLETED-EMAIL`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ WHAT THIS IS, AND THE ONE THING IT MUST NEVER DO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A hand-delivered school-visit order and a printed-and-posted order are the
 * same WooCommerce email id (`customer_completed_order`) describing two
 * completely different events. Today's E2 says the books "have left our print
 * partner", "are on their way to you", carries a no-tracking-number apology,
 * and closes with the sitewide FD-76 footer sentence "Printed and fulfilled by
 * our publishing partner, Bookvault."
 *
 * ⛔ EVERY ONE OF THOSE SENTENCES IS FALSE FOR A VISIT ORDER. The books were
 *    signed by hand and put into a child's backpack. Nothing was printed for
 *    that order, nothing was posted, and there is no partner in the story.
 *
 * ⭐ SO THIS FILE SUPPLIES A SECOND SET OF STRINGS, SELECTED BY ORDER META,
 *    AND NOTHING ELSE. It is a copy layer:
 *
 * ⛔ IT WRITES NO WooCommerce SETTING, NO OPTION, NO ORDER RECORD AND NO ORDER
 *    META, ON ANY ENVIRONMENT. It sends no email and it enables none. It only
 *    answers "which words?" when WooCommerce is already rendering. The whole
 *    variant reverts with a theme rollback.
 *
 * ⛔⛔ THE NON-VISIT PATH IS UNTOUCHED. Every helper below returns '' or false
 *     for an order with no `_bhp_school_visit_slug`, and every caller in
 *     `inc/transactional-emails.php` and in the two template overrides is
 *     written so that the false branch is byte-for-byte the 1.19.314 code.
 *     A standard customer's completed-order email is not changed by this
 *     build. That was verified by rendering one before and after and
 *     comparing (see the workstream QA record), not by reading the diff.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE COPY IS LOCKED. STANDING RULES §9. DO NOT REWORD IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The `adams-2026-08-28` set below is Andrew Signore's own writing, approved
 * verbatim on 2026-08-28. Provenance, read first-hand by the desk that wrote
 * this file rather than accepted from a brief:
 * `Business OS\WORKING-DRAFTS\chief-of-staff\FOUNDER-VERBATIM-2026-08-05-
 * PRODUCTION-DEPLOY-AUTHORIZATION.md`, carrier item 377, line 1030. Ruling 1:
 * *"VISIT-ORDER COMPLETED EMAIL APPROVED - his copy verbatim with the two
 * accepted smoothings, NO coloring-page QR line (deliberately removed - single
 * ask), the false Bookvault fulfilment footer DROPPED for visit orders,
 * standard shipped orders untouched."*
 *
 * ⛔ NO COLORING-PAGE QR LINE. Its absence is a DECISION, not an oversight.
 *    Andrew, verbatim: *"i removed the coloring page QR - if they see it -
 *    they will scan it and we will check the numbers from this read aloud in
 *    a week to see how many scans we got."* Adding one back would destroy the
 *    measurement the T+7 checkpoint (~2026-09-04) exists to take.
 *
 * ⛔ NO EM DASH (U+2014) anywhere in these strings. Standing email rule, and
 *    his own. There are none, and there must go on being none.
 *
 * ⚠ THE "we" IN PARAGRAPH ONE IS NOT A VOICE-RULE BREACH AND MUST NOT BE
 *   "FIXED". Standing Rules §9.1 forbids a "we" that stands for the COMPANY.
 *   *"We read from Mount Everest"* is Andrew and thirty children in a room; it
 *   stands for the group he was in. It is also his own locked prose, and §9
 *   forbids silently rewriting that. Both rules point the same way: leave it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY THE STRINGS ARE KEYED BY VISIT SLUG, WHICH IS THE WHOLE POINT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The approved subject says "1st and 2nd Graders". Paragraph one says "Mount
 * Everest" and "all thirty or so of us". ⭐ ALL THREE ARE TRUE OF ADAMS
 * ELEMENTARY ON 2026-08-28 AND OF NOTHING ELSE. A single global string would
 * mean that the day somebody completes a Dallas Harris order, a Dallas Harris
 * parent is told about a book that was not read to their child and a headcount
 * nobody took.
 *
 * ⛔ THAT WOULD BE A FABRICATED AUTHOR EXPERIENCE AND A FABRICATED CLASSROOM
 *    RESULT - two entries on the never-invent list (Standing Rules §3), sent
 *    to real parents. It is the single largest risk in this build and the
 *    keying below exists to make it structurally impossible.
 *
 * ⭐ SO: an UNKNOWN slug never inherits Adams wording. It falls to the neutral
 *    `_default` set, which names no school, no grade, no book and no number.
 *
 * ⚠⚠ THE `_default` SET IS DRAFTED BY ENGINEERING AND IS **NOT YET APPROVED BY
 *    ANDREW**. It is flagged `approved => false` and it is flagged in the
 *    workstream report. It is deliberately still WIRED IN rather than left to
 *    fall through to the standard E2, because the standard E2's shipping
 *    sentences are FALSE for a hand-delivered order and a false email is worse
 *    than an unapproved-but-true one. ⛔ NO OTHER VISIT'S ORDERS SHOULD BE
 *    FLIPPED TO `completed` UNTIL ANDREW HAS APPROVED THAT SET OR SUPPLIED HIS
 *    OWN. That is an operating instruction, not a code guarantee, and it is
 *    written here so the next reader meets it.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * The order meta key carrying the school-visit slug.
 *
 * ⛔ NOT re-derived and NOT re-typed as a bare literal at each call site. The
 *    bundle plugin owns this key and defines it as
 *    `BHP_SCHOOL_PICKUP_META_SLUG` in `includes/school-visit-pickup.php`. The
 *    constant is preferred when the plugin is active; the literal below is the
 *    fallback for the case the theme renders an email while the plugin is not
 *    loaded, which is exactly when a hard-coded mismatch would silently send
 *    the wrong email to a real parent.
 *
 * ⭐ VERIFIED LIVE, not assumed: all eight Adams orders on production carry
 *    `_bhp_school_visit_slug = "adams-2026-08-28"`, read read-only over SSH
 *    with `wp wc shop_order get <id> --user=1` on 2026-08-28.
 */
if ( ! defined( 'BHP_VISIT_EMAIL_META_SLUG' ) ) {
	define( 'BHP_VISIT_EMAIL_META_SLUG', '_bhp_school_visit_slug' );
}

/** The WooCommerce email id this whole file is scoped to. Nothing else. */
if ( ! defined( 'BHP_VISIT_EMAIL_ID' ) ) {
	define( 'BHP_VISIT_EMAIL_ID', 'customer_completed_order' );
}

/**
 * Read the school-visit slug off an order.
 *
 * @param WC_Order|mixed $order Order, or anything at all.
 * @return string Slug, or '' when this is not a visit order.
 */
function bhp_visit_email_order_slug( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	$key = defined( 'BHP_SCHOOL_PICKUP_META_SLUG' ) ? BHP_SCHOOL_PICKUP_META_SLUG : BHP_VISIT_EMAIL_META_SLUG;

	$slug = $order->get_meta( $key );

	return is_string( $slug ) ? trim( $slug ) : '';
}

/**
 * The order an email object is currently rendering, IF it is the completed
 * email. Null for every other email in the system.
 *
 * ⛔ THE ID TEST IS NOT DECORATION. `$email->object` is set by every
 *    `WC_Email::trigger()` in WooCommerce, so without the id check this helper
 *    would happily hand back the order for the processing email, the refund
 *    email and the add-on thank-you, and the visit copy would leak into all of
 *    them. This file changes exactly one email.
 *
 * @param WC_Email|mixed $email Email object.
 * @return WC_Order|null
 */
function bhp_visit_email_order( $email ) {
	if ( ! $email instanceof WC_Email ) {
		return null;
	}
	if ( BHP_VISIT_EMAIL_ID !== $email->id ) {
		return null;
	}

	$order = isset( $email->object ) ? $email->object : null;

	return ( $order instanceof WC_Order ) ? $order : null;
}

/**
 * The visit slug for the email currently rendering, or ''.
 *
 * This is the single question every caller in the copy layer asks. A '' answer
 * means "ordinary completed-order email, change nothing".
 *
 * @param WC_Email|mixed $email Email object.
 * @return string
 */
function bhp_visit_email_slug( $email ) {
	return bhp_visit_email_order_slug( bhp_visit_email_order( $email ) );
}

/**
 * Is the email currently rendering a school-visit completed-order email?
 *
 * @param WC_Email|mixed $email Email object.
 * @return bool
 */
function bhp_visit_email_is_visit( $email ) {
	return '' !== bhp_visit_email_slug( $email );
}

/**
 * The per-visit copy sets.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ LOCKED PROSE. STANDING RULES §9. PROPOSE CHANGES; DO NOT MAKE THEM.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Shape of a set:
 *   'subject'   string  Inbox subject line.
 *   'heading'   string  The email's H1.
 *   'preheader' string  Inbox preview text.
 *   'body'      array   Paragraphs, in render order. HTML and plain text both
 *                       walk this same array, so the two versions cannot drift
 *                       apart - a promise that exists in one and not the other
 *                       is the defect this shape exists to prevent.
 *   'approved'  bool    TRUE only where Andrew approved the exact strings.
 *
 * ⚠ `approved` IS DOCUMENTATION AND A TEST ASSERTION, NOT A KILL SWITCH. It
 *   does not gate rendering, deliberately: see this file's header for why a
 *   true-but-unapproved email beats a false approved one. It exists so that
 *   `bhp_visit_email_copy_is_approved()` can answer honestly, so the suite can
 *   assert that exactly one set is approved today, and so nobody has to guess.
 *
 * @return array<string,array>
 */
function bhp_visit_email_copy_sets() {
	return array(

		/*
		 * ⭐⭐⭐ ADAMS ELEMENTARY, 2026-08-28. ANDREW'S OWN WORDS, APPROVED
		 *      VERBATIM. Carrier item 377 ruling 1. The two accepted
		 *      smoothings he signed off are "(or children, for a few
		 *      families)" in paragraph two and "listened so well" in
		 *      paragraph three.
		 *
		 * ⛔ "1st and 2nd Graders", "Mount Everest" and "all thirty or so of
		 *    us" are ADAMS FACTS. They are true here and nowhere else, which
		 *    is the entire reason this array is keyed by slug.
		 */
		'adams-2026-08-28' => array(
			'approved'  => true,
			'subject'   => __( 'What an awesome group of 1st and 2nd Graders!', 'brave-hearts' ),
			'heading'   => __( 'The signed books are with the kiddos! Along with a coloring book page.', 'brave-hearts' ),
			'preheader' => __( 'Signed Books, Delivered, and ready to read.', 'brave-hearts' ),
			'body'      => array(
				__( 'What an awesome group of kiddos! We read from Mount Everest, practiced Stop, Breathe, Think, Act, and yelled I can do hard things together, all thirty or so of us!', 'brave-hearts' ),
				__( 'Your signed books went home with your child today (or children, for a few families), along with a coloring book page from the read aloud.', 'brave-hearts' ),
				__( 'I also wanted to reach out and genuinely say thank you for raising such an awesome kiddo. Everyone in the group paid attention and listened so well.', 'brave-hearts' ),
				/*
				 * ⛔⛔ 1.19.364 · THE REVIEW / AMAZON PARAGRAPH IS REMOVED FROM THIS
				 *     APPROVED SET. ANDREW, SEAL 977, 2026-09-05, TWO RULINGS IN ONE
				 *     SITTING: *"I want one destination not 2"* and *"I think the
				 *     thank you email should be the day 0 email, the visit day email
				 *     should be a warm ..."* — day 0 now asks for nothing at all.
				 *
				 * ⚠ THIS IS AN EDIT TO LOCKED PROSE (Standing Rules §9) AND IT IS
				 *   MADE ONLY BECAUSE THE OWNER HIMSELF RULED THE SENTENCE OUT. It is
				 *   a DELETION, not a rewording: not one surviving word of Andrew's
				 *   own writing was touched, and nothing was written in its place.
				 *
				 * ⛔ THE REMOVED SENTENCE, PRESERVED VERBATIM RATHER THAN DELETED
				 *    (approved 2026-08-28, superseded 2026-09-05 by seal 977):
				 *
				 *      "If they read the books and like them, there is a small thank
				 *       you page with a QR code in the back. It goes to Amazon
				 *       reviews. If you could write a review on the book/s it will
				 *       help other early readers learn the lessons your little human
				 *       got today."
				 *
				 * ⭐ AND IT IS NOT ONLY A PREFERENCE. Merry decoded the V6 bookmark QR
				 *    first-hand on 2026-09-05 (`CYCLE179-MKT-REVIEW-SEQ-V2.md` §7): it
				 *    resolves to `amazon.com/review/create-review`, so this paragraph
				 *    named the exact second destination Andrew has just removed.
				 *
				 * ⭐ THE ASK IS NOT LOST, IT MOVED. The review request is now touch 1
				 *    of the seal-965/977 sequence in `inc/review-ask-email.php`, seven
				 *    days later, pointing at the SITE review page. `CYCLE179-LD-40`
				 *    ("is the visit email's own ask one ask too many?") is closed by
				 *    this removal.
				 */
				__( 'Feel free to email me any time at Andrew@braveheartspublishing.com, once again thank you!', 'brave-hearts' ),
			),
		),

		/*
		 * ═══════════════════════════════════════════════════════════════════
		 * ⚠⚠ THE NEUTRAL FALLBACK. DRAFTED BY ENGINEERING 2026-08-28.
		 *     **NOT APPROVED BY ANDREW.** FLAGGED IN THE WORKSTREAM REPORT.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * ⭐ WHAT IT DELIBERATELY DOES NOT SAY, AND WHY EACH OMISSION IS
		 *    LOAD-BEARING:
		 *
		 *    - NO grade band. "1st and 2nd Graders" is an Adams fact.
		 *    - NO book title. "Mount Everest" is an Adams fact; a different
		 *      visit may read a different book.
		 *    - NO headcount. "thirty or so" is an Adams fact.
		 *    - NO coloring-book page. One was handed out at Adams. Promising
		 *      a parent an item that is not in the backpack is the exact
		 *      failure class the Bookvault footer removal exists to fix.
		 *    - NO "Everyone in the group paid attention and listened so
		 *      well." That is Andrew's observation of a room he was standing
		 *      in. Asserting it in advance of a visit nobody has run yet
		 *      would fabricate a classroom result (Standing Rules §3).
		 *    - NO "Stop, Breathe, Think, Act". It is the series' framework
		 *      and it is very likely said at every read aloud, but "very
		 *      likely" is not "observed", and this set may be read by a
		 *      parent before anyone checks.
		 *
		 * ⭐ WHAT SURVIVES UNCHANGED FROM THE APPROVED SET: paragraphs four
		 *    and five. The review ask is about the BOOK, not the event, and
		 *    is true of every copy ever printed; the sign-off is his address
		 *    and his thank-you. Neither carries an Adams fact.
		 */
		/*
		 * ══════════════════════════════════════════════════════════════════
		 * ⭐⭐ 1.19.364 · THE DAY-0 EMAIL IS NOW A WARM NOTE THAT ASKS FOR
		 *     NOTHING. ANDREW, SEAL 977, VERBATIM: *"I think the thank you email
		 *     should be the day 0 email, the visit day email should be a warm -
		 *     Your kid received the books, explain the mission, the lyrical
		 *     prose, the white space, the ease of use, short chapters and why
		 *     its important to use these books"*.
		 * ══════════════════════════════════════════════════════════════════
		 *
		 * ⛔⛔ `approved => false`, AND THAT IS NOT AN OVERSIGHT. Merry's own
		 *     file, which is where every word below comes from, states its status
		 *     in its first paragraph and again at the end of its §1: **"Status:
		 *     DRAFT. Copy only"** and **"This is proposed copy, not an applied
		 *     edit ... Andrew approves or rejects; a developer applies."** The
		 *     developer has applied it to the file; Andrew has not yet approved
		 *     it, so `bhp_visit_email_copy_is_approved()` keeps it unsendable.
		 *     ⚠ Flipping this bool is the whole of the approval, and it is his to
		 *     flip — nobody else's, and not on the strength of this comment.
		 *
		 * SOURCE: `Business OS\WORKING-DRAFTS\marketing-growth\
		 * CYCLE179-MKT-REVIEW-SEQ-V2.md` §1, 2026-09-05. Transcribed verbatim.
		 *
		 * ⛔ SUPERSEDED SET, PRESERVED VERBATIM RATHER THAN DELETED. Drafted by
		 *    engineering 2026-08-28, never approved, replaced 2026-09-05:
		 *
		 *      subject/heading: "The signed books are with the kiddos!"
		 *      preheader:       "Signed Books, Delivered, and ready to read."
		 *      body:
		 *        1. "What an awesome group of kiddos! Thank you for letting me
		 *            come and read with them."
		 *        2. "Your signed books went home with your child today (or
		 *            children, for a few families)."
		 *        3. "I also wanted to reach out and genuinely say thank you for
		 *            raising such an awesome kiddo."
		 *        4. the Amazon/QR review paragraph quoted in full above
		 *        5. "Feel free to email me any time at
		 *            Andrew@braveheartspublishing.com, once again thank you!"
		 *
		 * ⭐ WHY THE REPLACEMENT IS SAFER THAN WHAT IT REPLACES, not merely
		 *    newer. The old paragraphs 1 and 3 assert things about a room — *"an
		 *    awesome group of kiddos"*, *"raising such an awesome kiddo"* — in a
		 *    set that fires for a visit nobody has run yet. The body below names
		 *    no grade, no headcount, no book read aloud, no coloring page and no
		 *    child's behaviour. Every school-specific fact now lives in exactly
		 *    one place: `{VisitLine}`, which Andrew fills in himself.
		 *
		 * ⛔⛔ `{VisitLine}` IS NEVER AUTO-FILLED. Merry calls it *"the
		 *     load-bearing safety feature of this email"* and she is right: an
		 *     empty `{VisitLine}` renders NOTHING and the email still reads
		 *     correctly without it. Anything that generates a plausible line for
		 *     it fabricates a classroom result (Standing Rules §3), which is the
		 *     precise failure this slot exists to make structurally impossible.
		 *
		 * ⭐ CLAIMS PROVENANCE (Merry's own table, and it was checked, not
		 *    assumed): the gap between picture books and denser chapter books,
		 *    the white space and the short chapters are Andrew's own words from
		 *    `docs-private\business-os-interviews\
		 *    2026-08-01-founder-operating-interview-verbatim.md`; *"real places,
		 *    wildlife, science"* is the core promise in `C:\BHP\CLAUDE.md`.
		 *    ⛔ NO chapter count ("12 short chapters" is flagged UNVERIFIED in
		 *    repo `docs\NEXT_TASK.md` and is not used), no page count, no Lexile,
		 *    no age range, no award, no rating, no reaction, and no claim about
		 *    what the book will do to a child.
		 */
		'_default'         => array(
			/*
			 * ⭐⭐ 1.19.365 · APPROVED BY ANDREW, SEAL 982, RELAYED THROUGH
			 *     GANDALF VERBATIM: *"agreed, conitnue to build it out"*, given
			 *     on 2026-09-05 and naming day 0, touch 1, touch 2 and the web
			 *     variant of touch 1. It attaches to
			 *     `Business OS\WORKING-DRAFTS\marketing-growth\
			 *     CYCLE179-MKT-REVIEW-SEQ-V2.md` §1 AS IT STOOD AT md5
			 *     `1ecd9c75acfc755df0e121b47ca73842`, verified identical on both
			 *     mounts on 2026-09-05. The body below is that §1 verbatim and
			 *     was diffed against it in this build, not eyeballed.
			 *
			 * ⛔ THIS BOOL IS A HARD SEND GATE (1.19.364), so flipping it is the
			 *    whole of the approval and it was his to flip. It does NOT enable
			 *    anything else: `{VisitLine}` is still never auto-filled, an
			 *    unresolvable slot still renders nothing and routes the order to
			 *    WooCommerce's ordinary completed-order email, and the school-
			 *    specific `adams-2026-08-28` set is untouched.
			 */
			'approved'  => true,
			'subject'   => __( 'The signed books went home today', 'brave-hearts' ),
			/*
			 * ⚠ ENGINEERING COPY, MARKED AS SUCH, AND IT RESTATES THE SUBJECT
			 *   RATHER THAN ADDING A CLAIM. Merry's template is a plain note and
			 *   carries no heading, but `bhp_visit_email_copy_is_usable()` requires
			 *   a non-empty one and an empty string there would silently discard
			 *   this whole set in favour of nothing.
			 */
			'heading'   => __( 'The signed books went home today', 'brave-hearts' ),
			'preheader' => __( 'The signed books went home today', 'brave-hearts' ),
			'body'      => array(
				__( 'Hi {ParentFirstName},', 'brave-hearts' ),
				__( 'Thank you. The signed book went home in a backpack today from {SchoolName}.', 'brave-hearts' ),
				'{VisitLine}',
				__( 'I want to tell you why {BookTitle(s)} is built the way it is, because it is built for one particular kid.', 'brave-hearts' ),
				__( 'Between picture books and thick chapter books there is a gap, and these books are written for the reader standing in it. There is a lot of white space, so a page never looks like a wall. The chapters are short, so the finish line is always close enough to see. The prose is written to be read out loud, and that is how I would read it to you if you were sitting here.', 'brave-hearts' ),
				__( 'The places are real. So are the animals, the weather and the science. None of it is homework and all of it is true.', 'brave-hearts' ),
				__( 'Here is the part that matters more than the book itself. Read the first chapter together tonight, out loud, and then stop and hand it over. A signed book that sits on a shelf is a nice object. A signed book that gets opened on the first night is the reason I drove out to {SchoolName}.', 'brave-hearts' ),
				__( 'Email me any time at Andrew@braveheartspublishing.com.', 'brave-hearts' ),
				__( 'Andrew', 'brave-hearts' ),
			),
		),
	);
}

/**
 * The key used for the neutral fallback set.
 *
 * A named constant rather than a bare `'_default'` at four call sites, because
 * a typo in one of them would silently route a real visit to nothing.
 */
if ( ! defined( 'BHP_VISIT_EMAIL_DEFAULT_KEY' ) ) {
	define( 'BHP_VISIT_EMAIL_DEFAULT_KEY', '_default' );
}

/**
 * Resolve the copy set for one visit slug.
 *
 * ⛔ AN UNKNOWN SLUG GETS THE NEUTRAL SET. It NEVER gets Adams wording, and
 *    there is no ordering, caching or configuration state that can change
 *    that: the lookup is a direct key hit or it is not.
 *
 * @param string $slug Visit slug, e.g. 'adams-2026-08-28'.
 * @return array The resolved copy set.
 */
function bhp_visit_email_copy( $slug ) {
	$sets = bhp_visit_email_copy_sets();
	$slug = is_string( $slug ) ? trim( $slug ) : '';

	$set = ( '' !== $slug && isset( $sets[ $slug ] ) && is_array( $sets[ $slug ] ) )
		? $sets[ $slug ]
		: $sets[ BHP_VISIT_EMAIL_DEFAULT_KEY ];

	/**
	 * Filter the school-visit completed-order copy set.
	 *
	 * ⭐ THE SEAM A FUTURE VISIT DROPS INTO. When Andrew approves wording for
	 *    the next school, the clean move is a new entry in
	 *    `bhp_visit_email_copy_sets()` keyed by that visit's slug. This filter
	 *    exists for the case where the strings must arrive from outside the
	 *    theme, and for the suite, which uses it to prove that an unknown slug
	 *    cannot reach the Adams strings.
	 *
	 * ⛔ A HOOK THAT RETURNS SOMETHING UNUSABLE IS DISCARDED, not trusted. A
	 *    broken filter falls back to the resolved set rather than sending an
	 *    empty email to a parent.
	 *
	 * @since 1.19.315
	 * @param array  $set  The resolved copy set.
	 * @param string $slug The visit slug, '' when none.
	 */
	$filtered = apply_filters( 'bhp_visit_email_copy', $set, $slug );

	return bhp_visit_email_copy_is_usable( $filtered ) ? $filtered : $set;
}

/**
 * Is this array a complete, renderable copy set?
 *
 * @param mixed $set Candidate.
 * @return bool
 */
function bhp_visit_email_copy_is_usable( $set ) {
	if ( ! is_array( $set ) ) {
		return false;
	}
	foreach ( array( 'subject', 'heading', 'preheader' ) as $key ) {
		if ( ! isset( $set[ $key ] ) || ! is_string( $set[ $key ] ) || '' === trim( $set[ $key ] ) ) {
			return false;
		}
	}
	if ( empty( $set['body'] ) || ! is_array( $set['body'] ) ) {
		return false;
	}
	foreach ( $set['body'] as $paragraph ) {
		if ( ! is_string( $paragraph ) || '' === trim( $paragraph ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Has Andrew approved the exact strings that will be sent for this slug?
 *
 * Used by the suite and by any future admin surface. It reports; it does not
 * gate. See the note on `approved` in `bhp_visit_email_copy_sets()`.
 *
 * @param string $slug Visit slug.
 * @return bool
 */
function bhp_visit_email_copy_is_approved( $slug ) {
	$set = bhp_visit_email_copy( $slug );

	return ! empty( $set['approved'] );
}

/* =========================================================================
 * ⭐⭐ 1.19.364 · THE DAY-0 MERGE SLOTS, AND THE GATE THAT MAKES `approved`
 *     MEAN SOMETHING.
 *
 * ⛔⛔ UNTIL NOW `approved` REPORTED AND DID NOT GATE — its own docblock said
 *     so in as many words: *"It reports; it does not gate."* That was tolerable
 *     while every shipped set was either approved or word-for-word harmless.
 *     It stopped being tolerable the moment a set carrying `{ParentFirstName}`
 *     and `{VisitLine}` entered this file: an unapproved set reaching a parent
 *     would now render literal curly braces in the first line of the email.
 *
 * ⭐ THE GATE IS APPLIED AT THE TWO READERS, NOT AT THE SET. `bhp_visit_email_
 *    string()` and `bhp_visit_email_body()` are the only two functions the
 *    templates and `inc/transactional-emails.php` call, so gating them covers
 *    every path. ⚠ They return '' and array() respectively, which is the
 *    documented "this is not a visit email" answer — so a blocked set routes
 *    the order to WooCommerce's ordinary completed-order email. The customer
 *    still gets their completion notice. Nobody gets placeholder text.
 * ====================================================================== */

/**
 * Resolve the day-0 merge slots for one order.
 *
 * ⛔⛔ `{VisitLine}` IS READ FROM THE ORDER'S OWN VISIT RECORD AND IS NEVER
 *     GENERATED. Merry: *"One line Andrew writes himself, per visit ... If
 *     Andrew has not written one, render nothing. Never auto-fill it."* An
 *     empty value here removes the paragraph entirely (see the body reader
 *     below); it does not leave a blank line and it does not invent one.
 *
 * @since 1.19.364
 * @param WC_Order|mixed $order Order.
 * @return array<string,string> Slot => replacement.
 */
function bhp_visit_email_merge_values( $order ) {
	$parent = '';
	$school = '';
	$titles = '';
	$visit  = '';

	if ( $order instanceof WC_Order ) {
		$parent = trim( (string) $order->get_billing_first_name() );

		$slug = bhp_visit_email_order_slug( $order );

		if ( '' !== $slug && function_exists( 'bhp_school_visit_records' ) ) {
			$records = bhp_school_visit_records();

			if ( is_array( $records ) && isset( $records[ $slug ] ) && is_array( $records[ $slug ] ) ) {
				$record = $records[ $slug ];
				$school = isset( $record['school'] ) ? trim( (string) $record['school'] ) : '';

				// ⛔ Andrew's own line for THIS visit, or nothing at all.
				$visit = isset( $record['visit_line'] ) ? trim( (string) $record['visit_line'] ) : '';
			}
		}

		/*
		 * ⭐ A NATURAL LIST, NOT A COMMA-SEPARATED DUMP. Merry's slot table:
		 *    *"One title verbatim, or a natural list for two or more: The
		 *    Mariana Trench and Mount Everest"*.
		 */
		if ( function_exists( 'bhp_review_ask_chapter_book_keys' ) && function_exists( 'bhp_review_book_title' ) ) {
			$names = array();

			foreach ( (array) bhp_review_ask_chapter_book_keys( $order ) as $key ) {
				$title = trim( (string) bhp_review_book_title( $key ) );

				if ( '' !== $title ) {
					$names[] = $title;
				}
			}

			if ( 1 === count( $names ) ) {
				$titles = $names[0];
			} elseif ( count( $names ) > 1 ) {
				$last   = array_pop( $names );
				$titles = implode( ', ', $names ) . ' and ' . $last;
			}
		}
	}

	return array(
		'{ParentFirstName}' => '' !== $parent ? $parent : __( 'there', 'brave-hearts' ),
		'{SchoolName}'      => $school,
		'{BookTitle(s)}'    => $titles,
		'{VisitLine}'       => $visit,
	);
}

/**
 * Are the slots this set uses resolvable for this order?
 *
 * ⛔ `{VisitLine}` IS NOT CHECKED. It is designed to be absent — see
 *    `bhp_visit_email_merge_values()`. `{ParentFirstName}` is not checked
 *    either, because it falls back to "there". The other two ARE hard
 *    requirements: Merry's slot table says *"If no school resolves, this is
 *    not a visit order and this email must not send"* and *"If no title
 *    resolves, do not send"*.
 *
 * @since 1.19.364
 * @param array          $set   Copy set.
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function bhp_visit_email_merge_is_complete( $set, $order ) {
	$blob = wp_json_encode( $set );

	if ( ! is_string( $blob ) ) {
		return false;
	}

	$values = bhp_visit_email_merge_values( $order );

	foreach ( array( '{SchoolName}', '{BookTitle(s)}' ) as $slot ) {
		if ( false === strpos( $blob, $slot ) ) {
			continue;
		}

		if ( ! isset( $values[ $slot ] ) || '' === trim( (string) $values[ $slot ] ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Substitute every slot in one string.
 *
 * @since 1.19.364
 * @param string         $text  Text.
 * @param WC_Order|mixed $order Order.
 * @return string
 */
function bhp_visit_email_merge( $text, $order ) {
	$values = bhp_visit_email_merge_values( $order );

	return str_replace( array_keys( $values ), array_values( $values ), (string) $text );
}

/**
 * May this set be rendered to this order at all?
 *
 * ⛔⛔ THIS IS THE SEND GATE. `approved => false` is now a hard stop, not a
 *     report. See the block comment above.
 *
 * @since 1.19.364
 * @param string         $slug  Visit slug.
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function bhp_visit_email_may_render( $slug, $order ) {
	if ( '' === (string) $slug ) {
		return false;
	}

	$set = bhp_visit_email_copy( $slug );

	if ( empty( $set['approved'] ) ) {
		return false;
	}

	return bhp_visit_email_merge_is_complete( $set, $order );
}

/**
 * One field of the resolved copy set for the email currently rendering.
 *
 * @param WC_Email|mixed $email Email object.
 * @param string         $key   'subject', 'heading' or 'preheader'.
 * @return string '' when this is not a visit email, or the key is not a string field.
 */
function bhp_visit_email_string( $email, $key ) {
	$slug = bhp_visit_email_slug( $email );

	if ( '' === $slug ) {
		return '';
	}

	$order = bhp_visit_email_order( $email );

	// ⛔ 1.19.364 · unapproved copy, or an unresolvable slot, renders nothing.
	if ( ! bhp_visit_email_may_render( $slug, $order ) ) {
		return '';
	}

	$set = bhp_visit_email_copy( $slug );

	if ( ! isset( $set[ $key ] ) || ! is_string( $set[ $key ] ) ) {
		return '';
	}

	return bhp_visit_email_merge( $set[ $key ], $order );
}

/**
 * The body paragraphs for the email currently rendering.
 *
 * @param WC_Email|mixed $email Email object.
 * @return string[] Empty array when this is not a visit email.
 */
function bhp_visit_email_body( $email ) {
	$slug = bhp_visit_email_slug( $email );

	if ( '' === $slug ) {
		return array();
	}

	$order = bhp_visit_email_order( $email );

	// ⛔ 1.19.364 · same gate as `bhp_visit_email_string()`.
	if ( ! bhp_visit_email_may_render( $slug, $order ) ) {
		return array();
	}

	$set = bhp_visit_email_copy( $slug );

	if ( ! isset( $set['body'] ) || ! is_array( $set['body'] ) ) {
		return array();
	}

	$out = array();

	foreach ( $set['body'] as $paragraph ) {
		$merged = trim( bhp_visit_email_merge( $paragraph, $order ) );

		/*
		 * ⭐ AN EMPTY PARAGRAPH IS DROPPED, NOT RENDERED BLANK. This is how
		 *    `{VisitLine}` disappears when Andrew has not written one: the
		 *    paragraph is the slot and nothing else, so the email closes up
		 *    around it exactly as Merry specified.
		 */
		if ( '' !== $merged ) {
			$out[] = $merged;
		}
	}

	return $out;
}
