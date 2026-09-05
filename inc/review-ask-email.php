<?php
/**
 * THE STORE-SENT REVIEW-ASK ENGINE (E-REVIEW).
 * Theme 1.19.317. Workstream `CYCLE169-LD-REVIEW-ASK-ENGINE`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ 2026-09-05 · SEAL 965 · THE SINGLE 21-DAY ASK IS REPLACED BY A
 *       TWO-TOUCH SEQUENCE. Workstream `CYCLE179-LD-REVIEW-SEQ`, theme
 *       1.19.362.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ 1.19.363 (`CYCLE179-LD-REVIEW-SEQ-R2`) — TWO CHANGES, BOTH IN THIS FILE,
 *    NEITHER TOUCHING THE LANES, THE DAY MATH, THE COPY OR ANY GATE:
 *
 *      1. `bhp_review_ask_run()`'s disabled-halt no longer stops a DRY run, so
 *         `wp bhp review-ask dry --as-of=<date>` previews the schedule BEFORE
 *         the master switch is thrown. Measured on staging 1.19.362: it
 *         answered "Nothing done" for every date, which made enabling the
 *         engine the only way to see what it would do. ⛔ The dry path still
 *         cannot send, and `bhp_review_ask_send()` now re-asserts the master
 *         switch itself as the last gate before a real parent.
 *      2. `--as-of` is accepted on `dry` (refused on a live run) and as an
 *         alias for `--dates` on `plan`.
 *
 *    ⛔ NOTHING BELOW THIS NOTE CHANGED IN 1.19.363. The §1/§4 failures Gandalf
 *       measured on staging were a defect in the SUITE'S FIXTURE, not in this
 *       engine: it hooked `bhp_school_visit_records`, which no code applies.
 *       See `tests/test-cycle179-review-seq.php` §0.
 *
 * ⛔ THE RULING BEING REPLACED IS PRESERVED HERE, NOT DELETED. Standing Rules
 *    additive-only discipline. Founder ruling **D-3, carrier item 392,
 *    2026-08-29**, which this file was built to and which every "21" below
 *    still refers to:
 *
 *      ⛔ SUPERSEDED 2026-09-05 — "ONE email. There is no reminder, no
 *         sequence and no second ask", sent at **T+21 days from order
 *         completion**, with school-visit orders excluded forever.
 *
 * ⭐ THE REPLACEMENT. Andrew Signore, seal 965, 2026-09-05, verbatim
 *    (⛔ RELAYED through the Chief of Staff; NOT witnessed first-hand by the
 *    desk that wrote this, and recorded that way deliberately, Standing Rules
 *    §9.2 rule 2):
 *
 *      "I agree with the changes and to remove the 21 day review ask and do
 *       the frequency you recommend above"
 *
 *    Which resolves to, and every one of these is implemented below:
 *
 *      TOUCH 1, VISIT LANE (order carries `_bhp_school_visit_slug`):
 *        anchored on the VISIT DATE from the `bhp_school_visits` registry,
 *        + 7 days when the order holds ONE chapter book,
 *        + 10 days when it holds TWO OR MORE.
 *        Chapter books are the three Adventures of Charlotte and Henry
 *        titles in any format. ⛔ The Adventure Activity Book DOES NOT COUNT.
 *
 *      TOUCH 1, WEB LANE (no slug):
 *        order completion + 10 days.
 *        ⚠⚠ THE 10 IS **GANDALF'S INFERENCE, NOT ANDREW'S WORD**. It is a
 *        filterable constant, it is labelled `PENDING ANDREW` at its
 *        definition and in the CLI `status` output, and the web copy set it
 *        pairs with is NOT approved, so the engine cannot send this lane at
 *        all until both land. See `BHP_REVIEW_ASK_WEB_DELAY_DAYS`.
 *
 *      TOUCH 2 (both lanes):
 *        ONE reminder, 7 days after touch 1 actually went out, and ONLY when
 *        no site review exists from that buyer's address on any of the three
 *        chapter-book products. Then never again for that order.
 *
 *      SEND WINDOW: morning, site-local. See
 *      `bhp_review_ask_in_send_window()`.
 *
 * ⛔⛔ THE SCHOOL-VISIT EXCLUSION IS REVERSED BY THIS RULING, AND THAT IS THE
 *     SINGLE MOST DANGEROUS LINE IN THIS CHANGE. Hazard 1 in the DOUBLE-ASK
 *     section below closed the Adams double-ask by excluding every visit order
 *     forever. Seal 965 makes visit orders the PRIMARY lane. The old
 *     behaviour is preserved as a filterable switch
 *     (`bhp_review_ask_exclude_visit_orders`, default now FALSE) rather than
 *     ripped out, and `bhp_review_ask_is_visit_order()` is unchanged and still
 *     used — it now SELECTS the lane instead of declining the order.
 *     ⚠ THE VISIT COMPLETED EMAIL STILL CARRIES ITS OWN AMAZON REVIEW ASK
 *       (`inc/visit-completed-email.php`, Adams set, paragraph four). A visit
 *       buyer can therefore now receive that ask AND touch 1 AND touch 2.
 *       ⛔ THIS IS NOT RESOLVED HERE. It is recorded as `CYCLE179-LD-40` and
 *       routed to Andrew. It is not this desk's contradiction to settle.
 *
 * ⚠ PRODUCTION ACTIVATION IS BLOCKED ON A DNS FIX THAT IS NOT IN THIS
 *   REPOSITORY. Site mail (`wp_mail` through SiteGround) currently FAILS DKIM
 *   at Gmail — `dkim=permerror (no key for signature) header.s=default` — and
 *   therefore fails DMARC on every message, surviving only because the domain
 *   policy is `p=none`. Read: `Business OS\ANDREW-REVIEW\2026-09-05\
 *   SITE-MAIL-AUTH-READ.md` (Gimli, 2026-09-05, Gmail RAW headers).
 *   ⛔ Do not switch `bhp_review_ask_enabled` on in production before that
 *      record is published at `default._domainkey.braveheartspublishing.com`.
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
 *    buyers arrive as TRANSACTIONAL contacts. On 2026-08-28 a `connected-operator` live read
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
 * and the `marketing-growth` build spec §2.2 says so plainly rather than pretending
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
 *    $3.99/$4.99 versus $0.00) is still OPEN and unresolved. The `marketing-growth` spec §3
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
 *      only from Mailchimp, so the list is a seam `chief-of-staff`/`connected-operator` fill with the
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

/**
 * Days between a customer's ask and the next one they may receive.
 *
 * ⛔⛔ IT GATES TOUCH 1 ONLY, AND THAT IS A CORRECTION, NOT A LOOPHOLE. Touch 1
 *     writes the customer stamp. If this gate also ran on touch 2, the stamp
 *     touch 1 just wrote would decline touch 2 seven days later, EVERY TIME,
 *     and the sequence Andrew approved would silently be a single ask again.
 *     ⭐ Touch 2 is bounded by its own, tighter rule instead: one reminder per
 *     ORDER, ever, suppressed the moment a site review appears. See
 *     `bhp_review_ask_decline_reason()`.
 */
if ( ! defined( 'BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS' ) ) {
	define( 'BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS', 90 );
}

/* -------------------------------------------------------------------------
 * ⭐ SEAL 965 · THE TWO-TOUCH SEQUENCE. See this file's header.
 * ---------------------------------------------------------------------- */

/**
 * Order meta: the datetime touch 1 ACTUALLY went out, as `Y-m-d H:i:s`.
 *
 * ⭐⭐ WHY THIS EXISTS RATHER THAN PARSING `BHP_REVIEW_ASK_SENT_META`. That
 *     key's own docblock states the rule this file has always kept: *"The
 *     engine never parses the value to decide."* It legitimately holds
 *     `external-<date>` for an ask sent by hand, which is not a parseable
 *     datetime. ⛔ Touch 2 needs a REAL DATE to count seven days from, so it
 *     gets its own explicit field and the old rule stays intact.
 *
 * ⚠ ABSENT MEANS "touch 1 happened but nobody recorded when". Touch 2 then
 *   DECLINES rather than guessing a date. Fail closed; see
 *   `bhp_review_ask_touch1_sent_timestamp()`.
 */
if ( ! defined( 'BHP_REVIEW_ASK_TOUCH1_AT_META' ) ) {
	define( 'BHP_REVIEW_ASK_TOUCH1_AT_META', '_bhp_review_ask_touch1_at' );
}

/** Order meta: touch 2 has been dealt with. Any non-empty value suppresses. */
if ( ! defined( 'BHP_REVIEW_ASK_TOUCH2_SENT_META' ) ) {
	define( 'BHP_REVIEW_ASK_TOUCH2_SENT_META', '_bhp_review_ask_touch2_sent' );
}

/** Visit lane, touch 1: days after the VISIT DATE when the order holds ONE chapter book. */
if ( ! defined( 'BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK' ) ) {
	define( 'BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK', 7 );
}

/** Visit lane, touch 1: days after the VISIT DATE when the order holds TWO OR MORE. */
if ( ! defined( 'BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK' ) ) {
	define( 'BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK', 10 );
}

/**
 * Web lane, touch 1: days after ORDER COMPLETION.
 *
 * ⚠⚠ **PENDING ANDREW. THIS NUMBER IS AN INFERENCE, NOT A RULING.** Seal 965
 *    settled the visit lane in Andrew's own words and said nothing about
 *    shipped web orders. The 10 is the Chief of Staff's recommendation carried
 *    in the build brief. It is a constant and a filter so that his answer is a
 *    one-line change, and the CLI prints `PENDING ANDREW` beside it so nobody
 *    reads it off a status screen as settled.
 *
 * ⛔ IT CANNOT FIRE ANYWAY UNTIL COPY LANDS. The web touch-1 copy set is
 *    `approved => false` placeholder text, and an unapproved set is a hard
 *    decline in `bhp_review_ask_decline_reason()`. The timing question and the
 *    copy question therefore cannot be answered by accident.
 */
if ( ! defined( 'BHP_REVIEW_ASK_WEB_DELAY_DAYS' ) ) {
	define( 'BHP_REVIEW_ASK_WEB_DELAY_DAYS', 10 );
}

/**
 * Both lanes: days after touch 1 went out before the single reminder.
 *
 * ⭐⭐ 4, NOT 7, SINCE 1.19.364 — ANDREW, SEAL 977, VERBATIM: *"If no reviews
 *     we ask 4 days later"*. Merry's `CYCLE179-MKT-REVIEW-SEQ-V2.md` §3 records
 *     the same number and marks it as the change from V1.
 *
 * ⛔ SUPERSEDED VALUE, PRESERVED RATHER THAN DELETED: **7**, seal 965,
 *    2026-09-05, shipped in 1.19.362 and 1.19.363. Replaced the same day by
 *    seal 977.
 *
 * ⚠ THE COPY INTERLOCK MOVES WITH IT. `bhp_review_ask_copy_touch2()` declares
 *   this same constant in its `delay_days`, and
 *   `bhp_review_ask_copy_matches_delay()` halts the order if the two ever
 *   disagree — so changing this number alone cannot silently make a sentence
 *   about timing untrue.
 */
if ( ! defined( 'BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS' ) ) {
	define( 'BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS', 4 );
}

/**
 * The morning send window, site-local, as `[first hour, first hour AFTER]`.
 *
 * ⭐ 08:00 to 11:59 inclusive. Seal 965's brief says "morning local time" and
 *    this is the narrowest honest reading of it. ⛔ The runner is scheduled
 *    DAILY, not hourly, so this gate does not by itself guarantee a morning
 *    send — it guarantees the engine REFUSES to send outside the window, which
 *    is the half that can be enforced in code. Whoever schedules the daily
 *    action must land it inside the window; the CLI `status` prints whether
 *    the window is open right now so that is checkable rather than assumed.
 */
if ( ! defined( 'BHP_REVIEW_ASK_WINDOW_START_HOUR' ) ) {
	define( 'BHP_REVIEW_ASK_WINDOW_START_HOUR', 8 );
}
if ( ! defined( 'BHP_REVIEW_ASK_WINDOW_END_HOUR' ) ) {
	define( 'BHP_REVIEW_ASK_WINDOW_END_HOUR', 12 );
}

/** The fallback used wherever a child's first name is wanted and none is known. */
if ( ! defined( 'BHP_REVIEW_ASK_CHILD_FALLBACK' ) ) {
	define( 'BHP_REVIEW_ASK_CHILD_FALLBACK', 'your reader' );
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
 *     email reads *"Your book turned up about three weeks ago"*. The `marketing-growth` spec
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
	 * ⛔ SUPERSEDED DEFAULT, PRESERVED IN THIS COMMENT RATHER THAN DELETED:
	 *    **21** — founder ruling D-3, carrier item 392, 2026-08-29. Replaced
	 *    2026-09-05 by seal 965. See this file's header.
	 *
	 * ⚠ SCOPE NARROWED 2026-09-05. This is now the **WEB LANE, TOUCH 1** delay
	 *   and nothing else. The visit lane runs off the visit date with its own
	 *   one-book / multi-book split, and touch 2 runs off when touch 1 actually
	 *   went out. The filter name is kept so an existing override keeps working
	 *   on the lane it was almost certainly written for.
	 *
	 * @since 1.19.317
	 * @param int $days Default `BHP_REVIEW_ASK_WEB_DELAY_DAYS` (10, PENDING ANDREW).
	 */
	$days = (int) apply_filters( 'bhp_review_ask_delay_days', BHP_REVIEW_ASK_WEB_DELAY_DAYS );

	return $days > 0 ? $days : BHP_REVIEW_ASK_WEB_DELAY_DAYS;
}

/* =========================================================================
 * ⭐ SEAL 965 · LANES, BOOK COUNTING AND THE DAY MATH
 *
 * Every function in this block is PURE or a plain read. Nothing here sends,
 * schedules or writes. That is deliberate: it is the half of the feature that
 * can be asserted in a test suite without a mailer, a clock or a real order.
 * ====================================================================== */

/**
 * Which chapter-book titles are on this order, in the order they were bought.
 *
 * ⭐⭐ WHAT COUNTS AS A CHAPTER BOOK IS NOT RE-DECIDED HERE. It is asked of
 *     `bhp_book_lookup_product()` (`inc/book-formats.php`), the same reverse
 *     lookup the product pages use, which knows the paperback AND hardcover id
 *     of each of the three Adventures of Charlotte and Henry titles and
 *     nothing else. ⛔ THE ADVENTURE ACTIVITY BOOK IS THEREFORE EXCLUDED
 *     STRUCTURALLY, not by a name match — it is simply not in that registry.
 *     A name match would break the first time the activity book is retitled,
 *     and it would count a fourth chapter book the day one is published.
 *
 * ⚠ A VARIATION IS RESOLVED TO ITS PARENT. Mariana's paperback is a variable
 *   product (parent 333, variation 334) and `WC_Order_Item_Product::
 *   get_product_id()` already returns the parent, but the variation id is
 *   checked as a fallback for any line written by an importer that set only
 *   the variation.
 *
 * ⭐ DE-DUPLICATED BY TITLE, NOT BY LINE. Two copies of Mount Everest is ONE
 *    chapter book for the timing rule. The rule Andrew approved is about how
 *    many DIFFERENT books a child has to get through before being asked, and a
 *    parent who bought two of the same book for two children has not been
 *    handed a longer reading job.
 *
 * @param WC_Order|mixed $order Order.
 * @return string[] Adventure keys, first-seen order, no duplicates.
 */
function bhp_review_ask_chapter_book_keys( $order ) {
	if ( ! $order instanceof WC_Order || ! function_exists( 'bhp_book_lookup_product' ) ) {
		return array();
	}

	$keys = array();

	foreach ( $order->get_items() as $item ) {
		if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
			continue;
		}

		$candidates = array( (int) $item->get_product_id() );

		if ( method_exists( $item, 'get_variation_id' ) && (int) $item->get_variation_id() ) {
			$candidates[] = (int) $item->get_variation_id();
		}

		foreach ( $candidates as $product_id ) {
			if ( ! $product_id ) {
				continue;
			}

			$found = bhp_book_lookup_product( $product_id );

			if ( is_array( $found ) && ! empty( $found['key'] ) && ! in_array( $found['key'], $keys, true ) ) {
				$keys[] = (string) $found['key'];
				break;
			}
		}
	}

	return $keys;
}

