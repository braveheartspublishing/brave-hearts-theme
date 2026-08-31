<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CHECKOUT OPT-IN → MAILCHIMP SYNC — theme 1.19.313, 2026-08-28,
 * `CYCLE168-LD-CHECKOUT-OPTIN`. Founder carrier item 360 ruling 3,
 * Andrew Signore, 2026-08-28: "get the opt in built out please".
 * ⛔ RELAYED to this desk through `chief-of-staff` and the carrier file;
 *    NOT witnessed first-hand by the session that wrote this file.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT WAS ACTUALLY BROKEN — READ THIS BEFORE CHANGING ANYTHING HERE
 * ---------------------------------------------------------------------------
 *
 * The opt-in CHECKBOX was never missing. It has been rendering at checkout,
 * unchecked, for weeks:
 *
 *     <input id="order-brave-hearts-new-book-releases"
 *            name="order_brave-hearts/new-book-releases" type="checkbox">
 *     "Email me when a new Charlotte and Henry book or edition is released,
 *      plus the occasional family reading idea. (optional)"
 *
 * ⭐ OBSERVED LIVE on staging2 checkout at window.innerWidth 1440 and 375,
 *    2026-08-28, before this file existed. It is registered by
 *    `bhp_register_marketing_consent_fields()` in `functions.php` through
 *    WooCommerce's Additional Checkout Fields API, and its value is mirrored
 *    into `_bhp_new_book_releases_optin` by `bhp_store_marketing_consent_meta()`
 *    at priority 20 on both the classic and Store API order hooks.
 *
 * ⛔⛔ AND THEN NOTHING READ IT. A repository-wide grep for
 *     `_bhp_new_book_releases_optin` on 2026-08-28 returned the definition,
 *     the mirror-write, one comment, and ZERO consumers. A buyer could tick
 *     the box, and the only thing that happened was a row of order meta.
 *
 * ⭐ THAT is why purchasers arrive in Mailchimp NON-SUBSCRIBED. It is not a
 *    missing checkbox and it is not a Mailchimp setting. It is a missing wire,
 *    and this file is the wire. Nothing about the checkbox, its label, its
 *    default state or its placement is changed by this file.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHY THIS IS NOT THE MAILCHIMP PLUGIN'S OWN CHECKBOX
 * ---------------------------------------------------------------------------
 *
 * `mailchimp-for-woocommerce` 6.1.1 ships a perfectly good Blocks opt-in
 * (`woocommerce/mailchimp-newsletter-subscription`, auto-inserted via
 * `registerCheckoutBlock`). It is NOT used, for three reasons, each verified
 * rather than assumed:
 *
 *   1. Its whole Blocks integration is gated behind `mailchimp_is_configured()`
 *      (`blocks/newsletter.php`, first statement). ⛔ STAGING IS DELIBERATELY
 *      DISCONNECTED FROM MAILCHIMP — verified live 2026-08-28: the
 *      `mailchimp-woocommerce` option carries no api key and an empty
 *      `mailchimp_list`. So the plugin's checkbox CANNOT BE TESTED ON STAGING
 *      AT ALL, and shipping it would mean shipping untested to production.
 *   2. Its label is a hardcoded translated string (`optinDefaultText`) with no
 *      block attribute and no filter. Andrew's voice cannot be put in it
 *      without a `gettext` interception, which is custom code anyway.
 *   3. `mailchimp_checkbox_defaults` is `hide` on production (verified live,
 *      read-only, 2026-08-28). ⭐ LEAVING IT AT `hide` IS DELIBERATE: it is
 *      what guarantees the plugin never renders a SECOND opt-in box beside
 *      this one. Do not "helpfully" set it to `uncheck`.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ FUNNEL ISOLATION — THE TAG CHOICE IS A SAFETY DECISION, NOT A STYLE ONE
 * ---------------------------------------------------------------------------
 *
 * `bhp_get_mailchimp_signup_tags()` defaults to `['Adventure Club']`, and the
 * parent acquisition funnel (journey 89) listens on the Adventure Kit trigger.
 * ⛔ TAGGING A BUYER INTO THE PARENT ACQUISITION FUNNEL WOULD EMAIL A the parent funnel coupon
 *    DISCOUNT CODE TO SOMEBODY WHO HAS JUST PAID FULL PRICE — carrier item 360
 *    ruling 2 records that funnel email ONE carries the parent funnel coupon, not only email 3.
 *
 * ⭐ So this path carries its OWN tags and NO lead magnet. It subscribes; it
 *    does not enrol. Which journey (if any) should listen on
 *    `Customer - Purchased` is Andrew's decision and is NOT made here.
 *
 * ---------------------------------------------------------------------------
 * ⭐ NO PROMISE IS MADE THAT THE BUSINESS DOES NOT KEEP
 * ---------------------------------------------------------------------------
 *
 * The shipped label promises new-release announcements and an occasional
 * family reading idea. It does NOT promise a free chapter, a sample, a guide
 * or a download, and this file adds no such promise. A "free chapter" opt-in
 * would need a real asset and a real journey wired to deliver it; neither was
 * verified to exist by the session that wrote this, so neither is claimed.
 *
 * ---------------------------------------------------------------------------
 * ⛔ IT CAN NEVER BREAK AN ORDER
 * ---------------------------------------------------------------------------
 *
 * Every path is wrapped. A Mailchimp outage, a missing key, a thrown
 * exception, a malformed address — all of them record a status on the order
 * and return. ⭐ The customer's purchase completes either way. An unsubscribed
 * buyer is a marketing loss; a failed order is a business loss, and this file
 * is not permitted to trade the second for the first.
 */

