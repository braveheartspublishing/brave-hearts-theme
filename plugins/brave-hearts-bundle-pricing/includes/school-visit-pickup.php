<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * AUTHOR HAND-DELIVERY AT A SCHOOL VISIT — 1.8.52 (2026-08-17,
 * `CYCLE163-LD-PICKUP-NATIVE`). NOW BUILT ON WOOCOMMERCE'S OWN LOCAL PICKUP.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, verbatim, RELAYED through the Chief of Staff and NOT
 * witnessed by this agent: *"Lets do path 3 - if we can get that done in the
 * next day or two we wont have to worry about shipping any books at all - so
 * the Path 3 starts now"*.
 *
 * ⭐ AND THE REWORK THAT PRODUCED THIS VERSION — Andrew Signore, verbatim,
 *    RELAYED through the Chief of Staff, NOT witnessed by this agent, after
 *    walking the visit-parent path on staging:
 *
 *      *"it looks like they are buying the books and getting them shipped...
 *      I dont need shipping information. for that page we need to eliminate
 *      all the 'Free Shipping' - Shipping address and put Billing Address -
 *      This will definitely confuse the parents purchasing. They will think
 *      its getting shipped."*
 *
 * WHAT IT DOES. A parent who arrives from a school's pre-visit link orders
 * and pays online exactly as anyone else does, and is offered FREE
 * "Author hand-delivery" instead of shipping. Andrew brings the signed books
 * to the school on the visit date.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT CHANGED IN 1.8.52, AND WHY IT IS A MECHANISM CHANGE RATHER THAN A
 *     COPY CHANGE
 * ---------------------------------------------------------------------------
 * 1.8.49–1.8.51 offered hand-delivery as a **$0.00 SHIPPING RATE** sitting
 * beside the ordinary one. That worked — the order was free and the books were
 * never posted — and it still read, on screen, as *a shipping choice*. The
 * checkout asked for a SHIPPING ADDRESS, headed the form "Shipping address",
 * and printed "Free shipping" language around it. Andrew's reading of that
 * screen is the correct reading of that screen.
 *
 * ⭐ THE FIX IS NOT TO REWORD THE RATE. It is to stop using a rate. WooCommerce
 *    Blocks has a first-class concept for exactly this — **LOCAL PICKUP** — and
 *    the whole behaviour Andrew asked for is what that concept already does:
 *
 *      · a **Ship / Hand delivery** toggle appears above the address form;
 *      · choosing hand delivery **HIDES the shipping-address form entirely**
 *        and shows the **Billing address** form instead;
 *      · the pickup **location** is shown in its own step;
 *      · the order summary line changes from "Delivery" to "Pickup";
 *      · the confirmation page and the order emails hide the shipping address.
 *
 *    None of that is written here. It is WooCommerce 10.9.1 behaving normally
 *    once a pickup location exists. **Verified by reading the shipped source on
 *    staging, not assumed** — `Blocks/Shipping/PickupLocation.php`,
 *    `Blocks/Shipping/ShippingController.php`,
 *    `StoreApi/Utilities/LocalPickupUtils.php`, `BlockTypes/Checkout.php`, and
 *    the built `wc-cart-checkout-base-frontend.js`, whose address hook computes
 *    `showShippingFields: !forcedBillingAddress && needsShipping &&
 *    !prefersCollection` and `showBillingFields: !needsShipping ||
 *    !useShippingAsBilling || !!prefersCollection`.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ HOW LOCAL PICKUP IS TURNED ON WITHOUT CHANGING A WOOCOMMERCE SETTING
 * ---------------------------------------------------------------------------
 * WooCommerce stores local pickup in two SITE OPTIONS:
 *
 *      `woocommerce_pickup_location_settings`  — enabled / title / cost / tax
 *      `pickup_location_pickup_locations`      — the list of locations
 *
 * ⛔ CHANGING EITHER OF THOSE IN THE DATABASE IS A WOOCOMMERCE CHECKOUT-
 *    CONFIGURATION CHANGE, WHICH IS AN ANDREW-ONLY GATE. THIS FILE DOES NOT
 *    WRITE THEM, ON ANY ENVIRONMENT, EVER. Both remain exactly as an operator
 *    left them: on staging, `enabled => "no"` and `[]`.
 *
 * ⭐ WHAT IT DOES INSTEAD: it filters the two options **at read time**, for the
 *    duration of one request, and only when that request carries a live visit
 *    flag. `option_{$name}` / `default_option_{$name}` are ordinary WordPress
 *    read filters; nothing is persisted, nothing is migrated, and deactivating
 *    this plugin restores the previous behaviour completely with no cleanup.
 *
 * ⭐ IT APPENDS. If an operator ever configures real pickup locations, ours is
 *    added AFTER theirs and theirs are returned untouched — which is also why
 *    the injected location's index is discovered rather than assumed to be 0.
 *
 * ⛔ AN UNFLAGGED VISITOR GETS THE STORED VALUE BACK, BY IDENTITY. Both filters
 *    return `$value` before doing anything else. That is asserted in
 *    `tests/test-school-visit-pickup.php`, not hoped for.
 *
 * ---------------------------------------------------------------------------
 * THE PATH, END TO END, AND WHERE EACH PIECE LIVES
 * ---------------------------------------------------------------------------
 *   1. The pre-visit link carries `?bhp_visit=<slug>`.
 *   2. `bhp_school_visit_capture_intent()` (`template_redirect`, priority 5)
 *      turns that param into a WC SESSION FLAG holding ONLY THE SLUG.
 *   3. `bhp_school_pickup_tag_package()` puts the slug into the shipping
 *      package so WooCommerce's package hash changes and the rate cache
 *      cannot serve a pre-flag rate list. ⛔ STILL LOAD-BEARING under the new
 *      mechanism, for the same reason: `WC_Shipping::calculate_shipping_for_
 *      package()` only recalculates on a cache miss.
 *   4. `bhp_school_pickup_filter_location_settings()` and
 *      `bhp_school_pickup_filter_locations()` make WooCommerce's OWN local
 *      pickup method visible, with exactly one location, for this request.
 *   5. WooCommerce's `PickupLocation::calculate_shipping()` produces the rate.
 *      `bhp_school_pickup_decorate_rate()` renames it to the approved label and
 *      attaches the hidden visit meta the order will need later.
 *   6. `bhp_school_pickup_default_chosen_method()` makes hand delivery the
 *      DEFAULT selection for a flagged session, so the parent lands on the
 *      billing-only screen rather than having to find a toggle.
 *   7. `bhp_school_pickup_mark_order()` writes the packing-list meta and a
 *      visible order note.
 *   8. `bhp_school_pickup_block_bookvault_webhook()` stops the order ever
 *      reaching the print partner. THIS IS THE SAFETY-CRITICAL ONE.
 *
 * ⭐ 1.8.50 (`CYCLE162-LD-PICKUP-FIELDS`) EXTENDS THIS FILE from
 *    `school-visit-fields.php`: two checkout fields (the child's first name for
 *    the signed dedication, and an unchecked newsletter opt-in) that exist only
 *    for a flagged session. Unchanged by 1.8.52 except that its per-request
 *    session resolver now lives HERE, as `bhp_school_visit_request_record()`,
 *    because the option filters need the same trick on the same requests.
 *    `bhp_school_visit_fields_session()` survives as a thin alias.
 *
 * ⭐ 1.8.51 (`CYCLE162-LD-VISITS-PAGE`) added the optional `time` registry field
 *    for the public `/author-visits/` page. Unchanged by 1.8.52.
 *
 * ⭐⭐ 1.8.56 (2026-08-18, `CYCLE164-LD-ORDER-WINDOW`) MOVED THE ONLINE CLOSE OFF
 *    THE STATED DEADLINE. It is the only behaviour change in this file since
 *    1.8.52, and it is four functions plus one line inside
 *    `bhp_school_visit_resolve()`:
 *
 *      · `bhp_school_visit_today()` gained a FILTER — a test seam and nothing
 *        else. With nothing hooked, behaviour is byte-identical to 1.8.55.
 *      · `bhp_school_visit_shift_days()`, `bhp_school_visit_last_order_date()`
 *        and `bhp_school_visit_online_close_date()` derive the window from the
 *        VISIT DATE. No registry field was added.
 *      · `bhp_school_visit_is_open_on()` is now the ONE place the question is
 *        answered, and `/author-visits/` routes through it too.
 *
 *    ⛔ THE STATED `cutoff` FIELD IS UNTOUCHED — same data, same sanitiser, same
 *       "Order by ..." line on the public page. It simply stops gating. Read
 *       `bhp_school_visit_last_order_date()`'s docblock before changing any of
 *       this; the two deadlines are deliberate and the gap is never advertised.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT WAS DELETED IN 1.8.52, STATED PLAINLY
 * ---------------------------------------------------------------------------
 * `bhp_school_pickup_inject_rate()` — the $0.00 `bhp_school_pickup` rate — IS
 * GONE. It is not disabled, not feature-flagged and not kept "for an edge".
 * Two mechanisms offering the same free delivery would produce two rows in the
 * same list, two possible `method_id`s on an order, and two places to keep the
 * Bookvault skip correct. One mental model is the point.
 *
 * ⭐ THE LEGACY METHOD ID IS STILL RECOGNISED, and that is NOT a second
 *    mechanism — it is backward compatibility for ORDERS ALREADY PLACED under
 *    1.8.49–1.8.51. Those orders must keep skipping Bookvault, keep rendering
 *    the hand-delivery email branch and keep showing the admin panel forever.
 *    Nothing can CREATE one any more.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE DUPLICATE-PRINT PROTECTION — READ THIS BEFORE CHANGING ANYTHING
 * ---------------------------------------------------------------------------
 * A hand-delivered order must NEVER reach Bookvault. If it does, Andrew pays
 * to print and post a book he is also carrying to the school by hand.
 *
 * ⭐ HOW THE ORDER ACTUALLY REACHES BOOKVAULT, established by reading the
 *    installed `bookvault` plugin 6.0.0 and the live webhook table on staging
 *    on 2026-08-17 — NOT from documentation, which did not record it:
 *
 *      · The plugin itself has NO automatic push. Its only outbound order
 *        call is `bvlt_resendOrder()`, reachable solely from the wp-admin
 *        single-order action and the orders-list bulk action.
 *      · The automatic push is a pair of ordinary WOOCOMMERCE WEBHOOKS —
 *        "BV Order Creation" (`woocommerce_new_order`) and "BV Order Update"
 *        (`woocommerce_update_order`, `woocommerce_order_refunded`) — posting
 *        the whole order payload to `webhooks.bookvault.app`.
 *
 *    So the skip belongs on the webhook delivery decision, and that is where
 *    it is: `woocommerce_webhook_should_deliver`, the filter
 *    `WC_Webhook::should_deliver()` returns through and `WC_Webhook::process()`
 *    checks before doing anything at all.
 *
 * ⭐ IT FAILS SAFE IN BOTH DIRECTIONS, DELIBERATELY:
 *      · Pickup-ness is decided from the ORDER'S OWN SHIPPING LINE first and
 *        the meta second, so a failed meta write cannot let an order through.
 *      · The webhook is matched by DELIVERY-URL HOST, so a renamed,
 *        re-created or duplicated Bookvault webhook is still caught, and a
 *        non-Bookvault webhook (Rutter) is not touched.
 *      · Anything it cannot parse is treated as Bookvault-bound if the host
 *        matches. It never guesses "probably fine".
 *
 * ⚠⚠ ONE CONSEQUENCE OF THE 1.8.52 MECHANISM CHANGE, STATED RATHER THAN HIDDEN.
 *    The shipping-line test now matches WooCommerce's OWN `pickup_location`
 *    method id. On this store that method has exactly one source — this file —
 *    because the stored location list is empty and only this file fills it.
 *    **But if a future operator configures a genuine store-pickup location,
 *    orders using it would ALSO be treated as hand-delivery orders and would
 *    ALSO skip the Bookvault push.** That is deliberate and it is the safe
 *    direction: a missed print is recoverable with one click of the plugin's
 *    own resend action, whereas a duplicate print costs money, ships a book
 *    nobody expects, and cannot be recalled. If real store pickup is ever
 *    introduced, narrow `bhp_school_pickup_order_is_pickup()` to require the
 *    `_bhp_visit_slug` item meta — do not widen the Bookvault push.
 *
 * ⭐ SECOND LAYER: `bhp_school_pickup_remove_resend_action()` removes the
 *    Bookvault plugin's own "Resend order to Bookvault" admin action from a
 *    pickup order, so the manual route cannot be clicked by accident either.
 *    ⚠ IT DOES NOT AND CANNOT COVER THE ORDERS-LIST BULK ACTION, which takes
 *      post IDs and never consults per-order state. That is stated, not hidden.
 *
 * ⛔ NOTHING HERE MODIFIES THE `bookvault` PLUGIN. It is a third-party plugin
 *    and is left byte-untouched.
 *
 * ---------------------------------------------------------------------------
 * ⛔ RAILS THIS FILE DOES NOT CROSS
 * ---------------------------------------------------------------------------
 * ⛔ It touches NO WooCommerce zone, NO shipping-method setting, NO
 *    `flat_rate` setting, NO coupon, NO product, NO price, NO tax, NO payment
 *    setting, on any environment. It filters two option READS at request time
 *    and decorates one rate object.
 * ⛔ It WRITES no option at all — not a WooCommerce one, not any other one. The
 *    test suite asserts that against this source file directly, by searching it
 *    for every WordPress option-writing call. ⚠ The assertion is a plain source
 *    search, so this sentence deliberately does NOT spell those function names
 *    out: a rails comment that names the thing it forbids fails the test that
 *    enforces it. That exact trap already caught this suite once, on the
 *    "BookVAULT Shipping" assertion in 1.8.49, and it is recorded there too.
 * ⛔ It never adds "BookVAULT Shipping" to anything.
 * ⛔ IT DOES NOT MOVE THE TAX LOCATION. WooCommerce's
 *    `ShippingController::filter_taxable_address()` will re-base an order's tax
 *    on the PICKUP LOCATION'S address when local pickup is chosen. The injected
 *    location therefore carries NO country, which is the condition that
 *    function requires before it will swap anything, and
 *    `woocommerce_apply_base_tax_for_local_pickup` is additionally returned
 *    FALSE for a flagged session. Belt and braces, because a silently changed
 *    tax figure is exactly the class of bug nobody notices until an accountant
 *    does. The parent is taxed on their own address, as before.
 * ⛔ NO VISIT DATA IS HARDCODED. Every school name, date and cutoff is read
 *    from the per-environment `bhp_school_visits` site option, seeded by an
 *    operator. A store with the option unset behaves EXACTLY as it did
 *    before this file existed — that is asserted in
 *    `tests/test-school-visit-pickup.php`, not hoped for.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/** The per-environment registry of visits. Seeded by an operator, never here. */
