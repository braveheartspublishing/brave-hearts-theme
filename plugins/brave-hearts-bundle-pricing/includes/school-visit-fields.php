<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SCHOOL-VISIT CHECKOUT FIELDS — 1.8.50 (2026-08-17,
 * `CYCLE162-LD-PICKUP-FIELDS`). EXTENDS `school-visit-pickup.php`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, verbatim, RELAYED through the Chief of Staff and NOT
 * witnessed by this agent: *"lets make a official template that has a name
 * field for the child and an email capture for the parent and their name. We
 * can kill two bird with one stone - make it a email subscriber capture and
 * get the kids name for the signing"*.
 *
 * WHAT IT ADDS. Two fields at checkout, and ONLY for a session carrying a live
 * `?bhp_visit=` flag:
 *
 *   1. `brave-hearts/school-visit-child-name` — text, REQUIRED.
 *      "Child's first name (for the signed dedication)".
 *      → order meta `_bhp_school_visit_child_name`, the packing-list order
 *        note, the admin order panel, and the hand-delivery E1 email.
 *   2. `brave-hearts/school-visit-newsletter` — checkbox, UNCHECKED, optional.
 *      → order meta `_bhp_school_visit_newsletter_optin` (`yes`/`no`), and, on
 *        `yes` ONLY, one subscribe through the site's EXISTING signup core.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE TWO CONSENT RAILS. NEITHER IS NEGOTIABLE.
 * ---------------------------------------------------------------------------
 * ⛔ NO OPT-IN, NO SUBSCRIPTION, EVER. The checkbox is registered
 *    `'required' => false` and WooCommerce renders it UNCHECKED; the subscribe
 *    path is entered only from an explicit truthy value. Buying a book is not
 *    consent to be emailed, and neither is being a school-visit parent.
 * ⛔ THE CHILD'S NAME NEVER LEAVES THE ORDER. It is not in the Mailchimp
 *    payload, not in a merge field, not in a tag, and not in the source page.
 *    `bhp_school_visit_newsletter_payload()` is a PURE function precisely so
 *    that this can be asserted directly, and the test suite asserts it against
 *    a deliberately conspicuous child name.
 *
 * ---------------------------------------------------------------------------
 * ⭐ HOW THE MAILCHIMP REUSE WORKS — NO NEW INTEGRATION WAS MINTED
 * ---------------------------------------------------------------------------
 * The theme already has exactly ONE place that talks to Mailchimp:
 * `bhp_process_signup()` in `inc/mailchimp.php` (extracted 2026-07-30 so the
 * quiz endpoint and every classic form share one subscriber path). It is
 * documented as *"deliberately pure with respect to the request: it never
 * reads a superglobal, never redirects and never exits"* — which is exactly
 * what an order-processed hook needs. This file CALLS it. It does not
 * duplicate the API wrapper, the list resolution, the merge-field map, the
 * `TRAFFIC` graceful-no-op retry or the tag write.
 *
 * ⭐ TAGS travel through the EXISTING `bhp_mailchimp_signup_tags` filter, keyed
 *    on `$context === 'school_visit'` — the same shape as the four callbacks
 *    already in `functions.php`. Read that filter before changing anything
 *    here: its signature is `($tags, $context, $audience_type, $lead_magnet,
 *    $source_page)` and it CANNOT carry a visit slug, because no argument
 *    describes one.
 *
 * ⭐ SO THE SLUG IS PASSED OUT OF BAND, and that is a deliberate copy of the
 *    precedent already in the codebase: `bhp_get_capture_segment_audience_tag()`
 *    reads `$_POST['bhp_segment']` inside its own tag callback for the same
 *    reason. This file cannot read a superglobal (the value lives on the
 *    order), so it uses a one-request static set immediately before the call
 *    and cleared immediately after, in a `finally`.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHY REGISTRATION IS CONDITIONAL, AND THE TRAP IN DOING IT
 * ---------------------------------------------------------------------------
 * The brief requires normal checkout to be BYTE-IDENTICAL. WooCommerce's
 * additional-checkout-fields API has no per-request visibility switch, so the
 * only way to get that is to not REGISTER the fields at all for an unflagged
 * visitor.
 *
 * ⛔ THAT CREATES A REAL FAILURE MODE, and it is why this file has
 *    `bhp_school_visit_fields_session()` instead of just calling
 *    `bhp_school_visit_active()`:
 *
 *      · Registration runs on `woocommerce_init`.
 *      · `WC::init()` calls `wc_load_cart()` — which brings the session up —
 *        only when `is_request('frontend')`, and that test EXCLUDES REST.
 *        Verified by reading WooCommerce 10.9.1 `includes/class-woocommerce.php`
 *        on staging: `wc_load_cart()` at line ~960, `do_action('woocommerce_init')`
 *        at 968; the Store API loads the cart LAZILY instead, from
 *        `StoreApi/Utilities/CartController::get_cart_instance()`.
 *      · So on the `POST /wc/store/v1/checkout` request — the one that actually
 *        submits the order — `WC()->session` is NULL at `woocommerce_init`.
 *      · If the fields were therefore not registered on THAT request, the
 *        browser would post two `additional_fields` the server does not know,
 *        and the parent's checkout would fail.
 *
 *    The fix is narrow: when there is no session object yet, bring one up ONLY
 *    IF the visitor already carries a WooCommerce session cookie. A visitor
 *    with no cookie cannot possibly hold a visit flag, so they are returned
 *    from immediately, untouched — no session, no cookie, no cache effect.
 *
 * ---------------------------------------------------------------------------
 * ⚠ CONDITIONAL-REQUIRED: FOUND, EVALUATED, DELIBERATELY NOT USED
 * ---------------------------------------------------------------------------
 * WooCommerce 10.9.1 DOES support a rules-based conditional `required` —
 * `CheckoutFields::is_required_field()` runs `Validation::validate_document_object()`
 * against a `DocumentObject` whose `cart.shipping_rates` holds the SELECTED
 * rate ids (`Blocks/Domain/Services/CheckoutFieldsSchema/DocumentObject.php`
 * lines 129–141, read on staging). A rule keyed on the pickup rate id is
 * therefore expressible.
 *
 * ⛔ IT IS NOT USED, for one reason that is about failure mode rather than
 *    taste: `contains_valid_rules()` returns false for a malformed rule and the
 *    field then falls back to `true === $field['required']`, which is FALSE for
 *    an array. A typo would therefore make a required field SILENTLY OPTIONAL,
 *    and nothing would show it. The brief explicitly permits the simpler path
 *    — "required whenever the visit flag is active (simpler, acceptable — every
 *    flagged visitor is a visit parent)" — and a static `true` is a thing the
 *    test suite can assert exactly.
 *
 * ⚠ THE CONSEQUENCE, STATED RATHER THAN HIDDEN: a flagged parent who chooses
 *   ordinary posted shipping instead of hand-delivery is still asked for the
 *   child's first name. Nothing is signed for that order, and no copy anywhere
 *   promises that it will be.
 *
 * ⭐ 1.8.52 NOTE ON THAT PARAGRAPH. The rule-based route described above is
 *    still available and is still not used, but its rule would now be keyed on
 *    WooCommerce's own `pickup_location:<n>` rate id rather than the retired
 *    `bhp_school_pickup` one. The reason for not using it is unchanged and is
 *    about failure mode, not about which id it would name.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT 1.8.52 (`CYCLE163-LD-PICKUP-NATIVE`) CHANGED IN THIS FILE
 * ---------------------------------------------------------------------------
 * Exactly one thing: `bhp_school_visit_fields_session()` became a thin alias
 * for `bhp_school_visit_request_record()`, and `bhp_school_visit_has_session_
 * cookie()` moved to `school-visit-pickup.php` with it. **No field definition,
 * label, consent rail, sanitiser, validator, meta key, tag, payload or hook
 * priority in this file was touched.** The child-name field and the newsletter
 * opt-in still register for a flagged session and only for a flagged session.
 *
 * ---------------------------------------------------------------------------
 * ⛔ RAILS THIS FILE DOES NOT CROSS
 * ---------------------------------------------------------------------------
 * ⛔ NO product, price, coupon, stock, shipping, tax, payment or checkout
 *    SETTING is read or written on any environment. It registers two fields and
 *    writes order meta.
 * ⛔ It sends NO email. The one outbound call it can make is the existing
 *    Mailchimp signup, and only behind an affirmative checkbox.
 * ⛔ NO VISIT DATA IS HARDCODED — same rule as `school-visit-pickup.php`, same
 *    structural assertion in the suite.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