defined('ABSPATH') || exit;

/**
 * The order-meta key written by `bhp_store_marketing_consent_meta()` for the
 * one surviving consent field. Kept as a function rather than a constant so
 * the F12 single-field merge in `inc/checkout-experience.php` stays the one
 * place that decides which field survives.
 */
function bhp_checkout_optin_meta_key() {
    $definitions = bhp_get_marketing_consent_field_definitions();

    if (isset($definitions['new_book_releases']['meta'])) {
        return (string) $definitions['new_book_releases']['meta'];
    }

    // Whatever the first registered definition is, if the merge ever changes.
    foreach ($definitions as $field) {
        if (!empty($field['meta'])) {
            return (string) $field['meta'];
        }
    }

    return '_bhp_new_book_releases_optin';
}

/**
 * Did this order's buyer tick the box?
 *
 * ⭐ Reads the MIRRORED key, not WooCommerce's `_wc_other/{id}` raw key. The
 *    mirror is written at priority 20 by `bhp_store_marketing_consent_meta()`
 *    and is the documented stable contract; the raw key is WooCommerce's
 *    internal storage format and has changed shape before.
 */
function bhp_checkout_optin_was_given($order) {
    if (!is_object($order) || !method_exists($order, 'get_meta')) {
        return false;
    }

    return $order->get_meta(bhp_checkout_optin_meta_key()) === 'yes';
}

/**
 * The tags a checkout opt-in carries into Mailchimp.
 *
 * ⛔ DELIBERATELY NOT `Adventure Club` — see the funnel-isolation note at the
 *    top of this file. Filterable so Andrew can add an audience-source tag
 *    without a code change once he decides which journey listens.
 */
function bhp_get_checkout_optin_tags() {
    return apply_filters('bhp_checkout_optin_tags', [
        'Customer - Purchased',
        'Source: Checkout',
    ]);
}

/**
 * Swap the default signup tags for the purchase tags on this context only.
 *
 * ⛔ SCOPED BY CONTEXT. Every other signup surface on the site keeps the tags
 *    it has always had; this callback returns `$tags` untouched for them.
 */
function bhp_checkout_optin_filter_tags($tags, $context) {
    if ($context !== 'checkout_optin') {
        return $tags;
    }

    return bhp_get_checkout_optin_tags();
}
add_filter('bhp_mailchimp_signup_tags', 'bhp_checkout_optin_filter_tags', 10, 2);

