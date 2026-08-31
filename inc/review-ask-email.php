<?php
/**
 * THE STORE-SENT REVIEW-ASK ENGINE (E-REVIEW).
 * Theme 1.19.317. Workstream `CYCLE169-LD-REVIEW-ASK-ENGINE`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ WHY THE STORE SENDS THIS AND NOT MAILCHIMP
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-29, carrier item 397, verbatim (⛔ RELAYED through
 * the Chief of Staff; NOT witnessed first-hand by the desk that wrote this
 * file, and recorded that way deliberately):
 *
 *   "I do want anyone who has bought to get the review ask for the books
 *    thats huge for us- second priority and new KPI other than emails and
 *    sales"
 *
 * and item 391, ruling 2, in his own words: *"lets do it for all buys not
 * just subscribers."*
 *
 * ⛔ MAILCHIMP JOURNEY 94 CANNOT DELIVER THAT. Its entry filter is
 *    `Email subscription status is one of Subscribed`, and store-synced
 *    buyers arrive as TRANSACTIONAL contacts. On 2026-08-28 Gimli's live read
 *    recorded 23 contacts tagged `Customer - Purchased` and 3 started in
 *    journey 94. ⭐ "Anyone who has bought" is therefore a promise only the
 *    STORE can keep, because the store is the only system that knows every
 *    buyer.
 *
 * ⭐ SO THIS IS THE ENGINE OF RECORD. Journey 94 continues to run for the
 *    small subscribed cohort; §"DOUBLE-ASK" below is how the two are kept
 *    from hitting the same person.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT CLASS OF EMAIL THIS IS, STATED HONESTLY RATHER THAN ASSUMED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A post-purchase "how did it go, and would you review it" message to a
 * person who bought from this store is a RELATIONSHIP email in ordinary
 * retail practice. ⚠ It is NOT unambiguously a CAN-SPAM "transactional or
 * relationship message" under the statute's narrowly enumerated categories,
 * and Merry's build spec §2.2 says so plainly rather than pretending
 * otherwise. That analysis is preserved, not overridden.
 *
 * ⭐ THE ENGINEERING ANSWER TO AN UNRESOLVED LEGAL QUESTION IS TO SATISFY THE
 *    STRICTER STANDARD ANYWAY, so the classification stops mattering:
 *
 *    1. A PHYSICAL POSTAL ADDRESS in the footer, and ⛔ the engine REFUSES TO
 *       SEND if it cannot resolve one. See `bhp_review_ask_postal_address()`.
 *    2. A WORKING, NO-LOGIN OPT-OUT, honoured immediately and permanently,
 *       plus `List-Unsubscribe` / `List-Unsubscribe-Post` headers so Gmail and
 *       Yahoo render their own native unsubscribe control.
 *    3. Clear identification of the sender, which the store's own email footer
 *       already carries.
 *    4. ONE email. There is no reminder, no sequence and no second ask.
 *
 * ⛔ NO COUPON, NO DISCOUNT, NO PRICE, NO SHIPPING FIGURE, NO UPSELL, NO
 *    PRODUCT BLOCK, NO LEAD MAGNET. That is not tidiness: a number that is not
 *    in the email cannot go stale, and conflict `C-B` (Collection shipping
 *    $3.99/$4.99 versus $0.00) is still OPEN and unresolved. Merry's spec §3
 *    removed every figure for exactly this reason and the removal is load-
 *    bearing. ⛔ Do not add one back.
 *
 * ⛔ NO REVIEW COUNT, RATING, PARENT REACTION OR CLASSROOM RESULT APPEARS
 *    ANYWHERE IN THIS FEATURE. Standing Rules §3.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE DOUBLE-ASK PROBLEM, AND THE FOUR PLACES IT IS CLOSED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Three separate systems can ask the same buyer for a review. Every one of
 * them is handled, and the one that is NOT handled here is named rather than
 * hidden:
 *
 * 1. ⭐ THE SCHOOL-VISIT COMPLETED EMAIL ALREADY CARRIES A REVIEW ASK.
 *    `inc/visit-completed-email.php` body paragraph four, verbatim: *"there is
 *    a small thank you page with a QR code in the back. It goes to Amazon
 *    reviews."* All eight Adams orders (614, 622, 623, 625, 626, 629, 630,
 *    633) received it on 2026-08-28.
 *    ➡ CLOSED STRUCTURALLY: `bhp_review_ask_is_visit_order()` excludes ANY
 *      order carrying `_bhp_school_visit_slug`, forever, automatically.
 *      ⭐ THIS IS DELIBERATELY STRONGER THAN THE BRIEF ASKED FOR. The brief
 *      asked for the eight ids to be seeded with a dedup marker at deploy. A
 *      seeding step has to be remembered; a structural exclusion does not, and
 *      it also covers Dallas Harris and every future visit nobody has run yet.
 *      The seeding step is STILL in the deploy plan as belt and braces, so the
 *      order record says why it was skipped.
 *
 * 2. ⭐ MAILCHIMP JOURNEY 94 has up to four subscribed buyers parked in it,
 *    overdue at the 21-day delay.
 *    ➡ CLOSED BY AN EXCLUSION LIST: `bhp_review_ask_excluded_emails()`.
 *      ⛔ IT SHIPS EMPTY AND IT IS EMPTY-SAFE. Their identities are knowable
 *      only from Mailchimp, so the list is a seam Gandalf/Gimli fill with the
 *      four billing addresses BEFORE the production deploy. An empty list is
 *      not a bug in the code; it is an unfinished step in the deploy plan, and
 *      the deploy plan says so.
 *
 * 3. ⛔⛔ THE `customer-reviews-woocommerce` (CusRev) PLUGIN IS ENABLED ON
 *    PRODUCTION AND IS ALREADY SCHEDULED TO EMAIL THE SAME EIGHT ADAMS
 *    PARENTS. VERIFIED LIVE, READ-ONLY, ON PRODUCTION 2026-08-29:
 *    `ivole_enable = yes`, `ivole_order_status = wc-completed`,
 *    `ivole_delay = 5 days`, `ivole_enable_for_guests = yes`, and
 *    `wp cron event list` shows EIGHT `ivole_send_reminder` events due
 *    2026-09-03 00:15:03 to 00:15:16 UTC — second for second the eight Adams
 *    completion timestamps plus five days.
 *    ⛔ NOT CLOSED BY THIS FILE, AND IT CANNOT BE. Disabling a plugin setting
 *      is a WooCommerce/plugin configuration mutation and an Andrew gate
 *      (Standing Rules §6). It is escalated in the workstream report as the
 *      first item, ahead of this build. ⚠ Nothing in this file schedules,
 *      cancels, reads or changes an `ivole_*` option or an `ivole_send_reminder`
 *      event.
 *
 * 4. ⭐ A REPEAT BUYER. One ask per ORDER is not enough on its own: a customer
 *    with three orders would get three emails.
 *    ➡ CLOSED BY THE 90-DAY CUSTOMER GATE, keyed on the billing email. See
 *      `bhp_review_ask_customer_last()`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   - It writes NO WooCommerce setting and NO product, variation, price,
 *     coupon, stock, shipping, tax, payment or checkout record, on any
 *     environment. Registering an email CLASS is not writing a setting:
 *     `WC_Email::init_settings()` only reads.
 *   - It changes NO other email's subject, heading, content, recipient or
 *     enabled state.
 *   - It touches NO funnel storage key and NO funnel analytics prefix, so the
 *     parent/teacher isolation rule is untouched.
 *   - It sends NOTHING on staging. `inc/staging-mail-guard.php` lists
 *     `bhp_review_ask` by id, and the suite asserts that it still does.
 *   - It never sends for a refunded, partially refunded, cancelled or failed
 *     order, and never for anything that is not a `shop_order`.
 *
 * ⚠ THE `shop_order` TYPE GUARD IS NOT DEFENSIVE PADDING. Measured on staging
 *   2026-08-29: `wc_get_orders( array( 'status' => array( 'completed' ) ) )`
 *   with no `type` returns **1** result and it is an
 *   `Automattic\WooCommerce\Admin\Overrides\OrderRefund` — a REFUND carries
 *   status `completed`. With `'type' => 'shop_order'` the same query returns
 *   0. A query without the type key would have tried to email a refund object,
 *   which has no `get_billing_email()` at all.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * KEYS, CONSTANTS AND KNOBS
 * ====================================================================== */

/**
 * Order meta: this order's review ask has been dealt with.
 *
 * The VALUE is meaningful and is deliberately not a bare `1`:
 *   - a MySQL datetime  = this engine sent it, then.
 *   - `external-<date>` = somebody was asked outside this engine (the visit
 *                         email, a Mailchimp journey, a hand-sent note) and the
 *                         order was seeded at deploy so the engine never asks
 *                         again. The deploy plan writes exactly this form.
 * Anything non-empty suppresses. The engine never parses the value to decide.
 */
if ( ! defined( 'BHP_REVIEW_ASK_SENT_META' ) ) {
	define( 'BHP_REVIEW_ASK_SENT_META', '_bhp_review_ask_sent' );
}

/**
 * Order/user meta: when this CUSTOMER was last asked, by billing email.
 *
 * ⚠ THE ORDER AND USER META ARE TRACEABILITY, NOT THE GATE. Most buyers here
 *   are guests with no user account, and the next order does not know what the
 *   previous order was told. The authoritative record is the registry option
 *   `bhp_review_ask_customer_last`, keyed by a HASH of the lowercased billing
 *   email. See `bhp_review_ask_customer_last()`.
 */
