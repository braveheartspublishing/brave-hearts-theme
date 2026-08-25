<?php
/**
 * Brave Hearts Bundle Pricing — THE ACTIVITY BOOK EMAIL FIRES AT PURCHASE
 * (1.8.70). Workstream `CYCLE166-LD-ACTIVITY-EMAIL`.
 *
 * Run via WP-CLI, matching the other suites in this directory:
 *   wp eval-file wp-content/plugins/brave-hearts-bundle-pricing/tests/test-addon-email-processing-trigger.php --user=1
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT THIS SUITE EXISTS TO CATCH, AND WHY IT IS NOT PARANOIA
 * ═══════════════════════════════════════════════════════════════════════
 *
 * The 1.8.70 change is four `add_action()` calls. There is no branch to
 * cover and no value to compute, so a conventional unit test would assert
 * nothing worth asserting.
 *
 * ⛔ THE ONLY REALISTIC FAILURE MODE IS THAT A HOOK NAME IS WRONG AND THE
 *    EMAIL SILENTLY NEVER FIRES — which is EXACTLY the state 1.8.70 was
 *    written to fix, and exactly the trap the first attempt fell into:
 *    `woocommerce_order_status_processing_notification` reads like the
 *    correct hook, matches the shape of the working
 *    `..._completed_notification` hook, and IS NEVER FIRED BY WOOCOMMERCE.
 *    A wrong name here fails open into "no email", which is invisible in
 *    code review and invisible on a passing deploy.
 *
 * ⭐ SO THIS SUITE ASSERTS AGAINST THE RUNNING WOOCOMMERCE, NOT AGAINST A
 *    REMEMBERED LIST. It reads `woocommerce_email_actions` live and proves
 *    that every action this email depends on is really on it. If a future
 *    WooCommerce release renames or drops one, this suite fails on the
 *    next run instead of the store quietly ceasing to deliver a product
 *    people paid for.
 *
 * ⛔ NO DATABASE WRITE. NO ORDER IS SAVED. NO EMAIL IS SENT.
 *    The one order fixture is built in memory with `new WC_Order()` and is
 *    never `save()`d, so nothing reaches `wc_orders`. Nothing here calls
 *    `update_option()`, `wp_mail()` or `WC_Email::send()`. Safe on any
 *    environment, including production.
 *
 * @package brave-hearts-bundle-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run this via WP-CLI (wp eval-file), not directly.\n" );
	exit( 1 );
}

$failures = array();

function bhp_aept_assert( $condition, $label, array &$failures ) {
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		echo "FAIL: {$label}\n";
		$failures[] = $label;
	}
}

echo "\n=== The activity-book email fires at PURCHASE (1.8.70) ===\n\n";

/*
 * ─────────────────────────────────────────────────────────────────────
 * §1 — The email class is registered and reachable.
 * ─────────────────────────────────────────────────────────────────────
 */
WC()->mailer();
$emails = WC()->mailer()->get_emails();

$email = null;
foreach ( $emails as $candidate ) {
	if ( $candidate instanceof WC_Email && 'bhp_addon_thankyou' === $candidate->id ) {
		$email = $candidate;
		break;
	}
}

bhp_aept_assert( $email instanceof WC_Email, '§1.1 WC_Email_BHP_Addon_Thankyou is registered with WooCommerce', $failures );

if ( ! $email instanceof WC_Email ) {
	echo "\nABORT: the email class is not registered; the rest cannot be judged.\n";
	exit( 1 );
}

/*
 * ─────────────────────────────────────────────────────────────────────
 * §2 — THE TRAP. Every action the email hooks must actually be fired by
 *      WooCommerce.
 * ─────────────────────────────────────────────────────────────────────
 *
 * ⛔⛔ THE FIRST VERSION OF THIS SECTION USED A BROKEN INSTRUMENT AND THE
 *     TEST RUN CAUGHT IT — recorded here rather than quietly corrected,
 *     because the mistake is an easy one to make twice.
 *
 *     It called `apply_filters( 'woocommerce_email_actions', array() )`.
 *     ⛔ That returns the EMPTY ARRAY IT WAS HANDED. WooCommerce does not
 *     register a callback on that filter — it APPLIES the filter once, to
 *     its own literal list, inside `init_transactional_emails()`. There is
 *     nothing on the other end to add the list back. So the check reported
 *     "not on the list" for five actions that are demonstrably on it, and
 *     also made the §2.1 negative assertion PASS FOR THE WRONG REASON.
 *
 * ⭐ THE REAL INSTRUMENT IS THE REGISTRATION ITSELF. For every action on
 *    its list, `init_transactional_emails()` runs
 *    `add_action( $action, array( __CLASS__, 'send_transactional_email' ) )`,
 *    and `send_transactional_email()` is what fires the `_notification`
 *    twin this email hangs on. So asking `has_action()` whether THAT
 *    callback is attached answers the real question — "will my hook ever
 *    be called?" — against the running store, with no list to go stale.
 */
$dispatcher = array( 'WC_Emails', 'send_transactional_email' );

$has_dispatcher = static function ( $action ) use ( $dispatcher ) {
	return false !== has_action( $action, $dispatcher );
};

bhp_aept_assert(
	$has_dispatcher( 'woocommerce_order_status_completed' ),
	'§2.0 the WC_Emails dispatcher is attached to at least one known action (the instrument itself works)',
	$failures
);