if ( ! defined( 'BHP_SCHOOL_VISIT_OPTION' ) ) {
	define( 'BHP_SCHOOL_VISIT_OPTION', 'bhp_school_visits' );
}
/** The query parameter on a school's pre-visit link. */
if ( ! defined( 'BHP_SCHOOL_VISIT_PARAM' ) ) {
	define( 'BHP_SCHOOL_VISIT_PARAM', 'bhp_visit' );
}
/** WC session key. Holds the SLUG only — never the school name or a date. */
if ( ! defined( 'BHP_SCHOOL_VISIT_SESSION_KEY' ) ) {
	define( 'BHP_SCHOOL_VISIT_SESSION_KEY', 'bhp_school_visit' );
}
/**
 * ⭐⭐ 1.8.59 (`CYCLE165-LD-VISIT-FLAG-EXPIRY`) — WC session key holding the UNIX
 *     TIMESTAMP at which this session's flag was first set (or last re-armed by
 *     the link). It is the ONLY new thing stored in a session by this build.
 *
 * ⛔ IT IS A SECOND KEY, NOT A NEW SHAPE FOR THE OLD ONE, AND THAT IS THE WHOLE
 *    REASON IT IS A SECOND KEY. On 2026-08-19 there were 48 live staging
 *    sessions holding a bare slug string under
 *    `BHP_SCHOOL_VISIT_SESSION_KEY` (27 Adams, 17 Liberty, 4 Dallas Harris,
 *    counted by `commerce-cx`). Changing that key to an array would have made
 *    every one of them unreadable in a way that fails in the WRONG direction
 *    for a parent mid-order. The old key keeps its old meaning forever.
 */
if ( ! defined( 'BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY' ) ) {
	define( 'BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY', 'bhp_school_visit_set_at' );
}
/**
 * ⭐ 1.8.59 — THE HARD TTL. Fourteen days from the moment the flag was set, or
 *    from the last time the school link itself re-armed it.
 *
 * ⛔ IT IS THE SECOND GUARD, NEVER THE FIRST. The first guard is the visit's own
 *    ONLINE CLOSE (`visit - 1`, 1.8.56), which is a fact about the calendar and
 *    is what governs every real parent. The TTL exists for the case the close
 *    cannot govern: a registry row whose `date` is a VALID date but the wrong
 *    one — `2126-08-28` instead of `2026-08-28` is one keystroke — would
 *    otherwise pin a session to paperback-only framing for a century, and
 *    nothing in 1.8.56 would ever release it.
 *
 * ⚠ WHY FOURTEEN AND NOT SEVEN OR THIRTY, STATED SO IT IS NOT RE-DERIVED:
 *    · It has to be LONGER than any real link-to-close span, or it would fire
 *      inside a live window and break the feature for an early bird. Measured
 *      against the three real visits on 2026-08-19: Adams closes in 8 days,
 *      Dallas Harris in 14, Liberty in 15. Fourteen days covers the whole of
 *      the first two and all but one day of the third — and the third is
 *      covered anyway, see the re-arm note below.
 *    · It has to be SHORTER than a browser bookmark's useful life, or it stops
 *      being a guard at all. Thirty days is a month of a shopper being shown a
 *      store that is missing its hardcover.
 *    · Andrew Signore, 2026-08-19, relayed to this session by `chief-of-staff`
 *      and NOT witnessed first-hand: *"Yeah we need an expiration on that - I
 *      want them to come back and buy more books."* Two weeks is the shortest
 *      value that cannot fire inside a real ordering window.
 *
 * ⭐ THE LINK RE-ARMS IT. Every visit to `?bhp_visit=<live slug>` writes a fresh
 *    timestamp, so a parent who keeps the school's email has an unlimited
 *    number of fresh fourteen-day windows and can never be locked out of hand
 *    delivery by this guard while the visit is still open. That is what makes
 *    fourteen days safe rather than merely short.
 *
 * ⚠ THE RESIDUAL, NAMED RATHER THAN HIDDEN: a parent who opens the link more
 *   than fourteen days before the close, never opens it again, and returns on
 *   day fifteen sees the ORDINARY storefront — paperback first, hardcover one
 *   tap away — instead of the hand-delivery framing. They lose nothing they
 *   can't recover by clicking the link in their email again, and what they see
 *   meanwhile is the full shop, which is the direction Andrew asked for.
 */
if ( ! defined( 'BHP_SCHOOL_VISIT_TTL_SECONDS' ) ) {
	define( 'BHP_SCHOOL_VISIT_TTL_SECONDS', 14 * 86400 );
}
/**
 * ⭐ 1.8.59 — the reserved value of `?bhp_visit=` that CLEARS the flag.
 *
 * ⛔ IT IS NOT LINKED ANYWHERE CUSTOMER-FACING and must not be. It exists so QA
 *    and Andrew can put a browser back to the ordinary storefront in one hop
 *    without clearing cookies, because before 1.8.59 there was no way to do it
 *    at all: `?bhp_visit=<anything-bogus>` was, and still is, a NO-OP that
 *    leaves an existing flag exactly where it was.
 *
 * ⛔ `clear` IS THEREFORE A RESERVED SLUG. A registry row keyed `clear` would be
 *    shadowed by this check and could never be entered. Nothing seeds one, all
 *    three real slugs are `<school>-<date>` shaped, and the alternative — a
 *    magic parameter nobody can guess — is worse to hand a human being.
 */
if ( ! defined( 'BHP_SCHOOL_VISIT_CLEAR_TOKEN' ) ) {
	define( 'BHP_SCHOOL_VISIT_CLEAR_TOKEN', 'clear' );
}
/**
 * ⭐ 1.8.52 — WooCommerce's OWN local-pickup method id. This is the method id a
 *    hand-delivery order now carries. It is WooCommerce's constant, not ours.
 */
if ( ! defined( 'BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID' ) ) {
	define( 'BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID', 'pickup_location' );
}
/**
 * ⛔ THE RETIRED 1.8.49–1.8.51 METHOD ID. Nothing creates a rate with this id
 *    any more. It survives ONLY so orders already placed under the old
 *    mechanism keep being recognised as hand-delivery orders forever.
 */
if ( ! defined( 'BHP_SCHOOL_PICKUP_METHOD_ID' ) ) {
	define( 'BHP_SCHOOL_PICKUP_METHOD_ID', 'bhp_school_pickup' );
}
/** WooCommerce's two local-pickup site options. READ ONLY. Never written. */
if ( ! defined( 'BHP_SCHOOL_PICKUP_SETTINGS_OPTION' ) ) {
	define( 'BHP_SCHOOL_PICKUP_SETTINGS_OPTION', 'woocommerce_pickup_location_settings' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_LOCATIONS_OPTION' ) ) {
	define( 'BHP_SCHOOL_PICKUP_LOCATIONS_OPTION', 'pickup_location_pickup_locations' );
}
/** The longest display time that will be stored. A time, not a paragraph. */
if ( ! defined( 'BHP_SCHOOL_VISIT_TIME_MAXLEN' ) ) {
	define( 'BHP_SCHOOL_VISIT_TIME_MAXLEN', 40 );
}
/** Order meta. `_bhp_school_pickup` is the flag; the rest is the packing list. */
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_FLAG' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_FLAG', '_bhp_school_pickup' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_SLUG' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_SLUG', '_bhp_school_visit_slug' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_SCHOOL' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_SCHOOL', '_bhp_school_visit_school' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_META_DATE' ) ) {
	define( 'BHP_SCHOOL_PICKUP_META_DATE', '_bhp_school_visit_date' );
}
/**
 * ⭐ 1.8.52 — HIDDEN meta attached to the pickup RATE, which WooCommerce copies
 *    verbatim onto the order's shipping item on BOTH checkout paths
 *    (`WC_Checkout::create_order_shipping_lines()`, which the Store API's
 *    `OrderController::update_order_from_cart()` also calls). The leading
 *    underscore is what keeps it out of the customer-visible item meta list.
 *
 * ⛔ THIS IS THE SESSION-LESS FALLBACK'S ONLY SOURCE under the new mechanism.
 *    1.8.49 used the visible rate meta "School"/"Visit date"; WooCommerce's own
 *    `PickupLocation` builds its meta itself and would have shown ours to the
 *    customer as extra rows under the shipping line.
 */