if ( ! defined( 'BHP_REVIEW_ASK_CUSTOMER_LAST_META' ) ) {
	define( 'BHP_REVIEW_ASK_CUSTOMER_LAST_META', '_bhp_review_ask_customer_last' );
}

/** Order/user meta: this customer opted out. Same registry reasoning as above. */
if ( ! defined( 'BHP_REVIEW_ASK_OPTOUT_META' ) ) {
	define( 'BHP_REVIEW_ASK_OPTOUT_META', '_bhp_review_ask_optout' );
}

/** The WooCommerce email id. One place, never re-typed as a literal. */
if ( ! defined( 'BHP_REVIEW_ASK_EMAIL_ID' ) ) {
	define( 'BHP_REVIEW_ASK_EMAIL_ID', 'bhp_review_ask' );
}

/** Registry options. Small, bounded, and autoloaded off. */
if ( ! defined( 'BHP_REVIEW_ASK_OPTOUT_OPTION' ) ) {
	define( 'BHP_REVIEW_ASK_OPTOUT_OPTION', 'bhp_review_ask_optouts' );
}
if ( ! defined( 'BHP_REVIEW_ASK_CUSTOMER_OPTION' ) ) {
	define( 'BHP_REVIEW_ASK_CUSTOMER_OPTION', 'bhp_review_ask_customer_last' );
}
if ( ! defined( 'BHP_REVIEW_ASK_LOG_OPTION' ) ) {
	define( 'BHP_REVIEW_ASK_LOG_OPTION', 'bhp_review_ask_log' );
}
if ( ! defined( 'BHP_REVIEW_ASK_STATS_OPTION' ) ) {
	define( 'BHP_REVIEW_ASK_STATS_OPTION', 'bhp_review_ask_stats' );
}
if ( ! defined( 'BHP_REVIEW_ASK_EXCLUDE_OPTION' ) ) {
	define( 'BHP_REVIEW_ASK_EXCLUDE_OPTION', 'bhp_review_ask_excluded_emails' );
}

/** The daily send cap. See `bhp_review_ask_daily_cap()` for why it exists. */
if ( ! defined( 'BHP_REVIEW_ASK_DEFAULT_DAILY_CAP' ) ) {
	define( 'BHP_REVIEW_ASK_DEFAULT_DAILY_CAP', 5 );
}

/** Days between a customer's ask and the next one they may receive. */
if ( ! defined( 'BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS' ) ) {
	define( 'BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS', 90 );
}

/** The daily cron/Action Scheduler hook. */
if ( ! defined( 'BHP_REVIEW_ASK_CRON_HOOK' ) ) {
	define( 'BHP_REVIEW_ASK_CRON_HOOK', 'bhp_review_ask_daily' );
}

/** The public query var the opt-out link carries. */
if ( ! defined( 'BHP_REVIEW_ASK_OPTOUT_QUERY' ) ) {
	define( 'BHP_REVIEW_ASK_OPTOUT_QUERY', 'bhp_review_optout' );
}

/**
 * How long after completion the ask goes out.
 *
 * ⛔⛔ CHANGING THIS ALONE MAKES THE APPROVED COPY UNTRUE, AND THE ENGINE
 *     REFUSES TO SEND RATHER THAN LIE. The first sentence of the approved
 *     email reads *"Your book turned up about three weeks ago"*. Merry's spec
 *     §3 claim check states the wiring explicitly: *"Only true if the delay is
 *     21 days. If you keep 35 days, this line must read 'about five weeks
 *     ago.' They are wired together."*
 *
 * ⭐ SO THE COPY CARRIES ITS OWN `delay_days`, and `bhp_review_ask_run()`
 *    HALTS with `copy_delay_mismatch` when the two disagree. A filter that
 *    moves the delay must arrive with copy approved for that delay, supplied
 *    through `bhp_review_ask_copy`. ⛔ It fails LOUD and CLOSED: nothing sends
 *    and the run summary names the reason. It does not quietly send a false
 *    sentence, and it does not quietly send nothing.
 *
 * @return int Days.
 */
function bhp_review_ask_delay_days() {
	/**
	 * Filter the post-completion delay, in days.
	 *
	 * @since 1.19.317
	 * @param int $days Default 21 (founder ruling, carrier item 392, D-3).
	 */
	$days = (int) apply_filters( 'bhp_review_ask_delay_days', 21 );

	return $days > 0 ? $days : 21;
}

/**
 * The maximum number of asks this engine may send on one calendar day.
 *
 * ⭐ WHY A CAP EXISTS AT ALL, AND IT IS NOT HYPOTHETICAL. On the day this is
 *    switched on, EVERY past order that completed more than the delay ago
 *    qualifies at once. VERIFIED LIVE READ-ONLY ON PRODUCTION 2026-08-29 with
 *    `wp wc shop_order list --status=completed`: 14 completed orders exist, of
 *    which four (417, 493, 547, 548) completed on 2026-08-06/07 and are
 *    already past 21 days. Without a cap, a switch-on is a small blast.
 *
 * ⚠ A burst is not just impolite. A sudden cluster of near-identical mail from
 *   a domain that normally sends receipts is exactly what reputation filters
 *   are built to notice, and this store has one sending domain and a tiny
 *   list. Five a day drips fourteen orders out across three days.
 *
 * ⭐ THE CAP IS COUNTED FROM THE STATS LEDGER, NOT FROM A PER-RUN COUNTER, so
 *    two runs on the same day (Action Scheduler AND WP-Cron, a manual WP-CLI
 *    run, a retried queue) cannot together exceed it. That is what makes
 *    double-scheduling safe rather than merely unlikely.
 *
 * @return int
 */
function bhp_review_ask_daily_cap() {
	/**
	 * Filter the maximum review asks sent per calendar day.
	 *
	 * @since 1.19.317
	 * @param int $cap Default 5.
	 */
	$cap = (int) apply_filters( 'bhp_review_ask_daily_cap', BHP_REVIEW_ASK_DEFAULT_DAILY_CAP );

	return $cap > 0 ? $cap : BHP_REVIEW_ASK_DEFAULT_DAILY_CAP;
}

/**
 * Is the engine allowed to send at all?
 *
 * ⭐ THE MASTER SWITCH, AND IT DEFAULTS **OFF**. A file that begins emailing
 *    real customers the moment it is deployed is not a staging build, it is an
 *    incident. Switching it on is a deliberate act recorded in the deploy plan
 *    and gated on Andrew's word.
 *
 * ⛔ The option is READ here and is never written by this file.
 *
 * @return bool
 */
function bhp_review_ask_is_enabled() {
	$option = get_option( 'bhp_review_ask_enabled', 'no' );

	/**
	 * Filter whether the review-ask engine may send.
	 *
	 * @since 1.19.317
	 * @param bool $enabled Whether sending is enabled.
	 */
	return (bool) apply_filters( 'bhp_review_ask_enabled', ( 'yes' === $option ) );
}

/* =========================================================================
 * THE COPY — LOCKED. STANDING RULES §9. PROPOSE CHANGES; DO NOT MAKE THEM.
 * ====================================================================== */

/**
 * The approved review-ask copy.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ EVERY STRING BELOW IS FOUNDER-APPROVED COPY, TAKEN VERBATIM FROM
 *     `Business OS\ANDREW-REVIEW\2026-08-29\REVIEW-JOURNEY-BUILD-SPEC.md` §3,
 *     WHICH THIS DESK READ AT SOURCE RATHER THAN ACCEPTING FROM A BRIEF.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHERE THE WORDS COME FROM, recorded because the spec records it and a
 *    future reader must not "improve" a founder's own sentence:
 *      - *"How did they do reading it?"* — ANDREW'S, from his own interview
 *        answer, with the gender removed.
 *      - *"It will help other early readers find the book and learn the same
 *        lessons your little human did"* — ANDREW'S, near verbatim from
 *        carrier item 377.
 *      - *"Feel free to email me any time at Andrew@braveheartspublishing.com"*
 *        — ANDREW'S, verbatim, his Adams sign-off.
 *
 * ⚠ ONE LINE IS A PROMISE IN HIS NAME AND IS FLAGGED IN THE SPEC AS D-6:
 *   *"It comes to me and I read them."* The spec says, and this file repeats:
 *   ⛔ ship it only if he will actually do it. A broken promise to reply is
 *   worse than never inviting one.
 *
 * ⭐ THE GREETING IS A TRANSLATION, NOT A REWRITE. The spec renders it as
 *    Mailchimp's `*|IF:FNAME|*Hi *|FNAME|*,*|ELSE:|*Hi there,*|END:IF|*`
 *    because it was written for a journey. A merge tag has no meaning in a
 *    WooCommerce email, so the template performs the identical conditional
 *    against the order's billing first name and falls back to the spec's own
 *    `Hi there,`. ⛔ Store-synced buyers frequently have no first name; a bare
 *    merge would render "Hi ," to a real customer, which is the exact defect
 *    the spec's G-12 exists to prevent.
 *
 * ⛔ NO EM DASH (U+2014) ANYWHERE. Standing email rule. There are none, and
 *    there must go on being none. The suite asserts it.
 *
 * ⛔ THE VOICE IS "I", NOT "we". Standing Rules §9.1: Andrew is the sole
 *    operator and this is customer-facing copy. The suite asserts that too.
 *
 * Shape:
 *   'delay_days'      int      The delay this copy is TRUE at. See
 *                              `bhp_review_ask_delay_days()`.
 *   'approved'        bool     TRUE only where Andrew approved the strings.
 *   'subject'         string
 *   'preheader'       string
 *   'body_before'     string[] Paragraphs before the links.
 *   'question'        string   The one bolded ask.
 *   'links_lead'      string   The line that introduces the three links.
 *   'links'           array[]  Each: 'label', 'url'.
 *   'body_after'      string[] Paragraphs after the links.
 *   'signoff'         string[] Lines, in render order.
 *   'signoff_tagline' string
 *
 * @return array
 */