/**
 * How many DIFFERENT chapter books are on this order.
 *
 * @param WC_Order|mixed $order Order.
 * @return int
 */
function bhp_review_ask_chapter_book_count( $order ) {
	return count( bhp_review_ask_chapter_book_keys( $order ) );
}

/**
 * Which lane this order runs in.
 *
 * ⛔ THE TEST IS THE VISIT SLUG, NOT THE SHIPPING METHOD. A parent who ordered
 *    through the visit link and had it posted is still a visit buyer; the copy
 *    that names their school is the right copy for them.
 *
 * @param WC_Order|mixed $order Order.
 * @return string 'visit' or 'web'.
 */
function bhp_review_ask_lane( $order ) {
	return bhp_review_ask_is_visit_order( $order ) ? 'visit' : 'web';
}

/**
 * The visit date for this order, from the registry, as `Y-m-d`.
 *
 * ⛔⛔ THE DATE IS READ FROM THE `bhp_school_visits` REGISTRY AND IS NEVER
 *     DERIVED FROM THE SLUG STRING. Live slugs look like `adams-2026-08-28`
 *     and it is tempting to `substr` the date out of them. ⚠ That is a trap:
 *     the slug is a human-chosen key, the plugin does not guarantee its shape,
 *     and a visit rescheduled after its slug was minted would email every
 *     parent on the wrong day with total confidence. The registry is the
 *     record; the slug is a key into it.
 *
 * ⚠ '' MEANS "this order names a visit the registry does not know about". The
 *   caller must then fall back to the completion anchor, which can only send
 *   LATER, never earlier. See `bhp_review_ask_touch1_anchor()`.
 *
 * @param WC_Order|mixed $order Order.
 * @return string `Y-m-d`, or ''.
 */
function bhp_review_ask_visit_date( $order ) {
	$slug = bhp_review_ask_is_visit_order( $order ) ? bhp_visit_email_order_slug( $order ) : '';

	if ( '' === $slug || ! function_exists( 'bhp_school_visit_records' ) ) {
		return '';
	}

	$records = bhp_school_visit_records();

	if ( ! is_array( $records ) || ! isset( $records[ $slug ] ) || ! is_array( $records[ $slug ] ) ) {
		return '';
	}

	$date = isset( $records[ $slug ]['date'] ) ? trim( (string) $records[ $slug ]['date'] ) : '';

	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
}

/**
 * How many days after this order's anchor touch 1 is due.
 *
 * @param WC_Order|mixed $order Order.
 * @return int Days.
 */
function bhp_review_ask_touch1_delay_days( $order ) {
	if ( 'visit' === bhp_review_ask_lane( $order ) && '' !== bhp_review_ask_visit_date( $order ) ) {
		$days = bhp_review_ask_chapter_book_count( $order ) >= 2
			? BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK
			: BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK;
	} else {
		/*
		 * ⚠ A VISIT ORDER WHOSE VISIT THE REGISTRY DOES NOT KNOW FALLS IN HERE
		 *   TOO, deliberately. It gets the web delay measured from completion,
		 *   which is later than the visit-date rule would have produced, never
		 *   earlier. An unknown date must delay an email, not fire one.
		 */
		$days = bhp_review_ask_delay_days();
	}

	/**
	 * Filter the touch-1 delay in days for one order.
	 *
	 * @since 1.19.362
	 * @param int      $days  Resolved delay.
	 * @param WC_Order $order Order.
	 */
	$days = (int) apply_filters( 'bhp_review_ask_touch1_delay_days', $days, $order );

	return $days > 0 ? $days : 1;
}

/**
 * The timestamp touch 1's clock starts from.
 *
 * ⭐ VISIT LANE: the visit DATE at local midnight, so "visit + 7" lands on the
 *    calendar day a human would name, in the site's timezone, regardless of
 *    what hour Andrew happened to flip the orders to completed. The eight
 *    Adams orders all completed at one instant in a batch; anchoring those on
 *    completion would have made the send date an artefact of his afternoon.
 *
 * ⭐ WEB LANE: unchanged. `bhp_review_ask_anchor_timestamp()`, i.e. completion
 *    with its documented fallback chain.
 *
 * @param WC_Order|mixed $order Order.
 * @return int Unix timestamp, or 0.
 */
function bhp_review_ask_touch1_anchor( $order ) {
	$visit_date = bhp_review_ask_visit_date( $order );

	if ( '' !== $visit_date ) {
		$stamp = bhp_review_ask_local_midnight( $visit_date );

		if ( $stamp ) {
			return $stamp;
		}
	}

	return bhp_review_ask_anchor_timestamp( $order );
}

/**
 * Local midnight for a `Y-m-d`, as a real UTC timestamp.
 *
 * ⛔ `strtotime( "$ymd 00:00:00" )` IS NOT USED, AND THAT IS THE POINT. It
 *    resolves against PHP's process timezone, which WordPress sets to UTC on
 *    most hosts, so on a site running America/Denver it would land the anchor
 *    six or seven hours early and could fire a send a calendar day sooner than
 *    the rule says. `wp_timezone()` is the site's own zone and is what every
 *    date-boundary decision in this theme uses.
 *
 * @param string $ymd `Y-m-d`.
 * @return int Timestamp, or 0 when unparseable.
 */
function bhp_review_ask_local_midnight( $ymd ) {
	$ymd = trim( (string) $ymd );

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return 0;
	}

	try {
		$zone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$date = new DateTimeImmutable( $ymd . ' 00:00:00', $zone );
	} catch ( Exception $e ) {
		return 0;
	}

	return (int) $date->getTimestamp();
}

/**
 * When touch 1 becomes due for this order.
 *
 * @param WC_Order|mixed $order Order.
 * @return int Unix timestamp, or 0 when no anchor resolves.
 */
function bhp_review_ask_touch1_due_timestamp( $order ) {
	$anchor = bhp_review_ask_touch1_anchor( $order );

	if ( ! $anchor ) {
		return 0;
	}

	return $anchor + ( bhp_review_ask_touch1_delay_days( $order ) * DAY_IN_SECONDS );
}

/**
 * When touch 1 actually went out for this order.
 *
 * Resolution order:
 *   1. `BHP_REVIEW_ASK_TOUCH1_AT_META` — the explicit field, written by the
 *      engine on send and by the migration for a hand-sent ask.
 *   2. `BHP_REVIEW_ASK_SENT_META`, but ONLY when it holds a real datetime.
 *      Orders marked by the 1.19.317 engine carry one; orders seeded
 *      `external-<date>` do not, and are not parsed. See the note on
 *      `BHP_REVIEW_ASK_TOUCH1_AT_META`.
 *
 * ⛔ 0 MEANS "not known", AND TOUCH 2 THEN DECLINES. A reminder scheduled off a
 *    guessed date is a reminder that arrives at the wrong time to a real
 *    person, which is worse than no reminder.
 *
 * @param WC_Order|mixed $order Order.
 * @return int Unix timestamp, or 0.
 */
function bhp_review_ask_touch1_sent_timestamp( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return 0;
	}

	foreach ( array( BHP_REVIEW_ASK_TOUCH1_AT_META, BHP_REVIEW_ASK_SENT_META ) as $key ) {
		$raw = trim( (string) $order->get_meta( $key ) );

		if ( '' === $raw || ! preg_match( '/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $raw ) ) {
			continue;
		}

		/*
		 * ⚠ The stored value is site-local (`current_time( 'mysql' )`), so it
		 *   is read back in the site's zone. Reading it as UTC would shift
		 *   every reminder by the site's offset.
		 */
		$stamp = bhp_review_ask_local_datetime( $raw );

		if ( $stamp ) {
			return $stamp;
		}
	}

	return 0;
}

/**
 * A site-local `Y-m-d` or `Y-m-d H:i:s` as a real UTC timestamp.
 *
 * @param string $value Datetime string.
 * @return int Timestamp, or 0.
 */
function bhp_review_ask_local_datetime( $value ) {
	$value = trim( str_replace( 'T', ' ', (string) $value ) );

	if ( '' === $value ) {
		return 0;
	}

	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return bhp_review_ask_local_midnight( $value );
	}

	try {
		$zone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$date = new DateTimeImmutable( $value, $zone );
	} catch ( Exception $e ) {
		return 0;
	}

	return (int) $date->getTimestamp();
}

/**
 * When touch 2 becomes due for this order.
 *
 * @param WC_Order|mixed $order Order.
 * @return int Unix timestamp, or 0 when touch 1's date is unknown.
 */
function bhp_review_ask_touch2_due_timestamp( $order ) {
	$sent = bhp_review_ask_touch1_sent_timestamp( $order );

	if ( ! $sent ) {
		return 0;
	}

	/**
	 * Filter the gap between touch 1 and the single reminder, in days.
	 *
	 * @since 1.19.362
	 * @since 1.19.364 Default changed from 7 to 4 (seal 977).
	 * @param int      $days  Default `BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS` (4).
	 * @param WC_Order $order Order.
	 */
	$days = (int) apply_filters( 'bhp_review_ask_touch2_delay_days', BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS, $order );
	$days = $days > 0 ? $days : BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS;

	return $sent + ( $days * DAY_IN_SECONDS );
}

/**
 * Which touch, if any, this order is next in line for.
 *
 * ⚠ IT ANSWERS "WHICH", NOT "WHETHER". Due-ness, opt-outs, cooldowns, the send
 *   window and every other gate live in `bhp_review_ask_decline_reason()`. This
 *   function only reads the two sent markers, so the decline reasons stay in
 *   one place and cannot disagree with each other.
 *
 * @param WC_Order|mixed $order Order.
 * @return int 1, 2, or 0 when the sequence is finished for this order.
 */
function bhp_review_ask_next_touch( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return 0;
	}

	if ( '' === trim( (string) $order->get_meta( BHP_REVIEW_ASK_SENT_META ) ) ) {
		return 1;
	}

	/**
	 * Filter whether the seal-965 two-touch sequence is active.
	 *
	 * ⭐⭐ THE ONE LEVER THAT REVERTS THE WHOLE RULING. Returning false makes
	 *     this engine a single-ask engine again: an order whose touch 1 is
	 *     marked is finished, exactly as it was at 1.19.317. It exists for two
	 *     reasons, both real:
	 *
	 *       1. ⛔ IF ANDREW REVERSES SEAL 965, or pauses the reminder while
	 *          `CYCLE179-LD-40` (the visit-email double-ask) is open, the
	 *          answer is one filter rather than a rushed edit to a live engine.
	 *       2. ⭐ IT IS HOW `tests/test-cycle169-review-ask.php` STILL MEANS
	 *          SOMETHING. That suite is the 1.19.317 regression record; with
	 *          this filter it exercises the superseded behaviour honestly
	 *          instead of having its assertions deleted.
	 *
	 * @since 1.19.362
	 * @param bool     $enabled Whether touch 2 may ever be considered.
	 * @param WC_Order $order   Order.
	 */
	if ( ! (bool) apply_filters( 'bhp_review_ask_sequence_enabled', true, $order ) ) {
		return 0;
	}

	if ( '' === trim( (string) $order->get_meta( BHP_REVIEW_ASK_TOUCH2_SENT_META ) ) ) {
		return 2;
	}

	return 0;
}

/**
 * Has this buyer already left a site review on any chapter book?
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ AN UNAPPROVED REVIEW COUNTS. THAT IS THE WHOLE POINT OF THIS FUNCTION.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `inc/reviews.php` holds EVERY product review for moderation in code
 * (`bhp_review_force_moderation()`, hard-wired to 0 regardless of the site
 * option). So a parent who did exactly what touch 1 asked has a review sitting
 * at `comment_approved = 0` until Andrew gets to it. ⛔ Counting only approved
 * reviews would send that parent a reminder to do the thing they already did,
 * because Andrew had not opened wp-admin yet. That is the single most
 * embarrassing failure this sequence can have and it is closed here.
 *
 * ⛔ `spam` AND `trash` DO NOT COUNT, and that asymmetry is deliberate: a
 *    spam-flagged comment is not evidence the buyer wrote anything.
 *
 * ⚠ THE MATCH IS ON EMAIL ADDRESS, WHICH IS THE ONLY JOIN AVAILABLE. Most
 *   buyers are guests with no user account. A parent who reviews from a
 *   different address than they ordered with is not detected, and will receive
 *   the reminder. Stated rather than hidden; there is no fix that does not
 *   involve asking them to log in.
 *
 * ⚠ REVIEWS LIVE ON THE CANONICAL (PAPERBACK) PRODUCT ONLY, per
 *   `inc/reviews.php` §1 — one review store per title, not per SKU. So the
 *   three ids below are the whole search space, and a hardcover buyer's review
 *   is still found because it was stored against the paperback.
 *
 * @param string $email Billing email.
 * @return bool
 */
function bhp_review_ask_has_site_review( $email ) {
	$email = strtolower( trim( (string) $email ) );

	if ( '' === $email || ! is_email( $email ) || ! function_exists( 'bhp_review_route_slugs' ) ) {
		return false;
	}

	$product_ids = array();

	foreach ( array_keys( bhp_review_route_slugs() ) as $key ) {
		$id = function_exists( 'bhp_review_target_id' ) ? (int) bhp_review_target_id( $key ) : 0;

		if ( $id ) {
			$product_ids[] = $id;
		}
	}

	if ( empty( $product_ids ) ) {
		return false;
	}

	$found = get_comments(
		array(
			'author_email' => $email,
			'post__in'     => $product_ids,
			'type'         => 'review',
			'status'       => 'all',
			'number'       => 1,
			'count'        => true,
			'fields'       => 'ids',
		)
	);

	if ( (int) $found > 0 ) {
		return true;
	}

	/*
	 * ⚠ A SECOND PASS WITHOUT THE TYPE FILTER, AND IT IS NOT BELT AND BRACES.
	 *   `WC_Comments::update_comment_type()` stamps `comment_type = review` on
	 *   `wp_insert_comment`, but a review submitted before that hook ran, or
	 *   imported, can sit as a bare `comment` on a product. Missing one would
	 *   send a reminder to somebody who already wrote a review, which is the
	 *   failure this function exists to prevent, so the cheaper false negative
	 *   is traded away.
	 */
	$found = get_comments(
		array(
			'author_email' => $email,
			'post__in'     => $product_ids,
			'status'       => 'all',
			'number'       => 1,
			'count'        => true,
			'fields'       => 'ids',
		)
	);

	return (int) $found > 0;
}

/**
 * Is the site-local clock inside the morning send window?
 *
 * @param int $now Optional "now", for the suite.
 * @return bool
 */