if ( ! defined( 'BHP_SCHOOL_PICKUP_ITEM_META_SLUG' ) ) {
	define( 'BHP_SCHOOL_PICKUP_ITEM_META_SLUG', '_bhp_visit_slug' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL' ) ) {
	define( 'BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL', '_bhp_visit_school' );
}
if ( ! defined( 'BHP_SCHOOL_PICKUP_ITEM_META_DATE' ) ) {
	define( 'BHP_SCHOOL_PICKUP_ITEM_META_DATE', '_bhp_visit_date' );
}

/* =========================================================================
 * THE REGISTRY
 * ====================================================================== */

/**
 * Today's date in the SITE's timezone, as `Y-m-d`.
 *
 * ⛔ NOT `date('Y-m-d')` and not UTC. A visit date of "2026-08-28" means the
 *    28th where the school is, and a UTC comparison would close the option up
 *    to a day early for a US store every evening. The site timezone is
 *    `America/Boise` (read live, not assumed: `wp eval 'echo
 *    wp_timezone_string();'` on staging, 2026-08-18).
 *
 * ⭐ 1.8.56 (2026-08-18, `CYCLE164-LD-ORDER-WINDOW`) MAKES THIS THE ONE MOVABLE
 *    CLOCK IN THE FEATURE, and that is the whole reason the filter exists. Every
 *    date decision in this file, and — through `bhp_author_visits_today()` — on
 *    `/author-visits/` too, is downstream of this single function. A test that
 *    can move "today" can assert the boundary on both sides at 23:59 and at
 *    00:00 without waiting for a Thursday and WITHOUT WRITING THE REGISTRY,
 *    which is the failure that destroyed the real visit rows on 2026-08-17.
 *
 * ⛔ THE FILTER CANNOT CHANGE DEFAULT BEHAVIOUR. With nothing hooked,
 *    `apply_filters()` returns the value untouched, so the production and
 *    staging code paths are byte-for-byte what they were at 1.8.55. And a
 *    filtered value that is not a real `Y-m-d` is DISCARDED rather than trusted:
 *    a broken hook falls back to the real today instead of silently opening or
 *    closing every visit.
 *
 * @return string
 */
function bhp_school_visit_today() {
	$today = function_exists( 'wp_date' ) ? wp_date( 'Y-m-d' ) : gmdate( 'Y-m-d' );

	if ( ! function_exists( 'apply_filters' ) ) {
		return $today;
	}

	/**
	 * Filter the date the school-visit window is evaluated against.
	 *
	 * A TEST SEAM, not a configuration point. Nothing in the plugin, the theme
	 * or the admin UI hooks it; it exists so a suite can stand on either side
	 * of a boundary. An unusable value is ignored.
	 *
	 * @since 1.8.56
	 * @param string $today `Y-m-d` in the site's timezone.
	 */
	$filtered = apply_filters( 'bhp_school_visit_today', $today );

	return ( is_string( $filtered ) && bhp_school_visit_is_ymd( $filtered ) ) ? $filtered : $today;
}

/**
 * Shift a `Y-m-d` by whole days. Returns '' for anything unusable.
 *
 * ⛔ ANCHORED AT MIDNIGHT **UTC**, not at site-local midnight, and that is the
 *    point rather than laziness. This function does calendar arithmetic on a
 *    label, not on an instant: "two days before 2026-08-28" is 2026-08-26 in
 *    every timezone, and a DST transition inside the interval must not be able
 *    to make it 2026-08-25. Anchoring in UTC makes the arithmetic exact.
 *    `strtotime('... -2 days')` in local time is what would drift.
 *
 * @param string $ymd  Base date.
 * @param int    $days Signed day offset.
 * @return string `Y-m-d`, or '' when the input is not a real date.
 */
function bhp_school_visit_shift_days( $ymd, $days ) {
	if ( ! bhp_school_visit_is_ymd( $ymd ) ) {
		return '';
	}
	$ts = strtotime( $ymd . ' 00:00:00 UTC' );
	if ( false === $ts ) {
		return '';
	}
	return gmdate( 'Y-m-d', $ts + ( (int) $days * 86400 ) );
}

/**
 * ⭐ THE LAST DAY AN ORDER MAY BE PLACED ONLINE, INCLUSIVE. `visit date - 2`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ TWO DEADLINES EXIST ON PURPOSE. CONFUSING THEM IS THE ONE REAL BUG THIS
 *     FUNCTION CAN HAVE, AND IT IS A DIFFERENT PAIR FROM THE `date`/`cutoff`
 *     PAIR DOCUMENTED IN `inc/author-visits.php`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   1. THE STATED DEADLINE — the registry's `cutoff` field, three days before
 *      the visit. It is what `/author-visits/` prints as "Order by ...", and it
 *      is what parents were told in the pre-visit email. ⛔ IT IS DISPLAY ONLY
 *      FROM 1.8.56. It no longer gates anything, its data is untouched, and it
 *      must keep displaying exactly as it did.
 *
 *   2. THE ACTUAL ONLINE CLOSE — 00:00 site time on the day BEFORE the visit.
 *      Ordering is therefore open through the whole of `visit - 2`, and the
 *      button is grey from the first minute of `visit - 1`. That is what this
 *      function returns the inclusive last day of.
 *
 * ⛔ THE GAP BETWEEN THEM IS DELIBERATE AND IS NEVER ADVERTISED. Andrew's
 *    instruction, RELAYED and not witnessed by this agent: *"We say 3 days
 *    before but the online cutoff is 1 day before so they can sneak in after
 *    their deadline. Gives them a time crunch they need to meet."* ⛔ NO COPY
 *    ANYWHERE MAY MENTION THE GRACE WINDOW. A page that advertises a secret
 *    extension has no deadline at all, and the test suite asserts that the
 *    printed deadline is still the stated `cutoff`.
 *
 * ⛔ DERIVED FROM `date`, NEVER STORED. No registry field was added, so no
 *    existing row needed editing and no row can drift out of sync with its own
 *    visit. A visit moved to a new day moves its own close with it.
 *
 * ⚠ KNOWN EDGE, RECORDED RATHER THAN CODED AROUND: a hand-entered row whose
 *   `cutoff` is LATER than `visit - 2` would print an "Order by" date that
 *   falls after the button has already greyed. All three real rows are
 *   `visit - 3` and none is affected. It is not clamped here because clamping
 *   in either direction would silently override the operator, and Andrew's rule
 *   is stated in terms of the VISIT date, not the cutoff.
 *
 * @param string $visit_date Registry `date`.
 * @return string `Y-m-d`, or '' when the visit date is unusable (fail CLOSED).
 */
function bhp_school_visit_last_order_date( $visit_date ) {
	return bhp_school_visit_shift_days( $visit_date, -2 );
}

/**
 * The first day on which ordering is CLOSED. `visit date - 1`, from 00:00.
 *
 * The complement of `bhp_school_visit_last_order_date()`, exposed separately
 * because it is the sentence Andrew actually said — *"the button goes off and
 * gets greyed out the morning 1 day before the read aloud"* — and a reader
 * should not have to add one to a number to check the code against it.
 *
 * @param string $visit_date Registry `date`.
 * @return string `Y-m-d`, or ''.
 */
function bhp_school_visit_online_close_date( $visit_date ) {
	return bhp_school_visit_shift_days( $visit_date, -1 );
}

/**
 * Is online ordering open for this visit on this day?
 *
 * ⛔ THE SINGLE SOURCE OF THE ANSWER. `bhp_school_visit_resolve()` (the
 *    entitlement) and `bhp_author_visits_build_rows()` (the button) both route
 *    here, so the page and the checkout cannot drift apart: an old bookmark
 *    stops granting hand-delivery at the same instant the button greys.
 *
 * ⛔ FAILS CLOSED. An unusable visit date yields '' from the helper above and
 *    this returns false.
 *
 * @param string $visit_date Registry `date`.
 * @param string $today      `Y-m-d` in the site's timezone.
 * @return bool
 */
function bhp_school_visit_is_open_on( $visit_date, $today ) {
	$last = bhp_school_visit_last_order_date( $visit_date );
	if ( '' === $last ) {
		return false;
	}
	$today = (string) $today;
	if ( '' === $today ) {
		return true;
	}
	return $today <= $last;
}

/**
 * Every registered visit, sanitised, keyed by slug.
 *
 * Fails closed on every malformed row rather than guessing: a row missing a
 * school, a date or a cutoff is dropped, not defaulted.
 *
 * ⭐ 1.8.51 (2026-08-17, `CYCLE162-LD-VISITS-PAGE`) ADDS AN OPTIONAL `time`.
 *    It is a DISPLAY STRING and nothing else: it is never parsed, never
 *    compared, never used to decide whether a visit is open, and never sent
 *    anywhere. Only `/author-visits/` renders it today.
 *
 * ⛔ THE TOLERANCE IS THE POINT, AND IT IS DELIBERATELY ASYMMETRIC. School,
 *    date and cutoff are REQUIRED and a row missing one is dropped, because
 *    each of them gates money or a promise. `time` gates nothing, so a row
 *    without it is a COMPLETE row that renders date-only. Making it required
 *    would have silently deleted the three visits already seeded on both
 *    environments the moment this version shipped.
 *
 * ⭐⭐ 1.8.75 (`CYCLE168-LD-AMITY-STOCK-SUPPRESSION`) ADDS AN OPTIONAL
 *     `hide_stock` BOOLEAN, and it takes the SAME tolerance as `time` for the
 *     same reason: a row without it is a COMPLETE row. It defaults to FALSE,
 *     so every visit already seeded on either environment keeps exactly the
 *     behaviour it had at 1.8.74 on the strength of the registry alone.
 *
 * ⛔ NO REAL SCHOOL, SLUG OR DATE IS NAMED IN THIS FILE, AND THE ONE THAT THIS
 *    RELEASE IS FOR IS NOT NAMED HERE EITHER. §3 of
 *    `tests/test-school-visit-pickup.php` greps THIS FILE for real visit data
 *    and fails on a match — including a match inside a comment, which is
 *    correct and is how this note came to be reworded. The founder-ruled slug
 *    lives in `school-visit-stock-privacy.php`, in one filterable list, and is
 *    covered by its own suite.
 *
 * ⛔ THE RULING IS NOT CARRIED BY THIS FIELD ALONE, AND THAT IS DELIBERATE.
 *    Setting `hide_stock` on a production row is a production DATA write and
 *    therefore Andrew's gate; a build that depended on it would ship inert and
 *    the ruling would silently not be in force. THIS FIELD IS THE FORWARD
 *    PATH — the next visit that needs stock hidden is one
 *    `wp option patch update` away and never a deploy.
 *
 * @return array<string,array{slug:string,school:string,date:string,cutoff:string,time:string,hide_stock:bool}>
 */
function bhp_school_visit_records() {
	$raw = get_option( BHP_SCHOOL_VISIT_OPTION, array() );
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$out = array();
	foreach ( $raw as $slug => $row ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! is_array( $row ) ) {
			continue;
		}
		$school = isset( $row['school'] ) ? trim( wp_strip_all_tags( (string) $row['school'] ) ) : '';
		$date   = isset( $row['date'] ) ? trim( (string) $row['date'] ) : '';
		$cutoff = isset( $row['cutoff'] ) ? trim( (string) $row['cutoff'] ) : '';

		if ( '' === $school || ! bhp_school_visit_is_ymd( $date ) || ! bhp_school_visit_is_ymd( $cutoff ) ) {
			continue;
		}

		$out[ $slug ] = array(
			'slug'       => $slug,
			'school'     => $school,
			'date'       => $date,
			'cutoff'     => $cutoff,
			'time'       => bhp_school_visit_sanitize_time( isset( $row['time'] ) ? $row['time'] : '' ),
			'hide_stock' => bhp_school_visit_flag_is_on( isset( $row['hide_stock'] ) ? $row['hide_stock'] : false ),
		);
	}
	return $out;
}

/**
 * ⭐ 1.8.75 — Read an OPTIONAL boolean registry field tolerantly.
 *
 * ⛔ IT DEFAULTS TO FALSE ON EVERYTHING IT DOES NOT RECOGNISE, and that is the
 *    safe direction for every flag this shape will ever carry: an unreadable
 *    value leaves the visit behaving exactly as it did before the field
 *    existed, rather than silently switching a behaviour on for a real school.
 *
 * ⭐ IT ACCEPTS BOTH SHAPES AN OPERATOR CAN ACTUALLY PRODUCE. `wp option update
 *    --format=json` stores a real JSON `true`; a hand-edited serialised row, or
 *    one round-tripped through a form, arrives as the string `"1"`, `"yes"` or
 *    `"true"`. Accepting only one of those would make the field work on one
 *    entry route and fail silently on the other, which is worse than not having
 *    it. `"0"`, `""`, `"no"`, `"false"` and every unrecognised value are FALSE.
 *
 * @param mixed $value Raw option value.
 * @return bool
 */
function bhp_school_visit_flag_is_on( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) || is_float( $value ) ) {
		return (int) $value === 1;
	}
	if ( ! is_string( $value ) ) {
		return false;
	}

	return in_array( strtolower( trim( $value ) ), array( '1', 'yes', 'true', 'on' ), true );
}

/**
 * Sanitise the optional display time of a visit.
 *
 * ⛔ IT DOES NOT VALIDATE A TIME FORMAT, ON PURPOSE. "8:50 AM", "8:50–9:20 AM"
 *    and "right after lunch" are all things an operator may legitimately want
 *    on the page, and a format check would drop the third and then be "fixed"
 *    by loosening it anyway. What it DOES guarantee is that the value is a
 *    plain, short, single-line, tag-free string — because it is echoed to a
 *    public page.
 *
 * @param mixed $value Raw option value.
 * @return string Empty string when there is nothing usable.
 */
function bhp_school_visit_sanitize_time( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}
	$value = wp_strip_all_tags( (string) $value );
	$value = sanitize_text_field( $value );
	$value = trim( preg_replace( '/\s+/u', ' ', $value ) );

	return function_exists( 'mb_substr' )
		? mb_substr( $value, 0, BHP_SCHOOL_VISIT_TIME_MAXLEN )
		: substr( $value, 0, BHP_SCHOOL_VISIT_TIME_MAXLEN );
}

/**
 * Strict `Y-m-d` test. A real date, not merely a string that looks like one.
 *
 * @param string $value Candidate.
 * @return bool
 */
function bhp_school_visit_is_ymd( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return false;
	}
	$parts = explode( '-', $value );
	return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
}

/**
 * Resolve one slug to a LIVE, NON-EXPIRED visit, or null.
 *
 * ⛔ EVERY FACT IS RE-READ FROM THE OPTION AT CALL TIME. Nothing about a
 *    visit — not the school name, not the date, not the cutoff — is ever
 *    carried inside a session, so an edited or withdrawn visit takes effect
 *    on the very next request rather than at the end of somebody's session.
 *
 * @param string $slug Visit slug.
 * @return array{slug:string,school:string,date:string,cutoff:string,time:string,hide_stock:bool}|null
 */
function bhp_school_visit_resolve( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}
	$records = bhp_school_visit_records();
	if ( ! isset( $records[ $slug ] ) ) {
		return null;
	}
	$record = $records[ $slug ];

	/*
	 * ⭐ 1.8.56 — THE GATE IS THE ONLINE CLOSE, NOT THE STATED CUTOFF.
	 *
	 * ⛔ SUPERSEDED LINE, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
	 *    RE-DERIVED:
	 *
	 *        // The cutoff is INCLUSIVE — the last day an order may be placed.
	 *        if ( bhp_school_visit_today() > $record['cutoff'] ) {
	 *
	 *    That closed ordering at the end of the STATED deadline (`visit - 3`).
	 *    It now closes at 00:00 on `visit - 1`, so a parent has the whole of
	 *    `visit - 2` to sneak in after the deadline they were given. The
	 *    `cutoff` field is untouched, still sanitised, still returned in the
	 *    record, and still what `/author-visits/` prints as "Order by ...".
	 *
	 * ⛔ THIS IS ALSO WHAT CLEARS A STALE BOOKMARK. `bhp_school_visit_active()`
	 *    re-validates through this same call on every request, so a session
	 *    flagged before the close loses the entitlement at the same instant the
	 *    button greys — not at the end of that shopper's session.
	 */
	if ( ! bhp_school_visit_is_open_on( $record['date'], bhp_school_visit_today() ) ) {
		return null;
	}
	return $record;
}

/* =========================================================================
 * THE SESSION FLAG
 * ====================================================================== */

/**
 * ⭐ 1.8.59 — "now", as a UNIX timestamp, through one movable clock.
 *
 * The exact counterpart of `bhp_school_visit_today()` above, and it exists for
 * the exact same reason: a suite has to be able to stand on both sides of the
 * TTL boundary without sleeping for fourteen days and WITHOUT WRITING ANY
 * SESSION OR OPTION.
 *
 * ⛔ THE FILTER CANNOT CHANGE DEFAULT BEHAVIOUR. Nothing in the plugin, the
 *    theme or the admin UI hooks it. With nothing hooked, `apply_filters()`
 *    returns `time()` untouched. A filtered value that is not a positive
 *    integer is DISCARDED rather than trusted, so a broken hook falls back to
 *    the real clock instead of silently expiring or immortalising every flag.
 *
 * @return int
 */
function bhp_school_visit_now() {
	$now = time();

	if ( ! function_exists( 'apply_filters' ) ) {
		return $now;
	}

	/**
	 * Filter the instant the school-visit TTL is evaluated against.
	 *
	 * A TEST SEAM, not a configuration point.
	 *
	 * @since 1.8.59
	 * @param int $now UNIX timestamp.
	 */
	$filtered = apply_filters( 'bhp_school_visit_now', $now );

	return ( is_int( $filtered ) && $filtered > 0 ) ? $filtered : $now;
}

