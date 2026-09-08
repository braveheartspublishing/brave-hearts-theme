<?php
/**
 * THE TEST-ADDRESS ORDER-MAIL GUARD — a stray test order can never reach a relay.
 * Theme 1.19.386. Workstream `CYCLE179-LD-TEST-MAIL-SUPPRESS`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE DEFECT THIS CLOSES — AND WHY THE GUARD WE ALREADY HAD MISSED IT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ On 2026-09-06 six WooCommerce emails subject **"Your payment did not go
 *    through"** left staging through Google's SMTP relay, addressed to
 *    `bhp-cycle168+optin-*@example.com`. Andrew received six bounces.
 *    FluentSMTP log rows 41–46, `status = sent`, read live over SSH.
 *
 * ⭐ `inc/staging-mail-guard.php` WAS ACTIVE AND WORKING THE WHOLE TIME. It was
 *    not bypassed and its host detection was not wrong. Measured on staging,
 *    not inferred — `WC()->mailer()->get_emails()` with `is_enabled()` read for
 *    each:
 *
 *      WC_Email_New_Order                  new_order                 (suppressed)
 *      WC_Email_Cancelled_Order            cancelled_order           (suppressed)
 *      WC_Email_Customer_Cancelled_Order   customer_cancelled_order  ⛔ ENABLED
 *      WC_Email_Failed_Order               failed_order              (suppressed)
 *      WC_Email_Customer_Failed_Order      customer_failed_order     ⛔ ENABLED
 *      …every other order email             suppressed
 *
 * ⛔⛔ THE LIST WENT STALE. `customer_failed_order` and
 *     `customer_cancelled_order` are CUSTOMER-side emails that WooCommerce
 *     added after that guard's list was written; `failed_order` — the ADMIN
 *     one — was on it, which is exactly why the omission read as covered.
 *     "Your payment did not go through" is `WC_Email_Customer_Failed_Order`,
 *     WooCommerce 10.9.1, and it was never in the list.
 *
 * ⭐ THE TWO IDS ARE NOW ON THAT LIST. That is the direct fix and it is
 *    sufficient for the observed defect. ⛔ THIS FILE EXISTS BECAUSE IT IS NOT
 *    SUFFICIENT FOR THE NEXT ONE: a hand-maintained list of email ids is a
 *    thing that goes stale silently, and it just did.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE MECHANISM — TWO SEAMS, AND WHICH ONE FIRES IS NOT ARBITRARY
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ SEAM 1 — `pre_wp_mail`, and it is the one that fires almost always.
 *    When the RECIPIENT is a test address, the message is stopped inside
 *    `wp_mail()`, which returns `true`. ⛔ THAT RETURN VALUE IS THE WHOLE POINT
 *    OF CHOOSING THIS SEAM. `WC_Email::send()` still runs end to end and still
 *    reports success, so every ledger, marker and counter downstream of a send
 *    behaves exactly as it does in production. It also reaches mail this theme
 *    sends with a bare `wp_mail()` — the visit-completed and school
 *    read-aloud paths — which no WooCommerce-level filter can see.
 *
 * ⛔ THE FIRST VERSION OF THIS FILE SUBSTITUTED THE TRANSPORT INSTEAD, at
 *    `woocommerce_mail_callback`, and it BROKE A SUITE. Measured, not
 *    predicted: `tests/test-cycle169-review-ask.php` went from green to
 *    THIRTEEN failures on the first full-suite run — "wp_mail was reached
 *    exactly 5 times -- calls=0", and with it the KPI ledger, the send log and
 *    every per-order sent marker. That suite's own header states the contract
 *    the substitution had violated: *"`pre_wp_mail`, so `WC_Email::send()` runs
 *    end to end and returns true."* ⭐ Blocking a message must not also cancel
 *    the bookkeeping that a real send would have produced.
 *
 * ⭐ SEAM 2 — `woocommerce_mail_callback`, for the ONE case seam 1 cannot see.
 *    `new_order` and `failed_order` are addressed to `admin_email`, a perfectly
 *    real address, so a recipient test alone lets the ADMIN notification for a
 *    fixture order straight through. Only the WooCommerce layer can see that
 *    the ORDER is a fixture. WooCommerce 10.9.1,
 *    `includes/emails/class-wc-email.php:1234`, read on the server:
 *
 *        $mail_callback = apply_filters( 'woocommerce_mail_callback', 'wp_mail', $this );
 *        $return        = $mail_callback( ...$mail_callback_params );
 *
 *    ⛔ This seam enumerates no email ids, so it cannot go stale the way the
 *    list in `inc/staging-mail-guard.php` did. It fires ONLY when the recipient
 *    is not itself a test address, which keeps it out of the way of seam 1.
 *
 * ⛔ NEITHER SEAM IS KEYED TO A HOST. Both are keyed to the ADDRESS, so they
 *    hold on every environment. The brief called this belt and braces and that
 *    is precisely the intent: a suite run against the wrong site, a fixture
 *    order restored by a staging refresh, a cron picking up an old test order —
 *    none of them can mail a relay.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHY SUPPRESSING THESE ADDRESSES CANNOT HARM A REAL CUSTOMER
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `example.com`, `example.net`, `example.org` and the `.invalid`, `.test`,
 * `.example` and `.localhost` TLDs are RESERVED BY RFC 2606 precisely so that
 * they can never be registered and can never receive mail. ⭐ An email to one
 * of them has exactly two possible outcomes: it is dropped, or it BOUNCES —
 * which is the defect. There is no third outcome in which a customer reads it.
 *
 * ⚠️ THE ONE REAL COST, STATED RATHER THAN HIDDEN. The guard also silences the
 *    ADMIN notification for such an order, so if an order were ever placed with
 *    a reserved address, Andrew would not be emailed about it. That is
 *    deliberate — the brief asks for the admin mail to stop too — and it is why
 *    ⛔ **EVERY SUPPRESSION IS WRITTEN TO THE WooCommerce LOG**
 *    (`WooCommerce → Status → Logs`, source `bhp-test-mail-guard`). A silent
 *    suppression is a trap; a logged one is a record.
 *
 * ⭐ FAIL TOWARDS DELIVERY. Any doubt — no recipient readable, no order object,
 *    an exception while reading either — and the email is DELIVERED. There is
 *    no value of any variable that makes an unrecognised address suppress.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT DO
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * - ⛔ NO option, product, price, coupon, stock, shipping, tax, payment or
 *   checkout setting is read or written, on any environment. It changes no
 *   WooCommerce configuration; it is code, and the Andrew gate is not crossed.
 * - ⛔ It does not touch non-WooCommerce mail. Lead-magnet, newsletter,
 *   password-reset and this theme's direct `wp_mail()` paths are unaffected —
 *   suppressing those would silently break funnel QA, which is the same
 *   judgement `inc/staging-mail-guard.php` already recorded.
 * - ⛔ It does not replace the staging guard. Staging suppresses order email by
 *   HOST; this suppresses by ADDRESS. Two independent conditions, deliberately.
 *
 * @package Brave_Hearts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reserved second-level domains that can never receive mail (RFC 2606 §3).
 *
 * @return string[] Lower-case, no leading dot.
 */