/**
 * Tell `mailchimp-for-woocommerce` that this customer opted in.
 *
 * ⭐ WHY THIS EXISTS AT ALL, GIVEN WE ALSO SUBSCRIBE DIRECTLY: the MC4WOO
 *    plugin syncs the ORDER and the CUSTOMER as ecommerce records, and it
 *    decides `opt_in_status` from exactly this meta key. Without it the
 *    plugin's own sync keeps writing the contact as transactional and can
 *    undo, in the store record, what the direct subscribe just achieved.
 *
 * ⛔ ORDERING MATTERS AND IS THE REASON FOR PRIORITY 30.
 *    `Mailchimp_Woocommerce_Newsletter_Blocks_Integration::order_processed()`
 *    writes this same key at priority 10 on
 *    `woocommerce_store_api_checkout_update_order_from_request`, reading an
 *    extension field that is not present on this site (its block is not in
 *    the checkout page markup), so it writes null. Running later is what makes
 *    the customer's actual choice the value that survives.
 *
 * ⛔ IT ONLY EVER WRITES `1`. A buyer who left the box unticked is left
 *    exactly as they are today. This file never unsubscribes anybody and never
 *    writes a `0` over a value some other subsystem set.
 */
function bhp_checkout_optin_mark_mailchimp_meta($order) {
    if (!is_object($order) || !method_exists($order, 'update_meta_data')) {
        return;
    }

    $order->update_meta_data('mailchimp_woocommerce_is_subscribed', '1');

    $user_id = method_exists($order, 'get_user_id') ? (int) $order->get_user_id() : 0;
    if ($user_id > 0) {
        update_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', '1');
    }
}

/**
 * Is this order actually paid?
 *
 * ⛔⛔ THIS GATE WAS ADDED AFTER LIVE TESTING FOUND A REAL DEFECT, and the
 *     defect is recorded here rather than quietly fixed, because the first
 *     version of this file looked correct and was not.
 *
 * ⭐ WHAT WAS OBSERVED — staging2, 2026-08-28, real browser Store API submit:
 *    `woocommerce_store_api_checkout_order_processed` FIRES EVEN WHEN THE
 *    PAYMENT THEN FAILS. Probe order 5689 ended `wc-failed` and had already
 *    been subscribed and tagged. Two things were wrong with that:
 *
 *      1. ⛔ IT TAGGED A NON-BUYER `Customer - Purchased`. That is a claim
 *         about a purchase that did not happen — a fabricated fact about a
 *         real person, which is the never-invent rule, not a nitpick.
 *      2. ⛔ IT LOCKED IN A WITHDRAWN CONSENT. The Store API reuses ONE draft
 *         order per browser session. A customer who ticks, fails payment,
 *         UNTICKS and retries was left subscribed on the strength of the
 *         earlier tick, because the idempotence guard had already marked the
 *         order synced. ⭐ OBSERVED on 5689: the mirrored consent value
 *         flipped to `no` while `_bhp_checkout_optin_synced` stayed `ok`.
 *
 * ⭐ MOVING THE TRIGGER TO PAYMENT FIXES BOTH AT ONCE. The consent state is
 *    read at the moment money actually changes hands, so the customer's LAST
 *    expressed intent is the one that counts, and nobody is called a purchaser
 *    who has not purchased.
 *
 * ⚠️ THE TRADE-OFF, STATED RATHER THAN HIDDEN: somebody who ticks the box and
 *    then abandons at payment is NOT subscribed. That is deliberate — this is
 *    a PURCHASER opt-in and its tag says so. An abandoned-checkout opt-in
 *    would need its own tags and its own honest label, and that is Andrew's
 *    decision, not this file's.
 */
function bhp_checkout_optin_order_is_paid($order) {
    if (!is_object($order)) {
        return false;
    }

    if (method_exists($order, 'is_paid') && $order->is_paid()) {
        return true;
    }

    return in_array($order->get_status(), ['processing', 'completed'], true);
}

/**
 * The wire itself.
 *
 * ⭐ Fires on PAYMENT, not on order creation — see the long note above. Every
 *    registration is at priority 30, comfortably after
 *    `bhp_store_marketing_consent_meta()` at 20, which is what puts the
 *    mirrored consent value on the order in the first place.
 *
 * @param int $order_id
 */