/**
 * The TTL in seconds. Filterable so a suite can shorten it; defaults to the
 * constant and is never read from an option or a setting.
 *
 * ⛔ A filtered value that is not a positive integer is DISCARDED. In
 *    particular a filter returning 0 or a negative number cannot be used to
 *    expire every session at once — that is a footgun, not a feature.
 *
 * @return int
 */
function bhp_school_visit_ttl_seconds() {
	$ttl = (int) BHP_SCHOOL_VISIT_TTL_SECONDS;

	if ( ! function_exists( 'apply_filters' ) ) {
		return $ttl;
	}

	/**
	 * Filter the school-visit flag's hard TTL, in seconds.
	 *
	 * @since 1.8.59
	 * @param int $ttl Seconds.
	 */
	$filtered = apply_filters( 'bhp_school_visit_ttl_seconds', $ttl );

	return ( is_int( $filtered ) && $filtered > 0 ) ? $filtered : $ttl;
}

/**
 * Has a flag stamped at `$set_at` outlived the TTL as of `$now`?
 *
 * ⛔ A PURE FUNCTION ON PURPOSE. No session, no option, no clock of its own, so
 *    the boundary can be asserted at the second on both sides without any
 *    environment at all. Everything stateful is the caller's problem.
 *
 * ⛔ FAILS CLOSED ON AN UNUSABLE STAMP. A non-integer, zero, negative or
 *    future-dated stamp is treated as EXPIRED. A stamp the code cannot reason
 *    about is precisely the case the TTL exists to catch, and the safe answer
 *    is the ordinary storefront.
 *
 * @param mixed $set_at Stored stamp.
 * @param int   $now    Current UNIX timestamp.
 * @return bool
 */
function bhp_school_visit_ttl_expired( $set_at, $now ) {
	if ( ! is_int( $set_at ) && ! ( is_string( $set_at ) && ctype_digit( $set_at ) ) ) {
		return true;
	}
	$set_at = (int) $set_at;
	$now    = (int) $now;

	if ( $set_at <= 0 ) {
		return true;
	}
	// A stamp from the future is a corrupted stamp, not a longer entitlement.
	if ( $set_at > $now ) {
		return true;
	}
	return ( $now - $set_at ) >= bhp_school_visit_ttl_seconds();
}

/**
 * Read this session's TTL stamp, adopting one for a pre-1.8.59 session.
 *
 * ⭐⭐ THE LEGACY-ADOPTION BRANCH IS THE MOST CONSEQUENTIAL LINE IN THIS BUILD
 *     AND IT IS A DELIBERATE CHOICE BETWEEN TWO WRONG-LOOKING OPTIONS.
 *
 *     48 of 95 live staging sessions carried a slug and no stamp when this
 *     shipped, and every real production session will too. Two things could be
 *     done with them:
 *
 *       A. treat "no stamp" as EXPIRED — clears all 48 instantly, which is
 *          tidy, and which would also strip hand-delivery from a parent who is
 *          at that moment on the checkout page inside a wide-open window. That
 *          is the pre-order-in-flight case the brief explicitly protects.
 *       B. treat "no stamp" as "starts now" — the session gets its fourteen
 *          days from the deploy rather than from whenever it really began.
 *
 *     ⭐ B IS CHOSEN. The TTL is a BACKSTOP; the window close is the guard that
 *        actually governs, it is already live since 1.8.56, and it will fire
 *        for all three real visits (08-26, 09-01, 09-02) long before any
 *        adopted stamp reaches fourteen days. So B costs nothing real and A
 *        would cost a parent an order.
 *
 * ⚠ CONSEQUENCE, STATED PLAINLY SO NOBODY IS SURPRISED BY IT: deploying 1.8.59
 *   does NOT by itself clear the 48 stale staging sessions or Andrew's own
 *   Chrome. What clears those is the window close, or `?bhp_visit=clear`.
 *
 * @return int Stamp, or 0 when there is no session or no flag.
 */
function bhp_school_visit_set_at_stamp() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return 0;
	}
	$stamp = WC()->session->get( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY );

	if ( is_int( $stamp ) && $stamp > 0 ) {
		return $stamp;
	}
	if ( is_string( $stamp ) && ctype_digit( $stamp ) && (int) $stamp > 0 ) {
		return (int) $stamp;
	}

	// Pre-1.8.59 session: adopt "now" once, then behave normally forever after.
	$adopted = bhp_school_visit_now();
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, $adopted );
	return $adopted;
}

/**
 * Drop the flag and everything downstream of it, and leave no other trace.
 *
 * ⛔ WHAT IT TOUCHES, EXHAUSTIVELY: two WooCommerce SESSION keys, the session's
 *    chosen-shipping-method SELECTION, and the shipping rate-cache version.
 * ⛔ WHAT IT DOES NOT TOUCH, AND MUST NEVER: the cart, any order, any product,
 *    price, coupon, stock, shipping, tax, pickup or payment SETTING, and the
 *    `bhp_school_visits` registry. Clearing a session is not a data change.
 *
 * ⭐ THE CHOSEN METHOD IS CLEARED FOR THE SAME REASON `capture_intent()` clears
 *    it on the way IN, mirrored. A session that had selected
 *    `pickup_location:*` would otherwise hold a selection that no longer exists
 *    in the rate list, and `wc_get_default_shipping_method_for_package()`
 *    preserves an existing choice where it can. Clearing it lets the ordinary
 *    flat rate be selected cleanly on the next calculation.
 *
 * @return void
 */
function bhp_school_visit_clear_session() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, null );
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, null );

	if ( is_callable( array( WC()->session, 'set' ) ) ) {
		WC()->session->set( 'chosen_shipping_methods', array() );
	}

	bhp_school_pickup_invalidate_rate_cache();
}

/**
 * Turn `?bhp_visit=<slug>` into a session flag.
 *
 * ⛔ IT SETS NOTHING unless the slug resolves to a live, non-expired visit,
 *    so a stale bookmark, a guessed slug or a link used after the ONLINE
 *    CLOSE (`visit - 1`, 1.8.56) is an ordinary page view with an ignored
 *    query string. No notice, no error, no trace — a parent who missed the
 *    window sees the normal shop.
 * ⭐ IT IS NOT SCOPED TO ONE PAGE ON PURPOSE. The pre-visit email may point
 *    at any landing page; the param is the contract, not the destination.
 *   (Same reasoning, and the same shape, as `bhp_typ_capture_auto_coupon_intent()`.)
 */
add_action( 'template_redirect', 'bhp_school_visit_capture_intent', 5 );
function bhp_school_visit_capture_intent() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only intent flag from a public link; it grants a free delivery method, never a discount or a price change.
	if ( empty( $_GET[ BHP_SCHOOL_VISIT_PARAM ] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$slug = sanitize_key( wp_unslash( $_GET[ BHP_SCHOOL_VISIT_PARAM ] ) );

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	/*
	 * ⭐ 1.8.59 — THE EXPLICIT CLEAR PATH, CHECKED BEFORE THE REGISTRY.
	 *
	 * `?bhp_visit=clear` puts this browser back to the ordinary storefront.
	 * It is checked first because `clear` must never reach `resolve()` and be
	 * treated as a slug, and it is a plain session clear: no notice, no
	 * redirect, no cookie deleted, nothing removed from the cart.
	 *
	 * ⛔ NOT LINKED ANYWHERE CUSTOMER-FACING. It is for QA and for Andrew's own
	 *    phone, and it is documented in the build report rather than on a page.
	 * ⭐ IT IS SAFE FOR ANYONE TO HIT. On a session with no flag it does
	 *    nothing at all, and it can only ever REMOVE an entitlement — there is
	 *    no value of this parameter that grants one without a live visit.
	 */
	if ( BHP_SCHOOL_VISIT_CLEAR_TOKEN === $slug ) {
		bhp_school_visit_clear_session();
		return;
	}

	/*
	 * ⛔ AN UNRESOLVABLE SLUG IS STILL A NO-OP, AND THAT IS UNCHANGED FROM
	 *    1.8.56 ON PURPOSE. `commerce-cx` confirmed on 2026-08-19 that
	 *    `?bhp_visit=<bogus>` does not clear an existing flag; making it clear
	 *    one would mean a truncated or mistyped URL silently strips hand
	 *    delivery from a parent who is entitled to it. The clear path above is
	 *    an explicit word, not an accident anyone can have.
	 */
	if ( ! bhp_school_visit_resolve( $slug ) ) {
		return;
	}

	WC()->session->set( BHP_SCHOOL_VISIT_SESSION_KEY, $slug );
	/*
	 * ⭐ 1.8.59 — the TTL is (re-)armed on every arrival through the link, so a
	 *    parent holding the school's email always has a full fresh window and
	 *    the hard TTL can never fire on somebody who is still using the link.
	 */
	WC()->session->set( BHP_SCHOOL_VISIT_SET_AT_SESSION_KEY, bhp_school_visit_now() );
	if ( is_callable( array( WC()->session, 'set_customer_session_cookie' ) ) ) {
		WC()->session->set_customer_session_cookie( true );
	}

	/*
	 * ⭐ 1.8.52 — the previously chosen shipping method is cleared as well as the
	 *    rate cache. A parent who browsed, reached checkout and only THEN clicked
	 *    the school link would otherwise keep `flat_rate:1` selected, and
	 *    `wc_get_default_shipping_method_for_package()` deliberately preserves an
	 *    existing valid choice. Clearing it lets the hand-delivery default below
	 *    actually apply. It clears a SELECTION, never a setting.
	 */
	if ( is_callable( array( WC()->session, 'set' ) ) ) {
		WC()->session->set( 'chosen_shipping_methods', array() );
	}

	// A rate list may already be cached from before the flag existed.
	bhp_school_pickup_invalidate_rate_cache();
}

/**
 * The visit this session is entitled to, re-validated live, or null.
 *
 * Clears the flag when the visit is gone or past its ONLINE CLOSE, so an
 * expired session self-heals instead of being re-checked forever.
 *
 * ⛔ 1.8.56 — THIS IS WHY AN OLD BOOKMARK STOPS FLAGGING AT THE SAME INSTANT THE
 *    BUTTON GREYS. It re-runs `bhp_school_visit_resolve()` on every request, so
 *    the boundary is a property of the calendar, not of when a session started.
 *
 * ⭐⭐ 1.8.59 (`CYCLE165-LD-VISIT-FLAG-EXPIRY`) — TWO GUARDS NOW, IN THIS ORDER,
 *     AND THE ORDER IS LOAD-BEARING.
 *
 *       1. THE HARD TTL, checked FIRST and WITHOUT consulting the registry.
 *          Checking it first is what makes it a real backstop: a registry row
 *          with a valid but wrong far-future date resolves perfectly happily
 *          and would sail through guard 2 forever. Guard 1 never asks the
 *          registry anything, so nothing in the registry can defeat it.
 *       2. THE VISIT'S OWN ONLINE CLOSE, unchanged from 1.8.56, via
 *          `bhp_school_visit_resolve()`. This is the guard that governs every
 *          real parent, and on 2026-08-19 all three live visits were OPEN
 *          (Adams closes 08-27, Dallas Harris 09-02, Liberty 09-03), so this
 *          build changes NOTHING for any real family today.
 *
 * ⭐ EITHER GUARD FIRING CLEARS THE SESSION RATHER THAN MERELY ANSWERING "no",
 *    so an expired session self-heals on one request instead of paying for a
 *    registry read on every request for the rest of its life.
 *
 * ⛔ NEITHER GUARD TOUCHES THE CART, AN ORDER, OR ANY WOOCOMMERCE RECORD. The
 *    worst thing an expiry can do to a shopper mid-journey is put the ordinary
 *    storefront and the ordinary shipping rate back in front of them.
 *
 * @return array{slug:string,school:string,date:string,cutoff:string,time:string,hide_stock:bool}|null
 */
function bhp_school_visit_active() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return null;
	}
	$slug = WC()->session->get( BHP_SCHOOL_VISIT_SESSION_KEY );
	if ( ! $slug ) {
		return null;
	}

	// GUARD 1 — the hard TTL. Deliberately asks the registry nothing.
	if ( bhp_school_visit_ttl_expired( bhp_school_visit_set_at_stamp(), bhp_school_visit_now() ) ) {
		bhp_school_visit_clear_session();
		return null;
	}

	// GUARD 2 — the visit's own online close, re-read live on every request.
	$record = bhp_school_visit_resolve( $slug );
	if ( ! $record ) {
		bhp_school_visit_clear_session();
		return null;
	}
	return $record;
}

/**
 * True when this request carries a WooCommerce session COOKIE.
 *
 * ⛔ Reads the cookie only. It never creates one, never writes one and never
 *    touches the session store. A visitor with no cookie has no session data,
 *    therefore no visit flag, therefore nothing for this file to do.
 *
 * ⭐ MOVED HERE IN 1.8.52 from `school-visit-fields.php`, because the option
 *    filters below need it and they run before that file is required.
 *
 * @return bool
 */
function bhp_school_visit_has_session_cookie() {
	if ( ! defined( 'COOKIEHASH' ) ) {
		return false;
	}
	$name = 'wp_woocommerce_session_' . COOKIEHASH;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- presence test only; the value is never read, parsed or trusted here.
	return ! empty( $_COOKIE[ $name ] );
}

