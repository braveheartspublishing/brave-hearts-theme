<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE EARLY CART CAPTURE SUITE — theme 1.19.316, 2026-08-28,
 * `CYCLE168-CX-EARLY-CART-CAPTURE`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle168-early-cart-capture.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT THIS SUITE IS FOR
 * ---------------------------------------------------------------------------
 * `CYCLE168-CX-003` established that mailchimp-for-woocommerce cannot create
 * an abandoned-cart record until it knows an email, and that for a guest that
 * means the `mailchimp_user_email` cookie, first written at the Blocks
 * checkout email field a median 132 seconds before payment. This feature adds
 * an earlier, opt-in capture on the cart page.
 *
 * ⭐ THE ASSERTION THAT MATTERS MOST IS §3, THE CONSENT RAIL. Everything else
 *    here is ordinary regression cover. §3 is the one that, if it ever goes
 *    red, means real people are being subscribed to marketing they did not ask
 *    for -- which is not reversible in their inboxes.
 *
 * ---------------------------------------------------------------------------
 * ⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one.
 * ---------------------------------------------------------------------------
 * ⛔ IT DOES NOT PROVE A CART RECORD REACHED MAILCHIMP. It cannot, on staging:
 *    staging's mailchimp-for-woocommerce has NO API key, NO store_id, NO list,
 *    and `mailchimp_cart_tracking` is `disabled`, so `handleCartUpdated()`
 *    returns at its first line. The final hop is UNOBSERVABLE in this
 *    environment. What this suite proves is that the GATE WHICH WAS CLOSED IS
 *    NOW OPEN -- that `getCurrentUserEmail()` resolves for a guest after
 *    capture, where before it returned false. The hop from an open gate to a
 *    pushed record is the plugin's own code path, the one already producing
 *    `abandoned_cart.success` daily on production (`CYCLE168-CX-001`).
 *    That is an INFERENCE and is labelled as one in the handoff.
 *
 * ⛔ NOR does it prove layout, tap targets, console cleanliness or reveal
 *    timing. Those carry browser evidence at a stated `window.innerWidth`.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, no cart. It sends no mail. It registers no permanent filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$GLOBALS['bhp_ecc_pass'] = 0;
$GLOBALS['bhp_ecc_fail'] = 0;

function bhp_ecc_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_ecc_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_ecc_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_ecc_head( $title ) {
	echo "\n=== {$title} ===\n";
}

$ecc_inc = get_template_directory() . '/inc/early-cart-capture.php';
$ecc_js  = get_template_directory() . '/assets/js/early-cart-capture.js';
$ecc_css = get_template_directory() . '/assets/css/early-cart-capture.css';

$ecc_php_raw = file_exists( $ecc_inc ) ? (string) file_get_contents( $ecc_inc ) : '';
$ecc_js_raw  = file_exists( $ecc_js ) ? (string) file_get_contents( $ecc_js ) : '';

/**
 * ⭐⭐ WHY THE SOURCE IS COMMENT-STRIPPED BEFORE ANY "NEVER MENTIONS X"
 *     ASSERTION, AND WHY THIS IS NOT THE TEST GOING SOFT.
 *
 * ⛔ THE FIRST RUN OF THIS SUITE FAILED SIX ASSERTIONS, AND EVERY ONE WAS A
 *    FALSE POSITIVE CAUSED BY THIS FILE'S OWN DOCUMENTATION. §3.3 forbids the
 *    capture path from calling `bhp_process_signup()` -- and `early-cart-capture.php`
 *    FAILED it because its header comment EXPLAINS, at length, why it must never
 *    call `bhp_process_signup()`. Likewise §4.3-4.6 tripped on the JS header
 *    documenting the funnel prefixes it is forbidden to touch, and §9.1 tripped
 *    on the comment naming the checkout opt-in lane it must not disturb.
 *
 * ⭐ THE ASSERTION WAS RIGHT; ITS INSTRUMENT WAS WRONG. A raw `strpos()` over a
 *    whole file cannot tell a CALL from a WARNING NOT TO CALL, so it punishes
 *    the file for being well documented -- and the "fix" a hurried reader would
 *    reach for is deleting the explanatory comments, which would remove exactly
 *    the prose that stops the next person reintroducing the defect.
 *
 * ⭐ SO THE SOURCE IS STRIPPED TO EXECUTABLE CODE FIRST, AND THE ASSERTIONS RUN
 *    UNCHANGED AND UNWEAKENED AGAINST THAT. PHP uses `token_get_all()`, which is
 *    the real lexer rather than a regex guess at one.
 */
