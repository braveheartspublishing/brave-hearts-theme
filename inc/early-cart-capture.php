<?php
/**
 * EARLY CART CAPTURE — v1 theme 1.19.316 (2026-08-28), v2 this build.
 * v1: `CYCLE168-CX-EARLY-CART-CAPTURE`.
 * v2: `CYCLE179-CX-EARLY-CART-CAPTURE-2`, Andrew Signore 2026-09-07,
 *     "yes build the early cart capture". ⛔ RELAYED through `chief-of-staff` and the
 *     brief; NOT witnessed first-hand by the session that wrote this file.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE PROBLEM THIS SOLVES (unchanged from v1, restated because it is the
 *    reason every gate below is shaped the way it is)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `mailchimp-for-woocommerce` cannot create an abandoned-cart record until
 * `MailChimp_Service::getCurrentUserEmail()` resolves. For a guest that
 * resolves ONLY from the `mailchimp_user_email` COOKIE. On this store that
 * cookie is first written when the buyer types into the Blocks checkout email
 * field, which the production logs put at a MEDIAN 132 SECONDS BEFORE PAYMENT.
 *
 * Consequence, measured: in the 30-day production log window, 22 carts reached
 * Mailchimp and 20 were deleted within minutes by the order that completed
 * them. Exactly ONE genuinely-abandoned cart has existed since the journey
 * went Active on 2026-08-04. The entire upper funnel is INVISIBLE, not delayed.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ WHAT v2 CHANGES, AND THE ONE THING IT DOES NOT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 1. The prompt now also appears in the CART DRAWER (`#bhp-cart-drawer`), not
 *    only on `/cart/`. See "THE CACHE PROBLEM" below — this is the whole
 *    reason for the second endpoint, and it is not optional.
 * 2. A MARKETING OPT-IN CHECKBOX is added, UNTICKED, carrying the SAME
 *    approved label the checkout field carries.
 * 3. Frequency cap moves to ONCE PER BROWSER SESSION, still dismissible.
 * 4. The prompt is INLINE AND VISIBLE rather than revealed after a dwell
 *    timer; exit intent now EMPHASISES it (scroll into view + focus) once,
 *    rather than being one of three ways to un-hide it.
 *
 * ⛔ WHAT IS UNCHANGED: an UNTICKED shopper is still `transactional` BY
 *    CONSTRUCTION. That guarantee did not weaken; it gained a consent-gated
 *    branch beside it. See the consent rail immediately below.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE CONSENT RAIL — THIS IS THE PART THAT MUST NOT DRIFT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ TYPING AN EMAIL TO SAVE A CART IS STILL NOT CONSENT TO MARKETING.
 *    TICKING THE BOX IS. Those are two different acts and this file keeps
 *    them two different code paths.
 *
 * The cart record MUST land `transactional` in Mailchimp for an UNTICKED
 * shopper and MUST NEVER be auto-subscribed by that path. That guarantee is
 * STRUCTURAL, and here is the exact chain that makes it so — re-verified in
 * the vendor source on BOTH environments on 2026-09-07, not inherited from
 * the v1 comment:
 *
 *   class-mailchimp-woocommerce-cart-update.php:193
 *       $subscriber_status = $this->status ? 'subscribed' : 'transactional';
 *   $this->status  <- $handler->setStatus($this->cart_subscribe)   service:284 (6.1.1)
 *                                                                  service:388 (6.2)
 *   $cart_subscribe is populated in EXACTLY ONE PLACE:              service:1232 (6.1.1)
 *                                                                  service:1368 (6.2)
 *       $this->cart_subscribe = (bool) $_POST['subscribed'];
 *
 * ⛔ THIS FILE NEVER SETS, FORWARDS OR SYNTHESISES `$_POST['subscribed']`,
 *    AND MUST NEVER BE MADE TO. The test suite asserts this. The defensive
 *    `unset()` in `handle_ajax()` is deliberate and stays.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ SUPERSEDED, PRESERVED, AND DATED — the v1 "DELIBERATE NON-GOAL"
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * v1 shipped with NO opt-in box and said so at length. That decision is now
 * OVERTURNED BY THE OWNER. The superseded paragraph is preserved struck
 * rather than deleted, because a future reader who finds only the new
 * behaviour cannot tell whether the old rail was considered or forgotten:
 *
 *   ~~⚠ DELIBERATE NON-GOAL: there is NO marketing opt-in tick-box on this
 *     surface in v1. The brief permitted an optional unticked one. It is
 *     omitted on purpose: promoting a contact to `subscribed` requires the
 *     theme's own signup path (`bhp_process_signup()`), which carries the
 *     parent/teacher funnel tagging that belongs to another lane, and adding
 *     a second consent surface here widens the blast radius of the one thing
 *     this file must not get wrong. Recorded for Andrew as a follow-up, not
 *     silently dropped.~~
 *
 * ⭐ THE FOLLOW-UP CAME BACK APPROVED. Andrew Signore, 2026-09-07, relayed:
 *    "yes build the early cart capture", against a brief that specifies an
 *    UNTICKED box carrying the checkout label. The v1 paragraph's ONE REAL
 *    RISK — parent-funnel tagging — is answered directly rather than by
 *    hoping: this path passes its OWN tags (`bhp_get_cart_capture_optin_tags()`)
 *    and an EMPTY `lead_magnet`, exactly as `inc/checkout-optin-sync.php`
 *    does, so it subscribes without ENROLLING anybody in journey 89.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE BRIEF ASKED FOR SOMETHING THE PLUGIN CANNOT DO — read this before
 *     "fixing" the subscribe path back to the plugin
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The brief said to set the subscribed status "through the plugin's opt-in
 * path". ⛔ THAT PATH IS NOT REACHABLE FROM HERE, and the attempt would fail
 * SILENTLY — a ticked box producing a transactional contact, which is the
 * worst possible failure mode because it looks like it worked.
 *
 * ⭐ VERIFIED LIVE over SSH on 2026-09-07, on BOTH environments, by reading
 *    the installed vendor source rather than the documentation:
 *
 *      · `set_user_from_block_checkout($email)` — the function this file
 *        calls — READS `$_POST['subscribed']` NOWHERE. Its 23-line body sets
 *        the cookie, calls `getCartItems()` and calls `handleCartUpdated()`.
 *        ⭐ Byte-identical on staging 6.1.1 and production 6.2:
 *        md5 `008a6f70e7e2617c2cc4b131dc77ecd3` on both.
 *      · `$_POST['subscribed']` is read in EXACTLY ONE function,
 *        `set_user_by_email()`, which is the plugin's own AJAX responder and
 *        terminates the request with `respondJSON()`. It cannot be called
 *        from inside another handler without killing it.
 *      · `$cart_subscribe` is `protected` on 6.1.1 AND 6.2 and has NO public
 *        setter. `grep 'public function set'` on the service class returns
 *        `setLandingSiteCookie`, `setWooSession`, `set_user_from_block_checkout`
 *        and `set_user_by_email`. None of them reaches it.
 *
 * ⛔ REFLECTION WAS CONSIDERED AND REJECTED. Writing a protected vendor
 *    property would work today and break silently on the next plugin update,
 *    and the two environments are ALREADY on different plugin versions.
 *
 * ⭐ SO THE TICKED PATH USES THE HOUSE OPT-IN PATH INSTEAD — the same
 *    `bhp_process_signup()` call that `inc/checkout-optin-sync.php` has been
 *    shipping since 1.19.313. Two concerns, two mechanisms, both already
 *    proven: the PLUGIN captures the CART, the HOUSE PATH subscribes the
 *    PERSON. Recorded for Andrew as a deviation from the brief, not absorbed.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE CACHE PROBLEM, AND WHY THE DRAWER PANEL IS FETCHED RATHER THAN
 *     PRINTED — v1's own header predicted this exact requirement
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * SiteGround's page cache stores rendered HTML and varies only on
 * Accept-Encoding — the same fact that forced `BHP_Consent::default_signals()`
 * to be constant for every visitor (observed live on production 2026-08-04,
 * mis-serving in BOTH directions). Any per-visitor server-rendered markup is
 * served to the wrong visitors.
 *
 * This panel carries a NONCE and its visibility depends on per-visitor state
 * (is the cart empty, is this a guest, was it already dismissed). v1 solved
 * that by rendering ONLY on `/cart/`, which WooCommerce already excludes from
 * the cache. v2 must also reach the drawer, and the drawer opens on PRODUCT
 * PAGES, WHICH ARE CACHED.
 *
 * ⭐ v1's header wrote the answer down in advance: *"it would have to render
 *    on cached pages and every per-visitor decision would have to move to an
 *    uncached fetch first."* That is precisely what `handle_panel_ajax()` is.
 *
 * ⛔ THEREFORE, AND THIS IS THE RULE, NOT A PREFERENCE:
 *    · `/cart/` — server-rendered inline, nonce in the localised config.
 *      Uncached page, so this is safe and costs no extra request on the
 *      highest-intent surface.
 *    · EVERYWHERE ELSE — NOTHING per-visitor is printed. The localised config
 *      on a cacheable page carries NO NONCE and NO per-visitor value. The
 *      panel and its fresh nonce arrive from `admin-ajax.php`, which is never
 *      page-cached, at the moment the drawer opens.
 *
 * ⛔ DO NOT "SIMPLIFY" THIS BY PRINTING THE PANEL IN `wp_footer` SITEWIDE.
 *    It would hand one shopper's nonce to every other visitor of that URL.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHY NO PLUGIN FILE IS TOUCHED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `bundle-drawer.js` wipes `.bhp-cart-drawer__items`, `__message`,
 * `__crosssell` and `__summary` with `innerHTML = ''` on every render (lines
 * 1163, 1212, 1220, 1442). It NEVER touches `.bhp-cart-drawer__body` itself.
 * ⭐ So the panel is inserted as a SIBLING inside `__body`, after `__message`,
 *    and survives every Store API re-render without one byte of plugin change.
 *    This is the same principle as the `/cart/` placement, where the panel is
 *    a sibling AFTER `.wp-block-woocommerce-cart` and never inside that React
 *    root.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * FUNNEL ISOLATION (.claude/rules/funnels.md)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Storage prefix `bhp_cart_capture`, event prefix `cart_capture`, signup
 * context `cart_capture_optin`. All distinct from the parent funnel
 * (`bhp_parent_popup` / `parent_popup` / `Adventure Club`) and the teacher
 * funnel (`bhp_mariana_popup` / `teacher_popup`). This surface is NOT a third
 * popup and deliberately does NOT use `assets/js/mariana-popup.js`.
 */