/** Field ids. Namespaced `vendor/field`, as the API requires. */
if ( ! defined( 'BHP_SCHOOL_VISIT_FIELD_CHILD' ) ) {
	define( 'BHP_SCHOOL_VISIT_FIELD_CHILD', 'brave-hearts/school-visit-child-name' );
}
if ( ! defined( 'BHP_SCHOOL_VISIT_FIELD_OPTIN' ) ) {
	define( 'BHP_SCHOOL_VISIT_FIELD_OPTIN', 'brave-hearts/school-visit-newsletter' );
}
/** Explicit, stable order meta — never read WooCommerce's `_wc_other/` key twice. */
if ( ! defined( 'BHP_SCHOOL_VISIT_META_CHILD' ) ) {
	define( 'BHP_SCHOOL_VISIT_META_CHILD', '_bhp_school_visit_child_name' );
}
if ( ! defined( 'BHP_SCHOOL_VISIT_META_OPTIN' ) ) {
	define( 'BHP_SCHOOL_VISIT_META_OPTIN', '_bhp_school_visit_newsletter_optin' );
}
/** Idempotency guard — both order-processed hooks can fire in one request. */
if ( ! defined( 'BHP_SCHOOL_VISIT_META_SYNCED' ) ) {
	define( 'BHP_SCHOOL_VISIT_META_SYNCED', '_bhp_school_visit_newsletter_synced' );
}
/** The longest child's first name that will be stored. */
if ( ! defined( 'BHP_SCHOOL_VISIT_CHILD_MAXLEN' ) ) {
	define( 'BHP_SCHOOL_VISIT_CHILD_MAXLEN', 40 );
}