/**
 * The live visit for THIS request, on EVERY request type including the Store
 * API POST where `WC()->session` has not been built yet.
 *
 * ⛔ READ THIS BEFORE CHANGING IT. It is the difference between hand delivery
 *    existing on the checkout page and existing on the request that submits the
 *    order.
 *
 *      · `WC::init()` calls `wc_load_cart()` — which brings the session up —
 *        only when `is_request('frontend')`, and that test EXCLUDES REST.
 *        Verified by reading WooCommerce 10.9.1 `includes/class-woocommerce.php`
 *        on staging; the Store API loads the cart LAZILY instead, from
 *        `StoreApi/Utilities/CartController::get_cart_instance()`.
 *      · So on `POST /wc/store/v1/checkout` — the request that actually places
 *        the order — `WC()->session` can be NULL when this is first called.
 *      · A visitor with NO WooCommerce session cookie cannot possibly hold a
 *        visit flag, so they are returned from immediately, untouched: no
 *        session is created, no cookie is set, no cache is disturbed.
 *
 * ⛔ THE REENTRANCY GUARD IS REQUIRED, NOT DEFENSIVE PADDING. This function is
 *    called from inside `option_*` read filters. `WC()->initialize_session()`
 *    reads options of its own, and any path that came back through one of the
 *    two filtered options would recurse without bound. While the guard is up
 *    the filters return the stored value unchanged, which is the safe answer.
 *
 * @return array{slug:string,school:string,date:string,cutoff:string,time:string,hide_stock:bool}|null
 */
function bhp_school_visit_request_record() {
	static $busy = false;

	if ( $busy ) {
		return null;
	}
	if ( ! function_exists( 'WC' ) ) {
		return null;
	}

	$busy = true;
	try {
		if ( ! WC()->session ) {
			if ( ! bhp_school_visit_has_session_cookie() ) {
				return null; // No cookie → no flag. Nothing is created, nothing is touched.
			}
			if ( is_callable( array( WC(), 'initialize_session' ) ) ) {
				WC()->initialize_session();
			}
			if ( ! WC()->session ) {
				return null;
			}
		}

		return bhp_school_visit_active();
	} catch ( Throwable $e ) {
		// A resolver that throws must never take checkout down with it.
		return null;
	} finally {
		$busy = false;
	}
}

/**
 * Force WooCommerce to recalculate shipping on the next read.
 *
 * The same mechanism WooCommerce itself uses when a zone is saved, and the same
 * one `ShippingController::flush_cache()` uses when the pickup settings are
 * saved. Bumping the transient version is the only thing that reliably clears a
 * rate list the session already holds — see `.claude/rules/woocommerce.md`.
 */
function bhp_school_pickup_invalidate_rate_cache() {
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}

/* =========================================================================
 * ⭐⭐ TURNING ON WOOCOMMERCE'S OWN LOCAL PICKUP, FOR ONE REQUEST
 * ====================================================================== */

/**
 * The customer-facing label of the delivery option.
 *
 * ⛔ APPROVED COPY, CARRIED FORWARD BYTE-IDENTICAL FROM 1.8.49. A label a
 *    parent reads while paying is not something a mechanism change gets to
 *    reword on its own initiative. 1.8.51 recorded the same rule when it
 *    deliberately did not add the visit time to it.
 *
 * @param array $record Visit record.
 * @return string
 */
function bhp_school_pickup_label( array $record ) {
	$date = $record['date'];
	$ts   = strtotime( $date . ' 12:00:00' );
	if ( $ts ) {
		$date = wp_date( 'F j', $ts );
	}
	return sprintf(
		/* translators: 1: school name, 2: visit date */
		__( 'Author hand-delivery at the %1$s visit (%2$s)', 'brave-hearts' ),
		$record['school'],
		$date
	);
}

/**
 * The sentence shown beneath the location in the pickup step.
 *
 * ⭐ WooCommerce renders a location's `details` under its name. This is the one
 *    place the mechanism gives us to answer the question the founder says the
 *    old screen provoked — "is this getting shipped?" — so it answers it in
 *    words, without a promise the order cannot keep.
 *
 * ⛔ NO EM DASHES. The sitewide purge is a standing constraint.
 *
 * @param array $record Visit record.
 * @return string
 */
function bhp_school_pickup_location_details( array $record ) {
	$date = $record['date'];
	$ts   = strtotime( $date . ' 12:00:00' );
	if ( $ts ) {
		$date = wp_date( 'l, F j', $ts );
	}

	// 2026-08-17 Andrew, verbatim order: the checkout must highlight that this
	// is for personalized signed author copies. His words, fitted to voice rules.
	//
	// ⭐ 1.8.57 (2026-08-18): "books" becomes "paperbacks", one word, because a
	//    flagged order can now only BE paperbacks (Andrew, 2026-08-18: "based on
	//    my inventory I can only do paperbacks") and this is the last screen
	//    before payment. A parent who assumed hardcover finds out here, not at
	//    the read aloud. ⛔ NO "we". ⛔ NO EM DASH. Nothing else in the sentence
	//    moves, and the SUPERSEDED wording was "Andrew brings the signed books to
	//    %1$s on %2$s." — preserved here so the change is visible rather than
	//    re-derived.
	$details = __( 'This is for personalized signed author copies! Fill in your child&#8217;s first name below and I will hand them their book at the read aloud. ', 'brave-hearts' ) . sprintf(
		/* translators: 1: school name, 2: visit date */
		__( 'Andrew brings the signed paperbacks to %1$s on %2$s. Nothing is posted to your home, and there is no shipping charge.', 'brave-hearts' ),
		$record['school'],
		$date
	);

	if ( '' !== $record['time'] ) {
		$details .= ' ' . sprintf(
			/* translators: %s: the visit time, an operator-supplied display string */
			__( 'Visit time: %s.', 'brave-hearts' ),
			$record['time']
		);
	}

	return $details;
}

/**
 * The single pickup location this build injects, shaped exactly as
 * WooCommerce's own settings screen would store one.
 *
 * ⛔ THE ADDRESS IS DELIBERATELY EMPTY, INCLUDING THE COUNTRY, AND THAT IS A
 *    TAX DECISION RATHER THAN A CONVENIENCE. `ShippingController::filter_
 *    taxable_address()` re-bases the customer's taxable address on the pickup
 *    location the moment that location has a non-empty `country`. The school's
 *    real street address is not in the registry and is not needed to hand a
 *    book to a child, so supplying a partial one would move the tax basis for
 *    no benefit. With no country the swap cannot fire at all.
 *
 * ⭐ WHAT THE PARENT SEES INSTEAD OF AN ADDRESS: the location NAME already
 *    carries the school and the date, and `details` carries the sentence.
 *    `PickupLocation::has_valid_pickup_location()` returns false for this
 *    address, so WooCommerce sets the `pickup_address` meta to an empty string
 *    and renders no address line. Verified by reading that method, not assumed.
 *
 * @param array $record Visit record.
 * @return array
 */
function bhp_school_pickup_location_row( array $record ) {
	return array(
		'name'    => bhp_school_pickup_label( $record ),
		'address' => array(
			'address_1' => '',
			'city'      => '',
			'state'     => '',
			'postcode'  => '',
			'country'   => '',
		),
		'details' => bhp_school_pickup_location_details( $record ),
		'enabled' => true,
	);
}

/**
 * Make WooCommerce's local pickup method ENABLED, for a flagged request only.
 *
 * ⛔ READ FILTER. NOTHING IS PERSISTED. The stored option is returned by
 *    identity for every other request, which is what makes an ordinary
 *    checkout byte-identical.
 *
 * ⭐ `title` is the label on the Ship / … toggle itself. It is intentionally
 *    short, because it is a two-word button, and intentionally NOT the word
 *    "Pickup" — nobody is picking anything up.
 *
 * @param mixed $value Stored option value.
 * @return mixed
 */
function bhp_school_pickup_filter_location_settings( $value ) {
	$record = bhp_school_visit_request_record();
	if ( ! $record ) {
		return $value; // ZERO CHANGE for a normal visitor.
	}

	$settings = is_array( $value ) ? $value : array();

	$settings['enabled'] = 'yes';
	$settings['title']   = __( 'Hand delivery', 'brave-hearts' );
	// Free, and therefore untaxed. An empty cost is WooCommerce's own "free".
	$settings['cost']       = '';
	$settings['tax_status'] = 'none';

	return $settings;
}
add_filter( 'option_' . BHP_SCHOOL_PICKUP_SETTINGS_OPTION, 'bhp_school_pickup_filter_location_settings', 10, 1 );
add_filter( 'default_option_' . BHP_SCHOOL_PICKUP_SETTINGS_OPTION, 'bhp_school_pickup_filter_location_settings', 10, 1 );

/**
 * Add exactly one pickup location — this session's visit — for a flagged
 * request only.
 *
 * ⛔ IT APPENDS. Any location an operator has genuinely configured is returned
 *    first and untouched. That is also why the injected row's INDEX is
 *    discovered by `bhp_school_pickup_rate_id()` rather than assumed to be 0:
 *    WooCommerce builds the rate id as `pickup_location:<index>`.
 *
 * @param mixed $value Stored option value.
 * @return mixed
 */
function bhp_school_pickup_filter_locations( $value ) {
	$record = bhp_school_visit_request_record();
	if ( ! $record ) {
		return $value; // ZERO CHANGE for a normal visitor.
	}

	$locations   = is_array( $value ) ? array_values( $value ) : array();
	$locations[] = bhp_school_pickup_location_row( $record );

	return $locations;
}
add_filter( 'option_' . BHP_SCHOOL_PICKUP_LOCATIONS_OPTION, 'bhp_school_pickup_filter_locations', 10, 1 );
add_filter( 'default_option_' . BHP_SCHOOL_PICKUP_LOCATIONS_OPTION, 'bhp_school_pickup_filter_locations', 10, 1 );

/**
 * ⛔ DO NOT MOVE THE TAX BASIS. Belt to the empty-country braces above.
 *
 * WooCommerce asks this before re-basing an order's tax on the pickup location.
 * For a flagged session the answer is no: the parent is taxed on their own
 * address exactly as they were before this feature existed.
 *
 * ⭐ It answers only for a flagged session, so a store that one day has real
 *    pickup locations keeps WooCommerce's default behaviour for them.
 *
 * @param bool $apply WooCommerce's default (true).
 * @return bool
 */
function bhp_school_pickup_keep_customer_tax_basis( $apply ) {
	return bhp_school_visit_request_record() ? false : $apply;
}
add_filter( 'woocommerce_apply_base_tax_for_local_pickup', 'bhp_school_pickup_keep_customer_tax_basis', 10, 1 );

/**
 * The rate id WooCommerce will build for this build's injected location.
 *
 * @return string Empty string when there is no flagged visit.
 */
function bhp_school_pickup_rate_id() {
	$record = bhp_school_visit_request_record();
	if ( ! $record ) {
		return '';
	}
	$locations = get_option( BHP_SCHOOL_PICKUP_LOCATIONS_OPTION, array() );
	if ( ! is_array( $locations ) || empty( $locations ) ) {
		return '';
	}
	$keys  = array_keys( $locations );
	$index = end( $keys );

	return BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID . ':' . $index;
}

/**
 * Put the visit slug into the shipping package so the package HASH changes.
 *
 * ⛔ THIS IS LOAD-BEARING, NOT DECORATION, AND IT SURVIVED THE 1.8.52 REWRITE
 *    UNCHANGED. `woocommerce_package_rates` and the whole method-calculation
 *    pass only run on a cache MISS. Without a package that differs between "no
 *    visit flag" and "visit flag", a visitor who had already seen checkout
 *    would never be offered hand delivery, and — worse — a visitor whose flag
 *    EXPIRED would keep being offered it.
 */
add_filter( 'woocommerce_cart_shipping_packages', 'bhp_school_pickup_tag_package', 20 );
function bhp_school_pickup_tag_package( $packages ) {
	if ( ! is_array( $packages ) ) {
		return $packages;
	}
	$record = bhp_school_visit_active();
	if ( ! $record ) {
		return $packages; // Byte-identical package for every normal visitor.
	}
	foreach ( $packages as $key => $package ) {
		$packages[ $key ]['bhp_school_visit'] = $record['slug'];
	}
	return $packages;
}

/**
 * Rename WooCommerce's pickup rate to the approved label and attach the hidden
 * visit meta the order will need after the session is gone.
 *
 * ⛔ IT ADDS NO RATE AND REMOVES NO RATE. 1.8.52 deleted the rate injector
 *    entirely; this function only decorates a rate WooCommerce itself built.
 *    A flagged parent who would rather have the books posted still sees, and
 *    can still choose, ordinary shipping at its ordinary tiered price. That is
 *    asserted in the test suite.
 *
 * ⭐ WHY THE LABEL IS OVERRIDDEN AT ALL: WooCommerce composes a pickup rate's
 *    label as `<toggle title> (<location name>)`, which here would read
 *    "Hand delivery (Author hand-delivery at the X visit (September 3))". The
 *    label is what lands on the order as `method_title` and what the order
 *    summary prints, so it is set to the approved sentence on its own.
 *
 * ⛔ PRIORITY 25 IS DELIBERATE AND UNCHANGED: after the theme's Bookvault-rate
 *    strip (10) and after `bhp_bundle_override_shipping_cost()` (20), which
 *    only ever edits `flat_rate` and therefore cannot touch this rate whichever
 *    order they ran in.
 *
 * @param array $rates   Rates for this package.
 * @param array $package The package.
 * @return array
 */
add_filter( 'woocommerce_package_rates', 'bhp_school_pickup_decorate_rate', 25, 2 );
function bhp_school_pickup_decorate_rate( $rates, $package = array() ) {
	if ( ! is_array( $rates ) ) {
		return $rates;
	}
	$record = bhp_school_visit_request_record();
	if ( ! $record ) {
		return $rates; // ZERO CHANGE for a normal visitor.
	}

	foreach ( $rates as $rate ) {
		if ( ! is_object( $rate ) || ! method_exists( $rate, 'get_method_id' ) ) {
			continue;
		}
		if ( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID !== $rate->get_method_id() ) {
			continue;
		}

		if ( method_exists( $rate, 'set_label' ) ) {
			$rate->set_label( bhp_school_pickup_label( $record ) );
		}
		$rate->add_meta_data( BHP_SCHOOL_PICKUP_ITEM_META_SLUG, $record['slug'] );
		$rate->add_meta_data( BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL, $record['school'] );
		$rate->add_meta_data( BHP_SCHOOL_PICKUP_ITEM_META_DATE, $record['date'] );
	}

	return $rates;
}