function bhp_test_mail_guard_reserved_domains() {
	/**
	 * Reserved e-mail domains treated as non-deliverable test addresses.
	 *
	 * @param string[] $domains Lower-case domains.
	 */
	return (array) apply_filters(
		'bhp_test_mail_guard_reserved_domains',
		array( 'example.com', 'example.net', 'example.org', 'example.edu' )
	);
}

/**
 * Reserved top-level domains that can never resolve (RFC 2606 §2).
 *
 * @return string[] Lower-case, no leading dot.
 */
function bhp_test_mail_guard_reserved_tlds() {
	/**
	 * Reserved TLDs treated as non-deliverable test addresses.
	 *
	 * @param string[] $tlds Lower-case TLDs.
	 */
	return (array) apply_filters(
		'bhp_test_mail_guard_reserved_tlds',
		array( 'invalid', 'test', 'example', 'localhost' )
	);
}

/**
 * Local-part prefixes this project uses for its own test fixtures.
 *
 * ⭐ DELIBERATELY NARROW. `bhp-cycle` is the prefix the suites actually use
 *    (`bhp-cycle168+optin-flip@example.com`). Widening this to something like
 *    `test` would start matching real customer addresses.
 *
 * @return string[] Lower-case prefixes.
 */
function bhp_test_mail_guard_local_prefixes() {
	/**
	 * Local-part prefixes that mark an address as a project test fixture.
	 *
	 * @param string[] $prefixes Lower-case prefixes.
	 */
	return (array) apply_filters(
		'bhp_test_mail_guard_local_prefixes',
		array( 'bhp-cycle' )
	);
}

/**
 * Is this address a test fixture that must never be mailed?
 *
 * @param string $email An email address.
 * @return bool TRUE only for a recognised, non-deliverable test address.
 */
