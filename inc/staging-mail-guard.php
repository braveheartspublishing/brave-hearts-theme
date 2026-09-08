<?php
/**
 * THE STAGING ORDER-EMAIL GUARD — QA never emails Andrew again.
 * Theme 1.19.281. Workstream `CYCLE165-LD-FLOW-ADJUSTMENTS`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE DEFECT THIS CLOSES — OBSERVED, NOT ANTICIPATED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ The purchase-flow QA round of 2026-08-21 placed real staging orders
 *    (staging ids approximately 4094–4127) through the real Blocks checkout in
 *    order to prove the founder's flow end to end. ⛔ EVERY ONE OF THEM FIRED
 *    A REAL WOOCOMMERCE ADMIN EMAIL INTO ANDREW'S INBOX before the test orders
 *    were cleaned up. The orders were deleted; the emails could not be.
 *
 * ⭐ WHY IT HAPPENED, VERIFIED READ-ONLY OVER SSH ON STAGING 2026-08-21 rather
 *    than assumed: `woocommerce_new_order_settings` and its siblings are UNSET
 *    on staging, so WooCommerce falls back to its own defaults — enabled, with
 *    the recipient taken from `admin_email`, which on staging is
 *    `Andrew@braveheartspublishing.com`. ⛔ Staging is a faithful copy of
 *    production, which is exactly why it mails the real owner.
 *
 * ⛔⛔ THE FIX IS NOT A SETTINGS CHANGE. Writing
 *     `woocommerce_new_order_settings` would be a WooCommerce configuration
 *     mutation and an Andrew gate (`BHP-AGENT-STANDING-RULES.md` §6 / §16.4),
 *     and it would also drift the two environments apart. This is CODE, it is
 *     keyed to the staging host, and it writes nothing anywhere.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ HOW THE HOST IS DETECTED — THE CODEBASE'S OWN PATTERN, CITED, NOT A
 *     NEW ONE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `BHP_Analytics_Config::is_staging()` (`inc/class-bhp-analytics-config.php`)
 * is the established staging test in this theme and every staging-only guard
 * already routes through it — `BHP_Lead_Event_Log`, `BHP_Meta_Pixel`,
 * `BHP_Consent`, `bundle-analytics.php` and two `functions.php` guards all
 * call it. Verbatim, and this file adds no second rule:
 *
 *     const STAGING_HOST = 'staging2.braveheartspublishing.com';
 *
 *     public static function is_staging() {
 *         $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
 *         return self::STAGING_HOST === $host;
 *     }
 *
 * Its own docblock states the reason it compares a HOSTNAME rather than a
 * constant: *"not a constant that could be left stale after a migration…
 * Anything not that exact host is treated as production."*
 *
 * ⭐ THAT FAIL-SAFE DIRECTION IS THE WHOLE POINT HERE. An unknown host is
 *    PRODUCTION, and production suppresses nothing. There is no value of any
 *    variable, option, constant or environment variable that can make this
 *    file suppress an email on `braveheartspublishing.com`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE ONE EXTENSION, AND EXACTLY HOW FAR IT REACHES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ `is_staging()` READS `HTTP_HOST`, WHICH IS EMPTY UNDER WP-CLI AND CRON.
 *    Some QA suites create orders through WP-CLI, where the guard above would
 *    simply not fire — and an order-email defect that comes back under WP-CLI
 *    is not fixed.
 *
 * ⭐ SO THIS FILE RECOGNISES A SECOND SIGNAL, AND ONLY WHEN THERE IS NO HTTP
 *    HOST AT ALL: the site's own configured `home_url()` host, compared
 *    against the SAME literal. Both clauses are keyed to
 *    `BHP_Analytics_Config::STAGING_HOST`; neither invents a host.
 *
 * ⛔ WHAT THAT CANNOT DO, STATED PRECISELY. Consider the worst realistic
 *    misconfiguration — a staging database restored onto production. Every web
 *    request on production still carries `HTTP_HOST = braveheartspublishing.com`,
 *    so clause one is false; clause two requires `HTTP_HOST` to be EMPTY, so
 *    it is false for every browser request. ⭐ A REAL CUSTOMER CHECKING OUT ON
 *    PRODUCTION STILL RECEIVES THEIR ORDER EMAIL, and so does Andrew. Only a
 *    production WP-CLI-created order inside an already-catastrophic
 *    misconfiguration would be affected. ⚠️ That residual is stated rather
 *    than hidden; it is the price of covering the CLI path, and it was judged
 *    worth paying because the alternative leaves the reported defect open.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT IS SUPPRESSED, AND WHAT DELIBERATELY IS NOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * SUPPRESSED on staging: WooCommerce's ORDER emails, by id, through
 * WooCommerce's own `woocommerce_email_enabled_{$id}` filter — the mechanism
 * WooCommerce provides for exactly this and the one its own settings screen
 * drives. The email object is still constructed, still triggered, and still
 * logs; only `is_enabled()` answers false, so nothing is handed to `wp_mail()`.
 *
 * ⛔⛔ SUPERSEDED 1.19.397 — THE FIRST BULLET BELOW IS NO LONGER TRUE, AND IT
 *     IS PRESERVED STRUCK RATHER THAN DELETED, AT THE LINE IT CORRECTS.
 *
 *     ~~- password resets, admin notifications, new-user emails — nothing to
 *       do with orders and used by real QA;~~
 *
 *     ⭐ WHY IT WAS REVERSED: staging is refreshed FROM PRODUCTION, so its
 *        user table holds real customers. `customer_reset_password` and
 *        `customer_new_account` both read `is_enabled() === TRUE` on staging
 *        on 2026-09-07 — measured, not assumed — and both address a real
 *        person. "Used by real QA" is a reason to have an EXPLICIT DOOR, not
 *        a reason to leave the window open. The door is
 *        `bhp_staging_mail_guard_exempt_ids`.
 *
 * ⛔ NOT SUPPRESSED, AND THAT IS DELIBERATE:
 *    - the lead-magnet and newsletter paths, which have their own QA and whose
 *      suppression would silently break funnel testing;
 *    - Mailchimp, Klaviyo and HubSpot, which are separate systems with their
 *      own staging posture and are not this file's business;
 *    - anything at all on production.
 *
 * ⛔ A BLANKET `wp_mail` KILL WAS CONSIDERED AND REJECTED. It would have been
 *    two lines and it would have taken the funnel QA down with it.
 *
 * ⛔ NO OPTION, PRODUCT, PRICE, COUPON, STOCK, SHIPPING, TAX, PAYMENT OR
 *    CHECKOUT SETTING IS READ OR WRITTEN BY THIS FILE, ON ANY ENVIRONMENT.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every WooCommerce email id this guard silences on staging.
 *
 * ⛔⛔ SUPERSEDED 1.19.397, PRESERVED STRUCK AT THE LINE IT CORRECTS.
 *
 *     ~~⭐ CORE ORDER EMAILS ONLY, PLUS THIS PROJECT'S OWN ORDER EMAIL. Listed
 *     by literal id rather than derived from `WC_Emails::get_emails()`, so a
 *     plugin that registers a new email class in future is NOT silently
 *     silenced by a guard nobody re-read.~~
 *
 * ⭐ The struck reasoning weighed "a future plugin's email is silenced without
 *    anyone choosing to" against "a future plugin's email is SENT without
 *    anyone choosing to", and picked the wrong side. ⛔ On staging those two
 *    are not symmetric: the first costs a QA path somebody notices in the same
 *    session, the second costs an email to a real customer that cannot be
 *    unsent. The 2026-09-06 incident is the second one, already paid for.
 *
 * ⭐ THIS LIST IS NOW A FLOOR, NOT THE MECHANISM. It is registered at file
 *    scope, so it has no ordering dependency and it records which ids were
 *    guarded DELIBERATELY, with the reason beside each. Completeness is the
 *    job of `bhp_staging_mail_guard_sweep()` below. Keep adding to this list
 *    when the reason is worth writing down; do not rely on it being complete.
 *
 * @return string[]
 */