function bhp_checkout_optin_sync($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    /*
     * ⛔ THE PAID GATE. Nothing below runs for an unpaid, failed or abandoned
     *    order. See `bhp_checkout_optin_order_is_paid()` for the two live
     *    defects this closes.
     */
    if (!bhp_checkout_optin_order_is_paid($order)) {
        return;
    }

    /*
     * ⭐ IDEMPOTENCE IS NOT OPTIONAL HERE. Both hooks below can fire for one
     *    order in some payment flows, and a retried webhook can fire either
     *    again. A second run would re-POST the same address to Mailchimp.
     */
    if ($order->get_meta('_bhp_checkout_optin_synced') !== '') {
        return;
    }

    if (!bhp_checkout_optin_was_given($order)) {
        return;
    }

    $email = sanitize_email((string) $order->get_billing_email());
    if (!$email || !is_email($email)) {
        $order->update_meta_data('_bhp_checkout_optin_synced', 'invalid_email');
        $order->update_meta_data('_bhp_checkout_optin_synced_at', current_time('mysql', true));
        $order->save();
        return;
    }

    // Do this before the network call: it must land even if Mailchimp is down.
    bhp_checkout_optin_mark_mailchimp_meta($order);

    $status = 'failed';

    try {
        $result = bhp_process_signup([
            'email'         => $email,
            'name'          => (string) $order->get_billing_first_name(),
            'context'       => 'checkout_optin',
            'audience_type' => 'parents_families',
            /*
             * ⛔ EMPTY ON PURPOSE. A lead-magnet value is what enrols a
             *    contact in an acquisition journey. See the funnel-isolation
             *    note at the top of this file.
             */
            'lead_magnet'   => '',
            'source_page'   => wc_get_checkout_url(),
            'require_name'  => false,
        ]);

        $status = (!empty($result['ok']))
            ? 'ok'
            : 'failed:' . sanitize_key((string) ($result['code'] ?? 'unknown'));
    } catch (Throwable $exception) {
        /*
         * ⛔ SWALLOWED DELIBERATELY, AND RECORDED RATHER THAN HIDDEN. This
         *    runs inside order creation. Letting a Mailchimp exception escape
         *    would surface as a failed checkout to a customer who has already
         *    paid.
         */
        $status = 'exception';
        do_action('bhp_checkout_optin_exception', $exception, $order_id);
    }

    $order->update_meta_data('_bhp_checkout_optin_synced', $status);
    $order->update_meta_data('_bhp_checkout_optin_synced_at', current_time('mysql', true));
    $order->save();

    do_action('bhp_checkout_optin_synced', $order_id, $email, $status);
}
/*
 * ⛔⛔ REGISTERED ON PAYMENT, NOT ON ORDER CREATION. The two order-creation
 *     hooks (`woocommerce_checkout_order_processed` and
 *     `woocommerce_store_api_checkout_order_processed`) were the ORIGINAL
 *     registrations and are deliberately NOT used — they fire before the
 *     payment result is known, which is exactly how probe order 5689 got
 *     subscribed and tagged as a purchaser while ending up `wc-failed`.
 *     Do not add them back.
 *
 * ⭐ THREE HOOKS, BECAUSE ONE IS NOT ENOUGH:
 *      · `woocommerce_payment_complete` covers the ordinary gateway path.
 *      · `woocommerce_order_status_processing` / `_completed` cover orders
 *        that reach a paid state without a payment_complete call — a zero
 *        total, a manual admin transition, or a gateway that sets the status
 *        directly.
 *    The idempotence guard inside makes the overlap harmless: whichever fires
 *    first does the work and the rest return immediately.
 */
add_action('woocommerce_payment_complete', 'bhp_checkout_optin_sync', 30, 1);
add_action('woocommerce_order_status_processing', 'bhp_checkout_optin_sync', 30, 1);
add_action('woocommerce_order_status_completed', 'bhp_checkout_optin_sync', 30, 1);