/* =========================================================================
 * THE SESSION, SAFELY, ON EVERY REQUEST TYPE
 * ====================================================================== */

/**
 * The live visit for THIS request, resolved in a way that also works on the
 * Store API request where `WC()->session` has not been built yet.
 *
 * ⭐⭐ 1.8.52 (`CYCLE163-LD-PICKUP-NATIVE`) — THE IMPLEMENTATION MOVED. This is
 *     now a THIN ALIAS for `bhp_school_visit_request_record()` in
 *     `school-visit-pickup.php`, and `bhp_school_visit_has_session_cookie()`
 *     moved there with it.
 *
 * ⛔ WHY IT MOVED RATHER THAN BEING DUPLICATED: 1.8.52 makes WooCommerce's own
 *    local pickup visible by filtering two option READS, and those filters run
 *    on requests where this file has not been required yet. Two copies of a
 *    session resolver, one of which silently brings a session up, is exactly
 *    the sort of thing that drifts apart and then disagrees on the one request
 *    that matters.
 *
 * ⭐ THE ALIAS IS KEPT rather than the call sites rewritten, because this
 *    file's field registration, help-text enqueue and slug fallback all read
 *    better under this name, and because a future reader arriving from the
 *    1.8.50 documentation will look for it.
 *
 * ⛔ THE REENTRANCY GUARD AND THE `Throwable` CATCH LIVE IN THE MOVED
 *    IMPLEMENTATION, not here. Read that function before changing this one.
 *
 * @return array{slug:string,school:string,date:string,cutoff:string,time:string}|null
 */
function bhp_school_visit_fields_session() {
	if ( ! function_exists( 'bhp_school_visit_request_record' ) ) {
		return null;
	}
	return bhp_school_visit_request_record();
}

/* =========================================================================
 * FIELD REGISTRATION
 * ====================================================================== */

/**
 * The two field definitions, as one filterable list.
 *
 * Kept separate from the registration call so the suite can assert the exact
 * shipped labels, types and required-ness without a WooCommerce request.
 *
 * ⚠ THE OPT-IN LABEL NAMES A NEWSLETTER — "The Ten-Minute Thing" — THAT DOES
 *   NOT APPEAR ANYWHERE ELSE IN THIS REPOSITORY. The wording was supplied by
 *   the build brief, relayed through the Chief of Staff; it was NOT witnessed
 *   from Andrew by this agent, and no such publication was found in the theme,
 *   the plugin or the docs. It is CUSTOMER-FACING COPY MAKING A DELIVERY
 *   PROMISE ("one short letter a month"), so it is flagged for Andrew before
 *   production. It changes in one place: here.
 *
 * @return array<string,array<string,mixed>>
 */
