<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE OPT-IN POSITION SUITE — theme 1.19.385, 2026-09-06,
 * `CYCLE179-CX-EMAIL-CAPTURE`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-cx-optin-position.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ WHAT THIS SUITE GUARDS, AND WHY IT IS NOT A DUPLICATE OF
 *     `test-cycle168-checkout-optin.php`
 * ---------------------------------------------------------------------------
 * CYCLE168's suite guards the WIRE: a ticked box subscribes with the purchase
 * tags, an unticked box does nothing, and neither can break an order. That
 * suite is unchanged by this build and is NOT re-implemented here.
 *
 * This suite guards the ONE thing 1.19.385 changed and the THREE things it
 * must not have changed:
 *
 *   CHANGED  the field's `location` is now `contact`, so WooCommerce renders
 *            it in the Contact information step, under the email field,
 *            instead of in "Additional order information" below payment.
 *
 *   UNCHANGED  the stored order-meta key. `_wc_other/brave-hearts/new-book-releases`
 *              and its mirror `_bhp_new_book_releases_optin`. WooCommerce sends
 *              every non-`address` location to `CheckoutFields::OTHER_FIELDS_PREFIX`,
 *              so `contact` and `order` store the same key. ⛔ IF THIS EVER
 *              REGRESSES, EVERY HISTORICAL CONSENT RECORD ORPHANS SILENTLY,
 *              which is why it is asserted rather than trusted.
 *
 *   UNCHANGED  the approved label, byte for byte.
 *
 *   UNCHANGED  the field is optional and is NEVER pre-checked. A pre-ticked
 *              marketing box is Andrew's decision, not a layout side effect.
 *
 * ⛔ IT WRITES NOTHING. No order, no option, no product, no post, no meta, no
 *    network call. Every assertion reads a definition, a registry entry or a
 *    vendor constant. It is safe to run on any environment, and it makes no
 *    claim about what a browser rendered — that evidence is the captures in
 *    `Business OS\ANDREW-REVIEW\2026-09-06\EMAIL-CAPTURE\`, not this file.
 */

defined( 'ABSPATH' ) || exit;

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE (1.19.386).
 *
 * ⭐ Six real emails left staging through Google's SMTP relay during two suite
 *    runs and bounced back to the founder. Staging now relays live, so any
 *    test that creates an order or moves one between statuses is an
 *    outbound-mail event. This include stops every one of them at
 *    `pre_wp_mail`, captures it instead, and PROVES the block at include time
 *    rather than assuming it.
 *
 * ⛔ NO ISO DATE APPEARS IN THIS BLOCK, AND THAT IS DELIBERATE. Two suites
 *    scan their OWN source for one and fail if they find it — which is
 *    exactly what the first version of this comment did to them. The dated
 *    evidence lives in tests/bootstrap-mail-guard.php, which nothing scans.
 *
 * ⛔ Assert on mail with `bhp_test_mail_log()` / `bhp_test_mail_find()`.
 *    Never by sending. See tests/bootstrap-mail-guard.php.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$GLOBALS['bhp_op_pass'] = 0;
$GLOBALS['bhp_op_fail'] = 0;

function bhp_op_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_op_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_op_fail']++;
		echo "FAIL  {$label}" . ( $detail ? "  -- {$detail}" : '' ) . "\n";
	}
}

function bhp_op_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/* ===================================================================
 * §1 — the definition list after the F12 merge filter
 * =================================================================== */
bhp_op_head( '§1 the merged definition' );

bhp_op_ok(
	'§1.1 bhp_get_marketing_consent_field_definitions() exists',
	function_exists( 'bhp_get_marketing_consent_field_definitions' )
);

$defs = function_exists( 'bhp_get_marketing_consent_field_definitions' )
	? bhp_get_marketing_consent_field_definitions()
	: array();

bhp_op_ok( '§1.2 exactly ONE consent field survives the F12 merge', count( $defs ) === 1, 'count=' . count( $defs ) );
bhp_op_ok( '§1.3 the survivor is new_book_releases', isset( $defs['new_book_releases'] ) );
bhp_op_ok(
	'§1.4 explorer_updates is NOT registered (teacher offer stays out of the parent path)',
	! isset( $defs['explorer_updates'] )
);

$field = isset( $defs['new_book_releases'] ) ? $defs['new_book_releases'] : array();

/* ===================================================================
 * §2 — the change this build made
 * =================================================================== */
bhp_op_head( '§2 the location moved to contact' );

bhp_op_ok(
	'§2.1 location is "contact"',
	isset( $field['location'] ) && 'contact' === $field['location'],
	'location=' . ( isset( $field['location'] ) ? $field['location'] : '[absent]' )
);

bhp_op_ok(
	'§2.2 location is NOT "address" (an address location would move the meta key)',
	! isset( $field['location'] ) || 'address' !== $field['location']
);

/* ===================================================================
 * §3 — the three things that must NOT have changed
 * =================================================================== */
bhp_op_head( '§3 what must not have moved' );

bhp_op_ok(
	'§3.1 the field id is unchanged',
	isset( $field['id'] ) && 'brave-hearts/new-book-releases' === $field['id'],
	isset( $field['id'] ) ? $field['id'] : '[absent]'
);

bhp_op_ok(
	'§3.2 the mirrored meta key is unchanged',
	isset( $field['meta'] ) && '_bhp_new_book_releases_optin' === $field['meta'],
	isset( $field['meta'] ) ? $field['meta'] : '[absent]'
);

bhp_op_ok(
	'§3.3 bhp_checkout_optin_meta_key() still resolves to that key',
	function_exists( 'bhp_checkout_optin_meta_key' )
		&& '_bhp_new_book_releases_optin' === bhp_checkout_optin_meta_key(),
	function_exists( 'bhp_checkout_optin_meta_key' ) ? bhp_checkout_optin_meta_key() : '[fn absent]'
);

$approved_label = 'Email me when a new Charlotte and Henry book or edition is released, plus the occasional family reading idea.';
bhp_op_ok(
	'§3.4 the approved label is byte-identical',
	isset( $field['label'] ) && $approved_label === $field['label'],
	isset( $field['label'] ) ? $field['label'] : '[absent]'
);

/*
 * ⛔ THE VOICE RULE, ASSERTED RATHER THAN EYEBALLED. Standing Rules §9.1:
 *    no company "we/us/our" in customer-facing words, and no em dash in
 *    customer-facing copy.
 */
$label_lc = isset( $field['label'] ) ? strtolower( $field['label'] ) : '';
bhp_op_ok(
	'§3.5 the label carries no company we/us/our',
	'' !== $label_lc
		&& ! preg_match( '/\b(we|we\'ll|we\'re|us|our|ours)\b/', $label_lc )
);
bhp_op_ok(
	'§3.6 the label carries no em dash',
	'' !== $label_lc && false === strpos( $field['label'], "\xE2\x80\x94" )
);

/* ===================================================================
 * §4 — what WooCommerce actually registered
 * =================================================================== */
bhp_op_head( '§4 the live WooCommerce registration' );

$registered = null;
if ( class_exists( '\Automattic\WooCommerce\Blocks\Package' ) ) {
	try {
		$cf = \Automattic\WooCommerce\Blocks\Package::container()
			->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
		$all = $cf->get_additional_fields();
		$registered = isset( $all['brave-hearts/new-book-releases'] ) ? $all['brave-hearts/new-book-releases'] : null;

		bhp_op_ok( '§4.1 the field is registered with WooCommerce', null !== $registered );
		bhp_op_ok(
			'§4.2 WooCommerce records its location as contact',
			null !== $registered && isset( $registered['location'] ) && 'contact' === $registered['location'],
			null !== $registered && isset( $registered['location'] ) ? $registered['location'] : '[absent]'
		);
		bhp_op_ok(
			'§4.3 it is type checkbox and NOT required',
			null !== $registered && 'checkbox' === $registered['type'] && empty( $registered['required'] )
		);
		bhp_op_ok(
			'§4.4 no default value is set, so it can never render pre-checked',
			null !== $registered
				&& ( ! array_key_exists( 'default', $registered ) || in_array( $registered['default'], array( null, '', false ), true ) )
		);

		/*
		 * ⭐ THE ASSERTION THIS WHOLE SUITE EXISTS FOR. Read the vendor's own
		 *    group key rather than trusting the note in `functions.php`.
		 */
		$prefix = \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::get_group_key( 'other' );
		bhp_op_ok(
			'§4.5 the "other" group prefix is _wc_other/ (so contact and order store the same key)',
			'_wc_other/' === $prefix,
			$prefix
		);

		$billing_prefix = \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::get_group_key( 'billing' );
		bhp_op_ok(
			'§4.6 the billing prefix differs, confirming only "address" would have moved the key',
			$billing_prefix !== $prefix,
			$billing_prefix
		);

		$contact_fields = $cf->get_fields_for_location( 'contact' );
		bhp_op_ok(
			'§4.7 the field appears in the CONTACT location bucket',
			is_array( $contact_fields ) && array_key_exists( 'brave-hearts/new-book-releases', $contact_fields ),
			is_array( $contact_fields ) ? implode( ',', array_keys( $contact_fields ) ) : '[not an array]'
		);

		$order_fields = $cf->get_fields_for_location( 'order' );
		bhp_op_ok(
			'§4.8 and NOT in the ORDER location bucket any more',
			is_array( $order_fields ) && ! array_key_exists( 'brave-hearts/new-book-releases', $order_fields ),
			is_array( $order_fields ) ? implode( ',', array_keys( $order_fields ) ) : '[not an array]'
		);
	} catch ( \Throwable $e ) {
		bhp_op_ok( '§4.0 CheckoutFields service reachable', false, substr( $e->getMessage(), 0, 160 ) );
	}
} else {
	echo "SKIP  §4 WooCommerce Blocks Package not available in this context\n";
}

/* ===================================================================
 * §5 — the wire built by CYCLE168 is still intact
 * =================================================================== */
bhp_op_head( '§5 the CYCLE168 wire is untouched' );

bhp_op_ok( '§5.1 bhp_checkout_optin_was_given() still exists', function_exists( 'bhp_checkout_optin_was_given' ) );
bhp_op_ok( '§5.2 bhp_checkout_optin_sync() still exists', function_exists( 'bhp_checkout_optin_sync' ) );
bhp_op_ok(
	'§5.3 the sync is still registered on PAYMENT, not on order creation',
	false !== has_action( 'woocommerce_payment_complete', 'bhp_checkout_optin_sync' )
		&& false === has_action( 'woocommerce_checkout_order_processed', 'bhp_checkout_optin_sync' )
		&& false === has_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_checkout_optin_sync' )
);

$tags = function_exists( 'bhp_get_checkout_optin_tags' ) ? bhp_get_checkout_optin_tags() : array();
bhp_op_ok(
	'§5.4 the purchase tags are unchanged and carry NO Adventure Club (funnel isolation)',
	in_array( 'Customer - Purchased', $tags, true )
		&& in_array( 'Source: Checkout', $tags, true )
		&& ! in_array( 'Adventure Club', $tags, true ),
	implode( ',', $tags )
);

/* =================================================================== */
echo "\n";
echo "PASS {$GLOBALS['bhp_op_pass']}  FAIL {$GLOBALS['bhp_op_fail']}\n";
if ( $GLOBALS['bhp_op_fail'] > 0 ) {
	exit( 1 );
}
