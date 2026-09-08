<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE CHECKOUT OPT-IN WIRE SUITE — theme 1.19.313, 2026-08-28,
 * `CYCLE168-LD-CHECKOUT-OPTIN`. Founder carrier item 360 ruling 3.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING ONLY via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle168-checkout-optin.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE IS ACTUALLY GUARDING
 * ---------------------------------------------------------------------------
 * The checkout opt-in CHECKBOX already existed and already rendered unchecked.
 * ⛔ NOTHING READ ITS VALUE. A repo-wide grep for `_bhp_new_book_releases_optin`
 *    on 2026-08-28 found the definition, the mirror-write and ZERO consumers,
 *    which is why purchasers were arriving in Mailchimp NON-SUBSCRIBED.
 *
 * ⭐ So the assertions that matter are NOT "is there a checkbox". They are:
 *    a ticked box produces a SUBSCRIBED payload with the purchase tags; an
 *    unticked box produces NOTHING; and neither can break an order.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ IT REFUSES TO RUN ANYWHERE BUT STAGING, AND ONLY BEHIND THE STUB
 * ---------------------------------------------------------------------------
 * This suite drives the real subscribe path. On production that would write
 * real contacts into the founder's live audience of a few dozen people.
 * §0 aborts before a single assertion unless the recording stub is the
 * transport in play. ⛔ Do not "temporarily" relax §0.
 *
 * ⭐ WHAT "SUBSCRIBED" HONESTLY MEANS IN A PASS HERE: the payload arrived at
 *    the exact point where the real MC4WP client would transmit it, carrying
 *    `status: subscribed`, the address and the tags. ⛔ IT DOES NOT MEAN A
 *    CONTACT WAS CREATED IN MAILCHIMP. Nothing here makes an HTTP call.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IT WRITES: temporary WooCommerce orders on STAGING, each tagged with
 *   `_bhp_cycle168_probe`, and every one of them is force-deleted in §9.
 *   It touches NO product, price, coupon, stock, shipping, tax or payment
 *   record, and no option.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['bhp_oi_pass'] = 0;
$GLOBALS['bhp_oi_fail'] = 0;
$GLOBALS['bhp_oi_orders'] = array();

function bhp_oi_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_oi_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_oi_fail']++;
		echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

function bhp_oi_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * A throwaway staging order carrying a given opt-in state.
 *
 * ⛔ CREATED UNPAID (`pending`) ON PURPOSE. The wire fires on PAYMENT, so a
 *    probe that was born paid would never exercise the transition that
 *    actually triggers it.
 */
function bhp_oi_make_order( $email, $optin ) {
	$order = wc_create_order();
	$order->set_billing_email( $email );
	$order->set_billing_first_name( 'Cycle168' );
	$order->update_meta_data( '_bhp_cycle168_probe', '1' );
	$order->update_meta_data( bhp_checkout_optin_meta_key(), $optin ? 'yes' : 'no' );
	$order->set_status( 'pending' );
	$order->save();
	$GLOBALS['bhp_oi_orders'][] = $order->get_id();
	return $order;
}

/**
 * Take the order to a paid state THROUGH THE REAL STATUS TRANSITION.
 *
 * ⭐ DELIBERATELY NOT a direct call to `bhp_checkout_optin_sync()`. Driving
 *    `update_status()` means the registered hook is what does the work, so
 *    these assertions prove the WIRING and not merely the function body.
 */
function bhp_oi_pay( $order, $status = 'processing' ) {
	$order->update_status( $status, 'CYCLE168 probe' );
	return wc_get_order( $order->get_id() );
}

/** The stub's recorded calls, cleared first so each probe reads only its own. */
function bhp_oi_calls() {
	$payload = BHP_Mailchimp_Staging_Stub::last_payload();
	return isset( $payload['calls'] ) && is_array( $payload['calls'] ) ? $payload['calls'] : array();
}

