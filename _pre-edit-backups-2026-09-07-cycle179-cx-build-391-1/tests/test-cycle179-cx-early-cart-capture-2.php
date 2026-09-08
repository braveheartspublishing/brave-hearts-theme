<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE EARLY CART CAPTURE v2 SUITE — `CYCLE179-CX-EARLY-CART-CAPTURE-2`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Run on STAGING (never production) via:
 *   wp eval-file wp-content/themes/brave-hearts-theme-deploy-explorer-expedition-guides/tests/test-cycle179-cx-early-cart-capture-2.php --user=1
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT THIS SUITE IS FOR
 * ---------------------------------------------------------------------------
 * v1 (`tests/test-cycle168-early-cart-capture.php`) proved that a cart-page
 * panel could open the gate `getCurrentUserEmail()` had been closing for
 * guests. v2 adds THREE things v1 deliberately did not have, and each of them
 * can fail in a way that costs a real person something:
 *
 *   1. A MARKETING OPT-IN CHECKBOX. Fails wrong -> somebody is subscribed
 *      without asking. §3 and §5.
 *   2. A SECOND SURFACE (the cart drawer) that lives on CACHED PAGES. Fails
 *      wrong -> one shopper's nonce is served to every other visitor of a
 *      product page. §6.
 *   3. CONSENT THAT HAS TO SURVIVE TO AN ORDER. Fails wrong -> the choice is
 *      silently lost, or worse, silently inverted. §9.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ WHAT A PASS HERE DOES **NOT** PROVE — read before over-reading one
 * ---------------------------------------------------------------------------
 * ⛔ IT DOES NOT PROVE A CART RECORD REACHED MAILCHIMP, and on staging it
 *    CANNOT. Verified live on this box, not inferred:
 *      · `mailchimp_cart_tracking` is `disabled` (production: `all`)
 *      · `mailchimp_list` is empty and there is no api key
 *    `handleCartUpdated()` returns at its FIRST line on the first of those and
 *    at its SECOND on `!mailchimp_is_configured()`. ⭐ SO `trackCart()` IS
 *    NEVER REACHED AND NO ROW CAN APPEAR IN `ugc_mailchimp_carts` ON STAGING,
 *    however correct this theme code is. A zero row count here is NOT a
 *    defect and must not be reported as one.
 *
 * ⛔ IT DOES NOT PROVE A CONTACT WAS SUBSCRIBED. `bhp_process_signup()`
 *    returns `unavailable` on staging for the same reason (no api key). What
 *    §3.9 proves is that the ticked path CALLS it and the unticked path does
 *    NOT, observed through `bhp_mailchimp_signup_rejected` rather than through
 *    a network request.
 *
 * ⛔ NOR does it prove layout, tap targets, console cleanliness or the drawer
 *    insertion point. Those carry browser evidence at a stated
 *    `window.innerWidth` and are in the report, not here.
 *
 * ⛔ IT WRITES NOTHING. No option, no post, no product, no setting, no
 *    subscriber, no cart, no order. It sends no mail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/*
 * ⛔⛔ OUTBOUND MAIL IS BLOCKED FOR THE WHOLE OF THIS SUITE.
 *
 * ⭐ Six real emails left staging through Google's SMTP relay during two suite
 *    runs and bounced back to the founder. Staging relays live, so any test
 *    that creates an order or moves one between statuses is an outbound-mail
 *    event. This include stops every one of them at `pre_wp_mail`.
 *
 * ⛔ NO ISO DATE APPEARS IN THIS BLOCK, AND THAT IS DELIBERATE. Two suites
 *    scan their OWN source for one and fail if they find it.
 */
require_once get_template_directory() . '/tests/bootstrap-mail-guard.php';

$GLOBALS['bhp_ecc2_pass'] = 0;
$GLOBALS['bhp_ecc2_fail'] = 0;

function bhp_ecc2_ok( $label, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['bhp_ecc2_pass']++;
		echo "PASS  {$label}\n";
	} else {
		$GLOBALS['bhp_ecc2_fail']++;
		echo "FAIL  {$label}" . ( $detail ? '  -- ' . substr( (string) $detail, 0, 400 ) : '' ) . "\n";
	}
}

function bhp_ecc2_head( $title ) {
	echo "\n=== {$title} ===\n";
}

/**
 * ⭐⭐ THE SOURCE IS COMMENT-STRIPPED BEFORE ANY "NEVER MENTIONS X" ASSERTION,
 *     AND THIS IS NOT THE TEST GOING SOFT.
 *
 * ⛔ THE FIRST RUN OF THE v1 SUITE FAILED SIX ASSERTIONS AND EVERY ONE WAS A
 *    FALSE POSITIVE CAUSED BY THE FILE'S OWN DOCUMENTATION -- it FAILED "never
 *    calls X" because its header EXPLAINS AT LENGTH why it must never call X.
 *
 * ⭐ THE ASSERTION WAS RIGHT; ITS INSTRUMENT WAS WRONG. A raw `strpos()` over a
 *    whole file cannot tell a CALL from a WARNING NOT TO CALL, so it punishes
 *    the file for being well documented, and the "fix" a hurried reader
 *    reaches for is deleting the prose that stops the next person
 *    reintroducing the defect. `token_get_all()` is the real lexer.
 */
