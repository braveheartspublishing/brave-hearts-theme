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
				__( 'If they read the books and like them, there is a small thank you page with a QR code in the back. It goes to Amazon reviews. If you could write a review on the book/s it will help other early readers learn the lessons your little human got today.', 'brave-hearts' ),
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
		'_default'         => array(
			'approved'  => false,
			'subject'   => __( 'The signed books are with the kiddos!', 'brave-hearts' ),
			'heading'   => __( 'The signed books are with the kiddos!', 'brave-hearts' ),
			'preheader' => __( 'Signed Books, Delivered, and ready to read.', 'brave-hearts' ),
			'body'      => array(
				__( 'What an awesome group of kiddos! Thank you for letting me come and read with them.', 'brave-hearts' ),
				__( 'Your signed books went home with your child today (or children, for a few families).', 'brave-hearts' ),
				__( 'I also wanted to reach out and genuinely say thank you for raising such an awesome kiddo.', 'brave-hearts' ),
				__( 'If they read the books and like them, there is a small thank you page with a QR code in the back. It goes to Amazon reviews. If you could write a review on the book/s it will help other early readers learn the lessons your little human got today.', 'brave-hearts' ),
				__( 'Feel free to email me any time at Andrew@braveheartspublishing.com, once again thank you!', 'brave-hearts' ),
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

	$set = bhp_visit_email_copy( $slug );

	return ( isset( $set[ $key ] ) && is_string( $set[ $key ] ) ) ? $set[ $key ] : '';
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

	$set = bhp_visit_email_copy( $slug );

	return ( isset( $set['body'] ) && is_array( $set['body'] ) ) ? $set['body'] : array();
}