function bhp_school_visit_field_definitions() {
	return apply_filters(
		'bhp_school_visit_field_definitions',
		array(
			'child_name' => array(
				'id'          => BHP_SCHOOL_VISIT_FIELD_CHILD,
				'label'       => __( 'Child’s first name (for the signed dedication)', 'brave-hearts' ),
				'location'    => 'order',
				'type'        => 'text',
				'required'    => true,
				'meta'        => BHP_SCHOOL_VISIT_META_CHILD,
				/*
				 * ⛔ FIRST NAME ONLY — DELIBERATE PRIVACY MINIMALISM. The help
				 *    text says what the name is for, in one sentence, so the
				 *    parent is never guessing why a child's name is being asked
				 *    for in a checkout form.
				 */
				'description' => __( 'Andrew signs each book to your child by name.', 'brave-hearts' ),
				'attributes'  => array(
					'autocomplete'   => 'off',
					'autocapitalize' => 'words',
					// ⭐ THE STABLE QA SELECTOR. The block checkout generates its
					//    own ids; a `data-` attribute is the one hook that
					//    survives a WooCommerce markup change.
					'data-bhp-field' => 'school-visit-child-name',
					'maxLength'      => BHP_SCHOOL_VISIT_CHILD_MAXLEN,
				),
			),
			'newsletter' => array(
				'id'                         => BHP_SCHOOL_VISIT_FIELD_OPTIN,
				'label'                      => __( 'Send me The Ten-Minute Thing — one short letter a month from Andrew. Free, unsubscribe anytime.', 'brave-hearts' ),
				'location'                   => 'order',
				'type'                       => 'checkbox',
				// ⛔ FALSE IS THE CONSENT RAIL, not a default worth "improving".
				'required'                   => false,
				'meta'                       => BHP_SCHOOL_VISIT_META_OPTIN,
				'show_in_order_confirmation' => false,
				'attributes'                 => array(
					'data-bhp-field' => 'school-visit-newsletter',
				),
			),
		)
	);
}

/**
 * Register both fields — for a flagged session only.
 *
 * @return void
 */
function bhp_school_visit_register_checkout_fields() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	if ( ! bhp_school_visit_fields_session() ) {
		return; // ZERO CHANGE for a normal visitor: the fields do not exist.
	}

	foreach ( bhp_school_visit_field_definitions() as $field ) {
		$args = array(
			'id'       => $field['id'],
			'label'    => $field['label'],
			'location' => $field['location'],
			'type'     => $field['type'],
			'required' => $field['required'],
		);
		if ( ! empty( $field['attributes'] ) ) {
			$args['attributes'] = $field['attributes'];
		}
		if ( isset( $field['show_in_order_confirmation'] ) ) {
			$args['show_in_order_confirmation'] = $field['show_in_order_confirmation'];
		}
		if ( 'text' === $field['type'] ) {
			$args['sanitize_callback'] = 'bhp_school_visit_sanitize_child_name';
			$args['validate_callback'] = 'bhp_school_visit_validate_child_name';
		}
		woocommerce_register_additional_checkout_field( $args );
	}
}
add_action( 'woocommerce_init', 'bhp_school_visit_register_checkout_fields', 20 );

/**
 * Sanitise the child's first name.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function bhp_school_visit_sanitize_child_name( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}
	$value = wp_strip_all_tags( (string) $value );
	$value = sanitize_text_field( $value );
	$value = trim( preg_replace( '/\s+/u', ' ', $value ) );

	return function_exists( 'mb_substr' )
		? mb_substr( $value, 0, BHP_SCHOOL_VISIT_CHILD_MAXLEN )
		: substr( $value, 0, BHP_SCHOOL_VISIT_CHILD_MAXLEN );
}

/**
 * Validate the child's first name.
 *
 * ⛔ SUPPLYING A `validate_callback` REPLACES WooCommerce's default one, so the
 *    required check is re-implemented here rather than inherited. Dropping it
 *    would make the field silently optional.
 *
 * ⭐ The one extra rule is a PRIVACY rule, not a formatting one: a value that
 *    looks like an email address is refused, because the field is asked for
 *    directly beneath an email field and a mistyped row would put a child's
 *    contact detail into an order. It does NOT police names — no character
 *    whitelist, no length minimum, no capitalisation rule.
 *
 * @param mixed $value Sanitised value.
 * @param array $field Field definition.
 * @return WP_Error|void
 */
function bhp_school_visit_validate_child_name( $value, $field = array() ) {
	$value    = is_scalar( $value ) ? trim( (string) $value ) : '';
	$required = ! empty( $field['required'] );

	if ( $required && '' === $value ) {
		return new WP_Error(
			'bhp_school_visit_child_name_required',
			__( 'Please tell us your child’s first name so Andrew can sign their book.', 'brave-hearts' )
		);
	}
	if ( '' !== $value && false !== strpos( $value, '@' ) ) {
		return new WP_Error(
			'bhp_school_visit_child_name_invalid',
			__( 'Please enter your child’s first name only — not an email address.', 'brave-hearts' )
		);
	}
}

/* =========================================================================
 * THE HELP TEXT
 * ====================================================================== */

