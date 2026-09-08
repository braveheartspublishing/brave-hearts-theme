<?php
/**
 * EARLY CART CAPTURE — theme 1.19.316, 2026-08-28.
 * `CYCLE168-CX-EARLY-CART-CAPTURE`, implementing step 7 of
 * `Business OS\WORKING-DRAFTS\commerce-cx\
 *  CYCLE168-CX-ABANDONED-CART-DIAGNOSIS-2026-08-28.md`.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE PROBLEM THIS SOLVES
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * `mailchimp-for-woocommerce` cannot create an abandoned-cart record until
 * `MailChimp_Service::getCurrentUserEmail()` resolves. For a guest that
 * resolves ONLY from the `mailchimp_user_email` COOKIE
 * (`getEmailFromSession()` -> `cookie('mailchimp_user_email', false)` ->
 * `$_COOKIE`). On this store that cookie is first written when the buyer
 * types into the Blocks checkout email field, which the production logs put
 * at a MEDIAN 132 SECONDS BEFORE PAYMENT.
 *
 * Consequence, measured: in the 30-day production log window, 22 carts
 * reached Mailchimp and 20 were deleted within minutes by the order that
 * completed them. Exactly ONE genuinely-abandoned cart has existed since the
 * journey went Active on 2026-08-04. The entire upper funnel (add to cart,
 * browse, leave) is INVISIBLE to Mailchimp, not delayed.
 *
 * This file creates an earlier, opt-in moment where a shopper can hand over
 * an email to have their cart saved, so a cart record can exist for someone
 * who never reaches checkout.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ THE CONSENT RAIL — THIS IS THE PART THAT MUST NOT DRIFT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ TYPING AN EMAIL TO SAVE A CART IS NOT CONSENT TO MARKETING.
 *
 * The contact MUST land `transactional` in Mailchimp and MUST NEVER be
 * auto-subscribed by this path. That guarantee is STRUCTURAL, not a setting
 * anyone has to remember, and here is the exact chain that makes it so
 * (traced in the vendor source on BOTH environments, 2026-08-28):
 *
 *   class-mailchimp-woocommerce-cart-update.php:193
 *       $subscriber_status = $this->status ? 'subscribed' : 'transactional';
 *   $this->status  <- $handler->setStatus($this->cart_subscribe)   service:284
 *   $cart_subscribe is populated in EXACTLY ONE PLACE:              service:1232
 *       $this->cart_subscribe = (bool) $_POST['subscribed'];
 *
 * `$_POST['subscribed']` is the MAILCHIMP PLUGIN'S OWN opt-in checkbox. This
 * theme deliberately uses its own checkout checkbox instead
 * (`inc/checkout-optin-sync.php`, CYCLE168-LD-CHECKOUT-OPTIN, 1.19.313), so
 * `cart_subscribe` is never populated and cart-time status is
 * `transactional` BY CONSTRUCTION.
 *
 * ⛔ THEREFORE THIS FILE NEVER SETS, FORWARDS OR SYNTHESISES
 *    `$_POST['subscribed']`, AND MUST NEVER BE MADE TO. The test suite
 *    asserts this. If you are adding a marketing tick-box here, it does NOT
 *    go through this path -- see "DELIBERATE NON-GOAL" below.
 *
 * ⭐ `mailchimp_auto_subscribe` (which is "1" on PRODUCTION) CANNOT REACH
 *    THIS PATH. Verified by enumerating every use of that option in the
 *    plugin, 2026-08-28, both environments. It appears in exactly three
 *    places and none of them is the cart processor:
 *      bootstrap.php:694                    mailchimp_sync_existing_contacts_only(), === '2'
 *      processes/...-single-order.php:134   the ORDER processor
 *      processes/...-single-customer.php:128 the CUSTOMER processor
 *    The cart processor derives status solely from `$this->status` above.
 *
 * ⚠ DELIBERATE NON-GOAL: there is NO marketing opt-in tick-box on this
 *   surface in v1. The brief permitted an optional unticked one. It is
 *   omitted on purpose: promoting a contact to `subscribed` requires the
 *   theme's own signup path (`bhp_process_signup()`), which carries the
 *   parent/teacher funnel tagging that belongs to another lane, and adding a
 *   second consent surface here widens the blast radius of the one thing
 *   this file must not get wrong. Recorded for Andrew as a follow-up, not
 *   silently dropped.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHY THIS RENDERS ON THE CART PAGE ONLY (it is a CACHE constraint)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * SiteGround's page cache stores rendered HTML and varies only on
 * Accept-Encoding -- the same fact that forced `BHP_Consent::default_signals()`
 * to be constant for every visitor (observed live on production 2026-08-04,
 * mis-serving in BOTH directions). Any per-visitor server-rendered markup is
 * therefore served to the wrong visitors.
 *
 * This surface's visibility depends on per-visitor state (is the cart empty,
 * is this a guest, was it already dismissed) and it carries a nonce. Render
 * it on a cacheable page and the cache will hand one visitor's nonce and one
 * visitor's cart state to another.
 *
 * ⭐ SO IT RENDERS ONLY WHERE WooCommerce ALREADY SUPPRESSES THE CACHE: the
 *    cart page. That is also the highest-intent moment, so the constraint and
 *    the conversion judgement point the same way.
 *
 * ⚠ A product-page "you just added this" variant is a REAL v2 idea and is
 *   deliberately NOT built here, because it would have to render on cached
 *   pages and every per-visitor decision would have to move to an uncached
 *   fetch first. Do not add it by copying this file onto another hook.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * FUNNEL ISOLATION (.claude/rules/funnels.md)
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Storage prefix `bhp_cart_capture`, event prefix `cart_capture`. Both are
 * distinct from the parent funnel (`bhp_parent_popup` / `parent_popup`) and
 * the teacher funnel (`bhp_mariana_popup` / `teacher_popup`). This surface is
 * NOT a third popup and deliberately does NOT use `assets/js/mariana-popup.js`
 * or its `data-popup-config` schema -- it is a cart-page panel with different
 * mechanics, and forking that engine is exactly what the rules file forbids.
 */