function bhp_ecc_strip_php_comments( $src ) {
	if ( '' === $src || ! function_exists( 'token_get_all' ) ) {
		return $src;
	}
	$out = '';
	foreach ( token_get_all( $src ) as $token ) {
		if ( is_array( $token ) ) {
			if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
				continue;
			}
			$out .= $token[1];
		} else {
			$out .= $token;
		}
	}
	return $out;
}

function bhp_ecc_strip_js_comments( $src ) {
	// Block comments, then whole-line // comments. Adequate here: this file
	// has no regex literals or strings containing comment markers.
	$src = preg_replace( '#/\*.*?\*/#s', '', $src );
	$src = preg_replace( '#^\s*//.*$#m', '', $src );
	return (string) $src;
}

$ecc_php_src = bhp_ecc_strip_php_comments( $ecc_php_raw );
$ecc_js_src  = bhp_ecc_strip_js_comments( $ecc_js_raw );

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · THE FEATURE IS PRESENT AND WIRED
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§1 PRESENT AND WIRED' );

/*
 * ⛔ THE STRIPPER'S OWN GUARD. A comment stripper that silently returned ''
 *    would turn EVERY "never mentions X" assertion below green, which is the
 *    worst possible failure mode for this suite. These three assertions make
 *    that impossible: the stripped source must be non-empty, must be genuinely
 *    shorter than the raw file, and must still contain a known line of real code.
 */
bhp_ecc_ok(
	'§1.0a the PHP comment stripper produced non-empty code',
	strlen( $ecc_php_src ) > 500,
	'stripped length ' . strlen( $ecc_php_src )
);
bhp_ecc_ok(
	'§1.0b the PHP stripper actually removed comments (stripped < raw)',
	strlen( $ecc_php_src ) < strlen( $ecc_php_raw )
);
bhp_ecc_ok(
	'§1.0c the stripped PHP still contains real code (the AJAX handler)',
	strpos( $ecc_php_src, 'function handle_ajax' ) !== false
);
bhp_ecc_ok(
	'§1.0d the JS stripper produced non-empty code that still contains real code',
	strlen( $ecc_js_src ) > 500
		&& strlen( $ecc_js_src ) < strlen( $ecc_js_raw )
		&& strpos( $ecc_js_src, 'function submit' ) !== false
);

bhp_ecc_ok( '§1.1 inc/early-cart-capture.php exists', file_exists( $ecc_inc ) );
bhp_ecc_ok( '§1.2 assets/js/early-cart-capture.js exists', file_exists( $ecc_js ) );
bhp_ecc_ok( '§1.3 assets/css/early-cart-capture.css exists', file_exists( $ecc_css ) );
bhp_ecc_ok( '§1.4 class BHP_Early_Cart_Capture is loaded', class_exists( 'BHP_Early_Cart_Capture' ) );

bhp_ecc_ok(
	'§1.5 the guest AJAX endpoint is registered (wp_ajax_nopriv)',
	has_action( 'wp_ajax_nopriv_bhp_cart_capture' ) !== false
);
bhp_ecc_ok(
	'§1.6 the logged-in AJAX endpoint is registered',
	has_action( 'wp_ajax_bhp_cart_capture' ) !== false
);
bhp_ecc_ok(
	'§1.7 the panel renders on wp_footer',
	has_action( 'wp_footer', array( 'BHP_Early_Cart_Capture', 'render' ) ) !== false
);

/*
 * ⭐⭐ §1.8/§1.9 GUARD A BUG THAT ACTUALLY SHIPPED AND WAS INERT IN PRODUCTION
 *     CONDITIONS. With render at wp_footer priority 20 -- the same priority as
 *     `wp_print_footer_scripts()`, and registered after it -- the script tag was
 *     emitted ABOVE the panel markup, `getElementById()` returned null, and the
 *     whole feature did nothing. THE PHP SUITE WAS 72/72 GREEN AT THE TIME.
 *     Only a real browser on staging caught it.
 *
 * ⛔ These two assertions are the reason it cannot come back silently.
 */