/**
 * Render the child-name field's help sentence, for a flagged session only.
 *
 * ⛔ WHY THIS IS JAVASCRIPT AND NOT A FIELD PROPERTY. The additional-checkout-
 *    fields API has NO description/help-text property. Verified by reading
 *    `CheckoutFields::register_checkout_field()`'s `wp_parse_args` defaults in
 *    WooCommerce 10.9.1 on staging — the complete set is id, label,
 *    optionalLabel, location, type, hidden, required, attributes,
 *    show_in_order_confirmation, sanitize_callback, validate_callback,
 *    validation. `attributes` is additionally whitelisted to maxLength,
 *    readOnly, pattern, autocomplete, autocapitalize, title plus `aria-*` and
 *    `data-*` (same file, `register_field_attributes()`), so there is no
 *    `placeholder` and no `description` route either.
 *
 * ⛔ SO IT IS INSERTED INTO THE RENDERED FIELD, and the block checkout is React:
 *    the node is re-inserted on an interval for 20 seconds because a re-render
 *    can drop it, and the function is idempotent so a re-render cannot produce
 *    two. After 20 seconds the interval stops — a checkout page that has not
 *    rendered its fields by then has a larger problem than a missing sentence.
 *
 * ⛔ IT IS ENQUEUED ONLY FOR A FLAGGED SESSION. A normal checkout receives no
 *    handle, no inline script and no inline style.
 *
 * ⚠ HONEST LIMIT: this is progressive enhancement. If the script does not run,
 *   the field, its label and its required-ness are all unaffected — only the
 *   explanatory sentence is missing. Nothing about the order depends on it.
 *
 * @return void
 */
function bhp_school_visit_enqueue_field_help() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( ! bhp_school_visit_fields_session() ) {
		return;
	}

	$definitions = bhp_school_visit_field_definitions();
	$help        = isset( $definitions['child_name']['description'] ) ? (string) $definitions['child_name']['description'] : '';
	if ( '' === $help ) {
		return;
	}

	wp_register_style( 'bhp-school-visit-fields', false, array(), BHP_BUNDLE_PRICING_VERSION );
	wp_enqueue_style( 'bhp-school-visit-fields' );
	wp_add_inline_style(
		'bhp-school-visit-fields',
		'.bhp-school-visit-help{margin:6px 0 0;font-size:13px;line-height:1.45;color:#4a4a4a;}'
	);

	wp_register_script( 'bhp-school-visit-fields', false, array(), BHP_BUNDLE_PRICING_VERSION, true );
	wp_enqueue_script( 'bhp-school-visit-fields' );
	wp_add_inline_script(
		'bhp-school-visit-fields',
		'(function(){var T=' . wp_json_encode( $help ) . ';var ID="bhp-school-visit-help";' .
		'function go(){var i=document.querySelector(\'input[data-bhp-field="school-visit-child-name"]\');' .
		'if(!i){return;}var w=i.closest(".wc-block-components-text-input")||i.parentNode;' .
		'if(!w||!w.parentNode){return;}if(w.parentNode.querySelector("."+ID)){return;}' .
		'var p=document.createElement("p");p.className=ID;p.id=ID;p.textContent=T;' .
		'w.parentNode.insertBefore(p,w.nextSibling);i.setAttribute("aria-describedby",ID);}' .
		'var t=setInterval(go,400);setTimeout(function(){clearInterval(t);},20000);' .
		'if(document.readyState!=="loading"){go();}else{document.addEventListener("DOMContentLoaded",go);}})();'
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_school_visit_enqueue_field_help', 30 );

/* =========================================================================
 * ORDER MARKING
 * ====================================================================== */

/**
 * Read one additional-field value off an order.
 *
 * WooCommerce stores `order`-location values under `_wc_other/{field_id}` —
 * the same key the theme's marketing-consent mirror reads. This file mirrors
 * into its own explicit meta for the same reason that one does: so nothing
 * downstream depends on WooCommerce's internal storage format.
 *
 * @param WC_Order $order Order.
 * @param string   $id    Field id.
 * @return mixed
 */
function bhp_school_visit_raw_field_value( WC_Order $order, $id ) {
	return $order->get_meta( '_wc_other/' . $id );
}

/**
 * Is a checkbox value affirmative?
 *
 * Deliberately liberal in what it ACCEPTS as a yes and absolute in its
 * default: anything not on this list is NO.
 *
 * @param mixed $value Stored value.
 * @return bool
 */
function bhp_school_visit_is_affirmative( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) ) {
		return 1 === $value;
	}
	if ( is_string( $value ) ) {
		return in_array( strtolower( trim( $value ) ), array( '1', 'yes', 'true', 'on' ), true );
	}
	return false;
}

/**
 * The child's first name recorded on an order, or ''.
 *
 * Reads the explicit meta first and falls back to WooCommerce's own key, so a
 * caller (the email template, the admin panel) still works on an order written
 * before the mirror ran, or if the mirror ever failed.
 *
 * @param WC_Order|int|mixed $order Order or id.
 * @return string
 */
