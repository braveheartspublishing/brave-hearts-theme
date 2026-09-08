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
		 * =====================================================================
		 * ⛔⛔ 1.19.369 · THE PER-SCHOOL SETS ARE RETIRED. THE ADAMS SET IS
		 *     PRESERVED HERE AS A COMMENT AND IS NO LONGER AN ARRAY ENTRY, SO
		 *     NOTHING CAN SELECT IT.
		 * =====================================================================
		 *
		 * ANDREW, ROUND-8 BRIEF, ITEM 2: the engine and `wp bhp review-ask
		 * test-send --set=day0` must use the approved GENERIC day-0 set for ANY
		 * visit order, *"never the old per-school sets, which stay in comments as
		 * superseded"*. This is that instruction, applied literally.
		 *
		 * ⭐ WHY IT IS SAFE AS WELL AS ORDERED. The Adams strings are ANDREW'S
		 *    OWN WORDS about ONE morning: "1st and 2nd Graders", "Mount Everest",
		 *    "all thirty or so of us", "listened so well". They were approved
		 *    verbatim (carrier item 377 ruling 1) and they are TRUE OF ADAMS AND
		 *    NOWHERE ELSE. Keying a live array by slug meant every future visit
		 *    inherited the risk that a mis-keyed order met a claim about a room
		 *    its child was never in. The generic set names no grade, no headcount,
		 *    no book read aloud, no coloring page and no child's behaviour, and
		 *    puts every school-specific fact in exactly one slot Andrew fills in
		 *    himself: {VisitLine}.
		 *
		 * ⚠ WHAT IS LOST, STATED PLAINLY: a future Adams parent re-reading an
		 *   old thread will not find these words again from this file, and the
		 *   already-sent Adams emails are unaffected either way. Nothing is
		 *   recalled, resent or rewritten by this change.
		 *
		 * ⛔ THE SUPERSEDED SET, VERBATIM, KEY `adams-2026-08-28`,
		 *    approved => true, approved 2026-08-28, retired 2026-09-05:
		 *
		 *      subject:   "What an awesome group of 1st and 2nd Graders!"
		 *      heading:   "The signed books are with the kiddos! Along with a
		 *                  coloring book page."
		 *      preheader: "Signed Books, Delivered, and ready to read."
		 *      body:
		 *        1. "What an awesome group of kiddos! We read from Mount Everest,
		 *            practiced Stop, Breathe, Think, Act, and yelled I can do hard
		 *            things together, all thirty or so of us!"
		 *        2. "Your signed books went home with your child today (or
		 *            children, for a few families), along with a coloring book
		 *            page from the read aloud."
		 *        3. "I also wanted to reach out and genuinely say thank you for
		 *            raising such an awesome kiddo. Everyone in the group paid
		 *            attention and listened so well."
		 *        4. "Feel free to email me any time at
		 *            Andrew@braveheartspublishing.com, once again thank you!"
		 *
		 * ⛔ AND THE PARAGRAPH 1.19.364 HAD ALREADY REMOVED FROM THAT SET UNDER
		 *    SEAL 977, PRESERVED A SECOND TIME SO IT IS NOT RESTORED FROM AN OLD
		 *    DRAFT:
		 *
		 *      "If they read the books and like them, there is a small thank you
		 *       page with a QR code in the back. It goes to Amazon reviews. If you
		 *       could write a review on the book/s it will help other early
		 *       readers learn the lessons your little human got today."
		 */

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
				/*
				 * ⭐⭐ 1.19.376 · THE SENTENCE THAT NAMES THE BOOKS IS NOW A SLOT,
				 *     AND THIS IS THE ROUND-14 OPEN DEFECT BEING CLOSED.
				 *
				 *     At 1.19.375 a two- or three-book visit order rendered:
				 *
				 *       "I want to tell you why The Mariana Trench and Mount
				 *        Everest IS built the way IT IS, because IT IS built for
				 *        one particular kid."
				 *
				 *     Three disagreements in one sentence, in front of a parent
				 *     holding both books. ⚠ IT WAS NOT NEW: it has shipped since
				 *     1.19.364, because `{BookTitle(s)}` has ALWAYS been the joined
				 *     list in this lane. Seal 1032 did not cause it; rendering the
				 *     round-14 fixtures is what exposed it.
				 *
				 * ⛔ THE SINGULAR SENTENCE IS UNCHANGED, BYTE FOR BYTE. Both forms
				 *    live in `bhp_visit_email_merge_values()` and are selected by
				 *    `bhp_review_ask_book_verb()`, the same helper the review-ask
				 *    lane has used since 1.19.375. On a one-book order this
				 *    paragraph is identical to 1.19.375's output.
				 *
				 * ⚠ GRAMMAR-ONLY ADJUSTMENT, AWAITING ANDREW'S CONFIRMATION. The
				 *   plural branch changes `is`->`are`, `it is`->`they are` and
				 *   `it`->`they` in APPROVED FOUNDER COPY. Standing Rules §9 puts
				 *   approved copy with Andrew and Merry; this is built and reported
				 *   as a grammar correction for his confirmation, not slipped in as
				 *   an engineering detail. ⛔ Reverting it is one line: return the
				 *   singular from the helper call below.
				 *
				 * ⛔ THE EMPTY-TITLE HARD STOP SURVIVES THE MOVE. `{BookTitle(s)}`
				 *    no longer appears in this body, so
				 *    `bhp_visit_email_merge_is_complete()` now also requires
				 *    `{WhyBuiltLine}`, which resolves to '' when no title resolves.
				 *    An order with no book still refuses to send.
				 */
				'{WhyBuiltLine}',
				__( 'Between picture books and thick chapter books there is a gap, and these books are written for the reader standing in it. There is a lot of white space, so a page never looks like a wall. The chapters are short, so the finish line is always close enough to see. The prose is written to be read out loud, and that is how I would read it to you if you were sitting here.', 'brave-hearts' ),
				__( 'The places are real. So are the animals, the weather and the science. None of it is homework and all of it is true.', 'brave-hearts' ),
				__( 'Here is the part that matters more than the book itself. Read the first chapter together tonight, out loud, and then stop and hand it over. A signed book that sits on a shelf is a nice object. A signed book that gets opened on the first night is the reason I drove out to {SchoolName}.', 'brave-hearts' ),
				__( 'Email me any time at Andrew@braveheartspublishing.com.', 'brave-hearts' ),
				/*
				 * ⭐ SEAL 1007 - THE PLAIN SIGN-OFF IS GONE FROM THE DAY-0 SET TOO.
				 *    Andrew Signore, 2026-09-05, verbatim (⛔ RELAYED through
				 *    Gandalf, not heard first-hand): *"I like the nice signature
				 *    and big place brave hearts - drop the plain one"*. The
				 *    signature block below the rule carries the name.
				 *
				 * ⛔ SUPERSEDED LINE, PRESERVED VERBATIM SO IT IS NOT RE-ADDED:
				 *        __( 'Andrew', 'brave-hearts' ),
				 *    It was the last paragraph of the body and rendered a bare
				 *    "Andrew" directly above the same name in the signature.
				 *
				 * ⚠ THE BODY IS NOW SEVEN PARAGRAPHS, NOT EIGHT. The count is
				 *   asserted in tests/test-visit-completed-email.php; change one
				 *   and the other fails on purpose.
				 */
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

	/*
	 * ⭐⭐ 1.19.369 · ONE SET FOR EVERY VISIT, AND THE LOOKUP IS GONE. Round-8
	 *     brief item 2. `bhp_visit_email_copy_sets()` now carries exactly one
	 *     entry, so the slug no longer selects anything — it is still accepted,
	 *     still passed to the filter, and still the thing `{SchoolName}` and
	 *     `{VisitLine}` are resolved against.
	 *
	 * ⛔ SUPERSEDED LOOKUP, PRESERVED RATHER THAN DELETED:
	 *
	 *      $set = ( '' !== $slug && isset( $sets[ $slug ] ) && is_array( $sets[ $slug ] ) )
	 *          ? $sets[ $slug ]
	 *          : $sets[ BHP_VISIT_EMAIL_DEFAULT_KEY ];
	 *
	 * ⚠ IT IS WRITTEN THIS WAY RATHER THAN DELETED so that adding a second
	 *   entry to that array does NOT silently reinstate per-school routing.
	 *   Whoever wants a school-specific email again has to come back here and
	 *   re-argue seal 994's *"Do what the research suggests"* on purpose.
	 */
	$set = $sets[ BHP_VISIT_EMAIL_DEFAULT_KEY ];

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
	$slug = is_string( $slug ) ? trim( $slug ) : '';
	$set  = bhp_visit_email_copy( $slug );

	/*
	 * ⛔⛔ 1.19.366 · ROOT CAUSE. THIS FUNCTION USED TO ANSWER ABOUT WHATEVER
	 *     `bhp_visit_email_copy()` FELL BACK TO, NOT ABOUT THE KEY IT WAS
	 *     ASKED ABOUT. That resolver returns the `_default` set for any slug it
	 *     does not carry — correct for RENDERING, because a real order must
	 *     still get an email — but catastrophic for an APPROVAL question, which
	 *     is about a named set and nothing else.
	 *
	 * ⚠ IT WAS INVISIBLE UNTIL SEAL 982, AND ONLY BY LUCK. While `_default`
	 *   itself was unapproved, the fallback happened to return false for an
	 *   unknown key and the gate looked like it worked. Seal 982 approved
	 *   `_default`, and the same code then reported EVERY unknown slug on this
	 *   site as approved copy. `test-cycle179-review-seq` §9.8 caught it on
	 *   staging at 1.19.365 with the key `no-such-set-ever-2026`.
	 *
	 * ⭐ THE FIX NAMES THE FALLBACK RATHER THAN BANNING IT. An unknown slug
	 *    that resolved to the default set was NOT answered; it was
	 *    substituted for, so the honest answer is "no". ⛔ The filter seam
	 *    survives: a `bhp_visit_email_copy` filter that supplies real strings
	 *    for a slug this theme does not carry returns something OTHER than the
	 *    default set, and that set's own `approved` flag then governs, exactly
	 *    as it did before.
	 */
	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.19.369 · THE 1.19.366 GUARD IS NARROWED, AND IT IS NARROWED BY A
	 *     DESIGN CHANGE, NOT BY A REGRESSION. READ THIS BEFORE RESTORING IT.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE SUPERSEDED GUARD, PRESERVED VERBATIM:
	 *
	 *      if ( '' !== $slug
	 *          && ! isset( $sets[ $slug ] )
	 *          && $set === $sets[ BHP_VISIT_EMAIL_DEFAULT_KEY ] ) {
	 *          return false;
	 *      }
	 *
	 * ⭐ WHAT IT WAS FOR, and the reasoning is still correct: while the sets
	 *    array was keyed by slug, an unknown slug was SUBSTITUTED FOR rather
	 *    than answered, so reporting the substitute's approval as the answer
	 *    was a lie. `test-cycle179-review-seq` §9.8 caught it.
	 *
	 * ⛔ WHY IT CANNOT SURVIVE UNCHANGED. Under seal 994 there is exactly ONE
	 *    day-0 set and it is the set for EVERY visit slug by decision. Every
	 *    real slug is therefore "not in `$sets`", and the old guard would
	 *    return false for all of them — which `bhp_visit_email_may_render()`
	 *    turns into a hard stop, silently disabling the day-0 email for every
	 *    parent. ⚠ That is a worse failure than the one the guard prevented.
	 *
	 * ⭐ WHAT REPLACES IT, KEEPING THE HONESTY AND DROPPING THE SUBSTITUTION.
	 *    There is no substitution left to catch, so the two things that ARE
	 *    still checkable are checked:
	 *      1. NO SLUG AT ALL is not a visit order and gets no day-0 overlay.
	 *      2. A `bhp_visit_email_copy` FILTER that swapped the set out is
	 *         answered on ITS OWN `approved` flag, which is the case the seam
	 *         exists for — so an unapproved filtered set still renders nothing.
	 */
	if ( '' === $slug ) {
		return false;
	}

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

		/*
		 * ⭐⭐ 1.19.369 · {SchoolName} COMES OFF THE ORDER FIRST. Round-8 brief
		 *     item 2: *"{SchoolName} from `_bhp_school_visit_school`"*. The
		 *     bundle plugin writes that meta at checkout
		 *     (`BHP_SCHOOL_PICKUP_META_SCHOOL`), which makes it a fact about
		 *     THIS ORDER rather than a fact about a registry row that may since
		 *     have been renamed, re-keyed or removed.
		 *
		 * ⛔ AND A MISSING SCHOOL IS STILL A HARD STOP, not a blank. See
		 *    `bhp_visit_email_merge_is_complete()`: an email reading *"the
		 *    reason I drove out to ."* must never reach a parent.
		 */
		if ( defined( 'BHP_SCHOOL_PICKUP_META_SCHOOL' ) ) {
			$school = trim( (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_SCHOOL ) );
		}

		if ( '' === $school ) {
			$school = trim( (string) $order->get_meta( '_bhp_school_visit_school' ) );
		}

		$slug = bhp_visit_email_order_slug( $order );

		if ( '' !== $slug && function_exists( 'bhp_school_visit_records' ) ) {
			$records = bhp_school_visit_records();

			if ( is_array( $records ) && isset( $records[ $slug ] ) && is_array( $records[ $slug ] ) ) {
				$record = $records[ $slug ];

				// ⚠ THE REGISTRY IS THE FALLBACK NOW, not the source of record.
				if ( '' === $school ) {
					$school = isset( $record['school'] ) ? trim( (string) $record['school'] ) : '';
				}

				/*
				 * ⛔⛔ {VisitLine} IS STILL REGISTRY-ONLY AND STILL NEVER
				 *     AUTO-FILLED. Round-8 brief item 2: *"{VisitLine} from the
				 *     registry entry if present else omitted (never blank
				 *     line)"* — which is exactly what this already did, and the
				 *     body reader drops the paragraph entirely when it is ''.
				 *     Andrew's own line for THIS visit, or nothing at all.
				 */
				$visit = isset( $record['visit_line'] ) ? trim( (string) $record['visit_line'] ) : '';
			}
		}

		/*
		 * ⭐ A NATURAL LIST, NOT A COMMA-SEPARATED DUMP. Merry's slot table:
		 *    *"One title verbatim, or a natural list for two or more: The
		 *    Mariana Trench and Mount Everest"*.
		 */
		/*
		 * ⭐⭐ 1.19.375 · EXTRACTED, NOT REWRITTEN. This join was the ONLY
		 *     implementation of Merry's list rule until seal 1032 required the
		 *     review-ask lane to produce the same list. Rather than write a
		 *     second one — two copies of a join drift, and the day-0 email and
		 *     the +7 email listing the same order's books differently is the
		 *     exact failure a parent would notice — the body moved to
		 *     `bhp_review_ask_book_title_list()` and this call site now reads
		 *     it. ⚠ THE OUTPUT IS BYTE-IDENTICAL: the extracted function is
		 *     the same loop, the same `implode( ', ' )`, the same trailing
		 *     " and ", including the absent serial comma.
		 *
		 * ⛔ THE function_exists GUARD IS UNCHANGED IN KIND. It previously
		 *    guarded the two functions this file borrows from
		 *    `inc/review-ask-email.php` (loaded AFTER this file, so the guard
		 *    is about call-time availability, not load order); it now guards
		 *    the one function that wraps them. A missing helper still yields
		 *    '' and `bhp_visit_email_merge_is_complete()` still HARD STOPS the
		 *    send rather than mailing a blank.
		 */
		if ( function_exists( 'bhp_review_ask_book_title_list' ) ) {
			$titles = bhp_review_ask_book_title_list( $order );
		}
	}

	/*
	 * ⭐⭐ 1.19.376 · THE ONE SENTENCE IN THIS EMAIL THAT PUTS A VERB AND TWO
	 *     PRONOUNS NEXT TO THE BOOK LIST. Both forms are here, whole and
	 *     greppable, exactly the way `bhp_review_ask_book_verb()` takes them
	 *     everywhere else in this cycle.
	 *
	 * ⛔ THE SINGULAR IS THE 1.19.375 STRING, BYTE FOR BYTE, INCLUDING ITS
	 *    FULL STOP. Diff it against the 1.19.375 body array before changing a
	 *    character of it: it is approved founder copy.
	 *
	 * ⚠ THE PLURAL IS A GRAMMAR-ONLY ADJUSTMENT AWAITING ANDREW'S
	 *   CONFIRMATION — `is`->`are`, `it is`->`they are`, `it`->`they`. No
	 *   word is added and none is removed.
	 */
	$why_one  = __( 'I want to tell you why {BookTitle(s)} is built the way it is, because it is built for one particular kid.', 'brave-hearts' );
	$why_many = __( 'I want to tell you why {BookTitle(s)} are built the way they are, because they are built for one particular kid.', 'brave-hearts' );

	/*
	 * ⛔ THE SINGULAR IS THE FALLBACK, NOT THE PLURAL. If
	 *    `inc/review-ask-email.php` has not loaded, this renders exactly what
	 *    1.19.375 rendered rather than guessing at a count it cannot read.
	 */
	$why = function_exists( 'bhp_review_ask_book_verb' )
		? (string) bhp_review_ask_book_verb( $order, $why_one, $why_many )
		: $why_one;

	/*
	 * ⛔ THE TITLE IS SUBSTITUTED HERE, NOT LEFT TO THE OUTER `str_replace()`.
	 *    `{WhyBuiltLine}` CONTAINS `{BookTitle(s)}`, and `str_replace()` walks
	 *    its arrays in order — a later slot cannot fill a token that an
	 *    earlier substitution introduced. Resolving it here makes the order of
	 *    the array below irrelevant.
	 *
	 * ⛔ AND AN ORDER WITH NO RESOLVABLE TITLE YIELDS '', which
	 *    `bhp_visit_email_merge_is_complete()` reads as a HARD STOP. That is
	 *    the same protection `{BookTitle(s)}` gave this body before 1.19.376
	 *    moved the token out of it.
	 */
	$why = ( '' === trim( (string) $titles ) )
		? ''
		: str_replace( '{BookTitle(s)}', $titles, $why );

	return array(
		'{ParentFirstName}' => '' !== $parent ? $parent : __( 'there', 'brave-hearts' ),
		'{SchoolName}'      => $school,
		'{BookTitle(s)}'    => $titles,
		'{WhyBuiltLine}'    => $why,
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

	/*
	 * ⛔ 1.19.376 ADDS `{WhyBuiltLine}` TO THIS LIST AND THE ADDITION IS THE
	 *    WHOLE OF THE SAFETY. That slot replaced a body paragraph that used to
	 *    carry `{BookTitle(s)}` literally; without this line a title-less order
	 *    would stop failing the check and would send.
	 */
	foreach ( array( '{SchoolName}', '{BookTitle(s)}', '{WhyBuiltLine}' ) as $slot ) {
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

	/*
	 * ⛔⛔ 1.19.366 · THE SAME DEFECT LIVED HERE, ON THE PATH THAT REACHES
	 *     PARENTS. This read `$set['approved']` off the RESOLVED set, so an
	 *     unknown slug inherited the `_default` set's approval the moment seal
	 *     982 granted it. The approval question now goes through the one
	 *     function that answers it honestly. ⚠ `bhp_visit_email_copy()` is
	 *     still what supplies the STRINGS below; only the gate moved.
	 */
	if ( ! bhp_visit_email_copy_is_approved( $slug ) ) {
		return false;
	}

	$set = bhp_visit_email_copy( $slug );

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

/* -------------------------------------------------------------------------
 * ⭐⭐ 1.19.374 · SEAL 1025 · THE HAND-DELIVERY ROW IN THE DAY-0 ORDER SUMMARY
 * ---------------------------------------------------------------------- */

/**
 * Shorten and left-align the hand-delivery shipping row, day-0 email only.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT WAS OBSERVED, IN A RENDER, NOT REASONED ABOUT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `REVIEW-SEQ-STAGING\rs373-day0.html` line 152, read byte-for-byte at this
 * desk 2026-09-05. The totals table's shipping cell holds, right-aligned, in a
 * column roughly 200px wide:
 *
 *   Collection from <strong>Author hand-delivery at the Dallas Harris
 *   Elementary visit (September 3)</strong>:<br>Andrew brings the signed books
 *   to Dallas Harris Elementary on Thursday, September 3. Nothing is posted to
 *   your home, and there is no shipping charge. Visit time: 10:10 AM.
 *
 * That is forty-odd words ragged over four lines against the right edge, and
 * the row's own `<th>` already says *"Hand delivery: Author hand-delivery at
 * the Dallas Harris Elementary visit (September 3)"*. ⛔ THE PICKUP NAME IS
 * PRINTED TWICE AND THE SENTENCE UNDERNEATH IS THE CHECKOUT'S EXPLANATION,
 * which the day-0 email's own approved body has already given in full
 * paragraphs above the table. Legolas's render note, founder seal 1025.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS DELIBERATELY DOES NOT DO, AND WHY. READ BEFORE CHANGING IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT MINTS NO NEW CUSTOMER-FACING COPY. The round-13 brief offered the
 *    literal *"Hand delivered at the school visit"* OR *"the shortest truthful
 *    label the shipping method exposes"*. The second was taken. The string
 *    this renders is `bhp_school_pickup_label()`'s own output, marked in
 *    `plugins\brave-hearts-bundle-pricing\includes\school-visit-pickup.php`
 *    as *"APPROVED COPY, CARRIED FORWARD BYTE-IDENTICAL FROM 1.8.49. A label a
 *    parent reads while paying is not something a mechanism change gets to
 *    reword on its own initiative."* Standing Rules §9: approved copy is
 *    locked; propose changes, do not make them. ⭐ The alternative literal is
 *    one filter callback away if Andrew prefers it. See the filter below.
 *
 * ⛔ IT TOUCHES NO PLUGIN FILE AND NO WOOCOMMERCE SETTING. No shipping method,
 *    zone, rate, title or pickup location is created or edited. This is a
 *    render-time override of one table cell in one email.
 *
 * ⛔ IT CANNOT REACH AN ORDINARY ORDER EMAIL. The callback is added
 *    immediately before `woocommerce_email_order_details` in
 *    `woocommerce/emails/customer-completed-order.php` and removed
 *    immediately after, and only on the visit fork (`$bhp_visit_body`
 *    non-empty). A web receipt never has it attached, and its shipping row is
 *    byte-identical to 1.19.373.
 *
 * ⛔ IT REWRITES NOTHING BUT THE HAND-DELIVERY ROW. The guard is the row's own
 *    label starting with the plugin's shared phrase "Hand delivery". A posted,
 *    charged order says "Shipping:" and falls straight through untouched,
 *    which also means that if the bundle plugin is inactive, this is a no-op.
 *
 * ⛔ NO PRICE, NO TAX, NO TOTAL AND NO ROW ORDER IS TOUCHED. Only the shipping
 *    row's `value`, and only its presentation.
 *
 * ⚠ NOT VERIFIED: no render was produced from this build and there is no PHP
 *   on this machine. The failure was observed in the 1.19.373 render; the fix
 *   is stated as designed behaviour until Gandalf re-renders on staging.
 *
 * @since 1.19.374
 * @param array    $total_rows Rows from `WC_Order::get_order_item_totals()`.
 * @param mixed    $order      Order.
 * @return array
 */
function bhp_visit_email_shorten_pickup_row( $total_rows, $order = null ) {
	if ( ! is_array( $total_rows ) || ! isset( $total_rows['shipping'] ) || ! is_array( $total_rows['shipping'] ) ) {
		return $total_rows;
	}

	$row   = $total_rows['shipping'];
	$label = isset( $row['label'] ) ? (string) $row['label'] : '';
	$value = isset( $row['value'] ) ? (string) $row['value'] : '';

	/*
	 * ⛔ THE GUARD. `bhp_school_pickup_order_totals_label()` in the bundle
	 *    plugin rewrites this row's label to "Hand delivery:" for a pickup
	 *    order and leaves "Shipping:" alone for every other order. Testing the
	 *    label rather than the order means a posted order can never be
	 *    relabelled by this function even if it somehow reached it.
	 */
	if ( 0 !== stripos( $label, 'Hand delivery' ) ) {
		return $total_rows;
	}

	$short = bhp_visit_email_pickup_short_label( $value );

	if ( '' === $short ) {
		return $total_rows;
	}

	/**
	 * Filter the shortened hand-delivery label used in the day-0 order summary.
	 *
	 * ⭐ THIS IS THE ONE-LINE SEAM. Returning
	 *    `'Hand delivered at the school visit'` here gives the round-13
	 *    brief's first-choice literal without a build. It is not the default
	 *    because it would be new customer-facing copy and that is Andrew's.
	 *
	 * @since 1.19.374
	 * @param string $short The approved pickup label, tags stripped.
	 * @param mixed  $order Order.
	 */
	$short = (string) apply_filters( 'bhp_visit_email_pickup_short_label', $short, $order );

	if ( '' === trim( $short ) ) {
		return $total_rows;
	}

	/*
	 * ⛔ THE LEFT-ALIGN IS A WRAPPER, NOT A CLASS CHANGE. WooCommerce's
	 *    `emails/email-order-details.php` hard-codes `class="td
	 *    text-align-right"` with an inline `text-align: right` on this cell,
	 *    and a filter on the totals rows cannot reach either. A block-level
	 *    element carrying its own `text-align:left` inside the cell can, and
	 *    survives Emogrifier and `wp_kses_post()` intact.
	 *
	 * ⚠ Outlook desktop honours the inline style on the div, so this
	 *   left-aligns there too. Nothing here depends on `@media`.
	 */
	$total_rows['shipping']['value'] = '<div style="text-align:left;">' . esc_html( trim( $short ) ) . '</div>';

	/*
	 * ⛔ THE `<th>` IS TRIMMED BACK TO THE PHRASE ITSELF. It arrives as
	 *    "Hand delivery: Author hand-delivery at the ... visit (September 3)",
	 *    which prints the pickup name a second time. Cutting at the first
	 *    colon leaves the plugin's own label and duplicates nothing.
	 */
	/*
	 * ⭐ 1.19.376. THE PHRASE IS READ FROM THE PLUGIN THAT OWNS IT rather than
	 *    carved out of the incoming label, so a label that arrives WITHOUT a
	 *    colon — "Hand delivery Author hand-delivery at ..." — is trimmed too.
	 *    `bhp_school_pickup_totals_label()` is documented in
	 *    `school-visit-pickup.php` as the single source of these words for all
	 *    four surfaces, and the order surface appends the colon itself. ⛔ NO
	 *    NEW COPY: this renders the same two words 1.19.374 rendered.
	 */
	if ( function_exists( 'bhp_school_pickup_totals_label' ) ) {
		$total_rows['shipping']['label'] = bhp_school_pickup_totals_label() . ':';

		return $total_rows;
	}

	/*
	 * ⛔ THE FALLBACK IS 1.19.374 BYTE-FOR-BYTE, for the case where the bundle
	 *    plugin is not loaded. Cutting at the first colon leaves the label the
	 *    plugin would have written and duplicates nothing.
	 */
	$colon = strpos( $label, ':' );

	if ( false !== $colon ) {
		$total_rows['shipping']['label'] = substr( $label, 0, $colon + 1 );
	}

	return $total_rows;
}

/**
 * The shortest truthful label inside a rendered pickup shipping value.
 *
 * ⭐ WooCommerce Blocks renders a local-pickup shipping value as
 *    `Collection from <strong>NAME</strong>:<br>DETAILS`. NAME is
 *    `bhp_school_pickup_label()`'s approved string; DETAILS is the checkout
 *    explanation. This returns NAME.
 *
 * ⛔ TWO FALLBACKS, BOTH CONSERVATIVE. With no `<strong>`, everything after
 *    the first `<br>` is dropped and the remainder is stripped of tags. With
 *    nothing usable left, an empty string is returned and the caller leaves
 *    the row exactly as WooCommerce built it. ⛔ NOTHING IS EVER INVENTED to
 *    fill an empty result.
 *
 * @since 1.19.374
 * @param string $value Rendered shipping value.
 * @return string Plain text, or '' when nothing usable was found.
 */
function bhp_visit_email_pickup_short_label( $value ) {
	$value = (string) $value;

	if ( '' === trim( $value ) ) {
		return '';
	}

	if ( preg_match( '#<strong[^>]*>(.*?)</strong>#is', $value, $m ) ) {
		return trim( wp_strip_all_tags( $m[1] ) );
	}

	$first = preg_split( '#<br\s*/?>#i', $value );
	$first = ( is_array( $first ) && isset( $first[0] ) ) ? $first[0] : $value;

	return trim( wp_strip_all_tags( $first ) );
}