/**
 * Make hand delivery the DEFAULT selection for a flagged session.
 *
 * ⭐ THIS IS THE SINGLE MOST IMPORTANT LINE OF UX IN THE BUILD, and it is why
 *    the founder's complaint is actually answered rather than merely made
 *    answerable. WooCommerce's `wc_get_default_shipping_method_for_package()`
 *    deliberately defaults block checkout to the first NON-pickup rate, with
 *    the comment "when you enter block checkout, shipping is chosen rather than
 *    pickup". The Blocks store then derives `prefersCollection` from whichever
 *    rate is selected. So without this filter a visit parent would land on the
 *    SHIPPING screen and have to find a toggle to escape it — which is exactly
 *    the screen that was rejected.
 *
 * ⛔ IT NEVER OVERRIDES A CHOICE THE PARENT MADE. If the session already holds
 *    a chosen method that still exists in this package, the customer's own
 *    selection is returned untouched. A flagged parent who deliberately picks
 *    "Ship" gets shipping, and keeps it.
 *
 * @param string $default       WooCommerce's computed default rate key.
 * @param array  $rates         Rates for this package.
 * @param string $chosen_method The method the session already holds, if any.
 * @return string
 */
add_filter( 'woocommerce_shipping_chosen_method', 'bhp_school_pickup_default_chosen_method', 10, 3 );
function bhp_school_pickup_default_chosen_method( $default, $rates = array(), $chosen_method = '' ) {
	if ( ! is_array( $rates ) || empty( $rates ) ) {
		return $default;
	}
	// The parent has already chosen something that is still on offer. Respect it.
	if ( is_string( $chosen_method ) && '' !== $chosen_method && isset( $rates[ $chosen_method ] ) ) {
		return $default;
	}
	if ( ! bhp_school_visit_request_record() ) {
		return $default; // ZERO CHANGE for a normal visitor.
	}

	foreach ( $rates as $key => $rate ) {
		if ( is_object( $rate ) && method_exists( $rate, 'get_method_id' )
			&& BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID === $rate->get_method_id() ) {
			return (string) $key;
		}
	}

	return $default;
}

/* =========================================================================
 * ORDER MARKING — ANDREW'S PACKING LIST
 * ====================================================================== */

/**
 * Every shipping-method id that means "this order is hand-delivered".
 *
 * ⭐ TWO ENTRIES, AND THE SECOND IS HISTORY RATHER THAN A SECOND MECHANISM.
 *    `pickup_location` is WooCommerce's own local pickup, which is what 1.8.52
 *    creates. `bhp_school_pickup` is the retired 1.8.49–1.8.51 rate id, kept so
 *    orders placed before this release are still recognised forever. Nothing
 *    can create one any more.
 *
 * ⚠ SEE THE FILE HEADER for the deliberate consequence of matching
 *   `pickup_location` broadly, and the one-line instruction for narrowing it if
 *   real store pickup is ever introduced.
 *
 * @return string[]
 */
function bhp_school_pickup_method_ids() {
	return apply_filters(
		'bhp_school_pickup_method_ids',
		array( BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID, BHP_SCHOOL_PICKUP_METHOD_ID )
	);
}

/**
 * Is this order a hand-delivery order?
 *
 * ⛔ THE SHIPPING LINE IS CHECKED FIRST AND THE META SECOND, ON PURPOSE.
 *    The shipping line is written by WooCommerce itself from the chosen rate
 *    and exists on every order that chose pickup. The meta is written by this
 *    plugin. If the meta write ever failed, a meta-only test would let the
 *    order through to the printer — which is the one outcome this whole file
 *    exists to prevent. The authoritative signal is checked first and the
 *    convenience signal second.
 *
 * @param WC_Order|int|mixed $order Order or order id.
 * @return bool
 */
function bhp_school_pickup_order_is_pickup( $order ) {
	if ( is_numeric( $order ) ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $order ) : null;
	}
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$pickup_ids = bhp_school_pickup_method_ids();

	foreach ( $order->get_shipping_methods() as $item ) {
		if ( in_array( $item->get_method_id(), $pickup_ids, true ) ) {
			return true;
		}
	}

	return 'yes' === $order->get_meta( BHP_SCHOOL_PICKUP_META_FLAG );
}

/**
 * Recover the visit from the order's own shipping item, when the session is
 * gone.
 *
 * ⭐ WooCommerce copies a RATE's meta onto the order's shipping item on BOTH
 *    checkout paths — `WC_Checkout::create_order_shipping_lines()`, which
 *    `StoreApi\Utilities\OrderController::update_order_from_cart()` also calls.
 *    Verified by reading WooCommerce 10.9.1 on staging: the copy loop is
 *    `foreach ( $shipping_rate->get_meta_data() as $key => $value )
 *    { $item->add_meta_data( $key, $value, true ); }` and it copies
 *    underscore-prefixed keys too.
 *
 * ⛔ IT READS THE 1.8.49 KEYS AS A FALLBACK TO THE FALLBACK. An order placed
 *    under the old mechanism carries the visible "School"/"Visit date" rate
 *    meta instead. Dropping that read would silently degrade the packing-list
 *    note for every order placed before this release.
 *
 * @param WC_Order $order Order.
 * @return array{slug:string,school:string,date:string}
 */
function bhp_school_pickup_visit_from_order_item( WC_Order $order ) {
	$out        = array( 'slug' => '', 'school' => '', 'date' => '' );
	$pickup_ids = bhp_school_pickup_method_ids();

	foreach ( $order->get_shipping_methods() as $item ) {
		if ( ! in_array( $item->get_method_id(), $pickup_ids, true ) ) {
			continue;
		}

		$slug   = (string) $item->get_meta( BHP_SCHOOL_PICKUP_ITEM_META_SLUG );
		$school = (string) $item->get_meta( BHP_SCHOOL_PICKUP_ITEM_META_SCHOOL );
		$date   = (string) $item->get_meta( BHP_SCHOOL_PICKUP_ITEM_META_DATE );

		// 1.8.49–1.8.51 orders: the visible rate meta.
		if ( '' === $school ) {
			$school = (string) $item->get_meta( __( 'School', 'brave-hearts' ) );
			$date   = (string) $item->get_meta( __( 'Visit date', 'brave-hearts' ) );
		}

		if ( '' !== $school || '' !== $slug ) {
			$out['slug']   = $slug;
			$out['school'] = $school;
			$out['date']   = $date;
			break;
		}
	}

	return $out;
}

/**
 * Write the packing-list meta and the visible order note.
 *
 * Registered on BOTH checkout paths because this store runs WooCommerce
 * Blocks and the classic form is still reachable:
 *   · `woocommerce_store_api_checkout_order_processed` — Blocks / Store API.
 *   · `woocommerce_checkout_order_processed` — classic.
 *
 * @param WC_Order|int $order Order or id (classic passes the id first).
 */
function bhp_school_pickup_mark_order( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( (int) $order );
	}
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return;
	}
	if ( 'yes' === $order->get_meta( BHP_SCHOOL_PICKUP_META_FLAG ) ) {
		return; // Already marked; both hooks can fire in one request.
	}

	// Resolve the visit from the session where possible, and fall back to the
	// shipping item's own stored meta so an order is still marked if the
	// session died.
	$record = bhp_school_visit_request_record();
	$school = $record ? $record['school'] : '';
	$date   = $record ? $record['date'] : '';
	$slug   = $record ? $record['slug'] : '';

	if ( '' === $school ) {
		$from_item = bhp_school_pickup_visit_from_order_item( $order );
		$school    = $from_item['school'];
		$date      = $from_item['date'];
		$slug      = '' !== $from_item['slug'] ? $from_item['slug'] : $slug;
	}

	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_FLAG, 'yes' );
	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_SLUG, $slug );
	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_SCHOOL, $school );
	$order->update_meta_data( BHP_SCHOOL_PICKUP_META_DATE, $date );

	/*
	 * The note is Andrew's packing list and it is deliberately blunt. It says
	 * "do not ship" in words, because the person reading it at 6am is reading
	 * a list of orders, not this file.
	 *
	 * ⛔ TWO NOTES, NOT ONE WITH BLANKS IN IT. If the school and date could not
	 *    be resolved, saying "at a school visit on the visit date" is worse
	 *    than useless — it reads like a real answer. The degraded note says the
	 *    detail is MISSING and where to look for it, which is a thing a human
	 *    can act on. (This path was reached in QA with a probe order that had
	 *    neither a session nor rate meta, and the first wording produced
	 *    exactly the pleasant-sounding non-answer described here.)
	 *
	 * ⭐ 1.8.50 (`CYCLE162-LD-PICKUP-FIELDS`) — THE CHILD'S NAME JOINS THE NOTE.
	 *    It is a SEPARATE CLAUSE, NOT A SUBSTITUTION: the note says what it said
	 *    before, byte for byte, and gains a sentence. An order with no child
	 *    name says so IN WORDS rather than printing "SIGN TO:" followed by
	 *    nothing.
	 *
	 * ⛔ THE `function_exists()` GUARD IS REQUIRED and is not defensive padding:
	 *    `school-visit-fields.php` is required AFTER this file, and this
	 *    function also runs on orders created before that file existed.
	 */
	$child        = function_exists( 'bhp_school_visit_child_name' ) ? bhp_school_visit_child_name( $order ) : '';
	$child_clause = '' !== $child
		? sprintf(
			/* translators: %s: the child's first name */
			__( ' SIGN TO: %s.', 'brave-hearts' ),
			$child
		)
		: __( ' ⚠ No child name was captured on this order — ask before signing.', 'brave-hearts' );

	if ( '' !== $school && '' !== $date ) {
		$order->add_order_note(
			sprintf(
				/* translators: 1: school name, 2: visit date, 3: visit slug, 4: the signing clause */
				__( 'HAND DELIVERY — DO NOT SHIP. Author hand-delivery at %1$s on %2$s (visit: %3$s).%4$s This order is excluded from the Bookvault print/fulfilment push.', 'brave-hearts' ),
				$school,
				$date,
				'' !== $slug ? $slug : __( 'slug unresolved', 'brave-hearts' ),
				$child_clause
			)
		);
	} else {
		$order->add_order_note(
			sprintf(
				/* translators: %s: the signing clause */
				__( 'HAND DELIVERY — DO NOT SHIP. ⚠ THE SCHOOL AND VISIT DATE COULD NOT BE RESOLVED for this order — read the shipping method on the order itself to find out which visit it belongs to.%s This order is excluded from the Bookvault print/fulfilment push.', 'brave-hearts' ),
				$child_clause
			)
		);
	}

	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_school_pickup_mark_order', 5 );
add_action( 'woocommerce_checkout_order_processed', 'bhp_school_pickup_mark_order', 5 );

/* =========================================================================
 * ⛔⛔ THE DUPLICATE-PRINT PROTECTION
 * ====================================================================== */

/**
 * Hosts whose webhooks carry an order to the print partner.
 *
 * Matched on the DELIVERY URL rather than the webhook's NAME or ID, because
 * a name is editable in wp-admin and an id changes when a webhook is deleted
 * and re-created — both of which have already happened on this store (the
 * live table holds two generations of "BV Order Creation", ids 4 and 6).
 * A host is what actually determines where the payload lands.
 *
 * @return string[] Lowercase host fragments.
 */
function bhp_school_pickup_fulfilment_hosts() {
	return apply_filters(
		'bhp_school_pickup_fulfilment_hosts',
		array( 'bookvault.app', 'bookvault.com' )
	);
}

/**
 * True if this webhook posts to the print partner.
 *
 * @param WC_Webhook|mixed $webhook Webhook.
 * @return bool
 */
