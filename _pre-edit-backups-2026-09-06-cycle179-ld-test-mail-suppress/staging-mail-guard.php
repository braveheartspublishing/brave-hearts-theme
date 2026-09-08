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
 * ⛔ NOT SUPPRESSED, AND THAT IS DELIBERATE:
 *    - password resets, admin notifications, new-user emails — nothing to do
 *      with orders and used by real QA;
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
 * ⭐ CORE ORDER EMAILS ONLY, PLUS THIS PROJECT'S OWN ORDER EMAIL. Listed by
 *    literal id rather than derived from `WC_Emails::get_emails()`, so a
 *    plugin that registers a new email class in future is NOT silently
 *    silenced by a guard nobody re-read.
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
			'customer_on_hold_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_refunded_order',
			'customer_partially_refunded_order',
			'customer_invoice',
			'customer_note',
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
		add_filter( 'woocommerce_email_enabled_' . $id, 'bhp_staging_mail_guard_disable', 99 );
	}
}
bhp_staging_mail_guard_register();

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