function bhp_staging_mail_guard_email_ids() {
	/**
	 * The order-email ids suppressed on staging.
	 *
	 * @param string[] $ids WooCommerce email ids.
	 */
	return (array) apply_filters(
		'bhp_staging_suppressed_order_emails',
		array(
			// Admin — the ones that reached Andrew's inbox.
			'new_order',
			'cancelled_order',
			'failed_order',
			// Customer — a QA order uses a real-looking address; do not mail it.
			/*
			 * ⛔⛔ 1.19.386 — THESE TWO WERE MISSING, AND THEIR ABSENCE IS THE
			 *    WHOLE OF THE 2026-09-06 DEFECT. Six emails subject "Your
			 *    payment did not go through" left staging through Google's SMTP
			 *    relay to `bhp-cycle168+optin-*@example.com` and bounced back to
			 *    Andrew (FluentSMTP log rows 41–46, read live over SSH).
			 *
			 * ⭐ THE GUARD WAS WORKING THE ENTIRE TIME. Measured on staging, not
			 *    inferred: every other id below read `is_enabled() === false`
			 *    under WP-CLI while these two read TRUE. `WC_Email_Failed_Order`
			 *    (`failed_order`, ADMIN) was already listed three lines up —
			 *    which is exactly why the omission read as covered.
			 *    `WC_Email_Customer_Failed_Order` is a different, CUSTOMER-side
			 *    class that WooCommerce added later, and nobody re-read the list.
			 *
			 * ⚠️ THAT IS THE ARGUMENT AGAINST THIS LIST, NOT FOR IT, and it is
			 *    recorded here rather than in a report. A hand-maintained
			 *    enumeration of email ids goes stale silently. The list stays
			 *    because deriving it from `get_emails()` would silence a future
			 *    plugin's email that nobody chose to silence — but
			 *    `inc/test-order-mail-guard.php` now sits behind it and needs no
			 *    ids at all.
			 */
			'customer_failed_order',
			'customer_cancelled_order',
			'customer_on_hold_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_refunded_order',
			'customer_partially_refunded_order',
			'customer_invoice',
			'customer_note',
			/*
			 * ⛔⛔ 1.19.397 — `customer_partially_refunded_order` IS NOT A
			 *    REGISTERED EMAIL ID ON THIS STORE, AND IT NEVER WAS.
			 *
			 * ⭐ MEASURED, not reasoned about. `WC()->mailer()->get_emails()`
			 *    on staging 2026-09-07 returns 23 objects and NONE of them has
			 *    that id — WooCommerce handles the partial-refund case inside
			 *    `WC_Email_Customer_Refunded_Order` (id
			 *    `customer_refunded_order`, already listed above) rather than
			 *    with a second class.
			 *
			 * ⚠️ IT IS LEFT IN THE LIST ON PURPOSE. Registering
			 *    `woocommerce_email_enabled_customer_partially_refunded_order`
			 *    costs one `add_filter()` on a hook nothing applies, and if a
			 *    future WooCommerce ever splits that class out, the guard is
			 *    already there. ⛔ What matters is that its presence here was
			 *    read for years as evidence the list was complete. It is not
			 *    evidence of anything, which is the whole argument for the
			 *    DYNAMIC SWEEP added below.
			 */
			// Point-of-sale order emails (WooCommerce 10.x). Registered on this
			// store, currently disabled in settings — guarded anyway, because
			// "currently disabled" is a setting and settings change.
			'customer_pos_completed_order',
			'customer_pos_refunded_order',
			/*
			 * ⭐⭐ 1.19.397 — THE EIGHT IDS THAT READ `is_enabled() === TRUE`
			 *    ON STAGING, ENUMERATED FIRST-HAND RATHER THAN GUESSED.
			 *
			 * `wp eval 'WC()->mailer()->get_emails()'` on
			 * staging2.braveheartspublishing.com, 2026-09-07, 23 emails, with
			 * `is_enabled()` printed beside each. Every id below answered TRUE
			 * while every id above answered false. ⛔ TRUE means WooCommerce
			 * will hand it to `wp_mail()` the moment its trigger fires.
			 *
			 * ⛔ FIVE OF THEM COME FROM THE STRIPE GATEWAY
			 *    (`woocommerce-gateway-stripe` 10.8.5), and three of those fire
			 *    on a FAILED PAYMENT ATTEMPT — the single most likely thing to
			 *    happen during checkout QA on staging, where the card details
			 *    are deliberately bad. The recipient is the address typed into
			 *    the checkout form.
			 *
			 * ⚠️ THE PRIOR EXCLUSION OF THE THREE NON-STRIPE IDS IS REVERSED
			 *    HERE, DELIBERATELY, AND THE REVERSED REASONING IS PRESERVED.
			 *    This file's header says password resets, new-account and admin
			 *    notifications are "nothing to do with orders and used by real
			 *    QA". ⛔ The first half is true and the second half is the
			 *    trap: staging is refreshed FROM PRODUCTION, so its user table
			 *    holds real customers' real addresses, and
			 *    `customer_reset_password` on staging mails a real person from
			 *    a test box. A QA path that needs one of these back has an
			 *    explicit door — `bhp_staging_mail_guard_exempt_ids` — so
			 *    un-guarding is a deliberate act somebody wrote down, not a
			 *    gap nobody noticed.
			 */
			'customer_reset_password',
			'customer_new_account',
			'admin_payment_gateway_enabled',
			// Stripe gateway (woocommerce-gateway-stripe). Ids read off the
			// live objects, not copied from the plugin's documentation.
			'failed_renewal_authentication',
			'failed_preorder_sca_authentication',
			'failed_authentication_requested',
			'wc_stripe_failed_refund_admin',
			'wc_stripe_failed_refund_customer',
			// This project's own order email (class-wc-email-bhp-addon-thankyou.php).
			'bhp_addon_thankyou',
			/*
			 * ⭐⭐ 1.19.317 — THE STORE-SENT REVIEW ASK
			 *    (inc/class-wc-email-bhp-review-ask.php).
			 *
			 * ⛔ IT IS THE MOST DANGEROUS EMAIL IN THIS STORE TO LEAVE
			 *    UNGUARDED, and that is why it is listed here in the same
			 *    change that created it rather than afterwards. It is the only
			 *    email that is sent to a customer WEEKS after their order, by a
			 *    scheduled runner, with no human in the loop. A staging refresh
			 *    copies production's real orders, so an unguarded run on
			 *    staging would email real past customers a review ask from a
			 *    test environment.
			 *
			 * ⭐ THE GUARD REACHES IT AT ALL ONLY BECAUSE IT IS A REAL
			 *    `WC_Email`. See that class's header: a hand-rolled `wp_mail()`
			 *    would have walked straight past this list.
			 */
			'bhp_review_ask',
		)
	);
}