function bhp_test_mail_guard_is_test_address( $email ) {
	$email = strtolower( trim( (string) $email ) );
	if ( '' === $email || false === strpos( $email, '@' ) ) {
		return false; // ⛔ Unreadable. Fail towards delivery.
	}

	$at     = strrpos( $email, '@' );
	$local  = substr( $email, 0, $at );
	$domain = substr( $email, $at + 1 );
	if ( '' === $local || '' === $domain ) {
		return false;
	}

	if ( in_array( $domain, bhp_test_mail_guard_reserved_domains(), true ) ) {
		return true;
	}

	$dot = strrpos( $domain, '.' );
	$tld = false === $dot ? $domain : substr( $domain, $dot + 1 );
	if ( '' !== $tld && in_array( $tld, bhp_test_mail_guard_reserved_tlds(), true ) ) {
		return true;
	}

	foreach ( bhp_test_mail_guard_local_prefixes() as $prefix ) {
		$prefix = strtolower( (string) $prefix );
		if ( '' !== $prefix && 0 === strpos( $local, $prefix ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Does any address in a recipient list qualify?
 *
 * ⭐ ONE MATCH IS ENOUGH. A message addressed to a real person AND a fixture is
 *    still a message this store should not be assembling, and mailing half of
 *    a suppressed pair is worse than mailing neither.
 *
 * @param string|string[] $recipients Comma-separated list or array.
 * @return bool
 */
function bhp_test_mail_guard_list_is_test( $recipients ) {
	if ( is_array( $recipients ) ) {
		$recipients = implode( ',', $recipients );
	}
	$recipients = (string) $recipients;
	if ( '' === trim( $recipients ) ) {
		return false;
	}

	foreach ( explode( ',', $recipients ) as $candidate ) {
		// Strip an RFC 5322 display name: `Name <addr@host>`.
		if ( preg_match( '/<([^>]+)>/', $candidate, $m ) ) {
			$candidate = $m[1];
		}
		if ( bhp_test_mail_guard_is_test_address( $candidate ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Is the ORDER behind this email a test fixture?
 *
 * ⛔ THE ORDER, NOT THE RECIPIENT. `new_order` and `failed_order` are addressed
 *    to `admin_email`, which is a perfectly real address — so a recipient test
 *    alone lets the admin notification for a fixture order straight through.
 *    The billing address on the order is what makes it a test order, and this
 *    is the only layer that can see it.
 *
 * @param WC_Email $email The email being sent.
 * @return bool TRUE if its order carries a recognised test billing address.
 */
function bhp_test_mail_guard_order_is_test( $email ) {
	if ( ! is_object( $email ) || ! isset( $email->object ) || ! is_a( $email->object, 'WC_Order' ) ) {
		return false;
	}

	try {
		return bhp_test_mail_guard_is_test_address( $email->object->get_billing_email() );
	} catch ( Exception $e ) {
		return false; // ⛔ Fail towards delivery.
	} catch ( Error $e ) {
		return false; // ⛔ Fail towards delivery.
	}
}

/**
 * Write a suppression to the WooCommerce log.
 *
 * ⭐ LOGGED, NOT SILENT. See this file's header: the one real cost of this
 *    guard is a notification that does not arrive, and a record is what makes
 *    that cost visible instead of mysterious.
 *
 * @param string $seam      Which seam fired.
 * @param string $what      Email id, or a subject for non-WooCommerce mail.
 * @param string $recipient The resolved recipient list.
 * @return void
 */
function bhp_test_mail_guard_log( $seam, $what, $recipient ) {
	if ( ! function_exists( 'wc_get_logger' ) ) {
		return;
	}

	/*
	 * ⛔ THE BLOCK'S OWN SELF-TEST IS NOT LOGGED, AND THAT IS A CORRECTION.
	 *    `tests/bootstrap-mail-guard.php` fires two probes at
	 *    `@bhp-mail-guard.invalid` every time a suite loads. Measured on the
	 *    first full-suite run: 264 of 271 log entries were those probes, and
	 *    the six real suppressions this log exists to record were buried under
	 *    them. ⭐ A log nobody can read is not an audit trail.
	 *
	 * ⚠️ THE PROBES ARE STILL BLOCKED. Only the LOGGING is skipped, and only for
	 *    this one reserved hostname, which nothing but the self-test uses.
	 */
	if ( false !== stripos( (string) $recipient, '@bhp-mail-guard.invalid' ) ) {
		return;
	}
	wc_get_logger()->info(
		sprintf( 'Blocked at %s: "%s" to "%s" — recognised test address. Nothing was sent.', $seam, $what, $recipient ),
		array( 'source' => 'bhp-test-mail-guard' )
	);
}

/**
 * SEAM 1 — stop any message addressed to a test address, inside `wp_mail()`.
 *
 * ⛔ RETURNS TRUE, NOT FALSE, AND THAT IS THE LOAD-BEARING DETAIL. `true` means
 *    "accepted, and not transported": `wp_mail()` reports success, so
 *    `WC_Email::send()` runs end to end and every ledger, marker and counter
 *    downstream of a send behaves exactly as in production. Returning `false`
 *    would report a mail failure that did not happen and would cancel that
 *    bookkeeping — the regression recorded in this file's header.
 *
 * ⭐ PRIORITY 20, ABOVE THE DEFAULT. `apply_filters()` runs every callback
 *    regardless, so a test suite's own capture at priority 10 still sees the
 *    message; this one only settles the final answer.
 *
 * @param null|bool $short_circuit WordPress's running answer.
 * @param array     $atts          Compacted `wp_mail()` arguments.
 * @return null|bool TRUE to block; the incoming value untouched otherwise.
 */
function bhp_test_mail_guard_pre_wp_mail( $short_circuit, $atts = array() ) {
	if ( ! is_array( $atts ) || ! isset( $atts['to'] ) ) {
		return $short_circuit; // ⛔ Unreadable. Fail towards delivery.
	}

	if ( ! bhp_test_mail_guard_list_is_test( $atts['to'] ) ) {
		return $short_circuit;
	}

	$to      = is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : (string) $atts['to'];
	$subject = isset( $atts['subject'] ) ? (string) $atts['subject'] : '(no subject)';
	bhp_test_mail_guard_log( 'pre_wp_mail', $subject, $to );

	return true;
}
add_filter( 'pre_wp_mail', 'bhp_test_mail_guard_pre_wp_mail', 20, 2 );

/**
 * The substitute transport for seam 2. Accepts anything, sends nothing.
 *
 * ⛔ VARIADIC ON PURPOSE. WooCommerce calls this with whatever
 *    `woocommerce_mail_callback_params` produced — five arguments today, and it
 *    is not this file's business to care if that changes.
 *
 * @return bool TRUE — "accepted, and not transported", the same convention as
 *              seam 1, so a caller's bookkeeping is not cancelled by the block.
 */
function bhp_test_mail_guard_blackhole() {
	return true;
}

/**
 * SEAM 2 — stop an ADMIN notification about a fixture order.
 *
 * ⛔ IT DELIBERATELY STANDS DOWN WHEN SEAM 1 WILL HANDLE THE MESSAGE. If the
 *    recipient is itself a test address, this returns WooCommerce's own
 *    callback untouched so that `wp_mail()` is really reached and
 *    `pre_wp_mail` does the blocking — which is what keeps `WC_Email::send()`'s
 *    end-to-end behaviour intact for the suites that assert on it.
 *
 * @param callable $callback WooCommerce's answer, normally `wp_mail`.
 * @param WC_Email $email    The email being sent.
 * @return callable
 */
function bhp_test_mail_guard_mail_callback( $callback, $email = null ) {
	if ( ! is_object( $email ) ) {
		return $callback;
	}

	$recipient = '';
	try {
		$recipient = method_exists( $email, 'get_recipient' ) ? (string) $email->get_recipient() : '';
	} catch ( Exception $e ) {
		return $callback; // ⛔ Fail towards delivery.
	} catch ( Error $e ) {
		return $callback; // ⛔ Fail towards delivery.
	}

	// Seam 1's territory — leave it alone.
	if ( bhp_test_mail_guard_list_is_test( $recipient ) ) {
		return $callback;
	}

	if ( ! bhp_test_mail_guard_order_is_test( $email ) ) {
		return $callback;
	}

	$id = isset( $email->id ) ? (string) $email->id : 'unknown';
	bhp_test_mail_guard_log( 'woocommerce_mail_callback', $id, $recipient );

	/**
	 * ⭐ THE BLOCK IS OBSERVABLE, AND THAT IS WHAT MAKES IT TESTABLE.
	 *
	 * ⛔ Fired ONLY from seam 2. A message stopped at seam 1 really did reach
	 *    `wp_mail()`, so a suite can already see it there — firing this as well
	 *    would let a suite count one message twice.
	 *
	 * @param WC_Email $email     The email that was blocked.
	 * @param string   $id        Its WooCommerce email id.
	 * @param string   $recipient Its resolved recipient list.
	 */
	do_action( 'bhp_test_mail_guard_blocked', $email, $id, $recipient );

	return 'bhp_test_mail_guard_blackhole';
}
add_filter( 'woocommerce_mail_callback', 'bhp_test_mail_guard_mail_callback', 99, 2 );