function bhp_review_ask_copy() {
	$copy = array(
		'delay_days'      => 21,
		'approved'        => true,

		'subject'         => __( 'How did they do reading it?', 'brave-hearts' ),
		'preheader'       => __( 'One honest sentence helps the next parent decide.', 'brave-hearts' ),

		/*
		 * ═══════════════════════════════════════════════════════════════════
		 * ⭐⭐ THE H1 IS DELIBERATELY EMPTY, AND THIS IS A PRESENTATION
		 *     DECISION AN ENGINEER MADE. IT IS FLAGGED FOR ANDREW, NOT HIDDEN.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * ⛔ THE APPROVED COPY HAS NO HEADING. Merry's spec §3 gives a subject,
		 *    a preheader and a body that opens straight on "Hi ...,". There is
		 *    no founder-approved H1 string for this email, and inventing one
		 *    would be writing copy into an email whose copy is locked.
		 *
		 * ⛔ AND FILLING THE H1 WITH THE SUBJECT WAS TRIED AND REJECTED, ON THE
		 *    RENDER, NOT ON THE ARGUMENT. Rendered on staging 2026-08-29 with
		 *    `heading = 'How did they do reading it?'`, the reader meets that
		 *    exact sentence THREE TIMES in the first screen: the subject line,
		 *    a 32px serif H1, and the bolded ask sixty pixels below it. ⭐ That
		 *    is `CYCLE142-CX-029` exactly — the defect that had E1 saying thank
		 *    you twice in the first forty pixels, which this store already
		 *    fixed once.
		 *
		 * ⭐ MEASURED, NOT ASSUMED: with an empty string, WooCommerce's
		 *    `emails/email-header.php` (line 113, `<h1><?php echo esc_html(
		 *    $email_heading ); ?></h1>`) collapses to nothing visible. There is
		 *    no blank band. Compared side by side on staging as
		 *    `review-ask.html` against `review-ask-noheading.html`.
		 *
		 * ⭐ THE ESCAPE HATCH IS ALREADY WIRED AND NEEDS NO DEPLOY: typing a
		 *    heading into WooCommerce -> Settings -> Emails -> "Review ask
		 *    (T+21 days)" overrides this, because `WC_Email::get_heading()`
		 *    prefers a stored admin value. Reverting is also one string here.
		 */
		'heading'         => '',

		'body_before'     => array(
			__( 'Your book turned up about three weeks ago, so I am going to ask the one question I actually care about and then get out of your inbox.', 'brave-hearts' ),
		),

		'question'        => __( 'How did they do reading it?', 'brave-hearts' ),

		'body_middle'     => array(
			__( 'Not did they love it. Genuinely, how did it go. Did they read it themselves? Did you read it to them? Did it sit on the shelf for a while and then get picked up on a rainy Tuesday? All of those are real answers and all of them are useful to me.', 'brave-hearts' ),
			__( 'If they read it and liked it, the thing that helps most is a review on Amazon. It will help other early readers find the book and learn the same lessons your little human did.', 'brave-hearts' ),
			// Founder correction, carrier item 407 (2026-08-29): do not invite a
			// negative review. Superseded line preserved in the carrier record.
			__( 'An honest review helps other kiddos learn from these books.', 'brave-hearts' ),
		),

		'links_lead'      => __( 'Find the one you read:', 'brave-hearts' ),

		/*
		 * ⭐ THREE NAMED DOORS, NOT A GUESS. Merry's spec §4 sets out why: a
		 *    WooCommerce order knows which book was bought, but the ask is the
		 *    same for every title and a reader disambiguates three names in a
		 *    quarter of a second. Option B (branch by product) and Option C (a
		 *    `/review/` router page, which returns 404 today) were both
		 *    considered and rejected there.
		 *
		 * ⛔ THE ASINs ARE NOT TYPED FROM MEMORY. They are the canonical values
		 *    in repo `docs\PROJECT_STATE.md` lines 347-349, checked against that
		 *    file on 2026-08-29. The three `create-review` URLs were fetched by
		 *    Merry's lane on 2026-08-29 and returned HTTP 200.
		 *
		 * ⚠ WHAT 200 DOES NOT PROVE, carried forward from the spec rather than
		 *   quietly dropped: it was a SIGNED-OUT fetch. It does not prove the
		 *   review composer opens for a signed-in Amazon customer, and a buyer
		 *   who bought here rather than on Amazon gets no Verified Purchase
		 *   badge. Spec item G-1 is the logged-in walk-through and it is still
		 *   NOT DONE.
		 */
		'links'           => array(
			array(
				'label' => __( 'The Mariana Trench', 'brave-hearts' ),
				'url'   => 'https://www.amazon.com/review/create-review?asin=B0GQCCPZLL',
			),
			array(
				'label' => __( 'Mount Everest', 'brave-hearts' ),
				'url'   => 'https://www.amazon.com/review/create-review?asin=B0GWJ4PNPZ',
			),
			array(
				'label' => __( 'The Amazon', 'brave-hearts' ),
				'url'   => 'https://www.amazon.com/review/create-review?asin=B0H6QLFSN4',
			),
		),

		'body_after'      => array(
			// ⚠ D-6. Ship only if he will actually reply. See this docblock.
			__( 'Feel free to email me any time at Andrew@braveheartspublishing.com. It comes to me and I read them.', 'brave-hearts' ),
			__( 'Thank you for taking a chance on a book by somebody you had never heard of.', 'brave-hearts' ),
		),

		'signoff'         => array(
			__( 'Andrew', 'brave-hearts' ),
			__( 'Brave Hearts Publishing', 'brave-hearts' ),
		),

		'signoff_tagline' => __( 'Big Places. Brave Hearts.', 'brave-hearts' ),

		/*
		 * ⭐ THE OPT-OUT SENTENCE AND THE ADDRESS LABEL ARE ENGINEERING COPY,
		 *    NOT ANDREW'S, AND THEY ARE MARKED AS SUCH. They exist because
		 *    CAN-SPAM compliance requires them; they are the only strings in
		 *    this email the founder did not write. They are deliberately plain.
		 */
		'optout_lead'     => __( 'If you would rather not get a message like this again,', 'brave-hearts' ),
		'optout_link'     => __( 'unsubscribe from review emails', 'brave-hearts' ),
		'optout_note'     => __( 'This does not affect your order emails or your receipts.', 'brave-hearts' ),
	);

	/**
	 * Filter the review-ask copy set.
	 *
	 * ⭐ THE SEAM FOR A DIFFERENT DELAY. Copy approved for a delay other than
	 *    21 days arrives here carrying its own `delay_days`, and the run gate
	 *    then agrees rather than halting. That is the only supported way to
	 *    move the delay.
	 *
	 * ⛔ A FILTER THAT RETURNS SOMETHING UNUSABLE IS DISCARDED, not trusted.
	 *
	 * @since 1.19.317
	 * @param array $copy The approved copy set.
	 */
	$filtered = apply_filters( 'bhp_review_ask_copy', $copy );

	return bhp_review_ask_copy_is_usable( $filtered ) ? $filtered : $copy;
}

/**
 * Is this array a complete, renderable copy set?
 *
 * @param mixed $copy Candidate.
 * @return bool
 */
function bhp_review_ask_copy_is_usable( $copy ) {
	if ( ! is_array( $copy ) ) {
		return false;
	}

	foreach ( array( 'subject', 'preheader', 'question', 'links_lead' ) as $key ) {
		if ( empty( $copy[ $key ] ) || ! is_string( $copy[ $key ] ) ) {
			return false;
		}
	}

	/*
	 * ⚠ `heading` IS CHECKED FOR PRESENCE AND TYPE, NOT FOR EMPTINESS. An empty
	 *   H1 is the intended state here — see the note on `heading` in
	 *   `bhp_review_ask_copy()`. Leaving it in the `empty()` loop above would
	 *   make the approved copy fail its own usability test and silently fall
	 *   back to itself.
	 */
	if ( ! isset( $copy['heading'] ) || ! is_string( $copy['heading'] ) ) {
		return false;
	}

	foreach ( array( 'body_before', 'body_middle', 'body_after', 'signoff', 'links' ) as $key ) {
		if ( empty( $copy[ $key ] ) || ! is_array( $copy[ $key ] ) ) {
			return false;
		}
	}

	foreach ( $copy['links'] as $link ) {
		if ( ! is_array( $link ) || empty( $link['label'] ) || empty( $link['url'] ) ) {
			return false;
		}
	}

	return ! empty( $copy['delay_days'] ) && is_numeric( $copy['delay_days'] );
}