/**
 * Is THIS request running on staging, for order-email purposes?
 *
 * ⛔ TWO CLAUSES, BOTH KEYED TO THE SAME LITERAL. See this file's header for
 *    exactly how far the second one reaches and what it cannot do.
 *
 * @return bool TRUE only on staging.
 */
function bhp_staging_mail_guard_is_staging() {
	if ( ! class_exists( 'BHP_Analytics_Config' ) ) {
		// ⛔ FAIL TOWARDS PRODUCTION. No detector, no suppression.
		return false;
	}

	// Clause 1 — the codebase's own test, unchanged. Covers every browser
	// request, including the Store API checkout that fired the emails.
	if ( BHP_Analytics_Config::is_staging() ) {
		return true;
	}

	// Clause 2 — WP-CLI and cron only, where there is no HTTP host to read.
	$http_host = isset( $_SERVER['HTTP_HOST'] ) ? trim( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	if ( '' !== $http_host ) {
		return false; // A real request on a non-staging host. Production.
	}
	if ( ! function_exists( 'home_url' ) ) {
		return false;
	}
	$home_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	return BHP_Analytics_Config::STAGING_HOST === $home_host;
}

/**
 * Register the suppression.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ REGISTERED AT FILE SCOPE, AND THE FIRST ATTEMPT WAS WRONG.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THIS WAS FIRST HUNG ON `woocommerce_email_init`, on the assumption that
 *    WooCommerce fires it when the mailer is built. ⛔ IT DOES NOT — measured
 *    on staging, not reasoned about: `did_action( 'woocommerce_email_init' )`
 *    returned **0** both before and after `WC()->mailer()`, while
 *    `has_action()` confirmed the callback was correctly attached and
 *    `bhp_staging_mail_guard_disable( true )` correctly returned `false`.
 *
 * ⛔ THE GUARD WAS THEREFORE A COMPLETE NO-OP, AND IT LOOKED FINE. Every unit
 *    assertion about host detection passed, because those test the DECISION.
 *    The registration was never exercised until an email was actually
 *    triggered — at which point WooCommerce attempted four real sends, two of
 *    them addressed to Andrew.
 *
 * ⭐ WHY FILE SCOPE IS THE RIGHT ANSWER AND NOT MERELY A DIFFERENT HOOK:
 *    `WC_Email::is_enabled()` applies `woocommerce_email_enabled_{$id}` AT
 *    CALL TIME, every time. So a filter registered as early as possible has no
 *    ordering dependency whatsoever — it cannot be registered "too late",
 *    which is exactly the failure that just happened. ⛔ Registering a filter
 *    for a hook that a WooCommerce-less site never applies costs nothing.
 *
 * ⛔ THE HOST TEST IS STILL RE-RUN PER REQUEST, INSIDE THE CALLBACK, rather
 *    than captured here. `is_enabled()` can be asked at any point in a
 *    long-running request, and a boolean frozen at bootstrap is the kind of
 *    stale state this codebase has paid for before.
 */
function bhp_staging_mail_guard_register() {
	foreach ( bhp_staging_mail_guard_email_ids() as $id ) {
		bhp_staging_mail_guard_register_id( $id );
	}
}
bhp_staging_mail_guard_register();

/**
 * Register one id, once. Idempotent, so the dynamic sweep below can call it
 * for ids the explicit list already covered without stacking two callbacks on
 * the same hook.
 *
 * @param string $id WooCommerce email id.
 * @return bool TRUE if this call was the one that registered it.
 */
function bhp_staging_mail_guard_register_id( $id ) {
	$id = (string) $id;
	if ( '' === $id ) {
		return false;
	}
	$hook = 'woocommerce_email_enabled_' . $id;
	if ( has_filter( $hook, 'bhp_staging_mail_guard_disable' ) ) {
		return false;
	}
	add_filter( $hook, 'bhp_staging_mail_guard_disable', 99 );
	return true;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE DYNAMIC SWEEP — 1.19.397. THE LIST NO LONGER HAS TO BE COMPLETE.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS FILE HAS NOW BEEN WRONG THE SAME WAY THREE TIMES, and each time the
 *    diagnosis was "an id was missing", which produced the fix "add the id".
 *
 *      1.19.386  `customer_failed_order` was missing. Six real emails left
 *                staging and bounced back to Andrew.
 *      1.19.397  eight more ids read `is_enabled() === TRUE`, five of them
 *                from the Stripe gateway, three of those on a failed payment.
 *      (and `customer_partially_refunded_order`, listed above for years, is
 *      not a registered id at all — so the list was simultaneously short and
 *      wrong.)
 *
 * ⭐ THE LIST IS NOT THE DEFECT. THE LIST BEING THE ONLY MECHANISM IS. A
 *    hand-maintained enumeration of ids in a file nobody re-reads goes stale
 *    the next time WooCommerce ships a class or Andrew activates a plugin —
 *    and it goes stale SILENTLY, which is the property that makes it cost
 *    real emails to real people rather than a failing test.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY `woocommerce_email_classes` IS THE RIGHT SEAM, READ IN THE SOURCE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `wp-content/plugins/woocommerce/includes/class-wc-emails.php` line 336,
 * WooCommerce 10.9.1, read on the server rather than remembered:
 *
 *     $this->emails = apply_filters( 'woocommerce_email_classes', $this->emails );
 *
 * That line is the LAST thing `WC_Emails::init()` does to the array, and
 * `get_emails()` returns exactly that array. So a callback here sees the FINAL
 * set — core classes, this theme's two, and every class a gateway or connector
 * plugin added — at the one moment it is guaranteed complete and before any
 * caller can hold an object to ask `is_enabled()` of it.
 *
 * ⛔ IT RUNS AT `PHP_INT_MAX`. The Stripe gateway registers its five classes
 *    through this same filter. At priority 10 the ordering between two
 *    callbacks on one hook is registration order, which is plugin-load order,
 *    which is not something this file should depend on. Last is unambiguous.
 *
 * ⛔ THE PREVIOUS ATTEMPT AT A LATE HOOK FAILED AND IS WHY THIS ONE CITES ITS
 *    SOURCE LINE. See the registration note below: `woocommerce_email_init`
 *    was assumed to fire when the mailer is built, `did_action()` returned 0,
 *    and the guard was a silent no-op through four passing unit assertions.
 *    ⭐ THE EXPLICIT LIST ABOVE IS REGISTERED AT FILE SCOPE AND IS UNTOUCHED
 *    BY THIS ADDITION — if this seam ever moves, the core order emails are
 *    still guarded by a mechanism with no ordering dependency at all. The
 *    sweep is a second net under the first, never a replacement for it.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT IT STILL CANNOT DO, STATED RATHER THAN IMPLIED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * It reaches `WC_Email` subclasses and nothing else. A plugin that calls
 * `wp_mail()` by hand — Mailchimp for WooCommerce, MC4WP, HubSpot sync,
 * CusRev's own reminder mail — registers no email class, appears in no
 * enumeration, and walks straight past every id in this file. ⭐ THAT PATH IS
 * COVERED BY A DIFFERENT FILE: `inc/test-order-mail-guard.php` sits on
 * `pre_wp_mail` and filters by RECIPIENT, needing no ids at all. The two
 * guards are complementary and neither makes the other redundant.
 *
 * ⭐ MEASURED 2026-09-07: of the 25 active plugins on staging, exactly one
 *    non-core, non-theme source registers `WC_Email` classes — the Stripe
 *    gateway, five of them. Mailchimp for WooCommerce 6.1.1, MC4WP 4.13.1 and
 *    bhp-hubspot-sync 1.0.0 register NONE, which is a fact about how they
 *    send, not evidence that they do not send.
 *
 * @param array $emails WooCommerce's final email-class array, keyed by class name.
 * @return array The same array, unmodified.
 */
function bhp_staging_mail_guard_sweep( $emails ) {
	if ( ! is_array( $emails ) ) {
		return $emails;
	}

	$exempt = array_map( 'strval', (array) apply_filters( 'bhp_staging_mail_guard_exempt_ids', array() ) );

	foreach ( $emails as $email ) {
		if ( ! is_object( $email ) || ! isset( $email->id ) ) {
			continue;
		}
		$id = (string) $email->id;
		if ( '' === $id || in_array( $id, $exempt, true ) ) {
			continue;
		}
		bhp_staging_mail_guard_register_id( $id );
	}

	return $emails;
}
add_filter( 'woocommerce_email_classes', 'bhp_staging_mail_guard_sweep', PHP_INT_MAX );

/**
 * Every email id this guard is currently registered against.
 *
 * ⭐ It answers from `has_filter()` — the live filter registry — rather than
 *    from any list this file keeps, so it reports what is actually guarded and
 *    cannot agree with a list that has drifted. That is the whole point: the
 *    suite asks WooCommerce for its ids and asks this function which are
 *    guarded, and neither answer comes from the array at the top of the file.
 *
 * @param string[] $ids Ids to test. Defaults to every registered email id.
 * @return string[] The subset that is guarded.
 */
function bhp_staging_mail_guard_guarded_ids( $ids = null ) {
	if ( null === $ids ) {
		$ids = array();
		if ( function_exists( 'WC' ) && is_callable( array( WC(), 'mailer' ) ) ) {
			foreach ( (array) WC()->mailer()->get_emails() as $email ) {
				if ( is_object( $email ) && isset( $email->id ) && '' !== (string) $email->id ) {
					$ids[] = (string) $email->id;
				}
			}
		}
	}

	$guarded = array();
	foreach ( (array) $ids as $id ) {
		if ( false !== has_filter( 'woocommerce_email_enabled_' . $id, 'bhp_staging_mail_guard_disable' ) ) {
			$guarded[] = (string) $id;
		}
	}
	return $guarded;
}

/**
 * Answer "is this order email enabled?" with NO on staging.
 *
 * @param bool $enabled WooCommerce's answer.
 * @return bool
 */
function bhp_staging_mail_guard_disable( $enabled ) {
	return bhp_staging_mail_guard_is_staging() ? false : $enabled;
}

/**
 * ⭐ A VISIBLE, HONEST MARK IN THE ADMIN BAR ON STAGING.
 *
 * ⛔ A SILENT SUPPRESSION IS A TRAP. Somebody will eventually test "does the
 *    order email go out?" on staging, watch nothing arrive, and file a defect
 *    against the mail system. ⭐ This says, on the screen, that the guard is
 *    the reason — and it renders ONLY on staging and ONLY for an
 *    administrator, so it can never reach a customer or a production page.
 *
 * @param WP_Admin_Bar $bar
 */
function bhp_staging_mail_guard_admin_bar( $bar ) {
	if ( ! bhp_staging_mail_guard_is_staging() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! is_object( $bar ) || ! method_exists( $bar, 'add_node' ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'bhp-staging-mail-guard',
			'title' => 'Staging: order emails OFF',
			'meta'  => array( 'title' => 'WooCommerce order emails are suppressed on staging by inc/staging-mail-guard.php. Production is unaffected.' ),
		)
	);
}
add_action( 'admin_bar_menu', 'bhp_staging_mail_guard_admin_bar', 999 );