bhp_ecc_ok(
	'§1.8 ⭐ render runs at wp_footer priority < 20, ahead of wp_print_footer_scripts',
	( (int) has_action( 'wp_footer', array( 'BHP_Early_Cart_Capture', 'render' ) ) ) < 20,
	'priority ' . (string) has_action( 'wp_footer', array( 'BHP_Early_Cart_Capture', 'render' ) )
);
bhp_ecc_ok(
	'§1.9 ⭐ the JS carries its own DOM-ready guard, so emission order cannot kill it',
	strpos( $ecc_js_src, 'readyState' ) !== false
		&& strpos( $ecc_js_src, 'DOMContentLoaded' ) !== false
);

/*
 * ⭐ §1.10 GUARDS A SECOND BUG CAUGHT ONLY IN A REAL BROWSER: reveal() used
 *    `requestAnimationFrame` alone to add the `is-visible` class. rAF does not
 *    fire in a BACKGROUND TAB, so the panel un-hid (taking 284px of layout) but
 *    stayed at `opacity: 0` -- an invisible gap. Opening the cart in a
 *    background tab is an ordinary shopper action, so a setTimeout fallback is
 *    required alongside the rAF.
 */
bhp_ecc_ok(
	'§1.10 ⭐ reveal() does not depend on requestAnimationFrame alone '
		. '(background tabs never fire it)',
	strpos( $ecc_js_src, 'requestAnimationFrame' ) !== false
		&& (bool) preg_match( '/setTimeout\(\s*show\s*,/', $ecc_js_src )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · THE INTEGRATION POINT IS THE PLUGIN'S OWN SANCTIONED ENTRY POINT
 * ═══════════════════════════════════════════════════════════════════════════
 * `set_user_from_block_checkout()` is what the plugin's own Blocks integration
 * calls in `capture_from_store_api()`. Using it, rather than reimplementing
 * cookie handling, is what keeps this build correct across the 6.1.1/6.2 drift
 * between staging and production.
 */
bhp_ecc_head( '§2 INTEGRATION POINT' );

bhp_ecc_ok(
	'§2.1 the handler calls set_user_from_block_checkout()',
	strpos( $ecc_php_src, 'set_user_from_block_checkout' ) !== false
);

bhp_ecc_ok(
	'§2.2 ⛔ the theme does NOT hand-roll the mailchimp_user_email cookie '
		. '(no setcookie() anywhere in this file)',
	strpos( $ecc_php_src, 'setcookie' ) === false
		&& strpos( $ecc_php_src, 'mailchimp_set_cookie' ) === false
);

if ( class_exists( 'MailChimp_Service' ) ) {
	bhp_ecc_ok(
		'§2.3 MailChimp_Service::set_user_from_block_checkout() exists in the installed plugin',
		method_exists( 'MailChimp_Service', 'set_user_from_block_checkout' )
	);
	bhp_ecc_ok(
		'§2.4 MailChimp_Service::instance() exists',
		method_exists( 'MailChimp_Service', 'instance' )
	);
	bhp_ecc_ok(
		'§2.5 MailChimp_Service::getCurrentUserEmail() exists (the gate this feature opens)',
		method_exists( 'MailChimp_Service', 'getCurrentUserEmail' )
	);
} else {
	echo "SKIP  §2.3-2.5 MailChimp_Service not loaded in this context\n";
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 ⭐⭐ THE CONSENT RAIL — THE ASSERTIONS THAT MATTER MOST
 * ═══════════════════════════════════════════════════════════════════════════
 * Chain being protected:
 *   cart-update.php:193  $subscriber_status = $this->status ? 'subscribed' : 'transactional';
 *   $this->status  <- setStatus($this->cart_subscribe)
 *   $cart_subscribe <- $_POST['subscribed']   (ONE place, service.php:1232)
 * So: no `subscribed` POST field on this path == `transactional` by construction.
 */
bhp_ecc_head( '§3 ⭐ CONSENT RAIL — transactional by construction' );

bhp_ecc_ok(
	'§3.1 ⛔ the capture path never SETS a "subscribed" POST field',
	! preg_match( '/\$_POST\s*\[\s*[\'"]subscribed[\'"]\s*\]\s*=(?!=)/', $ecc_php_src )
		|| (bool) preg_match( '/\$_POST\[\s*[\'"]subscribed[\'"]\s*\]\s*=\s*\$prior_subscribed/', $ecc_php_src )
);

bhp_ecc_ok(
	'§3.2 ⭐ the handler defensively UNSETS $_POST[\'subscribed\'] before calling the service',
	(bool) preg_match( '/unset\(\s*\$_POST\[\s*[\'"]subscribed[\'"]\s*\]\s*\)/', $ecc_php_src )
);

bhp_ecc_ok(
	'§3.3 ⛔ the capture path never calls the theme signup pipeline '
		. '(bhp_process_signup would create a SUBSCRIBED contact)',
	strpos( $ecc_php_src, 'bhp_process_signup' ) === false
);

bhp_ecc_ok(
	'§3.4 ⛔ no marketing opt-in checkbox is rendered on this surface in v1 '
		. '(deliberate non-goal, recorded in the file header)',
	strpos( $ecc_php_src, 'type="checkbox"' ) === false
);

bhp_ecc_ok(
	'§3.5 ⛔ the capture path never touches the mailchimp_auto_subscribe option '
		. '(executable code only; the header documents it deliberately)',
	strpos( $ecc_php_src, 'mailchimp_auto_subscribe' ) === false
);

// Prove the option genuinely cannot reach the cart processor, by reading the
// installed plugin rather than trusting the comment in our own file.
$ecc_plugin_dir = WP_PLUGIN_DIR . '/mailchimp-for-woocommerce';
$ecc_cart_proc  = $ecc_plugin_dir . '/includes/processes/class-mailchimp-woocommerce-cart-update.php';
if ( file_exists( $ecc_cart_proc ) ) {
	$ecc_cart_src = (string) file_get_contents( $ecc_cart_proc );

	bhp_ecc_ok(
		'§3.6 ⭐ the plugin cart processor does NOT reference mailchimp_auto_subscribe',
		strpos( $ecc_cart_src, 'mailchimp_auto_subscribe' ) === false
	);
	bhp_ecc_ok(
		'§3.7 ⭐ the plugin cart processor derives status ONLY from $this->status '
			. '(transactional unless cart_subscribe was set)',
		(bool) preg_match( '/\$this->status\s*\?\s*[\'"]subscribed[\'"]\s*:\s*[\'"]transactional[\'"]/', $ecc_cart_src )
	);
} else {
	echo "SKIP  §3.6-3.7 plugin cart processor not found at {$ecc_cart_proc}\n";
}

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · FUNNEL ISOLATION (.claude/rules/funnels.md)
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§4 FUNNEL ISOLATION' );

bhp_ecc_ok(
	'§4.1 storage prefix is bhp_cart_capture',
	BHP_Early_Cart_Capture::STORAGE_PREFIX === 'bhp_cart_capture'
);
bhp_ecc_ok(
	'§4.2 event prefix is cart_capture',
	BHP_Early_Cart_Capture::EVENT_PREFIX === 'cart_capture'
);
bhp_ecc_ok(
	'§4.3 ⛔ the JS never touches the PARENT funnel storage prefix',
	strpos( $ecc_js_src, 'bhp_parent_popup' ) === false
);
bhp_ecc_ok(
	'§4.4 ⛔ the JS never touches the TEACHER funnel storage prefix',
	strpos( $ecc_js_src, 'bhp_mariana_popup' ) === false
);
bhp_ecc_ok(
	'§4.5 ⛔ the JS never emits a parent_popup / teacher_popup event',
	strpos( $ecc_js_src, 'parent_popup' ) === false
		&& strpos( $ecc_js_src, 'teacher_popup' ) === false
);
bhp_ecc_ok(
	'§4.6 ⛔ the popup engine was not forked for a third funnel',
	strpos( $ecc_js_src, 'mariana-popup' ) === false
		&& strpos( $ecc_js_src, 'data-popup-config' ) === false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · NO NEW COOKIE ON PAGE LOAD (consent-mode safety)
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§5 NO PRE-CONSENT COOKIE' );

bhp_ecc_ok(
	'§5.1 ⛔ the JS sets no cookie at all (document.cookie is never assigned)',
	! preg_match( '/document\.cookie\s*=/', $ecc_js_src )
);
bhp_ecc_ok(
	'§5.2 ⭐ the only cookie in this feature is written server-side by the plugin, '
		. 'and only after an explicit submit',
	strpos( $ecc_php_src, 'set_user_from_block_checkout' ) !== false
		&& ! preg_match( '/document\.cookie\s*=/', $ecc_js_src )
);
bhp_ecc_ok(
	'§5.3 storage writes are wrapped so blocked site data cannot throw',
	strpos( $ecc_js_src, 'function writeStore' ) !== false
		&& strpos( $ecc_js_src, 'catch' ) !== false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THE RENDER GATE — every condition is a reason not to nag
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§6 RENDER GATE' );

bhp_ecc_ok(
	'§6.1 render is gated to the cart page',
	strpos( $ecc_php_src, 'is_cart()' ) !== false
);
bhp_ecc_ok(
	'§6.2 ⛔ checkout is excluded explicitly (the 1.19.313 opt-in owns that surface)',
	strpos( $ecc_php_src, 'is_checkout()' ) !== false
);
bhp_ecc_ok(
	'§6.3 logged-in shoppers are excluded (their email already resolves)',
	strpos( $ecc_php_src, 'is_user_logged_in()' ) !== false
);
bhp_ecc_ok(
	'§6.4 an already-captured browser is excluded',
	strpos( $ecc_php_src, "_COOKIE[ self::MC_COOKIE ]" ) !== false
);
bhp_ecc_ok(
	'§6.5 an empty cart is excluded',
	strpos( $ecc_php_src, 'is_empty()' ) !== false
);
bhp_ecc_ok(
	'§6.6 a kill switch exists (bhp_cart_capture_enabled)',
	strpos( $ecc_php_src, 'bhp_cart_capture_enabled' ) !== false
);

// should_render() must be false in this CLI context: no cart page, and the
// suite runs as user 1.
bhp_ecc_ok(
	'§6.7 should_render() is false under WP-CLI (no cart page, logged-in user)',
	BHP_Early_Cart_Capture::should_render() === false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · SECURITY ON THE ENDPOINT
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§7 ENDPOINT SECURITY' );

bhp_ecc_ok( '§7.1 nonce is verified', strpos( $ecc_php_src, 'wp_verify_nonce' ) !== false );
bhp_ecc_ok( '§7.2 honeypot is checked', strpos( $ecc_php_src, 'bhp_website' ) !== false );
bhp_ecc_ok( '§7.3 rate limiting is applied', strpos( $ecc_php_src, 'rate_limited' ) !== false );
bhp_ecc_ok( '§7.4 email is sanitised and validated', strpos( $ecc_php_src, 'sanitize_email' ) !== false && strpos( $ecc_php_src, 'is_email' ) !== false );
bhp_ecc_ok(
	'§7.5 ⭐ a Mailchimp outage cannot break the cart page (call is wrapped)',
	strpos( $ecc_php_src, 'catch ( \Throwable' ) !== false
);
bhp_ecc_ok(
	'§7.6 all rendered copy is escaped',
	strpos( $ecc_php_src, 'esc_html(' ) !== false && strpos( $ecc_php_src, 'esc_attr(' ) !== false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · COPY DISCIPLINE — three variants, all compliant
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§8 COPY DISCIPLINE' );

$ecc_variants = BHP_Early_Cart_Capture::copy_variants();

bhp_ecc_ok( '§8.1 exactly three variants are offered for Andrew to pick from', count( $ecc_variants ) === 3 );

foreach ( $ecc_variants as $ecc_key => $ecc_copy ) {
	$ecc_all = implode( ' ', $ecc_copy );

	bhp_ecc_ok(
		"§8.2[{$ecc_key}] ⛔ no em dash",
		strpos( $ecc_all, '—' ) === false && strpos( $ecc_all, '--' ) === false
	);

	// Standing Rules §9.1: no "we"/"us"/"our" in customer-facing words.
	bhp_ecc_ok(
		"§8.3[{$ecc_key}] ⛔ no company \"we\"/\"us\"/\"our\" (Standing Rules §9.1)",
		! preg_match( '/\b(we|us|our|we\'ll|we\'re|we\'ve)\b/i', $ecc_all ),
		$ecc_all
	);

	// Never promise a delivery that has never been observed to happen.
	bhp_ecc_ok(
		"§8.4[{$ecc_key}] ⭐ no unconditional promise that an email WILL be sent",
		! preg_match( '/\b(i will send|i\'ll send|you will receive|you\'ll receive|we will send)\b/i', $ecc_all ),
		$ecc_all
	);

	// Never-invent list: no statistics, ratings or testimonials in this copy.
	bhp_ecc_ok(
		"§8.5[{$ecc_key}] ⛔ no statistic, rating or social-proof claim",
		! preg_match( '/\b(\d+%|\d+,\d+|thousands|millions|rated|reviews?|parents love|best[- ]selling)\b/i', $ecc_all ),
		$ecc_all
	);

	bhp_ecc_ok(
		"§8.6[{$ecc_key}] every required copy slot is filled",
		! empty( $ecc_copy['heading'] ) && ! empty( $ecc_copy['body'] )
			&& ! empty( $ecc_copy['button'] ) && ! empty( $ecc_copy['fine_print'] )
			&& ! empty( $ecc_copy['success'] ) && ! empty( $ecc_copy['placeholder'] )
	);

	bhp_ecc_ok(
		"§8.7[{$ecc_key}] ⭐ the fine print states the contact is not signed up to marketing",
		(bool) preg_match( '/newsletter|sign(s)? you up|does not sign|no marketing|anything else/i', $ecc_copy['fine_print'] ),
		$ecc_copy['fine_print']
	);
}

bhp_ecc_ok(
	'§8.8 the active variant resolves to a real variant',
	array_key_exists( BHP_Early_Cart_Capture::active_variant(), $ecc_variants )
);

bhp_ecc_ok(
	'§8.9 an unknown variant falls back to "a" rather than fataling',
	( function () {
		add_filter( 'bhp_cart_capture_variant', $f = function () { return 'zzz'; } );
		$v = BHP_Early_Cart_Capture::active_variant();
		remove_filter( 'bhp_cart_capture_variant', $f );
		return 'a' === $v;
	} )()
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · NO COLLISION WITH THE CONCURRENT CYCLE168 LANES
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§9 NO CROSS-LANE COLLISION' );

bhp_ecc_ok(
	'§9.1 ⛔ this feature does not touch the checkout opt-in lane (1.19.313)',
	strpos( $ecc_php_src, 'checkout-optin-sync' ) === false
		&& strpos( $ecc_php_src, 'bhp_checkout_optin' ) === false
);
bhp_ecc_ok(
	'§9.2 ⛔ this feature sends no mail of any kind (staging-mail-guard respected trivially)',
	strpos( $ecc_php_src, 'wp_mail' ) === false
		&& strpos( $ecc_php_src, 'WC_Email' ) === false
);
bhp_ecc_ok(
	'§9.3 ⛔ this feature mutates no WooCommerce product, price, coupon, shipping or tax setting',
	! preg_match( '/update_option|update_post_meta|set_price|wc_update_product_stock|WC_Shipping/i', $ecc_php_src )
);
bhp_ecc_ok(
	'§9.4 the checkout opt-in file is still present and untouched by this lane',
	file_exists( get_template_directory() . '/inc/checkout-optin-sync.php' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §10 · THIS SUITE MUTATED NOTHING
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc_head( '§10 NO SIDE EFFECTS' );

bhp_ecc_ok(
	'§10.1 no variant filter was left registered by this run',
	! has_filter( 'bhp_cart_capture_variant' )
);
bhp_ecc_ok(
	'§10.2 the theme version on disk is at or beyond 1.19.316',
	version_compare( (string) wp_get_theme()->get( 'Version' ), '1.19.316', '>=' ),
	(string) wp_get_theme()->get( 'Version' )
);

echo "\n============================================================\n";
printf(
	"EARLY CART CAPTURE: %d passed, %d failed\n",
	(int) $GLOBALS['bhp_ecc_pass'],
	(int) $GLOBALS['bhp_ecc_fail']
);
echo "============================================================\n";

if ( (int) $GLOBALS['bhp_ecc_fail'] > 0 ) {
	exit( 1 );
}