/**
 * Does the approved copy describe the delay the engine is actually using?
 *
 * See `bhp_review_ask_delay_days()` for why a mismatch halts the run.
 *
 * @return bool
 */
function bhp_review_ask_copy_matches_delay() {
	$copy = bhp_review_ask_copy();

	return (int) $copy['delay_days'] === bhp_review_ask_delay_days();
}

/* =========================================================================
 * THE CAN-SPAM FOOTER — POSTAL ADDRESS
 * ====================================================================== */

/**
 * The physical postal address printed in this email's footer.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ NO ADDRESS IS INVENTED HERE, AND NONE IS HARD-CODED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Resolution order, first non-empty wins:
 *   1. `BHP_REVIEW_ASK_POSTAL_ADDRESS` constant, for a wp-config override.
 *   2. `bhp_review_ask_postal_address` option, for a wp-admin override.
 *   3. ⭐ WooCommerce's OWN configured store base address.
 *
 * ⭐ WHY (3) IS THE RIGHT DEFAULT AND IS NOT AN INVENTION. It is the address
 *    Andrew himself configured in WooCommerce, and it is ALREADY printed at the
 *    bottom of every order email this store sends: the stored
 *    `woocommerce_email_footer_text` is `{site_title}<br />{store_address}`.
 *    Verified read-only on production 2026-08-29:
 *    `woocommerce_store_address = 580 Hyde Ave`, `_city = Pocatello`,
 *    `_postcode = 83201`, `_default_country = US:ID`. Printing the same address
 *    on one more email is not a new disclosure.
 *
 * ⛔ IF ALL THREE ARE EMPTY THE ENGINE DOES NOT SEND. An empty string returned
 *    here is a hard decline in `bhp_review_ask_should_send()`. A commercial-
 *    class email with no postal address is the one CAN-SPAM failure that cannot
 *    be argued about, and a missing address must never be papered over with a
 *    plausible-looking one.
 *
 * @return string HTML-safe plain text, lines separated by ", ".
 */
function bhp_review_ask_postal_address() {
	if ( defined( 'BHP_REVIEW_ASK_POSTAL_ADDRESS' ) && '' !== trim( (string) BHP_REVIEW_ASK_POSTAL_ADDRESS ) ) {
		return trim( (string) BHP_REVIEW_ASK_POSTAL_ADDRESS );
	}

	$option = trim( (string) get_option( 'bhp_review_ask_postal_address', '' ) );
	if ( '' !== $option ) {
		return $option;
	}

	$address = '';

	if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) && is_object( WC()->countries ) ) {
		$countries = WC()->countries;

		if ( method_exists( $countries, 'get_base_address' ) ) {
			$parts = array(
				(string) $countries->get_base_address(),
				method_exists( $countries, 'get_base_address_2' ) ? (string) $countries->get_base_address_2() : '',
				method_exists( $countries, 'get_base_city' ) ? (string) $countries->get_base_city() : '',
				method_exists( $countries, 'get_base_state' ) ? (string) $countries->get_base_state() : '',
				method_exists( $countries, 'get_base_postcode' ) ? (string) $countries->get_base_postcode() : '',
			);

			$parts   = array_filter( array_map( 'trim', $parts ) );
			$address = implode( ', ', $parts );
		}
	}

	/**
	 * Filter the postal address printed in the review-ask footer.
	 *
	 * ⛔ Returning an empty string DISABLES SENDING. That is deliberate.
	 *
	 * @since 1.19.317
	 * @param string $address Resolved address.
	 */
	return trim( (string) apply_filters( 'bhp_review_ask_postal_address', $address ) );
}

/* =========================================================================
 * IDENTITY, OPT-OUT AND THE CUSTOMER GATE
 * ====================================================================== */

/**
 * The stable key for one customer, derived from a billing email.
 *
 * ⛔ HASHED, NOT STORED IN CLEAR. The opt-out registry and the KPI ledger are
 *    plain `wp_options` rows and would otherwise be a customer email list
 *    sitting in a table that gets dumped into every backup and every migration.
 *    Hashing costs nothing here because every lookup is by exact address.
 *
 * ⚠ It is salted with `wp_salt( 'nonce' )`, which is the same salt the rest of
 *   this theme uses for opaque keys (`inc/conversion-token.php`,
 *   `inc/early-cart-capture.php`). Rotating salts therefore invalidates the
 *   registry, which would re-enable a customer who had opted out. ⭐ THAT IS
 *   WHY THE OPT-OUT IS ALSO MIRRORED TO USER META AND ORDER META, which are
 *   salt-independent, and why `bhp_review_ask_is_opted_out()` checks all three.
 *
 * @param string $email Billing email.
 * @return string 40-char key, or '' for an unusable address.
 */
function bhp_review_ask_customer_key( $email ) {
	$email = strtolower( trim( (string) $email ) );

	if ( '' === $email || ! is_email( $email ) ) {
		return '';
	}

	return substr( hash_hmac( 'sha256', 'bhp_review_ask|' . $email, wp_salt( 'nonce' ) ), 0, 40 );
}

/**
 * The signed opt-out URL for one order.
 *
 * ⭐ NO PERSONAL DATA IN THE QUERY STRING. The link carries an ORDER ID and a
 *    signature; the address is looked up server-side from the order. An
 *    unsubscribe link that carries `?email=someone@example.com` leaks the
 *    address into referrer headers, proxy logs, analytics and anybody's shoulder
 *    surfing, and this codebase's own privacy rule forbids it.
 *
 * ⛔ THE SIGNATURE BINDS THE ORDER ID *AND* THE ADDRESS. Changing either
 *    invalidates it, so a guessed order id cannot unsubscribe a stranger, and a
 *    link cannot be replayed after the order's billing address is edited.
 *
 * @param WC_Order|mixed $order Order.
 * @return string URL, or '' when one cannot be built.
 */
function bhp_review_ask_optout_url( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	$order_id = (int) $order->get_id();
	$email    = strtolower( trim( (string) $order->get_billing_email() ) );

	if ( ! $order_id || '' === $email ) {
		return '';
	}

	return add_query_arg(
		array(
			BHP_REVIEW_ASK_OPTOUT_QUERY => $order_id,
			'bhpt'                      => bhp_review_ask_optout_signature( $order_id, $email ),
		),
		home_url( '/' )
	);
}

/**
 * The HMAC that authenticates one opt-out link.
 *
 * @param int    $order_id Order id.
 * @param string $email    Lowercased billing email.
 * @return string 32 hex chars.
 */
function bhp_review_ask_optout_signature( $order_id, $email ) {
	return substr(
		hash_hmac(
			'sha256',
			'bhp_review_optout|' . (int) $order_id . '|' . strtolower( trim( (string) $email ) ),
			wp_salt( 'nonce' )
		),
		0,
		32
	);
}

/**
 * Has this customer opted out of review asks?
 *
 * Three independent records are consulted, and any one of them suppresses:
 *   1. the hashed registry option (the primary, works for guests);
 *   2. user meta, when the address belongs to a WordPress user;
 *   3. order meta on the order being considered.
 *
 * ⭐ THE REDUNDANCY IS THE POINT. A salt rotation invalidates (1); (2) and (3)
 *    survive it. An opt-out that silently stops being honoured is the single
 *    worst failure this feature can have, so it is recorded three ways and read
 *    three ways.
 *
 * @param string              $email Billing email.
 * @param WC_Order|null|mixed $order Optional order, for the order-meta check.
 * @return bool
 */
function bhp_review_ask_is_opted_out( $email, $order = null ) {
	$key = bhp_review_ask_customer_key( $email );

	if ( '' !== $key ) {
		$registry = get_option( BHP_REVIEW_ASK_OPTOUT_OPTION, array() );
		if ( is_array( $registry ) && isset( $registry[ $key ] ) ) {
			return true;
		}
	}

	$email = strtolower( trim( (string) $email ) );

	if ( '' !== $email && is_email( $email ) ) {
		$user = get_user_by( 'email', $email );
		if ( $user instanceof WP_User && get_user_meta( $user->ID, BHP_REVIEW_ASK_OPTOUT_META, true ) ) {
			return true;
		}
	}

	if ( $order instanceof WC_Order && $order->get_meta( BHP_REVIEW_ASK_OPTOUT_META ) ) {
		return true;
	}

	return false;
}

/**
 * Record an opt-out, in all three places.
 *
 * ⛔ THIS IS THE ONLY FUNCTION IN THE FEATURE THAT WRITES ANYTHING ON BEHALF OF
 *    A CUSTOMER ACTION, and it writes nothing but suppression state. It touches
 *    no product, price, coupon, stock, shipping, tax, payment or WooCommerce
 *    setting, and it never changes an order's status, total or line items.
 *
 * @param string              $email Billing email.
 * @param WC_Order|null|mixed $order Optional order the link came from.
 * @return bool True when something was recorded.
 */