defined( 'ABSPATH' ) || exit;

class BHP_Early_Cart_Capture {

	const NONCE_ACTION = 'bhp_cart_capture';
	const AJAX_ACTION  = 'bhp_cart_capture';

	/** The uncached panel fetch used by the drawer. See the cache note above. */
	const PANEL_ACTION = 'bhp_cart_capture_panel';

	/** Storage prefix. Deliberately distinct from both popup funnels. */
	const STORAGE_PREFIX = 'bhp_cart_capture';

	/** Analytics event prefix. Deliberately distinct from both popup funnels. */
	const EVENT_PREFIX = 'cart_capture';

	/**
	 * The cookie the Mailchimp plugin reads for a guest. Named here ONLY so
	 * this file can tell whether capture already happened; it is never
	 * written directly -- `set_user_from_block_checkout()` writes it, with
	 * the plugin's own duration, path and SameSite.
	 */
	const MC_COOKIE = 'mailchimp_user_email';

	/** WooCommerce session keys carrying the cart-time consent forward. */
	const SESSION_OPTIN    = 'bhp_cart_capture_optin';
	const SESSION_OPTIN_AT = 'bhp_cart_capture_optin_at';

	/** Order meta. `_bhp_cart_capture_optin` is PROVENANCE and is never overwritten. */
	const META_PROVENANCE = '_bhp_cart_capture_optin';
	const META_MIRROR     = '_bhp_new_book_releases_optin';

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 25 );

		/*
		 * ⛔ PRIORITY 5, NOT 20, AND THIS IS A BUG THAT WAS ACTUALLY OBSERVED,
		 *    NOT A PRECAUTION.
		 *
		 * `wp_print_footer_scripts()` is hooked to `wp_footer` at PRIORITY 20.
		 * This render was ALSO at 20 and was registered later, so WordPress ran
		 * the scripts FIRST: the `<script src=early-cart-capture.js>` tag was
		 * emitted ABOVE the panel markup, the script's `getElementById()`
		 * returned null, and the IIFE bailed out. ⭐ THE ENTIRE FEATURE WAS
		 * INERT while the PHP suite reported 72/72 PASS -- because no PHP
		 * assertion can see DOM ordering. Caught only in a real browser on
		 * staging (2026-08-28, `window.innerWidth` 1280).
		 *
		 * ⭐ Priority 5 puts the markup ahead of the footer scripts. The JS also
		 *    carries its own DOM-ready guard now, so neither fix alone is
		 *    load-bearing -- but do not "tidy" this back to 20.
		 */
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 5 );

		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'handle_ajax' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'handle_ajax' ) );

		add_action( 'wp_ajax_nopriv_' . self::PANEL_ACTION, array( __CLASS__, 'handle_panel_ajax' ) );
		add_action( 'wp_ajax_' . self::PANEL_ACTION, array( __CLASS__, 'handle_panel_ajax' ) );

		/*
		 * ⛔ PRIORITY 25, AND THE NUMBER IS LOAD-BEARING.
		 *    `bhp_store_marketing_consent_meta()` runs at 20 on these same two
		 *    hooks and writes `_bhp_new_book_releases_optin` to 'yes' OR 'no'
		 *    for every order. Running before it would be overwritten; running
		 *    at 25 lets the cart-time consent be considered afterwards.
		 * ⛔ REGISTERED FROM THIS FILE, NOT `functions.php`. functions.php is
		 *    another lane's file this pass and is deliberately not touched.
		 */
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'mirror_optin_to_order' ), 25, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'mirror_optin_to_order' ), 25, 1 );

		add_filter( 'bhp_mailchimp_signup_tags', array( __CLASS__, 'filter_signup_tags' ), 10, 2 );
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * COPY
	 * ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * ⭐⭐ THE APPROVED OPT-IN LABEL. IT IS ONE STRING, SHARED WITH CHECKOUT,
	 *     AND IT IS NOT A COPY VARIANT.
	 *
	 * ⛔ IT MUST STAY BYTE-IDENTICAL to the label registered by
	 *    `bhp_get_marketing_consent_field_definitions()` in `functions.php`
	 *    (rendered at checkout as `contact-brave-hearts-new-book-releases`
	 *    since 1.19.385). Two surfaces asking for the SAME consent with
	 *    DIFFERENT words is how a consent record stops meaning one thing.
	 *    The test suite asserts the two strings match.
	 *
	 * ⛔ AND IT PROMISES ONLY WHAT THE BUSINESS DELIVERS: new-release
	 *    announcements and an occasional family reading idea. No free chapter,
	 *    no sample, no guide, no download. There is no such asset and no
	 *    journey wired to deliver one, so none is claimed.
	 */
	public static function optin_label() {
		return 'Email me when a new Charlotte and Henry book or edition is released, plus the occasional family reading idea. (optional)';
	}

	/**
	 * ⭐ THE THREE COPY VARIANTS. Andrew picks one; the pick is a one-line
	 *    filter, not an edit to markup.
	 *
	 * ⛔ COPY DISCIPLINE APPLIED HERE, AND IT IS LOAD-BEARING:
	 *    · First person. No "we"/"us"/"our" -- Standing Rules 9.1, he is the
	 *      sole operator.
	 *    · No em dashes and no en dashes.
	 *    · American spelling.
	 *    · No outcome claims, no statistics, no testimonials.
	 *    · ⭐ AND NO PROMISE THE SYSTEM MAY NOT KEEP. Saving the cart is what
	 *      this code actually does, so the copy promises that. Whether a
	 *      reminder email ever arrives depends on Andrew's Mailchimp journey,
	 *      which is Active but has never been observed to fire. So the
	 *      reminder is worded as "can", never as a commitment.
	 *    · ⭐ THE FINE PRINT NOW POINTS AT THE BOX. v1 said "I will not add you
	 *      to my newsletter unless you ask me to", which was true when there
	 *      was nothing to ask with. There is now, so the sentence names it.
	 *      An inaccurate privacy line is worse than a missing one.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function copy_variants() {
		return array(
			// A -- the plainest. One line, leads with the offer, not the ask.
			'a' => array(
				'heading'     => 'Save your cart',
				'body'        => 'Leave your email and I will keep these books here for you.',
				'placeholder' => 'Your email address',
				'button'      => 'Save my cart',
				'fine_print'  => 'I use your email to save this cart and to send you a link back to it. I will not add you to my email list unless you tick the box above.',
				'success'     => 'Saved. Your cart will be waiting for you.',
			),
			// B -- frames it as a convenience the shopper performs on themselves.
			'b' => array(
				'heading'     => 'Keep this cart for later',
				'body'        => 'Leave your email and nothing here gets lost while you think it over.',
				'placeholder' => 'Your email address',
				'button'      => 'Keep my cart',
				'fine_print'  => 'Used to hold your cart and to send you a link back to it. No email list unless you tick the box above.',
				'success'     => 'Done. I am holding your cart for you.',
			),
			// C -- names the hesitation out loud, which is the honest read of
			//      what a parent on this surface is actually doing.
			'c' => array(
				'heading'     => 'Not ready to check out?',
				'body'        => 'That is fine. Leave your email and I will keep this cart exactly as you left it.',
				'placeholder' => 'Your email address',
				'button'      => 'Save my cart',
				'fine_print'  => 'This saves your cart and lets me send you a link back to it. It does not sign you up for anything else unless you tick the box above.',
				'success'     => 'Kept. Come back whenever you are ready.',
			),
		);
	}

	/**
	 * Which variant is live. Andrew's pick lands here.
	 *
	 * @return string
	 */
	public static function active_variant() {
		$variant  = (string) apply_filters( 'bhp_cart_capture_variant', 'a' );
		$variants = self::copy_variants();
		return isset( $variants[ $variant ] ) ? $variant : 'a';
	}

	/** @return array<string,string> */
	public static function active_copy() {
		$variants = self::copy_variants();
		$copy     = $variants[ self::active_variant() ];
		$copy['optin_label'] = self::optin_label();
		return $copy;
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * RENDER GATES
	 * ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * ⛔ THE PER-VISITOR GATE. Every condition here is a reason NOT to nag.
	 *    ⭐ IT IS SURFACE-INDEPENDENT ON PURPOSE: the cart page and the drawer
	 *      must agree about whether this shopper should be asked, or a shopper
	 *      suppressed on one surface would be asked on the other.
	 *
	 * @return bool
	 */
	public static function should_capture() {
		// Feature kill switch, so this can be turned off without a deploy.
		if ( ! apply_filters( 'bhp_cart_capture_enabled', true ) ) {
			return false;
		}

		/*
		 * ⛔⛔ `wp_doing_ajax()` IS LOAD-BEARING, AND IT WAS FOUND BY OPENING
		 *     THE DRAWER IN A REAL BROWSER, NOT BY READING THIS FILE.
		 *
		 * ⛔ THE DEFECT, EXACTLY: `is_admin()` RETURNS TRUE INSIDE
		 *    `admin-ajax.php`. WordPress treats that endpoint as an admin
		 *    request whichever side of the site called it. `handle_panel_ajax()`
		 *    is the ONLY way the drawer panel can ever exist, and it gates on
		 *    this function, so with a bare `is_admin()` the drawer panel could
		 *    never render for anybody. VERIFIED LIVE on staging 1.19.391,
		 *    2026-09-07: as a guest with a non-empty cart the endpoint answered
		 *    `{"ok":true,"render":false}` — a clean 200 that looks exactly like
		 *    a correctly suppressed prompt, which is why nothing upstream
		 *    caught it.
		 *
		 * ⭐ WHY THE GUARD STAYS AT ALL: it stops this running on wp-admin
		 *    screens, where there is no shopper and no cart. That intent is
		 *    unchanged; only the AJAX case is carved out.
		 *
		 * ⛔ THE PHP SUITE COULD NOT HAVE CAUGHT THIS. Under WP-CLI both
		 *    `is_admin()` and `wp_doing_ajax()` are false, so the line reads as
		 *    correct from every angle except the one that matters. This is the
		 *    conversion-audit rule in its own words: verify live; do not review
		 *    source and call it an audit.
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! function_exists( 'WC' ) ) {
			return false;
		}

		/*
		 * ⛔ REQUIREMENT 3: NO PROMPT FOR AN ALREADY-IDENTIFIED SHOPPER.
		 *    Two ways to be identified, and BOTH are checked:
		 *      · logged in  -> getCurrentUserEmail() resolves via
		 *        wp_get_current_user(), so there is nothing to capture.
		 *      · the mailchimp_user_email cookie is already set -> capture
		 *        already happened in this browser, on this surface or at
		 *        checkout. Never ask twice.
		 */
		if ( is_user_logged_in() ) {
			return false;
		}

		if ( ! empty( $_COOKIE[ self::MC_COOKIE ] ) ) {
			return false;
		}

		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return false;
		}

		return true;
	}

	/**
	 * Should the panel be SERVER-RENDERED into this page?
	 *
	 * ⛔ `/cart/` ONLY, AND THAT IS A CACHE CONSTRAINT, NOT A PREFERENCE.
	 *    See "THE CACHE PROBLEM" in the file header. Every other surface goes
	 *    through `handle_panel_ajax()`.
	 *
	 * @return bool
	 */
	public static function should_render() {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return false;
		}

		// is_checkout() is excluded explicitly as well as implicitly, because
		// the checkout email field and the 1.19.313 opt-in own that surface
		// and a second email ask there would compete with both.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return false;
		}

		return self::should_capture();
	}

	/**
	 * Should the SCRIPT be loaded on this request?
	 *
	 * ⛔⛔ EVERY CONDITION HERE IS CONSTANT FOR EVERY VISITOR OF A GIVEN URL,
	 *     AND THAT IS THE WHOLE POINT. The enqueue decision changes the
	 *     cached HTML. Gating it on cart contents or login state would bake
	 *     one visitor's state into the page other visitors are served.
	 *     ⭐ The per-visitor decision happens later, in the uncached fetch.
	 *
	 * @return bool
	 */
	public static function should_enqueue() {
		if ( ! apply_filters( 'bhp_cart_capture_enabled', true ) ) {
			return false;
		}

		if ( is_admin() || ! function_exists( 'WC' ) ) {
			return false;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return false;
		}

		// The cart page always needs it. Elsewhere it is needed only where the
		// drawer exists to put a panel into.
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		return function_exists( 'bhp_bundle_drawer_markup' );
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * ASSETS
	 * ═══════════════════════════════════════════════════════════════════════ */

	public static function enqueue() {
		if ( ! self::should_enqueue() ) {
			return;
		}

		$theme   = wp_get_theme();
		$version = (string) $theme->get( 'Version' );
		$dir     = get_template_directory();

		/**
		 * ⛔ VERSION IS THEME VERSION + FILE MTIME, AND THE MTIME HALF IS NOT
		 *    COSMETIC -- IT COST A FALSE "STILL BROKEN" READING.
		 *
		 * Versioning on the theme version alone means a patched asset keeps the
		 * SAME `?ver=` string. During the v1 build a corrected
		 * `early-cart-capture.js` was deployed to staging, the file on the
		 * server was byte-verified as the new one, and the BROWSER STILL RAN
		 * THE OLD CACHED COPY -- so a fixed bug read as unfixed. Purging the
		 * SiteGround cache does not help: this is the visitor's own HTTP cache.
		 */
		$css_rel = '/assets/css/early-cart-capture.css';
		$js_rel  = '/assets/js/early-cart-capture.js';

		$css_ver = $version;
		if ( file_exists( $dir . $css_rel ) ) {
			$css_ver .= '.' . filemtime( $dir . $css_rel );
		}

		$js_ver = $version;
		if ( file_exists( $dir . $js_rel ) ) {
			$js_ver .= '.' . filemtime( $dir . $js_rel );
		}

		wp_enqueue_style(
			'bhp-early-cart-capture',
			get_template_directory_uri() . $css_rel,
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'bhp-early-cart-capture',
			get_template_directory_uri() . $js_rel,
			array(),
			$js_ver,
			true
		);

		/*
		 * ⛔⛔ NOTHING PER-VISITOR GOES IN HERE ON A CACHEABLE PAGE.
		 *
		 * Every value below is identical for every visitor of this URL: two
		 * action names, an ajax URL, two storage prefixes, two integers and
		 * the copy strings. ⭐ THE NONCE IS THE EXCEPTION AND IT IS ADDED ONLY
		 * ON `/cart/`, which WooCommerce excludes from the page cache. On a
		 * cacheable page the nonce arrives with the fetched panel instead.
		 *
		 * ⛔ DO NOT ADD `is_user_logged_in()`, THE CART COUNT, OR ANY OTHER
		 *    PER-VISITOR FLAG TO THIS ARRAY.
		 */
		$data = array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'action'        => self::AJAX_ACTION,
			'panelAction'   => self::PANEL_ACTION,
			'storagePrefix' => self::STORAGE_PREFIX,
			'eventPrefix'   => self::EVENT_PREFIX,
			'variant'       => self::active_variant(),
			// Days a dismissal is respected for, on top of the session cap.
			'dismissDays'   => (int) apply_filters( 'bhp_cart_capture_dismiss_days', 30 ),
			'copy'          => self::active_copy(),
			'isCart'        => ( function_exists( 'is_cart' ) && is_cart() ) ? 1 : 0,
		);

		if ( self::should_render() ) {
			$data['nonce'] = wp_create_nonce( self::NONCE_ACTION );
		}

		wp_localize_script( 'bhp-early-cart-capture', 'bhpCartCapture', $data );
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * MARKUP
	 * ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * The panel, as a string, for either surface.
	 *
	 * ⛔ THE NONCE IS IN A HIDDEN INPUT, NOT ONLY IN THE LOCALISED CONFIG.
	 *    The drawer panel arrives without a config to put it in, so the markup
	 *    has to carry it. The cart-page panel carries it too, and the script
	 *    prefers the one in the form, so there is ONE code path rather than
	 *    two that can drift.
	 *
	 * @param string $surface 'cart' or 'drawer'. Presentation only.
	 * @return string
	 */
	public static function panel_html( $surface = 'cart' ) {
		$copy    = self::active_copy();
		$surface = in_array( $surface, array( 'cart', 'drawer' ), true ) ? $surface : 'cart';
		$uid     = 'bhp-cart-capture-' . $surface;

		ob_start();
		?>
		<div class="bhp-cart-capture bhp-cart-capture--<?php echo esc_attr( $surface ); ?>"
		     id="<?php echo esc_attr( $uid ); ?>"
		     data-bhp-cart-capture
		     data-surface="<?php echo esc_attr( $surface ); ?>"
		     data-variant="<?php echo esc_attr( self::active_variant() ); ?>">
			<div class="bhp-cart-capture__inner">
				<button type="button" class="bhp-cart-capture__close" aria-label="No thanks, hide this">&times;</button>

				<h2 class="bhp-cart-capture__heading"><?php echo esc_html( $copy['heading'] ); ?></h2>
				<p class="bhp-cart-capture__body"><?php echo esc_html( $copy['body'] ); ?></p>

				<form class="bhp-cart-capture__form" novalidate>
					<input type="hidden" class="bhp-cart-capture__nonce" name="nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>" />

					<div class="bhp-cart-capture__row">
						<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-email"><?php echo esc_attr( $copy['placeholder'] ); ?></label>
						<input
							type="email"
							id="<?php echo esc_attr( $uid ); ?>-email"
							class="bhp-cart-capture__email"
							name="email"
							autocomplete="email"
							inputmode="email"
							required
							placeholder="<?php echo esc_attr( $copy['placeholder'] ); ?>" />

						<button type="submit" class="bhp-cart-capture__submit"><?php echo esc_html( $copy['button'] ); ?></button>
					</div>

					<?php
					/*
					 * ⛔⛔ UNTICKED. THERE IS NO `checked` ATTRIBUTE HERE AND
					 *     THERE NEVER MAY BE. A pre-ticked marketing box is not
					 *     consent, it is a default, and the test suite fails the
					 *     build if `checked` appears anywhere in this file.
					 *
					 * ⭐ The label text is `optin_label()`, byte-identical to the
					 *    checkout field's. Do not localise one and not the other.
					 */
					?>
					<p class="bhp-cart-capture__optin">
						<input type="checkbox"
						       id="<?php echo esc_attr( $uid ); ?>-optin"
						       class="bhp-cart-capture__optin-input"
						       name="optin"
						       value="1" />
						<label class="bhp-cart-capture__optin-label" for="<?php echo esc_attr( $uid ); ?>-optin"><?php echo esc_html( $copy['optin_label'] ); ?></label>
					</p>

					<?php /* Honeypot. Same field name and semantics as the theme's other forms. */ ?>
					<div class="bhp-cart-capture__hp" aria-hidden="true">
						<label for="<?php echo esc_attr( $uid ); ?>-website">Website</label>
						<input type="text" id="<?php echo esc_attr( $uid ); ?>-website" name="bhp_website" tabindex="-1" autocomplete="off" />
					</div>
				</form>

				<p class="bhp-cart-capture__fine-print"><?php echo esc_html( $copy['fine_print'] ); ?></p>
				<p class="bhp-cart-capture__status" role="status" aria-live="polite"></p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Server-render for `/cart/` only. Moved into place after the cart block
	 * by the script.
	 *
	 * ⭐ IT IS INSERTED AS A SIBLING AFTER `.wp-block-woocommerce-cart`, NEVER
	 *    INSIDE IT. The cart is a React root; anything placed inside it is
	 *    destroyed on the next Store API re-render (every quantity change).
	 *    A sibling outside the root survives.
	 */
	public static function render() {
		if ( ! self::should_render() ) {
			return;
		}

		echo self::panel_html( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in panel_html().
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * ENDPOINTS
	 * ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * The uncached panel fetch. See "THE CACHE PROBLEM" in the file header.
	 *
	 * ⛔ NO NONCE IS REQUIRED TO CALL THIS, ON PURPOSE, and here is why that is
	 *    safe rather than sloppy: it MUTATES NOTHING. It returns a render
	 *    decision plus static copy plus a nonce that is bound to the CALLER'S
	 *    OWN session and is useless to anybody else. Requiring a nonce to
	 *    fetch a nonce is circular, and the alternative (printing one into a
	 *    cached page) is the exact bug this endpoint exists to avoid.
	 *
	 * ⛔ `nocache_headers()` IS NOT DECORATION. admin-ajax.php is not page
	 *    cached, but this response is per-visitor and must never be stored by
	 *    an intermediary either.
	 */
	public static function handle_panel_ajax() {
		nocache_headers();

		/*
		 * ⭐ THE CART HAS TO BE LOADED BEFORE IT CAN BE ASKED WHETHER IT IS
		 *    EMPTY. WooCommerce initialises the cart on a front-end request;
		 *    on `admin-ajax.php` it does not always, and `WC()->cart` is then
		 *    null — which `should_capture()` reads, correctly, as "no cart",
		 *    and suppresses the panel. Loading it here is idempotent: the guard
		 *    means an already-loaded cart is left exactly as it is.
		 */
		if ( function_exists( 'WC' ) && function_exists( 'wc_load_cart' ) && ! WC()->cart ) {
			wc_load_cart();
		}

		if ( ! self::should_capture() ) {
			wp_send_json( array( 'ok' => true, 'render' => false ), 200 );
		}

		// The drawer renders on checkout too (the plugin suppresses only its
		// opener there). A second email ask on that surface would compete with
		// the checkout field and the 1.19.313 opt-in, so it is refused.
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
		if ( $referer && function_exists( 'wc_get_checkout_url' ) ) {
			$checkout_path = (string) wp_parse_url( wc_get_checkout_url(), PHP_URL_PATH );
			$referer_path  = (string) wp_parse_url( $referer, PHP_URL_PATH );
			if ( $checkout_path && $referer_path && 0 === strpos( trailingslashit( $referer_path ), trailingslashit( $checkout_path ) ) ) {
				wp_send_json( array( 'ok' => true, 'render' => false ), 200 );
			}
		}

		wp_send_json(
			array(
				'ok'     => true,
				'render' => true,
				'html'   => self::panel_html( 'drawer' ),
			),
			200
		);
	}

	/**
	 * Rate limit, mirroring `bhp_quiz_signup_rate_limited()`'s shape so there
	 * is one house pattern rather than two.
	 *
	 * @return bool
	 */
	protected static function rate_limited() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		if ( '' === $ip ) {
			return false;
		}

		$key  = 'bhp_cc_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 24 );
		$hits = (int) get_transient( $key );
		$max  = (int) apply_filters( 'bhp_cart_capture_rate_limit', 8 );

		if ( $hits >= $max ) {
			return true;
		}

		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * ⭐ THE CART HALF OF THE FIX IS THE ONE CALL TO
	 *    `set_user_from_block_checkout()`.
	 *
	 * That is the PLUGIN'S OWN sanctioned entry point for "here is a guest
	 * email, treat it as the current user and push the cart" -- it is what
	 * the plugin's own Blocks integration calls at
	 * `blocks/woocommerce-blocks-integration.php:215` in
	 * `capture_from_store_api()`. Using it rather than reimplementing it
	 * means the cookie name, duration, path, SameSite flag, previous-email
	 * handling and cart-push sequencing all stay the vendor's problem.
	 *
	 * ⭐ AND IT IS BYTE-IDENTICAL IN 6.1.1 (staging) AND 6.2 (production),
	 *    md5 `008a6f70e7e2617c2cc4b131dc77ecd3`, re-verified over SSH on
	 *    2026-09-07. That matters because the two environments are on
	 *    different plugin versions, so a build validated on staging would
	 *    otherwise prove nothing about production.
	 *
	 * ⛔ NOTE WHAT IT DOES NOT TOUCH: `cart_subscribe`. Cart status therefore
	 *    stays `transactional` WHETHER OR NOT the box was ticked. That is not
	 *    a defect: the SUBSCRIPTION is carried by `bhp_process_signup()`
	 *    below, and the cart record is an ecommerce artefact, not a consent
	 *    record. See the long note in the file header.
	 */
	public static function handle_ajax() {
		$post = wp_unslash( $_POST );

		$nonce = isset( $post['nonce'] ) ? sanitize_text_field( $post['nonce'] ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json( array( 'ok' => false, 'code' => 'error' ), 403 );
		}

		if ( ! empty( $post['bhp_website'] ) ) {
			wp_send_json( array( 'ok' => false, 'code' => 'error' ), 400 );
		}

		if ( self::rate_limited() ) {
			wp_send_json( array( 'ok' => false, 'code' => 'rate_limited' ), 429 );
		}

		$email = isset( $post['email'] ) ? sanitize_email( trim( (string) $post['email'] ) ) : '';
		if ( ! $email || ! is_email( $email ) ) {
			wp_send_json( array( 'ok' => false, 'code' => 'invalid_email' ), 200 );
		}

		// Nothing to save if there is no cart. Guards a stale tab posting
		// after the cart was emptied in another one.
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json( array( 'ok' => false, 'code' => 'empty_cart' ), 200 );
		}

		/*
		 * ⛔ AN ABSENT CHECKBOX IS A "NO". A browser posts nothing at all for
		 *    an unchecked box, so absence and refusal are the same wire state
		 *    and both must mean NO. Never infer consent from a missing field.
		 */
		$optin = ! empty( $post['optin'] );

		$saved = false;

		if ( class_exists( 'MailChimp_Service' ) ) {
			/*
			 * ⛔⛔ DEFENCE IN DEPTH, AND IT SURVIVES v2 UNCHANGED.
			 *    `cart_subscribe` is only ever read from $_POST['subscribed'],
			 *    and this endpoint has no such field -- but a future caller, a
			 *    plugin, or a merged request could introduce one, and the cost
			 *    of it arriving is a contact subscribed without consent. So it
			 *    is unset explicitly for the duration of this call and restored
			 *    afterwards.
			 *
			 * ⭐ NOTE THAT THIS IS NOT WEAKENED BY THE OPT-IN BOX. Even a
			 *    TICKED shopper does not get `$_POST['subscribed']` set here.
			 *    The subscription travels by the house path below, where it can
			 *    be tagged, logged and reasoned about.
			 */
			$had_subscribed   = array_key_exists( 'subscribed', $_POST );
			$prior_subscribed = $had_subscribed ? $_POST['subscribed'] : null;
			unset( $_POST['subscribed'] );

			try {
				$service = MailChimp_Service::instance();
				$saved   = (bool) $service->set_user_from_block_checkout( $email );
			} catch ( \Throwable $e ) {
				// A Mailchimp outage must never break the cart page. The
				// shopper is told it did not save; nothing else changes.
				$saved = false;
			}

			if ( $had_subscribed ) {
				$_POST['subscribed'] = $prior_subscribed;
			}
		}

		/*
		 * ⭐ THE CONSENT IS RECORDED BEFORE THE NETWORK CALL AND INDEPENDENTLY
		 *    OF IT. A Mailchimp outage must not lose a choice the shopper
		 *    actually made; the order-meta mirror is what makes it durable.
		 */
		self::remember_optin( $optin );

		$subscribed = false;
		if ( $optin ) {
			$subscribed = self::subscribe( $email );
		}

		/**
		 * Fires after an early cart capture attempt.
		 *
		 * @param string $email      The captured address.
		 * @param bool   $saved      Whether the Mailchimp service accepted the cart.
		 * @param bool   $optin      Whether the marketing box was ticked.
		 * @param bool   $subscribed Whether the house signup path reported success.
		 */
		do_action( 'bhp_cart_capture_saved', $email, $saved, $optin, $subscribed );

		if ( ! $saved ) {
			wp_send_json( array( 'ok' => false, 'code' => 'unavailable' ), 200 );
		}

		$copy = self::active_copy();

		wp_send_json(
			array(
				'ok'      => true,
				'message' => $copy['success'],
			),
			200
		);
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * CONSENT
	 * ═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Put the cart-time choice in the WooCommerce session so it can reach the
	 * eventual order.
	 *
	 * ⛔ THE SESSION, NOT A COOKIE OF OUR OWN. WooCommerce already carries a
	 *    session for any shopper with a cart, and the ONLY shopper who can
	 *    reach this code has a cart. A second cookie would be a second consent
	 *    surface to keep in step, and this file's whole job is to not create
	 *    one of those.
	 *
	 * @param bool $optin
	 */
	protected static function remember_optin( $optin ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		WC()->session->set( self::SESSION_OPTIN, $optin ? 'yes' : 'no' );
		WC()->session->set( self::SESSION_OPTIN_AT, current_time( 'mysql', true ) );
	}

	/**
	 * The tags a CART-TIME opt-in carries into Mailchimp.
	 *
	 * ⛔⛔ DELIBERATELY NOT `Adventure Club`. That tag is what the parent
	 *     acquisition funnel (journey 89) listens on, and that journey's FIRST
	 *     email carries the parent funnel coupon. Tagging a shopper who is mid-cart into it
	 *     would email a discount code to somebody who is about to pay full
	 *     price. `inc/checkout-optin-sync.php` records the same reasoning for
	 *     the same reason; this is one rule, applied twice, not two rules.
	 *
	 * ⛔⛔ AND DELIBERATELY NOT `Customer - Purchased`. THEY HAVE NOT
	 *     PURCHASED. Writing that tag here would be a fabricated fact about a
	 *     real person, which is the never-invent rule and not a nitpick. It is
	 *     also the exact defect probe order 5689 recorded at checkout, where a
	 *     failed payment had already been tagged as a purchase.
	 */
	public static function optin_tags() {
		return apply_filters(
			'bhp_cart_capture_optin_tags',
			array(
				'Source: Cart Capture',
			)
		);
	}

	/**
	 * Swap the default signup tags for the cart-capture tags on this context
	 * only.
	 *
	 * ⛔ SCOPED BY CONTEXT. Every other signup surface on the site keeps the
	 *    tags it has always had; this callback returns `$tags` untouched for
	 *    them.
	 *
	 * @param array  $tags
	 * @param string $context
	 * @return array
	 */
	public static function filter_signup_tags( $tags, $context ) {
		if ( 'cart_capture_optin' !== $context ) {
			return $tags;
		}

		return self::optin_tags();
	}

	/**
	 * Subscribe a shopper who ticked the box.
	 *
	 * ⭐ THE HOUSE PATH, THE SAME ONE `inc/checkout-optin-sync.php` USES. See
	 *    the long note in the file header for why the plugin's own opt-in path
	 *    is unreachable from here.
	 *
	 * ⛔ `lead_magnet` IS EMPTY ON PURPOSE. A lead-magnet value is what enrols
	 *    a contact in an acquisition journey. Subscribing is not enrolling.
	 *
	 * ⛔ IT CAN NEVER BREAK THE CART. Every path is wrapped. A Mailchimp
	 *    outage, a missing key or a thrown exception returns false and the
	 *    shopper's cart is still saved. On staging this ALWAYS returns false,
	 *    because staging has no API key -- `bhp_process_signup()` returns
	 *    `unavailable` and fires `bhp_mailchimp_signup_rejected`, which is how
	 *    the suite observes that this path was taken without a network call.
	 *
	 * @param string $email
	 * @return bool
	 */
	protected static function subscribe( $email ) {
		if ( ! function_exists( 'bhp_process_signup' ) ) {
			return false;
		}

		try {
			$result = bhp_process_signup(
				array(
					'email'         => $email,
					'name'          => '',
					'context'       => 'cart_capture_optin',
					'audience_type' => 'parents_families',
					'lead_magnet'   => '',
					'source_page'   => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ),
					'require_name'  => false,
				)
			);

			return ! empty( $result['ok'] );
		} catch ( \Throwable $e ) {
			do_action( 'bhp_cart_capture_optin_exception', $e, $email );
			return false;
		}
	}

	/**
	 * Carry the cart-time consent onto the eventual order.
	 *
	 * ⛔⛔ PRIORITY 25, AFTER `bhp_store_marketing_consent_meta()` AT 20.
	 *     That function writes `_bhp_new_book_releases_optin` to 'yes' OR 'no'
	 *     for EVERY order from the checkout field. Running before it would be
	 *     silently overwritten.
	 *
	 * ⭐ TWO KEYS, AND THE SECOND ONE IS THE HONEST ONE:
	 *    · `_bhp_cart_capture_optin` is PROVENANCE. It records what happened
	 *      at the cart, always, whatever the checkout field said. It is never
	 *      overwritten and nothing else writes it.
	 *    · `_bhp_new_book_releases_optin` is the SHARED mirror the rest of the
	 *      system reads. It is only ever UPGRADED here, 'no' to 'yes'. This
	 *      function NEVER writes 'no' and never unsubscribes anybody.
	 *
	 * ⭐⭐ DECIDED BY ANDREW SIGNORE, 2026-09-07, SEAL 1225. This was an open
	 *     question when the file was prepared; it is not one now, and the
	 *     ruling is written HERE, at the `'yes' !== $existing` line it governs,
	 *     rather than in a report the next reader will not open.
	 *
	 * ⭐ THE RULE, IN HIS TERMS: **ANY TICK SUBSCRIBES. AN UNTICKED CHECKOUT
	 *    BOX DOES NOT UNDO A CART TICK.** A shopper who ticks at the cart and
	 *    then leaves the checkout box unticked ends up 'yes', and that is the
	 *    intended outcome, not a tolerated side effect.
	 *
	 * ⭐ WHY IT IS COHERENT: the cart tick ALREADY SUBSCRIBED THEM at capture
	 *    time. The order meta is a RECORD of an event that happened, not the
	 *    trigger for one, and not ticking a second box is not a withdrawal of
	 *    a consent already given. Withdrawal has its own path (unsubscribe),
	 *    and this function has never had the power to use it: it writes 'yes'
	 *    or nothing, never 'no'.
	 *
	 * ⛔ SUPERSEDED READING, PRESERVED SO THE CHANGE IS VISIBLE RATHER THAN
	 *    SILENT: ~~the opposite position is defensible too, and switching to it
	 *    is one line: drop the `'yes' !== $existing` upgrade below~~. ⛔ DO NOT
	 *    DROP IT. Dropping that guard now contradicts a founder ruling.
	 *
	 * ⚠ WHAT THIS RULING DOES NOT DO: it does not let an UNTICKED shopper be
	 *    subscribed by anything on this surface, and it does not let the cart
	 *    write 'no' over a checkout 'yes'. Both remain asserted in the suite.
	 *
	 * @param int $order_id
	 */
	public static function mirror_optin_to_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$choice = (string) WC()->session->get( self::SESSION_OPTIN, '' );
		if ( '' === $choice ) {
			return;
		}

		// Provenance, always, whatever the checkout field said.
		$order->update_meta_data( self::META_PROVENANCE, 'yes' === $choice ? 'yes' : 'no' );
		$at = (string) WC()->session->get( self::SESSION_OPTIN_AT, '' );
		if ( $at ) {
			$order->update_meta_data( '_bhp_cart_capture_optin_at', $at );
		}

		if ( 'yes' === $choice ) {
			$existing = (string) $order->get_meta( self::META_MIRROR );
			if ( 'yes' !== $existing ) {
				$order->update_meta_data( self::META_MIRROR, 'yes' );
				$order->update_meta_data( '_bhp_marketing_consent_timestamp', $at ? $at : current_time( 'mysql', true ) );
				$order->update_meta_data( '_bhp_marketing_consent_source', 'cart_capture' );
			}
		}

		$order->save();
	}
}

BHP_Early_Cart_Capture::init();