defined( 'ABSPATH' ) || exit;

class BHP_Early_Cart_Capture {

	const NONCE_ACTION = 'bhp_cart_capture';
	const AJAX_ACTION  = 'bhp_cart_capture';

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
		 * staging (2026-08-28, `window.innerWidth` 1280): the panel sat in
		 * `.site-wrapper`, never moved, never revealed after 9s.
		 *
		 * ⭐ Priority 5 puts the markup ahead of the footer scripts. The JS also
		 *    carries its own DOM-ready guard now, so neither fix alone is
		 *    load-bearing -- but do not "tidy" this back to 20.
		 */
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 5 );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'handle_ajax' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'handle_ajax' ) );
	}

	/**
	 * ⭐ THE THREE COPY VARIANTS. Andrew picks one; the pick is a one-line
	 *    filter, not an edit to markup.
	 *
	 * ⛔ COPY DISCIPLINE APPLIED HERE, AND IT IS LOAD-BEARING:
	 *    · First person. No "we"/"us"/"our" -- Standing Rules 9.1, he is the
	 *      sole operator.
	 *    · No em dashes.
	 *    · No outcome claims, no statistics, no testimonials.
	 *    · ⭐ AND NO PROMISE THE SYSTEM MAY NOT KEEP. Saving the cart is what
	 *      this code actually does, so the copy promises that. Whether a
	 *      reminder email ever arrives depends on Andrew's Mailchimp journey,
	 *      which is Active but has never been observed to fire (exactly one
	 *      eligible cart has existed, and it produced no start). So the
	 *      reminder is worded as "can", never as a commitment. Promising a
	 *      delivery nobody has observed would be a fabricated claim.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function copy_variants() {
		return array(
			// A -- the plainest. Leads with the offer, not the ask.
			'a' => array(
				'heading'     => 'Want me to save your cart?',
				'body'        => 'Leave me your email and I will keep these books here for you, so you can come back and finish whenever it suits you.',
				'placeholder' => 'Your email address',
				'button'      => 'Save my cart',
				'fine_print'  => 'I use this to save your cart and I can send you a reminder link if you do not make it back. I will not add you to my newsletter unless you ask me to.',
				'success'     => 'Saved. Your cart will be waiting for you.',
			),
			// B -- frames it as a convenience the shopper performs on themselves.
			'b' => array(
				'heading'     => 'Keep this cart for later',
				'body'        => 'I can hold these books for you so nothing gets lost if you want to think it over first.',
				'placeholder' => 'Your email address',
				'button'      => 'Hold my cart',
				'fine_print'  => 'Only used to hold your cart and, if you do not come back, to send you a link to it. No newsletter unless you choose one later.',
				'success'     => 'Done. I am holding your cart for you.',
			),
			// C -- names the hesitation out loud, which is the honest read of
			//      what a parent on this page is actually doing.
			'c' => array(
				'heading'     => 'Not ready to check out yet?',
				'body'        => 'That is completely fine. Leave me your email and I will keep this cart exactly as you left it.',
				'placeholder' => 'Your email address',
				'button'      => 'Keep my cart',
				'fine_print'  => 'This saves your cart and lets me send you a link back to it. It does not sign you up for anything else.',
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
		return $variants[ self::active_variant() ];
	}

	/**
	 * ⛔ THE RENDER GATE. Every condition here is a reason NOT to nag.
	 *
	 * @return bool
	 */
	public static function should_render() {
		// Feature kill switch, so this can be turned off without a deploy.
		if ( ! apply_filters( 'bhp_cart_capture_enabled', true ) ) {
			return false;
		}

		if ( is_admin() || ! function_exists( 'WC' ) ) {
			return false;
		}

		// ⛔ Cart page only. See the cache note in the file header. is_checkout()
		//    is excluded explicitly as well as implicitly, because the checkout
		//    email field and the 1.19.313 opt-in own that surface and a second
		//    email ask there would compete with both.
		if ( ! function_exists( 'is_cart' ) || ! is_cart() || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			return false;
		}

		// A logged-in shopper's email already resolves in getCurrentUserEmail()
		// via wp_get_current_user(), so there is nothing to capture and asking
		// would be pure friction.
		if ( is_user_logged_in() ) {
			return false;
		}

		// Already captured in this browser. Never ask twice.
		if ( ! empty( $_COOKIE[ self::MC_COOKIE ] ) ) {
			return false;
		}

		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return false;
		}

		return true;
	}

	public static function enqueue() {
		if ( ! self::should_render() ) {
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
		 * SAME `?ver=` string. During this build a corrected
		 * `early-cart-capture.js` was deployed to staging, the file on the
		 * server was byte-verified as the new one, and the BROWSER STILL RAN THE
		 * OLD CACHED COPY -- so a fixed bug read as unfixed. Purging the
		 * SiteGround cache does not help: this is the visitor's own HTTP cache.
		 *
		 * ⭐ mtime changes on every deploy of a changed file and never changes
		 *    otherwise, so this busts exactly when it should and keeps the cache
		 *    warm the rest of the time.
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

		wp_localize_script(
			'bhp-early-cart-capture',
			'bhpCartCapture',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'action'        => self::AJAX_ACTION,
				'nonce'         => wp_create_nonce( self::NONCE_ACTION ),
				'storagePrefix' => self::STORAGE_PREFIX,
				'eventPrefix'   => self::EVENT_PREFIX,
				'variant'       => self::active_variant(),
				// Seconds of dwell before the panel reveals itself. Not a
				// modal, not an interrupt -- it is already in the page, this
				// only controls when it becomes visible.
				'revealAfter'   => (int) apply_filters( 'bhp_cart_capture_reveal_after', 8 ),
				// Days a dismissal is respected for.
				'dismissDays'   => (int) apply_filters( 'bhp_cart_capture_dismiss_days', 30 ),
				'copy'          => self::active_copy(),
			)
		);
	}

	/**
	 * Rendered into wp_footer, then moved into place after the cart block by
	 * the script.
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

		$copy = self::active_copy();
		?>
		<div class="bhp-cart-capture" id="bhp-cart-capture" hidden data-variant="<?php echo esc_attr( self::active_variant() ); ?>">
			<div class="bhp-cart-capture__inner">
				<button type="button" class="bhp-cart-capture__close" aria-label="No thanks, hide this">&times;</button>

				<h2 class="bhp-cart-capture__heading"><?php echo esc_html( $copy['heading'] ); ?></h2>
				<p class="bhp-cart-capture__body"><?php echo esc_html( $copy['body'] ); ?></p>

				<form class="bhp-cart-capture__form" novalidate>
					<label class="screen-reader-text" for="bhp-cart-capture-email"><?php echo esc_attr( $copy['placeholder'] ); ?></label>
					<input
						type="email"
						id="bhp-cart-capture-email"
						class="bhp-cart-capture__email"
						name="email"
						autocomplete="email"
						inputmode="email"
						required
						placeholder="<?php echo esc_attr( $copy['placeholder'] ); ?>" />

					<?php /* Honeypot. Same field name and semantics as the theme's other forms. */ ?>
					<div class="bhp-cart-capture__hp" aria-hidden="true">
						<label for="bhp-cart-capture-website">Website</label>
						<input type="text" id="bhp-cart-capture-website" name="bhp_website" tabindex="-1" autocomplete="off" />
					</div>

					<button type="submit" class="bhp-cart-capture__submit"><?php echo esc_html( $copy['button'] ); ?></button>
				</form>

				<p class="bhp-cart-capture__fine-print"><?php echo esc_html( $copy['fine_print'] ); ?></p>
				<p class="bhp-cart-capture__status" role="status" aria-live="polite"></p>
			</div>
		</div>
		<?php
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
	 * ⭐ THE WHOLE FIX IS THE ONE CALL TO `set_user_from_block_checkout()`.
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
	 *    verified by diffing both copies over SSH on 2026-08-28. That matters
	 *    because the two environments are on different plugin versions right
	 *    now, so a build validated on staging would otherwise prove nothing
	 *    about production.
	 *
	 * ⛔ NOTE WHAT IT DOES NOT TOUCH: `cart_subscribe`. Status therefore stays
	 *    `transactional`. See the consent rail in the file header.
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

		$saved = false;

		if ( class_exists( 'MailChimp_Service' ) ) {
			// ⛔ DEFENCE IN DEPTH. `cart_subscribe` is only ever read from
			//    $_POST['subscribed'], and this endpoint has no such field --
			//    but a future caller, a plugin, or a merged request could
			//    introduce one, and the cost of it arriving is a contact
			//    subscribed without consent. So it is unset explicitly for the
			//    duration of this call and restored afterwards.
			$had_subscribed  = array_key_exists( 'subscribed', $_POST );
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

		/**
		 * Fires after an early cart capture attempt.
		 *
		 * @param string $email The captured address.
		 * @param bool   $saved Whether the Mailchimp service accepted it.
		 */
		do_action( 'bhp_cart_capture_saved', $email, $saved );

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
}

BHP_Early_Cart_Capture::init();