function bhp_review_ask_record_optout( $email, $order = null ) {
	$key = bhp_review_ask_customer_key( $email );

	if ( '' === $key ) {
		return false;
	}

	$registry = get_option( BHP_REVIEW_ASK_OPTOUT_OPTION, array() );
	$registry = is_array( $registry ) ? $registry : array();

	$registry[ $key ] = current_time( 'mysql' );

	update_option( BHP_REVIEW_ASK_OPTOUT_OPTION, $registry, false );

	$email = strtolower( trim( (string) $email ) );
	$user  = is_email( $email ) ? get_user_by( 'email', $email ) : false;

	if ( $user instanceof WP_User ) {
		update_user_meta( $user->ID, BHP_REVIEW_ASK_OPTOUT_META, current_time( 'mysql' ) );
	}

	if ( $order instanceof WC_Order ) {
		$order->update_meta_data( BHP_REVIEW_ASK_OPTOUT_META, current_time( 'mysql' ) );
		$order->save();
	}

	/**
	 * Fires after a customer opts out of review asks.
	 *
	 * @since 1.19.317
	 * @param string        $email Billing email.
	 * @param WC_Order|null $order Order the link came from, when known.
	 */
	do_action( 'bhp_review_ask_opted_out', $email, $order );

	return true;
}

/**
 * When was this customer last asked, across all their orders?
 *
 * @param string $email Billing email.
 * @return int Unix timestamp, or 0 when never.
 */
function bhp_review_ask_customer_last( $email ) {
	$key = bhp_review_ask_customer_key( $email );

	if ( '' === $key ) {
		return 0;
	}

	$registry = get_option( BHP_REVIEW_ASK_CUSTOMER_OPTION, array() );

	if ( ! is_array( $registry ) || empty( $registry[ $key ] ) ) {
		return 0;
	}

	$stamp = strtotime( (string) $registry[ $key ] );

	return $stamp ? (int) $stamp : 0;
}

/**
 * Record that this customer has just been asked.
 *
 * @param string              $email Billing email.
 * @param WC_Order|null|mixed $order Order the ask went out on.
 * @return void
 */
function bhp_review_ask_record_customer( $email, $order = null ) {
	$key = bhp_review_ask_customer_key( $email );

	if ( '' === $key ) {
		return;
	}

	$now      = current_time( 'mysql' );
	$registry = get_option( BHP_REVIEW_ASK_CUSTOMER_OPTION, array() );
	$registry = is_array( $registry ) ? $registry : array();

	$registry[ $key ] = $now;

	update_option( BHP_REVIEW_ASK_CUSTOMER_OPTION, $registry, false );

	if ( $order instanceof WC_Order ) {
		$order->update_meta_data( BHP_REVIEW_ASK_CUSTOMER_LAST_META, $now );
	}

	$email = strtolower( trim( (string) $email ) );
	$user  = is_email( $email ) ? get_user_by( 'email', $email ) : false;

	if ( $user instanceof WP_User ) {
		update_user_meta( $user->ID, BHP_REVIEW_ASK_CUSTOMER_LAST_META, $now );
	}
}

/**
 * Addresses this engine must never email.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE MAILCHIMP JOURNEY-94 SEAM. IT SHIPS EMPTY, ON PURPOSE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Up to four subscribed buyers are parked in Mailchimp journey 94 and are
 * overdue at its 21-day delay, so they will receive the SAME ask from
 * Mailchimp. ⛔ Their identities exist only inside Mailchimp. No query against
 * this store can find them, and inventing a list from order dates would be a
 * guess dressed as a fact.
 *
 * ⭐ SO THIS IS A SEAM, NOT A GUESS. Gandalf/Gimli read the four billing
 *    addresses out of Mailchimp and fill it BEFORE the production deploy, by
 *    either route:
 *      - `wp option update bhp_review_ask_excluded_emails '["a@x.com", ...]' --format=json`
 *      - or `define( 'BHP_REVIEW_ASK_EXCLUDED_EMAILS', 'a@x.com,b@y.com' );`
 *
 * ⚠ AN EMPTY LIST IS SAFE AND IS NOT A BUG. With the list empty the worst case
 *   is that up to four people receive two very similar asks three weeks after
 *   buying, which is a courtesy failure, not a compliance one. The engine is
 *   therefore not blocked on it. ⛔ But the deploy plan lists filling it as a
 *   prerequisite step, and it is Andrew's call whether to deploy without it.
 *
 * @return string[] Lowercased addresses.
 */
function bhp_review_ask_excluded_emails() {
	$list = array();

	if ( defined( 'BHP_REVIEW_ASK_EXCLUDED_EMAILS' ) ) {
		$list = array_merge( $list, explode( ',', (string) BHP_REVIEW_ASK_EXCLUDED_EMAILS ) );
	}

	$option = get_option( BHP_REVIEW_ASK_EXCLUDE_OPTION, array() );
	if ( is_string( $option ) ) {
		$option = explode( ',', $option );
	}
	if ( is_array( $option ) ) {
		$list = array_merge( $list, $option );
	}

	/**
	 * Filter the review-ask exclusion list.
	 *
	 * @since 1.19.317
	 * @param string[] $list Addresses.
	 */
	$list = (array) apply_filters( 'bhp_review_ask_excluded_emails', $list );

	$clean = array();

	foreach ( $list as $address ) {
		$address = strtolower( trim( (string) $address ) );
		if ( '' !== $address && is_email( $address ) ) {
			$clean[ $address ] = true;
		}
	}

	return array_keys( $clean );
}

/* =========================================================================
 * QUALIFICATION
 * ====================================================================== */

/**
 * Is this a school-visit order, whose parent already got the ask?
 *
 * ⭐ THE STRUCTURAL CLOSURE OF THE ADAMS DOUBLE-ASK. See this file's header,
 *    hazard 1. It reuses the bundle plugin's own meta key rather than a second
 *    literal, exactly as `inc/visit-completed-email.php` does, so the two files
 *    can never disagree about what a visit order is.
 *
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function bhp_review_ask_is_visit_order( $order ) {
	if ( function_exists( 'bhp_visit_email_order_slug' ) ) {
		return '' !== bhp_visit_email_order_slug( $order );
	}

	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$key = defined( 'BHP_SCHOOL_PICKUP_META_SLUG' ) ? BHP_SCHOOL_PICKUP_META_SLUG : '_bhp_school_visit_slug';

	return '' !== trim( (string) $order->get_meta( $key ) );
}

/**
 * The timestamp this order's clock starts from.
 *
 * ⭐ COMPLETION, NOT CREATION. Andrew hand-delivers some orders and ships the
 *    rest, and `completed` in this store tracks the physical event (the same
 *    fact `addon-thankyou-email.php` records at length). An order created in
 *    July and completed yesterday must wait 21 days from YESTERDAY.
 *
 * ⚠ THE FALLBACK CHAIN, and why each step is safe: `date_completed` is set by
 *   WooCommerce on the transition, but an order completed by a direct status
 *   write can lack it. `date_paid` and `date_modified` both sit at or after
 *   completion, so falling back to them can only DELAY an ask, never fire one
 *   early. `date_created` is deliberately NOT in the chain, because it sits
 *   BEFORE completion and would fire early.
 *
 * @param WC_Order|mixed $order Order.
 * @return int Unix timestamp, or 0 when none can be resolved.
 */
function bhp_review_ask_anchor_timestamp( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return 0;
	}

	foreach ( array( 'get_date_completed', 'get_date_paid', 'get_date_modified' ) as $getter ) {
		if ( ! method_exists( $order, $getter ) ) {
			continue;
		}
		$date = $order->$getter();
		if ( $date && method_exists( $date, 'getTimestamp' ) ) {
			return (int) $date->getTimestamp();
		}
	}

	return 0;
}

/**
 * Has this order waited long enough?
 *
 * @param WC_Order|mixed $order Order.
 * @param int            $now   Optional "now", for the suite.
 * @return bool
 */
function bhp_review_ask_is_due( $order, $now = 0 ) {
	$anchor = bhp_review_ask_anchor_timestamp( $order );

	if ( ! $anchor ) {
		return false;
	}

	$now = $now ? (int) $now : time();

	return ( $now - $anchor ) >= ( bhp_review_ask_delay_days() * DAY_IN_SECONDS );
}

/**
 * Why, if at all, this order must not receive the review ask.
 *
 * ⭐ IT RETURNS THE REASON, NOT A BOOLEAN, and that is the whole design. Every
 *    decline path has a name, the run summary counts them by name, and a QA
 *    report can say WHICH gate fired instead of "it did not send". A boolean
 *    would have made every one of the twelve reasons below look identical.
 *
 * The order of the checks is the order of cost: cheap structural tests first,
 * option reads and user lookups last.
 *
 * @param WC_Order|mixed $order Order.
 * @param int            $now   Optional "now", for the suite.
 * @return string '' when it may send, otherwise a stable reason slug.
 */