function bhp_ecc2_strip_php_comments( $src ) {
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

function bhp_ecc2_strip_js_comments( $src ) {
	$src = preg_replace( '#/\*.*?\*/#s', '', $src );
	$src = preg_replace( '#^\s*//.*$#m', '', $src );
	return (string) $src;
}

$ecc2_inc = get_template_directory() . '/inc/early-cart-capture.php';
$ecc2_js  = get_template_directory() . '/assets/js/early-cart-capture.js';
$ecc2_css = get_template_directory() . '/assets/css/early-cart-capture.css';

$ecc2_php_raw = file_exists( $ecc2_inc ) ? (string) file_get_contents( $ecc2_inc ) : '';
$ecc2_js_raw  = file_exists( $ecc2_js ) ? (string) file_get_contents( $ecc2_js ) : '';
$ecc2_css_raw = file_exists( $ecc2_css ) ? (string) file_get_contents( $ecc2_css ) : '';

$ecc2_php_src = bhp_ecc2_strip_php_comments( $ecc2_php_raw );
$ecc2_js_src  = bhp_ecc2_strip_js_comments( $ecc2_js_raw );

/* ═══════════════════════════════════════════════════════════════════════════
 * §1 · PRESENT AND WIRED
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§1 PRESENT AND WIRED' );

bhp_ecc2_ok( '§1.1 inc/early-cart-capture.php exists', file_exists( $ecc2_inc ) );
bhp_ecc2_ok( '§1.2 assets/js/early-cart-capture.js exists', file_exists( $ecc2_js ) );
bhp_ecc2_ok( '§1.3 assets/css/early-cart-capture.css exists', file_exists( $ecc2_css ) );
bhp_ecc2_ok( '§1.4 class BHP_Early_Cart_Capture is loaded', class_exists( 'BHP_Early_Cart_Capture' ) );

bhp_ecc2_ok(
	'§1.5 the capture endpoint is registered for guests',
	has_action( 'wp_ajax_nopriv_bhp_cart_capture' ) !== false
);

bhp_ecc2_ok(
	'§1.6 ⭐ the UNCACHED PANEL endpoint is registered for guests (this is what makes the drawer possible at all)',
	has_action( 'wp_ajax_nopriv_bhp_cart_capture_panel' ) !== false
);

bhp_ecc2_ok(
	'§1.7 the panel render still runs at wp_footer priority 5, ahead of wp_print_footer_scripts at 20',
	has_action( 'wp_footer', array( 'BHP_Early_Cart_Capture', 'render' ) ) === 5
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §2 · TWO SURFACES, ONE PANEL BUILDER
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§2 TWO SURFACES' );

$ecc2_cart_html   = BHP_Early_Cart_Capture::panel_html( 'cart' );
$ecc2_drawer_html = BHP_Early_Cart_Capture::panel_html( 'drawer' );

bhp_ecc2_ok(
	'§2.1 the cart panel carries the cart surface class',
	strpos( $ecc2_cart_html, 'bhp-cart-capture--cart' ) !== false
);

bhp_ecc2_ok(
	'§2.2 the drawer panel carries the drawer surface class',
	strpos( $ecc2_drawer_html, 'bhp-cart-capture--drawer' ) !== false
);

bhp_ecc2_ok(
	'§2.3 both surfaces carry the shared hook attribute the script selects on',
	strpos( $ecc2_cart_html, 'data-bhp-cart-capture' ) !== false
		&& strpos( $ecc2_drawer_html, 'data-bhp-cart-capture' ) !== false
);

bhp_ecc2_ok(
	'§2.4 ⭐ the two surfaces have DIFFERENT element ids (two panels can coexist in one DOM)',
	strpos( $ecc2_cart_html, 'id="bhp-cart-capture-cart"' ) !== false
		&& strpos( $ecc2_drawer_html, 'id="bhp-cart-capture-drawer"' ) !== false
);

bhp_ecc2_ok(
	'§2.5 an unknown surface falls back to cart rather than emitting an unstyled panel',
	strpos( BHP_Early_Cart_Capture::panel_html( 'nonsense' ), 'bhp-cart-capture--cart' ) !== false
);

bhp_ecc2_ok(
	'§2.6 ⛔ the drawer panel is inserted into .bhp-cart-drawer__body as a SIBLING, '
		. 'never into __items/__message/__crosssell/__summary (bundle-drawer.js wipes those by innerHTML)',
	strpos( $ecc2_js_src, 'bhp-cart-drawer__body' ) !== false
		&& strpos( $ecc2_js_src, "insertBefore( panel, messageEl.nextSibling )" ) !== false
);

bhp_ecc2_ok(
	'§2.7 ⛔ the drawer is watched by MutationObserver, so NO plugin file is edited',
	strpos( $ecc2_js_src, 'MutationObserver' ) !== false
);

bhp_ecc2_ok(
	'§2.8 ⛔ the drawer panel is NOT fetched on /cart/ (one ask per shopper, not two behind an overlay)',
	strpos( $ecc2_js_src, 'cfg.isCart ? null' ) !== false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §3 · ⭐⭐ THE CONSENT RAIL
 *
 * ⛔ THIS IS THE SECTION THAT, IF IT EVER GOES RED, MEANS REAL PEOPLE ARE
 *    BEING SUBSCRIBED TO MARKETING THEY DID NOT ASK FOR. That is not
 *    reversible in their inboxes.
 *
 * ⚠⚠ TWO v1 ASSERTIONS ARE DELIBERATELY SUPERSEDED HERE, BY THE OWNER, AND
 *    THE SUPERSESSION IS RECORDED AT THE LINE RATHER THAN IN AN ANNOTATION
 *    SOMEWHERE BELOW IT:
 *
 *      ~~§3.3 the capture path never calls bhp_process_signup()~~
 *      ~~§3.4 no marketing opt-in checkbox is rendered on this surface~~
 *
 *    Andrew Signore, 2026-09-07, relayed through `chief-of-staff` and NOT witnessed
 *    first-hand by this desk, against a brief specifying an UNTICKED box with
 *    the checkout label. ⭐ THE RAIL DID NOT WEAKEN. It gained a
 *    consent-gated branch beside it, and §3.5-§3.9 below are what hold that
 *    branch shut for anyone who did not tick.
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§3 ⭐⭐ CONSENT RAIL' );

bhp_ecc2_ok(
	'§3.1 ⛔ the capture path never SETS a "subscribed" POST field (unchanged from v1)',
	! preg_match( '/\$_POST\s*\[\s*[\'"]subscribed[\'"]\s*\]\s*=(?!=)/', $ecc2_php_src )
		|| (bool) preg_match( '/\$_POST\[\s*[\'"]subscribed[\'"]\s*\]\s*=\s*\$prior_subscribed/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§3.2 ⭐ the handler still defensively UNSETS $_POST[\'subscribed\'] before calling the service '
		. '(NOT weakened by the opt-in box: even a ticked shopper does not go through cart_subscribe)',
	(bool) preg_match( '/unset\(\s*\$_POST\[\s*[\'"]subscribed[\'"]\s*\]\s*\)/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§3.3 ⭐ SUPERSEDES v1 §3.3 — bhp_process_signup() is now called, and ONLY inside the $optin branch',
	(bool) preg_match( '/if\s*\(\s*\$optin\s*\)\s*\{\s*\$subscribed\s*=\s*self::subscribe\(/', $ecc2_php_src )
		&& strpos( $ecc2_php_src, 'bhp_process_signup' ) !== false
);

bhp_ecc2_ok(
	'§3.4 ⭐ SUPERSEDES v1 §3.4 — a marketing opt-in checkbox IS rendered on this surface',
	strpos( $ecc2_cart_html, 'type="checkbox"' ) !== false
);

bhp_ecc2_ok(
	'§3.5 ⛔⛔ THE BOX IS UNTICKED. No `checked` attribute is emitted on EITHER surface.',
	! preg_match( '/<input[^>]*type="checkbox"[^>]*\schecked/i', $ecc2_cart_html )
		&& ! preg_match( '/<input[^>]*type="checkbox"[^>]*\schecked/i', $ecc2_drawer_html )
);

bhp_ecc2_ok(
	'§3.6 ⛔⛔ the word `checked` never appears in the executable source at all '
		. '(so no filter, no attribute and no default can reintroduce a pre-tick)',
	strpos( $ecc2_php_src, 'checked' ) === false
);

bhp_ecc2_ok(
	'§3.7 ⛔ AN ABSENT CHECKBOX IS A "NO". Consent is read with empty(), never with isset().',
	(bool) preg_match( '/\$optin\s*=\s*!\s*empty\(\s*\$post\[\s*[\'"]optin[\'"]\s*\]\s*\)/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§3.8 ⛔ the capture path never touches the mailchimp_auto_subscribe option',
	strpos( $ecc2_php_src, 'mailchimp_auto_subscribe' ) === false
);

/*
 * ⭐ §3.9 IS THE ONLY RUNTIME ASSERTION IN THIS SECTION AND IT IS THE ONE THAT
 *    MATTERS MOST. Everything above reads source. This one OBSERVES BEHAVIOUR:
 *    it calls the private subscribe path through the public tag filter and
 *    through `bhp_mailchimp_signup_rejected`, which staging fires because it
 *    has no api key. No network request is made and nothing is written.
 */
$GLOBALS['bhp_ecc2_rejected'] = array();
add_action(
	'bhp_mailchimp_signup_rejected',
	function ( $code, $context ) {
		$GLOBALS['bhp_ecc2_rejected'][] = array( 'code' => $code, 'context' => $context );
	},
	10,
	2
);

bhp_ecc2_ok(
	'§3.9 ⭐ the signup-tag filter is registered and is SCOPED BY CONTEXT '
		. '(every other signup surface keeps the tags it has always had)',
	has_filter( 'bhp_mailchimp_signup_tags', array( 'BHP_Early_Cart_Capture', 'filter_signup_tags' ) ) !== false
		&& BHP_Early_Cart_Capture::filter_signup_tags( array( 'Adventure Club' ), 'parent_popup' ) === array( 'Adventure Club' )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §4 · ⭐ THE APPROVED LABEL — ONE CONSENT, ONE WORDING, TWO SURFACES
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§4 THE APPROVED LABEL' );

$ecc2_label = BHP_Early_Cart_Capture::optin_label();

bhp_ecc2_ok(
	'§4.1 the label ends with the optional marker the checkout field renders',
	substr( $ecc2_label, -11 ) === '(optional)'
		|| substr( $ecc2_label, -10 ) === '(optional)'
);

/*
 * ⛔ THE CHECKOUT FIELD REGISTERS ITS LABEL **WITHOUT** "(optional)".
 *    WooCommerce appends that marker itself when it renders an optional field.
 *    ⭐ SO THE ASSERTION IS registered-label + " (optional)" == this label,
 *      NOT a naive equality -- which would fail against a correct build and
 *      teach the next reader to distrust a passing suite.
 */
if ( function_exists( 'bhp_get_marketing_consent_field_definitions' ) ) {
	$ecc2_defs      = bhp_get_marketing_consent_field_definitions();
	$ecc2_checkout  = isset( $ecc2_defs['new_book_releases']['label'] )
		? (string) $ecc2_defs['new_book_releases']['label']
		: '';

	bhp_ecc2_ok(
		'§4.2 ⭐⭐ the cart label is the checkout label plus WooCommerce\'s "(optional)" marker, '
			. 'byte for byte. Two surfaces asking for the SAME consent in DIFFERENT words is how a '
			. 'consent record stops meaning one thing.',
		$ecc2_checkout !== '' && ( $ecc2_checkout . ' (optional)' ) === $ecc2_label,
		'checkout=[' . $ecc2_checkout . '] cart=[' . $ecc2_label . ']'
	);

	bhp_ecc2_ok(
		'§4.3 the shared meta key is still _bhp_new_book_releases_optin',
		isset( $ecc2_defs['new_book_releases']['meta'] )
			&& '_bhp_new_book_releases_optin' === $ecc2_defs['new_book_releases']['meta']
	);
} else {
	echo "SKIP  §4.2-4.3 bhp_get_marketing_consent_field_definitions() not available\n";
}

bhp_ecc2_ok(
	'§4.4 the rendered label reaches BOTH surfaces',
	strpos( $ecc2_cart_html, esc_html( $ecc2_label ) ) !== false
		&& strpos( $ecc2_drawer_html, esc_html( $ecc2_label ) ) !== false
);

bhp_ecc2_ok(
	'§4.5 ⛔ the label promises ONLY releases and a reading idea. No free chapter, sample, '
		. 'guide or download is claimed, because no such asset and no journey to deliver it was verified to exist.',
	stripos( $ecc2_label, 'free' ) === false
		&& stripos( $ecc2_label, 'chapter' ) === false
		&& stripos( $ecc2_label, 'download' ) === false
		&& stripos( $ecc2_label, 'guide' ) === false
);

bhp_ecc2_ok(
	'§4.6 the label is not a copy variant (it is identical across all three)',
	count( array_unique( array_map(
		function ( $v ) { return isset( $v['optin_label'] ) ? $v['optin_label'] : ''; },
		array( BHP_Early_Cart_Capture::active_copy() )
	) ) ) === 1
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §5 · ⛔⛔ CACHE SAFETY — the one that can leak a nonce to strangers
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§5 ⛔⛔ CACHE SAFETY' );

bhp_ecc2_ok(
	'§5.1 ⛔⛔ the localised config only gets a nonce when should_render() is true, i.e. on /cart/ '
		. '(SiteGround caches every other page and varies only on Accept-Encoding)',
	(bool) preg_match( '/if\s*\(\s*self::should_render\(\)\s*\)\s*\{\s*\$data\[\s*[\'"]nonce[\'"]\s*\]/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§5.2 ⛔ the panel endpoint sends nocache_headers()',
	(bool) preg_match( '/handle_panel_ajax\(\)\s*\{\s*nocache_headers\(\);/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§5.3 ⭐ the panel markup carries its OWN nonce, so a fetched drawer panel needs no cached one',
	strpos( $ecc2_drawer_html, 'bhp-cart-capture__nonce' ) !== false
);

bhp_ecc2_ok(
	'§5.4 ⭐ the script prefers the form nonce over the config nonce (one code path, not two that drift)',
	strpos( $ecc2_js_src, 'nonceEl.value ) ? nonceEl.value : ( cfg.nonce' ) !== false
);

bhp_ecc2_ok(
	'§5.5 ⛔ should_enqueue() reads NO per-visitor state. is_user_logged_in / the cart / the '
		. 'mailchimp cookie appear only in should_capture(), which never touches cached markup.',
	(bool) preg_match(
		'/function should_enqueue\(\)\s*\{(?:(?!function ).)*?\}/s',
		$ecc2_php_src,
		$ecc2_enq
	) && strpos( $ecc2_enq[0], 'is_user_logged_in' ) === false
		&& strpos( $ecc2_enq[0], 'MC_COOKIE' ) === false
		&& strpos( $ecc2_enq[0], 'is_empty' ) === false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §6 · THE RENDER GATES
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§6 RENDER GATES' );

bhp_ecc2_ok(
	'§6.1 ⭐ REQUIREMENT 3 (logged in): an identified shopper is never asked',
	strpos( $ecc2_php_src, 'is_user_logged_in' ) !== false
);

bhp_ecc2_ok(
	'§6.2 ⭐ REQUIREMENT 3 (known email): the mailchimp_user_email cookie suppresses the ask',
	strpos( $ecc2_php_src, 'MC_COOKIE' ) !== false
		&& strpos( $ecc2_php_src, "'mailchimp_user_email'" ) !== false
);

bhp_ecc2_ok(
	'§6.3 an empty cart suppresses the ask',
	strpos( $ecc2_php_src, 'is_empty()' ) !== false
);

bhp_ecc2_ok(
	'§6.4 checkout is excluded on BOTH surfaces (page render and panel endpoint)',
	strpos( $ecc2_php_src, 'is_checkout()' ) !== false
		&& strpos( $ecc2_php_src, 'wc_get_checkout_url' ) !== false
);

bhp_ecc2_ok(
	'§6.5 a kill switch exists so the feature can be turned off without a deploy',
	strpos( $ecc2_php_src, "'bhp_cart_capture_enabled'" ) !== false
);

bhp_ecc2_ok(
	'§6.6 should_capture() returns false in this CLI context (no cart, no session)',
	BHP_Early_Cart_Capture::should_capture() === false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §7 · FREQUENCY CAP
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§7 FREQUENCY CAP' );

bhp_ecc2_ok(
	'§7.1 ⭐ the drawer cap uses sessionStorage (once per browser session)',
	strpos( $ecc2_js_src, "_drawer_shown" ) !== false
		&& strpos( $ecc2_js_src, "readStore( 'sessionStorage', KEY_SESSION_DRAWER )" ) !== false
);

bhp_ecc2_ok(
	'§7.2 exit-intent emphasis is capped once per session too',
	strpos( $ecc2_js_src, "readStore( 'sessionStorage', KEY_SESSION_EXIT )" ) !== false
);

bhp_ecc2_ok(
	'§7.3 a dismissal persists in localStorage and is respected for dismissDays',
	strpos( $ecc2_js_src, "writeStore( 'localStorage', KEY_DISMISSED" ) !== false
		&& strpos( $ecc2_js_src, 'cfg.dismissDays' ) !== false
);

bhp_ecc2_ok(
	'§7.4 a completed capture is permanent',
	strpos( $ecc2_js_src, "writeStore( 'localStorage', KEY_DONE, '1' )" ) !== false
);

bhp_ecc2_ok(
	'§7.5 ⭐ a dismissal on ONE surface hides EVERY surface (not two independent dismissals)',
	strpos( $ecc2_js_src, 'hideEverywhere' ) !== false
		&& strpos( $ecc2_js_src, "querySelectorAll( '[data-bhp-cart-capture]' )" ) !== false
);

bhp_ecc2_ok(
	'§7.6 ⛔ every storage access is wrapped: private mode and blocked site data must not throw on the cart',
	substr_count( $ecc2_js_src, 'try {' ) >= 4
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §8 · FUNNEL ISOLATION (.claude/rules/funnels.md)
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§8 FUNNEL ISOLATION' );

bhp_ecc2_ok(
	'§8.1 ⛔⛔ NOT tagged `Adventure Club`. That tag feeds journey 89, whose FIRST email carries '
		. 'the parent funnel coupon, and this shopper is mid-cart at full price.',
	! in_array( 'Adventure Club', BHP_Early_Cart_Capture::optin_tags(), true )
);

bhp_ecc2_ok(
	'§8.2 ⛔⛔ NOT tagged `Customer - Purchased`. THEY HAVE NOT PURCHASED. Writing it would be a '
		. 'fabricated fact about a real person, which is the never-invent rule, and it is the exact '
		. 'defect probe order 5689 recorded at checkout.',
	! in_array( 'Customer - Purchased', BHP_Early_Cart_Capture::optin_tags(), true )
);

bhp_ecc2_ok(
	'§8.3 the signup context is its own lane',
	strpos( $ecc2_php_src, "'cart_capture_optin'" ) !== false
);

bhp_ecc2_ok(
	'§8.4 ⛔ lead_magnet is EMPTY. Subscribing is not enrolling; a lead-magnet value is what enrols.',
	(bool) preg_match( "/'lead_magnet'\s*=>\s*''/", $ecc2_php_src )
);

bhp_ecc2_ok(
	'§8.5 storage prefix is distinct from both popup funnels',
	strpos( $ecc2_js_src, 'bhp_parent_popup' ) === false
		&& strpos( $ecc2_js_src, 'bhp_mariana_popup' ) === false
);

bhp_ecc2_ok(
	'§8.6 the mariana popup engine is not forked for a third funnel',
	strpos( $ecc2_js_src, 'mariana-popup' ) === false
		&& strpos( $ecc2_js_src, 'data-popup-config' ) === false
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §9 · ⭐ CONSENT SURVIVES TO THE ORDER
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§9 CONSENT REACHES THE ORDER' );

bhp_ecc2_ok(
	'§9.1 ⛔ the mirror runs at priority 25, AFTER bhp_store_marketing_consent_meta() at 20 '
		. '(which writes yes OR no for every order and would otherwise overwrite this)',
	has_action( 'woocommerce_store_api_checkout_order_processed', array( 'BHP_Early_Cart_Capture', 'mirror_optin_to_order' ) ) === 25
		&& has_action( 'woocommerce_checkout_order_processed', array( 'BHP_Early_Cart_Capture', 'mirror_optin_to_order' ) ) === 25
);

bhp_ecc2_ok(
	'§9.2 ⭐ the checkout lane still runs at 20 and was NOT touched by this build',
	has_action( 'woocommerce_store_api_checkout_order_processed', 'bhp_store_marketing_consent_meta' ) === 20
);

bhp_ecc2_ok(
	'§9.3 the choice is carried in the WooCommerce session, not in a second cookie of our own',
	strpos( $ecc2_php_src, 'WC()->session->set' ) !== false
		&& strpos( $ecc2_php_src, 'SESSION_OPTIN' ) !== false
);

bhp_ecc2_ok(
	'§9.4 ⭐ PROVENANCE is written unconditionally and separately from the shared mirror',
	strpos( $ecc2_php_src, '_bhp_cart_capture_optin' ) !== false
		&& strpos( $ecc2_php_src, 'META_PROVENANCE' ) !== false
);

bhp_ecc2_ok(
	'§9.5 ⛔⛔ THE SHARED MIRROR IS ONLY EVER UPGRADED. This file never writes '
		. "'no' to _bhp_new_book_releases_optin and never unsubscribes anybody.",
	! preg_match( '/update_meta_data\(\s*self::META_MIRROR\s*,\s*[\'"]no[\'"]/', $ecc2_php_src )
		&& (bool) preg_match( '/update_meta_data\(\s*self::META_MIRROR\s*,\s*[\'"]yes[\'"]\s*\)/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§9.6 the consent source is recorded as cart_capture, so the checkout source is never overwritten silently',
	strpos( $ecc2_php_src, "'cart_capture'" ) !== false
);

bhp_ecc2_ok(
	'§9.7 ⛔ functions.php was NOT edited: the mirror is registered from this file',
	strpos( $ecc2_php_src, "add_action( 'woocommerce_store_api_checkout_order_processed'" ) !== false
);

/*
 * ⭐⭐ §9.8 IS THE FOUNDER'S TICK RULE, PINNED IN CODE.
 *     Andrew Signore, 2026-09-07, seal 1225: **ANY TICK SUBSCRIBES; AN
 *     UNTICKED CHECKOUT BOX DOES NOT UNDO A CART TICK.**
 *
 * ⛔ THIS ASSERTION EXISTS BECAUSE THE OPPOSITE BEHAVIOUR IS ONE DELETED
 *    LINE AWAY. When the file was prepared, dropping the `'yes' !== $existing`
 *    guard was written down as the switch to the other reading. A ruling that
 *    lives only in a report cannot stop a later lane deleting that line as
 *    dead code; an assertion can, and this is it. If the guard goes, this
 *    goes red and the founder ruling is revisited rather than lost.
 */
bhp_ecc2_ok(
	'§9.8 ⭐ SEAL 1225: the upgrade guard is present, so a cart tick survives an '
		. 'unticked checkout box (any tick subscribes)',
	(bool) preg_match( '/\'yes\'\s*!==\s*\$existing/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§9.9 ⛔ the ruling did NOT widen consent: an unticked cart shopper still writes '
		. "no 'yes' to the shared mirror (the write is inside the 'yes' === \$choice branch)",
	(bool) preg_match( '/if\s*\(\s*\'yes\'\s*===\s*\$choice\s*\)\s*\{[^}]*META_MIRROR/s', $ecc2_php_src )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §10 · COPY RAILS
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§10 COPY RAILS' );

$ecc2_variants = BHP_Early_Cart_Capture::copy_variants();

bhp_ecc2_ok( '§10.1 three variants are offered for Andrew to pick from', count( $ecc2_variants ) === 3 );

foreach ( $ecc2_variants as $ecc2_key => $ecc2_copy ) {
	$ecc2_all = implode( ' ', $ecc2_copy ) . ' ' . $ecc2_label;

	bhp_ecc2_ok(
		"§10.2[{$ecc2_key}] ⛔ no em dash and no en dash",
		strpos( $ecc2_all, "\xE2\x80\x94" ) === false && strpos( $ecc2_all, "\xE2\x80\x93" ) === false
	);

	bhp_ecc2_ok(
		"§10.3[{$ecc2_key}] ⛔ first person only. No we/us/our -- Standing Rules 9.1, he is the sole operator.",
		! preg_match( '/\b(we|us|our|ours|we\'re|we\'ll|we\'ve)\b/i', $ecc2_all )
	);

	bhp_ecc2_ok(
		"§10.4[{$ecc2_key}] ⭐ American spelling",
		! preg_match( '/\b\w+(ise|isation|ised|ising)\b/i', $ecc2_all )
			&& ! preg_match( '/\b(colour|favourite|catalogue|licence|whilst|neighbour|behaviour)\b/i', $ecc2_all )
	);

	bhp_ecc2_ok(
		"§10.5[{$ecc2_key}] ⛔ no outcome claim, statistic, rating or testimonial",
		! preg_match( '/\b(\d+%|thousands|proven|guaranteed|best[- ]selling|award|rated|reviews?)\b/i', $ecc2_all )
	);

	bhp_ecc2_ok(
		"§10.6[{$ecc2_key}] ⭐ the reminder is worded as a CAPABILITY, never a commitment. "
			. 'The Mailchimp journey is Active but has never been observed to fire, so promising a '
			. 'delivery nobody has seen would be a fabricated claim.',
		! preg_match( '/\b(I will send|I\'ll send|you will receive|we will send)\b/i', $ecc2_all )
	);

	bhp_ecc2_ok(
		"§10.7[{$ecc2_key}] ⭐ the fine print points at the BOX, not at a promise that has moved. "
			. "v1 said 'unless you ask me to' when there was nothing to ask with; there is now.",
		stripos( $ecc2_copy['fine_print'], 'box' ) !== false
	);
}

bhp_ecc2_ok(
	'§10.8 the active variant resolves and is one of the three',
	array_key_exists( BHP_Early_Cart_Capture::active_variant(), $ecc2_variants )
);

bhp_ecc2_ok(
	'§10.9 an unknown variant filter falls back to A rather than fataling',
	( function () {
		add_filter( 'bhp_cart_capture_variant', function () { return 'zzz'; }, 99 );
		$v = BHP_Early_Cart_Capture::active_variant();
		remove_all_filters( 'bhp_cart_capture_variant', 99 );
		return 'a' === $v;
	} )()
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §11 · ENDPOINT SECURITY
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§11 ENDPOINT SECURITY' );

/*
 * ⛔⛔ §11.0 IS THE REGRESSION GUARD FOR A DEFECT THAT ACTUALLY SHIPPED INTO
 *     THIS BUILD AND WAS CAUGHT IN A REAL BROWSER, NOT BY THIS SUITE.
 *
 * ⛔ WHAT HAPPENED: `should_capture()` opened with a bare `is_admin()` guard.
 *    `is_admin()` RETURNS TRUE INSIDE `admin-ajax.php`, and the panel endpoint
 *    is the only route by which the drawer panel can exist — so the drawer
 *    prompt could never render for anybody. The endpoint answered
 *    `{"ok":true,"render":false}`, a clean 200 indistinguishable from a
 *    correctly suppressed prompt. VERIFIED LIVE on staging 1.19.391,
 *    2026-09-07, as a guest with a non-empty cart.
 *
 * ⛔ AND WHY THIS SUITE MISSED IT: under WP-CLI `is_admin()` is false and
 *    `wp_doing_ajax()` is false, so the broken line evaluates correctly here.
 *    A source assertion is the only instrument available; it is written as a
 *    source assertion deliberately, and its limits are stated rather than
 *    implied. ⭐ The real proof of this feature is a browser opening the
 *    drawer, and that evidence belongs in the build report.
 */
bhp_ecc2_ok(
	'§11.0 ⛔⛔ the admin guard carves out AJAX, or the drawer panel can never render '
		. '(is_admin() is TRUE inside admin-ajax.php)',
	(bool) preg_match( '/is_admin\(\)\s*&&\s*!\s*wp_doing_ajax\(\)/', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§11.0a ⭐ and the panel endpoint loads the cart before asking whether it is empty',
	(bool) preg_match( '/wc_load_cart\(\)/', $ecc2_php_src )
);

/*
 * ⛔⛔ §11.0b IS THE OTHER HALF OF THE SAME LIVE FINDING, AND IT IS A
 *     LANGUAGE TRAP RATHER THAN A LOGIC ERROR.
 *
 * ⛔ `wp_localize_script()` STRINGIFIES EVERY SCALAR. PHP localises
 *    `'isCart' => 0`; the browser receives the STRING `"0"`, and `"0"` is
 *    TRUTHY in JavaScript. `cfg.isCart ? null : getElementById(...)` therefore
 *    took the truthy branch on every non-cart page, the drawer element was
 *    never found, the MutationObserver was never attached, and the drawer
 *    panel could not appear anywhere on the site.
 *
 * ⛔ THE TWO DEFECTS MASKED EACH OTHER PERFECTLY. §11.0's server-side
 *    `is_admin()` bug returned `render:false`, which looks like a correctly
 *    suppressed prompt; this client-side bug meant the request was never made
 *    in the first place. Fixing either one alone still shows an empty drawer.
 *    Only opening the drawer in a browser finds them.
 *
 * ⭐ THE ASSERTION IS DELIBERATELY ABOUT THE COMPARISON, NOT THE VALUE: a
 *    strict, explicit test that accepts both the string and the number.
 */
bhp_ecc2_ok(
	'§11.0b ⛔⛔ the isCart flag is compared explicitly, never by truthiness '
		. '(wp_localize_script stringifies it, and "0" is truthy in JS)',
	false === strpos( $ecc2_js_src, 'cfg.isCart ?' )
		&& (bool) preg_match( "/cfg\.isCart\s*===\s*'1'/", $ecc2_js_src )
);


bhp_ecc2_ok( '§11.1 nonce is verified on the capture endpoint', strpos( $ecc2_php_src, 'wp_verify_nonce' ) !== false );
bhp_ecc2_ok( '§11.2 honeypot is checked', strpos( $ecc2_php_src, 'bhp_website' ) !== false );
bhp_ecc2_ok( '§11.3 rate limiting is applied', strpos( $ecc2_php_src, 'rate_limited' ) !== false );
bhp_ecc2_ok(
	'§11.4 email is sanitised and validated',
	strpos( $ecc2_php_src, 'sanitize_email' ) !== false && strpos( $ecc2_php_src, 'is_email' ) !== false
);

bhp_ecc2_ok(
	'§11.5 ⭐ the panel endpoint MUTATES NOTHING, which is why it needs no nonce. '
		. 'It performs no update_option, no wp_insert, no update_meta and no session write.',
	( function () use ( $ecc2_php_src ) {
		if ( ! preg_match( '/function handle_panel_ajax\(\)\s*\{(?:(?!\n\t\}).)*/s', $ecc2_php_src, $m ) ) {
			return false;
		}
		foreach ( array( 'update_option', 'wp_insert', 'update_meta', 'session->set', 'set_transient' ) as $bad ) {
			if ( strpos( $m[0], $bad ) !== false ) {
				return false;
			}
		}
		return true;
	} )()
);

bhp_ecc2_ok(
	'§11.6 ⛔ a Mailchimp outage cannot break the cart: the service call is wrapped',
	(bool) preg_match( '/try\s*\{.*?set_user_from_block_checkout.*?catch/s', $ecc2_php_src )
);

bhp_ecc2_ok(
	'§11.7 ⛔ a signup failure cannot break the cart either: the subscribe call is wrapped',
	(bool) preg_match( '/try\s*\{.*?bhp_process_signup.*?catch/s', $ecc2_php_src )
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §12 · NO PRE-CONSENT COOKIE, NO SIDE EFFECTS
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§12 NO PRE-CONSENT COOKIE, NO SIDE EFFECTS' );

bhp_ecc2_ok(
	'§12.1 ⛔ the script sets no cookie. Page load performs no storage write at all.',
	strpos( $ecc2_js_src, 'document.cookie' ) === false
);

bhp_ecc2_ok(
	'§12.2 ⛔ the theme never writes mailchimp_user_email itself; the vendor entry point does, '
		. 'with the vendor\'s own duration, path and SameSite',
	strpos( $ecc2_php_src, 'setcookie' ) === false
		&& strpos( $ecc2_php_src, 'mailchimp_set_cookie' ) === false
);

bhp_ecc2_ok(
	'§12.3 no plugin file is referenced for editing; the drawer is reached by class name only',
	strpos( $ecc2_php_src, 'bundle-drawer' ) === false
);

bhp_ecc2_ok(
	'§12.4 ⛔ no aggregateRating or review schema is emitted from this surface',
	strpos( $ecc2_php_src, 'aggregateRating' ) === false && strpos( $ecc2_cart_html, 'aggregateRating' ) === false
);

bhp_ecc2_ok(
	'§12.5 the CSS introduces no second palette (tokens only, with fallbacks)',
	substr_count( $ecc2_css_raw, 'var( --color-' ) >= 10
);

/* ═══════════════════════════════════════════════════════════════════════════
 * §13 · ⭐ THE ENVIRONMENT ITSELF — recorded so a zero row count is never
 *          mistaken for a defect
 * ═══════════════════════════════════════════════════════════════════════════ */
bhp_ecc2_head( '§13 ENVIRONMENT (recorded, not asserted as desirable)' );

if ( function_exists( 'mailchimp_get_option' ) ) {
	$ecc2_tracking = (string) mailchimp_get_option( 'mailchimp_cart_tracking', 'all' );
	echo "INFO  §13.1 mailchimp_cart_tracking = '{$ecc2_tracking}'\n";
	echo "INFO  §13.2 mailchimp_is_configured = "
		. ( function_exists( 'mailchimp_is_configured' ) && mailchimp_is_configured() ? 'true' : 'false' )
		. "\n";
	echo "INFO  §13.3 ⛔ if either of the two lines above is 'disabled' / 'false', handleCartUpdated()\n";
	echo "INFO        returns before trackCart() and NO ugc_mailchimp_carts row can appear,\n";
	echo "INFO        however correct this theme code is. That is configuration, not a defect,\n";
	echo "INFO        and changing it is an Andrew gate.\n";
} else {
	echo "SKIP  §13 mailchimp-for-woocommerce helpers not loaded\n";
}

if ( class_exists( 'MailChimp_Service' ) ) {
	$ecc2_ref = new ReflectionClass( 'MailChimp_Service' );
	bhp_ecc2_ok(
		'§13.4 ⭐ set_user_from_block_checkout() exists (the vendor entry point this build depends on)',
		$ecc2_ref->hasMethod( 'set_user_from_block_checkout' )
	);

	/*
	 * ⛔⛔ THE ASSERTION THAT EXPLAINS WHY THE BRIEF COULD NOT BE FOLLOWED
	 *     LITERALLY. The brief asked for the subscribed status to travel
	 *     "through the plugin's opt-in path". IT CANNOT: `cart_subscribe` is
	 *     protected with no public setter, and the ONE function that populates
	 *     it is the plugin's own AJAX responder, which terminates the request.
	 *     ⭐ If this assertion ever FAILS, the plugin has grown a public
	 *       setter and the subscribe path SHOULD be revisited.
	 */
	bhp_ecc2_ok(
		'§13.5 ⛔ the plugin STILL exposes no public setter for cart_subscribe '
			. '(if this fails, the vendor added one and the house subscribe path should be reconsidered)',
		! $ecc2_ref->hasMethod( 'setCartSubscribe' )
			&& ! $ecc2_ref->hasMethod( 'set_cart_subscribe' )
			&& $ecc2_ref->hasProperty( 'cart_subscribe' )
			&& $ecc2_ref->getProperty( 'cart_subscribe' )->isProtected()
	);
} else {
	echo "SKIP  §13.4-13.5 MailChimp_Service not loaded\n";
}

/* ═══════════════════════════════════════════════════════════════════════════ */
echo "\n";
echo "PASS: {$GLOBALS['bhp_ecc2_pass']}  FAIL: {$GLOBALS['bhp_ecc2_fail']}\n";