/*
 * ⛔ THE NEGATIVE ASSERTION, AND IT IS THE MOST IMPORTANT LINE IN THE FILE.
 *    If `woocommerce_order_status_processing` ever DOES gain the
 *    dispatcher, the bare-status hook becomes viable and somebody will
 *    "simplify" the four transition hooks down to it. That would be
 *    correct on that day. This assertion is here so the change is a
 *    deliberate, test-driven decision rather than a guess — it fails
 *    loudly and tells the reader what it means.
 */
bhp_aept_assert(
	! $has_dispatcher( 'woocommerce_order_status_processing' ),
	'§2.1 woocommerce_order_status_processing has NO email dispatcher (so `..._processing_notification` is never fired, and the four transition hooks are required)',
	$failures
);

$required_actions = array(
	'woocommerce_order_status_pending_to_processing',
	'woocommerce_order_status_failed_to_processing',
	'woocommerce_order_status_cancelled_to_processing',
	'woocommerce_order_status_on-hold_to_processing',
	'woocommerce_order_status_completed',
);

foreach ( $required_actions as $action ) {
	bhp_aept_assert(
		$has_dispatcher( $action ),
		"§2.2 `{$action}` carries the WC_Emails dispatcher, so its _notification twin really fires",
		$failures
	);
}

/*
 * ─────────────────────────────────────────────────────────────────────
 * §3 — The email is actually attached to each of those _notification
 *      twins, at the priority that keeps it behind core's own emails and
 *      behind core's download-permission grant.
 * ─────────────────────────────────────────────────────────────────────
 */
foreach ( $required_actions as $action ) {
	$priority = has_action( $action . '_notification', array( $email, 'trigger' ) );
	bhp_aept_assert(
		20 === $priority,
		"§3.1 trigger() is hooked to `{$action}_notification` at priority 20 (found: " . var_export( $priority, true ) . ')',
		$failures
	);
}

/*
 * ⭐ THE PAID-STATUS SET IS CHECKED AGAINST WOOCOMMERCE'S OWN DEFINITION
 *    rather than a literal, so "after purchase" cannot drift away from
 *    what this store actually treats as paid.
 */
$paid_statuses = wc_get_is_paid_statuses();
bhp_aept_assert(
	in_array( 'processing', $paid_statuses, true ) && in_array( 'completed', $paid_statuses, true ),
	'§3.2 both trigger statuses are in wc_get_is_paid_statuses() — the email only ever rides a PAID order',
	$failures
);

/*
 * ⛔ NO UNPAID STATUS IS HOOKED. An on-hold or pending order must not
 *    receive a paid product.
 */
foreach ( array( 'woocommerce_order_status_pending_to_on-hold', 'woocommerce_order_status_pending_to_failed' ) as $unpaid ) {
	bhp_aept_assert(
		false === has_action( $unpaid . '_notification', array( $email, 'trigger' ) ),
		"§3.3 trigger() is NOT hooked to the unpaid transition `{$unpaid}_notification`",
		$failures
	);
}

/*
 * ─────────────────────────────────────────────────────────────────────
 * §4 — Delivery is still capped at one email per order, ACROSS both
 *      triggers. An order that walks pending -> processing -> completed
 *      passes two hooked actions; the customer must see one email.
 * ─────────────────────────────────────────────────────────────────────
 */
$addon_ids = bhp_bundle_addon_product_ids();

if ( empty( $addon_ids ) ) {
	echo "SKIP: §4 — no resolvable BHP-ACTIVITY-BOOK-01 on this environment; the feature fails closed here and there is nothing to assert.\n";
} else {
	$order = new WC_Order(); // ⛔ Never saved.
	$order->set_billing_email( 'qa-not-a-real-inbox@example.invalid' );

	$item = new WC_Order_Item_Product();
	$item->set_product_id( (int) $addon_ids[0] );
	$item->set_quantity( 1 );
	$order->add_item( $item );

	bhp_aept_assert(
		true === bhp_bundle_order_has_addon( $order ),
		'§4.1 an order carrying the add-on is detected as carrying it',
		$failures
	);

	/*
	 * ⚠ The unsaved fixture has no download permissions, so
	 *   `should_send()` declines on the no-download-row check. That is the
	 *   CORRECT behaviour and is asserted as such — it is the guard that
	 *   makes firing earlier safe, because an order whose payment never
	 *   granted a download mails nothing rather than mailing a dead link.
	 */
	bhp_aept_assert(
		false === bhp_bundle_addon_thankyou_should_send( $order ),
		'§4.2 with no granted download row, should_send() DECLINES rather than promising a link',
		$failures
	);

	$order->update_meta_data( BHP_BUNDLE_ADDON_SENT_META, current_time( 'mysql' ) );
	bhp_aept_assert(
		false === bhp_bundle_addon_thankyou_should_send( $order ),
		'§4.3 an order already marked sent never sends again (one email across both triggers)',
		$failures
	);
}

/*
 * ─────────────────────────────────────────────────────────────────────
 * §5 — The staging guard still covers this email by id. A change that
 *      made the email fire more often while leaving staging unguarded
 *      would mail QA traffic to real people.
 * ─────────────────────────────────────────────────────────────────────
 */
if ( function_exists( 'bhp_staging_mail_guard_email_ids' ) ) {
	bhp_aept_assert(
		in_array( 'bhp_addon_thankyou', bhp_staging_mail_guard_email_ids(), true ),
		'§5.1 inc/staging-mail-guard.php still lists bhp_addon_thankyou, so staging cannot mail a real customer',
		$failures
	);
} else {
	echo "SKIP: §5 — staging mail guard not loaded on this request.\n";
}

echo "\n=== RESULT ===\n";
if ( empty( $failures ) ) {
	echo "ALL PASS\n";
} else {
	echo count( $failures ) . " FAILURE(S):\n";
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
}