function bhp_review_ask_decline_reason( $order, $now = 0 ) {
	if ( ! $order instanceof WC_Order ) {
		return 'not_an_order';
	}

	// ⛔ THE REFUND TRAP. See this file's header. A WC_Order_Refund is an
	// order object with status `completed` and no billing email.
	if ( method_exists( $order, 'get_type' ) && 'shop_order' !== $order->get_type() ) {
		return 'not_shop_order';
	}

	if ( 'completed' !== $order->get_status() ) {
		return 'status_not_completed';
	}

	// A completed order that was later refunded in part must not be asked for
	// a review of a book it may no longer have.
	if ( method_exists( $order, 'get_total_refunded' ) && (float) $order->get_total_refunded() > 0 ) {
		return 'refunded';
	}

	if ( $order->get_meta( BHP_REVIEW_ASK_SENT_META ) ) {
		return 'already_sent';
	}

	if ( bhp_review_ask_is_visit_order( $order ) ) {
		return 'school_visit_already_asked';
	}

	if ( ! bhp_review_ask_is_due( $order, $now ) ) {
		return 'not_due';
	}

	$email = strtolower( trim( (string) $order->get_billing_email() ) );

	if ( '' === $email || ! is_email( $email ) ) {
		return 'no_billing_email';
	}

	if ( in_array( $email, bhp_review_ask_excluded_emails(), true ) ) {
		return 'excluded';
	}

	if ( bhp_review_ask_is_opted_out( $email, $order ) ) {
		return 'opted_out';
	}

	$last = bhp_review_ask_customer_last( $email );
	if ( $last ) {
		$now      = $now ? (int) $now : time();
		$cooldown = (int) apply_filters( 'bhp_review_ask_customer_cooldown_days', BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS );
		if ( ( $now - $last ) < ( $cooldown * DAY_IN_SECONDS ) ) {
			return 'customer_cooldown';
		}
	}

	// ⛔ THE HARD COMPLIANCE GATE. No address, no send. See
	// `bhp_review_ask_postal_address()`.
	if ( '' === bhp_review_ask_postal_address() ) {
		return 'no_postal_address';
	}

	/**
	 * Filter the decline reason for one order.
	 *
	 * Return a non-empty string to decline; '' to allow.
	 *
	 * @since 1.19.317
	 * @param string   $reason Reason so far ('' means allowed).
	 * @param WC_Order $order  Order.
	 */
	return (string) apply_filters( 'bhp_review_ask_decline_reason', '', $order );
}

/**
 * May this order receive the review ask?
 *
 * @param WC_Order|mixed $order Order.
 * @param int            $now   Optional "now", for the suite.
 * @return bool
 */
function bhp_review_ask_should_send( $order, $now = 0 ) {
	return '' === bhp_review_ask_decline_reason( $order, $now );
}

/**
 * Completed orders that might be due, LONGEST-WAITING FIRST.
 *
 * ⭐ THE ORDERING IS LOAD-BEARING WITH THE DAILY CAP. A backlog drips out over
 *    several days, and whoever is at the back of the queue waits longest. It
 *    must be the person who completed most recently, never an arbitrary one.
 *
 * ⛔⛔ AND IT IS SORTED IN PHP RATHER THAN IN THE QUERY, WHICH IS NOT
 *     LAZINESS — IT IS A CORRECTION. `wc_get_orders( 'orderby' => 'date' )`
 *     sorts by **date_created**. This engine's clock runs from
 *     **date_completed** (`bhp_review_ask_anchor_timestamp()`), and in this
 *     store those two routinely disagree: the eight Adams orders were created
 *     2026-08-21 to 08-25 and ALL completed at 2026-08-28T18:15, because
 *     Andrew flips visit orders to completed in a batch when he hands the books
 *     over.
 *
 *     ⭐ MEASURED, NOT REASONED ABOUT. The suite's first run on staging
 *     2026-08-29 sent to three of six batch orders instead of the expected
 *     five, because creation order and completion order were different. Sorting
 *     on the anchor is the fix.
 *
 * ⛔ `'type' => 'shop_order'` IS MANDATORY. See this file's header for the
 *    measured refund trap.
 *
 * @param int $limit How many to examine (not how many to send).
 * @return WC_Order[]
 */
function bhp_review_ask_candidates( $limit = 50 ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	/*
	 * ⚠ THE QUERY STILL ORDERS BY CREATION DATE, DESCENDING, AND THAT IS
	 *   DELIBERATE: it bounds the scan to the $limit most RECENT orders, which
	 *   is the set that can still contain something not yet asked. Sorting the
	 *   fetched window by the anchor then puts the longest-waiting first within
	 *   it. An ascending query would pin the window to the store's oldest
	 *   orders forever, and a growing store would stop seeing new ones.
	 */
	$orders = wc_get_orders(
		array(
			'type'    => 'shop_order',
			'status'  => array( 'completed' ),
			'limit'   => (int) $limit,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'objects',
		)
	);

	if ( ! is_array( $orders ) ) {
		return array();
	}

	usort(
		$orders,
		static function ( $a, $b ) {
			$at = bhp_review_ask_anchor_timestamp( $a );
			$bt = bhp_review_ask_anchor_timestamp( $b );

			if ( $at === $bt ) {
				// ⭐ A STABLE TIE-BREAK. The eight Adams orders completed within
				// fourteen seconds of each other; without this the order of a
				// tie would depend on the database's row order and a capped run
				// would be non-deterministic across two runs of the same day.
				$aid = ( $a instanceof WC_Abstract_Order ) ? (int) $a->get_id() : 0;
				$bid = ( $b instanceof WC_Abstract_Order ) ? (int) $b->get_id() : 0;

				return $aid <=> $bid;
			}

			return $at <=> $bt;
		}
	);

	return $orders;
}

/* =========================================================================
 * THE KPI LEDGER — what the morning report's REVIEWS section reads
 * ====================================================================== */

/**
 * Record one send in the KPI ledger.
 *
 * ⭐ TWO STRUCTURES, DELIBERATELY:
 *    - `bhp_review_ask_stats`: totals and a per-day count. Small, bounded,
 *      and the ONLY thing the daily cap consults, which is what makes two
 *      schedulers on the same day safe.
 *    - `bhp_review_ask_log`: the last 500 sends, as rows. Enough for a report
 *      to say what went where; capped so an option row cannot grow without
 *      limit.
 *
 * ⛔ NO EMAIL ADDRESS IS WRITTEN TO EITHER. The row carries the order id and
 *    the hashed customer key. An order id resolves to a customer through
 *    WooCommerce when a human legitimately needs it; a `wp_options` row full of
 *    customer addresses is a privacy liability sitting in every backup.
 *
 * @param WC_Order $order Order the ask went out on.
 * @return void
 */
function bhp_review_ask_log_send( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$today = current_time( 'Y-m-d' );

	$stats = get_option( BHP_REVIEW_ASK_STATS_OPTION, array() );
	$stats = is_array( $stats ) ? $stats : array();

	$stats['total']    = isset( $stats['total'] ) ? (int) $stats['total'] + 1 : 1;
	$stats['by_date']  = isset( $stats['by_date'] ) && is_array( $stats['by_date'] ) ? $stats['by_date'] : array();
	$stats['last_sent'] = current_time( 'mysql' );

	$stats['by_date'][ $today ] = isset( $stats['by_date'][ $today ] ) ? (int) $stats['by_date'][ $today ] + 1 : 1;

	// Keep roughly fourteen months of daily counts; a KPI series nobody reads
	// past a year is not worth an unbounded option row.
	if ( count( $stats['by_date'] ) > 430 ) {
		ksort( $stats['by_date'] );
		$stats['by_date'] = array_slice( $stats['by_date'], -430, null, true );
	}

	update_option( BHP_REVIEW_ASK_STATS_OPTION, $stats, false );

	$log = get_option( BHP_REVIEW_ASK_LOG_OPTION, array() );
	$log = is_array( $log ) ? $log : array();

	$log[] = array(
		'order_id'     => (int) $order->get_id(),
		'order_number' => (string) $order->get_order_number(),
		'customer_key' => bhp_review_ask_customer_key( $order->get_billing_email() ),
		'sent_at'      => current_time( 'mysql' ),
		'sent_at_gmt'  => gmdate( 'c' ),
	);

	if ( count( $log ) > 500 ) {
		$log = array_slice( $log, -500 );
	}

	update_option( BHP_REVIEW_ASK_LOG_OPTION, $log, false );

	/**
	 * Fires after a review ask has been handed to the mailer and logged.
	 *
	 * ⭐ THE HOOK THE KPI REPORT SHOULD PREFER over polling the option, if a
	 *    reporting subsystem ever wants a push rather than a pull.
	 *
	 * @since 1.19.317
	 * @param WC_Order $order Order.
	 */
	do_action( 'bhp_review_ask_sent', $order );
}

/**
 * The KPI numbers, for the morning report's REVIEWS section.
 *
 * ⚠ WHAT THESE NUMBERS HONESTLY ARE: counts of emails HANDED TO THE MAILER.
 *   `wp_mail()` returning true means "accepted by the transport", not
 *   "delivered to a human", and never "a review was written". ⛔ There is no
 *   attribution from this email to an Amazon review and there cannot be —
 *   Amazon returns no referrer. Do not let anyone build one.
 *
 * @return array {
 *     @type int    $total     All-time sends.
 *     @type int    $today     Sends on the current site-local day.
 *     @type int    $last_7    Sends in the last seven days including today.
 *     @type int    $last_30   Sends in the last thirty days including today.
 *     @type string $last_sent Site-local datetime of the most recent send.
 *     @type int    $pending   Orders that would qualify right now.
 *     @type int    $optouts   Customers who have opted out.
 * }
 */
function bhp_review_ask_stats() {
	$stats = get_option( BHP_REVIEW_ASK_STATS_OPTION, array() );
	$stats = is_array( $stats ) ? $stats : array();

	$by_date = isset( $stats['by_date'] ) && is_array( $stats['by_date'] ) ? $stats['by_date'] : array();

	$window = function ( $days ) use ( $by_date ) {
		$sum = 0;
		for ( $i = 0; $i < $days; $i++ ) {
			$day  = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -' . $i . ' days' ) );
			$sum += isset( $by_date[ $day ] ) ? (int) $by_date[ $day ] : 0;
		}
		return $sum;
	};

	$optouts = get_option( BHP_REVIEW_ASK_OPTOUT_OPTION, array() );

	return array(
		'total'     => isset( $stats['total'] ) ? (int) $stats['total'] : 0,
		'today'     => $window( 1 ),
		'last_7'    => $window( 7 ),
		'last_30'   => $window( 30 ),
		'last_sent' => isset( $stats['last_sent'] ) ? (string) $stats['last_sent'] : '',
		'pending'   => bhp_review_ask_pending_count(),
		'optouts'   => is_array( $optouts ) ? count( $optouts ) : 0,
	);
}