function bhp_school_visit_child_name( $order ) {
	if ( is_numeric( $order ) ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $order ) : null;
	}
	if ( ! $order instanceof WC_Order ) {
		return '';
	}
	$name = (string) $order->get_meta( BHP_SCHOOL_VISIT_META_CHILD );
	if ( '' === $name ) {
		$name = (string) bhp_school_visit_raw_field_value( $order, BHP_SCHOOL_VISIT_FIELD_CHILD );
	}
	return bhp_school_visit_sanitize_child_name( $name );
}

/**
 * Did this order's customer opt in to the newsletter?
 *
 * @param WC_Order|int|mixed $order Order or id.
 * @return bool
 */
function bhp_school_visit_optin_given( $order ) {
	if ( is_numeric( $order ) ) {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $order ) : null;
	}
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	$explicit = $order->get_meta( BHP_SCHOOL_VISIT_META_OPTIN );
	if ( '' !== $explicit && null !== $explicit ) {
		return 'yes' === $explicit;
	}
	return bhp_school_visit_is_affirmative( bhp_school_visit_raw_field_value( $order, BHP_SCHOOL_VISIT_FIELD_OPTIN ) );
}

/**
 * Mirror both submitted values into explicit order meta.
 *
 * ⛔ PRIORITY 1 IS LOAD-BEARING. `bhp_school_pickup_mark_order()` writes the
 *    packing-list note at priority 5 on the SAME two hooks, and that note now
 *    names the child. Running after it would produce a note that says the child
 *    is unknown on every single order.
 *
 * ⛔ IT WRITES `no` EXPLICITLY when the box was not ticked. A missing meta and a
 *    declined opt-in must not be the same state — one is "we never asked", the
 *    other is a recorded refusal, and only the second is auditable.
 *
 * @param WC_Order|int $order Order or id (classic passes the id first).
 * @return void
 */
function bhp_school_visit_store_field_meta( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( (int) $order );
	}
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$raw_child = bhp_school_visit_raw_field_value( $order, BHP_SCHOOL_VISIT_FIELD_CHILD );
	$raw_optin = bhp_school_visit_raw_field_value( $order, BHP_SCHOOL_VISIT_FIELD_OPTIN );

	// Neither field was registered for this order — an ordinary customer.
	// Touch nothing at all.
	if ( ( '' === $raw_child || null === $raw_child ) && ( '' === $raw_optin || null === $raw_optin ) ) {
		return;
	}

	$child = bhp_school_visit_sanitize_child_name( $raw_child );
	if ( '' !== $child ) {
		$order->update_meta_data( BHP_SCHOOL_VISIT_META_CHILD, $child );
	}

	$opted_in = bhp_school_visit_is_affirmative( $raw_optin );
	$order->update_meta_data( BHP_SCHOOL_VISIT_META_OPTIN, $opted_in ? 'yes' : 'no' );
	if ( $opted_in ) {
		$order->update_meta_data( '_bhp_school_visit_optin_timestamp', current_time( 'mysql', true ) );
	}

	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_school_visit_store_field_meta', 1 );
add_action( 'woocommerce_checkout_order_processed', 'bhp_school_visit_store_field_meta', 1 );

/* =========================================================================
 * THE MAILCHIMP SUBSCRIBE — REUSED, NOT REBUILT
 * ====================================================================== */

/**
 * The visit slug the CURRENT subscribe belongs to, for the tag callback.
 *
 * Set and cleared by `bhp_school_visit_subscribe_parent()` only. See the file
 * header for why this is out of band rather than a filter argument.
 *
 * @param string|null $set Internal. Pass a string to set, '' to clear.
 * @return string
 */
function bhp_school_visit_current_signup_slug( $set = null ) {
	static $slug = '';
	if ( null !== $set ) {
		$slug = sanitize_key( (string) $set );
	}
	return $slug;
}

/**
 * Tags for a school-visit signup, through the theme's existing filter.
 *
 * A SEPARATE `add_filter` call, never an edit to one of the four already in
 * `functions.php` — the same reasoning those four record about each other.
 *
 * ⛔ NO TAG NAMES A RESOURCE. This signup delivers no lead magnet, so it claims
 *    none. It records where the person came from and who they are.
 *
 * @param array  $tags          Tags so far.
 * @param string $context       Signup context.
 * @param string $audience_type Normalised audience.
 * @param string $lead_magnet   Lead-magnet key.
 * @param string $source_page   Source page.
 * @return array
 */