function bhp_school_pickup_is_fulfilment_webhook( $webhook ) {
	if ( ! is_object( $webhook ) || ! method_exists( $webhook, 'get_delivery_url' ) ) {
		return false;
	}
	$url = strtolower( (string) $webhook->get_delivery_url() );
	if ( '' === $url ) {
		return false;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$host = is_string( $host ) ? $host : $url; // Unparseable → test the whole string.

	foreach ( bhp_school_pickup_fulfilment_hosts() as $needle ) {
		if ( '' !== $needle && false !== strpos( $host, $needle ) ) {
			return true;
		}
	}
	return false;
}

/**
 * ⛔⛔ NEVER SEND A HAND-DELIVERY ORDER TO THE PRINT PARTNER.
 *
 * This is the single most safety-critical function in the build. If it stops
 * working, Andrew pays to print and post books he is also carrying to the
 * school by hand, and the parent receives two copies.
 *
 * It is intentionally the narrowest possible intervention:
 *   · It only ever returns FALSE, never true — it can suppress a delivery,
 *     and can never cause one that WooCommerce had already declined.
 *   · It only acts on webhooks bound for the print partner. Every other
 *     webhook on this store (Rutter's three) is returned untouched.
 *   · It only acts on ORDER resources. A product or coupon webhook that
 *     happened to point at the same host is not this function's business.
 *
 * @param bool             $should_deliver WooCommerce's decision so far.
 * @param WC_Webhook|mixed $webhook        The webhook.
 * @param mixed            $arg            First hook arg — the order id for order topics.
 * @return bool
 */
add_filter( 'woocommerce_webhook_should_deliver', 'bhp_school_pickup_block_bookvault_webhook', 10, 3 );
function bhp_school_pickup_block_bookvault_webhook( $should_deliver, $webhook = null, $arg = null ) {
	if ( ! $should_deliver ) {
		return $should_deliver; // Already declined. Nothing to do.
	}
	if ( ! bhp_school_pickup_is_fulfilment_webhook( $webhook ) ) {
		return $should_deliver;
	}
	if ( ! is_object( $webhook ) || ! method_exists( $webhook, 'get_resource' ) || 'order' !== $webhook->get_resource() ) {
		return $should_deliver;
	}
	if ( ! is_numeric( $arg ) ) {
		return $should_deliver;
	}
	if ( ! bhp_school_pickup_order_is_pickup( (int) $arg ) ) {
		return $should_deliver;
	}

	/*
	 * A skip is recorded ONCE per order, as a private order note, so that the
	 * protection having fired is visible on the order itself rather than only
	 * in a log nobody reads. It is not repeated on every subsequent
	 * `order.updated` event — a status change would otherwise add a note each
	 * time and bury the packing-list note.
	 */
	$order = wc_get_order( (int) $arg );
	if ( $order instanceof WC_Order && 'yes' !== $order->get_meta( '_bhp_school_pickup_bv_skipped' ) ) {
		$order->update_meta_data( '_bhp_school_pickup_bv_skipped', 'yes' );
		$order->add_order_note( __( 'Bookvault fulfilment webhook SKIPPED — this is a hand-delivery order.', 'brave-hearts' ) );
		$order->save();
	}

	return false;
}

/**
 * Second layer: hide the Bookvault plugin's manual "resend" action on a
 * pickup order, so the one remaining human route to the printer is closed.
 *
 * ⚠ HONEST LIMIT, STATED RATHER THAN IMPLIED: the Bookvault plugin also
 *   registers an orders-LIST BULK action (`bvlt_bulk_resend_orders`) which
 *   iterates raw post ids and consults no per-order state. There is no hook
 *   between that loop and `bvlt_resendOrder()`, so it cannot be intercepted
 *   without editing a third-party plugin, which this build does not do.
 *   Selecting a hand-delivery order in that bulk action would still push it.
 *
 * @param array    $actions Order actions.
 * @param WC_Order $order   Order.
 * @return array
 */
add_filter( 'woocommerce_order_actions', 'bhp_school_pickup_remove_resend_action', 20, 2 );
function bhp_school_pickup_remove_resend_action( $actions, $order = null ) {
	if ( ! is_array( $actions ) || ! $order instanceof WC_Order ) {
		return $actions;
	}
	if ( ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return $actions;
	}
	unset( $actions['blt_resend_order'] );
	return $actions;
}

/* =========================================================================
 * ⭐ 1.8.52 — KILLING THE SHIPPING LANGUAGE IN THE VISIT CONTEXT
 * ====================================================================== */

/**
 * The bullet that replaces "FREE Shipping on the complete collection or 3 or more books purchased" for a
 * parent who arrived from a school's pre-visit link.
 *
 * ⛔ IT IS A SWAP, NEVER A DELETION. A parent still needs to know delivery
 *    costs nothing; what they must not be told is that anything is being
 *    posted. The claim itself is unchanged in substance and is still gated on
 *    the same live predicate at every call site.
 *
 * ⛔ NO "WE". Founder-plain, per the standing copy convention.
 * ⛔ NO EM DASH.
 *
 * @return string
 */
function bhp_school_visit_delivery_bullet() {
	return __( 'FREE author hand-delivery at your school visit', 'brave-hearts' );
}

/**
 * True when the CURRENT request should use hand-delivery framing instead of
 * shipping framing.
 *
 * ⭐ ONE PREDICATE, CALLED FROM EVERY SURFACE, so the collection page, the cart
 *    drawer, the checkout cross-sell and the theme's bullet helper can never
 *    disagree about which story this visitor is being told.
 *
 * @return bool
 */
function bhp_school_visit_use_delivery_framing() {
	return (bool) bhp_school_visit_request_record();
}

/**
 * Swap the cart/drawer free-shipping copy for hand-delivery copy.
 *
 * ⭐ THROUGH THE PLUGIN'S OWN EXISTING FILTER, `bhp_bundle_freeship_copy`,
 *    rather than by editing `bundle-data.php`. The strings for a normal visitor
 *    are returned by identity and are byte-identical to 1.8.51.
 *
 * ⛔ HYPHENS, NEVER EM DASHES, in `cta_clause` — it is appended where
 *    " - Save $X.XX" goes and must keep that shape (B4).
 *
 * @param array $copy Existing copy set.
 * @return array
 */
function bhp_school_visit_freeship_copy( $copy ) {
	if ( ! is_array( $copy ) || ! bhp_school_visit_use_delivery_framing() ) {
		return $copy;
	}

	$copy['nudge']      = __( 'Add the final adventure and Andrew brings the whole set to the school visit.', 'brave-hearts' );
	$copy['earned']     = __( 'Andrew hand-delivers your books at the school visit.', 'brave-hearts' );
	$copy['cta_clause'] = __( ' - Hand Delivered', 'brave-hearts' );

	return $copy;
}
add_filter( 'bhp_bundle_freeship_copy', 'bhp_school_visit_freeship_copy', 10, 1 );

/**
 * ⭐⭐ THE WORDS THAT REPLACE "Shipping" ON A TOTALS ROW. ONE AUTHOR, FOUR
 *     SURFACES, AND THAT IS THE ENTIRE REASON THIS FUNCTION EXISTS.
 *
 * 1.8.52 fixed the ORDER-RECEIVED page and every order email by hardcoding
 * `__( 'Hand delivery:', 'brave-hearts' )` inside the filter below. 1.8.55 has
 * to say the same thing on the CART page and inside the cart DRAWER, and three
 * copies of a customer-facing phrase is how two of them end up disagreeing.
 * So the phrase moved here and the 1.8.52 filter now reads it.
 *
 * ⛔ THE ORDER FILTER'S OUTPUT IS BYTE-IDENTICAL TO 1.8.52. That filter appends
 *    the colon itself, because `WC_Order::get_order_item_totals()` labels carry
 *    one and the cart and drawer rows do not. The test suite asserts the exact
 *    string `Hand delivery:` on the order path and `Hand delivery` on the cart
 *    path, so a future edit here cannot silently move either.
 *
 * ⛔ NO EM DASH. NO "WE". Founder-plain, per the standing copy conventions.
 *
 * @return string
 */
function bhp_school_pickup_totals_label() {
	return __( 'Hand delivery', 'brave-hearts' );
}

/**
 * Rename the order-totals SHIPPING row on a hand-delivery order.
 *
 * ⭐ FOUND BY READING A RENDERED EMAIL, NOT BY READING CODE. The full order
 *    walk produced a confirmation email whose totals table read
 *    "Shipping: Author hand-delivery at the … visit", which is the last place
 *    in the whole flow the word survived. The checkout screen itself already
 *    says "Pickup" — WooCommerce's own order-summary block swaps that label
 *    when `prefersCollection` is true — but `WC_Order::get_order_item_totals()`
 *    hardcodes "Shipping:" and it is what the ORDER-RECEIVED page and EVERY
 *    order email render.
 *
 * ⛔ IT CHANGES A LABEL AND NOTHING ELSE. The row's VALUE is WooCommerce's own,
 *    including the "Collection from …" sentence its
 *    `ShippingController::show_local_pickup_details()` builds from the pickup
 *    location. No amount, no total and no other row is touched, and an ordinary
 *    order is returned by identity.
 *
 * @param array         $rows  Total rows.
 * @param WC_Order|null $order Order.
 * @return array
 */
add_filter( 'woocommerce_get_order_item_totals', 'bhp_school_pickup_order_totals_label', 20, 2 );
function bhp_school_pickup_order_totals_label( $rows, $order = null ) {
	if ( ! is_array( $rows ) || ! isset( $rows['shipping']['label'] ) ) {
		return $rows;
	}
	if ( ! $order instanceof WC_Order || ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return $rows; // ZERO CHANGE for every ordinary order.
	}
	// The colon belongs to the ORDER surface only. The words come from one place.
	$rows['shipping']['label'] = bhp_school_pickup_totals_label() . ':';
	return $rows;
}

/* =========================================================================
 * ⭐⭐ 1.8.55 — THE CART PAGE, WHICH 1.8.52 MISSED
 *
 * FOUNDER-CAUGHT, ON PRODUCTION, ON A PHONE, 2026-08-18. Andrew Signore,
 * verbatim, RELAYED through the Chief of Staff and NOT witnessed here:
 *
 *     "I checked the QA - the cart page still says shipping when moving to
 *      the checkout page - but I do like the option for hand delivery /
 *      shipping - very good."
 *
 * ⛔ THE VALUE WAS ALREADY RIGHT AND STILL IS. A flagged cart already read
 *    `FREE` with `Author hand-delivery at the Adams Elementary visit
 *    (August 28)` underneath it. The one wrong word was the LABEL, and it is
 *    the same defect class the founder rejected the whole build over on
 *    2026-08-17 ("They will think its getting shipped") — one screen earlier
 *    than the screen that was fixed.
 *
 * ⛔⛔ WHY A `gettext` FILTER WOULD HAVE BEEN A FAKE FIX, AND HOW THIS WAS
 *     ESTABLISHED — BY READING THE SHIPPED BUNDLE ON STAGING, NOT BY GUESSING.
 *     `wp-content/plugins/woocommerce/assets/client/blocks/
 *     wc-cart-checkout-base-frontend.js` (WooCommerce 10.9.1) renders that row
 *     from React:
 *
 *         const C = ({ label = __( "Shipping", "woocommerce" ), ... }) => {
 *             ...
 *             const g = p.length > 1 || m.length > 1;      // >1 rate on offer
 *             const _ = 0 === h.length || g ? label : h[0];
 *             return <TotalsItem label={_} value={...} description={...} />
 *         }
 *
 *     `m` is every rate NAME in the package. A flagged cart deliberately
 *     carries TWO rates — `pickup_location:N` and `flat_rate:1`, because a
 *     flagged parent may still choose to have the books posted — so `g` is
 *     true and the component falls back to the generic word. There is no PHP
 *     hook anywhere in that path: the string is a JavaScript `__()` call
 *     evaluated at render, in the `woocommerce` text domain. **A PHP `gettext`
 *     filter cannot see it, which is why one was not written.**
 *
 * ⭐ WHAT IS DONE INSTEAD: the ordinary, documented WordPress way to change a
 *    translated JavaScript string — `wp.i18n.setLocaleData()`, merged into the
 *    `woocommerce` domain BEFORE the Blocks bundle executes. `@wordpress/i18n`
 *    merges rather than replaces, so no other WooCommerce string is disturbed.
 *
 * ⛔ IT IS GATED THREE TIMES OVER, AND THE THIRD GATE IS THE ONE THAT MATTERS:
 *      1. the CART page only (`is_cart()`), so the checkout's own
 *         Ship/Pickup toggle — which already says "Pickup" and which Andrew
 *         explicitly praised — is not touched;
 *      2. a live visit-flagged session only;
 *      3. **only when the SELECTED rate really is the hand-delivery rate.**
 *         A flagged parent who deliberately chooses ordinary shipping and
 *         then walks back to the cart must see "Shipping", because that is
 *         what is about to happen to their books. Labelling a $2.99 posted
 *         order "Hand delivery" would be a worse lie than the one being fixed.
 * ====================================================================== */

/**
 * True when THIS cart-page request is really going to be hand-delivered.
 *
 * ⛔ THE CONTROL PATH RETURNS BEFORE ANYTHING IS TOUCHED. An unflagged visitor
 *    exits on the first line, so no cart is read, no shipping is calculated and
 *    no session is written for anybody who is not a visit parent.
 *
 * ⭐ WHY IT MAY CALCULATE SHIPPING. `chosen_shipping_methods` is written by
 *    WooCommerce during totals calculation, and on a Blocks cart page that can
 *    happen AFTER `wp_enqueue_scripts`. Rather than guess at the ordering, this
 *    asks WooCommerce to do the calculation it was going to do anyway; the
 *    result is cached against the package hash, so the later call is a hit.
 *
 * @return bool
 */
function bhp_school_pickup_cart_shows_hand_delivery() {
	if ( ! bhp_school_visit_use_delivery_framing() ) {
		return false; // ZERO CHANGE, and zero work, for a normal visitor.
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
		return false;
	}
	if ( WC()->cart->is_empty() ) {
		return false;
	}

	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	if ( empty( $chosen ) && is_callable( array( WC()->cart, 'calculate_shipping' ) ) ) {
		WC()->cart->calculate_shipping();
		$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	}

	foreach ( $chosen as $method ) {
		if ( is_string( $method ) && 0 === strpos( $method, BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID . ':' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Print the one-line locale override that renames the Blocks cart totals row.
 *
 * ⛔ IT IS AN INLINE SCRIPT ON A DEPENDENCY OF `wp-i18n`, ENQUEUED FROM
 *    `wp_enqueue_scripts`, WHICH IS WHAT MAKES IT RUN IN TIME. The Blocks cart
 *    bundle is enqueued later, during block render in the body, so WordPress
 *    prints this first and React sees the overridden string on its FIRST paint.
 *    There is no flash and no MutationObserver anywhere in this build.
 *
 * ⛔ IT WRITES NO SETTING, NO OPTION AND NO TRANSLATION FILE. The override
 *    lives for exactly one page load.
 */
add_action( 'wp_enqueue_scripts', 'bhp_school_pickup_cart_totals_label_assets', 100 );
function bhp_school_pickup_cart_totals_label_assets() {
	if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	if ( ! bhp_school_pickup_cart_shows_hand_delivery() ) {
		return;
	}

	$handle = 'bhp-school-pickup-cart-label';
	wp_register_script( $handle, '', array( 'wp-i18n' ), BHP_BUNDLE_PRICING_VERSION, true );
	wp_enqueue_script( $handle );
	wp_add_inline_script( $handle, bhp_school_pickup_cart_totals_label_js( bhp_school_pickup_totals_label() ) );
}

/**
 * The override itself, as a string, so the test suite can assert it without a
 * browser and without re-deriving what it is supposed to contain.
 *
 * ⛔⛔ THE ARRAY SHAPE IS `[ translation ]`, NOT `[ null, translation ]`, AND
 *     THIS COST A DEPLOY TO FIND. The first attempt shipped the Jed-style
 *     `[ null, "Hand delivery" ]` — which is a real format, in po2json's
 *     "jed" mode, where index 0 holds the plural msgid. **Tannin, which is what
 *     `@wordpress/i18n` actually runs, reads index 0 as the TRANSLATION.** With
 *     a `null` there its `entry[ index ]` test fails and it silently returns
 *     the untranslated string. The page was byte-perfect, the script was in the
 *     right place in the right order, `wp.i18n` was loaded, no error was thrown
 *     anywhere, and the row still said "Shipping".
 *
 *     WordPress core emits the correct shape a few lines above this script on
 *     every page, and that is the authority for it:
 *         wp.i18n.setLocaleData( { 'text directionltr': [ 'ltr' ] } );
 *
 * ⭐ THE RULE THIS LEAVES BEHIND: a translation override is only real when a
 *    browser has been asked what `wp.i18n.__()` returns AFTER the page loads.
 *    Reading the emitted `<script>` tag proves the tag, not the translation.
 *
 * @param string $label The replacement label.
 * @return string JavaScript.
 */
function bhp_school_pickup_cart_totals_label_js( $label ) {
	return sprintf(
		'if ( window.wp && wp.i18n && wp.i18n.setLocaleData ) { wp.i18n.setLocaleData( { "Shipping": [ %s ] }, "woocommerce" ); }',
		wp_json_encode( (string) $label )
	);
}

/* =========================================================================
 * ⭐ 1.8.55 — THE CART DRAWER'S OWN SHIPPING ROW
 *
 * The Blocks totals row is not the only place the word survived on the cart
 * page. `bundle-drawer.js` builds its own summary from the Store API and had
 * `addRow( 'Shipping', ... )` hardcoded, which rendered
 * `<span>Shipping</span><span>FREE</span>` inside
 * `.bhp-cart-drawer__summary-row--free`. That markup is OURS, so it is fixed
 * here rather than reported.
 *
 * ⭐ THROUGH `bundle-drawer.php`'s OWN FILTERS, in exactly the shape
 *    `bhp_bundle_freeship_copy` above already uses, so the drawer keeps knowing
 *    nothing about school visits and this file keeps owning the words.
 *
 * ⛔ THE DRAWER DECIDES AT RENDER TIME FROM THE LIVE SELECTED RATE, not from a
 *    session value baked into the page. That makes the drawer route
 *    cache-proof: the localized payload below is IDENTICAL for every visitor,
 *    flagged or not, and only the rate the Store API actually reports back can
 *    change what is drawn.
 * ====================================================================== */

add_filter( 'bhp_bundle_drawer_ship_row_pickup_label', 'bhp_school_pickup_drawer_row_label', 10, 1 );
function bhp_school_pickup_drawer_row_label( $label ) {
	return bhp_school_pickup_totals_label();
}

add_filter( 'bhp_bundle_drawer_ship_row_pickup_method', 'bhp_school_pickup_drawer_row_method', 10, 1 );
function bhp_school_pickup_drawer_row_method( $method_id ) {
	return BHP_SCHOOL_PICKUP_NATIVE_METHOD_ID;
}

/* =========================================================================
 * CUSTOMER-FACING HONESTY
 * ====================================================================== */

/**
 * Thank-you page: say where the books are actually going.
 *
 * The stock line talks about an order being received. For a hand-delivery
 * order the single most useful sentence is where and when to collect, so it
 * is added above the order table rather than buried in the meta list.
 *
 * ⭐ WooCommerce 10.9.1 ALSO hides the shipping address on this page for a
 *    local-pickup order, natively, through
 *    `ShippingController::hide_shipping_address_for_local_pickup()` on the
 *    `woocommerce_order_hide_shipping_address` filter. That is new in 1.8.52
 *    and it is WooCommerce's doing, not this file's.
 *
 * @param int $order_id Order id.
 */
add_action( 'woocommerce_thankyou', 'bhp_school_pickup_thankyou_notice', 5 );
function bhp_school_pickup_thankyou_notice( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order || ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return;
	}
	$school = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_SCHOOL );
	$date   = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_DATE );
	$ts     = $date ? strtotime( $date . ' 12:00:00' ) : false;
	$pretty = $ts ? wp_date( 'l, F j', $ts ) : $date;

	echo '<div class="bhp-school-pickup-notice" style="border:1px solid #e5e0d3;border-left:4px solid #2f6f4f;padding:16px 18px;margin:0 0 24px;">';
	echo '<p style="margin:0 0 6px;font-weight:700;">' . esc_html__( 'Your books are being hand-delivered — nothing is being posted.', 'brave-hearts' ) . '</p>';
	if ( '' !== $school && '' !== $pretty ) {
		echo '<p style="margin:0;">' . esc_html(
			sprintf(
				/* translators: 1: school name, 2: visit date */
				__( 'Andrew will bring them, signed, to %1$s on %2$s. You do not need to do anything, and you will not be charged for shipping.', 'brave-hearts' ),
				$school,
				$pretty
			)
		) . '</p>';
	}
	echo '</div>';
}

/* =========================================================================
 * ⭐⭐ 1.8.54 — `CYCLE164-LD-COD-GATE`. A PARENT MUST NOT BE ABLE TO ORDER A
 *              SIGNED BOOK WITHOUT PAYING FOR IT.
 * ====================================================================== */

/**
 * ⛔ THE DEFECT THIS CLOSES, IN THE FOUNDER'S OWN WORDS.
 *
 *    Andrew Signore, verbatim, 2026-08-18:
 *      "they should have to pay before I receive the order- the parent must pay
 *       for the book/s before I sign them"
 *
 *    `cod` — Cash on Delivery, titled "Pay in Person" on this store — creates an
 *    order with NOTHING COLLECTED. On an ordinary order that is a considered
 *    business choice: Andrew sells in person at the Portneuf Valley Farmers
 *    Market and takes payment there, face to face, as he hands the book over.
 *
 *    ⛔ ON A SCHOOL-VISIT ORDER IT IS A DIFFERENT ANIMAL ENTIRELY. The parent is
 *    not standing in front of him. The order still carries the visit flag, the
 *    child's name, the packing note and the HAND DELIVERY note, so it flows into
 *    exactly the same pack-and-carry pipeline as a paid one. Andrew would sign a
 *    book, pack it, drive it to a school and hand it to a child FOR AN ORDER
 *    NOBODY PAID FOR, and would not find out until he reconciled afterwards.
 *
 * ⭐ THE FIX IS CODE, AND DELIBERATELY NOT CONFIGURATION.
 *
 *    The obvious "fix" is to disable `cod` in WooCommerce, or to populate its
 *    `enable_for_methods` so it excludes local pickup. BOTH ARE WRONG HERE:
 *
 *      · Disabling it globally KILLS THE FARMERS MARKET. That is real revenue,
 *        and breaking it to fix a schools problem is a bad trade.
 *      · Either edit is a WooCommerce PAYMENT SETTING, which is an Andrew-only
 *        gate with no exceptions. No agent may make it, on any environment.
 *      · A settings-level exclusion is also global and permanent, where the
 *        thing that is actually true is per-request: THIS visitor, in THIS
 *        session, arrived from a school's pre-visit link.
 *
 *    So the gateway is withheld for the duration of one flagged request and for
 *    nobody else. `woocommerce_cod_settings` is never read and never written by
 *    this file.
 *
 * ⭐ IT IS NOT A SECOND SOURCE OF TRUTH. It asks
 *    `bhp_school_visit_use_delivery_framing()` — THE one request-scoped predicate
 *    the pickup machine already uses for the collection page, the cart drawer,
 *    the checkout cross-sell, the theme's delivery bullet and the print-on-demand
 *    copy gate. If this file grew its own session read, one parent could be shown
 *    hand-delivery copy while still being offered an unpaid checkout, or the
 *    reverse. Zero copies of the predicate. One answer per request.
 *
 * ⛔ IT FAILS OPEN, AND THAT DIRECTION IS CHOSEN ON PURPOSE.
 *    Every early return below leaves `$gateways` EXACTLY as it arrived. If the
 *    predicate is missing, throws, or cannot resolve a session, an ordinary
 *    shopper keeps every payment method they had. Wrongly REMOVING a gateway from
 *    a paying customer breaks real revenue and is silent; wrongly KEEPING one on
 *    a flagged checkout is the bug we already have and is at least visible in the
 *    order. Between those two the safe default is unambiguous.
 *
 * ⭐ THIS IS A REAL ENFORCEMENT BOUNDARY, NOT COSMETICS — VERIFIED IN
 *    WOOCOMMERCE 10.9.1's OWN SOURCE ON STAGING, NOT ASSUMED:
 *      · `StoreApi/Schemas/V1/CartSchema.php:386` builds the Blocks checkout's
 *        `payment_methods` list from `get_available_payment_gateways()`, so a
 *        gateway withheld here does not RENDER.
 *      · `StoreApi/Routes/V1/Checkout.php:949-963` re-reads the SAME list when an
 *        order is submitted and throws
 *        `woocommerce_rest_checkout_payment_method_disabled` for anything absent
 *        from it. A hand-crafted POST of `payment_method=cod` on a flagged
 *        session is therefore REJECTED, not merely hidden.
 *    A gate that only hides a button is not a gate.
 *
 * ⚠ THE SET IS THE DEFERRED-PAYMENT SET, AND THAT IS SLIGHTLY WIDER THAN `cod`.
 *    `bacs` (Direct bank transfer) and `cheque` (Check payments) create an unpaid
 *    order by exactly the same mechanism and would produce exactly the same
 *    outcome. BOTH ARE CURRENTLY DISABLED ON THIS STORE, so including them has
 *    ZERO live effect today — it only means the hole cannot silently reopen if
 *    one is ever switched on. ⛔ ANDREW SHOULD KNOW THIS AND MAY NARROW IT: if he
 *    ever wants a school district to pay a hand-delivery order by bank transfer
 *    or purchase order, `bacs` must come off this list. It is filterable below
 *    precisely so that is a one-line decision and not a code rewrite.
 *
 * ⛔ CARD-BACKED WALLETS ARE NOT TOUCHED AND MUST NOT BE. `stripe`,
 *    `stripe_link` and `stripe_amazon_pay` all capture funds at checkout
 *    (`woocommerce_stripe_settings['capture'] === 'yes'` on this store), so they
 *    already satisfy the founder's rule. Withholding them would break the flagged
 *    parent's ability to pay at all, which is the opposite of the objective.
 *
 * @param array $gateways Available gateways, keyed by id.
 * @return array
 */
add_filter( 'woocommerce_available_payment_gateways', 'bhp_school_visit_block_unpaid_gateways', 20, 1 );
function bhp_school_visit_block_unpaid_gateways( $gateways ) {
	// FAIL OPEN: anything unexpected about the input, and nobody's checkout changes.
	if ( ! is_array( $gateways ) || empty( $gateways ) ) {
		return $gateways;
	}

	/*
	 * FAIL OPEN in wp-admin. The gateway screens must never be filtered by a
	 * front-end session flag, or Andrew could not see "Pay in Person" in order to
	 * manage it. AJAX is excluded from this escape hatch because the Blocks
	 * checkout's own requests can run through admin-ajax.
	 */
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}

	// FAIL OPEN: no predicate (plugin half-loaded, WooCommerce absent) -> no change.
	if ( ! function_exists( 'bhp_school_visit_use_delivery_framing' ) ) {
		return $gateways;
	}

	try {
		$is_visit = (bool) bhp_school_visit_use_delivery_framing();
	} catch ( Throwable $e ) {
		// FAIL OPEN: a resolver that throws must never cost a paying customer
		// their payment methods.
		return $gateways;
	}

	if ( ! $is_visit ) {
		return $gateways; // ⭐ ZERO CHANGE for every ordinary shopper. Same array, same order.
	}

	$original = $gateways;

	/**
	 * The gateways that create an order with NOTHING COLLECTED.
	 *
	 * ⛔ Only ever ADD a gateway here that genuinely defers payment. This list is
	 *    subtractive on a flagged checkout, so a card gateway landing in it would
	 *    make the visit checkout unpayable.
	 *
	 * @param string[] $ids Gateway ids to withhold from a visit-flagged checkout.
	 */
	$deferred = apply_filters(
		'bhp_school_visit_unpaid_gateway_ids',
		array( 'cod', 'bacs', 'cheque' )
	);

	if ( ! is_array( $deferred ) || empty( $deferred ) ) {
		return $original; // FAIL OPEN: a filter that returns nonsense withholds nothing.
	}

	foreach ( $deferred as $id ) {
		$id = (string) $id;
		if ( '' !== $id && isset( $gateways[ $id ] ) ) {
			unset( $gateways[ $id ] );
		}
	}

	/*
	 * ⛔ LAST-DITCH SAFETY. If withholding the deferred set would leave this
	 *    parent with NO way to pay at all, give them back what they had. An
	 *    unpayable checkout is a worse customer outcome than the problem being
	 *    solved. Today this branch is unreachable on this store (three Stripe
	 *    gateways are enabled) and it exists so that it stays unreachable.
	 */
	if ( empty( $gateways ) ) {
		return $original;
	}

	return $gateways;
}