/**
 * How many sends have gone out today, from the ledger.
 *
 * @return int
 */
function bhp_review_ask_sent_today() {
	$stats = get_option( BHP_REVIEW_ASK_STATS_OPTION, array() );

	if ( ! is_array( $stats ) || empty( $stats['by_date'] ) || ! is_array( $stats['by_date'] ) ) {
		return 0;
	}

	$today = current_time( 'Y-m-d' );

	return isset( $stats['by_date'][ $today ] ) ? (int) $stats['by_date'][ $today ] : 0;
}

/**
 * How many orders would qualify right now.
 *
 * Read-only. Sends nothing, writes nothing, and is safe to call from a report
 * or an admin screen.
 *
 * @param int $scan How many completed orders to examine.
 * @return int
 */
function bhp_review_ask_pending_count() {
	$count = 0;

	foreach ( bhp_review_ask_candidates( 200 ) as $order ) {
		if ( bhp_review_ask_should_send( $order ) ) {
			$count++;
		}
	}

	return $count;
}

/**
 * The raw send log.
 *
 * @return array[]
 */
function bhp_review_ask_log() {
	$log = get_option( BHP_REVIEW_ASK_LOG_OPTION, array() );

	return is_array( $log ) ? $log : array();
}

/* =========================================================================
 * THE RUN
 * ====================================================================== */

/**
 * Examine due orders and send up to the daily cap.
 *
 * ⭐ IDEMPOTENT BY CONSTRUCTION. Every decision is re-read from state on every
 *    call: the sent marker on the order, the cooldown registry, the opt-out
 *    registry, and the day's count in the KPI ledger. Two invocations on the
 *    same day therefore top the day up to the cap; they never double it. That
 *    is what makes it safe for Action Scheduler and WP-Cron to both exist.
 *
 * ⛔ IT DOES NOT MARK AN ORDER IT DID NOT SEND. The marker is written only
 *    after the mailer accepts the message, so a transient mail failure retries
 *    tomorrow instead of silently swallowing one customer's ask forever. Same
 *    rule as `class-wc-email-bhp-addon-thankyou.php`, and for the same reason.
 *
 * @param array $args {
 *     @type int      $limit  Max sends this run. Defaults to the daily cap.
 *     @type int      $scan   How many completed orders to examine.
 *     @type bool     $dry    Decide everything, send nothing, write nothing.
 *     @type int      $now    Override "now", for the suite.
 *     @type callable $logger Optional line logger, for WP-CLI.
 * }
 * @return array Summary.
 */
function bhp_review_ask_run( $args = array() ) {
	$args = array_merge(
		array(
			'limit'  => 0,
			'scan'   => 200,
			'dry'    => false,
			'now'    => 0,
			'logger' => null,
		),
		(array) $args
	);

	$say = static function ( $line ) use ( $args ) {
		if ( is_callable( $args['logger'] ) ) {
			call_user_func( $args['logger'], $line );
		}
	};

	$summary = array(
		'started_at' => gmdate( 'c' ),
		'dry'        => (bool) $args['dry'],
		'examined'   => 0,
		'sent'       => 0,
		'declined'   => array(),
		'orders'     => array(),
		'halted'     => '',
		'cap'        => bhp_review_ask_daily_cap(),
		'sent_today' => bhp_review_ask_sent_today(),
	);

	if ( ! bhp_review_ask_is_enabled() ) {
		$summary['halted'] = 'disabled';
		$say( 'Review-ask engine is disabled (option bhp_review_ask_enabled != yes). Nothing done.' );
		return $summary;
	}

	// ⛔ THE COPY/DELAY INTERLOCK. See `bhp_review_ask_delay_days()`.
	if ( ! bhp_review_ask_copy_matches_delay() ) {
		$summary['halted'] = 'copy_delay_mismatch';
		$say( 'HALTED: the approved copy says "about three weeks ago" and the effective delay is ' . bhp_review_ask_delay_days() . ' days. Nothing sent.' );
		return $summary;
	}

	if ( '' === bhp_review_ask_postal_address() ) {
		$summary['halted'] = 'no_postal_address';
		$say( 'HALTED: no postal address resolves, so a CAN-SPAM footer cannot be rendered. Nothing sent.' );
		return $summary;
	}

	$cap       = bhp_review_ask_daily_cap();
	$remaining = $cap - bhp_review_ask_sent_today();

	if ( $args['limit'] > 0 ) {
		$remaining = min( $remaining, (int) $args['limit'] );
	}

	if ( $remaining <= 0 ) {
		$summary['halted'] = 'daily_cap_reached';
		$say( 'Daily cap of ' . $cap . ' already reached. Nothing sent.' );
		return $summary;
	}

	foreach ( bhp_review_ask_candidates( (int) $args['scan'] ) as $order ) {
		$summary['examined']++;

		$reason = bhp_review_ask_decline_reason( $order, (int) $args['now'] );

		if ( '' !== $reason ) {
			$summary['declined'][ $reason ] = isset( $summary['declined'][ $reason ] ) ? $summary['declined'][ $reason ] + 1 : 1;
			continue;
		}

		if ( $args['dry'] ) {
			$summary['orders'][] = array(
				'order_id' => (int) $order->get_id(),
				'result'   => 'would_send',
			);
			$summary['sent']++;
			$say( 'DRY: would send for order ' . $order->get_id() );

			if ( $summary['sent'] >= $remaining ) {
				break;
			}
			continue;
		}

		$sent = bhp_review_ask_send( $order );

		$summary['orders'][] = array(
			'order_id' => (int) $order->get_id(),
			'result'   => $sent ? 'sent' : 'mailer_declined',
		);

		if ( $sent ) {
			$summary['sent']++;
			$say( 'Sent review ask for order ' . $order->get_id() );

			if ( $summary['sent'] >= $remaining ) {
				$say( 'Daily cap reached for today.' );
				break;
			}
		} else {
			$say( 'Mailer declined for order ' . $order->get_id() . '; will retry on a later run.' );
		}
	}

	$summary['finished_at'] = gmdate( 'c' );

	return $summary;
}

/**
 * Send the ask for one order, and record everything that follows from it.
 *
 * ⛔ IT RE-CHECKS QUALIFICATION. The runner already checked, and this checks
 *    again, because this function is also reachable from WP-CLI and from the
 *    suite. A send path that trusts its caller is a send path that eventually
 *    emails somebody who opted out.
 *
 * @param WC_Order|mixed $order Order.
 * @return bool True when the mailer accepted the message.
 */
function bhp_review_ask_send( $order ) {
	if ( ! bhp_review_ask_should_send( $order ) ) {
		return false;
	}

	if ( ! function_exists( 'WC' ) || ! WC() ) {
		return false;
	}

	$mailer = WC()->mailer();

	if ( ! is_object( $mailer ) || ! method_exists( $mailer, 'get_emails' ) ) {
		return false;
	}

	$emails = $mailer->get_emails();
	$email  = null;

	foreach ( $emails as $candidate ) {
		if ( $candidate instanceof WC_Email && BHP_REVIEW_ASK_EMAIL_ID === $candidate->id ) {
			$email = $candidate;
			break;
		}
	}

	if ( ! $email instanceof WC_Email ) {
		return false;
	}

	$sent = $email->trigger( (int) $order->get_id(), $order );

	return (bool) $sent;
}

/**
 * Record a completed send against an order. Called by the email class.
 *
 * ⛔ ONE PLACE WRITES THE THREE RECORDS. The email class calls this rather than
 *    writing meta itself, so the sent marker, the customer cooldown and the KPI
 *    ledger can never fall out of step with each other.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function bhp_review_ask_mark_sent( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$order->update_meta_data( BHP_REVIEW_ASK_SENT_META, current_time( 'mysql' ) );

	bhp_review_ask_record_customer( $order->get_billing_email(), $order );

	$order->save();

	bhp_review_ask_log_send( $order );
}

/* =========================================================================
 * SCHEDULING
 * ====================================================================== */

/**
 * Ensure a daily run is scheduled.
 *
 * ⭐ ACTION SCHEDULER IS PREFERRED, WP-CRON IS THE FALLBACK, AND ONLY ONE IS
 *    REGISTERED AT A TIME. Action Scheduler ships with WooCommerce, is already
 *    running on this store (`action_scheduler_run_queue` was next-due in 45
 *    seconds when production's cron list was read read-only on 2026-08-29), and
 *    unlike WP-Cron it survives a request-starved site and records its own run
 *    history in wp-admin, which is exactly what a once-a-day customer email
 *    wants.
 *
 * ⚠ EVEN SO, `bhp_review_ask_run()` IS WRITTEN TO BE SAFE UNDER BOTH AT ONCE.
 *   The daily cap reads the KPI ledger rather than a per-run counter, so a
 *   belt-and-braces double schedule tops up to the cap instead of doubling it.
 *   The safety is in the runner, not in the scheduler choice.
 *
 * @return void
 */