function bhp_review_ask_in_send_window( $now = 0 ) {
	$now = $now ? (int) $now : time();

	$hour = (int) wp_date( 'G', $now );

	$start = (int) apply_filters( 'bhp_review_ask_window_start_hour', BHP_REVIEW_ASK_WINDOW_START_HOUR );
	$end   = (int) apply_filters( 'bhp_review_ask_window_end_hour', BHP_REVIEW_ASK_WINDOW_END_HOUR );

	/**
	 * Filter whether the engine may send at this moment.
	 *
	 * ⭐ THE SUITE AND THE DRY RUN BOTH DISABLE THE WINDOW THROUGH THIS FILTER
	 *    rather than by moving the clock, so a QA run at four in the afternoon
	 *    still exercises every other gate.
	 *
	 * @since 1.19.362
	 * @param bool $open Whether the window is open.
	 * @param int  $now  Timestamp being tested.
	 */
	return (bool) apply_filters( 'bhp_review_ask_in_send_window', ( $hour >= $start && $hour < $end ), $now );
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
function bhp_review_ask_copy_legacy_21day() {
	$copy = array(
		/*
		 * ⛔⛔ SUPERSEDED 2026-09-05 BY SEAL 965 AND UNREACHABLE BY DEFAULT.
		 *     PRESERVED VERBATIM, NOT DELETED.
		 *
		 * Every string below is still founder-approved copy and is still
		 * TRUE AT 21 DAYS. It is kept whole because (a) the additive-only
		 * discipline forbids deleting a superseded ruling's artefacts, and
		 * (b) if Andrew reverses seal 965 the 21-day ask is one filter away
		 * rather than a retyping job against a file that no longer has it.
		 *
		 * ⛔ NOTHING SELECTS THIS SET. `bhp_review_ask_copy()` never returns
		 *    it. The only route back is the `bhp_review_ask_copy` filter.
		 */
		'set'             => 'legacy_21day',
		'touch'           => 1,
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
		 * ⛔ THE APPROVED COPY HAS NO HEADING. The `marketing-growth` spec §3 gives a subject,
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
		 * ⭐ THREE NAMED DOORS, NOT A GUESS. The `marketing-growth` spec §4 sets out why: a
		 *    WooCommerce order knows which book was bought, but the ask is the
		 *    same for every title and a reader disambiguates three names in a
		 *    quarter of a second. Option B (branch by product) and Option C (a
		 *    `/review/` router page, which returns 404 today) were both
		 *    considered and rejected there.
		 *
		 * ⛔ THE ASINs ARE NOT TYPED FROM MEMORY. They are the canonical values
		 *    in repo `docs\PROJECT_STATE.md` lines 347-349, checked against that
		 *    file on 2026-08-29. The three `create-review` URLs were fetched by
		 *    the `marketing-growth` lane on 2026-08-29 and returned HTTP 200.
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

	return $copy;
}

/* -------------------------------------------------------------------------
 * ⭐ SEAL 965 · THE THREE LIVE COPY SETS
 *
 * One approved, two placeholders. The placeholders are `approved => false`,
 * which is a hard decline in `bhp_review_ask_decline_reason()`, so no
 * PENDING-COPY string can reach a customer under any configuration.
 * ---------------------------------------------------------------------- */

/**
 * VISIT LANE, TOUCH 1. ⭐ APPROVED.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ MERRY'S APPROVED PRIMARY TEMPLATE, RENDERED WORD FOR WORD.
 *     SOURCE, READ AT SOURCE RATHER THAN ACCEPTED FROM A BRIEF:
 *     `Business OS\WORKING-DRAFTS\marketing-growth\CYCLE179-MKT-REVIEW-ASKS.md`
 *     §1 "THE PRIMARY TEMPLATE (single book)", 2026-09-05.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⚠ THE SHORTER MULTI-BOOK VARIANT (that file's §2) IS **NOT** IMPLEMENTED,
 *   AND THAT IS A DELIBERATE OMISSION REPORTED RATHER THAN QUIETLY MADE. §2 is
 *   written for a HAND-SENT note and turns on facts this engine cannot know:
 *   *"the book the parent named at the table"*, and *"which I did not expect
 *   and have not stopped grinning about"*, which is a sentiment about a
 *   specific morning. The primary template carries no such fact, so it is true
 *   for a two-book order as well, and the multi-book difference Andrew ruled on
 *   is expressed where he put it — in the TIMING (+10 rather than +7) — not in
 *   a second set of words nobody has approved for automated sending.
 *
 * ⭐ MERGE SLOTS, resolved per order by `bhp_review_ask_merge()`:
 *      {ParentFirstName} {ChildFirstName} {SchoolName} {BookTitle} {ReviewLink}
 *
 * ⛔ NO EM DASH. ⛔ NO "we", "us" or "our". ⛔ No price, coupon, shipping
 *    figure, review count, rating, reaction or outcome claim. The suite
 *    asserts all of it.
 *
 * @return array
 */
function bhp_review_ask_copy_visit_touch1( $order = null ) {
	$named = bhp_review_ask_child_first_name_is_known( $order );

	/*
	 * ⚠ THE TIME PHRASE IS THE ONLY THING THE BOOK COUNT CHANGES, AND IT
	 *   CHANGES BECAUSE MERRY FLAGGED IT RATHER THAN BECAUSE IT LOOKED NICER.
	 *   V2 "Numbers used": *"about a week now" is ACTUAL for the 7-day send.
	 *   ⚠ For a two-or-more-book order sending at +10 days, swap to "for a
	 *   week and a half now". Flagged so it is not shipped wrong."* This set
	 *   declares BOTH delays, so the sentence has to be true at both, and one
	 *   fixed sentence cannot be. It is composed per order instead.
	 */
	$when = ( bhp_review_ask_chapter_book_count( $order ) >= 2 )
		? __( 'for a week and a half now', 'brave-hearts' )
		: __( 'for about a week now', 'brave-hearts' );

	/*
	 * ⭐ THE GENERIC SWAP IS NOT STYLING. V2 §2, "Generic swap, for any order
	 *    with no child first name on record": *"This is not optional styling.
	 *    See conflict CYCLE179-MKT-32: {ChildFirstName} is not a WooCommerce
	 *    order field, so on most orders this version is the one that sends."*
	 *
	 * ⛔ AND IT IS ALSO A RENDERING NECESSITY. `{ChildFirstName}` falls back to
	 *    the lower-case `your reader`, so the named opener would render
	 *    *"your reader has had ..."* with a lower-case letter opening the
	 *    email. Merry supplies a differently-capitalised generic line for
	 *    exactly that reason. This is the branch the docblock on
	 *    `bhp_review_ask_copy_touch2()` anticipated in 1.19.362.
	 */
	$opener = $named
		? sprintf( __( '{ChildFirstName} has had {BookTitle} %s.', 'brave-hearts' ), $when )
		: sprintf( __( 'Your reader has had {BookTitle} %s.', 'brave-hearts' ), $when );

	return array(
		'set'             => $named ? 'visit_touch1' : 'visit_touch1_generic',

		// ⭐ 1.19.364 · seal 977: this set renders the five-star row.
		'stars'           => true,
		'touch'           => 1,
		'lane'            => 'visit',

		/*
		 * ⭐ AN ARRAY, NOT A SCALAR, AND THIS IS THE ADAPTED INTERLOCK. The
		 *    1.19.317 guard compared one number to one number because there was
		 *    one delay. This lane has two legitimate delays, 7 and 10, chosen
		 *    per order by the book count. The copy therefore declares BOTH, and
		 *    `bhp_review_ask_copy_matches_delay()` asserts the delay this order
		 *    will actually use is one the copy is true at. ⛔ The guard is
		 *    adapted, not bypassed: a delay outside this list still halts.
		 *
		 * ⚠ WHY 7 AND 10 ARE BOTH TRUE FOR THESE WORDS, and it is no longer
		 *   free. The only time claim in this body is `$when`, composed from
		 *   the same book count that chooses the delay: at 7 it reads *"for
		 *   about a week now"*, at 10 *"for a week and a half now"*.
		 *   ⛔ IF ANYONE LATER HARD-CODES ONE OF THOSE TWO STRINGS, THIS ARRAY
		 *   MUST DROP TO THE SINGLE DELAY IT IS THEN TRUE AT.
		 */
		'delay_days'      => array(
			BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK,
			BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK,
		),

		/*
		 * ⭐⭐ 1.19.365 · APPROVED BY ANDREW, SEAL 982, RELAYED THROUGH GANDALF
		 *     VERBATIM: *"agreed, conitnue to build it out"*, given 2026-09-05
		 *     after seal 981 removed one sentence from touch 1 (recorded
		 *     below). The approval names day 0, touch 1, touch 2 and the web
		 *     variant of touch 1, and it attaches to
		 *     `Business OS\WORKING-DRAFTS\marketing-growth\
		 *     CYCLE179-MKT-REVIEW-SEQ-V2.md` AS IT STOOD AT md5
		 *     `1ecd9c75acfc755df0e121b47ca73842`, checked on both mounts today.
		 *
		 * ⛔ THE MASTER SWITCH IS STILL OFF. `bhp_review_ask_is_enabled()` reads
		 *    the `bhp_review_ask_enabled` option and it is unset. Approved copy
		 *    is not an activated engine, and this bool activates nothing.
		 */
		'approved'        => true,

		'subject'         => __( 'A small favor about the book', 'brave-hearts' ),

		/*
		 * ⚠ ENGINEERING COPY, MARKED AS SUCH. Merry's template is a plain note
		 *   with no preheader. A WooCommerce email renders a preheader slot,
		 *   and leaving it empty shows the reader the raw start of the HTML in
		 *   the inbox preview. This restates the subject rather than adding a
		 *   new claim.
		 */
		'preheader'       => __( 'A small favor about the book', 'brave-hearts' ),

		/*
		 * ⛔ EMPTY H1. The approved copy has no heading, and filling one repeats
		 *    a sentence the reader already met in the subject line.
		 */
		'heading'         => '',

		/*
		 * ⭐ V2 §2 BODY, TRANSCRIBED WORD FOR WORD. The greeting is NOT here:
		 *    both templates render *"Hi {first name},"* / *"Hi there,"*
		 *    themselves, which is the conditional Merry's merge table asks for.
		 */
		'body_before'     => array(
			$opener,
			__( 'Would you rate it? It takes about ten seconds, and if you have another minute after that, two or three honest sentences would help the next parent decide. Honest is the useful part.', 'brave-hearts' ),
		),

		/*
		 * ⛔⛔ REMOVED BY ANDREW, SEAL 981, 2026-09-05, PRESERVED HERE RATHER
		 *     THAN DELETED so nobody restores it from an older draft. Relayed
		 *     verbatim: *"Removed the "A three star review.." part - lets not
		 *     plant a seed for them to give us less stars than we deserve."*
		 *     The sentence was:
		 *
		 *       "A three-star review that says why is worth more to me than a
		 *        five-star one that does not."
		 *
		 * ⚠ IT WAS NEVER IN THIS FILE. It lived in Merry's draft only; this
		 *   engine's touch-1 set carried the superseded ASKS §1 prose until
		 *   1.19.365. Recorded so the absence is a decision on the record and
		 *   not an accident of which draft a future editor happens to open.
		 */
		'question'        => '',
		'body_middle'     => array(),

		/*
		 * ⭐ THE LINE THAT FOLLOWS THE STAR ROW IN V2, PUT IN THE ONLY SLOT THAT
		 *    SITS BETWEEN THE ROW AND THE LINK. ⚠ `links_lead` renders inside
		 *    <strong>, so these words are bolder in the email than on the page.
		 *    That is a RENDERING deviation, reported not hidden. The words are
		 *    Merry's, unchanged.
		 */
		'links_lead'      => __( 'Tap the stars that fit, then two or three honest sentences on the next page.', 'brave-hearts' ),

		/*
		 * ⭐ ONE LINK, THE SAME DESTINATION THE FIVE STARS POINT AT.
		 *
		 * ⚠ V2 §2's body shows only [STAR ROW] and no separate link line. The
		 *   link is kept for two reasons, both reported rather than assumed:
		 *   (a) `bhp_review_ask_copy_is_usable()` requires a non-empty `links`
		 *   array, and relaxing a send gate to match a layout preference is the
		 *   wrong trade; (b) the star row is a five-cell table, and a client
		 *   that flattens tables would otherwise leave the reader no way
		 *   through. It is the SAME page as all five stars, so *"one
		 *   destination"* holds.
		 */
		'links'           => array(
			array(
				'label' => '{BookTitle}',
				'url'   => '{ReviewLink}',
			),
		),

		/*
		 * ⭐⭐ 1.19.364 · ONE DESTINATION. ANDREW, SEAL 977, VERBATIM:
		 *     *"I want one destination not 2"*.
		 *
		 * ⛔ REMOVED SENTENCE, PRESERVED HERE RATHER THAN DELETED (approved
		 *    2026-09-05, superseded the same day by seal 977):
		 *
		 *      "If you would rather leave it on Amazon instead, that helps
		 *       too, and that link is the QR on the bookmark that came with
		 *       the book."
		 *
		 * ⚠ IT IS NOT ONLY A PREFERENCE. Merry's V2 §7 decoded the V6 bookmark
		 *   QR first-hand on 2026-09-05: it resolves to
		 *   `amazon.com/review/create-review`, so the removed sentence pointed
		 *   at the second destination in the same breath as describing it.
		 */
		'body_after'      => $named
			? array( __( 'Thank you for reading with {ChildFirstName}.', 'brave-hearts' ) )
			: array( __( 'Thank you for reading together.', 'brave-hearts' ) ),

		'signoff'         => array(
			__( 'Andrew', 'brave-hearts' ),
		),

		'signoff_tagline' => '',

		/*
		 * ⚠ D-6 AGAIN, AND IT IS A PROMISE IN HIS NAME. *"I answer every one."*
		 *   Merry's template carries it as a P.S.; ship it only if he will
		 *   actually do it, on an email the store sends without him.
		 */
		'postscript'      => $named
			? __( 'P.S. If {ChildFirstName} has a question about the book, hit reply. I answer every one.', 'brave-hearts' )
			: __( 'P.S. If your reader has a question about the book, hit reply. I answer every one.', 'brave-hearts' ),

		'optout_lead'     => __( 'If you would rather not get a message like this again,', 'brave-hearts' ),
		'optout_link'     => __( 'unsubscribe from review emails', 'brave-hearts' ),
		'optout_note'     => __( 'This does not affect your order emails or your receipts.', 'brave-hearts' ),
	);
}

/**
 * WEB LANE, TOUCH 1. ⭐ APPROVED 2026-09-05, SEAL 982.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ MERRY'S V2 §4 PROSE, TRANSCRIBED WORD FOR WORD FROM
 *     `Business OS\WORKING-DRAFTS\marketing-growth\CYCLE179-MKT-REVIEW-SEQ-V2.md`
 *     (md5 `1ecd9c75acfc755df0e121b47ca73842`, read on both mounts 2026-09-05),
 *     approved by Andrew, seal 982, relayed verbatim through Gandalf:
 *     *"agreed, conitnue to build it out"*.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ NO SCHOOL, NO SIGNING LINE, NO VISIT, NO CHILD NAME, and that is the whole
 *    point of a separate set. V2 §4: *"There was no table and no signature, and
 *    implying otherwise would fabricate an author experience."* Nothing in this
 *    body may ever be merged from the visit registry.
 *
 * ⭐ `Thank you for taking a chance on a book by somebody you had never heard
 *    of.` IS NOT NEW COPY. V2 §4 says so explicitly: it was already the
 *    engine's approved `body_after` and carries its existing approval.
 *
 * ⚠ THE 10-DAY DELAY IS STILL AN INFERENCE, NOT A SEAL. Conflict
 *   CYCLE179-MKT-34, carried forward UNRESOLVED and still PENDING ANDREW.
 *   Seal 977 spoke to visit timings; *"the 7 day review ask for the website"*
 *   has two honest readings and neither Merry nor this desk may settle it.
 *   ⛔ `BHP_REVIEW_ASK_WEB_DELAY_DAYS` was NOT edited in this build.
 *
 * @return array
 */
function bhp_review_ask_copy_web_touch1() {
	return array(
		'set'             => 'web_touch1',

		// ⭐ 1.19.364 · seal 977: this set renders the five-star row.
		'stars'           => true,
		'touch'           => 1,
		'lane'            => 'web',

		/*
		 * ⚠ ONE DELAY, AND THE BODY IS TRUE AT IT. *"for a week or so now"* is
		 *   loose enough to hold at 10 days and would still hold at 7 if Andrew
		 *   settles CYCLE179-MKT-34 the other way, so the copy does not have to
		 *   move when the number does. The interlock still checks it.
		 */
		'delay_days'      => array( BHP_REVIEW_ASK_WEB_DELAY_DAYS ),

		/*
		 * ⭐⭐ 1.19.365 · SEAL 982. See the same note on the visit set above.
		 * ⛔ The master switch remains off; approved copy is not an active
		 *    engine.
		 */
		'approved'        => true,

		'subject'         => __( 'A small favor about the book', 'brave-hearts' ),

		// ⚠ ENGINEERING COPY: restates the subject, adds no claim.
		'preheader'       => __( 'A small favor about the book', 'brave-hearts' ),

		'heading'         => '',

		/*
		 * ⭐ V2 §4 BODY, VERBATIM. The greeting is rendered by the templates.
		 */
		'body_before'     => array(
			__( 'Your reader has had {BookTitle} for a week or so now. It went out in the mail, so I never got to see who opened it.', 'brave-hearts' ),
			__( 'Would you rate it? It takes about ten seconds, and if you have another minute after that, two or three honest sentences would help the next parent decide. Honest is the useful part.', 'brave-hearts' ),
		),

		'question'        => '',
		'body_middle'     => array(),

		// ⭐ V2's post-star-row line. ⚠ Renders bold; see the visit set's note.
		'links_lead'      => __( 'Tap the stars that fit, then two or three honest sentences on the next page.', 'brave-hearts' ),

		'links'           => array(
			array(
				'label' => '{BookTitle}',
				'url'   => '{ReviewLink}',
			),
		),

		'body_after'      => array(
			__( 'Thank you for taking a chance on a book by somebody you had never heard of.', 'brave-hearts' ),
		),

		'signoff'         => array( __( 'Andrew', 'brave-hearts' ) ),
		'signoff_tagline' => '',

		/*
		 * ⛔ NO POSTSCRIPT ON THE WEB SET. V2 §4 carries none, and the visit
		 *    set's *"I answer every one"* P.S. is a promise in Andrew's name
		 *    (D-6). It is not copied into a set Merry did not put it in.
		 */
		'postscript'      => '',

		'optout_lead'     => __( 'If you would rather not get a message like this again,', 'brave-hearts' ),
		'optout_link'     => __( 'unsubscribe from review emails', 'brave-hearts' ),
		'optout_note'     => __( 'This does not affect your order emails or your receipts.', 'brave-hearts' ),
	);
}

/**
 * TOUCH 2, BOTH LANES. ⭐ APPROVED 2026-09-05, SEAL 982.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ MERRY'S V2 §3 PROSE, TRANSCRIBED WORD FOR WORD. Same source file and
 *     same md5 as the web set above. V2 §4 closes with *"Touch 2 for web
 *     orders is the section 3 body unchanged"*, which is why ONE set serves
 *     both lanes: it names no school and no child, so there is nothing to
 *     differ about.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ TWO SENTENCES IN THIS SET ARE LOAD-BEARING AND MERRY MARKED THEM SO.
 *
 *   1. *"I know how a week can get away from me"* is ANDREW'S OWN SENTENCE and
 *      MUST NOT BE REWORDED. Seal 977: *"the seven days later should be I know
 *      how a week can get away from me (not you) - I dont want to make it seem
 *      like their fault"*. The `me` is the whole point. It carries no `you`, no
 *      `your`, no `busy`, no `I know you meant to`. ⛔ IF IT EVER NEEDS TO
 *      SHORTEN, IT GETS DELETED, NOT REWRITTEN.
 *
 *   2. *"This is the last note I will send about it"* MUST NOT BE CUT. It is
 *      what makes the sequence finite, and it is what makes the copy TRUE,
 *      ⛔ because the engine must then actually stop. There is no touch 3, and
 *      `bhp_review_ask_next_touch()` must never grow one while this line ships.
 *
 * ⭐ *"If it is not for you, that is completely fine"* comes BEFORE the
 *    sign-off, and the ordering is deliberate: the exit is offered before the
 *    thanks. V2 §3: *"That is the difference between no pressure and polite
 *    pressure."* It is in `body_after`, which renders above the signoff.
 *
 * @return array
 */
function bhp_review_ask_copy_touch2() {
	return array(
		'set'             => 'touch2',

		// ⭐ 1.19.364 · seal 977: this set renders the five-star row.
		'stars'           => true,
		'touch'           => 2,
		'lane'            => 'any',

		/*
		 * ⚠ +4 DAYS FROM TOUCH 1, not from the anchor. Seal 977: *"If no
		 *   reviews we ask 4 days later"*. The body makes NO time claim at all
		 *   (*"a week"* in the opening line is about Andrew's own week, not the
		 *   reader's elapsed time since the book arrived), so the interlock has
		 *   nothing to contradict here.
		 */
		'delay_days'      => array( BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS ),

		/*
		 * ⭐⭐ 1.19.365 · SEAL 982. See the note on the visit set above.
		 * ⛔ The master switch remains off.
		 */
		'approved'        => true,

		'subject'         => __( 'Last note about the book', 'brave-hearts' ),

		// ⚠ ENGINEERING COPY: restates the subject, adds no claim.
		'preheader'       => __( 'Last note about the book', 'brave-hearts' ),

		'heading'         => '',

		'body_before'     => array(
			__( 'I know how a week can get away from me, so I made this as short as I could.', 'brave-hearts' ),
		),

		'question'        => '',
		'body_middle'     => array(),

		// ⭐ V2's post-star-row line. ⚠ Renders bold; see the visit set's note.
		'links_lead'      => __( 'Tap the stars that fit, then two or three honest sentences on the next page.', 'brave-hearts' ),

		'links'           => array(
			array(
				'label' => '{BookTitle}',
				'url'   => '{ReviewLink}',
			),
		),

		'body_after'      => array(
			__( 'If it is not for you, that is completely fine. This is the last note I will send about it.', 'brave-hearts' ),
		),

		'signoff'         => array( __( 'Andrew', 'brave-hearts' ) ),
		'signoff_tagline' => '',
		'postscript'      => '',

		'optout_lead'     => __( 'If you would rather not get a message like this again,', 'brave-hearts' ),
		'optout_link'     => __( 'unsubscribe from review emails', 'brave-hearts' ),
		'optout_note'     => __( 'This does not affect your order emails or your receipts.', 'brave-hearts' ),
	);
}

/**
 * The copy set for one touch on one order, with every merge slot resolved.
 *
 * ⭐ THE SIGNATURE IS BACKWARD-COMPATIBLE ON PURPOSE. Both parameters default,
 *    so every 1.19.317 call site (`WC_Email_BHP_Review_Ask::
 *    get_default_subject()`, `get_default_heading()`, the CLI) keeps working
 *    without an edit and gets the visit touch-1 set.
 *
 * @param int                 $touch 1 or 2.
 * @param WC_Order|null|mixed $order Order, for lane selection and merges.
 * @return array
 */
function bhp_review_ask_copy( $touch = 1, $order = null ) {
	return bhp_review_ask_merge_copy( bhp_review_ask_copy_raw( $touch, $order ), $order );
}

/**
 * The copy set for one touch on one order, WITH ITS MERGE SLOTS STILL IN IT.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.364 — THIS FUNCTION EXISTS BECAUSE THE MERGE GATE WAS STRUCTURALLY
 *     DEAD, AND THE SUITE CAUGHT IT.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DEFECT, MEASURED NOT REASONED ABOUT. `tests/test-cycle179-review-seq.php`
 *    §7 asserts that a visit order holding NO chapter book declines
 *    `unresolved_merge_slot`. On staging at 1.19.363 it returned `''` — the
 *    order QUALIFIED. The cause: `bhp_review_ask_copy()` ended with
 *    `return bhp_review_ask_merge_copy( $copy, $order );`, so by the time
 *    `bhp_review_ask_decline_reason()` handed that array to
 *    `bhp_review_ask_merge_is_complete()`, every `{BookTitle}` had ALREADY been
 *    replaced — with the empty string, for this order. The gate then ran
 *    `strpos( $blob, '{BookTitle}' )`, found nothing, `continue`d, and returned
 *    true for every order that has ever been checked.
 *
 * ⛔⛔ THE GATE HAS THEREFORE NEVER FIRED IN PRODUCTION OR ON STAGING. It is not
 *     that it fired late or fired wrongly: an email reading *"Thank you for
 *     picking up  at ."* was one qualifying order away the entire time. The
 *     only thing that has kept it off a real parent is that the engine's master
 *     switch is off and the web/touch-2 copy is unapproved.
 *
 * ⭐ THE FIX IS THE SEPARATION, NOT A PATCH TO THE GATE. Substitution and
 *    inspection are now two calls: the gates read the RAW set, the renderer
 *    reads the merged one. A gate that inspects post-substitution text can
 *    never see a slot, so no future gate can be written wrong the same way.
 *
 * ⚠ `approved` (bool) and `delay_days` (array of ints) are untouched by
 *   merging, so the two gates that read them behaved correctly before and
 *   behave identically now. Only `merge_is_complete()` was affected.
 *
 * @since 1.19.364
 * @param int                 $touch 1 or 2.
 * @param WC_Order|null|mixed $order Order, for lane selection.
 * @return array Unmerged copy set.
 */
function bhp_review_ask_copy_raw( $touch = 1, $order = null ) {
	$touch = ( 2 === (int) $touch ) ? 2 : 1;

	if ( 2 === $touch ) {
		$copy = bhp_review_ask_copy_touch2();
	} elseif ( $order instanceof WC_Order && 'web' === bhp_review_ask_lane( $order ) ) {
		$copy = bhp_review_ask_copy_web_touch1();
	} else {
		/*
		 * ⭐ 1.19.365 · THE ORDER IS PASSED NOW, and it is not cosmetic.
		 *    The visit set chooses the NAMED or the GENERIC wording on
		 *    `bhp_review_ask_child_first_name_is_known()` and composes its
		 *    one time phrase from the chapter-book count. ⚠ With no order
		 *    in hand (the CLI preview, `get_default_subject()`) it falls to
		 *    the GENERIC one-book wording, which is the safe default:
		 *    CYCLE179-MKT-32 says that is what most real orders get anyway.
		 */
		$copy = bhp_review_ask_copy_visit_touch1( $order );
	}

	/**
	 * Filter the review-ask copy set.
	 *
	 * ⭐ THE SEAM FOR NEW OR CORRECTED COPY. A set arrives carrying its own
	 *    `delay_days` and its own `approved` flag, and the gates then agree
	 *    rather than declining. That is the only supported way to change what
	 *    this email says or when it is true.
	 *
	 * ⛔ A FILTER THAT RETURNS SOMETHING UNUSABLE IS DISCARDED, not trusted.
	 *
	 * @since 1.19.317
	 * @since 1.19.362 `$touch` and `$order` added.
	 * @param array         $copy  The copy set.
	 * @param int           $touch 1 or 2.
	 * @param WC_Order|null $order Order, when one is in hand.
	 */
	$filtered = apply_filters( 'bhp_review_ask_copy', $copy, $touch, $order );

	if ( bhp_review_ask_copy_is_usable( $filtered ) ) {
		$copy = $filtered;
	}

	// ⛔ RAW. The caller merges. See this function's docblock for why.
	return $copy;
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

	foreach ( array( 'subject', 'preheader' ) as $key ) {
		if ( empty( $copy[ $key ] ) || ! is_string( $copy[ $key ] ) ) {
			return false;
		}
	}

	/*
	 * ⚠ RELAXED 2026-09-05, AND ONLY FOR EMPTINESS. `question` and `links_lead`
	 *   moved out of the `empty()` loop above and joined `heading`, which was
	 *   already here for exactly this reason: the visit touch-1 set Andrew
	 *   approved has no bolded question and no "Find the one you read:" line,
	 *   because Merry's template has neither. ⛔ Leaving them in the loop would
	 *   make the approved copy fail its own usability test and fall back to a
	 *   set nobody selected. They are still required to be PRESENT and to be
	 *   STRINGS, so a typo'd key is still caught.
	 */
	foreach ( array( 'heading', 'question', 'links_lead' ) as $key ) {
		if ( ! isset( $copy[ $key ] ) || ! is_string( $copy[ $key ] ) ) {
			return false;
		}
	}

	foreach ( array( 'body_before', 'body_after', 'signoff', 'links' ) as $key ) {
		if ( empty( $copy[ $key ] ) || ! is_array( $copy[ $key ] ) ) {
			return false;
		}
	}

	/*
	 * ⚠ `body_middle` MOVED TO PRESENT-AND-ARRAY 2026-09-05, same reasoning as
	 *   `question` above: the approved visit set has no middle block.
	 */
	if ( ! isset( $copy['body_middle'] ) || ! is_array( $copy['body_middle'] ) ) {
		return false;
	}

	foreach ( $copy['links'] as $link ) {
		if ( ! is_array( $link ) || empty( $link['label'] ) || empty( $link['url'] ) ) {
			return false;
		}
	}

	/*
	 * ⚠ `delay_days` MAY NOW BE AN ARRAY. See the note on the visit set: one
	 *   lane legitimately has two delays. A scalar is still accepted so the
	 *   superseded 21-day set and any existing filter keep validating.
	 */
	if ( ! isset( $copy['delay_days'] ) ) {
		return false;
	}

	if ( is_array( $copy['delay_days'] ) ) {
		if ( empty( $copy['delay_days'] ) ) {
			return false;
		}

		foreach ( $copy['delay_days'] as $day ) {
			if ( ! is_numeric( $day ) ) {
				return false;
			}
		}

		return true;
	}

	return ! empty( $copy['delay_days'] ) && is_numeric( $copy['delay_days'] );
}

/**
 * Is the delay this order will actually use one the copy is TRUE at?
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE 1.19.317 INTERLOCK, ADAPTED RATHER THAN BYPASSED.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The original compared one number to one number, because there was one delay
 * and one sentence — *"Your book turned up about three weeks ago"* — that was
 * true at exactly 21 days and a lie at any other. That reasoning is unchanged
 * and is still the reason this function exists. What changed is the shape of
 * the world it is checking:
 *
 *   - the delay is now PER ORDER (7 or 10 on the visit lane, 10 on the web
 *     lane), so the question became "is THIS order's delay in the set this
 *     copy is true at" rather than "does the one number equal the other";
 *   - touch 2's delay is measured from touch 1, not from an anchor, so it is
 *     checked against `BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS`.
 *
 * ⛔ IT STILL FAILS LOUD AND CLOSED. A delay outside the copy's declared set
 *    is `copy_delay_mismatch`, the order is declined by name, and the run
 *    summary counts it. It does not quietly send a false sentence and it does
 *    not quietly send nothing.
 *
 * @param int                 $touch 1 or 2.
 * @param WC_Order|null|mixed $order Order.
 * @return bool
 */
function bhp_review_ask_copy_matches_delay( $touch = 1, $order = null ) {
	$touch = ( 2 === (int) $touch ) ? 2 : 1;
	$copy  = bhp_review_ask_copy( $touch, $order );

	if ( ! isset( $copy['delay_days'] ) ) {
		return false;
	}

	$declared = is_array( $copy['delay_days'] ) ? $copy['delay_days'] : array( $copy['delay_days'] );
	$declared = array_map( 'intval', $declared );

	if ( 2 === $touch ) {
		$effective = (int) apply_filters( 'bhp_review_ask_touch2_delay_days', BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS, $order );
	} elseif ( $order instanceof WC_Order ) {
		$effective = bhp_review_ask_touch1_delay_days( $order );
	} else {
		/*
		 * ⚠ NO ORDER IN HAND — the CLI `status` screen and the admin email
		 *   preview both call in like this. Both lane delays are accepted so a
		 *   status screen does not report a mismatch that no real order has.
		 */
		return true;
	}

	return in_array( $effective, $declared, true );
}

/* -------------------------------------------------------------------------
 * ⭐ SEAL 965 · MERGE SLOTS
 *
 * ⛔ EVERY SLOT RESOLVES TO SOMETHING TRUE OR TO A NEUTRAL FALLBACK. Not one
 *    of them may render as an empty string, because a customer reading "Thank
 *    you for picking up  at " is worse than any fallback wording, and not one
 *    of them may guess. A name that is not on the order is not invented.
 * ---------------------------------------------------------------------- */

/**
 * The child's first name for this order, or the neutral fallback.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠⚠ NO CHILD-NAME ORDER META KEY HAS BEEN VERIFIED TO EXIST. STATED PLAINLY
 *    RATHER THAN IMPLIED BY A HOPEFUL `get_meta()` CALL.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The school-visit checkout flow is owned by the BUNDLE PLUGIN, which is not
 * in this repository, and this build could not reach either environment to
 * read a real order's meta (the session's SSH permission was denied; recorded
 * in the workstream report). The keys tried below are therefore CANDIDATES,
 * not confirmed fields, and the function is written so that finding none of
 * them is the ordinary case rather than an error.
 *
 * ⭐ THE FALLBACK IS THE BRIEF'S OWN WORD, `your reader`, and it is what
 *    Gimli's 16 hand-built drafts already used for the two orders with two
 *    children on them (`Business OS\ANDREW-REVIEW\2026-09-05\REVIEW-ASKS\
 *    SUMMARY.md`). So the automated note and the hand-sent note say the same
 *    thing in the same situation.
 *
 * ⛔ NO NAME IS EVER DERIVED FROM THE BILLING NAME. The billing name is the
 *    PARENT. Addressing a parent's own first name to their child is a mistake
 *    a reader notices immediately and never forgets.
 *
 * @param WC_Order|mixed $order Order.
 * @return string
 */
function bhp_review_ask_child_first_name( $order ) {
	$name = '';

	if ( $order instanceof WC_Order ) {
		/**
		 * Filter the order meta keys searched for a child's first name.
		 *
		 * ⚠ UNVERIFIED CANDIDATES. Replace with the real key once somebody has
		 *   read a live visit order over SSH. Until then the fallback runs and
		 *   the email is still correct, just less personal.
		 *
		 * @since 1.19.362
		 * @param string[] $keys Meta keys, in priority order.
		 */
		$keys = (array) apply_filters(
			'bhp_review_ask_child_name_meta_keys',
			array(
				'_bhp_school_visit_child_first_name',
				'_bhp_school_visit_child_name',
				'_bhp_child_first_name',
				'_bhp_child_name',
			)
		);

		foreach ( $keys as $key ) {
			$raw = trim( (string) $order->get_meta( (string) $key ) );

			if ( '' === $raw ) {
				continue;
			}

			/*
			 * ⭐ FIRST TOKEN ONLY, and only when it looks like a name. A field
			 *   holding "Ava and Noah" (the two-children case Gimli hit twice
			 *   in sixteen orders) would otherwise render "Ava and Noah has had
			 *   a few nights with it". ⛔ Two children is exactly the case the
			 *   fallback exists for, so it is detected and handed back.
			 */
			if ( preg_match( '/\b(and|&|\+|,)\b/i', $raw ) || false !== strpos( $raw, ',' ) ) {
				break;
			}

			$parts = preg_split( '/\s+/', $raw );
			$first = isset( $parts[0] ) ? trim( (string) $parts[0] ) : '';

			if ( '' !== $first && preg_match( "/^[\p{L}][\p{L}'\-]*$/u", $first ) ) {
				$name = $first;
				break;
			}
		}
	}

	/**
	 * Filter the child first name used in review-ask copy.
	 *
	 * @since 1.19.362
	 * @param string        $name  Resolved name, or '' when none is known.
	 * @param WC_Order|null $order Order.
	 */
	$name = trim( (string) apply_filters( 'bhp_review_ask_child_first_name', $name, $order ) );

	return '' !== $name ? $name : BHP_REVIEW_ASK_CHILD_FALLBACK;
}

/**
 * Is a real child first name known for this order?
 *
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function bhp_review_ask_child_first_name_is_known( $order ) {
	return BHP_REVIEW_ASK_CHILD_FALLBACK !== bhp_review_ask_child_first_name( $order );
}

/**
 * The school name for a visit order, from the registry.
 *
 * ⛔ THE REGISTRY'S OWN `school` VALUE, NEVER THE SLUG PRETTIFIED. Merry's
 *    merge table is explicit: *"The school's own name, never 'your school'"* —
 *    and `adams-2026-08-28` title-cased is "Adams 2026 08 28", not a school.
 *
 * @param WC_Order|mixed $order Order.
 * @return string School name, or '' when unknown.
 */
function bhp_review_ask_school_name( $order ) {
	$slug = bhp_review_ask_is_visit_order( $order ) ? bhp_visit_email_order_slug( $order ) : '';

	if ( '' === $slug || ! function_exists( 'bhp_school_visit_records' ) ) {
		return '';
	}

	$records = bhp_school_visit_records();

	if ( ! is_array( $records ) || empty( $records[ $slug ]['school'] ) ) {
		return '';
	}

	return trim( wp_strip_all_tags( (string) $records[ $slug ]['school'] ) );
}

/**
 * The first chapter book on this order, as an adventure key.
 *
 * @param WC_Order|mixed $order Order.
 * @return string Key, or '' when the order holds no chapter book.
 */
function bhp_review_ask_first_chapter_book_key( $order ) {
	$keys = bhp_review_ask_chapter_book_keys( $order );

	return isset( $keys[0] ) ? (string) $keys[0] : '';
}

/**
 * The display title of the first chapter book on this order.
 *
 * ⭐ THE SHORT TITLE, via `bhp_review_book_title()` — "The Mariana Trench",
 *    not "Adventures of Charlotte and Henry: The Mariana Trench", which is what
 *    Merry's merge table specifies and what a parent calls the book.
 *
 * @param WC_Order|mixed $order Order.
 * @return string
 */
function bhp_review_ask_book_title( $order ) {
	$key = bhp_review_ask_first_chapter_book_key( $order );

	if ( '' === $key || ! function_exists( 'bhp_review_book_title' ) ) {
		return '';
	}

	return (string) bhp_review_book_title( $key );
}

/**
 * The site review page URL for the first chapter book on this order.
 *
 * @param WC_Order|mixed $order Order.
 * @return string Absolute URL, or ''.
 */
function bhp_review_ask_review_link( $order ) {
	$key = bhp_review_ask_first_chapter_book_key( $order );

	if ( '' === $key || ! function_exists( 'bhp_review_page_url' ) ) {
		return '';
	}

	return (string) bhp_review_page_url( $key );
}

/**
 * Resolve every merge slot for one order.
 *
 * @param WC_Order|null|mixed $order Order.
 * @return array<string,string> Slot token => replacement.
 */
function bhp_review_ask_merge_values( $order ) {
	$parent = '';

	if ( $order instanceof WC_Order ) {
		$parent = trim( (string) $order->get_billing_first_name() );
	}

	$values = array(
		/*
		 * ⚠ `there` IS THE FALLBACK, NOT AN EMPTY STRING, so the greeting reads
		 *   "Hi there," exactly as the 1.19.317 template already does for a
		 *   buyer with no first name. Store-synced buyers frequently have none.
		 */
		'{ParentFirstName}' => '' !== $parent ? $parent : __( 'there', 'brave-hearts' ),
		'{ChildFirstName}'  => bhp_review_ask_child_first_name( $order ),
		'{SchoolName}'      => bhp_review_ask_school_name( $order ),
		'{BookTitle}'       => bhp_review_ask_book_title( $order ),
		'{ReviewLink}'      => bhp_review_ask_review_link( $order ),
	);

	/**
	 * Filter the resolved merge values for one order.
	 *
	 * @since 1.19.362
	 * @param array<string,string> $values Slot => replacement.
	 * @param WC_Order|null        $order  Order.
	 */
	return (array) apply_filters( 'bhp_review_ask_merge_values', $values, $order );
}

/**
 * Are all the slots this copy uses resolvable for this order?
 *
 * ⛔⛔ THIS IS A SEND GATE, NOT A FORMATTING NICETY. `{SchoolName}` is empty
 *     for an order whose visit the registry has forgotten, `{BookTitle}` and
 *     `{ReviewLink}` are empty for an order that somehow holds no chapter
 *     book, and an email that renders *"Thank you for picking up  at ."* has
 *     gone to a real parent and cannot be recalled. The order is declined
 *     `unresolved_merge_slot` instead and the run summary names it.
 *
 * ⚠ `{ChildFirstName}` and `{ParentFirstName}` are NOT checked, because both
 *   have designed fallbacks and neither can be empty.
 *
 * @param array               $copy  Copy set.
 * @param WC_Order|null|mixed $order Order.
 * @return bool
 */
function bhp_review_ask_merge_is_complete( $copy, $order ) {
	$blob = wp_json_encode( $copy );

	if ( ! is_string( $blob ) ) {
		return false;
	}

	$values = bhp_review_ask_merge_values( $order );

	foreach ( array( '{SchoolName}', '{BookTitle}', '{ReviewLink}' ) as $slot ) {
		if ( false === strpos( $blob, $slot ) ) {
			continue;
		}

		if ( ! isset( $values[ $slot ] ) || '' === trim( (string) $values[ $slot ] ) ) {
			return false;
		}
	}

	return true;
}

/* =========================================================================
 * ⭐⭐ 1.19.364 · THE STAR ROW — FIVE LINKS, ONE DESTINATION
 *
 * ANDREW, SEAL 977: *"Is there anyway to put the 5 stars in the email and all
 * they have to do is click 5 stars and it goes direct to the website?"*
 *
 * ⛔⛔ ALL FIVE ARE ALWAYS BUILT, AND NOTHING HERE STEERS TOWARD FIVE. Merry's
 *     V2 §5 rule 1, and it is not a styling preference: *"A star row that
 *     visually steers toward five is a solicitation for a five-star review, and
 *     it is exactly the thing that makes a review programme indefensible."*
 *     ⚠ Whoever touches the templates: no row may be bolded, coloured,
 *     enlarged, reordered or given a bigger tap target than its four
 *     neighbours, and there is no default selection without the parameter.
 *
 * ⛔ NO COUNT, NO AVERAGE, NO "JOIN N OTHER PARENTS". None exists to quote and
 *    inventing one is the never-invent rule.
 *
 * ⭐ DESCENDING, 5 TO 1, matching the site form's own order. Convention, not
 *    emphasis — the page the reader lands on lists them the same way.
 * ====================================================================== */

/**
 * The five star links for this order's first chapter book.
 *
 * ⛔ AN EMPTY ARRAY IS A SEND-STOPPER, NOT A DEGRADED EMAIL. It means no
 *    per-title review URL resolved, and `bhp_review_ask_decline_reason()`
 *    returns `unresolved_merge_slot`. Merry's V2 §2 merge table says the same
 *    in words: *"If no per-title review URL resolves, do not send."*
 *
 * ⚠ THE TOKEN IS MINTED PER ORDER AND IS THE SAME ON ALL FIVE LINKS. It
 *   pre-fills a name and an email box and carries no privilege whatsoever —
 *   see `bhp_review_prefill_verify()` in `inc/reviews.php`. When it cannot be
 *   minted (no WordPress salt, no billing email) the links are built WITHOUT
 *   it and still work; the reader just types their own name, as they do today.
 *
 * @since 1.19.364
 * @param WC_Order|mixed $order Order.
 * @return array<int,array> Rows of rating, label, url — 5 first.
 */
function bhp_review_ask_star_row( $order ) {
	if ( ! function_exists( 'bhp_review_star_labels' ) || ! function_exists( 'bhp_review_star_url' ) ) {
		return array();
	}

	$key = bhp_review_ask_first_chapter_book_key( $order );

	if ( '' === $key ) {
		return array();
	}

	$token = '';

	if ( $order instanceof WC_Order && function_exists( 'bhp_review_prefill_token' ) ) {
		$token = (string) bhp_review_prefill_token(
			(int) $order->get_id(),
			(string) $order->get_billing_email(),
			$key
		);
	}

	$rows = array();

	foreach ( bhp_review_star_labels() as $rating => $label ) {
		$url = bhp_review_star_url( $key, (int) $rating, $token );

		/*
		 * ⛔ ONE UNRESOLVABLE STAR VOIDS THE WHOLE ROW. A row of four is a
		 *    steered row, which is the one thing rule 1 above forbids.
		 */
		if ( '' === $url ) {
			return array();
		}

		$rows[] = array(
			'rating' => (int) $rating,
			'label'  => (string) $label,
			'url'    => $url,
		);
	}

	return $rows;
}

/**
 * Does this copy set render a star row?
 *
 * @since 1.19.364
 * @param array $copy Copy set.
 * @return bool
 */
function bhp_review_ask_copy_has_stars( $copy ) {
	return is_array( $copy ) && ! empty( $copy['stars'] );
}

/**
 * Walk a copy set and substitute every merge slot.
 *
 * ⚠ STRINGS ONLY, RECURSIVELY, AND NON-STRINGS ARE LEFT ALONE — `delay_days`
 *   is an array of ints and `approved` is a bool.
 *
 * @param array               $copy  Copy set.
 * @param WC_Order|null|mixed $order Order.
 * @return array
 */
function bhp_review_ask_merge_copy( $copy, $order ) {
	if ( ! is_array( $copy ) ) {
		return $copy;
	}

	$values = bhp_review_ask_merge_values( $order );
	$search = array_keys( $values );
	$repl   = array_values( $values );

	$walk = function ( $value ) use ( &$walk, $search, $repl ) {
		if ( is_string( $value ) ) {
			return str_replace( $search, $repl, $value );
		}

		if ( is_array( $value ) ) {
			return array_map( $walk, $value );
		}

		return $value;
	};

	return array_map( $walk, $copy );
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
 * ⭐ SO THIS IS A SEAM, NOT A GUESS. `chief-of-staff`/`connected-operator` read the four billing
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

	/*
	 * ⭐ SEAL 965. WHICH TOUCH IS THIS ORDER NEXT IN LINE FOR? `0` means both
	 *    have been dealt with and the sequence is finished for this order,
	 *    which is the replacement for the old flat `already_sent`. The old
	 *    slug is deliberately kept for the touch-2-done case so the run
	 *    summary, the KPI report and the suite all keep reading the same word
	 *    for "this order is finished".
	 */
	$touch = bhp_review_ask_next_touch( $order );

	if ( 0 === $touch ) {
		return 'already_sent';
	}

	/*
	 * ⛔⛔ SUPERSEDED 2026-09-05 BY SEAL 965, PRESERVED AS A SWITCH RATHER THAN
	 *     DELETED. Until today this read:
	 *
	 *       if ( bhp_review_ask_is_visit_order( $order ) ) {
	 *           return 'school_visit_already_asked';
	 *       }
	 *
	 *     It closed the Adams double-ask structurally (this file's header,
	 *     hazard 1). Seal 965 makes the visit order the PRIMARY lane, so the
	 *     exclusion now defaults OFF. ⚠ It is left reachable because the
	 *     visit completed-order email still carries its own Amazon review ask
	 *     and `CYCLE179-LD-40` is open on whether that is one ask too many. If
	 *     Andrew rules that it is, this filter is the one-line answer and no
	 *     code has to be rewritten under time pressure.
	 */
	if ( 'visit' === bhp_review_ask_lane( $order )
		&& (bool) apply_filters( 'bhp_review_ask_exclude_visit_orders', false, $order ) ) {
		return 'school_visit_already_asked';
	}

	$now_ts = $now ? (int) $now : time();

	if ( 1 === $touch ) {
		$due = bhp_review_ask_touch1_due_timestamp( $order );

		if ( ! $due ) {
			return 'no_anchor';
		}

		if ( $now_ts < $due ) {
			return 'not_due';
		}
	} else {
		$due = bhp_review_ask_touch2_due_timestamp( $order );

		/*
		 * ⛔ FAIL CLOSED. Touch 1 is recorded as done but nothing recorded
		 *    WHEN, so there is no honest date to count seven days from. See
		 *    `bhp_review_ask_touch1_sent_timestamp()`.
		 */
		if ( ! $due ) {
			return 'touch1_date_unknown';
		}

		if ( $now_ts < $due ) {
			return 'not_due';
		}
	}

	/*
	 * ⛔ THE MORNING WINDOW. Checked AFTER due-ness so a dry run at any hour
	 *    still reports "would send today" for everything that is genuinely due
	 *    and names the window as the only thing holding it.
	 */
	if ( ! bhp_review_ask_in_send_window( $now_ts ) ) {
		return 'outside_send_window';
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

	/*
	 * ⛔ THE 90-DAY CUSTOMER COOLDOWN GATES TOUCH 1 ONLY. Touch 1 writes the
	 *    customer stamp, so running this on touch 2 would have the stamp touch
	 *    1 wrote seven days ago decline touch 2 every single time, silently
	 *    turning Andrew's approved sequence back into one ask. The reasoning is
	 *    recorded at `BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS`.
	 */
	if ( 1 === $touch ) {
		$last = bhp_review_ask_customer_last( $email );
		if ( $last ) {
			$cooldown = (int) apply_filters( 'bhp_review_ask_customer_cooldown_days', BHP_REVIEW_ASK_CUSTOMER_COOLDOWN_DAYS );
			if ( ( $now_ts - $last ) < ( $cooldown * DAY_IN_SECONDS ) ) {
				return 'customer_cooldown';
			}
		}
	}

	/*
	 * ⭐⭐ THE REMINDER'S OWN SUPPRESSION, AND IT IS THE POINT OF TOUCH 2 BEING
	 *     CONDITIONAL AT ALL. Seal 965: the reminder goes only to somebody who
	 *     has NOT reviewed. Unapproved reviews count — see
	 *     `bhp_review_ask_has_site_review()` for why that is the load-bearing
	 *     half.
	 */
	if ( 2 === $touch && bhp_review_ask_has_site_review( $email ) ) {
		return 'already_reviewed';
	}

	/*
	 * ⛔⛔ NO APPROVED COPY, NO SEND. The web touch-1 set and the touch-2 set
	 *     ship as PENDING-COPY placeholders with `approved => false`, and this
	 *     is what guarantees placeholder text can never reach a customer even
	 *     if somebody switches the engine on early. It is a per-order gate
	 *     rather than a global halt so that the visit touch-1 lane, whose copy
	 *     IS approved, keeps running while the other two wait for Merry.
	 */
	/*
	 * ⛔⛔ RAW, NOT MERGED, SINCE 1.19.364. `bhp_review_ask_copy()` returns the
	 *     set with every slot already substituted, and
	 *     `bhp_review_ask_merge_is_complete()` below inspects the set for
	 *     UNRESOLVED SLOTS. Handing it the merged array made that gate a no-op
	 *     for every order ever checked. See `bhp_review_ask_copy_raw()`.
	 */
	$copy = bhp_review_ask_copy_raw( $touch, $order );

	if ( empty( $copy['approved'] ) ) {
		return 'copy_not_approved';
	}

	if ( ! bhp_review_ask_copy_matches_delay( $touch, $order ) ) {
		return 'copy_delay_mismatch';
	}

	if ( ! bhp_review_ask_merge_is_complete( $copy, $order ) ) {
		return 'unresolved_merge_slot';
	}

	/*
	 * ⛔ 1.19.364 · A COPY SET THAT PROMISES FIVE STARS MUST BE ABLE TO BUILD
	 *    FIVE. `bhp_review_ask_star_row()` returns an empty array when no
	 *    per-title review URL resolves, and an email whose only call to action
	 *    is a row that did not render is a wasted send to a real parent.
	 *    Merry's V2 §2 merge table: *"If no per-title review URL resolves, do
	 *    not send."* Same slug as the other unresolved slot, deliberately, so
	 *    the run summary counts one kind of failure by one name.
	 */
	if ( bhp_review_ask_copy_has_stars( $copy ) && array() === bhp_review_ask_star_row( $order ) ) {
		return 'unresolved_merge_slot';
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
function bhp_review_ask_log_send( $order, $touch = 0 ) {
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
		// ⭐ WHICH TOUCH, so a KPI read can separate first asks from reminders
		// without re-deriving it from two meta fields per order.
		'touch'        => (int) $touch,
		'lane'         => bhp_review_ask_lane( $order ),
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

	/*
	 * ═══════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.19.363 — THE MASTER SWITCH STOPS A SEND. IT NO LONGER STOPS A DRY
	 *     RUN, AND THAT IS THE WHOLE POINT OF A DRY RUN.
	 * ═══════════════════════════════════════════════════════════════════════
	 *
	 * ⚠ THE DEFECT THIS REPLACES, PRESERVED RATHER THAN DELETED. Until now this
	 *   read `if ( ! bhp_review_ask_is_enabled() ) { halted = 'disabled';
	 *   return; }` for EVERY caller, dry included. The effect on staging,
	 *   measured 2026-09-05: `wp bhp review-ask dry` answered
	 *   "engine is disabled ... Nothing done" for every `--as-of` date, so the
	 *   ONLY way to find out what the engine would do was to enable it. ⛔ That
	 *   is exactly backwards. The preview has to be readable BEFORE the switch
	 *   is thrown, or the switch gets thrown to read the preview — against real
	 *   parents, with copy that has not been approved.
	 *
	 * ⛔⛔ IT STILL CANNOT SEND, AND NOT BECAUSE OF A PROMISE IN A COMMENT.
	 *     THREE INDEPENDENT STRUCTURAL REASONS, any one of which is sufficient:
	 *       1. `$args['dry']` `continue`s the loop before `bhp_review_ask_send()`
	 *          is reached, so the mailer is never constructed.
	 *       2. `bhp_review_ask_send()` itself refuses when the engine is
	 *          disabled (added 1.19.363, immediately below its own docblock),
	 *          so even a caller that reached it directly gets nothing.
	 *       3. The cron entry point is `bhp_review_ask_cron_run()`, which is
	 *          gated on the master switch separately and never passes `dry`.
	 *     ⭐ And nothing on this path writes: no marker, no cooldown stamp, no
	 *     ledger row. `$summary['sent']` on a dry run counts WOULD-sends and is
	 *     labelled as such on every line it prints.
	 *
	 * ⭐ THE DISABLED STATE IS REPORTED, NOT HIDDEN. `halted` stays `disabled`
	 *    so no reader of a summary can mistake a dry run for a live one, and
	 *    the log line says so in words.
	 */
	$summary['enabled'] = bhp_review_ask_is_enabled();

	if ( ! $summary['enabled'] ) {
		$summary['halted'] = 'disabled';

		if ( ! $args['dry'] ) {
			$say( 'Review-ask engine is disabled (option bhp_review_ask_enabled != yes). Nothing done.' );
			return $summary;
		}

		$say( 'NOTE: the engine is DISABLED (option bhp_review_ask_enabled != yes).' );
		$say( 'This is a DRY RUN, so the preview below is produced anyway. Nothing is sent and nothing is written.' );
		$say( 'Every "would send" line below would NOT go out today; enabling the engine is a separate, deliberate act.' );
	}

	/*
	 * ⛔ THE COPY/DELAY INTERLOCK MOVED FROM HERE TO PER-ORDER, 2026-09-05.
	 *
	 * ⚠ THE SUPERSEDED GLOBAL HALT IS PRESERVED IN THIS COMMENT rather than
	 *   deleted, because a reader who knows the 1.19.317 engine will look for
	 *   it here and needs to know where it went:
	 *
	 *     if ( ! bhp_review_ask_copy_matches_delay() ) {
	 *         $summary['halted'] = 'copy_delay_mismatch';
	 *         ...
	 *         return $summary;
	 *     }
	 *
	 * ⭐ WHY IT COULD NOT STAY GLOBAL. There is no longer ONE delay to compare:
	 *    a visit order due at +7 and a visit order due at +10 are both correct
	 *    on the same run, and the web lane has a third. A global check would
	 *    have to pick one and would then halt the entire run because a
	 *    DIFFERENT order used a different delay. ⛔ The interlock is not
	 *    weakened by moving: `bhp_review_ask_decline_reason()` runs it against
	 *    every single order and returns the same `copy_delay_mismatch` slug,
	 *    which the summary still counts by name. It is now more precise, not
	 *    less strict — it declines the order that is wrong instead of the run.
	 */
	$summary['window_open'] = bhp_review_ask_in_send_window( (int) $args['now'] );

	if ( '' === bhp_review_ask_postal_address() ) {
		$summary['halted'] = 'no_postal_address';
		$say( 'HALTED: no postal address resolves, so a CAN-SPAM footer cannot be rendered. Nothing sent.' );

		/*
		 * ⭐ 1.19.363 — SAME REASONING AS THE MASTER SWITCH ABOVE. A dry run
		 *    that stops here tells an operator only that the address is
		 *    missing, which they can already see in `status`. Continuing shows
		 *    the schedule AND still names the blocker: the per-order
		 *    `no_postal_address` decline in `bhp_review_ask_decline_reason()`
		 *    fires for every order, so the preview reads "0 would send,
		 *    declined no_postal_address: N" and the halt is unmissable.
		 */
		if ( ! $args['dry'] ) {
			return $summary;
		}
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

	$bhp_candidates = bhp_review_ask_candidates( (int) $args['scan'] );

	/*
	 * ════════════════════════════════════════════════════════════════════
	 * ⭐⭐ 1.19.364 — "examined: 0" MUST NEVER AGAIN BE THE WHOLE ANSWER.
	 * ════════════════════════════════════════════════════════════════════
	 *
	 * ⛔ THE OBSERVED PROBLEM, 2026-09-05 ON STAGING: `wp bhp review-ask dry`
	 *    and the same command at `--as-of=2026-09-10` and `--as-of=2026-09-13`
	 *    all reported **examined 0**, which is indistinguishable from
	 *    "everything was skipped" and told the operator nothing about which of
	 *    the two very different causes was in play:
	 *
	 *      (a) THE POOL IS EMPTY. `bhp_review_ask_candidates()` asks for
	 *          `status => completed`, `type => shop_order`. If staging's copy of
	 *          the database holds no COMPLETED shop orders — because the sync
	 *          predates them, because they were pruned, or because they are all
	 *          in some other status — the loop body never runs even once and no
	 *          decline reason is ever produced, because a decline reason is a
	 *          property of an order and there are no orders.
	 *
	 *      (b) THE POOL IS FULL AND EVERY ORDER DECLINED. That case already
	 *          reported itself properly through `$summary['declined']`.
	 *
	 *    ⚠ (a) and (b) printed the SAME "examined 0" line only in case (a);
	 *    the distinction was invisible because nothing counted the pool
	 *    separately from the loop. It is counted here now.
	 *
	 * ⭐ SO THE POOL IS DESCRIBED BEFORE IT IS WALKED, by status, using a
	 *    COUNT-ONLY query (`return => ids`, `limit => -1`) that loads no order
	 *    objects. This is the line that answers Gandalf's question directly:
	 *    if `total shop orders (any status)` is 0, staging simply does not have
	 *    the sixteen visit orders; if it is non-zero but `completed` is 0, they
	 *    are present in another status and the candidate query is correctly
	 *    skipping them.
	 *
	 * ⛔ NO EMAIL ADDRESS, NAME OR ORDER TOTAL IS PRINTED BY ANY LINE BELOW.
	 *    Order ids only — same rule the KPI ledger follows.
	 */
	$summary['pool'] = array();

	if ( function_exists( 'wc_get_orders' ) ) {
		foreach ( array( 'any', 'completed', 'processing', 'on-hold', 'pending', 'cancelled', 'refunded', 'failed' ) as $bhp_status ) {
			$bhp_ids = wc_get_orders(
				array(
					'type'   => 'shop_order',
					'status' => 'any' === $bhp_status ? array_keys( wc_get_order_statuses() ) : array( $bhp_status ),
					'limit'  => -1,
					'return' => 'ids',
				)
			);

			$summary['pool'][ $bhp_status ] = is_array( $bhp_ids ) ? count( $bhp_ids ) : 0;
		}
	} else {
		$say( 'POOL: wc_get_orders() is not available. WooCommerce is not loaded, so there is no pool to describe.' );
	}

	$summary['pool']['scanned'] = count( $bhp_candidates );

	$say( 'POOL: shop orders in any status: ' . ( isset( $summary['pool']['any'] ) ? $summary['pool']['any'] : 'unknown' )
		. ' | completed: ' . ( isset( $summary['pool']['completed'] ) ? $summary['pool']['completed'] : 'unknown' )
		. ' | this run scans: ' . count( $bhp_candidates ) . ' (scan limit ' . (int) $args['scan'] . ')' );

	if ( empty( $bhp_candidates ) ) {
		/*
		 * ⭐ THE EMPTY-POOL EXPLANATION IS SPELLED OUT RATHER THAN LEFT TO BE
		 *    INFERRED FROM A ZERO, because a zero was exactly what was
		 *    uninformative on staging.
		 */
		$say( 'EXAMINED 0, AND THE REASON IS THE POOL, NOT THE GATES.' );
		$say( '  The candidate query is: type=shop_order, status=completed, limit=' . (int) $args['scan'] . '.' );

		if ( isset( $summary['pool']['any'] ) && 0 === (int) $summary['pool']['any'] ) {
			$say( '  This database holds NO shop orders in ANY status. Nothing was skipped; there was nothing to skip.' );
			$say( '  On staging that means the orders are not in this copy of the database.' );
		} elseif ( isset( $summary['pool']['completed'] ) && 0 === (int) $summary['pool']['completed'] ) {
			$say( '  Shop orders EXIST but none is in status "completed", so the candidate query correctly returns none.' );
			$say( '  Counts by status are in the POOL line above. The engine anchors on date_completed and cannot' );
			$say( '  schedule an order that has never completed.' );
		} else {
			$say( '  Orders exist and some are completed, but the query returned none. Suspect HPOS (this store runs it),' );
			$say( '  an order-type filter, or a wc_get_orders filter added by a plugin.' );
		}
	}

	foreach ( $bhp_candidates as $order ) {
		$summary['examined']++;

		$reason = bhp_review_ask_decline_reason( $order, (int) $args['now'] );

		if ( '' !== $reason ) {
			$summary['declined'][ $reason ] = isset( $summary['declined'][ $reason ] ) ? $summary['declined'][ $reason ] + 1 : 1;

			/*
			 * ⭐ 1.19.364 · EVERY SKIPPED ORDER NAMES ITS OWN REASON ON A DRY
			 *    RUN, with the facts needed to argue with it: which lane, how
			 *    many chapter books, which touch it was next in line for, and
			 *    the date that touch becomes due. The aggregate `declined`
			 *    counts stay exactly as they were — this adds detail, it does
			 *    not replace the summary. ⚠ Dry only: a live run walks the same
			 *    orders every morning and would fill the log with the same
			 *    lines forever.
			 */
			if ( $args['dry'] ) {
				$bhp_touch = bhp_review_ask_next_touch( $order );
				$bhp_due   = ( 2 === $bhp_touch )
					? bhp_review_ask_touch2_due_timestamp( $order )
					: bhp_review_ask_touch1_due_timestamp( $order );

				$summary['orders'][] = array(
					'order_id' => (int) $order->get_id(),
					'touch'    => $bhp_touch,
					'lane'     => bhp_review_ask_lane( $order ),
					'books'    => bhp_review_ask_chapter_book_count( $order ),
					'due'      => $bhp_due ? gmdate( 'Y-m-d', $bhp_due ) : 'unknown',
					'result'   => 'skipped',
					'reason'   => $reason,
				);

				$say( 'DRY: SKIP order ' . $order->get_id()
					. ' | ' . bhp_review_ask_lane( $order ) . ' lane'
					. ' | ' . bhp_review_ask_chapter_book_count( $order ) . ' chapter book(s)'
					. ' | next touch ' . ( $bhp_touch ? $bhp_touch : 'none, finished' )
					. ' | due ' . ( $bhp_due ? gmdate( 'Y-m-d', $bhp_due ) : 'unknown' )
					. ' | REASON: ' . $reason );
			}

			continue;
		}

		$touch = bhp_review_ask_next_touch( $order );

		if ( $args['dry'] ) {
			$summary['orders'][] = array(
				'order_id' => (int) $order->get_id(),
				'touch'    => $touch,
				'lane'     => bhp_review_ask_lane( $order ),
				'books'    => bhp_review_ask_chapter_book_count( $order ),
				'result'   => 'would_send',
			);
			$summary['sent']++;
			$say( 'DRY: would send TOUCH ' . $touch . ' for order ' . $order->get_id()
				. ' (' . bhp_review_ask_lane( $order ) . ' lane, '
				. bhp_review_ask_chapter_book_count( $order ) . ' chapter book(s))' );

			if ( $summary['sent'] >= $remaining ) {
				break;
			}
			continue;
		}

		$sent = bhp_review_ask_send( $order );

		$summary['orders'][] = array(
			'order_id' => (int) $order->get_id(),
			'touch'    => $touch,
			'lane'     => bhp_review_ask_lane( $order ),
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
	/*
	 * ⛔⛔ 1.19.363 — THE MASTER SWITCH, RE-ASSERTED AT THE SEND ITSELF.
	 *
	 * ⭐ WHY IT IS HERE AND NOT ONLY IN `bhp_review_ask_run()`. That function's
	 *    disabled-halt became dry-run-tolerant in 1.19.363 so the schedule can
	 *    be previewed before the switch is thrown. The dry path structurally
	 *    cannot reach this function, but "structurally cannot" is an argument,
	 *    and this file's own rule is that a send path never trusts its caller.
	 *    ⭐ So the switch is now enforced at the only place that actually hands
	 *    a message to the mailer. THIS IS THE LAST GATE BEFORE A REAL PARENT.
	 */
	if ( ! bhp_review_ask_is_enabled() ) {
		return false;
	}

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

	/*
	 * ⭐ THE TOUCH IS RESOLVED HERE AND HANDED DOWN, rather than re-derived
	 *    inside the email class. Two places asking "which touch is this" is how
	 *    a subject line for touch 1 ends up on a body for touch 2.
	 */
	$sent = $email->trigger( (int) $order->get_id(), $order, bhp_review_ask_next_touch( $order ) );

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
function bhp_review_ask_mark_sent( $order, $touch = 0 ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	/*
	 * ⚠ THE TOUCH IS READ BEFORE ANYTHING IS WRITTEN. `bhp_review_ask_next_touch()`
	 *   answers from the markers, so writing first and asking after would
	 *   always report touch 2. Callers that know the touch pass it in.
	 */
	$touch = (int) $touch;
	$touch = ( 1 === $touch || 2 === $touch ) ? $touch : bhp_review_ask_next_touch( $order );

	$now = current_time( 'mysql' );

	if ( 2 === $touch ) {
		$order->update_meta_data( BHP_REVIEW_ASK_TOUCH2_SENT_META, $now );
	} else {
		$order->update_meta_data( BHP_REVIEW_ASK_SENT_META, $now );

		/*
		 * ⭐ THE EXPLICIT TOUCH-1 DATE, WRITTEN AT THE SAME INSTANT AS THE
		 *    MARKER. Touch 2 counts seven days from this field and declines
		 *    when it is missing, so it must never be written separately or
		 *    later. See `BHP_REVIEW_ASK_TOUCH1_AT_META`.
		 */
		$order->update_meta_data( BHP_REVIEW_ASK_TOUCH1_AT_META, $now );

		/*
		 * ⛔ THE CUSTOMER COOLDOWN STAMP IS WRITTEN ON TOUCH 1 ONLY. Writing it
		 *    again on touch 2 would push a genuinely new order's first ask out
		 *    by an extra week for no reason Andrew asked for.
		 */
		bhp_review_ask_record_customer( $order->get_billing_email(), $order );
	}

	$order->save();

	bhp_review_ask_log_send( $order, $touch );
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
 * `wp bhp review-ask <status|run|dry|plan|migrate|test-send>`.
 *
 * ⭐ THE OPERATOR SURFACE. A daily emailer that can only be observed by waiting
 *    a day is a daily emailer nobody can verify. `dry` answers "who would get
 *    one, and why is everybody else being skipped" without sending anything.
 *
 * @param array $args Positional args.
 * @return void
 */
function bhp_review_ask_cli( $args, $assoc_args = array() ) {
	$sub = isset( $args[0] ) ? $args[0] : 'status';

	$say = static function ( $line ) {
		WP_CLI::log( $line );
	};

	if ( 'status' === $sub ) {
		$stats = bhp_review_ask_stats();
		$say( 'enabled:            ' . ( bhp_review_ask_is_enabled() ? 'yes' : 'NO' ) );
		$say( 'visit delay 1 book: ' . BHP_REVIEW_ASK_VISIT_DELAY_ONE_BOOK . ' days after the visit date' );
		$say( 'visit delay 2+:     ' . BHP_REVIEW_ASK_VISIT_DELAY_MULTI_BOOK . ' days after the visit date' );
		$say( 'web delay:          ' . bhp_review_ask_delay_days() . ' days after completion   ** PENDING ANDREW **' );
		$say( 'touch 2 delay:      ' . BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS . ' days after touch 1' );
		$say( 'send window:        ' . BHP_REVIEW_ASK_WINDOW_START_HOUR . ':00 to ' . BHP_REVIEW_ASK_WINDOW_END_HOUR . ':00 site-local; open right now: ' . ( bhp_review_ask_in_send_window() ? 'yes' : 'NO' ) );
		$say( 'copy visit touch 1: ' . ( ! empty( bhp_review_ask_copy_visit_touch1()['approved'] ) ? 'APPROVED' : 'not approved - cannot send' ) );
		$say( 'copy web touch 1:   ' . ( ! empty( bhp_review_ask_copy_web_touch1()['approved'] ) ? 'APPROVED' : 'PENDING-COPY - cannot send' ) );
		$say( 'copy touch 2:       ' . ( ! empty( bhp_review_ask_copy_touch2()['approved'] ) ? 'APPROVED' : 'PENDING-COPY - cannot send' ) );
		$say( 'copy day 0:         ' . ( function_exists( 'bhp_visit_email_copy_is_approved' ) && bhp_visit_email_copy_is_approved( BHP_VISIT_EMAIL_DEFAULT_KEY ) ? 'APPROVED (_default)' : '_default not approved' ) );
		$say( 'daily cap:          ' . bhp_review_ask_daily_cap() );
		$say( 'postal address:     ' . ( bhp_review_ask_postal_address() ? bhp_review_ask_postal_address() : 'MISSING - sending is blocked' ) );
		$say( 'excluded:           ' . count( bhp_review_ask_excluded_emails() ) );
		$say( 'sent total:         ' . $stats['total'] );
		$say( 'sent today:         ' . $stats['today'] );
		$say( 'pending now:        ' . $stats['pending'] );
		$say( 'opt-outs:           ' . $stats['optouts'] );
		return;
	}

	if ( 'plan' === $sub ) {
		bhp_review_ask_cli_plan( $assoc_args, $say );
		return;
	}

	if ( 'test-send' === $sub ) {
		bhp_review_ask_cli_test_send( $assoc_args, $say );
		return;
	}

	if ( 'migrate' === $sub ) {
		bhp_review_ask_cli_migrate( $assoc_args, $say );
		return;
	}

	$dry = ( 'dry' === $sub );

	/*
	 * ⭐ 1.19.363 — `--as-of=<Y-m-d[ HH:MM:SS]>` MOVES THE CLOCK, DRY ONLY.
	 *
	 * ⛔ IT IS REFUSED ON A LIVE RUN, DELIBERATELY. A moved clock on a real run
	 *    would send today the mail that belongs to a future date, to real
	 *    people, and would then stamp the cooldown with a date that never
	 *    happened. There is no legitimate use for it outside a preview, so it
	 *    is not merely discouraged — the command stops.
	 *
	 * ⚠ A BARE DATE IS EVALUATED AT 09:00 SITE-LOCAL, matching
	 *   `bhp_review_ask_cli_plan()`, because the daily runner is meant to land
	 *   inside the morning window and midnight would report every order as
	 *   `outside_send_window`.
	 */
	$now = 0;

	if ( isset( $assoc_args['as-of'] ) ) {
		$as_of = trim( (string) $assoc_args['as-of'] );

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $as_of ) ) {
			$as_of .= ' 09:00:00';
		}

		$now = bhp_review_ask_local_datetime( $as_of );

		if ( ! $now ) {
			WP_CLI::error( 'Unparseable --as-of value. Use Y-m-d or "Y-m-d H:i:s".' );
			return;
		}

		if ( ! $dry ) {
			WP_CLI::error( '--as-of is accepted on `dry` only. Moving the clock on a live run would send a future date\'s mail today.' );
			return;
		}

		$say( 'CLOCK MOVED for this preview only: ' . gmdate( 'Y-m-d H:i:s', $now ) . ' UTC.' );
	}

	$summary = bhp_review_ask_run(
		array(
			'dry'    => $dry,
			'now'    => $now,
			'logger' => $say,
		)
	);

	$say( '' );
	$say( 'examined: ' . $summary['examined']
		. ( $dry ? '  would send: ' : '  sent: ' ) . $summary['sent']
		. '  halted: ' . ( $summary['halted'] ? $summary['halted'] : '-' ) );

	foreach ( $summary['declined'] as $reason => $count ) {
		$say( '  declined ' . $reason . ': ' . $count );
	}

	if ( $dry && empty( $summary['enabled'] ) ) {
		$say( '' );
		$say( '** The engine is DISABLED. Nothing above was sent, and nothing will send until the switch is thrown. **' );
	}
}

/**
 * `wp bhp review-ask test-send --to=<address> --set=<day0|touch1|touch2|web1> --order=<id> [--dump=<path>]`
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.365 · THE VISUAL-CHECK SEAM. Andrew and Gandalf need to LOOK at
 *     these four emails in a real inbox before anything is activated, and
 *     until now the only way to make one appear was to enable the engine and
 *     wait for a cron. That trade is unacceptable, so this renders ONE real
 *     order through the REAL templates and puts it in ONE named inbox.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔⛔ WHAT IT DOES NOT TOUCH, AND THIS IS THE WHOLE REASON IT IS SAFE:
 *
 *   - it does NOT call `bhp_review_ask_log_send()`, `bhp_review_ask_mark_sent()`
 *     or `bhp_review_ask_record_customer()`, so no sent marker, no ledger row,
 *     no daily counter and no 90-day customer cooldown is written. A test send
 *     today cannot make a real order ineligible tomorrow;
 *   - it does NOT consult and does NOT change `bhp_review_ask_enabled`. The
 *     master switch stays exactly as found, and this command works with it OFF
 *     because a preview that needs the engine on is not a preview;
 *   - it does NOT read or write the opt-out store, and it never mails the
 *     order's own billing address. ⛔ `--to` is the ONLY recipient, and it is
 *     required. There is no default, no fallback to the order and no bcc;
 *   - it does NOT bypass the copy gate. An `approved => false` set is REFUSED,
 *     not previewed, because the whole point of the gate is that unapproved
 *     strings do not reach an inbox, and Gandalf's inbox is an inbox.
 *
 * ⛔⛔ IT REFUSES TO RUN ANYWHERE BUT STAGING, AND THE CHECK IS ON `home_url()`
 *     RATHER THAN ON `--url`, deliberately. `--url` is what the operator TYPED;
 *     `home_url()` is which site WordPress actually loaded. Checking the typed
 *     value would let a typo, a missing `--url` (which falls back to the
 *     primary site) or a multisite mapping put a test email in a real
 *     customer's mailbox from the production database. ⚠ The command therefore
 *     effectively requires `--url=https://staging2.braveheartspublishing.com`
 *     to be present and correct, but it verifies the CONSEQUENCE, not the
 *     argument.
 *
 * ⚠ SUBJECTS ARE PREFIXED `[STAGING TEST]`. A screenshot of an unprefixed
 *   review ask, forwarded on, is indistinguishable from a live send.
 *
 * @since 1.19.365
 * @param array    $assoc_args Named args.
 * @param callable $say        Logger.
 * @return void
 */
function bhp_review_ask_cli_test_send( $assoc_args, $say ) {
	/*
	 * ⛔ GATE 1 — STAGING ONLY. Fail closed and fail loud, before anything
	 *    else is read, so a wrong-site invocation never even resolves an order.
	 */
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$host = is_string( $host ) ? strtolower( $host ) : '';

	$staging_host = class_exists( 'BHP_Analytics_Config' ) && defined( 'BHP_Analytics_Config::STAGING_HOST' )
		? strtolower( (string) BHP_Analytics_Config::STAGING_HOST )
		: 'staging2.braveheartspublishing.com';

	if ( $host !== $staging_host ) {
		WP_CLI::error(
			'REFUSED. `test-send` runs on staging only. WordPress loaded "' . $host . '"; this command requires "' . $staging_host
			. '". Pass --url=https://' . $staging_host . ' and run it again. ⛔ The check is on home_url(), not on --url, so this cannot be argued past.'
		);
	}

	/*
	 * ⛔ GATE 2 — AN EXPLICIT, SINGLE, VALID RECIPIENT. No default. No fallback
	 *    to the order's billing email, which is a real customer.
	 */
	$to = isset( $assoc_args['to'] ) ? trim( (string) $assoc_args['to'] ) : '';

	if ( '' === $to || ! is_email( $to ) ) {
		WP_CLI::error( 'REFUSED. --to=<address> is required and must be one valid address. There is no default recipient.' );
	}

	if ( false !== strpos( $to, ',' ) || false !== strpos( $to, ';' ) ) {
		WP_CLI::error( 'REFUSED. --to takes ONE address. Run the command again for the second person.' );
	}

	// ⛔ GATE 3 — a known set name.
	$set = isset( $assoc_args['set'] ) ? strtolower( trim( (string) $assoc_args['set'] ) ) : '';

	if ( ! in_array( $set, array( 'day0', 'touch1', 'touch2', 'web1' ), true ) ) {
		WP_CLI::error( 'REFUSED. --set must be one of: day0, touch1, touch2, web1.' );
	}

	// ⛔ GATE 4 — a real order.
	$order_id = isset( $assoc_args['order'] ) ? absint( $assoc_args['order'] ) : 0;
	$order    = $order_id ? wc_get_order( $order_id ) : false;

	if ( ! $order instanceof WC_Order ) {
		WP_CLI::error( 'REFUSED. --order=<id> must be a real WooCommerce order on THIS database. Order ' . $order_id . ' did not load.' );
	}

	$say( 'site:      ' . $host . '   (staging, verified from home_url())' );
	$say( 'order:     #' . $order->get_id() . ' | ' . bhp_review_ask_lane( $order ) . ' lane | '
		. bhp_review_ask_chapter_book_count( $order ) . ' chapter book(s)' );
	$say( 'set:       ' . $set );
	$say( 'to:        ' . $to );
	$say( '' );

	/*
	 * ⭐ DAY 0 IS A DIFFERENT EMAIL ENTIRELY. It is WooCommerce's own
	 *    `customer_completed_order`, overlaid by `inc/visit-completed-email.php`
	 *    on a visit order. So it is rendered through WooCommerce's object, not
	 *    through the review-ask class, or the preview would prove nothing about
	 *    what a parent actually receives.
	 */
	if ( 'day0' === $set ) {
		if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) {
			WP_CLI::error( 'WooCommerce mailer unavailable.' );
		}

		$emails = WC()->mailer()->get_emails();

		if ( ! isset( $emails['WC_Email_Customer_Completed_Order'] ) ) {
			WP_CLI::error( 'WC_Email_Customer_Completed_Order is not registered.' );
		}

		$email            = $emails['WC_Email_Customer_Completed_Order'];
		$email->object    = $order;
		$email->recipient = $to;

		if ( property_exists( $email, 'placeholders' ) && is_array( $email->placeholders ) ) {
			$email->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$email->placeholders['{order_number}'] = $order->get_order_number();
		}

		$slug = function_exists( 'bhp_visit_email_slug' ) ? bhp_visit_email_slug( $email ) : '';

		$say( 'day-0 copy set: ' . ( '' !== $slug ? $slug : '(none - this order is not a visit order)' ) );

		if ( '' === $slug ) {
			WP_CLI::error( 'REFUSED. Order ' . $order->get_id() . ' resolves to no visit copy set, so the day-0 overlay would not fire for it. Pick a visit order.' );
		}

		if ( function_exists( 'bhp_visit_email_copy_is_approved' ) && ! bhp_visit_email_copy_is_approved( $slug ) ) {
			WP_CLI::error( 'REFUSED. The day-0 set "' . $slug . '" is approved => false. Unapproved copy is not previewed into an inbox.' );
		}

		$subject = '[STAGING TEST] ' . wp_strip_all_tags( $email->get_subject() );
		$html    = $email->style_inline( $email->get_content() );

		bhp_review_ask_cli_test_send_deliver( $to, $subject, $html, $email->get_from_name(), $email->get_from_address(), $assoc_args, $say );
		return;
	}

	/*
	 * ⭐ THE THREE REVIEW-ASK SETS. `prepare_preview()` is the existing QA seam
	 *    (1.19.362): it sets the object and the opt-out URL and writes NOTHING.
	 */
	if ( ! class_exists( 'WC_Email_BHP_Review_Ask' ) ) {
		require_once get_template_directory() . '/inc/class-wc-email-bhp-review-ask.php';
	}

	if ( ! class_exists( 'WC_Email_BHP_Review_Ask' ) ) {
		WP_CLI::error( 'WC_Email_BHP_Review_Ask did not load.' );
	}

	$email = new WC_Email_BHP_Review_Ask();

	if ( ! $email->prepare_preview( $order ) ) {
		WP_CLI::error( 'prepare_preview() refused order ' . $order->get_id() . '.' );
	}

	$email->touch = ( 'touch2' === $set ) ? 2 : 1;

	/*
	 * ⚠ WHICH TOUCH-1 SET RENDERS IS DECIDED BY THE ORDER'S LANE, not by the
	 *   flag, because that is how it will be decided on a real send. ⛔ ASKING
	 *   FOR `web1` ON A VISIT ORDER IS REFUSED RATHER THAN QUIETLY GIVEN THE
	 *   VISIT SET — a preview that silently shows you a different email than
	 *   the one you asked for is worse than no preview.
	 */
	$lane = bhp_review_ask_lane( $order );

	if ( 'web1' === $set && 'web' !== $lane ) {
		WP_CLI::error( 'REFUSED. Order ' . $order->get_id() . ' is on the "' . $lane . '" lane, so it renders the VISIT touch-1 set, not the web one. Pass a web order, or use --set=touch1.' );
	}

	if ( 'touch1' === $set && 'visit' !== $lane ) {
		WP_CLI::error( 'REFUSED. Order ' . $order->get_id() . ' is on the "' . $lane . '" lane, so --set=touch1 would render the WEB set. Pass a visit order, or use --set=web1.' );
	}

	// ⛔ THE COPY GATE APPLIES TO A PREVIEW TOO.
	$copy = bhp_review_ask_copy_raw( $email->touch, $order );

	$say( 'copy set:  ' . ( isset( $copy['set'] ) ? $copy['set'] : '(unnamed)' )
		. ' | approved: ' . ( ! empty( $copy['approved'] ) ? 'YES' : 'NO' )
		. ' | stars: ' . ( bhp_review_ask_copy_has_stars( $copy ) ? 'yes' : 'no' ) );

	if ( empty( $copy['approved'] ) ) {
		WP_CLI::error( 'REFUSED. That copy set is approved => false. Unapproved strings are not previewed into an inbox.' );
	}

	/*
	 * ⚠ THE MERGE GATE IS REPORTED, NOT ENFORCED, AND THAT IS DELIBERATE FOR A
	 *   PREVIEW. An order with an unresolvable slot is exactly the case a human
	 *   should be shown, so the command SAYS the send would be declined and
	 *   renders it anyway. ⛔ It is a hard decline on the real runner and that
	 *   is untouched.
	 */
	if ( ! bhp_review_ask_merge_is_complete( $copy, $order ) ) {
		$say( '⚠ WARNING: a REAL send of this order would be declined `unresolved_merge_slot`. Rendering it anyway so you can see what is missing.' );
	}

	if ( bhp_review_ask_copy_has_stars( $copy ) && ! bhp_review_ask_star_row( $order ) ) {
		$say( '⚠ WARNING: the star row is EMPTY for this order (no per-title review URL resolved). A real send would be declined.' );
	}

	$subject = '[STAGING TEST] ' . wp_strip_all_tags( $email->get_subject() );
	$html    = $email->style_inline( $email->get_content() );

	bhp_review_ask_cli_test_send_deliver( $to, $subject, $html, $email->get_from_name(), $email->get_from_address(), $assoc_args, $say );
}

/**
 * Put one rendered email in one inbox, and write nothing anywhere else.
 *
 * ⛔ `wp_mail()` DIRECTLY, NOT `WC_Email::send()`. `send()` runs the
 *    `woocommerce_mail_*` chain and, more importantly, invites a future editor
 *    to add ledger writes beside it. This function has exactly one side effect
 *    outside the SMTP transaction: none.
 *
 * @since 1.19.365
 * @param string   $to         Recipient.
 * @param string   $subject    Subject, already prefixed.
 * @param string   $html       Rendered, inlined HTML.
 * @param string   $from_name  From name.
 * @param string   $from_email From address.
 * @param array    $assoc_args Named args (`--dump`).
 * @param callable $say        Logger.
 * @return void
 */
function bhp_review_ask_cli_test_send_deliver( $to, $subject, $html, $from_name, $from_email, $assoc_args, $say ) {
	/*
	 * ⭐ `--dump=<path>` WRITES THE HTML TO A FILE INSTEAD OF NOTHING EXTRA. A
	 *    file can be opened in a browser and diffed; an inbox cannot.
	 */
	if ( ! empty( $assoc_args['dump'] ) ) {
		$path = (string) $assoc_args['dump'];

		if ( false !== file_put_contents( $path, $html ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$say( 'dumped:    ' . $path . ' (' . strlen( $html ) . ' bytes)' );
		} else {
			$say( '⚠ could not write --dump path: ' . $path );
		}
	}

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . $from_name . ' <' . $from_email . '>',

		/*
		 * ⛔ NOT A BULK MESSAGE AND IT SAYS SO. This header keeps a one-off QA
		 *    render out of any recipient-side automation that treats review
		 *    asks as marketing, and it is one more thing a reader can look at
		 *    to tell a test from the real thing.
		 */
		'X-BHP-Test-Send: 1',
		'Auto-Submitted: auto-generated',
	);

	$sent = wp_mail( $to, $subject, $html, $headers );

	$say( '' );

	if ( $sent ) {
		$say( 'SENT to ' . $to . ' | subject: ' . $subject );
	} else {
		$say( 'wp_mail() returned FALSE. Nothing was delivered. Check the SMTP plugin and the staging mail log.' );
	}

	$say( '' );
	$say( '⛔ LEDGER UNTOUCHED: no sent marker, no log row, no daily counter, no customer cooldown, no opt-out record.' );
	$say( '⛔ MASTER SWITCH UNCHANGED: ' . ( bhp_review_ask_is_enabled() ? 'ENABLED' : 'still disabled' ) . '.' );
}

/**
 * `wp bhp review-ask plan [--dates=<Y-m-d,...>] [--scan=<n>]`
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT WOULD SEND, ON EACH OF SEVERAL DAYS, WITHOUT SENDING ANYTHING.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT SENDS NOTHING AND WRITES NOTHING. It never calls `bhp_review_ask_run()`
 *    and never touches the mailer, the markers, the ledger or the registries.
 *    It walks the candidate list and asks `bhp_review_ask_decline_reason()`
 *    with a moved clock, which is the same function the real run uses, so what
 *    it reports is what the run would do rather than a parallel re-derivation.
 *
 * ⚠ THE SEND WINDOW IS FORCED OPEN FOR THE PLAN, and only for the plan. A plan
 *   run at four in the afternoon must still show what the eight-o'clock run
 *   would send; otherwise every row would read `outside_send_window` and the
 *   plan would be useless at exactly the hour a human is likely to run it. The
 *   filter is removed again before the function returns.
 *
 * ⚠ IT PROJECTS ONLY WHAT PRESENT STATE IMPLIES. A touch 2 shown for a future
 *   date assumes touch 1 goes out as planned; a review arriving in between
 *   would suppress it, and the plan cannot know that in advance. Stated on the
 *   output so nobody quotes a projection as a schedule.
 *
 * @param array    $assoc_args Associative args.
 * @param callable $say        Line logger.
 * @return void
 */
function bhp_review_ask_cli_plan( $assoc_args, $say ) {
	/*
	 * ⭐ 1.19.363 — `--as-of` IS ACCEPTED AS AN ALIAS FOR `--dates`. Both spellings
	 *    reached this desk from a real operator on the same afternoon, and a
	 *    preview command that silently previews TODAY when you asked it for a
	 *    date is worse than one that errors.
	 */
	$dates_arg = '';

	if ( isset( $assoc_args['dates'] ) ) {
		$dates_arg = (string) $assoc_args['dates'];
	} elseif ( isset( $assoc_args['as-of'] ) ) {
		$dates_arg = (string) $assoc_args['as-of'];
	}

	$dates = '' !== $dates_arg ? explode( ',', $dates_arg ) : array( current_time( 'Y-m-d' ) );
	$scan  = isset( $assoc_args['scan'] ) ? (int) $assoc_args['scan'] : 200;

	/*
	 * ⭐ AND THE PLAN SAYS WHETHER THE ENGINE IS ON. It has never been gated on
	 *    the master switch (correctly — it sends nothing), but a plan read
	 *    without that line looks exactly like a schedule that is about to
	 *    happen.
	 */
	$plan_enabled = bhp_review_ask_is_enabled();

	$force_window = static function () {
		return true;
	};

	add_filter( 'bhp_review_ask_in_send_window', $force_window, 999 );

	$say( 'REVIEW-ASK PLAN. Nothing is sent and nothing is written.' );
	$say( 'engine: ' . ( $plan_enabled ? 'ENABLED - these sends would really happen' : 'DISABLED - nothing below will send until the switch is thrown' ) );
	$say( 'The send window is forced open so the plan is not an artefact of the hour it was run.' );
	$say( 'A projected touch 2 assumes its touch 1 went out and no review arrived in between.' );
	$say( '' );

	foreach ( $dates as $date ) {
		$date = trim( (string) $date );

		/*
		 * ⭐ 09:00 SITE-LOCAL, not midnight. The daily runner is meant to land
		 *    inside the morning window, and an order due at local midnight on
		 *    the same date is due at nine as well, so this is the honest
		 *    instant to evaluate a calendar day at.
		 */
		$now = bhp_review_ask_local_datetime( $date . ' 09:00:00' );

		if ( ! $now ) {
			$say( 'SKIPPED unparseable date: ' . $date );
			continue;
		}

		$say( '=== ' . $date . ' 09:00 site-local ===' );

		$would    = 0;
		$declined = array();

		foreach ( bhp_review_ask_candidates( $scan ) as $order ) {
			$reason = bhp_review_ask_decline_reason( $order, $now );

			if ( '' !== $reason ) {
				$declined[ $reason ] = isset( $declined[ $reason ] ) ? $declined[ $reason ] + 1 : 1;
				continue;
			}

			$would++;

			$say( sprintf(
				'  WOULD SEND  order %-6s  touch %d  %-5s lane  %d chapter book(s)  visit %s',
				$order->get_id(),
				bhp_review_ask_next_touch( $order ),
				bhp_review_ask_lane( $order ),
				bhp_review_ask_chapter_book_count( $order ),
				bhp_review_ask_visit_date( $order ) ? bhp_review_ask_visit_date( $order ) : '-'
			) );
		}

		$cap = bhp_review_ask_daily_cap();

		$say( '  ---' );
		$say( '  would send: ' . $would . '   daily cap: ' . $cap . ( $would > $cap ? '   ** CAPPED: ' . ( $would - $cap ) . ' would slip to the next day **' : '' ) );

		foreach ( $declined as $reason => $count ) {
			$say( '  declined ' . $reason . ': ' . $count );
		}

		$say( '' );
	}

	remove_filter( 'bhp_review_ask_in_send_window', $force_window, 999 );
}

/**
 * `wp bhp review-ask migrate --orders=<id:Y-m-d,...> [--confirmed-sent] [--apply]`
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ IT DEFAULTS TO A DRY RUN AND IT DEFAULTS TO NOT BELIEVING YOU.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ WHY THIS COMMAND EXISTS. Sixteen orders were prepared for a hand-sent
 *    review ask outside this engine. Without a marker the engine would ask
 *    them again, which is the double-ask this whole feature is built to avoid.
 *
 * ⚠⚠ **AND WHY IT WILL NOT MARK THEM AS SENT BY DEFAULT. VERIFIED, NOT
 *    ASSUMED:** `Business OS\ANDREW-REVIEW\2026-09-05\REVIEW-ASKS\SUMMARY.md`
 *    (Gimli, 2026-09-05) records the live state of those sixteen as
 *    *"16 drafts created in Gmail. NOTHING SENT. Nothing scheduled."*, with
 *    Andrew pressing send himself, one note at a time. ⛔ A DRAFT IS NOT A
 *    SEND (Standing Rules §9.2 rule 1). Writing "touch 1 sent on 2026-09-10"
 *    against an order whose note is still sitting in Drafts would:
 *      1. record a live-state claim nobody has verified, and
 *      2. schedule a reminder for 2026-09-17 chasing a first ask that may
 *         never have gone out, which is the worst possible email to send.
 *
 * ⭐ SO THE TWO MODES ARE SEPARATED:
 *
 *    DEFAULT (no `--confirmed-sent`): writes `external-pending-<date>` to the
 *    sent marker and writes NO touch-1 date. Effect: touch 1 is suppressed
 *    forever, and touch 2 declines `touch1_date_unknown` because there is no
 *    date to count from. ⭐ Both asks are held. This is the "exclude them
 *    entirely" outcome, reached by the engine's ordinary rules rather than by
 *    a special case, and it is reversible by re-running with the flag.
 *
 *    `--confirmed-sent`: writes `external-<date>` AND the touch-1 date, so the
 *    reminder lands seven days after the date given. ⛔ Use this ONLY for
 *    orders somebody has confirmed IN GMAIL'S SENT FOLDER, per order, with the
 *    real send date. The flag exists so that confirmation is a deliberate act
 *    with a name on it.
 *
 * ⛔ `--apply` IS REQUIRED TO WRITE ANYTHING. Without it this prints the
 *    manifest and exits.
 *
 * ⛔ IT WRITES ONLY THESE TWO ORDER META KEYS. No product, price, coupon,
 *    stock, shipping, tax, payment, checkout record or WooCommerce setting is
 *    touched, and no order status, total or line item is changed.
 *
 * @param array    $assoc_args Associative args.
 * @param callable $say        Line logger.
 * @return void
 */
function bhp_review_ask_cli_migrate( $assoc_args, $say ) {
	$spec      = isset( $assoc_args['orders'] ) ? (string) $assoc_args['orders'] : '';
	$confirmed = ! empty( $assoc_args['confirmed-sent'] );
	$apply     = ! empty( $assoc_args['apply'] );

	if ( '' === trim( $spec ) ) {
		$say( 'Nothing to do. Pass --orders=612:2026-09-10,615:2026-09-10,...' );
		return;
	}

	$say( $apply ? 'MIGRATE - APPLYING.' : 'MIGRATE - DRY RUN. Nothing is written. Add --apply to write.' );
	$say( $confirmed
		? 'MODE: --confirmed-sent. Touch 1 will be recorded AS SENT on the date given, and a reminder will be scheduled 7 days later.'
		: 'MODE: default. Touch 1 will be SUPPRESSED and NO date recorded, so touch 2 also declines. Use --confirmed-sent only for orders confirmed in Gmail Sent.' );
	$say( '' );

	foreach ( explode( ',', $spec ) as $row ) {
		$row   = trim( $row );
		$parts = explode( ':', $row );

		$order_id = isset( $parts[0] ) ? absint( $parts[0] ) : 0;
		$date     = isset( $parts[1] ) ? trim( $parts[1] ) : '';

		if ( ! $order_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$say( 'SKIP  unparseable row: ' . $row );
			continue;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			$say( 'SKIP  order ' . $order_id . ' not found' );
			continue;
		}

		$existing = trim( (string) $order->get_meta( BHP_REVIEW_ASK_SENT_META ) );

		if ( '' !== $existing ) {
			$say( 'SKIP  order ' . $order_id . ' already marked: ' . $existing );
			continue;
		}

		$marker = ( $confirmed ? 'external-' : 'external-pending-' ) . $date;

		$say( sprintf(
			'%s order %-6s  marker %-28s  touch1_at %s  -> reminder %s',
			$apply ? 'WRITE' : 'WOULD',
			$order_id,
			$marker,
			$confirmed ? $date . ' 09:00:00' : '(none)',
			$confirmed ? gmdate( 'Y-m-d', strtotime( $date . ' +' . BHP_REVIEW_ASK_TOUCH2_DELAY_DAYS . ' days' ) ) : 'none - touch 2 declines touch1_date_unknown'
		) );

		if ( ! $apply ) {
			continue;
		}

		$order->update_meta_data( BHP_REVIEW_ASK_SENT_META, $marker );

		if ( $confirmed ) {
			/*
			 * ⭐ 09:00 SITE-LOCAL, matching the morning window. A hand-sent
			 *    note has no recorded minute, and midnight would make the
			 *    reminder due at midnight seven days later, which the send
			 *    window would then hold until the following morning anyway.
			 *    Recording nine keeps the arithmetic and the observed
			 *    behaviour the same.
			 */
			$order->update_meta_data( BHP_REVIEW_ASK_TOUCH1_AT_META, $date . ' 09:00:00' );
		}

		$order->save();
	}

	$say( '' );
	$say( 'Done. No WooCommerce setting, product, price, coupon, stock, shipping, tax or checkout record was read or written.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bhp review-ask', 'bhp_review_ask_cli' );
}