function bhp_oi_call_of_type( $calls, $method ) {
	foreach ( $calls as $call ) {
		if ( isset( $call['method'] ) && $call['method'] === $method ) {
			return $call;
		}
	}
	return array();
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §0 · THE REFUSAL GATE. Staging, behind the stub, or nothing.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§0 REFUSAL GATE — staging only, stub transport only' );

if ( ! class_exists( 'BHP_Mailchimp_Staging_Stub' ) ) {
	echo "ABORT  the staging stub class is not loaded. Refusing to drive the subscribe path.\n";
	echo "SUITE FAIL\n";
	return;
}
bhp_oi_ok( '§0.1 the stub class is loaded', true );

if ( ! BHP_Mailchimp_Staging_Stub::is_staging_install() ) {
	echo "ABORT  not the staging install. This suite subscribes and will not run here.\n";
	echo "SUITE FAIL\n";
	return;
}
bhp_oi_ok( '§0.2 this is the staging install', true );

bhp_oi_ok(
	'§0.3 and it is NOT production — home_url() confirms the environment',
	false === strpos( home_url(), '//braveheartspublishing.com' ),
	home_url()
);

$transport = apply_filters( 'bhp_mailchimp_api_transport', null );
if ( ! ( $transport instanceof BHP_Mailchimp_Staging_Stub ) ) {
	echo "ABORT  the transport in play is NOT the stub. Refusing to subscribe.\n";
	echo "SUITE FAIL\n";
	return;
}
bhp_oi_ok( '§0.4 the transport in play is the STUB, not a real API client', true );

bhp_oi_ok(
	'§0.5 and the audience in play is NOT production\'s',
	bhp_get_mailchimp_list_id() !== '2c0c9a25a3',
	'list=' . bhp_get_mailchimp_list_id()
);

foreach ( array(
	'bhp_checkout_optin_sync',
	'bhp_checkout_optin_meta_key',
	'bhp_checkout_optin_was_given',
	'bhp_checkout_optin_order_is_paid',
	'bhp_get_checkout_optin_tags',
	'bhp_process_signup',
) as $fn ) {
	bhp_oi_ok( "§0.6 {$fn}() is loaded", function_exists( $fn ) );
}

if ( ! function_exists( 'wc_create_order' ) ) {
	echo "ABORT  WooCommerce is not loaded.\n";
	echo "SUITE FAIL\n";
	return;
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE CHECKBOX ITSELF IS UNCHANGED BY THIS BUILD.
 *
 * ⭐ These assert what was ALREADY true, on purpose. The build's whole claim
 *    is that it added a wire and touched nothing about the control.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§1 the checkbox is untouched' );

$definitions = bhp_get_marketing_consent_field_definitions();

bhp_oi_ok( '§1.1 exactly ONE consent field is registered (F12 merge holds)', count( $definitions ) === 1, 'count=' . count( $definitions ) );
bhp_oi_ok( '§1.2 the surviving field id is brave-hearts/new-book-releases', isset( $definitions['new_book_releases']['id'] ) && $definitions['new_book_releases']['id'] === 'brave-hearts/new-book-releases' );
bhp_oi_ok( '§1.3 the mirrored meta key is _bhp_new_book_releases_optin', bhp_checkout_optin_meta_key() === '_bhp_new_book_releases_optin', bhp_checkout_optin_meta_key() );

$label = isset( $definitions['new_book_releases']['label'] ) ? $definitions['new_book_releases']['label'] : '';
bhp_oi_ok( '§1.4 the label is non-empty', $label !== '' );
bhp_oi_ok( '§1.5 the label contains NO em dash (house copy rail)', false === strpos( $label, "\xE2\x80\x94" ), $label );
bhp_oi_ok(
	'§1.6 the label uses no company "we" (voice rule §9.1)',
	0 === preg_match( '/\b(we|us|our)\b/i', $label ),
	$label
);
bhp_oi_ok(
	'§1.7 the label promises nothing the business has not verified it delivers',
	0 === preg_match( '/\b(free chapter|sample chapter|download|printable|guide)\b/i', $label ),
	$label
);

/*
 * ⛔ THE FIELD IS NOT `required`. WooCommerce renders an unrequired checkbox
 *    unchecked; there is no "default checked" option in the Additional
 *    Checkout Fields API, so an unchecked default is structural here rather
 *    than a setting somebody could flip by accident.
 */
bhp_oi_ok(
	'§1.8 the field is NOT required (so it renders unchecked and never blocks an order)',
	empty( $definitions['new_book_releases']['required'] )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE TICKED PATH — a SUBSCRIBED payload with the purchase tags.
 *      This is the assertion the whole workstream exists for.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§2 ticked -> subscribed + tagged' );

BHP_Mailchimp_Staging_Stub::clear();
$yes_email = 'bhp-cycle168+optin-yes@example.com';
$yes_order = bhp_oi_make_order( $yes_email, true );

bhp_oi_ok( '§2.0 the probe order records the opt-in as given', bhp_checkout_optin_was_given( $yes_order ) );
bhp_oi_ok( '§2.0b and while it is UNPAID, nothing has been sent', empty( bhp_oi_calls() ), 'calls=' . count( bhp_oi_calls() ) );

/* ⭐ The registered hook does the work here, not a direct function call. */
$yes_order = bhp_oi_pay( $yes_order );

$calls = bhp_oi_calls();
$add   = bhp_oi_call_of_type( $calls, 'add_list_member' );
$tagc  = bhp_oi_call_of_type( $calls, 'update_list_member_tags' );

bhp_oi_ok( '§2.1 the subscribe call REACHED THE API BOUNDARY', ! empty( $add ), 'calls=' . count( $calls ) );
bhp_oi_ok(
	'§2.2 it carries status "subscribed" (NOT transactional)',
	isset( $add['args']['status'] ) && $add['args']['status'] === 'subscribed',
	isset( $add['args']['status'] ) ? $add['args']['status'] : 'absent'
);
bhp_oi_ok(
	'§2.3 it carries the buyer\'s address',
	isset( $add['args']['email_address'] ) && $add['args']['email_address'] === $yes_email,
	isset( $add['args']['email_address'] ) ? $add['args']['email_address'] : 'absent'
);
bhp_oi_ok(
	'§2.4 the audience merge field records parents_families',
	isset( $add['args']['merge_fields']['AUDIENCE'] ) && $add['args']['merge_fields']['AUDIENCE'] === 'parents_families',
	isset( $add['args']['merge_fields']['AUDIENCE'] ) ? $add['args']['merge_fields']['AUDIENCE'] : 'absent'
);

$tag_names = array();
if ( isset( $tagc['args']['tags'] ) && is_array( $tagc['args']['tags'] ) ) {
	foreach ( $tagc['args']['tags'] as $t ) {
		if ( isset( $t['name'] ) ) {
			$tag_names[] = $t['name'];
		}
	}
}

bhp_oi_ok( '§2.5 a tag write REACHED THE API BOUNDARY', ! empty( $tag_names ), implode( ',', $tag_names ) );
bhp_oi_ok( '§2.6 tagged "Customer - Purchased"', in_array( 'Customer - Purchased', $tag_names, true ), implode( ',', $tag_names ) );
bhp_oi_ok( '§2.7 tagged with an audience source ("Source: Checkout")', in_array( 'Source: Checkout', $tag_names, true ), implode( ',', $tag_names ) );

/* ⛔ THE FUNNEL-ISOLATION ASSERTION. See the note at the top of
 *    inc/checkout-optin-sync.php: the parent acquisition journey emails a
 *    the parent funnel coupon discount, and a buyer who just paid full price must never be
 *    enrolled into it by a checkout tick. */
bhp_oi_ok(
	'§2.8 NOT tagged "Adventure Club" — a buyer is never enrolled in the parent discount funnel',
	! in_array( 'Adventure Club', $tag_names, true ),
	implode( ',', $tag_names )
);
bhp_oi_ok(
	'§2.9 no lead magnet is claimed on the contact',
	empty( $add['args']['merge_fields']['LEADMAG'] ),
	isset( $add['args']['merge_fields']['LEADMAG'] ) ? $add['args']['merge_fields']['LEADMAG'] : '(absent)'
);

$yes_fresh = wc_get_order( $yes_order->get_id() );
bhp_oi_ok( '§2.10 the order records the sync outcome', $yes_fresh->get_meta( '_bhp_checkout_optin_synced' ) === 'ok', $yes_fresh->get_meta( '_bhp_checkout_optin_synced' ) );
bhp_oi_ok( '§2.11 the sync is timestamped', $yes_fresh->get_meta( '_bhp_checkout_optin_synced_at' ) !== '' );

/*
 * ⭐ THE PLUGIN-SIDE HALF. `mailchimp-for-woocommerce` decides `opt_in_status`
 *    for its own store sync from exactly this key. Without it the plugin keeps
 *    writing the contact as transactional and undoes, in the store record,
 *    what the subscribe just achieved.
 */
bhp_oi_ok(
	'§2.12 mailchimp_woocommerce_is_subscribed is set, so the plugin sync agrees',
	$yes_fresh->get_meta( 'mailchimp_woocommerce_is_subscribed' ) === '1',
	var_export( $yes_fresh->get_meta( 'mailchimp_woocommerce_is_subscribed' ), true )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · THE UNTICKED PATH — byte-for-byte today's behaviour. Nothing happens.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§3 unticked -> nothing, exactly as today' );

BHP_Mailchimp_Staging_Stub::clear();
$no_email = 'bhp-cycle168+optin-no@example.com';
$no_order = bhp_oi_make_order( $no_email, false );

bhp_oi_ok( '§3.0 the probe order records NO opt-in', ! bhp_checkout_optin_was_given( $no_order ) );

$no_order = bhp_oi_pay( $no_order );

$no_calls = bhp_oi_calls();
bhp_oi_ok( '§3.1 NOTHING reached the API boundary', empty( $no_calls ), 'calls=' . count( $no_calls ) );

$no_fresh = wc_get_order( $no_order->get_id() );
bhp_oi_ok( '§3.2 no sync status is written', $no_fresh->get_meta( '_bhp_checkout_optin_synced' ) === '' );
bhp_oi_ok(
	'§3.3 mailchimp_woocommerce_is_subscribed is NOT set — the file never opts anyone in silently',
	$no_fresh->get_meta( 'mailchimp_woocommerce_is_subscribed' ) === '',
	var_export( $no_fresh->get_meta( 'mailchimp_woocommerce_is_subscribed' ), true )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · IDEMPOTENCE — both order hooks can fire for one order, and a retried
 *      webhook can fire either again. A second run must not re-POST.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§4 idempotence' );

BHP_Mailchimp_Staging_Stub::clear();
bhp_checkout_optin_sync( $yes_order->get_id() );
bhp_oi_pay( wc_get_order( $yes_order->get_id() ), 'completed' );
bhp_checkout_optin_sync( $yes_order->get_id() );
$repeat_calls = bhp_oi_calls();
bhp_oi_ok(
	'§4.1 re-running, and a second paid transition, send NOTHING',
	empty( $repeat_calls ),
	'calls=' . count( $repeat_calls )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · IT CAN NEVER BREAK AN ORDER.
 *
 * ⭐ Simulated by making the transport throw. The customer has already paid by
 *    the time this code runs; a thrown exception would surface as a failed
 *    checkout.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§5 a Mailchimp outage cannot fail a checkout' );

class BHP_OI_Exploding_Transport {
	public function add_list_member( $list_id, $args, $update_existing = false ) {
		throw new RuntimeException( 'simulated Mailchimp outage' );
	}
	public function update_list_member_tags( $list_id, $email, $args ) {
		throw new RuntimeException( 'simulated Mailchimp outage' );
	}
}

$exploder = static function () {
	return new BHP_OI_Exploding_Transport();
};
add_filter( 'bhp_mailchimp_api_transport', $exploder, 100 );

$boom_order = bhp_oi_make_order( 'bhp-cycle168+optin-boom@example.com', true );
$threw      = false;
try {
	bhp_oi_pay( $boom_order );
} catch ( Throwable $e ) {
	$threw = true;
}
remove_filter( 'bhp_mailchimp_api_transport', $exploder, 100 );

bhp_oi_ok( '§5.1 the sync did NOT throw into the order flow', ! $threw );

$boom_fresh = wc_get_order( $boom_order->get_id() );
bhp_oi_ok(
	'§5.2 the failure is RECORDED on the order rather than hidden',
	strpos( (string) $boom_fresh->get_meta( '_bhp_checkout_optin_synced' ), 'failed' ) === 0
		|| $boom_fresh->get_meta( '_bhp_checkout_optin_synced' ) === 'exception',
	$boom_fresh->get_meta( '_bhp_checkout_optin_synced' )
);
bhp_oi_ok(
	'§5.3 the consent itself is still on the order, so it can be replayed',
	bhp_checkout_optin_was_given( $boom_fresh )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · A MALFORMED ADDRESS IS HANDLED, NOT THROWN.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§6 malformed address' );

BHP_Mailchimp_Staging_Stub::clear();
$bad = wc_create_order();
$bad->set_billing_first_name( 'Cycle168' );
$bad->update_meta_data( '_bhp_cycle168_probe', '1' );
$bad->update_meta_data( bhp_checkout_optin_meta_key(), 'yes' );
$bad->set_status( 'pending' );
$bad->save();
$GLOBALS['bhp_oi_orders'][] = $bad->get_id();

$bad_fresh = bhp_oi_pay( $bad );

bhp_oi_ok( '§6.1 an empty billing email sends nothing', empty( bhp_oi_calls() ) );
bhp_oi_ok( '§6.2 and is recorded as invalid_email', $bad_fresh->get_meta( '_bhp_checkout_optin_synced' ) === 'invalid_email', $bad_fresh->get_meta( '_bhp_checkout_optin_synced' ) );

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · THE TAG FILTER IS SCOPED — every other signup surface is untouched.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§7 no other signup surface changed' );

/*
 * ⚠️ CORRECTED 2026-08-28 AFTER THE FIRST RUN, and the correction is recorded
 *    rather than quietly swapped in, because what it revealed is the whole
 *    reason §2.8 exists.
 *
 *    This assertion originally read "the parent popup still tags Adventure
 *    Club", taken from the DEFAULT in `bhp_get_mailchimp_signup_tags()`. It
 *    FAILED, and the failure was the test being wrong, not the code. The
 *    parent popup's real tags, observed on staging 2026-08-28, are:
 *
 *        Reluctant Reader Adventure Kit, Audience: Parent/Grandparent,
 *        Source: Parent Popup
 *
 * ⛔⛔ `Reluctant Reader Adventure Kit` IS THE TRIGGER TAG THAT JOURNEY 89
 *     LISTENS ON, and journey 89's email ONE carries the the parent funnel coupon discount
 *     (founder carrier item 360 ruling 2). ⭐ So a checkout opt-in that had
 *     simply inherited the default tags would have emailed a discount code to
 *     a customer who had just paid full price. §2.8 is not defensive
 *     boilerplate; it is guarding a live, reachable defect.
 */
$parent_tags = bhp_get_mailchimp_signup_tags( 'parent_popup', 'parents_families', 'reluctant_reader_adventure_kit', home_url( '/' ) );
bhp_oi_ok(
	'§7.1 the parent popup still carries its own Adventure Kit trigger tag',
	in_array( 'Reluctant Reader Adventure Kit', $parent_tags, true ),
	implode( ',', $parent_tags )
);
bhp_oi_ok(
	'§7.2 and does NOT pick up the checkout purchase tag',
	! in_array( 'Customer - Purchased', $parent_tags, true ),
	implode( ',', $parent_tags )
);

$checkout_tags = bhp_get_mailchimp_signup_tags( 'checkout_optin', 'parents_families', '', wc_get_checkout_url() );
bhp_oi_ok(
	'§7.3 the checkout context does swap to the purchase tags',
	in_array( 'Customer - Purchased', $checkout_tags, true ) && ! in_array( 'Adventure Club', $checkout_tags, true ),
	implode( ',', $checkout_tags )
);
bhp_oi_ok(
	'§7.4 and a buyer NEVER carries the journey-89 trigger tag (the parent funnel coupon to a full-price customer)',
	! in_array( 'Reluctant Reader Adventure Kit', $checkout_tags, true ),
	implode( ',', $checkout_tags )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · THE HOOKS ARE REGISTERED, ON BOTH CHECKOUT TRANSPORTS, AFTER THE
 *      MIRROR-WRITE THAT THIS FILE DEPENDS ON.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§8 hook registration and ordering' );

bhp_oi_ok( '§8.1 registered on woocommerce_payment_complete', has_action( 'woocommerce_payment_complete', 'bhp_checkout_optin_sync' ) === 30 );
bhp_oi_ok( '§8.2 registered on woocommerce_order_status_processing', has_action( 'woocommerce_order_status_processing', 'bhp_checkout_optin_sync' ) === 30 );
bhp_oi_ok( '§8.3 registered on woocommerce_order_status_completed', has_action( 'woocommerce_order_status_completed', 'bhp_checkout_optin_sync' ) === 30 );

/*
 * ⛔⛔ THE REGRESSION GUARD FOR THE DEFECT FOUND LIVE ON 2026-08-28.
 *     The first version of this build registered on the two ORDER-CREATION
 *     hooks. Those fire BEFORE the payment result is known, and probe order
 *     5689 was subscribed and tagged `Customer - Purchased` while ending up
 *     `wc-failed`. If either registration ever comes back, these fail.
 */
bhp_oi_ok(
	'§8.4 NOT registered on the Store API order-creation hook (fires pre-payment)',
	false === has_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_checkout_optin_sync' )
);
bhp_oi_ok(
	'§8.5 NOT registered on the classic order-creation hook either',
	false === has_action( 'woocommerce_checkout_order_processed', 'bhp_checkout_optin_sync' )
);
bhp_oi_ok(
	'§8.6 the mirror-write this file reads still runs at order creation, priority 20',
	has_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_store_marketing_consent_meta' ) === 20
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8b · THE PAID GATE — an unpaid, failed or abandoned order sends NOTHING,
 *       so nobody is ever tagged a purchaser who did not purchase.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§8b the paid gate' );

BHP_Mailchimp_Staging_Stub::clear();
$unpaid = bhp_oi_make_order( 'bhp-cycle168+optin-unpaid@example.com', true );

bhp_oi_ok( '§8b.1 a pending order is not treated as paid', ! bhp_checkout_optin_order_is_paid( $unpaid ), $unpaid->get_status() );

bhp_checkout_optin_sync( $unpaid->get_id() );
bhp_oi_ok( '§8b.2 syncing an UNPAID order sends nothing', empty( bhp_oi_calls() ), 'calls=' . count( bhp_oi_calls() ) );

$failed = bhp_oi_make_order( 'bhp-cycle168+optin-failed@example.com', true );
$failed = bhp_oi_pay( $failed, 'failed' );
bhp_oi_ok( '§8b.3 a FAILED order is not treated as paid', ! bhp_checkout_optin_order_is_paid( $failed ), $failed->get_status() );
bhp_oi_ok( '§8b.4 and a failed payment sends nothing', empty( bhp_oi_calls() ), 'calls=' . count( bhp_oi_calls() ) );
bhp_oi_ok(
	'§8b.5 a failed order is NEVER tagged Customer - Purchased',
	$failed->get_meta( '_bhp_checkout_optin_synced' ) === '',
	$failed->get_meta( '_bhp_checkout_optin_synced' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8c · THE WITHDRAWN-CONSENT REGRESSION — observed on probe order 5689.
 *
 * ⭐ The Store API reuses ONE draft order per browser session. Tick, fail
 *    payment, UNTICK, retry successfully. The customer's LAST intent is "no"
 *    and it must be the one that counts.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§8c tick -> fail -> untick -> pay' );

BHP_Mailchimp_Staging_Stub::clear();
$flip = bhp_oi_make_order( 'bhp-cycle168+optin-flip@example.com', true );

/* The failed first attempt. */
$flip = bhp_oi_pay( $flip, 'failed' );
bhp_oi_ok( '§8c.1 the failed first attempt sent nothing', empty( bhp_oi_calls() ), 'calls=' . count( bhp_oi_calls() ) );

/* The customer changes their mind on the same reused draft order. */
$flip->update_meta_data( bhp_checkout_optin_meta_key(), 'no' );
$flip->save();

$flip = bhp_oi_pay( $flip, 'processing' );
bhp_oi_ok(
	'§8c.2 the successful retry honours the WITHDRAWN consent and sends nothing',
	empty( bhp_oi_calls() ),
	'calls=' . count( bhp_oi_calls() )
);
bhp_oi_ok(
	'§8c.3 and the customer is not marked subscribed for the plugin sync either',
	$flip->get_meta( 'mailchimp_woocommerce_is_subscribed' ) === '',
	var_export( $flip->get_meta( 'mailchimp_woocommerce_is_subscribed' ), true )
);

/* And the mirror image: unticked first, ticked on the successful retry. */
BHP_Mailchimp_Staging_Stub::clear();
$flip2 = bhp_oi_make_order( 'bhp-cycle168+optin-flip2@example.com', false );
$flip2 = bhp_oi_pay( $flip2, 'failed' );
$flip2->update_meta_data( bhp_checkout_optin_meta_key(), 'yes' );
$flip2->save();
$flip2 = bhp_oi_pay( $flip2, 'processing' );
bhp_oi_ok(
	'§8c.4 the mirror image works too: a late tick on the paid attempt DOES subscribe',
	! empty( bhp_oi_call_of_type( bhp_oi_calls(), 'add_list_member' ) ),
	'calls=' . count( bhp_oi_calls() )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · CLEANUP. Every probe order is force-deleted.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_oi_head( '§9 cleanup' );

$deleted = 0;
foreach ( array_unique( $GLOBALS['bhp_oi_orders'] ) as $id ) {
	$o = wc_get_order( $id );
	if ( $o ) {
		$o->delete( true );
		$deleted++;
	}
}
bhp_oi_ok( '§9.1 every probe order was deleted', $deleted === count( array_unique( $GLOBALS['bhp_oi_orders'] ) ), "deleted={$deleted}" );

$leftover = 0;
foreach ( array_unique( $GLOBALS['bhp_oi_orders'] ) as $id ) {
	if ( wc_get_order( $id ) ) {
		$leftover++;
	}
}
bhp_oi_ok( '§9.2 nothing is left behind', $leftover === 0, "leftover={$leftover}" );

BHP_Mailchimp_Staging_Stub::clear();
bhp_oi_ok( '§9.3 the stub transient was cleared', BHP_Mailchimp_Staging_Stub::last_payload() === array() );

echo "\n";
echo "PASSED: {$GLOBALS['bhp_oi_pass']}   FAILED: {$GLOBALS['bhp_oi_fail']}\n";
echo ( $GLOBALS['bhp_oi_fail'] === 0 ? "SUITE PASS\n" : "SUITE FAIL\n" );
if ( $GLOBALS['bhp_oi_fail'] > 0 && defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::halt( 1 );
}