function bhp_review_ask_maybe_schedule() {
	if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
		if ( ! as_has_scheduled_action( BHP_REVIEW_ASK_CRON_HOOK ) ) {
			as_schedule_recurring_action(
				time() + ( 10 * MINUTE_IN_SECONDS ),
				DAY_IN_SECONDS,
				BHP_REVIEW_ASK_CRON_HOOK,
				array(),
				'brave-hearts'
			);
		}

		// If a WP-Cron event was left behind by an earlier install, clear it so
		// the two cannot both run.
		$legacy = wp_next_scheduled( BHP_REVIEW_ASK_CRON_HOOK );
		if ( $legacy ) {
			wp_unschedule_event( $legacy, BHP_REVIEW_ASK_CRON_HOOK );
		}

		return;
	}

	if ( ! wp_next_scheduled( BHP_REVIEW_ASK_CRON_HOOK ) ) {
		wp_schedule_event( time() + ( 10 * MINUTE_IN_SECONDS ), 'daily', BHP_REVIEW_ASK_CRON_HOOK );
	}
}

/**
 * Remove every schedule this feature owns.
 *
 * Bound to `switch_theme` so a theme rollback leaves nothing running behind it,
 * matching `BHP_Bookvault_Tracker::unschedule()`.
 *
 * @return void
 */
function bhp_review_ask_unschedule() {
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( BHP_REVIEW_ASK_CRON_HOOK );
	}

	$next = wp_next_scheduled( BHP_REVIEW_ASK_CRON_HOOK );
	if ( $next ) {
		wp_unschedule_event( $next, BHP_REVIEW_ASK_CRON_HOOK );
	}

	wp_clear_scheduled_hook( BHP_REVIEW_ASK_CRON_HOOK );
}
add_action( 'switch_theme', 'bhp_review_ask_unschedule' );

/**
 * The scheduled callback.
 *
 * @return void
 */
function bhp_review_ask_cron_run() {
	bhp_review_ask_run();
}
add_action( BHP_REVIEW_ASK_CRON_HOOK, 'bhp_review_ask_cron_run' );

/**
 * Register the schedule once WordPress is up.
 *
 * ⛔ GATED ON THE MASTER SWITCH, so a deployed-but-not-approved build creates
 *    no scheduled action at all and leaves the store's scheduler exactly as it
 *    was found. When the switch goes off again, the schedule is removed.
 *
 * @return void
 */
function bhp_review_ask_bootstrap_schedule() {
	if ( bhp_review_ask_is_enabled() ) {
		bhp_review_ask_maybe_schedule();
		return;
	}

	bhp_review_ask_unschedule();
}
add_action( 'init', 'bhp_review_ask_bootstrap_schedule', 20 );

/* =========================================================================
 * THE EMAIL CLASS
 * ====================================================================== */

/**
 * Register the email class with WooCommerce.
 *
 * The class file is required INSIDE the callback because `WC_Email` does not
 * exist until WooCommerce has loaded its own email classes, exactly as
 * `bhp_bundle_register_addon_thankyou_email()` does.
 *
 * @param array $emails Registered email classes.
 * @return array
 */
function bhp_review_ask_register_email( $emails ) {
	if ( ! class_exists( 'WC_Email' ) ) {
		return $emails;
	}

	require_once get_template_directory() . '/inc/class-wc-email-bhp-review-ask.php';

	if ( class_exists( 'WC_Email_BHP_Review_Ask' ) ) {
		$emails['WC_Email_BHP_Review_Ask'] = new WC_Email_BHP_Review_Ask();
	}

	return $emails;
}
add_filter( 'woocommerce_email_classes', 'bhp_review_ask_register_email' );

/* =========================================================================
 * THE OPT-OUT ENDPOINT
 * ====================================================================== */

/**
 * Handle a click (or an RFC 8058 one-click POST) on the unsubscribe link.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠ THE TRADE-OFF, STATED RATHER THAN HIDDEN: a GET that changes state can be
 *   fired by an email client's link prescanner without the human clicking it.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The alternative is a confirmation page with a POST button, which is two
 * clicks and is not what the brief asked for. ⭐ THE ASYMMETRY DECIDES IT: a
 * prescanner-triggered opt-out means one person stops receiving a review ask
 * they never asked for. The opposite failure means somebody who clicked
 * unsubscribe gets emailed anyway. The first is a nuisance; the second is the
 * compliance failure this whole endpoint exists to prevent.
 *
 * ⛔ NOTHING ELSE IS TOUCHED. It suppresses this ONE email. Order emails,
 *    receipts and the Mailchimp lists are unaffected, and the copy says so.
 *
 * @return void
 */
function bhp_review_ask_handle_optout() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a nonce cannot exist in an email; the signed token below is the authentication.
	$raw = isset( $_REQUEST[ BHP_REVIEW_ASK_OPTOUT_QUERY ] ) ? wp_unslash( $_REQUEST[ BHP_REVIEW_ASK_OPTOUT_QUERY ] ) : '';

	if ( '' === $raw ) {
		return;
	}

	$order_id = absint( $raw );
	$token    = isset( $_REQUEST['bhpt'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bhpt'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$order = $order_id ? wc_get_order( $order_id ) : false;

	$valid = false;

	if ( $order instanceof WC_Order ) {
		$email    = strtolower( trim( (string) $order->get_billing_email() ) );
		$expected = bhp_review_ask_optout_signature( $order_id, $email );

		// ⛔ `hash_equals`, not `===`. Timing-safe comparison is the point of a
		//    signed link, and this codebase already uses it for the order-key
		//    check in `inc/class-bhp-meta-pixel.php`.
		if ( '' !== $email && '' !== $token && hash_equals( $expected, $token ) ) {
			$valid = true;
			bhp_review_ask_record_optout( $email, $order );
		}
	}

	/*
	 * ⭐ AN RFC 8058 ONE-CLICK POST GETS A BARE 200 AND NO PAGE. Gmail and
	 *    Yahoo issue that POST from their own UI and show their own
	 *    confirmation; returning an HTML page there is noise nobody sees.
	 */
	if ( 'POST' === strtoupper( (string) ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) ) {
		status_header( $valid ? 200 : 400 );
		nocache_headers();
		exit;
	}

	if ( $valid ) {
		wp_die(
			esc_html__( 'You are unsubscribed from review emails. You will still get your order emails and receipts.', 'brave-hearts' ),
			esc_html__( 'Unsubscribed', 'brave-hearts' ),
			array(
				'response'  => 200,
				'back_link' => false,
			)
		);
	}

	/*
	 * ⛔ A BAD LINK IS NOT AN ERROR PAGE WITH A CLUE IN IT. It says nothing
	 *    about whether the order exists or whether the address is on file,
	 *    because an unsubscribe endpoint that distinguishes those two is an
	 *    address-enumeration oracle.
	 */
	wp_die(
		esc_html__( 'This unsubscribe link is not valid. Reply to any email from Brave Hearts Publishing and it will be handled by a person.', 'brave-hearts' ),
		esc_html__( 'Link not valid', 'brave-hearts' ),
		array(
			'response'  => 400,
			'back_link' => false,
		)
	);
}
add_action( 'init', 'bhp_review_ask_handle_optout', 5 );

/* =========================================================================
 * WP-CLI
 * ====================================================================== */

/**
 * `wp bhp review-ask <status|run|dry>`.
 *
 * ⭐ THE OPERATOR SURFACE. A daily emailer that can only be observed by waiting
 *    a day is a daily emailer nobody can verify. `dry` answers "who would get
 *    one, and why is everybody else being skipped" without sending anything.
 *
 * @param array $args Positional args.
 * @return void
 */
function bhp_review_ask_cli( $args ) {
	$sub = isset( $args[0] ) ? $args[0] : 'status';

	$say = static function ( $line ) {
		WP_CLI::log( $line );
	};

	if ( 'status' === $sub ) {
		$stats = bhp_review_ask_stats();
		$say( 'enabled:        ' . ( bhp_review_ask_is_enabled() ? 'yes' : 'NO' ) );
		$say( 'delay days:     ' . bhp_review_ask_delay_days() );
		$say( 'copy matches:   ' . ( bhp_review_ask_copy_matches_delay() ? 'yes' : 'NO' ) );
		$say( 'daily cap:      ' . bhp_review_ask_daily_cap() );
		$say( 'postal address: ' . ( bhp_review_ask_postal_address() ? bhp_review_ask_postal_address() : 'MISSING - sending is blocked' ) );
		$say( 'excluded:       ' . count( bhp_review_ask_excluded_emails() ) );
		$say( 'sent total:     ' . $stats['total'] );
		$say( 'sent today:     ' . $stats['today'] );
		$say( 'pending now:    ' . $stats['pending'] );
		$say( 'opt-outs:       ' . $stats['optouts'] );
		return;
	}

	$summary = bhp_review_ask_run(
		array(
			'dry'    => ( 'dry' === $sub ),
			'logger' => $say,
		)
	);

	$say( '' );
	$say( 'examined: ' . $summary['examined'] . '  sent: ' . $summary['sent'] . '  halted: ' . ( $summary['halted'] ? $summary['halted'] : '-' ) );

	foreach ( $summary['declined'] as $reason => $count ) {
		$say( '  declined ' . $reason . ': ' . $count );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bhp review-ask', 'bhp_review_ask_cli' );
}