function bhp_school_visit_mailchimp_tags( $tags, $context, $audience_type = '', $lead_magnet = '', $source_page = '' ) {
	if ( 'school_visit' !== $context ) {
		return $tags;
	}

	$resolved = array( 'Source: School Visit', 'Audience: Parent/Grandparent' );

	$slug = bhp_school_visit_current_signup_slug();
	if ( '' !== $slug ) {
		$resolved[] = 'Visit: ' . $slug;
	}

	return $resolved;
}
add_filter( 'bhp_mailchimp_signup_tags', 'bhp_school_visit_mailchimp_tags', 10, 5 );

/**
 * Build the signup payload for one order. PURE — no writes, no network.
 *
 * ⛔⛔ THIS FUNCTION IS THE CONSENT RAIL MADE TESTABLE. Everything that will be
 *     sent to Mailchimp is decided here and nowhere else, so the assertion
 *     "the child's name is not in the payload" is a real assertion about the
 *     shipped code rather than a claim about intent.
 *
 * ⛔ The parent's name and email come from the BILLING fields the order already
 *    has. No new personal data is collected for the newsletter — that is the
 *    whole "two birds" of Andrew's instruction.
 *
 * @param WC_Order $order Order.
 * @return array{email:string,name:string,require_name:bool,context:string,audience_type:string,lead_magnet:string,source_page:string,success_redirect_key:string}
 */
function bhp_school_visit_newsletter_payload( WC_Order $order ) {
	return array(
		'email'                => sanitize_email( (string) $order->get_billing_email() ),
		'name'                 => sanitize_text_field( (string) $order->get_billing_first_name() ),
		// The name is a nicety here, not a gate: a blank billing first name
		// must never cost the parent the subscription they asked for.
		'require_name'         => false,
		'context'              => 'school_visit',
		'audience_type'        => 'parents_families',
		// ⛔ EMPTY ON PURPOSE. No lead magnet is promised by this checkbox, so
		//    no lead-magnet key is recorded. `bhp_process_signup()` drops an
		//    empty merge value rather than writing a blank one.
		'lead_magnet'          => '',
		'source_page'          => home_url( '/' ),
		'success_redirect_key' => '',
	);
}

/**
 * Subscribe the parent, once, if and only if they ticked the box.
 *
 * ⛔ PRIORITY 30: after the meta mirror (1) and after the packing-list marking
 *    (5), so the decision is read from settled state.
 *
 * ⛔ IT CANNOT BREAK CHECKOUT. `bhp_process_signup()` catches `Throwable`
 *    internally and returns a code; this function additionally wraps the call,
 *    so a fatal in any hooked filter still cannot escape into the order
 *    pipeline. A failed subscribe leaves a private order note and a
 *    `failed` sync state — never a failed order.
 *
 * ⚠ IT IS SYNCHRONOUS, and that is a trade-off rather than an oversight: it
 *   adds one HTTP round-trip to the order-processed request. It was chosen over
 *   a scheduled event because a scheduled event that silently never runs loses
 *   a consent the parent actively gave, and this store's order volume makes the
 *   latency irrelevant. If that ever stops being true, move the call — not the
 *   consent check — behind `wp_schedule_single_event()`.
 *
 * @param WC_Order|int $order Order or id.
 * @return void
 */
function bhp_school_visit_subscribe_parent( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( (int) $order );
	}
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	// Already handled — both hooks can fire in a single request.
	if ( '' !== (string) $order->get_meta( BHP_SCHOOL_VISIT_META_SYNCED ) ) {
		return;
	}
	// ⛔⛔ THE RAIL. No affirmative opt-in → return, with nothing recorded and
	//     nothing sent. This is the only gate, and there is no other caller.
	if ( ! bhp_school_visit_optin_given( $order ) ) {
		return;
	}

	$payload = bhp_school_visit_newsletter_payload( $order );

	if ( '' === $payload['email'] ) {
		$order->update_meta_data( BHP_SCHOOL_VISIT_META_SYNCED, 'no_email' );
		$order->add_order_note( __( 'Newsletter opt-in recorded, but the order carries no billing email — nothing was sent to Mailchimp.', 'brave-hearts' ) );
		$order->save();
		return;
	}

	$slug = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_SLUG );
	if ( '' === $slug ) {
		$record = bhp_school_visit_fields_session();
		$slug   = $record ? $record['slug'] : '';
	}

	bhp_school_visit_current_signup_slug( $slug );

	try {
		/**
		 * Short-circuit the subscribe.
		 *
		 * Returning a non-null array skips `bhp_process_signup()` entirely.
		 * It exists so an operator can suspend the sync without a deploy, and
		 * so the test suite can assert this path WITHOUT touching Mailchimp.
		 *
		 * @param array|null $pre     Null to proceed.
		 * @param array      $payload The signup payload.
		 * @param WC_Order   $order   The order.
		 */
		$result = apply_filters( 'bhp_school_visit_pre_subscribe', null, $payload, $order );

		if ( null === $result ) {
			if ( ! function_exists( 'bhp_process_signup' ) ) {
				$order->update_meta_data( BHP_SCHOOL_VISIT_META_SYNCED, 'unavailable' );
				$order->add_order_note( __( 'Newsletter opt-in recorded, but the site signup handler is unavailable — this subscriber must be added by hand. The consent is stored on this order.', 'brave-hearts' ) );
				$order->save();
				return;
			}
			$result = bhp_process_signup( $payload );
		}
	} catch ( Throwable $e ) {
		$result = array( 'ok' => false, 'code' => 'error' );
	} finally {
		bhp_school_visit_current_signup_slug( '' );
	}

	$ok = is_array( $result ) && ! empty( $result['ok'] );
	$order->update_meta_data( BHP_SCHOOL_VISIT_META_SYNCED, $ok ? 'yes' : 'failed' );

	if ( $ok ) {
		$order->add_order_note(
			sprintf(
				/* translators: %s: visit slug, or a note that it is unresolved */
				__( 'Newsletter opt-in: subscribed to Mailchimp (Source: School Visit / %s). The child’s name was NOT sent.', 'brave-hearts' ),
				'' !== $slug ? 'Visit: ' . $slug : __( 'visit unresolved', 'brave-hearts' )
			)
		);
	} else {
		$code = is_array( $result ) && isset( $result['code'] ) ? (string) $result['code'] : 'error';
		$order->add_order_note(
			sprintf(
				/* translators: %s: generic failure classifier */
				__( '⚠ Newsletter opt-in recorded but the Mailchimp write did NOT succeed (%s). The consent is stored on this order and the subscriber must be added by hand.', 'brave-hearts' ),
				$code
			)
		);
	}

	/**
	 * Fires after a school-visit newsletter subscribe attempt.
	 *
	 * @param WC_Order $order   The order.
	 * @param array    $payload The payload that was used.
	 * @param mixed    $result  The signup result.
	 */
	do_action( 'bhp_school_visit_newsletter_processed', $order, $payload, $result );

	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_school_visit_subscribe_parent', 30 );
add_action( 'woocommerce_checkout_order_processed', 'bhp_school_visit_subscribe_parent', 30 );

/* =========================================================================
 * ONE GLANCE IN WP-ADMIN
 * ====================================================================== */

/**
 * The packing-list line in the admin order screen: school · date · child.
 *
 * WooCommerce's own additional-fields panel already shows the child's name on
 * an order, but it shows it as a field among fields. Andrew is reading a list
 * of orders at 6am to work out what to put in a bag, so school, date and child
 * are printed together, once, where the shipping address normally is.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function bhp_school_visit_admin_order_panel( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! function_exists( 'bhp_school_pickup_order_is_pickup' ) || ! bhp_school_pickup_order_is_pickup( $order ) ) {
		return;
	}

	$school = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_SCHOOL );
	$date   = (string) $order->get_meta( BHP_SCHOOL_PICKUP_META_DATE );
	$child  = bhp_school_visit_child_name( $order );
	$optin  = bhp_school_visit_optin_given( $order ) ? __( 'yes', 'brave-hearts' ) : __( 'no', 'brave-hearts' );

	echo '<div class="bhp-school-visit-admin" style="margin-top:12px;padding:10px 12px;border-left:4px solid #2f6f4f;background:#f6f7f7;">';
	echo '<p style="margin:0 0 4px;font-weight:700;">' . esc_html__( 'HAND DELIVERY — DO NOT SHIP', 'brave-hearts' ) . '</p>';
	echo '<p style="margin:0;">';
	echo esc_html__( 'School:', 'brave-hearts' ) . ' ' . esc_html( '' !== $school ? $school : __( 'unresolved', 'brave-hearts' ) ) . '<br>';
	echo esc_html__( 'Visit date:', 'brave-hearts' ) . ' ' . esc_html( '' !== $date ? $date : __( 'unresolved', 'brave-hearts' ) ) . '<br>';
	echo esc_html__( 'Sign to:', 'brave-hearts' ) . ' <strong>' . esc_html( '' !== $child ? $child : __( 'no child name on this order', 'brave-hearts' ) ) . '</strong><br>';
	echo esc_html__( 'Newsletter opt-in:', 'brave-hearts' ) . ' ' . esc_html( $optin );
	echo '</p></div>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'bhp_school_visit_admin_order_panel', 20 );
